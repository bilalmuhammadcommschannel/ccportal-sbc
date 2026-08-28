# The media anchor: why Asterisk 18 chan_sip

## The problem

Some PBXes (Yeastar Cloud PBX is the reference offender) send **mixed-ptime
G.711** on non-8kHz-sourced audio — IVR prompts, music-on-hold, announcements.
Instead of a steady stream of 20ms frames (160 bytes of PCM), they alternate
20ms and 40ms frames (160B / 320B). A pure SIP proxy + RTP relay passes those
irregular frames straight through, and the far end (PSTN carrier / softphone)
plays them back with audible stutter, clicks, and choppy MoH.

The signalling is fine. The call connects, bills correctly, and stays up. Only
the **audio** is wrong, and only in the direction where the mis-framing PBX is
the source. It is purely a media-framing defect.

## The fix

Put a back-to-back user agent (B2BUA) in the media path that **terminates the
RTP and re-frames it to a constant 20ms ptime** before sending it on. That one
transformation makes the stream regular and the far end plays it smoothly —
even across the residual packet loss that the irregular stream had been
amplifying.

In this stack that B2BUA is **Asterisk 18 with `chan_sip`**, sitting on loopback
behind Kamailio.

## Why chan_sip specifically (what we ruled out)

Every one of these was tried against live Yeastar → PSTN calls:

| Component | Result | Why |
|---|---|---|
| **rtpengine** | ✗ still choppy | Pure relay. Forwards packets 1:1; never repacketizes (the RTP SSRC never even changes). |
| **chan_pjsip** (Asterisk) | ✗ still choppy | Native RTP passthrough. Even with transcode + jitterbuffer forced, it preserves the source ptime. |
| **FreeSWITCH** | ✗ ~half the audio dropped | Re-frames, but cannot cleanly absorb *asynchronous* ptime input — it logs `CBR: Asynchronous PTIME not supported` and drops frames. |
| **chan_sip** on Asterisk 20 | ✗ intermittent (~1 call in 5 had audio) | chan_sip is deprecated in 20 and unstable there; symmetric-RTP / strictrtp tweaks made it worse. |
| **chan_sip** on Asterisk 18 | ✓ clean, every call | Terminates + re-frames to constant 20ms reliably. This is what MagnusBilling's Asterisk 13 has done for years. |

`chan_sip` is deprecated and ships **noload-by-default** in Asterisk 18/20, and
Debian's `asterisk` package is `chan_pjsip`-only. So we pin the last solid 18.x
release, build it from source with `chan_sip` explicitly enabled, and run the
PJSIP stack `noload`. See [`../scripts/build-asterisk-chansip.sh`](../scripts/build-asterisk-chansip.sh).

## How it wires together

```
  carrier / customer  ──public SIP──▶  Kamailio (public edge, auth, F1 guard)
                                          │  loopback leg (udp:127.0.0.1)
                                          ▼
                                Asterisk 18 chan_sip  (udpbindaddr 0.0.0.0:5065)
                                          │  AGI: cc-route.py  → portal /switch/route
                                          │  hangup: cc-cdr.py → portal /switch/cdr
                                          ▼
                                bridges back out through Kamailio to the carrier
```

Key wiring details, and why each is the way it is:

- **`udpbindaddr=0.0.0.0:5065`** (not `127.0.0.1`). chan_sip binds its RTP
  sockets to the same address as its SIP bind. Bound to loopback it can only
  send/receive media on `127.0.0.1` — no external audio. Bound to `0.0.0.0` it
  can receive RTP on the public IP while its SIP leg still only talks to
  Kamailio over `lo`. UDP 5065 is firewalled from the public internet; Kamailio
  reaches it via loopback.

- **SDP rewrite in Kamailio.** Because chan_sip's SIP leg is on loopback, it
  advertises `127.0.0.1` in the SDP `o=` and `c=` lines. Kamailio rewrites that
  to the public IP on the way out, in both directions:
  ```
  subst_body('/IN IP4 127.0.0.1/IN IP4 <public-ip>/g');
  ```
  The `/g` flag is **required** — both `o=` and `c=` carry `127.0.0.1`, and a
  first-match-only substitution leaves the connection line wrong and kills audio.
  This lives in `request_route` (requests the anchor sources outward) and
  `onreply_route` (its 200 OK / provisional answers). It is a no-op on real
  far-end messages, which already carry public IPs.

- **`directmedia=no`.** The anchor must never let the two ends talk directly, or
  the re-framing it exists to do is bypassed.

- **`insecure=port,invite`** on the `[kamailio]` peer. Kamailio has already
  authenticated the call; the loopback leg must not be re-challenged.

- **G.711 only** (`allow=ulaw,alaw`, `disallow=all`). Matches the carrier/PBX
  codec set; no transcoding surprises.

- **RTP range 20000–60000** in `rtp.conf` must match the range the firewall
  (`nftables.conf`) permits for media.

- **Routing + billing stay in the portal.** chan_sip doesn't know tariffs. The
  dialplan calls `cc-route.py` (AGI) which hits the portal's existing
  `/switch/route` endpoint for the routing + rating decision, and a hangup
  handler calls `cc-cdr.py` → `/switch/cdr` to rate the completed call and debit
  the prepaid balance. Both are keyed on a single call-key so the two legs
  collapse to one rated CDR / one debit, idempotently.

## Build gotchas (Debian 13 / gcc-14)

The build script handles both of these; they are documented here so a failed
build is diagnosable:

1. **`gethostbyname_r` redeclaration.** On trixie/gcc-14, Asterisk 18's
   `configure` mis-probes `gethostbyname_r` and emits a conflicting declaration
   in `main/utils.c`; the build dies with `Error 1` on `utils.o`. Fix: force the
   6-arg glibc prototype in `include/asterisk/autoconfig.h`
   (`#define HAVE_GETHOSTBYNAME_R_6 1`).

2. **Stale modules from a previous Asterisk crash the new one.** If a different
   Asterisk (e.g. a prior chan_pjsip-20 experiment) left `.so` modules behind,
   the fresh 18 binary logs `Module init failed` on modules like
   `res_websocket_client.so` and **exits** on startup. Fix: clear
   `/usr/lib/asterisk/modules/*.so` before `make install`.
