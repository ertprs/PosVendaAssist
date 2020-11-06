<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$fale_conosco_esmaltec = trim($_REQUEST["fale_conosco_esmaltec"]);
if ($fale_conosco_esmaltec == true) {
	$login_fabrica = 30;
	$cond_ativo = " AND tbl_produto.ativo IS TRUE";
} else {
	include 'autentica_admin.php';
	$cond_ativo = "";
	include_once '../class/aws/s3_config.php';
    include_once S3CLASS;
    $s3 = new AmazonTC("produto", $login_fabrica);

}
$mapa_linha = trim($_REQUEST['mapa_linha']);
$tipo       = trim($_REQUEST['tipo']);
$pos        = trim($_REQUEST['pos']);
$familia    = preg_replace('/\D/', '', trim($_REQUEST['familia']));
?>
<!DOCTYPE HTML public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
	<title> Pesquisa Produto... </title>
	<meta name="Author" content="">
	<meta name="Keywords" content="">
	<meta name="Description" content="">
	<meta http-equiv=pragma content=no-cache>
	<link href="css/estilo_cad_prod.css" rel="stylesheet" type="text/css">
	<link href="css/posicionamento.css" rel="stylesheet" type="text/css">
	<link rel="stylesheet" type="text/css" href="js/thickbox.css" media="screen">
	<script src="js/jquery-1.3.2.js"	type="text/javascript"></script>
	<script src="js/thickbox.js"		type="text/javascript"></script>
	<script src="../plugins/jquery/jquery.tablesorter.min.js" type="text/javascript"></script>
	<style type="text/css">
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

		table.tabela tr td {
			font-family: verdana;
			font: bold 11px "Arial";
			border-collapse: collapse;
			border:1px solid #596d9b;
		}
	</style>
	<style type="text/css">

		@import "../css/lupas/lupas.css";
		body {
			margin: 0;
			font-family: Arial, Verdana, Times, Sans;
			background: #fff;
		}
	</style>
	<script type="text/javascript">
		$(document).ready(function() {
		$("#gridRelatorio").tablesorter();
	});
	</script>
</head>
<body>
<?

if ($login_fabrica == 42) {
	$tipo_atendimento = $_GET["tipo_atendimento"];

	$sql = "SELECT entrega_tecnica FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento";
	$res = pg_query($con, $sql);

	$entrega_tecnica = pg_result($res, 0, "entrega_tecnica");
}

if ($login_fabrica == 42) {
		if ($entrega_tecnica == "t") {
			$sql_entrega_tecnica = " AND tbl_produto.entrega_tecnica IS TRUE ";
		} else if ($entrega_tecnica == "f") {
			$sql_entrega_tecnica = " AND tbl_produto.entrega_tecnica IS FALSE ";
		}
}

if($login_fabrica == 1){
	$programa_troca = $_REQUEST['exibe'];
	if(preg_match("os_cadastro_troca_black.php", $programa_troca)){
		$troca_valor = 't';
	}
	$mostra_inativo =(strpos($programa_troca,"lbm_cadastro.php") !== false) ? "t" : "f";
}

//hd 285292 adicionei este bloco para pegar a marca do admin
if ($login_fabrica == 30) {

	$sql_om = "SELECT substr(tbl_marca.nome,0,6) as marca from tbl_admin join tbl_cliente_admin ON tbl_cliente_admin.cliente_admin = tbl_admin.cliente_admin join tbl_marca ON tbl_marca.marca = tbl_cliente_admin.marca where tbl_admin.admin = $login_admin";

	$res_om = pg_exec($con,$sql_om);

	if (pg_num_rows($res_om)>0) {
		$marca = pg_result($res_om,0,0);
	}

	if ($marca=='AMBEV') {
		$sql = "SELECT marca FROM tbl_produto
				JOIN tbl_marca using(marca)
				WHERE  substr(nome,1,5) = '$marca'";

		$res = pg_exec($con,$sql);

		$array_marca = array();

		for ($i=0;$i<pg_num_rows($res);$i++) {
			$array_marca[$i] .= pg_result($res,$i,0);
		}

		$marcas = implode(',',$array_marca);
	}
}

if(in_array($login_fabrica, [167, 203])){
	$sql_linha = " tbl_linha.nome AS linha_descricao, (SELECT tbl_familia.descricao FROM tbl_familia WHERE tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = {$login_fabrica}) AS familia_descricao, ";
}

