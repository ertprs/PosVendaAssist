<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

include "funcoes.php";

$admin_privilegios = "gerencia";
include "autentica_admin.php";


# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$busca      = $_GET["busca"];
	$tipo_busca = $_GET["tipo_busca"];
	
	if (strlen($q)>2){
		if ($tipo_busca=="produto"){
			$sql = "SELECT tbl_produto.produto,
							tbl_produto.referencia,
							tbl_produto.descricao
					FROM tbl_produto
					JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
					WHERE tbl_linha.fabrica = $login_fabrica ";
			
			if ($busca == "codigo"){
				$sql .= " AND tbl_produto.referencia like '%$q%' ";
			}else{
				$sql .= " AND UPPER(tbl_produto.descricao) like UPPER('%$q%') ";
			}
			
			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$produto    = trim(pg_result($res,$i,produto));
					$referencia = trim(pg_result($res,$i,referencia));
					$descricao  = trim(pg_result($res,$i,descricao));
					echo "$produto|$descricao|$referencia";
					echo "\n";
				}
			}
		}
	}
	exit;
}


$msg_erro = "";

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

if (strlen($_POST['btn_acao']) > 0 ) {


	$mes = trim (strtoupper ($_POST['mes']));
	$ano = trim (strtoupper ($_POST['ano']));

	if (  strlen ($mes) == 0 OR strlen ($ano) == 0 )  {
		$msg_erro = "Selecione o mês e o ano para fazer a pesquisa";
	}


	if (strlen($mes) > 0 and strlen($ano)>0) {
		$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
		$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
		$data_inicial = "'$data_inicial'";
		$data_final   = "'$data_final'";
	}
	$produto_referencia = trim(strtoupper($_POST['produto_referencia']));

	if (strlen ($produto_referencia) > 0) {
		$sqlX = "SELECT produto FROM tbl_produto JOIN tbl_linha USING (linha) WHERE tbl_linha.fabrica = $login_fabrica AND tbl_produto.referencia = '$produto_referencia'";
		$resX = pg_exec ($con,$sqlX);
		$produto = pg_result ($resX,0,0);
		$cond_produto= "and tbl_os.produto = $produto ";
	}

}

$layout_menu = "gerencia";
$title = "RELATÓRIO DE GERÊNCIA";
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



.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
}

.subtitulo{

color: #7092BE
}

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}
</style>

<? include "javascript_pesquisas.php"; ?>
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script language="JavaScript">
$().ready(function() {
	
	function formatItem(row) {
		return row[2] + " - " + row[1];
	}
	/* Busca por Produto */
	$("#produto_descricao").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#produto_descricao").result(function(event, data, formatted) {
		$("#produto_referencia").val(data[2]) ;
	});

	/* Busca pelo Nome */
	$("#produto_referencia").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#produto_referencia").result(function(event, data, formatted) {
		$("#produto_descricao").val(data[1]) ;
		//alert(data[2]);
	});

});


</script>


<br>




<form name="frm_consulta" method="post" action="<?echo $PHP_SELF?>">

<input type="hidden" name="acao">

<table width="700" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
	<? if(strlen($msg_erro)>0){ ?>
		<tr class="msg_erro"> <td colspan="3"><? echo $msg_erro; ?></td></tr>
	<? } ?>
	<tr class="titulo_tabela" height="30">
		<td align="center" colspan="3">Parâmetros de Pesquisa</td>
	</tr>
	<tr align='left'>
		<td width="130">&nbsp;</td>
		<td> * Mês</td>
		<td> * Ano</td>
	</tr>
	<tr align='left'>
		<td width="100">&nbsp;</td>
		<td>
			<select name="mes" size="1" class="frm">
			<option value=''></option>
			<?
			for ($i = 1 ; $i <= count($meses) ; $i++) {
				echo "<option value='$i'";
				if ($mes == $i) echo " selected";
				echo ">" . $meses[$i] . "</option>";
			}
			?>
			</select>
		</td>
		<td>
			<select name="ano" size="1" class="frm">
			<option value=''></option>
			<?
			for ($i = 2003 ; $i <= date("Y") ; $i++) {
				echo "<option value='$i'";
				if ($ano == $i) echo " selected";
				echo ">$i</option>";
			}
			?>
			</select>

		</td>

	</tr>


	<tr align='left'>
		<td width="100">&nbsp;</td>
		<td>Ref. Produto</td>
		<td>Descrição Produto</td>
	</tr>


	<tr align='left'>
		<td width="100">&nbsp;</td>
		<td>
		<input class="frm" type="text" name="produto_referencia" id="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" > 
		&nbsp;
		<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_consulta.produto_referencia, document.frm_consulta.produto_descricao,'referencia')">
		</td>

		<td>
		<input class="frm" type="text" name="produto_descricao" id="produto_descricao" size="30" value="<? echo $produto_descricao ?>" >
		&nbsp;
		<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_consulta.produto_referencia, document.frm_consulta.produto_descricao,'descricao')">
	</tr>
	<tr>
		<td colspan='3' align='center'><br><input type="submit" name="btn_acao" value="Pesquisar"><br></td>
	</tr>

