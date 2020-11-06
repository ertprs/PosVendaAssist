<?php
include "../dbconfig.php";
include "../includes/dbconnect-inc.php";
include 'autentica_usuario_empresa.php';

#include 'cabecalho_pop_produtos.php';

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Peça... </title>

<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>

<link href="css/posicionamento.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="jquery/jquery-latest.pack.js"></script>
<script type="text/javascript" src="jquery/thickbox.js"></script>
<link rel="stylesheet" href="jquery/thickbox.css" type="text/css" media="screen" />
<link type="text/css" rel="stylesheet" href="css/estilo.css">
</head>

<!--
<body onblur="javascript: setTimeout('window.close()',2500);" topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>
-->

<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>


<br>

<?
$tipo = trim (strtolower ($_GET['tipo']));
$empresa = trim (strtolower ($_GET['empresa']));

$tabela=trim (strtolower ($_GET['tabela']));

if (strlen($empresa)>0){
	$sql2 = "SELECT marca,fabrica,nome
			FROM   tbl_marca
			WHERE marca=$empresa";
	$res2 = pg_exec ($con,$sql2) ;
	if (pg_numrows($res2)>0) {
		$empresa   = trim(pg_result ($res2,0,fabrica));
	}
}

if ($tipo == "descricao") {
	$descricao = trim (strtoupper($_GET["descricao"]));


	echo "<h4>Pesquisando por <b>descrição do produto</b>:";
	echo "<i>$descricao</i></h4>";
	echo "<p>";
	$descricao = strtoupper($descricao);

	$sql = "SELECT  tbl_peca.peca,
					tbl_peca.referencia,
					tbl_peca.descricao,
					tbl_peca.ativo,
					tbl_peca_item.valor_venda,
					tbl_estoque.qtde as estoque,
					tbl_estoque_extra.quantidade_entregar,
					to_char(tbl_estoque_extra.data_atualizacao,'DD/MM/YYYY') as data_atualizacao
			FROM    tbl_peca
			LEFT JOIN    tbl_peca_item ON tbl_peca_item.peca=tbl_peca.peca
			LEFT JOIN tbl_estoque ON tbl_estoque.peca=tbl_peca.peca
			LEFT JOIN tbl_estoque_extra ON tbl_estoque_extra.peca = tbl_peca.peca";
	if(strlen($tabela) > 0) {
		$sql.=" LEFT JOIN tbl_tabela_item_erp ON tbl_peca.peca =tbl_tabela_item_erp.peca
				JOIN tbl_tabela ON tbl_tabela.tabela=tbl_tabela_item_erp.tabela";
			}
	$sql.=" WHERE   (tbl_peca.fabrica = $login_empresa OR tbl_peca.fabrica = $empresa)
			AND     upper(tbl_peca.descricao) like '%$descricao%' ";
			
	if(strlen($tabela) > 0) {
		$sql.=" AND tbl_tabela.tabela='$tabela'";
	}

	$sql.=" ORDER BY descricao ";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 0) {
		echo "<h1>Peça '$descricao' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}


if ($tipo == "referencia") {
	$referencia = trim (strtoupper($_GET["peca"]));
	$referencia = str_replace (".","",$referencia);
	$referencia = str_replace (",","",$referencia);
	$referencia = str_replace ("-","",$referencia);
	$referencia = str_replace ("/","",$referencia);

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>referência da peça</b>:";

	echo "<i>$referencia</i></font>";
	echo "<p>";

	
	$sql = "SELECT  tbl_peca.peca,
					tbl_peca.referencia,
					tbl_peca.descricao,
					tbl_peca.ativo,
					tbl_peca_item.valor_venda,
					tbl_estoque.qtde as estoque,
					tbl_estoque_extra.quantidade_entregar,
					to_char(tbl_estoque_extra.data_atualizacao,'DD/MM/YYYY') as data_atualizacao
			FROM    tbl_peca
			LEFT JOIN    tbl_peca_item ON tbl_peca_item.peca=tbl_peca.peca
			LEFT JOIN tbl_estoque ON tbl_estoque.peca=tbl_peca.peca
			LEFT JOIN tbl_estoque_extra ON tbl_estoque_extra.peca = tbl_peca.peca";
	if(strlen($tabela) > 0) {
		$sql.=" LEFT JOIN tbl_tabela_item_erp ON tbl_peca.peca =tbl_tabela_item_erp.peca
				JOIN tbl_tabela ON tbl_tabela.tabela=tbl_tabela_item_erp.tabela";
			}
	$sql.="	WHERE   (tbl_peca.fabrica = $login_empresa OR tbl_peca.fabrica = $empresa)
			 AND     tbl_peca.referencia ilike '%$referencia%'";
	
	if(strlen($tabela) > 0) {
		$sql.=" AND tbl_tabela.tabela='$tabela'";
	}

	$sql.=" ORDER BY descricao;";

	$res = pg_exec ($con,$sql);
//	echo $sql;
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Peça '$referencia' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}


	echo "<script language='JavaScript'>\n";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";
	
	echo "<table width='100%' border='0'>\n";
	
	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {

		$peca                = trim(pg_result($res,$i,peca));
		$referencia          = trim(pg_result($res,$i,referencia));
		$descricao           = trim(pg_result($res,$i,descricao));
		$preco               = trim(pg_result($res,$i,valor_venda));
		$estoque             = trim(pg_result($res,$i,estoque));
		$quantidade_entregar = trim(pg_result($res,$i,quantidade_entregar));
		$data_atualizacao    = trim(pg_result($res,$i,data_atualizacao));
		$ativo               = trim(pg_result($res,$i,ativo));

		$descricao = str_replace ('"','',$descricao);
		$descricao = str_replace ("'","",$descricao);

		if ($ativo == 't') {
			$mativo = "ATIVO";
		}else{
			$mativo = "INATIVO";
		}

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
	

		$cor = '#ffffff';
		if ($i % 2 <> 0) $cor = '#EEEEEE';


		echo "<tr bgcolor='$cor' class='Propaganda'>\n";

		echo "<td width='120'>";
		$num_fotos = count($fotos);
		$cont = 0;
		if ($num_fotos>0){
			foreach($fotos as $foto) {
				$cam_foto   = $foto[0];
				$cam_foto_t = $foto[1];
				$desc_foto  = $foto[2];
				$foto_id    = $foto[3];
				
				//echo " <div class='contenedorfoto'><a href='?peca=$peca&excluir_foto=$foto_id'><img src='imagens/cancel.png' width='12px' alt='Excluir foto' style='margin-right:0;float:right;align:right' /></a><a href='$cam_foto' title='$desc_foto' class='thickbox' rel='gallery-plants'><img src='$cam_foto_t' alt='$desc_foto' /><br /><span>$desc_foto</span></a></div>"; 
				if($cont==0){
					echo " <div class='contenedorfoto'><a href='$cam_foto' title='$desc_foto' class='thickbox' rel='$peca'><img src='$cam_foto_t' alt='$desc_foto' /><br /></a></div>"; 
					$cont++;
				}else{
					echo "<a href='$cam_foto' style='display:none' title='$desc_foto' class='thickbox' rel='$peca'>";
				}
			}
		
		}else{
			echo "<div class='contenedorfoto'><img src='imagens/semimagem.jpg' alt='Sem Imagem' /><br /></a></div>";
		}
		echo "</td>";


		echo "<td>";

		echo "<a href=\"javascript: descricao.value = '$descricao' ; referencia.value = '$referencia'; preco.value = '$preco' ; estoque.value = '$estoque' ; quantidade_entregar.value = '$quantidade_entregar' ; data_entrega.value = '$data_atualizacao'; descricao.focus(); this.close();\" >";
		echo "<font face='Arial, Verdana, Times, Sans' size='4' color='#0000FF'>$descricao</font>\n";
		echo "</a><br>";


		echo "Código: \n";

		echo "<a href=\"javascript: descricao.value = '$descricao' ; referencia.value = '$referencia' ; preco.value = '$preco' ; estoque.value = '$estoque' ; quantidade_entregar.value = '$quantidade_entregar' ; data_entrega.value = '$data_atualizacao' ; descricao.focus(); this.close() ; \" >";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$referencia</font>\n";
		echo "</A><br>";

		echo "\n";

		echo "Estoque: \n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'><b>$estoque</b></font>\n";
		echo "\n<br>";

		echo "Quantidade a Entregar: ";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'><b>$quantidade_entregar</b></font>\n";
		echo "\n<br>";
	
		echo "\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='3' color='#FF0000'><b>R$ $preco</b></font>\n";
		echo "\n";
		
		echo "\n<br>";
		echo "<font face='Arial, Verdana, Times, Sans' size='-3' color='#000000'>$mativo</font>\n";
		echo "\n";
		
		echo "</tr>\n";

	}
	echo "</table>\n";
?>

</body>
</html>
