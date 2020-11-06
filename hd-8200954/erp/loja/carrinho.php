<?
session_name("carrinho");
session_start();

$cod_produto = $_POST['cod_produto'];
$qtde        = $_POST['qtde'];
$valor       = $_POST['valor'];
$descricao   = $_POST['descricao'];

if(strlen($_POST['cod_produto'])>0){
//verifica se ja existe o produto na cesta
	$indice = $_SESSION[cesta][numero] ;
//echo"$indice";
	for($i=1; $i<=($indice); $i++) {
		//PEGA O INDICE DO PRODUTO
		if (strlen($_SESSION[cesta][$i][produto])>0){
			$produto = $_SESSION[cesta][$i][produto];
			if ($produto==$cod_produto){
				$xqtde = $_SESSION[cesta][$i][qtde];
				$xqtde = $xqtde+$qtde;
				$_SESSION[cesta][$i][qtde]=$xqtde;
				$cad=1;
			}
		}
	}
	if($cad<>1){
		//cadastrando
		$indice = $_SESSION[cesta][numero] ;
		$indice = $indice + 1;
		$_SESSION[cesta][$indice][produto]   = $cod_produto;
		$_SESSION[cesta][$indice][qtde]      = $qtde;
		$_SESSION[cesta][$indice][valor]     = $valor;
		$_SESSION[cesta][$indice][descricao] = $descricao;
		$_SESSION[cesta][numero]             = $indice;
	}
}

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
		unset($_SESSION[cesta][$id][descricao]);
		unset($_SESSION[cesta][$id]);
		unset($_SESSION[cesta][$indice][numero]);
		$indice = $indice-1;
	}
}

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


$indice = $_SESSION[cesta][numero] ;
if($indice==0){
	echo "<BR><BR><center><B>Seu carrinho está vazio!!</B></center>";
}else{
	$login_posto = $_COOKIE['cook_pessoa'];
	if($login_posto){
		$sql="SELECT * FROM tbl_pessoa WHERE pessoa = $login_posto";
		$res = pg_exec ($con,$sql);
		if(pg_numrows($res)>0){
			$pessoa  = trim(pg_result ($res,0,pessoa));
		}
	}
	echo "<table width='550' border='0' align='center' cellpadding='0' cellspacing='0' style='font-family: verdana; font-size: 12px'>";
	echo "<tr>";
	echo "<td colspan='7' align='right'><a href='index.php'>Continuar Comprando</a>  |  <a href='$PHP_SELF?acao=limpa'>Limpar Carrinho</a>  |  <a href='identificacao.php'>Fechar Pedido</a>";
	echo "</td>";
	echo "</tr>";
	// cabeca
	echo "<tr>";
		echo "<td bgcolor='#F8F8F8'  width='13' height='40' align='center'><IMG SRC='corpo_dir1.jpg' width='13'  height='40'>";
		echo "</td>";
		echo "<td width='60' height='40' background='corpo_dir2.jpg' align='center'>Remover?";
		echo "</td>";
		echo "<td width='200' height='40' background='corpo_dir2.jpg' align='center'>Produto";
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
	
	//echo"$indice";
	for($i=1; $i<=($indice); $i++) {
		//PEGA O INDICE DO PRODUTO
		if (strlen($_SESSION[cesta][$i][produto])>0){
			$cod_produto2 = $_SESSION[cesta][$i][produto];
			$qtde2 = $_SESSION[cesta][$i][qtde];
			$valor2 = $_SESSION[cesta][$i][valor];
			$descricao2 = $_SESSION[cesta][$i][descricao];
			
			$valor2 = str_replace(",",".",$valor2);
			$valor_total = ($valor2*$qtde2);
			$valor2 = str_replace(".",",",$valor2);
			$soma += $valor_total;

			$valor_total = number_format($valor_total, 2, ',', '');
			//EXIBE OS PRODUTOS DA CESTA
			$a++;
			$cor = "#FFFFFF"; 
			if ($a % 2 == 0) {
				$cor = '#ECECE1';
			}
			echo "<tr>";

			echo "<td  bgcolor='$cor' width='13' align='center'>";
			echo "</td>";
			echo "<td  bgcolor='$cor' width='4' align='center'><a href='$PHP_SELF?acao=remover&id=$i'><IMG SRC='x.gif' alt='Remover Produto'border='0'></a>";
			echo "</td>";
			echo "<td  bgcolor='$cor' width='200' align='center'>$descricao2<BR><BR>";
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

	//TOTAL
	echo "<tr>";
		echo "<td bgcolor='#D5D5CB'>";
		$soma        = number_format($soma, 2, ',', '');
		echo "</td>";
		echo "<td  colspan='4' height='30' align='right' bgcolor='#D5D5CB'> <font size='2'><B>Total da Compra:</B></font>";
		echo "</td>";
		echo "<td align='center' bgcolor='#D5D5CB'><font color='#008800'><b>R$ $soma</B></font>";
		echo "</td>";
		echo "<td width='13' bgcolor='#D5D5CB'>";
		echo "</td>";
	echo "</tr>";
	//TOTAL
	
	echo "<tr>";
	echo "<td colspan='7' align='right'><a href='index.php'>Continuar Comprando</a>  |  <a href='$PHP_SELF?acao=limpa'>Limpar Carrinho</a>  |  <a href='identificacao.php'>Fechar Pedido</a>";
	echo "</td>";
	echo "</tr>";	
	
	echo "</table>";
	echo "<BR>";
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
}
?>