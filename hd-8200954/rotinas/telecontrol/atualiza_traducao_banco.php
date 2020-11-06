<?php

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require_once dirname(__FILE__) . '/../funcoes.php';

require_once dirname(__FILE__) . '/../../traducao.php';

pg_query($con, "BEGIN TRANSACTION");

try {
	foreach ($msg_traducao as $idioma => $frases) {
		$x_idioma[$idioma] = 0;
		foreach ($frases as $id_frase => $texto) {
			
			$sql = "SELECT msg_id
		                FROM tbl_msg 
		                JOIN tbl_idioma ON tbl_idioma.idioma_id = '$idioma'
		                AND tbl_msg.idioma = tbl_idioma.idioma
		                WHERE tbl_msg.msg_id = ".pg_escape_literal($con, $id_frase);
		    $res = pg_query($con, $sql);

			if (pg_last_error()) {
			    	throw new Exception("Erro");
			}

		    if (pg_num_rows($res) == 0) {
		    	$x_idioma[$idioma] += 1;
		    	$sql = "INSERT INTO tbl_msg (
		        				msg_id,
					        	idioma,
					        	msg_text
					       ) 
		    			VALUES (
		    				'$id_frase', 
		    				(
		    					SELECT tbl_idioma.idioma
		    					FROM tbl_idioma
		    					WHERE tbl_idioma.idioma_id = '$idioma'
		    					LIMIT 1
		    				),
		    				".pg_escape_literal($texto)."
		    				);";
		    	$res = pg_query($con, $sql);
		    }

		    if (pg_last_error()) {
		    	throw new Exception("Erro");
		    }
		}

	}
	pg_query($con, "COMMIT TRANSACTION");
} catch (Exception $e) {
	pg_query($con, "ROLLBACK TRANSACTION");

	echo "erro";
}

echo var_dump($x_idioma);
