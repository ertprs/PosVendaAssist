<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

include "funcoes.php";

$admin_privilegios = "gerencia,auditoria";
include "autentica_admin.php";
include_once '../class/communicator.class.php';

if ($S3_sdk_OK) {
    include_once S3CLASS;
}

if (in_array($login_fabrica, array(141,144))) {
    # A class AmazonTC está no arquivo assist/class/aws/anexaS3.class.php
    $s3 = new AmazonTC("pedido", $login_fabrica);
}

if(isset($_POST['excluir_comprovante']) && $_POST['excluir_comprovante'] == "sim"){

	$pedido 	= $_POST['pedido'];
	$posto 		= $_POST['posto'];
	$motivo 	= $_POST['motivo'];
	$img 		= $_POST['img'];

	$mensagem = traduz("Por favor insira um novo Comprovante de Pagamento no pedido %.",null,null,[$pedido])."<br>";
	$mensagem .= traduz("Motivo: %.",null,null,[$motivo]);

	$sql_comunicado = "INSERT INTO tbl_comunicado (fabrica, posto, mensagem, ativo, obrigatorio_site) VALUES ($login_fabrica, $posto, '$mensagem', 't', 't')";
	$res_comunicado = pg_query($con, $sql_comunicado);

	if($res_comunicado){
		$s3->deleteObject($img);
	}

	echo "ok";

	exit;

}

if ($btnacao=='filtrar'){

	if(empty($_POST['pedido'])){
		if (!empty($_POST['data_inicial'])) {
			$fnc = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
			if (strlen ( pg_errormessage ($con) ) > 0) $erro = pg_errormessage ($con) ;
			if (strlen($erro) == 0)                    $aux_data_inicial = @pg_result ($fnc,0,0);
			else									   $erro = "Data Inválida";

			$numero_nao_obrigatorio = ' style="display:none" ';
			$posto_nao_obrigatorio = ' style="display:none" ';
		}
		if (!empty($_POST['data_final'])) {
			$fnc = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
			if (strlen ( pg_errormessage ($con) ) > 0) $erro = pg_errormessage ($con) ;
			if (strlen($erro) == 0)                    $aux_data_final = @pg_result ($fnc,0,0);
			else									   $erro = traduz("Data Inválida");
		}

		if(empty($_POST['pedido']) && ((empty($_POST['data_inicial'])) OR (empty($_POST['data_final'])))) {
			if( (empty($_POST['codigo_posto'])) OR (empty($_POST['posto_nome'])) ){
				$erro = traduz("Preencha os campos obrigatórios.");
				$msg_erro["campos"][] = 'data';
				$msg_erro["campos"][] = 'pedido';
				$msg_erro["campos"][] = 'posto';
			}
		}

		if($aux_data_inicial > $aux_data_final)
			$erro = traduz("Data Inválida");
	}else{
		$pedido = $_POST['pedido'];

		if(!empty($pedido)){
			$data_nao_obrigatorio = ' style="display:none" ';
			$posto_nao_obrigatorio = ' style="display:none" ';
		}

		if( (!empty($_POST['codigo_posto'])) OR (!empty($_POST['posto_nome'])) ){
			$numero_nao_obrigatorio = ' style="display:none" ';
			$data_nao_obrigatorio = ' style="display:none" ';
		}
	}
}

function salvarAlteracao($pedido, $status, $login_admin, $con, $motivo){
	$sql = "INSERT INTO tbl_pedido_status(pedido, status, observacao, admin) VALUES ($pedido, $status, '$motivo', $login_admin)";
	$res = @pg_exec($con,$sql);
	$msg_erro = pg_errormessage($con);
	echo (empty($msg_erro)) ? "" : traduz("Erro ao gravra as informações do pedido %",$pedido);
}

