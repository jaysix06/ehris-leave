# DataTable (Vue.js) — Chunk & Lazy Loading

Vue.js DataTable component with **server-side chunk loading** and **lazy loading** to avoid lags and to ensure we **do not retrieve all data** at once (prevents freezing).

## How it works

- **Chunk loading:** The server (Laravel) returns only one page of rows per request (e.g. 10, 25, 50, 100). The full dataset is never sent to the client.
- **Lazy loading:** Optional “Load more” mode fetches the next chunk when the user clicks and appends it to the table, without loading the entire dataset.
- **No full data load:** Only the current chunk (or accumulated chunks in “Load more” mode) is kept in memory, so the UI stays responsive.

## Usage

Use with Laravel pagination (Inertia or API). Pass the current page `data` and `pagination` meta. Handle `@page-change` and `@per-page-change` (and optionally `@load-more` when using lazy mode).
