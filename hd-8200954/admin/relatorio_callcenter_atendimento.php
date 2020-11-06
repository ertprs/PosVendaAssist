<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

if ($trava_cliente_admin) {
	$admin_privilegios="call_center";
	$layout_menu = "callcenter";
} else {
	$admin_privilegios="financeiro,gerencia,call_center";
	$layout_menu = "callcenter";
}

include 'autentica_admin.php';
include 'funcoes.php';
include "monitora.php";

$title = "RELATÓRIO DOS ATENDIMENTOS POR POSTO";

if ($btn_acao=="Consultar") {

	//flush();

	if (strlen($_GET['data_inicial']) > 0)
		$data_inicial = $_GET['data_inicial'];
	else
		$data_inicial = $_POST['data_inicial'];

	if (strlen($_GET['data_final']) > 0)
		$data_final   = $_GET['data_final'];
	else
		$data_final   = $_POST['data_final'];

	if (strlen($codigo_posto) > 0) {

		$sql = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '$codigo_posto' AND fabrica = $login_fabrica";
		$res = pg_exec($con, $sql);

		if (pg_num_rows($res) > 0) {
			$posto = pg_result($res, 0, 'posto');
		}

	}

	if (strlen($_GET['estado']) > 0)
		$estado = $_GET['estado'];
	else
		$estado = $_POST['estado'];

	if (strlen($_GET['linha']) > 0)
		$linha = $_GET['linha'];
	else
		$linha = $_POST['linha'];

	if (strlen($_GET['familia']) > 0)
		$familia = $_GET['familia'];
	else
		$familia = $_POST['familia'];

	//HD 247592
	$cliente_admin = $_GET["cliente_admin"];
	if (strlen($cliente_admin) == 0) {
		$cliente_admin = $_POST["cliente_admin"];
	}

	if (strlen($_GET['protocolo_consumidor']) > 0)
		$protocolo_consumidor = $_GET['protocolo_consumidor'];
	else
		$protocolo_consumidor = $_POST['protocolo_consumidor'];
	if (strlen($cliente_admin) > 0) {

		$cliente_admin = intval($cliente_admin);

		$sql = "SELECT cliente_admin
					FROM tbl_cliente_admin
				WHERE cliente_admin = $cliente_admin
				AND fabrica = $login_fabrica";

		$res = pg_query($con, $sql);

	}
	//HD 247592: FIM

	if ((strlen($data_inicial) > 0 AND $data_inicial!="dd/mm/aaaa") AND (strlen($data_final)>0 AND $data_final!="dd/mm/aaaa")) {

	    if(empty($data_inicial) OR empty($data_final)){
	        $msg_erro["msg"][] = "Data Inválida";
	        $msg_erro["campos"][] = "data_inicial";
	        $msg_erro["campos"][] = "data_final";

	    }

	    if(count($msg_erro["msg"])==0){
	        list($di, $mi, $yi) = explode("/", $data_inicial);
	        if(!checkdate($mi,$di,$yi)){
	        	$msg_erro["msg"][] = "Data Inválida";
	        	$msg_erro["campos"][] = "data_inicial";
	        }
	    }

	    if(count($msg_erro["msg"])==0){
	        list($df, $mf, $yf) = explode("/", $data_final);
	        if(!checkdate($mf,$df,$yf)) {
	        	$msg_erro["msg"][] = "Data Inválida";
	        	$msg_erro["campos"][] = "data_final";
	        }
	    }

	    if(count($msg_erro["msg"])==0){
	        $aux_data_inicial = "$yi-$mi-$di";
	        $aux_data_final = "$yf-$mf-$df";
	    }
	    if(count($msg_erro["msg"])==0){
	        if(strtotime($aux_data_final) < strtotime($aux_data_inicial)
	        or strtotime($aux_data_final) > strtotime('today')){
	            $msg_erro["msg"][] = "Data Inválida.";
	        	$msg_erro["campos"][] = "data_inicial";
	        	$msg_erro["campos"][] = "data_final";
	        }
	    }

		if (count($msg_erro["msg"]) == 0) {


			if($login_fabrica == 52) {
				$parametro_data      = ' -6 months';
				$parametro_descricao = '6 meses';
			} else {
				$parametro_data      = ' -1 month';
				$parametro_descricao = '1 mês';
			}

			if (strtotime($aux_data_inicial) < strtotime($aux_data_final . $parametro_data)) {
				$msg_erro["msg"][] = "O intervalo entre as datas não pode ser maior que $parametro_descricao.";
	        	$msg_erro["campos"][] = "data_inicial";
	        	$msg_erro["campos"][] = "data_final";
			}

		}

	} else {

		$msg_erro["msg"][] = "Preencha os campos obrigatórios.";
    	$msg_erro["campos"][] = "data_inicial";
    	$msg_erro["campos"][] = "data_final";


	}

	if($login_fabrica == 52){
		$peca 				= $_POST["peca"];
		$peca_referencia 	= $_POST["peca_referencia"];
		$peca_descricao 	= $_POST["peca_descricao"];
		$defeito_constatado_grupo 	= $_POST["defeito_constatado_grupo"];
	}

	if ($btn_acao == "Consultar" AND strlen($msg_erro["msg"]) == 0) {

		if (strlen($estado) > 0) {
			$cond1 = "AND tbl_posto_fabrica.contato_estado = '$estado'";
		}

		if (strlen($marca) > 0) {
			$cond1 = "AND tbl_os.marca = '$marca'";
		}

		if (strlen($linha) > 0) {
			$cond2 = "AND tbl_produto.linha   = $linha";
		}

		if (strlen($familia) > 0) {
			$cond3 = "AND tbl_produto.familia  = $familia";
		}

		if (strlen($posto) > 0) {
			$cond4 = "AND tbl_os.posto = $posto";
		}

		if (strlen($cliente_admin) > 0) {//HD 247592
			$cond6 = "AND tbl_os.cliente_admin = $cliente_admin";
		}

		//A variável $trava_cliente_admin é definida na pasta ../admin_cliente/relatorio_callcenter_atendimento.php, este programa dá um include no programa da pasta admin, definindo esta variável para trava
		if (strlen($trava_cliente_admin) > 0) {
			$cond6 = "AND tbl_os.cliente_admin = $trava_cliente_admin";
		}

		if (strlen($cidade_consumidor) > 0) {//HD 250178
			$cond7 = "AND TRANSLATE(cidadeB.nome, 'áâàãäéêèëíîìïóôòõúùüçÁÂÀÃÄÉÊÈËÍÎÌÏÓÔÒÕÚÙÜÇ','aaaaaeeeeiiiioooouuucaaaaaeeeeiiiioooouuuc') ~* replace('^' || TRANSLATE('".strtoupper(trim($cidade_consumidor))."', 'áâàãäéêèëíîìïóôòõúùüçÁÂÀÃÄÉÊÈËÍÎÌÏÓÔÒÕÚÙÜÇ','aaaaaeeeeiiiioooouuucaaaaaeeeeiiiioooouuuc'), '.', E'.*s?') ";
		}

		if (strlen($serie) > 0) {//HD 250178
			$cond8 = "AND tbl_os.serie = '$serie'";
		}

		if(strlen($protocolo_consumidor) > 0){
			$cond9 = " AND tbl_hd_chamado.protocolo_cliente = '$protocolo_consumidor' ";
		}

		if($login_fabrica == 52){
			$join_ativo = " LEFT JOIN tbl_numero_serie ON tbl_os.produto = tbl_numero_serie.produto AND tbl_os.serie = tbl_numero_serie.serie AND tbl_numero_serie.fabrica = $login_fabrica
			JOIN tbl_hd_chamado ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_hd_chamado.fabrica = $login_fabrica ";
			$campo_ordem = ", tbl_numero_serie.ordem, tbl_hd_chamado.protocolo_cliente    ,
				tbl_os.nota_fiscal					,
				to_char(tbl_os.data_nf,'DD/MM/YYYY') AS data_nf";
		}

		if(count($defeito_constatado_grupo) > 0){
			$defeito_constatado_grupo_cond = implode(",", $defeito_constatado_grupo);
			$join_dcg = "";
			$cond_defeito_constatado_grupo = " AND tbl_defeito_constatado_grupo.defeito_constatado_grupo IN($defeito_constatado_grupo_cond) ";
		}else{
			$join_dcg = "LEFT";
		}

		if(strlen($peca_referencia) > 0 || strlen($peca_descricao) > 0){
			$sql = "SELECT peca FROM tbl_peca
					WHERE fabrica = $login_fabrica
					AND   referencia = '$peca_referencia'";
			$res = pg_query($con,$sql);
			$peca = pg_fetch_result($res,0,0);

			if(strlen($peca) > 0){

				$join_peca = "
					JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
					JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto AND tbl_os_item.peca = {$peca}
				";
				$cond_peca = " AND tbl_os_item.peca = $peca "; 
			}
		}

		if ($login_fabrica == 52) {
			$campo_array_campos_adicionais = " ,tbl_hd_chamado_extra.array_campos_adicionais ";

			if (!empty($_POST["consumidor_pais"])) {
                $pais_filtro = $_POST["consumidor_pais"];
                
                $where_pais = " AND tbl_hd_chamado_extra.array_campos_adicionais::jsonb->'pais' ? '$pais_filtro' ";
            } else {
            	$where_pais = "";
            }
		}

		$sql = "SELECT DISTINCT
						tbl_hd_chamado_item.hd_chamado,
						tbl_os.os AS os,
						tbl_os.marca,
						to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
						to_char(tbl_os.data_conserto,'DD/MM/YYYY') AS data_conserto,
						to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
						tbl_os.revenda_nome AS revenda_nome,
						tbl_posto_fabrica.codigo_posto AS posto_codigo,
						tbl_posto.nome AS posto_nome,
						tbl_os.revenda_cnpj AS revenda_cnpj,
						tbl_os.consumidor_nome AS consumidor_nome,
						tbl_os.revenda_nome AS revenda_nome,
						cidadeB.estado,
						cidadeA.estado AS revenda_estado,
						tbl_os.consumidor_fone AS consumidor_fone,
						tbl_os.consumidor_cidade AS consumidor_cidade,
						tbl_os.consumidor_estado AS consumidor_estado,
						tbl_os.serie                                 ,
						tbl_os.qtde_km,
						tbl_os.qtde_km_calculada AS qtde_km_calculada,
						tbl_os.mao_de_obra AS mao_de_obra,
						tbl_produto.descricao AS descricao_produto,
						tbl_defeito_constatado.descricao AS defeito_constatado,
						tbl_defeito_constatado_grupo.descricao AS defeito_constatado_grupo
						$campo_ordem
						$campo_array_campos_adicionais
				FROM tbl_os
				JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i={$login_fabrica}
				JOIN tbl_hd_chamado_item      ON tbl_hd_chamado_item.os = tbl_os.os
				JOIN tbl_hd_chamado_extra     ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado_extra.hd_chamado
				JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto and tbl_posto_fabrica.fabrica = $login_fabrica
				LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado AND tbl_defeito_constatado.fabrica={$login_fabrica}
				LEFT JOIN tbl_revenda ON tbl_revenda.cnpj = tbl_os.revenda_cnpj
				LEFT JOIN tbl_cidade cidadeA ON cidadeA.cidade = tbl_revenda.cidade
				LEFT JOIN tbl_cidade cidadeB ON cidadeB.cidade = tbl_hd_chamado_extra.cidade
				$join_dcg JOIN tbl_defeito_constatado_grupo ON tbl_defeito_constatado_grupo.defeito_constatado_grupo = tbl_os.defeito_constatado_grupo $cond_defeito_constatado_grupo
				$join_ativo
				$join_peca
				WHERE tbl_hd_chamado_item.os is not null AND tbl_os.fabrica = $login_fabrica
					 $cond1 $cond2 $cond3 $cond4 $cond5 $cond6 $cond7 $cond8 $cond9
				AND tbl_os.excluida is not true
				AND tbl_os.data_abertura BETWEEN '$aux_data_inicial' AND '$aux_data_final'
				$where_pais";
//echo $sql;
		$res_s = pg_query($con, $sql);

		//Gerando Excel
		if ($_POST["gerar_excel"]) {
			if (pg_num_rows($res_s)>0) {
				$data = date("d-m-Y-H:i");

				//$fileName = 'relatorio-callcenter-atendimento-{$data}.xls';
				//$file = fopen("/tmp/{$fileName}", "w");

				echo `rm -f /tmp/assist/relatorio-callcenter-atendimento-{$login_fabrica}-{$data}.xls`;

        $file = fopen ("/tmp/relatorio-callcenter-atendimento-{$login_fabrica}-{$data}.xls","w");

				$thead = "
					<table border='1'>
						<thead>
							<tr>
								<th colspan='25' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
									RELATÓRIO DE CALLCENTER
								</th>
							</tr>
							<tr>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>O.S.</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Abertura</th>";
								if($login_fabrica == 52){
									$thead .=" <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'> Data Conserto</th>";
								}
							$thead .= "
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Fechamento</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Cliente</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>UF</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Posto</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Consumidor</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Cidade</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>UF.</th>";

								if ($login_fabrica == 52) {
									$thead .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>País</th>";
								}

								$thead .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Fone</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Produto</th>";
								if($login_fabrica == 52){
									$thead .=" <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'> Nota Fiscal</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'> Data Nota Fiscal</th>";
								}
								$thead .="<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Série</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Qtde. KM</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Marca</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Grupo de Defeito</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Defeito Constatado</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Peças Lançadas</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Número do Ativo</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'> Nº Controle Cliente</th>";
								if($login_fabrica == 52){
									$thead .="<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'> Mão de Obra</th>";
									$thead .="<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'> Valor KM</th>";
								}

							$thead .="</tr>
						</thead>
						<tbody>
				";
				fwrite($file, $thead);

				for ($x=0; $x<pg_num_rows($res_s);$x++){
					$os                       = trim(pg_fetch_result($res_s, $x, 'os'));
					$data_abertura            = trim(pg_fetch_result($res_s, $x, 'data_abertura'));
					$data_conserto            = trim(pg_fetch_result($res_s, $x, 'data_conserto'));
					$data_fechamento          = trim(pg_fetch_result($res_s, $x, 'data_fechamento'));
					$revenda_nome             = trim(pg_fetch_result($res_s, $x, 'revenda_nome'));
					$revenda_estado           = trim(pg_fetch_result($res_s, $x, 'revenda_estado'));
					$posto_codigo             = trim(pg_fetch_result($res_s, $x, 'posto_codigo'));
					$posto_nome               = trim(pg_fetch_result($res_s, $x, 'posto_nome'));
					$consumidor_nome          = trim(pg_fetch_result($res_s, $x, 'consumidor_nome'));
					$consumidor_cidade        = trim(pg_fetch_result($res_s, $x, 'consumidor_cidade'));
					$consumidor_uf            = trim(pg_fetch_result($res_s, $x, 'consumidor_estado'));
					$consumidor_fone          = trim(pg_fetch_result($res_s, $x, 'consumidor_fone'));
					$produto                  = trim(pg_fetch_result($res_s, $x, 'descricao_produto'));
					$serie                    = trim(pg_fetch_result($res_s, $x, 'serie'));
					$qtde_km                  = trim(pg_fetch_result($res_s, $x, 'qtde_km'));
					$defeito_constatado       = trim(pg_fetch_result($res_s, $x, 'defeito_constatado'));
					$defeito_constatado_grupo = trim(pg_fetch_result($res_s, $x, 'defeito_constatado_grupo'));
					$ordem 					= trim(pg_fetch_result($res_s, $x, 'ordem'));
					$protocolo_consumidor 	= trim(pg_fetch_result($res_s, $x, 'protocolo_cliente'));
					$marca 					= trim(pg_fetch_result($res_s, $x, 'marca'));
					$qtde_km_calculada = trim(pg_fetch_result($res_s, $x, 'qtde_km_calculada'));
					$mao_de_obra = trim(pg_fetch_result($res_s, $x, 'mao_de_obra'));

					if($login_fabrica == 52){
						$array_campos_adicionais = json_decode(trim(pg_fetch_result($res_s, $i, 'array_campos_adicionais')), true);
						
						if (!empty($array_campos_adicionais["pais"])) {
							$consumidor_pais = $array_campos_adicionais["pais"];
						} else {
							$consumidor_pais = "";
						}

						$nota_fiscal_ex = trim(pg_fetch_result($res_s, $x, 'nota_fiscal'));
						$data_nf_ex = trim(pg_fetch_result($res_s, $x, 'data_nf'));

						$total = $mao_de_obra;

						if(strlen($qtde_km_calculada) == 0){
							$qtde_km_calculada = 0;
						}
						#$total += $qtde_km_calculada;

						if(strlen($total) > 0){
							$total = $total;
						}else{
							$total = 0;
						}
						$total = number_format($total, 2,",",".");
						$qtde_km_calculada = number_format($qtde_km_calculada, 2,",",".");
					}

					if ($marca > 0 ) {
						$sqlx="select nome from  tbl_marca where marca = $marca;";
						$resx=pg_exec($con,$sqlx);
						$marca_logo_nome         = pg_fetch_result($resx, 0, 'nome');
					}else{
						$marca_logo_nome = "";
					}

					$sql_status_km = "SELECT status_os FROM tbl_os_status WHERE os = {$os}";
					$res_status_km = pg_query($con, $sql_status_km);

					if(pg_num_rows($res_status_km) > 0){

						$status_os = array();

						for($k = 0; $k < pg_num_rows($res_status_km); $k++){
							$status_os[] = pg_fetch_result($res_status_km, $k, "status_os");
						}

						if(in_array(98, $status_os) && !in_array(99, $status_os) && !in_array(100, $status_os)){
							$qtde_km = 0;
						}else if(!in_array(98, $status_os) || in_array(99, $status_os) || in_array(100, $status_os)){
							$qtde_km = number_format($qtde_km, 2,",",".");
						}

					}else{
						$qtde_km = 0;
					}

					if($qtde_km == 0.00){
						$qtde_km = 0;
					}

					//inserindo dados no arquivo
					if ($cor == "#F1F4FA"){
						$cor = '#F7F5F0';
					}else{
						$cor = '#F1F4FA';
					}

					$sql_pecas = "SELECT (tbl_peca.referencia || ' - ' || tbl_peca.descricao) as pecas_lancadas
					                FROM tbl_os_item
									JOIN tbl_peca       ON tbl_os_item.peca          = tbl_peca.peca
									JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
									WHERE tbl_os_produto.os = $os
									$cond_peca";

					$res_pecas = pg_query($con, $sql_pecas);
					$tot_pecas = pg_num_rows($res_pecas);
					$vet_pecas = array();
					$pecas_lancadas = '';
					$os_anterior = '';
					$color = "";

					if($login_fabrica == 52){

						if(pg_num_rows($res_pecas) > 0){

							for ($z = 0; $z < $tot_pecas; $z++) {
								$pecas_lancadas = pg_fetch_result($res_pecas, $z, 'pecas_lancadas');

								if($os_anterior == $os){
									$total = 0;
									$qtde_km_calculada = 0;
									$color = "bgcolor='#FF0000' color='FFFFFF' style='color: FFFFFF !important;'";
								}
								$os_anterior = $os;

								$excel .= "<tr bgcolor='$cor'>";
								$excel .= "<td $color nowrap align='left' valign='top'>$os</td>";
								$excel .= "<td $color nowrap align='left' valign='top'>$data_abertura</td>";
								$excel .= "<td $color nowrap align='left' valign='top'>$data_conserto</td>";
								$excel .= "<td $color nowrap align='left' valign='top'>$data_fechamento</td>";
								$excel .= "<td $color nowrap align='left' valign='top'>$revenda_nome</td>";
								$excel .= "<td $color nowrap align='left' valign='top'>$revenda_estado</td>";
								$excel .= "<td $color nowrap align='left' valign='top'>$posto_codigo  - $posto_nome</td>";
								$excel .= "<td $color nowrap align='left' valign='top'>$consumidor_nome</td>";
								$excel .= "<td $color nowrap align='left' valign='top'>$consumidor_cidade</td>";
								$excel .= "<td $color nowrap align='left' valign='top'>$consumidor_uf</td>";
								$excel .= "<td $color nowrap align='left' valign='top'>$consumidor_pais</td>";
								$excel .= "<td $color nowrap align='left' valign='top'>$consumidor_fone</td>";
								$excel .= "<td $color nowrap align='left' valign='top'>$produto</td>";
								$excel .= "<td $color nowrap align='left' valign='top'>$nota_fiscal_ex</td>";
								$excel .= "<td $color nowrap align='left' valign='top'>$data_nf_ex</td>";
								$excel .= "<td $color nowrap align='left' valign='top'>$serie</td>";
								$excel .= "<td $color nowrap align='left' valign='top'>$qtde_km</td>";
								$excel .= "<td $color nowrap align='left' valign='top'>$marca_logo_nome</td>";
								$excel .= "<td $color nowrap align='left' valign='top'>$defeito_constatado_grupo</td>";
								$excel .= "<td $color nowrap align='left' valign='top'>$defeito_constatado</td>";
								$excel .= "<td $color nowrap align='left' valign='top'>$pecas_lancadas</td>";
								$excel .= "<td $color nowrap align='left' valign='top'>$ordem</td>";
								$excel .= "<td $color nowrap align='left' valign='top'>$protocolo_consumidor</td>";
								$excel .= "<td $color nowrap align='left' valign='top'>R$ $total</td>";
								$excel .= "<td $color nowrap align='left' valign='top'>R$ $qtde_km_calculada</td>";
								$excel .= "</tr>";
							}
						}else{

							$excel .= "<tr bgcolor='$cor'>";
							$excel .= "<td nowrap align='left' valign='top'>$os</td>";
							$excel .= "<td nowrap align='left' valign='top'>$data_abertura</td>";
							$excel .= "<td nowrap align='left' valign='top'>$data_conserto</td>";
							$excel .= "<td nowrap align='left' valign='top'>$data_fechamento</td>";
							$excel .= "<td nowrap align='left' valign='top'>$revenda_nome</td>";
							$excel .= "<td nowrap align='left' valign='top'>$revenda_estado</td>";
							$excel .= "<td nowrap align='left' valign='top'>$posto_codigo  - $posto_nome</td>";
							$excel .= "<td nowrap align='left' valign='top'>$consumidor_nome</td>";
							$excel .= "<td nowrap align='left' valign='top'>$consumidor_cidade</td>";
							$excel .= "<td nowrap align='left' valign='top'>$consumidor_uf</td>";
							$excel .= "<td $color nowrap align='left' valign='top'>$consumidor_pais</td>";
							$excel .= "<td nowrap align='left' valign='top'>$consumidor_fone</td>";
							$excel .= "<td nowrap align='left' valign='top'>$produto</td>";
							$excel .= "<td $color nowrap align='left' valign='top'>$nota_fiscal_ex</td>";
							$excel .= "<td $color nowrap align='left' valign='top'>$data_nf_ex</td>";
							$excel .= "<td nowrap align='left' valign='top'>$serie</td>";
							$excel .= "<td nowrap align='left' valign='top'>$qtde_km</td>";
							$excel .= "<td nowrap align='left' valign='top'>$marca_logo_nome</td>";
							$excel .= "<td nowrap align='left' valign='top'>$defeito_constatado_grupo</td>";
							$excel .= "<td nowrap align='left' valign='top'>$defeito_constatado</td>";
							$excel .= "<td nowrap align='left' valign='top'>$pecas_lancadas</td>";
							$excel .= "<td nowrap align='left' valign='top'>$ordem</td>";
							$excel .= "<td nowrap align='left' valign='top'>$protocolo_consumidor</td>";
							$excel .= "<td nowrap align='left' valign='top'>R$ $total</td>";
							$excel .= "<td nowrap align='left' valign='top'>R$ $qtde_km_calculada</td>";
							$excel .= "</tr>";
						}

					}else{

						for ($z = 0; $z < $tot_pecas; $z++) {
							$vet_pecas[] = pg_result($res_pecas, $z, 'pecas_lancadas');
						}
						$pecas_lancadas = count($vet_pecas) > 0 ? implode('<br />', $vet_pecas) : '&nbsp;';

						$excel .= "<tr bgcolor='$cor'>";
						$excel .= "<td nowrap align='left' valign='top'>$os</td>";
						$excel .= "<td nowrap align='left' valign='top'>$data_abertura</td>";
						$excel .= "<td nowrap align='left' valign='top'>$data_fechamento</td>";
						$excel .= "<td nowrap align='left' valign='top'>$revenda_nome</td>";
						$excel .= "<td nowrap align='left' valign='top'>$revenda_estado</td>";
						$excel .= "<td nowrap align='left' valign='top'>$posto_codigo  - $posto_nome</td>";
						$excel .= "<td nowrap align='left' valign='top'>$consumidor_nome</td>";
						$excel .= "<td nowrap align='left' valign='top'>$consumidor_cidade</td>";
						$excel .= "<td nowrap align='left' valign='top'>$consumidor_uf</td>";
						$excel .= "<td nowrap align='left' valign='top'>$consumidor_fone</td>";
						$excel .= "<td nowrap align='left' valign='top'>$produto</td>";
						$excel .= "<td nowrap align='left' valign='top'>$serie</td>";
						$excel .= "<td nowrap align='left' valign='top'>$qtde_km</td>";
						$excel .= "<td nowrap align='left' valign='top'>$marca_logo_nome</td>";
						$excel .= "<td nowrap align='left' valign='top'>$defeito_constatado_grupo</td>";
						$excel .= "<td nowrap align='left' valign='top'>$defeito_constatado</td>";
						$excel .= "<td nowrap align='left' valign='top'>$pecas_lancadas</td>";
						$excel .= "<td nowrap align='left' valign='top'>$ordem</td>";
						$excel .= "<td nowrap align='left' valign='top'>$protocolo_consumidor</td>";
						$excel .= "</tr>";
					}
				}
				$excel .="<tr><th colspan='25' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Número de registros $x</th> </tr>";
				$excel .= "</table>";

				fwrite($file, $excel);

				if (strlen($trava_cliente_admin) > 0) {

					system(" mv /tmp/relatorio-callcenter-atendimento-{$login_fabrica}-{$data}.xls ../admin_cliente/xls/relatorio-callcenter-atendimento-{$login_fabrica}-{$data}.xls");
					echo "../admin_cliente/xls/relatorio-callcenter-atendimento-{$login_fabrica}-{$data}.xls";

				} else {
					system(" mv /tmp/relatorio-callcenter-atendimento-{$login_fabrica}-{$data}.xls xls/relatorio-callcenter-atendimento-{$login_fabrica}-{$data}.xls");
					echo "xls/relatorio-callcenter-atendimento-{$login_fabrica}-{$data}.xls";

				}
			}
			exit;
		}
	}
}


