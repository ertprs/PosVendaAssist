<?php
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Detalhes de Produto... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>

<link href="css/posicionamento.css" rel="stylesheet" type="text/css" />

</head>

<!--
<body onblur="javascript: setTimeout('window.close()',2500);" topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>
-->

<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>
<style type="text/css">
.menu_top {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	border: 0px;
	background: '#596D9B';
}
.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color: #000000;
	border: 0px;
}

.titulo {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	color: #000000;
	background: #ced7e7;
}
</style>

<br>

<img src="imagens/pesquisa_produtos.gif">
<?
//conexao com o banco

$produto= trim (strtolower ($_GET['produto']));

if (strlen($produto)>0) {

	$sql = "SELECT *
			FROM tbl_peca
			JOIN tbl_peca_item using(peca)
			WHERE tbl_peca.peca =$produto";
	$res= pg_exec($con, $sql);
	
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Produto '$descricao' não encontrado SQL: $sql </h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}else {
		echo "<table width='550' border='0'>\n";


		$peca			= trim(pg_result($res,0,peca));
		$descricao		= trim(pg_result($res,0,descricao));
		$valor_compra	= trim(pg_result($res,0,valor_compra));
		$valor_venda	= trim(pg_result($res,0,valor_venda));
		$codigo_fabrica	= trim(pg_result($res,0,referencia));
		$valor1			= trim(pg_result($res,0,preco_sugerido));

		$fotos     = '';
		$num_fotos = '';

		$sql2 = "SELECT  peca_item_foto,caminho,caminho_thumb,descricao
				FROM tbl_peca_item_foto
				WHERE peca = $peca";
		$res2 = pg_exec ($con,$sql2) ;
		$fotos = array();
		$num_fotos = pg_num_rows($res2);
		if ($num_fotos){
			for ($r=0; $r<$num_fotos ;$r++){
				$caminho        = trim(pg_result($res2,$r,caminho));
				$caminho_thum   = trim(pg_result($res2,$r,caminho_thumb));
				$foto_descricao = trim(pg_result($res2,$r,descricao));    
				$foto_id        = trim(pg_result($res2,$r,peca_item_foto));    
				
				$caminho = str_replace("/www/assist/www/erp/","",$caminho);
				$caminho_thum = str_replace("/www/assist/www/erp/","",$caminho_thum);
				
				$aux=explode("|",$caminho."|".$caminho_thum."|".$foto_descricao."|".$foto_id);               
				array_push($fotos,$aux);
			}
		}
	




		echo "<tr>\n";
		echo "<td colspan='4' >\n";
		echo "Detalhes de produto: ";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr class='menu_top'>\n";
		echo "<td  colspan='3' align='center'>\n";
		echo "<b>Produto</b>";
		echo "</td>\n";
		echo "<td colspan='3' align='center'>\n";
		echo "<b>Preço</b>";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr class='menu_top'>\n";
		echo "<td  colspan='1' width='30'>\n";
		echo "<b>Cód.</b>";
		echo "</td>\n";
		echo "<td  colspan='1' >\n";
		echo "<b>Descrição</b>";
		echo "</td>\n";
		echo "<td colspan='1' >\n";
		echo "<b>Cód. Fáb.</b>";
		echo "</td>\n";
		echo "<td colspan='1' >\n";
		echo "<b>Compra</b>";
		echo "</td>\n";
		echo "<td colspan='1' >\n";
		echo "<b>Venda</b>";
		echo "</td>\n";
		echo "</tr>\n";



		echo "<tr class='table_line'>\n";
		echo "<td colspan='1' align='center'>\n";
		echo "$produto ";
		echo "</td>\n";
		echo "<td colspan='1' align='left'>\n";
		echo "$descricao";
		echo "</td>\n";
		echo "<td colspan='1' align='center'>\n";
		echo "$codigo_fabrica";
		echo "</td>\n";
		echo "<td colspan='1' align='right'>\n";
		echo "$valor_compra";
		echo "</td>\n";
		echo "<td colspan='1' align='right'>\n";
		echo "$valor_venda";
		echo "</td>\n";

		echo "</tr>\n";
/*
		echo "<tr>\n";
		echo "<td> &nbsp;\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr class='titulo' align='center'>\n";
		echo "<td colspan='1' >\n";
		echo "<b>Ult. Vlr Pago</b>";
		echo "</td>\n";
		echo "<td colspan='3' align='center'>\n";
		echo "<b>Fornecedor</b>";
		echo "</td>\n";
		echo "<td class='titulo' colspan='2' align='center' >\n";
		echo "<b>Data da Compra</b>";
		echo "</td>\n";
		echo "</tr>\n";
*/

		// ################################# CONEXAO TECNOPLUS ####################################//

		//$con_tec = ibase_connect("201.44.49.130:/home/tecno/GBANCO.GDB","SYSDBA","masterkey");
/*		if (!$con_tec) {
			echo "Acesso Negado!<br>";
			exit;
		}

		$sql = "SELECT FIRST 5 CODFORNECEDOR,
					DATA_COMPRA,
					PRECO_PAGO
				FROM VIEW_PRECOS_PAGOS_PHP 
				WHERE codigo = $produto;";

		$result = ibase_query($con_tec, $sql);
		if (!$result){
			echo "<br>Error executando a query count!";
			exit;
		}

		$count = 0;
		while ($row[$count] = ibase_fetch_assoc($result)){
			$count++;
		}

		for($i=0; $i< $count; $i++){
			$codfornecedor	= $row[$i][CODFORNECEDOR];
			$data_compra	= $row[$i][DATA_COMPRA];
			$preco_pago		= $row[$i][PRECO_PAGO];

			// ############## CONEXAO LOCAL	###############//

			$sql = "SELECT nome,
					TO_CHAR(('1800-01-01'::date + interval'$data_compra day')::date,'DD/MM/YYYY') as data_compra
					FROM tbl_fornecedor 
					WHERE fornecedor =$codfornecedor";
			$res= pg_exec($con, $sql);

			if (@pg_numrows ($res) == 0) {
				echo "<h1>Fornecedor '$codfornecedor' não encontrado SQL: $sql </h1>";
				echo "<script language='javascript'>";
				echo "setTimeout('window.close()',2500);";
				echo "</script>";
				exit;
			}else{
				$nome			= utf8_decode(trim(pg_result($res,0,nome)));
				$data_compra	= utf8_decode(trim(pg_result($res,0,data_compra)));

				echo "<tr>\n";
				echo "<td class='table_line' align='center'>R$$preco_pago</td>\n";
				echo "<td class='table_line' colspan='3'>$nome</td>\n";
				echo "<td class='table_line' colspan='2' align='center'>$data_compra</td>\n";
				echo "</tr>\n";
			}
		}*/
		echo "</table>\n";
		echo "<BR><BR>";
		
		$num_fotos = count($fotos);
		$cont = 0;
		if ($num_fotos>0){
			foreach($fotos as $foto) {
				$cam_foto   = $foto[0];
				$cam_foto_t = $foto[1];
				$desc_foto  = $foto[2];
				$foto_id    = $foto[3];
				
				if($cont==0){
					echo "<div class='contenedorfoto'><a href='$cam_foto' title='$desc_foto' class='thickbox' rel='$peca'><img src='$cam_foto_t' alt='$desc_foto' /><br /></a></div>"; 
					$cont++;
				}else{
					echo "<a href='$cam_foto' style='display:none' title='$desc_foto' class='thickbox' rel='$peca'>";
				}
			}
		
		}else{
			echo "<div class='contenedorfoto'><img src='imagens/semimagem.jpg' alt='Sem Imagem' /><br /></a></div>";
		}

	}
}
?>

</body>
</html>