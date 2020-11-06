<?php

include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';
include '../../funcoes.php';
include '../../helpdesk/mlg_funciones.php';
include '../../classes/cep.php';

$login_fabrica = 175;

$array_estado = array(
	'AC' => 'Acre',
	'AL' => 'Alagoas',
	'AM' => 'Amazonas',
	'AP' => 'Amapá',
	'BA' => 'Bahia',
	'CE' => 'Ceara',
	'DF' => 'Distrito Federal',
	'ES' => 'Espírito Santo',
	'GO' => 'Goiás',
	'MA' => 'Maranhão',
	'MG' => 'Minas Gerais',
	'MS' => 'Mato Grosso do Sul',
	'MT' => 'Mato Grosso',
	'PA' => 'Pará',
	'PB' => 'Paraíba',
	'PE' => 'Pernambuco',
	'PI' => 'Piauí­',
	'PR' => 'Paraná',
	'RJ' => 'Rio de Janeiro',
	'RN' => 'Rio Grande do Norte',
	'RO' => 'Rondônia',
	'RR' => 'Roraima',
	'RS' => 'Rio Grande do Sul',
	'SC' => 'Santa Catarina',
	'SE' => 'Sergipe',
	'SP' => 'São Paulo',
	'TO' => 'Tocantins'
);

