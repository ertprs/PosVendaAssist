<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="gerencia";
include 'autentica_admin.php';

include 'funcoes.php';

$title = "Consulta de pesquisas realizadas";

$layout_menu = "gerencia";

include 'cabecalho.php';
?>
<script type="text/javascript">
	function Formatadata(Campo, teclapres)
	{
		var tecla = teclapres.keyCode;
		var vr = new String(Campo.value);
		vr = vr.replace("/", "");
		vr = vr.replace("/", "");
		vr = vr.replace("/", "");
		tam = vr.length + 1;
		if (tecla != 8 && tecla != 8)
		{
			if (tam > 0 && tam < 2)
				Campo.value = vr.substr(0, 2) ;
			if (tam > 2 && tam < 4)
				Campo.value = vr.substr(0, 2) + '/' + vr.substr(2, 2);
			if (tam > 4 && tam < 7)
				Campo.value = vr.substr(0, 2) + '/' + vr.substr(2, 2) + '/' + vr.substr(4, 7);
		}
	}
	function PermiteNumeros()
	{
	  var tecla = window.event.keyCode;
	  tecla     = String.fromCharCode(tecla);
	  if(!((tecla >= "0") && (tecla <= "9")))
	  {
		window.event.keyCode = 0;
	  }
	}
</script>
<style type="text/css">
table.simpleborder {
	border-collapse: collapse; /* CSS2 */
	background: #FFFFFF;
}

table.simpleborder td {
	border: 1px solid black;
}

table.simpleborder th {
	border: 1px solid black;
	border-bottom: 2px solid black;
	background: #B9D3EE;
}
</style>
<br>

<table align='center' border=1 class='simpleborder'>
<form name="relatorio_lenoxx" method="POST"  action='relatorio_lenoxx_nf_gera.php'>
<tr>
<th> Gerar Relatório </th>
</tr>
<tr>
<td colspan='2'> <input type='submit' name='enviar' value='Gerar Relatório'> </td>
</tr>
</form>
</table>