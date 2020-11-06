<?php

error_reporting(E_ALL ^ E_NOTICE);

try {
	
    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require_once dirname(__FILE__) . '/../funcoes.php';

    define('APP', 'Atualiza Status Pedido');
	define('ENV','producao');

    $vet['fabrica'] = 'Telecontrol';
    $vet['tipo']    = 'atualiza-status';
    $vet['dest']    = ENV == 'testes' ? 'ronald.santos@telecontrol.com.br' : 'helpdesk@telecontrol.com.br';
    $vet['log']     = 1;

	if(!empty($argv[1])) {
		$peca = $argv[1];
		$cond = " AND tbl_faturamento_item.peca = $peca";
	}

	$sql = "SELECT faturamento_item,tbl_faturamento_item.qtde as qtde ,emissao
			FROM tbl_faturamento
				JOIN tbl_faturamento_item using (faturamento)
				JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca
				WHERE  tbl_faturamento.posto in ( 4311,376542)
				AND (
					tbl_faturamento.distribuidor IN (
						/* Seleciona apenas os distribuidores da condição nova, descartando quando o posto entrava como
						distribuidor (LRG Britania)*/
						SELECT DISTINCT distribuidor FROM tbl_faturamento WHERE fabrica = 10 AND posto = 4311
						and distribuidor is not null and distribuidor <> 4311)
					OR
					tbl_faturamento.fabrica in (10)
					AND tbl_faturamento.distribuidor is null
				)
				AND tbl_faturamento.cancelada IS NULL
				AND tbl_faturamento.fabrica =10
				AND (tbl_faturamento.tipo_nf = 0 or tbl_faturamento.tipo_nf IS NULL)
				$cond
				ORDER BY tbl_faturamento_item.faturamento_item;";
	$res = pg_query($con,$sql);
	if(pg_numrows($res) > 0){
			for($i=0;$i<pg_num_rows($res);$i++) {
					NOVO:
 					$fi_entrada	= pg_fetch_result($res,$i,'faturamento_item');
 					$emissao	= pg_fetch_result($res,$i,'emissao');
					$qtde_entrada		= pg_fetch_result($res,$i,'qtde');
					if(!empty($fi_restante) and $qtde_nota <> 0 ) {
						$cond_ex = " and tbl_faturamento.emissao >='$emissao_rest' ";
						$qtde_nota = $qtde_nota * -1; 
						$qtde_saida = $qtde_entrada - $qtde_nota;
						if($qtde_saida > 0) {
								$sqli = "INSERT INTO tbl_faturamento_fifo(faturamento_item_entrada,faturamento_item_devolucao,qtde)values($fi_entrada, $fi_restante,$qtde_nota)";
								$resi = pg_query($con,$sqli);
						}else{
								$qtde_rest = $qtde_saida + $qtde_nota;
								$qtde_nota = $qtde_saida;
								$sqli = "INSERT INTO tbl_faturamento_fifo(faturamento_item_entrada,faturamento_item_devolucao,qtde)values($fi_entrada, $fi_restante,$qtde_rest)";
								$resi = pg_query($con,$sqli);
								$sqli = "UPDATE  tbl_faturamento_fifo set baixado = true where faturamento_item_entrada = $fi_entrada ";
								$resi = pg_query($con,$sqli);

								$i++;
								goto NOVO;
						}
					}
					$sqlx = "SELECT faturamento_fifo
							FROM tbl_faturamento_fifo
							WHERE faturamento_item_entrada = $fi_entrada 
							and baixado";
					$resx = pg_query($con,$sqlx);
					if(pg_num_rows($resx) > 0) {
							continue;
					}

					$sqlx = "SELECT sum(qtde) as qtde_baixada 
							FROM tbl_faturamento_fifo
							WHERE faturamento_item_entrada = $fi_entrada 
							and qtde > 0";
					$resx = pg_query($con,$sqlx);
					if(pg_num_rows($resx) > 0) {
						$qtde_baixada = pg_fetch_result($resx,0,'qtde_baixada');
						$qtde_entrada -= $qtde_baixada;
					}

					$sqlx = "SELECT	faturamento_item,qtde,emissao
							FROM tbl_faturamento
							JOIN tbl_faturamento_item USING (faturamento)
							JOIN tbl_peca USING(peca)
							WHERE tbl_faturamento_item.peca = $peca
							AND   tbl_faturamento.fabrica = 10
							AND   tbl_faturamento.distribuidor in ( 4311,376542)
							and   tbl_faturamento_item.faturamento_item > $fi_entrada
							and   tbl_faturamento_item.faturamento_item not in (select faturamento_item from tbl_faturamento_item join tbl_faturamento_fifo on tbl_faturamento_fifo.faturamento_item_devolucao = tbl_faturamento_item.faturamento_item where peca = $peca) 
							$cond_ex
							AND   status_nfe='100'
						   and tbl_faturamento.cfop in ('5202')	order by emissao, faturamento_item ";
					$resx = pg_query($con,$sqlx);
					if(pg_num_rows($resx) == 0) {
						break;
					}
					$qtde_nota = $qtde_entrada;
					$fi_saida_total = array();
					for($j=0;$j<pg_num_rows($resx);$j++) {
						$fi_restante = null;
						$fi_saida	= pg_fetch_result($resx,$j,'faturamento_item');
 						$emissao_saida	= pg_fetch_result($resx,$j,'emissao');
						$qtde_saida	= pg_fetch_result($resx,$j,'qtde');
						$fi_saida_total[] = $fi_saida;

						$qtde_nota -= $qtde_saida;
						if($qtde_nota< 0){
							$fi_restante = $fi_saida;
							$emissao_rest = $emissao_saida;
							$qtde_restante = $qtde_nota + $qtde_saida;
							$sqli = "INSERT INTO tbl_faturamento_fifo(faturamento_item_entrada,faturamento_item_devolucao,qtde)values($fi_entrada,$fi_saida,$qtde_restante)";
							$resi = pg_query($con,$sqli);

							$sqli = "UPDATE  tbl_faturamento_fifo set baixado = true where faturamento_item_entrada = $fi_entrada ";
							$resi = pg_query($con,$sqli);

							$i++;
							goto NOVO;
						}else{
							$sqli = "INSERT INTO tbl_faturamento_fifo(faturamento_item_entrada,faturamento_item_devolucao,qtde)values($fi_entrada, $fi_saida,$qtde_saida)";
							$resi = pg_query($con,$sqli);
						}
					}

					if($qtde_nota > 0) break;
			}
		}

	if (!empty($msg_erro)) {
		$msg = 'Script: '.__FILE__.'<br />' . $msg_erro;
		Log::envia_email($vet, APP, $msg);
	}

} catch (Exception $e) {

    $msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
    Log::envia_email($vet, APP, $msg);

}
