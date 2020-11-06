<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
require_once 'autentica_admin.php';
include_once 'funcoes.php';
include_once '../helpdesk/mlg_funciones.php';

$title       = "MENU GERÊNCIA";
$layout_menu = "gerencia";

// Se for fazer público (¿?), retirar as validações, deixar apenas o master fazer as alterações.
if ($login_fabrica != 46 and $login_fabrica != 10) {
	include 'cabecalho.php';
	echo "<h2>Sem permissão de acesso!!</h2>\n";
	include 'rodape.php';
	die;
}

if (!in_array($login_login, array('manuel','tulio','valeria','ronaldo','sergio','waldir','marisasilvana','paulo','sergiotelecontrolnet'))){
	include 'cabecalho.php';
	echo "<h2>Sem permissão de acesso!!</h2>\n";
	include 'rodape.php';
	die;
}

if ($AWS_sdk_OK) {
	include_once AWS_SDK;
	$s3logo = new AmazonS3();
	$S3_online = is_object($s3logo);
	$bucket = 'br.com.telecontrol.posvenda-downloads';
} else {
	include 'cabecalho.php';
	echo "<h2>Não foi possível conectar com o S3. Tente dentro de alguns segundos.</h2>\n";
	include 'rodape.php';
	die;
}

$logosS3 = $s3logo->get_object_list($bucket, array('prefix'=>'logos/'));

foreach($logosS3 as $logo) {
	$url = $s3logo->get_object_url($bucket, $logo);
	$sel[$url] = $logo;
}
//die(print_r($sel, true));
$lista_logos  = array2select('logo_s3', 'logoS3', $sel, $logo_fabrica, " class='frm'", 'Selecione...', true);

if (count(array_filter($_POST))) {

	// Tipos de arquivos aceitos, e o tipo MIME para validação.
	$mimeTypes = array(
		'jpg'	=> 'image/jpeg',
		'jpeg'	=> 'image/jpeg',
		'gif'	=> 'image/gif',
		'png'	=> 'image/png',
	);

	$logo_nova = $_POST['setThisLogo'];

	if ($_FILES['upload']['tmp_name']) {
		if (is_uploaded_file($_FILES['upload']['tmp_name'])) {
			if (preg_match('/(gif|jpg|jpeg|png|x-png|pjpeg)/', $_FILES['upload']['type'])) {
				$fileinfo = pathinfo($_FILES['upload']['name']);

                // 19/06/2015 - limitar nome do arquivo, máx 50 caracteres no banco para a logo
                $fileinfo['filename'] = substr($fileinfo['filename'], 0, 38); // se não chega aos 38, fica na mesma.
				$novo_nome = 'logos/' . mb_strtolower(preg_replace('/\W/', '_', $fileinfo['filename'])) . '.' . $fileinfo['extension'];

				try {
					$i = 0;
					$retry = true;
					while ($retry and $i++ < 5) {
						$r = $s3logo->create_object(
							$bucket,
							$novo_nome,
							array(
								'fileUpload' => $_FILES['upload']['tmp_name'],
								'acl'        => AmazonS3::ACL_PUBLIC,
								'storage'    => AmazonS3::STORAGE_REDUCED
							)
						);
						$retry = false;
					}
				}
				catch (Exception $e) {
					//echo $this->_erro = $e->getMessage();
					$msg_erro .= "$i - Erro ao criar o objeto S3: $erroS3<br />";
					sleep(6);
					$retry = true;
				}
			}
		}   
	} else {
		if (!strpos($logo_nova, 'telecontrol.posvenda-downloads')) {
			if (preg_match(RE_URL, $logo_nova, $dados_logo_nova)) {
				$novo_nome = 'logos/' . basename($dados_logo_nova['path']);
				$tipo_OK   = preg_match('/(jpg|jpeg|png|gif)/', $novo_nome);
				$novo_type = $mimeTypes[pathinfo($novo_nome, PATHINFO_EXTENSION)];
			}
			if ($tipo_OK) {
				if (in_array($novo_nome, $logosS3)) {
					$msg_erro = 'O arquivo já existe no S3, não é necessário subí-lo de novo.<br />Se precisa renomear ele, baixe o arquivo (botão direito) e suba ele com outro nome.';
				} else {
					//Subir arquivo no S3
					$nova_logo = file_get_contents($logo_nova);
					$r = $s3logo->create_object(
						$bucket,
						$novo_nome,
						array(
							'body'       => $nova_logo,
							'acl'        => AmazonS3::ACL_PUBLIC,
							'storage'    => AmazonS3::STORAGE_REDUCED,
							'contentType'=> $novo_type
						)
					);
					if ($r->isOK()) {
						$logo_fabrica = $s3logo->get_object_url($bucket, $novo_nome);
					} else {
						unset($novo_nome);
					}
				}
			}
		} else {
			preg_match(RE_URL, $logo_nova, $dados_logo_nova);
			$logo_fabrica = $logo_nova;
			$novo_nome    = pathinfo($dados_logo_nova['path'], PATHINFO_BASENAME);
		}
	}
	if (isset($novo_nome)) {
		$logo_nome = pg_quote(basename($novo_nome));
		$site      = pg_quote(getPost('link_site'));

		if ($site)
			$updateSite = "site = $site,";

		$sql = "UPDATE tbl_fabrica
				   SET $updateSite logo = $logo_nome
				 WHERE fabrica = 46";

		$res = pg_query($con, $sql);
		if (pg_affected_rows($res) != 1)
			$msg_erro = 'Erro ao atualizar o banco de dados!';
	} else {
		$msg_erro = 'Erro ao recuperar o arquivo do novo logotipo.<br />' . $msg_erro;
	}
	if (!$msg_erro)
		die("<script type='text/javascript'>window.parent.location.reload();</script>");
}

