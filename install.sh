#!/usr/bin/env bash
#
# CommsChannel SBC — one-command installer for a fresh Debian 13 (trixie) host.
#
#   curl -fsSL https://raw.githubusercontent.com/REPO_SLUG/main/install.sh | sudo bash
#
# Installs & wires: Kamailio 6.0 (public SIP edge) + FreeSWITCH 1.11 (media) +
# Laravel portal (nginx + PHP 8.3 + MariaDB) + fail2ban + nftables + TLS.
# Prompts for: domain, Let's Encrypt email, SignalWire token (FreeSWITCH repo).
# Every DB password / shared secret / ESL password / admin password is generated
# fresh on this machine and never leaves it.
#
set -euo pipefail

REPO_SLUG="bilalmuhammadcommschannel/ccportal-sbc"
REPO_URL="https://github.com/${REPO_SLUG}.git"
BRANCH="main"
CLONE_DIR="/opt/ccportal-sbc"
CRED_DIR="/root/.cc"
APP_DIR="/var/www/ccportal"

c()  { printf '\033[%sm%s\033[0m\n' "$1" "$2"; }
step(){ c "1;36" "==> $*"; }
ok()  { c "1;32" "  ok $*"; }
warn(){ c "1;33" "  !! $*"; }
die() { c "1;31" "FATAL: $*"; exit 1; }

[ "$(id -u)" = 0 ] || die "run as root (use sudo)."

# ---------------------------------------------------------------- self-bootstrap
# When piped from curl, the repo isn't on disk yet — clone it and re-exec.
SELF_DIR="$(cd "$(dirname "${BASH_SOURCE[0]:-$0}")" 2>/dev/null && pwd || true)"
if [ -z "${SELF_DIR}" ] || [ ! -d "${SELF_DIR}/server" ]; then
    step "fetching installer repo -> ${CLONE_DIR}"
    export DEBIAN_FRONTEND=noninteractive
    apt-get update -qq && apt-get install -y -qq git >/dev/null
    if [ -d "${CLONE_DIR}/.git" ]; then git -C "${CLONE_DIR}" pull -q; else git clone -q --depth 1 -b "${BRANCH}" "${REPO_URL}" "${CLONE_DIR}"; fi
    exec bash "${CLONE_DIR}/install.sh" "$@"
fi
REPO="${SELF_DIR}"

# ---------------------------------------------------------------- inputs
DOMAIN="${DOMAIN:-}"; LE_EMAIL="${LE_EMAIL:-}"; SIGNALWIRE_TOKEN="${SIGNALWIRE_TOKEN:-}"; OPEN_SIP="${OPEN_SIP:-}"
while [ $# -gt 0 ]; do case "$1" in
    --domain) DOMAIN="$2"; shift 2;;
    --email) LE_EMAIL="$2"; shift 2;;
    --signalwire-token) SIGNALWIRE_TOKEN="$2"; shift 2;;
    --open-sip) OPEN_SIP=yes; shift;;
    *) shift;;
esac; done

ask() { local p="$1" d="${2:-}" v; if [ -n "$d" ]; then read -rp "$p [$d]: " v </dev/tty || true; echo "${v:-$d}"; else read -rp "$p: " v </dev/tty || true; echo "$v"; fi; }
[ -n "$DOMAIN" ]           || DOMAIN="$(ask 'SIP + portal domain (e.g. sbc.example.com)')"
[ -n "$LE_EMAIL" ]         || LE_EMAIL="$(ask 'Email for Lets Encrypt / expiry notices')"
[ -n "$SIGNALWIRE_TOKEN" ] || SIGNALWIRE_TOKEN="$(ask 'SignalWire personal access token (free from signalwire.com; for the FreeSWITCH repo)')"
if [ -z "$OPEN_SIP" ]; then a="$(ask 'Open SIP (5060/5061/RTP) to the public internet now? y/N' 'N')"; [ "${a,,}" = y ] && OPEN_SIP=yes || OPEN_SIP=no; fi
[ -n "$DOMAIN" ] || die "domain is required."
[ -n "$SIGNALWIRE_TOKEN" ] || die "SignalWire token is required for FreeSWITCH."

PUBLIC_IP="$(ip -4 route get 1.1.1.1 2>/dev/null | grep -oE 'src [0-9.]+' | awk '{print $2}' | head -1)"
[ -n "$PUBLIC_IP" ] || PUBLIC_IP="$(hostname -I | awk '{print $1}')"
step "domain=$DOMAIN  public-ip=$PUBLIC_IP  open-sip=$OPEN_SIP"

