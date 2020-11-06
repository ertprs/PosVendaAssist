<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="gerencia";

include "autentica_admin.php";
include 'funcoes.php';
require_once '../class/email/mailer/class.phpmailer.php';
include_once '../class/tdocs.class.php';

$ajax = $_GET['ajax'];
$ressarcimento = $_GET['ressarcimento'];


if ($ajax == "atualizaPrevisao") {
	list($d, $m, $y) = explode("/",$_GET['previsao_pag']);
	$previsao_banco = $y.'-'.$m.'-'.$d;

	$sql = "SELECT previsao_pagamento
			AS previsao
			FROM tbl_ressarcimento
			WHERE ressarcimento = $ressarcimento
			AND fabrica = $login_fabrica";
	$res = pg_query($con,$sql);
	while ($linha = pg_fetch_array($res)) {
		$previsao_antiga = $linha['previsao'];
	}


	if (($previsao_banco != $previsao_antiga && date('d/m/Y', strtotime($_GET['previsao_pag'])) > date("d/m/Y")) || $login_fabrica == 101) {
		$sql = "UPDATE tbl_ressarcimento
				   SET previsao_pagamento = '{$previsao_banco}'
				 WHERE ressarcimento = $ressarcimento
				   AND fabrica       = $login_fabrica";
		$res = pg_query($con,$sql);

		/*HD-4096786*/
		if ($login_fabrica == 101) {
			$aux_sql = "SELECT os FROM tbl_ressarcimento WHERE ressarcimento = $ressarcimento LIMIT 1";
			$aux_res = pg_query($con, $aux_sql);
			$aux_os  = pg_fetch_result($aux_res, 0, 0);

			if (!empty($aux_os)) {
				$aux_sql = "UPDATE tbl_os SET ressarcimento = true WHERE os = $aux_os RETURNING ressarcimento";
				$aux_res = pg_query($con, $aux_sql);
				$aux_rss = pg_fetch_result($aux_res, 0, 0);

				if ($aux_rss != 't') {
					echo "Erro ao atualizar a \"Previsão de Pagamento\"";
				} else {
					$aux_res = "SELECT data_fechamento, finalizada FROM tbl_os WHERE os = $aux_res";
					$aux_res = pg_query($con, $aux_sql);
					$aux_fec = pg_fetch_result($aux_res, 0, "data_fechamento");
					$aux_fin = pg_fetch_result($aux_res, 0, "finalizada");

					if (empty($aux_fec) || empty($aux_fin)) {
						$aux_sql = "UPDATE tbl_os SET data_fechamento = current_timestamp, finalizada = current_timestamp WHERE os = $aux_os AND fabrica = $login_fabrica";
                       $aux_res = pg_query($con,$aux_sql);
					}
				}
			}
		}
	} else {
		exit;
	}
}

if ($ajax == "aprovar") {

	$sql = "UPDATE tbl_ressarcimento
			   SET aprovado      = CURRENT_TIMESTAMP,
				   admin_aprova  = $login_admin
			 WHERE ressarcimento = $ressarcimento
			   AND fabrica       = $login_fabrica";

	$res = pg_query($con,$sql);

	if (strlen(pg_last_error($con) == 0)) {
		echo "ok";
	}

	// HD-940807
	if ($telecontrol_distrib) {
		$os 	 	 = $_GET['os'];
		$atendimento = $_GET['atendimento'];
		$nome 		 = $_GET['nome'];

		$mailer = new PHPMailer(); //Class para envio de email com autenticação no servidor

		// Envia e-mail
		if ($login_fabrica == 81) {
			$email_fab = "juliane.santosdasilva@la.spectrumbrands.com";
		} else if ($login_fabrica == 114) {
			$email_fab  = "commercial1@cobimex.com.br";
		} else if ($login_fabrica == 147) {
			//$email_fab  = "felipe.marttos@telecontrol.com.br";teste
			$email_fab  = "celso@telecontrol.com.br";
		} else{
			$email_fab  =  "claudio.silva@telecontrol.com.br";
		}

		$email_para = array($email_fab , "celso@telecontrol.com.br");
		$assunto	= "Ressarcimento Aprovado" . ($atendimento == null ? '' : (' - ' . $atendimento))." - ".$login_fabrica_nome;
		$mensagem	= "Foi aprovado o ressarcimento, favor verificar para efetuar o depósito.";
		$mensagem  .= "<br/><br/>OS: " . ($os == null ? 'Não informado' : $os);
		$mensagem  .= "<br/>Atendimento: " . ($atendimento == null ? 'Não informado' : $atendimento);
		$mensagem  .= "<br/>Nome do consumidor: " . (empty($nome) ? 'Não informado' : $nome);

		$mailer->IsSMTP();
	    $mailer->IsHTML();

	    foreach ($email_para as $email) {
	   		$mailer->AddAddress($email);
	    }

	    $mailer->Subject = $assunto;
	    $mailer->Body 	 = $mensagem;

		$mailer->Send();
	}

	exit;
}

if ($ajax == "finalizar") {

	$sql = "UPDATE tbl_ressarcimento
			   SET finalizado    = CURRENT_TIMESTAMP
			 WHERE ressarcimento = $ressarcimento
			   AND fabrica       = $login_fabrica";
	$res = pg_query($con,$sql);
	if (strlen(pg_last_error($con) == 0)) {
		echo "ok";
	}

	exit;
}

