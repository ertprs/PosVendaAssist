<?php
/**
 *
 * bonus.php 
 *
 * Definição de bonus
 */

error_reporting(E_ALL ^ E_NOTICE);


try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	include dirname(__FILE__) . '/../../classes/Posvenda/Extrato.php';

	$fabrica = 153;

	$classExtrato = new Extrato($fabrica);
	$sql1 = "SELECT extrato_programado, posto
			FROM tbl_posto_fabrica
			WHERE fabrica = $fabrica
			AND CREDENCIAMENTO <> 'DESCREDENCIADO'
			AND extrato_programado notnull
			";
	$res1 = pg_query($con,$sql1);

	for($i=0;$i<pg_num_rows($res1);$i++) {
		$posto = pg_fetch_result($res1,$i,'posto');
		$extrato_programado	= pg_fetch_result($res1,$i,'extrato_programado');

		$sqlp = "SELECT (current_date - '$extrato_programado'::date)/30";
		$resp = pg_query($con,$sqlp);

		$mes = pg_fetch_result($resp,0,0);

		if($mes < 3 or empty($mes)) continue;

		$sql = "SELECT  	os,
							data_fechamento,
							data_abertura,
							tbl_os.os_reincidente,
							(data_fechamento - data_abertura) as dias,
							tbl_os.tipo_atendimento,
							tbl_os.mao_de_obra,
							tbl_os_troca.os_troca
				into temp tmp_ex_$posto
				FROM tbl_os
				JOIN tbl_os_extra USING(OS)
				JOIn tbl_extrato USING (extrato,fabrica,posto)
				left join tbl_os_troca using(os) 
				where tbl_os.fabrica = $fabrica
				and tbl_os.posto = $posto
				and data_geracao > '$extrato_programado'::date + interval '1 day';

				create index tmp_ex_os_$posto on tmp_ex_$posto(os);

				SELECT * FROM tmp_ex_$posto;
				";
		$res = pg_query($con,$sql);
		$conta_os = 0 ; 
		if(pg_num_rows($res) > 0) {
			for($j=0;$j<pg_num_rows($res);$j++) {
				$os              = pg_fetch_result($res,$j,'os');
				$data_fechamento = pg_fetch_result($res,$j,'data_fechamento');
				$data_abertura   = pg_fetch_result($res,$j,'data_abertura');
				$os_troca		 = pg_fetch_result($res,$j,'os_troca');
				$dias            = pg_fetch_result($res,$j,'dias');

				if(empty($os_troca)) { 
					$sql_os = "SELECT (emissao - digitacao_item::date) as dias_item
										FROM tbl_os_item
										JOIN tbl_os_produto USING(os_produto)
										JOIN tbl_faturamento_item using(peca, pedido,os_item)
										JOIN tbl_faturamento using(faturamento)
										where tbl_os_produto.os = $os";

					$res_os = pg_query($con,$sql_os);
					if(pg_num_rows($res_os) > 0) {
						$dias_item = pg_fetch_result($res_os,0,'dias_item');

						if(($dias - ($dias_item + 10)) > 20) {
								$conta_os++;
						}
					}elseif($dias > 20){
						$conta_os++;
					}
				}
			}
		}else{
			$sql = "UPDATE tbl_posto_fabrica SET extrato_programado = extrato_programado + INTERVAL '3 months' WHERE posto = $posto AND fabrica = $fabrica";
			$res = pg_query($con,$sql);

			continue;
		}

		$qtde = 0 ;
		$sql = "SELECT COUNT(1) from tmp_ex_$posto where tipo_atendimento <> 243";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0) {
			$qtde = pg_fetch_result($res,0,0);
		}

		#troca com falha na analise;
		$sql = "SELECT count(1) FROM tbl_analise_falha join tbl_os using(os) join tmp_ex_$posto using(os) where posto = $posto and fabrica = $fabrica and causa_troca = 382 ";
		$res = pg_query($con,$sql);
		$analise_falha = 0 ;
		if(pg_num_rows($res) > 0) {
			$analise_falha = pg_fetch_result($res,0,0);
		}

		#os com auditoria reprovada
		$sql = "SELECT count(1) from tbl_auditoria_os join tbl_os using(os) join tbl_admin ON tbl_admin.admin = tbl_auditoria_os.admin 
			where (
			reprovada::date between '$extrato_programado'::date and current_date 
			or cancelada::date between '$extrato_programado'::date and current_date )
			and posto = $posto
		   and tbl_admin.fabrica = $login_fabrica";
		$res = pg_query($con,$sql);
		$os_reprovada = 0 ;
		if(pg_num_rows($res) > 0) {
			$os_reprovada = pg_fetch_result($res,0,0);
		}

		#os reincidente
		$sql = "SELECT count(1) from  tmp_ex_$posto  where os_reincidente";
		$res = pg_query($con,$sql);
		$os_reincidente = 0 ;
		if(pg_num_rows($res) > 0) {
			$os_reincidente = pg_fetch_result($res,0,0);
		}



		$ouro = false;
		$prata = false;
		if(((int)$qtde * 0.05) >= $conta_os) {
			$prata = true;
		}
		if($prata) {
			if($analise_falha > 1) {
				$prata = false;
			}
		}

		if($prata) {
			if($os_reprovada > 0) {
				$prata = false;
			}
		}

		if($prata) {
			if($qtde * 0.05 < $os_reincidente) {
				$prata = false;
			}
		}


		if($conta_os == 0) $ouro = true;
		if($ouro) {
			if($analise_falha > 0) $ouro = false;
		}
		if($ouro) {
			if($os_reprovada > 0) $ouro = false;
		}
		if($ouro) {
			if($qtde * 0.02 < $os_reincidente) $ouro = false;
		}

		# os usando componente
		$sql = "select count(1) as itens,sum(case when gera_pedido and acessorio then 1 when gera_pedido is false then 1 else 0 end) as acessorio, os 
				from tbl_os_item
				join tbl_os_produto using(os_produto)
				join tmp_ex_$posto using(os)
				join tbl_peca using(peca)
				join tbl_servico_realizado using(servico_realizado)
				where (tbl_os_item.parametros_adicionais !~* 'recall' or tbl_os_item.parametros_adicionais isnull)
				group by os";

		$res = pg_query($con,$sql);
		$os_componente = 0 ;
		if(pg_num_rows($res) > 0) {
			for($k=0;$k<pg_num_rows($res);$k++) {
				$itens = pg_fetch_result($res,$k,'itens');
				$acessorio = pg_fetch_result($res,$k,'acessorio');

				if($itens == $acessorio) {
					$os_componente++;
				}
			}
			if($qtde *0.4 > $os_componente) {
				$prata = false;
			}

			if($qtde *0.7 > $os_componente) $ouro = false;
		}else{
			$ouro = false;
			$prata = false;
		}

		$sql = "SELECT sum(mao_de_obra)
			FROM tmp_ex_$posto
			WHERE tipo_atendimento <> 243
			AND os not in (
				SELECT os
				FROM tmp_ex_$posto
				JOIN tbl_os_troca USING(os)
			)";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0) {
			$mao_de_obra = pg_fetch_result($res,0,0);
		}


		$sql = "UPDATE tbl_posto_fabrica set tipo_posto = 500 where posto = $posto and tipo_posto not in (551,500) and fabrica = $fabrica ";
		$res = pg_query($con,$sql);


		if($prata and !$ouro) {
			$sql = "select extrato from tbl_extrato where fabrica = $fabrica and posto = $posto and data_geracao::date=current_date ";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) > 0) {
				$extrato = pg_fetch_result($res, 0, extrato) ;
			}else{
				$extrato = null;
			}
			$bonus = $mao_de_obra *0.3;
			$sql = "insert into tbl_extrato_lancamento(extrato, fabrica, posto , lancamento, valor , historico, debito_credito) values
				($extrato, $fabrica, $posto, 475,'$bonus', 'Bonus','C');
				UPDate tbl_extrato set avulso='$bonus' where extrato = $extrato";

			$res = pg_query($con,$sql);
			$total_extrato = $classExtrato->calcula($extrato);  
		}
		if($ouro) {
			$sql = "select extrato from tbl_extrato where fabrica = $fabrica and posto = $posto and data_geracao::date=current_date ";
			$res = pg_query($con,$sql);
			$extrato = pg_fetch_result($res, 0, extrato) ;
			if(pg_num_rows($res) > 0) {
				$extrato = pg_fetch_result($res, 0, extrato) ;
			}else{
				$extrato = null;
			}
			$bonus = $mao_de_obra *0.6;
			$sql = "insert into tbl_extrato_lancamento(extrato, fabrica, posto , lancamento, valor , historico, debito_credito) values
				($extrato, $fabrica, $posto, 475,'$bonus', 'Bonus','C');

				UPDate tbl_extrato set avulso='$bonus' where extrato = $extrato";

			$res = pg_query($con,$sql);

			$total_extrato = $classExtrato->calcula($extrato);  
		}


		if($prata and !$ouro) {
			$sql = "UPDATE tbl_posto_fabrica set tipo_posto = 575 where posto = $posto and fabrica = $fabrica ";
			$res = pg_query($con,$sql);

		}
		if($ouro) {
			$sql = "UPDATE tbl_posto_fabrica set tipo_posto = 574 where posto = $posto and fabrica = $fabrica";
			$res = pg_query($con,$sql);
		}

		$sql = "UPDATE tbl_posto_fabrica SET extrato_programado = extrato_programado + INTERVAL '3 months' WHERE posto = $posto AND fabrica = $fabrica";
		$res = pg_query($con,$sql);

	}

} catch (Exception $e) {
	echo $e->getMessage();
}