if ($login_fabrica == 30 && $fale_conosco_esmaltec == true) {
	$ajuste_css = "padding-top: 10px;";
} else {
	$ajuste_css = "";
}
?>
<?php if ($login_fabrica != 30 && $fale_conosco_esmaltec != true) {?>
<div class="lp_header">
	<a href='' onclick='window.parent.Shadowbox.close();' style='border: 0;'>
		<img src='css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar' />
	</a>
</div>
<?php }?>

<div class='lp_nova_pesquisa' style="text-align: center;<?php echo $ajuste_css;?>">
	<form action='<?=$_SERVER["PHP_SELF"]?>' method="POST" name="nova_pesquisa">
		<input type="hidden" name="mapa_linha" value="<?=$mapa_linha?>" />
		<input type="hidden" name="tipo" value="<?=$tipo?>" />
		<input type="hidden" name="pos" value="<?=$pos?>" />

<?
	if ($login_fabrica == 156) {?>
		<input type="hidden" name="familia" value="<?=$familia?>" />
	<?php
	}

//hd 285292 troque *from pelos campos
if ($tipo == "descricao") {
	$descricao = trim (trim($_REQUEST["campo"]));
?>
<label>Descrição: </label><input type="text" name="campo" value="<?=$descricao?>" placeholder="Digite a descrição..." />
		<input type="submit" value="Pesquisar" />
	</form>
</div>
<?
	if(strlen($descricao) > 0 OR $login_fabrica == 156) {
		//echo "<h4>Pesquisando por <b>descrição do produto</b>: $descricao</h4>";
		//echo "<p>";
		$sql = "SELECT
				produto,
				tbl_produto.linha,";
			if($login_fabrica == 96)
			   	$sql .="referencia_fabrica as referencia, ";
			else
				$sql .="referencia, ";

			   $sql .= "
						descricao,
						garantia,
						mao_de_obra,
						troca_obrigatoria,
						numero_serie_obrigatorio,
						tbl_produto.ativo,
						off_line,
						valor_troca,
						ipi,
						capacidade,
						tbl_linha.informatica,
						$sql_linha
						voltagem,
						tbl_produto.parametros_adicionais

				FROM     tbl_produto
				JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha ";
		if($login_fabrica == 30 and strlen($login_cliente_admin)>0 ){
			$sql .= " JOIN     tbl_cliente_admin ON tbl_cliente_admin.marca = tbl_produto.marca and tbl_cliente_admin.cliente_admin = (select cliente_admin from tbl_admin where admin = $login_admin) ";
		}

		$sql .= "WHERE ( UPPER(fn_retira_especiais(tbl_produto.descricao)) LIKE UPPER('%' || fn_retira_especiais('$descricao') || '%')
						OR
                        UPPER(fn_retira_especiais(tbl_produto.nome_comercial)) LIKE UPPER('%' || fn_retira_especiais('{$descricao}') || '%'))
						AND      tbl_linha.fabrica = $login_fabrica
						AND      tbl_produto.fabrica_i = $login_fabrica
				{$cond_ativo} ";
		//comentado chamado 230 19-06			AND      tbl_produto.ativo";
		if (($login_fabrica == 1 && $mostra_inativo != "t") || in_array($login_fabrica, array(7,52,59))) {
			$sql .=  " AND      tbl_produto.ativo"; //hd 14501 22/2/2008 - HD 35014
		}
		if (!in_array($login_fabrica, array(14,59,161))) {
			$sql .= " AND      tbl_produto.produto_principal ";
		}
		if ($familia) {
			$sql .= " AND      tbl_produto.familia = $familia ";
		}

		//comentado chamado 230 honorato	if ($login_fabrica == 14) $sql .= " AND tbl_produto.abre_os IS TRUE ";
		//hd 285292 adicinei este union
		if ($login_fabrica == 30 and $marca == 'AMBEV') {
			$sql .= " UNION
						SELECT
						produto,
						tbl_produto.linha,
						referencia,
						descricao,
						garantia,
						mao_de_obra,
						numero_serie_obrigatorio,
						tbl_produto.ativo,
						off_line,
						valor_troca,
						ipi,
						capacidade,
						voltagem,
						tbl_produto.parametros_adicionais
						FROM tbl_produto where tbl_produto.marca in ($marcas) and (tbl_produto.descricao ilike '%$descricao%' or tbl_produto.nome_comercial ilike '%$descricao%') {$cond_ativo}";
		}

		$sql .= " ORDER BY 4 ";
		$res = pg_exec ($con,$sql);
		if (!pg_num_rows($res)) {
		?>
		<div class='lp_msg_erro'>Produto com a descrição '<?=$descricao?>' não encontrado</div>
		<?
	}

	} else {
		?>
		<div class='lp_msg_erro'>Informar toda ou parte da informação para realizar a pesquisa!</div>
		<?
	}
}