if ($ajax == "liberar") {

	$sql = "UPDATE tbl_ressarcimento
			   SET liberado      = CURRENT_DATE
			 WHERE ressarcimento = $ressarcimento
			   AND fabrica       = $login_fabrica";
	$res = pg_query($con,$sql);
	if(strlen(pg_last_error($con) == 0)){
		echo "ok";

		if($telecontrol_distrib){
			$os 	 	 = $_GET['os'];
			$atendimento = $_GET['atendimento'];
			$nome 		 = $_GET['nome'];

			$mailer = new PHPMailer(); //Class para envio de email com autenticação no servidor

			// Envia e-mail

			switch ($login_fabrica) {
				case '81':
					$email = "juliane.santosdasilva@la.spectrumbrands.com";
					break;
				case '114':
					$email = "atendimento@cobimex.com.br, thaliane.souza@cobimex.com.br";
					break;
				case '125':
					$email = "lauro.miguel@saint-gobain.com";
					break;
				case '147':
					$email = "serizawa@hitachi-koki.com.br";
					break;
				default:
					$email = "danilo.alves@telecontrol.com.br";
					break;
			}


			$assunto	= "Ressarcimento Liberado" . ($atendimento == null ? '' : (' - ' . $atendimento))." - ".$login_fabrica_nome;
			$mensagem	= "Foi liberado o ressarcimento, favor verificar para efetuar aprovação.";
			$mensagem  .= "<br/><br/>OS: " . ($os == null ? 'Não informado' : $os);
			$mensagem  .= "<br/>Atendimento: " . ($atendimento == null ? 'Não informado' : $atendimento);
			$mensagem  .= "<br/>Nome do consumidor: " . (empty($nome) ? 'Não informado' : $nome);

			$mailer->IsSMTP();
		    $mailer->IsHTML();

		    #foreach ($email_para as $email) {
		   		$mailer->AddAddress($email);
		    #}

		    $mailer->Subject = $assunto;
		    $mailer->Body 	 = $mensagem;

			$mailer->Send();
		}
	}
	exit;
}

if ($ajax == "deletar") {

	$sql = "DELETE FROM tbl_ressarcimento
			 WHERE ressarcimento = $ressarcimento
			   AND fabrica       = $login_fabrica";
	$res = pg_query($con,$sql);
	if (strlen(pg_last_error($con) == 0)) {
		echo "ok";
	}

	exit;
}

if($ajax == "lote"){
	$ressarcimentos = $_GET['ressarcimentos'];

	$sql = "UPDATE tbl_ressarcimento
			   SET lote_fechado      = CURRENT_DATE
			 WHERE ressarcimento in ($ressarcimentos)
			   AND fabrica       = $login_fabrica";
	$res = pg_query($con,$sql);
	if(strlen(pg_last_error($con) == 0)){
		echo "ok";
	}

	exit;
}

$btn_acao = $_POST['btn_acao'];
if($login_fabrica == 114){
	if($btn_acao == "Anexar"){
		$comprovante = $_FILES['comprovante'];
		$ressarcimento = $_POST["ressarcimento"];
		if(strlen($_FILES['comprovante']['name']) > 0 ){

			$extensao = array('pdf','jpg','jpeg','pjpeg','gif','png');
			$ext_tipo = explode('/',$_FILES['comprovante']['type']);

			if(!in_array($ext_tipo[1],$extensao)){
				$msg_erro .= "Tipo de arquivo inválido. Os tipos permitidos são: PDF,JPG,JPEG,GIF,PNG";
			}else{
				$tamanho = 2048000; // Tamanho máximo do arquivo (em bytes)
				if ($_FILES['comprovante']["size"] > $tamanho){
					$msg_erro = "Arquivo tem tamanho muito grande! Deve ser de no máximo 2MB. Envie outro arquivo.";
				}
				$arqNotaTemp = $_FILES['comprovante']['tmp_name'];
				$diretorio = 'ressarcimento_comprovante/';
				list($a,$ext) = explode('/',$_FILES['comprovante']['type']);
				$arqName = "comprovante_".$ressarcimento.".".$ext;
				$arqName = $diretorio . $arqName;
				system("mv $arqNotaTemp $arqName");

				if(file_exists($arqName)){
						$sql = "UPDATE tbl_ressarcimento SET
							anexo = '".$arqName."'
						WHERE ressarcimento = $ressarcimento
						AND fabrica = $login_fabrica";
						$res = pg_query($con,$sql);
					if(strlen(pg_last_error($con)) == 0 ){
						header("location: ressarcimento_consulta.php?msg=ok&upload=ok");
					}else{
						$msg_erro = "Erro ao realizar upload";
					}
				}else{
					$msg_erro = "Erro ao realizar upload";
				}
			}
		}
	}
}

