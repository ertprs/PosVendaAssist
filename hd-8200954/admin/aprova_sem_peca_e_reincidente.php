<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="auditoria";
include "autentica_admin.php";
include 'funcoes.php';

include "../helpdesk.inc.php";// Funcoes de HelpDesk hd_chamado=2537875

use util\ArrayHelper;
use model\ModelHolder;

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


if($select_acao == 65 AND $login_fabrica <> 127){
	reparoNaFabrica();
}
else if(strlen($btn_acao)>0 AND strlen($select_acao)>0){
	$qtde_os           = trim($_POST["qtde_os"]);
	$observacao        = trim($_POST["observacao"]);
	$tipo_os_auditoria = $_POST["tipo_os_auditoria"];
    if($login_fabrica != 30){
        if($select_acao == "13" AND strlen($observacao) == 0){
            $msg_erro .= "Informe o motivo da reprovação da OS.<br>";
        }
	}else{
        if(in_array($select_acao,array(14,15,16)) && strlen($observacao) == 0){
            $msg_erro .= "Informe o motivo da reprovação da OS.<br>";
        }
	}
    if($select_acao == "19" AND strlen($observacao) == 0 and $login_fabrica <> 147){
        $msg_erro .= "Informe o motivo da aprovação da OS.<br>";
    }

	if(strlen($observacao) == 0 and $login_fabrica==147){
		$observacao = " OS aprovada pelo admin";
	}

	if(strlen($observacao) > 0){
		$observacao = " Observação: $observacao ";
	}

	if (strlen($qtde_os)==0){
		$qtde_os = 0;
	}

	if($login_fabrica == 30){
        $sqlEmail = "
            SELECT  DISTINCT
                    tbl_admin.nome_completo,
                    tbl_admin.email
            FROM    tbl_admin
            WHERE   tbl_admin.fabrica                   = $login_fabrica
            AND     (
                        tbl_admin.responsavel_postos    IS TRUE
                    OR  tbl_admin.aprova_laudo          IS TRUE
                    )
            AND     tbl_admin.ativo                     IS TRUE
        ";
        $resEmail = pg_query($con,$sqlEmail);
        $nomesCompletos = pg_fetch_all_columns($resEmail,0);
        $emails         = pg_fetch_all_columns($resEmail,1);
	}

	for ($x=0;$x<$qtde_os;$x++){

		$xxos         = trim($_POST["check_".$x]);

		if (strlen($xxos) > 0 AND strlen($msg_erro) == 0){

			$res_os = pg_exec($con,"BEGIN TRANSACTION");
			switch ($tipo_os_auditoria) {
				case 'reincidencia':
					$status_pesquisa = array(67,68,70);
					break;

				case 'sem_peca':
					$status_pesquisa = array(115);
					break;

				case 'mais_pecas':
					$status_pesquisa = array(118);
					break;

                case 'defeito_constatado':
                    $status_pesquisa = array(20);
                    break;
			}

			$sql = "SELECT status_os
					FROM tbl_os_status
					WHERE status_os IN (".implode(",", $status_pesquisa).")
					AND os = $xxos
					ORDER BY data DESC
					LIMIT 1";
			$res_os = pg_exec($con,$sql);
			if (pg_numrows($res_os)>0){
				$status_da_os = trim(pg_result($res_os,0,status_os));

				if($select_acao == "19"){

					if($login_fabrica == 50 && $status_da_os == 115){
						$status_os_aprovada = 188;
					}elseif((in_array($login_fabrica,array(30,50,131,132)) || $login_fabrica >= 134) && $status_da_os == 118){
						$status_os_aprovada = 187;
					}else{
						$status_os_aprovada = 19;
					}

					$sql = "INSERT INTO tbl_os_status
							(os,status_os,data,observacao,admin)
							VALUES ($xxos,$status_os_aprovada,current_timestamp,'$observacao',$login_admin)";

					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}

				if($select_acao == "13"){

					if(($login_fabrica == 50 || $login_fabrica == 131 || $login_fabrica == 132 || $login_fabrica >= 134) && $status_da_os == 118){
						$status_os_aprovada = 185;
					}elseif($login_fabrica == 50 && $status_da_os == 115){
						$status_os_aprovada = 186;
					}else{
						$status_os_aprovada = 13;
					}

					$sql = "INSERT INTO tbl_os_status
							(os,status_os,data,observacao,admin)
							VALUES ($xxos,$status_os_aprovada,current_timestamp,'$observacao',$login_admin)";
					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);

                    if($login_fabrica == 50){
                        $sql = "UPDATE tbl_os SET
                                excluida  = 't'
                                WHERE os = $xxos
                                AND fabrica = $login_fabrica ";
                        $res = pg_exec($con,$sql);
                        $msg_erro .= pg_errormessage($con);
                    }
                    if ((($login_fabrica == '50' and $status_da_os == '20') or ($login_fabrica >= 131)) and empty($msg_erro)) {
                        $excluiOs = pg_query($con, "SELECT fn_os_excluida($xxos, $login_fabrica, $login_admin)");
                        $msg_erro .= pg_last_error();

                        if(strlen($msg_erro) == 0 ){                            

                            $qryOsInfo = pg_query($con, "SELECT sua_os, posto FROM tbl_os WHERE os = $xxos");
                            $xxsua_os = pg_fetch_result($qryOsInfo, 0, 'sua_os');
                            $xxposto = pg_fetch_result($qryOsInfo, 0, 'posto');


                            $mensagem_comunicado = 'A OS ' . $xxsua_os . ' foi excluída pela fabricante.';
                            $mensagem_comunicado.= str_replace('Observação', 'Motivo', $observacao);

                            $insComunic = "INSERT INTO tbl_comunicado (fabrica, posto, mensagem, tipo, obrigatorio_site, ativo)
                                              VALUES ($login_fabrica, $xxposto, '$mensagem_comunicado', 'Comunicado', 't', 't')";
                            $qryComunic = pg_query($con, $insComunic);

                            $sql_motivo = "UPDATE tbl_os_excluida SET motivo_exclusao = substr('{$observacao}',1,150) WHERE os = {$xxos} AND fabrica = {$login_fabrica}";
                            $res_motivo = pg_query($con, $sql_motivo);
                        }
                    }

					if(strlen($msg_erro) == 0 && (in_array($login_fabrica,array(131,140)) || (isset($novaTelaOs) && $login_fabrica != 145))){
						$sql = "SELECT fn_os_excluida ($1,$2,$3);";
						if(!pg_query_params($con,$sql,array($xxos,$login_fabrica,$login_admin)))
							$msg_erro .= pg_last_error($con);
					}

				}
				if(in_array($select_acao,array(14,15,16))){
                    switch($select_acao){
                        case 14:
                            $status_os_aprovada = 13;

                            $qryOsInfo = pg_query($con, "SELECT  posto FROM tbl_os WHERE os = $xxos");
                            $xxposto = pg_fetch_result($qryOsInfo, 0, 'posto');

                            $mensagem_comunicado = 'A OS ' . $xxos . ' foi recusada pelo fabricante.';
                            $mensagem_comunicado.= str_replace('Observação', 'Motivo', $observacao);

                            $insComunic = "INSERT INTO tbl_comunicado (fabrica, posto, mensagem, tipo, obrigatorio_site, ativo)
                                          VALUES ($login_fabrica, $xxposto, '$mensagem_comunicado', 'Comunicado', 't', 't')";
                            $qryComunic = pg_query($con, $insComunic);
                        break;
                        case 15:
                            $status_os_aprovada = 193;
                            require_once "../class/email/PHPMailer/PHPMailerAutoload.php";
                            require_once "../class/email/PHPMailer/class.phpmailer.php";

                            $mail = new PHPMailer;

                            $mail->isSMTP();

                            $mail->From         = "suporte@telecontrol.com.br";
                            $mail->FromName     = "Suporte Telecontrol";

                            foreach($emails as $chave=>$valor){
                                $mail->AddAddress($valor,$nomesCompletos[$chave]);
                            }

                            $mail->isHTML(true);
                            $mail->Subject      = "OS Indicada para troca";
                            $mensagem = "
                                Prezado(a);
                                <br /><br />A OS $xxos foi recusada pela auditoria de peças e será indicada para troca
                                <br />Atenciosamente,
                                <br />Suporte Telecontrol
                                <br />www.telecontrol.com.br
                                <br /><b><em>Esta é uma mensagem automática, não responda este e-mail.</em></b>
                            ";

                            $mail->Body = $mensagem;
                            $mail->send();
                        break;
                        case 16:
                            $status_os_aprovada = 81;

                            $sqlOs = "
                                UPDATE  tbl_os
                                SET     excluida = TRUE
                                WHERE   os = $xxos
                            ";
                            $resOs = pg_query($con,$sqlOs);
                        break;
                    }

                    $sql = "INSERT INTO tbl_os_status
							(os,status_os,data,observacao,admin)
							VALUES ($xxos,$status_os_aprovada,current_timestamp,'$observacao',$login_admin)";
					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}
			}

			if($login_fabrica >= 131 && in_array($status_da_os, array(67,68,70,157)) && $select_acao == 90 ){

				$sql_posto = "SELECT tbl_posto_fabrica.contato_email as email, tbl_posto_fabrica.posto FROM tbl_os JOIN tbl_posto_fabrica USING(posto, fabrica) WHERE os = $xxos;";
				$res_posto = @pg_exec($con, $sql_posto);

				if (@pg_numrows($res_posto) > 0) {

					$posto           = trim(pg_result($res_posto, 0, 'posto'));
					$remetente_email = trim(pg_result($res_posto, 0, 'email'));

				} else {

					$msg_erro = 'Erro ao buscar dados do posto!';

				}

				$sql = "INSERT INTO tbl_os_status (
							os,
							status_os,
							data,
							observacao,
							admin
						) VALUES (
							{$xxos},
							90,
							current_timestamp,
							'OS aprovada sem pagamento pelo fabricante na auditoria de OS reincidente',
							{$login_admin}
							)";
				$res = pg_query($con, $sql);
				$sql = "SELECT os FROM tbl_os_extra WHERE os = {$xxos}";
				$res = pg_query($con, $sql);

				$msg_erro .= pg_errormessage($con);

				if (pg_num_rows($res) == 0 ) {
					$sql = "INSERT INTO tbl_os_extra (os, extrato) VALUES ({$xxos}, 0)";
					$res = pg_query($con, $sql);
					$msg_erro .= pg_errormessage($con);
				} else {
					$sql = "UPDATE tbl_os_extra SET extrato = 0 WHERE os = {$xxos}";
					$res = pg_query($con, $sql);
					$msg_erro .= pg_errormessage($con);
				}


				if (!pg_last_error()) {
					if (strlen($observacao) > 0) {
						$observacao = trim(htmlentities($observacao,ENT_QUOTES,'UTF-8'));
						$mensagem_wanke = "&nbsp;<b>2</b> $observacao";
					}

					$assunto  = 'A O.S '.$xxos.' FOI APROVADA SEM PAGAMENTO PELA AUDITORIA DE REINCIDÊNCIA.';
					$mensagem = 'A O.S de Número '.$xxos.', foi aprovada sem pagamento pelo fabricante.'.$mensagem_wanke;


					$sql = "INSERT INTO tbl_comunicado (
								mensagem ,
								descricao ,
								tipo ,
								fabrica ,
								obrigatorio_site ,
								posto ,
								pais ,
								ativo ,
								remetente_email
							) VALUES (
								'$mensagem' ,
								'$assunto' ,
								'Comunicado' ,
								$login_fabrica ,
								't' ,
								$posto ,
								'BR' ,
								't' ,
								'$remetente_email'
							)";

					$res = pg_query($con, $sql);
				}

				if (pg_last_error()) {
					$msg_erro = "Erro ao aprovar OS";
				}
			} else if($select_acao == 90){
				$msg_erro = "A O.S.($xxos) não pode ser aprovada sem pagamento.";
			}
		}

		if (in_array($login_fabrica, array(141,144))) {
		    $sqlStatus = "SELECT fn_os_status_checkpoint_os({$xxos}) AS status;";
		    $resStatus = pg_query($con, $sqlStatus);

		    $statusCheckpoint = pg_fetch_result($resStatus, 0, "status");

		    $updateStatus = "UPDATE tbl_os SET status_checkpoint = {$statusCheckpoint} WHERE fabrica = {$login_fabrica} AND os = {$xxos}";
		    $resStatus = pg_query($con, $updateStatus);

		    if (strlen(pg_last_error()) > 0) {
		      $msg_erro = "Erro ao atualizar o status da Ordem de Serviço {$xxos}";
		    }
 		}

		if (strlen($msg_erro)==0){
			$res = pg_exec($con,"COMMIT TRANSACTION");
		}else{
			$res = pg_exec($con,"ROLLBACK TRANSACTION");
		}
	}
}

