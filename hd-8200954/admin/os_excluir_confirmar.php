<?

include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="financeiro";
include "autentica_admin.php";
include 'funcoes.php';
include_once '../class/communicator.class.php';
if (strlen($_GET['os']) > 0)   $os = trim ($_GET['os']);
if (strlen($_POST['os']) > 0)  $os = trim ($_POST['os']);

if (strlen($_GET['btn_acao']) > 0)   $btn_acao = trim ($_GET['btn_acao']);
if (strlen($_POST['btn_acao']) > 0)  $btn_acao = trim ($_POST['btn_acao']);

if (strlen($_GET['select_acao']) > 0)   $select_acao = trim ($_GET['select_acao']);
if (strlen($_POST['select_acao']) > 0)  $select_acao = trim ($_POST['select_acao']);

if (count($_GET['os_lote']) > 0)   $os_lote = $_GET['os_lote'];
if (count($_POST['os_lote']) > 0)  $os_lote = $_POST['os_lote'];

if (strlen($_GET['xjustificativa']) > 0)   $xjustificativa = trim ($_GET['xjustificativa']);
if (strlen($_POST['xjustificativa']) > 0)  $xjustificativa = trim ($_POST['xjustificativa']);

$target = filter_input(INPUT_GET,'target');

if ($login_fabrica == 1) {
	$nova_os = $_GET['nova_os'];
}

if (!empty($target)) {
    $tipo_atendimento = filter_input(INPUT_GET,'tipo');
} else {
    $tipo_atendimento = "NULL";
}

if($_GET['ajax_fat']){
	$os_item = $_GET['os_item'];

	$sql = "SELECT nota_fiscal FROM tbl_os_item_nf WHERE os_item = $os_item";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) == 0){
		echo "no";
	}
	exit;
}

if ($select_acao == "excluir_os_lote") {
	if (count($os_lote) == 0){
		header('Location: os_excluir.php');
		exit;
	}
	$btn_acao = "excluir";
} else {
	if (strlen($os)==0){
		header('Location: os_excluir.php');
		exit;
	}
}

if ($btn_acao == "excluir") {
	if (strlen ($os) > 0 || $select_acao == "excluir_os_lote") {
    $target             = filter_input(INPUT_POST,'target');
    $tipo_atendimento  = filter_input(INPUT_POST,'tipo');
		$justificativa = trim($_POST ['justificativa']);
		$qtde_itens = trim($_POST ['qtde_itens']);

		if ($select_acao == "excluir_os_lote") {
			$justificativa = $xjustificativa;
		}
		if($login_fabrica == 1){
			$sql = "SELECT posto FROM tbl_os WHERE os = $os";
			$res = pg_query ($con,$sql);
			if(pg_num_rows($res) > 0){
				$posto = pg_fetch_result($res, 0, 'posto');
			}

			$sql = "SELECT tbl_os.os
					FROM tbl_os
					WHERE tbl_os.fabrica = $login_fabrica
					AND   tbl_os.posto   = $posto
					AND   (tbl_os.data_abertura + INTERVAL '60 days') <= current_date
					AND   tbl_os.data_fechamento IS NULL
					AND  tbl_os.excluida is FALSE LIMIT 1";

			$res = pg_query ($con,$sql);
			if(pg_num_rows($res) > 0){
				$tem_os_aberta = pg_fetch_result($res, 0, 'os');
			}
		}

		if (strlen($justificativa)>0){
			$justificativa = "'".$justificativa."'";
		}else{
			$msg_erro = " Informe a Justificativa da exclusão";
		}

		if(strlen($justificativa)>0  AND strlen($msg_erro) == 0){

			if ($select_acao == "excluir_os_lote" && count($os_lote) > 0) {

				foreach ($os_lote as $os) {

					$ja_excluiu = checaOSExcluida($con, $os, $login_fabrica);

					if ($ja_excluiu != "sim") {
						$retorno = excluiOS($con, $os, $justificativa, $login_admin, $login_fabrica, true);
					}
					if ($retorno == "ok") {


						if (!empty($tem_os_aberta)) {
							$dir = __DIR__."/../rotinas/blackedecker/bloqueia-posto.php";
							echo `/usr/bin/php $dir $posto`;
						}

						//header("Location: $PHP_SELF?os=$os");
						//exit;

					} else {
						$problemas = true;
						$msg_erro .= implode("<br />", $retorno["msg"]);
					}
				}
			} else {
				$res = pg_query($con,"BEGIN TRANSACTION");

				$ja_excluiu = checaOSExcluida($con, $os);


				if ($ja_excluiu != "sim"){

					$retorno = excluiOS($con, $os, $justificativa, $login_admin, $login_fabrica, false);

				}
				if ($retorno == "ok") {
					$res = pg_query($con,"COMMIT TRANSACTION");
					if(!empty($tem_os_aberta)){
						$dir = __DIR__."/../rotinas/blackedecker/bloqueia-posto.php";
						echo `/usr/bin/php $dir $posto`;
					}

					$nova_os = $_POST['nova_os'];
					/*HD-4132808*/
					if ($login_fabrica == 1 && !empty($nova_os)) {
						header("Location: os_press.php?os=$nova_os");
					} else {
						header("Location: $PHP_SELF?os=$os");
					}
					exit;
				} else {
					$msg_erro .= implode("<br />", $retorno["msg"]);
					$res = pg_query($con,"ROLLBACK TRANSACTION");
				}
			}
		}
	}
}