include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
   "datepicker",
   "shadowbox",
   "multiselect",
   "maskedinput",
   "dataTable"
);

include "javascript_pesquisas.php";

include("plugin_loader.php");
?>

<script language="javascript">
	$(function(){

		$("#defeito_constatado_grupo").multiselect({
		   selectedText: "# of # selected"
		});

		$.dataTableLoad();
		$("#data_inicial").datepicker({ maxDate: 0, dateFormat: "dd/mm/yy" }).mask("99/99/9999");
		$("#data_final").datepicker({ maxDate: 0, dateFormat: "dd/mm/yy" }).mask("99/99/9999");
		/**
	 	* Inicia o shadowbox, obrigatório para a lupa funcionar
	 	*/
	 	$.autocompleteLoad(Array("posto","peca"));

		Shadowbox.init();
		/**
		 * Evento que chama a função de lupa para a lupa clicada
		 */
		$("span[rel=lupa]").click(function() {
			$.lupa($(this));
		});
	});
	var hora = new Date();
	var engana = hora.getTime();

	function retorna_posto(retorno){
    	$("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
	}
	function retorna_peca(retorno){
    	$("#peca_referencia").val(retorno.referencia);
		$("#peca_descricao").val(retorno.descricao);
	}

	function habilitaPosto() {

		if (document.getElementById('ativo').checked) {
			document.getElementById('codigo_posto').disabled    = false;
			document.getElementById('descricao_posto').disabled = false;
		} else {
			document.getElementById('codigo_posto').disabled    = true;
			document.getElementById('descricao_posto').disabled = true;
		}

	}

		function fnc_pesquisa_posto2 (campo, campo2, tipo) {

		if (tipo == "codigo" ) {
			var xcampo = campo;
		}
		if (tipo == "nome" ) {
			var xcampo = campo2;
		}
		if (xcampo.value != "") {
			var url = "";
			url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
			janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
			janela.codigo  = campo;
			janela.nome    = campo2;
			janela.focus();
		}
		else{
			alert("Informe toda ou parte da informação para realizar a pesquisa!");
		}
	}


</script>


<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}?>

