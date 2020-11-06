<?php
	require_once dirname(__FILE__) . '/../gera-extrato.class.php';
	require_once dirname(__FILE__) . '/../funcoes.php';

	/**
	 * HD 417698 - Nova rotina de extrato, será um modelo para novas fabricas.
	 * $this->setPosto( 877 ); // Para gerar extrato com somente alguns postos. Aceita array e int
	 * $this->setDataFinal($data); // Para qdo quiser gerar extrato com OS fechadas até uma certa data. Y-m-d
	 * @author Brayan
	 * @version 0.1
	 */
	final class GeraExtratoBlack extends GeraExtrato {

		/**
		 * Sequencial gerado ao gravar log na tbl_perl_processado
		 * @var int
		 */
		private $perl_processado;

		/**
		 * ID da fabrica
		 * @var int
		 */
		private $fabrica = 1;

		/**
		 * Construtor, gera extrato blackedecker
		 * @author Brayan
		 */
		public function __construct($config) {

			// Config para gerar logs, se passado 'debug' no array, apenas imprime erros, senão envia e-mail
			parent::__construct($this->fabrica, $config);

		}

		public function gerar() {

			return parent::gerar();

		}

		public function calculaExtrato($data, $data_fim) {

			return parent::calculaExtrato($data, $data_fim);

		}

		public function verificaExtratoAnterior($posto){

			$data = date("Y-m-01",mktime (0, 0, 0, date("m")-1, date("d"),  date("Y")));

			$data_fim = date("Y-m-t",mktime (0, 0, 0, date("m")-1, date("d"),  date("Y")));

			$sql = "SELECT extrato
					FROM tbl_extrato
					WHERE fabrica = {$this->fabrica}
					AND posto = $posto
					AND data_geracao BETWEEN '$data 00:00:00' and '$data_fim 23:59:59'";
			$res = pg_query($this->getCon(),$sql);

			if(pg_num_rows($res) > 0){
				return true;
			}
		}

		public function calculaOsItem(){
			$sql = "SELECT fn_calcula_os_item_black(tbl_os_extra.os,{$this->fabrica})
					FROM    tbl_os_extra
					JOIN    tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato
					WHERE   tbl_extrato.fabrica = {$this->fabrica}
					AND     tbl_os_extra.i_fabrica = {$this->fabrica}
					AND     tbl_extrato.data_geracao::date = current_date;";
			$res = pg_query($this->getCon(),$sql);
			if(!pg_last_error($this->getCon())){
				return true;
			}
		}

		/**
		 * Pega os postos à serem gerados os extratos, de acordo com a opção escolhida pelo posto (semanal, quinzenal, mensal, automatico)
		 * Se quiser rodar apenas para um posto, utilize o método setPosto( $id_posto )
		 * ATENÇÃO: Se nao executar esse metodo, e nao setar algum posto, irá gerar com todos os postos da fabrica.
		 * @author Brayan
		 */
        public function getPostos()
        {
			$postos = array();

			$sql = "SELECT  DISTINCT
                            intervalo_extrato,
                            tbl_posto_fabrica.posto,
                            automatico,
                            dia_semana,
                            periodicidade,
                            semana
                    FROM    tbl_posto_fabrica
                    JOIN    tbl_tipo_gera_extrato   USING (posto,fabrica)
                    JOIN    tbl_intervalo_extrato   USING (intervalo_extrato)
                    WHERE   tbl_posto_fabrica.fabrica           = {$this->fabrica}
                    AND     tbl_posto_fabrica.credenciamento    IN ('CREDENCIADO','EM DESCREDENCIAMENTO')
                    ";

			$res = pg_query ($this->getCon(), $sql);

			$this->verificaErro();

			for ( $i = 0; $i < pg_num_rows($res); $i++ ) {

				$posto 			= pg_result ($res, $i, 'posto');
				$automatico		= pg_result ($res, $i, 'automatico');
				$intervalo_extrato 	= pg_result ($res, $i, 'intervalo_extrato');

				if ( empty($intervalo_extrato) ) {

					if ( strtolower(date('D')) == 'mon' ) {
						// caso o posto nao respondeu a pesquisa, e for segunda-feira, gera normalmente
						$postos[] = $posto;

					}

					continue;

				}

				if ( $automatico == 't' ) {

					if ( strtolower(date('D')) == 'mon' AND date('d') <= 7) {
						// caso o posto nao respondeu a pesquisa, e for segunda-feira, gera normalmente
						if(!$this->verificaExtratoAnterior($posto)){
							$postos[] = $posto;
						}

					}
					continue;

				}

				$intervalo 	= pg_result ($res, $i, 'periodicidade');
				$dia_semana  	= pg_result ($res, $i, 'dia_semana');

				// Passa para o prox. posto caso o dia da semana nao seja hoje..
           		if ( !empty($dia_semana) and strtolower( $dia_semana ) != strtolower(date('D')) )  {
					continue;

				}

				if ( $intervalo == '7' ) {

					$postos[] = $posto;

				} else if ( $intervalo == '15') {
					// Coloca o posto, se o dia da semana for o primeiro ou o terceiro do mes..

					if ( $this->verificaData('first') || $this->verificaData('third') ) {

						$postos[] = $posto;

					}

				} else if ($intervalo == '30') {

					// Coloca o posto apenas se o dia da semana for o primeiro do mes

                    $semana = pg_fetch_result ($res, $i, 'semana');

                    switch ($semana) {
                        case 1:
                            $gravaSemana = 'first';
                            break;
                        case 2:
                            $gravaSemana = 'second';
                            break;
                        case 3:
                            $gravaSemana = 'third';
                            break;
                        case 4:
                            $gravaSemana = 'fourth';
                            break;
                        default:
                            $gravaSemana = 'first';
                            break;
                    }

                    if ($this->verificaData($gravaSemana,$dia_semana)) {

                        $postos[] = $posto;
                    }
                }
			}

			return $postos;

		}

        /**
         * Verifica se o dia eh o primeiro, segundo, whatever do mes
         * @param $inicio string contendo o dia que quer saber
         * @param $semana Int Mostra qual segunda feira do mês o extrato irá rodar
         * @example p.e. first. Se for segunda, verifica se eh a primeira segunda do mes
         * @return bool true se verdadeiro false falso.
         * @author Brayan Rastelli | William Ap. Brandino (2017-07-11)
         */
		private function verificaData($inicio,$dia_semana=null)
		{

			$mes_atual = date('M');
			$dia_atual = (empty($semana)) ? strtolower(date('D')) : $dia_semana ;

			$valor = (bool)(date('d', strtotime("$inicio $dia_atual of $mes_atual")) == date('d'));
			if(empty($valor)) $valor = 0 ;
			return $valor;

		}

		/**
		 * Gera log na tbl_perl_processado
		 * @param string $msg Mensagem de log a ser gravada
		 * @param bool $finalizado se TRUE marca
		 * @author Brayan
		 */
		public function logPerl ($msg, $finalizado = FALSE) {

			try {

				if ( empty($msg) ) {

					throw new InvalidArgumentException("Mensagem não pode ser vazia");

				}

			} catch (InvalidArgumentException $e) {

				$this->setErro($e);
				return false;

			}

			if (empty($this->perl_processado)) {

				$finaliza = $finalizado == TRUE ? 'current_timestamp' : 'null';

				$sql = "INSERT INTO tbl_perl_processado(
	                        perl,
	                        log,
	                        fim_processo
		                ) VALUES (
	                        17,
	                        '$msg',
	                        $finaliza
	                    );

						SELECT currval('seq_perl_processado') as perl_processado;";
			} else {

				$finaliza = $finalizado == TRUE ? ', fim_processo = current_timestamp' : '';

				$sql = "UPDATE tbl_perl_processado
						SET log = log || '  $msg'
						$finaliza
						WHERE perl_processado = {$this->perl_processado}";

			}

			$res = pg_query($this->getCon(), $sql);

			$this->verificaErro();

			if ( $this->getErros() ) {

				return FALSE;

			}

			if ( !$this->perl_processado ) {

				$this->perl_processado = pg_result($res,0,0);

			}

			return true;

		}

		public function verificarExtratoAvulso(){
			$data_atual = date("Y-m-d");

			$sql = "SELECT
					tbl_admin.admin,
					tbl_admin.nome_completo,
					tbl_admin.email,
					tbl_os.sua_os,
					tbl_posto.nome AS nome_posto,
					tbl_posto_fabrica.codigo_posto,
					tbl_extrato.protocolo,
					tbl_extrato_lancamento.os,
					tbl_extrato_lancamento.extrato,
					tbl_extrato_lancamento.valor
				FROM tbl_extrato_lancamento
					INNER JOIN tbl_admin ON tbl_admin.admin = tbl_extrato_lancamento.admin AND tbl_admin.fabrica = {$this->fabrica}
					INNER JOIN tbl_posto ON tbl_posto.posto = tbl_extrato_lancamento.posto
					INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_extrato_lancamento.posto AND tbl_posto_fabrica.fabrica = {$this->fabrica}
					INNER JOIN tbl_extrato ON tbl_extrato.extrato = tbl_extrato_lancamento.extrato AND tbl_extrato.fabrica = {$this->fabrica}
					INNER JOIN tbl_os ON tbl_os.os = tbl_extrato_lancamento.os AND tbl_os.fabrica = {$this->fabrica}
				WHERE tbl_extrato_lancamento.fabrica = {$this->fabrica} AND tbl_extrato_lancamento.extrato IS NOT NULL
					AND tbl_extrato.data_geracao BETWEEN '$data_atual 00:00:00' and '$data_atual 23:59:59'
				ORDER BY admin";

			$res = pg_query($this->getCon(), $sql);

			if(pg_num_rows($res) > 0){
				return array("sucesso" => true, "res" => $res);
			}else{
				return array("sucesso" => false);
			}
		}

	}



