<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$linha=$_GET['linha'];

$produto=$_GET['produto'];

$aux_data_inicial = $_GET['data_inicial'];
$aux_data_final = $_GET['data_final'];

$cond_1 = " 1=1 ";
if(strlen($_GET['posto']>0)) {
	$codigo_posto = $_GET['posto'];
	$sqlposto     = "select posto 
					from tbl_posto_fabrica 
					where fabrica = $login_fabrica 
					and codigo_posto = '$codigo_posto'";
	$res = pg_exec($con,$sqlposto);
	
	$posto = pg_result($res,0,0);
	$cond_1 = "tbl_os.posto = $posto";

}



$cond_2 = " 1=1 ";
if ($produto>0) {
	 $cond_2 = " tbl_os.produto = $produto";
}


/*$sql = "SELECT tbl_os_extra.os, tbl_os_produto.os_produto, tbl_extrato.posto,tbl_os.produto
				INTO TEMP tmp_rtp_$login_admin
				FROM tbl_extrato
				JOIN tbl_os_extra on tbl_extrato.extrato = tbl_os_extra.extrato
				JOIN tbl_os USING(os)
				JOIN tbl_os_produto on tbl_os_produto.os = tbl_os_extra.os
				WHERE tbl_extrato.fabrica = $login_fabrica
				AND tbl_os.fabrica = $login_fabrica
				AND tbl_extrato.data_geracao between '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59' ;

				CREATE INDEX tmp_rtp_OS_$login_admin        ON tmp_rtp_$login_admin(os);
				CREATE INDEX tmp_rtp_POSTO_$login_admin     ON tmp_rtp_$login_admin(posto);
				CREATE INDEX tmp_rtp_OSPRODUTO_$login_admin ON tmp_rtp_$login_admin(os_produto);

				SELECT	tbl_os.os,
						to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
						to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
						tbl_os.serie,
						tbl_os.consumidor_nome,
						tbl_produto.descricao
				FROM tmp_rtp_$login_admin X
				JOIN tbl_os USING(os)
				JOIN tbl_produto on X.produto = tbl_produto.produto
				JOIN tbl_os_item on X.os_produto = tbl_os_item.os_produto
				JOIN tbl_servico_realizado on tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado and $cond_2
				JOIN tbl_peca on tbl_os_item.peca = tbl_peca.peca and tbl_peca.fabrica = $login_fabrica
				JOIN tbl_posto on tbl_posto.posto = X.posto
				JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto 
				and tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE $cond_1
					  $cond_3
					  X.os not in (
										SELECT DISTINCT X.os
										FROM tmp_rtp_$login_admin X
										JOIN tbl_peca on tbl_os_item.peca = tbl_peca.peca and tbl_peca.fabrica = 24
										JOIN tbl_posto on tbl_posto.posto = X.posto
										JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto 
										and tbl_posto_fabrica.fabrica = 24
										WHERE $cond_1 
											$cond_3
											tbl_os_item.servico_realizado in (504,522)
									)";
*/
/*$sql = "SELECT	 tbl_os.os,
		to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
		to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
		tbl_os.serie,
		tbl_os.consumidor_nome,
		tbl_produto.descricao
         from tbl_extrato
         join tbl_os_extra          on tbl_extrato.extrato                     = tbl_os_extra.extrato
         join tbl_os                on tbl_os.os                               = tbl_os_extra.os
         join tbl_os_produto        on tbl_os_produto.os                       = tbl_os.os
         join tbl_os_item           on tbl_os_item.os_produto                  = tbl_os_produto.os_produto
         join tbl_servico_realizado on tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado and
                                       tbl_servico_realizado.troca_de_peca     = 'f'
         join tbl_posto             on tbl_posto.posto                         = tbl_os.posto and
                                       tbl_Posto.posto=$posto
         join tbl_posto_fabrica     on tbl_posto_fabrica.posto                 = tbl_posto.posto and
                                       tbl_posto_fabrica.fabrica=$login_fabrica
         join tbl_produto           on tbl_produto.produto                     = tbl_os.produto
		where    tbl_extrato.fabrica=$login_fabrica
		and $cond_1
		and $cond_2
		and tbl_extrato.data_geracao between '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'";
*/
$sql = "SELECT	 tbl_os.os,
		     to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
		     to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
	         tbl_os.serie,
		     tbl_os.consumidor_nome,
		     tbl_produto.descricao
        from tbl_extrato
        join tbl_os_extra          on tbl_extrato.extrato  = tbl_os_extra.extrato
        join tbl_os                on tbl_os.os            = tbl_os_extra.os and
		                               tbl_os.posto         =$posto and
									   tbl_os.fabrica       =$login_fabrica     
        join tbl_produto           on tbl_produto.produto  = tbl_os.produto
		where    tbl_extrato.fabrica=$login_fabrica
		and $cond_1
		and $cond_2
		and tbl_extrato.data_geracao between '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'
	    and tbl_os_extra.os in    ( select tbl_os_produto.os  from tbl_os_produto
                                      join tbl_os_item           on tbl_os_item.os_produto =                                                  tbl_os_produto.os_produto
                                      join tbl_servico_realizado on                                                          tbl_servico_realizado.servico_realizado =                                 tbl_os_item.servico_realizado and
                                                   tbl_servico_realizado.troca_de_peca = 'f'
                                     where  tbl_os_produto.os = tbl_os_extra.os) 
        and tbl_os_extra.os not in ( select tbl_os_produto.os  from tbl_os_produto
                                       join tbl_os_item           on tbl_os_item.os_produto =                                                 tbl_os_produto.os_produto
                                       join tbl_servico_realizado on                                                         tbl_servico_realizado.servico_realizado =                                tbl_os_item.servico_realizado and
                                                    tbl_servico_realizado.troca_de_peca = 't'
                                      where  tbl_os_produto.os = tbl_os_extra.os)";
//echo nl2br($sql);

$res = pg_exec ($con,$sql);

echo "$linha|";
	echo "<table border=1 cellpadding=1 cellspacing=1 style=border-collapse: collapse bordercolor=#d2e4fc align=center width=500>";
		echo "<tr class=titulo_coluna>";
		echo "<td>Os</td>";
		echo "<td >Data Abertura</td>";
		echo "<td >Data Fechamento</td>";
		echo "<td >Serie</td>";
		echo "<td >Consumidor</td>";
		echo "<td >Produto</td>";
		echo "</tr>";
	
		$total = pg_numrows($res);
		$total_pecas = 0;
		
		for ($i=0; $i<pg_numrows($res); $i++){

			$os                       = trim(pg_result($res,$i,os));
			$data_abertura            = trim(pg_result($res,$i,data_abertura));
			$data_fechamento          = trim(pg_result($res,$i,data_fechamento));
			$serie                    = trim(pg_result($res,$i,serie));
			$consumidor               = trim(pg_result($res,$i,consumidor_nome));
			$produto                  = trim(pg_result($res,$i,descricao));

			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';
			$total_pecas = $total_pecas + $qtde;
					echo "<tr>";
			echo "<td bgcolor=$cor align=center nowrap><a href=os_press.php?os=$os target=_blank>$os</a></td>";
			echo "<td bgcolor=$cor align=left nowrap>$data_abertura</td>";
			echo "<td bgcolor=$cor nowrap>$data_fechamento</td>";
			echo "<td bgcolor=$cor nowrap>$serie</td>";
			echo "<td bgcolor=$cor nowrap>$consumidor</td>";
			echo "<td bgcolor=$cor nowrap>$produto</td>";
			echo "</tr>";
					echo "</tr>";
				}

?>