<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$title = "Telecontrol-Assistência Técnica - Financeiro";
include "cabecalho.php";
#include "sub-menu.php";
?>


<table width="100%" border="0" cellpadding="0" cellspacing="0" align="center">
<tr>
	<td bgcolor="#ffffff"><img height="10" width='20' src="assets/spacer.gif"></td>
	<td bgcolor="#eeeeee" width='100%'><img height="10" src="assets/spacer.gif"></td>
	<td bgcolor="#ffffff"><img height="10" width='20' src="assets/spacer.gif"></td>
</tr>

<tr>
	<td></td>
	<td><img height="10" src="assets/spacer.gif"></td>
	<td></td>
</tr>

<tr>
	<td></td>
	<td>
		<table width='400' align='center' border='0'>
		<tr>
			<td>
				<a href='devolucao_cadastro.php'>Notas de Devolução</a>
			</td>
			<td>
				<a href='acerto_contas.php'>Acerto de Contas</a>
			</td>
		</tr>
		</table>
	</td>
	<td></td>
</tr>

</table>

<? #include "rodape.php" ?>