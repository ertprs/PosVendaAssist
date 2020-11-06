<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include "autentica_admin.php";

include "funcoes.php";

$msg = "";

if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);

if (strlen($acao) > 0) {

	##### Pesquisa de data #####
	$pesquisa_mes = trim($_POST["pesquisa_mes"]);
	$pesquisa_ano = trim($_POST["pesquisa_ano"]);

	//if (strlen($pesquisa_mes) == 0) $msg .= " Informe o mês para realizar a pesquisa. ";
	//if (strlen($pesquisa_ano) == 0) $msg .= " Informe o ano para realizar a pesquisa. ";

/*	if (strlen($msg) == 0) {
		if (strlen($pesquisa_ano) == 2 OR strlen($pesquisa_ano) == 4) {
			if ($pesquisa_ano >= 50 && strlen($pesquisa_ano) == 2) $pesquisa_ano = "19" . $pesquisa_ano;
			elseif ($pesquisa_ano < 50 && strlen($pesquisa_ano) == 2) $pesquisa_ano = "20" . $pesquisa_ano;
		}else{
			$msg .= " Informe o ano para realizar a pesquisa. ";
		}
	}

*/
##### Pesquisa de produto #####
	$produto_referencia = trim($_POST["produto_referencia"]);
	$produto_descricao  = trim($_POST["produto_descricao"]);

	if (strlen($produto_referencia) > 0 && strlen($produto_descricao) > 0) {
		$produto_referencia = str_replace("-", "", $produto_referencia);
		$produto_referencia = str_replace("_", "", $produto_referencia);
		$produto_referencia = str_replace(".", "", $produto_referencia);
		$produto_referencia = str_replace(",", "", $produto_referencia);
		$produto_referencia = str_replace("/", "", $produto_referencia);

		$sql =	"SELECT tbl_produto.produto    ,
						tbl_produto.referencia ,
						tbl_produto.descricao  
				FROM tbl_produto
				JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
				WHERE tbl_linha.fabrica = $login_fabrica";
		if (strlen($produto_referencia) > 0) $sql .= " AND tbl_produto.referencia_pesquisa = '$produto_referencia'";
#		if (strlen($produto_descricao) > 0)   $sql .= " AND tbl_produto.descricao = '$produto_descricao';";

		$res = pg_exec($con,$sql);
		if (pg_numrows($res) == 1) {
			$produto            = pg_result($res,0,produto);
			$produto_referencia = pg_result($res,0,referencia);
			$produto_descricao  = pg_result($res,0,descricao);
		}else{
			$msg .= " Produto não encontrado. ";
		}
	}else{
		$msg .= " Informe o produto para realizar a pesquisa. ";
	}
}




$layout_menu = "gerencia";
$title = "RELATÓRIO - NÚMERO DE SÉRIE";

include "cabecalho.php";
?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
}
</style>
<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.Mes{
	font-size: 9px;
}
</style>

<? include "javascript_pesquisas.php"; ?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<? include "javascript_calendario.php"; ?>

