# OfferWall - Task-based Earning Platform

This repository contains a complete, lightweight OfferWall web application scaffold using PHP 8+, MySQL, Tailwind CSS, and a vanilla JS SPA router.

Features
- User authentication (register/login/session tokens)
- Offer wall with click tracking and external postback handling
- Wallet with transaction audit trail
- Withdrawals via UPI (manual admin approval)
- Admin endpoints and dashboard primitives
- SPA frontend with animated UI using Tailwind and micro-interactions

Folder structure
- public/: Web root (index.php, assets, api endpoints)
- src/: PHP core (config, init, helpers)
- migrations/schema.sql: MySQL schema

Quick start (development)

1. Create database and run migrations:

```bash
mysql -u root -p < migrations/schema.sql
```

2. Configure environment (recommended):

Set environment variables (example export):

```bash
export DB_HOST=127.0.0.1
export DB_USER=root
export DB_PASS=yourpass
export DB_NAME=offerwall
export POSTBACK_SECRET=change_this_secret
```

3. Serve the `public` directory with PHP built-in server (dev):

```bash
cd public
php -S 0.0.0.0:8080
```

Open http://localhost:8080

Security & design notes
- Passwords hashed with PHP `password_hash`.
- Token-based sessions stored server-side in `sessions` table (supports revocation).
- IP and User-Agent logged on session creation.
- Postback endpoint validates a shared secret (`settings.postback_secret`) and is idempotent by checking `click_id` state.
- Wallet changes always through server-side atomic transactions and recorded to `wallet_transactions` for full audit trail.
- Withdrawals require admin approval; initial request automatically creates debit transaction to prevent double-spend.
- Admin endpoints are protected by `auth_guard()` and role-checks.

Scaling and production notes
- Move settings into a more secure env store and rotate `postback_secret` regularly.
- Add rate limiting (e.g., nginx + fail2ban or application middleware) and IP/device blocking logic in `blocked_entities`.
- Switch to prepared deployments with Composer dependencies and a queue worker for heavy postback processing.
- For high load, separate read-replica databases and process postbacks asynchronously with a job queue.

Next steps and TODO
- Implement stronger rate-limiting middleware and per-endpoint quotas.
- Add admin UI pages and data tables (server APIs are present).
- Implement email for forgot-password flows and two-factor auth.
# offerwall