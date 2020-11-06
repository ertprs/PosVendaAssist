<?php

error_reporting(E_ALL);

try {

            include dirname(__FILE__) . '/../../dbconfig.php';
            include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
            require dirname(__FILE__) . '/../funcoes.php';
            include dirname(__FILE__) . '/../../classes/Posvenda/Extrato.php';
            include dirname(__FILE__) .'/../../os_cadastro_unico/fabricas/154/Extrato.php';
	/*
	* Definições
	*/
	$fabrica 		= 154;
	$dia_mes     	= date('d');
	$dia_extrato 	= date('Y-m-d H:i:s');

	#$dia_mes     = "27";
	#$dia_extrato = "2014-08-27 23:59:00";

	/*
	* Cron Class
	*/
	$phpCron = new PHPCron($fabrica, __FILE__);
	$phpCron->inicio();

	/*
	* Log Class
	*/
            $logClass = new Log2();
            $logClass->adicionaLog(array("titulo" => "Log erro Geração de Extrato - Rheem")); // Titulo
            $logClass->adicionaEmail("helpdesk@telecontrol.com.br");
	// $logClass->adicionaEmail("william.lopes@telecontrol.com.br");

	/*
	* Extrato Class
	*/
            $classExtrato = new Extrato($fabrica);

            /*
            * Resgata a quantidade de OS por Posto
            */
            $os_posto = $classExtrato->getOsPosto($dia_extrato, $fabrica);

            if(empty($os_posto)){
                exit;
            }

            /*
            * Mensagem de Erro
            */
            $msg_erro = "";
            $msg_erro_arq = "";

	for ($i = 0; $i < count($os_posto); $i++) {

		$posto 			= $os_posto[$i]["posto"];
		$nome 			= $os_posto[$i]["nome"];
		$codigo_posto 	= $os_posto[$i]["codigo_posto"];
		$qtde  			= $os_posto[$i]["qtde"];

                          $sqlPostoDistrib = "SELECT
                                                                    tbl_tipo_posto.tipo_posto
                                                            FROM tbl_posto_fabrica
                                                            INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
                                                                                                        AND tbl_tipo_posto.fabrica = {$fabrica}
                                                            WHERE tbl_posto_fabrica.fabrica = {$fabrica}
                                                            AND tbl_posto_fabrica.posto = {$posto}
                                                            AND tbl_tipo_posto.distribuidor IS TRUE;";

                          $resPostoDistrib = pg_query($con, $sqlPostoDistrib);

                          if (pg_num_rows($resPostoDistrib) > 0)
                                        continue;

		try {
                                       /*
                                       * Begin
                                       */
                                       $classExtrato->_model->getPDO()->beginTransaction();

                                       /*
                                       * Insere o Extrato para o Posto
                                       */
                                       $classExtrato->insereExtratoPosto($fabrica, $posto, $dia_extrato, $mao_de_obra = 0, $pecas = 0, $total = 0, $avulso = 0);

                                       /*
                                       * Resgata o numero do Extrato
                                       */
                                       $extrato = $classExtrato->getExtrato();

                                       /*
                                       * Insere lançamentos avulsos para o Posto
                                       */
                                       $classExtrato->atualizaAvulsosPosto($fabrica, $posto, $extrato);

                                       /*
                                       * Relaciona as OSs com o Extrato
                                       */
                                       $classExtrato->relacionaExtratoOS($fabrica, $posto, $extrato, $dia_extrato);

                                       $classRegrasExtratro = new RegrasExtrato($classExtrato, $fabrica);

                                       $status_regra = $classRegrasExtratro->zeraKm($extrato);

                                       /*
                                       * Atualiza os valores avulso dos postos
                                       */
                                       $classExtrato->atualizaValoresAvulsos($fabrica,$extrato);

                                       /*
                                       * Calcula o Extrato
                                       */
                                       $total_extrato = $classExtrato->calcula($extrato);

                                       /*
                                       * Commit
                                       */
                                       $classExtrato->_model->getPDO()->commit();

		} catch (Exception $e){

                                       $msg_erro .= $e->getMessage()."<br />";
                                       $msg_erro_arq .= $msg_erro . " - SQL: " . $classExtrato->getErro();

                                       /*
                                       * Rollback
                                       */
                                       $classExtrato->_model->getPDO()->rollBack();

		}

	}

	/*
	* Erro
	*/
	if(!empty($msg_erro)){

                          $logClass->adicionaLog($msg_erro);

                          if($logClass->enviaEmails() == "200"){
                                       echo "Log de erro enviado com Sucesso!";
                          }else{
                                       echo $logClass->enviaEmails();
                          }

                        $fp = fopen("tmp/{$fabrica_nome}/pedidos/log-erro.text", "a");
                        fwrite($fp, "Data Log: " . date("d/m/Y") . "\n");
                        fwrite($fp, $msg_erro_arq . "\n \n");
                        fclose($fp);

            }

	/*
	* Cron Término
	*/
	$phpCron->termino();

} catch (Exception $e) {
	echo $e->getMessage();
}