if (strlen($erro) == 0) {
	
	$ajax_pedido = $_GET['ajax_pedido'];
	if(!empty($ajax_pedido)) {
			$sql = " SELECT referencia,descricao
				FROM tbl_pedido_item
				JOIN tbl_peca USING (peca)
				WHERE pedido = $ajax_pedido
				AND   peca_critica_venda IS TRUE ";
			
		$res = pg_exec($con,$sql);
		if(pg_numrows($res) > 0){
			for($i =0;$i<pg_numrows($res);$i++) {
				$referencia = pg_result($res,$i,referencia);
				$descricao  = pg_result($res,$i,descricao);
				echo "$referencia - $descricao<br>";
			}
		}else{
			echo traduz("Nenhuma peça crítica");
		}
		exit;
	}

	$ajax_pedido_os = $_GET['ajax_pedido_os'];
	if(!empty($ajax_pedido_os)) {
			$sql = " SELECT DISTINCT tbl_os.os, tbl_os.sua_os
					FROM tbl_os
					JOIN tbl_os_produto USING(os)
					JOIN tbl_os_item USING(os_produto)
					WHERE tbl_os_item.pedido = $ajax_pedido_os";
			
		$res = pg_exec($con,$sql);
		if(pg_numrows($res) > 0){
			for($i =0;$i<pg_numrows($res);$i++) {
				$os = pg_result($res,$i,os);
				$sua_os  = pg_result($res,$i,sua_os);

				echo "<a href='os_press.php?os=$os' target='_blank'>$sua_os</a><br>";
			}
		}else{
			echo traduz("Nenhuma peça crítica");
		}
		exit;
	}

	$autorizar_pedido = $_GET['autorizar_pedido'];
	if(!empty($autorizar_pedido)) {

	if (!$telecontrol_distrib OR $controle_distrib_telecontrol) {
		if (in_array($login_fabrica, array(163,11,172))) {
			$dist      = "";
			$exportado = "";
			
			if (in_array($login_fabrica, [11,172])) {
				$dist = ", distribuidor = 4311";
				$exportado = ", exportado = now()";
				
				// Retirado validação de estoque. Todos os pedidos seram da Distrib HD-6560267	
				/*$st_pedido = "status_pedido = 1, ";
				$dist      = ", distribuidor = NULL";
				$exportado = "";
				$fab_peca = ($login_fabrica == 11) ? 172 : 11;

				$sql_dados_pedido = "SELECT p.atende_pedido_faturado_parcial, 
											pi.peca,
											pc.referencia,
											pc.descricao, 
											pi.qtde
									 FROM tbl_pedido p
									 JOIN tbl_pedido_item pi USING(pedido)
									 JOIN tbl_peca pc USING(peca)
									 WHERE p.pedido = $autorizar_pedido
									 AND p.fabrica = $login_fabrica";
				$res_dados_pedido = pg_query($con, $sql_dados_pedido); 
				if (pg_num_rows($res_dados_pedido) > 0) {
					$exporta_parcial = false;
					$exporta = [];
					for ($i=0; $i < pg_num_rows($res_dados_pedido); $i++) { 
						$qtde_estoque = "";
						$atende_parcial = pg_fetch_result($res_dados_pedido, $i, 'atende_pedido_faturado_parcial');
						$peca_pedido    = pg_fetch_result($res_dados_pedido, $i, 'peca');
						$ref_peca       = pg_fetch_result($res_dados_pedido, $i, 'referencia');
						$desc_peca      = pg_fetch_result($res_dados_pedido, $i, 'descricao');
						$qtde_peca      = pg_fetch_result($res_dados_pedido, $i, 'qtde');

						if (!empty($ref_peca) && !empty($desc_peca)) {
							$sql_pecas = "SELECT peca FROM tbl_peca WHERE referencia = '$ref_peca' AND descricao = '$desc_peca' AND fabrica = $fab_peca";
							$res_pecas = pg_query($con, $sql_pecas);
							if (pg_num_rows($res_pecas) > 0) {
								$pecas = $peca_pedido.", ".pg_fetch_result($res_pecas, 0, 'peca');
							} else {
								$pecas = $peca_pedido;
							}
						}

						$sql_qtde_estoque = "SELECT SUM(qtde) AS qtde_estoque FROM tbl_posto_estoque WHERE posto = 4311 AND peca IN ($pecas)";
						$res_qtde_estoque = pg_query($con, $sql_qtde_estoque);
						$qtde_estoque = pg_fetch_result($res_qtde_estoque, 0, 'qtde_estoque');
						$qtde_estoque = (empty($qtde_estoque)) ? 0 : $qtde_estoque;
						if ($atende_parcial == "t") {
							if ($qtde_estoque > 0) {
								$exporta_parcial = true;
								break;	
							}
						}

						if ($qtde_estoque >= $qtde_peca) {
							$exporta[] = "sim";
						} else {
							$exporta[] = "nao";
						}
					}
				} 
				if ($exporta_parcial === true || !in_array("nao",$exporta)) {
					$exportado = ", exportado = now()";
					$st_pedido = "status_pedido = 2, ";
					$dist = "";
				}*/
			}

			$sql = " UPDATE tbl_pedido SET status_pedido = 2, status_fabricante = 'APROVADO' $dist $exportado
				WHERE pedido = $autorizar_pedido ";
				$auxiliar_status = 2;
		} else {
			if($login_fabrica == 191){
				$filial_posto = $_GET['filial_posto'];
				if (!empty($filial_posto)){
					$update_filial_posto = ",filial_posto = $filial_posto";
				}
			}
			$sql = " UPDATE tbl_pedido SET status_pedido = 1, status_fabricante = 'APROVADO' $update_filial_posto
				WHERE pedido = $autorizar_pedido ";
				$auxiliar_status = 1;
		}
		$res = @pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
		echo (empty($msg_erro)) ? traduz("Pedido autorizado") : traduz("Erro ao autorizar pedido %",null,null,[$autorizar_pedido]);
	}
		/*if (!in_array($login_fabrica, array(147,160))) {
			salvarAlteracao($autorizar_pedido, $auxiliar_status, $login_admin, $con, 'Pedido Autorizado');
		}
		HD - 4223006 para fabrica 147-160
		*/
		if($login_fabrica == 186){
			$aux_sql = " INSERT INTO tbl_pedido_status(pedido, data, status, admin, observacao) VALUES ($autorizar_pedido, current_timestamp, 1, $login_admin, 'Pedido Aprovado')";
			$aux_res = pg_query($con, $aux_sql);
		}

		if ($telecontrol_distrib) {

			$sqlAtendimentoTotal = "SELECT  atende_pedido_faturado_parcial FROM tbl_pedido WHERE  pedido = $autorizar_pedido";
			$resAtendimentoTotal = pg_query($con, $sqlAtendimentoTotal);
			$atende_parcial = pg_fetch_result($resAtendimentoTotal, 0, 'atende_pedido_faturado_parcial');
			$temTodas = true;
			$proximoStatus  = 29;

			
			$sqlPecasQtd = "SELECT peca,qtde FROM tbl_pedido_item WHERE pedido = $autorizar_pedido";
			$resPecasQtd = pg_query($con, $sqlPecasQtd);

			foreach (pg_fetch_all($resPecasQtd) as $pecas) {

				$sqlPecaEstoque = "SELECT qtde FROM tbl_posto_estoque WHERE tbl_posto_estoque.peca = {$pecas['peca']} AND tbl_posto_estoque.posto = 4311 ";
				$resPecasEstoque = pg_query($con, $sqlPecaEstoque);

				if (pg_fetch_result($resPecasEstoque, 0, 'qtde') <= $pecas['qtde']) {
					$temTodas = false;
				}
			}

			if ($atende_parcial == 'f' && !$temTodas) {
				$proximoStatus = 22;
			}

			if (empty($msg_erro)) {
				$updateTblPedido = " UPDATE tbl_pedido SET status_pedido = $proximoStatus , status_fabricante = 'APROVADO'
				WHERE pedido = $autorizar_pedido ";
				pg_query($con, $updateTblPedido);

				$udpateTblPedidoStatus = " INSERT INTO tbl_pedido_status(pedido, data, status, admin, observacao) VALUES ($autorizar_pedido, current_timestamp, $proximoStatus, $login_admin, 'Pedido Aprovado')";
				pg_query($con, $udpateTblPedidoStatus);

				$msg_erro = pg_errormessage($con);
			}			
			echo (empty($msg_erro)) ? traduz("Pedido autorizado") : traduz("Erro ao autorizar pedido %",null,null,[$autorizar_pedido]);
			
		}
		exit;
	}

	$cancelar_pedido = $_GET['cancelar_pedido'];
	if(!empty($cancelar_pedido)) {
		if(in_array($login_fabrica,array(35,104,105,140,167,203))){
			$motivo = $_GET['motivo'];
		} else{
			$motivo = traduz('Pedido Cancelado pela Fábrica');
		}
		$sql = " UPDATE tbl_pedido SET status_pedido = 14, status_fabricante = 'CANCELADO'
				WHERE pedido = $cancelar_pedido;

				UPDATE tbl_pedido_item SET qtde_cancelada = qtde
				WHERE pedido = $cancelar_pedido;

				INSERT INTO tbl_pedido_cancelado(pedido,posto,fabrica,peca,qtde,motivo,data)
				SELECT pedido,posto,fabrica,peca,qtde,'$motivo',current_date
				FROM tbl_pedido JOIN tbl_pedido_item USING(pedido) WHERE pedido = $cancelar_pedido;";
		$auxiliar_status = 14;
		$res = @pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);

		/*HD - 4223006*/
		if (in_array($login_fabrica, array(147, 160)) or $replica_einhell) {
			$aux_sql = " INSERT INTO tbl_pedido_status(pedido, data, status, admin, observacao) VALUES ($cancelar_pedido, current_timestamp, 14, $login_admin, 'Pedido Recusado')";
			$aux_res = pg_query($con, $aux_sql);
		}

		if($login_fabrica == 168){

			$sql_posto = " SELECT contato_email from tbl_pedido inner join tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_pedido.posto where tbl_pedido.pedido = $cancelar_pedido and tbl_posto_fabrica.fabrica = $login_fabrica";
            $res_posto = pg_query($con, $sql_posto);
            if(pg_num_rows($res_posto)>0){
                $contato_email = pg_fetch_result($res_posto, 0, contato_email);
            }

			$mailTc = new TcComm($externalId);//classe
		    $assunto = traduz("Cancelamento do Pedido %",null,null,[$cancelar_pedido]);
		    $mensagem = traduz("O pedido  % foi cancelado pelo admin, em caso de dúvida entrar em contato com o financeiro@acaciaeletro.com.br",null,null,[$cancelar_pedido]);
		    $res = $mailTc->sendMail(
		        $contato_email,
		        $assunto,
		        $mensagem,
		        'helpdesk@telecontrol.com.br'
		    );
		}

		if ($login_fabrica == 35) {
			$sql_posto = " SELECT tbl_pedido.posto, tbl_os_produto.os
			                 FROM tbl_pedido 
			            LEFT JOIN tbl_os_item USING(pedido)
			            LEFT JOIN tbl_os_produto USING(os_produto)
			                WHERE tbl_pedido.pedido = {$cancelar_pedido} 
			                  AND tbl_pedido.fabrica= {$login_fabrica}";
            $res_posto = pg_query($con, $sql_posto);
            $posto     = pg_fetch_result($res_posto, 0, 'posto');
            $xos       = pg_fetch_result($res_posto, 0, 'os');
			$mensagem   = "<div style=\"text-aling:center;\">".traduz("O Pedido de Peças CANCELADO")."<br /><br />";
            if (strlen($xos) > 0) {

				$mensagem  .= traduz("O pedido %, referente a O.S. % foi cancelado pela fábrica.",null,null,[$cancelar_pedido, $xos])."<br/><br />";
            } else {
				$mensagem  .= traduz("O pedido %, foi cancelado pela fábrica.",null,null,[$cancelar_pedido])."<br /><br />";

            }
			$mensagem  .= traduz("Justificativa: %",null,null,[$motivo])."</div>";

			$sql_comunicado = "INSERT INTO tbl_comunicado (fabrica, posto, mensagem, ativo, obrigatorio_site) VALUES ($login_fabrica, $posto, '$mensagem', 't', 't')";

			$res_comunicado = pg_query($con, $sql_comunicado);
		}
		echo (empty($msg_erro)) ? traduz("Pedido cancelado") : traduz("Erro ao cancelar pedido %",null,null,[$cancelar_pedido]);

		if (!in_array($login_fabrica, array(147, 160)) and !$replica_einhell) {
			salvarAlteracao($cancelar_pedido, $auxiliar_status, $login_admin, $con, $motivo);
		}
		exit;
	}

}


