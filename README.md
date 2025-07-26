# CESA Project

## Overview

CESA adalah aplikasi web berbasis Laravel yang sudah terintegrasi dengan Filament, Horizon, Octane, dan Telescope. Project ini mendukung manajemen user, autentikasi, job queue, cache, dan penyimpanan file (local, public, S3).

## Features

- Manajemen user (CRUD) dengan Filament
- Autentikasi dan otorisasi
- Job queue dengan Redis & Horizon
- Cache dengan Redis
- Penyimpanan file: local, public, dan S3
- Mode maintenance fleksibel (file, redis, database)
- Monitoring aplikasi dengan Telescope
- High performance dengan Octane
- Migrasi dan seeder database
- Unit dan feature testing

## Requirements

- PHP >= 8.2
- Composer
- Node.js & npm
- MySQL/MariaDB (atau database lain yang didukung Laravel)
- Redis (untuk session, queue, cache)
- (Opsional) Amazon S3 jika ingin menggunakan cloud storage

## Installation

1. Clone repository:
   ```bash
   git clone https://github.com/CompleteLabs/web-cesa.git
   cd web-cesa
   ```

2. Install dependencies:
   ```bash
   composer install
   npm install
   ```

3. Build asset frontend:
   ```bash
   npm run build
   ```

4. Copy file environment:
   ```bash
   cp .env.example .env
   ```

5. Generate APP_KEY:
   ```bash
   php artisan key:generate
   ```

6. Jalankan migrasi database:
   ```bash
   php artisan migrate
   ```

7. Jalankan server lokal:
   ```bash
   php artisan serve
   ```
   atau gunakan mode development paralel:
   ```bash
   composer dev
   ```

## Configuration

Edit file `.env` untuk menyesuaikan konfigurasi aplikasi.

### Variabel Penting

- `APP_NAME` — Nama aplikasi
- `APP_URL` — URL aplikasi
- `APP_MAINTENANCE_DRIVER` — Driver maintenance (default: file)
- `APP_MAINTENANCE_STORE` — Lokasi penyimpanan status maintenance (`file`, `redis`, `database`)
- `FILESYSTEM_DISK` — Disk default untuk penyimpanan file (`local`, `public`, `s3`)
- `DB_CONNECTION`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` — Konfigurasi database
- `SESSION_DRIVER` — Driver session (misal: redis)
- `QUEUE_CONNECTION` — Driver queue (misal: redis)
- `CACHE_STORE` — Driver cache (misal: redis)

#### Contoh Pengaturan Storage

- `FILESYSTEM_DISK=public`  
  File yang di-upload akan disimpan di folder `storage/app/public` dan dapat diakses melalui URL `/storage`.

Pilihan lain:
- `local` — Penyimpanan privat di `storage/app/private`
- `s3` — Penyimpanan di Amazon S3 (butuh konfigurasi AWS)

#### Maintenance Mode

- `APP_MAINTENANCE_STORE` dapat diatur ke `file`, `redis`, atau `database` untuk menentukan lokasi penyimpanan status maintenance.

## License

Project ini menggunakan lisensi MIT.
