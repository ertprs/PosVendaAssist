<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$sql = 'select posto from tbl_posto join tbl_posto_fabrica using(posto) where fabrica = 74;';
$res1 = pg_query($con,$sql);
echo '<table width="100%">';
for($i = 0;$i < pg_num_rows($res1); $i++) {

	$posto = pg_result($res1, $i,0);
	$sql2 = 'select nome, cnpj, estado, cidade
			 from tbl_posto join tbl_posto_fabrica using(posto) 
			 where tbl_posto.posto = ' . $posto . ' 
			 group by nome,cnpj, estado,cidade
			 having count(tbl_posto_fabrica.posto) > 1';
	$res2 = pg_query($con,$sql2);
	//$fp = fopen('/var/www/assist/www/xls/atlas-postos-cmp.txt',"w");
	
	for($j = 0; $j < pg_num_rows($res2); $j++ ) {	
		
		$razao = pg_result( $res2, $j , 0 );
		$cnpj  = pg_result( $res2, $j , 1 );
		$estado= pg_result( $res2, $j , 2 );
		$cidade= pg_result( $res2, $j , 3);
		
		//fputs($fp, $razao . "\t" . $cnpj);
		echo '<tr>
				<td>'.$razao.'</td>
				<td>'.$cnpj.'</td>
				<td>'.$cidade.'</td>
				<td>'.$estado.'</td>				
  			  </tr>';
		
	
	}
	
	//fclose($fp);
	
}
echo '</table>';