<?php
/**
 * NOTA:
 * =====
 * +-------------------------------+
 * | ESTE ARQUIVO ESTÁ EM UTF-8!   |
 * | Ao abrir ele, SEMPRE conferir |
 * | que está na codificação CERTA |
 * +-------------------------------+
 * Esta classe para REST NÃO É EXCLUSIVA PARA API2 da Telecontrol, ela deve
 * servir para fazer requisições HTTP[S] para qualquer API. Assim, qualquer
 * "customização" referente às APIs da TC (api e api2) devem ser feitas no
 * script que usa esta classe, e não na própria classe!!
 * Caso alguém ache realmente necessário, extender a classe e personalizar na
 * classe filha.
 */

// require_once __DIR__ . DIRECTORY_SEPARATOR . '../funcoes.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . '../helpdesk/mlg_funciones.php';

if ($_serverEnvironment =='development')
	define ('LOGPATH', '/home/manuel/test/logs/');
else
	define ('LOGPATH', '/tmp/');

class simpleREST
{

	static protected $METHODS = array('GET', 'POST', 'PUT', 'DELETE', 'OPTIONS');

	private
		$method,
		$URL,
		$statusCode,
		$statusMsg,
		$reqHeaders = array(),
		$reqData,
		$body,
		$response;
	private $params = array();

	public function __construct($url=null, $method='GET') {
		if ($url)
			$this->setUrl($url);

		if ($method)
			$this->setMethod($method);
	}

	public function __get($var) {
		if (in_array($var, ['request','requestData']))
			$var = 'reqData';
		$getters = explode(',', 'URL,reqData,allowed,statusCode,statusMsg,method,response,reqHeaders');
		if (in_array($var,$getters))
			return $this->$var;
		return null;
	}

	/**
	 * Retorna a última mensagem recebida:
	 * 200, 201 : retorna o body (sem tratar), ou o status se não tiver body
	 * resto    : retorna "CODE - Mensagem". Ex.: "500 - Internal Server Error"
	 * Se não há dados (response vazio, sem statusCode...), retorna ''
	 */
	public function __toString() {
		if (!$this->response)
			return '';

		$msg = $this->statusCode . ' - ' . $this->statusMsg;

		if ($this->statusCode == 200 or $this->statusCode == 201)
			return ($this->response['body']) ? : $msg;

		return $msg;
	}

	public function setUrl($url) {
		$this->clearParams();

		if ($this->URL == $url)
			return $this;

		if ($url and filter_var($url, FILTER_VALIDATE_URL, FILTER_NULL_ON_FAILURE)) {
			$this->clearResponseData();
			$this->URL = $url;
		}
		return $this;
	}

	/**
	 * @method setMethod(String $method)
	 * @param  String $method
	 * muda o método para o especificado.
	 */
	public function setMethod($method) {
		if (in_array(strtoupper($method), self::$METHODS)) {
			if ($this->method != strtoupper($method)) {

				$this->clearResponseData();
				$this->method = strtoupper($method);

			}
			return $this;
		}
		throw new Exception("Method '$method' not allowed!");
	}

	public function addHeader($headers) {
		if (is_string($headers)) {
			list($key, $val) = explode(':', $headers, 2);
			// pecho("New Header: $headers parsed as $key : $val");
			$this->reqHeaders[$key] = trim($val);
			return $this;
		}

		if (is_array($headers)) {
			if (array_keys($headers) === range(0, count($headers)-1)) {
				foreach($headers as $header) {
					list($key, $val) = explode(':', $headers, 2);
					$this->reqHeaders[$key] = trim($val);
				}
			}
			else {
				$this->reqHeaders = array_filter(
					array_merge($this->reqHeaders, $headers),
					'strlen'
				);
			}
		}
		return $this;
	}

	public function removeHeader($headers=null) {
		if ($headers === true or $headers == 'all') {
			$this->reqHeaders = array();

			return $this;
		}

		$orgHeaderCount = count($this->reqHeaders);
		if (is_array($headers))
			foreach ($headers as $key) {
				if (isset($this->reqHeaders[$key]))
					unset($this->reqHeaders[$key]);
			}

		if (is_string($headers) and isset($this->reqHeaders[$headers]))
			unset($this->reqHeaders[$headers]);

		if ($orgHeaderCount !== count($this->reqHeaders))
			$this->clearResponseData();

		return $this;
	}

	public function addParam($params) {
		if (is_string($params)) {
			if (strpos($params, '&')) {
				return $this->addParam(parse_str($params));
			}
			list($key, $val) = preg_split('/=/', $params, 1);
			$this->params[$key] = trim($val);
		}

		if (is_array($params)) {
			if (array_keys($params) === range(0, count($params)-1)) {
				foreach($params as $param) {
					$this->addParam(parse_str($params));
				}
			}
			else {
				$this->params = array_filter(
					array_merge($this->params, $params),
					'strlen'
				);
			}
		}
		return $this;
	}

