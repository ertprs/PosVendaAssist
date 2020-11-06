<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$comunicado = (int) $_GET['comunicado'];

if (!empty($_GET['comunicado'])) {

	try {

		$sql = "SELECT auditoria_online
				FROM tbl_auditoria_online_comunicado
				WHERE comunicado = $comunicado";

		$res = pg_query($con, $sql);

		if (!pg_num_rows($res)) {
			throw new Exception("Auditoria não encontrada");		
		}

		$_GET['auditoria'] = pg_result($res, 0, 0);

		ob_start();

			require_once 'admin/visualiza_auditoria.php';

			$content = ob_get_contents();

		ob_end_clean();

		require_once("pdf/mpdf/mpdf.php");
		$pdf = new mPDF();
		$pdf->allow_charset_conversion = true;
		$pdf->charset_in = 'ISO-8859-1';
		$pdf->WriteHtml(utf8_encode($content));
		$pdf->Output("auditoria_$login_posto.pdf", 'D');

		exit;

	} catch(Exception $e) {

		die("<script> alert('{$e->getMessage()}'); window.history.back(); </script>");

	}

}