# Dokumentasi Teknis — Website & POS Nadi's Fotocopy

*Untuk tim/programmer pelaksana — Stack: Laravel 13 + Livewire 4 + Flux UI*

> **Rev 2.** Perubahan dari revisi sebelumnya:
>
> - Stack diselaraskan dengan isi repo yang sebenarnya (repo ini `laravel/livewire-starter-kit`,
>   bukan Laravel polos). Versi Livewire dikoreksi dari 3 → **4**.
> - **Filament dibatalkan.** Panel Admin dibangun dengan Flux + Livewire (alasan di §1).
> - **Scaffolding Teams dari starter kit dibuang** (lihat Tahap 0 di §8).
> - Skema data dilengkapi: diskon, jejak void, pencatatan pergerakan stok, pengaturan toko.
> - Aturan stok saat transaksi dibatalkan (void) sekarang didefinisikan (§6.4).
> - Cara cetak struk dan cara kirim struk WhatsApp ditetapkan secara eksplisit.
>
> Revisi sebelumnya (Rev 1) mengganti ide toko online (keranjang, checkout, payment gateway)
> menjadi dua bagian terpisah: website publik sederhana dan sistem kasir/POS. Keputusan itu tetap berlaku.

## 1. Ringkasan Proyek

Nadi's Fotocopy membutuhkan dua bagian sistem:

1. **Website publik sederhana** — company profile dan katalog produk/layanan, supaya
   pelanggan bisa cek harga dan status stok dari HP, lalu menghubungi toko lewat
   WhatsApp kalau mau bertanya atau memesan. Tidak ada keranjang atau checkout online.
2. **Sistem Kasir (POS)** — dipakai staf di toko untuk mencatat transaksi langsung.
   Pembayaran dipilih manual oleh kasir: **Cash, QRIS, atau Debit** — tanpa integrasi
   payment gateway atau verifikasi otomatis ke bank/e-wallet.

### 1.1 Stack Teknis

Versi di bawah ini **sudah terpasang** di repo dan terkunci di `composer.json` / `package.json`.
Jangan mengganti versi major tanpa persetujuan pemilik proyek.

| Komponen | Versi | Catatan |
|---|---|---|
| PHP | ^8.3 | |
| Laravel | ^13.17 | |
| Livewire | **^4.1** | Bukan Livewire 3. Livewire 4 memakai pola *single-file component* — contoh yang sudah ada di repo: `resources/views/pages/settings/⚡profile.blade.php` |
| Flux UI | ^2.13 (`livewire/flux`) | Pustaka komponen UI resmi Livewire. Dipakai untuk POS **dan** Panel Admin |
| Tailwind CSS | ^4.0 | Lewat `@tailwindcss/vite` |
| Vite | ^8.0 | |
| Laravel Fortify | ^1.37 | Autentikasi: login, 2FA, dan passkey sudah tersedia dari starter kit |
| Database | **MySQL / MariaDB** | `.env` bawaan masih `sqlite` — wajib diubah di Tahap 0 |
| Testing & QA | Pest 5, Larastan (PHPStan), Pint | Dijalankan sekaligus lewat `composer test` |

### 1.2 Kenapa tanpa Filament

Rencana awal memakai Filament untuk Panel Admin. Rencana itu dibatalkan karena dua alasan:

1. **Tidak kompatibel.** Filament v4 mengunci `livewire/livewire ^3.5`, sementara proyek ini
   memakai Livewire 4. Hanya Filament v5 yang mendukung Livewire 4, dan menambahkannya berarti
   satu dependency besar plus build CSS terpisah yang harus dirawat.
2. **Tidak sepadan.** Panel Admin di proyek ini hanya mengelola dua entitas utama (kategori dan
   produk) ditambah halaman laporan. CRUD sebanyak itu lebih ringan dibuat langsung dengan Flux,
   dan tampilannya jadi konsisten dengan layar POS yang memang harus dibuat custom.

Seluruh bagian aplikasi — website publik, POS, dan Panel Admin — dibangun dengan Livewire 4 + Flux.

## 2. Tujuan Produk

