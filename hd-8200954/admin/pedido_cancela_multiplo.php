<?php
require_once 'dbconfig.php';
require_once 'includes/dbconnect-inc.php';

$admin_privilegios = 'call_center, gerencia';
require_once 'autentica_admin.php';

if($_POST["cancelar_pedidos"] == true){

    $msg_erro = "";
    $msg_sucesso = "";

    if(isset($_POST["item"])){

        $pedido = $_POST["pedido"];
        $os     = $_POST["os"];
        $peca   = $_POST["peca"];
        $motivo = $_POST["motivo"];

        require_once "./os_cadastro_unico/fabricas/{$login_fabrica}/classes/CancelarPedido.php";
        $cancelaPedidoClass = new CancelarPedido($login_fabrica);        
        
        if(strlen($os) > 0){

            if(strlen($peca) > 0){

                $cond_peca = " AND tbl_os_item.peca = {$peca} ";

            }

            $sql_dados = "SELECT
                                tbl_os_item.os_item,
                                tbl_os_item.peca,
                                tbl_os_item.pedido_item,
                                (tbl_pedido_item.qtde - (tbl_pedido_item.qtde_cancelada + tbl_pedido_item.qtde_faturada)) AS qtde
                          FROM tbl_os_item
                          INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                          INNER JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_os_item.pedido AND tbl_pedido_item.peca = tbl_os_item.peca
                          WHERE
                            tbl_os_produto.os = {$os}
                            $cond_peca";
            $res_dados = pg_query($con, $sql_dados);
            $num_dados = pg_num_rows($res_dados);

            for($i = 0; $i < $num_dados; $i++){

                $os_item     = pg_fetch_result($res_dados, $i, "os_item");
                $peca        = pg_fetch_result($res_dados, $i, "peca");
                $pedido_item = pg_fetch_result($res_dados, $i, "pedido_item");
                $qtde        = pg_fetch_result($res_dados, $i, "qtde");

                if($qtde > 0){

                    $retorno_cancelamento_send = $cancelaPedidoClass->cancelaPedidoItem($pedido, $pedido_item, $motivo);

                    if(!is_bool($retorno_cancelamento_send)){
                        $msg_erro .= "Erro ao enviar o pedido item {$pedido_item} do pedido {$pedido} à Mondial - ".utf8_decode($retorno_cancelamento_send)."<br />";
                    }else{
                        $sql_cancela = "SELECT fn_pedido_cancela_garantia_item(null, $login_fabrica, $pedido, $peca, $os_item, '$motivo', $login_admin, $qtde)";
                        $res_cancela = pg_query ($con, $sql_cancela);

                        if(strlen(pg_last_error($con)) > 0){
                            $msg_erro .= "Erro ao cancelar o pedido item {$pedido_item} do pedido <strong>{$pedido}</strong> - ".pg_last_error($con);
                        }
                    }

                }

				sleep(1); //hd-3668047
            }

            if(strlen($msg_erro) == 0){

                $sql = "SELECT fn_atualiza_status_pedido($login_fabrica, $pedido);";
                $res = pg_query($con,$sql);

                $msg_sucesso .= "O item do pedido <strong>{$pedido}</strong> foi cancelado com Sucesso! <br />";
            }

        }else{

            if(strlen($peca) > 0){

                $cond_peca = " AND tbl_pedido_item.peca = {$peca} ";

            }

            $sql_dados = "SELECT pedido_item, peca, (qtde - (qtde_cancelada + qtde_faturada)) AS qtde
                          FROM tbl_pedido_item
                          WHERE
                            pedido = {$pedido}
                            $cond_peca";
            $res_dados = pg_query($con, $sql_dados);
            $num_dados = pg_num_rows($res_dados);

            for($i = 0; $i < $num_dados; $i++){

                $pedido_item = pg_fetch_result($res_dados, $i, "pedido_item");
                $peca        = pg_fetch_result($res_dados, $i, "peca");
                $qtde        = pg_fetch_result($res_dados, $i, "qtde");

                if($qtde > 0){

                    $retorno_cancelamento_send = $cancelaPedidoClass->cancelaPedidoItem($pedido, $pedido_item, $motivo);

                    if(!is_bool($retorno_cancelamento_send)){
                        $msg_erro .= "Erro ao enviar o pedido item {$pedido_item} do pedido {$pedido} à Mondial - ".utf8_decode($retorno_cancelamento_send)."<br />";
                    }else{

                        $sql_cancela = "SELECT fn_pedido_cancela_gama(null, $login_fabrica, $pedido, $peca, $qtde, '$motivo', $login_admin)";
                        $res_cancela = pg_query ($con, $sql_cancela);

                        if(strlen(pg_last_error($con)) > 0){
                            $msg_erro .= "Erro ao cancelar o pedido item {$pedido_item} do pedido <strong>{$pedido}</strong> - ".pg_last_error($con);
                        }

                    }

                }

				sleep(1); //hd-3668047
            }

            if(strlen($msg_erro) == 0){

                $sql = "SELECT fn_atualiza_status_pedido($login_fabrica, $pedido);";
                $res = pg_query($con,$sql);

                $msg_sucesso .= "O pedido <strong>{$pedido}</strong> foi cancelado com Sucesso! <br />";
            }

        }

    }else{

        $pedidos_os  = $_POST["pedidos"];
        $motivo      = $_POST["motivo"];

        require_once "./os_cadastro_unico/fabricas/{$login_fabrica}/classes/CancelarPedido.php";
        $cancelaPedidoClass = new CancelarPedido($login_fabrica);

        list($pedido, $os) = explode("|", $pedidos_os);

        if(strlen($os) > 0){

            $sql_dados = "SELECT
                                tbl_os_item.os_item,
                                tbl_os_item.peca,
                                tbl_os_item.pedido_item,
                                (tbl_pedido_item.qtde - (tbl_pedido_item.qtde_cancelada + tbl_pedido_item.qtde_faturada)) AS qtde
                          FROM tbl_os_item
                          INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                          INNER JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_os_item.pedido AND tbl_pedido_item.peca = tbl_os_item.peca
                          WHERE
                            tbl_os_produto.os = {$os}";
            $res_dados = pg_query($con, $sql_dados);
            $num_dados = pg_num_rows($res_dados);

            for($i = 0; $i < $num_dados; $i++){

                $os_item     = pg_fetch_result($res_dados, $i, "os_item");
                $peca        = pg_fetch_result($res_dados, $i, "peca");
                $pedido_item = pg_fetch_result($res_dados, $i, "pedido_item");
                $qtde        = pg_fetch_result($res_dados, $i, "qtde");

                if($qtde > 0){

                    $retorno_cancelamento_send = $cancelaPedidoClass->cancelaPedidoItem($pedido, $pedido_item, $motivo);

                    if(!is_bool($retorno_cancelamento_send)){
                        $msg_erro .= "Erro ao enviar o pedido item {$pedido_item} do pedido {$pedido} à Mondial - ".utf8_decode($retorno_cancelamento_send)."<br />";
                    }else{
                        $sql_cancela = "SELECT fn_pedido_cancela_garantia_item(null, $login_fabrica, $pedido, $peca, $os_item, '$motivo', $login_admin, $qtde)";
                        $res_cancela = pg_query ($con, $sql_cancela);

                        if(strlen(pg_last_error($con)) > 0){
                            $msg_erro .= "Erro ao cancelar o pedido item {$pedido_item} do pedido <strong>{$pedido}</strong> - ".pg_last_error($con);
                        }
                    }
                }

				sleep(1); //hd-3668047
            }

            if(strlen($msg_erro) == 0){

                $sql = "SELECT fn_atualiza_status_pedido($login_fabrica, $pedido);";
                $res = pg_query($con,$sql);

                $msg_sucesso .= "O pedido <strong>{$pedido}</strong> foi cancelado com Sucesso! <br />";
            }

        }else{

            $sql_dados = "SELECT pedido_item, peca, (qtde - (qtde_cancelada + qtde_faturada)) AS qtde
                          FROM tbl_pedido_item
                          WHERE
                            pedido = {$pedido}";
            $res_dados = pg_query($con, $sql_dados);
            $num_dados = pg_num_rows($res_dados);

            for($i = 0; $i < $num_dados; $i++){

                $pedido_item = pg_fetch_result($res_dados, $i, "pedido_item");
                $peca        = pg_fetch_result($res_dados, $i, "peca");
                $qtde        = pg_fetch_result($res_dados, $i, "qtde");

                if($qtde > 0){

                    $retorno_cancelamento_send = $cancelaPedidoClass->cancelaPedidoItem($pedido, $pedido_item, $motivo);

                    if(!is_bool($retorno_cancelamento_send)){
                        $msg_erro .= "Erro ao enviar o pedido item {$pedido_item} do pedido {$pedido} à Mondial - ".utf8_decode($retorno_cancelamento_send)."<br />";
                    }else{

                        $sql_cancela = "SELECT fn_pedido_cancela_gama(null, $login_fabrica, $pedido, $peca, $qtde, '$motivo', $login_admin)";
                        $res_cancela = pg_query ($con, $sql_cancela);

                        if(strlen(pg_last_error($con)) > 0){
                            $msg_erro .= "Erro ao cancelar o pedido item {$pedido_item} do pedido <strong>{$pedido}</strong> - ".pg_last_error($con);
                        }

                    }

                }

            }

            if(strlen($msg_erro) == 0){

                $sql = "SELECT fn_atualiza_status_pedido($login_fabrica, $pedido);";
                $res = pg_query($con,$sql);

                $msg_sucesso .= "O pedido <strong>{$pedido}</strong> foi cancelado com Sucesso! <br />";
            }

        }

    }

    exit(json_encode(array("sucesso" => $msg_sucesso, "erro" => utf8_encode($msg_erro))));

}

function valida_date_maior($data1 = "", $data2 = ""){

    if(strlen($data1) > 0 && strlen($data2) > 0){

        list($d, $m, $a) = explode("/", $data1);
        $data1 = $a."-".$m."-".$d;

        list($d, $m, $a) = explode("/", $data2);
        $data2 = $a."-".$m."-".$d;

        if(strtotime($data2) < strtotime($data1)){
            return false;
        }

        return true;

    }

}

function data_limite($data1, $data2){

    list($dia, $mes, $ano) = explode("/", $data1);
    $data1 = $ano."-".$mes."-".$dia;

    list($dia, $mes, $ano) = explode("/", $data2);
    $data2 = $ano."-".$mes."-".$dia;

    $inicio = strtotime($data1." +3 months");
    $fim = strtotime($data2);
    // exit;
    return ((int)$inicio < (int)$fim) ? true : false;

    $inicio     = new DateTime($data1);
    $fim        = new DateTime($data2);
    $interval   = date_diff($inicio, $fim);

    $interval = $interval->format('%a');

    return ((int)$interval > 90) ? true : false;

}

$msg_erro = array();

