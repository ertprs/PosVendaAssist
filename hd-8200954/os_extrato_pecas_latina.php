<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$extrato = trim($_GET['extrato']);
if (strlen($extrato) == 0) $extrato = trim($_POST['extrato']);

if(strlen($extrato) == 0 or $login_fabrica<>15){ # HD 81361 Latinatec
	header("Location: os_extrato.php");
	exit;
}

$msg_erro = "";

$layout_menu = "os";
$title = "Relação de Peças no Extrato";

if(!empty($_GET['agrupar'])){
	$agrupar = trim($_GET["agrupar"]);
}else{
	$agrupar = "false";
}

include "cabecalho.php";

?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#000000;
	background-color: #d9e2ef
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF;
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

</style>

<p>
<?
if(strlen($msg_erro) > 0){
	echo "<TABLE width=\"650\" align='center' border=0>";
	echo "<TR>";

	echo "<TD align='center' class='error'>$msg_erro</TD>";

	echo "</TR>";
	echo "</TABLE>";
}

echo "<TABLE width=\"650\" align='center' border=0>";
echo "<TR class='menu_top'>\n";

if ($sistema_lingua=='ES')
	echo "<TD align='center'><a href='$PHP_SELF?agrupar=true&extrato=$extrato'><font color='#000000'>Agrupar por repuesto</font></a></TD>\n";
else
	echo "<TD align='center'><a href='$PHP_SELF?agrupar=true&extrato=$extrato'><font color='#000000'>Agrupar por peça</font></a></TD>\n";

if ($sistema_lingua=='ES')
	echo "<TD align='center'><a href='$PHP_SELF?agrupar=false&extrato=$extrato'><font color='#000000'>No agrupar</font></a></TD>\n";
else
	echo "<TD align='center'><a href='$PHP_SELF?agrupar=false&extrato=$extrato'><font color='#000000'>Não agrupar</font></a></TD>\n";

echo "</TR>";
echo "</TABLE>";

echo "<p>";

if ($agrupar == "false") {

	$sql = "SELECT	tbl_os.os                                                      ,
					tbl_os.sua_os                                                  ,
					tbl_os.consumidor_nome                                         ,
					tbl_produto.descricao  AS produto_nome                         ,
					tbl_produto.referencia AS produto_referencia                   ,
					tbl_peca.referencia    AS peca_referencia                      ,
					tbl_peca.descricao     AS peca_nome                            ,
					tbl_os_item.qtde       AS qtde                                 ,
					tbl_tabela_item.preco                                              ,
					to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao
			FROM    tbl_os
			JOIN    tbl_os_extra             ON tbl_os.os                               = tbl_os_extra.os
			JOIN    tbl_produto              ON tbl_os.produto                          = tbl_produto.produto
			JOIN    tbl_os_produto           ON tbl_os.os                               = tbl_os_produto.os
			JOIN    tbl_os_item              ON tbl_os_produto.os_produto               = tbl_os_item.os_produto
			JOIN    tbl_servico_realizado    ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
			JOIN    tbl_peca                 ON tbl_os_item.peca                        = tbl_peca.peca
			JOIN    tbl_extrato              ON tbl_extrato.extrato                     = tbl_os_extra.extrato
			LEFT    JOIN    tbl_posto_linha          ON tbl_posto_linha.linha = tbl_produto.linha AND tbl_posto_linha.posto = tbl_os.posto
			JOIN    tbl_tabela_item ON tbl_tabela_item.peca = tbl_os_item.peca 
			JOIN    tbl_tabela ON tbl_tabela_item.tabela = tbl_tabela.tabela and ativa AND tbl_tabela.fabrica = $login_fabrica

			WHERE   tbl_os_extra.extrato = $extrato
			AND     tbl_extrato.fabrica  = $login_fabrica
            AND (tbl_servico_realizado.troca_de_peca OR  tbl_servico_realizado.peca_estoque OR tbl_servico_realizado.gera_pedido)
            AND tbl_peca.devolucao_obrigatoria
			ORDER BY lpad(tbl_os.sua_os,10,' '), tbl_tabela_item.preco;";
}else{
//agrupado

	$sql = "SELECT			tbl_peca.peca                                                      ,
					tbl_peca.referencia        AS peca_referencia                      ,
					tbl_peca.descricao         AS peca_nome                            ,
					tbl_tabela_item.preco                                              ,
					sum(tbl_os_item.qtde)      AS qtde                                 ,
					to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao
			FROM    tbl_os
			JOIN    tbl_os_extra             ON tbl_os.os                               = tbl_os_extra.os
			JOIN    tbl_produto              ON tbl_os.produto                          = tbl_produto.produto
			JOIN    tbl_linha                ON tbl_linha.linha                         = tbl_produto.linha
											AND tbl_linha.fabrica                       = $login_fabrica
			JOIN    tbl_os_produto           ON tbl_os.os                               = tbl_os_produto.os
			JOIN    tbl_os_item              ON tbl_os_produto.os_produto               = tbl_os_item.os_produto
			JOIN    tbl_servico_realizado    ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
			JOIN    tbl_peca                 ON tbl_os_item.peca                        = tbl_peca.peca
			JOIN    tbl_extrato              ON tbl_extrato.extrato                     = tbl_os_extra.extrato
			AND     tbl_extrato.fabrica                     = $login_fabrica
			JOIN    tbl_tabela_item ON tbl_tabela_item.peca = tbl_os_item.peca 
			JOIN    tbl_tabela ON tbl_tabela_item.tabela = tbl_tabela.tabela and ativa AND tbl_tabela.fabrica = $login_fabrica
			WHERE   tbl_os_extra.extrato = $extrato
			AND     tbl_extrato.fabrica  = $login_fabrica
            AND (tbl_servico_realizado.troca_de_peca OR tbl_servico_realizado.peca_estoque OR tbl_servico_realizado.gera_pedido)
            AND tbl_peca.devolucao_obrigatoria 
			GROUP BY   		tbl_peca.peca         ,
						tbl_peca.referencia   ,
						tbl_peca.descricao    ,
						tbl_tabela_item.preco ,
						to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY')
			ORDER BY   tbl_peca.referencia";

}
$res = pg_exec ($con,$sql);
$totalRegistros = pg_numrows($res);

