<?php 

include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include_once 'funcoes.php';

    if($_POST['dados_pedido'] == true){

        $pedido = $_POST["pedido"];
        $fabrica = $_POST['fabrica'];
        $condicao = $_POST['condicao'];
        $posto = $_POST['posto'];

        $sql = "SELECT cnpj 
                FROM tbl_posto 
                WHERE posto = $posto
                ";
        $res_makita = pg_exec ($con,$sql);
        $cnpj = pg_result ($res_makita,0,0);

        $sql = "SELECT codigo_condicao 
                FROM tbl_condicao 
                WHERE condicao = $condicao ";
        $res_makita = pg_exec ($con,$sql);
        $condpg = pg_result ($res_makita,0,0);

        $res = pg_exec ($con,"BEGIN TRANSACTION");

        $sql_pedido = "SELECT   tbl_pedido_item.peca,
                                tbl_pedido_item.qtde, 
                                tbl_pedido_item.pedido_item,
                                tbl_peca.referencia,    
                                tbl_peca.descricao, 
                                tbl_peca.unidade,
                                tbl_posto.cnpj,
                                tbl_pedido.tipo_pedido
                    FROM tbl_pedido_item 
                    JOIN tbl_pedido ON tbl_pedido_item.pedido = tbl_pedido.pedido
                    INNER JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca AND tbl_peca.fabrica = $fabrica
                    JOIN tbl_posto ON tbl_pedido.filial_posto = tbl_posto.posto
                    where tbl_pedido_item.pedido = $pedido";
        $res_pedido = pg_query($con, $sql_pedido);

		for($i = 0; $i<pg_num_rows($res_pedido); $i++){
            $peca = pg_fetch_result($res_pedido, $i, 'peca');
            $pedido_item = pg_fetch_result($res_pedido, $i, 'pedido_item');
            $referencia = pg_fetch_result($res_pedido, $i, 'referencia');
            $descricao = pg_fetch_result($res_pedido, $i, 'descricao');
            $unidade  = pg_fetch_result($res_pedido, $i, 'unidade');
            $cnpjFilial  = pg_fetch_result($res_pedido, $i, 'cnpj');
            $qtde  = pg_fetch_result($res_pedido, $i, 'qtde');
            $tipo_pedido  = pg_fetch_result($res_pedido, $i, 'tipo_pedido');

			$itns_item[] = [
                				"referencia"=>trim($referencia),
                				"qtde"=>(int) $qtde,
                				"pedido_item"=>$pedido_item
							];

            $itensArray[]   = [
                                "codigo"=>trim($referencia),
                                "unidademedida"=>(empty($unidade)) ? 'PC' : $unidade,
                                "quantidade"=>(int) $qtde
                              ];
		}

        $resultImpostos = getImpostosPecas($cnpjFilial, $cnpj, $condpg,$itensArray, $tipo_pedido);

        if (!empty($resultImpostos)) {

            $resultImpostos = json_decode($resultImpostos, true);

  	        if (isset($resultImpostos["erro"]) || isset($resultImpostos["errorCode"]) || isset($resultImpostos["message"])) {
  	 		    $msg[]['erro'] = "Erro ao consultar impostos da Peça - $referencia";
            } else {
                $rows = [];
                $precoIten = '';
                $precoUnitLiq = '';

    			foreach ($resultImpostos as $k => $v) {
    				unset($rows);
    				$precoIten = '';
                    $ttlItem = 0;
                    $rows["log"]         = json_encode($v);
                    $rows["referencia"]  = trim($v["produto"]);
                    if (count($v["impostos"]) > 0) {
                        foreach ($v["impostos"] as $p => $imp) {
                            if (trim($imp["descr"]) == "COF (APUR)") {
                                $rows["COF"] = (float) trim($imp["valor"]);
                            } else if (trim($imp["descr"]) == "ICMS-ST") {
                                $rows["ST"] = (float) trim($imp["valor"]);
                            } else if (trim($imp["descr"]) == "PIS (APUR)") {
                                $rows["PIS"] = (float) trim($imp["valor"]);
                            } else {
                                $rows[trim($imp["descr"])] = (float) trim($imp["valor"]);
                            }
                        }
                    }
                    $rows["valorpedido"]    = ((float) trim($v["valornota"]) / trim($v["qtd"]));
                    $rows["precoUnitLiq"]   = (float) trim($v["valorunit"]); // preço unitario com imposto
                    $rows["valornota"]      = (float) trim($v["valornota"]);
                    $precoIten              = $rows["valorpedido"];
                    $precoUnitLiq           = $rows["precoUnitLiq"];
                    $rows["precoUnitImp"]   = $rows["valorpedido"];
    										
    				$pedido_item = '';
    				foreach($itns_item as $p => $vl) {
    					if ($vl['referencia'] == $rows['referencia'] && $vl['qtde'] == $v['qtd']) {
    						$pedido_item = $vl['pedido_item'];
    						unset($itns_item[$p]);
    						break;
    					}
    				}


    				if (!empty($precoIten)) {
    						$valoresAdd  = json_encode($rows);
                            $valoresAdd = str_replace('\\u', '\\\\u', $valoresAdd);

    						$sql_item = "UPDATE tbl_pedido_item SET preco = '".$precoIten."', preco_base = $precoUnitLiq, valores_adicionais = '$valoresAdd' WHERE pedido_item = $pedido_item  ";

    						$res_item = pg_query($con, $sql_item);
    						if(strlen(pg_last_error($con))>0){
    								$msg[]['erro'] = pg_last_error($con);
    						}
    				} else {
    						$msg[]['erro'] = "Preço do iten - $referencia não encontrado para a nova condição de pagamento.";
    				}
        		}
    	   }
        }

        $sql_ped = "UPDATE tbl_pedido SET condicao = $condicao WHERE pedido = $pedido and fabrica = $fabrica ";
        $res_ped = pg_query($con, $sql_ped);
        if(strlen(pg_last_error($con))>0){
            $msg[]['erro'] = pg_last_error($con);
        }

        if(count($msg) >0 ){
            $res = pg_exec ($con,"ROLLBACK TRANSACTION");
            echo json_encode(array('retorno' => 'erro' ,'msg' => utf8_encode("Falha ao calcular peças. ")));
        }else{
            $res = pg_exec ($con,"COMMIT TRANSACTION");    
            echo json_encode(array('retorno' => 'ok','msg' => utf8_encode("Peças calculadas com sucesso. ")));
        }      

}


    exit;


?>
