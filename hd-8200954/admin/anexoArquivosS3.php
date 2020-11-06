<?php
/**************************************************************
 * Programa para teste das novas funções de anexo de arquivos *
 * no S3 para OS, OS Revenda, OS Sedex e Extratos.            *
 **************************************************************/
// error_reporting(E_ALL);

define ('ASSIST',   dirname(__DIR__). DIRECTORY_SEPARATOR);
define ('HELPDESK', ASSIST . 'helpdesk/');

if (!strpos(__FILE__, 'mlg')) {
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'autentica_admin.php';
	include_once HELPDESK . "mlg_funciones.php";
} else {
	$login_fabrica = $_REQUEST['fabrica'];
	if ($login_fabrica == '') die("Não definiu a fábrica");
	if (!is_numeric($login_fabrica)) die("Fábrica inválida!");
	require_once '/var/www/assist/www/dbconfig.php';
	require '/var/www/assist/www/includes/dbconnect-inc.php';
	include_once "/var/www/telecontrol/www/mlg/mlg_funciones.php";
}

require_once ASSIST . 'anexaNF_inc.php';

//  Exclui a imagem da NF
if ($_REQUEST['ajax'] == 'excluir_nf') {
	$img_nf = $_REQUEST['imagem'];
	if ($img_nf == '') exit('KO|Não foi informado o nome do arquivo!');

	$excluiu = ($e=excluirNF($img_nf)) ? 'OK|Imagem excluída' : 'KO|'.$msgs_erro[$excluiu];
	if (is_null($e)) exit("KO|Arquivo não encontrado no servidor");
	exit($excluiu);
}//	FIM	Excluir	imagem

/**
 * 	AJAX para tratar a consulta de OS
 **/
