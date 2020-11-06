<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="auditoria";
include 'funcoes.php';

$gera_automatico = trim($_GET["gera_automatico"]);

if ($gera_automatico != 'automatico'){
	include "autentica_admin.php";
}

include "gera_relatorio_pararelo_include.php";

$os     = $_GET["os"];
$tipo   = $_GET["tipo"];
$status = trim($_GET["status"]);

$btn_acao    = trim($_POST["btn_acao"]);
$select_acao = trim($_POST["select_acao"]);


# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])) {
	$tipo_busca = $_GET["busca"];

	if (strlen($q) > 2) {
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";
		$sql .= ($tipo_busca == "codigo") ? " AND tbl_posto_fabrica.codigo_posto = '$q' " : " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";

		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) > 0) {
			for ($i=0; $i<pg_numrows ($res); $i++ ){
				$cnpj         = trim(pg_result($res,$i,cnpj));
				$nome         = trim(pg_result($res,$i,nome));
				$codigo_posto = trim(pg_result($res,$i,codigo_posto));
				echo "$cnpj|$nome|$codigo_posto";
				echo "\n";
			}
		}
	}
	exit;
}


if(strlen($btn_acao)>0 AND strlen($select_acao)>0){

	$qtde_os     = trim($_POST["qtde_os"]);

	if (strlen($qtde_os)==0){
		$qtde_os = 0;
	}

	for ($x=0;$x<$qtde_os;$x++){

		$xxos         = trim($_POST["check_".$x]);

		if (strlen($xxos) > 0 AND strlen($msg_erro) == 0){

			$res_os = pg_exec($con,"BEGIN TRANSACTION");

			$sql = "SELECT status_os
					FROM tbl_os_status
					WHERE status_os IN (120, 122, 123, 126)
					AND os = $xxos
					ORDER BY data DESC
					LIMIT 1";
			$res_os = pg_exec($con,$sql);
			if (pg_numrows($res_os)>0){
				$status_da_os = trim(pg_result($res_os,0,status_os));
				if ($status_da_os == 120 or $status_da_os == 122) {

					if($select_acao == "123"){
						$sql = "INSERT INTO tbl_os_status
								(os,status_os,data,observacao,admin)
								VALUES ($xxos,123,current_timestamp,'OS acima de 90 dias ainda aberta. Liberada para Alteração.',$login_admin)";
						$res = pg_exec($con,$sql);
						$msg_erro .= pg_errormessage($con);
					}

					if($select_acao == "126"){
						$sql = "INSERT INTO tbl_os_status
								(os,status_os,data,observacao,admin)
								VALUES ($xxos,126,current_timestamp,'OS acima de 90 dias ainda aberta. Cancelada pela fábrica.',$login_admin)";
						$res = pg_exec($con,$sql);
						$msg_erro .= pg_errormessage($con);
					}
				}else{
					$msg_erro .= "Para está OS não pode ser alterado o Status.";
				}
			}

			if (strlen($msg_erro)==0){
				$res = pg_exec($con,"COMMIT TRANSACTION");
				$msg_ok = "OK, informação gravada com Sucesso.";

			}else{
				$res = pg_exec($con,"ROLLBACK TRANSACTION");
			}
		}
	}
}

if($btn_pesquisa == 'Pesquisar'){

	$data_inicial = trim($_POST['data_inicial']);
	$data_final   = trim($_POST['data_final']);
	$status_os    = trim($_POST['status_os']);
	$os           = trim($_POST['os']);
	$tipo_os      = trim($_POST['tipo_os']);
	$posto_codigo = trim($_POST["posto_codigo"]);

	if (strlen($os)>0){
		$Xos = " AND tbl_os.sua_os = '$os' ";
	}

	$sql_tipo = "120, 122, 123, 126";

	if (strlen($data_inicial) > 0) {
		$xdata_inicial = formata_data ($data_inicial);
		$xdata_inicial = $xdata_inicial." 00:00:00";
	}

	if (strlen($data_final) > 0) {
		$xdata_final = formata_data ($data_final);
		$xdata_final = $xdata_final." 23:59:59";
	}
	if (strlen($xdata_inicial) > 0 AND strlen($xdata_final) > 0) {
		$cond_data.= " AND tbl_os.data_digitacao BETWEEN '$xdata_inicial' AND '$xdata_final' ";
	}

	if(strlen($posto_codigo)>0)         $sql_add .= " AND tbl_posto_fabrica.codigo_posto = '$posto_codigo' ";
}

