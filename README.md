# Creator Content & Pipeline Tracker

Aplikasi web untuk mengelola ide konten → naskah → produksi → editing → ready → published,
lengkap dengan checklist produksi dan pencatatan metrik performa (Views, Likes, Comments, Shares).

**Stack:** Laravel 12 (Blade + Alpine.js + Tailwind via CDN) · MySQL / PostgreSQL / SQLite · PHP 8.2+

## Isi paket ini
Paket ini berisi **file aplikasi saja** (migration, model, controller, view, seeder, test, routes),
untuk ditumpuk di atas project Laravel 12 yang masih baru.

## Instalasi lokal

```bash
# 1. Buat project Laravel baru (tanpa starter kit)
composer create-project laravel/laravel creator-pipeline
cd creator-pipeline

# 2. Salin isi paket ini ke folder project, TIMPA file yang sudah ada
#    (routes/web.php, app/Models/User.php, database/seeders/DatabaseSeeder.php,
#     database/factories/UserFactory.php, migration users)

# 3. Hapus test bawaan (route "/" sekarang redirect ke /contents)
rm tests/Feature/ExampleTest.php

# 4. Konfigurasi environment
cp .env.example .env
php artisan key:generate
```

Edit `.env` — contoh MySQL:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=creator_pipeline
DB_USERNAME=root
DB_PASSWORD=
```
(Untuk SQLite cukup `DB_CONNECTION=sqlite` dan pastikan file `database/database.sqlite` ada.)

```bash
# 5. Migrasi + seeder
php artisan migrate --seed

# 6. Jalankan server
php artisan serve
```

Buka http://127.0.0.1:8000 — login demo: `creator@example.com` / `password`
(admin demo: `admin@example.com` / `password`).

> Tailwind & Alpine dimuat lewat CDN, jadi tidak perlu `npm install`/`npm run build`, tetapi butuh koneksi internet.

## Menjalankan test
```bash
php artisan test
```
Test mencakup TC-01 s/d TC-04 pada dokumen spesifikasi, plus aturan transisi status, otorisasi, checklist AJAX, dan filter.

## Alur kerja kreator ↔ editor
| Tahap | Pemegang | Aksi |
|-------|----------|------|
| idea → scripting → recording → editing | **Kreator** | Pindah tahap; "Kirim ke Editor" dari recording |
| editing | **Editor** | **Setujui** → `ready`, atau **Minta revisi** (catatan wajib) → kembali ke `recording` |
| ready → published | **Kreator** | Isi `published_url`, lalu publish |

- Selama di `editing`, kreator tidak bisa memindahkan status (menunggu editor).
- Editor hanya melihat konten berstatus `editing` + konten yang pernah ia review; tidak bisa membuat ide, mengubah, menghapus, atau mempublish.
- Setiap keputusan editor tersimpan di tabel `content_reviews` (riwayat tampil di halaman detail; catatan revisi tampil sebagai banner untuk kreator).
- Akun demo editor: `editor@example.com` / `password`.

## Aturan bisnis penting
- Konten baru selalu berstatus `idea`.
- Status hanya bisa berpindah **satu tahap**, dan siapa yang boleh memindahkan tergantung peran (lihat tabel di atas; admin boleh maju/mundur satu tahap di semua tahap).
- Pindah ke `published` **wajib** `published_url` berupa tautan http(s) valid (divalidasi di server). `published_date` diisi otomatis.
- Metrik hanya bisa dicatat untuk konten `published`.
- Hapus konten ⇒ checklist & metrik ikut terhapus (`ON DELETE CASCADE`).
- Channel yang masih punya konten tidak bisa dihapus.
- Pengguna hanya dapat melihat/mengubah konten miliknya; role `admin` dapat mengakses semua.

## Catatan implementasi vs spesifikasi
- Kolom `users.password_hash` dipakai sesuai spesifikasi; `User::getAuthPasswordName()` memberi tahu Laravel Auth untuk memakainya.
- Tabel `channels`, `content_checklists`, `content_metrics` hanya punya `created_at` (sesuai ERD).
- Role `editor` dipakai untuk review pada tahap editing (lihat bagian Alur kerja di atas).
- Arsitektur yang dipakai adalah monolith Blade, sehingga kontrak endpoint `/api/v1/...` (Opsi 3) tidak dibuat.
  Endpoint checklist (`POST`, `PATCH .../toggle`, `DELETE`) tetap merespons JSON untuk AJAX.

## Struktur singkat
```
app/Http/Controllers   Auth, Channel, Content, Checklist, Metric
app/Http/Requests      StoreContentRequest (validasi server-side)
app/Models             User, Channel, Content, ContentChecklist, ContentMetric
app/Policies           ContentPolicy, ChannelPolicy
database/migrations    5 tabel sesuai ERD (+ sessions & password_reset_tokens)
database/seeders       akun dummy, 2 channel, 3 konten contoh
resources/views        layouts, auth, channels, contents (board, form, detail)
tests/Feature          ContentPipelineTest
```

## Git workflow & standar koding
- Branch: `main` (stabil) dan `feature/<kode-tiket>-nama-fitur`, mis. `feature/CCP-02-auth-channel`.
- Commit (Conventional Commits): `feat:`, `fix:`, `db:`, `docs:` — mis. `db: add migration for content checklists and metrics`.
- Tabel jamak snake_case; PK `id`; FK `<singular_table>_id`; semua validasi bisnis di server.

## Peta tiket
| Tiket | Cakupan | File utama |
|-------|---------|-----------|
| CCP-01 | Skema DB, model, seeder | `database/migrations`, `app/Models`, `DatabaseSeeder` |
| CCP-02 | Auth & channel | `AuthController`, `ChannelController`, `views/auth`, `views/channels` |
| CCP-03 | CRUD konten & filter | `ContentController`, `StoreContentRequest`, `views/contents` |
| CCP-04 | Checklist (AJAX) | `ChecklistController`, `contents/show.blade.php` |
| CCP-05 | Validasi publish & metrik | `ContentController@transition`, `MetricController` |
