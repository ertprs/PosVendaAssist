<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';
include '../helpdesk/mlg_funciones.php';

$value_column = 0;
$grafico_spline = 0;

$meses = array("01" => "Janeiro",     
	"02" => "Fevereiro",
	"03" => "Março",
	"04" => "Abril",
	"05" => "Maio",
	"06" => "Junho",
	"07" => "Julho",
	"08" => "Agosto",
	"09" => "Setembro",
	"10" => "Outubro",
	"11" => "Novembro",
	"12" => "Dezembro"
);

if ($_POST["btn_acao"] == "submit") {
	$data_inicial       = $_POST['data_inicial'];
	$data_final         = $_POST['data_final'];
	$tipo_atendimento 	= $_POST['tipo_atendimento'];
	$linha              = $_POST['linha'];
	$estado 			= $_POST['estado'];
	$tipo_posto         = $_POST['tipo_posto'];
	$inspetor 			= $_POST['inspetor'];
	$status 			= $_POST['status_checkpoint'];
	$codigo_posto 		= $_POST['codigo_posto'];
	$descricao_posto  	= $_POST['descricao_posto'];
	$mae_filha          = $_POST['mae_filha'];
	
	if($login_fabrica == 169){
		$calculo_dias 		= $_POST['calculo_dias'];
	}
   
	$tbl_aux = "tbl_os";
	$considera_revenda = false;
    if ($mae_filha == "mae") {
    	$considera_revenda = true;
    	$tbl_aux = "tbl_os_revenda";
    } else if ($mae_filha == "filha") {
		$join_revenda = "JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os
						 AND tbl_os_campo_extra.os_revenda IS NOT NULL";
    }
	$pedido_peca        = $_POST['pedido_peca'];

	if (is_array($status)){
		 
		$status = implode(',', $status);
		$cond_status = "AND tbl_os.status_checkpoint IN ($status)";
	} else if (empty($status)) {
		
		$cond_status = "AND tbl_os.status_checkpoint != 4";
	}


	 if(in_array($login_fabrica, [169,170])) { 
	 		 
        if (count($pedido_peca) > 0) {
        	 
               if (count($pedido_peca) == 1) {
            	 
                   if(in_array('sem_pedido', $pedido_peca)) {
            	 
                    $cond_status_pedido  .= " AND (SELECT tbl_os_item.pedido 
                                              FROM tbl_os AS o 
                                              JOIN tbl_os_produto ON tbl_os_produto.os = o.os
                                              JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                                              WHERE tbl_os_item.pedido IS NOT NULL
                                              AND o.os = tbl_os.os
                                              LIMIT 1) IS NULL  "; //traz todas as OS que nao tem pedido
                                         
                   }
                
                   if(in_array('com_pedido', $pedido_peca)) {
                    
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


	if (strlen($codigo_posto) > 0 or strlen($descricao_posto) > 0){
		$sql = "SELECT tbl_posto_fabrica.posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING(posto)
				WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
				AND (
					(UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}'))
					OR
					(TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('{$descricao_posto}'), 'LATIN-9'))
				)";

		//die(nl2br($sql));
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Posto não encontrado";
			$msg_erro["campos"][] = "posto";
		} else {
			$posto = pg_fetch_result($res, 0, "posto");
		}

		if (!empty($posto)) {
			$cond_posto = " AND {$tbl_aux}.posto = {$posto} ";
		}else{
			$cond_posto = " AND {$tbl_aux}.posto <> 6359 ";
		}
	}
	
	if (is_array($linha)) {
		$linha = implode(',', $linha);
		$cond_linha = "AND tbl_produto.linha IN ($linha)";
	}

	if (is_array($tipo_atendimento)){
		$tipo_atendimento = implode(',', $tipo_atendimento);
		$cond_atendimento = "AND {$tbl_aux}.tipo_atendimento IN ({$tipo_atendimento})";
	}

	if (!empty($estado)) {
	    $auxEstado = implode(",", $estado);
            $estado = array_map(function($e) {
                return "'{$e}'";
            }, $estado);
	    $estado = implode(",", $estado);
            $cond_estado = "AND tbl_posto_fabrica.contato_estado IN ({$estado})";
        }

	if (!empty($tipo_posto)) {
	    $auxTipoPosto = implode(",", $tipo_posto);
	        $tipo_posto = array_map(function($e) {
	            return "'{$e}'";
	        }, $tipo_posto);
	    $tipo_posto = implode(",", $tipo_posto);
	        $cond_tipo_posto = "AND tbl_posto_fabrica.tipo_posto IN ({$tipo_posto})";
	}

	if (!empty($inspetor)) {
		$inspetor = implode(",", $inspetor);
		$sql = "SELECT admin FROM tbl_admin WHERE fabrica = {$login_fabrica} AND ativo IS TRUE AND admin_sap IS TRUE AND admin IN ({$inspetor});";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) != count($_POST['inspetor'])) {
			$msg_erro["msg"][]    = "Inspetor não encontrado";
			$msg_erro["campos"][] = "inspetor";
		}
		$cond_inspetor = "AND tbl_posto_fabrica.admin_sap IN ({$inspetor})";
	}

	$countDistinct = " COUNT(*) AS qtde, ";

	$join_os_produto = " INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
						 INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = $login_fabrica";

	if ($telecontrol_distrib || $replica_einhell) {
		$join_os_produto = " JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto AND tbl_produto.fabrica_i = $login_fabrica";
		$countDistinct = " COUNT(DISTINCT os) AS qtde, ";
	}

	if(strlen(trim($data_inicial)) > 0 AND strlen(trim($data_final)) > 0){
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);

		if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
			$msg_erro["msg"][]    = "Data Inválida";
			$msg_erro["campos"][] = "data";
		} else {
			$aux_data_inicial = "{$yi}-{$mi}-{$di}";
			$aux_data_final   = "{$yf}-{$mf}-{$df}";

			if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
				$msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
				$msg_erro["campos"][] = "data";
			}
		}

		$sqlX = "SELECT '$aux_data_inicial'::date + interval '6 months' > '$aux_data_final'";
		$resX = pg_query($con,$sqlX);
		$periodo_6meses = pg_fetch_result($resX,0,0);
		if($periodo_6meses == 'f'){
			$msg_erro["msg"][]    = "As datas devem ser no máximo 6 meses";
			$msg_erro["campos"][] = "data";
		}
	}

	$colFechamento = (in_array($login_fabrica, [169, 170])) ? "data_conserto::date" : "data_fechamento";

	$cond_data_busca = " AND tbl_os.data_digitacao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'";
	
	$data_fx = (isset($_POST["data_fx"])) ? $_POST["data_fx"] : "" ;
	if ($data_fx == "fechamento") {
		$cond_data_busca = " AND tbl_os.data_fechamento BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'";
	}

	if (!count($msg_erro["msg"])) {
		 
		$resultado_fechada = "false";
		if (!empty($aux_data_inicial) AND !empty($aux_data_final)){

			if (!$considera_revenda) {
				if($login_fabrica == 169 && $calculo_dias == "uteis"){
					$cond_calculo_dias = "CASE WHEN fn_calcula_dias_uteis(tbl_os.data_digitacao::date, tbl_os.data_conserto::date) <= 5 THEN 0
											WHEN fn_calcula_dias_uteis(tbl_os.data_digitacao::date, tbl_os.data_conserto::date) <= 10 THEN 6
											WHEN fn_calcula_dias_uteis(tbl_os.data_digitacao::date, tbl_os.data_conserto::date) <= 15 THEN 11
											WHEN fn_calcula_dias_uteis(tbl_os.data_digitacao::date, tbl_os.data_conserto::date) <= 20 THEN 16
											WHEN fn_calcula_dias_uteis(tbl_os.data_digitacao::date, tbl_os.data_conserto::date) <= 25 THEN 21
											WHEN fn_calcula_dias_uteis(tbl_os.data_digitacao::date, tbl_os.data_conserto::date) <= 30 THEN 26
											WHEN fn_calcula_dias_uteis(tbl_os.data_digitacao::date, tbl_os.data_conserto::date) <= 60 THEN 31
											WHEN fn_calcula_dias_uteis(tbl_os.data_digitacao::date, tbl_os.data_conserto::date) <= 90 THEN 61
											WHEN fn_calcula_dias_uteis(tbl_os.data_digitacao::date, tbl_os.data_conserto::date) > 90 THEN 90
											END AS tempo,
											CASE WHEN fn_calcula_dias_uteis(tbl_os.data_digitacao::date, tbl_os.data_conserto::date) <= 10 THEN 'dez'
											WHEN fn_calcula_dias_uteis(tbl_os.data_digitacao::date, tbl_os.data_conserto::date) <= 30 THEN 'trinta'
											END AS dez_trinta,";
				} else {

					$colFinalizada = (!in_array($login_fabrica, [148])) ? "data_conserto" : "finalizada";
					$cond_calculo_dias = " CASE WHEN EXTRACT(DAYS FROM (tbl_os.data_conserto - tbl_os.data_digitacao)) <= 5 THEN 0
											WHEN EXTRACT(DAYS FROM (tbl_os.$colFinalizada - tbl_os.data_digitacao)) <= 10 THEN 6
											WHEN EXTRACT(DAYS FROM (tbl_os.$colFinalizada - tbl_os.data_digitacao)) <= 15 THEN 11
											WHEN EXTRACT(DAYS FROM (tbl_os.$colFinalizada - tbl_os.data_digitacao)) <= 20 THEN 16
											WHEN EXTRACT(DAYS FROM (tbl_os.$colFinalizada - tbl_os.data_digitacao)) <= 25 THEN 21
											WHEN EXTRACT(DAYS FROM (tbl_os.$colFinalizada - tbl_os.data_digitacao)) <= 30 THEN 26
											WHEN EXTRACT(DAYS FROM (tbl_os.$colFinalizada - tbl_os.data_digitacao)) <= 60 THEN 31
											WHEN EXTRACT(DAYS FROM (tbl_os.$colFinalizada - tbl_os.data_digitacao)) <= 90 THEN 61
											WHEN EXTRACT(DAYS FROM (tbl_os.$colFinalizada - tbl_os.data_digitacao)) > 90 THEN 90
											END AS tempo,					
											CASE WHEN EXTRACT(DAYS FROM (tbl_os.$colFinalizada - tbl_os.data_digitacao)) <= 10 THEN 'dez'
											WHEN EXTRACT(DAYS FROM (tbl_os.$colFinalizada - tbl_os.data_digitacao)) <= 30 THEN 'trinta'
											END AS dez_trinta, ";
				}
			 	
				$sql_fechada = "SELECT
					$countDistinct
					{$cond_calculo_dias} 
					SUM(tbl_os.{$colFechamento} - tbl_os.data_abertura) AS tma_fechamento
					FROM tbl_os
					$join_os_produto
					INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					{$join_revenda}
					WHERE tbl_os.fabrica = $login_fabrica
					$cond_data_busca
					AND tbl_os.excluida IS NOT TRUE
					AND tbl_os.finalizada IS NOT NULL
					$cond_linha
					$cond_atendimento
					$cond_estado
					$cond_tipo_posto
					$cond_inspetor
					$cond_status
					$cond_posto
					$cond_status_pedido
					GROUP BY tempo, dez_trinta
					ORDER BY tempo"; 	

			} else {

				$colFinalizada = (!in_array($login_fabrica, [148])) ? "data_fechamento" : "finalizada";	
				$sql_fechada = "SELECT
					$countDistinct
					CASE WHEN EXTRACT(DAYS FROM (tbl_os_revenda.data_fechamento - tbl_os_revenda.digitacao)) <= 5 THEN 0
					WHEN EXTRACT(DAYS FROM (tbl_os_revenda.$colFinalizada - tbl_os_revenda.digitacao)) <= 10 THEN 6
					WHEN EXTRACT(DAYS FROM (tbl_os_revenda.$colFinalizada - tbl_os_revenda.digitacao)) <= 15 THEN 11
					WHEN EXTRACT(DAYS FROM (tbl_os_revenda.$colFinalizada - tbl_os_revenda.digitacao)) <= 20 THEN 16
					WHEN EXTRACT(DAYS FROM (tbl_os_revenda.$colFinalizada - tbl_os_revenda.digitacao)) <= 25 THEN 21
					WHEN EXTRACT(DAYS FROM (tbl_os_revenda.$colFinalizada - tbl_os_revenda.digitacao)) <= 30 THEN 26
					WHEN EXTRACT(DAYS FROM (tbl_os_revenda.$colFinalizada - tbl_os_revenda.digitacao)) <= 60 THEN 31
					WHEN EXTRACT(DAYS FROM (tbl_os_revenda.$colFinalizada - tbl_os_revenda.digitacao)) <= 90 THEN 61
					WHEN EXTRACT(DAYS FROM (tbl_os_revenda.$colFinalizada - tbl_os_revenda.digitacao)) > 90 THEN 90
					END AS tempo,
					CASE WHEN EXTRACT(DAYS FROM (tbl_os_revenda.$colFinalizada - tbl_os_revenda.digitacao)) <= 10 THEN 'dez'
					WHEN EXTRACT(DAYS FROM (tbl_os_revenda.$colFinalizada - tbl_os_revenda.digitacao)) <= 30 THEN 'trinta'
					END AS dez_trinta,
					SUM(tbl_os_revenda.$colFinalizada - tbl_os_revenda.data_abertura) AS tma_fechamento
					FROM tbl_os_revenda
					INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os_revenda.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					WHERE tbl_os_revenda.fabrica = $login_fabrica
					AND tbl_os_revenda.digitacao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
					AND tbl_os_revenda.finalizada IS NOT NULL
					$cond_atendimento
					$cond_estado
					$cond_tipo_posto
					$cond_posto
					GROUP BY tempo, dez_trinta
					ORDER BY tempo";

			}
			//die(nl2br($sql_fechada));
			$resSubmitFechada = pg_query($con, $sql_fechada);
		}

		if(pg_num_rows($resSubmitFechada) > 0){
			$result_fechada = pg_fetch_all($resSubmitFechada);
			$resultado_fechada = "true";
			$dias_grafico_fechada = array();
			$cor_fechada = array();
			$dados_column_fechada = array();
			$array_spline_fechada = array();
			$grafico_spline_fechada = array();
			$total_fechada = 0;

			foreach ($result_fechada as $key => $value) {
				$total_fechada +=$value['qtde'];
				switch ($value['tempo']) {
					case '0':
							$cor_fechada[0]['y'] 		= $value['qtde'];
							$cor_fechada[0]['color'] 	= '#468847';
						break;

					case '6':
							$cor_fechada[1]['y'] 		= $value['qtde'];
							$cor_fechada[1]['color'] 	= '#468847';
						break;

					case '11':
							$cor_fechada[2]['y']		= $value['qtde'];
							$cor_fechada[2]['color'] 	= '#468847';
						break;

					case '16':
							$cor_fechada[3]['y']	= $value['qtde'];
							$cor_fechada[3]['color']	= '#f89406';
						break;

					case '21':
							$cor_fechada[4]['y']	= $value['qtde'];
							$cor_fechada[4]['color']	= '#f89406';
						break;

					case '26':
							$cor_fechada[5]['y']	= $value['qtde'];
							$cor_fechada[5]['color']	= '#b94a48';
						break;

					case '31':
							$cor_fechada[6]['y']	= $value['qtde'];
							$cor_fechada[6]['color']	= '#b94a48';
						break;

					case '61':
							$cor_fechada[7]['y']	= $value['qtde'];
							$cor_fechada[7]['color']	= '#b94a48';
						break;

					case '90':
							$cor_fechada[8]['y']	= $value['qtde'];
							$cor_fechada[8]['color']	= '#b94a48';
						break;
				}

				if ($value["dez_trinta"] == "dez"){
					$total_dez +=$value["qtde"];
				}

				if ($value["dez_trinta"] == "trinta"){
					$total_trinta +=$value["qtde"];
				}
				$total_tma_fechamento += $value["tma_fechamento"];
			}
			
			$media_dias_tma_fechada = ($total_tma_fechamento/$total_fechada);

			$porc_dez_fechada = ($total_dez/$total_fechada)*100;
			$porc_dez_fechada = round($porc_dez_fechada);
			$porc_dez_fechada = number_format($porc_dez_fechada,'2','.','.');

			$total_trinta += $total_dez;
			$porc_trinta_fechada = ($total_trinta/$total_fechada)*100;
			$porc_trinta_fechada = round($porc_trinta_fechada);
			$porc_trinta_fechada = number_format($porc_trinta_fechada,'2','.','.');

			$dias_grafico_fechada[0] = "0-5 Dias";
			$dias_grafico_fechada[1] = "6-10 Dias";
			$dias_grafico_fechada[2] = "11-15 Dias";
			$dias_grafico_fechada[3] = "16-20 Dias";
			$dias_grafico_fechada[4] = "21-25 Dias";
			$dias_grafico_fechada[5] = "26-30 Dias";
			$dias_grafico_fechada[6] = "31-60 Dias";
			$dias_grafico_fechada[7] = "61-90 Dias";
			$dias_grafico_fechada[8] = "> 90 Dias";

			$count_dias_fechada = count($dias_grafico_fechada);
			$dias_grafico_fechada = implode(',', $dias_grafico_fechada);

			###	Dados Column ###
			for ($i=0; $i < $count_dias_fechada; $i++) {
				$dados_column_fechada[$i]['y'] 		= 0;
				$dados_column_fechada[$i]['color'] 	= 0;
			}
			$xcor_fechada = $cor_fechada;
			$cor_fechada = array_merge_keys($dados_column_fechada,$cor_fechada);
			$value_column_fechada = json_encode($cor_fechada);
			### Fim Dados Column ###

			### Dados Spline ###
			for ($i=0; $i < $count_dias_fechada; $i++) {
				$array_spline_fechada[$i] = 0;
			}

			$tempo_key_fechada = array(
				0 => 0,
				6 => 1,
				11 => 2,
				16 => 3,
				21 => 4,
				26 => 5,
				31 => 6,
				61 => 7,
				90 => 8
			);
			$value_spline_fechada = array_fill(0, $count_dias_fechada, 0);

			foreach ($result_fechada as $row) {
				$value_spline_fechada[$tempo_key_fechada[$row["tempo"]]] = $row["qtde"];
			}

			foreach ($value_spline_fechada as $key => $value) {
				$porc_fechada = ($value/$total_fechada)*100;
				$porc_fechada = round($porc_fechada);
				$porc_fechada = number_format($porc_fechada,'2','.','.');
				$grafico_spline_fechada[] = $porc_fechada;
			}
			$grafico_spline_fechada = array_merge_keys($array_spline_fechada,$grafico_spline_fechada);
			$xgrafico_spline_fechada = $grafico_spline_fechada;

			$grafico_spline_fechada = implode(',', $grafico_spline_fechada);
			### Fim Dados Spline ###
		}else{
			$resultado_fechada = "false";
			$dias_grafico_fechada = "0";
			$grafico_spline_fechada = "0";
			$value_column_fechada = "0";
		}
	}
}else{
	$resultado_fechada = "false";
	$dias_grafico_fechada = "0";
	$grafico_spline_fechada = "0";
	$value_column_fechada = "0";
}

