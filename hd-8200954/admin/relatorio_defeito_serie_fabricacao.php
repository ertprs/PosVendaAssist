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

	$familia = $_POST['familia'];
	
	##### Pesquisa de data #####
	$pesquisa_mes = trim($_POST["pesquisa_mes"]);
	$pesquisa_ano = trim($_POST["pesquisa_ano"]);

	if (strlen($pesquisa_mes) == 0) $msg .= " Informe o mês para realizar a pesquisa. ";
	if (strlen($pesquisa_ano) == 0 && strlen($msg) == 0) $msg .= " Informe o ano para realizar a pesquisa. ";

	if (strlen($msg) == 0) {
		if (strlen($pesquisa_ano) == 2 OR strlen($pesquisa_ano) == 4) {
			if ($pesquisa_ano >= 50 && strlen($pesquisa_ano) == 2) $pesquisa_ano = "19" . $pesquisa_ano;
			elseif ($pesquisa_ano < 50 && strlen($pesquisa_ano) == 2) $pesquisa_ano = "20" . $pesquisa_ano;
		}else{
			$msg .= " Informe o ano para realizar a pesquisa. ";
		}
	}
	$tipo_os = $_POST['tipo_os'];
	
	if(strlen($familia) > 0){
		$sql = "SELECT familia FROM tbl_familia WHERE fabrica=$login_fabrica AND familia=$familia";
		$res = pg_exec($con,$sql);
		$num = pg_numrows($res);
		if(!$num){
			$msg .= " Família Selecionada é inválida. ";
		}
	}elseif(strlen($msg) == 0){
		$msg .= " Informe a família para realizar a pesquisa. ";
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
.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}
table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.titulo_coluna {
    background-color: #596D9B;
    color: #FFFFFF;
    font: bold 11px "Arial";
    text-align: center;
}
.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.espaco{
	padding-left:140px;
}
.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}
</style>

<? include "javascript_pesquisas.php"; ?>

<!--
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->

<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>


<script language="javascript" >
function AbrePeca(produto,n_serie){
	janela = window.open("relatorio_defeito_serie_fabricacao_os.php?produto=" + produto + "&nserie=" + n_serie + "&tipo_os=<?echo $tipo_os;  ?>","serie",'resizable=1,scrollbars=yes,width=750,height=450,top=0,left=0');
	janela.focus();
}
</script>
<link rel="stylesheet" href="js/blue/style.css" type="text/css" id="" media="print, projection, screen" />
<script type="text/javascript" src="js/jquery-1.1.4.pack.js"></script> 
<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script> 
<script>
$(document).ready(function(){
	$.tablesorter.defaults.widgets = ['zebra'];
	$("#relatorio").tablesorter();

});
</script>

<? if (strlen($msg) > 0) { ?>
<table width="700px" border="0" cellspacing="1" align="center" class="error">
	<tr>
		<td class="msg_erro"><?php echo $msg; ?></td>
	</tr>
</table>
<? } ?>

<form name="frm_relatorio" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="acao">


<table width="700px" align="center" border="0" cellspacing='1' class='formulario'>

<caption class="titulo_tabela">Parâmetros de Pesquisa</caption>