if ($tipo == "referencia") {
	if($login_fabrica != 96){
		$referencia = trim($_REQUEST["campo"]);
		$referencia = str_replace(".","",$referencia);
		$referencia = str_replace(",","",$referencia);
		$referencia = str_replace("'","",$referencia);
		$referencia = str_replace("''","",$referencia);
		$referencia = str_replace("-","",$referencia);
		$referencia = str_replace("/","",$referencia);
		$referencia = str_replace(" ","",$referencia);
	}else{
		$referencia = trim($_REQUEST["campo"]);
	}
?>
<label>Referência: </label><input type="text" name="campo" value="<?=$referencia?>" placeholder="Digite a referência..." />
		<input type="submit" value="Pesquisar" />
	</form>
</div>
<?
	//echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>referência do produto</b>: <i>$referencia</i></font>";
	//echo "<p>";
//hd 285292 troque *from pelos campos
	$sql = "SELECT
					produto,
					tbl_produto.linha,";
		if($login_fabrica == 96)
		   	$sql .="referencia_fabrica as referencia, ";
		else
			$sql .="referencia, ";

		   $sql .= "
					descricao,
					garantia,
					mao_de_obra,
					troca_obrigatoria,
					numero_serie_obrigatorio,
					tbl_produto.ativo,
					off_line,
					valor_troca,
					tbl_linha.informatica,
					$sql_linha
					ipi,
					capacidade,";
			// if ($login_fabrica == 52) {
			// 	$sql .= "tbl_marca.nome as marca,";
			// }


		$sql .= "	tbl_produto.marca as marca,
					  voltagem,
				tbl_produto.parametros_adicionais
			FROM     tbl_produto
 			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha ";
 			// if ($login_fabrica == 52) {
 			// 	$sql .= "LEFT JOIN tbl_marca on tbl_marca.marca = tbl_produto.marca and tbl_marca.fabrica = $login_fabrica";
 			// }
	if($login_fabrica == 30 and strlen($login_cliente_admin)>0 ){
		$sql .= " JOIN     tbl_cliente_admin ON tbl_cliente_admin.marca = tbl_produto.marca and tbl_cliente_admin.cliente_admin = (select cliente_admin from tbl_admin where admin = $login_admin) ";
	}
	if($login_fabrica != 96){
		$sql .= " WHERE    tbl_produto.referencia_pesquisa ILIKE '%$referencia%'
			AND      tbl_linha.fabrica = $login_fabrica
			{$cond_ativo}
			AND tbl_produto.fabrica_i = $login_fabrica
				$sql_entrega_tecnica";
	}else{
			$sql .= " WHERE (tbl_produto.referencia LIKE '%$referencia%' OR UPPER(tbl_produto.referencia_fabrica) LIKE UPPER('%$referencia%'))
				AND      tbl_linha.fabrica = $login_fabrica";
	}

	if (($login_fabrica == 1 && $mostra_inativo != "t") || in_array($login_fabrica, array(7,52,59))) {
		$sql .=  " AND      tbl_produto.ativo is true"; //hd 14501 22/2/2008 - HD 35014
	}
	if (!in_array($login_fabrica, array(14,59,161))) {
		$sql .= " AND      tbl_produto.produto_principal  is true";
	}
	if ($familia) {
		$sql .= " AND      tbl_produto.familia = $familia ";
	}

	//hd 285292 adicionei o union
	if ($login_fabrica == 30 and $marca == 'AMBEV') {
		$sql .= " UNION
					SELECT
					produto,
					tbl_produto.linha,
					referencia,
					descricao,
					garantia,
					mao_de_obra,
					numero_serie_obrigatorio,
					tbl_produto.ativo,
					off_line,
					valor_troca,
					ipi,
					capacidade,
					voltagem,
					tbl_produto.parametros_adicionais
					FROM tbl_produto where tbl_produto.marca in ($marcas)  and tbl_produto.referencia_pesquisa ilike '%$referencia%' {$cond_ativo} AND tbl_produto.fabrica_i = $login_fabrica";
	}

	$sql .= " ORDER BY";
	if ($login_fabrica == 45) {
		$sql .= " 3, ";
	}

	$sql .= " 4 ";

	$res = pg_exec ($con,$sql);

	if (!pg_num_rows($res)) {
		?>
		<div class='lp_msg_erro'>Produto com a referência '<?=$referencia?>' não encontrado</div>
		<?
	}
}


