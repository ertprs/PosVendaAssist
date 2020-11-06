<?

// ini_set('display_errors', 1);

include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';
require( '../../class_resize.php' );

include 'class/aws/s3_config.php';

include_once S3CLASS;
$fabrica = 35;
$login_fabrica = 35;
$msg_sucesso = $_GET['msg_sucesso'];
$protocolo = $_GET['protocolo'];

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

$buscaProduto = @$_REQUEST['buscaProduto'];
if($buscaProduto == "buscaProduto"){

	$familia = $_REQUEST['familia'];
	$sql = "SELECT produto, descricao FROM tbl_produto WHERE familia = $familia AND ativo ORDER BY descricao ASC;";
	$res = pg_exec($con,$sql);
	if (pg_numrows ($res) > 0) {
		for ($i=0; $i<pg_numrows ($res); $i++ ){
			$codigo = pg_result($res,$i,'produto');
			$descricao = pg_result($res,$i,'descricao');
			
			echo "<option value='$codigo'> $descricao</option>";
		}
	}else{
		echo "<option value='0'> Nenhum produto encontrada para esta família.</option>";
	}
	exit;
}
	

$Enviar = $_POST['Enviar'];
if(strlen($Enviar) > 0){

	$aux_nome        = trim($_POST['nome']);
	$aux_cpf		 = $_POST['cpf'];
	$aux_cpf 		 = preg_replace("/\D/","",$aux_cpf);
	$aux_endereco    = trim($_POST['endereco']);
	$aux_numero      = trim($_POST['numero']);
	$aux_complemento = trim($_POST['complemento']);
	$aux_estado      = trim($_POST['estado']);
	$aux_assunto     = trim($_POST['assunto']);
	$tipo_contato    = trim($_POST['assunto']);
	$aux_cidade      = trim($_POST['cidade']);
	$aux_cep         = trim($_POST['cep']);
	$aux_cep         = str_replace (".","",$aux_cep);
	$aux_cep         = str_replace ("-","",$aux_cep);
	$aux_cep         = str_replace (" ","",$aux_cep);
	$aux_email       = trim($_POST['email']);
	$aux_telefone    = trim($_POST['telefone']);
	$aux_msg         = trim($_POST['msg']);
	$aux_familia     = $_POST['familia'];
	$aux_produto     = $_POST['produto'];
	$aux_bairro      = trim($_POST['bairro']);

	$anexo_arquivo_1 = $_FILES['anexo_arquivo_1'];
	$anexo_arquivo_2 = $_FILES['anexo_arquivo_2'];
	$anexo_arquivo_3 = $_FILES['anexo_arquivo_3'];

	if(strlen($aux_nome) == 0){
		$msg_erro = "Preencha o nome ";
	}

	if(strlen($aux_cpf) == 0){
		$msg_erro = "Preencha o cpf/cnpj";
	} else {
		 $sql = "SELECT fn_valida_cnpj_cpf('{$aux_cpf}')";
		 $res = pg_query($con, $sql);
		if (strlen(pg_last_error()) > 0) {
			$msg_erro = "CPF/CNPJ Inválido! Digite corretamente o nº de seu CPF/CNPJ";
		} else {
			$sql = "SELECT tbl_hd_chamado.hd_chamado
					FROM tbl_hd_chamado
					JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
					WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
					AND tbl_hd_chamado_extra.cpf = '{$aux_cpf}'";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				$hd_chamado_existente = pg_fetch_result($res, 0, "hd_chamado");

				$msg_erro = "Identificamos que seu CPF já possui um protocolo de atendimento conosco.<br />
				Por favor, entre em contato com a nossa central de atendimento através do 0800-644-6442.<br />
				Número do protocolo: {$hd_chamado_existente}";
			}
		}
	}
	
	if(strlen($aux_email) == 0 AND strlen($msg_erro) == 0){
		$aux_email = "";
	}

	if(strlen($aux_endereco) == 0 AND strlen($msg_erro) == 0){
		$msg_erro = "Preencha o campo Endere&ccedil;o";
	}

	if(strlen($aux_nome) == 0 AND strlen($msg_erro) == 0){
		$msg_erro = "Preencha o campo N&uacute;mero";
	}

	if(strlen($aux_complemento) == 0 AND strlen($msg_erro) == 0){
		$aux_complemento = '';
	}

	if(strlen($aux_bairro) == 0 AND strlen($msg_erro) == 0){
		$msg_erro = "Preencha o campo Bairro";
	}

	if(strlen($aux_estado) == 0 AND strlen($msg_erro) == 0){
		$msg_erro = "Preencha o campo Estado";
	}

	if(strlen($aux_cep) == 0 AND strlen($msg_erro) == 0){
		$msg_erro = "Preencha o campo CEP";
	}


	if(strlen($aux_telefone) == 0 AND strlen($msg_erro) == 0){
		$msg_erro = "Preencha o campo Telefone";
	}

	if(strlen($aux_cidade) == 0 AND strlen($msg_erro) == 0){
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

	if(strlen($aux_assunto) < 2  AND strlen($msg_erro) == 0){
		$msg_erro = "Selecione um assunto";
	}

	if((strlen($aux_familia) == 0 OR $aux_familia == 0) AND strlen($msg_erro) == 0){
		$msg_erro = "Selecione uma família";
	}

	if((strlen($aux_produto) == 0 OR $aux_produto == 0) AND strlen($msg_erro) == 0){
		$msg_erro = "Selecione um produto";
	}

	if(strlen($aux_msg) == 0 AND strlen($msg_erro) == 0){
		$msg_erro = "Preencha o campo mensagem";
	}


	
	if(strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		/*$aux_nome          = mb_convert_encoding("$aux_nome"       , 'ISO-8859-1', 'UTF-8' );
		$aux_endereco      = mb_convert_encoding("$aux_endereco"   , 'ISO-8859-1', 'UTF-8' );
		$aux_numero        = mb_convert_encoding("$aux_numero"     , 'ISO-8859-1', 'UTF-8' );
		$aux_complemento   = mb_convert_encoding("$aux_complemento", 'ISO-8859-1', 'UTF-8' );
		$aux_bairro        = mb_convert_encoding("$aux_bairro"     , 'ISO-8859-1', 'UTF-8' );
		$aux_cep           = mb_convert_encoding("$aux_cep"        , 'ISO-8859-1', 'UTF-8' );
		$aux_cidade        = mb_convert_encoding("$aux_cidade"     , 'ISO-8859-1', 'UTF-8' );
		$aux_estado        = mb_convert_encoding("$aux_estado"     , 'ISO-8859-1', 'UTF-8' );
		$aux_email         = mb_convert_encoding("$aux_email"      , 'ISO-8859-1', 'UTF-8' );
		$aux_telefone      = mb_convert_encoding("$aux_telefone"   , 'ISO-8859-1', 'UTF-8' );
		$aux_msg           = mb_convert_encoding("$aux_msg"        , 'ISO-8859-1', 'UTF-8' );*/
		$titulo            = 'Atendimento interativo';
		$xstatus_interacao = "'Aberto'";

		$sql = "SELECT admin from tbl_admin where fale_conosco and ativo and fabrica = $login_fabrica";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res)>0){
			$atendendes = array();
			
			for ($i=0; $i < pg_num_rows($res); $i++) { 
				$atendentes[$i+1] = pg_fetch_result($res, $i, 0);
			}
			

		}

		$login_admin = $atendentes[rand(1,count($atendentes))];	// Seleciona um dos 'atendentes' de forma aleatória

		if(strlen($aux_email) == 0 AND strlen($msg_erro) == 0){
			$aux_email = "null";
		}

			#-------------- INSERT ---------------
			$sql = "INSERT INTO tbl_hd_chamado (
						admin                 ,
						data                  ,
						status                ,
						atendente             ,
						fabrica_responsavel   ,
						titulo                ,
						categoria             ,
						fabrica
					)values(
						$login_admin            ,
						current_timestamp       ,
						$xstatus_interacao      ,
						$login_admin            ,
						$login_fabrica          ,
						'$titulo'               ,
						'$tipo_contato'         ,
						$login_fabrica
				)";

			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
			$res    = pg_exec ($con,"SELECT CURRVAL ('seq_hd_chamado')");
			$hd_chamado = pg_result ($res,0,0);

			$fale_conosco = json_encode(array("fale_conosco" => "true"));

			$sql = "INSERT INTO tbl_hd_chamado_extra(
								hd_chamado           ,
								produto				 ,	
								reclamado            ,
								nome                 ,
								cpf					 ,
								endereco             ,
								numero               ,
								complemento          ,
								bairro               ,
								cep                  ,
								fone                 ,
								email                ,
								cidade               ,
								array_campos_adicionais
							)values(
							$hd_chamado              ,
							$aux_produto             ,
							'$aux_msg'               ,
							upper('$aux_nome')       ,
							upper('$aux_cpf')		 ,
							upper('$aux_endereco')   ,
							upper('$aux_numero')     ,
							upper('$aux_complemento'),
							upper('$aux_bairro')     ,
							upper('$aux_cep')        ,
							upper('$aux_telefone')   ,
							upper('$aux_email')      ,
							'$cidade'                ,
							'$fale_conosco'
							) ";
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);

			/* Upload de Anexo(s) */
			if(empty($msg_erro)){

				$s3 = new AmazonTC("callcenter", $login_fabrica);

				if(strlen($anexo_arquivo_1['name']) > 0 && $anexo_arquivo_1['size'] > 0){

					$pathinfo 	= pathinfo($anexo_arquivo_1['name']);
					$type  		= $pathinfo['extension'];

					$s3->upload("{$hd_chamado}-1", $anexo_arquivo_1, null, null);

				}

				if(strlen($anexo_arquivo_2['name']) > 0 && $anexo_arquivo_2['size'] > 0){

					$pathinfo 	= pathinfo($anexo_arquivo_2['name']);
					$type  		= $pathinfo['extension'];

					$s3->upload("{$hd_chamado}-2", $anexo_arquivo_2, null, null);

				}

				if(strlen($anexo_arquivo_3['name']) > 0 && $anexo_arquivo_3['size'] > 0){

					$pathinfo 	= pathinfo($anexo_arquivo_3['name']);
					$type  		= $pathinfo['extension'];

					$s3->upload("{$hd_chamado}-3", $anexo_arquivo_3, null, null);

				}

			}
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF?msg_sucesso=ok&protocolo=$hd_chamado");
		//echo "GRAVOU!!";
		//$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}


