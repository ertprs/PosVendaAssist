<?php

require_once 'dbconfig.php';
require_once 'includes/dbconnect-inc.php';
require_once 'autentica_admin.php';

switch($_GET["tipo"]) {
	case "atualizar_campo":
		$campo = $_GET["campo"];
		
		$sql = "
		UPDATE tbl_informativo_modulo SET
		{$_GET['campo']} = '{$_GET['valor']}'
		
		WHERE
		tbl_informativo_modulo.informativo_modulo={$_GET['informativo_modulo']}
		";
		@$res = pg_query($con, $sql);
		
		if (strlen(pg_last_error($con)) > 0) {
			echo "falha|" . pg_last_error($con);
			die;
		}
		else {
			echo "ok";
			die;
		}
	break;
	
	case "limpar_imagem":
		try {
			$campo = $_GET["campo"];
			
			$sql = "
			SELECT
			{$campo}
			
			FROM
			tbl_informativo_modulo
			
			WHERE
			informativo_modulo={$_GET['informativo_modulo']}
			";
			@$res = pg_query($con, $sql);
			
			if (strlen(pg_last_error($con)) > 0) throw new Exception("Falha ao ler dados no banco de dados <erro msg=" + pg_last_error($con) + ">");
			
			$imagem = pg_fetch_result($res, 0, 0);
			
			$sql = "
			UPDATE tbl_informativo_modulo SET
			{$_GET['campo']} = '{$_GET['valor']}'
			
			WHERE
			tbl_informativo_modulo.informativo_modulo={$_GET['informativo_modulo']}
			";
			@$res = pg_query($con, $sql);
			
			if (strlen(pg_last_error($con)) > 0) throw new Exception("Falha ao atualizar banco de dados <erro msg=" + pg_last_error($con) + ">");
			
			if (file_exists($imagem)) unlink($imagem);
			echo "ok";
			die;
		}
		catch(Exception $e) {
			echo "falha|" . $e->getMessage();
			die;
		}
	break;
	
	case "ajax_upload":
		require_once("../js/valums_upload/server/php.php");
		// list of valid extensions, ex. array("jpeg", "xml", "bmp")
		$allowedExtensions = array();
		// max file size in bytes
		$sizeLimit = 4 * 1024 * 1024;
		$uploader = new qqFileUploader($allowedExtensions, $sizeLimit);
		$result = $uploader->handleUpload($_GET['file_path'], true, "{$_GET['id']}_{$_GET['file_suffix']}");
		// to pass data through iframe you will need to encode all html tags
		echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
	break;
}

?>