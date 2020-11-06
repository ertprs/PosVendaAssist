<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="auditoria";
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

		$res = pg_query($con,$sql);
		if (pg_num_rows ($res) > 0) {
			for ($i=0; $i<pg_num_rows ($res); $i++ ){
				$cnpj         = trim(pg_fetch_result($res,$i,cnpj));
				$nome         = trim(pg_fetch_result($res,$i,nome));
				$codigo_posto = trim(pg_fetch_result($res,$i,codigo_posto));
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
$status_reinc =($login_fabrica == 30) ? " 67,132,19" : (($login_fabrica ==24) ? "67,70,19" : "");

if(strlen($btn_acao)>0 AND strlen($select_acao)>0){

	$qtde_os     = trim($_POST["qtde_os"]);
	$observacao  = trim($_POST["observacao"]);

	if(strlen($observacao) > 0){
		$observacao = " Observação: $observacao ";
	}else{
		if($select_acao == 15) {
			$msg_erro = "Informe a justificativa da recusa";
		}else{
			$observacao = " ";
		}
	}

	if (strlen($qtde_os)==0){
		$qtde_os = 0;
	}
	

	if (strlen($msg_erro)==0){

		for ($x=0;$x<$qtde_os;$x++){

			$xxos         = trim($_POST["check_".$x]);

			if (strlen($xxos) > 0 AND strlen($msg_erro) == 0){
				
				$res_os = pg_query($con,"BEGIN TRANSACTION");

				$sql = "SELECT status_os
						FROM tbl_os_status
						WHERE status_os IN ($status_reinc)
						AND os = $xxos
						ORDER BY data DESC 
						LIMIT 1";
				$res_os = pg_query($con,$sql);
				if (pg_num_rows($res_os)>0){

					$status_da_os = trim(pg_fetch_result($res_os,0,status_os));
					if (in_array($status_da_os,array(132,67,70))){ 

						/* Aprovar */
						if($select_acao == "19"){
							$sql = "INSERT INTO tbl_os_status
									(os,status_os,data,observacao,admin) 
									VALUES ($xxos,19,current_timestamp,'$observacao',$login_admin)";
							$res = pg_query($con,$sql);
							$msg_erro .= pg_last_error($con);
						}

						/* Recusar */
						if($select_acao == "15"){
							$sql = "INSERT INTO tbl_os_status
									(os,status_os,data,observacao,admin) 
									VALUES ($xxos,15,current_timestamp,'$observacao',$login_admin)";
							$res = pg_query($con,$sql);
							$msg_erro .= pg_last_error($con);

							$sql = "SELECT fn_os_excluida($xxos,$login_fabrica,$login_admin);";
							$res = @pg_query ($con,$sql);
							$msg_erro = pg_last_error($con);
						}
					}
				}
				if (strlen($msg_erro)==0){
					$res = pg_query($con,"COMMIT TRANSACTION");
				}else{
					$res = pg_query($con,"ROLLBACK TRANSACTION");
				}
			}
		}
	}
}

$layout_menu = "auditoria";

$title = ($login_fabrica == 30) ? "Aprovação de Reincidência de NF" : "Aprovação de Reincidência";

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


<? include "javascript_pesquisas.php"; 

if($btn_acao == 'Pesquisar'){

	$data_inicial = trim($_POST['data_inicial']);
	$data_final   = trim($_POST['data_final']);
	$aprova       = trim($_POST['aprova']);
	$os           = trim($_POST['os']);

	if (strlen($os)>0){
		$Xos = " AND os = $os ";
	}
	
	if(strlen($aprova) == 0){
		$aprova = "aprovacao";
		$aprovacao = ($login_fabrica == 30) ? "67,132" : (($login_fabrica == 24) ? "67,70" : "");
	}elseif($aprova=="aprovacao"){
		$aprovacao = ($login_fabrica == 30) ? "67,132" : (($login_fabrica == 24) ? "67,70" : "");
	}elseif($aprova=="aprovadas"){
		$aprovacao = "19";
	} elseif($aprova=="reprovadas"){
		$aprovacao = "15";
	}

	if ($login_fabrica == 30 and $aprovacao=="15") {
		$status_reinc = "67,132,19,15";
	}

	if (strlen($data_inicial) > 0) {
		$xdata_inicial = formata_data ($data_inicial);
		$xdata_inicial = $xdata_inicial." 00:00:00";
	}

	if (strlen($data_final) > 0) {
		$xdata_final = formata_data ($data_final);
		$xdata_final = $xdata_final." 23:59:59";
	}
}

if(strlen($msg_erro) > 0){
	echo "<p align='center' style='font-size: 14px; font-family: verdana;'><FONT COLOR='#FF0000'><b>$msg_erro</FONT></b></p>";
}

?>

<form name="frm_pesquisa" method="post" action="<?echo $PHP_SELF?>">

<TABLE width="450" align="center" border="0" cellspacing='0' cellpadding='0' class='PesquisaTabela'>

<caption><?=$title?></caption>

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
<TR>
	<td colspan='100%' align='left '>
		Estado<br><select name="estado" id='estado'  class="frm">
					<? $ArrayEstados = array('','AC','AL','AM','AP',
												'BA','CE','DF','ES',
												'GO','MA','MG','MS',
												'MT','PA','PB','PE',
												'PI','PR','RJ','RN',
												'RO','RR','RS','SC',
												'SE','SP','TO'
											);
					for ($i=0; $i<=27; $i++){
						echo"<option value='".$ArrayEstados[$i]."'";
						if ($estado == $ArrayEstados[$i]) echo " selected";
						echo ">".$ArrayEstados[$i]."</option>\n";
					}?>
		</select>
	</td>
</tr>
<tr>
	<td colspan='2'>
		<b>Mostrar as OS:</b><br>

			<INPUT TYPE="radio" NAME="aprova" value='aprovacao' <? if(trim($aprova) == 'aprovacao' OR trim($aprova)==0) echo "checked='checked'"; ?>>Em aprovação &nbsp;&nbsp;&nbsp;
			<INPUT TYPE="radio" NAME="aprova" value='aprovadas' <? if(trim($aprova) == 'aprovadas') echo "checked='checked'"; ?>>Aprovadas  &nbsp;&nbsp;&nbsp;<INPUT TYPE="radio" NAME="aprova" value='reprovadas' <? if(trim($aprova) == 'reprovadas') echo "checked='checked'"; ?>>Reprovadas  &nbsp;&nbsp;&nbsp;
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

	$posto_codigo= trim($_POST["posto_codigo"]);
	$estado      = trim($_POST["estado"]);

	if(strlen($posto_codigo)>0)         $sql_add .= " AND tbl_posto_fabrica.codigo_posto = '$posto_codigo' ";
	if(strlen($estado)>0)         $sql_add2 .= " AND tbl_posto_fabrica.contato_estado = '$estado' ";
	
	if ($aprovacao == '15') 
	{
		$cond_fabrica = " (tbl_os.fabrica = $login_fabrica or tbl_os.fabrica = 0) ";
	}
	else 
	{
		$cond_fabrica = " tbl_os.fabrica = $login_fabrica ";
	}
	
	#HD 407384 - INICIO
	
	$sql_data_a = ( $xdata_inicial or ( $xdata_inicial and $xdata_final ) and empty($msg_erro) ) ? " AND tbl_os_status.data>='$xdata_inicial' " : null;
	
	$sql_os_a   = ($_POST['os']) ? " AND tbl_os_status.os=$os " : null ; 
	#HD 407384 - FIM
	#######################################################################################
	#                                    HD 407384                                        #
	#																					  #
	# ATENÇÃO: não acrescentar campos na cláusula SELECT, pois irá interferir no          #
	# resultado, pois estamos usando GROUP BY. Não acrescentar filtros de status          #
	# nesta consulta.                                                                     #
	#																					  $
	#######################################################################################
	$sql =  "	SELECT
				tbl_os_status.os,
				MAX(tbl_os_status.os_status) AS os_status

				INTO TEMP
				tmp_inverv_os_$login_admin

				FROM
				tbl_os_status

				WHERE
				tbl_os_status.fabrica_status=$login_fabrica
				AND tbl_os_status.status_os IN ($status_reinc)
				$sql_data_a 
				$sql_os_a
				
				GROUP BY
				tbl_os_status.os;

			CREATE INDEX tmp_inverv_os_".$login_admin."_os ON tmp_inverv_os_".$login_admin."(os);
			CREATE INDEX tmp_inverv_os_".$login_admin."_os_status ON tmp_inverv_os_".$login_admin."(os_status);
	
			SELECT
			tbl_os.os                                                     		,
			tbl_os.sua_os                                                 		,
			TO_CHAR(tbl_os_status.data,'DD/MM/YYYY')    AS data_status    		,
			TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')  AS data_abertura 		,
			TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao		,
			tbl_os.consumidor_nome                                        		,
			tbl_os.obs_reincidencia                                       		,
			tbl_posto.nome AS posto_nome 								  		,
			tbl_posto_fabrica.codigo_posto 								  		,
			tbl_admin.nome_completo 									  		,
			tbl_posto_fabrica.contato_estado 							  		,	
			tbl_produto.referencia 						AS produto_referencia 	,
			tbl_produto.descricao 						AS produto_descricao	,
			tbl_produto.voltagem 												,
			tbl_os_status.status_os												,
			tbl_os_status.observacao 					AS status_observacao    ,
			tbl_status_os.descricao 					AS status_descricao

			FROM
			tmp_inverv_os_".$login_admin."
			
			JOIN tbl_os_status ON tmp_inverv_os_".$login_admin.".os_status=tbl_os_status.os_status
			JOIN tbl_status_os ON tbl_os_status.status_os=tbl_status_os.status_os
			JOIN tbl_os ON tbl_os_status.os = tbl_os.os
			LEFT JOIN tbl_admin ON tbl_os_status.admin = tbl_admin.admin
			JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
			JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
			JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = tbl_os.fabrica

			WHERE
			$cond_fabrica 
			AND tbl_os_status.status_os IN (".$aprovacao.") 
			$sql_add			
			$sql_add2  ";
	
	if($login_fabrica == 24) { # HD 158379
			$sql.=" AND tbl_os.data_digitacao > '2009-10-14 00:00:00'";
	}else if (strlen($xdata_inicial) > 0 AND strlen($xdata_final) > 0) {
		$sql .= " AND tbl_os.data_digitacao BETWEEN '$xdata_inicial' AND '$xdata_final' ";
	}

//	echo "<br />". nl2br($sql) .  "<br />";
	
	$res = pg_query($con,$sql);
		if(pg_num_rows($res)>0){

		echo "<BR><BR><FORM name='frm_pesquisa2' METHOD='POST' ACTION='$PHP_SELF'>";
		
		echo "<input type='hidden' name='data_inicial'   value='$data_inicial'>";
		echo "<input type='hidden' name='data_final'     value='$data_final'>";
		echo "<input type='hidden' name='aprova'         value='$aprova'>";

		echo "<table width='98%' border='0' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 11px' bgcolor='#FFFFFF'>";
		echo "<tr>";
		echo "<td bgcolor='#485989'><img border='0' src='imagens_admin/selecione_todas.gif' onclick='javascript: checkaTodos()' alt='Selecionar todos' style='cursor: hand;' align='center'></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>OS</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>DATA <br>DIGITAÇÃO</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Posto</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Produto</B></font></td>";
		if ($aprovacao =='15' or $aprovacao == '19') {
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>ADMIN</B></font></td>";
		}
		if ($login_fabrica == 30 and $aprovacao != '15' and ($aprovacao == '19' or $aprovacao = '67' or $aprovacao = '132')){
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>DATA <br>Aprovação</B></font></td>";
		}
		if ($login_fabrica == 30 and $aprovacao == '15'){
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>DATA <br>Recusada</B></font></td>";
		}
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>STATUS</B></font></td>";
		if ($login_fabrica == 30) {
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>OBSERVAÇÃO</B></font></td>";
		}
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Justificativa</B></font></td>";
		echo "</tr>";

		$cores = '';
		$qtde_intervencao = 0;

		for ($x=0; $x<pg_num_rows($res);$x++){

			$os						= pg_fetch_result($res, $x, os);
			$sua_os					= pg_fetch_result($res, $x, sua_os);
			$data_digitacao			= pg_fetch_result($res, $x, data_digitacao);
			$data_status			= pg_fetch_result($res, $x, data_status);
			$data_abertura			= pg_fetch_result($res, $x, data_abertura);
			$consumidor_nome		= pg_fetch_result($res, $x, consumidor_nome);
			$posto_nome				= pg_fetch_result($res, $x, posto_nome);
			$codigo_posto			= pg_fetch_result($res, $x, codigo_posto);
			$produto_referencia		= pg_fetch_result($res, $x, produto_referencia);
			$produto_descricao		= pg_fetch_result($res, $x, produto_descricao);
			$produto_voltagem		= pg_fetch_result($res, $x, voltagem);
			if ($aprovacao == '15' or $aprovacao == '19') {
				$nome_completo			= pg_fetch_result($res, $x, nome_completo);
			}
			
			$status_os				= pg_fetch_result($res, $x, status_os);
			$status_observacao		= str_replace("'","",pg_fetch_result($res, $x, status_observacao));
			$status_descricao		= pg_fetch_result($res, $x, status_descricao);
			$obs_reincidencia		= pg_fetch_result($res, $x, obs_reincidencia);

			$cores++;
			$cor = ($cores % 2 == 0) ? "#FEFEFE": '#E8EBEE';

			if(strlen($sua_os)==o){
				$sua_os=$os;
			}

			echo "<tr bgcolor='$cor' id='linha_$x'>";
			echo "<td align='center' width='0'>";
				if(in_array($status_os,array(132,67,70))){
					echo "<input type='checkbox' name='check_$x' id='check_$x' value='$os' onclick=\"setCheck('check_$x','linha_$x','$cor');\" ";
					if (strlen($msg_erro)>0){
						if (strlen($_POST["check_".$x])>0){
							echo " CHECKED ";
						}
					}
					echo ">";
				}
			echo "</td>";
			echo "<td style='font-size: 9px; font-family: verdana' nowrap ><a href='os_press.php?os=$os'  target='_blank'>".$sua_os."</a></td>";
			echo "<td style='font-size: 9px; font-family: verdana'>".$data_digitacao. "</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap title='".$codigo_posto." - ".$posto_nome."'>".$codigo_posto." - ".substr($posto_nome,0,20) ."...</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap><acronym title='Produto: $produto_referencia - $produto_descricao' style='cursor: help'>". $produto_referencia ." - ". substr($produto_descricao,0,30) ."</acronym></td>";
			if ($aprovacao == '15' or $aprovacao == '19') {
				echo "<td>$nome_completo</td>";
			}
			if ($login_fabrica == 30){
				echo "<td style='font-size: 9px; font-family: verdana'>".$data_status. "</td>";
			}
			echo "<td style='font-size: 9px; font-family: verdana' nowrap><acronym title='Observação: ".$status_observacao."'>".$status_descricao. "</acronym></td>";
			if ($login_fabrica == 30) {
				echo "<td style='font-size: 9px; font-family: verdana' nowrap>".$status_observacao. "</td>";
			}
			echo "<td style='font-size: 9px; font-family: verdana' nowrap><acronym title='Observação: ".$obs_reincidencia."'>".substr($obs_reincidencia,0,30). "</acronym></td>";
			echo "</tr>";
		}

		echo "<input type='hidden' name='qtde_os' value='$x'>";
		echo "<tr>";
		echo "<td height='20' bgcolor='#485989' colspan='100%' align='left'> ";
		if(trim($aprova) == 'aprovacao'){
			echo "&nbsp;&nbsp;&nbsp;&nbsp;<img border='0' src='imagens/seta_checkbox.gif' align='absmiddle'> &nbsp; <font color='#FFFFFF'><B>COM MARCADOS:</B></font> &nbsp;";
			echo "<select name='select_acao' size='1' class='frm' >";
			echo "<option value=''></option>";
			echo "<option value='19'";  if ($_POST["select_acao"] == "19")  echo " selected"; echo ">APROVADO</option>";
			echo "<option value='15'";  if ($_POST["select_acao"] == "15")  echo " selected"; echo ">RECUSADO</option>";
			echo "</select>";
			echo "&nbsp;&nbsp; <font color='#FFFFFF'><b>Motivo:<b></font> <input class='frm' type='text' name='observacao' id='observacao' size='30' maxlength='250' value='' >";
			echo "&nbsp;&nbsp;<img src='imagens/btn_gravar.gif' style='cursor:pointer' onclick='javascript: document.frm_pesquisa2.submit()' style='cursor: hand;' border='0'>";
			echo "</td>";
		}
		echo "<input type='hidden' name='btn_acao' value='Pesquisar'>";
		echo "</table>";
		echo "</form>";
	}else{ 
		echo "<center>Nenhuma OS encontrada.</center>";
	}
	$msg_erro = '';
}

include "rodape.php" ?>
