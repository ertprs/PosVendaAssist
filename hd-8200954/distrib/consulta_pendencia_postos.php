<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
?>
<html>
<head>
<title>Pendência de peças com Postos</title>

<link type="text/css" rel="stylesheet" href="css/css.css">
<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

</style>

<script language="JavaScript">
	function abrePopup(url,largura){
		data = new Date();
		window.open(url, 'Consulta'+data.getSeconds(), 'width='+largura+',height=500,toolbar=0,resizable=1,scrollbars=1');
	}
</script>

</head>

<body>

<? include 'menu.php' ?>


<center><h1>Pendência de peças com Postos</h1></center>

<?
$ordem= $_GET['ordem'];
//ADICIONEI: (Faltou colocar aqui tb!!!!) AND qtde > qtde_cancelada + qtde_faturada_distribuidor - HD  13939

$sql = "SELECT 	tbl_peca.peca       AS peca, 
				tbl_peca.referencia AS referencia, 
				tbl_peca.descricao  AS descricao, 
				pend.qtde AS qtde,
				tbl_posto_estoque.qtde AS estoque,
				tbl_posto_estoque_localizacao.localizacao AS localizacao
		FROM tbl_peca
		JOIN (
				SELECT 	peca, 
						SUM (
							tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada_distribuidor
						) as qtde 
					FROM tbl_pedido_item 
					JOIN tbl_pedido USING (pedido) 
					JOIN tbl_peca   USING(peca)
					WHERE tbl_pedido.fabrica <> 0
					AND   tbl_pedido.distribuidor = $login_posto 
					AND   tbl_peca.produto_acabado IS NOT TRUE
					AND   tbl_peca.referencia NOT LIKE '7%'
					GROUP BY tbl_pedido_item.peca
					HAVING SUM (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada_distribuidor) > 0 
			) 
		PEND ON tbl_peca.peca = pend.peca
		LEFT JOIN tbl_posto_estoque_localizacao on tbl_peca.peca = tbl_posto_estoque_localizacao.peca AND tbl_posto_estoque_localizacao.posto = $login_posto
		LEFT JOIN tbl_posto_estoque             on tbl_peca.peca = tbl_posto_estoque.peca             AND tbl_posto_estoque.posto = $login_posto";
		if ((strlen($ordem)==0) or ($ordem =='qtde')) {$sql .= " ORDER BY pend.qtde DESC";}
		if ($ordem =='referencia'){$sql .= " ORDER BY tbl_peca.referencia DESC";}
		if ($ordem =='descricao'){$sql .= " ORDER BY tbl_peca.descricao  ASC";}
		if ($ordem =='localizacao'){$sql .= " ORDER BY tbl_posto_estoque_localizacao.localizacao ASC";}
		if ($ordem =='estoque'){$sql .= " ORDER BY tbl_posto_estoque.qtde DESC";}
		
		#Retirei os produtos acabado e as peças de audio e video: Fabio - HD 14964

		//echo nl2br($sql);
		#exit;
$res = pg_exec ($con,$sql);

echo "<center><a href='javascript:window.print()'><font size='1'>Clique aqui para Imprimir</font></A></center>";
echo "<TABLE width='100%' align='center' border='0' cellspacing='0' cellpadding='0'>";
	echo "<TR class='table_line'>";
	echo "<td align='center' background='#D9E2EF'></td>";
	echo "</TR>";

	echo "<TR class='menu_top'  height='25'>\n";
	echo "<TD align='center'><a href=$PHP_SELF?ordem=referencia><font color='#FFFFFF'><center>Referência</center></FONT></a></TD>\n";
	echo "<TD align='center'><a href=$PHP_SELF?ordem=descricao><font color='#FFFFFF'>Descrição</font></a></TD>\n";
	echo "<TD align='center'><a href=$PHP_SELF?ordem=qtde><font color='#FFFFFF'>Qtde</font></a></TD>\n";
	echo "<TD align='center'><a href=$PHP_SELF?ordem=estoque><font color='#FFFFFF'>Estoque</font></a></TD>\n";
	echo "<TD align='center'><a href=$PHP_SELF?ordem=localizacao><font color='#FFFFFF'>Localização</FONT></a></TD>\n";
	echo "</TR>\n";
	
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
		$peca             = trim(pg_result ($res,$i,'peca'));
		$peca_referencia  = trim(pg_result ($res,$i,'referencia'));
		$peca_descricao   = trim(pg_result ($res,$i,'descricao'));
		$qtde             = trim(pg_result ($res,$i,'qtde'));
		$estoque          = trim(pg_result ($res,$i,'estoque'));
		$localizacao      = trim(pg_result ($res,$i,'localizacao'));

		$cor = "#F7F5F0"; 
		if ($i % 2 == 0) {$cor = '#F1F4FA';}

		echo "<TR class='table_line' style='background-color: $cor;' height='20'>\n";
		echo "<TD align=center nowrap><acronym title='Pedido Pendentes Feito Para à Fábrica'><a href=\"javascript:abrePopup('estoque_consulta.php?peca=$peca&busca=pedido_fabrica',600)\" targe='_blank'>$peca_referencia </a></acronym></TD>\n";
		echo "<TD nowrap>$peca_descricao</TD>\n";
		echo "<TD align=center nowrap><acronym title='Pedidos pendentes dos postos'><a href=\"javascript:abrePopup('estoque_consulta.php?peca=$peca&busca=pedido_postos',700)\" targe='_blank'>$qtde</a></acronym></TD>\n";
		echo "<TD align=center nowrap>$estoque</TD>\n";
		echo "<TD align=center nowrap>$localizacao</TD>\n";
		echo "</TR>\n";
	}

echo "</table>";



?>



</body>
</html>
