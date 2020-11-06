<?php

class PostoHelper extends Parametros {

	/**
	 * Recupera informações do admin
	 * @param array $info passar o nome dos campos que deseja recuperar
	 * @param array $params com os parametros para busca. p.e. array('codigo_posto' => '01122') ou array('cnpj' => '7777777777-14')
	 * @return mixed exception caso nao encontre, array com os campos passados no array $info
	 * @example $posto = $helper->posto->getInfo(array('posto', 'nome'), array ('codigo_posto' => $posto_codigo) );
	 */
	public function getInfo( $info = array('posto'), $params = array()) {

		if (empty($params)) {
			throw new Exception("Informe um parâmetro para pesquisa do posto");			
		}

		$cond = '';

		foreach($params as $param => $value) {

			$cond .= " AND $param = '$value' ";

		}

		$campos = implode (', ', $info);

		$sql = "SELECT $campos
				FROM tbl_posto_fabrica
				JOIN tbl_posto USING(posto)
				WHERE fabrica = {$this->getFabrica()}
				$cond";

		$res = pg_query($this->getCon(), $sql);

		if (pg_errormessage($this->getCon()) || pg_num_rows($res) == 0)
			throw new Exception("Posto não encontrado " . pg_errormessage($this->getCon()));			

		return pg_fetch_array($res, 0, PGSQL_ASSOC);

	}

}