	public function clearParams($key=null) {
		if (is_string($key) and isset($this->params[$key]))
			unset($this->params[$key]);
		else
			$this->params = array();

		return $this;
	}

	/**
	 * Adiciona um NOME DE ARQUIVO para ser enviado.
	 * O arquivo deve existir, é claro.
	 * Não é necessário adicionar o '@' ao nome.
	 * O método altera o Content-Type para form-data.
	 *
	 * @param $filename   String  Required  Path completo do arquivo
	 * @param $paramname  String  Optional  Nome do parâmetro do "form"
	 * @return Object $this
	 */
	public function addFile($filename, $paramname = 'arquivo') {
		$this->addParam(array($paramname => "@$filename"));
		$this->addHeader(array('Content-Type' => 'multipart/form-data'));
		return $this;
	}

	/**
	 * Apenas um simples setter, o body será tratado na hora de enviar
	 * a requisição.
	 */
	public function setBody($data) {
		$this->body = $data;
		$this->clearResponseData();
		return $this;
	}

	/**
	 * A diferença básica com o toString() é que se a resposta
	 * é um JSON, retorna um array ou um objeto.
	 */
	public function getBody($format=null) {
		$body = $this->response['body'];
		if (empty($body))
			return '';

		$contentType = $this->response['headers']['Content-Type'];

		switch ($contentType) {
			case 'application/json':
				return json_decode($body, ($format != 'object'));
			break;

			default:
				return $body;
			break;
		}
	}

	private function parseParams($urlencode=false, $params=null) {
		$paramArray = ($params) ? : $this->params;
		$ret = '';
		if (!empty($paramArray)) {
			if ($urlencode or $this->urlEncodedGet) {
				$ret .= http_build_query($paramArray, '&');
			} else {
				$ret .= '/';
				foreach ($paramArray as $k=>$v)
					$bp[] = "$k/$v";
				$ret .= join('/', $bp);
			}
		}
		return $ret;
	}

	private function parseBody($fmt) {
		if ($fmt == 'JSON') {
			$newBody = json_encode(Convert($this->body, 'utf8'));
		}
		if ($fmt == '' or $fmt == 'text') {
			$newBody = is_array($this->body) ? implode("\n", $this->body)
				: is_object($this->body) ? json_encode(Convert($this->body))
				: $this->body;
		}
		return $newBody;
	}

