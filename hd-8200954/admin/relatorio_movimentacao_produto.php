<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include "autentica_admin.php";

include "funcoes.php";

####### HD 23465 - Francisco Ambrozio #######################################################
#		Relatório movimentações do produto													#
#																							#
#		Relatorio que mostre todas as movimentações do produto em um determinado periodo	#
#																							#
####### 23/07/2008 ##########################################################################

# Apenas para NKS
if ($login_fabrica <> 45){
	include("acesso_restrito.php");
	exit;
}

$msg_erro = "";

if (strlen($btn_acao) > 0) {
	# Tratando campos obrigatórios
	$data_in = $_POST["data_in"];
	$data_fl = $_POST["data_fl"];
	if (strlen($data_in) == 0 or $data_in == 'dd/mm/aaaa') $msg_erro = "Informe a data inicial para realizar a pesquisa.";
	if (strlen($data_fl) == 0 or $data_fl == 'dd/mm/aaaa') $msg_erro = "Informe a data final para realizar a pesquisa.";
	if (((strlen($data_in) == 0) and (strlen($data_fl) == 0)) or ($data_in == 'dd/mm/aaaa' and $data_fl == 'dd/mm/aaaa')) $msg_erro = "É obrigatório informar um período para realizar a pesquisa!";
}

$layout_menu = "gerencia";
$title = "Relatório das movimentações do produto";

include "cabecalho.php";

?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
}
.ConteudoBranco {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#FFFFFF;
	font-weight: normal;
}
.Mes{
	font-size: 9px;
}
.Caixa{
	BORDER-RIGHT: #6699CC 1px solid; 
	BORDER-TOP: #6699CC 1px solid; 
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid; 
	BORDER-BOTTOM: #6699CC 1px solid; 
	BACKGROUND-COLOR: #FFFFFF
}
.Exibe{
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: none;
	color: #000000;
	text-align: center;
}
</style>

<? include "javascript_pesquisas.php"; 
   include "javascript_calendario.php"; ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_in').datePicker({startDate:'01/01/2000'});
		$('#data_fl').datePicker({startDate:'01/01/2000'});
		$("#data_in").maskedinput("99/99/9999");
		$("#data_fl").maskedinput("99/99/9999");
	});
</script>

<? if (strlen($msg_erro) > 0) { ?>
<br>
<table width="500" border="0" cellpadding="2" cellspacing="2" align="center" class="error">
	<tr>
		<td><?echo $msg_erro?></td>
	</tr>
</table>
<? } ?>


<br>
<!-- Form para a pesquisa. Apenas campos "Data inicial" e "Data final" -->
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center'>
	<table width='500' class='Conteudo' style='background-color: #485989' border='0' cellpadding='5' cellspacing='1' align='center'>
		<tr>
			<td class='Titulo' background='imagens_admin/azul.gif'>Relatório das movimentações de produto</td>
		</tr>
		
		<tr>
			<td bgcolor='#DBE5F5'>
		
				<table width='100%' border='0' cellspacing='1' cellpadding='2' class='Conteudo'>

					<tr class="Conteudo" bgcolor="#D9E2EF">
						<td width="10">&nbsp;</td>
						<td align='right'>Data Inicial:&nbsp;</td>
						<td align='left'>
							<input type="text" name="data_in" id="data_in" size="12" maxlength="10" class='Caixa' value="<? if (strlen($data_in) > 0) echo $data_in; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
						</td>
						<td align='right'>Data Final:&nbsp;</td> 
						<td align='left'>
							<input type="text" name="data_fl" id="data_fl" size="12" maxlength="10" class='Caixa' value="<? if (strlen($data_fl) > 0) echo $data_fl; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
						</td>
						<td width="10">&nbsp;</td>
					</tr>
					
				</table>

				<center><br><input type='submit' name='btn_gravar' value='Consultar'><input type='hidden' name='btn_acao' value=$btn_acao></center>

			</td>
		</tr>
	</table>
</form>
<?