$layout_menu = "auditoria";
if($login_fabrica == 131 or $login_fabrica == 132 || $login_fabrica >= 134){
	$title = "Auditoria de OSs reincidentes ou peças excedentes";
}else if($login_fabrica == 30){
    $title = "Auditoria de peças excedentes";
}else{
	$title = "Auditoria de OSs reincidentes, sem peças ou peças excedentes";
}

include "cabecalho.php";

if ($login_fabrica == 30) {
	$plugins = array(
	    "shadowbox"
	);
}

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

<script language='javascript' src='ajax.js'></script>
<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>

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



function gravarInteracao(linha,os,tipo) {
	var comentario = $.trim($("#comentario_"+linha).val());

	if (comentario.length == 0) {
		alert("Insira uma mensagem para interagir");
	} else {
		$.ajax({
			url: "ajax_grava_interacao_new.php",
			type: "GET",
			data: {
				linha: linha,
				os: os,
				tipo: tipo,
				comentario: comentario
			},
			beforeSend: function () {
				$("#interacao_"+linha).hide();
				$("#loading_"+linha).show();
			},
			complete: function(data){
				data = data.responseText;

				if (data == "erro") {
					alert("Ocorreu um erro ao gravar interação");
				} else {
					$("#loading_"+linha).hide();
					$("#gravado_"+linha).show();

					setTimeout(function () {
						$("#gravado_"+linha).hide();
					}, 3000);

					$("#linha_"+linha).css({
						"background-color": "#FFCC00"
					});
				}

				$("#comentario_"+linha).val("");
				refreshInteracoes(linha, os);
			}
		});
	}
}

