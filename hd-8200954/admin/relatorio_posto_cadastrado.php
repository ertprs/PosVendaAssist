<?php
include_once "dbconfig.php";
include_once "includes/dbconnect-inc.php";
include_once "autentica_admin.php";
include_once "funcoes.php";

if($login_fabrica == 1){
  	$sql_admin_sac = "SELECT fale_conosco FROM tbl_admin WHERE admin = {$login_admin} AND fabrica = {$login_fabrica}";
  	$res_admin_sac = pg_query($con, $sql_admin_sac);

  	if(pg_num_rows($res_admin_sac) > 0){
    	$admin_sac = pg_fetch_result($res_admin_sac, 0, "fale_conosco");
    	$admin_sac = ($admin_sac == "t") ? true : false;
  	}

}

if (filter_input(INPUT_POST,"ajax",FILTER_VALIDATE_BOOLEAN)) {
    if (filter_input(INPUT_POST,"tipo") == "retorna_linha") {
        $codigo_posto = filter_input(INPUT_POST,"codigo_posto");
        $sql = "
            SELECT  tbl_linha.linha
            FROM    tbl_linha
            JOIN    tbl_posto_linha USING(linha)
            JOIN    tbl_posto_fabrica USING(posto,fabrica)
            WHERE   codigo_posto    = '$codigo_posto'
            AND     fabrica         = $login_fabrica
            AND     tbl_linha.ativo IS TRUE
      ORDER BY      linha
        ";
        $res = pg_query($con,$sql);

        if (pg_num_rows($res) == 0) {
            echo "erro";
            exit;
        }

        echo json_encode(array("ok" => true,"linhas" => pg_fetch_all_columns($res,0)));
        exit;
    }
}