function checaOSExcluida($con, $os, $login_fabrica) {

	$ja_excluiu = "não";

	$sql = "SELECT status_os
			  FROM tbl_os_status
			 WHERE status_os IN (110,111,112)
			   AND os = $os
		  ORDER BY data DESC 
		  	 LIMIT 1";
	$res_os = pg_query($con, $sql);

	if (pg_num_rows($res_os) > 0) {

		$status_da_os = trim(pg_fetch_result($res_os, 0, status_os));


		if ($status_da_os == 110) { 
			$ja_excluiu = "sim";
		}

	}

	return $ja_excluiu;
}

function excluiOS($con, $os, $justificativa, $login_admin, $login_fabrica, $oslote = false) {
	global $qtde_itens;

	$sql = "INSERT INTO tbl_os_status (
										os,
										status_os,
										data,
										observacao,
										admin
									) VALUES (
										$os,
										110,
										current_timestamp,
										$justificativa,
										$login_admin
									)";
	$res = pg_query($con, $sql);
	if (pg_last_error($con)) {
		$msg_erro["msg"][] = "Erro ao inserir status os. " . pg_last_error($con);
	}

	//hd 53482 - reabre a OS caso esteja fechada
	if (count($msg_erro["msg"]) == 0) {

		$sql = "SELECT os 
				  FROM tbl_os 
				 WHERE os = $os
				   AND finalizada IS NOT NULL;";
		$res = pg_query($con, $sql);

		if (pg_last_error($con)) {
			$msg_erro["msg"][] = "Erro ao buscar os. " . pg_last_error($con);
		}

		if (pg_num_rows($res) > 0) {

			$sql = "UPDATE tbl_os SET 
				           data_fechamento = null, 
				           finalizada = null
			          FROM tbl_os_extra
			         WHERE tbl_os.os = tbl_os_extra.os
			           AND tbl_os_extra.extrato IS NULL
			           AND tbl_os.os = $os;";

			$res = pg_query($con, $sql);

			if (pg_last_error($con)) {
				$msg_erro["msg"][] = "Erro ao atualizar os. " . pg_last_error($con);
			}

		}

		/*
		1) sem peça e não é de troca
		2) tem peça e servico_realizado = 90
		3) é troca, mas ainda não tem pedido gerado 
		*/
		
		$sql = "SELECT os,count(tbl_os_item.*) as qtde_itens
				INTO TEMP tmp_os_item_count
				FROM tbl_os 
				LEFT JOIN tbl_os_produto using (os) 
				LEFT JOIN tbl_os_item using (os_produto)
				WHERE tbl_os.os=$os
				GROUP by os;


				SELECT tbl_os.os 
				FROM tbl_os 
				LEFT JOIN tbl_os_troca on tbl_os.os = tbl_os_troca.os
				LEFT JOIN tbl_os_produto on tbl_os.os = tbl_os_produto.os
				LEFT JOIN tbl_os_item on tbl_os_produto.os_produto = tbl_os_item.os_produto and tbl_os_item.fabrica_i = $login_fabrica
				LEFT JOIN tmp_os_item_count on tbl_os.os  = tmp_os_item_count.os

				WHERE tbl_os.fabrica = $login_fabrica 
				
				AND tbl_os.os = $os 
				AND 
				(

					(tbl_os_item.os_item is null and tbl_os_troca.os_troca is null) 
					
					OR (tbl_os_item.os_item is not null and tmp_os_item_count.qtde_itens = 
						( 	
							SELECT count(tbl_os_item.*)
							FROM tbl_os 
							LEFT JOIN tbl_os_produto on tbl_os.os = tbl_os_produto.os
							LEFT JOIN tbl_os_item on tbl_os_produto.os_produto = tbl_os_item.os_produto and tbl_os_item.fabrica_i = $login_fabrica
							WHERE tbl_os.os = $os 
							AND tbl_os.fabrica                = $login_fabrica
							AND tbl_os_item.servico_realizado = 90
						)
					)  
					
					OR (tbl_os_troca.os_troca is not null and tbl_os_troca.pedido is null) 
				)
			";
		$res = pg_query($con,$sql);

		if (strlen($os) > 0) {
			$sql  = "SELECT fn_calcula_os_item_black($os,$login_fabrica);";
			$resS = pg_query($con, $sql);

			$return = pg_fetch_result($resS, 0, 0);

			if ($return != 't') {
				$msg_erro["msg"][] = "Erro ao calcular a OS: " . pg_last_error($con) . " <br>";
			}
		} else {
			$msg_erro["msg"][] = "Erro ao identificar a OS <br>";
		}



		if (pg_num_rows($res) > 0) {

			$sql = "INSERT INTO tbl_os_status
												(
													os,
													status_os,
													data,
													observacao,
													automatico
												) VALUES (
													$os,
													111,
													current_timestamp,
													$justificativa,
													true
												)";
			$res = @pg_query($con, $sql);
			if (pg_last_error($con)) {
				$msg_erro["msg"][] = "Erro ao inserir status os. " . pg_last_error($con);
			}

			$sql = "INSERT INTO tbl_os_status
					                            (
						                           os,
						                           status_os,
						                           data,
						                           observacao,
						                           automatico
					                            ) VALUES (
					                           	   $os,
					                           	   15,
					                           	   current_timestamp,
					                           	   $justificativa,
					                           	   true
					                           	)";
			$res = @pg_query($con, $sql);
			if (pg_last_error($con)) {
				$msg_erro["msg"][] = "Erro ao inserir status os. " . pg_last_error($con);
			}

			$sql = "SELECT fn_os_excluida($os,$login_fabrica,null);";
			$res = @pg_query($con,$sql);

			if (pg_last_error($con)) {
				$msg_erro["msg"][] = "Erro fn_os_excluida. " . pg_last_error($con);
			}
		}

		if (count($msg_erro["msg"]) == 0 && !$oslote) {
			for($i = 0; $i < $qtde_itens; $i++) {
				$opcao_os 	= $_POST['opcao_os_'.$i];
				$os_item    = $_POST['os_item_'.$i];
				$debito_os 	= ($opcao_os == "debito_peca") ? "t" : "f";
				$coleta_os 	= ($opcao_os == "coleta_peca") ? "t" : "f";
				$adicionais = array("debito_peca" => $debito_os,"coleta_peca" => $coleta_os);					
				$adicionais = str_replace("\\", "", $adicionais);
				$adicional_os = json_encode($adicionais);

				$sql = "SELECT tbl_os_item.parametros_adicionais
					      FROM tbl_os_item 
					     WHERE os_item = $os_item";
				$res = pg_query($con,$sql);
				if (pg_last_error($con)) {
					$msg_erro["msg"][] = "Erro os buscar os item. " . pg_last_error($con);
				}

				if (pg_num_rows($res) > 0) {
					$parametros_adicionais = pg_fetch_result($res, 0, 'parametros_adicionais');

					$valor = number_format($custo_peca * $qtde * -1,2);
					if (!empty($parametros_adicionais)) {
						$adicionais = json_decode($parametros_adicionais,true);
						$adicionais['debito_peca'] = $debito_os;
						$adicionais['coleta_peca'] = $coleta_os;
						$adicional_os = json_encode($adicionais);								
					}

					$sql = "UPDATE tbl_os_item SET 
					                               parametros_adicionais = '$adicional_os'
						                      WHERE os_item = $os_item";
					$res2 = pg_query($con,$sql);
					if (pg_last_error($con)) {
						$msg_erro["msg"][] = "Erro ao atualizar os item. " . pg_last_error($con);
					}

				}
			}

		}

		if (count($msg_erro["msg"]) > 0) {
			return $msg_erro;
		} else {
			return "ok";
		}


	} else {
		return $msg_erro;
	}
}


