<?php
/**************************************************************
* Programa para teste das novas funções de anexo de arquivos *
* no S3 para OS, OS Revenda, OS Sedex e Extratos.            *
**************************************************************/
error_reporting(E_ALL);

if (strpos(__FILE__, 'admin')) {

	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'autentica_admin.php';
	require_once "../anexaNF_inc.php";

} else {

	// Para testes no servidor de produção
	$login_fabrica = $_REQUEST['fabrica'];
	if ($login_fabrica == '')		 die("Não definiu a fábrica");
	if (!is_numeric($login_fabrica)) die("Fábrica inválida!");

	require_once '/var/www/assist/www/dbconfig.php';
	require '/var/www/assist/www/includes/dbconnect-inc.php';
	include_once "mlg_funciones.php";
	require_once '/aws-amazon/sdk/sdk.class.php';
	include_once 'anexaS3_completo.php';

}

if ($login_fabrica == 1) {
	$anexa_NF_SEDEX   = true;
	$anexa_NF_extrato = true;
	$texto_Extrato    = ($login_fabrica == 1) ? 'Protocolo' : 'Extrato'; //Repetida a regra, para quando/se mudar a anterior
}

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

	$tipo = strtolower($tipo);

	$usar_os = ($acao_anterior == 'excluida');

	if ($tipo) {
		//print_r($a_tiposAnexo[$tipo]);
		if (strpos(TIPOS_ANEXO, $tipo)===false) return false;

		extract($a_tiposAnexo[$tipo]);

	} else {
		$os_revenda = e_OS_revenda($os);

		if ($os_revenda !== false) {
			extract($a_tiposAnexo['r']);
			$os = $os_revenda;
		} else {
			extract($a_tiposAnexo['o']);
		}
	}

	$num_os	 = preg_replace('/\D/', '', $os);

	$table = "tbl_$tbl_r";

	if ($tblOnly != 'y' and ($tipo == 'o' or $tipo == 'r' or $tipo == ''))
		$campoOS = 'sua_os';
	else if ($tipo == 'e' and $login_fabrica == 1)
		$campoOS = 'protocolo';
	else 
		$campoOS = $campo;

	$id_posto = ($tipo == 's') ? 'posto_origem' : 'posto';

	$sql = "SET DateStyle TO SQL, European;
			SELECT $campo    AS os,
			$table.$campoOS  AS os_fabrica,
			$campoData::date AS data_digitacao,
			codigo_posto, nome_fantasia,
			'Teste'          AS tipo
              FROM $table
              JOIN tbl_posto_fabrica
                ON tbl_posto_fabrica.fabrica = $table.fabrica
               AND tbl_posto_fabrica.posto   = $table.$id_posto
             WHERE $table.$campoOS           = '$os'
               AND $table.fabrica            = $login_fabrica";

	$res = pg_query($con, $sql);

	if (!is_resource($res)) die("Erro ao consultar o Banco de dados!\n" . iif($debug=='sql', $sql . chr(10) . pg_last_error($con))); //\n\n$sql

	if (!pg_num_rows($res)) die("Não há $tipoAnexo com este número!");

	if ($campo=='os_revenda') {
		$sql_rev      = "SELECT COUNT(*) FROM tbl_os_revenda_item JOIN tbl_os_revenda USING(os_revenda) WHERE tbl_os_revenda.sua_os = $os";
		$total_os_rev = pg_fetch_result(pg_query($con, $sql_rev), 0, 0);
	}

	$dados_os = pg_fetch_assoc($res, 0);

	// OS Revenda explodida
	$dados_os['tipo'] = ($campo == 'os_revenda' and $total_os_rev) ? "OS Rev. ($total_os_rev OS)" : $tipoAnexo;

	//echo "Prefixo: $tipo, Num: $os\n";

	if ($login_fabrica == 80 && $tipo == "r") {
		$sql_num_os = "SELECT os FROM tbl_os WHERE fabrica = $login_fabrica AND sua_os ILIKE '$os%'";
		$res_num_os = pg_query($con, $sql_num_os);

		$num_os = pg_fetch_result($res_num_os, 0, "os");

		$temAnexos = temNF2($num_os, $dados_os['os'], 'bool');
	} else {
		$temAnexos = temNF($tipo . $dados_os['os'], 'bool');
	}
	//var_dump($temAnexos);

	if (is_numeric($temAnexos))
		die("KO|" . $msgs_erro[$temAnexos]);

	$tipoTable = ($noExcl=='y') ? 'link' : 'linkEx';

	if ($login_fabrica == 80) {
		$dados_os['arq'] =  ($temAnexos) ? temNF2($num_os, $dados_os['os'], $tipoTable) : 'Sem anexo(s)';
	} else {
		$dados_os['arq'] =  ($temAnexos) ? temNF($tipo . $dados_os['os'], $tipoTable) : 'Sem anexo(s)';
	}

	// Se pediu apenas os links, devolve apenas a tabela com os links ou a mensagem "Sem anexos"
	if (getPost('tblOnly') == 'y')
		die($dados_os['arq']);

	extract($dados_os);
	die("<tr><td>$os_fabrica</td><td>$data_digitacao</td><td>$codigo_posto</td><td>$nome_fantasia</td><td>$tipo</td><td class='anexosOS'>$arq</td></tr>");
}

