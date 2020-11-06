<?php
/**
 * 
 */
$fabrica = 1;

include __DIR__ . '/../../dbconfig.php';
include __DIR__ . '/../../includes/dbconnect-inc.php';
require __DIR__ . '/../funcoes.php';
include_once '/www/assist/www/class/aws/s3_config.php';

include_once '/www/assist/www/class/aws/anexaS3.class.php';

$s3ve  = new anexaS3('ve', $fabrica);

// $phpCron = new PHPCron($fabrica, __FILE__); 
// $phpCron->inicio();

//if (strlen($_POST['produtos']) > 0) $produtos = strtoupper($_POST['produtos']);
if(!empty($argv[1])) {
	$produtos = $argv[1];
	$cond = " AND produto in ({$produtos}) ";
}
//$produtos = '204604';
//$produtos = '241221';
	//Achar a Linha dos Produtos.
	$sql = "SELECT DISTINCT tbl_familia.familia
				FROM tbl_comunicado
				JOIN tbl_produto USING(produto)
				JOIN tbl_familia on tbl_familia.familia = tbl_produto.familia
				WHERE tbl_comunicado.fabrica = $fabrica
				AND tbl_comunicado.extensao IS NOT NULL
				AND tbl_comunicado.ativo = 't'
				AND tbl_comunicado.data > CURRENT_TIMESTAMP - interval '10 days'
				AND tbl_comunicado.tipo = 'Vista Explodida'
				$cond
				union
				SELECT DISTINCT tbl_familia.familia
				FROM tbl_comunicado
				JOIN tbl_comunicado_produto USING(comunicado)
				JOIN tbl_produto ON tbl_comunicado_produto.produto = tbl_produto.produto
				JOIN tbl_familia on tbl_familia.familia = tbl_produto.familia
				WHERE tbl_comunicado.fabrica = $fabrica
				AND tbl_comunicado.extensao IS NOT NULL
				AND tbl_comunicado.ativo = 't'
				AND tbl_comunicado.data > CURRENT_TIMESTAMP - interval '10 days'
				AND tbl_comunicado.tipo = 'Vista Explodida';";
	$res = pg_query($con,$sql);
	if (pg_num_rows($res) > 0) {
		$familias = implode(",", pg_fetch_all_columns($res));

		//pegar todos os comunicados dos produtos das familias;
		$sql = "SELECT DISTINCT tbl_familia.familia, comunicado, tbl_produto.referencia, extensao
				FROM tbl_comunicado
				JOIN tbl_produto USING(produto)
				JOIN tbl_familia on tbl_familia.familia = tbl_produto.familia
				WHERE tbl_comunicado.fabrica = $fabrica
				AND tbl_familia.familia in ($familias)
				AND tbl_comunicado.extensao IS NOT NULL
				AND tbl_comunicado.ativo = 't'
				AND tbl_comunicado.tipo = 'Vista Explodida'
				union
				SELECT DISTINCT tbl_familia.familia, comunicado, tbl_produto.referencia,  extensao
				FROM tbl_comunicado
				JOIN tbl_comunicado_produto USING(comunicado)
				JOIN tbl_produto ON tbl_comunicado_produto.produto = tbl_produto.produto
				JOIN tbl_familia on tbl_familia.familia = tbl_produto.familia
				WHERE tbl_comunicado.fabrica = $fabrica
				AND tbl_familia.familia in ($familias)
				AND tbl_comunicado.extensao IS NOT NULL
				AND tbl_comunicado.ativo = 't'
				AND tbl_comunicado.tipo = 'Vista Explodida' ";
		$query = pg_query($con,$sql);
		//echo $sql."\n";

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
		//print_r( $comunicados);die;

		//Limpa a pasta e os arquivos
		$tmp_dir = __DIR__ . '/zip_vista';

		//verificar se existe e limpa , se não cria
		if (is_dir($tmp_dir)) {
			chdir($tmp_dir);
			$glob_arquivos = glob('*');
			foreach( $glob_arquivos as $f)
				unlink ($f);
			chdir ( __DIR__ );
	            //rmdir($tmp_dir);	
		}else{
			mkdir($tmp_dir,0777,true);
		}	
		

		//
		foreach ($comunicados as $familia => $vistas) {
			// echo "\n";
			// echo $familia." - ".$vistas;
			// echo "\n";
			foreach ($vistas as $com) {
				//echo "\nBaixando anexo comunicado {$com['comunicado']}\n URL: ";

				$referencia  = str_replace(" ", "_",  $com['referencia']);
				if ($s3ve->temAnexos($com['comunicado']) ) {
					// O nome do arquivo deve ser a referencia do prod.
					$fn = str_replace(' ', '_', $comunicado['referencia']) . '.' . $comunicado['extensao'];

					file_put_contents(
						"zip_vista/$fn", 
						file_get_contents($s3ve->url)
					);
				}
			}

			if (count(glob("zip_vista/*"))) {
				// Black vai pedir o nome da familia... :P
				$zipfile = "zip_vista/ve_001_$familia.zip";
				system("zip $zipfile zip_vista/* > /dev/null");

				if (is_readable($zipfile)) {
					$s3ve->set_tipo_anexoS3("ve_familia");
					$uploadOK = $s3ve->uploadFileS3($familia, $zipfile);
					$s3ve->set_tipo_anexoS3('ve'); // volta para reutilizar
				}
				system("rm $zipfile");	
				system("rm zip_vista/*");	
				// if (is_dir($tmp_dir)) {
				// 	chdir($tmp_dir);
				// 	$glob_arquivos = glob('*');
				// 	foreach( $glob_arquivos as $f)
				// 		unlink ($f);
				// 	chdir ( __DIR__ );
				// 	//rmdir($tmp_dir);	
				// }			
			}
		}
		// if (is_dir($tmp_dir)) {
		// 	chdir($tmp_dir);
		// 	$glob_arquivos = glob('*');
		// 	foreach( $glob_arquivos as $f)
		// 		unlink ($f);
		// 	chdir ( __DIR__ );
		// 	rmdir($tmp_dir);	
		// }		
	}

// $phpCron->termino();