<TBODY>
<tr>
	<td align='left' class="espaco" width="230px">
		Mês<br />
		<select name="pesquisa_mes" size="1" class="frm">
			<option value=""></option>
			<?
			$meses = array (1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
			for ($i = 1 ; $i <= count($meses) ; $i++) {
				echo "<option value='" . str_pad($i, 2, "0", STR_PAD_LEFT) . "'";
				if ( $pesquisa_mes == str_pad($i, "0", STR_PAD_LEFT) ) echo " selected";
				echo ">" . $meses[$i] . "</option>";
			}
			?>
		</select>
	</td>
	<td align='left'>
		Ano<br />
		<select name="pesquisa_ano" size="1" class="frm">
			<option value=""></option>
			<?
			for ($i = 2004 ; $i <= date("Y") ; $i++) {
				echo "<option value='$i'";
				if ($pesquisa_ano == $i) echo " selected";
					echo ">$i</option>";
			}
			?>
		</select>
	</td>
</tr>
<tr>
	<td align='center'>
		
	</td>
	<td align='center'>
		
	</td>
</tr>
<tr>
	<td colspan="2">&nbsp;</td>
</tr>

<? if($login_fabrica==24){ ?>
	<tr>
		<td align='left' class="espaco">
			Por tipo<br>
			<select name="tipo_os" size="1"  style='width:200px' class='frm'>
			<?php
			if($tipo_os == 'C'){	
			?>
				<option value="C">Consumidor</option>
				<option value="R">Revenda</option>
				<option value=""></option>
			<?php
			}
			if($tipo_os == 'R'){	
			?>
				<option value="R">Revenda</option>
				<option value="C">Consumidor</option>
				<option value=""></option>
			<?php
			}
			if($tipo_os == ''){	
			?>
				<option value=""></option>
				<option value="R">Revenda</option>
				<option value="C">Consumidor</option>
			<?php
			}
			?>
			</select>
		</td>
		<td align='left' colspan="2">
			Familia<br>
			<select name='familia' class='frm' style='width:200px'>
				<option></option>
				<?
				$sql = "SELECT familia, descricao
						FROM tbl_familia 
						WHERE fabrica = $login_fabrica 
						ORDER BY descricao";

				$res = pg_exec($con, $sql);
				if(pg_numrows($res)>0){
					for ($x=0;pg_numrows($res)>$x;$x++){
						$xfamilia = pg_result($res,$x,familia);
						$descricao = pg_result($res, $x,descricao);
						echo "<option value='$xfamilia'"; if($familia==$xfamilia){echo "SELECTED";} echo ">$descricao</option>";
					}
				}
				?>
			</select>
		</td>
	</tr>
<? }else{ ?>
	<tr>
		<td align='left' class="espaco" colspan="2">
			Familia<br>
			<select name='familia' class='frm' style='width:200px'>
			<option></option>
			<?
			$sql = "SELECT familia, descricao
					FROM tbl_familia 
					WHERE fabrica = $login_fabrica 
					ORDER BY descricao";

			$res = pg_exec($con, $sql);
			if(pg_numrows($res)>0){
				for ($x=0;pg_numrows($res)>$x;$x++){
					$xfamilia = pg_result($res,$x,familia);
					$descricao = pg_result($res, $x,descricao);
					echo "<option value='$xfamilia'"; if($familia==$xfamilia){echo "SELECTED";} echo ">$descricao</option>";
				}
			}
			?>
		</select>
		</td>
	</tr>
<?php }?>
<tr>
	<td colspan="2">&nbsp;</td>
</tr>
<tr>
	<td colspan="2" align="center">
		<input type="button" onclick="document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor:pointer " alt="Clique AQUI para pesquisar" value="Pesquisar">
		<br><br>
	</td>
</tr>
</tbody>
</table>
</form>

<br>

<?
flush();

if (strlen($acao) > 0 && strlen($msg) == 0 ) {
	$data_inicial = date("Y-m-01", mktime(0, 0, 0, $pesquisa_mes, 1, $pesquisa_ano));
	$data_final   = date("Y-m-t", mktime(23, 59, 59, $pesquisa_mes, 1, $pesquisa_ano));	
	
	$pesquisa_mes;
	$pesquisa_ano = substr($pesquisa_ano, 2, 2);
	$radical_n_serie = $pesquisa_mes.$pesquisa_ano;
//echo "n serie $radical_n_serie<bR><BR>";	
	$familia = $_POST['familia'];
	if (strlen ($tipo_os)  > 0) 
		$cond_5 = " AND tbl_os.consumidor_revenda = '$tipo_os' ";
	
	//$sql_cond_suggar = ($login_fabrica==24) ? "AND (tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final')" : null;
	$sql_cond_suggar = ($login_fabrica==24) ? "AND tbl_os.data_digitacao >= '$data_inicial'" : null;

	$sql = "SELECT tbl_os.produto,
			tbl_produto.referencia, tbl_produto.descricao,
			count(distinct tbl_os.os) as qtde
		FROM tbl_os
		JOIN tbl_os_extra using(os)
		JOIN tbl_produto using(produto)
		JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
		join tbl_os_item    ON tbl_os_item.os_produto = tbl_os_produto.os_produto
		WHERE tbl_os.fabrica= $login_fabrica
		AND tbl_produto.familia = $familia
		AND tbl_os.serie LIKE '$radical_n_serie%'
		$sql_cond_suggar
		AND tbl_os.solucao_os <> 127
		AND tbl_os_extra.extrato IS NOT NULL
		$cond_5
		GROUP BY tbl_os.produto,tbl_produto.referencia, tbl_produto.descricao
		ORDER BY qtde desc";
	//echo nl2br($sql); die;
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<center><div style='width: 700px;'><TABLE width='700' border='0' cellspacing='1' align='center' name='relatorio' id='relatorio' class='tablesorter tabela'>";
		echo "<thead>";
		echo "<tr height='15' class='titulo_coluna'>";
		echo "<td>N° Série</td>";
		echo "<td>Produto</td>";
		echo "<td align='center'width='120'>Ocorrência</td>";		
		echo "</tr>";
		echo "</thead>";
		echo "<tbody>";

		for($x=0; pg_numrows($res) > $x;$x++){

			$produto = pg_result($res,$x,produto);
			$produto_referencia = pg_result($res,$x,referencia);
			$produto_descricao  = pg_result($res,$x,descricao);
			$qtde               = pg_result($res,$x,qtde);
			
			$cor = ($x % 2) ? "#F7F5F0" : "#F1F4FA";
	
			echo "<tr height='15' bgcolor='$cor'>";
	
			echo "<td><font size='1'>".$radical_n_serie. "XXXXXXXX" ."</font></td>";
			echo "<td><font size='1'><a href='javascript:AbrePeca(\"$produto\",\"$radical_n_serie\")'>$produto_referencia - $produto_descricao</a></font></td>";
			echo "<td align='center'><font size='1'>$qtde</font></td>";
			echo "</tr>";

		}
		echo "</tbody>";
		echo "</table></div>";
	}
}
echo "<br>";

include "rodape.php";
?>
