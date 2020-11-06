<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$q = $_GET["q"];

if (!empty($q))
{
	$sql = "SELECT cod_ibge, cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('$q')";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0)
	{
		echo "<select name='cidade' style='width: 200px;'>
					<option value=''>Todas as Cidades</option>";
			
		for ($i = 0; $i < pg_num_rows($res); $i++)
		{
			$cod_ibge = pg_result($res, $i, "cod_ibge");
			$cidade   = pg_result($res, $i, "cidade");

			echo "<option value='$cod_ibge'>$cidade</option>";
		}

		echo "</select>";
	}
	else
	{
		echo "<p style='color: red;'>$q Nenhuma cidade encontrada</p>";
	}
}

?>