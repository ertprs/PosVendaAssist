<?php

error_reporting(E_ALL ^ E_NOTICE);

try {
	
    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

    define('APP', 'Atualiza Status Pedido');
	define('ENV','producao');

    $vet['fabrica'] = 'Telecontrol';
    $vet['tipo']    = 'atualiza-status';
    $vet['dest']    = ENV == 'testes' ? 'ronald.santos@telecontrol.com.br' : 'helpdesk@telecontrol.com.br';
    $vet['log']     = 1;

     $sql = "SELECT  revenda,cnpj from tbl_revenda
		where  cnpj_validado isnull
	       AND data_digitacao > current_date- interval '1 month'	";
	$res = pg_query($con,$sql);
	if(pg_numrows($res) > 0){
		for($i = 0; $i < pg_numrows($res); $i++){
			$msg_erro = "";
			$revenda		= pg_result($res,$i,'revenda');
			$cnpj		= pg_result($res,$i,'cnpj');
			$sql2 = "select fn_valida_cnpj_cpf('$cnpj')";
			$res2 = pg_query($con,$sql2);
			$msg_erro .= pg_errormessage($con);
			if(!empty($msg_erro)){
				$sql2 = "update tbl_revenda set cnpj_validado = false where revenda = $revenda";
				$res2 = pg_query($con,$sql2);
			}else{
				$sql2 = "update tbl_revenda set cnpj_validado = true where revenda = $revenda";
				$res2 = pg_query($con,$sql2);
			}
		}
	}

	if (!empty($msg_erro)) {
		$msg = 'Script: '.__FILE__.'<br />' . $msg_erro;
	}

} catch (Exception $e) {

    $msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
    Log::envia_email($vet, APP, $msg);

}
