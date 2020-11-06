<?php
include_once "dbconfig.php";
include_once "includes/dbconnect-inc.php";
include_once 'autentica_admin.php';
include_once 'funcoes.php';

$admin_privilegios="call_center";

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

$btn_acao          = $_POST['btn_acao'];
$btn_acao_pesquisa = trim ($_POST['btn_acao_pesquisa']);
$codigo_posto      = trim ($_POST['codigo_posto']);
$sua_os            = trim ($_POST['sua_os']);
$mes               = trim ($_POST['mes']);
$ano               = trim ($_POST['ano']);



if ($btn_acao == "Pesquisar" OR ($btn_acao_pesquisa == "Pesquisar")) {
	if (strlen($mes) > 0) {
		$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
		$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
	}
	
	if (strlen ($sua_os) == 0)  {
		if (strlen ($mes) == 0 OR strlen ($ano) == 0)  {
		$msg_erro = "Digite o mês e o ano para fazer a pesquisa";
		}
	}

	if(strlen($codigo_posto) > 0){
		$sql = "SELECT posto
				FROM tbl_posto_fabrica
				WHERE codigo_posto = '$codigo_posto'
				AND fabrica = $login_fabrica";
		$res = pg_query($con, $sql);
		if(pg_num_rows($res)>0) $posto = pg_fetch_result($res,0,0);
	}
}

//--------------------------------------------------------------

if($btn_acao == "Gravar"){
	$qtde_os          = $_POST['qtde_os'];
	$select_acao      = $_POST['select_acao'];

	for ($x = 0 ; $x < $qtde_os ; $x++) {
		$aprovar                   = trim($_POST['aprova_'. $x]);
		$os                        = trim($_POST['os_'. $x]);
		$justificativa_autorizacao = trim($_POST['justificativa_autorizacao_'. $x]);

		if($aprovar=='t' AND strlen($os)>0 AND $select_acao==99 AND strlen($justificativa_autorizacao)==0){
			$justificativa_autorizacao = "Aprovada pelo fabricante";
		}

		if($aprovar=='t' AND strlen($os)>0 AND $select_acao==101 AND strlen($justificativa_autorizacao)==0){
			$justificativa_autorizacao = "Recusada pelo fabricante";
		}

		if($aprovar=='t' AND strlen($msg_erro)==0){
		$sql = "INSERT INTO tbl_os_status(
				os,
				status_os,
				data,
				observacao,
				admin
				)VALUES(
				$os, 
				$select_acao,
				current_timestamp,
				'$justificativa_autorizacao',
				$login_admin);";
			$res = pg_query($con, $sql);
		}
	}
	if(strlen($qtde_os)>0){
		header("Location: $PHP_SELF");
	}
}

$title       = "Aprovação de Kilometragem";
$cabecalho   = "Aprovação de Kilometragem";
$layout_menu = "cadastro";
include_once 'cabecalho.php';
#HD 3809394
echo "<h1 class='erro'>Desativado Temporariamente</h1>";
include_once "rodape.php";exit;
 include_once "javascript_pesquisas.php"; 

?>
<SCRIPT LANGUAGE="JavaScript">
<!--
var ok = false;
var cont=0;
function checkaTodos() {
	f = document.frm_aprova2;
	if (!ok) {
		for (i=0; i<f.length; i++){
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = true;
				ok=true;
				if (document.getElementById('aprova_'+cont)) {
					document.getElementById('aprova_'+cont).style.backgroundColor = "#F0F0FF";
				}
				cont++;
			}
		}
	}else{
		for (i=0; i<f.length; i++) {
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = false;
				ok=false;
				if (document.getElementById('aprova_'+cont)) {
					document.getElementById('aprova_'+cont).style.backgroundColor = "#FFFFFF";
				}
				cont++;
			}
		}
	}
}//-->
</SCRIPT>
<style>
.Conteudo{
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
}
.Erro{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#CC3300;
	font-weight: bold;
	background-color:#FFFFFF;
}
</style>