if (strlen($btn_acao) > 0 and strlen($msg_erro) == 0) {
	# Quebra a data em dia, mês e ano
	$ano_di = substr($data_in, -4, 4);
	$mes_di = substr($data_in, -7, 2);
	$dia_di = substr($data_in, -10, 2);
	# Formata a data para AAAA - MM - DD
	$data_inicial = date("Y-m-d", mktime(0, 0, 0, $mes_di, $dia_di, $ano_di));
	$ano_df = substr($data_fl, -4, 4);
	$mes_df = substr($data_fl, -7, 2);
	$dia_df = substr($data_fl, -10, 2);
	$data_final = date("Y-m-d", mktime(0, 0, 0, $mes_df, $dia_df, $ano_df));

	# HD 35331 - Francisco Ambrozio
	#   O SELECT foi alterado para que OSs excluídas não
	#    sejam contadas como abertas

	$sql = "SELECT   tbl_produto.produto				,
					tbl_produto.referencia				,
					tbl_produto.descricao				,
					total								,
					abertas								,
					fechadas							,
					excluidas							,
					troca
				FROM tbl_produto 
				JOIN tbl_linha on tbl_produto.linha = tbl_linha.linha and tbl_linha.fabrica = $login_fabrica
				LEFT JOIN (
					SELECT bi_os.produto,
							COUNT (bi_os.data_abertura) AS total
					FROM bi_os 
					JOIN tbl_produto using (produto)
					WHERE bi_os.fabrica = $login_fabrica
					AND bi_os.data_abertura between '$data_inicial 00:00:00' and '$data_final 23:59:59'
					GROUP BY bi_os.produto
				) tot on tot.produto = tbl_produto.produto
				LEFT JOIN (
					SELECT bi_os.produto,
						COUNT (bi_os.data_abertura) as abertas
					FROM bi_os 
					JOIN tbl_produto using (produto)
					WHERE bi_os.fabrica = $login_fabrica 
					AND bi_os.data_fechamento IS NULL
					AND bi_os.excluida = 'f'
					AND bi_os.data_abertura between '$data_inicial 00:00:00' and '$data_final 23:59:59'
					GROUP BY bi_os.produto
				) abe on abe.produto = tbl_produto.produto
				LEFT JOIN (
					SELECT bi_os.produto,
						COUNT (bi_os.data_fechamento) AS fechadas
					FROM bi_os 
					JOIN tbl_produto using (produto)
					WHERE bi_os.fabrica = $login_fabrica
					AND bi_os.data_fechamento IS NOT NULL
					AND bi_os.excluida = 'f'
					AND bi_os.data_abertura between '$data_inicial 00:00:00' and '$data_final 23:59:59'
					GROUP BY bi_os.produto
				) fec on fec.produto = tbl_produto.produto
				LEFT JOIN (
					SELECT bi_os.produto,
						COUNT (bi_os.excluida) AS excluidas
					FROM bi_os
					JOIN tbl_produto using (produto)
					WHERE bi_os.fabrica = $login_fabrica
					AND bi_os.excluida = 't'
					AND bi_os.data_abertura between '$data_inicial 00:00:00' and '$data_final 23:59:59'
					GROUP BY bi_os.produto
				) exc on exc.produto = tbl_produto.produto
				LEFT JOIN (
					SELECT bi_os.produto,
						COUNT (bi_os.data_abertura) AS troca
					FROM bi_os 
					JOIN tbl_produto using (produto)
					JOIN tbl_os_troca on tbl_os_troca.os = bi_os.os
					WHERE bi_os.fabrica = $login_fabrica
					AND bi_os.data_abertura between '$data_inicial 00:00:00' and '$data_final 23:59:59'
					GROUP BY bi_os.produto
				) tro on tro.produto = tbl_produto.produto
				WHERE total > 0
				ORDER by total desc, tbl_produto.descricao";

		$res = pg_exec($con,$sql);

		if (pg_numrows($res) > 0){
			# Exibe o resultado

			echo "<br>";
			echo "<h6>$title no período de $data_in a $data_fl</h6>";

			$arquivo_nome     = "relatorio-mov-produto-$login_fabrica.$login_admin.xls";
			$path             = "/www/assist/www/admin/xls/";
			$path_tmp         = "/tmp/";

			$arquivo_completo     = $path.$arquivo_nome;
			$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

			$fp = fopen ($arquivo_completo_tmp,"w");

			fputs ($fp,"<html>");
			fputs ($fp,"<body>");
			
			echo "<p id='id_download' style='display:none'><a href='xls/$arquivo_nome' target='_blank'><img src='/assist/imagens/excel.gif'><br><font color='#3300CC'>Fazer download do arquivo em  XLS</font></a></p>";

			$conteudo .="<center><div style='width:98%;'><table align='center' width='500' border='0' class='conteudo' cellpadding='2' cellspacing='1' style='background-color: #FFFFFF; bordercolor: #000000; font-family: verdana; font-size: 14px'>";
			$conteudo .="<thead>";
			$conteudo .="<tr class='Titulo' background='imagens_admin/azul.gif'>";
			$conteudo .="<td align='center' colspan='2'><b>Produto<b></td>";
			$conteudo .="<td align='center' colspan='4'><b>Ordens de Serviço<b></td>";
			$conteudo .="</tr>";
			$conteudo .="<tr class='Conteudo' bgcolor='#FFFFFF'>";
			$conteudo .="<td align='center'><b>Referência</b></a></td>";
			$conteudo .="<td align='center'><b>Descrição</b></a></td>";
			$conteudo .="<td align='center'><b>Total</b></a></td>";
			$conteudo .="<td align='center'><b>Abertas</b></a></td>";
			$conteudo .="<td align='center'><b>Fechadas</b></a></td>";
			$conteudo .="<td align='center'><b>Trocas</b></a></td>";
			$conteudo .="</tr>";
			$conteudo .="</thead>";
			$conteudo .="<tbody>";

			for ($i = 0 ; $i < pg_numrows($res) ; $i++){
					# Alterna cor das linhas da tabela
					if ($i % 2 == 0) $cor= "#D9E2EF";
					else $cor="#f0f0f0";
					
					$referencia = trim(pg_result($res,$i,referencia));
					$descricao  = trim(pg_result($res,$i,descricao));
					$total      = trim(pg_result($res,$i,total));
					$abertas    = trim(pg_result($res,$i,abertas));
					$fechadas   = trim(pg_result($res,$i,fechadas));
					$excluidas  = trim(pg_result($res,$i,excluidas));
					$fechadatot = $fechadas + $excluidas;
					$troca      = trim(pg_result($res,$i,troca));

					if (strlen($total)==0) $total=0;
					if (strlen($abertas)==0) $abertas=0;
					if (strlen($fechadas)==0) $fechadas=0;
					if (strlen($troca)==0) $troca=0;

					$totaldototal    += $total;
					$totalabertas    += $abertas;
					$totalfechadas   += $fechadatot;
					$totaltroca      += $troca;

					$conteudo .="<tr class='Conteudo'>";
					$conteudo .="<td align='left' bgcolor=$cor>$referencia</td>";
					$conteudo .="<td nowrap align='left' bgcolor=$cor>$descricao</td>";
					$conteudo .="<td align='center' bgcolor=$cor>$total</td>";
					$conteudo .="<td align='center' bgcolor=$cor>$abertas</td>";
					$conteudo .="<td align='center' bgcolor=$cor>$fechadatot</td>";
					$conteudo .="<td align='center' bgcolor=$cor>$troca</td>";
					$conteudo .="</tr>";
				}
			$conteudo .="</tbody>";
			$conteudo .="<tfoot>";
			$conteudo .="<tr class='table_line'><td colspan='2' align='right'><font size='2'><b>TOTAIS&nbsp;</b></td>";
			$conteudo .="<td align='center'><font size='2'><b>$totaldototal</b></td>";
			$conteudo .="<td align='center'><font size='2'><b>$totalabertas</b></td>";
			$conteudo .="<td align='center'><font size='2'><b>$totalfechadas</b></td>";
			$conteudo .="<td align='center'><font size='2'><b>$totaltroca</b></td>";
			$conteudo .="</tr>";
			$conteudo .="</tfoot>";
			$conteudo .="</table></div></center>";

			echo $conteudo;

			fputs ($fp,$conteudo);

		
			fputs ($fp,"</body>");
			fputs ($fp,"</html>");
		
			fclose ($fp);
			flush();
			echo ` cp $arquivo_completo_tmp $path `;
			echo "<script language='javascript'>";
			echo "document.getElementById('id_download').style.display='block';";
			echo "</script>";
			echo "<br>";

			flush();
	}else{
		echo "<br>";
		
		echo "<b>Nenhum resultado encontrado entre $data_in e $data_fl</b>";
	}
}

echo "<br>";

include 'rodape.php';
?>