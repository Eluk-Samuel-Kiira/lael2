@echo off
REM Check if a sync is already running to prevent overlap
if exist "storage\sync.lock" exit

REM Create a lock file
echo syncing > storage\sync.lock

REM Run the actual sync
C:\xampp\php\php.exe artisan pos:sync --tenant=2 >> storage\logs\sync_js.log 2>&1

REM Remove lock file
del storage\sync.lock