<BR>
<?
if(strlen($msg_erro)>0){
	echo "<table class='Tabela' width='700' cellspacing='0'  cellpadding='0' align='center'>";
	echo "<tr >";
	echo "<td  class='Erro' bgcolor='FFFFFF' align='center'><img src='imagens/proibido2.jpg' align='middle'>&nbsp; $msg_erro</td>";
	echo "</tr>";
	echo "</table><br>";
}
?>
<form method="post"  name="frm_aprova" action="<? echo $PHP_SELF; ?>">
<table width="450" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Conteudo" bgcolor="#596D9B" align='left'>
		<td colspan='3' height='27' align='center' style='color:#FFFFFF'>Pesquisa Atendimento Domicilio</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td class='Conteudo'>Número OS</td>
		<td class='Conteudo'>Mês</td>
		<td class='Conteudo'>Ano</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>
			<input type="text" name="sua_os" size="12" value="<? echo $sua_os ?>" class="frm">
		</td>
		<td>
			<select name="mes" size="1" class="frm">
			<option value=''></option>
			<?
			for ($i = 1 ; $i <= count($meses) ; $i++) {
				echo "<option value='$i'";
				if ($mes == $i) echo " selected";
				echo ">" . $meses[$i] . "</option>";
			}
			?>
			</select>
		</td>
		<td>
			<select name="ano" size="1" class="frm">
			<option value=''></option>
			<?
			for ($i = 2003 ; $i <= date("Y") ; $i++) {
				echo "<option value='$i'";
				if ($ano == $i) echo " selected";
				echo ">$i</option>";
			}
			?>
			</select>
		</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td class='Conteudo'>Posto</td>
		<td class='Conteudo' colspan='2'>Nome do Posto</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>
			<input type="text" name="codigo_posto" id="codigo_posto" size="8" value="<? echo $codigo_posto ?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_aprova.codigo_posto, document.frm_aprova.posto_nome, 'codigo')">
		</td>
		<td colspan='2'>
			<input type="text" name="posto_nome" id="posto_nome" size="30" value="<?echo $posto_nome?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_aprova.codigo_posto, document.frm_aprova.posto_nome, 'nome')">
		</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan='3' align='center'><br><input type="submit" name="btn_acao" value="Pesquisar"></td>
	</tr>
	</table>
