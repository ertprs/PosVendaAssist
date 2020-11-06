<?php

namespace html\util;

use model\Model;

class SQLSelectOptions {

	private $pdo;
	private $sql;
	private $params;
	private $result;

	public function __construct($sql,$params=array(),$pdo=null){
		$this->pdo = empty($pdo)?Model::getDefaultPDO():$pdo;
		$this->params = $params;
		$this->sql = $sql;
		$this->result = null;
	}

	public function __invoke(){
		if(!empty($this->result))
			return $this->result;
		$stmt = $this->pdo->prepare($this->sql,$this->params);
		$stmt->execute();
		if($stmt->columnCount() == 2){
			$this->result = $stmt->fetchAll(\PDO::FETCH_COLUMN | \PDO::FETCH_UNIQUE);	
		}
		else{
			$this->result = $stmt->fetchAll(\PDO::FETCH_ASSOC);	
		}
		return $this->result;
	}	

}