# Chat With Your Docs

A RAG (Retrieval-Augmented Generation) application that lets you upload documents and chat with them using AI. Upload PDFs, text files, or markdown files and ask questions — the app finds the most relevant passages and generates answers grounded in your documents.

## Stack

- **Laravel 13** — backend framework
- **Livewire 4** — reactive UI without writing a SPA
- **Laravel AI SDK** (`laravel/ai`) — agent, embeddings, similarity search, conversation memory
- **PostgreSQL + pgvector** — vector storage and cosine similarity search
- **Tailwind CSS + Typography plugin** — styling and markdown rendering

## Features

- Upload PDF, TXT, or MD files
- Documents are chunked (150 words, 30-word overlap) and embedded asynchronously via queued jobs
- Per-document conversation threads — switching documents loads that document's chat history
- Streaming AI responses rendered as formatted markdown
- Document filter — select a specific document to scope the search to that file only
- Sidebar shows documents only once all chunks are fully embedded

## Requirements

- PHP 8.2+
- PostgreSQL with the `pgvector` extension
- An OpenAI API key (used for embeddings and chat)
- Node.js (for asset compilation)

## Setup

```bash
git clone <repo>
cd <repo>
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
```

Configure your `.env`:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=your_database
DB_USERNAME=your_user
DB_PASSWORD=your_password

OPENAI_API_KEY=sk-...

QUEUE_CONNECTION=database
```

Publish the AI SDK config and migrations:

```bash
php artisan vendor:publish --provider="Laravel\Ai\AiServiceProvider"
```

Run migrations:

```bash
php artisan migrate
```

## Running

You need three processes running:

```bash
# Terminal 1 — web server
php artisan serve

# Terminal 2 — queue worker (processes embedding jobs)
php artisan queue:work

# Terminal 3 — asset watcher (development only)
npm run dev
```

Then open [http://localhost:8000/chat](http://localhost:8000/chat).

## How It Works

1. **Upload** — file is stored, text extracted (PDF via `smalot/pdfparser`, plain text otherwise)
2. **Chunking** — text split into 150-word chunks with 30-word overlap; one queued job dispatched per chunk
3. **Embedding** — each job calls the OpenAI embeddings API and stores the vector in PostgreSQL
4. **Chat** — user question is embedded at query time; top 15 most similar chunks retrieved via cosine similarity; an AI agent synthesizes the answer using only those chunks
5. **Streaming** — response streams token by token to the browser via SSE

## Restarting the Worker After Code Changes

```bash
php artisan queue:restart
php artisan queue:work
```
