# 🧵 YoPrint Laravel CSV Upload Project

[![Laravel](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-%5E8.4-blue.svg)](https://php.net)
[![Tests](https://img.shields.io/badge/Tests-Pest-green.svg)](https://pestphp.com)
[![License](https://img.shields.io/badge/license-MIT-lightgrey.svg)](LICENSE)

A minimal but production-grade Laravel project demonstrating **CSV file uploads**, **background job processing**, **UPSERT logic**, and **clean architecture (Controller → Service → Repository → Job)**.

---

## 🚀 Features

- ✅ CSV Upload with Validation (`FormRequest`)
- ⚙️ Background Processing using Queues (Redis / Horizon)
- 🔁 Idempotent Uploads + UPSERT by `UNIQUE_KEY`
- 🧩 Clean Architecture (Repository + Service + Form Request)
- 🧱 MySQL for app runtime, SQLite for tests
- 🧪 Fully tested with PestPHP
- 🖥️ Real-time status updates via Alpine.js polling
- 🎨 Laravel Breeze for Auth + UI scaffold

---

## 🧰 Tech Stack

| Layer | Technology |
|-------|-------------|
| Framework | Laravel 11 .x |
| Language | PHP 8.2 + |
| Database | MySQL (App) / SQLite (Testing) |
| Queue | Redis + Horizon |
| Auth | Laravel Breeze |
| Frontend | Blade + Alpine.js |
| Testing | PestPHP |

---

## 🏗️ Project Setup

### 1️⃣ Clone Repository
```bash
git clone https://github.com/yourusername/yoprint-laravel.git
cd yoprint-laravel
````

---

### 2️⃣ Install Dependencies

```bash
composer install
npm install && npm run build
```

---

### 3️⃣ Environment Setup

Copy and configure environment files:

```bash
cp .env.example .env
cp .env.example .env.testing
```

Generate keys:

```bash
php artisan key:generate
php artisan key:generate --env=testing
```

---

### 4️⃣ Configure Databases

#### 🔹 Main App (MySQL)

Edit `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=yoprint
DB_USERNAME=root
DB_PASSWORD=
```

#### 🔹 Tests (SQLite)

Edit `.env.testing`:

```env
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
FILESYSTEM_DISK=public
```

Run migrations:

```bash
php artisan migrate
```

---

### 5️⃣ Install Laravel Breeze

```bash
php artisan breeze:install
npm run build
php artisan migrate
```

---

### 6️⃣ Queue Setup (Redis + Horizon)

Start Redis (e.g. via Homebrew or Docker):

```bash
brew services start redis
```

Run Horizon dashboard:

```bash
php artisan horizon
```

---

### 7️⃣ Serve Application

```bash
php artisan serve
```

Your app will be available at
👉 **[http://localhost:8000](http://localhost:8000)**

---

## ⚙️ Functional Overview

### 🧭 UploadController

* `index()` → Display upload UI
* `store()` → Validate file, save to storage, dispatch background job
* `list()` → Return latest uploads as JSON (via `UploadResource`)

### 🧾 StoreUploadRequest

Ensures file is valid:

```php
'file' => ['required', 'file', 'mimes:csv,txt', 'max:204800']
```

### 🧠 UploadService

Handles all business logic:

* Compute file checksum
* Save to storage (`storage/app/uploads/`)
* Call repository
* Dispatch `ProcessUploadCsv` job

### 🗄️ UploadRepository

Database layer:

* `findOrCreate()` → find existing upload or create new
* `latest()` → fetch recent uploads

### ⚡ ProcessUploadCsv Job

Runs asynchronously:

* Cleans non-UTF-8 characters
* Parses CSV
* UPSERTs products by `UNIQUE_KEY`

### 📦 RepositoryServiceProvider

Dependency injection binding:

```php
$this->app->bind(UploadRepositoryInterface::class, UploadRepository::class);
```

---

## 🧩 Project Structure

```
app/
 ├── Http/
 │   ├── Controllers/UploadController.php
 │   ├── Requests/StoreUploadRequest.php
 │   └── Resources/UploadResource.php
 ├── Interfaces/UploadRepositoryInterface.php
 ├── Repositories/UploadRepository.php
 ├── Services/UploadService.php
 ├── Jobs/ProcessUploadCsv.php
 ├── Providers/RepositoryServiceProvider.php
 └── Models/Upload.php
```

---

## 🧪 Testing (PestPHP)

All tests use **SQLite** in memory.

Run:

```bash
php artisan test
# or
./vendor/bin/pest
```

Example test:

```php
it('uploads csv successfully', function () {
    Bus::fake();
    $file = UploadedFile::fake()->create('test.csv', 5, 'text/csv');
    post(route('upload.store'), ['file' => $file])->assertRedirect('/');
    Bus::assertDispatched(ProcessUploadCsv::class);
});
```

---

## 🧱 Example Workflow

1. User uploads CSV
2. File saved → record created in `uploads` table
3. Job `ProcessUploadCsv` runs in background
4. Data parsed & upserted
5. UI auto-refreshes to show updated status

---

## 📂 CSV Format

| UNIQUE_KEY  | PRODUCT_TITLE | PRODUCT_DESCRIPTION | STYLE# | SANMAR_MAINFRAME_COLOR | SIZE | COLOR_NAME  | PIECE_PRICE |
| ----------- | ------------- | ------------------- | ------ | ---------------------- | ---- | ----------- | ----------- |
| G5000-RED-M | T-Shirt       | 100% Cotton         | G5000  | Red                    | M    | Classic Red | 5.50        |

---

## 🖥️ UI Overview

* Drag-and-Drop upload area
* Upload button
* Real-time table refresh

Example:

```
---------------------------------------------------------
| Time             | File Name         | Status          |
|-------------------------------------------------------|
| 2 mins ago       | import.csv        | Completed       |
| 5 mins ago       | prices_update.csv | Processing...   |
---------------------------------------------------------
```

---

## 🧑‍💻 Local Development Commands

| Action           | Command                            |
| ---------------- | ---------------------------------- |
| Run local server | `php artisan serve`                |
| Run queue worker | `php artisan queue:work`           |
| Run Horizon      | `php artisan horizon`              |
| Run tests        | `php artisan test`                 |
| Refresh DB       | `php artisan migrate:fresh --seed` |