# Troubleshooting HTTP 500 Error - e-library.akti.ac.id

## 🚨 Langkah-langkah Mengatasi Error 500

### 1. Langkah Pertama - Debug
1. Upload file `debug_cyberpanel.php` ke root directory
2. Akses: `https://e-library.akti.ac.id/debug_cyberpanel.php`
3. Periksa hasil debug untuk mengetahui penyebab error

### 2. Ganti .htaccess dengan Versi Minimal
Jika error masih terjadi, ganti file `.htaccess` dengan `.htaccess.minimal`:

```bash
# Rename current .htaccess
mv .htaccess .htaccess.backup

# Use minimal version
mv .htaccess.minimal .htaccess
```

### 3. Periksa File Permissions
Pastikan permission folder sudah benar:

```bash
# Set permission untuk folder
chmod 755 files/
chmod 755 images/
chmod 755 logs/
chmod 755 template/

# Set permission untuk file
chmod 644 *.php
chmod 644 .htaccess
chmod 644 .env
```

### 4. Periksa Database Connection
Pastikan database sudah benar:
- Database: `elib_slims_akti`
- User: `elib_slims_akti`
- Password: `Akti@123!!`
- Host: `localhost`

### 5. Periksa PHP Error Log
Cek file log error di:
- `/logs/php_errors.log`
- CyberPanel Error Logs
- Apache Error Logs

## 🔧 Solusi Berdasarkan Error

### Error: "mod_rewrite not enabled"
**Solusi:**
1. Aktifkan mod_rewrite di CyberPanel
2. Atau gunakan `.htaccess.minimal`

### Error: "Database connection failed"
**Solusi:**
1. Periksa kredensial database di `.env`
2. Pastikan database sudah dibuat di phpMyAdmin
3. Pastikan user database memiliki permission yang benar

### Error: "Permission denied"
**Solusi:**
1. Set permission folder yang diperlukan
2. Pastikan web server bisa menulis ke folder files dan images

### Error: "File not found"
**Solusi:**
1. Pastikan semua file sudah terupload
2. Periksa struktur folder
3. Pastikan file `.env` ada

## 📋 Checklist Cepat

- [ ] File `debug_cyberpanel.php` diupload dan diakses
- [ ] File `.htaccess` diganti dengan versi minimal
- [ ] Permission folder sudah diset dengan benar
- [ ] Database connection sudah ditest
- [ ] PHP error log sudah dicek
- [ ] File `.env` sudah ada dan benar
- [ ] Semua file SLiMS sudah terupload

## 🆘 Jika Masih Error

1. **Cek Error Log CyberPanel:**
   - Login ke CyberPanel
   - Go to "Error Logs"
   - Cari error terkait e-library.akti.ac.id

2. **Cek Apache Error Log:**
   - Cari file error log Apache
   - Cari error terkait domain Anda

3. **Test dengan File Sederhana:**
   - Buat file `test.php` dengan isi: `<?php phpinfo(); ?>`
   - Akses file tersebut
   - Jika error, masalah ada di server

4. **Kontak Support CyberPanel:**
   - Sertakan error message
   - Sertakan hasil debug
   - Sertakan konfigurasi PHP

## 📞 Informasi untuk Support

**Domain:** e-library.akti.ac.id
**Error:** HTTP 500
**Application:** SLiMS 9
**Database:** elib_slims_akti
**PHP Version:** (cek dengan debug script)

**File Debug:**
- `debug_cyberpanel.php` - untuk debug sistem
- `error_handler.php` - untuk error handling
- `.htaccess.minimal` - versi minimal .htaccess

---
**Status:** Debugging in progress
**Last Updated:** $(date)
