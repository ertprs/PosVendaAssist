<?php

class CRUDHelper extends Parametros {
	
	public function getFamilias( $params = array(), $fields = array('familia','descricao') ) {

		$cond = '';

		if (!empty($params)) {

			foreach ($params as $key => $value) {
				$param[] = "$key = $value";
			}

			$cond = 'AND ' . implode (' AND ', $param);

		}

		$sql = "SELECT " . implode(', ', $fields) . "
				FROM tbl_familia 
				WHERE fabrica = {$this->fabrica}
				$cond
				ORDER BY descricao";

		$res = pg_query($this->con, $sql);

		if (pg_num_rows($res) == 0) {

			throw new Exception("Nenhuma familia cadastrada para essa fabrica");			

		}

		return pg_fetch_all($res);

	}

	public function getLinhas( $params = array(), $fields = array('linha','nome') ) {

		$cond = '';

		if (!empty($params)) {

			foreach ($params as $key => $value) {
				$param[] = "$key = $value";
			}

			$cond = 'AND ' . implode (' AND ', $param);

		}

		$sql = "SELECT " . implode(', ', $fields) . "
				FROM tbl_linha 
				WHERE fabrica = {$this->fabrica}
				$cond
				ORDER BY nome";

		$res = pg_query($this->con, $sql);

		if (pg_num_rows($res) == 0) {

			throw new Exception("Nenhuma linha cadastrada para essa fabrica");			

		}

		return pg_fetch_all($res);

	}

}