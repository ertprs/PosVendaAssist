<?php

try {
	include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require_once dirname(__FILE__) . '/../funcoes.php';

    $fabrica = 15;

    $phpCron = new PHPCron($fabrica, __FILE__); 
	$phpCron->inicio();
    
    $data    = date("d-m-Y-H-i");
    $log     = array();

    $arquivo       = "/www/cgi-bin/latinatec/entrada/tabela_preco_item.txt";
	#$arquivo        = "entrada/tabela_preco_item.txt";
	$arquivo_temp   = "/tmp/latinatec/tabela_preco_item_temp_{$data}.txt";
	$arquivo_backup = "/tmp/latinatec/tabela_preco_item_backup_{$data}.txt";
	#$arquivo_backup = "entrada/backup/tabela_preco_item_backup_{$data}.txt";

	$log[] = "INÍCIO: ".date("H:i");

	if (file_exists($arquivo)) {
		system("cp {$arquivo} {$arquivo_temp}");

		$sql = "CREATE TEMP TABLE tmp_latinatec (
					sigla_tabela text,
					tabela int,
					referencia text,
					peca int,
					preco double precision,
					update boolean,
					insert boolean
				)";
		$res = pg_query($con, $sql);

		$arquivo_conteudo = explode("\n", file_get_contents($arquivo_temp));

		foreach ($arquivo_conteudo as $linha_numero => $linha_conteudo) {
			$linha_erro = false;

			list($sigla_tabela, $referencia, $preco) = explode(";", $linha_conteudo);

			$sigla_tabela = trim(str_replace("/", "", $sigla_tabela));
			$referencia   = trim($referencia);
			$preco        = trim(str_replace(",", ".", $preco));

			if (!strlen($sigla_tabela)) {
				$log[] = "LINHA ".($linha_numero + 1).": - TABELA DE PREÇO NÃO INFORMADA";
				$linha_erro = true;
			}

			if (!strlen($referencia)) {
				$log[] = "LINHA ".($linha_numero + 1).": - REFERÊNCIA NÃO INFORMADA";
				$linha_erro = true;
			}

			if (!strlen($preco)) {
				$preco = 0;
			}

			if (!(preg_match("/^[0-9]*\.[0-9]{1,2}$/", $preco) || preg_match("/^[0-9]*$/", $preco))) {
				$log[] = "LINHA ".($linha_numero + 1).": - PREÇO EM FORMATO INCORRETO";
				$linha_erro = true;
			}

			if ($linha_erro === false) {
				$sql = "INSERT INTO tmp_latinatec
						(sigla_tabela, referencia, preco)
						VALUES
						('{$sigla_tabela}', '{$referencia}', {$preco})";
				$res = pg_query($con, $sql);
			}
		}

		$sql = "UPDATE tmp_latinatec
				SET peca = tbl_peca.peca
				FROM tbl_peca
				WHERE tmp_latinatec.referencia = tbl_peca.referencia
				AND tbl_peca.fabrica = {$fabrica}";
		$res = pg_query($con, $sql);

		$sql = "UPDATE tmp_latinatec
				SET tabela = tbl_tabela.tabela
				FROM tbl_tabela
				WHERE UPPER(tmp_latinatec.sigla_tabela) = UPPER(tbl_tabela.sigla_tabela)
				AND tbl_tabela.fabrica = {$fabrica}";
		$res = pg_query($con, $sql);

		$sql  = "SELECT DISTINCT referencia FROM tmp_latinatec WHERE peca IS NULL";
		$res  = pg_query($con, $sql);
		$rows = pg_num_rows($res);

		if ($rows > 0) {
			$log[] = "<br /><br />-----------------------------";
			$log[] = "PEÇAS NÃO ENCOTRADAS NO SISTEMA";

			for ($i = 0; $i < $rows; $i++) {
				$log[] = pg_fetch_result($res, $i, "referencia");
			}
			$log[] = "-----------------------------<br /><br />";

			$sql = "DELETE FROM tmp_latinatec WHERE peca IS NULL";
			$res = pg_query($con, $sql);
		}

		$sql  = "SELECT DISTINCT sigla_tabela FROM tmp_latinatec WHERE tabela IS NULL";
		$res  = pg_query($con, $sql);
		$rows = pg_num_rows($res);

		if ($rows > 0) {
			$log[] = "<br /><br />-----------------------------";
			$log[] = "TABELAS NÃO ENCOTRADAS NO SISTEMA";

			for ($i = 0; $i < $rows; $i++) {
				$log[] = pg_fetch_result($res, $i, "sigla_tabela");
			}
			$log[] = "-----------------------------<br /><br />";

			$sql = "DELETE FROM tmp_latinatec WHERE tabela IS NULL";
			$res = pg_query($con, $sql);
		}

		$sql = "CREATE TEMP TABLE tmp_latinatec_item_duplicado (
					sigla_tabela text,
					referencia text
				)";
		$res = pg_query($con, $sql);

		$sql  = "SELECT sigla_tabela, referencia
				 INTO tmp_latinatec_item_duplicado
				 FROM tmp_latinatec 
				 GROUP BY referencia, sigla_tabela 
				 HAVING COUNT(*) > 1";
		$res  = pg_query($con, $sql);
		$rows = pg_num_rows($res);

		if ($rows > 0) {
			$log[] = "<br /><br />-----------------------------";
			$log[] = "PEÇAS APARECENDO MAIS DE UMA VEZ PARA A MESMA TABELA";

			for ($i = 0; $i < $rows; $i++) {
				$referencia   = pg_fetch_result($res, $i, "referencia");
				$sigla_tabela = pg_fetch_result($res, $i, "sigla_tabela");
				$qtde         = pg_fetch_result($res, $i, "qtde");

				$log[] = "REFERÊNCIA: {$referencia}, TABELA: {$sigla_tabela}, QUANTIDADE: {$qtde}";
			}
			$log[] = "-----------------------------<br /><br />";

			$sql = "DELETE FROM tmp_latinatec 
					USING tmp_latinatec_item_duplicado 
					WHERE tmp_latinatec.referencia = tmp_latinatec_item_duplicado.referencia
					AND tmp_latinatec.sigla_tabela = tmp_latinatec_item_duplicado.sigla_tabela";
			$res = pg_query($con, $sql);
		}

		$sql = "UPDATE tmp_latinatec
				SET update = true
				FROM tbl_tabela_item
				WHERE tmp_latinatec.tabela = tbl_tabela_item.tabela
				AND tmp_latinatec.peca = tbl_tabela_item.peca";
		$res = pg_query($con, $sql);

		$sql = "UPDATE tmp_latinatec
				SET insert = true
				WHERE update IS NOT TRUE";
		$res = pg_query($con, $sql);

		$sql  = "SELECT referencia, sigla_tabela, preco FROM tmp_latinatec WHERE update IS TRUE";
		$res  = pg_query($con, $sql);
		$rows = pg_num_rows($res);

		if ($rows > 0) {
			$log[] = "<br /><br />-----------------------------";
			$log[] = "PEÇAS ATUALIZADAS";

			for ($i = 0; $i < $rows; $i++) { 
				$referencia   = pg_fetch_result($res, $i, "referencia");
				$sigla_tabela = pg_fetch_result($res, $i, "sigla_tabela");
				$preco        = pg_fetch_result($res, $i, "preco");

				$log[] = "REFERÊNCIA: {$referencia}, TABELA: {$sigla_tabela}, PREÇO: {$preco}";
			}

			$log[] = "-----------------------------<br /><br />";

			$sql = "UPDATE tbl_tabela_item
					SET preco = tmp_latinatec.preco
					FROM tmp_latinatec
					WHERE update IS TRUE
					AND tbl_tabela_item.peca = tmp_latinatec.peca
					AND tbl_tabela_item.tabela = tmp_latinatec.tabela";
			$res = pg_query($con, $sql);
		}

		$sql  = "SELECT referencia, sigla_tabela, preco FROM tmp_latinatec WHERE insert IS TRUE";
		$res  = pg_query($con, $sql);
		$rows = pg_num_rows($res);

		if ($rows > 0) {
			$log[] = "<br /><br />-----------------------------";
			$log[] = "PEÇAS INSERIDAS";

			for ($i = 0; $i < $rows; $i++) { 
				$referencia   = pg_fetch_result($res, $i, "referencia");
				$sigla_tabela = pg_fetch_result($res, $i, "sigla_tabela");
				$preco        = pg_fetch_result($res, $i, "preco");

				$log[] = "REFERÊNCIA: {$referencia}, TABELA: {$sigla_tabela}, PREÇO: {$preco}";
			}

			$log[] = "-----------------------------<br /><br />";

			$sql = "INSERT INTO tbl_tabela_item 
					(tabela, peca, preco)
					SELECT tabela, peca, preco
					FROM tmp_latinatec
					WHERE insert IS TRUE";
			$res = pg_query($con, $sql);
		}
	} else {
		$log[] = "Arquivo não encontrado";
	}

	$log[] = "FIM: ".date("H:i");

	$header  = "MIME-Version: 1.0\n";
	$header .= "Content-type: text/html; charset=iso-8859-1\n";
	$header .= "From: Telecontrol <telecontrol\@telecontrol.com.br>";

	mail("iuri.brito@latina.com.br, marcelo.cardoso@latina.com.br, helpdesk@telecontrol.com.br", "TELECONTROL / LATINATEC ({$data}) - IMPORTA PREÇOS", implode("<br />", $log), $header);

	system("mv $arquivo_temp $arquivo_backup");

	$phpCron->termino();
} catch (Exception $e) {
	$log[] = date("H-i")."ERRO AO IMPORTAR PREÇOS: ".$e->getMessage();
	$log[] = "FIM: ".date("H-i");

	$header  = "MIME-Version: 1.0\n";
	$header .= "Content-type: text/html; charset=iso-8859-1\n";
	$header .= "From: Telecontrol <telecontrol\@telecontrol.com.br>";

	mail("iuri.brito@latina.com.br, marcelo.cardoso@latina.com.br, helpdesk@telecontrol.com.br", "TELECONTROL / LATINATEC ({$data}) - IMPORTA PREÇOS", implode("<br />", $log), $header);

	system("mv $arquivo_temp $arquivo_backup");
}
