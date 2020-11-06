<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$os     = $_POST["os"];
$motivo = utf8_decode($_POST["motivo"]);

$sql = "SELECT 
			sua_os, 
			posto, 
			DATE_PART('MONTH', data_abertura) AS mes_os, 
			DATE_PART('YEAR', data_abertura) AS ano_os 
		FROM tbl_os 
		WHERE fabrica = {$login_fabrica} 
		AND os = {$os}";
$res = pg_query($con, $sql);

if (pg_num_rows($res) > 0) {
	$sua_os = pg_fetch_result($res, $i, "sua_os");
	$posto  = pg_fetch_result($res, $i, "posto");
	$mes_os = pg_fetch_result($res, $i, "mes_os");
	$ano_os = pg_fetch_result($res, $i, "ano_os");

	if ($_POST["aprova"]) {
		$sql = "INSERT INTO tbl_os_status (os, status_os, observacao,admin) VALUES ({$os}, 191, 'NF aprovada na auditoria de nota fiscal',$login_admin)";
		$res = pg_query($con, $sql);

		if (!strlen(pg_last_error())) {
			$retorno = array("ok" => true);
		} else {
			$retorno = array("erro" => utf8_encode("Erro ao aprovar NF da OS {$sua_os}"));
		}
	} else if ($_POST["recusa"]) {
		$sql = "INSERT INTO tbl_os_status (os, status_os, observacao,admin) VALUES ({$os}, 190, 'NF recusada na auditoria de nota fiscal - motivo: $motivo',$login_admin)";
		$res = pg_query($con, $sql);

		if (!strlen(pg_last_error())) {
			$sql = "INSERT INTO tbl_comunicado (
						fabrica,
						posto,
						obrigatorio_site,
						tipo,
						ativo,
						descricao,
						mensagem
					) VALUES (
						{$login_fabrica},
						{$posto},
						true,
						'Com. Unico Posto',
						true,
						'Nota Fiscal recusada da OS {$sua_os}',
						'{$motivo}'
					)";
			$res = pg_query($con, $sql);

			if($login_fabrica != 6){

				include_once 'class/aws/s3_config.php';
				include_once S3CLASS;

				$s3 = new AmazonTC('os', (int) $login_fabrica);

				$arquivos = $s3->getObjectList("{$os}.", false, $ano_os, $mes_os);
				
				foreach ($arquivos as $arquivo) {
					$arquivo = preg_replace("/.+\//", "", $arquivo);

					$s3->deleteObject($arquivo, false, $ano_os, $mes_os);	
				}

			}
			$retorno = array("ok" => true);
		} else {
			$retorno = array("erro" => utf8_encode("Erro ao recusar NF da OS {$sua_os}"));
		}
	} else {
		$retorno = array("erro" => utf8_encode("Sem ação selecionada para a OS {$sua_os}"));
	}
} else {
	$retorno = array("erro" => utf8_encode("OS não encontrada"));
}

exit(json_encode($retorno));

?>
