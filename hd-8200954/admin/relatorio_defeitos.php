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

	if($login_fabrica==3){
		if (strlen($pesquisa_ano) == 0) $msg = " Informe o ano para realizar a pesquisa. ";
	}else{
		if (strlen($pesquisa_mes) == 0) $msg = " Informe o mês para realizar a pesquisa. ";
		if (strlen($pesquisa_ano) == 0) $msg = " Informe o ano para realizar a pesquisa. ";
	}

	if (strlen($msg) == 0) {
		if (strlen($pesquisa_ano) == 2 OR strlen($pesquisa_ano) == 4) {
			if ($pesquisa_ano >= 50 && strlen($pesquisa_ano) == 2) $pesquisa_ano = "19" . $pesquisa_ano;
			elseif ($pesquisa_ano < 50 && strlen($pesquisa_ano) == 2) $pesquisa_ano = "20" . $pesquisa_ano;
		}else{
			$msg = " Informe o ano para realizar a pesquisa. ";
		}
	}

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
			$msg = " Produto não encontrado. ";
		}
	}else{
		$msg = " Informe o produto para realizar a pesquisa. ";
	}
}

$layout_menu = "gerencia";
$title = "RELATÓRIO - DEFEITOS";

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
}
</style>

<? include "javascript_pesquisas.php"; ?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language="javascript" >
function AbrePeca(produto,n_serie){
	janela = window.open("relatorio_defeito_serie_fabricacao_os.php?produto=" + produto + "&nserie=" + n_serie,"serie",'resizable=1,scrollbars=yes,width=750,height=450,top=0,left=0');
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
<table width="700" border="0" cellpadding="0" cellspacing="1" align="center">
	<tr>
		<td class="msg_erro"><?echo $msg?></td>
	</tr>
</table>

<? } ?>

<form name="frm_relatorio" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="acao">

<TABLE width="700" align="center" border="0" cellspacing='1' cellpadding='0' class='formulario'>

<tr><td class="titulo_tabela" colspan="3">Parâmetros de Pesquisa</td></tr>

<tbody>
<tr>
		<td width="25%">&nbsp;</td>
		<td align='left' style="width:150px;">
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
	<td colspan="2">&nbsp;</td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td align='left'>
		Referência<br />
		<input type="text" name="produto_referencia" size="15" value="<?echo $produto_referencia?>" class="frm">
		<img src="imagens/lupa.png" align="absmiddle" onclick="fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao, 'referencia')" style="cursor: pointer;" alt="Clique aqui para pesquisar o produto">
	</td>
	<td align='left'>
		Descrição do Produto<br />
		<input type="text" name="produto_descricao" size="40" value="<?echo $produto_descricao?>" class="frm">
		<img src="imagens/lupa.png" align="absmiddle" onclick="fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao, 'descricao')" style="cursor: pointer;" alt="Clique aqui para pesquisar o produto">
	</td>
</tr>
<tr>
	<td colspan="2">&nbsp;</td>
</tr>
<tr>
	<td colspan="3" align='center' style="padding-bottom:10px;">
		<input type="button" onclick="javascript: document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor:pointer " value="Pesquisar" />
	</td>
</tr>
</tbody>
</table>
</form>

<br>

