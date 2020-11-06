<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Produto Troca... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>

<link href="css/posicionamento.css" rel="stylesheet" type="text/css" />
<link href="css/ebano.css" rel="stylesheet" type="text/css" />

</head>

<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>

<br>

<img src="imagens/pesquisa_produtos.gif">

<?

$referencia_produto = trim($_GET['referencia_produto']);
$voltagem_produto = trim($_GET['voltagem_produto']);
$tipo = trim($_GET['tipo']);

$referencia = strtoupper(trim($_GET['referencia']));
$descricao = strtoupper(trim($_GET['descricao']));

if ($login_fabrica == 1) {

	$sql = "SELECT produto 
			FROM tbl_produto
			WHERE referencia = '{$referencia_produto}'
			AND fabrica_i = {$login_fabrica}";
	$res = pg_query($con, $sql);

	$produto_id = pg_fetch_result($res, 0, 'produto');

	$sql = "SELECT tbl_produto_troca_opcao.produto, produto_opcao
			FROM tbl_produto_troca_opcao
			JOIN tbl_produto ON tbl_produto.produto = tbl_produto_troca_opcao.produto_opcao
			AND tbl_produto.fabrica_i = {$login_fabrica} AND tbl_produto.ativo IS TRUE 
			WHERE tbl_produto_troca_opcao.produto = {$produto_id}";
	$res = pg_query($con, $sql);

	//pegar somente o primeiro registro para validar no if
	$produto       = pg_fetch_result($res, 0, 'produto');
	$produto_opcao = pg_fetch_result($res, 0, 'produto_opcao');

	$qtde_produtos_troca = pg_num_rows($res);

	if ($qtde_produtos_troca > 1 || ($qtde_produtos_troca == 1 && $produto != $produto_opcao)) {
		?>
				<div class="alert" style="background-color: #ffd20a;color: black;text-align: center;">
	        <h5><?= traduz("O.modelo.de.origem.esta.indisponivel") ?>.<br /> <?= traduz("selecione.abaixo.o.modelo.desejado") ?></h5>
	    </div>
	<?php 
	} 
}

if(strlen($voltagem_produto) > 0) {
	$cond = " AND   UPPER(voltagem)   = UPPER('$voltagem_produto')";
}

$sql = "SELECT produto 
		FROM tbl_produto
		JOIN tbl_linha USING(linha)
		WHERE fabrica = $login_fabrica
		AND   UPPER(referencia) = UPPER('$referencia_produto')
		$cond";
$res = pg_exec($con, $sql);

if (pg_numrows($res) > 0) {
	$produto = pg_result($res,0,0);
} else {
	echo "Não foi encontrado nenhum produto com a referência $referencia_produto.";
	exit;
}

$kit_controle = true; //Variável para controlar se a sql vai trazer a coluna KIT ou não. Não traz somente no caso de não ter opção de troca cadastrada, ou seja, vai trazer o próprio produto

