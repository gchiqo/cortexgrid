# GTUH AI — ცოდნის პლატფორმა (Knowledge AI Platform)

> **Not a chatbot — a knowledge platform.** Connect your data (files, API, or a WordPress site), the platform *understands* it, and you get Georgian‑speaking AI agents you embed on any site with one line of code.

Built for the **GTU Technological Hackathon 2026** (*Intelligent Systems: AI, Analytics & BI*). Multi‑tenant SaaS: **Laravel + PostgreSQL/pgvector**, hybrid RAG across **Groq · Gemini · Anthropic (Claude)**, Georgian‑first.

---

## ✨ What it does

- **Datasets** — a tenant has many datasets (one per business/topic). One dataset is fed by **many sources** (PDF + CSV + XLSX + API together); retrieval is **scoped per dataset** (isolated).
- **Multiple AI agents per dataset** — user‑helper, admin‑helper, etc., each with its own Georgian system prompt and model tier. **Auto‑generate** them from your data with one click.
- **Hybrid RAG with citations** — vector (pgvector cosine) **+** lexical (Postgres BM25/`tsvector`), fused with **RRF**, optional **Groq reranker**; grounded, cited, Georgian answers.
- **Embeddable widget** — every agent gets a public key + a `<script>` snippet. **Streaming** answers, **clickable citations**, per‑agent **appearance** (color/position/greeting), 👍/👎 **feedback**, and **lead capture**.
- **Agentic admin tools** — admin agents can `add_item` / `update_item` / `find_items` on their dataset **by chat** (added items embed synchronously → instantly searchable). Blocked on the public widget for safety.
- **Glass‑box console** — a split screen showing the live pipeline (Groq rewrite → Gemini embed → vector + BM25 → RRF → Claude) with tokens & timings.
- **Knowledge Explorer** — what the platform *understood*: entity facets (brands, categories, price ranges) + one‑click **AI analysis** (relationships + missing info).
- **Platform bits** — API keys, usage/credits, **Flitt billing**, feedback insights, conversation history, a REST API (`/v1/*`) and a **WordPress plugin**.

---

## 🧱 Tech stack

| Layer | Choice |
|---|---|
| Backend / UI | **Laravel 13** (PHP 8.3), Blade + Tailwind (CDN) |
| Database | **PostgreSQL 18** + **pgvector** (HNSW cosine) + `tsvector`/GIN (BM25) |
| Embeddings | **Gemini** `gemini-embedding-001` (768‑dim) |
| Generation / agents | **Anthropic Claude** (Sonnet / Haiku / Opus tiers), official PHP SDK |
| Fast path | **Groq** (Llama) — query rewrite + reranker |
| Auth | Email/password + **Google** (Socialite) |
| Payments | **Flitt** (credit top‑ups) |

**Pipeline:** `Connect → Import → Document (+structured fields) → Chunk → Embed → (vector + BM25) index → Hybrid retrieve → Rerank → Claude → Answer / Action`.

---

## 🚀 Getting started

### Prerequisites
- **PHP 8.3+** and **Composer**
- **PostgreSQL 16/17/18** with the **pgvector** extension
- API keys: **Groq**, **Gemini**, **Anthropic** (free tiers work — see links below)

### 1. Clone & install
```bash
git clone <your-repo-url> gtuh && cd gtuh
composer install
cp .env.example .env
php artisan key:generate
```

### 2. PostgreSQL + pgvector
```bash
# Debian/Ubuntu example (match your PG version, e.g. 18):
sudo apt install -y postgresql-18-pgvector
sudo -u postgres createdb gtuh
sudo -u postgres psql -d gtuh -c "CREATE EXTENSION IF NOT EXISTS vector;"
```
Set the DB connection in `.env` (`DB_DATABASE=gtuh`, plus host/user/password for your setup).

### 3. API keys → `.env`
```env
ANTHROPIC_API_KEY=sk-ant-...      # https://console.anthropic.com/
GEMINI_API_KEY=...                # https://aistudio.google.com/apikey
GROQ_API_KEY=gsk_...              # https://console.groq.com/keys
# optional:
GOOGLE_CLIENT_ID= / GOOGLE_CLIENT_SECRET=          # Google login (Socialite)
FLITT_MERCHANT_ID=1549901 / FLITT_SECRET_KEY=test  # Flitt test creds
```
> **Gemini note:** the embedding model must exist for your key — this project uses `gemini-embedding-001` (768‑dim). Keep `EMBEDDING_DIM=768` in sync with the model.

