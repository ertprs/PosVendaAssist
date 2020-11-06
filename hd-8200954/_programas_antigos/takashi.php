<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';

$extrato = 112849;
//pega no extrato e verifica se na tbl_os.tipo_os_cortesia = 'Compressor';
/*$sql  = "SELECT hora_chegada_cliente as inicio, 
				hora_saida_cliente as fim,
				km_chegada_cliente as km, 
				valor_adicional
			FROM tbl_os_visita 
			WHERE os=$os";
*/
$sql =  "SELECT tbl_os.os,
				tbl_os_visita.hora_chegada_cliente as inicio,
				tbl_os_visita.hora_saida_cliente as fim,
				tbl_os_visita.km_chegada_cliente as km,
				tbl_os_visita.valor_adicional
			FROM tbl_os_extra
			JOIN tbl_os using(os)
			JOIN tbl_os_visita using(os)
			WHERE extrato = $extrato
			AND   tbl_os.tipo_os_cortesia = 'Compressor'
			ORDER by os";
//echo $sql;
$res = pg_exec($con, $sql);

for($i=0; pg_numrows($res)>$i;$i++){
	$os               = pg_result($res,$i, os);
	$inicio           = pg_result($res,$i, inicio);
	$fim              = pg_result($res,$i, fim);
	$km               = pg_result($res,$i, km);
	$valor_adicional  = pg_result($res,$i, valor_adicional);
if(strlen($valor_adicional)==0){$valor_adicional=0;}
	echo "<BR>$i : $inicio - $fim";

/*tulio*/
	$sql_horas = "SELECT extract ( 'hour' from timestamp '$fim' 
								- timestamp '$inicio') as horas";
	$res_horas = pg_exec($con, $sql_horas);
	$horas = pg_result($res_horas,0, horas);	

	$sql_minutos = "SELECT extract ( 'minute' from timestamp '$fim' 
								- timestamp '$inicio') as minutos";
	$res_minutos = pg_exec($con, $sql_minutos);
	$minutos = pg_result($res_minutos,0, minutos);	

	$total_minutos = $total_minutos + ($horas*60)+$minutos;
/*tulio*/

	echo "<BR>$os - total de minutos: <B>$total_minutos</b>";
	echo "<BR>$os - total de km: <b>$km</b>";
	echo "<BR>$os - total de valor adi: <B>$valor_adicional</B><Br>";
	
	$sql_update = "UPDATE tbl_os_extra set 
							qtde_horas            = qtde_horas + $total_minutos            ,
							qtde_km               = qtde_km + $km                          ,
							mao_de_obra_adicional = mao_de_obra_adicional + $valor_adicional
					WHERE os = $os";
	echo "$sql_update<BR>";
}


	$sql2 = "SELECT tbl_os_extra.os, 
					SUM (tbl_os_extra.qtde_horas * tbl_os_extra.valor_total_hora_tecnica) as valor_hora, 
					SUM(tbl_os_extra.qtde_km*tbl_os_extra.valor_por_km) as valor_km,
					mao_de_obra_adicional 
				FROM tbl_os_extra 
				JOIN tbl_os using(os)  
				WHERE extrato = $extrato 
				AND   tbl_os.tipo_os_cortesia='Compressor' 
				GROUP BY tbl_os_extra.os, tbl_os_extra.mao_de_obra_adicional";
	echo "<BR>$sql2<BR>";
	
	$sql2 = "SELECT tbl_os_extra.os as os, 
					SUM ((tbl_os_extra.qtde_horas * tbl_os_extra.valor_total_hora_tecnica) + (tbl_os_extra.qtde_km * tbl_os_extra.valor_por_km) + tbl_os_extra.mao_de_obra_adicional) as valor_total 
				FROM tbl_os_extra
				JOIN tbl_os using(os)
				WHERE extrato = $extrato
				AND   tbl_os.tipo_os_cortesia = 'Compressor'
				GROUP by tbl_os_extra.os";
	echo "<BR>$sql2<BR>";
	$res = pg_exec($con, $sql2);

for($i=0; pg_numrows($res)>$i;$i++){
	$os               = pg_result($res,$i, os);
	$valor_total           = pg_result($res,$i, valor_total);
	if(strlen($valor_total)==0){$valor_total=0;}
	
	$sql_up = "UPDATE tbl_os SET
								mao_de_obra = $valor_total
						WHERE os=$os
						AND fabrica=$login_fabrica";
	echo "$sql_up<BR>";
}

?>