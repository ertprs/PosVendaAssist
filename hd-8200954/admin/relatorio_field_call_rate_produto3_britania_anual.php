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

	$ano = trim (strtoupper ($_POST['ano']));

	if(strlen($ano) == 0){
		$msg = "Escolha o Ano.";
	}

}

$layout_menu = "gerencia";
$title = "RELATÓRIO - FIELD CALL-RATE 3 : OS POR PRODUTO";

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

<? include "javascript_pesquisas.php"; ?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>

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
<table width="400" border="0" cellspacing="0" cellpadding="2" align="center">
	<tr class="Titulo">
		<td colspan="4">PESQUISA</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
		<tr>
		<td class="Conteudo" bgcolor="#D9E2EF" style="width: 10px">&nbsp;</td>
		<td class="Conteudo" bgcolor="#D9E2EF" colspan='2' style="font-size: 10px"><center>Este relatório considera o mês inteiro de OS.</center></td>
		<td class="Conteudo" bgcolor="#D9E2EF" style="width: 10px">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">Ano</td>

	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">

		<td colspan="4">
			<select name="ano" size="1" class="frm">
			<?
			//for ($i = 2003 ; $i <= date("Y") ; $i++) {
			for($i = date("Y"); $i > 2003; $i--){
				echo "<option value='$i'";
				if ($ano == $i) echo " selected";
				echo ">$i</option>";
			}
			?>
			</select>
		</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td >&nbsp;</td>
		<td >Data para filtrar:</td>
		<td align='left'><input type="radio" name="data_filtro" value="data_digitacao" <? if($escolha == 'data_digitacao' OR $escolha == ''){ ?> checked <?}?> >Digitação da OS<br><input type="radio" name="data_filtro" value="finalizada" <? if ($escolha == 'finalizada'){?> checked <?}?> >Finalização da OS</td>
		<td >&nbsp;</td>
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
$escolha = trim($_POST['data_filtro']); 

if (strlen($acao) > 0 && strlen($msg) == 0) {
	$x_data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, "01", 1, $ano));
	$x_data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, "12", 1, $ano));

	$mostra_data_inicial = mostra_data($x_data_inicial);
	$mostra_data_final   = mostra_data($x_data_final);

	if(strlen($produto_referencia) > 0){
		$sql_produto = "SELECT produto from tbl_produto where referencia='$produto_referencia'";
		$res_produto = pg_exec($con, $sql_produto);
		$produto = pg_result($res_produto,0,produto);
	}

//	$mostra_data_inicial = "-01-01 00:00:00'";
//	$mostra_data_final   = "-12-01 00:00:00'";;

	$sql = "SELECT tbl_produto.produto,
				tbl_produto.descricao , 
				tbl_produto.referencia, 
				TO_CHAR(tbl_os.data_digitacao,'MM') AS mes_digitacao, 
				COUNT(os) AS qtde_os 
				INTO TEMP temp_field_call_rate_britania
			FROM tbl_os 
			JOIN tbl_produto using(produto)
			JOIN tbl_linha using(linha)
			WHERE tbl_os.$escolha BETWEEN '$x_data_inicial' AND '$x_data_final' 
				AND tbl_os.fabrica = 3 
				AND tbl_os.excluida IS NOT TRUE 
			GROUP BY tbl_produto.descricao, 
				tbl_produto.referencia, 
				tbl_produto.produto, 
				mes_digitacao 
			ORDER BY 
				COUNT(*) desc, 
				mes_digitacao,
				tbl_produto.descricao;";

	$res = pg_exec($con,$sql);

	$sql = " select produto, 
					mes_digitacao, 
					qtde_os 
			 from temp_field_call_rate_britania;";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		$total_os = '';
		echo "<table border='1' align='center' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
		echo "<tr class='Titulo'>";
			echo "<td colspan='3' height='40' style='font-size: 14px'>Datas da Pesquisa: ". $mostra_data_inicial ." até ". $mostra_data_final ."</td>";
		echo "</tr>";
		echo "<tr class='Titulo'>";
			echo "<td style='font-size: 14px'>Referência</td>";
			echo "<td style='font-size: 14px'>Produto</td>";
			echo "<td style='font-size: 14px'>Jan</td>";
			echo "<td style='font-size: 14px'>Fev</td>";
			echo "<td style='font-size: 14px'>Mar</td>";
			echo "<td style='font-size: 14px'>Abr</td>";
			echo "<td style='font-size: 14px'>Mai</td>";
			echo "<td style='font-size: 14px'>Jun</td>";
			echo "<td style='font-size: 14px'>Jul</td>";
			echo "<td style='font-size: 14px'>Ago</td>";
			echo "<td style='font-size: 14px'>Set</td>";
			echo "<td style='font-size: 14px'>Out</td>";
			echo "<td style='font-size: 14px'>Nov</td>";
			echo "<td style='font-size: 14px'>Dez</td>";

		
		echo "</tr>";
		$array_rel="";
		for($i=0;$i<pg_numrows($res);$i++){			
			$produto		= pg_result($res,$i,produto);
			$qtde_os        = pg_result($res,$i,qtde_os);
			$mes_digitacao	= pg_result($res,$i,mes_digitacao);

			$array_rel[$produto][$mes_digitacao]= $qtde_os;
		}

		$array_meses= array (1 => "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12");

		$sql = "select distinct referencia, produto, descricao	
			from temp_field_call_rate_britania
			order by descricao;";

		$res = pg_exec($con,$sql);

		for($i=0;$i<pg_numrows($res);$i++){			
			$produto	= pg_result($res,$i,produto);
			$referencia = pg_result($res,$i,referencia);
			$descricao	= pg_result($res,$i,descricao);
			echo "<tr height='15' class='Conteudo' >";
			echo "<td align='left'>$referencia</td>";
			echo "<td align='left'>$descricao</td>";


			for($p=1; $p<13; $p++){
				$m = $array_meses[$p];

				if(strlen($array_rel[$produto][$m]) > 0 ){
					echo "<td>".$array_rel[$produto][$m]."</td>";				
					$total_os[$m] = $array_rel[$produto][$m] + $total_os[$m];
				}else{
					echo "<td> - </td>";				
				}


			}
		}
		echo "<tr>";
		echo "<td colspan='2' class='Titulo'>Total: </td>";
		for($p=1; $p<13; $p++){
			$m = $array_meses[$p];
			echo "<td >".$total_os[$m] ."</td>";
		}
		echo "</tr>";
		echo "</table>";

	}

echo "<br>";
}
include "rodape.php";
?>
