<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include '../admin/autentica_admin.php';
		
?>


<style type="text/css">
.menu_top {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	border: 0px;
}
.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color: #000000;
	border: 0px;
}

.titulo {
	font-family: Arial;
	font-size: 10pt;
	color: #000000;
	background: #ced7e7;
}
</style>
<script language="javascript">
function abrir(){
		opener.location.reload();
		//janela.retorno = "<? echo $PHP_SELF ?>";
}
</script>

<table class='table_line' width='600px' border='0' cellspacing='1' cellpadding='2'>
<FORM ACTION='#' METHOD='GET'>
  <tr bgcolor='#596D9B'>
	<td nowrap class='menu_top' align='center'><font size='3'>
		Pedidos</font>
	</td>
  </tr>
  <tr>
    <td colspan='3'>

  <table width='100%' align='left'>
	<tr>	
	  <td nowrap class='menu_top' colspan='6' align='left'>
		<font color='#0000ff' size= '1px'>Clique no pedido</font>
	  </td>
	</tr>
	<tr bgcolor='#596D9B'>	
	  <td nowrap class='menu_top' width='20%' align='center'>Nº Pedido</td>
	  <td nowrap class='menu_top' width='20%' align='center'>Cotação</td>
  	  <td nowrap class='menu_top' width='20%' align='center'>Fornecedor</td>
	  <td nowrap class='menu_top' width='30%' align='center'>Data do Pedido</td>
  	  <td nowrap class='menu_top' width='30%' align='center'>Data de Entrega</td>
	  <td nowrap class='menu_top' width='20%' align='center'>Situação</td>
	</tr>
<?

	$sql= "SELECT 
			TBL_PEDIDO.PEDIDO,
			TBL_PEDIDO.DATA as DATA_PEDIDO,
			TBL_PEDIDO.ENTREGA as DATA_ENTREGA,
			TBL_PEDIDO.STATUS_PEDIDO,
			TBL_COTACAO.COTACAO, 
			TBL_POSTO.NOME,	
			TBL_POSTO.POSTO
			FROM TBL_PEDIDO
			JOIN TBL_COTACAO_FORNECEDOR USING(COTACAO_FORNECEDOR)
			JOIN TBL_POSTO on tbl_posto.posto = tbl_cotacao_fornecedor.posto
			JOIN TBL_COTACAO USING(COTACAO)
			ORDER BY pedido desc";

	$res= pg_exec($con, $sql);
	echo "sql:".$sql;

	$c=1;
	for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {
		$cotacao	=trim(pg_result($res,$i,cotacao));
		$pedido		=trim(pg_result($res,$i,pedido));
		$data_pedido=trim(pg_result($res,$i,data_pedido));
		$data_entrega=trim(pg_result($res,$i,data_entrega));
		$status		=trim(pg_result($res,$i,status_pedido));
		$nome		=trim(pg_result($res,$i,nome));

		if ($c==2){ 

			echo "<tr bgcolor='#eeeeff' class='table_line'>\n"; 

			$c=0;
		}else {
			echo "<tr bgcolor='#fafafa' class='table_line'>\n"; 		
		}

		echo "<td nowrap align='center'>";
	
/*		echo "<script language='javascript' type='text/javascript'> 
			opener.location.reload();
			opener.location.href = 'pedido.php';
			window.close(); 
			</script>";
*/
		  echo "<a href='#' onClick=\"opener.document.getElementById('idDoFrame').src='nf_entrada_item.php?pedido=$pedido'; window.close();\">
		  <font color='#0000ff'>$pedido</font>
			</a>	  
		  </td>\n";

/*		  echo "<a href=\"javascript:window.opener.location='nf_entrada_item.php?pedido=$pedido'; this.close() ; \" >
		  <font color='#0000ff'>$pedido</font>
			</a>	  
*/		  echo "</td>\n";
		  echo "<td nowrap align='center'>$cotacao</td>";
		  echo "<td nowrap align='left'> $nome</td>";
		  echo "<td nowrap align='center' >$data_pedido</td>";
		  echo "<td nowrap align='center' >$data_entrega</td>";
		  echo "<td nowrap align='center' >";
		  echo "<font color='#0000ff'>$status</font>";
		  echo "</td>";
		  echo "</tr>";
	  $c++;
	}

?>  
  </table>
  </td>
  </tr>
 </FORM>
</table>

</body>
</html>
