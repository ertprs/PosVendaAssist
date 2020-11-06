<?php
    
    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

    if ($_serverEnvironment == "production") {
            define("ENV", "prod");
    } else {
            define("ENV", "dev");
    }

    $login_fabrica = 153;

    /* Inicio Processo */
    $phpCron = new PHPCron($login_fabrica, __FILE__);
    $phpCron->inicio();
   
    $msg_erro      = array();
    $tipo          = 'A';
    
    if (ENV == "prod") {
        try {
            $soap = new SoapClient("http://webservicescol.correios.com.br/ScolWeb/WebServiceScol?wsdl", array("trace" => 1, "exception" => 1));
        } catch (Exception $e) {
            $response[] = array("resultado" => "false", "mensagem" => "ERRO AO CONECTAR SERVIDOR DOS CORREIOS");

            require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

            $assunto = utf8_decode('Webservice - Correios');

            $mail = new PHPMailer();
            $mail->IsHTML(true);
            $mail->From = 'suporte@telecontrol.com.br';
            $mail->FromName = 'Telecontrol';
            $mail->AddAddress('suporte@telecontrol.com.br');
            

            $mail->Subject = $assunto;
            $mail->Body = "ERRO AO CONECTAR SERVIDOR DOS CORREIOS...<br/> Fabrica: Positron <br/> Data: ".date('d/m/Y H:i:s')." <br/>";
            $mail->Send();
                    
            return $response;
        }
    } else {
        $soap = new SoapClient("http://webservicescol.correios.com.br/ScolWeb/WebServiceScol?wsdl", array("trace" => 1, "exception" => 1));
    }

    //pega senha da Fabrica
    $sqlAcesso = "SELECT  usuario,
        senha,
        codigo as codAdministrativo,
        contrato,
        cartao
      FROM tbl_fabrica_correios
      WHERE fabrica = $login_fabrica";
    $resAcesso = pg_query($sqlAcesso);
    if (pg_num_rows($resAcesso)>0) {
        $dados_acesso = pg_fetch_array($resAcesso);
    }

    $sql_protocolos = "SELECT distinct numero_postagem 
                        from tbl_faturamento_correio 
                        where fabrica = $login_fabrica 
                        and data >= CURRENT_DATE - interval '3 months'";
    $res_protocolos = pg_query($con, $sql_protocolos);

    for($i=0; $i<pg_num_rows($res_protocolos); $i++){
        $numero_postagem = pg_fetch_result($res_protocolos, $i, numero_postagem);

        $array_request =  (object) array('usuario'=>$dados_acesso['usuario'],
            'senha'=> $dados_acesso['senha'],
            'codAdministrativo'=> $dados_acesso['codadministrativo'],
            'tipoBusca'=>'H',
            'numeroPedido'=>$numero_postagem,
            'tipoSolicitacao'=> $tipo
        );
        
        $result = $soap->__soapCall('acompanharPedido', array($array_request));

        $coleta             = $result->return->coleta;
        $numeroPedido       = $result->return->coleta->numero_pedido;
        $controle_cliente   = $result->return->coleta->controle_cliente;
        $historico          = $result->return->coleta->historico;
        $conhecimento       = $result->return->coleta->objeto->numero_etiqueta;
        $data               = $result->return->coleta->objeto->data_ultima_atualizacao . " ". substr($result->return->coleta->objeto->hora_ultima_atualizacao, 0 , -3);
        $obs                = $result->return->coleta->objeto->descricao_status;
        $status             = $result->return->coleta->objeto->ultimo_status;

        if(strlen(trim($conhecimento))>0){
            $sql_upd = "UPDATE tbl_faturamento_correio SET conhecimento = '$conhecimento' WHERE numero_postagem = '$numero_postagem' AND conhecimento is null and fabrica = $login_fabrica";
            $res_upd = pg_query($con, $sql_upd);
        }
    }


