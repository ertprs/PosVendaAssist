<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["gerar_relatorio_detalhe_csv"]) {

	$dados = json_decode($_POST["request"], true);

	$dias   			= $dados["dias"];
	$data_inicial       = $dados['data_inicial'];
	$data_final         = $dados['data_final'];
	$tipo_atendimento 	= $dados['tipo_atendimento'];
	$linha              = $dados['linha'];
	$estado 			= $dados['estado'];
	$inspetor 			= $dados['inspetor'];
	$status 			= $dados['status'];
	$posto 				= $dados['posto'];
	$tipo_posto         = $dados['tipo_posto'];
	$peca_pedido        = explode(",",$dados['pedido_peca']);
	$calculo_dias 		= $dados['calculo_dias'];

	if (!empty($status)){
		$cond_status = "AND tbl_os.status_checkpoint IN ($status)";
	} elseif(empty($dias)) {
		$cond_status = "AND tbl_os.status_checkpoint NOT IN (4)";
	}

	$grafico_os_fechada = 'false';	

	if(strlen(trim($dias)) > 0){
		
		$cond_dias_x = "  AND EXTRACT(DAYS FROM (tbl_os.data_conserto - tbl_os.data_digitacao)) ";
		
		switch ($dias) {
			case '0-5 Dias':
				$cond_dias = "  AND EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) <= 5 ";
				$cond_dias_fn = $cond_dias_x . "<= 5 ";
				break;
			case '6-10 Dias':
				$cond_dias = "  AND EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) > 5
								AND EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) <= 10
 							";
				$cond_dias_fn = $cond_dias_x . "> 5" . $cond_dias_x . "<= 10 ";
				break;
			case '11-15 Dias':
				$cond_dias = "  AND EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) > 10
								AND EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) <= 15
							";
				$cond_dias_fn = $cond_dias_x . "> 10" . $cond_dias_x . "<= 15 ";
				break;
			case '16-20 Dias':
				$cond_dias = "	AND EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) > 15
								AND EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) <= 20
							";
				$cond_dias_fn = $cond_dias_x . "> 15" . $cond_dias_x . "<= 20 ";
				break;
			case '21-25 Dias':
				$cond_dias = "	AND EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) > 20
								AND EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) <= 25
							";
				$cond_dias_fn = $cond_dias_x . "> 20" . $cond_dias_x . "<= 25 ";
				break;
			case '26-30 Dias':
				$cond_dias = "	AND EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) > 25
								AND EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) <= 30
							";
				$cond_dias_fn = $cond_dias_x . "> 25" . $cond_dias_x . "<= 30 ";
				break;
			case '31-60 Dias':
				$cond_dias = "	AND EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) > 30
								AND EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) <= 60
							";
				$cond_dias_fn = $cond_dias_x . "> 30" . $cond_dias_x . "<= 60 ";
				break;
			case '61-90 Dias':
				$cond_dias = "	AND EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) > 60
								AND EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) <= 90
							";
				$cond_dias_fn = $cond_dias_x . "> 60" . $cond_dias_x . "<= 90 ";
				break;
			case '> 90 Dias':
				$cond_dias = "AND EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) > 90";
				$cond_dias_fn = $cond_dias_x . "> 90";
				break;
		}

		if(strlen(trim($data_inicial)) > 0 AND strlen(trim($data_final)) > 0){
			$grafico_os_fechada = 'true';
			$cond_data = " AND tbl_os.data_digitacao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' ";
		}

		if(!empty($tipo_atendimento)){
			$cond_atendimento = " AND tbl_os.tipo_atendimento IN ($tipo_atendimento) ";
		}

		if(!empty($linha)){
			$cond_linha = " AND tbl_produto.linha IN ($linha) ";
		}


		if(!empty($estado)){
			$estado = str_replace(",", "','", $estado);
			$estado = "'$estado'";
			$cond_estado = " AND tbl_posto_fabrica.contato_estado IN ({$estado})";
		}

		if(!empty($tipo_posto)){
			$tipo_posto = str_replace(",", "','", $tipo_posto);
			$tipo_posto = "'$tipo_posto'";
			$cond_tipoPosto = " AND tbl_posto_fabrica.tipo_posto IN ({$tipo_posto})";
		}

		if (!empty($posto)) {
			$cond_posto = " AND tbl_os.posto = {$posto} ";
		}else{
			$cond_posto = " AND tbl_os.posto <> 6359 ";
		}

		if(!empty($inspetor)){
			$cond_inspetor = " AND tbl_posto_fabrica.admin_sap IN ({$inspetor})";
		}

		if ($login_fabrica == 183) {
			$campo_inspetor   = ", inspetor_posto.nome_completo AS inspetor_sap_posto ";
			$groupBy_inspetor = ", inspetor_sap_posto ";
			$join_inspetor    = " LEFT JOIN tbl_admin inspetor_posto ON inspetor_posto.fabrica = $login_fabrica AND inspetor_posto.admin = tbl_posto_fabrica.admin_sap ";
		}

		if($grafico_os_fechada == 'false'){
			 
			$sql = "SELECT 	tbl_os.sua_os AS sua_os,
							tbl_os.os AS os,
							tbl_os.hd_chamado AS hd_chamado,
							TO_CHAR (tbl_os.data_digitacao, 'dd/mm/yyyy hh24:mi') AS data_digitacao,
							tbl_posto_fabrica.codigo_posto AS codigo_posto,
							tbl_posto.nome AS descricao_posto,
							tbl_tipo_atendimento.descricao AS tipo_atendimento,
							tbl_produto.referencia AS produto_referencia,
							tbl_produto.descricao AS produto_descricao,
							tbl_linha.nome AS linha,
							tbl_status_checkpoint.descricao AS status_checkpoint,
							(
		                        SELECT date(tbl_tecnico_agenda.data_agendamento)
		                        FROM tbl_tecnico_agenda
		                        WHERE tbl_tecnico_agenda.os = tbl_os.os
		                        AND tbl_os.fabrica = $login_fabrica
		                        AND tbl_tecnico_agenda.fabrica = $login_fabrica
		                        ORDER BY tbl_tecnico_agenda.data_input ASC
		                        LIMIT 1
		                    ) AS primeira_data_agendamento,
		                    (
		                        SELECT date(tbl_tecnico_agenda.data_agendamento)
		                        FROM tbl_tecnico_agenda
		                        WHERE tbl_tecnico_agenda.os = tbl_os.os
		                        AND tbl_os.fabrica = $login_fabrica
		                        AND tbl_tecnico_agenda.fabrica = $login_fabrica
		                        AND tbl_tecnico_agenda.confirmado IS NOT NULL
		                        LIMIT 1
		                    ) AS data_confirmacao,
		                    (
		                        SELECT tbl_admin.nome_completo
		                        FROM tbl_admin
		                        WHERE tbl_admin.admin = tbl_posto_fabrica.admin
		                        AND tbl_admin.fabrica = $login_fabrica
		                    ) AS inspetor_sap
		                    $campo_inspetor
						FROM tbl_os
						INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
						INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
							AND tbl_produto.fabrica_i = {$login_fabrica}
						INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto
							AND tbl_posto_fabrica.fabrica = {$login_fabrica}
						INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
						INNER JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
							AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
						INNER JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = {$login_fabrica}
						INNER JOIN tbl_status_checkpoint ON tbl_status_checkpoint.status_checkpoint = tbl_os.status_checkpoint
						LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_os.admin AND tbl_admin.fabrica = {$login_fabrica}
						$join_inspetor
						WHERE tbl_os.fabrica = $login_fabrica
						AND tbl_os.excluida IS NOT TRUE
						AND tbl_os.finalizada IS NULL
						$cond_linha
						$cond_atendimento
						$cond_estado
						$cond_tipoPosto
						$cond_inspetor
						$cond_dias
						$cond_status
						$cond_posto
						$cond_status_pedido
						GROUP BY tbl_os.os, primeira_data_agendamento, data_confirmacao, inspetor_sap,
								tbl_os.sua_os, tbl_os.hd_chamado, tbl_os.data_digitacao, tbl_posto_fabrica.codigo_posto,
								tbl_posto.nome, tbl_tipo_atendimento.descricao,tbl_produto.referencia,
								tbl_produto.descricao, tbl_linha.nome, tbl_status_checkpoint.descricao $groupBy_inspetor
						";
		}else{
			 
			$sql = "SELECT 	tbl_os.sua_os AS sua_os,
							tbl_os.os AS os,
							tbl_os.hd_chamado AS hd_chamado,
							TO_CHAR (tbl_os.data_digitacao, 'dd/mm/yyyy hh24:mi') AS data_digitacao,
							tbl_posto_fabrica.codigo_posto AS codigo_posto,
							tbl_posto.nome AS descricao_posto,
							tbl_tipo_atendimento.descricao AS tipo_atendimento,
							tbl_produto.referencia AS produto_referencia,
							tbl_produto.descricao AS produto_descricao,
							tbl_linha.nome AS linha,
							tbl_status_checkpoint.descricao AS status_checkpoint,
							(
		                        SELECT date(tbl_tecnico_agenda.data_agendamento)
		                        FROM tbl_tecnico_agenda
		                        WHERE tbl_tecnico_agenda.os = tbl_os.os
		                        AND tbl_os.fabrica = $login_fabrica
		                        AND tbl_tecnico_agenda.fabrica = $login_fabrica
		                        ORDER BY tbl_tecnico_agenda.data_agendamento ASC
		                        LIMIT 1
		                    ) AS primeira_data_agendamento,
		                    (
		                        SELECT date(tbl_tecnico_agenda.data_agendamento)
		                        FROM tbl_tecnico_agenda
		                        WHERE tbl_tecnico_agenda.os = tbl_os.os
		                        AND tbl_os.fabrica = $login_fabrica
		                        AND tbl_tecnico_agenda.fabrica = $login_fabrica
		                        AND tbl_tecnico_agenda.confirmado IS NOT NULL
		                        LIMIT 1
		                    ) AS data_confirmacao,
		                    (
		                        SELECT tbl_admin.nome_completo
		                        FROM tbl_admin
		                        WHERE tbl_admin.admin = tbl_posto_fabrica.admin
		                        AND tbl_admin.fabrica = $login_fabrica
		                    ) AS inspetor_sap
		                    $campo_inspetor
						FROM tbl_os
						INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
						INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
							AND tbl_produto.fabrica_i = {$login_fabrica}
						INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto
							AND tbl_posto_fabrica.fabrica = {$login_fabrica}
						INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
						INNER JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
							AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
						INNER JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = {$login_fabrica}
						INNER JOIN tbl_status_checkpoint ON tbl_status_checkpoint.status_checkpoint = tbl_os.status_checkpoint
						$join_inspetor
						WHERE tbl_os.fabrica = $login_fabrica
						AND tbl_os.excluida IS NOT TRUE
						AND tbl_os.data_digitacao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
						AND tbl_os.excluida IS NOT TRUE
						AND tbl_os.finalizada IS NOT NULL
						$cond_linha
						$cond_atendimento
						$cond_estado
						$cond_tipoPosto
						$cond_inspetor
						$cond_dias_fn
						$cond_posto
						$cond_status
						$cond_status_pedido
						GROUP BY tbl_os.os, primeira_data_agendamento, data_confirmacao, inspetor_sap,
								tbl_os.sua_os, tbl_os.hd_chamado, tbl_os.data_digitacao, tbl_posto_fabrica.codigo_posto,
								tbl_posto.nome, tbl_tipo_atendimento.descricao,tbl_produto.referencia,
								tbl_produto.descricao, tbl_linha.nome, tbl_status_checkpoint.descricao $groupBy_inspetor
						";
		}
		$resSubmit = pg_query($con, $sql);
	}

	$data = date("d-m-Y-H:i");

	$filename = "relatorio-detalhes-tma-{$data}.csv";

	$file = fopen("/tmp/{$filename}", "w");

	$cabecalho = "OS;Chamado;Cód. Posto;Nome Posto;Data Digitação;Tipo Atendimento;Ref. Produto;Desc. Produto;Linha;Status;Data de Ag. Callcenter; Data Conf. Posto; Data de Reag. Posto;Inspetor\n";

	fwrite($file, $cabecalho);

	$contador_submit = pg_num_rows($resSubmit); 

	for ($i = 0; $i < $contador_submit; $i++) {
		$xos 						= pg_fetch_result($resSubmit, $i, 'os');
		$xsua_os					= pg_fetch_result($resSubmit, $i, 'sua_os');
		$xhd_chamado 				= pg_fetch_result($resSubmit, $i, 'hd_chamado');
		$xdata_digitacao 			= pg_fetch_result($resSubmit, $i, 'data_digitacao');
		$xcodigo_posto 				= pg_fetch_result($resSubmit, $i, 'codigo_posto');
		$xdescricao_posto 			= pg_fetch_result($resSubmit, $i, 'descricao_posto');
		$xtipo_atendimento 			= pg_fetch_result($resSubmit, $i, 'tipo_atendimento');
		$xproduto_referencia 		= pg_fetch_result($resSubmit, $i, 'produto_referencia');
		$xproduto_descricao 		= pg_fetch_result($resSubmit, $i, 'produto_descricao');
		$xproduto_descricao         = (mb_detect_encoding($xproduto_descricao, "UTF-8")) ? utf8_decode($xproduto_descricao) : $xproduto_descricao;
		$xlinha 					= pg_fetch_result($resSubmit, $i, 'linha');
		$xstatus_checkpoint 		= pg_fetch_result($resSubmit, $i, 'status_checkpoint');
		$xprimeira_data_agendamento	= pg_fetch_result($resSubmit, $i, 'primeira_data_agendamento');
		$xprimeira_data_agendamento = mostra_data($xprimeira_data_agendamento);
		$xdata_confirmacao          = pg_fetch_result($resSubmit, $i, 'data_confirmacao');
		$xdata_confirmacao          = mostra_data($xdata_confirmacao);
		$xinspetor_sap 				= ($login_fabrica == 183) ? pg_fetch_result($resSubmit, $i, 'inspetor_sap_posto') : pg_fetch_result($resSubmit, $i, 'inspetor_sap');
		$xultima_data               = '';


		if (!empty($xprimeira_data_agendamento)){
			$xsqlReagendamento = "SELECT date(tbl_tecnico_agenda.data_agendamento) as data_agendamento
                                FROM tbl_tecnico_agenda
                                WHERE tbl_tecnico_agenda.os = $xos
                                AND tbl_tecnico_agenda.data_agendamento != '$xprimeira_data_agendamento'
                                AND tbl_tecnico_agenda.confirmado IS NOT NULL
                                ORDER BY tbl_tecnico_agenda.data_agendamento DESC
                                LIMIT 1
                                ";
            $xresReagendamento = pg_query($con, $xsqlReagendamento);

            $xultima_data = pg_fetch_result($xresReagendamento, 0, 'data_agendamento');
            $xultima_data = mostra_data($xultima_data);
        }

		fwrite($file,"$xsua_os;$xhd_chamado;$xcodigo_posto;$xdescricao_posto;$xdata_digitacao;$xtipo_atendimento;$xproduto_referencia;$xproduto_descricao;$xlinha;$xstatus_checkpoint;$xprimeira_data_agendamento;$xdata_confirmacao;$xultima_data;$xinspetor_sap\n");
	}

	fclose($file);

	if (file_exists("/tmp/{$filename}")) {

		system("mv /tmp/{$filename} xls/{$filename}");
		echo "xls/{$filename}";
	} else {
		echo "error";
	}

	exit;
}