</table>


</form>
<BR><BR>
<?
if(strlen($_POST['btn_acao']) > 0 and strlen($msg_erro) ==0 ){
	$sql="SELECT    referencia                                              ,
					descricao                                               ,
					tbl_os.produto                                          ,
					count (tbl_os.os) as os_qtde                            ,
					trunc(sum(tbl_os.mao_de_obra::numeric),2) as mao_de_obra,
					count(troca.qtde) as troca_qtde                         ,
					count(produto.qtde) as produto_qtde                     ,
					peca.qtde as peca_qtde                                  ,
					trunc(peca.preco::numeric,2) as preco
			FROM tbl_os
			LEFT JOIN (
				select count(tbl_os.produto) as qtde,tbl_os.os
					FROM tbl_os
					JOIN tbl_os_produto using(os)
					JOIN tbl_os_item using(os_produto)
					JOIN tbl_os_extra on tbl_os_extra.os=tbl_os.os
					JOIN tbl_pedido on tbl_os_item.pedido=tbl_pedido.pedido
					LEFT JOIN tbl_os_troca on tbl_os.os=tbl_os_troca.os
					WHERE tbl_os.data_abertura between $data_inicial and $data_final and tbl_os_extra.extrato is not null
					AND tbl_os.fabrica=$login_fabrica and (os_troca is null or gerar_pedido is not true)
					AND tbl_os_item.peca <> tbl_os.produto
					GROUP BY tbl_os.os,tbl_os.produto
				) produto on tbl_os.os =produto.os
			LEFT JOIN (
				SELECT count(tbl_os.produto) as qtde,tbl_os.os
					FROM  tbl_os
					JOIN  tbl_os_troca on tbl_os.os=tbl_os_troca.os
					JOIN  tbl_os_extra on tbl_os.os =tbl_os_extra.os
					WHERE tbl_os.data_abertura between $data_inicial and $data_final
					AND   tbl_os_extra.extrato is not null and tbl_os.fabrica=$login_fabrica
					AND   tbl_os_troca.gerar_pedido is true
					GROUP BY tbl_os.os
				) troca on tbl_os.os =troca.os
			LEFT JOIN (
				SELECT count(distinct tbl_os_item.os_item) as qtde,sum(tbl_pedido_item.preco) as preco,tbl_os.produto
					FROM  tbl_os
					JOIN tbl_os_produto using(os)
					JOIN tbl_os_item using(os_produto)
					JOIN tbl_os_extra on tbl_os_extra.os=tbl_os.os
					JOIN tbl_pedido_item on tbl_os_item.pedido=tbl_pedido_item.pedido and tbl_pedido_item.peca = tbl_os_item.peca
					LEFT JOIN tbl_os_troca on tbl_os.os=tbl_os_troca.os
					WHERE tbl_os.data_abertura between $data_inicial and $data_final
					AND tbl_os_extra.extrato is not null
					AND tbl_os.fabrica=$login_fabrica
					AND (os_troca is null or gerar_pedido is not true)
					AND tbl_os_item.peca <> tbl_os.produto
					GROUP BY tbl_os.produto
				) peca on tbl_os.produto=peca.produto
			JOIN tbl_produto on tbl_os.produto=tbl_produto.produto
			JOIN tbl_os_extra on tbl_os.os = tbl_os_extra.os
			WHERE tbl_os.fabrica=$login_fabrica 
			AND tbl_os.data_abertura between $data_inicial and $data_final
			AND tbl_os_extra.extrato is not null 
			$cond_produto
			GROUP BY tbl_produto.referencia,
					 tbl_produto.descricao ,
					 tbl_os.produto        ,
					 peca.preco            ,
					 peca.qtde
			ORDER BY descricao,referencia";
	$res=pg_exec($con,$sql);
	if(pg_numrows($res) > 0){
		echo "<table border='1' cellpadding='2' cellspacing='1' class='tabela' width='700' align='center'>";
		echo "<thead>";
		echo "<tr class='titulo_tabela' height='15'>";
		echo "<td colspan='7'>Resultado de Pesquisa para o mês de $meses[$mes]</td>";
		echo "</tr>";
		echo "<tr class='titulo_coluna' height='15'>";
		echo "<td>Produto</td>";
		echo "<td>Total de Produtos</td>";
		echo "<td>Total de Peças Utilizadas</td>";
		echo "<td>Mão-de-Obra</td>";
		echo "<td>Valor de Peças</td>";
		echo "<td>Total de Produtos Trocados</td>";
		echo "<td>Total de Produtos que Usaram Peças</td>";
		echo "</tr>";
		echo "</thead>";
		echo "<tbody>";

		flush();
			
		echo `rm /tmp/assist/relatorio-produtos-$login_fabrica.xls`;

		$fp = fopen ("/tmp/assist/relatorio-produtos-$login_fabrica.html","w");

		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>Relatório - Produtos - $data");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");

		fputs ($fp, "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'  align='center' width='96%'>");
		fputs ($fp, "<thead>");
		fputs ($fp, "<tr class='Titulo' height='15'>");
		fputs ($fp, "<td>Produto</td>");
		fputs ($fp, "<td>Total de Produtos</td>");
		fputs ($fp, "<td>Total de Peças Utilizadas</td>");
		fputs ($fp, "<td>Mão-de-Obra</td>");
		fputs ($fp, "<td>Valor de Peças</td>");
		fputs ($fp, "<td>Total de Produtos Trocados</td>");
		fputs ($fp, "<td>Total de Produtos que Usaram Peças</td>");
		fputs ($fp, "</tr>");
		fputs ($fp, "</thead>");
		fputs ($fp, "<tbody>");

		for($i=0; $i<pg_numrows($res); $i++){
			$referencia    = pg_result($res,$i,referencia);
			$descricao     = pg_result($res,$i,descricao);
			$produto       = pg_result($res,$i,produto);
			$os_qtde       = pg_result($res,$i,os_qtde);
			$mao_de_obra   = pg_result($res,$i,mao_de_obra);
			$troca_qtde    = pg_result($res,$i,troca_qtde);
			$produto_qtde  = pg_result($res,$i,produto_qtde);
			$peca_qtde     = pg_result($res,$i,peca_qtde);
			$preco         = pg_result($res,$i,preco);

			if(strlen($preco)==0) $preco=0;
			if(strlen($peca_qtde)==0) $peca_qtde=0;

			if ($i % 2 == 0) {
				$cor   = "#F1F4FA";
			}else{
				$cor   = "#F7F5F0";
			}
			echo "<tr class='Conteudo' bgcolor='$cor'>";
			echo "<td align='left'><acronym title='Referência: $referencia \nDescrição: $descricao \n style='cursor: help;'>$descricao</acronym> </td>";
			echo "<td align='center'>$os_qtde</td>";
			echo "<td align='center'>$peca_qtde</td>";
			echo "<td align='center'>$mao_de_obra</td>";
			echo "<td align='center'>$preco</td>";
			echo "<td align='center'>$troca_qtde</td>";
			echo "<td align='center'>$produto_qtde</td>";
			echo "</tr>";

			fputs ($fp,  "<tr class='Conteudo' bgcolor='$cor'>");
			fputs ($fp,  "<td align='left'>$referencia - $descricao </td>");
			fputs ($fp,  "<td align='center'>$os_qtde</td>");
			fputs ($fp,  "<td align='center'>$peca_qtde</td>");
			fputs ($fp,  "<td align='center'>$mao_de_obra</td>");
			fputs ($fp,  "<td align='center'>$preco</td>");
			fputs ($fp,  "<td align='center'>$troca_qtde</td>");
			fputs ($fp,  "<td align='center'>$produto_qtde</td>");
			fputs ($fp,  "</tr>");
		}
		echo "</tbody></table>";


		fputs ($fp,"</tbody></table>");
		fputs ($fp,"</body>");
		fputs ($fp,"</html>");
		fclose ($fp);

		$data = date("Y-m-d").".".date("H-i-s");

		echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/xls/relatorio-produtos-$login_fabrica.xls /tmp/assist/relatorio-produtos-$login_fabrica.html`;
		echo "<BR><BR>";
		echo"<table width='700' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo"<tr>";
		echo "<td align='center'><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='xls/relatorio-produtos-$login_fabrica.xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
		echo "</tr>";
		echo "</table>";
	}else{
		echo"<table width='700' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo"<tr>";
		echo "<td align='center'><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Nenhum Resultado encontrado</font></td>";
		echo "</tr>";
		echo "</table>";
	
	}
}
?>
<? include "rodape.php" ?>