function validaEmail() {
	global $_POST;

	$email = $_POST["email"];

	if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
		throw new Exception("Email inválido");
	}
}
if ($_POST["ajax_enviar"]) {

	$regras = array(
		"notEmpty" => array(
			"nome",
			"email",
			"telefone",
			"mensagem",
			"departamento"
		),
		"validaEmail"  => "email",
	);

	$msg_erro = array(
		"msg"    => array(),
		"campos" => array()
	);

	if ($_POST['departamento'] == "posvendas"){
		$regras["notEmpty"][] = "natureza";
	}

	foreach ($regras as $regra => $campo) {
		switch ($regra) {
			case "notEmpty":
				foreach($campo as $input) {
					$valor = trim($_POST[$input]);
					if (empty($valor)) {
						$msg_erro["msg"]["obg"] = utf8_encode("Preencha todos os campos obrigatórios");
						$msg_erro["campos"][]   = $input;
					}
				}
				break;

			default:
				$valor = trim($_POST[$campo]);

				if (!empty($valor)) {
					try {
						call_user_func($regra);
					} catch(Exception $e) {
						$msg_erro["msg"][]    = utf8_encode($e->getMessage());
						$msg_erro["campos"][] = $campo;
					}
				}
				break;
		}
	}

	if (count($msg_erro["msg"]) > 0) {
		$retorno = array("erro" => $msg_erro);
	} else {
		$nome     = str_replace('\'', '', utf8_decode(trim($_POST["nome"])));
		$email    = utf8_decode(trim($_POST["email"]));
		$telefone = utf8_decode(trim($_POST["telefone"]));
		$departamento = utf8_decode(trim($_POST["departamento"]));
		$natureza = utf8_decode(trim($_POST["natureza"]));
		$mensagem = str_replace('\'', '', utf8_decode(trim($_POST["mensagem"])));

		if ($departamento == "posvendas"){
			$departamento = "Pós vendas";
		}else if ($departamento == "duvidas"){
			$departamento = "Dúvidas";
		}else if ($departamento == "rh"){
			$departamento = "RH";
		}else if ($departamento == "outros"){
			$departamento = "Outros departamentos";
		}else{
			$departamento = ucfirst(strtolower($departamento));
		}

		$sqlOrigem = "SELECT hd_chamado_origem FROM tbl_hd_chamado_origem WHERE fabrica = $login_fabrica AND descricao = 'Fale Conosco'";
		$resOrigem = pg_query($con, $sqlOrigem);
		
		if (pg_num_rows($resOrigem) > 0){
			$resOrigem = pg_fetch_result($resOrigem, 0, 'hd_chamado_origem');
		}
		
		$sql = "SELECT COUNT(hc.hd_chamado) AS qtde, 
					a.admin, 
					a.email
		        FROM tbl_admin a
		        JOIN tbl_hd_origem_admin hoa ON hoa.admin = a.admin AND hoa.fabrica = {$login_fabrica}
		        	AND hoa.hd_chamado_origem = {$resOrigem}
		        LEFT JOIN tbl_hd_chamado hc ON hc.atendente = a.admin AND hc.fabrica = {$login_fabrica} 
		        	AND hc.status = 'Aberto'
		        WHERE a.fabrica = {$login_fabrica}
		        AND a.ativo IS TRUE
		        AND a.atendente_callcenter IS TRUE
		        GROUP BY a.admin
		        ORDER BY qtde ASC
		        LIMIT 1;";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0){
			$admin_fale_conosco = pg_fetch_result($res, 0, 'admin');
			$emails_admins 		= pg_fetch_result($res, 0, 'email');

			try {
				pg_query($con, "BEGIN");

				$sql = "INSERT INTO tbl_hd_chamado (
							admin,
							data,
							atendente,
							fabrica_responsavel,
							fabrica,
							titulo,
							status,
							categoria
						) VALUES (
							{$admin_fale_conosco},
							CURRENT_TIMESTAMP,
							{$admin_fale_conosco},
							{$login_fabrica},
							{$login_fabrica},
							'Fale Conosco',
							'Aberto',
							'$natureza'
						) RETURNING hd_chamado";
				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					throw new Exception("Erro ao abrir o atendimento");
				}

				$hd_chamado = pg_fetch_result($res, 0, "hd_chamado");

				$sql = "INSERT INTO tbl_hd_chamado_extra (
							hd_chamado,
							nome,
							email,
							fone,
							origem,
							hd_chamado_origem,
							reclamado
						) VALUES (
							{$hd_chamado},
							'{$nome}',
							'{$email}',
							'{$telefone}',
							'Fale Conosco',
							$resOrigem,
							'$mensagem'
						)";
				$res = pg_query($con, $sql);
				
				if (strlen(pg_last_error()) > 0) {
					throw new Exception(utf8_encode("Erro ao abrir o atendimento"));
				}

				$sql = "INSERT INTO tbl_hd_chamado_item (
							hd_chamado,
							admin,
							comentario
						) VALUES (
							{$hd_chamado},
							{$admin_fale_conosco},
							'$departamento : {$mensagem}'
						)";
				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					throw new Exception("Erro ao abrir o atendimento");
				}

				$headers  = 'From: Fale Conosco - Ibramed <helpdesk@telecontrol.com.br>' . "\r\n";
				$headers .= 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
				$admin_email = $emails_admins;

				if ($_serverEnvironment == "development") {
					$admin_email = "guilherme.monteiro@telecontrol.com.br";
					$mensagem_email = "Foi aberto o atendimento <a href=\"http://novodevel.telecontrol.com.br/~monteiro/Posvenda/admin/callcenter_interativo_new.php?callcenter=$hd_chamado\">$hd_chamado</a> pela página do Fale Conosco Ibramed. Por favor, verificar o Chamado.";
				} else {
					$admin_email = $emails_admins;
					$mensagem_email = "Foi aberto o atendimento <a href=\"http://posvenda.telecontrol.com.br/assist/admin/callcenter_interativo_new.php?callcenter=$hd_chamado\">$hd_chamado</a> pela página do Fale Conosco Ibramed. Por favor, verificar o Chamado.";
				}

				mail($admin_email, "Atendimento aberto pelo fale conosco", $mensagem_email, $headers);

				pg_query($con, "COMMIT");

				$retorno = array("sucesso" => true, "hd_chamado" => $hd_chamado);
			} catch (Exception $e) {
				$msg_erro["msg"][] = $e->getMessage();
				$retorno = array("erro" => $msg_erro);
				pg_query($con, "ROLLBACK");
			}
		}else{
			$msg_erro["msg"]["obg"] = utf8_encode("Erro ao gravar atendimento, favor entre em contato com o fabricante.");
			$retorno = array("erro" => $msg_erro);
		}
	}
	exit(json_encode($retorno));
}

?>

