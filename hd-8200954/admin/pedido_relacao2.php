<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$msg_erro = "";

$layout_menu = "callcenter";
$title		 = "Rela��o de Pedidos de Pe�as";
$body_onload = "javascript: document.frm_pedido.condicao.focus()";

include "cabecalho.php";
?>

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
	background-color: #ffffff
}
</style>

<p>

<!-- <TABLE align='center' border='0'>
<TR>
	<TD>Para visualizar os detalhes do pedido, clique no n�mero do pedido em negrito.</TD>
</TR>
</TABLE> -->

<?

// inicio sqlCount
$sqlCount = "SELECT	COUNT(*) 
			FROM	tbl_pedido 
			WHERE	tbl_pedido.fabrica = $login_fabrica ";



$sql = "SELECT      distinct
					tbl_pedido.pedido                                                ,
					to_char(tbl_pedido.data,'DD/MM/YYYY') AS data                    ,
					tbl_pedido.tipo_frete                                            ,
					tbl_pedido.pedido_cliente                                        ,
					tbl_pedido.validade                                              ,
					tbl_pedido.entrega                                               ,
					tbl_pedido.obs                                                   ,
					tbl_posto.nome                                                   ,
					tbl_posto.cnpj                                                   ,
					tbl_posto.cidade                                                 ,
					tbl_posto.estado                                                 ,
					tbl_posto.endereco                                               ,
					tbl_posto.numero                                                 ,
					tbl_posto.complemento                                            ,
					tbl_posto.fone                                                   ,
					tbl_posto.fax                                                    ,
					tbl_posto.contato                                                ,
					tbl_pedido.tabela                                                ,
					tbl_tabela.sigla_tabela                                          ,
					tbl_admin.login                                                  ,
					tbl_posto_fabrica.desconto                                       ,
					tbl_tipo_posto.codigo AS codigo_tipo_posto                       ,
					tbl_posto_fabrica.transportadora_nome                            ,
					tbl_peca.ipi                                                     ,
					sum(tbl_pedido_item.qtde * tbl_pedido_item.preco) AS preco       ,
					(
					SELECT	tbl_status.descricao 
					FROM	tbl_status 
					WHERE	tbl_status.status = (
						SELECT	 tbl_pedido_status.status 
						FROM	 tbl_pedido_status 
						WHERE	 tbl_pedido_status.pedido = tbl_pedido.pedido 
						ORDER BY tbl_pedido_status.data 
						DESC LIMIT 1
						)
					) AS descricao_status
		FROM        tbl_pedido
		JOIN        tbl_pedido_item   USING (pedido)
		JOIN        tbl_peca          USING (peca)
		JOIN        tbl_posto         USING (posto)
		JOIN        tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		LEFT JOIN   tbl_tabela        USING (tabela)
		LEFT JOIN   tbl_tipo_posto    ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
		LEFT JOIN   tbl_admin         USING (admin)
		WHERE       tbl_pedido.fabrica = $login_fabrica " ;

		$msg_consulta = "";

		$cnpj = $_GET['cnpj'];
		if (strlen ($cnpj) > 0) {
			$sql .= " AND tbl_posto.cnpj = '$cnpj' ";
			$sqlCount = " AND tbl_posto.cnpj = '$cnpj' ";
			$msg_consulta .= "<br>Pedidos do CNPJ $cnpj";
		}

		$data_incial = $_GET['data_inicial'];
		if (strlen ($data_inicial) > 0) {
			$sql .= " AND tbl_pedido.data >= '$data_inicial' ";
			$sqlCount = " AND tbl_pedido.data >= '$data_inicial' ";
			$msg_consulta .= "<br>Depois de $data_inicial";
		}

		$data_final = $_GET['data_final'];
		if (strlen ($data_inicial) > 0) {
			$sql .= " AND tbl_pedido.data <= '$data_final' ";
			$sqlCount = " AND tbl_pedido.data <= '$data_final' ";
			$msg_consulta .= "<br>Antes de $data_final";
		}

		$pedido_cliente = $_GET['pedido_cliente'];
		if (strlen ($pedido_cliente) > 0) {
			$sql .= " AND tbl_pedido.pedido_cliente = '$pedido_cliente' ";
			$sqlCount = " AND tbl_pedido.pedido_cliente = '$pedido_cliente' ";
			$msg_consulta .= "<br>Pedido do cliente $pedido_cliente";
		}

		$produto = $_GET['produto'];
		if (strlen ($produto) > 0) {
			$sql .= " AND tbl_peca.referencia = '$produto' ";
			$sqlCount = " AND tbl_peca.referencia = '$produto' ";
			$msg_consulta .= "<br>Pedidos do produto $produto";
		}

