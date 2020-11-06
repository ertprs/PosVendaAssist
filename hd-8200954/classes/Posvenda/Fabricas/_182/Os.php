<?php

namespace Posvenda\Fabricas\_182;
use Posvenda\Os as OsPosvenda;

class Os extends OsPosvenda
{
    private $_fabrica;

    public function __construct($fabrica, $os = null, $conn = null)
    {  

        if (!empty($fabrica)) {
            $this->_fabrica = $fabrica;
        }

        if (!empty($os)) {
            $this->_os = $os;
        }

        parent::__construct($this->_fabrica, $this->_os, $conn);

    }

    function buscarOs($qtdeDias){

        $pdo = $this->_model->getPDO(); 

        $sql = "SELECT os FROM tbl_os  WHERE tbl_os.fabrica = ".$this->_fabrica." AND data_abertura + INTERVAL '".$qtdeDias." days' < CURRENT_DATE AND  finalizada IS NULL AND excluida IS NOT TRUE ";

        $query = $pdo->prepare($sql);

        if (!$query->execute()) {
            return false;
        } else {
            
            $res = $query->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($res as $key) {
                $dados[] = $key['os'];
            }
        }

        return $dados;

    }

    function tempoReparar($os, $tempo_reparo){
            $pdo = $this->_model->getPDO();

            $sql_busca_reparo = "SELECT defeito_constatado_reclamado from tbl_os_defeito_reclamado_constatado where os = $os AND(tempo_reparo is null or tempo_reparo = 0) AND tbl_os_defeito_reclamado_constatado.fabrica = ".$this->_fabrica." limit 1"; 

            $query = $pdo->prepare($sql_busca_reparo);

            if (!$query->execute()) {
                return false;
            } else {
                
                $res_busca_reparo = $query->fetchAll(\PDO::FETCH_ASSOC);
                
                if(!empty($res_busca_reparo)){
                    $defeito_constatado_reclamado = $res_busca_reparo[0]['defeito_constatado_reclamado'];

                    $sql_reparo = "UPDATE tbl_os_defeito_reclamado_constatado SET tempo_reparo = $tempo_reparo 
                        Where os = $os 
                        and defeito_constatado_reclamado = $defeito_constatado_reclamado 
                        AND(tempo_reparo is null or tempo_reparo = 0) 
                        AND fabrica = ".$this->_fabrica ;

                    $res_reparo = $pdo->query($sql_reparo);                
				}else{
                    $sql_reparo = "INSERT INTO tbl_os_defeito_reclamado_constatado(os,tempo_reparo,fabrica) values($os,$tempo_reparo,".$this->_fabrica.")";
                    $res_reparo = $pdo->query($sql_reparo);                
				}

                if(empty($res_reparo)){
                    return false;
                }else{
                    return true;
                }  
            }

    }    

    function finalizar($os){
        $pdo = $this->_model->getPDO();

        $sql = "UPDATE tbl_os SET data_fechamento = now(), finalizada = now() where os = $os and fabrica = ".$this->_fabrica;
        $query = $pdo->query($sql);
        if(empty($query)){
            return false;
        }else{
            return true;
        }           
    }
    
}
