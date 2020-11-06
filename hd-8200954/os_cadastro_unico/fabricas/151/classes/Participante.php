<?php

	/* Classe para a gravação de participantes no Sistema da Mondial (151) */
	/* Os participantes podem ser definidos como Pessoa Jurídica(Posto / Empresa) ou Física(Cliente / Consumidor) */

	class Participante{

		#protected $_url_servidor = "http://sisweb-melhoria.mondialline.com.br/SIS_SND/rest/wsgravaparticipante"; - Homologação /* http://<ipservidor>:<porta>/<SisWeb>/rest/<Serviço> */
		protected $_url_servidor;
		protected $_chave_acesso;

		protected $_fabrica = 151;
		protected $_url;
		protected $servidor;

		public function __construct($server = "mk_nordeste"){
			global $_serverEnvironment;
			
			$this->servidor = $server;

			include_once dirname(__FILE__) . '/MKDistribuicao.php';
			$Send = new DadosSend($this->_fabrica);
			$url  = $Send->urlServidor(null,$this->servidor);
			$this->_url = $url['url'];
		}

		public function gravaParticipante($dadosParticipante){

			global $_serverEnvironment;

			if(count($dadosParticipante) > 0){

				$this->_chave_acesso = $this->getKey($this->servidor);

				if ($dadosParticipante["SdEntParticipante"]["ParticipanteTipoPessoa"] == "F") {
					$tipo_pessoa = "F";
					$dadosParticipante["SdEntParticipante"]["Enderecos"][0]["InscricaoEstadual"] = "ISENTO";
				}

				$ambiente = ($_serverEnvironment == 'development') ? 2 : 1;

				$dadosParticipante["SdEntParticipante"]["UnidadeOperacional"] 	= $this->_chave_acesso["unidade_operacional"];
				$dadosParticipante["SdEntParticipante"]["UsuarioChaveGUID"] 	= $this->_chave_acesso["chave_seguranca_send"];
				$dadosParticipante["SdEntParticipante"]["AmbienteTipo"] 		= $ambiente; /* 1 - Produção | 2 - Homoloção */

				$dados_participante["SdParmParticipante"]["RelacionamentoCodigo"] = $dadosParticipante["SdEntParticipante"]["RelacionamentoCodigo"];
				$dados_participante["SdParmParticipante"]["ParticipanteTipoPessoa"] = $dadosParticipante["SdEntParticipante"]["ParticipanteTipoPessoa"];
				$dados_participante["SdParmParticipante"]["ParticipanteFilialCPFCNPJ"] = $dadosParticipante["SdEntParticipante"]["ParticipanteFilialCPFCNPJ"];

				
				$status_participante = $this->verificaParticipante($dados_participante);
				$cep = $dadosParticipante['SdEntParticipante']['Enderecos'][0]['ParticipanteFilialEnderecoCep'];
				$dadosParticipante['SdEntParticipante']['Enderecos'][0]['ParticipanteFilialEnderecoSequencia'] = $this->codigoEnderecoParticipante($dados_participante, $cep);
				
				$dadosParticipante = json_encode($dadosParticipante);
				if ((!is_bool($status_participante) || (is_bool($status_participante) && $status_participante != true)) || $tipo_pessoa == "F") {
					$ch = curl_init($this->_url."wsgravaparticipante");
					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $dadosParticipante);
					curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
					curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
					$result = curl_exec($ch);
					curl_close($ch);
					$resposta = json_decode($result, true);

// 					if(strlen(trim($resposta['SdErro']['ErroDesc'])) > 0){
// 						$resposta['SdErro']['ErroDesc'] .= "<br>Entrar em contato com a TI da  ".utf8_encode('fábrica').".";
// 					}
// 					return $this->resposta($resposta);
				    if(($resposta['SdErro']['ErroCod']) == 0){
				    	return true;
				    }else{
						return $this->resposta($resposta);
				    }
				}else{
					return true;
				}

			}else{
				return false;
			}

		}

		public function codigoEnderecoParticipante($dadosParticipante, $cep){
			
			if(count($dadosParticipante) > 0){

				$this->_chave_acesso = $this->getKey($this->servidor);

				$ambiente = ($_serverEnvironment == 'development') ? 2 : 1;

				$dadosParticipante["SdParmParticipante"]["UnidadeOperacional"]   = $this->_chave_acesso["unidade_operacional"];
				$dadosParticipante["SdParmParticipante"]["UsuarioChaveGUID"]     = $this->_chave_acesso["chave_seguranca_send"];
				$dadosParticipante["SdParmParticipante"]["AmbienteTipo"]         = $ambiente; /* 1 - Produção | 2 - Homoloção */
				$dadosParticipante["SdParmParticipante"]["RelacionamentoCodigo"] = $dadosParticipante["SdEntParticipante"]["RelacionamentoCodigo"];
				$dadosParticipante["SdParmParticipante"]["ParticipanteTipoPessoa"] = $dadosParticipante["SdEntParticipante"]["ParticipanteTipoPessoa"];
				$dadosParticipante["SdParmParticipante"]["ParticipanteFilialCPFCNPJ"] = $dadosParticipante["SdEntParticipante"]["ParticipanteFilialCPFCNPJ"];

				$dadosParticipante = json_encode($dadosParticipante);

				
				$ch = curl_init($this->_url."wsconsultaparticipante");
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $dadosParticipante);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
				$result = curl_exec($ch);
				curl_close($ch);
				$resposta = json_decode($result, true);
				
				foreach ($resposta['SdRetConsultaParticipante']['SdSaiParticipante']['Enderecos'] as $key => $value) {
					if($value['ParticipanteFilialEnderecoCep'] == $cep){						
						return 	$value['ParticipanteFilialEnderecoSequencia'];				
					}
				}
			}else{
				return false;
			}
		}

		public function verificaParticipante($dadosParticipante, $retorno = false){

			if(count($dadosParticipante) > 0){

				$this->_chave_acesso = $this->getKey($this->servidor);

				$ambiente = ($_serverEnvironment == 'development') ? 2 : 1;

				$dadosParticipante["SdParmParticipante"]["UnidadeOperacional"]   = $this->_chave_acesso["unidade_operacional"];
				$dadosParticipante["SdParmParticipante"]["UsuarioChaveGUID"]     = $this->_chave_acesso["chave_seguranca_send"];
				$dadosParticipante["SdParmParticipante"]["AmbienteTipo"]         = $ambiente; /* 1 - Produção | 2 - Homoloção */
				$dadosParticipante = json_encode($dadosParticipante);
				$ch = curl_init($this->_url."wsconsultaparticipante");
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $dadosParticipante);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
				$result = curl_exec($ch);
				curl_close($ch);
				$resposta = json_decode($result, true);
				$msg = $resposta["SdRetConsultaParticipante"];
				if(strlen(trim($msg['SdErro']['ErroDesc'])) > 0){
					$resposta['SdErro']['ErroDesc'] .= "<br>Entrar em contato com a TI da  ".utf8_encode('fábrica').".";
				}
				
				if($msg["SdErro"]["ErroCod"] == 1 OR empty($msg["SdSaiParticipante"]["ParticipanteFilialCPFCNPJ"])){
					return false;
				}else{
					if ($retorno == true) {
						return $resposta["SdRetConsultaParticipante"];
					} else {
						return true;
					}
				}
			}else{
				return false;
			}
		}

		public function resposta($msg = array()){
			if(count($msg) > 0){

				if(isset($msg["SdErro"])){
					if($msg["SdErro"]["ErroCod"]){
						return true;
					}else{
						return utf8_decode($msg["SdErro"]["ErroDesc"]);
					}
				}else{
					return false;
				}

			}else{
				return false;
			}

		}

		public function getKey($servidor = null){

			global $con;

			if(strlen($servidor) == 0){
				$servidor = "mk_nordeste";
			}

			$sql = "SELECT parametros_adicionais FROM tbl_fabrica WHERE fabrica = {$this->_fabrica}";
			$res = pg_query($con, $sql);

			$dados = json_decode(pg_fetch_result($res, 0, "parametros_adicionais"), true);
			return $dados["dados_api_send"][$servidor];

        	}
		
		/* Teste de inclusão de arquivo */
		public function run(){

			echo "Classe iniciada com Sucesso!";

		}

	}

?>
