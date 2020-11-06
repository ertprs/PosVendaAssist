<?php

namespace model;

use model\ModelHolder;
use model\Model;
use util\SQLHelper;
use util\ArrayHelper;

class Pedido extends Model{

	public function __construct($connection=null){
		parent::__construct($connection);
	}


	public function insert($element){
		$itens = $element['itens'];
		unset($element['itens']);
		$pedidoId = parent::insert($element);
		$pedidoItemModel = ModelHolder::init('PedidoItem');
		foreach($itens as $item){
			$item['pedido'] = $pedidoId;
			$pedidoItemModel->insert($item);
		}
		return $pedidoId;
	}

}
