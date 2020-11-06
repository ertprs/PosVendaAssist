<?php

$this->actionBeforeInsert[] = function($event){
	$event['element']['fabrica'] = 134;
	$event['element']['message'] = "Oi End";
};

$this->actionBeforeInsert[] = function($event){
	
};

$this->actionAfterInsert[] = function($event){
	var_dump($event);
	$event['result'] = 'teste';
};


/*

'fieldName' => array(
	'required' => boolean,
	'notEmpty' => boolean,
	'maxlength' => int,
	'type' => [date|text|numeric|dateTime],
	'regex' => array(regex,...),
	'group' => string, //is not a rule
)
*/

return
array(
	'data_abertura' => array(
		'required' => true,
		'notEmpty' => true,
		//'type' => 'date',
		//'group' => 'os'
	),
	'sua_os'=>array(
		//'maxlength' => 20,
		//'group' => 'os'
	),
	'consumidor_nome'=>array(
		'group' => 'consumidor'
	)
);
