<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$layout_menu = "gerencia";
$title = "RELATÓRIO - QUANTIDADE DE OS APROVADAS POR POSTO";

include "cabecalho.php";


//if(pg_numrows($res) > 0){

$sql = "SELECT CURRENT_DATE";
$res = pg_exec ($con,$sql);
$data_final = pg_result ($res,0,0);
$data_final = substr ($data_final,0,8) . "01" ;
$sql = "SELECT ('$data_final'::date - INTERVAL '1 day')::date ";
$res = pg_exec ($con,$sql);
$data_final = pg_result ($res,0,0) . " 23:59:59";

$data_inicial = $data_final ;
$sql = "SELECT (('$data_inicial'::date - INTERVAL '3 months') + INTERVAL '2 days')::date ";
$res = pg_exec ($con,$sql);
$data_inicial = pg_result ($res,0,0) . " 00:00:00";


/*
echo $data_inicial;
echo "<br>";
echo $data_final;
echo "<br>";
*/


echo "<table style='font-family: verdana ; font-size: 10px; border-collapse: collapse' align='center'  bordercolor='#d2e4fc' border='1'>";
echo "<tr bgcolor='#FFFFCC' background='imagens_admin/amarelo.gif'>";
echo "<td rowspan='2'><b>Codigo</td>";
echo "<td rowspan='2'><b>Nome</td>";
if($login_fabrica==20) echo "<td rowspan='2'><b>Pais</td>";

$sql = "SELECT descricao FROM tbl_familia WHERE fabrica = $login_fabrica ORDER BY descricao";
$resX = pg_exec ($con,$sql);
$array_linhas = "";
for ($i = 0 ; $i < pg_numrows ($resX) ; $i++) {
	echo "<td colspan='3'><b>" . pg_result ($resX,$i,0) . "</td>";
}
$qtde_linhas = $i;


$indice = 0 ;
for ($i = 0 ; $i < $qtde_linhas ; $i++) {
	$mes = pg_result (pg_exec ($con,"SELECT TO_CHAR ('$data_inicial'::date , 'MM')"),0,0);
	$array_linha_mes [$indice][1] = trim (pg_result ($resX,$i,0)) . "-" . $mes;
	$array_linha_mes [$indice][2] = "0";
	$indice++;
	$mes = pg_result (pg_exec ($con,"SELECT TO_CHAR ('$data_inicial'::date + INTERVAL '1 month', 'MM')"),0,0);
	$array_linha_mes [$indice][1] = trim (pg_result ($resX,$i,0)) . "-" . $mes;
	$array_linha_mes [$indice][2] = "0";
	$indice++;
	$mes = pg_result (pg_exec ($con,"SELECT TO_CHAR ('$data_inicial'::date + INTERVAL '2 months', 'MM')"),0,0);
	$array_linha_mes [$indice][1] = trim (pg_result ($resX,$i,0)) . "-" . $mes;
	$array_linha_mes [$indice][2] = "0";
	$indice++;
}

/*
for ($i = 0 ; $i < $qtde_linhas * 3 ; $i++) {
	echo "<br>";
	echo $array_linha_mes [$i][1];
	echo "<br>";
}

exit;
*/


echo "<td rowspan='2'><b>TOTAL</td>";
echo "</tr>";

echo "<tr bgcolor='#FFFFCC'>";


for ($i = 0 ; $i < $qtde_linhas ; $i++) {
	echo "<td><b> Mes ";
	$res = pg_exec ($con,"SELECT TO_CHAR ('$data_inicial'::date,'MM')");
	echo pg_result ($res,0,0);
	echo "</td>";

	echo "<td><b> Mes ";
	$res = pg_exec ($con,"SELECT TO_CHAR ('$data_inicial'::date + INTERVAL '1 month','MM')");
	echo pg_result ($res,0,0);
	echo "</td>";

	echo "<td><b> Mes ";
	$res = pg_exec ($con,"SELECT TO_CHAR ('$data_inicial'::date + INTERVAL '2 months','MM')");
	echo pg_result ($res,0,0);
	echo "</td>";
}




$sql = "SELECT  tbl_posto_fabrica.codigo_posto ,
				tbl_posto.nome  AS nome_posto  ,
				tbl_posto.pais                 ,
				os.linha_descricao             ,
				mes                            ,
				os.qtde
		FROM    tbl_posto
		JOIN    tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		LEFT JOIN (SELECT COUNT(*) AS qtde , tbl_os.posto, tbl_familia.descricao AS linha_descricao , TO_CHAR (tbl_extrato.aprovado,'MM') AS mes
			FROM tbl_os
			JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
			JOIN tbl_familia   ON tbl_produto.familia = tbl_familia.familia
			JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os
			JOIN tbl_extrato ON tbl_os_extra.extrato = tbl_extrato.extrato
			WHERE tbl_extrato.aprovado BETWEEN '$data_inicial' AND '$data_final'
			AND   tbl_os.fabrica = $login_fabrica
			GROUP BY tbl_os.posto, tbl_familia.descricao, TO_CHAR (tbl_extrato.aprovado,'MM')
		) os ON tbl_posto.posto = os.posto
		ORDER BY tbl_posto.pais,nome_posto, mes, linha_descricao ";

#echo $sql;
$res = pg_exec ($con,$sql);

$codigo_posto_ant = pg_result ($res,0,codigo_posto) ;
$nome_posto_ant   = pg_result ($res,0,nome_posto) ;


for ($i = 0 ; $i < pg_numrows ($res) +1 ; $i++) {
	if($cor=="#F1F4FA")     $cor = '#F7F5F0';
	else                    $cor = '#F1F4FA';
	$quebra = false;

	if ($i >= pg_numrows ($res) ) {
		$quebra = true;
	}else{
		if ($codigo_posto_ant <> pg_result ($res,$i,codigo_posto) ) $quebra = true;
	}

	if ($quebra ) {
		
		echo "<tr bgcolor='$cor'>";
		echo "<td align='left'>" . $codigo_posto_ant . "</td>";
		echo "<td align='left'>" . $nome_posto_ant . "</td>";
		if($login_fabrica==20) echo "<td align='right'> $pais_ant </td>";

		$total = 0 ;
		for ($x = 0 ; $x < $qtde_linhas * 3; $x++ ) {
			echo "<td align='right'>";
			echo $array_linha_mes[$x][2];
			echo "</td>";
			$total += $array_linha_mes[$x][2] ;

			$array_linha_mes[$x][2] = "0";
		}
		echo "<td align='right'> $total </td>";

		echo "</tr>";
	}

	if ($i < pg_numrows ($res) ) {
		$codigo_posto_ant = pg_result ($res,$i,codigo_posto);
		$nome_posto_ant   = pg_result ($res,$i,nome_posto);
		$pais_ant         = pg_result ($res,$i,pais);

		for ($x = 0 ; $x < $qtde_linhas * 3 ; $x++ ) {
			$celula = $array_linha_mes [$x][1];
			if (trim (pg_result ($res,$i,linha_descricao)) . "-" . pg_result ($res,$i,mes) == $celula ) {
				$array_linha_mes [$x][2] = pg_result ($res,$i,qtde);
			}
		}
	}
}







echo "</table>";


?>


<?
include 'rodape.php';
?>