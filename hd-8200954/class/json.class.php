<?php
/**
 * Documentação: ./JsonClass.md
 * TODO:
 * Descobrir como trabalhar além do primeiro nível do array...
 *   Criar um método public findKey() ou private traverse()
 *   o primeiro parâmetro seria a key a buscar, o segundo limitaria
 *   a busca (false para o primeiro encontrado, true para todos?
 *   numérico seria X encontrados) um terceiro parâmetro opcional
 *   seria o novo value.
 *   Se o primeiro parâmetro for um array, a key seria a chave a buscar
 *   mas tem que ter o valor do value... ???
 *
 * Ideias:
 * removeItem() poderia receber um 2º parâmetro por referência, e devolver o valor excluído nesse parâmetro
 * removeItem() achar um jeito de localizar e excluir valores aninhados
 *
 * set() adicionar validações
 * set() adicionar elementos dentro de outros, talvez com um parâmetro adicional:
 *       $j->set('{k:v}', 'key0/key1');
 *       que informe a "rota a seguir"
 * igual para o removeItem()
 * igual para o __get(): se for uma "rota", devolver esse conteúdo
 *
 */
include_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'helpdesk/mlg_funciones.php';

class Json
{
	public
		$last_error = '',
		$data       = [];
	private
		$lastIndex = null,    // nome da última chave excluída
		$lastValue = null,    // valor/conteúdo do último elemento excluido
		$encoding  = 'ISO-8859-1', // $_SERVER['HTTP_CONNECTION'] ? 'ISO-8859-1' : 'UTF-8',
		$throw_errors = true;

	public function __construct($json=null, $throw_exceptions=false) {
		$this->throw_errors = (bool)$throw_exceptions;
		if (!is_null($json)) {
			$conv = mb_convert_variables('UTF-8', 'Latin-1,UTF-8,HTML-ENTITIES', $json);
			if (!$conv) {
				$this->last_error = 'Parse error: Input data cannot be converted to UTF-8.';
				if ($this->throw_errors === true)
					throw new \Exception("Input data cannot be converted to UTF-8.");
				return false;
			}
			switch (gettype($json)) {
				case 'NULL':
				case 'string':
					if ($json == '') $json = '[]';

					$this->data = json_decode($json, true);

					if (!is_array($this->data)) {
						$error = json_last_error();
						if ($error == JSON_ERROR_UTF8 or $error == JSON_ERROR_CTRL_CHAR) {
							$this->data = json_decode(utf8_decode($data), true);
						}

						if (!is_array($$this->data))
							$error = json_last_error();

						if ($error == JSON_ERROR_STATE_MISMATCH or $error == JSON_ERROR_SYNTAX) {
							$this->last_error = json_last_error_msg();
						}
						if ($error and $this->throw_errors)
							throw new \Exception("JSON: {$this->last_error}");
					}
				break;

				case 'array':
					$this->data = $json;
				break;

				case 'object':
					if (get_class($json) == 'Json') {
						$this->data = $json->data;
					} else {
						$this->data = json_encode(json_decode($json), true);
					}
				break;

				default:
					$this->data = array();
				break;
			}
		}
	}

	// Retorna o valor de um elemento do JSON
	public function __get($element) {
		if ($element == 'lastKey' or $element == 'lastIndex') {
			return $this->lastIndex;
		}
		if ($element == 'lastVal' or $element == 'lastValue') {
			return $this->lastValue;
		}

		if (array_key_exists($element, $this->data)) {
			$retVal  = $this->data[$element];
			$retData = !is_array($retVal) ? $retVal :
				$this->data[$element];

			return mb_convert_encoding($retData, $this->encoding, 'UTF-8');
		}

		//	return json_encode(array("$element"=>$this->data[$element]));
		return '';
	}

	public function __set($prop, $value) {

		if (defined('DEV') and DEV === true and DEBUG === true) {
			pre_echo(func_get_args(), 'SET ARGUMENTS');
			pre_echo("SET TO `$value`", $prop);
		}

		$attr = strtolower(str_replace('_','', $prop));

		if ($attr == 'throwerrors' or $attr == 'throwexceptions') {
			$this->throw_errors = (bool) $value;
			return $this;
		}

		// Muda a codificação de saída se for aceita pelo PHP
		if ($attr == 'encoding' or $attr == 'enc') {
			$this->encoding = in_array($value, mb_list_encodings())
				? $value : $this->encoding;
			return $this;
		}

		$tmp = $this->data;

		if (!mb_convert_variables('utf-8', 'ISO-8859-1,HTML-ENTITIES', $value)) {
			if ($this->throw_errors)
				throw new \Exception("Input data cannot be converted to UTF-8.");
			$this->last_error("Input data cannot be converted to UTF-8.");
			return false;
		}

		if (is_string($value)) {
			$val = self::isJson($value) ?
				json_decode($value, true) : $value;
			$tmp[$prop] = $val;
		}

		if (is_array($value))
			$tmp[$prop] = $value;

		if (!json_encode($tmp)) {
			$this->last_error = "Error adding or updating attribute '$prop'.";
			if ($this->throw_errors)
				throw new \Exception($this->last_error);
			return false;
		}
		$this->data = $tmp;
		return $this;
	}

