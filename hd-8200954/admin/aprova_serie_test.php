<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="call_center";
include "autentica_admin.php";
include 'funcoes.php';


# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];
	
	if (strlen($q)>2){
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";
		if ($tipo_busca == "codigo"){
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
		}else{
			$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		}
		
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

$os   = $_GET["os"];
$tipo = $_GET["tipo"];

$btn_acao    = trim($_POST["btn_acao"]);
$select_acao = trim($_POST["select_acao"]);

if(strlen($btn_acao)>0 AND strlen($select_acao)>0){

	$qtde_os     = trim($_POST["qtde_os"]);
	$observacao  = trim($_POST["observacao"]);

	if($select_acao == "104" AND strlen($observacao) == 0){
		$msg_erro .= "Informe o motivo da reprovação OS.";
	}

	if(strlen($observacao) > 0){
		$observacao = "' Observação: $observacao '";
	}else{
		$observacao = " NULL ";
	}

	if (strlen($qtde_os)==0){
		$qtde_os = 0;
	}

	for ($x=0;$x<$qtde_os;$x++){

		$xxos = trim($_POST["check_".$x]);

		if (strlen($xxos) > 0 AND strlen($msg_erro) == 0){

			$res_os = pg_exec($con,"BEGIN TRANSACTION");

			$sql = "SELECT status_os
					FROM tbl_os_status
					WHERE status_os IN (102,103,104)
					AND os = $xxos
					ORDER BY data DESC 
					LIMIT 1";
			$res_os = pg_exec($con,$sql);
			if (pg_numrows($res_os)>0){
				$status_da_os = trim(pg_result($res_os,0,status_os));
				if ($status_da_os == 102){ 
					//Aprovada
					if($select_acao == "103"){
						$sql = "INSERT INTO tbl_os_status
								(os,status_os,data,observacao,admin) 
								VALUES ($xxos,103,current_timestamp,$observacao,$login_admin)";
						$res = pg_exec($con,$sql);
						$msg_erro .= pg_errormessage($con);
					}
					//Recusada
					if($select_acao == "104"){
						$sql = "INSERT INTO tbl_os_status
								(os,status_os,data,observacao,admin) 
								VALUES ($xxos,104,current_timestamp,$observacao,$login_admin)";
						$res = pg_exec($con,$sql);
						$msg_erro .= pg_errormessage($con);

						if ($login_fabrica == 30 or $login_fabrica == 50) {
							$sql = "UPDATE tbl_os SET
									excluida  = 't'
									WHERE os = $xxos
									AND fabrica = $login_fabrica ";
							$res = pg_exec($con,$sql);
							$msg_erro .= pg_errormessage($con);
							
							if($login_fabrica == 30) {
								$sql = "SELECT fn_os_excluida($xxos,$login_fabrica,$login_admin);";
								$res = pg_exec($con,$sql);
								$msg_erro .= pg_errormessage($con);
							}
						}
						/*
						$sql = "UPDATE tbl_os SET
									excluida  = 't'
								WHERE os = $xxos
								AND fabrica = $login_fabrica ";
						$res = pg_exec($con,$sql);

						$sql = "UPDATE tbl_os_extra SET
									status_os = 94
								WHERE os = $xxos";
						$res = pg_exec($con,$sql);
						$msg_erro .= pg_errormessage($con);
						*/
					}
				}
			}

			if (strlen($msg_erro)==0){
				$res = pg_exec($con,"COMMIT TRANSACTION");
			}else{
				$res = pg_exec($con,"ROLLBACK TRANSACTION");
			}
		}
	}
}

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

$layout_menu = "auditoria";
$title = "Aprovação Ordem de Número de Série da OS";

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
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

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
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

/*****************************
ELEMENTOS DE POSICIONAMENTO
*****************************/

#container {
  border: 0px;
  padding:0px 0px 0px 0px;
  margin:0px 0px 0px 0px;
  background-color: white;
}
</style>

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
</script>