if($msg_sucesso =='ok') {
	$msg = "Contato Gravado com Sucesso! <br /> Número de protocolo: <strong>$protocolo</strong>";
	$msg_estilo='preta';
	$mensagem = $msg;
}

if(strlen($msg_erro) > 0){
	$msg_estilo = 'vermelha';
	if (strpos($msg_erro,"ERROR:") !== false) {
		$x = explode('ERROR:',$msg_erro);
		$msg_erro = $x[1];
	}
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}
	$mensagem	   	= $msg_erro;
	$nome          	= $_POST['nome'];
	$cpf 		   	= $_POST['cpf'];
	$endereco      	= $_POST['endereco'];
	$numero        	= $_POST['numero'];
	$complemento   	= $_POST['complemento'];
	$bairro        	= $_POST['bairro'];
	$cep           	= $_POST['cep'];
	$cidade        	= $_POST['cidade'];
	$estado        	= $_POST['estado'];
	$email         	= $_POST['email'];
	$fone          	= $_POST['fone'];
	$tipo_contato  	= $_POST['tipo_contato'];
	$msg           	= $_POST['msg'];
	$aux_cpf		= trim($_POST['cpf']);
	$consumidor_cpf_cnpj		= $_POST['consumidor_cpf_cnpj'];
 	//var_dump($aux_cpf);exit;
}

