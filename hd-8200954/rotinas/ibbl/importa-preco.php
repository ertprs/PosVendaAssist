<?php

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';

$fabrica = 90;
$arquivo = '/home/ibbl/ibbl-telecontrol/tab_preco.txt';

$phpCron = new PHPCron($fabrica, __FILE__); 
$phpCron->inicio();

$vet['fabrica'] = 'ibbl';
$vet['tipo'] = 'preco';

function enviaEmail($dest = array())
{
	$data = date('Y-m-d-H');
	$log_erro = '/tmp/ibbl/importa-preco-' . $data . '.erro';

	$msg = '';

	if (file_exists($log_erro) and (filesize($log_erro) > 0)) {
		$msg_tmp = @file_get_contents($log_erro);

		if (!empty($msg_tmp)) {
			$msg.= '<br/><br/>';
			$msg.= str_replace("\n", "<br/>", $msg_tmp);
		}
		
	} else {
		$msg.= "Erro ao executar importa-preco.php da IBBL.<br/>
				Não foi possível identificar o arquivo de log do erro.<br/>
				Favor verificar os arquivo gerados em /tmp/ibbl";
	}

	if (empty($dest)) {
		$vet['dest'] = 'helpdesk@telecontrol.com.br';
	} else {
		$dest[] = 'helpdesk@telecontrol.com.br';
		$vet['dest'] = $dest;
	}

	Log::envia_email($vet, utf8_decode("ERRO: Importa preço IBBL"), $msg);
}

$envia_email_erro = 0;