if ($_POST["btn_acao"] == "submit") {

    $codigo_posto       = filter_input(INPUT_POST,"codigo_posto");
    $descricao_posto    = filter_input(INPUT_POST,"descricao_posto");
    $estado             = filter_input(INPUT_POST,"estado");
    $cidade             = filter_input(INPUT_POST,"cidade");
	$cidade_latitude = filter_input(INPUT_POST, "cidade_latitude");
	$cidade_longitude = filter_input(INPUT_POST, "cidade_longitude");
    $bairro             = filter_input(INPUT_POST,"bairro");
    $cep_busca          = filter_input(INPUT_POST,"cep");
    $contato_endereco   = filter_input(INPUT_POST,"contato_endereco");
    $contato_bairro     = filter_input(INPUT_POST,"contato_bairro");
    $cep_posto          = filter_input(INPUT_POST,"cep_posto");
    $linha              = filter_input(INPUT_POST,"linha",FILTER_UNSAFE_RAW,FILTER_REQUIRE_ARRAY);
    $status_posto       = filter_input(INPUT_POST,"status_posto",FILTER_UNSAFE_RAW,FILTER_REQUIRE_ARRAY);

    if (!empty($cep_busca) || !empty($cep_posto)) {
		require_once '../classes/cep.php';

		$cep = (empty($cep_busca)) ? $cep_posto : $cep_busca;
		try {
			$retorno = CEP::consulta($cep,'soap');
			$retorno = array_map(utf8_encode, $retorno);
			$retorno['cidade'] = str_replace("'","",$retorno['cidade']);
		} catch(Exception $e) {
			try {
				$retorno = CEP::consulta($cep,'db');
				$retorno = array_map(utf8_encode, $retorno);
				$retorno['cidade'] = str_replace("'","",$retorno['cidade']);
			} catch(Exception $e) {
				$retorno = array("error" => utf8_encode($e->getMessage()));
				$retorno_erro = $retorno['error'];
				$retorno_erro = utf8_decode($retorno_erro);
				$msg_erro['msg'][] = $retorno_erro;
			}
		}
	}

	if (!empty($estado)) {
		$cond .= " AND tbl_posto_fabrica.contato_estado = '$estado'\n";
	}
	if (!empty($cidade)) {
		$cond .= " AND tbl_posto_fabrica.contato_cidade = '$cidade'\n";
	}

    if (!empty($codigo_posto)) {
        $cond .= " AND tbl_posto_fabrica.codigo_posto = '$codigo_posto'\n";

        $sqlLatLon = "
            SELECT  tbl_posto_fabrica.latitude AS latPosto,
                    tbl_posto_fabrica.longitude AS lngPosto
            FROM    tbl_posto_fabrica
            WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
            AND     tbl_posto_fabrica.codigo_posto = '$codigo_posto'
        ";
        $resLatLon = pg_query($con,$sqlLatLon);

        $latPosto = pg_fetch_result($resLatLon,0,latPosto);
        $lonPosto = pg_fetch_result($resLatLon,0,lngPosto);
    }

	if (empty($estado) && (empty($cidade) || empty($codigo_posto))) {
		$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
		if(empty($linha)){
			$msg_erro["campos"][] = "linha";
		}
		if(empty($estado)){
			$msg_erro["campos"][] = "estado";
		}
		if(empty($cidade)){
			$msg_erro["campos"][] = "cidade";
		}
	}

	if (count($linha) == 0) {
        $msg_erro["msg"][]    = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "linha";
	}

	if(count($msg_erro['msg']) == 0){

		$limit = (!isset($_POST["gerar_excel"])) ? "LIMIT 501" : "";

		$sqlLinha = "SELECT linha,nome FROM tbl_linha WHERE fabrica = $login_fabrica AND ativo IS TRUE";
		$resLinhas = pg_query($con,$sqlLinha);
		if(pg_last_error($con)){
			$msg_erro['msg'][] = pg_last_error($con);
		}

		if ($_POST["gerar_excel"]) {
			$sql = "";
			$sql = "SELECT	tbl_posto.posto                           ,
						tbl_posto_fabrica.contato_cidade  as cidade        ,
						tbl_posto_fabrica.contato_estado  as estado       ,
						tbl_posto.nome  AS nome_posto             ,
						lpad (tbl_posto_fabrica.sua_os::text,7,'0') AS sua_os ,
						tbl_posto_fabrica.nome_fantasia as fantasia       ,
						tbl_posto_fabrica.codigo_posto            ,
						tbl_tipo_posto.descricao                  ,
						tbl_posto_fabrica.pedido_faturado         ,
						tbl_posto_fabrica.pedido_em_garantia      ,
						tbl_posto_fabrica.coleta_peca             ,
						tbl_posto_fabrica.reembolso_peca_estoque  ,
						tbl_posto_fabrica.digita_os               ,
						tbl_posto_fabrica.prestacao_servico       ,
						tbl_posto_fabrica.pedido_via_distribuidor ,
						tbl_posto_fabrica.credenciamento          ,
						tbl_posto_fabrica.categoria               ,
						tbl_posto_fabrica.escolhe_condicao        ,
						tbl_posto_fabrica.condicao_escolhida      ,
						tbl_posto_fabrica.contato_email           ,
						tbl_tipo_gera_extrato.responsavel         ,
						tbl_tipo_gera_extrato.tipo_envio_nf       ,
						tbl_intervalo_extrato.descricao as intervalo_extrato,
						TO_CHAR (tbl_tipo_gera_extrato.data_atualizacao, 'dd/mm/YYYY hh24:ii:ss') AS data_atualizacao,
						(SELECT pedido FROM tbl_pedido WHERE fabrica=$login_fabrica AND posto = tbl_posto.posto LIMIT 1) AS pedido,
						(SELECT to_char(tbl_os.data_abertura,'DD/MM/YYYY') FROM tbl_os WHERE fabrica=$login_fabrica AND tbl_os.posto = tbl_posto.posto ORDER BY data_digitacao DESC LIMIT 1 ) AS data_abertura,
						CASE
							WHEN tbl_posto_fabrica.tipo_posto = 36 AND data_alteracao isnull THEN
								'Sim'
							ELSE
								'Não'
						END as cadastro_automatico,
						tbl_tipo_posto.descricao AS tipo_posto
				FROM	tbl_posto
				JOIN	tbl_posto_fabrica USING (posto)
				JOIN tbl_tipo_posto ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
				LEFT JOIN tbl_tipo_gera_extrato ON tbl_posto_fabrica.fabrica = tbl_tipo_gera_extrato.fabrica
				AND tbl_posto_fabrica.posto = tbl_tipo_gera_extrato.posto
				LEFT JOIN tbl_intervalo_extrato USING(intervalo_extrato)
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica
				{$limit}
				";
			$resSubmitExcel = pg_query($con,$sql);
			$count = pg_num_rows($resSubmitExcel);
			if(pg_last_error($con)){
				$msg_erro['msg'][] = pg_last_error($con);
			}

			$sqlLinha = "SELECT linha,nome FROM tbl_linha WHERE fabrica = $login_fabrica AND ativo IS TRUE";
			$resLinhas = pg_query($con,$sqlLinha);
			if(pg_last_error($con)){
				$msg_erro['msg'][] = pg_last_error($con);
			}

			if (pg_num_rows($resSubmitExcel) > 0) {
				$data = date("d-m-Y-H:i");

				$fileName = "relatorio_postos-cadastrados-{$data}.xls";

				$file = fopen("/tmp/{$fileName}", "w");

				fwrite($file, "<table border='1' >
								<thead>
									<tr>
										<th rowspan='2' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Cidade</th>
										<th rowspan='2' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Estado</th>
										<th rowspan='2' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nome</th>
										<th rowspan='2' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Código</th>
										<th rowspan='2' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nome Fantasia</th>
									    <th rowspan='2' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Email</th>
									    <th rowspan='2' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Categoria Posto</th>
										<th rowspan='2' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Tipo</th>
										<th rowspan='2' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Credenciamento</th>
										<th rowspan='2' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Credenciamento</th>
										<th rowspan='2' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Última OS</th>
										<th rowspan='2' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data ultima OS</th>
										<th rowspan='2' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Último Pedido</th>
										<th rowspan='2' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data do Último Pedido</th>
										<th rowspan='2' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Escolhe Cond. Pagamento</th>
										<th rowspan='2' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Cond. Pagamento</th>
										<th rowspan='2' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Cadastro Atutomático</th>");

					if(pg_num_rows($resLinhas) > 0){
						for ($j=0; $j < pg_num_rows($resLinhas); $j++) {
							$linha_nome = pg_fetch_result($resLinhas, $j, 'nome');
							fwrite($file,"<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' rowspan='2'>{$linha_nome}</th>");
						}
					}

					fwrite($file, "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' colspan='7'>Posto pode Digitar</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' colspan='4'>Opções de Extrato</th>
								</tr>
								<tr class='titulo_coluna'>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Pedido Faturado</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Pedido em Garantia</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Coleta de Peças</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Reembolso de Peça do Estoque</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Digita OS</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Prestação de Serviço</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Pedido via Distribuidor</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Opção de Extrato</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Atualização</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Último Extrato</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Responsável</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Tipo de envio de NF</th>
								</tr>
							<tbody>");
					for($i = 0; $i < $count; $i++){
						$posto 			 		 = pg_fetch_result($resSubmitExcel,$i,'posto');
						$nome_posto 			 = pg_fetch_result($resSubmitExcel,$i,'nome_posto');
						$nome_fantasia 			 = pg_fetch_result($resSubmitExcel,$i,'fantasia');
						$codigo_posto 			 = pg_fetch_result($resSubmitExcel,$i,'codigo_posto');
						$cidade 				 = pg_fetch_result($resSubmitExcel,$i,'cidade');
						$estado 				 = pg_fetch_result($resSubmitExcel,$i,'estado');
						$email 					 = pg_fetch_result($resSubmitExcel,$i,'contato_email');
						$categoria 				 = pg_fetch_result($resSubmitExcel,$i,'categoria');
						$tipo_posto 			 = pg_fetch_result($resSubmitExcel,$i,'tipo_posto');
						$credenciamento 		 = pg_fetch_result($resSubmitExcel,$i,'credenciamento');
						$pedido_faturado         = (pg_fetch_result($resSubmitExcel, $i, 'pedido_faturado') =='t')         ? "Sim" : "Não";
						$pedido_em_garantia      = (pg_fetch_result($resSubmitExcel, $i, 'pedido_em_garantia') =='t')      ? "Sim" : "Não";
						$digita_os               = (pg_fetch_result($resSubmitExcel, $i, 'digita_os') =='t')               ? "Sim" : "Não";
						$controla_estoque        = (pg_fetch_result($resSubmitExcel, $i, 'controla_estoque') =='t')        ? "Sim" : "Não";
						$prestacao_servico       = (pg_fetch_result($resSubmitExcel, $i, 'prestacao_servico') =='t')       ? "Sim" : "Não";
						$pedido_via_distribuidor = (pg_fetch_result($resSubmitExcel, $i, 'pedido_via_distribuidor') =='t') ? "Sim" : "Não";
						$pedido_bonificacao 	 = (pg_fetch_result($resSubmitExcel, $i, 'pedido_bonificacao') =='t') ? "Sim" : "Não";
						$reembolso_peca_estoque  = (pg_fetch_result($resSubmitExcel, $i, 'reembolso_peca_estoque') =='t') ? "Sim" : "Não";
						$coleta_peca 			 = (pg_fetch_result($resSubmitExcel, $i, 'coleta_peca') =='t') ? "Sim" : "Não";
						$ultima_os 				 = pg_fetch_result($resSubmitExcel, $i, 'sua_os');
						$data_abertura 			 = pg_fetch_result($resSubmitExcel, $i, 'data_abertura');
						$pedido 				 = pg_fetch_result($resSubmitExcel, $i, 'pedido');
						$data_atualizacao		 = pg_fetch_result($resSubmitExcel, $i, 'data_atualizacao');
						$responsavel		     = pg_fetch_result($resSubmitExcel, $i, 'responsavel');
						$escolhe_condicao		 = pg_fetch_result($resSubmitExcel, $i, 'escolhe_condicao');
						$condicao_escolhida		 = pg_fetch_result($resSubmitExcel, $i, 'condicao_escolhida');
						$cadastro_automatico 	 = pg_fetch_result($resSubmitExcel, $i, 'cadastro_automatico');
						$opcao_de_extrato 	 = pg_fetch_result($resSubmitExcel, $i, 'intervalo_extrato');

						switch(pg_fetch_result($resSubmitExcel,$i,'tipo_envio_nf')) {

							case 'correios' : $tipo_envio_nfe = 'Correios'; break;
							case 'online_possui_nfe' : $tipo_envio_nfe = 'Online/Possui NF-e'; break;
							case 'online_nao_possui_nfe': $tipo_envio_nfe = 'Online/Não Possui NF-e'; break;

							default: $tipo_envio_nfe = ''; break;

						}

						if($escolhe_condicao != "t"){
							$escolhe_condicao = "Sem Marcar";
						}else{
							$escolhe_condicao = ($condicao_escolhida == "t") ? "Escolhido" : "Não escolhido";
						}


						$ultimo_pedido = "";
						$data_ultimo_pedido = "";
						if(!empty($pedido)){
							$sqlPedido = "SELECT pedido,seu_pedido,
										  TO_CHAR(tbl_pedido.data, 'DD/MM/YYYY') AS data_ultimo_pedido
										  FROM tbl_pedido
										  WHERE fabrica = $login_fabrica
										  AND posto = $posto
										  ORDER BY pedido DESC LIMIT 1";
							$resPedido = pg_query($con,$sqlPedido);
							$ultimo_pedido = pg_fetch_result($resPedido, 0, 'seu_pedido');
							$data_ultimo_pedido = pg_fetch_result($resPedido, 0, 'data_ultimo_pedido');
						}

						$condicao_pagamento = "";
						$sqlCondicao = "SELECT tbl_black_posto_condicao.condicao
												FROM tbl_black_posto_condicao
												JOIN tbl_posto_fabrica ON tbl_black_posto_condicao.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
												WHERE tbl_black_posto_condicao.posto = $posto";
						$resCondicao = pg_query($con, $sqlCondicao);

						if(pg_num_rows($resCondicao) > 0){
							$condicao_pagamento = pg_fetch_result($resCondicao, 0, 'condicao');
						}

						$data_credenciamento = "";
						$sqlCred = " SELECT TO_CHAR(tbl_credenciamento.data, 'DD/MM/YYYY') AS data_credenciamento
						 					FROM tbl_credenciamento
						 					WHERE fabrica = $login_fabrica
						 					AND posto = $posto
						 					AND status = 'CREDENCIADO'
						 					ORDER BY credenciamento DESC LIMIT 1";
						$resCred = pg_query($con, $sqlCred);

						if(pg_num_rows($resCred) > 0){
							$data_credenciamento = pg_fetch_result($resCred, 0, 'data_credenciamento');
						}

						$data_ultimo_extrato = "";
						$sqlExtrato = "SELECT TO_CHAR(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_ultimo_extrato
											FROM tbl_extrato
											WHERE fabrica = $login_fabrica
											AND posto = $posto
											ORDER BY extrato desc limit 1 ";
						$resExtrato = pg_query($con, $sqlExtrato);
						if(pg_num_rows($resExtrato) > 0){
							$data_ultimo_extrato = pg_fetch_result($resExtrato, 0, 'data_ultimo_extrato');
						}

						$ultimo_pedido = fnc_so_numeros($ultimo_pedido);
						fwrite($file, "<tr>
										<td>{$cidade}</td>
										<td>{$estado}</td>
										<td>{$nome_posto}</td>
										<td>{$codigo_posto}</td>
										<td>{$nome_fantasia}</td>
										<td>{$email}</td>
										<td>{$categoria}</td>
										<td>{$tipo_posto}</td>
										<td>{$credenciamento}</td>
										<td>{$data_credenciamento}</td>
										<td>{$codigo_posto}{$ultima_os}</td>
										<td>{$data_abertura}</td>
										<td>{$ultimo_pedido}</td>
										<td>{$data_ultimo_pedido}</td>
										<td>{$escolhe_condicao}</td>
										<td>{$condicao_pagamento}</td>
										<td>{$cadastro_automatico}</td>");

										if(pg_num_rows($resLinhas) > 0){
											for ($j=0; $j < pg_num_rows($resLinhas); $j++) {
												$linha = pg_fetch_result($resLinhas, $j, 'linha');
												$sqlPostoLinha = "SELECT posto
																  FROM tbl_posto_linha
																  WHERE tbl_posto_linha.posto = $posto
																  AND tbl_posto_linha.linha = $linha";
												$resPostoLinha = pg_query($con,$sqlPostoLinha);
												$atende = (pg_num_rows($resPostoLinha) > 0) ? "Sim" : "Não";
												fwrite($file, "<td>{$atende}</td>");
											}
										}

										fwrite($file, "<td>{$pedido_faturado}</td>
										<td>{$pedido_em_garantia}</td>
										<td>{$coleta_peca}</td>
										<td>{$reembolso_peca_estoque}</td>
										<td>{$digita_os}</td>
										<td>{$prestacao_servico}</td>
										<td>{$pedido_via_distribuidor}</td>
										<td>{$opcao_de_extrato}</td>
										<td>{$data_atualizacao}</td>
										<td>{$data_ultimo_extrato}</td>
										<td>{$responsavel}</td>
										<td>{$tipo_envio_nfe}</td>
									</tr>");
					}
				fwrite($file, "<tr>
							<th colspan='44' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de ".pg_num_rows($resSubmitExcel)." registros</th>
						</tr></tbody></table>");
			}
			fclose($file);

			if (file_exists("/tmp/{$fileName}")) {
				system("mv /tmp/{$fileName} xls/{$fileName}");

				echo "xls/{$fileName}";
			}
			exit;
		}
	}

}

$layout_menu = "callcenter";
$title = "RELATÓRIO DE POSTOS AUTORIZADOS CADASTRADOS";

include_once "cabecalho_new.php";

$plugins = array(
    "shadowbox",
    "datepicker",
    "dataTable",
    "multiselect",
    "tooltip"

);

include("plugin_loader.php");

if ($_RESULT && !$_POST) {
	$valor_input = $_RESULT;
} else {
	$valor_input = $_POST;
}

foreach ($array_estados as $key => $value) {
	$inputs['estado']['options'][$key] = $value;
}

?>

<style>

	#body {
		width: 850px;
		position: relative;
		margin: auto;
		text-align: center;
		bottom: 20px;
	}

	#body ul {
		margin-left: -20px;
	}

	#body ul li {
		text-align: left;
	}

	#footer {
		width: 1024px;
		position: relative;
		margin: auto;
	}

	#GoogleMaps{
		width: 847px;
		height: 400px;
		border: 1px black solid;
		position: relative;
		margin-top: 20px;
		float: left;
	}

	#direction{
		width: 300px;
		height: 400px;
		border-top: 1px black solid;
		border-right: 1px black solid;
		border-bottom: 1px black solid;
		position: relative;
		margin-top: 20px;
		float: left;
		overflow: auto;
	}

	.adp-placemark{
		width: 100%;
	}

	.adp-placemark tbody tr td img{
		width: 26px;
		max-width: 26px !important;
	}

	.popover {
		display:block !important;
	   max-width: 800px!important;
	   width:auto;
	}