</form>
<BR>
<?
if (($btn_acao == "Pesquisar" OR $btn_acao_pesquisa == "Pesquisar") AND strlen($msg_erro)==0) {

	$sql =  "SELECT interv.os
		INTO TEMP tmp_interv_dom_$login_admin
		FROM (
		SELECT 
		ultima.os, 
		(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND status_os IN (98,99,101) AND tbl_os_status.os = ultima.os ORDER BY data DESC LIMIT 1) AS ultimo_status
		FROM (SELECT DISTINCT os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND status_os IN (98,99,101) ) ultima
		) interv
		WHERE interv.ultimo_status IN (98);
		
		CREATE INDEX tmp_interv_dom_OS_$login_admin ON tmp_interv_dom_$login_admin(os);";
	$res_status = pg_query($con,$sql);

	$sql = "SELECT tbl_posto.nome                        ,
				tbl_os.os                                ,
				tbl_os.sua_os                            ,
				tbl_os.qtde_km                           ,
				(SELECT observacao FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND tbl_os.os = tbl_os_status.os AND status_os IN (98,99,101) ORDER BY data DESC LIMIT 1) as observacao
			FROM tmp_interv_dom_$login_admin X
			JOIN  tbl_os ON tbl_os.os = X.os
			JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
			WHERE tbl_os.fabrica = $login_fabrica
			AND   tbl_os.tipo_atendimento = 37
			AND (SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (98,99,101) ORDER BY data DESC LIMIT 1) IN (98)";
	if (strlen($mes) > 0) {
		$sql .= " AND tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final'";
	}
	if (strlen($posto) > 0) {
		$sql .= " AND tbl_os.posto = $posto";
	}

	if (strlen($sua_os) > 0) {
		$sua_os = strtoupper ($sua_os);
		$pos = strpos($sua_os, "-");
		if ($pos === false) {
			if(!ctype_digit($sua_os)){
				$sql .= " AND tbl_os.sua_os = '$sua_os' ";
			}else{
				$sql .= " AND tbl_os.os_numero = '$sua_os' ";
			}
		}else{
			$conteudo = explode("-", $sua_os);
			$os_numero    = $conteudo[0];
			$os_sequencia = $conteudo[1];
			if(!ctype_digit($os_sequencia)){
				$sql .= " AND tbl_os.sua_os = '$sua_os' ";
			}else{
				$sql .= " AND tbl_os.os_numero = '$os_numero' AND tbl_os.os_sequencia = '$os_sequencia' ";
			}
		}
	}

	#if($login_admin==852)echo nl2br($sql);
	$res = pg_query($con, $sql);

	if(pg_num_rows($res)>0){
		echo "<form method='post' name=\"frm_aprova2\" action=\"$PHP_SELF\">";
		echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1'>";
		echo "<tr class='Conteudo' bgcolor='#596D9B' align='left'>";
			echo "<td  align='center' alt='Selecionar todos'><FONT COLOR='#FFFFFF' onclick='javascript: checkaTodos()'>TODAS<BR><INPUT TYPE='checkbox' NAME='todas'></FONT> </td>";
			echo "<td  align='center'><FONT COLOR='#FFFFFF'>POSTO</FONT> </td>";
			echo "<td  align='center'><FONT COLOR='#FFFFFF'>OS</FONT> </td>";
			echo "<td  align='center'><FONT COLOR='#FFFFFF'>JUSTIIFICATIVA</FONT> </td>";
			echo "<td  align='center'><FONT COLOR='#FFFFFF'>DISTANCIA/KM</FONT> </td>";
			echo "</tr>";?>

		<input type='hidden' name='qtde_os' value='<? echo pg_num_rows($res); ?>'>
		<?
			for($i=0; $i<pg_num_rows($res); $i++){
			$posto_nome    = pg_fetch_result($res, $i, nome);
			$os            = pg_fetch_result($res, $i, os);
			$sua_os        = pg_fetch_result($res, $i, sua_os);
			$qtde_km       = pg_fetch_result($res, $i, qtde_km);
			$observacao    = pg_fetch_result($res, $i, observacao);
		
			if($i%2==0) $cor = "#D9E2EF";
			else        $cor = "#EBEBEB";

			//hidden OS
			echo "<INPUT TYPE=\"hidden\" NAME=\"os_$i\" VALUE=\"$os\">";

			echo "<tr class='Conteudo' bgcolor='$cor' align='left'>";
				echo "<td class='Conteudo' align='center'><INPUT TYPE=\"checkbox\" NAME=\"aprova_$i\" value='t'></td>";
				echo "<td class='Conteudo' align='center'>$posto_nome</td>";
				echo "<td class='Conteudo' align='center'><A HREF='os_press.php?os=$os' target='_blank'>$sua_os</A></td>";
				echo "<td class='Conteudo' align='center'>$observacao</td>";
				echo "<td class='Conteudo' align='center'>$qtde_km</td>";
			echo "</tr>";
			echo "<tr class='Conteudo' bgcolor='$cor' align='left'>";
			echo "<td align='center' colspan='7'>";
				echo "<FONT SIZE='1'>Motivo Recusa:</FONT>";
				echo "<INPUT TYPE='text'  NAME='justificativa_autorizacao_$i' size='70' maxlength='255'>";
			echo "</td>";
			echo "</tr>";
		}
		echo "<TR>";
			echo "<TD colspan=\"7\" bgcolor='#596D9B' align=\"left\">";
				echo "&nbsp;&nbsp;&nbsp;&nbsp;<img border='0' src='imagens/seta_checkbox.gif' align='absmiddle'> &nbsp; <font color='#FFFFFF'><B>COM MARCADOS:</B></font> &nbsp;";
				echo "<select name='select_acao' size='1' class='frm' >";
				echo "<option value=''></option>";
				echo "<option value='99'";  if ($_POST["select_acao"] == "99")  echo " selected"; echo ">APROVADO</option>";
				echo "<option value='101'";  if ($_POST["select_acao"] == "101")  echo " selected"; echo ">RECUSADO</option>";
				echo "</select>";
				echo "&nbsp;&nbsp;<img src='imagens/btn_gravar.gif' style='cursor:pointer'  onclick=\"javascript: if (document.frm_aprova2.btn_acao.value == '' ) { document.frm_aprova2.btn_acao.value='Gravar' ;  document.frm_aprova2.submit() } else { alert ('Aguarde submiss?o') }\" style='cursor: hand;' border='0'></td>";
				echo "<input type='hidden' name='btn_acao' value=''>";
				echo "<input type=\"hidden\" name=\"ano\" value=\"$ano\">";
				echo "<input type=\"hidden\" name=\"mes\" value=\"$mes\">";
				echo "<input type=\"hidden\" name=\"codigo_posto\" value=\"$codigo_posto\">";
			echo "</TD>";
		echo "</tr>";
		echo "</table>";
		echo "</form>";
	}else{
		echo "<P>Nenhum resultado encontrado.</P>";
	}

}
?>
<? include_once "rodape.php"; ?>
