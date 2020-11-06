<?php

	

	if(isset($_POST["defeito_constatado_grupo"])){

		$defeito_constatado_grupo = $_POST["defeito_constatado_grupo"];
		$familia = $_POST["familia"];
		$posto = $_POST["posto"];

		if(strlen($posto) > 0){
			$login_posto = $posto;
		}

		/*$sql_antigo = "SELECT 
					tbl_defeito_constatado.defeito_constatado,
					tbl_defeito_constatado.descricao        
				FROM tbl_defeito_constatado
				JOIN tbl_diagnostico USING(fabrica,defeito_constatado)
				JOIN tbl_posto_fabrica USING(tabela_mao_obra)
				WHERE 
					tbl_defeito_constatado.defeito_constatado_grupo = $defeito_constatado_grupo
					AND tbl_defeito_constatado.ativo
					AND tbl_diagnostico.ativo
					AND tbl_defeito_constatado.fabrica = $login_fabrica
					AND tbl_posto_fabrica.posto = $login_posto
					AND tbl_diagnostico.familia = $familia 
					AND tbl_defeito_constatado.ativo IS TRUE 
				ORDER BY tbl_defeito_constatado.descricao ASC";
		*/


		$sql = "SELECT DISTINCT
					tbl_defeito_constatado.defeito_constatado,
					tbl_defeito_constatado.descricao        
				FROM tbl_defeito_constatado
				JOIN tbl_diagnostico USING(fabrica,defeito_constatado)		
				WHERE 
					tbl_defeito_constatado.defeito_constatado_grupo = $defeito_constatado_grupo
					AND tbl_defeito_constatado.ativo
					AND tbl_diagnostico.ativo
					AND tbl_defeito_constatado.fabrica = $login_fabrica					
					AND tbl_diagnostico.familia = $familia 
					AND tbl_defeito_constatado.ativo IS TRUE 
				ORDER BY tbl_defeito_constatado.descricao ASC";				

		$res = pg_query($con, $sql);

		$defeitos_constatados = array();

		if(pg_num_rows($res) > 0){

			for($i = 0; $i < pg_num_rows($res); $i++){

				$defeito_constatado = pg_fetch_result($res, $i, "defeito_constatado");
				$descricao          = pg_fetch_result($res, $i, "descricao");

				$defeitos_constatados[] = array("defeito_constatado" => utf8_encode($defeito_constatado), "descricao" => utf8_encode($descricao));

			}

		}else{

			$defeitos_constatados[] = array("defeito_constatado" => "", "descricao" => "");

		}

		exit(json_encode(array("retorno" => $defeitos_constatados)));

	}

	if(isset($_POST["busca_defeito_constatado"])){

		$defeito_constatado = $_POST["defeito_constatado"];

		$sql = "SELECT 
					tbl_solucao.solucao,
					 tbl_solucao.descricao 
				FROM tbl_defeito_constatado_solucao 
				INNER JOIN tbl_solucao ON tbl_solucao.solucao = tbl_defeito_constatado_solucao.solucao AND tbl_solucao.fabrica = {$login_fabrica} AND tbl_solucao.ativo IS TRUE  
				WHERE 
					tbl_defeito_constatado_solucao.defeito_constatado = {$defeito_constatado} 
					AND tbl_defeito_constatado_solucao.fabrica = {$login_fabrica} 
					AND tbl_defeito_constatado_solucao.ativo IS TRUE 
					AND tbl_solucao.ativo IS TRUE 
				ORDER BY tbl_solucao.descricao ASC";
		$res = pg_query($con, $sql);

		$solucoes = array();

		if(pg_num_rows($res) > 0){

			for($i = 0; $i < pg_num_rows($res); $i++){

				$solucao = pg_fetch_result($res, $i, "solucao");
				$descricao = pg_fetch_result($res, $i, "descricao");

				$solucoes[] = array("solucao" => utf8_encode($solucao), "descricao" => utf8_encode($descricao));

			}

		}else{

			$solucoes[] = array("solucao" => "", "descricao" => "");

		}

		exit(json_encode(array("retorno" => $solucoes)));

	}

	if(isset($_POST["valida_data"])){

		$data_abertura = $_POST["data_abertura"];

		list($d, $m, $a) = explode("/", $data_abertura);
		$data_abertura = $a."-".$m."-".$d;

		$sql = "SELECT current_date - '{$data_abertura}' AS intervalo";
		$res = pg_query($con, $sql);

		$intervalo = pg_fetch_result($res, 0, "intervalo");

		$retorno = array("retorno" => $intervalo);

		exit(json_encode($retorno));

	}


?>