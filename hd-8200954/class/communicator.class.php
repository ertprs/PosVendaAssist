<?php
/**
 * Este arquivo É ISO-8859-1 (a.k.a. 'Latin-1'), E A CODIFICAÇÃO NÃO DEVE MUDAR.
 * Se você não está vendo estes acentos corretamente,
 * FECHE O ARQUIVO SEM SALVAR, ou dê um 'git checkout'
 * se já salvou ele, e abra como Latin-1.
 */

/**
 * TcComm
 * ===
 * Classe para utilizar a API TC Communicator para enviar mensagens
 * Requisitos:
 * - simpleREST API2
 * - mlg_funciones
 *
 * TO-DO:
 *  - FORMATAR 'FROM' E 'TO' PARA RECEBER, VIA ARRAY, EMAIL E DESCRIÇÃO (A API ACEITA): Nome <Endereco@email.com>
 *  - anexos de e-mail
 *  - aceitar links como anexo, para enviar um anexo diretamente do S3
 *  - aceitar string com o conteúdo do arquivo, talvez usando um 'flag'.
 *  - campos return-path, reply-to, cc e bcc ?
 */
require_once __DIR__ . DIRECTORY_SEPARATOR . 'abstractAPI2.class.php';

class TcComm extends API2
{
	const RE_MAIL = '/(?:"?(?P<name>[.-ÿ -]+)"?\s)?<{0,1}(?P<address>[A-Za-z0-9._%-]+@([A-Za-z0-9._-]+){1,3}([.][A-Za-z]{2,4}){1,2})>?/';

	private $debug=false, $externalId,  $ref,
		$fabrica,           $name,        $fileList=array(),
		$body='',           $subject,     $to=array(),
		$returnPath=null,   $from,        $attachList=array();
	protected
		$application = 'COMMUNICATOR';
	public $why=null;

	public function __construct($externalId, $emailFrom=null) {
		$this->environment = 'PRODUCTION';
		parent::__construct();
		$this->externalId  = $externalId;
		$this->appKey      = self::$appKeys[$this->application][$this->environment];

		if ($emailFrom) {
			$this->setEmailFrom($emailFrom);
		}
	}

	public function __call($methodName, $args) {
		switch (strtolower(str_replace('_', '', $methodName))) {
			case 'adddest':
			case 'addemaildest':
				return $this->addEmailDest($args[0]);
			break;

			case 'addbody':
			case 'addtobody':
			case 'addemailbody':
			case 'addtoemailbody':
				return $this->addToEmailBody($args[0]);
			break;

			case 'setdest':
			case 'setemaildest':
				return $this->addEmailDest($args[0]);
			break;

			case 'addfile':
			case 'addanexo':
			case 'attachfile':
			case 'addemailattachment':
				return $this->addAttachment($args);
			break;

			case 'setfrom':
			case 'setfrom':
			case 'setemailfrom':
				return $this->setEmailFrom($args[0]);
			break;

			case 'unblock':
			case 'desbloqueia':
				return $this->blackListVerify($args[0]);
			break;
		}
	}

	public function __toString() {
		if ($this->error)
			return $this->error;
		if ($this->status)
			return $this->status;
		return '';
	}

	/**
	 * Adiciona endereços aos já existentes. Valida o endereço, adiciona apenas
	 * se o endereço for sintaticamente correto.
	 */
	public function addEmailDest($dest) {
		$to = self::parseEmail($dest);

		if (is_array($to) and count($to))
			foreach ($to as $addr)
				$this->to[] = $addr;
		else
			$this->to[] = $to;

		return $this;
	}

	/**
	 * Substitui os destinatários, se existem, pelos endereços do array.
	 * Apenas se há pelo menos um endereço válido.
	 * SE NÃO HOUVER, MANTÉM A LISTA ANTERIOR.
	 */
	public function setEmailDest($dest) {
		$to = self::parseEmail($dest);

		if (is_array($to) and count($to)) {
			$this->to = array();
			foreach ($to as $addr)
				$this->to[] = $addr;
		}
		return $this;
	}

	/**
	 * Se o 'from' vai ser diferente do endereço do
	 * client (externalId), pode ser alterado aqui.
	 *
	 * CORREÇÃO: o 'from' deve ser informado sempre. Porém ele não é validado.
	 */
	public function setEmailFrom($addr) {
		$end = is_array($addr) ? reset($addr) : $addr; // um endereço, o primeiro, se for array

		if (preg_match(self::RE_MAIL, $end))
			$this->from = self::parseEmail($addr);
		return $this;
	}

