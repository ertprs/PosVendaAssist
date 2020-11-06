<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$btn_acao = trim (strtolower ($_POST['btn_acao']));

$msg_erro = "";

if ($btn_acao == "gravar") {
}





$layout_menu = "callcenter";
$title = "Relação de Pedidos de Peças";
$body_onload = "javascript: document.frm_pedido.condicao.focus()";


include "cabecalho.php";

?>


<p>

<?
echo "<div id='container'>";
echo "Para visualizar os detalhes do pedido, clique no número do pedido em negrito.";
echo "</div>";

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
					sum(tbl_pedido_item.qtde * tbl_pedido_item.preco) AS preco
		FROM        tbl_pedido
		JOIN		tbl_pedido_item   USING (pedido)
		JOIN		tbl_peca		  USING (peca)
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
			$msg_consulta .= "<br>Pedidos do CNPJ $cnpj";
		}

		$data_incial = $_GET['data_inicial'];
		if (strlen ($data_inicial) > 0) {
			$sql .= " AND tbl_pedido.data >= '$data_inicial' ";
			$msg_consulta .= "<br>Depois de $data_inicial";
		}

		$data_final = $_GET['data_final'];
		if (strlen ($data_inicial) > 0) {
			$sql .= " AND tbl_pedido.data <= '$data_final' ";
			$msg_consulta .= "<br>Antes de $data_inicial";
		}

		$pedido_cliente = $_GET['pedido_cliente'];
		if (strlen ($pedido_cliente) > 0) {
			$sql .= " AND tbl_pedido.pedido_cliente = '$pedido_cliente' ";
			$msg_consulta .= "<br>Pedido do cliente $pedido_cliente";
		}

		$produto = $_GET['produto'];
		if (strlen ($produto) > 0) {
			$sql .= " AND tbl_peca.referencia = '$produto' ";
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
			ORDER BY tbl_pedido.pedido DESC;";
#echo $sql; exit;
$res = pg_exec ($con,$sql);

echo "<center><h2>$msg_consulta</h2></center>";



if (pg_numrows($res) > 0) {
	$cor = "#C2DCDC";
	echo "<div id='container'>";
	
	echo "<div id='contentcenter'>";
	
	echo "<div id='contentleft' style='margin-right:5px;width:100px;'>";
	echo "&nbsp;";
	echo "</div>";
	
	echo "<div id='contentleft' style='margin-right:5px;background-color: $cor;width:100px;'>";
	echo "Pedido";
	echo "</div>";
	
	echo "<div id='contentleft' style='margin-right:5px;background-color: $cor;width:100px;'>";
	echo "Data";
	echo "</div>";
	
	echo "<div id='contentleft' style='margin-right:5px;background-color: $cor;width:100px;'>";
	echo "Pedido Cliente";
	echo "</div>";
	
	echo "<div id='contentleft' style='margin-right:5px;background-color: $cor;width:120px;'>";
	echo "CNPJ";
	echo "</div>";
	
	echo "<div id='contentleft' style='margin-right:5px;background-color: $cor;width:70px;'>";
	echo "Total";
	echo "</div>";
	
	echo "</div>";
	
	echo "</div>";
	
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$pedido         = trim(pg_result ($res,$i,pedido));
		$data           = trim(pg_result ($res,$i,data));
		$tabela         = trim(pg_result ($res,$i,tabela));
		$pedido_cliente = trim(pg_result ($res,$i,pedido_cliente));
		$cnpj           = trim(pg_result ($res,$i,cnpj));
		$cnpj           = $cnpj = substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4) ."-". substr($cnpj,12,2);
		$total          = trim(pg_result ($res,$i,preco));
		$ipi            = trim(pg_result ($res,$i,ipi));
		$desconto       = trim(pg_result ($res,$i,desconto));
		$total          = $total + ($total * $ipi / 100);
		$total          = $total - ($total * $desconto / 100);
		
		$soma_total = $soma_total + $total;
		
		$cor = "#F7F5F0";
		if ($i % 2 == 0) $cor = '#F1F4FA';
		
		echo "<div id='container'>";
		echo "<div id='contentcenter'>";
		
		echo "<div id='contentleft' style='font-weight: normal;margin-right:5px;background-color: $cor;width:100px;'>";
		echo "<a href='pedido_cadastro.php?pedido=$pedido' target='_new'>ALTERAR</a>";
		echo "</div>";
		
		echo "<div id='contentleft' style='font-weight: normal;margin-right:5px;background-color: $cor;width:100px;'>";
		echo "<a href='pedido_finalizado.php?pedido=$pedido' target='_new'>$pedido</a>";
		echo "</div>";
		
		echo "<div id='contentleft' style='font-weight: normal;margin-right:5px;background-color: $cor;width:100px;'>";
		echo "$data";
		echo "</div>";
		
		echo "<div id='contentleft' style='font-weight: normal;margin-right:5px;background-color: $cor;width:100px;'>";
		echo "$pedido_cliente";
		echo "</div>";
		
		echo "<div id='contentleft' style='font-weight: normal;margin-right:5px;background-color: $cor;width:120px;'>";
		echo "$cnpj";
		echo "</div>";
		
		echo "<div id='contentleft' style='font-weight: normal;margin-right:5px;background-color: $cor;width:70px;text-align:right'>";
		echo number_format($total,2,",",".");
		echo "</div>";
		
		echo "</div>";
		echo "</div>";
	}
	echo "<div id='container'>";
	echo "<div id='contentcenter'>";
	
	echo "<div id='contentleft' style='font-weight: normal;margin-right:5px;background-color: $cor;width:560px;'>";
	echo "<b>TOTAL GERAL</b>";
	echo "</div>";
	
	echo "<div id='contentleft' style='font-weight: normal;margin-right:5px;background-color: $cor;width:70px;text-align:right'>";
	echo "<b>". number_format($soma_total,2,",",".") ."</b>";
	echo "</div>";
	
	echo "</div>";
	echo "</div>";
}
?>

<p>


<? include "rodape.php"; ?>