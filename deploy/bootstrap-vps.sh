#!/usr/bin/env bash
#
# Provision a fresh Ubuntu 24.04 LTS VPS for hezarrial.
#
# Run once, as root, on a brand new server:
#
#   DEPLOY_SSH_KEY="ssh-ed25519 AAAA... deploy@github-actions" \
#   bash bootstrap-vps.sh
#
# It is idempotent: re-running it is safe.
#
set -euo pipefail

DEPLOY_USER="${DEPLOY_USER:-deploy}"
APP_DIR="${APP_DIR:-/opt/hezarrial}"
SSH_PORT="${SSH_PORT:-22}"
DEPLOY_SSH_KEY="${DEPLOY_SSH_KEY:-}"

log() { printf '\n\033[1;32m==> %s\033[0m\n' "$1"; }
die() { printf '\n\033[1;31mERROR: %s\033[0m\n' "$1" >&2; exit 1; }

[[ $EUID -eq 0 ]] || die "Run this script as root."
[[ -n "$DEPLOY_SSH_KEY" ]] || die "Set DEPLOY_SSH_KEY to the public key GitHub Actions will use."

# ── System packages ─────────────────────────────────────────────
log "Updating system packages"
export DEBIAN_FRONTEND=noninteractive
apt-get update
apt-get upgrade -y
apt-get install -y --no-install-recommends \
    ca-certificates \
    curl \
    git \
    gnupg \
    ufw \
    unattended-upgrades

# ── Swap (small VPS plans often ship with little RAM) ───────────
if [[ ! -f /swapfile ]] && [[ $(free -m | awk '/^Mem:/{print $2}') -lt 4096 ]]; then
    log "Creating 2G swapfile"
    fallocate -l 2G /swapfile
    chmod 600 /swapfile
    mkswap /swapfile
    swapon /swapfile
    grep -q '^/swapfile' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
fi

# ── Docker Engine (official repository) ─────────────────────────
if ! command -v docker >/dev/null 2>&1; then
    log "Installing Docker Engine"
    install -m 0755 -d /etc/apt/keyrings
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg \
        | gpg --dearmor -o /etc/apt/keyrings/docker.gpg
    chmod a+r /etc/apt/keyrings/docker.gpg
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "$VERSION_CODENAME") stable" \
        > /etc/apt/sources.list.d/docker.list
    apt-get update
    apt-get install -y \
        docker-ce \
        docker-ce-cli \
        containerd.io \
        docker-buildx-plugin \
        docker-compose-plugin
    systemctl enable --now docker
else
    log "Docker already installed, skipping"
fi

# Cap container log growth so a chatty container cannot fill the disk.
if [[ ! -f /etc/docker/daemon.json ]]; then
    log "Configuring Docker log rotation"
    mkdir -p /etc/docker
    cat > /etc/docker/daemon.json <<'JSON'
{
  "log-driver": "json-file",
  "log-opts": {
    "max-size": "10m",
    "max-file": "3"
  }
}
JSON
    systemctl restart docker
fi

# ── Deploy user ─────────────────────────────────────────────────
if ! id -u "$DEPLOY_USER" >/dev/null 2>&1; then
    log "Creating user $DEPLOY_USER"
    adduser --disabled-password --gecos "" "$DEPLOY_USER"
fi
usermod -aG docker "$DEPLOY_USER"

log "Installing deploy SSH key"
DEPLOY_HOME="$(getent passwd "$DEPLOY_USER" | cut -d: -f6)"
install -d -m 700 -o "$DEPLOY_USER" -g "$DEPLOY_USER" "$DEPLOY_HOME/.ssh"
touch "$DEPLOY_HOME/.ssh/authorized_keys"
grep -qxF "$DEPLOY_SSH_KEY" "$DEPLOY_HOME/.ssh/authorized_keys" \
    || echo "$DEPLOY_SSH_KEY" >> "$DEPLOY_HOME/.ssh/authorized_keys"
chmod 600 "$DEPLOY_HOME/.ssh/authorized_keys"
chown "$DEPLOY_USER:$DEPLOY_USER" "$DEPLOY_HOME/.ssh/authorized_keys"

log "Creating $APP_DIR"
install -d -o "$DEPLOY_USER" -g "$DEPLOY_USER" "$APP_DIR"

# ── SSH hardening ───────────────────────────────────────────────
log "Hardening SSH (key-only, no root login)"
cat > /etc/ssh/sshd_config.d/99-hezarrial.conf <<CONF
Port $SSH_PORT
PermitRootLogin no
PasswordAuthentication no
KbdInteractiveAuthentication no
ChallengeResponseAuthentication no
PubkeyAuthentication yes
CONF
sshd -t || die "sshd config test failed; not restarting SSH."
systemctl restart ssh

# ── Firewall ────────────────────────────────────────────────────
log "Configuring firewall"
ufw allow "$SSH_PORT"/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable

# ── Automatic security updates ──────────────────────────────────
log "Enabling unattended security upgrades"
dpkg-reconfigure -f noninteractive unattended-upgrades

log "Done."
cat <<EOF

Next steps (as $DEPLOY_USER):

  ssh $DEPLOY_USER@<server-ip>
  cd $APP_DIR
  git clone https://github.com/<owner>/hezarrial.git .
  cp deploy/production.env.example .env.production
  # edit .env.production, then:
  ./deploy/deploy.sh

EOF