if (strlen($os) > 0) {

	$sql = "SELECT  tbl_os.*                                                 ,
			to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura2   ,
			tbl_produto.produto                                              ,
			tbl_produto.referencia                                           ,
			tbl_produto.descricao                                            ,
			tbl_produto.linha                                                ,
			tbl_linha.nome AS linha_nome                                     ,
			tbl_posto_fabrica.codigo_posto                                   ,
			tbl_posto.nome  AS nome_posto                                    ,
			tbl_defeito_constatado.descricao AS defeito_constatado_descricao ,
			tbl_causa_defeito.descricao      AS causa_defeito_descricao      ,
			tbl_posto_fabrica.codigo_posto                                   ,
			tbl_posto_fabrica.reembolso_peca_estoque
		FROM    tbl_os
		JOIN    tbl_os_extra USING (os)
		JOIN    tbl_posto USING (posto)
		JOIN    tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
							  AND tbl_posto_fabrica.fabrica = $login_fabrica
		LEFT JOIN    tbl_produto USING (produto)
		LEFT JOIN    tbl_linha   ON tbl_produto.linha = tbl_linha.linha
		LEFT JOIN    tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
		LEFT JOIN    tbl_causa_defeito      ON tbl_causa_defeito.causa_defeito           = tbl_os.causa_defeito
		WHERE   tbl_os.os      = $os
		AND     tbl_os.fabrica = $login_fabrica ";
	$res = pg_exec ($con,$sql) ;
	if (pg_numrows($res) >0) {
		$defeito_constatado = pg_result ($res,0,defeito_constatado);
		$data_abertura      = pg_result ($res,0,data_abertura2);
		$nota_fiscal        = pg_result ($res,0,nota_fiscal);
		$causa_defeito      = pg_result ($res,0,causa_defeito);
		$linha              = pg_result ($res,0,linha);
		$linha_nome         = pg_result ($res,0,linha_nome);
		$consumidor_nome    = pg_result ($res,0,consumidor_nome);
		$consumidor_fone    = pg_result ($res,0,consumidor_fone);
		$sua_os             = pg_result ($res,0,sua_os);
		$produto_os         = pg_result ($res,0,produto);
		$produto_referencia = pg_result ($res,0,referencia);
		$produto_descricao  = pg_result ($res,0,descricao);
		$produto_serie      = pg_result ($res,0,serie);
		$obs                = pg_result ($res,0,obs);
		$codigo_posto       = pg_result ($res,0,codigo_posto);
		$nome_posto         = pg_result ($res,0,nome_posto);
		$reembolso_peca_estoque = pg_result ($res,0,reembolso_peca_estoque);

	}else{
		if ($login_fabrica == 1) {
			$sql = "SELECT tbl_os.sua_os,
					       tbl_os_status.observacao, 
					       tbl_posto_fabrica.codigo_posto,
					       tbl_posto_fabrica.contato_email
					FROM tbl_os_status
					JOIN tbl_os ON tbl_os_status.os = tbl_os.os 
					JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
					JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto
					WHERE tbl_os_status.status_os = 15
					AND tbl_os_status.os = $os
					AND tbl_posto_fabrica.fabrica = $login_fabrica
					ORDER BY tbl_os_status.data DESC
					LIMIT 1";
					
			$res_os = pg_exec($con,$sql);
			
			if (pg_numrows($res_os)>0){
				$observacao          = trim(pg_result($res_os,0,'observacao'));
				$posto_contato_email = pg_result($res_os,0,'contato_email');
				$codigo_posto		 = pg_result($res_os,0,'codigo_posto');
				$sua_os              = pg_result($res_os,0,'sua_os');
				$num_os_black        = $codigo_posto . $sua_os;
				$destinatario = $posto_contato_email;
				$assunto = "Stanley Black&Decker - OS $num_os_black - Excluida pelo Fabricante";
				$message  = "<p>Prezado Autorizado,</p>";
				$message  .= "<p>A Ordem de serviço <b>{$num_os_black}</b> foi excluida pelo fabricante.</p>";
				$message  .= "<p>Motivo: <b>$observacao</b>.</p>";
				$message  .= "<p>Em caso de dúvidas, gentileza entrar em contato com o suporte de sua região.</p>";
				$message .= "<p>Atenciosamente.</p>\n<p>&nbsp;</p>\n";
				$message .= "<p>Stanley Black&Decker.</p>\n<p>&nbsp;</p>\n";

				if(strlen($observacao) > 5) {
					$mailTc = new TcComm($externalId);
						$res = $mailTc->sendMail(
						$destinatario,
						$assunto,
						$message,
						$externalEmail
					);
				}
			}
		}
		
		$msg_erro = "OS excluída automaticamente!";
		$os_nao_econtrada = "OS excluída automaticamente!";
	}
}

