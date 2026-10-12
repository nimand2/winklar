"""Optional Apache request tests. All SQL uses connection-local shadow tables."""
import http.client
import json
import os
from pathlib import Path
import re
import socket
import subprocess
import tempfile
import time
from urllib.parse import urlencode

root = Path(__file__).resolve().parents[2]
with tempfile.TemporaryDirectory(prefix="winklar-http-") as work:
    work = Path(work)
    work.chmod(0o755)
    for name in ("sessions", "limits", "empty"):
        (work / name).mkdir()
        (work / name).chmod(0o777 if name != "empty" else 0o755)
    prepend = work / "fixtures.php"
    prepend.write_text("""<?php
putenv('APP_ENV=' . (str_starts_with($_SERVER['HTTP_HOST'], 'secure.') ? 'production' : 'development'));
putenv('APP_BASE_PATH=' . (str_starts_with($_SERVER['HTTP_HOST'], 'subpath.') ? '/review-app' : ''));
putenv('API_TOKEN_SECRET=http-review-secret-for-temporary-fixtures-only');
putenv('LOGIN_LIMIT_DIR=%s/limits');
require '%s/tests/support/database.php';
$pdo = review_test_database();
review_test_seed($pdo);
$stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = 1');
$stmt->execute([password_hash('review-fixture-password', PASSWORD_DEFAULT)]);
""" % (work, root))
    with socket.socket() as sock:
        sock.bind(("127.0.0.1", 0))
        port = sock.getsockname()[1]
    config = work / "apache.conf"
    config.write_text(f"""
ServerRoot /etc/apache2
ServerName review.local
PidFile {work}/apache.pid
Listen 127.0.0.1:{port}
IncludeOptional /etc/apache2/mods-enabled/*.load
Include /etc/apache2/mods-enabled/php8.4.conf
TypesConfig /etc/mime.types
User www-data
Group www-data
ErrorLog {work}/error.log
LogLevel warn
php_value auto_prepend_file {prepend}
php_value session.save_path {work}/sessions
php_flag display_errors On
<Directory {root}>
    AllowOverride All
    Require all granted
</Directory>
<Directory {work}/empty>
    Require all granted
</Directory>
<VirtualHost 127.0.0.1:{port}>
    ServerName public.review
    ServerAlias secure.review
    DocumentRoot {root}/public
</VirtualHost>
<VirtualHost 127.0.0.1:{port}>
    ServerName root.review
    DocumentRoot {root}
</VirtualHost>
<VirtualHost 127.0.0.1:{port}>
    ServerName subpath.review
    DocumentRoot {work}/empty
    Alias /review-app {root}/public
</VirtualHost>
""")
    process = subprocess.Popen(["apache2", "-f", str(config), "-DFOREGROUND"], stdout=subprocess.DEVNULL, stderr=subprocess.PIPE, start_new_session=True)
    try:
        for _ in range(50):
            if process.poll() is not None:
                raise RuntimeError(process.stderr.read().decode())
            try:
                with socket.create_connection(("127.0.0.1", port), timeout=0.1):
                    break
            except OSError:
                time.sleep(0.1)

        def request(host, path, method="GET", body=None, cookie=None, token=None, content_type=None):
            headers = {"Host": host}
            if cookie:
                headers["Cookie"] = cookie
            if token:
                headers["Authorization"] = "Bearer " + token
            if content_type:
                headers["Content-Type"] = content_type
            conn = http.client.HTTPConnection("127.0.0.1", port, timeout=15)
            conn.request(method, path, body, headers)
            response = conn.getresponse()
            text = response.read().decode("utf-8")
            response_headers = dict(response.getheaders())
            for key, value in response.getheaders():
                if key.lower() == 'set-cookie' and value.startswith('PHPSESSID='):
                    response_headers['Set-Cookie'] = value
            result = response.status, response_headers, text
            conn.close()
            assert not re.search(r"(?:Fatal error|Warning:|Notice:)", text), (host, path, text[:400])
            return result

        for host, base in (("public.review", ""), ("root.review", ""), ("subpath.review", "/review-app")):
            status, headers, html = request(host, base + "/login")
            assert status == 200 and 'name="csrf_token"' in html
            csrf = re.search(r'name="csrf_token" value="([^"]+)"', html)[1]
            cookie = headers["Set-Cookie"].split(";", 1)[0]
            assert "HttpOnly" in headers["Set-Cookie"] and "secure" not in headers["Set-Cookie"].lower()
            assert f'action="{base}/login"' in html
            asset = re.search(r'<link[^>]+href="(' + re.escape(base) + r'/assets/[^"]+)"', html)[1]
            assert request(host, asset)[0] == 200
            assert request(host, base + "/login", "POST", "login=admin", cookie, content_type="application/x-www-form-urlencoded")[0] == 403
            form = urlencode({"login": "admin", "password": "review-fixture-password", "csrf_token": csrf})
            status, headers, _ = request(host, base + "/login", "POST", form, cookie, content_type="application/x-www-form-urlencoded")
            assert status == 302 and headers["Location"] == base + "/dashboard"
            cookie = headers["Set-Cookie"].split(";", 1)[0] if headers["Set-Cookie"].startswith("PHPSESSID=") else cookie
            for path in ("/dashboard", "/anlass/1/konfiguration", "/anlass/1/loesen/1", "/anlass/1/loesen/1/abrechnen", "/anlass/1/kasse", "/anlass/1/abschliessen"):
                assert request(host, base + path, cookie=cookie)[0] == 200, (host, path)
            _, _, form_html = request(host, base + '/anlass/1/konfiguration', cookie=cookie)
            form_csrf = re.search(r'name="csrf_token" value="([^"]+)"', form_html)[1]
            for path, fields in (
                ('/anlass/neu', {'name_anlass[]':'invalid'}),
                ('/anlass/1/konfiguration/stiche', {'name':'Retained name','preis':'-1','anzahl_schuss':'5'}),
                ('/anlass/1/konfiguration/final', {'final_stich_id':'1','final_anzahl_u18':'-1','final_anzahl_ue18':'6'}),
                ('/anlass/1/loesen/1', {'datum':'2026-02-30','stich_ids[]':'1','stich_counts[1]':'2'}),
                ('/anlass/1/loesen/1/abrechnen/schuesse', {'schuesse[0]':'invalid'}),
            ):
                status, _, body = request(host, base + path, 'POST', urlencode(dict(fields, csrf_token=form_csrf)), cookie, content_type='application/x-www-form-urlencoded')
                assert status == 422, (host,path,status,body[:200])
                if 'Retained name' in fields.values():
                    assert 'value="Retained name"' in body
            for path, fields in (
                ('/anlass/1/loesen/1/abrechnen', {}),
                ('/anlass/1/loesen/1/abrechnen/schuss', {'primaerwertung':'9','externe_nummer':'901','schuss_zeit':'2026-04-10T16:00:59'}),
                ('/anlass/1/loesen/1/abrechnen/schuss/1', {'primaerwertung':'9','externe_nummer':'901','schuss_zeit':'2026-04-10T16:00:59'}),
                ('/anlass/1/loesen/1/abrechnen/schuss/1/loeschen', {}),
            ):
                assert request(host, base + path, 'POST', urlencode(dict(fields,csrf_token=form_csrf)), cookie, content_type='application/x-www-form-urlencoded')[0] == 302, path
            status, _, body = request(host, base + "/api/login", "POST", json.dumps({"Username":"admin","Password":"review-fixture-password"}), content_type="application/json")
            assert status == 200
            token = json.loads(body)["token"]
            assert request(host, base + "/api/anlaesse", token=token)[0] == 200
            for bad in ("", "{oops", "[]", "null"):
                assert request(host, base + "/api/login", "POST", bad, content_type="application/json")[0] == 422
            shot = {"StartNr":1,"Primaerwertung":9.0,"Zeit":"2026-04-10 15:00:59","ExterneNummer":901,"InsDel":1,"TimeSinceChange":2.5}
            for payload, expected in (({},400), ({"Shots":[{}]},422), ({"AnlassId":[],"Shots":[]},422), ({"Shots":[dict(shot,primaerwertung=[])]},422), ({"Shots":[shot,shot]},201)):
                status, _, body = request(host, base + "/api/anlaesse/1/shots/import", "POST", json.dumps(payload), token=token, content_type="application/json")
                assert status == expected, (host, payload, status, body)
                if expected == 201:
                    assert json.loads(body) == {"success":True,"imported":1,"accepted":2,"duplicates":1}
            assert request(host, base + "/api/anlaesse/1oops/shooters", token=token)[0] == 404
            assert request(host, base + "/api/anlaesse/1/shooters/new?sinceId[]=1", token=token)[0] == 422
            if host == "root.review":
                for forbidden in ("/config/app.php", "/docs/reference/SiusData.pdf", "/storage/backups/", "/.git/config", "/tests/support/database.php"):
                    assert request(host, forbidden)[0] == 403
            if base:
                assert request(host, "/review-app-other/login")[0] in (403,404)
        status, headers, _ = request("secure.review", "/login")
        assert status == 200 and "secure" in headers["Set-Cookie"].lower()
        for attempt in range(11):
            status, headers, _ = request("public.review", "/api/login", "POST", '{"Username":"rate-limit-fixture","Password":"invalid"}', content_type="application/json")
            assert status == (401 if attempt < 10 else 429)
        assert headers["Retry-After"] == "900"
        print("Apache HTTP checks passed: public/project/subpath roots, assets, protected files, cookies, CSRF, login, authenticated pages, Bearer API, imports, malformed requests and rate limits.", flush=True)
    finally:
        process.terminate()
        try:
            process.wait(timeout=10)
        except subprocess.TimeoutExpired:
            process.kill(); process.wait(timeout=5)
