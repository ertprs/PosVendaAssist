<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";
include __DIR__.'/funcoes.php';

function busca_info_posto($valor){
    global $con, $login_fabrica;

    if(in_array($login_fabrica,array(152,180,181,182))){
        $campo = "codigo_posto";
    }else{
        $campo = "cnpj";
    }

    if(strstr($valor, "'") != false){
        $valor = str_replace("'", "", $valor);
    }

    if(strstr($valor, '"') != false){
        $valor = str_replace('"', "", $valor);
    }

    $sql = "SELECT nome, tbl_posto.posto
            FROM tbl_posto
            INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
            WHERE $campo = '$valor'
            AND tbl_posto_fabrica.fabrica = {$login_fabrica}";
    $res = pg_query($con, $sql);

    if(pg_num_rows($res) > 0){
        $posto_nome = pg_fetch_result($res, 0, "nome");
        $id         = pg_fetch_result($res, 0, "posto");
    }else{
        $posto_nome   = "";
        $id           = "";
    }

    return array(
        "nome" => $posto_nome,
        "id"   => $id
    );
}

if(isset($_FILES['arquivo_faturar_pedido'])){
    $arquivo = $_FILES['arquivo_faturar_pedido'];
    $types = array("csv","text/csv");
    $type  = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));

    if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["size"] > 0) {
        if (!in_array($type, $types)) {
            $msg_erro["msg"][] = "Formato inválido, é aceito apenas o formato <i>.csv</i>";
        } else {
            $file = fopen($arquivo['tmp_name'],"r");

            if($file){
                $file_content = explode("\n", file_get_contents($arquivo["tmp_name"]));

                $file_content = array_map(function($i) {
                    if(strripos($i, ";") !== false){
                        return explode(";", $i);
                    }else if(strripos($i, "\t")){
                        return explode("\t", $i);
                    }
                }, $file_content);

                $cabecalho = $file_content[0];
                unset($file_content[0]);

                $linhas = $file_content;
                unset($file_content);
                
                $linhas = array_map(function($l) use ($cabecalho) {
                    $arr = array();

                    foreach ($l as $key => $value) {
                        $arr[trim($cabecalho[$key])] = trim($value);
                    }

                    return $arr;
                }, $linhas);

                $count = count($linhas);

                for($i=1; $i<=$count; $i++){
                    if (empty($linhas[$i]['pedido'])) {
                        continue;
                    }

                    $sql = "SELECT pedido FROM tbl_pedido WHERE fabrica = {$login_fabrica} AND pedido = {$linhas[$i]['pedido']}";
                    $res = pg_query($con, $sql);

                    if (!pg_num_rows($res)) {
                        $msg_erro["msg"][] = "Pedido {$linhas[$i]['pedido']} não encontrado";
                        continue;
                    }

                    $sql = "SELECT pedido_item FROM tbl_pedido_item WHERE pedido = {$linhas[$i]['pedido']} AND pedido_item = {$linhas[$i]['pedido_item']}";
                    $res = pg_query($con, $sql);

                    if (!pg_num_rows($res)) {
                        $msg_erro["msg"][] = "Peça {$linhas[$i]['referencia_peca']} não encontrada para o pedido {$linhas[$i]['pedido']}";
                        continue;
                    }

                    $linhas[$i]['cnpj'] = str_replace(array('.','-','/'),"",$linhas[$i]['cnpj']);

                    if (in_array($login_fabrica, array(171))){
                        $xdata_emissao = $linhas[$i]['data_emissao'];
                        if (!empty($xdata_emissao)){
                            $xdata_emissao = fnc_formata_data_pg($xdata_emissao);
                            $xdata_emissao = str_replace("'", "", $xdata_emissao);

                            if (strpos($xdata_emissao, "-")) {
                                list($ano, $mes, $dia) = explode("-", $xdata_emissao);
                                $xdata_emissao = "$dia/$mes/$ano";
                            }
                            $linhas[$i]['data_emissao'] = $xdata_emissao;
                        }
                    }

                    if(strlen($linhas[$i]['nota_fiscal']) > 0){
                        $nf                = $linhas[$i]['nota_fiscal'];
                        if ($login_fabrica == 171){
                            $codigo_fn     = $linhas[$i]['codigo_ferragens_negrao'];
                        }
                        $codigo_posto      = $linhas[$i]['codigo_posto'];
                        $cnpj              = $linhas[$i]['cnpj'];
                        $serie_nota_fiscal = $linhas[$i]['serie_nota_fiscal'];
                        $data_emissao      = $linhas[$i]['data_emissao'];
                        $cfop              = $linhas[$i]['cfop'];
                        $total_nota        = $linhas[$i]['total_nota'];
                        $previsao_chegada  = $linhas[$i]['previsao_faturamento'];
                        $regiao_posto      = $linhas[$i]['regiao_posto'];

                        if ($login_fabrica == 162) {
                            $rastreio = $linhas[$i]['rastreio'];
                        }

                        if (in_array($login_fabrica, [169,170])) {
                            $motivo_troca = $linhas[$i]['motivo_troca'];
                        }

                        $xtotal_nota = "'".$total_nota."'";
                        if(strpos($xtotal_nota, "R$")){
                            $total_nota = str_replace("R$", " ", $xtotal_nota);
                            $total_nota = str_replace("'", " ", $total_nota);
                        }

                        if (strpos($total_nota, ",")) {
                            $total_nota = str_replace(".", "", $total_nota);
                            $total_nota = str_replace(",", ".", $total_nota);
                        }

                        if (strpos($data_emissao, "-")) {
                            list($ano, $mes, $dia) = explode("-", $data_emissao);
                            $data_emissao = "$dia/$mes/$ano";
                        }

                        if (strpos($previsao_chegada, "-")) {
                            list($ano, $mes, $dia) = explode("-", $previsao_chegada);
                            $previsao_chegada = "$dia/$mes/$ano";
                        }

                        if ($login_fabrica == 171){
                            $pedido_nf[$nf]["codigo_ferragens_negrao"] = $codigo_fn;
                        }
                        $pedido_nf[$nf]["codigo_posto"]      = $codigo_posto;
                        $pedido_nf[$nf]["cnpj"]              = $cnpj;
                        $pedido_nf[$nf]["serie_nota_fiscal"] = $serie_nota_fiscal;
                        $pedido_nf[$nf]["data_emissao"]      = $data_emissao;
                        $pedido_nf[$nf]["cfop"]              = $cfop;
                        $pedido_nf[$nf]["total_nota"]        = $total_nota;
                        $pedido_nf[$nf]["regiao_posto"]      = $regiao_posto;

                        if (in_array($login_fabrica, [169,170])) {
                            $pedido_nf[$nf]["motivo_troca"]      = $motivo_troca;
                        }

                        if (!empty($previsao_chegada)) {
                            $pedido_nf[$nf]["previsao_chegada"]  = $previsao_chegada;
                        }

                        if (empty($pedido_nf[$nf]["id_posto"])) {
                            if(in_array($login_fabrica,array(152,180,181,182))){
                                $info_posto = busca_info_posto($codigo_posto);
                            }else{
                                $info_posto = busca_info_posto($cnpj);
                            }

                            $pedido_nf[$nf]["nome_posto"] = $info_posto["nome"];
                            $pedido_nf[$nf]["id_posto"]   = $info_posto["id"];
                        }

                        if ($login_fabrica == 162) {
                            $pedido_nf[$nf]["rastreio"] = $rastreio;
                        }

                        $array_pedido = $linhas[$i];
                        
                        unset(
                            $array_pedido["codigo_ferragens_negrao"],
                            $array_pedido["codigo_posto"],
                            $array_pedido["cnpj"],
                            $array_pedido["serie_nota_fiscal"],
                            $array_pedido["data_emissao"],
                            $array_pedido["cfop"],
                            $array_pedido["total_nota"],
                            $array_pedido["previsao_faturamento"],
                            $array_pedido["nota_fiscal"],
                            $array_pedido["nome_posto"],
                            $array_pedido["regiao_posto"]
                        );

                        $pedido_nf[$nf]["pedidos"][] = $array_pedido;
                    } else if (!empty($linhas[$i]["previsao_faturamento"])) {
                        $pedido[$linhas[$i]['pedido']][] = $linhas[$i];
                    }
                }
            }
        }
    } else {
        $msg_erro["msg"][] = "Erro ao fazer o upload do arquivo";
    }
}

