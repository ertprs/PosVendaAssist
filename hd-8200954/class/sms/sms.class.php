<?php
include_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'simple_rest.class.php';

class SMS
{
	const API_URL = 'https://sms.comtele.com.br/';

	private
		$remetente,              // CallerID do fabricante
		$conn  = null,           // Conexão DB
		$key   = null,           // Chave da API SMS
		$name  = null,           // Nome da fábrica
		$saldo = null,
		$fabrica;
	private static
		$remetentes = array(
			'1'   => '14997075730',
			'3'   => '14981442006',
			'11'  => '1133399954',
			'35'  => '14981442006',
			'42'  => '14981442006',
			'80'  => '7792670131',
			'101' => '14997075730',
			'104' => '14997075730',
			'151' => '14981412668',
			'157' => '14991531120',
			'160' => '14996043562',
			'169' => '1121229473',
			'170' => '1121229473',
			'172' => '1133399954',
			'174' => '14996043562',
		/*	'167' => '1122569100',*/
			'186' => '14981442006',
			'189' => '11988888888'
		);


	public $statusEnvio = null;  // NULL  Nada enviado
	                             // FALSE erro no envio
	                             // TRUE  envio OK

	/**
	 * O construtor se encarrega de pegar a conexão do banco, fábrica logada e outros detalhes
	 */
	function __construct($fabrica = null) {
		$this->conn = $GLOBALS['con'];
		$this->api  = new SimpleREST();

		if (!is_resource($this->conn))
			die('Sem conexão com o Banco de Dados!');

		if (is_null($fabrica))
			$fabrica = $GLOBALS['login_fabrica'];

		if (!is_numeric($fabrica))
			die('SMS: erro durante a inicialização!');

		$this->setFabrica($fabrica);
	}

	public function __get($var) {
		$var = str_replace(array('-','_'), '', strtolower($var));

		switch($var) {
			case 'chave':
			case 'key':
				return $this->key;
			break;

			case 'nome':
			case 'name':
			case 'nomefabrica':
			case 'fabricanome':
				return $this->name;
			break;

			case 'saldo':
				if (is_null($this->saldo))
					$this->obterSaldo();
				return $this->saldo;
			break;

			case 'fabrica':
				return $this->fabrica;
			break;

			default:
				return null;
			break;
		}
	}

	public function setFabrica($fabrica) {
		global $_serverEnvironment;

		if (!is_numeric($fabrica) or ($this->fabrica and $fabrica == $this->fabrica))
			return $this;

		if (!in_array($fabrica, array_keys(self::$remetentes))) {
			return false;
		}

		$res = pg_query(
			$this->conn,
			"SELECT nome, api_secret_key_sms
			   FROM tbl_fabrica
			  WHERE ativo_fabrica IS TRUE
			    AND fabrica       =  $fabrica
			    AND api_secret_key_sms is not null"
		);

		if (pg_num_rows($res) !== 1){
			die('SMS: Erro ao recuperar as informações do Remetente.');
		}

		// Chave para enviar SMS nos testes do DEVEL. Com essa chave não gera cobrança para fábrica.
		if($_serverEnvironment == 'development') {
			$this->key       = '02e85ac1-bbcf-4d00-acf4-678087245444';	
		}else{
			$this->key       = pg_fetch_result($res, 0, 'api_secret_key_sms');
		}
		$this->name      = pg_fetch_result($res, 0, 'nome');

		$this->fabrica   = $fabrica;
		$this->remetente = self::$remetentes[$fabrica];

		if ($this->name == 'Precision')
			$this->name = 'Amvox';
		if ($this->name == 'Vonder')
			$this->name = 'OVD';

		return $this;
	}

	/**
	 * Sem parâmetros, retorna a lista de fabricantes habilitados para
	 * envio de SMS no sistema.
	 * Se passa um número, retorna TRUE ou FALSE se a fábrica está
	 * na lista ou não.
	 */
	public static function getFabricasSms($fabricante=null) {
		if (is_null($fabricante))
			return array_keys(self::$remetentes);

		if (is_numeric($fabricante))
			return in_array($fabricante, array_keys(self::$remetentes));

		return false;
	}

