#!/usr/bin/env python3
"""CommsChannel CDR AGI (hangup handler). Posts the completed call to the portal
/switch/cdr, which rates it + debits balance + releases the prepaid credit hold
(the portal's RatingService; idempotent on call_uuid). Keyed on the
call_key set at route time so both legs collapse to one rated CDR / one debit."""
import sys, re, urllib.request, urllib.parse

def read_env():
    while True:
        line = sys.stdin.readline()
        if line == '' or line == '\n':
            break

def cmd(c):
    sys.stdout.write(c + "\n"); sys.stdout.flush()
    return sys.stdin.readline()

def getvar(n):
    m = re.search(r'result=1 \((.*)\)', cmd('GET VARIABLE %s' % n))
    return m.group(1) if m else ''

read_env()
callkey = getvar('CC_CALLKEY')
if not callkey:
    sys.exit(0)

params = {
    'call_uuid': callkey,
    'account_id': getvar('CC_ACCOUNT'),
    'carrier_id': getvar('CC_CARRIER'),
    'direction': getvar('CC_DIR'),
    'destination_number': getvar('CC_DEST'),
    'caller_id_number': getvar('CALLERID(num)'),
    'billsec': getvar('CDR(billsec)') or '0',
}
url = 'http://127.0.0.1:8080/switch/cdr?' + urllib.parse.urlencode(params)
try:
    urllib.request.urlopen(url, timeout=4).read()
except Exception:
    pass