<!DOCTYPE html />
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
	<meta name="language" content="pt-br" />

	<!-- jQuery -->
	<script type="text/javascript" src="plugins/jquery-1.11.3.min.js" ></script>

	<!-- Bootstrap -->
	<script type="text/javascript" src="plugins/bootstrap/js/bootstrap.min.js" ></script>
	<link rel="stylesheet" type="text/css" href="plugins/bootstrap/css/bootstrap.min.css" />

	<!-- Plugins Adicionais -->
	<script type="text/javascript" src="../../plugins/jquery.mask.js"></script>
	<script type="text/javascript" src="../../plugins/jquery.alphanumeric.js"></script>
	<script type="text/javascript" src="../../plugins/fancyselect/fancySelect.js"></script>
	<link rel="stylesheet" type="text/css" href="../../plugins/fancyselect/fancySelect.css" />

	<style>
		html {
			font-family: "Open Sans", sans-serif;
			font-size: 14px;
			font-weight: 300;
			line-height: 1.42857;
			color: #3E3E3D;
		}

		body{
			font-family: 'Ubuntu', verdana,sans-serif !important;
	    	font-weight: 300 !important;
		}
		div.container {
			max-width: 595px;
		}

		legend {
			font-size: 18px;
			border: medium none;
			margin-bottom: 40px;
		}

		.fale_conosco{
			font-size: 28px;
			text-transform: uppercase;
			color: #26357c;
		}

		.span_icon{
			background: #ccc;
		    padding: 3px 3px 2px 3px;
		    border-radius: 3px;
		}

		.campo_obrigatorio {
			color: #ED333A;
		}

		label {
			font-weight: 400;
			font-size: 14px;
			color: #4b4d4d;
		}

		input, select, textarea{
			/*border-radius: 3px;
			font-size: 12px;
			color: #3E3E3D !important;
			height: 44px !important;
			padding: 10px 15px;
			border-color: #E2E0DF !important;
			box-shadow: 0px 0px 0px transparent;*/
		
		    width: 100% !important;
		    padding: 6px !important;
		    background: #eeeeee !important;
		    border: 1px solid #dadada !important;
		    margin-bottom: 10px !important;
		    border-radius: 0px !important;
		}

		textarea {
			height: auto !important;
		}

		input:focus, select:focus, textarea:focus {
			/*border-color: #d4d4f9 !important;*/
			border: solid 2px #bfbffc !important;
		}

		div.has-error label {
			color: #ED333A !important;
		}

		div.has-error input, div.has-error textarea, div.has-error select, div.has-error div.trigger {
			border-color: #ED333A !important;

		}

		#msg_erro, #msg_sucesso {
			display: none;
		}

		#enviar {
			transition: background-color 0.25s ease-in-out 0s, color 0.25s ease-in-out 0s, border-color 0.25s ease-in-out 0s;
			font-size: 14px;
			background-color: #26357c;
			color: #ffffff;
			font-weight: 700;
			letter-spacing: 1;
			border-radius: 5px;
			border: medium none;
			/*padding: 18px 30px;*/
			width: 100%;
		}

		#enviar:hover {
			transition: background-color 0.25s ease-in-out 0s, color 0.25s ease-in-out 0s, border-color 0.25s ease-in-out 0s;
			color: #ffffff;
			background-color: #152054;
		}

		span.loading {
			color: #58b847;
			margin-left: 20px;
		}

		.alert {
			border: medium none;
			font-weight: 300;
			padding: 15px 20px;
			border-radius: 3px;
		}

		.alert-danger {
			background-color: #EE6057;
			color: #FFF;
		}

		.alert-success {
			background-color: #58b847 !important;
			color: #FFF;
		}

		#div_produto, #div_familia {
			display: none;
		}

		div.fancy-select {
			font-size: 12px;
			color: #3E3E3D;
			font-weight: 400;
		}

		div.fancy-select select:focus + div.trigger {
			box-shadow: 0px 0px 0px transparent;
		}

		div.fancy-select div.trigger {
			cursor: pointer;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
			position: relative;
			background: #eeeeee;
			border: 1px solid #E2E0DF;
			width: 100%;
			height: 44px !important;
			padding: 10px 15px;
			border-radius: 3px;
			font-size: 12px;
			color: #3E3E3D;
			box-shadow: 0px 0px 0px transparent;

			transition: all 240ms ease-out;
			-webkit-transition: all 240ms ease-out;
			-moz-transition: all 240ms ease-out;
			-ms-transition: all 240ms ease-out;
			-o-transition: all 240ms ease-out;
		}

		div.fancy-select div.trigger::after {
			content: "";
			display: block;
			position: absolute;
			top: 50%;
			right: 15px;
			margin-top: -5px;
			background: url("imagens_ibramed/arrow.png") no-repeat scroll 0% 0% #eee !important;
		}

		div.fancy-select div.trigger.open {
			background: #eeeeee;
			border: 2px solid #bfbffc;
			color: #bfbffc;
			box-shadow: none;
		}

		div.fancy-select div.trigger.open:after {
			border-top-color: #E2E0DF;
		}

		div.fancy-select ul.options {
			width: 100%;
			list-style: none;
			margin: 0;
			position: absolute;
			top: 40px;
			left: 0;
			visibility: hidden;
			opacity: 0;
			z-index: 50;
			max-height: 200px;
			overflow: auto;
			background: #eeeeee;
			border-radius: 4px;
			border: 1px solid #E2E0DF;
			box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.4);
			padding-left: 0px;

			transition: opacity 300ms ease-out, top 300ms ease-out, visibility 300ms ease-out;
			-webkit-transition: opacity 300ms ease-out, top 300ms ease-out, visibility 300ms ease-out;
			-moz-transition: opacity 300ms ease-out, top 300ms ease-out, visibility 300ms ease-out;
			-ms-transition: opacity 300ms ease-out, top 300ms ease-out, visibility 300ms ease-out;
			-o-transition: opacity 300ms ease-out, top 300ms ease-out, visibility 300ms ease-out;
		}

		div.fancy-select ul.options li {
			padding: 8px 12px;
			color: #3E3E3D;
			cursor: pointer;
			white-space: nowrap;

			transition: all 150ms ease-out;
			-webkit-transition: all 150ms ease-out;
			-moz-transition: all 150ms ease-out;
			-ms-transition: all 150ms ease-out;
			-o-transition: all 150ms ease-out;
		}

		div.fancy-select ul.options li.selected {
			background: #eeeeee !important;
			color: #3e3e3d;
		}

		div.fancy-select ul.options li.hover {
			background: #5a90fc !important;
			color: #FFFFFF !important;
		}
	</style>

	<script>
		$(function() {
			$("select").fancySelect();

			$(".telefone").each(function() {
	            if ($(this).val().match(/^\(1\d\) 9/i)) {
	                $(this).mask("(00) 00000-0000", $(this).val());
	            } else {
	                $(this).mask("(00) 0000-0000", $(this).val());
	            }
	        });

	        $("#telefone").keypress(function() {
	            if ($(this).val().match(/^\(1\d\) 9/i)) {
	                $(this).mask("(00) 00000-0000");
	            } else {
	               $(this).mask("(00) 0000-0000");
	            }
	        });

	        var phoneMask = function(){
				if($(this).val().match(/^\(0/)){
	    			$(this).val('(');
	    			return;
	        	}
	    		if($(this).val().match(/^\([1-9][0-9]\) *[0-8]/)){
	    			$(this).mask('(00) 0000-0000');
	    		}else{
					$(this).mask('(00) 00000-0000');
	        	}
	    		$(this).keyup(phoneMask);
	    	};

	    	$('#celular').keyup(phoneMask);

	        $("input, textarea, select").blur(function() {
	        	var valor = $.trim($(this).val());

	        	if (valor.length > 0) {
	        		if ($(this).parents("div.form-group").hasClass("has-error")) {
	        			$(this).parents("div.form-group").removeClass("has-error");
	        		}
	        	}
	        });

			$("#enviar").click(function() {
				var btn      = $(this);
				var formData = $("#form_fale_conosco").serializeArray();

				var data = {};

				$("#form_fale_conosco").find("input, textarea, select").each(function() {
					var name  = $(this).attr("name");
					var value = $(this).val();

					data[name] = value;
				});

				data.ajax_enviar = true;

				$.ajax({
					url: "callcenter_cadastra_ibramed.php",
					type: "post",
					data: data,
					beforeSend: function() {
						$("div.input.erro").removeClass("erro");
						$("#msg_erro").html("").hide();
						$("#msg_sucesso").hide();
						$(btn).button("loading");
					}
				}).done(function(data) {
					data = JSON.parse(data);

					if (data.erro) {
						var msg_erro = [];

						$.each(data.erro.msg, function(key, value) {
							msg_erro.push(value);
						});

						$("#msg_erro").html("<span style='font-weight: bold;' >Desculpe!</span><br />"+msg_erro.join("<br />"));

						data.erro.campos.forEach(function(input) {
							$("input[name="+input+"], textarea[name="+input+"], select[name="+input+"]").parents("div.form-group").addClass("has-error");
						});

						$("#msg_erro").show();
					} else {
						if (typeof data.hd_chamado != "undefined") {
							$("#msg_sucesso").html("<span style='font-weight: bold;'>Obrigado!</span> Recebemos seu contato em breve retornaremos.<br />Protocolo: "+data.hd_chamado).show();
						} else {
							$("#msg_sucesso").html("<span style='font-weight: bold;'>Obrigado!</span> Recebemos seu contato em breve retornaremos.").show();
						}

						$("div.form-group").find("input, textarea, select").val("");
						$("#estado, #cidade, #hd_classificacao").trigger("update");
					}

					$(document).scrollTop(0);
					$(btn).button("reset");
				});
			});

			$("#departamento").on("change.fs", function() {
				var departamento = $(this).val();
				
				if (departamento == 'posvendas'){
					$("#div_natureza").show();
				}else{
					$("#div_natureza").hide();
					$("#natureza").val('');
    				$("#natureza").trigger("change");
				}
			});
		});
	</script>
</head>
<body>

<div class="container" >
	<h1 style="margin-left: 15px;" class='fale_conosco'>
		Fale conosco
	</h1>
	<p></p>
	<p style="margin-left: 15px;">
		<span class='span_icon'><i class="glyphicon glyphicon-earphone" style="color:#fff; font-size:1.0em;"></i></span>
		<span style="font-size:14px; font-weight:bold; color:#26357c;">+ 55 19 </span>
		<span style="font-size:18px; font-weight:bold; color:#26357c;">3817 9633</span>
	</p>
	<form id="form_fale_conosco" method="post" >
		<div id="mensagem_erro" style="display:none"></div>
		
		<div id="msg_erro" class="alert alert-danger" ></div>

		<div id="msg_sucesso" class="alert alert-success" ></div>

		<div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
			<input type="text" placeholder="Nome" class="form-control" id="nome" name="nome" />
		</div>

		<div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
			<input type="text" placeholder="E-mail" class="form-control" id="email" name="email" />
		</div>

		<div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
			<input type="text" placeholder="Telefone" class="form-control" id="telefone" class="telefone" name="telefone" />
		</div>
		
		<div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
			<select name='departamento' id='departamento'>
				<option selected="" value=''>Qual departamento você gostaria de contatar?</option>
				<option value="administrativo">Administrativo</option>
				<option value="comercial">Comercial</option>
				<option value="duvidas">Dúvidas</option>
				<option value="marketing">Marketing</option>
				<option value="rh">RH</option>
				<option value="posvendas">Pós vendas</option>
				<option value="vendas">Vendas</option>
				<option value="outros">Outros departamentos</option>		
			</select>
		</div>
		
		<div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" style="display: none;" id="div_natureza">
			<select name='natureza' id='natureza'>
				<option value='' selected="">Selecione o motivo do contato</option>
				<?php
					$sql= " SELECT natureza, nome, descricao 
							FROM tbl_natureza WHERE fabrica = $login_fabrica 
							AND ativo IS TRUE ORDER BY descricao";
					$res = pg_query($con,$sql);

					for ($i = 0; $i < pg_num_rows($res); $i++) {
						$natureza  			= pg_fetch_result($res,$i,'natureza');
						$aux_nome_natureza 	= pg_fetch_result($res,$i,'nome');
						$descricao 			= pg_fetch_result($res,$i,'descricao');
						echo " <option value='".$aux_nome_natureza."' ".($aux_nome_natureza == $nome_natureza ? "selected='selected'" : '').">$descricao</option>";
					}
				?>
			</select>
		</div>
		
		<div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
			<textarea class="form-control" placeholder="Mensagem" name="mensagem" rows="6" ></textarea>
		</div>

		<div class="col-xs-12 col-sm-4 col-md-4 col-lg-4" >
			<button type="button" id="enviar" class="btn btn-lg" data-loading-text="Enviando..." >Enviar</button>
		</div>
	</form>
</div>

<br /><br />

</body>
</html>

