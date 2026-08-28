#!/usr/bin/env bash
# Build Asterisk 18 (chan_sip) from source as the CommsChannel SBC media anchor.
#
# WHY a source build of an old branch:
#   chan_sip terminates and RE-FRAMES RTP to a constant 20ms ptime. That is the
#   only thing that fixes choppy audio from PBXes that emit mixed 20/40ms G.711
#   (Yeastar IVR/MoH are the reference offenders). chan_pjsip does native
#   passthrough (preserves the bad ptime -> chop); FreeSWITCH drops half the
#   frames on async input; rtpengine is a pure relay. chan_sip is deprecated and
#   ships noload-by-default in 18/20, and is unstable in 20 -- so we pin the last
#   solid 18.x, enable chan_sip explicitly, and run PJSIP noload.
#
# Idempotent: safe to re-run. Tested on Debian 13 (trixie) / gcc-14.
set -euo pipefail

AST_VER="${AST_VER:-18.9.0}"
SRC_ROOT="${SRC_ROOT:-/usr/src/ast18}"
TARBALL="asterisk-${AST_VER}.tar.gz"
URL="https://downloads.asterisk.org/pub/telephony/asterisk/releases/${TARBALL}"

echo "==> build deps"
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y --no-install-recommends \
    build-essential wget libxml2-dev libncurses5-dev uuid-dev \
    libjansson-dev libsqlite3-dev libssl-dev libedit-dev pkg-config ca-certificates

echo "==> fetch ${TARBALL}"
mkdir -p "$SRC_ROOT"
cd "$SRC_ROOT"
[ -f "$TARBALL" ] || wget -q "$URL"
rm -rf "asterisk-${AST_VER}"
tar xzf "$TARBALL"
cd "asterisk-${AST_VER}"

echo "==> configure (bundled pjproject + jansson)"
./configure --with-pjproject-bundled --with-jansson-bundled >/tmp/ast-configure.log 2>&1

# gcc-14 / glibc on trixie declares gethostbyname_r with the 6-arg prototype,
# but Asterisk 18's configure probe misdetects it and emits a conflicting
# re-declaration in main/utils.c -> the build dies with an "Error 1" on utils.o.
# Forcing the 6-arg macro on makes Asterisk use the system prototype as-is.
echo "==> patch autoconfig.h (gethostbyname_r 6-arg, gcc-14/trixie)"
AC=include/asterisk/autoconfig.h
if ! grep -q 'HAVE_GETHOSTBYNAME_R_6 1' "$AC"; then
    printf '\n#undef HAVE_GETHOSTBYNAME_R_6\n#define HAVE_GETHOSTBYNAME_R_6 1\n' >> "$AC"
fi

echo "==> menuselect: enable chan_sip, disable the noisy extras"
make menuselect.makeopts
menuselect/menuselect --enable chan_sip menuselect.makeopts
# app_voicemail imap etc. are not needed and pull heavy deps; leave defaults.

echo "==> compile"
make -j"$(nproc)"

# A newer Asterisk (e.g. a prior chan_pjsip-20 experiment) leaves .so modules
# that reference symbols this 18 binary does not export; on start Asterisk logs
# "Module init failed" on res_websocket_client.so / app_broadcast.so and EXITS.
# Clear the modules dir so `make install` lays down only this build's modules.
echo "==> clear stale modules from any previous Asterisk"
rm -f /usr/lib/asterisk/modules/*.so 2>/dev/null || true

echo "==> install binaries + base sample configs"
make install
# `make samples` lays down a complete /etc/asterisk (asterisk.conf, logger.conf,
# modules.conf, etc.) so Asterisk starts cleanly. It overwrites existing files
# (backing each up to *.conf.old), which is why the installer runs THIS build
# step BEFORE its config-copy step — the copy step then overlays our sip.conf /
# rtp.conf / extensions.conf / modules.conf on top. (If you re-run this script
# standalone, re-run the installer afterward, or re-copy the four configs, so the
# overlay is restored.)
make samples
ldconfig

echo "==> systemd unit (run as root; self-heal; wait for MariaDB the portal uses)"
cat > /etc/systemd/system/asterisk.service <<'UNIT'
[Unit]
Description=Asterisk PBX (CommsChannel SBC media anchor)
Documentation=https://github.com/bilalmuhammadcommschannel/ccportal-sbc
After=network-online.target mariadb.service
Wants=network-online.target

[Service]
Type=simple
ExecStart=/usr/sbin/asterisk -f -C /etc/asterisk/asterisk.conf
ExecReload=/usr/sbin/asterisk -rx 'core reload'
Restart=on-failure
RestartSec=4

[Install]
WantedBy=multi-user.target
UNIT
systemctl daemon-reload

echo "==> done: Asterisk ${AST_VER} with chan_sip"
/usr/sbin/asterisk -V || true
ls -l /usr/lib/asterisk/modules/chan_sip.so
