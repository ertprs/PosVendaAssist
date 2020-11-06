<?php

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

$extrato = $_GET['extrato'];

if(!empty($extrato)){
	if( $login_fabrica == 11 ){
	    $case_log  = " case when tbl_os_log.os_atual is not null then else 0 end as log , os_atual as os_log,";
	    $join_log  = " LEFT JOIN tbl_os_log on tbl_os.os = tbl_os_log.os_atual ";
	    $group_log = " os_atual,";
	}

	if ($areaAdmin !== true) {
		$wherePosto = "AND tbl_os.posto = {$login_posto}";
	}
	if(isset($novaTelaOs)){
		$campos = ", tbl_os_produto.serie ";
		$join_produto = " LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
			LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto ";
	}else{
		$campos = ", tbl_os.serie ";
		$join_produto = " LEFT JOIN tbl_produto ON  tbl_produto.produto = tbl_os.produto ";
	}
	
	if ($login_fabrica == 138 || $login_fabrica == 145) {
		$distinct_os = "DISTINCT ON (tbl_os.os)";
	}
	if(in_array($login_fabrica, array(30,42,104,145))){
		$total_pecas = " tbl_os.pecas AS total_pecas ,";
	}else{
		$total_pecas = "(SELECT SUM (tbl_os_item.qtde * tbl_os_item.custo_peca) FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) WHERE tbl_os_produto.os = tbl_os.os) AS total_pecas  ,";
	}
	if($login_fabrica == 1){
		$admin_pagto = ", tbl_extrato_financeiro.admin_pagto";
	}
	if($login_fabrica == 1){
		$left_britania = "LEFT JOIN tbl_extrato_financeiro ON tbl_extrato_financeiro.extrato = tbl_extrato.extrato";
	}
		
	/* Programa: $PHP_SELF ### Fabrica: $login_fabrica ### Admin: $login_admin */

	$sql = "SELECT
		{$distinct_os}
		tbl_os.os                                                                       ,
		lpad (tbl_os.sua_os,10,'0')                                  AS ordem           ,
		tbl_os.sua_os                                                                   ,
		to_char (tbl_os.data_digitacao,'DD/MM/YYYY')                 AS data            ,
		to_char (tbl_os.data_abertura ,'DD/MM/YYYY')                 AS abertura        ,
		to_char (tbl_os.data_fechamento,'DD/MM/YYYY')                AS fechamento       ,
		to_char (tbl_os.finalizada    ,'DD/MM/YYYY')                 AS finalizada      ,
		tbl_os.consumidor_revenda                                                       ,
		tbl_os.codigo_fabricacao                                                        ,
		tbl_os.consumidor_nome                                                          ,
		tbl_os.consumidor_fone                                                          ,
		tbl_os.revenda_nome                                                             ,
		tbl_os.troca_garantia                                                           ,
		tbl_os.data_fechamento                                                          ,
		{$total_pecas}
		tbl_os.mao_de_obra                                           AS total_mo        ,
		tbl_os.qtde_km                                               AS qtde_km         ,
		tbl_os.qtde_km_calculada                                     AS qtde_km_calculada,
		COALESCE(tbl_os.pedagio, 0)                                  AS pedagio		    ,
		tbl_os.cortesia                                                                 ,
		tbl_os.nota_fiscal                                                              ,
		to_char(tbl_os.data_nf, 'DD/MM/YYYY')                        AS data_nf         ,
		tbl_os.nota_fiscal_saida                                                        ,
		tbl_os.posto                                                                    ,
		tbl_produto.referencia                                                          ,
		tbl_produto.descricao                                                           ,
		tbl_os_extra.extrato                                                            ,
		tbl_os_extra.os_reincidente                                                     ,
		tbl_os.observacao                                                               ,
		tbl_os.motivo_atraso                                                            ,
		tbl_os_extra.motivo_atraso2                                                     ,
		tbl_os_extra.taxa_visita                                                        ,
		tbl_os_extra.valor_total_deslocamento AS entrega_tecnica                        ,
		tbl_os.obs_reincidencia                                                         ,
		tbl_os.valores_adicionais                                                       ,
		to_char (tbl_extrato.data_geracao,'DD/MM/YYYY')              AS data_geracao    ,
		tbl_extrato.total                                            AS total           ,
		tbl_extrato.mao_de_obra                                      AS mao_de_obra     ,
		tbl_extrato.pecas                                            AS pecas           ,
		tbl_extrato.deslocamento                                     AS total_km        ,
		tbl_extrato.admin                                            AS admin_aprovou   ,
		tbl_extrato.recalculo_pendente                                                  ,
		lpad (tbl_extrato.protocolo::text,6,'0')                     AS protocolo       ,
		tbl_posto.nome                                               AS nome_posto      ,
		tbl_posto_fabrica.codigo_posto                               AS codigo_posto    ,
		tbl_extrato_pagamento.valor_total                                               ,
		tbl_extrato_pagamento.acrescimo                                                 ,
		tbl_extrato_pagamento.desconto                                                  ,
		tbl_extrato_pagamento.valor_liquido                                             ,
		tbl_extrato_pagamento.nf_autorizacao                                            ,
		tbl_extrato_pagamento.baixa_extrato                                             ,
		to_char (tbl_extrato.previsao_pagamento,'DD/MM/YYYY') AS previsao_pagamento     ,
		to_char (tbl_extrato.data_recebimento_nf,'DD/MM/YYYY') AS data_recebimento_nf   ,
		to_char (tbl_extrato_pagamento.data_vencimento,'DD/MM/YYYY') AS data_vencimento ,
		to_char (tbl_extrato_pagamento.data_pagamento,'DD/MM/YYYY')  AS data_pagamento  ,
		tbl_extrato_pagamento.autorizacao_pagto                                         ,
		tbl_posto_fabrica.valor_km as valor_km 											,
		tbl_extrato_pagamento.obs                                                       ,
		tbl_extrato_pagamento.extrato_pagamento                                         ,
		(SELECT COUNT(*) FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) JOIN tbl_servico_realizado USING (servico_realizado) WHERE tbl_os_produto.os = tbl_os.os AND tbl_os_item.custo_peca = 0 AND tbl_servico_realizado.troca_de_peca IS TRUE) AS peca_sem_preco,
		(SELECT COUNT(1) FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) WHERE tbl_os_produto.os = tbl_os.os ) AS os_sem_item,
		(SELECT peca_sem_estoque FROM tbl_os_item JOIN tbl_os_produto using(os_produto) WHERE tbl_os_produto.os = tbl_os.os and peca_sem_estoque is true limit 1) AS peca_sem_estoque ,
		{$case_log}
		tbl_os.data_fechamento - tbl_os.data_abertura  as intervalo                     ,
		(SELECT login FROM tbl_admin WHERE tbl_admin.admin = tbl_os.admin AND tbl_admin.fabrica = {$login_fabrica}) AS admin,
		tbl_familia.descricao       as familia_descr,
		tbl_familia.familia         as familia_id,
		tbl_familia.codigo_familia  as familia_cod
		{$campos} 
		{$admin_pag}
		FROM        tbl_extrato
		LEFT JOIN tbl_extrato_pagamento ON  tbl_extrato_pagamento.extrato = tbl_extrato.extrato
		LEFT JOIN tbl_os_extra          ON  tbl_os_extra.extrato           = tbl_extrato.extrato
		LEFT JOIN tbl_os                ON  tbl_os.os                      = tbl_os_extra.os
		{$join_log}
		{$join_produto}
		JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_extrato.fabrica AND tbl_fabrica.fabrica = {$login_fabrica}
		JOIN      tbl_posto             ON  tbl_posto.posto                = tbl_extrato.posto
		JOIN      tbl_posto_fabrica     ON  tbl_posto.posto                = tbl_posto_fabrica.posto
		AND tbl_posto_fabrica.fabrica      = {$login_fabrica}
		LEFT JOIN tbl_familia           ON  tbl_produto.familia            = tbl_familia.familia
		AND tbl_familia.fabrica            = {$login_fabrica} 
		{$left_britania}
		WHERE       tbl_extrato.fabrica = {$login_fabrica}
		AND         tbl_extrato.extrato = {$extrato} 
		{$wherePosto} ";
        if( $login_fabrica == 45 ){ //HD 39933
        	$sql .= "
        	AND    tbl_os.mao_de_obra notnull
        	AND    tbl_os.pecas       notnull
        	AND    ((SELECT tbl_os_status.status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os.os ORDER BY tbl_os_status.data DESC LIMIT 1) IS NULL OR (SELECT tbl_os_status.status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os.os ORDER BY tbl_os_status.data DESC LIMIT 1) NOT IN (15)) ";
        }
        if(!in_array($login_fabrica, array(2, 50, 138, 145))){
	    	$sql .= "ORDER BY    tbl_os_extra.os_reincidente, lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')),20,'0')               ASC,
	        	replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')),20,'0'),'-','') ASC";
	    } else if (in_array($login_fabrica, array(50))) {
	    	$sql .= "ORDER BY   tbl_familia.descricao ASC,
	    	tbl_os_extra.os_reincidente,lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')),20,'0') ASC,
	    	replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')),20,'0'),'-','') ASC";
	    } else if (in_array($login_fabrica, array(138,145))) {
	    	$sql .= " ORDER BY tbl_os.os ";
	    } else {
	    	$sql .= " ORDER BY replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')),20,'0'),'-','') ASC ";
	    }

		// echo nl2br($sql);exit;	    

	    $res = pg_query($con,$sql);
	    if (pg_num_rows($res) > 0) {
            $count  = pg_num_rows($res);
            $result = pg_fetch_assoc($res,0);
	    	extract($result);
	    }
}