if (!count($msg_erro["msg"])) {

	$countDistinct = " COUNT(*) AS qtde, ";

	$join_os_produto = " INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
						 INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = $login_fabrica";

	if ($telecontrol_distrib || $replica_einhell) {
		$join_os_produto = " JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto AND tbl_produto.fabrica_i = $login_fabrica";
		$countDistinct = " COUNT(DISTINCT os) AS qtde, ";
	}

	if (!$considera_revenda) {
	 
		$condDias = "	CASE WHEN EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) <= 5 THEN 0
						WHEN EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) <= 10 THEN 6
						WHEN EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) <= 15 THEN 11
						WHEN EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) <= 20 THEN 16
						WHEN EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) <= 25 THEN 21
						WHEN EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) <= 30 THEN 26
						WHEN EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) <= 60 THEN 31
						WHEN EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) <= 90 THEN 61
						WHEN EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os.data_digitacao)) > 90  THEN 90
						END AS tempo,";
		$condFn = " AND tbl_os.finalizada IS NULL ";
		$ordena = " GROUP BY tempo
					ORDER BY tempo";

		if ($login_fabrica == 148 && $status == 9) {
			$condDias = "";
			$condFn = "";
			$ordena = "";
		} 

		$sql = "SELECT DISTINCT
				$countDistinct
				$condDias
				SUM(CURRENT_DATE - tbl_os.data_abertura) AS tma_abertura
				FROM tbl_os
				$join_os_produto
				INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				{$join_revenda}
				WHERE tbl_os.fabrica = $login_fabrica
				AND tbl_os.excluida IS NOT TRUE
				$condFn
				AND tbl_os.posto <> 6359
				$cond_linha
				$cond_atendimento
				$cond_estado
				$cond_tipo_posto
				$cond_inspetor
				$cond_status
				$cond_posto
				$cond_status_pedido
				$ordena";   
	} else {
		 	 
		$sql = "SELECT DISTINCT
				$countDistinct
				CASE WHEN EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os_revenda.digitacao)) <= 5 THEN 0
				WHEN EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os_revenda.digitacao)) <= 10 THEN 6
				WHEN EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os_revenda.digitacao)) <= 15 THEN 11
				WHEN EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os_revenda.digitacao)) <= 20 THEN 16
				WHEN EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os_revenda.digitacao)) <= 25 THEN 21
				WHEN EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os_revenda.digitacao)) <= 30 THEN 26
				WHEN EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os_revenda.digitacao)) <= 60 THEN 31
				WHEN EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os_revenda.digitacao)) <= 90 THEN 61
				WHEN EXTRACT(DAYS FROM (CURRENT_TIMESTAMP - tbl_os_revenda.digitacao)) > 90  THEN 90
				END AS tempo,
				SUM(CURRENT_DATE - tbl_os_revenda.data_abertura) AS tma_abertura
				FROM tbl_os_revenda
				INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os_revenda.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE tbl_os_revenda.fabrica = $login_fabrica
				AND tbl_os_revenda.finalizada IS NULL
				AND tbl_os_revenda.posto <> 6359
				$cond_atendimento
				$cond_estado
				$cond_tipo_posto
				$cond_posto
				GROUP BY tempo
				ORDER BY tempo";
	}
	//die(nl2br($sql));
	$resSubmit = pg_query($con, $sql);
	
	if(pg_num_rows($resSubmit) > 0){
		$result = pg_fetch_all($resSubmit);
		$resultado = "true";
		$dias_grafico = array();
		$cor = array();
		$dados_column = array();
		$array_spline = array();
		$grafico_spline = array();
		$total = 0;

		foreach ($result as $key => $value) {
			$total +=$value['qtde'];
			switch ($value['tempo']) {
				case '0':
						$cor[0]['y'] 		= $value['qtde'];
						$cor[0]['color'] 	= '#468847';
					break;

				case '6':
						$cor[1]['y'] 		= $value['qtde'];
						$cor[1]['color'] 	= '#468847';
					break;

				case '11':
						$cor[2]['y']		= $value['qtde'];
						$cor[2]['color'] 	= '#468847';
					break;

				case '16':
						$cor[3]['y']	= $value['qtde'];
						$cor[3]['color']	= '#f89406';
					break;

				case '21':
						$cor[4]['y']	= $value['qtde'];
						$cor[4]['color']	= '#f89406';
					break;

				case '26':
						$cor[5]['y']	= $value['qtde'];
						$cor[5]['color']	= '#b94a48';
					break;

				case '31':
						$cor[6]['y']	= $value['qtde'];
						$cor[6]['color']	= '#b94a48';
					break;

				case '61':
						$cor[7]['y']	= $value['qtde'];
						$cor[7]['color']	= '#b94a48';
					break;

				case '90':
						$cor[8]['y']	= $value['qtde'];
						$cor[8]['color']	= '#b94a48';
					break;
			}
			$total_tma_abertura += $value["tma_abertura"];
		}

		$media_dias_tma_aberta = ($total_tma_abertura/$total);

		$dias_grafico[0] = "0-5 Dias";
		$dias_grafico[1] = "6-10 Dias";
		$dias_grafico[2] = "11-15 Dias";
		$dias_grafico[3] = "16-20 Dias";
		$dias_grafico[4] = "21-25 Dias";
		$dias_grafico[5] = "26-30 Dias";
		$dias_grafico[6] = "31-60 Dias";
		$dias_grafico[7] = "61-90 Dias";
		$dias_grafico[8] = "> 90 Dias";
		$xdias_grafico = $dias_grafico;
		$count_dias = count($dias_grafico);
		$dias_grafico = implode(',', $dias_grafico);

		###	Dados Column ###
		for ($i=0; $i < $count_dias; $i++) {
			$dados_column[$i]['y'] 		= 0;
			$dados_column[$i]['color'] 	= 0;
		}
		$xcor = $cor;
		$cor = array_merge_keys($dados_column,$cor);
		$value_column = json_encode($cor);
		### Fim Dados Column ###

		### Dados Spline ###
		for ($i=0; $i < $count_dias; $i++) {
			$array_spline[$i] = 0;
		}

		$tempo_key = array(
			0 => 0,
			6 => 1,
			11 => 2,
			16 => 3,
			21 => 4,
			26 => 5,
			31 => 6,
			61 => 7,
			90 => 8
		);
		$value_spline = array_fill(0, $count_dias, 0);

		foreach ($result as $row) {
			$value_spline[$tempo_key[$row["tempo"]]] = $row["qtde"];
		}
		foreach ($value_spline as $key => $value) {
			$porc = ($value/$total)*100;
			$porc = number_format($porc,'2','.','.');
			$grafico_spline[] = $porc;
		}
		$grafico_spline = array_merge_keys($array_spline,$grafico_spline);
		$xgrafico_spline = $grafico_spline;

		$grafico_spline = implode(',', $grafico_spline);
		### Fim Dados Spline ###
	}
}

