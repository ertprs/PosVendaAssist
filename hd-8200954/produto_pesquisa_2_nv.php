<?php
//include "/etc/telecontrol.cfg";
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

$tipo_atendimento = $_REQUEST["tipo_atendimento"];

$linha_produto = $_REQUEST["linha_produto"]; //hd_chamado=2765193

if ($login_fabrica == 42) {
	if ($cook_tipo_posto_et == "t") {
		$entrega_tecnica = "t";
	} else if ($cook_entrega_tecnica == "f" or strlen($tipo_atendimento) == 0) {
		$entrega_tecnica = "f";
	}

	if (!empty($tipo_atendimento)){
		$sql = "SELECT entrega_tecnica FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento";
		$res = pg_query($con, $sql);

		$entrega_tecnica = pg_result($res, 0, "entrega_tecnica");
		if($entrega_tecnica == 'f' and $cook_tipo_posto_et =='f'){
			$lista_todos = 't';
		}
	}
}
// echo "Onde??";
if ($login_fabrica == 42 and $cook_tipo_posto_et == "t") {
	$entrega_tecnica = "t";
} else if ($login_fabrica == 42 and $cook_entrega_tecnica == 'f') {
	$entrega_tecnica = "f";
}

if ($login_fabrica == 11) {
	$l_mostra_produto = $_REQUEST["l_mostra_produto"];
}

if($login_fabrica == 94){
	$sql_posto = "SELECT tbl_tipo_posto.posto_interno
					FROM tbl_tipo_posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
					JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
					AND tbl_posto_fabrica.fabrica = $login_fabrica
					AND tbl_posto_fabrica.posto = $login_posto";
	$res = pg_query($con, $sql_posto);
	if(pg_last_error($con)){ $msg_erro = "Erro ao consultar o posto"; }

	if(pg_num_rows($res) > 0){
		$posto_interno = pg_fetch_result($res, 0, 'posto_interno');
	}
	if($posto_interno == 't'){
		$cond_posto_interno = "AND tbl_produto.uso_interno_ativo";
	}else{
		$cond_posto_interno = "AND tbl_produto.ativo";
		if ($login_fabrica == 3) {
			$cond_posto_interno = "AND (tbl_produto.ativo IS TRUE OR (tbl_produto.ativo IS NOT TRUE AND tbl_produto.parametros_adicionais::jsonb->>'ativacao_automatica' = 't'))";
		}
	}
}else{
	$cond_posto_interno = "AND tbl_produto.ativo";
	if ($login_fabrica == 3) {
		$cond_posto_interno = "AND (tbl_produto.ativo IS TRUE OR (tbl_produto.ativo IS NOT TRUE AND tbl_produto.parametros_adicionais::jsonb->>'ativacao_automatica' = 't'))";
	}
}

$descricao	= trim($_REQUEST["descricao"]);
if (!empty($descricao))
{
	$descricao = strtoupper($descricao);
}
$referencia	= trim($_REQUEST["referencia"]);
if (!empty($referencia))
{

	$referencia = str_replace(["-"," ","/",".",","],"",$referencia);

}
$posicao	= trim($_REQUEST["posicao"]);

	function verificaValorCampo($campo){
		return strlen($campo) > 0 ? $campo : "&nbsp;";
	}