function box_interacao(os) {
	Shadowbox.open({
		content: "relatorio_interacao_os.php?interagir=true&os="+os,
		player: "iframe",
		width: 850,
		height: 600,
		title: "Ordem de Serviço "+os
	});
}

function abreInteracao(linha,os,tipo) {
	$.ajax({
		url: "ajax_grava_interacao.php",
		type: "GET",
		data: "linha="+linha+"&os="+os+"&tipo="+tipo,
		complete: function(data){
			resposta = data.responseText;
			resposta_array = resposta.split("|");
			resposta = resposta_array [0];
			linha = resposta_array [1];
			$('#interacao_'+linha).html(resposta);
			$('#comentario_'+linha).focus;

		}
	});
}


function refreshInteracoes(linha, os) {
	$.ajax({
		url: "ajax_refresh_interacao.php",
		type: "POST",
		data: {
			linha: linha,
			os: os
		},
		complete: function (data) {
			$("#interacao_"+linha).find("td[rel=interacoes]").html(data.responseText);
		}
	})
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
	}
	if (document.getElementById(mudarcor)) {
		document.getElementById(mudarcor).style.backgroundColor  = (document.getElementById(theCheckbox).checked ? "#FFF8D9" : cor);
	}
}


</script>

<? include "javascript_calendario_new.php"; //adicionado por Fabio 27-09-2007
include "../js/js_css.php";
?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		<?php if ($login_fabrica == 30) { ?>
				Shadowbox.init();		
		<?php } ?>
		$("#data_inicial").mask("99/99/9999");
		$("#data_final").mask("99/99/9999");
		$('#data_inicial').datepick({startDate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});

	});
</script>