if($btn_acao == "listar" || isset($_POST["gerar_excel"])){
    $posto_id           = $_POST["posto_id"];
    $produto_id         = $_POST["produto_id"];
    $peca_id            = $_POST["peca_id"];
    $cliente_id         = $_POST["cliente_id"];
    $codigo_posto       = $_POST["codigo_posto"];
    $descricao_posto    = $_POST["descricao_posto"];
    $estado             = $_POST["estado"];
    $data_inicial       = $_POST["data_inicial"];
    $data_final         = $_POST["data_final"];
    $pedido             = $_POST["pedido"];
    $tipo_pedido        = $_POST["tipo_pedido"];
    $cliente_cpf        = $_POST["cliente_cpf"];
    $cliente_nome       = $_POST["cliente_nome"];
    $cliente_estado     = $_POST["cliente_estado"];
    $peca_referencia    = $_POST["peca_referencia"];
    $peca_descricao     = $_POST["peca_descricao"];
    $pedido_os          = $_POST["pedido_os"];
    $produto_referencia = $_POST["produto_referencia"];
    $produto_descricao  = $_POST["produto_descricao"];
    $protocolo          = $_POST["protocolo"];
    $status_pedido      = $_POST["status_pedido"];

    $tipo_relatorio     = $_POST["tipo_relatorio"];

    /* echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    exit; */

    if(!isset($_POST["gerar_excel"])){

        if(strlen($pedido) == 0){

            if(empty($data_inicial)){
                $msg_erro["msg"][] = "Preencha os campos obrigatórios";
                $msg_erro["campos"][]   = "data_inicial";
            }

            if(empty($data_final)){
                $msg_erro["msg"][] = "Preencha os campos obrigatórios";
                $msg_erro["campos"][]   = "data_final";
            }

            if(strlen($data_inicial) > 0 && strlen($data_final) > 0){

                if(valida_date_maior($data_inicial, $data_final) == false){
                    $msg_erro["msg"][]      = "A data de Abertura Inicial é maior que a Abertura Final";
                    $msg_erro["campos"][]   = "data_inicial";
                }

                if(data_limite($data_inicial, $data_final) == true){
                    $msg_erro["msg"][]      = "O intervalo entre as datas não pode ser maior que 3 meses";
                    $msg_erro["campos"][]   = "data_final";
                }

            }

        }

    }

    if(count($msg_erro["msg"]) == 0){

        if(strlen($data_inicial) > 0 && strlen($data_final) > 0){

            list($dia, $mes, $ano) = explode("/", $data_inicial);
            $data_inicial_opt = $ano."-".$mes."-".$dia;

            list($dia, $mes, $ano) = explode("/", $data_final);
            $data_final_opt = $ano."-".$mes."-".$dia;

            $cond_data      = " AND tbl_pedido.data BETWEEN '{$data_inicial_opt} 00:00:00' AND '{$data_final_opt} 23:59:59' ";
            $cond_data_item = " AND tbl_pedido_item.data_item BETWEEN '{$data_inicial_opt} 00:00:00' AND '{$data_final_opt} 23:59:59' ";

        }

        if(strlen($produto_referencia) > 0 && strlen($produto_descricao) > 0){
            $cond_produto = " AND ref_produto = '{$produto_referencia}' ";
            $cond_produto_2 = " AND tbl_produto.referencia = '{$produto_referencia}' ";
        }

        if(strlen($pedido) > 0){
            $cond_pedido = " AND tbl_pedido.pedido = {$pedido} ";
        }

        if(strlen($tipo_pedido) > 0){
            $cond_tipo_pedido = " AND tbl_tipo_pedido.tipo_pedido = {$tipo_pedido} ";
        }

        if(strlen($codigo_posto) > 0){

            $sql_posto = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '{$codigo_posto}' AND fabrica = {$login_fabrica}";
            $res_posto = pg_query($con, $sql_posto);

            if(pg_num_rows($res_posto) > 0){

                $posto = pg_fetch_result($res_posto, 0, "posto");

                $cond_codigo_posto = " AND tbl_pedido.posto = {$posto} ";

            }

        }

        if(strlen($estado) > 0){
            $cond_estado = " AND tbl_posto_fabrica.contato_estado = '{$estado}' ";
        }

        if(strlen($peca_referencia) > 0){
            if(strlen($peca_id) == 0 && strlen($peca_referencia) > 0){

                $sql_peca = "SELECT peca FROM tbl_peca WHERE referencia = '{$peca_referencia}' AND fabrica = {$login_fabrica}";
                $res_peca = pg_query($con, $sql_peca);

                if(pg_num_rows($res_peca) > 0){
                    $peca_id = pg_fetch_result($res_peca, 0, "peca");
                }

            }
            $cond_peca = (strlen($peca_id)) ? " AND tbl_peca.peca = {$peca_id} " : "";
        }

        $operador = "<";

        if(count($status_pedido) > 0){
            $status_pedido = implode(",", $status_pedido);
			if(strlen(trim($status_pedido)) > 0) {
				$cond_status_pedido = " AND tbl_pedido.status_pedido IN({$status_pedido}) ";
			}

    	    if($status_pedido == 14){
    		    $cond_data      = " AND tbl_pedido_cancelado.data BETWEEN '{$data_inicial_opt} 00:00:00' AND '{$data_final_opt} 23:59:59' ";
    		    $cond_data_item = $cond_data;
    		    $join_cancelado = " INNER JOIN tbl_pedido_cancelado ON tbl_pedido_cancelado.pedido = tbl_pedido.pedido ";
    	    }
        }else{
            $cond_status_pedido = " AND tbl_pedido.status_pedido NOT IN(4,14)  ";
            $cond_itens = " AND tbl_pedido_item.qtde_cancelada = 0 AND tbl_pedido_item.qtde_faturada = 0";
        }

        if(strlen($protocolo) > 0){
            $cond_protocolo = " AND hd_chamado = {$protocolo} ";
        }

        if(strlen($pedido_os) > 0){
            $cond_os = " AND os = {$pedido_os} ";
        }

        if(strlen($cliente_cpf) > 0){
            $cliente_cpf = str_replace(array(".", "-"), "", $cliente_cpf);
            $cond_cliente_cpf = " AND cpf ILIKE '%{$cliente_cpf}%' ";
        }

        if(strlen($cliente_nome) > 0){
            $cond_cliente_nome = " AND nome ILIKE '%{$cliente_nome}%' ";
        }

        if($tipo_relatorio == "pedido"){

            $sql = "SELECT tbl_pedido.pedido,
                        tbl_pedido.data,
                        tbl_tipo_pedido.descricao AS tipo_pedido,
                        tbl_pedido.status_pedido,
                        tbl_pedido.posto,
                        tbl_posto.cnpj AS cpf,
                        tbl_posto_fabrica.codigo_posto || ' - ' || tbl_posto.nome AS nome,
                        tbl_posto_fabrica.contato_estado AS uf,
                        array_to_string(array_agg(tbl_peca.referencia || ' | ' || tbl_peca.descricao || ' | ' || tbl_pedido_item.qtde || ' | ' || tbl_peca.peca || ' | ' || tbl_pedido_item.qtde_faturada || ' | ' || tbl_pedido_item.qtde_cancelada),';') AS pecas
                        INTO TEMP temp_pedidos_{$login_admin}
                    FROM tbl_pedido
                    INNER JOIN tbl_pedido_item USING(pedido)
                    INNER JOIN tbl_tipo_pedido USING(tipo_pedido)
                    INNER JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca AND tbl_peca.fabrica = {$login_fabrica}
                    INNER JOIN tbl_posto ON tbl_posto.posto = tbl_pedido.posto
		    INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
		    {$join_cancelado}
                    WHERE
                        tbl_pedido.fabrica = {$login_fabrica}
                        AND tbl_pedido.finalizado IS NOT NULL
                        AND (tbl_pedido_item.qtde_cancelada + tbl_pedido_item.qtde_faturada) $operador tbl_pedido_item.qtde
                        $cond_data
                        $cond_pedido
                        $cond_status_pedido
                        $cond_peca
                        $cond_estado
                        $cond_tipo_pedido
                        $cond_codigo_posto
                    GROUP BY
                        tbl_pedido.pedido,
                        tbl_pedido.data,
                        tbl_tipo_pedido.descricao,
                        tbl_pedido.status_pedido,
                        tbl_pedido.posto,
                        tbl_posto.cnpj,
                        tbl_posto_fabrica.codigo_posto,
                        tbl_posto.nome,
                        tbl_posto_fabrica.contato_estado;

                    ALTER TABLE temp_pedidos_{$login_admin} add column hd_chamado int4;
                    ALTER TABLE temp_pedidos_{$login_admin} add column os int4;
                    ALTER TABLE temp_pedidos_{$login_admin} add column ref_produto text;
                    ALTER TABLE temp_pedidos_{$login_admin} add column desc_produto text;

                    UPDATE temp_pedidos_{$login_admin}
                    SET
                        hd_chamado = tbl_hd_chamado_extra.hd_chamado,
                        ref_produto = tbl_produto.referencia,
                        desc_produto = tbl_produto.descricao,
                        nome = tbl_hd_chamado_extra.nome,
                        cpf = tbl_hd_chamado_extra.cpf,
                        uf = tbl_cidade.estado
                    FROM tbl_hd_chamado_extra, tbl_cidade, tbl_produto
                    WHERE
                        temp_pedidos_{$login_admin}.pedido = tbl_hd_chamado_extra.pedido
                        AND tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
                        AND (
                                (tbl_hd_chamado_extra.produto = tbl_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica})
                                OR tbl_hd_chamado_extra.produto ISNULL
                            );

                    UPDATE temp_pedidos_{$login_admin}
                    SET
                        os = tbl_os_produto.os,
                        desc_produto = tbl_produto.descricao,
                        ref_produto = tbl_produto.referencia
                    FROM tbl_os_produto, tbl_os_item, tbl_produto
                    WHERE
                        temp_pedidos_{$login_admin}.pedido = tbl_os_item.pedido
                        AND tbl_os_item.os_produto = tbl_os_produto.os_produto
                        AND tbl_os_produto.produto = tbl_produto.produto
                        $cond_produto_2
                        AND tbl_os_item.fabrica_i = {$login_fabrica}
                        AND tbl_produto.fabrica_i = {$login_fabrica};

                    UPDATE temp_pedidos_{$login_admin}
                    SET
                        hd_chamado = tbl_hd_chamado_extra.hd_chamado
                    FROM tbl_hd_chamado,tbl_hd_chamado_extra
                    WHERE
                        temp_pedidos_{$login_admin}.os = tbl_hd_chamado_extra.os
                        AND tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
                        AND tbl_hd_chamado.fabrica = {$login_fabrica};

                    SELECT * FROM temp_pedidos_{$login_admin} WHERE 1 = 1 $cond_produto $cond_protocolo $cond_os $cond_cliente_cpf $cond_cliente_nome;

                    ";

        }else{

            $sql = "SELECT
                        tbl_pedido_item.pedido_item,
                        tbl_pedido.pedido,
                        tbl_pedido.data,
                        tbl_tipo_pedido.descricao AS tipo_pedido,
                        tbl_pedido.status_pedido,
                        tbl_pedido.posto,
                        tbl_posto.cnpj AS cpf,
                        tbl_posto_fabrica.codigo_posto || ' - ' || tbl_posto.nome AS nome,
                        tbl_posto_fabrica.contato_estado AS uf,
                        tbl_peca.referencia,
                        tbl_peca.descricao,
            			tbl_pedido_item.qtde,
            			tbl_pedido_item.qtde_faturada,
            			tbl_pedido_item.qtde_cancelada,
                        tbl_peca.peca
                        INTO TEMP temp_pedidos_{$login_admin}
                    FROM tbl_pedido_item
                    INNER JOIN tbl_pedido USING(pedido)
                    INNER JOIN tbl_tipo_pedido USING(tipo_pedido)
                    INNER JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca AND tbl_peca.fabrica = {$login_fabrica}
                    INNER JOIN tbl_posto ON tbl_posto.posto = tbl_pedido.posto
        		    INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
        		    {$join_cancelado}
                    WHERE
                        tbl_pedido.fabrica = {$login_fabrica}
                        AND tbl_pedido.finalizado IS NOT NULL
                        AND (tbl_pedido_item.qtde_cancelada + tbl_pedido_item.qtde_faturada) $operador tbl_pedido_item.qtde
                        $cond_data_item
                        $cond_pedido
                        $cond_status_pedido
                        $cond_peca
                        $cond_estado
                        $cond_tipo_pedido
                        $cond_itens
                        $cond_codigo_posto;

                    ALTER TABLE temp_pedidos_{$login_admin} add column hd_chamado int4;
                    ALTER TABLE temp_pedidos_{$login_admin} add column os int4;
                    ALTER TABLE temp_pedidos_{$login_admin} add column ref_produto text;
                    ALTER TABLE temp_pedidos_{$login_admin} add column desc_produto text;

                    UPDATE temp_pedidos_{$login_admin}
                    SET
                        hd_chamado = tbl_hd_chamado_extra.hd_chamado,
                        ref_produto = tbl_produto.referencia,
                        desc_produto = tbl_produto.descricao,
                        nome = tbl_hd_chamado_extra.nome,
                        cpf = tbl_hd_chamado_extra.cpf,
                        uf = tbl_cidade.estado
                    FROM tbl_hd_chamado_extra, tbl_cidade, tbl_produto
                    WHERE
                        temp_pedidos_{$login_admin}.pedido = tbl_hd_chamado_extra.pedido
                        AND tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
                        AND (
                                (tbl_hd_chamado_extra.produto = tbl_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica})
                                OR tbl_hd_chamado_extra.produto ISNULL
                            );

                    UPDATE temp_pedidos_{$login_admin}
                    SET
                        os = tbl_os_produto.os,
                        desc_produto = tbl_produto.descricao,
                        ref_produto = tbl_produto.referencia
                    FROM tbl_os_produto, tbl_os_item, tbl_produto
                    WHERE
                        temp_pedidos_{$login_admin}.pedido = tbl_os_item.pedido
                        AND tbl_os_item.os_produto = tbl_os_produto.os_produto
                        AND tbl_os_produto.produto = tbl_produto.produto
                        $cond_produto_2
                        AND tbl_os_item.fabrica_i = {$login_fabrica}
                        AND tbl_produto.fabrica_i = {$login_fabrica};

                    UPDATE temp_pedidos_{$login_admin}
                    SET
                        hd_chamado = tbl_hd_chamado_extra.hd_chamado
                    FROM tbl_hd_chamado,tbl_hd_chamado_extra
                    WHERE
                        temp_pedidos_{$login_admin}.os = tbl_hd_chamado_extra.os
                        AND tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
                        AND tbl_hd_chamado.fabrica = {$login_fabrica};

                    SELECT * FROM temp_pedidos_{$login_admin} WHERE 1 = 1 $cond_produto $cond_protocolo $cond_os $cond_cliente_cpf $cond_cliente_nome;

                    ";

        }
        #echo nl2br($sql);exit;
        $res_pedidos = pg_query($con, $sql);
        $num_pedidos = pg_num_rows($res_pedidos);

        if(isset($_POST["gerar_excel"])){

            if($num_pedidos > 0){

                $sql_status_pedido = "SELECT status_pedido, descricao FROM tbl_status_pedido";
                $res_status_pedido = pg_query($con, $sql_status_pedido);

                $status_pedido_arr = pg_fetch_all($res_status_pedido);

                $file     = "xls/relatorio-pedidos-{$login_fabrica}.xls";
                $fileTemp = "/tmp/relatorio-pedidos-{$login_fabrica}.xls" ;
                $fp       = fopen($fileTemp,'w');

                if($tipo_relatorio == "pedido"){

                    $head = "
                    <table border='1'>
                        <thead>
                            <tr bgcolor='#596D9B'>
                                <th><font color='#FFFFFF'>Pedido</font></th>
                                <th><font color='#FFFFFF'>Data Abertura</font></th>
                                <th><font color='#FFFFFF'>Tipo Pedido</font></th>
                                <th><font color='#FFFFFF'>Status Pedido</font></th>
                                <th><font color='#FFFFFF'>CPF/CNPJ</font></th>
                                <th><font color='#FFFFFF'>Cód. Posto</font></th>
                                <th><font color='#FFFFFF'>Nome</font></th>
                                <th><font color='#FFFFFF'>Estado</font></th>
                                <th><font color='#FFFFFF'>Peças</font></th>
                                <th><font color='#FFFFFF'>Protocolo</font></th>
                                <th><font color='#FFFFFF'>OS</font></th>
                                <th><font color='#FFFFFF'>Ref. Produto</font></th>
                                <th><font color='#FFFFFF'>Desc. Produto</font></th>
                            </tr>
                        </thead>
                        <tbody>";
                    fwrite($fp, $head);

                    for ($i = 0; $i < $num_pedidos; $i++) {

                        $pedido        = pg_fetch_result($res_pedidos, $i, "pedido");
                        $data          = pg_fetch_result($res_pedidos, $i, "data");
                        $tipo_pedido   = pg_fetch_result($res_pedidos, $i, "tipo_pedido");
                        $status_pedido = pg_fetch_result($res_pedidos, $i, "status_pedido");
                        $posto         = pg_fetch_result($res_pedidos, $i, "posto");
                        $cpf           = pg_fetch_result($res_pedidos, $i, "cpf");
                        $codigo_posto  = pg_fetch_result($res_pedidos, $i, "codigo_posto");
                        $nome          = pg_fetch_result($res_pedidos, $i, "nome");
                        $uf            = pg_fetch_result($res_pedidos, $i, "uf");
                        $pecas         = pg_fetch_result($res_pedidos, $i, "pecas");
                        $hd_chamado    = pg_fetch_result($res_pedidos, $i, "hd_chamado");
                        $os            = pg_fetch_result($res_pedidos, $i, "os");
                        $ref_produto   = pg_fetch_result($res_pedidos, $i, "ref_produto");
                        $desc_produto  = pg_fetch_result($res_pedidos, $i, "desc_produto");

                        $pecas = explode(";", $pecas);

                        $table_pecas = "<table>
                                            <thead>
                                                <tr>
                                                    <th>Referência</th>
                                                    <th>Descrição</th>
						    <th>Qtde</th>
						    <th>Qtde Faturada</th>
						    <th>Qtde Cancelada</th>
						    <th>Pendência do Pedido</th>
                                                </tr>
                                            </thead>
                                            <tbody>";

                        foreach ($pecas as $key => $value) {

            				list($ref, $desc, $qtde, $peca, $qtde_faturada, $qtde_cancelada) = explode(" | ", $value);
            				$pendencia = $qtde - ($qtde_faturada + $qtde_cancelada);

                            $table_pecas .= "<tr>
                                                <td>{$ref}</td>
                                                <td>{$desc}</td>
                        						<td>{$qtde}</td>
                        						<td>{$qtde_faturada}</td>
                        						<td>{$qtde_cancelada}</td>
                        						<td>{$pendencia}</td>
                                            </tr>";

                        }

                        $table_pecas .= "</tbody></table>";

                        foreach ($status_pedido_arr as $key => $value) {
                            if($value["status_pedido"] == $status_pedido){
                                $status_pedido = $value["descricao"];
                                continue;
                            }
                        }


                        list($data, $mls) = explode(".", $data);
                        list($data, $hora) = explode(" ", $data);
                        list($a, $m, $d) = explode("-", $data);
                        $data = $d."/".$m."/".$a." ".$hora;

                        if(strlen($cpf) >= 14){
                            $dados_posto = explode(" - ", $nome);
                            $cod_posto = $dados_posto[0];
                            $nome = $dados_posto[1];
                            $nome = (isset($dados_posto[2])) ? $nome." ".$dados_posto[2] : $nome;
                        }

                        $tbody = '<tr>
                            <td>'.$pedido.'</td>
                            <td>'.$data.'</td>
                            <td>'.$tipo_pedido.'</td>
                            <td>'.$status_pedido.'</td>
                            <td>'.$cpf.'</td>
                            <td>'.$cod_posto.'</td>
                            <td>'.$nome.'</td>
                            <td>'.$uf.'</td>
                            <td>'.$table_pecas.'</td>
                            <td>'.$hd_chamado.'</td>
                            <td>'.$os.'</td>
                            <td>'.$ref_produto."</td>
                            <td>".$desc_produto.'</td>
                        </tr>';

                        fwrite($fp, $tbody);

                    }

                }else{

                    $head = "
                    <table border='1'>
                        <thead>
                            <tr bgcolor='#596D9B'>
                                <th><font color='#FFFFFF'>Pedido</font></th>
                                <th><font color='#FFFFFF'>Data Abertura</font></th>
                                <th><font color='#FFFFFF'>Tipo Pedido</font></th>
                                <th><font color='#FFFFFF'>Status Pedido</font></th>
                                <th><font color='#FFFFFF'>CPF/CNPJ</font></th>
                                <th><font color='#FFFFFF'>Cód. Posto</font></th>
                                <th><font color='#FFFFFF'>Nome</font></th>
                                <th><font color='#FFFFFF'>Estado</font></th>
                                <th><font color='#FFFFFF'>Referência da Peça</font></th>
                                <th><font color='#FFFFFF'>Descrição da Peça</font></th>
				<th><font color='#FFFFFF'>Quantidade</font></th>
				<th><font color='#FFFFFF'>Qtde Faturada</font></th>
				<th><font color='#FFFFFF'>Qtde Cancelada</font></th>
				<th><font color='#FFFFFF'>Pendência do Pedido</font></th>
                                <th><font color='#FFFFFF'>Protocolo</font></th>
                                <th><font color='#FFFFFF'>OS</font></th>
                                <th><font color='#FFFFFF'>Ref. Produto</font></th>
                                <th><font color='#FFFFFF'>Desc. Produto</font></th>
                            </tr>
                        </thead>
                        <tbody>";
                    fwrite($fp, $head);

                    for ($i = 0; $i < $num_pedidos; $i++) {

                        $pedido_item     = pg_fetch_result($res_pedidos, $i, "pedido_item");
                        $pedido          = pg_fetch_result($res_pedidos, $i, "pedido");
                        $data            = pg_fetch_result($res_pedidos, $i, "data");
                        $tipo_pedido     = pg_fetch_result($res_pedidos, $i, "tipo_pedido");
                        $status_pedido   = pg_fetch_result($res_pedidos, $i, "status_pedido");
                        $posto           = pg_fetch_result($res_pedidos, $i, "posto");
                        $cpf             = pg_fetch_result($res_pedidos, $i, "cpf");
                        $codigo_posto    = pg_fetch_result($res_pedidos, $i, "codigo_posto");
                        $nome            = pg_fetch_result($res_pedidos, $i, "nome");
                        $uf              = pg_fetch_result($res_pedidos, $i, "uf");
                        $peca            = pg_fetch_result($res_pedidos, $i, "peca");
                        $referencia_peca = pg_fetch_result($res_pedidos, $i, "referencia");
                        $descricao_peca  = pg_fetch_result($res_pedidos, $i, "descricao");
            			$qtde_peca       = pg_fetch_result($res_pedidos, $i, "qtde");
            			$qtde_faturada   = pg_fetch_result($res_pedidos, $i, "qtde_faturada");
            			$qtde_cancelada  = pg_fetch_result($res_pedidos, $i, "qtde_cancelada");
                        $hd_chamado      = pg_fetch_result($res_pedidos, $i, "hd_chamado");
                        $os              = pg_fetch_result($res_pedidos, $i, "os");
                        $ref_produto     = pg_fetch_result($res_pedidos, $i, "ref_produto");
                        $desc_produto    = pg_fetch_result($res_pedidos, $i, "desc_produto");

                        foreach ($status_pedido_arr as $key => $value) {
                            if($value["status_pedido"] == $status_pedido){
                                $status_pedido = $value["descricao"];
                                continue;
                            }
                        }

                        list($data, $mls) = explode(".", $data);
                        list($data, $hora) = explode(" ", $data);
                        list($a, $m, $d) = explode("-", $data);
                        $data = $d."/".$m."/".$a." ".$hora;

                        if(strlen($cpf) >= 14){
                            $dados_posto = explode(" - ", $nome);
                            $cod_posto = $dados_posto[0];
                            $nome = $dados_posto[1];
                            $nome = (isset($dados_posto[2])) ? $nome." ".$dados_posto[2] : $nome;
                        }

			$pendencia = $qtde_peca - ($qtde_faturada + $qtde_cancelada);

                         $tbody = '<tr>
                            <td>'.$pedido.'</td>
                            <td>'.$data.'</td>
                            <td>'.$tipo_pedido.'</td>
                            <td>'.$status_pedido.'</td>
                            <td>'.$cpf.'</td>
                            <td>'.$cod_posto.'</td>
                            <td>'.$nome.'</td>
                            <td>'.$uf.'</td>
                            <td>'.$referencia_peca.'</td>
                            <td>'.$descricao_peca.'</td>
			    <td>'.$qtde_peca.'</td>
			    <td>'.$qtde_faturada.'</td>
			    <td>'.$qtde_cancelada.'</td>
			    <td>'.$pendencia.'</td>
                            <td>'.$hd_chamado.'</td>
                            <td>'.$os.'</td>
                            <td>'.$ref_produto."</td>
                            <td>".$desc_produto.'</td>
                        </tr>';

                        fwrite($fp, $tbody);

                    }

                }

                fwrite($fp, '</tbody></table>');
                fclose($fp);

                if(file_exists($fileTemp)){
                    system("mv $fileTemp $file");

                    if(file_exists($file)){
                        echo $file;
                    }
                }

                exit;

            }

        }

    }

}

