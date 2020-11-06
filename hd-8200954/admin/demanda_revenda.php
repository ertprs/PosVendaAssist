<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include "autentica_admin.php";

include "funcoes.php";

$msg = "";

$meses = array (1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

if (strlen($_POST["acao"]) > 0) {
	$acao = strtoupper($_POST["acao"]);
}

function converte_data($date)
{
	$date = explode("-", str_replace('/', '-', $date));
	$date2 = ''.$date[2].'-'.$date[1].'-'.$date[0];
	if (sizeof($date)==3)
		return $date2;
	else return false;
}

##### GERAR ARQUIVO EXCEL #####
if ($acao == "RELATORIO") {
	$produto      = trim($_GET["produto"]);
	$data_inicial = trim($_GET["data_inicial"]);
	$data_final   = trim($_GET["data_final"]);
	$cnpj         = trim($_GET["cnpj"]);
	if(strlen($tipo_os)==0){
		$tipo_os = "t";
	}

	if($login_fabrica == 24){ 
		$sql =	"SELECT fn_field_call_rate_suggar($login_fabrica, $produto, '$data_inicial', '$data_final','$tipo_os');";
	}else{
		$sql =	"SELECT fn_field_call_rate($login_fabrica, $produto, '$data_inicial', '$data_final');";
	}
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

$layout_menu = "gerencia";
$title = "RELATÓRIO - RETORNO X VENDA";

include "cabecalho.php";
?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
</style>

<? include "javascript_pesquisas.php"; ?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>

<script language="JavaScript">

function fnc_pesquisa_revenda (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cnpj") {
		url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj";
	}
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
	janela.nome			= document.frm_relatorio.revenda_nome;
	janela.cnpj			= document.frm_relatorio.revenda_cnpj;
	janela.fone			= document.frm_relatorio.revenda_fone;
	janela.cidade		= document.frm_relatorio.revenda_cidade;
	janela.estado		= document.frm_relatorio.revenda_estado;
	janela.endereco		= document.frm_relatorio.revenda_endereco;
	janela.numero		= document.frm_relatorio.revenda_numero;
	janela.complemento	= document.frm_relatorio.revenda_complemento;
	janela.bairro		= document.frm_relatorio.revenda_bairro;
	janela.cep			= document.frm_relatorio.revenda_cep;
	janela.email		= document.frm_relatorio.revenda_email;
	janela.focus();
}

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
<table width="400" border="0" cellspacing="0" cellpadding="2" align="center">
	<tr class="Titulo">
		<td colspan="4">PESQUISA</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr>
		<td class="Conteudo" bgcolor="#D9E2EF" colspan='4' style="font-size: 10px"><center>Este relatório considera os últimos 6 meses <br> com deslocamento de 60 dias</center></td>
	</tr>
	<!--
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td colspan='2'>Data&nbsp;&nbsp;&nbsp;&nbsp;</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td colspan='2'>
			<input type="text" name="data_nf" size="13" maxlength="10" value="<? if (strlen($data_nf) > 0) echo substr($data_nf,0,10); else echo "dd/mm/aaaa"; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
			<img src="imagens_admin/btn_lupa.gif" align="absmiddle" onclick="javascript: showCal('DataFinal')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">
		</td>
		<td width="10">&nbsp;</td>
	</tr>
	-->
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td>Referência do Produto</td>
		<td>Descrição do Produto</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td>
			<input type="text" name="produto_referencia" size="15" value="<?echo $produto_referencia?>" class="frm">
			<img src="imagens_admin/btn_lupa.gif" align="absmiddle" onclick="fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao, 'referencia')" style="cursor: hand;" alt="Clique aqui para abrir a referência">
		</td>
		<td>
			<input type="text" name="produto_descricao" size="20" value="<?echo $produto_descricao?>" class="frm">
			<img src="imagens_admin/btn_lupa.gif" align="absmiddle" onclick="fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao, 'descricao')" style="cursor: hand;" alt="Clique aqui para abrir o produto">
		</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<!--
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td>CNPJ</td>
		<td>Razão Social</td>
		<td width="10">&nbsp;</td>
	</tr>
	<TR class="Conteudo" bgcolor="#D9E2EF" width = '100%' align="center">
		<td></td>
		<td><input type="text" name="revenda_cnpj" size="18" class="frm" maxlength="18" value="<? echo $cnpj ?>">&nbsp;<img src='imagens_admin/btn_lupa.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_revenda (document.frm_relatorio.revenda_cnpj, 'cnpj');"></td>
		<td ><input type="text" name="revenda_nome" size="25" class="frm" maxlength="60" value="<? echo $nome ?>" >&nbsp;<img src='imagens_admin/btn_lupa.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_revenda (document.frm_relatorio.revenda_nome, 'nome');"></td>
		<td>
			<input type='hidden' name = 'revenda_fone'>
			<input type='hidden' name = 'revenda_cidade'>
			<input type='hidden' name = 'revenda_estado'>
			<input type='hidden' name = 'revenda_endereco'>
			<input type='hidden' name = 'revenda_numero'>
			<input type='hidden' name = 'revenda_complemento'>
			<input type='hidden' name = 'revenda_bairro'>
			<input type='hidden' name = 'revenda_cep'>
			<input type='hidden' name = 'revenda_email'>
		</td>
	</TR>
-->
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
if (strlen($acao) > 0 && strlen($erro) == 0) {

	$produto_referencia = trim($_POST["produto_referencia"]);
	#$data_nf            = trim($_POST["data_nf"]);
	#$revenda_cnpj       = trim($_POST["revenda_cnpj"]);

	if (strlen($produto_referencia)==0){
		$msg_erro .= "Selecione o produto!";
	}
	if (strlen($data_nf)==0){
		#$msg_erro .= "Informe a data!";
	}
	if (strlen($revenda_cnpj)==0){
		#$msg_erro .= "Selecione a revenda!";
	}

	if (strlen($msg_erro)==0){
		//$Xdata_nf = explode("-", str_replace('/', '-', $data_nf));
		//$Xdata_nf = ''.$date[2].'-'.$date[1];
		//$data_nf  = converte_data($data_nf);
	}

	if (strlen($msg_erro)==0){
		$sqlP = "SELECT tbl_produto.produto,
						tbl_produto.descricao,
						tbl_produto.referencia
				FROM tbl_produto
				JOIN tbl_linha USING (linha)
				WHERE tbl_produto.referencia = '$produto_referencia'
				AND   tbl_linha.fabrica   = $login_fabrica;";
		$res_P   = pg_exec($con,$sqlP);
		if (pg_numrows($res_P) > 0) {
			$produto            = pg_result($res_P,0,produto);
			$produto_descricao  = pg_result($res_P,0,descricao);
			$produto_referencia = pg_result($res_P,0,referencia);
		}else{
			$msg_erro .= "Produto não encontrado!";
		}
	}

	//$x_data_inicial = date("Y-m-01 H:i:s", mktime(0, 0, 0, $mes_inicial, $dia_inicial, $ano_inicial));
	//$x_data_final   = date("Y-m-t H:i:s", mktime(23, 59, 59, $mes_final, $dia_final, $ano_final));
	
	$xrevenda_cnpj = str_replace (".","",$revenda_cnpj);
	$xrevenda_cnpj = str_replace ("-","",$xrevenda_cnpj);
	$xrevenda_cnpj = str_replace ("/","",$xrevenda_cnpj);
	$xrevenda_cnpj = str_replace (" ","",$xrevenda_cnpj);

	$sql = "SELECT to_char(CURRENT_DATE - interval '8 month','YYYY-MM-DD')";
	$res = pg_exec($con,$sql);
	$data_inicio = pg_result($res,0,0);

	$sql = "SELECT to_char(CURRENT_DATE - interval '2 month','YYYY-MM-DD')";
	$res = pg_exec($con,$sql);
	$data_fim = pg_result($res,0,0);

	$meses = array ('Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro');

	if(strlen($msg_erro)==0){

		$sql = "SELECT 	count(*) as ocorrencias
					FROM ( 
					SELECT extrato 
					FROM tbl_extrato 
					WHERE fabrica=11
					AND tbl_extrato.data_geracao BETWEEN '$data_inicio' AND '$data_fim'
					) extr
					JOIN tbl_os_extra ON tbl_os_extra.extrato = extr.extrato
					JOIN tbl_os  ON tbl_os.os = tbl_os_extra.os
					LEFT JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
					LEFT JOIN tbl_revenda USING(revenda) 
					WHERE tbl_os.consumidor_revenda='R'
					AND tbl_os.fabrica=11 
					AND tbl_os_produto.produto = $produto
					AND tbl_os.data_nf BETWEEN '$data_inicio'::date +interval '1 day' AND '$data_fim'
					GROUP BY	tbl_os_produto.produto,
								SUBSTR(tbl_revenda.cnpj,0,8)";
		//echo nl2br($sql);
		#exit;
		$res2    = pg_exec($con,$sql);
		$rowspan = pg_numrows($res2);
		$rowspan += 1;

		$sql = "SELECT tbl_os_produto.produto,
						to_char(tbl_os.data_nf,'YYYY-MM') AS data_nf,
						SUBSTR(tbl_revenda.cnpj,0,8) as cnpj_x,
						count(tbl_os.os) as ocorrencias
				FROM ( 
				SELECT extrato 
				FROM tbl_extrato 
				WHERE fabrica=11
				AND tbl_extrato.data_geracao BETWEEN '$data_inicio' AND '$data_fim'
				) extr
				JOIN tbl_os_extra ON tbl_os_extra.extrato = extr.extrato
				JOIN tbl_os  ON tbl_os.os = tbl_os_extra.os
				LEFT JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
				LEFT JOIN tbl_revenda USING(revenda) 
				WHERE tbl_os.consumidor_revenda='R'
				AND tbl_os.fabrica=11 
				AND tbl_os_produto.produto = $produto
				AND tbl_os.data_nf BETWEEN '$data_inicio'::date +interval '1 day' AND '$data_fim'
				GROUP BY	tbl_os_produto.produto,
							to_char(tbl_os.data_nf,'YYYY-MM'),
							SUBSTR(tbl_revenda.cnpj,0,8)
				ORDER BY SUBSTR(tbl_revenda.cnpj,0,8), to_char(tbl_os.data_nf,'YYYY-MM')";
		//echo nl2br($sql);
		#exit;
		$res2 = pg_exec($con,$sql);

		if (pg_numrows($res2) > 0) {
			echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' class='Conteudo'>";
			
			echo "<tr class='Titulo' height='20'>";
			echo "<td colspan='10'><font size='3'>Retorno Semestral com deslocamento de 60 dias</font></td>";
			echo "</tr>";

			echo "<tr class='Conteudo' bgcolor='#D9E2EF' >";
			echo "<td colspan='10'>$produto_referencia - $produto_descricao</td>";
			echo "</tr>";

			echo "<tr class='Titulo'>";
			echo "<td rowspan='$rowspan'>$produto_referencia</td>";
			echo "<td >Revenda <br><span style='font-weight:normal;font-size:10px;color:#E0E0E0'>(Radical CNPJ - Nome)</span></td>";

			$array_meses = array();
			$array_total = array();
			for ($i = 1 ; $i <= 6 ; $i++) {
				$sql_data   = "SELECT to_char('$data_inicio'::date + interval '$i month','YYYY-MM')";
				$res_data   = pg_exec($con,$sql_data);
				$mes        = pg_result($res_data,0,0);
				$mes_aux    = substr($mes,5,2);
				array_push($array_meses,$mes);
				array_push($array_total,0);
				echo "<td width='60px'>".$meses[$mes_aux-1]."</td>";
			}
			echo "<td width='60px'>TOTAL</td>";
			echo "<td width='60px'>MÉDIA SEMESTRAL</td>";
			echo "</tr>";

			$cnpj_x_ant   = "";
			$soma_por_loja = 0;
			$numero_coluna = 0;
			$numero_linha  =0;

			for ($i = 0 ; $i < pg_numrows($res2) ; $i++) {
				$produto      = pg_result($res2,$i,produto);
				$data_nf      = pg_result($res2,$i,data_nf);
				$cnpj_x       = pg_result($res2,$i,cnpj_x);
				$ocorrencias  = pg_result($res2,$i,ocorrencias);

				$sql_re = "SELECT nome FROM tbl_revenda WHERE cnpj like '$cnpj_x%' LIMIT 1";
				$res_re = pg_exec($con,$sql_re);
				if (pg_numrows($res_re)>0){
					$revena_nome = pg_result($res_re,0,0);
				}

				if ($cnpj_x_ant != $cnpj_x){
					if ($i!=0){
						while ($numero_coluna < count($array_meses)){
							echo "<td></td>";
							$numero_coluna++;
						}
						echo "<td><b>$soma_por_loja</b></td>";
						$soma_por_loja = round($soma_por_loja/count($array_meses));
						echo "<td>$soma_por_loja</td>";
						echo "</tr>";
					}
					$soma_por_loja =0;
					$cor = ($numero_linha % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;
					$numero_linha++;
					$numero_coluna=0;
					echo "<tr height='15' bgcolor='$cor'>";
					echo "<td align='left'>".$cnpj_x."XXXXXX - ".$revena_nome."</td>";
				}

				while ($numero_coluna <= count($array_meses)){
					if ($array_meses[$numero_coluna]==$data_nf){
						$array_total[$numero_coluna]+=$ocorrencias;
						echo "<td>$ocorrencias</td>";
						$numero_coluna++;
						break;
					}else{
						echo "<td></td>";
					}
					$numero_coluna++;
				}

				$cnpj_x_ant = $cnpj_x;
				$soma_por_loja += $ocorrencias;

				#Se for o ultimo
				if ($i+1 == pg_numrows($res2)){
					if ($i!=0){
						while ($numero_coluna < count($array_meses)){
							echo "<td></td>";
							$numero_coluna++;
						}
						echo "<td><b>$soma_por_loja</b></td>";
						$soma_por_loja = round($soma_por_loja/count($array_meses));
						echo "<td>$soma_por_loja</td>";
						echo "</tr>";
					}
					$soma_por_loja =0;
					$cor = ($numero_linha % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;
					$numero_linha++;
					$numero_coluna=0;
				}
				flush();
			}
			echo "<tr class='Titulo' height='20'>";
			echo "<td colspan='2'>Total por Mês</td>";
			$tota_geral=0;
			for($i=0;$i<count($array_total);$i++){
				echo "<td align='center'>". $array_total[$i]."</td>";
				$tota_geral += $array_total[$i];
			}
			echo "<td bgcolor='#73A2D5'>$tota_geral</td>";
			$media_total = round($tota_geral/count($array_total));
			echo "<td bgcolor='#5372BB'>$media_total</td>";
			echo "</tr>";

			echo "</table>";
		}else{
			echo "<h3>Nenhum resultado encontrado!</h3>";
		}
	}

	if(strlen($msg_erro)>0){
		echo "<h1>Erro: $msg_erro</h1>";
	}
	echo "<br><br>";

exit;

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
	
	echo "<br><a href=\"javascript: GerarRelatorio ('$produto', '$x_data_inicial', '$x_data_final');\"><font size='2'>Clique aqui para gerar arquivo do EXCEL</font></a><br>";
}
echo "<br>";

include "rodape.php";
?>
