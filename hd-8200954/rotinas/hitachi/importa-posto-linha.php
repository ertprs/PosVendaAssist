<?php

try {

	include dirname(__FILE__)."/../../dbconfig.php";
	include dirname(__FILE__)."/../../includes/dbconnect-inc.php";
	include dirname(__FILE__)."/../funcoes.php";

	$login_fabrica = 147;

	system("mkdir /tmp/hitachi/ 2> /dev/null ; chmod 777 /tmp/hitachi/");
	system("mkdir /tmp/hitachi/posto/ 2> /dev/null ; chmod 777 /tmp/hitachi/posto/");

	if ($_serverEnvironment == "production") {
		$emails = array(
			"helpdesk@telecontrol.com.br",
			"amaral@hitachi-koki.com.br"
		);

		$arquivo_dir = "/home/hitachi/pos-vendas/hitachi-telecontrol/postolinha/";
	} else {
		$emails = array(
			"guilherme.curcio@telecontrol.com.br"
		);

		$arquivo_dir = "/home/hitachi/pos-vendas/hitachi-telecontrol/postolinha/";
	 }

	$arquivo_backup = "/tmp/hitachi/posto/".date("Y-d-m-H-i")."-importa-posto-linha.txt";
	$arquivo_erro   = "/tmp/hitachi/posto/erro-".date("Y-d-m-H-i")."-importa-posto-linha.txt";

	$phpCron = new PHPCron($login_fabrica, __FILE__);
	$phpCron->inicio();

	foreach (glob("{$arquivo_dir}Postolinha2*") as $arquivo) {
		$erros = array();
		$arquivo_backup = "/tmp/hitachi/posto/".date("YdmHiS")."-importa-posto-linha.txt";
	        $arquivo_erro   = "/tmp/hitachi/posto/erro-".date("YdmHiS")."-importa-posto-linha.txt";

		if(filesize($arquivo) == 0){
			unlink($arquivo);
			continue;
		}

		$conteudo = file_get_contents($arquivo);
		$conteudo = explode("\n", $conteudo);

		foreach ($conteudo as $linha_numero => $linha_conteudo) {
			if (empty($linha_conteudo)) {
				continue;
			}

			$linha_erros = array();

			if (!empty($linha_conteudo)) {
				list (
						$cnpj,
						$linha,
						$tabela,
						$garantia
					) = explode ("\t",$linha_conteudo);

				$cnpj     = preg_replace("/\D/", "", trim($cnpj));
				$linha    = trim($linha);
				$tabela   = trim($tabela);
				$garantia = strtolower(trim($garantia));

				if (empty($cnpj)) {
					$linha_erros[] = "Campo CNPJ do Posto não informado";
				}

				if (empty($linha)) {
					$linha_erros[] = "Campo Linha não informado";
				}

				if (empty($tabela)) {
					$linha_erros[] = "Campo Tabela não informado";
				}

				if (empty($garantia)) {
					$linha_erros[] = "Campo Garantia não informado";
				}

				$valida_cpnj = pg_query($con, "SELECT fn_valida_cnpj_cpf('{$cnpj}')");

				if (pg_last_error()) {
					$linha_erros[] = "CNPJ {$cnpj} inválido";
				}

				if ($garantia == "garantia") {
					$columnTabela  = "tabela";
					$whereGarantia = "AND tbl_tabela.tabela_garantia IS TRUE";
				} else {
					$columnTabela  = "tabela_posto";
					$whereGarantia = "AND tbl_tabela.tabela_garantia IS NOT TRUE";
				}

				$sqlTabela = "SELECT tabela, tabela_garantia
							  FROM tbl_tabela
							  WHERE UPPER(tbl_tabela.descricao) = UPPER('{$tabela}')
							  AND tbl_tabela.fabrica = {$login_fabrica}
							  {$whereGarantia}";
				$resTabela = pg_query($con, $sqlTabela);

				if (!pg_num_rows($resTabela)) {
					$linha_erros[] = "Tabela {$tabela} não encontrada";
				} else {
					$tabela = pg_fetch_result($resTabela, 0, "tabela");
				}

				$sqlLinha = "SELECT linha
							 FROM tbl_linha
							 WHERE fabrica = {$login_fabrica}
							 UPPER(nome) = UPPER('{$linha}')";
				$resLinha = pg_query($con, $sqlLinha);

				if (!pg_num_rows($resLinha)) {
					$linha_erros[] = "Linha {$linha} não encontrada";
				} else {
					$linha = pg_fetch_result($resLinha, 0, "linha");
				}

				$sqlPosto = "SELECT tbl_posto_fabrica.posto
							 FROM tbl_posto_fabrica
							 INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
							 WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
							 AND tbl_posto.cnpj = '{$cnpj}'";
				$resPosto = pg_query($con, $sqlPosto);

				if (!pg_num_rows($resPosto)) {
					$linha_erros[] = "Posto {$cnpj} não encontrado";
				} else {
					$posto = pg_fetch_result($resPosto, 0, "posto");
				}

				if (!empty($linha_erro)) {
					$erros[$linha_numero] = $linha_erros;
				} else {
					$sqlPostoLinha = "SELECT posto, linha
									  FROM tbl_posto_linha
									  WHERE posto = {$posto}
									  AND linha = {$linha}";
					$resPostoLinha = pg_query($con, $sqlPostoLinha);

					if (pg_num_rows($resPostoLinha) > 0) {
						$gravaPostoLinha = "UPDATE tbl_posto_linha SET
												{$columnTabela} = {$tabela}
											WHERE posto = {$posto}
											AND linha =  {$linha}";
					} else {
						$gravaPostoLinha = "INSERT INTO tbl_posto_linha
											(posto, linha, {$columnTabela})
											VALUES
											({$posto}, {$linha}, {$tabela})";
					}

					$resGravaPostoLinha = pg_query($con, $gravaPostoLinha);

					if (strlen(pg_last_error()) > 0) {
						$erros[$linha_numero] = array("Ocorreu um erro de execução ao relacionar o posto com a linha");
					}
				}
			}
		}

		if (count($erros) > 0) {
			$f_erro = fopen($arquivo_erro, "w");

			foreach ($erros as $linha_numero => $linha_erros) {
				fwrite($f_erro, "<b style='color: #FF0000;' >Linha {$linha_numero}</b><br />");

				fwrite($f_erro, "<ul>");

				foreach ($linha_erros as $erro) {
					fwrite($f_erro, "<li>{$erro}</li>");
				}

				fwrite($f_erro, "</ul>");

				fwrite($f_erro, "<br />");
			}

			fclose($f_erro);

			mail(implode(",", $emails), "Telecontrol - Erro na Importação de Posto x Linha da Hitachi", file_get_contents($arquivo_erro), "MIME-Version: 1.0 \r\nContent-type: text/html; charset=iso-8859-1 \r\n");
		}

		system("mv {$arquivo} {$arquivo_backup}");
	}

	$phpCron->termino();
} catch (Exception $e) {
	$f_erro = fopen($arquivo_erro, "w");
	fwrite($f_erro, "Ocorreu um erro ao executar o script de importar posto x linha, entrar em contato com o suporte da Telecontrol");
	fclose($f_erro);

	mail(implode(",", $emails), "Telecontrol - Erro na Importação de Posto x Linha da Hitachi", file_get_contents($arquivo_erro), "MIME-Version: 1.0 \r\nContent-type: text/html; charset=iso-8859-1 \r\n");

	$phpCron->termino();
}
