<?php
namespace rules\process\os\calculos;
use model\ModelHolder;
use util\ArrayHelper;
use util\StringHelper;

class CalculaKm{
    private $calculaMesmaCidade;

    public function __construct($calculaMesmaCidade = true){
	$this->calculaMesmaCidade = $calculaMesmaCidade;
    }

    public function __invoke($os){

	$os = $this->getOS($os);

	if($this->calculaMesmaCidade){
	    return $this->calculaKm($os);
	}
	return $this->mesmaCidadePostoConsumidor($os) ? 0 : $this->calculaKm($os);

    }

    public function getOS($os){
	$model = ModelHolder::init("OS");
	return $model->select($os);

    }

    public function calculaKm($os){

	$valorKmFabrica = $this->getValorKmFabrica();
	$valorKmPosto = $this->getValorKmPosto($os["posto"]);

	$valorKm = $valorKmPosto > 0 ? $valorKmPosto : $valorKmFabrica;

	$valorKm *= (empty($os["qtdeKm"]) ? 0 : $os["qtdeKm"]);

	return $valorKm;
    }

    public function mesmaCidadePostoConsumidor($os){
	$sql = "SELECT tbl_os.consumidor_cidade,
			tbl_posto.cidade
		FROM tbl_os
		JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto 
		WHERE tbl_os.os = :os";

	$model = ModelHolder::init("OS");
	$result = $model->executeSql($sql, array(":os" => $os));
	return StringHelper::like($result[0]["consumidor_cidade"], $result[0]["cidade"]) ? true : false;
    }
    public function getValorKmFabrica(){
	$model = ModelHolder::init("Fabrica");
	return $model->field("valorKm", $model->getFactory() );
	
    }

    public function getValorKmPosto($posto){
	$model = ModelHolder::init("PostoFabrica");
	return $model->field("valorKm",  array("fabrica"=>$model->getFactory(),"posto"=>$posto ) );
    }
}