<script type="text/javascript" charset="utf-8">
	$(function()
	{
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>
<br>

<? if (strlen($msg) > 0) { ?>
<table width="730" border="0" cellpadding="2" cellspacing="2" align="center" class="error">
	<tr>
		<td><?echo $msg?></td>
	</tr>
</table>
<br>
<? } ?>

<form name="frm_relatorio" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="acao">
<table width="500" border="0" cellspacing="0" cellpadding="2" align="center">
	<tr class="Titulo">
		<td colspan="4">PESQUISA</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
<?
	echo "<TR bgcolor='#D9E2EF' class='Conteudo'>\n";
	echo "	<TD ALIGN='center' colspan='4' nowrap><b>O relatório será gerado com o período de 1 ano a partir da data inicial</b></TD></TR>";
	echo "<TR bgcolor='#D9E2EF' class='Conteudo'>\n";
	echo "	<TD ALIGN='right' colspan='2' nowrap>Data Inicial </TD>";
	echo "	<TD ALIGN='left' colspan='2' nowrap>";
	echo "	<INPUT size='12' maxlength='10' TYPE='text' NAME='data_inicial' id='data_inicial' value='$data_inicial' class='frm'>";
	echo "	</TD>\n";
	echo "</TR>\n";
?>
<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="2" align='right'> Referência do Produto</td>
		<td nowrap align='left' colspan="2">
			<input type="text" name="produto_referencia" size="15" value="<?echo $produto_referencia?>" class="frm">
			<img src="imagens_admin/btn_lupa.gif" align="absmiddle" onclick="fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao, 'referencia')" style="cursor: hand;" alt="Clique aqui para pesquisar o produto">
		</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="2" align='right'>Descrição do Produto</td>
		<td nowrap align='left' colspan="2">
			<input type="text" name="produto_descricao" size="20" value="<?echo $produto_descricao?>" class="frm">
			<img src="imagens_admin/btn_lupa.gif" align="absmiddle" onclick="fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao, 'descricao')" style="cursor: hand;" alt="Clique aqui para pesquisar o produto">
		</td>

	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4"><img src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor:pointer " alt="Clique AQUI para pesquisar"></td>
	</tr>
</table>
</form>

<br>

<?
flush();
if (strlen($acao) > 0 && strlen($msg) == 0) {

	// INICIO DA SQL
	$data_inicial = $_POST['data_inicial'];
	if (strlen($_GET['data_inicial']) > 0) $data_inicial = $_GET['data_inicial'];
	$data_final   = $_POST['data_final'];
	if (strlen($_GET['data_final']) > 0) $data_final = $_GET['data_final'];
	$posto_codigo = $_POST['posto_codigo'];
	if (strlen($_GET['cnpj']) > 0) $posto_codigo = $_GET['cnpj'];
	
	$x_data_inicial = trim($data_inicial);
	$x_data_final   = trim($data_final);

	$data_consulta1 = trim($data_inicial);

	$x_data_inicial = fnc_formata_data_pg($x_data_inicial);
	$x_data_final   = fnc_formata_data_pg($x_data_final);

	$x_data_inicial = str_replace("'","",$x_data_inicial);
	$x_data_final   = str_replace("'","",$x_data_final);

	$data_inicial = $x_data_inicial. " 00:00:00";
	$data_final   = $x_data_final.   " 23:59:59";

	$data_final = $data_inicial;
	$sql = "SELECT ('$data_inicial'::DATE + INTERVAL'1 YEAR')::DATE || ' 00:00:00',TO_CHAR(('$data_inicial'::DATE + INTERVAL'1 YEAR')::DATE ,'dd/mm/yyyy');";
	//echo $sql;
	$res = pg_exec($con,$sql);
	$data_final = pg_result($res,0,0);
	$data_consulta2 = pg_result($res,0,1);



//	$data_inicial = date("Y-m-01", mktime(0, 0, 0, $pesquisa_mes, 1, $pesquisa_ano));
//	$data_final   = date("Y-m-t", mktime(23, 59, 59, $pesquisa_mes, 1, $pesquisa_ano));

	$sql = "

		SELECT os,trim(serie) as serie,produto,to_char(tbl_os.data_digitacao,'YYYY-MM') as data_geracao
		INTO TEMP tmp_modial_serie
		FROM tbl_os
		WHERE tbl_os.fabrica = $login_fabrica
		AND   tbl_os.produto = $produto
		AND   tbl_os.data_digitacao BETWEEN '$data_inicial' and '$data_final'
		AND   tbl_os.excluida IS NOT TRUE;

		CREATE INDEX tmp_modial_serie_SERIE ON tmp_modial_serie(serie);
		CREATE INDEX tmp_modial_serie_PRODUTO ON tmp_modial_serie(produto);

		SELECT count(X.os)             AS qtde,
			X.data_geracao,
			S.serie,
			S.quantidade_produzida
		FROM tmp_modial_serie   X
		JOIN tbl_serie_controle S ON X.serie   = S.serie 
		JOIN tbl_produto        P ON X.produto = P.produto AND  S.produto = P.produto
		GROUP BY S.serie,
			 S.quantidade_produzida,
			 X.data_geracao 
		ORDER BY S.serie,X.data_geracao; ";

		$sql = "SELECT 
					TRIM(S.serie)          AS serie,
					S.quantidade_produzida,
					S.produto
				INTO TEMP tmp_modial_serie
				FROM tbl_serie_controle S
				JOIN tbl_produto        P ON S.produto = P.produto
				WHERE P.produto = $produto 
				ORDER BY S.serie;
				
				CREATE INDEX tmp_modial_serie_SERIE ON tmp_modial_serie(serie);
				CREATE INDEX tmp_modial_serie_PRODUTO ON tmp_modial_serie(produto);

				SELECT  count(os) AS qtde,
						X.serie,
						to_char(tbl_os.data_digitacao,'YYYY-MM') as data_geracao,
						X.quantidade_produzida
				INTO TEMP tmp_modial_serie2
				FROM tmp_modial_serie X 
				LEFT JOIN tbl_os ON X.serie = TRIM(tbl_os.serie) AND X.produto = tbl_os.produto
				WHERE tbl_os.fabrica = $login_fabrica
				AND   tbl_os.produto = $produto
				AND   tbl_os.data_digitacao between '$data_inicial' and '$data_final'
				AND   tbl_os.excluida IS NOT TRUE
				GROUP BY X.serie,
						 X.quantidade_produzida,
						 data_geracao
				ORDER BY X.serie,data_geracao;

				INSERT INTO tmp_modial_serie2 (quantidade_produzida,serie,data_geracao,qtde)
				SELECT quantidade_produzida,serie,TO_CHAR('$data_final'::DATE,'YYYY-MM'),0 FROM tmp_modial_serie 
				EXCEPT SELECT quantidade_produzida,serie,TO_CHAR('$data_final'::DATE,'YYYY-MM'),0 FROM tmp_modial_serie2;

				SELECT * FROM tmp_modial_serie2 ORDER BY serie,data_geracao;
				";



	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		
	
		echo "<table border='2' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' width='750' align='center'>";
		echo "<tr class='Titulo'>";
		echo "<td colspan='15' align='left'>$produto_referencia - $produto_descricao | Relatório de quebra em um intervalo de 12 Meses - </td>";
		echo "</tr>";
		echo "<tr class='Titulo'>";
		echo "<td rowspan='2'>Série</td>";
		echo "<td rowspan='2'>Produzida</td>";
		echo "<td colspan='12'>Período de Meses entre $data_consulta1 e $data_consulta2</td>";
		echo "<td rowspan='2' class='Mes'>Total Ano</td>";
		echo "</tr><tr class='Titulo'>";
		for($x=0;$x<12;$x++){
			$data_serv  = mktime (date("H"), date("i"), date("s"),date("m")- 11 + $x , date("d"), date("Y"));
			$mes[$x] = strftime ("%m/%Y", $data_serv);
			
			echo "<td class='Mes'>$mes[$x]</td>";
		}
		echo "</tr>";

		$serie=0;
		$x=0;
		$y=0;
		$serie_total=0;
		$qtde_mes =  array();
	
		flush();
	
		for ($i=0; $i<pg_numrows($res); $i++){
			$serie          = trim(pg_result($res,$i,serie));
			$qtde_produzida = trim(pg_result($res,$i,quantidade_produzida));
	
			if($serie_total<>$serie){
				$qtde_mes[$serie_total][0]  = 0;
				$qtde_mes[$serie_total][1]  = 0;
				$qtde_mes[$serie_total][2]  = 0;
				$qtde_mes[$serie_total][3]  = 0;
				$qtde_mes[$serie_total][4]  = 0;
				$qtde_mes[$serie_total][5]  = 0;
				$qtde_mes[$serie_total][6]  = 0;
				$qtde_mes[$serie_total][7]  = 0;
				$qtde_mes[$serie_total][8]  = 0;
				$qtde_mes[$serie_total][9]  = 0;
				$qtde_mes[$serie_total][10] = 0;
				$qtde_mes[$serie_total][11] = 0;
				$qtde_mes[$serie_total][12] = $qtde_produzida;
				$x=0;
				$serie_anterior=$serie;
				$serie_total = $serie_total+1;
			}
		}
	
		for ($i=0; $i<pg_numrows($res); $i++){
	
			$serie          = trim(pg_result($res,$i,serie));
			$qtde_produzida = trim(pg_result($res,$i,quantidade_produzida));
			$data_geracao   = trim(pg_result($res,$i,data_geracao));
			$qtde           = trim(pg_result($res,$i,qtde));
	
			$xdata_geracao = explode('-',$data_geracao);
			$data_geracao = $xdata_geracao[1].'/'.$xdata_geracao[0];
			//echo "<td>$data_geracao</td>";flush();
			if($serie_anterior<>$serie){
	
	//IMPRIME O VETOR COM OS DOZE MESES E LOGO APÓS PULA LINHA E ESCREVE O NOVO familia
				if($i<>0 AND $serie_anterior<>$serie ){
					
					for($a=0;$a<12;$a++){			//imprime os doze meses
						echo "<td bgcolor='$cor' title='".$mes[$a]."'>";
						if ($qtde_mes[$y][$a]>0)
							echo "<font color='#000000'><b>".$qtde_mes[$y][$a];
						else echo "<font color='#999999'> ";
	
						echo "</td>";
						$total_ano = $total_ano + $qtde_mes[$y][$a];
						if($a==11) {
							echo "<td bgcolor='$cor' >$total_ano</td>";
							echo "</tr>";
							flush();
						}	// se for o ultimo mes quebra a linha
					
					}
	
					$y=$y+1;						// usado para indicação de familia
				}
	
				if($cor=="#F1F4FA")$cor = '#F7F5F0';
				else               $cor = '#F1F4FA';
	
				echo "<tr class='Conteudo'align='center'>";
				echo "<td bgcolor='$cor' width='150' height='40'>$serie</td>";
				echo "<td bgcolor='$cor' height='40'>$qtde_produzida</td>";
	
				$total_ano = 0;
				$x=0; //ZERA OS MESES
				$serie_anterior =$serie;
			}
	
			while($data_geracao<>$mes[$x]){ //repete o lup até que o mes seja igual e anda um mes.
				$x=$x+1;
				if($x>12) $x=1;
	//			echo $data_geracao.$mes[$x]; echo "<br>";
			};
	
			if($data_geracao==$mes[$x]){
				$qtde_mes[$y][$x] = $qtde;
			}
	
			$x=$x+1; //após armazenar o valor no mes correspondente anda 1 mes no vetor
			if($i==(pg_numrows($res)-1)){
				for($a=0;$a<12;$a++){			//imprime os doze meses
					echo "<td bgcolor='$cor' title='".$mes[$a]."'>";
					if ($qtde_mes[$y][$a]>0)
						echo "<font color='#000000'><b>".$qtde_mes[$y][$a];
					else echo "<font color='#999999'> ";
	
					echo "</td>";
					$total_ano = $total_ano + $qtde_mes[$y][$a];
					if($a==11) {
						echo "<td bgcolor='$cor' >$total_ano</td>";
						echo "</tr>";
					}	// se for o ultimo mes quebra a linha
				}
			}
		}
		flush();
		$serie_total=0;
		for ($i=0; $i<pg_numrows($res); $i++){
			$serie          = trim(pg_result($res,$i,serie));
			$qtde_produzida = trim(pg_result($res,$i,quantidade_produzida));
			$total_produzida += $qtde_produzida;
			if($serie_total<>$serie){
				$total_0  += $qtde_mes[$serie_total][0];
				$total_1  += $qtde_mes[$serie_total][1] ;
				$total_2  += $qtde_mes[$serie_total][2];
				$total_3  += $qtde_mes[$serie_total][3];
				$total_4  += $qtde_mes[$serie_total][4];
				$total_5  += $qtde_mes[$serie_total][5];
				$total_6  += $qtde_mes[$serie_total][6];
				$total_7  += $qtde_mes[$serie_total][7];
				$total_8  += $qtde_mes[$serie_total][8];
				$total_9  += $qtde_mes[$serie_total][9];
				$total_10 += $qtde_mes[$serie_total][10];
				$total_11 += $qtde_mes[$serie_total][11];
				$x=0;
				$serie_anterior=$serie;
				$serie_total = $serie_total+1;
			}
		}
		echo "<tr class='Conteudo'>";
		echo "<td><b>TOTAL</b></td>";
		echo "<td><b>$total_produzida</b></td>";
		echo "<td><b>$total_0</b></td>";
		echo "<td><b>$total_1</b></td>";
		echo "<td><b>$total_2</b></td>";
		echo "<td><b>$total_3</b></td>";
		echo "<td><b>$total_4</b></td>";
		echo "<td><b>$total_5</b></td>";
		echo "<td><b>$total_6</b></td>";
		echo "<td><b>$total_7</b></td>";
		echo "<td><b>$total_8</b></td>";
		echo "<td><b>$total_9</b></td>";
		echo "<td><b>$total_10</b></td>";
		echo "<td><b>$total_11</b></td>";
		$total_geral = $total_0 + $total_1 + $total_2 + $total_3 + $total_4 + $total_5 + $total_6 + $total_7 + $total_8 + $total_9 + $total_10 + $total_11;
		echo "<td><b>$total_geral</b></td>";
		echo "</tr>";
		echo "</table>";
	} else {
		echo "Nenhum resultado encontrado";
	}
}



include "rodape.php";
?>