<script language="JavaScript">
function date_onkeydown() {
  if (window.event.srcElement.readOnly) return;
  var key_code = window.event.keyCode;
  var oElement = window.event.srcElement;
  if (window.event.shiftKey && String.fromCharCode(key_code) == "T") {
        var d = new Date();
        oElement.value = String(d.getMonth() + 1).padL(2, "0") + "/" +
                         String(d.getDate()).padL(2, "0") + "/" +
                         d.getFullYear();
        window.event.returnValue = 0;
    }
    if (!window.event.shiftKey && !window.event.ctrlKey && !window.event.altKey) {
        if ((key_code > 47 && key_code < 58) ||
          (key_code > 95 && key_code < 106)) {
            if (key_code > 95) key_code -= (95-47);
            oElement.value =
                oElement.value.replace(/[dma]/, String.fromCharCode(key_code));
        }
        if (key_code == 8) {
            if (!oElement.value.match(/^[dma0-9]{2}\/[dma0-9]{2}\/[dma0-9]{4}$/))
                oElement.value = "dd/mm/aaaa";
            oElement.value = oElement.value.replace(/([dma\/]*)[0-9]([dma\/]*)$/,
                function ($0, $1, $2) {
                    var idx = oElement.value.search(/([dma\/]*)[0-9]([dma\/]*)$/);
                    if (idx >= 5) {
                        return $1 + "a" + $2;
                    } else if (idx >= 2) {
                        return $1 + "m" + $2;
                    } else {
                        return $1 + "d" + $2;
                    }
                } );
            window.event.returnValue = 0;
        }
    }
    if (key_code != 9) {
        event.returnValue = false;
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
//		document.getElementById(theCheckbox).checked = (document.getElementById(theCheckbox).checked ? false : true);
	}
	if (document.getElementById(mudarcor)) {
		document.getElementById(mudarcor).style.backgroundColor  = (document.getElementById(theCheckbox).checked ? "#FFF8D9" : cor);
	}
}

</script>

<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");

		$("input[@rel='data_nf']").maskedinput("99/99/9999");
	});
</script>


<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script language='javascript' src='ajax.js'></script>
<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>
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


function abreInteracao(linha,os,tipo) {

	var div = document.getElementById('interacao_'+linha);
	var os = os;
	var tipo = tipo;

	//alert('ajax_grava_interacao.php?linha='+linha+'&os='+os+'&tipo='+tipo);
	
	requisicaoHTTP('GET','ajax_grava_interacao.php?linha='+linha+'&os='+os+'&tipo='+tipo, true , 'div_detalhe_carrega2');
	
}


function div_detalhe_carrega2 (campos) {
	campos_array = campos.split("|");
	resposta = campos_array [0];
	linha = campos_array [1];
	var div = document.getElementById('interacao_'+linha);
	div.innerHTML = resposta;
	var comentario = document.getElementById('comentario_'+linha);
	comentario.focus();
}

function gravarInteracao(linha,os,tipo) {
	
var linha = linha;
var os = os;
var tipo = tipo;
var comentario = document.getElementById('comentario_'+linha).value;
//alert('ajax_grava_interacao.php?linha='+linha+'&os='+os+'&comentario='+comentario+'&tipo='+tipo);

requisicaoHTTP('GET','ajax_grava_interacao.php?linha='+linha+'&os='+os+'&comentario='+comentario+'&tipo='+tipo, true , 'div_detalhe_carrega');

}

function div_detalhe_carrega (campos) {
	campos_array = campos.split("|");
	resposta = campos_array [1];
	linha = campos_array [2];
	os = campos_array [3];

	if (resposta == 'ok') {
		document.getElementById('interacao_' + linha).innerHTML = "Gravado Com sucesso!!!";
		document.getElementById('btn_interacao_' + linha).innerHTML = "<font color='red'><a href='#' onclick='abreInteracao("+linha+","+os+",\"Mostrar\")'><img src='imagens/btn_interagir_amarelo.gif' title='Aguardando Resposta do Posto'></a></font>";
//		var linha = new Number(linha+1);
		var table = document.getElementById('linha_'+linha);
//		alert(document.getElementById('linha_'+linha).innerHTML);
		table.style.background = "#FFCC00";
	
	}
}

</script>