$array_estado = array("AC"=>"AC - Acre","AL"=>"AL - Alagoas","AM"=>"AM - Amazonas",
  "AP"=>"AP - Amapá", "BA"=>"BA - Bahia", "CE"=>"CE - Ceará","DF"=>"DF - Distrito Federal",
  "ES"=>"ES - Espírito Santo", "GO"=>"GO - Goiás","MA"=>"MA - Maranhão","MG"=>"MG - Minas Gerais",
  "MS"=>"MS - Mato Grosso do Sul","MT"=>"MT - Mato Grosso", "PA"=>"PA - Pará","PB"=>"PB - Paraíba",
  "PE"=>"PE - Pernambuco","PI"=>"PI - Piauí","PR"=>"PR - Paraná","RJ"=>"RJ - Rio de Janeiro",
  "RN"=>"RN - Rio Grande do Norte","RO"=>"RO - Rondônia","RR"=>"RR - Roraima",
  "RS"=>"RS - Rio Grande do Sul", "SC"=>"SC - Santa Catarina","SE"=>"SE - Sergipe",
  "SP"=>"SP - São Paulo","TO"=>"TO - Tocantins");
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
	<html>
		<head> 
			<style>
				html, body, #wrap { height:100%; }
				body { background:#fff; color:#a2acac; font-family:'MyriadProRegular', Verdana, Geneva, sans-serif; font-size:13px; }
				p { line-height:18px; }
				h2 { margin-bottom:5px; float:left; font-size:18px; color:#f98a05; font-family:'MyriadProSemibold', Verdana, Geneva, sans-serif; width:100%; }
				h1 { color:#9ba6a6; font-size:32px; font-family:'MyriadProBold',Verdana, Geneva, sans-serif; padding:20px 20px 10px; }
				.box_content { padding:0 20px 20px; width:789px; }

				/* Central */
				#formCentral { float:left; width:749px; border-top:1px solid #e8ecec; border-bottom:1px solid #e8ecec; padding:20px 0 20px 40px; margin-top:20px; }
				#formCentral div { float:left; width:330px; margin-right:40px; }
				#formCentral label { float:left; font-size:18px; color:#f98a05; font-family:'MyriadProSemibold', Verdana, Geneva, sans-serif; width:264px; }
				#formCentral input, #formCentral select { height:23px; border:1px solid #bdc4c4; margin:5px 0 15px; color:#a2acac; font-family:'MyriadProRegular', Verdana, Geneva, sans-serif; font-size:13px; }
				#formCentral input { width:327px; line-height:23px; padding:0 5px; } 
				#formCentral select { width:340px; padding:2px; } 
				#formCentral textarea { float:right; width:400px; height:153px; border:1px solid #bdc4c4; resize:none; padding:5px; color:#a2acac; font-family:'MyriadProRegular', Verdana, Geneva, sans-serif; font-size:13px; }
				#formCentral input[type="submit"]{background: #FC8100 url(http://cadence.morphy.com.br/img/bg_leia2.jpg) repeat-x; width:61px; border:none; color:#fff; font-family:"Trebuchet MS", Arial, Helvetica, sans-serif; font-size:12px; font-weight:bold; cursor:pointer; margin:10px 0; }
				.opcao_central { background:url(http://cadence.morphy.com.br/img/bg_contato.jpg) repeat-x; width:670px; height:68px; float:left; border:1px solid #e6ecec; margin:40px 75px 0; }
				.opcao_central h2 { font-size:13px; font-family:"Trebuchet MS", Arial, Helvetica, sans-serif; color:#707c7c; width:320px; margin:12px 0 0 25px; }
				.opcao_central h2 em { font-family:Verdana, Geneva, sans-serif; font-size:11px; font-weight:normal; font-style:normal; }
				.opcao_central h3 { float:right; color:#707c7c; font-size:24px; font-weight:bold; font-family:"Trebuchet MS", Arial, Helvetica, sans-serif; margin:17px 25px 0 0; }
				.opcao_central img { float:left; margin:12px 0 0 59px; }

				.clear { clear:both; }
				input.error, textarea.error, select.error { border: 1px dotted red !important; }

				.msg_erro{
					padding: 5px;
					margin: 10px 0;
					border: 1px solid #933030;
					background: #E0A1A1;
					color: #FCFCFC;
				}

				.sucesso{
					padding: 5px;
					margin: 10px 0;
					border: 1px solid #339900;
					background: #99CC99;
					color: #FCFCFC;
				}

			</style>
			<script type="text/javascript" src="../../js/jquery-1.5.2.min.js"></script>
			<script language="JavaScript" src="../../js/jquery.maskedinput.js"></script>
			<script language="JavaScript">
				$(document).ready(function(){
					$("#telefone").maskedinput("(99) 9999-9999");
					$("#cep").maskedinput("99999-999");

					if (($("#cpf").val().length == 14) || $("input[name=consumidor_cpf_cnpj]:checked").val() == "C") {
						$('#cpf').attr('maxLength', 14);

						$('#cpf').keypress (function(e){ 
							return txtBoxFormat($(this), '999.999.999-99', e);
						});
					}else{
						$('#cpf').attr('maxLength', 18);
						
						$('#cpf').keypress (function(e){ 
							return txtBoxFormat($(this), '99.999.999/9999-99', e);
						});
					}
					
					

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
						url: "callcenter_cadastra_cadence.php",
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
									$(option).attr({ value: value.cidade });
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

				function buscaProduto(familia) {
					if(familia != 0){
						$.ajax({
							type: "POST",
							url:  "callcenter_cadastra_cadence.php",
							data: "familia="+familia+"&buscaProduto=buscaProduto",
							success: function(resposta){
								$("#produto").html(resposta);
							}
						});
					}
				}

				buscaProduto(<?php echo $aux_familia?>);


				function fnc_tipo_atendimento(tipo) {
					$('#cpf').val('');
					if (tipo.value == 'C') {
						$('#cpf').attr('maxLength', 14);
						$('#cpf').keypress (function(e){
							return txtBoxFormat($(this), '999.999.999-99', e);
						});
					} else {
						if (tipo.value == 'R') {
							$('#cpf').attr('maxLength', 18);
							$('#cpf').keypress(function(e){
								return txtBoxFormat($(this), '99.999.999/9999-99', e);
							});
						}
					}
				}

				function txtBoxFormat(strField, sMask, evtKeyPress) {
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

					sValue = $(strField).val();

					sValue = sValue.toString().replace( "-", "" );
					sValue = sValue.toString().replace( "-", "" );
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
					fldLen = sValue.length;
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

					$(strField).val(sCod);

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
			</script>
			
			<style>

			</style>

		<body>
			<div class='box_content' style='margin: 0 auto;'>
				<h1 style="color:#9ba6a6; font-size:28px; font-family:'MyriadProBold',Verdana, Geneva, sans-serif; padding:10px 0; margin: 0; padding-bottom: 0;">Central de Relacionamento.</h1>
				<p>Se voc&ecirc; deseja entrar em contato conosco, preencha o formul&aacute;rio abaixo e aguarde nosso retorno.</p>
				<form method="post"  action="<?=$PHP_SELF?>" method="post" name="form1" id="formCentral" enctype="multipart/form-data">
					
					<?php
						if(strlen($msg_erro) > 0){
							echo "<p style='display: block; text-align: center;' class='clear msg_erro'>$msg_erro</p>";
						}

						if(strlen($msg_sucesso) > 0){
							echo "<p style='display: block; text-align: center;' class='clear sucesso'>$mensagem</p>";
						}
					?>
					<div>
						<label for="nome">Nome:</label>
						<input type="text" name="nome" id="nome" value='<?php echo $aux_nome;?>' tabindex='1' ></input>

						<label for="cep">CEP:</label>
						<input type="text" name="cep" id="cep" onblur="buscaCEP(this.value )" value='<?php echo $aux_cep;?>' tabindex='2'></input>
						
						<label for="cpf"  style="width:35px;" >CPF:</label>
						<input style="width:30px; padding:0px; float:inherit;  margin-top: 0px;" type='radio' name='consumidor_cpf_cnpj' id='consumidor_cfp' value='C'
							<?PHP
								
								if (strlen($aux_cpf) == 14 or strlen($aux_cpf) == 0) {
										echo "CHECKED";
								}
							?>
							onclick="fnc_tipo_atendimento(this)">

						<label for="consumidor_cfp" style="float:left; width:49px;">CNPJ:</label> 
						<input style="width:30px; padding:0px; margin-top: 0px;" type='radio' name='consumidor_cpf_cnpj'id='consumidor_cnpj' value='R'
							<?PHP
								if (strlen($aux_cpf) == 18) {
										echo "CHECKED";
								}
							?>
							onclick="fnc_tipo_atendimento(this)">

						<input type="text" name="cpf" id="cpf" value='<?php echo $aux_cpf;?>' tabindex='3'></input>
					
						<label for="endereco">Endere&ccedil;o:</label>
						<input type="text"  name="endereco" id="endereco"  value='<?php echo $aux_endereco;?>' tabindex='4'></input>

						<label for="complemento">Complemento:</label>
						<input type="text" name="complemento" id="complemento" value='<?php echo $aux_complemento;?>' tabindex='5'></input>

						<label for='estado'>Estado:</label>
						<select name='estado' id='estado' tabindex='6'>
							<option></option>
							<?php
								foreach ($array_estado as $k => $v) {
									echo '<option value="'.$k.'"'.($aux_estado == $k ? ' selected="selected"' : '').'>'.$v."</option>\n";
								}
							?>
						</select>

						<label for='assunto'>Assunto:</label>
						<select name="assunto" id='assunto' style="width:710px;" tabindex='12'>
							<option value='0' selected> - selecione -</option>
							<option value='sugestao' <?php if($aux_assunto == 'sugestao') echo " selected ";?>>Sugestão</option>
							<option value='reclamacao_at' <?php if($aux_assunto == 'reclamacao_at') echo " selected ";?>>Reclamação da Assistência Técnica</option>
							<option value='reclamacao_empresa' <?php if($aux_assunto == 'reclamacao_empresa') echo " selected ";?>>Reclamação da Empresa</option>
							<option value='reclamacao_produto' <?php if($aux_assunto == 'reclamacao_produto') echo " selected ";?>>Reclamação de Produto/Defeito</option>
						</select>
						<p style="float:left; width:709px; margin:10px 0;">Se a d&uacute;vida for sobre produto, preencha tamb&eacute;m as op&ccedil;&otilde;es abaixo.</p>
						
						<label for='familia'>Família:</label>
						<select name='familia' id='familia'  onchange="buscaProduto(this.value )" tabindex='13'> 
							<?php
								$sql = "SELECT familia, descricao FROM tbl_familia WHERE fabrica = $login_fabrica AND ativo IS TRUE ORDER BY descricao ASC;";
								$res = pg_exec($con,$sql);

								if(pg_numrows($res) == 0){
									echo "<option selected> Nenhuma família encontrada</option>";
								}else{
									echo "<option value='0' selected> - selecione - </option>";
									for ($i=0; $i<pg_numrows ($res); $i++ ){
										$codigo = pg_result($res,$i,'familia');
										$descricao = pg_result($res,$i,'descricao');

										echo "<option value='$codigo' ".($aux_familia == $codigo ? ' selected="selected"' : '').">$descricao</option>";
									}
								}
							?>
						</select>

					</div>
					<div>
						<label for="email">E-mail:</label>
						<input type="text" name="email" id="email" value='<?php echo $aux_email;?>' tabindex='7'></input>

						<label for="numero">N&uacute;mero:</label>
						<input type="text" name="numero" id="numero" value='<?php echo $aux_numero;?>' tabindex='8'></input>

						<label for="bairro">Bairro:</label>
						<input type="text" name="bairro" id="bairro" value='<?php echo $aux_bairro;?>' tabindex='9'></input>

						<label for="telefone">Telefone:</label>
						<input type="text" name="telefone" id="telefone" value='<?php echo $aux_telefone;?>' tabindex='10'></input>

						<label for="cidade">Cidade:</label>
						<select name="cidade" id='cidade' title='Selecione um estado para escolher uma cidade' tabindex='11'>
							<option></option>
						</select>
						
						<label for='produto' style="margin-top:186px;" value='<?php echo $aux_produto;?>' >Produto:</label>
						<select name='produto' id='produto' tabindex='14'>
							<option></option>
						</select>
					</div>

					<?php
					if ($_POST) {
						echo "<script>buscaCidade('{$aux_estado}', '{$aux_cidade}')</script>";
					}
					?>

					<p>  
						<label for="msg" style="display: block; width: 660px;">Mensagem</label>
						<textarea name="msg" id="msg" style="float: left; width: 698px;" tabindex='15'><?php echo $aux_msg;?></textarea>
					</p>

					<script>




						$(document).ready(function(){

							/* Deleta o Anexo */

							$('#deleteAnexo1').live('click', function(){
								$("input[name=anexo_arquivo_1]").val('');
								$("#desc1").html('');
								$('#desc1').hide();
							});

							$('#deleteAnexo2').live('click', function(){
								$("input[name=anexo_arquivo_2]").val('');
								$("#desc2").html('');
								$('#desc2').hide();
							});

							$('#deleteAnexo3').live('click', function(){
								$("input[name=anexo_arquivo_3]").val('');
								$("#desc3").html('');
								$('#desc3').hide();
							});

							/* Altera o Anexo */

							$('#alterarAnexo1').live('click', function(){
								$("input[name=anexo_arquivo_1]").val('');
								$("input[name=anexo_arquivo_1]").click();
							});

							$('#alterarAnexo2').live('click', function(){
								$("input[name=anexo_arquivo_2]").val('');
								$("input[name=anexo_arquivo_2]").click();
							});

							$('#alterarAnexo3').live('click', function(){
								$("input[name=anexo_arquivo_3]").val('');
								$("input[name=anexo_arquivo_3]").click();
							});

						});

						function addImg(box){

							var img_arr 	= new Array();
							var img 		= $("input[name=anexo_arquivo_"+box+"]").val();
							var ext 		= "";

							img_arr = img.split("\\");
							img = img_arr[img_arr.length - 1];

							img_arr = img.split(".");

							if(img_arr[1] != "png" && img_arr[1] != "jpg" && img_arr[1] != "jpeg" && img_arr[1] != "bmp" && img_arr[1] != "pdf" && img_arr[1] != "doc" && img_arr[1] != "txt"){
								alert("A extensão do Anexo não é Permitida");
								$("input[name=anexo_arquivo_"+box+"]").val('');
								return;
							}

							$('#desc'+box).show();
							$("#desc"+box).html("<img src='../img/delete.png' style='height: 15px; margin-bottom: -2px; cursor: pointer;' title='Excluir Anexo' id='deleteAnexo"+box+"' /> <img src='../img/edit.png' style='height: 17px; margin-bottom: -3px; cursor: pointer;' title='Alterar Anexo' id='alterarAnexo"+box+"') /> Imagem <strong>"+img+"</strong> adicionada com Sucesso");

						}

						function addAnexos(){

							var anexo1 = $("input[name=anexo_arquivo_1]").val();
							var anexo2 = $("input[name=anexo_arquivo_2]").val();
							var anexo3 = $("input[name=anexo_arquivo_3]").val();

							if(anexo1.length == 0){
								$("input[name=anexo_arquivo_1]").click();
							}

							if(anexo1.length > 0 && anexo2.length == 0){
								$("input[name=anexo_arquivo_2]").click();
							}

							if(anexo2.length > 0 && anexo3.length == 0){
								$("input[name=anexo_arquivo_3]").click();
							}

						}

					</script>

					<p>
						<div style='margin-top: 17px; width: 100%;'>
							<label style='width: 100%;'>Anexar Arquivo? <img src="../img/plus.gif" style="margin-bottom: -5px; cursor: pointer;" onclick="addAnexos()" /> <em style='color: #999; font-size: 14px;'>clique para adicionar</em> </label> <br /> <br />
							<div id="desc1" style="margin-bottom: 15px; width: 100%; display: none;"></div>
							<div id="desc2" style="margin-bottom: 15px; width: 100%; display: none;"></div>
							<div id="desc3" style="margin-bottom: 15px; width: 100%; display: none;"></div>
						</div>
					</p>

					<input type="file" name="anexo_arquivo_1" style="display: none;" onchange="addImg('1')" />
					<input type="file" name="anexo_arquivo_2" style="display: none;" onchange="addImg('2')" />
					<input type="file" name="anexo_arquivo_3" style="display: none;" onchange="addImg('3')" />

					<p style='display='block; float: none' class='clear'><input name="Enviar" type="submit"  id="Enviar" value="Enviar"></input></p>
				</form>
				<span class="opcao_central">
					<h2>Central de Relacionamento com o Consumidor<em> Atendimento: Segunda &agrave; Sexta das 08h &agrave;s 17h30min.</em></h2>
					<img src="http://ww2.telecontrol.com.br/assist/externos/imagens/contato.gif" width="43" height="42" alt=""/>
					<h3>0800-644-6442</h3>
				</span>
				<div class='clear'>&nbsp;</div>
			</div>
		</body>
	</html>
