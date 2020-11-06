<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia,call_center";
include 'autentica_admin.php';

$msg_erro = "";

$layout_menu = "gerencia";
$title = "Call-Center - Relatório de Produtos - Detalhes dos atendimentos do produto";

include "cabecalho.php";

?>

<p>

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

a:link.top{
	color:#ffffff;
}
a:visited.top{
	color:#ffffff;
}
a:hover.top{
	color:#ffffff;
}

.table_line {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
}

</style>

<br>

<?

if (strlen($_GET["produto"]) > 0)           $produto           = $_GET["produto"];
//if (strlen($_GET["defeito_reclamado"]) > 0) $defeito_reclamado = $_GET["defeito_reclamado"];
if (strlen($_GET["natureza"]) > 0)          $natureza          = $_GET["natureza"];
if (strlen($_GET["data_inicial"]) > 0)      $data_inicial      = $_GET["data_inicial"];
if (strlen($_GET["data_final"]) > 0)        $data_final        = $_GET["data_final"];
if (strlen($_GET["nome_comercial"]) > 0)    $nome_comercial    = trim($_GET["nome_comercial"]);
if (strlen($_GET["defeito_descricao"]) > 0) $defeito_descricao = $_GET["defeito_descricao"];
if (strlen($_GET["defeito_reclamado"]) > 0) $defeito_reclamado = $_GET["defeito_reclamado"];
if (strlen($_GET["outro"]) > 0)             $outro             = $_GET["outro"];
if (strlen($_GET["anual"]) > 0)             $anual             = $_GET["anual"];

