<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
#Inclui o arquivo que tem as funções de anexar Nota fiscal
include_once('anexaNF_inc.php');

header("Cache-Control: no-cache, must-revalidate");
header("Expires: -1");
header("Pragma: no-cache");

if(strlen($_GET["os"])>0) $sua_os = $_GET["os"];
else                      $sua_os = $_POST["os"];

//Validando se a OS é do posto logado no sistema
$sql = "SELECT tbl_os.os, tbl_os.sua_os
		FROM tbl_os
		WHERE tbl_os.fabrica = $login_fabrica
		AND tbl_os.posto     = $login_posto
		AND (tbl_os.os = $sua_os OR tbl_os.sua_os = '$sua_os')";
$res = pg_query($con, $sql);

if(pg_num_rows($res) == 0) {
	echo "OS não localizada";
	die;
}

$os = pg_fetch_result($res, 0, os);

if ($anexaNotaFiscal) {
	$anexou = anexaNF($os, $_FILES['foto']);

	if  ($anexou !== 0) {
		$msg_erro = is_numeric($anexou) ? $msgs_erro[$anexou] : $anexou;
	} else {
		$enviado = true;
		$msg_erro = "Arquivo enviado com sucesso! ";
	}
}

include "cabecalho.php";
?>

<style>
	.titulo {
		font-family: Arial;
		font-size: 9pt;
		text-align: center;
		font-weight: bold;
		color: #FFFFFF;
		background: #408BF2;
	}
	.titulo2 {
		font-family: Arial;
		font-size: 12pt;
		text-align: center;
		font-weight: bold;
		color: #FFFFFF;
		background: #408BF2;
	}

	.conteudo {
		font-family: Arial;
		FONT-SIZE: 8pt;
		text-align: left;
	}

	.mesano {
		font-family: Arial;
		FONT-SIZE: 11pt;
	}

	.Tabela{
		border:		1px solid #485989;
		font-family:Arial;
		font-size:	9pt;
		text-align:	left;
	}
	img{
		border: 0px;
	}
	.caixa{
		border:1px solid #666;
		font-family: courier;
	}

	body {
		margin: 0px;
	}

	.msg {
		color: #f22;
		text-align: center;
        background-color: #fcc;
	}
</style>

<br />
<form name='frm_relatorio' method='post' enctype="multipart/form-data">
	<table width='700' class='Tabela' align = 'center' cellpadding='5' cellspacing='0' border='0' >
		<?if (strlen($msg_erro) > 0) {?>
		<tr>
			<td class="msg">
				<?=$msg_erro?>
			</td>
		</tr>
		<?}?>
		<tr>
			<td align='center'>
				<?=$include_imgZoom?>
			</td>
		</tr>
		<tr>
			<td align='center'>
				<input type='submit' name='btn_acao' value='Enviar Arquivo' />
				<input type='hidden' name='os' value='<?=$os?>'>
			</td>
		</tr>
	</table>
</form>

<div align="center">
<?
if (temNF($os, 'bool')) { 
	$temImg = true;
	echo temNF($os, 'link') . $include_imgZoom;
}
?>
</div>
