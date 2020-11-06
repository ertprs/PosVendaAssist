<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';


$layout_menu = "os";
$title = "Relatório de Saldo";

include "cabecalho.php";

?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
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
	background-color: #D9E2EF;
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

</style>

<p>

<TABLE width='650' align='center' border='0' cellspacing='3' cellpadding='3'>

<tr class='menu_top'><td colspan='2' align='center'>RELATÓRIO DE SALDO</td></tr>

<?
$sql = "SELECT  to_char(tbl_faturamento.emissao,'DD/MM/YYYY') AS data,
				tbl_faturamento.nota_fiscal                          ,
				tbl_faturamento.faturamento
		FROM    tbl_faturamento
		WHERE   tbl_faturamento.fabrica  = $login_fabrica
		AND     tbl_faturamento.posto    = $login_posto
		AND     tbl_faturamento.garantia = 't'
		ORDER BY tbl_faturamento.emissao;";
$res = pg_exec($con,$sql);

if (pg_numrows ($res) > 0){
	echo "<TR class='menu_top'>";
	echo"<TD align='center' width='50%'>DATA</TD>";
	echo"<TD align='center' width='50%'>NOTA FISCAL</TD>";
	echo"</TR>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
		$data        = trim(pg_result($res,$i,data));
		$nota_fiscal = trim(pg_result($res,$i,nota_fiscal));
		$faturamento = trim(pg_result($res,$i,faturamento));
		
		echo "<TR class='table_line' style='background-color: $cor;'>";
		echo "<TD align='center'>$data</TD>";
		echo "<TD align='center'>NF <a href='$PHP_SELF?faturamento=$faturamento'>$nota_fiscal</a></TD>";
		echo "</TR>";
	}
	
	echo "</TABLE>";
}





$faturamento = $_GET['faturamento'];

