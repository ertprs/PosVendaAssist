<?php

namespace Posvenda;

use Posvenda\Model\GenericModel as Model;

class Regras
{

	private static $_regras;

	private function carregaArquivoRegras($fabrica, $regra)
	{
		$diretorio = __DIR__  . '/./regras/' . $regra;

		if (file_exists($diretorio . '/' . $fabrica . '.json')) {
			$carrega = $diretorio . '/' . $fabrica . '.json';
		} else {
			$carrega = $diretorio . '/geral.json';
		}

		$conteudo = file_get_contents($carrega);

		return $conteudo;
	}

	private function carregaRegras($fabrica, $regra)
	{
		self::$_regras = json_decode(self::carregaArquivoRegras($fabrica, $regra), true);
	}

	public static function get($item, $regra, $fabrica = null)
	{
		self::carregaRegras($fabrica, $regra);

		return self::$_regras["{$item}"];
	}

	public static function getUnidades($item, $fabrica) {

		$model = new Model();

		$pdo = $model->getPDO();

		$sql = " 
			SELECT DISTINCT tbl_unidade_negocio.codigo
	        FROM tbl_unidade_negocio
	        JOIN tbl_distribuidor_sla ON tbl_distribuidor_sla.unidade_negocio = tbl_unidade_negocio.codigo
	        WHERE (tbl_unidade_negocio.parametros_adicionais->>'{$item}')::boolean IS TRUE
	        AND tbl_distribuidor_sla.fabrica = {$fabrica}
        ";
        $query   = $pdo->query($sql);
        $dados = $query->fetchAll(\PDO::FETCH_ASSOC);
        
       	$retorno = [];
        foreach($dados as $key => $codigo) {
        	$retorno[] = $codigo["codigo"];
        }

        return $retorno;

	}

}
