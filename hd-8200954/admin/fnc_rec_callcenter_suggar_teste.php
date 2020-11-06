<?php
//teste da funчуo recursiva do callcenter da suggar
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$controle = false;	//variavel que finaliza o loop
$ultimo_reg = 0;	//pega o ultimo registro, sem chamado anterior
$i = 0;				//contadora p/ zebrado e ajax/jQuery 

$hd_pai = 327450;

	function busca_chamados_filhos($hd_pai) {

		global $con;
		$sql = "SELECT hd_chamado FROM tbl_hd_chamado WHERE hd_chamado_anterior=$hd_pai";
//		echo $sql;
		$res = pg_query($con, $sql);

		$hd_filhos = array();

		for($i=0; $i<pg_num_rows($res); $i++) {
			$hd_atual = pg_result($res,$i,hd_chamado);
			$hd_filhos[$hd_atual] = busca_chamados_filhos($hd_atual);
		}

		return($hd_filhos);
	}

	$vet = busca_chamados_filhos($hd_pai);
	var_dump($vet);