<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="call_center";

include "autentica_admin.php";
include 'funcoes.php';

include "../helpdesk.inc.php";// Funcoes de HelpDesk hd_chamado=2537875

$array_reincidencias = array(98,99,100,101,161,162);

# Pesquisa pelo AutoComplete AJAX
$q = $_GET["q"];
if (isset($_GET["q"])) {

	$tipo_busca = $_GET["busca"];

	if (strlen($q) > 2) {
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";
		$sql .= ($tipo_busca == "codigo") ? " AND tbl_posto_fabrica.codigo_posto = '$q' " : " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";

		$res = pg_query($con,$sql);
		if (pg_num_rows ($res) > 0) {
			$resultados = pg_fetch_all($res);
			foreach ($resultados as $resultado){
				echo $resultado['cnpj']."|".$resultado['nome']."|".$resultado['codigo_posto'];
				echo "\n";
			}
		}
	}
	exit;
}

if(isset($_POST['valor_atualiza_et']) && $_POST['valor_atualiza_et'] == "ok"){

	$os = $_POST['os'];
	$valor = $_POST['valor'];

	$sql_mo = "SELECT mao_de_obra_adicional FROM tbl_os_extra WHERE os = {$os}";
    $res_mo = pg_query($con, $sql_mo);

    $pct = pg_fetch_result($res_mo, 0, 'mao_de_obra_adicional');
    $valor_entrega_tecnica =  ($valor / 100) * $pct;

    $valor_entrega_tecnica = number_format($valor_entrega_tecnica, 2);

    $sql_valor_et = "UPDATE tbl_os SET valores_adicionais = $valor WHERE os = {$os}";
    $res_valor_et = pg_query($con, $sql_valor_et);

    $sql_valor_et = "UPDATE tbl_os_extra SET valor_total_deslocamento = $valor_entrega_tecnica WHERE os = {$os}";
    $res_valor_et = pg_query($con, $sql_valor_et);

    $valor_entrega_tecnica = str_replace(".", ",", $valor_entrega_tecnica);
    $valor 				   = str_replace(".", ",", $valor);

    echo "ok|".$valor_entrega_tecnica."|".$pct." %|".$valor;
    exit;

}

if (strlen($os) > 0 AND $ver == 'endereco') {
	$sql = "SELECT  PO.nome                  ,
					PF.contato_endereco      ,
					PF.contato_numero        ,
					PF.contato_complemento   ,
					PF.contato_bairro        ,
					PF.contato_cidade        ,
					PF.contato_estado        ,
					PF.contato_cep           ,
					OS.consumidor_nome       ,
					OS.consumidor_endereco   ,
					OS.consumidor_numero     ,
					OS.consumidor_complemento,
					OS.consumidor_bairro     ,
					OS.consumidor_cidade     ,
					OS.consumidor_estado     ,
					OS.consumidor_cep        ,
					OS.os                    ,
					OS.sua_os                ,
					OS.qtde_km
			FROM tbl_os OS
				JOIN tbl_posto         PO ON PO.posto = OS.posto
				JOIN tbl_posto_fabrica PF ON PF.posto = OS.posto AND PF.fabrica = {$login_fabrica}
			WHERE OS.os      = {$os}
			AND   OS.fabrica = {$login_fabrica}";

	$dados = pg_fetch_assoc(pg_query($con, $sql));
	extract($dados);
	$sua_os = (strlen($sua_os) == 0) ? $os : $sua_os;
?>
<!DOCTYPE html>
<html>
	<head>
		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="css/tc_css.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="css/tooltips.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />
		<link href="plugins/leaflet/leaflet.css" rel="stylesheet" type="text/css" />
		<style type="text/css">
            #Maps {
                width: 100%;
                height: 500px;
                border: 1px black solid;
                position: relative;
                margin-bottom: 20px;
                float: left;
                padding: 1px;
            }
            .input-km{
				width: 105px;
				text-align: center;
            }
            .img-selecionada{
                border: solid 1px #d90000 !important;
                padding: 11px;
            }
		</style>
	</head>
	<body>
		<div class="container">
			<div class="row-fluid"><div class="alert alert-info"><h4><strong>Ordem de serviço: <?=$sua_os; ?></strong></h4></div></div>
			<div class="row-fluid">
				<div class="span12">
					<table id="table_contato" class='table table-striped table-bordered table-hover table-large'>
						<thead>
		                    <tr class='titulo_tabela'>
		                        <th colspan="9" >Posto: <?=$nome;?></th>
		                    </tr>
		                    <tr class='titulo_coluna' >
								<th>Endereço</th>
								<th>Bairro</th>
								<th>Cidade</th>
								<th>Estado</th>
								<th>CEP</th>
		                    </tr>
		                </thead>
		                <tbody>
		                    <tr rel='dados_posto'>
		                        <td class='tac'><?="{$contato_endereco}, {$contato_numero}"; ?></td>
		                        <td class='tac'><?=$contato_bairro;?></td>
		                        <td class='tal'><?=$contato_cidade;?></td>
		                        <td class='tac'><?=$contato_estado;?></td>
		                        <td class='tal'><?=$contato_cep;?></td>
		                    </tr>
		                </tbody>
					</table>
				</div>
			</div>
			<div class="row-fluid">
				<div class="span12">
					<table id="table_consumidor" class='table table-striped table-bordered table-hover table-large'>
						<thead>
		                    <tr class='titulo_tabela'>
		                        <th colspan="9" >Consumidor: <?=$consumidor_nome;?></th>
		                    </tr>
		                    <tr class='titulo_coluna' >
								<th>Endereço</th>
								<th>Bairro</th>
								<th>Cidade</th>
								<th>Estado</th>
								<th>CEP</th>
		                    </tr>
		                </thead>
		                <tbody>
		                    <tr rel='dados_consumidor'>
		                        <td class='tac'><?="{$consumidor_endereco}, {$consumidor_numero}";?></td>
		                        <td class='tac'><?=$consumidor_bairro;?></td>
		                        <td class='tal'><?=$consumidor_cidade;?></td>
		                        <td class='tac'><?=$consumidor_estado;?></td>
		                        <td class='tal'><?=$consumidor_cep;?></td>
		                    </tr>
		                </tbody>
					</table>
				</div>
			</div>
			<div class="row-fluid">
				<div class="span12 alert alert-warning">
					<span><strong>PARA REDEFINIR A POSIÇÃO DO CLIENTE OU DO POSTO AUTORIZADO NO MAPA, EM CASO DE ERRO NA SUA LOCALIZAÇÃO, SELECIONE UMA DAS LEGENDAS LOCALIZADAS A CIMA DO MAPA E EM SEGUIDA CLIQUE NO LOCAL DESEJADO PARA REDEFINIR SUA LOCALIZAÇÃO. APÓS A ALTERAÇÃO CLIQUE NO BOTÃO "RECALCULAR ROTA" PARA UMA NOVA COMPARAÇÃO</strong></span>
				</div>
			</div>
			<div class="row-fluid">
				<div class="span12" style="text-align: right;">
					<span style="cursor: pointer" id="icon-blue"><img style="margin-bottom: 6px;" src="imagens/Google_Maps_Marker_Blue.png" alt="Icone Cliente" /><strong><?=$consumidor_nome;?></strong></span>
					<span id="icon-red" style="cursor: pointer"><img style="margin-bottom: 6px;" src="imagens/Google_Maps_Marker_Red.gif" alt="Icone Postos" /><strong><?=$nome;?></strong></span>
					<button class="btn btn-primary" id="btn-recalcula-rota" style="margin-left: 18px; margin-bottom: 6px;">Recalcular rota</button>
					<div id='Maps'></div>
				</div>
			</div>
			<div class="row-fluid">
				<div class="span1"></div>
				<div class="span2">
					<div class='control-group'>
					    <label class='control-label' for=''><strong>Ida</strong></label>
					    <div class='controls controls-row'>
							<div class="input-append">
						        <input class="input-km" type='text' name='km_ida' id='km_ida' value="" disabled>
						        <span class="add-on" style="background-color: #596d9b; color: white;">+</span>
					        </div>
					    </div>
					</div>
				</div>
				<div class="span2">
					<div class='control-group'>
					    <label class='control-label' for=''><strong>Volta</strong></label>
					    <div class='controls controls-row'>
							<div class="input-append">
								<input class="input-km" type='text' name='km_volta' id='km_volta' value="" disabled>
								<span class="add-on" style="background-color: #596d9b; color: white;">=</span>
					        </div>
					    </div>
					</div>
				</div>
				<div class="span2">
					<div class='control-group'>
					    <label class='control-label' for=''><strong>Total</strong></label>
					    <div class='controls controls-row'>
					        <input class="input-km" type='text' name='total_km' id='total_km' value="" disabled>
					    </div>
					</div>
				</div>
				<div class="span2">
					<div class='control-group error'>
					    <label class='control-label' for='km_informado'><strong>Informado</strong></label>
						<div class='controls controls-row'>
					        <input class="input-km input-append" type='text' name='km_informado' id='km_informado' value="<?=$qtde_km?> km" disabled>
					    </div>
					</div>
				</div>
				<div class="span2">
					<div class='control-group'>
					    <label class='control-label' for='diferenca'><strong>Diferença</strong></label>
					    <div class='controls controls-row'>
							<input class="input-km input-append" type='text' name='diferenca' id='diferenca' value="" disabled>
						</div>
					</div>
				</div>
			</div>
		</div>
	</body>
	<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
	<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
	<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
	<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
	<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
	<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
	<script src="bootstrap/js/bootstrap.js"></script>
    <script src="plugins/leaflet/leaflet.js"></script>
    <script src="plugins/leaflet/map.js"></script>
    <script src="plugins/mapbox/geocoder.js"></script>
    <script src="plugins/mapbox/polyline.js"></script>
    <script type="text/javascript">
		var Map, Markers, Router, Geocoder, consumidor_lat, consumidor_lon, contato_lat, contato_lon;
		$(function(){
            Map      = new Map("Maps");
            Markers  = new Markers(Map);
            Router   = new Router(Map);
            Geocoder = new Geocoder();

            Map.load();
			Map.map.on('click', function (elem) {
                if (!$('#icon-blue').hasClass('img-selecionada') && !$('#icon-red').hasClass('img-selecionada')) { return false; }

                Markers.remove();
                Markers.clear();
                if ($('#icon-blue').hasClass('img-selecionada')) {
					consumidor_lat = elem.latlng.lat.toFixed(7);
					consumidor_lon = elem.latlng.lng.toFixed(7);
                }else{
					contato_lat = elem.latlng.lat.toFixed(7);
					contato_lon = elem.latlng.lng.toFixed(7);
                }
				Markers.add(consumidor_lat, consumidor_lon, "blue", "<?=$consumidor_nome;?>");
				Markers.add(contato_lat, contato_lon, "red", "<?=$nome;?>");
				Markers.render();
				Router.remove();
				Router.clear();
			});

            $('#icon-blue').click(function(){
                if (!$('#icon-blue').hasClass('img-selecionada')) {
	                $('#icon-red').removeClass('img-selecionada');
	                $('#icon-blue').addClass('img-selecionada');
                }
                Map.flyTo(consumidor_lat, consumidor_lon, 18);
            });
            $('#icon-red').click(function(){
                if (!$('#icon-red').hasClass('img-selecionada')) {
	                $('#icon-blue').removeClass('img-selecionada');
	                $('#icon-red').addClass('img-selecionada');
                }
                Map.flyTo(contato_lat, contato_lon, 18);
            });

            $('#btn-recalcula-rota').on('click', function(){
				$('#btn-recalcula-rota').text('Recalculando...').attr('disabled', true);
				calcula_rota(function(){
					$('#btn-recalcula-rota').text('Recalcular rota').attr('disabled', false);
				});
            });

            try {
                Geocoder.setEndereco({
                    endereco: '<?=$consumidor_endereco?>',
                    numero: '<?=$consumidor_numero?>',
                    bairro: '<?=$consumidor_bairro?>',
                    cidade: '<?=$consumidor_cidade?>',
                    estado: '<?=$consumidor_estado?>',
                    cep: '<?=$consumidor_cep?>',
                    pais: 'BR'
                });

                request = Geocoder.getLatLon();

                request.then(function(resposta) {
                    consumidor_lat  = resposta.latitude;
                    consumidor_lon  = resposta.longitude;

	                Geocoder.setEndereco({
	                    endereco: '<?=$contato_endereco?>',
	                    numero: '<?=$contato_numero?>',
	                    bairro: '<?=$contato_bairro?>',
	                    cidade: '<?=$contato_cidade?>',
	                    estado: '<?=$contato_estado?>',
	                    cep: '<?=$contato_cep?>',
	                    pais: 'BR'
	                });
		            request = Geocoder.getLatLon();
		            request.then(function(resposta) {
						contato_lat  = resposta.latitude;
						contato_lon  = resposta.longitude;

						Markers.add(consumidor_lat, consumidor_lon, "blue", "<?=$consumidor_nome;?>");
						Markers.add(contato_lat, contato_lon, "red", "<?=$nome;?>");
						Markers.render();
						Markers.focus();
						calcula_rota();
                    },
                    function(erro) {
                        alert(erro);
                    });
                },
                function(erro) {
                    alert(erro);
                });
            } catch(e) {
                alert(e.message);
            }
		});

		function calcula_rota(callback = null){
            $.ajax({
                url: '../controllers/TcMaps.php',
                method: "GET",
                data: { 'ajax': 'route', 'ida_volta': 'sim', 'destino': consumidor_lat+','+consumidor_lon, 'origem': contato_lat+','+contato_lon },
                timeout: 9000
            }).fail(function(){
                alert('Não foi possível calcular a rota solicitada, tente novamente mais tarde');
            }).done(function(data){
                data = JSON.parse(data);
                $('#km_ida').val(data.km_ida+' km');
                $('#km_volta').val(data.km_volta+' km');
                $('#total_km').val(data.total_km+' km');

                var km_informado = $('#km_informado').val().split(' ');
                var diferenca = km_informado[0] - data.total_km
                $('#diferenca').val(diferenca.toFixed(2)+' km');
                Router.add(Polyline.decode(data.rota.routes[0].geometry));
                Router.render();
                callback();
            });
		}
    </script>