- Pelanggan bisa cek produk, harga, dan status stok dari HP sebelum datang atau chat WA.
- Transaksi di toko lebih cepat dan tercatat rapi lewat POS, tidak manual di nota/buku.
- Stok otomatis berkurang saat transaksi selesai, mengurangi selisih stok.
- Pemilik bisa melihat laporan penjualan harian tanpa rekap manual.

## 3. Target Pengguna & Role

| Role | Kebutuhan |
|---|---|
| Pelanggan (publik, tanpa akun) | Lihat katalog & status stok, hubungi toko via WhatsApp |
| Owner (merangkap kasir & admin) | Input transaksi di POS, kelola produk & stok, lihat laporan |

Di versi awal hanya ada **satu jenis pengguna terautentikasi: Owner**. Toko dijaga langsung
oleh pemilik, jadi belum perlu akun kasir terpisah.

Meski begitu, kolom `role` tetap **ditambahkan lewat migration** ke tabel `users`
(nilai `owner` / `kasir`, default `owner`). Kolom ini dipakai sebagai gerbang otorisasi untuk
dua hal saja: **void transaksi** dan **akses laporan penjualan**. Dengan begitu aturan di §7.1
tetap bisa ditegakkan sejak awal, dan penambahan staf nanti tidak perlu mengubah struktur.

> Catatan: starter kit bawaan punya sistem role sendiri berbasis Team (`TeamRole`,
> `TeamPermission`). Sistem itu **dibuang** di Tahap 0 — jangan dipakai.

## 4. Lingkup Fitur

### 4.1 Website Publik (Sederhana)

- Beranda: profil toko, jam operasional, lokasi
- Katalog produk/layanan: nama, harga, status stok
- Tombol/link WhatsApp pada tiap produk untuk tanya stok atau pesan
- Tanpa akun pelanggan, tanpa keranjang, tanpa pembayaran online

Aturan tampilan katalog:

- Status stok hanya ditampilkan sebagai **"Tersedia" / "Habis"**. **Jangan pernah menampilkan
  angka stok** di halaman publik.
- Produk dengan `type: jasa` (fotocopy, print, jilid) selalu tampil "Tersedia" — jasa tidak
  punya stok.
- Produk dengan `is_active = false` tidak muncul di katalog.
- Nama toko, jam operasional, alamat, dan nomor WhatsApp dibaca dari tabel `Setting` — **jangan
  di-hardcode** di Blade, supaya pemilik bisa mengubahnya sendiri lewat Panel Admin.

### 4.2 POS (Kasir)

- Layar kasir: pilih produk/layanan, atur jumlah, sistem hitung total otomatis
- Diskon manual per transaksi (opsional) — dicatat sebagai diskon pada transaksi, **bukan**
  dengan mengubah harga produk. Bisa berupa nominal (Rp) atau persen
- Pilih metode pembayaran: **Cash / QRIS / Debit** (pilihan saja, dicatat manual, tanpa proses
  verifikasi otomatis ke bank/e-wallet)
- Untuk Cash: input uang diterima → sistem hitung kembalian otomatis
- Void/pembatalan transaksi (tetap tercatat di riwayat, tidak dihapus — lihat §6.4)
- Riwayat transaksi harian

**Struk — cetak.** Struk dicetak lewat **fitur print browser** (`window.print()`) dengan
halaman struk khusus berukuran 58mm/80mm (CSS `@media print`). Printer thermal dipasang sebagai
printer biasa di sistem operasi komputer kasir. Pendekatan ini dipilih karena tidak butuh
software tambahan di komputer kasir.

> Cetak ESC/POS langsung (lewat bridge lokal seperti QZ Tray) **tidak dikerjakan di fase ini**.
> Bisa jadi peningkatan nanti kalau hasil print browser dirasa kurang rapi atau terlalu lambat.

**Struk — WhatsApp.** Struk WhatsApp dikirim sebagai **teks biasa lewat link `wa.me`** yang
dibuka manual oleh kasir. Kasir input nomor WA pelanggan, sistem menyiapkan link berisi ringkasan
transaksi, kasir klik dan tekan kirim di WhatsApp.

