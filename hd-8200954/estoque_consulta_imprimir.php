<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include "login_unico_autentica_usuario.php";



#$title = "DETALHAMENTO DE NOTA FISCAL";
#$layout_menu = 'pedido';

#include "cabecalho.php";
?>

<html>
<head>
<title>Estoque de Peças</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
<style>
.titulo {
	font-size: 145px;
}
.titulo2 {
	font-size: 30px;
}
.sub-titulo {
	font-size: 20px;
	font-weight:bold;
}
</style>
</head>

<body>

<?

flush();

$qtde = $_POST["qtde"];
if ($qtde>0){
	$lista_pecas="";
	for ($k = 0 ; $k < $qtde; $k++) {
		$peca_x  = trim($_POST["pecas_$k"]);
		if (strlen($peca_x) > 0) {
			$lista_pecas  .= "'$peca_x',";
		}
	}
	$lista_pecas = substr($lista_pecas, 0, (strlen($lista_pecas)-1));
}


// imprimi a partir das referencia digitadas um a um na estoque_consulta.php
if (isset($_POST["lista"])){
	if ($_POST["lista"]=='sim'){
		$lista_referencias = $_POST["lista_referencias"];
		$lista_referencias = explode("\r\n",$lista_referencias);
		$lista_pecas="";
		foreach($lista_referencias as $linha) {
			if(strlen(trim($linha))>0){
				$lista_pecas  .= "'$linha',";
			}
		}
		$lista_pecas = substr($lista_pecas, 0, (strlen($lista_pecas)-1));
	}
}

// FAZ A CONSULTA COM  A PELA LOCALIZACAO, SOMENTE SE ELA TIVER + Q 2 STRING
if (strlen ($lista_pecas) > 0) {
	$sql = "SELECT	tbl_peca.referencia, 
					tbl_peca.descricao, 
					tbl_peca.ipi, 
					tbl_posto_estoque.qtde, 
					0 AS qtde_fabrica, 
					0 AS qtde_transp, 
					0 AS qtde_embarcada, 
					para.referencia AS para_referencia, 
					para.descricao AS para_descricao, 
					tbl_posto_estoque_localizacao.localizacao, 
					(SELECT tbl_tabela_item.preco FROM tbl_tabela_item WHERE tbl_tabela_item.peca = tbl_peca.peca AND tbl_tabela_item.tabela IN (SELECT tabela_posto FROM tbl_posto_linha WHERE posto = $login_posto) ORDER BY preco DESC LIMIT 1) AS preco
			FROM   tbl_peca
			LEFT JOIN tbl_posto_estoque             ON tbl_peca.peca = tbl_posto_estoque.peca AND tbl_posto_estoque.posto = $login_posto
			LEFT JOIN tbl_posto_estoque_localizacao ON tbl_peca.peca = tbl_posto_estoque_localizacao.peca AND tbl_posto_estoque_localizacao.posto = $login_posto
			LEFT JOIN tbl_depara ON tbl_peca.peca = tbl_depara.peca_de
			LEFT JOIN tbl_peca para ON tbl_depara.peca_para = para.peca
			WHERE  /* tbl_peca.fabrica = $login_fabrica AND */ 
			tbl_peca.referencia IN ($lista_pecas)
			ORDER BY tbl_posto_estoque_localizacao.localizacao";
	//echo $sql;
	$res = pg_exec ($con,$sql);
	if(pg_numrows ($res)==0){
		echo "<center><b><span class='vermelho'>$descricao </span>- NENHUM PRODUTO COM ESSA REFERÊNCIA FOI ENCONTRADO</center></b><br>";
	}
}


if (strlen($lista_pecas)>2) {
	echo '<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">';
	
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			echo '<tr >';
			echo "<td colspan='2' class='titulo' align='center'><b>".pg_result ($res,$i,referencia)."</b></td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td align='left' class='sub-titulo'>".pg_result ($res,$i,descricao)."</td>";
			echo "<td align='right' class='sub-titulo'>".pg_result ($res,$i,localizacao)."</td>";
			echo '</td><tr>';
		}
	echo "</table>";
}
flush();
?>


</body>
</html>
