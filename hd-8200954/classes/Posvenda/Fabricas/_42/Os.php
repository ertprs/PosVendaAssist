<?php

namespace Posvenda\Fabricas\_42;
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

        $sql = "SELECT os, data_digitacao FROM tbl_os  WHERE tbl_os.fabrica = ".$this->_fabrica." AND data_digitacao + INTERVAL '$qtdeDias days' < CURRENT_DATE AND  finalizada IS NULL AND excluida IS NOT TRUE ";

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

    function Cancelar($os){
        $pdo = $this->_model->getPDO();

        $this->_model->getPDO()->beginTransaction();
		$sql = "UPDATE tbl_os SET excluida = true where os = $os and fabrica = ".$this->_fabrica; 
	    $query = $pdo->prepare($sql);

		if (!$query->execute()) {
			$this->_model->getPDO()->rollBack();
        }           

		$sql = " insert into tbl_os_excluida(
					fabrica                     ,
					admin                       ,
					os                          ,
					sua_os                      ,
					posto                       ,
					produto                     ,
					data_digitacao              ,
					data_abertura               ,
					data_fechamento             ,
					serie                       ,
					nota_fiscal                 ,
					data_nf                     ,
					consumidor_nome             ,
					consumidor_endereco         ,
					consumidor_numero           ,
					consumidor_cidade           ,
					consumidor_estado           ,
					defeito_reclamado           ,
					defeito_reclamado_descricao ,
					defeito_constatado          ,
					revenda_cnpj                ,
					revenda_nome                ,
					consumidor_bairro           ,
					consumidor_fone             ,
					motivo_exclusao
					)
					select fabrica                     ,
					admin                       ,
					os                          ,
					sua_os                      ,
					posto                       ,
					produto                     ,
					data_digitacao              ,
					data_abertura               ,
					data_fechamento             ,
					serie                       ,
					nota_fiscal                 ,
					data_nf                     ,
					consumidor_nome             ,
					consumidor_endereco         ,
					consumidor_numero           ,
					consumidor_cidade           ,
					consumidor_estado           ,
					defeito_reclamado           ,
					defeito_reclamado_descricao ,
					defeito_constatado          ,
					revenda_cnpj                ,
					revenda_nome                ,
					consumidor_bairro           ,
					consumidor_fone             ,
					'OS excluída por estar aberta mais de 6 meses sem fechar'
					from tbl_os where os = $os" ; 
        $query = $pdo->prepare($sql);

        if (!$query->execute()) {
			$this->_model->getPDO()->rollBack();
			return false;
		}else{
			$this->_model->getPDO()->commit();
            return true;
        }           
    }
    
}
