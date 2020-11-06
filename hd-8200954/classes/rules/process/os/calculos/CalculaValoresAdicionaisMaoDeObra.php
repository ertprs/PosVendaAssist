<?php 
namespace rules\process\os\calculos;
use model\ModelHolder;
class CalculaValoresAdicionaisMaoDeObra{

    public function __invoke($os){
	$valorMobra = $this->getValoresAdicionais($os);
	return empty($valorMobra) ? 0 : $valorMobra;
	
    }

    public function getValoresAdicionais($os){
	$sql = "SELECT valores_adicionais 
		FROM tbl_os_campo_extra 
		WHERE tbl_os_campo_extra.os = :os";

	$model = ModelHolder::init("OsCampoExtra");
	$result = $model->executeSql($sql, array(":os" => $os));

	if(count($result) != 0){
	    $arrValoresAdicionais = json_decode($result[0]["valores_adicionais"],true);
	}else{
	    
	    return 0;
	}
	return $this->somaValoresAdicionaisFromArray($arrValoresAdicionais);

    }

    private function somaValoresAdicionaisFromArray($arrValoresAdicionais){
	$soma = 0;


	foreach($arrValoresAdicionais as $key => $valor){
	    $arrayKeys = array_keys($valor);
	
	    foreach($arrayKeys as $nomeValorAdicional){

		$soma += $valor[$nomeValorAdicional];
            }

	}

	return $soma;
    }
}

