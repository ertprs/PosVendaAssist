<?php
	
	include "dbconfig.php";
	include "includes/dbconnect-inc.php";

	$admin_privilegios = "cadastros";
	include "autentica_admin.php";

	include "funcoes.php";

	/* S3 Upload */
	include_once S3CLASS;
    $s3 = new AmazonTC("analise_peca", $login_fabrica);

	$msg = "";
	$msg_erro = array();

	if(isset($_POST["verifica_serie"])){

		$serie = $_POST["serie"];

		$sql = "SELECT os FROM tbl_os WHERE serie = '{$serie}' AND fabrica = {$login_fabrica}";
		$res = pg_query($con, $sql);

		if(pg_num_rows($res) > 0){

			$retorno = "OS(s) aberta(s) com o mesmo número de Série: ";
			$row = pg_num_rows($res);

			for($i = 0; $i < $row; $i++){ 

				$os = pg_fetch_result($res, $i, "os");
				
				$retorno .= "<a href='os_press.php?os={$os}' target='_blank'>{$os}</a>";
				$retorno .= ($i < ($row - 1)) ? ", " : "";

			}

		}else{
			$retorno = false;
		}

		exit(json_encode(array("retorno" => utf8_encode($retorno))));

	}

	if(isset($_GET["analise_peca"])){

		$analise_peca = trim($_GET["analise_peca"]);

		$sql = "SELECT 
					posto,
					data_abertura,
					nota_fiscal,
					data_nf,
					origem_recebimento,
					tecnico,
					termino_analise,
					inicio_analise,
					status_analise_peca,
					data_entrega,
					autorizacao,
					responsavel_recebimento,
					nf_saida,
					data_nf_saida,
					volume,
					termino_final,
					observacao 
				FROM tbl_analise_peca 
				WHERE 
					analise_peca = {$analise_peca} 
					AND fabrica = {$login_fabrica}";
		$res = pg_query($con, $sql);

		if(pg_num_rows($res) > 0){

			$posto                   = pg_fetch_result($res, 0, "posto");
			$data_abertura           = pg_fetch_result($res, 0, "data_abertura");
			$nota_fiscal             = pg_fetch_result($res, 0, "nota_fiscal");
			$data_nota_fiscal        = pg_fetch_result($res, 0, "data_nf");
			$origem_recebimento      = pg_fetch_result($res, 0, "origem_recebimento");
			$tecnico                 = pg_fetch_result($res, 0, "tecnico");
			$inicio_analise          = pg_fetch_result($res, 0, "inicio_analise");
			$termino_analise         = pg_fetch_result($res, 0, "termino_analise");
			$posicao_peca            = pg_fetch_result($res, 0, "status_analise_peca");
			$data_entrega_expedicao  = pg_fetch_result($res, 0, "data_entrega");
			$autorizado              = pg_fetch_result($res, 0, "autorizacao");
			$responsavel_recebimento = pg_fetch_result($res, 0, "responsavel_recebimento");
			$nota_fiscal_saida       = pg_fetch_result($res, 0, "nf_saida");
			$data_nf_saida       	 = pg_fetch_result($res, 0, "data_nf_saida");
			$volume                  = pg_fetch_result($res, 0, "volume");
			$observacao              = pg_fetch_result($res, 0, "observacao");
			$termino_final           = pg_fetch_result($res, 0, "termino_final");

			if(strlen($inicio_analise)){

				list($data, $horas)    = explode(" ", $inicio_analise);
				list($horas, $mls)     = explode(".", $horas);
				list($h, $m, $s)       = explode(":", $horas);
				list($ano, $mes, $dia) = explode("-", $data);

				$data_inicio_analise = $dia."/".$mes."/".$ano;
				$hora_inicio_analise = $h.":".$m;

			}

			if(strlen($posto) > 0){

				$sql = "SELECT 
								tbl_posto_fabrica.codigo_posto, 
								tbl_posto.nome 
							FROM tbl_posto 
							INNER JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto 
							WHERE 
								tbl_posto_fabrica.posto = {$posto} 
								AND tbl_posto_fabrica.fabrica = {$login_fabrica}";
				$res = pg_query($con, $sql);

				$codigo_posto = pg_fetch_result($res, 0, "codigo_posto");
				$descricao_posto = pg_fetch_result($res, 0, "nome");

			}

			$sql = "SELECT 
						peca,
						numero_serie,
						lote,
						qtde,
						laudo_defeito_constatado,
						laudo_analise,
						procede_reclamacao,
						garantia,
						laudo_apos_reparo,
						enviar_peca_nova,
						sucatear_peca,
						baixa_no_estoque,
						lancar_no_clain,
						gasto_nao_justifica_devolucao 
					FROM tbl_analise_peca_item 
					WHERE analise_peca = {$analise_peca}";
			$res = pg_query($con, $sql);

			$produtos_pecas = array();

			for($i = 0; $i < pg_num_rows($res); $i++){

				$peca                                 = pg_fetch_result($res, $i, "peca");
				$numero_serie                         = pg_fetch_result($res, $i, "numero_serie");
				$lote                                 = pg_fetch_result($res, $i, "lote");
				$qtde                                 = pg_fetch_result($res, $i, "qtde");
				$laudo_defeito_constatado             = pg_fetch_result($res, $i, "laudo_defeito_constatado");
				$laudo_analise                        = pg_fetch_result($res, $i, "laudo_analise");
				$procede_reclamacao                   = pg_fetch_result($res, $i, "procede_reclamacao");
				$garantia                             = pg_fetch_result($res, $i, "garantia");
				$laudo_apos_reparo                    = pg_fetch_result($res, $i, "laudo_apos_reparo");
				$enviar_peca_nova                     = pg_fetch_result($res, $i, "enviar_peca_nova");
				$sucatear_peca                        = pg_fetch_result($res, $i, "sucatear_peca");
				$baixa_no_estoque                     = pg_fetch_result($res, $i, "baixa_no_estoque");
				$lancar_no_clain                      = pg_fetch_result($res, $i, "lancar_no_clain");
				$gasto_nao_justificavel_com_devolucao = pg_fetch_result($res, $i, "gasto_nao_justifica_devolucao");

				$sql_peca = "SELECT referencia, descricao, produto_acabado FROM tbl_peca WHERE peca = {$peca} AND fabrica = {$login_fabrica}";
				$res_peca = pg_query($con, $sql_peca);

				$referencia = pg_fetch_result($res_peca, 0, "referencia");
				$descricao  = pg_fetch_result($res_peca, 0, "descricao");
				$categoria  = (pg_fetch_result($res_peca, 0, "produto_acabado") == "t") ? "produto" : "peca";

				$produtos_pecas[] = array(
					"referencia"                           => $referencia,
					"descricao"                            => $descricao,
					"categoria"                            => $categoria,
					"numero_serie"                         => $numero_serie,
					"numero_lote"                          => $lote,
					"quantidade"                           => $qtde,
					"defeito_constatado"                   => $laudo_defeito_constatado,
					"resultado_analise"                    => $laudo_analise,
					"procede_reclamacao"                   => $procede_reclamacao,
					"garantia"                             => $garantia,
					"laudo_apos_reparo"                    => $laudo_apos_reparo,
					"enviar_peca_nova"                     => $enviar_peca_nova,
					"sucatear_peca"                        => $sucatear_peca,
					"baixa_no_estoque"                     => $baixa_no_estoque,
					"lancar_no_clain"                      => $lancar_no_clain,
					"gasto_nao_justificavel_com_devolucao" => $gasto_nao_justificavel_com_devolucao
				);

			}

			$data_abertura          = form_data($data_abertura, "-");
			$data_nota_fiscal       = form_data($data_nota_fiscal, "-");
			$data_entrega           = form_data($data_entrega, "-");
			$termino_analise        = (strlen($termino_analise) > 0) ? form_data_hora($termino_analise) : "";
			$data_nf_saida          = form_data($data_nf_saida, "-");
			$data_entrega_expedicao = form_data($data_entrega_expedicao, "-");

		}else{

			$msg_erro["msg"]["obg"] = "Nenhuma Análise de Peças Localizada com esse código - {$analise_peca}";

		}

	}

	if(isset($_POST["excluir_analise"])){

		$analise_peca = $_POST["analise_peca"];

		$sql = "DELETE FROM tbl_analise_peca_item WHERE analise_peca = {$analise_peca}; DELETE FROM tbl_analise_peca WHERE analise_peca = {$analise_peca} AND fabrica = {$login_fabrica};";
		$res = pg_query($con, $sql);

		return true;

	}

	if(isset($_POST["finalizar_analise"])){

		$analise_peca = $_POST["analise_peca"];

		$sql = "UPDATE tbl_analise_peca SET termino_final = CURRENT_TIMESTAMP,  admin_termino = $login_admin WHERE analise_peca = {$analise_peca} AND fabrica = {$login_fabrica}";
		$res = pg_query($con, $sql);

		$result = (strlen(pg_last_error()) > 0) ? false : true;

		if($result == true){

			$sql = "SELECT termino_final FROM tbl_analise_peca WHERE analise_peca = {$analise_peca} AND fabrica = {$login_fabrica}";
			$res = pg_query($con, $sql);

			$termino_analise = form_data_hora(pg_fetch_result($res, 0, "termino_final"));

		}

		exit(json_encode(array("retorno" => $result, "termino_analise" => utf8_encode($termino_analise))));

	}

	if(isset($_POST["reabrir_analise"])){

		$analise_peca = $_POST["analise_peca"];
		$motivo_admin = $_POST["motivo"];

		$sql = "UPDATE tbl_analise_peca SET termino_final = NULL, admin_termino = NULL WHERE analise_peca = {$analise_peca} AND fabrica = {$login_fabrica}";
		$res = pg_query($con, $sql);

		/* Motivo da Reabertura */
		$sql = "SELECT motivo FROM tbl_analise_peca WHERE analise_peca = {$analise_peca} AND fabrica = {$login_fabrica}";
		$res = pg_query($con, $sql);

		$motivo = pg_fetch_result($res, 0, "motivo");

		if(strlen($motivo) > 0){

			$motivo = json_decode($motivo, true);

		}

		$motivo[] = array(
				"admin" 		=> $login_admin,
				"motivo" 		=> $motivo_admin,
				"data" 			=> date("d/m/Y H:m:s"),
				"analise_peca" 	=> $analise_peca
			);

		$motivo = str_replace("\\", "\\\\", utf8_encode(json_encode($motivo)));

		$sql = "UPDATE tbl_analise_peca SET motivo = '{$motivo}' WHERE analise_peca = {$analise_peca} AND fabrica = {$login_fabrica}";
		$res = pg_query($con, $sql);

		$result = (strlen(pg_last_error()) > 0) ? false : true;

		exit(json_encode(array("retorno" => $result)));

	}

	/* Form Action */

	function valida_qtde_produtos_pecas($produtos_pecas = array()){
		global $login_fabrica, $con;
		$cont_pecas = 0; 

		foreach ($produtos_pecas as $key => $value) {

			if(is_numeric($key)){
				if(!empty($value["referencia"]) && !empty($value["descricao"]) && $value["excluido"] != "sim"){
					$sql_peca = "SELECT peca FROM tbl_peca WHERE referencia = '{$value["referencia"]}' AND fabrica = {$login_fabrica}";
					$res_peca = pg_query($con, $sql_peca);
					if (pg_num_rows($res_peca) > 0) {
						$cont_pecas++;
					}
				}
			}

		}

		return ($cont_pecas == 0) ? false : true;

	}

	function valida_date($data = ""){

		if(strlen($data) > 0){

			list($dia, $mes, $ano) = explode("/", $data);
			return checkdate($mes, $dia, $ano);

		}

	}

	function valida_hora($hora = ""){

		if(strlen($hora) > 0){

			list($horas, $minutos) = explode(":", $hora);

			if((int)$horas > 24 || (int)$minutos > 60){
				return false;
			}

		}

		return true;

	}

	function form_data($data, $format = ""){

		if($format == "-"){

			list($a, $m, $d) = explode("-", $data);
			$data = $d."/".$m."/".$a;

		}else{

			list($d, $m, $a) = explode("/", $data);
			$data = $a."-".$m."-".$d;

		}

		return $data;

	}

	function form_data_hora($data){

		list($data, $hora) = explode(" ", $data);

		list($a, $m, $d) = explode("-", $data);

		list($hms, $ml) = explode(".", $hora);

		list($horas, $minutos, $segundos) = explode(":", $hms);

		return $d."/".$m."/".$a." às ".$horas.":".$minutos."hs";

	}

	function insere_pecas($produtos_pecas, $analise_peca){

		global $login_fabrica, $con;

		foreach ($produtos_pecas as $key => $value) {

			if(is_numeric($key)){

				$categoria                            = $value["categoria"];
				$referencia                           = $value["referencia"];
				$descricao                            = $value["descricao"];
				$numero_serie                         = $value["numero_serie"];
				$numero_lote                          = $value["numero_lote"];
				$quantidade                           = $value["quantidade"];
				$defeito_constatado                   = $value["defeito_constatado"];
				$resultado_analise                    = $value["resultado_analise"];
				
				$procede_reclamacao                   = $value["procede_reclamacao"];
				$garantia                             = $value["garantia"];
				$laudo_apos_reparo                    = $value["laudo_apos_reparo"];
				$enviar_peca_nova                     = $value["enviar_peca_nova"];
				$sucatear_peca                        = $value["sucatear_peca"];
				$baixa_no_estoque                     = $value["baixa_no_estoque"];
				$lancar_no_clain                      = $value["lancar_no_clain"];
				$gasto_nao_justificavel_com_devolucao = $value["gasto_nao_justificavel_com_devolucao"];

				$excluido 					          = $value["excluido"];

				if(empty($referencia) || $excluido == "sim"){
					continue;
				}

				$sql_peca = "SELECT peca FROM tbl_peca WHERE referencia = '{$referencia}' AND fabrica = {$login_fabrica}";
				$res_peca = pg_query($con, $sql_peca);

				$peca = pg_fetch_result($res_peca, 0, "peca");
				$quantidade = (strlen($quantidade) == 0) ? 0 : $quantidade;

				$sql_item = "INSERT INTO tbl_analise_peca_item 
							(
								analise_peca,
								peca,
								numero_serie,
								lote,
								qtde,
								laudo_defeito_constatado,
								laudo_analise,
								procede_reclamacao,
								garantia,
								laudo_apos_reparo,
								enviar_peca_nova,
								sucatear_peca,
								baixa_no_estoque,
								lancar_no_clain,
								gasto_nao_justifica_devolucao
							) 
							VALUES 
							(
								$analise_peca,
								$peca,
								'$numero_serie',
								'$numero_lote',
								$quantidade,
								'$defeito_constatado',
								'$resultado_analise',
								'$procede_reclamacao',
								'$garantia',
								'$laudo_apos_reparo',
								'$enviar_peca_nova',
								'$sucatear_peca',
								'$baixa_no_estoque',
								'$lancar_no_clain',
								'$gasto_nao_justificavel_com_devolucao'
							)";
				$res_item = pg_query($con, $sql_item);

			}

		}

	}

	function deleta_pecas($analise_peca){

		global $con;

		$sql = "DELETE FROM tbl_analise_peca_item WHERE analise_peca = {$analise_peca}";
		$res = pg_query($con, $sql);

	}

	function get_data_analise_peca($analise_peca, $opt = ""){

		global $con, $login_fabrica;

		$sql = "SELECT DATE(inicio_analise) AS data FROM tbl_analise_peca WHERE analise_peca = {$analise_peca} AND fabrica = {$login_fabrica}";
		$res = pg_query($con, $sql);

		$data = pg_fetch_result($res, 0, "data");
		list($ano, $mes, $dia) = explode("-", $data);

		if($opt == "mes"){
			return $mes;
		}

		if($opt == "ano"){
			return $ano;
		}

		return false;

	}

	if(isset($_POST["btn_acao"])){
		
		$posto                   = $_POST["posto"];
		$codigo_posto            = $_POST["codigo_posto"];
		$descricao_posto         = $_POST["descricao_posto"];
		$data_abertura           = $_POST["data_abertura"];
		$data_inicio_analise     = $_POST["data_inicio_analise"];
		$hora_inicio_analise     = $_POST["hora_inicio_analise"];
		$nota_fiscal             = $_POST["nota_fiscal"];
		$data_nota_fiscal        = $_POST["data_nota_fiscal"];
		$origem_recebimento      = $_POST["origem_recebimento"];
		$autorizado              = $_POST["autorizado"];
		$tecnico                 = $_POST["tecnico"];
		$posicao_peca            = $_POST["posicao_peca"];
		$data_entrega_expedicao  = $_POST["data_entrega_expedicao"];
		$responsavel_recebimento = $_POST["responsavel_recebimento"];
		$nota_fiscal_saida       = $_POST["nota_fiscal_saida"];
		$data_nf_saida           = $_POST["data_nf_saida"];
		$volume                  = $_POST["volume"];
		$observacao              = $_POST["observacao"];
		
		$analise_peca            = $_POST["analise_peca"];
		
		$produtos_pecas          = $_POST["produtos_pecas"];
		
		/* Anexos */
		$anexo_analise           = $_POST["anexo"];
		$anexo_analise_s3        = $_POST["anexo_s3"];

		if(empty($posto)){
			$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
        	$msg_erro["campos"][]   = "posto";
		}

		if(empty($data_abertura)){
			$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
        	$msg_erro["campos"][]   = "data_abertura";
		}

		if(strlen($analise_peca) > 0){

			if(empty($data_inicio_analise)){
				$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
	        	$msg_erro["campos"][]   = "data_inicio_analise";
			}

			if(empty($hora_inicio_analise)){
				$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
	        	$msg_erro["campos"][]   = "hora_inicio_analise";
			}

		}

		if(strlen($data_inicio_analise) > 0){

			if(empty($hora_inicio_analise)){
				$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
	        	$msg_erro["campos"][]   = "hora_inicio_analise";
			}

		}

		if(empty($nota_fiscal)){
			$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
        	$msg_erro["campos"][]   = "nota_fiscal";
		}

		if(empty($data_nota_fiscal)){
			$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
        	$msg_erro["campos"][]   = "data_nota_fiscal";
		}

		if(empty($origem_recebimento)){
			$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
        	$msg_erro["campos"][]   = "origem_recebimento";
		}

		if(empty($autorizado)){
			$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
        	$msg_erro["campos"][]   = "autorizado";
		}

		if(empty($tecnico)){
			$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
        	$msg_erro["campos"][]   = "tecnico";
		}

		if(empty($tecnico)){
			$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
        	$msg_erro["campos"][]   = "tecnico";
		}

		if(empty($posicao_peca)){
			$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
        	$msg_erro["campos"][]   = "posicao_peca";
		}

		if(strlen($posicao_peca) > 0){

			$sql = "SELECT descricao FROM tbl_status_analise_peca WHERE fabrica = {$login_fabrica} AND status_analise_peca = {$posicao_peca}";
			$res = pg_query($con, $sql);

			$desc_posicao_peca = pg_fetch_result($res, 0, "descricao");

			if(strstr(strtolower($desc_posicao_peca), "2")){

				if(empty($data_entrega_expedicao)){
					$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
		        	$msg_erro["campos"][]   = "data_entrega_expedicao";
				}

				if(empty($responsavel_recebimento)){
					$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
		        	$msg_erro["campos"][]   = "responsavel_recebimento";
				}

			}

			if(strstr(strtolower($desc_posicao_peca), "3")){

				if(empty($nota_fiscal_saida)){
					$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
		        	$msg_erro["campos"][]   = "nota_fiscal_saida";
				}

				if(empty($data_nf_saida)){
					$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
		        	$msg_erro["campos"][]   = "data_nf_saida";
				}

				if(empty($volume)){
					$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
		        	$msg_erro["campos"][]   = "volume";
				}

			}

		}

		if(!empty($data_abertura)){
			if(valida_date($data_abertura) == false){
				$msg_erro["msg"][]    = "A data de Abertura é invalida";
				$msg_erro["campos"][] = "data_abertura";
			}
		}

		if(!empty($data_inicio_analise)){
			if(valida_date($data_inicio_analise) == false){
				$msg_erro["msg"][]    = "A data de Início de Análise é invalida";
				$msg_erro["campos"][] = "data_inicio_analise";
			}
		}

		if(!empty($data_nota_fiscal)){
			if(valida_date($data_nota_fiscal) == false){
				$msg_erro["msg"][]    = "A data da Nota Fiscal é invalida";
				$msg_erro["campos"][] = "data_nota_fiscal";
			}
		}

		if(!empty($data_entrega_expedicao)){
			if(valida_date($data_entrega_expedicao) == false){
				$msg_erro["msg"][]    = "A data de Entrega à Expedição é invalida";
				$msg_erro["campos"][] = "data_entrega_expedicao";
			}
		}

		if(!empty($data_nf_saida)){
			if(valida_date($data_nf_saida) == false){
				$msg_erro["msg"][]    = "A data de Nota Fiscal de Saída é invalida";
				$msg_erro["campos"][] = "data_nf_saida";
			}
		}

		if(!empty($hora_inicio_analise)){
			if(valida_hora($hora_inicio_analise) == false){
				$msg_erro["msg"][]    = "A hora de Início de Análise é invalida";
				$msg_erro["campos"][] = "hora_inicio_analise";
			}
		}

		if(valida_qtde_produtos_pecas($produtos_pecas) == false){
			$msg_erro["msg"][] = "Por favor insira ao menos 1 peça válida para o cadastro da análise";
		}

		if(strlen($analise_peca) > 0){

			$sql = "SELECT termino_final FROM tbl_analise_peca WHERE analise_peca = {$analise_peca} AND fabrica = {$login_fabrica}";
			$res = pg_query($con, $sql);

			$termino_final = pg_fetch_result($res, 0, "termino_final");

			if(strlen($termino_final) > 0){

				$msg_erro["msg"]["obg"] = "Esta Análise de Peças já foi Finalizada, clique no botão Reabrir para alterar.";
			}

		}

		if(count($msg_erro["msg"]) == 0){

			$data_abertura_db         = form_data($data_abertura);
			$data_nota_fiscal_db      = form_data($data_nota_fiscal);
			$data_entrega             = (strlen($data_entrega_expedicao) > 0) ? form_data($data_entrega_expedicao) : "";
			$data_nf_saida_db         = (strlen($data_nf_saida) > 0) ? form_data($data_nf_saida) : "";
			$data_hora_inicio_analise = (strlen($data_inicio_analise) > 0) ? form_data($data_inicio_analise)." ".$hora_inicio_analise : "";
			
			if(strlen($analise_peca) == 0){

				$acao = "insert";

				$sql = "INSERT INTO tbl_analise_peca 
				(
					fabrica,
					posto,
					data_abertura,
					".((strlen($data_hora_inicio_analise) > 0) ? "inicio_analise," : "")."
					nota_fiscal,
					data_nf,
					origem_recebimento,
					tecnico,
					observacao,
					status_analise_peca,
					".((strlen($data_entrega) > 0) ? "data_entrega," : "")."
					autorizacao,
					responsavel_recebimento,
					nf_saida,
					".((strlen($data_nf_saida) > 0) ? "data_nf_saida," : "")."
					volume,
					admin_inicio,
					admin_termino,
					termino_analise
				) 
				VALUES 
				(
					$login_fabrica,
					$posto,
					'$data_abertura_db',
					".((strlen($data_hora_inicio_analise) > 0) ? "'$data_hora_inicio_analise'," : "")."
					'$nota_fiscal',
					'$data_nota_fiscal_db',
					$origem_recebimento,
					$tecnico,
					'$observacao',
					$posicao_peca,
					".((strlen($data_entrega) > 0) ? "'$data_entrega'," : "")."
					'$autorizado',
					'$responsavel_recebimento',
					'$nota_fiscal_saida',
					".((strlen($data_nf_saida) > 0) ? "'$data_nf_saida_db'," : "")."
					'$volume',
					$login_admin,
					$login_admin,
					CURRENT_TIMESTAMP
				) RETURNING analise_peca";


				$res = pg_query($con, $sql);

				if(strlen(pg_last_error()) == 0){

					$analise_peca = pg_fetch_result($res, 0, "analise_peca");

					insere_pecas($produtos_pecas, $analise_peca);

				}else{

					echo pg_last_error(); exit;

					$msg_erro["msg"]["obg"] = "Erro ao Gravar a Análise das Peças";

				}

				$msg = "<div class='alert alert-success text-center'><h4>Análise de Peças Cadastrada com Sucesso</h4> <a href='analise_pecas.php?analise_peca={$analise_peca}' target='_blank'><strong>Número da Análise: {$analise_peca}</strong></a> </div>";

			}else{

				$acao = "update";

				$sql = "UPDATE tbl_analise_peca 
						SET 
							posto                   = {$posto},
							data_abertura           = '{$data_abertura_db}',
							nota_fiscal             = '{$nota_fiscal}',
							data_nf                 = '{$data_nota_fiscal}',
							origem_recebimento      = {$origem_recebimento},
							tecnico                 = {$tecnico},
							observacao              = '{$observacao}',
							status_analise_peca     = {$posicao_peca},
							".((strlen($data_entrega) > 0) ? "data_entrega = '$data_entrega'," : "")."
							autorizacao             = '$autorizado',
							responsavel_recebimento = '{$responsavel_recebimento}',
							nf_saida                = '{$nota_fiscal_saida}',
							".((strlen($data_nf_saida) > 0) ? "data_nf_saida = '$data_nf_saida_db'," : "")."
							volume                  = '{$volume}',
							admin_termino           = {$login_admin},
							inicio_analise 			= '{$data_hora_inicio_analise}' 
						WHERE 
							analise_peca = {$analise_peca} 
							AND fabrica = {$login_fabrica}
						";
				$res = pg_query($con, $sql);

				if(strlen(pg_last_error()) > 0){

					$msg_erro["msg"]["obg"] = "Erro ao Atualizar a Análise das Peças";

				}else{

					deleta_pecas($analise_peca);

					insere_pecas($produtos_pecas, $analise_peca);

					$msg = "<div class='alert alert-success text-center'><h4>Análise de Peças Alterada com Sucesso</h4></div>";

				}

			}

			/* Anexos */

			if(strlen($analise_peca) > 0){

				$arquivos = array();

				$ano = get_data_analise_peca($analise_peca, "ano");
				$mes = get_data_analise_peca($analise_peca, "mes");

				foreach ($anexo_analise as $key => $value) {
					if ($anexo_analise_s3[$key] != "t" && strlen($value) > 0) {
						$ext = preg_replace("/.+\./", "", $value);
						$arquivos[] = array(
							"file_temp" => $value,
							"file_new"  => "{$login_fabrica}_{$analise_peca}_{$key}.{$ext}"
						);
					}
				}
				
				if (count($arquivos) > 0) {				
					$s3->moveTempToBucket($arquivos, $ano, $mes, false);			
				}

			}

			if($acao == "insert"){

				$posto                   = "";
				$codigo_posto            = "";
				$descricao_posto         = "";
				$data_abertura           = "";
				$data_inicio_analise     = "";
				$hora_inicio_analise     = "";
				$nota_fiscal             = "";
				$data_nota_fiscal        = "";
				$origem_recebimento      = "";
				$autorizado              = "";
				$tecnico                 = "";
				$posicao_peca            = "";
				$data_entrega_expedicao  = "";
				$responsavel_recebimento = "";
				$nota_fiscal_saida       = "";
				$data_nf_saida           = "";
				$volume                  = "";
				$observacao              = "";
				$anexo                   = "";
				
				$analise_peca            = "";
				$produtos_pecas          = array();

			}

			/* Fim Anexos */
			
			$gravado = "sucesso";

		}


	}

	/* End Form Action */

	/**
	* Cria a chave do anexo
	*/
	if (!strlen(getValue("anexo_chave"))) {
	    $anexo_chave = sha1(date("Ymdhi")."{$login_fabrica}".(($areaAdmin === true) ? $login_admin : $login_posto));
	} else {
	    $anexo_chave = getValue("anexo_chave");
	}

	/**
	* Inclui o arquivo no s3
	*/
	if (isset($_POST["ajax_anexo_upload"])) {

	    $posicao = $_POST["anexo_posicao"];
	    $chave   = $_POST["anexo_chave"];

	    $arquivo = $_FILES["anexo_upload_{$posicao}"];

	    $ext = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));

	    if ($ext == "jpeg") {
	        $ext = "jpg";
	    }

	    if (strlen($arquivo["tmp_name"]) > 0) {

	        if (!in_array($ext, array("png", "jpg", "jpeg", "bmp", "pdf", "doc", "docx"))) {
	            $retorno = array("error" => utf8_encode("Arquivo em formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp, pdf, doc, docx"));
	        } else {
	            $arquivo_nome = "{$chave}_{$posicao}";

	            $s3->tempUpload("{$arquivo_nome}", $arquivo);

	            if($ext == "pdf"){
	            	$link = "imagens/pdf_icone.png";
	            } else if(in_array($ext, array("doc", "docx"))) {
	            	$link = "imagens/docx_icone.png";
	            } else {
		            $link = $s3->getLink("thumb_{$arquivo_nome}.{$ext}", true);
		        }

		        $href = $s3->getLink("{$arquivo_nome}.{$ext}", true);

	            if (!strlen($link)) {
	                $retorno = array("error" => utf8_encode("Erro ao anexar arquivo"));
	            } else {
	                $retorno = array("link" => $link, "arquivo_nome" => "{$arquivo_nome}.{$ext}", "href" => $href, "ext" => $ext);
	            }
	        }

	    } else {
	        $retorno = array("error" => utf8_encode("Erro ao anexar arquivo"));
	    }

	    $retorno["posicao"] = $posicao;

	    exit(json_encode($retorno));
	}

	/**
	* Excluir anexo
	*/
	if (isset($_POST["ajax_anexo_exclui"])) {

		$anexo_nome_excluir = $_POST['anexo_nome_excluir'];
		$analise_peca       = $_POST['analise_peca'];

		$ano = get_data_analise_peca($analise_peca, "ano");
		$mes = get_data_analise_peca($analise_peca, "mes");

		if (count($anexo_nome_excluir) > 0) {
			$s3->deleteObject($anexo_nome_excluir, false, $ano, $mes);
			$retorno = array("ok" => utf8_encode("Excluído com sucesso!"));
		}else{
			$retorno = array("error" => utf8_encode("Erro ao excluir arquivo"));
		}

		exit(json_encode($retorno));

	}

	$layout_menu = "cadastro";

	$title = "ANÁLISE DE PEÇAS";

	include "cabecalho_new.php";

	$plugins = array(
		"multiselect",
		"autocomplete",
		"datepicker",
		"shadowbox",
		"alphanumeric",
		"mask",
		"dataTable",
		"ajaxform"
	);

	include("plugin_loader.php");