#############################TUDO#################################
if ($tipo == "tudo") {
	$campo = trim(strtoupper($_REQUEST["campo"]));
	$campo = str_replace(".","",$campo);
	$campo = str_replace(",","",$campo);
	$campo = str_replace("'","",$campo);
	$campo = str_replace("''","",$campo);
	$campo = str_replace("-","",$campo);
	$campo = str_replace("/","",$campo);

	//echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>referência do produto</b>: <i>$referencia</i></font>";
	//echo "<p>";
//hd 285292 troque *from pelos campos
	$sql = "SELECT
					produto,
					tbl_produto.linha,";
		if($login_fabrica == 96)
		   	$sql .="referencia_fabrica as referencia, ";
		else
			$sql .="referencia, ";

		   $sql .= "
					descricao,
					garantia,
					mao_de_obra,
					numero_serie_obrigatorio,
					tbl_produto.ativo,
					off_line,
					valor_troca,
					ipi,
					capacidade,
					tbl_linha.informatica,
					$sql_linha
					voltagem,
					tbl_produto.parametros_adicionais
			FROM     tbl_produto
 			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha ";
	if($login_fabrica == 30 and strlen($login_cliente_admin)>0 ){
		$sql .= " JOIN     tbl_cliente_admin ON tbl_cliente_admin.marca = tbl_produto.marca and tbl_cliente_admin.cliente_admin = (select cliente_admin from tbl_admin where admin = $login_admin) ";
	}
	$sql .= " WHERE    ( tbl_produto.referencia_pesquisa ILIKE '%$campo%' OR tbl_produto.descricao ILIKE '%$campo%')
		AND      tbl_linha.fabrica = $login_fabrica
		AND tbl_produto.fabrica_i = $login_fabrica
			$sql_abre_os
			$sql_entrega_tecnica";
	if (($login_fabrica == 1 && $mostra_inativo != "t") || in_array($login_fabrica, array(7,52,59))) {
		$sql .=  " AND      tbl_produto.ativo is true"; //hd 14501 22/2/2008 - HD 35014
	}
	if (!in_array($login_fabrica, array(14,59,161))) {
		$sql .= " AND      tbl_produto.produto_principal  is true";
	}
	if ($familia) {
		$sql .= " AND      tbl_produto.familia = $familia ";
	}

	//hd 285292 adicionei o union
	if ($login_fabrica == 30 and $marca == 'AMBEV') {
		$sql .= " UNION
					SELECT
					produto,
					tbl_produto.linha,
					referencia,
					descricao,
					garantia,
					mao_de_obra,
					numero_serie_obrigatorio,
					tbl_produto.ativo,
					off_line,
					valor_troca,
					ipi,
					capacidade,
					voltagem,
					tbl_produto.parametros_adicionais
					FROM tbl_produto where tbl_produto.marca in ($marcas)  and (tbl_produto.referencia_pesquisa ilike '%$campo%' OR tbl_produto.descricao ILIKE '%$campo%') AND tbl_produto.fabrica_i = $login_fabrica";
	}
	$sql .= " ORDER BY";
	if ($login_fabrica == 45) {
		$sql .= " 3, ";
	}

	$sql .= " 4 ";
	$res = pg_exec ($con,$sql);
	if (@pg_numrows ($res) == 0) {
		?>
		<div class='lp_msg_erro'>Informar toda ou parte da informação para realizar a pesquisa!</div>
		<?
	}
}