/*   AJAX tratar imagem enviada pelo Ajax   */
if ($_REQUEST['ajax']=='fileupload') {

	//pre_echo($_POST);
	$sua_os  = trim($_REQUEST['os']);
	$tipo    = $_REQUEST['tipo'];
	$arquivo = $_FILES['arquivo'];

	// Validação dos dados do formulário
	if ($sua_os == '') exit ("KO|Não foi informado o número da OS/Extrato a que pertence a imagem!");
	if (!is_uploaded_file($arquivo['tmp_name'])) exit ('KO|Arquivo não recebido pelo servidor');

	$num_os	 = preg_replace('/\D/', '', $sua_os);

	if ($tipo) {
		//print_r($a_tiposAnexo[$tipo]);
		if (strpos(TIPOS_ANEXO, $tipo)===false) return false;

		extract($a_tiposAnexo[$tipo]);
	} else
		extract($a_tiposAnexo['o']);

	$table = "tbl_$tbl_r";

	if ($tipo == 'o' or $tipo == 'r' or $tipo == '')
		$campoOS = 'sua_os';
	else if ($tipo == 'e' and $login_fabrica == 1)
		$campoOS = 'protocolo';
	else 
		$campoOS = $campo;

	$id_posto = ($tipo == 's') ? 'posto_origem' : 'posto';

	$sql = "SET DateStyle TO SQL, European;
			SELECT $campo AS os
				FROM $table
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.fabrica = $table.fabrica
				AND tbl_posto_fabrica.posto   = $table.$id_posto
				WHERE $table.$campoOS = '$os'
				AND $table.fabrica  = $login_fabrica";

	$res = pg_query($con, $sql);

	if (!is_resource($res)) die("KO|Erro ao consultar o Banco de dados!|\n\n$sql");

	if (!pg_num_rows($res)) die("KO|Não há $tipoAnexo com este número!|\n\n$sql");

	$sua_os = pg_fetch_result($res, 0, 0);

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
	exit("KO|$msg_erro");
}   /*FIM AjaxUpload*/

$layout_menu = "cadastro";
$title = "Upload da Nota OS";

include "cabecalho.php";
?>
<!--[if lt IE 9]>
<script src="http://ie7-js.googlecode.com/svn/version/2.0(beta3)/IE8.js" type="text/javascript"></script>
<![endif]-->
<!-- <link rel="stylesheet" type="text/css" href="/assist/js/jquery.lightbox-0.5.css" media="screen"> -->
<style type="text/css">
.msg{
	position:            absolute;
	top:                 248px;
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
	behavior:            url(js/PIE.php);
}
.erro {
	color:             #933;
	background-color:  #ffd0d0;
	border-color:      #900;
}

