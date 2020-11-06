<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios='cadastros';
require_once 'autentica_admin.php';
include_once 'helper.php';

include_once 'funcoes.php';

if (isset($_POST['ajax_pecas_sem_preco'])) {

	$dados_psp = json_decode(stripslashes($_POST['psp_json']), true);
	$buffer_psp = "";

	if (in_array($login_fabrica,[120,201])) {
		$buffer_psp .= "<thead>
					<tr class='titulo_coluna' style='border: 1px solid #596d9b;'>
						<th>".traduz('Referência')."</th>
						<th>".traduz('Descrição')."</th>
					</tr>
				</thead>
				<tbody>";

		foreach($dados_psp as $rec) {
			extract($rec, EXTR_PREFIX_ALL, 'psp');

			$sql2 = "SELECT os, sua_os
				FROM tmp_os_peca_sem_preco
				WHERE fabrica = {$login_fabrica}
				AND peca = {$psp_peca}";
			$res2 = pg_query($con, $sql2);
			$total_os_peca_sem_preco = pg_num_rows($res2);
			$bgcolor = ($total_os_peca_sem_preco > 0) ? "#f2dede" : "";
			$id = ($total_os_peca_sem_preco > 0) ? "id='ln_{$psp_peca}' rel='{$psp_peca}' style='cursor:pointer;'" : "";

			$buffer_psp .= "<tr bgcolor='{$bgcolor}' $id>
						<td align='center'><a href='preco_cadastro.php?peca={$psp_peca}' target='_blank'>{$psp_referencia}</a></td>
						<td align='left'>{$psp_descricao}</td>
					</tr>";

			if ($total_os_peca_sem_preco > 0) {
				$buffer_psp .= "<tr style='display:none' id='ln_os_{$psp_peca}'>";
				$buffer_psp .= "<td colspan='2'>
							<table width='100%'>";
				for($x = 0; $x < $total_os_peca_sem_preco; $x++){
					$os 	= pg_fetch_result($res2, $x, 'os');
					$sua_os = pg_fetch_result($res2, $x, 'sua_os');

						$buffer_psp .= "<tr>
									<td align='left'>
										<a href='os_press.php?os={$os}' target='_blank'>{$sua_os}</a>
									</td>
								</tr>";
				}
				$buffer_psp .= "</table>
					</td>
				</tr>";
			}
		}

		$buffer_psp .= "</tbody>";

	} else {

		$buffer_psp .= "<thead>
					<tr class='titulo_coluna' style='border:1px solid #596d9b;'>
						<th width='10%'>".traduz('Referência')."</th>
						<th width='30%'>".traduz('Descrição')."</th>
						<th width='30%'>".traduz('OSs em Garantia com Peças sem Preço')."</th>
						<th width='30%'>".traduz('Tabela onde a Peças está sem Preço')."</th>
					</tr>
				</thead>
			  	<tbody class='ocultar lista-peca-sem-preco'>";
	  	$box = 0;
		foreach($dados_psp as $rec) {
			extract($rec, EXTR_PREFIX_ALL, 'psp');

			/**
			 * @description HD 754908 Jacto - Não mostrar peças que tem de para, e o para contenha preço.
			 * @author Brayan
			 **/
			if ($login_fabrica == 87) {

				$sql2 = "SELECT tbl_peca.peca
						 FROM tbl_depara
						 JOIN tbl_peca ON tbl_depara.peca_para = tbl_peca.peca
						 	AND tbl_peca.fabrica = tbl_depara.fabrica
						 WHERE tbl_peca.fabrica = {$login_fabrica}
						 AND   tbl_depara.peca_de = {$psp_peca}
						 AND   tbl_peca.produto_acabado IS NOT TRUE
						 AND   tbl_peca.ativo IS TRUE";

				$res2 = pg_query($con,$sql2);

				if (pg_num_rows($res2) > 0) {
					continue;
				}

			}

			$links_os = array();

			$sql2 = "SELECT os,sua_os
				FROM tmp_os_peca_sem_preco
				WHERE fabrica = $login_fabrica
				AND peca = {$psp_peca};";
			$res2 = pg_query($con,$sql2);
			$total_os_peca_sem_preco = pg_num_rows($res2);
			$bgcolor = ($total_os_peca_sem_preco > 0) ? "#f2dede" : "";
			$id = ($total_os_peca_sem_preco > 0) ? "id='ln_{$psp_peca}' rel='{$psp_peca}' style='cursor:pointer;'" : "";

			$desc_tabelas = array();

		  	$sql_tabela = "SELECT
		  				sigla_tabela, descricao
		  			FROM tbl_tabela
		  			WHERE fabrica = {$login_fabrica}
		  			AND ativa IS TRUE
		  			AND tabela NOT IN (SELECT tabela FROM tbl_tabela_item WHERE peca = {$psp_peca})";
		  	$res_tabela = pg_query($con, $sql_tabela);

		  	if (pg_num_rows($res_tabela) > 0) {

				$buffer_psp .= "<tr bgcolor='{$bgcolor}' {$id}>
							<td align='center'><a href='preco_cadastro.php?peca={$psp_peca}' target='_blank'>{$psp_referencia}</a></td>
							<td align='left'>{$psp_descricao}</td>
							<td class='tac'>";

					if($total_os_peca_sem_preco > 0){
						for($x = 0; $x < $total_os_peca_sem_preco; $x++){
							$os 	= pg_fetch_result($res2, $x, 'os');
							$sua_os = pg_fetch_result($res2, $x, 'sua_os');

							$links_os[] = "<a href='os_press.php?os={$os}' target='_blank'>{$sua_os}</a>";
						}
						$buffer_psp .= implode(", ", $links_os);
					}else{
						$buffer_psp .= traduz("Nenhuma OS localizada");
					}

				$buffer_psp .= "</td>";

				$buffer_psp .= "<td>";

		  		for($t = 0; $t < pg_num_rows($res_tabela); $t++){
		  			$desc_tabelas[] = pg_fetch_result($res_tabela, $t, "sigla_tabela")." - ".pg_fetch_result($res_tabela, $t, "descricao");
		  		}

		  		$desc_tabelas = implode("<br />", $desc_tabelas);

		  		$box++;

		  		$buffer_psp .= "<div class='tac'>
		  					<button type='button' class='btn btn-success' id='exibir_{$box}' style='width:80px;' onclick='toggle_box({$box})'><strong>".traduz('Exibir')."</strong></button>
		  					<button type='button' class='btn btn-danger' id='ocultar_{$box}' style='width:80px;display:none;' onclick='toggle_box({$box})'><strong>".traduz('Ocultar')."</strong></button>
		  				</div>
		  				<div id='desc_tabelas_{$box}' class='tac' style='display:none;'>
		  					<br />
		  					{$desc_tabelas}
	  					</div>
					</td>
				</tr>";
			}
		}
	}

	echo $buffer_psp;
	exit;

}

