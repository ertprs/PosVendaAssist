<?
include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';
require( '../../class_resize.php' );

$fabrica = 35;
$login_fabrica = 35;
$msg_sucesso = $_GET['msg_sucesso'];
$protocolo = $_GET['protocolo'];


$buscaProduto = @$_REQUEST['buscaProduto'];
if($buscaProduto == "buscaProduto"){

	$familia = $_REQUEST['familia'];
	$sql = "SELECT produto, descricao FROM tbl_produto WHERE familia = $familia ORDER BY descricao ASC;";
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

	if(strlen($aux_nome) == 0){
		$msg_erro = "Preencha o nome ";
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
			$res = pg_exec ($con,"BEGIN TRANSACTION");
			if( strlen($aux_estado)>0 and strlen($aux_cidade)>0){
				$sql = "SELECT tbl_cidade.cidade
							FROM tbl_cidade
							where tbl_cidade.nome = '$aux_cidade'
							AND tbl_cidade.estado = '$aux_estado'
							limit 1";
					$res = pg_exec($con,$sql);
					if(pg_numrows($res)>0){
						$cidade = pg_result($res,0,0);
					}else{
						$sql = "INSERT INTO tbl_cidade(nome, estado)values(upper('$aux_cidade'),'$aux_estado')";
						$res = pg_exec($con,$sql);
						$msg_erro .= pg_errormessage($con);
						$res    = pg_exec ($con,"SELECT CURRVAL ('seq_cidade')");
						$cidade = pg_result ($res,0,0);
					}
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
		$aux_nome          = mb_convert_encoding("$aux_nome"       , 'ISO-8859-1', 'UTF-8' );
		$aux_endereco      = mb_convert_encoding("$aux_endereco"   , 'ISO-8859-1', 'UTF-8' );
		$aux_numero        = mb_convert_encoding("$aux_numero"     , 'ISO-8859-1', 'UTF-8' );
		$aux_complemento   = mb_convert_encoding("$aux_complemento", 'ISO-8859-1', 'UTF-8' );
		$aux_bairro        = mb_convert_encoding("$aux_bairro"     , 'ISO-8859-1', 'UTF-8' );
		$aux_cep           = mb_convert_encoding("$aux_cep"        , 'ISO-8859-1', 'UTF-8' );
		$aux_cidade        = mb_convert_encoding("$aux_cidade"     , 'ISO-8859-1', 'UTF-8' );
		$aux_estado        = mb_convert_encoding("$aux_estado"     , 'ISO-8859-1', 'UTF-8' );
		$aux_email         = mb_convert_encoding("$aux_email"      , 'ISO-8859-1', 'UTF-8' );
		$aux_telefone      = mb_convert_encoding("$aux_telefone"   , 'ISO-8859-1', 'UTF-8' );
		$aux_msg           = mb_convert_encoding("$aux_msg"        , 'ISO-8859-1', 'UTF-8' );
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
								produto		,
								reclamado            ,
								nome                 ,
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
							$hd_chamado                    ,
							$aux_produto                    ,
							'$aux_msg'                    ,
							upper('$aux_nome')       ,
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
	$mensagem	   = $msg_erro;
	$nome          = $_POST['nome'];
	$endereco      = $_POST['endereco'];
	$numero        = $_POST['numero'];
	$complemento   = $_POST['complemento'];
	$bairro        = $_POST['bairro'];
	$cep           = $_POST['cep'];
	$cidade        = $_POST['cidade'];
	$estado        = $_POST['estado'];
	$email         = $_POST['email'];
	$fone          = $_POST['fone'];
	$tipo_contato  = $_POST['tipo_contato'];
	$msg           = $_POST['msg'];
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
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
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
				.opcao_central { background:url(http://cadence.morphy.com.br/img/bg_contato.jpg) repeat-x; width:636px; height:68px; float:left; border:1px solid #e6ecec; margin:40px 75px 0; }
				.opcao_central h2 { font-size:15px; font-family:"Trebuchet MS", Arial, Helvetica, sans-serif; color:#707c7c; width:303px; margin:12px 0 0 25px; }
				.opcao_central h2 em { font-family:Verdana, Geneva, sans-serif; font-size:12px; font-weight:normal; font-style:normal; }
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
			<script language="JavaScript" src="../../js/jquery-1.3.2.js"></script>
			<script language="JavaScript" src="../../js/jquery.maskedinput.js"></script>
			<script language="JavaScript">
				$(document).ready(function(){
					$("#telefone").maskedinput("(99) 9999-9999");
					$("#cep").maskedinput("99999-999");
				});

				function buscaCEP(cep) {
					$.ajax({
						type: "GET",
						url:  "ajax_cep.php",
						data: "cep="+escape(cep),
						cache: false,
						complete: function(resposta){
							results = resposta.responseText.split(";");
							if (typeof (results[1]) != 'undefined') $('#endereco').val(results[1]);
							if (typeof (results[2]) != 'undefined') $('#bairro').val(results[2]);
							if (typeof (results[3]) != 'undefined') $('#cidade').val(results[3]);
							if (typeof (results[4]) != 'undefined') $('#estado').val(results[4]);
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
			</script>
			
			<style>

			</style>

		<body>
			<div class='box_content' style='margin: 0 auto;'>
				<h1 style="color:#9ba6a6; font-size:28px; font-family:'MyriadProBold',Verdana, Geneva, sans-serif; padding:10px 0; margin: 0; padding-bottom: 0;">Central de Relacionamento.</h1>
				<p>Se voc&ecirc; deseja entrar em contato conosco, preencha o formul&aacute;rio abaixo e aguarde nosso retorno.</p>
				<form method="post"  action="<?=$PHP_SELF?>" method="post" name="form1" id="formCentral">
					
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
						<input type="text" name="nome" id="nome" value='<?php echo $aux_nome;?>' tabindex='1'></input>

						<label for="cep">CEP:</label>
						<input type="text" name="cep" id="cep" onblur="buscaCEP(this.value )" value='<?php echo $aux_cep;?>' tabindex='2'></input>

						<label for="endereco">Endere&ccedil;o:</label>
						<input type="text"  name="endereco" id="endereco"  value='<?php echo $aux_endereco;?>' tabindex='3'></input>

						<label for="complemento">Complemento:</label>
						<input type="text" name="complemento" id="complemento" value='<?php echo $aux_complemento;?>' tabindex='4'></input>

						<label for='estado'>Estado:</label>
						<select name='estado' id='estado' tabindex='5'>
							<?php
								foreach ($array_estado as $k => $v) {
									echo '<option value="'.$k.'"'.($aux_estado == $k ? ' selected="selected"' : '').'>'.$v."</option>\n";
								}
							?>
						</select>
						<label for='assunto'>Assunto:</label>
						<select name="assunto" id='assunto' style="width:710px;" tabindex='11'>
							<option value='0' selected> - selecione -</option>
							<option value='sugestao' <?php if($aux_assunto == 'sugestao') echo " selected ";?>>Sugestão</option>
							<option value='reclamacao_at' <?php if($aux_assunto == 'reclamacao_at') echo " selected ";?>>Reclamação da Assistência Técnica</option>
							<option value='reclamacao_empresa' <?php if($aux_assunto == 'reclamacao_empresa') echo " selected ";?>>Reclamação da Empresa</option>
							<option value='reclamacao_produto' <?php if($aux_assunto == 'reclamacao_produto') echo " selected ";?>>Reclamação de Produto/Defeito</option>
						</select>
						<p style="float:left; width:709px; margin:10px 0;">Se a d&uacute;vida for sobre produto, preencha tamb&eacute;m as op&ccedil;&otilde;es abaixo.</p>
						
						<label for='familia'>Família:</label>
						<select name='familia' id='familia'  onchange="buscaProduto(this.value )" tabindex='12'> 
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
						<input type="text" name="email" id="email" value='<?php echo $aux_email;?>' tabindex='6'></input>

						<label for="numero">N&uacute;mero:</label>
						<input type="text" name="numero" id="numero" value='<?php echo $aux_numero;?>' tabindex='7'></input>

						<label for="bairro">Bairro:</label>
						<input type="text" name="bairro" id="bairro" value='<?php echo $aux_bairro;?>' tabindex='8'></input>

						<label for="telefone">Telefone:</label>
						<input type="text" name="telefone" id="telefone" value='<?php echo $aux_telefone;?>' tabindex='9'></input>

						<label for="cidade">Cidade:</label>
						<input type="text" name="cidade" id="cidade" value='<?php echo $aux_cidade;?>' tabindex='10'></input>
						
						<label for='produto' style="margin-top:99px;" value='<?php echo $aux_produto;?>' >Produto:</label>
						<select name='produto' id='produto' tabindex='13'>
							<option></option>
						</select>
					</div>
					<p>  
						<label for="msg" style="display: block; width: 660px;">Mensagem</label>
						<textarea name="msg" id="msg" style="float: left; width: 698px;" tabindex='14'><?php echo $aux_msg;?></textarea>
					</p>
					<p style='display='block; float: none' class='clear'><input name="Enviar" type="submit"  id="Enviar" value="Enviar"></input></p>
				</form>
				<span class="opcao_central">
					<h2>Central de Relacionamento com o Cliente<em> Atendimento: Segunda &agrave; Sexta das 08h &agrave;s 18h.</em></h2>
					<img src="http://cadence.morphy.com.br/img/img_contato.png" width="43" height="42" alt=""/>
					<h3>(54) 3290 2200</h3>
				</span>
				<div class='clear'>&nbsp;</div>
			</div>
		</body>
	</html>