include_once 'funcoes.php';
include_once '../helpdesk/mlg_funciones.php';
include_once '../class/fn_sql_cmd.php';

// variáveis do programa
// tipos Pedido, pode ter que mudar para um SELECT no banco
define('REQ_FIELDS', 'tbl_pedido_item.pedido, tbl_pedido.fabrica');

error_reporting(E_ERROR);

/**
 * Procura chaves no array $haystack que tenham o valor
 * ou valores do $needle: valor do needle nas CHAVES do
 * HAYSTACK.
 * $needle pode ser um JSON (irá buscar os VALORES),
 * um CSV ou um único string.
 * Retorna um array com as CHAVES (como value) se encontrou
 * ou null se não.
 */
function array_key_search($keys, array $haystack) {
    if (!is_array($keys)) {
        if (is_string($keys)) {
            if (!$needle = json_decode($keys, true)) {
                if (strpos($keys, ',')) {
                    $needle = split(',', $keys);
                } else {
                    $needle = (array)$keys;
                }
            }
        } else {
            return null;
        }
    } else
        $needle = $keys;
    return array_intersect((array)$needle, array_keys($haystack));
}

/**
 * formata CNPJ ou CPF para saída
 */
$fmtCPFJ = function($str) {
    if (!defined('RE_FMT_CNPJ'))
        throw new Exception('Erro no programa.');

    $str = preg_replace('/\D/', '', $str);
    if (strlen($str)<10 or strlen($str)>14)
        return false;

    if (strlen($str) == 10 or strlen($str) == 13)
        $str = '0'.$str;

    return strlen($str) > 11 ?
        preg_replace(RE_FMT_CNPJ, '$1.$2.$3/$4-$5', $str) :
        preg_replace(RE_FMT_CPF,  '$1.$2.$3-$4', $str);
};

