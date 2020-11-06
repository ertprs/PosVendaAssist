<?php 

use Posvenda\Model\Extrato as ExtratoModel;
/**
* Class Extrato Midea Carrier
*/
class ExtratoYanmar
{
    public $_model;
    protected $_fabrica;
    private $extrato;

    public function __construct($fabrica) {

        $this->_fabrica = $fabrica;

        if(!empty($this->_fabrica)){
            $this->_model = new ExtratoModel($this->_fabrica);
        }        
    }

    public function getTipoAtendimento(){
    	$pdo = $this->_model->getPDO();

    	$sql = "SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = ". $this->_fabrica ." and ativo and tipo_atendimento in (220,218,217) order by tipo_atendimento desc ";
        $query  = $pdo->query($sql);
        $res    = $query->fetchAll(\PDO::FETCH_ASSOC);

        return $res;

    }

    public function gravaExtratoAgrupado($extrato, $codigo){
    	$pdo = $this->_model->getPDO();

    	//select extrato zerado e sem os 
    	$sql = "SELECT count(1) as qtde from tbl_os_extra WHERE extrato = $extrato";
    	$query  = $pdo->query($sql);
    	$res    = $query->fetch(\PDO::FETCH_ASSOC);
    	$qtde = $res['qtde'];

    	$sqlEx 	  = "SELECT total from tbl_extrato WHERE extrato = $extrato";
    	$queryEx  = $pdo->query($sqlEx);
    	$resEx    = $queryEx->fetch(\PDO::FETCH_ASSOC);
    	$total 	  = $resEx['total'];

    	if($qtde > 0 OR $total > 0){    	
	    	$sql = "INSERT INTO tbl_extrato_agrupado (extrato, codigo) VALUES ($extrato, '".$codigo[0]."') ";
	    	$query  = $pdo->query($sql);
	    	if(!$query){
	            return false; 
	        }
	        return true;
	    }else{
	    	return false;
	    }
    }
}
	

?>