# ---------------------------------------------------------------- secrets
step "generating secrets"
mkdir -p "$CRED_DIR"; chmod 700 "$CRED_DIR"
gen() { openssl rand -hex "${1:-24}"; }
APP_DB_PASS="$(gen 18)"; KAM_DB_PASS="$(gen 18)"; SWITCH_SECRET="$(gen 24)"; ESL_PASSWORD="$(gen 20)"
ADMIN_EMAIL="admin@${DOMAIN}"; ADMIN_PASSWORD="$(gen 12)"
umask 077
cat > "$CRED_DIR/install-credentials" <<EOF
# CommsChannel SBC — generated $(date -u +%FT%TZ). KEEP PRIVATE.
DOMAIN=$DOMAIN
PORTAL_URL=https://$DOMAIN
ADMIN_EMAIL=$ADMIN_EMAIL
ADMIN_PASSWORD=$ADMIN_PASSWORD
APP_DB_USER=ccportal
APP_DB_PASS=$APP_DB_PASS
KAM_DB_USER=kamailio
KAM_DB_PASS=$KAM_DB_PASS
SWITCH_SHARED_SECRET=$SWITCH_SECRET
ESL_PASSWORD=$ESL_PASSWORD
EOF
ok "secrets stored in $CRED_DIR/install-credentials (0600)"

# ---------------------------------------------------------------- apt repos + packages
step "configuring apt repositories"
export DEBIAN_FRONTEND=noninteractive
install -d -m 0755 /usr/share/keyrings
apt-get install -y -qq curl gnupg ca-certificates lsb-release apt-transport-https >/dev/null
# PHP (sury)
curl -fsSL https://packages.sury.org/php/apt.gpg -o /usr/share/keyrings/sury-php.gpg
echo "deb [signed-by=/usr/share/keyrings/sury-php.gpg] https://packages.sury.org/php/ trixie main" > /etc/apt/sources.list.d/php.list
# Kamailio 6.0
curl -fsSL http://deb.kamailio.org/kamailiodebkey.gpg | gpg --dearmor -o /usr/share/keyrings/kamailio-archive.gpg
echo "deb [signed-by=/usr/share/keyrings/kamailio-archive.gpg] http://deb.kamailio.org/kamailio60 trixie main" > /etc/apt/sources.list.d/kamailio.list
# FreeSWITCH (SignalWire, token-gated)
curl -fsSL --user "signalwire:${SIGNALWIRE_TOKEN}" https://freeswitch.signalwire.com/repo/deb/debian-release/signalwire-freeswitch-repo.gpg -o /usr/share/keyrings/signalwire-freeswitch-repo.gpg \
    || die "SignalWire token rejected — check it at signalwire.com."
echo "machine freeswitch.signalwire.com login signalwire password ${SIGNALWIRE_TOKEN}" > /etc/apt/auth.conf.d/freeswitch.conf
chmod 600 /etc/apt/auth.conf.d/freeswitch.conf
echo "deb [signed-by=/usr/share/keyrings/signalwire-freeswitch-repo.gpg] https://freeswitch.signalwire.com/repo/deb/debian-release/ trixie main" > /etc/apt/sources.list.d/freeswitch.list

step "installing packages (this takes a while)"
apt-get update -qq
apt-get install -y -qq \
    nginx mariadb-server \
    php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-bcmath php8.3-zip php8.3-intl php8.3-gd \
    kamailio kamailio-mysql-modules kamailio-tls-modules \
    freeswitch-meta-all \
    certbot fail2ban nftables composer jq >/dev/null 2>&1 || warn "some packages reported issues — review apt output"
ok "packages installed"

# ---------------------------------------------------------------- databases
step "creating databases + users"
systemctl enable --now mariadb >/dev/null 2>&1 || true
mysql() { command mariadb "$@"; }
mysql <<SQL
CREATE DATABASE IF NOT EXISTS ccportal_app CHARACTER SET utf8mb4;
CREATE DATABASE IF NOT EXISTS switch;
CREATE DATABASE IF NOT EXISTS switchcdr;
CREATE DATABASE IF NOT EXISTS kamailio;
CREATE USER IF NOT EXISTS 'ccportal'@'localhost' IDENTIFIED BY '${APP_DB_PASS}';
CREATE USER IF NOT EXISTS 'kamailio'@'localhost' IDENTIFIED BY '${KAM_DB_PASS}';
GRANT ALL PRIVILEGES ON ccportal_app.* TO 'ccportal'@'localhost';
GRANT SELECT,INSERT,UPDATE,DELETE ON switch.* TO 'ccportal'@'localhost';
GRANT SELECT,INSERT,UPDATE,DELETE ON switchcdr.* TO 'ccportal'@'localhost';
GRANT ALL PRIVILEGES ON kamailio.* TO 'kamailio'@'localhost';
GRANT SELECT ON switch.customer_sip_account TO 'kamailio'@'localhost';
FLUSH PRIVILEGES;
SQL
step "loading schema"
render() { sed -e "s|__PUBLIC_IP__|${PUBLIC_IP}|g" -e "s|__DOMAIN__|${DOMAIN}|g" \
               -e "s|__ESL_PASSWORD__|${ESL_PASSWORD}|g" -e "s|__SWITCH_SECRET__|${SWITCH_SECRET}|g" \
               -e "s|__KAM_DB_PASS__|${KAM_DB_PASS}|g"; }
