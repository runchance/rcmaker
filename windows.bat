@echo off
setlocal EnableExtensions
chcp 65001 >nul

cd /d "%~dp0"

set "PHP_BIN=php"
if exist "%~dp0php.exe" set "PHP_BIN=%~dp0php.exe"
if exist "%~dp0php\php.exe" set "PHP_BIN=%~dp0php\php.exe"

"%PHP_BIN%" windows.php start
if errorlevel 1 (
    echo.
    echo rcmaker Windows 启动失败，请检查 PHP 环境和配置。
)

pause
endlocal