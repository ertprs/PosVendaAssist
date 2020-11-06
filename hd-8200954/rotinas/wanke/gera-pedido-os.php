<?php  

define('ENV', 'testes');

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';

	$msg_erro       = array();
	$log            = array();

	$vet['fabrica'] = 'wanke';
	$vet['tipo']    = 'pedido';

	if (ENV == 'testes') {
		$vet['dest'] = 'guilherme.curcio@telecontrol.com.br';
	} else {
		$vet['dest'] = 'helpdesk@telecontrol.com.br';
	}

	$vet['log']     = 2;

	$vet2        = $vet;
	$vet2['log'] = 1;

	$fabrica    = "91" ;

	$phpCron = new PHPCron($fabrica, __FILE__);
	$phpCron->inicio();



	$sql5 = "SELECT      tbl_os.posto        ,
			tbl_produto.linha   ,
			tbl_os_item.peca    ,
			tbl_os_item.os_item ,
			tbl_os_item.qtde    ,
			tbl_os.sua_os       ,
			array(select status_os from tbl_os_status where tbl_os_status.os = tbl_os.os and status_os IN (98,99,100,101,161)  and fabrica_status = $fabrica order by os_status desc) as status_km,
			array(select status_os from tbl_os_status where tbl_os_status.os = tbl_os.os and status_os IN (147,19)  and fabrica_status = $fabrica order by os_status desc) as status_serie,
			array(select status_os from tbl_os_status where tbl_os_status.os = tbl_os.os and status_os IN (102,103)  and fabrica_status = $fabrica order by os_status desc) as status_ns,
			array(select status_os from tbl_os_status where tbl_os_status.os = tbl_os.os and status_os IN (19,155,157,139,90)  and fabrica_status = $fabrica order by os_status desc) as status_ns_df,
			array(select status_os from tbl_os_status where tbl_os_status.os = tbl_os.os and status_os IN (62,64)  and fabrica_status = $fabrica order by os_status desc) as status_reinc,
			array(select status_os from tbl_os_status where tbl_os_status.os = tbl_os.os and status_os IN (19,68, 90, 139)  and fabrica_status = $fabrica order by os_status desc) as status_interv,
			array(select status_os from tbl_os_status where tbl_os_status.os = tbl_os.os and status_os IN (13, 19, 178)  and fabrica_status = $fabrica order by os_status desc) as status_cri

			INTO TEMP tmp_pedido_status_wanke

			FROM    tbl_os_item

			JOIN    tbl_os_produto USING (os_produto)
			JOIN    tbl_os         USING (os)
			JOIN    tbl_posto      USING (posto)
			JOIN    tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.troca_produto IS NOT TRUE
			JOIN    tbl_produto           ON tbl_os.produto          = tbl_produto.produto
			JOIN    tbl_posto_fabrica     ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = tbl_os.fabrica

			WHERE   tbl_os_item.pedido IS NULL
			AND     tbl_os.validada    IS NOT NULL
			AND     tbl_os.excluida    IS NOT TRUE
			AND     tbl_os.posto       <> 6359
			AND     tbl_os.fabrica     = $fabrica
			AND     tbl_os_item.fabrica_i = $fabrica
			AND     (tbl_posto_fabrica.credenciamento = 'CREDENCIADO' OR tbl_posto_fabrica.credenciamento = 'EM DESCREDENCIAMENTO')
			AND     tbl_servico_realizado.gera_pedido ;

			select * into temp tmp_pedido_wanke from tmp_pedido_status_wanke
			where (status_km[1] not in (98,161) or status_km[1] isnull) 
			and (status_serie[1] not in (147) or status_serie[1] isnull)
			and (status_ns[1] not in  (102) or status_ns[1] isnull)
			and (status_ns_df[1] not in (157) or status_ns_df[1] isnull)
			and (status_reinc[1] not in (62) or status_reinc[1] isnull)
			and (status_interv[1] not in (68) or status_interv[1] isnull)
			and (status_cri[1] not in (13, 178) or status_cri[1] isnull);

			SELECT DISTINCT posto,linha from tmp_pedido_wanke ";

	$res5 = pg_query($con,$sql5);
	
	if (strlen(pg_last_error($con)) > 0) {
		$msg_erro[] = 'Erro na $sql5: '.pg_last_error($con);
	}

	if (count($msg_erro)==0){

		for ($i=0; $i < pg_num_rows($res5); $i++) {
			unset($msg_erro);		
			list($posto, $linha) = pg_fetch_row($res5, $i);
			
			$sql6 = "BEGIN TRANSACTION";
			$res6 = pg_query($con,$sql6);

			$sql7 = "select condicao from tbl_condicao where fabrica = ".$fabrica." and lower(descricao) = 'garantia';";
			$res7 = pg_query($con,$sql7);
			if (strlen(pg_last_error($con)) > 0) {
				$msg_erro[] = 'Erro na $sql7: '.pg_last_error($con);
			}else{
				$condicao = pg_fetch_result($res7, 0, 0);
			}

			$sql8     = "select tipo_pedido from tbl_tipo_pedido where fabrica = ".$fabrica." and lower(descricao) = 'garantia';";
			$res8 = pg_query($con,$sql8);

			if (strlen(pg_last_error($con)) > 0) {
				$msg_erro[] = 'Erro na $sql8: '.pg_last_error($con);
			}else{
				$tipo_pedido = pg_fetch_result($res8, 0, 0);
			}

			if (count($msg_erro)==0){

				$sql9 = "INSERT INTO tbl_pedido (
					posto        ,
					fabrica      ,
					condicao     ,
					tipo_pedido  ,
					linha        ,
					status_pedido
				) VALUES (
					$posto      ,
					$fabrica    ,
					$condicao   ,
					$tipo_pedido,
					$linha      ,
					1
				) RETURNING pedido;";

				$res9 = pg_query($con,$sql9);

				if (strlen(pg_last_error($con)) > 0) {
					$msg_erro[] = 'Erro na $sql9, geração do pedido: '.pg_last_error($con);
				}else{
					$pedido = pg_fetch_result($res9, 0, 0);
				}

				$sql10 = "SELECT peca    ,
						sum(qtde)
				   from tmp_pedido_wanke 
				  WHERE posto = $posto 
					AND linha = $linha
				  group by peca";

				$res10 = pg_query($con,$sql10);

				if (strlen(pg_last_error($con)) > 0) {
					$msg_erro[] = 'Erro na $sql10: '.pg_last_error($con);
				}

				if (pg_num_rows($res10)>0){

					for ($z=0; $z < pg_num_rows($res10); $z++) {
						list($peca, $qtde) = pg_fetch_row($res10, $z);

						$sql11 = "INSERT INTO tbl_pedido_item (
							pedido,
							peca  ,
							qtde  ,
							qtde_faturada,
							qtde_cancelada
						) VALUES (
							$pedido,
							$peca  ,
							$qtde  ,
							0      ,
							0
						) RETURNING pedido_item";

						$res11 = pg_query($con,$sql11);

						if (strlen(pg_last_error($con)) > 0) {
							$msg_erro[] = 'Erro na $sql11: '.pg_last_error($con);
						}else{
							$pedido_item = pg_fetch_result($res11, 0, 0);
						}

						$sql12 = "SELECT fn_atualiza_os_item_pedido_item(os_item ,$pedido,$pedido_item, $fabrica)
								FROM   tmp_pedido_wanke
								WHERE  tmp_pedido_wanke.peca  = $peca
								AND    tmp_pedido_wanke.posto = $posto
								AND    tmp_pedido_wanke.linha = $linha";

						$res12 = pg_query($con,$sql12);

						if (strlen(pg_last_error($con)) > 0) {
							$msg_erro[] = 'Erro na $sql12: '.pg_last_error($con);
						}

					}

					$sql13 = "SELECT fn_pedido_finaliza($pedido, $fabrica)";
					$res13 = pg_query($con,$sql13);

					if (strlen(pg_last_error($con)) > 0) {
						$msg_erro[] = 'Erro na $sql13: '.pg_last_error($con);
					}

				}

			}

			if( count($msg_erro) == 0 ){

				$sql = "SELECT tbl_os.os 
						FROM tbl_os
						JOIN tbl_os_produto USING(os)
						JOIN tbl_os_item USING(os_produto)
						WHERE tbl_os.fabrica = {$fabrica}
						AND tbl_os_item.pedido = {$pedido}
						AND tbl_os.consumidor_revenda = 'C';";
				$res16 = pg_query($con,$sql);

				if(pg_num_rows($res16) == 0){

					$sql = "INSERT INTO tbl_pedido_status(
															pedido,
															status,
															observacao
														) VALUES(
															{$pedido},
															18,
															'Pedido de OS REVENDA'
														);";
					$res17 = pg_query($con,$sql);

					if (strlen(pg_last_error($con)) > 0) {
						$msg_erro[] = 'Erro na $sql17: '.pg_last_error($con);
					}else{

						$sql = "UPDATE tbl_pedido SET status_pedido = 18 WHERE pedido = {$pedido};";
						$res18 = pg_query($con,$sql);

						if (strlen(pg_last_error($con)) > 0) {
							$msg_erro[] = 'Erro na $sql18: '.pg_last_error($con);
						}

					}

				}

			}

			if (count($msg_erro)>0){

				$res14 = pg_query($con,"ROLLBACK TRANSACTION");

				$sql15 = "SELECT DISTINCT codigo_posto,
				tmp_pedido_wanke.sua_os,
				referencia,
				qtde,
				tbl_tabela_item.preco
				FROM 
				tmp_pedido_wanke 
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tmp_pedido_wanke.posto and tbl_posto_fabrica.fabrica = $fabrica
				JOIN tbl_peca USING(peca)
				JOIN tbl_posto_linha    ON tbl_posto_linha.posto     = tmp_pedido_wanke.posto
				JOIN tbl_tabela_item    ON tbl_tabela_item.peca      = tmp_pedido_wanke.peca and tbl_tabela_item.tabela    = tbl_posto_linha.tabela
				JOIN tbl_tabela ON tbl_tabela.tabela = tbl_tabela_item.tabela AND tbl_tabela.fabrica = $fabrica";

				$res15 = pg_query($con,$sql15);

				if (pg_num_rows($res15)>0){

					for ($y=0; $y < pg_num_rows($res15); $y++) { 

						list($codigo_posto, $sua_os, $referencia, $qtde, $preco) = pg_fetch_row($res15, $y);
						$log[] = " Posto:$codigo_posto - OS:$sua_os - Peça:$referencia - Qtde:$qtde - Preço:$preco";
					}

				}

			}else {

				$res16 = pg_query($con,"COMMIT TRANSACTION");

			}

		}

	} 



	if ($msg_erro) {

		$msg_erro = implode("<br>", $msg_erro);
		Log::log2($vet, $msg_erro);

	}

	if ($log) {

		$log = implode("<br>", $log);
		Log::log2($vet2, $log);

	}

	if ($msg_erro) {
		
		Log::envia_email($vet, "Log de ERROS - Geração de Pedido de OS WANKE", $msg_erro);

	}

	$phpCron->termino();

} catch (Exception $e) {
	echo $e->getMessage();
}
?>