?>

<!DOCTYPE html>
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
			font-size: 10px;
			margin: 0 auto;
		}

		table {
			width: 100%;
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
<html>
<body>
	<div class="box-print" >
		<table class="table table-bordered" style="margin: 0 auto; table-layout: fixed;" >
			<tr>
				<th class="titulo_tabela" colspan="4" ><?php echo traduz("extrato");?></th>
			</tr>

			<tr>
				<td><b><?php echo traduz("posto");?>:</b> <?=$nome_posto?></td>
				<td><b><?php echo traduz("extrato");?>:</b> <?=$extrato?></td>
				<td><b><?php echo traduz("data");?>:</b> <?=$data_geracao?></td>
				<td><b><?php echo traduz("quantidade.de.os");?>:</b> <?=$count?> </td>
			</tr>
			<tr>
				<td colspan='4'><b><?php echo traduz("total");?>:</b><?=$total?></td>
			</tr>
		</table>
		
		<table class="table table-bordered" style="margin: 0 auto; table-layout: fixed;" >
			<tr>
				<th class="titulo_tabela" colspan="8" ><?php echo traduz("ordens.de.servico");?></th>
			</tr>
			<tr>
				<td><b><?php echo traduz("os");?></b> </td>
				<td><b><?php echo traduz("serie");?></b> </td>
				<td><b><?php echo traduz("abertura");?></b> </td>
				<td><b><?php echo traduz("consumidor");?></b> </td>
				<td><b><?php echo traduz("produto");?></b> </td>
				<td><b><?php echo traduz("mao.de.obra");?></b></td>
				<td><b><?php echo traduz("valor.adicional");?></b></td>
				<td><b><?php echo traduz("total.km");?></b></td> 
			</tr>
			<?php
				for ($i=0; $i < $count; $i++) {
		        	$result = pg_fetch_assoc($res);
		        	extract($result);
		     	?>

			<tr>
				<td><?=$os?> </td>
				<td><?=$serie?></td>
				<td><?=$abertura?></td>
				<td><?=$consumidor_nome?></td>
				<td><?=$familia_descr?></td>
				<td><?=number_format($total_mo,2,',','.')?></td>
				<td><?=number_format($valores_adicionais,2,',','.')?></td>
				<td><?=number_format($qtde_km_calculada,2,',','.')?></td>
			</tr>
			<?php
			} 
			?>
		</table>
		
		<p id='footer' style="page-break-inside: auto; page-break-after: always;" ></p>
	</div>
</body>	
</html>
