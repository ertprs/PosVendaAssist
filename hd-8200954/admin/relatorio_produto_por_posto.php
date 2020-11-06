<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$layout_menu = "gerancia";

$title = "RELATÓRIO PRODUTOS POR POSTO";

include 'cabecalho.php';

$data_inicial = trim($_POST['data_inicial_01']);
$data_final   = trim($_POST['data_final_01']);
$codigo_posto = trim($_POST['codigo_posto']);

?>

<style type="text/css">
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.titPreto14 {
	color: #000000;
	text-align: center;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.titPreto12 {
	color: #000000;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.titDatas12 {
	color: #000000;
	text-align: center;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.titChamada10{
	background-color: #596D9B;
	color: #ffffff;
	text-align: center;
	font:11px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.conteudo10 {
	color: #000000;

	font:10px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.bgTRConteudo1{
	background-color: #FEFEFF;
}

.bgTRConteudo2{
	background-color: #F9FCFF;
}

</style>

<!--=============== <FUNÇÕES> ================================!-->
<!--  XIN´S POP UP CALENDAR -->

<script language="javascript" src="js/cal2.js">
/*
Xin's Popup calendar script-  Xin Yang (http://www.yxscripts.com/)
Script featured on/available at http://www.dynamicdrive.com/
This notice must stay intact for use
*/
</script>

<script language="javascript" src="js/cal_conf2.js"></script>

<? include "javascript_calendario.php"; ?>

<script type="text/javascript" charset="utf-8">
	$(function()
	{
		$('#data_inicial_01').datePicker({startDate:'01/01/2000'});
		$('#data_final_01').datePicker({startDate:'01/01/2000'});
		$("#data_inicial_01").maskedinput("99/99/9999");
		$("#data_final_01").maskedinput("99/99/9999");
	});
</script>

<br>

<form name="frm_pesquisa" method="post" action="<?echo $PHP_SELF?>">

<input type="hidden" name="btn_acao">

<table width="450" align="center" border="0" cellspacing="0" cellpadding="2">
	<TR>
		<TD colspan="4" class="menu_top"><div align="center"><b>Pesquisa</b></div></TD>
	</TR>
	<TR>
		<TD class="table_line" style="width: 10px">&nbsp;</TD>
		<TD class="table_line"><center>Data Inicial</center></TD>
		<TD class="table_line"><center>Data Final</center></TD>
		<TD class="table_line" style="width: 10px">&nbsp;</TD>
	</TR>
	<TR>
		<TD class="table_line" style="width: 10px">&nbsp;</TD>
		<TD class="table_line" style="width: 185px"><center><INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial_01" id='data_inicial_01' value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>" onClick="this.value=''"></center></TD>
		<TD class="table_line" style="width: 185px"><center><INPUT size="12" maxlength="10" TYPE="text" NAME="data_final_01" id='data_final_01' value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onClick="this.value=''">&nbsp;</center></TD>
		<TD class="table_line" style="width: 10px">&nbsp;</TD>
	</TR>
	<tr>
		<td class='table_line' colspan="4"  style="text-align: center;">Código do Posto</td>
	</tr>
	<tr>
		<td class='table_line' colspan="4" style="text-align: center;"><input type="text" name="codigo_posto" size="13" value="<? echo $codigo_posto; ?>" ></td>
	</tr>
	<tr class="table_line">
		<td colspan="4" style="text-align: center;"><img src="imagens_admin/btn_confirmar.gif" onClick="javascript: if (document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='PESQUISAR'; document.frm_pesquisa.submit(); }else{ alert('Aguarde submissão'); }" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar"></td>
	</tr>
</table>

</form>

<?
$btn_acao = trim($_POST['btn_acao']);

if (strlen($btn_acao) > 0){
	
	$sql = "SELECT  tbl_posto.posto,
					tbl_posto.nome,
					tbl_posto_fabrica.codigo_posto
			FROM tbl_posto 
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto 
								  AND tbl_posto_fabrica.fabrica = $login_fabrica 
			WHERE tbl_posto_fabrica.codigo_posto = '$codigo_posto'";
	$res = pg_exec($con,$sql);
	
	if (pg_numrows($res) > 0){
		$posto = pg_result($res,0,posto);
		$nome_posto  = pg_result($res,0,nome);
	
?>
<TABLE WIDTH = '550' align = 'center'>
	<TR>
		<TD class='titDatas12'><? echo $data_inicial." até ".$data_final ?></TD>
	</TR>
	<TR>
		<TD HEIGHT='25' class='titPreto12' align='center'>POSTO: <b><? echo $codigo_posto." - ".$nome_posto; ?></b></TD>
	</TR>
</TABLE>
<BR>

<TABLE width='600' cellspacing='0' cellpadding='2' border='0' align = 'center'>
<TR>
	<TD class='titChamada10'>REFERÊNCIA</TD>
	<TD class='titChamada10'>DESCRIÇÃO</TD>
	<TD class='titChamada10'>VOLTAGEM</TD>
	<TD class='titChamada10'>OCORRÊNCIAS</TD>
	<TD class='titChamada10'>%</TD>
</TR>
<?
		$data_inicial = fnc_formata_data_pg ($data_inicial);
		$data_final   = fnc_formata_data_pg ($data_final);

		$sql = "select	COUNT(tbl_os.produto) AS ocorrencia, 
						tbl_produto.referencia, 
						tbl_produto.descricao, 
						tbl_produto.voltagem
				FROM	tbl_os
				JOIN	tbl_produto on tbl_produto.produto = tbl_os.produto
				WHERE	tbl_os.posto = $posto
				AND		tbl_os.data_digitacao BETWEEN $data_inicial AND $data_final 
				AND		tbl_os.fabrica = $login_fabrica
				GROUP BY tbl_produto.referencia, tbl_produto.descricao, tbl_produto.voltagem
				ORDER BY ocorrencia, tbl_produto.referencia, tbl_produto.descricao, tbl_produto.voltagem;";
		$res = pg_exec($con, $sql);

		if(pg_numrows($res) > 0){

			for ($x = 0; $x < pg_numrows($res); $x++) {
				$total_ocorrencia = $total_ocorrencia + pg_result($res,$x,ocorrencia);
			}

			for($i=0; $i<pg_numrows($res); $i++){
				$referencia = pg_result($res,$i,referencia);
				$descricao  = pg_result($res,$i,descricao);
				$voltagem   = pg_result($res,$i,voltagem);
				$ocorrencia = pg_result($res,$i,ocorrencia);

				if ($total_ocorrencia > 0) {
					$porcentagem = (($ocorrencia * 100) / $total_ocorrencia);
				}
				
				$cor = '2';
				if ($i % 2 == 0) $cor = '1';

				echo "<TR class='bgTRConteudo$cor'>";
				echo "	<TD class='conteudo10' align='left'>$referencia</TD>";
				echo "	<TD class='conteudo10' align='left'>$descricao</TD>";
				echo "	<TD class='conteudo10' align='left'>$voltagem</TD>";
				echo "	<TD class='conteudo10' align='center'>$ocorrencia</TD>";
				echo "	<TD class='conteudo10' align='right'>". number_format($porcentagem,2,",",".") ."%</TD>";
				echo "</TR>";
			}

			echo "<TR class='bgTRConteudo$cor'>";
			echo "	<TD colspan=3 class='conteudo10' align='right'>Total de ocorrências</TD>";
			echo "	<TD class='conteudo10' align='center'><B>" . $total_ocorrencia ."</B></TD>";
			echo "	<TD class='conteudo10' align='right'><B>100%</B></TD>";
			echo "</TR>";

			$sql = "SELECT COUNT(*) 
					FROM (
							SELECT DISTINCT tbl_os.os 
							FROM tbl_os 
							LEFT JOIN tbl_os_produto USING (os) 
							LEFT JOIN tbl_os_item USING (os_produto) 
							WHERE tbl_os.posto = $posto 
							AND tbl_os.data_digitacao BETWEEN $data_inicial AND $data_final 
							AND tbl_os_item.os_produto IS NULL 
							AND tbl_os.fabrica = $login_fabrica 
							AND tbl_os.excluida IS NOT TRUE
						) os ";
			$res = pg_exec ($con,$sql);
			$qtde = pg_result ($res,0,0);
			echo "<TR class='bgTRConteudo$cor'>";
			echo "	<TD colspan=5 class='conteudo10' align='left'><br>OS sem peças = " . $qtde ."</TD>";
			echo "</TR>";

			$sql = "SELECT COUNT(*) 
					FROM (
							SELECT DISTINCT tbl_os.os 
							FROM tbl_os 
							LEFT JOIN tbl_os_produto USING (os) 
							LEFT JOIN tbl_os_item USING (os_produto) 
							WHERE tbl_os.posto = $posto 
							AND tbl_os.data_digitacao BETWEEN $data_inicial AND $data_final 
							AND tbl_os_item.os_produto IS NOT NULL 
							AND tbl_os.fabrica = $login_fabrica 
							AND tbl_os.excluida IS NOT TRUE
						) os ";
			$res = pg_exec ($con,$sql);
			$qtde = pg_result ($res,0,0);
			echo "<TR class='bgTRConteudo$cor'>";
			echo "	<TD colspan=5 class='conteudo10' align='left'><br>OS com peças = " . $qtde ."</TD>";
			echo "</TR>";

		}
	} // if
} // if do btn_acao

?>

</TABLE>

<br><br>

<?
include 'rodape.php';
?>