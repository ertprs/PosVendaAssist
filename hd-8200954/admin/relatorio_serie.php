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
	}*/

	//Início Validação de Datas
	
	$data_inicial = $_POST['data_inicial'];
	if (strlen($_GET['data_inicial']) > 0) $data_inicial = $_GET['data_inicial'];
	$data_final   = $_POST['data_final'];
	if (strlen($_GET['data_final']) > 0) $data_final = $_GET['data_final'];
	
	if($acao){
		if(strlen($data_inicial)==0 && strlen($data_final)==0)
			$msg = "Data Inválida.";
	}
	if(strlen($msg)==0){
		if($data_inicial){
			$dat = explode ("/", $data_inicial );//tira a barra
			$d = $dat[0];
				$m = $dat[1];
				$y = $dat[2];
				if(!checkdate($m,$d,$y)) $msg= "Data Inválida";
		}
		if($data_final){
			$dat = explode ("/", $data_final );//tira a barra
				$d = $dat[0];
				$m = $dat[1];
				$y = $dat[2];
				if(!checkdate($m,$d,$y)) $msg= "Data Inválida";
		}
		if(strlen($erro)==0){
			$d_ini = explode ("/", $data_inicial);//tira a barra
			$nova_data_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...


			$d_fim = explode ("/", $data_final);//tira a barra
			$nova_data_final = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

			if($nova_data_final < $nova_data_inicial){
				$msg = "Data Inválida.";
			}
	}

		//Fim Validação de Datas
	}


##### Pesquisa de produto #####
if(strlen($msg)==0){
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
			$msg = " Produto não encontrado. ";
		}
	}else{
		$msg = " Informe o produto para realizar a pesquisa. ";
	}
}
}




$layout_menu = "gerencia";
$title = "RELATÓRIO - NÚMERO DE SÉRIE";

include "cabecalho.php";
?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family:Arial,  Verdana, Tahoma, Geneva, Helvetica, sans-serif;
	font-size: 14px;
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

<form name="frm_relatorio" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="acao">
<table width="700" border="0" cellspacing="0" cellpadding="2" align="center">
	<? if (strlen($msg) > 0) { ?>
		<tr bgcolor='#ff000000' style='font:bold 16px Arial; color:#ffffff;'>
			<td align='center' colspan='6'><?echo $msg?></td>
		</tr>
		
		<? } ?>
	<tr class="Titulo">
		<td colspan="6">Parâmetros de Pesquisa</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="6">&nbsp;</td>
	</tr>

	<TR bgcolor='#D9E2EF' class='Conteudo'>
		<td width='110'>&nbsp;</td>
		<TD ALIGN='left' colspan='2' >Data Inicial
			<INPUT size='12' maxlength='10' TYPE='text' NAME='data_inicial' id='data_inicial' value='$data_inicial' class='frm'>
		</TD>
		<TD ALIGN='left' colspan='2'>Data Final
			<INPUT size='12' maxlength='10' TYPE='text' NAME='data_final' id='data_final' value='$data_final' class='frm'>
		</TD>
		<td width='70'>&nbsp;</td>
	</TR>


		
		

<tr class="Conteudo" bgcolor="#D9E2EF" >
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="3"> Referência do Produto</td>
		<td colspan="3" ALIGN='left'>Descrição do Produto</td>

	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td nowrap align='center' colspan="3">
			<input type="text" name="produto_referencia" size="15" value="<?echo $produto_referencia?>" class="frm">
			<img src="imagens_admin/btn_lupa.gif" align="absmiddle" onclick="fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao, 'referencia')" style="cursor: hand;" alt="Clique aqui para pesquisar o produto" style="cursor:pointer ">
		</td>
		<td nowrap ALIGN='left' colspan="3">
			<input type="text" name="produto_descricao" size="20" value="<?echo $produto_descricao?>" class="frm">
			<img src="imagens_admin/btn_lupa.gif" align="absmiddle" onclick="fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao, 'descricao')" style="cursor: hand;" alt="Clique aqui para pesquisar o produto" style="cursor:pointer ">
		</td>

	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="6"><input type='image' src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor:pointer " alt="Clique AQUI para pesquisar"></td>
	</tr>