echo "<TABLE width='650' align='center' border='0' cellspacing='1' cellpadding='1'>\n";

if ($totalRegistros > 0){
	echo "<TR class='menu_top'>\n";

	if ($agrupar == "false") $colspan = "5";
	if ($agrupar == "true")  $colspan = "3";

	echo "<TD colspan='$colspan' align = 'center'>";
	if ($sistema_lingua=='ES')
		echo "EXTRACTO $extrato GENERADO EN " . pg_result ($res,0,data_geracao) ;
	else
		echo "EXTRATO $extrato GERADO EM " . pg_result ($res,0,data_geracao) ;

	echo "</TD>";

	echo "</TR>\n";

	echo "<TR class='menu_top'>\n";

	if ($agrupar == "false") {
		echo "<TD align='center' >OS</TD>\n";
		echo "<TD align='center' >CLIENTE</TD>\n";
	}

	if ($sistema_lingua=='ES')
		echo "<TD align='center' >REPUESTO</TD>\n";
	else 
		echo "<TD align='center' >PEÇA</TD>\n";
	echo "<TD align='center' >QTDE</TD>\n";
	echo "<TD align='center' >VALOR</TD>\n";

	echo "</TR>\n";

	$soma_preco = 0;
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
		if ($agrupar == "false") {
			$os					= trim(pg_result ($res,$i,os));
			$sua_os				= trim(pg_result ($res,$i,sua_os));
			$consumidor			= trim(pg_result ($res,$i,consumidor_nome));
			$produto_nome		= trim(pg_result ($res,$i,produto_nome));
			$produto_referencia	= trim(pg_result ($res,$i,produto_referencia));

		}
		$peca_referencia	= trim(pg_result ($res,$i,peca_referencia));
		$peca_nome			= trim(pg_result ($res,$i,peca_nome));
		$preco				= trim(pg_result ($res,$i,preco));
		$qtde				= trim(pg_result ($res,$i,qtde));
		if($qtde>1){$preco = $preco*$qtde;}
		$soma_preco			= $soma_preco + $preco;
		$consumidor			= strtoupper($consumidor);
		$preco				= number_format($preco,2,",",".");

		$cor = "#d9e2ef";
		$btn = 'amarelo';

		if ($i % 2 == 0){
			$cor = '#F1F4FA';
			$btn = 'azul';
		}

		if (strstr($matriz, ";" . $i . ";")) {
			$cor = '#E49494';
		}

		if (strlen ($sua_os) == 0) $sua_os = $os;

		echo "<TR class='table_line' style='background-color: $cor;'>\n";

		if ($agrupar == "false") {
			echo "<TD align='center' nowrap><a href='os_press.php?os=$os' target='_blank'><font color='#000000'>$sua_os</font></a></TD>\n";
			echo "<TD align='left' nowrap>$consumidor</TD>\n";
		}

		echo "<TD align='left' nowrap>$peca_referencia - $peca_nome</TD>\n";
		echo "<TD align='center' nowrap>$qtde</TD>\n";
		echo "<TD align='right' nowrap style='padding-right:3px'>$preco</TD>\n";

		echo "</TR>\n";
	}
	echo "<TR class='table_line' style='background-color: $cor;'>\n";

	if ($agrupar == "false") $colspan = '4';
	if ($agrupar == "true")  $colspan = '2';

	echo "<TD align='center' nowrap colspan='$colspan'><b>TOTAL</b></TD>\n";
	echo "<TD align='right' nowrap style='padding-right:3px'>". number_format($soma_preco,2,",",".") ."</TD>\n";

	echo "</TR>\n";
}

echo "</TABLE>\n";

?>

<TABLE align='center'>
<TR>
	<TD>
		<br>
		<img src="imagens/btn_voltar.gif" onclick="javascript: window.location='os_extrato.php';" ALT="Voltar" border='0' style="cursor:pointer;">
	</TD>
</TR>
</TABLE>

<p>
<p>

<? include "rodape.php"; ?>
