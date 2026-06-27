# Running the GTUH AI platform

Laravel 13 + PostgreSQL/pgvector. Universal content ingestion → hybrid RAG → Georgian chatbot,
multi-tenant with API keys. See [plan.md](plan.md) for the full architecture.

## What's implemented

- **Auth:** email/password + Google (Socialite). Registration auto-provisions a tenant, an API key, and 3 Georgian preset AI configs.
- **Knowledge Explorer** (`📊 ცოდნის მკვლევარი` on a dataset) — shows what the platform *understood*: entity facets (brands, categories, price ranges) aggregated from the structured fields, plus a one-click **AI analysis** (Claude) that detects relationships (`socket → compatible_with`) and missing info. Reinforces "platform, not chatbot."
- **Datasets** — a tenant has many datasets (e.g. "computer store", "news portal"). **One dataset is fed by many sources** (PDF + CSV + API together) and has **many chatbots**; each chatbot only searches **its own dataset** (verified isolation). The dashboard is a datasets list → each opens a dataset workspace (sources + upload + chatbots + test-chat). `POST /v1/ingest` takes an optional `"dataset"` (id or name) to target one.
- **Dashboard** (`/dashboard`, Georgian): usage tiles, API-key issue/revoke; the dataset workspace has a live **test-chat**, plus:
  - **File upload** — PDF / CSV / XLSX / TXT into a dataset, stored in the DB via the chunk→embed pipeline (PDF text via Gemini multimodal; CSV/XLSX rows → one document each).
  - **AI config management** — create / edit / delete configurations (name, model tier, Georgian system prompt, tools). The 3 presets are just starters.
  - **Tool execution (admin chatbots)** — a chatbot with `enabled_tools` (`add_item` / `update_item` / `find_items`) can *act* on its dataset via chat ("add this product…"), and added items embed synchronously so they're instantly searchable. **Security:** tools run only on trusted surfaces (dashboard / console / your API key); the **public widget never executes tools**.
  - **Auto-generate configs from your data** (`✨ გენერაცია მონაცემებიდან`) — Claude samples your ingested content, infers the business, and proposes 2–3 ready-made chatbots (editable) you accept with one click.
- **ტესტ-კონსოლი (glass-box console)** — split screen: chat on the right, a live pipeline trace on the left (Groq query-rewrite → Gemini embedding → semantic + lexical candidates with scores → RRF fusion → Claude generate, with timings + tokens). Built for demos and debugging.
- **Conversational memory** — follow-ups keep context: Groq rewrites the follow-up into a standalone search query, and recent turns are fed to Claude (works in the widget, console, and stored conversations).
- **Embeddable widget (each AI config = a deployable chatbot):**
  - Every config has a browser-safe **public key** + optional **domain allowlist** + widget on/off.
  - The config edit page shows a copy-paste snippet: `<script src="<host>/embed.js?key=pk_gtuh_..." async></script>` — drop it on any site to get a floating Georgian chat bubble.
  - Public endpoint `POST /public/chat` (CORS, throttled) answers via the same RAG pipeline and **stores every conversation + message**.
  - **საუბრები** (Conversations) in the dashboard: list of chats per chatbot, message log, and per-chat token usage.
- **API (`/v1`, API-key auth):**
  - `POST /v1/ingest` — text or structured `records` (the WordPress plugin will reuse this).
  - `POST /v1/query` — hybrid retrieve → Claude answer in Georgian **with citations**.
- **RAG:** chunk → Gemini embeddings (`gemini-embedding-001`, 768-d) → Postgres hybrid search (pgvector cosine + `tsvector` BM25) fused with Reciprocal Rank Fusion → Claude.
- **Providers:** Anthropic (answers/agent), Gemini (embeddings + multimodal), Groq (fast path / Whisper). Keys live in `.env`.

## Prerequisites (already done on this machine)

```bash
sudo apt install -y postgresql-18-pgvector
sudo -u postgres createdb -O d gtuh
sudo -u postgres psql -d gtuh -c "CREATE EXTENSION IF NOT EXISTS vector;"
php artisan migrate
```

## Run it

Two processes (the queue worker does the embedding):

```bash
php artisan serve                 # http://127.0.0.1:8000
php artisan queue:work            # processes EmbedChunks jobs
```

Seed demo data (prints a one-time API key + login):

```bash
php artisan db:seed --class=DemoSeeder      # admin + 3 preset chatbots in one dataset
# Login: admin@gtuh.local / password
```

**Clean start for a demo** — wipes everything and creates the admin + four empty datasets
(კომპიუტერული მაღაზია / ახალი ამბების პორტალი / ფილმების საიტი / ტურისტული სააგენტო):

```bash
php artisan migrate:fresh --seed --seeder=DemoResetSeeder
```

Then upload the matching file from **`examples/`** into each dataset and watch the pipeline animation
(extract → chunk → store → embed → ready). Run `php artisan queue:work` so embeddings finish and the
animation reaches "ready".

Open http://127.0.0.1:8000 → log in → dashboard.

## Try the API

```bash
KEY=<your api key from the dashboard or seeder>

# Ingest (Georgian)
curl -X POST http://127.0.0.1:8000/v1/ingest \
  -H "Authorization: Bearer $KEY" -H "Content-Type: application/json" \
  -d '{"source_name":"ტურები","title":"ბათუმის ტური","text":"ბათუმის სამდღიანი ტური. ფასი 450 ლარი..."}'

# (run the queue worker so embeddings get computed)

# Ask
curl -X POST http://127.0.0.1:8000/v1/query \
  -H "Authorization: Bearer $KEY" -H "Content-Type: application/json" \
  -d '{"question":"რა ღირს ბათუმის ტური?"}'
```

Structured ingest (e.g. WooCommerce export) uses `records` instead of `text`:

```json
{ "type":"wordpress", "source_name":"products",
  "records":[ {"title":"ლეპტოპი X","name":"ლეპტოპი X","price":2500,"text":"15.6 ინჩი, 16GB RAM..."} ] }
```

## Google OAuth note

The Google client id/secret are set in `.env`. To complete Google login locally you must add the
callback URL to the Google Cloud Console (Authorized redirect URIs), e.g.
`http://localhost:8000/auth/google/callback`, and set `APP_URL=http://localhost:8000`.
Email/password login works without this.

## Config knobs (`.env`)

- `ANTHROPIC_MODEL` (default `claude-sonnet-4-6`); tiers map in `config/services.php` (fast/standard/max → haiku/sonnet/opus).
- `GEMINI_EMBEDDING_MODEL=gemini-embedding-001`, `EMBEDDING_DIM=768` (must match the `chunks.embedding` column — changing it means re-migrating).
- `GROQ_MODEL=llama-3.3-70b-versatile`.