if (file_exists($arquivo) and (filesize($arquivo) > 0)) {

	$sql = "CREATE TEMP TABLE tmp_ibbl_preco (sigla_tabela text, referencia text, preco float)";
	$query = pg_query($con, $sql);

	$prepare = pg_prepare($con, "insert", "INSERT INTO tmp_ibbl_preco (sigla_tabela, referencia, preco) VALUES ($1, $2, $3)");

	if (!is_resource($prepare)) {
		$vet['log'] = 2;
		Log::log2($vet, "Erro ao preparar as queries.\n");
		enviaEmail();
		exit(1);
	}

	$conteudo = @file_get_contents($arquivo);
	if (empty($conteudo)) {
		$vet['log'] = 2;
		Log::log2($vet, 'Não foi possível ler o conteúdo do arquivo: ' . $arquivo . "\n");
		enviaEmail();
		exit(1);
	}

	$conteudo = str_replace("\r", "", $conteudo);
	$arr_conteudo = explode("\n",$conteudo);

	foreach ($arr_conteudo as $linha) {

		if (!empty($linha)) {
			list($sigla_tabela, $referencia, $preco) = explode("\t", $linha);
			$sigla_tabela = trim($sigla_tabela);
			$referencia = trim($referencia);
			$preco = str_replace(",", ".", $preco);
			$x = pg_execute($con, "insert", array($sigla_tabela, $referencia, $preco));

			if (!is_resource($x)) {
				continue;
			}
		}

	}

	$alter = pg_query($con, "ALTER TABLE tmp_ibbl_preco ADD peca integer");
	$begin = pg_query($con, "BEGIN");
	$sql = "UPDATE tmp_ibbl_preco SET peca = tbl_peca.peca FROM tbl_peca WHERE trim(tbl_peca.referencia) = tmp_ibbl_preco.referencia AND tbl_peca.fabrica = $fabrica";
	$query = pg_query($con, $sql);

	if (pg_last_error()) {
		$vet['log'] = 2;
		Log::log2($vet, "Erro na query: $sql\n" . pg_last_error());
		$rollback = pg_query($con, "ROLLBACK");
		enviaEmail();
		exit(1);
	}

	$sql = "SELECT sigla_tabela, referencia, preco FROM tmp_ibbl_preco WHERE peca IS NULL";
	$query = pg_query($con, $sql);

	if (pg_num_rows($query) > 0) {
		$vet['log'] = 1;
		$msg = utf8_decode("Peças não encontradas no sistema:\n\n");
		while ($fetch = pg_fetch_assoc($query)) {
			$s = $fetch['sigla_tabela'];
			$r = $fetch['referencia'];
			$p = $fetch['preco'];

			$msg.= "$s\t$r\t$p\n";
		}

		Log::log2($vet, $msg);

	}
	

	$alter = pg_query($con, "ALTER TABLE tmp_ibbl_preco ADD tabela integer");
	$sql = "UPDATE tmp_ibbl_preco SET tabela = tbl_tabela.tabela FROM tbl_tabela WHERE trim(tbl_tabela.sigla_tabela) = tmp_ibbl_preco.sigla_tabela AND tbl_tabela.fabrica = $fabrica";
	$query = pg_query($con, $sql);

	if (pg_last_error()) {
		$vet['log'] = 2;
		Log::log2($vet, "Erro na query: $sql\n" . pg_last_error());
		$rollback = pg_query($con, "ROLLBACK");
		enviaEmail();
		exit(1);
	}

	$alter = pg_query($con, "ALTER TABLE tmp_ibbl_preco ADD tem_preco bool default 'f'");
	$sql = "UPDATE tmp_ibbl_preco SET tem_preco = 't' FROM tbl_tabela_item WHERE tmp_ibbl_preco.peca = tbl_tabela_item.peca AND tmp_ibbl_preco.tabela = tbl_tabela_item.tabela";
	$query = pg_query($con, $sql);

	if (pg_last_error()) {
		$vet['log'] = 2;
		Log::log2($vet, "Erro na query: $sql\n" . pg_last_error());
		$rollback = pg_query($con, "ROLLBACK");
		enviaEmail();
		exit(1);
	}

	$sql = "SELECT distinct peca, preco INTO TEMP tmp_ibbl_preco_duplic
			FROM tmp_ibbl_preco WHERE peca in (
				SELECT peca FROM tmp_ibbl_preco GROUP BY peca HAVING count(peca) > 1
			) group by peca, preco having count(peca) = 1";
	$query = pg_query($con, $sql);

	if (pg_last_error()) {
		$vet['log'] = 2;
		Log::log2($vet, "Erro na query: $sql\n" . pg_last_error());
		$rollback = pg_query($con, "ROLLBACK");
		enviaEmail();
		exit(1);
	}

	$sql = "SELECT peca, preco FROM tmp_ibbl_preco_duplic ORDER BY peca";
	$query = pg_query($con, $sql);

	if (pg_num_rows($query) > 0) {
		$vet['log'] = 2;
		$msg = utf8_decode("Peças duplicadas com preços diferentes - não foram inseridas:\n\n");
		
		while ($fetch = pg_fetch_assoc($query)) {
			$pe = $fetch['peca'];
			$pr = $fetch['preco'];

			$msg.= "$pe\t$pr\n";
		}

		Log::log2($vet, $msg);

		$delete = pg_query($con, "DELETE FROM tmp_ibbl_preco WHERE peca in (SELECT DISTINCT peca FROM tmp_ibbl_preco_duplic)");

		if (pg_last_error()) {
			$vet['log'] = 2;
			Log::log2($vet, "Erro na query: $sql\n"  . pg_last_error());
			$rollback = pg_query($con, "ROLLBACK");
			enviaEmail();
			exit(1);
		}

		$envia_email_erro = 1;

	}

	$sql = "INSERT INTO tbl_tabela_item (tabela, peca, preco)
			SELECT DISTINCT tabela, peca, preco
			FROM tmp_ibbl_preco
			WHERE tem_preco IS NOT TRUE
			AND peca IS NOT NULL";
	$res = pg_query($con,$sql);

	if (pg_last_error()) {
		$vet['log'] = 2;
		Log::log2($vet, "Erro na query: $sql\n" . pg_last_error());
		$rollback = pg_query($con, "ROLLBACK");
		enviaEmail();
		exit(1);
	}

	$sql = "UPDATE tbl_tabela_item set preco = tmp_ibbl_preco.preco
			FROM tmp_ibbl_preco
			WHERE tmp_ibbl_preco.tabela = tbl_tabela_item.tabela
			AND tmp_ibbl_preco.peca = tbl_tabela_item.peca
			AND tmp_ibbl_preco.tem_preco IS TRUE
			AND tmp_ibbl_preco.peca IS NOT NULL";
	$res = pg_query($con,$sql);

	if (pg_last_error()) {
		$vet['log'] = 2;
		Log::log2($vet, "Erro na query: $sql\n" . pg_last_error());
		$rollback = pg_query($con, "ROLLBACK");
		enviaEmail();
		exit(1);
	}

	$commit = pg_query($con, "COMMIT");

	if ($envia_email_erro == 1) {
		enviaEmail(array('nabil.filho@ibbl.com.br'));
	}

	echo `mkdir -p /tmp/ibbl/importacao`;
	$data_arquivo = date('Ymd_Hm');
	$dest = '/tmp/ibbl/importacao/preco-' . $data_arquivo . '.txt';
	rename($arquivo, $dest);

} else {
	echo 'Nao importou precos da IBBL: nao existia arquivo.';
}

$phpCron->termino();
