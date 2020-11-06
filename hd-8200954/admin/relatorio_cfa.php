<?

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";
include "funcoes.php";

$msg = "";

setcookie("cookredirect", $_SERVER["REQUEST_URI"]);

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");


$layout_menu = "gerencia";
$title       = "RELATÓRIO DE GARANTIA DIVIDIDO POR CFA's";

include "cabecalho.php";
?>


<style type="text/css">
.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.espaco td{
	padding:10px 0 10px;
}

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
	border:1px solid #596d9b;
}
</style>

<script type="text/javascript" src="js/jquery.js"></script>

<script type="text/javascript" src="js/jquery.maskedinput.js"></script>

<script>

$().ready(function(){
    $( "#data_inicial" ).datePicker({startDate : "01/01/2000"});
    $( "#data_inicial" ).maskedinput("99/99/9999");
	$( "#data_final" ).datePicker({startDate : "01/01/2000"});
    $( "#data_final" ).maskedinput("99/99/9999");
});

</script>

<?php include "javascript_calendario.php";?>

<? include "javascript_pesquisas.php"; ?>
<div id="msg"></div>
<div class="texto_avulso">Este relatório usa como pesquisa a data de digitação.</div><br />

<form name="frm_pesquisa" method="POST" action="<?echo $PHP_SELF?>">

<input type="hidden" name="acao">

<table width="700" align="center" border="0" cellspacing="1" cellpadding="0" class="formulario">

	<tr class="titulo_tabela">
		<td colspan="3">Parâmetros de Pesquisa</td>
	</tr>
	<tr>
		<td colspan="3">&nbsp;</td>
	</tr>

	<tr>
	<td align='center' colspan='2'>
		<table width='100%'>
			<tr>
			<td style="width:200px;">&nbsp;</td>
			<td align='left' style="width:130px;">
			Data Inicial<br>
			<input type="text" name="data_inicial" id="data_inicial" size="11" maxlength="10" value="<? echo $data_inicial ?>" class="frm">
			</td>
			<td align='left'>
			Data Final<br>
			<input type="text" name="data_final" id="data_final" size="11" maxlength="10" value="<? echo $data_final ?>" class="frm">
			</td>
			</tr>
		</table>
	</td>
	</tr>
	<tr>
		<td colspan="2">&nbsp;</td>
	</tr>
	<tr>
		<td colspan="2" align="center">
			<input type="button" onclick="document.frm_pesquisa.acao.value='PESQUISAR'; document.frm_pesquisa.submit();" style="cursor: pointer;" value="Pesquisar" />
		</td>
	</tr>
	<tr><td colspan="3">&nbsp;</td></tr>
</table>
</form>

<?
$btn_acao = $_POST['acao'];
$cond_os_produto   = "1=1";
$cond_data= "1=1";


