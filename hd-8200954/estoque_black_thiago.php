<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

ini_set('max_execution_time','-1');
exit;
#Begin
$sql ="BEGIN TRANSACTION";
$res = pg_query($con,$sql);

$fabrica = 1;
$posto_ant = 666;
$posto_novo = 149949;
$obs = 'Saldo referente ao posto 21475';

$sql_linhas ="SELECT peca,qtde FROM tbl_estoque_posto WHERE fabrica = $fabrica AND posto=$posto_ant;";
$res_linhas = pg_query($con,$sql_linhas);
$num_linhas = pg_num_rows($res_linhas);

for($i=0;$i<$num_linhas;$i++){

	$peca = pg_result($res_linhas,$i,'peca');
	$qtde = pg_result($res_linhas,$i,'qtde');

	$sql = "SELECT peca,qtde 
			FROM tbl_estoque_posto 
			WHERE fabrica = $fabrica 
			AND posto=$posto_novo 
			AND peca = $peca;";
	$res = pg_query($con,$sql);
	$num = pg_num_rows($res);
	
	if($num > 0){
		$upt = "UPDATE tbl_estoque_posto 
				SET	qtde = qtde + ($qtde)
				WHERE fabrica = $fabrica 
				AND posto=$posto_novo 
				AND peca = $peca;";
		$res = pg_query($con,$upt);
	}else{
		$ins = "INSERT INTO tbl_estoque_posto  (
							fabrica, 
							posto,
							peca,
							qtde)
						VALUES (
							$fabrica,
							$posto_novo,
							$peca,
							$qtde
						)";
		$res = pg_query($con,$ins);
	}
	
	if($qtde <> 0){
		
		$qtde_entrada = ($qtde>0) ? $qtde : 'null';
		$qtde_saida = ($qtde<0) ? $qtde : 'null';
	
		$ins = "INSERT INTO tbl_estoque_posto_movimento(
							fabrica, 
							posto,
							peca,
							data,
							qtde_entrada,
							qtde_saida,
							obs
					)VALUES (
							$fabrica,
							$posto_novo,
							$peca,
							'".date('2011-08-08')."',
							$qtde_entrada,
							$qtde_saida,
							'$obs'
						)";
		$res = pg_query($con,$ins);
	}

}

#Commit
$sql ="COMMIT TRANSACTION";
$res = pg_query($con,$sql);

?>
