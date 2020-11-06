<?
//INICIALIZA A SESSÃO
session_start();

//RECEBE AS VARIÁVEIS
$v_prod = $_POST["txtprod"];

//PEGA A CHAVE DO ARRAY
$chave = array_keys($v_prod);

//EXIBE
for($i=0; $i<sizeof($chave); $i++) {
$indice = $chave[$i];

//VERIFICA
if(!empty($v_prod[$indice][QTDE]) ) {

//GRAVA NO ARRAY CESTA
$cesta[$indice][CODIGO] = $v_prod[$indice][CODIGO];
$cesta[$indice][VALOR] = $v_prod[$indice][VALOR];
$cesta[$indice][QTDE] = $v_prod[$indice][QTDE];
}//FECHA IF
}//FECHA FOR

//GRAVA NA SESSÃO
$_SESSION[cesta] = $cesta;
?>
<TABLE>
<?
//PEGA A CHAVE
$chave_cesta = array_keys($_SESSION[cesta]);
echo "$chave_cesta";
//EXIBE OS PRODUTOS DA CESTA
for($i=0; $i<sizeof($chave_cesta); $i++) {
$indice = $chave_cesta[$i];
?>

<tr>
<td height="25">&nbsp;</td>
<td height="25"><font face='Arial' size='2'>CODIGO<? echo $_SESSION[cesta][$indice][CODIGO]; ?></font></td>
<td height="25"><font face='Arial' size='2'>VALOR<? echo $_SESSION[cesta][$indice][VALOR]; ?></font></td>
<td height="25"><font face='Arial' size='2'>QTDE <? echo $_SESSION[cesta][$indice][QTDE]; ?></font></td>
</tr>

<?
}//FECHA FOR ?>
</TABLE>

<?

//INICIALIZA A SESSÃO
/*
session_name("carrinho");
session_start();
//cadastrando
$chave = array_keys($_SESSION[cesta]);
echo "$chave";
$indice = $_SESSION[cesta][numero] ;
$indice = $indice + 1;
$_SESSION[cesta][$indice][produto] = $cod_produto = $_POST['cod_produto'];
$_SESSION[cesta][$indice][qtde] = $qtde = $_POST['qtde'];
$_SESSION[cesta][$indice][valor] = $valor = $_POST['valor'];
$_SESSION[cesta][numero]= $indice;

/*
//RECEBE AS VARIÁVEIS
$v_prod = $_POST["txtprod"];

//PEGA A CHAVE DO ARRAY
$chave = array_keys($v_prod);


//EXIBE
for($i=0; $i<sizeof($chave); $i++) {
$indice = $chave[$i];


//GRAVA NO ARRAY CESTA
 
$cesta[produto] = $cod_produto;
$cesta[qtde] = $qtde;


//GRAVA NA SESSÃO
$_SESSION[cesta][$indice] = $cesta;
*//*
$acao =$_GET['acao'];
if(strlen($acao)>0){
if($acao=='limpa'){
//echo"entrou";
session_unset();
session_destroy();
}

if($acao=='remover'){
$id =$_GET['id'];
//echo"entra";
unset($_SESSION[cesta][$id][produto]);
unset($_SESSION[cesta][$id][qtde]);
unset($_SESSION[cesta][$id][valor]);
unset($_SESSION[cesta][$id]);
}

}

*/
?>

<BODY TOPMARGIN=0>
<?