if (strlen($data_inicial) > 0 AND strlen($data_final) > 0) {
/*
	$sql = "SELECT referencia, descricao
			FROM   tbl_produto
			WHERE  produto = $produto ";
	$res = pg_exec ($con,$sql);
*/
?>
<table width='700' align='center' border='0' cellspacing='1' cellpadding='2'>
	
<?
$sql = "SELECT tbl_callcenter.callcenter                                    ,
				to_char (tbl_callcenter.data,'DD/MM/YYYY') AS data           ,
				tbl_produto.referencia                                       ,
				tbl_produto.descricao                                        ,
				tbl_callcenter.reclamacao                                    ,
				tbl_callcenter.serie                                         ,
				tbl_cliente.nome                           AS consumidor_nome,
				tbl_callcenter.sua_os                                        ,
				tbl_posto.nome                             AS posto_nome     ,
				tbl_callcenter.solucionado                                   
			FROM tbl_callcenter
			LEFT JOIN tbl_admin               ON tbl_admin.admin = tbl_callcenter.admin
			LEFT JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_callcenter.defeito_reclamado
			LEFT JOIN tbl_produto           USING (produto)
			LEFT JOIN tbl_posto             USING (posto)
			LEFT JOIN tbl_cliente           ON tbl_callcenter.cliente     = tbl_cliente.cliente
			LEFT JOIN tbl_posto_fabrica     ON tbl_posto.posto            = tbl_posto_fabrica.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_cidade            ON tbl_cidade.cidade = tbl_cliente.cidade";
if($anual == 0){
	$sql .= " WHERE tbl_callcenter.data BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'";
}else{
	$sql .= " WHERE tbl_callcenter.data BETWEEN '$anual-01-01 00:00:00' AND '$anual-12-31 23:59:59' ";
}

$sql .= " AND tbl_callcenter.natureza = '$natureza' AND tbl_callcenter.fabrica = $login_fabrica ";

if($nome_comercial == "FORA DE LINHA"){
	$sql .= " AND tbl_callcenter.produto in ('8059','8042','11159','1027','1042','1039','1056','8040','1043','1064','7494') ";
	
	if(strlen($defeito_reclamado) > 0)
		$sql .= " AND tbl_callcenter.defeito_reclamado = '$defeito_reclamado' ";
	else
		$sql .= " AND tbl_callcenter.defeito_reclamado IS NULL ";

//	$sql .= " AND tbl_callcenter.defeito_reclamado = '$defeito_reclamado' OR tbl_callcenter.defeito_reclamado is null ";
}else{

	if(strlen($defeito_reclamado) > 0)
		$sql .= " AND tbl_callcenter.defeito_reclamado = '$defeito_reclamado' ";
	else
		$sql .= " AND tbl_callcenter.defeito_reclamado IS NULL ";

	if(strlen($produto) > 0 AND $nome_comercial == "FORA DE LINHA"){
		$sql .= " AND tbl_callcenter.produto = '$produto' ";
	}

	if($nome_comercial <> "XXX" AND strlen($nome_comercial) > 0 AND $nome_comercial <> "FORA DE LINHA"){
		$sql .= " AND tbl_produto.nome_comercial = '$nome_comercial' ";
	}else{
		if($nome_comercial <> "FORA DE LINHA")
			$sql .= " AND (tbl_produto.nome_comercial = '' OR tbl_produto.nome_comercial IS NULL) ";
	}
}

$sql .= " AND tbl_callcenter.excluida is not true ";


$res = pg_exec ($con,$sql);


//echo "<br>$sql<br>";

echo "<TR class='menu_top'>\n";
echo "<TD colspan=8>$msg</TD>\n";
echo "</TR>\n";
echo "<TR class='menu_top'>\n";
echo "<TD>DATA</TD>\n";
echo "<TD>PRODUTO</TD>\n";
echo "<TD>Nº CHAMADO</TD>\n";
echo "<TD>CLIENTE</TD>\n";
echo "<TD>OS</TD>\n";
echo "<TD>SOLUCIONADO</TD>\n";
echo "<TD width='170' colspan='2'>AÇÕES</TD>\n";
echo "</TR>\n";

//if ($ip == "201.0.9.216") echo pg_numrows($res);
for ($i = 0 ; $i < pg_numrows($res) ; $i++) {

	$callcenter         = trim(pg_result ($res,$i,callcenter));
	$data               = trim(pg_result ($res,$i,data));
	$sua_os             = trim(pg_result ($res,$i,sua_os));
	$serie              = trim(pg_result ($res,$i,serie));
	$consumidor_nome    = trim(pg_result ($res,$i,consumidor_nome));
	$posto_nome         = trim(pg_result ($res,$i,posto_nome));
	$produto_nome       = trim(pg_result ($res,$i,descricao));
	$produto_referencia = trim(pg_result ($res,$i,referencia));
	$solucionado        = trim(pg_result ($res,$i,solucionado));
	$btn = 'amarelo';
	$cor = "#F7F5F0";
	if ($i % 2 == 0) {$cor = '#F1F4FA';$btn = 'azul';}

	echo "<TR class='table_line' style='background-color: $cor;'>\n";
	echo "<TD align=center nowrap>$data</TD>\n";
	echo "<TD nowrap><ACRONYM TITLE=\"$produto_referencia - $produto_nome\">".substr($produto_nome,0,17)."</ACRONYM></TD>\n";
	echo "<TD align=center nowrap>$callcenter</TD>\n";
	echo "<TD nowrap><ACRONYM TITLE=\"$consumidor_nome\">".substr($consumidor_nome,0,17)."</ACRONYM></TD>\n";
	echo "<TD nowrap><ACRONYM TITLE=\"$posto_nome\">$sua_os</ACRONYM></TD>\n";
	echo "<TD align=center nowrap>";
	if ($solucionado == 't') 
		echo "Solucionado";
	else 
		echo "Em andamento";
	echo "</TD>\n";
	echo "<TD width=85><a href='callcenter_press.php?callcenter=$callcenter'><img src='imagens/btn_consultar_".$btn.".gif'></a></TD>\n";
	echo "<TD width=85>";
	if ($solucionado <> 't') {
		echo "<a href='callcenter_cadastro_1.php?callcenter=$callcenter' target='_blank'><img src='imagens_admin/btn_alterar_".$btn.".gif'></a>";
	}
	echo "</TD>";
	echo "</TR>\n";
}
	
}else{
	echo "<center>Por favor, selecione todos os dados na tela anterior!!!</center>";
}
?>

</table>

<br><br>

<? include "rodape.php"; ?>