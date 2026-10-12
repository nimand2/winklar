# Lokale Ablage

`backups/` enthält lokale Datenbankbackups. Sie werden weder versioniert noch
in Updatepakete aufgenommen. Das vorhandene `winklar.sql` wurde aus dem früheren
Ordner `backup DB/` übernommen. Es wurde nicht in die Datenbank eingespielt.

Backups müssen außerhalb des öffentlichen Document Roots liegen. Bei der
Projektroot-Konfiguration sperrt `.htaccess` den Zugriff auf `storage/`.
