<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
require( '../class_resize.php' );

$fabrica = 24;
$verifica_cnpj  = trim($_GET['verifica_cnpj']);
$msg = $_GET['msg'];

if(strlen($verifica_cnpj) > 0){
	$verifica_cnpj = preg_replace('/\D/', '', $verifica_cnpj);
	$verifica_cnpj = substr($verifica_cnpj,0,14);
	
	$sql = "SELECT posto,nome, nome_fantasia,ie,fone,fax,contato,cidade,cep
			FROM tbl_posto
			WHERE cnpj = '$verifica_cnpj';";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res) > 0) {
		$posto         = pg_result($res,0,posto);
		$nome          = pg_result($res,0,nome);
		$nome_fantasia = pg_result($res,0,nome_fantasia);
		$ie            = pg_result($res,0,ie);
		$fone          = pg_result($res,0,fone);
		$fax           = pg_result($res,0,fax);
		$contato       = pg_result($res,0,contato);
		$cidade        = pg_result($res,0,cidade);
		$cep           = pg_result($res,0,cep);
		echo "ok;$posto;$nome;$fone;$fax;$ie;$contato;$nome_fantasia;$cidade;$cep";
	}
	exit;
}

$btn_acao = $_POST['btn_acao'];
if(strlen($btn_acao) > 0){
	
	
	$aux_nome          = trim($_POST['nome']);
	if(strlen($aux_nome) == 0){
		$msg_erro = "Preencha o campo Razão Social";
	}
	
	$aux_nome_fantasia = trim($_POST['nome_fantasia']);
	if(strlen($aux_nome_fantasia) == 0){
		$aux_nome_fantasia = "null";
	}
	

	$aux_cnpj          = trim($_POST['cnpj']);
	$aux_cnpj = preg_replace('/\D/', '', $aux_cnpj);

	if(strlen($aux_cnpj) == 14) {
		$sql = "SELECT posto FROM tbl_posto WHERE cnpj='$aux_cnpj'";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res) > 0) {
			$posto = pg_result($res,0,posto);
		}
	} else {
		$msg_erro = "Preencha/Verifique o campo CNPJ";
	}

	$aux_endereco      = trim($_POST['endereco']);
	if(strlen($aux_endereco) == 0 AND strlen($msg_erro) == 0){
		$msg_erro = "Preencha o campo Endereço";
	}

	$aux_numero        = trim($_POST['numero']);
	if(strlen($aux_nome) == 0 AND strlen($msg_erro) == 0){
		$msg_erro = "Preencha o campo Número";
	}

	$aux_complemento   = trim($_POST['complemento']);
	if(strlen($aux_complemento) == 0 AND strlen($msg_erro) == 0){
		$aux_complemento = 'null';
	}

	$aux_bairro        = trim($_POST['bairro']);
	if(strlen($aux_bairro) == 0 AND strlen($msg_erro) == 0){
		$msg_erro = "Preencha o campo Bairro";
	}

	$aux_cidade        = trim($_POST['cidade']);
	if(strlen($aux_cidade) == 0 AND strlen($msg_erro) == 0){
		$msg_erro = "Preencha o campo Cidade";
	}

	$aux_estado        = trim($_POST['estado']);
	if(strlen($aux_estado) == 0 AND strlen($msg_erro) == 0){
		$msg_erro = "Preencha o campo Estado";
	}

	$aux_cep           = trim($_POST['cep']);
	$aux_cep = str_replace (".","",$aux_cep);
	$aux_cep = str_replace ("-","",$aux_cep);
	$aux_cep = str_replace (" ","",$aux_cep);
	if(strlen($aux_cep) == 0 AND strlen($msg_erro) == 0){
		$msg_erro = "Preencha o campo CEP";
	}

	$aux_email         = trim($_POST['email']);
	if(strlen($aux_email) == 0 AND strlen($msg_erro) == 0){
		$aux_email = "null";
	}

	$aux_telefone      = trim($_POST['telefone']);
	if(strlen($aux_telefone) == 0 AND strlen($msg_erro) == 0){
		$msg_erro = "Preencha o campo Telefone";
	}
	
	$aux_fax           = trim($_POST['fax']);
	if(strlen($aux_fax) == 0 AND strlen($msg_erro) == 0){
		$aux_fax = "null";
	}else{
		$aux_fax           = $aux_fax ;
	}

	$aux_contato       = trim($_POST['contato']);
	if(strlen($aux_contato) == 0 AND strlen($msg_erro) == 0){
		$msg_erro = "Preencha o campo Contato.";
	}else{
		$aux_contato       =  $aux_contato ;
	}

	$aux_ie            = trim($_POST['ie']);
	if(strlen($aux_ie) == 0 AND strlen($msg_erro) == 0){
		$aux_ie = "null";
	}
