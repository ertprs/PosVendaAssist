<?php
/**
 * Este arquivo É UTF8, E A CODIFICAÇÃO NÃO DEVE MUDAR.
 * Se você não está vendo estes acentos corretamente,
 * FECHE O ARQUIVO SEM SALVAR, ou dê um 'git checkout'
 * se já salvou ele, e abra como UTF8.
 */
require_once __DIR__ . DIRECTORY_SEPARATOR . 'simple_rest.class.php';

abstract class API2 {

	const API2 = 'https://api2.telecontrol.com.br';
	protected $api,    // obj. simpleREST
		// $application,  // Nome do serviço: COMMUNICATOR, TDOCS, IMAGEUPLOADER...
		$appKey,       // a subclasse deve informar qual appKey usar
		$environment,  // Ambiente na API (é independente do PosVenda!): DEVEL, PRODUCTION...
		$token;
	static protected $appKeys = array(
		'COMMUNICATOR' => array(
			'DEVEL'        => '7a19442b1042d95c6da0f9647a2de83e71a3944c',
			'HOMOLOGATION' => 'b358ff16d2e87833826a1f02b63a88badb430983',
			'PRODUCTION'   => '3c8f3fbd89576e1116c185dc31302be433c577c0'
		),
		'TDOCS' => array(
			'DEVEL'        => '5dd88af03d8b225a8f0700bbdd1b754d5b112b82',
			'HOMOLOGATION' => '1cdb77710a63a375585f9331fdab7d394f485b51',
			'PRODUCTION'   => '32e1ea7c54c0d7c144bc3d3045d8309a5b137af9'
		),
		'IMAGE-UPLOADER' => array(
			'DEVEL'        => '9541d8a9a4bf5ff25488976b70eeb0c413fb89d4',
			'HOMOLOGATION' => 'fcbe7e2ae586efb6d928a2f254c5c49d07679d22',
			'PRODUCTION'   => 'cab877cea124389b62b78ff76b1eb297743e94b5'
		),
		'CALLCENTER' => array(
			'DEVEL'        => '519e67fe737c5de1c5656f1c08f9eac902c5eb25',
			'HOMOLOGATION' => '1cdb77710a63a375585f9331fdab7d394f485b51',
			'PRODUCTION'   => '701c59e0eb73d5ffe533183b253384bd52cd6973'
		),
		'AUDITOR' => array(
			'DEVEL'        => 'daf98543c487af6ceb230cae002c92fd',
			'HOMOLOGATION' => 'daf98543c487af6ceb230cae002c92fd',
			'PRODUCTION'   => '02b970c30fa7b8748d426f9b9ec5fe70'
		),
	);

	public function __construct() {
		$this->api = new simpleREST;
		$this->getAppKeys();
	}

	public function setEnvironment($env) {
		if (in_array(strtoupper($env), array_keys(self::$appKeys[$this->application])))
			$this->environment = strtoupper($env);
		$this->api->addHeader('access-env: '.$this->environment);
	}
	/**
	 * Solicita um token para a aplicação, insere os HEADERs de autenticação
	 */
	protected function fetchNewToken($env=null) {
		$this->environment = $env ? : $this->environment;

		$this->getAppKeys($this->application);
		// $this->appKey = self::$appKeys[$this->application][$this->environment];

		$this->api->setUrl(self::API2 . '/AccessControl/token/application-key/'.$this->appKey)
			->setMethod('POST')
			->addHeader('Content-Type: application/json')
			->setBody(array(
				'application-key' => $this->appKey,
				'application' => $this->application
			))
			->send();

		if ($this->api->statusCode == 201) {
			$response = json_decode($this->api->response['body']);
			$this->token = $response->token;

			$this->api->addHeader("access-application-key: {$this->appKey}")
				->addHeader("access-token: {$this->token}")
				->addHeader("access-env: ".$this->environment);
			return $this;
		}

		$this->token = false;
		$this->error = $this->api;
		return false;
	}

	protected function getAppKeys($service=null, $env=null) {
		$service     = $service ? : $this->application;
		$environment = $env     ? : $this->environment;

		// Não conecta a não ser que não exista o serviço no atributo appKeys.
		if (isset(self::$appKeys[$service][$environment]) and count(self::$appKeys[$service]) > 0) {
			$this->appKey = self::$appKeys[$service][$environment];
			$this->api->addHeader("access-application-key: {$this->appKey}")
				->addHeader("access-env: ".$environment);
			return true;
		}
		// else
		$this->api
			->setUrl(self::API2 . '/AccessControl/application-key')
			->send( 'GET', null, array(
					'client-code' => '10',
					'application' => $service
				)
			);

		$res = json_decode($this->api->response['body'], true);

		foreach($res as $appData) {
			$keyEnv = $appData['key_type']['system_code'];
			if (!$appData['blocked']) // apenas as que não estiverem bloqueadas
			$keys[$keyEnv] = $appData['application_key'];
		}
		self::$appKeys[$service] = $keys;
		$this->appKey = self::$appKeys[$service][$environment];
		$this->api->addHeader("access-application-key: {$this->appKey}")
			->addHeader("access-env: ".$environment);
	}

	static public function utf8_ascii7($str) {
		$dict = array (
			'a' => '/[áàãâä]/', 'e' => '/[éèêë]/', 'i' => '/[íìïî]/', 'o' => '/[óòôõö]/', 'u' => '/[úùüû]/',
			'A' => '/[ÁÀÃÂÄ]/', 'E' => '/[ÉÈÊË]/', 'I' => '/[ÍÌÏÎ]/', 'O' => '/[ÓÒÔÕÖ]/', 'U' => '/[ÚÙÜÛ]/',
			'n' => '/ñ/', 'c' => '/ç/', 'N' => '/Ñ/', 'C' => '/Ç/',
		);

		if (mb_check_encoding($str, 'UTF8'))
			foreach($dict as $k=>$v)
				$dict[$k] .= 'u';
		else
			foreach($dict as $k=>$v)
				$dict[$k] = utf8_decode($dict[$k]);

		return preg_replace(
			array_values($dict),
			array_keys($dict),
			$str
		);
	}

    static function ConverteUTF8($Array){
        if (is_array($Array)) {
            array_walk_recursive($Array, function(&$item, $key){
                if(!mb_detect_encoding($item, 'utf-8', true)){
                    $item = utf8_encode($item);
                }
            });
        }else{
            $Array = utf8_encode($Array);
        }

        return $Array;
    }

    static function ConverteLatin1($Array){
        if (is_array($Array)) {
            array_walk_recursive($Array, function(&$item, $key){
                if(mb_detect_encoding($item, 'utf-8', true)){
                    $item = utf8_decode($item);
                }
            });
        }else{
            $Array = utf8_decode($Array);
        }

        return $Array;
    }
}
