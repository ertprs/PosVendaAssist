<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "includes/funcoes.php";

include 'autentica_admin.php';

// PEGA OS PARAMETROS QUE FORAM PASSADOS PARA PODER SER FEITO O SELECT

$produto       = $_GET["produto"];
$serie_inicial = $_GET["serie_inicial"];
$serie_final   = $_GET["serie_final"];
$mes           = $_GET["mes"];
$ano           = $_GET["ano"];

$meses = array (1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");


echo "<html>";
echo "<head>";
echo "<title>RELATÓRIO DE PRODUÇÃO</title>";
echo "</head>";
?>
<style type='text/css'>
	.Titulo {
		text-align: center;
		font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
		font-size: 12px; font-weight: bold;
		color: #FFFFFF;
		background-color: #596D9B;
		};
	.Conteudo {
		font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
		font-size: 12px; font-weight: normal;
		};
	.BotaoNovo {
		border: 1px solid #596D9B;
		font-size: 11px;
		font-family: Arial, Helvetica, sans-serif;
		color: #596D9B; background-color: #FFFFFF;
		font-weight: bold;
	};
</style>
<?

echo "<body>";

echo ;
//CABEÇALHO ONDE VAI O NOME DO PRODUTO
$sql="SELECT referencia, descricao
		FROM tbl_produto
		WHERE produto=$produto";
$res = pg_exec ($con,$sql);
if (pg_numrows($res) > 0) {
	 $descricao            = trim(pg_result($res,0,descricao));
	 $referencia           = trim(pg_result($res,0,referencia));
	 echo '<CENTER><span class="Titulo">'.$meses[$mes] . " / " . $ano.'<br></span><span class="Conteudo">Produto: <b>'.$referencia.' - '.$descricao.' </b></span> <br><BR></CENTER>';
}

//SELECIONA A DESCRIÇÃO DO DEFEITO APARTIR DOS PARAMETROS PASSADOS



$sql="  SELECT  count(tbl_os.defeito_constatado) AS total,
				tbl_defeito_constatado.descricao
		FROM    tbl_os
		JOIN  tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
		WHERE tbl_os.fabrica = $login_fabrica
		AND   tbl_os.produto = $produto
		AND   tbl_os.serie BETWEEN '$serie_inicial' AND '$serie_final'
		GROUP BY tbl_defeito_constatado.descricao";
//echo $sql;
$res = pg_exec($con,$sql);

if ( pg_numrows($res)>0){
	echo "<table width='100%' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
		echo "<tr class='Titulo'>";
		echo "<td>Total de Defeitos</td>";
		echo "<td>Descrição</td>";
		echo "</tr>";
	for ($x = 0; $x < pg_numrows($res); $x++) {
		$descricao_defeito           = trim(pg_result($res,$x,descricao));
		$total_defeitos              = trim(pg_result($res,$x,total));
		echo "<tr>";
		echo "<td class='Conteudo'>$total_defeitos</td>";
		echo "<td class='Conteudo'>$descricao_defeito</td>";
		echo "</tr>";
	}
	echo "</table>";
	echo "<br><center><button type='button' class='BotaoNovo'name='btnfecha' title='Fechar' onclick='javascript: window.close();'>FECHAR</button>&nbsp;<button type='button' name='btnvolta' title='Voltar' onclick='javascript: history.back();' class='BotaoNovo'>VOLTAR</button></center>";
}else{
	echo "<table width='100%'height='90%' border='0' cellpadding='0' cellspacing='0' bordercolor='#FFFFFF' >";
	echo"<tr><td align='center' valign='middle'><b>Nenhum Defeito Relatado!<b></td></tr>";
	echo"<tr><td height='10%' align='center' valign='middle'><button type='button' class='BotaoNovo'name='btnfecha' title='Fechar' onclick='javascript: window.close();'>FECHAR</button>&nbsp;<button type='button' name='btnvolta' title='Voltar' onclick='javascript: history.back();' class='BotaoNovo'>VOLTAR</button></td></tr>";
	echo"</table>";
	}
	
echo "</body>";
echo "</html>";
?>