if($btn_acao == 'Finalizar'){
	$ressarcimento_finaliza = $_POST['ressarcimento_finaliza'];

	if($_FILES['comprovante']['name']){
		$extensao = array('pdf','jpg','jpeg','pjpeg','gif','png');
		$ext_tipo = explode('/',$_FILES['comprovante']['type']);

		if(!in_array($ext_tipo[1],$extensao)){
			$msg_erro .= "Tipo de arquivo inválido. Os tipos permitidos são: PDF,JPG,JPEG,GIF,PNG";
		}else{
			$tamanho = 2048000; // Tamanho máximo do arquivo (em bytes)
			if ($_FILES['comprovante']["size"] > $tamanho){
				$msg_erro = "Arquivo tem tamanho muito grande! Deve ser de no máximo 2MB. Envie outro arquivo.";
			}
		}
	}else{
		$msg_erro = "Comprovante não selecionado!";
	}

	$tDocs = new TDocs($con, $login_fabrica);
	if (empty($msg_erro)) {
		$anexoID = $tDocs->getdocumentsByRef($ressarcimento_finaliza, "comprovante")->hasattachment;
		if (!$anexoID) {
			$anexoID = $tDocs->uploadFileS3($_FILES['comprovante'], $ressarcimento_finaliza, true, "comprovante");
		}

		pg_query($con,'BEGIN');
		$sql = "UPDATE tbl_ressarcimento SET
				finalizado = CURRENT_TIMESTAMP,
				anexo = null
				WHERE ressarcimento = $ressarcimento_finaliza
				AND fabrica = $login_fabrica";
		$res = pg_query($con,$sql);

		if(!strlen(pg_last_error($con)) and $anexoID) {
			pg_query($con,'COMMIT');
		}else{
			pg_query($con,'ROLLBACK');
		}
	}
}

#Pega o POST
if($btn_acao == "Consultar"){
	$ressarcimento		= $_POST['ressarcimento'];
	$os					= $_POST['os_atendimento'];
	$atendimento		= $_POST['atendimento'];
	$nome				= $_POST['nome'];
	$status				= $_POST['status'];
	$data_inicial		= $_POST['data_inicial'];
	$data_final			= $_POST['data_final'];
	$data_tipo			= $_POST['data'];

	if(!empty($os) OR !empty($atendiemnto)){
		$cond = (!empty($os)) ? " AND os = $os " : " AND atendiemnto = $atendimento ";
	}else{
		if(!empty($nome)){
			$nome = strtoupper($nome);
			$cond = " AND upper(tbl_ressarcimento.nome) LIKE '$nome%' ";
		}

		if(!empty($data_inicial) AND !empty($data_final)){
			list($d, $m, $y) = explode("/", $data_inicial);
			if(!checkdate($m,$d,$y)){
				$msg_erro = "Data Inválida";
			}

			list($df, $mf, $yf) = explode("/", $data_final);
			if(!checkdate($mf,$df,$yf)){
				$msg_erro = "Data Inválida";
			}

			if(empty($msg_erro)){
				$nova_data_inicial = "$y-$m-$d";
				$nova_data_final = "$yf-$mf-$df";

				if(strtotime($nova_data_final) < strtotime($nova_data_inicial)){
					$msg_erro = "Data inicial não pode ser maior do que data final";
				}
				switch($data_tipo){
					case 'abertura':   $cond .= " AND tbl_ressarcimento.data_input BETWEEN '$nova_data_inicial 00:00:00' and '$nova_data_final 23:59:59' ";break;
					case 'aprovado':   $cond .= " AND aprovado   BETWEEN '$nova_data_inicial 00:00:00' and '$nova_data_final 23:59:59' ";break;
					case 'finalizado': $cond .= " AND finalizado BETWEEN '$nova_data_inicial 00:00:00' and '$nova_data_final 23:59:59' ";break;
				}
			}


		}

		if(!empty($status)){
			switch($status){
				case 'lote_fechado':   	$cond .= " AND lote_fechado   IS NOT NULL "; break;
				case 'ag_liberacao':   	$cond .= " AND liberado   IS NULL AND aprovado IS NULL "; break;
				case 'pendente'    :	$cond .= " AND aprovado   IS NULL     AND finalizado IS NULL "; break;
				case 'aprovado'    :	$cond .= " AND aprovado   IS NOT NULL AND finalizado IS NULL "; break;
				case 'finalizado'  :	$cond .= " AND finalizado IS NOT NULL AND lote_fechado IS NULL "; break;
			}
		}
	}
}

$layout_menu = "gerencia";
$title = "CADASTRO DE RESSARCIMENTO";
include "cabecalho.php";

?>

<style type="text/css">

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}
.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
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
	font:bold 11px Arial;
	text-align:left;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.espaco_left{
	padding-left:170px;
}

.mensagem_sucesso {
	color: green;
}

</style>

