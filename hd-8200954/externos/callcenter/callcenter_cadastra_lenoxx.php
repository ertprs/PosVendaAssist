<?php

include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';

$login_fabrica = 11;
$msg = $_GET['msg'];

if ($_POST["buscaCidade"] == true) {
	$estado = strtoupper($_POST["estado"]);

	if (strlen($estado) > 0) {
		$sql = "SELECT DISTINCT * FROM (
					SELECT UPPER(TO_ASCII(nome, 'LATIN9')) AS cidade FROM tbl_cidade WHERE UPPER(TO_ASCII(nome, 'LATIN9')) ~ UPPER(TO_ASCII('{$cidade}', 'LATIN9')) AND UPPER(estado) = UPPER('{$estado}')
					UNION (
						SELECT UPPER(TO_ASCII(cidade, 'LATIN9')) AS cidade FROM tbl_ibge WHERE UPPER(TO_ASCII(cidade, 'LATIN9')) ~ UPPER(TO_ASCII('{$cidade}', 'LATIN9')) AND UPPER(estado) = UPPER('{$estado}')
					)
				) AS cidade ORDER BY cidade ASC";
		$res  = pg_query($con, $sql);
		$rows = pg_num_rows($res);

		if ($rows > 0) {
			$cidades = array();

			for ($i = 0; $i < $rows; $i++) { 
				$cidades[$i] = array(
					"cidade"          => utf8_encode(pg_fetch_result($res, $i, "cidade")),
					"cidade_pesquisa" => utf8_encode(strtoupper(pg_fetch_result($res, $i, "cidade"))),
				);
			}

			$retorno = array("cidades" => $cidades);
		} else {
			$retorno = array("erro" => "Nenhuma cidade encontrada para o estado {$estado}");
		}
	} else {
		$retorno = array("erro" => "Nenhum estado selecionado");
	}

	exit(json_encode($retorno));
}

include_once("../../admin/callcenter_suggar_assuntos.php");
include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

//require('../../../class/email/mailer/class.phpmailer.php');

if (isset($_GET['familia']) AND isset($_GET['ajax']) ) {

	$familia = $_GET['familia'];

	$sql = "SELECT UPPER(tbl_produto.descricao) AS descricao,
				   /*tbl_produto.voltagem, */ 
				   tbl_produto.produto
              FROM tbl_produto
              JOIN tbl_linha   USING (linha)
             WHERE fabrica             = $login_fabrica
               AND tbl_produto.ativo  IS TRUE
               AND tbl_linha.ativo    IS TRUE
               AND tbl_produto.familia = $familia
             ORDER BY tbl_produto.descricao";

	$res = pg_query($con,$sql);

	if (pg_num_rows($res) == 0) {
		echo "<font size='1'>Nenhuma Informação Cadastrada.</font>";
	} else {
		$produtos = pg_fetch_all($res);
		echo "<table width='80%' border='0' align='center' cellpadding='0' cellspacing='2'>";
		echo "<tr>";

		foreach($produtos as $product_data) {
			//$voltagem  = $product_data['voltagem'];
			$descricao = $product_data['descricao'];
			$produto   = $product_data['produto'];
			echo "<td align='left' nowrap><input type='radio' name='produto' id='pr$produto' value='$produto' /><label for='pr$produto'>$descricao</label></td>";
			if ($x % 2 == 0) { echo "</tr><tr>"; }
		}
		echo "</tr>";
		echo "</table>";
	}
	exit;
}

$btn_acao = $_POST['btn_acao'];

