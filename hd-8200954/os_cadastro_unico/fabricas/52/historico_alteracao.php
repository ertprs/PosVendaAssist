<?php

	if($areaAdmin === true){

		/* Info Peça */
		$peca               = $campos_peca["id"];
		$referencia 		= $campos_peca["referencia"];
		$defeito_peca 		= $campos_peca["defeito_peca"];
		$servico_realizado  = $campos_peca["servico_realizado"];
		$os_item            = $campos_peca["os_item"];

		if(!empty($os_item)){

			/* Defeito */

			$sql = "SELECT 
						defeito,
						descricao 
					FROM tbl_defeito  
					WHERE 
						fabrica = {$login_fabrica} 
						AND defeito = (SELECT defeito FROM tbl_os_item WHERE os_item = {$os_item})";
			$res = pg_query($con, $sql);

			if(pg_num_rows($res) > 0){

				$defeito_peca_os = pg_fetch_result($res, 0, "defeito");
				$desc_defeito_peca_os = pg_fetch_result($res, 0, "descricao");

			}

			if(strlen($defeito_peca) == 0 && strlen($defeito_peca_os) > 0){

				$historico[] = "Defeito da Peça {$referencia} alterado de <strong>{$desc_defeito_peca_os}</strong> para <strong>Nenhum Defeito da Peça não Inserido</strong>";

			}

			if(strlen($defeito_peca) > 0 && strlen($defeito_peca_os) > 0){
				if($defeito_peca != $defeito_peca_os){

					$sql = "SELECT descricao FROM tbl_defeito WHERE defeito = {$defeito_peca} AND fabrica = {$login_fabrica}";
					$res = pg_query($con, $sql);

					$desc_defeito = pg_fetch_result($res, 0, "descricao");

					$historico[] = "Defeito da Peça {$referencia} alterado de <strong>{$desc_defeito_peca_os}</strong> para <strong>{$desc_defeito}</strong>";
				}
			}

			/* Serviço */

			$sql = "SELECT 
						servico_realizado,
						descricao 
					FROM tbl_servico_realizado 
					WHERE 
						fabrica = {$login_fabrica} 
						AND servico_realizado = (SELECT servico_realizado FROM tbl_os_item WHERE os_item = {$os_item})";
			$res = pg_query($con, $sql);

			if(pg_num_rows($res) > 0){

				$servico_realizado_os = pg_fetch_result($res, 0, "servico_realizado");
				$desc_servico_realizado_os = pg_fetch_result($res, 0, "descricao");

			}

			if(strlen($servico_realizado) == 0 && strlen($servico_realizado_os) > 0){

				$historico[] = "Servico Realizado da Peça {$referencia} alterado de <strong>{$desc_servico_realizado_os}</strong> para <strong>Nenhum Servico Realizado da Peça não Inserido</strong>";

			}

			if(strlen($defeito_peca) > 0 && strlen($defeito_peca_os) > 0){
				if($servico_realizado != $servico_realizado_os){

					$sql = "SELECT descricao FROM tbl_servico_realizado WHERE servico_realizado = {$servico_realizado} AND fabrica = {$login_fabrica}";
					$res = pg_query($con, $sql);

					$desc_servico = pg_fetch_result($res, 0, "descricao");

					$historico[] = "Servico Realizado da Peça {$referencia} alterado de <strong>{$desc_servico_realizado_os}</strong> para <strong>{$desc_servico}</strong>";
				}
			}

		}

		/* Peças Novas */

		$sql = "SELECT peca FROM tbl_os_item WHERE os_produto in (SELECT os_produto FROM tbl_os_produto WHERE os = {$os})";
		$res = pg_query($con, $sql);

		if(pg_num_rows($res) > 0){

			$pecas_os_item = array();

			for ($i = 0; $i < pg_num_rows($res); $i++) { 
				$pecas_os_item[] = pg_fetch_result($res, $i, "peca");
			}

			if(!in_array($peca, $pecas_os_item)){
				$historico[] = "A Peça <strong>{$referencia}</strong> foi inserida na lista de peças da OS {$os}";
			}

		}

	}

?>
