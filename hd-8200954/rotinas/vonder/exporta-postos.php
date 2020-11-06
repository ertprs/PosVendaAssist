<?php

error_reporting(E_ALL ^ E_NOTICE);

try {
	
    include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';

	if (!empty($argv[1])) {
		$argumento = strtolower($argv[1]);

		if ($argumento == 'dwt') {
			$vonder_dwt = 1;
		} else {
			$vonder_dwt = 0;
		}
	} else {
		$vonder_dwt = 0;
	}

	switch ($vonder_dwt) {
		case 0:
			// Vonder
			$login_fabrica = 104;
			$fabrica_nome = 'vonder';
			$ovd_dwt = 'ovd';
			break;
		case 1:
			// DWT
			$login_fabrica = 105;
			$fabrica_nome = 'dwt';
			$ovd_dwt = 'dwt';
			break;
		default:
			throw new Exception('Falha na passagem de parâmetros.');
	}
	
	$phpCron = new PHPCron($login_fabrica, __FILE__); 
	$phpCron->inicio();

	$sql = "SELECT	tbl_posto.posto,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome,
					tbl_posto.nome_fantasia,
					tbl_posto.ie,
					tbl_posto.cnpj,
					tbl_posto.capital_interior,
					tbl_posto_fabrica.contato_endereco,
					tbl_posto_fabrica.contato_numero,
					tbl_posto_fabrica.contato_complemento,
					tbl_posto_fabrica.contato_bairro,
					tbl_posto_fabrica.contato_cep,
					tbl_posto_fabrica.contato_cidade,
					tbl_posto_fabrica.contato_estado,
					tbl_posto_fabrica.contato_email,
					tbl_posto_fabrica.contato_fone_comercial,
					tbl_posto_fabrica.contato_fax,
					tbl_posto_fabrica.contato_nome,
					CASE WHEN tbl_posto_fabrica.divulgar_consumidor THEN 'S' else 'N' end as divulgar_consumidor,
					CASE WHEN tbl_posto_fabrica.credenciamento = 'CREDENCIADO' THEN 'C'	ELSE 'D' END AS credenciamento
			FROM tbl_posto
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica AND tbl_posto.posto <> 6359
			ORDER BY tbl_posto.nome_fantasia";
	$res = pg_query($con,$sql);
	$numrows = pg_num_rows($res);

	if ($numrows > 0) {

		$arquivo = dirname(__FILE__) . "/$ovd_dwt-ret-assistencias.csv";
		$fp = fopen ("$arquivo","w");

		for ($i = 0; $i < $numrows; $i++) {
			$posto = pg_fetch_result($res, $i, 'posto');
			$codigo_posto = pg_fetch_result($res, $i, 'codigo_posto');
			$nome = trim(pg_fetch_result($res, $i, 'nome'));
			$nome_fantasia = trim(pg_fetch_result($res, $i, 'nome_fantasia'));
			$ie = trim(pg_fetch_result($res, $i, 'ie'));
			$cnpj = trim(pg_fetch_result($res, $i, 'cnpj'));
			$capital_interior = trim(pg_fetch_result($res, $i, 'capital_interior'));
			$contato_endereco = trim(pg_fetch_result($res, $i, 'contato_endereco'));
			$contato_numero = trim(pg_fetch_result($res, $i, 'contato_numero'));
			$contato_complemento = trim(pg_fetch_result($res, $i, 'contato_complemento'));
			$contato_bairro = trim(pg_fetch_result($res, $i, 'contato_bairro'));
			$contato_cep = trim(pg_fetch_result($res, $i, 'contato_cep'));
			$contato_cidade = trim(pg_fetch_result($res, $i, 'contato_cidade'));
			$contato_estado = trim(pg_fetch_result($res, $i, 'contato_estado'));
			$contato_email = trim(pg_fetch_result($res, $i, 'contato_email'));
			$contato_fone_comercial = trim(pg_fetch_result($res, $i, 'contato_fone_comercial'));
			$contato_fax = trim(pg_fetch_result($res, $i, 'contato_fax'));
			$contato_nome = trim(pg_fetch_result($res, $i, 'contato_nome'));
			$divulgar_consumidor = trim(pg_fetch_result($res, $i, 'divulgar_consumidor'));
			$credenciamento = pg_fetch_result($res, $i, 'credenciamento');

			/**
			 *
			 *  Layout:
			 *
			 *  Codigo PA | Nome | Fantasia | CNPJ | IE | Endereço | Núm. | Compl. |
			 *  Bairro | CEP | Cidade | Estado | Email | Fone | Fax | Contato |
			 *  Capital/Interior | Cred./Desc. | Linha | Desc. linha
			 *
			 */

			fputs($fp,"$codigo_posto;");
			fputs($fp,"$nome;");
			fputs($fp, "$nome_fantasia;");
			fputs($fp,"$cnpj;");
			fputs($fp, "$ie;");
			fputs($fp,"$contato_endereco;");
			fputs($fp,"$contato_numero;");
			fputs($fp,"$contato_complemento;");
			fputs($fp,"$contato_bairro;");
			fputs($fp,"$contato_cep;");
			fputs($fp,"$contato_cidade;");
			fputs($fp,"$contato_estado;");
			fputs($fp,"$contato_email;");
			fputs($fp,"$contato_fone_comercial;");
			fputs($fp,"$contato_fax;");
			fputs($fp,"$contato_nome;");
			fputs($fp,"$capital_interior;");
			fputs($fp,"$credenciamento;");



			$sql_linha = "SELECT linha FROM tbl_linha WHERE fabrica = $login_fabrica AND ativo='t'";
		    $res_linha = pg_query($con,$sql_linha);
			$numrows_linha = pg_num_rows($res_linha);

			if($numrows_linha > 0) {

				for($a = 0; $a < $numrows_linha; $a++) {
					$linha_cod = pg_fetch_result($res_linha,$a,'linha');

					$sql2 = "SELECT tbl_linha.codigo_linha,
									tbl_linha.nome
								FROM tbl_posto_linha
								JOIN tbl_linha
								ON tbl_linha.linha = tbl_posto_linha.linha
								AND tbl_linha.fabrica = $login_fabrica
								WHERE tbl_posto_linha.posto = $posto
								AND tbl_posto_linha.linha = $linha_cod";
					$res2 = pg_query($con,$sql2);
					$numrows2 = pg_num_rows($res2);

					if ($numrows2 > 0) {

						$codigo_linha = pg_fetch_result($res2,0,'codigo_linha');
						$nome = pg_fetch_result($res2,0,'nome');

						fputs($fp,"$codigo_linha;");
						fputs($fp,"$nome;");

					} else {
						fputs($fp,";");
						fputs($fp,";");
					}

				}

			}

			fputs($fp,"$divulgar_consumidor;");
			fputs($fp,"\n");

		}
		fclose ($fp);

		if (file_exists($arquivo)) {
			system("mv $arquivo /home/vonder/telecontrol-$fabrica_nome/");
		}
	}
    
    $phpCron->termino();
    
} catch (Exception $e) {

	echo $e->getMessage();

}

