<?php
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';

 /*
* Definição
*/
date_default_timezone_set('America/Sao_Paulo');
$fabrica = 157;
$data = date('d-m-Y');
pg_query($con,"BEGIN TRANSACTION");

$sqlSelect = "SELECT tbl_os.os 
		FROM tbl_os
		JOIN tbl_os_produto USING(os)
		LEFT JOIN tbl_os_item USING(os_produto)
		WHERE tbl_os.fabrica = $fabrica
		AND tbl_os.excluida IS NOT TRUE
		AND tbl_os.finalizada IS NULL
		AND CURRENT_DATE > tbl_os.data_abertura + INTERVAL '45 days'
		AND tbl_os_item.os_item IS NULL
";
$resSelect = pg_exec ($con,$sqlSelect);

for ($i = 0 ; $i < pg_numrows ($resSelect); $i++){
	$os = trim(pg_result ($resSelect,$i,os));

    $sqlUpOs = "UPDATE tbl_os  SET excluida = 't' WHERE os = {$os};";
    $resUpOs = pg_query($con,$sqlUpOs);
    $msg_erro  .= pg_last_error($con);
    
    $sqlInsStatus   = "INSERT INTO tbl_os_status (os,status_os,observacao) VALUES ({$os}, 15, 'Ordem de Serviço aberta a mais de 45 dias sem lançamento de peças') ;";
    $resInsStatus    = pg_query($con,$sqlInsStatus);
    $msg_erro  .= pg_last_error($resInsStatus);
}

if (strlen($msg_erro) > 0) {
    pg_query($con,"ROLLBACK TRANSACTION");
    echo json_encode(array("ERRO" => TRUE,"MSN"  => "Erro ao gravar: ". pg_last_error($con)."!",));
} else {
    pg_query($con,"COMMIT TRANSACTION");
    echo json_encode(array("OK"   => TRUE,"MSN"  => "Gravado com Sucesso!",));
}
exit;
