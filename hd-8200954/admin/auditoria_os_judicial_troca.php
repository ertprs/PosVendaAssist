<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="call_center";

include "autentica_admin.php";
include 'funcoes.php';
include '../helpdesk/mlg_funciones.php';
include "../helpdesk.inc.php";// Funcoes de HelpDesk hd_chamado=2537875

function ultima_interacao($os) {
    global $con, $login_fabrica;

    $select = "SELECT admin, posto FROM tbl_os_interacao WHERE fabrica = {$login_fabrica} AND os = {$os} AND interno IS NOT TRUE ORDER BY data DESC LIMIT 1";
    $result = pg_query($con, $select);

    if (pg_num_rows($result) > 0) {
        $admin = pg_fetch_result($result, 0, "admin");
        $posto = pg_fetch_result($result, 0, "posto");

        if (!empty($admin)) {
            $ultima_interacao = "fabrica";
        } else {
            $ultima_interacao = "posto";
        }
    }

    return $ultima_interacao;
}

if ($S3_sdk_OK) {
	include_once S3CLASS;

	$s3tj = new anexaS3('tj', (int) $login_fabrica);
	$S3_online = is_object($s3tj);
}

$array_reincidencias = array(164,165,166);

# Pesquisa pelo AutoComplete AJAX
$q = $_GET["q"];
if (isset($_GET["q"])) {

	$tipo_busca = $_GET["busca"];

	if (strlen($q) > 2) {
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";
		$sql .= ($tipo_busca == "codigo") ? " AND tbl_posto_fabrica.codigo_posto = '$q' " : " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		$res = pg_query($con,$sql);
		if (pg_num_rows ($res) > 0) {
			$resultados = pg_fetch_all($res);
			foreach ($resultados as $resultado){
				echo $resultado['cnpj']."|".$resultado['nome']."|".$resultado['codigo_posto'];
				echo "\n";
			}
		}
	}
	exit;
}

$os   = $_GET["os"];
$tipo = $_GET["tipo"];

if ($_POST["btn_acao"]) {
	$btn_acao    = trim($_POST["btn_acao"]);
}
else {
	$btn_acao = $_GET["btn_acao"];
}

