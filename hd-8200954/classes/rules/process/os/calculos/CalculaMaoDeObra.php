<?php 
namespace rules\process\os\calculos;
use model\ModelHolder;
use util\ArrayHelper;
use util\StringHelper;

class CalculaMaoDeObra{
    private $arrCalculosMaoDeObra;
    public function __construct(){
	$arrCalculosMaoDeObra = func_get_args();
	usort($arrCalculosMaoDeObra, 
			     function($arr1, $arr2){
				  $precedenceCalc1 = $arr1->getPrecedenceOrder();
				  $precedenceCalc2 = $arr2->getPrecedenceOrder();
		                  if ($precedenceCalc1 == $precedenceCalc2) {
		                   	return 0;
		                  }
		                  return ($precedenceCalc1 < $precedenceCalc2) ? -1 : 1;

			     }
	);

	$this->arrCalculosMaoDeObra = $arrCalculosMaoDeObra;
	
    }

    public function __invoke($os){
	$soma = 0;

	foreach($this->arrCalculosMaoDeObra as $calculoMaoDeObra){
	    $soma = $calculoMaoDeObra($os);
	    
	    if(empty($soma) && $soma !== "0") {
		continue; 
	    }else{
		
		 return $soma;
	    }

	}
		
    }
    
}