if (isset($_REQUEST['ajax_p_novos'])) {
	$tipoReq = $_REQUEST['tipo'];

	$buffer_pn = "<thead>
				<tr class='titulo_coluna' style='border:1px solid #596d9b;'>
					<th width='10%'>".traduz('Referência')."</th>
					<th width='30%'>".traduz('Descrição')."</th>
				</tr>
			</thead>
		  	<tbody class='ocultar lista-produto-novo'>";

	if ($tipoReq == 'pecas') {

		$sql = "SELECT peca,
				descricao,
				referencia
			FROM tbl_peca
			WHERE fabrica = {$login_fabrica}
			AND tbl_peca.admin IS NULL
			AND produto_acabado IS NOT TRUE";

		$res = pg_query($sql);

		for ($i=0;$i<pg_num_rows($res);$i++) {

			$peca    = pg_result($res, $i, peca);
			$referencia = pg_result($res, $i, referencia);
			$descricao  = pg_result($res, $i, descricao);

			$buffer_pn .= "<tr>";
			$buffer_pn .= "<td align='center'><a href='peca_cadastro.php?peca={$peca}' target='_blank'>{$referencia}</a></td>";
			$buffer_pn .= "<td>$descricao</td>";
			$buffer_pn .= "</tr>";

		}

	} else if ($tipoReq == 'produtos') {

		$sql = "SELECT
				produto,
				descricao,
				referencia
			FROM tbl_produto
			WHERE fabrica_i = {$login_fabrica}
			AND tbl_produto.admin IS NULL";

		$res = pg_query($sql);

		for ($i=0;$i<pg_num_rows($res);$i++) {

			$produto    = pg_result($res, $i, produto);
			$referencia = pg_result($res, $i, referencia);
			$descricao  = pg_result($res, $i, descricao);

			$buffer_pn .= "<tr>";
			$buffer_pn .= "<td align='center'><a href='produto_cadastro.php?produto={$produto}' target='_blank'>{$referencia}</a></td>";
			$buffer_pn .= "<td>$descricao</td>";
			$buffer_pn .= "</tr>";

		}
	}

	$buffer_pn .= "</tbody>";

	echo $buffer_pn;
	exit;
}

if(isset($_POST["get_pecas_sem_preco"])){
	$pag = $_POST["pag"];
	$limit = $pag * 1000;
	if (in_array($login_fabrica, array(2))) {
		$tabela_garantia = " AND tabela_garantia";
	} else if (!in_array($login_fabrica, array(87))) {
		$tabela_garantia = " AND (ativa OR tabela_garantia)";
	}

	$lista_basica = (in_array($login_fabrica, array(72))) ? "JOIN tbl_lista_basica ON tbl_peca.peca = tbl_lista_basica.peca JOIN tbl_produto ON tbl_produto.produto = tbl_lista_basica.produto AND tbl_produto.ativo " : "";

	$sql = "SELECT DISTINCT tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao
		FROM tbl_peca
		{$lista_basica}
		LEFT JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca
		AND tbl_tabela_item.tabela in(SELECT tabela FROM tbl_tabela WHERE fabrica = $login_fabrica $tabela_garantia)
		WHERE tbl_peca.fabrica = $login_fabrica
		AND   tbl_peca.produto_acabado IS NOT TRUE
		AND   tbl_peca.ativo           IS TRUE
		AND   tbl_tabela_item.preco    IS NULL
		LIMIT $limit OFFSET $pag";
	$res = pg_query($con, $sql);

	$pecas_sem_preco = array();

	if(pg_num_rows($res) > 0){
		for ($i = 0; $i < pg_num_rows($res); $i++) {
			$peca = pg_fetch_result($res, $i, "peca");

			/* Verifica OS sem Preço */
			$links_os = array();

			$sql2 = "SELECT os,sua_os
				FROM tmp_os_peca_sem_preco
				WHERE fabrica = {$login_fabrica}
				AND peca = {$peca}";
			$res2 = pg_query($con,$sql2);
			$total_os_peca_sem_preco = pg_num_rows($res2);

			/* Verificas tabelas */
			$desc_tabelas = array();

			$sql_tabela = "SELECT
						sigla_tabela, descricao
					FROM tbl_tabela
					WHERE fabrica = {$login_fabrica}
					AND ativa IS TRUE
					AND tabela NOT IN (SELECT tabela FROM tbl_tabela_item WHERE peca = {$peca})";
			$res_tabela = pg_query($con, $sql_tabela);

			if(pg_num_rows($res_tabela) > 0){
				$mostra_legenda++;

				if($total_os_peca_sem_preco > 0){
					for($x = 0; $x < $total_os_peca_sem_preco; $x++){
						$os     = pg_fetch_result($res2, $x, 'os');
						$sua_os = pg_fetch_result($res2, $x, 'sua_os');

						$links_os[] = "<a href='os_press.php?os={$os}' target='_blank'>{$sua_os}</a>";
					}
					$links_os = implode(", ", $links_os);
				}else{
					$links_os = traduz("Nenhuma OS localizada");
				}

				for($t = 0; $t < pg_num_rows($res_tabela); $t++){
					$desc_tabelas[] = pg_fetch_result($res_tabela, $t, "sigla_tabela")." - ".pg_fetch_result($res_tabela, $t, "descricao");
				}

			}

			$desc_tabelas = implode("<br />", $desc_tabelas);

			$pecas_sem_preco["pecas"][] = array(
								"peca"         => $peca,
								"referencia"   => utf8_encode(pg_fetch_result($res, $i, "referencia")),
								"descricao"    => utf8_encode(pg_fetch_result($res, $i, "descricao")),
								"desc_tabelas" => utf8_encode($desc_tabelas),
								"links_os"     => utf8_encode($links_os)
							);
		}
		$pecas_sem_preco["tem_pecas"] = (pg_num_rows($res) < 1000) ? false : true;
	}

	exit(json_encode($pecas_sem_preco));

}