if ($_REQUEST["dias"]) {
	$dias   			= $_REQUEST["dias"];
	$data_inicial       = $_REQUEST['data_inicial'];
	$data_final         = $_REQUEST['data_final'];
	$tipo_atendimento 	= $_REQUEST['tipo_atendimento'];
	$linha              = $_REQUEST['linha'];
	$estado 			= $_REQUEST['estado'];
	$inspetor 			= $_REQUEST['inspetor'];
	$status 			= $_REQUEST['status'];
	$posto 				= $_REQUEST['posto'];
	$tipo_posto         = $_REQUEST['tipo_posto'];
	$peca_pedido        = explode(",",$_REQUEST['pedido_peca']);
	$calculo_dias 		= $_REQUEST['calculo_dias'];

	if (!empty($status)){
		$cond_status = "AND tbl_os.status_checkpoint IN ($status)";
	} elseif(empty($dias)) {
		$cond_status = "AND tbl_os.status_checkpoint NOT IN (4)";
	}

	$grafico_os_fechada = 'false';	

	 if(in_array($login_fabrica, [169,170])) { 
	 		
        if (count($peca_pedido) > 0) {
        	
            if (count($peca_pedido) <= 1) {
            
                   if(in_array('sem_pedido', $peca_pedido)) {
                        
                    $cond_status_pedido  .= " AND (SELECT tbl_os_item.pedido 
                                              FROM tbl_os AS o 
                                              JOIN tbl_os_produto ON tbl_os_produto.os = o.os
                                              JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                                              WHERE tbl_os_item.pedido IS NOT NULL
                                              AND o.os = tbl_os.os
                                              LIMIT 1) IS NULL  "; //traz todas as OS que nao tem pedido
                                         
                   }
                
                   if(in_array('com_pedido', $peca_pedido)) {
                   	
                    $cond_status_pedido .= " AND (SELECT tbl_os_item.pedido 
                                             FROM tbl_os AS o 
                                             JOIN tbl_os_produto ON tbl_os_produto.os = o.os
                                             JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                                             WHERE tbl_os_item.pedido IS NOT NULL
                                             AND o.os = tbl_os.os
                                             LIMIT 1) IS NOT NULL  "; //traz todas as OS que tem pedido
                   }
             } 
        }
    }       

	if(strlen(trim($dias)) > 0){
		if($login_fabrica == 169 && $calculo_dias == "uteis"){
			$cond_dias_x = "  AND fn_calcula_dias_uteis(tbl_os.data_digitacao::date, tbl_os.data_conserto::date) ";
		} else {
			$cond_dias_x = "  AND EXTRACT(DAYS FROM (tbl_os.data_conserto - tbl_os.data_digitacao)) ";
		}
		switch ($dias) {
			case '0-5 Dias':
				$cond_dias = "  AND EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) <= 5 ";
				$cond_dias_fn = $cond_dias_x . "<= 5 ";
				break;
			case '6-10 Dias':
				$cond_dias = "  AND EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) > 5
								AND EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) <= 10
 							";
				$cond_dias_fn = $cond_dias_x . "> 5" . $cond_dias_x . "<= 10 ";
				break;
			case '11-15 Dias':
				$cond_dias = "  AND EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) > 10
								AND EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) <= 15
							";
				$cond_dias_fn = $cond_dias_x . "> 10" . $cond_dias_x . "<= 15 ";
				break;
			case '16-20 Dias':
				$cond_dias = "	AND EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) > 15
								AND EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) <= 20
							";
				$cond_dias_fn = $cond_dias_x . "> 15" . $cond_dias_x . "<= 20 ";
				break;
			case '21-25 Dias':
				$cond_dias = "	AND EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) > 20
								AND EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) <= 25
							";
				$cond_dias_fn = $cond_dias_x . "> 20" . $cond_dias_x . "<= 25 ";
				break;
			case '26-30 Dias':
				$cond_dias = "	AND EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) > 25
								AND EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) <= 30
							";
				$cond_dias_fn = $cond_dias_x . "> 25" . $cond_dias_x . "<= 30 ";
				break;
			case '31-60 Dias':
				$cond_dias = "	AND EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) > 30
								AND EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) <= 60
							";
				$cond_dias_fn = $cond_dias_x . "> 30" . $cond_dias_x . "<= 60 ";
				break;
			case '61-90 Dias':
				$cond_dias = "	AND EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) > 60
								AND EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) <= 90
							";
				$cond_dias_fn = $cond_dias_x . "> 60" . $cond_dias_x . "<= 90 ";
				break;
			case '> 90 Dias':
				$cond_dias = "AND EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) > 90";
				$cond_dias_fn = $cond_dias_x . "> 90";
				break;
		}

		if(strlen(trim($data_inicial)) > 0 AND strlen(trim($data_final)) > 0){
			$grafico_os_fechada = 'true';
			$cond_data = " AND tbl_os.data_digitacao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' ";
		}

		if(!empty($tipo_atendimento)){
			$cond_atendimento = " AND tbl_os.tipo_atendimento IN ($tipo_atendimento) ";
		}

		if(!empty($linha)){
			$cond_linha = " AND tbl_produto.linha IN ($linha) ";
		}


		if(!empty($estado)){
			$estado = str_replace(",", "','", $estado);
			$estado = "'$estado'";
			$cond_estado = " AND tbl_posto_fabrica.contato_estado IN ({$estado})";
		}

		if(!empty($tipo_posto)){
			$tipo_posto = str_replace(",", "','", $tipo_posto);
			$tipo_posto = "'$tipo_posto'";
			$cond_tipoPosto = " AND tbl_posto_fabrica.tipo_posto IN ({$tipo_posto})";
		}

		if (!empty($posto)) {
			$cond_posto = " AND tbl_os.posto = {$posto} ";
		}else{
			$cond_posto = " AND tbl_os.posto <> 6359 ";
		}

		if(!empty($inspetor)){
			$cond_inspetor = " AND tbl_posto_fabrica.admin_sap IN ({$inspetor})";
		}

		if ($login_fabrica == 183) {
			$campo_inspetor   = ", inspetor_posto.nome_completo AS inspetor_sap_posto ";
			$groupBy_inspetor = ", inspetor_sap_posto ";
			$join_inspetor    = " LEFT JOIN tbl_admin inspetor_posto ON inspetor_posto.fabrica = $login_fabrica AND inspetor_posto.admin = tbl_posto_fabrica.admin_sap ";
		}

		if($grafico_os_fechada == 'false'){
			 
			$sql = "SELECT 	tbl_os.sua_os AS sua_os,
							tbl_os.os AS os,
							tbl_os.hd_chamado AS hd_chamado,
							TO_CHAR (tbl_os.data_digitacao, 'dd/mm/yyyy hh24:mi') AS data_digitacao,
							tbl_posto_fabrica.codigo_posto AS codigo_posto,
							tbl_posto.nome AS descricao_posto,
							tbl_tipo_atendimento.descricao AS tipo_atendimento,
							tbl_produto.referencia AS produto_referencia,
							tbl_produto.descricao AS produto_descricao,
							tbl_linha.nome AS linha,
							tbl_status_checkpoint.descricao AS status_checkpoint,
							(
		                        SELECT date(tbl_tecnico_agenda.data_agendamento)
		                        FROM tbl_tecnico_agenda
		                        WHERE tbl_tecnico_agenda.os = tbl_os.os
		                        AND tbl_os.fabrica = $login_fabrica
		                        AND tbl_tecnico_agenda.fabrica = $login_fabrica
		                        ORDER BY tbl_tecnico_agenda.data_input ASC
		                        LIMIT 1
		                    ) AS primeira_data_agendamento,
		                    (
		                        SELECT date(tbl_tecnico_agenda.data_agendamento)
		                        FROM tbl_tecnico_agenda
		                        WHERE tbl_tecnico_agenda.os = tbl_os.os
		                        AND tbl_os.fabrica = $login_fabrica
		                        AND tbl_tecnico_agenda.fabrica = $login_fabrica
		                        AND tbl_tecnico_agenda.confirmado IS NOT NULL
		                        LIMIT 1
		                    ) AS data_confirmacao,
		                    (
		                        SELECT tbl_admin.nome_completo
		                        FROM tbl_admin
		                        WHERE tbl_admin.admin = tbl_posto_fabrica.admin
		                        AND tbl_admin.fabrica = $login_fabrica
		                    ) AS inspetor_sap
		                    $campo_inspetor
						FROM tbl_os
						INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
						INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
							AND tbl_produto.fabrica_i = {$login_fabrica}
						INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto
							AND tbl_posto_fabrica.fabrica = {$login_fabrica}
						INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
						INNER JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
							AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
						INNER JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = {$login_fabrica}
						INNER JOIN tbl_status_checkpoint ON tbl_status_checkpoint.status_checkpoint = tbl_os.status_checkpoint
						LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_os.admin AND tbl_admin.fabrica = {$login_fabrica}
						$join_inspetor
						WHERE tbl_os.fabrica = $login_fabrica
						AND tbl_os.excluida IS NOT TRUE
						AND tbl_os.finalizada IS NULL
						$cond_linha
						$cond_atendimento
						$cond_estado
						$cond_tipoPosto
						$cond_inspetor
						$cond_dias
						$cond_status
						$cond_posto
						$cond_status_pedido
						GROUP BY tbl_os.os, primeira_data_agendamento, data_confirmacao, inspetor_sap,
								tbl_os.sua_os, tbl_os.hd_chamado, tbl_os.data_digitacao, tbl_posto_fabrica.codigo_posto,
								tbl_posto.nome, tbl_tipo_atendimento.descricao,tbl_produto.referencia,
								tbl_produto.descricao, tbl_linha.nome, tbl_status_checkpoint.descricao $groupBy_inspetor
						";
		}else{
			 
			$sql = "SELECT 	tbl_os.sua_os AS sua_os,
							tbl_os.os AS os,
							tbl_os.hd_chamado AS hd_chamado,
							TO_CHAR (tbl_os.data_digitacao, 'dd/mm/yyyy hh24:mi') AS data_digitacao,
							tbl_posto_fabrica.codigo_posto AS codigo_posto,
							tbl_posto.nome AS descricao_posto,
							tbl_tipo_atendimento.descricao AS tipo_atendimento,
							tbl_produto.referencia AS produto_referencia,
							tbl_produto.descricao AS produto_descricao,
							tbl_linha.nome AS linha,
							tbl_status_checkpoint.descricao AS status_checkpoint,
							(
		                        SELECT date(tbl_tecnico_agenda.data_agendamento)
		                        FROM tbl_tecnico_agenda
		                        WHERE tbl_tecnico_agenda.os = tbl_os.os
		                        AND tbl_os.fabrica = $login_fabrica
		                        AND tbl_tecnico_agenda.fabrica = $login_fabrica
		                        ORDER BY tbl_tecnico_agenda.data_agendamento ASC
		                        LIMIT 1
		                    ) AS primeira_data_agendamento,
		                    (
		                        SELECT date(tbl_tecnico_agenda.data_agendamento)
		                        FROM tbl_tecnico_agenda
		                        WHERE tbl_tecnico_agenda.os = tbl_os.os
		                        AND tbl_os.fabrica = $login_fabrica
		                        AND tbl_tecnico_agenda.fabrica = $login_fabrica
		                        AND tbl_tecnico_agenda.confirmado IS NOT NULL
		                        LIMIT 1
		                    ) AS data_confirmacao,
		                    (
		                        SELECT tbl_admin.nome_completo
		                        FROM tbl_admin
		                        WHERE tbl_admin.admin = tbl_posto_fabrica.admin
		                        AND tbl_admin.fabrica = $login_fabrica
		                    ) AS inspetor_sap
		                    $campo_inspetor
						FROM tbl_os
						INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
						INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
							AND tbl_produto.fabrica_i = {$login_fabrica}
						INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto
							AND tbl_posto_fabrica.fabrica = {$login_fabrica}
						INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
						INNER JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
							AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
						INNER JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = {$login_fabrica}
						INNER JOIN tbl_status_checkpoint ON tbl_status_checkpoint.status_checkpoint = tbl_os.status_checkpoint
						$join_inspetor
						WHERE tbl_os.fabrica = $login_fabrica
						AND tbl_os.excluida IS NOT TRUE
						AND tbl_os.data_digitacao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
						AND tbl_os.excluida IS NOT TRUE
						AND tbl_os.finalizada IS NOT NULL
						$cond_linha
						$cond_atendimento
						$cond_estado
						$cond_tipoPosto
						$cond_inspetor
						$cond_dias_fn
						$cond_posto
						$cond_status
						$cond_status_pedido
						GROUP BY tbl_os.os, primeira_data_agendamento, data_confirmacao, inspetor_sap,
								tbl_os.sua_os, tbl_os.hd_chamado, tbl_os.data_digitacao, tbl_posto_fabrica.codigo_posto,
								tbl_posto.nome, tbl_tipo_atendimento.descricao,tbl_produto.referencia,
								tbl_produto.descricao, tbl_linha.nome, tbl_status_checkpoint.descricao $groupBy_inspetor
						";
		}		
		$resSubmit = pg_query($con, $sql);
	}
}
?>

