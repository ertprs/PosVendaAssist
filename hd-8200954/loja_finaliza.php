<?
#session_name("carrinho");
#session_start();

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$indice = $_SESSION[cesta][numero] ;


if (strlen($condicao) == 0) $aux_condicao = "null";
else                        $aux_condicao = $condicao ;

$sql = "INSERT INTO tbl_pedido (
			posto          ,
			fabrica        ,
			tipo_pedido    ,
			condicao       
		) VALUES (
			$cook_login_posto   ,
			$login_fabrica      ,
			102                 ,
			$aux_condicao       
		)";

$res = @pg_exec ($con,$sql);
$msg_erro = pg_errormessage($con);

if (strlen($msg_erro) == 0){
	$res = @pg_exec ($con,"SELECT CURRVAL ('seq_pedido')");
	$pedido  = @pg_result ($res,0,0);
}

for($i=1; $i<=($indice); $i++) {

	//PEGA O INDICE DO PRODUTO
	if (strlen($_SESSION[cesta][$i][produto])>0){
		$pedido      = $_SESSION[cesta][$i][pedido];
		$pedido_item = $_SESSION[cesta][$i][pedido_item];
		$cod_produto = $_SESSION[cesta][$i][produto];
		$qtde        = $_SESSION[cesta][$i][qtde];
		$valor       = $_SESSION[cesta][$i][valor];
		$descricao   = $_SESSION[cesta][$i][descricao];
		$valor_total = ($valor*$qtde);
		$soma        = ($soma+$valor_total);

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
				) ";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}
}
echo "<td width='568' align='right' valign='top'>";

$sql = "SELECT * FROM tbl_posto WHERE POSTO=$cook_login_posto";
$res = @pg_exec ($con,$sql);
if(pg_numrows($res)>0){
	$posto						= trim(pg_result ($res,0,posto));
	$nome						= trim(pg_result ($res,0,nome));
	$endereco					= trim(pg_result ($res,0,endereco));
	$complemento				= trim(pg_result ($res,0,complemento));
	$numero						= trim(pg_result ($res,0,numero));
	$cep						= trim(pg_result ($res,0,cep));
	$cidade						= trim(pg_result ($res,0,cidade));
	$estado						= trim(pg_result ($res,0,estado));
	$bairro						= trim(pg_result ($res,0,bairro));
	$email						= trim(pg_result ($res,0,email));
	$fone						= trim(pg_result ($res,0,fone));
?>
<br>
<TABLE width=500px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
	<tr>
		<td class='inicio' colspan='4' height='15'>&nbsp;INFORMAÇÕES DO COMPRADOR&nbsp;</td>
	</tr>
	<TR>
		<TD class="titulo" width='90' height='15'>NOME&nbsp;</TD>
		<TD class="conteudo" height='15' width='200'>&nbsp;<? echo $nome ?></TD>
		<TD class="titulo" width='80'>FONE&nbsp;</TD>
		<TD class="conteudo"height='15'>&nbsp;<? echo $fone ?></TD>
	</TR>
	<TR>
		<TD class="titulo" height='15'>EMAIL&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $email ?></TD>
		<TD class="titulo" height='15'>CEP&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $cep ?></TD>
	</TR>
	<TR>
		<TD class="titulo" height='15'>ENDEREÇO&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $endereco ?></TD>
		<TD class="titulo" height='15'>NÚMERO&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $numero ?></TD>
	</TR>
	<TR>
		<TD class="titulo" height='15'>COMPLEMENTO&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $complemento ?></TD>
		<TD class="titulo" height='15'>BAIRRO&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $bairro ?></TD>
	</TR>
	<TR>
		<TD class="titulo">CIDADE&nbsp;</TD>
		<TD class="conteudo">&nbsp;<? echo $cidade ?></TD>
		<TD class="titulo">ESTADO&nbsp;</TD>
		<TD class="conteudo">&nbsp;<? echo $estado ?></TD>
	</TR>
</TABLE>

<?
}

	echo "<br><center>Seu pedido foi finalizado com sucesso, o número do seu pedido é: <FONT SIZE='3' COLOR='#FF0000'><b>".$pedido."</b></FONT></center>";
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
		

		$valor_total = ($valor2*$qtde2);
		$soma = ($soma+$valor_total);

		$valor_total = number_format($valor_total, 2, ',', '');
		$soma        = number_format($soma, 2, ',', '');
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
		echo "</td>";
		echo "<td  colspan='3' height='30' align='right' bgcolor='#D5D5CB'> <font size='2'><B>Total da Compra:</B></font>";
		echo "</td>";
		echo "<td align='center' bgcolor='#D5D5CB'><font color='#008800'><b>R$ $soma</B></font>";
		echo "</td>";
		echo "<td width='13' bgcolor='#D5D5CB'>";
		echo "</td>";
	echo "</tr>";
	//TOTAL
	

	echo "</table>";
	echo "<BR>";
	
echo "</td>";
echo "</tr>";

echo "<tr>";
echo "<td colspan='2' height='60' bgcolor='#f3f2f1' align='center'>&nbsp;Home&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;Quem Somos&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;Cadastro&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;Destaque&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;Fale Conosco<BR>
Akácia 2006 -  Todos os direitos Reservados<BR>
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