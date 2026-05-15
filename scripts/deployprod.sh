#!/usr/bin/env bash
# scripts/deploy-prod.sh — Deploy to production server via rsync over SSH.
# Required GitHub Secrets: PROD_SSH_KEY, PROD_HOST, PROD_USER, PROD_PATH
set -euo pipefail

# ── Validate required secrets ─────────────────────────────────────────────
missing=()
[ -z "${SSH_PRIVATE_KEY:-}" ] && missing+=("PROD_SSH_KEY")
[ -z "${DEPLOY_HOST:-}" ]     && missing+=("PROD_HOST")
[ -z "${DEPLOY_USER:-}" ]     && missing+=("PROD_USER")
[ -z "${DEPLOY_PATH:-}" ]     && missing+=("PROD_PATH")

if [ ${#missing[@]} -gt 0 ]; then
  echo "ERROR: The following secrets are not configured in GitHub Settings"
  echo "       > Environments > production > Secrets:"
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

# ── Sync files to production server ───────────────────────────────────────
echo "Deploying to production: ${DEPLOY_USER}@${DEPLOY_HOST}:${DEPLOY_PATH}"

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

  echo "Deploy to production complete."
REMOTE
