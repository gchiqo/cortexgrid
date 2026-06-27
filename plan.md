# Universal AI Knowledge Platform — Build Plan

> **Hackathon:** GTU Technological Hackathon 2026 — *Intelligent Systems: AI, Analytics & BI*.
> **Hard rule:** every user-facing UI and every chatbot/output text must be in **Georgian**. (Internal code, logs, DB can be English.)
> **Compliance:** all project code must be written and committed **during** the 8 hours — commit history is required, pre-built projects are disqualified. Design/wireframes/account setup beforehand is fine; the code is not.

---

## 1. Vision (full scope — nothing dropped)

A multi-tenant SaaS where a user connects their content (files, structured data, generic API, and — last — their WordPress/WooCommerce site), we process it into a searchable knowledge base, and they get a configurable AI chatbot/agent that answers questions and performs actions over **their own data**.

Each user can create **multiple AI configurations over the same data** — e.g. an *admin assistant* (helps add products), a *customer assistant* (helps choose products), and specialized ones (e.g. a *PC-builder advisor* that recommends compatible parts for a computer/hardware store).

---

## 2. User experience (the target flow)

1. **Public website** — explains what we do, pricing/features, sign-up.
2. **Registration & login** — user creates an account.
3. **User panel:**
   - Gets a personal **API key**.
   - **Uploads resources** to be processed (PDFs, XLSX/CSV by template, generic API push, WP plugin).
   - Sees **service usage** (requests, tokens, storage).
   - Sees an **overview of all data** we hold from their site/files/other sources.
   - Creates and manages **multiple AI configurations** per dataset (role + system prompt + allowed data scope + tools + model tier).
4. **Embeddable chatbot / API** — the configured assistant answers in Georgian and can call functions (actions) where allowed.

---

## 3. Core components

### 3.1 Ingestion (data in)
- **PDF analysis → DB.** Extract text + structure (tables, headings); store raw + structured.
- **Structured import (XLSX/CSV by our template).** Strict template → validated rows → typed records.
- **Generic import API.** A REST endpoint (`POST /v1/ingest`) authenticated by the user's API key, accepting JSON records + a `source` tag. *(This is the recommended path for "other forms" — an API is more flexible and auditable than ad-hoc file formats, and the WordPress plugin can reuse it.)*
- **WordPress plugin (LAST — optional if time runs out).** Reads WooCommerce products, blog posts, and page content, and pushes them to the generic import API. Build the WP plugin on top of `/v1/ingest` so it needs no special server code.

### 3.2 Processing / RAG pipeline
- **Chunking** (structure-aware: keep product fields / table rows intact).
- **Embeddings** (multilingual — must handle Georgian well).
- **Hybrid search** = lexical (**BM25**) + **semantic** (vector), score-fused.
- **Reranking** of the fused top-k.
- **Vector DB = PostgreSQL + `pgvector`** (single DB for relational + vectors → no separate store to operate).

