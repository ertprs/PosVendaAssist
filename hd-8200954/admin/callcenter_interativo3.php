<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';

$sql = "select hd_chamado, data from tbl_hd_chamado where fabrica_responsavel<>10";

$res = pg_exec($con,$sql);
if(pg_numrows($res)>0){
	for($i=0;pg_numrows($res)>$i;$i++){
		$hd_chamado = pg_result($res,$i,hd_chamado);
		$data       = pg_result($res,$i,data);
		echo "<B>$hd_chamado - $data </b><BR>";

		$xsql = "select hd_chamado_item, 
						data 
				from tbl_hd_chamado_item 
				where hd_chamado = $hd_chamado 
				and interno is not true order by data;";
		$xres = pg_exec($con,$xsql);
		if(pg_numrows($xres)>0){
			$xhd_chamado_item_ant = "$hd_chamado";
			$xdata_ant = "$data ";
			for($x=0;pg_numrows($xres)>$x;$x++){
				$xhd_chamado_item = pg_result($xres,$x,hd_chamado_item);
				$xdata            = pg_result($xres,$x,data);
				echo "$xhd_chamado_item - $xdata <BR>";
				
				echo " * ($xdata_ant - $xdata) ";
				$ysql = "select ('$xdata')::timestamp - ('$xdata_ant')::timestamp;";
			//	echo $ysql."<BR>";
				$yres = pg_exec($con,$ysql);
				$intervalo = pg_result($yres,0,0);
				echo " = $intervalo ";
			//	echo "<I>Entao $xhd_chamado_item demorou $intervalo desde a ultima interacao </i><BR><BR>";

				$zsql = "select count(*) as qtde 
						FROM fn_calendario('$xdata_ant','$xdata') 
						where nome_dia in ('Domingo','Sábado');";
				//		echo $zsql;
				$zres = pg_exec($con,$zsql);
				$fds = pg_result($zres,0,0);
				echo " - e tem $fds fim de semana";

				$hsql = "select count(*) as qtde 
						from tbl_feriado 
						where fabrica = 6
						and ativo is true 
						and data between '$xdata_ant' and '$xdata';";
				$hres = pg_exec($con,$hsql);
				$feriado = pg_result($hres,0,0);
				echo " - e tem $feriado feriado ";

				$ksql = "select '$intervalo'::interval - '$feriado days' - '$fds days';";
				$kres = pg_exec($con,$ksql);
				$intervalo_real = pg_result($kres,0,0);
				echo "|| Intervalo real <B>$intervalo_real</b><BR><BR> ";

				$wsql = "UPDATE tbl_hd_chamado_item set tempo_interacao='$intervalo_real' where hd_chamado_item = $xhd_chamado_item";
				//echo $wsql;
				//echo "<BR><BR>";
				$wres = pg_exec($con,$wsql);

				$xhd_chamado_item_ant = $xhd_chamado_item;
				$xdata_ant = $xdata ;
			}
		}
	
	}

}



//select date_part('day',interval '02:04:25.296765');

?>