<?php

namespace Posvenda\Helpers;

use Posvenda\Model\Os as OsModel;

class Auditoria
{

    public static function gravar(
    	$os,
    	$auditoria_status, 
    	$observacao, 
    	$descricao_status_checkpoint = null,
    	$con = null
    ) :bool
    {
     	global $login_admin, $login_fabrica; 
        
     	$osModel = new OsModel($login_fabrica, $os, $con);

     	if (!$osModel->entrouEmAuditoria($os, $observacao, $auditoria_status)) {

     		 $sql = "
	            INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao)
	            VALUES ({$os}, {$auditoria_status}, '{$observacao}');
	         ";
        	
        	if (is_resource($con)) {

        		$query = pg_query($con, $sql);

        		if (pg_last_error($con)) {
        			return false;
        		}

        	} else {
        		
        		$pdo = $osModel->getPDO();
        		$query = $pdo->query($sql);

        		if (!$query) {
        			return false;
        		}

        	}

        	if (!empty($descricao_status_checkpoint)) {

        		$osModel->atualizaStatusCheckpoint($os, $descricao_status_checkpoint);

        	}

     	}

     	return true;

    }

}
