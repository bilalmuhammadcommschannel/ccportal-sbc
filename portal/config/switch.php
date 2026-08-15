<?php
return [
    // shared secret FreeSWITCH presents to the /switch/* endpoints
    'secret' => env('SWITCH_SHARED_SECRET', ''),
    // default termination carrier_id when LCR finds no cheaper match (optional)
    'default_carrier_id' => env('SWITCH_DEFAULT_CARRIER', ''),
    // SIP domain endpoints register against
    'sip_domain' => env('SWITCH_SIP_DOMAIN', 'sip.example.com'),
    // what a device should actually send to. Shown verbatim on the endpoint's
    // "Device configuration" panel, so it must match what Kamailio listens on.
    'sip_proxy'      => env('SWITCH_SIP_PROXY', '127.0.0.1'),
    'sip_port'       => (int) env('SWITCH_SIP_PORT', 5060),
    'sip_tls_port'   => (int) env('SWITCH_SIP_TLS_PORT', 5061),
    // hard ceiling on a single call's granted duration (seconds)
    'max_call_seconds' => (int) env('SWITCH_MAX_CALL_SECONDS', 14400),
    // how much of a call to reserve up-front (seconds of cost), so one call does
    // not lock the whole balance and block concurrent calls
    'hold_window_seconds' => (int) env('SWITCH_HOLD_WINDOW_SECONDS', 300),
    // prepaid credit holds older than this are treated as orphaned and ignored
    'hold_ttl_hours' => (int) env('SWITCH_HOLD_TTL_HOURS', 6),
    // Absolute per-call ceiling applied to EVERY billing type, fail-closed:
    // even a postpaid/unknown account cannot run an unbounded call (F4).
    'default_max_call_seconds' => (int) env('SWITCH_DEFAULT_MAX_CALL_SECONDS', 3600),
    // Per-account concurrent-call default when account_cc is unset (was 200) (F7).
    'default_account_cc' => (int) env('SWITCH_DEFAULT_ACCOUNT_CC', 2),
    // High-risk destination prefixes (E.164) refused before routing (F5).
    // Seeded with satellite/premium ranges no AU business dials; the operator
    // MUST extend this to their own fraud profile. Matched after stripping
    // a leading +, 00 or 0011 international access code.
    'blocked_prefixes' => array_values(array_filter(array_map('trim', explode(',', (string) env('SWITCH_BLOCKED_PREFIXES',
        '870,871,872,873,874,878,881,882,883,8816,8817,979,88213,88216'))))),
];