?>

<script>

	$(function(){

		Shadowbox.init();

		$.datepickerLoad(Array(<?php if(strlen($analise_peca) == 0) { ?>"data_abertura", <?php } ?> "data_nota_fiscal", "data_entrega_expedicao", "data_nf_saida"));

		<?php if(strlen($analise_peca) == 0){ ?> 
			$("#data_abertura").mask("99/99/9999");
			$("#data_inicio_analise").mask("99/99/9999");
			$("#hora_inicio_analise").mask("99:99");
		<?php } ?>
		$("#data_nota_fiscal").mask("99/99/9999");
		$("#data_entrega_expedicao").mask("99/99/9999");
		$("#data_nf_saida").mask("99/99/9999");

		$(".numeric").numeric();

		$(document).on("click", "span[rel=lupa]", function () {
			$.lupa($(this),Array('posicao'));
		});

		$("#data_entrega_expedicao").blur(function(){

			var posicao_peca = $("#posicao_peca").find('option:selected').text();

			if((posicao_peca.search(/3/g) == -1) || (posicao_peca.search(/4/g) == -1)){
				$("#data_entrega_expedicao").val("");
			}

		});

		$("button[name=adicionar_linha]").click(function() {

			var nova_linha = $("#modelo_peca").clone();

			var posicao = $("div[name^=peca_][name!=peca___modelo__]").length;

			$("#pecas_produto").append($(nova_linha).html().replace(/__modelo__/g, posicao).replace(/disabled\=['"]disabled['"]/g, ""));

			$("div.row-fluid[name=peca_"+posicao+"]").find(".numeric").numeric();

		});

		$(document).on("click", "#remove_peca", function() {

			var posicao = $(this).attr("rel");

			$("input[name='produtos_pecas["+posicao+"][excluido]']").val("sim");

			$("div[name='peca_"+posicao+"']").hide();

		});

		$("#origem_recebimento").change(function(){

			var origem_recebimento = $(this).find('option:selected').text();

			if(origem_recebimento.toLowerCase().search(/remessa para conserto/g) == -1){

				$("#posicao_peca option").removeAttr("selected");
    			$("#posicao_peca").find("option").first().attr("selected", "selected");

				$("#asterisco_entrega_expedicao").show();
				$("#data_entrega_expedicao").prop({"readonly" : false}).val("");

				$("#asterisco_responsvel_recebimento").show();
				$("#responsavel_recebimento").prop({"readonly" : false}).val("");

				$("#asterisco_nota_fiscal_saida").hide();
				$("#nota_fiscal_saida").prop({"readonly" : true}).val("");

				$("#asterisco_data_nf_saida").hide();
				$("#data_nf_saida").prop({"readonly" : true}).val("");

				$("#asterisco_volume").hide();
				$("#volume").prop({"readonly" : true}).val("");

				$("#posicao_peca > option").each(function(){

					var texto = $(this).text();

					if(texto.search(/3/g) != -1 || texto.search(/4/g) != -1){
						$(this).attr({"disabled" : true});
					}

				});

				// $("#posicao_peca").selectreadonly(true);

			}else if(origem_recebimento.toLowerCase().search(/remessa para conserto/g) != -1){

				$("#posicao_peca option").removeAttr("selected");
    			$("#posicao_peca").find("option").first().attr("selected", "selected");

				$("#asterisco_entrega_expedicao").hide();
				$("#data_entrega_expedicao").prop({"readonly" : true}).val("");

				$("#asterisco_responsvel_recebimento").hide();
				$("#responsavel_recebimento").prop({"readonly" : true}).val("");

				$("#asterisco_nota_fiscal_saida").hide();
				$("#nota_fiscal_saida").prop({"readonly" : true}).val("");

				$("#asterisco_data_nf_saida").hide();
				$("#data_nf_saida").prop({"readonly" : true}).val("");

				$("#asterisco_volume").hide();
				$("#volume").prop({"readonly" : true}).val("");

				/* $("#posicao_peca > option").each(function(){

					var texto = $(this).text();

					if(texto.search(/2/g) != -1 || texto.search(/3/g) != -1){
						$(this).attr({"disabled" : true});
					}

				}); */

				$("#posicao_peca > option").each(function(){

					$(this).attr({"disabled" : false});

				});

			}else{

				$("#posicao_peca > option").each(function(){

					$(this).attr({"disabled" : false});

				});

				// $("#posicao_peca").selectreadonly(false).val("");

			}

		});

		$("#posicao_peca").change(function(){

			var origem_recebimento = $("#origem_recebimento").find('option:selected').text();
			/*
			if(origem_recebimento.toLowerCase().search(/devolução/g) != -1){

				// $("#posicao_peca option").removeAttr("selected");
    			// $("#posicao_peca").find("option").first().attr("selected", "selected");

				$("#asterisco_entrega_expedicao").hide();
				$("#data_entrega_expedicao").prop({"readonly" : true}).val("");

				$("#asterisco_responsvel_recebimento").hide();
				$("#responsavel_recebimento").prop({"readonly" : true}).val("");

				$("#asterisco_nota_fiscal_saida").hide();
				$("#nota_fiscal_saida").prop({"readonly" : true}).val("");

				$("#asterisco_data_nf_saida").hide();
				$("#data_nf_saida").prop({"readonly" : true}).val("");

				$("#asterisco_volume").hide();
				$("#volume").prop({"readonly" : true}).val("");

				$("#posicao_peca > option").each(function(){

					var texto = $(this).text();

					if(texto.search(/3/g) != -1 || texto.search(/4/g) != -1){
						$(this).attr({"disabled" : true});
					}

				});

				// $("#posicao_peca").selectreadonly(true);

				return;
			}
			*/
			var posicao_peca = $(this).find('option:selected').text();

			var cont = 1;

			$("#box-expedicao").find("h5.asteristico").each(function(){
				if(cont > 1){
					$(this).hide();
				}
				cont++;
			});

			$("#box-expedicao").find("input").each(function(){
				$(this).prop({"readonly" : true});
			});
			
			if(posicao_peca.search(/2/g) != -1){

				$("#asterisco_entrega_expedicao").show();
				$("#data_entrega_expedicao").prop({"readonly" : false});

				$("#asterisco_responsvel_recebimento").show();
				$("#responsavel_recebimento").prop({"readonly" : false});

			}else if(posicao_peca.search(/3/g) != -1){

				$("#asterisco_nota_fiscal_saida").show();
				$("#nota_fiscal_saida").prop({"readonly" : false});

				$("#asterisco_data_nf_saida").show();
				$("#data_nf_saida").prop({"readonly" : false});

				$("#asterisco_volume").show();
				$("#volume").prop({"readonly" : false});

			}

		});

		$("#finalizar_analise").click(function(){

			$(".alert-success").hide();

			var analise_peca = $(this).attr("rel");

			$.ajax({
				url: "<?php echo $_SERVER['PHP_SELF']; ?>",
				type: "POST",
				data: {
					finalizar_analise : true,
					analise_peca : analise_peca
				},
				beforeSend: function(){
					$("#carregando").html("<em>enviando, por favor aguarde...</em>");
				}
			}).always(function(data){

				data = JSON.parse(data);
				var msg = "";

				if(data.retorno){

					msg = "<div class='alert alert-success tac'><h4>Análise de Peças Finalizada com Sucesso</h4></div>";
					
					$(".box_finalizar_analise").hide();
					$(".box_reabrir_analise").show();

					var termino_analise = data.termino_analise;
					
					$("#msg-finalizacao").hide();
					$(".msg_termino_analise").html("<div class='alert alert-error'><h4>Esta Análise está Finalizada - "+termino_analise+"</h4> </div>");

					$(".box_gravar_alterar").hide();

				}else{
					msg = "<div class='alert alert-success tac'><h4>Erro ao Finalizar a Análise de Peças</h4></div>";
				}

				$("#carregando").html(msg);

				setTimeout(function(){
					$("#carregando").html("");
				}, 5000);

			});

		});

		$("#reabrir_analise").click(function(){

			var analise_peca = $(this).attr("rel");

			var motivo = window.prompt("Digite o motivo para a reabertura da Análise de Peças");

			if(motivo == null){
				return;
			}

			$.ajax({
				url: "<?php echo $_SERVER['PHP_SELF']; ?>",
				type: "POST",
				data: {
					reabrir_analise : true,
					analise_peca : analise_peca,
					motivo: motivo
				},
				beforeSend: function(){
					$("#carregando").html("<em>enviando, por favor aguarde...</em>");
				}
			}).always(function(data){

				data = JSON.parse(data);
				var msg = "";

				if(data.retorno){

					msg = "<div class='alert alert-success tac'><h4>Análise de Peças Reaberta com Sucesso</h4></div>";
					
					$(".box_finalizar_analise").show();
					$(".box_reabrir_analise").hide();
					$(".msg_termino_analise").html("");

					var data_hora = new Date();
					var data_analise = data_hora.getDate() + "/" + (parseInt(data_hora.getMonth() + 1)) + "/" + data_hora.getFullYear();

					var minutos = 0;
					minutos = (parseInt(data_hora.getMinutes()) < 10) ? "0" + data_hora.getMinutes() : data_hora.getMinutes();

					var hora_analise = data_hora.getHours() + ":" + minutos;

					$("#data_inicio_analise").val(data_analise);
					$("#data_inicio_analise").prop({"readonly" : true});
					$("#hora_inicio_analise").val(hora_analise);
					$("#hora_inicio_analise").prop({"readonly" : true});

					$(".box_gravar_alterar").show();
				
				}else{
					msg = "<div class='alert alert-danger tac'><h4>Erro ao Reabrir a Análise de Peças</h4></div>";
				}

				$("#carregando").html(msg);

				setTimeout(function(){
					$("#carregando").html("");
				}, 5000);

			});

		});

		$("#excluir_analise").click(function(){

			var analise_peca = $(this).attr("rel");

			var r = confirm("Deseja realmente Excluir esta Análise?");
			if(r == true) {
			    
				$.ajax({
					url: "<?php echo $_SERVER['PHP_SELF']; ?>",
					type: "POST",
					data: {
						excluir_analise : true,
						analise_peca : analise_peca
					}
				}).always(function(){

					location.href = "analise_pecas.php";

				});

			}

		});

		/**
	    * Eventos para anexar/excluir imagem
	    */
	    $("button.btn_acao_anexo").click(function(){
			var name = $(this).attr("name");
			
			if (name == "anexar") {
				$(this).trigger("anexar_s3", [$(this)]);
			}else{
				$(this).trigger("excluir_s3", [$(this)]);
			}
		});

	    $("button.btn_acao_anexo").bind("anexar_s3",function(){
	    	
	    	var posicao = $(this).attr("rel");
	    	
	    	var button = $(this);

			$("input[name=anexo_upload_"+posicao+"]").click();
	    });

	    $("button.btn_acao_anexo").bind("excluir_s3",function(){   
	    	
			var posicao      = $(this).attr("rel");
			var analise_peca = $("input[name='analise_peca']").val();
			
			var button       = $(this);
			var nome_an_p    = $("input[name='anexo["+posicao+"]']").val();

			$.ajax({			
				url: "analise_pecas.php",
				type: "POST",
				data: { ajax_anexo_exclui: true, anexo_nome_excluir: nome_an_p, analise_peca: analise_peca },
				beforeSend: function() {
					$("#div_anexo_"+posicao).find("button").hide();
					$("#div_anexo_"+posicao).find("img.anexo_thumb").hide();
					$("#div_anexo_"+posicao).find("img.anexo_loading").show();
				},
				complete: function(data) {
					data = $.parseJSON(data.responseText);

					if (data.error) {
						alert(data.error);
					} else {
						$("#div_anexo_"+posicao).find("a[target='_blank']").remove();
						$("#baixar_"+posicao).remove();
						$(button).text("Anexar").attr({
							id:"anexar_"+posicao,
							class:"btn btn-mini btn-primary btn-block",
							name: "anexar"
						});
						$("input[name='anexo["+posicao+"]']").val("f");				
						$("#div_anexo_"+posicao).prepend('<img class="anexo_thumb" style="width: 100px; height: 90px;" src="imagens/imagem_upload.png">');

						$("#div_anexo_"+posicao).find("img.anexo_loading").hide();
						$("#div_anexo_"+posicao).find("button").show();
						$("#div_anexo_"+posicao).find("img.anexo_thumb").show();
				  		alert(data.ok);
					}
							
				}
			});
	    });

		/**
	    * Eventos para anexar imagem
	    */
	    $("form[name=form_anexo]").ajaxForm({
	        complete: function(data) {
				data = $.parseJSON(data.responseText);

				if (data.error) {
					alert(data.error);
				} else {
					var imagem = $("#div_anexo_"+data.posicao).find("img.anexo_thumb").clone();
					$(imagem).attr({ src: data.link });

					$("#div_anexo_"+data.posicao).find("img.anexo_thumb").remove();

					var link = $("<a></a>", {
						href: data.href,
						target: "_blank"
					});

					$(link).html(imagem);

					$("#div_anexo_"+data.posicao).prepend(link);

					if ($.inArray(data.ext, ["doc", "pdf", "docx"]) == -1) {
						setupZoom();
					}

			        $("#div_anexo_"+data.posicao).find("input[rel=anexo]").val(data.arquivo_nome);
				}

				$("#div_anexo_"+data.posicao).find("img.anexo_loading").hide();
				$("#div_anexo_"+data.posicao).find("button").show();
				$("#div_anexo_"+data.posicao).find("img.anexo_thumb").show();
	    	}
	    });

		$("input[name^=anexo_upload_]").change(function() {
			var i = $(this).parent("form").find("input[name=anexo_posicao]").val();

			$("#div_anexo_"+i).find("button").hide();
			$("#div_anexo_"+i).find("img.anexo_thumb").hide();
			$("#div_anexo_"+i).find("img.anexo_loading").show();

			$(this).parent("form").submit();
	    });

	    $(document).on("change", "#select-produto-peca", function(){

	    	var posicao = $(this).attr("data-posicao");
	    	$("input[name='produtos_pecas["+posicao+"][numero_serie]']").blur();

	    });

	    $(document).on("blur", "#produto-peca-serie", function(){

	    	$("#info-serie-"+posicao).hide();
	    	$("#info-desc-serie-"+posicao).html("");

	    	var posicao = $(this).attr("data-posicao");
	    	var serie = $(this).val();
	    	var tipo = $("select[name='produtos_pecas["+posicao+"][categoria]'").val();

	    	if(tipo == "produto" && $.trim(serie) != ""){

	    		$.ajax({
	    			url: "<?php echo $_SERVER['PHP_SELF']; ?>",
	    			type: "POST",
	    			data: {
	    				serie : serie,
	    				verifica_serie: true
	    			},
	    			complete: function(data){

	    				data = JSON.parse(data.responseText);

	    				if(data.retorno != false){

	    					$("#info-serie-"+posicao).show();
	    					$("#info-desc-serie-"+posicao).html(data.retorno);

	    				}else{

	    					$("#info-serie-"+posicao).hide();
	    					$("#info-desc-serie-"+posicao).html("");

	    				}

	    			}
	    		});

	    	}

	    });

	    verifica_status_peca();

	});

	function retorna_posto(retorno){
	    $("#posto").val(retorno.posto);
	    $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
	}

	function retorna_peca(retorno){
		var posicao = retorno.posicao;
        $("input[name='produtos_pecas["+posicao+"][referencia]']").val(retorno.referencia);
        $("input[name='produtos_pecas["+posicao+"][descricao]']").val(retorno.descricao);

        if(retorno.produto_acabado == "t"){
        	$("select[name='produtos_pecas["+posicao+"][categoria]']").val("produto");
        }else{
        	$("select[name='produtos_pecas["+posicao+"][categoria]']").val("peca");
        }

    }

    function verifica_status_peca(){

    	var origem_recebimento = $("#origem_recebimento").find('option:selected').text();

    	if(origem_recebimento.toLowerCase().search(/devolução/g) != -1){

    		// $("#posicao_peca option").removeAttr("selected");
    		// $("#posicao_peca").find("option").first().attr("selected", "selected");

			$("#asterisco_entrega_expedicao").hide();
			$("#data_entrega_expedicao").prop({"readonly" : true}).val("");

			$("#asterisco_responsvel_recebimento").hide();
			$("#responsavel_recebimento").prop({"readonly" : true}).val("");

			$("#asterisco_nota_fiscal_saida").hide();
			$("#nota_fiscal_saida").prop({"readonly" : true}).val("");

			$("#asterisco_data_nf_saida").hide();
			$("#data_nf_saida").prop({"readonly" : true}).val("");

			$("#asterisco_volume").hide();
			$("#volume").prop({"readonly" : true}).val("");

			$("#posicao_peca > option").each(function(){

				var texto = $(this).text();

				if(texto.search(/3/g) != -1 || texto.search(/4/g) != -1){
					$(this).attr({"disabled" : true});
				}

			});

			// $("#posicao_peca").selectreadonly(true);

    		return;
    	}

		var posicao_peca = $("#posicao_peca").find('option:selected').text();

		var cont = 1;

		$("#box-expedicao").find("h5.asteristico").each(function(){
			if(cont > 1){
				$(this).hide();
			}
			cont++;
		});

		$("#box-expedicao").find("input").each(function(){
			$(this).prop({"readonly" : true});
		});
		
		if(posicao_peca.search(/2/g) != -1){

			$("#asterisco_entrega_expedicao").show();
			$("#data_entrega_expedicao").prop({"readonly" : false});

			$("#asterisco_responsvel_recebimento").show();
			$("#responsavel_recebimento").prop({"readonly" : false});

		}else if(posicao_peca.search(/3/g) != -1){

			$("#asterisco_nota_fiscal_saida").show();
			$("#nota_fiscal_saida").prop({"readonly" : false});

			$("#asterisco_data_nf_saida").show();
			$("#data_nf_saida").prop({"readonly" : false});

			$("#asterisco_volume").show();
			$("#volume").prop({"readonly" : false});

		}

    }

</script>

<style>
	
	.box_pecas{
		border: 1px solid #999;
		border-radius: 5px;
		margin-top: 10px;
		margin-right: 10px;
		margin-left: 10px;
		padding-top: 20px;
	}

	#modelo_peca{
		display: none !important;
	}

</style>

<?php
if ((count($msg_erro["msg"]) > 0) ) {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}

if(strlen($msg) > 0){
	echo $msg;
}

echo "<div class='msg_termino_analise'>";
	if(strlen($termino_analise) > 0 && count($msg_erro["msg"]) == 0){

		$parcial            = (strlen($termino_final) == 0) ? "(Parcial)" : "";
		$data_analise_final = (strlen($termino_final) == 0) ? $termino_analise : form_data_hora($termino_final);
		
		echo "<div class='alert alert-error' id='msg-finalizacao'><h4>Esta Análise está Finalizada $parcial - {$data_analise_final}</h4> </div>";
	}
echo "</div>";

if(strlen($analise_peca) > 0){
	$readonly = "readonly='readonly'";
	if(strlen($data_inicio_analise) == 0){
		$data_inicio_analise = date("d/m/Y");
		$hora_inicio_analise = date("H:i");
	}
}else{
	$readonly_pecas = "readonly='readonly'";

	?>
	<script>
		$(function(){
			$("select[name^='produtos_pecas']").selectreadonly(true);
		});
	</script>
	<?php

}

?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name="frm_credenciamento" method="POST" class="" action="<? echo $PHP_SELF; ?> ">

	<div class="tc_formulario">

		<div class="titulo_tabela">Informações do Posto</div>

		<br />

		<input type="hidden" name="posto" id="posto" value="<? echo $posto?>">

		<div class="row-fluid">
			<div class="span2"></div>

			<div class="span3">
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>

					<label class='control-label' for='codigo_posto'>Código Posto</label>
					<div class='controls controls-row'>
						<div class='span10 input-append'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<?php echo $codigo_posto ?>" >
							<span class='add-on' rel="lupa">
								<i class='icon-search' ></i>
								<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
							</span>
						</div>

					</div>
				</div>
			</div>

			<div class="span5">

				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_posto'>Razão Social</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<?php echo $descricao_posto ?>" >
							<span class='add-on' rel="lupa">
								<i class='icon-search' ></i>
								<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
							</span>
						</div>
					</div>
				</div>
			</div>
		</div>

		<br />

	</div>

	<br />

	<div class="tc_formulario">

		<div class="titulo_tabela">Informações de Recebimento</div>

		<br />

		<div class="row-fluid">

			<div class="span1"></div>

            <div class='span2'>
                <div class='control-group <?=(in_array("data_abertura", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_abertura'>Data Abertura</label>
                    <div class='controls controls-row'>
                        <div class='span11'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" name="data_abertura" id="data_abertura" size="12" class='span12' value= "<?=$data_abertura?>" <?php echo $readonly; ?> >
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'>
                <div class='control-group <?=(in_array("data_inicio_analise", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_inicio_analise'>Data Inicio Análise</label>
                    <div class='controls controls-row'>
                        <div class='span11'>
                            <?php if(strlen($analise_peca) > 0){ ?><h5 class='asteristico'>*</h5> <?php } ?>
                            <input type="text" name="data_inicio_analise" id="data_inicio_analise" size="12" class='span12' value= "<?=$data_inicio_analise?>" readonly="readonly" >
                        </div>
                    </div>
                </div>
            </div>
             <div class='span2'>
                <div class='control-group <?=(in_array("hora_inicio_analise", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='hora_inicio_analise'>Hora Inicio Análise</label>
                    <div class='controls controls-row'>
                        <div class='span11'>
                            <?php if(strlen($analise_peca) > 0){ ?><h5 class='asteristico'>*</h5> <?php } ?>
                            <input type="text" name="hora_inicio_analise" id="hora_inicio_analise" size="12" class='span12' value= "<?=$hora_inicio_analise?>" readonly="readonly" >
                        </div>
                    </div>
                </div>
            </div>
	        <div class='span2'>
                <div class='control-group <?=(in_array("nota_fiscal", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='nota_fiscal'>Nota Fiscal</label>
                    <div class='controls controls-row'>
                        <div class='span11'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" name="nota_fiscal" id="nota_fiscal" size="12" class='span12' value= "<?=$nota_fiscal?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class='span2'>
	            <div class='control-group <?=(in_array("data_nota_fiscal", $msg_erro["campos"])) ? "error" : ""?>'>
	                <label class='control-label' for='data_nota_fiscal'>Data da Nota Fiscal</label>
	                <div class='controls controls-row'>
	                    <div class='span12'>
	                        <h5 class='asteristico'>*</h5>
	                            <input type="text" name="data_nota_fiscal" id="data_nota_fiscal" size="12" class='span12' value="<?=$data_nota_fiscal?>" >
	                    </div>
	                </div>
	            </div>
	        </div>
			
			<div class="span1"></div>

		</div>

		<div class="row-fluid">

			<div class="span1"></div>

			<div class='span4'>
	            <div class='control-group <?=(in_array("origem_recebimento", $msg_erro["campos"])) ? "error" : ""?>'>
	                <label class='control-label' for='origem_recebimento'>Origem do Recebimento</label>
	                <div class='controls controls-row'>
	                    <div class='span11'>
	                        <h5 class='asteristico'>*</h5>
	                            <select name="origem_recebimento" id="origem_recebimento" class='span12'>
	                            	<option value=""></option>

	                            	<?php

	                            	$sql = "SELECT * FROM tbl_origem_recebimento WHERE fabrica = {$login_fabrica} AND ativo IS TRUE ORDER BY descricao ASC";
	                            	$res = pg_query($con, $sql);

	                            	if(pg_num_rows($res) > 0){

	                            		for($i = 0; $i < pg_num_rows($res); $i++){

	                            			$codigo = pg_fetch_result($res, $i, "origem_recebimento");
	                            			$descricao = pg_fetch_result($res, $i, "descricao");

	                            			$selected = ($codigo == $origem_recebimento) ? "SELECTED" : "";

	                            			echo "<option value='{$codigo}' {$selected}>{$descricao}</option>";

	                            		}

	                            	}

	                            	?>

	                            </select>
	                    </div>
	                </div>
	            </div>
	        </div>

	        <div class='span6'>
	            <div class='control-group <?=(in_array("autorizado", $msg_erro["campos"])) ? "error" : ""?>'>
	                <label class='control-label' for='autorizado'>Autorizado por</label>
	                <div class='controls controls-row'>
	                    <div class='span12'>
	                        <h5 class='asteristico'>*</h5>
	                            <input type="text" name="autorizado" id="autorizado" size="12" class='span12' value="<?=$autorizado?>" >
	                    </div>
	                </div>
	            </div>
	        </div>

			<div class="span1"></div>

		</div>

		<br />

	</div>

	<br />

	<div class="tc_formulario">

		<div class="titulo_tabela">Peças / Produtos para Análise</div>

		<br />

		<div id="modelo_peca">
			
			<div name="peca___modelo__" class="box_pecas">

				<input type="hidden" name="produtos_pecas[__modelo__][excluido]" value="" />

				<div class="row-fluid">
					
					<div class="span1"></div>

					<div class="span3">
						<div class='control-group' >
							<label class="control-label" >Categoria</label>
							<div class="controls controls-row">
								<div class="span11 input-append">
									<select name="produtos_pecas[__modelo__][categoria]" class="span12" id="select-produto-peca" data-posicao="__modelo__">
										<option value="peca">Peça</option>
										<option value="produto">Produto Acabado</option>
									</select>
								</div>
							</div>
						</div>
					</div>

					<div class="span2">
						<div class='control-group' >
							<label class="control-label">Referência</label>
							<div class="controls controls-row">
								<div class="span9 input-append">
									<input  name="produtos_pecas[__modelo__][referencia]" class="span12" type="text" value="" />
									<span class="add-on" rel="lupa" >
										<i class="icon-search"></i>
									</span>
									<input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" posicao="__modelo__" />
								</div>
							</div>
						</div>
					</div>

					<div class="span5">
						<div class='control-group' >
							<label class="control-label" >Descrição</label>
							<div class="controls controls-row">
								<div class="span11 input-append">
									<input name="produtos_pecas[__modelo__][descricao]" class="span12" type="text" value="" />
									<span class="add-on" rel="lupa" >
										<i class="icon-search"></i>
									</span>
									<input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" posicao="__modelo__" />
								</div>
							</div>
						</div>
					</div>

				</div>

				<div class="row-fluid">

					<div class="span1"></div>

					<div class="span3">
						<div class='control-group' >
							<label class="control-label" >Número de Série</label>
							<div class="controls controls-row">
								<div class="span11 input-append">
									<input type="text" name="produtos_pecas[__modelo__][numero_serie]" class="span12" id="produto-peca-serie" data-posicao="__modelo__">
								</div>
							</div>
						</div>
					</div>

					<div class="span3">
						<div class='control-group' >
							<label class="control-label" >Número de Lote</label>
							<div class="controls controls-row">
								<div class="span11 input-append">
									<input type="text" name="produtos_pecas[__modelo__][numero_lote]" class="span12">
								</div>
							</div>
						</div>
					</div>

					<div class="span2">
						<div class='control-group' >
							<label class="control-label" >Quantidade</label>
							<div class="controls controls-row">
								<div class="span11 input-append">
									<input type="text" name="produtos_pecas[__modelo__][quantidade]" class="span12 numeric">
								</div>
							</div>
						</div>
					</div>

				</div>

				<div class="row-fluid" id="info-serie-__modelo__" style="display: none; min-height: 30px !important;">
					<div class="span1"></div>
					<div class="span10" id="info-desc-serie-__modelo__"></div>
				</div>

				<div class="row-fluid">

					<div class="span1"></div>

					<div class="span5">
						<div class='control-group' >
							<label class="control-label" >Defeito Constatado</label>
							<div class="controls controls-row">
								<div class="span12 input-append">
									<input type="text" name="produtos_pecas[__modelo__][defeito_constatado]" class="span12" <?php echo $readonly_pecas; ?>>
								</div>
							</div>
						</div>
					</div>

					<div class="span5">
						<div class='control-group' >
							<label class="control-label" >Resultado da Análise</label>
							<div class="controls controls-row">
								<div class="span12 input-append">
									<input type="text" name="produtos_pecas[__modelo__][resultado_analise]" class="span12" <?php echo $readonly_pecas; ?>>
								</div>
							</div>
						</div>
					</div>

				</div>

				<div class="row-fluid">
					
					<div class="span1"></div>

					<div class="span2">
						<div class='control-group' >
							<label class="control-label" ><small>Procede Reclamação</small></label>
							<div class="controls controls-row">
								<div class="span12 input-append">
									<select name="produtos_pecas[__modelo__][procede_reclamacao]" class="span12" <?php echo $readonly_pecas; ?>>
										<option value="t">Sim</option>
										<option value="f">Não</option>
									</select>
								</div>
							</div>
						</div>
					</div>

					<div class="span2">
						<div class='control-group' >
							<label class="control-label" ><small>Garantia</small></label>
							<div class="controls controls-row">
								<div class="span12 input-append">
									<select name="produtos_pecas[__modelo__][garantia]" class="span12" <?php echo $readonly_pecas; ?>>
										<option value="t">Sim</option>
										<option value="f">Não</option>
									</select>
								</div>
							</div>
						</div>
					</div>

					<div class="span2">
						<div class='control-group' >
							<label class="control-label" ><small>Laudo após reparo</small></label>
							<div class="controls controls-row">
								<div class="span12 input-append">
									<select name="produtos_pecas[__modelo__][laudo_apos_reparo]" class="span12" <?php echo $readonly_pecas; ?>>
										<option value="t">Aprovado</option>
										<option value="f">Reprovado</option>
									</select>
								</div>
							</div>
						</div>
					</div>

					<div class="span2">
						<div class='control-group' >
							<label class="control-label" ><small>Enviar peça nova</small></label>
							<div class="controls controls-row">
								<div class="span12 input-append">
									<select name="produtos_pecas[__modelo__][enviar_peca_nova]" class="span12" <?php echo $readonly_pecas; ?>>
										<option value="t">Sim</option>
										<option value="f">Não</option>
									</select>
								</div>
							</div>
						</div>
					</div>

					<div class="span2">
						<div class='control-group' >
							<label class="control-label" ><small>Sucatear peça</small></label>
							<div class="controls controls-row">
								<div class="span12 input-append">
									<select name="produtos_pecas[__modelo__][sucatear_peca]" class="span12" <?php echo $readonly_pecas; ?>>
										<option value="t">Sim</option>
										<option value="f">Não</option>
									</select>
								</div>
							</div>
						</div>
					</div>

				</div>

				<div class="row-fluid">
					
					<div class="span1"></div>

					<div class="span2">
						<div class='control-group' >
							<label class="control-label" ><small>Baixar no estoque</small></label>
							<div class="controls controls-row">
								<div class="span12 input-append">
									<select name="produtos_pecas[__modelo__][baixa_no_estoque]" class="span12" <?php echo $readonly_pecas; ?>>
										<option value="t">Sim</option>
										<option value="f">Não</option>
									</select>
								</div>
							</div>
						</div>
					</div>

					<div class="span2">
						<div class='control-group' >
							<label class="control-label" ><small>Lançar no Claim</small></label>
							<div class="controls controls-row">
								<div class="span12 input-append">
									<select name="produtos_pecas[__modelo__][lancar_no_clain]" class="span12" <?php echo $readonly_pecas; ?>>
										<option value="t">Sim</option>
										<option value="f">Não</option>
									</select>
								</div>
							</div>
						</div>
					</div>

					<div class="span4">
						<div class='control-group' >
							<label class="control-label" ><small>Gasto não Justificado com devolução</small></label>
							<div class="controls controls-row">
								<div class="input-append" style="width: 123px;">
									<select name="produtos_pecas[__modelo__][gasto_nao_justificavel_com_devolucao]" class="span12" <?php echo $readonly_pecas; ?>>
										<option value="t">Sim</option>
										<option value="f">Não</option>
									</select>
								</div>
							</div>
						</div>
					</div>

				</div>

				<div class="row-fluid">
								
					<div class="span1"></div>
					<div class="span10" style="text-align: right;">
						<button type="button" class="btn btn-danger" id="remove_peca" rel="__modelo__" >Excluir Linha</button>
					</div>
					<div class="span1"></div>

				</div>

			</div>

		</div>

		<div id="pecas_produto">

			<?php  

			if(isset($produtos_pecas) && count($produtos_pecas) > 0){

				$posicao = 0;

				foreach ($produtos_pecas as $key => $value) {
					
					if(is_numeric($key)){

						$arr_categoria                            = $value["categoria"];
						$arr_referencia                           = $value["referencia"];
						$arr_descricao                            = $value["descricao"];
						$arr_numero_serie                         = $value["numero_serie"];
						$arr_numero_lote                          = $value["numero_lote"];
						$arr_quantidade                           = $value["quantidade"];
						$arr_defeito_constatado                   = $value["defeito_constatado"];
						$arr_resultado_analise                    = $value["resultado_analise"];
						$arr_procede_reclamacao                   = $value["procede_reclamacao"];
						$arr_garantia                             = $value["garantia"];
						$arr_laudo_apos_reparo                    = $value["laudo_apos_reparo"];
						$arr_enviar_peca_nova                     = $value["enviar_peca_nova"];
						$arr_sucatear_peca                        = $value["sucatear_peca"];
						$arr_baixa_no_estoque                     = $value["baixa_no_estoque"];
						$arr_lancar_no_clain                      = $value["lancar_no_clain"];
						$arr_gasto_nao_justificavel_com_devolucao = $value["gasto_nao_justificavel_com_devolucao"];
						$arr_excluido							  = $value["excluido"];

						?>

						<div name="peca_<?=$posicao?>" class="box_pecas" <?php echo ($arr_excluido == "sim") ? "style='display: none;'" : ""; ?> >

							<input type="hidden" name="produtos_pecas[<?=$posicao?>][excluido]" value="<?php echo $arr_excluido; ?>" />

							<div class="row-fluid">
								
								<div class="span1"></div>

								<div class="span3">
									<div class='control-group' >
										<label class="control-label" >Categoria</label>
										<div class="controls controls-row">
											<div class="span11 input-append">
												<select name="produtos_pecas[<?=$posicao?>][categoria]" class="span12">
													<option value="peca" <?php echo ($arr_categoria == "peca") ? "SELECTED" : ""; ?> >Peça</option>
													<option value="produto" <?php echo ($arr_categoria == "produto") ? "SELECTED" : ""; ?> >Produto Acabado</option>
												</select>
											</div>
										</div>
									</div>
								</div>

								<div class="span2">
									<div class='control-group' >
										<label class="control-label">Referência</label>
										<div class="controls controls-row">
											<div class="span9 input-append">
												<input  name="produtos_pecas[<?=$posicao?>][referencia]" class="span12" type="text" value="<?=$arr_referencia?>" />
												<span class="add-on" rel="lupa" >
													<i class="icon-search"></i>
												</span>
												<input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" posicao="<?=$posicao?>" />
											</div>
										</div>
									</div>
								</div>

								<div class="span5">
									<div class='control-group' >
										<label class="control-label" >Descrição</label>
										<div class="controls controls-row">
											<div class="span11 input-append">
												<input name="produtos_pecas[<?=$posicao?>][descricao]" class="span12" type="text" value="<?=$arr_descricao?>" />
												<span class="add-on" rel="lupa" >
													<i class="icon-search"></i>
												</span>
												<input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" posicao="<?=$posicao?>" />
											</div>
										</div>
									</div>
								</div>

							</div>

							<div class="row-fluid">

								<div class="span1"></div>

								<div class="span3">
									<div class='control-group' >
										<label class="control-label" >Número de Série</label>
										<div class="controls controls-row">
											<div class="span11 input-append">
												<input type="text" name="produtos_pecas[<?=$posicao?>][numero_serie]" class="span12" value="<?=$arr_numero_serie?>">
											</div>
										</div>
									</div>
								</div>

								<div class="span3">
									<div class='control-group' >
										<label class="control-label" >Número de Lote</label>
										<div class="controls controls-row">
											<div class="span11 input-append">
												<input type="text" name="produtos_pecas[<?=$posicao?>][numero_lote]" class="span12" value="<?=$arr_numero_lote?>">
											</div>
										</div>
									</div>
								</div>

								<div class="span2">
									<div class='control-group' >
										<label class="control-label" >Quantidade</label>
										<div class="controls controls-row">
											<div class="span11 input-append">
												<input type="text" name="produtos_pecas[<?=$posicao?>][quantidade]" class="span12 numeric" value="<?=$arr_quantidade?>">
											</div>
										</div>
									</div>
								</div>

							</div>

							<div class="row-fluid">

								<div class="span1"></div>

								<div class="span5">
									<div class='control-group' >
										<label class="control-label" >Defeito Constatado</label>
										<div class="controls controls-row">
											<div class="span12 input-append">
												<input type="text" name="produtos_pecas[<?=$posicao?>][defeito_constatado]" class="span12" value="<?=$arr_defeito_constatado?>" <?php echo $readonly_pecas; ?>>
											</div>
										</div>
									</div>
								</div>

								<div class="span5">
									<div class='control-group' >
										<label class="control-label" >Resultado da Análise</label>
										<div class="controls controls-row">
											<div class="span12 input-append">
												<input type="text" name="produtos_pecas[<?=$posicao?>][resultado_analise]" class="span12" value="<?=$arr_resultado_analise?>" <?php echo $readonly_pecas; ?>>
											</div>
										</div>
									</div>
								</div>

							</div>

							<div class="row-fluid">
								
								<div class="span1"></div>

								<div class="span2">
									<div class='control-group' >
										<label class="control-label" ><small>Procede Reclamação</small></label>
										<div class="controls controls-row">
											<div class="span12 input-append">
												<select name="produtos_pecas[<?=$posicao?>][procede_reclamacao]" class="span12" <?php echo $readonly_pecas; ?>>
													<option value="t" <?php echo ($arr_procede_reclamacao == "t") ? "SELECTED" : ""; ?> >Sim</option>
													<option value="f" <?php echo ($arr_procede_reclamacao == "f") ? "SELECTED" : ""; ?> >Não</option>
												</select>
											</div>
										</div>
									</div>
								</div>

								<div class="span2">
									<div class='control-group' >
										<label class="control-label" ><small>Garantia</small></label>
										<div class="controls controls-row">
											<div class="span12 input-append">
												<select name="produtos_pecas[<?=$posicao?>][garantia]" class="span12" <?php echo $readonly_pecas; ?>>
													<option value="t" <?php echo ($arr_garantia == "t") ? "SELECTED" : ""; ?> >Sim</option>
													<option value="f" <?php echo ($arr_garantia == "f") ? "SELECTED" : ""; ?> >Não</option>
												</select>
											</div>
										</div>
									</div>
								</div>

								<div class="span2">
									<div class='control-group' >
										<label class="control-label" ><small>Laudo após reparo</small></label>
										<div class="controls controls-row">
											<div class="span12 input-append">
												<select name="produtos_pecas[<?=$posicao?>][laudo_apos_reparo]" class="span12" <?php echo $readonly_pecas; ?>>
													<option value="t" <?php echo ($arr_laudo_apos_reparo == "t") ? "SELECTED" : ""; ?> >Aprovado</option>
													<option value="f" <?php echo ($arr_laudo_apos_reparo == "f") ? "SELECTED" : ""; ?> >Reprovado</option>
												</select>
											</div>
										</div>
									</div>
								</div>

								<div class="span2">
									<div class='control-group' >
										<label class="control-label" ><small>Enviar peça nova</small></label>
										<div class="controls controls-row">
											<div class="span12 input-append">
												<select name="produtos_pecas[<?=$posicao?>][enviar_peca_nova]" class="span12" <?php echo $readonly_pecas; ?>>
													<option value="t" <?php echo ($arr_enviar_peca_nova == "t") ? "SELECTED" : ""; ?> >Sim</option>
													<option value="f" <?php echo ($arr_enviar_peca_nova == "f") ? "SELECTED" : ""; ?> >Não</option>
												</select>
											</div>
										</div>
									</div>
								</div>

								<div class="span2">
									<div class='control-group' >
										<label class="control-label" ><small>Sucatear peça</small></label>
										<div class="controls controls-row">
											<div class="span12 input-append">
												<select name="produtos_pecas[<?=$posicao?>][sucatear_peca]" class="span12" <?php echo $readonly_pecas; ?>>
													<option value="t" <?php echo ($arr_sucatear_peca == "t") ? "SELECTED" : ""; ?> >Sim</option>
													<option value="f" <?php echo ($arr_sucatear_peca == "f") ? "SELECTED" : ""; ?> >Não</option>
												</select>
											</div>
										</div>
									</div>
								</div>

							</div>

							<div class="row-fluid">
								
								<div class="span1"></div>

								<div class="span2">
									<div class='control-group' >
										<label class="control-label" ><small>Baixar no estoque</small></label>
										<div class="controls controls-row">
											<div class="span12 input-append">
												<select name="produtos_pecas[<?=$posicao?>][baixa_no_estoque]" class="span12" <?php echo $readonly_pecas; ?>>
													<option value="t" <?php echo ($arr_baixa_no_estoque == "t") ? "SELECTED" : ""; ?> >Sim</option>
													<option value="f" <?php echo ($arr_baixa_no_estoque == "f") ? "SELECTED" : ""; ?> >Não</option>
												</select>
											</div>
										</div>
									</div>
								</div>

								<div class="span2">
									<div class='control-group' >
										<label class="control-label" ><small>Lançar no Claim</small></label>
										<div class="controls controls-row">
											<div class="span12 input-append">
												<select name="produtos_pecas[<?=$posicao?>][lancar_no_clain]" class="span12" <?php echo $readonly_pecas; ?>>
													<option value="t" <?php echo ($arr_lancar_no_clain == "t") ? "SELECTED" : ""; ?> >Sim</option>
													<option value="f" <?php echo ($arr_lancar_no_clain == "f") ? "SELECTED" : ""; ?> >Não</option>
												</select>
											</div>
										</div>
									</div>
								</div>

								<div class="span4">
									<div class='control-group' >
										<label class="control-label" ><small>Gasto não Justificado com devolução</small></label>
										<div class="controls controls-row">
											<div class="input-append" style="width: 123px;">
												<select name="produtos_pecas[<?=$posicao?>][gasto_nao_justificavel_com_devolucao]" class="span12" <?php echo $readonly_pecas; ?>>
													<option value="t" <?php echo ($arr_gasto_nao_justificavel_com_devolucao == "t") ? "SELECTED" : ""; ?> >Sim</option>
													<option value="f" <?php echo ($arr_gasto_nao_justificavel_com_devolucao == "f") ? "SELECTED" : ""; ?> >Não</option>
												</select>
											</div>
										</div>
									</div>
								</div>

							</div>

							<div class="row-fluid">
								
								<div class="span1"></div>
								<div class="span10" style="text-align: right;">
									<button type="button" class="btn btn-danger" id="remove_peca" rel="<?=$posicao?>" >Excluir Linha</button>
								</div>
								<div class="span1"></div>

							</div>

						</div>

						<?php

						$posicao++;

					}

				}

			}else{

			?>

				<div name="peca_0" class="box_pecas">

					<input type="hidden" name="produtos_pecas[0][excluido]" value="" />

					<div class="row-fluid">
						
						<div class="span1"></div>

						<div class="span3">
							<div class='control-group' >
								<label class="control-label" >Categoria</label>
								<div class="controls controls-row">
									<div class="span11 input-append">
										<select name="produtos_pecas[0][categoria]" class="span12" id="select-produto-peca" data-posicao="0">
											<option value="peca">Peça</option>
											<option value="produto">Produto Acabado</option>
										</select>
									</div>
								</div>
							</div>
						</div>

						<div class="span2">
							<div class='control-group' >
								<label class="control-label">Referência</label>
								<div class="controls controls-row">
									<div class="span9 input-append">
										<input  name="produtos_pecas[0][referencia]" class="span12" type="text" value="" />
										<span class="add-on" rel="lupa" >
											<i class="icon-search"></i>
										</span>
										<input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" posicao="0" />
									</div>
								</div>
							</div>
						</div>

						<div class="span5">
							<div class='control-group' >
								<label class="control-label" >Descrição</label>
								<div class="controls controls-row">
									<div class="span11 input-append">
										<input name="produtos_pecas[0][descricao]" class="span12" type="text" value="" />
										<span class="add-on" rel="lupa" >
											<i class="icon-search"></i>
										</span>
										<input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" posicao="0" />
									</div>
								</div>
							</div>
						</div>

					</div>

					<div class="row-fluid">

						<div class="span1"></div>

						<div class="span3">
							<div class='control-group' >
								<label class="control-label" >Número de Série</label>
								<div class="controls controls-row">
									<div class="span11 input-append">
										<input type="text" name="produtos_pecas[0][numero_serie]" class="span12" id="produto-peca-serie" data-posicao="0">
									</div>
								</div>
							</div>
						</div>

						<div class="span3">
							<div class='control-group' >
								<label class="control-label" >Número de Lote</label>
								<div class="controls controls-row">
									<div class="span11 input-append">
										<input type="text" name="produtos_pecas[0][numero_lote]" class="span12">
									</div>
								</div>
							</div>
						</div>

						<div class="span2">
							<div class='control-group' >
								<label class="control-label" >Quantidade</label>
								<div class="controls controls-row">
									<div class="span11 input-append">
										<input type="text" name="produtos_pecas[0][quantidade]" class="span12 numeric">
									</div>
								</div>
							</div>
						</div>

					</div>

					<div class="row-fluid" id="info-serie-0" style="display: none; min-height: 30px !important;">
						<div class="span1"></div>
						<div class="span10" id="info-desc-serie-0"></div>
					</div>

					<div class="row-fluid">

						<div class="span1"></div>

						<div class="span5">
							<div class='control-group' >
								<label class="control-label" >Defeito Constatado</label>
								<div class="controls controls-row">
									<div class="span12 input-append">
										<input type="text" name="produtos_pecas[0][defeito_constatado]" class="span12" <?php echo $readonly_pecas; ?>>
									</div>
								</div>
							</div>
						</div>

						<div class="span5">
							<div class='control-group' >
								<label class="control-label" >Resultado da Análise</label>
								<div class="controls controls-row">
									<div class="span12 input-append">
										<input type="text" name="produtos_pecas[0][resultado_analise]" class="span12" <?php echo $readonly_pecas; ?>>
									</div>
								</div>
							</div>
						</div>

					</div>

					<div class="row-fluid">
						
						<div class="span1"></div>

						<div class="span2">
							<div class='control-group' >
								<label class="control-label" ><small>Procede Reclamação</small></label>
								<div class="controls controls-row">
									<div class="span12 input-append">
										<select name="produtos_pecas[0][procede_reclamacao]" class="span12" <?php echo $readonly_pecas; ?>>
											<option value="t">Sim</option>
											<option value="f">Não</option>
										</select>
									</div>
								</div>
							</div>
						</div>

						<div class="span2">
							<div class='control-group' >
								<label class="control-label" ><small>Garantia</small></label>
								<div class="controls controls-row">
									<div class="span12 input-append">
										<select name="produtos_pecas[0][garantia]" class="span12" <?php echo $readonly_pecas; ?>>
											<option value="t">Sim</option>
											<option value="f">Não</option>
										</select>
									</div>
								</div>
							</div>
						</div>

						<div class="span2">
							<div class='control-group' >
								<label class="control-label" ><small>Laudo após reparo</small></label>
								<div class="controls controls-row">
									<div class="span12 input-append">
										<select name="produtos_pecas[0][laudo_apos_reparo]" class="span12" <?php echo $readonly_pecas; ?>>
											<option value="t">Aprovado</option>
											<option value="f">Reprovado</option>
										</select>
									</div>
								</div>
							</div>
						</div>

						<div class="span2">
							<div class='control-group' >
								<label class="control-label" ><small>Enviar peça nova</small></label>
								<div class="controls controls-row">
									<div class="span12 input-append">
										<select name="produtos_pecas[0][enviar_peca_nova]" class="span12" <?php echo $readonly_pecas; ?>>
											<option value="t">Sim</option>
											<option value="f">Não</option>
										</select>
									</div>
								</div>
							</div>
						</div>

						<div class="span2">
							<div class='control-group' >
								<label class="control-label" ><small>Sucatear peça</small></label>
								<div class="controls controls-row">
									<div class="span12 input-append">
										<select name="produtos_pecas[0][sucatear_peca]" class="span12" <?php echo $readonly_pecas; ?>>
											<option value="t">Sim</option>
											<option value="f">Não</option>
										</select>
									</div>
								</div>
							</div>
						</div>

					</div>

					<div class="row-fluid">
						
						<div class="span1"></div>

						<div class="span2">
							<div class='control-group' >
								<label class="control-label" ><small>Baixar no estoque</small></label>
								<div class="controls controls-row">
									<div class="span12 input-append">
										<select name="produtos_pecas[0][baixa_no_estoque]" class="span12" <?php echo $readonly_pecas; ?>>
											<option value="t">Sim</option>
											<option value="f">Não</option>
										</select>
									</div>
								</div>
							</div>
						</div>

						<div class="span2">
							<div class='control-group' >
								<label class="control-label" ><small>Lançar no Claim</small></label>
								<div class="controls controls-row">
									<div class="span12 input-append">
										<select name="produtos_pecas[0][lancar_no_clain]" class="span12" <?php echo $readonly_pecas; ?>>
											<option value="t">Sim</option>
											<option value="f">Não</option>
										</select>
									</div>
								</div>
							</div>
						</div>

						<div class="span4">
							<div class='control-group' >
								<label class="control-label" ><small>Gasto não Justificado com devolução</small></label>
								<div class="controls controls-row">
									<div class="input-append" style="width: 123px;">
										<select name="produtos_pecas[0][gasto_nao_justificavel_com_devolucao]" class="span12" <?php echo $readonly_pecas; ?>>
											<option value="t">Sim</option>
											<option value="f">Não</option>
										</select>
									</div>
								</div>
							</div>
						</div>

					</div>

					<div class="row-fluid">
								
						<div class="span1"></div>
						<div class="span10" style="text-align: right;">
							<button type="button" class="btn btn-danger" id="remove_peca" rel="0" >Excluir Linha</button>
						</div>
						<div class="span1"></div>

					</div>

				</div>

			<?php } ?>

		</div>

		<br />

		<div class="tac">
			<button type="button" name="adicionar_linha" class="btn btn-primary" >Adicionar nova linha</button>
		</div>

		<br />


	</div>

	<br />

	<div class="tc_formulario">

		<div class="titulo_tabela">Informações do Técnico</div>

		<br />

		<div class="row-fluid">

			<div class="span2"></div>

	        <div class='span8'>
	            <div class='control-group <?=(in_array("tecnico", $msg_erro["campos"])) ? "error" : ""?>'>
	                <label class='control-label' for='tecnico'>Técnico</label>
	                <div class='controls controls-row'>
	                    <div class='span12'>
	                    	<h5 class="asteristico">*</h5>
                            <select name="tecnico" id="tecnico" class='span12'>
                            	<option value=""></option>

                            	<?php

                            	$sql = "SELECT tecnico, nome FROM tbl_tecnico WHERE fabrica = {$login_fabrica} AND ativo IS TRUE ORDER BY nome ASC";
                            	$res = pg_query($con, $sql);

                            	if(pg_num_rows($res) > 0){

                            		for($i = 0; $i < pg_num_rows($res); $i++){

                            			$codigo = pg_fetch_result($res, $i, "tecnico");
                            			$nome = pg_fetch_result($res, $i, "nome");

                            			$selected = ($codigo == $tecnico) ? "SELECTED" : "";

                            			echo "<option value='{$codigo}' {$selected}>{$nome}</option>";

                            		}

                            	}

                            	?>

                            </select>
	                    </div>
	                </div>
	            </div>
	        </div>

		</div>

		<br />

	</div>

	<br />

	<div class="tc_formulario" id="box-expedicao">

		<div class="titulo_tabela">Informações de Saída</div>

		<br />

		<div class="row-fluid">

			<div class="span1"></div>

	        <div class='span4'>
	            <div class='control-group <?=(in_array("posicao_peca", $msg_erro["campos"])) ? "error" : ""?>'>
	                <label class='control-label' for='posicao_peca'>Posição da Peça</label>
	                <div class='controls controls-row'>
	                    <div class='span11'>
	                    	<h5 class="asteristico">*</h5>
                            <select name="posicao_peca" id="posicao_peca" class='span12'>

                            	<?php

                            	$sql = "SELECT status_analise_peca, descricao, ativo FROM tbl_status_analise_peca WHERE fabrica = {$login_fabrica} ORDER BY descricao ASC";
                            	$res = pg_query($con, $sql);

                            	if(pg_num_rows($res) > 0){

                            		for($i = 0; $i < pg_num_rows($res); $i++){

										$codigo = pg_fetch_result($res, $i, "status_analise_peca");
										$nome   = pg_fetch_result($res, $i, "descricao");
										$ativo  = pg_fetch_result($res, $i, "ativo");

                            			$selected = ($codigo == $posicao_peca) ? "SELECTED" : "";

                            			$disabled = ($ativo == "f") ? "disabled='disabled'" : "";

                            			if($selected == "SELECTED"){

                            				$readonly = (!strstr(strtolower($nome), "expedição")) ? "readonly='readonly'" : ""; 

                            			}

                            			echo "<option value='{$codigo}' {$selected} {$disabled}>{$nome}</option>";

                            		}

                            	}

                            	?>

                            </select>
	                    </div>
	                </div>
	            </div>
	        </div>

	        <div class='span3'>
	            <div class='control-group <?=(in_array("data_entrega_expedicao", $msg_erro["campos"])) ? "error" : ""?>'>
	                <label class='control-label' for='data_entrega_expedicao'>Entrega à Expedição / Estoque</label>
	                <div class='controls controls-row'>
	                    <div class='span8'>
	                    	<h5 class="asteristico" id="asterisco_entrega_expedicao">*</h5>
	                        <input type="text" name="data_entrega_expedicao" id="data_entrega_expedicao" size="12" class='span12' value="<?=$data_entrega_expedicao?>" <?=$readonly?> >
	                    </div>
	                </div>
	            </div>
	        </div>

	        <div class='span3'>
	            <div class='control-group <?=(in_array("responsavel_recebimento", $msg_erro["campos"])) ? "error" : ""?>'>
	                <label class='control-label' for='responsavel_recebimento'>Responsável pelo Recebimento</label>
	                <div class='controls controls-row'>
	                    <div class='span12'>
	                    	<h5 class="asteristico" id="asterisco_responsvel_recebimento">*</h5>
	                    	<input type="text" name="responsavel_recebimento" id="responsavel_recebimento" size="12" class='span12' value="<?=$responsavel_recebimento?>" <?=$readonly?> >
	                    </div>
	                </div>
	            </div>
	        </div>

		</div>

		<div class="row-fluid">

			<div class="span1"></div>

	        <div class='span2'>
                <div class='control-group <?=(in_array("nota_fiscal_saida", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='nota_fiscal_saida'>Nota Fiscal Saída</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                        	<h5 class="asteristico" id="asterisco_nota_fiscal_saida">*</h5>
                            <input type="text" name="nota_fiscal_saida" id="nota_fiscal_saida" size="12" class='span12' value= "<?=$nota_fiscal_saida?>" <?=$readonly?> >
                        </div>
                    </div>
                </div>
            </div>

            <div class='span2'>
                <div class='control-group <?=(in_array("data_nf_saida", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_nf_saida'>Data NF de Saída</label>
                    <div class='controls controls-row'>
                        <div class='span11'>
                        	<h5 class="asteristico" id="asterisco_data_nf_saida">*</h5>
                            <input type="text" name="data_nf_saida" id="data_nf_saida" size="12" class='span12' value= "<?=$data_nf_saida?>" <?=$readonly?> >
                        </div>
                    </div>
                </div>
            </div>

            <div class='span2'>
                <div class='control-group <?=(in_array("volume", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='volume'>Volume</label>
                    <div class='controls controls-row'>
                        <div class='span10'>
                        	<h5 class="asteristico" id="asterisco_volume">*</h5>
                            <input type="text" name="volume" id="volume" size="12" class='span12 numeric' value="<?=$volume?>" <?=$readonly?> >
                        </div>
                    </div>
                </div>
            </div>
			
			<div class="span1"></div>

		</div>

		<br />

	</div>

	<br />

	<div class="tc_formulario">

		<div class="titulo_tabela">Observações sobre a Análise de Peças</div>

		<br />

		<div class="row-fluid">

			<div class="span1"></div>

	        <div class='span10'>
	            <div class='control-group <?=(in_array("observacao", $msg_erro["campos"])) ? "error" : ""?>'>
	                <label class='control-label' for='observacao'>Observações</label>
	                <div class='controls controls-row'>
	                    <div class='span12'>
	                          <textarea class="span12" name="observacao" id="observacao" rows="5"><?php echo $observacao; ?></textarea>
	                    </div>
	                </div>
	            </div>
	        </div>

	        <div class="span1"></div>

		</div>

		<br />

	</div>

	<br />

	<!-- ANexo -->
	<div id="div_anexos" class="tc_formulario">

		<div class="titulo_tabela">Anexo(s)</div>

		<br />

		<div class="tac">

			<div style="clear: both;"></div>

			<?php

			$fabrica_qtde_anexos = 3;

			if ($fabrica_qtde_anexos > 0) {

				if (strlen($analise_peca) > 0) {

					$ano = get_data_analise_peca($analise_peca, "ano");
					$mes = get_data_analise_peca($analise_peca, "mes");

				}

				echo "<input type='hidden' name='anexo_chave' value='{$anexo_chave}' />";

				for ($i = 0; $i < $fabrica_qtde_anexos; $i++) {

					unset($anexo_link);

					$anexo_imagem = "imagens/imagem_upload.png";
					$anexo_s3     = false;
					$anexo        = "";
				
					if (strlen(getValue("anexo[{$i}]")) > 0 && getValue("anexo_s3[{$i}]") != "t" && strlen($gravado) == 0) {

					 	$anexos = $s3->getObjectList(getValue("anexo[{$i}]"), true);

					 	$ext = strtolower(preg_replace("/.+\./", "", basename($anexos[0])));

					 	if ($ext == "pdf") {
					 		$anexo_imagem = "imagens/pdf_icone.png";
					 	} else if (in_array($ext, array("doc", "docx"))) {
					 		$anexo_imagem = "imagens/docx_icone.png";
					 	} else {
					 		$anexo_imagem = $s3->getLink("thumb_".basename($anexos[0]), true);
					 	}

						$anexo_link = $s3->getLink(basename($anexos[0]), true);
						$anexo      = getValue("anexo[$i]");

					} else if(strlen($analise_peca) > 0) {

					    $anexos = $s3->getObjectList("{$login_fabrica}_{$analise_peca}_{$i}", false, $ano, $mes);
					   
					    if (count($anexos) > 0) {

					 		$ext = strtolower(preg_replace("/.+\./", "", basename($anexos[0])));
					 		if ($ext == "pdf") {
					 			$anexo_imagem = "imagens/pdf_icone.png";
					 		} else if (in_array($ext, array("doc", "docx"))) {
					 			$anexo_imagem = "imagens/docx_icone.png";
					 		} else {
					 			$anexo_imagem = $s3->getLink("thumb_".basename($anexos[0]), false, $ano, $mes);
					 		}
					
							$anexo_link = $s3->getLink(basename($anexos[0]), false, $ano, $mes);
							$anexo      = basename($anexos[0]);
							$anexo_s3   = true;
					    }
					}

					?>

					<div id="div_anexo_<?=$i?>" class="tac" style="display: inline-block; margin: 0px 5px 0px 5px;">
						<?php if (isset($anexo_link)) { ?>
							<a href="<?=$anexo_link?>" target="_blank" >
						<?php } ?>

						<img src="<?=$anexo_imagem?>" class="anexo_thumb" style="width: 100px; height: 90px;" />

						<?php if (isset($anexo_link)) { ?>
							</a>
							<script>setupZoom();</script>
						<?php } ?>

						<?php
						if ($anexo_s3 === false) {
						?>
						    <button id="anexar_<?=$i?>" type="button" class="btn btn-mini btn-primary btn-block btn_acao_anexo" name="anexar" rel="<?=$i?>" >Anexar</button>
						<?php
						}
						?>

						<img src="imagens/loading_img.gif" class="anexo_loading" style="width: 64px; height: 64px; display: none;" />

						<input type="hidden" rel="anexo" name="anexo[<?=$i?>]" value="<?=$anexo?>" />
						<input type="hidden" name="anexo_s3[<?=$i?>]" value="<?=($anexo_s3) ? 't' : 'f'?>" />
						<?php
						if ($anexo_s3 === true) {?>							
							<button id="excluir_<?=$i?>" type="button" class="btn btn-mini btn-danger btn-block btn_acao_anexo" name="excluir" rel="<?=$i?>" >Excluir</button>
							<button id="baixar_<?=$i?>" type="button" class="btn btn-mini btn-info btn-block" name="baixar" onclick="window.open('<?=$anexo_link?>')">Baixar</button>
							
		            	<?php	
		            	}
		            	?>		

					</div>

	            <?php	            	
				}
	        }
			?>

		</div>

		<br />

	</div>

	<br />

	<input type="hidden" name="btn_acao" value="" />
	<input type="hidden" name="analise_peca" value="<?php echo $analise_peca; ?>" />

	<div class="row-fluid">
		<div class="span4">
			<?php if(!empty($analise_peca)){ ?>

				<div class="box_finalizar_analise tac" style="display : <?php echo (empty($termino_final)) ? "block" : "none" ?>;" >
					<button type="button" class="btn btn-warning" id="finalizar_analise" rel="<?php echo $analise_peca; ?>">Finalizar Análise</button>
				</div>

				<div class="box_reabrir_analise tac" style="display : <?php echo (!empty($termino_final)) ? "block" : "none" ?>;">
					<button type="button" class="btn btn-primary" id="reabrir_analise" rel="<?php echo $analise_peca; ?>">Reabrir Análise</button>
				</div>

			<?php } ?>
		</div>
		<div class="span4">
			<div class="box_gravar_alterar tac" style="display: <?php echo (strlen($termino_final) > 0) ? 'none' : 'block'; ?>; width: 100%;">
				<input type="submit" class="btn <?php echo (empty($analise_peca)) ? "btn-large" : "btn-success"; ?>" name="gravar" value=" <?php echo (empty($analise_peca)) ? "Gravar" : "Alterar"; ?> Análise" />
			</div>
		</div>
		<div class="span4 tac">
			<?php if(!empty($analise_peca)){ ?>
				<button type="button" class="btn btn-danger" id="excluir_analise" rel="<?=$analise_peca?>">Excluir Análise</button>
			<?php } ?>
		</div>
	</div>

	<br />

	<div class="tac" id="carregando"></div>

	<br />

</form>

<?php

if ($fabrica_qtde_anexos > 0) {
	for ($i = 0; $i < $fabrica_qtde_anexos; $i++) {
    ?>
		<form name="form_anexo" method="post" action="analise_pecas.php" enctype="multipart/form-data" style="display: none;" >
			<input type="file" name="anexo_upload_<?=$i?>" value="" />

			<input type="hidden" name="ajax_anexo_upload" value="t" />
			<input type="hidden" name="anexo_posicao" value="<?=$i?>" />
			<input type="hidden" name="anexo_chave" value="<?=$anexo_chave?>" />
		</form>
	<?php
	}
}

?>

<?php include "rodape.php"; ?>
