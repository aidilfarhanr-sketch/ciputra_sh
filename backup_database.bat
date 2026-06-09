@echo off
setlocal enabledelayedexpansion
REM Backup database Ciputra SH-1 untuk Windows/XAMPP.
REM Sesuaikan MYSQLDUMP_PATH, DB_USER, DB_PASS jika instalasi XAMPP berbeda.

set MYSQLDUMP_PATH=C:\xampp\mysql\bin\mysqldump.exe
set DB_NAME=ciputra_sh
set DB_USER=root
set DB_PASS=
set BACKUP_DIR=%~dp0backups

if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"
for /f "tokens=1-4 delims=/ " %%a in ("%date%") do set DATESTAMP=%%d%%b%%c
set TIMESTAMP=%time:~0,2%%time:~3,2%%time:~6,2%
set TIMESTAMP=%TIMESTAMP: =0%
set OUTFILE=%BACKUP_DIR%\%DB_NAME%_%DATESTAMP%_%TIMESTAMP%.sql

if "%DB_PASS%"=="" (
  "%MYSQLDUMP_PATH%" -u %DB_USER% --databases %DB_NAME% --routines --events --single-transaction > "%OUTFILE%"
) else (
  "%MYSQLDUMP_PATH%" -u %DB_USER% -p%DB_PASS% --databases %DB_NAME% --routines --events --single-transaction > "%OUTFILE%"
)

if %ERRORLEVEL% EQU 0 (
  echo Backup berhasil: %OUTFILE%
) else (
  echo Backup gagal. Cek path mysqldump, database, user, dan password.
)
pause
