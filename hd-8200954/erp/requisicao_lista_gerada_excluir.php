<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include 'menu.php';
?>
<script language="JavaScript">
function fnc_pesquisa_produto (campo, tipo) {
	if (campo.value != "") {
		var url = "";
		url = "produto_pesquisa.php?retorno=<? echo $PHP_SELF ?>&forma=reload&campo=" + campo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.descricao = document.frm_produto.descricao;
		janela.referencia= document.frm_produto.referencia;
		//janela.linha     = document.frm_produto.linha;
		//janela.familia   = document.frm_produto.familia;
		janela.focus();
	}
}
</script>


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
<table class='table_line' width='700' border='0' cellspacing='1' cellpadding='2'>
<FORM name='frm_produto' action="<? $PHP_SELF ?>" METHOD='POST'>
  <tr >
	<td nowrap align='left'><font size='3'><b>Requisição Manual</b></font></td>
	<td nowrap align='right'></td>
  </tr>
  <tr>
    <td colspan='3'>

  <table width='100%' align='left'>
	<tr bgcolor='#596D9B'>	
	  <td colspan='6' nowrap class='menu_top' align='left'><font size='3'>Solicitação de Requisição Realizada</font></td>
	</tr>
	<tr bgcolor='#596D9B'>	
	  <td nowrap class='titulo' width='20%' align='center'>Nº Requisição</td>
	  <td nowrap class='titulo' width='20%' align='center'>Data</td>
	  <td nowrap class='titulo' width='15%' align='center'>Usuário</td>
	  <td nowrap class='titulo' width='25%' align='center'>Qtd de Itens Solicitados</td>
	  <td nowrap class='titulo' width='20%' align='center'>Status</td>
	</tr>
<?

$sql= " SELECT 
			tbl_requisicao.requisicao, 
			tbl_requisicao.status as status,
			TO_CHAR(tbl_requisicao.data,'DD/MM/YYYY') as data, 
			posto_empregado.nome,
			count(requisicao_item) as qtd
		FROM tbl_requisicao
		JOIN tbl_requisicao_item using (requisicao)
		JOIN tbl_empregado on tbl_empregado.empregado = tbl_requisicao.empregado
		JOIN tbl_posto as posto_empregado on posto_empregado.posto = tbl_empregado.posto_empregado
		GROUP BY 
				tbl_requisicao.requisicao, 
				tbl_requisicao.status,
				tbl_requisicao.data,
				posto_empregado.nome
		ORDER BY
				tbl_requisicao.status, 
				tbl_requisicao.requisicao DESC";

$res= pg_exec($con, $sql);
if(@pg_numrows($res)>0){
	$c=1;
	for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {
		$requisicao	=trim(pg_result($res,$i,requisicao));
		$data		=trim(pg_result($res,$i,data));
		$nome		=trim(pg_result($res,$i,nome));
		$status		=trim(pg_result($res,$i,status));
		$qtd		=trim(pg_result($res,$i,qtd));

		if ($c==2){ 
			echo "<tr bgcolor='#eeeeff' class='table_line'>"; 
			$c=0;
		}else {
			echo "<tr bgcolor='#fafafa' class='table_line'>";
		}
	    echo "<td nowrap align='center'>";
        echo "<a href='requisicao.php?requisicao=$requisicao'><font color='#0000ff'>$requisicao</font></a>";
	    echo "</td>";
	    echo "<td nowrap align='center'> $data</td>";
	    echo "<td nowrap align='center' >$nome</td>";
	    echo "<td nowrap align='center' >$qtd</td>";
	    if($status=='aberto')
		  echo "<td nowrap align='center' ><font color='#0000ff'>$status</font></td>";
		else
		  echo "<td nowrap align='center' >$status</td>";
	    echo "</tr>";
	    $c++;
	}
}else{
	echo "<tr bgcolor='#ff5555'><td colspan='5' align='center'> Sem requisições cadastradas&nbsp;</td></tr>"; 
}
?>  
   </table>
  </td>
  </tr>
  </form>
  <tr>
   <td>
   <table width='100%' align='left'>
	<tr>
      <td nowrap class='table_line' align='left'>&nbsp;
	  <!--<input type='button' onClick="abrir();" name='enviar' value='Gravar'>--></td>
      <td nowrap class='table_line' align='right'>&nbsp;
	  <!--<input type='submit' name='enviar' value='Cancelar'>--></td>
	</tr>
   </table>
	</td>
   </tr>
		
</table>
</body>
</html>
