@echo off
title Stardena POS Sync Service
color 0A

:START
echo [%date% %time%] Checking for Apache...

REM Wait until Apache is responding on port 80 (or your specific port)
curl -s http://localhost > nul
if errorlevel 1 (
    echo Apache not ready. Waiting 10 seconds...
    timeout /t 10 > nul
    goto START
)

echo Apache is Online. Starting Sync Scheduler...

:LOOP
REM Run the Laravel Scheduler
C:\xampp\php\php.exe C:\xampp\htdocs\Stardena\LAEL\artisan schedule:run >> C:\xampp\htdocs\Stardena\LAEL\storage\logs\sync_service.log 2>&1

REM Wait 60 seconds before checking again
timeout /t 60 > nul
goto LOOP