$layout_menu = "auditoria";
$title = (in_array($login_fabrica,array(115,116))) ? traduz("INTERVENÇÃO TÉCNICA DE PEDIDOS") : traduz("PEDIDOS COM INTERVENÇÃO DA FÁBRICA");
include "cabecalho_new.php";

$plugins = array(
  "autocomplete",
  "datepicker",
  "shadowbox",
  "mask",
  "dataTable"
);

include("plugin_loader.php");
?>

<script language="javascript">
$(function(){
	$.datepickerLoad(Array("data_final", "data_inicial"));
	$.autocompleteLoad(Array("posto"));
	Shadowbox.init();

	$("span[rel=lupa]").click(function () {
		$.lupa($(this));
	});

	$("#pedido").blur(function(){
		if( $(this).val().length > 0 ){
			$('.posto_nao_obrigatorio').hide();
			$('.data_nao_obrigatorio').hide();
			$(".error").removeClass('error');
		}
	});

	$("input[name^='data']").keydown(function(){
		$('.posto_nao_obrigatorio').hide();
		$('.numero_nao_obrigatorio').hide();		
		$(".error").removeClass('error');
	});

	$("input[name^='data']").click(function(){
			$('.posto_nao_obrigatorio').hide();
			$('.numero_nao_obrigatorio').hide();		
			$(".error").removeClass('error');
	});

	$("input[name^='codigo'], input[name^='posto']").blur(function(){
		if( $(this).val().length > 0 ){
			$('.data_nao_obrigatorio').hide();
			$('.numero_nao_obrigatorio').hide();
			$(".error").removeClass('error');
		}
	});

	$("#pedido_cadastro").click(function() {
		var pedido = $(this).attr("pedido");
		window.open("pedido_cadastro.php?pedido="+pedido, '_blank');
	});

});

function verPeca(pedido,dado,ver,i) {
	var dados = document.getElementById(dado);
	var ver   = document.getElementById(ver);
    $("#ver_dados_"+i).show();
	$.ajax({
		url: "<?=$PHP_SELF?>",
		data: "ajax_pedido=" + pedido,
		type: "GET",
		beforeSend: function(){
			$(ver).html("<img src='imagens/carregar2.gif' />");
		},
		complete: function(http){
			resultado = http.responseText;
			//console.log(resultado)
			$(ver).html("<a href='javascript:verPeca(pedido,dados_"+i+",ver_"+i+","+i+")'>VER</a>").click(function (){
				$("#ver_dados_"+i).toggle("");
			});
			$(dados).html(resultado);
		}
	});
}

