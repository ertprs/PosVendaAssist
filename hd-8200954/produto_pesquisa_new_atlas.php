<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

if($_GET['troca_prod']){
	$os = $_GET['os'];
	$produto = $_GET['produto'];

	$sql = "UPDATE tbl_os SET produto = $produto WHERE os = $os AND fabrica = $login_fabrica AND posto = $login_posto";
	$res = pg_query($con,$sql);

	if(strlen(pg_errormessage($con) == 0)){
		echo "ok";
	} else {
		echo "NO";
	}
	exit;
}

if (strlen($_GET["form"]) > 0)	$form = trim($_GET["form"]);
$xproduto = trim (strtoupper($_GET["produto_os"]));
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Produto... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>
<script type="text/javascript" src="js/jquery.js"></script>
<script language="JavaScript">
//<!--
function retorno(referencia, descricao, serie,data_fab, produto_os,os) {
	f = opener.window.document.<? echo $form; ?>;
	window.opener.jQuery("#data_fabricacao_opener").show().find('p').html(data_fab);
	<? if($login_fabrica == 74){?>
			window.opener.jQuery("#data_descricao_opener").show().find('b').html(descricao);
			f.produto_serie.value      = serie;
			f.produto_os.value         = produto_os;
			f.data_fabricacao.value    = data_fab;
	<? } else{?>
			f.produto_referencia.value = referencia;
			f.produto_descricao.value  = descricao;
	<? }?>
	window.close();
}
// -->
</script>

</head>

<body onblur="setTimeout('window.close()',22500);" topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>

<img src="imagens/pesquisa_produto.gif">

<br>

<?

$xserie = trim (strtoupper($_GET["serie"]));
$xos = trim (strtoupper($_GET["os"]));

echo "<h4>Pesquisando por <b>número de série do produto</b>: <i>$serie</i></h4>";
echo "<p>";

$sql = "SELECT   tbl_numero_serie.referencia_produto as referencia, to_char(data_fabricacao, 'DD/MM/YYYY') as data_fabricacao, tbl_produto.descricao, tbl_numero_serie.produto
		FROM     tbl_numero_serie
		JOIN tbl_produto ON tbl_produto.produto = tbl_numero_serie.produto
		JOIN     tbl_linha USING (linha)
		WHERE    tbl_linha.fabrica = $login_fabrica
		AND      tbl_produto.ativo
		AND 	 tbl_numero_serie.serie = '$xserie'
		ORDER BY tbl_produto.descricao;";
$res = pg_exec ($con,$sql);

if (@pg_numrows ($res) == 0) {
	echo "<h1>Número de série '$xserie' não encontrado</h1>";
	echo "<script language='javascript'>";
	echo "setTimeout('window.close()',2500);";
	echo "</script>";
	exit;
}

if (pg_numrows ($res) == 1 ) {
	$referencia = trim(pg_result($res,0,referencia));
	$descricao  = trim(pg_result($res,0,descricao));
	$descricao  = str_replace ('"','',$descricao);
	$data_fab   = pg_result($res,0,'data_fabricacao');
	$produto    = trim(pg_result($res,0,produto));
	
	if($produto != $xproduto AND $login_fabrica == 74){ ?>
		<script language="JavaScript">
		if(confirm('Para o número de série informado, o produto será trocado. Deseja continuar?') == true){

				$.ajax({
					url: '<? echo $PHP_SELF;?>?troca_prod=1&produto=<? echo $produto; ?>&os=<? echo $xos; ?>',
					cache: false,
					success: function(data) {
						
						if(data == "ok"){
							opener.window.document.<? echo $form; ?>.produto_serie.value      = '<? echo $xserie; ?>';    
							opener.window.document.<? echo $form; ?>.produto_os.value         = '<? echo $produto; ?>';
							opener.window.document.<? echo $form; ?>.data_fabricacao.value         = '<? echo $data_fab; ?>';
							window.opener.jQuery("#data_fabricacao_opener").show().find('p').html('<? echo $data_fab; ?>');
							window.opener.jQuery("#produto_descricao_opener").show().find('b').html('<? echo $descricao; ?>');
							window.opener.listaDefeitos('<? echo $referencia; ?>');
							window.close();
						} else {
							alert('Erro ao trocar o produto');
							window.close();
						}
					}
				});

		}else{
			window.close();
		}
		</script>
	<?
	}else{
		echo "<script language=\"JavaScript\">\n";
		echo "<!--\n";
		echo "opener.window.document.$form.produto_serie.value      = '$xserie';     \n";
		if($login_fabrica == 74){
			echo "opener.window.document.$form.produto_os.value         = '$produto';     \n";
			echo "opener.window.document.$form.data_fabricacao.value    = '$data_fab';     \n";
			echo "window.opener.jQuery(\"#produto_descricao_opener\").show().find('p').html('".$descricao."');";
		}else{
			echo "opener.window.document.$form.produto_referencia.value = '$referencia'; \n";
			echo "opener.window.document.$form.produto_descricao.value  = '$descricao';  \n";
		}
		//data de fabricação
		echo "window.opener.jQuery(\"#data_fabricacao_opener\").show().find('p').html('".$data_fab."');";
		
		echo "window.close();\n";
		echo "// -->\n";
		echo "</script>\n";

	}
}else{
	echo "<script language='JavaScript'>\n";
	echo "<!--\n";
	echo "window.moveTo (100,100);";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";
	
	echo "<table width='100%' border='0'>";

	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$referencia = trim(pg_result($res,$i,referencia));
		$descricao  = trim(pg_result($res,$i,descricao));
		$data_fab   = pg_result($res,$i,'data_fabricacao');
		$descricao  = str_replace ('"','',$descricao);
		if($login_fabrica == 74){
			$produto    = ",'".trim(pg_result($res,$i,produto))."'";
			$xos = ",'$xos'";
		}
		
		echo "<tr>";
		
		if($produto != $xproduto AND $login_fabrica == 74){
		echo "<td>";
		echo "<a href=\"javascript: if(CONFIRM('Para o número de série informado, o produto será trocado. Deseja continuar?')){retorno('$referencia', '$descricao', '$serie','$data_fab' $produto $xos)}\">";
		echo "<font size='-1'>$referencia</font>";
		echo "</a>";
		echo "</td>";
		} else {
			echo "<td>";
			echo "<a href=\"javascript: retorno('$referencia', '$descricao', '$serie','$data_fab' $produto $xos)\">";
			echo "<font size='-1'>$referencia</font>";
			echo "</a>";
			echo "</td>";
		}
		
		echo "<td>";
		echo "<font size='-1'>$descricao</font>";
		echo "</td>";

		echo "<td>";
		echo "<font size='-1'>$serie</font>";
		echo "</td>";
		
		echo "</tr>";
	}

	echo "</table>";
}

?>

</body>
</html>