<link rel="stylesheet" type="text/css" href="plugins/jquery/datepick/telecontrol.datepick.css">
<script type='text/javascript' src='js/jquery.alphanumeric.js'></script>
<script type='text/javascript' src='plugins/jquery.mask.js'></script>
<script type="text/javascript" src="plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<script type='text/javascript'>
	$(function(){

		$("#msg_sucesso").delay(5000).fadeOut("slow");

		$("input[name=os_atendimento]").numeric();
		$("input[name=atendimento]").numeric();
		$("input[name=agencia]").numeric({allow:".-"});
		$("input[name=conta]").numeric({allow:".-"});
		$("input[name=cpf]").mask("999.999.999-99");

		$("input[name=data_inicial]").datepick({startDate:'01/01/2000'});
		$("input[name=data_final]").datepick({startDate:'01/01/2000'});
		$("input[name=data_inicial]").mask("99/99/9999");
		$("input[name=data_final]").mask("99/99/9999");

		$("input[name=previsao_pagamento]").mask("99/99/9999");

			$('input[id^=previsao_pagamento_]').blur(function(){
				var previsao = $(this).val();
				var ressarcimento = $(this).attr("rel");

				$.ajax({
					type: "GET",
					url: "<?=$PHP_SELF?>",
					data: {
		                ajax:"atualizaPrevisao",
		                ressarcimento:ressarcimento,
		                previsao_pag:previsao
		            }
		       	})

		        .done(function(http) {

		        	var msg_ressarcimento = $('#msg_'+ressarcimento);
		        	var data_atual = $('#data_atual').val();

					var compara1 = parseInt(previsao.split("/")[2].toString() + previsao.split("/")[1].toString() + previsao.split("/")[0].toString());

					var compara2 = parseInt(data_atual.split("/")[2].toString() + data_atual.split("/")[1].toString() + data_atual.split("/")[0].toString());

		        	if ($(msg_ressarcimento).is(":hidden")) {
		        		$(msg_ressarcimento).show();
		        		$(msg_ressarcimento).text(" ");
		        	}

		        	if (compara1 < compara2) {
		        		$(msg_ressarcimento).css({color : 'red' });
	       				$(msg_ressarcimento).text("Data menor que a atual");

	      			} else if(compara1 >= compara2) {
	      				$(msg_ressarcimento).css({color : 'green' });
	      				$(msg_ressarcimento).text("Campo modificado");
	      			}

	      			setTimeout(function () {
							$(msg_ressarcimento).hide();
						}, 4000);
	    		})

	    		.fail(function (){

	   			 });
			})


	});



	function exibeLinha(ressarcimento){
			$('#btn_finalizar_'+ressarcimento).fadeOut('slow');

		if( $('#linha_'+ressarcimento).is(':visible') ){
			$('#linha_'+ressarcimento).hide('slow');
		}else{
			$('#linha_'+ressarcimento).show('slow');
		}
	}

	function aprovaRessarcimento(ressarcimento, os, atendimento, nome){

		$.ajax({
			type: "GET",
			url: "<?=$PHP_SELF?>",
			beforeSend: function(){
				$('#col_'+ressarcimento).html("Aguarde...&nbsp;&nbsp;<img src='js/loadingAnimation.gif'> ");
			},
			data: {
                ajax:"aprovar",
                ressarcimento:ressarcimento,
                os:os,
                atendimento:atendimento,
                nome:nome
			}
		})
        .done(function(http) {
            results = http.responseText;
            $('#col_'+ressarcimento).html("Aprovado");
        })
        .fail(function (){
            $('#col_'+ressarcimento).html("<font color='#FF0000'>Erro ao aprovar ressarcimento</font>");
        });
	}

	function finalizaRessarcimento(ressarcimento){
		$.ajax({
			type: "GET",
			url: "<?=$PHP_SELF?>",
			beforeSend: function(){
				$('#col_'+ressarcimento).html("Aguarde...&nbsp;&nbsp;<img src='js/loadingAnimation.gif'> ");
			},
			data: {
                ajax:"finalizar",
                ressarcimento:ressarcimento,
                os:os,
                atendimento:atendimento,
                nome:nome
			}
		})
        .done(function(http) {
            results = http.responseText;
            $('#col_'+ressarcimento).html("Finalizado");
        })
        .fail(function (){
            $('#col_'+ressarcimento).html("<font color='#FF0000'>Erro ao finalizar ressarcimento</font>");
        });
	}

	function deletaRessarcimento(ressarcimento){
        if (confirm("Deseja excluir o ressarcimento?")) {
            $.ajax({
                type: "GET",
                url: "<?=$PHP_SELF?>",
                data: {
                    ajax:"deletar",
                    ressarcimento:ressarcimento
                }
            })
            .done(function(http) {
                results = http.responseText;
                $('#col_'+ressarcimento).html("Excluido com sucesso");
                $('#row_'+ressarcimento).delay(5000).fadeOut();
            })
            .fail(function (){
                $('#col_'+ressarcimento).html("<font color='#FF0000'>Erro ao excluir ressarcimento</font>");
            });
        }
	}

	function liberarRessarcimento(ressarcimento, os, atendimento, nome){
		$.ajax({
			type: "GET",
			url: "<?=$PHP_SELF?>",
			beforeSend: function(){
				$('#col_'+ressarcimento).html("Aguarde...&nbsp;&nbsp;<img src='js/loadingAnimation.gif'> ");
			},
			data: {
                ajax:"liberar",
                ressarcimento:ressarcimento,
                os:os,
                atendimento:atendimento,
                nome:nome
            } ,
		})
        .done(function(http) {
            results = http.responseText;
            $('#col_'+ressarcimento).html("Liberado");
        })
        .fail(function (){
            $('#col_'+ressarcimento).html("<font color='#FF0000'>Erro ao liberar ressarcimento</font>");
        });
	}

	function finalizarLote(){
		var ressarcimentos = '';
		$("input[type=checkbox][class='lote']:checked").each(function(){
            if(ressarcimentos == ''){
                ressarcimentos += $(this).val();
            }else{
                ressarcimentos += ','+$(this).val();
            }
		});

		$.ajax({
			type: "GET",
			url: "<?=$PHP_SELF?>",
			beforeSend: function(){
				$("input[type=checkbox][class='lote']:checked").each(function(){
					$(this).parent().attr('class','lote_finalizado');
					$(this).parent().html("Aguarde...&nbsp;&nbsp;<img src='js/loadingAnimation.gif'> ");
				});
			},
			data: {
                ajax:"lote",
                ressarcimentos:ressarcimentos
            }
		})
        .done(function(http) {
            results = http.responseText;
            $(".lote_finalizado").html("Lote Finalizado");
        })
		.fail(function (){
            $('#col_'+ressarcimento).html("<font color='#FF0000'>Erro ao finalizar lote</font>");
        });
	}

	function geraExcel(os,atendimento,nome,data_inicial,data_final,status,data){
		$.ajax({
			type: "GET",
			url: "ressarcimento_xls_ajax.php",
			beforeSend: function(){
				$('#excel').html("Gerando...&nbsp;&nbsp;<img src='js/loadingAnimation.gif'> ");
			},
			data: {
                os:os,
                atendimento:atendimento,
                nome:nome,
                data_inicial:data_inicial,
                data_final:data_final,
                status:status,
                data:data
			}
		})
        .done(function(http) {
            $('#excel').html("<center><input type='button' value='Download Excel' onclick=\"window.open('"+http+"')\">");
        })
		.fail(function (){
            $('#excel').html("<font color='#FF0000'>Erro ao gerar arquivo</font>");
        });
	}


