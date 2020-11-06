<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$msg_erro = "";

#$title = "DETALHAMENTO DE NOTA FISCAL";
#$layout_menu = 'pedido';

#include "cabecalho.php";
if(strlen($os)>0){
?>
<style>
.Tabela{
	font-family: Verdana,sans;
	font-size:10px;
}
</style>
<?
	$sql = "SELECT DISTINCT 
				OS.os,
				OS.posto,
				PE.pedido,
				OI.qtde as osqtde,
				PI.peca,
				PI.qtde_faturada,
				PI.qtde_faturada_distribuidor,
				PI.qtde,
				FA.nota_fiscal,
				FA.distribuidor,
				FA.faturamento,
				FA.emissao,
				EI.embarque,
				EI.embarcado,
				PO.qtde AS Estoque_atual
			FROM tbl_os OS 
			JOIN tbl_os_produto OP USING(os) 
			JOIN tbl_os_item OI USING(os_produto) 
			JOIN tbl_pedido PE ON PE.pedido = OI.pedido 
			JOIN tbl_pedido_item PI ON PI.pedido = PE.pedido AND PI.peca = OI.peca 
			LEFT JOIN tbl_faturamento_item FI ON FI.pedido = PE.pedido AND PI.peca = PI.peca AND OS.os = FI.os 
			LEFT JOIN tbl_faturamento FA ON FA.faturamento = FI.faturamento  
			LEFT JOIN tbl_embarque_item EI ON EI.os_item = OI.os_item 
			LEFT JOIN tbl_posto_estoque PO ON PO.peca = PI.peca AND PO.posto = 4311 
			WHERE OS.os = $os
			ORDER BY FA.faturamento, FA.distribuidor;";

	$res = pg_exec ($con,$sql);

	echo "<table border='1' align='center' cellpadding='3' cellspacing='0' width='100%' class='Tabela' >";
	echo "<td align='center'>OS</td>";
	echo "<td align='center'>Referência</td>";
	echo "<td align='center'>Peça</td>";
	echo "<td align='center' >Pedido</td>";
	echo "<td align='center' >Nota Fiscal</td>";
	echo "<td align='center' >Embarque</td>";
	echo "<td align='center' >Embarcado dia</td>";
	echo "<td align='center'>Qtde OS</td>";
	echo "<td align='center'>Pedida</td>";
	echo "<td align='center' width='20'>Faturada Fabricante</td>";
	echo "<td align='center' width='20'>Faturada Distribuidor</td>";
	echo "<td align='center'>Estoque Atual</td>";


	echo "</tr>";
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$os            = pg_result ($res,$i,os);
		$posto         = pg_result ($res,$i,posto);
		$pedido        = pg_result ($res,$i,pedido);
		$peca          = pg_result ($res,$i,peca);
		$faturamento   = pg_result ($res,$i,faturamento);
		$embarque      = pg_result ($res,$i,embarque);
		$nota_fiscal   = pg_result ($res,$i,nota_fiscal);
		$emissao       = pg_result ($res,$i,emissao);
		$embarcado     = pg_result ($res,$i,embarcado);
		$estoque       = pg_result ($res,$i,Estoque_atual);
		$osqtde        = pg_result ($res,$i,osqtde);
		$qtde          = pg_result ($res,$i,qtde);
		$qtde_f        = pg_result ($res,$i,qtde_faturada);
		$qtde_fatura   = pg_result ($res,$i,qtde_faturada_distribuidor);

		$sql = "select sua_os FROM tbl_os WHERE os = $os";
		$res2 = pg_exec ($con,$sql);
		$sua_os       = pg_result ($res2,0,sua_os);

		$sql = "select referencia,descricao FROM tbl_peca WHERE peca = $peca";
		$res2 = pg_exec ($con,$sql);
		$referencia  = pg_result ($res2,0,referencia);
		$descricao   = pg_result ($res2,0,descricao);

		echo "<tr>";
		echo "<td align='center'><a href='../os_press.php?os=$os&keepThis=true&TB_iframe=true&height=500&width=750' target='_blank' class=\"thickbox\">$sua_os</a></td>";
		echo "<td align='center'>&nbsp;$referencia<br>$peca</td>";
		echo "<td align='center'>&nbsp;$descricao</td>";
		echo "<td align='center'>&nbsp;$pedido</td>";
		echo "<td align='center'>&nbsp;$nota_fiscal</td>";
		echo "<td align='center'>&nbsp;$embarque</td>";
		echo "<td align='center'>&nbsp;$embarcado</td>";
		echo "<td align='center'>&nbsp;$osqtde</td>";
		echo "<td align='center'>&nbsp;$qtde</td>";
		echo "<td align='center'>&nbsp;$qtde_f</td>";
		echo "<td align='center'>&nbsp;$qtde_fatura</td>";
		echo "<td align='center'>&nbsp;$estoque</td>";
		echo "<td align='center'>&nbsp;<a href='#'>$faturamento</a><br>$fi</td>";
		echo "</tr>";
	
	}
	exit;
}

?>

<html>
<head>
<title>Desembarcar Peças</title>
<style type="text/css">
.body {
font-family : verdana;
}
</style>
<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />
<script>
function excluirItem(url){
	if(confirm('Deseja realmente excluir esta peça deste embarque?')){
		window.location=url;
	}
}

function excluirItem2(url){
	if(confirm('Deseja realmente excluir todas peças listas dos seus respectivos embarques?')){
		window.location=url;
	}
}
</script>
</head>

<body>

<? include 'menu.php' ?>


<center><h1>Peças que serão desembarcadas</h1></center>

<center>

<p>
<?
$embarque_item         = trim ($_GET['embarque_item']);
$numero_embarque       = trim ($_GET['numero_embarque']);
$qtde                  = trim ($_GET['qtde']);
$msg = "";