</html>
<?php
	exit;
}

$os   = $_GET["os"];
$tipo = $_GET["tipo"];

//HD 237498: Coloquei para pegar a ação por GET, para que possa filtrar uma OS por get
//			 Desta forma filtra por GET e tem rotina que ja manda para que fique filtrado (extrato_consulta_os.php)
//			 Não retirar este filtro, pois é essencial para o funcionamento
if ($_POST["btn_acao"]) {
	$btn_acao    = trim($_POST["btn_acao"]);
}
else {
	$btn_acao = $_GET["btn_acao"];
}

if(strlen($btn_acao)>0 AND strlen($select_acao)>0){
	$qtde_os     = trim($_POST["qtde_os"]);
	$observacao  = trim($_POST["observacao"]);

	if($select_acao == "101" AND strlen($observacao) == 0){
		$msg_erro .= "Informe o motivo da reprovação da OS.<br>";
	}

	$observacao = (strlen($observacao) > 0) ? " Observação: $observacao " : "";

	if (strlen($qtde_os)==0){
		$qtde_os = 0;
	}

	for ($x=0;$x<$qtde_os;$x++){

		$xxos         = trim($_POST["check_".$x]);
		$xxqtde_km_os = trim($_POST["qtde_km_os_".$x]);
		$xxqtde_km    = trim($_POST["qtde_km_".$x]);

		if($login_fabrica == 140){
			$valor_produto = $_POST["valor_produto_".$x];
			$valor_entrega = $_POST["valor_calculado_".$x];

			$valor_produto = str_replace(".","",$valor_produto);
			$valor_produto = str_replace(",",".",$valor_produto);
		}

		$xxqtde_km    = str_replace (".","",$xxqtde_km);
		$xxqtde_km    = str_replace (",",".",$xxqtde_km);

		#$xxqtde_km_os = str_replace (".","",$xxqtde_km_os);
		#$xxqtde_km_os = str_replace (",",".",$xxqtde_km_os);

		#$xxqtde_km    = number_format($xxqtde_km ,3,'.',',');
		#$xxqtde_km_os = number_format($xxqtde_km_os,3,'.',',');

		if($select_acao == "99" AND ($xxqtde_km_os <> $xxqtde_km) AND $observacao == "Observação:" ){
			$msg_erro .= "Informe o motivo da alteração do km da OS: $xxos.";
		}else{
			// ALTERARA O STATUS DE APROVADA, PARA APROVADA COM ALTERAÇÃO
			if($select_acao == "99" AND ($xxqtde_km_os <> $xxqtde_km) ){
				if (empty($xxqtde_km_os) && $xxqtde_km == 0) {
					// BUG quando KM da OS é nulo, e a qtde de km aprovada é 0, o status estava indo para 100.
				} else {
					$select_acao = "100" ;
				}
			}
		}

		// HD 666788 - a aprovação ou reprovação das OS dependerá do cadastro de admin por funcionalidade.
		// Permissão para aprovação/reprovação apenas para usuários cadastrados para a funcionalidade: 1 - Aprovar KM acima de 120Km
		if ( ($xxqtde_km >= 120 || $xxqtde_km_os >= 120) && $login_fabrica == 50 && strlen($xxos)>0 ){

			$sql = "select admin
					from   tbl_funcionalidade_admin
					where  funcionalidade = 1
					and    admin          = $login_admin
					and    fabrica        = $login_fabrica";

			$res = pg_query($con,$sql);

			if (pg_num_rows($res) == 0){
				$msg_erro .= "Você não tem autorização para Aprovar ou Reprovar a OS: $xxos";
				$desabilita_check = true;
			}

		}

		if (strlen($xxos) > 0 AND strlen($msg_erro) == 0) {
			$res_os = pg_query($con,"BEGIN TRANSACTION");

			$sql = "SELECT contato_email,tbl_os.sua_os, tbl_os.posto
					FROM tbl_posto_fabrica
					JOIN tbl_os          ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					WHERE tbl_os.os      = $xxos
					AND   tbl_os.fabrica = $login_fabrica";
			$res_x = pg_query($con,$sql);
			$posto_email = pg_fetch_result($res_x,0,contato_email);
			$sua_os      = pg_fetch_result($res_x,0,sua_os);
			$posto       = pg_fetch_result($res_x,0,posto);

			$sql = "SELECT nome_completo FROM tbl_admin WHERE admin = $login_admin";
			$res_x = pg_query($con,$sql);
			$promotor = pg_fetch_result($res_x,0,nome_completo);

			$sql = "SELECT status_os
					FROM tbl_os_status
					WHERE status_os IN (" . implode(',', $array_reincidencias) . ")
					AND tbl_os_status.fabrica_status = $login_fabrica
					AND os = $xxos
					ORDER BY data DESC
					LIMIT 1";

			$res_os = pg_query($con,$sql);

			if (pg_num_rows($res_os) > 0) {

				$status_da_os = trim(pg_fetch_result($res_os, 0, status_os));

				if ($status_da_os == 98 ||
                    ($login_fabrica == 52 && ($status_da_os == 99 || $status_da_os == 100)) ||
                    ($login_fabrica == 74 && ($status_da_os == 161 || $status_da_os == 162))) {

					if(($status_da_os == 99 OR $status_da_os == 100) AND ($xxqtde_km_os <> $xxqtde_km) ){
						$observacao = trim($_POST["observacao"])." - O KM foi alterado de $xxqtde_km_os para $xxqtde_km " ;
					}

					if ($login_fabrica == 90 && ($xxqtde_km_os <> $xxqtde_km)) {
						$observacao = trim($_POST["observacao"])." - O KM foi alterado de $xxqtde_km_os para $xxqtde_km " ;	
					}
					
					if ($select_acao == "99") {

						if ($login_fabrica == 50) {
							$observacao = trim($_POST["observacao"]). " Auditoria aprovada com km ({$xxqtde_km})! ";
						}

						$sql = "INSERT INTO tbl_os_status (
                                                os,
                                                status_os,
                                                data,
                                                observacao,
                                                admin
                                            ) VALUES (
                                                $xxos,
                                                99,
                                                current_timestamp,
                                                '$observacao',
                                                $login_admin
                                            )
                        ";

						$res       = pg_query($con, $sql);
						$msg_erro .= pg_errormessage($con);

						if ($login_fabrica == 30 and empty($msg_erro)) {
							$envia_email_esmaltec = 1;
							$esmaltec_acao = 'aprovada';
						}

						if($login_fabrica == 52){
							$sql       = "UPDATE tbl_os SET qtde_km = $xxqtde_km, qtde_km_calculada = $xxqtde_km * tbl_os_extra.valor_por_km FROM tbl_os_extra WHERE tbl_os_extra.os = tbl_os.os AND tbl_os.os = $xxos AND tbl_os.fabrica = $login_fabrica";
						}else{
							$sql       = "UPDATE tbl_os SET qtde_km = $xxqtde_km WHERE os = $xxos AND fabrica = $login_fabrica";
						}
						$res       = pg_query($con, $sql);

// 					i	f ($login_fabrica == 52 and empty($msg_erro)) {
// 							$sql = "UPDATE tbl_os_extra SET obs_adicionais = 'A OS: $xxos foi alterado o KM de $xxqtde_km_os para $xxqtde_km' WHERE os = $xxos";
// 							$res       = pg_query($con, $sql);
// 							$msg_erro .= pg_errormessage($con);
// 						}

						if ($login_fabrica == 50 and empty($msg_erro)) {
							$sql       = "SELECT fn_calcula_os_colormaq($xxos,$login_fabrica)";
							$res       = pg_query($con, $sql);
							$msg_erro .= pg_errormessage($con);
						}

					}

					//ALTERADO O KM
					if ($select_acao == "100") {

						$sql = "INSERT INTO tbl_os_status(os,status_os,data,observacao,admin) VALUES ($xxos, 100, current_timestamp, '$observacao', $login_admin)";

						$res       = pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);

						if ($login_fabrica <> 3) {// A Britânia quer um historico de Km - Gustavo 26/6/2008

							if($login_fabrica == 52){
								$sql       = "UPDATE tbl_os SET qtde_km = $xxqtde_km, qtde_km_calculada = $xxqtde_km * tbl_os_extra.valor_por_km FROM tbl_os_extra WHERE tbl_os_extra.os = tbl_os.os AND tbl_os.os = $xxos AND tbl_os.fabrica = $login_fabrica";
							}else{
								$sql       = "UPDATE tbl_os SET qtde_km = $xxqtde_km WHERE os = $xxos AND fabrica = $login_fabrica";
							}
							$res       = pg_query($con, $sql);
							$msg_erro .= pg_errormessage($con);

							if ($login_fabrica == 50) {
								$sql       = "SELECT fn_calcula_os_colormaq($xxos,$login_fabrica)";
								$res       = pg_query($con, $sql);
								$msg_erro .= pg_errormessage($con);
							}

							if ($login_fabrica == 30) {
								$sql       = "SELECT fn_calcula_os_esmaltec($xxos,$login_fabrica)";
								$res       = pg_query($con, $sql);
								$msg_erro .= pg_errormessage($con);

								if (empty($msg_erro)) {
									$envia_email_esmaltec = 1;
									$esmaltec_acao = 'aprovada';
								}
							}

// 							if ($login_fabrica == 52 and empty($msg_erro)) {
// 								$sql = "UPDATE tbl_os_extra SET obs_adicionais = 'A OS: $xxos foi alterado o KM de $xxqtde_km_os para $xxqtde_km',qtde_km = $xxqtde_km WHERE os = $xxos";
// 								$res       = pg_query($con, $sql);
// 								$msg_erro .= pg_errormessage($con);
// 							}

							// HD 149799
							$sql       = "SELECT fn_calcula_extrato($login_fabrica, extrato) FROM tbl_os_extra JOIN tbl_extrato using(extrato) WHERE os = $xxos AND extrato IS NOT NULL and fabrica = $login_fabrica and liberado isnull";
							$res       = @pg_query($con,$sql);
							$msg_erro .= pg_errormessage($con);

						}

					}
					
					if ($login_fabrica == 52 && $select_acao != '101' && $xxqtde_km != $xxqtde_km_os) {
						$interacaoKm = "
							INSERT INTO tbl_os_interacao
							(os, admin, comentario, interno, fabrica, posto)
							SELECT 
								os, 
								{$login_admin},
								'KM alterado de {$xxqtde_km_os} para {$xxqtde_km}',
								TRUE,
								{$login_fabrica},
								posto
							FROM tbl_os
							WHERE fabrica = {$login_fabrica}
							AND os = {$xxos}
						";
						$resInteracaoKm = pg_query($con, $interacaoKm);
						$msg_erro .= pg_errormessage($con);
					}

					//RECUSADA
					if ($select_acao == "101") {

						$sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao,admin) VALUES ($xxos,101,current_timestamp,'$observacao',$login_admin)";

						$res       = pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);

						if ($login_fabrica <> 3) {// A Britânia quer um historico de Km - Gustavo 26/6/2008

							$sql       = "UPDATE tbl_os SET qtde_km = 0, qtde_km_calculada = 0 WHERE os = $xxos AND fabrica = $login_fabrica;
										UPDATE tbl_os_extra SET qtde_km = 0, valor_por_km = 0 WHERE os = $xxos ; 		";
							$res       = pg_query($con,$sql);
							$msg_erro .= pg_errormessage($con);

							# HD 149799
							$sql       = " SELECT fn_calcula_extrato($login_fabrica, extrato) FROM tbl_os_extra JOIN tbl_extrato using(extrato) WHERE os = $xxos AND extrato IS NOT NULL and fabrica = $login_fabrica and liberado isnull";
							$res       = @pg_query($con,$sql);
							$msg_erro .= pg_errormessage($con);

						}

						if($login_fabrica >= 131 && !in_array($login_fabrica,[140,145])){

							$sql = "SELECT fn_os_excluida($xxos,$login_fabrica,$login_admin);";
							$res = pg_exec($con,$sql);
							$msg_erro .= pg_errormessage($con);

							$sql_posto = "SELECT posto FROM tbl_os WHERE os = {$xxos}";
							$res_posto = pg_query($con, $sql_posto);
							$posto = pg_fetch_result($res_posto, 0, 'posto');

							$sql = "INSERT INTO tbl_comunicado (
												descricao              ,
												mensagem               ,
												tipo                   ,
												fabrica                ,
												obrigatorio_os_produto ,
												obrigatorio_site       ,
												posto                  ,
												ativo
											) VALUES (
												'OS {$xxos} Reprovada - Intervenção de Deslocamento de KM',
												'$observacao',
												'Deslocamento de KM',
												$login_fabrica,
												'f' ,
												't',
												$posto,
												't'
											);";

							$res       = pg_query($con,$sql);
							$msg_erro .= pg_errormessage($con);

							$sql_motivo = "UPDATE tbl_os_excluida SET motivo_exclusao = '{$observacao}' WHERE os = {$xxos} AND fabrica = {$login_fabrica}";
    						$res_motivo = pg_query($con, $sql_motivo);

						}

						if ($login_fabrica == 30 and empty($msg_erro)) {
							$envia_email_esmaltec = 1;
							$esmaltec_acao = 'reprovada';
						}



					}

					if ($login_fabrica == 94) { // HD 415106

						$situacao_os = ($select_acao == '101') ? 'Reprovada' : 'Aprovada';
						$email_destino = $posto_email;
						$assunto       = "Troca $situacao_os";

						$corpo.="<br>A OS nº $sua_os foi $situacao_os.<br /><hr />";
						//$corpo.="<br>Promotor que reprovou: $promotor\n\n";
						//$corpo.="<br>_______________________________________________\n";
						$corpo.="<br>OBS: MENSAGEM AUTOMÁTICA. NÃO RESPONDA ESTE EMAIL.";

						$headers  = 'MIME-Version: 1.0' . "\r\n";
						$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
						$headers .= 'To: <'.$email_destino.'>' . "\r\n";
						$headers .= 'From: Suporte <helpdesk@telecontrol.com.br>' . "\r\n";

						@mail($email_destino, utf8_encode($assunto), utf8_encode($corpo), $headers);

					}

				}

			}

			if($login_fabrica == 140 AND strlen($msg_erro) == 0 AND strlen($valor_entrega) > 0 AND $select_acao != '101'){

				$sql_valor_et = "UPDATE tbl_os_extra SET valor_total_deslocamento = $valor_entrega WHERE os = {$xxos}";
				$res_et = pg_query($con,$sql_valor_et);
				$msg_erro = pg_last_error();

				$sql_valor_et = "UPDATE tbl_os SET valores_adicionais = $valor_produto WHERE os = {$xxos}";
				$res_valor = pg_query($con,$sql_valor_et);
				$msg_erro = pg_last_error();

			}
			//HD 237498: Para algumas fábricas que trabalham com auditoria de KM a OS irá entrar no extrato mesmo em auditoria.
			// No entanto não é possí­vel liberar tais extratos antes que se audite os KM. Ao auditar o KM de uma OS que esteja
			// em um extrato o mesmo deve ser recalculado.
			//Na rotina de extrato existe um redirecionamento para este programa para que o usuário audite o KM da OS que ainda
			// não foi auditado. Antes de modificar este código, verificar as rotinas

			$sql = "SELECT extrato FROM tbl_os_extra JOIN tbl_extrato using(extrato) WHERE os = $xxos and fabrica=$login_fabrica and liberado isnull";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {

				$extrato_recalcular = pg_result($res, 0, 'extrato');

				if (strlen($extrato_recalcular) > 0) { #HD 249679
					$sql = "SELECT fn_calcula_extrato($login_fabrica, $extrato_recalcular)";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);
				}

			}//HD 237498: FIM

			if (strlen($msg_erro) == 0) {
				if (isset($envia_email_esmaltec) and $envia_email_esmaltec == 1) {
					$sqlPostoeMail = "SELECT tbl_posto_fabrica.contato_email, tbl_os.sua_os
										FROM tbl_posto_fabrica
										JOIN tbl_os ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
										WHERE os = $xxos";
					$resPostoeMail = pg_query($con, $sqlPostoeMail);

					if (pg_num_rows($resPostoeMail) == 0) {
						$sqlPostoeMail2 = "SELECT tbl_posto.email, tbl_os.sua_os FROM tbl_posto JOIN tbl_os USING (posto) where os = $xxos";
						$resPostoeMail2 = pg_query($con, $sqlPostoeMail2);

						if (pg_num_rows() == 1) {
							$posto_email  = pg_fetch_result($resPostoeMail2, 0, 'email');
							$sua_os_email = pg_fetch_result($resPostoeMail2, 0, 'sua_os');
						}
					} else {
						$posto_email  = pg_fetch_result($resPostoeMail, 0, 'contato_email');
						$sua_os_email = pg_fetch_result($resPostoeMail, 0, 'sua_os');
					}

					if (!empty($posto_email)) {
						$sqlAdminNome = "select nome_completo from tbl_admin where admin = $login_admin";
						$qryAdminNome = pg_query($con, $sqlAdminNome);
						$nome_admin = pg_fetch_result($qryAdminNome, 0, 'nome_completo');

						$assunto = 'O.S. ' . $sua_os_email  . ' ' . $esmaltec_acao . ' da Auditoria de Deslocamento de KM';

						$msg = 'A OS ' . $sua_os_email . ' foi ' . $esmaltec_acao . ' da Auditoria de Deslocamento de KM por ' . $nome_admin . ' da Esmaltec.';
						$msg .= '<br/><br/>';
						$msg .= str_replace("Observação", "Motivo", $observacao);

						$headers  = 'MIME-Version: 1.0' . "\r\n";
						$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
						$headers .= 'From: Esmaltec <auditoria.sae@esmaltec.com.br>' . "\r\n";

						mail($posto_email, utf8_encode($assunto), utf8_encode($msg), $headers);
					}
				}

				if (in_array($login_fabrica, array(141,144))) {
				    $sqlStatus = "SELECT fn_os_status_checkpoint_os({$xxos}) AS status;";
				    $resStatus = pg_query($con, $sqlStatus);

				    $statusCheckpoint = pg_fetch_result($resStatus, 0, "status");

				    $updateStatus = "UPDATE tbl_os SET status_checkpoint = {$statusCheckpoint} WHERE fabrica = {$login_fabrica} AND os = {$xxos}";
				    $resStatus = pg_query($con, $updateStatus);

				    if (strlen(pg_last_error()) > 0) {
				      $msg_erro = "Erro ao atualizar status da Ordem de Serviço $xxos";
				    }
				}

				if(strlen($msg_erro)==0 && $select_acao == 101 && $login_fabrica >= 131){
					$sql = 'SELECT fn_os_excluida ($1,$2,$3);';
					if(!pg_query_params($con,$sql,array($xxos,$login_fabrica,$login_admin)))
						$msg_erro .= pg_last_error($con);

					if(empty($msg_erro)){

						$sql = "INSERT INTO tbl_comunicado( mensagem ,
							descricao ,
							tipo ,
							fabrica ,
							obrigatorio_site ,
							posto ,
							pais ,
							ativo
							) VALUES ( 	'$observacao' ,
									'A O.S $xxos foi cancelada pela fabrica' ,
									'Comunicado' ,
									$login_fabrica ,
									't' ,
									$posto ,
									'BR' ,
									't'
							);";

						$res = pg_query($con,$sql);
						$msg_erro = pg_last_error($con);

						if(empty($msg_erro)){

							$email_destino = $posto_email;
							$assunto       = "Ordem de Serviço Reprovada";

							$corpo.="<br>A OS nº $sua_os foi reprovada.<br />";
							$corpo.="<br>$observacao<br /><hr />";

							$corpo.="<br>OBS: MENSAGEM AUTOMÁTICA. NÃO RESPONDA ESTE EMAIL.";

							$headers  = 'MIME-Version: 1.0' . "\r\n";
							$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
							$headers .= 'To: <'.$email_destino.'>' . "\r\n";
							$headers .= 'From: Suporte <helpdesk@telecontrol.com.br>' . "\r\n";

							@mail($email_destino, utf8_encode($assunto), utf8_encode($corpo), $headers);

						}

					}
				}

				$res = pg_query($con,"COMMIT TRANSACTION");
				$msg = "ok";

			} else {
				$res = pg_query($con,"ROLLBACK TRANSACTION");
				$msg = "";
			}

		}

	}

}

