<?
session_name("carrinho");
session_start();


include 'dbconfig.php';
include 'dbconnect-inc.php';
include 'configuracao.php';
include "topo.php";

$cook_pessoa = $_COOKIE['cook_pessoa'];
//echo ">>>".$cook_pessoa;

$indice = $_SESSION[cesta][numero] ;

if(strlen($condicao)== 0){
	$msg_erro = "Escolha uma forma de pagamento!";
}

if (strlen ($msg_erro) == 0 AND strlen ($btnG) > 0){

$res = pg_exec ($con,"BEGIN TRANSACTION");
$sql = "INSERT INTO tbl_pedido (
			posto          ,
			fabrica        ,
			tipo_pedido    ,
			condicao       
		) VALUES (
			$cook_pessoa   ,
			$login_empresa ,
			102            ,
			$condicao
		)";

$res = pg_exec ($con,$sql);
$msg_erro = pg_errormessage($con);
if (strlen($msg_erro) == 0){
	$res = pg_exec ($con,"SELECT CURRVAL ('seq_pedido')");
	$pedido  = pg_result ($res,0,0);
}

for($i=1; $i<=($indice); $i++) {
	//PEGA O INDICE DO PRODUTO
	if (strlen($_SESSION[cesta][$i][produto])>0){
		$cod_produto = $_SESSION[cesta][$i][produto];
		$qtde        = $_SESSION[cesta][$i][qtde];
		$valor       = $_SESSION[cesta][$i][valor];
		$descricao   = $_SESSION[cesta][$i][descricao];
		//$valor_total = ($valor*$qtde);
		//$soma        = ($soma+$valor_total);
	
		$valor=str_replace(",",".",$valor);

		$sql="INSERT INTO tbl_pedido_item 
				(
					pedido         ,
					peca           ,
					qtde           ,
					preco          
				)VALUES(
					$pedido        ,
					$cod_produto   ,
					$qtde          ,
					$valor
				)";

		$res = pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}
	}
	if (strlen ($msg_erro) == 0) {
	$res = pg_exec ($con,"COMMIT TRANSACTION");
	$msg_erro = "Pedido: $pedido gravado com sucesso!";

	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}
