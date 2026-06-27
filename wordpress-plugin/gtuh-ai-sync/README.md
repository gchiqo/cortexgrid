# GTUH AI Sync — WordPress plugin

Syncs your WordPress site's content to the GTUH AI knowledge platform so your AI agents can answer from it.

## What it syncs
- **WooCommerce products** → the *Products* dataset (name, price, SKU, category, **permalink URL**, description)
- **Blog posts** → the *Blog* dataset (title, category, date, **URL**, content)
- **Pages** → the *Pages* dataset (title, **URL**, content)

Each item is sent with an `external_id` (e.g. `product-42`), so **re-syncing updates the item instead of creating a duplicate** — your data stays fresh.

## Install
1. Zip the `gtuh-ai-sync` folder (or download the provided `gtuh-ai-sync.zip`).
2. WordPress admin → **Plugins → Add New → Upload Plugin** → choose the zip → **Install** → **Activate**.
3. Open **GTUH AI** in the admin sidebar.

## Configure
- **API Base URL** — your platform host, e.g. `https://your-host` (no trailing slash).
- **API Key** — from the platform: **Dashboard → API გასაღებები** (shown once).
- **Dataset names** — the platform datasets to sync into (created automatically if they don't exist).

## Sync
Click **🛒 Products / 📝 Blog / 📄 Pages** under "Sync now". Each button pushes that content type in batches of 50. Re-run anytime to refresh.

> Tip: in the platform, create one "site assistant" agent whose **home dataset = Products** and add **Blog** and **Pages** as *additional datasets* — it will then answer across your whole site.

## Notes
- Manual sync only (run it whenever you update content). Scheduled/real-time sync is on the roadmap.
- Requires WooCommerce active for the Products button.
