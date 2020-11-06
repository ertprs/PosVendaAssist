<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

$sql = "SELECT   tbl_posto_fabrica.item_aparencia
		FROM     tbl_posto
		JOIN     tbl_posto_fabrica USING(posto)
		WHERE    tbl_posto.posto           = $login_posto
		AND      tbl_posto_fabrica.fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);

if (pg_numrows ($res) > 0) {
	$item_aparencia = pg_result($res,0,item_aparencia);
}

$subconjunto   = trim($_REQUEST['subconjunto']);
$subconjunto   = str_replace(".","",$subconjunto);
$subconjunto   = str_replace(",","",$subconjunto);
$subconjunto   = str_replace("-","",$subconjunto);
$subconjunto   = str_replace("/","",$subconjunto);
$subconjunto   = str_replace(" ","",$subconjunto);
$produto_ref   = trim($_REQUEST['produto_ref']);
$input_posicao = trim($_REQUEST['posicao']);

?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Peças pela Lista Básica ... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>
<script type="text/javascript" src="js/jquery-1.4.2.js"></script>
<link rel="stylesheet" type="text/css" href="css/posicionamento.css">
<link rel="stylesheet" type="text/css" href="js/thickbox.css" media="screen">
<script src="js/thickbox.js" type="text/javascript"></script>
<script src="plugins/jquery/jquery.tablesorter.min.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="css/lupas/lupas.css">
<script>
function listaSubconjunto(campo,produto) {
//verifica se o	browser	tem	suporte	a ajax
	try	{ajax =	new	ActiveXObject("Microsoft.XMLHTTP");}
	catch(e) { try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
		catch(ex) {	try	{ajax =	new	XMLHttpRequest();}
				catch(exc) {alert("Esse	browser	não	tem	recursos para uso do Ajax"); ajax =	null;}
		}
	}
//se tiver suporte ajax
	if(ajax) {
	//deixa	apenas o elemento 1	no option, os outros são excluídos
	campo.options.length = 1;
	//	 ajax.open("POST", "ajax_produto.php", true);
	ajax.open("GET", "ajax_defeito_constatado_intelbras.php?subconjunto="+produto, true);
	ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

	ajax.onreadystatechange	= function() {
		if(ajax.readyState == 1) {
			campo.innerHTML	= "Carregando...!";
		}
		if(ajax.readyState == 4	) {
			if(ajax.responseXML) {
				montaCombo3(campo,ajax.responseXML);
			} else {
				campo.innerHTML	= "Selecione o produto";
			}
		}
	}

	//passa	o código do	produto	escolhido
	var	params = "subconjunto="+produto;
	ajax.send(null);
	}
}
function montaCombo3(campo,result) {

	var	dataArray3	= result.getElementsByTagName("produto3");//pega a	tag	produto

	campo.innerHTML = '<option id="opcoes3"></option>';//HD 382584

	if (dataArray3.length > 0) {
	
		for (var i = 0; i < dataArray3.length; i++) {	   //percorre o	arquivo	XML	paara extrair os dados

			var item3 = dataArray3[i];
			//contéudo dos campos no arquivo XML
			var	codigo3	   =  item3.getElementsByTagName("codigo3")[0].firstChild.nodeValue;
			var	nome3 =	 item3.getElementsByTagName("nome3")[0].firstChild.nodeValue;
			//campo.innerHTML =	"Selecione o subconjunto";
			//cria um novo option dinamicamente
			var	novo3 =	document.createElement("option");
			novo3.setAttribute("id", "opcoes3");//atribui um ID	a esse elemento
			novo3.value	= codigo3;		//atribui um valor
			novo3.text	= nome3;//atribui um texto
			campo.options.add(novo3);//adiciona	o novo elemento

		}

	} else {
		campo.innerHTML = "Selecione o	subconjunto";//caso	o XML volte	vazio, printa a	mensagem abaixo
	}

}
</script>
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
		$("#gridRelatorio").tablesorter();
	}); 
</script>
</head>

<body>
<div class="lp_header">
		<a href='javascript:window.parent.Shadowbox.close();' style='border: 0;'>
			<img src='css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar' />
		</a>
</div>

<?