	/**
	 * Assunto da mensagem
	 */
	public function setEmailSubject($str) {

		if (!mb_check_encoding($str, 'UTF8')) {
			$str = utf8_encode($str);
		}
		$this->subject = (string)$str;
		return $this;
	}

	/**
	 * $body (corpo do e-mail) é public, mas cria o setter para
	 * manter o encadeamento e o "padrão"
	 */
	public function setEmailBody($corpo) {
		$this->body = $corpo;
		return $this;
	}

	/**
	 * Permite adicionar mais texto ao corpo da mensagem.
	 * Útil para compor a mensagem de maneira dinâmica.
	 *
	 * @param $texto  String  Required
	 * @return Object
	 */
	public function addToEmailBody($text) {
		if (!is_string($text))
			$text = join("\n", $text); // Não é para fazer...

		if (is_array($this->body))
			$this->body[] = $text;
		else
			$this->body .= $text;
		return $this;
	}

	/**
	 * NÃO IMPLEMENTADO
	 * $file pode ser:
	 *
	 *  - um _hash_ associado a um arquivo no TDocs
	 *  - o nome do arquivo ou
	 *  - um array $_FILES
	 * é recomendado, para ter mais controle, usar o nome do
	 * arquivo ou um _hash_.
	 *
	 * Os arquivos do TDocs serão anexados ao e-mail pela API2.
	 * Os arquivos recebidos pelo método, serão enviados ao TDocs
	 * (sem vinculação com o banco de dados) e o _hash_ enviado
	 * na hora da transmissão do e-mail.
	 */
	public function addAttachment($file) {
		if (preg_match('/^[a-z0-9]{64}$/', $file)) {
			$this->attachList[] = $file;
			return $this;
		}

		if (!is_array($file) and !is_url($file)) {
			if (!is_readable($file))
				throw new Exception ('Arquivo para anexar não é legível');
			$this->fileList[] = $file;
		} else if (is_array($file)) {
			foreach ($file as $fn) $this->addAttachment($fn);
		}
		return $this;
	}

	/**
	 * Este método não permite, por enquanto, o envio de anexos.
	 * O parâmetro 'from' está no final porque normalmente ele será
	 * já selecionado no construtor ao processar o ID do fabricante
	 * e, na verdade, o 'from' deveria depender da Tc communicator
	 * (mas não é o caso).
	 */
	public function sendMail($to=null,$subj=null,$body=null,$from=null) {
		if ($from)
			$this->setEmailFrom($from);

		if (!is_null($to))
			$this->setEmailDest($to);

		if (!is_null($subj))
			$this->setEmailSubject($subj);

		if (!is_null($body))
			$this->body = $body;

		if (is_null($this->ref)) {
			$metadata = self::guessKeyFields($this->fabrica);

			$this->ref = array(
				'type' => 'PosVenda-'.$metadata['script'],
				'value' => http_build_query($metadata,'&')
			);
		}

		if (!$this->token)
			$this->fetchNewToken();

		// confere e "converte" o body se é um array.
		// Não deveria, mas...
		if (is_array($this->body)) {
			$newBody = implode(PHP_EOL, $this->body);
			$this->body = (strip_tags($newBody) != $newBody) ?
				$newBody : nl2br($newBody);
		}

		if (!mb_check_encoding($this->body, 'UTF8')) {
			$this->body = utf8_encode($this->body);
		}

		if (count($this->fileList)) {
			$this->prepareAttachments();
		}

		// if (count($this->fileList)) {
		// 	$this->body = $this->multipartEmail();
		// 	if ($this->debug)
		// 		file_put_contents(__DIR__ . '/../../attach.eml', $this->body);
		// 	// die;
		// }

		// criado array, pode ser alterado e usado para outros fins,
		// como log, retorno de erro, etc.
		$contents = array(
			'reference' => $this->ref,
			'from'      => (($this->from) ? : $this->externalId),
			'to'        => $this->to,
			'subject'   => $this->subject,
			'body'      => $this->body,
		);

		if (count(array_filter($this->attachList))) {
			$contents['attachments'] = $this->attachList;
		}

		$this->api->setMethod('POST')
			->setUrl(self::API2 . '/communicator/email')
			->addHeader(array(
				'Content-Type' => 'application/json',
				'smtp-account' => $this->externalId))
				->setBody($contents)
				->send();

		$ret = $this->api->response;

		$msg = ($this->api->statusCode < 203) ? json_encode($contents) : $this->api;
		
		if ($this->debug === true) {
			$logMsg = sprintf(
				"\n-- [%s] ------\nLAUNCHED FROM: %s\nMESSAGE FROM TCOM: %s\n",
				date('Y-m-d H:i:s'),
				$_SERVER['PHP_SELF'],
				$msg
			);
			file_put_contents('/tmp/mailer.log', $logMsg, FILE_APPEND);
		}

		if ($this->api->statusCode < 203)
			return true;

		$this->error = $this->api;
		$this->status = $this->api->statusCode;
		return false;
	}

