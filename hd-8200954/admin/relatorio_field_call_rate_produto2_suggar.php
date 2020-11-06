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
	$produto      = trim($_GET["produto"]);
	$data_inicial = trim($_GET["data_inicial"]);
	$data_final   = trim($_GET["data_final"]);
	
	$sql =	"SELECT fn_field_call_rate($login_fabrica, $produto, '$data_inicial', '$data_final');";
	$res1 = pg_exec($con,$sql);
	
	$sql =	"SELECT tbl_defeito.defeito   ,
					tbl_defeito.descricao 
			FROM tbl_defeito
			WHERE tbl_defeito.fabrica = $login_fabrica
			ORDER BY tbl_defeito.descricao;";
	$res2 = pg_exec($con,$sql);
	$colspan = (pg_numrows($res2) * 2) + 2;
	
	if (pg_numrows($res2) > 0) {
		flush();
		
		$data = date("Y_m_d-H_i_s");
		
		$arq = fopen("/tmp/assist/field-call-rate-produto2-$login_fabrica-$data.html","w");
		fputs($arq,"<html>");
		fputs($arq,"<head>");
		fputs($arq,"<title>FIELD CALL-RATE PRODUTO 2 - ".date("d/m/Y H:i:s"));
		fputs($arq,"</title>");
		fputs($arq,"</head>");
		fputs($arq,"<body>");
		
		$sqlP = "SELECT tbl_produto.referencia, tbl_produto.descricao
				FROM tbl_produto
				JOIN tbl_linha USING (linha)
				WHERE tbl_produto.produto = $produto
				AND   tbl_linha.fabrica   = $login_fabrica;";
		$resP = pg_exec($con,$sqlP);
		
		if (pg_numrows($resP) == 1) {
			fputs($arq,"<table border='1'>");
			fputs($arq,"<tr>");
			fputs($arq,"<td align='center' colspan='$colspan'><font face='Verdana, Tahoma, Arial' size='2'><b>" . trim(pg_result($resP,0,referencia)) ." - " . trim(pg_result($resP,0,descricao)) ."</b></font></td>");
			fputs($arq,"</tr>");
		}
		
		$sql =	"SELECT distinct *
				FROM    field_xx
				ORDER BY    data_digitacao_ano ASC,
							data_digitacao     ASC,
							qtde_total_defeito DESC,
							peca_referencia    ASC;";
		$res3 = pg_exec($con,$sql);
		
		$matriz_defeitos = array();
		$matriz_def_total = array();
		
		if (pg_numrows($res3) > 0) {
			flush();
			
			fputs($arq,"<tr><td colspan='$colspan'>&nbsp;</td></tr>");
			fputs($arq,"<tr><td colspan='$colspan'>&nbsp;</td></tr>");
			
			for ($i = 0 ; $i < pg_numrows($res3) ; $i++) {
				$data_digitacao     = pg_result($res3,$i,data_digitacao);
				$data_digitacao_ano = pg_result($res3,$i,data_digitacao_ano);
				$produto_referencia = pg_result($res3,$i,produto_referencia);
				$produto_descricao  = pg_result($res3,$i,produto_descricao);
				$peca_referencia    = pg_result($res3,$i,peca_referencia);
				$peca_descricao     = pg_result($res3,$i,peca_descricao);

				if ($data_digitacao_anterior != $data_digitacao) {
					fputs($arq,"<tr>");
					fputs($arq,"<td align='center' colspan='$colspan'><font face='Verdana, Tahoma, Arial' size='2'><b> &nbsp; " . $meses[intval($data_digitacao)] . "/$data_digitacao_ano &nbsp; </b></font></td>");
					fputs($arq,"</tr>");
					fputs($arq,"<tr>");
					fputs($arq,"<td align='center'><font face='Verdana, Tahoma, Arial' size='2'><b> &nbsp; PEÇA &nbsp; </b></font></td>");
					for ($j = 0 ; $j < pg_numrows($res2) ; $j++) {
						fputs($arq,"<td align='center' nowrap><font face='Verdana, Tahoma, Arial' size='2'><b> &nbsp; " . pg_result($res2,$j,descricao) . " &nbsp; </b></font></td>");
						fputs($arq,"<td align='center' nowrap><font face='Verdana, Tahoma, Arial' size='2'><b> &nbsp; % &nbsp; </b></font></td>");
					}
					fputs($arq,"<td align='center'><font face='Verdana, Tahoma, Arial' size='2'><b> &nbsp; TOTAL DE DEFEITOS &nbsp; </b></font></td>");
					fputs($arq,"</tr>");
				}
				
				fputs($arq,"<tr>");
				fputs($arq,"<td nowrap align='left'><font face='Verdana, Tahoma, Arial' size='2'> &nbsp; $peca_referencia - $peca_descricao &nbsp; </font></td>");
				
				for ($j = 0 ; $j < pg_numrows($res2) ; $j++) {
					$defeito    = "d"    . @pg_result($res2,$j,defeito);
					$percentual = "perc" . @pg_result($res2,$j,defeito);

					$defe = @pg_result($res3,$i,$defeito);
					$perc = @pg_result($res3,$i,$percentual);

					$m_defeito    = "d" . @pg_result($res2,$j,defeito);
					$m_qtddefeito = @pg_result($res3,$i,$defeito);

					if (strlen($m_qtddefeito) == 0) $m_qtddefeito = 0;
					if ( array_key_exists($peca_referencia.$m_defeito, $matriz_defeitos) ) {
						$matriz_defeitos[$peca_referencia.$m_defeito] = $matriz_defeitos[$peca_referencia.$m_defeito] + $m_qtddefeito;
					}else{
						$matriz_defeitos[$peca_referencia.$m_defeito] = $m_qtddefeito;
					}
					
					if ( array_key_exists($peca_referencia, $matriz_def_total) ) {
						$matriz_def_total[$peca_referencia] = $matriz_def_total[$peca_referencia] + $m_qtddefeito;
					}else{
						$matriz_def_total[$peca_referencia] = $m_qtddefeito;
					}
					
					if (strlen($defe) == 0) $defe = 0;
					fputs($arq,"<td align='center'><font face='Verdana, Tahoma, Arial' size='2'> &nbsp; $defe &nbsp; </font></td>");
					fputs($arq,"<td align='right'><font face='Verdana, Tahoma, Arial' size='2'> &nbsp; ". number_format ($perc,2,",",".") ." % &nbsp; </font></td>");
				}
				
				$qtde_total_defeito = pg_result($res3,$i,qtde_total_defeito);
				
				fputs($arq,"<td align='right'><font face='Verdana, Tahoma, Arial' size='2'> &nbsp; $qtde_total_defeito &nbsp; </font></td>");
				fputs($arq,"</tr>");
				
				$data_digitacao_anterior     = $data_digitacao;
				$data_digitacao_ano_anterior = $data_digitacao_ano;
			}
		}
	}
	
	fputs($arq,"<tr><td colspan='$colspan'>&nbsp;</td></tr>");
	fputs($arq,"<tr><td colspan='$colspan'>&nbsp;</td></tr>");
	fputs($arq,"<tr><td colspan='$colspan' align='center'><font face='Verdana, Tahoma, Arial' size='2'><b> &nbsp; TOTAL GERAL &nbsp; </b></font></td></tr>");
	
	arsort($matriz_def_total);
	reset($matriz_def_total);
	$matriz_pecas_total = array_keys($matriz_def_total);
	$matriz_pecas = array_keys($matriz_defeitos);
	$k = 1;
	foreach ($matriz_pecas_total as $valor) {
		if ($k == 1) {
			fputs($arq,"<tr>");
			fputs($arq,"<td align='center'><font face='Verdana, Tahoma, Arial' size='2'><b> &nbsp; PEÇA &nbsp; </b></font></td>");
			$sql =	"SELECT tbl_defeito.defeito   ,
							tbl_defeito.descricao 
					FROM tbl_defeito
					WHERE tbl_defeito.fabrica = $login_fabrica
					ORDER BY tbl_defeito.descricao;";
			$res = pg_exec($con,$sql);
			if (pg_numrows($res) > 0) {
				for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
					fputs($arq,"<td align='center' nowrap><font face='Verdana, Tahoma, Arial' size='2'><b> &nbsp; " . pg_result($res,$i,descricao) . " &nbsp; </b></font></td>");
					fputs($arq,"<td align='center' nowrap><font face='Verdana, Tahoma, Arial' size='2'><b> &nbsp; % &nbsp; </b></font></td>");
				}
			}
			fputs($arq,"<td align='center' nowrap><font face='Verdana, Tahoma, Arial' size='2'><b> &nbsp; QTDE TOTAL &nbsp; </b></font></td>");
			fputs($arq,"</tr>");
		}
		
		$peca_referencia = $valor;
		
		fputs($arq,"<tr>");
		$qtde_total = 0;
		
		$sql =	"SELECT referencia ,
						descricao  
				FROM  tbl_peca
				WHERE fabrica             = $login_fabrica
				AND   referencia_pesquisa = '$peca_referencia';";
		$res = pg_exec($con, $sql);
		if (pg_numrows($res) > 0) {
			fputs($arq,"<td nowrap align='left'><font face='Verdana, Tahoma, Arial' size='2'> &nbsp; " . pg_result($res,0,referencia) . " - " . pg_result($res,0,descricao) . " &nbsp; </font></td>");
		}
		
		$sql =	"SELECT tbl_defeito.defeito
				FROM tbl_defeito
				WHERE tbl_defeito.fabrica = $login_fabrica
				ORDER BY tbl_defeito.descricao;";
		$res = pg_exec($con, $sql);
		if (pg_numrows($res) > 0) {
				for ($j = 0 ; $j < pg_numrows($res) ; $j++) {
					$defeito = pg_result($res,$j,defeito);
					fputs($arq,"<td align='center' nowrap><font face='Verdana, Tahoma, Arial' size='2'> &nbsp; " . $matriz_defeitos[$peca_referencia."d".$defeito] . " &nbsp; </font></td>");
					fputs($arq,"<td align='right' nowrap><font face='Verdana, Tahoma, Arial' size='2'> &nbsp; " . number_format ((($matriz_defeitos[$peca_referencia."d".$defeito] * 100) / $matriz_def_total[$valor]),2,",",".") . " % &nbsp; </font></td>");
				}
			}
		fputs($arq,"<td align='right'><font face='Verdana, Tahoma, Arial' size='2'> &nbsp; " . $matriz_def_total[$valor] . " &nbsp; </font></td>");
		fputs($arq,"</tr>");
		$k++;
	}
	fputs($arq,"</table>");
	fputs($arq,"</body>");
	fputs($arq,"</html>");
	fclose($arq);

	echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/xls/field-call-rate-produto2-$login_fabrica-$data.xls /tmp/assist/field-call-rate-produto2-$login_fabrica-$data.html`;
	
	echo "<br>";
	echo "<p align='center'><font face='Verdana, Tahoma, Arial' size='2' color='#000000'><b>Relatório gerado com sucesso!<br><a href='xls/field-call-rate-produto2-$login_fabrica-$data.xls' target='_blank'>Clique aqui</a> para fazer o download do arquivo em EXCEL.<br>Você poderá ver, imprimir e salvar a tabela para consultas off-line.</b></font></p>";
	exit;
}

if (strlen($acao) > 0) {

	##### Pesquisa entre datas #####
	$x_data_inicial = trim($_POST["data_inicial"]);
	$x_data_final   = trim($_POST["data_final"]);
	$itemsbox       = trim($_POST["itemsbox"]);
	$itemsbox = trim($itemsbox);
	$itemsbox = str_replace("\r\n","' , '",$itemsbox);
	$itemsbox = "'".$itemsbox."'";
$produto_referencia = $itemsbox;
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
	
	if (strlen($produto_referencia) > 0) {
		$sql =	"SELECT tbl_produto.produto    ,
						tbl_produto.referencia ,
						tbl_produto.descricao  
				FROM tbl_produto
				JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
				WHERE tbl_linha.fabrica = $login_fabrica
				AND   tbl_produto.ativo IS TRUE";
		if (strlen($produto_referencia) > 0) $sql .= " AND tbl_produto.referencia_pesquisa in( $produto_referencia)";
//echo "$sql";
#		if (strlen($produto_descricao) > 0)   $sql .= " AND tbl_produto.descricao = '$produto_descricao';";

		$res = pg_exec($con,$sql);
		$qtde_produto = pg_numrows($res);
		if (pg_numrows($res) > 0) {
		for($x=0; pg_numrows($res)>$x;$x++){
			$produto[$x]            = pg_result($res,$x,produto);
			$produto_referencia[$x] = pg_result($res,$x,referencia);
			$produto_descricao[$x]  = pg_result($res,$x,descricao);
		}
		}else{
			$msg .= " Produto não encontrado. ";
		}
	}else{
		$msg .= " Informe o produto para realizar a pesquisa. ";
	}
}
		for($x=0; $qtde_produto>$x;$x++){
			echo $produto[$x]          ;
			echo $produto_referencia[$x];
			echo $produto_descricao[$x] ;
		}
$layout_menu = "gerencia";
$title = "RELATÓRIO - FIELD CALL-RATE 2 : LINHA DE PRODUTO";

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

<script language="JavaScript">
function GerarRelatorio (produto, data_inicial, data_final) {
	var largura  = 350;
	var tamanho  = 200;
	var lar      = largura / 2;
	var tam      = tamanho / 2;
	var esquerda = (screen.width / 2) - lar;
	var topo     = (screen.height / 2) - tam;
	var link = '<?echo $PHP_SELF?>?acao=RELATORIO&produto=' + produto + '&data_inicial=' + data_inicial + '&data_final=' + data_final;
	window.open(link, "janela", "toolbar=no, location=no, status=yes, menubar=no, scrollbars=no, directories=no, resizable=no, width=" + largura + ", height=" + tamanho + ", top=" + topo + ", left=" + esquerda + "");
}


oldvalue = "";
function passText(passedvalue) {
  if (passedvalue != "") {
    var totalvalue = passedvalue+"\n"+oldvalue;
    document.frm_relatorio.itemsbox.value = totalvalue;
    oldvalue = document.frm_relatorio.itemsbox.value;
  }
}

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
<table width="420" border="0" cellspacing="0" cellpadding="2" align="center">
	<tr class="Titulo">
		<td colspan="5">PESQUISA</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="5">&nbsp;</td>
	</tr>
	<tr>
		<td class="Conteudo" bgcolor="#D9E2EF" style="width: 10px">&nbsp;</td>
		<td class="Conteudo" bgcolor="#D9E2EF" colspan='3' style="font-size: 10px"><center>Este relatório considera o mês inteiro de OS <br> pela data da digitação.</center></td>
		<td class="Conteudo" bgcolor="#D9E2EF" style="width: 10px">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="5">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td>Data Inicial</td>
		<td width="10">&nbsp;</td>
		<td>Data Final</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td>
			<input type="text" name="data_inicial" size="13" maxlength="10" value="<? if (strlen($data_inicial) > 0) echo substr($data_inicial,0,10); else echo "dd/mm/aaaa"; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
			<img src="imagens_admin/btn_lupa.gif" align="absmiddle" onclick="javascript: showCal('DataInicial')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">
		</td>
		<td width="10">&nbsp;</td>
		<td>
			<input type="text" name="data_final" size="13" maxlength="10" value="<? if (strlen($data_final) > 0) echo substr($data_final,0,10); else echo "dd/mm/aaaa"; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
			<img src="imagens_admin/btn_lupa.gif" align="absmiddle" onclick="javascript: showCal('DataFinal')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">
		</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="5">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td>Referência do Produto</td>
		<td width="10">&nbsp;</td>
		<td>Produto(s) Selecionado(s)</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td>
			<select MULTIPLE size="4" name='dropdownbox' style='width:120px'>
			<?  
			$sql = "SELECT tbl_produto.produto, tbl_produto.referencia 
					FROM tbl_produto
					JOIN tbl_familia using(familia)
					WHERE fabrica=24 
					AND tbl_produto.ativo='t' 
					ORDER by tbl_produto.referencia";

			$res = pg_exec($con,$sql);
			if(pg_num_rows($res)>0){
				for($x=0;pg_num_rows($res)>$x;$x++){
					$referencia = pg_result($res,$x,referencia);
					echo "<option value='$referencia'>$referencia</option>";
				}
			}
			?>
			</select>
		</td>
		<td width="10" align='center'><input type=button value="->" onClick="passText(this.form.dropdownbox.options[this.form.dropdownbox.selectedIndex].value);"></td>
		<td><textarea cols="15" rows="4" name="itemsbox"></textarea>
		</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="5">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="5"><img src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor:pointer " alt="Clique AQUI para pesquisar"></td>
	</tr>
</table>
</form>

<br>

<?
if (strlen($acao) > 0 && strlen($erro) == 0) {
	$x_data_inicial = date("Y-m-01 H:i:s", mktime(0, 0, 0, $mes_inicial, $dia_inicial, $ano_inicial));
	$x_data_final   = date("Y-m-t H:i:s", mktime(23, 59, 59, $mes_final, $dia_final, $ano_final));
for($x=0; $qtde_produto>$x;$x++){
$produto = $produto[$x];
$produto_referencia =  $produto_referencia[$x];
$produto_descricao  = $produto_descricao[$x] ;
//echo "prod:$produto a $x: $produto[$x]<BR>";
	$sql =	"SELECT fn_field_call_rate($login_fabrica, $produto, '$x_data_inicial', '$x_data_final');";
echo "$sql";
	$res1 = @pg_exec($con,$sql);
	
	$sql =	"SELECT tbl_defeito.defeito   ,
					tbl_defeito.descricao 
			FROM tbl_defeito
			WHERE tbl_defeito.fabrica = $login_fabrica
			ORDER BY tbl_defeito.descricao;";
	$res2 = pg_exec($con,$sql);
	$colspan = (pg_numrows($res2) * 2) + 2;
	
	if (pg_numrows($res2) > 0) {
		echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
		
		echo "<tr class='Titulo' height='20'>";
		echo "<td colspan='$colspan'><font size='3'>$produto_referencia - $produto_descricao</font></td>";
		echo "</tr>";
		
		$sql =	"SELECT distinct *
				FROM    field_xx
				ORDER BY    data_digitacao_ano ASC,
							data_digitacao     ASC,
							qtde_total_defeito DESC,
							peca_referencia    ASC;";
		$res3 = pg_exec($con,$sql);
		
		$matriz_defeitos = array();
		$matriz_def_total = array();
		
		if (pg_numrows($res3) > 0) {
			for ($i = 0 ; $i < pg_numrows($res3) ; $i++) {
				$data_digitacao     = pg_result($res3,$i,data_digitacao);
				$data_digitacao_ano = pg_result($res3,$i,data_digitacao_ano);
				$produto_referencia = pg_result($res3,$i,produto_referencia);
				$produto_descricao  = pg_result($res3,$i,produto_descricao);
				$peca_referencia    = pg_result($res3,$i,peca_referencia);
				$peca_descricao     = pg_result($res3,$i,peca_descricao);

				if ($data_digitacao_anterior != $data_digitacao) {
					echo "<tr height='15'><td colspan='$colspan'>&nbsp;</td></tr>";
					echo "<tr class='Titulo' height='20'>";
					echo "<td colspan='$colspan'><font size='3'>" . $meses[intval($data_digitacao)] . "/$data_digitacao_ano</font></td>";
					echo "</tr>";
					echo "<tr class='Titulo' height='15'>";
					echo "<td>PEÇA</td>";
					for ($j = 0 ; $j < pg_numrows($res2) ; $j++) {
						echo "<td nowrap>" . pg_result($res2,$j,descricao) . "</td>";
						echo "<td nowrap>&nbsp;&nbsp;%&nbsp;&nbsp;</td>";
					}
					echo "<td>TOTAL DE DEFEITOS</td>";
					echo "</tr>";
				}
				
				$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;
				
				echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
				echo "<td nowrap align='left'>$peca_referencia - $peca_descricao</td>";
				
				for ($j = 0 ; $j < pg_numrows($res2) ; $j++) {
					$defeito    = "d"    . @pg_result($res2,$j,defeito);
					$percentual = "perc" . @pg_result($res2,$j,defeito);

					$defe = @pg_result($res3,$i,$defeito);
					$perc = @pg_result($res3,$i,$percentual);

					$m_defeito    = "d" . @pg_result($res2,$j,defeito);
					$m_qtddefeito = @pg_result($res3,$i,$defeito);

					if (strlen($m_qtddefeito) == 0) $m_qtddefeito = 0;
					if ( array_key_exists($peca_referencia.$m_defeito, $matriz_defeitos) ) {
						$matriz_defeitos[$peca_referencia.$m_defeito] = $matriz_defeitos[$peca_referencia.$m_defeito] + $m_qtddefeito;
					}else{
						$matriz_defeitos[$peca_referencia.$m_defeito] = $m_qtddefeito;
					}
					
					if ( array_key_exists($peca_referencia, $matriz_def_total) ) {
						$matriz_def_total[$peca_referencia] = $matriz_def_total[$peca_referencia] + $m_qtddefeito;
					}else{
						$matriz_def_total[$peca_referencia] = $m_qtddefeito;
					}
					
					if (strlen($defe) == 0) $defe = 0;
					echo "<td align='center'>$defe</td>";
					echo "<td align='right' nowrap>". number_format ($perc,2,",",".") ." %</td>";
				}
				
				$qtde_total_defeito = pg_result($res3,$i,qtde_total_defeito);
				
				echo "<td align='right'>$qtde_total_defeito</td>";
				echo "</tr>";
				
				$data_digitacao_anterior     = $data_digitacao;
				$data_digitacao_ano_anterior = $data_digitacao_ano;
			}
			echo "</table>";
		}
	}

	echo "<br><br>";

	arsort($matriz_def_total);
	reset($matriz_def_total);
	$matriz_pecas_total = array_keys($matriz_def_total);
	$matriz_pecas = array_keys($matriz_defeitos);
	$k = 1;
	foreach ($matriz_pecas_total as $valor) {
		if ($k == 1) {
			echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
			echo "<tr class='Titulo' height='20' style='background-color: #FF0000'>";
			echo "<td>PEÇA</td>";
			$sql =	"SELECT tbl_defeito.defeito   ,
							tbl_defeito.descricao 
					FROM tbl_defeito
					WHERE tbl_defeito.fabrica = $login_fabrica
					ORDER BY tbl_defeito.descricao;";
			$res = pg_exec($con,$sql);
			if (pg_numrows($res) > 0) {
				for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
					echo "<td nowrap>" . pg_result($res,$i,descricao) . "</td>";
					echo "<td nowrap>%</td>";
				}
			}
			echo "<td nowrap>QTDE TOTAL</td>";
			echo "</tr>";
		}
		
		$peca_referencia = $valor;
		
		$cor = ($k % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;
		
		echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
		$qtde_total = 0;
		
		$sql =	"SELECT referencia ,
						descricao  
				FROM  tbl_peca
				WHERE fabrica             = $login_fabrica
				AND   referencia_pesquisa = '$peca_referencia';";
		$res = pg_exec($con, $sql);
		if (pg_numrows($res) > 0) {
			echo "<td nowrap align='left'>" . pg_result($res,0,referencia) . " - " . pg_result($res,0,descricao) . "</td>";
		}
		
		$sql =	"SELECT tbl_defeito.defeito
				FROM tbl_defeito
				WHERE tbl_defeito.fabrica = $login_fabrica
				ORDER BY tbl_defeito.descricao;";
		$res = pg_exec($con, $sql);
		if (pg_numrows($res) > 0) {
				for ($j = 0 ; $j < pg_numrows($res) ; $j++) {
					$defeito = pg_result($res,$j,defeito);
					echo "<td nowrap>" . $matriz_defeitos[$peca_referencia."d".$defeito] . "</td>";
					echo "<td align='right' nowrap>" . number_format ((($matriz_defeitos[$peca_referencia."d".$defeito] * 100) / $matriz_def_total[$valor]),2,",",".") . " %</td>";
				}
			}
		echo "<td>" . $matriz_def_total[$valor] . "</td>";
		echo "</tr>";
		$k++;
	}
	echo "</table>";
}
	echo "<br><a href=\"javascript: GerarRelatorio ('$produto', '$x_data_inicial', '$x_data_final');\"><font size='2'>Clique aqui para gerar arquivo do EXCEL</font></a><br>";
}
echo "<br>";

include "rodape.php";
?>
