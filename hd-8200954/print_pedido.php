<?
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

if ($areaAdmin === true) {
	include __DIR__.'/dbconfig.php';
	include __DIR__.'/includes/dbconnect-inc.php';
	include __DIR__.'/admin/autentica_admin.php';
} else {
	include __DIR__.'/dbconfig.php';
	include __DIR__.'/includes/dbconnect-inc.php';
	include __DIR__.'/autentica_usuario.php';
}

if (strlen($pedido)==0){
	$pedido = $_GET['pedido'];
}

if (strlen ($pedido) > 0) {

	$sql = "SELECT      tbl_pedido.pedido                                                ,
						tbl_pedido.pedido_blackedecker                                   ,
						tbl_pedido.seu_pedido                                            ,
						to_char(tbl_pedido.data,'DD/MM/YYYY') AS data                    ,
						tbl_pedido.tipo_frete                                            ,
						tbl_pedido.pedido_cliente                                        ,
						tbl_pedido.validade                                              ,
						tbl_pedido.entrega                                               ,
						tbl_pedido.obs                                                   ,
						tbl_posto.nome                                                   ,
						tbl_posto.cnpj                                                   ,
						tbl_posto.ie                                                     ,
						tbl_posto_fabrica.contato_cidade      AS cidade                  ,
						tbl_posto_fabrica.contato_estado      AS estado                  ,
						tbl_posto_fabrica.contato_endereco    AS endereco                ,
						tbl_posto_fabrica.contato_numero      AS numero                  ,
						tbl_posto_fabrica.contato_complemento AS complemento             ,
						tbl_posto.fone                                                   ,
						tbl_posto.fax                                                    ,
						tbl_posto.contato                                                ,
						tbl_pedido.tabela                                                ,
						tbl_tabela.sigla_tabela                                          ,
						tbl_condicao.descricao AS condicao                               ,
						tbl_admin.login                                                  ,
						tbl_posto_fabrica.desconto                                       ,
						tbl_posto_fabrica.codigo_posto                                   ,
						tbl_tipo_posto.codigo AS codigo_tipo_posto                       ,
						tbl_posto_fabrica.transportadora_nome                            ,
						tbl_linha.nome       AS linha_nome
			FROM        tbl_pedido
			JOIN        tbl_posto         USING (posto)
			JOIN 		tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto 
										AND  tbl_posto_fabrica.fabrica = {$login_fabrica}
			LEFT JOIN   tbl_condicao      USING (condicao)
			LEFT JOIN   tbl_tabela        ON tbl_pedido.tabela            = tbl_tabela.tabela
			LEFT JOIN   tbl_tipo_posto    ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
			LEFT JOIN   tbl_admin         ON tbl_admin.admin              = tbl_pedido.admin
			LEFT JOIN   tbl_linha         ON tbl_pedido.linha             = tbl_linha.linha
			WHERE       tbl_pedido.pedido  = {$pedido}
			AND         tbl_pedido.fabrica = {$login_fabrica}";


	$res = pg_query($con,$sql);
  	if (pg_num_rows($res) > 0) {
        $count  = pg_num_rows($res);
        $result = pg_fetch_assoc($res,0);
    	extract($result);
    }

	if (strlen ($tabela) == 0) $tabela = "null";
		$sql = "SELECT  to_char (tbl_pedido_item.qtde,'000') AS qtde , ";
	if ($login_fabrica <> 14) {
		$sql .= "tbl_pedido_item.preco   , ";
	}else{
		$sql .= "tbl_pedido_item.preco,7,0 ,
				rpad (tbl_pedido_item.qtde * tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100)),7,0)::float as total, ";
	}
		$sql .= "tbl_peca.descricao      ,
				tbl_peca.referencia     ,
				tbl_peca.unidade        ,
				tbl_peca.ipi            ,
				tbl_peca.origem         ,
				tbl_peca.peso           ,
				tbl_peca.classificacao_fiscal,
				tbl_pedido_item.obs     ,
				tbl_pedido_item.peca    ,
				tbl_pedido_item.pedido
			FROM      tbl_pedido_item
			JOIN      tbl_peca        ON tbl_pedido_item.peca = tbl_peca.peca
			LEFT JOIN tbl_tabela_item ON (tbl_tabela_item.tabela = $tabela AND tbl_tabela_item.peca = tbl_pedido_item.peca)
			WHERE     tbl_pedido_item.pedido = $pedido
			ORDER BY  tbl_pedido_item.pedido_item";

	flush();
	$resI = pg_query ($con,$sql);
	if (pg_num_rows($resI) > 0) {
        $countI  = pg_num_rows($resI);
    }
	$total = 0;
	
	for ($i = 0 ; $i < $countI ; $i++) {
		$ipi   = trim(pg_fetch_result ($resI,$i,"ipi"));
		$preco = pg_fetch_result ($resI,$i,"qtde") * pg_fetch_result ($resI,$i,"preco") ;
		$preco = $preco + ($preco * $ipi / 100);
		$total += $preco;
	}

	$sql = "SELECT      tbl_os.os             ,
					tbl_os.sua_os         ,
					tbl_produto.referencia,
					tbl_produto.descricao
			FROM    tbl_pedido
			JOIN    tbl_pedido_item     USING (pedido)
			JOIN    tbl_peca            USING (peca)
			JOIN    tbl_os_item         USING (pedido)
			JOIN    tbl_os_produto      USING (os_produto)
			JOIN    tbl_os              USING (os)
			JOIN    tbl_produto         ON tbl_produto.produto = tbl_os_produto.produto
			WHERE tbl_pedido_item.pedido = $pedido
			GROUP BY    tbl_os.os             ,
						tbl_os.sua_os         ,
						tbl_produto.referencia,
						tbl_produto.descricao
			ORDER BY    tbl_os.sua_os;";
	$resO = pg_query($con,$sql);

	if (pg_num_rows($resO) > 0) {
   		$countO  = pg_num_rows($resO);
	}

}