if(isset($_POST['btn_acao'])){
    $btn_acao = $_POST['btn_acao'];
}

if($btn_acao == "gravar_previsao"){
    try {
        $transaction = false;

        $pedido                = $_POST["pedido"];
        $ordem                 = str_replace("\n", "", $_POST["ordem"]);
        $previsao_faturamento  = $_POST["previsao_faturamento"];

        if (empty($pedido)) {
            throw new Exception("Pedido não informado");
        }

        if (empty($previsao_faturamento)) {
            throw new Exception("Previsão de faturamento não informada");
        } else {
            list($dia, $mes, $ano) = explode("/", $previsao_faturamento);

            $previsao_faturamento = "{$ano}-{$mes}-{$dia}";

            if (!strtotime($previsao_faturamento)) {
                throw new Exception("Data inválida");
            }
        }

        $sql = "
            SELECT
                posto,
                TO_CHAR(previsao_entrega, 'YYYY-MM-DD') AS previsao_entrega,
                pedido_cliente
            FROM tbl_pedido
            WHERE fabrica = {$login_fabrica}
            AND pedido = {$pedido}
        ";
        $res = pg_query($con, $sql);

        if (!pg_num_rows($res)) {
            throw new Exception("Pedido não encontrado");
        }

        $posto            = pg_fetch_result($res, 0, "posto");
        $previsao_entrega = pg_fetch_result($res, 0, "previsao_entrega");
        $pedido_cliente   = pg_fetch_result($res, 0, "pedido_cliente");

        $update = array();

        if ($previsao_faturamento != $previsao_entrega && strtotime($previsao_faturamento) > strtotime("today")) {
            $envia_comunicado = true;
            $descricao        = "Previsão de Faturamento do Pedido $pedido";
            $mensagem         = "A previsão de faturamento para o pedido $pedido é de {$_POST['previsao_faturamento']}";

            $update[] = "previsao_entrega = '{$previsao_faturamento}'";
        } else {
            $envia_comunicado = false;
        }

        if (empty($pedido_cliente)) {
            $update[] = "pedido_cliente = '{$ordem}'";
        }

        if (empty($update) && !empty($pedido_cliente)) {
            throw new Exception("O pedido já possui um número de ordem");
        }

        if (count($update) > 0) {
            pg_query($con, "BEGIN");

            $transaction = true;

            $sql = "
                UPDATE tbl_pedido SET
                    ".implode(", ", $update)."
                WHERE fabrica = {$login_fabrica}
                AND pedido = {$pedido}
            ";
            $res = pg_query($con, $sql);

            if (pg_affected_rows($res) == 0) {
                throw new Exception("Erro ao atualizar o pedido");
            }

            if ($envia_comunicado == true) {
                $sql = "
                    INSERT INTO tbl_comunicado
                        (fabrica, posto, obrigatorio_site, tipo, ativo, descricao, mensagem)
                    VALUES
                        ({$login_fabrica}, {$posto}, true, 'Com. Unico Posto', true, '{$descricao}', '{$mensagem}')
                ";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao atualizar o pedido");
                }
            }

            pg_query($con, "COMMIT");
        }

        exit(json_encode(array("sucesso" => true)));
    } catch(Exception $e) {
        if ($transaction === true) {
            pg_query($con, "ROLLBACK");
        }

        exit(json_encode(array("erro" => utf8_encode($e->getMessage()))));
    }
}