$sql_logo_46  = "SELECT logo FROM tbl_fabrica WHERE fabrica = 46";
$logo_fabrica = 'logos/' . pg_fetch_result(pg_query($con, $sql_logo_46), 0, 'logo');

if (!in_array($logo_fabrica, $logosS3)): // Não tem já no S3...
	$logo_fabrica = '../' . $logo_fabrica;
else:
	$logo_fabrica = $s3logo->get_object_url($bucket, $logo_fabrica);
endif;

// Começa o HTML ?>
<!DOCTYPE HTML>
<html lang="en">
<head>
	<meta charset="latin-1">
	<title>Logotipo Telecontrol Net</title>
	<link rel="stylesheet" href="css/admin_personaliza.css" />
	<style type="text/css">
	div#frm {
		background-color: white;
		background-color: rgba(255, 255, 255, 0.9);
		border: 0 solid white;
		box-shadow: 2px 3px 3px #333;
		border-radius: 4px 4px 6px 6px;
	}
	fieldset {
		border: 1px solid lightgrey;
		border-radius: 4px;
		margin: 1ex 1em;
	}
	fieldset label {
		display: inline-block;
		text-align: right;
		font-weight: bold;
		width: 120px;
	}
	fieldset input {
		display: inline-block;
		width: 280px;
		margin-left: 2em;
	}
	fieldset select {
		margin-left: 2em;
	}
	form+div {
		margin-top: 2em;
		text-align: center;
		vertival-align: middle;
		padding: 2em auto;
	}
    .erro {
        background-color: #faa;
        color: darkred;
        font-weight: bold;
        text-align: center;
    }
	img#imglogo {
		display: inline-block;
		max-height: 60px;
		max-width: 220px;
	}
	</style>
</head>
<body>
	<div id='userCfg'>
	<div id="frm">
		<h2 id='cfgHeader'>Logotipo para TelecontrolNet<button type="button" id='btnFechar' onClick='window.parent.toggleCustomizePopUp("admLogoTCNet")'>X</button></h2>
		<form enctype="multipart/form-data" method="POST">
			<br />
			<fieldset>
				<legend>Selecione um logotipo ou um arquivo</legend>
				<p>Selecione um arquivo para subir...</p>
					<label for="">Do seu computador:</label>
					<input id="arquivo" type="file" name='upload' accept='jpg,jpeg,png,gif' />
					<br />
					<label for="url">URL Imagem:</label>
					<input type="url" id="url" name="url"
						placeholder='Cole o link da imagem aqui'
						pattern='^(https?:\/\/)?([-\w]+\.[-\w\.]+)(:?\w\d+)?(\/([-~\w\/_\.%]+(\?\S+)?)?)*$' />
				<br />
				<p>...ou um arquivo já existente:</p>
					<label for="logoS3">Logo do servidor:</label>
					<?=$lista_logos?>
			</fieldset>
			<fieldset>
				<legend>URL do cliente</legend>
				<label for="link_site">URL Site:</label>
				<input id="link_site" name="link_site" type="url"
			  placeholder='Cole o link do site do cliente'
				  pattern='^(https?:\/\/)?([-\w]+\.[-\w\.]+)(:?\w\d+)?(\/([-~\w\/_\.%]+(\?\S+)?)?)*$' />
			</fieldset>
			<input id="setThisLogo" name='setThisLogo' type="hidden" />
			<br />
			<div align="center">
				<img id="imglogo" src="<?=$logo_fabrica?>" alt="Logotipo" />
				<br />
				<button onclick='prepara_envio()'>Atualizar Logo na Telecontrol Net</button>
			</div>
			<br />
		</form>
<?php
	if ($msg_erro)
		echo "<div class='erro'>$msg_erro</div>\n";
?>
	</div>
	</div>
</body>
<script type="text/javascript">
	function isURL(str) {
		return str.match(/^(https?:\/\/)?([-\w]+\.[-\w\.]+)(:?\w\d+)?(\/([-~\w\/_\.]+(\?\S+)?)?)*$/);
	}
	function altera_imagem(url) {
		document.getElementById('imglogo').src = url;
	}
	document.getElementById('url').onchange = function() {
		if (isURL(this.value)) {
			altera_imagem(this.value);
			document.getElementById('logoS3').value = ''
		}
	}
	document.getElementById('logoS3').onchange = function() {
		altera_imagem(this.value);
	}
	function prepara_envio() {
		var logo = document.getElementById('imglogo').src;
		console.log(logo);
		if (logo != '') {
			document.getElementById('setThisLogo').value = logo;
			return true;
		}
		return false;
	}
</script>
</html>