$aux_funcionarios             = trim($_POST['funcionarios']);
	if(strlen($aux_funcionarios) == 0 AND strlen($msg_erro) == 0){
		//$msg_erro = "Preencha o campo Estado";
	}else{
		if(!is_numeric($aux_funcionarios)){
			$msg_erro = "Apenas números no campo Qtde de funcionários.";
		}
	}

	$aux_oss                      = trim($_POST['oss']);
	if(strlen($aux_oss) == 0 AND strlen($msg_erro) == 0){
		//$msg_erro = "Preencha o campo Estado";
	}else{
		if(!is_numeric($aux_oss)){
			$msg_erro = "Apenas números no campo Qtde de Ordem de Serviço mensal.";
		}
	}



	if(strlen($_POST['linha_1']) > 0){ $linhas = $_POST['linha_1']; $linha_1 = $_POST['linha_1'];}
	if(strlen($_POST['linha_2']) > 0){ $linhas .= ",".$_POST['linha_2']; $linha_2 = $_POST['linha_2'];}
	if(strlen($_POST['linha_3']) > 0){ $linhas .= ",".$_POST['linha_3']; $linha_3 = $_POST['linha_3'];}
	if(strlen($_POST['linha_4']) > 0){ $linhas .= ",".$_POST['linha_4']; $linha_4 = $_POST['linha_4'];}
	if(strlen($_POST['linha_5']) > 0){ $linhas .= ",".$_POST['linha_5']; $linha_5 = $_POST['linha_5'];}
	if(strlen($_POST['linha_6_obs']) > 0){ $linhas .= ",".$_POST['linha_6_obs']; $linha_6_obs = $_POST['linha_6_obs'];}

	if(strlen($linhas) == 0){
		$msg_erro = "Escolha ao menos uma LINHA de atuação.";
	}

	$fabricantes = $_POST['fabricantes'];

	if(strlen($_POST['descricao']) == 0){
		$msg_erro = "Faça uma breve descrição de sua Autorizada.";
	}else{
		$descricao = $_POST['descricao'];
	}


	$aux_atende_cidade_proxima            = trim($_POST['atende_cidade_proxima']);
	if(strlen($aux_atende_cidade_proxima) == 0 AND strlen($msg_erro) == 0){
		$aux_atende_cidade_proxima = "null";
	}else{
		$aux_atende_cidade_proxima = "'". $aux_atende_cidade_proxima ."'";
	}

	$aux_marca_nao_autorizada            = trim($_POST['marca_nao_autorizada']);
	if(strlen($aux_marca_nao_autorizada) == 0 AND strlen($msg_erro) == 0){
		$aux_marca_nao_autorizada = "null";
	}else{
		$aux_marca_nao_autorizada = "'". $aux_marca_nao_autorizada ."'";
	}

	$aux_marca_ser_autorizada            = trim($_POST['marca_ser_autorizada']);
	if(strlen($aux_marca_ser_autorizada) == 0 AND strlen($msg_erro) == 0){
		$aux_marca_ser_autorizada = "null";
	}else{
		$aux_marca_ser_autorizada = "'". $aux_marca_ser_autorizada ."'";
	}

	$aux_melhor_sistema            = trim($_POST['melhor_sistema']);
	if(strlen($aux_melhor_sistema) == 0 AND strlen($msg_erro) == 0){
		$aux_melhor_sistema = "null";
	}else{
		$aux_melhor_sistema = "'". $aux_melhor_sistema ."'";
	}



	if(strlen($msg_erro) == 0) {

		//$res = pg_exec ($con,"BEGIN TRANSACTION");
		if(strlen($posto) == 0){

			$aux_nome                  = mb_convert_encoding( "$aux_nome"                  , 'ISO-8859-1', 'UTF-8' );
			$aux_cnpj                  = mb_convert_encoding( "$aux_cnpj"                  , 'ISO-8859-1', 'UTF-8' );
			$aux_ie                    = mb_convert_encoding( "$aux_ie"                    , 'ISO-8859-1', 'UTF-8' );
			$aux_endereco              = mb_convert_encoding( "$aux_endereco"              , 'ISO-8859-1', 'UTF-8' );
			$aux_numero                = mb_convert_encoding( "$aux_numero"                , 'ISO-8859-1', 'UTF-8' );
			$aux_complemento           = mb_convert_encoding( "$aux_complemento"           , 'ISO-8859-1', 'UTF-8' );
			$aux_bairro                = mb_convert_encoding( "$aux_bairro"                , 'ISO-8859-1', 'UTF-8' );
			$aux_cep                   = mb_convert_encoding( "$aux_cep"                   , 'ISO-8859-1', 'UTF-8' );
			$aux_cidade                = mb_convert_encoding( "$aux_cidade"                , 'ISO-8859-1', 'UTF-8' );
			$aux_estado                = mb_convert_encoding( "$aux_estado"                , 'ISO-8859-1', 'UTF-8' );
			$aux_contato               = mb_convert_encoding( "$aux_contato"               , 'ISO-8859-1', 'UTF-8' );
			$aux_email                 = mb_convert_encoding( "$aux_email"                 , 'ISO-8859-1', 'UTF-8' );
			$aux_telefone              = mb_convert_encoding( "$aux_telefone"              , 'ISO-8859-1', 'UTF-8' );
			$aux_fax                   = mb_convert_encoding( "$aux_fax"                   , 'ISO-8859-1', 'UTF-8' );
			$aux_nome_fantasia         = mb_convert_encoding( "$aux_nome_fantasia"         , 'ISO-8859-1', 'UTF-8' );

			#-------------- INSERT ---------------
			$sql = "INSERT INTO tbl_posto (
						nome            ,
						cnpj            ,
						ie              ,
						endereco        ,
						numero          ,
						complemento     ,
						bairro          ,
						cep             ,
						cidade          ,
						estado          ,
						contato         ,
						email           ,
						fone            ,
						fax             ,
						nome_fantasia
					) VALUES (
						'$aux_nome'         ,
						'$aux_cnpj'         ,
						'$aux_ie'           ,
						'$aux_endereco'     ,
						'$aux_numero'       ,
						'$aux_complemento'  ,
						'$aux_bairro'       ,
						'$aux_cep'          ,
						'$aux_cidade'       ,
						'$aux_estado'       ,
						'$aux_contato'      ,
						'$aux_email'        ,
						'$aux_telefone'     ,
						'$aux_fax'          ,
						'$aux_nome_fantasia'
					)";
		

			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage ($con);
		
			if(strlen($msg_erro) == 0) {
				$sql3 = "SELECT CURRVAL ('seq_posto')";
				$res3 = pg_exec ($con,$sql3);
				$posto = pg_result ($res3,0,0);
	
	
			$sql = " INSERT INTO tbl_posto_fabrica_autocredenciamento(
							posto                  ,
							fabrica                ,
							data_autocredenciamento )
						VALUES (
							$posto                  ,
							24                      ,
							current_timestamp)";
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage ($con);
			}

		}else{

			$sql = "UPDATE tbl_posto set
						nome            = '$aux_nome',
						cnpj            = '$aux_cnpj',
						ie              = '$aux_ie'  ,
						endereco        = '$aux_endereco',
						numero          = '$aux_numero',
						complemento     = '$aux_complemento',
						bairro          = '$aux_bairro',
						cep             = '$aux_cep',
						cidade          = '$aux_cidade',
						estado          = '$aux_estado',
						contato         = '$aux_contato',
						email           = '$aux_email',
						fone            = '$aux_telefone',
						fax             = '$aux_fax',
						nome_fantasia   = '$aux_nome_fantasia'
						WHERE posto = $posto";
			$res = pg_exec($con,$sql);
			//echo nl2br($sql);		
			$sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $fabrica and posto = $posto and credenciamento = 'CREDENCIADO'";
			$res = pg_exec($con,$sql);
		
			if(pg_numrows($res) > 0) {
				$msg_erro=" O posto $aux_nome já é um posto autorizado da Suggar ";
			}else{
				$sql = "select posto from tbl_posto_fabrica_autocredenciamento where fabrica = 24 and posto = $posto AND data_descredenciado is not null";
				$res = pg_exec($con,$sql);
				if(pg_numrows($res) == 0) {
					$sql = " INSERT INTO tbl_posto_fabrica_autocredenciamento(
							posto                  ,
							fabrica                ,
							data_autocredenciamento )
						VALUES (
							$posto                  ,
							24                      ,
							current_timestamp)";
				$res = @pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
				}
				else {
					$msg_erro=" Este posto foi descrendenciado pela fabrica ";
				}
			}
		}
	}

	if(strlen($msg_erro) == 0 AND strlen($posto) > 0){

		$descricao                 = mb_convert_encoding( "$descricao"                 , 'ISO-8859-1', 'UTF-8' );
		$fabricantes               = mb_convert_encoding( "$fabricantes"               , 'ISO-8859-1', 'UTF-8' );
		$linhas                    = mb_convert_encoding( "$linhas"                    , 'ISO-8859-1', 'UTF-8' );
		$aux_funcionarios          = mb_convert_encoding( "$aux_funcionarios"          , 'ISO-8859-1', 'UTF-8' );
		$aux_oss                   = mb_convert_encoding( "$oss"                       , 'ISO-8859-1', 'UTF-8' );
		$aux_atende_cidade_proxima = mb_convert_encoding( "$aux_atende_cidade_proxima" , 'ISO-8859-1', 'UTF-8' );
		$aux_marca_nao_autorizada  = mb_convert_encoding( "$aux_marca_nao_autorizada"  , 'ISO-8859-1', 'UTF-8' );
		$aux_marca_ser_autorizada  = mb_convert_encoding( "$aux_marca_ser_autorizada"  , 'ISO-8859-1', 'UTF-8' );
		$aux_melhor_sistema        = mb_convert_encoding( "$aux_melhor_sistema"        , 'ISO-8859-1', 'UTF-8' );

		$sql = "UPDATE tbl_posto_extra SET 
								descricao                 = '$descricao'                ,
								fabricantes               = '$fabricantes'              ,
								linhas                    = '$linhas'                   ,
								funcionario_qtde          = $aux_funcionarios           ,
								os_qtde                   = $aux_oss                    ,
								data_modificado           = current_timestamp           ,
								atende_cidade_proxima     = $aux_atende_cidade_proxima  ,
								marca_nao_autorizada      = $aux_marca_nao_autorizada   ,
								marca_ser_autorizada      = $aux_marca_ser_autorizada   ,
								melhor_sistema            = $aux_melhor_sistema         
							WHERE posto = $posto;";
		$res = pg_exec($con,$sql);
		$msg_erro = pg_errormessage ($con);
		#echo "$sql";
		
		
		
		$res = pg_exec($con,$sql);
		$msg_erro = pg_errormessage ($con);
						
						
		if(strlen($msg_erro) == 0){
			$config["tamanho"] = 4096000;
			for($i = 1; $i < 4; $i++){
				$arquivo                = isset($_FILES["arquivo$i"]) ? $_FILES["arquivo$i"] : FALSE;
				if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["tmp_name"] != "none"){
					$xposto = $posto;
					if (!preg_match("/\/(pjpeg|jpeg|png|gif|bmp)$/", $arquivo["type"])){
						$msg_erro = "Arquivo em formato inválido!";
					} else {
						if ($arquivo["size"] > $config["tamanho"]) 
							$msg_erro = "Arquivo em tamanho muito grande! Deve ser de no máximo 4MB. Envie outro arquivo.";
					}
					if (strlen($msg_erro) == 0) {
						preg_match("/\.(gif|bmp|png|jpg|jpeg){1}$/i", $arquivo["name"], $ext);
						$aux_extensao = "." .strtolower($ext[1]);
						
						$xposto .= "_" .$i . $aux_extensao;

						$nome_anexo = $xposto;

						$imagem_dir = "/www/assist/www/credenciamento/fotos/".strtolower($nome_anexo);
						
						if (strlen($msg_erro) == 0) {
							$thumbail = new resize( "arquivo$i", 600, 400 );
							$thumbail -> saveTo($nome_anexo,"/www/assist/www/credenciamento/fotos/" ); 
						}
					}
				}
			}
		}
	}
	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF?msg=ok");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if($msg =='ok') {
	$msg = "Cadastrado com sucesso";
	$msg_estilo='msg';
	$mensagem = $msg;
}
if(strlen($msg_erro) > 0){
	$msg_estilo = 'msg_erro';
	if (strpos($msg_erro,"ERROR:") !== false) {
		$x = explode('ERROR:',$msg_erro);
		$msg_erro = $x[1];
	}
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
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
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<title>Suggar - Auto Credenciamento</title>

	<link rel="stylesheet" type="text/css" href="http://www.suggar.com.br/telecontrol/css/estilo_telecontrol.css" /> 
	<script type='text/javascript' src="callcenter/suggar.js"></script>
	<script type='text/javascript' src="../js/jquery-1.6.1.min.js"></script>
	<script type='text/javascript' src="../js/jquery.maskedinput.min.js"></script>
	<script type='text/javascript'>
		var php_self = window.location.pathname;
		$(document).ready(function(){
			$("#cnpj").maskedinput("99.999.999/9999-99")
				.blur(function() {
					buscaCNPJ($(this).val());
			});
			$("#telefone").maskedinput("(99) 9999-9999");
			$("#fax").maskedinput("(99) 9999-9999");
			$("#cep").maskedinput("99999-999")
				 .blur(function() {
					buscaCEP($(this).val());
			});
		});

		function buscaCNPJ(cnpj) {
			if(cnpj.length > 0){
				$.ajax({
					type: "GET",
					url:  php_self,
					data: "verifica_cnpj="+escape(cnpj),
					cache: false,
					complete: function(resposta){
						if (resposta.indexOf('NAO IDENTIFICADO')>-1) {
							$('#cnpj').focus();
							return false;
						}
						results = resposta.responseText.split(";");
						if (typeof (results[1]) != 'undefined') $('#posto').val(results[1]);
						if (typeof (results[2]) != 'undefined') $('#nome').val(results[2]);
						if (typeof (results[3]) != 'undefined') $('#telefone').val(results[3]);
						if (typeof (results[4]) != 'undefined') $('#fax').val(results[4]);
						if (typeof (results[5]) != 'undefined') $('#ie').val(results[5]);
						if (typeof (results[6]) != 'undefined') $('#contato').val(results[6]);
						if (typeof (results[7]) != 'undefined') $('#nome_fantasia').val(results[7]);
						if (typeof (results[8]) != 'undefined') $('#cidade').val(results[8]);
						if (typeof (results[9]) != 'undefined') $('#cep').val(results[9]);
					}
				});
			}
		}

		function buscaCEP(cep) {
			$.ajax({
				type: "GET",
				url:  "../admin/ajax_cep.php",
				data: "cep="+escape(cep),
				cache: false,
				complete: function(resposta){
					results = resposta.responseText.split(";");
					if (typeof (results[1]) != 'undefined') $('#endereco').val(results[1]);
					if (typeof (results[2]) != 'undefined') $('#bairro').val(results[2]);
					if (typeof (results[3]) != 'undefined') $('#cidade').val(results[3]);
					if (typeof (results[4]) != 'undefined') $('#estado').val(results[4]);
					if (results.length > 3) $('#numero').focus();
				}
			});
		}


	</script>
</head>

<body>
<table class='tabela_miolo' width="95%" border="0" align="center" cellpadding="0" cellspacing="0">
<tr>
<td valign="top">
	<div id='msg' class='<?=$msg_estilo?>'><?if(strlen($mensagem) > 0) { echo $mensagem;
	}?></div>
	<p class="texto"><br /></p>
	<form name="frm_posto" action="<?$PHP_SELF?>" method="post" enctype="multipart/form-data">
	<table width="100%" border="0" cellpadding="5" cellspacing="5" class="tabela_produtos">
		<tr>
			<td>
				<table width="100%" border="0" cellspacing="5" cellpadding="5">
					<tr class='menu_top'>
						<td nowrap="nowrap"><center>INFORMAÇÕES CADASTRAIS</center></td>
					</tr>
					<tr>
						<td><label for='cnpj'>CNPJ:</label>
							<br />
							<input type="text" name="cnpj" size="20" class="label" maxlength="18" id="cnpj" value="<? echo $cnpj ?>" />
							<input type='hidden' name='posto' id='posto' value='' />
						</td>
						<td><label for='nome'>Razão Social:</label>
							<br />
							<input type="text" name="nome" size="30" class="label" id='nome' maxlength="150" value="<? echo $nome ?>" style="width:250px" />
						</td>
					</tr>
					<tr>
						<td><label for='ie'>I.E.:</label>
							<br />
							<input type="text" class="label" name="ie" id='ie' size="20" maxlength="30" value="<? echo $ie ?>"  />
						</td>
						<td><label for='telefone'>Fone:</label>
							<br />
							<input type="text" name="telefone" class="label" size="15" maxlength="30" value="<? echo $telefone ?>" id='telefone' <? echo "$readonly";?> />
						</td>
					</tr>
					<tr>
						<td><label for='fax'>Fax:</label>
							<br />
							<input type="text"  class="label" name="fax" size="15" id='fax' maxlength="13" value="<? echo $fax ?>"  />
						</td>
						<td><label for='contato'>Contato:</label>
							<br />
							<input type="text" class="label" name="contato" id='contato' size="30" maxlength="30" value="<? echo $contato ?>" style="width:100px" <? echo "$readonly";?> />
						</td>
					</tr>
					<tr>
						<td><label for='nome_fantasia'>Nome Fantasia:</label>
							<br />
							<input type="text" class="label" name="nome_fantasia"  id ='nome_fantasia' size="100" maxlength="50" value="<? echo $nome_fantasia ?>" style="width:250px" <? echo "$readonly";?> />
						</td>
					</tr>
					<tr>
						<td><label for='email'>E-mail:</label>
							<br />
							<input name="email" type="text" class="label" id="email" size="40" value='<?=$email?>' />
						</td>
						<td><label for='cep'>CEP:</label>
							<br />
							<input name="cep" type="text" class="label" id="cep" size="20" value='<?=$cep?>' />
						</td>
					</tr>
					<tr>
						<td><label for='endereco'>Endere&ccedil;o:</label>
							<br />
							<input name="endereco" type="text" class="label" id="endereco" size="50" maxlength='50' value='<?=$endereco?>' />
						</td>
						<td><label for='numero'>Número:</label>
							<br />
							<input name="numero" type="text" class="label" id="numero" size="20" maxlength='10' value='<?=$numero?>' />
						</td>
					</tr>
					<tr>
						<td><label for='complemento'>Complemento:</label>
							<br />
							<input name="complemento" type="text" class="label" id="complemento" size="30" maxlength='20' value='<?=$complemento?>' />
						</td>
						<td><label for='bairro'>Bairro:</label>
							<br />
							<input name="bairro" type="text" class="label" id="bairro" size="30" maxlength='40' value='<?=$bairro?>' />
						</td>
					</tr>
					<tr>
						<td><label for='cidade'>Cidade:</label>
							<br />
							<input name="cidade" type="text" class="label" id="cidade" size="40" maxlength='30' value='<?=$cidade?>' />
						</td>
						<td><label for='estado'>Estado:</label>
							<br />
							<select name="estado" id='estado' style='width:81px; font-size:9px'>
							<? $ArrayEstados = array('','AC','AL','AM','AP',
														'BA','CE','DF','ES',
														'GO','MA','MG','MS',
														'MT','PA','PB','PE',
														'PI','PR','RJ','RN',
														'RO','RR','RS','SC',
														'SE','SP','TO'
													);
								for ($i=0; $i<=27; $i++){
									echo"<option value='".$ArrayEstados[$i]."'";
									if ($estado == $ArrayEstados[$i]) echo " selected='selected'";
									echo ">".$ArrayEstados[$i]."</option>\n";
								}
							?>
							</select>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
	<br />
	<table width="100%" border="0" cellpadding="5" cellspacing="5" class="tabela_produtos">
		<tr class="menu_top">
			<td colspan="2">LINHAS QUE TRABALHA</td>
		</tr>
		<tr>
			<td align='left'>
				<font size="-2" >
					<input type="checkbox"  class="label" id='linha1' name="linha_1" value='BRANCA'          <?if(strlen($linha_1) > 0 or substr_count($linhas, 'BRANCA') > 0) echo "checked='checked'";?> /><label for='linha1'>BRANCA - adega, refrigeração - ar condicionado (split, janela,..).</label><br />
					<input type="checkbox"  class="label" id='linha2' name="linha_2" value='MARRON'          <?if(strlen($linha_2) > 0 or substr_count($linhas, 'MARRON') > 0) echo "checked='checked'";?> /><label for='linha2'>MARRON - áudio e video (DVD,MP3, MP4,...).</label><br />
					<input type="checkbox"  class="label" id='linha3' name="linha_3" value='ELETROPORTATEIS' <?if(strlen($linha_3) > 0 or substr_count($linhas, 'ELETROPORTATEIS') > 0) echo "checked='checked'";?> /><label for='linha3'>ELETROPORTÁTEIS - liquidificadores, ventiladores,...              </label><br />
					<input type="checkbox"  class="label" id='linha4' name="linha_4" value='INFORMATICA'     <?if(strlen($linha_4) > 0 or substr_count($linhas, 'INFORMATICA') > 0) echo "checked='checked'";?> /><label for='linha4'>INFORMÁTICA - notebook, monitores,...                             </label><br />
					<input type="checkbox"  class="label" id='linha5' name="linha_5" value='FERRAMENTAS'     <?if(strlen($linha_5) > 0 or substr_count($linhas, 'FERRAMENTAS') > 0) echo "checked='checked'";?> /><label for='linha5'>FERRAMENTAS                                                       </label><br />
					<input type="checkbox"  class="label" id='linha6' name="linha_6" value='OUTRAS'          <?if(strlen($linha_6) > 0 or substr_count($linhas, 'OUTRAS') > 0) echo "checked='checked'";?> /><label for='linha6'>OUTRAS. QUAIS?</label>&nbsp;<input type="text" class="label" id='linha6obs' name="linha_6_obs" size='50' />
				</font>
			</td>
		</tr>
	</table>

	<br />

	<table width="100%" border="0" cellpadding="5" cellspacing="5" class="tabela_produtos">
		<tr class="menu_top">
			<td colspan='2'>INFORMAÇÕES ADICIONAIS</td>
		</tr>

		<tr class="menu_top">
			<td align='right' width='300'><label for='funcionarios'>Quantidade de funcionários</label></td>
			<td align='left'><input type="text" name="funcionarios" id="funcionarios" class="label" size='10' value="<? echo "$funcionarios"; ?>" /></td>
		</tr>

		<tr class="menu_top">
			<td align='right' width='300'><label for='oss'>Quantidade de Ordem de Serviço mensal</label></td>
			<td align='left'><input type="text" name="oss" id="oss" class="label" size='10' value="<? echo "$oss"; ?>" /></td>
		</tr>

		<tr class="menu_top">
			<td align='right' width='300'><label for='fabricantes'>Quais as marcas sua empresa é autorizada atualmente?</label></td>
			<td align='left'><input type="text" class="label" name="fabricantes" id="fabricantes" size='40' value="<? echo "$fabricantes"; ?>" /></td>
		</tr>

		<tr class="menu_top">
			<td align='right' width='300'><label for='atende_cidades'>Sua empresa atende cidades próximas? Quais?</label></td>
			<td align='left'><input type="text" class="label" name="atende_cidade_proxima" id="atende_cidades" size='40' value="<? echo "$atende_cidade_proxima"; ?>" /></td>
		</tr>

		<tr class="menu_top">
			<td align='right' width='300'><label for='marca_nao_ser'>Quais as marcas sua empresa não gostaria de ser autorizada? Por quê?</label></td>
			<td align='left'><input type="text" class="label" name="marca_nao_autorizada" id="marca_nao_ser"  size='40' value="<? echo "$marca_nao_autorizada"; ?>" /></td>
		</tr>

		<tr class="menu_top">
			<td align='right' width='300'><label for='marca_ser'>Quais as marcas sua empresa gostaria de ser autorizada?</label></td>
			<td align='left'><input type="text" class="label" name="marca_ser_autorizada" id="marca_ser"  size='40' value="<? echo "$marca_ser_autorizada"; ?>" /></td>
		</tr>

		<tr class="menu_top">
			<td align='right' width='300'><label for='melhor_sistema'>Na sua opnião, qual o melhor Sistema Informatizado de Ordens de Serviço? Porque?</label></td>
			<td align='left'><input type="text" class="label" name="melhor_sistema" id="melhor_sistema"        size='40' value="<? echo "$melhor_sistema"; ?>" /></td>
		</tr>

		<tr>
			<td align='center' height='10' colspan='3'>&nbsp;</td>
		</tr>

		<tr class="menu_top">
			<td align='center' height='20' colspan='3' ><label>3 fotos digitais da sua loja (fachada, recepção e laboratório)</label></td>
		</tr>
		<tr>
			<td align='center' colspan='3'>
				<input type='file'  class="label" name='arquivo1' size='50' /><br /> 
				<input type='file'  class="label" name='arquivo2' size='50' /><br />
				<input type='file'  class="label" name='arquivo3' size='50' /><br />
			</td>
		</tr>
		<tr class="menu_top">
			<td align='center' colspan='3'><label for='descricao'>Descrição de sua Autorizada</label></td>
		</tr>
		<tr>
			<td align='center' colspan='3'><textarea class="label" name="descricao" id="descricao" rows="5" cols="50"><? echo mb_convert_encoding( "$descricao", 'UTF-8', 'ISO-8859-1' );?></textarea></td>
		</tr>
	</table>
	<br />
	<p align="center">
		<input type='hidden' name='btn_acao' value='' />
		<img src="../imagens/btn_gravar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_posto.btn_acao.value == '' ) { document.frm_posto.btn_acao.value='cadastrar' ; document.frm_posto.submit() } else { alert ('Aguarde submissão') }" alt="Gravar formulário" border='0' />
		<br />
		</p>
	</form>
		<a href="javascript:history.back()"><img src="imagens/voltar.jpg" width="69" height="22" alt='Voltar' border="0" align="right" /></a>
		<br />
	</td>
	</tr>
</table>
</body>
</html>