<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />


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
	$tipo_os      = (!strlen($_POST['tipo_os'])) ? $tipo_os_auditoria : $_POST["tipo_os"];
    $regiao_comercial = trim($_POST['regiao_comercial']);
	$posto_estado = $_POST['posto_estado'];

	if (!strlen($tipo_os)) {
		$msg_erro .= "Informe o Tipo de OS.";
		$erro_pesquisa = true;
	}

	if((strlen($data_inicial) == 0 || strlen($data_final) == 0) && empty($os) && $aprova != "aprovacao"){
		$msg_erro .= "Data Inicial e Data Final é Obrigatorio.";
		$erro_pesquisa = true;
	}else{
		if (strlen($data_inicial) > 0) {
			$xdata_inicial = formata_data ($data_inicial);
			$xdata_inicial = $xdata_inicial." 00:00:00";
		}

		if (strlen($data_final) > 0) {
			$xdata_final = formata_data ($data_final);
			$xdata_final = $xdata_final." 23:59:59";
		}

		if (strlen($xdata_inicial) > 0 && strlen($xdata_final) > 0) {
			if(strtotime($xdata_inicial) > strtotime($xdata_final)){
				$msg_erro .= "Data Inicial não pode ser maior Data Final.";
				$erro_pesquisa = true;
			}

			if(strtotime($xdata_inicial." +3 months") < strtotime($xdata_final)){
				$msg_erro .= "O intervalo entre Datas não pode ser superiores a 3 meses.";
				$erro_pesquisa = true;
			}
		}

	}

	if (strlen($os)>0){
		$Xos = " AND os = $os ";
	}

	if (in_array($login_fabrica, array(30,50))) { //hd 71341 waldir

		$cond_excluidas = "AND tbl_os.excluida is not true ";

	}

	if(strlen($aprova) == 0){
		$aprova = "aprovacao";
		$aprovacao = "67,68,70,115,118";
	}elseif($aprova=="aprovacao"){
		$aprovacao = "67,68,70,115,118";
	}elseif($aprova=="aprovadas"){
		$aprovacao = "19,187,188";

		if (in_array($login_fabrica, array(131,141,140,144))) {
			$aprovacao .= ", 90";
		}
	}elseif($aprova=="reprovadas"){
		$aprovacao = "13,185,186";
		$cond_reprovada = " or tbl_os.fabrica = 0" ; 
	}

	if($login_fabrica == 50){
		if($aprova=="reprovadas"){
			$cond_excluidas = "";
		}
		$sql_tipo = "67,68,70,13,19,115,118,185,186,187,188";
		if($tipo_os =='reincidencia'){
			$sql_tipo = "67,68,70,19,13";
			switch($aprova){
				case "aprovacao": $aprovacao = "67,68,70"; break;
				case "aprovadas": $aprovacao = "19"; break;
				case "reprovadas": $aprovacao = "13"; break;
			}
		}elseif($tipo_os=="sem_peca"){
			$sql_tipo = "115,188,186";
			switch($aprova){
				case "aprovacao": $aprovacao = "115"; break;
				case "aprovadas": $aprovacao = "188"; break;
				case "reprovadas": $aprovacao = "186"; break;
			}
		}elseif($tipo_os=="mais_pecas"){
			$sql_tipo = "118,187,185";
			switch($aprova){
				case "aprovacao": $aprovacao = "118"; break;
				case "aprovadas": $aprovacao = "187"; break;
				case "reprovadas": $aprovacao = "185"; break;
			}
        } elseif ($tipo_os == 'defeito_constatado') {
            $sql_tipo = '13, 19, 20';
            switch($aprova){
                case "aprovacao": $aprovacao = "20"; break;
                case "aprovadas": $aprovacao = "19"; break;
                case "reprovadas": $aprovacao = "13"; break;
            }
		}

	}else{

		$sql_tipo = "67,68,70,13,19,115,118,139,187,188,191";
		if($tipo_os =='reincidencia'){
			$sql_tipo = "191,67,68,70,13,19,139";
		}elseif($tipo_os=="sem_peca"){
			$sql_tipo = "188,115,13,19";
		}elseif($tipo_os=="mais_pecas"){
			if(in_array($login_fabrica,array(30,50,131,132)) || ($login_fabrica >= 134)){
                if($login_fabrica != 30){
					$sql_tipo = "187,118,185";
                }else{
                    $sql_tipo = "13,81,118,185,187,193";
                }
			}else{
					$sql_tipo = "13,19,118";
			}
		}

	}

	if(in_array($login_fabrica, array(131,136,138,141,142,143,140,144,151))){
		$sql_tipo .= ',90,65';
	}


}

if(strlen($msg_erro) > 0){
	echo "<p align='center' style='font-size: 14px; font-family: verdana;'><FONT COLOR='#FF0000'><b>$msg_erro</FONT></b></p>";
}


?>

<form name="frm_pesquisa" method="post" action="<?echo $PHP_SELF?>">

<TABLE width="700" align="center" border="0" cellspacing='0' cellpadding='0' class='PesquisaTabela'>

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

