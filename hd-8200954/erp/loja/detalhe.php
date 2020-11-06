<script language='javascript'>
function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseInt(num);
	if (campo.value=='NaN') {
		campo.value='';
	}
}
</script>
<?
echo "<BODY TOPMARGIN=0>";

include "topo.php";

echo "<table width='750' border='0' align='center' cellpadding='0' cellspacing='0' style='font-family: verdana; font-size: 12px'>";
echo "<tr>";
echo "<td width='182' valign='top'>";
include "menu.php";
echo "<BR>";
echo "</td>";
echo "<td width='568' align='right' valign='top'>";
//corpo do produto
	echo "<table width='550' border='0' align='center' cellpadding='2' cellspacing='2' style='font-family: verdana; font-size: 12px'>";
	echo "<tr>";
		echo "<td colspan='2' style=' font-size: 12px' align='left'><IMG SRC='produto.gif' width='530'>";
		echo "</td>";
	echo "</tr>";
	echo "<form action='carrinho.php' method='post' name='frmcarrinho'>";
	echo "<tr>";
	echo "<td align='center' width='150' align='baseline'>";

	$sqlx="select tbl_peca_item_foto.peca, caminho, tbl_peca.peca FROM tbl_peca_item_foto JOIN tbl_peca ON tbl_peca.peca = tbl_peca_item_foto.peca where tbl_peca_item_foto.peca = $cod_produto";
	
	$xres = pg_exec ($con,$sqlx);

	if(strlen(pg_numrows($xres)>0)){
		$caminho = trim(pg_result ($xres,0,caminho));

	}else{
		$caminho = "produtos/552497-semimagem.jpg";
	}

	$caminho = str_replace("/www/assist/www/erp/","",$caminho);
	if($caminho <>"produtos/552497-semimagem.jpg"){$caminho = "../".$caminho;}

	echo "<IMG SRC='$caminho' width='120' border='0'><BR>";

	echo "</td>";

	/*##################################### SELECT ######################################
	#####################################################################################*/

	include 'dbconfig.php';
	include 'dbconnect-inc.php';
	include 'configuracao.php';

	$cod_produto = $_GET['cod_produto'];

		
		$sql = "SELECT tbl_peca.peca        ,
			tbl_peca.referencia             ,
			tbl_peca.estoque                ,
			tbl_peca.descricao              ,
			tbl_peca.garantia_diferenciada  ,
			tbl_peca.informacoes            ,
			tbl_tabela_item_erp.peca        ,
			tbl_tabela_item_erp.preco
			FROM tbl_peca
		left JOIN tbl_tabela_item_erp ON tbl_tabela_item_erp.peca=tbl_peca.peca
		WHERE tbl_peca.peca=$cod_produto";
	
	$res = pg_exec ($con,$sql);
	if(pg_numrows($res)>0){
		$cod_produto			= trim(pg_result ($res,0,peca));
		$referencia				= trim(pg_result ($res,0,referencia));
		$descricao				= trim(pg_result ($res,0,descricao));
		$preco_compra			= trim(pg_result ($res,0,preco));
		$estoque				= trim(pg_result ($res,0,estoque));
		$garantia_diferenciada	= trim(pg_result ($res,0,garantia_diferenciada));
		$informacoes			= trim(pg_result ($res,0,informacoes));
		}
		
	$preco_compra = number_format($preco_compra, 2, ',', '');
	/*##################################### FIM SELECT ##################################
	#####################################################################################*/

	echo "<td valign='top' width='400' align='top'>
		<input type='hidden' name='cod_produto' value='$cod_produto'>
		<input type='hidden' name='valor' value='$preco_compra'>
		<input type='hidden' name='descricao' value='$descricao'>
		<input type='hidden' name='linha' value='$linha'>
		<B>$descricao</B>
		<BR>Disponibilidade de Estoque: $estoque<BR>
		Tempo de Garantia: $garantia_diferenciada MES(ES)<BR><B>Valor: R$ $preco_compra</B><BR>
		Qtde: <input type='text' size='3' maxlength='3' name='qtde' value='";
		if(strlen($qtde)==0){ $qtde= "1"; echo $qtde;}
		else                  $qtde= "$qtde_minima_site";
		echo "' class='Caixa' onblur='javascript:
			checarNumero(this);
			if (this.value < $qtde || this.value==\"\" ) {
				alert(\"Quantidade abaixo da mínima permitida. A quantidade mínima para compra desta peça é de $qtde!\");
				this.value=\"$qtde\";
			}
		'>";
	
	echo "&nbsp;&nbsp;&nbsp;<input type='submit' name='btn_comprar' value='Comprar' class='botao'>";
	echo "</td>";
	echo "</tr>";
	echo "</form>";
	echo "<tr>";
	echo "<td colspan='2' align='baseline'><IMG SRC='forma.gif' width='530'>";
	echo "</td>";
	echo "</tr>";
	//alterado Gustavo HD 3389
	/*#############################################################################*/
			$sql = "SELECT condicao,descricao,parcelas,visivel
			FROM tbl_condicao
			WHERE fabrica=$login_empresa
			AND visivel IS TRUE
			ORDER BY parcelas ASC";
		$res = pg_exec ($con,$sql) ;
		//echo $sql;
			for ($k = 0; $k <pg_numrows($res); $k++) {
				$condicao      = trim(pg_result($res,$k,condicao));
				$descricao      = trim(pg_result($res,$k,descricao));
				$parcelas      = trim(pg_result($res,$k,parcelas));

				$parcelas_array = explode("|",$parcelas);
				$parcelas_qtde  = count($parcelas_array);

				$preco_compra = str_replace(",",".", $preco_compra);
				$parcela = ($preco_compra/$parcelas_qtde);
				
				
				$parcela = number_format($parcela, 2, ',', '');
				
				$preco_compra = str_replace(".",",", $preco_compra);
				$preco_compra = number_format($preco_compra, 2, ',', '');
				
	echo "<tr>";
		echo "<td colspan='2' align='top' style='font-family: verdana; font-size: 12px'>";
				echo"<TR>";
					echo"<TD bgcolor='#D8D8D8' align='center'>$descricao - $parcela</TD>";
				echo"</TR>";
		echo "</td>";
	echo "</tr>";
	}
	/*#############################################################################*/
	echo "<tr>";
		echo "<td  colspan='2'  align='baseline'><IMG SRC='descrica.gif' width='530'>";
		echo "</td>";
	echo "</tr>";
	echo "<tr>";
		echo "<td colspan='2' align='top' style='font-family: verdana; font-size: 12px'>
		$informacoes<BR><BR>";
		echo "</td>";
	echo "</tr>";
	echo "</table>";
//corpo do produto
echo "</td>";
echo "</tr>";
	echo "<tr>";
		echo "<td colspan='2' height='60' bgcolor='#f3f2f1' align='center'>
		&nbsp;<a href='index.php'>Home</a>&nbsp;&nbsp;&nbsp;|
		&nbsp;&nbsp;&nbsp;<a href='empresa.php'>Quem Somos</a>&nbsp;&nbsp;&nbsp;|
		&nbsp;&nbsp;&nbsp;<a href='cadatro.php'>Cadastro</A>&nbsp;&nbsp;&nbsp;|
		&nbsp;&nbsp;&nbsp;<a href='promocao.php'>Destaque</A>&nbsp;&nbsp;&nbsp;|
		&nbsp;&nbsp;&nbsp;<a href='#'>Fale Conosco</a><BR>
		Tecnoplus 2007 -  Todos os direitos Reservados<BR>
		Sistema Telecontrol";
		echo "</td>";
	echo "</tr>";
echo "</table>";

?>