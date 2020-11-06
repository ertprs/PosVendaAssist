<?php

	include dirname(__FILE__) . '/../dbconfig.php';
	include dirname(__FILE__) . '/../includes/dbconnect-inc.php';
	require_once dirname(__FILE__) . '/funcoes.php';

	/**
	 * HD 417698 - Nova rotina de extrato, será um modelo para novas fabricas.
	 * @author Brayan
	 * @example Ver a implementação da Blackedecker em rotinas/blackedecker/gera-extrato.php
	 * @version 0.1
	 */
	abstract class GeraExtrato {

		/**
		 * Conexao com o banco
		 * @var resource
		 */
		private $con;

		/**
		 * Array com os postos que irão gerar o extrato, retornado por setPosto()
		 * @var array
		 */
		private $postos = array();

		/**
		 * Array com erros que irão disparar exceção. Possui métodos set e get
		 * @var array
		 */
		private $erros  = array();

		/**
		 * Array passado por parâmetro no construtor, com configurações para gerar logs e fazer a rotina.
		 * Para desenvolver, passar 'debug' como 'bash', assim nao manda e-mail e imprime erros na tela. Se mandar TRUE envia e-mail. Se nao setar, nao faz nada
		 * @var array
		 */
		private $config = array();

		/**
		 * Gera até uma data especifica de fechamento da OS. Setado em setDataFinal()
		 * @var date Y-m-d
		 */
		private $dataFinal;

		/**
		 * ID da fábrica, passado por parâmetro no construtor
		 * @var int
		 */
		private $fabrica;

		/**
		 * Nome da rotina, apenas para gravar logs. Definido no método construtor
		 * @var string
		 */
		private $appName;

		/**
		 * Construtor, seta as propriedades necessárias, e cria conexao com o banco
		 * @param integer $fabrica
		 * @param array $config
		 * @author Brayan
		 */
		public function __construct ( $fabrica, array $config ) {

			try {

				global $con;
				$this->con 		= $con;

				$this->fabrica 	= $fabrica;
				$this->config   = $config;
				$this->config['tipo'] = 'extrato';
				$this->config['log']  = 1;

				$this->appName = 'Gera Extrato ' . $this->config['fabrica'];

				// Apenas grava que iniciou a rotina, no /tmp
				Log::log2($this->config, $this->appName . ' - Iniciando gera Extrato - ' . date('d-m-Y H:i:s') . PHP_EOL);

				if ( empty($this->fabrica) || empty ($this->config) ) {

					throw new InvalidArgumentException('Falha na passagem de parâmetros');

				}

				if ( !is_resource($this->con) ) {

					throw new Exception ('Falha ao conectar no banco de dados');

				}

			} catch( Exception $e ) {

				$this->setErro($e);

			}

		}

		/**
		 * Seta o erro para o array. Joga para o array todo o conteudo da exception. Para tratar, sobrescrever o método na classe herdada.
		 * @param Object Exception $e
		 */
		protected function setErro(Exception $e) {

			$this->erros[] = "Descrição do erro: {$e->getMessage()}<br />
							  Arquivo: {$e->getFile()}<br />
							  Linha: {$e->getLine()}";

		}

		/**
		 * Devolve o array com erros
		 * @return array $this->erros
		 * @author Brayan
		 */
		public function getErros() {

			return $this->erros;

		}

		/**
		 * Retorna o objeto de conexao, para usar em classes herdadas
		 * @return Object $this->con
		 * @author Brayan
		 */
		public function getCon(){

			return $this->con;

		}

		/**
		 * Define quais postos vão gerar extrato
		 * @param mixed $posto integer para apenas um posto, array para varios postos
		 * @author Brayan
		 */
		public function setPosto($posto) {

			try {

				if ( !is_array($posto) && !empty($posto) ) {

					if ( !in_array( $posto, $this->postos ) )
						$this->postos[] = $posto;

					return true;

				}

				foreach ($posto as $item) {

					if ( in_array( $item, $this->postos ) )
						continue;

					$this->postos[] = $item;

				}

				if ( empty ( $this->postos ) ) {

					throw new Exception ('Nenhum posto para gerar extrato');

				}

			} catch ( Exception $e ) {

				$this->setErro($e);
				return false;

			}

			return true;

		}

		/**
		 * Seta a propriedade data
		 * @param string $data data final para fechamento de extrato, padrao Y-m-d
		 * @author Brayan
		 */
		public function setDataFinal($data) {

			try {

				if ( !$this->validaData($data) ) {

					throw new Exception("Data Inválida $data");

				}

				$this->dataFinal = $data;

			} catch( Exception $e) {

				$this->setErro($e);
				return false;

			}

			return true;

		}

		protected function validaData( $data ) {

			if ( strpos ($data, '/') === FALSE )
				$delim = '-';
			else
				$delim = '/';

			list($yi, $mi, $di) = explode($delim, $data);

        	if(!checkdate($mi,$di,$yi)) {

        		return false;

        	}

		    $aux_data = "$yi-$mi-$di";

		    if(strtotime($aux_data) > strtotime('today')) {

		        return false;

		    }

        	return true;

		}

		/**
		 * Gera Extrato para os postos setados no método setPosto, se nao for setado, irá gerar para todos os postos da fabrica;
		 * @author Brayan
		 */
		protected function gerar() {

			if ( empty($this->dataFinal) ) {

				$this->dataFinal = date('Y-m-d');

			}

			if ( !empty($this->postos) ) {

				$sql = "CREATE TEMP TABLE tmp_gera_extrato_{$this->config['fabrica']}
						(posto int);

						INSERT INTO tmp_gera_extrato_{$this->config['fabrica']}
						VALUES(" . implode ('), (', $this->postos) . ")";

			} else {

				$sql = "SELECT posto
                        INTO TEMP tmp_gera_extrato_{$this->config['fabrica']}
                        FROM   tbl_posto_fabrica
                        WHERE  tbl_posto_fabrica.fabrica = {$this->fabrica}
                        ORDER BY tbl_posto_fabrica.posto";

			}

			pg_query($this->con, $sql);
			$this->verificaErro();

			try {

				$sql = "SELECT 'SELECT fn_fechamento_extrato(' || posto || ',' || ".$this->fabrica." || E',\\'".$this->dataFinal."\\');'
	                    INTO TEMP extrato_{$this->config['fabrica']}
	                    FROM tmp_gera_extrato_{$this->config['fabrica']};

	                    SELECT * FROM extrato_{$this->config['fabrica']};";

	            $res = pg_query($this->con, $sql);

	            $this->verificaErro();

	            if ( pg_num_rows($res) == 0 ) {

	            	throw new Exception("Nenhum posto para gerar o extrato");

	            }

	            for ($i=0; $i < pg_num_rows($res); $i++) {

	            	$sql = pg_result($res, $i, 0); // reecbe sql para executar

	            	if (PHPUNIT !== TRUE) {

	            		pg_query($this->con, "BEGIN TRANSACTION");

	            	}

	            	$res2 = pg_query($this->con, $sql);

	            	$msg_erro = pg_errormessage($this->con);

	            	if (PHPUNIT !== TRUE) {

	            		$sql = empty($msg_erro) ? 'COMMIT' : 'ROLLBACK';

	            		pg_query($this->con, $sql);

	            	}

	            }

	        } catch(Exception $e) {

	        	$this->setErro($e);
	        	return false;

	        }

	        return true;

		}

		/**
		 * Função para calcular extratos gerados
		 * @param $data_ini date Y-m-d Data inicial (opcional), default hoje
		 * @param $data_final date -Y-m-d Data final (opcional), default hoje
		 */
		protected function calculaExtrato($data_ini = null, $data_final = null) {

            try {

				if ( !empty($data_ini) && !empty($data_final) ) {

					if ( !$this->validaData($data_ini) || !$this->validaData($data_final) ) {

						throw new Exception("Data Invalida");

					}

					$cond = "tbl_extrato.data_geracao BETWEEN '$data_ini' AND '$data_final'";

				} else {

					$cond = 'tbl_extrato.data_geracao::date = current_date';

				}

				$sql = "SELECT 'SELECT fn_calcula_extrato(' || tbl_extrato.fabrica || ',' || tbl_extrato.extrato || ');'
	                    INTO TEMP extrato_calculado
	                    FROM    tbl_extrato
	                    WHERE   tbl_extrato.fabrica = {$this->fabrica}
	                    AND     $cond;

	                    SELECT * FROM extrato_calculado";

	            $res = pg_query($this->con, $sql);
	            $this->verificaErro();

	            if ( pg_num_rows($res) == 0 ) {

	            	throw new Exception("Nenhum extrato gerado para calcular.");

	            }

	        } catch(Exception $e) {

	        	$this->setErro($e);
	        	return false;

	        }

            for ($i=0; $i < pg_num_rows($res); $i++) {

            	$sql = pg_result($res, $i, 0);

            	$res2 = pg_query($this->con, $sql);

            	$this->verificaErro();

            }

            return true;

		}

		/**
		 * Verifica se houve erro na ultima query e seta no array de erros
		 * @return void
		 * @author Brayan
		 */
		protected function verificaErro() {

			try {

				$erro = trim ( pg_errormessage($this->con) );

				if ( $erro ) {

					throw new Exception($erro);

				}

			} catch (Exception $e) {

				$this->setErro($e);

			}

		}

		/**
		 * Se nao estiver com debug ligado (array $this->config), envia e-mail para e-mail passado no array $this->config, senao imprime na tela os erros.
		 * Caso queira alterar a forma, sobrescrever esse método onde está implementando, p.e. blackedecker/gera-extrato.php
		 * @author Brayan
		 */
		protected function geraLog() {

			if ( !empty($this->erros) ) {

				$msg = 'Ocorreu um ou mais erros ao gerar o extrato. Abaixo a descrição do(s) erro(s): <br /><br />' .
						implode('<br />', $this->erros) .
						'<br /><br />--<br />
						Suporte Telecontrol';

				if ( $this->config['debug'] === 'bash' ) {

					echo implode ("\n", $this->erros);
					return;

				}

				Log::envia_email( $this->config, $this->appName, $msg);

			}

		}

		/**
		 * Função executada ao finalizar a rotina.
		 * @author Brayan
		 */
		public function __destruct() {

			if ( isset ( $this->config['debug'] ) ) {

				$this->geraLog();

			}

			//Apenas grava que terminou a rotina, no /tmp ..
			Log::log2($this->config, $this->appName . ' - Gera Extrato Executado com Sucesso - ' . date('d-m-Y H:i:s') . PHP_EOL);

		}

	}