$helper->login;

$title = traduz("Cadastros do Sistema");
$layout_menu = "cadastro";
include 'cabecalho_new.php';

include 'jquery-ui.html';
if($login_fabrica == 86 and 1==2) {
	echo "<h2 style='text-align:center'>".traduz('Acesso Restrito')."</h2>";
	include "rodape.php" ;
	exit;
}

?>
<style type="text/css">
table.tabela {margin-bottom: 1em;font-family: Verdana, Arial, helvetica, sans-serif}
table.tabela>caption.titulo_coluna {
	background: #596d9b  url(imagens/icon_collapse_white.png) no-repeat 16px 2px;
	border-radius: 5px 5px 0 0;
	cursor: pointer;
	padding:2px;
	transition: background-color 0.4s;
	-o-transition: background-color 0.4s;
	-ms-transition: background-color 0.4s;
	-moz-transition: background-color 0.4s;
	-webkit-transition: background-color 0.4s;

}

table.tabela>caption.titulo_coluna2 { /*HD-3269910*/
	background: #596d9b  url(imagens/icon_collapse_white.png) no-repeat 16px 2px;
	border-radius: 5px 5px 0 0;
	cursor: pointer;
	padding:2px;
	background-color: #596d9b;
    font: bold 11px "Arial";
    color: #FFFFFF;
    text-align: center;
    padding: 5px 0 0 0;
}
table.tabela>caption.p_novos {
	background: #596d9b  url(imagens/icon_collapse_white.png) no-repeat 16px 2px;
	border-radius: 5px 5px 0 0;
	cursor: pointer;
	padding:2px;
	transition: background-color 0.4s;
	-o-transition: background-color 0.4s;
	-ms-transition: background-color 0.4s;
	-moz-transition: background-color 0.4s;
	-webkit-transition: background-color 0.4s;
	font-size: 11px;
	font-weight: bold;
	color:#FFFFFF;
	text-align:center;
}