//Adiciona na tbl_faturamento_correio os Rastreio que estão na tbl_etiqueta_serviço
    $sql_conhecimento_etiqueta = "INSERT INTO tbl_faturamento_correio (fabrica,local,situacao,data,numero_postagem, conhecimento) 
        select 153, 'Adicionado Rotina', 'Adicionado Rotina', tbl_etiqueta_servico.data_input, 999, tbl_etiqueta_servico.etiqueta
        from tbl_etiqueta_servico 
        INNER JOIN tbl_embarque ON tbl_embarque.embarque = tbl_etiqueta_servico.embarque
        where data_input > CURRENT_DATE - interval '3 months' AND tbl_embarque.fabrica = $login_fabrica and etiqueta not in (select conhecimento from tbl_faturamento_correio where data_input > CURRENT_DATE - interval '3 months' and conhecimento is not null AND fabrica = $login_fabrica GROUP BY conhecimento)";
    $res_conhecimento_etiqueta = pg_query($con, $sql_conhecimento_etiqueta);

    if (ENV == "prod") {
        $soapRastreio = new SoapClient("http://webservice.correios.com.br/service/rastro/Rastro.wsdl", array("trace" => 1, "exception" => 1));
    } else {
        $soapRastreio = new SoapClient("http://webservice.correios.com.br/service/rastro/Rastro.wsdl", array("trace" => 1, "exception" => 1));
    }


    //pegar código de rastreio.
    $sql_rastreio = "SELECT conhecimento, numero_postagem FROM tbl_faturamento_correio 
                        WHERE fabrica = $login_fabrica  
                        AND conhecimento <> '' 
                        AND data >= CURRENT_DATE - interval '3 months'";
    $res_rastreio = pg_query($con, $sql_rastreio);

    for($i=0; $i<pg_num_rows($res_rastreio); $i++){
        $conhecimento       = pg_fetch_result($res_rastreio, $i, conhecimento);
        $numero_postagem    = pg_fetch_result($res_rastreio, $i, numero_postagem);

        if (preg_match("/^\[.+\]$/", $conhecimento)) {
            $conhecimento   = json_decode($conhecimento, true);         
        }else{
            $conhecimento = array($conhecimento);
        }

        foreach($conhecimento as $linhaConhecimento){
            $cod_conhecimento = $linhaConhecimento;

            $metodo         = "buscaEventos";
            $buscaEventos   = (object) array("usuario" => $dados_acesso['usuario'], "senha" => $dados_acesso['senha'], "tipo" => "L", "resultado" => "T", "lingua" => 101,"objetos" => "$cod_conhecimento");

            $soapResult = $soapRastreio->__soapCall($metodo, array($buscaEventos));

            foreach ($soapResult->return->objeto->evento as $linha) {
                $obs = "";

                $local      = $linha->local;
                $data       = $linha->data;
                $hora       = $linha->hora;             
                $situacao   = $linha->descricao;
                $cidade     = $linha->cidade;

                $local = $cidade." - ".$local;
        
                list($d, $m, $y)    = explode("/", $data);
                $data               = $y."-".$m."-".$d;
                $dataHora           = $data ." ".$hora;

                if(strlen(trim($linha->comentario))>0){
                    $obs        = $linha->comentario;   
                } 

                if(isset($linha->destino)) {
                    $destinoLocal   = $linha->destino->local;
                    $destinoCidade  = $linha->destino->cidade;
                    $destinoCodigo  = $linha->destino->codigo;
                    $destinoUf      = $linha->destino->uf;

                    $obs .= "Código: $destinoCodigo Encaminhado para ".$destinoLocal."/".$destinoCidade."-".$destinoUf;
                }
                
                $sql_verifica = "SELECT data FROM tbl_faturamento_correio 
                                    WHERE data = '$dataHora' 
                                    AND fabrica = $login_fabrica 
                                    AND conhecimento = '$cod_conhecimento' ";
                $res_verifica = pg_query($con, $sql_verifica);

                if(pg_num_rows($res_verifica)==0){
                    $sql_grava_rastreio = "INSERT INTO tbl_faturamento_correio (fabrica, local, conhecimento, situacao, data, obs, numero_postagem) 
                                        VALUES ($login_fabrica, '$local', '$cod_conhecimento', '$situacao', '$dataHora', '$obs', '$numero_postagem')";
                    $res_grava_rastreio = pg_query($con, $sql_grava_rastreio);

                }
            }
        }
    }

    /*Término Processo*/
    $phpCron->termino();

    
?>