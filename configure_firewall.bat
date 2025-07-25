@echo off
echo MIW Travel - Configure Windows Firewall
echo =========================================
echo.

echo [*] Adding firewall rules for Docker ports...
netsh advfirewall firewall add rule name="MIW Docker Web Port" dir=in action=allow protocol=TCP localport=8080
netsh advfirewall firewall add rule name="MIW Docker PHPMyAdmin Port" dir=in action=allow protocol=TCP localport=8081
netsh advfirewall firewall add rule name="MIW Docker MySQL Port" dir=in action=allow protocol=TCP localport=3307

echo.
echo [*] Firewall rules added successfully!
echo [*] Other devices can now access:
echo [*] - Web App: http://192.168.1.7:8080
echo [*] - PHPMyAdmin: http://192.168.1.7:8081
echo.
echo =========================================