	private function prepareAttachments() {
		include_once __DIR__ . DIRECTORY_SEPARATOR . 'tdocs.class.php';
		$this->tdocs = new TDocs($GLOBALS['con'], 10);
		foreach ($this->fileList as $file) {
			$att = $this->tdocs->sendFile($file);

			if (!is_bool($att)) {
				$this->attachList[] = $att;
				continue;
			}
			$this->error[] = "O arquivo $file não foi anexado.";
		}
	}

	/**
	 * Métodos para gerenciar a BlackList
	 */

	/**
	 * Consultar a blacklist.
	 * A API permite validar apeas um endereço por requisição.
	 * Este método pode validar vários endereços, se recebe um array.
	 * Se tem mais de um e-mail para validar, também irá retornar um
	 * array que contém os endereços INVÁLIDOS.
	 */
	public function isBlocked($email) {
		$checkList = self::parseEmail($email);

		if (count($checkList)) {
			foreach ($checkList as $idx => $addr) {
				if (!$this->token)
					$this->fetchNewToken();

				$this->api->setMethod('GET')
					->setUrl(self::API2 . '/communicator/emailBlackList')
					->addParam(array('email' => $addr))
					->send();

				if ($this->api->statusCode == 404) {
					if (count($checkList) === 1)
						return false;
					$checkList[$idx] == null; // retira o endereço
				}

				if ($this->api->statusCode == 200) {
					$response = json_decode($this->api, true);
					$checkList[$idx] = array(
						'email' => $addr,
						'motivo' => $response['justification'],
						'data' => $response['created_at']
					);
				}
			}
		}

		$ret = array_filter($checkList);
		if (count($ret) == 1) {
			$this->why = $response['justification'];
			return true;
		}

		return $ret;
	}

	/**
	 * Marca o e-mail informado como verificado na blacklist, para permitir o envio.
	 */
	public function blackListVerify($email) {
		// Um a um... não pode receber uma lista
		$address = self::parseEmail($email);

		if (count($address) !== 1) {
			$this->error = 'Apenas um e-mail para desbloquear da BlackList';
			return null;
		}

		if (!$this->token)
			$this->fetchNewToken();

		$this->api->setMethod('PUT')
			->setUrl(self::API2 . '/communicator/emailBlackList')
			->addParam(array('email' => $address[0]))
			->send();

		if ($this->api->statusCode == 200)
			return true;

		return false;
	}

	private static function guessKeyFields($fabrica) {
		$ids = array_intersect(
			array('os','callcenter','hd_chamado','hd_chamado_item','extrato','faturamento','pedido','comunicado'),
			array_keys($_REQUEST)
		);

		if (count($ids)) {
			$idField = reset($ids);
			$id[$idField] = $_REQUEST[$idField];
		}

		if (isset($_COOKIE['cook_posto']))
			$id['posto'] = $_COOKIE['cook_posto'];

		if (isset($_COOKIE['cook_login_unico']))
			$id['login_unico'] = $_COOKIE['cook_login_unico'];

		if (isset($_COOKIE['cook_admin']))
			$id['admin'] = $_COOKIE['cook_admin'];

		$id['fabrica'] = $fabrica;
		$id['script'] = pathinfo($_SERVER['PHP_SELF'], PATHINFO_BASENAME);

		if (count($id))
			return $id;

		return null;
	}