if ($login_fabrica == 14) {
	$sql_familia = " JOIN tbl_familia on tbl_familia.familia = tbl_produto.familia ";
}
if ($login_fabrica == 30) {

	if($login_fabrica == 30){
		$join_busca_referencia = 'LEFT JOIN tbl_esmaltec_referencia_antiga ON (tbl_produto.referencia = tbl_esmaltec_referencia_antiga.referencia) ';
	}

	if (!empty($referencia)) {

		if($login_fabrica == 30){
			$or_busca_referencia = " OR tbl_esmaltec_referencia_antiga.referencia_antiga LIKE '%$referencia%' ";
		}

		$sql = "SELECT CASE WHEN tbl_produto.marca = 164 then 't' ELSE 'f' END as itatiaia
				FROM  tbl_produto
				JOIN  tbl_linha   on tbl_produto.linha   = tbl_linha.linha
				$join_busca_referencia
				WHERE (tbl_produto.referencia_pesquisa like '%$referencia%' $or_busca_referencia)
				AND   tbl_linha.fabrica = $login_fabrica
				$cond_posto_interno
				AND   tbl_produto.produto_principal;";

		$res = @pg_exec($con,$sql);

	} else {

		$sql = "SELECT CASE WHEN tbl_produto.marca = 164 then 't' ELSE 'f' END as itatiaia
				FROM  tbl_produto
				JOIN  tbl_linha   on tbl_produto.linha   = tbl_linha.linha
				WHERE (UPPER(tbl_produto.descricao)      LIKE '%$descricao%'
				    OR UPPER(tbl_produto.nome_comercial) LIKE '%$descricao%' )
				AND   tbl_linha.fabrica = $login_fabrica
				$cond_posto_interno
				AND   tbl_produto.produto_principal;";

		$res = @pg_exec($con,$sql);

	}

	if (@pg_numrows($res) > 0) {

		$itatiaia = pg_result($res,0,'itatiaia');

		if ($itatiaia == 't') {?>
			<script language="JavaScript">
				function alertaItaitaia() {
					alert('Este produto ITATIAIA não pode ser aberto Ordem de Serviço pelo Posto, somente o CALLCENTER poderá abrir. Favor entrar em contato com o CALLCENTER!');
				}
				alertaItaitaia();
			</script><?php
		}

	}

}

if ($login_fabrica == 1) {

	$programa_troca = $_REQUEST['exibe'];

	if (preg_match("os_cadastro_troca.php", $programa_troca)) {
		$troca_produto = 't';
	}

	if (preg_match("os_revenda_troca.php", $programa_troca)) {
		$revenda_troca = 't';
	}

	if (preg_match("os_cadastro.php", $programa_troca)) {
		$troca_obrigatoria_consumidor = 't';
	}

	if (preg_match("os_revenda.php", $programa_troca)) {
		$troca_obrigatoria_revenda = 't';
	}

}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title><?=traduz('pesquisa.de.produto', $con)?></title>
	<meta name="Author" content="">
	<meta name="Keywords" content="">
	<meta name="Description" content="">
	<meta http-equiv="pragma" content="no-cache">

	<link rel="stylesheet" type="text/css" href="css/lupas/lupas.css">
	<link rel="stylesheet" type="text/css" href="css/posicionamento.css">
	<link rel="stylesheet" type="text/css" href="js/thickbox.css" media="screen">
	<script type="text/javascript" src="js/jquery-1.6.2.js"></script>
	<script src="js/thickbox.js" type="text/javascript"></script>
	<script src="plugins/jquery/jquery.tablesorter.min.js" type="text/javascript"></script>


	<style type="text/css">
				body {
					margin: 0;
					font-family: Arial, Verdana, Times, Sans;
					background: #fff;
				}
	</style>
	<script type='text/javascript'>
		//função para fechar a janela caso a telca ESC seja pressionada!
		$(window).keypress(function(e) {
			if(e.keyCode == 27) {
				 window.parent.Shadowbox.close();
			}
		});

		$(document).ready(function() {
			$("#gridRelatorio").tablesorter({
                headers: {
                    5: {sorter: false}
                }
			});
		});
	</script>

</head>

<body>
	<div class="lp_header">
		<a href='javascript:window.parent.Shadowbox.close();' style='border: 0;'>
			<img src='css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar' />
		</a>
	</div>
	<div class='lp_nova_pesquisa'>
		<form action='<?=$PHP_SELF?>' method='POST' name='nova_pesquisa'>
			<input type='hidden' name='voltagem' 		 value='<?=$voltagem?>' />
			<input type='hidden' name='tipo'     		 value='<?=$tipo?>' />
			<input type='hidden' name='posicao'  		 value='<?=$posicao?>' />
			<input type='hidden' name='tipo_atendimento' value='<?=$tipo_atendimento?>' />
			<input type='hidden' name='exibe' value='<?=$_REQUEST['exibe'];?>' />

			<input type='hidden' name='linha_produto' value='<?=$linha_produto?>' /> <!-- //hd_chamado=2765193 -->
	<?php
			if ($login_fabrica == 11) {
				echo "<input type='hidden' name='l_mostra_produto' value='$l_mostra_produto' />";
			}
?>
			<table cellspacing='1' cellpadding='2' border='0'>
				<tr>
					<td>
						<label><?=traduz('referencia', $con)?></label>
						<input type='text' name='referencia' value='<?=$referencia?>' style='width: 150px' maxlength='20' />
					</td>
					<td>
						<label><?=traduz('descricao', $con)?></label>
						<input type='text' name='descricao' value='<?=$descricao?>' style='width: 370px' maxlength='80' />
					</td>
					<td colspan='2' class='btn_acao' valign='bottom'><input type='submit' name='btn_acao' value='<?=traduz('pesquisar.novamente', $con)?>' /></td>
				</tr>
			</table>
		</form>
	</div>

