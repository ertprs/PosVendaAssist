<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$msg_erro = "";

switch ($login_fabrica) {
	case 8:
		$data_padrao = "data_fechamento";
		break;
	default:
		$data_padrao = "data_abertura";
		break;
}

$layout_menu = "gerencia";
$title = "Porcenteje de fallas de herramientas por período.";

include "cabecalho.php";

?>

<p>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

a:link.top{
	color:#ffffff;
}
a:visited.top{
	color:#ffffff;
}
a:hover.top{
	color:#ffffff;
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
}

</style>

<table align='center' border='0' cellspacing='2' cellpadding='4'>
<form name='frm_percentual' action='<? echo $PHP_SELF ?>'>
<tr class='menu_top'>
	<td align='center'><font size='2'>Elija el MES</font></td>
	<td align='center'><font size='2'>Elija el AÑO</font></td>
	<td align='center'><font size='2'>Por família</font></td>
	<td align='center'><font size='2'>Por servicio</font></td>
	<td>&nbsp;</td>
</tr>
<tr class='table_line'>
	<td align='center'>
<?
/*--------------------------------------------------------------------------------
selectMesSimples()
Cria ComboBox com meses de 1 a 12
--------------------------------------------------------------------------------*/
function selectMesSimples($selectedMes){
	for($dtMes=1; $dtMes <= 12; $dtMes++){
		$dtMesTrue = ($dtMes < 10) ? "0".$dtMes : $dtMes;
		
		echo "<option value=$dtMesTrue ";
		if ($selectedMes == $dtMesTrue) echo "selected";
		echo ">$dtMesTrue</option>\n";
	}
}
?>
		<select name='mes'>
			<option value=''></option>
<? selectMesSimples($mes); ?>
		</select>
	</td>
	<td align='center'>
<?
/*--------------------------------------------------------------------------------
selectAnoSimples($ant,$pos,$dif,$selectedAno)
// $ant = qtdade de anos retroceder
// $pos = qtdade de anos posteriores
// $dif = ve qdo ano termina
// $selectedAno = ano já setado
Cria ComboBox com Anos
--------------------------------------------------------------------------------*/
function selectAnoSimples($ant,$pos,$dif=0,$selectedAno)
{
	$startAno = date("Y"); // ano atual
	for($dtAno = $startAno - $ant; $dtAno <= $startAno + ($pos - $dif); $dtAno++){
		echo "<option value=$dtAno ";
		if ($selectedAno == $dtAno) echo "selected";
		echo ">$dtAno</option>\n";
	}
}
?>
		<select name='ano'>
			<option value=''></option>
<? selectAnoSimples(1,0,'',$ano) ?>
		</select>
	</td>
	<td align=center><INPUT TYPE="checkbox" NAME="familia" value='t' <? if($familia == 't') echo " checked"?>></td>
	<td align=center><INPUT TYPE="checkbox" NAME="posto"   value='t' <? if($posto == 't')   echo " checked"?>></td>
	<td><img src='imagens_admin/btn_confirmar.gif' border=0 onclick='javascript: submit();' style='cursor:pointer'></td>
</tr>
</form>
</table>

<br>

