<?php
/**INICIO - Funções para o HD-2017979 da Esmaltec */
function isPostoTipoAtende($pedido=null){
    global $con;
    global $login_fabrica;
    global $login_posto;
    if(isset($pedido)){
        return getPostoTipoAtendeByPedido($pedido);
    }
    $sql = "SELECT tipo_atende FROM tbl_posto_fabrica where fabrica = $login_fabrica AND posto = $login_posto AND tipo_atende is true";
    $res = pg_query($con, $sql);
    if(strlen(pg_last_error($con)) > 0){
        throw new Exception("Erro ao selecionar tipo do posto (tipo atende)");
    }

    if(pg_num_rows($res) > 0  ){
       return true; 
    } 
    return false;
}

function getPostoTipoAtendeByPedido($pedido){
    global $con;
    global $login_fabrica;
    $sql = "SELECT tipo_atende 
        from tbl_pedido 
        INNER JOIN tbl_posto_fabrica ON tbl_pedido.fabrica = tbl_posto_fabrica.fabrica AND
                     tbl_pedido.posto = tbl_posto_fabrica.posto 
        WHERE pedido=$pedido AND tbl_posto_fabrica.tipo_atende is true";

    $res = pg_query($con, $sql);
    if(strlen(pg_last_error($con)) > 0){
        throw new Exception("Erro ao selecionar tipo do posto (tipo atende) pelo nro do pedido");
    }

    if(pg_num_rows($res) > 0  ){
       return true; 
    } 
    return false;


}
function savePedido($pedido){
    global $con;
    global $login_fabrica;
    $pedido[0]['condicao']              = ($pedido[0]['condicao'] ) ? $pedido[0]['condicao'] : 'NULL'; 
    $pedido[0]['pedido_cliente']        = ($pedido[0]['pedido_cliente'] ) ? "'".$pedido[0]['pedido_cliente']."'" : 'NULL' ;
    $pedido[0]['transportadora']        = ($pedido[0]['transportadora']) ? $pedido[0]['transportadora']:'NULL' ;
    $pedido[0]['linha']                 = ($pedido[0]['linha']) ? $pedido[0]['linha']:'NULL' ;
    $pedido[0]['tipo_pedido']           = ($pedido[0]['tipo_pedido']) ? $pedido[0]['tipo_pedido']:'NULL' ;
    $pedido[0]['digitacao_distribuidor']= ($pedido[0]['digitacao_distribuidor']) ? $pedido[0]['digitacao_distribuidor']:'NULL' ;
    $pedido[0]['obs']                   = ($pedido[0]['obs']) ? "'".$pedido[0]['obs']."'" :'NULL' ; 

    $pedido = array(
        "posto" => $pedido[0]['posto'],
        "fabrica" => $login_fabrica,
        "condicao" => $pedido[0]['condicao'],
        "pedido_cliente" => $pedido[0]['pedido_cliente'],
        "transportadora" => $pedido[0]['transportadora'],
        "linha" => $pedido[0]['linha'],
        "tipo_pedido" => $pedido[0]['tipo_pedido'],
        "digitacao_distribuidor" => $pedido[0]['digitacao_distribuidor'],
        "obs" => $pedido[0]['obs']
    );

    $novoPedido = "INSERT INTO tbl_pedido (
            posto          ,
            fabrica        ,
            condicao       ,
            pedido_cliente ,
            transportadora ,
            linha          ,
            tipo_pedido    ,
            digitacao_distribuidor,
            obs
       ) VALUES ("
       .	$pedido['posto']             ." ," 
       .	$pedido['fabrica']     ." ,"
       .	$pedido['condicao']      ." ,"
       .	$pedido['pedido_cliente']." ,"
       .	$pedido['transportadora']." ,"
       .	$pedido['linha']         ." ,"
       .	$pedido['tipo_pedido']   ." ,"
       .	$pedido['digitacao_distribuidor'].","
       .	$pedido['obs']."                      
    ) RETURNING pedido";

    $res = pg_query($con, $novoPedido);

    if(strlen(pg_last_error($con)) == 0 ){
       $pedido = pg_fetch_array($res);
       setSeuPedido($pedido['pedido'], array('F', $pedido['pedido']), '');
       if(strlen(pg_last_error($con)) == 0 ){
           return $pedido['pedido'];
       }
    }

    throw new Exception("Erro ao salvar novo pedido->".pg_last_error($con));
}
function saveItens($registros, $pedido){
    global $con;
    global $login_fabrica;
    foreach($registros as $row){
        $deletarPedidos[] = $row['pedido_item'];
        $sql = "INSERT INTO tbl_pedido_item (
                                                pedido  ,
                                                peca    ,
                                                qtde
                                            ) VALUES (
                                                $pedido     ,
                                            ".    $row['peca']."   ,
                                            ".    $row['qtde'] ." )";
        $res = pg_query($con, $sql);

        if(strlen(pg_last_error($con)) > 0 ){
            throw new Exception ("Erro ao salvar itens do pedido");
        }
        $sql = "SELECT fn_valida_pedido_item ($pedido,".$row['peca'].",$login_fabrica)";
        $res = pg_exec($con,$sql);
        $msg_erro = pg_errormessage($con);
        if(strlen($msg_erro) > 0 ){

            throw new Exception($msg_erro);
        }
    }
    return $deletarPedidos;
}
function deletaItensPedido($arrItens){
    global $con;
    
    foreach($arrItens as $pedidoItem){
        $sql = "DELETE FROM tbl_pedido_item where pedido_item = $pedidoItem";

        $res = pg_query($con, $sql);
        if(strlen(pg_last_error($con)) > 0){
            throw new Exception("Erro ao Deletar itens do pedido");
        }

    }
}
function lancaPedido($registros){

    $newPedido = savePedido($registros);
    $deletarItens = saveItens($registros, $newPedido);
    deletaItensPedido($deletarItens);
    return $newPedido;
}
function getPecasNacionais($pedido){
    global $con;
    global $login_fabrica;
    $sql = "SELECT   tbl_pedido.pedido, 
                             tbl_peca.peca,
                             tbl_peca.origem, 
                             tbl_posto.posto , 
                             tbl_pedido.condicao , 
                             pedido_cliente , 
                             tbl_pedido.transportadora , 
                             linha , 
                             tipo_pedido , 
                             digitacao_distribuidor, 
                             tbl_pedido.obs,
                             tbl_pedido_item.qtde ,
                             tbl_pedido_item.pedido_item
            FROM tbl_pedido
            INNER JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_pedido.pedido
            INNER JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca
            INNER JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_pedido.posto AND
                        tbl_posto_fabrica.fabrica = tbl_pedido.fabrica
            INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
            WHERE tbl_pedido.fabrica = $login_fabrica AND
                  tbl_pedido.pedido = $pedido AND
                  tbl_posto_fabrica.tipo_atende is true AND
                  tbl_peca.origem = 'NAC'";
    $resPecasNacionais = pg_query($con, $sql);

    if(strlen(pg_last_error($con)) > 0){
        throw new Exception("Erro ao selecionar peças Não Nacionais");
    }
    $result = pg_fetch_all($resPecasNacionais);
    if($result === false){
        return array();
    }
    return $result; 
}
function getPecasNaoNacionaisPedido($pedido){
   global $con;
   global $login_fabrica;
   $sql = "SELECT   tbl_pedido.pedido, 
                             tbl_peca.peca,
                             tbl_peca.origem, 
                             tbl_posto.posto , 
                             tbl_pedido.condicao , 
                             pedido_cliente , 
                             tbl_pedido.transportadora , 
                             linha , 
                             tipo_pedido , 
                             digitacao_distribuidor, 
                             tbl_pedido.obs,
                             tbl_pedido_item.qtde ,
                             tbl_pedido_item.pedido_item
            FROM tbl_pedido
            INNER JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_pedido.pedido
            INNER JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca
            INNER JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_pedido.posto AND
                        tbl_posto_fabrica.fabrica = tbl_pedido.fabrica
            INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
            WHERE tbl_pedido.fabrica = $login_fabrica AND
                  tbl_pedido.pedido = $pedido AND
                  tbl_posto_fabrica.tipo_atende is true AND
                  tbl_peca.origem <> 'NAC'";

    $resPecasPedido= pg_query($con, $sql);
    if(strlen(pg_last_error($con)) > 0){
        throw new Exception("Erro ao selecionar peças Não Nacionais");
    }
    $result = pg_fetch_all($resPecasPedido);
    if($result === false){
        return array();
    }
    return $result;

}

