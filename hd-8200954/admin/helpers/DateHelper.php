<?php

class DateHelper {

	/**
	 * Valida data informada pelo usuario (padrao dd/mm/YYYY)
	 * @param Mixed $date pode ser uma data, ou um array com varias datas. Padrão d/m/Y
	 * @return Mixed Exception caso erro, true caso sucesso. Usar dentro de bloco try.. catch
	 * @todo melhorar valida��es.. verificar data > data atual, verificar timestamp, etc.
	 */
	public static function validate($date) {

		if (empty($date)) {
			throw new Exception("Data Inv�lida");
		}

		if (!is_array($date)) {

			list($d, $m, $y) = explode ('/', $date);

			if ( empty($d) || !checkdate ($m, $d, $y) ) {

				throw new InvalidArgumentException("Data Inv�lida");

			}

			return true;

		}

		foreach ($date as $item) {
			
			list($d, $m, $y) = explode ('/', $item);

			if ( empty($d) || !checkdate ($m, $d, $y) ) {

				throw new InvalidArgumentException("Data Inv�lida");

			}

		}

		return true;

	}

	/**
	 * Valida se a data inicial e a data final est� entre o periodo informado (padr�o 30 dias)
	 * @param string $inicial data inicial para a valida��o
	 * @param string $final data final para a valida��o
	 * @param int $periodo (opcional) quantidade de dias permitidos para a valida��o. Default 30 dias.
	 * @param string $msg para definir a mensagem, p.e. 30 dias, 1 m�s, etc. Default 'um m�s'.
	 * @return mixed exception caso n�o valide, true para periodo valido
	 */
	public static function validaPeriodo( $inicial, $final, $periodo = '1 month', $msg = 'um m�s') {

		static::validate(array($inicial, $final)); // Apenas para garantir.. vai que..

		$inicial 	= static::converte($inicial);
		$final 		= static::converte($final);

		if (strtotime($inicial) > strtotime($final) ) {
			throw new InvalidArgumentException("Data Inv�lida");			
		}
		
		if (strtotime("$inicial +$periodo") < strtotime($final) ) {
			throw new InvalidArgumentException("O intervalo entre as datas n�o pode ser maior que $msg");
        }

        return true;

	}

	public static function converte($data) {

		$delim = strpos($data, '/') !== FALSE ? '/' : '-';
		
		return implode($delim, array_reverse(explode($delim, $data)));

	}

}