table.tabela tr td{
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

/* muda cor topo peça sem preço */
table.tabela.oculta.ocultar > thead,
table.tabela.oculta.ocultar > tbody {display:none}
table.tabela.oculta caption {
	border-radius: 5px;
	background: red url(imagens/icon_expand_white.png) no-repeat 16px 2px;
	background-color: red;
	color: white;
}

table.tabela.oculta2 caption { /* //HD-3269910 */
	border-radius: 5px;
	background: red url(imagens/icon_expand_white.png) no-repeat 16px 2px;
	background-color: red;
	color: white;
}
</style>

<script type="text/javascript">
	var pag = 1;
	$(function() {

		$(document).on("click", "tr[id^=ln_]", function(){
			var peca = $(this).attr("rel");
			if( $("#ln_os_"+peca).is(":visible") ){
				$("#ln_os_"+peca).hide();
			}else{
				$("#ln_os_"+peca).show();
			}
		});

		$(document).on("click", ".titulo_coluna", function(){
			var linha = $(this).attr("rel");
			var descricao = $(this).text();
			var rowsTable = $('.tbl_psp_'+linha+' tr').length;
			var psp_json = $("#psp_json_"+linha).val();

			if (rowsTable > 0) {
				$(".tbl_psp_"+linha).addClass('oculta');
				$(".tbl_psp_"+linha).html('<caption class="titulo_coluna" style="font-size:14px;padding-top:5px;"" rel="'+linha+'">'+descricao+'</caption>');
			} else {
				$.ajax({
					url : "<?= $_SERVER['PHP_SELF']; ?>",
					type : "POST",
					data : { ajax_pecas_sem_preco : true, psp_json : psp_json },
					beforeSend : function() {
						$(".tbl_psp_"+linha).append("<em><?=traduz('carregando, por favor aguarde')?>...</em>");
					}
				}).done(function(retorno){
					$(".tbl_psp_"+linha+" em").remove();
					$(".tbl_psp_"+linha).removeClass('oculta');
					$(".tbl_psp_"+linha).append(retorno);
				});
			}

			if ($(".box-carregar").is(":visible")) {
				$(".box-carregar").hide();
			} else {
				$(".box-carregar").show();
			}
		});

		$(document).on("click", ".titulo_coluna2", function(){//HD-3269910
			if ($(".table2").is(":visible")) {
				$(".table2").hide();
				$(".table2").parent().addClass('oculta2');
			} else {
				$(".table2").show();
				$(".table2").parent().removeClass('oculta2');
			}
		});


		$(".btn-carregar").click(function(){
			$.ajax({
				url : "<?php echo $_SERVER['PHP_SELF'] ?>",
				type : "post",
				data : {
					pag : pag,
					get_pecas_sem_preco : true
				},
				beforeSend: function(){
					$(".before-msg").html("<em><?=traduz('carregando, por favor aguarde')?>...</em>");
				}
			}).always(function(data){
				data = JSON.parse(data);
				$(".before-msg").html("");
				var box = (pag * 1000) + 1;
				$.each(data.pecas, function(key, value){
					$(".lista-peca-sem-preco").append("<tr> \
										<td align='center'><a href='preco_cadastro.php?peca="+value.peca+"' target='_blank'>"+value.referencia+"</td> \
										<td>"+value.descricao+"</td> \
										<td class='tac'>"+value.links_os+"</td> \
										<td> \
										<div class='tac'> \
										<button type='button' class='btn btn-success' id='exibir_"+box+"' style='width: 80px;' onclick='toggle_box(\""+box+"\")'><strong>Exibir</strong></button> \
										<button type='button' class='btn btn-danger' id='ocultar_"+box+"' style='width: 80px; display: none;' onclick='toggle_box(\""+box+"\")'><strong>Ocultar</strong></button> \
										</div> \
										<div id='desc_tabelas_"+box+"' class='tac' style='display: none;'> \
										<br /> \
										"+value.desc_tabelas+" \
										</div> \
										</td> \
									</tr>");

					box++;

				});
				if(data.tem_pecas == true){
					pag += 1;
				}else{
					$(".box-carregar").hide();
				}
			});
		});

		<? if ($login_fabrica == 151) { ?>
			$(document).on("click", ".p_novos", function() {
				var tipo = $(this).attr('rel');
				var rowsTable = $('.'+tipo+' tr').length;
				var tipoDesc = (tipo == 'produtos') ? 'Produtos Novos' : 'Peças Novas';

				if (rowsTable > 0) {
					$("."+tipo).addClass('oculta');
					$("."+tipo).html('<caption class="p_novos" style="font-size:14px;padding-top:5px;" rel="'+tipo+'">'+tipoDesc+'</caption>');
				} else {
					$.ajax({
						url : "<?= $_SERVER['PHP_SELF']; ?>",
						type : "POST",
						data : { ajax_p_novos : true, tipo : tipo }
					}).done(function(retorno){
						$("."+tipo).removeClass('oculta');
						$("."+tipo).append(retorno);
					});
				}
			});
		<? } ?>
	});
</script>
<?

//VETORES QUE ADICIONAM FUNCIONALIDADES - HD 383687

$vet_peca_sem_preco                 = array(2,3,5,7,8,11,35,40,43,52,74,80,86,72,85,161,172); //Ao alterar esse ARRAY, também deverá alterar a rotina 'rotinas/rotina-os-peca-sem-preco.php'
$vet_produto_sem_mo                 = array(3,6,86);
$vet_produto_sem_preco              = array(51,86);
$vet_produto_sem_familia            = array(3,6,7,14,43,85,86);
$vet_produto_sem_linha              = array(43,86);

$vet_produto_sem_capacidade_divisao = array(7);

//hd 19043 - Selecionei as fábricas que usam tbl_subproduto e coloquei no array.
$usam_subproduto = array(43, 8, 3, 14, 46, 17, 66, 4, 10, 2, 5);

//$usa_sap_atende_hd_postos    = array(1, 3, 11, 42, 30,151, 153);
//$fabrica_hd_posto_categorias = array(30,151, 153);
$array_hd_posto = array();
if(in_array($login_fabrica, array(74,101))) {
	$helpdeskPostoAutorizado = false;
}
if (in_array($login_fabrica, array(1, 3, 11,30,35,42,72,151, 153, 160, 163, 172,175)) or $replica_einhell) {
	$helpdeskPostoAutorizado = true;
	$array_hd_posto[] = $login_fabrica;
}

if($helpdeskPostoAutorizado == true){
	$array_hd_posto[] = $login_fabrica;	
}

$usam_kit_pecas              = array(3,15,24,30,91,123);
$fabrica_pecas_represadas    = array_merge($fabricas_contrato_lite, array(6,24,50,86,115,116,117,122,81,114,123,124,126,127,128,129));
$vet_fabrica_multi_marca     = array(3, 10, 30, 35, 52, 101, 104, 105, 125, 141, 144, 146);
if ($multimarca == 't')
    array_push($vet_fabrica_multi_marca, $login_fabrica);

// Fábricas que não deixam digitar o defeito reclamado,
// tem que cadastrar e manter a lista de defeitos reclamados
$sql = "SELECT pedir_defeito_reclamado_descricao
		  FROM tbl_fabrica
		 WHERE fabrica = $login_fabrica
		   AND (pedir_defeito_reclamado_descricao IS NULL
			OR pedir_defeito_reclamado_descricao  IS FALSE);";
$res = @pg_exec($con,$sql);

// Vem do include menus/menu_cadastro
// Fábricas que fazem relacionamento de integridade de defeito constatado com família

$fabrica_integridade_familia_constatado = array(19,42,46,74,81,86,94,95,96,98,99,104,105,108,101,111,114,115,116,117,120,201,121,122,123,124,125,126,127,128,129,131,132,134,136,137,138,139,141,142,143,144,145,146,147,148,149,150,151,152,153,154,155,156,157,158,160,161,162,163,164,165,166,167,169,170,171,174,180,181,182,203);

if (isset($novaTelaOs) && $login_fabrica >= 169) {
	$fabrica_integridade_familia_constatado[] =  $login_fabrica;
}

if ($login_fabrica == 140 OR $usa_linha_defeito_constatado == 't'){
	$fabrica_integridade_linha_constatado = array($login_fabrica);
}

if ($usa_linha_defeito_reclamado == 't'){
	$fabrica_integridade_linha_reclamado = array($login_fabrica);
}
// Fábricas que fazem relacionamento de integridade de defeito reclamado com família
if ($login_fabrica > 130 or isset($novaTelaOs)) {
    $fabrica_integridade = $login_fabrica;
}

if ($login_fabrica == 172) {
    unset($fabrica_integridade);
}

$fabrica_integridade_familia_reclamado  = array(15,30,35,52,74,81,86,98,99,104,105,108,101,111,115,116,117,122,123,125,128,129,134,137,$fabrica_integridade,166,169,170);

$fabrica_integridade_reclamado_constatado = array(1, 2, 5, 8, 10, 14, 16, 20);

$fabrica_nao_cadastra_solucao_defeito     = array_merge($fabricas_contrato_lite, array(2,14,19,20,86,94,106,120,201,122,114,123,124,125,126,127,128,129,131,132,134,136,137,140,143));

// Clientes que usam a nova tela de Relacionamento de Integridade
$fabrica_usa_rel_diag_new = (in_array($login_fabrica, array(2, 7, 15, 25, 28, 30, 35, 40, 42, 43, 45, 46, 47)) or ($login_fabrica > 49 and !in_array($login_fabrica, array(59, 66,120,201, 172)))) ? array($login_fabrica) : array(0);
$fabrica_integridade_peca = array(5,15,24,50);

// Habilita o Laudo Técnico (cadastro de questionário)
$fabrica_pede_laudo_tecnico = array(1, 19, 43, 46, 56, 57, 58);

// Cadastra S/N
$fabrica_cadastra_num_serie   = array(74,85,90,94,95,106,108,111,120,201,138,145,146,148,149,150,156,157,158,165,167,203);
// Upload de arquivo para importação de S/N
$fabrica_integra_serie_upload = array(95,108,111,120,201,146,149,150,154,156,165,167,203);
// Máscaras de Número de série
$fabrica_usa_mascara_serie    = array(3, 14, 66, 99, 101, 140,141,144,151,153); // HD 86636 HD 264560
// Fábrica cadastra Número de série para peças
$fabrica_cadastra_serie_pecas = array(95,108,111);

//hd 19043 - Selecionei as fábricas que usam tbl_subproduto e coloquei no array.
$usam_subproduto          = array(43, 8, 3, 14, 46, 17, 66, 4, 10, 2, 5);

// Fábricas que não deixam digitar o defeito reclamado,
// tem que cadastrar e manter a lista de defeitos reclamados
$sql = "SELECT pedir_defeito_reclamado_descricao
          FROM tbl_fabrica
         WHERE fabrica = $login_fabrica
           AND (pedir_defeito_reclamado_descricao IS NULL
            OR pedir_defeito_reclamado_descricao  IS FALSE);";
$res = @pg_exec($con,$sql);

$fabrica_seleciona_defeito_reclamado = (
    (@pg_numrows($res) > 0 or $novaTelaOs or
    in_array($login_fabrica, array(15,35,42,74,81,123,134, 86,96,114,115,116,125,126,129,137, 140)) or $telecontrol_distrib)
    and !in_array($login_fabrica, array(117,124,126,127,128))
);

// Cadastro de clientes Admin

$fabrica_tem_clientes_admin  = array(7,30,52,85,96,156,158,167,190,191,203);
$fabrica_pedido_loja_virtual = array(3, 10, 35);

/* INFORMAÇÕES DE PROBLEMAS COM O CADASTRO DE PEÇAS E PRODUTOS */
// Configuração padrão das tabelas de dados
$tblAttrs = array(
	// adicionar na tableAttrs a class 'oculta' para iniciar as tabelas colapsadas
	'tableAttrs'   => ' align="center" width="850" cellspacing="1" class="tabela"',
	'captionAttrs' => ' class="titulo_coluna"',
	'headerAttrs'  => ' class="titulo_coluna"',
	'rowAttrs'     => ' align="left"',
);
#----------------- Produtos sem Linha ---------------
if (in_array($login_fabrica, $vet_produto_sem_linha)) {

	$sql = "SELECT produto, referencia, descricao
			  FROM tbl_produto
			  JOIN tbl_linha USING (linha)
			 WHERE tbl_linha.fabrica = $login_fabrica";
	if ($login_fabrica == 43) {
		$sql .= " AND tbl_produto.linha = '466'";
	} else {
		$sql .= " AND tbl_produto.linha IS NULL";
	}
	$sql .= " AND tbl_produto.ativo IS TRUE ";

	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$prod_sem_linha = pg_fetch_all($res);

		foreach($prod_sem_linha as $rec) {
			extract($rec, EXTR_PREFIX_ALL, 'psl');

			$tbl_sem_linha[] = array(
				'Referência' => "<a href='produto_cadastro.php?produto=$psl_produto'>$psl_referencia</a>",
				'Descrição'  => $psl_descricao
			);
		}
		$tbl_sem_linha['attrs'] = $tblAttrs; // Copia a config.

		echo array2table($tbl_sem_linha, 'Produtos sem Linha');

	}
}

