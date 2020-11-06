<?php
// define('DEBUG', true); // Mostra logs e erros durante o processo de upload
$fabrica = 1;

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'funcoes.php';
include_once '../class/aws/s3_config.php';
include_once S3CLASS;

$s3ve  = new anexaS3('ve', $fabrica);

if (strlen($_GET['produtos']) > 0) $produtos = strtoupper($_GET['produtos']);

if (!empty($produtos)) {
	//Achar a Linha dos Produtos.
	$sql = "SELECT familia FROM tbl_produto WHERE fabrica_i = {$fabrica} AND produto in ({$produtos});";
	$res = pg_query($con,$sql);
	if (pg_num_rows($res) > 0) {
		$familias = implode(",", pg_fetch_all_columns($res));

		//pegar todos os comunicados dos produtos das familias;
		$sql = "SELECT DISTINCT tbl_familia.familia, comunicado, extensao
				  FROM tbl_comunicado
				  JOIN tbl_produto USING(produto)
				  JOIN tbl_familia on tbl_familia.familia = tbl_produto.familia
				 WHERE tbl_comunicado.fabrica   = $fabrica
				   AND tbl_familia.familia     IN ($familias)
				   AND tbl_comunicado.extensao IS NOT NULL
				   AND tbl_comunicado.ativo     = 't'
				   AND tbl_comunicado.tipo      = 'Vista Explodida'";
		$query = pg_query($con,$sql);

		$comunicados = pg_fetch_all($query);

		if (strpos($familias, ',')) {
			foreach ($comunicados as $com) {
				$familia = $com['familia'];
				unset($com['familia']);
				$newcom[$familia][] = $com;
			}
		} else {
			$newcom[$familias] = $comunicados;
		}

		$comunicados = $newcom;
		unset($newcom);

		if ($_serverEnvironment == 'development') {
			define ('ZIPDIR', '/home/manuel/test/zip_vista/');
		} else {
			define ('ZIPDIR', __DIR__ . '/xls/zip_vista/');
		}

		//verificar se existe e limpa , se não cria
		if (is_dir(ZIPDIR)) {
			chdir(ZIPDIR);
			$glob_arquivos = glob('*');

			// Limpa a pasta e os arquivos
			// Se está no devel, não exclui os arquivos, para evitar
			// gastar tempo e banda (especialemtne no devel2).
			if (!$_serverEnvironment == 'development')
			foreach( $glob_arquivos as $f)
				unlink ($f);
			chdir ( __DIR__ );	    
		}else{
			if (!mkdir(ZIPDIR, 0777, true))
				die('ERRO');
		}	
		
		foreach ($comunicados as $familia => $vistas) {

			foreach ($vistas as $com) {

				$destPath = ZIPDIR . $fn;

				// if (!file_exists($destPath)) {
				if ($s3ve->temAnexos($com['comunicado']) ) {
					$fn = pathinfo($s3ve->attachList[0], PATHINFO_BASENAME);
					file_put_contents(
						$destPath,
						file_get_contents($s3ve->url)
					);
				}
					// pecho("Baixando arquivo $fn");
					// flush();
				// }
			}

			if (count(glob(ZIPDIR . "*"))) {
				// Black vai pedir o nome da familia... 
				$zipfile = ZIPDIR . "ve_001_$familia.zip";
				system("zip $zipfile " . ZIPDIR . '*');

				if (is_readable($zipfile)) {
					$s3ve->set_tipo_anexoS3("ve_familia");
					$uploadOK = $s3ve->uploadFileS3($familia, $zipfile);
					$s3ve->set_tipo_anexoS3('ve'); // volta para reutilizar
				}
			}
		}
	}
}