mysql switch       < "$REPO/database/schema-switch.sql"
mysql switchcdr    < "$REPO/database/schema-switchcdr.sql"
mysql ccportal_app < "$REPO/database/schema-ccportal_app.sql"
mysql switch       < "$REPO/database/seed-currencies.sql" 2>/dev/null || true
mysql ccportal_app < "$REPO/database/seed-migrations.sql" 2>/dev/null || true
# kamailio schema carries the subscriber VIEW over switch.customer_sip_account;
# render __DOMAIN__ into it before load.
render < "$REPO/database/schema-kamailio.sql" | mysql kamailio
ok "databases ready"

# ---------------------------------------------------------------- portal
step "deploying portal"
mkdir -p "$APP_DIR"
cp -a "$REPO/portal/." "$APP_DIR/"
cd "$APP_DIR"
sudo -u www-data HOME=/tmp COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --no-interaction --prefer-dist -q || \
    COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --no-interaction --prefer-dist -q
cat > "$APP_DIR/.env" <<EOF
APP_NAME="CommsChannel SBC"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://${DOMAIN}
APP_LOCALE=en
LOG_CHANNEL=stack
LOG_LEVEL=warning
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ccportal_app
DB_USERNAME=ccportal
DB_PASSWORD=${APP_DB_PASS}
SWITCH_DB_HOST=127.0.0.1
SWITCH_DB_PORT=3306
SWITCH_DB_DATABASE=switch
SWITCH_DB_USERNAME=ccportal
SWITCH_DB_PASSWORD=${APP_DB_PASS}
SWITCHCDR_DB_DATABASE=switchcdr
SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=strict
SESSION_LIFETIME=60
SESSION_EXPIRE_ON_CLOSE=true
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local
SWITCH_SHARED_SECRET=${SWITCH_SECRET}
SWITCH_SIP_DOMAIN=${DOMAIN}
SWITCH_SIP_PROXY=${PUBLIC_IP}
ADMIN_SEED_EMAIL=${ADMIN_EMAIL}
ADMIN_SEED_PASSWORD=${ADMIN_PASSWORD}
EOF
mkdir -p "$APP_DIR"/storage/framework/{cache/data,sessions,views} "$APP_DIR"/storage/logs "$APP_DIR"/bootstrap/cache
php8.3 artisan key:generate --force -q
php8.3 artisan db:seed --class=AdminUserSeeder --force -q || warn "admin seed skipped"
chown -R www-data:www-data "$APP_DIR"
find "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" -type d -exec chmod 775 {} \; 2>/dev/null || true
php8.3 artisan config:cache -q
ok "portal deployed"

