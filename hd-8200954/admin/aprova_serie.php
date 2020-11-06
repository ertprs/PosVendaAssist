<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="call_center";
include "autentica_admin.php";
include 'funcoes.php';
include "../helpdesk.inc.php";// Funcoes de HelpDesk hd_chamado=2537875

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

	if(strlen($observacao) == 0 && $select_acao != "103"){
		$msg_erro .= "Informe o motivo.";
	}

	if (empty($observacao) && $select_acao == "103") {
		$observacao = "OS aprovada pelo fabricante";
	}

    if ($login_fabrica == 30 && $select_acao == 104) {
        $observacao = "Ordem de serviço reprovada em auditoria de número de série. Motivo: ".$observacao.".<br>Qualquer dúvida entrar em contato com a fábrica.";
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
					WHERE status_os IN (64,67)
					AND os = $xxos
					ORDER BY data DESC
					LIMIT 1";
			$res_os = pg_query($con,$sql);

			if(pg_num_rows($res) > 0){
				$status_da_os = trim(pg_fetch_result($res_os,0,"status_os"));
			}

			if($status_da_os != 67){
				$sql = "SELECT status_os
					FROM tbl_os_status
					WHERE status_os IN (102,103,104)
					AND os = $xxos
					ORDER BY data DESC
					LIMIT 1";
				$res_os = pg_query($con,$sql);

				if(pg_num_rows($res) > 0){
					$status_da_os = trim(pg_fetch_result($res_os,0,"status_os"));
				}

			}

			if ($status_da_os == 102 or $status_da_os == 67){
				$status_aprova = ($status_da_os == 102) ? 103 : 64;
				//Aprovada
				if($select_acao == "103"){
					$sql = "INSERT INTO tbl_os_status
							(os,status_os,data,observacao,admin)
							VALUES ($xxos,$status_aprova,current_timestamp,$observacao,$login_admin)";
					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);

					$esmaltec_acao = 'aprovada';

				}
				//Recusada04/05/2010
				if($select_acao == "104"){
					$sql = "INSERT INTO tbl_os_status
							(os,status_os,data,observacao,admin)
							VALUES ($xxos,104,current_timestamp,$observacao,$login_admin)";
					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);

					$esmaltec_acao = 'reprovada';

					// Regras para auditoria de NS e Reincidência - HD-2539696 (Esmaltec[30])
					if (in_array($login_fabrica, array(30))) {

						$sql = "UPDATE tbl_os SET
								cancelada  = 't'
								WHERE os = $xxos
								AND fabrica = $login_fabrica ";
						$res = pg_query($con,$sql);
						$msg_erro .= pg_last_error($con);

					}

					if (/*$login_fabrica == 30 or*/ $login_fabrica == 50 OR $login_fabrica >= 131) {
						$sql = "UPDATE tbl_os SET
								excluida  = 't'
								WHERE os = $xxos
								AND fabrica = $login_fabrica ";
						$res = pg_exec($con,$sql);
						$msg_erro .= pg_errormessage($con);

						//if(in_array($login_fabrica, array(30,140)) || (isset($novaTelaOs) && $login_fabrica != 145)) {
						if(in_array($login_fabrica, array(50,140)) || (isset($novaTelaOs) && $login_fabrica != 145)) {
							if ($login_fabrica != 50) {
								$sql = "SELECT fn_os_excluida($xxos,$login_fabrica,$login_admin);";
								$res = pg_exec($con,$sql);
								$msg_erro .= pg_errormessage($con);
							}

							if($login_fabrica == 50 || $login_fabrica >= 131){

								$sql_posto = "SELECT posto FROM tbl_os WHERE os = {$xxos}";
								$res_posto = pg_query($con, $sql_posto);
								$posto = pg_fetch_result($res_posto, 0, 'posto');

								if ($login_fabrica == 50) {
									$titulo = "";
									$texto = "A O.S {$xxos} foi reprovada pela auditoria da fábrica - Motivo: N° de série inválido - Favor verificar e nos retornar através do 0800 770 8541";
								}else{
									$titulo = "OS {$xxos} Reprovada - Intervenção de Número de Série";
									$texto = $observacao;
									$sql_motivo = "UPDATE tbl_os_excluida SET motivo_exclusao = {$observacao} WHERE os = {$xxos} AND fabrica = {$login_fabrica}";
									$res_motivo = pg_query($con, $sql_motivo);
								}

								$sql = "INSERT INTO tbl_comunicado (
													descricao              ,
													mensagem               ,
													tipo                   ,
													fabrica                ,
													obrigatorio_os_produto ,
													obrigatorio_site       ,
													posto                  ,
													ativo
												) VALUES (
													'$titulo',
													$texto,
													'Número de Série',
													$login_fabrica,
													'f' ,
													't',
													$posto,
													't'
												);";

								$res       = pg_query($con,$sql);
								$msg_erro .= pg_errormessage($con);
							}

						}
					}

					if ($login_fabrica == 120 or $login_fabrica == 201) {
						$sql = "UPDATE tbl_os
								SET
								data_fechamento = CURRENT_TIMESTAMP,
								finalizada = CURRENT_TIMESTAMP
								WHERE os = $xxos
								AND fabrica = $login_fabrica";
						$res = pg_query($con, $sql);
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

			if (in_array($login_fabrica, array(141,144))) {
                $sqlStatus = "SELECT fn_os_status_checkpoint_os({$xxos}) AS status;";
                $resStatus = pg_query($con, $sqlStatus);

                $statusCheckpoint = pg_fetch_result($resStatus, 0, "status");

                $updateStatus = "UPDATE tbl_os SET status_checkpoint = {$statusCheckpoint} WHERE fabrica = {$login_fabrica} AND os = {$xxos}";
                $resStatus = pg_query($con, $updateStatus);

                if (strlen(pg_last_error()) > 0) {
                  $msg_erro = "Erro ao alterar status da OS $xxos";
                }
              }

			if (strlen($msg_erro)==0){
				/**
				 * @since HD 261434 - enviar email pro posto
				 */
				if ($login_fabrica == 30 and empty($msg_erro)) {
					$sqlPostoeMail = "SELECT tbl_posto_fabrica.contato_email, tbl_os.sua_os
						FROM tbl_posto_fabrica
						JOIN tbl_os ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
						WHERE os = $xxos";
					$resPostoeMail = pg_query($con, $sqlPostoeMail);

					if (pg_num_rows($resPostoeMail) == 0) {
						$sqlPostoeMail2 = "SELECT tbl_posto.email, tbl_os.sua_os FROM tbl_posto JOIN tbl_os USING (posto) where os = $xxos";
						$resPostoeMail2 = pg_query($con, $sqlPostoeMail2);

						if (pg_num_rows() == 1) {
							$posto_email  = pg_fetch_result($resPostoeMail2, 0, 'email');
							$sua_os_email = pg_fetch_result($resPostoeMail2, 0, 'sua_os');
						}
					} else {
						$posto_email  = pg_fetch_result($resPostoeMail, 0, 'contato_email');
						$sua_os_email = pg_fetch_result($resPostoeMail, 0, 'sua_os');
					}

					if (!empty($posto_email)) {
						$sqlAdminNome = "select nome_completo from tbl_admin where admin = $login_admin";
						$qryAdminNome = pg_query($con, $sqlAdminNome);
						$nome_admin = pg_fetch_result($qryAdminNome, 0, 'nome_completo');

						$assunto = 'O.S. ' . $sua_os_email  . ' ' . $esmaltec_acao . ' da Auditoria por Número de Série';
						$msg = 'A OS ' . $sua_os_email . ' foi ' . $esmaltec_acao . ' da Auditoria por Número de Série por ' . $nome_admin . ' da Esmaltec. ';
						$msg .= '<br/><br/>';

						$motivo_msg = str_replace("'", "", $observacao);
						$msg .= str_replace("Observação", "Motivo", $motivo_msg);

						$headers  = 'MIME-Version: 1.0' . "\r\n";
						$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
						$headers .= 'From: Esmaltec <auditoria.sae@esmaltec.com.br>' . "\r\n";

						mail($posto_email, utf8_encode($assunto), utf8_encode($msg), $headers);
					}

				}

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

if ($login_fabrica == 30) {
	$plugins = array (
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

<? include "javascript_calendario_new.php"; //adicionado por Fabio 27-09-2007 ?>
<? include "../js/js_css.php";?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		<?php if ($login_fabrica == 30) { ?>
				Shadowbox.init();
		<?php } ?>

		$('#data_inicial').datepick({startDate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$("#data_inicial").mask("99/99/9999");
		$("#data_final").mask("99/99/9999");

		//$("input[@rel='data_nf']").maskedinput("99/99/9999");
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


function abreInteracao(linha,os,tipo) {
	$.get(
		'ajax_grava_interacao.php',
		{
			linha:linha,
			os:os,
			tipo:tipo
		},
		function (resposta){
			resposta_array = resposta.split("|");
			resposta = resposta_array [0];
			linha = resposta_array [1];
			$('#interacao_'+linha).html(resposta);
			$('#comentario_'+linha).focus;

		}
	)

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

	$gerar_excel = $_POST["gerar_excel"];

	if($gerar_excel == 't'){
        $data = date("d-m-Y-h-i");
        $arquivo_completo = "xls/aprova_serie_$login_fabrica"."_$data.csv";
        $excel = fopen ($arquivo_completo,"w+");
	}


	if($aprova != "aprovacao" and $login_fabrica == 30){
		if ((strlen($data_inicial) == 0 or strlen($data_final) == 0) && empty($os)){
			$msg_erro = "Informe o período inicial e final";
		}
	}

    $Jos = '';

	if (strlen($os)>0){
        $Xos = " AND os = $os ";

        if ($login_fabrica == 50) {
            $Jos = " JOIN tbl_os USING(os) ";
            $Xos = " AND tbl_os.sua_os = '$os' ";
        }
	}

	if($login_fabrica == 15){
		if(strlen($aprova) == 0){
		    $aprova = "aprovacao";
		    $aprovacao = "67,102";
		}elseif($aprova=="aprovacao"){
		    $aprovacao = "67,102";
		}elseif($aprova=="aprovadas"){
		    $aprovacao = "64,103";
		}elseif($aprova=="reprovadas"){
		    $aprovacao = "104";
		}
	}else{
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
		if ($aprovacao != 104) {
			$cond_excluidas = "AND tbl_os.excluida is not true ";
		}
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
<?php if($login_fabrica == 30) {?>
<tr>
	<Td colspan="2" align='center' style="color:#ff0000">Ao selecionar a opção  em aprovação, não é necessario digitar filtro de data, serão exibidas todas Ordens de Serviço abertas no período de 6 meses </td><Tr>
<?}?>
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

<?if ($login_fabrica==30){ $aAtendentes = hdBuscarAtendentes(); ?>

	<!-- hd_chamado=2537875 -->
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
	<!-- FIM hd_chamado=2537875 -->

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

	  <?php if($login_fabrica == 30){ ?>
	  		<tr>
	  			<td>Gerar Excel <input type="checkbox" name="gerar_excel" value="t"></td>
	  			<td></td>
	  		</tr>
	  <?php } ?>
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

	if(in_array($login_fabrica, array(142))){
		$join_produto = " JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os JOIN tbl_produto ON tbl_os_produto.produto = tbl_produto.produto ";
	}else{
		$join_produto = " JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto ";
	}

	if($login_fabrica == 30){ //hd_chamado=2537875

		$posto_codigo = trim($_POST["codigo_posto_off"]);

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

	$sql1 = "SELECT revenda.os
		INTO TEMP tmp_interv1_$login_admin
		FROM (
			SELECT ultima_serie.os, (
				SELECT status_os
				FROM tbl_os_status
                $Jos
				WHERE tbl_os_status.os  = ultima_serie.os
				$Xos
				AND tbl_os_status.fabrica_status = $login_fabrica
				AND status_os IN (64,67)
				ORDER BY os_status DESC LIMIT 1) AS ultimo_serie_status
				FROM (
					SELECT DISTINCT os
					FROM tbl_os_status
                    $Jos
					WHERE tbl_os_status.fabrica_status = $login_fabrica
					$Xos
					AND status_os IN (64,67)
				) ultima_serie) revenda
				WHERE revenda.ultimo_serie_status IN ($aprovacao)";
	$res1 = pg_query($con,$sql1);

	$sql2 = "SELECT revenda.os
		INTO TEMP tmp_interv2_$login_admin
		FROM (
			SELECT ultima_serie.os, (
				SELECT status_os
				FROM tbl_os_status
                $Jos
				WHERE tbl_os_status.os  = ultima_serie.os
				$Xos
				AND tbl_os_status.fabrica_status = $login_fabrica
				AND status_os IN (102,103,104)
				ORDER BY os_status DESC LIMIT 1) AS ultimo_serie_status
				FROM (
					SELECT DISTINCT os
					FROM tbl_os_status
                    $Jos
					WHERE tbl_os_status.fabrica_status = $login_fabrica
					$Xos
					AND status_os IN (102,103,104)
				) ultima_serie) revenda
				WHERE revenda.ultimo_serie_status IN ($aprovacao)";
	$res2 = pg_query($con,$sql2);

	$sql = "SELECT	DISTINCT tbl_os.os                                                   ,
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
					tbl_os_status.status_os            AS status_os             ,
					tbl_os_status.admin                AS admin                 ,
					tbl_os_status.observacao           AS status_observacao     ,
					tbl_status_os.descricao            AS status_descricao
				FROM tbl_os
				JOIN tbl_os_status ON tbl_os_status.os = tbl_os.os AND tbl_os_status.fabrica_status=$login_fabrica
				JOIN tbl_status_os ON tbl_status_os.status_os = tbl_os_status.status_os
				$join_produto
				JOIN tbl_posto                ON tbl_os.posto        = tbl_posto.posto
				JOIN tbl_posto_fabrica        ON tbl_posto.posto     = tbl_posto_fabrica.posto
				AND tbl_posto_fabrica.fabrica = $login_fabrica
				$cond_admin_sap";

	if ($login_fabrica == 30) {
		if ($aprovacao == 104) {
			$sql .= " WHERE ((tbl_os.fabrica = 0 AND tbl_os.excluida = 't') OR (tbl_os.fabrica = $login_fabrica AND tbl_os.cancelada = 't'))";
		}else{
			$sql .= " WHERE tbl_os.fabrica = $login_fabrica";
		}
		if (strlen($estado)>0){
			$sql .= "AND tbl_posto_fabrica.contato_estado = '$estado'";
		}
	}else{
		$sql .= " WHERE tbl_os.fabrica = $login_fabrica";
	}

	if(!empty($os)) {
		$sql .=  " and (tbl_os.os = {$os} or tbl_os.sua_os ='{$os}') ";
	}
	if(strlen($codigo_posto_off)>0){
		$sql .= " AND tbl_posto_fabrica.codigo_posto = '$codigo_posto_off' ";
	}
	$sql.= " AND (tbl_os.os IN(SELECT os FROM tmp_interv1_$login_admin) OR tbl_os.os IN(SELECT os FROM tmp_interv2_$login_admin)) AND tbl_os_status.status_os IN($aprovacao)";
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
	if (strlen($xdata_inicial) > 0 AND strlen($xdata_final) > 0 AND $login_fabrica != 30) {
		$sql .= " AND tbl_os.data_digitacao BETWEEN '$xdata_inicial' AND '$xdata_final'
				$cond_excluidas
				ORDER BY tbl_posto_fabrica.codigo_posto,tbl_os.os ";
	}

	if($login_fabrica == 30){
		if (strlen($xdata_inicial) > 0 AND strlen($xdata_final) > 0) {
			$sql .= " AND tbl_os_status.data BETWEEN '$xdata_inicial' AND '$xdata_final'
					$cond_excluidas
					ORDER BY tbl_posto_fabrica.codigo_posto,tbl_os.os ";
		}
		if (strlen(trim($xdata_inicial)) == 0 AND strlen(trim($xdata_final)) == 0 and $aprova == "aprovacao" AND strlen($Xos) == 0) {
			$sql .= " and  tbl_os.data_digitacao BETWEEN (current_date-interval '180 days')  AND current_date
					$cond_excluidas
					ORDER BY tbl_posto_fabrica.codigo_posto,tbl_os.os ";
		}
	}
	$res = pg_exec($con,$sql);

	if(pg_numrows($res)>0){

		if(in_array($login_fabrica,array(30,50)) OR $login_fabrica >= 131){

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
		}
		echo "<br> <FORM name='frm_pesquisa2' METHOD='POST' ACTION='$PHP_SELF'>";

		echo "<input type='hidden' name='data_inicial'   value='$data_inicial'>";
		echo "<input type='hidden' name='data_final'     value='$data_final'>";
		echo "<input type='hidden' name='aprova'         value='$aprova'>";
		echo "<table width='98%' id='table_aprova' border='0' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 11px' bgcolor='#FFFFFF'>";
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
		if ($login_fabrica == 30 and ($aprovacao == 102)){
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>DATA <br>Intervenção</B></font></td>";
		}
		if ($login_fabrica == 30 and ($aprovacao == 103)){
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>DATA <br>Aprovação</B></font></td>";
		}
		if ($login_fabrica == 30 and $aprovacao == 104){
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>DATA <br>Recusada</B></font></td>";
		}
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>OBSERVAÇÃO</B></font></td>";
		if ($login_fabrica == 30 or $login_fabrica == 50 or $login_fabrica >= 131) {
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Interação</B></font></td>";
		}
		echo "</tr>";

		if($gerar_excel == 't'){
			$thead = "OS;".utf8_encode('Data Digitação')."; Data Abertura;Posto;UF;Produto;".utf8_encode('Descrição').";".utf8_encode('Série').";Status;Admin;".utf8_encode('Data Aprovação').";".utf8_encode('Observação').";\r\n";
			$escreve = fwrite($excel, $thead);
		}

		$cores = '';
		$qtde_intervencao = 0;
		//$os_arr = array();
		$tbody = "";

		$total_os = pg_num_rows($res);

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

			if($gerar_excel == "t"){
				$tbody .= "$sua_os;$data_digitacao;$data_abertura;$codigo_posto-".utf8_encode("$posto_nome").";$contato_estado;$produto_referencia;$produto_descricao;$produto_serie;$status_descricao;";
			}

			/*if(in_array($os, $os_arr)){
				continue;
			}else{
				$os_arr[] = $os;
			}*/

                if($login_fabrica == 30){ //hd_chamado=2537875
                    echo "<input type=\"hidden\" value=\"$posto_codigo\" name=\"codigo_posto_off\" />";
                    echo "<input type=\"hidden\" value=\"$nome_posto\" name=\"posto_nome_off\" />";
                    echo "<input type=\"hidden\" value=\"$admin_sap\" name=\"admin_sap\" />";
                }


                if ($login_fabrica == 50 or $login_fabrica >= 131) {

                    $sqlint = "SELECT os_interacao,admin from tbl_os_interacao
                    WHERE os = $os
                    AND interno IS NOT TRUE
                    ORDER BY os_interacao DESC limit 1";
                    $resint = pg_exec($con,$sqlint);
                    if(pg_num_rows($resint)>0) {
                        $admin = pg_result($resint,0,admin);
                        if (strlen($admin)>0) {
                            $cor = "#FFCC00";
                        } else {
                            $cor = "#669900";
                        }
                    }
                }

                echo "<tr bgcolor='$cor' id='linha_$x'>";
                echo "<td align='center' width='0'>";

				if($status_os==102 or $status_os == 67){
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
                    if($gerar_excel == 't'){
                    	$tbody .= ";";
                    }
                }else{
                        $sql_login = "select login from tbl_admin where admin = $admin";
                        $res_login = @pg_exec($con,$sql_login);
                        $login_status = @pg_result($res_login,0,login);
                        echo "<td style='font-size: 9px; font-family: verdana' nowrap>$login_status</td>";
                        if($gerar_excel == 't'){
                        	$tbody .= "$login_status;";
                        }
                }
                if ($login_fabrica == 30){
                    echo "<td style='font-size: 9px; font-family: verdana'>".$data_status. "</td>";
                }
                echo "<td style='font-size: 9px; font-family: verdana' nowrap>".$status_observacao."</td>";

                $tbody .=  "$data_status;".utf8_encode("$status_observacao").";\r\n";

                if ($login_fabrica == 30 or $login_fabrica == 50 or $login_fabrica >= 131) {
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
                        }else {
                            $botao = "<img src='imagens/btn_interagir_verde.gif' title='Posto Respondeu, clique aqui para visualizar'>";
                        }
                    }
                    
                    if ($login_fabrica == 30) {
                    	echo "<td><div id='btn_interacao_".$x."' style='cursor: pointer;' onclick='box_interacao($os)' >$botao</div></td>";
                    } else {
                    	echo "<td><div id='btn_interacao_".$x."' style='cursor: pointer;' onclick='if ($(\"#interacao_{$x}\").is(\":visible\")) { $(\"#interacao_{$x}\").hide(); } else { $(\"#interacao_{$x}\").show(); }' >$botao</div></td>";	
                    }
                }
                echo "</tr>";
                if ($login_fabrica == 50 or $login_fabrica >= 131) {
?>
				<tr>
					<td colspan="14" >
						<div id="loading_<?=$x?>" style="display: none;"><img src="imagens/ajax-loader.gif" /></div>
						<div id="gravado_<?=$x?>" style="font-size: 14px; background-color: #669900; color: #FFFFFF; font-weight: bold; display: none;">Interação gravada</div>
						<div id="interacao_<?=$x?>" style="margin: 0 auto; display: none;">
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
}

if($gerar_excel){
  fwrite($excel, $tbody);
  fclose($excel);
}

			//$total_os = count($os_arr);
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
            echo "<br>";
            if($login_fabrica == 30 and $gerar_excel == 't'){
	          echo "<br /> <a href='$arquivo_completo' target='_blank'><img src='imagens/excel.png' height='20px' width='20px' align='absmiddle'>&nbsp;&nbsp;Gerar Arquivo Excel</a> <br />";
	        }
    }else{
        echo "<center>Nenhum OS encontrada.</center>";
        $msg_erro = '';
    }
}
include "rodape.php" ?>
