<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Deploy production dengan Docker

Stack production terpisah dari `compose.yaml` lokal: Nginx non-root, PHP-FPM
8.5.12, MySQL 8.4 dan queue worker. Asset dibangun menggunakan Node 24 LTS;
Node, dependency development, `.env` dan data lokal tidak masuk image runtime.
Siapkan Docker Engine + Compose dan sekitar 4 GB RAM untuk batas container bawaan.
Sesuaikan kapasitas PHP-FPM dan batas memori bila kapasitas server berbeda.

### Pemasangan pertama

```bash
cp .env.production.example .env.production
chmod 600 .env.production
```

Edit `.env.production`: isi domain HTTPS pada `APP_URL`, password database acak
yang kuat pada `DB_PASSWORD`, serta konfigurasi email jika diperlukan. Jangan
memakai password contoh atau commit file ini. `MAIL_MAILER=log` tidak mengirim email.

Gunakan fungsi berikut pada terminal server agar tidak tertukar dengan database lokal:

```bash
dc() { docker compose --env-file .env.production -f compose.production.yaml "$@"; }
dc build --pull app web
dc run --rm --no-deps --entrypoint php app artisan key:generate --show --no-interaction
```

Simpan hasil perintah terakhir sebagai `APP_KEY` di `.env.production`.
Jika memindahkan toko yang sudah berisi data, gunakan APP_KEY toko tersebut,
bukan membuat key baru. Simpan key yang sama pada setiap redeploy.

```bash
dc up -d --wait mysql
dc run --rm app php artisan migrate --force --no-interaction
dc up -d --wait
dc exec app php artisan app:create-admin
dc ps
```

Perintah pembuatan admin sengaja interaktif; password tidak ditulis sebagai
argumen shell. Migrasi tidak berjalan otomatis saat container restart dan
data demo tidak di-seed. Jika memindahkan toko lama, pulihkan backup database
dan file upload sebelum membuka akses publik; jangan menjalankan database kosong
sebagai pengganti data toko.

### HTTPS dan reverse proxy

Backend hanya dipublish di loopback port `HTTP_PORT` (default 8080). Pasang TLS
di reverse proxy host dan teruskan trafik domain ke port tersebut. Contoh
bagian `location /` pada server HTTPS Nginx host:

```nginx
location / {
    proxy_pass http://127.0.0.1:8080;
    proxy_set_header Host $host;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_set_header X-Forwarded-For $remote_addr;
    client_max_body_size 32m;
    proxy_read_timeout 70s;
}
```

Konfigurasi sertifikat dan redirect HTTP ke HTTPS tetap harus dibuat pada host.
`TRUSTED_PROXIES=REMOTE_ADDR` hanya sesuai bila backend tidak dapat diakses publik
dan proxy mengganti header tersebut, bukan meneruskan nilai buatan pengunjung.
Jika memakai Cloudflare atau proxy berantai, atur trusted IP/real-IP pada proxy
terluar secara spesifik terlebih dahulu. Jangan publish port MySQL atau PHP-FPM.

PHP menonaktifkan session upload progress sejak startup untuk mengikuti advisory
PHP2Shell. `.user.ini` lokal tidak digunakan, Xdebug tidak dipasang, dan PHP
menggunakan batas memori. Build ulang dengan `--pull` untuk memperoleh pembaruan
image; naikkan tag PHP/Node pada `Dockerfile` saat patch keamanan baru tersedia.

### Update dan backup

Simpan backup **database, seluruh volume `app_storage`, dan `.env.production`**
di luar server serta uji pemulihannya. Volume Docker bukan pengganti backup.
Contoh backup database tanpa password pada argumen host:

```bash
umask 077
dc exec -T mysql sh -c 'MYSQL_PWD="$MYSQL_PASSWORD" exec mysqldump --user="$MYSQL_USER" --single-transaction --no-tablespaces "$MYSQL_DATABASE"' > database-backup.sql
```

Jangan commit dump database. Jalankan prosedur update setelah backup terverifikasi:

```bash
dc build --pull app web
dc exec app php artisan down --no-interaction
dc stop worker
dc run --rm app php artisan migrate --force --no-interaction
dc up -d --force-recreate
dc exec app php artisan up --no-interaction
dc ps
```

Jika migrasi gagal, jangan lanjutkan ke langkah berikutnya; periksa log dan
rencana pemulihan. Healthcheck `/up` dapat gagal selama maintenance; jalankan
`artisan up` dan tunggu status healthy sebelum membuka trafik.
Perubahan environment memerlukan recreate container, bukan hanya `restart`.
Jangan gunakan `docker compose down -v`: opsi tersebut menghapus volume data.
Scheduler belum dibutuhkan karena belum ada scheduled task di aplikasi.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