> Ini **bukan** pengiriman otomatis, dan **tidak bisa mengirim gambar** — link `wa.me` hanya
> mendukung teks. Integrasi WhatsApp Business API (terkirim otomatis tanpa kasir membuka WA)
> bisa jadi peningkatan di fase berikutnya kalau dibutuhkan.

### 4.3 Panel Admin

- Kelola kategori (nama, urutan)
- Kelola produk (harga, stok, satuan, jenis, aktif/nonaktif)
- Penyesuaian stok manual — **wajib disertai alasan**, tercatat sebagai `StockMovement` (§5)
- Kelola pengaturan toko (nama, jam operasional, alamat, nomor WA, header/footer struk)
- Laporan penjualan: harian/mingguan/bulanan, per metode pembayaran, produk terlaris

### 4.4 Di Luar Cakupan (tidak dikerjakan kecuali diminta ulang)

- Payment gateway atau verifikasi pembayaran otomatis
- Keranjang & checkout online untuk pelanggan
- Akun/login untuk pelanggan
- Pengiriman/ongkir online
- **Harga bertingkat/grosir** (mis. >100 lembar lebih murah) — harga bersifat flat per satuan
- **Cetak ESC/POS langsung** lewat bridge lokal
- **Mode offline** — aplikasi butuh koneksi ke server saat dipakai
- **Shift kasir / tutup kasir** (buka-tutup laci, setoran per shift)
- WhatsApp Business API

## 5. Skema Data (Entitas Utama)

| Entitas | Field Penting | Keterangan |
|---|---|---|
| **User** | name, email, password, **role** | role: `owner` / `kasir`, default `owner`. Dipakai untuk otorisasi void & laporan |
| **Setting** | key, value | Baru. Nama toko, jam operasional, alamat, nomor WA, header/footer struk |
| **Category** | name, slug, description, sort_order | |
| **Product** | name, slug, category_id, price, stock, **unit**, type, **track_stock**, is_active | `unit`: lembar / pcs / rim. `type`: produk / jasa. `track_stock`: lihat aturan di bawah |
| **Transaction** | transaction_number, **user_id**, payment_method, **subtotal**, **discount_type**, **discount_value**, **discount_amount**, total, paid_amount, change_amount, status, customer_phone, **voided_at**, **voided_by**, **void_reason** | payment_method: cash / qris / debit. status: selesai / dibatalkan |
| **TransactionItem** | transaction_id, product_id, product_name, price, qty, subtotal | Snapshot harga saat transaksi |
| **StockMovement** | product_id, type, qty_change, reference_type, reference_id, user_id, note | Baru. Jejak setiap perubahan stok |

### 5.1 Aturan `track_stock`

- Produk fisik (`type: produk`) → `track_stock = true`. Stok dipotong saat transaksi.
- Jasa (`type: jasa`) → `track_stock = false`. Stok **tidak** dipotong, dan di katalog publik
  selalu tampil "Tersedia".

Tanpa aturan ini, semua jasa fotocopy akan tampil "Habis" di website karena stoknya 0.

### 5.2 Aturan perhitungan Transaction

```
subtotal        = jumlah seluruh TransactionItem.subtotal
discount_amount = nilai diskon hasil hitung (dari discount_type + discount_value)
total           = subtotal - discount_amount
change_amount   = paid_amount - total     (hanya untuk cash)
```

`discount_type`: `nominal` / `persen` / `null` (tanpa diskon). Bila `persen`, `discount_value`
menyimpan angka persennya dan `discount_amount` menyimpan hasil rupiahnya — keduanya disimpan
supaya laporan tidak perlu menghitung ulang.

### 5.3 Aturan `StockMovement`

`type` berisi salah satu dari: `penjualan`, `pembatalan`, `penyesuaian`, `stok_masuk`.
`qty_change` bernilai negatif untuk pengurangan, positif untuk penambahan.
`reference_type` / `reference_id` menunjuk ke sumbernya (mis. `Transaction` + id-nya).

**Setiap** perubahan angka stok wajib melahirkan satu baris `StockMovement`. Tanpa ini,
selisih stok tidak bisa ditelusuri asalnya.