if ($btn_acao == "gravar_faturamento") {
    try {
        $transaction = false;

        $nota_fiscal       = $_POST["nota_fiscal"];
        $serie_nota_fiscal = $_POST["serie_nota_fiscal"];
        $id_posto          = $_POST["id_posto"];
        $data_emissao      = $_POST["data_emissao"];
        $cfop              = $_POST["cfop"];
        $total_nota        = $_POST["total_nota"];
        $pedidos           = $_POST["pedidos"];

        if (empty($nota_fiscal)) {
            throw new Exception("Nota Fiscal não informada");
        }

	if(!in_array($login_fabrica,array(180,181,182))){
		if (!strlen($serie_nota_fiscal)) {
		    throw new Exception("Série da Nota Fiscal não informada");
		} else if (strlen($serie_nota_fiscal) > 3) {
		    throw new Exception("Série não pode conter mais que 3 caracteres");
		}
	}

        if (empty($id_posto)) {
            throw new Exception("Posto Autorizado não informado");
        } else {
            $sql = "SELECT controla_estoque FROM tbl_posto_fabrica WHERE fabrica = {$login_fabrica} AND posto = {$id_posto}";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                if (pg_fetch_result($res, 0, "controla_estoque") == "t") {
                    $posto_controla_estoque = true;
                } else {
                    $posto_controla_estoque = false;
                }
            } else {
                throw new Exception("Posto Autorizado não encontrado");
            }
        }

        if (empty($data_emissao)) {
            throw new Exception("Data de Emissão da Nota Fiscal não informada");
        } else {
            list($dia, $mes, $ano) = explode("/", $data_emissao);

            if (strlen($dia) < 2 || strlen($mes) < 2 || strlen($ano) < 4) {
                throw new Exception("Data de Emissão da Nota Fiscal inválida, o formato deve ser DD/MM/YYYY");
            }

            $data_emissao = "$ano-$mes-$dia";

            if (!strtotime($data_emissao)) {
                throw new Exception("Data de Emissão da Nota Fiscal inválida, o formato deve ser DD/MM/YYYY");
            }
        }

        if (empty($cfop) AND !in_array($login_fabrica,array(180,181,182))) {
            throw new Exception("CFOP não informado");
        }

        if (!strlen($total_nota)) {
            throw new Exception("Total da Nota Fiscal não informado");
        }

        if (in_array($login_fabrica,array(152,180,181,182))) {
            $previsao_chegada = $_POST["previsao_chegada"];

            if (empty($previsao_chegada)) {
                throw new Exception("Previsão de Chegada não informada");
            } else {
                list($dia, $mes, $ano) = explode("/", $previsao_chegada);

                $previsao_chegada = "$ano-$mes-$dia";

                if (!strtotime($previsao_chegada)) {
                    throw new Exception("Previsão de Chegada inválida");
                } else if (strtotime($previsao_chegada) < strtotime("today")) {
                    throw new Exception("Previsão de Chegada não pode ser inferior ao o dia atual");
                }
            }
        }

        if ($login_fabrica == 162) {
            $rastreio = filter_input(INPUT_POST,'rastreio');
        }

        if (!count($pedidos)) {
            throw new Exception("Nota Fiscal sem Itens para faturar");
        } else {
            $erros = array();

            foreach ($pedidos as $key => $pedido) {
                if (empty($pedido["referencia_peca"])) {
                    $erros[] = "Peça do pedido {$pedido['pedido']} não informada";
                } else {


                    $sql = "SELECT peca FROM tbl_peca WHERE fabrica = {$login_fabrica} AND LOWER(referencia) = LOWER('".trim($pedido['referencia_peca'])."')";
                    $res = pg_query($con, $sql);

                    if (!pg_num_rows($res)) {
                        $erros[] = "Peça {$pedido['referencia_peca']} não encontrada";
                        continue;
                    } else {
                        $pedidos[$key]["id_peca"] = pg_fetch_result($res, 0, "peca");

                        if (empty($pedido["pedido"])) {
                            $erros[] = "Pedido da Peça {$pedido['referencia_peca']} não informado";
                        } else {
                            $sql = "
                                SELECT
                                    tbl_pedido.pedido,
                                    tbl_tipo_pedido.garantia_antecipada
                                FROM tbl_pedido
                                INNER JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido AND tbl_tipo_pedido.fabrica = {$login_fabrica}
                                WHERE tbl_pedido.fabrica = $login_fabrica
                                AND tbl_pedido.pedido = {$pedido['pedido']}";
                            $res = pg_query($con, $sql);

                            if (!pg_num_rows($res)) {
                                $erros[] = "Pedido {$pedido['pedido']} da Peça {$pedido['referencia_peca']} não encontrado";
                            } else {
                                if (pg_fetch_result($res, 0, "garantia_antecipada") == "t") {
                                    $pedidos[$key]["garantia_antecipada"] = true;
                                } else {
                                    $pedidos[$key]["garantia_antecipada"] = false;
                                }

                                if (empty($pedido["pedido_item"])) {
                                    $erros[] = "Número de Pedido Item da Peça {$pedido['referencia_peca']} não informado";
                                } else {
                                    $sql = "SELECT pedido_item FROM tbl_pedido_item WHERE pedido = {$pedido['pedido']} AND pedido_item = {$pedido['pedido_item']}";
                                    $res = pg_query($con, $sql);
                                    if (!pg_num_rows($res)) {
                                        $erros[] = "Número de Pedido Item {$pedido['pedido_item']} não encontrado para o Pedido {$pedido['pedido']}, Peça {$pedido['referencia_peca']}";
                                    } else {
                                        if (!empty($pedido["os"])) {
                                            if(in_array($login_fabrica,array(40,165))){ //hd_chamado=3120886
                                                $cond_os = "AND tbl_os.sua_os = '".$pedido['os']."'";
                                            }else{
                                                $sqlOs = "
                                                    SELECT os
                                                    FROM tbl_os
                                                    WHERE fabrica = {$login_fabrica}
                                                    AND sua_os = '{$pedido['os']}'
                                                ";
                                                $resOs = pg_query($con, $sqlOs);

                                                if (pg_num_rows($resOs) > 0) {
                                                    $pedido["os"] = pg_fetch_result($resOs, 0, "os");
                                                }

                                                $cond_os = "AND tbl_os.os = {$pedido['os']}";
                                            }
                                            $sql = "
                                                SELECT
                                                    tbl_os.os,
                                                    tbl_os_item.peca_obrigatoria,
                                                    tbl_os_item.os_item
                                                FROM tbl_os
                                                INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                                                INNER JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                                                WHERE tbl_os.fabrica = {$login_fabrica}
                                                {$cond_os}
                                                AND tbl_os_item.pedido_item = {$pedido['pedido_item']}
                                            ";
                                            $res = pg_query($con, $sql);
                                            if (!pg_num_rows($res)) {
                                                $erros[] = "OS {$pedido['os']} não encontrado ou não é atendida pelo Pedido {$pedido['pedido']}, Peça {$pedido['referencia_peca']}";
                                            } else {
                                                $pedidos[$key]["devolucao_obrigatoria"] = pg_fetch_result($res, 0, "peca_obrigatoria");
                                                $pedidos[$key]["os_item"] = pg_fetch_result($res, 0, "os_item");
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        if (!strlen($pedido["qtde_faturada"])) {
                            $erros[] = "Qtde Faturada da Peça {$pedido['referencia_peca']} não informada";
                        } else {
                            $sql = "
                                SELECT (qtde - (qtde_cancelada + qtde_faturada)) AS qtde_pendente
                                FROM tbl_pedido_item
                                WHERE pedido = {$pedido['pedido']}
                                AND pedido_item = {$pedido['pedido_item']}
                            ";
                            $res = pg_query($con, $sql);

                            if (pg_num_rows($res) > 0) {
                                $qtde_pendente = pg_fetch_result($res, 0, "qtde_pendente");
                                $parcial = false;

                                if ($pedido["qtde_faturada"] > $qtde_pendente) {
                                    $erros[] = "Peça {$pedido['referencia_peca']} qtde faturada ({$pedido['qtde_faturada']}) não pode ser superior a qtde pendente ({$qtde_pendente})";
                                } else if ($pedido["qtde_faturada"] < $qtde_pendente) {
                                    $parcial = true;
                                }
                            }
                        }

                        if (!strlen($pedido["preco"])) {
                            $erros[] = "Preço da Peça {$pedido['referencia_peca']} não informado";
                        }
                    }
                }
            }

            if (count($erros) > 0) {
                throw new Exception(implode("<br />", $erros));
            }

            pg_query($con, "BEGIN");

            $transaction = true;

	    if(in_array($login_fabrica,array(180,181,182))){
		$colunas = array("fabrica", "emissao", "saida", "posto", "total_nota", "nota_fiscal");
		$valores = array($login_fabrica, "'{$data_emissao}'", "'{$data_emissao}'", $id_posto, $total_nota, "'{$nota_fiscal}'");
	    }else{
            	$colunas = array("fabrica", "emissao", "saida", "posto", "total_nota", "cfop", "nota_fiscal", "serie");
            	$valores = array($login_fabrica, "'{$data_emissao}'", "'{$data_emissao}'", $id_posto, $total_nota, "'{$cfop}'", "'{$nota_fiscal}'", "'{$serie_nota_fiscal}'");
	    }
            if (in_array($login_fabrica,array(152,180,181,182))){
                $colunas[] = "previsao_chegada";
                $valores[] = "'{$previsao_chegada}'";
            }
            if ($login_fabrica == 162) {
                $colunas[] = "conhecimento";
                $valores[] = "'$rastreio'";
            }

            $sql = "SELECT faturamento
                    FROM tbl_faturamento
                    WHERE fabrica = {$login_fabrica}
                    AND nota_fiscal = '{$nota_fiscal}'
                    AND serie = '{$serie_nota_fiscal}' ";
            $res = pg_query($con,$sql);

            if(pg_num_rows($res) == 0){
                $sql = "
                    INSERT INTO tbl_faturamento
                        (".implode(", ", $colunas).")
                    VALUES
                        (".implode(", ", $valores).")
                    RETURNING faturamento
";
                $res = pg_query($con,$sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao gravar nota fiscal");
                }
            }

            $faturamento = pg_fetch_result($res, 0, "faturamento");

            if ($login_fabrica == 152) {
                $pedido_atualiza  = array();
                $array_comunicado = array();
            }

            foreach ($pedidos as $pedido) {
                if ($login_fabrica == 152) {
                    if (!isset($pedido_atualiza[$pedido["pedido"]]) && !empty($pedido["ordem"])) {
                        $pedido_atualiza[$pedido_atualiza] = str_replace("\n", "", $pedido["ordem"]);
                    }

                    $array_comunicado[] = array(
                        "pedido"           => $pedido["pedido"],
                        "peca"             => $pedido["referencia_peca"],
                        "previsao_chegada" => $previsao_chegada
                    );
                }

                $colunas = array("faturamento", "peca", "qtde", "preco", "pedido", "pedido_item");
                $valores = array($faturamento, $pedido["id_peca"], $pedido["qtde_faturada"], $pedido["preco"], $pedido["pedido"], $pedido["pedido_item"]);

                unset($os_troca, $data_conserto);

                if (!empty($pedido["os"])) {
                    $sua_os = $pedido['os'];

                    $sql_os = "
                        SELECT tbl_os.os, tbl_os.sua_os, tbl_os_troca.os_troca, tbl_os.data_conserto
                        FROM tbl_os
                        LEFT JOIN tbl_os_troca ON tbl_os_troca.os = tbl_os.os AND tbl_os_troca.fabric = {$login_fabrica}
                        WHERE tbl_os.fabrica = $login_fabrica
                        AND tbl_os.sua_os = '$sua_os'
                    ";
                    $res_os = pg_query($con, $sql_os);

                    if(pg_num_rows($res_os) > 0){
                        $pedido["os"]  = pg_fetch_result($res_os, 0, 'os');
                        $os_troca      = pg_fetch_result($res_os, 0, "os_troca");
                        $data_conserto = pg_fetch_result($res_os, 0, "data_conserto");
                    }

                    $colunas[] = "os";
                    $colunas[] = "os_item";
                    $colunas[] = "devolucao_obrig";

                    $valores[] = $pedido["os"];
                    $valores[] = $pedido["os_item"];
                    $valores[] = ($pedido["devolucao_obrigatoria"] == "t") ? "true" : "false";
                }

                $sql_fat_item = "SELECT faturamento_item from tbl_faturamento_item where faturamento = $faturamento and pedido_item = ".$pedido["pedido_item"];
                $res_fat_item = pg_query($con, $sql_fat_item);

                if(pg_num_rows($res_fat_item)> 0){
                    throw new Exception("{$pedido['referencia_peca']} item já faturado anteriormente");
                }else{
                    $sql = "
                        INSERT INTO tbl_faturamento_item
                            (".implode(", ", $colunas).")
                        VALUES
                            (".implode(", ", $valores).")
                    ";
                    $res = pg_query($con, $sql);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao gravar itens da nota fiscal");
                    }

                    if (!empty($os_troca) && empty($data_conserto)  && !in_array($login_fabrica, [167,203])) {
                        $sql = "UPDATE tbl_os SET data_conserto = CURRENT_TIMESTAMP WHERE fabrica = {$login_fabrica} AND os = {$pedido['os']}";
                        $res = pg_query($con, $sql);

                        if (strlen(pg_last_error()) > 0) {
                            throw new Exception("Erro ao atualizar status da ordem de serviço");
                        }
                    }
                }

                $sql = "
                    SELECT peca
                    FROM tbl_estoque_posto
                    WHERE fabrica = {$login_fabrica}
                    AND posto = {$id_posto}
                    AND peca = {$pedido['id_peca']}
                ";
                $res = pg_query($con, $sql);

                if (pg_num_rows($res) > 0) {
                    $peca_estoque_posto = true;
                } else {
                    $peca_estoque_posto = false;
                }

                if (in_array($login_fabrica, [163,173]) AND $peca_estoque_posto === false) {
                    $sqlEstoque = "
                                    INSERT INTO tbl_estoque_posto (
                                        fabrica,
                                        posto,
                                        peca,
                                        qtde,
                                        tipo,
                                        estoque_minimo,
                                        data_input
                                    ) VALUES (
                                        {$login_fabrica},
                                        {$id_posto},
                                        {$pedido['id_peca']},
                                        0,
                                        'estoque',
                                        0,
                                        CURRENT_DATE
                                    );";
                    $resEstoque = pg_query($con,$sqlEstoque);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao lançar estoque do posto autorizado");
                    }

                    $peca_estoque_posto = true;

                }

                if ($peca_estoque_posto === true && $posto_controla_estoque === true) {
                    if ($pedido["garantia_antecipada"] === true OR in_array($login_fabrica, array(163,173))) {
                        $sql = "
                            INSERT INTO tbl_estoque_posto_movimento
                                (fabrica, posto, pedido, peca, qtde_entrada, nf, data, obs,admin)
                            VALUES
                                ({$login_fabrica}, {$id_posto}, {$pedido['pedido']}, {$pedido['id_peca']}, {$pedido['qtde_faturada']}, '$nota_fiscal', '{$data_emissao}', 'Faturamento do pedido {$pedido['pedido']}',$login_admin)
                        ";
                        $res = pg_query($con, $sql);

                        if (strlen(pg_last_error()) > 0) {
                            throw new Exception("Erro ao lançar movimentação no estoque do posto autorizado");
                        }

                        $sql = "
                            UPDATE tbl_estoque_posto SET
                                qtde = (COALESCE(qtde, 0)) + {$pedido['qtde_faturada']}
                            WHERE fabrica = {$login_fabrica}
                            AND posto = {$id_posto}
                            AND peca = {$pedido['id_peca']}
                        ";
                        $res = pg_query($con, $sql);

                        if (strlen(pg_last_error()) > 0) {
                            throw new Exception("Erro ao atualizar o estoque do posto autorizado");
                        }
                    } else if (!empty($pedido["os"])) {
                        $sql = "
                            INSERT INTO tbl_estoque_posto_movimento
                                (fabrica, posto, pedido, peca, qtde_entrada, nf, data, obs)
                            VALUES
                                ({$login_fabrica}, {$id_posto}, {$pedido['pedido']}, {$pedido['id_peca']}, {$pedido['qtde_faturada']}, '$nota_fiscal', '{$data_emissao}', 'Faturamento do pedido {$pedido['pedido']}')
                        ";
                        $res = pg_query($con, $sql);

                        if (strlen(pg_last_error()) > 0) {
                            throw new Exception("Erro ao lançar movimentação no estoque do posto autorizado");
                        }

                        $sql = "
                            INSERT INTO tbl_estoque_posto_movimento
                                (fabrica, posto, os, peca, qtde_saida, obs)
                            VALUES
                                ({$login_fabrica}, {$id_posto}, {$pedido['os']}, {$pedido['id_peca']}, {$pedido['qtde_faturada']}, 'Usado na Ordem de Serviço {$pedido['os']}')
                        ";
                        $res = pg_query($con, $sql);

                        if (strlen(pg_last_error()) > 0) {
                            throw new Exception("Erro ao lançar movimentação no estoque do posto autorizado");
                        }
                    }
                }

                $sql = "SELECT fn_atualiza_pedido_item({$pedido['id_peca']}, {$pedido['pedido']}, {$pedido['pedido_item']}, {$pedido['qtde_faturada']})";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao atualizar peça do pedido");
                }

                $sql = "SELECT fn_atualiza_status_pedido({$login_fabrica}, {$pedido['pedido']})";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao atualizar status do pedido");
                }

                if (!empty($pedido["os"])) {
                    $sql = "UPDATE tbl_os SET status_checkpoint = fn_os_status_checkpoint_os({$pedido['os']}) WHERE fabrica = {$login_fabrica} AND os = {$pedido['os']}";
                    $res = pg_query($con, $sql);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao atualizar status da ordem de serviço");
                    }

                    if (in_array($login_fabrica, [178])) { 
                        if (verifica_tipo_posto("posto_interno", "true", $id_posto)) {
                            $sqlUpdate = "UPDATE tbl_os SET finalizada  = '{$data_emissao}', 
                                                        data_fechamento = '{$data_emissao}', 
                                                        data_conserto   = '{$data_emissao}' 
                                        WHERE os = {$pedido['os']} AND fabrica = {$login_fabrica}";
                            $resUpdate = pg_query($con, $sqlUpdate);

                            if (strlen(pg_last_error()) > 0) {
                                $msg_erro["msg"][] = "Erro ao atualizar a Ordem de Serviço #PST1";
                            }       
                        }
                    }
                }

                if (in_array($login_fabrica, array(169,170))) {
                    $updPdI = "UPDATE tbl_pedido_item SET serie_locador = '{$pedido['docnum']}' WHERE pedido_item = {$pedido['pedido_item']};";
                    $resUpdPdI = pg_query($con, $updPdI);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao atualizar o número do documento da nota fiscal");
                    }
                }
            }

            if ($login_fabrica == 152 && count($pedido_atualiza) > 0) {
                foreach ($pedido_atualiza as $pedido => $ordem) {
                    $sql = "
                        SELECT pedido_cliente, previsao_entrega
                        FROM tbl_pedido
                        WHERE fabrica = {$login_fabrica}
                        AND pedido = {$pedido}
                    ";
                    $res = pg_query($con, $sql);

                    if (strlen(pg_last_error()) > 0 || !pg_num_rows($res)) {
                        throw new Exception("Erro ao atualizar ordem/previsão de faturamento do pedido");
                    }

                    if (strlen(pg_fetch_result($res, 0, "previsao_entrega")) > 0 || !strlen(pg_fetch_result($res, 0, "pedido_cliente"))) {
                        if (!empty($ordem)) {
                            $coluna_ordem = ", pedido_cliente = '{$ordem}'";
                        }

                        $sql = "
                            UPDATE tbl_pedido SET
                                previsao_entrega = null
                                {$coluna_ordem}
                            WHERE fabrica = {$login_fabrica}
                            AND pedido = {$pedido}
                        ";
                        $res = pg_query($con, $sql);

                        if (strlen(pg_last_error()) > 0) {
                            throw new Exception("Erro ao atualizar ordem/previsão de faturamento do pedido");
                        }
                    }
                }
            }

            if (in_array($login_fabrica,array(152,180,181,182)) && count($array_comunicado)) {
                $html = "<ul>";

                foreach ($array_comunicado as $comunicado) {
                    list($ano, $mes, $dia) = explode("-", $previsao_chegada);

                    $previsao_chegada = "$dia/$mes/$ano";

                    $html .= "
                        <li>Pedido: {$comunicado['pedido']}, Peça: {$comunicado['peca']}, Previsão de Chegada: {$previsao_chegada}</li>
                    ";
                }

                $html .= "</ul>";

                $sql = "
                    INSERT INTO tbl_comunicado
                        (fabrica, posto, obrigatorio_site, tipo, ativo, descricao, mensagem)
                    VALUES
                        ({$login_fabrica}, {$id_posto}, true, 'Com. Unico Posto', true, 'Faturamento - Nota Fiscal {$nota_fiscal} Série {$serie_nota_fiscal}', '{$html}')";
                $res = pg_query($con, $sql);

                if(pg_last_error() > 0){
                    throw new Exception("Erro ao enviar comunicado de pedidos faturados para o posto autorizado");
                }
            }

            $sqlVer  = "SELECT * FROM tbl_os_troca WHERE os = {$pedido['os']} AND fabric = {$login_fabrica}";
            $resVer  = pg_query($con,$sqlVer);
            $isTroca = (pg_num_rows($resVer) > 0) ? true : false;

            if (($login_fabrica == 190 || $login_fabrica == 203 && $isTroca) && !empty($pedido["os"])) {
                //AO FATURAR PEDIDO, ALTERA O STATUS DA OS PARA AGUARDANDO CONSERTO
                $xStatus = ($login_fabrica == 203) ? 8 : 3;
                $sql = "UPDATE tbl_os SET status_checkpoint = $xStatus WHERE fabrica = {$login_fabrica} AND os = {$pedido['os']}";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao atualizar status da ordem de serviço");
                }
            }

            pg_query($con, "COMMIT");

            $array_sucesso["sucesso"] = true;

            if ($parcial) {
                $array_sucesso["parcial"] = true;
            }

            exit(json_encode($array_sucesso));
        }
    } catch(Exception $e) {
        if ($transaction === true) {
            pg_query($con, "ROLLBACK");
        }

        exit(json_encode(array("erro" => utf8_encode($e->getMessage()))));
    }
}

