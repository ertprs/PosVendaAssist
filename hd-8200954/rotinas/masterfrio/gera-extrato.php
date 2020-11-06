<?php  

define('ENV', 'producao');

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';
	require dirname(__FILE__) . "/../calcula_extrato_excecao_mobra.php";

	$msg_erro = array();
	$bug         = '';
	$fabrica     = 40;
	$dia_mes     = date('d');
	#$dia_mes     = "28";
	$dia_extrato = date('Y-m-d H:i:s');
	#$dia_extrato = "2012-07-28 02:00:00";
	$vet['fabrica'] = 'masterfrio';
	$vet['tipo']    = 'extrato';
	
	$phpCron = new PHPCron($fabrica, __FILE__); 
	$phpCron->inicio();
	
	if (ENV == 'testes') {
		$vet['dest'] = 'gabriel.silveira@telecontrol.com.br';
	} else {
		$vet['dest'] = 'helpdesk@telecontrol.com.br';
	}
	$vet['log']     = 2;

	$sql = "SELECT  posto, COUNT(*) AS qtde
			FROM tbl_os
			JOIN tbl_os_extra USING (os)
			WHERE tbl_os.fabrica = $fabrica
			AND   tbl_os_extra.extrato IS NULL
			AND   tbl_os.excluida      IS NOT TRUE
			AND   tbl_os.finalizada    < '$dia_extrato'
			AND   tbl_os.finalizada::date < current_date
			AND   tbl_os.posto <> 6359
			GROUP BY posto
			ORDER BY posto ";
	
	$resPostos = pg_query($con,$sql);

	if (pg_num_rows($resPostos) > 0) {
		
		foreach (pg_fetch_all($resPostos) as $key) {
			
			$posto = $key['posto'];
			$qtde = $key['qtde'];

			$res = pg_query($con,"BEGIN TRANSACTION");

			#Cria um extrato para o posto
			$sql = "INSERT INTO tbl_extrato (posto, fabrica, avulso, total) VALUES ($posto,$fabrica, 0, 0) returning extrato";
			$res_extrato = pg_query($con,$sql);
			if (strlen(pg_last_error($con))>0) {
				$msg_erro[] = pg_last_error($con);
			}else{
				$extrato = pg_fetch_result($res_extrato, 0, 'extrato');
			}
			
			if (count($msg_erro)==0 and !empty($extrato)) {

				$sql = "UPDATE tbl_extrato_lancamento SET extrato = $extrato
						WHERE tbl_extrato_lancamento.fabrica = $fabrica
						AND   tbl_extrato_lancamento.extrato IS NULL
						AND   tbl_extrato_lancamento.posto = $posto; ";
				$res = pg_query($con,$sql);

				if (strlen(pg_last_error($con))>0) {
					$msg_erro[] = pg_last_error($con);
				}

				#Seta o número do extrato em que as OS pertencem.
				$sql = "UPDATE tbl_os_extra SET extrato = $extrato
						FROM  tbl_os
						WHERE tbl_os.posto   = $posto
						AND   tbl_os.fabrica = $fabrica
						AND   tbl_os.os      = tbl_os_extra.os
						AND   tbl_os_extra.extrato IS NULL
						AND   tbl_os.excluida      IS NOT TRUE
						AND   tbl_os.finalizada    < '$dia_extrato' 
						";

				#print $sql;
				$res = pg_query($con,$sql);

				if (strlen(pg_last_error($con))>0) {
					$msg_erro[] = pg_last_error($con);
				}

				#Aqui é feito o cálculo de mão de obra e de peças do extrato em si
				$sql = "SELECT fn_calcula_extrato ($fabrica,$extrato)";
				$res = pg_query($con,$sql);

				if (strlen(pg_last_error($con))>0) {
					$msg_erro[] = pg_last_error($con);
				}

				//instanciando o objeto (cem "Calculo Excecao Mobra") 
				$cem = new ExcecaoMobra($extrato,$fabrica);
				$cem->calculaExcecaoMobra();

				$erros_cem = $cem->getErros();

				if (count($erros_cem)>0) {
					$msg_erro[] = $cem->getErros();
				}


				$sql = "SELECT ('$dia_extrato'::date - INTERVAL '1 month' + INTERVAL '14 days')::date";
				$res = pg_query($con,$sql);
				$data_15 = pg_fetch_result($res, 0, 0);

				$sql = "UPDATE tbl_faturamento
							SET extrato_devolucao = tbl_extrato.extrato
						FROM   tbl_extrato
						WHERE  tbl_faturamento.fabrica = $fabrica
						AND    tbl_extrato.extrato     = $extrato
						AND    tbl_faturamento.posto   = tbl_extrato.posto
						AND    tbl_faturamento.fabrica = tbl_extrato.fabrica
						AND    tbl_faturamento.emissao <  '$data_15'
						AND    tbl_faturamento.extrato_devolucao IS NULL
						AND    (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%');";

				$res = pg_query($con,$sql);

				if (strlen(pg_last_error($con))>0) {
					$msg_erro[] = pg_last_error($con);
				}

				$sql = "INSERT INTO tbl_extrato_lgr (extrato, posto, peca, qtde)
				(	SELECT tbl_extrato.extrato, tbl_extrato.posto, tbl_faturamento_item.peca, SUM (tbl_faturamento_item.qtde)
					FROM tbl_extrato
					JOIN tbl_faturamento ON tbl_extrato.extrato = tbl_faturamento.extrato_devolucao
					JOIN tbl_faturamento_item ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
					WHERE tbl_extrato.fabrica = $fabrica
					AND   tbl_extrato.extrato = $extrato
					AND   tbl_faturamento.extrato_devolucao = $extrato
					GROUP BY tbl_extrato.extrato,
							 tbl_extrato.posto,
							 tbl_faturamento_item.peca
				);";
				
				$res = pg_query($con,$sql);

				if (strlen(pg_last_error($con))>0) {
					$msg_erro[] = pg_last_error($con);
				}

				#totaliza o extrato novamente
				$sql = "UPDATE tbl_extrato
							SET avulso = (
								SELECT SUM (valor)
								FROM tbl_extrato_lancamento
								WHERE tbl_extrato_lancamento.extrato = tbl_extrato.extrato
							)
						WHERE tbl_extrato.fabrica = $fabrica
						AND tbl_extrato.data_geracao = CURRENT_DATE
						;
						UPDATE tbl_extrato
							SET total = mao_de_obra + case when avulso isnull then 0 else avulso end 
						WHERE tbl_extrato.fabrica = $fabrica
						AND tbl_extrato.data_geracao = CURRENT_DATE;";

				$res = pg_query($con,$sql);

				if (strlen(pg_last_error($con))>0) {
					$msg_erro[] = pg_last_error($con);
				}

			}

			if (!empty($msg_erro)>0) {
				print_r($msg_erro);
				$res = pg_query($con,"ROLLBACK TRANSACTION");
				$erros .= implode("<br>", $msg_erro);
				unset($msg_erro);
				
			}else{
				$res = pg_query($con,"COMMIT TRANSACTION");
			}

		}

	}
	
	if (!empty($erros)) {
	 	Log::log2($vet, $erros);
	 	Log::envia_email($vet, "Log - Geração de geração de extrado MASTERFRIO", $erros);
	 }
	 
	 $phpCron->termino();

} catch (Exception $e) {
	echo $e->getMessage();
}