### 3.3 Efficiency / inference
- **Caching everywhere:** embedding cache (don't re-embed unchanged content), retrieval cache, and **LLM prompt caching** (Claude prompt caching for stable system prompts + per-config context).
- **Fast inference path** via Groq for high-volume/low-latency steps.
- *(KV-cache optimization & quantization — TurboQuant/QJL — apply only if/when we self-host an open model. With hosted providers we get this for free; documented here as a roadmap item, not an 8-hour task.)*

### 3.4 AI configurations (key differentiator)
A config is just: `{ system_prompt (Georgian), data_scope, enabled_tools, model_tier }`.
- Ship presets: **admin-assist**, **customer-assist**, **PC-builder advisor**.
- Same dataset, many configs.

### 3.5 Chatbot / agent
- Answers in Georgian, grounded in retrieved chunks, **with source citations**.
- **Function calling / tool use** for actions (e.g. "add product", "create draft", "look up order") — gated per config.

### 3.6 Platform / control plane
- Auth, tenants, **API-key issuing**, **usage metering**, data-overview dashboard.

---

## 4. Models & providers

We use three providers via env vars: `GROQ_API_KEY`, `GEMINI_API_KEY`, `ANTHROPIC_API_KEY`. Route each task to the provider that fits.

| Role in the system | Provider (env var) | Suggested model(s) | Why |
|---|---|---|---|
| **Main chatbot answers, agentic function calling, structured extraction, the "expert assistant" configs** | **Anthropic** (`ANTHROPIC_API_KEY`) | `claude-sonnet-4-6` (default workhorse), `claude-haiku-4-5` (cheap/fast steps), `claude-opus-4-8` (hardest reasoning) | Best tool use + structured outputs (`output_config.format`, `strict` tools); prompt caching; strong instruction-following for grounded, cited Georgian answers. |
| **Multimodal document/image understanding, structured extraction from PDFs/images, embeddings** | **Google Gemini** (`GEMINI_API_KEY`) | a current Gemini multimodal model for PDF/image → structured; a Gemini embedding model for vectors | Native multimodal + large context; strong multilingual coverage incl. Georgian. *(Confirm exact model strings in the Google AI console; test Georgian embedding quality first.)* |
| **High-volume / low-latency: query rewriting, classification, lead-scoring features, draft generation; speech-to-text for the Georgian voice path** | **Groq** (`GROQ_API_KEY`) | a hosted Llama (e.g. Llama 3.3 70B) for fast chat; Whisper-large-v3 for STT | Very fast/cheap inference for the hot path; offloads cheap work from Claude. *(Confirm current Groq model IDs in the Groq console.)* |

**Routing rule of thumb:** cheap/fast & high-volume → **Groq** (or Claude Haiku); multimodal/extraction & embeddings → **Gemini**; quality answers + agentic actions → **Claude**.

> Note on accuracy: Anthropic has no first-party embeddings endpoint, so **embeddings come from Gemini** (or an open multilingual model) — that's why Gemini owns the vector path. Claude owns reasoning/agent; Groq owns the fast path.

---

## 5. Data model (PostgreSQL sketch)

```
tenants(id, name, created_at)
users(id, tenant_id, email, password_hash, role)
api_keys(id, tenant_id, key_hash, label, created_at, last_used_at, revoked)
sources(id, tenant_id, type[pdf|xlsx|csv|api|wordpress], name, status, created_at)
documents(id, source_id, tenant_id, title, raw_text, structured jsonb, created_at)
chunks(id, document_id, tenant_id, content, metadata jsonb, embedding vector(N))   -- pgvector
                                                                                     -- + tsvector col for BM25/full-text
ai_configs(id, tenant_id, name, system_prompt, data_scope jsonb, enabled_tools jsonb, model_tier)
usage_events(id, tenant_id, api_key_id, kind[ingest|query|tokens], qty, cost, created_at)
```
- `chunks.embedding` → pgvector index (HNSW/IVFFlat).
- `chunks` also has a `tsvector` column + GIN index for **BM25-style** lexical search (`ts_rank`, or ParadeDB/VectorChord BM25 if available).
- **Every query is tenant-scoped** (`WHERE tenant_id = ?`) — non-negotiable for multi-tenant isolation.

---

## 6. Suggested tech stack

- **DB:** PostgreSQL + `pgvector` (+ full-text / BM25).
- **Backend/API:** one service exposing `/v1/ingest`, `/v1/query`, `/v1/configs`, auth, usage. (Node/TypeScript or Python/FastAPI — pick one and stay in it.)
- **Frontend:** web app for the public site + user panel + embeddable chat widget (Georgian UI).
- **WordPress plugin:** PHP plugin that calls `/v1/ingest` with the user's API key (built last).

---

## 7. Build order (keep everything; sequence for the 8 hours)

Everything in §3 stays in scope. This is the order to build so we always have a working demo; the WordPress plugin is intentionally **last** and optional.

1. **DB + pgvector schema** and tenant scoping.
2. **Auth + API key issuing** (minimal panel).
3. **Ingestion #1 — PDF → documents/chunks**, and **#2 — XLSX/CSV template import**.
4. **Generic import API** (`/v1/ingest`) — also the foundation the WP plugin will reuse.
5. **RAG pipeline:** chunk → Gemini embeddings → store → **hybrid (BM25 + vector)** retrieval → rerank.
6. **Chatbot/agent (`/v1/query`):** retrieve → **Claude** answers in **Georgian with citations**; add **function calling** for one action.
7. **AI configurations:** config model + 2–3 presets (admin-assist, customer-assist, **PC-builder advisor**).
8. **User panel polish:** data overview + usage metering + config management.
9. **Voice path (optional):** Groq Whisper STT → query → TTS.
10. **WordPress plugin (LAST / optional):** WooCommerce + blog + pages → `/v1/ingest`.

> For the live hackathon demo, the must-haves are steps 1–7 working end-to-end in Georgian. Steps 8–10 are upside; pitch anything unfinished as the roadmap.

---

## 8. Environment variables

```
DATABASE_URL=postgres://...
GROQ_API_KEY=...        # fast inference (Llama) + Whisper STT
GEMINI_API_KEY=...      # multimodal extraction + embeddings
ANTHROPIC_API_KEY=...   # main chatbot, agentic function calling, structured extraction
```

---

## 9. Open items to confirm early (first 30 min)

- **Georgian quality check:** verify the chosen **Gemini embedding model** retrieves Georgian well, and that **Claude** answers cleanly in Georgian. If retrieval is weak, swap the embedding model before building further.
- Confirm **current model IDs** in each provider's console (Groq + Gemini move fast).
- Pick **one** backend language and stack to it for the whole 8 hours.
