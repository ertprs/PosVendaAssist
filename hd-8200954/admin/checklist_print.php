<?php
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'funcoes.php';

if ($areaAdmin === true) {
    include 'autentica_admin.php';
    include_once('../plugins/fileuploader/TdocsMirror.php');
} else {
    include 'autentica_usuario.php';
    include_once('plugins/fileuploader/TdocsMirror.php');
}

$tdocsMirror      = new TdocsMirror();

if (isset($_POST['parecer_tecnico'])) {

	$visita_posto = $_POST['visita_posto'];
	$posto        = $_POST['posto'];
	$data         = formata_data($_POST['data']);

	$dados_formulario = json_encode($_POST['parecer_tecnico']);

	$sqlVisita = "INSERT INTO tbl_visita_posto (
						visita_posto, 
						posto, 
						fabrica, 
						data, 
						admin, 
						parecer_tecnico) 
				  VALUES (
				  		{$visita_posto},
				  		{$posto},
				  		{$login_fabrica},
				  		'{$data}', 
				  		{$login_admin}, 
				  		'{$dados_formulario}'
				  )";
	pg_query($con, $sqlVisita);

	if (pg_last_error()) {
		exit(json_encode(['erro' => true]));
	}
	
	exit(json_encode(['erro' => false]));
}

$id_posto = $_POST['posto'];

if (isset($_POST['codigo_posto']) && empty($id_posto)) {
	$sqlPosto = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '{$_POST['codigo_posto']}' AND fabrica = $login_fabrica ";
	$resPosto = pg_query($con, $sqlPosto);
	if (pg_num_rows($resPosto) > 0) {
		$id_posto = pg_fetch_result($resPosto, 0, "posto");
	}
}
if (empty($id_posto) && empty($_GET['visita_posto'])) {
	header("location: check_list_visita.php");
}

if (empty($_GET['visita_posto'])) {

	//reservando um ID no banco de dados
	$sql_reserva_id = "SELECT (last_value + 1) AS id FROM tbl_visita_posto_visita_posto_seq";
	$res_reserva_id = pg_query($con, $sql_reserva_id);

	$visita_posto   = pg_fetch_result($res_reserva_id, 0, 'id');
	$nova_sequencia = $visita_posto + 1;

	pg_query($con, "ALTER SEQUENCE tbl_visita_posto_visita_posto_seq restart {$nova_sequencia}");

} else {

	$visita_posto = $_GET['visita_posto'];

	$sqlVisita = "SELECT posto, parecer_tecnico, TO_CHAR(data, 'yyyy/mm/dd') as data_visita
				  FROM tbl_visita_posto WHERE visita_posto = {$visita_posto}";
	$resVisita = pg_query($con, $sqlVisita);

	$id_posto 		  = pg_fetch_result($resVisita, 0, 'posto');
	$dados_checklist  = pg_fetch_result($resVisita, 0, 'parecer_tecnico');
	$data  			  = pg_fetch_result($resVisita, 0, 'data_visita');

}

$dados_posto = "SELECT tbl_posto_fabrica.codigo_posto,
					   tbl_posto.nome,
					   tbl_posto_fabrica.contato_nome,
					   tbl_posto_fabrica.contato_email,
					   tbl_posto_fabrica.nome_fantasia,
					   tbl_posto.cidade,
					   tbl_posto.estado,
					   tbl_posto_fabrica.contato_fone_comercial
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = {$id_posto}
				AND tbl_posto_fabrica.fabrica = {$login_fabrica}
				WHERE tbl_posto.posto = {$id_posto}";
$res_dados = pg_query($dados_posto);