if (strlen($btn_acao) > 0) {

	$aux_nome = trim($_POST['nome']);

	if (strlen($aux_nome) == 0) {
		$msg_erro = "Preencha o nome <br />";
	}

	$aux_endereco = trim($_POST['endereco']);

	if (strlen($aux_endereco) == 0 AND strlen($msg_erro) == 0) {
		$msg_erro .= "Preencha o campo Endereço <br />";
	}

	$aux_numero = trim($_POST['numero']);
	if(strlen($aux_nome) == 0 AND strlen($msg_erro) == 0){
		$msg_erro .= "Preencha o campo Número <br />";
	}

	$aux_complemento = trim($_POST['complemento']);
	if (strlen($aux_complemento) == 0 AND strlen($msg_erro) == 0) {
		$aux_complemento = '';
	}

	$aux_bairro = trim($_POST['bairro']);
	if (strlen($aux_bairro) == 0 AND strlen($msg_erro) == 0) {
		$msg_erro .= "Preencha o campo Bairro <br />";
	}

	$aux_estado = trim($_POST['estado']);
	if (strlen($aux_estado) == 0 AND strlen($msg_erro) == 0) {
		$msg_erro .= "Preencha o campo Estado <br />";
	}

	$aux_cidade = trim($_POST['cidade']);
	if (strlen($aux_cidade) == 0 AND strlen($msg_erro) == 0) {
		$msg_erro = "Preencha o campo Cidade";
	}else{
		if(strlen($msg_erro)==0){
			$res = pg_query ($con,"BEGIN TRANSACTION");
			if (strlen($aux_estado)>0 and strlen($aux_cidade)>0) {
					/* $sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(TO_ASCII(nome, 'LATIN9')) = UPPER(TO_ASCII('{$aux_cidade}', 'LATIN9')) AND UPPER(estado) = UPPER('{$aux_estado}')";
					$res = pg_query($con,$sql);
				//	echo nl2br($sql)."<BR>";
					if(pg_numrows($res)>0){
						$cidade = pg_fetch_result($res,0,0);
					}else{
						$sql = "INSERT INTO tbl_cidade(nome, estado) VALUES (upper('$aux_cidade'),'$aux_estado')";
					//	echo nl2br($sql)."<BR>";
						$res = pg_query($con,$sql);
						$msg_erro .= pg_last_error($con);
						$res    = pg_query($con,"SELECT CURRVAL ('seq_cidade')");
						$cidade = pg_fetch_result ($res,0,0);
					} */

					/* Verifica Cidade */

					$cidade = $aux_cidade;
					$estado = $aux_estado;

					$sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER('{$estado}')";
					$res = pg_query($con, $sql);

					if(pg_num_rows($res) == 0){

						$sql = "SELECT cidade, estado FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade)) = UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER('{$estado}')";
						$res = pg_query($con, $sql);

						if(pg_num_rows($res) > 0){

							$cidade = pg_fetch_result($res, 0, 'cidade');
							$estado = pg_fetch_result($res, 0, 'estado');

							$sql = "INSERT INTO tbl_cidade (nome, estado) VALUES ('$cidade', '$estado')";
							$res = pg_query($con, $sql);

						}else{
							$cidade = 'null';
						}

					}else{
						$cidade = pg_fetch_result($res, 0, 'cidade');
					}

					/* Fim - Verifica Cidade */

			}elseif($indicacao_posto=='f') {
				$msg_erro .= "Informe a cidade do consumidor";
			}
		}
	}


	$aux_cep = trim($_POST['cep']);
	$aux_cep = preg_replace ("/\D/",'',$aux_cep);
//	if(strlen($aux_cep) == 0 AND strlen($msg_erro) == 0){
//		$msg_erro = "Preencha o campo CEP";
//	}
	$aux_cpf				= trim($_POST['cpf']);
	$aux_cpf = preg_replace("/\D/", '', $aux_cpf);

	if(strlen($aux_cpf) == 0 and strlen($msg_erro) == 0){
		$msg_erro .= "Preencha o campo CPF <br />";
	}

	$sql_cpf = "SELECT fn_valida_cnpj_cpf('$aux_cpf')";
	$res_cpf = pg_query($con, $sql_cpf);

	$valida_cpf = pg_result($res_cpf, 0, 0);

	if($valida_cpf != true){
		$msg_erro .= "CPF Inválido <br />";
	}

	$aux_email         = trim($_POST['email']);
	if(strlen($aux_email) == 0 AND strlen($msg_erro) == 0){
		$msg_erro .= "Preencha o campo E-mail <br />";
	} else {
		if(filter_var($aux_email, FILTER_VALIDATE_EMAIL)) {
			$email_exploded = explode('@', $aux_email);
			if (!checkdnsrr($email_exploded[1])) {
				$msg_erro .= "E-mail inválido. <br />";
			}
		} else {
			$msg_erro .= "E-mail inválido <br />";
		}
	}

	$aux_rg     = trim($_POST['consumidor_rg']);
	if(strlen($aux_rg) == 0 AND strlen($msg_erro) == 0){
		$aux_rg = "null";
	}

	$aux_telefone = trim($_POST['telefone']);
	$aux_telefone_comercial = trim($_POST['telefone_comercial']);
	$aux_celular = trim($_POST['celular']);
	if(strlen($aux_telefone) == 0 && strlen($aux_telefone_comercial) == 0 && strlen($aux_celular) == 0 && strlen($msg_erro) == 0){
		$msg_erro .= "Preencha pelo menos um dos telefones: Telefone, Telefone Comercial ou Celular <br />";
	}

	$tipo_contato_categoria = trim($_POST['tipo_contato_categoria']);
	if(strlen($tipo_contato) == 0){
		$msg_erro .= "Selecione sobre o que deseja falar <br />";
	}

	if(strlen($tipo_contato_categoria) > 0){

		switch ($tipo_contato_categoria) {
			case 'Reclamação':
				$valor_tipo_contato_categoria = "reclamacao_produto";
				$hd_ex_sql_add_coluna = " ,hd_motivo_ligacao ";
				$hd_ex_sql_add_valor = " ,$tipo_contato ";

				$hd_sql_add_coluna = " ,categoria ";
				$hd_sql_add_valor = " ,'reclamacao_produto' ";
				break;

			case 'Reclamação Empresa':
				$valor_tipo_contato_categoria = "reclamacao_empresa_click";
				break;

			case 'Reclamação Assistência Técnica':
				$valor_tipo_contato_categoria = "reclamacao_at_click";
				$hd_sql_add_coluna = " ,categoria ";
				$hd_sql_add_valor = " ,'$tipo_contato' ";
				break;

			case 'Duvida Produto':
				$valor_tipo_contato_categoria = "duvida_produto_click";
				break;

			case 'Sugestão':
				$valor_tipo_contato_categoria = "sugestao_click";
				break;

			case 'Proncon/Judiciário':
				$valor_tipo_contato_categoria = "procon_click";
				$hd_sql_add_coluna = " ,categoria ";
				$hd_sql_add_valor = " ,'$tipo_contato' ";
				break;

			case 'Onde Comprar':
				$valor_tipo_contato_categoria = "onde_comprar_click";
				break;
		}

	}

	$tipo_contato = trim($_POST['tipo_contato']);
	if(strlen($tipo_contato) == 0 && ($tipo_contato_categoria == "Reclamação" || $tipo_contato_categoria == "Reclamação Assistência Técnica" || $tipo_contato_categoria == "Proncon/Judiciário")){
		$msg_erro .= "Selecione o qual o assunto <br />";
	}

	$produto =       trim($_POST['produto']);
	if(strlen($produto) == 0 && (in_array($tipo_contato, $assuntos["PRODUTOS"]))) {
		$msg_erro .= "SELECIONE O PRODUTO <br />";
	}

	$familia =       trim($_POST['familia']);
	if(strlen($familia) == 0 && (in_array($tipo_contato, $assuntos["PRODUTOS"]) || in_array($tipo_contato, $assuntos["MANUAL"]) ||$tipo_contato == 'at_demora_atendimento' )) {
		$msg_erro .= "SELECIONE UMA FAMILIA DE PRODUTO <br />";
	}

	$os = trim($_POST['os']);
	if ($os == '') $os = 'null';

	if(strlen($msg_erro) == 0) {
		if(strlen($familia) == 0) {
			$familia = 'null';
		}
		if(strlen($produto) == 0) {
			$produto = 'null';
		}
	}


	$reclamado    = trim($_POST['reclamado']);
	if(strlen($reclamado) == 0){
		$msg_erro .= "Preencha a Mensagem <br />";
	}

	if(strlen($msg_erro) == 0) {

		//HD 335548 - adicionei também ativo IS TRUE, pois estava mandando email para usuário inativo
		$sql_admins = "SELECT admin, email
		   				 FROM tbl_admin
						WHERE fabrica = $login_fabrica
					      AND fale_conosco IS TRUE 
						  AND ativo        IS TRUE
					 ORDER BY admin";
		$res_admins = @pg_query($con, $sql_admins);
		if (is_resource($res_admins)) {
			if (pg_num_rows($res_admins) == 0) {
				$login_admin = 453;
				$destinatario = "alessandra@lenoxx.com.br";
			} else {
				$admins = pg_fetch_all($res_admins);

				foreach ($admins as $a_admin) {
					$at[]= $a_admin['admin'];
				}
				$atendentes = implode(',',$at);

				$sql_last = "SELECT atendente FROM tbl_hd_chamado
							  WHERE fabrica_responsavel = $login_fabrica
								AND atendente IN($atendentes)
								AND admin = 453
							ORDER BY data DESC LIMIT 1";
				$res_last = @pg_query($con, $sql_last);
				unset($at,$atendentes);

				if (pg_num_rows($res_last) == 1) {
					$login_admin	= pg_fetch_result($res_last, 0, 0);
					//  Prcdura o próximo atendente da lista...
					foreach($admins as $idx => $atendente) {
						$admin = $atendente['admin'];
						//echo "<pre>Último: $login_admin, conferindo lista, atual (" . ++$idx ." de " . count($admins) ."): $admin</pre>";
						if ($admin == $login_admin) break;
					}
					if (++$idx == (count($admins))) {
// 						echo "<p>Resentando índices</p>";
						reset($admins);    // Se chegou no último, voltar para o primeiro
// 					} else {
// 						next($admins);
					}
					$atendente = current($admins);
					$login_admin	= $atendente['admin'];
					$destinatario	= $atendente['email'];

// 					exit("<p>Próximo atendente: $login_admin</p>");
				} else {
					$login_admin	= 453;
					$destinatario	= "alessandra@lenoxx.com.br";
				}

			}
			
			$res = pg_query($con,"BEGIN TRANSACTION");
			$titulo = 'Atendimento interativo';
			$xstatus_interacao = "'Aberto'";

				#-------------- INSERT ---------------
                $sql = "INSERT INTO tbl_hd_chamado (
                            admin               ,
                            data                ,
                            status              ,
                            atendente           ,
                            fabrica_responsavel ,
                            titulo              ,
                            fabrica 			
                            $hd_sql_add_coluna
                        )values(
                            2473                ,
                            current_timestamp   ,
                            $xstatus_interacao  ,
                            $login_admin        ,
                            $login_fabrica      ,
                            '$titulo'           ,
                            $login_fabrica		
                            $hd_sql_add_valor
                            )";
				$res		= pg_query($con,$sql);
				$msg_erro  .= pg_last_error($con);
				$res		= pg_query($con,"SELECT CURRVAL ('seq_hd_chamado')");
				$hd_chamado	= pg_fetch_result($res,0,0);

				$reshorario = pg_query($con,"SELECT TO_CHAR(data,'DD/MM/YYYY HH:MM') AS data FROM tbl_hd_chamado WHERE hd_chamado = $hd_chamado");
				$horario_hd = pg_fetch_result($reshorario,0,0);

				$sql = "INSERT INTO tbl_hd_chamado_extra(
									hd_chamado           ,
									reclamado            ,
									consumidor_revenda   ,
									nome                 ,
									endereco             ,
									numero               ,
									complemento          ,
									bairro               ,
									cep                  ,
									fone                 ,
									fone2                ,
									celular              ,
									email                ,
									cpf                  ,
									rg                   ,
									cidade               ,
									familia              ,
									produto				 ,
									os
									$hd_ex_sql_add_coluna
								)values(
								$hd_chamado					,
								'". $reclamado."'				,
								UPPER('$consumidor_revenda'),
								upper('$aux_nome')			,
								upper('$aux_endereco')		,
								upper('$aux_numero')		,
								upper('$aux_complemento')	,
								upper('$aux_bairro')		,
								'$aux_cep'					,
								'$aux_telefone'				,
								'$aux_telefone_comercial'	,
								'$aux_celular'				,
								'$aux_email'				,
								'$aux_cpf'					,
								upper('$aux_rg')			,
								$cidade						,
								$familia					,
								$produto					,
								$os
								$hd_ex_sql_add_valor
								) ";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_last_error($con);
		}
	}

	if (strlen ($msg_erro) == 0) {

		//$destinatario = "joao.junior@telecontrol.com.br";

		$email_admin = "alessandra@lenoxx.com.br";
		//$email_admin = "joao.junior@telecontrol.com.br";
		$remetente   = "Telecontrol <suporte@telecontrol.com.br>";
		$assunto     = "Chamado aberto pela página Fale Conosco";
		$msg_email   = "Prezado,<br />Foi cadastrado o chamado <a href=\"http://posvenda.telecontrol.com.br/assist/admin/callcenter_interativo_new.php?callcenter=$hd_chamado\">$hd_chamado</a> em $horario_hd pela página Fale Conosco Lenoxx. Por favor, verificar o Chamado. ";


		$headers  = "MIME-Version: 1.0 \r\n";
		$headers .= "Content-type: text/html; charset=iso-8859-1 \r\n";
		$headers .= "From: $remetente \r\n";
		$headers .= "Reply-to: $email_admin \r\n";

		mail($email_admin, utf8_encode($assunto), utf8_encode($msg_email), $headers);
		
		/* $username = 'tc.sac.suggar@gmail.com';
		$senha = 'tcsuggar';

	    $mailer = new PhpMailer(true);

	    $mailer->IsSMTP();
	    $mailer->Mailer = "smtp";
	    
	    $mailer->Host = 'ssl://smtp.gmail.com';
	    $mailer->Port = '465';
	    $mailer->SMTPAuth = true;
               
	    $mailer->Username = $username;
	    $mailer->Password = $senha;
	    $mailer->SetFrom($username, $username); 
	    $mailer->AddAddress($destinatario,$destinatario ); 
	    $mailer->AddAddress($email_admin,$email_admin); 
	    $mailer->Subject = utf8_decode($assunto);
	    $mailer->Body = utf8_decode($msg_email);

	    try{
			$mailer->Send();															
			$msg = "ok";
	    }catch(Exception $e){
	    
		}*/ 

		// Adicionar 'Cc:faleconosco@suggar.com.br' ao $headers se pedirem para enviar com cópia
		// if ( mail($destinatario, utf8_encode($assunto), utf8_encode($msg_email), $headers) ) {
		// 	$msg = "ok";
		// }

		//HD 234227
		if (strtolower($aux_email) != "null") {
			$remetente    = "Telecontrol <suporte@telecontrol.com.br>";
			$replyto      = "Lenoxx <alessandra@lenoxx.com.br>";
			$destinatario = $aux_email;
			$assunto      = "Protocolo $hd_chamado - Central de Relacionamento com o Cliente - Lenoxx";
			$msg_email = "
				Prezado(a) Consumidor (a), <br /> <br />

            	Agradecemos pelo seu contato com a Lenoxx, sua mensagem foi recebida com sucesso.   
            	É fundamental que você anote o número do seu protocolo, pois com ele será possível 
            	acompanhar o andamento da sua manifestação, se for necessário. 
            	Número do Protocolo: $hd_chamado. Seu e-mail será respondido em breve. 
            	Esta é uma mensagem automática. <br />
            	Por favor, não responda este e-mail. <br />

				*** Verifique em nosso site (através do link
				http://www.lenoxxsound.com.br/index2.php?cont=videos ) 
				temos diversas orientações de como resolver seu caso, 
				como por exemplo: Baixar aplicativos no Tablet, desbloqueio de senhas, 
				uso do wi-fi e uso do modem no tablet, entre outros. <br /> <br />

				Atenciosamente, <br /> <br />

				<strong>SAC Lenoxx</strong>
			";

			//INCLUIR QUANDO O CLIENTE SOLICITAR NO FINAL DA MENSAGEM:  ou se preferir, você pode também entrar em contato por telefone 08002005050
			
			//$headers = "Return-Path: <$email_admin>\r\nFrom: $remetente\r\n$replyto\r\nContent-type: text/html\r\n";

			/* $mail = new PHPMailer();
			$mail->IsSMTP();
			$mail->IsHTML();
			$mail->AddReplyTo($remetente);
			$mail->AddReplyTo($replyto);
			$mail->Subject = $assunto;
		    $mail->Body = $msg_email;
		    $mail->AddAddress($destinatario);
		    $mail->Send();

			$username = 'tc.sac.suggar@gmail.com';
			$senha = 'tcsuggar';

		    $mailer = new PhpMailer(true);

		    $mailer->IsSMTP();
		    $mailer->Mailer = "smtp";
		    
		    $mailer->Host = 'ssl://smtp.gmail.com';
		    $mailer->Port = '465';
		    $mailer->SMTPAuth = true;
	               
		    $mailer->Username = $username;
		    $mailer->Password = $senha;
		    $mailer->SetFrom($username, $username); 
		    $mailer->AddAddress($destinatario,$destinatario ); 		    
		    $mailer->Subject = utf8_encode($assunto);
		    $mailer->Body = utf8_encode($msg_email);

		    try{
				$mailer->Send();																		
		    }catch(Exception $e){
		    
			} */


			mail($email_admin, utf8_encode($assunto), utf8_encode($msg_email), $headers);
			mail($destinatario, utf8_encode($assunto), utf8_encode($msg_email), $headers);

		}

		$res = pg_query ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF?msg=ok&protocolo=$hd_chamado");
		exit;

	} else {
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
	}

}


