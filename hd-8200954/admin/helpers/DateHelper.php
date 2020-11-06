<?php

class DateHelper {

	/**
	 * Valida data informada pelo usuario (padrao dd/mm/YYYY)
	 * @param Mixed $date pode ser uma data, ou um array com varias datas. PadrÃ£o d/m/Y
	 * @return Mixed Exception caso erro, true caso sucesso. Usar dentro de bloco try.. catch
	 * @todo melhorar validações.. verificar data > data atual, verificar timestamp, etc.
	 */
	public static function validate($date) {

		if (empty($date)) {
			throw new Exception("Data Inválida");
		}

		if (!is_array($date)) {

			list($d, $m, $y) = explode ('/', $date);

			if ( empty($d) || !checkdate ($m, $d, $y) ) {

				throw new InvalidArgumentException("Data Inválida");

			}

			return true;

		}

		foreach ($date as $item) {
			
			list($d, $m, $y) = explode ('/', $item);

			if ( empty($d) || !checkdate ($m, $d, $y) ) {

				throw new InvalidArgumentException("Data Inválida");

			}

		}

		return true;

	}

	/**
	 * Valida se a data inicial e a data final está entre o periodo informado (padrão 30 dias)
	 * @param string $inicial data inicial para a validação
	 * @param string $final data final para a validação
	 * @param int $periodo (opcional) quantidade de dias permitidos para a validação. Default 30 dias.
	 * @param string $msg para definir a mensagem, p.e. 30 dias, 1 mês, etc. Default 'um mês'.
	 * @return mixed exception caso não valide, true para periodo valido
	 */
	public static function validaPeriodo( $inicial, $final, $periodo = '1 month', $msg = 'um mês') {

		static::validate(array($inicial, $final)); // Apenas para garantir.. vai que..

		$inicial 	= static::converte($inicial);
		$final 		= static::converte($final);

		if (strtotime($inicial) > strtotime($final) ) {
			throw new InvalidArgumentException("Data Inválida");			
		}
		
		if (strtotime("$inicial +$periodo") < strtotime($final) ) {
			throw new InvalidArgumentException("O intervalo entre as datas não pode ser maior que $msg");
        }

        return true;

	}

	public static function converte($data) {

		$delim = strpos($data, '/') !== FALSE ? '/' : '-';
		
		return implode($delim, array_reverse(explode($delim, $data)));

	}

}