#----------------- Produtos sem Capacidade Divisão---------------
if (in_array($login_fabrica, $vet_produto_sem_capacidade_divisao)) {

	$sql = "SELECT produto, referencia, descricao
			  FROM tbl_produto
			  JOIN tbl_linha USING (linha)
			 WHERE tbl_linha.fabrica = $login_fabrica
			 AND tbl_produto.ativo IS TRUE
			 AND (tbl_produto.divisao isnull or tbl_produto.capacidade isnull) ";

	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		$prod_sem_linha = pg_fetch_all($res);

		foreach($prod_sem_linha as $rec) {
			extract($rec, EXTR_PREFIX_ALL, 'pscd');

			$tbl_sem_linha[] = array(
				'Referência' => "<a href='produto_cadastro.php?produto=$pscd_produto'>$pscd_referencia</a>",
				'Descrição'  => $pscd_descricao
			);
		}
		$tbl_sem_linha['attrs'] = $tblAttrs; // Copia a config.

		echo array2table($tbl_sem_linha, 'Produtos sem Capacidade ou Divisão');

	}
}

#----------------- Produtos sem Familia ---------------
if (in_array($login_fabrica, $vet_produto_sem_familia)) {

	$sql = "SELECT produto, referencia, descricao
			  FROM tbl_produto
			  JOIN tbl_linha USING (linha)
			 WHERE tbl_linha.fabrica   = $login_fabrica
			   AND tbl_produto.familia IS NULL
			   AND tbl_produto.ativo   IS TRUE ";

	if ($login_fabrica == 14) {
		$sql .= " AND tbl_produto.abre_os IS TRUE AND SUBSTR(tbl_produto.referencia,1,1) = '4'";
	}

	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		$prod_sem_familia = pg_fetch_all($res);

		foreach($prod_sem_familia as $rec) {
			extract($rec, EXTR_PREFIX_ALL, 'psf');

			$tbl_sem_familia[] = array(
				'Referência' => "<a href='produto_cadastro.php?produto=$psf_produto'>$psf_referencia</a>",
				'Descrição'  => $psf_descricao
			);
		}
		$tbl_sem_familia['attrs'] = $tblAttrs; // Copia a config.
		echo array2table($tbl_sem_familia, 'Produtos sem Família');
	}
}

