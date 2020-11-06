<?php

	if($_POST['ajax_causa_defeito']){

		$defeitos = $_POST['defeitos'];

		$sql = "SELECT array_to_string(array_agg(codigo),',') AS codigos FROM tbl_defeito_constatado where defeito_constatado in($defeitos);";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){

			$codigos = explode(",",pg_fetch_result($res,0, "codigos"));

			forEach($codigos as $value){
		        $auxCodigos[] = "'".$value."'";
		    }

		    $codigos = implode(',', $auxCodigos);

			$sql = "SELECT DISTINCT tbl_causa_defeito.causa_defeito, 
							tbl_causa_defeito.codigo,
							tbl_causa_defeito.descricao 
						FROM tbl_defeito
						JOIN tbl_defeito_causa_defeito using(defeito)
						JOIN tbl_causa_defeito using(causa_defeito)
						WHERE codigo_defeito in($codigos) 
						AND tbl_causa_defeito.fabrica = $login_fabrica
						ORDER BY tbl_causa_defeito.descricao;";
			$res = pg_query($con,$sql);

			for ($i=0; $i < pg_num_rows($res); $i++) {
	            $causas[] = array('descricao' => utf8_encode(pg_fetch_result($res, $i, descricao)),'codigo' => pg_fetch_result($res, $i, 'causa_defeito'));
	        }
	    }

		if(count($causas) > 0){
			$causasJson = json_encode($causas);
		}else{
			$causasJson = json_encode(array("erro" => "Nenhuma Causa relacionada aos defeitos"));
		}

		echo $causasJson;
		exit;
	}



	if($_POST['ajax_servico_defeito']){

		$defeitos = $_POST['defeitos'];

		$sql = "SELECT array_to_string(array_agg(codigo),',') AS codigos FROM tbl_defeito_constatado where defeito_constatado in($defeitos);";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){

			$codigos = explode(",",pg_fetch_result($res,0, "codigos"));

			forEach($codigos as $value){
		        $auxCodigos[] = "'".$value."'";
		    }

		    $codigos = implode(',', $auxCodigos);

			$sql = "SELECT DISTINCT  tbl_servico_realizado.descricao,
									tbl_servico_realizado.servico_realizado
									FROM  tbl_defeito_servico_realizado
									JOIN  tbl_servico_realizado using(servico_realizado)
									JOIN  tbl_defeito on tbl_defeito.defeito = tbl_defeito_servico_realizado.defeito
									AND   tbl_defeito.codigo_defeito in ($codigos)
									AND   tbl_defeito.fabrica = $login_fabrica
									WHERE tbl_defeito.ativo IS TRUE
									AND   tbl_defeito_servico_realizado.ativo IS TRUE
									AND   tbl_servico_realizado.ativo IS TRUE
									ORDER BY tbl_servico_realizado.descricao";
			$res = pg_query($con,$sql);

			for ($i=0; $i < pg_num_rows($res); $i++) {
	            $servicos[] = array('descricao' => utf8_encode(pg_fetch_result($res, $i, 'descricao')),'codigo' => pg_fetch_result($res, $i, 'servico_realizado'));
	        }
	    }

		if(count($servicos) > 0){
			$defeitosJson = json_encode($servicos);
		}else{
			$defeitosJson = json_encode(array("erro" => "Nenhum serviço relacionado aos defeitos"));
		}

		echo $defeitosJson;
		exit;
	}
?>