# HD 35771 - Francisco Ambrozio
# Foi movido de CALLCENTER para AUDITORIA
$layout_menu = "auditoria";
$title = "APROVAÇÃO DE DESLOCAMENTO DE KM.";
include "cabecalho_new.php"; 

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable",
	"price_format"
);

include("plugin_loader.php");
?>


<script language="JavaScript">
function ver(os) {
	var url = "<? echo $PHP_SELF ?>?ver=endereco&os="+os;
	janela_aut = window.open(url, "_blank", "toolbar=no, location=no, status=no, scrollbars=yes, directories=no, width=550, height=300, top=18, left=0");
	janela_aut.focus();
}

function valorProduto(id, os){

	var valor = $('#valor_produto_'+id).val();
	valor = valor.replace(",", ".");
	valor = parseFloat(valor).toFixed(2);

	var valor_percentual = $('#valor_percentual_'+id).val();
	valor_percentual = valor_percentual.replace(",",".");
	valor_percentual = parseFloat(valor_percentual).toFixed(2);

	var valor_calculado = parseFloat(valor * valor_percentual / 100);
	$('#valor_calculado_'+id).val(valor_calculado.toFixed(2));
	$('#valor_et_'+id).text(valor_calculado);
	return;

	var valor_comp = $('#valor_produto_'+id).attr('rel');
	valor_comp = valor_comp.replace(",", ".");
	valor_comp = parseFloat(valor_comp).toFixed(2);

	if(valor == valor_comp){
		return;
	}

	$.ajax({
		url : "<?php echo $_SERVER['PHP_SELF']; ?>",
		type: "POST",
		data: {
			valor_atualiza_et : "ok",
			os 		: os,
			valor 	: valor
		},
		complete: function(data){

			var arr = new Array();
			data = data.responseText;
			arr = data.split("|");

			if(arr[0] == "ok"){
				$('#valor_produto_'+id).val(arr[3]);
				$('#valor_produto_'+id).attr('rel', arr[3]);
				$('#valor_et_'+id).text(arr[1]);
				$('#valor_pct_'+id).text(arr[2]);
			}

		}
	});

}

</script>

<script language="JavaScript">
function date_onkeydown() {
  if (window.event.srcElement.readOnly) return;
  var key_code = window.event.keyCode;
  var oElement = window.event.srcElement;
  if (window.event.shiftKey && String.fromCharCode(key_code) == "T") {
        var d = new Date();
        oElement.value = String(d.getMonth() + 1).padL(2, "0") + "/" +
                         String(d.getDate()).padL(2, "0") + "/" +
                         d.getFullYear();
        window.event.returnValue = 0;
    }
    if (!window.event.shiftKey && !window.event.ctrlKey && !window.event.altKey) {
        if ((key_code > 47 && key_code < 58) ||
          (key_code > 95 && key_code < 106)) {
            if (key_code > 95) key_code -= (95-47);
            oElement.value =
                oElement.value.replace(/[dma]/, String.fromCharCode(key_code));
        }
        if (key_code == 8) {
            if (!oElement.value.match(/^[dma0-9]{2}\/[dma0-9]{2}\/[dma0-9]{4}$/))
                oElement.value = "dd/mm/aaaa";
            oElement.value = oElement.value.replace(/([dma\/]*)[0-9]([dma\/]*)$/,
                function ($0, $1, $2) {
                    var idx = oElement.value.search(/([dma\/]*)[0-9]([dma\/]*)$/);
                    if (idx >= 5) {
                        return $1 + "a" + $2;
                    } else if (idx >= 2) {
                        return $1 + "m" + $2;
                    } else {
                        return $1 + "d" + $2;
                    }
                } );
            window.event.returnValue = 0;
        }
    }
    if (key_code != 9) {
        event.returnValue = false;
    }
}

function btn_gravar(){
	if ($('select[name=select_acao]').val() == '') {
		alert('Seleciona a operação (aprovar/reprovar)');
	}else if($('#observacao').val() == ''){
		alert('Informe o motivo da operação selecionada');
	}else{
		var retorno = false;
		$('#relatorio_os_auditoria tr td input[type=checkbox]').each(function(){
			if ($(this).is(':checked')) {
				retorno = true;
			}
		});
		if (retorno) {
			$('form[name=frm_pesquisa2]').submit();
		}else{
			alert('Selecione alguma OS para aprovar/reprovar');
		}
	}
}

var ok = false;
var cont=0;
function checkaTodos() {
	$('#relatorio_os_auditoria tr td input[type=checkbox]').prop('checked', $('input[name=seleciona_todos]').is(':checked'));
	/*f = document.frm_pesquisa2;
	if (!ok) {
		for (i=0; i<f.length; i++){
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = true;
				ok=true;
				if (document.getElementById('linha_'+cont)) {
					document.getElementById('linha_'+cont).style.backgroundColor = "#F0F0FF";
				}
				cont++;
			}
		}
	}else{
		for (i=0; i<f.length; i++) {
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = false;
				ok=false;
				if (document.getElementById('linha_'+cont)) {
					document.getElementById('linha_'+cont).style.backgroundColor = "#FFFFFF";
				}
				cont++;
			}
		}
	}*/
}

function setCheck(theCheckbox,mudarcor,cor){
	if (document.getElementById(theCheckbox)) {
//		document.getElementById(theCheckbox).checked = (document.getElementById(theCheckbox).checked ? false : true);
	}
	if (document.getElementById(mudarcor)) {
		document.getElementById(mudarcor).style.backgroundColor  = (document.getElementById(theCheckbox).checked ? "#FFF8D9" : cor);
	}
}

