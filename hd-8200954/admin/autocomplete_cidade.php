<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$estado   = trim(strtoupper($_GET["estado"]));
$cidade   = utf8_decode(trim(strtoupper($_GET["q"])));
//$cidade   = trim(strtoupper($_GET["q"]));

if (!empty($estado))
{
	switch ($estado)
	{
		case "CENTRO-OESTE":
			$estado = "'GO','MT','MS','DF'";
		break;

		case "NORDESTE":
			$estado = "'MA','PI','CE','RN','PB','PE','AL','SE','BA'";
		break;

		case "NORTE":
			$estado = "'AC','AM','RR','RO','PA','AP','TO'";
		break;

		case "SUDESTE":
			$estado = "'MG','ES','RJ','SP'";
		break;

		case "SUL":
			$estado = "'PR','SC','RS'";
		break;

		default :
			$estado = "'".$estado."'";
		break;
	}

	$sql = "SELECT cidade, estado FROM tbl_ibge WHERE estado IN($estado) AND UPPER(fn_retira_especiais(cidade)) ILIKE UPPER(fn_retira_especias('%$cidade%'))";
	$res = pg_query($con,$sql);
	if (pg_num_rows($res) > 0)
	{
		for ($i = 0; $i < pg_num_rows($res); $i++)
		{
			$cidade = trim(pg_result($res,$i,"cidade"));
			$estado = trim(pg_result($res,$i,"estado"));

			echo $cidade."|".$estado."\n";
		}
	}
	else
	{
		echo "Cidade|Não Encontrada";
	}
}
?>
