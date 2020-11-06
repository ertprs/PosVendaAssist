<?php
/**
 * PHPDoc:
 * @access:     public
 * @author:     Manuel López
 * @copyright:  © 2008-17 Manuel López
 * @internal:   Variables, arrays y funciones varias de uso frecuente
 * @version:    1.20
 * Adicionada inicialização de algumas variáveis
 **/

// Defines...
// define('DEBUG', true);
if (!defined('DEBUG'))
	define('DEBUG', false);

if (!defined('APP_DIR')) define('APP_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR);
if (!defined('isCLI'))   define('isCLI',   (PHP_SAPI == 'cli'));
if (!defined('isRoot'))  define('isRoot',  (posix_geteuid() == 0));
if (!defined('PHP_TAG')) define('PHP_TAG', '<?php'.PHP_EOL);
if (!defined('PHPTAG'))  define('PHPTAG',  '<?php'.PHP_EOL);

if (!defined('RE_URL'))   define('RE_URL',   '/^(?P<protocol>https?:\/\/)?(?P<server>[-\w]+\.[-\w\.]+)(:?\w\d+)?(?P<path>\/([-~\w\/_\.]+(\?\S+)?)?)*$/');
if (!defined('RE_EMAIL')) define('RE_EMAIL', '/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*\.(([0-9]{1,3})|([a-zA-Z]{2,3})|(aero|coop|info|museum|name))$/');

if (!defined('RE_TIME'))        define('RE_TIME',        '/^(([0-1]\d|2[0-3]):[0-5]\d)$/');
if (!defined('RE_DATE'))        define('RE_DATE',        '/^(([0-2]\d|3[01])\W(0[1-9]|1[0-2])\W((19|20|21)\d{2}))$/');
if (!defined('RE_ISODATE'))     define('RE_ISODATE',     '/^(((19|20|21)\d{2})\W(0[1-9]|1[0-2])\W([0-2]\d|3[01]))$/');
if (!defined('RE_DATETIME'))    define('RE_DATETIME',    '/^(([0-2]\d|3[01])\W(0[1-9]|1[0-2])\W((19|20|21)\d{2}))\s(([0-1]\d|2[0-3]):[0-5]\d)$/');
if (!defined('RE_ISODATETIME')) define('RE_ISODATETIME', '/^(((19|20|21)\d{2})\W(0[1-9]|1[0-2])\W([0-2]\d|3[01]))\s(([0-1]\d|2[0-3]):[0-5]\d)$/');

if (!defined('RE_FMT_CNPJ'))    define('RE_FMT_CNPJ', '/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/');
if (!defined('RE_FMT_CPF'))     define('RE_FMT_CPF',  '/(\d{3})(\d{3})(\d{3})(\d{2})/');
if (!defined('RE_FMT_CEP'))     define('RE_FMT_CEP',  '/(\d{2})(\d{3})(\d{3})/');

define('INT_FIELDS',
	// informações pessoais
	'cep|cnpj|senha|cpf|celular|(tele)?(ph|f)one|\b(rg|ie|im)\b|compl(emento)?|(contato_)?numero|'.
	// códigos vários
	'cfop|nota_fiscal|barras|ibge|chave(_nfe)?|recibo|'.
	// pseudo-IDs
	'referencia|sua_os|seu_hd|codigo_posto|categoria|codigo'
);

// RegEx para data e hora
define('YEAR_ATOM',     '(?P<Y>[0-9]{4})');
define('MONTH_ATOM',    '(?P<M>1[0-2]|0[1-9])');
define('DAY_ATOM',      '(?P<D>3[0-1]|0[1-9]|[1-2][0-9])');
define('HOUR_ATOM',     '(?P<h>2[0-3]|[0-1][0-9])');
define('MIN_ATOM',      '(?:\:(?P<m>[0-5][0-9]))');
define('SEC_ATOM',      '(?:\:(?P<s>[0-5][0-9]))');
define('DECTIME',       '(?P<decTime>\.\d+)?');       // Decimal part for date or time as per ISO 8601
define('TIMEZONE',      '(?P<TZ>Z|[+-](?:2[0-3]|[0-1][0-9]):[0-5][0-9])');
define('ISO_DATE',      YEAR_ATOM . '-'        . MONTH_ATOM . '-'        . DAY_ATOM);
define('EUR_DATE',      DAY_ATOM  . '[.\/-]'   . MONTH_ATOM . '[.\/-]'   . YEAR_ATOM);
define('ISO_CDATE',     YEAR_ATOM .              MONTH_ATOM . DAY_ATOM); // COMPACT DATE: YYYYMMDD
define('ISO_TIME',      HOUR_ATOM .              MIN_ATOM   . '?'        . SEC_ATOM   . '?');
define('ISO_DATETIME',  ISO_DATE  . '(?:\s|T)' . ISO_TIME   . DECTIME    . TIMEZONE   . '?');
define('ISO_CDATETIME', ISO_CDATE . 'T?'       . HOUR_ATOM  . MIN_ATOM   . SEC_ATOM);
define('EUR_DATETIME',  EUR_DATE  . '(?:\s|T)' . ISO_TIME   . DECTIME );

if (!defined('CR'))   define('CR',   chr(13));
if (!defined('LF'))   define('LF',   chr(10));
if (!defined('ESC'))  define('ESC',  chr(27));
if (!defined('TAB'))  define('TAB',  chr( 9));
if (!defined('CRLF')) define('CRLF', chr(13).chr(10));

/**
 * php > echo preg_match(RE_DATETIME, '21/12/1970 11:00', $dt);
 * 1
 * php > print_r($dt);
 * Array
	(
		[0] => 21/12/1970 11:00
		[1] => 21/12/1970
		[2] => 21
		[3] => 12
		[4] => 1970
		[5] => 19
		[6] => 11:00
		[7] => 11
	)
 * php > list($dataehora_inicial, $data_inicial,$dia,$mes,$ano,,$hora) = $dt;
 * php > echo "$ano-$mes-$dia $hora";
 * 1970-12-21 11:00
 **/

// Declaração de variáveis usadas normalmente
global $Dias, $Meses, $arrayEstados, $regioesBR, $estados_BR;
// Dias e Meses do ano. Os dias começam com o '0' em Domingo, para ficar
//   igual o padrão do pSQL e PHP, fica mais fácil de mexer
$Dias = array(
	'pt-br' => array(
		'Domingo',      'Segunda-feira', 'Terça-feira',
		'Quarta-feira', 'Quinta-feira',  'Sexta-feira',
		'Sábado',       'Domingo'),
	'es'  => array(
		'Domingo', 'Lunes',   'Martes',   'Miércoles',
		'Jueves',  'Viernes', 'Sábado' ),
	'en-us' => array(
		'Sunday',   'Monday', 'Tuesday',  'Wednesday',
		'Thursday', 'Friday', 'Saturday')
);

$Meses = $meses_idioma = array(
	'pt-br' => array(1 =>
		'Janeiro',  'Fevereiro', 'Março',    'Abril',
		'Maio',     'Junho',     'Julho',    'Agosto',
		'Setembro', 'Outubro',   'Novembro', 'Dezembro'
	),
	'es'    => array(1 =>
		'Enero',      'Febrero', 'Marzo',     'Abril',
		'Mayo',       'Junio',   'Julio',     'Agosto',
		'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
	),
	'en-us' => array(1 =>
		'January',   'February', 'March',    'April',
		'May',       'June',     'July',     'August',
		'September', 'October',  'November', 'December'
	)
);

$arrayEstados = $estadosBrasil = $estados = array(
	'AC' => 'Acre',             'AL' => 'Alagoas',             'AM' => 'Amazonas',
	'AP' => 'Amapá',            'BA' => 'Bahia',               'CE' => 'Ceará',
	'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo',      'GO' => 'Goiás',
	'MA' => 'Maranhão',         'MG' => 'Minas Gerais',        'MS' => 'Mato Grosso do Sul',
	'MT' => 'Mato Grosso',      'PA' => 'Pará',                'PB' => 'Paraíba',
	'PE' => 'Pernambuco',       'PI' => 'Piauí',               'PR' => 'Paraná',
	'RJ' => 'Rio de Janeiro',   'RN' => 'Rio Grande do Norte', 'RO' => 'Rondônia',
	'RR' => 'Roraima',          'RS' => 'Rio Grande do Sul',   'SC' => 'Santa Catarina',
	'SE' => 'Sergipe',          'SP' => 'São Paulo',           'TO' => 'Tocantins'
 );

$regioesBR = array(
	'Norte' => array(
		'AC' => 'Acre',     'AM' => 'Amazonas', 'AP' => 'Amapá', 'PA' => 'Pará',
		'RO' => 'Rondônia', 'RR' => 'Roraima',  'TO' => 'Tocantins'
	),
	'Nordeste' => array(
		'AL' => 'Alagoas',  'BA' => 'Bahia',   'CE' => 'Ceará',
		'MA' => 'Maranhão', 'PB' => 'Paraíba', 'PE' => 'Pernambuco',
		'PI' => 'Piauí',    'SE' => 'Sergipe', 'RN' => 'Rio Grande do Norte'
	),
	'Centro-Oeste' => array(
		'DF' => 'Distrito Federal', 'GO' => 'Goiás',
		'MT' => 'Mato Grosso',      'MS' => 'Mato Grosso do Sul'
	),
	'Sud-Este' => array(
		'ES' => 'Espírito Santo', 'MG' => 'Minas Gerais',
		'RJ' => 'Rio de Janeiro', 'SP' => 'São Paulo'
	),
	'Sul' => array(
		'PR' => 'Paraná', 'RS' => 'Rio Grande do Sul', 'SC' => 'Santa Catarina'
	)
);

$estados_BR = array_keys($estadosBrasil);

$specialchars = ".,/°ªáâàãéâèíîìóôòõúùüñ¿?¡!·$%&()^*[]{}¨çÇ`´|©®\\\"'@#~";

if (!function_exists('mt_substr')) {
	include_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'mt_substr.inc.php';
}

if (!function_exists('iif')) {
	function iif($condition, $val_true, $val_false = "") {
		if (is_numeric($val_true) and is_null($val_false)) $val_false = 0;
		if (is_null($val_true) or is_null($val_false) or !is_bool($condition)) return null;
		return ($condition) ? $val_true : $val_false;
	}
}

if (!function_exists('save_var')) {
	function save_var($var, $fName) {
		return file_put_contents(
			$fName,
			PHPTAG . PHP_EOL . 'return ' . var_export($var, true) . ";\n\n"
		);
	}
}

if (!function_exists('long_date')) {
	function long_date($time = 0, $lang=null) {
		global $Dias, $Meses;

		$lang = $lang ? : 'pt-br';
		$time = $time
			? is_date($time, '', 'U')
			: is_date('now', '', 'U'); // TimeStamp

		$dow       = intval(date("w",$time));
		$moy       = intval(date("m",$time));
		$dayName   = $Dias[$lang][$dow];
		$monthName = $Meses[$lang][$moy];

		if ($lang == 'pt-br' or $lang == 'es')
			return "$dayName, " .
					date("d",$time) . " de ".
					$monthName . " de " .
					date("Y, H:i",$time);
		return strftime("%c", $time);
	}
}

if (!function_exists('phone_format')) {
	function phone_format($fone_str) {

		$fone_limpo = preg_replace('/\D/', '', $fone_str);

		$value = $fone_limpo;

		switch (strlen($fone_limpo)) {  // 13/04/2011 MLG - Formatando números de telefone...
			case  7: $value = preg_replace('/(\d{3})(\d{4})/', '$1-$2', $fone_limpo);
			break;
			case  8: $value = preg_replace('/(\d{4})(\d{4})/', '$1-$2', $fone_limpo);
			break;
			case  9: $value = preg_replace('/(\d{2})(\d{3})(\d{4})/', '($1) $2-$3', $fone_limpo);
			break;
			case 10: $value = preg_replace('/(\d{2})(\d{4})(\d{4})/', '(0$1) $2-$3', $fone_limpo);
			break;
			case 11:
				if ($fone_limpo[0] == '0') {
					$value = preg_replace('/(0\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $fone_limpo);
				} else {
					$value =  preg_replace('/(\d\d)(9\d{4})(\d{4})|(\d{3})(\d{4})(\d{4})/', '($1$4) $2$5-$3$6', $fone_limpo);
				}
				break;
			case 12: $value = preg_replace('/(\d{2})(\d{2})(\d{4})(\d{4})/', '+$1 ($2) $3-$4', $fone_limpo);
				break;
			case 13: $value = preg_replace('/(\d{2})(\d{3})(\d{4})(\d{4})|(55)(\d\d)(9\d{4})(\d{4})/', '+$1 ($2) $3-$4', $fone_limpo);
				break;
			case 14: $value = preg_replace('/(\d{3})(\d{3})(\d{4})(\d{4})/', '$1 ($2) $3-$4', $fone_limpo);
				break;
			default:
				$value = $fone_limpo;
				break;
		}

		if ($value == '() -' or $value == '(0) -' or $value == '')
			$value = $fone_limpo;
		return $value;
	}
}

if (!function_exists('date_to_timestamp')) {
	function date_to_timestamp($fecha='agora') { // $fecha formato DD/MM/YY [HH:MM:[:SS]] YYYY-MM-DD [H24:MI[:SS]] ou DD-MM-YYYY [H24:MI[:SS]]
			if ($fecha=="hoje")  $fecha = date('Y-m-d');
			if ($fecha=="agora") $fecha = date('Y-m-d H:i:s');
		list($date, $time)      = explode(' ', $fecha);
		if (strlen($date)==8) {
			list($day, $month, $year) = preg_split('/[\/|\.|-]/', $date);
		} else {
			list($year, $month, $day) = preg_split('/[\/|\.|-]/', $date);
		}
		if (strlen($year)==2 and strlen($day)==4) list($day,$year) = array($year,$day); // Troca a ordem de dia e ano, se precisar
		if ($time=="") $time = "00:00:00";
		list($hour, $minute, $second) = explode(':', $time);
		return @mktime($hour, $minute, $second, $month, $day, $year);
	}
}

if (!function_exists('is_in')) {
	function is_in($valor, $valores, $tipo="exact", $sep=",") { // BEGIN function is_in v2.0 (usa in_array para 'exact')
		// *** Precisa da função iif ***
		// O 2º parâmetro pode ser uma lista CSV ou um array
		// O 3º parâmetro é opcional, seleciona o tipo de busca: exata, datas, desde o começo, desde o final, em qualquer parte
		//      (exact (padrão), date, start, end, any)
		// O 4º parâmetro é opcional, trata-se do separador da lista, se quiser usar um outro
		// Devolve 'true' se o $valor é um dos $valores, 'false' se não está, 'null' se uma
		// das duas variáveis é "" ou não á separador em $valores
		//
		// Em caso de usar o tipo 'data' (date), o segundo parâmetro deve conter a data menor e a data maior, nessa ordem.
		// Pode ser num array (chave numérica, 0 e 1)  ou uma string
		if (!$valor)
			return null;

		if (!is_array($valores) and !strpos($valores, $sep))
			return null;

		$a_valores = is_array($valores) ? $valores : explode($sep,$valores);

		//  Compara datas
		//  Requires: date_to_timestamp()
		if ($tipo== 'date') {
			$datatest = date_to_timestamp($valor);
			$data_ini = date_to_timestamp($a_valores[0]);
			$data_fim = date_to_timestamp($a_valores[1]);
			return (($data_ini >= $datatest) and ($datatest <= $data_fim));
		}

		if ($tipo = "exact"):
			$is_in = in_array($valor, $a_valores);
				return $is_in;
		endif;

		foreach ($a_valores as $valor_i) {
			if ($tipo = "icase") $is_in = (strtolower($valor)==strtolower($valor_i));
			if ($tipo = "any")   $is_in = (strpos($valor_i, $valor) > 0);
			if ($tipo = "start") $is_in = (substr($valor, 0, strlen($valor_i))==$valor_i);
			if ($tipo = "end")   $is_in = (substr($valor, 0 - strlen($valor_i))==$valor_i);
			if ($is_in) break;
		}
		return $is_in;
	} // END function is_in
}

/**
 * @name _is_in
 * @author  Nica Mlg <nicamlg@gmail.com>
 * @desc    Confere se o valor passado existe no 2º parâmetro
 * @param   mixed   $needle     Valor a ser procurado. Pode ser um número ou uma string (TODO: array)
 * @param   mixed   $haystack   Valor onde procurar a agulha. Pode ser uma string, CSV ou array. TODO: Object properties.
 * @param   boolean $match_all  TRUE para devolver todos os índices do array original (útil apenas se $haystack é array)
 * @param   array   $keys       Optional ByRef (&$keys). Array onde devolver os resultados. Se não estiver definida, será no return
 * @return  NULL se erro, TRUE ou FALSE se achou ou não. Se pediu para devolver as keys e não passou &$keys, irá retornar ARRAY
 *
 * @other   Definido um tipo próprio para o haystack: RANGE
 *          Range é definido por dois números (menor e maior) separados por - ou :
 *          Ex.: _is_in(33, '10:50') irá retornar TRUE. Isso facilita MUITO a verificação de valores numéricos
 *          Detalhe: segue o padrão Python: a classe é fechada à esquerda e aberta à direita (traduzindo:
 *          se o needle é igual ao limite superior da classe, retorna false):
 *          Ex.: _is_in(100, '0:100')    retorna FALSE
 *
 *          Para fechar a classe à direita (validar com o valor máximo), usar :: como separador:
 *          Ex.: _is_in(100, '0::100')   retorna TRUE
 *
 *          É possível validar um valor para várias classes, usando o array ou JSON:
 *
 *          Ex.: _is_in(3000, '[3000,"7000:8000"]')                  // JSON
 *          Ex.: _is_in(7200, '["3000","7000:8000","2000:3000"]')    // JSON
 *          Ex.: _is_in(7200, array(3000, '7000::8000'))             // Array
 *
 *          2017-10-05 - Nova funcionalidade: validação de maior (ou igual) que e menor (ou igual) que
 *          ---
 *          Os possíveis operadores são:
 *
 *          - Menor que:          `..10`  `<10`  `lt 10`
 *          - Menor ou igual que: `...10` `<=10` `le 10`
 *          - Maior que:          `10..`  `>10`  `gt 10`
 *          - Maior ou igual que: `10...` `>=10` `ge 10`
 */
if (!function_exists('_is_in')) {
	function _is_in($needle, $haystack, $match_all=false, &$keys=null) {

		if (!$haystack or (is_array($haystack) and !count($haystack)))
			return NULL;

		if (is_array($needle)) {
			foreach ((array)$needle as $ouch) {
				$ret = _is_in($ouch, $haystack);
				if ($ret === true)
					return true;
			}
			return false;
		}

		if (is_array($haystack)) {
			if ($keys = array_keys($haystack, $needle))
				return ($match_all) ? $keys : true;

			foreach ($haystack as $key=>$bunch) {
				if (_is_in($needle, $bunch, $match_all)) {
					if (!$match_all)
						return true;
					if (is_array($keys))
						$keys[] = $key;
				}
			}
			if (count($keys))
				return (count($keys)==1) ? $keys[0] : $keys;
			else
				return false;
		}

		// 17/04/2014 - verfica intervalo de datas: _is_in('05/06/2014', '01/05/2014::2014-07-31') deveria retornar TRUE
		if (is_date_format($needle))
			if ($xData = is_date($needle, '', '@')) {
				// Para saber se $needle está entre duas datas...
				if (strpos($haystack, '::')) {
					list($dIni, $dFim) = explode('::', $haystack);
					if (!is_date($dIni) or !is_date($dFim))
						return false;
					return (is_date($dIni, '', '@') <= $xData and $xData <= is_date($dFim, '', '@'));
				}
				if (strpos($haystack, ':')) {
					list($dIni, $dFim) = explode(':', $haystack);
					if (!is_date($dIni) or !is_date($dFim))
						return false;
					return (is_date($dIni, '', '@') <= $xData and $xData < is_date($dFim, '', '@'));
				}
				// Para saber se $needle é a mesma data que $haystack
				if ($xComp = is_date($haystack, '', '@'))
					return ($xData == $xComp);
			}

		if (is_numeric($needle) and is_numeric($haystack))
			if ($needle == $haystack)
				return true;

		// Se é uma string JSON, processar como array... recursivamente :P
		if (is_array($new_haystack = json_decode($haystack, true)))
			return _is_in($needle, $new_haystack, $match_all);

		// Se for um XML, também funciona!! Pode procurar um valor dentro de um XML
		// usando a mesma interface. :)
		if (substr($haystack, 0, 5) == '<'.'?xml' and
			is_object($xml_arr = simplexml_load_string($haystack)))
			return _is_in(json_decode(json_encode($xml_arr), true), $match_all);

		// Tipo de dados de usuário: range.
		// Se haystack tem o formato número-número, verifica que needle esteja entre esses dois valores.
		if (is_numeric($needle) and preg_match('/^(?P<min>\d+)(?P<sep>-|::?)(?P<max>\d+)$/', $haystack, $range))
			return ($range['min'] <= $needle and (($range['sep'] == '::' and $needle <= $range['max']) or $needle < $range['max']));

		// Tipo de operador de usuário: limite inferior e superior
		// Menor ou igual que:
		if (is_numeric($needle) and preg_match('/^(?P<op>\.{2,3}|<=\s?|l[te]\s?)(?P<max>\d+)$/', $haystack, $range)) {
			if (in_array($range['op'], ['..','<','lt'])) {
				return $needle < $range['max'];
			}
			elseif (in_array($range['op'], ['...','<=','le'])) {
				return $needle <= $range['max'];
			}
		}

		// Maior ou igual que:
		if (is_numeric($needle) and preg_match('/^(?P<op>>=?\s?|g[te]\s?)?(?P<min>\d+)(?P<op2>\.{2,3})?$/', $haystack, $range)) {
			$range['op'] = trim($range['op']) ? : trim($range['op2']);
			if (in_array($range['op'], ['..','>','gt'])) {
				return $needle > $range['min'];
			}
			elseif (in_array($range['op'], ['...','>=','ge'])) {
				return $needle >= $range['min'];
			}
		}

		// var_export($range);
		// pecho($needle);

		// Por último, trata como simples string
		return (strpos($haystack, $needle) !== false);
	}
}

/**
 * @name   getValorFabrica
 * @param  Array     $values Array com os valores que dependem do fabricante. O valor 'default'
 *                           é o índice 0.
 * @param  Int (opt) $key    Valor opcional que informa o índice a retornar. Isso permite usar
 *                           a função com outros índices que não sejam o `$login_fabrica`.
 * @return mixed             Retorna o valor do índice '$key', ou do `$login_fabrica` ou o `0`,
 *                           ou `NULL` se não existe nenhum deles.
 */
if (!function_exists('getValorFabrica')) {
	function getValorFabrica($values, $key=null) {
		if (!is_array($values))
			return $values;

		if (is_null($key))
			$key = $GLOBALS['login_fabrica'];

		return array_key_exists($key, $values)
			? $values[$key]
			: $values[0];
	}
}

if (!function_exists('array_group')) {
	function array_group($GDF, $groupKey) {
		$GD = array();
		foreach ($GDF as $rowID=>$row) {
			// pre_echo($row, 'Processando elemento '.$rowID);
			if (!array_key_exists($groupKey, $row)) {
				$GD[$rowID] = $row; // copy
				continue;
			}
			$keyValue = $row[$groupKey];
			unset($row[$groupKey]);

			$GD[$keyValue][] = $row;
		}
		// pre_echo($GD, "ROW '$rowID'");
		return $GD;
	}
}

if (!function_exists('array_group_by')) {
	function array_group_by($GDF, $groupKey) {
		$GD = array();
		foreach ($GDF as $rowID=>$row) {
			// pre_echo($row, 'Processando elemento '.$rowID);
			if (!array_key_exists($groupKey, $row)) {
				$GD[$rowID] = $row; // copy
				continue;
			}
			$keyValue = $row[$groupKey];
			$info[$keyValue][$groupKey] = $keyValue;

			unset($row[$groupKey]);
			foreach ($row as $field=>$value)
					$info[$keyValue][$field][] = $value;
			$GD[$keyValue] = $info[$keyValue];
		}
		// pre_echo($GD, "ROW '$rowID'");
		return $GD;
	}
}

/**
 * Esta função retorna os ítens do array $arr cujas keys CONTENHAM
 * a string $key, SEM ter que coincidir 100%. Para o caso que deva coincidir 100%, é melhor usar a função PHP array_key_exists()
 **/
if (!function_exists('array_key_filter')) {
	function array_key_filter(array $arr, $key) {
		return array_intersect_key(
			$arr,
			array_flip(
				array_filter(
					array_keys($arr),
					function($i) use ($key) {
						return strpos($i, $key) !== false;
					}
				)
			)
		);
		$keys = array_keys($arr);
		$ret = array();
		foreach($keys as $k) {
			//print "strpos($k, $key) @> $key?\n";
			if (strpos($k, $key) !== false)
				$ret[$k] = $arr[$k];
		}
		return $ret;
	}
}

/**
 * Returns the first non-NULL element of an array, like SQL `COALESCE` function,
 * hence its name.
 * @copyright © 2017 Manuel López
 * @param   Array   Array to be filtered
 * $param   Boolean Recurse coalescing through each element, if a sub-array as non-null
 *          values, it will still return the whole sub-array!
 * @return  Mixed   First non-NULL element, or NULL if ALL elements are null
 */
if (!function_exists('array_coalesce')) {
	function array_coalesce(array $a, $recursive=false) {
		return reset(
			array_filter($a, function($i) use ($recursive) {
				if ($recursive and is_array($i)) {
					$a2 = $i; unset($i);
					$i = array_coalesce($a2, $recursive);
				}
				return !is_null($i);
			}
		)) ? : null;
	}
}

// função para comparar os arrays bidimensionais
if (!function_exists('msort')) {
	function msort(&$arr, $campo) {
		usort($arr,
			function($a, $b) use ($campo) {
				if ($a[$campo] === $b[$campo]) return 0;
				return $a[$campo] > $b[$campo] ? 1 : -1;
			}
		);
	}

	function mrsort(&$arr, $campo) {
		usort($arr,
			function($a, $b) use ($campo) {
				if ($a[$campo] === $b[$campo]) return 0;
				return $a[$campo] < $b[$campo] ? 1 : -1;
			}
		);
	}
}

if (!function_exists('array_full_search')) {
	function array_full_search($v,  $a,  $tipo='any') {

		if (in_array($v, $a)) {
			return $a[array_search($v, $a)];
		}

		foreach($a as $b) {
			//echo "<$b> $tipo <$v>?\n";
			switch ($tipo) {
				case 'any':
					if (strpos($b, $v) !== false) return $b;
					break;

				case 'start':
					if (strpos($b, $v) === 0) return $b;
					break;

				case 'end':
					if (strpos($b, $v) == (strlen($b) - strlen($v)))
						return $b;
					break;
			}
		}
		return false;
	}
}

/**
 * Retorna o valor mais longo de uma string, ou o índice
 * @name array_max_len
 * @param array   $arr      Array a ser processada. Ainda não é recursivo, estou pensando na forma de fazer o retorno do índice
 * @param boolean $retIndex TRUE para retornar o índice do elemento mais longo e não o valor.
 **/
if (!function_exists('array_max_len')) {
	function array_max_len($arr, $retIndex=false) {
		$lens = array_map('strlen', $arr);
		$maxidx = array_search(max($lens), $lens);
		return ($retIndex) ? $maxidx : $arr[$maxidx];
	}
}

/**
 * @name array_merge_keys()
 * @param array $a, $b
 * @return array
 * @desc
 * Copia os valores do array $b no array $a, substituindo chaves
 * e valores, mesmo se o índice for numérico.
 */
if(!function_exists('array_merge_keys')){
	function array_merge_keys($a, $b) {
		foreach ($b as $k => $v)
			$a[$k] = $v;
		return $a;
	}
}

if (!function_exists('array_dump')) {
	function array_dump($fn, $var, $incl=false) {
		if (!is_array($var))
			return false;
		$template = PHP_TAG . "%s %s;\n\n";
		$command  = ($incl === true) ? 'return ' : "\$$incl = ";
		$str      = sprintf($template, $command, var_export($var, true));
		$str      = preg_replace('/>\s+array\s*/m', '> array', $str);
		@file_put_contents($fn, $str);
		if (!file_exists($fn))
			return false;
		chmod($fn, 0664);
		return true;
	}
}

// Devolve 'true' se o valor está entre ("between") o $min e o $max
if (!function_exists('is_between')) {
	function is_between($valor,$min,$max) {
		return ($valor >= $min and $valor <= $max);
	}
}

if (!function_exists('is_even')) {
	function is_even($num) {
		if (!is_numeric($num))
			return null;
		return ($num % 2 == 0);
	}
}

//  Limpa a string para evitar SQL injection
if (!function_exists('anti_injection')) {
	function anti_injection($string) {
		$a_limpa = array("'" => "", "%" => "", '"' => "", "\\" => "");
		return strtr(strip_tags(trim($string)), $a_limpa);
	}
}

if (!function_exists('is_email')) {
	function is_email($email="") {
		return (preg_match(RE_EMAIL, $email));
	}
}

if (!function_exists('is_url')) {
	function is_url($url="") {
		return (preg_match(RE_URL, $url));
	}
}

if (!function_exists('tira_acentos')) {
	function tira_acentos ($texto) {
		$acentos = array(
			"com" => "áâàãäéêèëíîìïóôòõúùüçñÁÂÀÃÄÉÊÈËÍÎÌÏÓÔÒÕÚÙÜÇÑ",
			"sem" => "aaaaaeeeeiiiioooouuucnAAAAAEEEEIIIIOOOOUUUCN"
		);
		return strtr($texto,$acentos['com'], $acentos['sem']);
	}
}

if (!function_exists('change_case')) {
	function change_case($texto, $l_u = 'lower') {
		$acentos      = array(
			"lower"	=> "áâàãäéêèëíîìïóôòõúùüç",
			"upper"	=> "ÁÂÀÃÄÉÊÈËÍÎÌÏÓÔÒÕÚÙÜÇ"
		);
		if (substr($l_u, 0, 1) == 'l') {
			return strtr(strtolower($texto), $acentos['upper'], $acentos['lower']);
		} else {
			return strtr(strtoupper($texto), $acentos['lower'], $acentos['upper']);
		}
	}
}

/**
 * Converte de vários tipos de encoding para outros, reconhecidos pelo PHP
 * ex.: ISO-8859-1, iso-8859-2, UTF-8, BASE64, HTML-ENTITIES ...
 *
 * echo convert('Código', 'HTML-ENTITIES'); // C&oacute;digo
 * echo Convert('C&oacutedigo', 'Latin1', 'HTML-ENTITIES'); Código
 * O padrão é reconhecer UTF8 e Latin1 (nessa ordem!) e converter
 * para o encoding solicitado no segundo parâmetro.
 * @name Convert
 * @author Manuel López
 * @param $text     mixed   Texto a ser recodificado, pode ser também um array
 * @param $to   string  Encoding de destino. Vide `mb_list_encodings()`
 * @param $from     mixed Array ou CSV com a lista de encodings a serem detectados
 * @return String   $texto convertido, se foi possível. Se não precisava ou não foi possível
 *               retorna o original
 */
if (!function_exists('Convert')) {
	function Convert($text, $to, $from='UTF-8,ISO-8859-1') {
		if (preg_match('/Latin-?1/i', $to))
			$to = 'ISO-8859-1';

		if (is_string($text)) {
			return mb_convert_encoding($text, $to, $from);
		}

		array_walk_recursive(
			$text,
			function(&$item, $key, $enc) {
				$item = mb_convert_encoding($item, $enc[0], $enc[1]);
			}, array($to, $from)
		);
		return $text;
	}
}

/**
 * @name str_words
 * @author  Nica Mlg <nicamlg@gmail.com>
 * @param $str       string     String a ser 'cortada'
 * @param $maxstr    integer    Máx. de caracteres (ou palavras, vide $wordcount) no retorno
 * @param $wordcount boolean    Default false. Se true, $maxstr informa o número de palavras a ser devolvida
 **/

if (!function_exists('str_words')) {
	function str_words($str, $maxstr = null, $wordcount=false) {
		if (!str_word_count($str))
			return $str;

		$str = preg_replace("/\s+/", ' ', $str); // Substitui TABs e múltiplos espaços num só espaço
		$arr = str_word_count($str, 1);

		$maxlen   = strlen($str);
		$maxwords = count($arr);

		if (($wordcount  and $maxstr >= $maxwords) or
			(!$wordcount and $maxstr >= $maxlen))
			return $str;

		$c = 0;
		if ($wordcount)
			while(str_word_count($ret)<$maxwords and str_word_count($ret)<$maxstr)
				$ret .= $arr[$c++]. ' ';
		else
			$ret = substr($r=substr($str, 0, $maxstr), 0, strrpos($r,' '));

		return trim($ret);
	}
}

if (!function_exists('ValidateBRTaxID')) {
	function ValidateBRTaxID ($TaxID,$return_str = true) {
		global $con;    // Para conectar com o banco...
	//  echo "Validando $TaxID...<br>";
		$cpf = preg_replace("/\D/","",$TaxID);   // Limpa o CPF / CNPJ
	//  echo "Validando $cpf...<br>";
		if (!$cpf or $cpf == '' or (strlen($cpf) != 11 and strlen($cpf) != 14)) return false;

		$res_cpf = @pg_query($con,"SELECT fn_valida_cnpj_cpf('$cpf')");
		if ($res_cpf === false) {
			return ($return_str) ? pg_last_error($res_cpf) : false;
		}
		return ($return_str) ? $cpf : true;
	}
}

/**** FUNÇÕES PARA FACILITAR AÇOES COM SQL ****/
/**
 * @name    parse_parameter($param, $fieldName[, $sep, $isNumeric])
 * @author  Manuel Lopez <nicamlg@gmail.com>
 * @param   string  $param      required    Campo com as informações, separadas pelo $sep
 * @param   string  $fieldName  optional    Nome do campo (melhor com o da tabela, tipo tabela.campo).
 *                                          Se não informado, apenas devolve o(s) valor(es) formatado(s)
 * @param   string  $sep        optional    String q separa os campos no $param, default '-'
 * @param   boolean $isNumeric  optional    Informa se o campo é numérico ou não, para usar ou não aspas nos valores
 * @return  string  Retorna uma string com a sintaxe SQL adequada (campo = valor se for 1, campo IN(valor, valor, ..)
 *                  para vários, entre aspas, se não for um campo ou valores numéricos
 * @example parse_parameter('1;2;3;;4', 'prod.id', ';')
 *          irá retornar:
 *              "prod.id IN(1, 2, 3, 4)"
 *          1. Para valores negados, usar '!' no final do nome do campo:
 *              parse_parameter('janeiro', 'mes!')
 *              irá retornar:
 *                  "mes <> 'janeiro'"
 *             LEGENDAS:
 *               val1::val2   interpretado como BETWEEN 'val1' AND 'val2'
 *                 mas...
 *                  se o nome do campo acaba em '#' interpreta val1 e val2 como campos:
 *                  parse_parameter('campoA::campoB', 'campo#')   será interpretado como
 *                             campo BETWEEN campoA AND campoB
 *                  E se val1 E val2 são ambos DATAS (ou DATA E HORA), serão interpretados como
 *                  datas, validados os dois valores e interpretados e convertidos para ISO:
 *                  parse_parameter('01/04/2010::31/05/2010' => 'campo_data') será interpretado como:
 *                             campo_data BETWEEN '2010-04-01' AND '2010-05-31'
 *
 *          2. Se o VALOR começa ou finaliza com '%', o operador será LIKE (ou NOT LIKE se o começo do campo tem '!')
 *          3. Para forçar a função a NÃO usar aspas para valores não numéricos, usar '#' no nome do campo:
 *              parse_parameter('data_entrada::data_entrega', 'data_envio');
 *             devolveria
 *              "data_envio BETWEEN 'data_entrada' AND 'data_entrega'"
 *              que não é o esperado. Assim, usando '#' (em qualquer parte do nome, até no meio, mas não recomendo):
 *              parse_parameter('data_entrada::data_entrega', '#data_envio');
 *             devolveria
 *              "data_envio BETWEEN data_entrada AND data_entrega"
 *          4. Outros modificadores (a serem colocados no final da KEY menos @ que vai sempre no começo e ! que pode ir nos dois):
 *              a) !  negação
 *              b) #  força interpretar o value como numérico (= sem aspas), útil para passar nomes de campos como value
 *              c) %  força usar ILIKE   array('cep%' => '175_____') seria cep ILIKE '175_____'
 *              d) $  força interpretar o value como não-numérico (força aspas no valor)
 *              e) &  força o uso do operador '&&' (logical AND)
 *              f) =  NÃO interpreta o value e não usa operador. Usado para operações complexas.
 *
 * [ATAUALIZAÇÂO]
 *   21/07/2014 - Processa datas para BETWEEN, aceita arrays como valor!
 *   Para tanto, o array só pode ter chave numérica E consecutiva, para a função distinguir
 *   entre valores e as condições complexas.
 *   parse_parameter(array('AR','ES','US','UK','JP'), 'pais')
 *
 *   devolve:
 *      pais IN('AR','ES','US','UK','JP')
 **/
if (!function_exists('parse_parameter')) {
	function parse_parameter($param, $fieldName, $sep=null, $isNumeric=true) {

		if (is_null($sep))
			$sep = '-';

		$is_between = false;
		if (is_string($param) or is_numeric($param)) {
			switch (strtolower($param)) {
				case 'true':  $values[] = true;  break;
				case 'false': $values[] = false; break;
				case 'null':  $values[] = null;  break;
				default:
					// Evita 'quebrar' os campos de data
					if (is_date($param)) {
						$values[] = $param;
						break;
					}
					if ($sep != ':' and strpos($param, '::')>1) { // possível campo 'BETWEEN'
						$v = explode('::', $param);
						if (count($v) == 2) {
							$values[] = $param;
							$is_between = true;
						}
						break;
					}
					// Primeiro verifica se não é um JSON. Se é, converte-o para array.
					// Se não é, corta a string pelo $sep, tira os espaços extra que tiver
					// deixando os valores 0 e '0'
					if (!is_array($values=json_decode($param, true))) {
						$values = array_map('trim', explode($sep, $param));
						break;
					}
				break;
			}
		} else if (is_array($param)) {
			$values = array_filter(array_map('trim', $param), 'strlen');
		} else if (is_bool($param) or is_null($param))
			$values[] = $param;
		else
			return false;

		// Processa os mdificadores do operador
		$modifier = preg_replace('/(\W+)$/', '$1', $fieldName);
		$op = '=';
		$not = false;
		$no_quotes = false;

		if ((strpos($fieldName, '#') !== false)) {
			$no_quotes = true;
			$fieldName = str_replace('#', '', $fieldName);
		}
		if (strpos($fieldName, '!') !== false) {
			$not = true;
			$op = '<>';
		}
		if (strpos($modifier, '>') !== false) {
			$op = '>';
		} else if (strpos($modifier, '<') !== false) {
			$op = '<';
		} else if (strpos($modifier, '&') !== false) {
			$op = '&&';
		}
		if (strpos($modifier, '%') !== false) {
			$op = 'ILIKE';
		}
		if (strpos($modifier, '$') !== false) {
			$isNumeric = false;
			$no_quotes = false;
		}

		$fieldName = str_replace(array('>','<', '!', '&', '$', '%', '='),'', $fieldName);
		$filter = '';

		if (count($values)) {
			// Se passou 'null' ou não passou o campo, apenas devolve os valores como array
			if (is_null($fieldName))
				return $values;

			sort($values); // Não faz diferença para o motor do banco, mas melhora a leitura do SQL para os humanos

			// Confere se tem algum valor no array que não seja numérico.
			// Se o programador esqueceu de informar que o campo é string,
			// aqui valida essa informação, para poder colocar as aspas nos
			// valores fornecidos e evitar um erro de SQL.
			if (count(array_filter($values, 'is_numeric')) < count($values))
				$isNumeric = false;

			// Alguns campos muito usados em geral, mesmo sendo valores numéricos,
			// devem ser tratados como string, assim, verificamos que os campos
			// informados seja algum deles:
			if ($NaN = preg_match('/'.INT_FIELDS.'/', $fieldName))
				$isNumeric = false;

			if (count($values) == 1) {
				if(is_bool($values[0]) or is_null($values[0]))
					$op = $not ? 'IS NOT':'IS';
				else if (!is_email($values[0]) and preg_match('/[%]/', $values[0]))
					$op = 'ILIKE';

				if (substr($op, -4) == 'LIKE' and !preg_match('/[%]/', $values[0]))
					$values[0] = '%'.preg_replace('/\W+/', '_', $values[0]).'%';

				if (count($lim = preg_split('/::/', $values[0])) == 2) {
					$op = 'BETWEEN';
					foreach ($lim as $idx=>$val) {
						if (preg_match('/'.EUR_DATE.'/', $val))
							$val = is_date($val);
						$lim[$idx] = (($isNumeric and !$NaN) or $no_quotes) ? $val : "'" . $val . "'";
					}
					$values[0] = implode(' AND ', $lim);
				}
			} else
				$op = $not ? 'NOT IN' : 'IN';

			if (!$isNumeric and $op != 'BETWEEN') // Se o campo não for numérico, adiciona aspas a cada valor
				foreach ($values as $idx=>$val) {
					if (is_null($val))
						 $values[$idx] = 'NULL';
					else if (is_bool($val))
						 $values[$idx] = $val ? 'TRUE':'FALSE';
					else
						$values[$idx] = (($isNumeric and !$NaN) or $no_quotes) ? $val : "'" . $val . "'";
				}
			$op = ($not and !preg_match('/NOT|<>/', $op)) ? "NOT $op" : $op;
			$filter = (count($values) == 1) ? "$fieldName $op " . $values[0] : "$fieldName $op(" . implode(', ', $values) . ')';
		} else
			$filter = '';
		return $filter;
	}
}

/**
 * @name sql_where()
 * @author Manuel López <nicamlg@gmail.com>
 * @param  array   $filters Array com as informações.
 * @param  string  $sep     Separador dos valores múltiplos, se diferente de '-' (padrão)
 * @return string
 * @dependencies   parse_parameter(), array_max_len(), iif()
 * @desc
 * - As chaves são os nomes dos campos, o valor pode ser numérico,
 *   string ou array ou valores separados por '-' (ou definido pelo
 *   parâmetro $sep), parse_parameter() irá converter na forma
 *   apropriada para SQL.
 *   array(
 *      'brasil' => array(
 *          'pais' => 'BR',
 *          'estado' => 'SP-RS-SC-PR'
 *      ),
 *      '@pais' => array('AR','ES','US','UK','JP')
 *      'data' => '01/05/2014::10/05/2014',
 *      'tax_id!' => 'XYZ'
 *   )
 *
 *  deve devolver:
 *    ((pais   = 'BR'
 *    AND estado IN('PR', 'RS', 'SC', 'SP')
 *       )
 *      OR pais   IN('AR', 'ES', 'JP', 'UK', 'US')
 *    )
 *    AND data    BETWEEN '2014-05-01' AND '2014-05-10'
 *    AND taax_id <> 'XYZ'
 *
 *    TODO:
 *   ***************************************************
 *   * sql_where deve "entender" o operador de REGEX   *
 *   * não deve usar o ILIKE nos campos... Isso é mais *
 *   * complicado. Talvez um modificador novo, tipo ?  *
 *   * para o LIKE e o ~ para as REGEX...              *
 *   ***************************************************
 **/
if (!function_exists('sql_where')) {
	function sql_where($filters, $sep='§') {
		static $level = -1;
		$indent = '  ';
		$level++;
		if ($level > 0)
			$indent = str_repeat('    ', $level);

		// retira chaves duplicadas... os modificadores fazem com que isso não seja possível
		// de forma automática. Segue o mesmo conceito do array_merge: a última chave é a que fica
		$org = $filters; $copy = $filters = array(); // copia e limpa o array
		foreach($org as $key=>$value) {
			$chave = preg_replace('/[#!%<>=]/', '', $key);
			$copy[$chave] = array( 'orgKey' => $key, 'value' => $value);
		}
		foreach($copy as $o)
			$filters[$o['orgKey']] = $o['value'];

		$padTo = strlen(array_max_len(array_keys($filters)));

		foreach ($filters as $field=>$value) {
			$field = str_pad(
				$field,
				$padTo+(preg_match('/[#!@%<>=]/', $field)), ' ', STR_PAD_RIGHT);

			if (is_array($value)) {
				// Se os índices do array são numéricos e consecutivos, vai
				// asumir que trata-se de valores, e não de uma sub cláusula...
				$valueKeys = array_keys($value);
				if (count($valueKeys) == count(array_filter($valueKeys, 'is_numeric'))
					and end($valueKeys) == count($valueKeys)-1) {
					$newValue = implode($sep, $value); unset($value);
					$value = $newValue; unset($newValue);
				} else {
					$w[] = iif(($field[0]=='@'), '@(', '(') .  sql_where($value, $sep) . "\n$indent   )";
					continue;
				}
			}
			// Se o nome do campo finaliza com '=', usar o $value literalmente
			// if (preg_match("/[a-z0-9._]+=/", $field))
			if (substr(trim($field), -1) === '=')
				$w[] = str_replace('=', '', $field) . ' ' . $value;
			else
				$w[] = parse_parameter($value, str_replace('=', '', $field), $sep);
		}

		$where = str_replace(
			'AND @', ' OR ',
			implode("\n$indent AND ", array_filter($w))
		);

		$level--;
		// Se alguma chave começa com @, quer dizer que era um 'OR' e não AND...
		return $where;
	}
}

/**
 * @name    is_date_format()
 * @param   string      string a ser verificada
 * @returns bool        TRUE se á algum tipo de data (ISO ou euro)
 * @obs     NÃO VALIDA o VALOR, apenas a formatação. PAra validar a data, usar is_date()
 **/
if (!function_exists('is_date_format')) {
	function is_date_format($date) {
		return preg_match(
			preg_replace('/\?P<\w+>/', '', '/'.ISO_DATE.'|'.ISO_DATETIME.'|'.EUR_DATE.'|'.EUR_DATETIME.'/'),
			$date
		);
	}
}

/**
 * @name  date_interval($init, $end)
 * @param init    Initial datetime (is_date() to check and parse)
 * @param end     Ending  datetime (is_date() to check and parse)
 * @param unit (s)  Unit to use for the return format:
 *            - D, h, m, s : days, hours, minutes or seconds
 *            - ISO, EUR   : use date/time format (interval)
 *            - UNIX, TS, U or TIMESTAMP : secs since UNIX epoch
 * @return  float, string (defined by $unit)
 * @obs
 * This functions is (at  2017-06-05), suitable for date intervals of less than a year.
 * Future versions may change that.
 */
if (!function_exists('date_interval')) {
	function date_interval($ini, $end, $unit='s') {
		if (!$stTS = is_date($ini, '', '@'))
			return false;
		if (!$lsTS = is_date($end, '', '@'))
			return false;

		$interval = abs($lsTS - $stTS);

		return $unit == 's' ? $interval : format_interval($interval, $unit);
	}

	function format_interval($time, $unit) {
		switch (strtolower($unit)) {
			case 's': return $time;
			case 'h':
				$ret = $time / 3600.0;
			break;

			case 'm':
				$ret = $time / 60.0;
			break;

			case 'd':
				$ret = $time / 86400.0; // 24h
			break;

			case 'iso': case 'eur':
				$fmt = $time>86400 ?
					'z H:i:s' : ($time>3600 ?
					'H:i:s'   : ($time>60   ?
					'i:s'     : 's'));
				$ret = date($fmt, strtotime("1st Jan + $time seconds"));
			break;

			case 'hms':
				// if ($time < 24*3600)
				//  $ret = date('H:i:s', strtotime("1st Jan + $time seconds"));
				// elseif ($time) {
				$s   = $time%60;
				$m   = intval(($time-$s)%3600/60);
				$h   = ($time-$m*60-$s)/3600;

				$ret = sprintf('%02d:%02d:%02d', $h, $m, $s);
				// }
			break;

			case 'day': case 'full':
				$ret = date('z\d H\h i\m s\s', strtotime("1st Jan + $interval seconds"));
			break;

			case 'u':   case 'unix': case 'ts': case 'timestamp':
			default: // 's'
				$ret = $interval;
			break;
		}
		return $ret;
	}
}

/**
 * @name    is_date()
 * @param   string      $date       Required    Data a ser validada ou reformatada
 * @param   string      $ifmt       Optional    Este parâmetro é obsoleto. na próxima versão vai sumir
 * @paramm  string      $ofmt       Optional    Formato de saída da data, se for válida. Possíveis valores:
 *                                              - EUR   Europeu:    dd/mm/YYYY Hh:mm:ss
 *                                              - ISO   ISO:        yyyy-mm-dd[Thh[:mm[:ss]][+TZ]]
 *                                              - UNIX/@ TimeStamp  longint
 *                                              - DEU   Alemão:     dd.mm.YYYY Hh:mm:ss
 *                                              - CDATE Sem separadores: YYYYMMDD[hh[mm[ss]]]
 *                                              - LONG EXT extenso: data por extenso, no idioma $lang
 *                                              - a date() valid format string
 * @desc
 * Esta função valida data e hora e se for válida retorna a data (e hora, ou TIMEZONE INFO) no formato
 * solicitado (o padrão é o ISO).
 * Como entrada, esta funcão aceita as expressões em linguagem natural que a função strtotime() aceita,
 * porém também aceita essas expressões em português e espanhol, além, é claro, do inglês:
 * echo is_date('today'); // é igual a
 * echo is_date('hoje');
 *
 * echo is_date('today -1 week'); // Pode ser escrito em pt:
 * echo is_date('hoje -1 semana');// e também irá devolver a mesma data
 **/
if (!function_exists('is_date')) {
	function is_date($date, $ifmt='EUR', $ofmt='ISO', $lang='pt', $die=false) {

		if (!$date) return false; // pra que pensar mais... :P
		if (strlen($lang)>2)
			$lang = substr($lang, 0, 2);

		$date = trim($date);
		$EUR_DATE = '/^'.EUR_DATE.'/';
		$ISO_DATE = '/^'.ISO_DATE.'/';
		$flipDateRE = '$3-$2-$1';

		// Se a entrada é um timestamp, converte para string ISO
		if (in_array(strtolower($ifmt), array('u', 'ts', 'unix', 'timestamp', '@'))) {
			$cvFmt = ($date/86400 == intval($date/86400)) ? 'Y-m-d': 'Y-m-d H:i:s';
			$date = date($cvFmt, $date);
			$ifmt = 'iso';
		}

		if (preg_match('/^'.ISO_DATETIME.'$/',  $date, $cdate) or
			preg_match('/^'.EUR_DATETIME.'$/',  $date, $cdate) or
			preg_match('/^'.ISO_CDATETIME.'$/', $date, $cdate) or
			preg_match('/^'.ISO_DATE.'$/',      $date, $cdate) or
			preg_match('/^'.EUR_DATE.'$/',      $date, $cdate) or
			preg_match('/^'.ISO_CDATE.'$/',     $date, $cdate)
		) {
			$date = $cdate['Y'].'-'.$cdate['M'].'-'.$cdate['D'];
			if ($cdate['h'])
				$date .= iif(strtolower($ofmt) == 'iso', 'T', ' ') . $cdate['h'];
			if ($cdate['m'])
				$date .= ':'.$cdate['m'];
			if ($cdate['s'])
				$date .= ':'.$cdate['s'];
			$ifmt='ISO';
			preg_match(iif(strlen($date)>10, '/^'.ISO_DATETIME.'$/', $ISO_DATE), $date, $p);
		} else {

			// Não interpretar 'datas' tipo '10-9' ou '2-3-4-5'... já é demais... :)
			if (strpos($date, '-') and !preg_match('/[a-zA-Z]+/', $date) and preg_match_all('/-\d/', $date, $pma) != 2)
				return false;

			// Se não é um dos dois formatos, tentar com o strToTime()
			// pode ser um texto que strtotime entenda ('today', '-1 month', data em formato USA, etc.)
			//if (!preg_match($ISO_DATE, $date, $p) and !preg_match($EUR_DATE, $date, $p)) {
			$traduz = array(
				'en' => explode(' ',
					'now tomorrow today yesterday '.
					'day month week year '.
					'next last previous this ago '.
					'sunday monday tuesday wednesday thursday friday saturday '.
					'hour minute second '.
					'january february march april may june july august september october november december'
				),
				'pt' => explode(' ',
					'/\bagora\b/i /\bamanh[aã]\b/i /\bhoje\b/i /\bontem\b/i '.
					'/\bdias?\b/i /\bm[eê]s(es)?\b/i /\bsemanas?\b/i /\banos?\b/i '.
					'/\bpr[oó]xim[ao]s?\b/i /\b[uú]ltim[ao]\b/i /\banterior|passad[ao]\b/i /\best[ae]\b/i /\b(antes|atr[aá]s)\b/ '.
					'/\bdomingo\b/i /\bsegunda\b/i /\bter[cç]a\b/i /\bquarta\b/i /\bquinta\b/i /\bsexta\b/i /\bs[aá]bado\b/i '.
					'/\bhoras?\b/i /\bminutos?\b/i /\bseg(undo)?s?\b/i '.
					'/\bjaneiro\b/i /\bfevereiro\b/i /\bmarço\b/i /\babril\b/i /\bmaio\b/i /\bjunho\b/i /\bjulho\b/i '.
					'/\bagosto\b/i /\bsetembro\b/i /\boutubro\b/i /\bnovembro\b/i /\bdezembro\b/i'
				),
				'es' => explode(' ',
					'/\bahora\b/i /\bma[nñ]ana\b/i /\bhoy\b/i /\bayer\b/i '.
					'/\bd[ií]as?\b/i /\bmes(es)?\b/i /\bsemanas?\b/i /\ba[nñ]os?\b/i '.
					'/\bpr[oó]xim[ao]s?\b/i /\b[uú]ltim[ao]\b/i /\banterior|pasad[oa]\b/i /\best[ae]\b/i /\b(antes|atr[aá]s)\b/ '.
					'/\bdomingo\b/i /\blunes\b/i /\bmartes\b/i /\bmi[eé]rcoles\b/i /\bjueves\b/i /\bviernes\b/i /\bs[aá]bado\b/i '.
					'/\bhoras?\b/i /\bminutos?\b/i /\bseg(undo)?s?\b/i '.
					'/\benero\b/i /\bfebrero\b/i /\bmarzo\b/i /\babril\b/i /\bmayo\b/i /\bjunio\b/i /\bjulio\b/i '.
					'/\bagosto\b/i /\bseptiembre\b/i /\boctubre\b/i /\bnoviembre\b/i /\bdiciembre\b/i'
				)
			);

			$err_msg = array(
				'pt' => 'Data inválida!',
				'es' => '¡Fecha inválida!',
				'en' => 'Invalid Date!'
			);
			$str_date = $date;
			if (!count(array_intersect(explode(' ', $date), $traduz['en']))){
				$date = preg_replace($traduz[$lang], $traduz['en'], $date); // Traduz para o inglês
				if($date == $str_date) return false ;
			}

			if (is_bool(strtotime($date)))
				if ($die)
					throw new Exception($err_msg[$lang]);
				else
					return false;
			$date = str_replace(' 00:00:00', '', date('Y-m-d H:i:s', strtotime($date)));
			return is_date($date, $ifmt, $ofmt, $die, $lang);
		}

		if (strtoupper($ifmt) == 'EUR' or !preg_match($ISO_DATE, $date))
			$date = preg_replace($EUR_DATE, $flipDateRE, $date);

		extract($p);

		if (!checkdate($M, $D, $Y)) {
			if ($die)
				throw new Exception($datename.': '.$err_msg[$lang]);
			else
				return false;
		}

		// data por extenso, mas com o mês de 3 letras
		if (preg_match('/c[ou]rt[oa]|short/', $ofmt))
			$ofmt = 'curta';

		switch(strtoupper($ofmt)) {
		case 'I': case 'ISO':
			return $date;
		case 'E': case 'EUR':
			return str_replace('-','/',preg_replace($ISO_DATE, $flipDateRE, $date));
		case 'D': case 'DEU':
			return str_replace('-','.',preg_replace($ISO_DATE, $flipDateRE, $date));
		case 'S': case 'SQL': case 'PG': case 'PGSQL': case 'POSTGRES':
			return str_replace('T',' ',$date);
		case 'C': case 'CDATE':
			return preg_replace('#\s|[T/:-]#','',$date);
		case 'TIMESTAMP':
		case '@': case 'U': case 'TS':
		case 'UNIX': case 'LINUX':
			return strtotime($date);
		case 'LONG': case 'EXT': case 'EXTENSO':
			return long_date($date, $lang, 'normal');
		case 'CURTA':
			return long_date($date, $lang, 'short');
		default:
			// Tries a custom formatting if everything else fails
			return date($ofmt, strtotime($date));
		}
		return true;
	}
}

/**
 * @name  pg_quote
 * @returns string
 * @param str     array|string  Se for array, converte para array postgreSQL a não ser que o 2º parâmetro seja true, que devolve CSV
 * @param is_str    boolean     Se o 1º parâmetro é string, define se é tratado como string ou numérico (string vai ter aspas, numérico não)
 *                    Se o 1º parâmetro é TRUE, FALSE ou NULL, ignora este parâmetro e devolve apenas a string correta
 *                    Se o 1º parâmetro é um array, define se o retorno será CSV (TRUE) ou um array PostgreSQL (default)
 * @param num_arr   boolean     Se o 1º parâmetro é array, define se os valores são numéricos (TRUE) ou não. Default para não.
 * @see_also pg_parse_array
 * @example
 *
 * Um array para array postgreSQL
 * php > echo pg_quote($meses['es']);
 *    '{"Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"}'
 *
 * Seria o mesmo que:
 * php > echo pg_quote($meses['en'], false, false);
 * '{"January","February","March","April","May","June","July","August","September","October","November","December"}'
 *
 * Um array para CSV (aspas simples, não é 100% CSV, para conversões mais complexas, ver array2CSV())
 * php > echo pg_quote($meses['es'], true);
 *    'Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'
 * php > echo pg_quote($meses['es'], true, true);
 *    Enero,Febrero,Marzo,Abril,Mayo,Junio,Julio,Agosto,Septiembre,Octubre,Noviembre,Diciembre
 *
 * Strings várias:
 * php > echo pg_quote($meses['en'][3]);
 *    'March'
 * php > echo pg_quote(56, true);
 *    '56'
 * php > echo pg_quote(56, false);
 *      56
 * php > echo pg_quote('true');
 *    TRUE
 * php > echo pg_quote(NULL);
 *    NULL
 * php > echo pg_quote(false);
 *      FALSE
 *
 **/
if (!function_exists('pg_quote')) {
	function pg_quote($str, $is_str = false, $num_arr = null) {
			if (is_bool($str))  return ($str===true) ? 'TRUE':'FALSE';
		if (is_null($str))  return 'NULL';

		//$STR ARRAY
		if (is_array($str)) {
			if ($is_str == false) { // Devolver como Array postgreSQL
				return pg_escape_literal($GLOBALS['con'], pg_parse_array($str, !$num_arr)); // pg_parse_array o 2º parâmetro define se é string
			}
			// Devolver como CSV
			$sep = ($num_arr) ? ',' : "','";    // separa com vírgulas se for numérico, coloca entre aspas se não for

			$tmp_ret = implode($sep, array_filter($str));
			return ($num_arr) ? $tmp_ret : "'$tmp_ret'";
		}

		if (in_array(strtolower($str),array('null','true','false'))) return strtoupper($str);

		//IS_STR == FALSE E $STR != ARRAY
		if (preg_match("/^-?\d+(\.\d+)?$/", $str) and !$is_str) return $str;

		return pg_escape_literal($GLOBALS['con'], $str);
	}
}

	/**
	 *  Gera uma string para usar como condição na cláusula WHERE de uma consulta SQL
	 *  $campo    Nome do campo, pode conter ou não o nome da tabela
	 *  $valores  Dependendo do tipo, a função monta a condição:
	 *          bool  usa IS $valor (Ex. pg_where('a', true) devolve 'a IS TRUE')
	 *          null  devolve $campo IS NULL
	 *          string  se não contém ',', dá um '=' (devolve '$campo = $valores')
	 *          array ou valores separados por vírgula:
	 *              devolve $campo IN($valores) cada ítem entre aspas (ver $numeric)
	 *  $numeric  (opcional) informa à função se o campo é numérico, para usar ou não aspas
	 *
	 **/
if (!function_exists('pg_where')) {
	function pg_where($campo,$valores,$numeric = false) {
	//  Confere valores especiais
		if (is_null($valores))  return "$campo IS ".iif(!is_bool($numeric),$numeric).'NULL';
		if ($valores===true)  return "$campo IS ".iif(!is_bool($numeric),$numeric).'TRUE';
		if ($valores===false) return "$campo IS ".iif(!is_bool($numeric),$numeric).'FALSE';
		if ($valores=='')       return $valores;
	//  Converte valores CSV para array
		if (!is_array($valores) && strpos($valores, ',')!==false) {
			$a_valores = array_map('trim', explode(',',$valores));
		} else {
				$a_valores = iif((is_array($valores)),$valores,array($valores));
			$a_valores = array_filter($a_valores);
			if (!count($a_valores)) return false;
		}
		$sep = ($numeric) ? ',' : "','";    // separa com vírgulas se for numérico, coloca entre aspas se não for
			$tmp_ret = implode($sep, array_filter($a_valores));
		if (!$numeric) $tmp_ret = "'$tmp_ret'";
		if ($campo == '') return $tmp_ret;  // para devolver só os valores separados por vírgula, setar '$campo' como ''

		return (count($a_valores)>1) ? $tmp_ret = "$campo IN ($tmp_ret)" : "$campo = $tmp_ret";
	}
}

if (!function_exists('pg_parse_array')) {
	function pg_parse_array($var, $is_str = false) {
		if (is_array($var)) {  // PHP Array -> SQL ARRAY
			foreach($var as $key=>$val) {

				//Determina valores "especiais"
				if (is_null($val))  $var[$key] = 'NULL';
				if ($val === true)  $var[$key] = 'TRUE';
				if ($val === false) $var[$key] = 'FALSE';

				if ($is_str===true or $is_str=='string')
					$var[$key] = "\"$val\"";
			}
			return '{' . implode(',', $var) . '}';

		} else {  // SQL ARRAY -> PHP Array

			// Se não tem a sintaxe certa, sai fora com FALSE
			if ($var[0] != '{' and substr($var,1) != '}') return false;

			// Confere se é um array SQL de tipo sting (syntax: {"value","value",...,"value"}  )
			$strArr = preg_match_all('/((:?"[^"]+")|NULL|FALSE|TRUE),?/', substr($var,1,-1),$arr);
			if ($strArr) {
				foreach($arr[1] as $i => $val) {
					$arr[1][$i] = ($val == 'NULL') ? null :
						(($val == 'TRUE')  ? true :
						(($val == 'FALSE') ? false:
						preg_replace('/^"|"$/', '', $val)));
				}
				return $arr[1]; //Achou um array postgres com tipo string, devolve o array
			}

			return explode(',', substr($var, 1, -1)); // tira os { e } do array Postgres e devolve um array com o conteúdo

			// Se o preg_match devolveu vários
		}
	}
}

if (!function_exists('pg_begin')) {
	function pg_begin($savepoint=null) { // BEGIN function pg_begin
		global $con, $begin_aberto;

	//  Se já há um begin aberto, não abrir um outro, o banco retorna um Warning
		if ($begin_aberto > 0 and is_null($savepoint)) return false;
		$sql = (is_null($savepoint)) ? 'BEGIN TRANSACTION' :"SAVEPOINT $savepoint";
		$res = @pg_query($con, $sql);
		if (is_resource($res)) {
			$begin_aberto+= 1;
			return $res;
		}
		return false;
	} // END function pg_begin
}

/**
 * pg_fetch_pairs()
 * Dada uma consulta que retorna dois (ou mais, que serão ignorados!) campos,
 * retorna um array com o primeiro campo como chave e o segundo como valor.
 * Foi pensado para SELECTs html.
 * Se o SELECT (sql) tem um só campo, um array de um só campo é retornado.
 **/
if (!function_exists('pg_fetch_pairs')) {
	function pg_fetch_pairs($con, $sql) {
		if (!is_resource($con))
			throw new Exception('Invalid DataBase Connection Resource!');

		$res = pg_query($con, $sql);

		if (pg_last_error($con))
			return false;
		// throw new Exception(pg_last_error($con));

		// if (pg_field_num($res) != 2) {
		//  throw new Exception('Query returned more than two columns!');
		// }

		$single = (pg_num_fields($res) == 1);

		$ret = array();
		while ($row = pg_fetch_row($res)) {
			if ($single)
				$ret[] = $row[0];
			else
				$ret[$row[0]] = $row[1];
		}
		return $ret;
	}
}

if (!function_exists('pg_commit')) {
	function pg_commit($savepoint=null) { // BEGIN function pg_commit
		global $con, $begin_aberto;
		if (!$begin_aberto) return false;
		$sql = (is_null($savepoint)) ? 'COMMIT' : "RELEASE SAVEPOINT $savepoint";
		$res = @pg_query($con, $sql);
		if (is_resource($res)) {
			$begin_aberto-= 1;
			return $res;
		}
		return false;
	}
}

if (!function_exists('pg_rollBack')) {
	function pg_rollBack($savepoint=null) { // BEGIN function pg_rollBack
		global $con, $begin_aberto;
		$sql = (is_null($savepoint)) ? 'ROLLBACK TRANSACTION' : "ROLLBACK TO SAVEPOINT $savepoint";
		$res = @pg_query($con, $sql);
		if (is_resource($res)) {
			$begin_aberto-= 1;
			return $res;
		}
		return false;
	}
}

/**
 * @name   pg_enum_values()
 * @param  String enum type namespace
 * @return array
 * @author Manuel López <manuel.lopez@telecontrol.com.br>
 * @description
 * Devolve um array com os valores (na ordem em que foram declarados) do tipo ENUM
 * informado como parâmetro.
 **/

if (!function_exists('pg_enum_values')) {
	function pg_enum_values($type_name) {
		global $con;
		$res = pg_query(
			$con,
			"SELECT UNNEST(ENUM_RANGE(NULL::$type_name)) AS $type_name"
		);
		if ($err = pg_last_error($con))
			return $err;

		if (!pg_num_rows($res))
			return [];

		return array_column(pg_fetch_all($res), $type_name);
	}
}

/**
 * Helper para a função getPost
 **/
if (!function_exists('getParam')) {
	function getParam($valor) {
	if (is_array($valor))
		return array_filter($valor, 'anti_injection');
	//else
	return anti_injection($valor);
	}
}

/**
 * @name getPost()
 * @param string                nome da KEY para o $_GET ou $_POST
 * @param boolean default false Se TRUE, vê primeiro no $_GET e depois no $_POST
 * $return mixed                NULL se não existe a chave, ou string com o conteúdo
 **/
if (!function_exists('getPost')) {
	function getPost($param,$get_first = false) {
		if ($get_first) {
			if (isset($_GET[$param])) {
				return  getParam($_GET[$param]);
			}
			if (isset($_POST[$param]))  {
				return  getParam($_POST[$param]);
			}
		} else {
			if (isset($_POST[$param]))  {
				return  getParam($_POST[$param]);
			}

			if (isset($_GET[$param]))  {
				return  getParam($_GET[$param]);
			}

		}
		return null;
	}
}

if (!function_exists('formatFileSize')) {
	function formatFileSize($tamanho, $precision = 2, $suffix = true) {
		$fs_suffix  = explode(',','b,KiB,MiB,GiB');
		$m = 0;
		while ($tamanho > 1024) {
			$m++;
			$tamanho = $tamanho / 1024;
		}
		$tamanho    = ($m == 0) ? $tamanho : number_format($tamanho, $precision, ',' , '.');
		if ($suffix) $tamanho .= ' ' . $fs_suffix[$m];
		return $tamanho;
	}
}

/**
 * Funções que geram código HTML
 **/
if (!function_exists('p_echo')) {
	function p_echo ($str, $style = "", $die=false) {
		$prn = (isCLI) ? sprintf("%1\$s[1m%2\$s%1\$s[0m\n", chr(27), $str)
						 : "<p $style>".$str."</p>\n";
		echo ($prn);
		if ($die) die;
	}
	// alias
	function pecho ($str, $style = "", $die=false) {
		p_echo($str, $style, $die);
	}
}

if (!function_exists('pre_echo')) {
	function pre_echo ($var, $header='', $die=false) {
		$hd = empty($header) ? '' : "<h4 style='font-weight:bold'>$header</h4>";
		$var_content = !is_string($var) ?  var_export($var, true)   : $var;
		if (isCLI)
			printf('%1$s[1m%2$s%1$s[0m%4$s%3$s', chr(27), $header, $var_content, PHP_EOL);
		else
			printf("%s<pre>\n%s</pre>\n", $hd, $var_content);
		if ($die)
			die;
	}
}

	/*************************************
	 * Converte um array em tabela HTML
	 * Parámetros obrigatórios:
	 *  $arr      O array a ser convertido. Ver opções abaixo.
	 * Parámetros opcionais:
	 *  $caption    Caso não passe por parâmetros no array, pode passar o "título" (<caption>) da tabela aqui
	 *  $usesTiny   TINY TableSorter precisa que os TH do header estejam embutidos num h3 (th>h3)
	 *  $keysAsHeaders  Usar as 'key' do array como headers (<th>) da tabela. É o padrão.
	 *  $hasHeaders   Se colocou a lista de cabeçalhos das colunas como opções do array, passar true (default false)
	 *
	 *  Parâmetros dentro do array:
	 *  [under construction]
	 *  Ex.:
	 *  array (
	 *   'headers' => array(// cabeçalhos das colunas //),
	 *   'attrs' => array(
	 *      //o 'caption' vai dentro da tag <caption>
	 *     'caption' => 'Título do array',
	 *      //esta vai dentro do <table>: <table style="...."> o espaço no começo é importante!
	 *     'tableAttrs' => ' style="margin:auto,background-color:white;border-collapse:separate"',
	 *      //atributos para a tag <caption>
	 *      'captionAttrs' => ' color: white;background: navy; text-align: center"',
	 *      //atributos para a tag <thead>
	 *      'headerAttrs' => ' color: white;background: navy; text-align: center"',
	 *      //atributos para cada linha da tabela
	 *      'rowAttrs' => ' background-color:#fafafa',
	 *      //atributos para cada célula da tabela
	 *      'cellAttrs' => ' color: navy'
	 *      // zebrado: array com as cores a serem usadas. Normalmente duas cores, mas pode ter mais
	 *      // O valores são cores CSS válidas
	 *      'trBg' ou 'zebra' ou 'zebrado' ou 'trColors' => array('#fff','lightgrey')
	 *    ),
	 *    //Aqui viria um array normal, as "keys" passariam a ser THs
	 *  )
	 ************************************/
if (!function_exists('array2table')) {
	function array2table($arr, $caption=null, $usesTiny=false, $keysAsHeaders=true, $hasHeaders=false) {
		global $tableAttrs;

		if (!is_array($arr)) {
			if (is_resource($arr)) {
				if (get_resource_type($arr) == 'pgsql result') {
					$data = pg_fetch_all($arr);
					unset($arr);
					$arr = $data;
					$arr_is_res = true;
					unset($data);
				} else {
					return false;
				}
			} else {
				return false;
			}
		}

		if (!array_key_exists('attrs', $arr) and is_array($tableAttrs))
			$arr['attrs'] = $tableAttrs;

		if (isset($arr['attrs'])) {
			extract ($a = $arr['attrs'], EXTR_PREFIX_ALL, 'A2T');

			// Renomeia a variável se veio com outro nome. Tipo alias.
			$rowAttrs     = $A2T_rowAttrs     ? : $A2T_trAttrs;
			$cellAttrs    = $A2T_cellAttrs    ? : $A2T_tdAttrs;
			$headerAttrs  = $A2T_headerAttrs  ? : $A2T_thAttrs;
			$captionAttrs = $A2T_captionAttrs ? : '';
			$trColors     = $A2T_trColors     ? : null;

			foreach (array('zebra','zebrado','trBg','trColors') as $key) {
				$val = $arr['attrs'][$key];
				if (is_array($val) and count($val) > 1) {
					$trColors = $val;
					break;
				}
			}
			unset($arr['attrs']);
		}

		// Ao usar uma função anónima, o valor de $rowAttrs é
		// o valor que tem neste momento, assim, preserva seu
		// valor original e pode ser reasignado
		$trStyle = function($i=0) use ($rowAttrs, $trColors) {
			if (!is_array($trColors))
				return $rowAttrs;

			$color = $trColors[$i%count($trColors)];

			// e já vem um style como atributo da fila...
			if (strpos($rowAttrs, 'style')) {
				return preg_replace('/style=[\'"](.*?)["\'](\s?)/', "style='$1;background:$color;'$2", $rowAttrs);
			} else {
				return $rowAttrs." style='background:$color'";
			}
		}; // Salva o valor original

		$table = $A2T_tableAttrs ? "<table $A2T_tableAttrs>\n" : "<table>\n";

		if (!is_null($caption))
			$table .= "<caption$captionAttrs>$caption</caption>\n";

		if ($keysAsHeaders or $hasHeaders) {
			$table.= "\t<thead $headerAttrs>\n".
				"\t<tr>\n";

			if($hasHeaders) {
				$a_head = $arr['headers'];
			} else {
				$a_head = array_keys(reset($arr));
			}

			foreach ($a_head as $header) {
				$header = str_replace('_', ' ', $header);
				if (strpos($header, "style") !== false) continue;// pula os elementos de estilo do array
				$table.= ($usesTiny) ? "\t\t<th><h3>$header</h3></th>\n" : "\t\t<th>$header</th>\n" ;
			}
			$table.= "\t</tr>\n\t</thead>\n";
		}

		$z = 0; // inicializa o zebrado
		$table.= "\t<tbody>\n";

		if ($keysAsHeaders) {
			foreach ($arr as $rowheader => $row) {
				if (strpos($rowheader, "style") !== false) continue;// pula os elementos de estilo do array

				$rowAttrs = $trStyle($z++);
				$table.= "\t\t<tr$rowAttrs>\n";

				foreach ($row as $title => $cell) {
					$temp    = null;
					$cellval = null;

					$cellval = pg_parse_array($cell);
					if (is_array($cellval)) {
						$cell = $cellval;
						unset($cellval);
					}
					if (is_array($cell)) {
						foreach ($cell as $line)
							$temp .= "<div style='display:table-row'>$line</div>";
						unset($cell); //Se for array, tem que destruir...
						$cell = $temp;
					}

					$table.= "\t\t\t<td$cellAttrs".
						iif ($title != '' and !strpos($cell, 'title='), " title='$title'").
						">$cell</td>\n";
				}

				$table.= "\t\t</tr>\n";
			}
		} else {
			foreach ($arr as $rowHeader => $row) {
				if ($rowheader == "attrs" or
					$rowheader == "headers") continue;// pula os elementos de estilo do array

				$rowAttrs = $trStyle($z++);
				$table.= "\t\t<tr$rowAttrs>\n";

				if ($hasHeaders) {
					foreach ($row as $cell) {
						$cellval   = null;
						$cellvalue = null;
						if ($arr_is_res) { // Verificar se o campo é um array postgres...
							$cellval = pg_parse_array($cell[0]);
							if (is_array($cellval)) {
								$cell = $cellval;
							}
							unset($cellval);
						}

						list ($cellvalue,$celltitle) = $cell;

						if (is_array($cellvalue)) {
							$cellval = implode('<br />', $cellvalue);
							unset($cellvalue); //Se for array, tem que destruir a variável para poder reusar como scalar.
							$cellvalue = $cellval;
						}

						$table.= "\t\t\t<td$cellAttrs".
							iif (($title != ""), " title='$celltitle'").
							">$cellvalue</td>\n";
					}
				} else {
					foreach ($row as $cell) {
						$cellval   = null;
						list ($cellvalue,$celltitle) = $cell;

						if ($arr_is_res) { // Verificar se o campo é um array postgres...
							$cellval = pg_parse_array($cellvalue);
							if (is_array($cellval)) {
								$cellvalue = $cellval;
							}
							unset($cellval);
						}

						if (is_array($cellvalue)) {
							$cellval = implode('<br />', $cellvalue);
							unset($cellvalue); //Se for array, tem que destruir a variável para poder reusar como scalar.
							$cellvalue = $cellval;
						}

						$table.= "\t\t\t<td$cellAttrs".
							iif (($celltitle != ""), " title='$celltitle'").
							">$cellvalue</td>\n";
					}
				}

				$table.= "\t\t</tr>\n";
			}
		}
		return $table."\t</tbody>\n</table>\n";
	}
}

if (!function_exists('array2select')) {
	function array2select($name, $id, $dados, $default = '', $attr = '', $branco = ' ', $usar_key = false) {

		global $lastSelectedValue, $lastSelectedKey;

		if (is_resource($dados) and strpos(get_resource_type($dados), 'sql')) {
			while ($row = pg_fetch_array($dados))
				$a_dados[$row[0]] = $row[1];
			unset($dados, $row);
		} else {
			$a_dados = (is_array($dados)) ? $dados : explode(',',$dados);
		}

		if (!count($a_dados)) return "<select id='$id' name='$name' $attr></select>\n";

		$select = "<select name='$name' id='$id' $attr>\n";
		$select.= ($branco) ? "\t<option value=''>$branco</option>\n" : '';

		foreach ($a_dados as $key=>$value) {

			if (is_array($value)) {
				$optName = key($name);
			}
			$sel   = iif(_is_in($valor, $default), ' selected');
			$valor = ($usar_key) ? $key : $value;
			if(is_array($default)) {
				$sel = iif(in_array($valor,$default), ' selected');
			} else {
				$sel = iif($valor == $default, ' selected');
			}
			if ($sel) {
				$lastSelectedValue = $value;
				$lastSelectedKey   = $key;
			}
			$select.= "\t<option value='$valor'$sel>$value</option>\n";
		}
		return $select . "</select>\n";
	}
}

if (!function_exists('createHtmlOption')) {
	function createHtmlOption($value='', $text='', $selected=false) {
		$sel = ($selected) ? " selected='selected'" : '';
		return sprintf("\t\t<option value='%s'%s>%s</option>\n", $value, $sel, $text);
	}
}

if (!function_exists('createHTMLLink')) {
	function createHTMLLink($url, $innerHTML, $params='') {
		return trim("<a href='$url' $params").">$innerHTML</a>";
	}
}

	/***
	 * @name: array2CSV
	 * @param:  $data (array) array com os dados a serem convertidos. A função foi pensada para tratar o
					retorno do pg_fetch_all() portanto, a estrutura do array seria:
					array(
						0 => array('key'=>'data0', 'key1'=>'data2', ..)
						1 => array('key'=>'data3', 'key1'=>'data4', ..)
						..
						n => array('key'=>'datay', 'key1'=>'dataz', ..)
					)
					O retorno seria:
					"key","key1"
					data0,data2
					data3,data4
					..
					"datay","dataz"

	 * @param:  $sep (string) Optional. Altera o separador padrão (,)
	 * @param: $outputHeaders Optional, Boolean TRUE para incluir uma 1ª linha com o nome das "colunas"
	 * @param: $quoteStr    Optional, Boolean FALSE para NÃO usar aspas duplas em campos de texto
	 * @return: (mixed)     FALSE se houve algum erro (parâmetros errados, normalmente) ou uma string com
							a conversão
	 ***/
if (!function_exists('array2csv')) {
	function array2csv($data, $sep=',', $outputHeaders=false, $quoteStr=true) {

		global $debug;

		if (!is_array($data)) {

			if (is_resource($data)) {
				if (get_resource_type($data) == 'pgsql result') {
					$arr = pg_fetch_all($data);
					$arr_is_res = true;
					unset($data);
				} else return false;
			} else {
				return false;

				// TO-DO: tratar um CSV para conversão. Avaliar uso de função
				// interna do PHP
				// Vè se não é um CSV... Pode servir para mudar de separador
				// (p.e.: array2csv($csvStr, ',;') para converter "1,2,3" em "1;2;3")
				if (!strpos($sep, $data) or strlen($sep)!=2)
					return false;

				$arr = str_getcsv($data, $sep[0]);
				// Segue o código para tratar CSV simples (com linhas separadas por '\n')
				// TO-DO
				return $csv;
			}
		} else {

			$arr = $data;
			unset($data);

		}

		if (is_null($sep) or !$sep)
			$sep = ',';

		$headers = array();

		if ($outputHeaders) {
			if (isCLI and $debug) {
				echo "Cabeçalhos: " . implode($sep, array_keys($arr[0]));
			}
			$headers[] = array_keys($arr[0]);
		}

		if (isCLI and $debug)
			var_export($arr);

		foreach (array_merge($headers, $arr) as $i => $arrItem) {

			foreach($arrItem as $j => $data) {
				if (!$quoteStr)
					$data = str_replace($sep, "\\" .$sep, $data); // Escapa o caractere de separação, se existir
				if ($quoteStr and is_string($data))
					$arrItem[$j] = (is_date($data, 'EUR', 'bool')) ? $data : "\"$data\"";
			}

			$csv[] = implode($sep, $arrItem);
		}

		if (isCLI and $debug)
			print_r($csv);

		return implode(chr(10), $csv);

	}
}

if (!function_exists('getAttachLink')) {
		function getAttachLink($arquivo, $base_url = null, $return_array = false) { // BEGIN function getAttachLink
				global $a_trad;
				$tipo_arquivo = array(
						'imagens'   => array(
								'ext'   => '/gif|jpg|jpeg|png|bmp$/',
								'ico'   =>  'image.ico',   /*  Imagen_PNG.ico  */
								'desc'  => 'click_ver_imagem',
								'acao'  => "<a href='%s' title='%s' target='_blank'>Visualizar imagem&nbsp;<!--%s--><img src='img/%s'></a> \n"),
						'docs'  => array(
								'ext'   => '/doc|docx$/',
								'desc'  => 'click_ver_doc_online',
								'ico'   =>  'icone_DOCx.png',
								'acao'  => "<a href='http://docs.google.com/viewer?url=%s' title='%s' target='_blank'>Abrir documento</a> \n".
													 "<a href='%s' title='Baixar'>&nbsp;<img src='img/%s'></a>"),
						'openDoc'  => array(
								'ext'   => '/odt|odf|rtf|sxw|wp|wpd$/',
								'desc'  => 'click_ver_doc_online',
								'ico'   =>  'icone_ODF.png',
								'acao'  => "<a href='http://docs.google.com/viewer?url=%s' title='%s' target='_blank'>Abrir documento</a> \n".
													 "<a href='%s' title='Baixar'>&nbsp;<img src='img/%s'></a>"),
						'ppoint'  => array(
								'ext'   => '/ppt|pptx|ppd|odp|sxi|sxd$/',
								'desc'  => 'click_ver_doc_online',
								'ico'   =>  'icone_PPTx.png',
								'acao'  => "<a href='http://docs.google.com/viewer?url=%s' title='%s' target='_blank'>Abrir documento</a> \n".
													 "<a href='%s' title='Baixar'>&nbsp;<img src='img/%s'></a>"),
						'excel' => array(
								'ext'   => '/xls|xlsx$/',
								'desc'  => 'click_ver_xls_online',
								'ico'   =>  'icone_XLS.png',
								'acao'  => "<a href='http://docs.google.com/viewer?url=%s' title='%s' target='_blank'>".
													 "Abrir documento &nbsp;<a href='%s' target='_blank' title='Baixar documento'><img src='img/%s'></a> \n"),
						'planilhas' => array(
								'ext'   => '/sxw|sxc|sxi|rtf$/',
								'desc'  => 'click_ver_xls_online',
								'ico'   =>  'icone_ODS.png',
								'acao'  => "<a href='http://docs.google.com/viewer?url=%s' title='%s' target='_blank'>".
													 "Abrir documento &nbsp;<a href='%s' target='_blank' title='Baixar documento'><img src='img/%s'></a> \n"),
						'csv' => array(
								'ext'   => '/csv$/',
								'desc'  => 'click_ver_xls_online',
								'ico'   =>  'icone_CSV.png',
								'acao'  => "<a href='http://docs.google.com/viewer?url=%s' title='%s' target='_blank'>".
													 "Abrir documento &nbsp;<a href='%s' target='_blank' title='Baixar documento'><img src='img/%s'></a> \n"),
						'pdf'       => array(
								'ext'   => '/pdf$/',
								'desc'  => 'click_ver_doc_pdf',
								'ico'   =>  'icone_PDF.png',
								'acao'  => "<a href='http://docs.google.com/viewer?url=%s' title='%s' target='_blank'>Abrir&nbsp;PDF</a> \n".
													 "<a href='%s' target='_blank' title='Baixar documento'><img src='img/%s'></a>"),
						'html'      => array(
								'ext'   => '/htm|html|shtm|shtml|php|asp|pl|py$/',
								'desc'  => 'click_ver_doc_online',
								'ico'   =>  'icone_HTML.png',
								'acao'  => "<a href='%s' title='%s' target='_blank'>Abrir&nbsp;XML/HTML</a> \n".
													 "<a href='%s' title='Baixar documento'><img src='img/%s'></a>"),
						'xml'      => array(
								'ext'   => '/xml$/',
								'desc'  => 'click_ver_doc_online',
								'ico'   =>  'icone_XML.png',
								'acao'  => "<a href='%s' title='%s' target='_blank'>Abrir&nbsp;XML/HTML</a> \n".
													 "<a href='%s' title='Baixar documento'><img src='img/%s'></a>"),
						'compactado'=> array(
								'ext'   => '/7z|arj|lha|gzip|lzh|rar|tar|zip|gz|ace|z$/',
								'desc'  => 'click_baixar_arquivo',
								'ico'   =>  'Comprimidos_ZIP.ico',
								'acao'  => "<a href='%s' title='%s'>Baixar Arquivo<!--%s--><img src='imagens/%s'></a> \n"),
						'SQL'=> array(
								'ext'   => '/sql|psql|roles|dump|dump_\w{2}$/',
								'desc'  => 'click_baixar_arquivo',
								'ico'   =>  'icone_SQL.png',
								'acao'  => "<a href='%s' title='%s' target='_blank'>Baixar Arquivo<!--%s--><img src='img/%s'></a> \n")
				);
				$url = !is_null($base_url) ? $base_url : '';
				$url.= str_ireplace(array('/var/www/','telecontrol/www/','assist/www/'), array('','/','/assist/'), $arquivo);
				foreach ($tipo_arquivo as $tipo_desc) {
						extract($tipo_desc);
			$nome_arquivo = strpos($arquivo, '?') !== false ?
				basename(substr($arquivo, 0, strpos($arquivo, '?'))) :
				basename($arquivo);

						if (preg_match($ext.'i',$nome_arquivo)) {
								if ($return_array) {
										$tipo_desc['url'] = $url;
										$tipo_desc['filename'] = $nome_arquivo;
										return $tipo_desc;
				} else {
					$desc = ($desc == '') ? 'Baixar' : $desc;
					if(strpos($acao,'google')){
						return sprintf($acao, urlencode($url), $desc, $url, $ico);
					}
										return sprintf($acao, $url, $desc, $url, $ico);
								}
						}
				}
				// Para o resto de arquivos não reconhecidos...
				$tipo_desc = array(
								'acao'     => "<a href='%s' title='%s'>Baixar Arquivo<!--%s--><img src='img/%s'></a> \n",
								'desc'     => 'click_baixar_arquivo',
								'filename' => $nome_arquivo,
								'ico'      => 'unknown_file.png',
								'url'      => $url
				);
				return ($return_array) ? $tipo_desc : "<a href='$url' target='_blank'>".basename($url)."</a>";
		} // END function getAttachLink
}

if (!function_exists('send_attachment')) {
	function send_attachment($sender, $from, $to, $file, $subj = 'Arquivo anexo', $msg = '', $charEnc = 'UTF-8') {
		if (!is_email($from)) return "Remitente não é válido!";
		// $to pode ter mais de um endereço...
		if (!strpos($to, ',') and !is_email($to)) return "Destinatário não é válido!";

	if (strpos($to, ',') > 0) {
		$a_dests = explode(',', $to);
		foreach ($a_dests as $value) {
					if (!is_email($value)) return "Destinatário:<br /><strong class='sangue'>$value</strong><br /> não é válido!";
				}
	}
	if (!file_exists($file)) return "O arquivo $file não existe!";
	if (empty($file)) return "Não foi informado o arquivo para enviar!";

	$boundary = "XYZ-" . date("dmYis") . "-ZYX";

	if (!$msg) $msg = "Arquivo enviado por $sender.<br />";

	$headers = <<<FHEADS
MIME-Version: 1.0\r
From: $from\r
Reply-To: $from\r
Content-type: multipart/mixed; boundary="$boundary"\r
$boundary
FHEADS;

	if (is_array($file) and isset($file['tmp_name'])) {

		// Vários anexos via _FILES: tmp_name == array
		// Se não é, joga o array dentro de um outro para
		// processar com o mesmo foreach
		$files = is_array($file['tmp_name']) ? parse_multiple_FILES($file) : array(0 => $file);

	} else {

		// Também podem vir um ou vários arquivos num array simples,
		// p.e. de um glob() ou similar
		if (!is_array($file)) {
			$files = array(0=>$file);
		}
		// Verifica se são arquivos, se existem e se podem ser lidos...
		$files = array_filter($files, 'is_readable');
	}

	if (!count($files))
		return ("Arquivo(s) inválido(s)!");

	$file_contents = '';

	// a função file_to_eml_part() admite como 'arquivo' tanto
	// um array estilo _FILE[x] quanto uma string com o path,
	// assim, podemos processar as duas formas com o mesmo foreach!
	foreach ($files as $att) {
		$file_part = file_to_eml_part($att, $boundary);

		if (is_array($file_part)) {
			$file_contents .= $file_part['eml_part'];
			$cid = ($file_part['cid']) ? $file_part['cid'] : null;
		} else {
			$file_contents .= $file_part;
			$cid = null;
		}
		unset($file_part);

		if (!is_null($cid)) {
			$imgAtt .= "\n<br /><img src=\"cid:" . md5(date('Ymd')) . "\" />\n";
		}
	}

	$corpo_mensagem = <<<MSGBODY
<html>
<head>
	 <title>$subj</title>
</head>
<body style="font:normal normal 14px/16px Trebuchet, Arial, helvetica, sans-serif;color:#333333">
<br />
$msg
<p>Atte., Suporte Telecontrol.</p>$imgAtt
</body>
</html>
MSGBODY;

	// Nas linhas abaixo vamos passar os parâmetros de formatação e codificação, juntamente com a inclusão do arquivo anexado no corpo da mensagem.
		$mensagem = "--$boundary\n";
		$mensagem.= "Content-Transfer-Encoding: 8bit\n";
		$mensagem.= "Content-Type: text/html; charset=\"$charEnc\"\n\n";
		$mensagem.= "$corpo_mensagem\n";
		$mensagem.= "$file_contents";
		$mensagem.= "--$boundary--\n";

	return mail($to, $subj, $mensagem, $headers);
	}
}

/**
 * @name: parse_multiple_FILES(array)
 * @param:   $files   Array do arquivo ou arquivos Se só tiver um, devolve o mesmo array, senão, devolve um novo array mais... lógico.
 * @returns: $array   Se o parâmetro tiver vários uploads, devolve um array de índice numérico com todos os dados de cada um.
 **/
if (!function_exists('parse_multiple_FILES')) {
	function parse_multiple_FILES(array $files) {
		if (!is_array($files['name']))
			return $files;

		$c = count($files['name']); // Total de arquivos

		for ($f = 0; $f < $c; $f++) {
			$newFiles[$f] = array(
				'name'     => $files['name'][$f],
				'tmp_name' => $files['tmp_name'][$f],
				'type'     => mime_content_type($files['tmp_name'][$f]),
				'size'     => $files['size'][$f],
				'error'    => $files['error'][$f],
			);
		}
		return $newFiles;
	}
}

if (!function_exists('MontarLink')) {
	function MontarLink($text) {
		return preg_replace(
			[
				'#((?:ftp|http)s?://?[-\w]+\.[-\w\.]+(?:\w\d+)?(/([-~\w/_.]+(\?\S+)?)?\w)*)#i',
				'#([[:space:]()[{}])(www.[-a-zA-Z0-9@:%_\+.~\#?&//=]+)#i',
				'#([_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,3})#i',
			], [
				'<a href="\\1" target="_blank">\\2</a>',
				'\\1<a href="http://\\2" target="_blank">\\2</a>',
				'<a href="mailto:\\1">\\1</a>',
			],
			html_entity_decode($text)
		);
	}
}

if (!function_exists('file_to_eml_part')) {
	function file_to_eml_part($arquivo, $bounds=null, $attach_name=null) {

		if (is_array($arquivo)) {
			$fcontents = $arquivo['tmp_name'];
			$fname     = preg_replace('/[^A-Za-z0-9.]/', '_', $arquivo['name']);
		} else {
			$fcontents = $arquivo;
			$fname     = preg_replace('/[^A-Za-z0-9.]/', '_', basename($arquivo));
		}

		if (!is_readable($fcontents))
			return false;

		if (!is_null($attach_name))
			$fname = preg_replace('/[^A-Za-z0-9.]/', '_', basename($attach_name));

		$mime = mime_content_type($fcontents);
		$disp = preg_match('/gif|png|jpg|pjpeg|jpeg|bmp/', $mime) ? 'inline' : 'attachment';
		$b64  = chunk_split(base64_encode(file_get_contents($fcontents)), 76, "\n");

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
/*
<!--[if lt IE 8]>
	<script src="http://ie7-js.googlecode.com/svn/version/2.0(beta3)/IE8.js" type="text/javascript"></script>
<![endif]-->
http://ie7-js.googlecode.com/svn/version/ie7.gif
*/
// vim: set noet ts=4 sts=4 fdm=syntax fdl=1 :

