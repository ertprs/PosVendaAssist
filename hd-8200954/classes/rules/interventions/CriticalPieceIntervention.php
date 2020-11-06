<?php

namespace rules\interventions;

use model\ModelHolder;

class CriticalPieceIntervention{

	private $sql =
			'SELECT COUNT(*) > 0 AS critical
			FROM tbl_os_produto
			INNER JOIN tbl_os_item
				ON (tbl_os_produto.os_produto = tbl_os_item.os_produto)
			INNER JOIN tbl_peca
				ON (tbl_os_item.peca = tbl_peca.peca)
			WHERE tbl_os_produto.os = :os AND tbl_peca.peca_critica IS TRUE';

	private $statusOs;
	private $observacao;


	public function __construct($statusOs = 62,$observacao='Intervenção de peça Crítica'){
		$this->statusOs = $statusOs;
		$this->observacao = $observacao;
	}
	
	public function __invoke($event){
		$model = $event['source'];
		$os = $event['result'];
		$result = $model->executeSql($this->sql,array(':os'=>$os));
		if(!$result[0]['critical'])
			return;
		$osStatusModel = ModelHolder::init('OsStatus');
		$osStatus = array(
			'os' => $os,
			'statusOs' => $this->statusOs,
			'observacao' => $this->observacao
		);
		$osStatusModel->insert($osStatus);
	}

}