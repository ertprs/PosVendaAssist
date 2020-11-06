<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$ajax = $_GET['ajax'];
if(strlen($ajax)>0){
	$peca = $_GET['peca'];
	if ($dh = opendir('imagens_pecas/media/')) {
		echo"<center>
			<img src='imagens_pecas/media/$peca' border='0'>
			</center>";
	}
	exit;
}

$layout_menu = 'pedido';
$title="Detalhes do produto!";
include "cabecalho.php";

?>
<script type="text/javascript" src="admin/js/jquery-latest.pack.js"></script>

<script src="admin/js/jquery.MultiFile.js" type="text/javascript" language="javascript"></script>
<script src="admin/js/jquery.MetaData.js" type="text/javascript" language="javascript"></script>
<script type="text/javascript" src="admin/js/thickbox.js"></script>
<link rel="stylesheet" href="admin/js/thickbox.css" type="text/css" media="screen" />

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

$cod_produto = $_GET['cod_produto'];

	$sql = "SELECT  
				tbl_peca.peca            ,
				referencia              ,
				tbl_peca.ipi            ,
				descricao               ,
				estoque                 ,
				garantia_diferenciada   , 
				informacoes             ,
				linha_peca              ,
				multiplo_site           ,
				qtde_minima_site        ,
				qtde_max_site           ,
				qtde_disponivel_site
			FROM tbl_peca 
			WHERE tbl_peca.peca='$cod_produto'";

	$res = pg_exec ($con,$sql);

	if(pg_numrows($res)>0){
		$peca						= trim(pg_result ($res,0,peca));
		$referencia					= trim(pg_result ($res,0,referencia));
		$ipi						= trim(pg_result ($res,0,ipi));
		$descricao					= trim(pg_result ($res,0,descricao));
		$estoque					= trim(pg_result ($res,0,estoque));
		$garantia_diferenciada		= trim(pg_result ($res,0,garantia_diferenciada));
		$informacoes				= trim(pg_result ($res,0,informacoes));
		$linha						= trim(pg_result ($res,0,linha_peca));
		$multiplo_site 				= trim(pg_result ($res,0,multiplo_site));
		$qtde_minima_site			= trim(pg_result ($res,0,qtde_minima_site));
		$qtde_max_site				= trim(pg_result ($res,0,qtde_max_site));
		$qtde_disponivel_site		= trim(pg_result ($res,0,qtde_disponivel_site));
	
		$sql2 = "SELECT DISTINCT tbl_produto.linha
				FROM tbl_produto 
				JOIN tbl_lista_basica USING(produto)
				JOIN tbl_peca USING(peca)
				WHERE peca = $peca LIMIT 1";
		$res2 = pg_exec ($con,$sql2);
		$linha = trim(pg_result ($res2,0,linha));

		$sql3 = "SELECT preco
				 FROM tbl_tabela_item
				 WHERE peca  = $peca
				 AND   tabela IN (
					SELECT tbl_tabela.tabela
					 FROM tbl_posto_linha 
					 JOIN tbl_tabela       USING(tabela)
					 JOIN tbl_posto        USING(posto) 
					 JOIN tbl_linha        USING(linha)
					 WHERE tbl_posto.posto       = $login_posto
					 AND   tbl_linha.fabrica     = $login_fabrica
					 AND   tbl_posto_linha.linha = $linha
				)";
		$res3 = pg_exec ($con,$sql3);
		$preco = trim(pg_result ($res3,0,preco));
		$preco_formatado = number_format($preco,2,'.',',');


		$parcelas = ($preco/2);
		$parcelas = number_format($parcelas, 2, ',', '');
		$preco_formatado    = number_format($preco, 2, ',', '');

	}


//corpo do produto
include 'loja_menu.php';

echo "<form action='loja_carrinho.php?acao=adicionar' method='post' name='frmcarrinho' align='center'>";
echo "<table width='700' border='0' align='center' cellpadding='0' cellspacing='0' style=' border:#B5CDE8 1px solid;  bordercolor='#d2e4fc'>";