if ($msg =='ok') {

	$msg = "Mensagem enviada com sucesso! <br />
Aguarde que entraremos em contato em até 5 dias úteis.<br />
Número do Protocolo deste atendimento: " . $_GET["protocolo"]
."<br />A resposta poderá ser enviada para o e-mail cadastrado, fique atento a caixa de spam ou lixo eletrônico.";

	$msg_estilo = 'msg';
	$mensagem   = $msg;

}

if (strlen($msg_erro) > 0){

	$msg_estilo = 'msg_erro';

	if (strpos($msg_erro,"ERROR:") !== false) {
		$x = explode('ERROR:', $msg_erro);
		$msg_erro = $x[1];
	}

	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:', $msg_erro);
		$msg_erro = $x[0];
	}

	$mensagem = $msg_erro;

	$nome          = $_POST['nome'];
	$cnpj          = $_POST['cnpj'];
	$ie            = $_POST['ie'];
	$endereco      = $_POST['endereco'];
	$numero        = $_POST['numero'];
	$complemento   = $_POST['complemento'];
	$bairro        = $_POST['bairro'];
	$cep           = $_POST['cep'];
	$cidade        = $_POST['cidade'];
	$estado        = $_POST['estado'];
	$contato       = $_POST['contato'];
	$email         = $_POST['email'];
	$fone          = $_POST['fone'];
	$fax           = $_POST['fax'];
	$nome_fantasia = $_POST['nome_fantasia'];

} ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<title>Lenoxx</title>
	<link rel="stylesheet" type="text/css" href="../css/estilo_suggar.css" />
	<link rel="stylesheet" type="text/css" href="http://www.suggar.com.br/telecontrol/css/estilo_telecontrol.css" />
	<link rel="stylesheet" type="text/css" href="http://www.suggar.com.br/App_Themes/telecontrol/estilo_telecontrol.css" />
	<script type="text/javascript" src="suggar.js"></script>
	<script type="text/javascript" src="../../js/jquery-1.5.2.min.js"></script>
	<script type="text/javascript" src="../../js/jquery.maskedinput.js"></script>
	<script type="text/javascript">

	var assuntos = new Array();

	$(document).ready(function(){
		$("#telefone").maskedinput("(99) 9999-9999");
		$("#telefone_comercial").maskedinput("(99) 9999-9999");
		$("#celular").maskedinput("(99) 9999-9999");
		$("#cep").maskedinput("99999-999");
		$('#cpf').keypress(function(e){
			return txtBoxFormat(document.frm_posto, this.name, '999.999.999-99', e);
		});

		<?
			if ($tipo_contato) {
				echo "mostraFamilia('$tipo_contato');";
			}

		?>

		$("#estado").change(function () {
			if ($(this).val().length > 0) {
				buscaCidade($(this).val());
			} else {
				$("#cidade > option[rel!=default]").remove();
			}
		});
	});

	function buscaCidade (estado, cidade) {
		$.ajax({
			async: false,
			url: "callcenter_cadastra_suggar.php",
			type: "POST",
			data: { buscaCidade: true, estado: estado },
			cache: false,
			complete: function (data) {
				data = $.parseJSON(data.responseText);

				if (data.cidades) {
					$("#cidade > option[rel!=default]").remove();

					var cidades = data.cidades;

					$.each(cidades, function (key, value) {
						var option = $("<option></option>");
						$(option).attr({ value: value.cidade_pesquisa });
						$(option).text(value.cidade);

						if (cidade != undefined && value.cidade.toUpperCase() == cidade.toUpperCase()) {
						 	$(option).attr({ selected: "selected" });
						}

						$("#cidade").append(option);
					});
				} else {
					$("#cidade > option[rel!=default]").remove();
				}
			}
		});
	}

	function buscaCEP(cep) {
		$.ajax({
			type: "GET",
			url:  "../../admin/ajax_cep.php",
			data: "cep="+escape(cep),
			cache: false,
			complete: function(resposta){
				results = resposta.responseText.split(";");
				if (typeof (results[1]) != 'undefined') $('#endereco').val(results[1]);
				if (typeof (results[2]) != 'undefined') $('#bairro').val(results[2]);
				if (typeof (results[4]) != 'undefined') $('#estado').val(results[4]);

				buscaCidade(results[4], results[3]);
			}
		});
	}

	function txtBoxFormat(objForm, strField, sMask, evtKeyPress) {
		var i, nCount, sValue, fldLen, mskLen,bolMask, sCod, nTecla;

		if(document.all) { // Internet Explorer
			nTecla = evtKeyPress.keyCode;
		} else if(document.layers) { // Nestcape
			nTecla = evtKeyPress.which;
		} else {
			nTecla = evtKeyPress.which;
			if (nTecla == 8) {
				return true;
			}
		}

		sValue = objForm[strField].value;

		sValue = sValue.toString().replace( /\W/g, "" );
/*		sValue = sValue.toString().replace( "-", "" );
		sValue = sValue.toString().replace( ".", "" );
		sValue = sValue.toString().replace( ".", "" );
		sValue = sValue.toString().replace( "/", "" );
		sValue = sValue.toString().replace( "/", "" );
		sValue = sValue.toString().replace( "/", "" );
		sValue = sValue.toString().replace( "(", "" );
		sValue = sValue.toString().replace( "(", "" );
		sValue = sValue.toString().replace( ")", "" );
		sValue = sValue.toString().replace( ")", "" );
		sValue = sValue.toString().replace( " ", "" );
		sValue = sValue.toString().replace( " ", "" );
 */		fldLen = sValue.length;
		mskLen = sMask.length;

		i = 0;
		nCount = 0;
		sCod = "";
		mskLen = fldLen;

		while (i <= mskLen) {
		bolMask = ((sMask.charAt(i) == "-") || (sMask.charAt(i) == ":") || (sMask.charAt(i) == ".") || (sMask.charAt(i) == "/"))
		bolMask = bolMask || ((sMask.charAt(i) == "(") || (sMask.charAt(i) == ")") || (sMask.charAt(i) == " ") || (sMask.charAt(i) == "."))


		if (bolMask) {
			sCod += sMask.charAt(i);
			mskLen++;

		} else {
			sCod += sValue.charAt(nCount);
			nCount++;
		}
		i++;
		}

		objForm[strField].value = sCod;
		if (nTecla != 8) { // backspace
			if (sMask.charAt(i-1) == "9") { // apenas números...
				return ((nTecla > 47) && (nTecla < 58)); } // números de 0 a 9
			else { // qualquer caracter...
				return true;
			}
		} else {
			return true;
		}
	}

	function fnc_tipo_atendimento(tipo) {
			$('#cpf').val('');
		if (tipo.value == 'C') {
			$('#cpf').attr('maxLength', 14);
			$('#cpf').attr('size', 18);
			$('#label_cpf').html('CPF:');
			$('#cpf').keypress(function(e){
				return txtBoxFormat(document.frm_posto, this.name, '999.999.999-99', e);
			});
		} else {
			$('#cpf').attr('maxLength', 18);
			$('#cpf').attr('size', 23);
			$('#label_cpf').html('CNPJ:');
			$('#cpf').keypress(function(e){
				return txtBoxFormat(document.frm_posto, this.name, '99.999.999/9999-99', e);
			});
		}
	}

	function mostraProdutos(familia){
		if (familia) {
			$.ajax({
				type: "GET",
				url: "<?=$PHP_SELF?>",
				data: 'familia='+familia+'&ajax=sim',
				beforeSend: function(){
					$('#produtos').html("<img src='../../imagens/carregando.gif'> ");
				},
				complete: function(resultado) {
					resultado2 = resultado.responseText;
					$('#produtos').css('display','block');
					$('#produtos').html(resultado2);
				}
			});
		}
		else {
			$('#produtos').html('');
		}
	}

	function mostraAssunto(assunto){

		if(assunto == "Reclamação"){
			$('table > tbody > tr.menu_top').eq(3).show();

			$('#tipo_contato').html("<option value=''></option>");

			$('#tipo_contato').append("\
				<option value='28' >Indicação de Posto</option> \
				<option value='29' >Duvidas Técnicas</option> \
				<option value='31' >Falta de Posto na região </option> \
				<option value='30' >O.S. pendente no Posto</option> \
				<option value='32' >Reincidência de vicio</option> \
				<option value='33' >Retorno de ligação</option> \
				<option value='34' >Falta de acessórios</option> \
				<option value='36' >Especific.Técnica divergente</option> \
				<option value='35' >Atendimento ao PROCON</option> \
				<option value='37' >Confirmação de documentos</option> \
				<option value='39' >Produto fora de garantia</option> \
				<option value='40' >Criticas e Sugestões</option> \
				<option value='41' >Loja virtual</option>\
			");
		}

		if(assunto == "Reclamação Empresa"){
			$('table > tbody > tr.menu_top').eq(3).hide();
		}

		if(assunto == "Reclamação Assistência Técnica"){
			$('table > tbody > tr.menu_top').eq(3).show();

			$('#tipo_contato').html("<option value=''></option>");

			$('#tipo_contato').append("\
				<option value='reclamacao_at' >RECLAMAÇÃO DA ASSIST. TÉCN.</option> \
				<option value='reclamacao_at_info' >INFORMAÇÕES DE A.T</option> \
				<option value='posto_nao_contribui' >POSTO NÃO CONTRIBUI COM INFORMAÇÕES</option> \
				<option value='mau_atendimento' >MAU ATENDIMENTO</option> \
				<option value='possui_bom_atend' >POSSUI BOM ATENDIMENTO</option> \
				<option value='demonstra_desorg' >DEMONSTRA DESORGANIZAÇÃO </option> \
				<option value='demonstra_org' >DEMONSTRA ORGANIZAÇÃO</option> \
			");

		}

		if(assunto == "Duvida Produto"){
			$('table > tbody > tr.menu_top').eq(3).hide();
		}

		if(assunto == "Sugestão"){
			$('table > tbody > tr.menu_top').eq(3).hide();
		}


		if(assunto == "Proncon/Judiciário"){

			$('table > tbody > tr.menu_top').eq(3).show();

			$('#tipo_contato').html("<option value=''></option>");

			$('#tipo_contato').append("\
				<option value='pr_reclamacao_at' >RECLAMAÇÃO DA ASSIST. TÉCN.</option> \
				<option value='pr_reclamacao_at_info' >INFORMAÇÕES DE A.T</option> \
				<option value='pr_posto_nao_contribui' >POSTO NÃO CONTRIBUI COM INFORMAÇÕES</option> \
				<option value='pr_mau_atendimento' >MAU ATENDIMENTO</option> \
				<option value='pr_possui_bom_atend' >POSSUI BOM ATENDIMENTO</option> \
				<option value='pr_demonstra_desorg' >DEMONSTRA DESORGANIZAÇÃO </option> \
				<option value='pr_demonstra_org' >DEMONSTRA ORGANIZAÇÃO</option> \
			");

		}

		if(assunto == "Onde Comprar"){
			$('table > tbody > tr.menu_top').eq(3).hide();
		}

	}

	function escondeAssunto(){
		$('table > tbody > tr.menu_top').eq(3).hide();
	}

