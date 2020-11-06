<?php
    if(isset($os_fechar)){
        $os = $os_fechar;
    }

    $sql = "SELECT  tbl_os_produto.os, tbl_os_item.os_item, tbl_os_item.posto_i
                FROM tbl_os_extra
                inner join tbl_os_produto ON  tbl_os_produto.os = tbl_os_extra.os
                inner join tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto
                WHERE tbl_os_item.servico_realizado = 11165
                AND tbl_os_item.peca_obrigatoria is true
				AND tbl_os_produto.os = $os					";
	$resxx = pg_query($con, $sql);
    if(strlen(pg_last_error($con))> 0 ){
        $msg_erro = pg_last_error($con);
    }

    if(pg_num_rows($resxx)>0){
        $sql_item = "SELECT tbl_os_item.peca, tbl_os_item.qtde, tbl_os_item.custo_peca, tbl_os_item.os_item,tbl_os_item.posto_i,tbl_os_produto.os
                    from tbl_os_produto
                    inner join tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto
                    where tbl_os_item.fabrica_i = $login_fabrica
					AND tbl_os_item.peca_obrigatoria is true
					AND tbl_os_produto.os = $os
					and tbl_os_item.servico_realizado = 11165 ";
        $res_item = pg_query($con, $sql_item);
        if(strlen(pg_last_error($con))> 0 ){
            $msg_erro .= pg_last_error($con);
		}
		$faturamento = "";
		for($f= 0; $f<pg_num_rows($res_item); $f++){
			$faturamento =  "";
			$os_item = pg_fetch_result($res_item, $f, os_item);
            $qtde = pg_fetch_result($res_item, $f, qtde);
            $peca = pg_fetch_result($res_item, $f, peca);
			$os = pg_fetch_result($res_item, $f, 'os');
            $postoId = pg_fetch_result($res_item, $f, 'posto_i');
            $custo_peca = pg_fetch_result($res_item, $f, custo_peca);


			$sqlf = "SELECT faturamento FROM tbl_faturamento_item JOIN tbl_os_item USING(os_item) JOIN tbl_os_produto USING(os_produto) WHERE tbl_os_produto.os = $os ";
			$resf = pg_query($con,$sqlf);
			if(pg_num_rows($resf) == 0){
				$sql_insert = "INSERT INTO tbl_faturamento (fabrica, cfop, emissao, saida, total_nota, posto, obs) VALUES ($login_fabrica, '5949', now(), now(), '0' ,$postoId, 'OS: $os') returning faturamento";
				$res_insert = pg_query($con, $sql_insert);
				$faturamento = pg_fetch_result($res_insert, 0, faturamento);
			}else{
				$faturamento = pg_fetch_result($resf, 0 , 'faturamento');
			}

			if(strlen(pg_last_error($con))> 0 ){
				$msg_erro .= pg_last_error($con);
			}

			$sql = "SELECT faturamento_item FROM tbl_faturamento_item WHERE os_item = $os_item ";
			$resi = pg_query($con,$sql);
			if(strlen(pg_last_error($con))> 0 ){
                $msg_erro .= pg_last_error($con);
            }

			if(pg_num_rows($resi) == 0) {
				$sql_insert_item = "INSERT INTO tbl_faturamento_item (faturamento, peca, devolucao_obrig,  qtde, preco, os, os_item)
				VALUES ($faturamento, $peca, 't',  '$qtde', '$custo_peca', null, $os_item)";
				$res_insert_item = pg_query($con, $sql_insert_item);
			}
            if(strlen(pg_last_error($con))> 0 ){
                $msg_erro .= pg_last_error($con);
            }
        }
    }

?>
