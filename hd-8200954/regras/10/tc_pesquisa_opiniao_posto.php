<?php

$fabricas_pesquisa = array(
      1,  10,  24,  30,  51,
     52,  74,  85,  88,  94,
    129, 145, 152, 161, 169,
);
if(!empty($login_fabrica) and empty($cook_admin)) {
$sql = "select posto from tbl_faturamento where fabrica = 10 and distribuidor = 4311 and posto = $login_posto and data_input > current_timestamp - interval '6 months'";
$res = pg_query($con,$sql);
if (pg_num_rows($res) > 0) {
	$sqlr = "select * from tbl_resposta where pesquisa = 673 and posto = $login_posto ";
	$resr = pg_query($con, $sqlr);
	if(pg_num_rows($resr) == 0) {
		header("Location: opiniao_posto_new.php?pesquisa=673");
	}
   
}
}
