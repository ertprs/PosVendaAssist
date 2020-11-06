<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

if (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) {
	include 'autentica_admin.php';
} else {
	include 'autentica_usuario.php';
}

include 'funcoes.php';

include_once '../class/communicator.class.php';

$pedido = $_REQUEST['pedido'];

function limpaString ($string){
	$stringLimpa = str_replace("'", "", $string);
	return $stringLimpa;
}

if(isset($_POST["gravar_pedido_item"])){

		$qtde           = $_POST["qtde"];
		$qtde_anterior  = $_POST["qtde_anterior"];
		$pedido_item    = $_POST["pedido_item"];
		$pedido         = $_POST["pedido"];
		$pedido_dewalt  = $_POST["pedido_dewalt"];
		$auditoria 		= $_POST["auditoria"];
		$justificativa  = $_POST["justificativa"];
	 
		$observacao = pg_escape_string(retira_acentos(utf8_decode($_POST['observacao'])));

		$sql = "SELECT tbl_pedido.posto,
					tbl_pedido.seu_pedido,   
					tbl_pedido_item.obs,
					tbl_pedido_item.valores_adicionais, 
					tbl_peca.referencia,
					tbl_peca.descricao 
				FROM tbl_pedido_item 
				JOIN tbl_peca USING(peca) 
				join tbl_pedido using(pedido) 
				WHERE pedido_item = $pedido_item";
		$res = pg_query($con, $sql);

		$observacao_anterior = pg_fetch_result($res, 0, "obs");
		$referencia_peca     = pg_fetch_result($res, 0, "referencia");
		$descricao_peca      = pg_fetch_result($res, 0, "descricao");
		$posto 				 = pg_fetch_result($res, 0, "posto");
		$seu_pedido 		 = pg_fetch_result($res, 0, 'seu_pedido');
		$valores_adicionais  = pg_fetch_result($res, 0, "valores_adicionais");

		$observacao_comunicado = "A quantidade solicitada $qtde_anterior unidades, foi alterada para $qtde unidades.";
		if ($pedido_dewalt != 't') {
			$observacao_nova = "A quantidade solicitada $qtde_anterior unidades, foi alterada para $qtde unidades. <br>  <b>Observação do admin</b>: ";
			$observacao_nova .= $observacao;
		} else {
			$observacao_nova       = "$observacao_anterior <br/><br/> <b>".date('d/m/Y H:i:s')."</b> - A quantidade solicitada $qtde_anterior unidades, foi alterada para $qtde unidades.";
		}
	
		/*$sqlPedido = "UPDATE tbl_pedido set aprovacao_tipo =  '$auditoria', aprovacao_obs = '$observacao' WHERE pedido = $pedido and fabrica = $login_fabrica ";
		$resPedido = pg_query($con, $sqlPedido);
		$erro .= pg_last_error($con);*/

		if(strlen(trim($valores_adicionais))>0){
			$valores_adicionais = json_decode($valores_adicionais, true);
		}

		$valores_adicionais['auditoria'] = $auditoria;
		$valores_adicionais['observacao'] = $observacao;
		$valores_adicionais['aprovado'] = "true";
		$valores_adicionais['demanda'] = "true";
		$valores_adicionais['alterada'] = "true";
		$valores_adicionais['admin'] = $login_admin;
		$valores_adicionais['msg_comunicado'] = $observacao_comunicado;
		$valores_adicionais = json_encode($valores_adicionais);


		$sql = "UPDATE tbl_pedido_item SET obs = '$justificativa', qtde = $qtde, valores_adicionais = '$valores_adicionais'  WHERE pedido_item = $pedido_item ";
		$res = pg_query($con, $sql);
		$erro .= pg_last_error($con);

		if (strpos($erro,"CONTEXT:")) {// retira CONTEXT:
			$x = explode('CONTEXT:',$erro);
			$erro = $x[0];
		}

		if(strlen(trim($erro))==0 and $login_fabrica == 1){
				$sql_finaliza_pedido = "SELECT fn_finaliza_pedido_blackedecker($pedido,$login_fabrica)";
				$res_finaliza_pedido = pg_query($con, $sql_finaliza_pedido);

				$sqlPosto = "SELECT posto,seu_pedido 
										 FROM tbl_pedido
										 WHERE tbl_pedido.pedido = $pedido";
				$resPosto = pg_query($con,$sqlPosto);

				$posto      = pg_fetch_result($resPosto, 0, 'posto');
				$seu_pedido = pg_fetch_result($resPosto, 0, 'seu_pedido');

				$mensagem = "Item do pedido $seu_pedido alterado";
				if ($pedido_dewalt == 't') {
					$mensagem = "Alteração de pedido Rental - $seu_pedido";
				}

				$sqlComunicado = "INSERT INTO tbl_comunicado (mensagem,descricao,posto,fabrica,tipo,ativo,obrigatorio_site,pais,obrigatorio_os_produto,digita_os,reembolso_peca_estoque,destinatario_especifico) 
													VALUES ('Pedido $seu_pedido alterado: <br /><br /> 
															 Peça: $referencia_peca - $descricao_peca <br /><br />
															  $observacao_comunicado<br />','$mensagem',$posto,$login_fabrica,'Comunicado Automatico','t','t','BR','f',null,null,'')";
				$res = pg_query($con,$sqlComunicado);
				
				$erro .= pg_last_error();
		}

		if(strlen(trim($erro))==0){
			$sqlPosto = "SELECT contato_email FROM tbl_posto_fabrica where posto = $posto and fabrica = $login_fabrica ";
			$resPosto = pg_query($con, $sqlPosto);
			if(pg_num_rows($resPosto)>0){
				$email = pg_fetch_result($resPosto, 0, 'contato_email');
				$assunto = "Alteração do Pedido $seu_pedido";
				$mensagem = " Pedido $seu_pedido alterado: <br /><br /> 
															 Peça: $referencia_peca - $descricao_peca <br /> <br/> $observacao_comunicado <Br>  Mensagem: ". utf8_decode($observacao);

				$mailTc = new TcComm($externalId);
				$res = $mailTc->sendMail(
					$email,
					$assunto,
					$mensagem,
					'no-reply@telecontrol.com.br'
				);
			}
			finalizar($pedido);
			echo "ok";
		}else{
			echo $erro;
		}

exit;
}

if (isset($_POST['grava_obs_peca'])) {
	$pedido_item    = $_POST['item_pedido'];
	$observacao     = utf8_decode(pg_escape_string($_POST["obs"]));

	$sql = "SELECT obs FROM tbl_pedido_item WHERE pedido_item = $pedido_item";
	$res = pg_query($con, $sql);

	$observacao_anterior = pg_fetch_result($res, 0, "obs");

	$nova_observacao     = "$observacao_anterior <br /><br /> <b> ".date('d/m/Y H:i:s')." Obs. do admin</b>: $observacao ";

	$sql = "UPDATE tbl_pedido_item SET obs = '$nova_observacao' WHERE pedido_item = $pedido_item ";
	$res = pg_query($con, $sql);

	$sqlPosto = "SELECT tbl_pedido.posto,tbl_pedido.seu_pedido 
										 FROM tbl_pedido_item
										 JOIN tbl_pedido USING(pedido)
										 WHERE tbl_pedido_item.pedido_item = $pedido_item";
	$resPosto = pg_query($con,$sqlPosto);

	$posto      = pg_fetch_result($resPosto, 0, 'posto');
	$seu_pedido = pg_fetch_result($resPosto, 0, 'seu_pedido');

	$sqlComunicado = "INSERT INTO tbl_comunicado (mensagem,descricao,posto,fabrica,tipo,ativo,obrigatorio_site,pais,obrigatorio_os_produto,digita_os,reembolso_peca_estoque,destinatario_especifico) 
													VALUES ('Pedido $seu_pedido alterado:<br /><br /> $observacao<br />','Alteração de pedido Rental',$posto,$login_fabrica,'Comunicado Automatico','t','t','BR','f',null,null,'')";
	$res = pg_query($con,$sqlComunicado);

	exit("Observação gravada com sucesso");
}

if (isset($_POST["grava_todos"])) {

	$pedido               = $_POST['pedido'];               
	$justificativa_aprova = pg_escape_string($_POST['justificativa']);
	$auditoria_aprova     = $_POST['auditoria'];
	$ms_erro              = "";
	$ms                   = "";

	if (!empty($pedido)) {
		$sql_pedido_item = "SELECT  tbl_pedido_item.pedido_item
							FROM tbl_pedido_item
							INNER JOIN tbl_pedido ON tbl_pedido.pedido = tbl_pedido_item.pedido
							INNER JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca
							WHERE tbl_pedido_item.pedido = $pedido
							AND (tbl_pedido_item.valores_adicionais->>'aprovado'::text <> 'true' OR tbl_pedido_item.valores_adicionais IS NULL)
							AND tbl_pedido_item.qtde > 2
							AND tbl_pedido.fabrica = $login_fabrica";
		$res_pedido_item = pg_query($con, $sql_pedido_item);
		if (pg_num_rows($res_pedido_item) > 0) {
			for ($p=0; $p < pg_num_rows($res_pedido_item); $p++) { 
				$pedido_item  = pg_fetch_result($res_pedido_item, $p, 'pedido_item');

				$sql_item = "SELECT valores_adicionais from tbl_pedido_item where pedido_item = $pedido_item";
				$res_item = pg_query($con, $sql_item);

				if(pg_num_rows($res_item)>0) {
					$valores_adicionais = pg_fetch_result($res_item, 0, 'valores_adicionais');

					$valores_adicionais = json_decode($valores_adicionais, true);
					$valores_adicionais['aprovado'] = "true";
					$valores_adicionais['demanda'] = "true";
					$valores_adicionais['admin'] = $login_admin;
					$valores_adicionais['auditoria'] = $auditoria_aprova;
					$valores_adicionais = json_encode($valores_adicionais);

					$sql_del_pedido_item = "UPDATE tbl_pedido_item set obs = '$justificativa_aprova', valores_adicionais = '$valores_adicionais' WHERE pedido_item = $pedido_item";
					$res_del_pedido_item = pg_query($con, $sql_del_pedido_item);
					$erro = pg_last_error();

					if(strlen(trim($erro)) > 0){
						$ms_erro = "Erro ao aprovar";
					}
				}else{
					$ms_erro = "Falha ao aprovar item";
				}
			}

			if (empty($ms_erro)) {
				if (strlen(trim($erro))==0){
					finalizar($pedido);
					$ms = "ok";
				}
			}
		} else {
			$ms_erro = 'Não Possui itens para aprovar';
		}
	} else {
		$ms_erro = 'Erro pedido';
	} 

	if ($ms == 'ok') {
		echo 'ok';
	} else if (!empty($ms_erro)) {
		echo $ms_erro;
	}

	exit;
}

if(isset($_POST["aprovar_item"])){

	$pedido_item  = (int)$_POST["pedido_item"];
	$auditoria_aprova = $_POST["auditoria_aprova"];
	$justificativa_aprova = pg_escape_string($_POST["justificativa_aprova"]);
	$pedido = $_POST['pedido'];

	$sql_item = "SELECT valores_adicionais from tbl_pedido_item where pedido_item = $pedido_item";
	$res_item = pg_query($con, $sql_item);

	if(pg_num_rows($res_item)>0) {
		$valores_adicionais = pg_fetch_result($res_item, 0, 'valores_adicionais');

		$valores_adicionais = json_decode($valores_adicionais, true);
		$valores_adicionais['aprovado'] = "true";
		$valores_adicionais['demanda'] = "true";
		$valores_adicionais['admin'] = $login_admin;
		$valores_adicionais['auditoria'] = $auditoria_aprova;
		$valores_adicionais = json_encode($valores_adicionais);

		$sql_del_pedido_item = "UPDATE tbl_pedido_item set obs = '$justificativa_aprova', valores_adicionais = '$valores_adicionais' WHERE pedido_item = $pedido_item";
		$res_del_pedido_item = pg_query($con, $sql_del_pedido_item);

		$erro = pg_last_error();

		if(strlen(trim($erro))==0){
			finalizar($pedido);
			echo "ok";
		}else{
			echo $erro;
		}
	}else{
		echo "Falha ao aprovar item";
	}
	exit;
}


if(isset($_POST["excluir"])){

	$pedido_item  = (int)$_POST["pedido_item"];
	$pedido       = (int)$_POST["pedido"];
	$auditoria       = $_POST["auditoria"];
	$observacao   = pg_escape_string(retira_acentos(utf8_decode($_POST['observacao'])));
	$justificativa   = pg_escape_string($_POST['justificativa']);

	$qtde_anterior  = $_POST["qtde_anterior"];

	$observacao_comunicado = "A quantidade solicitada $qtde_anterior unidades, foi alterada para 0(zero) unidade.";

	$sql_item = "SELECT valores_adicionais from tbl_pedido_item where pedido_item = $pedido_item";
	$res_item = pg_query($con, $sql_item);

	if(pg_num_rows($res_item)>0) {
		$valores_adicionais = pg_fetch_result($res_item, 0, 'valores_adicionais');

		$valores_adicionais = json_decode($valores_adicionais, true);
		$valores_adicionais['aprovado'] = "true";
		$valores_adicionais['excluido'] = "true";
		$valores_adicionais['demanda'] = "true";
		$valores_adicionais['admin'] = $login_admin;
		$valores_adicionais['auditoria'] = $auditoria;
		$valores_adicionais['observacao'] = $observacao;
		$valores_adicionais['msg_comunicado'] = $observacao_comunicado;
		$valores_adicionais = json_encode($valores_adicionais);
	}

	$sql_del_pedido_item = "UPDATE tbl_pedido_item set obs='$justificativa', qtde = 0, valores_adicionais = '$valores_adicionais' WHERE pedido_item = $pedido_item";

	$res_del_pedido_item = pg_query($con, $sql_del_pedido_item);

	$erro = pg_last_error();

	$sql_peca = "select tbl_peca.referencia, tbl_peca.descricao  from tbl_pedido_item join tbl_peca on tbl_peca.peca = tbl_pedido_item.peca and tbl_peca.fabrica = $login_fabrica where tbl_pedido_item.pedido_item = $pedido_item ";
	$res_peca = pg_query($con, $sql_peca);
	if(pg_num_rows($res_peca)>0){
		$referencia_peca = pg_fetch_result($res_peca, 0, "referencia");
		$descricao_peca  = pg_fetch_result($res_peca, 0, "descricao");
	}

	if(strlen(trim($erro))==0 ){
		 if($login_fabrica == 1){
				$sql_finaliza_pedido = "SELECT fn_finaliza_pedido_blackedecker($pedido,$login_fabrica)";
				$res_finaliza_pedido = pg_query($con, $sql_finaliza_pedido);
				$erro = pg_last_error();
		}else{
				$sql_finaliza_pedido = "SELECT fn_pedido_finaliza($pedido,$login_fabrica)";
				$res_finaliza_pedido = pg_query($con, $sql_finaliza_pedido);
				$erro = pg_last_error();
		}
	}

	if(strlen(trim($erro))==0){
		$sqlPosto = "SELECT contato_email FROM tbl_posto_fabrica join tbl_pedido on tbl_pedido.posto = tbl_posto_fabrica.posto and tbl_pedido.fabrica = $login_fabrica where tbl_posto_fabrica.fabrica = $login_fabrica and tbl_pedido.pedido = $pedido ";
			$resPosto = pg_query($con, $sqlPosto);
			if(pg_num_rows($resPosto)>0){
				$email = pg_fetch_result($resPosto, 0, 'contato_email');
				$assunto = "Alteração do Pedido $seu_pedido";
				$mensagem = " Pedido $seu_pedido alterado: <br /><br /> 
															 Peça: $referencia_peca - $descricao_peca <br /> <br/> 
A quantidade solicitada $qtde_anterior unidades, foi alterada para 0(zero) .

															  <Br>  Mensagem: ".utf8_decode($observacao);

				$mailTc = new TcComm($externalId);
				$res = $mailTc->sendMail(
					$email,
					$assunto,
					$mensagem,
					'no-reply@telecontrol.com.br'
				);
			}

		finalizar($pedido);
		echo "ok";
	}else{
		echo $erro;
	}

	exit;
}


//$res = pg_query($con,"BEGIN TRANSACTION");
//pg_query($con,"ROLLBACK TRANSACTION");


//if(isset($_POST["finalizar"])){
function finalizar($pedido){
	global $con, $login_fabrica, $login_admin; 

	if($login_fabrica == 1){
		$sql = "SELECT fn_finaliza_pedido_blackedecker($pedido,$login_fabrica)";
		$res = pg_query($con, $sql);
		$erro = pg_last_error();
	}

	if(strlen(trim($erro))==0){
		//verifica se tem item para ser aprovado. 
		$sqlVerifica = "SELECT pedido_item from tbl_pedido_item where  pedido = $pedido and valores_adicionais::JSON->>'aprovado' <> 'true' and valores_adicionais::JSON->>'demanda' = 'true'; ";
		$resVerifica = pg_query($con, $sqlVerifica);

		if(pg_num_rows($resVerifica)==0){	

			$sql1 = "UPDATE tbl_pedido SET status_pedido = 1 WHERE pedido = $pedido AND fabrica = $login_fabrica and status_pedido = 18";
			$res1 = pg_query($con, $sql1);
			$erro = pg_last_error();

			$sql2 = "INSERT INTO tbl_pedido_status(pedido,status,admin) VALUES($pedido,1,$login_admin)";
			$res2 = pg_query($con, $sql2);
			$erro = pg_last_error();
		}
	}
	echo $erro;
	
}

if(isset($_POST["cancelar"])){
		$pedido = (int)$_POST["pedido"];
		$motivo = "Cancelamento Total";

		$sql = "UPDATE tbl_pedido SET status_pedido = 14 WHERE pedido = $pedido AND fabrica = $login_fabrica";
		$res = pg_query($con, $sql);

		$erro = pg_last_error();

		if(strlen(trim($erro))==0){
					$sql1 = "UPDATE tbl_pedido_item
					SET qtde_cancelada = tbl_pedido_item.qtde - tbl_pedido_item.qtde_faturada
					WHERE pedido = $pedido";
					$res1 = pg_query($con, $sql1);

					$sql2 = "INSERT INTO tbl_pedido_cancelado(pedido,posto,fabrica,peca,qtde,motivo,data ,admin)
					SELECT pedido,posto,fabrica,peca,qtde,'$motivo',current_date, $login_admin
					FROM tbl_pedido JOIN tbl_pedido_item USING(pedido) WHERE pedido = $pedido";
					$res2 = pg_query($con, $sql2);
					
					$sql3 = "INSERT INTO tbl_pedido_status(pedido,status,observacao,admin) VALUES($pedido,14,'$motivo',$login_admin)";
					$res3 = pg_query($con, $sql3);
		}

		if(strlen(trim($erro))==0){
			 echo "ok";
		}else{
			 echo $erro;
		}

exit;
}
?>
<!DOCTYPE html />
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
		<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />

		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="bootstrap/js/bootstrap.js"></script>
		<script src="plugins/dataTable.js"></script>
		<script src="plugins/resize.js"></script>
		<script src='plugins/shadowbox_lupa/shadowbox.js'></script>
		<script src="plugins/shadowbox_lupa/lupa.js"></script>

		<script>

		function aprovar_todos_item(pedido) {
			if ($("#auditoria_aprova_todos option:selected").val() == "" || $("#justificativa_aprova_todos").val() == "") {
				if ($("#auditoria_aprova_todos option:selected").val() == "" || $("#auditoria_aprova_todos option:selected").val() == undefined) {
					$(".errorauditoria_aprova_todos_aud").show();
				} else {
					$(".errorauditoria_aprova_todos_aud").hide();
				}

				if ($("#justificativa_aprova_todos").val() == "") {
					$(".errorjust_aprova_todos_just").show();
				} else {
					$(".errorjust_aprova_todos_just").hide();
				}
			 //alert ('passo 1');

			} else {
    			//alert ('passo 2');
				$(".btn_grava_todos").hide();
				$(".loading_aprova_todos").show();
				var aud = "";
				var just = "";
				aud = $("#auditoria_aprova_todos").val();
				just = $("#justificativa_aprova_todos").val();
				grava_todos_itens(pedido, aud, just);
			}


		}	

		function grava_todos_itens(pedido, aud, just) {
			$.ajax({
				url: 'pecas_pedido.php',
				type: 'POST',
				data: {grava_todos: true, pedido: pedido, auditoria: aud, justificativa: just},
			})
			.done(function(data) {
				if (data == 'ok') {
					window.parent.$("#aprovou_pedido").val('1');
					window.parent.location.reload();
					window.parent.Shadowbox.close();
				} else {
					$(".btn_grava_todos").show();
					$(".loading_aprova_todos").hide();
					$('.alert_ajax').show();
					$("#h4_texto").html(data).focus();
					
				}
			});
		}	

		function mostrar_aprovar_todos() {
			$("#tr_grava_todos").hide();
			$(".grava_tdos").show();
		}	



		function alterar_item(pedido_item, posicao){

			$(".linha_oculta_alteracao_"+posicao).show();
			$(".linha_oculta_aprovacao_"+posicao).hide();	
			$(".linha_oculta_"+posicao).hide();	

			$("#observacao_alteracao_"+posicao).val("");
			
			$(".qtde_"+posicao).attr("readonly", false);
			//$("#grava_obs_pedido").attr("pedidoitem",pedido_item);
			//$("#grava_obs_pedido").attr("posicao",posicao);
			$("#observacao").val("");
			//$("#grava_obs_cancelar").show();
			$("#grava_obs_pedido").show();
			$("#observacao").val($(".observacao_"+posicao).val());
			$("#loading_obs").hide();
		}

		function gravar_item(pedido_item, posicao, observacao, auditoria, justificativa){

			var qtde = parseInt($(".qtde_"+posicao).val());
			var qtde_anterior = parseInt($(".qtde_anterior_"+posicao).val());
			var pedido = $("#pedido").val();
			var pedido_dewalt = $("#pedido_dewalt").val();
	

			if(qtde > qtde_anterior && pedido_dewalt != 't'){
					alert("A quantidade da peca não pode ser maior que a solicitada anteriormente.");
					return false;
			}else{
				$.ajax({
					url: "pecas_pedido.php",
					type: "post",
					data: { gravar_pedido_item: true, auditoria:auditoria, pedido_item: pedido_item, observacao: observacao, justificativa : justificativa, qtde: qtde, qtde_anterior: qtde_anterior, pedido: pedido, pedido_dewalt: pedido_dewalt},      
					beforeSend: function () {
                            $(".gravar_alteracao_"+posicao).hide();
                            $(".loading_alteracao_"+posicao).show(); 
                        }, 
					success: function(data) {
						if(data == "ok"){
							//alert("Pedido alterado com sucesso.");
							//$(".alterar").show();
							$(".linha_oculta_alteracao_"+posicao).hide();
							$(".qtde_"+posicao).attr("readonly", true);
							//$(".close").click();

							$(".celula_aprovar_"+posicao).text("");
							$(".td_alterar_"+posicao).text('Peça alterada com sucesso.');
							$(".celula_excluir_"+posicao).text('');
							$(".linha_"+posicao+" td").css("background","#81DAF5");	

						}else{
							alert(data);
						}              
					}
				});
			}
		}

		function excluir_item(pedido_item, posicao){
			$(".linha_oculta_"+posicao).show();
			$(".linha_oculta_aprovacao_"+posicao).hide();
			$(".linha_oculta_alteracao_"+posicao).hide();
		}

		function mostrar_aprovar(posicao){
			$(".linha_oculta_aprovacao_"+posicao).show();	
			$(".linha_oculta_"+posicao).hide();	
			$(".linha_oculta_alteracao_"+posicao).hide();
		}

		function aprovar_item(pedido_item, posicao){		
			var error = false;
			var auditoria_aprova = $("#auditoria_aprova_"+posicao).val();
			var justificativa_aprova = $("#justificativa_aprova_"+posicao).val();
			var pedido = $("#pedido").val();

			$(".errorauditoria_aprova_"+posicao).text("");
			if(auditoria_aprova.length == 0){
				$(".errorauditoria_aprova_"+posicao).text("Informe a auditoria");
				error = true;
			}

			$(".errorjust_aprova_"+posicao).text("");
			if(justificativa_aprova.length == 0){
				$(".errorjust_aprova_"+posicao).text("Informe a justificatica");
				error = true;
			}

			if(error == false){			
				$.ajax({
					url: "pecas_pedido.php",
					type: "post",
					data: { aprovar_item: true, pedido_item: pedido_item, pedido:pedido, auditoria_aprova:auditoria_aprova, justificativa_aprova:justificativa_aprova   }, 
					beforeSend: function () {
	                    $(".gravar_aprova_"+posicao).hide();
	                    $(".loading_aprova_"+posicao).show(); 
	                },
					success: function(data) {
						if(data == "ok"){
							$(".celula_aprovar_"+posicao).text("Peça aprovada com sucesso.");
							$(".td_alterar_"+posicao).text('');
							$(".celula_excluir_"+posicao).text('');
							$(".linha_"+posicao+" td").css("background","#A9F4AD");	
							$(".linha_oculta_aprovacao_"+posicao).hide();
							window.parent.$("#aprovou_pedido").val('1');	
						}else{
							alert(data);
						}              
					}
				});
			}
		}

		function confirma_gravacao(pedido_item, posicao){
			var error = false;
			var observacao = $("#observacao_excluir_"+posicao).val();
			var justificativa = $("#justificativa_excluir_"+posicao).val();
			var auditoria = $("#auditoria_excluir_"+posicao).val();

			var pedido = $("#pedido").val();
			var qtde_anterior = parseInt($(".qtde_anterior_"+posicao).val());

			$(".erro_linha_"+posicao).text('');
			if(observacao.length == 0){
				$(".erro_linha_"+posicao).text('O campo motivo é obrigatório.');
				error = true;
			}	

			$(".errorauditoria_excluir_"+posicao).text("");
			if(auditoria.length == 0){
				$(".errorauditoria_excluir_"+posicao).text("Informe a auditoria");
				error = true;
			}

			$(".errorjust_excluir_"+posicao).text("");
			if(justificativa.length == 0){
				$(".errorjust_excluir_"+posicao).text("Informe a justificatica");
				error = true;
			}

			if(error == false){
				$.ajax({
					url: "pecas_pedido.php",
					type: "post",
					data: { excluir: true, pedido_item: pedido_item, pedido:pedido, auditoria: auditoria, justificativa:justificativa, observacao: observacao, qtde_anterior: qtde_anterior },
					beforeSend: function () {
                            $(".gravar_excluir_"+posicao).hide();
                            $(".loading_excluir_"+posicao).show(); 
                        }, 
					success: function(data) {
						if(data == "ok"){
							alert("Peça excluí­da com sucesso.");
							$(".celula_excluir_"+posicao).text("Quantidade Excluída");
							//$(".linha_"+posicao).remove();
							$(".linha_oculta_"+posicao).remove();
							$(".linha_oculta_aprovacao_"+posicao).hide();
							$(".td_alterar_"+posicao).text("");
							$(".celula_aprovar_"+posicao).text('');
							$(".linha_"+posicao+" td").css("background","#FE9A2E");	

						}else{
							alert(data);
						}              
					}
				});		
			}	
		}


		/*function finalizar(){
			var pedido = $("#pedido").val();
			alert(pedido);
			
			$.ajax({
				url: "pecas_pedido.php",
				type: "post",
				data: { finalizar: true, pedido: pedido },			
			success: function(data) {
					if(data == "ok"){
						console.log("Pedido aprovado com sucesso.");						
					}else{
						console.log(data);
					}              
				}
			});
		}*/




			$(function () {
				$(".produto").click(function(){
					var peca = $(this).data('peca'); 
					$("#pecas").hide();
					$("#produto_pedido").html('');
					$("#produto_pedido").show();
					$("#produto_pedido").load("produto_peca.php?peca="+peca);
					$("#sb-container").hide();

				});

				$(".pedido").click(function(){
					var peca = $(this).data('peca'); 
					$("#pecas").hide();
					$("#produto_pedido").html('');
					$("#produto_pedido").show();
					$("#loading_obs_pedido").show();
					$("#produto_pedido").load("pedido_peca.php?peca="+peca, function(){
						$("#loading_obs_pedido").hide();
					});
					$("#sb-container").hide();
				});

				$(document).on('click', '.voltar', function(){
					$("#produto_pedido").html('');
					$("#produto_pedido").hide();
					$("#pecas").show();
				})
				
				$("#cancelar").click(function(){
					pedido = $("#pedido").val();

					var r = confirm("Deseja realmente cancelar o pedido?");
					if (r == true) {
							$.ajax({
								url: "pecas_pedido.php",
								type: "post",
								data: { cancelar: true, pedido: pedido},
							
							success: function(data) {
									if(data == "ok"){
										alert("Pedido cancelado com sucesso.");
										window.parent.location.reload();
										window.parent.Shadowbox.close();
									}else{
										alert("Falha ao cancelar pedido.");
									}              
								}
							});
					}            
				});
				$(".alterar").click(function(){					
					var posicao = $(this).data('linha');
					$(".qtde_"+posicao).attr("readonly", false);
					$(this).hide();
					$(".alterar_gravar_"+posicao).show();
				});

				$(".alterar_item").click(function(){
						var pedido        = $("#pedido").val();
						var pedido_item   = $(this).attr("pedido_item");
						var posicao       = $(this).attr("posicao");
						var qtde          = parseInt($(".qtde_"+posicao).val());
						var qtde_anterior = parseInt($(".qtde_anterior_"+posicao).val());
						var pedido_dewalt = $("#pedido_dewalt").val();

						$.ajax({
							url: "pecas_pedido.php",
							type: "post",
							data: { gravar_pedido_item: true, pedido_item: pedido_item, qtde: qtde, qtde_anterior: qtde_anterior, pedido: pedido, pedido_dewalt: pedido_dewalt},      
							success: function(data) {
								if(data == "ok"){
									alert("Pedido alterado com sucesso.");
									$(".close").click();
								}else{
									alert(data);
								}              
							}
						});
				});

				$(".grava_observacao").click(function(){
					var pedido_item = $(this).attr("pedido_item");
					var posicao     = $(this).attr("posicao");

					$("#grava_obs").attr("pedidoitem",pedido_item);
					$("#grava_obs").attr("posicao",posicao);
					$("#observacao").val("");
				});

				$(".grava_obs_pedido").click(function(){
					
					var observacao = "";
					var auditoria = "";
					var justificativa = "";
					var error = false;

					var item_pedido = $(this).attr("pedidoitem");
					var posicao     = $(this).attr("posicao");					

					observacao = $("#observacao_alteracao_"+posicao).val();
					auditoria = $("#auditoria_alteracao_"+posicao).val();
					justificativa = $("#justificativa_alteracao_"+posicao).val();

					$(".errormsg_alteracao_"+posicao).text("");
					if(observacao.length == 0){
						$(".errormsg_alteracao_"+posicao).text("Informe a mensagem");
						error = true;
					}
					$(".errorauditoria_alteracao_"+posicao).text("");
					if(auditoria.length == 0){
						$(".errorauditoria_alteracao_"+posicao).text("Informe a auditoria");
						error = true;
					}
					$(".errorjust_alteracao_"+posicao).text("");
					if(justificativa.length == 0){
						$(".errorjust_alteracao_"+posicao).text("Informe a justificatica");
						error = true;
					}


		
					if(error == false){
						gravar_item(item_pedido,posicao, observacao, auditoria, justificativa);

						//$("#grava_obs_cancelar").hide();
						//$("#grava_obs_pedido").hide();
						//$("#loading_obs").show();
					}									
				});

				$("#grava_obs").click(function(){
					var pedido      = $("#pedido").val();
					var item_pedido = $(this).attr("pedidoitem");
					var obs         = $("#observacao").val();

					$.ajax({
							url: "pecas_pedido.php",
							type: "post",
							data: { grava_obs_peca: true, pedido: pedido,item_pedido: item_pedido,obs: obs},
						
						success: function(data) {
								alert(data);
								$(".close").click();           
							}
						});
				});

				$("#aprovar_total").click(function(){
					$(".linha_botao_aprova_total").hide();
					$(".linha_justificativa_aprova_total").show();
				});

				
			});
		</script>
		<style type="text/css">
			.errorauditoria, .errormsg, .errorauditoria, .errorjust{
				color:red;
			}

		</style>
	</head>
	<body style="background:white;">
		<?php 
		if (!isset($_REQUEST['pedido_dewalt'])) {
		?>
			<!-- Modal -->
			<!-- <div id="myModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
					<h3 id="myModalLabel">Informe uma Observação</h3>
				</div>
				<div class="modal-body">
					
					<span class="errorauditoria"></span>
					<label>Auditoria</label>
					<div>
						<p>
							<select name='auditoria' id="auditoria">
								<option value="">Selecione uma Auditoria</option>
								<option value="pontual">Pontual</option>
								<option value="rotineira">Rotineira</option>
							</select>
						</p>
					</div>
					<span class="errormsg"></span>
					<label>Mensagem</label>
					<div class="tac">
						<p>
							<textarea name="observacao" id="observacao" style="width: 100%;">
							</textarea>
						</p>
					</div>
				</div>
				<div class="modal-footer" style='text-align: right;'>
					<div id="loading_obs" style="display: none; text-align: right;">
			            <img width="40" height="40" src="imagens/loading_img.gif" />
			            <p>Aguarde...</p>
			        </div>
					<button class="btn" data-dismiss="modal" id="grava_obs_cancelar" aria-hidden="true">Cancelar</button>
					<button type='button' pedidoitem='' posicao='' class='btn btn-primary' id="grava_obs_pedido">Gravar</button>
				</div>
			</div> -->    
		<?php 
		} else {
		?>
					<!-- Modal -->
			<div id="myModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
					<h3 id="myModalLabel">Informe uma Observação</h3>
				</div>
				<div class="modal-body tac">
					<p>
						<textarea name="observacao" id="observacao" style="width: 450px;">
						</textarea>
					</p>
				</div>
				<div class="modal-footer">
					<button class="btn" data-dismiss="modal" aria-hidden="true">Cancelar</button>
					<button type='button' pedidoitem='' posicao='' class='btn btn-primary' id="grava_obs">Gravar</button>
				</div>
			</div>  
		<?php 
		}
		?>
		<div id="container_lupa" style="overflow-y:auto;">
			<div id="topo">
				<img class="espaco" src="imagens/logo_new_telecontrol.png">
				<img class="lupa_img pull-right" src="imagens/lupa_new.png">
			</div>
			
			<form>
					<input type="hidden" name="pedido" id="pedido" value="<?=$pedido?>">
					<input type="hidden" name="pedido_dewalt" id="pedido_dewalt" value="<?= $_REQUEST['pedido_dewalt'] ?>">
			</form>
			<br /><hr />  
			<div id="loading_obs_pedido" style="display: none; text-align: center;">
		            <img width="40" height="40" src="imagens/loading_img.gif" />
		            <p>Aguarde...</p>
		        </div>
			<div id="produto_pedido">
				
			</div>
			<div id="pecas">  
			<?php          
					$sql = "SELECT tbl_peca.referencia,tbl_peca.descricao, tbl_pedido_item.qtde, tbl_pedido_item.pedido_item,  tbl_peca.informacoes, tbl_peca.ativo, tbl_peca.parametros_adicionais,tbl_pedido.pedido, tbl_peca.estoque, tbl_peca.peca, tbl_pedido_item.obs , tbl_pedido.seu_pedido, tbl_pedido_item.valores_adicionais,  
					(select status from tbl_pedido_status where tbl_pedido_status.pedido = tbl_pedido.pedido order by pedido_status desc limit 1) as status_pedido
									FROM tbl_pedido_item 
									INNER join tbl_pedido ON tbl_pedido.pedido = tbl_pedido_item.pedido
									INNER JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca
									WHERE tbl_pedido_item.pedido = $pedido
									AND tbl_pedido.fabrica = $login_fabrica";
					$res = pg_query($con, $sql);

					$rows = pg_num_rows($res);
					if ($rows > 0) {
						if (!isset($_REQUEST['pedido_dewalt'])) {
						?>
							<table align='left'>
									<tr>
											<td width='25px' bgcolor='#FFC0D0'></td><td>&nbsp; Quantidade acima da demanda</td>
											<td width='25px' bgcolor='#A9F4AD'></td><td>&nbsp; Peças aprovadas</td>
											<td width='25px' bgcolor='#81DAF5'></td><td>&nbsp; Peças Alteradas</td>
											<td width='25px' bgcolor='#FE9A2E'></td><td>&nbsp; Peças Excluida</td>


											
									</tr>
							</table>
							<br />
						<?php
						}
						?>
					<br />
						<div style='padding-bottom: 50px;'>
						<div class="alert_ajax alert alert-error" style="display: none;"> <h4 id="h4_texto"></h4> </div>
						<table  class="table table-striped table-bordered table-lupa" >
							<thead>
								<tr class='titulo_coluna'>
									<th>Peça</th>
									<th>Qtde Solicitada</th>
										<?php 
									if (!isset($_REQUEST['pedido_dewalt'])) {
										?>  
										<th>Qtde Demanda</th>
										<?php
									}
									?>

									<th>Status Peça</th>
									<th>Disponível</th>
									<th>Produtos</th>
									<th>Pedidos</th>

									<?php
										$colspan = 3;
									?>
									<th colspan='<?= $colspan ?>'>Ações</th>                  
								</tr>
							</thead>
							<tbody>
								<?php

								$arquivo_nome = "relatorio_callcenter_pendente_$login_fabrica.$login_admin".date('YmdHis').".csv";
								$path         = "xls/";
								$arquivo_completo     = $path.$arquivo_nome;

								$file = fopen($arquivo_completo, 'w');

								$thead = "Pedido;Peça;Qtde Solicitada;Qtde Demanda;Status Peça;Disponível; \r\n ";

								fwrite($file, $thead); 

								for ($i = 0 ; $i < $rows; $i++) {
									$referencia       = pg_fetch_result($res, $i, "referencia");
									$descricao       = pg_fetch_result($res, $i, "descricao");
									$pedido_item      = pg_fetch_result($res, $i, "pedido_item");
									$qtde             = pg_fetch_result($res, $i, "qtde");
									$obs              = (mb_check_encoding(pg_fetch_result($res, $i, "obs"), "UTF-8")) ? utf8_decode(trim(pg_fetch_result($res, $i, "obs"))) : trim(pg_fetch_result($res, $i, "obs"));
									$obs 			  = str_replace(array("<b>", "</b>", "<br>"), "", $obs);
									$valores_adicionais = str_replace("\\\\u", "\\u", pg_fetch_result($res, $i, 'valores_adicionais'));
									$valores_adicionais = json_decode($valores_adicionais, true);
							
									$aprovado = $valores_adicionais['aprovado'];
									$demanda = $valores_adicionais['demanda'];
									$alterada = $valores_adicionais['alterada'];
									$excluida = $valores_adicionais['excluido'];

									$outros_obs = '';
									if (isset($valores_adicionais['outros_obs']) && $valores_adicionais["outros_obs"] != "null") {
										$outros_obs = (mb_check_encoding($valores_adicionais['outros_obs'], "UTF-8")) ? utf8_decode($valores_adicionais['outros_obs']) : $valores_adicionais['outros_obs'] ;
									}
																			
									$parametros_adicionais = json_decode(pg_fetch_result($res, $i, "parametros_adicionais"), true);
									$status_pedido    = pg_fetch_result($res, $i, "status_pedido");
									$informacoes 	= pg_fetch_result($res, $i, 'informacoes');
									$ativo 			= (pg_fetch_result($res, $i, 'ativo') == "t")? "Ativo" : "Inativo";
									$peca 			= pg_fetch_result($res, $i, 'peca');
									$seu_pedido 	= pg_fetch_result($res, $i, 'seu_pedido');

									if(strlen(trim($informacoes))==0){
										$status_peca = $ativo;
									}else{
										$status_peca = $informacoes; 
									}
									
									$qtde_demanda = $valores_adicionais['qtde_demanda'];

									if(empty($qtde_demanda)){
										$qtde_demanda = $parametros_adicionais['qtde_demanda'];	
									}

									$qtde_demanda = strlen($qtde_demanda) > 0 ? $qtde_demanda : "Não cadastrada" ;
									$qtde_demandax = str_replace(",",".",$qtde_demanda);

									$estoque      = $parametros_adicionais['estoque'];

									$tbody .= "$seu_pedido; $referencia - $descricao;$qtde;$qtde_demanda;$status_peca;$estoque;\n\r ";

									if($aprovado == true and $alterada != true and $excluida != true){
										$cor = "#A9F4AD";
									}elseif($alterada == true){
										$cor = "#81DAF5";
									}elseif($excluida == true){
										$cor = "#FE9A2E";
									}elseif((( ($qtde > $qtde_demandax && $qtde > 3) && (strlen(trim($qtde_demanda)) > 0)) ) && !isset($_REQUEST['pedido_dewalt'])){
										$cor = "#FFC0D0";
									}else{
										$cor = " ";
									}

									echo "<tr class='linha_$i' >";
									echo "<td style='background:$cor'>{$referencia} - {$descricao}";
									
									if (!empty($obs)) {
										echo "<br><b>Motivo:</b> $obs";
									}

									if (!empty($outros_obs)) {
										echo "<br><b>Observação:</b> $outros_obs";	
									}

									echo " </td>";
									echo "<td style='text-align:center; background:$cor'>";

									if($login_fabrica == 1){
										echo"<input type='text' name='qtde_$i' value='$qtde' class='qtde_$i' style='width:50px' readonly='true'>
											<input type='hidden' name='qtde_anterior_$i' value='$qtde' class='qtde_anterior_$i'>";
									}else{
										echo $qtde;
									}

									if (!isset($_REQUEST['pedido_dewalt'])) {
									 echo "<td style='text-align:center; background:$cor;'>{$qtde_demanda}</td>";
									}
									echo "</td>";
									echo "<td class='tac' style='text-align:center; background:$cor'>$status_peca</td>";
									echo "<td class='tac' style='text-align:center; background:$cor'>$estoque</td>";
									echo "<td style='text-align:center; background:$cor'><span style='cursor:pointer;' class='produto' data-peca='$peca'>Ver Produtos</span></td>";
									echo "<td style='text-align:center; background:$cor'><span style='cursor:pointer;' class='pedido' data-peca='$peca'>Ver Pedidos</span></td>";

									if($login_fabrica == 1){
										echo "<td style='text-align:center; background:$cor; ' class='td_alterar_$i'>";
										//if($status_pedido == 33){

											if(($aprovado != true AND $qtde >= 3 and $qtde > $qtde_demandax ) && !isset($_REQUEST['pedido_dewalt']) ){
												echo"<button type='button' class='btn btn-info alterar' data-linha='$i' >Alterar Quantidade</button>";

												echo"<button style='display:none' role='button' data-toggle='modal' type='button' class='btn btn-primary alterar_gravar_$i' data-linha='$i' onclick='alterar_item($pedido_item,$i)' >Gravar</button>";								
											} else if (isset($_REQUEST['pedido_dewalt'])) {
												echo"<button class='btn btn-info alterar_item' pedido_item='$pedido_item' posicao='$i'>Alterar</button>";
											}
										//}
										echo "</td>";
									}

									echo "<td class='celula_excluir_$i' style='text-align:center; background:$cor;'>";
									if($qtde >= 3 and $qtde > $qtde_demandax and $aprovado != true ) {
										echo "<button type='button' onclick='excluir_item($pedido_item, $i)' class='btn btn-danger excluir_$i'>Excluir</button>";
									}
									echo "</td>";
									echo "<td class='celula_aprovar_$i' style='text-align:center; background:$cor;'>";
									if(!isset($_REQUEST['pedido_dewalt']) and $aprovado != true AND $qtde >= 3 AND $qtde > $qtde_demandax ){	
										echo "<button type='button' onclick='mostrar_aprovar($i)' class='btn btn-success'>Aprovar</button>";
									}
									if (isset($_REQUEST['pedido_dewalt'])) { ?>						
										<button href='#myModal' pedido_item='<?= $pedido_item ?>' posicao='<?= $i ?>' role='button' data-toggle='modal' type='button' class="btn btn-warning grava_observacao">
											Observação
										</button>					
									<?php
									}
									echo "</td>";
									echo "</tr>";
									echo "<tr class='linha_oculta_alteracao_$i' style='display:none'>";
									echo "<td colspan='7'></td>
											<td colspan='3'>
												<div class='body'>				
													
													<div class='tac'>
														<span class='errorauditoria_alteracao_$i' style='color:red;'></span>
														<label>Auditoria</label>
														<select name='auditoria' id='auditoria_alteracao_$i'>
															<option value=''>Selecione uma Auditoria</option>
															<option value='pontual'>Pontual</option>
															<option value='rotineira'>Rotineira</option>
														</select>
													</div>

													<div style='text-align:center'>
														<span class='errorjust_alteracao_$i' style='color:red;'></span><br>
															<label>Justificativa: <label>
															<input type='text' name='justificativa_alteracao_$i' id='justificativa_alteracao_$i' value='' style='width:300px;'>
													</div>
													<br>
													<div class='tac'>
														<span class='errormsg_alteracao_$i' style='color:red;'></span>
														<label>Motivo:(Mensagem para o posto)</label>
														<textarea name='observacao' id='observacao_alteracao_$i' style='width: 100%;'>
																</textarea>
													</div>
													<div class='tac'>
														<button type='button' pedidoitem='$pedido_item' posicao='$i' class='btn btn-primary grava_obs_pedido gravar_alteracao_$i'>Gravar</button>
														<img width='40' height='40' src='imagens/loading_img.gif' style='display:none' class='loading_alteracao_$i'/>
													</div>
												</div>

											</td>";
									echo "</tr>";
									echo "<tr class='linha_oculta_aprovacao_$i' style='display:none'>
											<td colspan='7'></td>
											<td colspan='3'>
												<div style='text-align:center'>
													<span class='errorauditoria_aprova_$i'  style='color:red;'></span><br>
													Auditoria:<br> <select name='auditoria_aprova_$i' id='auditoria_aprova_$i'>
														<option value=''>Selecione um auditoria</option>
														<option value='pontual'>Pontual</option>
														<option value='rotineira'>Rotineira</option>
													</select> 
												</div>
												<div style='text-align:center'>
													<span class='errorjust_aprova_$i'  style='color:red;'></span><br>
														Justificativa: <input type='text' name='justificativa_aprova_$i' id='justificativa_aprova_$i' value='' style='width:300px;'>
												</div>								
												<div style='text-align: center'>									
													<button type='button' onclick='aprovar_item($pedido_item, $i)' class='btn btn-primary gravar_aprova_$i'>Gravar</button></div>
													<img width='40' height='40' src='imagens/loading_img.gif' style='display:none' class='loading_aprova_$i'/>
											</td>
										</tr>";
									echo "<tr class='linha_oculta_$i' style='display:none'><td colspan='7'></td><td colspan='3'>
									<div style='text-align:center'>
										<span class='errorauditoria_excluir_$i' style='color:red;'></span><br>
											Auditoria:<br> <select name='auditoria_excluir_$i' id='auditoria_excluir_$i'>
											<option value=''>Selecione um auditoria</option>
											<option value='pontual'>Pontual</option>
											<option value='rotineira'>Rotineira</option>
										</select> 
									</div>

									<div style='text-align:center'>
										<span class='errorjust_excluir_$i' style='color:red;' ></span><br>
											Justificativa:<br> <input type='text' name='justificativa_excluir_$i' id='justificativa_excluir_$i' value='' style='width:300px;'>
									</div>
									<br>
									<div class='tac'>
										<label>Motivo:(Mensagem para o posto)</label><span class='erro_linha_$i' style='color:red;'></span>
										<textarea id='observacao_excluir_".$i."' name='observacao' style='width:100%' ></textarea> 
									</div>
									<div class='tac'>
										<br> <button type='button' class='btn btn-primary gravar_excluir_$i' onclick='confirma_gravacao(".$pedido_item.", ".$i.")' >Gravar</button> 
											<img width='40' height='40' src='imagens/loading_img.gif' style='display:none' class='loading_excluir_$i'/>
									</div>

									</td></tr>";
												}
						fwrite($file, $tbody);
						fclose($file);
								?>
							</tbody>
						</table>
						<br>
						<?php if(in_array($status_pedido,[18,33]) && !isset($_REQUEST['pedido_dewalt'])) { ?>
							<table width="100%">
									<!-- <tr>
										<td style="color:#ff0000; text-align:center"> *Para que o Pedido seja aprovado, é preciso clicar no botão "<b>Aprovar Total</b>".</td>
									</tr>
									<tr class="linha_botao_aprova_total">
										<td style="text-align: center"><button type='button' id="cancelar" class='btn btn-danger'>
											<?php echo ($login_fabrica == 1)? "Excluir" : "Cancelar"; ?>
										</button>
										
										<button type='button' id="aprovar_total" class='btn btn-success'>Aprovar Total</button>
										</td>
									</tr>
									<tr class="linha_justificativa_aprova_total" style="display: none;">
										<td style="text-align: center;">
											<span class="errorauditoria"></span><br>
											Auditoria: <select name="auditoria_aprova_total" id="auditoria_aprova_total">
												<option value="">Selecione um auditoria</option>
												<option value="pontual">Pontual</option>
												<option value="rotineira">Rotineira</option>
											</select> 
											<br> 
											
											<span class="errorjust"></span><br>
											Justificativa: <input type="text" name="just_aprovacao_total" id="just_aprovacao_total" value="" style="width:400px;">											
										</td>
									</tr>
									<tr class="linha_justificativa_aprova_total" style="display: none;">
										<td  style="text-align: center" "><button type='button' id="finalizar" class='btn btn-success'>Aprovar Total</button></td>
									</tr> -->
									<tr>
										<td style="text-align: center">
											<br>
											<a href="xls/<?=$arquivo_nome?>" target='_blank'>
												<div class='btn_excel'>
													<span>
														<img src='imagens/excel.png' />
													</span>
													<span class='txt'>Download em Excel</span>
												</div>

												<br>
										    	</a><br />
										</td>
								    </tr>
								   <tr id="tr_grava_todos"> 
								   		<td style="text-align: center">
								   			<button type='button' onclick="mostrar_aprovar_todos()" class='btn btn-success'>Aprovar Todos</button>
								   		</td>
								   </tr>

								   	<tr class="grava_tdos" style="display: none;">
										<td style="text-align: center">
											<div style='text-align:center'>
												<span class='errorauditoria_aprova_todos_aud'  style='color:red; display: none;'>Informe a Auditoria</span>
												<br><b>Auditoria</b><br><br> 
												<select name='auditoria_aprova_todos' id='auditoria_aprova_todos'>
													<option value=''>Selecione um auditoria</option>
													<option value='pontual'>Pontual</option>
													<option value='rotineira'>Rotineira</option>
												</select> 
											</div>
											<div style='text-align:center'>
												<span class='errorjust_aprova_todos_just'  style='color:red; display: none;'>Informe a Justificativa</span><br><b>Justificativa</b><br><br>
												 <input type='text' name='justificativa_aprova_todos' id='justificativa_aprova_todos' value='' style='width:300px;'>
											</div>	
											<div style='text-align:center'>
												<br />							
												<button type='button' onclick='aprovar_todos_item(<?=$pedido?>)' class='btn btn-primary btn_grava_todos'>Gravar Todos</button>
												<img width='40' height='40' src='imagens/loading_img.gif' style='display:none' class='loading_aprova_todos'/>
										</td>											
											</div>
									</tr>
							</table>
						<? } ?>

						</div>
					<?php
					} else {
						echo '
						<div class="alert alert_shadobox">
							<h4>Nenhum resultado encontrado</h4>
						</div>';
					}

			 
		?>
		</div>
	</div>
	

	</body>
</html>
