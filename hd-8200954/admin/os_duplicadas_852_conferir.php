<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";


	$sql = "select nota_fiscal, count(tbl_os.nota_fiscal) as qtde
			into temp tmp_nota_fiscal_852
			from tbl_os
			join tmp_black_os_duplicada_852 on tmp_black_os_duplicada_852.os = tbl_os.os and tmp_black_os_duplicada_852.posto = tbl_os.posto
			where tbl_os.fabrica = 1 and tbl_os.consumidor_revenda = 'C'
			group by nota_fiscal
			having count(tbl_os.nota_fiscal) = 1;

			select distinct tmp_black_os_duplicada_852.codigo_posto,
					tbl_os.data_digitacao,
					tbl_os.consumidor_nome,
					tbl_os.nota_fiscal,
					tmp_black_os_duplicada_852.sua_os,
					tmp_black_os_duplicada_852.os,
					tbl_posto_fabrica.reembolso_peca_estoque,
					to_char(tbl_os.data_fechamento, 'dd/mm/yyyy') as data_fechamento,
					tbl_extrato.protocolo as extrato,
					tbl_extrato_financeiro.data_envio
					into temp tmp_black_os_duplicada_final_852
			from tbl_os
			join tbl_os_extra using(os)
			join tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_os.posto and tbl_posto_fabrica.fabrica = 1
			left join tbl_extrato on tbl_extrato.extrato = tbl_os_extra.extrato
			left join tbl_extrato_financeiro on tbl_extrato_financeiro.extrato = tbl_extrato.extrato
			join tmp_black_os_duplicada_852 on tmp_black_os_duplicada_852.os = tbl_os.os and tmp_black_os_duplicada_852.posto = tbl_os.posto
			join tmp_nota_fiscal_852 on tmp_nota_fiscal_852.nota_fiscal = tbl_os.nota_fiscal
			where tbl_os.fabrica = 1 and tbl_os.consumidor_revenda = 'C'
			order by tbl_os.data_digitacao, tbl_os.consumidor_nome desc;
			
			update tmp_black_os_duplicada_852 set relatorio = 't' where tmp_black_os_duplicada_852.os = tmp_black_os_duplicada_final_852.os;
			
			select * from tmp_black_os_duplicada_final_852
			
			/*select distinct (select codigo_posto from tbl_posto_fabrica where tbl_posto_fabrica.posto = tbl_os.posto and tbl_posto_fabrica.fabrica = 1) as codigo_posto,
			tbl_os.os,
			tbl_os.sua_os,
			to_char(tbl_os.data_digitacao, 'dd/mm/yyyy') as data_digitacao,
			tbl_posto_fabrica.reembolso_peca_estoque,
			to_char(tbl_os.data_fechamento, 'dd/mm/yyyy') as data_fechamento,
			tbl_os_extra.extrato,
			tbl_extrato_financeiro.data_envio
			from tbl_os
			join tbl_os_extra using(os)
			join tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_os.posto and tbl_posto_fabrica.fabrica = 1
			left join tbl_extrato_financeiro using(extrato)
			where tbl_os.fabrica = 1 and tbl_os.consumidor_revenda = 'C' and tbl_os.os in(12029589,11502768,11657165)*/
			";
	$res = pg_exec($con, $sql);
	#echo nl2br($sql);
	if(pg_numrows($res)>0){
		echo "<br>";
		echo "<TABLE border='1' cellpadding='2' cellspacing='2' align='center'>";
			echo "<TR>";
				echo "<TD>Posto</TD>";
				echo "<TD>OS</TD>";
				echo "<TD>SUA OS</TD>";
				//echo "<TD>Data digitação</TD>";
				echo "<TD>Recebe peça em garantia</TD>";
				echo "<TD>OS com peças</TD>";
				echo "<TD>Data fechamento</TD>";
				echo "<TD>Extrato</TD>";
				echo "<TD>Enviado para o financeiro</TD>";
			echo "</TR>";

		for($x=0; $x<pg_numrows($res); $x++){
			$codigo_posto           = pg_result($res,$x,codigo_posto);
			$os                     = pg_result($res,$x,os);
			$sua_os                 = pg_result($res,$x,sua_os);
			$consumidor_nome        = pg_result($res,$x,consumidor_nome);
			$nota_fiscal            = pg_result($res,$x,nota_fiscal);
			$reembolso_peca_estoque = pg_result($res,$x,reembolso_peca_estoque);
			$data_fechamento        = pg_result($res,$x,data_fechamento);
			$extrato                = pg_result($res,$x,extrato);
			$data_envio             = pg_result($res,$x,data_envio);
			$data_digitacao         = pg_result($res,$x,data_digitacao);

			echo "<TR>";
				echo "<TD>&nbsp; $codigo_posto</TD>";
				echo "<TD>&nbsp; $os</TD>";
				echo "<TD>";
					echo "&nbsp; ".$sua_os;
					if($sua_os_ant != $sua_os and $nota_fiscal_ant == $nota_fiscal){
						//echo " (OS duplicada com a OS $sua_os_ant)";
					}
					$sua_os_ant      = $sua_os;
					$nota_fiscal_ant = $nota_fiscal;
					echo " (OS única, duplicada excluída anteriormente)";
				echo "</TD>";
				//echo "<TD>$data_digitacao</TD>";
				echo "<TD>";
					if($reembolso_peca_estoque=='t'){ echo 'SIM'; }else{ echo 'NÃO'; }
				echo "</TD>";
				echo "<TD>";
					echo '&nbsp;';
					if($reembolso_peca_estoque=='t' or 1==1){
						$sqlP = "select tbl_os_item.peca, tbl_os_item.pedido
								 from tbl_os
								 join tbl_os_produto using(os)
								 join tbl_os_item    using(os_produto)
								 where tbl_os.os = $os";
						$resP = pg_exec($con, $sqlP);

						if(pg_numrows($resP)>0){
							echo "OS com peças lançadas";

							$sqly = "select tbl_os_item.peca, tbl_peca.produto_acabado
									 from tbl_os
									 join tbl_os_produto using(os)
									 join tbl_os_item    using(os_produto)
									 join tbl_peca       using(peca)
									 where tbl_os.os = $os and tbl_os_item.pedido is not null";
							$resy = pg_exec($con, $sqly);

							if(pg_numrows($resy)>0){
								for($i=0; $i<pg_numrows($resy); $i++){
									$produto_acabado = pg_result($resy,$i,produto_acabado);

									if($produto_acabado=='t') $tipo_pedido = ' e com pedido de produtos'; else $tipo_pedido = ' e com pedidos de peças';
								}
								echo  $tipo_pedido;
							}
						}
					}
				echo "</TD>";
				echo "<TD>&nbsp; $data_fechamento</TD>";
				echo "<TD>&nbsp; $extrato</TD>";
				echo "<TD>";
					 echo "&nbsp;";
					if(strlen($data_envio)>0) echo 'SIM'; else echo 'NÃO';
				echo "</TD>";
			echo "</TR>";

		}
		echo "</TABLE>";
	}

?>