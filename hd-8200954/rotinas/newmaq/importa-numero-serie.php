<?php
try {
	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require_once dirname(__FILE__) . '/../funcoes.php';

	$fabrica  = "120" ;

	$phpCron = new PHPCron($fabrica, __FILE__); 
	$phpCron->inicio();
    
    $data    = date("d-m-Y-H-i");

	$arquivos = "/tmp/newmaq";
	$origem   = "/home/newmaq/newmaq-telecontrol/";

	$sql = "SELECT TO_CHAR(current_timestamp, 'DDMMYYYYHH24MI');";
	$res = pg_query($con, $sql); echo pg_last_error();

	$data_bkp = pg_fetch_result($res, 0, 0);

	$log  = array();

	$arquivo = "{$origem}telecontrol-numero-serie.txt";

	if (file_exists($arquivo)) {
		$sql = "CREATE TEMP TABLE tmp_newmaq_numero_serie (
					serie text,
					cnpj text,
					referencia text,
					venda date,
					fabricacao date,
					insert boolean,
					update boolean,
					produto int,
					numero_serie int,
					data_fabricacao date
				);

				CREATE TEMP TABLE tmp_newmaq_numero_serie_duplicado (
					serie text,
					produto int
				)";
		$res = pg_query($con, $sql); echo pg_last_error();

		$arquivo_conteudo = file_get_contents($arquivo);
		$arquivo_conteudo = explode("\n", $arquivo_conteudo);

		foreach ($arquivo_conteudo as $linha) {
			if ($linha == "\n") {
				continue;
			} else {
				list($serie, $cnpj, $referencia, $venda, $fabricacao) = explode("\t", $linha);

				$serie      = trim($serie);
				$serie      = strtoupper($serie);
				$cnpj       = trim($cnpj);
				$referencia = trim($referencia);
				$venda      = trim($venda);
				$fabricacao = trim($fabricacao);

				$venda = (empty($venda)) ?  "null" : "'".$venda."'";
				$fabricacao = (empty($fabricacao)) ? "null" : "'".$fabricacao."'";

				$sql = "INSERT INTO tmp_newmaq_numero_serie
						(serie, cnpj, referencia, venda, fabricacao)
						VALUES
						('{$serie}', '{$cnpj}','{$referencia}', $venda, $fabricacao)";
				$res = pg_query($con, $sql); echo pg_last_error();
			}
		}

		$sql = "UPDATE tmp_newmaq_numero_serie
				SET produto = tbl_produto.produto
				FROM tbl_produto
				JOIN tbl_linha USING (linha)
				WHERE tmp_newmaq_numero_serie.referencia = tbl_produto.referencia
				AND tbl_produto.fabrica_i = {$fabrica}";
		$res = pg_query($con, $sql); echo pg_last_error();

		$sql = "SELECT serie, referencia FROM tmp_newmaq_numero_serie WHERE produto IS NULL";
		$res = pg_query($con, $sql); echo pg_last_error();
		$rows = pg_num_rows($res);

		for ($i = 0; $i < $rows; $i++) { 
			$serie      = pg_fetch_result($res, $i, "serie");
			$referencia = pg_fetch_result($res, $i, "referencia");

			$log[] = "SÉRIE {$serie} NÃO FOI CADASTRADA POIS O PRODUTO {$referencia} NÃO ESTÁ CADASTRADO NO SISTEMA";
		}

		$sql = "DELETE FROM tmp_newmaq_numero_serie WHERE produto IS NULL";
		$res = pg_query($con, $sql); echo pg_last_error();

		$sql = "SELECT serie, referencia FROM tmp_newmaq_numero_serie WHERE fabricacao IS NULL";
		$res = pg_query($con, $sql); echo pg_last_error();
		$rows = pg_num_rows($res);

		for ($i = 0; $i < $rows; $i++) { 
			$serie      = pg_fetch_result($res, $i, "serie");
			$referencia = pg_fetch_result($res, $i, "referencia");
			
			$log[] = "SÉRIE {$serie} - PRODUTO {$referencia} NÃO FOI CADASTRADA POIS ESTÁ SEM A DATA DE FABRICAÇÃO";
		}

		$sql = "DELETE FROM tmp_newmaq_numero_serie WHERE fabricacao IS NULL";
		$res = pg_query($con, $sql); echo pg_last_error();

		$sql = "INSERT INTO tmp_newmaq_numero_serie_duplicado
                       (serie, produto)
                       SELECT serie, produto
                       FROM tmp_newmaq_numero_serie
                       GROUP BY serie, produto
                       HAVING COUNT(*) > 1";
	        $res = pg_query($con, $sql); echo pg_last_error();

		$sql = "DELETE FROM tmp_newmaq_numero_serie
                                  USING tmp_newmaq_numero_serie_duplicado
			          WHERE tmp_newmaq_numero_serie_duplicado.serie = tmp_newmaq_numero_serie.serie
			          AND tmp_newmaq_numero_serie_duplicado.produto = tmp_newmaq_numero_serie.produto";
		$res = pg_query($con, $sql); echo pg_last_error();

		$sql = "UPDATE tmp_newmaq_numero_serie
				SET numero_serie = tbl_numero_serie.numero_serie
				FROM tbl_numero_serie
				WHERE tmp_newmaq_numero_serie.produto = tbl_numero_serie.produto
				AND tmp_newmaq_numero_serie.serie = tbl_numero_serie.serie
				AND tbl_numero_serie.fabrica = {$fabrica}";
		$res = pg_query($con, $sql); echo pg_last_error();

		$sql = "UPDATE tmp_newmaq_numero_serie
				SET insert = TRUE
				WHERE numero_serie IS NULL";
		$res = pg_query($con, $sql); echo pg_last_error();

		$sql = "UPDATE tmp_newmaq_numero_serie
				SET update = TRUE
				WHERE numero_serie IS NOT NULL";
		$res = pg_query($con, $sql); echo pg_last_error();

		$sql = "INSERT INTO tbl_numero_serie
				(fabrica, serie, cnpj, referencia_produto, produto, data_venda, data_fabricacao)
				SELECT DISTINCT 
				{$fabrica}, serie, cnpj, referencia, produto, venda, fabricacao
				FROM tmp_newmaq_numero_serie
				WHERE insert IS TRUE";
		$res = pg_query($con, $sql); echo pg_last_error();


		$sql = "SELECT COUNT(*) AS total_insert FROM tmp_newmaq_numero_serie WHERE insert IS TRUE";
		$res = pg_query($con, $sql); echo pg_last_error();
		$rows = pg_num_rows($res);

		$total_insert = pg_fetch_result($res, 0, "total_insert");

		$log[] = "$total_insert NÚMEROS DE SÉRIE INSERIDOS";

                system("mv {$arquivo} /tmp/newmaq/telecontrol-numero-serie_{$data_bkp}.txt");
	}else{
		$log[] = "ARQUIVO DE NÚMERO DE SÉRIE NÃO ENCONTRADO";
	}

	$header  = "MIME-Version: 1.0\n";
	$header .= "Content-type: text/html; charset=iso-8859-1\n";
	$header .= "From: Telecontrol <telecontrol\@telecontrol.com.br>";

	mail("helpdesk@telecontrol.com.br", "TELECONTROL / newmaq ({$data}) - IMPORTA NÚMERO DE SÉRIE", implode("<br />", $log), $header);

	$phpCron->termino();
} catch (Exception $e) {
	$log[] = date("H-i")."ERRO AO IMPORTAR PREÇOS: ".$e->getMessage();

	$header  = "MIME-Version: 1.0\n";
	$header .= "Content-type: text/html; charset=iso-8859-1\n";
	$header .= "From: Telecontrol <telecontrol\@telecontrol.com.br>";

	mail("helpdesk@telecontrol.com.br", "TELECONTROL / newmaq ({$data}) - IMPORTA NÚMERO DE SÉRIE", implode("<br />", $log), $header);

	system("mv {$arquivo} /tmp/newmaq/num_serie_{$data_bkp}.txt");
}
