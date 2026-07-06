# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

"Sistem Prediksi Stok Pangan Lhokseumawe" — a PHP-native (no framework) web app for managing food-stock (stok pangan) time-series data, preprocessing it for LSTM, training per-commodity LSTM models via a Python subprocess, and reporting evaluation/forecast results, plus a public landing page. Domain language is Indonesian throughout (routes, column names, UI text) — keep new code/comments consistent with that convention where it touches existing modules.

## Commands

There is no build step, package.json, or test suite — this is plain PHP + a couple of standalone Python scripts.

- Install PHP deps: `composer install` (only dependency: `tecnickcom/tcpdf` for PDF export)
- Serve locally: place the project under a web root so `/` resolves to `index.php` (e.g. Laragon at `C:\laragon\www\LSTM`, URL `http://localhost/LSTM`). `.htaccess` rewrites all non-file/non-dir requests to `index.php`.
- Import DB schema: `mysql -u root db_stok_pangan < database/schema.sql` (note: `data_preprocessing_lstm` table is created lazily on first preprocessing run, not by the schema file)
- Python deps for training: `pip install mysql-connector-python numpy tensorflow`
- Run a training batch manually (normally triggered from the UI): `python database/train_lstm_batch.py <batch_id>`
- Seed sample historical stock data: `python database/seed_stok_pangan.py`
- Regenerate the thesis/report export (bab4): `python database/export_bab4_analysis.py`
- Config is env-var driven with hardcoded fallbacks, no `.env` loader exists — set `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `APP_URL` as real environment variables if not using the Laragon defaults (`root`/no password, `db_stok_pangan`, `http://localhost/lstm`).

No automated tests exist in this repo.

## Architecture

### Custom micro-framework (`core/`)

There is no Laravel/Symfony — routing, DB access, sessions, and CSRF are hand-rolled:

- `core/Router.php` — regex-based route matching (`{param}` → capture group), runs middleware then instantiates `Controller::action(...$params)`. Routes are registered in `routes/web.php` and dispatched from `bootstrap/start.php`.
- `core/Database.php` — a single lazily-created static PDO connection (`Database::connection()`), config from `config/database.php`, prepared statements only (`EMULATE_PREPARES => false`).
- `core/Controller.php` — base class with `view()` (renders `app/Views/{dot.path}.php`), `redirect()`, `redirectBack()`.
- `core/Session.php` / `core/CSRF.php` — plain `$_SESSION` wrapper and token generation/`hash_equals` validation. All state-changing POST routes go through `CSRFCheckMiddleware`; `AuthMiddleware`/`GuestMiddleware` gate admin vs. guest routes.
- `bootstrap/autoload.php` registers a minimal PSR-4-ish autoloader mapping `Core\` → `core/` and `App\` → `app/` (in addition to Composer's autoloader for vendor packages).
- `helpers/functions.php` has global helpers loaded once in `bootstrap/start.php`: `base_url()`, `e()` (HTML-escape), `csrf_field()`, `old()`/`set_old_input()`/`clear_old_input()` (old-input flashing), `flash()`, `query_string()` (for building pagination/filter links), and `landing_page_theme_assets()` (inlines Tailwind config + CSS for the public landing page).

Models (`app/Models/*.php`) are static-method classes that talk to `Database::connection()` directly with raw SQL — no query builder, no ORM, no migrations framework. `app/Models/LstmBatchRun.php` (~866 lines) is the largest and centralizes almost all read/write logic for batches, runs, metrics, predictions, residuals, forecasts, pagination, and recap aggregation — check it first before adding new LSTM-related queries.

### The LSTM pipeline (the core domain workflow)

1. **Komoditas & Stok Historis** (`KomoditasController`, `StokHistorisController`) — CRUD for commodities and their daily/periodic stock records (`data_stok_historis`).
2. **Preprocessing** (`PreprocessingController` → `App\Models\DataPreprocessingLstm`) — windows raw stock history into `(sequence_length)`-step input/target pairs, normalizes values, splits rows into `Latih` (train) / `Uji` (test) sets, and writes them to `data_preprocessing_lstm` (this table is created on demand, not part of `schema.sql`).
3. **Training** (`LstmController::train()`) — validates hyperparameters (`sequence_length`, `train_ratio`, `epochs`, `batch_size`, `lstm_units`, `dropout_rate`, `optimizer`, `learning_rate`), creates a `lstm_batch_runs` row with status `queued`, then launches `database/train_lstm_batch.py <batch_id>` as a detached background process (`start /B ... > NUL 2>&1`, Windows-specific) and immediately redirects to the batch detail page — the PHP side never waits on or monitors the subprocess.
4. **`database/train_lstm_batch.py`** — for every distinct commodity found in `data_preprocessing_lstm`: creates/updates a `lstm_model_runs` row, builds a `Sequential([LSTM(units) → Dropout → Dense(32, relu) → Dense(1, linear)])` Keras model with EarlyStopping on `val_loss`, trains, evaluates on the `Uji` split (RMSE/MAE/MAPE), autoregressively forecasts 365 days ahead, saves the model to `storage/models/batch_{batch_id}_{commodity}.keras`, and persists metrics/predictions/residuals/forecasts back to their respective tables. Progress/status is polled by the PHP side by re-reading `lstm_batch_runs` / `lstm_model_runs` rows — there is no websocket/queue, just DB polling from the batch/run detail pages.
5. **Evaluation & export** (`LstmController` evaluation/export actions + `App\Services\LstmExportService` + `App\Services\ExportResponse`) — batch/run detail pages, CSV/Excel(`.xls`)/PDF (TCPDF) export for summaries, predictions, residuals, and forecasts.
6. **Public landing page** (`HomeController`) — read-only forecast browsing (filter/search/pagination) plus an interactive mascot widget with FAQ and Web Speech API text-to-speech; its inline Tailwind theme/config and CSS lives in `helpers/functions.php::landing_page_theme_assets()`.

### Database tables

Defined in `database/schema.sql`: `users`, `komoditas`, `data_stok_historis`, `lstm_batch_runs`, `lstm_model_runs`, `lstm_model_metrics`, `lstm_model_predictions`, `lstm_model_residuals`, `lstm_model_forecasts`. `data_preprocessing_lstm` is *not* in the schema file — it's created by `App\Models\DataPreprocessingLstm::ensureTable()` the first time preprocessing runs (mirrored by `LstmBatchRun::ensureTables()` being called defensively at the top of most `LstmController` actions).

### Views

`app/Views/pages/{module}/*.php` (plain PHP templates, no template engine) rendered via `Controller::view('pages.module.action')`; `app/Views/includes` holds shared partials (sidebar, nav, etc.). Frontend uses Tailwind via CDN, Chart.js for graphs, and vanilla JS in `public/js/`.

## Platform notes

- Developed and run on Windows (Laragon). The training-launch command in `LstmController::train()` uses `start /B` — a Windows-only invocation. Keep this in mind if adapting deployment or training-trigger logic for Linux.
- No `.env` loader is set up despite being listed as a future improvement in `README.md` — actual config resolution is `getenv()` with inline fallbacks in `config/app.php` / `config/database.php`.