function getCliente($_DATA, $utf8 = false) {
    global $con, $estados_BR, $fmtCPFJ;
    $where = array_key_search($_DATA,array('cpf','nome','estado','cep','codigo_cliente'));

    // Validações
    if (isset($where['cpf'])) {
        $cpf = preg_replace('/\D/', '', $_DATA['cpf']);
        if (strlen($cpf) < 10)
            $cpf .= '%';
        $where['cpf'] = $cpf;
    }

    if (isset($where['estado'])) {
        if (!in_array($_DATA['estado'], $estados_BR))
            unset($where['estado']);
    }

    if (isset($where['nome'])) {
    if (strlen($where['nome']))
        $where['nome'] .= '%';
    }

    $res = pg_query($con, sql_cmd('tbl_cliente', 'cliente', $where));
    if (!$res)
        die('Erro ao consultar o cliente');

    if (!pg_num_rows($res))
        return null;

    return pg_fetch_result($res, 0, 'cliente');
}

/**
 * filtra, valida e formata os valores do POST
 * Filtros:
 * OK Data Inicial e Final (Abertura do Pedido) 3 meses no maximo, obrigatório
 * OK Código e Descrição Posto Autorizado
 * Código e Descrição Peça(Deixar pesquisar produto acabado no caso de troca)
 * OK CNPJ/CPF e Descrição do posto
 * OK Número do Pedido
 * Número do Protocolo(hd_chamado)
 * OK Número da OS
 * OK UF
 * OK Tipo de Pedido (OT, CT, VT, TT)
 * Campo checkbox - Cancelados /Gerar Excel
 * botão Excel e Pesquisar
 *
 * obs:apenas numero de pedido, protocolo e os pode pesquisar sem data, o resto é obrigatório
 */
function prepareUserData($_DATA, $toLatin1 = false) {
    if (!is_array($_DATA))
        return false;
    global $con, $login_fabrica, $estados_BR, $status_pedido, $tipos_pedido, $msg_erro;

    /**
     * Filtros para pesquisa
     */
    if (!empty($_DATA['data_inicial']) or !empty($_DATA['data_final'])) {
        $data_inicial = isset($_DATA['data_inicial']) ?
            is_date($_DATA['data_inicial']) :
            is_date('hoje -3 meses');

        $data_final = isset($_DATA['data_final']) ?
            is_date($_DATA['data_final']) :
            (empty($_DATA['data_inicial']) ?
                is_date('amanha -1 segundo') :
                is_date("$data_inicial +3 meses  +1 dia -1 segundo")
            );
        // Caso as datas tenham vindo invertidas...
        if ($data_inicial > $data_final)
            list($data_inicial, $data_final) = array($data_final, $data_inicial);

        // Valida período máximo de 3 meses entre datas
        if (is_date("$data_inicial +3 meses") < $data_final)
            $msg_erro['msg'][] = "O intervalo de datas ($data_inicial - $data_final) não pode ser maior que 3 meses.";

        $userData = array(
            'os.os'                => $_DATA['os_pedido'],
            'p.data::DATE'         => "$data_inicial::$data_final",
            'p.pedido'             => $_DATA['id_pedido'],
            'p.tipo_pedido'        => array_key_search($_DATA['tipo_pedido'], $tipos_pedido),
            'p.status_pedido'      => array_key_search($_DATA['status_pedido'], $status_pedido),
            'hdx.hd_chamado'       => $_DATA['protocolo'],
            'pf.codigo_posto'      => $_DATA['codigo_posto'],
            'pa.estado'            => $_DATA['estado'],
        );

        // Se tem um ID de pedido, OS ou atendimento, desconsidera a data. ??
        if (!empty($_DATA['pedido']) or is_numeric($_DATA['os_pedido']) or !empty($_DATA['protocolo'])) {
            unset ($userData['p.data::DATE']);
        }

        /**
         * Filtro por estado do consumidor
         */
        if ($clEstado = $_DATA['cliente_estado']) {
            $userData['os_estado'] = array(
                'cl.estado'  => $clEstado,
                '@os.consumidor_estado' =>  $clEstado,
            );
        }

        /**
         * Procura o ID do posto
         */
        if (empty($_DATA['posto']) and ($_DATA['posto_codigo'] or $_DATA['posto_cnpj'])) {
            $sql = sql_cmd(
                array('tbl_posto', 'JOIN tbl_posto_fabrica USING(posto, fabrica)'),
                'posto, tbl_posto.nome, tbl_posto_fabrica.codigo_posto',
                array(
                    'codigo' => array(
                        'tbl_posto_fabrica.codigo_posto' => $_DATA['posto_codigo'],
                        '@tbl_posto.cnpj' => preg_replace('/\D/', '', $_DATA['posto_cnpj'])
                    ),
                    'tbl_posto_fabrica.fabrica' => $login_fabrica,
                    '!tbl_posto_fabrica.credenciamento' => 'DESCREDENCIADO'
                )
            );

            if ($sql[0] == 'S' and pg_num_rows($res = pg_query($con, $sql))) {
                $userData['posto'] = pg_fetch_result($res, 0, 'posto');
            } else {
                if (DEV_ENV)
                    pre_echo($sql, 'CONSULTA POSTO', true);
                $msg_erro['msg'][] = "Posto informado não existe ou não está credenciado!";
            }
        }

        $pecas = array();
        if ($_DATA['peca'] or $_DATA['peca_referencia']) {
            if (is_numeric($_DATA['peca_id'])) {
                $pecas[] = $_DATA['peca_id'];
            } else {
                $sql = sql_cmd(
                    'tbl_peca', 'peca',
                    array(
                        'referencia' => $_DATA['peca_referencia'],
                        'fabrica'    => $login_fabrica
                    )
                );
                $peca = pg_fetch_result(pg_query($con, $sql), 0, 'peca');
                if ($peca) {
                    $pecas[] = $peca;
                } else {
                    if (DEV_ENV)
                        pre_echo($sql,'CONSUSLTA PEÇA', true);
                    $msg_erro['msg'][] = "Peça Ref.: {$_DATA['peca_referencia']} não encontrada!";
                }
            }
        }

        /**
         * O filtro será sobre o "produto" no pedido_item, portanto, devemos buscar a
         * referência do produto e buscar a peca-produto_acabado para filtrar pedido_item
         * com essa "peça". Agora buscamos a "peça" pela OS ou pelo protocolo de atendimento
         */
        if ($os = $_DATA['pedido_os']) {
            // Vê se é uma OS de troca
            $selProdTroca = sql_cmd(
                'tbl_os_troca', 'peca',
                array(
                    'fabric' => $login_fabrica,
                    'os' => $os
                )
            );

            $res = pg_query($con, $selProdTroca);
            if (pg_num_rows($res))
                $pecas[] = pg_fetch_result($res, 0, 'peca');

            // e pode ser qualquer OS, então
            $userData['op.os'] = $os;
        }

        if ($protocolo = $_DATA['protocolo']) {
            $selProdHd = sql_cmd(
                array(
                    'tbl_hd_chamado_extra AS hd',
                    'JOIN tbl_produto     AS pt USING(produto)',
                    'JOIN tbl_peca        AS pc ON pc.referencia = pt.referencia '.
                    " AND pc.fabrica = $login_fabrica"
                ),
                'peca',
                array(
                    'hd.hd_chamado' => $protocolo
                )
            );

            $res = pg_query($con, $sql);

            if (pg_num_rows($res))
                $pecas[] = pg_fetch_result($res, 0, 'peca');
        }

        // Se informou o produto, buscar ele como produto acabado na tbl_peca
        if ($_DATA['produto_id'] or $produto_referencia = $_DATA['produto_referencia']) {
            if (is_numeric($_DATA['produto_id'])) {
                $selRefProd = sql_cmd(
                    'tbl_produto',
                    'referencia',
                    array(
                        'produto'   => $_DATA['produto_id'],
                        'fabrica_i' => $login_fabrica
                    )
                );
                $res = pg_query($con, $selRefProd);
                if (!pg_num_rows($res)) {
                    $msg_erro['msg'][] = 'Produto não encontrado!';
                    $msg_erro['campo'][] = 'Referência do produto';
                } else {
                    $produto_referencia = pg_fetch_result($res, 0, 'referencia');
                }
            }
            $sql = sql_cmd(
                'tbl_peca', 'peca',
                array(
                    'referencia'      => $produto_referencia,
                    'produto_acabado' => true,
                    'fabrica'         => $login_fabrica
                )
            );

            $peca = pg_fetch_result(pg_query($con, $sql), 0, 'peca');
            if ($peca) {
                $pecas[] = $peca;
            } else {
                if (DEV_ENV)
                    pre_echo($sql,'CONSUlTA PEÇA', true);
                $msg_erro['msg'][] = "Peça Ref.: {$_DATA['peca_referencia']} não encontrada!";
            }
        }

        $userData['pi.peca'] = $pecas;

        // O cliente deve ser preenchido por AJAX, tem que conter o ID...
        if (!empty($_DATA['cliente']))
            $userData['os.cliente'] = (int)$_DATA['cliente'];
        /// ...mas, pode vir o CPF.
        else if ($_DATA['cliente_cpf']) {
            $cliente = getCliente(array('cpf'=>$_DATA['cliente_cpf']));
            if (!is_null($cliente)) {
                $userData['os.cliente'] = $cliente;
            }
        }

        /**
         * se não tem status, qlquer um menos 'Faturado Total' ou 'Cancelado'
         */
        if ((!$_DATA['status_pedido'])) {
            unset($userData['p.status_pedido']);
            $userData['!p.status_pedido'] = array(4, 14);
        }

        $acoes = array(
            'gerar_excel' => ($_DATA['excel'] == 't'),
        );

        if ($toLatin1 and !is_null(json_encode($userData, true)))
            $userData = array_map('utf8_decode', $userData);

        $userData = array('filtros' => $userData, 'acoes' => $acoes);

        return array_filter($userData);
    }
    /**
     * Dados para a requisição de cancelamento do pedido
     */
    if (isset($_DATA['acao']) and $_DATA['acao'] === 'excluir') {

        $ret = array();

        foreach($pedidos as $idx=>$pedido_item) {
            $pedido = is_numeric($pedido_item['pedido']) ? (int)$pedido_item['pedido'] : null;
            $posto  = is_numeric($pedido_item['posto'])  ? (int)$pedido_item['posto']  : null;
            $item   = is_numeric($pedido_item['item'])   ? (int)$pedido_item['item']   : null;
            $qtde   = is_numeric($pedido_item['qtde'])   ? (int)$pedido_item['qtde']   : null;

            if (is_null($pedido))
                $ret[$idx]['msg_erro'][] = "O PEDIDO {$pedido_item['pedido']} é inválido!";

            if (strlen($motivo = $pedido_item['motivo']) < 3)
                $ret[$idx]['msg_erro'][] = 'MOTIVO é obrigatório';

            if (!$peca and (is_null($qtde) or $qtde < 1))
                $ret[$idx]['msg_erro'][] = 'Informe a QUANTIDADE a ser cancelada do produto' . $produto_referencia;

            if (!$produto and (is_null($qtde) or $qtde < 1))
                $ret[$idx]['msg_erro'][] = 'Informe a QUANTIDADE a ser cancelada da peça ' . $peca_referencia;

            $ret[$idx] = array(
                'pedido_item' => $pedido,
                'quantidade'  => $qtde,
                'posto'       => $posto,
                'motivo'      => $pedido_item['motivo']
            );
        }
        return $ret;
    }
    return null;
}

