from pathlib import Path
import tempfile, subprocess, socket, time, urllib.request, urllib.error
exe='C:/xampp/apache/bin/httpd.exe'
with tempfile.TemporaryDirectory(prefix='edugpa-apache-') as tmp:
    root=Path(tmp); site=root/'site'; site.mkdir()
    (site/'.htaccess').write_bytes(Path('uploads/.htaccess').read_bytes())
    for name in ['probe.php','probe.PHP','probe.php.jpg','probe.phtml','probe.phar','safe.jpg']:
        (site/name).write_text('fixture',encoding='ascii')
    sock=socket.socket(); sock.bind(('127.0.0.1',0)); port=sock.getsockname()[1]; sock.close()
    config=root/'httpd.conf'
    config.write_text(f'''
ServerRoot "C:/xampp/apache"
Listen 127.0.0.1:{port}
ServerName localhost
LoadModule authz_core_module modules/mod_authz_core.so
LoadModule headers_module modules/mod_headers.so
PidFile "{root.as_posix()}/pid"
ErrorLog "{root.as_posix()}/error.log"
DocumentRoot "{site.as_posix()}"
<Directory "{site.as_posix()}">
    AllowOverride All
    Require all granted
</Directory>
''',encoding='utf-8')
    subprocess.run([exe,'-f',str(config),'-t'],check=True)
    p=subprocess.Popen([exe,'-f',str(config),'-X'],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
    try:
        for _ in range(50):
            try:
                with socket.create_connection(('127.0.0.1',port),timeout=.1): break
            except OSError: time.sleep(.1)
        for name in ['probe.php','probe.PHP','probe.php.jpg','probe.phtml','probe.phar','safe.jpg']:
            try:
                r=urllib.request.urlopen(f'http://127.0.0.1:{port}/{name}',timeout=5); status=r.status
            except urllib.error.HTTPError as e: status=e.code
            expected=200 if name=='safe.jpg' else 403
            assert status==expected,(name,status)
            print('PASS Apache',name,status)
    finally:
        p.terminate(); p.wait(timeout=10)
