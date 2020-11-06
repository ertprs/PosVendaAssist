<?php

/**
 * Helper para funcionalidades do admin
 * @author Brayan
 */
class LoginHelper extends Parametros {

	private $admin;

	public function setAdmin ($admin) { //@todo tratar se admin existe
		$this->admin = $admin;
	}

	/**
	 * Recupera informa��es do admin
	 * @param array $info passar o nome dos campos que deseja recuperar, por default traz o campo nome_completo
	 * @param int $admin para buscar informa��es de um admin especifico
	 * @example $adminInfo = $helper->login->getInfo(); $adminInfo = $helper->login->getInfo(array('login', 'senha')); 
	 * @example para buscar um admin especifico: $adminInfo = $helper->login->getInfo(array('nome_completo'), 123);
	 * @return Mixed caso encontre retorna array contendo os campos => valores dos dados do admin, caso nao encontre retorna exception
	 */
	public function getInfo( $info = array('nome_completo'), $admin = '') {

		if (!empty($admin)) {
			$this->setAdmin($admin);
		}

		$campos = implode(', ', $info);

		$sql = "SELECT $campos
				FROM tbl_admin
				WHERE admin = {$this->admin}";

		$res = pg_query($this->getCon(), $sql);

		if (pg_errormessage($this->getCon()) || pg_num_rows($res) == 0)
			throw new Exception("Erro ao buscar informa��es do usu�rio " . pg_errormessage($this->getCon()));			

		return pg_fetch_array($res, 0, PGSQL_ASSOC);

	}

	/**
	 * Verifica se o admin tem as permiss�es passadas por par�metro
	 * @param string $param podendo passar apenas uma ou um array de permiss�es para verificar.
	 * @param int $admin (opcional), para quando for verificar varios registros, poder verificar direto num if, ao inv�s de usar o m�todo.
	 * @example Para verificar se o admin possui permiss�o de cadastro, fa�a: if ( $helper->login->hasPermission('cadastro') )
 	 * @example Para verificar se o admin 123 possui permiss�o de cadastro, fa�a: if ( $helper->login->hasPermission('cadastro', 123) )
	 * @return bool
	 */
	public function hasPermission( $param, $admin = '') {

		try {

			if (!empty($admin)) {
				$this->setAdmin($admin);
			}

			if ( empty($param) || empty($this->admin) )
				throw new Exception("Par�metros inv�lidos");

			$sql = "SELECT admin
					FROM   tbl_admin
					WHERE  admin   = {$this->admin}
					AND    fabrica = {$this->fabrica}
					AND    (privilegios LIKE '%$param%' OR privilegios LIKE '%*%')";

			$res = pg_query($this->getCon(), $sql);

			return (bool) pg_num_rows($res);

		} catch(InvalidArgumentException $e) {

			return $e;

		}

	}

}