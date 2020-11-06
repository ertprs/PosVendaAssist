<?php

namespace rules\interventions;

use util\NameHelper;
use model\ModelHolder;
use \DateTime;
use \DateInterval;

class RepeatIntervention{

	private $dayPeriod;
	private $statusOs;
	private $compareFields;

	public function __construct($dayPeriod=90,$compareFields=array('revendaCnpj','produto','notaFiscal'),$statusOs=70){
		$this->dayPeriod = (int)$dayPeriod;
		$this->statusOs = $statusOs;
		$this->compareFields = $compareFields;
	}

	private function buildSql($os){
		$params = array();
		$params[':fabrica'] = $os['fabrica'];
		$where = array('fabrica = :fabrica');
		$where[] = 'data_abertura >= :period';
		$dateInterval = new DateInterval('P'.$this->dayPeriod.'D');
		$date = new DateTime();
		$date->setTime(0,0,0);
		$date->sub($dateInterval);
		$params[':period'] = $date->format(DateTime::ISO8601);
		$group = array();
		foreach($this->compareFields as $field){
			$columnName =  NameHelper::toColumnName($field);
			$fieldName = NameHelper::prepareName($field);
			$where[] = $columnName.' = :'.$fieldName;
			$group[] = $columnName;
			$params[':'.$fieldName] = $os[$fieldName];
		}
		$where = ' WHERE '.implode(' AND ',$where);
		$group = ' GROUP BY '.implode(',',$group);
		$sql  = 'SELECT COUNT(*) = 1 AS ok FROM tbl_os '.$where.$group;
		return array($sql,$params);
	}

	public function __invoke($event){
		$model = $event['source'];
		$os = $event['element'];
		list($sql,$params) = $this->buildSql($os);
		$result = $model->executeSql($sql,$params);
		if($result[0]['ok']){
			return;
		}
		$os = $event['result'];
		$osStatusModel = ModelHolder::init('OsStatus');
		$osStatus = array(
			'os' => $os,
			'statusOs' => $this->statusOs,
			'observacao' => 'OS reincidente'
		);
		$osStatusModel->insert($osStatus);
	}
	
}