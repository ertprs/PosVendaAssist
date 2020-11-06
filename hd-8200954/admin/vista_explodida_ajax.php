<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "cadastros";
include 'autentica_admin.php';
include 'funcoes.php';


error_reporting(E_ALL);
ini_set('display_errors',1);

use model\ModelHolder;

function prepareName($name) {
	$ns = explode('_',$name);

	$trueName = '';
	foreach($ns as $n){
		$trueName .= ucfirst($n);
	}
	return lcfirst($trueName);
}

$args = array();

foreach($_REQUEST as $argName => $arg){
	$args[prepareName($argName)] = $arg;
}

if(empty($args) || empty($args['action']))
	die('action empty');

$args['action'] = prepareName($args['action']);

$actions = array(

	'addVista' => array(
		'args' => array('produto'),
		'function' => function($produto){
			if(!(array_key_exists('vista',$_FILES) && $_FILES['vista'])){
				return false;
			}
			$vista = $_FILES['vista'];
			$ext = end(explode('.',$vista['name']));
			$model = ModelHolder::init('Produto');
			$vistaId = $model->addExplodeView($produto,$vista['tmp_name']);
			if(!$vistaId)
				return false;
			$response = array('vista'=>$vistaId);
			$response['src'] = $model->getExplodeView($produto,$vistaId);
			return $response;
		}
	),

	'removeVista' => array(
		'args' => array('produto','vista'),
		'function' => function($produto,$vista){
			$model = ModelHolder::init('Produto');
			return $model->removeExplodeView($produto,$vista);
		}
	),

	'getVistas' => array(
		'args' => array('produto'),
		'function' => function($produto){
			$model = ModelHolder::init('Produto');
			return $model->getExplodeViewImages($produto);
		}
	),

	'setMap' => array(
		'args' => array('listaBasica','x1','x2','y1','y2','vista'),
		'function' => function($listaBasica,$x1,$x2,$y1,$y2,$vista){
			$model = ModelHolder::init('ListaBasica');
			$lista = array(
				'coordenadas' => array(
					'vista' => $vista,
					'x1' => (int)$x1,
					'x2' => (int)$x2,
					'y1' => (int)$y1,
					'y2' => (int)$y2
				)
			);
			return $model->update($lista,$listaBasica);
		}
	)

);


if(!isset($actions[$args['action']])){
	die('action not found');
}

$preparedArgs = array();
foreach($actions[$args['action']]['args'] as $argName){
	if(!isset($args[$argName]))
		die('parameter error');
	$preparedArgs[] = $args[$argName];
}
header('Content-Type: application/json');
$result = call_user_func_array($actions[$args['action']]['function'],$preparedArgs);
echo json_encode($result);