include "topo.php";
echo "<table width='750' border='0' align='center' cellpadding='0' cellspacing='0' style='font-family: verdana; font-size: 12px'>";
echo "<tr>";
echo "<td width='182' valign='top'>";
include "menu.php";
echo "<BR>";
echo "</td>";
echo "<td width='568' align='right' valign='top'>";
echo "<BR>";
	echo "<table width='550' border='1' align='center' cellpadding='0' cellspacing='0' style='font-family: verdana; font-size: 12px'>";
	echo "<tr>";
	echo "<td colspan='8' align='right'><a href='index.php'>Continuar Comprando</a>  |  <a href='$PHP_SELF?acao=limpa'>Limpar Carrinho</a>  |  Fechar Pedido";
	echo "</td>";
	echo "</tr>";
	// cabeca
	echo "<tr>";
		echo "<td bgcolor='#F8F8F8'  width='13' height='40' align='center'><IMG SRC='corpo_dir1.jpg' width='13'  height='40'>";
		echo "</td>";
		echo "<td width='4' background='corpo_dir2.jpg' >";
		echo "</td>";
		echo "<td width='60' height='40' background='corpo_dir2.jpg' align='center'>Código";
		echo "</td>";
		echo "<td width='200' height='40' background='corpo_dir2.jpg' align='center'>Descrição";
		echo "</td>";
		echo "<td width='50' height='40' background='corpo_dir2.jpg' align='center'>Qtde";
		echo "</td>";
		echo "<td width='100' height='40' background='corpo_dir2.jpg' align='center'>Valor Unit.";
		echo "</td>";
		echo "<td width='110' height='40' background='corpo_dir2.jpg' align='center'>Valor Total";
		echo "</td>";
		echo "<td width='13' height='40' align='center'><IMG SRC='corpo_dir3.jpg' width='13'  height='40'>";
		echo "</td>";
	echo "</tr>";
	//fim cabeca
	
 	for($i=1; $i<=($indice); $i++) {
    	//PEGA O INDICE DO PRODUTO
	if (strlen($_SESSION[cesta][$i])>0){
	$cod_produto2 = $_SESSION[cesta][$i][produto];
	$qtde2 = $_SESSION[cesta][$i][qtde];
	$valor2 = $_SESSION[cesta][$i][valor];
	$valor_total = ($valor2*$qtde2);
	$soma = ($soma+$valor_total);
	//EXIBE OS PRODUTOS DA CESTA
 	$cor = "#FFFFFF"; 
	if ($i % 2 == 0) $cor = '#ECECE1';
	
	
	echo "<tr>";
		echo "<td  bgcolor='$cor' width='13' align='center'>";
		echo "</td>";
		echo "<td  bgcolor='$cor' width='4' align='center'><a href='$PHP_SELF?acao=remover&id=$i'>e</a>";
		echo "</td>";
		echo "<td  bgcolor='$cor' width='60' align='center'>$cod_produto2";
		echo "</td>";
		echo "<td  bgcolor='$cor' width='200' align='center'>Descrição asda sa dasd asdada asda sa dasd asdada asda sa dasd asdada asda sa dasd asdada ";
		echo "</td>";
		echo "<td  bgcolor='$cor' width='50' align='center'>$qtde2";
		echo "</td>";
		echo "<td  bgcolor='$cor' width='100' align='center'>R$ $valor2";
		echo "</td>";
		echo "<td  bgcolor='$cor' width='110' align='center'>R$ $valor_total";
		echo "</td>";
		echo "<td  bgcolor='$cor' width='13' align='center'>";
		echo "</td>";
	echo "</tr>";
}
}




		/*
	
	echo "<tr>";
		echo "<td  bgcolor='#ECECE1' width='13' align='center'>";
		echo "</td>";
		echo "<td  bgcolor='#ECECE1' width='60' align='center'>$cod_produto2 ";
		echo "</td>";
		echo "<td  bgcolor='#ECECE1' width='200' align='center'>Descrição asda sa dasd asdada asda sa dasd asdada asda sa dasd asdada asda sa dasd asdada asda sa dasd asdada asda sa dasd asdada ";
		echo "</td>";
		echo "<td  bgcolor='#ECECE1' width='50' align='center'>$qtde2";
		echo "</td>";
		echo "<td  bgcolor='#ECECE1' width='100' align='center'>Valor Unit.";
		echo "</td>";
		echo "<td  bgcolor='#ECECE1' width='114' align='center'>Valor Total";
		echo "</td>";
		echo "<td  bgcolor='#ECECE1' width='13' align='center'>";
		echo "</td>";
	echo "</tr>";
	*/
	//TOTAL
	echo "<tr>";
		echo "<td bgcolor='#D5D5CB'>";
		echo "</td>";
		echo "<td  colspan='5' height='30' bgcolor='#D5D5CB'> <font size='2'><B>Total da Compra:</B></font>";
		echo "</td>";
		echo "<td align='center' bgcolor='#D5D5CB'>R$ $soma";
		echo "</td>";
		echo "<td width='13' bgcolor='#D5D5CB'>";
		echo "</td>";
	echo "</tr>";
	//TOTAL
	
	echo "<tr>";
	echo "<td colspan='8' align='right'>Continuar Comprando  |  Limpar Carrinho  |  Fechar Pedido";
	echo "</td>";
	echo "</tr>";	
	
	echo "</table>";
	
echo "</td>";
echo "</tr>";

echo "<tr>";
echo "<td colspan='2' height='60' bgcolor='#f3f2f1' align='center'>&nbsp;Home&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;Quem Somos&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;Cadastro&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;Destaque&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;Fale Conosco<BR>
Tecnoplus 2007 -  Todos os direitos Reservados<BR>
Sistema Telecontrol

";
echo "</td>";
echo "</tr>";


echo "</table>";

?>