function formatar_data($data){
    if(strripos($data,"-") == true){
        list($ano, $mes, $dia) = explode("-", $data);
        $data = $dia."/".$mes."/".$ano;
    }else{
        list($dia, $mes, $ano) = explode("/", $data);
        $data = $ano."-".$mes."-".$dia;
    }
    return($data);
}

function verifica_tipo_posto($tipo, $valor, $id_posto = null) {
    global $con, $login_fabrica, $login_posto, $areaAdmin, $posto_id;

    if (empty($areaAdmin)) {
        $areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;
    }

    if (empty($id_posto)) {
        $id_posto  = ($areaAdmin == true) ? $posto_id : $login_posto;    
    }

    $sql = "
        SELECT tbl_tipo_posto.tipo_posto
        FROM tbl_posto_fabrica
        INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
        WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
        AND tbl_posto_fabrica.posto = {$id_posto}
        AND tbl_tipo_posto.{$tipo} IS {$valor}
    ";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        return true;
    } else {
        return false;
    }
}


$layout_menu = "callcenter";
$title = "FATURAR PEDIDO";

include "cabecalho_new.php";

$plugins = array(
   "datepicker",
   "shadowbox",
   "maskedinput"
);

include __DIR__."/plugin_loader.php";
?>

<script type="text/javascript">
    $(function(){
        $("input.previsao_chegada").datepicker({ dateFormat: "dd/mm/yy", minDate: 0 }).mask("99/99/9999");

        $("button.salvar_arquivo").on("click",function(){
            $(this).button("loading");

            setTimeout(function() {
                var arquivo = $('#arquivo_faturar_pedido').val();

                if(arquivo.length > 0){
                    $("button.salvar_arquivo").parents("#frm_upload_faturar_pedido").submit();
                }else{
                    $("button.salvar_arquivo").button("reset");
                    alert("Selecione o arquivo da nota fiscal de serviço");
                }
            }, 1);
        });

        <?php
        if ($login_fabrica == 152) {
        ?>
            $("button.btn_previsao").on("click", function(){
                $(this).button("loading");

                $("table.pedido_sem_nota > tbody > tr").each(function() {
                    var td_status = $(this).find("td.status");

                    $(td_status).html("<span class='label label-info' >Aguardando...</span>");
                });

                var processo_erro = false;

                $("table.pedido_sem_nota > tbody > tr.pedido").each(function() {
                    var td_status = $(this).find("td.status");

                    var pedido               = $(this).data("pedido");
                    var ordem                = $(this).find("input.ordem").val();
                    var previsao_faturamento = $(this).find("input.previsao").val();

                    if (typeof previsao_faturamento == "undefined" || previsao_faturamento.length == 0) {
                        processo_erro = true;
                        $(td_status).html("<span class='label label-important' >Preencha a Previsão de Faturamento</span>");
                    } else {
                        $.ajax({
                            async: false,
                            url: "upload_faturar_pedido.php",
                            type: "post",
                            data: {
                                btn_acao: "gravar_previsao",
                                pedido: pedido,
                                ordem: ordem,
                                previsao_faturamento: previsao_faturamento
                            },
                            beforeSend: function() {
                                $(td_status).html("<span class='label label-warning' >Processando...</span>");
                            },
                            timeout: 5000
                        }).fail(function(response) {
                            processo_erro = true;
                            $(td_status).html("<span class='label label-important' >Tempo limite esgotado tente novamente</span>");
                        }).done(function(response) {
                            response = JSON.parse(response);

                            if (response.sucesso) {
                                $(td_status).html("<span class='label label-success' >Atualizado</span>");
                            } else {
                                processo_erro = true;
                                $(td_status).html("<span class='label label-important' >"+response.erro+"</span>");
                            }
                        });
                    }
                });

                if (processo_erro === true) {
                    alert("Processamento concluído com erros");
                } else {
                    alert("Processamento concluído com sucesso");
                }

                $(this).button("reset");
            });
        <?php
        }
        ?>

        $("button.btn_grava_nota_fiscal").on("click", function() {
            $(this).button("loading");

            $("table.nota_fiscal_faturar").each(function() {
                $(this).find("td.status").html("<span class='label label-info' >Aguardando...</span>");
            });

            var processo_erro = false;
            var parcial = 0;

            $("table.nota_fiscal_faturar").each(function() {
                var td_status = $(this).find("td.status");

                if ($(td_status).find("span.label-success").length > 0) {
                    return;
                }

                var erro = [];

                var nota_fiscal       = $(this).find("input.nota_fiscal").val();
                var serie_nota_fiscal = $(this).find("input.serie_nota_fiscal").val();
                var id_posto          = $(this).find("input.id_posto").val();
                var data_emissao      = $(this).find("input.data_emissao").val();
                var cfop              = $(this).find("input.cfop").val();
                var total_nota        = $(this).find("input.total_nota").val();

                <?php if (in_array($login_fabrica,array(152,180,181,182))) { ?>
                    var previsao_chegada = $(this).find("input.previsao_chegada").val();

                    if (typeof previsao_chegada == "undefined" || previsao_chegada.length == 0) {
                        erro.push("Previsão de Chegada não informada");
                    }
                <?php }
                if ($login_fabrica == 162) {?>
                    var rastreio = $(this).find("input.rastreio").val();
                <?php } ?>
		<?php
		if (!in_array($login_fabrica,array(180,181,182))) {?>
			if (typeof serie_nota_fiscal == "undefined" || serie_nota_fiscal.length == 0) {
			    erro.push("Série da Nota Fiscal não informada");
			}
		<?php } ?>

                if (typeof id_posto == "undefined" || id_posto.length == 0) {
                    erro.push("Posto Autorizado não encontrado");
                }

                if (typeof data_emissao == "undefined" || data_emissao.length == 0) {
                    erro.push("Data de Emissão da Nota Fiscal não informada");
                }

		<?php
		if (!in_array($login_fabrica,array(180,181,182))) {?>
			if (typeof cfop == "undefined" || cfop.length == 0) {
			    erro.push("CFOP não informado");
			}
		<?php } ?>

                if (typeof total_nota == "undefined" || total_nota.length == 0) {
                    erro.push("Total da Nota Fiscal não informado");
                }

                var pedidos = [];

                $(this).find("table.pedidos > tbody > tr").each(function() {
                    var pedido = $(this).find("input.pedido").val();
                    var pedido_item = $(this).find("input.pedido_item").val();
                    var os = $(this).find("input.os").val();
                    var referencia_peca = $(this).find("input.referencia_peca").val();
                    var qtde_pendente = parseInt($(this).find("input.quantidade_pendente").val());
                    var qtde_faturada = parseInt($(this).find("input.quantidade_faturada").val());
                    var preco = parseFloat($(this).find("input.preco").val());
                    var total = parseFloat($(this).find("input.total").val());

                    <?php if ($login_fabrica == 152) { ?>
                        var ordem = $(this).find("input.ordem").val();
                    <?php }
                    if (in_array($login_fabrica, array(169,170))) { ?>
                        var docnum = $(this).find("input.docnum").val();
                    <?php } ?>

                    var erro_linha = false;

                    if (typeof referencia_peca == "undefined" || referencia_peca.length == 0) {
                        erro.push("Peça do Pedido "+pedido+" não informada");
                        erro_linha = true;
                    } else {
                        if (typeof pedido == "undefined" || pedido.length == 0) {
                            erro.push("Número de Pedido da Peça "+referencia_peca+" não informado");
                            erro_linha = true;
                        }

                        if (typeof pedido_item == "undefined" || pedido_item.length == 0) {
                            erro.push("Número de Pedido Item da Peça "+referencia_peca+" não informado");
                            erro_linha = true;
                        }

                        if (isNaN(qtde_pendente)) {
                            erro.push("Peça "+referencia_peca+" Qtde Pendente inválida (deve ser um número inteiro) ou não informada");
                            erro_linha = true;
                        }

                        if (isNaN(qtde_faturada)) {
                            erro.push("Peça "+referencia_peca+" Qtde Faturada inválida (deve ser um número inteiro) ou não informada");
                            erro_linha = true;
                        }

                        if ((!isNaN(qtde_pendente) && !isNaN(qtde_faturada)) && qtde_faturada > qtde_pendente) {
                            erro.push("Peça "+referencia_peca+" Qtde Faturada não pode ser superior a Qtde Pendente");
                            erro_linha = true;
                        }

                        if (isNaN(preco)) {
                            erro.push("Peça "+referencia_peca+" Preço inválido (formatos 1.333,44 ou 1333.44) ou não informado");
                            erro_linha = true;
                        }

                        <?php if (in_array($login_fabrica, array(169,170))) { ?>
                            if (typeof docnum == "undefined" || docnum.length == 0) {
                                erro.push("Número de documento do faturamento do Pedido "+pedido+" necessário para realizar o faturamento");
                                erro_linha = true;
                            }
                        <?php } ?>

                    }

                    if (!erro_linha) {
                        var dados_pedido = {
                            pedido: pedido,
                            pedido_item: pedido_item,
                            os: os,
                            referencia_peca: referencia_peca,
                            qtde_pendente: qtde_pendente,
                            qtde_faturada: qtde_faturada,
                            preco: preco,
                            total: total
                        };

                        <?php if ($login_fabrica == 152) { ?>
                            dados_pedido.ordem = ordem;
                        <?php }
                        if (in_array($login_fabrica, array(169,170))) { ?>
                            dados_pedido.docnum = docnum;
                        <?php } ?>

                        pedidos.push(dados_pedido);
                    }
                });

                if (erro.length > 0) {
                    processo_erro = true;
                    $(this).find("td.status").html("<span class='label label-important' >"+erro.join("<br />")+"</span>");
                    return;
                } else {
                    var dados_nota_fiscal = {
                        btn_acao: "gravar_faturamento",
                        nota_fiscal: nota_fiscal,
                        serie_nota_fiscal: serie_nota_fiscal,
                        id_posto: id_posto,
                        data_emissao: data_emissao,
                        cfop: cfop,
                        total_nota: total_nota,
                        pedidos: pedidos
                    };

                    <?php if (in_array($login_fabrica,array(152,180,181,182))) { ?>
                        dados_nota_fiscal.previsao_chegada = previsao_chegada;
                    <?php }
                    if ($login_fabrica == 162) {?>
                        dados_nota_fiscal.rastreio = rastreio;
                    <?php } ?>

                    $.ajax({
                        async: false,
                        url: "upload_faturar_pedido.php",
                        type: "post",
                        dataType:"JSON",
                        data: dados_nota_fiscal,
                        beforeSend: function() {
                            $(td_status).html("<span class='label label-warning' >Processando...</span>");
                        },
                        timeout: 5000
                    }).fail(function(response) {
                        processo_erro = true;
                        $(td_status).html("<span class='label label-important' >Tempo limite esgotado tente novamente</span>");
                    }).done(function(response) {
                        if (response.sucesso) {
                            $(td_status).html("<span class='label label-success' >Faturado</span>");
                            if (response.parcial) {
                                parcial = 1;
                            }
                        } else {
                            processo_erro = true;
                            $(td_status).html("<span class='label label-important' >"+response.erro+"</span>");
                        }
                    });
                }
            });

            if (processo_erro === true) {
                alert("Processamento concluído com erros");
            } else {
                var msg = "Processamento concluído com sucesso";
                if (parcial == 1) {
                    msg += "\nHá itens que foram processados parcialmente";
                }
                alert(msg);
            }

            $(this).button("reset");
        });
    });