?>

<head>
	<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="all" />
	<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="all" />

<style type="text/css">

.titulo_tabela {
font-weight: bold;
background-color: #CACACA;
}

.box-print {
max-width: 800px;
/*font-size: 10px;*/
margin: 0 auto;
}

table {
width: 100%;
font-size: 12px;
}


</style>

<script>

	window.addEventListener("load", function() {
		var segunda_via = document.getElementsByClassName("box-print")[0].cloneNode(true);
		document.body.appendChild(segunda_via);

		window.print();
	});
/*
The pageBreakInside property is supported in all major browsers.
Note: Firefox, Chrome, and Safari do not support the property value "avoid". 
*/
document.getElementById("footer").style.pageBreakInside = "auto";

</script>
</head>
<body>
	<div class="box-print" >
		<table class="table table-bordered" style="margin: 0 auto; table-layout: fixed;" >
			<tr>
				<th class="titulo_tabela" colspan="4" >Informações do Pedido</th>
			</tr>

			<tr>
				<td><b>Pedido :</b> <?=$pedido?></td>
				<td><b>Pedido Cliente :</b> <?=$seu_pedido?></td>
				<td><b>Posto :</b> <?=$nome?></td>
				<td><b>Data :</b> <?=$data?></td>
			</tr>
			<tr>
				<td><b>Estado :</b> <?=$estado?> </td>
				<td><b>Cidade :</b> <?=$cidade?> </td>
				<td><b>Endereço :</b> <?=$endereco?> </td>
				<td><b>Fax :</b><?=$fax?></td>
			</tr>
			<tr>
				<td><b>CNPJ :</b> <?=$cnpj?> </td>
				<td><b>Fone :</b><?=$fone?></td>
				<td><b>Linha :</b> <?=$linha_nome?> </td>
				<td><b>Transportadora :</b> <?=$transportadora_nome?> </td>
			</tr>
			<tr>
				<td><b>Total :</b> <?=$total?> </td>
				<td><b>Validade :</b> <?=$validade?> </td>
				<td><b>Entrega :</b> <?=$entrega?> </td>
				<td><b>Classe :</b><?=$codigo_tipo_posto?></td>
			</tr>

			<tr>
				<td colspan="4"><b>Mensagem :</b> <?=$obs?> </td>
			</tr>
		</table>
		
		<table class="table table-bordered" style="margin: 0 auto; table-layout: fixed;" >
			<tr>
				<th class="titulo_tabela" colspan="8" >Itens do Pedido</th>
			</tr>
			<tr>
				<td><b>Item</b></td>
				<td><b>Código</b></td>
				<td><b>Quantidade</b></td>
				<td><b>Descrição</b></td>
				<td><b>Origem</b></td>
				<td><b>IPI</b></td>
				<td><b>Un s/ IPI+desc</b></td>
				<td><b>Total c/ IPI</b></td>
			</tr>
			<?php
				for ($ii=0; $ii < $countI; $ii++) {
		        	$resultI = pg_fetch_assoc($resI);
		        	extract($resultI);
		     	?>

			<tr>
				<td><?=$ii+1?> </td>
				<td><?=$referencia?></td>
				<td><?=$unidade?>:<?=$qtde?></td>
				<td><?=strtolower((substr($descricao,0,20)))?></td>
				<td><?=$origem?></td>
				<td><?=$ipi?>%</td>
				<td><?=number_format($preco - ($preco * $desconto / 100),2,",",".")?></td>
				<td><?=number_format(($preco + ($preco * $ipi / 100))*$qtde,2,",",".")?></td>
			</tr>
			<?php
			} 
			?>
		</table>

		<?
		if($countO > 0 ){
			?>
			<table class="table table-bordered" style="margin: 0 auto; table-layout: fixed;" >
				<tr>
					<th class="titulo_tabela" colspan="2" >Itens do Pedido</th>
				</tr>
				<tr>
					<td><b>OS</b></td>
					<td><b>Equipamento</b></td>
				</tr>
				<?php
					for ($io=0; $io < $countO; $io++) {
			        	$resultO = pg_fetch_assoc($resO);
			        	extract($resultO);
		     	?>

				<tr>
					<td><?=$os?></td>
					<td><?=$referencia ."-". $descricao?></td>
				</tr>
				<?php
				} 
				?>
			</table>
		<?
		}
		?>
		
		<p id='footer' style="page-break-inside: auto; page-break-after: always;" ></p>
	</div>
</body>	
</html>