$layout_menu = "auditoria";
$title = "Auditoria de OS aberta a mais de 90 dias";

include "cabecalho.php";

?>

<style type="text/css">

.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
}
.Erro{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#CC3300;
	font-weight: bold;
	background-color:#FFFFFF;
}
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
</style>

<? include "javascript_calendario_new.php"; //adicionado por Fabio 27-09-2007 ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");

	});
</script>


<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}

	function formatResult(row) {
		return row[2];
	}

	/* Busca pelo Código */
	$("#posto_codigo").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#posto_codigo").result(function(event, data, formatted) {
		$("#posto_nome").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#posto_nome").result(function(event, data, formatted) {
		$("#posto_codigo").val(data[2]) ;
		//alert(data[2]);
	});

});
</script>

<script language="JavaScript">

function fnc_pesquisa_posto(campo, campo2, tipo) {
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



var ok = false;
var cont=0;
function checkaTodos() {
	f = document.frm_pesquisa2;
	if (!ok) {
		for (i=0; i<f.length; i++){
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = true;
				ok=true;
				if (document.getElementById('linha_'+cont)) {
					document.getElementById('linha_'+cont).style.backgroundColor = "#F0F0FF";
				}
				cont++;
			}
		}
	}else{
		for (i=0; i<f.length; i++) {
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = false;
				ok=false;
				if (document.getElementById('linha_'+cont)) {
					document.getElementById('linha_'+cont).style.backgroundColor = "#FFFFFF";
				}
				cont++;
			}
		}
	}
}

function setCheck(theCheckbox,mudarcor,cor){
	if (document.getElementById(theCheckbox)) {
	}
	if (document.getElementById(mudarcor)) {
		document.getElementById(mudarcor).style.backgroundColor  = (document.getElementById(theCheckbox).checked ? "#FFF8D9" : cor);
	}
}
</script>

<?
if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {
	include "gera_relatorio_pararelo.php";
}

if ($gera_automatico != 'automatico' and strlen($msg_erro)==0){
	include "gera_relatorio_pararelo_verifica.php";
}

?>

<? 
if(strlen($msg_erro) > 0){
	echo "<p align='center' style='font-size: 14px; font-family: verdana;'><FONT COLOR='#FF0000'><b>$msg_erro</FONT></b></p>";
}else{
	if(strlen($msg_ok) > 0){
		echo "<p align='center' style='font-size: 14px; font-family: verdana;'><FONT COLOR='#0000ff'><b>$msg_ok</FONT></b></p>";
	}

}

if($login_fabrica == 15) { ?>
<br>
<form name="frm_pesquisa" method="post" action="<?echo $PHP_SELF?>">

<TABLE width="700" align="center" border="0" cellspacing='0' cellpadding='0' class='PesquisaTabela'>

<caption>Relatório de Auditoria de OS aberta a mais de 90 dias</caption>

<TBODY>
<TR>
	<TD>Número da OS<br><input type="text" name="os" id="os" size="20" maxlength="20" value="<? echo $os ?>" class="frm"></TD>
	<TD></TD>
</TR>
<TR>
	<TD>Data Inicial<br><input type="text" name="data_inicial" id="data_inicial" size="11" maxlength="10" value="<? echo $data_inicial ?>" class="frm"></TD>
	<TD>Data Final<br><input type="text" name="data_final" id="data_final" size="11" maxlength="10" value="<? echo $data_final ?>" class="frm"></TD>
</TR>
<TR>
	<TD>Código Posto<br><input type="text" name="posto_codigo" id="posto_codigo" size="15"  value="<? echo $posto_codigo ?>" class="frm"></TD>
	<TD>Nome do Posto<br><input type="text" name="posto_nome" id="posto_nome" size="40"  value="<? echo $posto_nome ?>" class="frm"></TD>
</TR>
<tr>
	<td colspan='2'>
		<b>Status OS:</b><br>
			<INPUT TYPE="radio" NAME="status_os" value='120' <? if(trim($status_os) == '120' OR trim($status_os)==0) echo "checked='checked'"; ?>>Bloqueada&nbsp;&nbsp;&nbsp;
			<INPUT TYPE="radio" NAME="status_os" value='123' <? if(trim($status_os) == '123') echo "checked='checked'"; ?>>Liberada Alteração&nbsp;&nbsp;&nbsp;
	</td>
</tr>
</tbody>
<TR>
	<TD colspan="2">
		<br>
		<input type='hidden' name='btn_pesquisa' value=''>
		<IMG src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_pesquisa.value == '' ) { document.frm_pesquisa.btn_pesquisa.value='Pesquisar'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer " alt='Clique AQUI para pesquisar'>
	</TD>
</TR>
</table>
</form>
<br/><br/>
<? }