</script>

<? if (count($msg_erro['msg']) > 0) { ?>
    <div class="alert alert-error">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    <h4><?=implode("<br />", $msg_erro['msg'])?></h4></div>
<? } ?>

<div class="mensagem"></div>
<form name="frm_upload_faturar_pedido" id="frm_upload_faturar_pedido" method="POST" action="<?echo $PHP_SELF?>"  class="form-search form-inline tc_formulario" enctype="multipart/form-data">
    <div class='titulo_tabela '><?=traduz('Parâmetros de Pesquisa')?></div>
    <br/>
    <?php if (!in_array($login_fabrica, [180,181,182])) { ?>
	<div class="alert alert-info">
            <h5>Clique <a href="consulta_pedido_nao_faturado.php" style="color: red;"><strong>aqui</strong></a> para acessar os pedidos e baixar o arquivo completo para upload de faturamento.</h5>
    	</div>
    <?php } ?>
    <span class="label label-important" style="max-width: 95%;">
        Layout do arquivo: A planilha deverá ser no formato CSV (.csv), Os campos devem ser separados por ponto e virgula(;)<br /><br />
        <?php if (in_array($login_fabrica, [144])) { ?>
            os;codigo_posto;nome_posto;consumidor;numero_serie;pedido;cnpj; pedido_item;referencia_peca;<br />descricao_peca;quantidade_pendente;preco;nota_fiscal;serie_nota_fiscal;data_emissao;cfop;total_nota;quantidade_faturada <br />
        <?php } ?>
    </span>
    <div class='row-fluid'>
        <div class="span2"></div>
        <div class="span2">
            <div class='control-group <?=(in_array('data_inicial', $msg_erro['campos'])) ? "error" : "" ?>' >
                <div class="controls controls-row">
                    <div class="span12"><h5 class='asteristico'>*</h5>
                        <label><?=traduz('Upload de arquivo')?></label>
                        <input type='file' name='arquivo_faturar_pedido' id="arquivo_faturar_pedido" size='18' />
                    </div>
                </div>
            </div>
        </div>
    </div>
    <p>
        <button type="button" data-loading-text="Realizando Upload..." class="btn salvar_arquivo"><?=traduz('Upload de Arquivo')?></button>
    </p>
    <br />