<?
if (strlen($mes) > 0 AND strlen($ano) > 0){

	$data_ano = "$ano-01-01";
	$data     = "$ano-$mes-01";

	$sql = "SELECT fn_dias_mes('$data',0)";
	$resX = pg_exec($con,$sql);
	$data_inicial = pg_result($resX,0,0);

	$sql = "SELECT fn_dias_mes('$data',1)";
	$resX = pg_exec($con,$sql);
	$data_final = pg_result($resX,0,0);

	$sql = "SELECT fn_dias_mes('$data_ano',0)";
	$resX = pg_exec($con,$sql);
	$data_inicial_ano = pg_result($resX,0,0);

	//*********************************************************************
	// INICIO DO XLS
	if (strlen($posto) > 0){
		$data = date ("dmY-hsi");
		
		echo `rm /var/www/assist/www/admin/xls/defeitos_produtos-$data-$login_fabrica.xls`;
		
		$fp = fopen ("/tmp/assist/defeitos_produtos-$data-$login_fabrica.html","w");
		
		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>DEFEITOS POR PRODUTOS - $data");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");
	}
	//*********************************************************************

	// se não é por FAMILIA
	if (strlen($familia) == 0){

		if(strlen($posto) == 0){
			# ----------- 1 - Familia == 0  :: Posto == 0  -------------- #
			$sql = "SELECT	tbl_produto.produto       ,
							tbl_produto.descricao     ,
							tbl_produto.nome_comercial,
							tbl_produto.referencia    ,
							COUNT(*) AS conta
					FROM	tbl_os
					JOIN	tbl_produto    ON tbl_produto.produto  = tbl_os.produto
					JOIN	tbl_linha      ON tbl_linha.linha      = tbl_produto.linha
					JOIN	tbl_fabrica    ON tbl_fabrica.fabrica  = tbl_linha.fabrica 
					JOIN	tbl_posto      ON tbl_posto.posto      = tbl_os.posto AND tbl_posto.pais = '$login_pais'
					WHERE	tbl_linha.fabrica = $login_fabrica
					AND		tbl_os.$data_padrao BETWEEN '$data_inicial_ano' AND '$data_final'
					GROUP BY tbl_produto.descricao    ,
							tbl_produto.nome_comercial,
							tbl_produto.referencia    ,
							tbl_produto.produto;";
			#$res = pg_exec($con,$sql);
			flush();

			$sql = "SELECT os,to_char(tbl_os.$data_padrao,'MM') as mes,produto,defeito_constatado
					INTO TEMP rpd_0_$login_admin
					FROM tbl_os 
					JOIN tbl_posto      ON tbl_posto.posto      = tbl_os.posto AND tbl_posto.pais = '$login_pais'
					WHERE tbl_os.fabrica = $login_fabrica
					AND   tbl_posto.pais = '$login_pais'
					AND   tbl_os.$data_padrao BETWEEN '$data_inicial_ano' AND '$data_final';

					CREATE INDEX rpd_0_os_$login_admin      ON rpd_0_$login_admin(os);
					CREATE INDEX rpd_0_dc_$login_admin      ON rpd_0_$login_admin(defeito_constatado);
					CREATE INDEX rpd_0_produto_$login_admin ON rpd_0_$login_admin(produto);";
			$res = pg_exec($con,$sql);

			$sql = "SELECT  tbl_produto.produto       ,
						tbl_produto.descricao     ,
						tbl_produto.nome_comercial,
						tbl_produto.referencia    ,
						COUNT(*) AS conta
					FROM    rpd_0_$login_admin OS
					JOIN    tbl_produto            ON tbl_produto.produto  = OS.produto
					GROUP BY tbl_produto.descricao    ,
						 tbl_produto.nome_comercial,
						 tbl_produto.referencia    ,
						 tbl_produto.produto
					ORDER BY tbl_produto.referencia;";
			$res = pg_exec($con,$sql);
			flush();
			
			if (pg_numrows($res) == 0) {
				echo "<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>";
				echo "<tr class='table_line'>";
				echo "<td align='center'><font size='2'>No hay defectos por producto en este período</font></td>";
				echo "</tr>";
				echo "</table>";
			}else{
				echo "<table align='center' border='0' cellspacing='1' cellpadding='2'>";

				### monta linha de nome dos produtos
				echo "<tr class='menu_top'>\n";
				echo "<td>#</td>";
				for ($i=0; $i<pg_numrows($res); $i++){
					echo "<td><b><acronym title='".pg_result($res,$i,descricao)."'>";
					if($login_fabrica<>20){
					if(pg_result($res,$i,nome_comercial)) echo pg_result($res,$i,nome_comercial); else echo pg_result($res,$i,referencia);
					}else echo pg_result($res,$i,referencia);
					echo "</acronym></b></td>\n";
					
					$produto = pg_result($res,$i,produto);
					
					# ------------------------------------ 2 ------------------------------------ #
					$sql = "SELECT	COUNT(*) AS contaano
							FROM	tbl_os
							JOIN	tbl_produto    ON tbl_produto.produto  = tbl_os.produto
							JOIN	tbl_linha      USING(linha)
							JOIN	tbl_posto      ON tbl_posto.posto      = tbl_os.posto AND tbl_posto.pais = '$login_pais'
							WHERE	tbl_linha.fabrica   = $login_fabrica
							AND		tbl_produto.produto = $produto
							AND		tbl_os.$data_padrao BETWEEN '$data_inicial' AND '$data_final';";
					//echo $sql; exit;

					$sql = "SELECT COUNT(*) AS contaano
						FROM    rpd_0_$login_admin  OS
						JOIN    tbl_produto            ON tbl_produto.produto  = OS.produto
						WHERE   tbl_produto.produto = $produto
						AND     OS.mes = TO_CHAR ('$data_inicial'::date,'MM');";

					$res2 = pg_exec($con,$sql);
					$contaano[$i] = pg_result($res2,0,0);
				}
				echo "</tr>\n";

				### MONTA LINHA EM BRANCO, PQ GARANTIA
				echo "<tr class='table_line' BGCOLOR='#F7F7F7'>\n";
				echo "<td class='menu_top'>PQ GARANTIA</td>";
				for ($i=0; $i<pg_numrows($res); $i++){
					echo "<td>&nbsp;</td>\n";
				}
				echo "</tr>\n";

				### MONTA LINHA COM TOTAL DE OS DO ANO
				$acumula_ano = 0;
				echo "<tr class='table_line' bgcolor='#F1F4FA'>\n";
				echo "<td class='menu_top'>ATEND. ANO</td>";
				for ($i=0; $i<pg_numrows($res); $i++){
					echo "<td align='right' style='padding-right:5px;'>".pg_result($res,$i,conta)."</td>\n";
					$acumula_ano += pg_result($res,$i,conta);
				}
				echo "</tr>\n";

				### MONTA LINHA COM TOTAL DE OS DO MÊS
				$acumula_mes = 0;
				echo "<tr class='table_line' BGCOLOR='#F7F7F7'>\n";
				echo "<td class='menu_top'>ATEND. MES</td>";
				for ($i=0; $i<count($contaano); $i++){
					echo "<td align='right' style='padding-right:5px;'>".$contaano[$i]."</td>\n";
					$acumula_mes += $contaano[$i];
				}
				echo "</tr>\n";

				### % NO ANO
				echo "<tr class='table_line'BGCOLOR='#F7F7F7'>\n";
				echo "<td class='menu_top' bgcolor='#F1F4FA'>% NO ANO</td>";
				for ($i=0; $i<pg_numrows($res); $i++){
					$porc_ano = (pg_result($res,$i,conta) / $acumula_ano) * 100;
					echo "<td align='right' style='padding-right:5px;'>".round($porc_ano,2)."</td>\n";
				}
				echo "</tr>\n";

				### % NO MÊS
				echo "<tr class='table_line' bgcolor='#F1F4FA'>\n";
				echo "<td class='menu_top'>% NO MES</td>";
				for ($i=0; $i<pg_numrows($res); $i++){
					$porc_mes = ($contaano[$i] / $acumula_mes) * 100;
					echo "<td align='right' style='padding-right:5px;'>".round($porc_mes,2)."</td>\n";
				}
				echo "</tr>\n";

				### % MÉDIA
				echo "<tr class='table_line' bgcolor='#F1F4FA'>\n";
				echo "<td class='menu_top'>% MEDIA</td>";
				for ($i=0; $i<pg_numrows($res); $i++){
					echo "<td>&nbsp;</td>\n";
				}
				echo "</tr>\n";

				echo "</table>";
			}

		}else{
			# ------------------------------------ 2 - Familia == 0  :: Posto > 0  ------------------------------------ #
			$sql = "SELECT	distinct
							tbl_posto.posto  ,
							tbl_posto.nome   ,
							tbl_posto.estado
					FROM	tbl_posto
					JOIN	tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND   tbl_posto.pais = '$login_pais'
					JOIN	tbl_os            ON tbl_os.posto            = tbl_posto.posto
					JOIN	tbl_produto       ON tbl_produto.produto     = tbl_os.produto
					JOIN	tbl_linha   ON tbl_linha.linha     = tbl_produto.linha
					JOIN	tbl_fabrica ON tbl_fabrica.fabrica = tbl_linha.fabrica
					WHERE	tbl_os.$data_padrao BETWEEN '$data_inicial' AND '$data_final'
					AND		tbl_posto_fabrica.fabrica = $login_fabrica
					AND		tbl_linha.fabrica         = $login_fabrica
					AND		tbl_produto.ativo         = 't'
					AND     tbl_posto.pais            = '$login_pais' 
					ORDER BY tbl_posto.nome;";
			$sql = "
				SELECT os,to_char(tbl_os.$data_padrao,'MM') as mes,produto,tbl_os.posto,defeito_constatado
				INTO TEMP rpd_0_$login_admin
				FROM tbl_os 
				JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto AND tbl_posto.pais = '$login_pais'
				WHERE tbl_os.fabrica = $login_fabrica
				AND   tbl_posto.pais = '$login_pais'
				AND   tbl_os.$data_padrao BETWEEN '$data_inicial' AND '$data_final';

				CREATE INDEX rpd_0_os_$login_admin      ON rpd_0_$login_admin(os);
				CREATE INDEX rpd_0_dc_$login_admin      ON rpd_0_$login_admin(defeito_constatado);
				CREATE INDEX rpd_0_produto_$login_admin ON rpd_0_$login_admin(produto);
				CREATE INDEX rpd_0_posto_$login_admin   ON rpd_0_$login_admin(posto);

				SELECT  DISTINCT
					tbl_posto.posto  ,
					tbl_posto.nome   ,
					tbl_posto.estado
				FROM    tbl_posto
				JOIN    tbl_posto_fabrica         ON tbl_posto_fabrica.posto = tbl_posto.posto
				JOIN    rpd_0_$login_admin tbl_os ON tbl_os.posto            = tbl_posto.posto
				JOIN    tbl_produto               ON tbl_produto.produto     = tbl_os.produto
				JOIN    tbl_linha                 ON tbl_linha.linha         = tbl_produto.linha
				JOIN    tbl_fabrica               ON tbl_fabrica.fabrica    = tbl_linha.fabrica
				WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
				AND     tbl_linha.fabrica         = $login_fabrica
				AND     tbl_posto.pais            = '$login_pais'
				AND     tbl_produto.ativo         = 't'
				ORDER BY tbl_posto.nome;";
			$resX = pg_exec($con,$sql);
			
			if (pg_numrows($resX) > 0) {
				echo "<table align='center' border='0' cellspacing='1' cellpadding='2'>\n";
				### monta linha de nome dos produtos
				echo "<tr class='menu_top'>\n";
				echo "<td>Servicio</td>\n";
				echo "<td>Provincia</td>\n";
				
				$sql = "SELECT	tbl_produto.produto       ,
								tbl_produto.descricao     ,
								tbl_produto.nome_comercial,
								tbl_produto.referencia
						FROM	tbl_produto
						JOIN	tbl_linha   ON tbl_linha.linha     = tbl_produto.linha
						JOIN	tbl_fabrica ON tbl_fabrica.fabrica = tbl_linha.fabrica
						WHERE	tbl_linha.fabrica = $login_fabrica
						AND		tbl_produto.ativo = 't' ";
				$res = pg_exec($con,$sql);
				
				for ($i=0; $i<pg_numrows($res); $i++){
					echo "<td><b><acronym title='".pg_result($res,$i,descricao)."'>";
					if(strlen(pg_result($res,$i,nome_comercial)) > 0)
						echo pg_result($res,$i,nome_comercial);
					else
						echo pg_result($res,$i,referencia);
					echo "</acronym></b></td>\n";
					$produto[$i] = pg_result($res,$i,produto);
				}
				echo "</tr>\n";

				//*********************************************************************
				// XLS
				fputs ($fp,"<table width='100%' align='left' border='1' cellpadding='2' cellspacing='2'>");
				fputs ($fp,"<tr>");
				fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>POSTO</td>");
				fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>UF</td>");

				for ($i=0; $i<pg_numrows($res); $i++){
					fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>".pg_result($res,$i,descricao)."</td>");
				}

				fputs ($fp,"</tr>");
				// XLS
				//*********************************************************************

				for ($k=0; $k<pg_numrows($resX); $k++){

					$cor = ($k%2 == 0) ? '#ffffff' : '#fbfbfb';

					# ------------------------------------ 2 ------------------------------------ #
					$posto  = pg_result($resX,$k,posto);
					$nome   = pg_result($resX,$k,nome);
					$estado = pg_result($resX,$k,estado);


					### MONTA LINHA COM TOTAL DE OS DO Mes
					echo "<tr class='table_line'>\n";
					echo "	<td bgcolor='$cor' nowrap>$nome</td>\n";
					echo "	<td bgcolor='$cor'>$estado</td>\n";

					//*********************************************************************
					// XLS
					fputs ($fp,"<tr>");
					fputs ($fp,"<td bgcolor='$cor' align='left' nowrap>$nome</td>");
					fputs ($fp,"<td bgcolor='$cor' align='center'>$estado</td>");
					// XLS
					//*********************************************************************

					for($x=0; $x<pg_numrows($res); $x++){
						$sql = "SELECT	COUNT(*) AS conta
								FROM	tbl_os
								JOIN	tbl_posto      ON tbl_posto.posto      = tbl_os.posto AND tbl_posto.pais = '$login_pais'
								JOIN	tbl_produto    ON tbl_produto.produto  = tbl_os.produto
								JOIN	tbl_linha      ON tbl_linha.linha      = tbl_produto.linha
								JOIN	tbl_fabrica    ON tbl_fabrica.fabrica  = tbl_linha.fabrica
								WHERE	tbl_os.$data_padrao BETWEEN '$data_inicial' AND '$data_final'
								AND		tbl_linha.fabrica   = $login_fabrica
								AND		tbl_os.posto        = $posto
								AND		tbl_produto.produto = $produto[$x]";

						$sql = "SELECT  COUNT(*) AS conta
							FROM    rpd_0_$login_admin tbl_os
							JOIN    tbl_produto    ON tbl_produto.produto  = tbl_os.produto
							JOIN    tbl_linha      ON tbl_linha.linha      = tbl_produto.linha
							JOIN    tbl_fabrica    ON tbl_fabrica.fabrica  = tbl_linha.fabrica
							WHERE   tbl_linha.fabrica   = $login_fabrica
							AND     tbl_os.posto        = $posto
							AND     tbl_produto.produto = $produto[$x]";
						$resMes = pg_exec($con,$sql);
						echo "<td  bgcolor='$cor' align='right' style='padding-right:5px;'>".pg_result($resMes,0,conta)."</td>\n";

						//*********************************************************************
						// XLS
						fputs ($fp,"<td bgcolor='$cor' align='center'>".pg_result($resMes,0,conta)."</td>");
						//*********************************************************************
					}
					echo "</tr>\n";
					fputs ($fp,"</tr>"); // XLS
				}

				echo "</table>\n";
			}
			flush();
		}
	}else{
		// por FAMILIA

		if (strlen($posto) == 0){
				# ------------------------------------ 3 - Familia > 0  :: Posto == 0  ------------------------------------ #
			$sql = "SELECT	tbl_familia.familia   ,
							tbl_familia_idioma.descricao ,
							COUNT(*) AS conta     
					FROM	tbl_os
					JOIN	tbl_posto      ON tbl_posto.posto      = tbl_os.posto AND tbl_posto.pais = '$login_pais'
					JOIN	tbl_produto ON tbl_produto.produto = tbl_os.produto 
					JOIN	tbl_linha   ON tbl_linha.linha     = tbl_produto.linha 
					JOIN	tbl_fabrica ON tbl_fabrica.fabrica = tbl_linha.fabrica
					JOIN	tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = tbl_fabrica.fabrica  
					JOIN    tbl_familia_idioma ON tbl_familia.familia = tbl_familia_idioma.familia
					WHERE	tbl_linha.fabrica = $login_fabrica
					AND		tbl_os.$data_padrao BETWEEN '$data_inicial_ano' AND '$data_final'
					GROUP BY tbl_familia.familia, tbl_familia_idioma.descricao ORDER BY tbl_familia.familia;";
			$sql = "
				SELECT os,to_char(tbl_os.$data_padrao,'MM') as mes,produto,defeito_constatado
				INTO TEMP rpd_0_$login_admin
				FROM tbl_os
				JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto AND tbl_posto.pais = '$login_pais'
				WHERE tbl_os.fabrica = $login_fabrica
				AND   tbl_os.$data_padrao BETWEEN '$data_inicial_ano' AND '$data_final';

				CREATE INDEX rpd_0_os_$login_admin      ON rpd_0_$login_admin(os);
				CREATE INDEX rpd_0_dc_$login_admin      ON rpd_0_$login_admin(defeito_constatado);
				CREATE INDEX rpd_0_produto_$login_admin ON rpd_0_$login_admin(produto);

				SELECT  F.familia   ,
						FI.descricao ,
						COUNT(*) AS conta
				FROM    rpd_0_$login_admin X
				JOIN    tbl_produto           P  ON P.produto  = X.produto
				JOIN    tbl_familia           F  ON F.familia  = P.familia
				JOIN    tbl_familia_idioma    FI ON F.familia  = FI.familia
				GROUP BY F.familia, FI.descricao
				ORDER BY FI.descricao;";

			$res = pg_exec($con,$sql);

			if (pg_numrows($res) == 0) {
				echo "<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>";
				echo "<tr class='table_line'>";
				echo "<td align='center'><font size='2'>Não existem defeitos por produtos neste período</font></td>";
				echo "</tr>";
				echo "</table>";
				flush();
			}else{
				echo "<table align='center' border='0' cellspacing='1' cellpadding='2'>";

				### monta linha de nome dos produtos
				echo "<tr class='menu_top'>\n";
				echo "<td>#</td>";
				
				for ($i=0; $i<pg_numrows($res); $i++){
					$familia = trim(pg_result($res,$i,familia));
					
					echo "<td><b><acronym title='".pg_result($res,$i,descricao)."'>";
					echo pg_result($res,$i,descricao);
					echo "</acronym></b></td>\n";

					# ------------------------------------ 4 ------------------------------------ #
					$sql = "SELECT	COUNT(*) AS contaano
							FROM	tbl_os
							JOIN	tbl_posto      ON tbl_posto.posto      = tbl_os.posto 
							JOIN	tbl_produto ON tbl_produto.produto = tbl_os.produto
							JOIN	tbl_linha   ON tbl_linha.linha     = tbl_produto.linha
							JOIN	tbl_fabrica ON tbl_fabrica.fabrica = tbl_linha.fabrica
							JOIN	tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = tbl_fabrica.fabrica
							WHERE	tbl_linha.fabrica   = $login_fabrica
							AND		tbl_familia.familia = $familia
							AND     tbl_posto.pais = '$login_pais'
							AND     tbl_os.fabrica = $login_fabrica
							AND		tbl_os.$data_padrao BETWEEN '$data_inicial' AND '$data_final';";
					$sql = "SELECT  COUNT(*) AS contaano
						FROM    rpd_0_$login_admin X
						JOIN    tbl_produto P ON P.produto = X.produto
						JOIN    tbl_familia F ON F.familia = P.familia
						WHERE   F.familia = $familia
						AND     X.mes     = TO_CHAR ('$data_inicial'::date,'MM');";
					$res2 = pg_exec($con,$sql);
					$contaano[$i] = pg_result($res2,0,0);
				}
				echo "</tr>\n";

				### MONTA LINHA EM BRANCO, PQ GARANTIA
				echo "<tr class='table_line' BGCOLOR='#F7F7F7'>\n";
				echo "<td class='menu_top'>PQ GARANTIA</td>";
				for ($i=0; $i<pg_numrows($res); $i++){
					echo "<td>&nbsp;</td>\n";
				}
				echo "</tr>\n";

				### MONTA LINHA COM TOTAL DE OS DO MES
				echo "<tr class='table_line' bgcolor='#F1F4FA'>\n";
				echo "<td class='menu_top'>ATEND. ANO</td>";
				for ($i=0; $i<pg_numrows($res); $i++){
					echo "<td align='right' style='padding-right:5px;'>".pg_result($res,$i,conta)."</td>\n";
				}
				echo "</tr>\n";

				### MONTA LINHA COM TOTAL DE OS DO ANO
				echo "<tr class='table_line' BGCOLOR='#F7F7F7'>\n";
				echo "<td class='menu_top'>ATEND. MES</td>";
				for ($i=0; $i<count($contaano); $i++){
					echo "<td align='right' style='padding-right:5px;'>".$contaano[$i]."</td>\n";
				}
				echo "</tr>\n";

				### % NO MÊS
				echo "<tr class='table_line' bgcolor='#F1F4FA'>\n";
				echo "<td class='menu_top'>% NO MES</td>";
				for ($i=0; $i<pg_numrows($res); $i++){
					echo "<td>&nbsp; </td>\n";
				}
				echo "</tr>\n";

				### % NO ANO
				echo "<tr class='table_line'BGCOLOR='#F7F7F7'>\n";
				echo "<td class='menu_top' bgcolor='#F1F4FA'>% NO ANO</td>";
				for ($i=0; $i<pg_numrows($res); $i++){
					echo "<td>&nbsp;</td>\n";
				}
				echo "</tr>\n";

				### % MÉDIA
				echo "<tr class='table_line' bgcolor='#F1F4FA'>\n";
				echo "<td class='menu_top'>% MEDIA</td>";
				for ($i=0; $i<pg_numrows($res); $i++){
					echo "<td>&nbsp;</td>\n";
				}
				echo "</tr>\n";

				echo "</table>";
				flush();
			}
		}else{
			# ------------------------------------ 4 - Familia > 0  :: Posto > 0  ------------------------------------ #
			$sql = "SELECT	distinct
							tbl_posto.posto  ,
							tbl_posto.nome   ,
							tbl_posto.estado
					FROM	tbl_posto
					JOIN	tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					JOIN	tbl_os            ON tbl_os.posto            = tbl_posto.posto
					JOIN	tbl_produto       ON tbl_produto.produto     = tbl_os.produto
					JOIN	tbl_linha   ON tbl_linha.linha     = tbl_produto.linha
					JOIN	tbl_fabrica ON tbl_fabrica.fabrica = tbl_linha.fabrica
					WHERE	tbl_os.$data_padrao BETWEEN '$data_inicial' AND '$data_final'
					AND		tbl_posto_fabrica.fabrica = $login_fabrica
					AND		tbl_linha.fabrica         = $login_fabrica
					AND		tbl_produto.ativo         = 't'
					AND     tbl_posto.pais            = '$login_pais'
					ORDER BY tbl_posto.nome;";

			$sql = "
				SELECT os,to_char(tbl_os.$data_padrao,'MM') as mes,produto,defeito_constatado,tbl_os.posto
				INTO TEMP rpd_0_$login_admin
				FROM tbl_os
				JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto AND tbl_posto.pais = '$login_pais'
				WHERE tbl_os.fabrica = $login_fabrica
				AND   tbl_os.$data_padrao BETWEEN '$data_inicial_ano' AND '$data_final';

				CREATE INDEX rpd_0_os_$login_admin      ON rpd_0_$login_admin(os);
				CREATE INDEX rpd_0_dc_$login_admin      ON rpd_0_$login_admin(defeito_constatado);
				CREATE INDEX rpd_0_produto_$login_admin ON rpd_0_$login_admin(produto);
				CREATE INDEX rpd_0_posto_$login_admin   ON rpd_0_$login_admin(posto);

				SELECT	distinct
					A.posto  ,
					A.nome   ,
					A.estado
				FROM    tbl_posto          A
				JOIN    tbl_posto_fabrica  F ON F.posto   = A.posto AND F.fabrica = $login_fabrica
				JOIN    rpd_0_$login_admin X ON X.posto   = A.posto
				JOIN    tbl_produto        P ON P.produto = X.produto
				WHERE   X.mes    = TO_CHAR ('$data_inicial'::date,'MM')
				AND     P.ativo  IS TRUE
				ORDER BY A.nome;";
			$resX = pg_exec($con,$sql);
			if (pg_numrows($resX) > 0) {
				
				echo "<table align='center' border='0' cellspacing='1' cellpadding='2'>";

				### monta linha de nome dos produtos
				echo "<tr class='menu_top'>\n";
				echo "<td>Posto</td>";
				echo "<td>UF</td>";

				$sql = "SELECT	tbl_familia.familia  ,
								tbl_familia_idioma.descricao
						FROM	tbl_os
						JOIN	tbl_posto      ON tbl_posto.posto      = tbl_os.posto AND tbl_posto.pais = '$login_pais'
						JOIN	tbl_produto  ON tbl_produto.produto = tbl_os.produto 
						JOIN	tbl_linha    ON tbl_linha.linha     = tbl_produto.linha 
						JOIN	tbl_fabrica  ON tbl_fabrica.fabrica = tbl_linha.fabrica 
						JOIN	tbl_familia  ON tbl_familia.familia = tbl_produto.familia 
											AND tbl_familia.fabrica = tbl_fabrica.fabrica
						JOIN	tbl_familia_idioma  ON tbl_familia.familia = tbl_familia_idioma.familia  
						WHERE	tbl_linha.fabrica = $login_fabrica
						AND		tbl_os.$data_padrao BETWEEN '$data_inicial_ano' AND '$data_final'
						GROUP BY tbl_familia.familia, tbl_familia_idioma.descricao
						ORDER BY tbl_familia.familia;";
				$sql = "SELECT  F.familia  ,
						F.descricao
					FROM    rpd_0_$login_admin X
					JOIN    tbl_produto        P ON P.produto = X.produto 
					JOIN    tbl_familia        F ON F.familia = P.familia 
					GROUP BY F.familia, F.descricao 
					ORDER BY F.descricao;";
				$res = pg_exec($con,$sql);
				
				//echo $sql; exit;
				
				for ($i=0; $i<pg_numrows($res); $i++){
					$familias[$i] = trim(pg_result($res,$i,familia));
					
					echo "<td><b><acronym title='".pg_result($res,$i,descricao)."'>";
					echo pg_result($res,$i,descricao);
					echo "</acronym></b></td>\n";
				}
				echo "</tr>\n";

				//*********************************************************************
				// XLS
				fputs ($fp,"<table width='100%' align='left' border='1' cellpadding='2' cellspacing='2'>");
				fputs ($fp,"<tr>");
				fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>POSTO</td>");
				fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>UF</td>");

				for ($i=0; $i<pg_numrows($res); $i++){
					fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>".pg_result($res,$i,descricao)."</td>");
				}

				fputs ($fp,"</tr>");
				// XLS
				//*********************************************************************

				for ($k=0; $k<pg_numrows($resX); $k++){

					$cor = ($k%2 == 0) ? '#ffffff' : '#fafafa';

					# ------------------------------------ 2 ------------------------------------ #
					$posto  = pg_result($resX,$k,posto);
					$nome   = pg_result($resX,$k,nome);
					$estado = pg_result($resX,$k,estado);
				
/*					echo "<tr class='table_line'>\n";
					echo "	<td class='menu_top'>ANO</td>";
					echo "	<td rowspan=2 bgcolor='$cor' nowrap>$nome</td>";
					echo "	<td rowspan=2 bgcolor='$cor'>$estado</td>";

					### MONTA LINHA COM TOTAL DE OS DO Ano
					for($x=0; $x<pg_numrows($res); $x++){

						$sql = "SELECT	COUNT(*) AS contaano
								FROM	tbl_os
								JOIN	tbl_posto    ON tbl_posto.posto     = tbl_os.posto
								JOIN	tbl_produto  ON tbl_produto.produto = tbl_os.produto
								JOIN	tbl_linha    ON tbl_linha.linha     = tbl_produto.linha
								JOIN	tbl_fabrica  ON tbl_fabrica.fabrica = tbl_linha.fabrica
								JOIN	tbl_familia  ON tbl_familia.familia = tbl_produto.familia 
													AND tbl_familia.fabrica = tbl_fabrica.fabrica
								WHERE	tbl_linha.fabrica   = $login_fabrica
								AND		tbl_os.data_abertura BETWEEN '$data_inicial_ano' AND '$data_final'
								AND		tbl_os.posto        = $posto
								AND		tbl_familia.familia = $familias[$x]";
						$resAno = pg_exec($con,$sql);
						echo "<td  bgcolor='$cor' align='right' style='padding-right:5px;'>".pg_result($resAno,0,contaAno)."</td>\n";
					}
					echo "</tr>\n";
*/

					//*********************************************************************
					// XLS
					fputs ($fp,"<tr>");
					fputs ($fp,"<td bgcolor='$cor' align='left' nowrap>$nome</td>");
					fputs ($fp,"<td bgcolor='$cor' align='center'>$estado</td>");
					// XLS
					//*********************************************************************

					### MONTA LINHA COM TOTAL DE OS DO Mes
					echo "<tr class='table_line'>\n";
					echo "	<td bgcolor='$cor' nowrap>$nome</td>";
					echo "	<td bgcolor='$cor'>$estado</td>";
					for($x=0; $x<pg_numrows($res); $x++){
						$sql = "SELECT	COUNT(*) AS conta
								FROM	tbl_os
								JOIN	tbl_posto      ON tbl_posto.posto      = tbl_os.posto AND tbl_posto.pais = '$login_pais'
								JOIN	tbl_produto  ON tbl_produto.produto = tbl_os.produto
								JOIN	tbl_linha    ON tbl_linha.linha     = tbl_produto.linha
								JOIN	tbl_fabrica  ON tbl_fabrica.fabrica = tbl_linha.fabrica
								JOIN	tbl_familia  ON tbl_familia.familia = tbl_produto.familia 
													AND tbl_familia.fabrica = tbl_fabrica.fabrica
								WHERE	tbl_linha.fabrica   = $login_fabrica
								AND		tbl_os.$data_padrao BETWEEN '$data_inicial' AND '$data_final'
								AND		tbl_os.posto        = $posto
								AND		tbl_familia.familia = $familias[$x]";
						$sql = "SELECT  COUNT(*) AS conta
							FROM    rpd_0_$login_admin X
							JOIN    tbl_posto          A ON A.posto   = X.posto
							JOIN    tbl_produto        P ON P.produto = X.produto
							JOIN    tbl_familia        F ON F.familia = P.familia 
							WHERE   X.mes     = TO_CHAR ('$data_inicial'::date,'MM')
							AND     X.posto   = $posto
							AND     F.familia = $familias[$x]";
						//echo "sql: $sql";
						$resMes = pg_exec($con,$sql);
						echo "<td  bgcolor='$cor' align='right' style='padding-right:5px;'>".pg_result($resMes,0,conta)."</td>\n";

						//*********************************************************************
						// XLS
						fputs ($fp,"<td bgcolor='$cor' align='center'>".pg_result($resMes,0,conta)."</td>");
						//*********************************************************************
					}
					echo "</tr>\n";
					fputs ($fp,"</tr>"); // XLS
				}
				echo "</table>";
				flush();
			}
		}
	}

