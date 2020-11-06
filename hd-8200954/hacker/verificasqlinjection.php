<?php
//******************* CONFIG ****************//

// pagina que sera impessa quando a tentativa de invasao for detectada
$page = "http://posvenda.telecontrol.com.br/assist/hacker/injection.html";

//******************* CONFIG ****************//

//
// Versao 2.5 testada em ambos servers ....
//
// Inicio tracker
//


  $arquivo = $_SERVER['SCRIPT_FILENAME'];
  $cracktrack = urlencode ($_SERVER['QUERY_STRING'] . array_implode( '=', '&', $_POST ) );

  $cracktrack = str_replace('+' , '%20' , $cracktrack);
  $wormprotector = array('chr(', 'chr=', 'chr%20', '%20chr', 'wget%20', '%20wget', 'wget(',
		   'cmd=', '%20cmd', 'cmd%20', 'rush=', '%20rush', 'rush%20',
                   'union%20', '%20union', 'union(', 'union=', 'echr(', '%20echr', 'echr%20', 'echr=',
                   'esystem(', 'esystem%20', 'cp%20', '%20cp', 'cp(', 'mdir%20', '%20mdir', 'mdir(',
                   'mcd%20', 'mrd%20', 'rm%20', '%20mcd', '%20mrd', '%20rm',
                   'mcd(', 'mrd(', 'rm(', 'mcd=', 'mrd=', 'mv%20', 'rmdir%20', 'mv(', 'rmdir(',
                   'chmod(', 'chmod%20', '%20chmod', 'chmod(', 'chmod=', 'chown%20', 'chgrp%20', 'chown(', 'chgrp(',
                   'locate%20', 'grep%20', 'locate(', 'grep(', 'diff%20', 'kill%20', 'kill(', 'killall',
                   'passwd%20', '%20passwd', 'passwd(', 'telnet%20', 'vi(', 'vi%20',
                   'insert%20into', 'select%20', 'nigga(', '%20nigga', 'nigga%20', 'fopen', 'fwrite', '%20like', 'like%20',
                   '$_request', '$_get', '$request', '$get', '.system', 'HTTP_PHP', '&aim', '%20getenv', 'getenv%20',
                   'new_password', '&icq','/etc/password','/etc/shadow', '/etc/groups', '/etc/gshadow',
                   'HTTP_USER_AGENT', 'HTTP_HOST', '/bin/ps', 'wget%20', 'uname\x20-a', '/usr/bin/id',
                   '/bin/echo', '/bin/kill', '/bin/', '/chgrp', '/chown', '/usr/bin', 'g\+\+', 'bin/python',
                   'bin/tclsh', 'bin/nasm', 'perl%20', 'traceroute%20', 'ping%20', '.pl', '/usr/X11R6/bin/xterm', 'lsof%20',
                   '/bin/mail', '.conf', 'motd%20', 'HTTP/1.', '.inc.php', 'config.php', 'cgi-', '.eml',
                   'file\://', 'window.open', '<SCRIPT>', 'javascript\://','img src', 'img%20src','.jsp','ftp.exe',
                   'xp_enumdsn', 'xp_availablemedia', 'xp_filelist', 'xp_cmdshell', 'nc.exe', '.htpasswd',
                   'servlet', '/etc/passwd', 'wwwacl', '~root', '~ftp', '.js', '.jsp', 'admin_', '.history',
                   'bash_history', '.bash_history', '~nobody', 'server-info', 'server-status', 'reboot%20', 'halt%20',
                   'powerdown%20', '/home/ftp', '/home/www', 'secure_site, ok', 'chunked', 'org.apache', '/servlet/con',
                   '<script', '/robot.txt' ,'/perl' ,'mod_gzip_status', 'db_mysql.inc', '.inc', 'select%20from',
                   'select from', 'drop%20', '.system', 'getenv', 'http_', '_php', 'php_', 'phpinfo()', '<?php', '?>', 'sql=');

    //--- Tulio --- 2012-03-05 - retirei string 'admin_'
    //--- Tulio --- 2012-03-05 - retirei string 'rm%20'
    //--- Tulio --- 2012-03-05 - retirei string '20%rm'
    //--- Tulio --- 2012-03-05 - retirei string 'vi%20'
    // Brayan - Retirei ping%20 - 21/03/2012
    $wormprotector = array('chr(', 'chr=', 'chr%20', '%20chr', 'wget%20', '%20wget', 'wget(',
		'cmd=', '%20cmd', 'cmd%20', 'rush=', '%20rush', 'rush%20',
		'union%20', '%20union', 'union(', 'union=', 'echr(', '%20echr', 'echr%20', 'echr=',
		'esystem(', 'esystem%20', 'cp%20', '%20cp', 'cp(', 'mdir%20', '%20mdir', 'mdir(',
		'mcd%20', 'mrd%20', '%20mcd', '%20mrd',
		'mcd(', 'mrd(', 'rm(', 'mcd=', 'mrd=', 'mv%20', 'rmdir%20', 'mv(', 'rmdir(',
		'chmod(', 'chmod%20', '%20chmod', 'chmod(', 'chmod=', 'chown%20', 'chgrp%20', 'chown(', 'chgrp(',
		'locate%20', 'grep%20', 'locate(', 'grep(', 'diff%20', 'kill%20', 'kill(', 'killall',
		'passwd%20', '%20passwd', 'passwd(', 'telnet%20', 'vi(',
		'insert%20into', 'select%20', 'nigga(', '%20nigga', 'nigga%20', 'fopen', 'fwrite', '%20like', 'like%20',
		'$_request', '$_get', '$request', '$get', '.system', 'HTTP_PHP', '&aim', '%20getenv', 'getenv%20',
		'new_password', '&icq','/etc/password','/etc/shadow', '/etc/groups', '/etc/gshadow',
		'HTTP_USER_AGENT', 'HTTP_HOST', '/bin/ps', 'wget%20', 'unamex20-a', '/usr/bin/id',
		'/bin/echo', '/bin/kill', '/bin/', '/chgrp', '/chown', '/usr/bin', 'g++', 'bin/python',
		'bin/tclsh', 'bin/nasm', 'perl%20', 'traceroute%20', '.pl', '/usr/X11R6/bin/xterm', 'lsof%20',
		'/bin/mail', '.conf', 'motd%20', 'HTTP/1.', '.inc.php', 'config.php', 'cgi-', '.eml',
		'file://', 'window.open', '<script>', 'java script://','img src', 'img%20src','.jsp','ftp.exe',
		'xp_enumdsn', 'xp_availablemedia', 'xp_filelist', 'xp_cmdshell', 'nc.exe', '.htpasswd',
		'servlet', '/etc/passwd', 'wwwacl', '~root', '~ftp', '.js', '.jsp', '.history',
		'bash_history', '.bash_history', '~nobody', 'server-info', 'server-status', 'reboot%20', 'halt%20',
		'powerdown%20', '/home/ftp', '/home/www', 'secure_site, ok', 'chunked', 'org.apache', '/servlet/con',
		'<script', '/robot.txt' ,'/perl' ,'mod_gzip_status', 'db_mysql.inc', '.inc.', 'select%20from',
		'select from', 'drop%20', '.system', 'getenv', 'http_', '_php', 'php_', 'phpinfo()', '<?php', '?>', 'sql=');

  $checkworm = str_replace($wormprotector, '*********', $cracktrack);

 
  if ($cracktrack != $checkworm)
    {
      $cremotead = $_SERVER['REMOTE_ADDR'];
      $cuseragent = $_SERVER['HTTP_USER_AGENT'];

      date_default_timezone_set('America/Sao_Paulo');
      $data = date('d/m/Y H:i:s');
	
      $log = "========================================================================================================================\n";
      $log.= "   Arquivo: $arquivo\n";
      $log.= "   Data: $data\n   IP: $cremotead\n   User agent: $cuseragent\n";
      $log.= "------------------------------------------------------------------------------------------------------------------------\n\n";

      $decoded_track = urldecode($cracktrack);
      $decoded_worm = urldecode($checkworm);
      $post_orig = explode("&", $decoded_track);
      $post_post = explode("&", $decoded_worm);

      foreach ($post_orig as $k => $val) {
	$pos = strpos($post_post[$k], '*********');
	if ($pos !== false) {
		$log.= "   -> Enviado: \n";
		$arr_tmp = explode("=", $val);
		$log.= '   Campo: ' . $arr_tmp[0] . "\n";
		$log.= '   Dados: ' . $arr_tmp[1] . "\n\n----------------------------------------\n\n";
        	$log.= "   -> Filtrado: \n";
		$arr_tmp = explode("=", $post_post[$k]);
		$log.= '   Campo: ' . $arr_tmp[0] . "\n";
		$log.= '   Dados: ' . $arr_tmp[1] . "\n\n----------------------------------------\n\n";
	}
      }

      $log.= "\n";

      system ("echo '" . $log . "' | qmail-injection tulio@telecontrol.com.br");
      system ("echo '" . $log . "' >> /tmp/hacker.log");

      header("location:$page");
      die();
    }


function array_implode( $glue, $separator, $array ) {
    if ( ! is_array( $array ) ) return $array;
    $string = array();
    foreach ( $array as $key => $val ) {
	if ( is_array( $val ) ) {
	    $val = implode( ',', $val );
	}
        $string[] = "{$key}{$glue}{$val}";
    }
    return implode( $separator, $string );
}
?>
