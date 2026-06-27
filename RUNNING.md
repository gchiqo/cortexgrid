# Running the GTUH AI platform

Laravel 13 + PostgreSQL/pgvector. Universal content ingestion → hybrid RAG → Georgian chatbot,
multi-tenant with API keys. See [plan.md](plan.md) for the full architecture.

## What's implemented

- **Auth:** email/password + Google (Socialite). Registration auto-provisions a tenant, an API key, and 3 Georgian preset AI configs.
- **Dashboard** (`/dashboard`, Georgian): usage tiles, API-key issue/revoke, a live **test-chat**, plus:
  - **File upload** — PDF / CSV / XLSX / TXT, stored in the DB via the chunk→embed pipeline (PDF text via Gemini multimodal; CSV/XLSX rows → one document each).
  - **AI config management** — create / edit / delete configurations (name, model tier, Georgian system prompt, tools). The 3 presets are just starters.
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
php artisan db:seed --class=DemoSeeder
# Login: admin@gtuh.local / password
```

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
