<?php

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','producao');  // producao Alterar para produção ou algo assim
try {
	include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require_once dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

    $data['login_fabrica'] 		= 15;
    $data['fabrica'] 	= 'latinatec';
    $data['arquivo_log'] 	= 'importa-faturamento';
	$data['tipo'] 	= 'importa-faturamento';
    $data['log'] 			= 2;
    $data['arquivos'] 		= "/tmp";
    $data['data_sistema'] 	= Date('Y-m-d');
    $logs 					= array();
    $logs_erro				= array();
    $logs_cliente			= array();
    $erro 					= false;

    $login_fabrica = 15;

    $phpCron = new PHPCron($login_fabrica, __FILE__);
	$phpCron->inicio();

    #$data    = date("d-m-Y-H-i");
    #$log     = array();

    ##$arquivo       = "/www/cgi-bin/latinatec/entrada/faturamento.txt";
	#$arquivo        = dirname(__FILE__) ."/entrada/faturamento.txt";
	#$arquivo_temp   = dirname(__FILE__) ."/tmp/latinatec/faturamento/faturamento_temp_{$data}.txt";
	##$arquivo_backup = "/tmp/latinatec/faturamento_backup_{$data}.txt";
	#$arquivo_backup = dirname(__FILE__) ."entrada/backup/faturamento_backup_{$data}.txt";
    #
	##$arquivo_item        = "/www/cgi-bin/latinatec/entrada/faturamento_item.txt";
	#$arquivo_item         = dirname(__FILE__) ."/entrada/faturamento_item.txt";
	#$arquivo_item_temp    = dirname(__FILE__) ."/tmp/latinatec/faturamento/faturamento_item_temp_{$data}.txt";
	##$arquivo_item_backup = "/tmp/latinatec/faturamento_item_backup_{$data}.txt";
	#$arquivo_item_backup  = dirname(__FILE__) ."entrada/backup/faturamento_item_backup_{$data}.txt";

    if (ENV == 'producao' ) {
	    $data['dest'] 		= 'helpdesk@telecontrol.com.br';
	    $data['dest_cliente']  	= 'iuri.brito@latina.com.br, marcelo.cardoso@latina.com.br';
	    $data['origem']		= "/www/cgi-bin/latinatec/entrada/";
	    $data['file']		= 'faturamento.txt';
	    $data['file2']		= 'faturamento_item.txt';
    } else {
	    $data['dest'] 		= 'ronald.santos@telecontrol.com.br';
	    $data['dest_cliente'] 	= 'ronald.santos@telecontrol.com.br,william.brandino@telecontrol.com.br';
	    $data['origem']		= dirname(__FILE__) . "/entrada/";
	    $data['file']		= 'faturamento.txt';
	    $data['file2']		= 'faturamento_item.txt';
    }

    extract($data);

	define('APP', 'Importa Faturamento - '.$fabrica);

    $arquivo_err = "{$arquivos}/{$fabrica}/{$arquivo_log}-{$data_sistema}.err";
    $arquivo_log = "{$arquivos}/{$fabrica}/{$arquivo_log}-{$data_sistema}.log";
    system ("mkdir {$arquivos}/{$fabrica}/ 2> /dev/null ; chmod 777 {$arquivos}/{$fabrica}/" );


	$logs[] = "INÍCIO: ".date("H:i");
	if (file_exists($origem.$file)) {
		#system("cp {$arquivo} {$arquivo_temp}");

        $sql = "DROP TABLE IF EXISTS tmp_latinatec;";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);

		$sql = "CREATE TEMP TABLE tmp_latinatec (
					posto_cnpj text,
					posto int,
					nota_fiscal text,
					nota_fiscal_serie text,
					data_emissao date,
					cfop text,
					natureza text,
					valor_total double precision,
					valor_ipi double precision,
					valor_icms double precision,
					transportadora text,
					empresa text,
					nota_fiscal_existente boolean
				)";
		$res = pg_query($con, $sql);
        $msg_erro .= pg_errormessage($con);

		$arquivo_conteudo = explode("\n", file_get_contents($origem.$file));

		foreach ($arquivo_conteudo as $linha_numero => $linha_conteudo) {
			$linha_erro = false;

			list(
				$posto_cnpj,
				$nota_fiscal,
				$nota_fiscal_serie,
				$data_emissao,
				$cfop,
				$natureza,
				$valor_total,
				$valor_ipi,
				$valor_icms,
				$transportadora,
				$empresa
			) = explode(";", $linha_conteudo);

			$posto_cnpj        = trim($posto_cnpj);
			$nota_fiscal       = substr(trim($nota_fiscal), 0, 6);
			$nota_fiscal_serie = trim($nota_fiscal_serie);
			$data_emissao      = trim($data_emissao);
			$cfop              = trim($cfop);
			$natureza          = trim($natureza);
			$valor_total       = trim($valor_total);
			$valor_ipi         = trim($valor_ipi);
			$valor_icms        = trim($valor_icms);
			$transportadora    = trim($transportadora);
			$empresa           = trim($empresa);

			if (!strlen($posto_cnpj)) {
				$logs[] = "NF LINHA ".($linha_numero + 1).": - CNPJ DO POSTO NÃO INFORMADO";
				$linha_erro = true;
			}

			if (!strlen($nota_fiscal)) {
				$logs[] = "NF LINHA ".($linha_numero + 1).": - NOTA FISCAL NÃO INFORMADA";
				$linha_erro = true;
			}

			if (!strlen($nota_fiscal_serie)) {
				$logs[] = "NF LINHA ".($linha_numero + 1).": - SÉRIE DA NOTA FISCAL NÃO INFORMADA";
				$linha_erro = true;
			}

			if (!strlen($data_emissao)) {
				$logs[] = "NF LINHA ".($linha_numero + 1).": - DATA DE EMISSÃO DA NOTA FISCAL NÃO INFORMADA";
				$linha_erro = true;
			}

			if (!strlen($data_emissao)) {
				$logs[] = "NF LINHA ".($linha_numero + 1).": - DATA DE EMISSÃO DA NOTA FISCAL NÃO INFORMADA";
				$linha_erro = true;
			}

			if (!strlen($valor_total)) {
				$valor_total = 0;
			}

			if (!strlen($valor_ipi)) {
				$valor_ipi = 0;
			}

			if (!strlen($valor_icms)) {
				$valor_icms = 0;
			}

			if (!(preg_match("/^[0-9]*\.[0-9]{2}$/", $valor_total) || preg_match("/^[0-9]*$/", $valor_total))) {
				$logs[] = "NF LINHA ".($linha_numero + 1).": - VALOR TOTAL EM FORMATO INCORRETO";
				$linha_erro = true;
			}

			if (!(preg_match("/^[0-9]*\.[0-9]{2}$/", $valor_ipi) || preg_match("/^[0-9]*$/", $valor_ipi))) {
				$logs[] = "NF LINHA ".($linha_numero + 1).": - VALOR DO IPI EM FORMATO INCORRETO";
				$linha_erro = true;
			}

			if (!(preg_match("/^[0-9]*\.[0-9]{2}$/", $valor_icms) || preg_match("/^[0-9]*$/", $valor_icms))) {
				$logs[] = "NF LINHA ".($linha_numero + 1).": - VALOR DO ICMS EM FORMATO INCORRETO";
				$linha_erro = true;
			}

			if ($linha_erro === false) {
                pg_query($con,"BEGIN");

				$sql = "INSERT INTO tmp_latinatec(
							posto_cnpj,
							nota_fiscal,
							nota_fiscal_serie,
							data_emissao,
							cfop,
							natureza,
							valor_total,
							valor_ipi,
							valor_icms,
							transportadora,
							empresa
						)VALUES(
							'{$posto_cnpj}',
							'{$nota_fiscal}',
							'{$nota_fiscal_serie}',
							'{$data_emissao}',
							'{$cfop}',
							'{$natureza}',
							{$valor_total},
							{$valor_ipi},
							{$valor_icms},
							'{$transportadora}',
							'{$empresa}'
						)";

				$res = pg_query($con, $sql);
                $msg_erro .= pg_errormessage($con);

				if(!empty($msg_erro)){
					$res = pg_query($con,"ROLLBACK");
					$erro .= $msg_erro;
				} else {
					$res = pg_query($con,"COMMIT");
				}
			}
		}

		$sql = "UPDATE  tmp_latinatec
				SET     posto = tbl_posto.posto
				FROM    tbl_posto
				JOIN    tbl_posto_fabrica USING (posto)
				WHERE   tbl_posto_fabrica.fabrica   = $login_fabrica
				AND     tmp_latinatec.posto_cnpj    = tbl_posto.cnpj";
		$res = pg_query($con, $sql);
        $msg_erro .= pg_errormessage($con);

		$sql  = "SELECT DISTINCT posto_cnpj FROM tmp_latinatec WHERE posto IS NULL";
		$res  = pg_query($con, $sql);
		$rows = pg_num_rows($res);

		if ($rows > 0) {
			$logs[] = "<br /><br />-----------------------------";
			$logs[] = "POSTOS NÃO ENCOTRADOS NO SISTEMA";

			for ($i = 0; $i < $rows; $i++) {
				$logs[] = pg_fetch_result($res, $i, "posto_cnpj");
			}
			$logs[] = "-----------------------------<br /><br />";

			$sql = "DELETE FROM tmp_latinatec WHERE posto IS NULL";
			$res = pg_query($con, $sql);
            $msg_erro .= pg_errormessage($con);
		}

		if (file_exists($origem.$file2)) {
			#system("cp {$arquivo_item} {$arquivo_item_temp}");

			$sql = "CREATE TEMP TABLE tmp_latinatec_item (
					posto_cnpj text,
					posto int,
					nota_fiscal text,
					nota_fiscal_serie text,
					peca_referencia text,
					peca int,
					txt_pedido text,
					pedido int,
					pedido_item int,
					peca_qtde int,
					preco_unitario double precision,
					aliq_ipi double precision,
					aliq_icms double precision,
					base_ipi double precision,
					base_icms double precision,
					valor_icms double precision,
					valor_ipi double precision,
					empresa text,
					nota_fiscal_existente boolean,
					faturamento int
				)";
			$res = pg_query($con, $sql);
            $msg_erro .= pg_errormessage($con);
			$arquivo_item_conteudo = explode("\n", file_get_contents($origem.$file2));

			foreach ($arquivo_item_conteudo as $linha_item_numero => $linha_item_conteudo) {
				$linha_erro = false;

				list(
					$posto_cnpj,
					$nota_fiscal,
					$nota_fiscal_serie,
					$peca_referencia,
					$pedido,
					$pedido_item,
					$peca_qtde,
					$preco_unitario,
					$aliq_ipi,
					$aliq_icms,
					$base_ipi,
					$base_icms,
					$valor_icms,
					$valor_ipi,
					$empresa
				) = explode(";", $linha_item_conteudo);

				$posto_cnpj        = trim($posto_cnpj);
				$nota_fiscal       = substr(trim($nota_fiscal), 0, 6);
				$nota_fiscal_serie = trim($nota_fiscal_serie);
				$peca_referencia   = trim($peca_referencia);
				$pedido            = str_replace("-", "", trim($pedido));
				$pedido_item       = trim($pedido_item);
				$peca_qtde         = trim($peca_qtde);
				$preco_unitario    = trim($preco_unitario);
				$aliq_ipi          = trim($aliq_ipi);
				$aliq_icms         = trim($aliq_icms);
				$base_ipi          = trim($base_ipi);
				$base_icms         = trim($base_icms);
				$valor_icms        = trim($valor_icms);
				$valor_ipi         = trim($valor_ipi);
				$empresa           = trim($empresa);

				if (!strlen($posto_cnpj)) {
					$logs[] = "NF ITEM - LINHA ".($linha_numero + 1).": - CNPJ DO POSTO NÃO INFORMADO	";
					$linha_erro = true;
				}

				if (!strlen($nota_fiscal)) {
					$logs[] = "NF ITEM - LINHA ".($linha_numero + 1).": - NOTA FISCAL NÃO INFORMADA";
					$linha_erro = true;
				}

				if (!strlen($peca_referencia)) {
					$logs[] = "NF ITEM - LINHA ".($linha_numero + 1).": - REFERÊNCIA DA PEÇA NÃO INFORMADA";
					$linha_erro = true;
				}

				if (!strlen($pedido)) {
					$logs[] = "NF ITEM - LINHA ".($linha_numero + 1).": - PEDIDO NÃO INFORMADA";
					$linha_erro = true;
				}

				if (!strlen($peca_qtde)) {
					$logs[] = "NF ITEM - LINHA ".($linha_numero + 1).": - QUANTIDADE NÃO INFORMADA";
					$linha_erro = true;
				}

				if (strlen($peca_qtde) > 0 && $peca_qtde == 0) {
					$logs[] = "NF ITEM - LINHA ".($linha_numero + 1).": - QUANTIDADE NÃO PODE SER 0";
					$linha_erro = true;
				}

				if (!(preg_match("/^[0-9]*\.[0-9]{2}$/", $preco_unitario) || preg_match("/^[0-9]*$/", $preco_unitario))) {
					$logs[] = "NF ITEM - LINHA ".($linha_numero + 1).": - PREÇO UNITÁRIO EM FORMATO INCORRETO";
					$linha_erro = true;
				} else {
					if ($preco_unitario == 0) {
						$logs[] = "NF ITEM - LINHA ".($linha_numero + 1).": - PREÇO UNITÁRIO NÃO PODE SER 0";
					}
				}

				if (!strlen($aliq_ipi)) {
					$aliq_ipi = 0;
				}

				if (!strlen($aliq_icms)) {
					$aliq_icms = 0;
				}

				if (!strlen($base_ipi)) {
					$base_ipi = 0;
				}

				if (!strlen($base_icms)) {
					$base_icms = 0;
				}

				if (!strlen($valor_icms)) {
					$valor_icms = 0;
				}

				if (!strlen($valor_ipi)) {
					$valor_ipi = 0;
				}

				if (!(preg_match("/^[0-9]*\.[0-9]{3}$/", $aliq_ipi) || preg_match("/^[0-9]*$/", $aliq_ipi))) {
					$logs[] = "NF ITEM - LINHA ".($linha_numero + 1).": - ALIQ IPI EM FORMATO INCORRETO";
					$linha_erro = true;
				}

				if (!(preg_match("/^[0-9]*\.[0-9]{2}$/", $aliq_icms) || preg_match("/^[0-9]*$/", $aliq_icms))) {
					$logs[] = "NF ITEM - LINHA ".($linha_numero + 1).": - ALIQ ICMS EM FORMATO INCORRETO";
					$linha_erro = true;
				}

				if (!(preg_match("/^[0-9]*\.[0-9]{2}$/", $base_ipi) || preg_match("/^[0-9]*$/", $base_ipi))) {
					$logs[] = "NF ITEM - LINHA ".($linha_numero + 1).": - BASE IPI EM FORMATO INCORRETO";
					$linha_erro = true;
				}

				if (!(preg_match("/^[0-9]*\.[0-9]{2}$/", $base_icms) || preg_match("/^[0-9]*$/", $base_icms))) {
					$logs[] = "NF ITEM - LINHA ".($linha_numero + 1).": - BASE ICMS EM FORMATO INCORRETO";
					$linha_erro = true;
				}

				if (!(preg_match("/^[0-9]*\.[0-9]{2}$/", $valor_icms) || preg_match("/^[0-9]*$/", $valor_icms))) {
					$logs[] = "NF ITEM - LINHA ".($linha_numero + 1).": - VALOR ICMS EM FORMATO INCORRETO";
					$linha_erro = true;
				}

				if (!(preg_match("/^[0-9]*\.[0-9]{2}$/", $valor_ipi) || preg_match("/^[0-9]*$/", $valor_ipi))) {
					$logs[] = "NF ITEM - LINHA ".($linha_numero + 1).": - VALOR IPI EM FORMATO INCORRETO";
					$linha_erro = true;
				}

				if (!strlen($pedido_item)) {
					$pedido_item = "null";
				}
				if ($linha_erro === false) {
                    pg_query($con,"BEGIN");
					$sql = "INSERT INTO tmp_latinatec_item(
								posto_cnpj,
								nota_fiscal,
								nota_fiscal_serie,
								peca_referencia,
								txt_pedido,
								pedido_item,
								peca_qtde,
								preco_unitario,
								aliq_ipi,
								aliq_icms,
								base_ipi,
								base_icms,
								valor_icms,
								valor_ipi,
								empresa
							)VALUES(
								'{$posto_cnpj}',
								'{$nota_fiscal}',
								'{$nota_fiscal_serie}',
								'{$peca_referencia}',
								'{$pedido}',
								{$pedido_item},
								{$peca_qtde},
								{$preco_unitario},
								{$aliq_ipi},
								{$aliq_icms},
								{$base_ipi},
								{$base_icms},
								{$valor_icms},
								{$valor_ipi},
								'{$empresa}'
							)";

					$res = pg_query($con, $sql);
                    $msg_erro .= pg_errormessage($con);

                    if(!empty($msg_erro)){
                        $res = pg_query($con,"ROLLBACK");
                        $erro .= $msg_erro;
                    } else {
                        $res = pg_query($con,"COMMIT");
                    }
				}
			}


			$sql = "UPDATE  tmp_latinatec_item
					SET     posto = tbl_posto.posto
					FROM    tbl_posto
					JOIN    tbl_posto_fabrica USING (posto)
					WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
					AND     tmp_latinatec_item.posto_cnpj = tbl_posto.cnpj";
			$res = pg_query($con, $sql);

			$sql  = "SELECT DISTINCT posto_cnpj FROM tmp_latinatec_item WHERE posto IS NULL";
			$res  = pg_query($con, $sql);
			$rows = pg_num_rows($res);

			if ($rows > 0) {
				$logs[] = "<br /><br />-----------------------------";
				$logs[] = "POSTOS NÃO ENCOTRADOS NO SISTEMA";

				for ($i = 0; $i < $rows; $i++) {
					$logs[] = pg_fetch_result($res, $i, "posto_cnpj");
				}
				$logs[] = "-----------------------------<br /><br />";

				$sql = "DELETE FROM tmp_latinatec_item WHERE posto IS NULL";
				$res = pg_query($con, $sql);
                $msg_erro .= pg_errormessage($con);
			}

			$sql = "DELETE FROM tmp_latinatec_item WHERE txt_pedido ~* '\\D';

					UPDATE  tmp_latinatec_item
					SET     pedido = tbl_pedido.pedido
					FROM    tbl_pedido
					WHERE   tbl_pedido.fabrica = $login_fabrica
					AND     tmp_latinatec_item.txt_pedido::integer = tbl_pedido.pedido";
			$res = pg_query($con, $sql);
            $msg_erro .= pg_errormessage($con);

			$sql  = "SELECT DISTINCT pedido FROM tmp_latinatec_item WHERE pedido IS NULL";
			$res  = pg_query($con, $sql);
			$rows = pg_num_rows($res);

			if ($rows > 0) {
				$logs[] = "<br /><br />-----------------------------";
				$logs[] = "PEDIDOS NÃO ENCOTRADOS NO SISTEMA";

				for ($i = 0; $i < $rows; $i++) {
					$logs[] = pg_fetch_result($res, $i, "pedido");
				}
				$logs[] = "-----------------------------<br /><br />";

				$sql = "DELETE FROM tmp_latinatec_item WHERE pedido IS NULL";
				$res = pg_query($con, $sql);
                $msg_erro .= pg_errormessage($con);
			}

			$sql = "UPDATE  tmp_latinatec_item
					SET     peca = tbl_peca.peca
					FROM    tbl_peca
					WHERE   tbl_peca.fabrica = $login_fabrica
					AND     tmp_latinatec_item.peca_referencia = tbl_peca.referencia_pesquisa";
			$res = pg_query($con, $sql);
            $msg_erro .= pg_errormessage($con);

			$sql  = "SELECT DISTINCT peca_referencia FROM tmp_latinatec_item WHERE peca IS NULL";
			$res  = pg_query($con, $sql);
			$rows = pg_num_rows($res);

			if ($rows > 0) {
				$logs[] = "<br /><br />-----------------------------";
				$logs[] = "PEÇAS NÃO ENCOTRADAS NO SISTEMA";

				for ($i = 0; $i < $rows; $i++) {
					$logs[] = pg_fetch_result($res, $i, "peca_referencia");
				}
				$logs[] = "-----------------------------<br /><br />";

				$sql = "DELETE FROM tmp_latinatec_item WHERE peca IS NULL";
				$res = pg_query($con, $sql);
			}

			$sql = "UPDATE  tmp_latinatec
					SET     nota_fiscal_existente = true
					FROM    tbl_faturamento
					WHERE   tmp_latinatec.nota_fiscal       = tbl_faturamento.nota_fiscal
					AND     tmp_latinatec.nota_fiscal_serie = tbl_faturamento.serie
					AND     tmp_latinatec.empresa           = tbl_faturamento.empresa
					AND     tbl_faturamento.fabrica         = $login_fabrica
					AND     tbl_faturamento.distribuidor IS NULL";
			$res = pg_query($con, $sql);
            $msg_erro .= pg_errormessage($con);

			$sql  = "SELECT DISTINCT nota_fiscal, nota_fiscal_serie, empresa FROM tmp_latinatec WHERE nota_fiscal_existente IS TRUE";
			$res  = pg_query($con, $sql);
			$rows = pg_num_rows($res);

			if ($rows > 0) {
				$logs[] = "<br /><br />-----------------------------";
				$logs[] = "NOTAS FISCAIS JÁ FATURADAS";

				for ($i = 0; $i < $rows; $i++) {
					$nota_fiscal       = pg_fetch_result($res, $i, "nota_fiscal");
					$nota_fiscal_serie = pg_fetch_result($res, $i, "nota_fiscal_serie");
					$empresa           = pg_fetch_result($res, $i, "empresa");
					$logs[] = "NOTA FISCAL: {$nota_fiscal} / SÉRIE: {$nota_fiscal_serie} / EMPRESA: {$empresa}";
				}
				$logs[] = "-----------------------------<br /><br />";

				$sql = "DELETE FROM tmp_latinatec WHERE nota_fiscal_existente = true";
				$res = pg_query($con, $sql);
                $msg_erro .= pg_errormessage($con);
			}

			$sql = "UPDATE  tmp_latinatec_item
					SET     nota_fiscal_existente = true
					FROM    tbl_faturamento
					JOIN    tbl_faturamento_item USING(faturamento)
					WHERE   tmp_latinatec_item.nota_fiscal          = tbl_faturamento.nota_fiscal
					AND     tmp_latinatec_item.nota_fiscal_serie    = tbl_faturamento.serie
					AND     tmp_latinatec_item.empresa              = tbl_faturamento.empresa
					AND     tmp_latinatec_item.peca                 = tbl_faturamento_item.peca
					AND     tbl_faturamento.fabrica                 = $login_fabrica
					AND     tbl_faturamento.distribuidor            IS NULL
            ";
			$res = pg_query($con, $sql);
            $msg_erro .= pg_errormessage($con);

			$sql  = "SELECT DISTINCT peca_referencia, nota_fiscal, nota_fiscal_serie, empresa FROM tmp_latinatec_item WHERE nota_fiscal_existente IS TRUE";
			$res  = pg_query($con, $sql);
			$rows = pg_num_rows($res);

			if ($rows > 0) {
				$logs[] = "<br /><br />-----------------------------";
				$logs[] = "PEÇAS JÁ FATURADAS NAS NOTAS FISCAIS";

				for ($i = 0; $i < $rows; $i++) {
					$peca_referencia   = pg_fetch_result($res, $i, "peca_referencia");
					$nota_fiscal       = pg_fetch_result($res, $i, "nota_fiscal");
					$nota_fiscal_serie = pg_fetch_result($res, $i, "nota_fiscal_serie");
					$empresa           = pg_fetch_result($res, $i, "empresa");
					$logs[] = "PEÇA: {$peca_referencia} / NOTA FISCAL: {$nota_fiscal} / SÉRIE: {$nota_fiscal_serie} / EMPRESA: {$empresa}";
				}
				$logs[] = "-----------------------------<br /><br />";

				$sql = "DELETE FROM tmp_latinatec_item WHERE nota_fiscal_existente = true";
				$res = pg_query($con, $sql);
                $msg_erro .= pg_errormessage($con);
			}

			$sql = "DROP TABLE IF EXISTS tmp_latinatec_nf_sem_item;

                    CREATE TEMP TABLE tmp_latinatec_nf_sem_item (
					nota_fiscal text,
					nota_fiscal_serie int,
					empresa text
				);";
			$res = pg_query($con, $sql);
            $msg_erro .= pg_errormessage($con);

			$sql = "SELECT  tmp_latinatec.nota_fiscal       ,
                            tmp_latinatec.nota_fiscal_serie ,
                            tmp_latinatec.empresa
					INTO    tmp_latinatec_nf_sem_item
					FROM    tmp_latinatec
               LEFT JOIN    tmp_latinatec_item  ON  tmp_latinatec_item.nota_fiscal          = tmp_latinatec.nota_fiscal
                                                AND tmp_latinatec_item.nota_fiscal_serie    = tmp_latinatec.nota_fiscal_serie
                                                AND tmp_latinatec_item.empresa              = tmp_latinatec.empresa
					WHERE   tmp_latinatec_item.nota_fiscal IS NULL";
			$res = pg_query($con, $sql);
            $msg_erro .= pg_errormessage($con);

			$sql  = "SELECT DISTINCT nota_fiscal, nota_fiscal_serie, empresa FROM tmp_latinatec_nf_sem_item";
			$res  = pg_query($con, $sql);
			$rows = pg_num_rows($res);

			if ($rows > 0) {
				$logs[] = "<br /><br />-----------------------------";
				$logs[] = "NOTAS FICAIS SEM PEÇAS";

				for ($i = 0; $i < $rows; $i++) {
					$nota_fiscal       = pg_fetch_result($res, $i, "nota_fiscal");
					$nota_fiscal_serie = pg_fetch_result($res, $i, "nota_fiscal_serie");
					$empresa           = pg_fetch_result($res, $i, "empresa");
					$logs[] = "NOTA FISCAL: {$nota_fiscal} / SÉRIE: {$nota_fiscal_serie} / EMPRESA: {$empresa}";
				}
				$logs[] = "-----------------------------<br /><br />";

				$sql = "DELETE FROM tmp_latinatec
						USING tmp_latinatec_nf_sem_item
						WHERE tmp_latinatec.nota_fiscal = tmp_latinatec_nf_sem_item.nota_fiscal
						AND tmp_latinatec.nota_fiscal_serie = tmp_latinatec_nf_sem_item.nota_fiscal_serie
						AND tmp_latinatec.empresa = tmp_latinatec_nf_sem_item.empresa";
				$res = pg_query($con, $sql);
                $msg_erro .= pg_errormessage($con);
			}

			$sql = "INSERT INTO tbl_faturamento(
						fabrica     ,
						emissao     ,
						saida       ,
						transp      ,
						posto       ,
						total_nota  ,
						cfop        ,
						nota_fiscal ,
						serie       ,
						empresa
					)
                    SELECT  $login_fabrica,
                            tmp_latinatec.data_emissao,
                            tmp_latinatec.data_emissao,
                            tmp_latinatec.transportadora,
                            tmp_latinatec.posto,
                            tmp_latinatec.valor_total,
                            tmp_latinatec.cfop,
                            tmp_latinatec.nota_fiscal,
                            tmp_latinatec.nota_fiscal_serie,
                            tmp_latinatec.empresa
                    FROM    tmp_latinatec
               LEFT JOIN    tbl_faturamento ON  tmp_latinatec.nota_fiscal       = tbl_faturamento.nota_fiscal
                                            AND tmp_latinatec.nota_fiscal_serie = tbl_faturamento.serie
                                            AND tmp_latinatec.empresa           = tbl_faturamento.empresa
                                            AND tbl_faturamento.fabrica         = $login_fabrica
                                            AND tbl_faturamento.distribuidor    IS NULL
                    WHERE   tbl_faturamento.faturamento IS NULL
            ";
			$res = pg_query($con, $sql);
            $msg_erro .= pg_errormessage($con);

			$sql = "UPDATE tmp_latinatec_item
					SET faturamento = tbl_faturamento.faturamento
					FROM tbl_faturamento
					WHERE tbl_faturamento.fabrica = $login_fabrica
					AND tbl_faturamento.nota_fiscal = tmp_latinatec_item.nota_fiscal
					AND tbl_faturamento.serie = tmp_latinatec_item.nota_fiscal_serie
					AND tbl_faturamento.empresa = tmp_latinatec_item.empresa
					AND tbl_faturamento.distribuidor IS NULL";
			$res = pg_query($con, $sql);
            $msg_erro .= pg_errormessage($con);

			$sql  = "SELECT DISTINCT peca_referencia, nota_fiscal, nota_fiscal_serie, empresa FROM tmp_latinatec_item WHERE faturamento IS NULL";
			$res  = pg_query($con, $sql);
			$rows = pg_num_rows($res);

			if ($rows > 0) {
				$logs[] = "<br /><br />-----------------------------";
				$logs[] = "PEÇAS SEM NOTAS FICAIS";

				for ($i = 0; $i < $rows; $i++) {
					$peca_referencia   = pg_fetch_result($res, $i, "peca_referencia");
					$nota_fiscal       = pg_fetch_result($res, $i, "nota_fiscal");
					$nota_fiscal_serie = pg_fetch_result($res, $i, "nota_fiscal_serie");
					$empresa           = pg_fetch_result($res, $i, "empresa");
					$logs[] = "PEÇA: {$peca_referencia} / NOTA FISCAL: {$nota_fiscal} / SÉRIE: {$nota_fiscal_serie} / EMPRESA: {$empresa}";
				}
				$logs[] = "-----------------------------<br /><br />";

				$sql = "DELETE FROM tmp_latinatec_item WHERE faturamento IS NULL";
				$res = pg_query($con, $sql);
                $msg_erro .= pg_errormessage($con);
			}

			$sql = "INSERT INTO tbl_faturamento_item(
						faturamento ,
						peca        ,
						qtde        ,
						preco       ,
						aliq_icms   ,
						valor_icms  ,
						base_icms   ,
						aliq_ipi    ,
						valor_ipi   ,
						base_ipi    ,
						pedido
					)
					(
						SELECT
							faturamento,
							peca,
							peca_qtde,
							preco_unitario,
							aliq_icms,
							valor_icms,
							base_icms,
							aliq_ipi,
							valor_ipi,
							base_ipi,
							pedido
						FROM tmp_latinatec_item
					)";
			$res = pg_query($con, $sql);
            $msg_erro .= pg_errormessage($con);

			$sql = "CREATE TEMP TABLE tmp_latinatec_estoque_movimento (
						fabrica int,
						posto int,
						data date,
						qtde_item int,
						faturamento int,
						peca int,
						pedido int,
						nota_fiscal text
					)";
			$res = pg_query($con, $sql);
            $msg_erro .= pg_errormessage($con);

			$sql = "SELECT  $login_fabrica                      AS fabrica  ,
                            tmp_latinatec_item.posto                        ,
                            CURRENT_DATE                        AS data     ,
                            SUM(tmp_latinatec_item.peca_qtde)   AS qtde_item,
                            tmp_latinatec_item.faturamento                  ,
                            tmp_latinatec_item.peca                         ,
                            tmp_latinatec_item.pedido                       ,
                            tmp_latinatec.nota_fiscal
					FROM    tmp_latinatec_item
					JOIN    tmp_latinatec       ON  tmp_latinatec_item.nota_fiscal  = tmp_latinatec.nota_fiscal
					JOIN    tbl_faturamento     ON  tmp_latinatec.nota_fiscal       = tbl_faturamento.nota_fiscal
                                                AND tmp_latinatec.nota_fiscal_serie = tbl_faturamento.serie
                                                AND tmp_latinatec.empresa           = tbl_faturamento.empresa
                                                AND tbl_faturamento.fabrica         = $login_fabrica
                                                AND tbl_faturamento.distribuidor    IS NULL
					JOIN    tbl_estoque_cfop    ON  tbl_faturamento.cfop            = tbl_estoque_cfop.cfop
                                                AND tbl_estoque_cfop.fabrica        = tbl_faturamento.fabrica
              GROUP BY      tmp_latinatec_item.posto        ,
                            tmp_latinatec_item.faturamento  ,
                            tmp_latinatec_item.pedido       ,
                            tmp_latinatec.nota_fiscal       ,
                            tmp_latinatec_item.peca
            ";
			$res = pg_query($con, $sql);
            $msg_erro .= pg_errormessage($con);
			$rows = pg_num_rows($res);

			for ($i = 0; $i < $rows; $i++) {
				$xfabrica    = pg_fetch_result($res, $i, "fabrica");
				$xposto       = pg_fetch_result($res, $i, "posto");
				$xdata        = pg_fetch_result($res, $i, "data");
				$xqtde_item   = pg_fetch_result($res, $i, "qtde_item");
				$xfaturamento = pg_fetch_result($res, $i, "faturamento");
				$xpeca        = pg_fetch_result($res, $i, "peca");
				$xpedido      = pg_fetch_result($res, $i, "pedido");
				$xnota_fiscal = pg_fetch_result($res, $i, "nota_fiscal");

				$sql2 = "INSERT INTO tmp_latinatec_estoque_movimento
						 (fabrica,posto,data,qtde_item,faturamento,peca,pedido,nota_fiscal)
						 VALUES
						 ({$xfabrica},{$xposto},'{$xdata}',{$xqtde_item},{$xfaturamento},{$xpeca},{$xpedido},'{$xnota_fiscal}')";
				$res2 = pg_query($con, $sql2);
                $msg_erro .= pg_errormessage($con);
			}

			$sql = "INSERT INTO tbl_estoque_posto_movimento(
						fabrica     ,
						posto       ,
						data        ,
						qtde_entrada,
						faturamento ,
						peca        ,
						pedido      ,
						nf
					)
					(
						SELECT  fabrica     ,
                                posto       ,
                                data        ,
                                qtde_item   ,
                                faturamento ,
                                peca        ,
                                pedido      ,
                                nota_fiscal
						FROM    tmp_latinatec_estoque_movimento
					)";
			$res = pg_query($con, $sql);
            $msg_erro .= pg_errormessage($con);
			$sql = "SELECT  posto,
                            peca,
                            SUM(qtde_item) AS qtde
					FROM    tmp_latinatec_estoque_movimento
					JOIN    tbl_posto_fabrica USING(posto)
					WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
					AND     (
                                tbl_posto_fabrica.controla_estoque      IS TRUE
                            OR  tbl_posto_fabrica.controle_estoque_novo IS TRUE
                            )
              GROUP BY      tbl_posto_fabrica.fabrica   ,
                            posto                       ,
                            peca
            ";
			$res = pg_query($con, $sql);
            $msg_erro .= pg_errormessage($con);
			$rows = pg_num_rows($res);

			if ($rows > 0) {
				for ($i = 0; $i < $rows; $i++) {
					$posto = pg_fetch_result($res, $i, "posto");
					$peca  = pg_fetch_result($res, $i, "peca");
					$qtde  = pg_fetch_result($res, $i, "qtde");

					$sql2 = "SELECT posto,
                                    peca,
                                    qtde
							FROM    tbl_estoque_posto
							WHERE   tbl_estoque_posto.fabrica   = $login_fabrica
							AND     tbl_estoque_posto.posto     = $posto
							AND     tbl_estoque_posto.peca      = $peca
                    ";
					$res2 = pg_query($con, $sql2);
					$rows2 = pg_num_rows($sql2);

					if ($rows2 > 0) {
						$sql2 = "   UPDATE  tbl_estoque_posto
                                    SET     qtde = tbl_estoque_posto.qtde + $qtde
                                    WHERE   tbl_estoque_posto.fabrica = $login_fabrica
                                    AND     tbl_estoque_posto.posto   = $posto
                                    AND     tbl_estoque_posto.peca    = $peca
                        ";
					} else {
						$sql2 = "INSERT INTO tbl_estoque_posto  (
                                                                    fabrica ,
                                                                    posto   ,
                                                                    peca    ,
                                                                    qtde
                                                                )VALUES(
                                                                    $login_fabrica  ,
                                                                    $posto          ,
                                                                    $peca           ,
                                                                    $qtde
                                                                )";
					}

					$res2 = pg_query($con, $sql2);
                    $msg_erro .= pg_errormessage($con);
				}
			}

			$sql = "SELECT fn_atualiza_pedido_recebido_fabrica(tbl_pedido.pedido, tbl_pedido.fabrica, CURRENT_DATE)
					FROM tbl_pedido
					JOIN tmp_latinatec_item ON tbl_pedido.pedido = tmp_latinatec_item.pedido";
			$res = pg_query($con, $sql);
            $msg_erro .= pg_errormessage($con);

			$sql = "SELECT fn_atualiza_pedido_item(fi.peca, fi.pedido, NULL, fi.peca_qtde)
					FROM (SELECT DISTINCT peca, pedido, peca_qtde FROM tmp_latinatec_item) AS fi";
			$res = pg_query($con, $sql);
            $msg_erro .= pg_errormessage($con);
			$sql = "SELECT fn_atualiza_status_pedido(tbl_pedido.fabrica, tbl_pedido.pedido)
					FROM tbl_pedido
					WHERE fabrica = $login_fabrica
					AND pedido IN (SELECT DISTINCT pedido FROM tmp_latinatec_item)";
			$res = pg_query($con, $sql);
            $msg_erro .= pg_errormessage($con);
			$sql  = "SELECT DISTINCT nota_fiscal, nota_fiscal_serie, posto_cnpj, empresa FROM tmp_latinatec";
			$res  = pg_query($con, $sql);
			$rows = pg_num_rows($res);

			if ($rows > 0) {
				$logs[] = "<br /><br />-----------------------------";
				$logs[] = "NOTAS FISCAIS FATURADAS";

				for ($i = 0; $i < $rows; $i++) {
					$nota_fiscal       = pg_fetch_result($res, $i, "nota_fiscal");
					$nota_fiscal_serie = pg_fetch_result($res, $i, "nota_fiscal_serie");
					$empresa           = pg_fetch_result($res, $i, "empresa");

					$sql2 = "SELECT COUNT(*) AS itens_nf
						 	 FROM   tmp_latinatec_item
							 WHERE  nota_fiscal         = '$nota_fiscal'
							 AND    nota_fiscal_serie   = '$nota_fiscal_serie'
							 AND    empresa             = '$empresa'
                    ";
					$res2 = pg_query($con, $sql2);

					$itens_nf = pg_fetch_result($res2, 0, "itens_nf");

					$logs[] = "NOTA FISCAL: {$nota_fiscal} / SÉRIE: {$nota_fiscal_serie} / EMPRESA: {$empresa} / ITENS FATURADOS: {$itens_nf}";
				}
				$logs[] = "-----------------------------<br /><br />";
			}
		} else {
			$logs[] = "ARQUIVO DE FATURAMENTO ITEM NÃO ENCONTRADO";
		}
	} else {
		$logs[] = "ARQUIVO DE FATURAMENTO NÃO ENCONTRADO";
	}

	$logs[] = "FIM: ".date("H:i");

	$header  = "MIME-Version: 1.0\n";
	$header .= "Content-type: text/html; charset=iso-8859-1\n";
	$header .= "From: Telecontrol <telecontrol\@telecontrol.com.br>";

	mail("iuri.brito@latina.com.br, marcelo.cardoso@latina.com.br, helpdesk@telecontrol.com.br", "TELECONTROL / LATINATEC ({$data}) - IMPORTA FATURAMENTO", implode("<br />", $logs), $header);

	system("mv $arquivo_temp $arquivo_backup");

    if (!empty($msg_erro)) {
		$msg_erro .= "\n\n".$log_erro;
		$fp = fopen("/tmp/latinatec/faturamento.err","w");
		fwrite($fp,$msg_erro);
		fclose($fp);
		$msg = 'Script: '.__FILE__.'<br />' . $msg_erro;
		Log::envia_email($data, APP, $msg);

	} else {
		$fp = fopen("/tmp/latinatec/faturamento.err","w");
		fwrite($fp,$log_erro);
		fclose($fp);

		system("mv $origem$file /tmp/latinatec/faturamento".date('Y-m-d-H-i').".txt");
		system("mv $origem$file2 /tmp/latinatec/faturamento_item".date('Y-m-d-H-i').".txt");

		Log::log2($data, APP . ' - Executado com Sucesso - ' . date('Y-m-d-H-i'));

	}

	$phpCron->termino();

} catch (Exception $e) {
    $e->getMessage();
    $msg = "Arquivo: ".__FILE__."\r\n<br />Linha: " . $e->getLine() . "\r\n<br />Descrição do erro: " . $e->getMessage() ."<hr /><br /><br />". implode("<br /><br />", $logs);

    Log::envia_email($data,Date('d/m/Y H:i:s')." - LATINATEC - Importa faturamento (importa-faturamento.php)", $msg);


	$logs[] = date("H-i")."ERRO AO IMPORTAR PREÇOS: ".$e->getMessage();
	$logs[] = "FIM: ".date("H-i");
    #
	$header  = "MIME-Version: 1.0\n";
	$header .= "Content-type: text/html; charset=iso-8859-1\n";
	$header .= "From: Telecontrol <telecontrol\@telecontrol.com.br>";

	mail("iuri.brito@latina.com.br, marcelo.cardoso@latina.com.br, helpdesk@telecontrol.com.br", "TELECONTROL / LATINATEC ({$data}) - IMPORTA FATURAMENTO", implode("<br />", $logs), $header);

	system("mv $arquivo_temp $arquivo_backup");
}