if ($_POST['ajax'] == 'consulta') {
	extract(array_filter($_POST, 'anti_injection'));
	//print_r($_POST);

	if ($tipo) {
		//print_r($a_tiposAnexo[$tipo]);
		if (strpos(TIPOS_ANEXO, $tipo)===false) return false;

		extract($a_tiposAnexo[$tipo]);

	} else {
		$os_revenda = e_OS_revenda($os);

		if ($os_revenda !== false) {
			extract($a_tiposAnexo['r']);
		} else {
			extract($a_tiposAnexo['o']);
		}
	}

	$num_os	 = preg_replace('/\D/', '', $os);

	$table = "tbl_$tbl_r";

	$id_posto = ($tipo == 's') ? 'posto_origem' : 'posto';

	$sql = "SET DateStyle TO SQL, European;
			SELECT $campo AS os,
				   $campoData::date AS data_digitacao, codigo_posto, nome_fantasia, 
				   'Teste' AS tipo
			  FROM $table
			  JOIN tbl_posto_fabrica ON tbl_posto_fabrica.fabrica = $table.fabrica
									AND tbl_posto_fabrica.posto   = $table.$id_posto
			 WHERE $campo  = $os
			   AND $table.fabrica = $login_fabrica";

	$res = pg_query($con, $sql);

	if (!is_resource($res)) die("Erro ao consultar o Banco de dados!\n\n$sql");

	if (!pg_num_rows($res)) die("Não há $tipoAnexo com este número!\n\n$sql");

	if ($campo=='os_revenda') {
		$sql_rev      = "SELECT COUNT(*) FROM tbl_os_revenda_item WHERE os_revenda = $os";
		$total_os_rev = pg_fetch_result(pg_query($con, $sql_rev), 0, 0);
	}

	$dados_os = pg_fetch_assoc($res, 0);

	// OS Revenda explodida
	$dados_os['tipo'] = ($campo == 'os_revenda' and $total_os_rev) ? "OS Rev. ($total_os_rev OS)" : $tipoAnexo;

	//echo "Prefixo: $tipo, Num: $os\n";

	$temAnexos = temNF($os, 'bool', $tipo);
	//var_dump($temAnexos);

	if (is_numeric($temAnexos))
		die("KO|" . $msgs_erro[$temAnexos]);

	$dados_os['arq'] =  ($temAnexos) ? temNF($tipo . $os, 'linkEx') : 'Sem anexo(s)';

	extract($dados_os);
	die("
<tr>
	<td>$os</td>
	<td>$data_digitacao</td>
	<td>$codigo_posto</td>
	<td>$nome_fantasia</td>
	<td>$tipo</td>
	<td class='anexosOS'>$arq</td>
</tr>
<!-- $sql -->
");
}

/*   AJAX tratar imagem enviada pelo Ajax   */
if ($_REQUEST['ajax']=='fileupload') {

	//pre_echo($_POST);
	$sua_os  = trim($_REQUEST['os']);
	$tipo    = $_REQUEST['tipo']; //Para saber que tipo de anexo é...
	$arquivo = $_FILES['arquivo'];
	
	// Validação dos dados do formulário
	if ($sua_os == '') exit ("KO|Não foi informado o número da OS/Extrato a que pertence a imagem!");
	if (!is_uploaded_file($arquivo['tmp_name'])) exit ('KO|Arquivo não recebido pelo servidor');

	if (strpos($sua_os, '-')>0) {
		list ($sua_os, $seq) = explode('-', $sua_os);
		$tipo = 'r';
	}

	if ($tipo and strpos(TIPOS_ANEXO, $tipo)===false)
		die("KO|Declaração de tipo de anexo (OS, Revenda, SEDEX ou Extrato) inválido ($tipo)");

	//die("KO|OS POST:" . $_POST['os'] . ", os $tipo_os: $sua_os, seq: $seq");

	//p_echo("Command: <b>anexaNF</b>('$tipo_os$sua_os', '$arquivo');");

	$anexou = anexaNF($tipo . $sua_os, $arquivo);

	//die("OK|Teste fase 4|X|$anexou|<br>Duas fotos: $anexa_duas_fotos<br>$prefixo_img $sua_os");
	
	if ($anexou === 0) {

		if ($tipo):
			extract($a_tiposAnexo[$tipo]);
		else:
			extract($a_tiposAnexo['o']);
		endif;

		$imagens = temNF($tipo . $sua_os, 'linkEx');
		//die("KO|temNF($sua_os, 'url')");
		$msg = "OK|Arquivo anexado com sucesso à $os|" .
			  "<tr><td>$os</td>" .
				  "<td>Imagem para $tipoAnexo <a href='$progAnexo?$campo=$os' target='_blank'>$os</a></td>" .
				  "<td>$imagens</td>" .
			  "</tr>";
		exit ($msg);
	}

	$msg_erro = (is_numeric($anexou)) ? $msgs_erro[$anexou] : $anexou; // '0' é que executou OK
	exit("KO|$anexou : $msg_erro");
}   /*FIM AjaxUpload*/

$layout_menu = "cadastro";
$title = "Upload da Nota OS";
?>
<link rel="stylesheet" type="text/css" href="../js/jquery.lightbox-0.5.css" media="screen">
<? if ($login_admin == "1375") { ?>
<link rel="stylesheet" type="text/css" href="../helpdesk/mlg/css/start/jquery-ui-1.7.2.custom.css">
<?}?>
<style type="text/css">
.msg{
    position:            absolute;
    top:                 228px;
    display:             none;
    height:              2.5em;
    width:               60%;
    margin-left:         20%;
    background-color:    #f0faff;
    border:              1px solid #6699cc;
    border-radius:       6px;
    -moz-border-radius:  6px;
    font:                normal bold 13px Arial;
    color:               #339;
    vertical-align:      middle;
    behavior:            url(../js/PIE.php);
}
.erro {
	color:             #933;
	background-color:  #ffd0d0;
	border-color:      #900;
}
<? if ($login_admin == "1375") { ?>
#nb {
    position: fixed;
    top: 15%;
    left: 77%;
    width: 23%;
    height: 60%;
    background-color: transparent;
}
#notebook {
    position:          absolute;
    width:             180px;
    height:            150px;
    font:              normal normal 12px arial, helvetica, sans-serif;
    text-align:        center;
    background-color:  #e3eeff;
    box-shadow:        0 1px 8px #DFEFD8;
    border-radius:     5px;
    cursor:            move;
/*behavior: url(../js/PIE.php);*/
}
#notebook p {
    height: 16px;
    padding: 0;
    margin: 5px 5px;
    background-color: #e3eeff;
    font-weight: bold
}

