<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "includes/funcoes.php";

$admin_privilegios="gerencia";
include 'autentica_admin.php';
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
.Conteudo1 {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	background-color: #ffff99;
	font-weight: normal;
}
.Conteudo2 {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	background-color: #99ff99;
	font-weight: normal;
}
.BotaoNovo {
	border: 1px solid #596D9B;
	font-size: 11px;font-family: Arial, Helvetica, sans-serif;
	color: #596D9B;
	background-color: #FFFFFF;
	font-weight: bold;
	}
	.Total {
	
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	color: #FFFFFF;
	background: #006600
	font-size: 12px;
	font-weight: bold;
}
</style>

<?
$erro = "";

$meses = array (1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

$layout_menu = "gerencia";
$title = "RELATÓRIO DE PEÇAS DIGITADAS";

include "cabecalho.php";
?>


<? if (strlen($erro) > 0) { ?>
<br>
<table width="700" border="0" cellpadding="2" cellspacing="2" align="center" class="error">
	<tr>
		<td><? echo $erro; ?></td>
	</tr>
</table>
<? } ?>

<br>

<form name="frm_pesquisa" method="get" action="<? echo $PHP_SELF ?>">

<input type="hidden" name="acao">

<table width="600" border="0" cellspacing="0" cellpadding="2" align="center">
	<tr>
		<td class="Titulo">RELATÓRIO DE PEÇAS FATURADAS E GARANTIA DOS ÚLTIMOS 4 MESES</td>
	</tr>
</table>

</form>


<!-- ################# FATURADOS -->

<?
$sqlX = "SELECT to_char (current_timestamp - INTERVAL '3 MONTHS', 'YYYY-MM')";
$res = pg_exec($con,$sqlX);
$data = pg_result($res,0,0);

$sql = "SELECT  x.mes,
				x.ano, 
				tbl_linha.nome, 
				tbl_tipo_pedido.descricao, 
				x.qtde, 
				x.valor 
		FROM (
		SELECT  TO_CHAR (tbl_pedido.exportado,'MM') AS mes, 
				TO_CHAR (tbl_pedido.exportado,'YYYY') AS ano, 
				tbl_pedido.tipo_pedido, tbl_pedido.linha,
				SUM(tbl_pedido_item.qtde) as qtde, 
				ROUND (SUM(tbl_pedido_item.qtde * tbl_pedido_item.preco)::numeric,2) as valor
		FROM  tbl_pedido_item
		JOIN  tbl_pedido ON tbl_pedido.pedido = tbl_pedido_item.pedido
		WHERE tbl_pedido.fabrica = $login_fabrica 
		AND   tbl_pedido.exportado::date >= '$data-01'
		GROUP BY TO_CHAR (tbl_pedido.exportado,'MM'), 
		TO_CHAR (tbl_pedido.exportado,'YYYY'), 
				tbl_pedido.linha, 
				tbl_pedido.tipo_pedido
		) x
		JOIN tbl_linha       ON tbl_linha.linha = x.linha 
		JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = x.tipo_pedido
		ORDER BY tbl_linha.nome, 
		tbl_tipo_pedido.descricao, 
		x.ano, 
		x.mes" ;
//echo nl2br($sql); exit;
$res = pg_exec($con,$sql);

if ( pg_numrows($res)>0){
?>
<br>
<table width="700"  border="0" cellpadding="1" cellspacing="1" >
<?

	echo "<tr>\n";
	echo "<td rowspan=\"2\"class = \"Titulo\">LINHA</td>\n";
	echo "<td rowspan=\"2\"class = \"Titulo\">TIPO</td>\n";
	for ($i=3; $i>=0; $i--){
		$sqlZ = "SELECT to_char (current_timestamp - INTERVAL '$i MONTHS', 'MM')";
		$resZ = pg_exec($con,$sqlZ);
		$arrmes = pg_result($resZ,0,0);
		$arrmes = intval($arrmes);

		$sqlZ = "SELECT to_char (current_timestamp - INTERVAL '$i MONTHS', 'YYYY')";
		$resZ = pg_exec($con,$sqlZ);
		$arrano = pg_result($resZ,0,0);

		echo "<td colspan=\"2\"class = \"Titulo\">".$meses[$arrmes]." - ".$arrano."</td>\n";
	}
	echo "</tr>\n";
	echo "<tr>\n";
	for ($i=3; $i>=0; $i--){
		echo "<td class = \"Titulo\">Qtde</td>\n";
		echo "<td class = \"Titulo\">Valor</td>\n";
	}
	echo "</tr>\n";

//INICIA O FOR PRA LISTAR TODOS OS DADOS ESPECIFICOS DO MES
	$total_linhas = pg_numrows($res);
	for ($x = 0; $x < $total_linhas; $x++) {
		$mes       = trim(pg_result($res,$x,mes));
		$ano       = trim(pg_result($res,$x,ano));
		$nome      = trim(pg_result($res,$x,nome));
		$descricao = trim(pg_result($res,$x,descricao));
		$qtde      = trim(pg_result($res,$x,qtde));
		$valor     = trim(pg_result($res,$x,valor));

		if ($x <> 0 and $descricao_antigo <> $descricao) echo "</tr><tr>";
		if($nome_antigo <> $nome) {
			if ($bg <> 'class="Conteudo1"') $bg = 'class="Conteudo1"'; 
			else                            $bg = 'class="Conteudo2"';
			echo "<td rowspan='2' $bg>$nome</td>";
		}

		if($descricao_antigo<>$descricao) echo "<td $bg>$descricao</td>";

		echo "<td align=\"center\" $bg>$qtde</td>\n";
		echo "<td align=\"right\" $bg>".number_format($valor,2,',','.')."</td>\n";

		//if(($x%6)==1) echo "</tr>";

		$nome_antigo      = $nome;
		$descricao_antigo = $descricao;
	}//fecha o segundo for
?>

</table><br><br>
		
<?
		
}//fecha o if q verifica se há registros

include "rodape.php";
?>