# ---------------------------------------------------------------- server configs
step "installing server configs"
# freeswitch user (package usually creates it; ensure it exists to avoid chown failures)
id freeswitch >/dev/null 2>&1 || useradd -r -s /usr/sbin/nologin freeswitch || true
copy_render() { install -D -m "${3:-0644}" /dev/null "$2"; render < "$1" > "$2"; }
while IFS= read -r -d '' f; do
    rel="${f#"$REPO"/server}"; dest="$rel"
    case "$rel" in
        /etc/nftables.conf) continue;;                    # handled below (open-sip toggle)
        /usr/local/sbin/*) copy_render "$f" "$dest" 0755;;
        *) copy_render "$f" "$dest";;
    esac
done < <(find "$REPO/server" -type f -print0)
# nftables: render + inject public-SIP block on opt-in
PUBLIC_BLOCK="        # (public SIP not enabled — add carrier/admin IPs to the nft sets, or re-run with --open-sip)"
if [ "$OPEN_SIP" = yes ]; then
PUBLIC_BLOCK=$(cat <<'NFT'
        udp dport 5060 meter cc_sip_udp4 { ip saddr limit rate over 40/second burst 80 packets } drop
        udp dport 5060 accept comment "public SIP udp"
        tcp dport { 5060, 5061 } ct state new meter cc_sip_newc4 { ip saddr limit rate over 10/second burst 20 packets } drop
        tcp dport { 5060, 5061 } ct state new meter cc_sip_cnt4 { ip saddr ct count over 40 } drop
        tcp dport 5060 accept comment "public SIP tcp"
        tcp dport 5061 accept comment "public SIP tls"
        udp dport 16384-32768 accept comment "RTP media - public"
NFT
)
fi
render < "$REPO/server/etc/nftables.conf" | awk -v b="$PUBLIC_BLOCK" '{gsub(/# __PUBLIC_SIP_RULES__/, b)}1' > /etc/nftables.conf
sysctl --system >/dev/null 2>&1 || true
nft -f /etc/nftables.conf || warn "nftables load reported an error — review /etc/nftables.conf"
ok "configs installed"

# ---------------------------------------------------------------- TLS
step "obtaining TLS certificate for $DOMAIN"
mkdir -p /var/www/html
systemctl reload nginx 2>/dev/null || systemctl restart nginx 2>/dev/null || true
if certbot certonly --webroot -w /var/www/html -d "$DOMAIN" --email "$LE_EMAIL" --agree-tos --non-interactive >/dev/null 2>&1; then
    ok "Lets Encrypt cert issued"
else
    warn "certbot failed (DNS for $DOMAIN must point at $PUBLIC_IP). Generating a self-signed cert so services start; re-run certbot once DNS is live."
    mkdir -p "/etc/letsencrypt/live/$DOMAIN"
    openssl req -x509 -newkey ec -pkeyopt ec_paramgen_curve:prime256v1 -nodes -days 90 \
        -keyout "/etc/letsencrypt/live/$DOMAIN/privkey.pem" -out "/etc/letsencrypt/live/$DOMAIN/fullchain.pem" \
        -subj "/CN=$DOMAIN" >/dev/null 2>&1
    cp "/etc/letsencrypt/live/$DOMAIN/fullchain.pem" "/etc/letsencrypt/live/$DOMAIN/chain.pem"
fi
# Kamailio TLS copy + renewal hook
mkdir -p /etc/kamailio/tls
cp "/etc/letsencrypt/live/$DOMAIN/fullchain.pem" /etc/kamailio/tls/fullchain.pem
cp "/etc/letsencrypt/live/$DOMAIN/privkey.pem"   /etc/kamailio/tls/privkey.pem
chown -R kamailio:kamailio /etc/kamailio/tls 2>/dev/null || true
install -D -m 0755 /dev/null /etc/letsencrypt/renewal-hooks/deploy/20-cc-reload.sh
cat > /etc/letsencrypt/renewal-hooks/deploy/20-cc-reload.sh <<EOF
#!/bin/sh
cp "/etc/letsencrypt/live/$DOMAIN/fullchain.pem" /etc/kamailio/tls/fullchain.pem
cp "/etc/letsencrypt/live/$DOMAIN/privkey.pem"   /etc/kamailio/tls/privkey.pem
chown -R kamailio:kamailio /etc/kamailio/tls 2>/dev/null || true
systemctl reload nginx; systemctl restart kamailio
EOF
chmod +x /etc/letsencrypt/renewal-hooks/deploy/20-cc-reload.sh

# ---------------------------------------------------------------- services
step "enabling services"
systemctl enable --now php8.3-fpm nginx fail2ban nftables >/dev/null 2>&1 || true
for svc in kamailio freeswitch; do systemctl enable "$svc" >/dev/null 2>&1 || true; systemctl restart "$svc" >/dev/null 2>&1 || warn "$svc did not start — check: journalctl -u $svc"; done
for t in ccportal-scheduler.timer cc-stats.timer; do systemctl enable --now "$t" >/dev/null 2>&1 || true; done
systemctl reload nginx 2>/dev/null || systemctl restart nginx || true

# ---------------------------------------------------------------- done
echo; c "1;32" "============================================================"
c "1;32" " CommsChannel SBC installed."
c "1;32" "============================================================"
echo "  Portal:        https://$DOMAIN"
echo "  Admin login:   $ADMIN_EMAIL"
echo "  Admin pass:    $ADMIN_PASSWORD"
echo "  Secrets file:  $CRED_DIR/install-credentials  (root-only)"
echo "  SIP public:    $OPEN_SIP"
echo
echo "  Next:"
echo "   1. Point DNS: $DOMAIN -> $PUBLIC_IP  (then: certbot certonly ... if self-signed)"
echo "   2. Add a carrier IP:  nft add element inet filter carrier_v4 { <carrier-ip> }"
echo "   3. Add your admin IP: nft add element inet filter admin_v4 { <your-ip> }"
[ "$OPEN_SIP" = yes ] && echo "   4. SIP is OPEN to the world (rate-limited). Register a phone: user@$DOMAIN via proxy $PUBLIC_IP."
echo "   Log in and change the admin password immediately."
echo