	public static function parseEmail($addr) {

		if (is_array($addr))
			$addr = implode(',', array_filter(array_map('trim', $addr)));
		else if (strpos($addr, ',') !== false) {
			$addr = array_filter(array_map('trim', preg_split('/,|;/', $addr)), 'strlen');
			$addr = implode(',', $addr);
		}
		else if (!strlen($addr))
			return '';

		preg_match_all(
			self::RE_MAIL,
			$addr,
			$res, PREG_SET_ORDER
		);

		// formata os endereços no formato RFC822 et al.
		// mas como string
		$retArr = array();
		foreach ($res as $parsed) {
			$name = str_replace('"','',$parsed['name']);
			$end  = $parsed['address'];
			if (strpos($name, ' '))
				$name = "\"$name\"";

			// edita a repetição de emails no mesmo parse
			if (!in_array($end, $retArr))
				$retArr[] = $end; // Por enquanto a Communicator não aceita nome+endereço
			// $retArr[] = (strlen($name)) ? "$name <$end>" : $end;

		}
		return $retArr;
	}

	/**
	 * Atualiza os atributos para poder enviar email com anexo(s)
	 */
	private function multipartEmail($body=null) {
		$arquivos = $this->fileList;
		$htmlMsg = $body ? : $this->body;
		$boundary = uniqid('TCM_');

		if (!mb_check_encoding($htmlMsg, 'UTF8')) {
			$htmlMsg = utf8_encode($htmlMsg);
		}

		$isPlainText = preg_match('/<(\w+)[^>]*>.*<\/\1>/', $htmlMsg) === 0;

		foreach ($arquivos as $att) {
			if (is_array($anexo = self::file_to_eml_part($att, $boundary))) {
				$file_contents .= $anexo['eml_part'];

				if (isset($anexo['cid']) and !$isPlainText) {
					$htmlMsg .= (!$att_link) ? "\n<br /><img src=\"cid:" . $anexo['cid'] . "\" />\n"
						: "\n<br /><a href='$att_link' target='_blank'>" .
						"<img src=\"cid:" . $anexo['cid'] . "\" /></a>\n";
				}
			} else {
				$file_contents .= $anexo;
			}
			unset($anexo);
		}

		$CT = ($isPlainText) ?
			'Content-Type: text/plain; charset="UTF-8"' :
			'Content-Type: text/html; charset="UTF-8"';

		if ($file_contents) {
			return <<<HTMLMSGBODY
Content-Type: multipart/mixed; boundary="$boundary"
--$boundary
Content-Transfer-Encoding: 8bit
$CT

$htmlMsg
$file_contents--$boundary--
HTMLMSGBODY;
		} else {
			$headers[] = "Content-Transfer-Encoding: 8bits\n";
			$headers[] = $CT . "\r\n\r\n";

			$mensagem = $htmlMsg;
		}

		return join("\r\n", $headers) . "\r\n" . $mensagem;

	}

	private static function file_to_eml_part($arquivo, $bounds=null, $attach_name=null) {

		if (!is_readable($arquivo))
			return $this;
		$fname = preg_replace('/[^A-Za-z0-9.-]/', '_', basename($arquivo));

		if (!is_null($attach_name))
			$fname = preg_replace('/[^A-Za-z0-9.-]/', '_', basename($attach_name));

		$mime = mime_content_type($arquivo);
		$disp = preg_match('/gif|png|jpg|pjpeg|jpeg|bmp/', $mime) ? 'inline' : 'attachment';
		$b64  = chunk_split(base64_encode(file_get_contents($arquivo)), 76, "\n");

		if ($disp == 'inline') {
			$cid = md5(date('mdYhis') . $fname);
			$inline_headers = "Content-ID: <$cid>\nX-Attachment-Id: $cid";
		} else {
			$cid  = null;
		}

		$a[] = "Content-Type: $mime";
		$a[] = "Content-Transfer-Encoding: base64";
		$a[] = "Content-Disposition: $disp; filename=\"$fname\"";
		if ($cid)
			$a[] = $inline_headers;

		$ret = implode("\n", $a) . chr(10) . chr(10) . $b64;

		if ($bounds)
			$ret = "\n--$bounds\n" . $ret;

		if ($cid)
			return array(
				'eml_part' => $ret,
				'cid'     => $cid
			);

		return $ret;
	}

}
// vim: set noet ts=2 sts=2 fdm=syntax fdl=1 :