function setSeuPedido($pedido, $arrSeuPedido, $separator){
    global $con;
    $seuPedido = implode($separator, $arrSeuPedido);
    $update = "UPDATE tbl_pedido SET seu_pedido = '".$seuPedido."' WHERE pedido = ". $pedido;
    $res = pg_query($con, $update);
    if(strlen(pg_last_error($con)) > 0) {
        throw new Exception("Erro ao preencher seu_pedido");
    }
}
function lancaNovoPedido($pedido){
    $pecasNacionais = getPecasNacionais($pedido);
    $pecasPedido  = getPecasNaoNacionaisPedido($pedido);
    if(count($pecasNacionais) == 0 && count($pecasPedido) == 0){
       return array(); 
    }elseif(count($pecasNacionais) == 0){
        setSeuPedido($pedido, array('T',$pedido),'');
        return array();
    }elseif(count($pecasPedido) == 0){
        setSeuPedido($pedido, array('F',$pedido), '');
        return array();
    }
    setSeuPedido($pedido, array('T',$pedido),'');

    return $pecasNacionais;
    
}

function verificaOrigemPecas($pedido){
    global $con;
    global $login_fabrica;
    $registros = lancaNovoPedido($pedido);
    if(count($registros) > 0){
        pg_query($con, "BEGIN TRANSACTION");
        return lancaPedido($registros);
    }

    return false;
    
}
/**FIM - Funções para o HD-2017979 da Esmaltec */


