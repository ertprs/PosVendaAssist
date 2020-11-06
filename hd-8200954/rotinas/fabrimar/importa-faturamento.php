<?php

define('ENV','producao');  // producao Alterar para produção ou algo assim

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require_once dirname(__FILE__) . '/../funcoes.php';
	// include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

    $data['login_fabrica'] 	= 145;
    $data['fabrica'] 		= 'fabrimar';
    $data['arquivo_log'] 	= 'importa-faturamento';
	$data['tipo'] 			= 'importa-faturamento';
    $data['log'] 			= 2;
    $data['arquivos'] 		= "/tmp";
    $data['data_sistema'] 	= Date('Y-m-d');
    $logs 					= array();
    $logs_erro				= array();
    $logs_cliente			= array();
    $erro 					= false;

    /*
	* Log Class
	*/
    $logClass = new Log2();

    if (ENV == 'teste') {

	    $logClass->adicionaLog(array("titulo" => "Log de erro - Importa Faturamento - Fabrimar")); // Titulo
	    $logClass->adicionaEmail("pedidos@telecontrol.com.br");

	}else{

		$logClass->adicionaEmail("marisa.silvana@telecontrol.com.br");
		$logClass->adicionaEmail("pedidos@telecontrol.com.br");
	    #$logClass->adicionaEmail("fernando.saibro@fabrimar.com.br");
	    #$logClass->adicionaEmail("kevin.robinson@fabrimar.com.br");
	    #$logClass->adicionaEmail("anderson.dutra@fabrimar.com.br");

		$arquivo        = "/tmp/fabrimar/faturamento/faturamento.txt";
		$arquivo_temp   = "/tmp/fabrimar/faturamento/faturamento_temp_{$data}.txt";
		$arquivo_backup = "/tmp/fabrimar/faturamento/faturamento_backup_{$data}.txt";

		$arquivo_item         = "/tmp/fabrimar/faturamento/faturamento_item.txt";
		$arquivo_item_temp    = "/tmp/fabrimar/faturamento/faturamento_item_temp_{$data}.txt";
		$arquivo_item_backup  = "/tmp/fabrimar/faturamento/faturamento_item_backup_{$data}.txt";

	}

	if (ENV == 'producao' ) {
		$data['origem']       = "/home/fabrimar/fabrimar-telecontrol/";
		$data['file']         = 'telecontrol-nf.txt';
		$data['file2']        = 'telecontrol-nf-item.txt';
		$data["dest"]         = "helpdesk@telecontrol.com.br";
		$data["dest_cliente"] = "helpdesk@telecontrol.com.br";
	} else {
		$data['origem'] = __DIR__ . "/./";
		$data['file']   = 'telecontrol-nf.txt';
		$data['file2']  = 'telecontrol-nf-item.txt';
	}

    extract($data);

	$phpCron = new PHPCron($login_fabrica, __FILE__);
	$phpCron->inicio();

	define('APP', 'Importa Faturamento - '.$fabrica);

	if (ENV != 'teste') {

	    $arquivo_err = "{$arquivos}/{$fabrica}/{$arquivo_log}-{$data_sistema}.err";
	    $arquivo_log = "{$arquivos}/{$fabrica}/{$arquivo_log}-{$data_sistema}.log";
	    system ("mkdir -p -m 777 {$arquivos}/{$fabrica}" );

	}

	$msg_erro = "";
	$arquivo_nao_encontrado = false;

	date_default_timezone_set("America/Sao_Paulo");

	$logs[] = "INÍCIO: ".date("H:i")." hs";

	if (file_exists($origem.$file)) {
		#system("cp {$arquivo} {$arquivo_temp}");

        $sql = "DROP TABLE IF EXISTS tmp_fabrimar;";
        $res = pg_query($con,$sql);

	#echo $msg_erro .= pg_last_error();

        if(strlen(pg_last_error()) > 0){
			$msg_erro .= pg_last_error();
		}

		#echo "entrei aqui.....";

		$sql = "CREATE TEMP TABLE tmp_fabrimar (
					posto_cnpj text,
					posto int,
					nota_fiscal text,
					nota_fiscal_serie text,
					data_emissao date,
					valor_total double precision,
					valor_ipi double precision,
					valor_icms double precision,
					nota_fiscal_existente boolean
				)";
		$res = pg_query($con, $sql);

        if(strlen(pg_last_error()) > 0){
			$msg_erro .= pg_last_error();
		}

		#echo "entrei aqui aqui.....";

		$arquivo_conteudo = explode("\n", file_get_contents($origem.$file));

		foreach ($arquivo_conteudo as $linha_numero => $linha_conteudo) {
			$linha_erro = false;

			if ($linha_conteudo == "\n" || empty($linha_conteudo)) {
				continue;
			}

			list(
				$posto_cnpj,
				$nota_fiscal,
				$data_emissao,
				$nota_fiscal_serie,
				$valor_total,
				$valor_ipi,
				$valor_icms
			) = explode(";", $linha_conteudo);

			$posto_cnpj        = trim($posto_cnpj);
			$nota_fiscal       = substr(trim($nota_fiscal), 0, 6);
			$nota_fiscal_serie = trim($nota_fiscal_serie);
			$data_emissao      = trim($data_emissao);
			$valor_total       = trim($valor_total);
			$valor_ipi         = trim($valor_ipi);
			$valor_icms        = trim($valor_icms);

			if (!strlen($posto_cnpj)) {
				#$logs[] = "NF LINHA ".($linha_numero + 1).": - CNPJ DO POSTO NÃO INFORMADO";
				$linha_erro = true;
			}

			if (!strlen($nota_fiscal)) {
				#$logs[] = "NF LINHA ".($linha_numero + 1).": - NOTA FISCAL NÃO INFORMADA";
				$linha_erro = true;
			}

			if (!strlen($nota_fiscal_serie)) {
				#$logs[] = "NF LINHA ".($linha_numero + 1).": - SERIE DA NOTA FISCAL NÃO INFORMADA";
				$linha_erro = true;
			}

			if (!strlen($data_emissao)) {
				#$logs[] = "NF LINHA ".($linha_numero + 1).": - DATA DE EMISSÃO DA NOTA FISCAL NÃO INFORMADA";
				$linha_erro = true;
			}

			if (!strlen($data_emissao)) {
				#$logs[] = "NF LINHA ".($linha_numero + 1).": - DATA DE EMISSÃO DA NOTA FISCAL NÃO INFORMADA";
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

			if ($linha_erro === false) {

				$sql = "INSERT INTO tmp_fabrimar(
							posto_cnpj,
							nota_fiscal,
							nota_fiscal_serie,
							data_emissao,
							valor_total,
							valor_ipi,
							valor_icms
						)VALUES(
							'{$posto_cnpj}',
							'{$nota_fiscal}',
							'{$nota_fiscal_serie}',
							'{$data_emissao}',
							{$valor_total},
							{$valor_ipi},
							{$valor_icms}
						)";

				$res = pg_query($con, $sql);
                if(strlen(pg_last_error()) > 0){
					$msg_erro .= pg_last_error();
				}
			}
		}

		$sql = "UPDATE  tmp_fabrimar
				SET     posto = tbl_posto.posto
				FROM    tbl_posto
				JOIN    tbl_posto_fabrica USING (posto)
				WHERE   tbl_posto_fabrica.fabrica   = $login_fabrica
				AND     tmp_fabrimar.posto_cnpj    = tbl_posto.cnpj";
		$res = pg_query($con, $sql);
        if(strlen(pg_last_error()) > 0){
			$msg_erro .= pg_last_error();
		}

		$sql  = "SELECT DISTINCT posto_cnpj FROM tmp_fabrimar WHERE posto IS NULL";
		$res  = pg_query($con, $sql);
		$rows = pg_num_rows($res);

		if ($rows > 0) {
			#$logs[] = "<br /> -----------------------------";
			#$logs[] = "POSTOS NÃO ENCOTRADOS NO SISTEMA";

			#for ($i = 0; $i < $rows; $i++) {
			#	$logs[] = pg_fetch_result($res, $i, "posto_cnpj");
			#}
			#$logs[] = "-----------------------------<br />";

			$sql = "DELETE FROM tmp_fabrimar WHERE posto IS NULL";
			$res = pg_query($con, $sql);
            if(strlen(pg_last_error()) > 0){
				$msg_erro .= pg_last_error();
			}
		}

		if (file_exists($origem.$file2)) {
			#system("cp {$arquivo_item} {$arquivo_item_temp}");

			$sql = "CREATE TEMP TABLE tmp_fabrimar_item (
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
					nota_fiscal_existente boolean,
					faturamento int,
					os_item int,
					devolucao_obrig boolean
				)";
			$res = pg_query($con, $sql);
            if(strlen(pg_last_error()) > 0){
				$msg_erro .= pg_last_error();
			}
			$arquivo_item_conteudo = explode("\n", file_get_contents($origem.$file2));

			foreach ($arquivo_item_conteudo as $linha_item_numero => $linha_item_conteudo) {
				$linha_erro = false;

				if ($linha_item_conteudo == "\n" || empty($linha_item_conteudo)) {
					continue;
				}

				list(
					$nota_fiscal,
					$nota_fiscal_serie,
					$peca_referencia,
					$sequencia,
					$pedido,
					$peca_qtde,
					$preco_unitario,
					$aliq_ipi,
					$aliq_icms,
					$valor_ipi,
					$valor_icms,
					$base
				) = explode(";", $linha_item_conteudo);

				$nota_fiscal       = substr(trim($nota_fiscal), 0, 6);
				$nota_fiscal_serie = trim($nota_fiscal_serie);
				$peca_referencia   = trim($peca_referencia);
				$pedido            = str_replace("-", "", trim($pedido));
				$peca_qtde         = trim($peca_qtde);
				$preco_unitario    = trim($preco_unitario);
				$aliq_ipi          = trim($aliq_ipi);
				$aliq_icms         = trim($aliq_icms);
				$base_ipi          = trim($base);
				$base_icms         = trim($base);
				$valor_icms        = trim($valor_icms);
				$valor_ipi         = trim($valor_ipi);

				if (!strlen($nota_fiscal)) {
					$logs[] = "NF ITEM - LINHA ".($linha_numero + 1).": - NOTA FISCAL NÃO INFORMADA";
					$linha_erro = true;
				}

				if (!strlen($peca_referencia)) {
					$logs[] = "NF ITEM - LINHA ".($linha_numero + 1).": - REFERENCIA DA PECA NÃO INFORMADA";
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

				if (!is_numeric($preco_unitario)) {
					$logs[] = "NF ITEM - LINHA ".($linha_numero + 1).": - PRECO UNITARIO EM FORMATO INCORRETO";
					$linha_erro = true;
				} else {
					if ($preco_unitario == 0) {
						$logs[] = "NF ITEM - LINHA ".($linha_numero + 1).": - PRECO UNITARIO NÃO PODE SER 0";
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

				if ($linha_erro === false) {
					$sql = "INSERT INTO tmp_fabrimar_item(
								nota_fiscal,
								nota_fiscal_serie,
								peca_referencia,
								txt_pedido,
								peca_qtde,
								preco_unitario,
								aliq_ipi,
								aliq_icms,
								base_ipi,
								base_icms,
								valor_icms,
								valor_ipi
							)VALUES(
								'{$nota_fiscal}',
								'{$nota_fiscal_serie}',
								'{$peca_referencia}',
								{$pedido},
								{$peca_qtde},
								{$preco_unitario},
								{$aliq_ipi},
								{$aliq_icms},
								{$base_ipi},
								{$base_icms},
								{$valor_icms},
								{$valor_ipi}
							)";

					$res = pg_query($con, $sql);

                    if(strlen(pg_last_error()) > 0){
						$msg_erro .= pg_last_error();
					}

				}
			}

			$sql = "UPDATE  tmp_fabrimar
					SET     posto = tbl_posto.posto
					FROM    tbl_posto
					JOIN    tbl_posto_fabrica USING (posto)
					WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
					AND     tmp_fabrimar.posto_cnpj = tbl_posto.cnpj";
			$res = pg_query($con, $sql);

			$sql  = "SELECT DISTINCT posto_cnpj FROM tmp_fabrimar WHERE posto IS NULL";
			$res  = pg_query($con, $sql);
			$rows = pg_num_rows($res);

			if ($rows > 0) {
				#$logs[] = "<br /> -----------------------------";
				#$logs[] = "POSTOS(CNPJ) ENVIADOS NO ARQUIVO DE FATURAMENTO QUE NAO FORAM ENCONTRADOS NO SISTEMA TELECONTROL";

				for ($i = 0; $i < $rows; $i++) {
					$logs[] = pg_fetch_result($res, $i, "posto_cnpj");
				}
				#$logs[] = "-----------------------------<br />";

				$sql = "DELETE FROM tmp_fabrimar WHERE posto IS NULL";
				$res = pg_query($con, $sql);
                if(strlen(pg_last_error()) > 0){
					$msg_erro .= pg_last_error();
				}
			}

			$sql = "DELETE FROM tmp_fabrimar_item WHERE txt_pedido ~* '\\\\D';

					UPDATE  tmp_fabrimar_item
					SET     pedido = tbl_pedido.pedido
					FROM    tbl_pedido
					WHERE   tbl_pedido.fabrica = $login_fabrica
					AND     tmp_fabrimar_item.txt_pedido::integer = tbl_pedido.pedido_blackedecker";
			$res = pg_query($con, $sql);
            if(strlen(pg_last_error()) > 0){
				$msg_erro .= pg_last_error();
			}

			$sql  = "SELECT DISTINCT txt_pedido FROM tmp_fabrimar_item WHERE pedido IS NULL";
			$res  = pg_query($con, $sql);
			$rows = pg_num_rows($res);

			/* $res = pg_query($con, "SELECT * from tmp_fabrimar_item");
			var_dump(pg_fetch_all($res)); exit(); */

			if ($rows > 0) {
				#$logs[] = "<br /> -----------------------------";
				#$logs[] = "PEDIDOS ENVIADOS NO ARQUIVO DE FATURAMENTO QUE NAO FORAM  ENCOTRADOS NO SISTEMA TELECONTROL";

				for ($i = 0; $i < $rows; $i++) {
					#$logs[] = "PEDIDO: ".pg_fetch_result($res, $i, "txt_pedido");
				}
				$#logs[] = "-----------------------------<br />";


				$sql = "DELETE FROM tmp_fabrimar_item WHERE pedido IS NULL";
				$res = pg_query($con, $sql);
                
				if(strlen(pg_last_error()) > 0){
					$msg_erro .= pg_last_error();
				}

			}

			$sql = "UPDATE  tmp_fabrimar_item
					SET     peca = tbl_peca.peca
					FROM    tbl_peca
					WHERE   tbl_peca.fabrica = $login_fabrica
					AND     tmp_fabrimar_item.peca_referencia = tbl_peca.referencia_pesquisa";
			$res = pg_query($con, $sql);
            if(strlen(pg_last_error()) > 0){
				$msg_erro .= pg_last_error();
			}

			$sql  = "SELECT DISTINCT peca_referencia FROM tmp_fabrimar_item WHERE peca IS NULL";
			$res  = pg_query($con, $sql);
			$rows = pg_num_rows($res);

			if ($rows > 0) {
				$logs[] = "<br /> -----------------------------";
				$logs[] = "PEÇAS NÃO ENCOTRADAS NO SISTEMA";

				for ($i = 0; $i < $rows; $i++) {
					$logs[] = pg_fetch_result($res, $i, "peca_referencia");
				}
				$logs[] = "-----------------------------<br />";


				$sql = "DELETE FROM tmp_fabrimar_item WHERE peca IS NULL";
				$res = pg_query($con, $sql);
			}

			$sql = "UPDATE  tmp_fabrimar_item
					SET     pedido_item = tbl_pedido_item.pedido_item
					FROM    tbl_pedido_item
					WHERE   tbl_pedido_item.pedido = tmp_fabrimar_item.pedido
					AND     tbl_pedido_item.peca = tmp_fabrimar_item.peca";
			$res = pg_query($con, $sql);

 		
			$sql = "UPDATE tmp_fabrimar_item SET
					os_item = tbl_os_item.os_item,
					devolucao_obrig = tbl_os_item.peca_obrigatoria
				FROM tbl_pedido_item, tbl_os_item
				WHERE tbl_pedido_item.pedido = tmp_fabrimar_item.pedido
				AND tbl_pedido_item.pedido_item = tmp_fabrimar_item.pedido_item
				AND tbl_pedido_item.peca = tmp_fabrimar_item.peca
				AND tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
				AND tbl_pedido_item.pedido = tbl_os_item.pedido
				AND tbl_pedido_item.peca = tbl_os_item.peca";
			$res = pg_query($con, $sql);	


			$sql  = "SELECT DISTINCT peca_referencia, pedido FROM tmp_fabrimar_item WHERE pedido_item IS NULL";
			$res  = pg_query($con, $sql);
			$rows = pg_num_rows($res);

			if ($rows > 0) {
				$logs[] = "<br /> -----------------------------";
				$logs[] = "PEÇAS NÃO ENCOTRADAS EM PEDIDOS NO SISTEMA";

				for ($i = 0; $i < $rows; $i++) {
					$logs[] = "peça: ".pg_fetch_result($res, $i, "peca_referencia")." pedido:".pg_fetch_result($res, $i, "pedido");
				}
				$logs[] = "-----------------------------<br />";


				$sql = "DELETE FROM tmp_fabrimar_item WHERE pedido_item IS NULL";
				$res = pg_query($con, $sql);
				
			}

			$sql = "UPDATE  tmp_fabrimar
					SET     nota_fiscal_existente = true
					FROM    tbl_faturamento
					WHERE   tmp_fabrimar.nota_fiscal       = tbl_faturamento.nota_fiscal
					AND     tmp_fabrimar.nota_fiscal_serie = tbl_faturamento.serie
					AND     tbl_faturamento.fabrica         = $login_fabrica
					AND     tbl_faturamento.distribuidor IS NULL";
			$res = pg_query($con, $sql);

			if(strlen(pg_last_error()) > 0){
				$msg_erro .= pg_last_error();
			}

			$sql  = "SELECT DISTINCT nota_fiscal, nota_fiscal_serie FROM tmp_fabrimar WHERE nota_fiscal_existente IS TRUE";
			$res  = pg_query($con, $sql);
			$rows = pg_num_rows($res);

			if ($rows > 0) {
				$sql = "DELETE FROM tmp_fabrimar WHERE nota_fiscal_existente = true";
				$res = pg_query($con, $sql);

                		if(strlen(pg_last_error()) > 0){
					$msg_erro .= pg_last_error();
				}

			}

			$sql = "UPDATE  tmp_fabrimar_item
					SET     nota_fiscal_existente = true
					FROM    tbl_faturamento
					JOIN    tbl_faturamento_item USING(faturamento)
					WHERE   tmp_fabrimar_item.nota_fiscal          = tbl_faturamento.nota_fiscal
					AND     tmp_fabrimar_item.nota_fiscal_serie    = tbl_faturamento.serie
					AND     tmp_fabrimar_item.peca                 = tbl_faturamento_item.peca
					AND     tbl_faturamento.fabrica                 = $login_fabrica
					AND     tbl_faturamento.distribuidor            IS NULL
            ";
			$res = pg_query($con, $sql);

            if(strlen(pg_last_error()) > 0){
				$msg_erro .= pg_last_error();
			}

			$sql  = "SELECT DISTINCT peca_referencia, nota_fiscal, nota_fiscal_serie FROM tmp_fabrimar_item WHERE nota_fiscal_existente IS TRUE";
			$res  = pg_query($con, $sql);
			$rows = pg_num_rows($res);

			if ($rows > 0) {
				$sql = "DELETE FROM tmp_fabrimar_item WHERE nota_fiscal_existente = true";
				$res = pg_query($con, $sql);

		                if(strlen(pg_last_error()) > 0){
					$msg_erro .= pg_last_error();
				}
			}

			$sql = "DROP TABLE IF EXISTS tmp_fabrimar_nf_sem_item;
            
                    CREATE TEMP TABLE tmp_fabrimar_nf_sem_item (
					nota_fiscal text,
					nota_fiscal_serie int
				);";
			$res = pg_query($con, $sql);

            if(strlen(pg_last_error()) > 0){
				$msg_erro .= pg_last_error();
			}

			$sql = "SELECT  tmp_fabrimar.nota_fiscal       ,
                            tmp_fabrimar.nota_fiscal_serie 
					INTO    tmp_fabrimar_nf_sem_item
					FROM    tmp_fabrimar
               LEFT JOIN    tmp_fabrimar_item  ON  tmp_fabrimar_item.nota_fiscal          = tmp_fabrimar.nota_fiscal
                                                AND tmp_fabrimar_item.nota_fiscal_serie    = tmp_fabrimar.nota_fiscal_serie
					WHERE   tmp_fabrimar_item.nota_fiscal IS NULL";
			$res = pg_query($con, $sql);

            if(strlen(pg_last_error()) > 0){
				$msg_erro .= pg_errormessage($con);
			}

			$sql  = "SELECT DISTINCT nota_fiscal, nota_fiscal_serie FROM tmp_fabrimar_nf_sem_item";
			$res  = pg_query($con, $sql);
			$rows = pg_num_rows($res);

			if ($rows > 0) {
				$logs[] = "<br /> -----------------------------";
				$logs[] = "NOTAS FICAIS SEM PEÇAS";

				for ($i = 0; $i < $rows; $i++) {
					$nota_fiscal       = pg_fetch_result($res, $i, "nota_fiscal");
					$nota_fiscal_serie = pg_fetch_result($res, $i, "nota_fiscal_serie");
					$logs[] = "NOTA FISCAL: {$nota_fiscal} / SÉRIE: {$nota_fiscal_serie}";
				}
				$logs[] = "-----------------------------<br />";

				$sql = "DELETE FROM tmp_fabrimar
						USING tmp_fabrimar_nf_sem_item
						WHERE tmp_fabrimar.nota_fiscal = tmp_fabrimar_nf_sem_item.nota_fiscal
						AND tmp_fabrimar.nota_fiscal_serie = tmp_fabrimar_nf_sem_item.nota_fiscal_serie";
				$res = pg_query($con, $sql);
                if(strlen(pg_last_error()) > 0){
					$msg_erro .= pg_last_error();
				}
			}

			$sql = "INSERT INTO tbl_faturamento(
						fabrica     ,
						emissao     ,
						saida       ,
						posto       ,
						total_nota  ,
						nota_fiscal ,
						serie       
					)
                    SELECT  $login_fabrica,
                            tmp_fabrimar.data_emissao,
                            tmp_fabrimar.data_emissao as saida,
                            tmp_fabrimar.posto,
                            tmp_fabrimar.valor_total,
                            tmp_fabrimar.nota_fiscal,
                            tmp_fabrimar.nota_fiscal_serie
                    FROM    tmp_fabrimar
                    LEFT JOIN    tbl_faturamento ON  tmp_fabrimar.nota_fiscal       = tbl_faturamento.nota_fiscal
                                            AND tmp_fabrimar.nota_fiscal_serie = tbl_faturamento.serie
                                            AND tbl_faturamento.fabrica         = $login_fabrica
                    WHERE   tbl_faturamento.faturamento IS NULL
            ";
			$res = pg_query($con, $sql);

			$sql = "UPDATE tmp_fabrimar_item
					SET faturamento = tbl_faturamento.faturamento
					FROM tbl_faturamento
					WHERE tbl_faturamento.fabrica = $login_fabrica
					AND tbl_faturamento.nota_fiscal = tmp_fabrimar_item.nota_fiscal
					AND tbl_faturamento.serie = tmp_fabrimar_item.nota_fiscal_serie
					AND tbl_faturamento.distribuidor IS NULL";
			$res = pg_query($con, $sql);
			if(strlen(pg_last_error()) > 0){
				$msg_erro .= pg_last_error();
			}

			$sql  = "SELECT DISTINCT peca_referencia, nota_fiscal, nota_fiscal_serie FROM tmp_fabrimar_item WHERE faturamento IS NULL";
			$res  = pg_query($con, $sql);
			$rows = pg_num_rows($res);
			
			if ($rows > 0) {
				$logs[] = "<br /> -----------------------------";
				$logs[] = strtoupper("Itens de Notas Fiscais enviados no arquivo de itens que não encontrou o cabeçalho no arquivo de faturamento");

				for ($i = 0; $i < $rows; $i++) {
					$peca_referencia   = pg_fetch_result($res, $i, "peca_referencia");
					$nota_fiscal       = pg_fetch_result($res, $i, "nota_fiscal");
					$nota_fiscal_serie = pg_fetch_result($res, $i, "nota_fiscal_serie");
					$logs[] = "PEÇA: {$peca_referencia} / NOTA FISCAL: {$nota_fiscal} / SÉRIE: {$nota_fiscal_serie}";
				}
				$logs[] = "-----------------------------<br />";


				$sql = "DELETE FROM tmp_fabrimar_item WHERE faturamento IS NULL";
				$res = pg_query($con, $sql);
				            
                if(strlen(pg_last_error()) > 0){
					$msg_erro .= pg_last_error();
				}
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
						pedido_item ,
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
							pedido_item ,
							pedido
						FROM tmp_fabrimar_item
					)";
			$res = pg_query($con, $sql);
            if(strlen(pg_last_error()) > 0){
				$msg_erro .= pg_last_error();
			}

			$sql = "SELECT fn_atualiza_pedido_recebido_fabrica(tbl_pedido.pedido, tbl_pedido.fabrica, CURRENT_DATE)
					FROM tbl_pedido
					JOIN tmp_fabrimar_item ON tbl_pedido.pedido_blackedecker = tmp_fabrimar_item.pedido";
			$res = pg_query($con, $sql);
            if(strlen(pg_last_error()) > 0){
				$msg_erro .= pg_last_error();
			}

			$sql = "SELECT fn_atualiza_pedido_item(fi.peca, fi.pedido, fi.pedido_item, fi.peca_qtde)
					FROM (SELECT DISTINCT peca, pedido, pedido_item, peca_qtde FROM tmp_fabrimar_item) AS fi";
			$res = pg_query($con, $sql);
            if(strlen(pg_last_error()) > 0){
				$msg_erro .= pg_last_error();
			}

			$sql = "SELECT fn_atualiza_status_pedido(tbl_pedido.fabrica, tbl_pedido.pedido)
					FROM tbl_pedido
					WHERE fabrica = $login_fabrica
					AND pedido IN (SELECT DISTINCT pedido FROM tmp_fabrimar_item)";
			$res = pg_query($con, $sql);
            if(strlen(pg_last_error()) > 0){
				$msg_erro .= pg_last_error();
			}

			$sql  = "SELECT DISTINCT nota_fiscal, nota_fiscal_serie, posto_cnpj FROM tmp_fabrimar";
			$res  = pg_query($con, $sql);
			$rows = pg_num_rows($res);
			
			/* if ($rows > 0) {
				$logs[] = "<br /> -----------------------------";
				$logs[] = "NOTAS FISCAIS FATURADAS";

				for ($i = 0; $i < $rows; $i++) {
					$nota_fiscal       = pg_fetch_result($res, $i, "nota_fiscal");
					$nota_fiscal_serie = pg_fetch_result($res, $i, "nota_fiscal_serie");

					$sql2 = "SELECT COUNT(*) AS itens_nf
						 	 FROM   tmp_fabrimar_item
							 WHERE  nota_fiscal         = '$nota_fiscal'
							 AND    nota_fiscal_serie   = '$nota_fiscal_serie'
                    ";
					$res2 = pg_query($con, $sql2);

					$itens_nf = pg_fetch_result($res2, 0, "itens_nf");

					$logs[] = "NOTA FISCAL: {$nota_fiscal} / SÉRIE: {$nota_fiscal_serie} / ITENS FATURADOS: {$itens_nf}";
				}
				$logs[] = "-----------------------------<br />";
			} */
		} else {
			$logs[] = "ARQUIVO DE FATURAMENTO ITEM NÃO ENCONTRADO";
		}
	} else {
		// $logs[] = "ARQUIVO DE FATURAMENTO NÃO ENCONTRADO";
		$arquivo_nao_encontrado = true;
	}

	$logs[] = "FIM: ".date("H:i")." hs";

	// print_r($logs); exit;

	if($arquivo_nao_encontrado == false){

		/*
		* Erro
		*/
		if(strlen($msg_erro) == 0 && count($logs) > 2){

			$logClass->adicionaTituloEmail("Log de erro - Rotina: Importa Faturamento - Fabrimar");

			$msg_erro = implode("<br />", $logs);

	        $logClass->adicionaLog($msg_erro);

	        if($logClass->enviaEmails() == "200"){
	          	echo "Log de erro enviado com Sucesso!";
	        }else{
	          	echo $logClass->enviaEmails();
	        }

	        system("mv {$origem}{$file} /tmp/fabrimar/faturamento".date('Y-m-d-H-i').".txt");
	        system("mv {$origem}{$file2} /tmp/fabrimar/faturamento_item".date('Y-m-d-H-i').".txt");

	    }else if(strlen($msg_erro) > 0){

	    	$logClass->adicionaTituloEmail("Log de erro de Consulta(SQL) - Rotina: Importa Faturamento - Fabrimar");

	    	$logClass->apagaEmails();

	    	if (ENV == 'teste') {
			    $logClass->adicionaLog(array("titulo" => "Log de erro - Importa Faturamento - Fabrimar")); // Titulo
				$logClass->adicionaEmail("pedidos@telecontrol.com.br");
			}else{
				$logClass->adicionaEmail("pedidos@telecontrol.com.br");
			    $logClass->adicionaEmail("guilherme.curcio@telecontrol.com.br");
			}

			$logClass->adicionaLog($msg_erro);

			if($logClass->enviaEmails() == "200"){
	          	echo "Log de erro enviado com Sucesso!";
	        }else{
	          	echo $logClass->enviaEmails();
	        }

	    	$fp = fopen("/tmp/fabrimar/faturamento.err","w");
			fwrite($fp,$log_erro);
			fclose($fp);

			system("mv {$origem}{$file} /tmp/fabrimar/faturamento".date('Y-m-d-H-i').".txt");
			system("mv {$origem}{$file2} /tmp/fabrimar/faturamento_item".date('Y-m-d-H-i').".txt");

	    }else{
            $log = date('Y-m-d H:i:s') . ' -- Nenhum erro ao importar o arquivo de faturamento';
            file_put_contents("/tmp/fabrimar/importa-faturamento.log", $log);
	    }

	}

    $phpCron->termino();

	/* $header  = "MIME-Version: 1.0\n";
	$header .= "Content-type: text/html; charset=iso-8859-1\n";
	$header .= "From: Telecontrol <telecontrol\@telecontrol.com.br>";

	mail("fernando.saibro@fabrimar.com.br,kevin.robinson@fabrimar.com.br,anderson.dutra@fabrimar.com.br,helpdesk@telecontrol.com.br", "TELECONTROL / FABRIMAR ({$data}) - IMPORTA FATURAMENTO", implode("<br />", $logs), $header);

	system("mv $arquivo_temp $arquivo_backup");

    if (!empty($msg_erro) && count($log_erro) > 0) {
		$msg_erro .= "\n\n".$log_erro;
		$fp = fopen("/tmp/fabrimar/faturamento.err","w");
		fwrite($fp,$msg_erro);
		fclose($fp);
		$msg = 'Script: '.__FILE__.'<br />' . $msg_erro;
		Log::envia_email($data_sistema, APP, $msg);

		 system("mv $origem.$file /tmp/fabrimar/faturamento".date('Y-m-d-H-i').".txt");
                system("mv $origem.$file2 /tmp/fabrimar/faturamento_item".date('Y-m-d-H-i').".txt");


	} else {
		$fp = fopen("/tmp/fabrimar/faturamento.err","w");
		fwrite($fp,$log_erro);
		fclose($fp);

		system("mv $origem.$file /tmp/fabrimar/faturamento".date('Y-m-d-H-i').".txt");
		system("mv $origem.$file2 /tmp/fabrimar/faturamento_item".date('Y-m-d-H-i').".txt");

		Log::log2($data_sistema, APP . ' - Executado com Sucesso - ' . date('Y-m-d-H-i'));

	} */

} catch (Exception $e) {
    $e->getMessage();
    $msg = "Arquivo: ".__FILE__."\r\n<br />Linha: " . $e->getLine() . "\r\n<br />Descrição do erro: " . $e->getMessage() ."<hr /><br /><br />". implode("<br /><br />", $logs);
    $data_sistema = Date('Y-m-d');
    // Log::envia_email($data,Date('d/m/Y H:i:s')." - FABRIMAR - Importa faturamento (importa-faturamento.php)", $msg);

	$logs[] = date("H-i")."ERRO AO IMPORTAR FATURAMENTO: ".$e->getMessage();
	$logs[] = "FIM: ".date("H-i");

	$header  = "MIME-Version: 1.0\n";
	$header .= "Content-type: text/html; charset=iso-8859-1\n";
	$header .= "From: Telecontrol <telecontrol\@telecontrol.com.br>";

	// mail("william.lopes@telecontrol.com.br", "TELECONTROL / FABRIMAR ({$data}) - IMPORTA FATURAMENTO", implode("<br />", $logs), $header);
	mail("pedidos@telecontrol.com.br, guilherme.curcio@telecontrol.com.br", "TELECONTROL / FABRIMAR ({$data}) - IMPORTA FATURAMENTO", implode("<br />", $logs), $header);

	//system("mv $arquivo_temp $arquivo_backup");
}
