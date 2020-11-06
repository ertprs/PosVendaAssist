<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$mes = date("n");
$ano = date("Y");
$comunicado = intval($_GET["comunicado"]);
$arquivo = "comunicados/$comunicado." . $_GET["tipo"];

$sql = "
SELECT
tbl_comunicado.comunicado

FROM
tbl_comunicado 
LEFT JOIN tbl_comunicado_produto ON tbl_comunicado_produto.comunicado = tbl_comunicado.comunicado
LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_comunicado.produto
LEFT JOIN tbl_produto prod ON prod.produto = tbl_comunicado_produto.produto

WHERE
tbl_comunicado.fabrica = $login_fabrica
AND (tbl_comunicado.posto      = $login_posto OR  tbl_comunicado.posto      IS NULL)
AND tbl_comunicado.ativo IS TRUE
AND tbl_comunicado.comunicado=$comunicado
";
if($login_fabrica==5){
	$sql .= "
	AND (tbl_produto.ativo IS TRUE OR prod.ativo IS TRUE) ";
}

$res = pg_exec($con, $sql);

if (pg_num_rows($res) == 0)
{
	echo "Comunicado Inexistente!!";
	die;
}

if(!file_exists($arquivo) == true)
{
	echo "Arquivo Inexistente!!";
	die;
}

$sql = "
SELECT
comunicado_download

FROM
tbl_comunicado_download

WHERE
	fabrica		= $login_fabrica
AND comunicado	= $comunicado
AND mes			= $mes
AND ano			= $ano
";
$res = pg_exec($con, $sql);

if (pg_num_rows($res) > 0)
{
	$comunicado_download = pg_result($res, 0, comunicado_download);
	
	$sql = "
	UPDATE
	tbl_comunicado_download

	SET
	qtde = qtde+1

	WHERE
	comunicado_download = $comunicado_download
	";
}
else
{
	$sql = "
	INSERT INTO
	tbl_comunicado_download(fabrica, comunicado, mes, ano, qtde)
	VALUES($login_fabrica, $comunicado, $mes, $ano, 1)
	";
}

$res = pg_exec($con, $sql);

$sql = "
INSERT INTO
tbl_comunicado_download_log(fabrica, comunicado, posto, data)
VALUES($login_fabrica, $comunicado, $login_posto, current_date)
";
$res = pg_exec($con, $sql);

header("location:" . $arquivo);

?>