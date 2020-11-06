<?php

$admin_privilegios = 'auditoria';

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

if (isset($_POST['btn_acao']) and $_POST['btn_acao'] == 'pesquisar') {
	$data_inicial       = $_POST['data_inicial'];
	$data_final         = $_POST['data_final'];
	$posto_cnpj         = (isset($_POST['posto_cnpj']))    ? trim($_POST['posto_cnpj'])    : '';
	$posto_nome         = (isset($_POST['posto_nome']))    ? trim($_POST['posto_nome'])    : '';
	$validados          = (isset($_POST['validados']))     ? trim($_POST['validados'])     : '';
	$nao_validados      = (isset($_POST['nao_validados'])) ? trim($_POST['nao_validados']) : '';
	$nao_enviados       = (isset($_POST['nao_enviados']))  ? trim($_POST['nao_enviados'])  : '';
	$enviados           = (isset($_POST['enviados']))      ? trim($_POST['enviados'])      : '';
	$preencheu_fabricas = trim($_POST['preencheu_fabricas']);

	if (!empty($validados)) {
		$sql_cond[] = " AND tbl_posto_alteracao.validado IS TRUE ";
	}

	if (!empty($nao_validados)) {
		$sql_cond[] = " AND tbl_posto_alteracao.validado IS FALSE ";
	}

	if (!empty($nao_enviados)) {
		$sql_cond[] = " AND tbl_posto_alteracao.banner_enviado IS NULL";
	}

	if (!empty($enviados)){
		$sql_cond[] = " AND tbl_posto_alteracao.banner_enviado IS NOT NULL";
	}

	if (!empty($posto_cnpj)) {
		$sql_cond[] = " AND tbl_posto_alteracao.cnpj = '$posto_cnpj' ";//"AND UPPER(tbl_posto_alteracao.nome_fantasia) = UPPER('$posto_nome')";
	}

	if ($preencheu_fabricas != '') {
		$sql_cond7 = ($preencheu_fabricas == 'f') ? " AND NOT" : " AND" ;
		$sql_cond7.= " (LENGTH(marca_ser_autorizada) > 3 AND LENGTH(outras_fabricas)>3) /* Preencheu campos obrigatórios */";
		$sql_cond[] = $sql_cond7;
	}

	if (!empty($data_inicial) and !empty($data_final)) {
		$aux_data_inicial = dateFormat($data_inicial, 'dmy', 'iso');
		$aux_data_final   = dateFormat($data_final,   'dmy', 'iso');
		$sql_cond[] = " AND tbl_posto_alteracao.data_input between '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59' ";
	}

}

$title = 'Relatório de Pedidos de Banner';

