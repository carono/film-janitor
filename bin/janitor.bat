@echo off

@setlocal

set DIR_PATH=%~dp0

if "%PHP_COMMAND%" == "" set PHP_COMMAND=php.exe

"%PHP_COMMAND%" "%DIR_PATH%..\janitor.php" %*

@endlocal
