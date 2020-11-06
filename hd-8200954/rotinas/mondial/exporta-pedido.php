<?php

    try {

        /*
        * Includes
        */

        include dirname(__FILE__) . '/../../dbconfig.php';
        include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
        require dirname(__FILE__) . '/../funcoes.php';

        include dirname(__FILE__) . '/../../classes/Posvenda/Fabrica.php';
        include dirname(__FILE__) . '/../../classes/Posvenda/Os.php';
        include dirname(__FILE__) . '/../../classes/Posvenda/Pedido.php';
        include dirname(__FILE__) . '/../../os_cadastro_unico/fabricas/151/classes/Participante.php';
        include dirname(__FILE__) . '/../../os_cadastro_unico/fabricas/151/classes/MKDistribuicao.php';
	include dirname(__FILE__) . '/../../os_cadastro_unico/fabricas/151/classes/verificaEstoque.php';

        /*
        * Definição
        */
        date_default_timezone_set('America/Sao_Paulo');
        $fabrica = 151;
        $env = ($_serverEnvironment == 'development') ? "teste" : "producao";

        $pedido = $argv[1];

        /*
        * Log
        */
        $logClass = new Log2();
	$logClass->adicionaLog(array("titulo" => "Log erro - Geração de Exporta Pedidos Mondial Brasil")); // Titulo
		$datenow = date('dmYH');
		$fp = fopen ("/tmp/mondial/logExportaPedido-".$datenow.".txt","a");
        if ($env == 'producao' ) {
            $logClass->adicionaEmail("rogerio.soares@mondialline.com.br");
            $logClass->adicionaEmail("jefferson.nogueira@mondialline.com.br");
            $logClass->adicionaEmail("arnaldo.furtado@mondialline.com.br");
            $logClass->adicionaEmail("helpdesk@telecontrol.com.br");
        } else {
            $logClass->adicionaEmail("ronald.santos@telecontrol.com.br");
        }

        /*
        * Cron
        */
        $phpCron = new PHPCron($fabrica, __FILE__);
        $phpCron->inicio();

        /*
        * Class Fábrica
        */
        $fabricaClass = new \Posvenda\Fabrica($fabrica);

        /*
        * Resgata o nome da Fabrica
        */
        $fabrica_nome = $fabricaClass->getNome();

        /*
        * Resgata os parametros adicionais da Fabrica
        */
        $parametros_adicionais = $fabricaClass->getParametroAdicional();

        /*
        * Mensagem de Erro
        */
        $msg_erro = array();

        $pedidoClass = new \Posvenda\Pedido($fabrica);
        $enviar_pedido = $pedidoClass->getPedidoNaoExportado($pedido);
	#$tipo_pedido = $pedidoClass->getTipoPedidoGarantia("GARANTIA - PRODUTO");
        $chaveAcesso = $parametros_adicionais->chave_seguranca_send;

        $countPedido = count($enviar_pedido);
        
	$ambiente = ($_serverEnvironment == 'development') ? 2 : 1;
       
	$Send = new DadosSend($fabrica);
	$estoque = new verificaEstoque();

        for ($i = 0; $i < $countPedido; $i++) {
            unset($dadosPedido);
            try {
                //$pedidoClass->_model->getPDO()->beginTransaction();
                
                $pedido         = $enviar_pedido[$i]["pedido"];
                $tipo_pedido    = $enviar_pedido[$i]["tipo_pedido"];
                $dadosServidor  = $Send->urlServidor($pedido,null,$tipo_pedido); 
		$urlServidor    = $dadosServidor["url"];
		$estoqueProduto	= json_decode($dadosServidor["estoque"],true); 
		$tabela         = $enviar_pedido[$i]["tabela"];
		$server		= "mk_nordeste";

		fwrite($fp,"Exportação Pedido: {$pedido}\n");

        $info_pedido = $pedidoClass->getInformacaoPedido($pedido, $tabela);

        $tipo_frete_posto = "C";
        if(isset($info_pedido['parametros_adicionais_posto'])){

            $parametros_adicionais_posto = json_decode($info_pedido['parametros_adicionais_posto']);

            if(isset($parametros_adicionais_posto->frete)){

                if($parametros_adicionais_posto->frete == 'FOB'){
                     $tipo_frete_posto = 'F';
                }
            }
        }

		if(strpos($info_pedido['tipo_pedido'],"GARANTIA") !== false){
                        $tipo_debito = "GA";
                }else{
                        $tipo_debito = "NA";
                }

		if($pedidoClass->getVerificaPecaProduto($pedido)){
		    // Pedido de Troca de Produto
		    $tipo_pedido = "TT";
		}else if ($info_pedido["tipo_pedido"] == "VENDA") {
		    $tipo_pedido = "VT";
		}else if($info_pedido["tipo_pedido"] == "CORTESIA"){
		    // Pedido de Cortesia
		    $tipo_pedido = "CT";
		} else {
		    // Pedido de Peça
		    $tipo_pedido = "OT";
		}

		$pecas = $pedidoClass->getInformacaoPecaPedido($pedido, $tabela);
		
		if($tipo_pedido == "TT"){	

			try{
		
				$server = (strpos($urlServidor,"mksul") !== false) ? "mk_sul" : "mk_nordeste";

				if(count($estoqueProduto) == 0){
					$consultaEstoque[0] = $server;
				}else{
					foreach($estoqueProduto AS $key => $value){
						if($value == $server){
							$consultaEstoque[0] = $value;
						}else{
							$consultaEstoque[1] = $value;

						}
					}
				}

				foreach($consultaEstoque AS $key => $value){
					
					if(empty($value)) continue;

					if($value != $server){

						$pecasReplace = $estoque->alteraReferenciaPeca($value,$pecas);
						if(is_array($pecasReplace)) {
						$estoquePecas = $estoque->consultaEstoquePecas($pecasReplace,$tipo_pedido,$value,$fp,true);
						}
						if($estoquePecas != false){
							$urlServidor  = $Send->urlServidor(null,$value);
							$server = $value;
							$pecas = $pecasReplace;
						}
					}else{
						if(is_array($pecas)) {
							$estoquePecas = $estoque->consultaEstoquePecas($pecas,$tipo_pedido,$value,$fp,true);
						}
					}

					if($estoquePecas === false){
						continue;
					}
				}
			}catch(Exception $e){
				$msg_erro[] = $e->getMessage();
				continue;
			}
		}

		$dadosAcesso = $Send->getKey($server);
		$dadosPedido["SdEntPedido"]["UnidadeOperacional"] = $dadosAcesso["unidade_operacional"];
                $dadosPedido["SdEntPedido"]["UsuarioChaveGUID"]   = ($_serverEnvironment == 'development') ? $dadosAcesso["chave_seguranca_send_homologacao"] : $dadosAcesso["chave_seguranca_send"];
                $dadosPedido["SdEntPedido"]["AmbienteTipo"]       = $ambiente; /* 2 - Homologação | 1 - Produção */

                $pedidoClass->_model->getPDO()->beginTransaction();

                // Verifica se o pedido foi gerado em cima de uma OS de troca de produto.
                $info_cliente = $pedidoClass->getDadosClienteOS($pedido);
                if($info_cliente == false){

                    $info_cliente = $pedidoClass->getDadosCliente($pedido, $tipo_pedido);
                }
                if($info_cliente !== false){

                    $Participante = new Participante($server);

                    /* Teste de inclusão do arquivo */
                    // $Participante->run();

                    $dados_posto = array();

                    if(strlen($info_cliente['fone']) > 0){
                        $fone = str_replace(array("(", ")", " ", "-", "."), "", $fone);
                        $ddd = substr($fone, 0, 2);
                        $telefone = substr($fone, 2, strlen($fone) - 1);
                    }else{
                        $ddd = "";
                        $telefone = "";
                    }

                    $cep = preg_replace("/\D/", "", $info_cliente["cep"]);

                    $dados_posto["SdEntParticipante"] = array(
                        "RelacionamentoCodigo"                  => "ConsumidorFinal", /* AssistTecnica - Assistência Técnica | ConsumidorFinal - Consumidor Final */
                        "ParticipanteTipoPessoa"                => (strlen($info_cliente['cpf']) == 11) ? "F" : "J", /* F- Física | J - Jurídica | E - Estrangeira */
                        "ParticipanteFilialCPFCNPJ"             => $info_cliente['cpf'],
                        "ParticipanteRazaoSocial"               => utf8_encode($info_cliente['nome']),
                        "ParticipanteFilialNomeFantasia"        => utf8_encode($info_cliente['nome']),
                        // "ParticipanteFilialRegimeTributario"     => "", /* Microempresa | SimplesNacional | LucroPresumido | LucroReal */
                        "ParticipanteStatus"                    => "A", /* A - Ativo | I - Inativo */

                        /** Endereço **/
                        "Enderecos"                             => array(
                            array(
                                "ParticipanteFilialEnderecoSequencia"   => 1, /* Campo númerico */
                                "ParticipanteFilialEnderecoTipo"        => "Entrega", /* Cobranca | Entrega */
                                "ParticipanteFilialEnderecoCep"         => $cep,
                                "ParticipanteFilialEnderecoLogradouro"  => utf8_encode($info_cliente['endereco']),
                                "ParticipanteFilialEnderecoNumero"      => utf8_encode($info_cliente['numero']),
                                "ParticipanteFilialEnderecoComplemento" => utf8_encode($info_cliente['complemento']),
                                "ParticipanteFilialEnderecoBairro"      => utf8_encode($info_cliente['bairro']),
                                "PaisCodigo"                            => 1058, /* 1058 - Brasil */
                                "PaisNome"                              => "Brasil",
                                "UnidadeFederativaCodigo"               => utf8_encode($info_cliente['estado']),
                                "UnidadeFederativaNome"                 => utf8_encode($info_cliente['estado']),
                                // "MunicipioCodigo"                    => "",
                                "MunicipioNome"                         => utf8_encode($info_cliente['cidade']),
                                // "InscricaoEstadual"                  => "123456987",
                                "ParticipanteFilialEnderecoStatus"      => "A" /* A - Ativo | I - Inativo */
                                )
                            ),
                        /** Contatos **/
                        "Contatos"                              => array(
                            array(
                                "ParticipanteFilialEnderecoContatoNome"         => utf8_encode($info_cliente['nome']),
                                "ParticipanteFilialEnderecoContatoEmail"        => utf8_encode($info_cliente['email']),
                                "ParticipanteFilialEnderecoContatoTelefoneDDI"  => 55, /* Default Brasil */
                                "ParticipanteFilialEnderecoContatoTelefoneDDD"  => $ddd,
                                "ParticipanteFilialEnderecoContatoTelefone"     => $telefone
                                )
                            )

                        );
                    if ($dados_posto["SdEntParticipante"]["ParticipanteTipoPessoa"] == "F") {
                        $dados_posto["SdEntParticipante"]["Enderecos"][0]["InscricaoEstadual"] = "ISENTO";
                    }else{
                        $dados_ie = $pedidoClass->getInscricaoEstadual($info_cliente['cpf'], $pedido);
                        $dados_posto["SdEntParticipante"]["Enderecos"][0]["InscricaoEstadual"] = $dados_ie[0]['ie'];
                    }

                    $dados_posto["SdEntParticipante"]["Enderecos"][0]["ParticipanteFilialEnderecoSequencia"] = $Participante->codigoEnderecoParticipante($dados_posto, $info_cliente['cep']);


                    $status_posto = $Participante->gravaParticipante($dados_posto,$urlServidor);

                    if (!is_bool($status_posto) || (is_bool($status_posto) && $status_posto != true)) {
                        $msg_erro[] = "ERRO: Pedido {$pedido} não exportado, erro ao gravar cliente!\n";
                        $msg_erro[] = $status_posto;

                        if (isset($argv[1])) {
                            echo json_encode(array(
                                "SdErro" => array(
                                        "ErroCod" => 1,
                                        "ErroDesc" => utf8_decode("Erro ao gravar Participante, Descrição: $status_posto"),
                                        "GravaParticipante" => true
                                    )
                                ));
                        }
                        continue;
                    } else {
                        $info_pedido['cnpj'] = $info_cliente['cpf'];
                    }

                    unset($Participante);

                }

                $dadosPedido["SdEntPedido"]["PedidoNumero"]            = $pedido;
                $dadosPedido["SdEntPedido"]["ParticipanteCPFCNPJ"]     = $info_pedido['cnpj'];
                $dadosPedido["SdEntPedido"]["PedidoNumeroCliente"]     = (!empty($info_pedido['pedido_cliente'])) ? utf8_encode($info_pedido['pedido_cliente']) : $pedido;
                $dadosPedido["SdEntPedido"]["PedidoTransportadora"]    = "1005";
                $dadosPedido["SdEntPedido"]["PedidoICMS"]              = 0;
                $dadosPedido["SdEntPedido"]["PedidoTipoFrete"]         = $tipo_frete_posto;
                $dadosPedido["SdEntPedido"]["PedidoEntregaData"]       = date("Y/m/d");
                $dadosPedido["SdEntPedido"]["PedidoObervacao"]         = "";
                $dadosPedido["SdEntPedido"]["RepresentanteCodigo"]     = 0;
                $dadosPedido["SdEntPedido"]["TipoMercado"]             = "VA";
                $dadosPedido["SdEntPedido"]["TipoPedidoLivreDebito"]   = $tipo_debito;
                $dadosPedido["SdEntPedido"]["PedidoReferenciaExterna"] = $pedido;
		$dadosPedido["SdEntPedido"]["EnderecoEntregaSeq"]      = $dados_posto["SdEntParticipante"]["Enderecos"][0]["ParticipanteFilialEnderecoSequencia"];

                unset($itens);

                if($pedidoClass->getVerificaPecaProduto($pedido)){
                    // Pedido de Troca de Produto
                    $dadosPedido["SdEntPedido"]["PedidoTipo"] = "TT";
                    $dadosPedido["SdEntPedido"]["CondicaoPagamentoCodigo"] = "5";
                    $dadosPedido["SdEntPedido"]["TipoPedidoLivreDebito"]   = "BR";
                }else if ($info_pedido["tipo_pedido"] == "VENDA") {
                    $dadosPedido["SdEntPedido"]["PedidoTipo"] = "VT";
                    $dadosPedido["SdEntPedido"]["CondicaoPagamentoCodigo"] = $info_pedido['codigo_condicao'];
                }else if($info_pedido["tipo_pedido"] == "CORTESIA"){
                    // Pedido de Cortesia
                    $dadosPedido["SdEntPedido"]["PedidoTipo"] = "CT";
                    $dadosPedido["SdEntPedido"]["CondicaoPagamentoCodigo"] = "5";
                    $dadosPedido["SdEntPedido"]["TipoPedidoLivreDebito"]   = "GA";
                } else {
                    // Pedido de Peça
                    $dadosPedido["SdEntPedido"]["PedidoTipo"] = "OT";
                    $dadosPedido["SdEntPedido"]["CondicaoPagamentoCodigo"] = "5";
                }

                if ($info_pedido['tipo_pedido'] == 'BONIFICACAO' ) {

                    $dadosPedido["SdEntPedido"]["PedidoTipo"] = "OT";
                    $dadosPedido["SdEntPedido"]["CondicaoPagamentoCodigo"] = "5";
                    $dadosPedido["SdEntPedido"]["TipoPedidoLivreDebito"]   = "GA";
                }
                $info_peca = $pedidoClass->getInformacaoPecaPedido($pedido, $tabela);
                $countPeca = count($info_peca);

                for($j=0; $j < $countPeca; $j++){
		    $refItem = ($tipo_pedido == "TT") ? $pecas[$j]['referencia'] : $info_peca[$j]['referencia'];
                    $itens[] = array("ProdutoCodigo" => $refItem,
                        "NumeroVPC"            => 0,
                        "ValorUnitario"        => $info_peca[$j]['preco'],
                        "DescontoPercentual"   => 0,
                        "Quantidade"           => $info_peca[$j]['qtde'],
                        "TabelaPreco"          => $info_peca[$j]['sigla_tabela'],
                        "CFOP"                 => 0,
                        "CIO"                  => 0,
                        "FretePercentual"      => 0,
                        "PedidoItemRefExterna" => $info_peca[$j]['pedido_item'],
                        "ProdutoCodigoOS" => $info_peca[$j]['ref_produto'],

                        );
                }

                $dadosPedido["SdEntPedido"]["Item"] = $itens;

                $dadosPedido = json_encode($dadosPedido);
                #echo $dadosPedido;exit;
		$dadosEnvio = "Dados Enviados\n URL: {$urlServidor}wsgravapedido\n Dados enviados\n {$dadosPedido}\n";
		echo $dadosEnvio ."\n\n";
		fputs ($fp,$dadosEnvio);
                if(count($itens) > 0){
                    $ch = curl_init($urlServidor."wsgravapedido");
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $dadosPedido);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		    $result = curl_exec($ch);
		    curl_close($ch);
		    
		    $dadosRetorno = "Dados de Retorno\n {$result}\n\n\n";
		    fputs($fp,$dadosRetorno);

echo $result ."\n\n\n========================================================================\n";
                    $result = json_decode($result, true);
                    if($result['SdErro']['ErroCod'] == "0"){
                        if(!($pedidoClass->registrarPedidoExportado($pedido))){
                            $msg_erro[] = "ERRO: Pedido {$pedido} não exportado!\n";
                        }
                    }else{
                        $msg_erro[] = $result["SdErro"]["ErroDesc"];
                    }
                }else{
                    $msg_erro[] = "ERRO: Pedido {$pedido} não exportado por não conter nenhum item!\n";
                }

                $pedidoClass->_model->getPDO()->commit();

                if (isset($argv[1])) {
                    echo json_encode($result);
                }

            } catch(Exception $e) {
                $pedidoClass->_model->getPDO()->rollBack();

                $msg_erro[] = $e->getMessage();

                continue;
            }
        }

        if(!empty($msg_erro)){

            $logClass->adicionaLog(implode("<br />", $msg_erro));

            //print_r($msg_erro);
            $logClass->enviaEmails();

            $fp = fopen("/tmp/{$fabrica_nome}/pedidos/log-erro".date("d-m-Y_H-i-s").".txt", "a");
            fwrite($fp, "Data Log: " . date("d/m/Y") . "\n");
            fwrite($fp, implode("\n", $msg_erro));
            fclose($fp);

        }

		fclose($fp);
        $phpCron->termino();

    } catch (Exception $e) {
        echo $e->getMessage();
    }
