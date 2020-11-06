<?php

if($login_fabrica == 1){
	$complemento_black = " AND tbl_posto_bloqueio.pedido_faturado is false ";
}

if ($login_fabrica == 24 ) {
	$complemento_black = " AND tbl_posto_bloqueio.observacao <> 'Extrato com mais de 60 dias sem fechamento' ";
}

$sql = "SELECT posto
			FROM tbl_posto_bloqueio
			WHERE posto = $login_posto
			AND fabrica = $login_fabrica
			AND desbloqueio IS FALSE
			$complemento_black
			ORDER BY data_input DESC
			LIMIT 1";
$res = pg_query($con,$sql);

if(pg_num_rows($res) > 0){

	$sql = "SELECT tbl_os.os
			FROM tbl_os
			JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os AND tbl_os_campo_extra.fabrica = $login_fabrica
			WHERE tbl_os.fabrica = $login_fabrica
			AND tbl_os.posto = $login_posto
			AND tbl_os.excluida IS NOT TRUE
			AND tbl_os.finalizada IS NULL
			AND tbl_os.data_fechamento IS NULL
			AND tbl_os.data_conserto IS NULL
			AND tbl_os.cancelada is not true
			AND tbl_os_campo_extra.os_bloqueada IS TRUE";
	$res = pg_query($con,$sql);

	if(pg_num_rows($res) > 0){

		$endereco_procon = $_SERVER['REQUEST_URI'];
		$redireciona_procon = true;

		if((strpos($endereco_procon,'menu_os.php' ) !== false) OR (strpos($endereco_procon,'os_item_new.php' ) !== false) OR (strpos($endereco_procon,'os_motivo_atraso.php') !== false) ){
			$redireciona_procon = false;			
		}elseif (strpos($endereco_procon,'os_fechamento.php') !== false) {
			$redireciona_procon = false;
		}elseif (strpos($endereco_procon,'os_press.php') !== false) {
			$redireciona_procon = false;
		}else{
			$redireciona_procon = true;
		}
		
		if ($redireciona_procon == true) {
			header("Location: menu_os.php");
		}

	}else{

		$sql = "UPDATE tbl_posto_bloqueio SET desbloqueio = TRUE WHERE posto = $login_posto AND fabrica = $login_fabrica";
		$res = pg_query($con,$sql);

	}
}