#----------------- Produtos sem Mão-de-Obra ---------------
if (in_array($login_fabrica, $vet_produto_sem_mo)) {

	$sql = "SELECT produto, referencia, descricao
			  FROM tbl_produto
			  JOIN tbl_linha USING (linha)
			 WHERE tbl_linha.fabrica = $login_fabrica
			   AND (tbl_produto.mao_de_obra IS NULL OR tbl_produto.mao_de_obra = 0)
			   AND tbl_produto.ativo IS NOT FALSE  ";

	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		$prod_sem_mo = pg_fetch_all($res);

		foreach($prod_sem_mo as $rec) {
			extract($rec, EXTR_PREFIX_ALL, 'psm');

			$tbl_sem_mo[] = array(
				'Referência' => "<a href='produto_cadastro.php?produto=$psm_produto'>$psm_referencia</a>",
				'Descrição'  => $psm_descricao
			);
		}
		$tbl_sem_mo['attrs'] = $tblAttrs; // Copia a config.
		echo array2table($tbl_sem_mo, 'Produtos sem Mão-de-Obra');
	}
}

#------------ Produtos sem preço ------------
if (in_array($login_fabrica, $vet_produto_sem_preco)) {

	$sql = "SELECT tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao
				FROM tbl_peca
				LEFT JOIN tbl_tabela_item USING(peca)
				WHERE tbl_peca.fabrica = $login_fabrica
				AND   tbl_peca.referencia in(SELECT referencia FROM tbl_produto JOIN tbl_linha USING(linha) WHERE fabrica = $login_fabrica)
				AND   tbl_peca.produto_acabado = 'f'
				AND   tbl_tabela_item.preco IS NULL";

	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		$prod_sem_preco = pg_fetch_all($res);

		foreach($prod_sem_preco as $rec) {
			extract($rec, EXTR_PREFIX_ALL, 'psp');

			$tbl_sem_preco[] = array(
				'Referência' => "<a href='preco_cadastro.php?peca=$psp_produto'>$psp_referencia</a>",
				'Descrição'  => $psp_descricao
			);
		}
		$tbl_sem_preco['attrs'] = $tblAttrs; // Copia a config.
		echo array2table($tbl_sem_preco, 'Produtos sem Preço');
	}
}

#----------------Postos SEM TABELA DE PREÇO------------------
if($login_fabrica == 94){//HD-3269910
	$sqlPosto = "SELECT tbl_posto.posto,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto_linha.linha,
					tbl_posto.nome
				FROM tbl_posto_linha
				JOIN tbl_linha ON tbl_posto_linha.linha = tbl_linha.linha AND tbl_linha.fabrica = $login_fabrica
				JOIN tbl_posto ON tbl_posto_linha.posto = tbl_posto.posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE tbl_posto_linha.tabela IS NULL";
	$resPosto = pg_query($con, $sqlPosto);
	if(pg_num_rows($resPosto) > 0){
?>
		<table class='tabela oculta2' width='850' cellspacing='0' cellpadding='5' align='center'>
			<caption class='titulo_coluna2' style='font-size:14px;padding-top:5px;'><?=traduz('Postos sem tabela de preço')?></caption>
			<thead class='titulo_coluna table2' style='display: none;'>
				<th><?=traduz('Código Posto')?></th>
				<th><?=traduz('Descrição')?></th>
			</thead>
			<tbody class='table2' style='display: none;'>
			<?php
				for ($y=0; $y < pg_num_rows($resPosto); $y++) {
					$id_posto = pg_fetch_result($resPosto, $y, 'posto');
					$codigo_posto = pg_fetch_result($resPosto, $y, 'codigo_posto');
					$nome_posto = pg_fetch_result($resPosto, $y, 'nome');

					echo "<tr>
							<td class='tac'><a href='posto_cadastro.php?posto=$id_posto'>$codigo_posto</a></td>
							<td>$nome_posto</td>
						</tr>
					";
				}
			?>
			</tbody>
		</table>
<?php
	}

}