$title = "Telecontrol - Assistência Técnica - Exclusão de Ordem de Serviço";
$layout_menu = 'financeiro';
include "cabecalho.php";
?>
<script src="js/jquery-1.8.3.min.js"></script>
<script>
	$(document).ready(function () {
		$("textarea[name=justificativa]").keyup(function () {
			if ($(this).val().length > 500)
			{
				var valor = $(this).val();
				var valor = valor.substr(0,500);

				$(this).val(valor);
			}
		});

		$("input[name^=opcao_os_]:radio").click(function(){
			if( $(this).is(":checked") ){
				var objeto = $(this);
				var os_item = $(this).parents("tr").find("input[name^=os_item_]").val();
				$.ajax({
					url : "os_excluir_confirmar.php",
					type : "get",
					data : {ajax_fat : "sim", os_item : os_item},
					complete : function(data){
						if(data.responseText == "no"){
							alert("Peça ainda não enviada, favor solicitar o cancelamento do item no pedido !");
							$(objeto).attr({"checked":false});
						}
					}
				});
			}
		});
	});

	function mostraDebito(xposicao, xqtde = 0, xcusto_peca = 0) {
		let vl = xqtde * xcusto_peca
		vl = vl.toFixed(2)
		$("#debito_"+xposicao).html(vl).css('text-align', 'center')
	}

	function ocultaDebito(xposicao) {
		$("#debito_"+xposicao).html('')	
	}

