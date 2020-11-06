<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';


$btn_acao = $_POST["btn_acao"];
if(strlen($btn_acao)>0){
	$qtde_os = $_POST["qtde_os"];
	$select_acao = $_POST["select_acao"];
	$observacao = trim($_POST["observacao"]);
	//APROVADA - 19
	//RECUSAR - 13
	for ($x=0;$x<$qtde_os;$x++){
		$xxos = $_POST["ckeck_".$x];
		if (strlen($xxos)>0){
			echo "$xxos<br>";

		}
	}
}


include 'cabecalho.php';

?>

<style type="text/css">

.menu_top2 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	color:#ffffff;
	background-color: #596D9B
}

.table_line3 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

</style>

<script language="JavaScript">
var ok = false;
function checkaTodos() {
	f = document.frm_pesquisa;
	if (!ok) {
		for (i=0; i<f.length; i++){
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = true;
				ok=true;
			}
		}
	}else{
		for (i=0; i<f.length; i++) {
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = false;
				ok=false;
			}
		}
	}
}

function fnc_pesquisa_posto2 (campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
}

function AdicionaValor(os){
	janela = window.open("os_press.php?os=" + os,'os');
	janela.focus();
}
</script>
<?

$codigo_posto = $_POST['codigo_posto'];
$ano = $_POST['ano'];
$mes = $_POST['mes'];
$btn_pesquisa = $_POST['pesquisa'];

if($btn_pesquisa == 'Pesquisa'){
	if( strlen($codigo_posto) == 0) $msg_erro = "Escolha o posto. ";
	if( strlen($mes) == 0) $msg_erro .= " Escolha o mês.";
	if( strlen($ano) == 0) $msg_erro .= " Escolha o ano.";
}

//MSG ERRO<-------------
if(strlen($msg_erro) > 0){
	echo "<p align='center' style='font-size: 12px; font-family: verdana;'><FONT COLOR=\"#FF0033\"><b>$msg_erro</b></FONT></p>";
	$msg_erro = '';
}
//include 'include_fer.php';
?>

<br>

<FORM name="frm_comunicado" method="post" action="<? echo $PHP_SELF; ?>">
<INPUT type="hidden" name="btn_acao">
<? $meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"); ?>
<TABLE width="400" align="center" border="0" cellspacing="0" cellpadding="0">
<TR>
	<TD colspan="4" height='25' class="menu_top2" align='center' background='imagens_admin/azul.gif'><b>Pesquisa</b></TD>
</TR>
<TR>
	<TD class="table_line3">
		<TABLE border='0' width='250' cellspacing='0' cellpadding='0'>
		<TR>
			<TD class="table_line3" style="width: 10px">&nbsp;</TD>
			<TD class="table_line3"><center>Mês</center></TD>
			<TD class="table_line3"><center>Ano</center></TD>
			<TD class="table_line3" style="width: 10px">&nbsp;</TD>
		</TR>
		<TR>
			<TD class="table_line3" style="width: 10px">&nbsp;</TD>
			<TD class="table_line3" width="50%">
				<CENTER><BR>
				<select name="mes" size="1">
					<option value=''></option>
					<? 
					for ($i = 1 ; $i <= count($meses) ; $i++) {
						echo "<option value='$i'";
						if ($mes == $i) echo " selected";
						echo ">" . $meses[$i] . "</option>";
					}
					?>
				</CENTER>
				</select>
			</TD>
			<TD class="table_line3" width="50%">
				<CENTER><BR>
				<select name="ano" size="1">
				<option value=''></option>
				<?
				for ($i = 2003 ; $i <= date("Y") ; $i++) {
					echo "<option value='$i'";
					if ($ano == $i) echo " selected";
					echo ">$i</option>";
				}
				?>
				</CENTER>
				</select>
			</TD>
			<TD class="table_line3" style="width: 10px">&nbsp;</TD>
		</TR>
		</TABLE>
	</TD>
</TR>
<TR>
	<TD class="table_line3">
		<TABLE border='0'>
		<TR>
			<TD class="table_line3" colspan='2' style="width: 10px">&nbsp;</TD>
		</TR>
		<TR align='center'>
			<TD class="table_line3">Código<br><input type="text" name="codigo_posto" size="13" value="<? echo $codigo_posto ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm_comunicado.codigo_posto,document.frm_comunicado.posto_nome,'codigo')" <? } ?>>&nbsp;<img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_comunicado.codigo_posto,document.frm_comunicado.posto_nome,'codigo')"></TD>
			<TD class="table_line3" nowrap>Razão Social<br><input type="text" name="posto_nome" size="35" value="<? echo $posto_nome ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm_comunicado.codigo_posto,document.frm_comunicado.posto_nome,'nome')" <? } ?>>&nbsp;<img src='imagens_admin/btn_lupa.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_comunicado.codigo_posto,document.frm_comunicado.posto_nome,'nome')" style="cursor:pointer;"></TD>
		</TR>
		<TR>
			<TD class="table_line3" colspan='2' style="width: 10px">&nbsp;</TD>
		</TR>
		<TR class="table_line3">
			<td colspan="2" style="text-align: center;"></td>
		</TR>
		<TR class="table_line3">
			<td colspan="2" style="text-align: center;"><INPUT TYPE="submit" name='pesquisa' value='Pesquisa'></td>
		</TR>
		</TABLE>
	</TD>
</TR>
</TABLE>
</FORM>

<?


$codigo_posto = $_POST['codigo_posto'];