#------------ Peças sem preço ------------
if (in_array($login_fabrica, $vet_peca_sem_preco) || $login_fabrica >= 86) {

	if (in_array($login_fabrica, array(120,201))) {

		$sql_n = "SELECT tabela, descricao
				FROM tbl_tabela
				WHERE fabrica = {$login_fabrica}
				AND (tabela_garantia OR tbl_tabela.ativa);";
		$res_s = pg_query($con, $sql_n);

		$cont_psp = pg_num_rows($res_s);

	} else {

		if (in_array($login_fabrica, array(2))) {
			$tabela_garantia = " AND tabela_garantia";
		} else if (!in_array($login_fabrica, array(87))) {
			$tabela_garantia = " AND (tabela_garantia or sigla_tabela ~*'gar' or descricao ~* 'gar')";
		}

		$lista_basica = (in_array($login_fabrica, array(72))) ? "JOIN tbl_lista_basica ON tbl_peca.peca = tbl_lista_basica.peca JOIN tbl_produto ON tbl_produto.produto = tbl_lista_basica.produto AND tbl_produto.ativo " : "";

		$cont_psp = 1;
	}

	$legenda = true;
	$btn_hide = true;

	if ($cont_psp > 0) {
		for ($i = 0; $i < $cont_psp; $i++) {
			if (in_array($login_fabrica, array(120,201))) {
				$tabela_s = pg_fetch_result($res_s, $i, tabela);
				$tabela_desc = pg_fetch_result($res_s, $i, descricao);
			} else {
				$tabela_s = "SELECT tabela FROM tbl_tabela WHERE fabrica = {$login_fabrica}{$tabela_garantia}";
			}

			if($login_fabrica == 151) {
				$cond_os = " AND tbl_peca.peca in (select peca from tmp_os_peca_sem_preco where fabrica=$login_fabrica) ";
			}

			if($login_fabrica == 186){
				$cond_os = " AND tbl_peca.acessorio IS NOT TRUE ";
			}

			$sql_n_t = "SELECT DISTINCT
					tbl_peca.peca,
					tbl_peca.referencia,
UPPER(fn_retira_especiais(tbl_peca.descricao)) AS descricao
				FROM tbl_peca
				{$lista_basica}
				LEFT JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca
				AND tbl_tabela_item.tabela IN ({$tabela_s})
				WHERE tbl_peca.fabrica = {$login_fabrica}
				AND tbl_peca.produto_acabado IS NOT TRUE
				AND tbl_peca.ativo IS TRUE
				AND tbl_tabela_item.preco IS NULL
				$cond_os
				LIMIT 1000;";

			$res_n_t = pg_query($con, $sql_n_t);
			$qtde_pecas = pg_num_rows($res_n_t);

			$especiais = array('ä','ã','à','á','â','ê','ë','è','é','ï','ì','í','ö','õ','ò','ó','ô','ü','ù','ú','û','À','Á','É','Í','Ó','Ú','ñ','Ñ','ç','Ç', '');
    		$neutros   = array('a','a','a','a','a','e','e','e','e','i','i','i','o','o','o','o','o','u','u','u','u','A','A','E','I','O','U','n','n','c','C', '');


			if (pg_num_rows($res_n_t) > 0) {
				$peca_sem_preco = pg_fetch_all($res_n_t);
				foreach ($peca_sem_preco as $key => $peca) {
					if (mb_detect_encoding($peca['descricao']) == "UTF-8" && iconv("UTF-8", "ISO-8859-1", $peca['descricao'])) {
						$peca['descricao'] = utf8_decode($peca['descricao']);
					} else {
						$peca['descricao'] = $peca['descricao'];
					}
					
					$peca_sem_preco[$key] = $peca;
				}

				$pecas_sem_preco_json = json_encode($peca_sem_preco);


				$pecas_sem_preco_json = str_replace("=>","-",$pecas_sem_preco_json);
				if ($legenda == true) { ?>
					<table width='100%' cellspacing='1' align='center'>
						<tr>
							<td width='15px'>
								<div style='padding:6px;border:1px solid #eed3d7;background:#f2dede;border-radius:2px; '></div>
							</td>
							<td align='left' style='font-size:12px;'> &nbsp; <?=traduz('Peças sem preço e que foram lançadas em alguma Ordem de Serviço')?></td>
						  </tr>
					</table>
					<? $legenda = false;
				} ?>
				<input type='hidden' id='psp_json_<?= $i; ?>' name='psp_json_<?= $i; ?>' value='<?=$pecas_sem_preco_json?>' />
				<table class='tabela oculta ocultar tbl_psp_<?= $i; ?>' width='100%' cellspacing='0' cellpadding='5' align='center'>
					<caption class='titulo_coluna' style='font-size:14px;padding-top:5px;' rel="<?= $i; ?>"><?=traduz('Peças sem Preço')?><?=  (in_array($login_fabrica, array(120,201))) ? traduz(" na Tabela: ").$tabela_desc : ""; ?></caption>
				</table>
			<? }
		}

		if ($qtde_pecas == 1000) {
			$btn_hide = false;
		}

		if ($btn_hide == false) { ?>
			<div class='tac box-carregar' style='display: none;'>
				<button type='button' class='btn btn-warning btn-carregar'><?=traduz('Carregar mais Peças sem Preço')?></button>
				<div class='before-msg tac'></div>
			</div>
			<br />
		<? }
		if ($login_fabrica == 151) { ?>

			<table class='tabela oculta ocultar produtos' width='100%' cellspacing='0' cellpadding='5' align='center'>
				<caption class='p_novos' style='font-size:14px;padding-top:5px;' rel='produtos'><?=traduz('Produtos Novos')?></caption>
			</table>


			<table class='tabela oculta ocultar pecas' width='100%' cellspacing='0' cellpadding='5' align='center'>
				<caption class='p_novos' style='font-size:14px;padding-top:5px;' rel='pecas'><?=traduz('Peças Novas')?></caption>
			</table>

		<? } ?>

		<script type="text/javascript">

			<? if (!in_array($login_fabrica, array(120,201))) { ?>

				function toggle_box(box){
					if($("#desc_tabelas_"+box).is(":visible")){
						$("#exibir_"+box).show();
						$("#ocultar_"+box).hide();
						$("#desc_tabelas_"+box).hide(300);
					}else{
						$("#exibir_"+box).hide();
						$("#ocultar_"+box).show();
						$("#desc_tabelas_"+box).show(300);
					}
				}

			<? } ?>

		</script>
	<? }
}

