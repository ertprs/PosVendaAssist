<?php

	require_once "dbconfig.php";
	require_once "includes/dbconnect-inc.php";
	require_once 'autentica_admin.php';
	require_once "../js/js_css.php";

	$get = (object) $_GET;
	if(strpos($get->advertencia,'-') !== false)
		list($get->advertencia) = explode('-',$get->advertencia);

	# Busca privilégio SAP para o admin (só estes poderão alterar o status)
	$sql = "SELECT admin_sap
			FROM tbl_admin
			WHERE admin = $login_admin";

	$res = pg_query($con, $sql);

	$login_sap = pg_fetch_result($res, 0, 0);

	# Busca a data que a advertência foi finalizada (para habilitar a alteração do status ou não)
	$sql = "SELECT data_concluido,
				   mensagem,
				   tipo_ocorrencia
			FROM tbl_advertencia
			WHERE advertencia = $get->advertencia";

	$res = pg_query($con, $sql);

	$data_concluido  = pg_fetch_result($res, 0, 'data_concluido');
	$mensagem        = preg_replace('/\r\n|\r|\n/', '<br />', pg_fetch_result($res, 0, 'mensagem'));
	$tipo_ocorrencia = pg_fetch_result($res, 0, 'tipo_ocorrencia');

    if (mb_check_encoding($mensagem, 'UTF-8'))
        $mensagem = utf8_decode($mensagem);

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv='pragma' content='no-cache'>
		<link type="text/css" href="css/relatorio_advertencia_bo.css" rel="stylesheet"></link>
		<style type="text/css">
			body {
				margin: 0;
				font-family: Arial, Verdana, Times, Sans;
				background: #fff;
				cursor: default;
			}
			.main-content-sbx{
				overflow: auto;
				height: 500px;
			}
		</style>

		<script src="../plugins/jquery/jquery.tablesorter.min.js" type="text/javascript"></script>
		<link rel="stylesheet" type="text/css" href="../css/lupas/lupas.css">
		<link type="text/css" href="css/css.css" rel="stylesheet"></link>
		<link rel="stylesheet" type="text/css" href="../plugins/jquery/datepick/telecontrol.datepick.css">
		<script type='text/javascript'>
			//função para fechar a janela caso a telca ESC seja pressionada!
			$(window).keypress(function(e) {
				if(e.keyCode == 27) {
					 window.parent.Shadowbox.close();
				}
			});

			$(document).ready(function() {

				function verifyDate() {

					var date1_d = $("#data_finalizada").datepick('getDate');

					date1_d = new Date(date1_d);

					if(date1_d.toString() == "Invalid Date") {
						$("#data_finalizada").val("");
					}
				}

				$("#gridRelatorio").tablesorter();

				$("#data_finalizada").mask("99/99/9999");

				$("#data_finalizada").datepick({
					startDate:'01/01/2000'
				});

				$("#data_finalizada").blur(function() {
					verifyDate();
				});

				$('form[name=interagir]').bind('submit', function() {

					if ($("#acao_bo").val() == 'outros' && $("#outros_explicacao").val().replace(" ", "") == '') {
						alert("Preencha todos os campos")
						$("#outros_explicacao").css("background-color", "red")
						setTimeout(function(){ $("#outros_explicacao").css("background-color", "white") }, 2000)
						return false
					}

					var advertencia = "<?=$get->advertencia?>";

					$.post(
						"relatorio_advertencia_bo_ajax.php",
						{
							acao: "interagir",
							advertencia: advertencia,
							status: $("#status").val(),
							data_finalizada: $("#data_finalizada").val(),
							texto: $("#texto").val(),
							nivel_falha: $("#nivel_falha").val(),
							acao_bo: $("#acao_bo").val(),
							outros_explicacao: $("#outros_explicacao").val()
						},
						function(data) {
							if(data != "") {
								$(".error").html(data);
								$(".error").show();
							} else {
								if($("#status").val() == "finalizar") {
									$("#" + advertencia, window.parent.document).find("td[name=data_fechamento]").html($("#data_finalizada").val());
									$("#" + advertencia, window.parent.document).find("a[name=acao]").html("Ver histórico");
									$("#" + advertencia, window.parent.document).find("td[name=statuss]").html("Finalizado");
								}
								//window.parent.Shadowbox.close();
								window.parent.simulaClick()
							}
						}
					);

					return false;
				});

				$("#status").change(function() {
					if($(this).val() == "finalizar") {
						$("#data_finalizada").show();
						$("#data_finalizada").attr("required", "true");
					} else {
						$("#data_finalizada").hide();
						$("#data_finalizada").removeAttr("required");
						$("#data_finalizada").val('');
					}
				});

				$("#acao_bo").change(function() {
					if($(this).val() == "outros") {
						$("#outros_explicacao").show();
						$("#outros_explicacao").attr("required", "true");
					} else {
						$("#outros_explicacao").hide();
						$("#outros_explicacao").removeAttr("required");
						$("#outros_explicacao").val('');
					}
				});
			});
		</script>
	</head>

	<body>
	<div class="main-content-sbx">
		<div class="lp_header">
			<a href='javascript:window.parent.Shadowbox.close();' style='border: 0;'>
				<img src='css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar' />
			</a>
		</div>

		<br/>

		<table width='100%' border='0' cellspacing='1' cellspading='0' class='lp_tabela'>
			<thead>
				<tr class='error' style="display: none;">
					<td colspan='3'></td>
				</tr>
				<tr>
					<th>Descrição</th>
				</tr>
			</thead>
			<tbody>
				<tr style='background: #F1F4FA;'>
					<td style="padding: 1ex 1em; cursor: default;">
						<?=$mensagem?>
					</td>
				</tr>
			</tbody>
		</table>

		<form name="interagir">

		<?

		if($get->finalizado == 'false') :

		?>
				<table width='100%' border='0' cellspacing='1' cellspading='0' class='lp_tabela'>
					<thead>
						<tr class='error' style="display: none;">
							<td colspan='3'></td>
						</tr>
						<tr>
							<th>Interagir</th>
						</tr>
					</thead>
					<tbody>
					<?php
						# Só poderá alterar o status da advertência o admin que tiver privilégio
						# $showStatus = ($login_sap == 't' && empty($data_concluido) && !empty($tipo_ocorrencia)); Ação será liver para Advertencia tmb HD-7054340
						$showStatus = ($login_sap == 't' && empty($data_concluido));

						$mostra = 'style="display: none"';
						$sqlNivel = "	SELECT parametros_adicionais->>'nivel_falha' AS nivel_falha,
											   parametros_adicionais->>'acao_bo' AS acao_bo,
											   parametros_adicionais->>'outros_explicacao' AS outros_explicacao
										FROM tbl_advertencia 
										WHERE advertencia = $get->advertencia";
						$resNivel = pg_query($con, $sqlNivel);
					?>
							<tr style='background: #F1F4FA; <?= $showStatus ? "" : "display: none"; ?>'>
								<td style="text-align: center; cursor: default;">
									<font>Status:</font>
									<select id="status" name="status">
										<option>Pendente</option>
										<option value="finalizar">Finalizar</option>
									</select>
									<input type="text" size="10" name="data_finalizada" id="data_finalizada" style="display: none" />
									&nbsp;
									<font>Nível da Falha:</font>
									<select id="nivel_falha" name="nivel_falha" required>
										<?php
											$arrayNivel = ["leve"=>"Leve", "medio"=>"Médio", "alto"=>"Alto"];

											if (pg_num_rows($resNivel) > 0) {
												$nivel_falha = pg_fetch_result($resNivel, 0, 'nivel_falha');
											}

											foreach ($arrayNivel as $key => $value) {
												$selected = "";
												if (!empty($nivel_falha)) {
													if ($nivel_falha == $key) {
														$selected = "SELECTED";	
													}
												}
										?>
												<option <?=$selected?> value="<?=$key?>"><?=$value?></option>
										<?php
											}
										?>
									</select>
									&nbsp;
									<font>Ação:</font>
									<select id="acao_bo" name="acao_bo" required>
										<?php
											$arrayBO = ["acompanhamento"=>"Acompanhamento", 
														"orientacao_verbal"=>"Orientação Verbal", 
														"orientacao_escrita"=>"Orientação Escrita",
														"advertencia"=>"Advertência",
														"descredenciamento"=>"Descredenciamento",
														"outros"=>"Outros"
													   ];
											if (pg_num_rows($resNivel) > 0) {
												$acao_bo = pg_fetch_result($resNivel, 0, 'acao_bo');
												$outros_explicacao = pg_fetch_result($resNivel, 0, 'outros_explicacao');
												$str       = str_replace('\u','u',$outros_explicacao);
												$outros_explicacao = preg_replace('/u([\da-fA-F]{4})/', '&#x\1;', $str);
												$outros_explicacao = (mb_check_encoding($outros_explicacao, "UTF-8")) ? utf8_decode($outros_explicacao) : $outros_explicacao;
												
												if ($acao_bo == 'outros') {
													$mostra = '';
												}
											}

											foreach ($arrayBO as $key => $value) {
												$selected = "";
												if (!empty($acao_bo)) {
													if ($acao_bo == $key) {
														$selected = "SELECTED";	
													}
												}
										?>
												<option <?=$selected?> value="<?=$key?>"><?=$value?></option>
										<?php
											}
										?>
									</select>
									<input type="text" size="20" name="outros_explicacao" id="outros_explicacao" value="<?=$outros_explicacao?>" <?=$mostra?> />
							</tr>
						<tr style='background: #F1F4FA;'>
							<td style="text-align: center; cursor: default;">
								<textarea style="width: 799px;" id="texto" required></textarea>
							</td>
						</tr>
						<tr style='background: #F1F4FA;'>
							<td style="text-align: center; cursor: default;">
								<input type="submit" value="Gravar" />
							</td>
						</tr>
					</tbody>
				</table>
		<?
		endif;
		?>
		</form>

		<table width='100%' border='0' cellspacing='1' cellspading='0' class='lp_tabela' id='gridRelatorio'>
			<thead>
				<tr>
					<th>Admin</th>
					<th>Data</th>
					<th>Texto</th>
				</tr>
			</thead>
			<tbody>
				<?
					$sql = "SELECT tbl_admin.nome_completo,
								   to_char(tbl_advertencia_item.data_input, 'dd/mm/yyyy') as data_input,
								   tbl_advertencia_item.texto
							FROM tbl_advertencia_item
							JOIN tbl_admin ON tbl_admin.admin = tbl_advertencia_item.admin
							WHERE tbl_advertencia_item.advertencia = $get->advertencia
							ORDER BY tbl_advertencia_item.advertencia_item DESC";

					$res = pg_query($con, $sql);
					if(pg_num_rows($res)) {

						while($advertencia_item = pg_fetch_object($res)) {
                            $texto = mb_check_encoding($advertencia_item->texto, 'UTF-8') ?
                                utf8_decode($advertencia_item->texto):
                                $advertencia_item->texto;
                            $texto = preg_replace('/\r\n|\r|\n/', '<br />', $texto);
							print "<tr>";
							print "<td style='width: 200px;'>$advertencia_item->nome_completo</td>";
							print "<td style='padding: 1ex 1em; width: 80px;'>$advertencia_item->data_input</td>";
							print "<td>$texto</td>";
							print "</tr>";
						}
					} else {
						print "<tr><td colspan='3'>Nenhuma interação encontrada</td></tr>";
					}
				?>
			</tbody>
		</table>
	</div>
	</body>
</html>
