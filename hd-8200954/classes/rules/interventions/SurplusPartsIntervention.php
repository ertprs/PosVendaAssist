<?php

namespace rules\interventions;

use model\ModelHolder;

class SurplusPartsIntervention{

	private $statusOs;
	private $limit;

	private $sql =
		'SELECT COALESCE(SUM(tbl_os_item.qtde),0) > :limit AS surplus
		FROM tbl_os_produto
		INNER JOIN tbl_os_item
			ON (tbl_os_produto.os_produto = tbl_os_item.os_produto)
		WHERE tbl_os_produto.os = :os;';


	public function __construct($limit = 3,$statusOs = 118){
		$this->statusOs = $statusOs;
		$this->limit = $limit;
	}


	public function __invoke($event){
		$os = $event['result'];
		$model = $event['source'];
		$result = $model->executeSql($this->sql,array(
			':limit' => $this->limit,
			':os' => $os
		));
		if(!$result[0]['surplus'])
			return;
		$osStatusModel = ModelHolder::init('OsStatus');
		$osStatus = array(
			'os' => $os,
			'statusOs' => $this->statusOs,
			//'observacao' => 'OS com mais de '.$this->limit.' peça(s)'
			'observacao' => 'OS com peças excedentes'
		);
		$osStatusModel->insert($osStatus);
	}

}