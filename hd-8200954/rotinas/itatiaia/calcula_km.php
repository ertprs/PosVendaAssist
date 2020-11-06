<?php
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';
use Posvenda\TcMaps;

try {

    /*
    * Definições
    */
    $fabrica        = 183;
    $nome_fabrica   = "Itatiaia";
    $classTcMaps    = new TcMaps($fabrica);
    $ambiente       = "PROD";

    if ($ambiente == "DEV") {
        $url_websevice = "http://webservices.vermont.com.br/itatiaiaws_QA/itatiaia/wsItatiaia.asmx?wsdl";
    } else {
        $url_websevice = "http://webservices.vermont.com.br/itatiaiaws_prod/itatiaia/wsItatiaia.asmx?wsdl";
    }

    /*
    * Cron Class
    */
    $phpCron = new PHPCron($fabrica, __FILE__);
    $phpCron->inicio();

    /*
    * Log Class
    */
    $logClass = new Log2();
    $logClass->adicionaLog(array("titulo" => "Log erro Calculo de Km Itatiaia")); // Titulo
    //$logClass->adicionaEmail("helpdesk@telecontrol.com.br");
    $logClass->adicionaEmail("felipe.marttos@telecontrol.com.br");
    $logClass->adicionaEmail("luis.carlos@telecontrol.com.br");

   

    $sql = "SELECT * FROM tbl_os_itatiaia WHERE  km_data_calculo IS NULL LIMIT 50";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {

        foreach (pg_fetch_all($res) as $key => $rows) {
            $os = $rows['os'];
            $dados = json_decode($rows['dados'],1);
//echo "$os\n";

            $endereco_posto     = $dados['endereco_posto'];
            $cidade_posto       = $dados['cidade_posto'];
            $uf_posto           = $dados['uf_posto'];
            $cep_posto          = $dados['cep_posto'];
            $endereco_cliente   = $dados['endereco_cliente'];
            $n_cliente          = $dados['n_cliente'];
            $uf_cliente         = $dados['uf_cliente'];
            $cep_cliente        = $dados['cep_cliente'];
            $bairro_cliente     = $dados['bairro_cliente'];
            $cidade_cliente     = utf8_decode($dados['cidade_cliente']);

            $latLonPosto    = $classTcMaps->geocode($endereco_posto, '', '', $cidade_posto, $uf_posto, 'Brasil', $cep_posto);
    		$latLonCliente  = $classTcMaps->geocode($endereco_cliente, $n_cliente, $bairro_cliente, $cidade_cliente, $uf_cliente, 'Brasil', $cep_cliente);

            $distanciaIda   = $classTcMaps->route($latLonCliente["latlon"], $latLonPosto["latlon"]);
            $distanciaVolta = $classTcMaps->route($latLonPosto["latlon"], $latLonCliente["latlon"]);
            $totalKMIdaEVolta = round($distanciaIda["total_km"]+$distanciaVolta["total_km"]);

            //envia o km para api deles
            $client = new SoapClient($url_websevice, array(
                'trace'         => 1,
                'exceptions'    => 0,
            ));
            //$array_params = array("atualizaKm" => array("codigoOs" => '27422', "quantidadeKm" => '6'));
            $array_params = array("atualizaKm" => array("codigoOs" => $os, "quantidadeKm" => $totalKMIdaEVolta));
            $resultado = $client->AtualizarKm($array_params);


            echo "-- >> os << ".$os."\n";
            echo "-- >> 1 << ".$latLonCliente["latlon"]."\n";
            echo "-- >> 1 << ".$latLonPosto["latlon"]."\n";
            echo "-- >> 1 << ".$endereco_cliente."\n";
            echo "-- >> 1 << ".$cidade_cliente."\n";
            echo "-- >> 1 << ".$endereco_posto."\n";
            echo "-- >> 1 << ".$cidade_posto."\n";
            echo "-- >> 1 << ".$totalKMIdaEVolta."\n";
            echo "-- >> 1 << ".$resultado->AtualizarKmResult."\n";
            echo "-- ----------------------------\n";
	    var_dump($resultado);

	    $sql = "UPDATE tbl_os_itatiaia 
                    SET km_data_calculo = '".date("Y-m-d H:i:s")."', 
                    km_qtde_calculada = '{$totalKMIdaEVolta}' 
		    WHERE os = '{$os}'";

            if (substr($resultado->AtualizarKmResult, 0, 2) == "00") {
                //atualiza a tabela local data de geracao
                $res = pg_query($con, $sql);
                if (pg_last_error()) {
                    $msg_erro[] = ["erro" => pg_last_error(), "os" => $os]; 
                } else {
                    $msg_success[] = ["sucesso" => "Km Atualizado com sucesso", "os" => $os, "total_KM_Ida_Volta" => $totalKMIdaEVolta]; 
                }
            } else {
                if (empty($resultado->AtualizarKmResult)) {
                    $msg_erro[] = ["erro" => "Erro interno no webservice da Itatiaia", "os" => $os]; 
                } else {
			$msg_erro[] = ["erro" => $resultado->AtualizarKmResult, "os" => $os]; 

			 if (substr($resultado->AtualizarKmResult, 0, 2) == "02") {
				$res = pg_query($con, $sql);
			 }
                }
	    }

	    print_r($msg_erro);
        }
    }


    /*
    * Erro
    */
    if(count($msg_erro) > 0){

        $mensagem = "";

        foreach ($msg_erro as $key => $value) {
            $mensagem .= 'Erro: '.$value["erro"];
            $mensagem .= 'OS: '.$value["os"]."<br>";
        }
        $logClass->adicionaLog($mensagem);
        if($logClass->enviaEmails() == "200"){
          echo "Log de erro enviado com Sucesso!";
        }else{
          echo $logClass->enviaEmails();
        }

    }

    /*
    * Sucesso
    */
    if(count($msg_success) > 0){

        $mensagem = "";
        $logClass->adicionaLog(array("titulo" => "Log Sucesso Calculo de Km Itatiaia")); // Titulo

        foreach ($msg_success as $key => $value) {
            $mensagem .= 'Sucesso: '.$value["sucesso"]."<br>";
            $mensagem .= 'OS: '.$value["os"]."<br>";
            $mensagem .= 'Total KM: '.$value["total_KM_Ida_Volta"]."<br>";
            $mensagem .= "----------------------------------------------<br>";
        }
        $logClass->adicionaLog($mensagem);
        if($logClass->enviaEmails() == "200"){
          echo "Log de sucesso enviado!";
        }else{
          echo $logClass->enviaEmails();
        }

    }

    /*
    * Cron Término
    */
    $phpCron->termino();

} catch (Exception $e) {
    echo $e->getMessage();
}