if(strlen($btn_acao)>0 AND strlen($select_acao)>0){
	$qtde_os     = trim($_POST["qtde_os"]);
	$observacao  = trim($_POST["observacao"]);

	if($select_acao == "165" AND strlen($observacao) == 0){
		$msg_erro .= "Informe o motivo da reprovação da OS.<br>";
	}

	$observacao = (strlen($observacao) > 0) ? " Observação: $observacao " : "Observação:";

	if (strlen($qtde_os)==0){
		$qtde_os = 0;
	}

	for ($x = 0; $x < $qtde_os; $x++){

		$xxos = trim($_POST["check_".$x]);


		if (strlen($xxos) > 0 AND strlen($msg_erro) == 0) {

			$res_os = pg_query($con,"BEGIN TRANSACTION");

			$sql = "SELECT status_os
					FROM tbl_os_status
					WHERE status_os IN (" . implode(',', $array_reincidencias) . ")
					AND tbl_os_status.fabrica_status = $login_fabrica
					AND os = $xxos
					ORDER BY data DESC
					LIMIT 1";

			$res_os = pg_query($con,$sql);

			if (pg_num_rows($res_os) > 0) {

				$status_da_os = trim(pg_fetch_result($res_os, 0, status_os));

				$sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao,admin) VALUES ($xxos,$select_acao,current_timestamp,'$observacao',$login_admin)";
				$res       = pg_query($con, $sql);
				$msg_erro .= pg_errormessage($con);

				if (empty($msg_erro)) {
					$envia_email_esmaltec = 1;
					$esmaltec_acao = ($select_acao == "166") ? 'aprovada' : 'reprovada';
				}
			}

			if (strlen($msg_erro) == 0) {

				$sqlPostoeMail = "SELECT tbl_posto_fabrica.contato_email, tbl_os.sua_os
									FROM tbl_posto_fabrica
									JOIN tbl_os ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
									WHERE os = $xxos";
				$resPostoeMail = pg_query($con, $sqlPostoeMail);

				if (pg_num_rows($resPostoeMail) == 0) {
					$sqlPostoeMail2 = "SELECT tbl_posto.email, tbl_os.sua_os
											FROM tbl_posto
											JOIN tbl_os USING (posto)
											WHERE os = $xxos";
					$resPostoeMail2 = pg_query($con, $sqlPostoeMail2);

					if (pg_num_rows($resPostoeMail2) == 1) {
						$posto_email  = pg_fetch_result($resPostoeMail2, 0, 'email');
						$sua_os_email = pg_fetch_result($resPostoeMail2, 0, 'sua_os');
					}
				} else {
					$posto_email  = pg_fetch_result($resPostoeMail, 0, 'contato_email');
					$sua_os_email = pg_fetch_result($resPostoeMail, 0, 'sua_os');
				}

				if (!empty($posto_email)) {

					if($select_acao == 166){
						$assunto = 'A OS ' . $sua_os_email . ' Foi Aprovada pela Auditoria';
						$msg = 'OS ' . $sua_os_email . ' aprovada por constar o documento solicitado correto.';
					}else{
						$assunto = 'A OS ' . $sua_os_email . ' Foi Reprovada pela Auditoria';
						$msg = 'OS ' . $sua_os_email . ' reprovada por não constar o documento solicitado correto.';
						$msg .= '<br/><br/>';
						$msg .= 'Favor regularizar no prazo máximo de 30 dias.';
					}

					$headers  = 'MIME-Version: 1.0' . "\r\n";
					$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
					$headers .= 'From: Esmaltec <juritico.at@esmaltec.com.br>' . "\r\n";

					mail($posto_email, utf8_encode($assunto), utf8_encode($msg), $headers);
				}

				$res = pg_query($con,"COMMIT TRANSACTION");
			} else {
				$res = pg_query($con,"ROLLBACK TRANSACTION");
			}
		}
	}
}

$layout_menu =  "auditoria";
$title = "APROVAÇÃO DAS OSS DE TROCA DE PRODUTO E PROCESSO JUDICIAL";
include "cabecalho.php";

if ($login_fabrica == 30) {
	$plugins = array(
		"shadowbox"
	);
}

?>

<style type="text/css">

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}

#relatorio_os_auditoria thead tr th {

	cursor: pointer;

}

.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}



.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.env-interagir{
    width: 800px;
    min-height: 400px;
    border: 3px solid #e2e2e2;
    background: #fff;
    position: fixed;
    top: 50px;
    left: 20%;
    z-index: 10;
    display: none;
}

.env-interagir textarea{
    margin-top: 40px;
    width: 500px;
    height: 100px;

}

#env-interacoes{
    height: 181px;
    overflow-y: scroll;
}

#env-interacoes table{
    width: 100%;
}

.env-buttons{
    margin-top: 15px;
    width: 100%;
}

#interacao-msg{
    min-height: 0px;
    background: #e2e2e2;
    position: absolute;
    width: 100%;
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
	else
		alert("Informe toda ou parte da informação para realizar a pesquisa!");
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


var timeHelper;
function clearMessage(){
    window.clearTimeout(timeHelper);

    timeHelper =  setTimeout(function(){
        $("#interacao-msg").html("");
    },5000);

}

function changeColorLine(os){

	$(".btn-interagir").each(function(idx,elem){
		if($(elem).data("os") == os){
			var tr = $(elem).parents("tr").first();
			$(tr).attr("style","background: #FFDC4C");
		}
	});
}