$defined = PHPUNIT;

if ( $defined !== TRUE ) {

	$config = array(
        'fabrica'   => 'blackedecker',
        'dest'      => 'helpdesk@telecontrol.com.br',
        'debug'		=> true
    );

	$obj = new GeraExtratoBlack($config);
	$obj->setPosto( $obj->getPostos() );

	if ( $obj->getErros() ) {
		$obj->logPerl($obj->getErros(), TRUE);
		return;
	}

	$obj->logPerl('Iniciando processamento..');
	$obj->gerar();

	$obj->logPerl('Calculando intens das OSs dos extratos..');
	$obj->calculaOsItem();

	$obj->logPerl('Calculando extratos..');
	$obj->calculaExtrato();

	$obj->logPerl('Finalizando processamento..', TRUE);

	$resExtratoAvulso = $obj->verificarExtratoAvulso();

	if($resExtratoAvulso["sucesso"] == true){
		$admin            = 0;
		$email            = "";
		$resExtratoAvulso = $resExtratoAvulso["res"];

		while($objeto_extrato_avulso = pg_fetch_object($resExtratoAvulso)){
			if($admin == 0){
				$admin = $objeto_extrato_avulso->admin;
				$email = $objeto_extrato_avulso->email;

				$mensagem = "Prezado(a) ".$objeto_extrato_avulso->nome_completo;
				$mensagem .= "<br/><br/>Lançamento avulso entrou no extrato.<br/>";
				$mensagem .= "<br/> Foi lançado o avulso referente a ";
			}

			if($admin != $objeto_extrato_avulso->admin){
				/*
				* Log Class
				*/
			    $logClass = new Log2();
			    $logClass->adicionaTituloEmail("Extrato Avulso");

				$logClass->adicionaEmail($email);

				$logClass->adicionaLog($mensagem);

				if($logClass->enviaEmails() == "200"){
		          // echo "Log de erro enviado com Sucesso!";
		        }else{
		          // echo $logClass->enviaEmails();
		        }

		        $mensagem = "Prezado(a) ".$objeto_extrato_avulso->nome_completo;
				$mensagem .= "<br/><br/>Lançamento avulso entrou no extrato.<br/>";
				$mensagem .= "<br/> Foi lançado o avulso referente a ";

				$admin = $objeto_extrato_avulso->admin;
				$email = $objeto_extrato_avulso->email;
			}

			$mensagem .= "<br/><b>OS</b> ".$objeto_extrato_avulso->codigo_posto.$objeto_extrato_avulso->sua_os."<br/>";
			$mensagem .= "<b>Cód. Posto </b>".$objeto_extrato_avulso->codigo_posto."<br/>";
			$mensagem .= "<b>Posto </b>".$objeto_extrato_avulso->nome_posto."<br/>";
			$mensagem .= "<b>Protocolo</b> ".$objeto_extrato_avulso->protocolo."<br/>";
			$mensagem .= "<b>Valor</b> ".number_format($objeto_extrato_avulso->valor, 2, ",", ".")."<br/>";
		}

		/*
		* Log Class
		*/
	    $logClass = new Log2();
	    $logClass->adicionaTituloEmail("Extrato Avulso");

		$logClass->adicionaEmail($email);

		$logClass->adicionaLog($mensagem);

		if($logClass->enviaEmails() == "200"){
          echo "Log de erro enviado com Sucesso!";
        }else{
          echo $logClass->enviaEmails();
        }
	}

}
