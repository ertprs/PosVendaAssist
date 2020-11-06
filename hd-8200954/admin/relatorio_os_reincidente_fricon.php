<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="gerencia";
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



$layout_menu = "Gerencia";
$title = "Relatório de OSs reincidentes";

include "cabecalho.php";

?>
<link type="text/css" rel="stylesheet" href="css/'.css">
<style type="text/css" media="screen">


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


#tooltip{
                
	background: #FF9999;
	border:2px solid #000;
	display:none;
	padding: 2px 4px;
	color: #003399;
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




<script language="JavaScript" type="text/javascript">
	window.onload = function(){

		tooltip.init();

	}
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

<script language="JavaScript" src="js/qTip.js" type="text/JavaScript"></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
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

	function recusaFabricante(){
		var motivo =prompt("Qual o Motivo da Recusa da(s) OS(s)  ?",'',"Motivo da Recusa");
		if (motivo !=null && $.trim(motivo) !="" && motivo.length > 0 ){
				document.getElementById('motivo_recusa').value = motivo;
				document.frm_pesquisa2.submit();
		}else{
			alert('Digite um motivo por favor!','Erro');
		}
	}


</script>


<? include "javascript_pesquisas.php";

if($btn_acao == 'Pesquisar'){

	$data_inicial = trim($_POST['data_inicial']);
	$data_final   = trim($_POST['data_final']);
	$aprova       = trim($_POST['aprova']);
	$os           = trim($_POST['os']);
	$status_os    = trim($_POST['status_os']);

	if (strlen($os)>0){
		$Xos = " AND os = $os ";
	}

	if(strlen($aprova) == 0){
		$aprova = "aprovacao";
		switch($login_fabrica) {
			case ($login_fabrica ==14 or $login_fabrica ==11):
			$aprovacao = "67, 68,70";
			break;
			case 52:
			$aprovacao = "67,134";
			break;
			case 24: $aprovacao = "67, 68,70";
			break;

		}
		$sql_add2 = " AND tbl_os.excluida IS NOT TRUE ";
	}elseif($aprova=="aprovacao"){
		switch($login_fabrica) {
			case ($login_fabrica ==14 or $login_fabrica ==11):
			$aprovacao = "67, 68,70";
			break;
			case 52:
			$aprovacao = "67,134";
			break;
			case 24: $aprovacao = "67, 68,70";
			break;
		}
		$sql_add2 = " AND tbl_os.excluida IS NOT TRUE";
	}elseif($aprova=="aprovadas"){
		$aprovacao = "19";
		$sql_add2 = " AND tbl_os_status.extrato IS NULL  
		AND tbl_os.excluida IS NOT TRUE";
	}elseif($aprova=="reprovadas"){
		
		$aprovacao = ($login_fabrica ==11 or $login_fabrica == 24) ? "13,15	" : "131";

		$sql_add2 = " AND tbl_os_status.extrato IS NULL ";
	}
	
	if (strlen($status_os)>0) {
		$sql_tipo = $status_os;
	}

	else {
		switch($login_fabrica) {
			case ($login_fabrica ==14 or $login_fabrica ==11):
			$sql_tipo = "67, 68,70,131,19,13";
			break;
			case 52:
			$sql_tipo = "67, 134,135,13,19,131";
			break;
			case 24:
			$sql_tipo = "67, 68,70,131,19,13";
			break;
		}

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
<div id="page-container">
<form name="frm_pesquisa" method="post" action="<?echo $PHP_SELF?>">


<TABLE width="700" align="center" border="0" cellspacing='0' cellpadding='0' class='PesquisaTabela'>

<caption>Relatório de OS reincidente</caption>

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
	<TD colspan=4>Status<br>
		<select class='frm' name='status_os'>
			<option> </option>
			<?php 
				
				$sql = "select * from tbl_status_os where status_os in(67, 68,70,131,19,13)";
				$res = pg_exec($con,$sql);

				
				for ($i=0;$i<pg_numrows($res);$i++) {

					$status_os_x = pg_result($res,$i,status_os);
					$descricao = pg_result($res,$i,descricao);
					
					?>
					<option value="<? echo $status_os_x;?>" <? if ($status_os == $status_os_x) echo "SELECTED";?>><?php echo $descricao;?></option>
			<?
				}
			?>
	
		
		</select>
	</TD>
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

	if(strlen($posto_codigo)>0)     {
		$sql = "SELECT posto FROM tbl_posto_fabrica where codigo_posto = '$posto_codigo' and fabrica = $login_fabrica";
		$res = pg_exec($con,$sql);

		$posto = pg_result($res,0,0);

		$sql_add .= " AND tbl_os.posto = '$posto' ";
	}

	if($login_fabrica == 11) {
		$sql_data = " AND tbl_os.data_digitacao > '2009-10-01 00:00:00' ";
	}

	if (strlen($xdata_inicial) > 0 AND strlen($xdata_final) > 0) {
		$sql_data2 .= " AND tbl_os.data_digitacao BETWEEN '$xdata_inicial' AND '$xdata_final' ";

	}

	$sql =  "
			SELECT interv.os
			INTO TEMP tmp_interv_$login_admin
			FROM (
				SELECT	ultima.os,
						(	
							SELECT status_os 
							FROM tbl_os_status
							JOIN tbl_os USING(os)
							WHERE status_os IN ($sql_tipo) 
							AND tbl_os_status.os = ultima.os 
							AND tbl_os.fabrica = $login_fabrica
							AND tbl_os.os_reincidente IS TRUE
							$sql_add2
							$sql_add
							$sql_data
							$sql_data2
							ORDER BY os_status DESC 
							LIMIT 1
						) AS ultimo_status
				FROM (
						SELECT DISTINCT os 
						FROM tbl_os_status 
						JOIN tbl_os USING(os)
						WHERE status_os IN ($sql_tipo) 
						AND tbl_os.fabrica = $login_fabrica
						AND tbl_os.os_reincidente IS TRUE
						$sql_add
						$sql_data
						$sql_data2
				) ultima
			) interv
			WHERE interv.ultimo_status IN ($aprovacao)
			$Xos;

			CREATE INDEX tmp_interv_OS_$login_admin ON tmp_interv_$login_admin(os);


			SELECT	tbl_os.os                                                   ,
					tbl_os.serie                                                ,
					tbl_os.sua_os                                               ,
					tbl_os.consumidor_nome                                      ,
					tbl_os.revenda_nome                                         ,
					tbl_os.consumidor_revenda                                   ,
					tbl_os.consumidor_fone                                      ,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')  AS data_abertura,
					TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY')   AS data_fechamento,
					TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
					tbl_os.nota_fiscal                                          ,
					TO_CHAR(tbl_os.data_nf,'DD/MM/YYYY')              AS data_nf,
					tbl_os.fabrica                                              ,
					tbl_os.consumidor_nome                                      ,
					tbl_posto.nome                     AS posto_nome            ,
					tbl_posto_fabrica.codigo_posto                              ,
					tbl_posto_fabrica.contato_email       AS posto_email        ,
					tbl_produto.referencia             AS produto_referencia    ,
					tbl_produto.descricao              AS produto_descricao     ,
					tbl_produto.voltagem                                        ,
					tbl_motivo_reincidencia.descricao  AS motivo_reincidencia_desc ,
					tbl_os_extra.os_reincidente                                 ,
					(SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN ($sql_tipo) ORDER BY data DESC LIMIT 1) AS status_os         ,
					(SELECT observacao FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN ($sql_tipo) ORDER BY data DESC LIMIT 1) AS status_observacao,
					(SELECT tbl_status_os.descricao FROM tbl_os_status JOIN tbl_status_os USING(status_os) WHERE tbl_os.os = tbl_os_status.os AND status_os IN ($sql_tipo) ORDER BY data DESC LIMIT 1) AS status_descricao,
					tbl_os.obs_reincidencia                                    ,
					(SELECT descricao FROM tbl_defeito_constatado where tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado) as defeito_constatado,
					(SELECT descricao FROM tbl_defeito_reclamado where tbl_os.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado) as defeito_reclamado
				FROM tmp_interv_$login_admin X
				JOIN tbl_os                   ON tbl_os.os                  = X.os
				JOIN tbl_os_extra             ON tbl_os.os                  = tbl_os_extra.os
				JOIN tbl_produto              ON tbl_produto.produto        = tbl_os.produto
				JOIN tbl_posto                ON tbl_os.posto               = tbl_posto.posto
				LEFT JOIN tbl_motivo_reincidencia  ON tbl_os.motivo_reincidencia = tbl_motivo_reincidencia.motivo_reincidencia
				JOIN tbl_posto_fabrica        ON tbl_os.posto               = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE tbl_os.fabrica = $login_fabrica
				AND  tbl_os_extra.extrato IS NULL ";
				if ($login_fabrica == 14) {
					$sql .= "AND  tbl_os.data_fechamento IS NOT NULL";
				}
				$sql .= "$sql_add
						 $sql_data ";
				
	# ---- 2009-07-08 - Tulio - na Intelbras - Ramona pediu para que somente aparecessem para analise OS FECHADAS -------------


		$sql.=" ORDER BY tbl_posto.nome,status_observacao,tbl_os.os";

		$res = pg_exec($con,$sql);
	//echo nl2br($sql);
	if(pg_numrows($res)>0){

		echo "<BR><BR><FORM name='frm_pesquisa2' METHOD='POST' ACTION='$PHP_SELF'>";

		echo "<input type='hidden' name='data_inicial'   value='$data_inicial'>";
		echo "<input type='hidden' name='data_final'     value='$data_final'>";
		echo "<input type='hidden' name='aprova'         value='$aprova'>";
		echo "<input type='hidden' name='posto_codigo'     value='$posto_codigo'>";
		echo "<input type='hidden' name='posto_nome'     value='$posto_nome'>";

		echo "<table width='800' border='0' align='center' cellpadding='0' cellspacing='1' style='font-family: verdana; font-size: 11px' bgcolor='#FFFFFF'>";
		echo "<tr>";
		echo "<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>OS</B></font></td>";
		echo "<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>Série</B></font></td>";
		echo "<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>DATA <br>ABERTURA</B></font></td>";
		echo "<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>DATA <br>FECHAMENTO</B></font></td>";
		echo "<td bgcolor='#485989' width=20 style='font-size: 9px;'><font color='#FFFFFF'><B>Posto</B></font></td>";
		echo "<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>Nota Fiscal</B></font></td>";
		//echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Data <br>Compra</B></font></td>";
		echo "<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>Consum.</B></font></td>";
		//echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Fone</B></font></td>";
		echo "<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>Produto</B></font></td>";
		echo "<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>";
		echo ($login_fabrica == 11) ? "Defeito Reclamado" : "Defeito Constatado";
		echo "</B></font></td>";
		echo "<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>STATUS</B></font></td>";
		echo "<td bgcolor='#485989' style='font-size: 9px;' nowrap><font color='#FFFFFF'><B>Justificativa</B></font></td>";
		echo "<td bgcolor='#485989' style='font-size: 9px;' nowrap><font color='#FFFFFF'><B>Motivo Reinc.</B></font></td>";
		echo "</tr>";

		$cores = '';
		$qtde_intervencao = 0;

		for ($x=0; $x<pg_numrows($res);$x++){

			$os						= pg_result($res, $x, os);
			$serie					= pg_result($res, $x, serie);
			$data_abertura			= pg_result($res, $x, data_abertura);
			$data_fechamento		= pg_result($res, $x, data_fechamento);
			$sua_os					= pg_result($res, $x, sua_os);
			$codigo_posto			= pg_result($res, $x, codigo_posto);
			$posto_nome				= pg_result($res, $x, posto_nome);
			$posto_email			= pg_result($res, $x, posto_email);
			$nota_fiscal			= pg_result($res, $x, nota_fiscal);
			$data_nf				= pg_result($res, $x, data_nf);
			$consumidor_nome		= pg_result($res, $x, consumidor_nome);
			$consumidor_revenda     = pg_result($res, $x, consumidor_revenda);
			$revenda_nome           = pg_result($res, $x, revenda_nome);
			$consumidor_fone		= pg_result($res, $x, consumidor_fone);
			$produto_referencia		= pg_result($res, $x, produto_referencia);
			$produto_descricao		= pg_result($res, $x, produto_descricao);
			$produto_voltagem		= pg_result($res, $x, voltagem);
			$data_digitacao			= pg_result($res, $x, data_digitacao);
			$data_abertura			= pg_result($res, $x, data_abertura);
			$status_os				= pg_result($res, $x, status_os);
			$status_observacao		= pg_result($res, $x, status_observacao);
			$status_descricao		= pg_result($res, $x, status_descricao);
			$os_reincidente			= pg_result($res, $x, os_reincidente);
			$obs_reincidencia		= pg_result($res, $x, obs_reincidencia);
			$defeito_constatado		= pg_result($res, $x, defeito_constatado);
			$defeito_reclamado		= pg_result($res, $x, defeito_reclamado);
			if ($login_fabrica == 52){
				$motivo_reincidencia_desc = pg_result($res, $x, 'motivo_reincidencia_desc');
			}

			if(strlen($os_reincidente)>0){

				$sql =  "SELECT	tbl_os.os                                                   ,
								tbl_os.serie                                                ,

								tbl_os.sua_os                                               ,
								tbl_os.consumidor_nome                                      ,
								tbl_os.revenda_nome                                         ,
								tbl_os.consumidor_revenda                                   ,
								tbl_os.consumidor_fone                                      ,
								TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')  AS data_abertura,
								TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY')   AS data_fechamento,
								TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
								tbl_os.nota_fiscal                                          ,
								TO_CHAR(tbl_os.data_nf,'DD/MM/YYYY')              AS data_nf,
								tbl_os.fabrica                                              ,
								tbl_os.consumidor_nome                                      ,
								tbl_posto.nome                     AS posto_nome            ,
								tbl_posto_fabrica.codigo_posto                              ,
								tbl_posto_fabrica.contato_email       AS posto_email        ,
								tbl_produto.referencia             AS produto_referencia    ,
								tbl_produto.descricao              AS produto_descricao     ,
								tbl_produto.voltagem                                        ,
								tbl_os_extra.os_reincidente                                 ,
								(SELECT descricao FROM tbl_defeito_constatado where tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado) as defeito_constatado,
								(SELECT descricao FROM tbl_defeito_reclamado where tbl_os.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado) as defeito_reclamado
						FROM tbl_os                   
						JOIN tbl_os_extra             ON tbl_os.os = tbl_os_extra.os
						JOIN tbl_produto              ON tbl_produto.produto = tbl_os.produto
						JOIN tbl_posto                ON tbl_os.posto        = tbl_posto.posto
						JOIN tbl_posto_fabrica        ON tbl_os.posto     = tbl_posto_fabrica.posto
						AND tbl_posto_fabrica.fabrica = $login_fabrica
						WHERE tbl_os.os = $os_reincidente
						LIMIT 1";
				$res_reinc = pg_exec($con,$sql);
				if ($login_admin==1375) echo "<br>OS $os_reincidente, Núm. registros: ".pg_num_rows($res_reinc)."<br>";

				$reinc_os					= pg_result($res_reinc, 0, os);
				$reinc_serie				= pg_result($res_reinc, 0, serie);
				$reinc_data_abertura		= pg_result($res_reinc, 0, data_abertura);
				$reinc_data_fechamento		= pg_result($res_reinc, 0, data_fechamento);
				$reinc_sua_os				= pg_result($res_reinc, 0, sua_os);
				$reinc_codigo_posto			= pg_result($res_reinc, 0, codigo_posto);
				$reinc_posto_nome			= pg_result($res_reinc, 0, posto_nome);
				$reinc_posto_email			= pg_result($res_reinc, 0, posto_email);
				$reinc_nota_fiscal			= pg_result($res_reinc, 0, nota_fiscal);
				$reinc_data_nf				= pg_result($res_reinc, 0, data_nf);
				$reinc_consumidor_nome		= pg_result($res_reinc, 0, consumidor_nome);
				$reinc_revenda_nome		    = pg_result($res_reinc, 0, revenda_nome);
				$reinc_consumidor_revenda   = pg_result($res_reinc, 0, consumidor_revenda);
				$reinc_consumidor_fone		= pg_result($res_reinc, 0, consumidor_fone);
				$reinc_produto_referencia	= pg_result($res_reinc, 0, produto_referencia);
				$reinc_produto_descricao	= pg_result($res_reinc, 0, produto_descricao);
				$reinc_produto_voltagem		= pg_result($res_reinc, 0, voltagem);
				$reinc_data_digitacao		= pg_result($res_reinc, 0, data_digitacao);
				$reinc_data_abertura		= pg_result($res_reinc, 0, data_abertura);
				$reinc_defeito_constatado	= pg_result($res_reinc, 0, defeito_constatado);
				$reinc_defeito_reclamado	= pg_result($res_reinc, 0, defeito_reclamado);
			}

			$cores++;
			$cor = ($cores % 2 == 0) ? "#B1CED8": '#E8EBEE';

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

			if(strlen($sua_os)==o)$sua_os=$os;
			echo "<tr bgcolor='$cor' id='linha_$x'>";
			echo "<td style='font-size: 9px; font-weight: bold; font-family: verdana '><a href='os_press.php?os=$os'  target='_blank'>$sua_os</a> </td>";
			echo "<td style='font-size: 9px; font-weight: bold; font-family: verdana' >$serie</td>";
			echo "<td style='font-size: 9px; font-weight: bold; font-family: verdana' >$data_abertura</td>";
			echo "<td style='font-size: 9px; font-weight: bold; font-family: verdana' >$data_fechamento </td>";
			echo "<td style='font-size: 9px; font-weight: bold; font-family: verdana' align='left' title='".$codigo_posto." - ".$posto_nome."'>".substr($posto_nome,0,20) ."...</td>";
			echo "<td style='font-size: 9px; font-weight: bold; font-family: verdana' >$nota_fiscal</td>";
			//HD 119665
			if (strlen($consumidor_nome) == 0) $consumidor_nome = $revenda_nome;
			if ($consumidor_revenda=='R'){
				if (strlen($revenda_nome) == 0){
					$revenda_nome = $consumidor_nome;
				}
			}
			echo "<td style='font-size: 9px; font-weight: bold; font-family: verdana'>$consumidor_nome</td>";
			echo "<td style='font-size: 9px; font-weight: bold; font-family: verdana' align='left' title='Produto: $produto_referencia - $produto_descricao' style='cursor: help'>".substr($produto_descricao,0,20)."</td>";
			echo "<td style='font-size: 9px; font-weight: bold; font-family: verdana'>";
			echo ($login_fabrica == 11) ? $defeito_reclamado:$defeito_constatado;
			echo "</td>";
			echo "<td style='font-size: 9px; font-weight: bold; font-family: verdana' title='Observação: ".$status_observacao."'>".str_replace('CNPJ','CNPJ <BR>',$status_descricao). "</td>";
			echo "<td style='font-size: 9px; font-family: verdana' title='$sua_os - Motivo: ".$obs_reincidencia."' nowrap>".substr($obs_reincidencia,0,50). "</td>";	
			if ($login_fabrica == 52){
				echo "<td style='font-size: 9px; font-family: verdana' title='$sua_os - Motivo da Reincidência: ".$motivo_reincidencia_desc."' nowrap>".substr($motivo_reincidencia_desc,0,50). "</td>";	
			}
		
			echo "</tr>";
			echo "<tr bgcolor='#C0E2D9'>";
			echo "<td colspan=13><div id='interacao_".$x."'></div></td>";
			echo "</tr>";


			/* ---------------- OS REINCIDENTE -------------------*/
			echo "<tr bgcolor='$cor'>";
			echo "<td align='center' width='0'>Reinc.</td>";
			echo "<td style='font-size: 9px; font-family: verdana' >$reinc_sua_os</a></td>";
			echo "<td style='font-size: 9px; font-family: verdana'  >$reinc_serie</td>";
			echo "<td style='font-size: 9px; font-family: verdana'  >$reinc_data_abertura</td>";
			echo "<td style='font-size: 9px; font-family: verdana' >$reinc_data_fechamento </td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' >".substr($reinc_posto_nome,0,20) ."...</td>";
			echo "<td style='font-size: 9px; font-family: verdana' >$reinc_nota_fiscal</td>";
			//HD 119665
			if (strlen($reinc_consumidor_nome) == 0) $reinc_consumidor_nome = $reinc_revenda_nome;
			if ($reinc_consumidor_revenda=='R'){
				if (strlen($reinc_revenda_nome) == 0){
					$reinc_revenda_nome = $reinc_consumidor_nome;
				}
			}
			echo "<td style='font-size: 9px; font-family: verdana' >$reinc_consumidor_nome</td>";
			echo "<td align='left' style='font-size: 8px; font-family: verdana' style='cursor: help'>$reinc_produto_referencia - ".substr($reinc_produto_descricao ,0,20)."</td>";
			echo "<td style='font-size: 9px; font-weight: bold; font-family: verdana' >";
			echo ($login_fabrica == 11) ? $reinc_defeito_reclamado:$reinc_defeito_constatado;
			echo "</td>";
			echo "<td style='font-size: 9px; font-family: verdana' title='Observação: ".$reinc_status_observacao."' colspan=3>".$reinc_status_descricao. "</td>";
			echo "</tr>";
		}
		
		
		echo "</table>";
		echo "</form>";
	}else{
		echo "<center>Nenhuma OS encontrada.</center>";
	}
	$msg_erro = '';
}

include "rodape.php" ?>
</div>