</script>

<script language="javascript">
$(function() {

	$("input[name=aprova]").click(function(){
		if ($(this).val() == "aprovacao") {
			$(".data").hide();
		} else {
			$(".data").show();
		}
	});

	$.datepickerLoad(Array("data_final", "data_inicial"));
	$.autocompleteLoad(Array("posto"));

	Shadowbox.init();

	$("span[rel=lupa]").click(function () {
		$.lupa($(this));
	});

	$(".btn_interacao").click(function() {
		var os = $(this).attr("os");

		<?php if ($login_fabrica == 30) { ?>
				Shadowbox.open({
					content: "relatorio_interacao_os.php?interagir=true&os="+os,
					player: "iframe",
					width: 850,
					height: 600,
					title: "Ordem de Serviço "+os
				});
		<?php } else { ?>
			    Shadowbox.open({
		            content: "exibe_interacao_km_os.php?os="+os,
		            player: "iframe",
		            title:  "Interações da OS "+os,
		            width:  800,
		            height: 500
		        });
		<?php } ?>
	});

});

function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
	$("#descricao_posto").val(retorno.nome);
}

function abreInteracao(linha,os,tipo) {

	$.get(
		'ajax_grava_interacao.php',
		{
			linha:linha,
			os:os,
			tipo:tipo
		},
		function (resposta){
			resposta_array = resposta.split("|");
			resposta = resposta_array [0];
			linha = resposta_array [1];
			$('#interacao_'+linha).html(resposta);
			$('#comentario_'+linha).focus;

		}
	)

}

function abreObs(os,codigo_posto,sua_os){
	janela = window.open("obs_os_troca.php?os=" + os + "&codigo_posto=" + codigo_posto +"&sua_os=" + sua_os,"formularios",'resizable=1,scrollbars=yes,width=400,height=250,top=0,left=0');
	janela.focus();
}

$(function() {
	$('input.price').priceFormat({
		prefix: '',
		thousandsSeparator: '.',
		centsSeparator: ',',
		centsLimit: 2
	});
});

</script>
<?
include "javascript_pesquisas.php";
if($btn_acao == 'Pesquisar'){
	$data_inicial		= trim($_POST['data_inicial']);
	$data_final			= trim($_POST['data_final']);
	$aprova				= trim($_POST['aprova']);
	$regiao_comercial	= trim($_POST['regiao_comercial']);
	$posto_codigo		= trim($_POST["posto_codigo"]);
    $posto_estado       = $_POST['posto_estado'];
    $campos             = array();

    if ($login_fabrica == 24) {

    	$tipoData = $_POST['tipo_data'];
    }

	if(!isset($_POST['observacao'])){
		$pesquisa_posto = $_POST['posto_nome'];
	}else if($_POST['pesquisa_posto'] == ""){
		$posto_codigo = "";
		$pesquisa_posto = "";
	}

	if ($_POST["os"]) {
		$os = trim($_POST['os']);
	}
	else {
		$os = trim($_GET['os']);
	}

	if (strlen($os)>0){
		$Xos = " AND (tbl_os.sua_os = '$os' OR tbl_os.os = $os) ";
	}

	if ((in_array($aprova, array("aprovadas", "reprovadas")) && (empty($data_inicial) || empty($data_final))) && empty($os)) {
		$msg_erro .= "Preencha os campos obrigatórios";
        if (empty($os)) {
            $campos[] = 'os';
        }
        if (empty($data_inicial)) {
            $campos[] = 'data_inicial';
        }
        if (empty($data_final)) {
            $campos[] = 'data_final';
        }                
	}

	
	if (strlen($aprova) > 0) {

		$listaAprovacao = ['aprovacao'  => '98',
						   'aprovadas'  => '99, 100',
						   'reprovadas' => '101' ];

		$aprovacao = $listaAprovacao[$aprova];
	}

	/**
	 * HD 854585 - Filtros de pesquisa por tipo de auditoria.
	 * @author Brayan
	 */
	if (isset($_POST['tipo_auditoria'])) {

		$tipo_auditoria = (int) $_POST['tipo_auditoria'];


		/**
		 * Para filtros que possuem status diferenciado, coloquei no value do campo radio, o id do status.
		 */
		if ( $tipo_auditoria ) {

			/**
			 * Se for filtro por OS em aprovação, o status da OS deve ser o da auditoria,
			 * pois vai ser diferente do padrão, que é 98.
			 */
			if($aprova=="aprovacao") {

				$aprovacao = $tipo_auditoria;

				if ($login_fabrica == 74 and !empty($tipo_auditoria)) {
					$array_reincidencias = ($tipo_auditoria == 161) ? array(99,100,101,161) : (($tipo_auditoria == 162)?array(99,100,101,162):array(98,99,100,101));
				}

			}

			$cond_auditoria = " AND tbl_os_status.status_os = $tipo_auditoria ";

		} else {

			/**
			 * Aqui tive q fazer um workaround para pesquisar pela observação da auditoria,
			 * pois as auditorias de alteração manual e KM superior a 80 gravam o mesmo status_os (98).
			 */

			$tipo_auditoria = $_POST['tipo_auditoria'];

			if ($tipo_auditoria == 'superior_80') {

				$cond_auditoria = " AND tbl_os_status.observacao LIKE '%Quantidade de KM calculado superior%' ";

			} else if ($tipo_auditoria == 'alteracao_manual') {

				$cond_auditoria = " AND tbl_os_status.observacao LIKE '%Alteração manual%' ";

			}

		}

	} else if ($login_fabrica == 74) {

		$aprovacao .= ", 161, 162";

	}

	// if (strlen($data_inicial) > 0) {
	// 	$xdata_inicial = formata_data ($data_inicial);
	// 	$xdata_inicial = $xdata_inicial." 00:00:00";
	// }

	// if (strlen($data_final) > 0) {
	// 	$xdata_final = formata_data ($data_final);
	// 	$xdata_final = $xdata_final." 23:59:59";
	// }

	if(!empty($data_inicial) and !empty($data_final)) {
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);

		if(!checkdate($mi, $di, $yi)):
			$msg_erro .= "Data Inicial Inválida <br />";
		endif;

		if(!checkdate($mf, $df, $yf)):
			$msg_erro .= "Data Final Inválida <br />";
		endif;

		if (empty($msg_erro))
		{
			$xdata_inicial = "{$yi}-{$mi}-{$di}";
			$xdata_final   = "{$yf}-{$mf}-{$df}";

			if(strtotime($xdata_final) < strtotime($xdata_inicial)) {
				$msg_erro .= "Data Inválida <br />";
			}

			if (strtotime($xdata_inicial.'+6 month') < strtotime($xdata_final) ) {
				$msg_erro .= "O intervalo entre as datas não pode ser maior que 6 meses <br />";
			}
		}

		// $sqlX = "SELECT ('$xdata_final'::date - '$xdata_inicial'::date)";
		// $resX = @pg_query($con,$sqlX);
		// $msg_erro .= pg_errormessage($con);
		// if(strpos($msg_erro,"date/time field value out of range") !==false) {
		// 	$msg_erro .= "Data Inválida.";
		// }
		// if(strlen($msg_erro)==0){
		// 	if(pg_num_rows($resX) > 0){
		// 		$periodo = pg_fetch_result($resX,0,0);
		// 		if($periodo < 0) {
		// 			$msg_erro .= "Data Inválida.";
		// 		}elseif($periodo > 90){
		// 			$msg_erro .= "Período entre datas não pode ser maior que 90 dias";
		// 		}
		// 	}
		// }
	}

	if ($login_fabrica == 50 ) { //hd 71341 waldir
		$cond_excluidas = "AND tbl_os.excluida is not true ";
	}
	if(strlen($posto_codigo)>0){

		$sql = " SELECT posto
				FROM tbl_posto_fabrica
				WHERE fabrica = $login_fabrica
				AND   codigo_posto = '$posto_codigo' ";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
			$sql_posto .= " AND tbl_posto_fabrica.codigo_posto = '$posto_codigo' ";
		}else{
			$msg_erro .= "Código do posto $posto_codigo incorreto";
		}

	}
}
?>
<br>
<? if(!empty($msg_erro)){ ?>
<div class="alert alert-danger">
		<h4>
			<? echo $msg_erro; ?>
		</h4>
</div>
<? } ?>

<? if(!empty($msg)){ ?>
<div class="alert alert-success">
		<h4>
			Gravado com sucesso
		</h4>
</div>
<? } ?>
<div class="row">
    <?php if (!in_array($aprova, array('aprovadas', 'reprovadas'))) {
        $display = 'display: none';
    }?>
	<b class="obrigatorio pull-right data" style="<?=$display;?>">  * Campos obrigatórios </b>
</div>
<form name="frm_pesquisa" method="post" class='form-search form-inline tc_formulario' action="<?echo $PHP_SELF?>">
<div class="titulo_tabela">Parâmetros de Pesquisa</div>
	<br />
	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array('os', $campos)) ? 'error' : ''; ?>'>
					<label class='control-label' for='numero_os'>Número da OS</label>
					<div class='controls controls-row'>
						<div class='span7'>
							<?php if (!in_array($aprova, array('aprovadas', 'reprovadas'))) {
								$display = 'display: none';
							}?>
							<h5 class='asteristico data' style='<?=$display; ?>'>*</h5>
							<input class="span12" type="text" name="os" id="os" size="20" maxlength="20" value="<? echo $os ?>" tabindex='1'>	
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<?
				if ($login_fabrica == 52) {
				?>
							<div class='control-group'>
								<label class='control-label' for='numero_os'>Marca</label>
								<div class='controls controls-row'>
									<div class='span4'>	
					<?
					$sql_fricon = "SELECT marca, nome
									FROM tbl_marca
									WHERE tbl_marca.fabrica = $login_fabrica
									ORDER BY tbl_marca.nome ";

									$res_fricon = pg_query($con, $sql_fricon); ?>

					<select name='marca_logo' id='marca_logo' class="frm">
					<?
					if (pg_numrows($res_fricon) > 0) { ?>
						<option value=''>ESCOLHA</option> <?
						for ($x = 0 ; $x < pg_numrows($res_fricon) ; $x++){
							$marca_aux = trim(pg_result($res_fricon, $x, marca));
							$nome_aux = trim(pg_result($res_fricon, $x, nome));

							if ($marca_logo == $marca_aux) {
								$selected = "SELECTED";
							}else {
								$selected = "";
							}?>
							<option value='<?=$marca_aux?>' <?=$selected?>><?=$nome_aux?></option> <?
						}
					}else { ?>
						<option value=''>Não existem linhas cadastradas</option><?
					}
					?>
					</select>
					</div>
				</div>
			</div>
	<?
	}

	?>		</div>
		<div class='span2'></div>
	</div>
    <?php if ($login_fabrica == 30) { ?>
        <div class='span2' style="width: 122px !important;"></div>
        <div>
            <input type="radio" name="data_consulta" value="data_digitacao" <?=($data_consulta == 'data_digitacao' || empty($data_consulta)) ? 'checked' : ''?>>Data Digitação
            <input type="radio" name="data_consulta" value="data_auditoria" <?=($data_consulta == 'data_auditoria') ? 'checked' : ''?>>Data Auditoria
        </div>
    <?php } ?>
	<div class='row-fluid'>
			<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?= (in_array('data_inicial', $campos)) ? 'error' : '' ?>'>
						<label class='control-label' for='data_inicial'>Data Inicial</label>
						<div class='controls controls-row'>
							<div class='span7'>
								<?php if (!in_array($aprova, array('aprovadas', 'reprovadas'))) {
									$display = 'display: none';
								}?>
								<h5 class='asteristico data' style='<?=$display; ?>'>*</h5>
									<input type="text" name="data_inicial" id="data_inicial" maxlength="10" value="<? echo $data_inicial ?>" class="span12" tabindex='2'>
							</div>
						</div>
					</div>
				</div>
			<div class='span4'>
				<div class='control-group <?= (in_array('data_final', $campos)) ? 'error' : '' ?>'>
					<label class='control-label' for='data_final'>Data Final</label>
					<div class='controls controls-row'>
						<div class='span7'>
							<?php if (!in_array($aprova, array('aprovadas', 'reprovadas'))) {
								$display = 'display: none';
							}?>
							<h5 class='asteristico data' style='<?=$display; ?>'>*</h5>
								<input type="text" name="data_final" id="data_final" maxlength="10" value="<? echo $data_final ?>" class="span12" tabindex='3'>
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'>Código Posto</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" name="posto_codigo" id="codigo_posto" size="15"  value="<? echo $posto_codigo != '' ? $posto_codigo : ''; ?>" class="span12" tabindex='4'>
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
							<input type="text" name="posto_nome" id="descricao_posto" size="40"  value="<? echo $posto_codigo != '' ? $posto_nome : ''; ?>" class="frm" tabindex='5'>
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>

