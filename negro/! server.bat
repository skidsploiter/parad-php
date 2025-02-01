@echo off
echo [~] Starting PHP Server @ localhost:7060.
start php -S 127.0.0.1:7060
echo [~] Starting ngrok instance.
start ngrok http 7060 --url resolved-widely-bedbug.ngrok-free.app
exit