### 4. Migrate & seed
```bash
php artisan migrate

# Option A — empty clean start (admin + 4 empty datasets):
php artisan db:seed --class=DemoResetSeeder

# Option B — full demo: also imports 3 example datasets + 6 agents (makes real
# Gemini embedding calls, ~30s). Requires GEMINI_API_KEY to be set.
php artisan db:seed --class=DemoContentSeeder
```
Each seeder prints a **login** and a one‑time **API key**. Default login: **`admin@gtuh.local` / `password`**.

### 5. Run
```bash
php artisan serve          # http://127.0.0.1:8000
php artisan queue:work     # REQUIRED — embeddings run on the queue
```
> No `npm` build needed — the UI uses Tailwind via CDN.

---

## 🕹️ Using it

1. Open **`/`** (landing) → **`/dashboard`** (login `admin@gtuh.local` / `password`).
2. Open a **dataset** → **upload** a file (`examples/*.csv`) → watch the pipeline animation.
3. Click **📊 ცოდნის მკვლევარი** to see what was understood; **✨ generate** agents from the data.
4. Try an agent in the **ტესტ-კონსოლი** (glass‑box) or grab its **embed snippet** from the agent's edit page.
5. Admin agent can **add products by chat**; watch **👍/👎**, **ლიდები** (leads), **ბილინგი** (credits) fill up.

`examples/` contains ready datasets: computer store, news portal, movies, travel.

---

## 🔌 API (Bearer = tenant API key)

| Method | Endpoint | Purpose |
|---|---|---|
| `POST` | `/v1/ingest` | Ingest `text` or `records` into a `dataset` (id or name) |
| `POST` | `/v1/query` | Grounded, cited answer over the tenant's data |
| `GET`  | `/v1/agents` | List agents + embed snippets |
| `POST` | `/public/chat` · `/public/chat/stream` | Widget chat (public key, CORS) |
| `GET`  | `/embed.js?key=pk_...` | The embeddable widget script |

```bash
curl -X POST http://127.0.0.1:8000/v1/ingest \
  -H "Authorization: Bearer <API_KEY>" -H "Content-Type: application/json" \
  -d '{"dataset":"My store","records":[{"name":"RTX 4070","price_gel":2100,"url":"https://.../rtx-4070"}]}'
```
Full, tabbed docs are in‑app at **`/dashboard/docs`**.

---

## 🧩 WordPress plugin

`wordpress-plugin/gtuh-ai-sync.zip` — install in WP, set Base URL + API Key, then:
- **Sync** WooCommerce products / posts / pages into per‑type datasets (idempotent via `external_id`).
- **Embed widget** — pick an agent in settings; the plugin auto‑injects the chat widget site‑wide (`wp_footer`).

---

## 📁 Project structure

```
app/
  Http/Controllers/        Web/* (dashboard, datasets, configs, billing, …) · Api/* (ingest, query, agents) · PublicChatController · WidgetController
  Services/                Anthropic · Gemini · Groq · KnowledgeProfiler · ConfigSuggester
    Rag/                   AskService · Retriever · Reranker · Chunker
    Ingest/                IngestService
    Tools/                 ToolRegistry (add_item / update_item / find_items)
  Jobs/EmbedChunks.php      async embedding
  Models/                  Tenant · Dataset · Source · Document · Chunk · AiConfig · Conversation · Message · Lead · Payment · ApiKey
database/migrations/        schema (pgvector, tsvector, datasets, credits, …)
database/seeders/           DemoResetSeeder · DemoContentSeeder
resources/views/            landing · dashboard · dataset · console · explorer · insights · billing · leads · …
routes/                     web.php · api.php
examples/                   4 ready demo datasets (CSV)
wordpress-plugin/           the WP sync + embed plugin
```

---

## 📚 More docs
- **[plan.md](plan.md)** — architecture, data model, and full feature status.
- **[RUNNING.md](RUNNING.md)** — detailed run notes & feature walkthrough.
- **[PROMPTS.md](PROMPTS.md)** — every prompt used to build this (the build log).

---

## 🔐 Security notes
- **Never commit `.env`** — it holds live API keys (it's git‑ignored by default here).
- If a key was ever exposed, **rotate it** in the provider console.
- API keys are stored **hashed** (SHA‑256); the plaintext is shown only once at creation. Chatbot **public keys** are browser‑safe and gated by a per‑agent **domain allowlist**; write **tools never run** on the public widget.
- Flitt credentials shipped here are **test** creds; use real merchant creds + a public callback URL in production.

---

*Georgian‑first AI knowledge platform · Laravel · PostgreSQL/pgvector · Groq · Gemini · Claude.*