</table>
</form>

<br>

<?
flush();


if (strlen($acao) > 0 && strlen($msg) == 0) {

	// INICIO DA SQL
	

	
	$x_data_inicial = trim($data_inicial);
	$x_data_final   = trim($data_final);

	$x_data_inicial = fnc_formata_data_pg($x_data_inicial);
	$x_data_final   = fnc_formata_data_pg($x_data_final);

	$x_data_inicial = str_replace("'","",$x_data_inicial);
	$x_data_final   = str_replace("'","",$x_data_final);

	$x_data_inicial = $x_data_inicial. " 00:00:00";
	$x_data_final   = $x_data_final.   " 23:59:59";


//	$data_inicial = date("Y-m-01", mktime(0, 0, 0, $pesquisa_mes, 1, $pesquisa_ano));
//	$data_final   = date("Y-m-t", mktime(23, 59, 59, $pesquisa_mes, 1, $pesquisa_ano));


	$sql="	SELECT trim(serie) as serie,produto,count(os) as total
			INTO TEMP tmp_serie_mondial
			FROM tbl_os
			WHERE tbl_os.fabrica = $login_fabrica
			AND   tbl_os.produto = $produto
			AND   tbl_os.excluida IS NOT TRUE
			AND    tbl_os.data_digitacao::date BETWEEN '$x_data_inicial' AND '$x_data_final'
			GROUP BY serie,produto;
			
			CREATE INDEX tmp_serie_mondial_SERIE ON tmp_serie_mondial(serie);
			CREATE INDEX tmp_serie_mondial_PRODUTO ON tmp_serie_mondial(produto);

			SELECT S.serie,S.quantidade_produzida,X.total
			into temp tmp_serie_mondial2
			FROM tmp_serie_mondial   X
			JOIN tbl_serie_controle S ON X.serie   = S.serie 
			JOIN tbl_produto        P ON X.produto = P.produto AND  S.produto = P.produto
			GROUP BY S.serie,
					 S.quantidade_produzida,
					 X.total
			ORDER BY S.serie,
					 S.quantidade_produzida;
			
			ALTER table tmp_serie_mondial2 add column mes integer, add column ano integer;
			
			SELECT serie from tmp_serie_mondial2;
			";

	$res=@pg_exec($con,$sql);

	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {

		$serie=@pg_result($res,$i,serie);
		$xserie=substr($serie,0,2)."/".substr($serie,3,2);
		if(strlen($serie) == 6){
			//separa o ano
			$sql2 = "select to_char(to_date(".substr($serie,3,2).",'YY'),'YYYY')";
			$res2 = pg_exec($con,$sql2);
			$serie_ano=pg_result($res2,0,0);
			
			//separa o primeiro dia do ano, pois o postgres utiliza sempre primeiro dia do ano como sendo o primeiro de todas as semanas
			//ex: primeiro dia da semana = segunda-feira, o postgres interpreta todos os primeiros dias da semana do ano todo como sendo segunda-feira
			$serie_dia=substr($serie,2,1);
			$sql2="select to_char('$serie_ano-01-01' :: date,'D') as dia_semana";
			$res2=@pg_exec($con,$sql2);
			$dia_semana=@pg_result($res2,0,dia_semana);

			if($dia_semana>=$serie_dia) {
				$sql3=" select to_char(to_date('$xserie','WW/YY')-($dia_semana-$serie_dia),'MM') as mes_serie";
			}else{
				$sql3=" select to_char(to_date('$xserie','WW/YY')+($serie_dia-$dia_semana),'MM') as mes_serie";
			}
				
			$res3=@pg_exec($con,$sql3);
			$mes_serie=@pg_result($res3,0,mes_serie);

			$sql4="update tmp_serie_mondial2 set
						mes = '$mes_serie',
						ano = '$serie_ano' 
					where serie='$serie' ;";
			$res4=@pg_exec($con,$sql4);
		}
	}

	$sqlsoma="SELECT mes,ano,sum(quantidade_produzida) as total_quantidade,sum(total) as total from tmp_serie_mondial2 group by mes,ano order by mes,ano";

	$ressoma=@pg_exec($con,$sqlsoma);
	echo "<center>";
	echo "<name='frm_serie' method='POST' action=$PHP_SELF >";
	echo "<table width='350' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
	echo "<tr class='Titulo' >";
	echo "<td colspan='5'>$produto_referencia - $produto_descricao</td>";
	echo "</tr>";

	echo "<tr class='Titulo' height='15'>";
	echo "<td nowrap>MÊS</td>";
	echo "<td nowrap>QTD PROD.</td>";
	echo "<td nowrap>OCORRÊNCIAS</td>";
	echo "<td nowrap>%</td>";
	echo "<td nowrap>Ver Séries.</td>";
	echo "</tr>";
	for ($k = 0 ; $k < pg_numrows($ressoma) ; $k++) {
		$mes=pg_result($ressoma,$k,mes);
		$ano=@pg_result($ressoma,$k,ano);
		$total_quantidade=pg_result($ressoma,$k,total_quantidade);
		$total=pg_result($ressoma,$k,total);
		$porcentagem = (($total*100)/$total_quantidade);
		$porcentagem =number_format($porcentagem, 2, '.','');
		$cor = ($k % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;

		echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
		echo "<td>";
		echo $mes."/".$ano;
		echo "</td>";
		echo "<td>";
		echo "$total_quantidade";
		echo "</td>";
		echo "<td>";
		echo "$total";
		echo "</td>";
		echo "<td>";
		echo "$porcentagem";
		echo "</td>";
		echo "<td>";
		echo "<a href='$PHP_SELF?data_inicial=$data_inicial&data_final=$data_final&mes=$mes&ano=$ano&produto=$produto&produto_referencia=$produto_referencia&produto_descricao=$produto_descricao' target='_blank'>Ver Séries</a>";
		echo "</td>";
		echo "</tr>";
	}
	echo "</table>";
}


$mes = $_GET['mes'];
$ano = $_GET['ano'];
if(strlen($mes) == 1) $mes = "0".$mes;
if(strlen($ano) > 0 and strlen($mes) > 0){

	$data_inicial = $_POST['data_inicial'];
	if (strlen($_GET['data_inicial']) > 0) $data_inicial = $_GET['data_inicial'];
	$data_final   = $_POST['data_final'];
	if (strlen($_GET['data_final']) > 0) $data_final = $_GET['data_final'];

	$x_data_inicial = trim($data_inicial);
	$x_data_final   = trim($data_final);

	$x_data_inicial = fnc_formata_data_pg($x_data_inicial);
	$x_data_final   = fnc_formata_data_pg($x_data_final);

	$x_data_inicial = str_replace("'","",$x_data_inicial);
	$x_data_final   = str_replace("'","",$x_data_final);

	$x_data_inicial = $x_data_inicial. " 00:00:00";
	$x_data_final   = $x_data_final.   " 23:59:59";

	$sql="SELECT trim(serie) as serie,produto,count(os) as total
			INTO TEMP tmp_serie_mondial
			FROM tbl_os
			WHERE tbl_os.fabrica = $login_fabrica
			AND   tbl_os.produto = $produto
			AND   tbl_os.excluida IS NOT TRUE
			AND    tbl_os.data_digitacao::date BETWEEN '$x_data_inicial' AND '$x_data_final'
			GROUP BY serie,produto;
			
			CREATE INDEX tmp_serie_mondial_SERIE ON tmp_serie_mondial(serie);
			CREATE INDEX tmp_serie_mondial_PRODUTO ON tmp_serie_mondial(produto);

			SELECT	S.serie,
					S.quantidade_produzida,
					X.total
			FROM tmp_serie_mondial   X
			JOIN tbl_serie_controle S ON X.serie   = S.serie 
			JOIN tbl_produto        P ON X.produto = P.produto AND  S.produto = P.produto
			GROUP BY S.serie,
					 S.quantidade_produzida,
					 X.total
			ORDER BY S.serie,
					 S.quantidade_produzida; ";

	$res=pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {

		echo "<center>";
		echo "<table width='350' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
		echo "<tr class='Titulo' >";
		echo "<td colspan='5'>" . $data_inicial . " - " .$data_final;
		echo "</td>";
		echo "</tr>";
		echo "<tr class='Titulo' >";
		echo "<td colspan='5'>$produto_referencia - $produto_descricao</td>";
		echo "</tr>";
		echo "<tr class='Titulo' height='15'>";
		echo "<td nowrap>SÉRIE</td>";
		echo "<td nowrap>QTD PROD.</td>";
		echo "<td nowrap>OCORRÊNCIAS</td>";
		echo "<td nowrap>%</td>";
		echo "<td nowrap>Ver OSs.</td>";
		echo "</tr>";
		for ($j = 0 ; $j < pg_numrows($res) ; $j++) {

			$serie=pg_result($res,$j,serie);
			$xserie=substr($serie,0,2)."/".substr($serie,3,2);

			//separa o ano
			$sql2 = "select to_char(to_date(".substr($serie,3,2).",'YY'),'YYYY')";
			$res2 = pg_exec($con,$sql2);
			$serie_ano=pg_result($res2,0,0);
			
			//separa o primeiro dia do ano, pois o postgres utiliza sempre primeiro dia do ano como sendo o primeiro de todas as semanas
			//ex: primeiro dia da semana = segunda-feira, o postgres interpreta todos os primeiros dias da semana do ano todo como sendo segunda-feira
			$serie_dia=substr($serie,2,1);
			$sql2="select to_char('$serie_ano-01-01' :: date,'D') as dia_semana";
			$res2=pg_exec($con,$sql2);
			$dia_semana=pg_result($res2,0,dia_semana);

			if($dia_semana>=$serie_dia) {
				$sql3=" select to_char(to_date('$xserie','WW/YY')-($dia_semana-$serie_dia),'MM') as mes_serie";
			}else{
				$sql3=" select to_char(to_date('$xserie','WW/YY')+($serie_dia-$dia_semana),'MM') as mes_serie";
			}
				
			$res3=pg_exec($con,$sql3);
			$mes_serie=pg_result($res3,0,mes_serie);
			
			if($mes_serie==$mes AND $serie_ano==$ano){

				$serie                = pg_result($res,$j,serie);
				$quantidade_produzida = pg_result($res,$j,quantidade_produzida);
				$total                = pg_result($res,$j,total);
				
				$cor = ($j % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;
				$porcentagem = (($total*100)/$quantidade_produzida);
				$porcentagem =number_format($porcentagem, 2, '.','');
				
				echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
				echo "<td nowrap align='left'>&nbsp; $serie</td>";
				echo "<td nowrap align='right'>&nbsp; $quantidade_produzida</td>";
				echo "<td nowrap align='right'>&nbsp; $total</td>";
				echo "<td nowrap align='right'>&nbsp; $porcentagem</td>";
				echo "<td nowrap align='center'><a href='relatorio_serie_detalhe.php?produto=$produto&serie=$serie&data_inicio=$x_data_inicial&data_fim=$x_data_final' target='_blank'>&nbsp; Ver Oss.</a></td>";
				echo "</tr>";
			}
		}
		echo "</table>";
		echo "</form>";
	}else {
		echo "Nenhum resultado encontrado.";
	}
}





			




echo "<br>";

include "rodape.php";
?>
