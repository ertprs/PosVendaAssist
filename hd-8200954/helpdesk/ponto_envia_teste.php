<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

if ($btn_acao
for($i=0;$i<2;$i++){
	$data1			 = trim($HTTP_POST_VARS["data1_" . $i]);
	$data_tmp		 = trim($HTTP_POST_VARS["data_tmp_" . $i]);
	$motivo			 = trim($HTTP_POST_VARS["motivo_" . $i]);
	$colaborador     = $HTTP_POST_VARS["colaborador_" . $i];
	$observacao      = trim($HTTP_POST_VARS["observacao_" . $i]);


	$data2 = " $data_tmp";
	$data=$data1.$data2;

	$sql = "INSERT INTO tbl_ponto_digital (
								data			,
								motivo			,
								admin			,
								observacao
							) VALUES (
								'$data'		 ,
								'$motivo'	 ,
								$colaborador ,
								'$observacao'
							);";
	$res = @pg_exec ($con,$sql);
}

if (!$res){
	echo "<h3><center>Erro na inserção do registro!</center></h3>";
	}else{
	echo "<h3><center>Registro inserido com sucesso!</center><h3>";
}
?>
<br><br>
<form name='frm_ponto_digital_voltar' action=ponto_teste.php>
<center>
<input type="submit" name='btn_voltar' value="Voltar">
<center>
</form>