<?php if($login_fabrica == 30){ $aAtendentes = hdBuscarAtendentes(); //hd_chamado=2537875 
	$atendente_ordenado = array();
	foreach ($aAtendentes as $aAtendente) {
		$atendente_ordenado[$aAtendente['admin']] = (!empty($aAtendente['nome_completo'])) ? $aAtendente['nome_completo'] : $aAtendente['login'];
	}
	asort($atendente_ordenado);
?>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='codigo_posto'>Inspetor</label>
				<div class='controls controls-row'>
					<div class='span7'>
						<select class='frm' name="admin_sap" id="admin_sap">
					      <option value=""></option>
					      <?php foreach ($atendente_ordenado as $admin => $nome) {
					      		echo "<option value='{$admin}' ";
					      		if ($admin == $admin_sap) {
					      			echo "selected";
					      		}
					      		echo ">".ucfirst(strtolower($nome))."</option>";
					      } ?>
					   </select>
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>		

<?php } ?>



<?if ($login_fabrica==50){?>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='codigo_posto'>Região Comercial</label>
				<div class='controls controls-row'>
					<div class='span7'>
						<select name='regiao_comercial' class="frm">
							<option value=''></option>
							<option value='1' <? if($regiao_comercial=='1') echo "selected"?>>Região Comercial 1 (SP)<option>
							<option value='2' <? if($regiao_comercial=='2') echo "selected"?>>Região Comercial 2 (PR SC RS RO AC MS e MT)<option>
							<option value='3' <? if($regiao_comercial=='3') echo "selected"?>>Região Comercial 3 (ES BA PE PB SE AL CE AM TO PI MA PA RN RR AP)<option>
							<option value='4' <? if($regiao_comercial=='4') echo "selected"?>>Região Comercial 4 (MG GO DF RJ)<option>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>

<?}
if (in_array($login_fabrica,[24,142])) {
?>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group'>
				<?php 
					$lblEstado = 'Estado Posto';

					if ($login_fabrica == 24) {

						$lblEstado = 'Estado';
					}
				?>
				<label class='control-label' for='codigo_posto'><?=$lblEstado?></label>
				<div class='controls controls-row'>
					<div class='span7'>
						<select name="posto_estado" class="frm">
						            <option value="">&nbsp;</option>
									<?
									    foreach($array_estados() as $est=>$nome){
									?>
									            <option value="<?=$est?>" <?=($est == $posto_estado) ? "selected" : "" ;?>><?=$nome?></option>
									<?
									    }
									?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
<?
}
?>
	<br />
		<div class='row-fluid'>
			<div class='span2'></div>
			<b>Mostrar as OS:</b><br>
			<div class='span3'>
				 <label class="radio">
			        <INPUT TYPE="radio" NAME="aprova" value='aprovacao' <? if(trim($aprova) == 'aprovacao' OR trim($aprova)==0) echo "checked='checked'"; ?> tabindex='6'>Em aprovação
			    </label>
			</div>
			<div class='span3'>
			    <label class="radio">
			        <INPUT TYPE="radio" NAME="aprova" value='aprovadas' <? if(trim($aprova) == 'aprovadas') echo "checked='checked'"; ?>>Aprovadas 
			    </label>
			</div>
			<div class='span3'>
			    <label class="radio">
			        <? if (!in_array($login_fabrica, array(149))) { ?>
						<INPUT TYPE="radio" NAME="aprova" value='reprovadas' <? if(trim($aprova) == 'reprovadas') echo "checked='checked'"; ?>>Reprovadas
					<? } ?>
			    </label>
			</div>
			<div class='span2'></div>
		</div>
		<?php /*if(in_array($login_fabrica, array(30))){ 
			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span3'>
					 <label class="radio">			        
							<input type="checkbox" name="gerar_excel" value="sim" <?php echo ($_POST["gerar_excel"] == "sim") ? "checked" : ""; ?> /> 
							Gerar Excel
				    </label>
				</div>
			</div>	
		 } */?>
<?php if ($login_fabrica == 74): ?>
	<div class='row-fluid'>
		<div class='span2'></div>
		<b>Tipo de auditoria</b><br>
		<div class='span4'>
			 <label class="radio">
		        <input type="radio" name="tipo_auditoria" value='alteracao_manual' <? if(trim($tipo_auditoria) == 'alteracao_manual') echo "checked"; ?> />Alteração manual de KM
		    </label>
		</div>
		<div class='span4'>
		    <label class="radio">
		       	<input type="radio" name="tipo_auditoria" value='superior_80' <? if(trim($tipo_auditoria) == 'superior_80') echo "checked"; ?> />KM superior a 80KM 
		    </label>
		</div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
		    <label class="radio">
		        <input type="radio" name="tipo_auditoria" value='162' <? if(trim($tipo_auditoria) == '162') echo "checked"; ?> />Reincidência cidade/semana/posto 
		    </label>
		</div>
		<div class='span4'>
		    <label class="radio">
		        <input type="radio" name="tipo_auditoria" value='161' <? if(trim($tipo_auditoria) == '161') echo "checked"; ?> />KM Acima de 90 dias 
		    </label>
		</div>	
	</div>

	


<?php endif; ?>
<?php if ($login_fabrica == 24) { ?>
	<div class='row-fluid'>
		<div class='span2'></div>
		<b>Tipo de Data:</b><br>
		<div class='span3'>
			 <label class="radio">
		        <input type="radio" name="tipo_data" value='abertura' <? if (strlen($tipoData) > 0 && $tipoData == 'abertura' || strlen($tipoData) == 0) echo "checked='checked'"; ?> tabindex='6'>Abertura
		    </label>
		</div>
		<div class='span3'>
			 <label class="radio">
		        <input type="radio" name="tipo_data" value='digitacao' <? if (strlen($tipoData) > 0 && $tipoData == 'digitacao') echo "checked='checked'"; ?> tabindex='6'>Digitação
		    </label>
		</div>
		<div class='span3'>
			 <label class="radio">
		        <input type="radio" name="tipo_data" value='liberada_auditoria' <? if (strlen($tipoData) > 0 && $tipoData == 'liberada_auditoria') echo "checked='checked'"; ?> tabindex='6'>
		        Liberada Auditoria
		    </label>
		</div>
		<div class='span2'></div>
	</div>
<?php } ?>

		<input type='hidden' name='btn_acao' value=''>
		<input type="button" class="btn" value="Pesquisar" onclick="javascript: if ( document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='Pesquisar'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" alt='Clique AQUI para pesquisar' tabindex='7'>
<br /><br />
</form>