if ($tipo == 'referencia' and strlen($referencia) > 0) {
	echo "<h4>Pesquisando opções de troca pela <b>referência</b>: <i>$referencia</i></h4>";
	echo "<p>";

		$sql = "SELECT  tbl_produto.produto, 
						tbl_produto.referencia, 
						tbl_produto.descricao, 
						tbl_produto.voltagem,
						tbl_produto_troca_opcao.kit
				FROM tbl_produto_troca_opcao
				JOIN tbl_produto    ON tbl_produto_troca_opcao.produto_opcao = tbl_produto.produto
				WHERE tbl_produto_troca_opcao.produto = $produto
				AND   upper(tbl_produto.referencia)   like '$referencia%'
				ORDER by kit, descricao";
		$res = pg_exec ($con,$sql);
		
		//se não tem nenhum opcional mostra o próprio produto
		if (pg_numrows($res)==0) {
			$sql = "SELECT  tbl_produto.produto, 
							tbl_produto.referencia, 
							tbl_produto.descricao, 
							tbl_produto.voltagem
					FROM tbl_produto    
					WHERE tbl_produto.produto = $produto
					AND   upper(tbl_produto.referencia)   like '$referencia%'
					AND   (select count(*) from tbl_produto_troca_opcao where produto = $produto) = 0 
					ORDER by descricao";
			$res = pg_exec ($con,$sql);
			$kit_controle = false;
		}
	if (pg_numrows ($res) == 0) {
		echo "<h1>Nenhum produto com a referência '$referencia' encontrado como opção de troca para o produto $referencia_produto</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}


if ($tipo == 'descricao' and strlen($descricao) > 0) {
	echo "<h4>Pesquisando opções de troca pela <b>descrição</b>: <i>$descricao</i></h4>";
	echo "<p>";

	$sql = "SELECT  tbl_produto.produto, 
					tbl_produto.referencia, 
					tbl_produto.descricao, 
					tbl_produto.voltagem,
					tbl_produto_troca_opcao.kit
			FROM tbl_produto_troca_opcao
			JOIN tbl_produto    ON tbl_produto_troca_opcao.produto_opcao = tbl_produto.produto
			WHERE tbl_produto_troca_opcao.produto = $produto
			AND   upper(tbl_produto.descricao)   like '$descricao%'
			ORDER by kit, descricao";
	$res = pg_exec ($con,$sql);
	
	//se não tem nenhum opcional mostra o próprio produto
	if (pg_numrows($res)==0) {
		$sql = "SELECT  tbl_produto.produto, 
						tbl_produto.referencia, 
						tbl_produto.descricao, 
						tbl_produto.voltagem
				FROM tbl_produto    
				WHERE tbl_produto.produto = $produto
				AND   upper(tbl_produto.descricao)   like '$descricao%'
				AND   (select count(*) from tbl_produto_troca_opcao where produto = $produto) = 0 
				ORDER by descricao";
		$res = pg_exec ($con,$sql);
		$kit_controle = false;
	}

	if (pg_numrows ($res) == 0) {
		echo "<h1>Nenhum produto com a descrição '$descricao' encontrado como opção de troca para o produto $referencia_produto</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}


if ( ($tipo=='referencia' and strlen($referencia)==0) or ($tipo=='descricao' and strlen($descricao)==0) ) {
	echo "<h4>Pesquisando opções de troca pelo produto: <i>$referencia_produto</i></h4>";
	echo "<p>";

	$sql = "SELECT  tbl_produto.produto, 
					tbl_produto.referencia, 
					tbl_produto.descricao, 
					tbl_produto.voltagem,
					tbl_produto_troca_opcao.kit
			FROM tbl_produto_troca_opcao
			JOIN tbl_produto    ON tbl_produto_troca_opcao.produto_opcao = tbl_produto.produto
			WHERE tbl_produto_troca_opcao.produto = $produto
			ORDER by kit, descricao";
	$res = pg_exec ($con,$sql);
		
	//se não tem nenhum opcional mostra o próprio produto
	if (pg_numrows($res)==0) {
		$sql = "SELECT  tbl_produto.produto, 
						tbl_produto.referencia, 
						tbl_produto.descricao, 
						tbl_produto.voltagem
				FROM tbl_produto    
				WHERE tbl_produto.produto = $produto
				AND   (select count(*) from tbl_produto_troca_opcao where produto = $produto) = 0 
				ORDER BY descricao";
		$res = pg_exec ($con,$sql);

		$kit_controle = false;
	}

	if (pg_numrows ($res) == 0) {
		echo "<h1>Nenhum produto encontrado como opção de troca para o produto $referencia_produto</h1>";
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

echo "
<style>
.kit_titulo {
	font-size: 11pt;
	background-color: #555599;
	color: #FFFFFF;
	text-decoration: none;
}

</style>
";

echo "<table style='font-family:Arial, Verdana, Sans; font-size: 10pt; color:#000000;' width='100%' border='0'>\n";
	$kit_anterior = 0;

	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$produto      = trim(pg_result($res,$i,produto));
		$referencia   = trim(pg_result($res,$i,referencia));
		$descricao    = trim(pg_result($res,$i,descricao));
		$voltagem     = trim(pg_result($res,$i,voltagem));

		if ($kit_controle) {
			$kit	      = trim(pg_result($res,$i,kit));
		}
		else {
			$kit = 0;
		}
		
		if ($kit == 0) {
			echo "<tr>\n";
				echo "<td>\n";
				echo "<font>$referencia</font>\n";
				echo "</td>\n";

				echo "<td>\n";
				echo "<a href=\"javascript:produto.value=$produto;descricao.value='$descricao';referencia.value='$referencia';voltagem.value='$voltagem';this.close();\">";
				echo "<font>$descricao</font>\n";
				echo "</a>\n";
				echo "</td>\n";

				echo "<td nowrap>\n";
				echo "<font>$voltagem</font>\n";
				echo "</td>\n";
			echo "</tr>\n";
		}
		else {
			if ($kit_anterior == 0) {
				echo "
				<tr height:20>
					<td colspan=4>
					&nbsp;
					</td>
				</tr>
				
				<tr class='etlc_instrucao'>
					<td colspan=4 align=center>
						<b>KITs:</b> Poderá ser selecionado um KIT para trocar o produto atual por vários outros.
					</td>
				</tr>
				";
			}

			if ($kit != $kit_anterior) {
				echo "
				<tr class=kit_titulo>
					<td colspan=4>
						<a class=kit_titulo href=\"javascript:produto.value=$kit;descricao.value='KIT $kit';referencia.value='KIT';voltagem.value='KIT $kit';this.close();\">KIT $kit - <u>clique aqui para selecionar este KIT</u></a>
					</td>
				</tr>
				";
			}

			echo "<tr>\n";
				echo "<td>\n";
				echo "<font>$referencia</font>\n";
				echo "</td>\n";

				echo "<td>\n";
				echo "<font>$descricao</font>\n";
				echo "</td>\n";

				echo "<td nowrap>\n";
				echo "<font>$voltagem</font>\n";
				echo "</td>\n";
			echo "</tr>\n";

			$kit_anterior = $kit;
		}
	}
echo "</table>\n";
?>

</body>
</html>