/**
 * Formata para saída os dados do banco, registro por registro.
 * Está numa função para poder fazer a manipulação mais visível.
 */
function formatResult(array $data) {
    global $estadosBrasil, $fmtCPFJ;

    $chkItem = '<input type="checkbox" class="checkbox %2$s" name="pedido[]" value="%1$s" title="Cancelar pedido %1$s" %3$s />'; // class id_pedido, extra, disabled
    $urlPedido  = '<a title="Consultar pedido %1$s" href="pedido_admin_consulta.php?pedido=%1$s" target="_blank">%1$s</a>';
    $urlChamado = '<a title="Consultar Atendimento %1$s" href="callcenter_interativo_new.php?callcenter=%1$s" target="_blank">%1$s</a>';
    $urlOS      = '<a title="Consultar OS %1$s" href="os_press.php?os=%1$s" target="_blank">%1$s</a>';

    if (!count($data))
        return null;

    foreach ($data as $idx=>$row)
        $result[] = array(
            '<i class="icon-check" id="checkall"></i>' => sprintf($chkItem, $row['pedido'],(strpos($row['situacao_pedido'], 'Canc')!==false ? 'disabled' : ''), ''),
            'Data'        => is_date(substr($row['data'], 0, 10), 'ISO', 'EUR'),
            'Pedido'      => array(
                sprintf($urlPedido, $row['pedido']),
                $row['situacao_pedido']
            ),
            'Posto Autorizado' => array(
                'cnpj' => $fmtCPFJ($row['cnpj']),
                'nome' => $row['nome_posto']
            ),
            'Cliente' => array(
                'CPF/CNPJ' => $fmtCPFJ((substr($row['cliente_nome'], 0, 10) == '(REVENDA) ') ? $row['revenda_cnpj'] : $row['cliente_cpf']),
                'Nome'     => $row['cliente_nome']
            ),
            'Protocolo'   => $row['hd_chamado'] ? sprintf($urlChamado, $row['hd_chamado']) : '&mdash;',
            'OS'          => $row['OS'] ? array(sprintf($urlOS, $row['OS']), ($row['consumidor_revenda']=='R')?'Revenda':'Consumidor') : '&mdash;',
            'Peça/Produto' => array(
                'referencia' => $row['peca_referencia'],
                'descricao' => $row['peca_descricao']
            ),
            'Qtde.'       => "<span class='text-default quantidade' data-pedido='{$row['pedido']}' data-item='{$row['pedido_item']}' data-qtde='{$row['qtde']}'>{$row['qtde']}</span>",
            'Tipo Pedido' => strtoupper($row['tipo_pedido']),
            'Estado'      => "<span title='".$estadosBrasil[$row['estado']]."'>{$row['estado']}</span>"
        );
    return $result;
}

function getPedido($pedido = null) {
    global $con, $login_fabrica;

    if (!is_null($pedido)) {
        if (is_array($pedido) and count($pedido) != count(array_filter($pedido, 'is_numeric')))
            return false;

        if (!is_array($pedido) and !is_numeric($pedido))
            return false;
    }

    if (!is_resource($con))
        throw new Exception('Sem conexão ao banco de dados!');

    $post = prepareUserData($_POST);
    $filtros = array_filter($post['filtros']);

    if (!$filtros and !$pedido)
        return null;

    if ($pedido and !isset($filtros['p.pedido']))
        $filtros['p.pedido'] = $pedido;

    $filtros['p.fabrica'] = $login_fabrica;

    $sql = sql_cmd(
        array(
            'tbl_pedido AS p',
            '     JOIN tbl_status_pedido    AS sp  USING (status_pedido)',
            '     JOIN tbl_tipo_pedido      AS tp  USING (tipo_pedido,fabrica)',
            '     JOIN tbl_pedido_item      AS pi  USING (pedido)',
            '     JOIN tbl_posto            AS pa  USING (posto)',
            '     JOIN tbl_peca             AS pc  USING (peca,fabrica)',
            'LEFT JOIN tbl_posto_fabrica    AS pf  USING (posto,fabrica)',
            'LEFT JOIN tbl_os_item          AS oi  USING (pedido_item)',
            'LEFT JOIN tbl_produto          AS pr  ON    pr.produto = p.produto',
            'LEFT JOIN tbl_os_produto       AS op  USING (os_produto)',
            'LEFT JOIN tbl_os               AS os  USING (os)',
            'LEFT JOIN tbl_os_troca         AS ot  USING (os)',
            'LEFT JOIN tbl_cliente          AS cl  USING (cliente)',
            'LEFT JOIN tbl_hd_chamado_extra AS hdx ON    hdx.pedido = p.pedido'
        ),
        // Info Posto Autorizado
        'codigo_posto, pa.cnpj, pa.nome AS nome_posto, COALESCE(pf.contato_estado, pa.estado) AS estado, '.
        // Peça ou Produto
        'pr.referencia AS produto_referencia, pr.descricao AS produto_nome, '.
        'pc.referencia AS peca_referencia, pc.descricao AS peca_descricao, pi.qtde, '.
        // Informações do pedido
        'p.pedido, p.data, tp.descricao AS tipo_pedido, sp.descricao AS situacao_pedido, pi.qtde, pi.pedido_item, '.
        // cliente
        'COALESCE(cl.cpf, os.consumidor_cpf, hdx.cpf) AS cliente_cpf, os.revenda_cnpj, '.
        'CASE WHEN LENGTH(TRIM(cl.nome)) > 0 THEN cl.nome '.
        '     WHEN os.consumidor_revenda = $$R$$ AND LENGTH(TRIM(os.revenda_nome)) > 0 THEN $$(REVENDA) $$||os.revenda_nome '.
        '     WHEN LENGTH(TRIM(os.consumidor_nome)) > 0 THEN os.consumidor_nome '.
        '     WHEN LENGTH(TRIM(hdx.nome)) > 0 THEN hdx.nome '.
        'ELSE \'\' END AS cliente_nome, '.
        'COALESCE(cl.nome, os.consumidor_nome, hdx.nome) AS cliente_nome_old, '.
        'COALESCE(cl.estado, os.consumidor_estado) AS cliente_estado, ' .
        // Objetos relacionados: OS, atendimento, tipo de OS, etc.
        'hdx.hd_chamado, os.consumidor_revenda, COALESCE (op.os, ot.os, hdx.os, NULL) AS "OS"',
        $filtros
    ) .
    "\n  ORDER BY tipo_pedido, data, codigo_posto, peca_referencia ";

    if ($_GET['debug'] == 'sql')
        pre_echo($sql, 'Consulta PEDIDOS', isset($_GET['stop']));

    /*
     */
        pre_echo($_POST, 'USER DATA');
        pre_echo($filtros, 'FILTRADO');
        pre_echo($sql, 'Consulta PEDIDOS', isset($_GET['stop']));

    if ($sql[0] == 'S') {
        $res = pg_query($con, $sql);
        if (pg_last_error($con))
            if (DEV_ENV)
                die(pg_last_error() . PHP_EOL . $sql);
            else
                return 'Erro ao recuperar as informações do pedido '.$pedido;
        return pg_num_rows($res) ? pg_fetch_all($res) : array();
    }
    if (!DEV_ENV)
        return false;
}

$tipos_pedido = explode(',', 'OT,CT,VT,TT');
$tipos_pedido = pg_fetch_pairs(
    $con, sql_cmd(
        'tbl_tipo_pedido',
        'tipo_pedido,descricao',
        array(
            'fabrica' => $login_fabrica,
            'ativo' => true
        )
    )
);

$status_pedido = pg_fetch_pairs(
    $con, sql_cmd(
        array('tbl_pedido', 'JOIN tbl_status_pedido USING(status_pedido)'),
        'DISTINCT status_pedido,descricao',
        array(
            'fabrica' => $login_fabrica,
            '!status_pedido' => 4,
            'data>' =>  is_date('2016-01-01')
        )
    ) . ' ORDER BY status_pedido'
);

/* if (count($_POST) and $btn_acao == 'listar') {
    if (count($_POST)) {
        $_RESULT = $_POST;
        // pre_echo($post  = prepareUserData($_POST), 'DADOS PROCESSADOS');
        // pre_echo($where = array_filter($post['filtros']), 'DADOS FILTRADOS');
        // pre_echo(sql_where($where), 'CLÁUSULA WHERE');
    }

    $pedidos = formatResult(getPedido());
    if (is_null($pedidos))
        $msg_erro['msg'][] = "Sem resultados para estes parâmetros";
} else if (isset($_GET['pedido'])) {
    $pedidos = getPedido(getPost('pedido'));
}

if (is_string($pedidos))
    $msg_erro['msg'] = $pedidos;

if (count($msg_erro['msg']))
    unset($pedidos); */

$title = $titulo = 'CANCELAMENTO DE PEDIDOS';
$layout = 'callcenter';

include 'cabecalho_new.php';

?>
<div class="row"> <b class="obrigatorio pull-right">  * Campos obrigatórios </b> </div>
<?php

$plugins = array('shadowbox', 'autocomplete', 'datepicker', 'mask', 'dataTable', 'multiselect');
include 'plugin_loader.php';

// Começa a tela
?>
<style type="text/css">
    /* table#pedidos {
        width: 90%;
        margin: auto;
        margin-bottom: 3em;
    }
    #pedidos th {
        text-align: left!important;
        font-size:0.8em;
        vertical-align: bottom;
    }
    #pedidos input[type=number] {text-align: right}
    #pedidos input[type=number],
    #pedidos input[type=text] {
        height: auto;
    } */
    /* table#pedidos th:nth-child(1) {width: 1em}   */ /* Coluna: checkbox */
    /* table#pedidos th:nth-child(2) {width: 60px}  */ /* Coluna: Data     */
    /* table#pedidos th:nth-child(4) {width: 15%}   */ /* Coluna: Posto    */
    /* table#pedidos th:nth-child(5) {width: 15%}   */ /* Coluna: Cliente  */
    /* table#pedidos th:nth-child(6) {width: 50px}  */ /* Coluna: HD       */
    /* table#pedidos th:nth-child(7) {width: 60px}  */ /* Coluna: OS       */
    /* table#pedidos th:nth-child(8) {width: 15%}   */ /* Coluna: Peça     */
    /* table#pedidos th:nth-child(9) {width: 110px} */ /* Coluna: Qtde     */

    /* Coluna: checkbox, Pedido, Protocolo, OS, quantidade */
    /* table#pedidos td:nth-child(1),
    table#pedidos td:nth-child(2),
    table#pedidos td:nth-child(3),
    table#pedidos td:nth-child(6),
    table#pedidos td:nth-child(7),
    table#pedidos td:nth-child(9) {
        text-align: right;
        font-family: monospace;
    } */

    /* Coluna: data, tipo, estado */
    /* table#pedidos td:nth-child(2),
    table#pedidos td:nth-child(3),
    table#pedidos td:nth-child(10),
    table#pedidos td:nth-child(11) {
        text-align: center;
    } */

    /* table#pedidos td:nth-child(2) {text-align: center;font-family: inherit;} */ /* Coluna: Posto */
    /* table#pedidos td:nth-child(3) {text-align: right;font-family: inherit;;color:grey} */ /* Coluna: Posto */
    /* table#pedidos td:nth-child(7) {text-align: right;font-family: inherit;;color:grey} */ /* Coluna: Posto */
    /* table#pedidos td:nth-child(3)::first-line,
    table#pedidos td:nth-child(4)::first-line,
    table#pedidos td:nth-child(5)::first-line,
    table#pedidos td:nth-child(7)::first-line,
    table#pedidos td:nth-child(8)::first-line {font-family: monospace;color:black;} */ /* Coluna: Posto, CNPJ */