<?
if (strlen($btn_acao)  > 0 && !strlen($msg_erro)) {

	if($login_fabrica == 30){ //hd_chamado=2537875
		if(strlen($admin_sap) > 0){
			$admin_sap = (int) $_POST['admin_sap'];
			$cond_admin_sap = " AND tbl_posto_fabrica.admin_sap = $admin_sap ";
		}
	}



	if(strlen($promotor_treinamento)>0) $sql_add = " AND tbl_promotor_treinamento.admin = $promotor_treinamento ";
	else                                $sql_add = " ";

	//HD 169362
	if ($regiao_comercial){
		/*
		1-SP
		2-PR SC RS RO AC MS MT
		3-ES BA PE PB SE AL CE AM TO PI MA PA RN RR AP
		4-MG GO DF RJ
		*/
		$array_regioes = array(
								'1' => "SP",
								'2' => "PR SC RS RO AC MS MT",
								'3' => "ES BA PE PB SE AL CE AM TO PI MA PA RN RR AP",
								'4' => "MG GO DF RJ",
							);

		if (isset($array_regioes[$regiao_comercial])) {
			$estados = $array_regioes[$regiao_comercial];
			$estados = str_replace(" ","','",$estados);
			$estados = "'".$estados."'";
			$sql_add .= " AND tbl_posto.estado IN ($estados) ";
		}
	}

	if(strlen($posto_estado) > 0){
        $sql_add .= " AND tbl_posto.estado = '$posto_estado' ";
    }

	if(isset($novaTelaOs)){

		if ($login_fabrica != 145) {
			$distinctOn = "DISTINCT ON(tbl_os.os)";
		}

		$condJoin = " JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
					  JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i=$login_fabrica ";
	}else{
		$condJoin = " JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i=$login_fabrica ";
		$distinctOn = "DISTINCT";
	}

	$camposProduto = "  tbl_produto.referencia             AS produto_referencia    ,
							tbl_produto.descricao              AS produto_descricao     ,
							tbl_defeito_constatado.descricao AS defeito_constatado,
							tbl_defeito_constatado_grupo.descricao AS defeito_constatado_grupo,
							tbl_produto.voltagem                                        , ";

	if($login_fabrica == 52){
		if ($marca_logo > 0) {
			$cond_marca_logo = " AND tbl_os.marca = $marca_logo ";
		}

	}

	$statusOs = "98"; 

	if ($login_fabrica == 24) {

		if (strlen($tipoData) > 0) {
			
			if ($tipoData != 'aprovacao') {
				
				$statusOs = ' 99,100 ';
			}

			if ($tipoData == 'liberada_auditoria') {

				$joinAuditoria = "JOIN tbl_os_status ON (tbl_os_status.os = tbl_os.os) ";
			}
    	}

	} else {

		if(!empty($xdata_inicial)) {
			$condData = " AND tbl_os_status.data > '$xdata_inicial 00:00' ";
		}else{
			$condData = " AND tbl_os_status.data > current_timestamp - interval '1 year' ";
		}
	}

	$sql =  "SELECT interv.os
			INTO TEMP tmp_interv_$login_admin
			FROM (
				SELECT
				ultima.os,
				(
					SELECT status_os
					FROM tbl_os_status
					WHERE status_os IN (" . implode(',', $array_reincidencias) . ")
					AND tbl_os_status.os = ultima.os AND tbl_os_status.fabrica_status = $login_fabrica
					$condData
					ORDER BY data DESC LIMIT 1
				) AS ultimo_status
				FROM (
					SELECT DISTINCT os
					FROM tbl_os_status
					WHERE status_os IN (" . implode(',', $array_reincidencias) . ")
					$cond_auditoria
					$condData
					AND tbl_os_status.fabrica_status = $login_fabrica
				) ultima
			) interv
			WHERE interv.ultimo_status IN ($aprovacao);

			CREATE INDEX tmp_interv_OS_$login_admin ON tmp_interv_$login_admin(os);

			/* select os from  tmp_interv_$login_admin; */

			SELECT	{$distinctOn} tbl_os.os                                          ,
					tbl_os.hd_chamado											,
					tbl_os.posto                                                ,
					tbl_os.data_abertura                                        ,
					tbl_os.marca                                                ,
					tbl_os.sua_os                                               ,
					tbl_os.consumidor_nome                                      ,
					tbl_os.qtde_km                                              ,
					tbl_os_extra.deslocamento_km                AS km_google    ,
					tbl_os.autorizacao_domicilio                                ,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')  AS data_abertura,
					TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
					(SELECT TO_CHAR(tbl_os_status.data,'DD/MM/YYYY') 
				     FROM tbl_os_status 
					 WHERE tbl_os_status.status_os IN ({$statusOs})
					 AND tbl_os_status.os = tbl_os.os 
					 ORDER BY os_status 
					 DESC LIMIT 1) AS data_auditoria,
 					(SELECT nome_completo
				     FROM tbl_os_status
				     LEFT JOIN tbl_admin ON (tbl_admin.admin = tbl_os_status.admin)
					 WHERE tbl_os_status.status_os IN ({$statusOs})
					 AND tbl_os_status.os = tbl_os.os 
					 ORDER BY os_status 
					 DESC LIMIT 1) AS admin_auditor,
					tbl_os.fabrica                                              ,
					tbl_os.consumidor_nome                                      ,
					tbl_os.consumidor_cpf,
					tbl_os.consumidor_cidade                                    ,
					tbl_os.consumidor_estado                                    ,
					tbl_os.nota_fiscal_saida                                    ,
					tbl_os.tipo_atendimento  									,
					tbl_os.qtde_diaria,
					to_char(tbl_os.data_nf_saida,'DD/MM/YYYY') AS data_nf_saida ,
					tbl_posto.nome                     AS posto_nome            ,
					tbl_posto_fabrica.codigo_posto                              ,
					tbl_posto_fabrica.contato_estado                            ,
					tbl_posto_fabrica.contato_cidade                            ,
					$camposProduto
					(SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (" . implode(',', $array_reincidencias) . ") AND tbl_os_status.fabrica_status = $login_fabrica ORDER BY data DESC LIMIT 1) AS status_os         ,
					(SELECT regexp_replace( observacao, '\n', ' ' ) as observacao FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (" . implode(',', $array_reincidencias) . ") AND tbl_os_status.fabrica_status = $login_fabrica
					ORDER BY data DESC LIMIT 1) AS status_observacao,
					(SELECT tbl_status_os.descricao FROM tbl_os_status JOIN tbl_status_os USING(status_os) WHERE tbl_os.os = tbl_os_status.os AND status_os IN (" . implode(',', $array_reincidencias) . ") AND tbl_os_status.fabrica_status = $login_fabrica
					ORDER BY data DESC LIMIT 1) AS status_descricao
				FROM tmp_interv_$login_admin X
				JOIN tbl_os ON tbl_os.os = X.os
				JOIN    tbl_os_extra ON tbl_os_extra.os = tbl_os.os AND tbl_os_extra.i_fabrica=$login_fabrica
				$joinAuditoria
				$condJoin
				JOIN tbl_posto                ON tbl_os.posto        = tbl_posto.posto
				JOIN tbl_posto_fabrica        ON tbl_posto.posto     = tbl_posto_fabrica.posto
				AND tbl_posto_fabrica.fabrica = $login_fabrica
				LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado AND tbl_defeito_constatado.fabrica = $login_fabrica
				LEFT JOIN tbl_defeito_constatado_grupo ON tbl_defeito_constatado_grupo.defeito_constatado_grupo = tbl_defeito_constatado.defeito_constatado_grupo AND tbl_defeito_constatado_grupo.fabrica = $login_fabrica
				WHERE tbl_os.fabrica = $login_fabrica AND tbl_os.excluida IS NOT TRUE
				$cond_marca_logo
				$sql_add
				$Xos
				$cond_admin_sap
				$sql_posto ";
	if($login_fabrica==20) $sql .= " AND tipo_atendimento=13 ";
	if($login_fabrica==3)  $sql .= " AND tipo_atendimento=37 ";

    $order_by = " ORDER BY tbl_os.os, tbl_posto_fabrica.codigo_posto,tbl_os.os ";
    if (!empty($data_consulta) && $data_consulta == 'data_auditoria' && $login_fabrica == 30) {
        $sql .= " AND (SELECT TO_CHAR(tbl_os_status.data,'DD/MM/YYYY') AS data_auditoria FROM tbl_os_status WHERE tbl_os_status.status_os = 98 AND tbl_os_status.os = tbl_os.os ORDER BY os_status DESC LIMIT 1) BETWEEN '$data_inicial' AND '$data_final'";
    }else{
        if (strlen($xdata_inicial) > 0 AND strlen($xdata_final) > 0 && $login_fabrica != 24) {
            $sql .= " AND tbl_os.data_digitacao BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59' $cond_excluidas";

			if ($login_fabrica == 145) {
				$order_by = "ORDER BY tbl_os.data_digitacao::date ASC, tbl_os.posto, tbl_os.consumidor_cpf";
			}
        } else {
        	
        	if ($login_fabrica == 24) {

		    	$tipos = [ 'abertura'  => 'tbl_os.data_abertura',
		    			   'digitacao' => 'tbl_os.data_digitacao',
		    			   'liberada_auditoria' => 'tbl_os_status.data' ];

		    	if ($tipoData == 'liberada_auditoria') {

        			$sql .= " AND tbl_os_status.data BETWEEN '$xdata_inicial 00:00' AND '$xdata_final 23:59' AND tbl_os_status.status_os = 99 ";
		    	
		    	} else {

		    		$sql .= " AND " . $tipos[$tipoData] . " BETWEEN '$xdata_inicial 00:00' AND '$xdata_final 23:59' ";
		    	}		  
        	}
        }
    }
	if($login_fabrica == 74){
		$order_by =' ORDER BY tbl_os.os, tbl_os.data_abertura,tbl_os.posto,tbl_os.consumidor_cidade,tbl_os.consumidor_estado';
	}
	$sql .= $order_by;

	$res = pg_query($con,$sql);

	#echo pg_last_error();exit;

	if(pg_num_rows($res)>0){

		$arr_gera_xls_aprovadas = array($login_fabrica); //liberado para todas as fabrica hd-3701929

		if (in_array($login_fabrica, $arr_gera_xls_aprovadas)) {
			$file = "xls/relatorio-os-km-$login_fabrica.csv";
			$fp = fopen($file,"w");
		}

		// if(in_array($login_fabrica, array(30))){
		// 	$gerar_excel = $_POST["gerar_excel"];
		// 	if($gerar_excel == "sim"){
		// 		$file = "xls/relatorio-os-km-$login_fabrica.csv";
		// 		$fp = fopen($file, "w");
		// 	}
		// }

		?>

		<br /> <br />
		<div class="btn_excel" onclick="javascript: window.location='<?=$file?>';">		    
		    <span class="txt" style="background: #5e9c76;">Gerar Arquivo CSV</span>
		    <span><img style="width:40px ; height:40px;" src='imagens/icon_csv.png' /></span>
		</div>
		<br /> <br />

		<table width='98%' border='0' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 11px' bgcolor='#FFFFFF'>
			<tr>
				<td bgcolor='#FFCC00' width='60' style="border-radius: 3px; border: solid 2px #fff;">&nbsp;</td>
				<td align='left'>Fábrica interagiu</td>
			</tr>

			<tr>
				<td bgcolor='#669900' width='60' style="border-radius: 3px; border: solid 2px #fff">&nbsp;</td>
				<td align='left'>Posto interagiu</td>
			</tr>

			<?php
			if($login_fabrica == 140){
				?>
				<tr>
					<td bgcolor='#F6CECE' width='30' style="border-radius: 3px;">&nbsp;</td>
					<td align='left'>OS com KM e Entrega Técnica</td>
				</tr>
				<?php
			}
			?>

	  	</table>

	  	<br />
        </div>
		<form name="frm_pesquisa2" method="POST" action="<?=$_SERVER['PHP_SELF']?>" >
			<input type="hidden" name="data_inicial" value="<?=$data_inicial?>" />
			<input type="hidden" name="data_final" value="<?=$data_final?>" />
			<input type="hidden" name="aprova" value="<?=$aprova?>" />
			<input type="hidden" name="pesquisa_posto" value="<?=$pesquisa_posto?>" />

			<?php
			if (in_array($login_fabrica, $arr_gera_xls_aprovadas)) {
				if ($login_fabrica == 52) {
					fwrite($fp, utf8_encode("OS;CHAMADO;KM;DATA DIGITAÇÃO;POSTO;POSTO CIDADE;POSTO UF;CONSUMIDOR CIDADE;CONSUMIDOR UF;PRODUTO REFERÊNCIA;PRODUTO DESCRIÇÃO;MARCA;GRUPO DE DEFEITO;DEFEITO CONSTATADO;STATUS\n") );
				// 	fwrite($fp, "
				// 	<table>
				// 		<thead>
				// 			<tr>
				// 				<th>OS</th>
				// 				<th>CHAMADO</th>
				// 				<th>KM</th>
				// 				<th>DATA DIGITAÇÃO</th>
				// 				<th>POSTO</th>
				// 				<th>POSTO CIDADE</th>
				// 				<th>POSTO UF</th>
				// 				<th>CONSUMIDOR CIDADE</th>
				// 				<th>CONSUMIDOR UF</th>
				// 				<th>PRODUTO REFERÊNCIA</th>
				// 				<th>PRODUTO DESCRIÇÃO</th>
				// 				<th>MARCA</th>
				// 				<th>GRUPO DE DEFEITO</th>
				// 				<th>DEFEITO CONSTATADO</th>
				// 				<th>STATUS</th>
				// 			</tr>
				// 		</thead>
				// 		<tbody>
				// ");
				}elseif ($login_fabrica == 30){
					//fwrite($fp, utf8_encode( "OS;KM CALCULADO;KM;DATA DIGITAÇÃO;DATA AUDITORIA;POSTO;POSTO CIDADE;POSTO UF;CONSUMIDOR CIDADE;CONSUMIDOR UF;PRODUTO REFERÊNCIA;PRODUTO DESCRIÇÃO;GRUPO DE DEFEITO;DEFEITO CONSTATADO;STATUS\n") );
					fwrite($fp, utf8_encode(  "OS;KM CALCULADO;KM;OBSERVAÇÃO;DATA DIGITAÇÃO;DATA AUDITORIA;CONSUMIDOR CIDADE/UF;POSTO CIDADE/UF;POSTO;PRODUTO;DESCRIÇÃO;STATUS \n"));
                    // fwrite($fp, "
                    // <table>
                    //     <thead>
                    //         <tr>
                    //             <th>OS</th>
                    //             <th>KM CALCULADO</th>
                    //             <th>KM</th>
                    //             <th>DATA DIGITAÇÃO</th>
                    //             <th>DATA AUDITORIA</th>
                    //             <th>POSTO</th>
                    //             <th>POSTO CIDADE</th>
                    //             <th>POSTO UF</th>
                    //             <th>CONSUMIDOR CIDADE</th>
                    //             <th>CONSUMIDOR UF</th>
                    //             <th>PRODUTO REFERÊNCIA</th>
                    //             <th>PRODUTO DESCRIÇÃO</th>
                    //             <th>GRUPO DE DEFEITO</th>
                    //             <th>DEFEITO CONSTATADO</th>
                    //             <th>STATUS</th>
                    //         </tr>
                    //     </thead>
                    //     <tbody>
                    // ");
				}else{

					if ($login_fabrica == 24) {

						$headerSuggar = utf8_encode('DATA ABERTURA; LIBERADO AUDITORIA; LIBERADO PELO ADMIN');
					}

					fwrite($fp, "OS;KM;DATA DIGITAÇÃO;" . $headerSuggar . "POSTO;POSTO CIDADE;POSTO UF;CONSUMIDOR CIDADE;CONSUMIDOR UF;PRODUTO REFERÊNCIA;PRODUTO DESCRIÇÃO;GRUPO DE DEFEITO;DEFEITO CONSTATADO;STATUS\n");
					// fwrite($fp, "
					// <table>
					// 	<thead>
					// 		<tr>
					// 			<th>OS</th>
					// 			<th>KM</th>
					// 			<th>DATA DIGITAÇÃO</th>
					// 			<th>POSTO</th>
					// 			<th>POSTO CIDADE</th>
					// 			<th>POSTO UF</th>
					// 			<th>CONSUMIDOR CIDADE</th>
					// 			<th>CONSUMIDOR UF</th>
					// 			<th>PRODUTO REFERÊNCIA</th>
					// 			<th>PRODUTO DESCRIÇÃO</th>
					// 			<th>GRUPO DE DEFEITO</th>
					// 			<th>DEFEITO CONSTATADO</th>
					// 			<th>STATUS</th>
					// 		</tr>
					// 	</thead>
					// 	<tbody>
					// ");
				}
			}

			// if($login_fabrica == 30){
			// 	if($gerar_excel == "sim"){
			// 		fwrite($fp, "OS;KM CALCULADO;KM;OBSERVAÇÃO;DATA DIGITAÇÃO;DATA AUDITORIA;CONSUMIDOR CIDADE/UF;POSTO CIDADE/UF;POSTO;PRODUTO;DESCRIÇÃO;STATUS \n");
			// 	}
			// }

			?>
			<!-- </div> -->
            <input type="hidden" name="qtde_os" value="<?=pg_num_rows($res)?>" />
			<table id="relatorio_os_auditoria" class="table table-bordered table-large" >
				<thead>
					<tr class='titulo_tabela'>
						<th colspan='100'>
		                    <?php
		                    $data_inicial = (empty($data_inicial)) ? pg_fetch_result($res, 0, "data_digitacao") : $data_inicial;
		                    $data_final  = (empty($data_final)) ? pg_fetch_result($res, pg_num_rows($res)-1, "data_digitacao") : $data_final;
		                    echo "Resultados referentes ao período: $data_inicial até $data_final";
		                    ?>
						</th>
					</tr>	
					<tr class="titulo_coluna">
						<th>
                        <?php
                        if (($login_fabrica == 52 && trim($aprova) == "aprovacao") || !in_array($login_fabrica, array(52))) {
                        ?>
                        <input type="checkbox" name="seleciona_todos" onclick="javascript: checkaTodos();" alt="Selecionar todos">
                        <?php } ?></th>
						<th>OS</th>
						<?php
						if ($login_fabrica == 52) {
						?>
							<th>CHAMADO</th>
						<?php
						}

						if ($login_fabrica == 50) {
						?>
							<th>KM <a href="regra_km_colormaq.html" target="_blank" title="Ver Regra">?</a></th>
						<?php
						} else {
                            if($login_fabrica == 30){
						?>
							<th>KM CALCULADO</th>
                        <?
                            }
                        ?>
							<th>KM</th>
						<?php
						}

						if ($login_fabrica == 142) {
						?>
							<th>VISITA</th>
						<?php
						}

						if ($login_fabrica == 140) {
						?>
							<th>VALOR PRODUTO</th>
							<th>%</th>
							<th>VALOR ENTREGA TÉC.</th>
						<?php
						}

						if ($login_fabrica == 94) {
						?>
							<th>TOTAL KM</th>
						<?php
						}

						if ($login_fabrica == 3) {
						?>
							<th>Nº AUTORIZAÇÃO</th>
							<th>JUSTIFICATIVA</th>
						<?php
						}

						if ($login_fabrica == 30 || $login_fabrica == 15) {
						?>
							<th>OBSERVAÇÃO</th>
						<?php
						}
						?>
						<th class="date_column">DATA DIGITAÇÃO</th>
						
						<?php if ($login_fabrica == 24) { ?>

							<th class="date_column">DATA ABERTURA</th>

						<?php } ?>
						<?php
                        if(in_array($login_fabrica, [24,30,145])) {
                        	$lblAuditoria = "DATA AUDITORIA";
                        	if ($login_fabrica == 24) {
                        		$lblAuditoria = "LIBERADO AUDITORIA";
                        	}
                        ?>
                        <th class="date_column"><?=$lblAuditoria?></th>

                        <?php if ($login_fabrica == 24) { ?>
                        	<th class="date_column">LIBERADO PELO ADMIN</th>
                    	<?php } ?>
                        <?
                        }
						if ($login_fabrica == 145) {
						?>
							<th>CONSUMIDOR</th>
							<th>CPF/CNPJ</th>
						<?php
						}
						?>

						<?php
						if ($login_fabrica == 52 || $login_fabrica == 30) {
						?>
							<th>CONSUMIDOR CIDADE / UF</th>
							<th>POSTO CIDADE / UF</th>
						<?php
						}
						?>
						<th>POSTO</th>
						<?php
						if (in_array($login_fabrica, array(74,15,94))) {
						?>
							<th>CIDADE</th>
							<th>ESTADO</th>
						<?php
						}
						?>

						<?php
						if (!in_array($login_fabrica, array(138,142,143))) {
						?>
						<th>PRODUTO</th>
						<th>DESCRIÇÃO</th>
						<?php
						}
						if ($login_fabrica == 52) {
						?>
							<th>MARCA</th>
							<th>GRUPO DE DEFEITO</th>
							<th>DEFEITO CONSTATADO</th>
						<?php
						}

						if ($login_fabrica == 74) {
						?>
							<th>OBSERVAÇÕES</th>
						<?php
						} else {
						?>
							<th>STATUS</th>
						<?php
						}

						if (in_array($login_fabrica, array(30, 50, 74)) || $login_fabrica >= 131) {
						?>
							<th>INTERAÇÃO</th>
						<?php
						}
						?>
					</tr>
				</thead>
				<tbody>
					<?php
					$qtde_intervencao = 0;
					$total_os         = pg_num_rows($res);

					for ($x = 0; $x < pg_num_rows($res); $x++) {
						$os                       = pg_fetch_result($res, $x, "os");
						$posto                    = pg_fetch_result($res, $x, "posto");
						$hd_chamado               = pg_fetch_result($res, $x, "hd_chamado");
						$sua_os                   = pg_fetch_result($res, $x, "sua_os");
						$codigo_posto             = pg_fetch_result($res, $x, "codigo_posto");
						$posto_nome               = pg_fetch_result($res, $x, "posto_nome");
						$qtde_km                  = pg_fetch_result($res, $x, "qtde_km");
						$autorizacao_domicilio    = pg_fetch_result($res, $x, "autorizacao_domicilio");
						$consumidor_nome          = pg_fetch_result($res, $x, "consumidor_nome");
						$consumidor_cpf          = pg_fetch_result($res, $x, "consumidor_cpf");
						$consumidor_cidade        = pg_fetch_result($res, $x, "consumidor_cidade");
						$consumidor_estado        = pg_fetch_result($res, $x, "consumidor_estado");
						$qtde_diaria = pg_fetch_result($res, $x, "qtde_diaria");

						if(!in_array($login_fabrica, array(138,142,143))){
							$produto_referencia       = pg_fetch_result($res, $x, "produto_referencia");
							$produto_descricao        = pg_fetch_result($res, $x, "produto_descricao");
							$produto_voltagem         = pg_fetch_result($res, $x, "voltagem");
							$defeito_constatado       = pg_fetch_result($res, $x, "defeito_constatado");
							$defeito_constatado_grupo = pg_fetch_result($res, $x, "defeito_constatado_grupo");
							$consumidor_marca_logo	  = pg_fetch_result($res, $x, "marca");
						}

                        if($login_fabrica == 30){
                            $km_google      = pg_fetch_result($res, $x, "km_google");               
                        }

                        $data_auditoria 		  = pg_fetch_result($res, $x, "data_auditoria");
                        $admin_auditor            = pg_fetch_result($res, $x, "admin_auditor");
						$data_digitacao           = pg_fetch_result($res, $x, "data_digitacao");
						$data_abertura            = pg_fetch_result($res, $x, "data_abertura");
						$status_os                = pg_fetch_result($res, $x, "status_os");
						$status_observacao        = pg_fetch_result($res, $x, "status_observacao");
						$status_descricao         = pg_fetch_result($res, $x, "status_descricao");
						$contato_estado           = pg_fetch_result($res, $x, "contato_estado");
						$contato_cidade           = pg_fetch_result($res, $x, "contato_cidade");
						$tipo_atendimento         = pg_fetch_result($res, $x, "tipo_atendimento");

						$qtde_kmx = number_format($qtde_km,3,',','.');

						$cor = ($x % 2 == 0) ? "#FEFEFE": '#E8EBEE';

						if (in_array($login_fabrica, array(30, 50, 74)) || $login_fabrica >= 131) {
							$sqlint = " SELECT os_interacao, admin
										FROM tbl_os_interacao
										WHERE os = {$os}
										AND interno IS NOT TRUE
										ORDER BY os_interacao DESC
										LIMIT 1";
							$resint = pg_query($con, $sqlint);

							if (pg_num_rows($resint) > 0) {
								$cor = (strlen(pg_fetch_result($resint, 0, "admin")) > 0) ? "#FFCC00" : "#669900";
							}

							if ($desabilita_check) {
								$disabled_check = "disabled='disabled'";
							}
						}

						if (strlen($sua_os) == 0) {
							$sua_os = $os;
						}

						if($login_fabrica == 140){

							$sql_ta = "SELECT entrega_tecnica FROM tbl_tipo_atendimento WHERE tipo_atendimento = $tipo_atendimento AND fabrica = $login_fabrica";
							$res_ta = pg_query($con, $sql_ta);

							if(pg_num_rows($res_ta) > 0){

								$entrega_tecnica = pg_fetch_result($res_ta, 0, 'entrega_tecnica');

								if($entrega_tecnica == "t"){
									$cor = "#F6CECE";
								}

							}

						}
						echo "<input type=\"hidden\" value=\"$admin_sap\" name=\"admin_sap\" />"; //hd_chamado=2537875
						?>

						<input type="hidden" name="posto_codigo" value="<?=$codigo_posto?>" />
						<input type="hidden" name="posto_nome" value="<?=$posto_nome?>" />

						<tr id="linha_<?=$x?>" style="background-color: <?=$cor?>;" >
							<td class="tac">
								<?php
								if ($status_os == 98 || /*($login_fabrica == 52 && ($status_os == 99 || $status_os == 100)) ||*/ ($login_fabrica == 74 && ($status_os == 161 || $status_os == 162) && trim($aprova) == "aprovacao")) {
								?>
									<input type="checkbox" id="check_<?=$x?>" name="check_<?=$x?>" onclick="javascript: setCheck('check_<?=$x?>', 'linha_<?=$x?>', '<?=$cor?>');" value="<?=$os?>" <?=$disabled_check?> <?=(strlen($msg_erro) > 0 && strlen($_POST["check_{$x}"]) > 0) ? "CHECKED" : ""?> />
								<?php
								}
								?>
							</td>

							<?php
							$sql_extrato = "SELECT extrato
											FROM tbl_os_extra
											JOIN tbl_extrato USING (extrato)
											WHERE os = {$os}
											and fabrica = {$login_fabrica}";
							$res_extrato = pg_query($con, $sql_extrato);

							unset($title_extrato, $title_extrato2);

							if (pg_num_rows($res_extrato) > 0 && strlen(pg_fetch_result($res_extrato, 0, "extrato")) > 0) {
								$title_extrato  = "<br />".pg_fetch_result($res_extrato, 0, "extrato");
								$title_extrato2 = "Esta Ordem de Serviço já consta em um extrato e será recalculado! Se você não tem certeza da alteração, não a faça! A impressão deste extrato pelo Posto ou por outro setor da administração pode ter sido realizada!";
							}
							?>

							<td class="tac" nowrap >
								<a href="os_press.php?os=<?=$os?>" title="<?=$title_extrato2?>" target="_blank" >
									<?=$sua_os?> <?=$title_extrato?>
								</a>
								<?php
								if ($login_fabrica == 74) {
									if ($tipo_auditoria != 162) {
										$cond = " AND 162 IN (SELECT status_os FROM tbl_os_status WHERE os = {$os}) ";
									}

									$sql = "SELECT os
											FROM tbl_os
											JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
											AND km_google IS TRUE
											WHERE tbl_os.fabrica = {$login_fabrica}
									        AND posto = {$posto}
									        AND os <> {$os}
											{$cond}
								            AND TRIM(LOWER(consumidor_cidade)) = TRIM(LOWER('{$consumidor_cidade}'))
									        AND data_abertura BETWEEN ('{$data_abertura}'::date - INTERVAL '1 WEEK') AND '{$data_abertura}'::date";
									$res2 = pg_query($con, $sql);

									$os_anteriores = array();

									for ($j = 0; $j < pg_num_rows($res2); $j++) {
										$os_anteriores[] = pg_result($res2, $j, "os");
									}

									if (!empty($os_anteriores)) {
										echo "<br />OS Anterior(es): ".(implode(", ", $os_anteriores));
									}
								}
								?>
							</td>

							<?php
							if ($login_fabrica == 52) {
							?>
								<td class="tac">
									<a href="callcenter_interativo_new.php?callcenter=<?=$hd_chamado?>" target="_blank">
										<?=$hd_chamado?>
									</a>
								</td>
							<?php
							}

							if ($login_fabrica == 3) {
							?>
								<td class="tac">
									<?=$qtde_kmx?> <a href="javascript: ver(<?=$os?>);" >Ver Endereços</a>
								</td>
								<td><?=$autorizacao_domicilio?></td>
								<td><?=$status_observacao?></td>
							<?php
							} else {
                                if($login_fabrica == 30){
?>
                                <td class="tac">
                                    <?=$km_google?>
                                </td>
<?
                                }
?>
								<td class="tac">
									<input type="hidden" name="qtde_km_os_<?=$x?>" value="<?=$qtde_km?>" />
									<?php
									
                                    $qtde_kmx = str_replace(',', '.', $qtde_kmx);
									
									if (trim($aprova) == "aprovacao" || ($login_fabrica == 52 && trim($aprova) == "aprovadas")) {
									?>
										<input style="width: 60px; text-align: center;" class="input price" type="text" name="qtde_km_<?=$x?>" value="<?=number_format($qtde_kmx, 2, ',', '.')?>" />
									<?php
									} else {
                                        if ($login_fabrica == 90) {
                                            $qtde_kmx = number_format($qtde_kmx, 2, ',', '.');
                                        }
									?>
										<?=$qtde_kmx?>
									<?php
									}
									?>

									<!-- <a href="javascript: ver(<?=$os?>);" >Ver Endereços</a> -->
									<br /><br />
									<a class='btn btn-primary btn-small' rel='shadowbox' href="<?="$PHP_SELF?ver=endereco&os=$os" ?>">Endereços</a>
								</td>
							<?php
							}

							if ($login_fabrica == 142) {
							?>
								<td class="tac"><?=$qtde_diaria?></td>
							<?php
							}

							if($login_fabrica == 140){

								?>
									<?php
									if($entrega_tecnica == "t"){

										$sql_valor = "SELECT valores_adicionais FROM tbl_os WHERE os = $os";
										$res_valor = pg_query($con, $sql_valor);
										if(pg_num_rows($res_valor) > 0){
											$valor_produto = pg_fetch_result($res_valor, 0, 'valores_adicionais');
											$valor_produto = number_format($valor_produto, 2, ",", ".");

											echo  "<td>
												<input type='text' id='valor_produto_{$x}' name='valor_produto_{$x}' value='{$valor_produto}' rel='{$valor_produto}' onblur='valorProduto(\"{$x}\", \"{$os}\")' style='width: 60px;' />
												</td>";

										}else{
											echo "<td></td>";
											echo "<td></td>";
										}

										$sql_valor = "SELECT mao_de_obra_adicional, valor_total_deslocamento FROM tbl_os_extra WHERE os = $os";
										$res_valor = pg_query($con, $sql_valor);
										if(pg_num_rows($res_valor) > 0){
											$pct 		= pg_fetch_result($res_valor, 0, 'mao_de_obra_adicional');
											$valor_et 	= pg_fetch_result($res_valor, 0, 'valor_total_deslocamento');
											$valor_et 	= number_format($valor_et, 2, ",", ".");

											echo  "<td id='valor_pct_{$x}'>
													<input type='hidden' name='valor_percentual_{$x}' id='valor_percentual_{$x}' value='{$pct}'>
													{$pct} %
												</td>";
											echo  "<td>
													<input type='hidden' name='valor_calculado_{$x}' id='valor_calculado_{$x}'>
													<span id='valor_et_{$x}'>{$valor_et}</span>
												</td>";

										}else{
											echo "<td></td>";
											echo "<td></td>";
										}

									}else{
										echo "<td></td>";
										echo "<td></td>";
										echo "<td></td>";
									}
									?>
								<?php

							}

							if ($login_fabrica == 94) {
								$sqlKM = "SELECT CASE WHEN tbl_posto_fabrica.valor_km = 0 THEN
													tbl_fabrica.valor_km
												 ELSE
												 	tbl_posto_fabrica.valor_km
												 END AS valor_km
										  FROM tbl_posto_fabrica
										  JOIN tbl_fabrica USING (fabrica)
										  WHERE posto = {$posto}
										  AND fabrica = {$login_fabrica}";
								$resKM = pg_query($con, $sqlKM);

								$valor_km       = pg_fetch_result($resKM, 0, "valor_km");
								$qtde_kmx       = number_format($qtde_km, 3, ".", ",");
								$valor_km_total = $valor_km * $qtde_kmx;
								?>

								<td>
									<?=number_format($valor_km_total, 2, ",", ".")?>
								</td>
							<?php
							}

							if ($login_fabrica == 30 || $login_fabrica == 15) {
							?>
								<td class="tac">
									<?=$status_observacao?>
								</td>
							<?php
							}
							?>

							<td class="tac">
								<?=$data_digitacao?>
							</td>

							<?php if ($login_fabrica == 24) { 
								$data_abertura = explode('-',$data_abertura);
								$data_abertura = implode('/', array_reverse($data_abertura)); 
							?>
								<td class="tac">
									<?=$data_abertura?>
								</td>
							<?php } ?>
							
							<?php if (in_array($login_fabrica, [24,30,145])) { ?>
	                            <td class="tac">
	                                <?=$data_auditoria?>
	                            </td>
							<? } 

							if ($login_fabrica == 24) { ?>
	                            <td class="tac">
	                                <?=$admin_auditor?>
	                            </td>
							<?php }

							if ($login_fabrica == 145) {
							?>
								<td><?=$consumidor_nome?></td>
								<td><?=$consumidor_cpf?></td>
							<?php
							}

							if ($login_fabrica == 52 || $login_fabrica ==30) {
							?>
								<td><?=$consumidor_cidade?> - <?=$consumidor_estado?></td>
								<td><?=$contato_cidade?> - <?=$contato_estado?></td>
							<?
							}
							?>
							<td title="<?=$codigo_posto?> - <?=$posto_nome?>"  nowrap >
								<?=$codigo_posto?> - <?=substr($posto_nome, 0, 20)?>...
							</td>

							<?php

							if (in_array($login_fabrica, array(74, 15, 94))) {
							?>
								<td><?=$consumidor_cidade?></td>
								<td class="tac"><?=$consumidor_estado?></td>
							<?php
							}

							if(!in_array($login_fabrica, array(138,142,143))){
							?>

							<td nowrap>
								<acronym title="Produto: <?=$produto_referencia?> - " style="cursor: help;" >
									<?=$produto_referencia?>
								</acronym>
							</td>
							<td nowrap>
								<acronym title="Produto: <?=$produto_referencia?> - <?=$produto_descricao?>" style="cursor: help;" >
									<?=$produto_descricao?>
								</acronym>
							</td>

							<?php
							}
							if ($login_fabrica == 52) {
								if ($consumidor_marca_logo > 0 ) {
									$sqlx="select nome from tbl_marca where marca = $consumidor_marca_logo;";
									$resx=pg_exec($con,$sqlx);
									$marca_logo_nome         = pg_fetch_result($resx, 0, 'nome');
								}else{
									$marca_logo_nome = '';
								}
							?>
								<td nowrap ><?=$marca_logo_nome?></td>
								<td nowrap ><?=$defeito_constatado_grupo?></td>
								<td nowrap ><?=$defeito_constatado?></td>
							<?php
							}

							$obs_status = array();

							if ($login_fabrica == 74) {
								$sql = "SELECT observacao
										FROM tbl_os_status
										WHERE os = {$os}
										AND status_os IN (98,161,162)
										ORDER BY data DESC";
								$res_status = pg_query($con, $sql);

								for ($i = 0; $i < pg_num_rows($res_status); $i++) {
									$obs_status[] = pg_fetch_result($res_status, $i, "observacao");
								}

								$obs_status = (empty($obs_status)) ? "&nbsp;" : implode("<br />", $obs_status);
								?>
								<td style="overflow: auto;" nowrap ><?=trim($obs_status)?></td>
							<?php
							} else {
							?>
								<td class="tac" nowrap>
									<acronym title="Observação do Promotor: <?=$status_observacao?>" >
										<?=$status_descricao?>
									</acronym>
								</td>
							<?php
							}

							if (in_array($login_fabrica, array(30, 50, 74)) || $login_fabrica >= 131) {
								$sqlint = "SELECT os_interacao, admin
										   FROM tbl_os_interacao
										   WHERE os = {$os}
										   AND interno IS NOT TRUE
										   ORDER BY os_interacao DESC
										   LIMIT 1";
								$resint = pg_query($con, $sqlint);

								if (pg_num_rows($resint) == 0) {
									$botao = "<input value='Interagir' type='button' class='btn btn-primary' title='Enviar Interação com Posto'>";
								} else {
									$admin = pg_fetch_result($resint, 0, "admin");

									if (strlen($admin) > 0) {
										$botao = "<input value='Interagir' type='button' class='btn btn-primary' title='Aguardando Resposta do Posto' />";
									} else {
										$botao = "<input value='Interagir' type='button' class='btn btn-primary' title='Posto Respondeu, clique aqui para visualizar' />";
									}
								}
								?>

								<td>
									<div id="btn_interacao_<?=$x?>" style='cursor: pointer;' os='<?= $os ?>' class='btn_interacao'>
										<?=$botao?>
									</div>
								</td>
							<?php
							}
							?>
						</tr>
						<?php

						if (in_array($login_fabrica, $arr_gera_xls_aprovadas)) {
							if ($login_fabrica == 52) {
								fwrite($fp, utf8_encode( "{$sua_os};{$hd_chamado};{$qtde_kmx};{$data_digitacao};{$codigo_posto} - {$posto_nome};{$contato_cidade};{$contato_estado};{$consumidor_cidade};{$consumidor_estado};{$produto_referencia};{$produto_descricao};{$marca_logo_nome};{$defeito_constatado_grupo};{$defeito_constatado};{$status_descricao}\n") );
								// fwrite($fp, "
								// <tr>
								// 	<td>{$sua_os}</td>
								// 	<td>{$hd_chamado}</td>
								// 	<td>{$qtde_kmx}</td>
								// 	<td>{$data_digitacao}</td>
								// 	<td>{$codigo_posto} - {$posto_nome}</td>
								// 	<td>{$contato_cidade}</td>
								// 	<td>{$contato_estado}</td>
								// 	<td>{$consumidor_cidade}</td>
								// 	<td>{$consumidor_estado}</td>
								// 	<td>{$produto_referencia}</td>
								// 	<td>{$produto_descricao}</td>
								// 	<td>{$marca_logo_nome}</td>
								// 	<td>{$defeito_constatado_grupo}</td>
								// 	<td>{$defeito_constatado}</td>
								// 	<td>{$status_descricao}</td>
								// </tr>
								// ");
							}elseif($login_fabrica == 30){
								//fwrite($fp, utf8_encode( "{$sua_os};{$km_google};{$qtde_kmx};{$data_digitacao};{$data_auditoria};{$codigo_posto} - {$posto_nome};{$contato_cidade};{$contato_estado};{$consumidor_cidade};{$consumidor_estado};{$produto_referencia};{$produto_descricao};{$defeito_constatado_grupo};{$defeito_constatado};{$status_descricao}\n") );
								
        						$status_observacao = html_entity_decode($status_observacao);

        						$qtde_kmx = number_format($qtde_kmx, 2, ',', '.');

								$linha_csv = "{$sua_os};{$km_google};{$qtde_kmx};{$status_observacao};{$data_digitacao};{$data_auditoria};{$consumidor_cidade} - {$consumidor_estado};{$contato_cidade} - {$contato_estado};{$codigo_posto} - {$posto_nome};{$produto_referencia};{$produto_descricao};{$status_descricao} \n";

								fwrite($fp, utf8_encode($linha_csv));

                                // fwrite($fp, "
                                //     <tr>
                                //         <td>{$sua_os}</td>
                                //         <td>{$km_google}</td>
                                //         <td>{$qtde_kmx}</td>
                                //         <td>{$data_digitacao}</td>
                                //         <td>{$data_auditoria}</td>
                                //         <td>{$codigo_posto} - {$posto_nome}</td>
                                //         <td>{$contato_cidade}</td>
                                //         <td>{$contato_estado}</td>
                                //         <td>{$consumidor_cidade}</td>
                                //         <td>{$consumidor_estado}</td>
                                //         <td>{$produto_referencia}</td>
                                //         <td>{$produto_descricao}</td>
                                //         <td>{$defeito_constatado_grupo}</td>
                                //         <td>{$defeito_constatado}</td>
                                //         <td>{$status_descricao}</td>
                                //     </tr>
                                // ");
							}else{

								$varSuggar = " {$data_abertura}; {$data_auditoria}; {$admin_auditor}; ";

								fwrite($fp,"{$sua_os};{$qtde_kmx};{$data_digitacao};" . $varSuggar  . "{$codigo_posto} - {$posto_nome};{$contato_cidade};{$contato_estado};{$consumidor_cidade};{$consumidor_estado};{$produto_referencia};{$produto_descricao};{$defeito_constatado_grupo};{$defeito_constatado};{$status_descricao}\n");
								// fwrite($fp, "
								// 	<tr>
								// 		<td>{$sua_os}</td>
								// 		<td>{$qtde_kmx}</td>
								// 		<td>{$data_digitacao}</td>
								// 		<td>{$codigo_posto} - {$posto_nome}</td>
								// 		<td>{$contato_cidade}</td>
								// 		<td>{$contato_estado}</td>
								// 		<td>{$consumidor_cidade}</td>
								// 		<td>{$consumidor_estado}</td>
								// 		<td>{$produto_referencia}</td>
								// 		<td>{$produto_descricao}</td>
								// 		<td>{$defeito_constatado_grupo}</td>
								// 		<td>{$defeito_constatado}</td>
								// 		<td>{$status_descricao}</td>
								// 	</tr>
								// ");
							}
						}

						// if($login_fabrica == 30){
						// 	if($gerar_excel == "sim"){

						// 		$linha_csv = "{$sua_os};{$km_google};{$qtde_kmx};{$status_observacao};{$data_digitacao};{$data_auditoria};{$consumidor_cidade} - {$consumidor_estado};{$contato_cidade} - {$contato_estado};{$codigo_posto} - {$posto_nome};{$produto_referencia};{$produto_descricao};{$status_descricao} \n";

						// 		fwrite($fp, $linha_csv);
						// 	}
						// }

					}
					?>

					<!-- <input type="hidden" name="qtde_os" value="<?=$x?>" /> -->
				</tbody>
				<tfoot>
					<tr>
						<td style="height: 20px; background-color: #485989; text-align: left;" colspan="50" >
							<?php
							$enable_option =  null;

							if ($login_fabrica == 50 && $qtde_km > 120) {
								// HD 666788 - a aprovação ou reprovação das OS dependerá do cadastro de admin por funcionalidade.
								// Permissão para aprovação/reprovação apenas para usuários cadastrados para a funcionalidade: 1 - Aprovar KM acima de 120Km
								$sql = "SELECT admin
									FROM tbl_funcionalidade_admin
									WHERE funcionalidade = 1
									AND admin = {$login_admin}
									AND fabrica = {$login_fabrica}";
								$res = pg_query($con, $sql);

								if (pg_num_rows($res) > 0) {
									$enable_option = "style='display: block;'";
								} else {
									$enable_option = "style='display: none;'";
								}
							}

							if (trim($aprova) == "aprovacao" /*|| ($login_fabrica == 52 && trim($aprova) == "aprovadas")*/) {
							?>
								&nbsp;&nbsp;
								<img src="imagens/seta_checkbox.gif" align="absmiddle" /> <b style="color: #FFFFFF;" >COM MARCADOS:</b>
								<select name="select_acao" size="1" class="frm" >
									<option></option>
									<option value="99" <?=$enable_option?> >APROVADO</option>
									<option value="101" <?=$enable_option?> >RECUSADO</option>
								</select>&nbsp;&nbsp; 
								<b style="color: #FFFFFF;">Motivo:</b> <input class="frm" type="text" id="observacao" name="observacao" size="30" maxlength="250" value="" <?=($_POST["select_acao"] == "19") ? "disabled" : ""?> />
								&nbsp;&nbsp; <input value="Gravar" class="btn" type="button"  style="cursor: pointer; cursor: hand; /*margin-bottom: 10px;*/" onclick="javascript: btn_gravar();" align="absmiddle" />
							<?php
							}
							?>
						</td>
					</tr>
                    <tr>
                        <TD style="height: 20px; background-color: #485989; color: white; text-align: center;" colspan="50">
                            <label style="font-size: 15px;"><strong>TOTAL OS: <?=$total_os?></strong></label>
                        </TD>
                    </tr>
				</tfoot>
			</table>

			<?php
			if (in_array($login_fabrica, $arr_gera_xls_aprovadas)) {
				fclose($fp);
			// 	fwrite($fp, "
			// 		</tbody>
			// 		</table>
			// 	");
			}
			?>

			<input type="hidden" name="btn_acao" value="Pesquisar" />]
		</form>
		<br> <br>
		<div class="btn_excel" onclick="javascript: window.location='<?=$file?>';">		    
		    <span class="txt" style="background: #5e9c76;">Gerar Arquivo CSV</span>
		    <span><img style="width:40px ; height:40px;" src='imagens/icon_csv.png' /></span>
		</div>
		<?php

		//if($login_fabrica == 30){
			//if($gerar_excel == "sim"){
				// fclose($fp);
			//}

		//}

	} else {
	?>
		<div class="alert alert-warning"><h4>Nenhuma OS encontrada.</h4></div>
	<?php
	}

	unset($msg_erro);
}
?>

<br />
<script>
	<?php if (in_array($login_fabrica, array(30, 50, 74)) && $login_fabrica < 131) { ?>

	    var tds = $('#relatorio_os_auditoria').find(".titulo_coluna");

	    var colunas = [];

	    $(tds).find("th").each(function(){
	        if ($(this).attr("class") == "date_column") {
	            colunas.push({"sType":"date"});
	        } else {
	            colunas.push(null);
	        }
	    });
    	$.dataTableLoad({ table: "#relatorio_os_auditoria",aoColumns:colunas });
    <?php } ?>
</script>	
<?php
include "rodape.php";
?>
