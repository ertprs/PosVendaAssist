<?php

	include '../../dbconfig.php';
	include '../../includes/dbconnect-inc.php';
	include '../../funcoes.php';
	include '../../helpdesk/mlg_funciones.php';

	function removeString($string){

		$string = preg_replace("/[^0-9]/","", $string);

		return $string;

	}

	function formatDate($date){

		list($dia, $mes, $ano) = explode("/", $date);
		$date = $ano."/".$mes."/".$dia;

		return $date;

	}

	$array_estado = array(
		'AC' => 'AC - Acre',			
		'AL' => 'AL - Alagoas',	
		'AM' => 'AM - Amazonas',			
		'AP' => 'AP - Amapá', 
		'BA' => 'BA - Bahia',			
		'CE' => 'CE - Ceara',		
		'DF' => 'DF - Distrito Federal',	
		'ES' => 'ES - EspÃ­to Santo',
		'GO' => 'GO - Goiás',			
		'MA' => 'MA - Maranhão',	
		'MG' => 'MG - Minas Gerais',		
		'MS' => 'MS - Mato Grosso do Sul',
		'MT' => 'MT - Mato Grosso',	
		'PA' => 'PA - Pará',		
		'PB' => 'PB - Paraíba',			
		'PE' => 'PE - Pernambuco',
		'PI' => 'PI - Piauí',			
		'PR' => 'PR - Paraná',	
		'RJ' => 'RJ - Rio de Janeiro',	
		'RN' => 'RN - Rio Grande do Norte',
		'RO' => 'RO - Rondônia',		
		'RR' => 'RR - Roraima',	
		'RS' => 'RS - Rio Grande do Sul', 
		'SC' => 'SC - Santa Catarina',
		'SE' => 'SE - Sergipe',		
		'SP' => 'SP - São Paulo',	
		'TO' => 'TO - Tocantins'
	);

	if(isset($_POST['est'])){

		$est = $_POST['est'];

		$sql = "SELECT cidade FROM tbl_ibge WHERE estado = '$est'";
		$res = pg_query($con, $sql);

		if(pg_num_rows($res) > 0){

			echo "<option value=''>Selecio uma Cidade</option>";

			for($i = 0; $i < pg_num_rows($res); $i++){
				echo "<option value='".pg_fetch_result($res, $i, cidade)."'>".pg_fetch_result($res, $i, cidade)."</option>";
			}

		}else{
			echo "<option value=''>Nenhuma cidade encontrada</option>";
		}

		exit;

	}

	if ($_POST["buscaCidade"] == true) {
		$estado = strtoupper($_POST["estado"]);

		if (strlen($estado) > 0) {
			$sql = "SELECT cidade, cidade_pesquisa FROM tbl_ibge WHERE estado = '{$estado}' ORDER BY cidade ASC";
			$res = pg_query($con, $sql);
			$rows = pg_num_rows($res);

			if ($rows > 0) {
				$cidades = array();

				for ($i = 0; $i < $rows; $i++) { 
					$cidades[$i] = array(
						"cidade" => utf8_encode(pg_fetch_result($res, $i, "cidade")),
						"cidade_pesquisa" => utf8_encode(strtoupper(pg_fetch_result($res, $i, "cidade_pesquisa"))),
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

	/* Fale Conosco */

	if($_POST['fale_conosco'] == "ok"){

		$nome 			= utf8_decode($_POST['nome']);
		$email 			= utf8_decode($_POST['email']);
		$area_interesse = utf8_decode($_POST['area_interesse']);
		$telefone 		= $_POST['telefone'];
		$cep 			= $_POST['cep'];
		$endereco 		= utf8_decode($_POST['endereco']);
		$numero 		= utf8_decode($_POST['numero']);
		$bairro 		= utf8_decode($_POST['bairro']);
		$complemento 	= utf8_decode($_POST['complemento']);
		$estado 		= utf8_decode($_POST['estado']);
		$cidade 		= utf8_decode($_POST['cidade']);
		$mensagem 		= utf8_decode($_POST['mensagem']);

		$complemento = (strlen($complemento) == 0) ? "Não Informado" : $complemento;

		/*
		Seleciona email para o pós-venda
		*/
		$sql_email_admin = "SELECT email, admin FROM tbl_admin WHERE fale_conosco = 't' AND fabrica = 137";
		$res_email_admin = pg_query($con, $sql_email_admin);

		for($i = 0; $i < pg_num_rows($res_email_admin); $i++){
			$admin_fale_conosco = pg_fetch_result($res_email_admin, $i, 'admin');
			$emails_admins[$admin_fale_conosco] = pg_fetch_result($res_email_admin, $i, 'email');
		}

		$admin_fale_conosco = array_rand($emails_admins, 1);

		switch($area_interesse){
			case "vendas" 					: $email_admin = "vendas@arge.com.br"; 				$assunto_email = "Vendas";						break;
			case "pos_venda_sac" 			: $email_admin = $emails_admins[$admin_fale_conosco]; $assunto_email = "Pós Venda / SAC";				break;
			case "comercio_exterior" 		: $email_admin = "fernanda.couto@arge.com.br"; 		$assunto_email = "Comércio Exterior";			break;
			case "marketing" 				: $email_admin = "elaine.nebel@arge.com.br"; 		$assunto_email = "Marketing";					break;
			case "logistica" 				: $email_admin = "paulo.cruz@arge.com.br"; 			$assunto_email = "Logística";					break;
			case "projeto_desenvolvimento" 	: $email_admin = "nilton.bianchi@arge.com.br"; 		$assunto_email = "Projeto e Desenvolvimento";	break;
			case "financeiro" 				: $email_admin = "marilda.cardoso@arge.com.br"; 	$assunto_email = "Financeiro"; 					break;
			case "compras" 					: $email_admin = "compras@arge.com.br"; 			$assunto_email = "Compras";						break;
			case "recursos_humanos" 		: $email_admin = "jailson@arge.com.br"; 			$assunto_email = "Recursos Humanos";			break;
			case "ti" 						: $email_admin = "marcelo.leite@arge.com.br"; 		$assunto_email = "Tecnologia da Informação";	break;
			case "contabilidade" 			: $email_admin = "luisvaldo.penariol@arge.com.br"; 	$assunto_email = "Contabilidade"; 				break;
			case "fiscal" 					: $email_admin = "jaqueline.godoy@arge.com.br"; 	$assunto_email = "Fiscal";						break;
			case "controle_portaria" 		: $email_admin = "portaria.arge@arge.com.br"; 		$assunto_email = "Controle Portaria";			break;
		}

		$tipo = "producao"; // producao - teste

	    $email_admin 	= ($tipo == "teste") ? "guilherme.silva@telecontrol.com.br,guilherme.monteiro@telecontrol.com.br" : $email_admin;
	    $remetente   	= "Aviso Fale Conosco - Arge <suporte@telecontrol.com.br>";
		$msg_email   	= "
			Nome: 				$nome <br /> \n
			Email: 				$email <br /> \n
			Área de Interesse: 	$assunto_email <br /> \n
			Telefone: 			$telefone <br /> \n
			CEP: 				$cep <br /> \n
			Endereço: 			$endereco <br /> \n
			Número: 			$numero <br /> \n
			Complemento: 		$complemento <br /> \n
			Bairro: 			$bairro <br /> \n
			Estado: 			$estado <br /> \n
			Cidade: 			$cidade <br /> <br /> \n
			Mensagem: 			$mensagem
		";

		$headers  = "MIME-Version: 1.0 \r\n";
		$headers .= "Content-type: text/html; charset=iso-8859-1 \r\n";
		$headers .= "From: $remetente \r\n";
		$headers .= "Reply-to: $email_admin \r\n";

		mail($email_admin, utf8_encode($assunto_email), $msg_email, $headers);

		/* Abre um atendimento */
		if($area_interesse == "pos_venda_sac"){

			$sql = "INSERT INTO tbl_hd_chamado (admin, atendente, data, fabrica_responsavel, fabrica, titulo, status,categoria) VALUES ($admin_fale_conosco, $admin_fale_conosco, CURRENT_TIMESTAMP, 137, 137, 'Atemdimento Fale Conosco - Site', 'Aberto','reclamacao_produto') RETURNING hd_chamado";
			$res = pg_query($con, $sql);

			if(strlen(pg_last_error()) > 0){
				$erro = pg_last_error();
			}

			$hd_chamado = pg_fetch_result($res, 0, 'hd_chamado');

			/* Seleciona Cidade */

			$sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER('{$estado}')";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				$id_cidade = pg_fetch_result($res, 0, "cidade");
			} else {
				$sql = "SELECT cidade, estado FROM tbl_ibge WHERE UPPER(TO_ASCII(cidade, 'LATIN9')) = UPPER(TO_ASCII('{$cidade}', 'LATIN9')) AND UPPER(estado) = UPPER('{$estado}')";
				$res = pg_query($con, $sql);

				if (pg_num_rows($res) > 0) {
					$cidade_ibge        = pg_fetch_result($res, 0, "cidade");
					$cidade_estado_ibge = pg_fetch_result($res, 0, "estado");

					$sql = "INSERT INTO tbl_cidade (
								nome, estado
							) VALUES (
								'{$cidade_ibge}', '{$cidade_estado_ibge}'
							) RETURNING cidade";
					$res = pg_query($con, $sql);

					$id_cidade = pg_fetch_result($res, 0, "cidade");
				}
			}

			if(strlen(pg_last_error()) > 0){
				$erro = pg_last_error();
			}

			/* Fim - Seleciona Cidade */

			$campos_desc = "";
			$campos_vals = "";

			if(strlen($complemento) > 0){
				$campos_desc .= ", complemento";
				$campos_vals .= ", '$complemento'";
			}

			$cep = str_replace("-", "", $cep);

			$sql = "INSERT INTO tbl_hd_chamado_extra 
					(hd_chamado,
					nome,
					endereco,
					numero,
					bairro,
					cep,
					fone,
					email,
					cidade 
					$campos_desc) 
				VALUES 
					($hd_chamado,
					'$nome',
					'$endereco', 
					'$numero',
					'$bairro',
					'$cep',
					'$telefone',
					'$email',
					$id_cidade 
					$campos_vals)
			";

			$res = pg_query($con, $sql);

			if(strlen(pg_last_error()) > 0){
				$erro = pg_last_error();
			}

			$sql = "INSERT INTO tbl_hd_chamado_item (hd_chamado, admin, comentario) VALUES ($hd_chamado, $admin_fale_conosco, '$mensagem')";		
			$res = pg_query($con, $sql);

			if(strlen(pg_last_error()) > 0){
				$erro = pg_last_error();
			}

			echo (strlen($erro) == 0) ? "ok" : "fail";

		}else{
			echo "ok";
		}

		exit;

	}

?>

<!DOCTYPE>
<html>
	<head>

		<meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
		<meta name="language" content="pt-br" />

		<script type="text/javascript" src="../../js/jquery-1.8.3.min.js"></script>
		<script type="text/javascript" src="../../admin/js/jquery.mask.js"></script>

		<style>

			body{
				font-family: Trebuchet MS, "Trebuchet MS", Arial, Helvetica, sans-serif;
				font-size: 12px;
				color: #5a5a5a;
			}

			input{
				padding: 7px 2px;
				border: 1px solid #ccc;
				background-color: #fff;
			}

			input[type="submit"]{
				color: #fff;
				border-radius: 4px;
				background-color: #377B9E;
				padding-right: 15px;
				padding-left: 15px;
				font-weight: bold;
			}

			input[type="submit"]:hover{
				cursor: pointer;
				background-color: #065B86;
			}

			select{
				padding: 7px 2px;
				border: 1px solid #ccc;
				background-color: #fff;
			}

			textarea{
				padding: 7px 2px;
				border: 1px solid #ccc;
				background-color: #fff;
				font: 12px arial;
			}

			.tab{
				width: 790px;
				padding: 25px;
				background-color: #fff; 
			}

			.left{
				width: 48%;
				float: left;
				margin-bottom: 10px;
			}

			.right{
				width: 48%;
				float: right;
				margin-bottom: 10px;
			}

			.min-box{
				float: left;
				margin-bottom: 10px;
			}

			.box_envio{
				width: 99%;
				padding-top: 12px;
				padding-bottom: 12px;
				text-align: center;
				background-color: #CEF6CE;
				font-weight: bold;
				color: #0B610B;
				font-size: 13px;
			}

			.box_erro_produto{
				width: 98%;
				padding-top: 12px;
				padding-bottom: 12px;
				text-align: center;
				background-color: #F8E0E6;
				font-weight: bold;
				color: #ff0000;
				margin-bottom: 20px;
			}

		</style>

		<script type="text/javascript">

			$(function(){

				$("#cep").mask("99999-999");

				$('#telefone').keypress(function(){
	             	if( $(this).val().match(/^\(1\d\) 9/i)){
	                 	$(this).mask('(00) 00000-0000'); /* 9 Dígito */
	             	}else{
	                	$(this).mask('(00) 0000-0000');  /* Máscara default */
	             	}
	         	});

				<?php

					$array_ids = array(
						"nome"					,
						"email"					,
						"area_interesse"		,
						"telefone"				,
						"cep"					,
						"endereco"				,
						"numero"				,
						"bairro"				,
						"complemento"			,
						"estado"				,
						"cidade"				,
						"mensagem"				
					);

					for ($i = 0; $i < count($array_ids); $i++){
						
						echo "
							$('#".$array_ids[$i]."').change(function(){
								$('#".$array_ids[$i]."').css({'border' : '1px solid #e0e0e0'});
							});
						";

					};

				?>

				$('#estado').change(function(){
					var est = $(this).val();

					$.ajax({
						url 		: "<?php echo $_SERVER['PHP_SELF']; ?>",
						type 		: "POST",
						data 		: { est : est },
						beforeSend 	: function(){
							$('#cidade').html('<option>carregando cidades...</option>');
						},
						complete 	: function(cidade){
							cidade = cidade.responseText;
							$('#cidade').html('');
							$('#cidade').append(cidade);
						}
					});

				});

				$('#cep').blur(function(){

					var cep = $(this).val();

					$.ajax({
						url  : "../../admin/ajax_cep.php",
						type : "GET",
						data : { cep : cep },
						complete: function(data){

							var endereco = new Array();

							endereco = data.responseText.split(';');

							if(endereco[0] == "ok"){
								$('#endereco').val(endereco[1]);
								$('#bairro').val(endereco[2]);
								// $('#cidade_reclamacao').val(endereco[3]);
								$('#estado').val(endereco[4]);
								buscaCidade(endereco[4], endereco[3]);
							}

						}
					});

				});

				$('#estado').change(function(){

					var estado = $(this).val();

					if(estado.length > 0){

						buscaCidade(estado);

					}else{

						$("#cidade > option[rel!=default]").remove();

					}

				});

			});

			function buscaCidade (estado, cidade) {
				$.ajax({
					async: false,
					url: "<?php echo $_SERVER['PHP_SELF']; ?>",
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

			/* Outros - Fale Conosco */

			function enviarFaleConosco(){

				var nome 			= $('#nome').val();
				var email 			= $('#email').val();
				var area_interesse 	= $('#area_interesse').val();
				var telefone 		= $('#telefone').val();
				var cep 			= $('#cep').val();
				var endereco 		= $('#endereco').val();
				var numero 			= $('#numero').val();
				var bairro 			= $('#bairro').val();
				var complemento 	= $('#complemento').val();
				var estado 			= $('#estado').val();
				var estado 			= $('#estado').val();
				var cidade 			= $('#cidade').val();
				var mensagem 		= $('#mensagem').val();

				if(nome == ""){
					alert('O campo Nome é obrigatório');
					$('#nome').css({'border' : '1px solid #ff0000'});
					return;
				}

				if(email == ""){
					alert('O campo Email é obrigatório');
					$('#email').css({'border' : '1px solid #ff0000'});
					return;
				}

				if(email.indexOf("@") < 1){
					alert('O Email não é valido');
					$('#email').css({'border' : '1px solid #ff0000'});
					return;
				}	

				if(area_interesse == ""){
					alert('O campo Area de Interesse é obrigatório');
					$('#area_interesse').css({'border' : '1px solid #ff0000'});
					return;
				}	

				if(telefone == ""){
					alert('O campo Telefone é obrigatório');
					$('#telefone').css({'border' : '1px solid #ff0000'});
					return;
				}

				if(cep == ""){
					alert('O campo CEP é obrigatório');
					$('#cep').css({'border' : '1px solid #ff0000'});
					return;
				}

				if(endereco == ""){
					alert('O campo Endereço é obrigatório');
					$('#endereco').css({'border' : '1px solid #ff0000'});
					return;
				}

				if(numero == ""){
					alert('O campo Número é obrigatório');
					$('#numero').css({'border' : '1px solid #ff0000'});
					return;
				}

				if(bairro == ""){
					alert('O campo Bairro é obrigatório');
					$('#bairro').css({'border' : '1px solid #ff0000'});
					return;
				}

				if(estado == ""){
					alert('O campo Estado é obrigatório');
					$('#estado').css({'border' : '1px solid #ff0000'});
					return;
				}

				if(cidade == ""){
					alert('O campo Cidade é obrigatório');
					$('#cidade').css({'border' : '1px solid #ff0000'});
					return;
				}

				if(mensagem == ""){
					alert('O campo Mensagem é obrigatório');
					$('#mensagem').css({'border' : '1px solid #ff0000'});
					return;
				}

				$.ajax({
					url 	: "<?php echo $_SERVER['PHP_SELF']; ?>",
					type 	: "POST",
					data 	: {
						fale_conosco 	: "ok",
						nome 			: nome,
						email 			: email,
						area_interesse 	: area_interesse,
						telefone 		: telefone,
						cep 			: cep,
						endereco 		: endereco,
						numero 			: numero,
						bairro 			: bairro,
						complemento 	: complemento,
						estado 			: estado,
						cidade 			: cidade,
						mensagem 		: mensagem
					},
					beforeSend: function(){
						$('#enviando').text('Enviado, aguarde...');
					},
					complete: function(data){

						$('#enviando').text('');

						data = data.responseText;

						if(data == "ok"){
							$('#box_envio').show();
						}

						$('#nome').val('');
						$('#email').val('');
						$('#area_interesse').val('');
						$('#telefone').val('');
						$('#cep').val('');
						$('#endereco').val('');
						$('#numero').val('');
						$('#bairro').val('');
						$('#complemento').val('');
						$('#estado').val('');
						$('#estado').val('');
						$('#cidade').val('');
						$('#mensagem').val('');

						setTimeout(function(){
							$('#box_envio').hide();
						}, 10000);

					}
				});	

			}

		</script>

	</head>
	<body>

		<div class="tab">

			<div class="left">
				<strong>NOME*</strong> <br />
				<input type="text" name="nome" id="nome" style='width: 98%;' />
			</div>

			<div class="right">
				<strong>EMAIL*</strong> <br />
				<input type="text" name="email" id="email" style='width: 98%;' />
			</div>

			<!-- qbr -->

			<div class="left">
				<strong>AREA DE INTERESSE*</strong> <br />
				<select name="area_interesse" id="area_interesse" style='width: 98%;'>
					<option value=""></option>
					<option value="vendas">Vendas</option>
					<option value="pos_venda_sac">Pós Vendas / SAC</option>
					<option value="comercio_exterior">Comércio Exterior</option>
					<option value="marketing">Marketing</option>
					<option value="logistica">Logística</option>
					<option value="projeto_desenvolvimento">Projeto e Desenvolvimento</option>
					<option value="financeiros">Financeiro</option>
					<option value="compras">Compras</option>
					<option value="recursos_humanos">Recursos Humanos</option>
					<option value="ti">Tecnologia da Informação</option>
					<option value="contabilidade">Contabilidade</option>
					<option value="fiscal">Fiscal</option>
					<option value="controle_portaria">Controle de Portaria</option>
				</select>
			</div>

			<div class="right">
				<strong>TELEFONE*</strong> <br />
				<input type="text" name="telefone" id="telefone" style='width: 98%;' />
			</div>

			<br />

			<!-- qbr -->

			<div class="min-box" style="width: 100%">

				<div class="min-box" style="width: 25%">
					<strong>CEP*</strong> <br />
					<input type="text" name="cep" id="cep" style='width: 90%;' />
				</div>

				<div class="min-box" style="width: 60%">
					<strong>ENDEREÇO*</strong> <br />
					<input type="text" name="endereco" maxlength='60' id="endereco" style='width: 95%;' />
				</div>

				<div class="min-box" style="width: 15%">
					<strong>NÚMERO*</strong> <br />
					<input type="text" name="numero" id="numero" style='width: 95%;' />
				</div>

			</div>

			<!-- qbr -->

			<div class="min-box" style="width: 100%">

				<div class="min-box" style="width: 35%">
					<strong>BAIRRO*</strong> <br />
					<input type="text" name="bairro" id="bairro" maxlength='60' style='width: 90%;' />
				</div>

				<div class="min-box" style="width: 65%">
					<strong>COMPLEMENTO</strong> <br />
					<input type="text" name="complemento" maxlength='40' id="complemento" style='width: 99%;' />
				</div>

			</div>

			<!-- qbr -->

			<div class="right">
				<strong>CIDADE*</strong> <br />
				<select name="cidade" id="cidade" style='width: 98%;'>
					<option value=""></option>
				</select>
			</div>

			<div class="left">
				<strong>ESTADO*</strong> <br />
				<select name="estado" id="estado" style='width: 98%;'>
					<option value=""></option>
					<?php
						foreach($array_estado as $key => $value){
							echo "<option value='".$key."'>".$value."</option>";
						}
					?>
				</select>
			</div>

			<!-- qbr -->

			<div class="min-box" style="width: 100%">

				<strong>*MENSAGEM</strong> <br />
				<textarea name="mensagem" id="mensagem" rows="6" style="width: 99%"></textarea> <br /> <br />

			</div>

			<!-- qbr -->

			<div class="min-box" style="width: 100%">

				<input type="submit" value="ENVIAR &nbsp; &nbsp;" onclick="enviarFaleConosco();" /> <span style="font-size: 35px; font-weight: bold; z-index: 10; margin-left: -35px !important; color: #fff; position: absolute; border: 0px; margin-top: -6px; cursor: pointer;" onclick="enviarFaleConosco();" >&raquo;</span> <br /> <br />
			
				<em id="enviando"></em>

				<div class="box_envio" id="box_envio" style="display: none;">Mensagem Enviada com Sucesso!</div>

			</div>

			<div style="clear: both;"></div>

		</div>
		
	</body>
</html>

