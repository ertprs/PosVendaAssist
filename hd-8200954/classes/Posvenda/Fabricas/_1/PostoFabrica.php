<?php
/**
 *
 * @author  Kaique
 * @version 2020.05.13
 *
*/
namespace Posvenda\Fabricas\_1;

use Posvenda\Model\GenericModel;

class PostoFabrica extends GenericModel
{

	private $_conn;
	private $_fabrica;

	public function __construct($fabrica, $conn) {

		$this->_conn 	= $conn;
		$this->_fabrica = $fabrica;

	}

	public function getDadosPosto($posto) {

		$sqlPosto = "SELECT tbl_posto_fabrica.nome_fantasia,
					   tbl_posto_fabrica.contato_fone_comercial,
					   tbl_posto_fabrica.contato_fax,
					   tbl_posto_fabrica.contato_nome,
					   tbl_posto_fabrica.contato_email
				FROM tbl_posto_fabrica
				WHERE tbl_posto_fabrica.fabrica = {$this->_fabrica}
				AND tbl_posto_fabrica.posto = {$posto}";
		$resPosto = pg_query($this->_conn, $sqlPosto);

		$arrayDados = [
			"Nome Fantasia" => pg_fetch_result($resPosto, 0, "nome_fanstasia"),
			"Telefone"      => pg_fetch_result($resPosto, 0, "contato_fone_comercial"),
			"Fax" 			=> pg_fetch_result($resPosto, 0, "contato_fax"),
			"Contato (1)"   => pg_fetch_result($resPosto, 0, "contato_nome"),
			"E-mail (1)"    => pg_fetch_result($resPosto, 0, "contato_email"),
			"Contato (2)"   => "",
			"E-mail (2)"    => ""
		];

		$sqlPostoLinha = "SELECT tbl_linha.linha,
								 CASE 
								 	WHEN tbl_posto_linha.ativo IS TRUE
								 	THEN 'Sim'
								 	ELSE 'Não'
								 END as linha_ativa
						  FROM tbl_linha
						  LEFT JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_linha.linha
						  AND tbl_posto_linha.posto = {$posto}
						  WHERE tbl_linha.fabrica = {$this->_fabrica}
						  AND tbl_linha.ativo IS TRUE";
		$resPostoLinha = pg_query($this->_conn, $sqlPostoLinha);

		$arrayLinhas = [];
		while ($dados = pg_fetch_object($resPostoLinha)) {

			$arrayLinhas[$dados->linha] = $dados->linha_ativa;

		}

		$retorno = [
			"dadosPosto"  => $arrayDados,
			"dadosLinhas" => $arrayLinhas
		];

		return $retorno;

	}

}