$ano = $_POST['ano'];
$mes = $_POST['mes'];

if (strlen($mes) > 0) {
	$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
	$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
}

if(strlen($codigo_posto) > 0){
	$sql = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '$codigo_posto' AND tbl_posto_fabrica.fabrica = $login_fabrica limit 1;";
	$res = pg_exec($con,$sql);
}else{
	$msg_erro = "Entre com os dados do posto.";
}

if (strlen($erro) == 0 AND pg_numrows($res) > 0) {
	$posto = pg_result($res,0,posto);
	$sql="SELECT distinct tbl_os.os                     ,
				tbl_os.sua_os                  ,
				TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura   ,
				tbl_posto.nome                 ,
				tbl_produto.descricao
			FROM tbl_os_item 
			JOIN tbl_os_produto using(os_produto)
			JOIN tbl_produto using(produto)
			JOIN tbl_os using(os)
			JOIN tbl_posto_fabrica using(posto)
			JOIN tbl_posto using(posto)
		WHERE servico_realizado = 8
		AND digitacao_item between '$data_inicial' and '$data_final'
		AND tbl_os.posto = '$posto'
		AND tbl_produto.linha = 334
		AND tbl_os_item.pedido IS NOT NULL
		AND tbl_posto_fabrica.fabrica = $login_fabrica
		GROUP BY tbl_os.os                      ,
				 tbl_os.sua_os                  ,
				 tbl_os.data_abertura           ,
				 tbl_posto.nome                 ,
				 tbl_os_produto.os_produto      ,
				 tbl_produto.descricao
		ORDER BY tbl_os.os; ";

	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		
	echo "<BR><BR><FORM name='frm_pesquisa' METHOD='POST' ACTION='$PHP_SELF'>";
	echo "<table width='750' border='0' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 11px' bgcolor='#FFFFFF'>";
	echo "<tr>";
	echo "<td bgcolor='#485989'><img border='0' src='imagens_admin/selecione_todas.gif' onclick='javascript: checkaTodos()' alt='Selecionar todos' style='cursor: hand;' align='center'></td>";
	echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>OS</B></font></td>";
	echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>AB</B></font></td>";
	echo "<td colspan='2' bgcolor='#485989'><font color='#FFFFFF'><B>Posto</B></font></td>";
	echo "<td colspan='2' bgcolor='#485989'><font color='#FFFFFF'><B>Produto</B></font></td>";
	echo "</tr>";
		$cores = '';
		for ($x=0; $x<pg_numrows($res);$x++){

			$os						= pg_result($res, $x, os);
			$sua_os					= pg_result($res, $x, sua_os);
			$data_abertura			= pg_result($res, $x, data_abertura);
			$produto_descricao		= pg_result($res, $x, descricao);
			$posto_nome				= pg_result($res, $x, nome);
			$status_os				= 0;

			$cores++;
			$cor = ($cores % 2 == 0) ? "#FEFEFE": '#E8EBEE';
			echo "<tr>";
			echo "<td bgcolor='$cor' align='center'><input type='checkbox' name='ckeck_$x' value='$os'></td>";
			echo "<td bgcolor='$cor'><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></td>";
			echo "<td bgcolor='$cor' align='left'>$data_abertura</td>";
			echo "<td colspan='2' bgcolor='$cor' align='left'>$posto_nome</td>";
			echo "<td bgcolor='$cor' align='left'>$produto_descricao</td>";
			if($tipo_atendimento==18)
				echo "<td bgcolor='$cor' align='left' nowrap><a href='javascript:AdicionaValor(\"$os\");'><img border='0' src='imagens/btn_adicionar_azul.gif' align='absmiddle'></a></td>";
			else 
				echo "<td bgcolor='$cor' align='left' nowrap><a href='javascript:AdicionaValor(\"$os\");'><img border='0' src='imagens/btn_consultar_azul.gif' align='absmiddle'></a></td>";
			echo "</tr>";
		}
		echo "<input type='hidden' name='qtde_os' value='$x'>";
		echo "<tr>";
		echo "<td height='20' bgcolor='#485989' colspan='8' align='left'> &nbsp;&nbsp;&nbsp;&nbsp;<img border='0' src='imagens/seta_checkbox.gif' align='absmiddle'> &nbsp; <font color='#FFFFFF'><B>COM MARCADOS:</B></font> &nbsp;";
		echo "<select name='select_acao' size='1' class='frm'>";
		echo "<option value=''></option>";
		echo "<option value='13'";  if ($_POST["select_acao"] == "13")  echo " selected"; echo ">RECUSADO PELO FABRICANTE</option>";
		echo "<option value='19'";  if ($_POST["select_acao"] == "19")  echo " selected"; echo ">APROVADA PELO FABRICANTE</option>";
		echo "</select>";
		echo "&nbsp;&nbsp;<input class='frm' type='text' name='observacao' size='30' maxlength='50' value=''>";
		echo "<input type='hidden' name='btn_acao' value=''>";
		echo "&nbsp;&nbsp;<img src='imagens/btn_continuar.gif' style='cursor:pointer' onclick=\"javascript: if (document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='continuar' ; document.frm_pesquisa.submit() } else { alert ('Aguarde submiss?') }\" ALT=\"Continuar a aprovação\" border='0'></td>";
		echo "</tr>";
		echo "</table>";
		echo "</form>";
	}else{ 
		echo "<center>Não foi encontrada OS de Troca.</center>";
	}
}

include "rodape.php" ?>