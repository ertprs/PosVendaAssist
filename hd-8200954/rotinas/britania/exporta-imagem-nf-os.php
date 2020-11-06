<?php
define ('DIR_ROTINAS', dirname(__DIR__).'/'); // [path_to_assist_dir/]assist/rotinas
define ('DIR_ASSIST',  dirname(DIR_ROTINAS).'/');
define('isRoot',       (posix_geteuid() == 0));

define('MAX_EXEC_TIME', 300); // tempo máximo de execução em segundos
define ('FTPTEST',      false);
//define('S3TEST',        '');

$tmp_dir = __DIR__ . '/tmp/'.uniqid();

function dev_echo($s) {
	if (FTPTEST === false or $GLOBALS['_serverEnvironment'] !== 'development')
		return true;
	echo trim($s) . PHP_EOL;
	return true;

}
function clean_up($tmp_dir) {
	// clean-up the mess...
	if (is_dir($tmp_dir)) {
		chdir($tmp_dir);
		foreach(glob('*') as $f)
			unlink ($f);
		chdir ( __DIR__ );
		rmdir($tmp_dir);
		rmdir(__DIR__.'/tmp');
	}
}

try {
	include_once DIR_ASSIST .  'dbconfig.php';
	include_once DIR_ASSIST .  'includes/dbconnect-inc.php';
	require_once DIR_ROTINAS . 'funcoes.php';
	require_once DIR_ASSIST .  'helpdesk/mlg_funciones.php';

	$fabrica = 3;
	$fabrica_nome = 'britania';
	// não processar OS anteriores à...
	$data_corte = '2016-12-02';
	$data_corte = is_date('hoje 6 meses antes');
	$data_corte = is_date('ontem');

	$sql = <<<SQL
	   SELECT os, sua_os
		 FROM tbl_os_extra
		 JOIN tbl_os USING(os)
		WHERE fabrica =  $fabrica
		  AND baixada IS NULL
		  AND data_abertura >= CURRENT_DATE - INTERVAL '1 MONTH'
		  -- AND data_abertura BETWEEN '$data_corte' AND CURRENT_DATE - INTERVAL '4 MONTHS'
	    ORDER BY data_digitacao
SQL;

	// Necessário para os S3 (AmazonTC e AnexaNF_S3)
	global $login_fabrica; $login_fabrica = $fabrica;

	dev_echo($sql);

	$ordens = pg_fetch_pairs($con, $sql);

	if (count($ordens)) {
		$c = 0;
		$ign = 0;
		$err = 0;
		$t = count($ord);
		$errmsg = array();
		$start_time = time(); // para estabelecer o tempo máximo de execução

		require_once DIR_ASSIST . 'class/aws/s3_config.php';

		if (!$S3_sdk_OK)
			throw new Exception('AWS3 não disponível!');

		require_once S3CLASS; // AmazonTC
		require_once DIR_ASSIST . 'anexaNF_inc.php';
		require_once DIR_ASSIST . 'class/ftp.class.php';

		if (!isRoot and isCLI===true)
			pre_echo($ordens, "Localizados ".count($ordens) . '. Processando.');

		// dados de acesso ao FTP
		// $ftpURL = 'pftp://suporte:tele6588@ftp.telecontrol.com.br/suporte';
		$ftpURL = "pftp://akacia:britania2009@telecontrol.britania.com.br/Entrada/Imagens";

		// Acessos externos
		$s3  = new AmazonTC('os', $fabrica);
		$ftp = new Ftp($ftpURL);

		$processedFiles = array(); // arquivos já processados, para não repetir (os revenda, p.e.)

		if (!$ftp->loggedIn)
			throw new Exception($ftp->error);

		if (!is_dir($tmp_dir)) {
			mkdir($tmp_dir, 0777, true);

			if (!is_dir($tmp_dir))
				throw new Exception ('Sem permissão para criar diretório temporário, ou sem espaço no dispositivo.');
		}

		// Verifica, para cada OS, se têm anexo de OS, e se é jpg ou png...
		foreach ($ordens as $os=>$sua_os) {
			// tempo máximo
			if ((time() - $start_time) > MAX_EXEC_TIME) {
				if (!isRoot) {
					echo "TIME's UP!";
				}
				echo "[Exporta anexo OS=>FTP Britania] Enviados $c de $t arquivos.\n";
				break; // sai do foreach
			}

			dev_echo("Anexo para a OS $os...");

			//verifica se tem imagem na OS no AmazonTC
			$s3->getObjectList("anexo_os_{$fabrica}_{$os}_img_os_1", false);

			if (FTPTEST===true)
				pre_echo($s3->files, 'S3TC');

			if ($s3->files[0]) {
				$s3_file = $s3->files[0];

				$ext = pathinfo(parse_url($s3_file, PHP_URL_PATH), PATHINFO_EXTENSION);
				$dest_file = "$sua_os.$ext";
				$tmp_file  = "$tmp_dir/$dest_file";

				if (FTPTEST===true)
					pre_echo(compact('s3_file', 'tmp_file', 'dest_file'), 'FROM S3TC TO FTP');
				// continue;

				// copia o arquivo do S3 para o localfs
				$s3->get_object(
					$s3->bucket,
					$s3_file,
					array('fileDownload'=>$tmp_file)
				);
			} else {
				$anexos = temNF($os, 'url');
				$s3_file = is_array($anexos) ? reset($anexos) : $anexos;

				if (!$s3_file) {
					$ign++;
					continue;
				}

				$ext = pathinfo(parse_url($s3_file, PHP_URL_PATH), PATHINFO_EXTENSION);
				$dest_file = "$sua_os.$ext";
				$tmp_file  = "$tmp_dir/$dest_file";

				if (FTPTEST===true)
					pre_echo(compact('s3_file', 'tmp_file', 'dest_file'), 'FROM TDOCS TO FTP');
				// continue;

				if ($fileData=file_get_contents($s3_file))
					file_put_contents($tmp_file, $fileData);
				else {
					$err++;
					$errmsg[] = "Erro ao ler o arquivo de anexo da OS $sua_os.";
					continue;
				}
			}

			// subindo o arquivo para o FTP do cliente
			if ($ftp->put($tmp_file, $dest_file)) {
				pg_query(
					$con,
					"UPDATE tbl_os_extra
					    SET baixada = CURRENT_TIMESTAMP
					  WHERE os = $os"
				);
				$c++;
			} else {
				$err++;
				$errmsg[] = $ftp->error . ", Erro ao subir o anexo da OS $sua_os.";
			}
		}
		clean_up($tmp_dir);

		if (count($errmsg))
			echo is_date('agora') . " - ERROS DETECTADOS: $err\n" . implode(PHP_EOL, $errmsg) . "\n\n";
	}

} catch (Exception $e) {
	$msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();

	clean_up($tmp_dir);

	if (isCLI and !$isRoot)
		die($msg.PHP_EOL);
	Log::envia_email($vet,APP, $msg );
}

