<?php
//	if (basename($PHP_SELF) == basename(__FILE__)) {    //  Incluir só se
		include_once "dbconfig.php";
		include_once "includes/dbconnect-inc.php";
		include_once "autentica_usuario.php";
//	}

	include_once "helpdesk/mlg_funciones.php";

	$tipo_de_conta = array('Conta conjunta','Conta corrente','Conta individual','Conta jurídica','Conta poupança');
	$linhas_atendimento = array('Lavanderia', 'Refrigeração Convencional', 'Refrigeração Eletrônica', 'Ventilador de Teto');

	if ($_GET['ajax']=='banco' and isset($_GET['codigo'])) {
		$cod_banco = preg_replace('/\W/', '.', getPost('codigo')	);
		$w_banco = (is_numeric($cod_banco)) ? "codigo ~ E'^$cod_banco'" : "nome ~* '$cod_banco'";
		$sql = "SELECT codigo||';'||nome AS dados_banco FROM tbl_banco WHERE $w_banco";
		$res_b = pg_query($con, $sql);
		if (!is_resource($res_b)) die('ko');
		exit('ok;' . pg_fetch_result($res_b, 0, 0));
	}
	if ($_GET['ajax']=='rv_cidade' and isset($_GET['q'])) {
		$q = utf8_decode(anti_injection($_GET["q"]));
		$q = tira_acentos($q);
		$cidade = preg_replace('/(\W)/', '($1|.)', $q);
		$limite = anti_injection($_GET['limit']);
		$estado = anti_injection($_GET['estado']);
	    if (is_numeric($limite)) $limite = "LIMIT $limite";

		if (strlen($estado)==2) $w_estado = "estado = '$estado' AND";

		$sql_c = "SELECT cidade, estado FROM tbl_ibge
				   WHERE $w_estado TRANSLATE(TRIM(cidade),
								'áâàãäéêèëíîìïóôòõúùüçÁÂÀÃÄÉÊÈËÍÎÌÏÓÔÒÕÚÙÜÇ',
								'aaaaaeeeeiiiioooouuucAAAAAEEEEIIIIOOOOUUUC'
						   ) ~* '$cidade' ORDER BY estado, cidade $limite";

		$res_c = pg_query($con, $sql_c);
		if (!is_resource($res_c) or @pg_num_rows($res_c) == 0) exit();
		$cidades = pg_fetch_all($res_c);

		foreach ($cidades as $info_cidade) {
			extract($info_cidade);
			echo "$cidade|$estado\n";
	    }
		exit;
	}

	if ($_POST['ajax']=='frm') {
		extract($parametros = array_map('utf8_decode',array_map('anti_injection', $_POST)), EXTR_PREFIX_ALL, 'posto');
// 		extract($parametros = array_map('anti_injection', $_POST), EXTR_PREFIX_ALL, 'posto');
		if ($posto_posto != $login_posto) $msg_erro[] = 'Erro de autenticação de usuário. Recarregue a tela (F5), por favor.';
		if (!ValidateBRTaxID($posto_cnpj, false)) $msg_erro[] = 'CNPJ inválido!';
		if (!ValidateBRTaxID($posto_banco_fav_cnpj, false)) $msg_erro[] = 'CNPJ do Favorecido inválido!';

		$posto_ie				= (strtoupper($posto_ie) == 'ISENTO') ? strtoupper($posto_ie) : preg_replace('/\D/', '', $posto_ie);
		$posto_fone1			= preg_replace('/\D/', '', $posto_fone1);
		$posto_fone2			= preg_replace('/\D/', '', $posto_fone2);
		$posto_fax				= preg_replace('/\D/', '', $posto_fax);
		$posto_cep				= preg_replace('/\D/', '', $posto_cep);
		$posto_cnpj				= preg_replace('/\D/', '', $posto_cnpj);
		$posto_banco_fav_cnpj	= preg_replace('/\D/', '', $posto_banco_fav_cnpj);
		$posto_linhas           = array_map('anti_injection', $_POST['linhas']);

	/*  Validação   */
		if (strlen($posto_razao_social) < 5)					$msg_erro[] = 'Digite a Razão Social do seu Posto Autorizado';
		if (strlen($posto_nome_fantasia) < 5)					$msg_erro[] = 'Digite o nome Fantasia do seu Posto Autorizado';
		if (!is_numeric($posto_ie) and $posto_ie != 'ISENTO')	$msg_erro[] = 'Digite sua Inscrição Estadual';
		if ($posto_im == '')									$msg_erro[] = 'Digite sua Inscrição Municipal';
		if (strlen($posto_fone1)<10)							$msg_erro[] = 'Digite o telefone de contato';
		if (strlen($posto_fax)<10)								$msg_erro[] = 'Digite o número de fax';
		if ($posto_fone2 and strlen($posto_fone2)<10)			$msg_erro[] = 'O 2º telefone de contato parece incorreto. Por favor, digite novamente.';
		if (!$posto_cep or !$posto_endereco or !$posto_numero or
			!$posto_bairro or !$posto_cidade or !$posto_estado) $msg_erro[] = 'Endereço incompleto. Por favor, preencha todos os campos.';
		if (!$posto_banco			or !$posto_banco_agencia or
			!$posto_banco_conta		or !$posto_banco_fav or
			!$posto_banco_fav_cnpj	or
			strlen($posto_banco_conta) > 10 or
			!in_array($posto_tipo_conta, $tipo_de_conta))		$msg_erro[] = 'Dados bancários incompletos. Por favor, preencha corretamente TODOS os campos';
		if (!$posto_tipo_posto)                                 $msg_erro[] = 'Selecione o tipo de atendimento (consumidor/revenda)';
		if (!$posto_atende_cidades)								$msg_erro[] = 'Por favor, informa a(s) cidade(s) que atende.';
		if (!is_array($posto_linhas))							$msg_erro[] = 'Por favor, informe sua(s) área(s) de atuação';
		if (!in_array($posto_estado, array_keys($estados)))		$msg_erro[] = 'Selecione o Estado';
		if ($posto_suframa == 't' and
			strlen($posto_codigo_suframa) != 9)					$msg_erro[] = 'Código SUFRAMA incorreto.';
		if (!is_email($posto_email))                            $msg_erro[] = 'Digite um e-mail válido';

		if ($ajax == 'frm' and $msg_erro) exit('ko|'.implode('<br>', $msg_erro));// . print_r($parametros, true) . print_r(array_keys($estados), true)); // Retorno do AJAX

		/*	Formata os valores para inserir na tabela	*/
		$posto_fone1 = preg_replace('/(\d{2})(\d{4})(\d{4})$/', '($1) $2-$3', $posto_fone1);
		if ($posto_fone2) $posto_fone2 = preg_replace('/(\d{2})(\d{4})(\d{4})$/', '($1) $2-$3', $posto_fone2);
		if ($posto_fax)   $posto_fax   = preg_replace('/(\d{2})(\d{4})(\d{4})$/', '($1) $2-$3', $posto_fax);
		$posto_atende_cidades = implode(',', array_filter(explode(',', $posto_atende_cidades)));

		if (!$msg_erro) {
		//  Cadastro
			$posto_razao_social		= pg_quote($posto_razao_social);
			$posto_nome_fantasia	= pg_quote($posto_nome_fantasia);
			$posto_cnpj				= pg_quote($posto_cnpj);
			$posto_ie				= pg_quote($posto_ie);
			$posto_im				= pg_quote($posto_im);
		//	Contato
			$posto_fone1			= pg_quote($posto_fone1);
			$posto_fone2			= pg_quote($posto_fone2);
			$posto_fax				= pg_quote($posto_fax);
			$posto_email			= pg_quote($posto_email);
		//  Endereço
			$posto_endereco			= pg_quote($posto_endereco);
			$posto_numero			= pg_quote($posto_numero);
			$posto_complemento  	= pg_quote($posto_complemento);
			$posto_bairro			= pg_quote($posto_bairro);
			$posto_cep				= pg_quote($posto_cep);
			$posto_cidade			= pg_quote($posto_cidade);
			$posto_estado			= pg_quote($posto_estado);
			$posto_pais				= pg_quote($posto_pais);
		//  Banco
			$posto_banco			= pg_quote($posto_banco);
			$posto_banco_agencia	= pg_quote($posto_banco_agencia);
			$posto_banco_conta		= pg_quote($posto_banco_conta);
			$posto_banco_fav		= pg_quote($posto_banco_fav);
			$posto_banco_fav_cnpj	= pg_quote($posto_banco_fav_cnpj);
			$posto_tipo_conta		= pg_quote($posto_tipo_conta);
		//  Atende...
			$posto_tipo_posto       = pg_quote($posto_tipo_posto);
			$posto_atende_revendas	= pg_quote($posto_atende_revendas);
			$posto_atende_cidades	= pg_quote($posto_atende_cidades);
			$posto_atende_linhas	= pg_quote(implode(',', array_map('utf8_decode', $posto_linhas)));
			$posto_suframa			= pg_quote($posto_suframa);

			$sql = "BEGIN;
						DELETE FROM tbl_posto_atualizacao
						 WHERE posto = $login_posto
						   AND fabrica = $login_fabrica; /* Exclui o registro anterior */
						INSERT INTO tbl_posto_atualizacao (
						posto,
						fabrica,
						razao_social,
						nome_fantasia,
						cnpj,
						ie,
						im,
						fone1,
						fone2,
						fax,
						email,
						endereco,
						numero,
						complemento,
						bairro,
						cep,
						cidade,
						estado,
						pais,
						banco,
						agencia,
						conta,
						favorecido,
						favorecido_cnpj,
						tipo_conta,
						tipo_posto,
						atende_revendas,
						atende_cidades,
						linhas,
						suframa
					) VALUES (
						$posto_posto,
						$login_fabrica,
						$posto_razao_social,
						$posto_nome_fantasia,
						$posto_cnpj,
						$posto_ie,
						$posto_im,
						$posto_fone1,
						$posto_fone2,
						$posto_fax,
						$posto_email,
						$posto_endereco,
						$posto_numero,
						$posto_complemento,
						$posto_bairro,
						$posto_cep,
						$posto_cidade,
						$posto_estado,
						$posto_pais,
						'$posto_banco',
						$posto_banco_agencia,
						$posto_banco_conta,
						$posto_banco_fav,
						$posto_banco_fav_cnpj,
						$posto_tipo_conta,
						$posto_tipo_posto,
						$posto_atende_revendas,
						$posto_atende_cidades,
						$posto_atende_linhas,
						$posto_suframa
			)";
			$res_ins = @pg_query($con, $sql);
			if (!is_resource($res_ins)) {
				$err = pg_last_error($con);
				pg_query($con, 'ROLLBACK;');
				if (strpos($err, 'email_check')) exit('ko|Email inválido!');
				exit("ko|Dados inconsistentes. Confira os dados do seu cadastro e tente novamente<span style='display:none'>$sql<br>$err</span>");
			}
			if (pg_affected_rows($res_ins) == 0) {
				pg_query($con, 'ROLLBACK;');
				exit("ko|Dados inconsistentes, confira os dados do seu cadastro e tente novamente");
			}
			pg_query($con, 'COMMIT;');
			exit('OK');
		}
		exit('ko|Erro desconhecido. Por favor, contate com a Telecontrol');
	}