#notebook textarea {
    width: 90%;
    height: 75%;
    text-align: left;
}
<?}?>
#frm {
	position: relative;
	top: 4.5em;
	margin-bottom: 2em 20%;
	width: 60%;
}
#frm a, td button {
	border: 1px outset #009;
	border-radius: 4px;
	background-color: #6699CC;
	color: #E0E9FF;
	cursor: pointer;
	padding: 3px 5px;
	height: 2em;
	font: normal bold 12px verdana, arial, helvetica, sans-serif;
	behavior: url(../js/PIE.php);
}
#frm a:hover {
	text-decoration: underline;
}
#frm a.disabled {
	border: 1px inset #77b;
	background-color: #e0e9ff;
	color: #242946;
	font: normal bold 12px verdana, arial, helvetica, sans-serif;
}

table#imagens {
	display: none;
	margin-top: 2.5em;
	width: 512px;
}
table#imagensOS {
	margin: 4em auto;
	width: 800px;
}
table#imagens,table#imagensOS {
	table-layout: fixed;
	border: 2px solid #6699CC;
	border-collapse: collapse;
	font: normal normal 11px Verdana, Arial, helvetica, sans-serif;
	color: #006;
}
table#imagens tbody,table#imagensOS tbody {
	border: 2px solid #6699CC;
}
table#imagens tbody tr:nth-child(even),table#imagensOS tbody tr:nth-child(even) {
	background-color: #ECF7FF;
}

table#imagens caption,table#imagensOS caption {
	margin-bottom: 5px;
	font-weight: bolder;
	background-color: white;
}
table#imagens th,table#imagensOS th {
	font-weight: bold;
	font-size: 12px;
	color: white;
	height: 1.3em;
	text-align: center;
	border-bottom: 1px solid #6699CC;
	background-color: #6699CC;
	white-space: nowrap;
}
table#imagensOS th:last-of-type {
	width: 320px;
}
table#imagensOS td.anexosOS a {
	font-size: 11px;
	color: darkgrey;
}
table#imagensOS td.anexosOS a:hover {
	color: grey;
}
table#imagensOS td.anexosOS img {
	max-width: 48px!important;
	margin-right: 4px;
}
div#consultaOS {display:none;}
table#imagens tr td span {cursor: pointer;color: navy}
#footer {position: fixed;bottom:0;_bottom:48px;*bottom:48px;*position:relative}
</style>

<?if (!$anexaNotaFiscal) { ?>
<div class="erro">
	<hr />
	<h1>NÃO ESTÁ HABILITADO PARA ANEXAR IMAGENS ÀS ORDENS DE SERVIÇO.<br>
	   CONTATE COM A TELECONTROL PARA ATIVAR ESTA OPÇÃO</h1>
	<hr />
</div>
<?	include "rodape.php";
	exit();
}?>

<script type='text/javascript' src='js/jquery.min.js'></script>
<script type='text/javascript' src='../js/ajaxupload.3.6.js'></script>
<script type="text/javascript" src="../js/jquery.lightbox-0.5.min.js"></script>
<? if ($login_admin == "1375") { ?>
<script type='text/javascript' src="//ww2.telecontrol.com.br/mlg/js/jquery-ui.min.js"></script>
<?}?>
<script type="text/javascript">
	var isIE = false;
</script>
<!--[if IE]>
<script type="text/javascript">
	isIE = true;
</script>
<![endif]-->
<script type='text/javascript' src='../js/FancyZoom.js'></script>
<script type='text/javascript' src='../js/FancyZoomHTML.js'></script>
</script>
<script language="JavaScript">
var program_self = window.location.pathname;

