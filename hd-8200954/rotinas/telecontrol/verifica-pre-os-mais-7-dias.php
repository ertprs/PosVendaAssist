<?php

error_reporting(E_ALL ^ E_NOTICE);

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../../class/log/log.class.php';

$log = new Log();
$log->adicionaLog(array("titulo" => "Log de Erro - arquivo verifica-pre-os-mais-7-dias.php"));
$log->adicionaTituloEmail("Log de Erro - arquivo verifica-pre-os-mais-7-dias.php");
$log->adicionaEmail("helpdesk@telecontrol.com.br");

try {

	$fabricas 	= array(136,137,139,140,141,144,160,161,162,163,164,165,167,169,170,174,175,177,186,191,193);
	$admins 	= array(
        "140" => "sistema",
        "141" => "sistema2",
        "144" => "sistema1",
        "165" => "sistema",
        "169" => "sistema_midea",
        "170" => 'sistema_carrier',
        "186" => "sistema_MQ"
    );

	for ($j = 0; $j < count($fabricas); $j++) {

		$fabrica = $fabricas[$j];

		$qtde_dias = 7;
		$cond_posto_interno = "";
		$left_posto_interno = "";

		if(in_array($fabrica, array(136))){
			$qtde_dias = 10;
		}

        if(in_array($fabrica, [174,186])){
            $qtde_dias = 15;
        }

		if(in_array($fabrica, array(141,144,191))){
			$qtde_dias = 20;
		}

		if(in_array($fabrica, array(165,175))){
            $qtde_dias = 30;
        }

		if(in_array($fabrica, array(164))){

			$left_posto_interno = " LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_hd_chamado_extra.posto AND tbl_posto_fabrica.fabrica = {$fabrica} ";

			$cond_posto_interno = "
				AND tbl_posto_fabrica.tipo_posto NOT IN (
					SELECT tipo_posto FROM tbl_tipo_posto WHERE fabrica = {$fabrica} AND posto_interno IS TRUE
				)
			";

		}

		$sql = "SELECT DISTINCT
				tbl_hd_chamado.hd_chamado
	        FROM tbl_hd_chamado
            LEFT JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
            LEFT JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
            {$left_posto_interno}
            WHERE tbl_hd_chamado.fabrica = $fabrica
            AND tbl_hd_chamado_extra.abre_os = 't'
            AND tbl_hd_chamado_item.os IS NULL
            AND tbl_hd_chamado_extra.os IS NULL
            AND current_date - INTERVAL '$qtde_dias DAYS' > tbl_hd_chamado.data
            AND tbl_hd_chamado.status != 'Cancelado'
		    {$cond_posto_interno}
        ";

        $res = pg_query($con, $sql);
		$msg_erro = pg_num_rows($con);

		if(!empty($msg_erro)){
			$log->adicionaLog("Erro ao selecionar os HDs Chamados abertos a mais de 7 dias - ".$msg_erro);
		}

		if(empty($msg_erro)){

			for ($i = 0; $i < pg_num_rows($res); $i++) {

				$hd_chamado = pg_fetch_result($res, $i, 'hd_chamado');
				$login		= $admins[$fabrica];

				$sql_admin = "SELECT admin FROM tbl_admin WHERE login = '$login' AND fabrica = $fabrica";
				$res_admin = pg_query($con, $sql_admin);
				$msg_erro = pg_num_rows($con);

				if(!empty($msg_erro)){
					$log->adicionaLog("Erro ao selecionar o Admin - ".$msg_erro);
				}

				if(empty($msg_erro)){

					if(pg_num_rows($res_admin) > 0){

						$admin = pg_fetch_result($res_admin, 0, 'admin');
						$comentario = "Atendimento Finalizado automaticamente após {$qtde_dias} dias sem a abertura da Pré-OS pelo Posto.";
						$status = 'Resolvido';
						
						pg_query($con, 'BEGIN');
						
						if (in_array($fabrica, array(169,170))) {
							$sql_status = "UPDATE tbl_hd_chamado_extra SET abre_os = FALSE where hd_chamado = {$hd_chamado};";
							$res_status = pg_query($con, $sql_status);
						} else {
							$sql_interacao = "INSERT INTO tbl_hd_chamado_item (hd_chamado, data, comentario, admin, status_item) VALUES ($hd_chamado, current_timestamp, '$comentario', $admin, 'Resolvido')";
							$res_interacao = pg_query($con, $sql_interacao);
							$msg_erro = pg_num_rows($con);

							$sql_status = "UPDATE tbl_hd_chamado SET status = '$status' WHERE hd_chamado = $hd_chamado AND fabrica = $fabrica ; UPDATE tbl_hd_chamado_extra SET abre_os = FALSE where hd_chamado = $hd_chamado ; ";
							$res_status = pg_query($con, $sql_status);
							$msg_erro = pg_num_rows($con);
						}

						if(!empty($msg_erro)) {
							pg_query($con, 'ROLLBACK');
						}else{
							pg_query($con, 'COMMIT');
						}
					}

				}

			}

		}

	}

	if(!empty($msg_erro)){
		$log->enviaEmails();
	}

}catch (Exception $e) {

    $msg_erro = $e->getMessage();
    $log->adicionaLog("Erro ao selecionar o Admin - ".$msg_erro);
    $log->enviaEmails();

}

?>
