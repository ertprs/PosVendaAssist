<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";

$gera_automatico = trim($_GET["gera_automatico"]);

if ($gera_automatico != 'automatico'){
	include "autentica_admin.php";
}

include "gera_relatorio_pararelo_include.php";

if (strlen(trim($_POST["btn_acao"])) > 0)	$btn_acao = trim($_POST["btn_acao"]);
if (strlen(trim($_GET["btn_acao"])) > 0)	$btn_acao = trim($_GET["btn_acao"]);


if (strlen($btn_acao)>0) {

	if (strlen(trim($_POST["mes"])) > 0)	$mes = trim($_POST["mes"]);
	if (strlen(trim($_GET["mes"])) > 0)		$mes = trim($_GET["mes"]);

	if (strlen(trim($_POST["ano"])) > 0)	$ano = trim($_POST["ano"]);
	if (strlen(trim($_GET["ano"])) > 0)		$ano = trim($_GET["ano"]);

	if (strlen(trim($_POST["produto"])) > 0)$produto = trim($_POST["produto"]);
	if (strlen(trim($_GET["produto"])) > 0)	$produto = trim($_GET["produto"]);

	if ( strlen($mes)==0 OR strlen($ano)==0 )  {
		$msg_erro = "Selecione o mês e o ano para fazer a pesquisa";
	}

	if (strlen($msg_erro)==0) {
		$data_inicial     = date("01/m/Y", mktime(0, 0, 0, $mes, 1, $ano));
		$data_final       = date("t/m/Y", mktime(0, 0, 0, $mes, 1, $ano));
		$aux_data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
		$aux_data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
	}

	if (strlen($msg_erro) > 0) {
		$data_inicial = date("01/m/Y", mktime(0, 0, 0, $mes, 1, $ano));
		$data_final   = date("t/m/Y", mktime(0, 0, 0, $mes, 1, $ano));
		$msg  = "<b>Foi(foram) detectado(s) o(s) seguinte(s) erro(s): </b><br>";
		$msg_erro = $msg.$msg_erro;
	}
}

$layout_menu = "gerencia";
$title = "RELATÓRIO - PRODUTOS EM ORDENS DE SERVIÇO FINALIZADAS";

include "cabecalho.php";

?>

<script language="JavaScript">
function date_onkeydown() {
	if (window.event.srcElement.readOnly) return;
	var key_code = window.event.keyCode;
	var oElement = window.event.srcElement;
	if (window.event.shiftKey && String.fromCharCode(key_code) == "T") {
		var d = new Date();
		oElement.value = String(d.getMonth() + 1).padL(2, "0") + "/" +
						 String(d.getDate()).padL(2, "0") + "/" +
						 d.getFullYear();
		window.event.returnValue = 0;
	}
	if (!window.event.shiftKey && !window.event.ctrlKey && !window.event.altKey) {
		if ((key_code > 47 && key_code < 58) || (key_code > 95 && key_code < 106)) {
			if (key_code > 95) key_code -= (95-47);
				oElement.value = oElement.value.replace(/[dma]/, String.fromCharCode(key_code));
		}
		if (key_code == 8) {
			if (!oElement.value.match(/^[dma0-9]{2}\/[dma0-9]{2}\/[dma0-9]{4}$/))
				oElement.value = "dd/mm/aaaa";
				oElement.value = oElement.value.replace(/([dma\/]*)[0-9]([dma\/]*)$/,
					function ($0, $1, $2) {
						var idx = oElement.value.search(/([dma\/]*)[0-9]([dma\/]*)$/);
						if (idx >= 5) {
							return $1 + "a" + $2;
						} else if (idx >= 2) {
							return $1 + "m" + $2;
						} else {
							return $1 + "d" + $2;
						}
					} 
				);
				window.event.returnValue = 0;
		}
	}
	if (key_code != 9) {
		event.returnValue = false;
	}
}
</script>

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

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 1px solid;
	border-color: #FFFFFF;
	background-color: #D9E2EF
}
</style>