<script>
function abreObs(os,codigo_posto,sua_os){
	janela = window.open("obs_os_troca.php?os=" + os + "&codigo_posto=" + codigo_posto +"&sua_os=" + sua_os,"formularios",'resizable=1,scrollbars=yes,width=400,height=250,top=0,left=0');
	janela.focus();
}
</script>
<style type="text/css">

body {
	margin: 0px;
}

body {
	margin: 0px;
}

.titulo {
	font-family: Arial;
	font-size: 7pt;
	text-align: right;
	color: #000000;
	background: #ced7e7;
}
.titulo2 {
	font-family: Arial;
	font-size: 7pt;
	text-align: center;
	color: #000000;
	background: #ced7e7;
}



.titulo3 {
	font-family: Arial;
	font-size: 7pt;
	text-align: left;
	color: #000000;
	background: #ced7e7;
}
.inicio {
	font-family: Arial;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-align: left;
	color: #FFFFFF;
}

.conteudo {
	font-family: Arial;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-align: left;
	background: #F4F7FB;
}

.conteudo2 {
	font-family: Arial;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-align: left;
	background: #FFDCDC;
}

.Tabela{
	border:1px solid #d2e4fc;
	background-color:#485989;
	}
.subtitulo {
	font-family: Verdana;
	FONT-SIZE: 9px;
	text-align: left;
	background: #F4F7FB;
	padding-left:5px
}
.justificativa{
    font-family: Arial;
    FONT-SIZE: 10px;
    background: #F4F7FB;
}
.inpu{
	border:1px solid #666;
}
.conteudo_sac {
    font-family: Arial;
    FONT-SIZE: 10pt;
    text-align: left;
    background: #F4F7FB;
}

table.bordasimples {border-collapse: collapse;}

table.bordasimples tr td {border:1px solid #000000;}

</style>
<? include "javascript_pesquisas.php"; 

if($btn_acao == 'Pesquisar'){

	$data_inicial       = trim($_POST['data_inicial']);
	$data_final         = trim($_POST['data_final']);
	$aprova             = trim($_POST['aprova']);
	$os                 = trim($_POST['os']);
	$codigo_posto_off   = trim(strtoupper($_POST['codigo_posto_off']));
	$posto_nome_off     = trim(strtoupper($_POST['posto_nome_off']));
	$regiao_comercial   = trim($_POST['regiao_comercial']);

	if (strlen($data_inicial)==0 or strlen($data_final)==0 ){
		$msg_erro = "Informe o período inicial e final";
	}
	if (strlen($os)>0){
		$Xos = " AND os = $os ";
	}
	
	if(strlen($aprova) == 0){
		$aprova = "aprovacao";
		$aprovacao = "102";
	}elseif($aprova=="aprovacao"){
		$aprovacao = "102";
	}elseif($aprova=="aprovadas"){
		$aprovacao = "103";
	}elseif($aprova=="reprovadas"){
		$aprovacao = "104";
	}

	if (strlen($data_inicial) > 0) {
		$xdata_inicial = formata_data ($data_inicial);
		$xdata_inicial = $xdata_inicial." 00:00:00";
	}

	if (strlen($data_final) > 0) {
		$xdata_final = formata_data ($data_final);
		$xdata_final = $xdata_final." 23:59:59";
	}

	if ($login_fabrica == 50 ) { //hd 71341 waldir

		$cond_excluidas = "AND tbl_os.excluida is not true "; 
	}
}

if(strlen($msg_erro) > 0){
	echo "<p align='center' style='font-size: 14px; font-family: verdana;'><FONT COLOR='#FF0000'><b>$msg_erro</FONT></b></p>";
}

//LEGENDAS hd 14631
/*
echo "<p>";
echo "<div align='center' style='position: relative; left: 10'>";
echo "<table border='0' cellspacing='0' cellpadding='0'>";
echo "<tr height='18'>";
echo "<td nowrap width='18' >";
echo "<span style='background-color:#FDEBD0;color:#FDEBD0;border:1px solid #F8B652'>__</span></td>";
echo "<td align='left'><font size='1'><b>&nbsp;  OS com origem da Intervenção</b></font></td><BR>";
echo "</tr>";
echo "<tr height='18'>";
echo "<td nowrap width='18' >";
echo "<span style='background-color:#99FF66;color:#00FF00;border:1px solid #F8B652'>__</span></td>";
echo "<td align='left'><font size='1'><b>&nbsp;  OS com Observação</b></font></td><BR>";
echo "</tr>";
echo "<tr height='18'>";
echo "<td nowrap width='18' >";
echo "<span style='background-color:#CCFFFF; color:#D7FFE1;border:1px solid #F8B652'>__</span></td>";
echo "<td align='left'><font size='1'><b>&nbsp; Reincidências</b></font></td><BR>";
echo "</tr>";
echo "</table>";
echo "</div>";
echo "</p>";
*/
//----------------------

?>

<form name="frm_pesquisa" method="post" action="<?echo $PHP_SELF?>">

<TABLE width="450" align="center" border="0" cellspacing='0' cellpadding='0' class='PesquisaTabela'>

<caption>Parâmetros para pesquisa</caption>

<TBODY>
<TR>

	<TD>Número da OS<br><input type="text" name="os" id="os" size="20" maxlength="20" value="<? echo $os ?>" class="frm"></TD>
	<TD></TD>
</TR>
<TR>

	<TD>Data Inicial<br><input type="text" name="data_inicial" id="data_inicial" size="11" maxlength="10" value="<? echo $data_inicial ?>" class="frm"></TD>
	<TD>Data Final<br><input type="text" name="data_final" id="data_final" size="11" maxlength="10" value="<? echo $data_final ?>" class="frm"></TD>
</TR>
<tr bgcolor="#D9E2EF" align='left'>
	<td>Posto</td>
	<td>Nome do Posto</td>
</tr>
<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
	<td>
		<input type="text" name="codigo_posto_off" id="codigo_posto_off" size="8" value="<? echo $codigo_posto_off ?>" class="frm">
		<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto_off, document.frm_pesquisa.posto_nome_off, 'codigo')">
	</td>
	<td>
		<input type="text" name="posto_nome_off" id="posto_nome_off" size="30" value="<?echo $posto_nome_off ?>" class="frm">
		<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto_off, document.frm_pesquisa.posto_nome_off, 'nome')">
	</td>
