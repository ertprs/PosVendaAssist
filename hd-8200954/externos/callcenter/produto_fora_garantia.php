<?php

include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';
include '../../funcoes.php';
include '../../helpdesk/mlg_funciones.php';

function removeString($string){

		$string = preg_replace("/[^0-9]/","", $string);
		return $string;
	}

$sql = "SELECT produto,descricao,referencia FROM tbl_produto WHERE ativo is true AND off_line is false AND fabrica_i = 122 ORDER BY descricao";
$res_produtos = pg_query($con,$sql);
$tipo = "producao";  
//$tipo="teste";

if (isset($_POST['ajax']) && $_POST['ajax']== 'ok'){


	$array_campos_adicionais["origem_reclamacao"] =  array("fora_garantia");
	$array_campos_adicionais = str_replace("\\", "\\\\", json_encode($array_campos_adicionais));

	if(isset($_POST['consumidor']) && $_POST['consumidor'] == "ok"){

		$nome 			= utf8_decode($_POST['nome']);
		$telefone1 		= removeString($_POST['telefone1']);
		$telefone2 		= removeString($_POST['telefone2']);
		$produto 		= $_POST['produto'];
		$produto_desc	= utf8_decode($_POST['produto_desc']);
		$mensagem 		= utf8_decode($_POST['mensagem']);

		$tipo = "teste"; // producao - teste 
		$login_admin = ($tipo == "teste") ?  6573 : 6219  ;
		$res = pg_query($con, "BEGIN TRANSACTION");	
		
		$sql = "INSERT INTO tbl_hd_chamado (
							admin              ,
							data               ,
							status             ,
							atendente          ,
							fabrica_responsavel,
							titulo             ,
							categoria          ,
							fabrica
						) VALUES (
							$login_admin      		,
							current_timestamp 		,
							'Aberto' 				,
							$login_admin      		,
							122    					,
							'produto_fora_garantia' ,	
							'reclamacao_produto',
							122	
						)";
		$res        = pg_query($con,$sql);
		$msg_erro  .= pg_last_error($con);
		$res        = pg_query ($con,"SELECT CURRVAL ('seq_hd_chamado')");
		$hd_chamado = pg_fetch_result($res,0,0);
		
		$sql = "INSERT INTO tbl_hd_chamado_extra (
							hd_chamado ,
							nome       ,
							fone       ,
							fone2      ,
							produto    ,
							array_campos_adicionais
						) VALUES (
							$hd_chamado     ,
							'$nome'       	,
							'$telefone1'    ,
							'$telefone2'    ,
							$produto    	,
							'$array_campos_adicionais'
						) 
			";
		//echo $sql;
		$res = pg_query($con, $sql);
		$msg_erro .= pg_last_error($con);
		if (strlen($msg_erro)== 0 AND strlen(mensagem)>0 ){
			$sql = "INSERT INTO tbl_hd_chamado_item (
									hd_chamado ,
									data 		,
									comentario 	,
									admin 		,
									interno 	,
									status_item	
								) VALUES (
									$hd_chamado 		,
									current_timestamp 	,
									'$mensagem' 		,
									$login_admin 		,
									false 				,
									'Aberto'
								)";
			$res = pg_query($con, $sql);
			$msg_erro .= pg_last_error($con);

		}
		if (strlen($msg_erro) == 0) {
			$res = pg_query($con,"COMMIT TRANSACTION");
			$email_admin 	= ($tipo == "teste") ? "joao.junior@telecontrol.com.br" : "wellington.campos@telecontrol.com.br";
		    $remetente   	= "Produto Fora de Garantia CONSUMIDOR - WURTH <no_reply@telecontrol.com.br>";
			$assunto_email 	= "Atendimento $hd_chamado ";
			$msg_email   	= "
				Nome: $nome <br /> \n
				Telefone: $telefone1 <br /> \n
				Telefone 2 : $telefone2 <br /> \n
				Produto: $produto_desc <br /> \n
				Mensagem: $mensagem
			";

			$headers  = "MIME-Version: 1.0 \r\n";
			$headers .= "Content-type: text/html; charset=iso-8859-1 \r\n";
			$headers .= "From: $remetente \r\n";
			$headers .= "Reply-to: $email_admin \r\n";

			mail($email_admin, utf8_encode($assunto_email), $msg_email, $headers);
			
		}else{
			$res = pg_query($con, "ROLLBACK TRANSACTION");
			exit;
		}
	}
	if(isset($_POST['assist']) && $_POST['assist'] == "ok"){
		
		$nome_consumidor		= utf8_decode($_POST['nome_consumidor']);
		$telefone_consumidor 	= removeString($_POST['telefone_consumidor']);
		$telefone2_consumidor 	= removeString($_POST['telefone2_consumidor']);
		$razao_social 			= utf8_decode($_POST['razao_social']);
		$cnpj 					= removeString($_POST['cnpj']);
		$produto 				= $_POST['produto'];
		$produto_desc			= utf8_decode($_POST['produto_desc']);
		$telefone_assist 		= removeString($_POST['telefone_assist']);
		$contato 				= utf8_decode($_POST['contato']);
		$mensagem 				= utf8_decode($_POST['mensagem']);

		$login_admin = ($tipo == "teste") ?  6573 : 6219  ;
		$res = pg_query($con, "BEGIN TRANSACTION");
		$sql = "INSERT INTO tbl_hd_chamado (
							admin              ,
							data               ,
							status             ,
							atendente          ,
							fabrica_responsavel,
							titulo             ,
							categoria          ,
							fabrica
						) VALUES (
							$login_admin      		,
							current_timestamp 		,
							'Aberto'				,
							$login_admin      		,
							122    					,
							'produto_fora_garantia' ,	
							'reclamacao_produto',
							122	
						)";
		$res        = pg_query($con,$sql);
		$msg_erro  .= pg_last_error($con);
		$res        = pg_query ($con,"SELECT CURRVAL ('seq_hd_chamado')");
		$hd_chamado = pg_fetch_result($res,0,0);
		
		$sql = "INSERT INTO tbl_hd_chamado_extra (
							hd_chamado ,
							nome       ,
							posto_nome ,
							fone       ,
							fone2      ,
							produto    ,
							contato_nome ,
							defeito_reclamado_descricao,
							array_campos_adicionais
						) VALUES (
							$hd_chamado     ,
							'$nome_consumidor'       	,
							E'CNPJ : $cnpj - Razão :$razao_social' ,
							'$telefone_consumidor'    ,
							'$telefone2_consumidor'    ,
							$produto    	,
							'$contato'		,
							'$mensagem'		,
							'$array_campos_adicionais'
						) ";
		$res = pg_query($con, $sql);
		$msg_erro .= pg_last_error($con);

		if (strlen($msg_erro)== 0 AND strlen(mensagem)>0 ){
			$sql = "INSERT INTO tbl_hd_chamado_item (
									hd_chamado ,
									data 		,
									comentario 	,
									admin 		,
									interno 	,
									status_item	
								) VALUES (
									$hd_chamado 		,
									current_timestamp 	,
									E'$mensagem' 		,
									$login_admin 		,
									false 				,
									'Aberto'
								)";
			$res = pg_query($con, $sql);
			$msg_erro .= pg_last_error($con);

		}
		if (strlen($msg_erro) == 0) {
			$res = pg_query($con,"COMMIT TRANSACTION");
			$email_admin 	= ($tipo == "teste") ? "joao.junior@telecontrol.com.br" : "wellington.campos@telecontrol.com.br";
		    $remetente   	= "Produto Fora de Garantia Assistência - WURTH <no_reply@telecontrol.com.br>";
			$assunto_email 	= "Atendimento $hd_chamado ";
			$msg_email   	= "
				Nome Consumidor: $nome_consumidor <br /> \n
				Telefone Consumidor: $telefone_consumidor <br /> \n
				Telefone 2 Consumidor: $telefone2_consumidor <br /> \n
				Razão Social: $razao_social <br /> \n
				CNPJ: $cnpj <br /> \n
				Produto: $produto_desc <br /> \n
				Telefone Assistência: $telefone_assist <br /> \n
				Contato: $contato <br /> \n
				Mensagem: $mensagem
			";

			$headers  = "MIME-Version: 1.0 \r\n";
			$headers .= "Content-type: text/html; charset=iso-8859-1 \r\n";
			$headers .= "From: $remetente \r\n";
			$headers .= "Reply-to: $email_admin \r\n";

			mail($email_admin, utf8_encode($assunto_email), $msg_email, $headers);
			
		}else{
			$res = pg_query($con, "ROLLBACK TRANSACTION");
			exit;
		}
	}
	if(isset($_POST['revenda']) && $_POST['revenda'] == "ok"){

		$nome 					= utf8_decode($_POST['nome']);
		$telefone1 				= removeString($_POST['telefone1']);
		$nome_consumidor 		= $_POST['nome_consumidor'];
		$telefone_consumidor 	= removeString($_POST['telefone_consumidor']);
		$assist 				= $_POST['assist'];
		$produto 				= $_POST['produto'];
		$produto_desc			= utf8_decode($_POST['produto_desc']);
		$mensagem 				= utf8_decode($_POST['mensagem']);

		$tipo = "teste"; // producao - teste 
		$login_admin = ($tipo == "teste") ?  6573 : 6219  ;
		$res = pg_query($con, "BEGIN TRANSACTION");

		$sql = "INSERT INTO tbl_hd_chamado (
							admin              ,
							data               ,
							status             ,
							atendente          ,
							fabrica_responsavel,
							titulo             ,
							categoria          ,
							fabrica 		   
						) VALUES (
							$login_admin      		,
							current_timestamp 		,
							'Aberto' 					,
							$login_admin      		,
							122    					,
							'produto_fora_garantia' ,
							'reclamacao_produto',
							122						
						)";
		$res        = pg_query($con,$sql);
		$msg_erro  .= pg_last_error($con);
		$res        = pg_query ($con,"SELECT CURRVAL ('seq_hd_chamado')");
		$hd_chamado = pg_fetch_result($res,0,0);
		$sql = "INSERT INTO tbl_hd_chamado_extra (
							hd_chamado ,
							posto_nome ,
							nome       ,
							revenda_nome,
							fone       ,
							produto    ,
							defeito_reclamado_descricao ,
							array_campos_adicionais
						) VALUES (
							$hd_chamado     ,
							'$nome'       	,
							'$nome_consumidor'       	,
							'$assist'       	,
							'$telefone_consumidor'    ,
							$produto    	,
							'$mensagem',
							'$array_campos_adicionais'
						) ";
		$res = pg_query($con, $sql);
		$msg_erro .= pg_last_error($con);
		if (strlen($msg_erro)== 0 AND strlen(mensagem)>0 ){
			$sql = "INSERT INTO tbl_hd_chamado_item (
									hd_chamado ,
									data 		,
									comentario 	,
									admin 		,
									interno 	,
									status_item	
								) VALUES (
									$hd_chamado 		,
									current_timestamp 	,
									'$mensagem
									Posto: $assist 
									Representante: $nome ' 		,
									$login_admin 		,
									false 				,
									'Aberto'
								)";
			$res = pg_query($con, $sql);
			$msg_erro .= pg_last_error($con);
		}

		if (strlen($msg_erro) == 0) {
			$res = pg_query($con,"COMMIT TRANSACTION");
			$email_admin 	= ($tipo == "teste") ? "joao.junior@telecontrol.com.br" : "wellington.campos@telecontrol.com.br";
		    $remetente   	= "Produto Fora de Garantia Representante- WURTH <no_reply@telecontrol.com.br>";
			$assunto_email 	= "Atendimento $hd_chamado";
			$msg_email   	= "
				Nome: $nome <br /> \n
				Telefone: $telefone1 <br /> \n
				Nome Consumidor: $nome_consumidor <br /> \n
				Telefone Consumidor: $telefone_consumidor <br /> \n
				Assitência: $assist <br /> \n
				Produto: $produto_desc <br /> \n
				Mensagem: $mensagem
			";

			$headers  = "MIME-Version: 1.0 \r\n";
			$headers .= "Content-type: text/html; charset=iso-8859-1 \r\n";
			$headers .= "From: $remetente \r\n";
			$headers .= "Reply-to: $email_admin \r\n";

			mail($email_admin, utf8_encode($assunto_email), $msg_email, $headers);

		}else{
			$res = pg_query($con, "ROLLBACK TRANSACTION");
			exit;
		}
	}
	echo "ok";
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

		* {
			margin: 0;
			padding: 0;
		}
		body {
			background: repeat-x fixed 0 0 #fff;
			color: #000;
			font: 12px/18px Arial,Helvetica,sans-serif;
			overflow-y: scroll;
		}
		input {
			font: 12px Arial,Helvetica,sans-serif;
		}
		#box {
			background: none repeat scroll 0 0 #fff;
			float: left;
			padding: 0 9px 0 10px;
			width: 974px;
		}
		h1 {
			color: #fff;
			float: left;
			font: bold 22px "Arial",Helvetica,sans-serif;
			margin: 34px 0 0 40px;
		}
		.h1 {
			color: #605d5c;
			font: bold 12pt "Arial",Helvetica,sans-serif;
			margin: 17px 0 14px;
			padding: 0;
			width: 100%;
		}
		.h2 {
			color: #605d5c;
			font: bold 12pt "Arial",Helvetica,sans-serif;
			margin: 0 0 14px;
			padding: 0;
			width: 100%;
		}
		h2 {
			color: #636363;
			float: left;
			font: bold 16px Arial,Helvetica,sans-serif;
			text-align: center;
			width: 214px;
		}
		h3 {
			color: #000;
			float: left;
			font: 16px Arial,Helvetica,sans-serif;
			margin: 0;
			padding: 4px 3px;
		}
		h4 {
			color: #000;
			float: left;
			font: 12px Arial,Helvetica,sans-serif;
			margin: 0;
			padding: 2px 3px;
		}
		a {
			color: #cc0001;
			text-decoration: none;
		}
		a:visited {
			color: #cc0001;
			text-decoration: none;
		}
		a:hover {
			color: #cc0001;
			text-decoration: none;
		}
		a:link {
			color: #cc0001;
			text-decoration: none;
		}
		.noborder {
			border: 0 none;
		}
		.icon_salvar {
			float: left;
			margin: 5px 3px 0 0;
		}
		.salvar {
			float: left;
			font-size: 8pt;
			font-style: italic;
			margin-top: 6px;
		}
		#content_interno {
			background: none repeat scroll 0 0 #fff;
			float: left;
			height: auto;
			padding: 0 30px 40px 0;
			width: 944px;
		}
		#conteudo {
			float: left;
			height: auto;
			position: relative;
			width: 440px;
		}
		#conteudo2 {
			float: left;
			height: auto;
			position: relative;
			width: 683px;
		}
		input.text, input.file, input[type="file"], input[type="text"], input.password, input[type="password"], textarea, select {
			border: 1px solid #ada89c;
			font-family: Arial,Helvetica,Verdana;
			font-size: 12px;
			padding: 1px;
		}
		.btnacao {
			background: url("imagens_wurth/bgBotao.jpg") no-repeat scroll 0 0 rgba(0, 0, 0, 0);
			border: medium none;
			color: #ffffff;
			float: left;
			padding: 1px 0 1px 10px;
			width: 100px;
		}

	</style>
	<script type="text/javascript">
		$(function(){
			$("#cnpj_assist").mask('00.000.000/0000-00');
			$('#telefone1_consumidor').keypress(function(){
             	if( $(this).val().match(/^\(1\d\) 9/i)){
                 	$(this).mask('(00) 00000-0000'); /* 9º Dígito */
             	}else{
                	$(this).mask('(00) 0000-0000');  /* Máscara default */
             	}
         	});
         	$('#telefone2_consumidor').keypress(function(){
             	if( $(this).val().match(/^\(1\d\) 9/i)){
                 	$(this).mask('(00) 00000-0000'); /* 9º Dígito */
             	}else{
                	$(this).mask('(00) 0000-0000');  /* Máscara default */
             	}
         	});

         	$('#telefone_representante').keypress(function(){
             	if( $(this).val().match(/^\(1\d\) 9/i)){
                 	$(this).mask('(00) 00000-0000'); /* 9º Dígito */
             	}else{
                	$(this).mask('(00) 0000-0000');  /* Máscara default */
             	}
         	});

         	$('#telefone_condumidor_representantes').keypress(function(){
             	if( $(this).val().match(/^\(1\d\) 9/i)){
                 	$(this).mask('(00) 00000-0000'); /* 9º Dígito */
             	}else{
                	$(this).mask('(00) 0000-0000');  /* Máscara default */
             	}
         	});
			$('#telefone_consumidor_assist').keypress(function(){
             	if( $(this).val().match(/^\(1\d\) 9/i)){
                 	$(this).mask('(00) 00000-0000'); /* 9º Dígito */
             	}else{
                	$(this).mask('(00) 0000-0000');  /* Máscara default */
             	}
         	});
         	$('#telefone2_consumidor_assist').keypress(function(){
             	if( $(this).val().match(/^\(1\d\) 9/i)){
                 	$(this).mask('(00) 00000-0000'); /* 9º Dígito */
             	}else{
                	$(this).mask('(00) 0000-0000');  /* Máscara default */
             	}
         	});
         	$('#telefone_assist').keypress(function(){
             	if( $(this).val().match(/^\(1\d\) 9/i)){
                 	$(this).mask('(00) 00000-0000'); /* 9º Dígito */
             	}else{
                	$(this).mask('(00) 0000-0000');  /* Máscara default */
             	}
         	});

			$('select[name=setor]').change(function(){
				var opt = $(this).val();
				if(opt == "A"){
					$('.C').hide();
					$('.R').hide();
					$('.A').show();
				}else if(opt == "C"){
					$('.R').hide();
					$('.A').hide();
					$('.C').show();
				}else if(opt == "R"){
					$('.C').hide();
					$('.A').hide();
					$('.R').show();
				}else{
					$('.C').hide();
					$('.R').hide();
					$('.A').hide();
				}
			});
		});
			function enviarconsumidor(){
				var nome 			= $('#nome_consumidor').val();
				var telefone1 		= $('#telefone1_consumidor').val();
				var telefone2 		= $('#telefone2_consumidor').val();
				var mensagem 		= $('#mensagem_consumidor').val();
				$('#prod option:selected').each(function(){
					produto = $(this).val();
					produto_desc = $(this).text();
				});
				

				if(nome == "" || nome == undefined) {
					alert('O campo Nome é Obrigatório');
					$('#nome_consumidor').css({'border' : '1px solid #ff0000'});
					return;
				}

				if(telefone1 == "" || telefone1 == undefined){
					alert('O campo telefone é Obrigatório');
					$('#telefone1_consumidor').css({'border' : '1px solid #ff0000'});
					return;
				}
				if(telefone2 == "" || telefone2 == undefined ){
					alert('O campo telefone2 é Obrigatório');
					$('#telefone2_consumidor').css({'border' : '1px solid #ff0000'});
					return;
				}

				if(produto == "" || produto == undefined){
					alert('O campo produto é Obrigatório');
					$('#produto_consumidor').css({'border' : '1px solid #ff0000'});
					return;
				}

				$.ajax({
					url 	: "<?php echo $_SERVER['PHP_SELF']; ?>",
					type 	: "POST",
					data 	: {
						ajax 			: "ok",
						consumidor 		: "ok",
						nome 			: nome,
						telefone1 		: telefone1,
						telefone2 		: telefone2,
						produto 		: produto,
						produto_desc 	: produto_desc,
						mensagem 		: mensagem
					},
					beforeSend: function(){
						$('#enviando_consumidor').text('Enviado, aguarde...');
					},
					complete: function(data){
						$('#enviando_consumidor').text('');
						data = data.responseText;
						if(data == "ok"){
							$('#box_envio_consumidor').show();
						}

						$('#nome_consumidor').val('');
						$('#telefone1_consumidor').val('');
						$('#telefone2_consumidor').val('');
						$('#produto_consumidor').val('');
						$('#mensagem_consumidor').val('');

					}
				});	
			}

			function enviarassist(){

				var nome_consumidor			= $('#nome_consumidor_assist').val();
				var telefone_consumidor 	= $('#telefone_consumidor_assist').val();
				var telefone2_consumidor 	= $('#telefone2_consumidor_assist').val();
				var razao_social 			= $('#razao_social_assist').val();
				var cnpj 					= $('#cnpj_assist').val();
				var telefone_assist 		= $('#telefone_assist').val();
				var contato 				= $('#contato_assist').val();
				var mensagem 				= $('#mensagem_assist').val();
				$('#produto_assist option:selected').each(function(){
					produto = $(this).val();
					produto_desc = $(this).text();
				});

				if(nome_consumidor == "" || nome_consumidor == undefined){
					alert('O campo Nome do consumidor é Obrigatório');
					$('#nome_consumidor_assist').css({'border' : '1px solid #ff0000'});
					return;
				}

				if(telefone_consumidor == "" || telefone_consumidor == undefined){
					alert('O campo telefone do consumidor é Obrigatório');
					$('#telefone_consumidor_assist').css({'border' : '1px solid #ff0000'});
					return;
				}
				if(telefone2_consumidor == "" || telefone2_consumidor == undefined){
					alert('O campo telefone2 do consumidor é Obrigatório');
					$('#telefone2_consumidor_assist').css({'border' : '1px solid #ff0000'});
					return;
				}

				if(razao_social == "" || razao_social == undefined){
					alert('O campo Razão Social é Obrigatório');
					$('#razao_social_assist').css({'border' : '1px solid #ff0000'});
					return;
				}
				if(cnpj == "" || cnpj == undefined){
					alert('O campo CNPJ é Obrigatório');
					$('#cnpj_assist').css({'border' : '1px solid #ff0000'});
					return;
				}
				if(produto == "" || produto == undefined){
					alert('O campo produto é Obrigatório');
					$('#produto_assist').css({'border' : '1px solid #ff0000'});
					return;
				}
				if(telefone_assist == "" || telefone_assist == undefined){
					alert('O campo telefone da assistência é Obrigatório');
					$('#telefone_assist').css({'border' : '1px solid #ff0000'});
					return;
				}
				if(contato == "" || contato == undefined){
					alert('O campo contato é Obrigatório');
					$('#contato_assist').css({'border' : '1px solid #ff0000'});
					return;
				}
				
				$.ajax({
					url 	: "<?php echo $_SERVER['PHP_SELF']; ?>",
					type 	: "POST",
					data 	: {
						
						ajax 				: "ok",
						assist 				: "ok",
						nome_consumidor		: nome_consumidor,
						telefone_consumidor : telefone_consumidor,
						telefone2_consumidor: telefone2_consumidor,
						razao_social 		: razao_social,
						cnpj 				: cnpj,
						produto 			: produto,
						produto_desc 		: produto_desc,
						telefone_assist 	: telefone_assist,
						contato 			: contato,
						mensagem 			: mensagem
					
					},
					beforeSend: function(){
						$('#enviando_assist').text('Enviado, aguarde...');
					},
					complete: function(data){
						$('#enviando_assist').text('');
						data = data.responseText;
						if(data == "ok"){
							$('#box_envio_assist').show();
						}

						$('#nome_consumidor_assist').val('');
						$('#telefone_consumidor_assist').val('');
						$('#telefone2_consumidor_assist').val('');
						$('#razao_social_assist').val('');
						$('#cnpj_assist').val('');
						$('#produto_assist').val('');
						$('#telefone_assist').val('');
						$('#contato_assist').val('');
						$('#mensagem_assist').val('');

					}
				});	
			}
			function enviarrevenda(){
			
				var nome 				= $('#nome_representante').val();
				var telefone1 			= $('#telefone_representante').val();
				var nome_consumidor		= $('#nome_consumidor_representante').val();
				var telefone_consumidor	= $('#telefone_condumidor_representantes').val();
				var assist 				= $('#assistencia_tecnica_representante').val();
				var mensagem 			= $('#mensagem_representante').val();
				$('#produto_representante option:selected').each(function(){
					produto = $(this).val();
					produto_desc = $(this).text();
				});

				if(nome == "" || nome == undefined ){
					alert('O campo Nome é Obrigatório');
					$('#nome_representante').css({'border' : '1px solid #ff0000'});
					return;
				}

				if(telefone1 == "" || telefone1 == undefined ){
					alert('O campo telefone1 é Obrigatório');
					$('#telefone_representante').css({'border' : '1px solid #ff0000'});
					return;
				}

				if(produto == ""  || produto == undefined ){
					alert('O campo produto é Obrigatório');
					$('#produto_representante').css({'border' : '1px solid #ff0000'});
					return;
				}

				$.ajax({
					url 	: "<?php echo $_SERVER['PHP_SELF']; ?>",
					type 	: "POST",
					data 	: {
						ajax 				: "ok",
						revenda 			: "ok",
						nome 				: nome ,
						telefone1 			: telefone1 ,
						produto 			: produto ,
						produto_desc 		: produto_desc,
						nome_consumidor		: nome_consumidor ,
						telefone_consumidor	: telefone_consumidor ,						
						assist 				: assist ,
						mensagem 			: mensagem
					},
					beforeSend: function(){
						$('#enviando_representante').text('Enviado, aguarde...');
					},
					complete: function(data){
						$('#enviando_representante').text('');
						data = data.responseText;
						if(data == "ok"){
							$('#box_envio_representante').show();
						}
						$('#nome_representante').val('');
						$('#telefone_representante').val('');
						$('#produto_representante').val('');
						$('#nome_consumidor_representante').val('');
						$('#telefone_condumidor_representantes').val('');
						$('#assistencia_tecnica_representante').val('');
						$('#mensagem_representante').val('');

					}
				});	
			}

	</script>