<?

	if ($login_fabrica == 1) {?>
		<script language="JavaScript">
			function alertaTroca() {
				alert('ESTE PRODUTO NÃO É TROCA. SOLICITAR PEÇAS E REALIZAR O REPARO NORMALMENTE. EM CASO DE DÚVIDAS ENTRE EM CONTATO COM O SUPORTE DA SUA REGIÃO.');
			}
			function alertaTrocaSomente() {
				alert('Prezado Posto, este produto é somente para troca. Gentileza cadastrar na o.s de troca específica.');
			}
		</script><?php
	}

if (preg_match('/comunicado_mostra.php/', $exibe)) {
	$exibe_makita = "t";
}

if (preg_match('/helpdesk_cadastrar.php/', $exibe)) {
	$exibe_makita = "t";
}

if ($login_fabrica == 42 AND strlen($exibe_makita) == 0) {

	$exibe_makita = strpos($exibe, "comunicado_mostra.php");

	if ($entrega_tecnica == "t") {
		$sql_entrega_tecnica = " AND tbl_produto.entrega_tecnica IS TRUE ";
	}

	if ($lista_todos =='t') {
		$sql_entrega_tecnica = "";
	}
}

if ($login_fabrica == 11 ) {
	$programa = $_REQUEST['exibe'];

	$sqlPosto = "SELECT permite_envio_produto
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING(posto)
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica
				AND tbl_posto_fabrica.posto = $login_posto";
	$resPosto = pg_query($con, $sqlPosto);

	$permite_envio_produto = pg_fetch_result($resPosto, 0, "permite_envio_produto");

	if($l_mostra_produto <> "ok" && $permite_envio_produto == "f"){
		$sql_abre_os = " AND tbl_produto.abre_os IS TRUE ";
	}

	if (preg_match("pedido_cadastro.php", $programa)) {
		$sql_abre_os = "";
	}
}

if(in_array($login_fabrica,[120,201])){ //hd_chamado=2765193
	if(strlen($linha_produto) > 0){
		$cond_linha = " AND tbl_produto.linha = $linha_produto ";
	}else{
		$cond_linha = "";
	}
}

$join_posto_linha = "";
$cond_posto_linha = "";
$programa = $_REQUEST['exibe'];

if (in_array($login_fabrica, array(139)) && preg_match("/pedido_cadastro.php|os_cadastro.php/", $programa)) {
	$cond_posto_linha = " AND tbl_posto_linha.posto = {$login_posto} AND tbl_posto_linha.ativo IS TRUE";
	$join_posto_linha = " JOIN tbl_posto_linha ON tbl_produto.linha=tbl_posto_linha.linha";
}

