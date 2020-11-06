<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="gerencia";
include 'autentica_admin.php';

$data_inicial       = $_GET['data_inicial'];
$data_final         = $_GET['data_final'];
$produto            = $_GET['produto'];
$linha              = $_GET['linha'];
$estado             = $_GET['estado'];
$pais               = $_GET['pais'];
$familia            = $_GET['familia'];
$posto              = $_GET['posto'];
$consumidor_revenda = $_GET['consumidor_revenda'];
$tipo_pesquisa      = $_GET['tipo_pesquisa'];

if ($login_fabrica <> 20) $pais = 'BR';

// Alterado por Paulo - chamado : 3195
//ALTERADO POR IGOR
if($login_fabrica == 20 and $pais != 'BR'){
	$sql = "SELECT tbl_produto.referencia, tbl_produto_idioma.descricao FROM tbl_produto LEFT JOIN tbl_produto_idioma on tbl_produto.produto = tbl_produto_idioma.produto and tbl_produto_idioma.idioma = 'ES' WHERE tbl_produto.produto = $produto";
}else{
	$sql = "SELECT referencia, descricao FROM tbl_produto WHERE produto = $produto";
}
$res = pg_exec($con,$sql);
$descricao_produto  = pg_result($res,0,descricao);
$referencia_produto = pg_result($res,0,referencia);

$aux_data_inicial = substr($data_inicial,8,2) . "/" . substr($data_inicial,5,2) . "/" . substr($data_inicial,0,4);
$aux_data_final   = substr($data_final,8,2)   . "/" . substr($data_final,5,2)   . "/" . substr($data_final,0,4);

$title = "RELATÓRIO DE QUEBRA DE PEÇAS";

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<HEAD>
<TITLE><? echo $title; ?></TITLE>

