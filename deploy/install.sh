#!/usr/bin/env bash
# One-time setup of a fresh Ubuntu 24.04 server for CHABA.
#
# Run as root on the new server:
#   curl -fsSL https://raw.githubusercontent.com/toohat322-hue/chaba/master/deploy/install.sh | bash -s -- you@example.com
#
# The argument is the email Let's Encrypt sends certificate notices to.
# Safe to run again: it never overwrites existing settings files.
set -euo pipefail

ACME_EMAIL="${1:-}"
REPO_URL="https://github.com/toohat322-hue/chaba.git"
APP_DIR="/opt/chaba"
DEPLOY_USER="deploy"

if [ "$(id -u)" -ne 0 ]; then
  echo "Run this as root." >&2
  exit 1
fi
if [ -z "$ACME_EMAIL" ]; then
  echo "Usage: install.sh <email-for-certificates>" >&2
  exit 1
fi

echo "==> Installing packages"
export DEBIAN_FRONTEND=noninteractive
apt-get update -q
apt-get install -yq git curl ufw openssl ca-certificates
if ! command -v docker >/dev/null; then
  curl -fsSL https://get.docker.com | sh
fi

echo "==> Swap (the storefront build needs more memory than small servers have)"
if ! swapon --show | grep -q .; then
  fallocate -l 2G /swapfile
  chmod 600 /swapfile
  mkswap /swapfile
  swapon /swapfile
  echo "/swapfile none swap sw 0 0" >> /etc/fstab
fi

echo "==> Firewall: SSH, HTTP, HTTPS only"
ufw allow OpenSSH
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 443/udp
ufw --force enable

echo "==> '$DEPLOY_USER' user (GitHub Actions logs in as this user to deploy)"
if ! id "$DEPLOY_USER" >/dev/null 2>&1; then
  adduser --disabled-password --gecos "" "$DEPLOY_USER"
fi
usermod -aG docker "$DEPLOY_USER"
DEPLOY_HOME="$(getent passwd "$DEPLOY_USER" | cut -d: -f6)"
install -d -m 700 -o "$DEPLOY_USER" -g "$DEPLOY_USER" "$DEPLOY_HOME/.ssh"
KEY="$DEPLOY_HOME/.ssh/github_actions"
if [ ! -f "$KEY" ]; then
  ssh-keygen -t ed25519 -N "" -C "github-actions-deploy" -f "$KEY" -q
  cat "$KEY.pub" >> "$DEPLOY_HOME/.ssh/authorized_keys"
  chown "$DEPLOY_USER:$DEPLOY_USER" "$KEY" "$KEY.pub" "$DEPLOY_HOME/.ssh/authorized_keys"
  chmod 600 "$KEY" "$DEPLOY_HOME/.ssh/authorized_keys"
fi

echo "==> Code in $APP_DIR"
if [ ! -d "$APP_DIR/.git" ]; then
  git clone "$REPO_URL" "$APP_DIR"
fi
chown -R "$DEPLOY_USER:$DEPLOY_USER" "$APP_DIR"

echo "==> Settings files"
cd "$APP_DIR/deploy"
IP="$(curl -4 -fsS https://api.ipify.org)"
DASHED_IP="${IP//./-}"
if [ ! -f .env ]; then
  cp .env.example .env
  sed -i \
    -e "s|^WEB_HOST=.*|WEB_HOST=chaba-$DASHED_IP.sslip.io|" \
    -e "s|^API_HOST=.*|API_HOST=api-$DASHED_IP.sslip.io|" \
    -e "s|^ACME_EMAIL=.*|ACME_EMAIL=$ACME_EMAIL|" \
    -e "s|^POSTGRES_PASSWORD=.*|POSTGRES_PASSWORD=$(openssl rand -hex 24)|" \
    .env
fi
if [ ! -f api.env ]; then
  cp api.env.example api.env
  sed -i "s|^APP_KEY=.*|APP_KEY=base64:$(openssl rand -base64 32)|" api.env
fi
chown "$DEPLOY_USER:$DEPLOY_USER" .env api.env
chmod 600 .env api.env

WEB_HOST="$(grep '^WEB_HOST=' .env | cut -d= -f2)"
API_HOST="$(grep '^API_HOST=' .env | cut -d= -f2)"

cat <<EOF

============================================================
 Server is ready. Next steps (docs/DEPLOYMENT-HETZNER.md):

 1. Fill in the R2 settings:
      nano $APP_DIR/deploy/api.env      (AWS_* lines)
      nano $APP_DIR/deploy/.env         (NEXT_PUBLIC_ASSET_URL)

 2. First deploy:
      su - $DEPLOY_USER -c "$APP_DIR/deploy/deploy.sh"

 3. Create the admin account:
      su - $DEPLOY_USER -c "$APP_DIR/deploy/create-admin.sh"

 4. Automatic deploys: add these GitHub repository secrets
      HETZNER_HOST     $IP
      HETZNER_SSH_KEY  (the private key printed below, all of it)

 Store:  https://$WEB_HOST
 API:    https://$API_HOST
============================================================

----- HETZNER_SSH_KEY (copy everything between these lines) -----
EOF
cat "$KEY"
echo "----- end of key -----"
