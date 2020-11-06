<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$title='Posição Financeira';
include 'cabecalho.php';

?>
<style>
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Arial;
	font-size: 10px;
	font-weight: normal;
}
.ConteudoBranco {
	font-family: Arial;
	font-size: 9px;
	color:#FFFFFF;
	font-weight: normal;
}
.Mes{
	font-size: 9px;
}
.Caixa{
	BORDER-RIGHT: #6699CC 1px solid; 
	BORDER-TOP: #6699CC 1px solid; 
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid; 
	BORDER-BOTTOM: #6699CC 1px solid; 
	BACKGROUND-COLOR: #FFFFFF;
}
.Exibe{
	font-family: Arial, Helvetica, sans-serif;
	font-size: 8 px;
	font-weight: none;
	color: #000000;
	text-align: center;
}
.Erro{
	BORDER-RIGHT: #990000 1px solid; 
	BORDER-TOP: #990000 1px solid; 
	FONT: 10pt Arial ;
	COLOR: #ffffff;
	BORDER-LEFT: #990000 1px solid; 
	BORDER-BOTTOM: #990000 1px solid; 
	BACKGROUND-COLOR: #FF0000;
}
.Carregando{
	TEXT-ALIGN: center;
	BORDER-RIGHT: #aaa 1px solid; 
	BORDER-TOP: #aaa 1px solid; 
	FONT: 10pt Arial ;
	COLOR: #000000;
	BORDER-LEFT: #aaa 1px solid; 
	BORDER-BOTTOM: #aaa 1px solid; 
	BACKGROUND-COLOR: #FFFFFF;
	margin-left:20px;
	margin-right:20px;
}
</style>
<?

$sql = "SELECT  DISTINCT
		tbl_faturamento.faturamento                                         ,
		tbl_faturamento.nota_fiscal                                         ,
		tbl_faturamento.emissao                            AS x             ,
		tbl_faturamento.total_nota                                          ,
		to_char (tbl_faturamento.emissao,'DD/MM/YYYY')    AS emissao        ,
		to_char (tbl_faturamento.vencimento,'DD/MM/YYYY') AS vencimento     ,
		tbl_condicao.descricao                         AS condicao_descricao,
		tbl_pedido.tipo_pedido
	FROM    tbl_faturamento_item
	JOIN    tbl_faturamento USING (faturamento)
	JOIN    tbl_condicao    USING (condicao)
	JOIN    tbl_pedido      ON tbl_faturamento_item.pedido = tbl_pedido.pedido
	WHERE  tbl_faturamento.fabrica     = $login_fabrica
	AND    tbl_faturamento.posto       = $login_posto	
	AND  ( tbl_faturamento.tipo_pedido = 2    OR tbl_faturamento.tipo_pedido IS NULL )
	AND    tbl_faturamento.vencimento IS NULL
	AND    tbl_faturamento.baixa      IS NULL
	AND    tbl_faturamento.emissao    > '2006-12-01'
	ORDER BY  tbl_faturamento.emissao DESC,tbl_faturamento.nota_fiscal";
//and condicao <> 7 que eh garantia???

$res = pg_exec ($con,$sql);

if (pg_numrows ($res) > 0) {
// background='admin/imagens_admin/azul.gif' height='25'
	$total_a_pagar = 0;
	echo "<table border='1' cellpadding='2' cellspacing='2' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center' width='700'>";
	echo "<tr class='Titulo'>";
	echo "<td>NOTA FISCAL</td>";
	echo "<td>TIPO PEDIDO</td>";
	echo "<td>EMISSÃO</td>";
	echo "<td>VENCIMENTO</td>";
	echo "<td width='80'>VALOR</td>";
	echo "</tr>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

		$faturamento       = trim(pg_result ($res,$i,faturamento));
		$nota_fiscal       = trim(pg_result ($res,$i,nota_fiscal));
		$emissao           = trim(pg_result ($res,$i,emissao));
		$vencimento        = trim(pg_result ($res,$i,condicao_descricao));
		$vencimento        = trim(pg_result ($res,$i,vencimento));
		$tipo_pedido       = trim(pg_result ($res,$i,tipo_pedido));
		$total_nota        = trim(pg_result ($res,$i,total_nota));

		$total_a_pagar = $total_a_pagar + $total_nota;
		$total_nota = number_format ($total_nota,2,",",".");

		if($cor=="#F1F4FA")$cor = '#FAFAFA';
		else               $cor = '#F1F4FA';

		if($tipo_pedido == 2 ) $tipo_pedido = "Venda";
		if($tipo_pedido == 3 ) $tipo_pedido = "Garantia";

		echo "<tr class='Conteudo' bgcolor='$cor'height='20' align='center'>";
		echo "<td>$nota_fiscal</td>";
 		echo "<td>$tipo_pedido</td>";
		echo "<td>$emissao</td>";
		echo "<td>$vencimento</td>";
		echo "<td align = 'right'> $total_nota</td>";
		echo "</tr>";

	}
	$total_a_pagar = number_format ($total_a_pagar,2,",",".");
	echo "<tr >";
	echo "<td colspan ='3' class='Titulo'>TOTAL DÉBITO</td>";
	echo "<td colspan ='2' align = 'right'> <b>R$ $total_a_pagar</b></td>";
	echo "</tr>";
	echo "</table>";
}else{
	echo "<table style=' border: #D3BE96 1px solid; background-color: #FCF0D8 ' align='center' width='700'>";
	echo "<tr>";
	echo "<td align='center'><b>Não existe nenhuma pendência financeira com o fabricante</b></td>";
	echo "</tr>";
	echo "</table>";
	
}
include 'rodape.php';
?>