</tr>

<?if ($login_fabrica==30){?>
	<TR width = '100%' align="left">
		  <TD colspan = '4' > <center>Estado</center></TD>
	</TR>	  
	 <TR width = '100%' align="left">
		<td colspan = '4' CLASS='table_line'>
			<center>
			<select name="estado" size="1">
				<option value=""   <? if (strlen($estado) == 0) echo " selected "; ?>>TODOS OS ESTADOS</option>
				<option value="AC" <? if ($estado == "AC") echo " selected "; ?>>AC - Acre</option>
				<option value="AL" <? if ($estado == "AL") echo " selected "; ?>>AL - Alagoas</option>
				<option value="AM" <? if ($estado == "AM") echo " selected "; ?>>AM - Amazonas</option>
				<option value="AP" <? if ($estado == "AP") echo " selected "; ?>>AP - Amapá</option>
				<option value="BA" <? if ($estado == "BA") echo " selected "; ?>>BA - Bahia</option>
				<option value="CE" <? if ($estado == "CE") echo " selected "; ?>>CE - Ceará</option>
				<option value="DF" <? if ($estado == "DF") echo " selected "; ?>>DF - Distrito Federal</option>
				<option value="ES" <? if ($estado == "ES") echo " selected "; ?>>ES - Espírito Santo</option>
				<option value="GO" <? if ($estado == "GO") echo " selected "; ?>>GO - Goiás</option>
				<option value="MA" <? if ($estado == "MA") echo " selected "; ?>>MA - Maranhão</option>
				<option value="MG" <? if ($estado == "MG") echo " selected "; ?>>MG - Minas Gerais</option>
				<option value="MS" <? if ($estado == "MS") echo " selected "; ?>>MS - Mato Grosso do Sul</option>
				<option value="MT" <? if ($estado == "MT") echo " selected "; ?>>MT - Mato Grosso</option>
				<option value="PA" <? if ($estado == "PA") echo " selected "; ?>>PA - Pará</option>
				<option value="PB" <? if ($estado == "PB") echo " selected "; ?>>PB - Paraíba</option>
				<option value="PE" <? if ($estado == "PE") echo " selected "; ?>>PE - Pernambuco</option>
				<option value="PI" <? if ($estado == "PI") echo " selected "; ?>>PI - Piauí</option>
				<option value="PR" <? if ($estado == "PR") echo " selected "; ?>>PR - Paraná</option>
				<option value="RJ" <? if ($estado == "RJ") echo " selected "; ?>>RJ - Rio de Janeiro</option>
				<option value="RN" <? if ($estado == "RN") echo " selected "; ?>>RN - Rio Grande do Norte</option>
				<option value="RO" <? if ($estado == "RO") echo " selected "; ?>>RO - Rondônia</option>
				<option value="RR" <? if ($estado == "RR") echo " selected "; ?>>RR - Roraima</option>
				<option value="RS" <? if ($estado == "RS") echo " selected "; ?>>RS - Rio Grande do Sul</option>
				<option value="SC" <? if ($estado == "SC") echo " selected "; ?>>SC - Santa Catarina</option>
				<option value="SE" <? if ($estado == "SE") echo " selected "; ?>>SE - Sergipe</option>
				<option value="SP" <? if ($estado == "SP") echo " selected "; ?>>SP - São Paulo</option>
				<option value="TO" <? if ($estado == "TO") echo " selected "; ?>>TO - Tocantins</option>
			</select>
			</center>
		</td>
	  </TR>
<?}?>

