<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "includes/funcoes.php";
include "funcoes.php";

$admin_privilegios="cadastros";
include "autentica_admin.php";

$msg_erro   = "";
$msg_update = "";

if (strlen($_GET['produto']) > 0) 
	$produto = strtoupper($_GET['produto']);

if (strlen($_GET['serie']) > 0) 
	$serie = strtoupper($_GET['serie']);

if (strlen($_GET['data_inicio']) > 0) 
	$data_inicio= strtoupper($_GET['data_inicio']);

if (strlen($_GET['data_fim']) > 0) 
	$data_fim = strtoupper($_GET['data_fim']);

$layout_menu = "cadastro";
$title = "Cadastro de Números de Série";


if (strlen($produto) == 0) {
	$title = "RELATÓRIO - NÚMERO DE SÉRIE - ORDENS DE SERVIÇO";
	include "cabecalho.php";	
} else {
	echo "<title>RELATÓRIO - NÚMERO DE SÉRIE - ORDENS DE SERVIÇO</title>";
	echo "<link type='text/css' rel='stylesheet' href='css/css.css'>";
}
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
.Titulo2 {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 14px;
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




<script language="JavaScript">
function FuncMouseOver (linha, cor) {
	linha.style.backgroundColor = cor;
}
function FuncMouseOut (linha, cor) {
	linha.style.backgroundColor = cor;
}
</script>




<BR>

<?
//mostra todos os números de série do produto
if (strlen($produto)>0 and strlen($serie)>0 and strlen($data_inicio)>0 and strlen($data_fim)>0) {
	$sql = "SELECT  tbl_os.os                                               ,
					tbl_os.sua_os                                           ,
					tbl_os.serie                                            ,
					CASE tbl_os.consumidor_revenda 
						WHEN 'C' THEN
							'Consumidor'
						ELSE
							'Revenda'
						END as tipo                                         ,
					tbl_os.consumidor_nome                                  ,
					to_char(tbl_os.data_digitacao,'DD/MM/YYYY') as digitacao,
					tbl_posto_fabrica.codigo_posto                          ,
					tbl_posto.nome                                           
			FROM   tbl_os
			JOIN   tbl_posto USING (posto)
			JOIN   tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
									AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE  tbl_os.fabrica = $login_fabrica
			AND    tbl_os.produto = $produto
			AND    tbl_os.serie   = '$serie'
			AND    tbl_os.data_digitacao::date BETWEEN '$data_inicio' AND '$data_fim'
			AND    tbl_os.excluida IS NOT TRUE
			ORDER BY tbl_os.data_digitacao,
					 tbl_os.sua_os";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		$sqld = "SELECT to_char('$data_inicio'::date,'DD/MM/YYYY') as inicio,
						to_char('$data_fim'::date,'DD/MM/YYYY') as fim";
		$resd = pg_exec($con, $sqld);
		$imprime_inicio = pg_result($resd,0,inicio);
		$imprime_fim    = pg_result($resd,0,fim);

		$sqlp = "SELECT referencia||' - '||descricao as prod FROM tbl_produto where produto = $produto";
		$resp = pg_exec($con, $sqlp);
		$imprime_produto = pg_result($resd,0,0);
		
		echo "<center><a href=\"javascript: window.close();\">Fechar Janela</a><center>";
		echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center' width='600'>";
		echo "<tr height='15' class='Titulo2'>";
		echo "<td colspan=6>Produto ($imprime_produto) - Série ($serie) </td>";
		echo "</tr>";
		echo "<tr height='15' class='Titulo2'>";
		echo "<td colspan=6>Ordens de Serviço digitadas no período de $imprime_inicio a $imprime_fim</td>";
		echo "</tr>";
		echo "<tr height='15' class='Titulo'>";
		echo "<td nowrap>&nbsp;OS&nbsp;</td>";
		echo "<td nowrap>&nbsp;Nº SÉRIE&nbsp;</td>";
		echo "<td nowrap>&nbsp;TIPO&nbsp;</td>";
		echo "<td nowrap>&nbsp;CONSUMIDOR&nbsp;</td>";
		echo "<td nowrap>&nbsp;DIGITAÇÃO&nbsp;</td>";
		echo "<td nowrap>&nbsp;POSTO&nbsp;</td>";
		echo "</tr>";
		
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$os              = trim(pg_result($res,$i,os));
			$sua_os          = trim(pg_result($res,$i,sua_os));
			$serie           = trim(pg_result($res,$i,serie));
			$tipo            = trim(pg_result($res,$i,tipo));
			$consumidor_nome = trim(pg_result($res,$i,consumidor_nome));
			$digitacao       = trim(pg_result($res,$i,digitacao));
			$codigo_posto    = trim(pg_result($res,$i,codigo_posto));
			$nome            = trim(pg_result($res,$i,nome));

			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;
			
			echo "<tr class='Conteudo' height='15' bgcolor='$cor' onmouseover=\"javascript: FuncMouseOver(this, '#FFCC99');\" onmouseout=\"javascript: FuncMouseOut(this, '$cor');\">";
			echo "<td nowrap align='left'>&nbsp;<a href='os_press?os=$os' target='_blank'>".$sua_os."&nbsp;</a></td>";
			echo "<td nowrap align='left'>&nbsp;".$serie."&nbsp;</td>";
			echo "<td nowrap align='left'>&nbsp;".$tipo."&nbsp;</td>";
			echo "<td nowrap align='left'>&nbsp;".$consumidor_nome."&nbsp;</td>";
			echo "<td nowrap align='left'>&nbsp;".$digitacao."&nbsp;</td>";
			echo "<td nowrap align='left'>&nbsp;".$codigo_posto." - ".$nome."&nbsp;</td>";
			echo "</tr>";
		}
		echo "</table>";
		echo "<h3>Total de " . pg_numrows($res) . " registro(s) cadastrado(s).</h3>";
	}
} else {
	echo "<script languague='javascript'>";
	echo "window.close();";
	echo "</script>";
}


if (strlen($produto) == 0) include "rodape.php";
?>