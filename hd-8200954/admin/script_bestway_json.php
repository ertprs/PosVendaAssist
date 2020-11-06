<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$sql = "SELECT tbl_hd_chamado.hd_chamado, tbl_hd_chamado_extra.array_campos_adicionais 
	FROM tbl_hd_chamado 
	JOIN tbl_hd_chamado_extra USING(hd_chamado)
	WHERE tbl_hd_chamado.fabrica = 81
	AND tbl_hd_chamado_extra.array_campos_adicionais IS NOT NULL";
$res = pg_query($con, $sql);

$rows = pg_num_rows($res);

$fp = fopen("/tmp/bestway_json.txt", "w");

if ($rows > 0) {
	for ($i = 0; $i < $rows; $i++) {
		$array_campos_adicionais = pg_fetch_result($res, $i, "array_campos_adicionais");

		if (json_decode($array_campos_adicionais, true) === null) {
			$hd_chamado = pg_fetch_result($res, $i, "hd_chamado");

			fwrite($fp, "{$hd_chamado}\|/{$array_campos_adicionais}\n");

			$new_array = array();

			$array = explode("||", $array_campos_adicionais);

			foreach ($array as $valor) {
				list($key, $value) = explode("=>", $valor);

				$new_array[$key] = utf8_encode($value);
			}

			$array_campos_adicionais = json_encode($new_array);

			pg_query($con, "BEGIN");

			$xsql = "UPDATE tbl_hd_chamado_extra
				SET array_campos_adicionais = '$array_campos_adicionais'
				WHERE hd_chamado = $hd_chamado";
			$xres = pg_query($con, $xsql);

			if (strlen(pg_last_error()) > 0) {
				pg_query($con, "ROLLBACK");
			} else {
				pg_query($con, "COMMIT");
			}
		}
	}
}

fclose($fp);

?>
