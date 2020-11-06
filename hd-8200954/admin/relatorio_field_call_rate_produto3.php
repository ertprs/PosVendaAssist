<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include "autentica_admin.php";

include "funcoes.php";

$msg = "";

$meses = array (1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);

##### GERAR ARQUIVO EXCEL #####
if ($acao == "RELATORIO") {
	$produto_referencia = trim($_GET["produto_referencia"]);
	$produto_descricao  = trim($_GET["produto_descricao"]);
	$x_data_inicial     = trim($_GET["data_inicial"]);
	$x_data_final       = trim($_GET["data_final"]);
	
	$sql =	"SELECT TO_CHAR(tbl_os.data_digitacao,'YYYY') AS data_digitacao_ano ,
					TO_CHAR(tbl_os.data_digitacao,'MM')   AS data_digitacao_mes ,
					COUNT(tbl_os.os)                      AS qtde_os
			FROM tbl_os
			JOIN tbl_produto USING (produto)
			WHERE tbl_os.fabrica = $login_fabrica
			AND   tbl_os.data_digitacao BETWEEN '$x_data_inicial' AND '$x_data_final'";
	if (strlen($produto_referencia) > 0)
		$sql .= " AND tbl_produto.referencia = '$produto_referencia'";
	if (strlen($produto_descricao) > 0)
		$sql .= " AND tbl_produto.descricao ILIKE '%$produto_descricao%'";
	$sql .=	" GROUP BY  data_digitacao_ano,
						data_digitacao_mes
			ORDER BY data_digitacao_ano ASC,
					 data_digitacao_mes ASC;";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		flush();
		
		$data = date("Y_m_d-H_i_s");
		
		$arq = fopen("/tmp/assist/field-call-rate-produto3-$login_fabrica-$data.html","w");
		
		fputs($arq,"<html>");
		fputs($arq,"<head>");
		fputs($arq,"<title>FIELD CALL-RATE PRODUTO 3 - ".date("d/m/Y H:i:s"));
		fputs($arq,"</title>");
		fputs($arq,"</head>");
		fputs($arq,"<body>");
		
		fputs($arq,"<table border='1' cellpadding='2' cellspacing='0'>");
		fputs($arq,"<tr>");
		fputs($arq,"<td colspan='2'><font face='Verdana, Tahoma, Arial' size='2'><b> &nbsp; Aparelho: $produto_referencia - $produto_descricao &nbsp; </b></font></td>");
		fputs($arq,"</tr>");
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$data_ano = pg_result($res,$i,data_digitacao_ano);
			$data_mes = pg_result($res,$i,data_digitacao_mes);
			$qtde_os  = pg_result($res,$i,qtde_os);
			
			if ($data_mes_anterior != $data_mes && $data_ano_anterior != $data_ano) {
				fputs($arq,"<tr>");
				fputs($arq,"<td colspan='2'><font face='Verdana, Tahoma, Arial' size='2'><b> &nbsp; Ano: $data_ano &nbsp; </b></font></td>");
				fputs($arq,"</tr>");
			}
			
			fputs($arq,"<tr>");
			fputs($arq,"<td nowrap align='left'><font face='Verdana, Tahoma, Arial' size='2'> &nbsp; " . $meses[intval($data_mes)] . " &nbsp; </font></td>");
			fputs($arq,"<td nowrap align='left'><font face='Verdana, Tahoma, Arial' size='2'> &nbsp; " . $qtde_os . " &nbsp; </font></td>");
			fputs($arq,"</tr>");
			
			$data_mes_anterior = $data_mes;
			$data_ano_anterior = $data_ano;
		}
		fputs($arq,"</table>");
		fputs($arq,"</body>");
		fputs($arq,"</html>");
		fclose($arq);
	}
	
	echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/xls/field-call-rate-produto3-$login_fabrica-$data.xls /tmp/assist/field-call-rate-produto3-$login_fabrica-$data.html`;
	
	echo "<br>";
	echo "<p align='center'><font face='Verdana, Tahoma, Arial' size='2' color='#000000'><b>Relatório gerado com sucesso!<br><a href='xls/field-call-rate-produto3-$login_fabrica-$data.xls' target='_blank'>Clique aqui</a> para fazer o download do arquivo em EXCEL.<br>Você poderá ver, imprimir e salvar a tabela para consultas off-line.</b></font></p>";
	exit;
}

if (strlen($acao) > 0) {

	##### Pesquisa entre datas #####
	$x_data_inicial = trim($_POST["data_inicial"]);
	$x_data_final   = trim($_POST["data_final"]);
	if ($x_data_inicial != "dd/mm/aaaa" && $x_data_final != "dd/mm/aaaa") {

		if (strlen($x_data_inicial) > 0) {
			$x_data_inicial = fnc_formata_data_pg($x_data_inicial);
			$x_data_inicial = str_replace("'", "", $x_data_inicial);
			$dia_inicial    = substr($x_data_inicial, 8, 2);
			$mes_inicial    = substr($x_data_inicial, 5, 2);
			$ano_inicial    = substr($x_data_inicial, 0, 4);
			$data_inicial = date("01/m/Y H:i:s", mktime(0, 0, 0, $mes_inicial, $dia_inicial, $ano_inicial));
		}else{
			$msg .= " Preencha o campo Data Inicial para realizar a pesquisa. ";
		}

		if (strlen($x_data_final) > 0) {
			$x_data_final = fnc_formata_data_pg($x_data_final);
			$x_data_final = str_replace("'", "", $x_data_final);
			$dia_final    = substr($x_data_final, 8, 2);
			$mes_final    = substr($x_data_final, 5, 2);
			$ano_final    = substr($x_data_final, 0, 4);
			$data_final   = date("t/m/Y H:i:s", mktime(23, 59, 59, $mes_final, $dia_final, $ano_final));
		}else{
			$msg .= " Preencha o campo Data Final para realizar a pesquisa. ";
		}
	}else{
		$msg .= " Informe as datas corretas para realizar a pesquisa. ";
	}

	##### Pesquisa de produto #####
	$produto_referencia = trim($_POST["produto_referencia"]);
	$produto_descricao  = trim($_POST["produto_descricao"]);

	if (strlen($produto_referencia) == 0 && strlen($produto_descricao) == 0) {
		$msg .= " Informe o produto para realizar a pesquisa. ";
	}
	$tipo_os = $_POST['tipo_os'];


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

<!--
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->
<? include "javascript_calendario.php"; //adicionado por Fabio 10-10-2007 ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>


<script language="JavaScript">
function GerarRelatorio (produto_referencia, produto_descricao, data_inicial, data_final) {
	var largura  = 350;
	var tamanho  = 200;
	var lar      = largura / 2;
	var tam      = tamanho / 2;
	var esquerda = (screen.width / 2) - lar;
	var topo     = (screen.height / 2) - tam;
	var link = '<?echo $PHP_SELF?>?acao=RELATORIO&produto_referencia=' + produto_referencia + '&produto_descricao=' + produto_descricao + '&data_inicial=' + data_inicial + '&data_final=' + data_final;
	window.open(link, "janela", "toolbar=no, location=no, status=yes, menubar=no, scrollbars=no, directories=no, resizable=no, width=" + largura + ", height=" + tamanho + ", top=" + topo + ", left=" + esquerda + "");
}
</script>

<br>


<? 
//Variavel escolha serve para selecionar entre 'data_digitacao' ou 'finalizada' na pesquisa.
//Modificado por Fernando 28/09
$escolha = trim($_POST['data_filtro']); 

?>

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
		<td width="10">&nbsp;</td>
		<td align='left'>Data Inicial</td>
		<td align='left'>Data Final</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td>
			<input type="text" name="data_inicial" id="data_inicial" size="13" maxlength="10" value="<? if (strlen($data_inicial) > 0) echo substr($data_inicial,0,10); else echo "dd/mm/aaaa"; ?>" class="frm">
		</td>
		<td>
			<input type="text" name="data_final" id="data_final" size="13" maxlength="10" value="<? if (strlen($data_final) > 0) echo substr($data_final,0,10); else echo "dd/mm/aaaa"; ?>"  class="frm">
		</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td align='left'>Referência do Produto</td>
		<td align='left'>Descrição do Produto</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td align='left'>
			<input type="text" name="produto_referencia" size="15" value="<?echo $produto_referencia?>" class="frm">
			<img src="imagens/lupa.png" align="absmiddle" onclick="fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao, 'referencia')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">
		</td>
		<td align='left''>
			<input type="text" name="produto_descricao" size="20" value="<?echo $produto_descricao?>" class="frm">
			<img src="imagens/lupa.png" align="absmiddle" onclick="fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao, 'descricao')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">
		</td>
		<td width="10">&nbsp;</td>
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
<? if($login_fabrica == 24){ ?>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td >&nbsp;</td>
		<td >Tipo para filtrar:</td>
		<td align='left'><input type="radio" name="tipo_os" value="C"<? if($tipo_os=='C')echo "CHECKED"; ?> >Consumidor<br>
		<input type="radio" name="tipo_os" value="R"<? if($tipo_os=='R')echo "CHECKED"; ?>>Revenda</td>
		<td >&nbsp;</td>
	</tr>
<? } ?>
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


if (strlen($acao) > 0 && strlen($msg) == 0) {
	$x_data_inicial = date("Y-m-01 H:i:s", mktime(0, 0, 0, $mes_inicial, $dia_inicial, $ano_inicial));
	$x_data_final   = date("Y-m-t H:i:s", mktime(23, 59, 59, $mes_final, $dia_final, $ano_final));
	/* TAKASHI 15-01 NAO É PARA USAR ILIKE, CASO NAO ESTEJA OK, VOLTE AO NORMAL. HD 877 falava da divergencia de fcr 1 e 3, porem nao vi este caso, so tirei ilike
	$sql =	"SELECT TO_CHAR(tbl_os.$escolha,'YYYY') AS data_digitacao_ano ,
					TO_CHAR(tbl_os.$escolha,'MM')   AS data_digitacao_mes ,
					COUNT(tbl_os.os)                      AS qtde_os
			FROM tbl_os
			JOIN tbl_produto USING (produto)
			WHERE tbl_os.fabrica = $login_fabrica
			AND   tbl_os.$escolha BETWEEN '$x_data_inicial' AND '$x_data_final'";
	if (strlen($produto_referencia) > 0)
		$sql .= " AND tbl_produto.referencia = '$produto_referencia'";
	if (strlen($produto_descricao) > 0)
		$sql .= " AND tbl_produto.descricao ILIKE '%$produto_descricao%'";
	$sql .=	" GROUP BY  data_digitacao_ano,
						data_digitacao_mes
			ORDER BY data_digitacao_ano ASC,
					 data_digitacao_mes ASC;";
*/
	if(strlen($produto_referencia)>0){
		$sql_produto = "SELECT produto from tbl_produto where referencia='$produto_referencia'";
		$res_produto = pg_exec($con, $sql_produto);
		$produto = pg_result($res_produto,0,0);
	}
	$sql =	"SELECT TO_CHAR(tbl_os.$escolha,'YYYY') AS data_digitacao_ano ,
					TO_CHAR(tbl_os.$escolha,'MM')   AS data_digitacao_mes ,
					COUNT(tbl_os.os)                      AS qtde_os
			FROM tbl_os
			JOIN tbl_produto USING (produto)
			WHERE tbl_os.fabrica = $login_fabrica
			AND   tbl_os.$escolha BETWEEN '$x_data_inicial' AND '$x_data_final'";
			if(strlen($produto)>0){
				$sql .= " AND tbl_produto.produto=$produto ";
			}
			if(strlen($tipo_os)>0){
				$sql .= " AND tbl_os.consumidor_revenda = '$tipo_os' ";
			}
	$sql .=	" GROUP BY  data_digitacao_ano,
						data_digitacao_mes
			ORDER BY data_digitacao_ano ASC,
					 data_digitacao_mes ASC;";




//if (getenv("REMOTE_ADDR") == "201.27.215.6") echo nl2br($sql);
	
	$res = pg_exec($con,$sql);
	
	if (pg_numrows($res) > 0) {
		echo "<table border='1' align='center' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
		
		echo "<tr height='15' class='Titulo'>";
		echo "<td colspan='2'> Aparelho: $produto_referencia - $produto_descricao</td>";
		echo "</tr>";
		
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$data_ano = pg_result($res,$i,data_digitacao_ano);
			$data_mes = pg_result($res,$i,data_digitacao_mes);
			$qtde_os  = pg_result($res,$i,qtde_os);
			
			if ($data_mes_anterior != $data_mes && $data_ano_anterior != $data_ano) {
				echo "<tr class='Titulo' height='15'>";
				echo "<td colspan='2'>Ano: $data_ano</td>";
				echo "</tr>";
			}
			
			echo "<tr class='Conteudo' height='15' bgcolor='#F1F4FA'>";
			echo "<td nowrap align='left'>" . $meses[intval($data_mes)] . "</td>";
			echo "<td nowrap align='left'>$qtde_os</td>";
			echo "</tr>";
			
			$data_mes_anterior = $data_mes;
			$data_ano_anterior = $data_ano;
		}
		echo "</table>";
		echo "<br><a href=\"javascript: GerarRelatorio ('$produto_referencia', '$produto_descricao', '$x_data_inicial', '$x_data_final');\"><font size='2'>Clique aqui para gerar arquivo do EXCEL</font></a><br>";
	}
}
echo "<br>";

include "rodape.php";
?>