<?php if($login_fabrica == 30){ $aAtendentes = hdBuscarAtendentes(); //hd_chamado=2537875 ?>

<TR>
	<td colspan='100%' align='left '>Inspetor<br>
		<select class='frm' name="admin_sap" id="admin_sap">
	      <option value=""></option>
	      <?php foreach($aAtendentes as $aAtendente): ?>
            <option value="<?php echo $aAtendente['admin']; ?>" <?php echo ($aAtendente['admin'] == $admin_sap) ? 'selected="selected"' : '' ; ?>><?php echo empty($aAtendente['nome_completo']) ? $aAtendente['login'] : $aAtendente['nome_completo'] ; ?></option>
         <?php endforeach; ?>
	   </select>
	</td>
</TR>

<?php } ?>


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
<?}
if(in_array($login_fabrica, array(142,151))){
?>
<tr>
    <td colspan="2">Estado Posto<br />
        <select name="posto_estado" class="frm">
            <option value="">&nbsp;</option>
<?
    foreach($array_estados() as $est=>$nome){
?>
            <option value="<?=$est?>" <?=($est == $posto_estado) ? "selected" : "" ;?>><?=$nome?></option>
<?
    }
?>
        </select>
    </td>
</tr>
<?
}
?>
<tr>
	<td colspan='2'>
		<b>Mostrar as OS:</b><br>

			<INPUT TYPE="radio" NAME="aprova" value='aprovacao' <? if(trim($aprova) == 'aprovacao' OR trim($aprova)==0) echo "checked='checked'"; ?>>Em aprovação &nbsp;&nbsp;&nbsp;
			<INPUT TYPE="radio" NAME="aprova" value='aprovadas' <? if(trim($aprova) == 'aprovadas') echo "checked='checked'"; ?>>Aprovadas  &nbsp;&nbsp;&nbsp;
			<INPUT TYPE="radio" NAME="aprova" value='reprovadas' <? if(trim($aprova) == 'reprovadas') echo "checked='checked'"; ?>>Reprovadas &nbsp;&nbsp;&nbsp;
	</td>
</tr>

<tr>
	<td colspan='2'>
		<b>Tipo de OS:</b><br>

			<INPUT TYPE="radio" NAME="tipo_os" value='reincidencia' <? if(trim($tipo_os) == 'reincidencia') echo "checked='checked'"; ?>>Reincidência&nbsp;&nbsp;&nbsp;
			<? if(!in_array($login_fabrica,array(30,127,131,132,136)) && $login_fabrica < 137){ ?>
				<INPUT TYPE="radio" NAME="tipo_os" value='sem_peca' <? if(trim($tipo_os) == 'sem_peca') echo "checked='checked'"; ?>>OS sem peça  &nbsp;&nbsp;&nbsp;
			<? } ?>
			<INPUT TYPE="radio" NAME="tipo_os" value='mais_pecas' <? if(trim($tipo_os) == 'mais_pecas') echo "checked='checked'"; ?>>OS com peças excedentes &nbsp;&nbsp;&nbsp;
            <?php if ($login_fabrica == '50'): ?>
                <INPUT TYPE="radio" NAME="tipo_os" value='defeito_constatado' <? if(trim($tipo_os) == 'defeito_constatado') echo "checked='checked'"; ?>>Defeito constatado &nbsp;&nbsp;&nbsp;
            <?php endif ?>
	</td>
</tr>

</tbody>
<TR>
	<TD colspan="2" align='center'>
		<br>
		<input type='hidden' name='btn_acao' value=''>
		<IMG src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='Pesquisar'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer " alt='Clique AQUI para pesquisar'>
	</TD>
</TR>
<tr><td colspan="100%">&nbsp;</td></tr>
</table>
</form>


