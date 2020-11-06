<?php
	function replica_anexo_os_revenda ($uniqueId, $contexto, $row, $reference, $reference_id, $hash_temp){
		global $con, $login_fabrica;
		
		$domain = $row[0]["page"];
		$os_press 			 = strstr($domain, 'os_press');
		$os_revenda_press 	 = strstr($domain, 'os_revenda_press');
		$cadastro_os 		 = strstr($domain, 'cadastro_os');
		$cadastro_os_revenda = strstr($domain, 'cadastro_os_revenda');

		$sql_os_revenda = "SELECT DISTINCT os_revenda FROM tbl_os_campo_extra WHERE fabrica = {$login_fabrica} AND (os = {$reference_id} OR os_revenda = {$reference_id})";
		$res_os_revenda = pg_query($con, $sql_os_revenda);

		if (pg_num_rows($res_os_revenda) > 0){
			$os_revenda = pg_fetch_result($res_os_revenda, 0, "os_revenda");
			$obs 		= json_encode($row);

			if (!empty($hash_temp)){
				$campo = ", hash_temp";
				$valor = ", '$hash_temp'";
			}

			// -- INSERT OS REVENDA -- //
			if (empty($cadastro_os_revenda) AND (!empty($os_press) OR !empty($cadastro_os))){
				$cond_not_in = " AND os not in ($reference_id) ";
				foreach ($row as $key => $value) {
					$sql_insert = "
						INSERT INTO tbl_tdocs(
							tdocs_id,
							fabrica,
							contexto,
							situacao,
							obs,
							referencia,
							referencia_id
							{$campo}
						) values (
							'$uniqueId',
							$login_fabrica,
							'revenda',
							'ativo',
							'$obs', 
							'revenda', 
							$os_revenda
							{$valor}
						)";
					$res_insert = pg_query($con, $sql_insert);
				}
			}
			
			$sql_os = "SELECT os FROM tbl_os_campo_extra WHERE fabrica = {$login_fabrica} AND os_revenda = {$os_revenda} $cond_not_in";
			$res_os = pg_query($con, $sql_os);

			if (pg_num_rows($res_os) > 0){
				for ($i=0; $i < pg_num_rows($res_os); $i++) { 
					$id_os = pg_fetch_result($res_os, $i, "os");
					foreach ($row as $key => $value) {
						if ((!empty($os_press) OR !empty($cadastro_os) OR !empty($cadastro_os_revenda)) AND ($value["typeId"] == "notafiscal" OR $value["typeId"] == "assinatura")){
							$sql_insert = "
								INSERT INTO tbl_tdocs(
									tdocs_id,
									fabrica,
									contexto,
									situacao,
									obs,
									referencia,
									referencia_id
									{$campo}
								) values (
									'$uniqueId',
									$login_fabrica,
									'$contexto',
									'ativo',
									'$obs', 
									'$reference', 
									$id_os
									{$valor}
								)";
							$res_insert = pg_query($con, $sql_insert);
						}else if (!empty($os_revenda_press)){
							$sql_insert = "
								INSERT INTO tbl_tdocs(
									tdocs_id,
									fabrica,
									contexto,
									situacao,
									obs,
									referencia,
									referencia_id
									{$campo}
								) values (
									'$uniqueId',
									$login_fabrica,
									'os',
									'ativo',
									'$obs', 
									'os', 
									$id_os
									{$valor}
								)";
							$res_insert = pg_query($con, $sql_insert);
						}
					}
				}
			}
		}	
	}

	function cancela_anexos_os_revenda ($reference, $tdocsId, $hash_temp){
		global $con, $login_fabrica;
		
		$sql_os_revenda = "SELECT DISTINCT os_revenda FROM tbl_os_campo_extra WHERE fabrica = {$login_fabrica} AND (os = {$reference} OR os_revenda = {$reference})";
		$res_os_revenda = pg_query($con, $sql_os_revenda);

		if (pg_num_rows($res_os_revenda) > 0){
			$os_revenda = pg_fetch_result($res_os_revenda, 0, "os_revenda");
			$obs 		= json_encode($row);

			if (!empty($hash_temp)){
				$campo = ", hash_temp";
				$valor = ", '$hash_temp'";
			}
			
			if ($hash_temp){
				$sql = "UPDATE tbl_tdocs SET situacao = 'inativo' WHERE hash_temp = '$os_revenda' and tdocs_id = '$tdocsId'";
	            $res = pg_query($con, $sql);
			}else{
				$sql = "UPDATE tbl_tdocs SET situacao = 'inativo' WHERE tdocs_id = '$tdocsId' AND referencia_id = $os_revenda";
				$res = pg_query($con, $sql);
			}

			$sql_os = "SELECT os FROM tbl_os_campo_extra WHERE fabrica = {$login_fabrica} AND os_revenda = {$os_revenda}";
			$res_os = pg_query($con, $sql_os);

			if (pg_num_rows($res_os) > 0){
				for ($i=0; $i < pg_num_rows($res_os); $i++) { 
					$id_os = pg_fetch_result($res_os, $i, "os");
					
					if ($hash_temp){
						$sql = "UPDATE tbl_tdocs SET situacao = 'inativo' WHERE hash_temp = '$id_os' and tdocs_id = '$tdocsId'";
			            $res = pg_query($con, $sql);
					}else{
						$sql = "UPDATE tbl_tdocs SET situacao = 'inativo' WHERE tdocs_id = '$tdocsId' AND referencia_id = $id_os";
						$res = pg_query($con, $sql);
					}
				}
			}
		}
	}
?>