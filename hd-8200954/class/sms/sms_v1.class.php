<?php

	class SMS{

		private $ddd = array(11,12,13,14,15,16,17,18,19,21,22,24,27,28,31,32,33,34,35,37,38,41,42,43,44,45,46,47,48,49,51,53,54,55,61,62,63,64,65,66,67,68,69,71,73,74,75,77,79,81,82,83,84,85,86,87,88,89,91,92,93,94,95,96,97,98,99);

        private $remetentes = array(
            '11' => '1133399954',
            '80' => '7792670131',
            '104' => '14997075730',
            '151' => '14981412668',
            '172' => '1133399954',
        );


		function chave($fabrica, $con){

			$sql = "SELECT api_secret_key_sms FROM tbl_fabrica WHERE ativo_fabrica IS TRUE AND fabrica = $fabrica";
			$res = pg_query($con, $sql);

			if(pg_num_rows($res) > 0){

				return pg_fetch_result($res, 0, 'api_secret_key_sms');

			}

		}

		function nomeFabrica($fabrica, $con){

			$sql = "SELECT nome FROM tbl_fabrica WHERE ativo_fabrica IS TRUE AND fabrica = $fabrica";
			$res = pg_query($con, $sql);

			if(pg_num_rows($res) > 0){

				return pg_fetch_result($res, 0, 'nome');

			}

		}

		function obterSaldo($fabrica, $con){

			$url = curl_init();
			$url_api = "https://sms.comtele.com.br/api/".$this->chave($fabrica, $con)."/balance";

			curl_setopt($url, CURLOPT_URL, $url_api);

			curl_setopt($url, CURLOPT_RETURNTRANSFER, true);

			$dados = curl_exec($url);

			$cabecalhos = curl_getinfo($url);

			curl_close($url);

			if($cabecalhos['http_code'] == 200){
				return $dados;
			}

		}

		function validaDestinatario($fone){

			$fone = str_replace(" ", "", $fone);
			$fone = str_replace("(", "", $fone);
			$fone = str_replace(")", "", $fone);
			$fone = str_replace("-", "", $fone);

			$ddd_fone = substr($fone, 0, 2);

			if(!in_array($ddd_fone, $this->ddd)){
				return false; 
			}else{

                if (strlen($fone) == 10) {
                    $primeiro_num = substr($fone, 2, 1);
                } elseif (strlen($fone) == 11) {
                    $primeiro_num = substr($fone, 3, 1);
                } else {
                    return false;
                }

				if(!in_array($primeiro_num, array(5,6,7,8,9))){
					return false;
				}else{

					$total_num = strlen($fone) - 2; // Menos o DDD

					if($total_num == 8 OR $total_num == 9){
						return true;
					}else{
						return false;
					}

				}

			}
			
		}

		function enviarMensagem($destinatario, $os, $data, $fabrica, $con, $msg = ''){

			if(!$this->validaDestinatario($destinatario)){
				return true;
			}

			$remetente = $this->remetentes[$fabrica];

            if (empty($msg)) {
                $mensagem = "OS $os. A " . $this->nomeFabrica($fabrica, $con) . " informa: seu produto encontra-se disponivel para retirada junto ao posto autorizado de origem. Reparado em: $data.";
            } else {
                $mensagem = $msg;
            }

			$url_api = "https://sms.comtele.com.br/api/".$this->chave($fabrica, $con)."/sendmessage";
			$url_api.= "?sender=".$remetente."&receivers=".$destinatario."&content=".urlencode($mensagem);

			$dados = array("sender" => $remetente, "receivers" => $destinatario, "content" => $mensagem);

			$url = curl_init();
			curl_setopt($url, CURLOPT_URL, $url_api);
			curl_setopt($url, CURLOPT_POST, count($dados));
			curl_setopt($url, CURLOPT_POSTFIELDS, $dados);
			curl_setopt($url, CURLOPT_RETURNTRANSFER, 1);

			$dados = curl_exec($url);

			$enviar = curl_getinfo($url);

            curl_close($url);

			return ($enviar["http_code"] == 200) ? true : false;

		}

		function selecionarSMSPedente($con){

			$sql = "SELECT * FROM tbl_sms_pendente";
			$res = pg_query($con, $sql);

			$return = (pg_num_rows($res) > 0) ? pg_fetch_all($res) : 0;
			return $return;

		}

		function gravarSMSPendente($fabrica, $os, $con){

			$sql = "INSERT INTO tbl_sms_pendente (fabrica, os) VALUES ($fabrica, $os)";
			$res = pg_query($con, $sql);
	

		}

		function excluirSMSPendente($fabrica, $os, $con){

			$sql = "DELETE FROM tbl_sms_pendente WHERE fabrica = $fabrica AND os = $os";
			$res = pg_query($con, $sql);	

			return "excluido";

		}

	}

?>
