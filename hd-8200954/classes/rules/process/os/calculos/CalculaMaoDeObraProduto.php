<?php 

namespace rules\process\os\calculos;
use rules\process\os\calculos\ExcecaoMaoDeObra;
use model\ModelHolder;
class CalculaMaoDeObraProduto extends ExcecaoMaoDeObra{


    public function __invoke($os){
	return $this->getValorMobra($os);
    }

    public function getValorMobra($os){
	$sql = "SELECT tbl_produto.mao_de_obra
		FROM tbl_os 
		JOIN tbl_produto ON tbl_produto.produto= tbl_os.produto
		WHERE tbl_os.os = :os";

	$model = ModelHolder::init("OS");
	$result = $model->executeSql($sql, array(":os" => $os));

	return (count($result) > 0) ? $result[0]["mao_de_obra"] : NULL;

    }

}