### 5.4 Catatan tipe data

- **Semua nilai uang disimpan sebagai integer** (Rupiah, tanpa desimal) — gunakan
  `unsignedBigInteger`. **Jangan** pakai `float` atau `decimal`; pembulatan float adalah sumber
  selisih kas yang klasik. Format Rupiah dilakukan saat menampilkan saja.
- Format `transaction_number`: **`TRX-YYYYMMDD-NNNN`**, urut per hari (reset tiap hari).
  Nomor digenerate **di dalam DB transaction dengan lock** agar dua transaksi bersamaan tidak
  mendapat nomor yang sama. Beri unique index pada kolomnya.
- `customer_phone` opsional, diisi hanya kalau struk dikirim via WA.

## 6. Alur Sistem

### 6.1 Alur Pelanggan (Website)

1. Buka website, lihat katalog produk/layanan & status stok.
2. Kalau tertarik, klik tombol WhatsApp pada produk untuk tanya atau pesan.
3. Transaksi sebenarnya terjadi langsung di toko lewat kasir (POS).

### 6.2 Alur Transaksi POS

1. Owner membuka layar kasir, pilih produk/layanan yang dibeli pelanggan, atur jumlah.
2. Sistem menghitung total (termasuk diskon manual bila ada).
3. Owner memilih metode pembayaran:
   - **Cash**: input uang diterima, sistem tampilkan kembalian.
   - **QRIS / Debit**: owner menandai "sudah dibayar" setelah memastikan pembayaran berhasil
     langsung di mesin EDC/aplikasi QRIS (tanpa integrasi otomatis ke sistem).
4. Transaksi tersimpan, stok produk berkurang otomatis dan tercatat di `StockMovement`
   bertipe `penjualan`. Seluruh langkah ini berjalan dalam satu DB transaction.
5. Owner memilih cara memberikan struk ke pelanggan:
   - **Cetak** — buka halaman struk, print ke printer thermal lewat dialog print browser, atau
   - **Kirim ke WhatsApp** — input nomor WA pelanggan, sistem menyiapkan link `wa.me` berisi
     ringkasan struk dalam bentuk teks; owner klik link tersebut dan menekan kirim di WhatsApp.

### 6.3 Alur Admin

1. Login ke Panel Admin.
2. Kelola produk, kategori, stok, dan pengaturan toko.
3. Tinjau laporan penjualan.

### 6.4 Alur Void (Pembatalan Transaksi)

1. Owner membuka riwayat transaksi dan memilih transaksi yang akan dibatalkan.
2. Owner **wajib mengisi alasan pembatalan**. Aksi ini hanya boleh dilakukan user dengan
   role `owner`.
3. Sistem mengubah `status` menjadi `dibatalkan` dan mengisi `voided_at`, `voided_by`,
   `void_reason`. **Baris transaksi tidak pernah dihapus.**
4. **Stok dikembalikan** untuk setiap item yang `track_stock = true`, dan setiap pengembalian
   dicatat sebagai `StockMovement` bertipe `pembatalan`.
5. Transaksi berstatus `dibatalkan` **tidak dihitung** dalam laporan penjualan, tapi tetap
   muncul di riwayat.

Seluruh langkah 3–4 berjalan dalam satu DB transaction. Transaksi yang sudah dibatalkan tidak
bisa dibatalkan ulang.

## 7. Rules untuk Programmer Pelaksana

Bagian ini berisi hal yang **WAJIB** dilakukan dan yang **TIDAK BOLEH** dilakukan
selama pengembangan, supaya hasil akhir konsisten dengan dokumen ini dan aman
digunakan.

### 7.1 Wajib Dilakukan

**Konfigurasi (kerjakan lebih dulu, di Tahap 0)**

- Set `APP_TIMEZONE=Asia/Jakarta` dan `APP_LOCALE=id`. Tanpa ini Laravel memakai UTC dan
  **laporan penjualan harian akan salah potong 7 jam**.
- Ubah `DB_CONNECTION` ke `mysql` di `.env` dan `.env.example`.
- **Tutup registrasi publik** dari Fortify. Akun dibuat lewat seeder atau artisan command,
  bukan lewat halaman daftar.