#------------------Posto EM DESCREDENCIAMENTO-----------------
if($login_fabrica == 3){

	$sqlDesc = "SELECT  tbl_posto.posto,
				tbl_posto.nome,
				tbl_posto_fabrica.codigo_posto
			FROM tbl_posto_fabrica
			JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
			JOIN tbl_credenciamento ON tbl_posto_fabrica.posto = tbl_credenciamento.posto
			AND tbl_credenciamento.fabrica = $login_fabrica
			AND tbl_credenciamento.dias notnull
			AND tbl_credenciamento.dias <= 5
			AND tbl_credenciamento.credenciamento = (SELECT MAX(credenciamento) FROM tbl_credenciamento C WHERE C.posto = tbl_posto.posto AND C.fabrica = $login_fabrica)
			WHERE tbl_posto_fabrica.fabrica  = $login_fabrica
			AND tbl_posto_fabrica.credenciamento = 'EM DESCREDENCIAMENTO'";
	$resDesc = pg_query($con,$sqlDesc);
	if(pg_num_rows($resDesc) > 0){
		$posto_em_descredenciamento = pg_fetch_all($resDesc);

		foreach($posto_em_descredenciamento as $rec) {
			extract($rec, EXTR_PREFIX_ALL, 'ped');

			$tbl_em_descredenciamento[] = array(
				'Código Posto' => "<a href='credenciamento.php?codigo=$ped_codigo_posto&posto=$ped_posto&listar=3'>$ped_codigo_posto</a>",
				'Descrição'  => $ped_nome
			);
		}
		$tbl_em_descredenciamento['attrs'] = $tblAttrs; // Copia a config.
		echo array2table($tbl_em_descredenciamento, 'Posto Em Descredenciamento');
	}
}

#------------------Posto EM CREDENCIAMENTO-----------------
if(in_array($login_fabrica, [186])){
	$sqlCred = "SELECT  tbl_posto.posto,
				tbl_posto.nome,
				tbl_posto_fabrica.codigo_posto
			FROM tbl_posto_fabrica
			JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
			JOIN tbl_credenciamento ON tbl_posto_fabrica.posto = tbl_credenciamento.posto
			AND tbl_credenciamento.fabrica = $login_fabrica
			AND tbl_credenciamento.credenciamento = (SELECT MAX(credenciamento) FROM tbl_credenciamento C WHERE C.posto = tbl_posto.posto AND C.fabrica = $login_fabrica)
			WHERE tbl_posto_fabrica.fabrica  = $login_fabrica
			AND tbl_posto_fabrica.credenciamento = 'EM CREDENCIAMENTO'";
	$resCred = pg_query($con,$sqlCred);
	if(pg_num_rows($resCred) > 0){
		$posto_em_credenciamento = pg_fetch_all($resCred);

		foreach($posto_em_credenciamento as $rec) {
			extract($rec, EXTR_PREFIX_ALL, 'pos');

			$tbl_em_credenciamento[] = array(
				'Nome' => "<a href='credenciamento.php?codigo=$pos_codigo_posto&posto=$pos_posto&listar=3'>$pos_nome</a>"
			);
		}
		$tbl_em_credenciamento['attrs'] = $tblAttrs; // Copia a config.
		echo array2table($tbl_em_credenciamento, 'Postos Em Credenciamento');
	}
}


menuTCAdmin($menuP = include('menus/menu_cadastro_produto.php'));

if ($login_fabrica == 10 OR $login_fabrica == 35)
	echo "<center><a href='loja_virtual_adm.php'>".traduz(' Administrador da Loja Virtual')."</a></center>\n<br />\n";

//include_once 'helper.php';

menuTCAdmin($menuC = include('menus/menu_cadastro.php'));

// Monta o menu CADASTRO (Parte II)
if ($_GET['debug'] == 'array') {
	$menu = array_merge($menuP, $menuC);
	foreach($menu as $secao) {
		$total += count($secao) - 2; //'secao' e 'linha_de_separação'
	}
	echo "Total das " . count($menu) . " seções: <b>$total</b> ítens.<br />";
	if (isCLI)
		pre_echo(var_export($menu, true), 'Menu Cadastro completo');
}

include 'rodape.php';
include '../google_analytics.php';