<style type="text/css">
.Titulo { text-align: center; font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif; font-size: 10px;font-weight: bold; color: #FFFFFF; background-color: #485989;}
.Conteudo { font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif; font-size: 10px; font-weight: normal;}
.Mes{ font-size: 9px;}
</style>
</HEAD>

<BODY>
<?
$cond_1 = "1=1";
$cond_2 = "1=1";
$cond_3 = "1=1";
$cond_4 = "1=1";
$cond_5 = "1=1";
$cond_6 = "1=1";
	
#if (strlen ($familia) > 0) $cond_1 = " tbl_produto.familia = $familia ";
if (strlen ($estado) > 0)             $cond_2 = " tbl_posto.estado    = '$estado' ";
if (strlen ($posto)  > 0)             $cond_3 = " tbl_posto.posto     = $posto ";
if (strlen ($linha)  > 0)             $cond_4 = " tbl_produto.linha   = $linha ";
if (strlen ($consumidor_revenda) > 0) $cond_5 = " tbl_os.consumidor_revenda = '$consumidor_revenda' ";
if (strlen ($pais)   > 0)             $cond_6 = " tbl_posto.pais     = '$pais' ";


echo "<table><tr><td align='center'>";
//include 'relatorio_field_call_rate_pecas_grafico.php';
echo "</td></tr></table>";
?>
<TABLE WIDTH = '600' align = 'center' class='Conteudo'>
	<TR>
		<TD align = 'center'><B><font size='3'><? echo $title; ?></font></B></TD>
	</TR>
	<TR>
		<TD>
		<?
		echo "<TABLE  border='0' cellspacing='0' cellpadding='2' align='center' name='relatorio' id='relatorio'  style=' border:#485989 1px solid; background-color: #e6eef7 '>";
		echo "<tr>";
		echo "<td colspan='12' class='Titulo' nowrap>PROCESSANDO OS SEGUINTES MESES</td>";
		echo "</tr>";
		echo "<tr>";
		for($x=0;$x<12;$x++){
			$fator = 11 -$x;
			$sql = "SELECT TO_CHAR(('$data_inicial'::DATE - INTERVAL'$fator MONTH')::DATE,'MM/YY');";
			$res = pg_exec($con,$sql);
			$xMES = pg_result($res,0,0);
			echo "<td width='40' height='40' class='Conteudo' bgcolor='#FFFFDD' id='mes_$x' align='center'>$xMES</td>";
		}
		echo "</tr></table>";
		flush();
		?>
		</TD>
	</TR>
	<TR>
		<TD align='center'>PRODUTO: <b><? echo $referencia_produto ." - ". $descricao_produto; ?></b></TD>
	</TR>
</TABLE>

<BR>

<?
flush();

if($login_fabrica == 20 and $pais != 'BR'){
	$tipo_data = " tbl_extrato.data_geracao ";
}else{
	if($login_fabrica == 20)
		$tipo_data = " tbl_extrato_extra.exportado ";
	else
		$tipo_data = " tbl_extrato.data_geracao ";
}
	if ($login_fabrica == 14) $sql_14 = "AND   tbl_extrato.liberado IS NOT NULL ";

//--==== Otimização para rodar o relatório Anual =============================================


for($x=0;$x<12;$x++){
	$sql = "SELECT ('$data_inicial'::DATE - INTERVAL'$x MONTH')::DATE || ' 00:00:00';";
	$res = pg_exec($con,$sql);
	$xdata_inicial = pg_result($res,0,0);


	$sql = "SELECT ('$xdata_inicial'::DATE + INTERVAL'1 MONTH'- INTERVAL'1 day')::DATE || ' 23:59:59';";
	$res = pg_exec($con,$sql);
	$xdata_final = pg_result($res,0,0);

	if($x==0){
		$sql = "
			SELECT DISTINCT tbl_os_extra.os ,  $tipo_data::date AS data_analise
			INTO   TEMP temp_fcr_pecas2_osex_$login_admin
			FROM  tbl_os_extra
			JOIN tbl_extrato USING (extrato)
			JOIN tbl_extrato_extra USING (extrato)
			WHERE tbl_extrato.fabrica = $login_fabrica
			AND $tipo_data BETWEEN '$xdata_inicial' AND '$xdata_final'
			$sql_14;

			CREATE INDEX temp_fcr_pecas2_osex_os_$login_admin ON temp_fcr_pecas2_osex_$login_admin(os);
		
			SELECT tbl_os.os , data_analise ,tbl_os.produto
			INTO  TEMP temp_fcr_pecas2_os_$login_admin
			FROM tbl_os 
			JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
			JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
			JOIN temp_fcr_pecas2_osex_$login_admin ON temp_fcr_pecas2_osex_$login_admin.os = tbl_os.os
			WHERE 1=1
			AND tbl_os.excluida IS NOT TRUE
			AND tbl_os.produto = $produto
			AND   $cond_1
			AND   $cond_2
			AND   $cond_3
			AND   $cond_4
			AND   $cond_5 ";
		if  ($login_fabrica == 20 and $pais != 'BR') $sql .= "AND   $cond_6 ";
		$sql .=";
			CREATE INDEX temp_fcr_pecas2_os_os_$login_admin ON temp_fcr_pecas2_os_$login_admin(os);

			ALTER TABLE temp_fcr_pecas2_os_$login_admin ADD contabilizado BOOLEAN;

			SELECT tbl_os_item.peca, TO_CHAR(data_analise,'MM/YY') AS data_analise, COUNT(*) AS qtde 
			INTO TEMP temp_fcr_pecas2_peca_$login_admin
			FROM tbl_os_item
			JOIN tbl_os_produto USING (os_produto)
			JOIN   temp_fcr_pecas2_os_$login_admin fcr ON tbl_os_produto.os = fcr.os
			GROUP BY tbl_os_item.peca,data_analise;

			CREATE INDEX temp_fcr_pecas2_peca_PECA_$login_admin ON temp_fcr_pecas2_peca_$login_admin(peca); 

			UPDATE temp_fcr_pecas2_os_$login_admin SET contabilizado = TRUE WHERE contabilizado IS NOT TRUE;";
	}else{
		$sql = "INSERT INTO temp_fcr_pecas2_osex_$login_admin (os,data_analise)
			SELECT DISTINCT tbl_os_extra.os , $tipo_data::date AS data_analise
			FROM  tbl_os_extra
			JOIN tbl_extrato USING (extrato)
			JOIN tbl_extrato_extra USING (extrato)
			WHERE tbl_extrato.fabrica = $login_fabrica
			AND $tipo_data BETWEEN '$xdata_inicial' AND '$xdata_final'
			$sql_14;

			INSERT  INTO temp_fcr_pecas2_os_$login_admin (os,data_analise,produto)
			SELECT tbl_os.os , data_analise, tbl_os.produto
			FROM tbl_os 
			JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
			JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
			JOIN temp_fcr_pecas2_osex_$login_admin ON temp_fcr_pecas2_osex_$login_admin.os = tbl_os.os
			WHERE 1=1
			AND tbl_os.excluida IS NOT TRUE
			AND tbl_os.produto = $produto
			AND   $cond_1
			AND   $cond_2
			AND   $cond_3
			AND   $cond_4
			AND   $cond_5 ";
		if  ($login_fabrica == 20 and $pais != 'BR') $sql .= "AND   $cond_6 ";
		$sql .= ";
			INSERT INTO temp_fcr_pecas2_peca_$login_admin (peca,data_analise,qtde)
			SELECT tbl_os_item.peca, TO_CHAR(data_analise,'MM/YY') AS data_analise, COUNT(*) AS qtde 
			FROM  tbl_os_item
			JOIN  tbl_os_produto USING (os_produto)
			JOIN  temp_fcr_pecas2_os_$login_admin fcr ON tbl_os_produto.os = fcr.os
			WHERE contabilizado IS NOT TRUE
			GROUP BY tbl_os_item.peca,data_analise;
			UPDATE temp_fcr_pecas2_os_$login_admin SET contabilizado = TRUE WHERE contabilizado IS NOT TRUE;
			";
	}
	$res = pg_exec($con,$sql);
	echo "<script language='javascript'>document.getElementById('mes_$x').style.background = '#D7FFE1'</script>";
	flush();
}


if($login_fabrica == 20 and $pais !='BR'){
	$sql_peca =" tbl_peca_idioma.descricao AS descricao_espanhol, ";
	$join_pc_idioma="LEFT JOIN tbl_peca_idioma on tbl_peca_idioma.peca = tbl_peca.peca AND tbl_peca_idioma.idioma = 'ES'";
}else{
	$sql_peca=" tbl_peca.descricao, ";
	$join_pc_idioma="";
}

$sql =" 
	SELECT tbl_peca.referencia,
		$sql_peca
		tbl_peca.peca,
		pecas.qtde AS ocorrencia,
		data_analise
	FROM tbl_peca 
	$join_pc_idioma
	JOIN   temp_fcr_pecas2_peca_$login_admin pecas ON tbl_peca.peca = pecas.peca
	ORDER BY $sql_peca data_analise,pecas.qtde " ;
$res = pg_exec($con, $sql);

$peca=0;
$x=0;
$y=0;
$peca_total=0;
$qtde_mes =  array();

if (pg_numrows($res) > 0) {

	echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center'>\n";
	echo "<tr class='Titulo'>\n";
	echo "<td width='300' rowspan='2' >Peça</td>\n";
	echo "<td colspan='12'>Meses</td>\n";
	echo "<td rowspan='2' class='Mes'>Total Trimestre</td>\n";
	echo "</tr><tr class='Titulo'>\n";

	for($x=0;$x<12;$x++){
		$fator = 11 -$x;
		$sql = "SELECT TO_CHAR(('$data_inicial'::DATE - INTERVAL'$fator MONTH')::DATE,'MM/YY');";
		$res2 = pg_exec($con,$sql);
		$mes[$x] = pg_result($res2,0,0);
		echo "<td class='Mes'>$mes[$x]</td>\n";
	}
	echo "</tr>\n";
	flush();

	for ($i=0; $i<pg_numrows($res); $i++){
		$peca = pg_result($res,$i,peca);
		if($login_fabrica == 20 and $pais !='BR'){
			$descricao  = pg_result($res,$i,descricao_espanhol);		
			if(strlen($descricao) == 0){
				$descricao = "<font color = 'red'>Tradução não cadastrada</font>";
			}
		}else{
			$descricao  = pg_result($res,$i,descricao);
		}

		if($peca_anterior <> $peca){
			$qtde_mes[$peca_total][0]  = 0;
			$qtde_mes[$peca_total][1]  = 0;
			$qtde_mes[$peca_total][2]  = 0;
			$qtde_mes[$peca_total][3]  = 0;
			$qtde_mes[$peca_total][4]  = 0;
			$qtde_mes[$peca_total][5]  = 0;
			$qtde_mes[$peca_total][6]  = 0;
			$qtde_mes[$peca_total][7]  = 0;
			$qtde_mes[$peca_total][8]  = 0;
			$qtde_mes[$peca_total][9]  = 0;
			$qtde_mes[$peca_total][10]  = 0;
			$qtde_mes[$peca_total][11]  = 0;
			$qtde_mes[$peca_total][12] = $descricao;
			$x=0;
			$peca_anterior = $peca;
			$peca_total = $peca_total+1;
		}
	}
	flush();
	$peca_anterior='';
	for ($i=0; $i<pg_numrows($res); $i++){

		$referencia   = trim(pg_result($res,$i,referencia));
		$peca         = trim(pg_result($res,$i,peca));
		$data_geracao = trim(pg_result($res,$i,data_analise));
		$qtde         = trim(pg_result($res,$i,ocorrencia));
		if($login_fabrica == 20 and $pais !='BR'){
			$descricao  = pg_result($res,$i,descricao_espanhol);		
			if(strlen($descricao) == 0){
				$descricao = "<font color = 'red'>Tradução não cadastrada</font>";
			}
		}else{
			$descricao  = pg_result($res,$i,descricao);
		}

		if($peca_anterior<>$peca){

		//IMPRIME O VETOR COM OS DOZE MESES E LOGO APÓS PULA LINHA E ESCREVE O NOVO familia
			if($i<>0 AND $peca_anterior<>$peca ){
				
				for($a=0;$a<12;$a++){//imprime os doze meses
					echo "<td bgcolor='$cor' title='".$mes[$a]."'>\n";
					if ($qtde_mes[$y][$a]>0) echo "<font color='#000000'><b>".$qtde_mes[$y][$a];
					else                     echo "<font color='#999999'> ";
					echo "</td>\n";
					$total_ano = $total_ano + $qtde_mes[$y][$a];
					if($a==11) {
						echo "<td bgcolor='$cor' >$total_ano</td>\n";
						echo "</tr>\n";
					}
				
				}
				$y=$y+1;
			}
	
			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';

			echo "<tr class='Conteudo'align='center'>\n";
			echo "<td bgcolor='$cor' width='300' height='25' align='left'>$referencia - $descricao</td>\n";
			$total_ano = 0;
			$x=0; //ZERA OS MESES
			$peca_anterior=$peca;
			flush();
		}

		$x = 0;
		while($data_geracao<>$mes[$x]){ //repete o lup até que o mes seja igual e anda um mes.
			$x=$x+1;
			if($x==12)continue;
		};

		if($data_geracao==$mes[$x]) $qtde_mes[$y][$x] = $qtde;

		$x=$x+1; //após armazenar o valor no mes correspondente anda 1 mes no vetor
		if($i==(pg_numrows($res)-1)){
			for($a=0;$a<12;$a++){ //imprime os doze meses
				echo "<td bgcolor='$cor' title='".$mes[$a]."'>";
				if ($qtde_mes[$y][$a]>0) echo "<font color='#000000'><b>".$qtde_mes[$y][$a];
				else                     echo "<font color='#999999'> ";
				echo "</td>";

				$total_ano = $total_ano + $qtde_mes[$y][$a];
				if($a==11) {
					echo "<td bgcolor='$cor' >$total_ano</td>";
					echo "</tr>";
				}// se for o ultimo mes quebra a linha
			}
		}
	}
	echo "</table>";
}
?>
</TABLE>

</BODY>
</HTML>