$codigo_posto  = pg_fetch_result($res_dados, 0, 'codigo_posto');
$nome_posto    = pg_fetch_result($res_dados, 0, 'nome');
$nome_fantasia = pg_fetch_result($res_dados, 0, 'nome_fantasia');
$cidade        = pg_fetch_result($res_dados, 0, 'cidade');
$estado        = pg_fetch_result($res_dados, 0, 'estado');
$fone_comercial= pg_fetch_result($res_dados, 0, 'contato_fone_comercial');
$contato_nome  = pg_fetch_result($res_dados, 0, 'contato_nome');
$email         = pg_fetch_result($res_dados, 0, 'contato_email');
?>
<html>
	<head>

		<title>Checklist Visita</title>

		<meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
		<meta http-equiv="Expires"       content="0">
		<meta http-equiv="Pragma"        content="no-cache, public">
		<meta http-equiv="Cache-control" content="no-cache, public, must-revalidate, post-check=0, pre-check=0">
		<meta name      ="Author"        content="Telecontrol Networking Ltda">
		<meta name      ="Generator"     content="na mão...">
		<meta name      ="Description"   content="Sistema de gerenciamento para Postos de Assistência Técnica e Fabricantes.">
		<meta name      ="KeyWords"      content="Assistência Técnica, Postos, Manutenção, Internet, Webdesign, Orçamento, Comercial, Jóias, Callcenter">
		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
		<script src='plugins/jquery.mask.js'></script>
		<script src='plugins/shadowbox_lupa/shadowbox.js'></script><link rel='stylesheet' type='text/css' href='plugins/shadowbox_lupa/shadowbox.css' />

		<script>


			$(function(){

				Shadowbox.init();

				$("#data").mask("99/99/9999");

				if ($("#dados_checklist").length) {

						var dados_checklist = JSON.parse($("#dados_checklist").val());

						$("input[type=checkbox], input[type=radio]").prop("disabled", true);
						
						$.each( dados_checklist, function( type, campos ) {
						  
							if ($.inArray(type, ['text','number', 'textarea']) !== -1) {

								$.each( campos, function( campo, valor ) {

									if (campo == 'concorrencia') {

										$.each( campos.concorrencia, function( campo_vl, valor_vl ) {

											adicionar_linha();

											let concorrencia = $(".concorrencia:last");

											$(concorrencia).show();

											$(concorrencia).find('.vl_marca').append('<strong>' + valor_vl.vl_marca + '</strong>');
											$(concorrencia).find('.vl_compra').append('<strong>' + valor_vl.vl_compra + '</strong>');
											$(concorrencia).find('.vl_venda').append('<strong>' + valor_vl.vl_venda + '</strong>');

										});

									} else if (campo == 'outros') {
										$("#outros_input").html(' <strong>' + valor + '</strong>');
									} else {
										let text = $("#"+campo).parent("td").text();
										
										$("#"+campo).parent("td").html(text + '<strong>' + valor + '</strong>');
									}

								});

							}

							if ($.inArray(type, ['checkbox']) !== -1) {

								$.each( campos, function( campo, valor ) {

									$("input[name="+campo+"]").filter(function(){
										return valor == 'true';
									}).prop("checked", true);

								});

							}

							if ($.inArray(type, ['radio']) !== -1) {

								$.each( campos, function( campo, valor ) {

									$('input[name='+campo+']').filter(function(){
										return $(this).val() == valor;
									}).prop('checked', true);

								});

							}

						});

						if ($("#exibicao").val() != "true") {

							$("td").css({
								'font-size' : '10px',
								'padding' : '3px'
							});

							$(".area").css({
								'margin' : '1%',
								'width' : '98%'
							});
						
							window.print();
						}

				}

				$("#btn-grava-checklist").click(function(){

					var parecer_tecnico = {
							text     : {
								concorrencia : {}
							},
							checkbox : {},
							radio    : {},
							number   : {},
							textarea : {
								observacoes : $("textarea[name=observacoes]").val()
							}
					};

					$("input").filter(function(){
						return $(this).attr("type") != 'hidden' && !$(this).hasClass("vl");
					}).each(function(){

						var type  = $(this).attr("type");
						var campo = $(this).attr("name");
						var valor = $(this).val();

						if (type == "checkbox") {
							valor = ($(this).is(":checked")) ? true : false;
						} else if (type == "radio") {
							valor = $("input[name="+campo+"]:checked").val();
						}

						if(type != 'file') {
							parecer_tecnico[type][campo] = valor;
						}

					});

					$(".concorrencia").each(function(){

						let linha    = $(this).attr("linha");

						var marca    = $(this).find(".vl-marca").val();
						var compra   = $(this).find(".vl-compra").val();
						var venda    = $(this).find(".vl-venda").val();

						parecer_tecnico['text']['concorrencia'][linha] =  {
							vl_marca : marca, 
							vl_compra : compra, 
							vl_venda : venda
						};

					});

					var posto        = $("#posto").val();
					var visita_posto = $("#visita_posto").val();
					var data         = $("#data").val();

					$.ajax({
                        url: window.location,
                        type: "POST",
                        data: {
                        	posto           : posto,
                        	visita_posto    : visita_posto,
                        	data            : data,
                        	parecer_tecnico : parecer_tecnico
                        },
                        timeout: 7000
                    }).fail(function(){
                        alert("Erro ao gravar checklist!");
                    }).done(function(data){

                         let result = JSON.parse(data);

                         if (!result.erro) {

                         	window.location = 'checklist_print.php?visita_posto='+visita_posto;

                         } else {

                         	alert("Erro ao gravar checklist!");

                         }

                    });

				});

				$("#btn-linha").click(function(){

					adicionar_linha();

				});

				$(document).on("click", ".btn-remove-linha", function(){

					$(this).closest(".concorrencia").remove();

				});

			});

			function adicionar_linha() {
				var linha_concorrencia = $(".concorrencia:last").clone();

				$(linha_concorrencia).find("input").val("");
				$(linha_concorrencia).find(".btn-remove-linha").show();

				var posicao = parseInt($(linha_concorrencia).attr("linha"));
				var posicao = posicao + 1;

				$(linha_concorrencia).attr("linha", posicao);

				var rowspan =  parseInt($("#label_concorrencia").attr("rowspan"));
				var rowspan = rowspan + 1;

				$("#label_concorrencia").attr("rowspan", rowspan);

				$(".concorrencia:last").after(linha_concorrencia);
			}

		</script>

		<style>

			.float-img {
				float: left;
				margin: 40px;
			}

			@media print {

				@page {
	                size: A4;
	                margin: 5mm;
	            }

				.float-img {
					float: left;
					width: 40%;
				}
				.floatr-img {
					float: right;
					width: 40%;
				}

				table#tabela_principal { 
	                page-break-inside: auto; 
	            }
	            
	            table#tabela_principal > tbody > tr { 
	                page-break-inside: avoid; 
	                page-break-after: auto; 
	            }
	            
	            table#tabela_principal > thead {
	                display: table-header-group; 
	            }
	            
	            table#tabela_principal > tfoot { 
	                display: table-footer-group; 
	                padding-top: -20px;
	            }

	            #cabecalho {
					background-color: white !important;
        			-webkit-print-color-adjust: exact; 
					z-index: 999;
				}

				#tabela_principal {
					z-index: 99;
				}

				#btn-sair-checklist{
					visibility: hidden;
				}

			}

			.concorrencia > td {
				border: none;
			}

			.concorrencia:last-child > td {
				border-bottom: 1px solid black;
			}

			#div_anexos > .titulo_tabela {
				display: none;
			}

			#div_anexos {
				background-color: white !important;
			}

			.area {
				margin: 2%;
				width: 96%;
				border: 1px solid black;
			}

			tr > td:last-child {
				border-right: 1px solid black;
			}

			body {
				font-family: sans-serif;
			}

			table {
				border-collapse: collapse;
			}

			td {
				padding: 5px;
				border-bottom: solid 1px black;
			}

			.titulo {
				background-color: lightgray;
				font-weight: bolder;
				text-align: center;
				font-family: Arial;
			}

			.rowspan > td {
				border: none;
			}

			.border_top {
				border-top: none !important;
			}

			.nova_linha {
				text-align: center;
			}

			.vl {
				width: 160px;
			}

			#data {
				width: 100px;
			}

			#colaborador {
				width: 170px;
			}

			#regiao_repre {
				width: 200PX;
			}

			#outros {
				width: 200px;
			}

			#media_bombas, #volume_mensal {
				width: 60px;
			}


			#btn-sair-checklist {
				background-color: #d90000;
				color: white;
				width: 200px;
				height: 40px;
				border-radius: 5px;
				border: solid 1px #ff0000;
				cursor: pointer;
			}

			#btn-sair-checklist:hover {
				background-color: #ff0000;
			}

			#btn-linha, #btn-grava-checklist {
				background-color: #0052cc;
				color: white;
				width: 200px;
				height: 40px;
				border-radius: 5px;
				border: solid 1px darkblue;
				cursor: pointer;
			}

			.btn-remove-linha {
				background-color: darkred;
				color: white;
				width: 35px;
				height: 30px;
				border-radius: 5px;
				border: solid 1px darkred;
				cursor: pointer;
				margin-left: 20px;
			}

			#btn-linha:hover, #btn-grava-checklist:hover {
				background-color: darkblue;
			}

			#apto {
				width: 50%;
			}

			#logo {
				text-align: center;
				width: 20%;
			}

			#logo > img {
				max-height:70px;
				max-width:270px;
			}
			
			#titulo_pagina {
				font-size: 11px;
				text-align: center;
				width: 60%;
			}

			#titulo_pagina > h1 {
				font-size: 14px;
				text-align: center;
			}

			#info {
				text-align: center;
				width: 20%;
			}

			#info > h3 {
				font-size: 14px;
				text-align: center;
			}

			#header > td {
				border: solid 1px black;
			}

			#observacoes {
				width: 75%;
				margin-left: 12.5%;
			}

			#label_concorrencia {
				border-bottom: solid 1px black;
			}

			.linha_bombas {
				border-bottom: solid 1px black;
			}

			#tabela_principal {
				position: relative;
				margin-left: 2.5%;
				width: 95%;
			}

		</style>
	</head>
	<body>

		<?php
		if (!empty($_GET['visita_posto'])) { ?>
			<input type="hidden" name="dados_checklist" id="dados_checklist" value='<?= $dados_checklist ?>' />
		<?php
		} ?>

		<input type="hidden" name="exibicao" id="exibicao" value="<?= $_GET['exibicao'] ?>" />
		<input type="hidden" name="visita_posto" id="visita_posto" value="<?= $visita_posto ?>" />
		<input type="hidden" name="posto" id="posto" value="<?= $id_posto ?>" />

		<table id="tabela_principal">
			<thead>
				<tr>
					<th>
						<table style="width: 98%;margin-left: 1%;" id="cabecalho">
							<tr id="header">
								<td id="logo"><img src="../logos/logo_anauger.png" /></td>
								<td id="titulo_pagina"><h1>CHECK LIST VISITA / TREINAMENTO - PSA</h1>Formulário - FQ-134</td>
								<td id="info"><h3>Área de Utilização:</h3> Assistência Tecnica</td>
							</tr>
						</table>
					</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<th>
						<table class="area">
							<tr>
								<td>Check List Nº: <strong><?= $visita_posto ?></strong></td>
								<td>Data: <input type="text" name="data" id="data" value="<?= date("d/m/Y") ?>" /></td>
								<td>Colaborador: <input type="text" name="colaborador" id="colaborador" /></td>
								<td>Região / Repre: <input type="text" name="regiao_repre" id="regiao_repre" /></td>
								<td></td>
							</tr>
							<tr>
								<td class="titulo" colspan="100%">DADOS DO POSTO DE SERVIÇO ANAUGER</td>
							</tr>
							<tr>
								<td>Código: <strong><?= $codigo_posto ?></strong></td>
								<td colspan="2">Razão Social: <strong><?= $nome_posto ?></strong></td>
								<td>Cidade: <strong><?= $cidade ?></strong></td>
								<td></td>
							</tr>
							<tr>
								<td>UF: <strong><?= $estado ?></strong></td>
								<td colspan="2">Nome Fantasia: <strong><?= $nome_fantasia ?></strong></td>
								<td>Contato: <input type="text" name="contato" id="contato" value="<?= $contato_nome; ?>" /></td>
								<td></td>
							</tr>
							<tr>
								<td>Telefone: <strong><?= $fone_comercial ?></strong></td>
								<td colspan="2">Email: <input type="text" name="email" value="<?= $email; ?>" id="email" /></td>
								<td>Ramo Atividade: <input type="text" name="ramo_atividade" id="ramo_atividade" /></td>
								<td></td>
							</tr>
							<tr>
								<td>Possui Propaganda Anauger:</td>
								<td colspan="2">
									<label>
										<input type="radio" name="propaganda_anauger" value="t" /> Sim ( Fotografar )
									</label>
								</td>
								<td colspan="2">
									<label>
										<input type="radio" name="propaganda_anauger" value="f" /> Não 
									</label>
								</td>
							</tr>
							<tr>
								<td>Possui Propaganda Concorrência:</td>
								<td colspan="2">
									<label>
										<input type="radio" name="propaganda_concorrencia" value="t" /> Sim ( Fotografar )
									</label>
								</td>
								<td colspan="2">
									<label>
										<input type="radio" name="propaganda_concorrencia" value="f" /> Não 
									</label>
								</td>
							</tr>
							<tr>
								<td>Possui KIT Rotametro:</td>
								<td colspan="2">
									<label>
										<input type="radio" name="kit_rotametro" value="t" /> Sim 
									</label>
								</td>
								<td colspan="2">
									<label>
										<input type="radio" name="kit_rotametro" value="f" /> Não 
									</label>
								</td>
							</tr>
							<tr>
								<td>Possui Ferramentas da 4"H60:</td>
								<td colspan="2">
									<label>
										<input type="radio" name="ferramentas_h60" value="t" /> Sim 
									</label>
								</td>
								<td colspan="2"><input type="radio" name="ferramentas_h60" value="f" /> Não </td>
							</tr>
							<tr>
								<td>Possui Peças da 4"H60:</td>
								<td colspan="2">
									<label>
										<input type="radio" name="pecas_h60" value="t" /> Sim
									</label>
								</td>
								<td colspan="2">
									<label>
										<input type="radio" name="pecas_h60" value="f" /> Não 
									</label>
								</td>
							</tr>
							<tr>
								<td>Possui Peças da 5ª Geração:</td>
								<td colspan="2">
									<label>
										<input type="radio" name="pecas_5_geracao" value="t" /> Sim 
									</label>
								</td>
								<td colspan="2">
									<label>
										<input type="radio" name="pecas_5_geracao" value="f" /> Não 
									</label>
								</td>
							</tr>
							<tr>
								<td rowspan="3">Atende Quais Modelos de Bombas:</td>
							</tr>
							<tr class="rowspan">
								<td>
									<label>
										<input type="checkbox" name="vibratorias" value="t" /> Vibratórias 
									</label>	
								</td>
								<td>
									<label>
										<input type="checkbox" name="h60" value="t" /> 4"H60 
									</label>
								</td>
								<td>
									<label>
										<input type="checkbox" name="moto_bombas" /> Moto Bombas
									</label>
								</td>
								<td>
									<label>
										<input type="checkbox" name="ags" /> AGS 
									</label>
								</td>
							</tr>
							<tr class="rowspan linha_bombas">
								<td>
									<label>
										<input type="checkbox" name="solar_bomba" /> Solar (Bomba) 
									</label>
								</td>
								<td>
									<label>
										<input type="checkbox" name="linha_6" /> 6" 
									</label>
								</td>
								<td>
									<label>
										<input type="checkbox" name="solar_driver" /> Solar (Driver)
									</label>
								</td>
								<td></td>
							</tr>
							<tr>
								<td rowspan="2">Quais as Tensões da Região:</td>
							</tr>
							<tr class="rowspan">
								<td>
									<label>
										<input type="checkbox" name="110v" /> 110 V
									</label>
								</td>
								<td>
									<label>
										<input type="checkbox" name="220v" /> 220 V
									</label>
								</td>
								<td>
									<label>
										<input type="checkbox" name="127v" /> 127 V
									</label>
								</td>
								<td>
									<label>
										<input type="checkbox" name="254v" /> 254 V
									</label>
								</td>
							</tr>
						</table>
						<table class="area">
							<tr>
								<td class="titulo" colspan="100%">TREINAMENTO</td>
							</tr>
							<tr>
								<td>Possui KIT Treinamento a Distância:</td>
								<td colspan="2">
									<label>
										<input type="radio" name="possui_kit_treinamento" value="t" /> 
										Sim
									</label>
								</td>
								<td colspan="2">
									<label>
										<input type="radio" name="possui_kit_treinamento" value="f" /> Não 
									</label>
								</td>
							</tr>
							<tr>
								<td>Foi Realizado Treinamento no PSA:</td>
								<td colspan="2">
									<label>
										<input type="radio" name="treinamento_no_psa" value="t" /> 
										Sim ( Fotografar )
									</label>
								</td>
								<td colspan="2">
									<label>
										<input type="radio" name="treinamento_no_psa" value="f" /> Não
									</label>
								</td>
							</tr>
							<tr>
								<td rowspan="5">Qual Treinamento Foi Realizado:</td>
							</tr>
							<tr class="rowspan">
								<td>
									<label>
										<input type="checkbox" name="dimensionamento" /> Dimensionamento
									</label>
								</td>
								<td>
									<label>
										<input type="checkbox" name="tecnico" /> Técnico 
									</label>
								</td>
								<td>
									<label>
										<input type="checkbox" name="quadro_exija" /> Quadro "Exija sua Garantia"
									</label>
								</td>
							</tr>
							<tr class="rowspan">
								<td>
									<label>
										<input type="checkbox" name="burocratico" /> Burocrático
									</label>
								</td>
								<td>
									<label>
										<input type="checkbox" name="kit_rotametro" /> Kit Rotamento 
									</label>
								</td>
								<td>
									<label>
										<input type="checkbox" name="aplicacao" /> Aplicações 
									</label>
								</td>
							</tr>
							<tr>
								<td colspan="2">
									<label>
										<input type="checkbox" name="outros_check" /> Outros:
										<span id="outros_input">
											<input type="text" name="outros" id="outros" />
										</span>
									</label>
								</td>
								<td colspan="2"></td>
							</tr>
						</table>
						<table class="area">
							<tr>
								<td class="titulo" colspan="100%">INFORMAÇÕES COMERCIAIS</td>
							</tr>
							<tr>
								<td>Revende Bomba Vibratória:</td>
								<td colspan="2">
									<label>
										<input type="radio" name="revende_bomba" value="t" /> 
										Sim
									</label>
								</td>
								<td>
									<label>
										<input type="radio" name="revende_bomba" value="f" /> Não
									</label>
								</td>
								<td class="border_top">Media mensal de Bombas : <input type="number" name="media_bombas" id="media_bombas" /></td>
							</tr>
							<tr>
								<td>Revende Peças e Serviços:</td>
								<td colspan="2">
									<label>
										<input type="radio" name="revende_pecas" value="t" /> 
										Sim
									</label>
								</td>
								<td>
									<label>
										<input type="radio" name="revende_pecas" value="f" /> Não
									</label>
								</td>
								<td class="border_top">Volume Mensal de Conserto : <input type="number" name="volume_mensal" id="volume_mensal" /></td>
							</tr>
							<tr>
								<td colspan="2" rowspan="2" id="label_concorrencia">Concorrência: </td>
							</tr>
							<?php
							if (empty($_GET['visita_posto'])) {
							?>
								<tr class="concorrencia" linha="0">
									<td>
										Marca:
										<input type="text" name="marca" class="vl vl-marca" />
									</td>
									<td>
										VL Compra:
										<input type="text" name="vl_compra" class="vl vl-compra" />
									</td>
									<td>
										VL Venda:
										<input type="text" name="vl_venda" class="vl vl-venda" />
										<button class="btn-remove-linha" style="display: none;">
											X
										</button>
									</td>
								</tr>
								<tr>
									<td colspan="5" class="border_top nova_linha">
										<button id="btn-linha">
											Adicionar nova Linha
										</button>
									</td>
								</tr>
							<?php
							} else { ?>
								<tr class="concorrencia" linha="0" style="display: none;">
									<td class="vl_marca">
										Marca:
									</td>
									<td class="vl_compra">
										VL Compra:
									</td>
									<td class="vl_venda">
										VL Venda:
									</td>
								</tr>
							<?php
							}
							?>
						</table>
						<table class="area">
							<tr>
								<td class="titulo" colspan="100%">PSA PRESTA SERVIÇO TÉCNICO PARA OUTRAS MARCAS</td>
							</tr>
							<tr class="rowspan">
								<td>
									<label>
										<input type="checkbox" name="schneider" /> Schneider
									</label>
								</td>
								<td>
									<label>
										<input type="checkbox" name="weg" /> Weg
									</label>
								</td>
								<td>
									<label>
										<input type="checkbox" name="stihl" /> Stihl
									</label>
								</td>
								<td>
									<label>
										<input type="checkbox" name="wap" /> Wap
									</label>
								</td>
								<td>
									<label>
										<input type="checkbox" name="dancor" /> Dancor
									</label>
								</td>
							</tr>
							<tr class="rowspan">
								<td>
									<label>
										<input type="checkbox" name="ebara" /> Ebara
									</label>
								</td>
								<td>
									<label>
										<input type="checkbox" name="bosch" /> Bosch
									</label>
								</td>
								<td>
									<label>
										<input type="checkbox" name="husqvarna" /> Husqvarna
									</label>
								</td>
								<td>
									<label>
										<input type="checkbox" name="karcher" /> Karcher
									</label>
								</td>
								<td>
									<label>
										<input type="checkbox" name="trapp" /> Trapp
									</label>
								</td>
							</tr>
							<tr class="rowspan">
								<td>
									<label>
										<input type="checkbox" name="thebe" /> Thebe
									</label>
								</td>
								<td>
									<label>
										<input type="checkbox" name="ksb" /> KSB 
									</label>
								</td>
								<td>
									<label>
										<input type="checkbox" name="jacto" /> Jacto
									</label>
								</td>
								<td>
									<label>
										<input type="checkbox" name="bombas_leao" /> Bombas Leão
									</label>
								</td>
								<td></td>
							</tr>
						</table>
						<table class="area">
							<tr>
								<td class="titulo" colspan="100%">ENCONTRADO NO PSA</td>
							</tr>
							<tr class="rowspan">
								<td>
									<label>
										<input type="checkbox" name="morsa" /> Morsa
									</label>
								</td>
								<td>
									<label>
										<input type="checkbox" name="bancada_vazao" /> Bancada Teste Vazão
									</label>
								</td>
								<td>
									<label>
										<input type="checkbox" name="certificado_anauger" /> Certificado Anauger
									</label>
								</td>
								<td>
									<label>
										<input type="checkbox" name="psa_exija_garantia" /> Quadro "Exija sua Garantia"
									</label>
								</td>
							</tr>
							<tr class="rowspan">
								<td>
									<label>
										<input type="checkbox" name="ferramentas_basicas" /> Ferramentas Básicas
									</label>
								</td>
								<td>
									<label>
										<input type="checkbox" name="bancada_eletrico" /> Bancada Teste Elétrico
									</label>
								</td>
								<td>
									<label>
										<input type="checkbox" name="multimetro" /> Multímetro
									</label>
								</td>
								<td></td>
							</tr>
						</table>
						<table class="area">
							<tr>
								<td class="titulo" colspan="100%">PARECER DA VISTA</td>
							</tr>
							
							<tr class="rowspan">
								<td id="apto">Apto a Atender dentro e fora da garantia:</td>
								<td>
									<label>
										<input type="radio" name="apto_garantia" value="t" /> 
										Sim
									</label>
								</td>
								<td>
									<label>
										<input type="radio" name="apto_garantia" value="f" /> Não
									</label>
								</td>
							</tr>
						</table>
						<table class="area">
							<tr>
								<td class="titulo" colspan="100%">OBSERVAÇÕES</td>
							</tr>
							<tr>
								<td style="text-align: center;">
									<textarea rows="3" id="observacoes" name="observacoes"></textarea>
								</td>
							</tr>
						</table>
						<?php
						if (!empty($_GET['visita_posto'])) {
						?>
						<table class="area">
						<tr>
							<td class="titulo" colspan="100%">IMAGENS</td>
						</tr>

						<?php
							$sqlUniqueId = "SELECT tdocs_id FROM tbl_tdocs WHERE referencia = 'checklist_visita' and referencia_id = {$visita_posto}";
							$resUniqueId = pg_query($con, $sqlUniqueId);
							$contador = 0;
							while ($result = pg_fetch_object($resUniqueId)) {

								$info = $tdocsMirror->get($result->tdocs_id); 
								 
								if ($contador == 0 || ($contador % 2) == 0) { $fim = false;?>
							
									<tr>
										<td>
											<img src="<?= $info['link'] ?>" class="<?php echo 'float-img'; ?>" />
										</td>
							
						<?php } else { $fim = true; ?>
										
										<td>
											<img src="<?= $info['link'] ?>" class="<?php echo 'float-img'; ?>" />
										</td>
									</tr>
					 	<?php } 
								$contador++;  
							} 
							
							if (!$fim) echo "</tr>";
						?>

						</table>
						<?php } ?>
					</th>
				</tr>
			</tbody>
		</table>

		<?php

		if (empty($_GET['visita_posto'])) {

			$tempUniqueId = $visita_posto;
	        $boxUploader = array(
	            "div_id" => "div_anexos",
	            "prepend" => $anexo_prepend,
	            "context" => "checklist_visita",
	            "unique_id" => $tempUniqueId,
	            "hash_temp" => $anexoNoHash,
	            "bootstrap" => false,
	            "hidden_button" => false
	        );
	        include "../box_uploader.php";


		?>
			<center>
				<button type="button" onclick="window.location.href='check_list_visita.php'" id="btn-sair-checklist">Sair Checklist</button>
				<button id="btn-grava-checklist">Finalizar Checklist</button>
			</center>
			<br />
		<?php

		} else {

			$linkVoltar = ($_GET['exibicao'] == "true") ? "relatorio_checklist_visita.php" : "check_list_visita.php";

			?>
			<center>
				<button type="button" onclick="window.location.href='<?= $linkVoltar ?>'" id="btn-sair-checklist">Sair Checklist</button>
			</center>
		<?php } 
	    for ($i = 1; $i <=  1; $i++) { ?>
	        <form name="form_anexo" method="post" action="checklist_print.php" enctype="multipart/form-data" style="display: none !important;" >
	            <input type="file" name="anexo_upload_<?=$i?>" value="" />
	            <input type="hidden" name="ajax_anexo_upload" value="t" />
	            <input type="hidden" name="anexo_posicao" value="<?=$i?>" />
	            <input type="hidden" name="anexo_chave" value="<?=$anexo_chave?>" />
	        </form>
	    <?php 
		}
		?>
	</body>
</html>
