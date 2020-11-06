<?php                                                                                                                     

if(!function_exists('movimentoEstoque')) {
	function movimentoEstoque($faturamento_item , $login_posto, $login_unico, $pedido) {
		global $con ;
		
		$sql = "SELECT peca FROM tbl_posto_estoque_movimento where faturamento_item = $faturamento_item "; 
		$res = pg_query($con, $sql);
		if(pg_num_rows($res) == 0) {
		$sql = "INSERT INTO tbl_posto_estoque_movimento(
			posto,
			peca,
			qtde_saida,
			faturamento,
			faturamento_item,
			tipo,
			data,
			login_unico)
			SELECT
			$login_posto,
			peca,
			qtde,
			faturamento,
			faturamento_item, 
			'SAIDA',
			now(),
				$login_unico
				FROM tbl_faturamento_item WHERE faturamento_item = $faturamento_item RETURNING qtde_saida,  peca; ";
			$res = pg_query($con, $sql) ;

			$qtde_saida = pg_fetch_result($res, 0 , 'qtde_saida') ;
			$peca      = pg_fetch_result($res, 0 , 'peca') ;

			if(!empty($pedido)) {
				$sql = "SELECT pedido_faturado FROM tbl_pedido JOIN tbl_tipo_pedido USING(tipo_pedido) where pedido = $pedido ";
				$res = pg_query($con, $sql) ;
				$pedido_faturado = pg_fetch_result($res, 0 , 'pedido_faturado') ;

			}else{
				return false;
			}
			$sql = "SELECT posto_estoque_movimento, qtde_entrada, qtde_usada, tbl_posto_estoque_movimento.faturamento_item from tbl_posto_estoque_movimento JOIN tbl_faturamento_item USING(faturamento, peca, faturamento_item) join tbl_faturamento using(faturamento)  WHERE peca = $peca and tbl_posto_estoque_movimento.posto = $login_posto
				AND qtde_entrada > 0 and (qtde_usada < qtde_entrada or qtde_usada isnull) order by emissao, faturamento " ;
			$res = pg_query($con, $sql);
			$total_saida = $qtde_saida;

			for($q = 0 ; $q < pg_num_rows($res) ; $q++) {
				$posto_estoque_movimento = pg_fetch_result($res, $q , 'posto_estoque_movimento') ;
				$qtde_entrada            = pg_fetch_result($res, $q , 'qtde_entrada') ;
				$qtde_usada              = pg_fetch_result($res, $q , 'qtde_usada') ;
				$faturamento_item_entrada = pg_fetch_result($res, $q , 'faturamento_item') ;
				$qtde_usada              = empty($qtde_usada) ? 0 : $qtde_usada;

				if($qtde_entrada >= $total_saida + $qtde_usada) {
					$qtde_saida = $total_saida;

					if($pedido_faturado == 't') {
						$usada_tipo = " , qtde_usada_venda = coalesce(qtde_usada_venda,0) + $qtde_saida ";
					}else{   
						$usada_tipo = " , qtde_usada_garantia = coalesce(qtde_usada_garantia,0) + $qtde_saida ";
					}

					$sqlU = "UPDATE tbl_posto_estoque_movimento SET qtde_usada = coalesce(qtde_usada,0) + $qtde_saida $usada_tipo  WHERE posto_estoque_movimento = $posto_estoque_movimento  ;  ";
					$resU = pg_query($con, $sqlU) ;

					return true;
				}else{
					$qtde_saida = $qtde_entrada - $qtde_usada;
					$total_saida -= $qtde_saida;

					if($pedido_faturado == 't') {
						$usada_tipo = " , qtde_usada_venda = coalesce(qtde_usada_venda,0) + $qtde_saida ";
					}else{
						$usada_tipo = " , qtde_usada_garantia = coalesce(qtde_usada_garantia,0) + $qtde_saida ";
					}

					$sqlU = "UPDATE tbl_posto_estoque_movimento SET qtde_usada = coalesce(qtde_usada,0) + $qtde_saida  $usada_tipo WHERE posto_estoque_movimento = $posto_estoque_movimento ;  ";
					$resU = pg_query($con, $sqlU) ;
					if($total_saida == 0) return true;
				}
			}
		}else{
			return true;
		}
	}
}                              