</style>
<script type="text/javascript" src="js/jquery.mask.js"></script>
<link href="plugins/leaflet/leaflet.css" rel="stylesheet" type="text/css" />
<script src="plugins/leaflet/leaflet.js" ></script>
<script src="plugins/leaflet/map.js" ></script>
<script src="plugins/mapbox/geocoder.js"></script>
<script src="plugins/mapbox/polyline.js"></script>

<script type="text/javascript" language='javascript'>
var buscaCepCidade = "";
$(function(){
	$(document).on("mouseover","#tooltipee",function(){
		$(this).popover()
	});

	$("#status_posto").multiselect({
	   selectedText: "# de # opções"
	});

    $("#linha").multiselect({
        selectedText: "# de # opções"
    });

    Shadowbox.init();

    $("span[rel=lupa]").click(function () {
        $.lupa($(this));
    });

	$("#cep").mask("99.999-999");

	$("#estado").change(function(){
		var uf = $(this).val();

		$.ajax({
            url: "ajax_cidade.php",
            type:"POST",
            dataType:"JSON",
            data : {
                ajax : true,
                uf : uf,
                acao: "consulta_cidades_ibge"
            }
        })
        .always(function(response){
            var option = "<option value=''>SELECIONE</option>";
            $.each(response.cidades,function(index,obj){
                option += "<option value='" + obj.distrito + "' data-latitude='" + obj.latitude.replace(",", ".") + "' data-longitude='" + obj.longitude.replace(",", ".") + "'>" + obj.distrito + "</option>";
            });

            $("#cidade").html(option);
            if (buscaCepCidade.length > 0) {
            	$("#cidade").val(buscaCepCidade);
            	$("#cidade").trigger("change");
            }
        });
    });

    $("#cidade").on("change", function () {
    	let latitude = $(this).find("option:selected").data("latitude");
    	let longitude = $(this).find("option:selected").data("longitude");

    	$("#cidade-latitude").val(latitude);
    	$("#cidade-longitude").val(longitude);
    });

	function buscaCEP(cep, callback, method = null){
	    if (typeof cep != "undefined" && cep.length > 0) {
	        if (typeof method == "undefined" || method == null || method.length == 0) {
	            method = "webservice";

	            $.ajaxSetup({
	                timeout: 10000
	            });
	        } else {
	            $.ajaxSetup({
	                timeout: 5000
	            });
	        }

	        $.ajax({
	            url: "ajax_cep.php",
	            type: "GET",
	            data: { cep: cep, method: method },
	            error: function(xhr, status, error) {
	                buscaCEP(cep, callback, "database");
	            },
	            success: function(data) {
	                results = data.split(";");

	                if(typeof callback == "function"){
	                    callback(results);
	                }
	            }
	        });
	    }
	}

	$('#cep').on('blur', function(){
	    $('#loading-block').show();
	    $('#loading').show();
	    buscaCEP($('#cep').val(), function(results){
	        $('#estado').val(results[4]);
	        $("#estado").trigger("change");
	        $('#contato_endereco').val(results[1]);
	        $('#contato_bairro').val(results[2]);
	        // $('#cidade option').remove();
	        $('#loading-block').hide();
	        $('#loading').hide();
	        buscaCepCidade = results[3];
	    });
	});

});