</script>
<style type='text/css'>
	.texto_avulso{
		font: 14px Arial; color: rgb(89, 109, 155);
		background-color: #d9e2ef;
		text-align: center;
		width:700px;
		margin: 0 auto;
		border-collapse: collapse;
		border:1px solid #596d9b;
	}

	.formulario{
		background-color:#D9E2EF;
		font:12px Arial;
		text-align:left;
	}

	.titulo_tabela{
		background-color:#596d9b;
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

	table.tabela tr td{
		font-family: verdana;
		font-size: 11px;
		border-collapse: collapse;
		border:1px solid #596d9b;
	}
</style>
<?
if (strlen ($msg_erro) > 0){
?>

<br>
<br>
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" class='msg_erro'>
<tr>
	<td height="27" valign="middle" align="center">
		<b><font face="Arial, Helvetica, sans-serif" color="#FFFFFF">
<?
	// retira palavra ERROR:
	if (strpos($msg_erro,"ERROR: ") !== false) {
		$erro = "Foi detectado o seguinte erro:<br>";
		$msg_erro = substr($msg_erro, 6);
	}

	// retira CONTEXT:
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}
	echo $erro . $msg_erro;
?>
		</font></b>
	</td>
</tr>


</table >
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" >
	<tr>
		<td>
			<button onclick='window.location="os_excluir.php"'>Voltar</button>
		</td>
	</tr>
</table>
<? } ?>