<br />
<div class="row"> <b class="obrigatorio pull-right">  * Campos obrigatórios </b> </div>

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' class="form-search form-inline tc_formulario" enctype="multipart/form-data">
	<div class="titulo_tabela">Parâmetros de Pesquisa</div>
	<br />
		<div class="row-fluid">
				<div class="span2"></div>

				<div class="span4">
					<div class='control-group <?=(in_array("data_inicial", $msg_erro["campos"])) ? "error" : ""?>' >
						<label class="control-label" for="data_inicial">Data Inicial</label>
						<div class="controls controls-row">
							<div class="span5"><h5 class='asteristico'>*</h5>
								<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>" >
							</div>
						</div>
					</div>
				</div>

				<div class="span4">
					<div class='control-group <?=(in_array('data_final', $msg_erro['campos'])) ? "error" : "" ?>' >
						<label class="control-label" for="data_final">Data Final</label>
						<div class="controls controls-row">
							<div class="span5">
								<h5 class='asteristico'>*</h5>
								<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
							</div>
						</div>
					</div>
				</div>

			<div class="span2"></div>
		</div>
		<div class="row-fluid">
			<div class="span2"></div>

			<div class="span4">
				<div class='control-group' >
					<label class="control-label" for="codigo_posto">Código do Posto</label>
					<div class="controls controls-row">
						<div class="span10 input-append">
							<input id="codigo_posto" name="codigo_posto" class="span12" type="text" value="<?=getValue('codigo_posto')?>" <?=$posto_readonly?> />
							<span class="add-on" rel="lupa" <?=$posto_esconde_lupa?> >
								<i class="icon-search"></i>
							</span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
						</div>
					</div>
				</div>
			</div>

			<div class="span4">
				<div class='control-group' >
					<label class="control-label" for="descricao_posto">Nome do Posto</label>
					<div class="controls controls-row">
						<div class="span10 input-append">
							<input id="descricao_posto" name="descricao_posto" class="span12" type="text" value="<?=getValue('descricao_posto')?>" <?=$posto_readonly?> />
							<span class="add-on" rel="lupa" <?=$posto_esconde_lupa?> >
								<i class="icon-search"></i>
							</span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>

			<div class="span2"></div>
		</div>
		<?if ($login_fabrica == 52) {?>
		<div class="row-fluid">
			<div class="span2"></div>

			<div class="span4">
				<div class='control-group' >
					<label class="control-label" for="peca_referencia">Referência da Peça</label>
					<div class="controls controls-row">
						<div class="span10 input-append">
							<input id="peca_referencia" name="peca_referencia" class="span12" type="text" value="<?=getValue('peca_referencia')?>" <?=$posto_readonly?> />
							<span class="add-on" rel="lupa" <?=$posto_esconde_lupa?> >
								<i class="icon-search"></i>
							</span>
							<input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" />
						</div>
					</div>
				</div>
			</div>

			<div class="span4">
				<div class='control-group' >
					<label class="control-label" for="peca_descricao">Descrição da Peça</label>
					<div class="controls controls-row">
						<div class="span10 input-append">
							<input id="peca_descricao" name="peca_descricao" class="span12" type="text" value="<?=getValue('peca_descricao')?>" <?=$posto_readonly?> />
							<span class="add-on" rel="lupa" <?=$posto_esconde_lupa?> >
								<i class="icon-search"></i>
							</span>
							<input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" />
						</div>
					</div>
				</div>
			</div>

			<div class="span2"></div>
		</div>
		<div class="row-fluid">
			<div class="span2"></div>

			<div class="span8">
				<div class='control-group' >
					<label class="control-label" for="defeito_constatado_grupo">Grupo de Defeito Constatado</label>
					<div class="controls controls-row">
						<div class="span12 input-append">
							<select name="defeito_constatado_grupo[]" id="defeito_constatado_grupo" multiple="multiple">
								<?php
								$sql_gdc = "SELECT defeito_constatado_grupo, grupo_codigo, descricao FROM tbl_defeito_constatado_grupo WHERE fabrica = $login_fabrica ORDER BY descricao, grupo_codigo, defeito_constatado_grupo ASC";
								$res_gdc = pg_query($con, $sql_gdc);

								foreach (pg_fetch_all($res_gdc) as $key) {
									$selected_defeito_constatado_grupo = ( isset($defeito_constatado_grupo) and ($defeito_constatado_grupo == $key['defeito_constatado_grupo']) ) ? "SELECTED" : '' ;

								?>
								<option value="<?php echo $key['defeito_constatado_grupo']?>" <?php echo $selected_defeito_constatado_grupo ?> >

									<?php echo $key['descricao']?> - <?php echo $key['grupo_codigo']?>

								</option>
							<?php
							}
							?>
							</select>
						</div>
					</div>
				</div>
			</div>

			<div class="span2"></div>
		</div>
		<?}?>
		<div class="row-fluid">
			<div class="span2"></div>

			<div class="span4">
				<div class='control-group' >
					<label class="control-label" for="estado">Estado do Posto Autorizado</label>
					<div class="controls controls-row">
						<div class="span12 input-append">
							<select id="estado" name="estado" class="span12">
								<option value="" >TODOS OS ESTADOS</option>
								<?php
								#O $array_estados() está no arquivo funcoes.php
								foreach ($array_estados() as $sigla => $nome_estado) {
									$selected = ($sigla == getValue('estado')) ? "selected" : "";

									echo "<option value='{$sigla}' {$selected} >{$nome_estado}</option>";
								}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>

			<div class="span2">
				<div class='control-group' >
					<label class="control-label" for="linha">Linha</label>
					<div class="controls controls-row">
						<div class="span12 input-append">
							<select id="linha" name="linha" class="span12" >
								<option value=''></option>
								<?php
								$sql = "SELECT linha, nome from tbl_linha where fabrica = $login_fabrica and ativo = true order by nome";
								$res_li = pg_query($con,$sql);

								if (pg_num_rows($res_li) > 0) {
									for($i=0;pg_num_rows($res_li)>$i;$i++){
										$xlinha = pg_fetch_result($res_li,$i,linha);
										$xnome = pg_fetch_result($res_li,$i,nome); ?>
										<option value="<?echo $xlinha;?>" <? if ($xlinha == $linha) echo " selected "; ?>> <?echo $xnome;?></option><?
									}
								}

								?>
							</select>
						</div>
					</div>
				</div>
			</div>

			<div class="span2">
				<div class='control-group' >
					<label class="control-label" for="familia">Família</label>
					<div class="controls controls-row">
						<div class="span12 input-append">
							<select id="familia" name="familia" class="span12" >
								<option value=''></option>
								<?php
								$sql = "SELECT familia, descricao from tbl_familia where fabrica = $login_fabrica and ativo = true order by descricao";
								$res_fam = pg_query($con,$sql);

								if (pg_num_rows($res_fam) > 0) {

									for($i=0;pg_num_rows($res_fam)>$i;$i++) {

										$xfamilia = pg_fetch_result($res_fam,$i,familia);
										$xdescricao = pg_fetch_result($res_fam,$i,descricao); ?>

										<option value="<?echo $xfamilia;?>"<? if ($xfamilia == $familia) echo " selected "; ?>> <?echo $xdescricao;?></option> <?
									}

								}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>

			<div class="span2"></div>
		</div>
		<?
		//HD 247592: Acrescentar filtro para cliente admin
		//A variável $trava_cliente_admin é definida na pasta ../admin_cliente/relatorio_callcenter_atendimento.php, este programa dá um include no programa da pasta admin, definindo esta variável para trava
		if ((strlen($trava_cliente_admin) == 0) && ($login_fabrica == 30 || $login_fabrica == 52 || $login_fabrica == 85)) {
		?>

		<div class="row-fluid">
			<div class="span2"></div>

			<div class="span4">
				<div class='control-group' >
					<label class="control-label" for="cliente_admin">Cliente Admin</label>
					<div class="controls controls-row">
						<div class="span12 input-append">
							<select id="cliente_admin" name="cliente_admin" class="span12">
								<option value="" >TODOS</option>
								<?php
								$sql = "SELECT cliente_admin, nome FROM tbl_cliente_admin WHERE fabrica=$login_fabrica ORDER BY nome ";
								$res_cli = pg_query($con, $sql);
								$n   = pg_num_rows($res_cli);

								for ($i = 0; $i < $n; $i++) {

									$_cliente_admin = pg_result($res_cli, $i, cliente_admin);
									$_nome          = pg_result($res_cli, $i, nome);

									if ($cliente_admin == $_cliente_admin) {
										$selected = "selected";
									} else {
										$selected = "";
									}

									echo "<option value='$_cliente_admin' $selected>$_nome</option>";

								}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>

			<div class="span2">
				<div class='control-group' >
					<label class="control-label" for="marca">Marca</label>
					<div class="controls controls-row">
						<div class="span12 input-append">
							<select id="marca" name="marca" class="span12">
								<option value="" >TODOS</option>
								<?php
								$sql_fricon = "SELECT marca, nome
									FROM tbl_marca
									WHERE tbl_marca.fabrica = $login_fabrica
									ORDER BY tbl_marca.nome ";

								$res_fricon = pg_query($con, $sql_fricon);
								for ($i=0; $i<pg_num_rows($res_fricon); $i++){
									echo"<option value='".pg_fetch_result($res_fricon,$i,0)."'";
									echo ">".pg_fetch_result($res_fricon,$i,1)."</option>\n";
								}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>

			<div class="span2">
				<div class='control-group' >
					<label class="control-label" for="serie">Série</label>
					<div class="controls controls-row">
						<div class="span12 input-append">
								<input type="text" name="serie" id="serie" class="span12" size="20" value="<?=$serie?>" />
						</div>
					</div>
				</div>
			</div>

			<div class="span2"></div>
		</div>
		<?
		}
		?>

		<div class="row-fluid">
			<div class="span2"></div>

			<div class="span4">
				<div class='control-group' >
					<label class="control-label" for="cidade_consumidor">Cidade Consumidor</label>
					<div class="controls controls-row">
						<div class="span12 input-append">
							<input type="text" name="cidade_consumidor" id="cidade_consumidor" class="span12" size="50" value="<?=$cidade_consumidor?>" />
						</div>
					</div>
				</div>
			</div>

			<?
			if ($login_fabrica == 52) {
			?>
				<div class="span4">
				<div class='control-group' >
					<label class="control-label" for="protocolo_consumidor">Nº Controle Cliente</label>
					<div class="controls controls-row">
						<div class="span12 input-append">
							<input type="text" name="protocolo_consumidor" id="protocolo_consumidor" class="span12" size="15" value="<?=$protocolo_consumidor?>" />
						</div>
					</div>
				</div>
			</div>
			<div class="span2"></div>
			</div>
			<div class="row-fluid">
				<div class="span2"></div>
				<div class="span8">
					<div class='control-group' >
						<label class="control-label" for="consumidor_pais">País</label>
						<div class="controls controls-row">
							<div class="span12 input-append">
								<select name="consumidor_pais" id="consumidor_pais">
									<option value=""></option>
									<?php
										$aux_sql = "SELECT pais, nome FROM tbl_pais";
										$aux_res = pg_query($con, $aux_sql);
										$aux_row = pg_num_rows($aux_res);

										for ($wz = 0; $wz < $aux_row; $wz++) { 
											$aux_pais = pg_fetch_result($aux_res, $wz, 'pais');
											$aux_nome = pg_fetch_result($aux_res, $wz, 'nome');

											?> <option value="<?=$aux_pais;?>"><?=$aux_nome;?></option> <?
										}
									?>
								</select>
							</div>
						</div>
					</div>
				</div>
			<?
			}
			?>

			<div class="span2"></div>
		</div>
		<br />
		<p class="tac">
			<input type="submit" class="btn" name="btn_acao" value="Consultar" />
		</p>
		<br />
