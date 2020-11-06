<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$dir = "suggar";
$dh  = opendir($dir);
$i = 0;
while (false !== ($filename = readdir($dh))) {
	if($filename==".." OR $filename=="."){
		//faz nada se tiver .. ou . (diretórios)
	}else{
//		echo "$filename <br>";
		$nome = explode('.',$filename);
//		echo "$nome[0] <br>";
		
		$sql= "insert into tbl_comunicado (tipo, descricao, fabrica, ativo, extensao) VALUES
			('Esquema Elétrico', '$nome[0]', '24', TRUE, 'pdf')";
			echo "$sql <br>";
//		$res = @pg_exec ($con,$sql);
//		$msg_erro = pg_errormessage($con);
		
//		$res = pg_exec ($con,"SELECT CURRVAL ('seq_comunicado')");
//		$comunicado  = pg_result ($res,0,0);

//	$nome2 = $comunicado.'.pdf';
//	echo "$nome2 - $comunicado";
	
	rename("/suggar/$filename", "/suggar/novo/nomem$i.pdf");
	$i++;
	}
}

?>
