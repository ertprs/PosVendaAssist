<?php

namespace model;

use model\ModelHolder;
use util\SQLHelper;
use util\ArrayHelper;

include_once __DIR__.'/../../class/aws/s3_config.php';
include_once S3CLASS;

class OS extends Model{

	public function __construct($connection=null){
		parent::__construct($connection);
	}

	public function insert($element){
		$osProduto = $element['osProduto'];
		unset($element['osProduto']);
		unset($element['anexoNf']);
		$osId = parent::insert($element);
		$osProdutoModel = ModelHolder::init('OsProduto');
		foreach ($osProduto as $produto) {
			$produto['os'] = $osId;
			$osProdutoId = $osProdutoModel->insert($produto);
		}
		return $osId;
	}

	public function delete($elementId){
		var_dump(func_get_args());
		return null;
	}

	public function update($element,$elementId){
		var_dump(func_get_args());
		return null;
	}

}