</style>
<?php if ($msg_success) { ?>
    <div class="alert alert-success">
        <h4>Pedido(s) cancelado(s)</h4>
        <?=$msg_success?>
    </div>
<?php }

if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php }

// Formulário de pesquisa
$form = array(
    'hidden' => array(
        'posto_id'   => array('value' => ($posto)   ?  : $_POST['posto']),
        'produto_id' => array('value' => ($produto) ?  : $_POST['produto']),
        'peca_id'    => array('value' => ($peca)    ?  : $_POST['peca']),
        'cliente_id' => array('value' => ($cliente) ?  : $_POST['cliente'])
    ),
    'campos' => array(
        'data_inicial' => array(
            'label' => 'Data Inicial',
            'type' => 'input/text',
            'required' => true,
            'extra' => array(
                'value' => $_POST["data_inicial"] /* ($_POST['data_inicial']) ? : is_date('hoje -1 semana', '', 'EUR') */,
                'align' => 'right'
            ),
            'span' => 2
        ),
        'data_final' => array(
            'label' => 'Data Final',
            'type' => 'input/text',
            'required' => true,
            'extra' => array(
                'value' => $_POST["data_final"] /* ($_POST['data_final']) ? : is_date('hoje', '', 'EUR') */,
                'align' => 'right',
            ),
            'span' => 2
        ),
        'pedido' => array(
            'label' => 'Núm. de Pedido',
            'type' => 'input/text',
            'span' => 2,
            'extra' => array(
                'pattern' => '\d{1,12}',
                'align'=>'right'
            )
        ),
        'tipo_pedido' => array(
            'label' => 'Tipo de Pedido',
            'type' => 'select',
            'options' => $tipos_pedido,
            'span' => 2,
        ),
        'codigo_posto' => array(
            'label' => 'Código do Posto',
            'type' => 'input/text',
            'maxlength' => 30,
            'span' => 2,
            'width' => 10,
            'lupa' => array(
                'name' => 'lupa',
                'tipo' => 'posto',
                'parametro' => 'codigo'
            ),
        ),
        'descricao_posto' => array(
            'label' => 'Razão Social',
            'type' => 'input/text',
            'maxlength' => 100,
            'span' => 4,
            'extra' => array(
                'placeholder' => 'Nome do Posto'
            ),
            'width' => 11,
            'lupa' => array(
                'name' => 'lupa',
                'tipo' => 'posto',
                'parametro' => 'nome'
            ),
        ),
        'estado' => array(
            'label' => 'Estado',
            'type' => 'select',
            'options' => $estadosBrasil,
            'extra' => array('class'=>'"multiselect"'),
            'span' => 2
        ),
        'cliente_cpf' => array(
            'label' => 'CPF / CNPJ Cliente',
            'type' => 'input/text',
            'maxlength' => 18,
            'span' => 2,
            'width' => 10,
            'lupa' => array(
                'name' => 'lupa',
                'tipo' => 'consumidor',
                'parametro' => 'cnpj'
            )
        ),
        'cliente_nome' => array(
            'label' => 'Nome ou Razão Social',
            'type' => 'input/text',
            'maxlength' => 100,
            'span' => 4,
            'extra' => array(
                'placeholder' => 'Nome do cliente'
            ),
            'width' => 11,
            'lupa' => array(
                'name' => 'lupa',
                'tipo' => 'consumidor',
                'parametro' => 'nome_consumidor'
            ),
        ),
        'cliente_estado' => array(
            'label' => 'Estado',
            'type' => 'select',
            'options' => $estadosBrasil,
            'extra' => array('class'=>'"multiselect"'),
            'span' => 2
        ),
        'peca_referencia' => array(
            'label' => 'Código da Peça',
            'type' => 'input/text',
            'maxlength' => 30,
            'span' => 2,
            'width' => 10,
            'lupa' => array(
                'name' => 'lupa',
                'tipo' => 'peca',
                'parametro' => 'referencia'
            ),
        ),
        'peca_descricao' => array(
            'label' => 'Descrição da Peça',
            'type' => 'input/text',
            'maxlength' => 60,
            'span' => 4,
            'width' => 11,
            'lupa' => array(
                'name' => 'lupa',
                'tipo' => 'peca',
                'parametro' => 'descricao'
            ),
        ),
        'pedido_os' => array(
            'label' => 'Ordem de Serviço',
            'type' => 'input/text',
            'extra' => array('pattern' => '\d{1,12}'),
            'maxlength' => 12,
            'span' => 2
        ),
        'produto_referencia' => array(
            'label' => 'Código do Produto',
            'type' => 'input/text',
            'maxlength' => 30,
            'span' => 2,
            'width' => 10,
            'lupa' => array(
                'name' => 'lupa',
                'tipo' => 'produto',
                'parametro' => 'referencia'
            ),
        ),
        'produto_descricao' => array(
            'label' => 'Nome do Produto',
            'type' => 'input/text',
            'maxlength' => 60,
            'span' => 4,
            'width' => 11,
            'lupa' => array(
                'name' => 'lupa',
                'tipo' => 'produto',
                'parametro' => 'descricao',
            )
        ),
        'protocolo' => array(
            'label' => 'Protocolo',
            'type' => 'input/text',
            'extra' => array(
                'pattern' => '\d{1,12}',
                'placeholder'=>'Atendimento'
            ),
            'maxlength' => 10,
            'span' => 2
        ),
        'status_pedido' => array(
            'label' => 'Situação do Pedido',
            'type' => 'checkbox',
            'checks' => $status_pedido,
            'span' => 3
        ),
        /* 'gerar_excel' => array(
            'label' => 'Ação',
            'type' => 'checkbox',
            'checks' => array('t'=>'Gerar Excel?'),
            'span' => 2
        ) */
    )
);

// pre_echo($form['hidden'], 'HIDDEN FIELDS', true);

?>
<script>
    $(function() {

        Shadowbox.init();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });

        $("[name^=data]").mask('99/99/9999');

        $.autocompleteLoad(
            Array("produto", "peca", "posto", "cliente"),
            Array("produto", "peca", "posto", "cliente")
        );

        $.datepickerLoad(Array("data_inicial", "data_final"));

        $("#checkall").click(function() {
            if ($(this).hasClass('icon-check')) {
                $("#pedidos input:checkbox").prop('checked', true).click();
                $(this).removeClass('icon-check')
                    .addClass('icon-remove-circle');
            } else {
                $("#pedidos input:checkbox").prop('checked', false);
                $(this).removeClass('icon-remove-circle')
                    .addClass('icon-check');
            }
        });

        /* $("#pedidos input:checkbox").click(function() {
            var mostra = $(this).is(':checked');
            var tdQtde = $(this).parents('tr').find('.quantidade').parent(); // TD que contém a quantidade
            var span   = $(tdQtde).find('span'); // SPAN com a quantidade e os dados para cancelar o pedido

            if (!mostra) {
                $(tdQtde).find(".div-cancela").remove();
                $(span).show();
            } else {
                var dados  = $(span).data();
                console.log(dados);
                $(span).hide();
                var div  = '<div class="div-cancela form row-fluid">';
                    div +='<input type="text" class="input-small qtde_cancela" value="'+dados.qtde+'" min="1" max="'+dados.qtde+'" required /><br />';
                    div += '<input type="text"  class="input-small motivo_cancela" required placeholder="Motivo" />';

                $(tdQtde).append(div);
            }
        });*/

    });

    function retorna_produto(json){

        $("#produto_referencia").val(json.referencia);
        $("#produto_descricao").val(json.descricao);

    }

    function retorna_peca(json){

        $("#peca_referencia").val(json.referencia);
        $("#peca_descricao").val(json.descricao);

    }

    function retorna_posto(json){

        $("#codigo_posto").val(json.codigo);
        $("#descricao_posto").val(json.nome);

    }

    function retorna_consumidor(retorno) {

        $("#consumidor_nome").val(retorno.nome);
        $("#consumidor_cpf").val(retorno.cpf);

    }

    function check_all(){
        /*
        var linhas = dataTableGlobal._fnGetTrNodes();
        if($(".check_all").is(":checked")){
            $(linhas).find("input[type=checkbox]").prop("checked",true);
        }else{
            $(linhas).find("input[type=checkbox]").prop("checked",false);
        }
        */

        /*  hd_chamado=3097736
            Alterada a forma de cancelamento dos pedidos, deve selecionar
            apenas os pedidos que estiverem em tela, e não selecionar todos
            como estava fazendo.
        */
        if($(".check_all").is(":checked")){
            $("#listagem").find("input[type='checkbox']").each(function(){
                $(this).prop("checked", true);
            });
        }else{
            $("#listagem").find("input[type='checkbox']").each(function(){
                $(this).prop("checked", false);
            });
        }
    }

    function cancela_pedido_item(pedido, os, peca){

        var motivo = (peca == "") ? $("#motivo_"+pedido).val() : $("#motivo_"+pedido+"_"+peca).val();

        if(motivo == ""){

            if(peca == ""){
                alert("Por favor insira o Motivo do Cancelamento para o Pedido - "+pedido);
                $("#motivo_"+pedido).focus();
                return;
            }else{
                alert("Por favor insira o Motivo do Cancelamento do item do Pedido - "+pedido);
                $("#motivo_"+pedido+"_"+peca).focus();
                return;
            }

        }

        $.ajax({
            url: "<?php echo $_SERVER['PHP_SELF']; ?>",
            type: "post",
            async: false,
            data: {
                cancelar_pedidos: true,
                item: true,
                pedido: pedido,
                os: os,
                peca: peca,
                motivo: motivo
            },
            beforeSend: function(){

                if(peca == ""){
                    $(".loading-item-"+pedido).html("<em>Cancelando, por favor aguarde...</em>");
                    $(".btn-cancela-item-"+pedido).prop({disabled: true});
                }else{
                    $(".loading-item-"+pedido+"-"+peca).html("<em>Cancelando, por favor aguarde...</em>");
                    $(".btn-cancela-item-"+pedido+"-"+peca).prop({disabled: true});
                }

            }
        }).always(function(data){

            if(peca == ""){
                $(".loading-item-"+pedido).html("");
                $("#motivo_"+pedido).val("");
            }else{
                $(".loading-item-"+pedido+"-"+peca).html("");
                $("#motivo_"+pedido+"-"+peca).val("");
            }

            // $(".retorno").html("");

            data = JSON.parse(data);

            if(data.sucesso != ""){
                alert("O pedido "+pedido+" foi cancelado com sucesso!");
                //  $(".retorno").append("<div class='alert alert-success'>"+data.sucesso+"</div>");
                if(peca == ""){
                    $(".tr_"+pedido).remove();
                }else{
                    $(".tr_"+pedido+"_"+peca).remove();
                }
            }

            if(data.erro != ""){
                $(".retorno").append("<div class='alert alert-danger'>"+data.erro+"</div>");
            }

            if(peca == ""){
                $(".btn-cancela-item-"+pedido).prop({disabled: false});
            }else{
                $(".btn-cancela-item-"+pedido+"-"+peca).prop({disabled: false});
            }

        });

    }

    function cancela_pedidos_all(){

        var motivo = $("#motivo_geral").val();
        if(motivo == ""){
            alert("Por favor insira o Motivo do Cancelamento paras os Pedidos!");
            $("#motivo_geral").focus();
            return;
        }

        $("#listagem").find("input[type='checkbox']").each(function(){
            $(this).prop("checked", true);
        });

        var pedidos = [];
        //var linhas = dataTableGlobal._fnGetTrNodes();

        /*  hd_chamado=3097736
            Alterada a forma de cancelamento dos pedidos, deve selecionar
            apenas os pedidos que estiverem em tela, e não selecionar todos
            como estava fazendo.
        */
        var linhas = $("#listagem tbody > tr > td"); //hd_chamado=3097736
        linhas_check = $(linhas).find("input[type=checkbox]");

        $($(linhas_check)).each(function(i){
            if($(this).is(":checked")){
                $('.check_all').prop("checked", false);
                pedidos.push($(this).val());
            }
        });

        if(pedidos.length == 0){
            alert("Não há pedidos selecionados para cancelar!");
            return;
        }

        var pedidos_cancelados = 0;
        var pedidos_enviados = 0;
        var pedidos_total = pedidos.length;
        var pedidos_arr = [];
        var total_itens = 0;

        $(".loading").html("<em>Cancelando, por favor aguarde...</em> (<strong>Cancelado 0 de "+pedidos_total+"</strong>)");
        $(".btn-cancela-all").prop({disabled: true});

        //Inico hd_chamado=3097736
            for(var x = 0; x < pedidos.length; x++){
                var itens_peidos = [];
                itens_peidos = pedidos[x].split("|");
                var qtde_itens = itens_peidos[2];
                total_itens += parseInt(qtde_itens);
            }

            if(total_itens > 2000){
                var texto = "isso pode demorar varios minutos por favor aguarde...";
            }else{
                var texto = "isso pode demorar alguns minutos por favor aguarde...";
            }
        // Fim hd_chamado=3097736
        for(var i = 0; i < pedidos.length; i++){
            var pedido_os = [];
            pedido_os = pedidos[i].split("|");
            var pedido = pedido_os[0];
            var os = pedido_os[1];
            var pedidos_send = pedidos[i];
            pedidos_arr.push(pedido);

            $.ajax({
                url: "<?php echo $_SERVER['PHP_SELF']; ?>",
                type: "post",
                data: {
                    cancelar_pedidos: true,
                    pedidos: pedidos_send,
                    motivo: motivo
                },
            }).always(function(data){

                data = JSON.parse(data);
                pedidos_enviados += 1;

                if(data.sucesso != ""){
                    pedidos_cancelados += 1;
                    $(".loading").html("<em>Existem "+total_itens+" itens referente aos pedidos selecionados. Cancelando, "+texto+"</em> (<strong>Cancelado "+pedidos_cancelados+" de "+pedidos_total+"</strong>)");
                }

                if(data.erro != ""){
                    $(".retorno").append("<div class='alert alert-danger'>"+data.erro+"</div>");
                }

                if(pedidos_enviados == pedidos_total){
                    $(".btn-cancela-all").prop({disabled: false});
                    $(".loading").html("");
                    $("#motivo_geral").val("");

                    for(var i = 0; i < pedidos_arr.length; i++){

                        $("#listagem").dataTable().$("tr.tr_"+pedidos_arr[i]).remove();

                    }

                    $(".retorno").append("<div class='alert alert-success'><h4>Pedidos cancelados com sucesso!</h4></div>");

                    setTimeout(function(){
                        $(".alert-success").remove();
                    }, 10000);

                }

            });

        }

        /* $.ajax({
            url: "<?php echo $_SERVER['PHP_SELF']; ?>",
            type: "post",
            async: true,
            data: {
                cancelar_pedidos: true,
                pedidos: pedidos,
                motivo: motivo
            },
            beforeSend: function(){
                $(".loading").html("<em>Cancelando, por favor aguarde...</em>");
                $(".btn-cancela-all").prop({disabled: true});
            }
        }).always(function(data){

            $(".loading").html("");
            $("#motivo_geral").val("");

            $(".retorno").html("");

            data = JSON.parse(data);

            if(data.sucesso != ""){
                $(".retorno").append("<div class='alert alert-success'>"+data.sucesso+"</div>");

                for(var i = 0; i < pedidos.length; i++){

                    var pedido_os = [];
                    pedido_os= pedidos[i].split("|");

                    $(".tr_"+pedido_os[0]).remove();

                }

                setTimeout(function(){
                    $(".retorno").html("");
                }, 10000);

            }

            if(data.erro != ""){
                $(".retorno").append("<div class='alert alert-danger'>"+data.erro+"</div>");
            }

            $(".btn-cancela-all").prop({disabled: false});

        }); */

    }

    function cancela_pedidos_itens_all(){

        var motivo = $("#motivo_geral").val();
        if(motivo == ""){
            alert("Por favor insira o Motivo do Cancelamento paras os Itens dos Pedidos!");
            $("#motivo_geral").focus();
            return;
        }

        var pedidos = [];
        /*  hd_chamado=3097736
            Alterada a forma de cancelamento dos pedidos, deve selecionar
            apenas os pedidos que estiverem em tela, e não selecionar todos
            como estava fazendo.

            //var linhas = dataTableGlobal._fnGetTrNodes();
        */
        var linhas = $("#listagem tbody > tr > td"); //hd_chamado=3097736
        linhas_check = $(linhas).find("input[type=checkbox]");

        $($(linhas_check)).each(function(){
            if($(this).is(":checked")){
                pedidos.push($(this).val());
            }
        });

        if(pedidos.length == 0){
            alert("Não há pedidos selecionados para cancelar!");
            return;
        }

        var pedidos_cancelados = 0;
        var pedidos_enviados = 0;
        var pedidos_total = pedidos.length;
        var pedidos_arr = [];

        $(".loading").html("<em>Cancelando, por favor aguarde...</em> (<strong>Cancelado 0 de "+pedidos_total+"</strong>)");
        $(".btn-cancela-all").prop({disabled: true});

        for(var i = 0; i < pedidos.length; i++){

            var pedido_os = [];
            pedido_os = pedidos[i].split("|");

            var pedido = pedido_os[0];
            var os = pedido_os[1];
            var peca = pedido_os[2];

            pedidos_arr.push(pedido+"_"+peca);

            $.ajax({
                url: "<?php echo $_SERVER['PHP_SELF']; ?>",
                type: "post",
                data: {
                    cancelar_pedidos: true,
                    item: true,
                    pedido: pedido,
                    os: os,
                    peca: peca,
                    motivo: motivo
                },
            }).always(function(data){

                data = JSON.parse(data);
                pedidos_enviados += 1;

                if(data.sucesso != ""){
                    pedidos_cancelados += 1;
                    $(".loading").html("<em>Cancelando, por favor aguarde...</em> (<strong>Cancelado "+pedidos_cancelados+" de "+pedidos_total+"</strong>)");
                }

                if(data.erro != ""){
                    $(".retorno").append("<div class='alert alert-danger'>"+data.erro+"</div>");
                }

                if(pedidos_enviados == pedidos_total){
                    $(".btn-cancela-all").prop({disabled: false});
                    $(".loading").html("");
                    $("#motivo_geral").val("");

                    for(var i = 0; i < pedidos_arr.length; i++){

                        $("#listagem").dataTable().$("tr.tr_"+pedidos_arr[i]).remove();

                    }

                    $(".retorno").append("<div class='alert alert-success'><h4>Itens dos Pedidos cancelados com sucesso!</h4></div>");

                    setTimeout(function(){
                        $(".alert-success").remove();
                    }, 10000);

                }

            });

        }

    }