	public function __call($fn, $params) {
		if (defined('DEV') and DEV === true) {
			pre_echo(func_get_args(), 'CALL ARGUMENTS');
			pre_echo($params, $fn);
		}

		$fn = strtolower(str_replace('_','',$fn));

		switch ($fn) {
			case 'add':
			case 'push':
			case 'append':
			case 'insert':
				return $this->set($params[0]);
			break;

			case 'rm':
			case 'del':
			case 'unset':
			case 'delete':
			case 'remove':
				$this->removeItem($params);
			break;

      case 'setencoding':
      case 'encoding':
      case 'setenc':
        $this->encoding = in_array($params[0], mb_list_encodings())
          ? $value : $this->encoding;
        return $this;
      break;

			case 'throwerrors':
				if (is_bool($params[0]))
					$this->throw_errors = $params[0];
				return $this;
			break;

			case 'tostring':
				return "$this";
			break;

		}

	}

	public function __toString() {
		if (!is_array($this->data))
			return '';
		if (!count($this->data))
			return '{}';
		return (string)json_encode($this->data);
	}

	private function exceptions($throw=true) {
		$this->throw_errors = (bool)$throw;
	}

	public function toArray($key=null) {
		if (!is_array($this->data))
			return array();

		if (!empty($key) and array_key_exists($key, $this->data)) {
			$ret = $this->data;
			mb_convert_variables($this->encoding, 'UTF-8', $ret);
			return $ret;
		}
		return $this->data;
	}

	public function toObject($key=null) {
		if (!is_array($this->data))
			return null;

		if (!empty($key) and array_key_exists($key, $this->data))
			return json_decode(json_encode($this->data[$key]));

		return json_decode(json_encode($this->data));
	}

	public function set($field) {
		if (!mb_convert_variables('utf-8', 'latin-1,HTML-ENTITIES', $field)) {
			$this->last_error("Input data cannot be converted to UTF-8.");
			if ($this->throw_errors)
				throw new \Exception($this->last_error);
			return $this;
		}

		if (is_string($field)) {
			if (self::isJson($field)) {
				$field = json_decode($field, true);
			} else {
				$this->last_error = 'Parse error';
				if ($this->throw_errors === true)
					throw new \Exception('Parse error');
				return $this; // ...ou nao faz nada
			}
		} else if (is_object($field)) {
			$field = json_decode(json_encode($field), true);
		}
		$newData = self::checkUtf8($field);
		$this->data = array_merge($this->data, $field);
		return $this;
	}

	public function removeItem($key) {
		$fargc = func_num_args();
		$fargv = func_get_args();
		$keys = array();

		if (count($fargv) == 1 and is_array($fargv[0])) {
			$newarg = $fargv[0];
			unset($fargv);
			$fargv = $newarg;
			unset($newarg);
		}

		foreach ($fargv as $a=>$arg) {
			if (defined('DEV') and DEV === true) {
				pre_echo($arg, "Argumento $a:");
			}
			if (is_array($arg))
				$keys = array_merge($keys, $arg);
			else
				$keys[] = $arg;
		}

		if (!count($keys))
			return $this;

		foreach($keys as $key) {

			if (defined('DEV') and DEV === true)
				echo "Excluir $key .. \n";

			if (array_key_exists($key, $this->data)) {
				$this->lastValue = $this->data[$key];
				$this->lastIndex = $key;
				unset($this->data[$key]);
			}
		}
		return $this;
	}

	public function shift() {
		if (!is_array($this->data) or count($this->data) == 0)
			return $this;

		$key = reset(array_keys($this->data));
		return $this->removeItem($key);
	}

	public function pop() {
		if (!is_array($this->data) or count($this->data) == 0)
			return this;

		$key = end(array_keys($this->data));
		return $this->removeItem($key);
	}

  private static function path_exists($path, array $data) {
		$search_for = preg_split('#[^\\]/#', $path);

		foreach($sarch_for as $arrKey) {
			if (!array_key_exists($arrKey, $data))
				return false;

			// else, down one level
			$data = $data[$arrKey];
		}
		return true;
	}

	public static function isJson($str) {
		return  is_array(json_decode($str, true));
	}

	public static function checkUtf8($text) {
		if (!mb_convert_variables('utf-8', 'latin-1,HTML-ENTITIES', $text)) {
			$this->last_error("Input data cannot be converted to UTF-8.");
			return null;
		}
		return $text;
	}
}

// vim: ts=2:sw=2:sts=2:fdl=1:noet
