<?php

namespace rules\interventions;

use model\ModelHolder;
use util\StringHelper;

class KmIntervention{

	private $statusOs;
	private $minKm;


	public function __construct($minKm,$statusOs=98){
		$this->minKm = $minKm;
		$this->statusOs = $statusOs;
	}

	public function __invoke($event){
		$os = $event['result'];
		$model = $event['source'];
		$km = $event['element']['qtdeKm'];
		if($km < $this->minKm){
			return;
		}
		$osStatusModel = ModelHolder::init('OsStatus');
		$message = 'OS com mais de '.$this->minKm.'Km';
		$osStatus = array(
			'os' => $os,
			'statusOs' => $this->statusOs,
			'observacao' => $message
		);
		$osStatusModel->insert($osStatus);
	}

}