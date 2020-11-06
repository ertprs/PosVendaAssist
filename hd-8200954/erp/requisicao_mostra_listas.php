<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';

include 'menu.php';

################### PESQUISA ##########################

	//--================================================
	if(($tipo == 'ab') && (strlen($busca)>0)){
			$pesquisa = "where tbl_cotacao.status='aberta' AND UPPER(pessoa_empregado.nome) ILIKE UPPER('%$busca%')";
		}
	//--================================================
	if(($tipo == 'fi') && (strlen($busca)>0)){
			$pesquisa = "where tbl_cotacao.status='finalizada'  AND UPPER(pessoa_empregado.nome) ILIKE UPPER('%$busca%')";
		}
		//--================================================
	if(($tipo == 'ab') && (strlen($busca)==0)){
			$pesquisa = "where tbl_cotacao.status='aberta'";
		}
	//--================================================
	if(($tipo == 'fi') && (strlen($busca)==0)){
			$pesquisa = "where tbl_cotacao.status='finalizada'";
		}

#########################################################
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
  <tr bgcolor='#596D9B'>	
    <td colspan='7' nowrap class='menu_top' align='left' background='imagens/azul.gif'><font size='3'>Cotação de Compra</font></td>
  </tr>
  <tr bgcolor='#596D9B'>	
    <td nowrap class='titulo' width='20%' align='center'>Nº Pedido</td>
	<td nowrap class='titulo' width='20%' align='center'>Cotação de Pedido</td>
    <td nowrap class='titulo' width='20%' align='center'>Data de Geração</td>
    <td nowrap class='titulo' width='15%' align='center'>Usuário</td>
    <td nowrap class='titulo' width='25%' align='center'>Qtd de Itens Solicitados</td>
    <td nowrap class='titulo' width='20%' align='center'>Status</td>
  </tr>
<?

$sql= "SELECT 
			DISTINCT (tbl_requisicao_lista.requisicao_lista), 
			to_char(tbl_requisicao_lista.data,'DD/MM/YYYY')as data, 
			COUNT(requisicao_lista_item) AS  qtd, 
			pessoa_empregado.nome,
			tbl_cotacao.cotacao,
			tbl_cotacao.status
		FROM tbl_requisicao_lista
		JOIN tbl_cotacao using(requisicao_lista)
		JOIN tbl_requisicao_lista_item USING(requisicao_lista)
		JOIN tbl_empregado on tbl_empregado.empregado = tbl_requisicao_lista.empregado
		JOIN tbl_pessoa as pessoa_empregado on pessoa_empregado.pessoa = tbl_empregado.pessoa
		$pesquisa
		GROUP BY 
				tbl_requisicao_lista.requisicao_lista, 
				tbl_requisicao_lista.data, 
				pessoa_empregado.nome,
				tbl_cotacao.cotacao,
				tbl_cotacao.status
		ORDER BY 
				tbl_cotacao.status,
				requisicao_lista DESC";

//echo "sql: $sql";

$res= pg_exec($con, $sql);
if(@pg_numrows($res)>0){
	$c=1;
	for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {
		$requisicao_lista=trim(pg_result($res,$i,requisicao_lista));
		$data			 =trim(pg_result($res,$i,data));
		$nome			 =trim(pg_result($res,$i,nome));
		$status			 =trim(pg_result($res,$i,status));
		$qtd			 =trim(pg_result($res,$i,qtd));
		$cotacao		 =trim(pg_result($res,$i,cotacao));

		if ($c==2){ 
			echo "<tr bgcolor='#eeeeff' class='table_line'>"; 
			$c=0;
		}else {
			echo "<tr bgcolor='#fafafa' class='table_line'>";
		}

		echo "<td nowrap align='center'>";
        echo "<a href='requisicao_mostra_listas_item.php?requisicao_lista=$requisicao_lista'><font color='#0000ff'>$requisicao_lista</font></a>";
	    echo "</td>";
		echo "<td nowrap align='center'>";
        echo "<a href='cotacao_mapa.php?cotacao=$cotacao'><font color='#0000ff'>$requisicao_lista</font></a>";
	    echo "</td>";
	    echo "<td nowrap align='center'> $data</td>";
	    echo "<td nowrap align='center' >$nome</td>";
	    echo "<td nowrap align='center' >$qtd</td>";
	    if($status=='aberta')
		  echo "<td nowrap align='center' ><font color='#0000ff'>$status</font></td>";
		else
		  echo "<td nowrap align='center' >$status</td>";
	    echo "</tr>";
	    $c++;
	}
}else{
	echo "<tr ><td colspan='6' align='center'> <font color='#0000ff'><b>Nenhum Pedido foi gerado!</font></b></td></tr>"; 
}
?>  
  </form>
</table>
<A HREF="javascript:history.go(-1);">Nova Pesquisa</A>
</body>
</html>