$sql .= " GROUP BY  tbl_pedido.pedido                         ,
					tbl_pedido.data                           ,
					tbl_pedido.tipo_frete                     ,
					tbl_pedido.pedido_cliente                 ,
					tbl_pedido.validade                       ,
					tbl_pedido.entrega                        ,
					tbl_pedido.obs                            ,
					tbl_posto.nome                            ,
					tbl_posto.cnpj                            ,
					tbl_posto.cidade                          ,
					tbl_posto.estado                          ,
					tbl_posto.endereco                        ,
					tbl_posto.numero                          ,
					tbl_posto.complemento                     ,
					tbl_posto.fone                            ,
					tbl_posto.fax                             ,
					tbl_posto.contato                         ,
					tbl_pedido.tabela                         ,
					tbl_tabela.sigla_tabela                   ,
					tbl_admin.login                           ,
					tbl_posto_fabrica.desconto                ,
					tbl_tipo_posto.codigo                     ,
					tbl_posto_fabrica.transportadora_nome     ,
					tbl_peca.ipi
			ORDER BY tbl_pedido.pedido DESC";

//echo "<br>".$sql."<br>";

	// ##### PAGINACAO ##### //
	require "_class_paginacao.php";

	// definicoes de variaveis
	$max_links = 10;				// m�ximo de links � serem exibidos
	$max_res   = 30;				// m�ximo de resultados � serem exibidos por tela ou pagina
	$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
	$mult_pag->num_pesq_pag = $max_res; // define o n�mero de pesquisas (detalhada ou n�o) por p�gina

	// **************
	// $sqlCount - passa tambem o sql com COUNT()
	// **************

	$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

	// ##### PAGINACAO ##### //

echo "<center><h2>$msg_consulta</h2></center>";

if (pg_numrows($res) > 0) {
	$cor = "#C2DCDC";
?>
<TABLE width='700' cellpadding='2' cellspacing='1'>
<TR class="table_line">
	<td colspan="8" align="center"><img src='imagens/cab_cliquenopedidodetalhe.gif' border=0></td>
</TR>
<TR class="table_line">
	<td colspan="9" align="center"><img src='imagens/cab_cliquenobotalstatus.gif' border=0></td>
</TR>
<TR class='menu_top'>
	<TD width='080'>Pedido</TD>
	<TD width='070'>Data</TD>
	<TD width='015'>&nbsp;</TD>
	<TD width="150">Status</TD>
	<TD width="090">Pedido Cliente</TD>
	<TD width="200">CNPJ</TD>
	<TD width='080'>Total</TD>
	<TD width='085'>&nbsp;</TD>
</TR>

<?
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$pedido           = trim(pg_result ($res,$i,pedido));
		$data             = trim(pg_result ($res,$i,data));
		$tabela           = trim(pg_result ($res,$i,tabela));
		$pedido_cliente   = trim(pg_result ($res,$i,pedido_cliente));
		$cnpj             = trim(pg_result ($res,$i,cnpj));
		$cnpj             = $cnpj = substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4) ."-". substr($cnpj,12,2);
		$total            = trim(pg_result ($res,$i,preco));
		$ipi              = trim(pg_result ($res,$i,ipi));
		$desconto         = trim(pg_result ($res,$i,desconto));
		$descricao_status = trim(pg_result ($res,$i,descricao_status));
		$total            = $total + ($total * $ipi / 100);
		$total            = $total - ($total * $desconto / 100);
		
		$soma_total = $soma_total + $total;
		
		$cor = "#fdfdfd";
		if ($i % 2 == 0) $cor = '#F1F4FA';

		echo "<TR class='table_line'>";
		echo "	<TD bgcolor='$cor' align='center'><a href='pedido_finalizado.php?pedido=$pedido' target='_new'>$pedido</a></TD>";
		echo "	<TD bgcolor='$cor' align='center'>$data</TD>";
		echo "	<TD bgcolor='$cor'><a href='pedido_status.php?pedido=$pedido' target='_blank'><img src='imagens/btn_maisstatus.gif' border=0 width=15 height=15></a></TD>";
		echo "	<TD bgcolor='$cor'><acronym title=\"$descricao_status\">$descricao_status</acronym></TD>";
		echo "	<TD bgcolor='$cor'>$pedido_cliente</TD>";
		echo "	<TD bgcolor='$cor' align='center'>$cnpj</TD>";
		echo "	<TD bgcolor='$cor' align='right' style='padding-right:3px;'>".number_format($total,2,",",".")."</TD>";
		echo "	<TD bgcolor='$cor'><a href='pedido_cadastro.php?pedido=$pedido'><img src='imagens_admin/btn_alterar.gif' border=0 width=85></a></TD>";
		echo "</TR>";

	}

	echo "<TR class='table_line'>";
	echo "	<TD align='right' colspan='6'><b>TOTAL</b></TD>";
	echo "	<TD align='right'><b>".number_format($soma_total,2,",",".")."</b></TD>";
	echo "	<TD>&nbsp;</TD>";
	echo "</TR>";

}
?>

</TABLE>

<?

// ##### PAGINACAO ##### //

// links da paginacao
echo "<br>";

echo "<div>";

if($pagina < $max_links) { 
	$paginacao = pagina + 1;
}else{
	$paginacao = pagina;
}

// paginacao com restricao de links da paginacao

// pega todos os links e define que 'Pr�xima' e 'Anterior' ser�o exibidos como texto plano
$todos_links		= $mult_pag->Construir_Links("todos", "sim");

// fun��o que limita a quantidade de links no rodape
$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

for ($n = 0; $n < count($links_limitados); $n++) {
	echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
}

echo "</div>";

// ##### PAGINACAO ##### //

?>

<p>

<? include "rodape.php"; ?>