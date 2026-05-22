@echo off
rem ============================================================
rem lnmp.cmd - Windows wrapper for scripts/lnmp
rem
rem Author:  PiaoYun ^<piaoyunsoft@163.com^>
rem Website: https://www.chinapyg.com
rem License: MIT
rem ============================================================
setlocal EnableDelayedExpansion

set "SCRIPT_DIR=%~dp0"
set "SCRIPT_DIR=%SCRIPT_DIR:~0,-1%"
set "LNMP_SH=%SCRIPT_DIR%\lnmp"

rem ---- 1) 显式查 Git Bash (绕过 WSL 桩 C:\Windows\System32\bash.exe) ----
set "GIT_BASH="
if exist "%ProgramFiles%\Git\bin\bash.exe"        set "GIT_BASH=%ProgramFiles%\Git\bin\bash.exe"
if exist "%ProgramFiles(x86)%\Git\bin\bash.exe"   set "GIT_BASH=%ProgramFiles(x86)%\Git\bin\bash.exe"
if exist "%LOCALAPPDATA%\Programs\Git\bin\bash.exe" set "GIT_BASH=%LOCALAPPDATA%\Programs\Git\bin\bash.exe"

if defined GIT_BASH (
    "%GIT_BASH%" "%LNMP_SH%" %*
    exit /b !errorlevel!
)

rem ---- 2) 退路: WSL ----
where wsl >nul 2>nul
if !errorlevel! equ 0 (
    for /f "usebackq delims=" %%i in (`wsl wslpath -a "%LNMP_SH%" 2^>nul`) do set "WSL_PATH=%%i"
    if defined WSL_PATH (
        wsl bash "!WSL_PATH!" %*
        exit /b !errorlevel!
    )
)

echo [err] 找不到可用 bash。请安装 Git for Windows: https://git-scm.com
exit /b 1