</head>
<body>

	<div id="conteudo2">
		<div class='setor'>
				<h2>Produto Fora de Garantia
				 <br />  
				 <br /> 
					<select name='setor'>
						<option value=''></option>
						<option value='C'>Consumidor</option>
						<option value='R'>Representante</option>
						<option value='A'>Assistência Técnica</option>
					</select>
				</h2>
			</div>

			<br /> 
			<div class="C" style="display: none;" id="conteudo2">

				<p style='color: #ff0000; text-align: right; width: 98%;'>
					* Campos Obrigatórios
				</p>

				*Nome 
				<input type="text" name="nome_consumidor" id="nome_consumidor" style='width: 98%;' /> 
				*Telefone 			
				<input type="text" name="telefone1_consumidor" id="telefone1_consumidor" style='width: 98%;' />

				*Telefone 2 			
				<input type="text" name="telefone2_consumidor" id="telefone2_consumidor" style='width: 98%;' />
				
				*Produto 
				<div style="width: 98%">
				<select name='produto_consumidor' id="prod" style="width: 100%">
						<option value=''></option>
					<?php
						for ($i = 0 ;$i < pg_num_rows($res_produtos); $i++){
							$produto 	= pg_fetch_result($res_produtos, $i, 'produto');
							$descricao 	= pg_fetch_result($res_produtos, $i, 'descricao');
							echo "<option value='".$produto."'>".$descricao."</option>";
						}
						//<input type="text" name="produto_consumidor" id="produto_consumidor" style='width: 98%;' />
					?>
				</select>	
				</div>		

				Mensagem 			
				<textarea name="mensagem_consumdor" id="mensagem_consumidor" rows="6" style="width: 98%"></textarea> <br /> <br /> 

				<button  class="btnacao" value="Enviar" onclick="enviarconsumidor();">Enviar</button> <br /> <br /> 
				<em id="enviando_consumidor"></em>

				<div class="box_envio" id="box_envio_consumidor" style="display: none;">Mensagem Enviada com Sucesso!</div>

			</div>


			<div class="R" style="display: none;" id="conteudo2">

				<p style='color: #ff0000; text-align: right; width: 98%;'>
					* Campos Obrigatórios
				</p>

				*Nome do representante 
				<input type="text" name="nome_representante" id="nome_representante" style='width: 98%;' /> 

				*Telefone <br /> 
				<input type="text" name="telefone_representante" id="telefone_representante" style='width: 98%;' /> 
				
				*Produto <br /> 
				<div style="width: 98%">
				<select name='propduto_representante' id="produto_representante" style="width: 100%">
						<option value=''></option>
					<?php
						for ($i = 0 ;$i < pg_num_rows($res_produtos); $i++){
							$produto 	= pg_fetch_result($res_produtos, $i, 'produto');
							$descricao 	= pg_fetch_result($res_produtos, $i, 'descricao');
							echo "<option value='".$produto."'>".$descricao."</option>";
						}
					?>
				</select>
				</div>
				Nome do Consumidor <br /> 
				<input type="text" name="nome_consumidor_representante" id="nome_consumidor_representante" style='width: 98%;' />

				Telefone do Consumidor  <br /> 
				<input type="text" name="telefone_condumidor_representante" id="telefone_condumidor_representantes" style='width: 98%;' />

				Assistência Técnica <br /> 
				<input type="text" name="assistencia_tecnica_representante" id="assistencia_tecnica_representante" style='width: 98%;' />

				Mensagem <br /> 
				<textarea name="mensagem_representante" id="mensagem_representante" rows="6" style="width: 98%"></textarea> <br /> <br /> 

				<input type="submit" class="btnacao" value="Enviar" onclick="enviarrevenda();" /> <br /> <br /> 
				<em id="enviando_representante"></em>

				<div class="box_envio" id="box_envio_representante" style="display: none;">Mensagem Enviada com Sucesso!</div>

			</div>
			<?php

			if (strlen($posto = $_GET['cnpj_assist'])> 0){

				$sql = " SELECT cnpj ,
								nome_fantasia,
								fone,
								contato 
						FROM tbl_posto 
						WHERE posto =  {$posto} ";
				$res = pg_query($con,$sql);
				
				$razao_social_assist = pg_fetch_result($res, 0, "nome_fantasia");
				$cnpj_assist		 = pg_fetch_result($res, 0, "cnpj");
				$telefone_assist 	 = pg_fetch_result($res, 0, "fone");
				$contato_assist 	 = pg_fetch_result($res, 0, "contato");
			}
			?>
			<div class="A" style="display: <?php if (strlen($cnpj_assist > 0)) echo "block"; else echo "none";  ?>;" id="conteudo2" >
				<p style='color: #ff0000; text-align: right; width: 98%;'>
					* Campos Obrigatórios
				</p>

				*Nome do consumidor
				<input type="text" name="nome_consumidor_assist" id="nome_consumidor_assist" style='width: 98%;'  /> 

				*Telefone do consumidor <br /> 
				<input type="text" name="telefone_consumidor_assist" id="telefone_consumidor_assist" style='width: 98%;' />

				*Telefone do consumidor 2  <br /> 
				<input type="text" name="telefone2_consumidor_assist" id="telefone2_consumidor_assist" style='width: 98%;' /> 

				*Razão Social <br /> 
				<input type="text" name="razao_social_assist" id="razao_social_assist" style='width: 98%;' value='<?php if (isset($razao_social_assist)) echo $razao_social_assist ; ?>'/> 

				*CNPJ <br />
				<input type="text" name="cnpj_assist" id="cnpj_assist" style='width: 98%;' value='<?php if (isset($cnpj_assist)) echo $cnpj_assist ; ?>' />

				*Produto  <br /> 
				<div style="width: 98%">
				<select name='propduto_assist' id="produto_assist" style="width: 100%">
						<option value=''></option>
					<?php
						for ($i = 0 ;$i < pg_num_rows($res_produtos); $i++){
							$produto 	= pg_fetch_result($res_produtos, $i, 'produto');
							$descricao 	= pg_fetch_result($res_produtos, $i, 'descricao');
							echo "<option value='".$produto."'>".$descricao."</option>";
						}
					?>
				</select>
				</div>
				*Telefone da assistência <br /> 
				<input type="text" name="telefone_assist" id="telefone_assist" style='width: 98%;' value='<?php if (isset($telefone_assist)) echo $telefone_assist ; ?>' /> 

				*Contato da assistência <br /> 
				<input type="text" name="contato_assist" id="contato_assist" style='width: 98%;' value='<?php if (isset($contato_assist)) echo $contato_assist ; ?>' />

				Mensagem <br /> 
				<textarea name="mensagem_assist" id="mensagem_assist" rows="6" style="width: 98%"></textarea> <br /> <br /> 

				<input type="submit" class="btnacao" value="Enviar" onclick="enviarassist();" /> <br /> <br /> 
				<em id="enviando_assist"></em>

				<div class="box_envio" id="box_envio_assist" style="display: none;">Mensagem Enviada com Sucesso!</div>

			</div>

		</div>

</body>
</html>
<?php




?>
