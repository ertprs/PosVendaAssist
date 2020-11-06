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

<table class='table_line' width='700' border='1' cellpadding="2" cellspacing="0" style='border-collapse: collapse' bordercolor='#D2E4FC'>
<FORM name='frm_produto' action="<? $PHP_SELF ?>" METHOD='POST'>
  <tr>
	<td nowrap align='left' class='menu_top' background='imagens/azul.gif'><font size='3'><b>Consulta Requisição</b></font></td>
  </tr>
  <tr>
    <td >

  <table width='100%' align='left'>
	<tr bgcolor='#596D9B'>	
	  <td nowrap class='titulo' width='20%' align='center'>Nº Requisição</td>
	  <td nowrap class='titulo' width='20%' align='center'>Data</td>
	  <td nowrap class='titulo' width='15%' align='center'>Usuário</td>
	  <td nowrap class='titulo' width='25%' align='center'>Qtd de Itens Solicitados</td>
	  <td nowrap class='titulo' width='20%' align='center'>Status</td>
	</tr>


<?
/*
$sql= "select requisicao, 
			to_char(DATA,'DD/MM/YYYY') as DATA, 
			login,
			tbl_requisicao.status as status,
			count(requisicao_item) as qtd
		from tbl_requisicao
		join tbl_requisicao_item using (requisicao)
		join tbl_admin on tbl_admin.admin = tbl_requisicao.usuario
		where admin= $login_admin
		group by requisicao, data, tbl_requisicao.status, login
		order by tbl_requisicao.status, requisicao desc";
//echo "<br> sql: $sql";*/
$sql= "SELECT 
			tbl_requisicao.requisicao, 
			tbl_requisicao.status,
			TO_CHAR(data,'DD/MM/YYYY') as DATA, 
			pessoa_empregado.nome,
			COUNT(requisicao_item) as qtd
		FROM tbl_requisicao
		JOIN tbl_requisicao_item using (requisicao)
		JOIN tbl_empregado on tbl_empregado.empregado = tbl_requisicao.empregado
		JOIN tbl_pessoa as pessoa_empregado on pessoa_empregado.pessoa = tbl_empregado.pessoa
		WHERE tbl_empregado.empregado = $login_empregado 
		GROUP BY tbl_requisicao.requisicao, 
				 tbl_requisicao.data, 
				 tbl_requisicao.status, 
 				 pessoa_empregado.nome,
				 pessoa_empregado.pessoa";

//echo "sql: $sql";
$res= pg_exec($con, $sql);
if(@pg_numrows($res)>0){
	for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {
		$requisicao	=trim(pg_result($res,$i,requisicao));
		$data		=trim(pg_result($res,$i,data));
		$nome		=trim(pg_result($res,$i,nome));
		$status		=trim(pg_result($res,$i,status));
		$qtd		=trim(pg_result($res,$i,qtd));

		if ($cor=="#fafafa")	$cor= "#eeeeff";
		else					$cor= "#fafafa";
		
		echo "<tr bgcolor='$cor' class='table_line'>"; 
	    echo "<td nowrap align='center'>";
        echo "<a href='requisicao.php?requisicao=$requisicao'><font color='#0000ff'><U>$requisicao</U></font></a>";
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
	echo "<tr ><td colspan='5' align='center'> <font color='#0000ff'><b>Sem requisições Cadastradas!</font></b></td></tr>"; 
}
?>  
   </table>
  </td>
  </tr>
  </form>
</table>
</body>
</html>
