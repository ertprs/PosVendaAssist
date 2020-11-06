<?php 
namespace rules\process\os\calculos;
use rules\process\os\calculos\ExcecaoMaoDeObra;
 
use model\ModelHolder;
use util\ArrayHelper;
class CalculaExcecaoMaoDeObraPostoProduto extends ExcecaoMaoDeObra{
    
    public function __invoke($os){
	return $this->getValorMobra($os);
    }

    public function getValorMobra($os){
	$sql = "SELECT tbl_excecao_mobra.mao_de_obra
		FROM tbl_os 
		JOIN tbl_excecao_mobra ON tbl_excecao_mobra.posto = tbl_os.posto AND
				tbl_excecao_mobra.produto = tbl_os.produto AND
				tbl_excecao_mobra.fabrica = tbl_os.fabrica
		WHERE	tbl_os.os = :os AND
			linha		       is null	AND
			adicional_mao_de_obra  is null	AND
			percentual_mao_de_obra is null	AND
			familia		       is null	AND
			qtde_dias	       is null	AND
			tbl_excecao_mobra.revenda		       is null	AND
			troca_produto	       is null	AND
			id_revenda	       is null	AND
			solucao		       is null	AND
			peca_lancada	       is null	AND
			tbl_excecao_mobra.tipo_atendimento       is null	AND
			tx_administrativa      is null	AND
			tipo_posto	       is null	";
	$model = ModelHolder::init("ExcecaoMobra");
	$result = $model->executeSql($sql, array(":os" => $os));

	return (count($result) > 0) ? $result[0]["mao_de_obra"] : NULL;
	

    }

}