echo "<div class='lp_nova_pesquisa'>";
	echo "<form action='$PHP_SELF' method='POST' name='nova_pesquisa'>";
		echo "<input type='hidden' name='voltagem' value='$voltagem' />";
		echo "<input type='hidden' name='tipo' value='$tipo' />";
		echo "<input type='hidden' name='posicao' value='$posicao' />";
		echo "<input type='hidden' name='input_posicao' value='$input_posicao'>";
		echo "<input type='hidden' name='produto_ref' value='$produto_ref'>";
		echo "<center><table cellspacing='1' cellpadding='2' border='0' style='width: 400px; !important'>";
			echo "<tr>";
				echo "<td>";
					echo "Subconjunto:<br><select name='subconjunto' id='subconjunto' class='frm classeItens' style='width:220px;' onFocus='listaSubconjunto(this, $produto_ref);'>";
						echo "<option  selected></option>";
					echo "</select>";
				echo "</td>";
				echo "<td valign='center'><input type='submit' name='btn_acao' value='Pesquisar Novamente' /></td>"; 
			echo "</tr>";
		echo "</table></center>";
	echo "</form>";
echo "</div>";

if (strlen($subconjunto) > 0) {
	

	$sql =	"SELECT tbl_produto.produto    ,
					tbl_produto.referencia ,
					tbl_produto.descricao  
			FROM   tbl_produto
			JOIN   tbl_linha USING (linha)
			WHERE  tbl_produto.referencia_pesquisa = UPPER('$subconjunto')
			AND    tbl_linha.fabrica = $login_fabrica
			AND    tbl_produto.ativo IS TRUE";
	$res = pg_exec ($con,$sql);
	if (pg_numrows($res) == 1) {
		$produto            = pg_result ($res,0,produto);
		$produto_referencia = pg_result ($res,0,referencia);
		$produto_descricao  = pg_result ($res,0,descricao);
	}
}



if (strlen($produto_referencia) > 0 AND strlen($produto_descricao) > 0) {
	echo "<div class='lp_pesquisando_por'>Pesquisando toda a lista básica do produto: $produto_referencia - $produto_descricao</div>";

	if (strlen($produto) > 0) {
		$sql =	"SELECT DISTINCT
						tbl_peca.referencia      ,
						tbl_peca.descricao       ,
						tbl_lista_basica.posicao 
				FROM    tbl_lista_basica
				JOIN    tbl_peca USING (peca)
				WHERE   tbl_lista_basica.fabrica = $login_fabrica
				AND     tbl_lista_basica.produto = $produto 
				AND     tbl_peca.ativo IS NOT FALSE";
		if ($item_aparencia == 'f') $sql .= " AND tbl_peca.item_aparencia IS FALSE";
				if ($login_fabrica == 14) $sql .= " AND tbl_lista_basica.ativo IS NOT FALSE";
		$sql .= " ORDER BY tbl_peca.referencia;";
	}
	$res = pg_exec ($con,$sql);

	if (@pg_numrows($res) == 0) {
		$msg_erro = "Nenhuma lista básica de peças encontrada para este produto";
	}
}

if (!empty($msg_erro))
{
	echo "<div class='lp_msg_erro'>$msg_erro</div>";
}
else
{
	echo "<table width='100%' border='0' cellspacing='1' cellspading='0' class='lp_tabela' id='gridRelatorio'>";
		echo "<thead>";
			echo "<tr>";
				echo "<th>Posição</th>";
				echo "<th>Código</th>";
				echo "<th>Descrição</th>";
			echo "</tr>";
		echo "</thead>";
		echo "<tbody>";

for ( $i = 0 ; $i < pg_numrows($res) ; $i++ ) {
	$peca_referencia = trim(@pg_result($res,$i,referencia));
	$peca_descricao  = trim(@pg_result($res,$i,descricao));
	$posicao         = trim(@pg_result($res,$i,posicao));

	$peca_descricao_js = strtr($peca_descricao, array('"'=>'&rdquo;', "'"=>'&rsquo;'));

	$cor = ($i % 2 <> 0) ? '#F7F5F0' : '#F1F4FA';

	$onclick = "onclick= \"javascript: window.parent.retorna_lista_subconjunto('$peca_referencia','$peca_descricao','$posicao','$input_posicao'); window.parent.Shadowbox.close();\"";

	echo "<tr bgcolor='$cor' $onclick>\n";

	echo "<td nowrap align='center'>";
	echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'>$posicao</font>";
	echo "</td>\n";

	echo "<td nowrap align='center'>";
	echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'>$peca_referencia</font>";
	echo "</td>\n";

	echo "<td nowrap>";
	echo "<a href=\"javascript: posicao.value='$posicao'; referencia.value='$peca_referencia'; descricao.value='$peca_descricao_js'; this.close();\"><font face='Arial, Verdana, Times, Sans' size='1' color='#0000FF'>$peca_descricao</font></a>";
	echo "</td>\n";

	echo "</tr>\n";
}
echo "</tbody>";
echo "</table>\n";
}
?>

</body>
</html>