?>
<head>
	<script type="text/javascript" src="js/jquery-1.6.1.min.js"></script>
	<script type="text/javascript" src="js/jquery.maskedinput.min.js "></script>
	<script type="text/javascript" src="js/jquery.autocomplete.js"></script>
	<script type="text/javascript" src="js/jquery-ui.min.js"></script>
	<link type="text/css" rel="stylesheet" href="js/jquery.autocomplete.css">
	<link type="text/css" rel='stylesheet' href="css/css.css">

	<style type="text/css">
	html body {margin:0;padding:0}
	div.oculto {text-align: left;padding: 8px 16px;background-color: #f0f0fa;}
	#window_box {
	    display: block;
	    opacity: 0.9;
	    background-color: #ffffff;
	    position: relative;
		text-align: left;
	    top:  0;
	    left: 0;
        padding: 32px 0 1em 1em;
	/*	height: 70%;*/
		margin: 20px auto;
	    border: 2px solid #68769f;
        border-radius: 8px;
        -moz-border-radius: 8px;
		box-shadow: 3px 3px 3px #ccc;
		-moz-box-shadow: 3px 3px 3px #ccc;
		-webkit-box-shadow: 3px 3px 3px #ccc;
        overflow: hidden;
		z-index: 10000;
		width:800px;
		*width:800px;
		_width:800px;
		_margin: 1em 15%;
		*margin: 1em 15%;
	}
	#window_box:hover {
		opacity: 1;
		box-shadow: 5px 4px 5px grey;
		-moz-box-shadow: 5px 4px 5px grey;
		-webkit-box-shadow: 5px 4px 5px grey;
	}
	#window_box #ei_container p {
		font-size: 12px;
	    padding: .5ex 1ex;
		overflow-y:auto;
	}
	#window_box #ei_header {
		position: absolute;
		top:	0;
		left:	0;
		margin:	0;
		width: 100%;
		_width: 798px;
		*width: 798px;
		height:28px;
		border-radius: 7px 7px 0 0 ;
        -moz-border-radius: 7px 7px 0 0 ;
        -webkit-border-radius: 7px 7px 0 0;
		background-image: url('admin/imagens_admin/azul.gif');    /* IE, Opera */
		background-image: -moz-linear-gradient(top, #b4bbce, #68769f 6px, #68769f 15px, #7889bb);
		background-image: -webkit-gradient(linear,  0 0, 0 100%,
											from(#b4bbce),
												color-stop(0.07,#68769f),
												color-stop(0.20,#68769f),
											to(#7889bb));
	    padding: 2px 1em;
	    color: white;
	    font: normal bold 13px Segoe UI, Verdana, MS Sans-Serif, Arial, Helvetica, sans-serif;
		cursor: move;
	}
	#window_box #ei_container {
        background-color: #fdfdfd;
		margin: 1px;
		padding: 0;
		padding-bottom: 1ex;
		overflow-y: auto;
        overflow-x: hidden;
	    height: 480px;
		font-size: 11px;
		color: #313452;
		width: 100%;
		position:relative;
	}
	#window_box #fechar_msg {
		position: absolute;
		top: 4px;
		right: 5px;
		width: 16px;
		height:16px;
		font: normal bold 12px Verdana, Arial, Helvetica, sans-serif;
		color:white;
	    cursor: pointer;
		margin:0;padding:0;
		vertical-align:top;
		text-align:center;
		background-color: #f44;
		border:	1px solid #d00;
		border-radius: 3px;
		-moz-border-radius: 3px;
		box-shadow: 2px 2px 2px #900;
		-moz-box-shadow: 1px 1px 1px #900;
		-webkit-box-shadow: 2px 2px 2px #900;
	}
	#legenda {
		width: 95%;
		display: none;
		margin:auto;
		background-color: #fcc;
		color:darkred;
	}
	#ei_container #msgErro {
		background-color: rgba(255, 127, 127, 0.7);
		color: #fff;
		display: none;
		font-weight:bold;
		font-size: 11px;
		border: 2px solid red;
		border-radius: 5px;
        -moz-border-radius: 5px ;
		box-shadow: 2px 2px 3px #005;
        -moz-box-shadow: 2px 2px 3px #005;
        -webkit-box-shadow: 2px 2px 3px #005;
		width: 700px;
		margin: auto;
		padding: 0.6em 2em;
	}
	.error {font-size: 11px!important}
	#window_box form label.error {color:darkred;font-weight:bold;}
	#window_box form {line-height:1.8em;}
	#ei_container form input[type=text] {width:170px}
	#ei_container form label {
		color: #009;
		text-align:right;
		width: 130px;
		display:inline-block;
		_zoom:1;
	}
	#ei_container form fieldset {
		margin:	2em 10px 2em 0;
		border: 1px solid #d3d3d3;
		border-radius: 6px;
		_padding-bottom: 0.5em;
		*padding-bottom: 0.5em;
		*position:relative;
	}
	#ei_container form #fs_end label {width:70px}
	#ei_container form #fs_at #suframa_sim {display:none}
	#ei_container form #fs_banco label {width:70px}
	#ei_container form fieldset#fs_linhas {
		float: right;
		width: 250px;
	}
	#ei_container form fieldset#fs_at label {
		width:auto;
		text-align: left;
		display: inline;
	}
	#ei_container form fieldset legend {
		border-radius: 4px 4px 0 0;
		-moz-border-radius: 4px 4px 0 0;
		background-color: #d3d3d3;
		position:relative;
		top: -9px;
		height: 16px;
		-o-top: -16px;
		padding: 0 4px 2px 4px;
		font-weight: bold;
		color: #333;
	}
	</style>