if(pg_num_rows($res) > 1) {

?>
<table style='width:100%; border: 0;' cellspacing='1' class='lp_tabela' id='gridRelatorio'>
	<thead>
		<tr>
			<?php if (in_array($login_fabrica, array(195))) {?>
			<th>Imagem</th>
			<?php }?>
			<th>Código</th>
			<th>Nome</th>
			<th>Voltagem</th>
			<th>Status</th>
		</tr>
	</thead>
	<tbody>
<?
	for ($i = 0; $i < pg_num_rows($res); $i++ ) {
		$produto       = trim(pg_result($res, $i, 'produto'));
		$linha         = trim(pg_result($res, $i, 'linha'));
		$linha_descricao     = trim(pg_result($res, $i, 'linha_descricao'));
		$familia_descricao   = trim(pg_result($res, $i, 'familia_descricao'));
		$descricao     = trim(pg_result($res, $i, 'descricao'));
		$voltagem      = trim(pg_result($res, $i, 'voltagem'));
		$marca_produto = trim(pg_result($res, $i, 'marca'));
		$referencia    = trim(pg_result($res, $i, 'referencia'));
		$garantia      = trim(pg_result($res, $i, 'garantia'));
		$mobra         = str_replace(".", ", ", trim(pg_result($res, $i, 'mao_de_obra')));
		$serie_obrigatorio      = trim(pg_result($res, $i, 'numero_serie_obrigatorio'));
		$ativo         = trim(pg_result($res, $i, 'ativo'));
		$off_line      = trim(pg_result($res, $i, 'off_line'));
		$capacidade    = trim(pg_result($res, $i, 'capacidade'));
		$informatica   = trim(pg_result($res, $i, 'informatica'));
		$valor_troca   = trim(pg_result($res, $i, 'valor_troca'));
		$ipi           = trim(pg_result($res, $i, 'ipi'));
		$descricao     = str_replace ('"', '', $descricao);
		$descricao     = str_replace("'",  "", $descricao);
		$descricao     = str_replace("''", "", $descricao);
		$troca_obrigatoria = trim(pg_result($res, $i, 'troca_obrigatoria'));

		if (in_array($login_fabrica, [151])) {
                        $parametros_adicionais = json_decode(pg_fetch_result($res, $i, "parametros_adicionais"), true);
                        $troca_direta              = (!empty($parametros_adicionais['troca_direta']) && $parametros_adicionais['troca_direta'] == "t") ? "t" : "f";
                }

		if (strlen($ipi)>0 AND $ipi != "0") {
			$valor_troca = $valor_troca * (1 + ($ipi /100));
		}

		$mativo = ($ativo == 't') ? "ATIVO" : "INATIVO";

		$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

		$onclick = (trim($descricao)  != '' ? "'$descricao'" : "''") .
				   (trim($referencia) != '' ? ", '$referencia'" : ", ''") .
				   (trim($voltagem)   != '' ? ", '$voltagem'" : ", ''") .
				   (trim($marca_produto)   != '' ? ", '$marca_produto'" : ", ''") .
				   (trim($produto) 	  != '' ? ", $produto" : ", ''") .
				   (($mapa_linha == 't') ? ", $linha" : ", ''") .
				   (($pos != 'undefined') ? ", '$pos'" : ", ''") .
				   (trim($informatica)   != '' ? ", '$informatica'" : ", ''") .
				   (trim($serie_obrigatorio) != 't' ? ", 'f'" : ", 't'").
				   (trim($linha_descricao)   != '' ? ", '$linha_descricao'" : ", ''").
				   (trim($familia_descricao)   != '' ? ", '$familia_descricao'" : ", ''").
				   (trim($garantia)   != '' ? ", '$garantia'" : ", ''").
				   (trim($troca_obrigatoria)   != '' ? ", '$troca_obrigatoria'" : ", ''").
				   (trim($troca_direta) != '' ? ", '" . $troca_direta . "'" : ", ''");

		echo "<tr style='background: $cor' onclick=\"";
		echo ($login_fabrica != 51) ? "window.parent.mostraDefeitos('Reclamado','$referencia','$pos');":"";
		echo "window.parent.retorna_produto($onclick); window.parent.Shadowbox.close();\">";
		$tag_imagem = "";
		if (in_array($login_fabrica, array(195))) {
			    $imagem_produto = $s3->getObjectList($produto);
			    $imagem_produto = basename($imagem_produto[0]);
			    $imagem_produto = $s3->getLink($imagem_produto);

			if (empty($imagem_produto)){
				$tag_imagem = "";
			} else {
				
					$tag_imagem = "<img src='$imagem_produto' valign='middle' alt='$referencia' style='width:70px;border: 2px solid #FFCC00' class='thickbox'  />\n";
			}



			echo "<td  style='text-align: center;'>{$tag_imagem}</td>";
		}
		echo "
				  	<td style='text-align: center;'>$referencia</td>
				  	<td style='text-align: center;'>$descricao</td>
				  	<td style='text-align: center;'>$voltagem</td>
				  	<td style='text-align: center;'>$mativo</td>
				  </tr>";

		if ($login_fabrica == 3) {
			$imagem = "imagens_produtos/$login_fabrica/pequena/$produto.jpg";
			echo "<td title='$imagem' bgcolor='#FFFFFF' align='center'>\n";
			if (file_exists("/var/www/assist/www/$imagem")) {
				$tag_imagem = "<a href='../".str_replace("pequena", "media", $imagem)."' class='thickbox'>\n";
				$tag_imagem.= "<img src='../$imagem' valign='middle' style='border: 2px solid #FFCC00' class='thickbox' height='40'></a>\n";
				echo $tag_imagem;
			}
			echo "</td>";
		}
	}
	?>
	</tbody>
</table>
<?
} else if (pg_num_rows($res) == 1) {

	 	$produto       = trim(pg_result($res,0,'produto'));
		$linha         = trim(pg_result($res,0,'linha'));
		$descricao     = trim(pg_result($res,0,'descricao'));
		$linha_descricao     = trim(pg_result($res,0,'linha_descricao'));
		$familia_descricao   = trim(pg_result($res,0,'familia_descricao'));
		$voltagem      = trim(pg_result($res,0,'voltagem'));
		$referencia    = trim(pg_result($res,0,'referencia'));
		$marca_produto = trim(pg_result($res,0,'marca'));
		$garantia      = trim(pg_result($res,0,'garantia'));
		$mobra         = str_replace(".",",",trim(pg_result($res,0,'mao_de_obra')));
		$serie_obrigatorio      = trim(pg_result($res, $i, 'numero_serie_obrigatorio'));
		$ativo         = trim(pg_result($res,0,'ativo'));
		$off_line      = trim(pg_result($res,0,'off_line'));
		$capacidade    = trim(pg_result($res,0,'capacidade'));

		if (in_array($login_fabrica, [151])) {
                        $parametros_adicionais = json_decode(pg_fetch_result($res, 0, "parametros_adicionais"), true);
                        $troca_direta              = (!empty($parametros_adicionais['troca_direta']) && $parametros_adicionais['troca_direta'] == "t") ? "t" : "f";
                }

		$valor_troca   = trim(pg_result($res,0,'valor_troca'));
		$ipi           = trim(pg_result($res,0,'ipi'));
		$informatica   = trim(pg_result($res, $i, 'informatica'));

		$descricao     = str_replace('"',  '', $descricao);
		$descricao     = str_replace("'",  "", $descricao);
		$descricao     = str_replace("''", "", $descricao);

		$onclick = (trim($descricao)  != '' ? "'$descricao'" : "''") .
				   (trim($referencia) != '' ? ", '$referencia'" : ", ''") .
				   (trim($voltagem)   != '' ? ", '$voltagem'" : ", ''") .
				   (trim($marca_produto)   != '' ? ", '$marca_produto'" : ", ''") .
				   (trim($produto) 	  != '' ? ", $produto" : ", ''") .
				   (($mapa_linha == 't') ? ", $linha" : ", ''") .
				   ((strlen($pos) > 0) ? ", '$pos'" : ", ''") .
				   (trim($informatica)   != '' ? ", '$informatica'" : ", ''") .
				   (trim($serie_obrigatorio) != 't' ? ", 'f'" : ", 't'").
				   (trim($linha_descricao)   != '' ? ", '$linha_descricao'" : ", ''").
				   (trim($familia_descricao)   != '' ? ", '$familia_descricao'" : ", ''").
				   (trim($garantia)   != '' ? ", '$garantia'" : ", ''").
				   (trim($troca_obrigatoria)   != '' ? ", '$troca_obrigatoria'" : ", ''").
				   (trim($troca_direta) != '' ? ", '" . $troca_direta . "'" : ", ''"); 
		?>
		<script type="text/javascript">
		<?= ($login_fabrica != 51) ? "window.parent.mostraDefeitos('Reclamado','$referencia','$pos');":"";?>
		window.parent.retorna_produto(<?=$onclick?>); window.parent.Shadowbox.close();
		</script>
		<?
}
?>
</body>
</html>