<?
if ((strlen($btn_acao)  > 0 || strlen($msg_erro) > 0) && !isset($erro_pesquisa)) {
	$posto_codigo= trim($_POST["posto_codigo"]);


if($login_fabrica == 30){ //hd_chamado=2537875
	if(strlen($admin_sap) > 0){
		$admin_sap = (int) $_POST['admin_sap'];
		$cond_admin_sap = " AND tbl_posto_fabrica.admin_sap = $admin_sap";
	}

	if(strlen($posto_codigo)>0){ //hd_chamado=2537875
		$sql = " SELECT tbl_posto_fabrica.posto,
							tbl_posto.nome AS nome_posto
				FROM tbl_posto_fabrica
				JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica
				AND   tbl_posto_fabrica.codigo_posto = '$posto_codigo' ";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
			$nome_posto = pg_fetch_result($res, 0, 'nome_posto');
		}
	}
}



	if(strlen($posto_codigo)>0)         $sql_add .= " AND tbl_posto_fabrica.codigo_posto = '$posto_codigo' ";

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
            $sql_add .= " AND tbl_posto.estado IN ($estados) ";
		}
	}

	if(strlen($posto_estado) > 0){
        $sql_add .= " AND tbl_posto.estado = '$posto_estado' ";
	}

	if($aprova == 'aprovadas' or $aprova =='reprovadas') {
		$sql_cond ="		AND   interv.primeiro_status in ($sql_tipo)
			AND   interv.primeiro_status not in ($aprovacao) ";

	}

	if(in_array($login_fabrica, array(138))){
		$joinProduto = " JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto";
		$leftJoin = "LEFT JOIN tbl_os ON tbl_os.os = X.os
					LEFT JOIN   tbl_os_produto ON tbl_os_produto.os = tbl_os.os
				     LEFT JOIN tbl_produto              ON tbl_produto.produto = tbl_os_produto.produto";
	}else{
		$joinProduto = "JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto ";
		$leftJoin    = " LEFT JOIN tbl_os ON tbl_os.os = X.os
						 LEFT JOIN tbl_produto              ON tbl_produto.produto = tbl_os.produto ";

		$campoProduto = "tbl_produto.referencia             AS produto_referencia    ,
					tbl_produto.descricao              AS produto_descricao     ,
					tbl_produto.voltagem                                        , ";
	}

	if( $login_fabrica == 30 AND $tipo_os == 'mais_pecas' ){
			$attr_tbl_os_status = ",	(SELECT to_char(tbl_os_status.data,'DD/MM/YYYY') FROM tbl_os_status JOIN tbl_status_os USING(status_os) WHERE fabrica_status = $login_fabrica and tbl_os.os = tbl_os_status.os AND status_os IN ($sql_tipo) ORDER BY data DESC LIMIT 1) AS data_pedido ";
	}else{
		$attr_tbl_os_status = '';
		$join_tbl_os_status = '';
	}

	$sql =  "SELECT interv.os
			 INTO TEMP tmp_interv_$login_admin
			 FROM (
				SELECT
					ultima.os,
					(
						SELECT status_os
						FROM tbl_os_status
						WHERE fabrica_status = $login_fabrica
						AND status_os IN ($sql_tipo)
						AND tbl_os_status.os = ultima.os
						ORDER BY data DESC
						LIMIT 1
					) AS ultimo_status,
					(
						SELECT status_os
						FROM tbl_os_status
						WHERE fabrica_status = $login_fabrica
						AND status_os IN ($sql_tipo)
						AND tbl_os_status.os = ultima.os
						ORDER BY os_status
						LIMIT 1
					) AS primeiro_status
				FROM (
					SELECT DISTINCT os
					FROM tbl_os_status
					WHERE fabrica_status = $login_fabrica
					AND status_os IN ($sql_tipo)
					".((strlen($xdata_inicial) > 0 AND strlen($xdata_final) > 0) ?	"AND data BETWEEN '$xdata_inicial' AND '$xdata_final'" : "")."
					$Xos
				) ultima
			) interv
			WHERE interv.ultimo_status IN ($aprovacao)
			$sql_cond
			$Xos;

			CREATE INDEX tmp_interv_OS_$login_admin ON tmp_interv_$login_admin(os);


			SELECT	DISTINCT tbl_os.os                                                   ,
					tbl_os.sua_os                                               ,
					tbl_os.consumidor_nome                                      ,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')  AS data_abertura,
					TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
					tbl_os.fabrica                                              ,
					tbl_os.consumidor_nome                                      ,
					tbl_posto.posto                                             ,
					tbl_posto.nome                     AS posto_nome            ,
					tbl_posto_fabrica.codigo_posto                              ,
					tbl_posto_fabrica.contato_email       AS posto_email        ,
					$campoProduto
					(SELECT status_os FROM tbl_os_status WHERE fabrica_status = $login_fabrica and tbl_os.os = tbl_os_status.os AND status_os IN ($sql_tipo) ORDER BY data DESC LIMIT 1) AS status_os         ,
					(SELECT observacao FROM tbl_os_status WHERE fabrica_status = $login_fabrica and tbl_os.os = tbl_os_status.os AND status_os IN ($sql_tipo) ORDER BY data DESC LIMIT 1) AS status_observacao,
					(SELECT tbl_status_os.descricao FROM tbl_os_status JOIN tbl_status_os USING(status_os) WHERE fabrica_status = $login_fabrica and tbl_os.os = tbl_os_status.os AND status_os IN ($sql_tipo) ORDER BY data DESC LIMIT 1) AS status_descricao
					$attr_tbl_os_status
				FROM tmp_interv_$login_admin X
				$leftJoin
				INNER JOIN tbl_posto                ON tbl_os.posto        = tbl_posto.posto
				INNER JOIN tbl_posto_fabrica        ON tbl_posto.posto     = tbl_posto_fabrica.posto
				$join_tbl_os_status
				AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE (tbl_os.fabrica = $login_fabrica $cond_reprovada ) 
				AND finalizada isnull
				";

	if (strlen($xdata_inicial) > 0 AND strlen($xdata_final) > 0) {
		$sql .= " AND tbl_os.data_digitacao BETWEEN '$xdata_inicial' AND '$xdata_final'
				 ";
	}
		$sql.=" $cond_excluidas
				$sql_add
				$cond_admin_sap
		ORDER BY tbl_posto_fabrica.codigo_posto,tbl_os.os";
	$res = pg_exec($con,$sql);
	// var_dump(pg_last_error());


	if(pg_numrows($res)>0){

		echo "<BR><BR><table width='98%' border='0' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 11px' bgcolor='#FFFFFF'>
					<tr>
						<td bgcolor='#FFCC00' width='30'>&nbsp;</td>
						<td align='left'>Fábrica interagiu</td>
					</tr>

					<tr>
						<td bgcolor='#669900' width='30'>&nbsp;</td>
						<td align='left'>Posto interagiu</td>
					</tr>
			  </table>";

		echo "<FORM name='frm_pesquisa2' METHOD='POST' ACTION='$PHP_SELF'>";

		echo "<input type='hidden' name='data_inicial'   value='$data_inicial'>";
		echo "<input type='hidden' name='data_final'     value='$data_final'>";
		echo "<input type='hidden' name='aprova'         value='$aprova'>";
		echo "<input type='hidden' name='os'         value='$os'>";
		echo "<input type='hidden' id='tipo_os_auditoria' name='tipo_os_auditoria' value='{$tipo_os}' />";

		echo "<table width='98%' border='0' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 11px' bgcolor='#FFFFFF'>";
		echo "<tr>";
		echo "<td bgcolor='#485989'><img border='0' src='imagens_admin/selecione_todas.gif' onclick='javascript: checkaTodos()' alt='Selecionar todos' style='cursor: hand;' align='center'></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>OS</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>DATA <br>DIGITAÇÃO</B></font></td>";

		if( $login_fabrica == 30 AND $tipo_os == 'mais_pecas' ){
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>DATA <br>PEDIDO</B></font></td>";
		}

		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Posto</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Email</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Produto</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Descrição</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>STATUS</B></font></td>";
		if ($login_fabrica == 30 or $login_fabrica == 50 or $login_fabrica == 127 or $login_fabrica == 131 or $login_fabrica == 132 or $login_fabrica >= 134) {
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Interação</B></font></td>";
		}
		if(isset($novaTelaOs)){
			echo "<td bgcolor='#485989'><strong style='color: #fff;'>Ações</strong></td>";
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
			$posto_email			= pg_result($res, $x, posto_email);
			$produto_referencia		= pg_result($res, $x, produto_referencia);
			$produto_descricao		= pg_result($res, $x, produto_descricao);
			$produto_voltagem		= pg_result($res, $x, voltagem);
			$data_digitacao			= pg_result($res, $x, data_digitacao);
			$data_abertura			= pg_result($res, $x, data_abertura);
			$status_os				= pg_result($res, $x, status_os);
			$status_observacao		= pg_result($res, $x, status_observacao);
			$status_descricao		= pg_result($res, $x, status_descricao);
			$posto                  = pg_result($res, $x, posto);
			$data_pedido			= pg_result($res, $x, 'data_pedido');

			$cores++;
			$cor = ($cores % 2 == 0) ? "#FEFEFE": '#E8EBEE';

			if ($login_fabrica == 50 || $login_fabrica == 127 || $login_fabrica == 131 || $login_fabrica == 132 || $login_fabrica >= 134 || $login_fabrica == 30) {

				$sqlint = "SELECT os_interacao,admin from tbl_os_interacao
				WHERE os = $os
				AND interno IS NOT TRUE
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

			if($login_fabrica == 30){
				echo "<input type=\"hidden\" value=\"$posto_codigo\" name=\"posto_codigo\" />"; //hd_chamado=2537875
				echo "<input type=\"hidden\" value=\"$nome_posto\" name=\"posto_nome\" />"; //hd_chamado=2537875
				echo "<input type=\"hidden\" value=\"$admin_sap\" name=\"admin_sap\" />"; //hd_chamado=2537875
			}


			if(strlen($sua_os)==o)$sua_os=$os;
			echo "<tr bgcolor='$cor' id='linha_$x'>";
			echo "<td align='center' width='0'>";
				if(in_array($status_os,array(20,67,68,70,115,118))){
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

			if( $login_fabrica == 30 AND $tipo_os == 'mais_pecas' ){
				echo "<td style='font-size: 9px; font-family: verdana'>". $data_pedido . "</td>";
			}

			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap title='".$codigo_posto." - ".$posto_nome."'>".$codigo_posto." - ".substr($posto_nome,0,20) ."...</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap ><a href='mailto:$posto_email'>$posto_email</a></td>";


			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap><acronym title='Produto: $produto_referencia' style='cursor: help'>". $produto_referencia ."</acronym></td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap><acronym title='Produto: $produto_referencia - $produto_descricao' style='cursor: help'>". $produto_descricao ."</acronym></td>";
			echo "<td style='font-size: 9px; font-family: verdana' nowrap><acronym title='Observação: ".$status_observacao."'>".$status_descricao. "</acronym></td>";
			if (($login_fabrica == 30 or $login_fabrica == 50 or $login_fabrica == 127 or $login_fabrica == 131 or $login_fabrica == 132 || $login_fabrica >= 134) and $aprova <> 'reprovadas') {

				$sqlint = "SELECT os_interacao,admin from tbl_os_interacao
				WHERE os = $os
				AND interno IS NOT TRUE
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

				if ($login_fabrica == 30) {
					echo "<td><div style='cursor: pointer;' onclick='box_interacao($os)' >$botao</div></td>";
				} else {
					echo "<td><div id=btn_interacao_".$x." style='cursor: pointer;' onclick='if ($(\"#interacao_{$x}\").is(\":visible\")) { $(\"#interacao_{$x}\").hide(); } else { $(\"#interacao_{$x}\").show(); }' >$botao</div></td>";
				}
			}

			if(isset($novaTelaOs)){
				if($login_fabrica == 147){
					$select_produto = "SELECT produto from tbl_os where os = {$os} and fabrica = {$login_fabrica}";
					$res_produto    = pg_query($con, $select_produto);
                  	if ((pg_num_rows($res_produto) > 0 && in_array(pg_fetch_result($res_produto, 0, "produto"), array(234103)))) {
                  		echo "<td></td>";
              		}else{
                  		if($tipo_os == "mais_pecas"){
							echo "<td> &nbsp; ";
							echo "<a href='os_troca_subconjunto.php?os={$os}' target='_blank'><img src='imagens/btn_trocar.gif' /></a></td>";
						}
                  	}
				}else{

					echo "<td>";

						if($tipo_os == "mais_pecas"){
							echo " &nbsp; ";
							echo "<a href='os_troca_subconjunto.php?os={$os}' target='_blank'><img src='imagens/btn_trocar.gif' /></a>";
						}

					echo "</td>";
				}
			}

			echo "</tr>";

			if ($login_fabrica == 50 or $login_fabrica == 127 or $login_fabrica == 131 or $login_fabrica == 132 or $login_fabrica >= 134) {
			?>
				<tr>
					<td colspan="9" >
						<div id="loading_<?=$x?>" style="display: none;"><img src="imagens/ajax-loader.gif" /></div>
						<div id="gravado_<?=$x?>" style="font-size: 14px; background-color: #669900; color: #FFFFFF; font-weight: bold; display: none;">Interação gravada</div>
						<div id="interacao_<?=$x?>" style="display: none;">
						    <table border="0" cellspacing="1" cellpadding="0" class="tabela" style="width: 700px; margin: 0 auto;" >
						        <tr>
						            <th>INTERAGIR NA OS</th>
						        </tr>
						        <tr>
						            <td class="conteudo" style="text-align: center;" >
						            	<textarea name="comentario_<?=$x?>" id="comentario_<?=$x?>" style="width: 400px;"></textarea>
						            </td>
						        </tr>
								<?php
								$sql_i = "SELECT
											tbl_os_interacao.os_interacao,
										  	to_char(tbl_os_interacao.data,'DD/MM/YYYY HH24:MI') as data,
											tbl_os_interacao.comentario,
											tbl_os_interacao.interno,
											tbl_os.posto,
				                            tbl_posto_fabrica.contato_email as email,
											tbl_admin.nome_completo
										  FROM tbl_os_interacao
										  JOIN tbl_os            ON tbl_os.os    = tbl_os_interacao.os
										  JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
										  LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_os_interacao.admin
										  WHERE tbl_os_interacao.os = $os
										  AND tbl_os.fabrica = {$login_fabrica}
										  AND tbl_os_interacao.interno IS NOT TRUE
										  ORDER BY tbl_os_interacao.os_interacao DESC";
								$res_i  = pg_query($con, $sql_i);
								?>
						        <tr>
						            <td rel="interacoes">
						            	<?php
						            	if (pg_num_rows($res_i) > 0) {
										?>
							                <table border="0" cellspacing="1" cellpadding="0" style="width: 700px; margin: 0 auto;" >
							                    <thead>
							                        <tr>
							                            <th class="titulo">Nº</th>
							                            <th class="titulo">Data</th>
							                            <th class="titulo">Mensagem</th>
							                            <th class="titulo">Admin</th>
							                        </tr>
							                    </thead>
							                    <tbody>
													<?php
													$k = 1;

													while ($result_i = pg_fetch_array($res_i)) {
														if ($result_i["interno"] == 't') {
															$cor = "style='font-family: Arial; font-size: 8pt; font-weight: bold; text-align: left; background: #F3F5CF;'";
														} else {
															$cor = "class='conteudo'";
														}
														?>
								                        <tr>
								                            <td width="25" <?=$cor?> ><?=$k?></td>
								                            <td width="90" <?=$cor?> nowrap ><?=$result_i["data"]?></td>
								                            <td <?=$cor?> ><?=$result_i["comentario"]?></td>
								                            <td <?=$cor?> nowrap ><?=$result_i["nome_completo"]?></td>
								                        </tr>
													<?php
														$k++;
													}
													?>
							                    </tbody>
							                </table>
						                <?php
										}
										?>
						            </td>
						        </tr>
						    </table>
						    <br />
						    <img src="imagens/btn_gravar.gif" style="cursor:pointer" onclick="gravarInteracao(<?=$x?>, <?=$os?>, 'Gravar');">
						</div>
					</td>
				</tr>
			<?php
		}
			if ($login_fabrica == 50 or $login_fabrica == 131) {
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
			if(in_array($login_fabrica,array(138)) && $_REQUEST['tipo_os'] == 'mais_pecas'){
				echo '<option value="65" '.($_POST['select_acao']==65?'selected="selected"':'').' >Reparar na Fabrica</option>';
			}
			else if ($login_fabrica >= 132 && $tipo_os == "reincidencia") {
				echo "<option value='90'"; if ($_POST["select_acao"] == "90") echo " selected"; echo ">OS APROVADA SEM PAGAMENTO</option>";
			}

			echo "<option value='19'";  if ($_POST["select_acao"] == "19")  echo " selected"; echo ">APROVADO</option>";
			if($login_fabrica != 30){
                echo "<option value='13'";  if ($_POST["select_acao"] == "13")  echo " selected"; echo ">RECUSADO</option>";
			}else{
//                 echo "<option value='14'";  if ($_POST["select_acao"] == "14")  echo " selected"; echo ">RECUSADO (Retorno do Posto)</option>";
                echo "<option value='15'";  if ($_POST["select_acao"] == "15")  echo " selected"; echo ">RECUSADO (Gerar Troca)</option>";
                echo "<option value='16'";  if ($_POST["select_acao"] == "16")  echo " selected"; echo ">RECUSADO (Cancelar OS)</option>";
			}
			echo "</select>";
			echo "&nbsp;&nbsp; <font color='#FFFFFF'><b>Motivo:<b></font> <input class='frm' type='text' name='observacao' id='observacao' size='30' maxlength='250' value='' >";
			echo "&nbsp;&nbsp;<img src='imagens/btn_gravar.gif' style='cursor:pointer' onclick='javascript: document.frm_pesquisa2.submit()' style='cursor: hand;' border='0'></td>";
		}
		echo "<input type='hidden' name='btn_acao' value='Pesquisar'>";
		echo "</table>";
		echo "<p>TOTAL OS: $total_os</p>";
		echo "</form>";
	}else{
		echo "<br /><center>Nenhum OS encontrada.</center>";
	}
	$msg_erro = '';
}

echo "<br><br>";
include "rodape.php";


function reparoNaFabrica(){
	$oss = ArrayHelper::findWithRegex($_REQUEST,'@check_[0-9]+@');
	$observacao = $_REQUEST['observacao'];
	$observacao = 'Reparo do produto deve ser feito pela fábrica. '.$observacao;
	if(empty($oss)){
		return;
	}
	$model = ModelHolder::init('OS');
	foreach ($oss as $os) {
		$model->repairInFactory($os,$observacao);
	}
}
