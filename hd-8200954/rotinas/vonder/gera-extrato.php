<?php
	
	define('APP','Gera Extrato  - Vonder'); // Nome da rotina, para ser enviado por e-mail
	define('ENV','producao'); // Alterar para produção ou algo assim

	try {

		include dirname(__FILE__) . '/../../dbconfig.php';
		include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
		require dirname(__FILE__) . '/../funcoes.php';

		$fabrica     = 104;
		$data 		 = date('d-m-Y');

		$vet['fabrica'] = 'vonder';
		$vet['tipo']    = 'extrato';
		$vet['dest']    = 'helpdesk@telecontrol.com.br';
		$vet['log']     = 1;
		
		$dir = "/tmp/vonder";
		$file = 'extrato_erro.txt';

		#Gera Extrato Marca DWT
		$sql = "SELECT DISTINCT posto, current_date as data_limite
					FROM tbl_os 
					JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os AND tbl_os.fabrica = tbl_os_extra.i_fabrica 
					JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto AND tbl_produto.fabrica_i = $fabrica
					JOIN tbl_marca ON tbl_produto.marca = tbl_marca.marca AND tbl_marca.fabrica = $fabrica
					WHERE tbl_os.fabrica = $fabrica
					AND   tbl_os_extra.extrato IS NULL
					AND   tbl_os.excluida      IS NOT TRUE
					AND   tbl_os.finalizada::date    <=  CURRENT_DATE 
					AND   tbl_os.posto <> 6359
					ORDER BY posto";
		$res = pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);
			
		if ( !empty($msg_erro) ) {
			throw new Exception($msg_erro);
		}

		$total = pg_num_rows($res);

		for($i = 0; $i < $total; $i++){
			$posto		 = pg_result($res,$i,'posto');
			$data_limite = pg_result($res,$i,'data_limite');

			$resB = pg_query($con,"BEGIN TRANSACTION");

			$sql_extrato = "SELECT fn_fechamento_extrato ($posto, $fabrica, '$data_limite');";
			$res_extrato = pg_query($con,$sql_extrato);
			$msg_erro = pg_errormessage($con);
				
			if(pg_num_rows($res_extrato) > 0)
				$extrato = pg_result($res_extrato,0,0);

			if(strlen($extrato) > 0){
				$sql_extrato = "SELECT fn_calcula_extrato($fabrica, $extrato);";
				$res_extrato = pg_query($con,$sql_extrato);
				$msg_erro = pg_errormessage($con);

				$sql_libera = "UPDATE tbl_extrato
								SET aprovado = CURRENT_TIMESTAMP,
								liberado = CURRENT_DATE
								WHERE extrato = $extrato
								AND fabrica = $fabrica";

				$res_libera = pg_query($con,$sql_libera);
				$msg_erro = pg_errormessage($con);

			}
	
			if ( !empty($msg_erro) ) {
				pg_query($con,"ROLLBACK TRANSACTION");
				$fp = fopen( $dir . '/' . $file, "a" );
				fputs ($fp,$msg_erro);
				fclose ($fp);
				$msg .= $conteudo;

			} else {
				pg_query($con,"COMMIT TRANSACTION");
				Log::log2($vet, APP . ' - Executado com Sucesso - ' . date('d-m-Y H:i:s') . PHP_EOL);
			}

		}

		#Gera Extrato Marca Vonder

		for($i = 0; $i < $total; $i++){
			$posto		 = pg_result($res,$i,'posto');
			$data_limite = pg_result($res,$i,'data_limite');

			$resB = pg_query($con,"BEGIN TRANSACTION");
			$sql_extrato = "SELECT fn_fechamento_extrato ($posto, $fabrica, '$data_limite');";
			$res_extrato = pg_query($con,$sql_extrato);
			$msg_erro = pg_errormessage($con);

			if(pg_num_rows($res_extrato) > 0)
				$extrato = pg_result($res_extrato,0,0);

			if(strlen($extrato) > 0){
				$sql_extrato = "SELECT fn_calcula_extrato($fabrica, $extrato);";
				$res_extrato = pg_query($con,$sql_extrato);
				$msg_erro = pg_errormessage($con);

				$sql_libera = "UPDATE tbl_extrato
								SET aprovado = CURRENT_TIMESTAMP,
								liberado = CURRENT_DATE
								WHERE extrato = $extrato
								AND fabrica = $fabrica";

			$res_libera = pg_query($con,$sql_libera);
			$msg_erro = pg_errormessage($con);
			}

			if ( !empty($msg_erro) ) {
				pg_query($con,"ROLLBACK TRANSACTION");
				$fp = fopen( $dir . '/' . $file, "a" );
				fputs ($fp,$msg_erro);
				fclose ($fp);
				$msg .= $conteudo;

			} else {
				pg_query($con,"COMMIT TRANSACTION");
				Log::log2($vet, APP . ' - Executado com Sucesso - ' . date('d-m-Y H:i:s') . PHP_EOL);
			}


		}

	}
	catch (Exception $e) {
	echo  $e->getMessage();	
		$msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
		Log::envia_email($vet,APP, $msg );

	}