<?if ($login_fabrica==50){?>
<TR>
	<TD colspan='2'>Região Comercial<br>
			<select name='regiao_comercial' class="frm">
				<option value=''></option>
				<option value='1' <? if($regiao_comercial=='1') echo "selected"?>>Região Comercial 1 (SP)<option>
				<option value='2' <? if($regiao_comercial=='2') echo "selected"?>>Região Comercial 2 (PR SC RS RO AC MS e MT)<option>
				<option value='3' <? if($regiao_comercial=='3') echo "selected"?>>Região Comercial 3 (ES BA PE PB SE AL CE AM TO PI MA PA RN RR AP)<option>
				<option value='4' <? if($regiao_comercial=='4') echo "selected"?>>Região Comercial 4 (MG GO DF RJ)<option>
			</select>
	</TD>
</TR>
<?}?>
<tr>
	<td colspan='2'>
		<b>Mostrar as OS:</b><br>

			<INPUT TYPE="radio" NAME="aprova" value='aprovacao' <? if(trim($aprova) == 'aprovacao' OR trim($aprova)==0) echo "checked='checked'"; ?>>Em aprovação &nbsp;&nbsp;&nbsp;
			<INPUT TYPE="radio" NAME="aprova" value='aprovadas' <? if(trim($aprova) == 'aprovadas') echo "checked='checked'"; ?>>Aprovadas  &nbsp;&nbsp;&nbsp;
			<INPUT TYPE="radio" NAME="aprova" value='reprovadas' <? if(trim($aprova) == 'reprovadas') echo "checked='checked'"; ?>>Reprovadas &nbsp;&nbsp;&nbsp;

	</td>
</tr>
</tbody>
<TR>
	<TD colspan="2">
		<br>
		<input type='hidden' name='btn_acao' value=''>
		<IMG src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='Pesquisar'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer " alt='Clique AQUI para pesquisar'>
	</TD>
</TR>
</table>
</form>

