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

$btn_acao    = trim($_POST["btn_acao"]);
$select_acao = trim($_POST["select_acao"]);


if(strlen($btn_acao)>0 AND strlen($select_acao)>0){

	$qtde_os     = trim($_POST["qtde_os"]);
	$observacao  = trim($_POST["observacao"]);

	if($select_acao == "13" AND strlen($observacao) == 0){
		$msg_erro .= "Informe o motivo da reprovação da OS.<br>";
	}

	if($select_acao == "19" AND strlen($observacao) == 0){
		$msg_erro .= "Informe o motivo da aprovação da OS.<br>";
	}

	if(strlen($observacao) > 0){
		$observacao = " Observação: $observacao ";
	}

	if (strlen($qtde_os)==0){
		$qtde_os = 0;
	}

	for ($x=0;$x<$qtde_os;$x++){

		$xxos         = trim($_POST["check_".$x]);

		if (strlen($xxos) > 0 AND strlen($msg_erro) == 0){

			$res_os = pg_exec($con,"BEGIN TRANSACTION");

			$sql = "SELECT status_os
					FROM tbl_os_status
					WHERE status_os = 127
					AND os = $xxos
					ORDER BY data DESC
					LIMIT 1";
			$res_os = pg_exec($con,$sql);
			if (pg_numrows($res_os)>0){
				if($select_acao == "19"){
					$sql = "INSERT INTO tbl_os_status
							(os,status_os,data,observacao,admin)
							VALUES ($xxos,19,current_timestamp,'$observacao',$login_admin)";
					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}

				if($select_acao == "13"){
					$sql = "INSERT INTO tbl_os_status
							(os,status_os,data,observacao,admin)
							VALUES ($xxos,13,current_timestamp,'$observacao',$login_admin)";
					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);

					$sqlx = "UPDATE tbl_os SET excluida = 't' WHERE os = $xxos";
					$resx = pg_exec($con, $sqlx);
					$msg_erro .= pg_errormessage($con);
					
					#158147 Paulo/Waldir desmarcar se for reincidente
					$sql = "SELECT fn_os_excluida_reincidente($xxos,$login_fabrica)";
					$res = pg_exec($con, $sql);

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

$layout_menu = "auditoria";
$title = "Auditoria de OSs com 4 peças";

include "cabecalho.php";
?>

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

<?
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
		$aprovacao = "127";
	}elseif($aprova=="aprovacao"){
		$aprovacao = "127";
	}elseif($aprova=="aprovadas"){
		$aprovacao = "19";
	}elseif($aprova=="reprovadas"){
		$aprovacao = "13";
	}

	$sql_tipo = "13,19,127";

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

<TABLE width="700" align="center" border="0" cellspacing='0' cellpadding='0' class='PesquisaTabela'>

<caption>Auditoria de OS com 4 peças</caption>

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
	$posto_codigo= trim($_POST["posto_codigo"]);

	if(strlen($posto_codigo)>0)         $sql_add .= " AND tbl_posto_fabrica.codigo_posto = '$posto_codigo' ";

	$sql =  "SELECT interv.os
			INTO TEMP tmp_interv_$login_admin
			FROM (
			SELECT
			ultima.os,
			(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND status_os IN ($sql_tipo) AND tbl_os_status.os = ultima.os ORDER BY data DESC LIMIT 1) AS ultimo_status
			FROM (SELECT DISTINCT os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND status_os IN ($sql_tipo) ) ultima
			) interv
			WHERE interv.ultimo_status IN ($aprovacao)
			$Xos
			;

			CREATE INDEX tmp_interv_OS_$login_admin ON tmp_interv_$login_admin(os);


			SELECT	tbl_os.os                                                   ,
					tbl_os.sua_os                                               ,
					tbl_os.consumidor_nome                                      ,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')  AS data_abertura,
					TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
					tbl_os.fabrica                                              ,
					tbl_os.consumidor_nome                                      ,
					tbl_posto.nome                     AS posto_nome            ,
					tbl_posto_fabrica.codigo_posto                              ,
					tbl_posto_fabrica.contato_email       AS posto_email        ,
					tbl_produto.referencia             AS produto_referencia    ,
					tbl_produto.descricao              AS produto_descricao     ,
					tbl_produto.voltagem                                        ,
					(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND tbl_os.os = tbl_os_status.os AND status_os IN ($sql_tipo) ORDER BY data DESC LIMIT 1) AS status_os         ,
					(SELECT observacao FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND tbl_os.os = tbl_os_status.os AND status_os IN ($sql_tipo) ORDER BY data DESC LIMIT 1) AS status_observacao,
					(SELECT tbl_status_os.descricao FROM tbl_os_status JOIN tbl_status_os USING(status_os) WHERE tbl_os_status.fabrica_status = $login_fabrica AND tbl_os.os = tbl_os_status.os AND status_os IN ($sql_tipo) ORDER BY data DESC LIMIT 1) AS status_descricao
				FROM tmp_interv_$login_admin X
				JOIN tbl_os ON tbl_os.os = X.os
				JOIN tbl_os_extra             ON tbl_os_extra.os     = tbl_os.os
				JOIN tbl_produto              ON tbl_produto.produto = tbl_os.produto
				JOIN tbl_posto                ON tbl_os.posto        = tbl_posto.posto
				JOIN tbl_posto_fabrica        ON tbl_posto.posto     = tbl_posto_fabrica.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
						$sql_add
				WHERE tbl_os.fabrica = $login_fabrica AND tbl_os_extra.extrato IS NULL
				";
	if (strlen($xdata_inicial) > 0 AND strlen($xdata_final) > 0) {
		$sql .= " AND tbl_os.data_digitacao BETWEEN '$xdata_inicial' AND '$xdata_final'
				 ";
	}
		$sql.="ORDER BY tbl_posto_fabrica.codigo_posto,tbl_os.os";

	$res = pg_exec($con,$sql);

	if(pg_numrows($res)>0){

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
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Email</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Produto</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Descrição</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>STATUS</B></font></td>";
		echo "</tr>";

		$cores = '';
		$qtde_intervencao = 0;

		for ($x=0; $x<pg_numrows($res);$x++){

			$os						= pg_result($res, $x, os);
			$sua_os					= pg_result($res, $x, sua_os);
			$codigo_posto			= pg_result($res, $x, codigo_posto);
			$posto_nome				= pg_result($res, $x, posto_nome);
			$posto_email			= pg_result($res, $x, posto_email);
			$produto_referencia		= pg_result($res, $x, produto_referencia);
			$produto_descricao		= pg_result($res, $x, produto_descricao);
			$produto_voltagem		= pg_result($res, $x, voltagem);
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
				if($status_os==127){
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
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap title='".$codigo_posto." - ".$posto_nome."'>".$codigo_posto." - ".substr($posto_nome,0,20) ."...</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap ><a href='mailto:$posto_email'>$posto_email</a></td>";


			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap><acronym title='Produto: $produto_referencia' style='cursor: help'>". $produto_referencia ."</acronym></td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap><acronym title='Produto: $produto_referencia - $produto_descricao' style='cursor: help'>". $produto_descricao ."</acronym></td>";
			echo "<td style='font-size: 9px; font-family: verdana' nowrap><acronym title='Observação: ".$status_observacao."'>".$status_descricao. "</acronym></td>";
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
			echo "<option value='13'";  if ($_POST["select_acao"] == "13")  echo " selected"; echo ">RECUSADO</option>";
			echo "</select>";
			echo "&nbsp;&nbsp; <font color='#FFFFFF'><b>Motivo:<b></font> <input class='frm' type='text' name='observacao' id='observacao' size='30' maxlength='250' value='' >";
			echo "&nbsp;&nbsp;<img src='imagens/btn_gravar.gif' style='cursor:pointer' onclick='javascript: document.frm_pesquisa2.submit()' style='cursor: hand;' border='0'></td>";
		}
		echo "<input type='hidden' name='btn_acao' value='Pesquisar'>";
		echo "</table>";
		echo "</form>";
	}else{
		echo "<center>Nenhum OS encontrada.</center>";
	}
	$msg_erro = '';
}

include "rodape.php" ?>