**Data & transaksi**

- Setiap transaksi mengurangi stok produk secara atomic — potong stok dan simpan transaksi
  dalam **satu DB transaction**, dan **tolak transaksi bila stok tidak cukup**.
- Semua perubahan stok lewat `StockMovement`, termasuk penyesuaian manual dari Panel Admin.
- Nomor transaksi unik dan berurutan (format & cara generate di §5.4).
- Void tetap tercatat di riwayat (bukan dihapus), wajib beralasan, dan butuh role `owner`.
- Perhitungan total, diskon, dan kembalian dihitung **di sisi server**, bukan hanya di tampilan.
- Riwayat transaksi tersimpan permanen, termasuk transaksi yang dibatalkan/void.
- Harga di `TransactionItem` adalah snapshot saat transaksi, bukan referensi harga produk yang
  bisa berubah kemudian.
- Semua nilai uang disimpan sebagai integer (§5.4).
- Data stok yang dipakai di website publik dan di POS berasal dari tabel `Product` yang sama,
  supaya info stok di website selalu akurat.
- Gunakan migration Laravel untuk setiap perubahan struktur database — tidak mengubah skema
  langsung di database produksi.

**Keamanan & operasional**

- Layar POS dan seluruh Panel Admin berada di belakang middleware `auth`.
- Akses ke fitur admin (kelola produk, laporan, void) dibatasi lewat otorisasi berbasis role
  di server, bukan hanya disembunyikan di tampilan.
- **Backup database terjadwal** harus disiapkan sebelum sistem dipakai produksi — klaim
  "riwayat tersimpan permanen" tidak ada artinya tanpa backup.
- Jalankan `composer test` (Pint + PHPStan + Pest) dan pastikan hijau **sebelum serah terima
  tiap tahap**.

### 7.2 Tidak Boleh Dilakukan

- Tidak membuat transaksi tanpa tercatat resmi sebagai `Transaction` — semua penjualan harus
  lewat sistem, tidak ada "transaksi bayangan".
- Tidak menghapus transaksi secara permanen dari database walau statusnya dibatalkan/void.
- Tidak mengubah harga produk langsung dari layar kasir tanpa pencatatan — potongan harga
  harus tercatat sebagai diskon pada transaksi.
- Tidak mengubah kolom `stock` dengan `update` langsung tanpa membuat `StockMovement`.
- Tidak mengandalkan validasi total/diskon/kembalian hanya di sisi tampilan (frontend) — harus
  dihitung ulang dan divalidasi di server.
- Tidak menambahkan integrasi payment gateway atau verifikasi pembayaran otomatis kecuali
  diminta ulang secara tertulis oleh pemilik proyek.
- Tidak mengekspos data laporan penjualan, angka stok, atau margin lewat halaman publik.
- **Tidak memasang Filament v4** — mengunci `livewire/livewire ^3.5` dan akan bentrok dengan
  Livewire 4 yang dipakai proyek ini.
- Tidak menonaktifkan validasi atau proteksi keamanan bawaan Laravel (CSRF, mass assignment
  protection, dll.) demi mempercepat pengembangan.

## 8. Tahapan Pengerjaan

Urutan ini berbeda dari Rev 1: POS didahulukan sebelum website publik, karena nilai bisnis
terbesar ada di POS, dan katalog publik baru berguna setelah master produk terisi.

### Tahap 0 — Pembersihan Starter Kit

Repo ini berasal dari `laravel/livewire-starter-kit` versi **Teams (multi-tenancy)**. Untuk
toko tunggal, scaffolding itu murni beban: semua route ter-prefix `{current_team}` dan setiap
query harus sadar team. Buang seluruhnya.

Yang dihapus:

- Model `Team`, `Membership`, `TeamInvitation`
- `app/Concerns/HasTeams.php`, `app/Concerns/GeneratesUniqueTeamSlugs.php`
- `app/Enums/TeamRole.php`, `app/Enums/TeamPermission.php`
- `app/Data/TeamPermissions.php`, `app/Data/UserTeam.php`
- `app/Policies/TeamPolicy.php`, `app/Actions/Teams/`, `app/Notifications/Teams/`
- `app/Rules/TeamName.php`, `app/Rules/UniqueTeamInvitation.php`
- Middleware `EnsureTeamMembership`, `SetTeamUrlDefaults`, dan
  `app/Http/Responses/Concerns/RedirectsToCurrentTeam.php`