$layout_menu = "gerencia";
$title = "RELATÓRIO DE OS x Atendimentos";
include 'cabecalho_new.php';

if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error">
	<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php } ?>

<div class='row-fluid'>
	<div class="alert alert-block">
		<h4>Atenção!</h4>
	  	Para visualizar o Aging de OSs fechadas é necessário informar a data inicial e final
	</div>
</div>
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
		<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
		<br/>
		<?php $msgFc = ($login_fabrica == 148) ? "" : "(Somente OSs fechadas)";  ?>
		<div class='row-fluid'>
			<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='data_inicial'>Data Inicial <?=$msgFc?></label>
						<div class='controls controls-row'>
							<div class='span4'>
								<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
							</div>
						</div>
					</div>
				</div>
			<div class='span2'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_final'>Data Final <?=$msgFc?></label>
					<div class='controls controls-row'>
						<div class='span8'>
							<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
						</div>
					</div>
				</div>
			</div>
			<?php if ($login_fabrica == 148) { ?>
					<div class="span2">
						<div class='control-group' >
							<label class="control-label" for="data_fx"></label>
							<div class="controls controls-row">
								<div class="span12">
									<input type="checkbox" name="data_fx" value="fechamento" <?= ($data_fx == "fechamento") ? "checked" : "" ?> /> Data Fechamemto
								</div>
							</div>
						</div>
					</div>
			<?php } ?>
		</div>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'>Código Posto</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_posto'>Nome Posto</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("tipo_atendimento", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='familia'>Tipo Atendimento</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<select name="tipo_atendimento[]" id="tipo_atendimento" multiple>
								<option value=""></option>
								<?php
									$sql = "SELECT tipo_atendimento, descricao
												FROM tbl_tipo_atendimento
												WHERE fabrica = $login_fabrica
												AND ativo order by descricao";
									$res = pg_query($con,$sql);
									foreach (pg_fetch_all($res) as $key) {
										$selected_tipo_atendimento = (in_array($key['tipo_atendimento'], $_POST['tipo_atendimento'])) ? "SELECTED" : '' ;
									?>
										<option value="<?php echo $key['tipo_atendimento']?>" <?php echo $selected_tipo_atendimento ?> >
											<?php echo $key['descricao']?>
										</option>
									<?php
									}
								?>
							</select>
						</div>
						<div class='span2'></div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("linha", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='linha'>Linha</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<select name="linha[]" id="linha" multiple>
								<option value=""></option>
								<?php
								$sql = "SELECT linha, nome FROM tbl_linha WHERE fabrica = {$login_fabrica} AND ativo;";
								$res = pg_query($con,$sql);

								foreach (pg_fetch_all($res) as $key) {
								    $selected_linha = (in_array($key['linha'], getValue('linha'))) ? "SELECTED" : '' ; ?>
								    <option value="<?= $key['linha']; ?>" <?= $selected_linha; ?> ><?= $key['nome']; ?>	</option>
								<?php } ?>
							</select>
						</div>
					</div>
				</div>
			</div>		
		</div>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class="span4" >
                        <div class="control-group" >
                            <label class="control-label" >Estado do Posto Autorizado</label>
                            <div class="controls controls-row" >
                                <div class="span4" >
                                    <select id="estado" name="estado[]" multiple >
                                        <?php
                                        foreach ($array_estados() as $sigla => $nome_estado) {
                                            $selected = (in_array($sigla, $_POST["estado"])) ? "selected" : "";
                                            echo "<option value='{$sigla}' {$selected} >{$nome_estado}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        </div>
			<div class="span4">
				<div class='control-group' >
					<label class="control-label" for="inspetor">Inspetor</label>
					<div class="controls controls-row">
						<div class="span4">
							<select name="inspetor[]" id="inspetor" multiple>
							    <option value=""></option>
							    <?php
                                        		    $sqlInspetor = "
                                            			SELECT admin, nome_completo, login
                                            			FROM tbl_admin
                                            			WHERE fabrica = {$login_fabrica}
                                            			AND ativo IS TRUE
                                            			AND admin_sap IS TRUE
                                            			ORDER BY login
                                        		    ";
                                        		    $resInspetor = pg_query($con, $sqlInspetor);

                                        		    while ($row = pg_fetch_object($resInspetor)) {
                                            			$descricao = (!empty($row->nome_completo)) ? $row->nome_completo : $row->login;
                                            			$selected = (in_array($row->admin, $_POST["inspetor"])) ? "selected" : "";
                                            			echo "<option value='{$row->admin}' {$selected} >{$descricao}</option>";
                                        		    }
                                        		    ?>
							</select>
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class="span4">
				<div class='control-group' >
					<label class="control-label" for="status_checkpoint">Status OS</label>
					<div class="controls controls-row">
						<div class="span4">
							<select name="status_checkpoint[]" multiple="multiple" id="status_checkpoint">
								<option value=""></option>
<?php
							    	$condStatus = ($login_fabrica == 148) ? "0,1,2,3,4,8,9,28" : "1,2,8,45,46,47,3,4,14,30,9,48,49,50,28";
								$sql_status = "SELECT status_checkpoint,descricao,cor FROm tbl_status_checkpoint WHERE status_checkpoint IN ($condStatus)";
				                    		$res_status = pg_query($con,$sql_status);
				                    		if (pg_num_rows($res_status) > 0){
									foreach (pg_fetch_all($res_status) as $key) {
									$selected_status = (in_array($key['status_checkpoint'], $_POST['status_checkpoint'])) ? "SELECTED" : '' ; ?>
									<option value="<?= $key['status_checkpoint']; ?>" <?= $selected_status; ?> >
										<?= $key['descricao']; ?>
									</option>
								<?php
									}
								}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>

			<?php
			if ($login_fabrica == 178) { ?>
				<div class="span6">
					<div class='control-group' >
						<label class="control-label" for="mae_filha"></label>
						<div class="controls controls-row">
							<div class="span3">
								<input type="radio" name="mae_filha" value="ambas" <?= ($mae_filha == "ambas" || !isset($_POST["btn_acao"])) ? "checked" : "" ?> /> Ambas
							</div>
							<div class="span3">
								<input type="radio" name="mae_filha" value="mae" <?= ($mae_filha == "mae") ? "checked" : "" ?> /> OSs Mães
							</div>
							<div class="span4">
								<input type="radio" name="mae_filha" value="filha" <?= ($mae_filha == "filha") ? "checked" : "" ?> /> OSs Filhas
							</div>
						</div>
					</div>
				</div>
			<?php
			} 
			if (in_array($login_fabrica, [167, 203])) { ?>
				<div class="span4" >
                    <div class="control-group" >
                        <label class="control-label" >Tipo do Posto Autorizado</label>
                        <div class="controls controls-row" >
                            <div class="span12" >
                                <select class="span12" id="tipo_posto" name="tipo_posto[]" multiple >
                                    <?php
                                    	$sql = "SELECT tipo_posto, descricao
									            FROM   tbl_tipo_posto
									            WHERE  tbl_tipo_posto.fabrica = $login_fabrica
									            AND tbl_tipo_posto.ativo = 't'
									            ORDER BY tbl_tipo_posto.descricao";
									    $res = pg_query($con, $sql);
									    $tipos_postos = pg_fetch_all($res);

	                                    foreach ($tipos_postos as $p => $desc) {
	                                        $selected = (in_array($desc['tipo_posto'], $_POST["tipo_posto"])) ? "selected" : "";
	                                        echo "<option value='".$desc['tipo_posto']."' {$selected} >".$desc['descricao']."</option>";
	                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
			<?php } 

            if(in_array($login_fabrica, [169,170])) {
        ?>
            <div class="span4">
                <div class="control-group" id="marca_bd">
                    <label class='control-label'>Pedido de Peça</label>
                    <div class="controls controls-row">
                    <div class="span4">
	                    <select name='pedido_peca[]' id='pedido_peca' class='tipo_posto_bd bd_sel' multiple="multiple">
	                        <option <?php echo (in_array('sem_pedido',$pedido_peca)) ? "selected" : ""; ?> value="sem_pedido">Os sem pedido de peça</option>
	                        <option <?php echo (in_array('com_pedido',$pedido_peca)) ? "selected" : ""; ?> value="com_pedido">Os com pedido de peça</option>
	                    </select>
	                </div>
	               </div>
                </div>
                <div class='span2'></div>
            </div>  
           	</div>
           	<? if($login_fabrica == 169) { 
       			if($calculo_dias == "uteis") {
       				$chk_uteis = "checked='checked'";
       			} else {
       				$chk_corridos = "checked='checked'";
       			}
           	?>
	            <div class='row-fluid'> 
	            <div class='span2'></div>         
	            <div class="span4">
	                <div class="control-group" id="marca_bd">
	                    <label class='control-label'>Cálculo de Dias</label>
	                    <div class="controls controls-row">
	                    <div class="span12">
	                        <label class="radio inline">
	                            <input type="radio" name="calculo_dias" value="uteis" <?=$chk_uteis ?> /> Úteis
	                        </label>
	                        <label class="radio inline">
	                            <input type="radio" name="calculo_dias" value="corridos" <?=$chk_corridos ?> /> Corridos
	                        </label>
		                </div>
		               </div>
	                </div>
	                <div class='span2'></div>
	            </div>
        <?
        		}
            }
        ?>
    	<div class='span2'></div>
		</div>

		<p><br/>
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p><br/>
</form>


<?php
if((pg_num_rows($resSubmit) > 0 && $login_fabrica != 148) || (pg_num_rows($resSubmit) > 0 && $login_fabrica == 148 && $status != 9)){
?>
	<div id="container" style="min-width: 310px; height: 400px; margin: 0 auto"></div>
<?php
}else{
	echo '<div class="container">
			<div class="alert">
			    <h4>Nenhum resultado encontrado para Ordem de Serviços Abertas</h4>
			</div>
		</div>';
}

if(pg_num_rows($resSubmitFechada) > 0){
?>
	<br/>
	<hr>
	<div id="container_fechada" style="min-width: 310px; height: 400px; margin: 0 auto"></div>

	<div class="container container-fluid">
	<br/>
		<div class="row-fluid">
			<div class="span6">
				<div id="container_dez" style="min-width: 310px; height: 300px; margin: 0 auto"></div>
			</div>

			<div class="span6">
				<div id="container_trinta" style="min-width: 310px; height: 300px; margin: 0 auto"></div>
			</div>
			
		</div>
	</div>
<?
}else{
	if(isset($_POST['btn_acao'])){
		echo '<div class="container">
			<div class="alert">
			    <h4>Nenhum resultado encontrado para Ordem de Serviços Fechadas</h4>
			</div>
		</div>';
	}

}
?>
<?php
	$dados_tabela_relatorio = array();
	for ($i=0; $i < $count_dias; $i++) { 
		$dados_tabela_relatorio[$i] = array(
			"dias" => $xdias_grafico[$i],
			"qtde_aberta" => $xcor[$i]["y"],
			"porcentagem_aberta" => $xgrafico_spline[$i],
			"qtde_fechada" => $xcor_fechada[$i]["y"],
			"porcentagem_fechada" => $xgrafico_spline_fechada[$i]
		);
	}

	if (in_array($login_fabrica, [178])) {
		ob_start();
	}
?>
<table id="tabela_relatorio" class="table table-bordered table-striped table-fixed" >
	<thead>
		<tr class="titulo_tabela">
			<th colspan="7">Tabela dados TMA</th>
		</tr>
		<tr class="titulo_coluna">
			<th>Dias</th>
			<th>Encerradas</th>
			<th>%</th>
			<th>Em aberto</th>
			<th>%</th>
			<th>TMA de encerramento</th>
			<th>TMA em aberto</th>
		</tr>
	</thead>
	<tbody>
		<?php
		foreach ($dados_tabela_relatorio as $key => $value) {

			if (empty($value["qtde_fechada"])){
				$value["qtde_fechada"] = 0;
			}
			if (empty($value["qtde_aberta"])){
				$value["qtde_aberta"] = 0;
			}

			$qtde_total_aberta += $value["qtde_aberta"];
			$qtde_total_fechada += $value["qtde_fechada"];
		?>
			<tr>
				<td class="tal"><?=$value["dias"]?></td>
				<td class="tac"><?=$value["qtde_fechada"]?></td>
				<td class="tac"><?=round($value["porcentagem_fechada"])?>%</td>
				<td class="tac"><?=$value["qtde_aberta"]?></td>
				<td class="tac"><?=round($value["porcentagem_aberta"])?>%</td>
				<?php
				if ($key < 1){
				?>
				 	<td class='tac'><?=round($media_dias_tma_fechada)?></td>
				 	<td class='tac'><?=round($media_dias_tma_aberta)?></td>
				<?php
				}else if ($key == 1 AND $key < 2 ){
				?>
				 	<td class='tac' rowspan='8'></td>
				 	<td class='tac' rowspan='8'></td>
				<?php
				}
				?>
			</tr>
		<?php
		}
		?>
	</tbody>
	<tfooter>
		<tr class="titulo_coluna">
			<th >TOTAL</th>
			<th><?=$qtde_total_fechada?></th>
			<th></th>
			<th><?=$qtde_total_aberta?></th>
			<th></th>
			<th colspan="3"></th>
		</tr>
	</tfooter>
</table>
<?php

if (in_array($login_fabrica, [178, 148])) {

	$head = ob_get_contents();
	$body = null;
	$data = date("d-m-Y-H:i");

	$fileName = "xls/relatorio-tma-{$data}.csv";

	$file = fopen("{$fileName}", "w");
	if($login_fabrica == 148){
		$head = "Posto;Nome Posto;Segmento;Mês;Ano;Processo;Série;Horas;Atendimento;Análise;M.O;KM;Valor Total Peças;Avulso;Abertura;Encerrada;Dias;\n";

		if (!empty($aux_data_inicial) && !empty($aux_data_final)) {
			if ($data_fx == "fechamento") {
				$cond_data = "AND tbl_os.data_fechamento BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'";
			} else {
				$cond_data = "AND tbl_os.data_digitacao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'";
			}
		}

		$sql_excel = "SELECT DISTINCT tbl_posto.nome,
							tbl_posto_fabrica.codigo_posto,
							tbl_linha.nome as linha,
							tbl_os.os,
							tbl_os.serie,
							tbl_os.qtde_hora,
							tbl_tipo_atendimento.descricao as atendimento,
							tbl_os.mao_de_obra,
							tbl_os.qtde_km,
							tbl_os.qtde_km_calculada,
							tbl_os.data_abertura,
							tbl_os.data_fechamento,
							(
								SELECT SUM(coalesce(tbl_os_item.custo_peca, 0)) 
								FROM tbl_os_produto 
								JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
								WHERE tbl_os_produto.os = tbl_os.os
								AND tbl_os_item.fabrica_i = tbl_os.fabrica
							) AS vl_total_pecas
							FROM tbl_os
							JOIN tbl_auditoria_os on tbl_auditoria_os.os = tbl_os.os
							JOIN tbl_posto on tbl_os.posto = tbl_posto.posto
							JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
							JOIN tbl_tipo_atendimento on tbl_os.tipo_atendimento = tbl_tipo_atendimento.tipo_atendimento
							JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
							JOIN tbl_produto ON tbl_os_produto.produto = tbl_produto.produto
							JOIN tbl_linha on tbl_produto.linha = tbl_linha.linha
							WHERE tbl_os.fabrica = {$login_fabrica}
							AND tbl_os.excluida IS NOT TRUE
							$cond_linha
							$cond_atendimento
							$cond_estado
							$cond_tipo_posto
							$cond_inspetor
							$cond_status
							$cond_posto
							$cond_status_pedido
							$cond_data
							ORDER BY tbl_posto_fabrica.codigo_posto, tbl_os.data_abertura";
		$res_excel = pg_query($con, $sql_excel);

		if(pg_num_rows($res_excel) > 0){
			setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
			date_default_timezone_set('America/Sao_Paulo');
			for($ex = 0; $ex < pg_num_rows($res_excel); $ex++){
				$statusDesc = null;

				$idOs = pg_fetch_result($res_excel, $ex, 'os');
				$sqlAud = "WITH datas_aud AS (
			                                    SELECT os,
			                                           auditoria_os,
			                                           CASE WHEN liberada NOTNULL THEN liberada
			                                           WHEN reprovada NOTNULL THEN reprovada
			                                           WHEN cancelada NOTNULL THEN cancelada
			                                           END AS data_auditoria
			                                    FROM tbl_auditoria_os
			                                    WHERE os = $idOs
			                              ),
			            ultima_aud AS     (
			                                    SELECT * 
			                                    FROM tbl_auditoria_os
			                                    WHERE auditoria_os = (SELECT auditoria_os FROM datas_aud ORDER BY data_auditoria DESC LIMIT 1)
			                              )
			            SELECT * FROM ultima_aud;";
			    $resAud = pg_query($con, $sqlAud);

			    $aprovada = "";
				$reprovada = "";
				$cancelada = "";
				$excepicional = "";
			    
			    if (pg_num_rows($resAud) > 0) {
					$aprovada = pg_fetch_result($resAud, 0, 'liberada');
					$reprovada = pg_fetch_result($resAud, 0, 'reprovada');
					$cancelada = pg_fetch_result($resAud, 0, 'cancelada');
					$excepicional = pg_fetch_result($resAud, 0, 'observacao');
			    }

			    $sqlMO = "SELECT SUM(valor) AS valor_avulso FROM tbl_extrato_lancamento WHERE fabrica = $login_fabrica AND os = $idOs";
			    $resMO = pg_query($con, $sqlMO);
			    $valor_avulso = 0;
			    if (pg_num_rows($resMO) > 0) {
			    	$valor_avulso = number_format(pg_fetch_result($resMO, 0, 'valor_avulso'),2,',','.');
			    }

			    $valor_total_pecas = number_format(pg_fetch_result($res_excel, $ex, 'vl_total_pecas'),2,',','.');

				$qtde_km_calculada = number_format(pg_fetch_result($res_excel, $ex, 'qtde_km_calculada') ,2,",",".");
				$mO                = number_format(pg_fetch_result($res_excel, $ex, 'mao_de_obra') ,2,",",".");

				$data_abertura = pg_fetch_result($res_excel, $ex, 'data_abertura');
				$data_fechamento = pg_fetch_result($res_excel, $ex, 'data_fechamento');
				
				$dt_ab = "";
				$dt_fc = "";

				if (!empty($data_abertura)) {
					$date  = new DateTime($data_abertura);
					$dt_ab = (new DateTime($data_abertura))->format('d-m-Y');
				}

				if (!empty($data_fechamento)) {
					$date2 = new DateTime($data_fechamento);
					$dias = $date->diff($date2);
					list($ano,$m,$d) = explode("-",$data_fechamento);
					$dt_fc = (new DateTime($data_fechamento))->format('d-m-Y');
				}
				
				if (strlen($aprovada) > 0){
					$statusDesc = "Procedente";
				} elseif(strlen($reprovada) > 0 || strlen($cancelada) > 0){ 
					$statusDesc = "Não Procedente";
				} else {
					$statusDesc = "Pendente";
				}

				$body .= "".str_replace(',', '', pg_fetch_result($res_excel, $ex, 'codigo_posto')) .";";
				$body .= "".str_replace(',','',pg_fetch_result($res_excel, $ex, 'nome')) .";";
				$body .= "".str_replace(',','',pg_fetch_result($res_excel, $ex, 'linha')) .";";
				$body .= "".$meses[$m].";";
				$body .= "".$ano.";";
				$body .= "".str_replace(',','',pg_fetch_result($res_excel, $ex, 'os')) .";";
				$body .= "".str_replace(',','',pg_fetch_result($res_excel, $ex, 'serie')) .";";
				$body .= "".str_replace(',','',pg_fetch_result($res_excel, $ex, 'qtde_hora')) .";";
				$body .= "".str_replace(',','',pg_fetch_result($res_excel, $ex, 'atendimento')) .";";
				$body .= "".$statusDesc .";";
				$body .= "".$mO.";";
				$body .= "".$qtde_km_calculada.";";
				$body .= "".$valor_total_pecas.";";
				$body .= "".$valor_avulso .";";
				$body .= "".$dt_ab .";";
				$body .= "".$dt_fc .";";
				$body .= "". $dias->days .";\n";
			}
		}
	}
	
	fwrite($file, $head . $body);
	fclose($file); ?>
<br />
<div class='btn_excel' onclick="window.open('<?= $fileName ?>')">
	<span><img src='imagens/excel.png' /></span>
	<span class="txt">Arquivo CSV</span>
</div>
<?php
}
$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable",
	"highcharts",
	"select2"
);

include("plugin_loader.php");
?>

<script type="text/javascript">

var hora = new Date();
var engana = hora.getTime();

$(function() {
	Shadowbox.init();
	$.datepickerLoad(Array("data_final", "data_inicial"));
	$.autocompleteLoad(Array("posto"));
});	

$("span[rel=lupa]").click(function () {
    $.lupa($(this));
});

$("select").select2();

var data_inicial 		= '<?=$aux_data_inicial?>';
var data_final 			= '<?=$aux_data_final?>';
var tipo_atendimento 	= '<?=$tipo_atendimento?>';
var linha 				= '<?=$linha?>';
var estado 				= '<?=$auxEstado?>';
var tipo_posto  		= '<?=$auxTipoPosto?>';
var inspetor 			= '<?=$inspetor?>';
var status 				= '<?=$status?>';
var fab                 = '<?=$login_fabrica?>';
var resultado 			= '<?=$resultado?>';
if (fab == 148 && status == 9) {
	resultado = 'false';
}
var resultado_fechada 	= '<?=$resultado_fechada?>';
var porc_dez_fechada    = '<?=$porc_dez_fechada?>';
var porc_trinta_fechada = '<?=$porc_trinta_fechada?>';
var posto 				= '<?=$posto?>';
var pedido_peca         = '<?=implode(",", $pedido_peca)?>';
var calculo_dias 		= '<?=$calculo_dias?>';
		//GRÁFICO OS ABERTAS
		if(resultado == 'true'){
			var dias_grafico = "<?=$dias_grafico?>" ;
			dias_grafico = dias_grafico.split(',');

			var grafico_spline = "<?=$grafico_spline?>";
			grafico_spline = grafico_spline.split(',');
			grafico_spline = grafico_spline.map(function(v){
				return parseInt(v);
			});

			var grafico_column = <?=$value_column?>;
			grafico_column = grafico_column.map(function(v){
				v.y = parseInt(v.y);
				return v;
			});
			Highcharts.chart('container', {
			    chart: {
			        zoomType: 'xy'
			    },
			    title: {
			        text: 'Ordens de Serviço em Aberto'
			    },
			    xAxis: [{
			        categories: dias_grafico,
			        crosshair: true
			    }],
			    yAxis: [{ // Primary yAxis
			        labels: {
			            /*format: '{value}°C',*/
			            style: {
			                color: Highcharts.getOptions().colors[1]
			            }
			        },
			        title: {
			            text: 'Quantidade',
			            style: {
			                color: Highcharts.getOptions().colors[1]
			            }
			        }
			    }, { // Secondary yAxis
			        title: {
			            text: 'Percentual',
			            style: {
			                color: Highcharts.getOptions().colors[0]
			            }
			        },
			        labels: {
			            format: '{value} %',
			            style: {
			                color: Highcharts.getOptions().colors[0]
			            }
			        },
			        opposite: true
			    }],
			    tooltip: {
			        shared: true
			    },
			    legend: {
			        layout: 'vertical',
			        align: 'left',
			        x: 120,
			        verticalAlign: 'top',
			        y: 100,
			        floating: true,
			        backgroundColor: (Highcharts.theme && Highcharts.theme.legendBackgroundColor) || '#FFFFFF'
			    },
			    plotOptions: {
			        column: {
			            borderWidth: 0,
			            dataLabels: {
			            	enabled: true,
			                format: ' '
			                //format: '{point.y:.0f} Qtde'
			            },
			            cursor: 'pointer',
			            events: {
			                click: function (event) {
			                	var url = "detalhes_tma.php?dias="+event.point.category+"&tipo_atendimento="+tipo_atendimento+"&linha="+linha+"&estado="+estado+"&inspetor="+inspetor+"&status="+status+"&posto="+posto+"&pedido_peca="+pedido_peca+"&tipo_posto="+tipo_posto+"&calculo_dias="+calculo_dias;
			                    Shadowbox.open({
						            content:url,
						            player: "iframe",
						            title:  "Ordens de Serviço",
						            width:  800,
						            height: 500
						        });
			                }
			            }
			        },
			        spline: {
			            borderWidth: 0,
			            dataLabels: {
			            	enabled: true,
			                format: '{point.y:.0f}%'
			            }
			        }
			    },
			    series: [{
			    	showInLegend: false,
			        name: 'Quantidade',
			        type: 'column',
			        data: grafico_column,
			        tooltip: {
			            valueSuffix: ''
			        }

			    }, {
			    	showInLegend: false,
			        name: 'Percentual',
			        type: 'spline',
			        yAxis: 1,
			        data: grafico_spline,
			        tooltip: {
			            valueSuffix: '%'
			        }
			    }]
			});
		}

		//GRÁFICO OS FECHADA
		if(resultado_fechada == 'true'){
			var dias_grafico_fechada = "<?=$dias_grafico_fechada?>" ;
			dias_grafico_fechada = dias_grafico_fechada.split(',');

			var grafico_spline_fechada = "<?=$grafico_spline_fechada?>";
			grafico_spline_fechada = grafico_spline_fechada.split(',');
			grafico_spline_fechada = grafico_spline_fechada.map(function(v){
				return parseInt(v);
			});

			var grafico_column_fechada = "";
			<?php if(!empty($value_column_fechada)){ ?>
				grafico_column_fechada = <?=$value_column_fechada?>;
			<?php } ?>
			grafico_column_fechada = grafico_column_fechada.map(function(v){
				v.y = parseInt(v.y);
				return v;
			});
			Highcharts.chart('container_fechada', {
			    chart: {
			        zoomType: 'xy'
			    },
			    title: {
			        text: 'Ordens de Serviço Fechada'
			    },
			    xAxis: [{
			        categories: dias_grafico_fechada,
			        crosshair: true
			    }],
			    yAxis: [{ // Primary yAxis
			        labels: {
			            /*format: '{value}°C',*/
			            style: {
			                color: Highcharts.getOptions().colors[1]
			            }
			        },
			        title: {
			            text: 'Quantidade',
			            style: {
			                color: Highcharts.getOptions().colors[1]
			            }
			        }
			    }, { // Secondary yAxis
			        title: {
			            text: 'Percentual',
			            style: {
			                color: Highcharts.getOptions().colors[0]
			            }
			        },
			        labels: {
			            format: '{value} %',
			            style: {
			                color: Highcharts.getOptions().colors[0]
			            }
			        },
			        opposite: true
			    }],
			    tooltip: {
			        shared: true
			    },
			    legend: {
			        layout: 'vertical',
			        align: 'left',
			        x: 120,
			        verticalAlign: 'top',
			        y: 100,
			        floating: true,
			        backgroundColor: (Highcharts.theme && Highcharts.theme.legendBackgroundColor) || '#FFFFFF'
			    },
			    plotOptions: {
			        column: {
			            borderWidth: 0,
			            dataLabels: {
			            	enabled: true,
			            	format: ' '
			                //format: '{point.y:.0f} Qtde'
			            },
			            cursor: 'pointer',
			            events: {
			                click: function (event) {
			                	var url = "detalhes_tma.php?dias="+event.point.category+"&data_inicial="+data_inicial+"&data_final="+data_final+"&tipo_atendimento="+tipo_atendimento+"&linha="+linha+"&estado="+estado+"&inspetor="+inspetor+"&posto="+posto+"&pedido_peca="+pedido_peca+"&tipo_posto="+tipo_posto+"&calculo_dias="+calculo_dias;
			                    Shadowbox.open({
						            content:url,
						            player: "iframe",
						            title:  "Ordens de Serviço",
						            width:  800,
						            height: 500
						        });
			                }
			            }
			        },
			        spline: {
			            borderWidth: 0,
			            dataLabels: {
			            	enabled: true,
			                format: '{point.y:.0f}%'
			            }
			        }
			    },
			    series: [{
			    	showInLegend: false,
			        name: 'Quantidade',
			        type: 'column',
			        data: grafico_column_fechada,
			        tooltip: {
			            valueSuffix: ''
			        }

			    }, {
			    	showInLegend: false,
			        name: 'Percentual',
			        type: 'spline',
			        yAxis: 1,
			        data: grafico_spline_fechada,
			        tooltip: {
			            valueSuffix: '%'
			        }
			    }]
			});
		}

		if(resultado_fechada == 'true'){
			porc_dez_fechada = parseInt(porc_dez_fechada);
			porc_trinta_fechada = parseInt(porc_trinta_fechada);

			Highcharts.chart('container_dez', {

			    chart: {
			        type: 'gauge',
			        plotBackgroundColor: null,
			        plotBackgroundImage: null,
			        plotBorderWidth: 0,
			        plotShadow: false
			    },

			    title: {
			        text: 'Os encerradas dentro de 10 dias'
			    },

			    pane: {
			        startAngle: -90,
			        endAngle: 90,
			        background: null
			    },

			    // the value axis
			    yAxis: {
			    
			        min: 0,
			        max: 100,

			        minorTickInterval: 'auto',
			        minorTickWidth: 0,
			        minorTickLength: 0,
			        minorTickPosition: 'inside',
			        minorTickColor: '#666',

			        tickPixelInterval: 0,
			        tickWidth: 0,
			        tickPosition: 'inside',
			        tickLength: 0,
			        tickColor: '#666',
			        labels: {
			            step: 2,
			            rotation: 'auto'
			        },
			        title: {
			            text: ''
			        },
			        plotBands: [{
			            from: 0,
			            to: 25,
			            color: '#CC0000',
			            outerRadius: '100%',
			            thickness: '40%'
			        }, {
			            from: 25,
			            to: 45,
			            color: '#CC8800',
			            outerRadius: '100%',
			            thickness: '40%'
			        }, {
			            from: 45,
			            to: 50,
			            color: '#88CC88',
			            outerRadius: '100%',
			            thickness: '40%'
			        }, {
			          from: 50,
			          to: 100,
			          color: '#00CC00',
			          outerRadius: '100%',
			            thickness: '40%'
			        }]
			    },

			    series: [{
			        name: 'Os encerradas dentro de 10 dias',
			        data: [porc_dez_fechada],
			        tooltip: {
			            valueSuffix: '%'
			        }
			    }]

			});

			Highcharts.chart('container_trinta', {

			    chart: {
			        type: 'gauge',
			        plotBackgroundColor: null,
			        plotBackgroundImage: null,
			        plotBorderWidth: 0,
			        plotShadow: false
			    },

			    title: {
			        text: 'Os encerradas dentro de 30 dias'
			    },

			    pane: {
			        startAngle: -90,
			        endAngle: 90,
			        background: null
			    },

			    // the value axis
			    yAxis: {
			    
			        min: 0,
			        max: 100,

			        minorTickInterval: 'auto',
			        minorTickWidth: 0,
			        minorTickLength: 0,
			        minorTickPosition: 'inside',
			        minorTickColor: '#666',

			        tickPixelInterval: 0,
			        tickWidth: 0,
			        tickPosition: 'inside',
			        tickLength: 0,
			        tickColor: '#666',
			        labels: {
			            step: 2,
			            rotation: 'auto'
			        },
			        title: {
			            text: ''
			        },
			        plotBands: [{
			            from: 0,
			            to: 25,
			            color: '#CC0000',
			            outerRadius: '100%',
			            thickness: '40%'
			        }, {
			            from: 25,
			            to: 45,
			            color: '#CC8800',
			            outerRadius: '100%',
			            thickness: '40%'
			        }, {
			            from: 45,
			            to: 50,
			            color: '#88CC88',
			            outerRadius: '100%',
			            thickness: '40%'
			        }, {
			          from: 50,
			          to: 100,
			          color: '#00CC00',
			          outerRadius: '100%',
			            thickness: '40%'
			        }]
			    },

			    series: [{
			        name: 'Os encerradas dentro de 30 dias',
			        data: [porc_trinta_fechada],
			        tooltip: {
			            valueSuffix: '%'
			        }
			    }]

			});
		}

	function retorna_posto(retorno){
            $("#codigo_posto").val(retorno.codigo);
	    $("#descricao_posto").val(retorno.nome);
	}
</script>
<?php
include 'rodape.php';
?>
