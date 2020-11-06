<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$btn_acao = trim (strtolower ($_POST['btn_acao']));

$msg_erro = "";

$layout_menu = "callcenter";
$title = "Relação de Pedidos Lançados e não exportados";

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

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

</style>

<?
	$dt = 0;
	
	// BTN_NOVA BUSCA
	echo "<TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
	echo "<TR class='table_line'>";
	echo "<td align='center' background='#D9E2EF'>";
	echo "<a href='pedido_parametros.php'><img src='imagens_admin/btn_nova_busca.gif'></a>";
	echo "</td>";
	echo "</TR>";
	echo "</TABLE>";

	$sql = "SELECT
				LPAD(LTRIM(tbl_posto_fabrica.codigo_posto, '0'),6,' ')                    AS codigo_posto  ,
				tbl_posto.nome                    AS posto_nome                                            ,
				tbl_peca.referencia                                                       AS referencia    ,
				LPAD(LTRIM(SUM(tbl_os_item.qtde)::text, '0'),5,' ')                       AS qtde          ,
				LPAD(LTRIM(tbl_pedido.pedido::text, '0'),10,' ')                          AS pedido        ,
				TO_CHAR(tbl_pedido.data,'DD/MM/YYYY')                                           AS data_pedido
			FROM tbl_pedido
				JOIN tbl_posto   ON tbl_pedido.posto = tbl_posto.posto
				JOIN tbl_pedido_item     ON tbl_pedido.pedido           = tbl_pedido_item.pedido
				JOIN tbl_posto_fabrica   ON tbl_pedido.fabrica = tbl_posto_fabrica.fabrica AND tbl_posto.posto = tbl_posto_fabrica.posto
				JOIN tbl_tabela ON tbl_pedido.tabela = tbl_tabela.tabela
				JOIN tbl_peca            ON tbl_pedido_item.peca        = tbl_peca.peca
				LEFT JOIN tbl_os_item    ON tbl_pedido_item.pedido      = tbl_os_item.pedido AND tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
			WHERE     tbl_pedido.fabrica                  = $login_fabrica
			AND       (tbl_pedido.status_pedido            = 1 or tbl_pedido.status_pedido is null)
			AND       tbl_pedido.exportado                IS NULL
			AND       tbl_pedido.finalizado               IS NOT NULL
			AND       tbl_pedido.troca                    IS NOT TRUE
			AND       tbl_peca.faturada_manualmente       IS TRUE
			GROUP BY tbl_posto_fabrica.codigo_posto,
			tbl_posto.nome,
			TO_CHAR(CURRENT_DATE,'DDMMYY'),
			tbl_posto.estado ,
			tbl_peca.referencia,
			tbl_pedido.pedido,
			tbl_pedido.data
			ORDER BY  tbl_pedido.pedido, tbl_peca.referencia";

	$res = pg_exec($con,$sql);

// ##### PAGINACAO ##### //

echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";

echo "<TR class='menu_top'>\n";
echo "	<TD colspan='5'>PEDIDOS COM PEÇAS PARA FATURAR MANUALMENTE</TD>\n";
echo "</TR>\n";
echo "</table>\n";

if (@pg_numrows($res) == 0) {

	echo "<center><h2>Não existem pedidos com estes parâmetros</h2></center>";

}else{

	echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";

	echo "<TR class='menu_top'>\n";
	echo "	<TD >POSTO</TD>\n";
	echo "	<TD >REFERENCIA</TD>\n";
	echo "	<TD >QTDADE</TD>\n";
	echo "	<TD >PEDIDO</TD>\n";
	echo "	<TD>DATA PEDIDO</TD>\n";
	echo "	<TD>AÇÃO</TD>\n";
	echo "</TR>\n";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
		$pedido             = trim(pg_result ($res,$i,pedido));
		$data_pedido        = trim(pg_result ($res,$i,data_pedido));
		$referencia         = trim(pg_result ($res,$i,referencia));
		$codigo_posto       = trim(pg_result ($res,$i,codigo_posto));
		$posto_nome         = trim(pg_result ($res,$i,posto_nome));
		$qtde               = trim(pg_result ($res,$i,qtde));
		if($qtde == '')
			$qtde = 1;

		$cor = "#F7F5F0"; 
		$btn = 'amarelo';
		if ($i % 2 == 0) 
		{
			$cor = '#F1F4FA';
			$btn = 'azul';
		}

	echo "<TR class='table_line' style='background-color: $cor;'>\n";
	echo "	<TD style='padding-left:5px'>$codigo_posto-$posto_nome</TD>\n";
	echo "	<TD style='padding-left:5px'>$referencia</TD>\n";
	echo "	<TD style='padding-left:5px'>$qtde</TD>\n";
	echo "	<TD align='center'>$pedido</TD>\n";
	echo "	<TD nowrap >$data_pedido</TD>\n";
	echo "	<TD nowrap width='85'><a href='pedido_nao_exportado.php?pedido=$pedido' target='_blank'><img src='imagens/btn_exportar_".$btn.".gif'></a></TD>\n";

	echo "</TR>\n";
}

echo "</TABLE>\n";

	echo "<TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
	echo "<TR class='table_line'>";
	echo "<td align='center' background='#D9E2EF'>";
	echo "<a href='pedido_parametros.php'><img src='imagens_admin/btn_nova_busca.gif'></a>";
	echo "</td>";
	echo "</TR>";
	echo "</TABLE>";


}
?>

<p>

<? include "rodape.php"; ?>
