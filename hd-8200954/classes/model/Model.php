<?php

namespace model;

use rules\RuleProcessor;
use util\SQLHelper;
use util\NameHelper;
use model\ModelEvent;
use exceptions\MultipleException;
use rules\exceptions\CheckException;
use \PDO;
use \Exception;

class Model{

	private static $init = null;
	protected static $fabrica;
	protected static $posto;
	protected static $rulesProcessors=array();
	protected static $rulesArray=array();
	public static $defaultPdo;
	protected $pdo;
	protected $sqlHelper;

	private static $transactionStack = 0;

	private $actionBeforeInsert = array();
	private $actionAfterInsert = array();

	private $actionBeforeDelete = array();
	private $actionAfterDelete = array();

	private $actionBeforeUpdate = array();
	private $actionAfterUpdate = array();


	private $methods = array();

	private function initStatic(){
		if(!empty(Model::$init))
			return;
		global $login_fabrica;
		global $login_posto;
		Model::$init = true;
		Model::$fabrica = $login_fabrica;
		Model::$posto = $login_posto;

		require '/etc/telecontrol.cfg';

		Model::$defaultPdo = new PDO("pgsql:dbname=$dbnome;host=$dbhost;port=$dbport",$dbusuario,$dbsenha);
		Model::$defaultPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	public static function getDefaultPDO(){
		Model::initStatic();
		return Model::$defaultPdo;
	}

	private function initRule(){
		$class = $this->getModelName();
		if(!empty(Model::$rulesProcessors[$class]))
			return;
		$modelFolder = NameHelper::toFolderName($class);
		$ruleFile = __DIR__.'/../rules/'.$modelFolder.'/'.Model::$fabrica.'.php';
		if(!file_exists($ruleFile))
			return;
		$ruleArray = require_once $ruleFile;
		if(!is_array($ruleArray))
			$ruleArray = array();
		Model::$rulesArray[$class] = $ruleArray;
		Model::$rulesProcessors[$class] = new RuleProcessor($ruleArray);
	}



	protected function __construct($pdo = null){
		Model::initStatic();
		$this->initRule();
		$this->pdo = empty($pdo)?Model::$defaultPdo:$pdo;
		$this->sqlHelper = new SQLHelper($this->pdo);
	}
	
	public function check($element){
		$class = $this->getModelName();
		if(!array_key_exists($class,Model::$rulesProcessors))
			return;
		$ruleProcessor = Model::$rulesProcessors[$class];
		$ruleProcessor->validate($element);
	}

	public function makeMontaFormArray(){
		$desiredRules = array('maxlength','required');
		$class = $this->getModelName();
		$rulesArray = NameHelper::prepareArray(Model::$rulesArray[$class]);
		var_dump($rulesArray);
		$formArray = array();
		foreach ($rulesArray as $field => $rules) {
			$group = isset($rules['group'])?$rules['group']:$class;
			$group = NameHelper::toColumnName($group);
			$field = NameHelper::toColumnName($field);
			$formArray[$group][$field] = array();
			foreach ($desiredRules as $ruleName) {
				if(!isset($rules[$ruleName]))
					continue;
				$ruleName = NameHelper::toColumnName($ruleName);
				$formArray[$group][$field][$ruleName] = $rules[$ruleName];
			}
		}
		return $formArray;
	}

	public function fireBeforeInsert(&$element){
		$event = array(
			'source' => $this,
			'method' => 'INSERT',
			'element'=> &$element
		);
		$exception = new MultipleException();
		foreach($this->actionBeforeInsert as $action){
			try{
				if(is_callable($action)){
					$action($event);
					continue;
				}	
			}
			catch(CheckException $ex){
				$exception->addException($ex);
			}
		}
		$exception->throwIfNotEmpty();
	}

	public function fireAfterInsert($element,&$result){
		$event = array(
			'source' => $this,
			'method' => 'INSERT',
			'element'=> $element,
			'result' => &$result
		);
		foreach($this->actionAfterInsert as $action){
			if(is_callable($action)){
				$action($event);
				continue;
			}
		}
	}

	public function fireBeforeUpdate(&$element,&$elementId){
		$event = array(
			'source' => $this,
			'method' => 'UPDATE',
			'element'=> &$element,
			'elementId'=> &$elementId
		);
		$exception = new MultipleException();
		foreach($this->actionBeforeUpdate as $action){
			try{
				if(is_callable($action)){
					$action($event);
					continue;
				}
			}
			catch(CheckException $ex){
				$exception->addException($ex);
			}
		}
		$exception->throwIfNotEmpty();
	}

	public function fireAfterUpdate($element,$elementId,&$result){
		$event = array(
			'source' => $this,
			'method' => 'UPDATE',
			'element'=> $element,
			'elementId'=> $elementId,
			'result'=> &$result,
		);
		foreach($this->actionAfterUpdate as $action){
			if(is_callable($action)){
				$action($event);
				continue;
			}
		}
	}

	public function fireBeforeDelete(&$elementId){
		$event = array(
			'source' => $this,
			'method' => 'DELETE',
			'elementId'=> &$elementId,
		);
		foreach($this->actionBeforeDelete as $action){
			if(is_callable($action)){
				$action($event);
				continue;
			}
		}
	}

	public function fireAfterDelete($elementId,&$result){
		$event = array(
			'source' => $this,
			'method' => 'DELETE',
			'elementId'=> $elementId,
			'result' => &$result,
		);
		foreach ($actionAfterDelete as $action) {
			if(is_callable($action)){
				$action($event);
				continue;
			}
		}
	}

	public function fireMethod($methodName,$params){
		if(isset($this->methods[$methodName])){
			$args = array($this);
			$args = array_merge($args,$params);
			return call_user_func_array($this->methods[$methodName],$args);	
		}
		$reflectionClass = new \ReflectionClass($this);
		$reflectionMethod = $reflectionClass->getMethod($methodName);
		return $reflectionMethod->invokeArgs($this,$params);
	}

	public function getModelName(){
		return end(explode('\\',get_class($this)));
	}

	/**
	* $idColumn foi necessario por causa
	* da maldita tabela tbl_os_retorno, que nao segue
	* o padrao do sistema
	*/
	public function insert($element,$idColumn=null){
		if(empty($idColumn))
			$idColumn = NameHelper::toColumnName($this->getModelName());
		$stmt = $this->sqlHelper->makeInsertStatement($this,$element,$idColumn);
		$stmt->execute();
		$fetch = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $fetch[0][$idColumn];
	}

	public function delete($elementId){
		if(!is_array($elementId)){
			$column = NameHelper::toColumnName($this->getModelName());
			$elementId = array( $column=> $elementId);
		}
		$stmt = $this->sqlHelper->makeDeleteStatement($this,$elementId);
		$stmt->execute();
		return $stmt->rowCount();
	}

	public function update($element,$elementId){
		if(!is_array($elementId)){
			$column = NameHelper::toColumnName($this->getModelName());
			$elementId = array( $column=> $elementId);
		}
		$stmt = $this->sqlHelper->makeUpdateStatement($this,$element,$elementId);
		$stmt->execute();
		return $stmt->rowCount();
	}

	public function select($elementId){
		if(!is_array($elementId)){
			$column = NameHelper::toColumnName($this->getModelName());
			$elementId = array( $column=> $elementId);
		}
		$stmt = $this->sqlHelper->makeSelectStatement($this,$elementId);
		$stmt->execute();
		$fetch = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if(empty($fetch))
			return null;
		return NameHelper::prepareArray($fetch[0]);
	}

	public function find($filter,$fields=array('*'),$limit=null){
		$stmt = $this->sqlHelper->makeFindStatement($this,$filter,$fields,$limit);
		$stmt->execute();
		return NameHelper::prepareArray($stmt->fetchAll(PDO::FETCH_ASSOC));
	}

	public function field($field,$elementId){
		if(!is_array($elementId)){
			$column = NameHelper::toColumnName($this->getModelName());
			$elementId = array( $column=> $elementId);
		}
		$stmt = $this->sqlHelper->makeFieldStatement($this,$field,$elementId);
		$stmt->execute();
		$fetch = NameHelper::prepareArray($stmt->fetchAll(PDO::FETCH_ASSOC));
		return $fetch[0][$field];
	}

	public function begin(){
		if(Model::$transactionStack == 0)
			$this->pdo->beginTransaction();
		Model::$transactionStack++;
	}

	public function commit(){
		Model::$transactionStack--;
		if(Model::$transactionStack == 0)
			$this->pdo->commit();
	}

	public function rollback(){
		Model::$transactionStack--;
		if(Model::$transactionStack == 0)
			$this->pdo->rollBack();
	}

	public function executeSql($sql,$params=array()){
		$stmt = $this->pdo->prepare($sql);
		foreach($params as $key => $value){
			$stmt->bindValue($key,$value);
		}
		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function getFactory(){
		return Model::$fabrica;
	}

	public function getPost(){
		return Model::$posto;
	}

}