<style type="text/css">
<!--
.conteudo10 {
	color: #000000;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.bgTRConteudo1{
	background-color: #F1F4FA;
}

.bgTRConteudo2{
	background-color: #F7F5F0;
}
-->
</style>

<? include "javascript_pesquisas.php" ?>

<?

#HD 16584
if (strlen($produto)==0){
	if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {
		include "gera_relatorio_pararelo.php";
	}

	if ($gera_automatico != 'automatico' and strlen($msg_erro)==0){
		include "gera_relatorio_pararelo_verifica.php";
	}
}

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

?>

<?if (strlen($produto)==0){?>

	<!-- =========== PESQUISA POR INTERVALO ENTRE DATAS ============ -->
	<FORM name="frm_pesquisa" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
		<? if (strlen($msg_erro) > 0) {
			echo "<table width='730' border='0' cellpadding='2' cellspacing='2' align='center'>";
			echo "<tr><td align='center' class='error'>$msg</td></tr>";
			echo "</table><br>";
		} ?>
		<BR>
		<TABLE width="400" align="center" border="0" cellspacing="0" cellpadding="2">
			<TR>
				<TD colspan="4" class="menu_top" background='imagens_admin/azul.gif' align='center'><b>Pesquisa</b></TD>
			</TR>
			<TR>
				<TD class="table_line" colspan='2'><CENTER>Este relatório considera a data de finalização da Ordem de Serviço (os valores podem variar de acordo com o dia em que o relatório for retirado e/ou exclusões de OSs pelo posto ou pelo administrador).</CENTER></TD>
			</TR>
			<TR>
				<TD class="table_line" width="50%">
					<CENTER>Mês<BR>
					<select name="mes" size="1">
						<option value=''></option>
						<? 
						for ($i = 1 ; $i <= count($meses) ; $i++) {
							echo "<option value='$i'";
							if ($mes == $i) echo " selected";
							echo ">" . $meses[$i] . "</option>";
						}
						?>
					</CENTER>
					</select>
				</TD>
				<TD class="table_line" width="50%">
					<CENTER>Ano<BR>
					<select name="ano" size="1">
					<option value=''></option>
					<?
					for ($i = 2003 ; $i <= date("Y") ; $i++) {
						echo "<option value='$i'";
						if ($ano == $i) echo " selected";
						echo ">$i</option>";
					}
					?>
					</CENTER>
					</select>
				</TD>
			</TR>
			<TR>
				<input type='hidden' name='btn_acao' value=''>
				<TD colspan="4" class="table_line" style="text-align: center;"><IMG src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='1'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer " alt='Clique AQUI para pesquisar'></TD>
			</TR>
		</TABLE>
	</FORM>
	<!-- =========== AQUI TERMINA O FORMULÁRIO FRM_PESQUISA =========== -->
<?}?>
<?

