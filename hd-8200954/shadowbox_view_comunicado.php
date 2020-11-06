<?php
$admin_es = (preg_match("/\/admin_es\//", $_SERVER["PHP_SELF"]) ? true : false);
if ($admin_es) {
	include_once '../dbconfig.php';
	include_once '../includes/dbconnect-inc.php';
	include_once 'autentica_admin.php';
	include_once 'funcoes.php';
	include 'plugins/fileuploader/TdocsMirror.php';
} else {
	include_once 'dbconfig.php';
	include_once 'includes/dbconnect-inc.php';
	include_once 'autentica_usuario.php';
	include_once 'funcoes.php';
	include 'plugins/fileuploader/TdocsMirror.php';
}
$imgExtensions = ["png","jpg","jpeg","gif","bmp","exif","tiff","ico"];
$videoExtensions = ["mp4", "MP4", "avi", "AVI"];
$comunicado = $_GET['comunicado'];
$sqlComunicado = "SELECT DISTINCT tbl_comunicado.comunicado,
				   tbl_comunicado.descricao,
				   TO_CHAR(tbl_comunicado.data,'dd/mm/yyyy') AS data,
				   tbl_comunicado.tipo,
				   tbl_produto.descricao AS produto_descricao,
				   tbl_produto.referencia_fabrica AS produto_referencia_fabrica,
				   tbl_comunicado.video,
				   tbl_comunicado.serie,
				   tbl_comunicado.ativo,
				   tbl_comunicado.extensao
			FROM tbl_comunicado
		 	LEFT JOIN tbl_comunicado_produto ON tbl_comunicado.comunicado = tbl_comunicado_produto.comunicado
			LEFT JOIN tbl_produto ON (tbl_produto.produto = tbl_comunicado_produto.produto OR tbl_produto.produto =
		 	tbl_comunicado.produto AND tbl_produto.fabrica_i = {$login_fabrica})
		 	LEFT JOIN tbl_linha   ON tbl_linha.linha = tbl_produto.linha
			WHERE tbl_comunicado.fabrica = {$login_fabrica}
			AND tbl_comunicado.comunicado = {$comunicado}";
$resComunicado = pg_query($con, $sqlComunicado);

$sqlTdocs = "SELECT tdocs_id 
		FROM tbl_tdocs
		WHERE referencia_id = '{$comunicado}'
		AND referencia = 'comunicados'
		AND contexto = 'comunicados'";
$resTdocs = pg_query($con, $sqlTdocs);

$tdocs = new TdocsMirror();
echo "<div >";

foreach (pg_fetch_all($resTdocs) as $docs) {
	$result = $tdocs->get($docs['tdocs_id']);
	$extensao = end(explode(".", $result['file_name']));

	if (in_array($extensao, $imgExtensions)) {
		echo "<img src='{$result['link']}' width='100%' height='100%' />";
	} else {
		echo "<iframe width='100%' height='100%' src='{$result['link']}'></iframe>";
	} 
}
echo "</div>";