	/**
	 * ao contrário dos métodos add*(), valores passados para este
	 * método irão SUBSTITUIR os valores inseridos anteriormente.
	 */
	public function send($method=null, $headers=null, $params=null, $body=null) {

		if (!is_null($method))
			$this->setMethod($method);

		if ($headers) {
			$this->reqHeaders = null;
			$this->addHeader($headers);
		}

		if ($params) {
			$this->reqParams = null;
			$this->addParam($params);
		}

		if ($body) {
			if (!is_array($body) and $body[0] === '@') {
				$this->addFile(substr($body, 1), 'file');
				$body = null;
			}
			else
				$this->body = $body;
		}

		switch ($this->method) {

			case 'POST':
				if (_is_in('json', $this->reqHeaders)) {
					$body = json_encode(Convert($this->body, 'utf8'));
				} else if (_is_in('form-data', $this->reqHeaders)) {
					$newBody = is_array($body) ? $body : (array)$body;
					$newBody = array_merge($this->params, $newBody);
					$body = self::formData($newBody);
					unset($newBody);
				}else if (count($this->params)) {
					$this->addHeader('Content-Type: application/x-www-form-urlencoded');
					$body = $this->parseParams(true);
				} else if (is_array($this->body)) {
					$body = join(PHP_EOL, $this->body);
				} else {
					$body = $this->body;
				}
			break;

			case 'PUT':
				if (_is_in('json', $this->reqHeaders)) {
					$body = $this->parseBody('JSON');
				} else if (is_array($this->body)) {
					$body = join(PHP_EOL, $this->body);
				} else {
					$body = $this->body;
				}
				// sem break; segue para adicionar os parâmetros na URL
			case 'GET':
			case 'DELETE':
				// query string
				$this->URL .= $this->parseParams();
			break;
		}

		$headers = array();

		// if ($length = strlen($body))
		// 	$this->addHeader("Content-Length: $length");

		foreach ($this->reqHeaders as $name=>$value) {
			$headers[] = "$name: $value";
		}

		$config = array(
			'http' => array(
				'method'           => $this->method,
				'ignore_errors'    => true,
				'protocol_version' => 1.0,
				'header'           => $headers,
			)
		);

		if ($body) {
			//pecho ("O corpo da mensagem tem ".strlen($body). " bytes");
			$config['http']['content'] = $body;
		}

		if (isCLI and DEBUG === true)
			pre_echo($config, 'REQUISIÇÃO. URL '.$this->URL);

		if (isCLI and DEBUG === true)
			file_put_contents(
				LOGPATH . 'post_request.log',
				"URL: {$this->URL}\n".var_export($config['http'], true)
			);

		$stream = fopen(
			$this->URL, 'r', false,
			stream_context_create($config)
		);

		if (!$stream) {
			$this->statusCode = 504;
			$this->statusMsg  = 'Erro de conexão com o servidor';
			if (isCLI and DEBUG === true) {
				echo '-------------'.PHP_EOL;
				var_dump($stream);
				echo '-------------'.PHP_EOL;
			}
			return array(504, 'Remote Server  Unreachable');
		}

		$headers = array();
		foreach($http_response_header as $headLine) {
			if (strpos($headLine, ':')) {
				list($key, $value) = explode(':', $headLine, 2);
				if ($key === 'Date')
					$value = date('Y-m-d H:i:s', strtotime($value));
				if (preg_match('/^HTTP\/...\s(?P<code>\d{3})\s(?P<msg>.*)$/', $key, $parse)) {
					$this->statusMsg  = $headers['HTTP_STATUS']      = $parse['msg'];
					$this->statusCode = $headers['HTTP_STATUS_CODE'] = (int)$parse['code'];
					if ($value)
						$this->statusExtra = $headers['HTTP_STATUS_MESSAGE'] = $value;
				}
				$headers[$key] = trim($value);
			} else {
				if (preg_match('/^HTTP\/...\s(?P<code>\d{3})\s(?P<msg>.*)$/', $headLine, $parse)) {
					$this->statusCode = $headers['HTTP_STATUS_CODE'] = (int)$parse['code'];
					$this->statusMsg  = $headers['HTTP_STATUS']      = $parse['msg'];
					if (!isset($headers['HTTP_STATUS_MESSAGE']) and isset($headLine))
						$this->statusExtra = $headers['HTTP_STATUS_MESSAGE'] = $headLine;
				}
			}
		}

		$this->response = array(
			'response_headers' => $http_response_header,
			'status'  => $this->statusCode,
			'headers' => $headers,
			'body'    => stream_get_contents($stream)
		);

		fclose($stream);

		if (isCLI and DEBUG === true)
			file_put_contents(
				LOGPATH . 'post_request.log',
				"\nRESPONSE:\n-------\n".var_export($this->response, true),
				FILE_APPEND
			);

		if (isCLI and DEBUG === true) {
			pre_echo($this->response, 'RESPONSE');
			echo '-------------'.PHP_EOL;
		}
		return $this->response;
	}

	/**
	 * @param $postData    array   Required   Data to be parsed
	 * @param $boundary    string  Optional   Multipart part sep. (boundary)
	 * @return String   Parsed array as multipart/form-data
	 */
	public function formData(array $postData, $boundary=null, $b64=false) {
		$CRLF = "\r\n";
		$formData = '';
		if (!$boundary)
			$boundary = uniqid('--8<----TC-Posvenda-');

		foreach ($postData as $partName => $partData) {
			$subHeaders = "Content-Disposition: form-data; name=\"$partName\"";

			if ($partData[0] === '@') {
				// é um arquivo
				if (!is_readable($fn = substr($partData, 1)))
					throw new Exception ('File not found or is not readable!');
				$mime_type = mime_content_type($fn);

				$subHeaders .= '; filename="'.basename($fn).'"' . $CRLF .
					"Content-Type: $mime_type" . $CRLF;
				if ($b64) {
					$subHeaders .= 'Content-Transfer-Encoding: base64' . $CRLF;
					$partData = chunk_split(base64_encode(file_get_contents($fn)), 76);
				} else {
					$partData = file_get_contents($fn);
				}
			} else {
				if (self::isJson($partData)) {
					$subHeaders .= $CRLF .
						'Content-Type: application/json'. $CRLF.
						'Content-Transfer-Encoding: 8bit' . $CRLF;
				} else {
					$subHeaders .= $CRLF.
						'Content-Transfer-Encoding: 8bit' . $CRLF;
				}
			}

			$formData .= '--'.$boundary  . $CRLF .
				$subHeaders . $CRLF .
				$partData . $CRLF;
		}

		$this->addHeader('Content-Type: multipart/form-data; boundary='.$boundary);
		$formData .= "--$boundary--" . $CRLF;
		return $formData . $CRLF;
	}

	public static function isJson($str) {
		return  is_array(json_decode($str, true));
	}

	private function clearResponseData() {
		$this->response   = null;
		$this->statusCode = null;
		$this->statusMsg  = null;
	}
}
// vim: set noet ts=2 sts=2 fdm=syntax fdl=1 :