</script>

<?php if(!empty($msg_erro)){ ?>
	<table align="center" width="700">
		<tr class='msg_erro'><td><?=$msg_erro?></td></tr>
	</table>
<?php } ?>

<?php if($_GET['msg'] == "ok" && !isset($_GET["upload"])){ ?>
	<div id="msg_sucesso">
	<table align="center" width="700">
		<tr class='sucesso'><td>Finalizado com sucesso</td></tr>
	</table>
</div>
<?php } ?>
<?php if($_GET['msg'] == "ok" && isset($_GET["upload"])){ ?>
	<div id="msg_sucesso">
	<table align="center" width="700">
		<tr class='sucesso'><td>Upload realizado com sucesso</td></tr>
	</table>
</div>
<?php } ?>
<form name="frm_cadastro" method="post" enctype="multipart/form-data">
	<table width="700" align="center" class="formulario">
		<caption class="titulo_tabela">Cadastro</caption>

		<tr><td colspan='3'>&nbsp;</td></tr>

		<tr>
			<td class="espaco_left" width='200'>
				Ordem de Serviço <br /> <input type="text" name="os_atendimento" value="<?=$os?>" class="frm">
			</td>
			<td colspan='2'>
				Atendimento <br /> <input type="text" name="atendimento" value="<?=$atendimento?>" class="frm">
			</td>
		</tr>

		<tr><td colspan='3'>&nbsp;</td></tr>

		<tr>
			<td class="espaco_left" colspan='3'>
				Consumidor <br /> <input type="text" name="nome" value="<?=$nome?>" size="54" class="frm">
			</td>
		</tr>

		<tr><td colspan='3'>&nbsp;</td></tr>

		<tr>
			<td class="espaco_left" width='200'>
				Data inicial <br />
				<input type="text" name="data_inicial" value="<?=$data_inicial?>" class="frm">
			</td>
			<td colspan='2'>
				Data final <br />
				<input type="text" name="data_final" value="<?=$data_final?>" class="frm">
			</td>
		</tr>

		<tr>
			<td class="espaco_left"colspan='3'>
				<fieldset style='width:300px;'>
					<legend>Tipo da Data</legend>
					<input type="radio" name="data" value="abertura" checked>ABERTURA &nbsp;
					<input type="radio" name="data" value="aprovado" <? echo ($data_tipo == "aprovado") ? "checked" : ""; ?>>APROVAÇÃO &nbsp;
					<input type="radio" name="data" value="finalizado" <? echo ($data_tipo == "finalizado") ? "checked" : ""; ?>>FINALIZAÇÃO
				</fieldset>
			</td>
		</tr>

		<tr><td colspan='3'>&nbsp;</td></tr>

		<tr>
	<?php
				if(in_array($login_admin,array(8750,9155,7263,8286,8518,7264,7837,8015,6144,9135,9332,4789,5613,4276,8636,9827,10749,10764,9213,5717,9814,9214,5520))){ //Login do Jader e Celso
					$width = "style='width:550px;'";
					$padding = "style='padding-left:60px;'";
				}else{
					$width = "style='width:400px;'";
					$padding = "style='padding-left:120px;'";
				}

				/*HD-4096786*/
				if ($login_fabrica == 101) {
					$width = "style='width:570px;'";
					$padding = "style='padding-left:50px;'";
				}

			?>
			<td  colspan='3' <?=$padding?>>
				<fieldset <?=$width?>>
					<legend>Status</legend>
					<?php 	if(in_array($login_admin,array(8750,9155,7263,8286,8518,7264,7837,8015,6144,9135,9332,5613,4276,8636,9827,10749,10764,9213,5717,9814,9214,5520,11107,11103,11104,11105,11108,11106,11102)) || $login_fabrica == 101){ //Login do Jader ?>
					<input type="radio" name="status" value="ag_liberacao" <? echo ($status == "ag_liberacao") ? "checked" : ""; ?>>AGUARDANDO LIBERAÇÃO &nbsp;
					<?php } ?>
					<input type="radio" name="status" value="pendente" <? echo ($status == "pendente") ? "checked" : ""; ?>>PENDENTE &nbsp;
					<input type="radio" name="status" value="aprovado" <? echo ($status == "aprovado") ? "checked" : ""; ?>>APROVADO &nbsp;
					<input type="radio" name="status" value="finalizado" <? echo ($status == "finalizado") ? "checked" : ""; ?>>FINALIZADO
					<input type="radio" name="status" value="lote_fechado" <? echo ($status == "lote_fechado") ? "checked" : ""; ?>>LOTE FECHADO
				</fieldset>
			</td>
		</tr>

		<tr><td colspan='3'>&nbsp;</td></tr>

		<tr>
			<td colspan='3' align='center'>
				<input type="hidden" name="ressarcimento">
				<input type='submit' name='btn_acao' value='Consultar'>
				<input type='button' value='Cadastrar Novo' onclick="javascript: window.open('ressarcimento_cadastro.php')">
			</td>
		</tr>

		<tr><td colspan='3'>&nbsp;</td></tr>

	</table>
