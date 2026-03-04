<p align="center">
  <img src="https://www.radius.co.id/wp-content/uploads/2025/12/logo-radius-background-putih-1284x800-1.webp" 
       alt="Radius Logo" 
       width="500">
</p>

# 🚗 Car Rental Radius

Aplikasi Web Sistem Peminjaman Mobil Operasional Internal  
Dibangun menggunakan **PHP Native & MySQL**

---

## 📌 Deskripsi Sistem

Sistem ini digunakan untuk mengelola peminjaman mobil operasional perusahaan secara terstruktur dengan sistem approval bertingkat.

---

# 🔄 Alur Sistem

1. **User / Staf Divisi** mengajukan peminjaman mobil.
2. Status awal: **Pending Leader Divisi**
3. **Leader Divisi** melakukan:
   - Approve → diteruskan ke Leader GA
   - Reject → selesai
   - Cancel → selesai
4. Jika di-approve Leader Divisi → masuk ke **Pending Leader GA**
5. **Leader GA** melakukan:
   - Approve → Selesai (Approved)
   - Reject → Selesai
   - Cancel → Selesai
6. Jika pengajuan ditolak →  
   User dapat memilih mobil lain dan mengajukan ulang.

---

# 👥 Role & Hak Akses

Sistem melakukan pengecekan dari tabel **users** dan **divisi** untuk menentukan role:

- Leader GA  
- Leader Divisi  
- Anggota GA  
- BOD  
- Admin  

---

## 🔐 Leader GA

- Melihat data pengajuan mobil
- Approve / Reject / Cancel
- Edit data pengajuan
- Melihat laporan
- Melihat jadwal mobil
- Melihat data user
- Melihat data admin
- Melihat data mobil
- Melihat data car_requests
- Tidak bisa melihat approval Leader GA lain

Dashboard:
- Jika login sebagai Leader GA → tampil pengajuan:
  - Leader GA
  - Leader Divisi
  - User Divisi

---

## 🔐 Leader Divisi

- Melihat data pengajuan mobil
- Approve / Reject / Cancel
- Edit data pengajuan
- Melihat laporan
- Melihat jadwal mobil
- Melihat data user
- Melihat data admin
- Melihat data mobil
- Melihat data car_requests

Dashboard:
- Jika login sebagai Leader Divisi → tampil pengajuan:
  - Pengajuan dirinya sendiri
  - User dalam divisinya

---

## 🔐 Anggota GA (Bang Jali)

- Hanya dapat melihat data
- Tidak dapat approve / reject / cancel

---

## 🔐 BOD

- Melihat pengajuan mobil
- Approve / Reject / Cancel

---

## 🔐 Admin

- Melihat pengajuan mobil
- Approve / Reject / Cancel
- CRUD Admin
- Manajemen Pengguna
- Manajemen Mobil
- Approval peminjaman
- Admin juga dapat meminjam mobil

---

# 🚘 Fitur Utama

✅ Login Multi Role  
✅ Approval Bertingkat (Leader Divisi → Leader GA)  
✅ Dashboard Dinamis Berdasarkan Role  
✅ Jadwal Mobil (Kalender + Opsi Harian)  
✅ Laporan (Filter Bulan / Minggu / Tahun)  
✅ Filter berdasarkan:
- Divisi
- Status
- Mobil
- User
- Jumlah penolakan divisi
- Mobil paling sering dipinjam
- Riwayat peminjaman per divisi  

✅ Cetak PDF & Excel  
✅ Notifikasi sistem  
✅ Komentar & alasan penolakan  
✅ Status mobil (Tersedia / Tidak tersedia / Service)  
✅ Update foto mobil  
✅ Profil user & admin (upload & update foto)  
✅ Responsive sidebar (hamburger)  
✅ Manajemen Admin & Pengguna  
✅ Username otomatis dari session  
✅ Ganti username via profil  
✅ Checkbox menjadi bullet (pilih salah satu)  
✅ Innova default status: Tidak tersedia  
✅ Fitur Pengembalian mobil (Selesai)  
✅ User bisa cancel pengajuan  
✅ Admin bisa ubah Approved → Canceled  
✅ Jika 2 user booking waktu sama:
   - Jika salah satu Approved
   - Maka yang lain otomatis Rejected  

---

# 📊 Laporan Sistem

- Filter berdasarkan Bulan, Minggu, Tahun
- Rekap peminjaman per divisi
- Statistik jumlah penolakan divisi
- Mobil paling sering dipinjam
- Riwayat peminjaman per user
- Perhitungan otomatis jumlah peminjaman per divisi  
  Contoh:
  Technical Support - 1x  
  Technical Support - 2x  

---

# 🛠 Teknologi

- PHP Native
- MySQL
- HTML
- CSS
- JavaScript
- Font Awesome

---

# 📂 Struktur Database Utama

- users
- divisi
- mobil
- car_requests
- jadwal

---

# 🚀 Pengembangan Selanjutnya

🔲 Filterisasi di halaman pengguna pada daftar pengajuan (on progress)

---

© 2026  
Created by **Arfa Muhammad Fadhillah**  
for **PT Radius Allkindo Electric** ❤️  
All Rights Reserved.
