<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$liberacao = $_COOKIE ['liberacao'];

if (strlen ($liberacao) == 0) {
	echo "Entrada de Senha a ser definida";
}



?>

<html>
<head>
<title>Relatório de Caixa</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
</head>

<body>

<? include 'menu.php' ?>


<center><h1>Relatório de Caixa</h1></center>

<p>


<?

flush();

if ($login_unico != 13 and $login_unico != 1 and $login_unico != 1884) {
	echo "<p>Sem permissão de acesso!</p>";
	exit;
}
if (isset($_POST['senha'])) {
	if (md5($_POST['senha']) != '7edccc661418aeb5761dbcdc06ad490c' and md5 ($_POST['senha']) != '262d889b1162761851138bb91f6c4778') {
		echo "<p>Sem permissão de acesso!</p>";
		exit;
	}
} else {?>
<center>
    <form name="logar_caixa" id="logar_caixa" method="post"
		action="<?=$PHP_SELF?>" title="Digite a senha para acessar"
accept-charset="windows-1252" enctype="application/x-www-form-urlencoded">
	<label for='senha'>Senha:</label>
	<input type="password" accesskey="S" id='senha' name="senha" class="frm">
    <button type="submit" title="Acessar" name="acao" value="Acessar">
     Acessar
    </button>
  </form>
</center>
<? exit;
}

$sql = "SELECT TO_CHAR (vencimento,'DD/MM/YYYY') AS vencimento, vencimento AS x_vencimento, total 
		FROM (SELECT tbl_contas_receber.vencimento::date AS vencimento, SUM (valor) AS total 
				FROM tbl_contas_receber 
				WHERE recebimento IS NULL AND distribuidor=$login_posto
				GROUP BY tbl_contas_receber.vencimento::date
		) x
		ORDER BY x.vencimento";


//Somente tulio e Valeria veem valores altos
if ($login_unico != 13 and $login_unico != 1) {
	$sql = "SELECT TO_CHAR (vencimento,'DD/MM/YYYY') AS vencimento, vencimento AS x_vencimento, total 
		FROM (SELECT tbl_contas_receber.vencimento::date AS vencimento, SUM (valor) AS total 
				FROM tbl_contas_receber 
				WHERE recebimento IS NULL AND distribuidor=$login_posto AND valor < 1
				GROUP BY tbl_contas_receber.vencimento::date
		) x
		ORDER BY x.vencimento";

}


$res = pg_exec ($con,$sql);


echo "<br><table align='center' border='0' cellspacing='1' cellpaddin='1'>";
echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
echo "<td>Vencimento</td>";
echo "<td>Total</td>";
echo "</tr>";

$total = 0 ;

$hoje = date ("Y-m-d");

for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

	$cor = "#cccccc";
	if ($i % 2 == 0) $cor = '#eeeeee';

	$vencto = pg_result ($res,$i,vencimento);
	$x_vencto = pg_result ($res,$i,x_vencimento);

	if ($x_vencto >= $hoje) {
		echo "<tr bgcolor='#FF9966'>";

		echo "<td> Atrasado </td>";

		echo "<td align='right' width='100'>";
		echo number_format ($total,2,",",".");
		echo "</td>";

		echo "</tr></a>";

		$hoje = "999999";
	}

	echo "<tr bgcolor='$cor' onmouseover=\"this.style.cursor='hand'; \" >";
	echo "<a href='caixa_detalhe.php?vencto=$vencto' target='_blank'>";

	echo "<td>";
	echo "<a href='caixa_detalhe.php?vencto=$vencto' target='_blank'>";
	echo pg_result ($res,$i,vencimento);
	echo "</a>";
	echo "</td>";

	echo "<td align='right' width='100'>";
	echo number_format (pg_result ($res,$i,total),2,",",".");
	echo "</td>";

	echo "</a></tr>";

	$total += pg_result ($res,$i,total);

}

$total = number_format ($total,2,",",".");

echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
echo "<td>TOTAL EM ABERTO</td>";
echo "<td align='right'>$total</td>";
echo "</tr>";

echo "</table>";

echo "<P>";
flush();

if ($login_posto == 4311) $tabelas = "116,15";

echo "<table border='1' cellpadding='3' cellspacing='0'>";

echo "<tr>";
$sql = "SELECT SUM (tbl_posto_estoque.qtde * tbl_tabela_item.preco * (1 + (tbl_peca.ipi / 100) ) ) AS total_estoque 
		FROM tbl_posto_estoque 
		JOIN tbl_peca ON tbl_posto_estoque.peca = tbl_peca.peca 
		LEFT JOIN tbl_tabela_item ON tbl_tabela_item.tabela IN ($tabelas) AND tbl_posto_estoque.peca = tbl_tabela_item.peca 
		WHERE tbl_posto_estoque.posto = $login_posto";