</form>
</div>
<?
if (isset($res_s) and count($msg_erro["msg"]) == 0) {
	if(pg_num_rows($res_s) > 0){
		$count_res = pg_num_rows($res_s);
?>
	<table id="depara_cadastro" class='table table-striped table-bordered table-hover table-fixed'>
		<thead>
		<tr class='titulo_coluna'>
		<td>O.S.</td>
		<td>Abertura</td>
		<?
		if($login_fabrica == 52){?>
			<td>Conserto</td>
		<?
		}
		?>
		<td>Fechamento</td>
		<td>Cliente</td>
		<td>UF</td>
		<td>Posto</td>
		<td>Consumidor</td>
		<td>Cidade</td>
		<td>UF.</td>
		<?php if ($login_fabrica == 52) { ?>
			<td>País</td>
		<?php } ?>
		<td>Fone</td>
		<td>Produto</td>
		<?
		if($login_fabrica == 52){?>
			<td>Nota Fiscal</td>
			<td>Data Nota Fiscal</td>
		<?
		}
		?>
		<td>Série</td>
		<?
		if($login_fabrica == 52){?>
			<td>Qtde. KM</td>
			<td>Marca</td>
		<?
		}
		?>
		<td>Grupo de Defeito</td>
		<td>Defeito Constatado</td>
		<?
		if($login_fabrica == 52){?>
			<td>Peças Lançadas</td>
			<td>Número do Ativo</td>
			<td>Nº Controle Cliente</td>
		<?
		}
		?>
		</tr>
		</thead>
		<tbody>
	<?

	for ($i = 0 ; $i < $count_res ; $i++) {

			$os                       = trim(pg_fetch_result($res_s, $i, 'os'));
			$data_abertura            = trim(pg_fetch_result($res_s, $i, 'data_abertura'));
			$data_conserto            = trim(pg_fetch_result($res_s, $i, 'data_conserto'));
			$data_fechamento          = trim(pg_fetch_result($res_s, $i, 'data_fechamento'));
			$revenda_nome             = trim(pg_fetch_result($res_s, $i, 'revenda_nome'));
			$revenda_estado           = trim(pg_fetch_result($res_s, $i, 'revenda_estado'));
			$posto_codigo             = trim(pg_fetch_result($res_s, $i, 'posto_codigo'));
			$posto_nome               = trim(pg_fetch_result($res_s, $i, 'posto_nome'));
			$consumidor_nome          = trim(pg_fetch_result($res_s, $i, 'consumidor_nome'));
			$consumidor_cidade        = trim(pg_fetch_result($res_s, $i, 'consumidor_cidade'));
			$consumidor_uf            = trim(pg_fetch_result($res_s, $i, 'consumidor_estado'));
			$consumidor_fone          = trim(pg_fetch_result($res_s, $i, 'consumidor_fone'));
			$produto                  = trim(pg_fetch_result($res_s, $i, 'descricao_produto'));
			$serie                    = trim(pg_fetch_result($res_s, $i, 'serie'));
			$qtde_km                  = trim(pg_fetch_result($res_s, $i, 'qtde_km'));
			$defeito_constatado       = trim(pg_fetch_result($res_s, $i, 'defeito_constatado'));
			$defeito_constatado_grupo = trim(pg_fetch_result($res_s, $i, 'defeito_constatado_grupo'));

			if ($login_fabrica == 52) { /*HD - 4304128*/
				$array_campos_adicionais = json_decode(trim(pg_fetch_result($res_s, $i, 'array_campos_adicionais')), true);
				
				if (!empty($array_campos_adicionais["pais"])) {
					$consumidor_pais = $array_campos_adicionais["pais"];
				} else {
					$consumidor_pais = "";
				}
			}

			if ($login_fabrica == 52) {
				$ordem 					= trim(pg_fetch_result($res_s, $i, 'ordem'));
				$protocolo_consumidor 	= trim(pg_fetch_result($res_s, $i, 'protocolo_cliente'));
				$marca 					= trim(pg_fetch_result($res_s, $i, 'marca'));

				$nota_fiscal 					= trim(pg_fetch_result($res_s, $i, 'nota_fiscal'));

				$data_nf 					= trim(pg_fetch_result($res_s, $i, 'data_nf'));

				if ($marca > 0 ) {
					$sqlx="select nome from  tbl_marca where marca = $marca;";
					$resx=pg_exec($con,$sqlx);
					$marca_logo_nome         = pg_fetch_result($resx, 0, 'nome');
				}else{
					$marca_logo_nome = "";
				}

				$sql_status_km = "SELECT status_os FROM tbl_os_status WHERE os = {$os}";
				$res_status_km = pg_query($con, $sql_status_km);

				if(pg_num_rows($res_status_km) > 0){

					$status_os = array();

					for($k = 0; $k < pg_num_rows($res_status_km); $k++){
						$status_os[] = pg_fetch_result($res_status_km, $k, "status_os");
					}

					if(in_array(98, $status_os) && !in_array(99, $status_os) && !in_array(100, $status_os)){
						$qtde_km = 0;
					}else if(!in_array(98, $status_os) || in_array(99, $status_os) || in_array(100, $status_os)){
						$qtde_km = number_format($qtde_km, 2,",",".");
					}

				}else{
					$qtde_km = 0;
				}

				if($qtde_km == 0.00){
					$qtde_km = 0;
				}
			}

		?>
		<tr>
		<td><?echo "<a href='os_press.php?os=$os' target='_blank'>$os</a>"?></td>
		<td><?echo $data_abertura?></td>
		<?
		if($login_fabrica == 52){?>
			<td><?echo $data_conserto?></td>
		<?
		}
		?>
		<td><?echo $data_fechamento?></td>
		<td><?echo $revenda_nome?></td>
		<td><?echo $revenda_estado?></td>
		<td><?echo $posto_codigo." - ".$posto_nome?></td>
		<td><?echo $consumidor_nome?></td>
		<td><?echo $consumidor_cidade?></td>
		<td><?echo $consumidor_uf?></td>
		
		<?php if ($login_fabrica == 52) { ?>
			<td><?=$consumidor_pais;?></td>
		<?php } ?>

		<td><?echo $consumidor_fone?></td>
		<td><?echo $produto?></td>
		<?
		if($login_fabrica == 52){?>
			<td><?echo $nota_fiscal?></td>
			<td><?echo $data_nf?></td>
		<?
		}
		?>
		<td><?echo $serie?></td>
		<?
		if ($login_fabrica == 52) {
		?>
			<td><?echo $qtde_km?></td>
			<td><?echo $marca_logo_nome?></td>
		<?
		}
		?>
		<td><?echo $defeito_constatado_grupo?></td>
		<td><?echo $defeito_constatado?></td>
		<?
		if ($login_fabrica == 52) {

			$sql_pecas = "SELECT (tbl_peca.referencia || ' - ' || tbl_peca.descricao) as pecas_lancadas
					                FROM tbl_os_item
									JOIN tbl_peca       ON tbl_os_item.peca          = tbl_peca.peca
									JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
									WHERE tbl_os_produto.os = $os
									$cond_peca ";

			$res_pecas = pg_query($con, $sql_pecas);
			$tot_pecas = pg_num_rows($res_pecas);
			$vet_pecas = array();

			for ($z = 0; $z < $tot_pecas; $z++) {
				$vet_pecas[] = pg_result($res_pecas, $z, 'pecas_lancadas');
			}

			$pecas_lancadas = count($vet_pecas) > 0 ? implode('<br />', $vet_pecas) : '&nbsp;';
			?>
			<td><?echo $pecas_lancadas?></td>
			<td><?echo $ordem?></td>
			<td><?echo $protocolo_consumidor?></td>
		<?
		}
		?>

		</tr>
	<?
	}
	?>
		</tbody>
	</table>
	<?if ($login_fabrica == 52) {
		?>
		<br />

			<?php
				$jsonPOST = excelPostToJson($_POST);
			?>

			<div id='gerar_excel' class="btn_excel">
				<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
				<span><img src='imagens/excel.png' /></span>
				<span class="txt">Gerar Download</span>
			</div>
		<?php
	}?>
<br />
<?
	} else {
			echo '
							<div class="alert alert_shadobox">
								<h4>Nenhum resultado encontrado</h4>
							</div>';
	}
}


?>

<?php

include "rodape.php";

?>
