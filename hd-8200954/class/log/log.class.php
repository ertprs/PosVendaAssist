<?php

	Class Log{

		public $mensagem 		= "";
		public $emails 			= array();
		public $tituloEmail 	= "";

		public function adicionaLog($log){

			if ($log == "linha") {
				$this->mensagem .= "<hr />";
			} else if (is_array($log) && array_key_exists("titulo", $log)) {
				$this->mensagem .= "<br /> <b>{$log['titulo']}</b> <br />";
			} else {
				$this->mensagem .= "<br /> {$log}";
			}

		}

		public function escreveLogs(){

			return $this->mensagem;

		}

		public function adicionaEmail($email){

			$this->emails[] = $email;

		}

		public function adicionaTituloEmail($titulo){

			$this->tituloEmail = $titulo;

		}

		public function escreveEmails(){

			$send = implode(",", $this->emails);
			return $send;

		}

		public function enviaEmails(){

			$header  = "MIME-Version: 1.0\n";
			$header .= "Content-type: text/html; charset=iso-8859-1\n";
			$header .= "From: Telecontrol <telecontrol\@telecontrol.com.br>";

			if(!mail($this->escreveEmails(), $this->tituloEmail, $this->escreveLogs(), $header)){

				$return = "";
				$emails = $this->escreveEmails();
				$logs  	= $this->escreveLogs();

				if(empty($emails)){
					$return .= "Não foi inserido nenhum email. <br />";
				}

				if(empty($this->tituloEmail)){
					$return .= "Não foi inserido nenhum titulo para o email. <br />";
				}

				if(empty($logs)){
					$return .= "Não foi inserido nenhum log no corpo do email. <br />";
				}

				return $return;

			}else{
				return "200";
			}

		}

		public function limpaDados(){
			$this->mensagem = "";
			$this->emails = array();
			$this->tituloEmail = "";
		}

	}

	/*

	Exemplo de uso

	--------------------------------------------------------------

	$log = new Log();

	$log->adicionaLog(array("titulo" => "Titulo teste 1")); // Titulo
	$log->adicionaLog("teste 1"); // Log
	$log->adicionaLog("teste 2"); // Log
	$log->adicionaLog("linha"); // Linha de separação

	$log->adicionaLog(array("titulo" => "Titulo teste 2"));
	$log->adicionaLog("teste 3");
	$log->adicionaLog("teste 4");
	$log->adicionaLog("linha");

	$log->adicionaLog(array("titulo" => "Titulo teste 3"));
	$log->adicionaLog("teste 5");
	$log->adicionaLog("teste 6");
	$log->adicionaLog("linha");

	$log->adicionaTituloEmail("Teste para Logs de Rotinas");

	$log->adicionaEmail("guilherme.silva@telecontrol.com.br");
	$log->adicionaEmail("guilherme.curcio@telecontrol.com.br");
	$log->adicionaEmail("ronald.santos@telecontrol.com.br");

	if($log->enviaEmails() == "200"){
		echo "Log de erro enviado com Sucesso!";
	}else{
		echo $log->enviaEmails();
	}

	*/

?>
