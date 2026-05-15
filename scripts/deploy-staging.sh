#!/usr/bin/env bash
# scripts/deploy-staging.sh — Deploy to staging server via rsync over SSH.
# Required GitHub Secrets: STAGING_SSH_KEY, STAGING_HOST, STAGING_USER, STAGING_PATH
set -euo pipefail

# ── Validate required secrets ─────────────────────────────────────────────
missing=()
[ -z "${SSH_PRIVATE_KEY:-}" ] && missing+=("STAGING_SSH_KEY")
[ -z "${DEPLOY_HOST:-}" ]     && missing+=("STAGING_HOST")
[ -z "${DEPLOY_USER:-}" ]     && missing+=("STAGING_USER")
[ -z "${DEPLOY_PATH:-}" ]     && missing+=("STAGING_PATH")

if [ ${#missing[@]} -gt 0 ]; then
  echo "ERROR: The following secrets are not configured in GitHub Settings"
  echo "       > Environments > master > Secrets:"
  printf '  - %s\n' "${missing[@]}"
  exit 1
fi

# ── Set up SSH ────────────────────────────────────────────────────────────
mkdir -p ~/.ssh
chmod 700 ~/.ssh
echo "$SSH_PRIVATE_KEY" > ~/.ssh/deploy_key
chmod 600 ~/.ssh/deploy_key
ssh-keyscan -H "$DEPLOY_HOST" >> ~/.ssh/known_hosts 2>/dev/null

SSH_CMD="ssh -i ~/.ssh/deploy_key -o StrictHostKeyChecking=no -o BatchMode=yes"

# ── Sync files to staging server ──────────────────────────────────────────
echo "Deploying to staging: ${DEPLOY_USER}@${DEPLOY_HOST}:${DEPLOY_PATH}"

rsync -az --delete \
  --exclude '.git/' \
  --exclude '.github/' \
  --exclude 'node_modules/' \
  --exclude 'tests/' \
  --exclude 'coverage/' \
  --exclude '*.md' \
  --exclude '.env*' \
  -e "$SSH_CMD" \
  . "${DEPLOY_USER}@${DEPLOY_HOST}:${DEPLOY_PATH}/"

# ── Post-deploy: install prod deps and apply schema on server ─────────────
$SSH_CMD "${DEPLOY_USER}@${DEPLOY_HOST}" bash <<REMOTE
  set -euo pipefail
  cd "${DEPLOY_PATH}"

  echo "Installing production dependencies..."
  composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

  echo "Applying database schema..."
  mkdir -p database
  php -r "
    \\\$db = new PDO('sqlite:database/wizdam.db');
    \\\$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \\\$db->exec(file_get_contents('database/schema.sql'));
    echo 'Schema OK' . PHP_EOL;
  "

  echo "Deploy to staging complete."
REMOTE