function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
    $("#descricao_posto").val(retorno.nome);
    $("#cep_posto").val(retorno.cep);
    $("#estado").val(retorno.estado);
    $("#estado").trigger("change");

    retorna_linha(retorno.codigo);

	setTimeout(function (){ $('#cidade').val(retorno.cidade); }, 2000);
}

function retorna_linha(codigo_posto) {
    $.ajax({
        url:"relatorio_posto_cadastrado.php",
        type:"POST",
        dataType:"JSON",
        data:{
            ajax:true,
            tipo:"retorna_linha",
            codigo_posto:codigo_posto
        }
    })
    .done(function(data){
        if (data.ok) {
            $("#linha").val(data.linhas);

            $("#linha").multiselect("refresh");
        }
    });
}

function loading (display){
	var loadingCount = 0;

	var zindexSelector = '.ui-widget';

	var subZIndex = function(){
		$(zindexSelector).each(function(){
			var oldZindex = $(this).css('z-index');
			$(this).attr('old-z-index',oldZindex);
			$(this).css('z-index',1);
		});
	};

	var returnZIndex = function(){
		$('[old-z-index]').each(function(){
			var oldZindex = $(this).attr('old-z-index');
			$(this).removeAttr('old-z-index');
			$(this).css('z-index',oldZindex);
		});
	};


	var funcLoading = function(display){

		switch (display) {
			case true:
			case "show":
				loadingCount += 1;
				if(loadingCount != 1)
					return;
				subZIndex();
				$("#loading").show();
				$("#loading-block").show();
				$("#loading_action").val("t");
				break;
			case false:
			case "hide":
				if(loadingCount >0)
					 loadingCount-= 1;
				if(loadingCount != 0)
					return;
				$("#loading").hide();
				$("#loading_action").val("f");
				$("#loading-block").hide();
				returnZIndex();
				break;
		}
	};

	window.loading = funcLoading;

}

