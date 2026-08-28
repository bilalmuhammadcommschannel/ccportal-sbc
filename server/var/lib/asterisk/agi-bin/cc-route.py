#!/usr/bin/env python3
"""CommsChannel routing AGI for chan_sip (the loopback media anchor).
Reuses the portal's /switch/route JSON decision: reads the X-CC-* headers
Kamailio set, asks the portal how to route, sets the
Dial string + caller-id + duration cap + custom headers + billing vars. The
matching cc-cdr.py hangup handler posts /switch/cdr to rate + debit the call.
nginx on :8080 injects the switch shared-secret, so this calls plain loopback."""
import sys, re, json, urllib.request, urllib.parse

def read_env():
    e = {}
    while True:
        line = sys.stdin.readline()
        if line == '' or line == '\n':
            break
        if ':' in line:
            k, v = line.split(':', 1)
            e[k.strip()] = v.strip()
    return e

def cmd(c):
    sys.stdout.write(c + "\n"); sys.stdout.flush()
    return sys.stdin.readline()

def getvar(n):
    m = re.search(r'result=1 \((.*)\)', cmd('GET VARIABLE %s' % n))
    return m.group(1) if m else ''

def setvar(n, v):
    cmd('SET VARIABLE %s "%s"' % (n, str(v).replace('"', '')))

def execapp(app, data):
    cmd('EXEC %s "%s"' % (app, str(data).replace('"', '')))

def verbose(m):
    cmd('VERBOSE "%s" 1' % str(m).replace('"', ''))

env = read_env()
dest = env.get('agi_extension', '')
callid = env.get('agi_uniqueid', '')
direction = getvar('cc_dir').lower()
user = getvar('cc_user')
src = getvar('cc_src')
# Kamailio marks carrier calls X-CC-Direction: inbound and authenticated
# customer calls with X-CC-User. Fall back sanely if a header is absent.
if direction != 'inbound':
    direction = 'outbound' if user else 'inbound'

# src_ip goes both ways: outbound uses it to resolve the endpoint; inbound uses
# it to attribute the CDR to the carrier that actually delivered the call (the
# X-CC-Src Kamailio stamped = the trusted carrier source IP).
params = {'direction': direction, 'destination': dest, 'call_key': callid, 'src_ip': src}
if direction == 'outbound':
    params['auth_user'] = user

url = 'http://127.0.0.1:8080/switch/route?' + urllib.parse.urlencode(params)
try:
    with urllib.request.urlopen(url, timeout=4) as r:
        d = json.loads(r.read().decode())
except Exception as ex:
    verbose('CC route error: %s' % ex)
    setvar('ROUTE_ACTION', 'reject')
    setvar('ROUTE_HANGUP_CAUSE', '38')      # NETWORK_OUT_OF_ORDER
    sys.exit(0)

if d.get('action') != 'route':
    verbose('CC route reject: %s' % d.get('cause', ''))
    setvar('ROUTE_ACTION', 'reject')
    setvar('ROUTE_HANGUP_CAUSE', '21')      # CALL_REJECTED
    sys.exit(0)

# billing/CDR vars (consumed by cc-cdr.py on hangup)
setvar('CC_CALLKEY', d.get('call_key', callid))
setvar('CC_ACCOUNT', d.get('account_id', ''))
setvar('CC_CARRIER', d.get('carrier_id', ''))
setvar('CC_DIR', direction)
setvar('CC_DEST', dest)

maxsec = int(d.get('max_sec') or 0)
setvar('CC_DIALOPTS', 'L(%d000)' % maxsec if maxsec > 0 else '')

if direction == 'inbound':
    # deliver to the registered endpoint via Kamailio usrloc: present the DID and
    # the real AoR so Kamailio's loopback-anchor branch looks up the contact.
    execapp('SIPAddHeader', 'X-CC-AOR: %s' % d.get('target_aor', ''))
    execapp('SIPAddHeader', 'X-CC-DID: %s' % d.get('ruri_user', dest))
    setvar('DIALSTRING', 'SIP/%s@kamailio' % d.get('ruri_user', dest))
else:
    cid = d.get('caller_id', '')
    if cid:
        setvar('CALLERID(num)', cid)     # -> From URI user part (the actual CLI)
        setvar('CALLERID(name)', cid)
    for h in (d.get('headers') or []):
        execapp('SIPAddHeader', '%s: %s' % (h.get('name'), h.get('value')))
    # terminate to the carrier gateway the portal returned (IP-authenticated)
    setvar('DIALSTRING', 'SIP/%s@%s' % (d.get('ruri_user', dest), d.get('ruri_host', '')))

setvar('ROUTE_ACTION', 'route')