function verOS(pedido,dado,ver,i) {
	var dados = document.getElementById(dado);
	var ver   = document.getElementById(ver);
    $("#ver_dados_"+i).show();
	$.ajax({
		url: "<?=$PHP_SELF?>",
		data: "ajax_pedido_os=" + pedido,
		type: "GET",
		beforeSend: function(){
			$(ver).html("<img src='imagens/carregar2.gif' />");
		},
		complete: function(http){
			resultado = http.responseText;
			console.log(resultado)
			$(ver).html("<a href='javascript:verOS(pedido,dados_"+i+",verOS_"+i+","+i+")'>VER</a>").click(function (){
				$("#ver_dados_"+i).toggle("");
			});
			$(dados).html(resultado);
		}
	});
}


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
		alert('<?=traduz("informe toda ou parte da informação para realizar a pesquisa!")?>');
}

function fnc_autorizar(pedido,acao){
	var acao   = document.getElementById(acao);
	var url_filial = "";
	
	<?php if ($login_fabrica == 191){ ?>
		var filial_posto = $(".filial_"+pedido).val();
		url_filial = "&filial_posto="+filial_posto;	

		if (filial_posto == "" || filial_posto == "undefined"){
			alert("Selecione a filial para continuar");
			return false;
		}
	<?php } ?>

	$.ajax({
		url: "<?=$PHP_SELF?>",
		data: "autorizar_pedido=" + pedido + url_filial,
		type: "GET",
		beforeSend: function(){
			$(acao).html("<img src='imagens/carregar2.gif' />");
		},
		complete: function(http){
			resultado = http.responseText;
			$(acao).html(resultado);
		}
	});
}

function alterar(pedido){
	window.open("cadastro_pedido.php?pedido="+pedido, '_blank');
}

function fnc_cancelar(pedido,acao, motivo, linha, posto){
	var acao   = document.getElementById(acao);
	<?php if(in_array($login_fabrica,array(35,104,105,140,167,203))){?>
		    var motivo = document.getElementById(motivo).value;
	<?php }else{ ?>
            var motivo = "";
    <?php }?>
	$.ajax({
		url: "<?=$PHP_SELF?>",
		data: "cancelar_pedido=" + pedido +"&motivo=" + motivo + "&posto=" + posto,
		type: "GET",
		beforeSend: function(){
			$(acao).html("<img src='imagens/carregar2.gif' />");
		},
		complete: function(http){
			resultado = http.responseText;
			$(acao).html(resultado);
			<? if(in_array($login_fabrica,array(35,104,105,140,167,203))){?>
				$("#"+linha).hide();
			<? } ?>
		}
	});
}

function mostraLinha(linha){
	var campo = $("#"+linha);

	if($(campo).is(":visible") == false){
		$(campo).show();
	}else{
		$(campo).hide();
	}

	// if(campo.display == "none"){
	//  	campo.display = "block";
	// } else {
	//  	campo.display = "none";
	// }
}

<?php

if(in_array($login_fabrica, array(141,144))){

	?>

	function boxComunicadoComprovante(pedido){

		$('.box_comunicado_comprovante_'+pedido).toggle();

	}

	function enviarComunicado(pedido, posto, img){

		var comunicado = $('#comunicado_comprovante_'+pedido).val();

		if(comunicado == ""){
			$('#comunicado_comprovante_'+pedido).focus();
			alert('<?= traduz('Por favor insira o comunicado da exclusão de pagamento do Pedido: ' )?>' +pedido);
		}else{

			$.ajax({
				url: "<?php echo $_SERVER['PHP_SELF']; ?>",
				type: "POST",
				data: {
					excluir_comprovante	: "sim",
					pedido  			: pedido,
					posto  				: posto,
					motivo 				: comunicado,
					img 				: img
				},
				complete: function(data){
					data = data.responseText;

					if(data == "ok"){
						$('.td_comprovante_'+pedido).text(traduz('Sem Comprovante de Pagamento Inserido'));
					}else{
						alert('<?=traduz("Erro ao excluir o Comprovante de Pagamento !")?>');
					}
				}
			});

		}

	}

	<?php

}
$fabricas_novo_layout = [35];
?>

function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
	$("#posto_nome").val(retorno.nome);
}


</script>
<br>
<? if(strlen($erro)>0){ ?>
		<div class="alert alert-error">
			<h4><? echo $erro; ?></h4>
		</div>
<? } ?>
<div class="row">
	<b class="obrigatorio pull-right">  *<?=traduz("Campos obrigatórios")?> </b>
</div>

