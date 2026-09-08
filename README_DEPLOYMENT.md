# SLiMS - Siap untuk Deployment ke CyberPanel

## ✅ Konfigurasi Database
- **Database**: elib_slims_akti
- **User**: elib_slims_akti
- **Password**: Akti@123!!
- **Host**: localhost

## ✅ File yang Sudah Dikonfigurasi
- `.env` - Environment configuration
- `config/database.php` - Database configuration  
- `.htaccess` - Apache configuration
- `robots.txt` - SEO configuration

## 📋 Langkah Deployment

### 1. Upload File
Upload semua file ke folder `public_html` di CyberPanel

### 2. Set Permission
Set permission 755 untuk folder:
- files/
- files/backup/
- files/cache/
- files/membercard/
- files/reports/
- images/
- images/cache/
- images/persons/
- logs/
- template/

### 3. Import Database
- Buka phpMyAdmin di CyberPanel
- Pilih database `elib_slims_akti`
- Import file SQL yang sudah disiapkan

### 4. Test Aplikasi
- Akses website melalui browser
- Test login admin
- Test fitur-fitur utama

## 🔧 Troubleshooting

### Error 500
- Cek file `.htaccess`
- Cek permission folder
- Cek error log di `/logs/php_errors.log`

### Database Error
- Pastikan kredensial database benar
- Cek file `config/database.php` dan `.env`

## 📞 Support
Jika ada masalah, hubungi tim support dengan menyertakan:
- Error message
- Screenshot error
- Log file dari `/logs/php_errors.log`

---
**Status**: ✅ Siap untuk deployment
**Database**: ✅ Sudah dikonfigurasi
**File**: ✅ Sudah siap
