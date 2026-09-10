@echo off
title Menyiapkan Paket Hosting Banterpool
echo ======================================================================
echo    MEMBUAT FILE ZIP BANTERPOOL SIAP UPLOAD KE HOSTINGER
echo ======================================================================
echo.
echo 1. Mengompilasi Aset CSS dan JS Produksi (npm run build)...
call npm run build
echo.
echo 2. Membersihkan Cache Sementara (php artisan optimize:clear)...
call php artisan optimize:clear
echo.
echo 3. Mengemas proyek ke banterpool_ready_for_hosting.zip...
if exist banterpool_ready_for_hosting.zip del /f /q banterpool_ready_for_hosting.zip
tar.exe -a -c -f banterpool_ready_for_hosting.zip --exclude=node_modules --exclude=.git --exclude=banterpool_ready_for_hosting.zip * .htaccess .env.example
echo.
echo ======================================================================
echo    SUKSES! File 'banterpool_ready_for_hosting.zip' siap di-upload ke
echo    folder 'public_html' di File Manager Hostinger!
echo ======================================================================
echo.
pause