include 'cabecalho.php';
?>
<link type="text/css" rel="stylesheet" href="js/jquery.autocomplete.css" />
<link type="text/css" rel="stylesheet" href="../plugins/jquery/datepick/telecontrol.datepick.css" />
<style type="text/css">
	.menu_top {
		text-align: center;
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: 9px;
		font-weight: bold;
		border: 1px solid;
		color:#ffffff;
		background-color: #596D9B;
	}

	.border {
		border: 1px solid #ced7e7;
	}

	.table_line {
		text-align: left;
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: 9px;
		font-weight: normal;
		border: 0px solid;
		background-color: #D9E2EF;
	}

	.table_line2 {
		text-align: left;
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: 9px;
		font-weight: normal;
	}

	.titulo_tabela{
		background-color:#596d9b;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}

	.titulo_coluna{
		background-color:#596d9b;
		font: bold 11px "Arial";
		color:#FFFFFF;
		text-align:center;
	}

	.msg_erro{
		background-color:#FF0000;
		font: bold 16px "Arial";
		color:#FFFFFF;
		text-align:center;
		margin:auto;
		width:700px;
	}

	.formulario{
		background-color:#D9E2EF;
		font:11px Arial;
		text-align:left;	
	}
	.formulario_detalhes{
		background-color:#D9E2EF;
		font:11px Arial;
		text-align:left;	
		margin-top: 1em;
		margin-bottom: 1em;
	}

	.formulario_detalhes tr td p.subtitulo+p {
		width: 95%;
		margin: auto;
	}

	.formulario_detalhes tr td ul {
		column-count: 3;
		-o-column-count: 3;
		-ms-column-count: 3;
		-moz-column-count: 3;
		-webkit-column-count: 3;
	}

	.formulario_detalhes tr td{
		font-family: verdana;
		font-size: 11px;
		border-collapse: collapse;
		border:0px solid !important;
	}

	.subtitulo{

		background-color: #7092BE;
		font:bold 11px Arial;
		color: #FFFFFF;
	}

	table.tabela tr td{
		font-family: verdana;
		font-size: 11px;
		border-collapse: collapse;
		border:1px solid #596d9b;
	}

	.main-content, .main-content-search-results{
		width:700px;
		max-width:700px;
		margin:auto;
		max-width:700px;
	}

</style>

<script type="text/javascript" src="js/jquery-1.8.3.min.js"></script>
<script type="text/javascript" src="js/jquery.mask.js"></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<script type="text/javascript">
	
	function formatItem(row) {
		return row[0] + " - " + row[1];
	}

	function validarDados(posto_alteracao,posto){
		var getData = "&action=validarDadosPosto&posto="+posto+"&posto_alteracao="+posto_alteracao+"&"+$('#table_'+posto_alteracao).find('input').serialize()+'&'+$('#table_'+posto_alteracao).find('textarea').serialize();
		$.get("relatorio_banner_ajax.php", getData,
		function(data){

			var result = data.split('|');
			if (result[0] == 0) {
				$("#table_"+posto_alteracao).find('input').attr('disabled', true);
				$("#table_"+posto_alteracao).find('textarea').attr('disabled', true);
				$("#setSend_"+posto_alteracao).show();
				$("#imgAtivo_"+posto_alteracao).show();
				$("#imgInativo_"+posto_alteracao).hide();
				$("#validado_"+posto_alteracao).hide();
				return true;
			}else{
				return "Erro|"+result[1];
			}

		});
	}

	function marcarEnviado(posto_alteracao){
		var getData = "&action=marcaEnviado&posto_alteracao="+posto_alteracao;
		$.get("relatorio_banner_ajax.php", getData,
		function(data){

			var result = data.split('|');
			if (result[0] == 0) {
				$("#enviado_"+posto_alteracao).html(result[2]);
				$("#setSend_"+posto_alteracao).hide();
				return true;
			}else{
				return "Erro|"+result[1];
			}

		});
	}

	$(function() {

		$('#data_inicial').datepick({startDate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$("#data_inicial").mask("99/99/9999");
		$("#data_final").mask("99/99/9999");
		$(".fone").mask("(99)9999-9999");
		$(".cnpj").mask("99.999.999/9999-99");
		$(".cep").mask("99.999-999");

		$('.scrollup').click(function(){
			$("html, body").animate({ scrollTop: 0 }, 600);
			return false;
		});

		$(window).scroll(function(){
			if ($(this).scrollTop() > 200) {
				$('.scrollup').slideDown('slow');
			} else {
				$('.scrollup').slideUp('slow');
			}
		});

		$('.salvarVarios').click(function(){
			$('.checkPostos').each(function() {

				var valor_selecionado = $(this).val();
				if ($("#check_posto_"+valor_selecionado).is(":checked")) {
					if($('#selecionados_action').val() == 'E'){
						marcarEnviado(valor_selecionado);
					}
				}
			});
		});

		$('#selecionados_action').change(function() {
			
		});

		/* Busca pelo Código */
		$("#posto_cnpj").autocomplete("relatorio_banner_ajax.php?busca_auto_complete=true&tipo_busca=posto&busca=cnpj", {
			minChars: 3,
			delay: 150,
			width: 350,
			matchContains: true,
			formatItem: formatItem,
			formatResult: function(row) {return row[1];}
		});

		$("#posto_cnpj").result(function(event, data, formatted) {
			$("#posto_nome").val(data[1]) ;
		});

		/* Busca pelo Nome Fantasia */
		$("#posto_nome").autocomplete("relatorio_banner_ajax.php?busca_auto_complete=true&tipo_busca=posto&busca=nome_fantasia", {
			minChars: 3,
			delay: 150,
			width: 350,
			matchContains: true,
			formatItem: formatItem,
			formatResult: function(row) {return row[1];}
		});

		$("#posto_nome").result(function(event, data, formatted) {
			$("#posto_cnpj").val(data[0]) ;
			//alert(data[2]);
		});

		//Valida os dados passados pelo usuário
		$('input[name=btn_pesquisa]').click(function(){
			var getData = $('#searchTable').find('input').serialize()+"&"+"&action=validateFormPesquisa";
			$.get("relatorio_banner_ajax.php", getData,
			function(data){

				var results = data.split("|");
				if (results[0] == 1) {
					$("div.msg_erro").html(results[1]);
					return false;
				}else{
					$('form[name=frm_pesquisa]').submit();
				}
			});
		})

		$('#selectAllBoxes').click(function(){

			$(".checkPostos").attr('checked', $('#selectAllBoxes').is(':checked'));    
		});

		$('#validados').click(function(){
			if ( $(this).is(':checked') && $('#nao_validados').is(':checked') ) {
				$('#nao_validados').attr('checked', false );
			}
		});

		$('#nao_validados').click(function(){
			if ($(this).is(':checked') && $('#validados').is(':checked')) {
				$('#validados').attr('checked', false );
			}
		});

		$('#enviados').click(function(){
			if ( $(this).is(':checked') && $('#nao_enviados').is(':checked') ) {
				$('#nao_enviados').attr('checked', false );
			}
		});

		$('#nao_enviados').click(function(){
			if ($(this).is(':checked') && $('#enviados').is(':checked')) {
				$('#enviados').attr('checked', false );
			}
		});

		$('.showDetails').click(function(){
			var id = $(this).attr('rel');
			var text = ($(this).val().indexOf('Ver D')>-1) ? 'Ocultar Detalhes' : 'Ver Detalhes';
			$(this).val(text);
			$('#tr_'+id).toggle('fast');
		});

	});

</script>
	<div class="msg_erro"> </div>
	<form action="<?=$PHP_SELF?>" method="post" name='frm_pesquisa'>
		<table class='formulario' cellpadding="0" cellspacing="0" id="searchTable">
			<caption class="titulo_tabela"> Parâmetros de Pesquisa </caption>
			<tr>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td>
					<table style="width:90%;max-width:90%;margin:auto;" class='formulario' >
						<tr>
							<td>Data Inicial</td>
							<td>Data Final</td>
						</tr>

						<tr>
							<td>
								<input type="text" name="data_inicial" id="data_inicial" class="frm" value="<?=$data_inicial?>">
							</td>
							<td>
								<input type="text" name="data_final" id="data_final" class="frm" value="<?=$data_final?>">
							</td>
						</tr>

						<tr>
							<td>Posto CNPJ</td>
							<td>Posto Nome Fantasia</td>
						</tr>
						<tr>
							<td>
							<input type="text" name="posto_cnpj" id="posto_cnpj" class="frm" value="<?=$posto_cnpj?>" />
							</td>
							<td>
								<input type="text" name="posto_nome" id="posto_nome" class="frm" />
							</td>
						</tr>
						<?php
						$check_validados     = (!empty($validados))       ? "checked = 'CHECKED'" : '';
						$check_nao_validados = (!empty($nao_validados))   ? "checked = 'CHECKED'" : '';
						$check_enviados      = (!empty($enviados))        ? "checked = 'CHECKED'" : '';
						$check_nao_enviados  = (!empty($nao_enviados))    ? "checked = 'CHECKED'" : '';
						$check_preencheu     = ($preencheu_fabricas=='t') ? "checked = 'CHECKED'" : '';
						$check_nao_preencheu = ($preencheu_fabricas=='f') ? "checked = 'CHECKED'" : '';
						?>
						<tr>
							<td>
								<input type="checkbox" name="validados" id="validados" value='true' <?php echo $check_validados ?> >&nbsp;
								<label for="validados">Validados</label>
							</td>
							<td rowspan="4" width="50%">
								<fieldset>
									<legend>Verificar quem preencheu os campos 'Outras Fábricas'</legend>
									<input id="pf1" name="preencheu_fabricas" value='' type="radio" />
									<label for="pf1">Todos</label><br />
									<input id="pf2" name="preencheu_fabricas" value="f" type="radio" <?=$check_nao_preencheu?> />
									<label for="pf2">Ainda NÃO preencheram</label><br />
									<input id="pf3" name="preencheu_fabricas" value="t" type="radio" <?=$check_preencheu?> />
									<label for="pf3">JÁ preencheram</label>
								</fieldset>
							</td>
						</tr>
						<tr>
							<td>
								<input type="checkbox" name="nao_validados" id="nao_validados" value='true' <?php echo $check_nao_validados ?> >&nbsp;
								<label for="nao_validados"><strong>Não</strong> Validados</label> 
							</td>
						</tr>
						<tr>
							<td>
								<input type="checkbox" name="enviados" id="enviados" value='true' <?php echo $check_enviados ?> >&nbsp;
								<label for="enviados">Somente enviados</label>
							</td>
						</tr>
						<tr>
							<td>
								<input type="checkbox" name="nao_enviados" id="nao_enviados" value='true' <?php echo $check_nao_enviados ?> >&nbsp;
								<label for="nao_enviados">Somente <strong>não</strong> enviados</label>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td align="center">
					<input type="button" value="Pesquisar" name='btn_pesquisa'>
					<input type="hidden" name="btn_acao" value='pesquisar'>
				</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
			</tr>
		</table>
	</form>

</div>

<br>
<?php if ($_POST['btn_acao'] == 'pesquisar'): ?>
	
	<div class="titulo_tabela" style="width:700px;margin:auto">Resultados da Pesquisa</div>
	<div class="main-content-search-results">
		<table class="tabela" width="100%" cellspacing="1" cellpadding="0">
		<?php
			$sql = "SELECT 	tbl_posto_alteracao.*
					FROM tbl_posto_alteracao 
					WHERE banner IS TRUE \n" .
					implode("\n", $sql_cond);

			/*
			echo "<pre style='text-align: left'>\n";
			var_dump($_POST);
			echo($sql);
			die("</pre>");
			*/

			$res = pg_query($con,$sql);
			if (pg_num_rows($res)>0) { ?>
				<tr class="titulo_coluna">
					<th>
						<input type="checkbox" name="selectAllBoxes" id="selectAllBoxes" title="Selecionar Todos">
					</th>
					<th>CNPJ</th>
					<th>Razão Social</th>
					<th>Data de Envio</th>
					<th>Validado</th>
					<th>Ações</th>
				</tr>

				<?
				foreach (pg_fetch_all($res) as $key => $value) {
					
					/**
					 * $value['campo_da_tabela'] > este array representa o valor de cada campo da tabela tbl_posto_alteracao
					 * que vem na query acima
					 */

					$cor_registro = '';

					if ($value['validado'] == 't') {
						$input_disabled = "disabled='DISABLED'";
						$ativo_display = "";
						$inativo_display = "display:none";
					}else{
						$input_disabled = "";
						$ativo_display = "display:none";
						$inativo_display = "";
					}

					if (strlen($value['banner_enviado']) > 0) {
						$button_display = "display:none";
					}else{
						$button_display = "";
						if ($value['validado'] != 't') {
							$button_display = "display:none";
						}
					}

					?>
					<tr <?=$cor_registro?>>
						<td>
							<input type="checkbox" name="check_posto[]" id="check_posto_<?=$value['posto_alteracao']?>" class="checkPostos" value="<?=$value['posto_alteracao']?>" >
						</td>
						<td>
							<?php echo $value['cnpj']; ?>
						</td>
						<td>
							<?php echo $value['razao_social'] ?>
						</td>
						<td id="enviado_<?=$value['posto_alteracao']?>">
							<?php if (!empty($value['banner_enviado'])): ?>
								<?php echo date('d/m/Y', strtotime($value['banner_enviado'])); ?>
							<?php endif ?>
						</td>
						<td>
							<img src="imagens/ativo.png" id="imgAtivo_<?=$value['posto_alteracao']?>" style="<?php echo $ativo_display ?>">
							<img src="imagens/inativo.png" id="imgInativo_<?=$value['posto_alteracao']?>" style="<?php echo $inativo_display ?>">
						</td>
						<td>
							<input type="button" value="Ver Detallhes" class="showDetails" rel="<?=$value['posto_alteracao']?>">
							<?php
								if (empty($value['banner_enviado'])): 
							?>
									<input type="button" value="Enviado" id="setSend_<?=$value['posto_alteracao']?>" onclick="marcarEnviado(<?=$value['posto_alteracao']?>)" style="<?php echo $button_display ?>">
							<?php 
								endif 
							?>
						</td>
					</tr>
					<!-- Dados do posto - Tr Oculta. é exibida ao clicar no botão 'Ver Detalhes' do respectivo posto autorizado -->
					<tr id="tr_<?=$value['posto_alteracao']?>" style="display:none" >
						
						<td colspan="6">
							<table width="600px" align="center" cellspacing="0" cellpadding="0" class='formulario_detalhes' id="table_<?php echo $value['posto_alteracao'] ?>"  style="text-align:left;">
								<caption class="titulo_tabela">Dados do Posto Autorizado</caption>
								<tr>
									<td style="padding:5px">
										Razão Social: <br>
										<input type="text" name="<?echo $value['posto']?>_razao_social" id="<?echo $value['posto']?>_razao_social" value="<?php echo $value['razao_social']?>" class='frm' style="width:90%" <?echo $input_disabled?> >
									</td>
									<td style="padding:5px">
										Nome Fantasia: <br>
										<input type="text" name="<?echo $value['posto']?>_nome_fantasia" id="<?echo $value['posto']?>_nome_fantasia" value="<?php echo $value['nome_fantasia']?>" style="width:90%" class="frm" <?echo $input_disabled?> >
									</td>
									<td style="padding:5px">
										E-mail: <br>
										<input type="text" name="<?echo $value['posto']?>_email" id="<?echo $value['posto']?>_email" class="frm" style="width:90%" value="<?php echo $value['email']?>" <?echo $input_disabled?> >
									</td>
								</tr>
								<tr>
									<td style="padding:5px">
										CNPJ: <br>
										<input type="text" name="<?echo $value['posto']?>_cnpj" id="<?echo $value['posto']?>_cnpj" class="frm cnpj" style="width:90%" value="<?php echo $value['cnpj']?>" <?echo $input_disabled?> >
									</td>
									<td style="padding:5px">
										Fone: <br>
										<input type="text" name="<?echo $value['posto']?>_fone" id="<?echo $value['posto']?>_fone" class="frm fone" style="width:90%" value="<?php echo $value['fone']?>" <?echo $input_disabled?> >
									</td>
									<td style="padding:5px">
										Contato: <br>
										<input type="text" name="<?echo $value['posto']?>_contato" id="<?echo $value['posto']?>_contato" class="frm" style="width:90%" value="<?php echo $value['contato']?>" <?echo $input_disabled?> >
									</td>
								</tr>

								<tr>
									<td style="padding:5px">
										Endereço: <br>
										<input type="text" name="<?echo $value['posto']?>_endereco" id="<?echo $value['posto']?>_endereco" class="frm" style="width:90%" value="<?php echo $value['endereco']?>" <?echo $input_disabled?> >
									</td>
									<td style="padding:5px">
										Nº: <br>
										<input type="text" name="<?echo $value['posto']?>_numero" id="<?echo $value['posto']?>_numero" class="frm" style="width:90%" value="<?php echo $value['numero']?>" <?echo $input_disabled?> >
									</td>
									<td style="padding:5px">
										Complemento: <br>
										<input type="text" name="<?echo $value['posto']?>_complemento" id="<?echo $value['posto']?>_complemento" class="frm" style="width:90%" value="<?php echo $value['complemento']?>" <?echo $input_disabled?> >
									</td>
								</tr>
								<tr>
									<td style="padding:5px">
										Cidade: <br>
										<input type="text" name="<?echo $value['posto']?>_cidade" id="<?echo $value['posto']?>_cidade" class="frm" style="width:90%" value="<?php echo $value['cidade']?>" <?echo $input_disabled?> >
									</td>
									<td style="padding:5px">
										Estado: <br>
										<input type="text" name="<?echo $value['posto']?>_estado" id="<?echo $value['posto']?>_estado" class="frm" style="width:90%" value="<?php echo $value['estado']?>" <?echo $input_disabled?>>
									</td>
									<td style="padding:5px">
										CEP: <br>
										<input type="text" name="<?echo $value['posto']?>_cep" id="<?echo $value['posto']?>_cep" class="frm" style="width:90%" value="<?php echo $value['cep']?>" <?echo $input_disabled?>>
									</td>
								</tr>
								<tr>
									<td style="padding:5px" colspan="3">
										<div style="width:90%;margin: auto;">
											<p class="subtitulo">Fábricas Credenciadas Telecontrol</p>
											<p>
												<ul>
													<?php  
													// $fabricas_telecontrol = explode(',', preg_replace("/^{|}$/", '', $value['fabrica_credenciada']));
													// foreach ($fabricas_telecontrol as $indice => $fabrica_credenciada) {
														echo "<li>";
														$sqlf = "SELECT ARRAY_TO_STRING(
															ARRAY(SELECT nome FROM tbl_fabrica WHERE ARRAY[fabrica] <@ '{$value['fabrica_credenciada']}'), ',')";
														$resf = pg_query($con,$sqlf);
														echo implode("</li><li>", explode(',', pg_fetch_result($resf, 0,0)));
														echo "</li>";
													//}
													?>
												</ul>
												<?//die($sql);?>
												<input type="hidden" name="<?=$value['posto']?>_fabrica_credenciada" value="<?php echo $value['fabrica_credenciada'] ?>">
											</p>
											<p class="subtitulo">Outras Fábricas Credenciadas</p>
											<p>
												<textarea name="<?php echo $value['posto'] ?>_marca_ser_autorizada" id="<?php echo $value['posto'] ?>_marca_ser_autorizada" class="frm" style="width:100%" rows="5" <?echo $input_disabled?>><?php echo utf8_decode($value['marca_ser_autorizada']) ?></textarea>
											</p>
											<p class="subtitulo">Fabricas especializadas</p>
											<p>
												<textarea name="<?php echo $value['posto'] ?>_outras_fabricas" id="<?php echo $value['posto'] ?>_outras_fabricas" class="frm" style="width:100%" rows="5" <?echo $input_disabled?>><?php echo utf8_decode($value['outras_fabricas']) ?></textarea>
											</p>
											<p class="subtitulo">Observação</p>
											<p>
												<textarea name="<?php echo $value['posto'] ?>_observacao" id="<?php echo $value['posto'] ?>_observacao" class="frm" style="width:100%" rows="5" <?echo $input_disabled?>><?php echo $value['observacao'] ?> </textarea>
											</p>
										</div>
									</td>
								</tr>
								<tr>
									<td style="padding:5px" colspan="3" >
										<div>
											<p class="titulo_coluna">Ações</p>
											<p style="text-align:center">
												<?php if (empty($input_disabled)): ?>
													<input type="button" value="Validar dados" id="validado_<?php echo $value['posto']?>" onclick="validarDados(<?php echo $value['posto_alteracao'].",".$value['posto'] ?>)" >
												<?php endif ?>
											</p>
										</div>
									</td>
								</tr>
							</table>
						</td>
					</tr>
		<?php	} ?>
				<tr class='titulo_coluna'>
					<td>
						<img src="imagens/setinha_linha4.gif" alt="">
					</td>
					<td colspan="3">
						Com selecionados:
						<select name="selecionados_action" id="selecionados_action">
							<option value=""></option>
							<option value="E">Marcar como Enviado</option>
						</select>
					</td>
					<td colspan="2">
						<input type="button" value="Salvar" class="salvarVarios">
					</td>
				</tr>

				<?
			}else{
				?>
				<tr>
					<td colspan="5" class='msg_erro'>Nenhum resultado encontrado</td>
				</tr>
				<?
			}
		?>
		</table>		
	</div>

<?php endif;
include 'rodape.php';
