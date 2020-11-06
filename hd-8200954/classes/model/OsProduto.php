<?php

namespace model;

class OsProduto extends  Model{

	public function __construct($connection=null){
		parent::__construct($connection);
	}

	public function insert($element){
		$osItem = isset($element['osItem'])?$element['osItem']:array();
		unset($element['osItem']);
		$osProdutoId = parent::insert($element);
		$osItemModel = ModelHolder::init('OsItem');
		foreach ($osItem as $item) {
			$item['osProduto'] = $osProdutoId;
			$osItemModel->insert($item);
		}
	}

}