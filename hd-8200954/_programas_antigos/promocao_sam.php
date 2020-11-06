<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$title = "P R O M O Ç Õ E S";

$layout_menu = 'preco';

include 'cabecalho.php';

?>

<BR>
<style>
.Mensagem{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	color:#7192C4;
	font-weight: bold;
}
.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
	}
</style>
<table width="700" align="center" border="1" cellpadding="3" cellspacing="0">
<tr BGCOLOR="#D9E2EF">
	<td align="left" valign='top' rowspan="2">
		<p align="justify"><b>Produto</b></p>
	</td>
	<td align="left" valign='top' rowspan="2">
		<p align="justify"><b>Descrição</b></p>
	</td>
	<td align="center" valign='top' colspan="5">
			<p><b>Preço ICMS</b></p>
	</td>
</tr>
<tr>
		<td align="center" valign='top' ><p><b>18%</b></p></td>
		<td align="center" valign='top'><p><b>12%</b></p></td>
		<td align="center" valign='top'><p><b>7%</b></p></td>
		<td align="center" valign='top'><p><b>8,8%</b></p></td>
		<td align="center" valign='top'><p><b>7,14%</b></p></td>
</tr>
<tr>
	<td align="left" valign='top' nowrap>
		<p align="justify">BT3600</p>
	</td>
	<td align="left" valign='top' nowrap>
		<p align="justify">BANCADA DE ESMERIL</p>
	</td>
	<td align="center" valign='top'><p></p></td>
	<td align="center" valign='top'><p></p></td>
	<td align="center" valign='top'><p></p></td>
	<td align="center" valign='top'><p>97,13</p></td>
	<td align="center" valign='top'><p>93,39</p></td>
</tr>
<tr>
	<td align="left" valign='top' nowrap>
		<p align="justify">9078-BR</p>
	</td>
	<td align="left" valign='top' nowrap>
		<p align="justify">PARAFUSADEIRA ANGULAR COM<br>CONTROLE DE TORQUE DE 3,6V  BIVOLT</p>
	</td>
	<td align="center" valign='top'><p>88,00</p></td>
	<td align="center" valign='top'><p>82,00</p></td>
	<td align="center" valign='top'><p>77,59</p></td>
	<td align="center" valign='top'><p></p></td>
	<td align="center" valign='top'><p></p></td>
</tr>
<tr>
	<td align="left" valign='top' nowrap>
		<p align="justify">H11957F</p>
	</td>
	<td align="left" valign='top' nowrap>
		<p align="justify">MOTO COMPRESSOR 25 LITROS</p>
	</td>
	<td align="center" valign='top'><p>513,30</p></td>
	<td align="center" valign='top'><p>487,20</p></td>
	<td align="center" valign='top'><p>465,45</p></td>
	<td align="center" valign='top'><p></p></td>
	<td align="center" valign='top'><p></p></td>
</tr>
</table>
<br><table class='Tabela' width='700' cellspacing='0'  cellpadding='0' align='center'>
<tr >
<td bgcolor='FFFFFF'  width='60' align='center'><img src='imagens/info.jpg' align='middle'></td><td  class='Mensagem' bgcolor='FFFFFF'>
<p align="justify">Para solicitar a compra dos produtos em promoção, por gentileza 
encaminhe um e-mail para rfernandes@blackedecker.com.br aos cuidados de Rúbia, informando o código do posto autorizado, a condição de pagamento e a quantidade.<br>

Ressaltamos que são vendidos apenas 02 unidades por produto, uma vez que estes são de uso na própria assistência e não devem ser comercializados.<br>
<br>
Atenciosamente.<br></p>
<br>
Deptº Assistência Técnica<br>
Black & Decker do Brasil<br>
</td>
</tr>
</table><br>



<p>

<?include 'rodape.php';?>