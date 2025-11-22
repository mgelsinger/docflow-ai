# DocFlow AI

DocFlow AI is a Laravel + Vue 3 application that turns unstructured documents into structured data. Upload PDFs or images, let an on-prem LLM (Ollama + Qwen) classify them, and receive clean, export-ready records for invoices, contracts, and general documents.

## Why it matters
- **Automates document intake** so teams spend less time on data entry.
- **AI-powered classification & extraction** with transparent JSON exports and retry flows when something goes wrong.
- **Built for developers** with a clean Laravel backend, Inertia-powered Vue frontend, and queue-based processing you can deploy anywhere.

## Feature highlights
- Document uploads for PDF/JPG/PNG with per-user access controls.
- Automatic classification into invoices, contracts, or general documents using Ollama + Qwen (`qwen3-vl` by default).
- Structured extraction:
  - Invoices: vendor details, totals, currency, line items, and validation warnings.
  - Contracts: counter-parties, key dates, and summary text.
  - General documents: raw JSON snapshot for downstream processing.
- Dashboard for browsing, filtering, and viewing document status and metadata.
- Download originals, export extracted JSON, retry failed extractions, and bulk CSV invoice export.

## Tech stack
- **Backend:** Laravel 11, PHP 8.2+, Inertia
- **Frontend:** Vue 3, Vite, Tailwind CSS
- **AI:** Ollama (configurable base URL/model), Qwen3-VL prompts for classification and extraction
- **Storage & queues:** Laravel filesystem + queue workers (e.g., `database`, `redis`, or `sqs` drivers)

## Getting started
### Prerequisites
- PHP 8.2+ with Composer
- Node.js 20+ with npm
- A database supported by Laravel (SQLite/PostgreSQL/MySQL)
- [Ollama](https://ollama.com) running with the `qwen3-vl:8b` model (or set `OLLAMA_MODEL` in `.env`)

### Setup
1. Clone the repo and install dependencies:
   ```bash
   composer install
   npm install
   ```
2. Copy environment config and generate keys:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
3. Configure your database and Ollama settings in `.env`:
   ```ini
   DB_CONNECTION=sqlite # or mysql/pgsql
   OLLAMA_BASE_URL=http://127.0.0.1:11434
   OLLAMA_MODEL=qwen3-vl:8b
   ```
4. Run migrations and link storage:
   ```bash
   php artisan migrate
   php artisan storage:link
   ```
5. Start the dev servers (separate terminals):
   ```bash
   php artisan serve
   npm run dev
   ```
6. Process queued document extraction jobs:
   ```bash
   php artisan queue:work
   ```

### Sample workflow
1. Register/login and open **Upload Document**.
2. Submit a PDF/JPG/PNG; the file is stored and queued for extraction.
3. Watch processing status on **All Documents**; download the original, export JSON, or retry if it failed.
4. For invoices, export a CSV of all parsed invoices at `/exports/invoices.csv`.

## Configuration notes
- Ollama settings live in `config/ollama.php`; defaults target `http://127.0.0.1:11434` and `qwen3-vl:8b`.
- Document files are stored under `storage/app/documents/{YYYY}/{MM}/` with unique names; originals remain downloadable.
- Queue driver, cache, and storage can be swapped in `.env` for production.

## Testing
Run the backend test suite:
```bash
php artisan test
```

## Deployment tips
- Use a persistent queue worker (e.g., Supervisor) so extraction stays responsive.
- Serve the built assets in production with `npm run build` and a proper web server (Nginx/Apache) pointing to `public/`.
- Keep Ollama alongside the app or on a secured internal host for low-latency model calls.

## Project status & roadmap
The core flows—upload, classify, extract, export—are in place. Future enhancements could include per-tenant billing, audit trails, S3 storage, and pluggable models beyond Qwen.