function getInterations (os) {
    $("#tr-coments").html("");

    <?php if ($login_fabrica == 30) { ?>
    		Shadowbox.open({
                content: "relatorio_interacao_os.php?interagir=true&os="+os,
                player: "iframe",
                width: 850,
                height: 600,
                title: "Ordem de Serviço "+os
            });

    <?php } else { ?>
	    $.ajax("ajax_interagir_os.php",{
	        data:{
	            os: os
	        }
	    }).done(function(response){

	        $.each(response,function(idx,elem){
	            var tr = $("<tr>");

	            $(tr).append($("<td>").html(elem.comentario));
	            if(elem.nome_completo == ""){
	                $(tr).append($("<td>").html(elem.nome_fantasia ));
	            }else{
	                $(tr).append($("<td>").html(elem.nome_completo));
	            }

	            $(tr).append($("<td>").html(elem.data));

	            $("#tr-coments").append(tr);
	        });
    	});
    <?php } ?>
}

$(function(){
	<?php if ($login_fabrica == 30) { ?>
			Shadowbox.init();
	<?php } ?>

    $(".btn-interagir").click(function(){
        var os = $(this).data("os");
		$("#os-number-env").html(os);

		interations = getInterations(os);

		<?php if ($login_fabrica != 30) { ?>

	        $("#btn-grava-interacao").data("os",$(this).data("os"))
	        $("#txt-interacao").val("");

	        $(".env-interagir").fadeIn("500");

    	<?php } ?>
    });


    $("#btn-grava-interacao").click(function(){
        var os = $(this).data("os");
        var text = $("#txt-interacao").val();

        if(text == ""){
            $("#interacao-msg").html("Digite uma interação");
            clearMessage();

            return false;
        }

        $("#btn-grava-interacao").html("Gravando...");

        $.post("ajax_interagir_os.php",{
            interacao: text,
            os: os
        }).done(function(response){
            $("#btn-grava-interacao").html("Gravar");

            if(response.exception == undefined && response.msg == "ok"){
                $("#interacao-msg").html("Interação Gravada!!!");
                clearMessage();

                changeColorLine(os);

                setTimeout(function(){
                    $(".env-interagir").fadeOut("500");
                    $("#interacao-msg").html("");
                },1500);
            }else{
                $("#interacao-msg").html(response.exception);
                clearMessage();
            }
        });
    });

    $("#btn-cancela-interacao").click(function(){
        $(".env-interagir").fadeOut("500");
    });
});
</script>

<?php include "../js/js_css.php";?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datepick({startDate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$("#data_inicial").mask("99/99/9999");
		$("#data_final").mask("99/99/9999");
	});
</script>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.alphanumeric.js'></script>
<script language='javascript' src='ajax.js'></script>
<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>
<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script>