if (strlen($btn_acao)>0 AND strlen($msg_erro)==0){

	if (strlen($produto) > 0) {
		
		//ESTÁ PEGANDO PELA DATA DE FINALIZADA DA OS, A PEDIDO DE NELSON LENOXX, POIS A DATA DE GERAÇÃO E LIBERAÇÃO DO EXTRATO DA LENOXX NÃO É FIXA. NELSON ESTÁ CIENTE DE QUE OS RESULTADOS PODEM VARIAR DEVIDO A ESTAS OSS NAO ESTAREM APROVADAS AINDA.
		$sql = "SELECT  tbl_os.os,
						tbl_os.sua_os,
						tbl_produto.referencia||' - '||tbl_produto.descricao as prod,
						tbl_os.serie,
						to_char(tbl_os.finalizada,'dd/mm/yyyy') as finalizada
				FROM (	SELECT  os,
								posto,
								sua_os,
								produto,
								serie,
								finalizada 
						FROM tbl_os 
						WHERE fabrica = {$login_fabrica}   
						AND finalizada BETWEEN '$aux_data_inicial' AND '$aux_data_final'
						AND produto = $produto
						AND excluida IS NOT TRUE
						AND posto <> 6359
				) tbl_os
				JOIN tbl_produto using(produto)
				ORDER BY tbl_os.sua_os";
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			$produto_desc = pg_result($res,0,prod);

			echo "<FONT COLOR='#FF0000' face='Arial' size='2'>Para voltar a consulta do período <a href='javascript:window.close()'>feche esta janela.</a></FONT>";
			echo "<br><br>";
			$data_inicial_exibir = substr($aux_data_inicial,8,2)."/".substr($aux_data_inicial,5,2)."/".substr($aux_data_inicial,0,4);
			$data_final_exibir   = substr($aux_data_final,8,2)."/".substr($aux_data_final,5,2)."/".substr($aux_data_final,0,4);
			echo "<FONT size=2>Resultado da pesquisa de OSs finalizadas no período de $data_inicial_exibir a $data_final_exibir</FONT><BR><b>PRODUTO: $produto_desc</b>";
			echo "<br><br>";

			echo "<TABLE width='600' border='0' cellspacing='0' cellpadding='0' align='center'>";
			echo "<TR width='100%'><TD align='left'><FONT face='Arial' size='1'>*Os números apresentados podem variar de acordo com o dia em que o relatório for retirado e/ou pela exclusão de OSs pelo posto ou administrador.</FONT></TD></TR>";
			echo "<TR width='100%'><TD align='left'><FONT face='Arial' size='1'>*Clique sobre o número da OS para visualizar as peças.</FONT></TD></TR>";
			echo "</TABLE>";

			echo"<TABLE width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
			echo"<TR>";
			echo"<TD width='30%' height='15' class='table_line'><b>OS</b></TD>";
			//echo"<TD width='55%' height='15' class='table_line'><b>Produto</b></TD>";
			echo"<TD width='10%' height='15' class='table_line'><b>Série</b></TD>";
			echo"<TD width='10%' height='15' class='table_line'><b>Finalizada</b></TD>";
			echo"</TR>";
			
			for ($i=0; $i<pg_numrows($res); $i++){
				$os            = trim(pg_result($res,$i,os));
				$sua_os        = trim(pg_result($res,$i,sua_os));
				//$produto_desc  = trim(pg_result($res,$i,prod));
				$serie         = trim(pg_result($res,$i,serie));
				$finalizada    = trim(pg_result($res,$i,finalizada));

				$cor = '2';
				if ($i % 2 == 0) $cor = '1';
				
				echo "<TR class='bgTRConteudo$cor'>";
				echo "<TD class='conteudo10' align='left' nowrap><a href='os_press.php?os=$os' target='_blank'>$sua_os&nbsp;&nbsp;</a></TD>";
				//echo "<TD class='conteudo10' align='left' nowrap>$produto_desc&nbsp;&nbsp;</TD>";
				echo "<TD class='conteudo10' align='center' nowrap>$serie&nbsp;&nbsp;</TD>";
				echo "<TD class='conteudo10' align='center' nowrap>$finalizada</TD>";
				echo "</TR>";
			}

			$total = pg_numrows($res);
			echo "<TR class='table_line'><td colspan='2'><font size='2'><b><CENTER>TOTAL DE PRODUTOS</b></td><td colspan='2'><font size='2' color='009900'><b>$total</b></td></tr>";
			echo" </TABLE>";
		}else{
			echo "<br>";
			echo "<b>Nenhum resultado encontrado entre $data_inicial e $data_final</b>";
		}
		$listar = "";

		echo "<a href='javascript:window.close()'><img border='0' src='imagens/btn_fechar_azul.gif' alt='Fechar'></a>";

	}else{

		//ESTÁ PEGANDO PELA DATA DE FINALIZADA DA OS, A PEDIDO DE NELSON LENOXX, POIS A DATA DE GERAÇÃO E LIBERAÇÃO DO EXTRATO DA LENOXX NÃO É FIXA. NELSON ESTÁ CIENTE DE QUE OS RESULTADOS PODEM VARIAR DEVIDO A ESTAS OSS NAO ESTAREM APROVADAS AINDA.

		$sql = "SELECT  tbl_produto.produto, 
						tbl_produto.ativo, 
						tbl_produto.referencia, 
						tbl_produto.descricao, 
						fcr1.qtde AS ocorrencia
				FROM tbl_produto
				JOIN (	SELECT  tbl_os.produto, 
								COUNT(*) AS qtde
						FROM tbl_os
						WHERE tbl_os.fabrica = $login_fabrica
						AND   tbl_os.excluida IS NOT TRUE
						AND tbl_os.finalizada BETWEEN '$aux_data_inicial' AND '$aux_data_final'
						AND tbl_os.posto <> 6359
						GROUP BY tbl_os.produto
				) fcr1 ON tbl_produto.produto = fcr1.produto
				ORDER BY fcr1.qtde DESC ";
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			$total = 0;
			echo "<br>";
			echo "<b>Resultado de pesquisa entre os dias $data_inicial e $data_final</b>";
			echo "<br><br>";
			echo "<FONT SIZE=\"2\">Clique sobre a referência do produto para exibir as OSs.</FONT><br>";
			echo "<FONT SIZE=\"2\">(*) Produtos que estão inativos.</FONT>";
			echo"<TABLE width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
			echo"<TR>";
			echo"<TD width='10%' height='15' class='table_line'><b>Referência</b></TD>";
			echo"<TD width='55%' height='15' class='table_line'><b>Produto</b></TD>";
			echo"<TD width='10%' height='15' class='table_line'><b>Ocorrência</b></TD>";
			echo"</TR>";
			
			for ($x = 0; $x < pg_numrows($res); $x++) {
				$total_ocorrencia = $total_ocorrencia + pg_result($res,$x,ocorrencia);
			}
			
			for ($i=0; $i<pg_numrows($res); $i++){
				$referencia = trim(pg_result($res,$i,referencia));
				$ativo      = trim(pg_result($res,$i,ativo));
				$descricao  = trim(pg_result($res,$i,descricao));
				$produto    = trim(pg_result($res,$i,produto));
				$ocorrencia = trim(pg_result($res,$i,ocorrencia));
				
				$cor = '2';
				if ($i % 2 == 0) $cor = '1';

				// Todo produto que for inativo estará com um (*) na frente para indicar se está Inativo ou Ativo.
				if($ativo == 'f'){$ativo = "<B>*</B>"; }else{$ativo= '';} 
				echo "<TR class='bgTRConteudo$cor'>";

				echo "<TD class='conteudo10' align='left' nowrap>$ativo<a href='$PHP_SELF?btn_acao=1&produto=$produto&ano=$ano&mes=$mes' target='_blank'>$referencia</a></TD>";
				echo "<TD class='conteudo10' align='left' nowrap>$descricao</TD>";
				echo "<TD class='conteudo10' align='center' nowrap>$ocorrencia</TD>";
				echo "</TR>";
				
				$total = $ocorrencia + $total;
			}
			echo "<TR class='table_line'><td colspan='2'><font size='2'><b><CENTER>TOTAL DE PRODUTOS</b></TD>";
			echo "<TD colspan='2'><font size='2' color='009900'><B>$total</B></TD></TR>";
			echo " </TABLE>";
		}else{
			echo "<BR>";
			
			echo "<B>Nenhum resultado encontrado entre $data_inicial e $data_final</B>";
		}
	}
}
flush();
?>

<p>
<? include "rodape.php" ?>