$posto= trim($_GET["posto"]);
if(strlen($posto) == 0){
	$posto= trim($_POST["posto"]);
}
if(strlen($posto) > 0) {
	$cond_posto = " AND tbl_os.posto = $posto ";
}

if (strlen($posto) > 0 or strlen($btn_pesquisa) > 0) {
	$sql_tipo = " 120, 122, 123, 126";
	$aprovacao = " 120, 122 ";
	if ($status_os == '120') $aprovacao ="120";
	if ($status_os == '123') $aprovacao ="123";

	if($login_fabrica == 15) {
		$cond_data2 = " AND data_abertura < (current_date - interval '59 days')  ";
	}else{
		$cond_data2 = " AND data_abertura < (current_date - interval '90 days') ";
	}


	$sql =  "
			SELECT 
				interv.os
				INTO TEMP tmp_interv_$login_admin
			FROM (
				SELECT
				ultima.os,
				(
					SELECT status_os 
					FROM tbl_os_status 
					WHERE status_os IN ($sql_tipo) 
					AND tbl_os_status.os = ultima.os
					ORDER BY data 
					DESC LIMIT 1
				) AS ultimo_status
				FROM (
						SELECT DISTINCT os 
						FROM tbl_os_status 
						WHERE status_os IN ($sql_tipo)
				) ultima
			) interv
			JOIN tbl_os USING(os)
			WHERE interv.ultimo_status IN ($aprovacao)
			$Xos
			;

			CREATE INDEX tmp_interv_OS_$login_admin ON tmp_interv_$login_admin(os);

			SELECT  
				tbl_os.os,
				tbl_os.sua_os,
				tbl_posto.posto,
				tbl_posto_fabrica.codigo_posto,
				tbl_posto.nome,
				to_char(tbl_os.data_abertura, 'dd/mm/yyyy') as data_abertura,
				to_char(tbl_os.data_digitacao, 'dd/mm/yyyy') as data_digitacao,
				tbl_produto.referencia as produto_referencia, 
				tbl_produto.descricao as produto_descricao,
				(SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN ($sql_tipo) ORDER BY data DESC LIMIT 1) AS status_os         ,
				(SELECT observacao FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN ($sql_tipo) ORDER BY data DESC LIMIT 1) AS status_observacao,
				(SELECT tbl_status_os.descricao FROM tbl_os_status JOIN tbl_status_os USING(status_os) WHERE tbl_os.os = tbl_os_status.os AND status_os IN ($sql_tipo) ORDER BY data DESC LIMIT 1) AS status_descricao
			FROM tmp_interv_$login_admin X
			JOIN tbl_os ON tbl_os.os = X.os
			JOIN tbl_posto using(posto)
			JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_produto using(produto)
			WHERE tbl_os.fabrica = $login_fabrica";
			if (!empty($_GET['linha']) && is_numeric($_GET['linha'])) {
				$sql .=	" AND linha = " . abs($_GET['linha']);
			}
			$sql .= " AND excluida is not true 
					  AND finalizada is null
					  $cond_data
					  $cond_data2
					  $sql_add
					  $Xos
					  $cond_posto
					  ORDER BY sua_os; ";

	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		$codigo_posto = pg_result($res, 0, codigo_posto);
		$posto_nome   = pg_result($res, 0, nome);

		echo "<FORM name='frm_pesquisa2' METHOD='POST' ACTION='$PHP_SELF'>";
		echo "<input type='hidden' name='aprova'         value='$aprova'>";
		echo "<input type='hidden' name='posto' value='$posto'>";

		echo "<table width='98%' border='0' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 11px' bgcolor='#FFFFFF'>";

		if (strlen($posto) > 0) {
			echo "<tr>";
			echo "<td bgcolor='#485989' colspan='8' align='left'><font color='#FFFFFF'><B>Codigo Posto: </B>$codigo_posto - <B>Nome Posto:</B> $posto_nome </font></td>";
			echo "</tr>";
		}

		echo "<tr>";
		echo "<td bgcolor='#485989'><img border='0' src='imagens_admin/selecione_todas.gif' onclick='javascript: checkaTodos()' alt='Selecionar todos' style='cursor: hand;' align='center'></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>OS</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Data <br>Abertura</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Data Digitação</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Produto</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Descrição</B></font></td>";
		echo "<td bgcolor='#485989' width='300'><font color='#FFFFFF'><B>Observação</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Status</B></font></td>";
		echo "</tr>";

		$cores = '';
		$qtde_intervencao = 0;

		for ($x = 0; $x < pg_numrows($res); $x++) {

			$os						= pg_result($res, $x, os);
			$sua_os					= pg_result($res, $x, sua_os);
			$produto_referencia		= pg_result($res, $x, produto_referencia);
			$produto_descricao		= pg_result($res, $x, produto_descricao);
			$data_digitacao			= pg_result($res, $x, data_digitacao);
			$data_abertura			= pg_result($res, $x, data_abertura);
			$status_os				= pg_result($res, $x, status_os);
			$status_observacao		= pg_result($res, $x, status_observacao);
			$status_descricao		= pg_result($res, $x, status_descricao);

			$cores++;
			$cor = ($cores % 2 == 0) ? "#FEFEFE": '#E8EBEE';
			if(strlen($sua_os)==o)$sua_os=$os;
			echo "<tr bgcolor='$cor' id='linha_$x'>";
			echo "<td align='center' width='0'>";
				if($status_os==120 or $status_os==122 or $status_os ==123 ){
					echo "<input type='checkbox' name='check_$x' id='check_$x' value='$os' onclick=\"setCheck('check_$x','linha_$x','$cor');\" ";
					if (strlen($msg_erro)>0){
						if (strlen($_POST["check_".$x])>0){
							echo " CHECKED ";
						}
					}
					echo ">";
				}
			echo "</td>";
			echo "<td style='font-size: 9px; font-family: verdana' nowrap ><a href='os_press.php?os=$os'  target='_blank'>$sua_os</a></td>";
			echo "<td style='font-size: 9px; font-family: verdana'>".$data_abertura. "</td>";
			echo "<td style='font-size: 9px; font-family: verdana' nowrap>".$data_digitacao. "</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap><acronym title='Produto: $produto_referencia' style='cursor: help'> $produto_referencia </acronym></td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap><acronym title='Produto: $produto_referencia - $produto_descricao' style='cursor: help'>". $produto_descricao ."</acronym></td>";
			echo "<td style='font-size: 9px; font-family: verdana' ><acronym >".$status_observacao. "</acronym></td>";
			echo "<td style='font-size: 9px; font-family: verdana' nowrap><acronym title='Observação: ".$status_observacao."'>".$status_descricao. "</acronym></td>";
			echo "</tr>";
		}
		echo "<input type='hidden' name='qtde_os' value='$x'>";
		echo "<input type='hidden' name='qtde_os' value='$x'>";
		echo "<tr>";
		echo "<td height='20' bgcolor='#485989' colspan='100%' align='left'> ";
		echo "&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "<img border='0' src='imagens/seta_checkbox.gif' align='absmiddle'> &nbsp; <font color='#FFFFFF'><B>COM MARCADOS:</B></font> &nbsp;";
		echo "<select name='select_acao' size='1' class='frm' >";
		echo "<option value=''></option>";
		echo "<option value='123'";  if ($_POST["select_acao"] == "123")  echo " selected"; echo ">LIBERAR ALTERAÇÃO</option>";
		if($login_fabrica == 3) {
			echo "<option value='126'";  if ($_POST["select_acao"] == "126")  echo " selected"; echo ">CANCELAR OS</option>";
		}
		echo "</select>";
		echo "&nbsp;&nbsp;<img src='imagens/btn_gravar.gif' style='cursor:pointer' onclick='javascript: document.frm_pesquisa2.submit()' style='cursor: hand;' border='0'></td>";
		echo "<input type='hidden' name='btn_acao' value='Pesquisar'>";
		echo "</table>";
		echo "</form>";
	}else{
		echo "<center>Nenhuma OS encontrada.</center>";
	}
	$msg_erro = '';
}else{
	echo ($login_fabrica==3) ?"<center>Nenhuma OS encontrada.</center>" : "";
}

include "rodape.php" ?>