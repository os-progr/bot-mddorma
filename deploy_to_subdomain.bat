@echo off
chcp 65001 >nul
echo ========================================================
echo   QUANTUM AI - DESPLIEGUE DIRECTO A BOT.MDDORMA.COM
echo ========================================================

echo [1/4] Copiando archivos actualizados hacia DORM WEB...
xcopy /E /Y /I "C:\Users\pucll\Desktop\Binancepac\dashboard_subdominio\*" "c:\Users\pucll\Desktop\DORM WEB\bot.mddorma.com\" >nul

echo [2/4] Guardando historial en GitHub bot-mddorma...
cd /d "C:\Users\pucll\Desktop\Binancepac\dashboard_subdominio"
git add .
git commit -m "update: sincronizacion automatica con bot.mddorma.com [%date% %time%]" >nul 2>&1
git push origin main >nul 2>&1

echo [3/4] Subiendo a produccion BanaHosting (FTP Pipeline)...
cd /d "c:\Users\pucll\Desktop\DORM WEB"
call silent_push.bat "deploy: sincronizacion de dashboard bot.mddorma.com"

echo [4/4] Activando archivos en el directorio del subdominio...
curl.exe -4 -s "https://mddorma.com/sync_bot_folder.php?key=mddorma_sync_9f83a7c6e14b2d5890e1f4a7c8b2d1e0"
echo.
echo ========================================================
echo   LISTO: Todo actualizado en https://bot.mddorma.com
echo ========================================================