.gStyle, a.gStyle:active {
	background: white url(imagens/gButton_bg.png) 0 0 repeat-x;
/*	background-size: auto 101%;*/
	background: linear-gradient(top, #fff, #ddd);
	background: -o-linear-gradient(top, #fff, #ddd);
	background: -ms-linear-gradient(top, #fff, #ddd);
	background: -moz-linear-gradient(top, #fff, #ddd);
	background: -webkit-linear-gradient(top, #fff, #ddd);
	-pie-background: white linear-gradient(top, #fff, #ddd);
	border: 1px solid #bbb;
	border-radius: 3px;
	-moz-border-radius: 3px;
	padding: 2px 4px;
	margin-right: 3px;
	color: black!important;
	box-shadow: 0 1px 3px rgba(0,0,0,0.5);
	-moz-box-shadow: 0 1px 3px rgba(0,0,0,0.5);
	text-shadow: 0 -1px -1px rgba(0,0,0,0.5),1px 1px 0 white;
	behavior: url(js/PIE.php);
	}
	a.gStyle:visited {color:#666!important}
	a.gStyle:hover, .gStyle:hover {
		color:black!important;
		background: white url(imagens/gButton_hover.png) repeat-x;
		background: linear-gradient(top, #ddd, #fff);
		background: -o-linear-gradient(top, #ddd, #fff);
		background: -ms-linear-gradient(top, #ddd, #fff);
		background: -moz-linear-gradient(top, #ddd, #fff);
		background: -webkit-linear-gradient(top, #ddd, #fff);
		-pie-background: white linear-gradient(top, #ddd, #fff);
		cursor:	pointer;
		behavior: url(js/PIE.php);
	}
	a.gStyle.disabled,a.gStyle.disabled:hover {color:#aaa!important}

	table#imagensOS {
		/*width: 950px;*/
	}
	table#imagens,table#imagensOS {
		table-layout: fixed;
		border: 2px solid #6699CC;
		border-collapse: collapse;
		font: normal normal 11px Verdana, Arial, helvetica, sans-serif;
		color: #006;
		margin: 4em auto;
	}
	table#imagensOS tbody {
		border: 2px solid #6699CC;
	}
	table#imagensOS tbody tr:nth-child(even) {
		background-color: #ECF7FF;
	}

	table#imagensOS caption {
		margin-bottom: 5px;
		font-weight: bolder;
		background-color: white;
	}
	table#imagensOS th {
		font-weight: bold;
		font-size: 12px;
		color: white;
		height: 1.3em;
		text-align: center;
		border-bottom: 1px solid #6699CC;
		background-color: #6699CC;
		white-space: nowrap;
	}
	table#imagensOS > thead > tr > th:last-of-type {
		width: 610px;
	}
	table#imagensOS td.anexosOS a {
		font-size: 11px;
		color: darkgrey;
	}
	table#imagensOS td.anexosOS a:hover {
		color: grey;
	}
	div#consultaOS {display:none;}
	table#anexos tr td a>img {max-height: 64px;min-height: 64px;}
	table#anexos th {min-width: 75px}
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
<script type='text/javascript' src='../js/jquery-1.6.1.min.js'></script>
<script type='text/javascript' src='../js/ajaxupload.3.6.js'></script>
<script type="text/javascript" src="../js/jquery.formrestrict.js"></script>
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
<script type='text/javascript'>
var program_self = window.location.pathname;

$().ready(function() {
	var MIN_OS_LENGTH = 6; /* Tamanho mínimo do campo para habilitar o botão de upload */

	function showHide(seletor,tempo) { // Passa o seletor CSS para o jQuery e o tempo que vai ser mostrado
	    $(seletor).slideDown(300).delay(tempo).slideUp(300);
		<?=$imageZoom?>
	}

    var botaoUpload = new AjaxUpload('#arquivo', {
		action: "<?=$PHP_SELF?>",
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
				$('div.msg').html('Formato não válido.').addClass('erro');
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
			} else {
				alert("Arquivo em formato inválido!");
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
				setupZoom();
			}
		}
	});

	botaoUpload.disable();

	$('input:radio[name=tipo]').change(function() {
		var os  = $('#nota_sua_os');
		var osr = $('p#info_osr');

		osr.hide();

		switch ($(this).val()) {
			case 'r':
				MIN_OS_LENGTH = 5;
				os.attr('maxlength', '7');
				osr.show();
				break;
			
			case 's':
				MIN_OS_LENGTH = 5;
				os.attr('maxlength', '7');
				break;
			
			case 'e':
				MIN_OS_LENGTH = 6;
				os.attr('maxlength', '10');
				break;
			
			default:
				MIN_OS_LENGTH = 7;
				os.attr('maxlength', '8');
				break;
		}

		$('#nota_sua_os').keyup().focus();
	});

	function lockFileUpload(t) {
		botaoUpload.disable();
		var texto = (t == undefined) ? 'Digite o número da OS' : t;
		$('#arquivo').addClass('disabled').text(texto).css('cursor', 'default');
	}

	function unlockFileUpload(t) {
		botaoUpload.enable();
		var texto = (t == undefined) ? 'Selecione a imagem' : t;
		$('#arquivo').removeClass('disabled').text(texto).css('cursor', 'pointer');
		botaoUpload.setData($(this).val());
	}
//  Olha se tem um nº de OS no input text...
	$('#nota_sua_os').keyup(function() {
		if ($(this).val().length > MIN_OS_LENGTH) {
			unlockFileUpload();
		} else {
			lockFileUpload();
		}
	});

	// Consulta uma OS, retorna os dados, inclusive imagens em Anexo, para poder excluir.
	$('#btnConsultar').click(function() {
		var os = $.trim($('#nota_sua_os').val());
		var cr = $('input[name=tipo]:checked').val();

		if (os == undefined || os.length == 0) {
			alert("Informe o número da OS");
			return;
		}
		
		if (cr == undefined) {
			alert("Informe o tipo da OS");
			return;
		}

		$.post(
			"<?=$_SERVER['PHP_SELF']?>",
			{   'ajax':    'consulta',
				'os':      os,
				'tipo':     cr,
				'fabrica': '<?=$login_fabrica?>'
			},
			function(data) {
				if (data.indexOf('<tr>')>-1) {

					var numAnexos = 0;

					$('#consultaOS').show('fast');
					$('#imagensOS > tbody').prepend(data);
					setupZoom();

					var Ximgs = $('#imagensOS tbody tr:first img.excluir_NF');
					numAnexos = Ximgs.length;
					
					if (isIE) $('table#imagensOS tbody tr:even').css('background-color','#ecf7ff');
					if (numAnexos >= 4) {
						lockFileUpload('Não pode mais anexar');
					}
				} else {
					alert(data);

					if (data.indexOf('com este número'))
						$('#nota_sua_os').val('').keyup();
				}
		});
	});

	$('#imagensOS').delegate('img.excluir_NF', 'click', function() {
		var imgUrl  = $(this).attr('name').replace(/^http:\/\/[a-z0-9.-]+\//, '');

		var attBaseName = imgUrl.replace(/.*\/(([rse]_)?\d+)(?:-\d)?\.\w+$/, "$1").split('_').sort();

		var numOS = attBaseName[0];

		var tipo = (attBaseName[1] != undefined) ? attBaseName[1] : '';

		var innerTD = $(this).parents('.anexosOS');

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
					if (data[0] == 'OK') {
						innerTD.load(
							program_self,
							{   'ajax':    'consulta',
								'os':      numOS,
								'tipo':    tipo,
								'acao_anterior': 'excluida',
								'fabrica': '<?=$login_fabrica?>',
								'tblOnly': 'y'
							}
						);
					}
				}
			);
		}
	});

	$('#nota_sua_os').numeric();
});
</script>
	<div class="texto_avulso"><?=nl2br(substr($inputFileTitle, 7))?></div>
<input type='hidden' name='nota_os' id='nota_os' value='' />
<p>&nbsp;</p>
<div class="msg"></div>
<p>&nbsp;</p>
<table border="0" class="formulario" align="center">
	<tr>
		<tr class="titulo_tabela inicio">
		<td colspan="4">Tipo de Anexo</td>
	</tr>
	<tr>
		<td width='20%'>&nbsp;</td>
		<td>
			<input type="radio" name="tipo" id="cr_o" checked value="" />
			<label for="cr_o">OS Consumidor</label>
		</td>
		<td>
			<input type="radio" name="tipo" id="cr_r" value='r' />
			<label for='cr_r'>OS Revenda</label>
		</td>
		<td width='25%'>&nbsp;</td>
	</tr>
	<tr>
		<td colspan="4"></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>
		<?	if ($anexa_NF_SEDEX) { ?>
			<input type="radio" name="tipo" id="cr_s" value='s' />
			<label for='cr_s'>OS SEDEX</label>
		<?}?>
		</td>
		<td>
		<?	if ($anexa_NF_extrato) { ?>
			<input type="radio" name="tipo" id="cr_e" value='e' />
			<label for='cr_e'><?=$texto_Extrato?></label>
		<?}?>
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td align='right'>
			<label for='nota_sua_os'>Digite o número da OS:&nbsp;</label>
		</td>
		<td>
			<input type='text' align='right' name='nota_sua_os' align='right' id='nota_sua_os' maxlength='8' value=<?=$nota_sua_os?>>&nbsp;&nbsp;
		</td>
		<td>
			<button type='button' id='btnConsultar'>Consultar OS</button>
		</td>
		<td>
			<a id='arquivo' class='gStyle disabled'>Digite o n&uacute;mero da OS</a>
		</td>
	</tr>
	<tr>
		<td colspan="4"><p align='center' id='info_osr' style='display:none;background-color:#ffffdd;color:darkred'>Digite apenas o radical da OS Revenda (<u><b>XXXXXX</b></u>-xx)</p></td>
	</tr>
</table>
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
<div style='height: 4em'>&nbsp;</div>
<? include "rodape.php"; ?>