<!DOCTYPE html />
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
		<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="bootstrap/js/bootstrap.js"></script>
<!--
		<link href="plugins/datatable_responsive/css/jquery.dataTables.min.css" type="text/css" rel="stylesheet" />
		<link href="plugins/datatable_responsive/css/responsive.dataTables.min.css" type="text/css" rel="stylesheet" />

		<script src="plugins/datatable_responsive/js/jquery.dataTables.min.js"></script>
		<script src="plugins/datatable_responsive/js/dataTables.responsive.min.js"></script>
-->
		<script src="plugins/resize.js"></script>
<?php
	$plugins = array(
		"datatable_responsive"
	);
	include("plugin_loader.php");
?>

<script type="text/javascript">
	$(function() {
		$("#detalhes_tma").DataTable({
			responsive: true,
			"language": {
	            "lengthMenu": "Qtde _MENU_  por página",
	            "search": "Buscar",
	            "zeroRecords": "Nenhum resultado encontrado",
	            "info": "Visualizando página _PAGE_ de _PAGES_",
	            "infoEmpty": "Nenhum resultado encontrado",
	            "infoFiltered": "(busca feita pelo total de _MAX_ registros)",
	            'paginate': {
			    	'previous': '<span class="prev-icon"></span>',
			    	'next': '<span class="next-icon"></span>'
			    }
	        }
		});

		<?php if (in_array($login_fabrica, [167, 203])) { ?>
			$(".mostra_posto").show();
		<?php } ?>

		$("#gerar_csv").on("click", function() { 

			let getDados = '<?=json_encode($_REQUEST)?>'

			$.ajax({
		        async: true,
		        type: 'POST',
		        dataType:"JSON",
		        url: 'detalhes_tma.php',
		        data: {
		            gerar_relatorio_detalhe_csv:true,
		            request:getDados
		        },
		        beforeSend: function(){
                    $('#gerar_csv').html("&nbsp;&nbsp;Gerando...&nbsp;&nbsp;<br><img src='imagens/loading_bar.gif'> ");
                },
                complete: function(data) {
			    	$('#gerar_csv').html("Gerar Arquivo CSV");
			    	
			        if(data.responseText == 'error'){
			        	alert('Erro ao gerar CSV');
			            return false;
			        } else {
			        	window.open(data.responseText);
			        }
		    	}
			});
		});
	});