</form>
<br />

<?php

if($btn_acao == "Consultar" AND empty($msg_erro)){
	$sql = "SELECT tbl_ressarcimento.ressarcimento,
					tbl_ressarcimento.os,
					tbl_ressarcimento.hd_chamado,
					tbl_ressarcimento.nome,
					tbl_ressarcimento.previsao_pagamento,
					tbl_ressarcimento.cpf,
					tbl_ressarcimento.tipo_conta,
					tbl_banco.nome AS banco,
					tbl_banco.codigo AS codigo_banco,
					agencia,
					conta,
					valor_original,
					valor_alterado,
					CASE WHEN valor_alterado > 0 THEN
						valor_alterado
					ELSE
						valor_original
					END AS valor,
					urgencia,
					CASE
						WHEN tbl_ressarcimento.admin_altera is not null THEN
							tbl_ressarcimento.admin_altera
						ELSE
							tbl_ressarcimento.admin
					END AS admin,
					CASE
						WHEN liberado is null THEN
							'Aguardando Liberação'
						WHEN aprovado is null THEN
							'Pendente'
						WHEN finalizado is not null AND lote_fechado is null THEN
							'Finalizado'
						WHEN lote_fechado is not null THEN
							'Lote Fechado'
						ELSE
							'Aprovado'
					END AS status,
					TO_CHAR(tbl_ressarcimento.data_input,'DD/MM/YYYY') AS abertura,
					TO_CHAR(aprovado,'DD/MM/YYYY') AS aprovado,
					TO_CHAR(finalizado,'DD/MM/YYYY') AS finalizado,
					anexo,
					tbl_admin.login,
					TO_CHAR(lote_fechado,'DD/MM/YYYY') AS lote_fechado,
					CASE
						WHEN tbl_produto.referencia IS NOT NULL THEN
							tbl_produto.referencia
						ELSE
							PHE.referencia
					END AS referencia,
					CASE
						WHEN tbl_produto.descricao IS NOT NULL THEN
							tbl_produto.descricao
						ELSE
							PHE.descricao
					END AS descricao
				FROM tbl_ressarcimento
				JOIN tbl_banco ON tbl_ressarcimento.banco = tbl_banco.banco
				LEFT JOIN tbl_admin ON tbl_ressarcimento.admin_altera = tbl_admin.admin
				LEFT JOIN tbl_os USING(os)
				LEFT JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
				LEFT JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_ressarcimento.hd_chamado
				LEFT JOIN tbl_produto PHE ON PHE.produto = tbl_hd_chamado_extra.produto AND PHE.fabrica_i = $login_fabrica
				WHERE tbl_ressarcimento.fabrica = $login_fabrica
				$cond
				ORDER BY tbl_ressarcimento.data_input DESC, tbl_ressarcimento.nome";

				$res = pg_query($con,$sql);
#echo nl2br($sql); exit;
	if(pg_num_rows($res) > 0){
		ob_start();
	?>
		<table align="center" class="tabela" cellpadding="1" cellspacing="1" border="1">
			<tr class="titulo_coluna">
				<th>OS</th>
				<th>Produto</th>
				<th>Atendimento</th>
				<th>Nome</th>
				<th>CPF</th>
				<th>Data Abertura</th>
				<th>DataAprovação</th>
				<th>Data Finalização</th>
				<th>Fechamento Lote</th>
				<th>Tipo Conta</th>
				<th>Banco</th>
				<th>Agência</th>
				<th>Conta</th>
				<th>Urgência</th>
				<th>Status</th>
				<th>Valor (R$)</th>
				<th>Alterado por</th>
				<th>Nota Fiscal</th>
				<th>Comprovante</th>
				<?php
					if (in_array($login_fabrica, array(81, 101))) {
				?>
						<th>Previsão Pagamento</th>
				<?php
					}
				?>
				<th>Ações</th>
			</tr>
	<?php
		$tDocs = new TDocs($con, $login_fabrica);
		for($i = 0; $i < pg_numrows($res); $i++){
			$ressarcimento	= pg_result($res,$i,'ressarcimento');
			$os				= pg_result($res,$i,'os');
			$hd_chamado		= pg_result($res,$i,'hd_chamado');
			$nome			= pg_result($res,$i,'nome');
			$cpf			= pg_result($res,$i,'cpf');
			$banco			= pg_result($res,$i,'banco');
			$codigo_banco	= pg_result($res,$i,'codigo_banco');
			$agencia		= pg_result($res,$i,'agencia');
			$conta			= pg_result($res,$i,'conta');
			$valor_original = pg_result($res,$i,'valor_original');
			$valor_alterado = pg_result($res,$i,'valor_alterado');
			$valor			= pg_result($res,$i,'valor');
			$urgencia		= pg_result($res,$i,'urgencia');
			$admin			= pg_result($res,$i,'admin');
			$admin_altera	= pg_result($res,$i,'admin_altera');
			$status			= pg_result($res,$i,'status');
			$abertura		= pg_result($res,$i,'abertura');
			$aprovado		= pg_result($res,$i,'aprovado');
			$finalizado		= pg_result($res,$i,'finalizado');
			$login			= pg_result($res,$i,'login');
			$lote_fechado	= pg_result($res,$i,'lote_fechado');
			$referencia  	= pg_result($res,$i,'referencia');
			$descricao  	= pg_result($res,$i,'descricao');
			$tipo_conta		= pg_fetch_result($res, $i, 'tipo_conta');
			$previsao_pagamento	= pg_fetch_result($res, $i, 'previsao_pagamento');

			$os 		= ($os == null ? "" : $os);
			$hd_chamado = ($hd_chamado == null ? "" : $hd_chamado);

			if($status == "Pendente" OR $status == "Aguardando Liberação"){
				$cor = "#CDC9C9";
				$total_pendente += $valor;
			}else if($status == "Aprovado"){
				$cor = "#8FBC8F";
				$total_aprovado += $valor;
			}else{
				$cor = "#EEC591";
				$total_finalizado += $valor;
			}

			$cor_linha = ($i % 2) ? "#F7F5F0" : "#F1F4FA";


            // Seleção de códigos de admin da fábrica 147 (Hitachi), que precisam ter opção de lote fechado
            // 2489, 5434, 9334 (códigos em produção), que podem divergir dos códigos na base de desenvolvimento.

            $sql = "SELECT admin FROM tbl_admin WHERE login = 'celso' AND fabrica IN (81, 114, 147)";
            $admins_lote_fechado = pg_fetch_pairs($con, $sql);

            if ($login_fabrica == 101) {
            	$exibir_os = "<a href='os_press.php?os=$os' target='_blank'>$os</a>";
            } else {
            	$exibir_os = "$os";
            }

			echo "
			<tr bgcolor='$cor_linha'  id='row_$ressarcimento' >
				<td>$exibir_os</td>
				<td nowrap>$referencia - $descricao</td>
				<td>$hd_chamado</td>
				<td align='left'>$nome</td>
				<td>$cpf</td>
				<td>$abertura</td>
				<td>$aprovado</td>
				<td>$finalizado</td>
				<td>$lote_fechado</td>
				<td>$tipo_conta</td>
				<td>$codigo_banco - $banco</td>
				<td>$agencia</td>
				<td>$conta</td>
				<td>$urgencia</td>
				<td bgcolor='$cor' style='text-transform:uppercase;'>".$status."</td>
				<td  bgcolor='$cor'>".number_format($valor,2,',','.')."</td>
				<td>$login</td><td>";

				$os 		= ($os == "" ? "''" : $os);
				//$hd_chamado = ($hd_chamado == "" ? "''" : $hd_chamado);

				$anexo_nf = $tDocs->getdocumentsByRef($ressarcimento, "ressarcimento")->url;
				echo "<a href='$anexo_nf' target='_blank'>Nota Fiscal</a>";
				$anexo_nf = "";

				echo "</td><td>";

				$anexo = $tDocs->getdocumentsByRef($ressarcimento, "comprovante")->url;
				if (isset($anexo)) {
					echo "<a href='$anexo' target='_blank'>Comprovante</a>";
				}

				if($login_fabrica == 114){
					if($status == "Finalizado"){ ?>
						<form action="<?=$PHP_SELF?>" method="POST" name="frm_anexa_comprovante_<?=$ressarcimento?>" enctype="multipart/form-data">
							<input type="hidden" name="ressarcimento" value="<?=$ressarcimento?>">
							<input type="hidden" name="btn_acao" value="Anexar">
							<input type="file" name="comprovante"  onchange="$(this).parent().submit();">
						</form>
					<?}
				}

				echo "</td>";
				$previsao_pagamento_formatada='';
				if (in_array($login_fabrica, array(81, 101))) {

					if(strlen($previsao_pagamento) > 0){
						$previsao_pagamento_formatada = date('d/m/Y',strtotime($previsao_pagamento));
					}

				?>
						<td id="data_previsao_<?=$ressarcimento?>">
							<input type="hidden" value="<?= date("d/m/Y") ?>" name="data_atual" id="data_atual" />
							<input id="previsao_pagamento_<?= $ressarcimento ?>" name="previsao_pagamento" type="text" value="<?= $previsao_pagamento_formatada ?>" rel="<?=$ressarcimento?>" style="text-align:center;float:left;" />
							<div class="mensagem_sucesso" id="msg_<?=$ressarcimento?>"></div>
						</td>
				<?php
				}

				echo "<td bgcolor='$cor' id='col_$ressarcimento' nowrap>";
					if ($status == "Pendente" OR $status == "Aguardando Liberação") {
						echo "
						<input type='button' value='Alterar' onclick=\"window.open('ressarcimento_cadastro.php?ressarcimento=$ressarcimento')\">";
						if ($status == "Pendente") {
							echo "<input type='button' value='Aprovar' onclick=\"aprovaRessarcimento($ressarcimento, $os, '$hd_chamado', '$nome')\">";
						} else {
							if(in_array($login_admin,array(8750,9155,7263,8286,8518,7264,7837,8015,6144,9135,9332,5613,4276,8636,9827,10749,10764,9213,5717,9814,9214,5520,11107,11103,11104,11105,11108,11106,11102)) || $login_fabrica == 101 || $login_responsavel_ressarcimento){ //Login do Jader e Celso
									echo "<input type='button' value='Liberar' onclick=\"liberarRessarcimento($ressarcimento, $os, '$hd_chamado', '$nome')\">";
							}
						}
					}

					if ($status == "Aprovado") {
						echo " <input type='button' value='Finalizar' id='btn_finalizar_$ressarcimento' onclick='exibeLinha($ressarcimento)'>";
					}

					if ($status == "Finalizado" AND in_array($login_admin, $admins_lote_fechado ) AND empty($lote_fechado)) {
						echo " <input type='checkbox' value='$ressarcimento' class='lote'>";
                    }
                    if ($status != "Finalizado" && (in_array($login_admin,array(4276,4871,5613,5717,5853,8636,9135,9212,9213,9214,9522,9814,9827,10764,10749))) OR $login_responsavel_ressarcimento) {
                       echo " <input type='button' value='Deletar' onclick='deletaRessarcimento($ressarcimento)'>";
                    }
			echo "
				</td>
			</tr> ";

			if($status == "Aprovado"){
				echo "<tr id='linha_$ressarcimento' style='display:none'>
							<td colspan='15'>
								<form method='post' name='frm_comprovante_$ressarcimento' enctype='multipart/form-data'>
									<input type='hidden' name='ressarcimento_finaliza' value='$ressarcimento'>
									<b>Comprovante de Pagamento</b>&nbsp;<input type='file' name='comprovante'>
									<input type='submit' name='btn_acao' value='Finalizar' '>
								</form>
							</td>
					  </tr>";
			}
		}

		/*HD-4096786*/
		if ($login_fabrica == 101) {
			$aux_colspan = " colspan='19' ";
		} else {
			$aux_colspan = " colspan='13' ";
		}

		echo "<tr><td colspan='16' style='border:none;'>&nbsp;</td></tr>
		      <tr bgcolor='#8FBC8F' style='font-weight:bold;'>
					<td $aux_colspan align='right'>TOTAL APROVADO</td>
					<td align='right'>".number_format($total_aprovado,2,',','.')."</td>
					<td colspan='3'>&nbsp;</td>
				</tr>
				<tr bgcolor='#EEC591' style='font-weight:bold;'>
					<td $aux_colspan align='right'>TOTAL FINALIZADO</td>
					<td align='right'>".number_format($total_finalizado,2,',','.')."</td>
					<td colspan='3'>&nbsp;</td>
				</tr>
				<tr bgcolor='#CDC9C9' style='font-weight:bold;'>
					<td $aux_colspan align='right'>TOTAL PENDENTE</td>
					<td align='right'>".number_format($total_pendente,2,',','.')."</td>
					<td colspan='3'>&nbsp;</td>
				</tr>";
		echo "</table>";
		$resultado = ob_get_contents();
		ob_clean();
		echo $resultado;

		echo "<br /><center>";
		if($_POST['status'] == "finalizado" AND in_array($login_admin, $admins_lote_fechado )){
			echo " <input type='button' value='Fechamento de Lote' onclick='finalizarLote();'>";
		}

		echo " <span id='excel'><input type='button' value='Gerar Excel' onclick=\"geraExcel('{$_POST['os']}','{$_POST['atendimento']}','{$_POST['nome']}','{$_POST['data_inicial']}','{$_POST['data_final']}','{$_POST['status']}','{$_POST['data']}')\"></span></center>";
	} else {
		echo "<center>Nenhum registro encontrado</center>";
	}
}

include "rodape.php";

?>
