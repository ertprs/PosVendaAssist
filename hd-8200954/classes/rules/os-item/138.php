<?php

use model\ModelHolder;
use util\ArrayHelper;
use action\ActionHolder;
use action\NotEmptyFilter;

$this->actionBeforeInsert[] = function($event){
	$model = $event['source'];
	$event['element']['fabrica_i'] = 138;
};

$this->actionBeforeInsert[] = function($event){
	$geraPedido = ArrayHelper::getIfSet($event,array('element','geraPedido'),'false');
	$servicoModel = ModelHolder::init('ServicoRealizado');
	$servico = $servicoModel->select(array(
		'geraPedido'=>$geraPedido,
		'trocaDePeca'=>$geraPedido,
		'ativo'=>true,
		'fabrica'=> 138
	));
	unset($event['element']['geraPedido']);
	$event['element']['servicoRealizado'] = $servico['servicoRealizado'];
};

$this->actionBeforeInsert[] = function($event){
	$model = $event['source'];
	$peca = $event['element']['peca'];
	$osProduto = $event['element']['osProduto'];
	$sql = 'SELECT COUNT(*) > 0 AS ok
			FROM tbl_os_produto
			INNER JOIN tbl_lista_basica
				ON (tbl_lista_basica.produto = tbl_os_produto.produto)
			WHERE tbl_os_produto.os_produto = :osProduto AND tbl_lista_basica.peca = :peca';
	$result = $model->executeSql($sql,array(
		':osProduto' => $osProduto,
		':peca' => $peca
		));
	if(!$result[0]['ok']){
		throw new Exception('Peça não pertence ao produto');
	}
};

$this->actionBeforeInsert[] = function($event){
	$model = $event['source'];
	$peca = $event['element']['peca'];
	$sql = 'SELECT
				tbl_peca.ativo IS TRUE AS ok,
				tbl_peca.referencia,
				tbl_peca.descricao
			FROM tbl_peca WHERE peca = :peca LIMIT 1;';
	$result = $model->executeSql($sql,array(':peca'=>$peca));
	if(!$result[0]['ok']){
		throw new Exception('Peça '.$result[0]['referencia'].'('.$result[0]['descricao'].') não está ativa');
	}
};

$this->actionBeforeInsert[] = function($event){
	$model = $event['source'];
	$peca = $event['element']['peca'];
	$sql = 'SELECT produto_acabado IS NOT TRUE AS ok FROM tbl_peca WHERE peca = :peca LIMIT 1;';
	$result = $model->executeSql($sql,array(':peca'=>$peca));
	if(!$result[0]['ok']){
		throw new Exception('A peça é um produto acabado');
	}
};

$this->actionBeforeInsert[] = function($event){
	$model = $event['source'];
	$peca = $event['element']['peca'];
	$sql = 'SELECT bloqueada_garantia IS NOT TRUE AS ok FROM tbl_peca WHERE peca = :peca LIMIT 1;';
	$result = $model->executeSql($sql,array(':peca'=>$peca));
	if(!$result[0]['ok']){
		throw new Exception('Peça bloqueada para garantia');
	}
};


$this->actionBeforeInsert[] = new ActionHolder(
	function($event){
		$model = $event['source'];
		$peca = $event['element']['peca'];
		$osProduto = $event['element']['osProduto'];
		$qtde = ArrayHelper::getIfSet($event,array('element','qtde'),0);
		if(empty($qtde))
			$qtde = 0;
		$sql = 'SELECT tbl_lista_basica.qtde >= :qtde AS ok
				FROM tbl_os_produto
				INNER JOIN tbl_lista_basica
					ON (tbl_lista_basica.produto = tbl_os_produto.produto)
				WHERE tbl_os_produto.os_produto = :osProduto AND tbl_lista_basica.peca = :peca';
		$result = $model->executeSql($sql,array(
			':osProduto' => $osProduto,
			':peca' => $peca,
			':qtde' => $qtde
			));
		if(!$result[0]['ok']){
			$model = ModelHolder::init('Peca');
			$peca = $model->select($peca);
			throw new Exception('Quantidade da peça '.$peca['referencia'].' ('.$peca['descricao'].') excede o permitido');
		}
	},
	new NotEmptyFilter('qtde')
);

return array(
	'osProduto' => array(
		'notEmpty' => true,
	),
	'peca' => array(
		'notEmpty' => true,
	),
	'qtde' => array(
		'required' => "Digite uma quantidade para o produto",
		'regex' => array(
			'@^[1-9][0-9]*$@' => 'A quantidade de peças deve ser um valor inteiro positivo'
		)
	),
);