if (strlen($numero_embarque)>0 ){

	$msg .= "Excluindo peca do embarque: $numero_embarque ...";

	$res = @pg_exec($con,"BEGIN TRANSACTION");

	$peca = $excluir_embarque_peca;

	$sqlX = "SELECT fn_desembarca_item2($embarque_item);";
	$resX = @pg_exec ($con,$sqlX);
	$msg_erro .= pg_errormessage($con);

	if (strlen ($msg_erro) == 0) {
		$msg .=  "Operação realizada com sucesso.";
		$res = @pg_exec ($con,"COMMIT TRANSACTION");
	}else{
		$msg .=  "Operação não realizada. Erro: $msg_erro";
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
	echo "<br><br>";
}

?>

<?
if (strlen($msg)>0){
	echo "<h4 style='color:black;text-align:center;border:1px solid #2FCEFD;background-color:#E1FDFF'>$msg</h4>";
}
//AND    tbl_embarque.embarque IN (SELECT DISTINCT embarque FROM tbl_embarque_item WHERE liberado IS NULL )
$sql = "
	SELECT DISTINCT os,embarque,embarque_item,tbl_embarque_item.peca
	INTO TEMP tmp_desembarca_os_$login_posto
	FROM   tbl_embarque
	JOIN   tbl_embarque_item USING (embarque)
	JOIN   tbl_os_item       USING (os_item)
	JOIN   tbl_os_produto    USING (os_produto)
	WHERE  tbl_embarque_item.liberado IS NULL
	AND    tbl_embarque.faturar       IS NULL
	AND    tbl_embarque.distribuidor = $login_posto
 	;
	
	CREATE INDEX tmp_desembarca_os_OS_$login_posto ON tmp_desembarca_os_$login_posto(os);
	CREATE INDEX tmp_desembarca_os_EMBARQUE_ITEM_$login_posto ON tmp_desembarca_os_$login_posto(embarque_item);

	SELECT     tbl_os_produto.os,
		   tbl_servico_realizado.troca_produto,
		   tbl_servico_realizado.troca_de_peca
	INTO TEMP tmp_desembarca_os2_$login_posto
	FROM      tmp_desembarca_os_$login_posto
	JOIN      tbl_os_produto        USING (os)
	JOIN      tbl_os_item           USING (os_produto)
	JOIN      tbl_servico_realizado USING (servico_realizado)
	LEFT JOIN tbl_embarque_item     USING (os_item)
	WHERE     tbl_embarque_item.embarque_item IS NULL
	AND       (tbl_servico_realizado.troca_produto IS TRUE OR tbl_servico_realizado.troca_de_peca);

	CREATE INDEX tmp_desembarca_os2_OS_$login_posto ON tmp_desembarca_os2_$login_posto(os);
	";

$res = pg_exec ($con,$sql);

$sql = "SELECT distinct 
		X.embarque,
		X.embarque_item,
		os,
		tbl_peca.referencia,
		tbl_peca.descricao,
		troca_produto,
		tbl_embarque_item.qtde
	FROM      tmp_desembarca_os_$login_posto  X
	JOIN      tmp_desembarca_os2_$login_posto Y USING (os)
	JOIN      tbl_embarque_item         USING (embarque_item)
	LEFT JOIN tbl_peca                  ON  tbl_embarque_item.peca =tbl_peca.peca
	WHERE tbl_embarque_item.liberado is null
	ORDER BY embarque;";


$res = pg_exec ($con,$sql);
echo "<center><a href=\"javascript:excluirItem2('$PHP_SELF?desembarca_tudo=sim')\">Desembarcar todos os itens abaixo</a>";
for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

	if($embarque <> pg_result ($res,$i,embarque)){
		$embarque   = pg_result ($res,$i,embarque);
		echo "</table><br>";
		echo "<table border='1' align='center' cellpadding='3' cellspacing='0' width='700' >";
		echo "<tr>";
		echo "<td align='left' colspan='6'>Embarque <b>$embarque</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td align='center'>OS</td>";
		echo "<td align='center'>Referência</td>";
		echo "<td align='center'>Peça</td>";
		echo "<td align='center'>QTDE</td>";
		echo "<td align='center'>Situação</td>";
		echo "<td align='center' >Ação</td>";
		echo "</tr>";
	}

	$os            = pg_result ($res,$i,os);
	$referencia    = pg_result ($res,$i,referencia);
	$descricao     = pg_result ($res,$i,descricao);
	$troca         = pg_result ($res,$i,troca_produto);
	$embarque_item = pg_result ($res,$i,embarque_item);
	$qtde          = pg_result ($res,$i,qtde);

	$sql = "select sua_os FROM tbl_os WHERE os = $os";
	$res2 = pg_exec ($con,$sql);
	$sua_os       = pg_result ($res2,0,sua_os);
	if($troca == 't') $troca = "<font color='#FF0000'>Troca de Produto</a>";
	else              $troca = "Embarque Parcial";
	echo "<tr>";
	echo "<td align='center'><a href='../os_press.php?os=$os&keepThis=true&TB_iframe=true&height=500&width=750' target='_blank' class=\"thickbox\">$sua_os</a></td>";
	echo "<td align='center'>$referencia</td>";
	echo "<td align='center'>$descricao</td>";
	echo "<td align='center'>$qtde</td>";
	echo "<td align='center'>$troca</td>";
	echo "<td align='center'><a href='?os=$os&keepThis=true&TB_iframe=true&height=350&width=750'class=\"thickbox\">Ver situação</a></td>";
	echo "</tr>";
}
echo "</table>";




?>



<p>

<? #include "rodape.php"; ?>

</body>
</html>
