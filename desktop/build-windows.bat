@echo off
rem JOUD Desktop - build on Windows (produces dist\JOUD.exe portable + dist\JOUD-Setup.exe installer)
cd /d "%~dp0"
where node >nul 2>nul || (echo Node.js is not installed. Install the LTS version from https://nodejs.org then run this file again. & pause & exit /b 1)
call npm install || goto :err
call npm test || goto :err
call npm run dist || goto :err
echo.
echo Done. Output folder: %~dp0dist
echo   JOUD.exe        (portable)
echo   JOUD-Setup.exe  (installer)
pause & exit /b 0
:err
echo Build failed - see messages above.
pause & exit /b 1