</script>


</head>

<body onload="escondeAssunto()">
	<div id='tabela_miolo'>
		<div id='msg' class='<?=$msg_estilo?>' style='margin:auto;text-align:center;color:green;font-size:14px;font-weight:bold;left:0;'>
			<?if(strlen($mensagem) > 0) echo $mensagem;	?>
		</div>
		<p class="texto">&nbsp;</p>
		<form name="frm_posto" action="<?$PHP_SELF?>" method="post">
		<table width="100%" border="0" cellpadding="5" cellspacing="5" class="tabela_produtos">
			<tr>
				<td>
					<table width="100%" border="0" cellspacing="5" cellpadding="5">
						<tr class='menu_top'>
							<td colspan='2' nowrap='nowrap' align='center'>INFORMAÇÕES CADASTRAIS</td>
						</tr>
						<tr>
							<td colspan='2' nowrap='nowrap' class="obrigatorio">* Campos obrigatórios</td>
						</tr>
						<tr>
							<td align='left'>
							<b>Você é:</b>&nbsp;
								<input type='radio' name='consumidor_revenda' value='C' <?php if ($consumidor_revenda == 'C' or $consumidor_revenda == '') { echo "checked='checked' ";}?> onclick="fnc_tipo_atendimento(this)" />Consumidor

								<input type='radio' name='consumidor_revenda' value='R' <?php if ($consumidor_revenda == 'R') { echo "checked='checked' ";}?> onclick="fnc_tipo_atendimento(this)" />Revenda
							</td>
						</tr>
						<tr>
							<td><span class="obrigatorio">* </span>Nome:
								<br />
								<label><input type="text" name="nome" size="50" class="label" maxlength="50" id="nome" value="<? echo $nome ?>" onkeyup="somenteMaiusculaSemAcento (this)" /></label>
							</td>
							<td><label id='label_cpf'><span class="obrigatorio">* </span>CPF:</label>
								<br />
								<label><input type="text" name="cpf" size="18" class="label" id='cpf' maxlength="14" value="<? echo $cpf ?>"  /></label>
							</td>
						</tr>
						<tr>
							<td colspan="2"><span class="obrigatorio">* </span>Telefone:
								<br />
								<label><input type="text" name="telefone" class="label" size="15" maxlength="30" value="<? echo $telefone ?>" id='telefone' <? echo "$readonly";?> /></label>
							</td>
						</tr>
						<tr>
							<td>Telefone Comercial.:
								<br />
								<label><input type="text" class="label" name="telefone_comercial" id='telefone_comercial' size="15" maxlength="20" value="<? echo $telefone_comercial ?>"  /></label>
							</td>
							<td>Celular:
								<br />
								<label><input type="text" name="celular" class="label" size="15" maxlength="20" value="<? echo $celular ?>" id='celular' <? echo "$readonly";?> /></label>
							</td>
						</tr>
						<tr>
							<td><span class="obrigatorio">* </span>E-mail:
								<br />
								<label><input name="email" type="email" class="label" id="email" size="40" value='<?=$email?>'/></label>
							</td>
							<td>CEP:
								<br />
								<label><input name="cep" type="text" class="label" id="cep" size="20" onblur="buscaCEP(this.value )" value='<?=$cep?>'/></label>
							</td>
						</tr>
						<tr>
							<td><span class="obrigatorio">* </span>Endere&ccedil;o:
								<br />
								<label><input name="endereco" type="text" class="label" id="endereco" size="50" maxlength='50' value='<?=$endereco?>'/></label>
							</td>
							<td><span class="obrigatorio">* </span>Número:
								<br />
								<label><input name="numero" type="text" class="label" id="numero" size="20" maxlength='10' value='<?=$numero?>'/></label>
							</td>
						</tr>
						<tr>
							<td>Complemento:
								<br />
								<label><input name="complemento" type="text" class="label" id="complemento" size="30" maxlength='20' value='<?=$complemento?>'/></label>
							</td>
							<td><span class="obrigatorio">* </span>Bairro:
								<br />
								<label><input name="bairro" type="text" class="label" id="bairro" size="30" maxlength='40' value='<?=$bairro?>'/></label>
							</td>
						</tr>
						<tr>
							<td><span class="obrigatorio">* </span>Estado:
								<br />
								<label>
								<select name="estado" id='estado' style='width:81px; font-size:9px'>
								<? $ArrayEstados = array('','AC','AL','AM','AP',
															'BA','CE','DF','ES',
															'GO','MA','MG','MS',
															'MT','PA','PB','PE',
															'PI','PR','RJ','RN',
															'RO','RR','RS','SC',
															'SE','SP','TO'
														);

									for ($i = 0; $i <= 27; $i++) {
										echo"<option value='".$ArrayEstados[$i]."'";
										if ($estado == $ArrayEstados[$i]) echo " selected='selected' ";
										echo ">".$ArrayEstados[$i]."</option>\n";
									}?>

								</select>
								</label>
							</td>
							<td><span class="obrigatorio">* </span>Cidade:
								<br />
								<label>
								<select name="cidade" id='cidade' style='font-size:9px' title='Selecione um estado para escolher uma cidade'>
									<option></option>
								</select>
								</label>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>

		<?php
		if ($_POST) {
			echo "<script>buscaCidade('{$estado}', '{$cidade}')</script>";
		}
		?>

		<br />
		
		<table width="100%" border="0" cellpadding="5" cellspacing="5" class="tabela_produtos">
			<tr class="menu_top">
				<td colspan='2'>INFORMAÇÕES DO CONTATO</td>
			</tr>

			<tr class="menu_top" align='center'>
				<td align='right' width='180'><span class="obrigatorio">* </span>DESEJA FALAR SOBRE:</td>
				<td align='left'>
				<select name='tipo_contato_categoria' id='tipo_contato_categoria' onchange='mostraAssunto(this.value)'>
					<option value=''  onclick='alert("Escolha um Tipo de Contato");'></option>
					<option value='Reclamação'>Reclamação</option>
					<option value='Reclamação Empresa'>Reclamação Empresa</option>
					<option value='Reclamação Assistência Técnica'>Reclamação Assistência Técnica</option>
					<option value='Duvida Produto'>Duvida Produto</option>
					<option value='Sugestão'>Sugestão</option>
					<option value='Proncon/Judiciário'>Proncon/Judiciário</option>
					<option value='Onde Comprar'>Onde Comprar</option>
				</select>
				</td>
			</tr>

			<tr class="menu_top" align='center'>
				<td align='right' width='180'>QUAL O ASSUNTO:</td>
				<td align='left'>
				<select name='tipo_contato' id='tipo_contato'><?php

				if ($tipo_contato_categoria) {

					foreach ($assuntos[$tipo_contato_categoria] AS $opcao => $categoria_grava_bd) {

						if ($categoria_grava_bd == $tipo_contato) {
							$selected = "selected='selected' ";
						} else {
							$selected = "";
						}

						echo " <option value='$categoria_grava_bd' $selected>$opcao</option>";
					}

				} else {

					echo '<option value="">Escolha o tipo de contato</option>';

				}?>
				</select>
				</td>
			</tr>
			<tr class="menu_top" align='center'>
				<td align='right'>FAMÍLIA DE PRODUTO:</td>
				<td align='left'>
				<select name='familia' id='familia' onchange='mostraProdutos(this.value)'><?
					echo "<option value=''>&gt;&gt;&gt; ESCOLHA &lt;&lt;&lt;</option>";

					$sql = "SELECT familia,descricao
							FROM tbl_familia
							WHERE fabrica = $login_fabrica
							AND ativo IS TRUE
							ORDER BY descricao ";

					$res = pg_query($con, $sql);

					for ($i =  0; $i < pg_numrows($res); $i++) {

						$familia           = pg_fetch_result($res, $i, 'familia');
						$descricao_familia = pg_fetch_result($res, $i, 'descricao');
						$descricao_familia = mb_strtoupper($descricao_familia);

						echo "<option value='$familia'>$descricao_familia</option>";

					}?>
					</select>
				</td>
			</tr>
			<tr class="menu_top" align='center'>
				<td colspan='2'>
					<div id='produtos' style='display:block;position:relative;background-color: #e6eef7;width:100%'></div>
				</td>
			</tr>
			<tr style="display:none;" id="os_tr">
				<td colspan='2' align='center'><b><span class="obrigatorio">* </span>Número da Ordem de Serviço: </b>
					<label><input name="os" type="text" class="label" id="os" size="20" maxlength='20' value='<?=$os?>'/></label>
				</td>
			</tr>
			<tr class="menu_top" align='center'>
				<td align='center' colspan='2'><span class="obrigatorio">* </span>Mensagem:<br /><textarea class="label" name="reclamado" rows="5" cols="60"><? echo $reclamado;?></textarea></td>
			</tr>
		</table>
		<p align="center">
		<input type='hidden' name='btn_acao' value='' />
		<img src="../../imagens/btn_gravar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_posto.btn_acao.value == '' ) { document.frm_posto.btn_acao.value='cadastrar' ; document.frm_posto.submit() } else { alert ('Aguarde submissão') }" alt="Gravar formulário" border='0' />
		</p>
	</form>

	<div id='msg' class='<?=$msg_estilo?>' style='margin:auto;text-align:center;color:green;font-size:14px;font-weight:bold;left:0;'>
		<?if(strlen($mensagem) > 0) echo $mensagem;	?>
	</div>

	</div>
	
	<a href="javascript:history.back()"><img src="../../imagens/voltar.jpg" alt='Voltar' width="69" height="22" border="0" align="right" /></a>
</body>
</html>
