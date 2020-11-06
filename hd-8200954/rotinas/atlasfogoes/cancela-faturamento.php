<?php
	define('APP','Importa Faturamento Cancelado - Atlas Fogões'); // Nome da rotina, para ser enviado por e-mail
	define('ENV','producao'); // Alterar para produção ou algo assim

	try {

		include dirname(__FILE__) . '/../../dbconfig.php';
		include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
		require dirname(__FILE__) . '/../funcoes.php';

		$fabrica     = 74;
		$data 		 = date('d-m-Y');
		
		$phpCron = new PHPCron($fabrica, __FILE__); 
		$phpCron->inicio();

		$vet['fabrica'] = 'atlas';
		$vet['tipo']    = 'cancela-faturamento';
		$vet['dest']    = ENV == 'testes' ? 'ronald.santos@telecontrol.com.br' : 'helpdesk@telecontrol.com.br';
		$vet['log']     = 1;

		$file = 'faturamento_cancelado.txt';

		if ( ENV == 'testes' ) {
			$dir 	= 'entrada';
		} else {
			$dir 	= '/home/' . $vet['fabrica'] . '/' . $vet['fabrica'] . '-telecontrol';
		}

		$fp = fopen( $dir . '/' . $file, "r+" );

		if ( !is_resource($fp) ) {
			throw new Exception ('Arquivo ' . $dir . '/' . $file . ' nao Encontrado !');
		}

		$sql = "DROP TABLE IF EXISTS ".$vet['fabrica']."_cancelado;

				CREATE TABLE ".$vet['fabrica']."_cancelado 
				(cnpj text, txt_nota_fiscal text, txt_serie text);

				COPY ".$vet['fabrica']. "_cancelado FROM stdin";

		$res = pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);
		
		while (!feof($fp)) {
			//remove quebra de linha do arquivo, para nao dar problema no pg_put_line 
			$buffer[] = preg_replace( '/\n|\r/', '', fgets($fp) );
		        $msg_erro .= pg_errormessage($con);

	        }
		$i = 0;
		foreach($buffer as $linha) {
			// Verifica se nao eh a ultima linha e insere quebra de linha padrao do server
			if ( count($buffer) - ($i +1) > 0 ) {
				$linha .= PHP_EOL;
			}
			pg_put_line($con, $linha);
			$i++;
		}

	        if ( !empty($msg_erro) ) {
			throw new Exception($msg_erro);
		}

	        pg_put_line($con, "\\." . PHP_EOL);
		$msg_erro .= pg_errormessage($con);
  		pg_end_copy($con);
		$msg_erro .= pg_errormessage($con);

		if ( !empty($msg_erro) ) {
        		throw new Exception($msg_erro);
		}
	    
        fclose($fp);

        $sql = "UPDATE ".$vet['fabrica']."_cancelado SET 
			cnpj        	= trim (cnpj)       		,
			txt_nota_fiscal	= lpad(trim (txt_nota_fiscal),9,'0') 	,
			txt_serie    	= trim (txt_serie)   		;

			ALTER TABLE ".$vet['fabrica']."_cancelado ADD COLUMN faturamento INT4;
			ALTER TABLE ".$vet['fabrica']."_cancelado ADD COLUMN posto INT4;";

		$res = pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);
			
		if ( !empty($msg_erro) ) {
			throw new Exception($msg_erro);
		}
		
		$sql = "UPDATE ".$vet['fabrica']."_cancelado SET posto = tbl_posto.posto
				FROM   tbl_posto 
				JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $fabrica
				WHERE  ".$vet['fabrica']."_cancelado.cnpj = tbl_posto.cnpj";

		$res = pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);

		if ( !empty($msg_erro) ) {
			throw new Exception($msg_erro);
		}

		$sql = "UPDATE ".$vet['fabrica']."_cancelado SET faturamento = tbl_faturamento.faturamento
				FROM   tbl_faturamento
				WHERE ".$vet['fabrica']."_cancelado.txt_nota_fiscal = tbl_faturamento.nota_fiscal
				AND    ".$vet['fabrica']."_cancelado.txt_serie     = tbl_faturamento.serie
				AND   ".$vet['fabrica']."_cancelado.posto     = tbl_faturamento.posto
				AND   tbl_faturamento.fabrica = $fabrica";
		
		$res = pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);

		if ( !empty($msg_erro) ) {
			throw new Exception($msg_erro);
		}

		
		$sql = "DELETE FROM ".$vet['fabrica']."_cancelado WHERE cnpj IS NULL;

				DROP TABLE IF EXISTS ".$vet['fabrica']."_cancelado_sem_posto;

				SELECT * INTO ".$vet['fabrica']."_cancelado_sem_posto FROM ".$vet['fabrica']."_cancelado WHERE posto IS NULL;

				DELETE FROM ".$vet['fabrica']."_cancelado WHERE posto IS NULL;

				DROP TABLE IF EXISTS ".$vet['fabrica']."_cancelado_sem_faturamento;

				SELECT * INTO ".$vet['fabrica']."_cancelado_sem_faturamento FROM ".$vet['fabrica']."_cancelado WHERE faturamento IS NULL;

				DELETE FROM ".$vet['fabrica']."_cancelado WHERE faturamento IS NULL;
			";

		$res = pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);
		
		if ( !empty($msg_erro) ) {
			throw new Exception($msg_erro);
		}

		$sql = "DROP TABLE IF EXISTS ".$vet['fabrica']."_cancelado_peca;

				SELECT tbl_faturamento_item.peca, 
				SUM(tbl_faturamento_item.qtde) AS qtde, 
				tbl_faturamento.posto,
				tbl_faturamento.faturamento,
				tbl_faturamento.nota_fiscal 
				INTO ".$vet['fabrica']."_cancelado_peca 
				FROM tbl_faturamento_item 
				JOIN tbl_faturamento ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento AND tbl_faturamento.fabrica = $fabrica
				JOIN ".$vet['fabrica']."_cancelado ON tbl_faturamento_item.faturamento = ".$vet['fabrica']."_cancelado.faturamento 
				GROUP BY tbl_faturamento_item.peca,tbl_faturamento.faturamento,tbl_faturamento.posto,tbl_faturamento.nota_fiscal;
				";

		$res = pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);

		if ( !empty($msg_erro) ) {
			throw new Exception($msg_erro);
		}

		pg_query($con,"BEGIN");

		$data_cancelamento = date('d/m/Y');
		$sql = "UPDATE tbl_pedido_item SET 
						qtde_faturada = 0,
						obs = ".$vet['fabrica']."_cancelado.txt_nota_fiscal || '|' || tbl_peca.referencia || '-' || tbl_peca.descricao || '|' || '$data_cancelamento'
			FROM tbl_faturamento_item, ".$vet['fabrica']."_cancelado,tbl_peca
			WHERE tbl_faturamento_item.faturamento = ".$vet['fabrica']."_cancelado.faturamento
			AND tbl_pedido_item.pedido = tbl_faturamento_item.pedido
			AND tbl_pedido_item.pedido_item = tbl_faturamento_item.pedido_item
			AND tbl_pedido_item.peca = tbl_peca.peca";
		$res = pg_query($con,$sql);

		$sql = "UPDATE tbl_pedido set status_pedido = 2  
				FROM ".$vet['fabrica']."_cancelado
				JOIN tbl_faturamento_item ON tbl_faturamento_item.faturamento = ".$vet['fabrica']."_cancelado.faturamento
				WHERE tbl_pedido.pedido = tbl_faturamento_item.pedido ";

		$msg_erro = pg_errormessage($con);

                if ( !empty($msg_erro) ) {
                        throw new Exception($msg_erro);
                }
		
		$sql = "SELECT fn_atualiza_status_pedido($fabrica,tbl_faturamento_item.pedido)
			FROM tbl_faturamento_item
			JOIN atlas_cancelado_peca ON tbl_faturamento_item.faturamento = atlas_cancelado_peca.faturamento";
		$res = pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);
		 
		if ( !empty($msg_erro) ) {
		     throw new Exception($msg_erro);
		}

		$sql = "DELETE FROM tbl_faturamento_item USING ".$vet['fabrica']."_cancelado  
				WHERE tbl_faturamento_item.faturamento = ".$vet['fabrica']."_cancelado.faturamento";

		$res = pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);

		if ( !empty($msg_erro) ) {
			throw new Exception($msg_erro);
		}

		$sql = "UPDATE tbl_faturamento set fabrica = 0 from ".$vet['fabrica']."_cancelado 
			WHERE tbl_faturamento.faturamento = ".$vet['fabrica']."_cancelado.faturamento";

		$res = pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);

		if ( !empty($msg_erro) ) {
			throw new Exception($msg_erro);
		}

		$sql = "INSERT INTO tbl_estoque_posto_movimento(fabrica,posto,peca,data,qtde_saida,faturamento_devolucao,obs,nf)
			SELECT $fabrica, posto, peca, current_date, qtde, faturamento, 'Faturamento Cancelado',nota_fiscal
			FROM ".$vet['fabrica']."_cancelado_peca";

		$res = pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);

		if ( !empty($msg_erro) ) {
			throw new Exception($msg_erro);
		}
		
		$sql = "UPDATE tbl_estoque_posto SET qtde = (tbl_estoque_posto.qtde - ".$vet['fabrica']."_cancelado_peca.qtde)
				FROM ".$vet['fabrica']."_cancelado_peca
				WHERE tbl_estoque_posto.fabrica = $fabrica
				AND tbl_estoque_posto.posto = ".$vet['fabrica']."_cancelado_peca.posto
				AND tbl_estoque_posto.peca = ".$vet['fabrica']."_cancelado_peca.peca";

		$res = pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);

		
		if ( !empty($msg_erro) ) {
			pg_query($con,"ROLLBACK");
			throw new Exception($msg_erro);
		}

		pg_query($con,"COMMIT");
		Log::log2($vet, APP . ' - Executado com Sucesso - ' . date('d-m-Y H:i:s') . PHP_EOL);

		system ("mv $dir/$file /tmp/".$vet['fabrica']."/telecontrol-faturamento-cancelado-$data.txt");
		
		$phpCron->termino();

	}
	catch (Exception $e) {

		$msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
		Log::envia_email($vet,APP, $msg );

	}
