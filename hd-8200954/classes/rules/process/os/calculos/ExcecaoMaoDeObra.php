<?php 
namespace rules\process\os\calculos;
abstract class ExcecaoMaoDeObra{
    private $precedenceOrder = 0;
    
    public function __construct($precedenceOrder){
	$this->precedenceOrder = $precedenceOrder;
    }
    
    public function getPrecedenceOrder(){
	return $this->precedenceOrder;
    }
}
