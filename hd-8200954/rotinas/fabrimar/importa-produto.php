<?php

try {

	include dirname(__FILE__)."/../../dbconfig.php";
	include dirname(__FILE__)."/../../includes/dbconnect-inc.php";
	include dirname(__FILE__)."/../funcoes.php";

	$login_fabrica = 145;

	$erros = array();

	system("mkdir /tmp/fabrimar/ 2> /dev/null ; chmod 777 /tmp/fabrimar/");
	system("mkdir /tmp/fabrimar/produto/ 2> /dev/null ; chmod 777 /tmp/fabrimar/produto/");

	if ($_serverEnvironment == "production") {
		$emails = array(
			"helpdesk@telecontrol.com.br",
			"fernando.saibro@fabrimar.com.br",
			"kevin.robinson@fabrimar.com.br",
			"anderson.dutra@fabrimar.com.br"
		);

		$arquivo = "/home/fabrimar/fabrimar-telecontrol/telecontrol-produto.txt";
	} else {
		$emails = array(
			"guilherme.curcio@telecontrol.com.br"
		);

		$arquivo = "/home/fabrimar/fabrimar-telecontrol/telecontrol-produto.txt";
	}

	$arquivo_backup = "/tmp/fabrimar/produto/".date("Y-d-m-H-i")."-importa-produto.txt";
	$arquivo_erro   = "/tmp/fabrimar/produto/erro-".date("Y-d-m-H-i")."-importa-produto.txt";

	$phpCron = new PHPCron($login_fabrica, __FILE__);
	$phpCron->inicio();

	if (file_exists($arquivo)) {
		$conteudo_arquivo = explode("\n", file_get_contents($arquivo));

		if (count($conteudo_arquivo) > 0) {
			foreach ($conteudo_arquivo as $linha_numero => $linha_conteudo) {
				if (empty($linha_conteudo)) {
					continue;
				}

				$linha_erros = array();

				list(
					$referencia,
					$descricao,
					$linha,
					$familia,
					$grp_comercial,
					$origem,
					$voltagem,
					$garantia,
					$mao_de_obra,
					$numero_serie_obrigatorio,
					$troca_obrigatoria,
					$ativo
				) = explode(";", $linha_conteudo);

				$referencia               = trim($referencia);
				$descricao                = addslashes(trim($descricao));
				$linha                    = strtoupper(trim($linha));
				$familia                  = strtoupper(trim($familia));
				$origem                   = strtoupper(trim($origem));
				$voltagem                 = trim($voltagem);
				$garantia                 = trim($garantia);
				$mao_de_obra        = (!strlen(trim($mao_de_obra))) ? 0 : str_replace(",", ".", trim($mao_de_obra));
				$mao_de_obra_admin        = 0;
				$numero_serie_obrigatorio = (strtolower(trim($numero_serie_obrigatorio)) == "t") ? "true" : "false";
				$troca_obrigatoria        = (strtolower(trim($troca_obrigatoria)) == "t") ? "true" : "false";
				$ativo                    = (strtolower(trim($ativo)) == "ativo") ? "true" : "false";

				if (empty($referencia)) {
					$linha_erros[] = "Referência não informada";
				}

				if (empty($descricao)) {
					$linha_erros[] = "Descrição não informada";
				}

				if (empty($linha)) {
					$linha_erros[] = "Linha não informada";
				} else {
					$sqlLinha = "SELECT linha FROM tbl_linha WHERE fabrica = {$login_fabrica} AND UPPER(nome) LIKE '{$linha}%'";
					$resLinha = pg_query($con, $sqlLinha);

					if (!pg_num_rows($resLinha)) {
						$linha_erros[] = "Linha {$linha} não encontrada no sistema Telecontrol";
					} else {
						$linha = pg_fetch_result($resLinha, 0, "linha");
					}
				}

				if (empty($familia)) {
					$linha_erros[] = "Família não informada";
				} else {
					$sqlFamilia = "SELECT familia FROM tbl_familia WHERE fabrica = {$login_fabrica} AND UPPER(descricao) = '{$familia}'";
					$resFamilia = pg_query($con, $sqlFamilia);

					if (!pg_num_rows($resFamilia)) {
						$linha_erros[] = "Família {$familia} não encontrada no sistema Telecontrol";
					} else {
						$familia = pg_fetch_result($resFamilia, 0, "familia");
					}
				}

				if (empty($origem)) {
					$linha_erros[] = "Origem não informada";
				} else if (!in_array($origem, array("NAC", "IMP"))) {
					$linha_erros[] = "Origem deve ser NAC ou IMP, origem informada: {$origem}";
				}

				if (!strlen($garantia)) {
					$linha_erros[] = "Garantia não informada";
				}

				if (count($linha_erros) > 0) {
					$erros[$linha_numero] = $linha_erros;
				} else {
					$sqlProduto = "SELECT produto FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND UPPER(referencia) = UPPER('{$referencia}')";
					$resProduto = pg_query($con, $sqlProduto);

					if (pg_num_rows($resProduto) > 0) {
						$produto = pg_fetch_result($resProduto, 0, "produto");

						$sql = "UPDATE tbl_produto SET
									descricao                = '{$descricao}',
									linha                    = {$linha},
									familia                  = {$familia},
									origem                   = '{$origem}',
									voltagem                 = '{$voltagem}',
									garantia                 = {$garantia},
									mao_de_obra              = {$mao_de_obra},
									numero_serie_obrigatorio = {$numero_serie_obrigatorio},
									troca_obrigatoria        = {$troca_obrigatoria},
									ativo                    = {$ativo}
								WHERE fabrica_i = {$login_fabrica}
								AND produto = {$produto}";
					} else {
						$sql = "INSERT INTO tbl_produto
								(
									fabrica_i, 
									referencia, 
									descricao, 
									linha, 
									familia, 
									origem, 
									voltagem, 
									garantia, 
									mao_de_obra, 
									mao_de_obra_admin, 
									numero_serie_obrigatorio, 
									troca_obrigatoria, 
									ativo
								)
								VALUES
								(
									{$login_fabrica},
									'{$referencia}',
									'{$descricao}',
									{$linha},
									{$familia},
									'{$origem}',
									'{$voltagem}',
									{$garantia},
									{$mao_de_obra},
									{$mao_de_obra_admin},
									{$numero_serie_obrigatorio},
									{$troca_obrigatoria},
									{$ativo}
								)";
					}

					$res = pg_query($con, $sql);

					if (strlen(pg_last_error()) > 0) {
						$erros[$linha_numero] = array("Ocorreu um erro de execução ao gravar o produto {$referencia} {$sql}");
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

				mail(implode(",", $emails), "Telecontrol - Erro na Importação de Produtos da Fabrimar", file_get_contents($arquivo_erro), "MIME-Version: 1.0 \r\nContent-type: text/html; charset=iso-8859-1 \r\n");
			}

			system("mv {$arquivo} {$arquivo_backup}");
		}
	}

	$phpCron->termino();

} catch (Exception $e) {

	$f_erro = fopen($arquivo_erro, "w");
	fwrite($f_erro, "Ocorreu um erro ao executar o script de importar produtos, entrar em contato com o suporte da Telecontrol");
	fclose($f_erro);

	mail(implode(",", $emails), "Telecontrol - Erro na Importação de Produtos da Fabrimar", file_get_contents($arquivo_erro), "MIME-Version: 1.0 \r\nContent-type: text/html; charset=iso-8859-1 \r\n");

	$phpCron->termino();

}