</script>
    <form name="frm_cancela_pedido" method="POST" action="<?=$_SERVER['PHP_SELF']?>" class="form-search form-inline tc_formulario">
        <div class='titulo_tabela '><?=$title?></div>
        <br/>
        <?=montaForm($form['campos'], $form['hidden'])?>

        <br />

        <div class="row-fluid">
            <div class="span2"></div>
            <div class="span8">
                <div class="breadcrumb tac">
                    <label class="radio inline">
                        <strong>Relatório por:</strong>
                    </label>
                    <label class="radio inline">
                        <input type="radio" name="tipo_relatorio" value="item" <?php echo (strlen($tipo_relatorio) == 0 || $tipo_relatorio == "item") ? "checked" : ""; ?> /> Itens do Pedido
                    </label>
                    &nbsp; &nbsp;
                    <label class="radio inline">
                        <input type="radio" name="tipo_relatorio" value="pedido" <?php echo ($tipo_relatorio == "pedido") ? "checked" : ""; ?> /> Pedidos
                    </label>
                </div>
            </div>
        </div>

        <p>
            <br/>
            <input type='hidden' id="btn_click" name='btn_acao' value='listar' />
            <button class='btn' type="submit">Pesquisar</button>
            <?php if (count($_POST) or count($_RESULT)) { ?>
                <button class='btn btn-warning' type="button"
                      onclick="window.location.href=window.location.pathname;">Limpar</button>
            <?php } ?>
        </p>
        <br/>
    </form>

</div>

<?php

