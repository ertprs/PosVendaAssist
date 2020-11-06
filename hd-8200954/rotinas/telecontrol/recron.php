<?php
/**
 *
 * recron.php
 *
 * @author  Francisco Ambrozio
 * @version 2012.03
 *
 */

try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require_once dirname(__FILE__) . '/../funcoes.php';
	require_once dirname(__FILE__) . '/../../helpdesk/monitoracron.inc.php';

	$login_fabrica = 10;

	$monitoraCron = new MonitoraCron;

	if ($monitoraCron->isFileWorkFile() == 1) {
		throw new Exception('Arquivo não encontrado');
	}

	$arquivo = $monitoraCron->getWorkfile();

	$conteudo = file_get_contents($arquivo);
	$perls = explode("\n", $conteudo);
	$removido = $conteudo;

	foreach ($perls as $p) {
		if (!empty($p)) {
			$query = pg_query($con, "SELECT programa FROM tbl_perl WHERE perl = $p");

			if (pg_num_rows($query) == 0) {
				continue;
			}

			$programa = pg_fetch_result($query, 0, 'programa');

			echo "Rodar $programa\n";
			$sql = "UPDATE tbl_perl_processado SET fim_processo = current_timestamp, log = 'Reprocessado'
					WHERE perl = $p AND inicio_processo > current_date AND fim_processo is null";
			//system($programa, $retorno);
			$retorno = 0;

			if ($retorno == 0) {
				//$update = pg_query($con, $sql);
				$removido = str_replace("$p\n", "", $removido);

				$f = fopen($arquivo, 'w');
				fwrite($f, $removido);
				fclose($f);
			}
		}
	}


	$restou = str_replace("\n", "", $removido);
	if (empty($restou)) {
		unlink($arquivo);
	}

} catch (Exception $e) {

    echo $e->getMessage() , "\n";

}

