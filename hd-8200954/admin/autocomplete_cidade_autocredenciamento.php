<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';


if (!empty($_GET['e'])) {
	$e = strtoupper($_GET['e']);

	$query = pg_query($con, "SELECT cidade, cidade_pesquisa FROM tbl_ibge WHERE upper(estado) = '$e' order by cidade");
	$rows = pg_num_rows($query);

	if ($rows > 0) {
		echo '[';

		for ($i = 0; $i < $rows; $i++) {
			$cidade = pg_fetch_result($query, $i, 'cidade');
			$cidade_pesquisa = pg_fetch_result($query, $i, 'cidade_pesquisa');

			echo '{"cidade": "' ,  $cidade  , '", "cidade_pesquisa": "' , $cidade_pesquisa ,  '"}';

			if (($i + 1) <> $rows) {
				echo ',';
			}
		}

		echo ']';

	}
}