<FORM class="form-search form-inline tc_formulario" name="frm_consulta" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
	<div class="titulo_tabela "> <?=traduz("Parâmetros de Pesquisa")?></div>
	<br>
	<?php if (!in_array($login_fabrica, $fabricas_novo_layout)) { ?>
	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span8'>
				<div class='control-group <?=(in_array("pedido", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class="control-label" for="numero_pedido"><?=traduz("Número Pedido")?></label>
					<div class='controls controls-row'>
						<div class='span4'>
						<h5 class='asteristico numero_nao_obrigatorio'<?=$numero_nao_obrigatorio?> >*</h5>
							<input type="text" name="pedido" id="pedido" class="span12" maxlength="10" value="<? if (strlen($pedido) > 0) echo $pedido; ?>" >
						</div>
					</div>
				</div>
			</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'><?=traduz("Data Inicial")?></label>
					<div class='controls controls-row'>
						<div class='span4'>
								<h5 class='asteristico data_nao_obrigatorio' <?=$data_nao_obrigatorio ?> >*</h5>
								<input type="text" name="data_inicial" class="span12" id="data_inicial" maxlength="10" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>" >
						</div>
					</div>
				</div>
			</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_final'><?=traduz("Data Final")?></label>
				<div class='controls controls-row'>
					<div class='span4'>
							<h5 class='asteristico data_nao_obrigatorio' <?=$data_nao_obrigatorio ?> >*</h5>
							<input type="text" name="data_final" class="span12" id="data_final" maxlength="10" value="<? if (strlen($data_final) > 0) echo $data_final; ?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='codigo_posto'><?=traduz("Código Posto")?></label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<h5 class='asteristico posto_nao_obrigatorio' <?=$posto_nao_obrigatorio?>>*</h5>
						<input type="text" id="codigo_posto" name="codigo_posto" class="span12 ui-autocomplete-input" maxlength="20" value="<? echo $codigo_posto ?>" autocomplete="off">
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='descricao_posto'><?=traduz("Nome Posto")?></label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<h5 class='asteristico posto_nao_obrigatorio' <?=$posto_nao_obrigatorio?> >*</h5>
						<input type="text" id="descricao_posto" name="posto_nome" class="span12 ui-autocomplete-input" value="<?echo $posto_nome?>" autocomplete="off">
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<?php if($login_fabrica == 161){?>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span2'>
			<br>
			<div class="radio">
			    <label>
			      <input type="radio" name="status" value="aprovado" <?php if($_POST['status'] == 'aprovado'){ echo " checked "; } ?>> <?=traduz("Aprovado")?>
			    </label>
			</div>
		</div>
		<div class='span2'>
			<br>
			<div class="radio">
			    <label>
			      <input type="radio" name="status" value="cancelado" <?php if($_POST['status'] == 'cancelado'){ echo " checked "; } ?> > <?=traduz("Cancelado")?>
			    </label>
			</div>
		</div>
		<div class='span4'>
			<br>
			<div class="radio">
			    <label>
			      <input type="radio" name="status" value="ag_aprovacao" <?php if($_POST['status'] == 'ag_aprovacao'){ echo " checked "; } ?> > <?=traduz("Aguardando Aprovação")?>
			    </label>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<?php } ?>
	<p>
	<br>
		<input type='hidden' name='btnacao'>
		<input type="button" class="btn" onclick="javascript: document.frm_consulta.btnacao.value='filtrar' ; document.frm_consulta.submit() " alt="filtrar" border='0' value="Pesquisar">
	</p>
	<br>
	<?php
	} else { ?>
		<div class="row-fluid" >
        <div class="span1" ></div>
        <div class="span2">
            <div class="control-group <?=(in_array('data_inicial', $msg_erro['campos'])) ? 'error' : '' ?>" >
                <label class="control-label" for="data_inicial" ><?=traduz("Data Inicial")?></label>
                <div class="controls controls-row" >
                    <div class="span12" >
                        <h5 class='asteristico'>*</h5>
                        <input type="text" id="data_inicial" class="span12" name="data_inicial" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span2">
            <div class="control-group <?=(in_array('data_final', $msg_erro['campos'])) ? 'error' : '' ?>" >
                <label class="control-label" for="data_final" ><?=traduz("Data Final")?></label>
                <div class="controls controls-row" >
                    <div class="span12" >
                        <h5 class='asteristico'>*</h5>
                        <input type="text" id="data_final" class="span12" name="data_final" value="<? if (strlen($data_final) > 0) echo $data_final; ?>" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span2">
            <div class="control-group <?=(in_array('pedido', $msg_erro['campos'])) ? 'error' : '' ?>" >
                <label class="control-label" for="os"><?=traduz("Numero Pedido")?></label>
                <div class="controls controls-row">
                    <div class="span10 input-append" >
                        <input type="text" id="pedido" class="span12" name="pedido" value="<? if (strlen($pedido) > 0) echo $pedido; ?>" />
                        <span class="add-on" title="Para pesquisar por Numero Pedido não é necessário informar as datas" >
                            <i class="icon-info-sign" ></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="span4" >
            <div class="control-group" >
                <label class="control-label" for="estado" ><?=traduz("Estado")?></label>
                <div class="controls control-row">
                    <select id="estado" name="estado" class="span12" >
                        <option value="" ><?=traduz("Selecione")?></option>
                        <?php foreach ($array_estados() as $sigla => $estado_nome) {
                            $selected = "";

                            if (is_array($_POST["estado"]) && in_array($sigla, $_POST["estado"])) {
                                $selected = "selected";
                            } else if (!is_array($_POST["estado"]) && $estado == $sigla) {
                                $selected = "selected";
                            }
                            ?>
                            <option value="<?= $sigla; ?>" <?= $selected; ?> ><?= $estado_nome ?></option>
                        <? } ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <?php
    if (strlen(getValue("posto_id")) > 0) {
        $posto_input_readonly     = "readonly";
        $posto_span_rel           = "trocar_posto";
        $posto_input_append_icon  = "remove";
        $posto_input_append_title = "title='Trocar Posto'";
    } else {
        $posto_input_readonly     = "";
        $posto_span_rel           = "lupa";
        $posto_input_append_icon  = "search";
        $posto_input_append_title = "";
    }
    ?>
    <div class="row-fluid" >
        <div class="span1" ></div>
        <div class="span3" >
            <div class="control-group" >
                <label class="control-label" for="codigo_posto" ><?=traduz("Código do Posto")?></label>
                <div class="controls controls-row">
                    <div class="span10 input-append">
                        <input id="codigo_posto" name="codigo_posto" class="span12" type="text" value="<? echo $codigo_posto;?>" <?=$posto_input_readonly?> />
                        <span class="add-on" rel="<?=$posto_span_rel?>" >
                            <i class="icon-<?=$posto_input_append_icon?>" <?=$posto_input_append_title?> ></i>
                        </span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                        <input type="hidden" id="posto_id" name="posto_id" value="<?=getValue('posto_id')?>" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span4" >
            <div class="control-group" >
                <label class="control-label" for="descricao_posto" ><?=traduz("Nome do Posto")?></label>
                <div class="controls controls-row" >
                    <div class="span10 input-append" >
                        <input id="descricao_posto" name="descricao_posto" class="span12" type="text" value="<? echo $posto_nome;?>" <?=$posto_input_readonly?> />
                        <span class="add-on" rel="<?=$posto_span_rel?>" >
                            <i class="icon-<?=$posto_input_append_icon?>" <?=$posto_input_append_title?> ></i>
                        </span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span1" ></div>
    </div>
    <div class="row-fluid" >
        <div class="span1" ></div>
        <div class="span10" >
            <div class="control-group" >
                <label class="control-label" ><?=traduz("Status da OS")?></label>
                <div class="controls controls-row">
                    <div class="span12">
                        <span class="label label-info" >
                            <label class="radio" >
                                    <input type="radio" class="status-auditoria-pesquisa" name="status" value="ag_aprovacao" <?php if($_POST['status'] == 'ag_aprovacao'){ echo " checked "; } ?> /><?=traduz("Auditoria Pendente")?>
                            </label>
                        </span>

                        <span class="label label-success" >
                            <label class="radio" >
                                    <input type="radio" class="status-auditoria-pesquisa" name="status" value="aprovado" <?php if($_POST['status'] == 'aprovado'){ echo " checked "; } ?> /><?=traduz("Auditoria Aprovada")?>
                            </label>
                        </span>

                        <span class="label label-important" >
                            <label class="radio" >
                                    <input type="radio" class="status-auditoria-pesquisa" name="status" value="cancelado" <?php if($_POST['status'] == 'cancelado'){ echo " checked "; } ?> /><?=traduz("Auditoria Cancelada")?>
                            </label>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <br />

    <p>
        <button class="btn" type="submit" name="btnacao" value="filtrar" ><?=traduz("Pesquisar")?></button>
        <button class="btn btn-primary listar-todos" type="submit" name="btnacao" value="listar_todas" title=<?=traduz("Somente os filtros de estado e posto autorizado irão funcionar em conjunto com está ação")?> ><?=traduz("Listar Todas")?></button>
    </p>

    <br />
<?php } ?>
</FORM>