function siglaEstado(sigla){
	switch(sigla){
		case "AC" : sigla = "Acre"; break;
		case "AL" : sigla = "Alagoas"; break;
		case "AP" : sigla = "Amapá"; break;
		case "AM" : sigla = "Amazonas"; break;
		case "BA" : sigla = "Bahia"; break;
		case "CE" : sigla = "Ceará"; break;
		case "DF" : sigla = "Distrito Federal"; break;
		case "ES" : sigla = "Espírito Santo"; break;
		case "GO" : sigla = "Goiás"; break;
		case "MA" : sigla = "Maranhão"; break;
		case "MT" : sigla = "Mato Grosso"; break;
		case "MS" : sigla = "Mato Grosso do Sul"; break;
		case "MG" : sigla = "Minas Gerais"; break;
		case "PA" : sigla = "Pará"; break;
		case "PB" : sigla = "Paraíba"; break;
		case "PR" : sigla = "Paraná"; break;
		case "PE" : sigla = "Pernambuco"; break;
		case "PI" : sigla = "Piauí"; break;
		case "RJ" : sigla = "Rio de Janeiro"; break;
		case "RN" : sigla = "Rio Grande do Norte"; break;
		case "RS" : sigla = "Rio Grande do Sul"; break;
		case "RO" : sigla = "Rondônia"; break;
		case "RR" : sigla = "Roraima"; break;
		case "SC" : sigla = "Santa Catarina"; break;
		case "SP" : sigla = "São Paulo"; break;
		case "SE" : sigla = "Sergipe"; break;
		case "TO" : sigla = "Tocantins"; break;
	}
	return sigla;
}

function retiraAcentos(palavra){
	var com_acento = "áàãâäéèêëíìîïóòõôöúùûüçÁÀÃÂÄÉÈÊËÍÌÎÏÓÒÕÖÔÚÙÛÜÇ'";
	var sem_acento = 'aaaaaeeeeiiiiooooouuuucAAAAAEEEEIIIIOOOOOUUUUC';
    var newPalavra = "";

    for(i = 0; i < palavra.length; i++) {
    	if (com_acento.indexOf(palavra.substr(i,1)) >= 0) {
      		newPalavra += sem_acento.substr(com_acento.search(palavra.substr(i,1)),1);
      	}
      	else{
       		newPalavra += palavra.substr(i,1);
    	}
    }
    return newPalavra.toUpperCase();
}

/* - - - - - - - - - - - - - - - - - - - - - - - - - - */

var pagina = "";

var latlon;
var latlonCliente = "";
var auxlatlon = "";
var marker;
var markerClick;
var map;
var geocoder;
var object;
var marks    = new Array();
var posto    = new Array();
var infotype = new Array("nome_posto", "endereco", "cidade", "estado", "email", "telefone", "observacao");

//var bounds = new google.maps.LatLngBounds();

var directionsService;
var directionsRenderer;
var directionsDisplay;

var qtdRotas = 0;

var callcenter       = '<?=$callcenter?>';
var linha            = '<?=$linha;?>';
var cidadeConsumidor = '<?=$cidade;?>';
var estadoConsumidor = '<?=$estado;?>';

function getText(posto, rel) {
	switch (rel) {
		case "nome_posto":
			var title = "<b>Nome do Posto:</b> ";
		break;

		case "endereco":
			var title = "<b>Endereço:</b> ";
		break;

		case "cidade":
			var title = "<b>Cidade:</b> ";
		break;

		case "estado":
			var title = "<b>Estado:</b> ";
		break;

		case "email":
			var title = "<b>Email:</b> ";
		break;

		case "telefone":
			var title = "<b>Telefone:</b> ";
		break;

		case "observacao":
			var title = "<b>Observação:</b> ";
		break;
	}

	return title + $.trim($("#"+posto).find("td[rel="+rel+"]").text());
}

function getInfo(posto){
	var content = new Array();

	for (var i in infotype) {
		content.push(getText(posto, infotype[i]));
	}

	return "<div style='text-align: left;'>" + content.join("<br />") + "</div>";
}

<?php