<script language="JavaScript">
$().ready(function() {

	$.tablesorter.defaults.widgets = ['zebra'];
	$("#relatorio_os_auditoria").tablesorter();

	$("input.qtde_km").numeric( {allow: ','} );

	$("#os").keypress(function(e) {
		var c = String.fromCharCode(e.which);
		var allowed = '1234567890-';
		if ((e.keyCode != 9 && e.keyCode != 8) && allowed.indexOf(c) < 0) return false;
	});


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
		matchCase: true,
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
include "javascript_pesquisas.php";
if($btn_acao == 'Pesquisar'){
	$data_inicial		= trim($_POST['data_inicial']);
	$data_final			= trim($_POST['data_final']);
	$aprova				= trim($_POST['aprova']);
	$regiao_comercial	= trim($_POST['regiao_comercial']);
	$posto_codigo		= trim($_POST["posto_codigo"]);

    $gerar_excel = $_POST["gerar_excel"];

    if($gerar_excel == 't'){
        $data = date("d-m-Y-h-i");
        $arquivo_completo = "xls/auditoria_os_judicial_troca_$login_fabrica"."_$data.csv";
        $excel = fopen ($arquivo_completo,"w+");
    }


	if ($_POST["os"]) {
		$os = trim($_POST['os']);
	}
	else {
		$os = trim($_GET['os']);
	}

	if (strlen($os)>0){
		$Xos = " AND os = $os ";
	}

	if($login_fabrica == 30){ //hd_chamado=2537875
		if(strlen($admin_sap) > 0){
			$admin_sap = (int) $_POST['admin_sap'];
			$cond_admin_sap = " AND tbl_posto_fabrica.admin_sap = $admin_sap";
		}
	}

	if(strlen($aprova) == 0){
		$aprova = "aprovacao";
		$aprovacao = "164";
		$status = "Aguardando Aprovação";
	}elseif($aprova=="aprovacao"){
		$aprovacao = "164";
		$status = "Aguardando Aprovação";
	}elseif($aprova=="aprovadas"){
		$aprovacao = "166";
		$status = "Aprovada";
	}elseif($aprova=="reprovadas"){
		$aprovacao = "165";
		$status = "Reprovada";
	}


	if (strlen($data_inicial) > 0) {
		$xdata_inicial = formata_data ($data_inicial);
		$xdata_inicial = $xdata_inicial." 00:00:00";
	}

	if (strlen($data_final) > 0) {
		$xdata_final = formata_data ($data_final);
		$xdata_final = $xdata_final." 23:59:59";
	}
	if(!empty($data_inicial) and !empty($data_final)) {
		$sqlX = "SELECT ('$xdata_final'::date - '$xdata_inicial'::date)";
		$resX = @pg_query($con,$sqlX);
		$msg_erro .= pg_errormessage($con);
		if(strpos($msg_erro,"date/time field value out of range") !==false) {
			$msg_erro .= "Data Inválida.";
		}
		if(strlen($msg_erro)==0){
			if(pg_num_rows($resX) > 0){
				$periodo = pg_fetch_result($resX,0,0);
				if($periodo < 0) {
					$msg_erro .= "Data Inválida.";
				}elseif($periodo > 90){
					$msg_erro .= "Período entre datas não pode ser maior que 90 dias";
				}
			}
		}
	}

	if(strlen($posto_codigo)>0){

		$sql = " SELECT tbl_posto_fabrica.posto,
							tbl_posto.nome AS nome_posto
				FROM tbl_posto_fabrica
				JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica
				AND   tbl_posto_fabrica.codigo_posto = '$posto_codigo' ";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
			$nome_posto = pg_fetch_result($res, 0, 'nome_posto');
			$sql_posto .= " AND tbl_posto_fabrica.codigo_posto = '$posto_codigo' ";
		}else{
			$msg_erro .= "Código do posto $posto_codigo incorreto";
		}

	}
}
?>
<br>
<? if(!empty($msg_erro)){ ?>
<table width="700px" align="center" class="msg_erro">
		<tr>
			<td> <? echo $msg_erro; ?></td>
		</tr>
</table>
<? } ?>

<?php if($login_fabrica == 30){ ?>
<div class='env-interagir'>
    <h1>Interagir na OS <span id="os-number-env">123123</span></h1>
    <div id="interacao-msg"></div>
    <textarea id="txt-interacao" name="txt-interacao" rows="3"></textarea>

    <div class="env-buttons">
        <button type="button" id="btn-grava-interacao" data-os="">Gravar</button>
        <button type="button" id="btn-cancela-interacao">Fechar</button>
    </div>
    <hr>
    <div id='env-interacoes'>
        <table border="0" cellspacing="1" class="tabela">
            <thead>
                <tr class="titulo_coluna">
                    <th>Interação</th>
                    <th>Admin</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody id="tr-coments">
            </tbody>
        </table>
    </div>
</div>
<?php } ?>

<form name="frm_pesquisa" method="post" action="<?echo $PHP_SELF?>">

<TABLE width="700" align="center" border="0" cellspacing='0' cellpadding='0' class='formulario'>

<tr class="titulo_tabela"><td colspan='3' height="20px">Parâmetros de Pesquisa</td></tr>
<TBODY>
	<tr><td>&nbsp;</td></tr>

<TR>
	<td width="100">&nbsp;</td>
	<TD>Número da OS<br><input type="text" name="os" id="os" size="20" maxlength="20" value="<? echo $os ?>" class="frm" tabindex='1'></TD>
	<TD></TD>
</TR>

<tr><td>&nbsp;</td></tr>

<TR>
	<td width="100">&nbsp;</td>
	<TD>Data Inicial<br><input type="text" name="data_inicial" id="data_inicial" size="11" maxlength="10" value="<? echo $data_inicial ?>" class="frm" tabindex='2'></TD>
	<TD>Data Final<br><input type="text" name="data_final" id="data_final" size="11" maxlength="10" value="<? echo $data_final ?>" class="frm" tabindex='3'></TD>
</TR>

<tr><td>&nbsp;</td></tr>

<TR>
	<td width="100">&nbsp;</td>
	<TD>Código Posto<br><input type="text" name="posto_codigo" id="posto_codigo" size="15"  value="<? echo $posto_codigo ?>" class="frm" tabindex='4'></TD>
	<TD>Nome do Posto<br><input type="text" name="posto_nome" id="posto_nome" size="40"  value="<? echo $posto_nome ?>" class="frm" tabindex='5'></TD>
</TR>

<tr><td>&nbsp;</td></tr>

<?php if($login_fabrica == 30){ $aAtendentes = hdBuscarAtendentes(); //hd_chamado=2537875 ?>

<TR>
	<td width="100">&nbsp;</td>
	<td>Inspetor<br>
		<select class='frm' name="admin_sap" id="admin_sap">
	      <option value=""></option>
	      <?php foreach($aAtendentes as $aAtendente): ?>
            <option value="<?php echo $aAtendente['admin']; ?>" <?php echo ($aAtendente['admin'] == $admin_sap) ? 'selected="selected"' : '' ; ?>><?php echo empty($aAtendente['nome_completo']) ? $aAtendente['login'] : $aAtendente['nome_completo'] ; ?></option>
         <?php endforeach; ?>
	   </select>
	</td>
</TR>
<?php if($login_fabrica == 30){?>
<tr>
    <td>&nbsp;</td>
</tr>
<TR>
    <td width="100">&nbsp;</td>
    <td>Gerar Excel <input type="checkbox" name="gerar_excel" value="t"></td>
</TR>
<tr><td>&nbsp;</td></tr>

<?php } } ?>
<tr>
	<td width="100">&nbsp;</td>
	<td colspan='2'>
		<b>Mostrar as OS:</b><br>
			<INPUT TYPE="radio" NAME="aprova" value='aprovacao' <? if(trim($aprova) == 'aprovacao' OR trim($aprova)==0) echo "checked='checked'"; ?> tabindex='6'>Em aprovação &nbsp;&nbsp;&nbsp;
			<INPUT TYPE="radio" NAME="aprova" value='aprovadas' <? if(trim($aprova) == 'aprovadas') echo "checked='checked'"; ?>>Aprovadas  &nbsp;&nbsp;&nbsp;
			<INPUT TYPE="radio" NAME="aprova" value='reprovadas' <? if(trim($aprova) == 'reprovadas') echo "checked='checked'"; ?>>Reprovadas &nbsp;&nbsp;&nbsp;
	</td>
</tr>

<tr><td>&nbsp;</td></tr>

</tbody>
<TR>
	<TD colspan="3" align='center'>
		<br>
		<input type='hidden' name='btn_acao' value=''>
		<input type="button" value="Pesquisar" onclick="javascript: if ( document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='Pesquisar'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" alt='Clique AQUI para pesquisar' tabindex='7'>
	</TD>
</TR>

<tr><td>&nbsp;</td></tr>

</table>
</form>

<?
if (strlen($btn_acao)  > 0) {

    if($login_fabrica == 30){
        $sql_data_auditoria = " (select TO_CHAR(tbl_os_status.data,'DD/MM/YYYY') as data_auditoria from tbl_os_status where os = tbl_os.os order by data asc limit 1) as data_auditoria, ";
    }

	$sql =  "SELECT interv.os
			INTO TEMP tmp_interv_$login_admin
			FROM (
				SELECT
				ultima.os,
				(
					SELECT status_os
					FROM tbl_os_status
					WHERE status_os IN (" . implode(',', $array_reincidencias) . ")
					AND tbl_os_status.os = ultima.os AND tbl_os_status.fabrica_status = $login_fabrica
					ORDER BY data DESC LIMIT 1
				) AS ultimo_status
				FROM (
					SELECT DISTINCT os
					FROM tbl_os_status
					WHERE status_os IN (" . implode(',', $array_reincidencias) . ")
					$cond_auditoria
					AND tbl_os_status.fabrica_status = $login_fabrica
				) ultima
			) interv
			WHERE interv.ultimo_status IN ($aprovacao)
			$Xos;

			CREATE INDEX tmp_interv_OS_$login_admin ON tmp_interv_$login_admin(os);

			SELECT	tbl_os.os                                                   ,
					tbl_os.posto 												,
					tbl_os.sua_os                                               ,
					TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
					tbl_posto.nome                     AS posto_nome            ,
					tbl_posto_fabrica.codigo_posto                              ,
					tbl_produto.referencia             AS produto_referencia    ,
					tbl_produto.descricao              AS produto_descricao     ,
					tbl_defeito_constatado.descricao AS defeito_constatado      ,
                    $sql_data_auditoria
					(SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (" . implode(',', $array_reincidencias) . ") AND tbl_os_status.fabrica_status = $login_fabrica ORDER BY data DESC LIMIT 1) AS status_os         ,
					(SELECT observacao FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (" . implode(',', $array_reincidencias) . ") AND tbl_os_status.fabrica_status = $login_fabrica
					ORDER BY data DESC LIMIT 1) AS status_observacao,
					(SELECT tbl_status_os.descricao FROM tbl_os_status JOIN tbl_status_os USING(status_os) WHERE tbl_os.os = tbl_os_status.os AND status_os IN (" . implode(',', $array_reincidencias) . ") AND tbl_os_status.fabrica_status = $login_fabrica
					ORDER BY data DESC LIMIT 1) AS status_descricao,
					(SELECT tbl_admin.login FROM tbl_admin LEFT JOIN tbl_os_status USING(admin) WHERE tbl_os.os = tbl_os_status.os AND status_os IN (" . implode(',', $array_reincidencias) . ") AND tbl_os_status.fabrica_status = $login_fabrica
					ORDER BY data DESC LIMIT 1) AS admin
				FROM tmp_interv_$login_admin X
				JOIN tbl_os ON tbl_os.os = X.os
				JOIN tbl_produto              ON tbl_produto.produto = tbl_os.produto
				JOIN tbl_posto                ON tbl_os.posto        = tbl_posto.posto
				JOIN tbl_posto_fabrica        ON tbl_posto.posto     = tbl_posto_fabrica.posto
				AND tbl_posto_fabrica.fabrica = $login_fabrica
				LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
				WHERE tbl_os.fabrica = $login_fabrica
				$cond_admin_sap
				$sql_add
				$sql_posto ";

	if (strlen($xdata_inicial) > 0 AND strlen($xdata_final) > 0) {
		$sql .= " AND tbl_os.data_digitacao BETWEEN '$xdata_inicial' AND '$xdata_final'
				$cond_excluidas";
				$order_by = " ORDER BY tbl_posto_fabrica.codigo_posto,tbl_os.os ";
	}

	$sql .= $order_by;

	$res = pg_query($con,$sql);
	if(pg_num_rows($res)>0){

	?>

		<BR><BR>
		<FORM name='frm_pesquisa2' method='POST' action='<?=$PHP_SELF?>'>

		<input type='hidden' name='data_inicial'   value='<?=$data_inicial?>'>
		<input type='hidden' name='data_final'     value='<?=$data_final?>'>
		<input type='hidden' name='aprova'         value='<?=$aprova?>'>

		<?php if($login_fabrica == 30){ ?>
        <div class="legenda-interacao" style="width: 700px;margin: 0 auto;">
            <table>
                <tr>
                    <td style="background: #A6D941">&nbsp&nbsp</td>
                    <td>Interação do Posto</td>
                </tr>
                <tr>
                    <td style="background: #FFDC4C">&nbsp&nbsp</td>
                    <td>Interação do Admin</td>
                </tr>
            </table>
        </div>
        <?php } ?>

		<table width='98%' id='relatorio_os_auditoria' class='tabela'>
			<thead>
				<tr class='titulo_coluna'>

					<th>
						<img border='0' src='imagens_admin/selecione_todas.gif' onclick='javascript: checkaTodos()' alt='Selecionar todos' style='cursor: hand;' align='center'>
					</th>
					<th>OS</th>
					<th>Data Digitação</th>
                    <?php if($login_fabrica == 30){?>
                    <th>Data Auditoria</th>
					<?php } ?>
                    <th>Posto</th>
					<th>Produto</th>
					<th>Defeito Constatado</th>
					<th>Status</th>
					<th>Admin</th>
					<th>Arquivo</th>
					<th>Observação</th>
					<?php
                    if($login_fabrica == 30){
                        ?>
                        <th>Interagir</th>
                        <?php
                    }
                    ?>
				</tr>
			</thead>
			<tbody>
	<?php

        if($gerar_excel == 't'){
            $thead .= "OS;".utf8_encode("Data Digitação").";".utf8_encode("Data Auditoria").";Posto;Produto;Defeito Constatado;Status;Admin;".utf8_encode("Observação").";\r\n";
            $escreve = fwrite($excel, $thead);
        }

		$cores = '';
		$qtde_intervencao = 0;
		$total_os = pg_num_rows($res);
        $tbody = "";
		for ($x=0; $x<pg_num_rows($res);$x++){

			$os						= pg_fetch_result($res, $x, 'os');
			$posto  				= pg_fetch_result($res, $x, 'posto');
			$sua_os					= pg_fetch_result($res, $x, 'sua_os');
			$codigo_posto			= pg_fetch_result($res, $x, 'codigo_posto');
			$posto_nome				= pg_fetch_result($res, $x, 'posto_nome');
			$produto_referencia		= pg_fetch_result($res, $x, 'produto_referencia');
			$produto_descricao		= pg_fetch_result($res, $x, 'produto_descricao');
			$defeito_constatado     = pg_fetch_result($res, $x, 'defeito_constatado');
			$data_digitacao			= pg_fetch_result($res, $x, 'data_digitacao');
            $data_auditoria         = pg_fetch_result($res, $x, 'data_auditoria');
			$status_os				= pg_fetch_result($res, $x, 'status_os');
			$status_observacao		= pg_fetch_result($res, $x, 'status_observacao');
			$status_descricao		= pg_fetch_result($res, $x, 'status_descricao');
			$admin 					= pg_fetch_result($res, $x, 'admin');
			$cores++;
			$cor = ($cores % 2 == 0) ? "#FEFEFE": '#E8EBEE';

			if($login_fabrica == 30){
                $ultima_interacao = ultima_interacao($os);
                switch ($ultima_interacao) {
                    case "fabrica":
                        $cor = "#FFDC4C";
                        break;

                    case "posto":
                        $cor = "#A6D941";
                        break;
                }
            }

            if($gerar_excel == 't'){
                $tbody .= "$sua_os;$data_digitacao;$data_auditoria;$codigo_posto - ".utf8_encode("$posto_nome")."; $produto_referencia - $produto_descricao; $defeito_constatado;$status_descricao;$admin;".utf8_encode("$status_observacao").";\r\n";
            }

			echo "<input type=\"hidden\" value=\"$posto_codigo\" name=\"posto_codigo\" />";
			echo "<input type=\"hidden\" value=\"$nome_posto\" name=\"posto_nome\" />";
			echo "<input type=\"hidden\" value=\"$admin_sap\" name=\"admin_sap\" />"; //hd_chamado=2537875
			if(strlen($sua_os)== 0)$sua_os=$os;
			?>
				<tr bgcolor='<?=$cor?>' id='linha_<?=$x?>' style='height:36px;'>

					<td>
						<input type='checkbox' name='check_<?=$x?>' id='check_<?=$x?>' value='<?=$os?>' onclick=\"setCheck('check_<?=$x?>','linha_<?=$x?>','<?=$cor?>');\" <? echo (strlen($msg_erro)>0 and strlen($_POST["check_".$x])>0) ? " CHECKED " : ""; ?> >
					</td>
					<td nowrap >
						<a href='os_press.php?os=<?=$os?>' title='$title_extrato2' target='_blank'><?=$sua_os?></a>
					</td>
					<td><?=$data_digitacao?></td>
                    <td><?=$data_auditoria?></td>
					<td><?=$codigo_posto.' - '.$posto_nome?></td>
					<td align='left' nowrap><?=$produto_referencia.' - '.$produto_descricao?></td>
					<td align='left' nowrap><?=$defeito_constatado?></td>
					<td nowrap><?=$status?></td>
					<td nowrap><?=$admin?></td>
					<td nowrap>
						<?php
							if ($s3tj->temAnexos($os)) {
								$link = getAttachLink($s3tj->url, '', true);
								echo createHTMLLink($link['url'],"<img width='32' src='../imagens/{$link['ico']}' />", "target='_blank'");
							}
						?>
					</td>
					<td nowrap><?=$status_observacao?></td>
					<?php
                    if($login_fabrica == 30){
                        ?>
                        <td><button type="button" data-os="<?=$os?>" class="btn-interagir">Interagir</button></td>
                        <?php
                    }
                    ?>

				</tr>
			<?php
		}
        if($gerar_excel){
          fwrite($excel, $tbody);
          fclose($excel);  
        }
		?>
		<input type='hidden' name='qtde_os' value='<?=$x?>'>
		</tbody>
		<tfooter>
			<tr>
				<th height='20' bgcolor='#485989' colspan='100%' align='left'>

		<?php
		if (trim($aprova) == 'aprovacao') {
		?>
					&nbsp;&nbsp;&nbsp;&nbsp;<img border='0' src='imagens/seta_checkbox.gif' align='absmiddle'> &nbsp; <font color='#FFFFFF'><B>COM MARCADOS:</B></font> &nbsp;
					<select name='select_acao' size='1' class='frm' >
						<option value=''></option>
						<option value='166' <? if($_POST["select_acao"] == "166") echo "selected";?> >APROVADO</option>
						<option value='165' <? if($_POST["select_acao"] == "165") echo "selected";?> >RECUSADO</option>
					</select>
					&nbsp;&nbsp; <font color='#FFFFFF'><b>Motivo:<b></font> <input class='frm' type='text' name='observacao' id='observacao' size='30' maxlength='250' value='' <? if ($_POST["select_acao"] == "19") echo " DISABLED ";?> >
					&nbsp;&nbsp;<img src='imagens/btn_gravar.gif' style='cursor:pointer' onclick='javascript: document.frm_pesquisa2.submit()' style='cursor: hand;' border='0'>
				</th>
		<?php
		}
		?>

		<input type='hidden' name='btn_acao' value='Pesquisar'>
		</tr></tfooter></table>
		<p>TOTAL OS: <?=$total_os?></p>
	</form>
	<?php
    echo "<br>";
        if($login_fabrica == 30 and $gerar_excel == 't'){
          echo "<br /> <a href='$arquivo_completo' target='_blank'><img src='imagens/excel.png' height='20px' width='20px' align='absmiddle'>&nbsp;&nbsp;Gerar Arquivo Excel</a> <br />";
        }
	} else {
		echo "<center>Nenhuma OS encontrada.</center>";
	}

	$msg_erro = '';

}
echo "<br>";
include "rodape.php" ?>