- `resources/views/pages/teams/` dan komponen `⚡team-switcher`, `⚡create-team-modal`,
  `team-invitation-alert`
- Route prefix `{current_team}` di `routes/web.php` dan route teams di `routes/settings.php`
- Migration teams (`2026_01_27_000001_create_teams_table.php`,
  `2026_01_27_000002_add_current_team_id_to_users_table.php`)
- `tests/Feature/Teams/`

> **Perkiraan effort:** sekitar **57 file** di `app/`, `resources/`, `routes/`, `database/`,
> dan `tests/` menyebut "team". Ini bukan pekerjaan lima menit — sediakan waktu dan jalankan
> `composer test` setelahnya.

Sekaligus di tahap ini: ubah `.env` ke MySQL, set timezone & locale, tutup registrasi publik,
dan tambahkan kolom `role` ke tabel `users`.

**Selesai bila:** `composer test` hijau, tidak ada lagi referensi `team` di `app/` dan
`routes/`, login berhasil dan mendarat di dashboard tanpa prefix team, `php artisan migrate:fresh`
jalan bersih di MySQL.

### Tahap 1 — Master Data & Panel Admin

Migration + model + seeder untuk `Category`, `Product`, `Setting`, `StockMovement`.
CRUD Panel Admin dengan Flux, termasuk penyesuaian stok beralasan dan halaman pengaturan toko.

**Selesai bila:** owner bisa menambah/mengubah/menonaktifkan kategori & produk dari browser,
penyesuaian stok menghasilkan baris `StockMovement`, pengaturan toko tersimpan dan terbaca.

### Tahap 2 — POS Inti

Layar kasir, diskon, metode pembayaran, kembalian, potong stok, halaman struk (print + link WA),
riwayat transaksi harian, dan void sesuai §6.4.

**Selesai bila:** satu transaksi cash lengkap dari pilih produk sampai struk tercetak berhasil,
stok berkurang sesuai dan tercatat di `StockMovement`, transaksi dengan stok tidak cukup ditolak,
void mengembalikan stok dan transaksinya tetap ada di riwayat.

### Tahap 3 — Website Publik

Beranda (profil toko dari `Setting`) dan katalog produk/layanan dengan tombol WhatsApp.

**Selesai bila:** katalog tampil rapi di layar HP, jasa tampil "Tersedia", produk habis tampil
"Habis" tanpa angka, tombol WA membuka chat dengan pesan yang sudah terisi nama produk.

### Tahap 4 — Laporan

Laporan per periode (harian/mingguan/bulanan), per metode pembayaran, dan produk terlaris.

**Selesai bila:** total laporan harian cocok dengan penjumlahan manual riwayat hari itu,
transaksi void tidak ikut terhitung, batas hari mengikuti Asia/Jakarta.

Setiap tahap diserahterimakan dan diuji sebelum lanjut ke tahap berikutnya.

## 9. Keputusan yang Masih Terbuka

Hal-hal berikut **sengaja belum diputuskan**. Jangan ditebak sendiri — tanyakan ke pemilik
proyek saat tahap terkait mulai dikerjakan.

| Topik | Yang perlu diputuskan |
|---|---|
| **Hosting** | VPS online atau server lokal di toko? Berpengaruh besar: kalau online, POS tidak bisa dipakai saat internet mati. Kalau lokal, website publik butuh pengaturan terpisah |
| **Cetak ESC/POS** | Apakah print lewat browser cukup rapi & cepat, atau perlu bridge lokal (QZ Tray) di fase berikutnya |
| **Harga bertingkat** | Apakah toko benar-benar memakai harga grosir per jumlah lembar. Saat ini diasumsikan harga flat per satuan |
| **Backup** | Media dan frekuensi backup database (harian ke storage lokal? cloud?) |