if (!empty($descricao)) {

	if (strlen($descricao) > 2)
	{
		echo "<div class='lp_pesquisando_por'>" . traduz('pesquisando.pela.descricao', $con, $cook_idioma, array($descricao)) . "</div>";

		if ($login_pais <> 'BR') {
			$cond1 = "";
		}

		if ($login_posto == 7214) {
			$cond_posto_interno = "tbl_produto.uso_interno_ativo";
		}

		if (in_array($login_fabrica, array(141,144))) {
			$sql_tipo_posto = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE fabrica = {$login_fabrica} AND posto = {$login_posto}";
			$res_tipo_posto = pg_query($con, $sql_tipo_posto);

			if (pg_num_rows($res_tipo_posto)) {
				$tipo_posto = json_decode(pg_fetch_result($res_tipo_posto, 0, "parametros_adicionais"), true);

				if ($tipo_posto["posto_troca"] == "t") {
					$cond_troca_obrigatoria = " AND tbl_produto.troca_obrigatoria IS TRUE ";
				}
			}
		}

		$sql = "SELECT   *
				FROM     tbl_produto
				JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
				$join_busca_referencia
				$join_posto_linha
				LEFT JOIN tbl_produto_idioma using(produto)
				LEFT JOIN tbl_produto_pais   using(produto)
				$sql_familia
				WHERE   (
					   UPPER(tbl_produto.descricao)      LIKE '%$descricao%'
					OR UPPER(tbl_produto.nome_comercial) LIKE '%$descricao%'
					OR (
						UPPER(tbl_produto_idioma.descricao) LIKE '%$descricao%'
						AND tbl_produto_idioma.idioma = '$sistema_lingua'
					)

				)
				AND      tbl_linha.fabrica = $login_fabrica
				$cond_posto_interno
				AND      tbl_produto.produto_principal
				$cond_linha
				$cond_posto_linha
				$sql_entrega_tecnica
				$sql_abre_os
				$cond_troca_obrigatoria";

		if ($login_fabrica == 14 or $login_fabrica == 66) $sql .= " AND tbl_produto.abre_os IS TRUE ";
		if ($login_fabrica == 20) $sql .= " AND tbl_produto_pais.pais = '$login_pais' ";
		if ($login_fabrica == 30) $sql .= " AND COALESCE(tbl_produto.marca, 0) <> 164 ";

		if ($login_fabrica == 14 and $login_pais=='BR') $sql .= " and upper(tbl_produto.origem) <> 'IMP' and upper(tbl_produto.origem) <> 'USA' and upper(tbl_produto.origem) <> 'ASI' ";

		$sql .= " ORDER BY tbl_produto.descricao;";
		$res = pg_exec($con,$sql);

		if (pg_numrows($res) == 0){
			if(in_array($login_fabrica,[120,201])){
				$msg_erro = "<div class='lp_msg_erro'>Nehum resultado encontrado.<br/>Verifique se o produto pertence a linha selecionada.</div>";
			}else{
				$msg_erro = "<div class='lp_msg_erro'>Nehum resultado encontrado</div>";
			}

		}

	}
	else
	{
		$msg_erro = traduz('informar.toda.parte.informacao.para.realizar.pesquisa', $con);
	}
}

if (!empty($referencia)) {

	if (strlen($referencia) > 2)
	{
		echo "<div class='lp_pesquisando_por'>" . traduz('pesquisando.pela.referencia', $con, $cook_idioma, array($referencia)) . "</div>";

		$sql = "SELECT   *
				FROM     tbl_produto
				JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
				$join_busca_referencia
				$join_posto_linha
				LEFT JOIN tbl_produto_pais   using(produto)
				$sql_familia
				WHERE    (UPPER(tbl_produto.referencia_pesquisa) LIKE UPPER('%$referencia%') $or_busca_referencia)
				AND      tbl_linha.fabrica = $login_fabrica
				$cond_posto_interno
				AND      tbl_produto.produto_principal
				$cond_posto_linha
				$sql_entrega_tecnica
				$sql_abre_os
				$cond_linha";

		if ($login_fabrica == 20) {

			$sql = "SELECT   *
				FROM     tbl_produto
				JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
				LEFT JOIN tbl_produto_pais   using(produto)
				WHERE    (tbl_produto.referencia_pesquisa LIKE '%$referencia%' OR tbl_produto.referencia_fabrica LIKE '%$referencia%')
				AND      tbl_linha.fabrica = $login_fabrica
				$cond_posto_interno
				AND      tbl_produto.produto_principal ";

		}

		if ($login_fabrica == 14 or $login_fabrica == 66) $sql .= " AND tbl_produto.abre_os IS TRUE ";
		if ($login_fabrica == 20) $sql .= " AND tbl_produto_pais.pais = '$login_pais' ";
		if ($login_fabrica == 30) $sql .= " AND COALESCE(tbl_produto.marca, 0) <> 164 ";

		// fabrica intelbras postos BR não exibir produtos importados.
		if ($login_fabrica == 14 and $login_pais=='BR') $sql .= " and upper(tbl_produto.origem) <> 'IMP' and upper(tbl_produto.origem) <> 'USA' and upper(tbl_produto.origem) <> 'ASI' ";

		if ($login_fabrica == 14 AND $login_posto <> 7214) {
			$sql .= " AND tbl_produto.linha <> 560 ";
		}

		$sql .= " ORDER BY";
		if ($login_fabrica == 45) $sql .= " tbl_produto.referencia, ";
		$sql .= " tbl_produto.descricao;";
 //echo nl2br($sql);
		$res = pg_exec($con,$sql);

		if (pg_numrows($res) == 0)
		{
			$msg_erro = traduz('nenhum.resultado.encontrado', $con);
		}

	} else {
        $msg_erro = traduz('informar.toda.parte.informacao.para.realizar.pesquisa', $con);
    }

}