if(strlen($btn_acao)>0){


	$data_inicial = trim($_POST["data_inicial"]);
	$data_final = trim($_POST["data_final"]);

	if (strlen($data_inicial) > 0 AND strlen($data_final) > 0){
		$x_data_inicial = fnc_formata_data_pg($data_inicial);
		$x_data_final   = fnc_formata_data_pg($data_final);
		$y_data_inicial = substr($x_data_inicial,9,2) . substr($x_data_inicial,6,2) . substr($x_data_inicial,1,4);
		$y_data_final = substr($x_data_final,9,2) . substr($x_data_final,6,2) . substr($x_data_final,1,4);
		
		if ($x_data_inicial != "null") {
			$data_inicial = substr($x_data_inicial,9,2) . "/" . substr($x_data_inicial,6,2) . "/" . substr($x_data_inicial,1,4);
		}else{
			$data_inicial = "";
			$erro = "Data Inválida";
		}
		
		if ($x_data_final != "null") {
			$data_final = substr($x_data_final,9,2) . "/" . substr($x_data_final,6,2) . "/" . substr($x_data_final,1,4);
		}else{
			$data_final = "";
			$erro = "Data Inválida";
		}
			
		if($x_data_inicial > $x_data_final)
			$erro = "Data Inválida";

		list($df, $mf, $yf) = explode("/", $data_final);
        if(!checkdate($mf,$df,$yf)) 
            $erro = "Data Inválida";

		list($di, $mi, $yi) = explode("/", $data_inicial);
        if(!checkdate($mi,$di,$yi)) 
            $erro = "Data Inválida";

		if(strlen($erro) == 0){
			$cond_data = " tbl_suggar_questionario.data between $x_data_inicial and $x_data_final ";
		}
	}
	else $erro = "Data Inválida";
	if(strlen($erro) == 0) {
		echo "<br /><table border='0' cellpadding='0' cellspacing='1' width='700' align='center' class='tabela'>";
		echo "<tr class='titulo_coluna'>";
		echo "<TD colspan ='1' rowspan='4'>	";
		echo "Conta de fabricação";
		echo "</td>";
		echo "<TD colspan='8' border='0'>	";
		echo "&nbsp;";
		echo "</td>";
		echo "</tr>";

		echo "<tr class='titulo_coluna'>";
		echo "<TD nowrap colspan='2' bgcolor='#CCFFCC'>";
		echo "<font color=red>Valores em Reais</font>";
		echo "</td>";
		echo "<TD nowrap colspan='2' bgcolor='#CCFFCC'>";
		echo "<font color=red>Quantidade de OS's</font>";
		echo "</td>";
		echo "<TD nowrap colspan='2' bgcolor='#FFFF99'>";
		echo "<font color=red>Valores em Reais</font>";
		echo "</td>";
		echo"<TD nowrap colspan='2' bgcolor='#FFFF99'>";
		echo "<font color=red>Quantidade de OS's</font>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<TD colspan='2' bgcolor='#CCFFCC'>";
		echo "Conta Nacional<br>480184002";
		echo "</td>";
		echo "<TD colspan='2' bgcolor='#CCFFCC'>";
		echo "Conta Nacional<br>480184002";
		echo "</td>";
		echo "<TD colspan='2' nowrap bgcolor='#FFFF99'>";
		echo "Conta máquinas importadas<br>480184003";
		echo "</td>";
		echo "<TD colspan='2' nowrap bgcolor='#FFFF99'>";
		echo "Conta máquinas importadas<br>480184003";
		echo "</td>";
		echo "</tr>";

		echo "<tr class='Conteudo'>";
		echo "<TD bgcolor='#CCFFCC'>MO</td>";
		echo "<TD bgcolor='#CCFFCC'>PC</td>";
		echo "<TD bgcolor='#CCFFCC' colspan='2'>QTDE</td>";
		echo "<TD bgcolor='#FFFF99'>MO</td>";
		echo "<TD bgcolor='#FFFF99'>PC</td>";
		echo "<TD bgcolor='#FFFF99' colspan='2'>QTDE</td>";
		echo "</tr>";

		$sql = "SELECT	tbl_familia.bosch_cfa,
					tbl_familia.descricao,
					tbl_familia.familia,
					origem,
					qtd,
					vlr_pecas,
					vlr_mo
				FROM tbl_familia
				JOIN (
					SELECT  tbl_produto.origem,
						 tbl_produto.familia,
						 count(tbl_os.os) as QTD,
						 sum(tbl_os.pecas) as VLR_PECAS,
						 sum(tbl_os.mao_de_obra) as VLR_MO
					FROM tbl_os 
					JOIN tbl_produto on tbl_os.produto = tbl_produto.produto
					JOIN tbl_posto on tbl_posto.posto = tbl_os.posto 
					JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica 
					WHERE tbl_os.fabrica = $login_fabrica AND 
					tbl_posto_fabrica.distribuidor IS NULL AND
					tbl_os.data_digitacao >= $x_data_inicial and tbl_os.data_digitacao <= $x_data_final AND
					tbl_os.excluida is not true AND
					tbl_posto.pais = 'BR' AND
					tbl_posto.posto not in(20419, 19317, 19074, 20287, 6359, 20173, 19059)
					group by 
						tbl_produto.familia,
						tbl_produto.origem
				)SOMA ON soma.familia = tbl_familia.familia

				ORDER BY descricao;";

		$res = pg_exec($con,$sql);

		if(pg_numrows($res)>0){
			for($x=0;$x<pg_numrows($res);$x++){
				$familia   = pg_result($res,$x,familia);
				$origem    = pg_result($res,$x,origem);
				$qtd       = pg_result($res,$x,qtd);
				$vlr_pecas = pg_result($res,$x,vlr_pecas);
				$vlr_mo    = pg_result($res,$x,vlr_mo);
				
				$vet_qtd[$familia][$origem]  = $qtd;
				$vet_pecas[$familia][$origem]= number_format($vlr_pecas,2, ',', '');
				$vet_mo[$familia][$origem]   = number_format($vlr_mo,2, ',', '');
			}

			$sql = "SELECT familia, descricao, bosch_cfa
					FROM   tbl_familia
					WHERE fabrica = $login_fabrica;";

			$res = pg_exec($con,$sql);

			if(pg_numrows($res)>0){
				for($x=1; $x < pg_numrows($res); $x++){
					$bosch_cfa = pg_result($res,$x,bosch_cfa);
					$descricao = pg_result($res,$x,descricao);
					$familia   = pg_result($res,$x,familia);
					$cor = ($x % 2) ? "#F7F5F0" : "#F1F4FA";

					echo "<tr bgcolor='$cor'>";
					echo "<TD colspan='1' nowrap align='left'>";
					echo "$bosch_cfa - $descricao";
					echo "</td>";
					
					if(strlen($vet_qtd[$familia]["Nac"])==0){
						$vet_qtd[$familia]["Nac"]="0,00";
					}
					if(strlen($vet_pecas[$familia]["Nac"])==0){
						$vet_pecas[$familia]["Nac"]="0,00";
					}
					if(strlen($vet_mo[$familia]["Nac"])==0){
						$vet_mo[$familia]["Nac"]="0,00";
					}
					if(strlen($vet_qtd[$familia]["Imp"])==0){
						$vet_qtd[$familia]["Imp"]="0,00";
					}
					if(strlen($vet_pecas[$familia]["Imp"])==0){
						$vet_pecas[$familia]["Imp"]="0,00";
					}
					if(strlen($vet_mo[$familia]["Imp"])==0){
						$vet_mo[$familia]["Imp"]="0,00";
					}

					
					echo "<TD colspan='1' nowrap align='right'>R$". $vet_qtd[$familia]["Nac"] ."</td>";
					echo "<TD colspan='1' nowrap align='right'>R$". $vet_pecas[$familia]["Nac"]."</td>";
					echo "<TD colspan='2' nowrap align='right'>R$". $vet_mo[$familia]["Nac"]."</td>";


					echo "<TD colspan='1' nowrap align='right'>R$". $vet_qtd[$familia]["Imp"] ."</td>";
					echo "<TD colspan='1' nowrap align='right'>R$". $vet_pecas[$familia]["Imp"]."</td>";
					echo "<TD colspan='2' nowrap align='right'>R$". $vet_mo[$familia]["Imp"]."</td>";				
					echo "</tr>";



				}
			}
		}
	echo "</table>";
	}
	else echo '<div id="erro_msg" style="display:none;width:700px;margin:auto;" class="msg_erro">' . $erro . '</div>';
}
?>

<script type="text/javascript">
	$("#erro_msg").appendTo("#msg").fadeIn("slow");
</script>

<br>

<? include "rodape.php" ?>
