<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$layout_menu = "tecnica";
$title = "Relação de Peças";

include 'cabecalho.php';

?>
<style type="text/css">

.menu_top {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 0px solid;
	color:#596d9b;
	background-color: #d9e2ef
}

.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

</style>

<table width='700' align='center' border='0' class='conteudo' cellpadding='0' cellspacing='1'>
<form name='frm_consulta' method='post' action='<? echo $PHP_SELF; ?>'>
<tr class='menu_top' height='20'>
	<td align='center'><b>Origem</b></td>
	<td align='center'><b>Tipo</b></td>
	<td align='center'>&nbsp;</td>
</tr>
<tr>
	<td align='center'>
		<select name='origem'>
			<option selected></option>
			<option value='NAC' <? if ($origem == 'NAC') echo "selected";?>> Fabricação </option>
			<option value='IMP' <? if ($origem == 'IMP') echo "selected";?>> Importado </option>
			<option value='TER' <? if ($origem == 'TER') echo "selected";?>> Terceiros </option>
		</select>
	</td>
	<td align='center'>
		<select name='tipo'>
			<option selected></option>
			<option value='1' <? if ($tipo == 1) echo "selected";?>> Devolução obrigatória </option>
			<option value='2' <? if ($tipo == 2) echo "selected";?>> Item de aparência </option>
			<option value='3' <? if ($tipo == 3) echo "selected";?>> Peça acumulada para kit </option>
			<option value='4' <? if ($tipo == 4) echo "selected";?>> Peça retorno para concerto </option>
			<option value='5' <? if ($tipo == 5) echo "selected";?>> Bloqueada para garantia </option>
		</select>
	</td>
	<td align='center'><img src='imagens/btn_continuar.gif' border=0 onclick='javascript: submit();' style='cursor:pointer'></td>
</tr>
</form>
</table>
<br>
<?

$mens = '';

if (strlen($tipo) > 0 OR strlen($origem) > 0){
	$sql = "SELECT  peca                  ,
					referencia            ,
					descricao             ,
					origem                ,
					unidade               ,
					peso                  ,
					garantia_diferenciada ,
					item_aparencia        
			FROM	tbl_peca
			WHERE	fabrica               = $login_fabrica ";

	switch ($origem){
		case 'NAC':		$sql .= "AND (origem = 'NAC' OR origem = '1' OR origem = 'FAB') ";	$mens .= " FABRICAÇÃO - ";	break;
		case 'IMP':		$sql .= "AND (origem = 'IMP' OR origem = '2') ";						$mens .= " IMPORTADA - ";	break;
		case 'TER':		$sql .= "AND origem = 'TER' ";										$mens .= " TERCEIRO - ";	break;
		default:	break;
	}

	switch ($tipo){
		case 1:		$sql .= "AND devolucao_obrigatoria IS true ";	$mens .= " DEVOLUÇÃO OBRIGATÓRIA ";		break;
		case 2:		$sql .= "AND item_aparencia IS true ";			$mens .= " ITEM DE APARÊNCIA ";			break;
		case 3:		$sql .= "AND acumular_kit IS true ";			$mens .= " PEÇA ACUMULADA PARA KIT ";	break;
		case 4:		$sql .= "AND retorna_conserto IS true ";		$mens .= " PEÇA RETORNO PARA CONCERTO ";	break;
		case 5:		$sql .= "AND bloqueada_garantia IS true ";		$mens .= " BLOQUEADA PARA GARANTIA ";	break;
		default:	break;
	}

	$sql .= "ORDER BY descricao ASC, referencia ASC";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) == 0){
		echo "<table width='700' align='center' border='0' class='conteudo' cellpadding='0' cellspacing='1'>";
		echo "<tr>";
		echo "<td align='center' colspan='5'><H3><b>Nenhum resultado encontrado</b></H3></td>";
		echo "</tr>";
	}elseif (pg_numrows($res) > 0){
		echo "<table width='700' align='center' border='0' class='conteudo' cellpadding='0' cellspacing='1'>";
		echo "<tr class='menu_top' height='20'>";
		echo "<td align='center' colspan='5'><b>$mens</b></td>";
		echo "</tr>";

		echo "<tr class='menu_top' height='20'>";
		echo "<td align='center'><b>Referência</b></td>";
		echo "<td align='center'><b>Descrição</b></td>";
		echo "<td align='center'><b>Origem</b></td>";
		echo "<td align='center'><b>Unid</b></td>";
		echo "<td align='center'><b>Peso</b></td>";
		echo "</tr>";

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$bg = ($i%2 == 0) ? '#fbfbfb' : '#FFFFFF';
			echo "<tr class='table_line' height='18'>";
			echo "<td align='left' bgcolor='$bg'>".pg_result ($res,$i,referencia)."</td>";
			echo "<td align='left' bgcolor='$bg'>".pg_result ($res,$i,descricao)."</td>";
			echo "<td align='center' bgcolor='$bg'>".pg_result ($res,$i,origem)."</td>";
			echo "<td align='center' bgcolor='$bg'>".pg_result ($res,$i,unidade)."</td>";
			echo "<td align='right' bgcolor='$bg' style='padding-right:5px'>".pg_result ($res,$i,peso)."</td>";
			echo "</tr>";
		}

		echo "</table>";
	}
}
?>

<p>

<? include "rodape.php"; ?>