if (!empty($msg_erro)){
	echo $msg_erro;
}else{
	if(pg_numrows($res) == 1){
		if($login_fabrica == 30){
			$referencia_antiga  = trim(pg_result($res, $i, 'referencia_antiga'));
		}

		$produto            = trim(pg_result($res, 0, 'produto'));
		$linha              = trim(pg_result($res, 0, 'linha'));
		$descricao          = trim(pg_result($res, 0, 'descricao'));
		$nome_comercial     = trim(pg_result($res, 0, 'nome_comercial'));
		$voltagem           = trim(pg_result($res, 0, 'voltagem'));
		$referencia         = trim(pg_result($res, 0, 'referencia'));
		$referencia_fabrica = trim(pg_result($res, 0, 'referencia_fabrica'));
		$garantia           = trim(pg_result($res, 0, 'garantia'));
		$mobra              = str_replace(".",",",trim(pg_result($res, 0, 'mao_de_obra')));
		$ativo              = trim(pg_result($res, 0, 'ativo'));
		$off_line           = trim(pg_result($res, 0, 'off_line'));
		$capacidade         = trim(pg_result($res, 0, 'capacidade'));

		$valor_troca        = trim(pg_result($res, 0, 'valor_troca'));
		$troca_garantia     = trim(pg_result($res, 0, 'troca_garantia'));
		$troca_faturada     = trim(pg_result($res, 0, 'troca_faturada'));

		$oem     			= trim(pg_result($res, 0, 'oem'));

		$descricao          = str_replace('"','',$descricao);
		$descricao          = str_replace("'","",$descricao);
		$troca_obrigatoria  = trim(pg_result($res, 0, 'troca_obrigatoria'));
		$numero_serie_obrigatorio = pg_result($res, 0, 'numero_serie_obrigatorio');

		if (strlen($produto) > 0) {
			$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto AND idioma = '$cook_idioma'";

			$res_idioma = @pg_exec($con,$sql_idioma);
			if (@pg_numrows($res_idioma) > 0) {
				$descricao  = trim(@pg_result($res_idioma, 0, 'descricao'));
			}
		}

		$mativo = ($ativo == 't') ?  "ATIVO" : "INATIVO";

		$produto_pode_trocar = 1;

		if ($troca_produto == 't' or $revenda_troca == 't') {

			if ($troca_faturada != 't' AND $troca_garantia != 't') {
				$produto_pode_trocar = 0;
			}

		}

		$produto_so_troca = 1;

		if ($troca_obrigatoria_consumidor == 't' or $troca_obrigatoria_revenda == 't') {

			if ($troca_obrigatoria == 't') {
				$produto_so_troca = 0;
			}

		}

		if ($login_fabrica == 3) {
			if (strrpos($exibe, "helpdesk_cadastrar.php") === false ) {
				echo "<script> window.parent.retorna_dados_produto('$produto','$linha','$descricao','$nome_comercial','$voltagem','$referencia','$referencia_fabrica','$garantia','$mobra','$ativo','$off_line','$capacidade','$valor_troca','$troca_garantia','$troca_faturada','$referencia_antiga','$troca_obrigatoria','$posicao', '$numero_serie_obrigatorio', '$oem'); window.parent.Shadowbox.close();</script>";
			}else{
				echo "<script> window.parent.retorna_dados_produto('$produto','$linha','$descricao','$nome_comercial','$voltagem','$referencia','$referencia_fabrica','$garantia','$mobra','$ativo','$off_line','$capacidade','$valor_troca','$troca_garantia','$troca_faturada','$referencia_antiga','$troca_obrigatoria','$posicao', '$numero_serie_obrigatorio', '$oem'); window.parent.busca_defeitos_produto(); window.parent.Shadowbox.close();</script>";
			}
		}else{
			echo "<script> window.parent.retorna_dados_produto('$produto','$linha','$descricao','$nome_comercial','$voltagem','$referencia','$referencia_fabrica','$garantia','$mobra','$ativo','$off_line','$capacidade','$valor_troca','$troca_garantia','$troca_faturada','$referencia_antiga','$troca_obrigatoria','$posicao', '$numero_serie_obrigatorio', '$oem'); window.parent.Shadowbox.close();</script>";
		}

	}

	echo "<table width='100%' border='0' cellspacing='1' cellspading='0' class='lp_tabela' id='gridRelatorio'>";
		echo "<thead>";
			echo "<tr>";
				if ($login_fabrica == 30)
					echo "<th width='20%'>Referência Antiga</th>";
				echo "<th width='20%'>" . traduz('codigo', $con) . "</th>";
				if ($login_fabrica == 20)
					echo "<th width='20%'>Referência Fabrica</th>";
				echo "<th width='40%'>" . traduz('nome', $con) . "</th>";
				echo "<th width='10%'>" . traduz('status', $con) . "</th>";
				echo "<th width='10%'>" . traduz('voltagem', $con) . "</th>";
				echo "<th width='10%'>" . traduz('ativo', $con) . "</th>";
				if ($login_fabrica==3)
				echo "<th width='10%'>" . traduz('imagem', $con) . "</th>";
			echo "</tr>";
		echo "</thead>";
		echo "<tbody>";

	for ($i = 0; $i < pg_numrows($res); $i++) {

		if($login_fabrica == 30){
			$referencia_antiga  = trim(pg_result($res, $i, 'referencia_antiga'));
		}

		$produto            = trim(pg_result($res, $i, 'produto'));
		$linha              = trim(pg_result($res, $i, 'linha'));
		$descricao          = trim(pg_result($res, $i, 'descricao'));
		$nome_comercial     = trim(pg_result($res, $i, 'nome_comercial'));
		$voltagem           = trim(pg_result($res, $i, 'voltagem'));
		$referencia         = trim(pg_result($res, $i, 'referencia'));
		$referencia_fabrica = trim(pg_result($res, $i, 'referencia_fabrica'));
		$garantia           = trim(pg_result($res, $i, 'garantia'));
		$mobra              = str_replace(".",",",trim(pg_result($res, $i, 'mao_de_obra')));
		$ativo              = trim(pg_result($res, $i, 'ativo'));
		$off_line           = trim(pg_result($res, $i, 'off_line'));
		$capacidade         = trim(pg_result($res, $i, 'capacidade'));

		$valor_troca        = trim(pg_result($res, $i, 'valor_troca'));
		$troca_garantia     = trim(pg_result($res, $i, 'troca_garantia'));
		$troca_faturada     = trim(pg_result($res, $i, 'troca_faturada'));

		$descricao = str_replace('"','',$descricao);
		$descricao = str_replace("'","",$descricao);
		$troca_obrigatoria= trim(pg_result($res, $i, 'troca_obrigatoria'));

		if (strlen($produto) > 0) {
			$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = '$sistema_lingua'";

			$res_idioma = @pg_exec($con,$sql_idioma);
			if (@pg_numrows($res_idioma) >0) {
				$descricao  = trim(@pg_result($res_idioma, 0, 'descricao'));
			}
		}

		$mativo = ($ativo == 't') ?  "ATIVO" : "INATIVO";

		$produto_pode_trocar = 1;

		if ($troca_produto == 't' or $revenda_troca == 't') {

			if ($troca_faturada != 't' AND $troca_garantia != 't') {
				$produto_pode_trocar = 0;
			}

		}

		$produto_so_troca = 1;

		if ($troca_obrigatoria_consumidor == 't' or $troca_obrigatoria_revenda == 't') {

			if ($troca_obrigatoria == 't') {
				$produto_so_troca = 0;
			}

		}

		$cor = ($i % 2 <> 0) ? '#F7F5F0' : '#F1F4FA';

		if ($login_fabrica == 11) {
			$num = pg_numrows($res);
			if ($num>1) {
				$msg_confirma = "if (confirm('Atenção \\nEste modelo possui mais de uma versão!\\nVerifique na etiqueta do produto o modelo e\\nCertifique-se que está sendo imputada a versão correta na OS.')==true){";
				$chave = "}";
			}
		}

		if ($login_fabrica == 3) {
			if (strrpos($exibe, "helpdesk_cadastrar.php") === false ) {
				$onclick = "onclick= \"javascript: $msg_confirma window.parent.retorna_dados_produto('$produto','$linha','$descricao','$nome_comercial','$voltagem','$referencia','$referencia_fabrica','$garantia','$mobra','$ativo','$off_line','$capacidade','$valor_troca','$troca_garantia','$troca_faturada','$referencia_antiga','$troca_obrigatoria','$posicao','$numero_serie_obrigatorio'); window.parent.Shadowbox.close(); $chave\"";
			}else{
				$onclick = "onclick= \"javascript: $msg_confirma window.parent.retorna_dados_produto('$produto','$linha','$descricao','$nome_comercial','$voltagem','$referencia','$referencia_fabrica','$garantia','$mobra','$ativo','$off_line','$capacidade','$valor_troca','$troca_garantia','$troca_faturada','$referencia_antiga','$troca_obrigatoria','$posicao','$numero_serie_obrigatorio'); window.parent.busca_defeitos_produto(); window.parent.Shadowbox.close(); $chave\"";
			}
		}else{
			$onclick = "onclick= \"javascript: $msg_confirma window.parent.retorna_dados_produto('$produto','$linha','$descricao','$nome_comercial','$voltagem','$referencia','$referencia_fabrica','$garantia','$mobra','$ativo','$off_line','$capacidade','$valor_troca','$troca_garantia','$troca_faturada','$referencia_antiga','$troca_obrigatoria','$posicao','$numero_serie_obrigatorio'); window.parent.Shadowbox.close(); $chave\"";
		}


		echo "<tr bgcolor='$cor' $onclick>\n";

		if($login_fabrica == 30){
			echo "<td><font size='1'>Ref. Ant.: $referencia_antiga</font> </td>\n";
		}

		echo "<td>\n";

		if ($produto_pode_trocar == 0) {
			echo "<a href='javascript:alertaTroca()'><font face='Arial, Verdana, Times, Sans' size='-1' color='#6A6A6A'>$referencia</font></a>\n";
		} else if($produto_so_troca == 0) {
			echo "<a href='javascript:alertaTrocaSomente()'><font face='Arial, Verdana, Times, Sans' size='-1' color='#6A6A6A'>$referencia</font></a>\n";
		} else {

			if ($login_fabrica == 11) {
				$num = pg_numrows($res);
				if ($num>1) {
					$msg_confirma = "if (confirm('Atenção \\nEste modelo possui mais de uma versão!\\nVerifique na etiqueta do produto o modelo e\\nCertifique-se que está sendo imputada a versão correta na OS.')==true){";
					$chave = "}";
				}
			}
			echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$referencia</font>\n";
		}

		echo "</td>\n";

		if ($login_fabrica == 20) {
			echo "<td>\n";
			if (strlen($referencia_fabrica) > 0) {
				echo "<font size='1' color='#AAAAAA'>Bare Tool</font><br />";
			}
			echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'> $referencia_fabrica </font>\n";
			echo "</td>\n";
		}

		if ($login_fabrica == 14 or ($login_fabrica == 66 and $subproduto_consulta)) {

				#------------ Pesquisa de Produto Pai para INTELBRÁS -----------
				if ($login_fabrica == 66) {

					$sql = "SELECT DISTINCT tbl_produto.produto, tbl_produto.descricao, tbl_produto.referencia
							   FROM     tbl_produto
							   JOIN     tbl_subproduto ON tbl_subproduto.produto_pai = tbl_produto.produto
							   WHERE    tbl_subproduto.produto_pai = $produto
							   $cond_posto_interno
							   ORDER BY tbl_produto.descricao";

				} else {

					$sql = "SELECT tbl_produto.produto, tbl_produto.descricao, tbl_produto.referencia
							   FROM     tbl_produto
							   JOIN     tbl_subproduto ON tbl_subproduto.produto_pai = tbl_produto.produto
							   WHERE    tbl_subproduto.produto_filho = $produto
							   $cond_posto_interno ";

					if ($login_fabrica == 14 and $login_pais=='BR') $sql .= " and upper(tbl_produto.origem) <> 'IMP' and upper(tbl_produto.origem) <> 'USA' and upper(tbl_produto.origem) <> 'ASI' ";

					$sql .= "ORDER BY tbl_produto.descricao";

				}

				$resX = pg_exec($con,$sql);

				if (pg_numrows($resX) == 0) {

					echo "<td>";
					echo "<a href=\"javascript: descricao.value = '$descricao' ; referencia.value = '$referencia' ; if (window.voltagem) { voltagem.value = '$voltagem' ; } ;descricao.focus(); this.close() ; \" >";
					echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$descricao</font>\n";
					echo "</a>";

				} else {

					echo "<td>";

				}

				for ($x = 0; $x < pg_numrows($resX); $x++) {

					$produto_pai    = trim(pg_result($resX, $x, 'produto'));
					$descricao_pai  = trim(pg_result($resX, $x, 'descricao'));
					$referencia_pai = trim(pg_result($resX, $x, 'referencia'));

					$descricao_pai = str_replace('"','',$descricao_pai);
					$sql = "SELECT tbl_produto.produto, tbl_produto.descricao, tbl_produto.referencia
								   FROM     tbl_produto
								   JOIN     tbl_subproduto ON tbl_subproduto.produto_filho = tbl_produto.produto
								   WHERE    tbl_subproduto.produto_pai = $produto_pai
								   $cond_posto_interno
								   ORDER BY tbl_produto.descricao";
					$resZ = pg_exec($con,$sql);
					if (pg_numrows($resZ) == 0) {
						echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'></font>\n";
						echo "<a href=\"javascript: descricao.value = '$descricao' ; referencia.value = '$referencia' ; if (window.voltagem) { voltagem.value = '$voltagem'} ; if (window.referencia_pai) { referencia_pai.value = '$referencia_pai' } ; if (window.descricao_pai) { descricao_pai.value = '$descricao_pai' } ; descricao.focus();this.close() ; \" >";
						echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$descricao </font>\n";
						echo "</a>\n";
					}else{
						echo "<a href=\"javascript: $msg_confirma descricao.value = '$descricao' ; referencia.value = '$referencia' ; voltagem.value = '$voltagem'; if (window.capacidade){ capacidade.value = '$capacidade';} descricao.focus();this.close() ; $chave \" >";
						echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$descricao</font>\n";
						break;
					}

					if (1==2) {
						for ( $z = 0 ; $z < pg_numrows($resZ) ; $z++ ) {
							$produto_avo    = trim(pg_result($resZ, $z, 'produto'));
							$descricao_avo  = trim(pg_result($resZ, $z, 'descricao'));
							$referencia_avo = trim(pg_result($resZ, $z, 'referencia'));

							$descricao_avo = str_replace('"','',$descricao_avo);
							echo "<br />";
							echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|___> </font>\n";
							echo "<a href=\"javascript: descricao.value = '$descricao_avo' ; referencia.value = '$referencia_avo' ; if (window.voltagem) { voltagem.value = '$voltagem'} ; if (window.referencia_pai) { referencia_pai.value = '$referencia_pai' } ; if (window.descricao_pai) { descricao_pai.value = '$descricao_pai' } ; if (window.referencia_avo) { referencia_avo.value = '$referencia_avo' } ; if (window.descricao_avo) { descricao_avo.value = '$descricao_avo' } ; descricao.focus();this.close() ; \" >";
							echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$descricao_avo </font>\n";
							echo "</a>\n";
						}
					}
				}
				echo "</td>";
		}else{
			echo "<td>";

			if($produto_pode_trocar ==0){
				echo "<a href='javascript:alertaTroca()'><font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$descricao</font></a>\n";
			}elseif($produto_so_troca ==0){
				echo "<a href='javascript:alertaTrocaSomente()'><font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$descricao</font></a>\n";
			}else{
				if ($login_fabrica == 11) {
					$num = pg_numrows($res);
					if ($num>1) {
						$msg_confirma = "if (confirm('Atenção \\nEste modelo possui mais de uma versão!\\nVerifique na etiqueta do produto o modelo e\\nCertifique-se que está sendo imputada a versão correta na OS.')==true){";
						$chave = "}";
					}
				}
				echo "$descricao";
			}
				echo "</td>";
		}

		echo "<td>$nome_comercial</td>";
		echo "<td>$voltagem</td>";
		echo "<td>$mativo</td>";
		$imagem = "imagens_produtos/$login_fabrica/pequena/$produto.jpg";
		if ($login_fabrica==3) {
				echo "<td title='$imagem' align='center'>\n";
		    if (file_exists("/var/www/assist/www/$imagem")) {
		        $tag_imagem = "<a href='".str_replace("pequena", "media", $imagem)."' class='thickbox'>\n";
				$tag_imagem.= "<img src='$imagem' valign='middle' alt='$referencia' style='border: 2px solid #FFCC00' class='thickbox' height='40' /></a>\n";
			} else {
				$tag_imagem = '&nbsp;';
			}
				echo "$tag_imagem</td>\n";
		}
		echo "</tr>\n";
	}
	echo "</tbody>";
	echo "</table>\n";
}
?>

</body>
</html>

<?php
// Fechando explicitamente a conexão com o BD
if (is_resource($con)) {
    pg_close($con);
}
?>
