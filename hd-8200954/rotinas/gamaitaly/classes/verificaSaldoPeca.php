<?php

	/* CONSULTA DE SALDO DE PEÇA GAMA ITALY */
    $servidor = $_SERVER['SERVER_NAME'];
    $pos = strpos($servidor, 'devel');
   
    if($pos === false){
        $url = "http://189.56.10.34:8082/03/GAMA_CSLD.apw?WSDL";
    }else{
        $url = "http://189.56.10.34:8081/03/GAMA_CSLD.apw?WSDL";
    }

    class SaldoException extends \Exception {}
   
    class verificaSaldoPeca {

		private $_fabrica;

		public function __construct($fabrica){

			if(empty($fabrica)){
				return "ERRO: Não foi possível criar a classe de Consulta de Saldo de Peça.";
			}

			$this->_fabrica = $fabrica;

		    
		}

		public function retornaSaldo($codPeca, $postoInterno, $os = null){

            /*

            $postoInterno - true | false.
            A Gama Italy irá criar um retorno para o posto interno.

            */
			try{
				global $url;

				$armz = ($postoInterno) ? 12 : 19;

				$soap = new SoapClient($url, array("trace" => 1, "exception" => 1));
				$metodo = "CSLD";
				$options = array('location' => 'http://189.56.10.34:8082/03/GAMA_CSLD.apw');
				$peca['STRUCTMAT']['STRCODMAT'] = array('STRSNDMAT'=> array('CODMATERIAL'=>"{$codPeca}",'ARMZ'=>"{$armz}"));

				$peca = array($peca);

				$soapResult = $soap->__soapCall($metodo, $peca,$options);
				$retorno = (array) $soapResult->CSLDRESULT;
				$retorno = (array) $retorno['STRCSLD'];
				$retorno['QTDT'] = (empty($retorno['QTDT'])) ? 0 : $retorno['QTDT'];

				$info[$os]["request"]  = json_encode($peca);
				$info[$os]["response"] = json_encode($retorno);

				$this->gravaLog($info);

				return $retorno['QTDT'];
			}catch(\Exception $e){
				echo $e->getMessage(); 
				throw new SaldoException($e->getMessage());
			}

		}

        public function retornaAuditoriaOsPecas($os){

            include dirname(__FILE__) . '/../../../dbconfig.php';
            include dirname(__FILE__) . '/../../../includes/dbconnect-inc.php';

            $sql_pecas = "SELECT 
                            tbl_os_item.os_item, 
                            tbl_os_item.servico_realizado, 
                            tbl_os_item.qtde,
                            tbl_peca.peca,
                            (SELECT posto FROM tbl_os WHERE os = {$os}) AS posto,
							tbl_peca.referencia AS referencia_peca,
							tbl_os_item.parametros_adicionais	
                        FROM tbl_os_item 
                        INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca 
                        WHERE 
                            tbl_os_item.os_produto IN (SELECT os_produto FROM tbl_os_produto WHERE os = {$os}) 
                            AND tbl_os_item.fabrica_i = {$this->_fabrica}";
            $res_pecas = pg_query($con, $sql_pecas);
            $cont_pecas = pg_num_rows($res_pecas);

            $auditoria_status        = 4;
            $status_peca_sem_estoque = false;
            $pecas_sem_estoque       = array();

            for ($i = 0; $i < $cont_pecas; $i++) {

				$os_item           = pg_fetch_result($res_pecas, $i, "os_item");
				$servico_realizado = pg_fetch_result($res_pecas, $i, "servico_realizado");
				$qtde_peca         = pg_fetch_result($res_pecas, $i, "qtde");
				$peca_id           = pg_fetch_result($res_pecas, $i, "peca");
				$referencia_peca   = pg_fetch_result($res_pecas, $i, "referencia_peca");
				$posto             = pg_fetch_result($res_pecas, $i, "posto");
				$parametros_adicionais     = pg_fetch_result($res_pecas, $i, "parametros_adicionais");

				$sql_tp = "SELECT 
					posto 
					FROM tbl_posto_fabrica 
					WHERE 
					fabrica = {$this->_fabrica} 
					AND posto = {$posto} 
					AND tipo_posto IN (SELECT tipo_posto FROM tbl_tipo_posto WHERE fabrica = {$this->_fabrica} AND posto_interno IS TRUE)";

				$res_tp = pg_query($con, $sql_tp);

				$posto_interno = (pg_num_rows($res_tp) > 0) ? true : false;
				$sql_sr = "SELECT servico_realizado FROM tbl_servico_realizado WHERE servico_realizado = {$servico_realizado} AND fabrica = {$this->_fabrica} AND troca_de_peca IS TRUE";
				$res_sr = pg_query($con, $sql_sr);

				if(pg_num_rows($res_sr) == 1){  

					try{
						$qtde_estoque = $this->retornaSaldo($referencia_peca, $posto_interno, $os);
						if(empty($parametros_adicionais)) {
							$sqlu = "UPDATE tbl_os_item set parametros_adicionais = '{\"qtde_estoque\":\"$qtde_estoque\"}' where os_item = $os_item ";
							$resu = pg_query($con,$sqlu);
						}else{
							$parametros_adicionais = json_decode($parametros_adicionais,true);
							$parametros_adicionais["qtde_estoque"] = $qtde_estoque;
							$parametros_adicionais = json_encode($parametros_adicionais);
							$sqlu = "UPDATE tbl_os_item set parametros_adicionais = '{$parametros_adicionais}' where os_item = $os_item ";
							$resu = pg_query($con,$sqlu);
						}
					}catch(SaldoException $e){
						$pecas_sem_estoque[] = $referencia_peca;
						$status_peca_sem_estoque = true; 	
					}

					if($qtde_estoque < $qtde_peca or empty($qtde_estoque)){

						if(!in_array($referencia_peca, $pecas_sem_estoque )){
							$pecas_sem_estoque[] = $referencia_peca;
						}
						$status_peca_sem_estoque = true;

					}

				}

            }
            if($status_peca_sem_estoque == true){

                $pecas_sem_estoque = implode(", ", $pecas_sem_estoque);

                $sql_aud = "INSERT INTO tbl_auditoria_os 
                                (os, auditoria_status, observacao, bloqueio_pedido) 
                            VALUES 
                                ({$os}, $auditoria_status, 'Peça(s) sem estoque suficiente: {$pecas_sem_estoque}.', true)"; 
                $res_aud = pg_query($con, $sql_aud);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao lançar ordem de serviço");
                }

                return false;

            }else{

                return true;

            }

	}

	
	public function gravaLog($info){
		
		$arq = "/www/assist/www/rotinas/gamaitaly/classes/logs/log-consulta-saldo-".date("Y-m-d").".txt";

		$fp = fopen($arq,"a+");
		$info = json_encode($info)."\n\n";
		fwrite($fp,$info);

		fclose($fp);
	}

}
?>
