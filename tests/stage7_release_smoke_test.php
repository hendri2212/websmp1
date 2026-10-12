<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' || !in_array($argv[1] ?? '', ['--run','--configuration','--https'], true)) { http_response_code(403); exit('CLI --run/--configuration/--https <bundle-directory> only'); }
require __DIR__ . '/stage3_database_preflight.php';
$pdo=null; $process=null; $run=null; $site=null; $checks=[]; $accounts=[]; $owned=[]; $hashes=[]; $baseline=[]; $schema=[]; $clean=false; $failure=null;
function smoke7_check(bool $ok,string $label): void { global $checks; $checks[]=['passed'=>$ok,'label'=>$label]; if(!$ok){throw new RuntimeException($label);} }
function smoke7_http(array $client,string $path,?array $post=null,?string $cookie=null): array {
    $h=curl_init($client['url'].$path); $headers=[];
    curl_setopt_array($h,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>20,CURLOPT_PATH_AS_IS=>true,
        CURLOPT_HEADERFUNCTION=>static function($h,string $line)use(&$headers):int{if(str_contains($line,':')){[$k,$v]=explode(':',$line,2);$headers[strtolower(trim($k))][]=trim($v);}return strlen($line);}]);
    if($cookie!==null){curl_setopt($h,CURLOPT_HTTPHEADER,['Cookie: PHPSESSID='.$cookie]);}
    else{curl_setopt_array($h,[CURLOPT_COOKIEFILE=>$client['jar'],CURLOPT_COOKIEJAR=>$client['jar']]);}
    if(isset($client['ca'])){curl_setopt_array($h,[CURLOPT_CAINFO=>$client['ca'],CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2]);}
    if($post!==null){$multi=(bool)array_filter($post,fn($v)=>$v instanceof CURLFile);curl_setopt_array($h,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$multi?$post:http_build_query($post)]);}
    $body=curl_exec($h);$status=curl_getinfo($h,CURLINFO_RESPONSE_CODE);curl_close($h);
    if(!is_string($body)){throw new RuntimeException('HTTP transport failed');}
    smoke7_check(!preg_match('~Fatal error|Warning:|Stack trace:|SQLSTATE\[|C:[\\\\/]xampp|C:[\\\\/]Users~i',$body),'No internal error exposure: '.$path);
    preg_match('/name="csrf_token"\s+value="([^"]+)"/',$body,$token);
    return ['status'=>$status,'body'=>$body,'token'=>$token[1]??'','headers'=>$headers];
}
function smoke7_token(array $client,string $path): string { $r=smoke7_http($client,$path);smoke7_check($r['status']===200&&$r['token']!=='','CSRF form available: '.$path);return $r['token']; }
function smoke7_submit(array $client,string $path,array $post):array{$post['csrf_token']=smoke7_token($client,$path);return smoke7_http($client,$path,$post);}
function smoke7_schema(PDO $pdo,string $table):string{return preg_replace('/ AUTO_INCREMENT=\d+/','',$pdo->query('SHOW CREATE TABLE `'.$table.'`')->fetch()['Create Table']);}
function smoke7_files(string $directory):array{if(!is_dir($directory)){return [];} $paths=[];foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory,FilesystemIterator::SKIP_DOTS))as$f){if($f->isFile()&&!$f->isLink()){$paths[]=str_replace('\\','/',$f->getPathname());}}return $paths;}
try{
    $pdo=stage3_test_database();
    $tables=$pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    foreach($tables as$table){smoke7_check((bool)preg_match('/\A[a-z0-9_]+\z/',$table),'Safe fixture table');$baseline[$table]=(int)$pdo->query('SELECT COUNT(*) FROM `'.$table.'`')->fetchColumn();$schema[$table]=smoke7_schema($pdo,$table);if($table!=='stage3_test_environment'){smoke7_check($baseline[$table]===0,'Testing table initially empty: '.$table);}}
    $bundle=realpath($argv[2]??'');smoke7_check(is_string($bundle),'Bundle directory exists');
    $receipt=json_decode(file_get_contents($bundle.'/build-receipt.json'),true,512,JSON_THROW_ON_ERROR);
    $zipPath=$bundle.'/website-source.zip';smoke7_check(hash_file('sha256',$zipPath)===$receipt['zip_sha256'],'Original archive receipt hash');
    $inventory=json_decode(file_get_contents($bundle.'/file-hashes.json'),true,512,JSON_THROW_ON_ERROR);
    $private=realpath(dirname(__DIR__).'/storage/testing/stage3-mariadb');smoke7_check(is_string($private),'Verified private testing root');
    $tag=bin2hex(random_bytes(6));$run=$private.'/release-smoke7-'.$tag;$site=$run.'/site';mkdir($site,0700,true);mkdir($run.'/sessions',0700);
    $zip=new ZipArchive();smoke7_check($zip->open($zipPath,ZipArchive::RDONLY)===true,'Release ZIP opens');
    try{foreach($inventory as$entry){$path=$entry['path'];smoke7_check(!preg_match('~(^/|^[A-Za-z]:|\\\\|(^|/)\.\.?(/|$))~',$path),'Safe release path');$bytes=$zip->getFromName($path);smoke7_check(is_string($bytes)&&hash('sha256',$bytes)===$entry['sha256'],'Release bytes: '.$path);if(!is_dir(dirname($site.'/'.$path))){mkdir(dirname($site.'/'.$path),0700,true);}file_put_contents($site.'/'.$path,$bytes);$hashes[$path]=$entry['sha256'];}}finally{$zip->close();}
    smoke7_check(count($hashes)===78,'Exactly 78 release source files; none copied from repository');
    $config=json_decode(file_get_contents($private.'/connection.json'),true,512,JSON_THROW_ON_ERROR);
    // Verified restricted testing connection only; no application helper/config source substitution.
    $socket=stream_socket_server('tcp://127.0.0.1:0',$code,$message);smoke7_check(is_resource($socket),'Free Apache loopback port');$port=(int)substr(strrchr(stream_socket_get_name($socket,false),':'),1);fclose($socket);
    $url='http://127.0.0.1:'.$port;
    file_put_contents($site.'/.env',"APP_ENV=local\nAPP_URL=$url\nDB_HOST=127.0.0.1\nDB_PORT=33317\nDB_DATABASE=".$config['database']."\nDB_USERNAME=".$config['username']."\nDB_PASSWORD=".$config['password']."\nDB_CHARSET=utf8mb4\n");
    foreach(['storage/uploads/documents','storage/uploads/accreditation','storage/media-cleanup','storage/backups/.temporary']as$folder){mkdir($site.'/'.$folder,0700,true);}
    $ini="extension_dir=C:/xampp/php/ext\nextension=pdo_mysql\nextension=mbstring\nextension=fileinfo\nextension=gd\nextension=zip\ndisplay_errors=Off\ndisplay_startup_errors=Off\nlog_errors=On\nexpose_php=Off\nmemory_limit=256M\nupload_max_filesize=20M\npost_max_size=25M\nsession.save_path=\"".$run.'/sessions'."\"\ndate.timezone=Asia/Shanghai\nerror_log=\"".$run.'/php-errors.log'."\"\n";
    file_put_contents($run.'/php.ini',$ini);
    $apache='C:/xampp/apache/bin/httpd.exe';
    $conf='ServerRoot "C:/xampp/apache"'."\nListen 127.0.0.1:$port\nServerName 127.0.0.1:$port\nTraceEnable Off\nServerTokens Prod\nServerSignature Off\n"
        .'PidFile "'.$run.'/apache.pid"'."\n".'ErrorLog "'.$run.'/apache.log"'."\nLogLevel warn\n"
        ."LoadModule authz_core_module modules/mod_authz_core.so\nLoadModule authz_host_module modules/mod_authz_host.so\nLoadModule dir_module modules/mod_dir.so\nLoadModule mime_module modules/mod_mime.so\nLoadModule rewrite_module modules/mod_rewrite.so\nLoadModule headers_module modules/mod_headers.so\n"
        ."LoadFile C:/xampp/php/php8ts.dll\nLoadFile C:/xampp/php/libpq.dll\nLoadFile C:/xampp/php/libsqlite3.dll\nLoadModule php_module C:/xampp/php/php8apache2_4.dll\n"
        .'PHPIniDir "'.$run.'"'."\nTypesConfig C:/xampp/apache/conf/mime.types\nDirectoryIndex index.php\n"
        .'DocumentRoot "'.$site.'"'."\n<Directory />\nAllowOverride None\nRequire all denied\n</Directory>\n"
        .'<Directory "'.$site.'">'."\nAllowOverride All\nRequire all granted\nOptions -Indexes\n</Directory>\n<FilesMatch \"\\.php$\">\nSetHandler application/x-httpd-php\n</FilesMatch>\n";
    if (($argv[1] ?? '') === '--https') {
        $socket=stream_socket_server('tcp://127.0.0.1:0',$code,$message);smoke7_check(is_resource($socket),'Free TLS loopback port');$tlsPort=(int)substr(strrchr(stream_socket_get_name($socket,false),':'),1);fclose($socket);
        $certificate=$run.'/tls-test-cert.pem';$privateKey=$run.'/tls-test-key.pem';
        $certProcess=proc_open(['C:/xampp/apache/bin/openssl.exe','req','-x509','-newkey','rsa:2048','-nodes','-days','1','-subj','/CN=127.0.0.1','-addext','subjectAltName=IP:127.0.0.1','-config','C:/xampp/apache/conf/openssl.cnf','-keyout',$privateKey,'-out',$certificate],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$certPipes,$run,null,['bypass_shell'=>true]);
        smoke7_check(is_resource($certProcess),'Temporary test certificate process');fclose($certPipes[0]);stream_get_contents($certPipes[1]);stream_get_contents($certPipes[2]);fclose($certPipes[1]);fclose($certPipes[2]);smoke7_check(proc_close($certProcess)===0&&is_file($certificate)&&is_file($privateKey),'Temporary TLS certificate created outside DocumentRoot');
        $conf.="\nLoadModule socache_shmcb_module modules/mod_socache_shmcb.so\nLoadModule ssl_module modules/mod_ssl.so\nListen 127.0.0.1:$tlsPort\n<VirtualHost 127.0.0.1:$tlsPort>\nServerName 127.0.0.1:$tlsPort\nSSLEngine on\nSSLProtocol -all +TLSv1.2 +TLSv1.3\nSSLCertificateFile \"$certificate\"\nSSLCertificateKeyFile \"$privateKey\"\n</VirtualHost>\n";
    }
    file_put_contents($run.'/apache.conf',$conf);
    $probe=proc_open([$apache,'-t','-f',$run.'/apache.conf'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$run,null,['bypass_shell'=>true]);fclose($pipes[0]);$syntax=stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);smoke7_check(proc_close($probe)===0&&str_contains($syntax,'Syntax OK'),'Apache isolated config syntax');
    $process=proc_open([$apache,'-X','-f',$run.'/apache.conf'],[0=>['pipe','r'],1=>['file',$run.'/apache-stdout.log','a'],2=>['file',$run.'/apache-stderr.log','a']],$pipes,$run,null,['bypass_shell'=>true]);smoke7_check(is_resource($process),'Start isolated Apache');fclose($pipes[0]);
    $ready=false;for($i=0;$i<100;$i++){$s=@stream_socket_client('tcp://127.0.0.1:'.$port,$code,$message,0.1);if(is_resource($s)){fclose($s);$ready=true;break;}if(!proc_get_status($process)['running']){break;}usleep(100000);}smoke7_check($ready,'Apache loopback listener ready');
    $anonymous=['url'=>$url,'jar'=>$run.'/anonymous.cookies'];$clients=[];
    if (($argv[1] ?? '') === '--https') {
        file_put_contents($site.'/.env',str_replace('APP_URL='.$url,'APP_URL=https://127.0.0.1:'.$tlsPort,file_get_contents($site.'/.env')));
        $tls=['url'=>'https://127.0.0.1:'.$tlsPort,'jar'=>$run.'/https.cookies','ca'=>$certificate];
        $expectedHeaders=['x-content-type-options'=>'nosniff','referrer-policy'=>'strict-origin-when-cross-origin','x-frame-options'=>'SAMEORIGIN','content-security-policy'=>"frame-ancestors 'self'",'permissions-policy'=>'camera=(), microphone=(), geolocation=()'];
        foreach(['/','/profil','/berita','/galeri','/dokumen','/ppdb','/admin/login.php']as$path){
            $reply=smoke7_http($tls,$path);smoke7_check($reply['status']===200,'Real TLS verified page: '.$path);
            foreach($expectedHeaders as$name=>$value){smoke7_check(($reply['headers'][$name]??[])===[$value],'TLS security header: '.$name.' '.$path);}
            smoke7_check(!isset($reply['headers']['strict-transport-security']),'HSTS not introduced for testing: '.$path);
            smoke7_check(!isset($reply['headers']['x-powered-by']),'No PHP version header: '.$path);
            $document=new DOMDocument();$previous=libxml_use_internal_errors(true);$document->loadHTML($reply['body']);libxml_clear_errors();libxml_use_internal_errors($previous);
            foreach((new DOMXPath($document))->query('//*[@src or @srcset or @action] | //link[@href]')as$node){foreach(['src','srcset','action','href']as$attribute){if($node->hasAttribute($attribute)){smoke7_check(!preg_match('~(?:^|[\s,])http://~i',$node->getAttribute($attribute)),'No obvious mixed content/form URL: '.$path.' '.$attribute);}}}
            smoke7_check(!preg_match('~url\(\s*[\x22\x27]?http://~i',$reply['body']),'No obvious inline CSS mixed content: '.$path);
        }
        $fresh=['url'=>$tls['url'],'jar'=>$run.'/fresh-https.cookies','ca'=>$certificate];$reply=smoke7_http($fresh,'/admin/login.php');$cookies=implode('; ',$reply['headers']['set-cookie']??[]);
        smoke7_check((bool)preg_match('/PHPSESSID=/i',$cookies),'HTTPS session cookie emitted');
        foreach(['Secure','HttpOnly','SameSite=Lax']as$flag){smoke7_check((bool)preg_match('/(?:^|;\s*)'.preg_quote($flag,'/').'(?:;|$)/i',$cookies),'HTTPS cookie flag: '.$flag);}
        $plain=smoke7_http($anonymous,'/admin/login.php');$plainCookies=implode('; ',$plain['headers']['set-cookie']??[]);
        smoke7_check($plain['status']===200,'HTTP redirect not configured in current bundle/testing server');
        smoke7_check(!preg_match('/;\s*Secure(?:;|$)/i',$plainCookies),'HTTP localhost cookie not forced Secure');
        foreach(['HttpOnly','SameSite=Lax']as$flag){smoke7_check((bool)preg_match('/(?:^|;\s*)'.preg_quote($flag,'/').'(?:;|$)/i',$plainCookies),'HTTP cookie flag: '.$flag);}
        foreach(smoke7_files($site.'/public/assets')as$file){if(preg_match('/\.(css|js)$/i',$file)){smoke7_check(!preg_match('~(?:url\(\s*[\x22\x27]?|[\x22\x27])http://~i',file_get_contents($file)),'No obvious HTTP resource in release asset: '.basename($file));}}
    } elseif (($argv[1] ?? '') === '--configuration') {
        $localEnvironment=file_get_contents($site.'/.env');
        $productionBase="APP_ENV=production\nAPP_URL=https://school.example.invalid\n";
        $testingValues="DB_HOST=127.0.0.1\nDB_PORT=33317\nDB_DATABASE=".$config['database']."\nDB_USERNAME=".$config['username']."\nDB_PASSWORD=".$config['password']."\nDB_CHARSET=utf8mb4\n";
        $variants=[
            'missing-database-values'=>$productionBase,
            'empty-database-password'=>$productionBase."DB_HOST=db.example.invalid\nDB_PORT=33317\nDB_DATABASE=school_content\nDB_USERNAME=restricted_account\nDB_PASSWORD=\nDB_CHARSET=utf8mb4\n",
            'development-fallback-candidate'=>$productionBase."DB_HOST=localhost\nDB_PORT=3306\nDB_DATABASE=website_sekolah\nDB_USERNAME=root\nDB_PASSWORD=\nDB_CHARSET=utf8mb4\n",
            'valid-testing-credential-policy-rejected'=>$productionBase.$testingValues,
        ];
        foreach($variants as$name=>$environment){
            file_put_contents($site.'/.env',$environment);
            foreach([['/',null],['/admin/login.php',null],['/admin/login.php',['username'=>'synthetic_only','password'=>'synthetic_only']],['/admin/dashboard.php',null]]as[$path,$post]){
                $reply=smoke7_http($anonymous,$path,$post);
                smoke7_check($reply['status']===503,'Production configuration fail closed: '.$name.' '.$path);
                smoke7_check($reply['body']==='Terjadi gangguan pada sistem. Silakan coba lagi nanti.','Generic configuration error: '.$name.' '.$path);
                smoke7_check(!isset($reply['headers']['set-cookie']),'No session on rejected configuration: '.$name.' '.$path);
                smoke7_check(!str_contains($reply['body'],$config['password'])&&!str_contains($reply['body'],$config['username'])&&!str_contains($reply['body'],'mysql:'),'No credential/DSN exposure: '.$name.' '.$path);
            }
            foreach($baseline as$table=>$count){smoke7_check((int)$pdo->query('SELECT COUNT(*) FROM `'.$table.'`')->fetchColumn()===$count,'Rejected config no database changes: '.$name.' '.$table);}
        }
        file_put_contents($site.'/.env',$localEnvironment);
        smoke7_check(smoke7_http($anonymous,'/')['status']===200,'Explicit local testing configuration homepage works');
        smoke7_check(smoke7_http($anonymous,'/admin/login.php')['token']!=='','Explicit local testing configuration login form works');
        smoke7_check(smoke7_http($anonymous,'/admin/dashboard.php')['status']===302,'Explicit local testing configuration still requires authentication');
    } else {
    $password=bin2hex(random_bytes(24));
    $stmt=$pdo->prepare('INSERT INTO admins (username,full_name,role,is_active,password_hash) VALUES (?,?,?,?,?)');
    foreach(['superadmin','editor','ppdb']as$role){$username='release7'.$tag.'_'.$role;$stmt->execute([$username,'TEST_TAHAP7_RELEASE_ACCOUNT',$role,1,password_hash($password,PASSWORD_DEFAULT)]);$accounts[]=['id'=>(int)$pdo->lastInsertId(),'username'=>$username];$client=['url'=>$url,'jar'=>$run.'/'.$role.'.cookies'];$form=smoke7_http($client,'/admin/login.php');$login=smoke7_http($client,'/admin/login.php',['username'=>$username,'password'=>$password,'csrf_token'=>$form['token'],'role'=>'superadmin']);smoke7_check($login['status']===302,'Login actual release: '.$role);smoke7_check(smoke7_http($client,'/admin/dashboard.php')['status']===200,'Dashboard actual role: '.$role);$clients[$role]=$client;}
    $super=$clients['superadmin'];$editor=$clients['editor'];$ppdb=$clients['ppdb'];
    $diagnostic=smoke7_http($super,'/admin/production_check.php');
    smoke7_check($diagnostic['status']===200,'Superadmin diagnostic remains accessible');
    smoke7_check(str_contains($diagnostic['body'],'Keamanan password akun existing belum diverifikasi.'),'Diagnostic does not claim password verification');
    smoke7_check(str_contains($diagnostic['body'],'peninjauan dan rotasi secara privat'),'Diagnostic retains historical credential rotation warning');
    foreach([$editor,$ppdb]as$client){smoke7_check(smoke7_http($client,'/admin/production_check.php')['status']===403,'Non-superadmin diagnostic direct URL denied');}
    foreach(['/.env','/config/database.php','/includes/auth.php','/storage/','/tests/probe.php']as$path){smoke7_check(smoke7_http($anonymous,$path)['status']===403,'Sensitive URL blocked: '.$path);}
    foreach(['/','/profil','/berita','/galeri','/dokumen','/ppdb']as$path){$r=smoke7_http($anonymous,$path);smoke7_check($r['status']===200&&str_contains($r['body'],'<html'),'Public release page: '.$path);}
    foreach(['/public/assets/css/style.css','/public/favicon.png','/public/logo-sekolah.png','/public/kepala-sekolah.jpeg']as$path){smoke7_check(smoke7_http($anonymous,$path)['status']===200,'Release asset: '.$path);}
    $p=smoke7_http($anonymous,'/ppdb');smoke7_check(str_contains($p['body'],'Dinas Pendidikan')&&!preg_match('~<form[^>]+(?:post|multipart)|type="file"|name="nik"~i',$p['body']),'PPDB information no local registration');
    $ppdbBefore=$pdo->query('SELECT * FROM ppdb_applications ORDER BY id')->fetchAll();
    foreach(['/ppdb','/ppdb/bukti','/pages/ppdb_receipt.php','/admin/ppdb_applications.php','/admin/ppdb_download.php','/admin/ppdb_export.php']as$path){$expected=$path==='/ppdb'?405:410;foreach([null,['id'=>'1','action'=>'delete'],['id'=>'1','csrf_token'=>'OLD_SYNTHETIC_TOKEN']]as$post){$r=smoke7_http($super,$path,$post);smoke7_check($r['status']===($post===null&&$path==='/ppdb'?200:$expected),'PPDB passive/tombstone direct request: '.$path);}}
    smoke7_check($pdo->query('SELECT * FROM ppdb_applications ORDER BY id')->fetchAll()===$ppdbBefore,'PPDB legacy zero mutation');
    foreach(['/admin/settings.php','/admin/admins.php','/admin/db_backup.php']as$path){smoke7_check(smoke7_http($editor,$path)['status']===403,'Editor restricted URL: '.$path);}
    $image=imagecreatetruecolor(640,480);imagepng($image,$run.'/fixture.png');imagedestroy($image);
    $pdf="%PDF-1.4\n";$objects=['<< /Type /Catalog /Pages 2 0 R >>','<< /Type /Pages /Kids [3 0 R] /Count 1 >>','<< /Type /Page /Parent 2 0 R /MediaBox [0 0 200 200] >>'];$offsets=[];foreach($objects as$i=>$object){$offsets[]=strlen($pdf);$pdf.=($i+1)." 0 obj\n".$object."\nendobj\n";}$xref=strlen($pdf);$pdf.="xref\n0 4\n0000000000 65535 f \n";foreach($offsets as$offset){$pdf.=sprintf("%010d 00000 n \n",$offset);}$pdf.="trailer\n<< /Size 4 /Root 1 0 R >>\nstartxref\n$xref\n%%EOF\n";file_put_contents($run.'/fixture.pdf',$pdf);
    $cases=require __DIR__.'/fixtures/stage3_content_cases.php';
    foreach($cases as$module=>$case){
        $path='/admin/'.$module.'.php';$label='TEST_TAHAP7_RELEASE_'.$case['label'].'_'.$tag;$data=$case['payload'];$data[$case['field']]=$label;if($module==='news'){$data['slug']='test-release7-'.$tag;}
        $before=(int)$pdo->query('SELECT COUNT(*) FROM `'.$case['table'].'`')->fetchColumn();
        $reply=smoke7_http($anonymous,$path,$data);smoke7_check($reply['status']===302,'Anonymous POST blocked: '.$module);
        $data['csrf_token']=smoke7_token($ppdb,'/admin/change_password.php');$reply=smoke7_http($ppdb,$path,$data);smoke7_check($reply['status']===403,'PPDB role POST cannot elevate: '.$module);unset($data['csrf_token']);
        $reply=smoke7_http($super,$path,$data);smoke7_check($reply['status']===200&&(int)$pdo->query('SELECT COUNT(*) FROM `'.$case['table'].'`')->fetchColumn()===$before,'Missing CSRF prevents CREATE: '.$module);
        if(in_array($module,['news','gallery'],true)){$data['image_file']=new CURLFile($run.'/fixture.png','image/png','safe.png');}if($module==='documents'){$data['document_file']=new CURLFile($run.'/fixture.pdf','application/pdf','safe.pdf');}
        $reply=smoke7_submit($super,$path,$data);$query=$pdo->prepare('SELECT * FROM `'.$case['table'].'` WHERE `'.$case['field'].'`=?');$query->execute([$label]);$row=$query->fetch();smoke7_check($reply['status']===200&&is_array($row),'CREATE database record: '.$module);$owned[]=['table'=>$case['table'],'id'=>(int)$row['id'],'field'=>$case['field'],'prefix'=>'TEST_TAHAP7_RELEASE_'];
        smoke7_check(str_contains(smoke7_http($super,$path.'?edit='.$row['id'])['body'],$label),'READ admin edit: '.$module);
        $public=smoke7_http($anonymous,$case['public']);smoke7_check($public['status']===200&&str_contains($public['body'],$label),'READ public content: '.$module);
        if($module==='news'){smoke7_check(str_contains(smoke7_http($anonymous,'/berita/'.$data['slug'])['body'],$label),'News detail route from release');}
        if(in_array($module,['news','gallery'],true)){smoke7_check(is_file($site.'/'.$row['image_path']),'Image upload persisted: '.$module);smoke7_check(smoke7_http($anonymous,'/'.ltrim($row['image_path'],'/'))['status']===200,'Uploaded image HTTP served: '.$module);unset($data['image_file']);}
        if($module==='documents'){
            smoke7_check(hash('sha256',smoke7_http($anonymous,'/dokumen/unduh?id='.$row['id'])['body'])===hash('sha256',$pdf),'PDF download exact fixture');
            $counter=$pdo->prepare('SELECT download_count FROM school_documents WHERE id=?');$counter->execute([$row['id']]);$beforeDownload=$counter->fetchColumn();
            $invalid=smoke7_http($anonymous,'/dokumen/unduh?id='.$row['id'].'abc');$counter->execute([$row['id']]);
            smoke7_check($invalid['status']===404&&!str_starts_with($invalid['body'],'%PDF')&&$counter->fetchColumn()===$beforeDownload,'Malformed download ID rejected with 404/no file/no counter mutation');
            smoke7_check(smoke7_http($anonymous,'/'.ltrim($row['file_path'],'/'))['status']===403,'PDF direct storage URL denied');unset($data['document_file']);
        }
        $data['id']=(string)$row['id'];$data[$case['field']]=$label.'_EDIT';$reply=smoke7_submit($editor,$path,$data);$check=$pdo->prepare('SELECT * FROM `'.$case['table'].'` WHERE id=?');$check->execute([$row['id']]);$edited=$check->fetch();smoke7_check($reply['status']===200&&$edited[$case['field']]===$label.'_EDIT','UPDATE editor same record: '.$module);smoke7_check(str_contains(smoke7_http($anonymous,$case['public'])['body'],$label.'_EDIT'),'UPDATE public visible: '.$module);
        smoke7_check(smoke7_http($super,$path.'?edit='.$row['id'].'abc')['status']===400,'Malformed admin ID rejected: '.$module);
        $reply=smoke7_submit($super,$path,['delete_id'=>(string)$row['id']]);$check->execute([$row['id']]);smoke7_check($reply['status']===200&&$check->fetch()===false,'DELETE database record: '.$module);
        foreach(['image_path','file_path','photo_path']as$key){$file=ltrim($row[$key]??'','/');if(str_starts_with($file,'public/uploads/')||str_starts_with($file,'storage/uploads/')){clearstatcache();smoke7_check(!is_file($site.'/'.$file),'DELETE file cleanup: '.$module);}}
    }
    smoke7_check(smoke7_http($anonymous,'/admin/db_backup.php',['backup_type'=>'sql'])['status']===302,'Anonymous backup denied');
    foreach([$editor,$ppdb]as$client){$token=smoke7_token($client,'/admin/change_password.php');smoke7_check(smoke7_http($client,'/admin/db_backup.php',['backup_type'=>'sql','csrf_token'=>$token])['status']===403,'Non-superadmin backup POST denied');}
    $r=smoke7_http($super,'/admin/db_backup.php',['backup_type'=>'sql']);smoke7_check($r['status']===200&&!isset($r['headers']['content-disposition']),'Superadmin backup missing CSRF denied');
    foreach(['sql','full']as$type){$r=smoke7_submit($super,'/admin/db_backup.php',['backup_type'=>$type]);smoke7_check($r['status']===200&&isset($r['headers']['content-disposition']),'Authorized backup download: '.$type);smoke7_check($type==='sql'?str_contains($r['body'],'CREATE TABLE'):str_starts_with($r['body'],'PK'),'Backup payload type: '.$type);}
    smoke7_check(count(smoke7_files($site.'/storage/backups/.temporary'))===0,'Temporary backup cleanup');
    foreach($clients as$role=>$client){$cookie=null;foreach(file($client['jar'],FILE_IGNORE_NEW_LINES)?:[]as$line){$parts=explode("\t",$line);if(count($parts)===7&&$parts[5]==='PHPSESSID'){$cookie=$parts[6];}}
        smoke7_check(is_string($cookie)&&$cookie!=='','Session available before logout: '.$role);
        $token=smoke7_token($client,'/admin/change_password.php');$r=smoke7_http($client,'/admin/logout.php',['csrf_token'=>$token]);
        smoke7_check($r['status']===302,'Logout POST: '.$role);smoke7_check(smoke7_http($client,'/admin/dashboard.php')['status']===302,'Direct dashboard after logout denied: '.$role);smoke7_check(smoke7_http($anonymous,'/admin/dashboard.php',null,$cookie)['status']===302,'Old session replay denied: '.$role);}
    }
    foreach($hashes as$path=>$hash){smoke7_check(hash_file('sha256',$site.'/'.$path)===$hash,'Runtime source unchanged from ZIP: '.$path);}
    foreach($schema as$table=>$definition){smoke7_check(smoke7_schema($pdo,$table)===$definition,'No runtime schema mutation: '.$table);}
    smoke7_check(hash_file('sha256',$zipPath)===$receipt['zip_sha256'],'Original release ZIP unchanged');
}catch(Throwable$e){$failure=['type'=>get_class($e),'line'=>$e->getLine(),'details'=>'Suppressed; inspect assertion metadata, not raw credentials/errors.'];}
finally{
    if(is_resource($process)){proc_terminate($process);for($i=0;$i<50&&proc_get_status($process)['running'];$i++){usleep(100000);}if(proc_get_status($process)['running']){proc_terminate($process,9);}proc_close($process);$process=null;}
    if($pdo){try{
        // Delete only rows owned by this run; the verified baseline must not be replaced.
        foreach($owned as$row){$pdo->prepare('DELETE FROM `'.$row['table'].'` WHERE id=? AND LEFT(`'.$row['field'].'`,20)=?')->execute([$row['id'],'TEST_TAHAP7_RELEASE_']);}
        foreach($accounts as$account){$pdo->prepare('DELETE FROM admin_activity_logs WHERE username=?')->execute([$account['username']]);$pdo->prepare('DELETE FROM admins WHERE id=? AND username=?')->execute([$account['id'],$account['username']]);}
        $clean=true;foreach($baseline as$table=>$count){if((int)$pdo->query('SELECT COUNT(*) FROM `'.$table.'`')->fetchColumn()!==$count){$clean=false;}}
    }catch(Throwable$e){$clean=false;}}
    if($site&&is_file($site.'/.env')){unlink($site.'/.env');}
    foreach([$run.'/tls-test-cert.pem',$run.'/tls-test-key.pem']as$tlsFile){if($run&&is_file($tlsFile)){unlink($tlsFile);}}
    if($run){foreach(smoke7_files($run)as$file){if(str_ends_with($file,'.cookies')||str_contains($file,'/sessions/')||str_contains($file,'/storage/sessions/')||str_contains($file,'/public/uploads/')&&!str_ends_with($file,'/.htaccess')||str_contains($file,'/storage/uploads/')||preg_match('~/fixture\.(png|pdf)$~',$file)){unlink($file);}}}
}
$failed=array_values(array_filter($checks,fn($check)=>!$check['passed']));$summary=['status'=>$failure||!$clean?'FAIL':'PASS','checks'=>count($checks),'failed'=>$failed,'failure'=>$failure,'database_cleanup'=>$clean,'apache_stopped'=>$process===null,'environment_removed'=>$site?!is_file($site.'/.env'):null,'evidence'=>$run,'bundle_source_files'=>count($hashes),'configuration'=>'Explicit local testing environment; restricted verified loopback database; no source connection substitutions','database'=>'website_sekolah_test_stage3 on isolated port 33317','results'=>$checks];
if($run){file_put_contents($run.'/result.json',json_encode($summary,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));}
echo json_encode($summary,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;exit($summary['status']==='PASS'?0:1);
