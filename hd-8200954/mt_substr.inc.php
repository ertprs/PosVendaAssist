<?php
/**
 * mt_substr() - multiple substring
 * @param $str  string    String a ser processada
 * @param $mask string    Define a posição ou posições a serem devolvidas pela função.
 * @return mixed          Bool FALSE se não é possível usar a $mask na $str.
 *						  String solicitada pela $mask
 * @desc
 * Esta função permite extrair caracteres de uma string de várias formas, aceitando diferentes regras
 * para definir quais caracteres da $str retornar.
 *
 * Ao contrário da função interna substr(), esta função retorna FALSE se alguma das posições cai
 * "fora" da string a ser processada.
 *
 * A 'máscara' da string pode ser uma única posição (um inteiro), um range (dois inteiros separados
 * por um hífen '-'), ou uma máscara complexa que pode ser uma mistura dos anteriores ou inclusive
 * uma máscara binária.
 * $mask = '10'   $mask = '6-9'    $mask = '5,7-9'    $mask = '0000101110'
 * Também, no 3º caso, é possível informar de um array:
 * $mask = array(5, '7-9');
 *
 * Ainda, se num range o segundo número é menor que o primeiro, a função assume que o segundo número
 * refere-se à quantidade de caracteres a devolver, assim '8,3' irá retornar 3 caracteres à partir da
 * posição 8 (que seria a 7 para o substr()).
 *
 * Em TODOS os casos, a posição informada pela $mask é de BASE 1: o primeiro caractere corresponde
 * com a posição 1 e não 0 (como seria no substr()).
 **/
function mt_substr($str, $mask) {

	$ret = '';

	if (is_array($mask))
		$mask = implode(',', array_map('trim',$mask));

	/**
	 * A máscara binária é mais "tradicional": 0, o caractere não é retornado, 1, sim. Ex.:
	 * $str  = 'ABCDEFGH',
	 * $mask = '00100011'   - Para começar, o comprimento deve ser IGUAL.
	 *            C   GH    - Apenas esses serão devolvidos, portanto,
	 * mt_substr($str, $mask) devolveria 'CGH'
	 *
	 * $mask deve ter exatamente o mesmo comprimento que $str.
	 **/
	if (preg_match('/^[01]{'.strlen($str).'}$/', $mask)) {
		$binMask = array_filter(preg_split('//', $mask));
		foreach($binMask as $pos=>$bit)
			$ret .= $str[$pos-1];

		return ($ret) ? $ret : false;
	}

	/**
	 * opção mais simples de todas: posição única
	 **/
	if (is_numeric($mask) and (string)intval($mask) == $mask) {
		// usar [] é mais rápido que usar qualquer função
		return ($mask < 1 or $mask > strlen($str)) ? false : $str[--$mask];
	}

	/**
	 * valores separados por vírgulas, dois ou mais:
	 * $mask = '3,5,9'   retornaria essas posições
	 **/
	if (preg_match('/^[0-9,]+$/', $mask)) {

		$positions = array_map('trim', explode(',', $mask));

		foreach ($positions as $pos) {
			$ret .= mt_substr($str, $pos); // recursivo...
		}

		if (strlen($ret) != count($positions)) // alguma posição retornou false...
			return false;

		return $ret;
	}

	/**
	 * O range deve ser definido com posição inicial e final.
	 *   ex.: $mask='2-4' define que as posições a retornar são 2, 3 e 4.
	 **/
	if (preg_match('/^(\d+)[-](\d+)$/', $mask, $range)) { // um range, XX-YY

		list($mask,$min,$max) = $range;

		if ($min < 1 or $max < 1 or $min > strlen($str) or $max > strlen($str)) {
			return false;
		}

		if ($max < $min) { // assume que o segundo valor informa a quantidade de posições
			return false; // por enquanto não vai deixar usar essa opção. max += min - 1;
		}

		if ($max > strlen($str))
			return false;

		return substr($str, --$min, $max-$min);
	}

	/**
	 * Aumenta a complexidade: definição de padrão estilo impressão:
	 * $mask = '3,5-7,10' retorna as posições 3,5,6,7,10
	 *
	 * como isso tudo já existe, usaremos a recursão.
	 **/
	if (preg_match('/^\d[0-9,-]+$/', $mask)) {

		$positions = array_map('trim', explode(',', $mask));

		foreach ($positions as $pos) {
			$v = mt_substr($str, $pos); // recursivo...
			if ($v === false)
				return false; // se algum elemento retorna false, nem continua!
			$ret .= $v;
		}
		return $ret;
	}

}

