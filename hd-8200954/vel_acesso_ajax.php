<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

function anti_injection($string){
   $string = str_replace("'",  "", $string);
   $string = str_replace("*", "", $string);
   $string = str_replace("\"", "", $string);
   $string = str_replace("\\", "", $string);
   $string = trim($string);
   $string = strip_tags($string);
   $string = addslashes($string);
   return $string;
}

//	tratos todos de uma vez e já cria as variaveis correspondentes
foreach ($_REQUEST as $campo => $valor) {
$$campo = anti_injection ($valor);
}
if ($ajax="sim") {
//	Para evitar o cache da página e devolver dados anteriores
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sun, 01 Mar 2009 05:00:00 GMT");

	if ($velocidade <> "Discado" and $velocidade <> "Banda Larga"or !is_numeric($posto)) {
		echo "KO:Informações incorretas";
		exit;
	}

    $sql = "SELECT posto FROM tbl_posto WHERE posto=$posto";
	$res= pg_query($con,$sql);
	if (pg_num_rows($res)==0) {
		echo "KO:O posto $posto não existe!";
		exit;
	}

//  Agora sim, se está tudo certo, grava a informação
	$sql = "UPDATE tbl_posto SET velocidade_internet = '$velocidade' ".
				"WHERE posto = $posto";
// Testar...	echo $sql;exit;
	$res= pg_query($con,$sql);
	$msg_erro = pg_errormessage($con);
	if(strlen($msg_erro) == 0) {
		$msg_erro="Dados atualizados com sucesso!";
	}
	else{
		$msg_erro="KO:".$msg_erro;
	}
	echo $msg_erro;
	exit;
}
exit;
?>
