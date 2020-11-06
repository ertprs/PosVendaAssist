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


$os   = $_GET["hd_chamado"];
if (strlen($_POST["hd_chamado"]) > 0){
	$hd_chamado = $_POST["hd_chamado"];
}

$tipo = $_GET["tipo"];

$btn_acao    = trim($_POST["btn_acao"]);
$select_acao = trim($_POST["select_acao"]);

if(strlen($btn_acao)>0 AND strlen($select_acao)>0){
	
	$qtde_chamado     = trim($_POST["qtde_chamado"]);

	if (strlen($qtde_chamado)==0){
		$qtde_chamado = 0;
	}

	for ($x=0;$x<$qtde_chamado;$x++){

		$xx_chamado = trim($_POST["check_".$x]);


		if (strlen($xx_chamado) > 0 AND strlen($msg_erro) == 0){

			$res = pg_exec($con,"BEGIN TRANSACTION");
			
			if (strlen($xx_chamado) > 0) {
			
				$sql = "UPDATE tbl_hd_chamado_extra SET abre_os = null
									WHERE hd_chamado = $xx_chamado ";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
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

$layout_menu = "callcenter";
$title = "Cancelar Pré-OS";

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

<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

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
<? include "javascript_pesquisas.php"; 

if($btn_acao == 'Pesquisar'){

	$data_inicial = trim($_POST['data_inicial']);
	$data_final   = trim($_POST['data_final']);
	$hd_chamado   = trim($_POST['hd_chamado']);
	$codigo_posto = trim($_POST['posto_codigo']);
	if (strlen($data_inicial) > 0) {
		$xdata_inicial = formata_data ($data_inicial);
		$xdata_inicial = $xdata_inicial." 00:00:00";
	}

	if (strlen($data_final) > 0) {
		$xdata_final = formata_data ($data_final);
		$xdata_final = $xdata_final." 23:59:59";
	}
	if (strlen($codigo_posto) > 0) {
		$sql = "";
	}


}

if(strlen($msg_erro) > 0){
	echo "<p align='center' style='font-size: 14px; font-family: verdana;'><FONT COLOR='#FF0000'><b>$msg_erro</FONT></b></p>";
}

?>

<form name="frm_pesquisa" method="post" action="<?echo $PHP_SELF?>">

<TABLE width="450" align="center" border="0" cellspacing='0' cellpadding='0' class='PesquisaTabela'>

<caption>Parâmetros para pesquisa</caption>

<TBODY>
<TR>
	<TD colspan="2">Chamado<br><input type="text" name="hd_chamado" id="hd_chamado" size="10" maxlength="10" value="<? echo $hd_chamado ?>" class="frm"></TD>

</TR>
<TR>

	<TD>Data Inicial<br><input type="text" name="data_inicial" id="data_inicial" size="11" maxlength="10" value="<? echo $data_inicial ?>" class="frm"></TD>
	<TD>Data Final<br><input type="text" name="data_final" id="data_final" size="11" maxlength="10" value="<? echo $data_final ?>" class="frm"></TD>
</TR>
<TR>

	<TD>Código Posto<br><input type="text" name="posto_codigo" id="posto_codigo" size="15"  value="<? echo $posto_codigo ?>" class="frm"><img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.posto_codigo, document.frm_pesquisa.posto_nome, 'codigo')"></TD>
	<TD>Nome do Posto<br><input type="text" name="posto_nome" id="posto_nome" size="40"  value="<? echo $posto_nome ?>" class="frm"><img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.posto_codigo, document.frm_pesquisa.posto_nome, 'nome')"></TD>
</TR>
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
if (strlen($btn_acao)  > 0) {

			$sql =  " SELECT tbl_hd_chamado.hd_chamado,
							  to_char(tbl_hd_chamado.data,'dd/mm/yyyy') as data,
							  tbl_posto.nome AS posto_nome,
							  tbl_posto_fabrica.codigo_posto               ,
							  tbl_hd_chamado_extra.nome AS consumidor_nome,
							  tbl_produto.referencia                      ,
							  tbl_produto.descricao                       
						FROM tbl_hd_chamado
						JOIN tbl_hd_chamado_extra USING(hd_chamado)
						JOIN tbl_posto ON tbl_hd_chamado_extra.posto = tbl_posto.posto
						JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
						JOIN tbl_produto USING (produto)
						WHERE tbl_hd_chamado.fabrica = $login_fabrica
						AND   tbl_hd_chamado_extra.abre_os IS TRUE
						AND   tbl_hd_chamado_extra.os IS NULL";

			if(strlen($codigo_posto)>0){
				$sql .= " AND tbl_posto_fabrica.codigo_posto = '$codigo_posto' ";
			}
			if(strlen($hd_chamado)>0){
				$sql .= " AND tbl_hd_chamado.hd_chamado = $hd_chamado ";
			}

			if (strlen($xdata_inicial) > 0 AND strlen($xdata_final) > 0) {
				$sql .= " AND tbl_hd_chamado.data BETWEEN '$xdata_inicial' AND '$xdata_final'";
			}
			$sql .=" ORDER BY tbl_hd_chamado.hd_chamado";
			$res = pg_exec($con,$sql);

			
	if(pg_numrows($res)>0){

		echo "<BR><BR><FORM name='frm_pesquisa2' METHOD='POST' ACTION='$PHP_SELF'>";
		echo "<table width='800' border='0' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 11px' bgcolor='#FFFFFF'>";
		echo "<tr>";
		echo "<td bgcolor='#485989'><img border='0' src='imagens_admin/selecione_todas.gif' onclick='javascript: checkaTodos()' alt='Selecionar todos' style='cursor: hand;' align='center'></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Chamado</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Data </B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Posto</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Consumidor</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Produto</B></font></td>";
		echo "</tr>";

		for ($x=0; $x<pg_numrows($res);$x++){

			$hd_chamado				= pg_result($res, $x, hd_chamado);
			$data					= pg_result($res, $x, data);
			$codigo_posto			= pg_result($res, $x, codigo_posto);
			$posto_nome				= pg_result($res, $x, posto_nome);
			$consumidor_nome		= pg_result($res, $x, consumidor_nome);
			$produto_referencia		= pg_result($res, $x, referencia);
			$produto_descricao		= pg_result($res, $x, descricao);

			$cores++;
			$cor = ($cores % 2 == 0) ? "#FEFEFE": '#E8EBEE';
			
			echo "<tr bgcolor='$cor' id='linha_$x'>";
			echo "<td align='center' width='0'>";
			echo "<input type='checkbox' name='check_$x' id='check_$x' value='$hd_chamado' onclick=\"setCheck('check_$x','linha_$x','$cor');\">";
			echo "</td>";
			echo "<td style='font-size: 9px; font-family: verdana'><a href='callcenter_interativo_new.php?callcenter=$hd_chamado' target='_blank'>".$hd_chamado. "</a></td>";
			echo "<td style='font-size: 9px; font-family: verdana'>".$data. "</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap title='".$codigo_posto." - ".$posto_nome."'>".$codigo_posto." - ".substr($posto_nome,0,20) ."</td>";
			echo "<td style='font-size: 9px; font-family: verdana'>".$consumidor_nome. "</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap><acronym title='Produto: $produto_referencia - ' style='cursor: help'>". $produto_referencia."-".$produto_descricao ."</acronym></td>";
			echo "</tr>";
		}
		
		echo "<input type='hidden' name='qtde_chamado' value='$x'>";
		echo "<tr>";
		echo "<td height='20' bgcolor='#485989' colspan='100%' align='left'> ";
		echo "&nbsp;&nbsp;&nbsp;&nbsp;<img border='0' src='imagens/seta_checkbox.gif' align='absmiddle'> &nbsp; <font color='#FFFFFF'><B>COM MARCADOS:</B></font> &nbsp;";
		echo "<select name='select_acao' size='1' class='frm' >";
		echo "<option value=''></option>";
		echo "<option value='cancelar'";  if ($_POST["select_acao"] == "cancelar")  echo " selected"; echo ">CANCELAR PRÉ-OS</option>";
		echo "</select>";
		echo "&nbsp;&nbsp; <font color='#FFFFFF'>";
		echo "&nbsp;&nbsp;<img src='imagens/btn_gravar.gif' style='cursor:pointer' onclick='javascript: document.frm_pesquisa2.submit()' style='cursor: hand;' border='0'>";
		echo "<input type='hidden' name='btn_acao' value='Pesquisar'></td>";
		echo "</tr>";
		echo "</table>";
		echo "</form>";
	}else{ 
		echo "<center>Nenhum OS encontrada.</center>";
	}
	$msg_erro = '';
}

include "rodape.php" ?>