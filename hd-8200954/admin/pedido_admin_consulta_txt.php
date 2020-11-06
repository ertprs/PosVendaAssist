<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'cabecalho.php';

if ($login_fabrica == 24) {
	echo "<div><center><p style='width:400px; text-align: center;'>Esta rotina foi desativada pois pode entrar em conflito com a rotina de exportacao automatica de pedidos implantada para a Suggar</p></center></div>";
	include "rodape.php";
	exit;
}

$sedex       = $_GET['sedex'];
$key         = $_GET['key'];
$garantia    = $_GET['garantia'];
$pedido      = $HTTP_GET_VARS['pedido'];
$pedido_link = $pedido;
$exportar    = $_GET['exportar'];

$tipo_pedido_garantia = "104";
$tipo_pedido_faturado = "103";
if(strlen($pedido)>0 and $exportar=="true"){
	$data = date ("d-m-Y");
	echo `rm /www/assist/www/admin/xls/$pedido-$data-$login_admin.sdf`;
	$fp = fopen ("/www/assist/www/admin/xls/$pedido-$data-$login_admin.sdf","w");
//	$fp = fopen ("/tmp/assist/30-07-200710-18-09-637973-397.txt","w");
	$sql = "SELECT      trim(tbl_pedido.pedido::text)  AS pedido          ,
					'1'::char(1)                       AS p1              ,
					'0'::char(1)                       AS p2              ,
					to_char(current_date,'DD/MM/YYYY') AS dt1             ,
					to_char(current_date,'DD/MM/YYYY') AS dt2             ,
					''::char(1)                        AS obs             ,
					tbl_posto.cnpj                     AS cnpj            ,
					tbl_posto_fabrica.codigo_posto     AS posto           ,
					tbl_posto_fabrica.codigo_posto     AS posto_original  ,
					tbl_posto.nome                     AS nome            ,
					upper (tbl_posto.cidade)           AS cidade          ,
					upper (tbl_posto.estado)           AS estado          ,
					'870'::char(3)                     AS cod_repr        ,
					trim(tbl_tabela.sigla_tabela)      AS tabela          ,
					trim(tbl_condicao.codigo_condicao) AS cond_pagto      ,
					''::char(1)                        AS nome_repr       ,
					to_char(current_date,'DD/MM/YYYY') AS dt3             ,
					''::char(1)                        AS ped_suggar      ,
					''::char(1)                        AS descr_cond_pagto,
					'001'::char(3)                     AS empresa         ,
					''::char(1)                     AS tipo_trans      ,
					'227'::char(3)                     AS cod_trans       ,
					''::char(1)                        AS num_ped_suggar  ,
					tbl_tipo_pedido.descricao          AS tipo_pedido     ,
					tbl_pedido.garantia_antecipada                        ,
					tbl_posto.suframa                  AS suframa,
					tbl_pedido.desconto  as desconto
		FROM        tbl_pedido
		JOIN        tbl_tipo_pedido   ON tbl_pedido.tipo_pedido = tbl_tipo_pedido.tipo_pedido
		JOIN        tbl_posto         ON tbl_pedido.posto       = tbl_posto.posto
		JOIN        tbl_posto_fabrica ON tbl_posto.posto        = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		JOIN        tbl_tabela        ON tbl_pedido.tabela      = tbl_tabela.tabela
		JOIN        tbl_condicao      ON tbl_pedido.condicao    = tbl_condicao.condicao
		WHERE       tbl_pedido.finalizado IS NOT NULL
		AND         tbl_pedido.fabrica     = $login_fabrica
		AND       ((tbl_pedido.tipo_pedido = 104 
		AND (tbl_pedido.pedido_via_distribuidor IS NOT TRUE OR tbl_pedido.posto = tbl_pedido.distribuidor)) OR (tbl_pedido.tipo_pedido = 103 ))
		AND         tbl_pedido.recebido_fabrica IS NULL
		AND         tbl_pedido.pedido = $pedido
		ORDER BY    tbl_pedido.pedido; ";
	$res = pg_exec($con,$sql);
//	echo "<BR>";
//echo nl2br($sql);
		$footer_pedido_qtde_item   = 0;
		$footer_pedido_qtde_peca   = 0;
		$footer_pedido_pedido      = "0";
		$footer_pedido_posto       = "0";

		$footer_qtde_pedido = pg_numrows($res);
		$footer_qtde_item   = 0;
		$footer_qtde_peca   = 0;
	
	if (pg_numrows($res) > 0) {
	
		for ($i=0;pg_numrows($res)>$i;$i++){

			$pedido             = trim(pg_result ($res,$i,pedido));
			$p1                 = trim(pg_result ($res,$i,p1));
			$p2                 = trim(pg_result ($res,$i,p2));
			$dt1                = trim(pg_result ($res,$i,dt1));
			$dt2                = trim(pg_result ($res,$i,dt2));
			$obs                = trim(pg_result ($res,$i,obs));
			$cnpj               = trim(pg_result ($res,$i,cnpj));
			$posto              = trim(pg_result ($res,$i,posto));
			$posto_original     = trim(pg_result ($res,$i,posto_original));
			$nome               = trim(pg_result ($res,$i,nome));
			$cidade             = trim(pg_result ($res,$i,cidade));
			$uf                 = trim(pg_result ($res,$i,estado));
			$cod_repr           = trim(pg_result ($res,$i,cod_repr));
			$tabela             = trim(pg_result ($res,$i,tabela));
			$cond_pagto         = trim(pg_result ($res,$i,cond_pagto));
			$nome_repr          = trim(pg_result ($res,$i,nome_repr));
			$dt3                = trim(pg_result ($res,$i,dt3));
			$ped_suggar         = trim(pg_result ($res,$i,ped_suggar));
			$descr_cond_pagto   = trim(pg_result ($res,$i,descr_cond_pagto));
			$empresa            = trim(pg_result ($res,$i,empresa));
			$tipo_trans         = trim(pg_result ($res,$i,tipo_trans));
			$cod_trans          = trim(pg_result ($res,$i,cod_trans));
			$num_ped_suggar     = trim(pg_result ($res,$i,num_ped_suggar));
			$tipo_pedido        = trim(pg_result ($res,$i,tipo_pedido));
			$suframa            = trim(pg_result ($res,$i,suframa));
			$garantia_antecipada= trim(pg_result ($res,$i,garantia_antecipada));
			$desconto           = trim(pg_result ($res,$i,desconto));
			$cod_trans  = " " ;
			$tipo_trans = " ";
			
			if (strlen($uf) == 0) {
				$uf = " ";
			}

			#---------- Rodape do Pedido ------------
			if ($footer_pedido_pedido == 0) {
				$footer_pedido_pedido = $pedido;
				$footer_pedido_posto  = $cnpj;
			}

			if ($pedido <> $footer_pedido_pedido) {
				echo "<BR>imprimindo footer";
				fputs ($fp, "#;");
				fputs ($fp, "$footer_pedido_pedido");
				fputs ($fp, ";");
				fputs ($fp, "$footer_pedido_qtde_item");
				fputs ($fp, ";");
				fputs ($fp, "$footer_pedido_qtde_peca");
				fputs ($fp, ";");
				fputs ($fp, "$footer_pedido_posto");
				fputs ($fp, "\n");

				$footer_pedido_pedido = $pedido ;
				$footer_pedido_posto  = $cnpj ;
				$footer_pedido_qtde_item = 0 ;
				$footer_pedido_qtde_peca = 0 ;
			}

	/*fputs ($fp, "<tr bgcolor='#0000FF' align='center'>\n");
	$pedido = trim(pg_result ($res,$i,pedido));*/

		#	$preco              = "0";

			if ($tipo_pedido == "Venda") {
				$sql = "SELECT	trim (tbl_peca.referencia)      AS peca ,
								tbl_pedido_item.qtde            AS qtde ,
								' '::char(1)                    AS sua_os ,
								' '::char(1)                    AS abertura ,
								' '::char(1)                    AS serie    ,
								' '::char(1)                    AS produto  ,
								' '::char(1)                    AS consumidor_revenda,
								tbl_pedido_item.preco           AS preco,
								CASE WHEN tbl_peca.devolucao_obrigatoria IS TRUE THEN 'YES'
								ELSE 'NO'
								END AS devolucao_obrigatoria
						FROM    tbl_pedido_item 
						JOIN    tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca
						WHERE   tbl_pedido_item.pedido = $pedido
						AND     tbl_pedido_item.troca_produto IS NOT TRUE
						AND     tbl_peca.produto_acabado IS NOT TRUE
						AND     tbl_pedido_item.qtde > tbl_pedido_item.qtde_cancelada
						ORDER BY tbl_peca.referencia";
				$resItem = pg_exec($con,$sql);
			}else{
				if ($tipo_pedido == "Garantia" ) {
					$sql = "SELECT	trim (tbl_peca.referencia)      AS peca ,
									tbl_pedido_item.qtde            AS qtde ,
									'GARANTIA'::char(8)             AS sua_os ,
									' '::char(1)                    AS abertura ,
									' '::char(1)                    AS serie    ,
									' '::char(1)                    AS produto  ,
									' '::char(1)                    AS consumidor_revenda,
									tbl_pedido_item.preco           AS preco,
								CASE WHEN tbl_peca.devolucao_obrigatoria IS TRUE THEN 'YES'
								ELSE 'NO'
								END AS devolucao_obrigatoria
							FROM    tbl_pedido_item 
							JOIN    tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca
							WHERE   tbl_pedido_item.pedido = $pedido
							AND     tbl_pedido_item.troca_produto IS NOT TRUE
							AND     tbl_peca.produto_acabado IS NOT TRUE
							AND     tbl_pedido_item.qtde > tbl_pedido_item.qtde_cancelada
							ORDER BY tbl_peca.referencia";
					$resItem = pg_exec($con,$sql);
				}else{
					$sql = "SELECT	trim (tbl_peca.referencia)         AS peca              ,
									tbl_os_item.qtde                   AS qtde              ,
									tbl_os.sua_os                      AS sua_os            ,
									to_char (tbl_os.data_abertura,'DD/MM/YYYY') AS abertura ,
									tbl_os.serie                       AS serie             ,
									tbl_produto.referencia             AS produto           ,
									tbl_os.consumidor_revenda          AS consumidor_revenda,
									tbl_pedido_item.preco           AS preco,
								CASE WHEN tbl_peca.devolucao_obrigatoria IS TRUE THEN 'YES'
								ELSE 'NO'
								END AS devolucao_obrigatoria
							FROM    tbl_os_item
							JOIN    tbl_servico_realizado USING (servico_realizado)
							JOIN    tbl_peca       ON tbl_os_item.peca       = tbl_peca.peca
							JOIN    tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
							JOIN    tbl_os         ON tbl_os_produto.os      = tbl_os.os
							JOIN    tbl_produto    ON tbl_os.produto         = tbl_produto.produto
							WHERE   tbl_os_item.pedido = $pedido
							AND     tbl_servico_realizado.troca_de_peca
							AND     tbl_servico_realizado.gera_pedido
							AND     tbl_servico_realizado.troca_produto IS NOT TRUE
							AND     tbl_peca.produto_acabado IS NOT TRUE
							ORDER BY tbl_os.sua_os , tbl_peca.referencia";
					$resItem = pg_exec($con,$sql);
				}
			}
			//echo "<BR>";
	//echo nl2br($sql);echo "<BR>";


			$sql = "UPDATE tbl_pedido SET exportado = current_timestamp,
					status_pedido   = 9, 
					admin_alteracao = 	$login_admin
					WHERE  tbl_pedido.pedido = $pedido
					and tbl_pedido.fabrica = $login_fabrica
					AND    tbl_pedido.exportado IS NULL ";
			$res0 = pg_exec($con,$sql);

		//	$res0 = pg_exec($con,$sql);
			
			$sql = "UPDATE tbl_pedido SET status_pedido = 9
					WHERE  tbl_pedido.pedido = $pedido
					AND    tbl_pedido.distribuidor IS NULL ";
		//	$res0 = $conn-> exec ($sql);
		//	$res0 = pg_exec($con,$sql);
		

			### NATUREZA DE OPERAÇÃO PARA PEDIDOS EM GARANTIA ###
			if (strlen($tipo_pedido)>0) {
				if ($tipo_pedido == "Garantia") {
					$pedido = $pedido . "G";
					$footer_pedido_pedido = $footer_pedido_pedido . "G";
					$desconto = '70,00';
					if ($uf == "MG") {
						$natureza_operacao = "5.949.19";
					}else{
						$natureza_operacao = "6.949.21";
						
						### Verifica no cadastro do posto se é da região SUFRAMA
						if ($suframa == "t") {
							$natureza_operacao = "6.949.17";
						}
					}
				}
			}

	#		print "Gravando pedido $pedido \n";
	#		print $resItem->ntuples();
	#		print "=========== \n";


			for($x=0;pg_numrows($resItem)>$x;$x++){
	//			= trim(pg_result ($res,$i,pedido));
				$peca               = trim(pg_result ($resItem,$x,peca));
				$qtde               = trim(pg_result ($resItem,$x,qtde));
				$sua_os             = trim(pg_result ($resItem,$x,sua_os));
				$produto            = trim(pg_result ($resItem,$x,produto));
				$serie              = trim(pg_result ($resItem,$x,serie));
				$abertura           = trim(pg_result ($resItem,$x,abertura));
				$consumidor_revenda = trim(pg_result ($resItem,$x,consumidor_revenda));
				$preco              = trim(pg_result ($resItem,$x,preco));
				$devolucao_obrigatoria = trim(pg_result ($resItem,$x,devolucao_obrigatoria));

				#hd 43415
				if ($tipo_pedido == "Garantia") {
					$transacao = '90107';
				} else {
					$transacao = '90106';
				}


				if (strlen($consumidor_revenda)==0) {
					$consumidor_revenda = "C";
				}
				
				if ($consumidor_revenda == 'R') {
					$c_r = 'REVENDA';
				}else{
					$c_r = 'NORMAL';
				}

				if ($pedido_ant <> $pedido) {

					if ($pedido) {
						fputs ($fp, "1;".$pedido . ";");
					}else{
						fputs ($fp, ";");
					}

					if ($transacao) {
						fputs ($fp, $transacao . ";");
					}else{
						fputs ($fp, ";");
					}

					if ($dt1) {
						fputs ($fp, $dt1 . ";");
					}else{
						fputs ($fp, ";");
					}

					if ($obs) {
						fputs ($fp, $obs . ";");
					}else{
						fputs ($fp, ";");
					}

					if ($cnpj) {
						fputs ($fp, $cnpj . ";");
					}else{
						fputs ($fp, ";");
					}

					if ($nome) {
						fputs ($fp, $nome . ";");
					}else{
						fputs ($fp, ";");
					}

					if ($cond_pagto == '0') {
						fputs ($fp, $cond_pagto . ";");
					}else{
						fputs ($fp, $cond_pagto . ";");
					}

					if ($descr_cond_pagto) {
						fputs ($fp, $descr_cond_pagto . ";");
					}else{
						fputs ($fp, ";");
					}

					if ($empresa) {
						fputs ($fp, $empresa . ";");
					}else{
						fputs ($fp, ";");
					}
					
					if ($tipo_trans) {
						fputs ($fp, $tipo_trans . ";");
					}else{
						fputs ($fp, ";");
					}

					if ($cod_trans) {
						fputs ($fp, $cod_trans . ";");
					}else{
						fputs ($fp, ";");
					}

					if ($c_r) {
						fputs ($fp, $c_r . ";");
					}else{
						fputs ($fp, ";");
					}

					fputs ($fp, "\n");

				}

				if ($pedido) {
					fputs ($fp, "2;".$pedido . ";");
				}else{
					fputs ($fp, ";");
				}

				if ($dt1) {
					fputs ($fp, $dt1 . ";");
				}else{
					fputs ($fp, ";");
				}

				#hd 33632  -está fixo para teste, quando for liberar deve ser atualizada a tabela do banco para este código
				//$tabela = 'TABG';
				if ($tabela) {
					fputs ($fp, $tabela . ";");
				}else{
					fputs ($fp, ";");
				}

				if ($peca) {
					fputs ($fp, $peca . ";");
				}else{
					fputs ($fp, ";");
				}
				
				if ($qtde) {
					fputs ($fp, $qtde . ";");
				}else{
					fputs ($fp, ";");
				}

				if ($transacao) {
					fputs ($fp, $transacao . ";");
				}else{
					fputs ($fp, ";");
				}

				if ($preco) {
					fputs ($fp, $preco . ";");
				}else{
					fputs ($fp, ";");
				}

				if ($tipo_pedido) {
					if ($tipo_pedido == "Garantia") {
						fputs ($fp, "S;");
					}else{
						fputs ($fp, "N;");
					}
				}else{
					fputs ($fp, ";");
				}

				if ($serie) {
					fputs ($fp, $serie . ";");
				}else{
					fputs ($fp, ";");
				}

				if ($desconto) {
					fputs ($fp, $desconto . ";");
				}else{
					fputs ($fp, ";");
				}

				if ($cnpj) {
					fputs ($fp, $cnpj . ";");
				}else{
					fputs ($fp, ";");
				}

				/*if ($p1 == '1') {
					fputs ($fp, $p1 . ";");
				}else{
					fputs ($fp, ";");
				}
				
				if ($p2 == '0') {
					fputs ($fp, $p2 . ";");
				}else{
					fputs ($fp, ";");
				}
				
				if ($dt2) {
					fputs ($fp, $dt2 . ";");
				}else{
					fputs ($fp, ";");
				}
				
				if ($posto) {
					fputs ($fp, $posto . ";");
				}else{
					fputs ($fp, ";");
				}
				
				if ($cod_repr) {
					fputs ($fp, $cod_repr . ";");
				}else{
					fputs ($fp, ";");
				}
				
				if ($nome_repr) {
					fputs ($fp, $nome_repr . ";");
				}else{
					fputs ($fp, ";");
				}
				
				if ($dt3) {
					fputs ($fp, $dt3 . ";");
				}else{
					fputs ($fp, ";");
				}
				
				if ($ped_suggar) {
					fputs ($fp, $ped_suggar . ";");
				}else{
					fputs ($fp, ";");
				}
				
				if ($num_ped_suggar) {
					fputs ($fp, $num_ped_suggar . ";");
				}else{
					fputs ($fp, ";");
				}
				
				if ($natureza_operacao) {
					fputs ($fp, " ;");
				}else{
					fputs ($fp, ";");
				}
				
				if ($sua_os) {
					fputs ($fp, $sua_os . ";");
				}else{
					fputs ($fp, ";");
				}
				
				if ($produto) {
					fputs ($fp, $produto . ";");
				}else{
					fputs ($fp, ";");
				}
				
				if ($abertura) {
					fputs ($fp, $abertura . ";");
				}else{
					fputs ($fp, ";");
				}
				
				if ($posto_original) {
					fputs ($fp, $posto_original . ";");
				}else{
					fputs ($fp, ";");
				}

				if ($tabela_distribuidor) {
					fputs ($fp, $tabela_distribuidor . ";");
				}else{
					fputs ($fp, ";");
				}

				if ($devolucao_obrigatoria) {
					fputs ($fp, $devolucao_obrigatoria . ";");
				}else{
					fputs ($fp, "NO" . ";");
				}*/

				$footer_qtde_item += 1 ;
				$footer_qtde_peca += $qtde ;

				$footer_pedido_qtde_item += 1 ;
				$footer_pedido_qtde_peca += $qtde ;

				fputs ($fp, "\n");
				
				$pedido_ant = $pedido;
			}
		}
	}
	fputs ($fp, "#");
	fputs ($fp, $footer_pedido_pedido);
	fputs ($fp, ";");
	fputs ($fp, $footer_pedido_qtde_item);
	fputs ($fp, ";");
	fputs ($fp, $footer_pedido_qtde_peca);
	fputs ($fp, ";");
	fputs ($fp, $footer_pedido_posto);
	fputs ($fp, "\n");

	//fputs ($fp, "*;");
	//fputs ($fp, $footer_qtde_pedido);
	//fputs ($fp, ";");
	//fputs ($fp, $footer_qtde_item);
	//fputs ($fp, ";");
	//fputs ($fp, $footer_qtde_peca);
	//fputs ($fp, "\n");
	
	fclose ($fp);
}

/*
if (strlen($garantia)>0  AND $login_admin='232') {
	$sql = "SELECT fn_black_pedido_garantia($garantia)";
//	echo $sql;
	$res = pg_exec ($con,$sql);
	if ($res) {
		$pedido = $garantia;
	}
}*/
#------------ Le Pedido da Base de dados ------------#
//	echo `/www/assist/www/admin/xls/30-07-200710-18-09-637973-397.txt /tmp/assist/30-07-200710-18-09-637973-397.txt`;
	//	$fp = fopen ("/tmp/assist/30-07-200710-18-09-637973-397.txt","w");
/*
		header("Content-type: application/x-msdownload");
        header("Content-Disposition: attachment; filename=xls/$pedido-$data-$login_admin.sdf");
        header("Pragma: no-cache");
        header("Expires: 0");
*/
	echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
	echo"<tr>";
	echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'><BR>ARQUIVO DE PEDIDO<BR>Clique aqui para fazer o </font><a href='xls/$pedido_link-$data-$login_admin.sdf'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em SDF</font></a>.<br></td>";
	echo "</tr>";
	echo "</table>";


?>
<? include "rodape.php"; ?>