if(isset($num_pedidos)){

    if($num_pedidos > 0){

        $sql_status_pedido = "SELECT status_pedido, descricao FROM tbl_status_pedido";
        $res_status_pedido = pg_query($con, $sql_status_pedido);

        $status_pedido_arr = pg_fetch_all($res_status_pedido);

        if($tipo_relatorio == "pedido"){

        ?>

            <br />

            <div class="container">
                <div class="retorno"></div>
            </div>

            <div style="padding: 10px;">

                <input type="text" id="motivo_geral" class="form-control" placeholder="Motivo do Cancelamento" style="margin-top: -4px;" /> <button type="button" class="btn btn-danger btn-cancela-all" onclick="cancela_pedidos_all();" style="margin-bottom: 15px;">Cancelar Pedidos Selecionados</button> <span class="loading"></span>

                <table id="listagem" class='table table-striped table-bordered' style="min-width: 100%;">
                    <thead>
                        <tr class='titulo_tabela'>
                            <th colspan="15">Relação de Pedidos</th>
                        </tr>
                        <tr class='titulo_coluna' >
                            <th><input type="checkbox" class="check_all" onclick="check_all();" /></th>
                            <th>Pedido</th>
                            <th nowrap>Data Abertura</th>
                            <th nowrap>Tipo Pedido</th>
                            <th nowrap>Status Pedido</th>
                            <th>CPF/CNPJ</th>
                            <th nowrap>Cód. Posto</th>
                            <th>Nome</th>
                            <th>Estado</th>
                            <th>Peças</th>
                            <th>Protocolo</th>
                            <th>OS</th>
                            <th nowrap>Ref. Produto</th>
                            <th nowrap>Desc. Produto</th>
                            <th>Ações</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php

                            for ($i = 0; $i < $num_pedidos; $i++) {

                                $pedido        = pg_fetch_result($res_pedidos, $i, "pedido");
                                $data          = pg_fetch_result($res_pedidos, $i, "data");
                                $tipo_pedido   = pg_fetch_result($res_pedidos, $i, "tipo_pedido");
                                $status_pedido = pg_fetch_result($res_pedidos, $i, "status_pedido");
                                $posto         = pg_fetch_result($res_pedidos, $i, "posto");
                                $cpf           = pg_fetch_result($res_pedidos, $i, "cpf");
                                $codigo_posto  = pg_fetch_result($res_pedidos, $i, "codigo_posto");
                                $nome          = pg_fetch_result($res_pedidos, $i, "nome");
                                $uf            = pg_fetch_result($res_pedidos, $i, "uf");
                                $pecas         = pg_fetch_result($res_pedidos, $i, "pecas");
                                $hd_chamado    = pg_fetch_result($res_pedidos, $i, "hd_chamado");
                                $os            = pg_fetch_result($res_pedidos, $i, "os");
                                $ref_produto   = pg_fetch_result($res_pedidos, $i, "ref_produto");
                                $desc_produto  = pg_fetch_result($res_pedidos, $i, "desc_produto");

                                $pecas = explode(";", $pecas);
                                $total_itens = count($pecas);
                                $table_pecas = "<table class='table table-bordered table-striped' style='width: 100%;'>
                                                    <thead>
                                                        <tr class='titulo_coluna'>
                                                            <th>Referência</th>
                                                            <th>Descrição</th>
                            							    <th>Qtde</th>
                            							    <th>Qtde Faturada</th>
                            							    <th>Qtde Cancelada</th>
                            							    <th>Pendência do Pedido</th>
                                                            <th>Ações</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>";

                                foreach ($pecas as $key => $value) {

                                    list($ref, $desc, $qtde, $peca, $qtde_faturada, $qtde_cancelada) = explode(" | ", $value);

				                    $pendencia = $qtde - ($qtde_faturada + $qtde_cancelada);

                                    if($status_pedido == 14){
                                        $acao_peca = "Peça Cancelada";
                                    }else{
                                        if($login_fabrica == 151){ //hd_chamado=3097736
                                            if($pendencia > 0){
                                                $acao_peca = "
                                                    <input type='text' id='motivo_{$pedido}_{$peca}' class='form-control' placeholder='Motivo do Cancelamento' style='margin-bottom: 5px;' />
                                                    <button type='button' class='btn btn-danger btn-block btn-cancela-item-{$pedido}-{$peca}' onclick='cancela_pedido_item(\"{$pedido}\", \"{$os}\", \"{$peca}\")'>Cancelar Item do Pedido</button>
                                                    <span class='loading-item-{$pedido}-{$peca}'></span>
                                                ";
                                            }else{
                                                $acao_peca = "";
                                            }
                                        }else{
                                            $acao_peca = "
                                                <input type='text' id='motivo_{$pedido}_{$peca}' class='form-control' placeholder='Motivo do Cancelamento' style='margin-bottom: 5px;' />
                                                <button type='button' class='btn btn-danger btn-block btn-cancela-item-{$pedido}-{$peca}' onclick='cancela_pedido_item(\"{$pedido}\", \"{$os}\", \"{$peca}\")'>Cancelar Item do Pedido</button>
                                                <span class='loading-item-{$pedido}-{$peca}'></span>
                                            ";
                                        }
                                    }

                                    $table_pecas .= "<tr class='tr_{$pedido}_{$peca}'>
                                                        <td nowrap>{$ref}</td>
                                                        <td>{$desc}</td>
                            							<td class='tac'>{$qtde}</td>
                            							<td class='tac'>{$qtde_faturada}</td>
                            							<td class='tac'>{$qtde_cancelada}</td>
                            							<td class='tac'>{$pendencia}</td>
                                                        <td class='tac'>
                                                            $acao_peca
                                                        </td>
                                                    </tr>";

                                }

                                $table_pecas .= "</tbody></table>";

                                foreach ($status_pedido_arr as $key => $value) {
                                    if($value["status_pedido"] == $status_pedido){
                                        $status_pedido_desc = $value["descricao"];
                                        continue;
                                    }
                                }

                                list($data, $mls) = explode(".", $data);
                                list($data, $hora) = explode(" ", $data);
                                list($a, $m, $d) = explode("-", $data);
                                $data = $d."/".$m."/".$a." ".$hora;

                                if(strlen($cpf) >= 14){
                                    $dados_posto = explode(" - ", $nome);
                                    $cod_posto = $dados_posto[0];
                                    $nome = $dados_posto[1];
                                    $nome = (isset($dados_posto[2])) ? $nome." ".$dados_posto[2] : $nome;
                                }

                                ?>
                                <tr class="tr_<?php echo $pedido; ?>">
                                    <td class="tac"><input type="checkbox" id="check_cancelar_<?php echo $pedido; ?>" value="<?php echo $pedido.'|'.$os.'|'.$total_itens; ?>" /></td>
                                    <td><?php echo $pedido; ?></td>
                                    <td><?php echo $data; ?></td>
                                    <td><strong><?php echo $tipo_pedido; ?></strong></td>
                                    <td><strong><?php echo $status_pedido_desc; ?></strong></td>
                                    <td><?php echo $cpf; ?></td>
                                    <td><?php echo $cod_posto; ?></td>
                                    <td><?php echo $nome; ?></td>
                                    <td><?php echo $uf; ?></td>
                                    <td><?php echo $table_pecas; ?></td>
                                    <td><?php echo $hd_chamado; ?></td>
                                    <td><?php echo $os; ?></td>
                                    <td><?php echo $ref_produto; ?></td>
                                    <td><? echo $desc_produto; ?></td>
                                    <td>
                                        <?php if($status_pedido != 14){ ?>
                                        <input type="text" id="motivo_<?php echo $pedido; ?>" class="form-control" placeholder="Motivo do Cancelamento" style="margin-bottom: 5px;" />
                                        <button type="button" class="btn btn-danger btn-block btn-cancela-item-<?php echo $pedido; ?>" onclick="cancela_pedido_item('<?php echo $pedido; ?>', '<?php echo $os; ?>', '')">Cancelar Pedido</button>
                                        <span class="loading-item-<?php echo $pedido; ?>"></span>
                                        <?php } ?>
                                    </td>
                                </tr>
                        <? } ?>

                    </tbody>
                </table>

            </div>

            <br /> <br />

            <?php

        }else{

        ?>

            <br />

            <div class="container">
                <div class="retorno"></div>
            </div>

            <div style="padding: 10px;">

                <input type="text" id="motivo_geral" class="form-control" placeholder="Motivo do Cancelamento" style="margin-top: -4px;" /> <button type="button" class="btn btn-danger btn-cancela-all" onclick="cancela_pedidos_itens_all();" style="margin-bottom: 15px;">Cancelar Itens dos Pedidos Selecionados</button> <span class="loading"></span>

                <table id="listagem" class='table table-striped table-bordered' style="min-width: 100%;">
                    <thead>
                        <tr class='titulo_tabela'>
                            <th colspan="17">Relação de Itens de Pedido</th>
                        </tr>
                        <tr class='titulo_coluna' >
                            <th><input type="checkbox" class="check_all" onclick="check_all();" /></th>
                            <th>Pedido</th>
                            <th nowrap>Data Abertura</th>
                            <th nowrap>Tipo Pedido</th>
                            <th nowrap>Status Pedido</th>
                            <th>CPF/CNPJ</th>
                            <th nowrap>Cód. Posto</th>
                            <th>Nome</th>
                            <th>Estado</th>
                            <th>Referência da Peça</th>
                            <th>Descrição da Peça</th>
            			    <th>Quantidade</th>
            			    <th>Qtde Faturada</th>
            			    <th>Qtde Cancelada</th>
            			    <th>Pendência do Pedido</th>
                            <th>Protocolo</th>
                            <th>OS</th>
                            <th nowrap>Ref. Produto</th>
                            <th nowrap>Desc. Produto</th>
                            <th>Ações</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php

                            for ($i = 0; $i < $num_pedidos; $i++) {

                                $pedido_item     = pg_fetch_result($res_pedidos, $i, "pedido_item");
                                $pedido          = pg_fetch_result($res_pedidos, $i, "pedido");
                                $data            = pg_fetch_result($res_pedidos, $i, "data");
                                $tipo_pedido     = pg_fetch_result($res_pedidos, $i, "tipo_pedido");
                                $status_pedido   = pg_fetch_result($res_pedidos, $i, "status_pedido");
                                $posto           = pg_fetch_result($res_pedidos, $i, "posto");
                                $cpf             = pg_fetch_result($res_pedidos, $i, "cpf");
                                $codigo_posto    = pg_fetch_result($res_pedidos, $i, "codigo_posto");
                                $nome            = pg_fetch_result($res_pedidos, $i, "nome");
                                $uf              = pg_fetch_result($res_pedidos, $i, "uf");
                                $peca            = pg_fetch_result($res_pedidos, $i, "peca");
                                $referencia_peca = pg_fetch_result($res_pedidos, $i, "referencia");
                                $descricao_peca  = pg_fetch_result($res_pedidos, $i, "descricao");
                				$qtde_peca       = pg_fetch_result($res_pedidos, $i, "qtde");
                				$qtde_faturada   = pg_fetch_result($res_pedidos, $i, "qtde_faturada");
                				$qtde_cancelada  = pg_fetch_result($res_pedidos, $i, "qtde_cancelada");
                                $hd_chamado      = pg_fetch_result($res_pedidos, $i, "hd_chamado");
                                $os              = pg_fetch_result($res_pedidos, $i, "os");
                                $ref_produto     = pg_fetch_result($res_pedidos, $i, "ref_produto");
                                $desc_produto    = pg_fetch_result($res_pedidos, $i, "desc_produto");

                                foreach ($status_pedido_arr as $key => $value) {
                                    if($value["status_pedido"] == $status_pedido){
                                        $status_pedido_desc = $value["descricao"];
                                        continue;
                                    }
                                }

                                $pendencia_peca = $qtde_peca - ($qtde_faturada + $qtde_cancelada);

                                list($data, $mls) = explode(".", $data);
                                list($data, $hora) = explode(" ", $data);
                                list($a, $m, $d) = explode("-", $data);
                                $data = $d."/".$m."/".$a." ".$hora;

                                if(strlen($cpf) >= 14){
                                    $dados_posto = explode(" - ", $nome);
                                    $cod_posto = $dados_posto[0];
                                    $nome = $dados_posto[1];
                                    $nome = (isset($dados_posto[2])) ? $nome." ".$dados_posto[2] : $nome;
                                }

                                ?>
                                <tr class="tr_<?php echo $pedido."_".$peca; ?>">
                                    <td class="tac"><input type="checkbox" id="check_cancelar_<?php echo $pedido; ?>" value="<?php echo $pedido.'|'.$os.'|'.$peca; ?>" /></td>
                                    <td><?php echo $pedido; ?></td>
                                    <td><?php echo $data; ?></td>
                                    <td><strong><?php echo $tipo_pedido; ?></strong></td>
                                    <td><strong><?php echo $status_pedido_desc; ?></strong></td>
                                    <td><?php echo $cpf; ?></td>
                                    <td><?php echo $cod_posto; ?></td>
                                    <td><?php echo $nome; ?></td>
                                    <td><?php echo $uf; ?></td>
                                    <td><?php echo $referencia_peca; ?></td>
                                    <td><?php echo $descricao_peca; ?></td>
                				    <td class="tac"><?php echo $qtde_peca; ?></td>
                				    <td class="tac"><?php echo $qtde_faturada; ?></td>
                				    <td class="tac"><?php echo $qtde_cancelada; ?></td>
                				    <td class="tac"><?php echo $pendencia_peca; ?></td>
                                    <td><?php echo $hd_chamado; ?></td>
                                    <td><?php echo $os; ?></td>
                                    <td><?php echo $ref_produto; ?></td>
                                    <td><? echo $desc_produto; ?></td>
                                    <td>
                                        <?php
                                            if($status_pedido != 14){
                                                if($pendencia_peca > 0){
                                        ?>
                                                    <input type="text" id="motivo_<?=$pedido?>_<?=$peca?>" class="form-control" placeholder="Motivo do Cancelamento" style="margin-bottom: 5px;" />
                                                    <button type="button" class="btn btn-danger btn-block btn-cancela-item-<?=$pedido?>-<?=$peca?>" onclick="cancela_pedido_item('<?=$pedido?>', '<?=$os?>', '<?=$peca?>')">Cancelar Item do Pedido</button>
                                                    <span class="loading-item-<?=$pedido?>-<?=$peca?>"></span>
                                        <?php
                                                }
                                            }
                                        ?>
                                    </td>
                                </tr>
                        <? } ?>

                    </tbody>
                </table>

            </div>

            <br /> <br />

        <?php

        }

        $arr_excel = array(
            "posto_id"           => $_POST["posto_id"],
            "produto_id"         => $_POST["produto_id"],
            "peca_id"            => $_POST["peca_id"],
            "cliente_id"         => $_POST["cliente_id"],
            "codigo_posto"       => $_POST["codigo_posto"],
            "descricao_posto"    => $_POST["descricao_posto"],
            "estado"             => $_POST["estado"],
            "data_inicial"       => $_POST["data_inicial"],
            "data_final"         => $_POST["data_final"],
            "pedido"             => $_POST["pedido"],
            "tipo_pedido"        => $_POST["tipo_pedido"],
            "cliente_cpf"        => $_POST["cliente_cpf"],
            "cliente_nome"       => $_POST["cliente_nome"],
            "cliente_estado"     => $_POST["cliente_estado"],
            "peca_referencia"    => $_POST["peca_referencia"],
            "peca_descricao"     => $_POST["peca_descricao"],
            "pedido_os"          => $_POST["pedido_os"],
            "produto_referencia" => $_POST["produto_referencia"],
            "produto_descricao"  => $_POST["produto_descricao"],
            "protocolo"          => $_POST["protocolo"],
            "tipo_relatorio"     => $_POST["tipo_relatorio"],
            "status_pedido"      => $_POST["status_pedido"]
        );

        ?>

        <?php if($num_pedidos > 0) { ?>
        <script>
            $.dataTableLoad({
                table : "#listagem"
            });
        </script>
        <?php }?>

        <div id='gerar_excel' class="btn_excel">
            <input type="hidden" id="jsonPOST" value='<?=json_encode($arr_excel)?>' />
            <span><img src='imagens/excel.png' /></span>
            <span class="txt">Gerar Arquivo Excel</span>
        </div>

        <br />

        <?php

    }else{

        echo "<br /> <div class='container'><div class='alert alert-warning tav'> <h4>Nenhum resultado encontrado</h4> </div> </div> <br />";

    }

}

/* if (count($pedidos)) {
    $tblCfg = array('attrs' =>
        array(
            'tableAttrs' => ' class="table table-hover table-fixed table-striped" id="pedidos"'
        )
    );
    echo array2table(array_merge($tblCfg, $pedidos));
} */

include 'rodape.php';