<!--[if IE]>
	<!-- Teste -->
  	<style type="text/css">
		#ei_container #msgErro {
			color: #900;
			background-color: #ff8080;
		}
	</style>
<![endif]-->

    <script type="text/javascript">
	$().ready(function() {
		$('#window_box').draggable({handle: '#ei_header', distance: 16, containment: 'parent', cursor: 'move'});
		$('#frm_at_posto').submit(function() {
			var required_error = 0;
			var c = 0;
			$('.required,textarea:visible').each(function(idx) {
				if ($(this).val() == '') {
					var campo = $(this);
					campo.css('background-color','#fcc')
							.focus(function() {
								$(this).css('background-color','#ccf')
							})
							.change(function() {
								if ($(this).val() == '') {
									$(this).css('background-color','#fcc').focus();
									return false;
								}
								$(this).css('background-color','#cfc')
										.unbind('focus');
					});
					if (c++ == 0) campo.focus();
					required_error++;
				} else {
					$(this).css('background-color','#f0f0f0');
				}
			});
			if (required_error > 0) {
				$('#legenda').show();
				$('#msgErro').hide();
				return false;
			}

			var info_posto = $(this).serialize();
			$('#msgErro').html('').hide('fast');
			$.post('<?=basename(__FILE__)?>',
					info_posto+'&ajax=frm',
					function(ret) {
						retorno = ret.split('|');
						if (retorno[0] != 'OK') {
							$('#void').focus();
							$('#legenda').hide('fast');
							$('#msgErro').html(retorno[1]).slideDown('fast').addClass('erro');
							$('#razao_social').focus();
						}
						if (retorno[0] == 'OK') {
// 							$('#msgErro').html(retorno[1]).show();
							alert('Seus dados foram cadastrados com sucesso! Obrigado.');
							$('#window_box').hide('fast').remove();
							window.location.reload();
						}
			});
			return false; //Para não enviar o formulário!!
		});
		$.mask.definitions['0']='[0-9]?'; // Adicoina '0' como máscara para número OPCIONAL
		$.mask.definitions['A']='[A-Z]';    // Letra maiúscula
		$.mask.definitions['Z']='[a-zA-Z]?';
		$.mask.definitions['G']='\.?'; // Separador de milhares, opcional
		$.mask.definitions['D']=',?'; // Separador de decimais, opcional
		$.mask.definitions['·']='.?'; // Qualquer caractere
		$('#cnpj,#favorecido_cnpj').mask('99.999.999/9999-99');
		$('#cep').mask('99999-999')
				 .change(function() {
				 	$('#endereco').val('Aguarde...');
				 	$.get('/ajax_cep.php',
							{'cep': $(this).val().replace(/\D/, '')},
							function(data) {
								resultado = data.split(';');
								if (resultado[0] != 'ok') {
									$('#endereco').val('').focus();
									return false;
								}
								if (typeof (resultado[1]) != 'undefined') $('#endereco').val(resultado[1]);
								if (typeof (resultado[2]) != 'undefined') $('#bairro').val(resultado[2]);
								if (typeof (resultado[3]) != 'undefined') $('#cidade').val(resultado[3]);
								if (typeof (resultado[4]) != 'undefined') $('#estado').val(resultado[4]);
								$('#numero').focus();
							});
		}).keyup(function(ev) {
			if (ev.keyCode == 9 || ev.keyCode == 16) return false; // Evita que quando o campo esteja preenchido, não seja possível manter o foco
			var q = $(this).val();
				if (q.match(/^\d{5}.\d{3}$/) != null) {
					$('#cep').blur();
				}
		});

		$('#pais').mask('aa');
		$('#fone1,#fone2,#fax').mask('(99) 9999-9999');

		$('#banco').blur(function() {
			var q = $(this).val();
			if (q.length<3) return true;
			$.get('posto_atualizacao_dados.php',
				  'ajax=banco&codigo='+q,
				  function(data) {
					if (data.substr(0,2) != 'ok') {
						$('#nome_banco').addClass('error').text('Entidade bancária "'+q+'" não localizada. Por favor, digite o primeiro grupo de números da conta.');
						$('#banco').val('').focus();
						return true;
					}
					dados = data.split(';');
					$('#banco').val(dados[1]);
					$('#nome_banco').removeClass('error').text(dados[2]);
					$('#agencia').focus();
			});
		}).keyup(function(ev) {
			if (ev.keyCode == 9 || ev.keyCode == 16 || (ev.keyCode == 9 && ev.shiftKey)) return false; // Evita que quando o campo esteja preenchido, não seja possível manter o foco
			var q = $(this).val();
				if (q.match(/^\d{3}$/) != null) {
					$('#banco').blur();
				}
		});
		$('#cidade').autocomplete('posto_atualizacao_dados.php', {
			minChars: 3,
			delay: 250,
			width: 350,
			max: 20,
			matchContains: true,
			extraParams: {
				ajax: 'rv_cidade',
				estado: function() {return $('#estado option:selected').val();}
			},
			formatItem: function(row) {return row[0] + " - " + row[1];},
			formatResult: function(row) {return row[0];}
		}).result(function(event, data, formatted) {
			$("#estado").val(data[1]);
		});

		$('#atende_cidades').autocomplete('posto_atualizacao_dados.php', {
			minChars: 3,
			delay: 250,
			width: 350,
			max: 20,
			multiple: true,
			matchContains: true,
			extraParams: {ajax: 'rv_cidade'},
			formatItem: function(row) {return row[0] + " (" + row[1] + ')';},
			formatResult: function(row) {return row[0];}
		});

		$('#atende_suframa').change(function() {
			$('#suframa_sim').toggle('fast');
			if ($('#suframa').is(':hidden') == false) $('#suframa').focus();
		});
		$('#tipo_posto').change(function() {
			if ($(this).val()=='R') {
				$('#atende_revendas').slideDown('fast').removeAttr('disabled').focus();
				return true;
			}
			$('#atende_revendas').slideUp('fast').attr('disabled', 'disabled');
		});
		$('#frm_at_posto input').not('#email,').not('#cidade').blur(function(){$(this).val($(this).val().toUpperCase())});
		$('#razao_social').focus();
		$('#mark_all').click(function () {
                 	if ($(this).is(':checked')) {
					 	$('#fs_linhas > input:checkbox').attr('checked','checked');
						$('#mark_all_label').text('Desmarcar todos o ítens');
					}
                 	if ($(this).is(':checked')==false) {
						$('#fs_linhas > input:checkbox').removeAttr('checked');
						$('#mark_all_label').text('Selecionar todos o ítens');
					}
                 });
		$('#swap_mark').click();
	});
    </script>
  </head>
  <body>
	 <div id="window_box">
		<div id="ei_header"><img src='/img/favicon.ico' style='padding: 4px 1ex 0 0' />
			GERÊNCIA DE ASSISTÊNCIA TÉCNICA - Atualização do Cadastro do Posto
		</div>
		<div id="fechar_msg" title='Deshabilitado'>X</div>
	 	<div id="ei_container">
			<h1>GERÊNCIA DE ASSISTÊNCIA TÉCNICA</h1>
			<h3>Atualização do Cadastro do Posto Autorizado</h3>
			<input type="text" id="void" tabindex='0' style='background-color:white;border:0 solid white;color:white' readonly />
			<p>
