# CommsChannel SBC

A self-contained **session border controller + billing portal** for a fresh Debian 13 host:

- **Kamailio 6.0** — public SIP edge (5060/udp+tcp, 5061/tls). Digest auth on REGISTER *and* INVITE, pike flood-limiting, `htable` bans, TLS, in-dialog open-relay guard.
- **Asterisk 18 (chan_sip)** — loopback-only media anchor, built from source. It terminates and **re-frames RTP to a constant 20ms ptime** — the fix for choppy audio from PBXes that emit mixed 20/40ms G.711 (e.g. Yeastar IVR/MoH) — and runs an AGI dialplan that asks the portal to route + rate each call before bridging to the carrier. See [docs/MEDIA-ANCHOR.md](docs/MEDIA-ANCHOR.md) for why chan_sip and not chan_pjsip / FreeSWITCH / rtpengine.
- **Laravel 12 portal** — carriers, tariffs/ratecards, DIDs, endpoints, customers/resellers, bundles, balances, CDRs, live-call + fail2ban monitoring dashboard. Prepaid credit gate, server-side rating, atomic idempotent billing.
- **Hardening baked in** — nftables (default-drop, per-source rate meters), fail2ban (SIP + web jails), sysctl anti-spoof, TLS everywhere, per-account concurrency + call-duration caps, high-risk-destination blocklist.

## Install (one command)

On a **fresh Debian 13 (trixie)** server, as root:

```bash
curl -O https://raw.githubusercontent.com/bilalmuhammadcommschannel/ccportal-sbc/main/install.sh
bash install.sh
```

It prompts for everything it needs, up front:

| Prompt | What it is |
|---|---|
| **Domain** | FQDN for the portal + SIP realm (e.g. `sbc.example.com`). Point its DNS `A` record at the server first. |
| **Email** | For the Let's Encrypt certificate + expiry notices. |
| **Open SIP now?** | Whether to open 5060/5061/RTP to the public internet immediately (default: no — SIP stays restricted to trusted carrier/admin IPs until you're ready). |

No third-party account or token is required. Non-interactive / unattended:

```bash
curl -O https://raw.githubusercontent.com/bilalmuhammadcommschannel/ccportal-sbc/main/install.sh
bash install.sh --domain sbc.example.com --email you@example.com --open-sip
```

> **First run builds Asterisk 18 from source** (Debian ships a chan_pjsip-only package; we need chan_sip). Budget a few minutes for the compile on a 2-vCPU box. It is cached — a re-run skips it once `chan_sip.so` is in place.

**Safe to re-run.** If the install fails partway (a network blip, DNS not ready), just run `bash install.sh` again on the same machine. It reuses the secrets it already generated (`/root/.cc/install-credentials`), skips databases that are already loaded, keeps an existing certificate, skips the Asterisk build if already done, and only redoes what's missing — so it converges instead of starting over.

## What it does

1. Adds the sury (PHP 8.3) and Kamailio apt repos, installs the stack, and **builds Asterisk 18 + chan_sip from source** ([scripts/build-asterisk-chansip.sh](scripts/build-asterisk-chansip.sh)).
2. **Generates every secret on the machine** — DB passwords, switch shared secret, admin password — and writes them to `/root/.cc/install-credentials` (root-only). Nothing sensitive ships in this repo.
3. Creates the `ccportal_app` / `switch` / `switchcdr` / `kamailio` databases and loads the schema (structure only — no customer, carrier, or billing data).
4. Deploys the portal (`composer install`, `.env`, key, seeds the first admin).
5. Renders every server config from templates (your domain, the server's own public IP, the generated secrets) and applies firewall + hardening.
6. Obtains a Let's Encrypt certificate (self-signed fallback if DNS isn't live yet).

At the end it prints the portal URL and the generated admin login.

## After install

- **Log in and change the admin password immediately.**
- Add a carrier's inbound signalling IP so its INVITEs skip auth: `nft add element inet filter carrier_v4 { <carrier-ip> }`, and add the same IP to the `carrieracl` htable / carrier record in the portal.
- Add your admin/office IP: `nft add element inet filter admin_v4 { <your-ip> }`.
- Extend the toll-fraud destination blocklist (`SWITCH_BLOCKED_PREFIXES` in the portal `.env`) to your own risk profile.
- If a self-signed cert was used, re-run `certbot certonly --webroot -w /var/www/html -d <domain>` once DNS resolves.

## Security notes

- This repository is public and contains **no** IP addresses, secrets, credentials, tokens, or customer/carrier data — only templates with `__PLACEHOLDER__` tokens the installer fills locally.
- Per-install secrets are generated fresh and never leave the target machine.
- The Asterisk media anchor listens on loopback only (UDP 5065, reachable solely from Kamailio over `lo`); MariaDB and the switch API are loopback-only and firewalled; the portal login has nginx rate-limiting + app throttle + fail2ban.

## Requirements

Fresh Debian 13 (trixie), root access, and a domain with DNS pointing at the host. ~2 vCPU / 2 GB RAM minimum (the Asterisk source build is the heaviest step; 2 GB RAM is enough).