$res = pg_exec ($con,$sql);
echo "<tr>";
echo "<td nowrap>Total em Estoque</td>";
echo "<td align='right' nowrap>" . number_format (pg_result ($res,0,0),2,",",".") . "</td>";
echo "</tr>";




$sql = "SELECT SUM (tbl_embarque_item.qtde * tbl_tabela_item.preco * (1 + (tbl_peca.ipi / 100) ) ) AS total_estoque 
		FROM tbl_embarque_item
		JOIN tbl_embarque USING (embarque)
		JOIN tbl_peca ON tbl_embarque_item.peca = tbl_peca.peca 
		LEFT JOIN tbl_tabela_item ON tbl_tabela_item.tabela IN ($tabelas) AND tbl_embarque_item.peca = tbl_tabela_item.peca 
		WHERE tbl_embarque.distribuidor = $login_posto
		AND   tbl_embarque.faturar IS NULL
		AND   tbl_embarque_item.os_item IS NULL";
$res = pg_exec ($con,$sql);
echo "<tr>";
echo "<td nowrap>Vendas Aguardando NF </td>";
echo "<td align='right' nowrap>" . number_format (pg_result ($res,0,0),2,",",".") . "</td>";
echo "</tr>";



$sql = "SELECT SUM (tbl_embarque_item.qtde * tbl_tabela_item.preco * (1 + (tbl_peca.ipi / 100) ) ) AS total_estoque 
		FROM tbl_embarque_item
		JOIN tbl_embarque USING (embarque)
		JOIN tbl_peca ON tbl_embarque_item.peca = tbl_peca.peca 
		LEFT JOIN tbl_tabela_item ON tbl_tabela_item.tabela IN ($tabelas) AND tbl_embarque_item.peca = tbl_tabela_item.peca 
		WHERE tbl_embarque.distribuidor = $login_posto
		AND   tbl_embarque.faturar IS NULL
		AND   tbl_embarque_item.os_item IS NOT NULL";
$res = pg_exec ($con,$sql);
echo "<tr>";
echo "<td nowrap>Garantia Aguardando NF </td>";
echo "<td align='right' nowrap>" . number_format (pg_result ($res,0,0),2,",",".") . "</td>";
echo "</tr>";



$sql = "SELECT SUM (tbl_faturamento.total_nota) AS total_nota
		FROM tbl_faturamento
		WHERE tbl_faturamento.distribuidor = $login_posto
		AND   tbl_faturamento.faturamento_fatura IS NULL
		AND   tbl_faturamento.tipo_pedido = 2
		AND   tbl_faturamento.posto <> 970";
$res = pg_exec ($con,$sql);
echo "<tr>";
echo "<td nowrap>NFs Aguardando Boleto </td>";
echo "<td align='right' nowrap>" . number_format (pg_result ($res,0,0),2,",",".") . "</td>";
echo "</tr>";

$sql = "SELECT SUM (tbl_faturamento.total_nota) AS total_nota
		FROM tbl_faturamento
		WHERE tbl_faturamento.distribuidor = $login_posto
		AND   tbl_faturamento.faturamento_fatura IS NULL
		AND   tbl_faturamento.tipo_pedido = 2
		AND   tbl_faturamento.posto = 970";
$res = pg_exec ($con,$sql);
echo "<tr>";
echo "<td nowrap>NFs Antecipadas GM-TOSCAN </td>";
echo "<td align='right' nowrap>" . number_format (pg_result ($res,0,0),2,",",".") . "</td>";
echo "</tr>";





#----------- Esta SQL está errada. Muito demorada -----------#
$sql = "SELECT SUM ((tbl_pedido_item.qtde_faturada_distribuidor - tbl_pedido_item.qtde_faturada) * tbl_tabela_item.preco * (1 + (tbl_peca.ipi / 100) ) ) AS total_fabrica
		FROM tbl_pedido_item
		JOIN tbl_pedido USING (pedido)
		JOIN tbl_peca   ON tbl_pedido_item.peca = tbl_peca.peca
		LEFT JOIN tbl_tabela_item ON tbl_tabela_item.tabela IN ($tabelas) AND tbl_pedido_item.peca = tbl_tabela_item.peca 
		WHERE tbl_pedido.distribuidor = $login_posto
		AND   tbl_pedido.tipo_pedido  = 3
		AND   tbl_pedido_item.qtde_faturada_distribuidor > tbl_pedido_item.qtde_faturada";
#$res = pg_exec ($con,$sql);
#$pecas_britania = number_format (pg_result ($res,0,0),2,",",".") ;

echo "<tr>";
echo "<td nowrap>Peças em poder da BRITÂNIA </td>";
echo "<td align='right' nowrap>" . $pecas_britania . "</td>";
echo "</tr>";






echo "</table>";

echo "<p>";

?>


<? #include "rodape.php"; ?>

</body>
</html>