$().ready(function() {
	var MIN_OS_LENGTH = 6; /* Tamanho mínimo do campo para habilitar o botão de upload */

	function showHide(seletor,tempo) { // Passa o seletor CSS para o jQuery e o tempo que vai ser mostrado
	    $(seletor).slideDown(300).delay(tempo).slideUp(300);
		<?=$imageZoom?>
	}

    var botaoUpload = new AjaxUpload('#arquivo', {
		action: program_self,
		name: 'arquivo',
		responseType: 'text',
		onSubmit: function(file, ext){
			if (ext && /^(<?=$nf_config['mime_type']?>)$/.test(ext)){
				$('div.msg').removeClass('erro')
					    .text('Anexando o arquivo ' + file + ' na OS ' + $('#nota_sua_os').val())
					    .show('fast');
				$('#arquivo').text('Enviando imagem...');
				this.disable();
			} else {
				// extensão não válida
				$('div.msg').html('Formato não válido.<br>Por favor, envie um arquivo JPG.').addClass('erro');
				showHide("div.msg",4000);
				// cancela upload
				return false;				
			}
		},
		onChange: function(file, ext) {
			var cr = $('input:radio:checked').val();
			if (ext && /^(<?=$nf_config['mime_type']?>)$/.test(ext)){
				this.setData({
					'os':       $('#nota_sua_os').val(),
					'fabrica':  '<?=$login_fabrica?>',
					'tipo':     cr,
					'ajax':     'fileupload'
				});
			}
		},
		onComplete: function(file, response) {
			var resposta = response.split("|");

			if (resposta[0] != 'OK' && resposta[2] != undefined) alert(resposta[1]);

			if (resposta[0] == "KO") {
				$('div.msg').html(resposta[1]).addClass('erro');
				showHide("div.msg",5000);
				$('#arquivo').addClass('disabled').text('Digite o número da OS');
				this.disable();
				$('#nota_sua_os').val('').focus();
				return true;
			}

			if (resposta[0] == 'OK') {
				$('div.msg').removeClass('erro').text(resposta[1]);
				// $('table#imagens').slideDown('normal');			
				// $('table#imagens tbody').prepend(resposta[2]);
				// if (isIE) $('table#imagens tbody tr:even').css('background-color','#ecf7ff');

				showHide("div.msg",5000);
				$('button#btnConsultar').click(); //Consulta para mostrar a nova adição...
				
				this.disable();

				$('#arquivo').addClass('disabled').text('Digite o número da OS');
				$('#nota_sua_os').val('');
/*
				//  Para excluir uma imagem recém enviada...
				$('table#imagens tbody > tr:first > td img.exclui_foto').click(function () { // Para funcionar, o botão tem que estar no 1º TD
					var thisBtn = $(this);
					var nomeImg = thisBtn.attr('name').replace(/^http:\/\/[a-z0-9.-]+\//, '');

					if (confirm("Excluir a imagem " + nomeImg + '?')) {
					    $.get('<?=$PHP_SELF?>',{'imagem':nomeImg,'ajax':'excluir_nf'},function(data) {
						var resposta = data.split("|");
						if (resposta[0]=='OK') {
							$('div.msg').removeClass('erro').text(resposta[1]);
							thisBtn.parent().parent().slideUp(400,function () {
								thisBtn.parent().parent().remove();
							});
						} else {
							$('div.msg').addClass('erro').text(resposta[1]);
						}
						showHide("div.msg",4000);
					    });
					}
				});*/
				setupZoom();
			}
		}
	});

	botaoUpload.disable();

	$('input:radio[name=tipo]').change(function() {
		var os  = $('#nota_sua_os');
		var osr = $('p#info_osr');
		switch ($(this).val()) {
			case 'r':
				MIN_OS_LENGTH = 5;
				os.attr('maxlength', '7');
				break;
			
			case 's':
				MIN_OS_LENGTH = 5;
				os.attr('maxlength', '7');
				break;
			
			case 'e':
				MIN_OS_LENGTH = 6;
				os.attr('maxlength', '8');
				break;
			
			default:
				MIN_OS_LENGTH = 7;
				os.attr('maxlength', '8');
				break;
		}

		$('#nota_sua_os').keyup().focus();
	});

//  Olha se tem um nº de OS no input text...
	$('#nota_sua_os').keyup(function() {
		if ($(this).val().length > MIN_OS_LENGTH) {
			botaoUpload.enable();
			$('#arquivo').removeClass('disabled').text('Selecione a imagem');
			botaoUpload.setData($(this).val());
		} else {
			botaoUpload.disable();
			$('#arquivo').addClass('disabled').text('Digite o número da OS');
		}
	});

	// Consulta uma OS, etorna os dados, inclusive imagens em Anexo, para poder excluir.
	$('button#btnConsultar').click(function() {
		var os = $('#nota_sua_os').val();
		var cr = $('input:radio:checked').val();

		$.post(
			program_self,
			{   'ajax':    'consulta',
				'os':      os,
				'tipo':     cr,
				'fabrica': '<?=$login_fabrica?>'
			},
			function(data) {
				if (data.indexOf('<tr>')>-1) {
					$('#consultaOS').show('fast');
					$('#imagensOS > tbody').prepend(data);
					$('#imagensOS tbody tr td.anexosOS img.excluir_NF').dblclick(function() {
						var imgUrl   = $(this).attr('name').replace(/^http:\/\/[a-z0-9.-]+\//, '');

						if (imgUrl.indexOf('?')>-1) {
							imgUrl = imgUrl.substr(0, imgUrl.indexOf('?'));
						}

						var postData = new Object;
						postData.imagem  = imgUrl;
						postData.fabrica = '<?=$login_fabrica?>';
						postData.ajax    = 'excluir_nf';

						if (confirm("Excluir a imagem "+postData.imagem+'?')) {
							$.post(
								program_self,
								postData,
								function(resposta) {
									data = resposta.split('|');
									alert(data[1]);
									if (data[0] == 'OK') img.remove();
							});
						}
					});
				} else {
					alert(data);
				}
		});
	});
});
</script>

<p>Se o anexo for IMAGEM, deverá ter <b> menos de <acronym title="Megapíxels (altura x largura)">3Mpx</acronym></b>.</p>
<div class='msg' align='center'>&nbsp;</div>

<div id='frm' style='text-align:center;margin: auto'>
	<input type='hidden' name='nota_os' id='nota_os' value='' />
	<fieldset>
		<legend>Tipo de Anexo</legend>
		<input type="radio" name="tipo" id="cr_o" value=""  /><label for="cr_o">OS Consumidor</label>
		<input type="radio" name="tipo" id="cr_r" value='r' /><label for='cr_r'>OS Revenda</label>
<?	if ($login_fabrica == 1) { ?>
		<input type="radio" name="tipo" id="cr_s" value='s' /><label for='cr_s'>OS SEDEX</label>
		<input type="radio" name="tipo" id="cr_e" value='e' /><label for='cr_e'>Extrato</label>
<?}?>
	</fieldset>
	<label for='nota_sua_os'>Digite o número da OS:&nbsp;</label>
	<input type='text' align='right' name='nota_sua_os' align='right' id='nota_sua_os' maxlength='8' value=<?=$nota_sua_os?>>&nbsp;&nbsp;
	<button type='button' id='btnConsultar'>Consultar OS</button>
	<a id='arquivo' class='disabled'>Digite o n&uacute;mero da OS</a>
	<p align='center' id='info_osr' style='display:none;background-color:#ffffdd;color:darkred'>Digite apenas o radical da OS Revenda (<u><b>XXXXXX</b></u>-xx)</p>
</div>
<p>&nbsp;</p>
<div id="consultaOS">
	<table id="imagensOS">
		<thead>
			<tr>
				<th>Nº OS</th>
				<th>Data Dig.</th>
				<th>Posto</th>
				<th>Nome Fantasia</th>
				<th>Tipo OS</th>
				<th>Anexo(s)</th>
			</tr>
		</thead>
		<tbody></tbody>
	</table>
</div>
<table id='imagens' align='center'>
    <caption>Imagens j&aacute; inseridas</caption>
    <thead><tr>
        <th title="Número de OS/Extrato" width="200">OS/Extrato</th>
        <th width="310">Resultado</th>
        <th>Imagens</th>
    </tr></thead>
    <tbody>
	<tr></tr>
    </tbody>
</table>
<? include "rodape.php"; ?>