echo "<table width='750' border='0' align='center' cellpadding='0' cellspacing='0' style='font-family: verdana; font-size: 12px'>";
echo "<tr>";
echo "<td width='182' valign='top'>";
include "menu.php";
echo "<BR>";
echo "</td>";
echo "<form action='$PHP_SELF' method='post' name='frmfecharpedido'>";
echo "<td width='568' align='right' valign='top'>";
$sql = "SELECT * FROM tbl_pessoa WHERE pessoa=$cook_pessoa";
$res = pg_exec ($con,$sql);
if(pg_numrows($res)>0){
	$pessoa		= trim(pg_result ($res,0,pessoa));
	$nome		= trim(pg_result ($res,0,nome));
	$cnpj		= trim(pg_result ($res,0,cnpj));
	$ie			= trim(pg_result ($res,0,ie));
	$endereco	= trim(pg_result ($res,0,endereco));
	$cep		= trim(pg_result ($res,0,cep));
	$cidade		= trim(pg_result ($res,0,cidade));
	$estado		= trim(pg_result ($res,0,estado));
	$bairro		= trim(pg_result ($res,0,bairro));
	$email		= trim(pg_result ($res,0,email));
?>
<br>
<TABLE width="500px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
	<tr>
		<td class='inicio' colspan='4' height='15'>&nbsp;Informações do Cliente&nbsp;</td>
	</tr>
	<TR>
		<TD class="titulo" width='90' height='15'>Nome&nbsp;</TD>
		<TD class="conteudo" height='15' width='200'>&nbsp;<? echo $nome ?></TD>
		<TD class="titulo" height='15'>Email&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $email ?></TD>
	</TR>
	<TR>
		<TD class="titulo" height='15'>CPF&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $cnpj ?></TD>
		<TD class="titulo" height='15'>RG&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $ie ?></TD>

	</TR>
	<TR>
		<TD class="titulo" height='15'>Endereço&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $endereco ?></TD>
		<TD class="titulo" height='15'>CEP&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $cep ?></TD>
	</TR>
	<TR>
		<TD class="titulo" height='15'>Bairro&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $bairro ?></TD>
		<TD class="titulo">Cidade&nbsp;</TD>
		<TD class="conteudo">&nbsp;<? echo $cidade ?></TD>
	</TR>
	<TR>
		<TD class="titulo">Estado&nbsp;</TD>
		<TD class="conteudo">&nbsp;<? echo $estado ?></TD>
		<TD class="titulo">&nbsp;</TD>
		<TD class="conteudo">&nbsp;</TD>
	</TR>
</TABLE>

<?
}
	echo "<br>";
	if(strlen($msg_erro) > 0){
	echo "<div align='center'><FONT SIZE='2' COLOR='FF0000'>".$msg_erro."</FONT></div>";
	}
	if(strlen($pedido) > 0){
	echo "<center>Seu pedido foi finalizado com sucesso, o número do seu pedido é: <FONT SIZE='3' COLOR='#FF0000'><b>".$pedido."</b></FONT></center>";
	}
	echo "<table width='550' border='0' align='center' cellpadding='0' cellspacing='0' style='font-family: verdana; font-size: 12px'>";

	// cabeca
	echo "<tr>";
		echo "<td bgcolor='#F8F8F8'  width='13' height='40' align='center'><IMG SRC='corpo_dir1.jpg' width='13'  height='40'>";
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

		$valor2 = str_replace(",", ".", $valor2);
		$valor_total = ($valor2*$qtde2);
		$soma = ($soma+$valor_total);

		$valor2 = str_replace(".", ",", $valor2);

		$valor_total = number_format($valor_total, 2, ',', '');
		$valor_total = str_replace(".", ",", $valor_total);

		//EXIBE OS PRODUTOS DA CESTA
		$a++;
		$cor = "#FFFFFF"; 
		if ($a % 2 == 0) $cor = '#ECECE1';
		echo "<tr>";

		echo "<td  bgcolor='$cor' width='13' align='center'>";
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
		echo "<td  colspan='3' height='30' align='right' bgcolor='#D5D5CB'> <font size='2'><B>Total da Compra:</B></font>";
		echo "</td>";
		echo "<td align='center' bgcolor='#D5D5CB'><font color='#008800'><b>R$ $soma</B></font>";
		echo "</td>";
		echo "<td width='13' bgcolor='#D5D5CB'>";
		echo "</td>";
	echo "</tr>";
	//TOTAL

	echo "<TR>";
		echo "<TD heigth='20'>&nbsp;</TD>";
	echo "</TR>";

	//condicao de pagto
	echo "<tr >";
		echo "<TD colspan='3'>";
			echo "<TABLE class='tabela' width='170'>";
				echo"<TR>";
					echo "<td colspan='2' class='inicio' height='20'>Forma de Pagamento</td>";
				echo"</TR>";
			echo"</TABLE>";
		echo "</TD>";
	echo "</tr>";

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
			
				$valor_total = str_replace(",",".", $valor_total);
				$parcela = ($valor_total/$parcelas_qtde);
				
				$parcela = number_format($parcela, 2, ',', '');
				
				$valor_total = str_replace(".",",", $valor_total);
				$valor_total = number_format($valor_total, 2, ',', '');
				
	echo "<tr>";
		echo "<td colspan='3' >";
				echo"<table width=160><TR>";
					echo"<TD align='center'>";
						echo "<INPUT TYPE='radio' NAME='condicao' value='$condicao'>";
					echo"</TD>";
					echo"<TD class='titulo'>";
						echo "$descricao - $parcela";
					echo"</TD>";
				echo"</TR></table>";
		echo "</td>";
	echo "</tr>";
	}
	/*#############################################################################*/
	echo "<tr>";
		echo "<td colspan='3' >";
			echo "<BR>";
		echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td colspan='5' align='right'>";
		if(strlen($btnG)==0){	
			echo "<INPUT TYPE= 'submit' name='btnG' value='Finalizar'>";
		}
		echo "</td>";
	echo "</tr>";
	echo "</form>";
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

echo "</td>";
echo "</tr>";
echo "</table>";
	session_unset();
	session_destroy();

?>