<?php
    
    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';
    include dirname(__FILE__) . '/../../class/communicator.class.php';

    $mail = new TcComm("smtp@posvenda");
    $destinatarios = [
        'felipe.marttos@telecontrol.com.br',
        'ronald.santos@telecontrol.com.br'
    ];
    $subject = "Erro rotina busca historico postagem";


    if ($_serverEnvironment == "production") {
        define("ENV", "prod");
    } else {
        define("ENV", "dev");
    }



	function buscaConhecimento($login_fabrica ,$numeros_postagens, $conhecimentos) {
		global $con; 
		$metodo 		= "buscaEventosLista";
		$soap = new SoapClient("http://webservice.correios.com.br/service/rastro/Rastro.wsdl", array("trace" => 1, "connection_timeout" => 30,
			'stream_context'=>stream_context_create(
				array('http'=>
				array(
					'protocol_version'=>'1.0',
					'header' => 'Connection: Close'
					)
				)
			)
		));

		$buscaEventos 	= (object) array("usuario" => "9912358441", "senha" => "P?WPP?VZ@O", "tipo" => "L", "resultado" => "T", "lingua" => 101,"objetos" => $conhecimentos);
		sleep(1);
		$soapResult = $soap->__soapCall($metodo, array($buscaEventos));
			$obs = "";

			$cod_conhecimento = $soapResult->return->objeto->numero;
			$numero_postagem = array_search($cod_conhecimento, $numeros_postagens);
			foreach($soapResult->return->objeto->evento as $key => $linha) {
				$obs = "";
				$local      = $linha->local;
				$data       = $linha->data;
				$hora       = $linha->hora;             
				$situacao   = $linha->descricao;
				$cidade     = $linha->cidade;
				if(empty($data)) continue;

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

				if (in_array($login_fabrica, [81,114,122,123,125,128,155,160,168,174])) {

					$status_hd_item = "";

					if (strpos($situacao, 'Objeto postado') !== false) {
						$status_hd_item = "Produto postado";
						$status_obs     = "<strong>O objeto foi postado</strong>";
					}

					if (strpos($situacao, 'Objeto entregue') !== false) {
						$status_hd_item = "Entregue para avaliação";
						$status_obs     = "<strong>O objeto foi entregue</strong>";
					}

					if (!empty($status_hd_item)) {

						$sql_hd_chamado = "SELECT hd_chamado
							FROM tbl_hd_chamado_postagem
							WHERE tbl_hd_chamado_postagem.numero_postagem = '$numero_postagem'
							AND tbl_hd_chamado_postagem.fabrica = $login_fabrica";
						$res_hd_chamado = pg_query($con, $sql_hd_chamado);
						if (pg_last_error()) {
							$log["erro"]["select_hd_chamado_postagem"][] = ["msg" => pg_last_error(), "sql" => $sql_hd_chamado];
						}
						$hd_chamado = pg_fetch_result($res_hd_chamado, 0, 'hd_chamado');

						if (!empty($hd_chamado)) {
							$sqlh = "SELECT hd_chamado from tbl_hd_chamado_item where hd_chamado = $hd_chamado and interno and status_item ='$status_hd_item'";
							$resh = pg_query($con, $sqlh);
							if(pg_num_rows($resh) == 0) {
								$sql_status = "INSERT INTO tbl_hd_chamado_item(
									hd_chamado   ,
									data         ,
									comentario   ,
									interno      ,
									status_item
								) VALUES (
									$hd_chamado,
									current_timestamp,
									'{$status_obs}',
									't',
									'{$status_hd_item}'
								)";
								$res_status = pg_query($con, $sql_status);
								if (pg_last_error()) {
									$log["erro"]["insert_hd_chamado_item"][] = ["msg" => pg_last_error(), "sql" => $sql_status];
								}
							}
						}
					}

				}

				$sql_verifica = "SELECT data FROM tbl_faturamento_correio 
					WHERE data = '$dataHora' 
					AND fabrica = $login_fabrica 
					AND conhecimento = '$cod_conhecimento' ";
				$res_verifica = pg_query($con, $sql_verifica);
				if (pg_last_error()) {
					$log["erro"]["select_faturamento_correio"][] = ["msg" => pg_last_error(), "sql" => $sql_verifica];
				}
				if(pg_num_rows($res_verifica)==0){
					$local = str_replace("'","\'", $local);
					$sql_grava_rastreio = "INSERT INTO tbl_faturamento_correio (fabrica, local, conhecimento, situacao, data, obs, numero_postagem) 
						VALUES ($login_fabrica, E'$local', '$cod_conhecimento', '$situacao', '$dataHora', '$obs', '$numero_postagem')";
					$res_grava_rastreio = pg_query($con, $sql_grava_rastreio);
					if (pg_last_error()) {
						$log["erro"]["insert_faturamento_correio"][] = ["msg" => pg_last_error(), "sql" => $sql_grava_rastreio];
					}
				}
			}
	}

    $fabricas = array(35,104,81,114,122,123,125,128,155,160,168,174,11,172);
    $log  = [];

    foreach ($fabricas as $login_fabrica) {
       
        /* Inicio Processo */
        $phpCron = new PHPCron($login_fabrica, __FILE__);
        $phpCron->inicio();
       
        $msg_erro      = array();
        $tipo          = 'A';

		//pega senha da Fabrica
        $sqlAcesso = "SELECT  usuario,
            senha,
            codigo as codAdministrativo,
            contrato,
			cartao, 
			id_correio
          FROM tbl_fabrica_correios
          WHERE fabrica = $login_fabrica";
        $resAcesso = pg_query($sqlAcesso);

        if (pg_last_error()) {
            $log["erro"]["select_fabrica_correios"][] = ["msg" => pg_last_error(), "sql" => $sqlAcesso];
        }
        if (pg_num_rows($resAcesso)>0) {
			$dados_acesso = pg_fetch_array($resAcesso);
			$username = $dados_acesso['id_correio'];
			$password = $dados_acesso['senha'];
        }
        
        if (ENV == "prod") {
            try {
				$url_novo_webservice = "https://cws.correios.com.br/logisticaReversaWS/logisticaReversaService/logisticaReversaWS?wsdl";
				$soap = new SoapClient("$url_novo_webservice", array(
					"trace" => 1, 
					"exception" => 0,
					'connection_timeout' => 30,
					'authorization' => 'Basic',
					'login' => $username,
					'password' => $password ,
					'stream_context'=>stream_context_create(
						array('http'=>
							array(
								'protocol_version'=>'1.0',
								'header' => 'Connection: Close'
							)
						)
					)
				));
            } catch (Exception $e) {
                $response[] = array("resultado" => "false", "mensagem" => "ERRO AO CONECTAR SERVIDOR DOS CORREIOS");

                require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

                return $response;
            }
        } else {
            $soap = new SoapClient("http://webservicescol.correios.com.br/ScolWeb/WebServiceScol?wsdl", array("trace" => 1, "exception" => 1));
        }
        
		$sql_protocolos = "INSERT into tbl_faturamento_correio ( 
								fabrica,
								local,
								situacao,
								data, 
								numero_postagem
							) select $login_fabrica, ' ', ' ', a.data, numero_postagem from tbl_hd_chamado_postagem a join tbl_hd_chamado using(hd_chamado) where fabrica_responsavel =$login_fabrica and a.data > current_timestamp - interval '3 months' and numero_postagem not in (select numero_postagem from tbl_faturamento_correio where fabrica = $login_fabrica and data_input > current_timestamp - interval '3 months') ; 


							SELECT distinct numero_postagem 
                            from tbl_faturamento_correio 
                            where fabrica = $login_fabrica 
                            and data >= CURRENT_DATE - interval '3 months' and (conhecimento isnull or length(conhecimento) = 0 ) ";
        $res_protocolos = pg_query($con, $sql_protocolos);

        if (pg_last_error()) {
            $log["erro"]["select_faturamento_correio"][] = ["msg" => pg_last_error(), "sql" => $sql_protocolos];
        }
        for($i=0; $i<pg_num_rows($res_protocolos); $i++){
            $numero_postagem = pg_fetch_result($res_protocolos, $i, numero_postagem);

            $array_request =  (object) array(
                'codAdministrativo'=> $dados_acesso['codadministrativo'],
                'tipoBusca'=>'H',
                'numeroPedido'=>$numero_postagem,
                'tipoSolicitacao'=> $tipo
            );
            
            $result = $soap->__soapCall('acompanharPedido', array($array_request));

            $coleta             = $result->acompanharPedido->coleta;
            $numeroPedido       = $result->acompanharPedido->coleta->numero_pedido;
            $controle_cliente   = $result->acompanharPedido->coleta->controle_cliente;
            $historico          = $result->acompanharPedido->coleta->historico;
            $conhecimento       = $result->acompanharPedido->coleta->objeto->numero_etiqueta;
            $data               = $result->acompanharPedido->coleta->objeto->data_ultima_atualizacao . " ". substr($result->acompanharPedido->coleta->objeto->hora_ultima_atualizacao, 0 , -3);
            $obs                = $result->acompanharPedido->coleta->objeto->descricao_status;
            $status             = $result->acompanharPedido->coleta->objeto->ultimo_status;

            if(strlen(trim($conhecimento))>0){
                $sql_upd = "UPDATE tbl_faturamento_correio SET conhecimento = '$conhecimento' WHERE numero_postagem = '$numero_postagem' AND (conhecimento is null or length(conhecimento) = 0)  and fabrica = $login_fabrica";
                $res_upd = pg_query($con, $sql_upd);
                if (pg_last_error()) {
                    $log["erro"]["update_faturamento_correio"][] = ["msg" => pg_last_error(), "sql" => $sql_upd];
                }
            }
        }



        //pegar código de rastreio.
        $sql_rastreio = "SELECT distinct conhecimento, numero_postagem FROM tbl_faturamento_correio 
                            WHERE fabrica = $login_fabrica  
                            AND conhecimento !~'http'
                            AND conhecimento ~'BR'
							and (faturamento not in (select faturamento from tbl_faturamento_correio where (situacao ~'entregue' or situacao ~'roubado' or situacao ~ 'devolvido') and fabrica = $login_fabrica ) OR (faturamento IS NULL and conhecimento not in (select conhecimento from tbl_faturamento_correio where (situacao ~'entregue' or situacao ~'roubado' or situacao ~ 'devolvido') and fabrica = $login_fabrica)))
							and numero_postagem notnull
			    AND data >= CURRENT_DATE - interval '2 months'";

        $res_rastreio = pg_query($con, $sql_rastreio);
        if (pg_last_error()) {
            $log["erro"]["select_faturamento_correio_rastreio"][] = ["msg" => pg_last_error(), "sql" => $sql_rastreio];
        }
		$conhecimentos = array();
		$numeros_postagens = array();
        for($i=0; $i<pg_num_rows($res_rastreio); $i++){
            $conhecimento       = pg_fetch_result($res_rastreio, $i, conhecimento);
            $numero_postagem    = pg_fetch_result($res_rastreio, $i, numero_postagem);

            $conhecimentos[] = $conhecimento;
            $numeros_postagens[$numero_postagem] = $conhecimento;
			$j = $i+1;
			if(count($conhecimentos) == 1 or $j == pg_num_rows($res_rastreio)) {

				buscaConhecimento($login_fabrica,$numeros_postagens, $conhecimentos);
				$conhecimentos = array();
				$numeros_postagens = array();

			}
		}

        /*Término Processo*/
        $phpCron->termino();
    }

    if (count($log) > 0) {

        $body .= "<b>Horário:</b> " . date("d/m/Y H:i:s") . "<br />";
        $body .= "<table width='100%' border='1'>";
        foreach ($log["erro"] as $titulo => $valores) {
            $body .= "
                    <thead> 
                        <tr>
                            <th bgcolor='#d90000' style='padding: 5px;color: #fff;font-family: Arial;text-align:center;'>
                                ".strtoupper($titulo)."
                            </th>
                        </tr>
                    </thead><tbody>";
            foreach ($valores as $rows) {
                    
                $body .= "
                        <tr>
                            <td style='padding: 5px;font-family: Arial;text-align:left;'>
                                <b>Erro:</b> ".$rows["msg"]."<br>
                                <b>SQL:</b> <pre>".var_export($rows["sql"], true)."</pre><br>
                            </td>
                        </tr>
                        ";
            }
        }
        $body .= "</tbody></table>";

        $mail->sendMail(
            $destinatarios,
            $subject,
            $body,
            "noreply@telecontrol.com.br"
        );
    }

?>