	public function obterSaldo() {
		$this->api
			->clearParams()
			->setUrl(self::API_URL . 'api/'.$this->key.'/balance')
			->send('GET');

		if ($this->api->statusCode == 200) {
			$this->saldo = $this->api->response['body'];
			return $this->saldo;
		}
		$this->saldo = null;
		return false;
	}

	/**
	 * Validação do telefone do destinatário usando a mesma validação
	 * que a classe LibPhoneNumber.
	 */
	public function validaDestinatario($fone) {
		// Tira o DDI, se vier
		$fone  = preg_replace("/^(:?\+?(:?00)?\s??(55)?)?|\D/", "", $fone);
		// Números bloqueados
		$fones_invalidos = [
			11111111111,
			22222222222,
			33333333333,
			44444444444,
			55555555555,
			66666666666,
			77777777777,
			88888888888,
			99999999999,
			00000000000
		];

		if(in_array($fone, $fones_invalidos)){			
			return false;
		} else {
			$regra = include(__DIR__.DIRECTORY_SEPARATOR.'../../classes/libphonenumber/data/PhoneNumberMetadata_BR.php');
			$regex = preg_replace('/\s/m','', $regra['mobile']['NationalNumberPattern']);
			return (bool)preg_match("/$regex/", $fone);		
		}
	}

	public function enviarMensagem($destinatario, $sua_os, $data, $msg = '', $hd_chamado = null , $treinamento = null, $origem = null) {
		global $login_admin;

		$msgTpl = "OS %s. A %s informa: Seu produto encontra-se disponível para retirada junto ao Posto Autorizado de origem.";
		
		if (!$this->validaDestinatario($destinatario))
			return false;

		if(empty($this->fabrica)) return false;
		
		if($this->fabrica == 101) return true;

        $destinatario = preg_replace('/\D/', '', $destinatario);

		$mensagem = ($msg)  ? : sprintf($msgTpl, $this->getSuaOs($sua_os), $this->name);

		if (!$msg and $data)
			$mensagem .= " Reparado em: $data";

		if (strlen($mensagem) > 2040)
			return false;

		$mensagem = str_replace(array("\r", "\n"), " ", strip_tags($mensagem));

		$numMsg = $this->retiraAcentos($mensagem);

		$numMsg = strlen($numMsg);
		if($numMsg > 160) {
			$creditoEnvio = ceil($numMsg/153);
		}else{
			$creditoEnvio = ceil($numMsg/160);
		}

		if (!mb_check_encoding($mensagem, 'UTF8')) 
			$mensagem = utf8_encode($mensagem);

		$campo = "";
		$value = "";

		if (strlen($login_admin) > 0) {
			$campo = " ,admin ";
			$value = " ,$login_admin ";			
		}

		if (strlen($treinamento) > 0) {
			$campo .= " ,treinamento ";
			$value .= " ,$treinamento ";			
		}

		if (!empty($origem)) {
			$campo .= " ,origem ";
			$value .= " ,'{$origem}' ";
		}

		if (!empty($sua_os)) {

			$sqlAddSMS = "INSERT INTO tbl_sms (
								fabrica,
								data,
								os,
								destinatario,
								texto_sms,
								credito_envio
								$campo
							) VALUES (
								{$this->fabrica},
								now(),
								{$sua_os},
								{$destinatario},
								'{$mensagem}',
								{$creditoEnvio}
								{$value}
							) RETURNING sms;";
			$resAddSMS = pg_query($this->conn, $sqlAddSMS);

			$this->remetente = pg_fetch_result($resAddSMS, 0, 'sms');

		} elseif (!empty($hd_chamado)) {

			$sqlAddSMS = "INSERT INTO tbl_sms (
								fabrica,
								data,
								hd_chamado,
								destinatario,
								texto_sms,
								credito_envio
								$campo
							) VALUES (
								{$this->fabrica},
								now(),
								{$hd_chamado},
								{$destinatario},
								'{$mensagem}',
								{$creditoEnvio}
								{$value}
							) RETURNING sms;";
			$resAddSMS = pg_query($this->conn, $sqlAddSMS);