<span class="Apple-style-span" style="border-collapse: separate; color: rgb(0, 0, 0); font-family: 'Lucida Bright'; font-style: normal; font-variant: normal; font-weight: normal; letter-spacing: normal; line-height: normal; orphans: 2; text-align: -webkit-auto; text-indent: 0px; text-transform: none; white-space: normal; widows: 2; word-spacing: 0px; -webkit-border-horizontal-spacing: 0px; -webkit-border-vertical-spacing: 0px; -webkit-text-decorations-in-effect: none; -webkit-text-size-adjust: auto; -webkit-text-stroke-width: 0px; font-size: medium; "><span class="Apple-style-span" style="color: rgb(51, 51, 51); font-family: 'Segoe UI', Arial, Helvetica, Verdana, sans-serif; font-size: 12px; ">Caro autorizado,<br><br>Solicitamos o preenchimento das informações cadastrais contidas na ficha de atualização cadastral para atualização das informações em nosso banco de dados. O preenchimento da ficha é obrigatório para que você volte a ter acesso ao sistema.<br><br>Solicitamos o correto preenchimento das informações, pois é através destas que as atividades administrativas / financeiras (emissão de nota fiscal, pagamentos, etc) serão desenvolvidas.<br><br>A veracidade das informações fornecidas é de inteira responsabilidade do posto autorizado<br><br>Atenciosamente,<br><br>Latinatec Ltda</span></span>

			</p>
			<div id="msgErro">
			</div>
			<form action="" id='frm_at_posto' method='post' autocomplete='off'>
				<input type="hidden" name="posto" value="<?=$login_posto?>" />
				<p id="legenda">Os campos em vemelho são obrigatórios.</p>
				<fieldset>
					<legend>Informações Cadastrais</legend>
					<label for="razao_social">Razão Social do Posto</label>
						<input type="text" style='width:290px' tabindex='1' maxlength='150' class='required'
						placeholder='Digite a Razão Social do seu Posto' name="razao_social" id="razao_social" />
					<label for="cnpj">CNPJ</label>
						<input type="text" name="cnpj" id="cnpj" placeholder='CNPJ do Posto'
							class='required' tabindex='2'
							mask='99.999.999/9999-99' />
					<br>
					<label for="nome_fantasia">Nome Fantasia</label>
						<input type="text" style='width:290px' tabindex='3' maxlength='50' name="nome_fantasia"
							  class='required' id="nome_fantasia" />
					<label for="ie">Inscrição Estadual</label>
						<input type="text" name="ie" tabindex='4' class='required' id="ie" />
					<label for=""></label>
					<input style='width:290px;visibility:hidden' disabled>
					<label for="im">Inscrição Municipal</label>
						<input type="text" name="im" id="im" class='required' tabindex='5' />
				</fieldset>
				<fieldset id='fs_tels'>
					<legend>Dados de Contato</legend>
					<label for="fone1">Telefone de Contato</label>
						<input type="text" name="fone1" id="fone1" tabindex='6' class='required' placeholder='Telefone de contato' />
					<label for="fax" style='width:8em'>Fax</label>
						<input type="text" name="fax" id="fax" tabindex='7' class='required' />
					<br>
					<label for="fone2">Telefone de Contato</label>
						<input type="text" name="fone2" id="fone2" tabindex='8' placeholder='Telefone alternativo' value='55' />
					<label for="email" style='width:8em'>E-Mail</label>
						<input type="text" name="email" style='width:330px' tabindex='9' id="email" class='required'
						placeholder='E-Mail para contato (Telecontrol e <?=$login_fabrica_nome?>)' />
				</fieldset>
				<fieldset id='fs_end'>
					<legend>Endereço do Posto</legend>
					<label for="endereço">Logradouro</label>
						<input type="text" style='width:300px' class='required' name="endereco" id="endereco" tabindex='10' />
					<label for="numero" style='width:64px'>Nº</label>
						<input type="text" name="numero" id="numero" class='required' maxlength='10' style='width:6em' tabindex='11' />
					<label for="complemento" style='width:106px'>Complemento</label>
						<input type="text" name="complemento" maxlength='20' style='width:104px' id="complemento" tabindex='12' />
					<br>
					<label for="cep">CEP</label>
						<input type="text" name="cep" id="cep" style='width:80px' class='required' tabindex='13' />
					<label for="bairro" style='width:62px'>Bairro</label>
						<input type="text" name="bairro" id="bairro" class='required' maxlength='40' style='width:150px' tabindex='14' />
					<label for="cidade" style='width:64px'>Cidade</label>
						<input type="text" name="cidade" id="cidade" class='required' style='width:282px' tabindex='15' />
					<br>
					<label for="estado">Estado (UF)</label>
					<select name="estado" id="estado" tabindex='16' class='required' style='width:160px'>
						<option value=""></option>
					<?
					foreach($estados as $estado=>$nome) {
						$sel = ($posto_estado == $estado)?' SELECTED':'';
						echo str_repeat("\t", 6).
							"<option$sel value='$estado'>$nome</option>\n";
					}?>
					</select>
					<label for="pais" style='width:110px;'>País</label>
						<input type="text" name="pais" id="pais" value='BR' style='width:2em' pattern='[A-Z]{2}' tabindex='17' />
				</fieldset>
				<fieldset id='fs_banco'>
					<legend>Dados Bancários</legend>
					<label for="banco">Banco</label>
						<input type="text" name="banco" id="banco" class='required' style='width:10em' tabindex='18' />
						<span id="nome_banco" style='margin-left:2em;'></span>
					<br>
					<label for="agencia">Agência</label>
						<input type="text" name="banco_agencia" id="agencia" class='required' tabindex='19' />
					<label for="conta" style='width:135px'>Nº de Conta</label>
						<input type="text" name="banco_conta" id="conta" class='required' maxlength='10' style='width:140px' tabindex='20' />
					<label for="tipo_conta" style='width:60px'>Tipo</label>
					<select name="tipo_conta" id="tipo_conta" class='required' tabindex='21' title='Selecione o tipo de conta'>
						<option value=""></option>
						<option>Conta jurídica</option>
						<option>Conta corrente</option>
						<option>Conta individual</option>
						<option>Conta conjunta</option>
						<option>Conta poupança</option>
					</select>
					<br>
					<label for="favorecido">Favorecido</label>
						<input type="text" name="banco_fav" id="favorecido" class='required' tabindex='22' />
					<label for="favorecido_cnpj" style='width:135px'>CNPJ do Favorecido</label>
						<input type="text" name="banco_fav_cnpj" id="favorecido_cnpj" class='required' tabindex='23' style='width:140px' />
				</fieldset>
				<fieldset id='fs_at'>
					<legend>Atendimento</legend>
					<p>Selecione o que corresponda ao(s) tipo(s) de atendimento que o seu Posto oferece:</p>
					<fieldset id='fs_linhas' style='column'>
						<legend>Linhas de atendimento</legend>
				<?
						foreach($linhas_atendimento as $temp_linha) {
							echo "<input type='checkbox'  tabindex='" . (29 + $i) . "' name='linhas[]' id='linha_$i' value='$temp_linha' />&nbsp;";
							echo "<label for='linha_" . $i++ . "'>$temp_linha</label><br>";
							//if (!is_even($i)) echo ('<br>');
						}
				?>
					</fieldset>
					<label>Tipo de atendimento</label>&nbsp;
					<select name="tipo_posto" id="tipo_posto" tabindex='24'>
						<option value="C">Consumidor Final</option>
						<option value="R">Revendas</option>
						<option value="A">Consumidor e Revendas</option>
					</select>
					<br>
					<textarea name="atende_revendas" id="atende_revendas" style='display:none;width:420px;margin:2px' rows='4'
						  title='Digite as Revendas atendidas pelo seu Posto' tabindex='25'></textarea>
					<br>
					<input type="checkbox" name="atende_suframa" id="atende_suframa" tabindex='26' />
					<label for="atende_suframa">Atende <acronym title='Superintendência da Zona Franca de Manaus'>SUFRAMA</acronym>?</label>
					<div id='suframa_sim'>
					<label style='display:inline' for="codigo_suframa">Nº Inscrição no <acronym title='Superintendência da Zona Franca de Manaus'>SUFRAMA</acronym></label>
						<input type="text" name="suframa" id="suframa" maxlength='9' style='width: 8em' tabindex='27' />
					</div>
					<br><br>
					<label for="atende_cidades" valign='top'>Cidades que o seu Posto atende</label><br>
						<textarea rows='4' cols='60' name="atende_cidades" class='required' style='width: 420px' id="atende_cidades"
								  title='Digite as cidades separándo-as com vírgula' tabindex='28'></textarea>
				</fieldset>
				<div style='text-align:center;margin:auto;'>
					<button type='submit' style='cursor:pointer'>Gravar</button>
					<button type='reset'  style='cursor:pointer'>Limpar Formulário</button>
				</div>
			</form>
		</div>
	 </div>
	 </div>
<?  // O usuário não vai poder usar o sistema enquanto não responder o questionário
include 'rodape.php';
exit;?>
