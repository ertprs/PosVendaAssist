<?php

include dirname(__FILE__) . '/../../../../classes/Posvenda/Fabrica.php';
include dirname(__FILE__) . '/../../../../classes/Posvenda/Pedido.php';

include dirname(__FILE__) . '/MKDistribuicao.php';

	/* ENVIO DE CANCELAMENTO DE PEDIDO PARA SERVIDOR SEND DA MONDIAL */

    /* hd_chamado=3097736 */
        $servidor = $_SERVER['SERVER_NAME'];
        $pos = strpos($servidor, 'devel');
        /* 2 - Homologação | 1 - Produção */
        if($pos === false){
            $ambiente_tipo = 1;
        }else{
            $ambiente_tipo = 2;
        }
    /* Fim hd_chamado=3097736 */
    class CancelarPedido {

		private $_fabrica;
		private $chaveAcesso;
		private $urlServidor;
		private $server;

		public function __construct($fabrica){
            		global $ambiente_tipo;

			if(empty($fabrica)){
				return "ERRO: Não foi possível criar a classe de Cancelamento de Pedido.";
			}

	    		$this->_fabrica = $fabrica;

            	}

		public function cancelaPedidoItem($pedido, $pedido_item, $motivo_cancelamento){
			global $ambiente_tipo;

			if(empty($pedido)){
				return "Número do Pedido vázio";
			}

			if(empty($pedido_item)){
				return "Número do Pedido Item vázio";
			}

			if(empty($motivo_cancelamento)){
				return "O motivo de cancelamento não poder ser vázio";
			}

			$this->dadosAPI($pedido);

			unset($dadosPedido);
			$dadosPedido["SdCancPedido"]["UnidadeOperacional"] = $this->chaveAcesso["unidade_operacional"];
			$dadosPedido["SdCancPedido"]["UsuarioChaveGUID"]   = ($ambiente_tipo == 1) ? $this->chaveAcesso["chave_seguranca_send"] : $this->chaveAcesso["chave_seguranca_send_homologacao"];
        		$dadosPedido["SdCancPedido"]["AmbienteTipo"]       = $ambiente_tipo; /* 2 - Homologação | 1 - Produção */
			
			$pedidos[] = array("PedidoReferenciaExterna" => "$pedido",
				"PedidoItemRefExterna" => "$pedido_item",
				"MotivoCancelamento"   => utf8_encode($motivo_cancelamento)
			);

			$dadosPedido["SdCancPedido"]["Pedidos"] = $pedidos;
			$dadosPedido = json_encode($dadosPedido);

			$datenow = date('dmYH');
			$fp = fopen ("/tmp/assist/mondial_logCancelaPedido_item-".$datenow.".txt","a");
			fputs ($fp,"$dadosPedido  \n");

		    	$ch = curl_init($this->urlServidor."WsCancelaPedido");
		    	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		    	curl_setopt($ch, CURLOPT_POSTFIELDS, $dadosPedido);
		    	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		    	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		    	$result = curl_exec($ch);
		    	curl_close($ch);
			fputs ($fp,"$result \n\n\n");
            		$resposta = json_decode($result, true);

			$msg_erro = "";

			if($resposta['SdRetCancPedido']['SdErro']['ErroCod'] == "1") {
				fputs ($fp,$resposta['SdRetCancPedido']['SdErro']['ErroDesc'] ." \n");
				fclose($fp);
				return $resposta['SdRetCancPedido']['SdErro']['ErroDesc'];
			}

			unset($pedidos);
			$pedidos = $resposta['SdRetCancPedido']['Pedidos'];
			$count = count($pedidos);

			for($i=0; $i<$count; $i++){
				if($pedidos[$i]['ErroCod'] == 1){
					$msg_erro .= $pedidos[$i]['ErroDesc']."<br/>";
					fputs ($fp,"Erro: $msg_erro - Pedido: $pedido --- Pedido_item: $pedido_item --- Motivo: $motivo_cancelamento \n");
				}else{
					fputs ($fp,"Sucesso: ". $pedidos[$i]['ErroDesc']."<br/> - Pedido: $pedido --- Pedido_item: $pedido_item --- Motivo: $motivo_cancelamento \n");
				}
			}

			fclose($fp);

            		if(empty($msg_erro)){
                		return true;
            		}else{
            			return $msg_erro;
            		}
		}

		public function cancelaTodoPedido($pedido, $motivo_cancelamento,$array_pedido_item){
			global $ambiente_tipo;


			if(empty($pedido)){
				return "Número do Pedido vázio";
			}

			if(empty($motivo_cancelamento)){
				return "O motivo de cancelamento não poder ser vázio";
			}

			$this->dadosAPI($pedido);

			unset($dadosPedido);
			$dadosPedido["SdCancPedido"]["UnidadeOperacional"] = $this->chaveAcesso["unidade_operacional"];
			$dadosPedido["SdCancPedido"]["UsuarioChaveGUID"]   = ($ambiente_tipo == 1) ? $this->chaveAcesso["chave_seguranca_send"] : $this->chaveAcesso["chave_seguranca_send_homologacao"];
			$dadosPedido["SdCancPedido"]["AmbienteTipo"]       = $ambiente_tipo; /* 2 - Homologação | 1 - Produção */
			$datenow = date('dmYH');
			$fp = fopen ("/tmp/mondial/logCancelaTodoPedido-".$datenow.".txt","a");

			if(count($array_pedido_item)>0){

				foreach($array_pedido_item as $value) {
						$pedidos[] = array("PedidoReferenciaExterna" => "$pedido",
							"PedidoItemRefExterna" => "$value",
							"MotivoCancelamento"   => utf8_encode($motivo_cancelamento)
						);
					}
			}else{
				return "ERRO: Não foi encontrado as peças do pedido para cancelar";

			}

			$dadosPedido["SdCancPedido"]["Pedidos"] = $pedidos;
			$dadosPedido = json_encode($dadosPedido);

			fputs ($fp,"$dadosPedido \n");

		    	$ch = curl_init($this->urlServidor."WsCancelaPedido");
		        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		        curl_setopt($ch, CURLOPT_POSTFIELDS, $dadosPedido);
		        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		        $result = curl_exec($ch);
		        curl_close($ch);
		        fputs ($fp,"\n\n\n$result");
		        $resposta = json_decode($result, true);
		        $msg_erro = "";

			if($resposta['SdRetCancPedido']['SdErro']['ErroCod'] == "1") {
				fputs ($fp,$resposta['SdRetCancPedido']['SdErro']['ErroDesc'] ." \n");
				fclose($fp);
				return $resposta['SdRetCancPedido']['SdErro']['ErroDesc'];
			}

			unset($pedidos);
			$pedidos = $resposta['SdRetCancPedido']['Pedidos'];
			$count = count($pedidos);

			for($i=0; $i<$count; $i++){
				if($pedidos[$i]['ErroCod'] == 1){
					$msg_erro .= $pedidos[$i]['ErroDesc']."<br/>";
					fputs ($fp,"Erro : " .$pedidos[$i]['ErroDesc']. " - Pedido: $pedido --- Motivo: $motivo_cancelamento \n");
				}else{
					fputs ($fp,"Sucesso: ". $pedidos[$i]['ErroDesc']."<br/> - Pedido: $pedido --- Pedido_item: $pedido_item --- Motivo: $motivo_cancelamento \n");
				}
			}

			fclose($fp);

			if(empty($msg_erro)){
				return true;
			 }else{
				return $msg_erro;
			 }
		}


		public function dadosAPI($pedido){

			$pedidoClass = new \Posvenda\Pedido($this->_fabrica);
			$Send = new DadosSend($this->_fabrica);

			$info_pedido = $pedidoClass->getInformacaoPedido($pedido);
			$dadosServidor  = $Send->urlServidor($pedido,null,$info_pedido['tipo_pedido_id']);
			
			$this->urlServidor  = $dadosServidor["url"];
			$this->server = (strpos($this->urlServidor,"mksul") !== false) ? "mk_sul" : "mk_nordeste";
			$this->chaveAcesso = $Send->getKey($this->server);
		}
	}
?>
