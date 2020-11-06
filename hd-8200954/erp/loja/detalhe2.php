<?
$cod_produto = $_GET['cod_produto'];



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
//corpo do produto
 	echo "<table width='550' border='0' align='center' cellpadding='2' cellspacing='2' style='font-family: verdana; font-size: 12px'>";
	echo "<tr>";
		echo "<td colspan='2' style=' font-size: 12px' align='left'><IMG SRC='../produto.gif' width='530'>";
		echo "</td>";
	echo "</tr>";
	echo "<form action='carrinho2.php' method='post' name='frmcarrinho'>";
	echo "<tr>";
	echo "<td align='center' width='150' align='baseline'><IMG SRC='../cafeteira1a.jpg' width='120'><BR><font size='1'><< &nbsp;&nbsp; 1 &nbsp;&nbsp; | &nbsp;&nbsp; 2 &nbsp;&nbsp; >></font><BR><BR>";
	echo "</td>";
	echo "<td valign='top' width='400' align='top'>
		<input type='hidden' name='txtprod[$cod_produto][CODIGO]' value='<? echo $codigo; ?>'>
		<input type='hidden' name='txtprod[$cod_produto][VALOR]' value='<? echo $valor; ?>'>
		
		
		<B>Refrigerador Compacto CRC08 Consul - Frigobar</B>
		<BR>Disponibilidade de Estoque:  	 IMEDIATA<BR>
		Marca:  BRITANIA<BR>
		Modelo: 9822##487<BR>
		Tempo de Garantia: 03 MESES<BR><B>Valor: R$ 99,99</B><BR>
		Qtde: <input type='text' size='3' maxlength='3' name='txtprod[$cod_produto][QTDE]' value=''>&nbsp;&nbsp;&nbsp;<input type='submit' name='btn_comprar' value='Comprar' class='botao'>";
	echo "</td>";
	echo "</tr>";
	echo "</form>";
	echo "<tr>";
	echo "<td colspan='2' align='baseline'><IMG SRC='../forma.gif' width='530'>";
	echo "</td>";
	echo "</tr>";
 	echo "<tr>";
		echo "<td colspan='2' align='top' style='font-family: verdana; font-size: 12px'>
		&nbsp;&nbsp;À vista: R$ 99,99<BR> 
		&nbsp;&nbsp;30 dias: R$ 99,99<BR> 
		&nbsp;&nbsp;1 + 2 de R$ 99,99<BR><BR>";
		echo "</td>";
	echo "</tr>";
	echo "<tr>";
		echo "<td  colspan='2'  align='baseline'><IMG SRC='../descrica.gif' width='530'>";
		echo "</td>";
	echo "</tr>";
	echo "<tr>";
		echo "<td colspan='2' align='top' style='font-family: verdana; font-size: 12px'>Desfrute de todas as possibilidades de entretenimento com seus filmes e músicas preferidos. Em qualquer lugar e a qualquer momento você tem imagem e som de alta qualidade, é o melhor da diversão em sua casa.<BR><BR>
		Tecnologia Progressive Scan
		<BR><BR>
		A tecnologia Progressive Scan duplica o número de linhas de varredura, criando uma nitidez e uma definição incomparáveis. Para desfrutar deste recurso, a TV deve também ter integrado o Progressive Scan.<BR><BR>";
		echo "</td>";
	echo "</tr>";
	echo "</table>";
 //corpo do produto
echo "</td>";
echo "</tr>";
echo "<tr>";
	echo "<td colspan='2' height='60' bgcolor='#f3f2f1' align='center'>&nbsp;Home&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;Quem Somos&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;Cadastro&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;Destaque&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;Fale Conosco<BR>
	Akácia 2006 -  Todos os direitos Reservados<BR>
	Sistema Telecontrol";
	echo "</td>";
echo "</tr>";
echo "</table>";

?>