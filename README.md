# CommsChannel SBC

A self-contained **session border controller + billing portal** for a fresh Debian 13 host:

- **Kamailio 6.0** — public SIP edge (5060/udp+tcp, 5061/tls). Digest auth on REGISTER *and* INVITE, pike flood-limiting, `htable` bans, TLS, in-dialog open-relay guard.
- **FreeSWITCH 1.11** — media, loopback-only, driven by the portal's `xml_curl` dialplan; bridges to carriers.
- **Laravel 12 portal** — carriers, tariffs/ratecards, DIDs, endpoints, customers/resellers, bundles, balances, CDRs, live-call + fail2ban monitoring dashboard. Prepaid credit gate, server-side rating, atomic idempotent billing.
- **Hardening baked in** — nftables (default-drop, per-source rate meters), fail2ban (SIP + web jails), sysctl anti-spoof, TLS everywhere, per-account concurrency + call-duration caps, high-risk-destination blocklist.

## Install (one command)

On a **fresh Debian 13 (trixie)** server, as root:

```bash
curl -fsSL https://raw.githubusercontent.com/bilalmuhammadcommschannel/ccportal-sbc/main/install.sh | sudo bash
```

It prompts for three things:

| Prompt | What it is |
|---|---|
| **Domain** | FQDN for the portal + SIP realm (e.g. `sbc.example.com`). Point its DNS `A` record at the server first. |
| **Email** | For the Let's Encrypt certificate + expiry notices. |
| **SignalWire token** | Free personal access token from [signalwire.com](https://signalwire.com) — FreeSWITCH 1.11 packages are gated behind it. |

You'll also be asked whether to **open SIP to the public internet** now (default: no — SIP stays restricted to trusted carrier/admin IPs until you're ready).

Non-interactive:

```bash
curl -fsSL .../install.sh | sudo bash -s -- \
  --domain sbc.example.com --email you@example.com \
  --signalwire-token pt_XXXX --open-sip
```

## What it does

1. Adds the sury (PHP 8.3), Kamailio, and SignalWire (FreeSWITCH) apt repos and installs the stack.
2. **Generates every secret on the machine** — DB passwords, switch shared secret, ESL password, admin password — and writes them to `/root/.cc/install-credentials` (root-only). Nothing sensitive ships in this repo.
3. Creates the `ccportal_app` / `switch` / `switchcdr` / `kamailio` databases and loads the schema (structure only — no customer, carrier, or billing data).
4. Deploys the portal (`composer install`, `.env`, key, seeds the first admin).
5. Renders every server config from templates (your domain, the server's own public IP, the generated secrets) and applies firewall + hardening.
6. Obtains a Let's Encrypt certificate (self-signed fallback if DNS isn't live yet).

At the end it prints the portal URL and the generated admin login.

## After install

- **Log in and change the admin password immediately.**
- Add a carrier: `nft add element inet filter carrier_v4 { <carrier-ip> }` (and create it in the portal).
- Add your admin/office IP: `nft add element inet filter admin_v4 { <your-ip> }`.
- Extend the toll-fraud destination blocklist (`SWITCH_BLOCKED_PREFIXES` in the portal `.env`) to your own risk profile.
- If a self-signed cert was used, re-run `certbot certonly --webroot -w /var/www/html -d <domain>` once DNS resolves.

## Security notes

- This repository is public and contains **no** IP addresses, secrets, credentials, tokens, or customer/carrier data — only templates with `__PLACEHOLDER__` tokens the installer fills locally.
- Per-install secrets are generated fresh and never leave the target machine.
- FreeSWITCH's control socket (ESL) and external profile, MariaDB, and the switch API are loopback-only and firewalled; the portal login has nginx rate-limiting + app throttle + fail2ban.

## Requirements

Fresh Debian 13 (trixie), root access, a domain with DNS pointing at the host, and a SignalWire token. ~2 vCPU / 2 GB RAM minimum.