echo "<tr>";
echo "<td align='left'  colspan='2' bgcolor='#e6eef7' ><br>&nbsp;<B>$descricao</B><br>&nbsp;</td>";
echo "</tr>";

echo "<tr>";
echo "<td align='center' width='150' ><br>";
if ($dh = opendir('imagens_pecas/pequena/')) {
	$contador=0;
	while (false !== ($filename = readdir($dh))) {
		if($contador == 1) break;
		if (strpos($filename,$referencia) !== false){
			$contador++;
			//$peca_referencia = ntval($peca_referencia);
			$po = strlen($referencia);
			if(substr($filename, 0,$po)==$referencia){?>
				<div class='contenedorfoto'><a href="<? echo "$PHP_SELF?ajax=true&peca=$filename"; ?>&keepThis=trueTB_iframe=true&height=340&width=420" title="Imagem" class="thickbox">
				<img src='imagens_pecas/pequena/<?echo $filename; ?>' border='0'>
				<input type='hidden' name='peca_imagem' value='<?echo $filename; ?>'><br><font size='-4' color='#999999'>clique na foto para visualizar</font></a></div>
<?			}
		}
	}
}
echo "</td>";
// trabalhando HD 3780 - GUSTAVO
echo "<td valign='middle' align='left' class='Conteudo' width='450' >
	<input type='hidden' name='cod_produto' value='$cod_produto'>
	<input type='hidden' name='valor'       value='$preco'>
	<input type='hidden' name='ipi'         value='$ipi'>
	<input type='hidden' name='descricao'   value='$descricao'>
	<input type='hidden' name='linha'       value='$linha'>
	<input type='hidden' name='qtde_maxi'   value='$qtde_max_site'>
	<input type='hidden' name='qtde_disp'   value='$qtde_disponivel_site'>

	Qtde Multipla: $multiplo_site <BR>
	Qtde Máxima: $qtde_max_site <BR>
	Qtde Disponível: $qtde_disponivel_site <BR>
	<B>Valor: R$ $preco_formatado</B><BR>
	IPI: $ipi %<BR>
	Qtde: ";
	if($multiplo_site > 1){
		echo "<select name='qtde' class='Caixa' >";
		for($i=1;$i<=10;$i++){
			$aux = $i * $multiplo_site;
			if(($aux>=$qtde_minima_site) AND (strlen($qtde_max_site)>0 AND $aux<=$qtde_max_site))echo "<option value='$aux'>$aux</option>";
		}
		echo "</select>";
	}else{
		 echo "<input type='text' size='3' maxlength='3' name='qtde' value='";
		if(strlen($qtde_minima_site)==0){ $qtde_minima_site= "1"; echo $qtde_minima_site;}
		else                             $qtde_minima_site= "$qtde_minima_site";
		echo "' class='Caixa' onblur='javascript:
			checarNumero(this);
			if (this.value < $qtde_minima_site || this.value==\"\" ) {
				alert(\"Quantidade abaixo da mínima permitida. A quantidade mínima para compra desta peça é de $qtde_minima_site!\");
				this.value=\"$qtde_minima_site\";
			}
			if (this.value > $qtde_max_site || this.value==\"\" ) {
				alert(\"Quantidade acima da máxima permitida. A quantidade máxima para compra desta peça é de $qtde_max_site!\");
				this.value=\"$qtde_minima_site\";
			}
			'>";
	}
	echo "&nbsp;&nbsp;&nbsp;<input type='submit' name='btn_comprar' value='Comprar' class='botao'>";
echo "</td>";
echo "</tr>";
echo "<tr>";
	echo "<td colspan='2' align='top' class='Conteudo'>
	<br>";
	if(strlen($informacoes)>0) echo "<B>&nbsp;Informações</b><br><br>&nbsp;$informacoes";
	echo "<BR><BR>";
	echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<td bgcolor='#e6eef7'></td>";
echo "<td align='right' bgcolor='#e6eef7' ><a href='javascript:history.back()'>Voltar</a></td>";
echo "</tr>";
echo "</table>";
echo "</form>";

?>