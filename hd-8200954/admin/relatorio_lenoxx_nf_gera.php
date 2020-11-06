<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="gerencia";
include 'autentica_admin.php';

$extratos = array(0 => "334657",1 => "351023",2 => "367203",3 => "376335",4 => "397634",5 => "418916");

header('Content-type: application/msexcel');
header('Content-Disposition: attachment; filename="relatorio.xls"');
for ($y=0; $y<6; $y++){

	if($extratos[$y]=='334657'){
		$data_ini = '2008-09-01';
		$data_fim = '2008-09-12';
	}
	if($extratos[$y]=='351023'){
		$data_ini = '2008-10-01';
		$data_fim = '2008-10-18';
	}
	if($extratos[$y]=='367203'){
		$data_ini = '2008-11-01';
		$data_fim = '2008-11-18';
	}
	if($extratos[$y]=='376335'){
		$data_ini = '2008-12-01';
		$data_fim = '2008-12-03';
	}
	if($extratos[$y]=='397634'){
		$data_ini = '2008-01-01';
		$data_fim = '2008-01-10';
	}
	if($extratos[$y]=='418916'){
		$data_ini = '2008-02-01';
		$data_fim = '2008-02-18';
	}
	$sql = "select 
		    tbl_os_extra.extrato, os
			from tbl_os
				join tbl_os_extra using(os)
			where tbl_os.fabrica=11 and tbl_os_extra.extrato='$extratos[$y]' and tbl_os.data_digitacao between '$data_ini 00:00:00' and '$data_fim 23:59:59' and tbl_os.posto='27401' limit 10;";

	echo "<table border=1><tr><td>";
	echo "<table bgcolor='#B9D3EE'>";
	echo "<tr> <td> &nbsp;</td> <td>EXTRATO NRO:</td> <td>".$extratos[$y]."</td> <td size='30'> &nbsp; </td> <td> MULTIPLEX</td> </tr>";
	echo "</table>";

	if(strlen($data_ini)>0 && strlen($data_fim)>0){
				echo "<table border=1>";
				echo "<tr>";
				echo "<td>OS</td><td>SÉRIE</td><td>Nº NF REVENDA</td><td>NOME REVENDA</td><td>AB</td><td>FC</td><td>CONSUMIDOR</td><td>PRODUTO</td><td>VLR M. OBRA</td>";
				echo "</tr>";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		for ($h=0; $h<pg_numrows($res); $h++){
			$extrato = trim(pg_result($res,$h,extrato));
			$os = trim(pg_result($res,$h,os));
			if(strlen($extrato)==0){
				$extrato = "Sem Extrato";
			}
			$sql2 = "select
			os, serie::text, nota_fiscal, revenda_nome,
			to_char(data_abertura,'DD/MM/YYYY')as data_abertura,
			to_char(data_fechamento,'DD/MM/YYYY')as data_fechamento,
			consumidor_nome, produto, tbl_produto.descricao, tbl_os.mao_de_obra from tbl_os join tbl_produto using(produto)
			where nota_fiscal in (
				select nota_fiscal from tbl_os
				where fabrica=11 and finalizada is not null group by nota_fiscal having count(nota_fiscal) > 1)
			and os=$os limit 10";

			$res2 = pg_exec ($con,$sql2);
			if (pg_numrows($res2) > 0) {

				for ($i=0; $i<pg_numrows($res2); $i++){
					$os = trim(pg_result($res2,$i,os));
					$nf = trim(pg_result($res2,$i,nota_fiscal))   ;
					$revenda_nome =trim(pg_result($res2,$i,revenda_nome))   ;
					$dt_ini2 = trim(pg_result($res2,$i,data_abertura))   ;
					$dt_fim2 = trim(pg_result($res2,$i,data_fechamento))   ;
					$consumidor = trim(pg_result($res2,$i,consumidor_nome))   ;
					$produto = trim(pg_result($res2,$i,descricao))   ;
					$serie = trim(pg_result($res2,$i,serie))   ;
					$mao_obra = trim(pg_result($res2,$i,mao_de_obra))   ;
					if(strlen($mao_obra)==0){
					$mao_obra = 0;
					}
					if(strlen($consumidor)==0){
					$consumidor = "-";
					}
					if(strlen($dt_ini2)==0){
					$dt_ini2 = "-";
					}
					if(strlen($dt_fim2)==0){
					$dt_fim2 = "-";
					}
					echo "<tr>";
					echo "<td align='center'>$os</td><td align='center'>$serie</td><td align='center'>$nf</td><td align='center'>$revenda_nome</td><td align='center'>$dt_ini2</td><td align='center'>$dt_fim2</td><td align='center'>$consumidor</td><td align='center'>$produto</td><td align='center'>$mao_obra</td>";
					echo "</tr>";
				}

			}else{
					echo "Não foram encontrados resultados!</td></tr></table><br>";
			}
		}
	}else{
	?>
	<script>
		alert("Nenhum Registro foi encontrado no intervalo de  \"<? echo $data_ini?>\" e \"<? echo $data_fim?>\"");
		<? echo "Nenhum Registro foi encontrado no intervalo de  echo".$data_ini."e".data_fim; ?>
	</script>
	<?
	}
	}else{
	?>
	<script>
		alert("Nenhum Registro foi encontrado no intervalo de  \"<? echo $data_ini?>\" e \"<? echo $data_fim?>\" Por favor volte e refaça a busca.");
		<? echo "Nenhum Registro foi encontrado no intervalo de  echo".$data_ini ."e".$data_fim; ?>
	</script>
	<?
	}
	echo "</table></td></tr>";
}
?>