</div>
<div class="container-fluid">
<?
if ( ($btnacao=='filtrar' || $btnacao=='listar_todas') && (strlen($erro)==0)){

	if ($btnacao=='filtrar') {
		if(!empty($aux_data_inicial) && !empty($aux_data_final)) {
			$condicao1 = " AND tbl_pedido.data between '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'";
		}
		if(!empty($_POST["codigo_posto"]) ) {
			$condicao2 = " AND tbl_posto_fabrica.codigo_posto='$codigo_posto'";
		}

		if(!empty($_POST["pedido"]) ) {
			$condicao2 = " AND tbl_pedido.pedido=$pedido";
		}

		if($login_fabrica == 161){	
		
			if(!empty($_POST['status']) and $_POST['status'] != 'ag_aprovacao'){
				$camposStatus = " ,tbl_pedido_status.admin, TO_CHAR(tbl_pedido_status.data,'DD/MM/YYYY') AS data_pedido_status, tbl_admin.nome_completo, tbl_pedido_status.observacao as obsStatusPedido   ";
				$camposStatusGb = " ,tbl_pedido_status.admin, tbl_pedido_status.data, tbl_admin.nome_completo, tbl_pedido_status.observacao  ";
				$joinStatusPedido = " JOIN  tbl_pedido_status on  tbl_pedido_status.pedido = tbl_pedido.pedido ";
				$joinAdmin = " JOIN tbl_admin ON tbl_admin.admin = tbl_pedido_status.admin ";
				if($_POST['status'] == 'aprovado'){
					$condStatus = " AND tbl_pedido_status.status in (1, 2) ";
				}elseif($_POST['status'] == 'cancelado'){
					$condStatus = " AND tbl_pedido_status.status = 14 ";
				}
			}else{
				$condStatus = " AND tbl_pedido.status_pedido = 18 ";
			}	
		} elseif ($login_fabrica == 35 && !empty($_POST['status']) and $_POST['status'] != 'ag_aprovacao') {
			if($_POST['status'] == 'aprovado'){
				$condStatus = " AND tbl_pedido.status_pedido in (1,2) AND status_fabricante = 'APROVADO' ";
			}elseif($_POST['status'] == 'cancelado'){
				$condStatus = " AND tbl_pedido.status_pedido = 14 ";
			}
		} else {
			$condStatus = " AND tbl_pedido.status_pedido = 18 ";
		}
	}else{
		 $condStatus = " AND tbl_pedido.status_pedido = 18 ";
	}

	if (!empty($_POST['estado'])) {
        $whereEstado = "
            AND LOWER(tbl_posto_fabrica.contato_estado) = LOWER('{$_POST['estado']}')
        ";
    }

	$campo_qtde_itens = (in_array($login_fabrica,array(91,191))) ? " sum(tbl_pedido_item.qtde) as qtde_item," : " count(pedido_item) as qtde_item,";
	$sql =  "SELECT DISTINCT tbl_pedido.pedido,
					tbl_pedido.total,
					TO_CHAR(tbl_pedido.data,'DD/MM/YYYY')  AS data
					$camposStatus,
					tbl_tipo_pedido.tipo_pedido,
					tbl_tipo_pedido.descricao,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto_fabrica.posto,
					tbl_posto.nome AS posto_nome,
                    			$campo_qtde_itens
                    			tbl_posto.cnpj
				FROM tbl_pedido
				JOIN tbl_pedido_item USING(pedido)
				JOIN tbl_posto ON tbl_posto.posto = tbl_pedido.posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido AND tbl_tipo_pedido.fabrica = $login_fabrica
				$joinStatusPedido
				$joinAdmin
				WHERE tbl_pedido.fabrica = $login_fabrica
				AND tbl_pedido.finalizado notnull
				$condicao1
				$condicao2
				$condStatus
				$whereEstado
				GROUP BY tbl_pedido.pedido,
					 tbl_pedido.total,
					 tbl_pedido.data
					 $camposStatusGb,
					 tbl_posto.nome,
                     			 tbl_posto.cnpj,
					 tbl_posto_fabrica.codigo_posto,
					 tbl_tipo_pedido.descricao,
					 tbl_posto_fabrica.posto,
					 tbl_tipo_pedido.tipo_pedido";
	
	$res = pg_exec($con,$sql);
	//echo nl2br($sql);

	if(pg_numrows($res) > 0){
		echo "<br>";
		echo "<table id='pedido_intervencao' class='table table-striped table-bordered table-hover table-fixed'>";
		echo "<thead>";
		echo "<tr class='titulo_coluna'>";
		echo "<th>".traduz("Pedido")."</th>";
		if ($login_fabrica == 35) {
			echo "<th>".traduz("Tipo Pedido")."</th>";
		}
		echo "<th>".traduz("Data")."</th>";
        echo "<th nowrap>".traduz("Posto")."</th>";
        if ($login_fabrica == 35) {
        	echo "<th nowrap>".traduz("CNPJ")."</th>";
        	echo "<th nowrap>".traduz("Peça Crítica")."</th>";
        }

        if ($telecontrol_distrib) {
        	echo "<th nowrap>".traduz("CNPJ")."</th>";
        }

		$label_pecas = 'Peças';

        if ($login_fabrica == 91) {
        	echo "<th nowrap>".traduz("OS")."</th>";
			$label_pecas = 'Itens';
        }

		if(!in_array($login_fabrica,array(91,104,105,140)) AND (!isset($novaTelaOs))){
			echo "<th nowrap>".traduz("Peça Crítica")."</th>";
		}
		if(in_array($login_fabrica, array(141,144))){
			echo "<th>".traduz("Comprovante")."</th>";
		}
		echo "<th>".traduz("Qtde")."<br>$label_pecas</th>";
		if($telaPedido0315){
			echo "<th class='price'>".traduz("Total Pedido")."</th>";
			if(!empty($_POST['status']) and $_POST['status'] != 'ag_aprovacao'){
				echo "<th>".traduz("Admin")."</th>";
				echo "<th>".traduz("Data")."</th>";
			}
		}
	
		if ($login_fabrica == 191){
			echo "<th nowrap>".traduz("Filial")."</th>";
		}

		if ($login_fabrica == 194){
			echo "<th nowrap>".traduz("Motivo Intervenção")."</th>";
		}

		echo "<th nowrap>".traduz("Ações")."</th></tr>";
		echo "</thead>";
		echo "<tbody>";

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$pedido             = trim(pg_result($res,$i,pedido));
			$data               = trim(pg_result($res,$i,data));
			$codigo_posto       = trim(pg_result($res,$i,codigo_posto));
			$posto_nome         = trim(pg_result($res,$i,posto_nome));
            $cnpj               = trim(pg_result($res,$i,cnpj));
			$qtde_item          = trim(pg_result($res,$i,qtde_item));
			$total 				= trim(pg_fetch_result($res, $i, 'total'));
			$data_pedido_status	= trim(pg_fetch_result($res, $i, 'data_pedido_status'));
			$nome_completo		= trim(pg_fetch_result($res, $i, 'nome_completo'));
			$obsStatusPedido	= trim(pg_fetch_result($res, $i, 'obsStatusPedido'));
			$tipo_pedido	    = trim(pg_fetch_result($res, $i, 'descricao'));
			$tipo_pedido_codigo = trim(pg_fetch_result($res, $i, 'tipo_pedido'));
			$posto              = trim(pg_fetch_result($res, $i, 'posto'));

			if ($login_fabrica == 194){
				$sql_motivo_intervencao = "
					SELECT pedido_item
					FROM tbl_pedido_item WHERE pedido = $pedido 
					AND qtde >= 10";
				$res_motivo_intervencao = pg_query($con, $sql_motivo_intervencao);

				if (pg_num_rows($res_motivo_intervencao) > 0){
					$motivo_intervencao = "Intervenção por qtde de peças";
				}else{
					$motivo_intervencao = "Intervenção pedido venda";
				}
			}

			$cor= ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
			
			echo "<tr>";
			echo "<td class='tac'><a href='pedido_admin_consulta.php?pedido=$pedido' target='_blank'>$pedido</a></td>";
			if ($login_fabrica == 35) {
				echo "<td class='tac' nowrap>$tipo_pedido</td>";
			}
			echo "<td class='tac'>$data</td>";
			echo "<td class='tal' nowrap title='$codigo_posto $posto_nome'>$codigo_posto ".substr($posto_nome,0,15)."</td>";

			if(in_array($login_fabrica,array(91)) ){
				echo "<td class='tac' id='verOS_$i'><a href='javascript:verOS($pedido,\"dados_$i\",\"verOS_$i\",$i)'>VER</a></td>";
			}
			
			if ($telecontrol_distrib) {

	            echo "<td class='tal' nowrap>";
	            echo preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/","\$1.\$2.\$3/\$4-\$5", $cnpj);
	            echo '<br>';
	            echo $cnpj;
	            echo '</br>';
	            echo '</td>';
	        }

			/*if(!in_array($login_fabrica,array(35,104,105,140)) AND (!isset($novaTelaOs))){
				echo "<td class='tac' id='ver_$i'><a href='javascript:verPeca($pedido,\"dados_$i\",\"ver_$i\",$i)'>VER</a></td>";
			}*/

			if (in_array($login_fabrica, [35])) {

	            echo "<td class='tal' nowrap>";
	            echo preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/","\$1.\$2.\$3/\$4-\$5", $cnpj);
	            echo '<br>';
	            echo $cnpj;
	            echo '</br>';
	            echo '</td>';
	        }

			if((!in_array($login_fabrica,array(91,11,104,105,140,172)) AND (!isset($novaTelaOs))) or $login_fabrica == 35){

				echo "<td class='tac' id='ver_$i'><a href='javascript:verPeca($pedido,\"dados_$i\",\"ver_$i\",$i)'>VER</a></td>";
			}

			if(in_array($login_fabrica, array(141,144))){

                $comprovante = $s3->getObjectList("thumb_comprovante_pedido_{$login_fabrica}_{$pedido}");
                $link_img	 = $comprovante[0];

                if(!empty($link_img)){

                	$link_img = str_replace("thumb_", "", $link_img);
	                $link_img = explode("/", $link_img);
	                $link_img = $link_img[count($link_img) -1];

	                $comprovante = basename($comprovante[0]);
	                $comprovante = $s3->getLink($comprovante);

	                echo "<td class='tac td_comprovante_{$pedido}'>
	                		<a href='{$comprovante}' target='_blank'><img src='{$comprovante}' style='max-width: 100px; max-height: 100px;_height:100px;*height:100px;' /></a>
	                		<br />
	                		<a href='javascript: boxComunicadoComprovante(\"$pedido\")'>".traduz("Excluir e solicitar outro Comprovante")."</a>

	                		<div class='box_comunicado_comprovante_{$pedido}' style='display: none;'>
	                			<textarea name='comunicado_comprovante_{$pedido}' id='comunicado_comprovante_{$pedido}' rows='3' placeholder='Digite um motivo'></textarea> <br />
	                			<button type='button' onclick='enviarComunicado(\"$pedido\", \"$codigo_posto\", \"$link_img\")'>".traduz("Enviar Motivo")."</button>
	                		</div>

	                	</td>";

                }else{
                	echo "<td class='tac'>".traduz("Sem Comprovante de Pagamento Inserido")."</td>";
                }

			}
			echo "<td class='tac' title='".traduz("Quantidade de item:")."$qtde_item'>$qtde_item</td>";
			if($telaPedido0315){
				echo "<td class='tac'>".number_format($total,2,",",".")."</td>";
				if(!empty($_POST['status']) and $_POST['status'] != 'ag_aprovacao' ){
					echo "<td>$nome_completo</td>";
					echo "<td>$data_pedido_status</td>";
				}
			}

			if ($login_fabrica == 194){
				echo "<td class='tac'>$motivo_intervencao</td>";
			}

			if($login_fabrica == 191){
				echo "<td class='tac' style='width: 20%'>
					<select class='filial_$pedido span6' name='posto_$i'>
                                		<option value=''>Selecione a Filial</option>";
                                			$sqlFilial = "
								SELECT tbl_posto.cnpj, tbl_posto.posto,tbl_posto.nome, tbl_posto_fabrica.contato_estado 
                                              			FROM tbl_posto  
                                              			INNER JOIN tbl_posto_fabrica USING(posto)
                                              			INNER JOIN tbl_tipo_posto ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica}
                                              			WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
                                              			AND tbl_tipo_posto.posto_interno Is TRUE 
                                              			ORDER BY tbl_posto.nome ASC;";
                                			$resFilial = pg_query($con, $sqlFilial);

                                			foreach (pg_fetch_all($resFilial) as $pa) {
                                    				$selected = ($pa['posto'] == $posto) ? "SELECTED" : "";
                                    				echo "<option value='{$pa['posto']}' {$selected}> {$pa['cnpj']} {$pa['nome']} - {$pa['contato_estado']}</option>";
                                			}			
				echo "</select></td>";
			}

			$text_align = (in_array($login_fabrica, [11,172])) ? "style='text-align : center !important;'" : "";

			if($login_fabrica == 161){
				if(empty($_POST['status']) or $_POST['status'] == 'ag_aprovacao'){
					echo "<td class='tac'>
							<div id='acoes_$i' class='tac'> <input type='button' class='btn btn-success btn-small tac' ALT='Autorizar Pedido' onClick='javascript: fnc_autorizar($pedido,\"acoes_$i\");' value='Autorizar'/>";
				}else{
					echo "<td class='tac'><div id='acoes_$i'>";
					echo "$obsStatusPedido";
				}
			}else{
				echo "<td class='tac' id='acoes_$i' nowrap> <input type='button' class='btn btn-success btn-small tac' ALT='Autorizar Pedido' onClick='javascript: fnc_autorizar($pedido,\"acoes_$i\");' value='Autorizar'/>";	
			}

			if(in_array($login_fabrica, array(167,203))){
				echo "<input pedido='$pedido' id='pedido_cadastro' type='button' class='btn btn-small' target='_blank' value='Alterar'/>";
 			}
 			if (in_array($login_fabrica, array(35)) && $tipo_pedido_codigo == 114) {//valida se tipo de pedido é FAT "114" para FAB 35
				echo "<input style='margin-left: 5px' type='button' class='btn btn-small btn-primary' onClick='alterar($pedido)' value='Alterar'/> ";
 			}

			if(in_array($login_fabrica,array(35,104,105,140,167,203))){
				echo " <input type='button' class='btn btn-danger btn-small tac' ALT='Cancelar Pedido' onClick='javascript: mostraLinha(\"linha_motivo_$i\");' value='Cancelar'></div>";
			}elseif($login_fabrica == 161) {
				if(empty($_POST['status']) or $_POST['status'] == 'ag_aprovacao'){
					echo " <input type='button' class='btn btn-danger btn-small tac' ALT='Cancelar Pedido' onClick='javascript: fnc_cancelar($pedido,\"acoes_$i\");' value='Cancelar'/> ";
				}			
			}
			 else {
			echo " <input type='button' class='btn btn-danger btn-small tac' ALT='Cancelar Pedido' onClick='javascript: fnc_cancelar($pedido,\"acoes_$i\", \"$posto\");' value='Cancelar'/></div>";
			}


			//if(in_array($login_fabrica, array(161,162,163,164))){
			if($login_fabrica >= 161 AND !in_array($login_fabrica, [167, 203])){
				if($login_fabrica == 161){
					if(empty($_POST['status']) or $_POST['status'] == 'ag_aprovacao'){
						echo " <input type='button' class='btn btn-small' onClick='alterar($pedido)' value='Alterar'/> </div>";
					}
				}else if ($login_fabrica != 183) {
					echo " <input type='button' class='btn btn-small' onClick='alterar($pedido)' value='Alterar'/>";
				}
				
			}


			echo "</td>";
			echo "</tr>";
			if(!in_array($login_fabrica, array(160,161)) and !$replica_einhell){
				echo "<tr>";
				echo "<td colspan='100%' style='display:none' id='ver_dados_$i'><div id='dados_$i'></div></td>";
				echo "</tr>";
				echo "<tr id='linha_motivo_$i' style='display:none;'>";
				echo "<td colspan='100%'>
					Motivo : <input type='text' name='motivo_$i' id='motivo_$i' size='110' />
				        <input type='button' value='Ok' onclick='fnc_cancelar($pedido,\"acoes_$i\",\"motivo_$i\",\"linha_motivo_$i\");'>
				      </td>";
				echo "</tr>";
			}
		}
		echo "</tbody></table></center>";
	}else{
		echo "<div class='alert alert-warning alert alert_shadobox' role='alert'><h4>Nenhum resultado encontrado</h4></div>";
	}
}
?>
<br><br><br>
</div>
<script type="text/javascript">

        var tds = $('#pedido_intervencao').find(".titulo_coluna");
        var colunas = [];
        $(tds).find("th").each(function(){
            if ($(this).attr("class") == "price") {
                colunas.push({"sType":"numeric"});
            } else {
                colunas.push(null);
            }
        });
        //console.log(colunas);
    $.dataTableLoad({ table: "#pedido_intervencao" });
</script>

<? include "rodape.php" ?>