<? if (strlen($os_nao_econtrada)==0) { ?>

<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" class='formulario'>
<tr class='titulo_tabela' height='25' valign='middle'><td colspan='3'>Dados da OS</td></tr>
<tr>
	<td><img height="1" width="20" src="imagens/spacer.gif"></td>
	<td valign="top" align="center">
		<!-- ------------- Formulário ----------------- -->
		<input type="hidden" name="os" value="<?echo $os?>">
		<input type="hidden" name="sua_os" value="<? echo $codigo_posto.$sua_os?>">
		<input type="hidden" name="justificativa" value="<?echo $justificativa?>">
		<p>

		<table width="100%" border="0" cellspacing="2" cellpadding="0" class='formulario tabela'>

		<tr>
			<td nowrap>
				Posto
			</td>
			<td nowrap>
				Nome do Posto
			</td>
			<td nowrap>
				Recebe Peça em Garantia
			</td>
		</tr>
		<tr bgcolor='#FFFFFF'>
			<td><? echo $codigo_posto; ?></td>
			<td><? echo $nome_posto?></td>
			<td>
				<?
					if ($reembolso_peca_estoque=='t'){
						echo "SIM";
					}else{
						echo "NÃO";
					}
				?>
			</td>
		</tr>
		<tr>
			<td nowrap>
				OS
			</td>
			<td nowrap>
				Abertura
			</td>
			<td nowrap>
				<?if ($sistema_lingua=='ES') echo "Usuário"; else echo "Consumidor";?>
			</td>

		</tr>

		<tr bgcolor='#FFFFFF'>
			<td><? if ($login_fabrica == 1) echo $codigo_posto; echo $sua_os; ?></td>
			<td><? echo $data_abertura?></td>
			<td><? echo $consumidor_nome ?></td>
		</tr>


		<tr>
			<td nowrap>
				<?if ($sistema_lingua == 'ES') echo "Teléfono"; else echo "Telefone"?>
			</td>
			<td nowrap>
				<?if ($sistema_lingua == 'ES') echo "Factura comercial"; else echo "Nota Fiscal"?>
			</td>
		</tr>

		<tr bgcolor='#FFFFFF'>
			<td><? echo $consumidor_fone ?></td>
			<td><? echo $nota_fiscal; ?></td>
		</tr>
		</table>

		<table width="100%" border="0" cellspacing="2" cellpadding="0" class='formulario tabela'>
		<tr>
			<td nowrap>
				<?if ($sistema_lingua == 'ES') echo "Producto"; else echo "Produto"?>
			</td>
			<td nowrap>
				<?if ($sistema_lingua == 'ES') echo "N. serie"; else echo "N. Série"?>
			</td>
		</tr>

		<tr bgcolor='#FFFFFF'>
			<td><? echo $produto_referencia . " - " . $produto_descricao?></td>
			<td><? echo $produto_serie ?></td>
		</tr>
		</table>
		<?php
			$sql = "SELECT tbl_os_item.os_item,referencia,descricao,tbl_os_item.parametros_adicionais, tbl_os_item.qtde, tbl_os_item.custo_peca
					FROM tbl_os_item
					JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
					JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca AND tbl_peca.fabrica = $login_fabrica
					WHERE tbl_os_produto.os = $os";
			$res = pg_query($con,$sql);

			if(pg_num_rows($res) > 0){
				$qtde_itens = pg_num_rows($res);
		?>
				<input type='hidden' value='<?=$qtde_itens?>' name='qtde_itens'>
				<table width="500" border="0" cellspacing="2" cellpadding="0" class='formulario tabela'>
					<tr>
						<td colspan='4' class='titulo_coluna'>Itens da OS</td>
					</tr>
					<tr class='titulo_coluna'>
						<td>Referência</td>
						<td>Descrição</td>
						<td>Opção</td>
						<td>Débito</td>
					</tr>
					<?php
						for($i = 0; $i < pg_num_rows($res); $i++){
							$referencia_peca = pg_fetch_result($res, $i, 'referencia');
							$descricao_peca  = pg_fetch_result($res, $i, 'descricao');
							$os_item  		 = pg_fetch_result($res, $i, 'os_item');
							$qtde  		 	 = pg_fetch_result($res, $i, 'qtde');
							$custo_peca  	 = pg_fetch_result($res, $i, 'custo_peca');
							$parametros_adicionais = pg_fetch_result($res, $i, 'parametros_adicionais');

							if(!empty($msg_erro)){
								$opcao_os = $_POST['opcao_os_'.$i];
							}else{
								$parametros_adicionais = json_decode($parametros_adicionais,true);
								$opcao_os = ($parametros_adicionais['debito_peca'] == "t") ? "debito_peca" : "";
								$opcao_os = ($parametros_adicionais['coleta_peca'] == "t") ? "coleta_peca" : $opcao_os;
							}
					?>
							<tr>
								<td><?=$referencia_peca?></td>
								<td><?=$descricao_peca?></td>
								<td align="center" width='200'>
									<input type='hidden' name='os_item_<?=$i?>' value='<?=$os_item?>'>
									<input type='radio' name='opcao_os_<?=$i?>' value='debito_peca' onClick="mostraDebito(<?=$i?>,<?=$qtde?>,<?=$custo_peca?>)" <? if($opcao_os == "debito_peca") echo "checked"; ?>>Gerar Débito
									<input type='radio' name='opcao_os_<?=$i?>' value='coleta_peca' onClick="ocultaDebito(<?=$i?>)" <? if($opcao_os == "coleta_peca") echo "checked"; ?>>Coletar peça
								</td>
								<td align='right' id="debito_<?=$i?>" >
									<?php
										 echo ($opcao_os == "debito_peca") ? number_format($qtde * $custo_peca,2,',','.') : "&nbsp;";
									?>
								</td>
							</tr>
					<?php
						}
					?>
				</table>
		<?php
			}
		?>
	</td>
</tr>

<? $erro = ""; ?>

<tr>
	<td height="27" valign="middle" align="center" colspan="3" bgcolor='#FFFFFF'>
	<br>
<?
		$sql = "SELECT status_os
				FROM tbl_os_status
				WHERE status_os IN (110,111,112)
				AND os = $os
				ORDER BY data DESC
				LIMIT 1";
		$res_os = pg_exec($con,$sql);
		if (pg_numrows($res_os)>0){
			$status_da_os = trim(pg_result($res_os,0,status_os));
			if ($status_da_os == 110){
				$os_enviada_para_exclusao = "sim";
			}
		}

		#HD 32438
		$sql = "SELECT tbl_os_extra.extrato
				FROM   tbl_os_extra
				JOIN   tbl_os on tbl_os.os = tbl_os_extra.os
				WHERE  tbl_os.os      = $os
				AND    tbl_os.fabrica = $login_fabrica
				AND    tbl_os_extra.extrato IS NOT NULL ";
		$res_os = pg_exec($con,$sql);
		if (pg_numrows($res_os)>0){
			$erro = "1";
			$msg_erro .= "OS já está em extrato e não pode ser excluída.";
		}

		#HD 52463
		$sql = "SELECT tbl_os.excluida
				FROM   tbl_os
				WHERE  tbl_os.os      = $os
				AND    tbl_os.fabrica = $login_fabrica
				AND    tbl_os.excluida IS TRUE";
		$res_os = pg_exec($con,$sql);
		if (pg_numrows($res_os)>0){
			$erro = "1";
			$msg_erro .= "O.S EXCLUÍDA AUTOMATICAMENTE";
		}

?>
		<table width='700' class='texto_avulso'>
		<tr>
			<td align='center'><b>Exclusão de OS</b><br>

			<?
			if (strlen($msg_erro)>0){
				echo "<font size='2'>".$msg_erro."<br>";
			}elseif ($os_enviada_para_exclusao == "sim"){
				echo "<font size='2'>OS aguardando aprovação para exclusão da OS.<br>";
			}else{
			?>
				<font size='2'>Gentileza justificar exclusão da OS<br> A OS será enviada para aprovação e somente após aprovada será excluída
				<br>
				<!-- <font size='2'>	<b>Se excluída, não será gerado débito das peças.</b> -->
			<?}?>
			</td>
		</tr>

		<tr>
			<td align="center">

			</td>
		</tr>
		</table>
		<br>
		<FONT SIZE="2"><B>
		<?

		if ($os_enviada_para_exclusao != "sim" and $erro == ""){
				$aux_nov = $_GET['nova_os'];
				
				if (strlen($aux_nov) > 0) {
					$aux_sql = "SELECT posto, sua_os FROM tbl_os WHERE os = $aux_nov LIMIT 1";
					$aux_res = pg_query($con, $aux_sql);
					$nova_so = pg_fetch_result($aux_res, 0, 'sua_os');
					$aux_pos = pg_fetch_result($aux_res, 0, 'posto');

					$aux_sql = "SELECT codigo_posto FROM tbl_posto_fabrica WHERE posto = $aux_pos AND fabrica = $login_fabrica LIMIT 1";
					$aux_res = pg_query($con, $aux_sql);
					$aux_cp  = pg_fetch_result($aux_res, 0, 0);

					if (strlen($aux_cp) > 0 && strlen($nova_so) > 0) $aux_so  = $aux_cp.$nova_so; else $aux_so = "";

				}

				echo "Justificativa:";
				echo "<br>";
				echo "<textarea name='justificativa' cols='110' rows='5' maxlength='500' class='frm'>";
				if (strlen($aux_so) > 0) echo "Nova OS: $aux_so \n";
				echo "$justificativa</textarea>";
		}
		?>
		</B>

		<br>


	</td>
</tr>

<? if ($os_enviada_para_exclusao != "sim" and $erro == ""){ ?>
<tr>
	<td height="27" valign="middle" align="center" colspan="3" bgcolor="#FFFFFF">
		<input type="hidden" name="btn_acao" value="">
		<input type="hidden" name="target" value="<?=$target?>">
		<input type="hidden" name="tipo" value="<?=$tipo_atendimento?>">
		<input type="hidden" name="nova_os" value="<?=$nova_os?>">

		<input type='button' value='Gravar' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='excluir' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar" border='0' style="cursor:pointer;">
	</td>
</tr>
<?}?>


</table>
</form>

<?}

if ($os_enviada_para_exclusao == 'sim'){?>
<table width="700px" align="center" border="0" cellpadding="0">
<tr>
	<td align="center">
		<button onclick='window.location="os_excluir.php"'>Voltar</button>
	</td>
</tr>
</table>
<?}?>
<p>

<? include "rodape.php";?>