			$this->remetente = pg_fetch_result($resAddSMS, 0, 'sms');
		}else{
			$sqlAddSMS = "INSERT INTO tbl_sms (
								fabrica,
								data,
								destinatario,
								texto_sms,
								credito_envio
								$campo
							) VALUES (
								{$this->fabrica},
								now(),
								{$destinatario},
								'{$mensagem}',
								{$creditoEnvio}
								{$value}
							) RETURNING sms;";
			$resAddSMS = pg_query($this->conn, $sqlAddSMS);

			$this->remetente = pg_fetch_result($resAddSMS, 0, 'sms');
		}

		$msg_erro = pg_last_error();

		if(!empty($msg_erro) or empty($this->remetente)) {
			return 'erro ao enviar ';
			die();
		}


		$postData = array(
			"sender"    => $this->remetente,
			"receivers" => $destinatario,
			"content"   => $mensagem
		);
		$url = self::API_URL . 'api/'.$this->key.'/sendmessage?' .
			http_build_query($postData, '&');
		// return $url;
		$this->api
			->setUrl($url)
			->addParam($postData)
			->send('POST');

		$this->statusEnvio = (string)$this->api;

		if ($this->api->statusCode == 200) {
			return true;
		} else {
			$sqlErroSMS = "UPDATE tbl_sms SET status_sms = 'Erro na Comtele' WHERE sms = " . $this->remetente;
			$resErroSMS = pg_query($this->conn, $sqlErroSMS);

			return true;
		}
	}

	public function selecionarSMSPedente($fabrica=null) {
		$sql =  "SELECT * FROM tbl_sms_pendente";

		if (is_numeric($fabrica))
			$sql .= ' WHERE fabrica = '.$fabrica;

		$res = pg_query($this->conn, $sql);
		return (is_resource($res) and pg_num_rows($res) > 0) ? pg_fetch_all($res) : 0;
	}

	public function gravarSMSPendente($id, $campo='os') {
		if (!strpos(' os hd_chamado', $campo))
			return false;

		$sql = "INSERT INTO tbl_sms_pendente (
			fabrica, {$campo}
		) VALUES (
			{$this->fabrica}, {$id}
		)";

		$res = pg_query($this->conn, $sql);
		return (is_resource($res) and pg_affected_rows($res)>0);
	}

	public function excluirSMSPendente($id, $campo='os') {
		if (!strpos(' os hd_chamado', $campo))
			return false;

		if (!is_numeric($id))
			return false;

		$sql = "DELETE FROM tbl_sms_pendente
			     WHERE fabrica = {$this->fabrica} AND {$campo} = {$id}";

		$res = pg_query($this->conn, $sql);
		return (is_resource($res) and pg_affected_rows($res)>0) ? 'excluido' : 'erro';
	}

	public function getDetailedReport($startDate, $endDate) {
		$data_inicial = is_date($startDate);
		$data_final   = is_date($endDate);

		if (!$data_inicial or !$data_final)
			return false;

			$this->api->setUrl(
				self::API_URL . 'api/v2/detailedreporting' .
				str_replace(' ', '%20', "?startDate={$data_inicial}&endDate={$data_final}&auth-key={$this->key}")
			)
			->send('GET');
		return (string)$this->api;
	}

	public function getReplyReport($startDate, $endDate, $sender = null, $respLidas = 'all') {
		
		$data_inicial = is_date($startDate);
		$data_final   = is_date($endDate);

		if (!$data_inicial or !$data_final)
			return false;

		$this->api->setUrl(
			self::API_URL . 'api/' . $this->key . '/replyreport' .
			str_replace(' ', '%20', "?startDate={$data_inicial}&"."endDate={$data_final}"."&unread={$respLidas}")
		)
		->send('GET');

		return (string)$this->api;
	}

	public function retiraAcentos( $texto ){
        $array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç","º","&","%","$","?","@" );
        $array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C","_","_","_","_","_","_" );
        return str_replace( $array1, $array2, $texto );
    }

	public function getSuaOs($os) {
		$sql = "SELECT sua_os FROM tbl_os where os = $os ";
		$res = pg_query($this->conn, $sql);
		return pg_fetch_result($res, 0, 'sua_os');

	}
}
