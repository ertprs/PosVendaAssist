<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

if (strlen($_POST['btn_acao']) > 0) $btn_acao = $_POST['btn_acao'];

if ($btn_acao == 'gravar'){

}

$admin_privilegios="cadastros";
include 'autentica_admin.php';

$layout_menu = "cadastro";
$title = "Cadastramento de Preços de Mercadorias";
include 'cabecalho.php';
?>

<style type='text/css'>
.texto {
	font-family: arial;
	font-size: 12px;
	text-align: left;
}

a {
	font-family: arial;
	font-size: 12px;
	text-align: left;
}
</style>

<table width='700' border='2' bordercolor='#d9e2ef'>
<FORM name='frm_precopecas' METHOD=POST ACTION="<? echo $PHP_SELF; ?>">
<INPUT TYPE="hidden" name='btn_acao' value=''>
<tr>
	<td>
		<table width='300' border='0' cellpadding='0' cellspacing='0'>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td class='texto' bgcolor='#D9E8F9'>Descrição:&nbsp;<input type='text' size='25'></td>
		</tr>
		<tr>
			<td align='left' class='texto'><INPUT TYPE="checkbox" NAME="peca_sem_valor">Listar Peças sem atribuição valor</td>
		</tr>
		<tr>
			<td align='left' class='texto'><INPUT TYPE="checkbox" NAME="peca_sem_tabela">Listar Peças que não constam em tabelas</td>
		</tr>
		<tr>
			<td align='left' class='texto'><INPUT TYPE="checkbox" NAME="peca_todas">Listar Todas as Peças</td>
		</tr>
		<tr>
			<td><INPUT TYPE="button" value='Pesquisar' onclick="javascript:document.frm_precopecas.btn_acao.value='pesquisar';submit();"></td>
		</tr>
		</table>
	</td>
</tr>
</FORM>
</table>

<?
if ($btn_acao == 'pesquisar'){
	$sql = "SELECT	tbl_tabela_item.preco
			FROM	tbl_tabela_item
			JOIN	tbl_peca USING(peca)
			WHERE	tbl_peca.fabrica = $login_fabrica";

	$peca_sem_valor  = "";
	$peca_sem_tabela = "";
	$peca_todas      = "";

	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0){
?>
<table width='700' border='2' cellpadding='3' cellspacing='0' bordercolor='#d9e2ef'>
<FORM name='frm_pecas' METHOD=POST ACTION="<? echo $PHP_SELF; ?>">
<INPUT TYPE="hidden" name='btn_acao' value=''>
<tr bgcolor='#d9e2ef' class='texto' style='font=weight: bold;'>
	<td>CÓDIGO</td>
	<td width='100%'>DESCRIÇÃO</td>
	<td>TABELA 01</td>
	<td>TABELA 02</td>
	<td>TABELA 03</td>
	<td>TABELA 04</td>
</tr>
<?
# seleciona as peças
		for($i=0; $i<pg_numrows($res); $i++){
			echo "<tr class='texto'>\n";
			echo "	<td>$referencia</td>\n";
			echo "	<td>$descricao</td>\n";
			echo "	<td><input type='text' size='10' name='tabela_$i'></td>\n";
			echo "	<td><input type='text' size='10' name='tabela_$i'></td>\n";
			echo "	<td><input type='text' size='10' name='tabela_$i'></td>\n";
			echo "	<td><input type='text' size='10' name='tabela_$i'></td>\n";
			echo "</tr>\n";
		}
?>
</table>

<br>
<table width='700' border='0' cellpadding='0' cellspacing='0'>
<tr>
	<td>&nbsp;</td>
	<td><input type='submit' value='Gravar Alterações' onclick="javascript:document.frm_pecas.btn_acao.value='gravar';submit();"></td>
	<td>&nbsp;</td>
<!-- 
	<td><a href='#'>Anterior</a></td>
	<td><a href='#'>01</a> | <a href='#'>02</a> | <a href='#'>03</a></td>
	<td><a href='#'>Próximo</a></td>
 -->
</tr>
</table>
<?
	}else{
		echo "<br><br>Não foram encontradas peças.<br><br>";
	}
?>
</FORM>
<?
}
?>
<br>

<? include "rodape.php"; ?>