//////////////////////////////////////
// xls
//////////////////////////////////////
	if (strlen($posto) > 0){ // só exibe XLS para relatorios com exibicao dos postos
		fputs ($fp,"</table>");
		
		fputs ($fp,"</body>");
		fputs ($fp,"</html>");
		fclose ($fp);
		
		echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /var/www/assist/www/admin/xls/defeitos_produtos-$data-$login_fabrica.xls /tmp/assist/defeitos_produtos-$data-$login_fabrica.html`;
		
		echo "<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo "<tr>";
		echo "<td align='center'></td>";

//		echo "<td align='center'><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Haga un click para hacer el </font><a href='xls/defeitos_produtos-$data-$login_fabrica.xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>dowload del archivo en EXCEL.</font></a></td>";
		echo "</tr>";
		echo "</table>";
		
		echo `rm /tmp/assist/defeitos_produtos-$data-$login_fabrica.html`;
	}
//////////////////////////////////////
// xls
//////////////////////////////////////

	echo "<br><br>";

	// cinco maiores defeitos
	echo "<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>";
	echo "<tr class='table_line'>";
	echo "<td align='center' colspan=5>CINCO MAYORES DEFECTOS ( CONVENCIONAIS)</td>";
	echo "</tr>";

	# ------------------------------------ 5 ------------------------------------ #
	$sql = "SELECT	tbl_defeito_constatado_idioma.descricao,
					COUNT(*) AS defeito
			FROM	tbl_os
			JOIN	tbl_posto      ON tbl_posto.posto      = tbl_os.posto AND tbl_posto.pais = '$login_pais'
			JOIN	tbl_os_produto        ON tbl_os_produto.os = tbl_os.os
			JOIN	tbl_produto           ON tbl_produto.produto = tbl_os_produto.produto
			JOIN	tbl_linha             USING(linha)
			JOIN	tbl_defeito_constatado_idioma ON tbl_defeito_constatado_idioma.defeito_constatado = tbl_os.defeito_constatado
			WHERE	tbl_linha.fabrica = $login_fabrica
			AND		tbl_os.$data_padrao BETWEEN '$data_inicial' AND '$data_final'
			GROUP BY tbl_os.defeito_constatado,
					tbl_defeito_constatado_idioma.descricao
			ORDER BY defeito DESC, tbl_defeito_constatado_idioma.descricao ASC LIMIT 5";
	$res2 = pg_exec($con,$sql);

	echo "<tr class='menu_top'>";
	for ($i=0; $i<pg_numrows($res2); $i++){
		echo "<td align='center'>".pg_result($res2,$i,0)."</td>";
	}
	echo "</tr>";

	echo "<tr class='table_line'>";
	for ($i=0; $i<pg_numrows($res2); $i++){
		echo "<td align='center'>".pg_result($res2,$i,1)."</td>";
	}
	echo "</tr>";

	echo "</table>";

	echo "<br><br>";

	// cinco maiores pecas com defeito
	echo "<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>";
	echo "<tr class='table_line'>";
	echo "<td align='center' colspan=5>CINCO PIEZAS QUE MÁS OCURRIRAN DFECTOS</td>";
	echo "</tr>";

	# ------------------------------------ 6 ------------------------------------ #
	$sql = "SELECT	tbl_peca.peca,
					tbl_peca.descricao,
					COUNT(*) AS qtde  
			FROM	tbl_peca
			JOIN	tbl_os_item           USING (peca)
			JOIN	tbl_os_produto        USING (os_produto)
			JOIN	tbl_os                USING (os)
			JOIN	tbl_posto             ON tbl_posto.posto       = tbl_os.posto 
			WHERE   tbl_os.fabrica = $login_fabrica
			AND     tbl_posto.pais = '$login_pais'
			AND		tbl_os.$data_padrao BETWEEN '$data_inicial' AND '$data_final'
			GROUP BY tbl_peca.peca, tbl_peca.descricao
			ORDER BY qtde DESC, tbl_peca.descricao ASC LIMIT 5";

	$sql = "SELECT	P.peca,P.descricao,
			COUNT(*) AS qtde  
		FROM	tbl_peca           P
		JOIN	tbl_os_item        I   ON I.peca       = P.peca
		JOIN	tbl_os_produto     O   ON O.os_produto = I.os_produto
		JOIN    rpd_0_$login_admin X   ON X.os         = O.os
		WHERE	X.mes     = TO_CHAR ('$data_inicial'::date,'MM')
		GROUP BY P.peca, P.descricao
		ORDER BY qtde DESC, P.descricao ASC 
		LIMIT 5";

	$res2 = pg_exec($con,$sql);

	echo "<tr class='menu_top'>";
	for ($i=0; $i<pg_numrows($res2); $i++){

		$peca       = @pg_result($res2,$i,peca);
		$descricao  = trim(@pg_result($res2,$i,descricao));

		$sql_idioma = "SELECT * FROM tbl_peca_idioma WHERE peca = $peca AND upper(idioma) = 'ES'";
		$res_idioma = @pg_exec($con,$sql_idioma);
		if (@pg_numrows($res_idioma) >0) {
			$descricao  = trim(@pg_result($res_idioma,0,descricao));
		}

		echo "<td align='center'>".$descricao."</td>";
	}
	echo "</tr>";

	echo "<tr class='table_line'>";
	for ($i=0; $i<pg_numrows($res2); $i++){
		echo "<td align='center'>".pg_result($res2,$i,qtde)."</td>";
	}
	echo "</tr>";

	echo "</table>";

	echo "<br><br>";

}

include "rodape.php"; 

?>