</form>
<div class="campo_obrigatorio"></div>
<?php
if(count($linhas) > 0){
    if(in_array($login_fabrica, array(152,180,181,182)) && count($pedido) > 0){
    ?>
    <table class='pedido_sem_nota table table-striped table-bordered table-large' style="margin: 0 auto;" >
        <tr class="warning" >
            <td class="tac" colspan="5" style="font-weight: bold;" ><?=traduz('Atualização da Ordem/Previsão de Faturamento')?></td>
        </tr>
        <tr>
            <td class="tac" colspan="5" >
                <button type="button" data-loading-text="Processando..." class="btn btn-warning btn_previsao"><?=traduz('Gravar Ordem/Previsão de Faturamento')?></button>
            </td>
        </tr>
        <tr class='titulo_coluna'>
            <th><?=traduz('Pedido')?></th>
            <th><?=traduz('Ordem')?></th>
            <th><?=traduz('Previsão de Faturamento')?></th>
            <th><?=traduz('Peça')?></th>
            <th><?=traduz('Status')?></th>
        </tr>
        <?php
        $array_pedido = array();

        foreach ($pedido as $num_pedido => $value) {
            $count = count($value);

            $previsao_faturamento = $value[0]['previsao_faturamento'];

            if(strripos($previsao_faturamento, "-") == true){
                $previsao_faturamento = formatar_data($previsao_faturamento);
            }
            ?>
            <tr class="pedido" data-pedido="<?=$num_pedido?>" id="<?=$num_pedido?>">
                <td class="sub-titulo" id="pedido">
                    <?=$num_pedido?>
                </td>
                <td>
                    <?=$value[0]["ordem"]?>
                    <input type="hidden" class="ordem" value="<?=$value[0]["ordem"]?>" />
                </td>
                <td class="sub-titulo" id="data_previsao_faturamento">
                    <input type="text" class="previsao" value="<?=$previsao_faturamento?>" style="width: 80px;" />
                </td>
                <td>
                    <?php
                    $array_pedido[] = $num_pedido;

                    for($i=0; $i<$count; $i++){
                        echo "<span style='display: block;' >".$value[$i]["referencia_peca"]." - ".str_replace('"', '', $value[$i]["descricao_peca"])."</span>";
                    }
                    ?>
                </td>
                <td class="status" ></td>
            </tr>
        <?php
        }
        ?>
    </table>

    <hr />
    <?php
    }

    if(count($pedido_nf) > 0){
    ?>
    <!-- <div class="campo_obrigatorio2"></div>
    <table class='fatura_pedido table table-striped table-bordered table-large' style="margin: 0 auto;" >
        <thead>
            <tr class='titulo_coluna' class="fonte_titulo">
                <th colspan="5" class="fonte_titulo">Faturamento de Pedido
                    <button type="button" data-loading-text="Gravando..." class="btn btn-mini btn-success pull-right btn_fatura_pedido">Gravar Faturamento de Pedidos</button>
                </th>
            </tr>
        </thead>
        <tbody> -->
        <table class="table table-striped table-bordered table-large" style="margin: 0 auto;" >
            <tr class="success" >
                <td class="tac" style="font-weight: bold;" ><?=traduz('Faturamento de Pedidos')?></td>
            </tr>
            <td class="tac" >
                <button type="button" data-loading-text="Processando..." class="btn btn-success btn_grava_nota_fiscal" ><?=traduz('Gravar Notas Fiscais')?></button>
            </td>
        </table>

        <br />

        <?php
        foreach ($pedido_nf as $nota_fiscal => $dados) {
            if (in_array($login_fabrica,array(152,162,171,180,181,182))) {
                $colspan = 5;
                $colspan2 = 2;
            } else {
                $colspan = 4;
            }

            $total_nota_mostrar = $dados["total_nota"];

            if ($login_fabrica == 156) {
                $total_nota_mostrar = 'R$ ' . number_format($dados["total_nota"], 2, ",", "");
            }
            ?>
            <table class="table table-striped table-bordered table-large nota_fiscal_faturar" style="margin: 0 auto;" >
                <tr class="info" >
                    <td class="tac" colspan="<?=$colspan?>" style="font-weight: bold;" >
                        Nota fiscal: <?=$nota_fiscal?> - Série: <?=$dados["serie_nota_fiscal"]?>
                        <input type="hidden" class="nota_fiscal" value="<?=$nota_fiscal?>" />
                        <input type="hidden" class="serie_nota_fiscal" value="<?=$dados['serie_nota_fiscal']?>" />
                        <input type="hidden" class="id_posto" value="<?=$dados['id_posto']?>" />
                        <input type="hidden" class="data_emissao" value="<?=$dados['data_emissao']?>" />
                        <input type="hidden" class="cfop" value="<?=$dados['cfop']?>" />
                        <input type="hidden" class="total_nota" value="<?=$dados['total_nota']?>" />
                    </td>
                </tr>
                <tr class='titulo_coluna'>
                    <?php
                    if(in_array($login_fabrica, array(152,180,181,182))){
                    ?>
                        <th><?=traduz('Previsão de Chegada')?></th>
                    <?php
                    }
                    
                    if ($login_fabrica == 171){
                    ?>
                    <th>Código Ferragens Negrão</th>
                    <?php
                    }
                    ?>
                    <th <?=($login_fabrica == 162) ? $colspan2 : ""?>><?=traduz('Posto')?></th>
		    <th><?=traduz('Data de Emissão')?></th>
		<?php
			if(!in_array($login_fabrica,array(180,181,182))){
		   ?>
		    		<th>CFOP</th>
		<?php
			}
			?>
                    <th>Total</th>
                </tr>
                <tr>
                    <?php
                    if(in_array($login_fabrica, array(152,180,181,182))){
                    ?>
                        <td><input type="text" class="previsao_chegada" value="<?=$dados['previsao_chegada']?>" style="width: 140px;" /></td>
                    <?php
                    }
                    if ($login_fabrica == 171){
                    ?>
                    <td class='tac'><?=$dados["codigo_ferragens_negrao"]?></td>
                    <?php
                    }
                    ?>
                    <td nowrap <?=($login_fabrica == 162) ? $colspan2 : ""?>><?=$dados["nome_posto"]?></td>
		    <td class='tac'><?=$dados["data_emissao"]?></td>
		    <?php if(!in_array($login_fabrica,array(180,181,182))){ ?>
		    <td class='tac'><?=$dados["cfop"]?></td>
		    <?php } ?>
                    <td class='tac'><?=$total_nota_mostrar?></td>
                </tr>
                <tr class="titulo_coluna" >
                    <th colspan="<?=$colspan?>" >Itens</th>
                </tr>
                <tr>
                    <td colspan="<?=$colspan?>" style="padding: 0px;" >
                        <table class="table table-striped table-bordered pedidos" style="margin: 0 auto; width: 100%;" >
                            <thead>
                                <tr class="titulo_coluna" >
                                    <th>Pedido</th>
                                    <? if ($login_fabrica == 152) { ?>
                                        <th>Ordem</th>
                                    <? } else if ($login_fabrica == 162) { ?>
                                    <th>Rastreio</th>
                                    <? }
                                    if (in_array($login_fabrica, array(169,170))) { ?>
                                        <th>Docnum</th>
                                    <? } ?>
                                    <th><?=traduz('Peça')?></th>
                                    <th><?=traduz('Qtde Pendente')?></th>
                                    <th><?=traduz('Qtde Faturada')?></th>
                                    <th><?=traduz('Preço')?></th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($dados["pedidos"] as $pedido) {
                                    $preco_unitario = $pedido["preco_unitario"];

                                    /* hd_chamado=3109810 */
                                    $xpreco_unitario = "'".$preco_unitario."'";
                                    if(strpos($xpreco_unitario, "R$")){
                                        $preco_unitario = str_replace("R$", " ", $xpreco_unitario);
                                        $preco_unitario = str_replace("'", " ", $preco_unitario);
                                    }
                                    /* FIM hd_chamado=3109810 */
                                    if (strpos($preco_unitario, ",")) {
                                        $preco_unitario = str_replace(".", "", $preco_unitario);
                                        $preco_unitario = str_replace(",", ".", $preco_unitario);
                                    }

                                    $total                  = $preco_unitario * $pedido["quantidade_faturada"];
                                    $preco_unitario_mostrar = $preco_unitario;
                                    $total_mostrar          = $total;

                                    if ($login_fabrica == 156) {
                                        $preco_unitario_mostrar = 'R$ ' . number_format($preco_unitario, 2, ",", "");
                                        $total_mostrar = 'R$ ' . number_format($total, 2, ",", "");
                                    }
                                    ?>
                                    <tr>
                                        <td>
                                            <?=$pedido["pedido"]?>
                                            <input type="hidden" class="pedido" value="<?=$pedido['pedido']?>" />
                                            <input type="hidden" class="pedido_item" value="<?=$pedido['pedido_item']?>" />
                                            <input type="hidden" class="os" value="<?=$pedido['os']?>" />
                                            <input type="hidden" class="referencia_peca" value="<?=$pedido['referencia_peca']?>" />
                                            <input type="hidden" class="quantidade_pendente" value="<?=$pedido['quantidade_pendente']?>" />
                                            <input type="hidden" class="quantidade_faturada" value="<?=$pedido['quantidade_faturada']?>" />
                                            <input type="hidden" class="preco" value="<?=$preco_unitario?>" />
                                            <input type="hidden" class="total" value="<?=$total?>" />
                                            <?php if ($login_fabrica == 152) { ?>
                                                <input type="hidden" class="ordem" value="<?=$pedido['ordem']?>" />
                                            <?php } else if ($login_fabrica == 162) { ?>
                                                <input type="hidden" class="rastreio" value="<?=$pedido['rastreio']?>" />
                                            <?php }
                                            if (in_array($login_fabrica, array(169,170))) { ?>
                                                <input type="hidden" class="docnum" value="<?=$pedido['docnum']?>" />
                                            <?php } ?>
                                        </td>
                                        <?php  if ($login_fabrica == 152) { ?>
                                            <td><?=$pedido["ordem"]?></td>
                                        <?php } else if ($login_fabrica == 162) { ?>
                                            <td><?=$pedido["rastreio"]?></td>
                                        <?php }
                                        if (in_array($login_fabrica, array(169,170))) { ?>
                                            <td><?=$pedido["docnum"]?></td>
                                        <?php } ?>
                                        <td><?=$pedido["referencia_peca"]?> - <?=$pedido["descricao_peca"]?></td>
                                        <td class='tac'><?=$pedido["quantidade_pendente"]?></td>
                                        <td class='tac'><?=$pedido["quantidade_faturada"]?></td>
                                        <td class='tac'><?=$preco_unitario_mostrar?></td>
                                        <td class='tac'><?=$total_mostrar?></td>
                                    </tr>
                                <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <?php
                if (in_array($login_fabrica, [169,170]) && !empty($dados['motivo_troca'])) {

                    ?>
                    <tr class="titulo_coluna">
                        <th colspan="<?=$colspan?>" >Motivo da Troca</th>
                    </tr>
                    <tr>
                        <td class="motivo_troca tac" style="font-size: 16px;font-weight; bolder;" colspan="<?=$colspan?>" ><?= $dados['motivo_troca'] ?></td>
                    </tr>
                <?php
                }
                ?>
                <tr class="titulo_coluna" >
                    <th colspan="<?=$colspan?>" >Status</th>
                </tr>
                <tr>
                    <td class="status" colspan="<?=$colspan?>" ></td>
                </tr>
            </table>
            <br />
        <?php
        }
        ?>

        <hr />

    <?php
    }else{
        ?>
        <div class="alert alert-warning"><h4>No arquivo não foram encontrados pedidos para faturar</h4></div>
        <?php
    }

    if(count($pedido_faturado) > 0){
    ?>
        <table class='table table-striped table-bordered table-large' style="margin: 0 auto;" >
            <thead>
                <tr class='titulo_coluna' >
                    <th colspan="4" >Notas Fiscais já cadastradas no sistema</th>
                </tr>
                <tr class='titulo_coluna'>
                    <th>Nota Fiscal</th>
                    <th>Serie</th>
                    <th>Data de Emissão</th>
                    <th>Posto</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($pedido_faturado as $nota_fiscal => $value) {
                    $emissao = $value['emissao'];
                    $serie   = $value['serie'];
                    $cnpj    = $value['cnpj'];

                    if(strripos($emissao, "-") == true){
                        $emissao = formatar_data($emissao);
                    }

                    if($login_fabrica == 152){
                        $aux          = busca_info_posto($codigo_posto);
                    }else{
                        $aux          = busca_info_posto($cnpj);
                    }

                    $posto_nome   = $aux["nome"];

                    ?>
                    <tr class="pedido_item_<?=$value[$i]['pedido_item']?>">
                        <td class="tac"><?=$nota_fiscal?></td>
                        <td class="tac"><?=$serie?></td>
                        <td class="tac"><?=$emissao?></td>
                        <td class="tac"><?=$posto_nome?></td>
                    </tr>
                <?php
                }
                ?>
            </tbody>
        </table>
    <?php
    }
}

include "rodape.php";
?>
