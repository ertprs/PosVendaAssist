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
<?
$requisicao_lista= $_GET["requisicao_lista"];
?>

<table class='table_line' width='700' border='1' cellpadding="2" cellspacing="0" style='border-collapse: collapse' bordercolor='#D2E4FC'>
<FORM name='frm_produto' action="<? $PHP_SELF ?>" METHOD='POST'>
	<tr bgcolor='#596D9B'>	
	  <td colspan='9' nowrap class='menu_top' align='left' background='imagens/azul.gif'><font size='3'>Pedido: <?echo " $requisicao_lista";?></font></td>
	</tr>
	<tr bgcolor='#596D9B'>	
	  <td nowrap class='titulo' width='10%' align='center'>Nº Requisição</td>
	  <td nowrap class='titulo' width='10%' align='center'>Peça</td>
	  <td nowrap class='titulo' width='30%' align='center'>Descrição Produto</td>
	  <td nowrap class='titulo' width='10%' align='center'>Usuário</td>
	  <td nowrap class='titulo' width='10%' align='center'>Qtd disp.</td>
	  <td nowrap class='titulo' width='10%' align='center'>Qtd ent.</td>
	  <td nowrap class='titulo' width='10%' align='center'>Qtd Sol.</td>
	  <td nowrap class='titulo' width='10%' align='center'>Qtd Comp.</td>
	  <td nowrap class='titulo' width='10%' align='center'>Selecao</td>
	</tr>


<?

$sql= "	SELECT 
			tbl_peca.peca,
			tbl_peca.referencia,
			tbl_peca.descricao as nome_prod, 
			tbl_pessoa.nome,
			tbl_requisicao_lista_item.requisicao_lista_item,
			tbl_requisicao_lista_item.requisicao_lista,
			tbl_requisicao_lista_item.quantidade_disponivel as qd, 
			tbl_requisicao_lista_item.quantidade_entregar as qe, 
			tbl_requisicao_lista_item.quantidade_solicitada as qs, 
			tbl_requisicao_lista_item.quantidade_comprar as qc,
			tbl_requisicao_lista_item.selecao,
			tbl_requisicao_lista_item.requisicao
		FROM tbl_requisicao_lista
		JOIN tbl_requisicao_lista_item using(requisicao_lista)
		JOIN tbl_peca					on tbl_peca.peca			 = tbl_requisicao_lista_item.peca
		JOIN tbl_requisicao				on tbl_requisicao.requisicao = tbl_requisicao_lista_item.requisicao
		JOIN tbl_empregado				on tbl_empregado.empregado	 = tbl_requisicao.empregado
		JOIN tbl_pessoa					on tbl_pessoa.pessoa		 = tbl_empregado.pessoa
		WHERE tbl_requisicao_lista.requisicao_lista = $requisicao_lista";


//echo "sql: $sql";
$res= pg_exec($con, $sql);
if(@pg_numrows($res)>0){
	$c=1;
	for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ){
		$requisicao				=trim(pg_result($res,$i,requisicao));
		$requisicao_lista_iten	=trim(pg_result($res,$i,requisicao_lista_item));
		$requisicao_lista		=trim(pg_result($res,$i,requisicao_lista));
		$peca					=trim(pg_result($res,$i,peca));
		$referencia				=trim(pg_result($res,$i,referencia));
		$nome_prod				=trim(pg_result($res,$i,nome_prod));
		$requisicao_login		=trim(pg_result($res,$i,nome));
		$qd		=trim(pg_result($res,$i,qd));
		$qe		=trim(pg_result($res,$i,qe));
		$qs		=trim(pg_result($res,$i,qs));
		$qc		=trim(pg_result($res,$i,qc));
		$selecao=trim(pg_result($res,$i,selecao));

		if ($cor=="#fafafa")	$cor= "#eeeeff";
		else					$cor= "#fafafa";

		echo "<tr bgcolor='$cor' class='table_line'>"; 
	    echo "<td nowrap align='center'>";
        echo "<a href='requisicao.php?requisicao=$requisicao'>
			<font color='#0000ff'>$requisicao</font></a>";
	    echo "</td>";		
		echo "<td nowrap align='left'>$referencia</td>";
	    echo "<td nowrap align='left'> $nome_prod</td>";
	    echo "<td nowrap align='center' >$requisicao_login</td>";
	    echo "<td nowrap align='center' >$qd</td>";
	    echo "<td nowrap align='center' >$qe</td>";
	    echo "<td nowrap align='center' >$qs</td>";
	    echo "<td nowrap align='center' >$qc</td>";
	    if($selecao=='selecao')
		  echo "<td nowrap align='center' ><font color='#0000ff'>$selecao</font></td>";
		else
		  echo "<td nowrap align='center' >$selecao</td>";
	    echo "</tr>";
	    $c++;
	}
}else{
			echo "<tr bgcolor='#ff5555'><td colspan='5'> Sem requisições cadastradas&nbsp;</td></tr>"; 


}
?>  
  </form>
</table>
</body>
</html>