function acentos ($string) {
	$array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
	$array2 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" );
	$string = str_replace($array1, $array2, $string);


	$array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
	$array2 = array("Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" ,"Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
	$string = str_replace($array1, $array2, $string);

	$array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
	$array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C" );
	$string = str_replace($array1, $array2, $string);

	return $string;
}

	if($cep_busca != ""){
		if(utf8_decode($retorno["end"]) != ""){
			$endereco_rota = utf8_decode($retorno["end"]).",".utf8_decode($retorno["cidade"]).",".$retorno["uf"].",Brasil";
			$endereco_formatado = utf8_decode($retorno["end"]).",".utf8_decode($retorno["cidade"]).",".$retorno["uf"].",Brasil";
			$cidade_formatado = utf8_decode($retorno["cidade"]).",".$retorno["uf"].",Brasil, CEP: $cep";
		}else{
			$endereco_rota = utf8_decode($retorno["cidade"]).",".$retorno["uf"].",Brasil";
			$endereco_formatado = utf8_decode($retorno["cidade"]).",".$retorno["uf"].",Brasil";
			$cidade_formatado = utf8_decode($retorno["cidade"]).",".$retorno["uf"].",Brasil, CEP: $cep";
		}

		$address = str_replace(" ", "%20", $endereco_rota);
	}
?>

/* INICIO - MAPBOX */
var geocoder, latlon, c_lat, c_lon, token, LatLngPosto;
var Map, Markers, Router, Geocoder, geometry, endPosto;

function localizar (lat, lng, endereco, id){
	endPosto = lat+","+lng;

	Map.setView(lat, lng, 15);
	Map.scrollToMap();
}

function rota(lat, lng){
	var endCliente = "<?php echo $endereco_rota; ?>";
	endPosto = lat+","+lng;
	/*if (endPosto == undefined) {
		alert('Clique em "Localizar" no posto desejado antes de solicitar a rota!');
		return;
	}*/
	endCliente = endCliente.split(',');

	if(LatLngPosto !== undefined){
		calcRota();
		return;
	}

	try {
        Geocoder.setEndereco({
            endereco: endCliente[0],
            cidade: endCliente[1],
            estado: endCliente[2],
            pais: "Brasil"
        });

        request = Geocoder.getLatLon();

        request.then(
            function(resposta) {
                c_lat  = resposta.latitude;
                c_lon  = resposta.longitude;
                LatLngPosto = c_lat+","+c_lon;

                Markers.add(c_lat, c_lon, "blue", "Cliente");
                Markers.render();
				//Markers.focus();

				calcRota();
            },
            function(erro) {
                alert(erro);
            }
        );
    } catch(e) {
        alert(e.message);
    }
}

var RotasPostos;

function loadMaps()
{
    var Pais                = "Brasil";
    var cidade              = '<?=$cidade?>';
    var estado              = '<?=$estado?>';
    var codigo_posto        = '<?=$codigo_posto?>';
    var latPosto            = '<?=$latPosto?>';
    var lonPosto            = '<?=$lonPosto?>';
    var cep                 = '<?=$cep_busca?>';
    var cep_posto           = '<?=$cep_posto?>';
    var bairro              = '<?=$contato_bairro?>';
    var endereco            = '<?=$contato_endereco?>';
    var linha               = '<?=json_encode($linha)?>';
    var status_posto        = '<?=json_encode($status_posto); ?>';
    var endereco_formatado  = '';

    var latCidade = '<?= $cidade_latitude ?>';
    var longCidade = '<?= $cidade_longitude ?>';

	loading("show");

		if ((typeof cep == "string" && cep !== "") || (typeof cep_posto == "string" && cep_posto !== "")) {
			relatorio_posto = true;
		} else {
			relatorio_posto = "semCep";
		}

	    if (typeof Map !== "object") {
	        Map      = new Map("GoogleMaps");
	        Markers  = new Markers(Map);
	        Router   = new Router(Map);
	    }

		try {
            if (codigo_posto == "") {
                latlon = latCidade + "," + longCidade;

                var dataAjax = {
                    latlon: latlon,
                    callcenter: false,
                    relatorio_posto: relatorio_posto,
                    cep: cep,
                    linha: linha,
                    consumidor_estado: estado,
                    consumidor_cidade: cidade,
                    status_posto: status_posto
                };

                $.ajax({
                    url: 'mapa_rede_ajax.php',
                    type: 'get',
                    data: dataAjax
                })
                .done(function(data){
                    /* Lista Informaçoes dos Postos */
                    data = data.split('*');
                    CliLatLng = data[((data.length) - 1)].split(",");
                    qtdRotas = 1;

                    $('.tbody').html(data[2]);

                    Map.load();

                    Markers.remove();
                    Markers.clear();

                    $(".tbody").find("tr.posto").each(function() {
                        var lat        = $(this).find("input[name=lat]").val();
                        var lon        = $(this).find("input[name=lng]").val();
                        var nome_posto = ($(this).find("td[rel=nome_posto]:first").next().text()).trim();
                        if (nome_posto == '') {
                            var nome_posto = ($(this).find("td[rel=nome_posto]:first").text()).trim();
                        }

                        var observacao = ($(this).find("td[rel=observacao]:first").text()).trim();
                        if (observacao !== '') {
                            observacao = "<br /><br /><label style='color:red;'><strong>Observação:</strong></label>"+observacao+"<br />";
                        }

                        Markers.add(lat, lon, "red", nome_posto+observacao);
                    });

                    if (cep !== '') {
                        Markers.add(CliLatLng[0], CliLatLng[1], "blue", "Cliente");
                    }

                    Markers.render();
                    Markers.focus();

                    RotasPostos = JSON.parse(data[5]);

                    loading("hide");

                    table_excel = data[3];

                })
                .fail(function(){
                    $('.tbody').html("<td colspan='9'><br /><h1 align='center'>Nenhum Posto localizado com o Endereço/CEP Informado</h1><br /></td>");
                    loading("hide");
                });
	        } else {

                var dataAjax = {
                    latlon: latPosto+","+lonPosto,
                    callcenter: false,
                    relatorio_posto: relatorio_posto,
                    cep: cep,
                    cep_posto:cep_posto,
                    linha: linha,
                    consumidor_estado: estado,
                    consumidor_cidade: cidade,
                    status_posto: status_posto,
                    codigo_posto:codigo_posto
                };

                $.ajax({
                    url: 'mapa_rede_ajax.php',
                    type: 'get',
                    data: dataAjax
                })
                .done(function(data){
                    /* Lista Informaçoes dos Postos */
                    data = data.split('*');
                    CliLatLng = data[((data.length) - 1)].split(",");
                    qtdRotas = 1;

                    $('.tbody').html(data[2]);

                    Map.load();

                    Markers.remove();
                    Markers.clear();

                    $(".tbody").find("tr.posto").each(function() {
                        var lat        = $(this).find("input[name=lat]").val();
                        var lon        = $(this).find("input[name=lng]").val();
                        var nome_posto = ($(this).find("td[rel=nome_posto]:first").next().text()).trim();
                        if (nome_posto == '') {
                            var nome_posto = ($(this).find("td[rel=nome_posto]:first").text()).trim();
                        }

                        var observacao = ($(this).find("td[rel=observacao]:first").text()).trim();
                        if (observacao !== '') {
                            observacao = "<br /><br /><label style='color:red;'><strong>Observação:</strong></label>"+observacao+"<br />";
                        }

                        Markers.add(lat, lon, "red", nome_posto+observacao);
                    });

                    if (cep !== '') {
                        Markers.add(CliLatLng[0], CliLatLng[1], "blue", "Cliente");
                    }

                    Markers.render();
                    Markers.focus();

                    RotasPostos = JSON.parse(data[5]);

                    loading("hide");

                    table_excel = data[3];

                })
                .fail(function(){
                    $('.tbody').html("<td colspan='9'><br /><h1 align='center'>Nenhum Posto localizado com o Endereço/CEP Informado</h1><br /></td>");
                    loading("hide");
                });
	        }
	    } catch(e) {
	        alert(e.message);
	    }
}

$(document).on("click", "a.rota", function() {
	var id = $(this).parent().data("id");

	var rota = RotasPostos[id];

	Router.remove();
	Router.clear();
	Router.add(Polyline.decode(rota.routes[0].geometry));
	Router.render();
});

<?php if($_POST["btn_acao"] == "submit" and count($msg_erro["msg"]) == 0) { ?>
	$(function() {
		loadMaps();
	});
<?php } ?>
</script>

<?php

if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>
<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name="frm_condicao" method="POST" class="form-search form-inline tc_formulario" >
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<br/>

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
			<div class='control-group <?=(in_array("linha", $msg_erro["campos"])) ? "error" : ""?>'>

				<?php
					$sql = "SELECT * FROM tbl_linha WHERE tbl_linha.fabrica = $login_fabrica ORDER BY tbl_linha.nome;";
					$res = pg_exec ($con,$sql);
				?>
				<label class='control-label' for='Linha'>Linha</label>
				<div class='controls controls-row'>
                    <h5 class='asteristico'>*</h5>
                    <select name='linha[]' id='linha' multiple="multiple">
                            <?php
                                if (pg_numrows($res) > 0) {
                                    for ($x = 0 ; $x < pg_numrows($res) ; $x++) {
                                        $aux_linha = trim(pg_result($res,$x,linha));
                                        $aux_nome  = trim(pg_result($res,$x,nome));


                                        $selected = (in_array($aux_linha,$linha )) ? "SELECTED" : "";
                                        echo "<option value='$aux_linha' $selected>$aux_nome</option>";
                                    }
                                }
                            ?>
                    </select>
                </div>
			</div>
		</div>
		<div class='span4'>
            <div class='control-group <?=(in_array("status_posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='status_posto'>Status Posto</label>
                <div class='controls controls-row'>
                    <div class='span4'>
<?php

foreach ($status_posto as $key => $value) {
    switch ($value) {
        case 'CREDENCIADO':
            $credenciado = "CREDENCIADO";
            break;
        case 'DESCREDENCIADO':
            $descredenciado = "DESCREDENCIADO";
            break;
        case 'EM CREDENCIAMENTO':
            $em_credenciamento = "EM CREDENCIAMENTO";
            break;
        case 'EM DESCREDENCIAMENTO':
            $em_descredenciamento = "EM DESCREDENCIAMENTO";
            break;
    }
}
?>
                        <select name="status_posto[]" id="status_posto" multiple="multiple">
                            <option value='CREDENCIADO' <?=($credenciado== "CREDENCIADO") ? "SELECTED" :""?> selected>CREDENCIADO</option>
                            <option value='DESCREDENCIADO' <?=($descredenciado== "DESCREDENCIADO") ? " SELECTED " : ""?>>DESCREDENCIADO</option>
                            <option value='EM CREDENCIAMENTO' <?=($em_credenciamento== "EM CREDENCIAMENTO") ? " SELECTED " : ""?>>EM CREDENCIAMENTO</option>
                            <option value='EM DESCREDENCIAMENTO'<?=($em_descredenciamento== "EM DESCREDENCIAMENTO") ? " SELECTED " : ""?>>EM DESCREDENCIAMENTO</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="span2"></div>
    </div>

	<div class="row-fluid">
		<div class="span2"></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("estado", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='Estado'>Estado</label>
				<div class='controls controls-row'>
					<div class='span4 input-append'>
						<h5 class='asteristico'>*</h5>
						<select id="estado" name="estado">
		  					<option value="">Selecione um estado</option>
		  					<?php
		  					$estados = array(
		  						'AC' => 'Acre',
								'AL' => 'Alagoas',
								'AP' => 'Amapá',
								'AM' => 'Amazonas' ,
								'BA' => 'Bahia',
								'CE' => 'Ceará',
								'DF' => 'Distrito Federal' ,
								'GO' => 'Goiás' ,
								'ES' => 'Espirito Santo',
								'MA' => 'Maranhão',
								'MT' => 'Mato Grosso',
								'MS' => 'Mato Grosso do Sul',
								'MG' => 'Minas Gerais',
								'PA' => 'Pará',
								'PB' => 'Paraíba',
								'PR' => 'Paraná',
								'PE' => 'Pernambuco',
								'PI' => 'Piaui',
								'RJ' => 'Rio de Janeiro',
								'RN' => 'Rio Grande do Norte',
								'RS' => 'Rio Grande do Sul',
								'RO' => 'Rondônia',
								'RR' => 'Roraima',
								'SC' => 'Santa Catarina',
								'SE' => 'Sergipe',
								'SP' => 'São Paulo',
								'TO' => 'Tocantins'
					 		);
					 		foreach ($estados as $key => $value) {
					 			if($key == $estado){
					 				$selected = "selected";
					 			}else{
					 				$selected = "";
					 			}
					 			echo "<option ".$selected." value='".$key."'>".$value."</option>";
					 		}
							?>
		  				</select>
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("cidade", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='Cidade'>Cidade</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<h5 class='asteristico'>*</h5>
						<input type="hidden" value="<?= $cidade_latitude ?>" name="cidade_latitude" id="cidade-latitude">
						<input type="hidden" value="<?= $cidade_longitude ?>" name="cidade_longitude" id="cidade-longitude">
						<select id="cidade" name="cidade">
		  					<option value="" <?php echo $cidade == "" ? "selected" : "" ?>>Selecione uma cidade</option>
							<?php
								$sql = "SELECT DISTINCT ON (UPPER(distrito)) fn_retira_especiais(distrito) AS distrito,
											id AS cod_cidade,
											latitude,
											longitude
										FROM tbl_ibge_completa
										WHERE uf = '{$estado}'
										AND (tipo = 'URBANO' OR tipo IS NULL)
										ORDER BY UPPER(distrito);";
								$res = pg_exec($con,$sql);

								for ($i=0; $i < pg_num_rows($res); $i++) {
						 			$dataCidade = [
						 				pg_fetch_result($res, $i, "distrito"),
						 				pg_fetch_result($res, $i, "latitude"),
						 				pg_fetch_result($res, $i, "longitude")
						 			];

						 			if($dataCidade[0] == $cidade)
						 				$selected = "selected";
						 			else
						 				$selected = "";

									echo "<option $selected value='". $dataCidade[0] . "' data-latitude='" . implode(".", explode(",", $dataCidade[1])) . "' data-longitude='" . implode(".", explode(",", $dataCidade[2])) ."'>" . $dataCidade[0] . "</option>";
								}
		  					?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class="span2"></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='cep'>CEP</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<input type="text" name="cep" id="cep" size="10" maxlength="10" value="<? echo $cep_busca ?>">
							<input type="hidden" name="latitudeLongitude" id="latitudeLongitude" value="">
							<input type="hidden" name="cep_posto" id="cep_posto" value="">
							<input type="hidden" name="contato_endereco" id="contato_endereco" value="<? echo $contato_endereco ?>">
							<input type="hidden" name="contato_bairro" id="contato_bairro" value="<? echo $contato_bairro ?>">
					</div>
				</div>
			</div>
		</div>
	</div>
	<p><br/>
		<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Consultar</button>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
	</p><br/>
</form>
</div>

<?php
if ($_POST["btn_acao"] == "submit" AND count($msg_erro['msg']) == 0) {
	?><div id="body" >
		<div id="GoogleMaps"></div>
	</div>
	<br/>
<?php

	// Retirada, pois só estava atrasando o carregamento da tela HD-6208384
    if($_POST["btn_acao"] == "submit" && count($msg_erro['msg']) == 0 && 1 == 2){
        $sql = "SELECT  tbl_posto.posto                                     ,
                        tbl_posto.endereco                                  ,
                        tbl_posto.numero                                    ,
                        tbl_posto.bairro                                    ,
                        tbl_posto.cep                                       ,
                        tbl_posto.fone                                      ,
                        tbl_posto_fabrica.contato_cidade  as cidade        ,
                        tbl_posto_fabrica.contato_estado  as estado       ,
                        tbl_posto.nome  AS nome_posto             ,
                        lpad (tbl_posto_fabrica.sua_os::text,7,'0') AS sua_os ,
                        tbl_posto.fantasia                        ,
                        tbl_posto_fabrica.codigo_posto            ,
                        tbl_tipo_posto.descricao                  ,
                        tbl_posto_fabrica.pedido_faturado         ,
                        tbl_posto_fabrica.pedido_em_garantia      ,
                        tbl_posto_fabrica.coleta_peca             ,
                        tbl_posto_fabrica.reembolso_peca_estoque  ,
                        tbl_posto_fabrica.digita_os               ,
                        tbl_posto_fabrica.prestacao_servico       ,
                        tbl_posto_fabrica.pedido_via_distribuidor ,
                        tbl_posto_fabrica.credenciamento          ,
                        tbl_posto_fabrica.categoria               ,
                        tbl_posto_fabrica.escolhe_condicao        ,
                        tbl_posto_fabrica.condicao_escolhida      ,
                        tbl_posto_fabrica.contato_email           ,
                        tbl_posto_fabrica.tipo_atende             ,
                        tbl_posto_fabrica.latitude                ,
                        tbl_posto_fabrica.longitude               ,
                        tbl_tipo_gera_extrato.responsavel         ,
                        tbl_tipo_gera_extrato.tipo_envio_nf       ,
                        tbl_intervalo_extrato.descricao as intervalo_extrato,
                        TO_CHAR (tbl_tipo_gera_extrato.data_atualizacao, 'dd/mm/YYYY hh24:ii:ss')   AS data_atualizacao,
                        (
                            SELECT  pedido
                            FROM    tbl_pedido
                            WHERE   fabrica = $login_fabrica
                            AND     posto   = tbl_posto.posto
                            LIMIT   1
                        )                                                                           AS pedido,
                        (
                            SELECT  to_char(tbl_os.data_abertura,'DD/MM/YYYY')
                            FROM    tbl_os
                            WHERE   fabrica = $login_fabrica
                            AND     posto   = tbl_posto.posto
                      ORDER BY      data_digitacao DESC
                            LIMIT   1
                        )                                                                           AS data_abertura,
                        CASE WHEN tbl_posto_fabrica.tipo_posto = 36 AND data_alteracao IS NULL
                             THEN 'Sim'
                             ELSE 'Não'
                        END                                                                         AS cadastro_automatico,
                        tbl_tipo_posto.descricao                                                    AS tipo_posto
                FROM    tbl_posto
                JOIN    tbl_posto_fabrica       USING (posto)
                JOIN    tbl_tipo_posto          ON  tbl_posto_fabrica.tipo_posto    = tbl_tipo_posto.tipo_posto
                JOIN    tbl_posto_linha         ON  tbl_posto_linha.posto           = tbl_posto.posto
                                                AND tbl_posto_linha.linha           IN (".implode(',',$linha).")
           LEFT JOIN    tbl_tipo_gera_extrato   ON  tbl_posto_fabrica.fabrica       = tbl_tipo_gera_extrato.fabrica
                                                AND tbl_posto_fabrica.posto         = tbl_tipo_gera_extrato.posto
           LEFT JOIN    tbl_intervalo_extrato   USING (intervalo_extrato)
                WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
                {$cond}
                {$limit}";
        $resSubmit = pg_query($con,$sql);
        $countMapa = pg_num_rows($resSubmit);
        if (pg_last_error($con)) {
            $msg_erro['msg'][] = pg_last_error($con);
        }

        if ($countMapa == 0) {
?>
			<div class="container">
				<div class="alert">
				    <h5>Não possui Posto Autorizado para a cidade selecionada</h5>
				</div>
			</div>
		<?php
		}
    }

?>

		<br />

		<style type="text/css">
		.box{
			border-radius: 3px;
			width: 20px;
			height: 20px;
			float: left;
			margin-right: 10px;
		}

		.vermelho{
			background-color: #F78181;
		}

		.amarelo{
			background-color: #F3F781;
		}

		.verde{
			background-color: #9FF781;
		}

		</style>

		<div class="container">
			<div class="row">
				<div class="span12">
					<div class="box vermelho"></div> Posto Descredenciado
				</div>
			</div>
			<div class="row">
				<div class="span12">
					<span class="box amarelo"></span> Posto atende somente revenda
				</div>
			</div>
			<div class="row">
				<div class="span12">
					<span class="box verde"></span> Posto mais de 30 KM do consumidor
				</div>
			</div>
		</div>

		<br />

		<table id="resultado_postos" align='center' class='table table-striped table-bordered table-hover table-large' >
			<thead>
				<tr class='titulo_coluna'>
<?php $colsCodigo = ($login_fabrica == 1) ? "colspan='2'" : ""?>
					<th nowrap rowspan='2' <?=$colsCodigo?>>Código</th>
					<th nowrap rowspan='2'>Nome</th>
					<th nowrap rowspan='2'>Nome Fantasia</th>
					<th nowrap rowspan='2'>Credenciamento</th>
					<th nowrap rowspan='2'>Linha</th>
					<th nowrap rowspan='2'>Rua</th>
					<th nowrap rowspan='2'>Bairro</th>
					<th nowrap rowspan='2'>Cidade</th>
					<th nowrap rowspan='2'>Estado</th>
					<th nowrap rowspan='2'>CEP</th>
				    <th nowrap rowspan='2'>Email</th>
				    <th nowrap rowspan='2'>Telefone</th>
				    <th nowrap rowspan='2'>KM</th>
				    <th nowrap rowspan='2'>Localização</th>
				    <th nowrap rowspan='2'>Rota</th>
				</tr>
			</thead>
			<tbody class="tbody"><!-- Contudo --></tbody>
		</table>

	<?php

	$jsonPOST = excelPostToJson($_POST);

	if($admin_sac != true){
	?>
		<!-- Excel -->
		<div id='gerar_excel' class="btn_excel">
			<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
			<span><img src='imagens/excel.png' /></span>
			<span class="txt">Gerar Arquivo Excel</span>
		</div>
		<!-- Fim Excel -->
<?php
	}
}

echo "<br />";

include "rodape.php";

?>
