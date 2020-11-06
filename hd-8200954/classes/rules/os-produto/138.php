<?php

$this->actionBeforeInsert[] = function($event){
	$produto = $event['element']['produto'];
	$model = $event['source'];
	$sql = 'SELECT ativo IS TRUE AS ativo, descricao FROM tbl_produto WHERE produto = :produto LIMIT 1;';
	$result = $model->executeSql($sql,array(':produto'=>$produto));
	if(!$result[0]['ativo']){
		throw new Exception('Produto '.$result[0]['descricao'].' não Ativo');
	}
};

$this->actionBeforeInsert[] = function($event){
	if(!isset($event['element']['osItem']))
		return;
	foreach ($event['element']['osItem'] as $key => $osItem) {
		if(empty($osItem['peca']))
			unset($event['element']['osItem'][$key]);
	}
};

$this->actionBeforeInsert[] = function($event){
	$produto = $event['element']['produto'];
	$defeitoConstatado = $event['element']['defeitoConstatado'];
	$model = $event['source'];
	$fabrica = $model->getFactory();
	$sql = 'SELECT COUNT(tbl_defeito_constatado.defeito_constatado) > 0 AS ok
			FROM tbl_produto
			INNER JOIN tbl_diagnostico
				ON (tbl_diagnostico.familia = tbl_produto.familia)
			INNER JOIN tbl_defeito_constatado
				ON 	(tbl_diagnostico.defeito_constatado = tbl_defeito_constatado.defeito_constatado)
			WHERE tbl_produto.produto = :produto AND tbl_defeito_constatado.fabrica = :fabrica AND tbl_defeito_constatado.defeito_constatado = :defeitoConstatado ;';
	$result = $model->executeSql($sql,array(
		':produto'=>$produto,
		':fabrica'=>$fabrica,
		':defeitoConstatado'=>$defeitoConstatado
		));
	if(!$result[0]['ok']){
		throw new Exception('Defeito constatado inválido para o produto');
	}
};


return array(
	'os' => array(
		'notEmpty' => true,
	),
	'produto' => array(
		'notEmpty' => true,
	),
	'serie' => array(
		'required' => 'Produto sem Número de Série',
		'notEmpty' => true
	),
);