<?
if (strlen($btn_acao)  > 0 AND strlen($msg_erro)==0) {

	$sql =  "SELECT interv.os, data
			INTO TEMP tmp_interv_$login_admin
			FROM (
			SELECT 
			ultima.os, data,
			(SELECT status_os FROM tbl_os_status WHERE status_os IN (102,103,104) AND tbl_os_status.os = ultima.os ORDER BY data DESC LIMIT 1) AS ultimo_status
			FROM (SELECT DISTINCT os, data FROM tbl_os_status WHERE status_os IN (102,103,104) ) ultima
			) interv
			WHERE interv.ultimo_status IN ($aprovacao)
			$Xos
			;
			
			CREATE INDEX tmp_interv_OS_$login_admin ON tmp_interv_$login_admin(os);

			/* select os from  tmp_interv_$login_admin; */
			
			SELECT	tbl_os.os                                                   ,
					tbl_os.sua_os                                               ,
					tbl_os.consumidor_nome                                      ,
					TO_CHAR(data,'DD/MM/YYYY')                    AS data_status,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')  AS data_abertura,
					TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
					tbl_os.fabrica                                              ,
					tbl_os.consumidor_nome                                      ,
					tbl_os.nota_fiscal_saida                                    ,
					tbl_os.serie                       AS produto_serie         ,
					to_char(tbl_os.data_nf_saida,'DD/MM/YYYY') AS data_nf_saida ,
					tbl_posto.nome                     AS posto_nome            ,
					tbl_posto_fabrica.codigo_posto                              ,
					tbl_posto_fabrica.contato_estado                            ,
					tbl_produto.referencia             AS produto_referencia    ,
					tbl_produto.descricao              AS produto_descricao     ,
					tbl_produto.voltagem                                        ,
					(SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (102,103,104) ORDER BY data DESC LIMIT 1) AS status_os         ,
					(SELECT admin FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (102,103,104) ORDER BY data DESC LIMIT 1) AS admin,
					(SELECT observacao FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (102,103,104) ORDER BY data DESC LIMIT 1) AS status_observacao,
					(SELECT tbl_status_os.descricao FROM tbl_os_status JOIN tbl_status_os USING(status_os) WHERE tbl_os.os = tbl_os_status.os AND status_os IN (102,103,104) ORDER BY data DESC LIMIT 1) AS status_descricao
				FROM tmp_interv_$login_admin X
				JOIN tbl_os ON tbl_os.os = X.os
				JOIN tbl_produto              ON tbl_produto.produto = tbl_os.produto
				JOIN tbl_posto                ON tbl_os.posto        = tbl_posto.posto
				JOIN tbl_posto_fabrica        ON tbl_posto.posto     = tbl_posto_fabrica.posto 
				AND tbl_posto_fabrica.fabrica = $login_fabrica";
	if ($login_fabrica == 30) {
		if ($aprovacao == 104) {
			$sql .= " JOIN tbl_os_excluida on X.os = tbl_os_excluida.os and tbl_os_excluida.fabrica=$login_fabrica
			 WHERE tbl_os.fabrica = 0";
		}else{
			$sql .= "WHERE tbl_os.fabrica = $login_fabrica";
		}
		if (strlen($estado)>0){
			$sql .= "AND tbl_posto_fabrica.contato_estado = '$estado'"; 
		}
	}else{
		$sql .= "WHERE tbl_os.fabrica = $login_fabrica";
	}
	if(strlen($codigo_posto_off)>0){
		$sql .= " AND tbl_posto_fabrica.codigo_posto = '$codigo_posto_off' ";
	}
	//HD 169362
	if ($regiao_comercial){
		/*
		1-SP
		2-PR SC RS RO AC MS MT
		3-ES BA PE PB SE AL CE AM TO PI MA PA RN RR AP
		4-MG GO DF RJ
		*/
		$array_regioes = array(
								'1' => "SP",
								'2' => "PR SC RS RO AC MS MT",
								'3' => "ES BA PE PB SE AL CE AM TO PI MA PA RN RR AP",
								'4' => "MG GO DF RJ",
							);
		if (isset($array_regioes[$regiao_comercial])) {
			$estados = $array_regioes[$regiao_comercial];
			$estados = str_replace(" ","','",$estados);
			$estados = "'".$estados."'";
			$sql .= " AND tbl_posto.estado IN ($estados) ";
		}
	}
	if (strlen($xdata_inicial) > 0 AND strlen($xdata_final) > 0) {
		$sql .= " AND tbl_os.data_digitacao BETWEEN '$xdata_inicial' AND '$xdata_final'
				$cond_excluidas
				ORDER BY tbl_posto_fabrica.codigo_posto,tbl_os.os ";
	}
	$res = pg_exec($con,$sql);

	if(pg_numrows($res)>0){
		echo "<BR><BR><FORM name='frm_pesquisa2' METHOD='POST' ACTION='$PHP_SELF'>";
		echo "<input type='hidden' name='data_inicial'   value='$data_inicial'>";
		echo "<input type='hidden' name='data_final'     value='$data_final'>";
		echo "<input type='hidden' name='aprova'         value='$aprova'>";
		echo "<table width='800' id='table_aprova' border='0' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 11px' bgcolor='#FFFFFF'>";
		echo "<tr>";
		echo "<td bgcolor='#485989'><img border='0' src='imagens_admin/selecione_todas.gif' onclick='javascript: checkaTodos()' alt='Selecionar todos' style='cursor: hand;' align='center'></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>OS</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>DATA <br>Digitação</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>DATA <br>Abertura</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Posto</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>UF</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Produto</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Descrição</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Série</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>STATUS</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>ADMIN</B></font></td>";
		if ($login_fabrica == 30 and ($aprovacao == '103' or $aprovacao == 102)){
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>DATA <br>Aprovação</B></font></td>";
		}
		if ($login_fabrica == 30 and $aprovacao == '104'){
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>DATA <br>Recusa</B></font></td>";
		}
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>OBSERVAÇÃO</B></font></td>";
		if ($login_fabrica == 50) {
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Interação</B></font></td>";
		}
		echo "</tr>";
		$cores = '';
		$qtde_intervencao = 0;
		$total_os = pg_numrows($res);
		for ($x=0; $x<pg_numrows($res);$x++){
			$os						= pg_result($res, $x, os);
			$sua_os					= pg_result($res, $x, sua_os);
			$codigo_posto			= pg_result($res, $x, codigo_posto);
			$posto_nome				= pg_result($res, $x, posto_nome);
			$contato_estado			= pg_result($res, $x, contato_estado);
			$consumidor_nome		= pg_result($res, $x, consumidor_nome);
			$produto_referencia		= pg_result($res, $x, produto_referencia);
			$produto_descricao		= pg_result($res, $x, produto_descricao);
			$produto_serie			= pg_result($res, $x, produto_serie);
			$produto_voltagem		= pg_result($res, $x, voltagem);
			$data_status			= pg_result($res, $x, data_status);
			$data_digitacao			= pg_result($res, $x, data_digitacao);
			$data_abertura			= pg_result($res, $x, data_abertura);
			$status_os				= pg_result($res, $x, status_os);
			$status_observacao		= pg_result($res, $x, status_observacao);
			$status_descricao		= pg_result($res, $x, status_descricao);
			$admin					= pg_result($res, $x, admin);
			$cores++;
			$cor = ($cores % 2 == 0) ? "#FEFEFE": '#E8EBEE';
			if ($login_fabrica == 50) {
				$sqlint = "SELECT os_interacao,admin from tbl_os_interacao
				WHERE os = $os
				ORDER BY os_interacao DESC limit 1";
				$resint = pg_exec($con,$sqlint);
				if(pg_num_rows($resint)>0) {
					$admin = pg_result($resint,0,admin);
					if (strlen($admin)>0) {
						$cor = "#FFCC00";
					}
					else {
						$cor = "#669900";
					}
				}
			}
			echo "<tr bgcolor='$cor' id='linha_$x'>";
			echo "<td align='center' width='0'>";
				if($status_os==102){
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
			echo "<td style='font-size: 9px; font-family: verdana'>".$data_digitacao. "</td>";
			echo "<td style='font-size: 9px; font-family: verdana'>".$data_abertura. "</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap title='".$codigo_posto." - ".$posto_nome."'>".$codigo_posto."-".substr($posto_nome,0,20) ."...</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap><acronym title='Estado: ' style='cursor: help'>". $contato_estado."</acronym></td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap><acronym title='Produto: $produto_referencia - ' style='cursor: help'>". $produto_referencia ."</acronym></td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap><acronym title='Produto: $produto_referencia - $produto_descricao' style='cursor: help'>". $produto_descricao ."</acronym></td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap>". $produto_serie."</td>";
			echo "<td style='font-size: 9px; font-family: verdana' nowrap>".$status_descricao. "</td>";
			if(strlen($admin)==0){
				echo "<td style='font-size: 9px; font-family: verdana' nowrap>&nbsp;</td>";
			}else{
					$sql_login = "select login from tbl_admin where admin = $admin";
					$res_login = @pg_exec($con,$sql_login);
					$login_status = @pg_result($res_login,0,login);
					echo "<td style='font-size: 9px; font-family: verdana' nowrap>$login_status</td>";
			}
			if ($login_fabrica == 30){
				echo "<td style='font-size: 9px; font-family: verdana'>".$data_status. "</td>";
			}
			echo "<td style='font-size: 9px; font-family: verdana' nowrap>".$status_observacao."</td>";
//			echo "<td style='font-size: 9px; font-family: verdana' nowrap><acronym title='Observação da Aprovação: ".$status_observacao."'>".$status_descricao. "</acronym></td>";
			#echo "<td style='font-size: 9px; font-family: verdana'><a href=\"javascript: abreObs('$os','$codigo_posto','$sua_os')\">VER</a></td>";
			if ($login_fabrica == 50) {
				$sqlint = "SELECT os_interacao,admin from tbl_os_interacao
				WHERE os = $os
				ORDER BY os_interacao DESC limit 1";
				$resint = pg_exec($con,$sqlint);
				if(pg_num_rows($resint)==0) {
					$botao = "<img src='imagens/btn_interagir_azul.gif' title='Enviar Interação com Posto'>";
				} else {
					$admin = pg_result($resint,0,admin);
					if (strlen($admin)>0) {
						$botao = "<img src='imagens/btn_interagir_amarelo.gif' title='Aguardando Resposta do Posto'>";
					}
					else {
						$botao = "<img src='imagens/btn_interagir_verde.gif' title='Posto Respondeu, clique aqui para visualizar'>";
					}
				}
			echo "<td><font color='#FFFFFF'><div id=btn_interacao_".$x."><B><font color='blue'><a href='#' onclick='abreInteracao($x,$os,\"Mostrar\")'>$botao</a></font></B></font></div></td>";
			}
			echo "</tr>";
			if ($login_fabrica == 50 ) {
			echo "<tr>";
			echo "<td colspan=9><div id='interacao_".$x."'></div></td>";
			echo "</tr>";
			}
		}
		echo "<input type='hidden' name='qtde_os' value='$x'>";
		echo "<tr>";
		echo "<td height='20' bgcolor='#485989' colspan='100%' align='left'> ";
		if(trim($aprova) == 'aprovacao'){
			echo "&nbsp;&nbsp;&nbsp;&nbsp;<img border='0' src='imagens/seta_checkbox.gif' align='absmiddle'> &nbsp; <font color='#FFFFFF'><B>COM MARCADOS:</B></font> &nbsp;";
			echo "<select name='select_acao' size='1' class='frm' >";
			echo "<option value=''></option>";
			echo "<option value='103'";  if ($_POST["select_acao"] == "103")  echo " selected"; echo ">APROVAR SÉRIE</option>";
			echo "<option value='104'";  if ($_POST["select_acao"] == "104")  echo " selected"; echo ">REPROVAR SÉRIE</option>";
			echo "</select>";
			echo "&nbsp;&nbsp; <font color='#FFFFFF'><b>Motivo:<b></font> <input class='frm' type='text' name='observacao' id='observacao' size='30' maxlength='250' value=''  "; if ($_POST["select_acao"] == "19") echo " DISABLED "; echo ">";
			echo "&nbsp;&nbsp;<img src='imagens/btn_gravar.gif' style='cursor:pointer' onclick='javascript: document.frm_pesquisa2.submit()' style='cursor: hand;' border='0'></td>";
		}
		echo "<input type='hidden' name='btn_acao' value='Pesquisar'>";
		echo "</table>";
		echo "<p>TOTAL OS: $total_os</p>";
		echo "</form>";
	}else{ 
		echo "<center>Nenhum OS encontrada.</center>";
	}
	$msg_erro = '';
}
include "rodape.php" ?>