if(strlen($faturamento) > 0){
	echo "<BR>";
	echo "<p>";
	
	$sql = "SELECT  to_char(tbl_faturamento.emissao,'DD/MM/YYYY') AS data,
					tbl_faturamento.nota_fiscal
			FROM    tbl_faturamento
			WHERE   tbl_faturamento.fabrica     = $login_fabrica
			AND     tbl_faturamento.faturamento = $faturamento;";
	$res = pg_exec($con,$sql);

	if (pg_numrows ($res) > 0){
		$nota_fiscal = trim(pg_result($res,0,nota_fiscal));
		$data        = trim(pg_result($res,0,data));
	}
	
	echo "<TABLE width='650' align='center' border='0' cellspacing='3' cellpadding='3'>";
	echo "<tr class='menu_top'>";
	echo "<td align='center' COLSPAN='5'>NF $nota_fiscal - $data</td>";
	echo "</tr>";

	$sql = "SELECT	tbl_faturamento_item.peca                   ,
					tbl_faturamento_item.qtde AS qtde_solicitada,
					(
					SELECT COUNT(tbl_nf_os.nf_os)
					FROM   tbl_nf_os
					JOIN   tbl_os_item USING (os_item)
					WHERE tbl_nf_os.faturamento_item = tbl_faturamento_item.faturamento_item
					) AS qtde_usada                             ,
					tbl_faturamento_item.preco                  ,
					tbl_peca.referencia                         ,
					tbl_peca.descricao
			FROM    tbl_faturamento_item
			JOIN    tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
			JOIN    tbl_peca        ON tbl_faturamento_item.peca   = tbl_peca.peca
			WHERE   tbl_faturamento.fabrica     = $login_fabrica
			AND     tbl_faturamento.faturamento = $faturamento
			ORDER BY tbl_peca.descricao;";
	$res = pg_exec($con,$sql);
	//if ($ip == "192.168.0.20") echo $sql;
	
	if (pg_numrows ($res) > 0){
		echo "<TR class='menu_top'>";
		echo "<TD align='center' width='20%'>Qtde Baixada</TD>";
		echo "<TD align='center' width='20%'>Qtde Solicitada</TD>";
		echo "<TD align='center' width='10%'>Ref.</TD>";
		echo "<TD align='center' width='40%' nowrap>Descrição</TD>";
		echo "<TD align='center' width='10%'>Preço</TD>";
		echo "</TR>";
		
		$total = 0;
		
		for ($i=0; $i < pg_numrows($res); $i++){
			$qtde_solicitada = trim(pg_result($res,$i,qtde_solicitada));
			$qtde_usada      = trim(pg_result($res,$i,qtde_usada));
			$peca            = trim(pg_result($res,$i,peca));
			$preco           = trim(pg_result($res,$i,preco));
			$peca_referencia = trim(pg_result($res,$i,referencia));
			$peca_descricao  = trim(pg_result($res,$i,descricao));
			
			$preco_total = ($qtde_solicitada * $preco);
			$total       = $total + $preco_total;
			$preco_total = number_format($preco_total,2,',','.');
			
			echo "<TR class='table_line' style='background-color: $cor;'>";
			echo "<TD align='right'>$qtde_usada </TD>";
			echo "<TD align='right'>$qtde_solicitada</TD>";
			echo "<TD align='left'>$peca_referencia</TD>";
			echo "<TD align='left' nowrap>$peca_descricao</TD>";
			echo "<TD align='right'>$preco_total</TD>";
			echo "</TR>";
		}

		echo "<TR class='table_line' style='background-color: $cor;'>";
		echo "<TD align='right' colspan='4'><b>Total </b></TD>";
		echo "<TD align='right'>";
		$total           = number_format($total,2,',','.');
		echo "$total </TD>";
		echo "</TR>";
		echo "</TABLE>";
	}

	//3ª tabela - Abatimento dos itens da NF pelas OS
	
	$sql = "SELECT	tbl_faturamento_item.peca                   ,
					tbl_faturamento_item.faturamento_item       ,
					tbl_faturamento_item.qtde AS qtde_solicitada,
					tbl_faturamento_item.preco                  ,
					tbl_peca.referencia                         ,
					tbl_peca.descricao                           
			FROM	tbl_faturamento_item
			JOIN    tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
			JOIN    tbl_peca        ON tbl_faturamento_item.peca   = tbl_peca.peca
			WHERE   tbl_faturamento.fabrica     = $login_fabrica
			AND     tbl_faturamento.faturamento = $faturamento
			ORDER BY tbl_peca.descricao;";
	$res = pg_exec($con,$sql);
	
	for ($i=0; $i < pg_numrows($res); $i++){
		$faturamento_item = trim(pg_result($res,$i,faturamento_item));
		$qtde_solicitada  = trim(pg_result($res,$i,qtde_solicitada));
		$peca             = trim(pg_result($res,$i,peca));
		$preco            = trim(pg_result($res,$i,preco));
		$peca_referencia  = trim(pg_result($res,$i,referencia));
		$peca_descricao   = trim(pg_result($res,$i,descricao));
		
		$sql = "SELECT	tbl_os.os                                 ,
						tbl_os.sua_os                                 ,
						tbl_os.consumidor_nome                        ,
						tbl_produto.referencia  AS produto_referencia ,
						tbl_produto.descricao   AS produto_descricao  ,
						tbl_os_item.qtde 
				FROM	tbl_nf_os
				JOIN    tbl_os_item    USING (os_item)
				JOIN    tbl_os_produto USING (os_produto)
				JOIN    tbl_os         USING (os)
				JOIN    tbl_produto    ON tbl_os.produto = tbl_produto.produto
				WHERE	tbl_nf_os.faturamento_item = $faturamento_item
				AND     tbl_nf_os.os_item NOTNULL;";
		$res0 = pg_exec($con,$sql);
		
		if (pg_numrows ($res0) > 0){
			$x = 0;
			if ($x == 0) {
				echo "<TABLE width='650' align='center' border='0' cellspacing='3' cellpadding='3'>";
				echo "<tr class='menu_top'>";
				echo "<td colspan='3' align='center'>NOTA FISCAL $nota_fiscal ABATIDAS PELAS SEGUINTES OS</td>";
				echo "</tr>";
				$x++;
			}
			echo "<TR class='menu_top'>";
			echo "<TD align='center' colspan='3'><font size='+1'>$peca_referencia - $peca_descricao</font></TD>";
			echo"</TR>";
			echo "<TR>";
			
			echo "<TD COLSPAN='3'>";
			
			echo "<table width='100%' border='0' cellpaddin='3' cellspacing='3'>";
			echo "<tr bgcolor='#5F82BC'>";
			echo "<TD align='center'><font color=#FFFFFF><b>OS</b></font></TD>";
			echo "<TD align='center'><font color=#FFFFFF><b>Produto</b></font></TD>";
			echo "<TD align='center'><font color=#FFFFFF><b>Cliente</b></font></TD>";
			echo "<TD align='center'><font color=#FFFFFF><b>Qtde</b></font></TD>";
			echo "</TR>";
			
			$qtde_usada = 0 ;
			
			//echo $sql;
			for ($ii=0; $ii < pg_numrows($res0); $ii ++) {
				$os                 = trim(pg_result($res0,$ii,os)); 
				$sua_os             = trim(pg_result($res0,$ii,sua_os)); 
				$consumidor_nome    = trim(pg_result($res0,$ii,consumidor_nome));
				$produto_referencia = trim(pg_result($res0,$ii,produto_referencia));
				$produto_descricao  = trim(pg_result($res0,$ii,produto_descricao));
				$qtde               = trim(pg_result($res0,$ii,qtde));
				
				$qtde_usada += $qtde ;
				
				echo "<TR class='table_line'>";
				echo"<TD align='left' ><a href='os_press.php?os=$os' target='blank'>$sua_os</a></TD>";
				echo"<TD align='left' >$produto_referencia - $produto_descricao</TD>";
				echo"<TD align='left' >$consumidor_nome</TD>";
				echo"<TD align='right' >$qtde</TD>";
				echo"</TR>";
			}
			
			echo"</TABLE>";
			
			echo"</TD>";
			echo"</TR>";
			
			$saldo = $qtde_solicitada - $qtde_usada ;
			
			echo "<TR class='menu_top'>";
			
			echo"<TD align='center'>Qtde Solicitada</TD>";
			echo"<TD align='center'>Qtde Usada</TD>";
			echo"<TD align='center'>Saldo</TD>";
			
			echo"</TR>";

			echo "<TR class='table_line'>";
			
			echo"<TD align='center'>$qtde_solicitada</TD>";
			echo"<TD align='center'>$qtde_usada</TD>";
			echo"<TD align='center'>$saldo </TD>";
			
			echo"</TR>";
			echo "<TR";
			echo "<TD align='center' colspan='3'><hr></TD>";
			echo"</TR>";

		}
	}

	echo "</TABLE>";

}

include "rodape.php"; ?>