</script>
	</head>
	<body>
		<div id="" style="overflow-y:auto;z-index:1">
			<?php if(pg_num_rows($resSubmit) > 0){ ?>
				<table id="detalhes_tma" class="display nowrap" cellspacing="0" width="100%" >
					<thead>
						<tr class='titulo_tabela'>
							<th colspan="10">Relatorio TMA Detalhado</th>
						</tr>
						<tr class='titulo_coluna' >
							<th>OS</th>
							<th>Chamado</th>
							<th>Data digitação</th>
							<th>Tipo Atendimento</th>
							<th>Ref. Produto</th>
	                        <th>Desc. Produto</th>
							<th>Linha</th>
							<th>Status</th>
							<th>Cód. Posto</th>
							<?php if (in_array($login_fabrica, [167, 203])) { ?>
								<th class="mostra_posto">Posto</th>
							<?php } ?>
							<th>Data de agendamento Callcenter</th>
							<th>Data Confirmação Posto</th>
							<th>Data de Reagendamento Posto</th>
							<th>Inspetor</th>
						</tr>
					</thead>
					<tbody>
						<?php for ($i=0; $i < pg_num_rows($resSubmit); $i++) {
							$os 						= pg_fetch_result($resSubmit, $i, 'os');
							$sua_os						= pg_fetch_result($resSubmit, $i, 'sua_os');
							$hd_chamado 				= pg_fetch_result($resSubmit, $i, 'hd_chamado');
							$data_digitacao 			= pg_fetch_result($resSubmit, $i, 'data_digitacao');
							$codigo_posto 				= pg_fetch_result($resSubmit, $i, 'codigo_posto');
							$descricao_posto 			= pg_fetch_result($resSubmit, $i, 'descricao_posto');
							$tipo_atendimento 			= pg_fetch_result($resSubmit, $i, 'tipo_atendimento');
							$produto_referencia 		= pg_fetch_result($resSubmit, $i, 'produto_referencia');
							$produto_descricao 			= pg_fetch_result($resSubmit, $i, 'produto_descricao');
							$linha 						= pg_fetch_result($resSubmit, $i, 'linha');
							$status_checkpoint 			= pg_fetch_result($resSubmit, $i, 'status_checkpoint');
							$primeira_data_agendamento	= pg_fetch_result($resSubmit, $i, 'primeira_data_agendamento');
							$data_confirmacao           = pg_fetch_result($resSubmit, $i, 'data_confirmacao');
							$inspetor_sap 				= ($login_fabrica == 183) ? pg_fetch_result($resSubmit, $i, 'inspetor_sap_posto') : pg_fetch_result($resSubmit, $i, 'inspetor_sap');

							if (!empty($primeira_data_agendamento)){
								$sqlReagendamento = "SELECT date(tbl_tecnico_agenda.data_agendamento) as data_agendamento
			                                        FROM tbl_tecnico_agenda
			                                        WHERE tbl_tecnico_agenda.os = $os
			                                        AND tbl_tecnico_agenda.data_agendamento != '$primeira_data_agendamento'
			                                        AND tbl_tecnico_agenda.confirmado IS NOT NULL
			                                        ORDER BY tbl_tecnico_agenda.data_agendamento DESC
			                                        LIMIT 1
			                                        ";
			                    $resReagendamento = pg_query($con, $sqlReagendamento);

			                    $ultima_data = pg_fetch_result($resReagendamento, 0, 'data_agendamento');
		                    }
						?>
							<tr>
								<td><a href="os_press.php?os=<?=$os?>" target="_blank"><?=$sua_os?></a></td>
								<td><a href="callcenter_interativo_new.php?callcenter=<?=$hd_chamado?>" target="_blank"><?=$hd_chamado?></a></td>
								<td><?=$data_digitacao?></td>
								<td><?=$tipo_atendimento?></td>
								<td><?=$produto_referencia?></td>
								<td><?=(mb_detect_encoding($produto_descricao, "UTF-8")) ? utf8_decode($produto_descricao) : $produto_descricao;?></td>
								<td><?=$linha?></td>
								<td><?=$status_checkpoint?></td>
								<td><?=$codigo_posto?></td>
								<?php if (in_array($login_fabrica, [167, 203])) { ?>
									<td class="mostra_posto"><?=$descricao_posto?></td>
								<?php } ?>
								<td><?=mostra_data($primeira_data_agendamento)?></td>
								<td><?=mostra_data($data_confirmacao)?></td>
								<td><?=mostra_data($ultima_data)?></td>
								<td><?=$inspetor_sap?></td>
							</tr>
						<?php
						}
						?>
					</tbody>

					<?php if ($login_fabrica == 183) { ?>
						<div id='gerar_excel' class="btn_excel tac" style="position: absolute; bottom: 0; width: 100% !important;">
							<span><img src='imagens/icon_csv.png' /></span>
							<span class="txt" id="gerar_csv">Gerar Arquivo CSV</span>
						</div>
					<?php } ?>
			<?php }else{ ?>
				<div class="alert alert-warning">
					<h4>Nenhum resultado encontrado.</h4>
			    </div>
			<?}?>
		</div>
	</body>
</html>