<?
if (strlen($acao) > 0 && strlen($msg) == 0) {
	if($login_fabrica==3 AND strlen($pesquisa_mes)==0){
		$gera_xls = true;
		$data_inicial = date("Y-m-01", mktime(0, 0, 0, 1, 1, $pesquisa_ano));
		$data_final   = date("Y-m-t", mktime(23, 59, 59, 12, 1, $pesquisa_ano));
	}else{
		$data_inicial = date("Y-m-01", mktime(0, 0, 0, $pesquisa_mes, 1, $pesquisa_ano));
		$data_final   = date("Y-m-t", mktime(23, 59, 59, $pesquisa_mes, 1, $pesquisa_ano));	
	}

	$pesquisa_mes;
	$pesquisa_ano = substr($pesquisa_ano, 2, 2);
	$radical_n_serie = $pesquisa_mes.$pesquisa_ano;
//echo "n serie $radical_n_serie<bR><BR>";	


	$sql = "SELECT tbl_os.os                         ,
				   tbl_os.data_abertura              ,
				   tbl_os.sua_os                     ,
				   upper(consumidor_revenda) as consumidor_revenda,
				   tbl_os.serie                      ,
				   tbl_posto.nome         as posto_nome           ,
					tbl_posto_fabrica.codigo_posto        as posto_codigo           ,
				   tbl_defeito_reclamado.descricao   as defeito_reclamado,
				   tbl_defeito_constatado.descricao  as defeito_constatado, ";
				   if($data_inicial<'2007-02-01') { $sql .= " tbl_servico_realizado.descricao as solucao, ";
					}else{ $sql .=" tbl_solucao.descricao             as solucao,";}
					$sql .=" tbl_peca.descricao               as peca , 
				   tbl_defeito.descricao as servico_peca
			FROM tbl_os
			JOIN tbl_posto using (posto)
			JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_defeito_reclamado using(defeito_reclamado)
			LEFT JOIN tbl_defeito_constatado using(defeito_constatado)";
if($data_inicial<'2007-02-01') { $sql .= " LEFT JOIN tbl_servico_realizado on tbl_servico_realizado.servico_realizado = tbl_os.solucao_os"; 
			}else{
			$sql .= " LEFT JOIN tbl_solucao on tbl_solucao.solucao = tbl_os.solucao_os";
			}
		$sql .= "
			LEFT JOIN tbl_os_produto ON tbl_os_produto.os         = tbl_os.os
			LEFT JOIN tbl_os_item    ON tbl_os_produto.os_produto = tbl_os_item.os_produto
			LEFT JOIN tbl_defeito    ON tbl_os_item.defeito       = tbl_defeito.defeito
			LEFT JOIN tbl_peca       ON tbl_peca.peca             = tbl_os_item.peca
			WHERE tbl_os.fabrica   = $login_fabrica
			AND   tbl_os.produto   = $produto
			AND   tbl_os.excluida IS NOT TRUE
			and   tbl_os.data_digitacao between '$data_inicial 00:00:00'  and '$data_final 23:59:59'
			ORDER BY tbl_os.os, tbl_posto_fabrica.codigo_posto";

	$res = pg_exec($con,$sql);
	
	//if (getenv("REMOTE_ADDR") == "201.27.215.6") echo nl2br($sql);

	flush();
	if (pg_numrows($res) > 0) {
		if($login_fabrica==3 AND $gera_xls==true){
			$data = date ("d-m-Y-H-i");

			$arquivo_nome     = "relatorio_defeitos-$login_fabrica-$data.txt";
			$path             = "/www/assist/www/admin/xls/";
			$path_tmp         = "/tmp/assist/";

			$arquivo_completo     = $path.$arquivo_nome;
			$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

			echo `rm $arquivo_completo_tmp `;
			echo `rm $arquivo_completo_tmp.zip `;
			echo `rm $arquivo_completo.zip `;
			echo `rm $arquivo_completo `;

			$fp = fopen ($arquivo_completo_tmp,"w");
			fputs ($fp, "OS\t");
			fputs ($fp, "Consumidor/Revenda\t");
			fputs ($fp, "N° Série\t");
			fputs ($fp, "Código Posto\t");
			fputs ($fp, "Posto\t");
			fputs ($fp, "Defeito Reclamado\t");
			fputs ($fp, "Defeito Constatado\t");
			fputs ($fp, "Solução\t");
			fputs ($fp, "Peça\t");
			fputs ($fp, "Defeito Peça\r\n");

		}else{
			echo "<center><TABLE width='600' border='0' cellspacing='2' cellpadding='2' align='center' name='relatorio' id='relatorio' class='tablesorter' style=' border:#485989 1px solid; background-color: #e6eef7 '>";
			echo "<thead>";
			echo "<tr height='15'>";
			echo "<td>OS</td>";
			echo "<td>Consumidor/&nbsp;&nbsp;&nbsp;&nbsp;<br>Revenda&nbsp;&nbsp;&nbsp;&nbsp;</td>";
			echo "<td>N° Série</td>";
			echo "<td>Código Posto&nbsp;&nbsp;&nbsp;&nbsp;</td>";
			echo "<td>Posto</td>";
			echo "<td nowrap >Defeito <br>Reclamado</td>";
			echo "<td>Defeito Constatado</td>";
			echo "<td>Solução</td>";
			echo "<td>Peça</td>";
			echo "<td>Defeito Peça&nbsp;&nbsp;&nbsp;</td>";
			echo "</tr>";
			echo "</thead>";
			echo "<tbody>";
		}

		for($x=0;pg_numrows($res)>$x;$x++)		{
			
			$data_abertura                 = pg_result($res,$x,data_abertura);
			$os                 = pg_result($res,$x,os);
			$sua_os             = pg_result($res,$x,sua_os);
			$consumidor_revenda = pg_result($res,$x,consumidor_revenda);
			$serie              = pg_result($res,$x,serie);
			$posto_nome         = pg_result($res,$x,posto_nome);
			$posto_codigo      = pg_result($res,$x,posto_codigo);
			$defeito_reclamado  = pg_result($res,$x,defeito_reclamado);
			$defeito_constatado = pg_result($res,$x,defeito_constatado);
			$peca               = pg_result($res,$x,peca);
			$defeito_peca       = pg_result($res,$x,servico_peca);
			$solucao            = pg_result($res,$x,solucao);

			if($login_fabrica==3 AND $gera_xls==true){
				fputs($fp,"$sua_os\t");
				fputs($fp,"$consumidor_revenda\t");
				fputs($fp,"$serie\t");
				fputs($fp,"$posto_codigo\t");
				fputs($fp,"$posto_nome\t");
				fputs($fp,"$defeito_reclamado\t");
				fputs($fp,"$defeito_constatado\t");
				fputs($fp,"$solucao\t");
				fputs($fp,"$peca\t");
				fputs($fp,"$defeito_peca\t");
				fputs($fp,"\r\n");
			}else{
				echo "<tr class='Conteudo' height='15'>";
				echo "<td><font size='1'><a href='os_press.php?os=$os' target='blank'>$sua_os</a></font></td>";
				echo "<td align='center'><font size='1'>$consumidor_revenda</font></td>";
				echo "<td align='left'><font size='1'>$serie</font></td>";
				echo "<td align='left'><font size='1'>$posto_codigo</font></td>";
				echo "<td align='left'><font size='1'>$posto_nome</font></td>";
				echo "<td align='left'><font size='1'>$defeito_reclamado</font></td>";
				echo "<td align='left'><font size='1'>$defeito_constatado</font></td>";
				echo "<td align='left'><font size='1'>$solucao</font></td>";
				echo "<td align='left' nowrap ><font size='1'>$peca</font></td>";
				echo "<td align='left'><font size='1'>$defeito_peca</font></td>";
				echo "</tr>";
			}
		}

		if($login_fabrica==3 AND $gera_xls==true){
			fclose ($fp);
			flush();

			//gera o zip
			echo `cd $path_tmp; rm -rf $arquivo_nome.zip; zip -o $arquivo_nome.zip $arquivo_nome > /dev/null ; mv  $arquivo_nome.zip $path `;

			echo "<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
			echo "<tr>";
			echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Download em formato TXT (Colunas separadas com TABULAÇÃO)</font><br><a href='xls/$arquivo_nome.zip'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>Clique aqui para fazer o download </font></a> </td>";
			echo "</tr>";
			echo "</table>";
		}else{
			echo "</tbody>";
			echo "</table>";
		}
	}else{
		echo "<center>";
		echo "<FONT SIZE='2'>Não foram Encontrados Resultados para esta Pesquisa!</FONT>";
		echo "</center>";
	}
}
echo "<br>";

include "rodape.php";
?>
