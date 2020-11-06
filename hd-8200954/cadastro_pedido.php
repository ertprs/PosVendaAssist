<?php
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;
include_once __DIR__.'/class/AuditorLog.php';

if ($areaAdmin === true) {
    include __DIR__.'/dbconfig.php';
    include __DIR__.'/includes/dbconnect-inc.php';
    $admin_privilegios = "call_center";
    include __DIR__.'/admin/autentica_admin.php';
} else {
    include __DIR__.'/dbconfig.php';
    include __DIR__.'/includes/dbconnect-inc.php';
    include __DIR__.'/autentica_usuario.php';
}

if (!defined('DEVEL'))
    define ('DEVEL', ($_serverEnvironment == 'development'));

include __DIR__.'/funcoes.php';
include_once './class/communicator.class.php';
include_once './email_pedido.php';

function VerificaPecaAlternativa($peca, $qtde, $pedidoClass){
    global $login_posto, $login_fabrica;

    $sqlAlternativa = "SELECT * from tbl_peca_alternativa where fabrica = $login_fabrica and peca_de = $peca  AND status IS TRUE;";
    $resAlternativa = $pedidoClass->_model->getPDO()->query($sqlAlternativa);
    $dadosAlternativa = $resAlternativa->fetchAll(\PDO::FETCH_ASSOC);

    foreach($dadosAlternativa as $dados){
        $pecaAlternativaIntencao = $dados['peca_para'];
        if(VerificaEstoque($qtde, $pecaAlternativaIntencao, $pedidoClass)){
            return true;
        }
    }

    return false;
}

function temItemNoPedido($pedido) {
    global $con;

    $sql = "SELECT count(pedido_item) AS total_itens FROM tbl_pedido_item WHERE pedido = $pedido";
    $res = pg_query($con, $sql);
    
    if (pg_last_error()) {
        return false;
    }

    $total_itens = pg_fetch_result($res, 0, 'total_itens');

    if ($total_itens > 0) {
        return true;
    }

    return false;
}

function VerificaEstoque($qtde, $peca, $pedidoClass){

    global $login_posto, $login_fabrica;

    $sqlEstoque = "SELECT qtde FROM tbl_posto_estoque WHERE posto = 4311 
                    AND  qtde >= $qtde 
                    and peca = ".$peca;
    $resEstoque = $pedidoClass->_model->getPDO()->query($sqlEstoque);
    $dadosEstoque = $resEstoque->fetchAll(\PDO::FETCH_ASSOC);

    if(count($dadosEstoque)==0){
        return false;        
    }else{
        return true;
    }
}

function gravaIntencaoCompra($peca, $posto, $qtde, $pedido, $pedidoClass, $alternativa = null){
    global $login_posto, $login_fabrica;

    if($alternativa != null){
        $campoInformado = ", informado ";
        $valueInformado = ", true ";
        $updateInformado = ", informado = true ";
    }else{
        $campoInformado = "";
        $valueInformado = "";
        $updateInformado = "";
    }

    $sql = "SELECT fabrica, peca, posto, qtde, pedido, intencao_compra_peca 
            FROM tbl_intencao_compra_peca 
            WHERE posto = $posto
            AND pedido = $pedido 
            AND peca = $peca 
            AND fabrica = $login_fabrica ";
    $res = $pedidoClass->_model->getPDO()->query($sql);
    $dadosintencao = $res->fetchAll(\PDO::FETCH_ASSOC);

    if(count($dadosintencao)>0){
        $qtdeIntencao           = $dadosintencao[0]['qtde'];
        $intencao_compra_peca   = $dadosintencao[0]['intencao_compra_peca'];

        if($qtdeIntencao != $qtde ){
            $sqlIntencao = "UPDATE tbl_intencao_compra_peca set qtde = $qtde $updateInformado  where intencao_compra_peca = $intencao_compra_peca";    
            $resIntencao = $pedidoClass->_model->getPDO()->query($sqlIntencao);
        }

    }else{
        $sqlIntencao = "INSERT INTO tbl_intencao_compra_peca (fabrica, peca, posto, qtde, pedido $campoInformado) VALUES ($login_fabrica, $peca, $posto, $qtde, $pedido $valueInformado)";
        $resIntencao = $pedidoClass->_model->getPDO()->query($sqlIntencao);
    }        
}

function retiraPecaIndisponivelPedido($peca, $pedido, $pedidoClass){
    global $login_posto, $con, $login_fabrica;

    $sqlDelete = "DELETE FROM tbl_pedido_item where peca = $peca and pedido = $pedido ";
    $resDelete = $pedidoClass->_model->getPDO()->query($sqlDelete);
}

if ($_POST['ajax_carrega_linha']) {
    $xxposto = $_POST["posto"];

    $sqlT = "SELECT DISTINCT tbl_linha.linha, tbl_linha.nome
                        FROM tbl_posto_linha
                        JOIN tbl_linha USING(linha)
                        WHERE tbl_posto_linha.posto = $xxposto
                        AND tbl_linha.fabrica = $login_fabrica";
    $resT = pg_query($con, $sqlT);

    if (pg_num_rows($resT) > 0) {
        $retorno .= '<option value="">Selecione ...</option>';
        foreach (pg_fetch_all($resT) as $key => $row) {
            $retorno .= '<option value="'.$row["linha"].'">'.$row["nome"].'</option>';
        }

    } else {
        $retorno .= '<option value="">Nenhuma Linha encontrada</option>';
    }


    exit($retorno);
}

// Verifica se foi informado o tipo de frete no cadastro do posto
if(isset($_POST['verifica_frete_posto'])){

    $codigo_posto = $_POST['codigo_posto'];

    if($codigo_posto){

        $sql = "SELECT parametros_adicionais 
                FROM tbl_posto_fabrica 
                WHERE fabrica = $login_fabrica AND posto = $codigo_posto";

        $qry = pg_query($con, $sql);

        $parametros_adicionais = pg_fetch_result($qry, 0, 'parametros_adicionais');

        if($parametros_adicionais){

            $parametros_adicionais = json_decode($parametros_adicionais, true);
            if($parametros_adicionais['frete']){
               exit(json_encode(['tipo_frete' => $parametros_adicionais['frete']]));
            }    
        }
    }

    exit(json_encode(['tipo_frete' => false]));
}

$aux_pedido = $_GET['pedido'];
if(isset($_POST["verifica_condicao"])){

    $tipo_pedido = trim($_POST["tipo_pedido"]);

    $total_pecas = trim($_POST["total_pecas"]);
    $total_pecas = str_replace(".", "", $total_pecas);
    $total_pecas = str_replace(",", ".", $total_pecas);

    if($tipo_pedido == "Pedido WAP"){

        /*

        Tipo de Pedido: Pedido WAP

        Pedidos entre R$ 250,00 e R$ 449,99 >> 28 dias sem juros
        Pedidos entre R$ 450,00 e R$ 799,99 >> 28 e 56 dias sem juros
        Pedidos acima de R$ 800,00 >> 28, 56 e 84 dias sem juros

        */

        if($total_pecas == 0.00){

            $retorno = array(
                        "codigo"    => "",
                        "descricao" => ""
                    );

        }else if($total_pecas >= 250.00 && $total_pecas <= 449.99){

            $desc_condicao = traduz("28 dias sem juros");

        }else if($total_pecas >= 450.00 && $total_pecas <= 799.99){

            $desc_condicao = traduz("28 e 56 dias sem juros");

        }else if($total_pecas >= 800.00){

            $desc_condicao = traduz("28, 56 e 84 dias sem juros");

        }else{

            $retorno = array(
                        "codigo"    => "",
                        "descricao" => ""
                    );

        }

    }else{

        /*

        Tipo de Pedido: Ventila??o

        Pedidos entre R$ 100,00 e R$ 249,99 >> 28 dias sem juros
        Pedidos entre R$ 250,00 e R$ 499,99 >> 28 e 56 dias sem juros
        Pedidos acima de R$ 500,00 >> 28, 56 e 84 dias sem juros

        */

        if($total_pecas == 0.00){

            $retorno = array(
                        "codigo"    => "",
                        "descricao" => ""
                    );

        }else if($total_pecas >= 100.00 && $total_pecas <= 249.99){

            $desc_condicao = traduz("28 dias sem juros");

        }else if($total_pecas >= 250.00 && $total_pecas <= 499.99){

            $desc_condicao = traduz("28 e 56 dias sem juros");

        }else if($total_pecas >= 500.00){

            $desc_condicao = traduz("28, 56 e 84 dias sem juros");

        }else{

            $retorno = array(
                        "codigo"    => "",
                        "descricao" => ""
                    );

        }

    }

    if(strlen($desc_condicao) > 0){

        $sql_condicao = "SELECT condicao, descricao FROM tbl_condicao WHERE LOWER(descricao) = LOWER('{$desc_condicao}') AND fabrica = {$login_fabrica}";
        $res_condicao = pg_query($con, $sql_condicao);

        $descricao_condicao = $codigo_condicao = "";

        if(pg_num_rows($res_condicao) > 0){

            $codigo_condicao    = pg_fetch_result($res_condicao, 0, "condicao");
            $descricao_condicao = pg_fetch_result($res_condicao, 0, "descricao");

        }

        $retorno = array(
                        "codigo"    => $codigo_condicao,
                        "descricao" => $descricao_condicao
                    );

    }

    exit(json_encode($retorno));

}

if (in_array($login_fabrica, array(151))) {
    include __DIR__.'/os_cadastro_unico/fabricas/151/classes/Participante.php';
    $ParticipanteObj = new Participante();
    $limiteDisponivel = 0;

    if ((isset($_POST['ajax']) AND $_POST['ajax'] == 'sim') || $areaAdmin !== true) {
        $posto_pesquisa = isset($_POST['posto']) ? $_POST['posto'] : $login_posto;
        $sql = "SELECT cnpj FROM tbl_posto WHERE posto = {$posto_pesquisa}";
        $res_cnpj = pg_query($con, $sql);

        if (pg_num_rows($res_cnpj) > 0) {
            $cnpj_posto = pg_fetch_result($res_cnpj, 0, "cnpj");
            $dadosParticipante = array(
                "SdParmParticipante" => array(
                    "RelacionamentoCodigo"      => "AssistTecnica",
                    "ParticipanteTipoPessoa"    => (strlen($cnpj_posto) > 11) ? "J" : "F",
                    "ParticipanteFilialCPFCNPJ" => $cnpj_posto
                )
            );
            $result = $ParticipanteObj->verificaParticipante($dadosParticipante, true);
            if (isset($result)) {
                $limiteDisponivel = $result['SdSaiParticipante']['ParticipanteLimiteCreditoDispValor'];
            }
        }
        if ($areaAdmin == true) {
            exit(json_encode(array('ok' => $limiteDisponivel)));
        }
    }
}

if($login_fabrica == 168 and !empty($login_posto)){

    $sql_posto_bloqueio = "SELECT posto_bloqueio, desbloqueio FROM tbl_posto_bloqueio WHERE posto = $login_posto AND fabrica = $login_fabrica order by data_input desc limit 1";
    $res_posto_bloqueio = pg_query($con, $sql_posto_bloqueio);

    if(pg_num_rows($res_posto_bloqueio)>0){
        $desbloqueio = pg_fetch_result($res_posto_bloqueio, 0, 'desbloqueio');
    }
}

if ($_POST['ajax_condicao']) {

    if(!empty($_POST["condicao"])) {
        $condicao = $_POST['condicao'];

        $sql = "SELECT parcelas,acrescimo_financeiro,desconto_financeiro
            FROM tbl_condicao
            WHERE fabrica = $login_fabrica
            AND condicao = $condicao";

        $res = pg_query($con, $sql);

        $parcelas = pg_fetch_result($res, 0, 'parcelas');
        $acrescimo = pg_fetch_result($res, 0, 'acrescimo_financeiro');

        $acrescimo = ($acrescimo- 1) * 100;

        $desconto = pg_fetch_result($res, 0, 'desconto_financeiro');

        if ($acrescimo > 0.00) {
            $retorno['parcelas'] = $parcelas;
            $retorno['tipo_condicao'] = 'acrescimo';
            $retorno['valor'] = $acrescimo;
        } else if ($desconto > 0.00) {
            $retorno['parcelas'] = $parcelas;
            $retorno['tipo_condicao'] = 'desconto';
            $retorno['valor'] = $desconto;
        }

        exit(json_encode($retorno));
    }
    exit;
}

if ($login_fabrica == 183 AND !in_array($login_tipo_posto_codigo, array("Rev", "Rep"))) {
    
    $sql = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE posto = {$login_posto} AND fabrica = {$login_fabrica}";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0){
        $parametros_adicionais = pg_fetch_result($res, 0, 'parametros_adicionais');
        $parametros_adicionais = json_decode($parametros_adicionais, true);
        $percentual_posto = $parametros_adicionais["encontro_de_contas"];
    }
    $sql_saldo = "
        SELECT 
            SUM(tbl_os.mao_de_obra) AS saldo_disponivel
        FROM tbl_os 
        LEFT JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
        WHERE tbl_os.fabrica = {$login_fabrica}
        AND tbl_os_extra.extrato IS NULL
        AND tbl_os.finalizada IS NOT NULL
        AND tbl_os.posto = {$login_posto}";
    $res_saldo = pg_query($con, $sql_saldo);

    if (pg_num_rows($res_saldo) > 0 AND !empty($percentual_posto)){
        $saldo_encontro_contas = pg_fetch_result($res_saldo, 0, 'saldo_disponivel');
        
        if (strlen($saldo_encontro_contas) > 0){
            $tem_saldo_encontro_contas = true;
            $saldo_encontro_contas_disponivel = ($saldo_encontro_contas / 100 * $percentual_posto);
        }else{
            $tem_saldo_encontro_contas = false;
            $saldo_encontro_contas_disponivel = "0,00";
        }
    }else{
        $tem_saldo_encontro_contas = false;
        $saldo_encontro_contas_disponivel = "0,00";
    }
    
    $sql_debito = "
        SELECT 
            SUM(tbl_pedido.total) AS total_compras
        FROM tbl_pedido
        JOIN tbl_condicao ON tbl_condicao.condicao = tbl_pedido.condicao AND tbl_condicao.fabrica = {$login_fabrica}
        WHERE tbl_pedido.fabrica = {$login_fabrica}
        AND tbl_condicao.visivel_acessorio IS TRUE
        AND tbl_pedido.data BETWEEN to_char(now(),'YYYY-MM-01')::date AND now()
        AND tbl_pedido.posto = {$login_posto}";
    $res_debito = pg_query($con, $sql_debito);

    if (pg_num_rows($res_debito) > 0){
        $debito_encontro_contas = pg_fetch_result($res_debito, 0, 'total_compras');
    }

    if (!empty($saldo_encontro_contas_disponivel) AND !empty($debito_encontro_contas)){
        $saldo_encontro_contas_disponivel = ($saldo_encontro_contas_disponivel - $debito_encontro_contas);
    }

}   

if (isset($_POST["ajax_upload_peca"])) {
    $arquivo     = $_FILES["upload_peca"];
    $tipo_pedido = $_POST["tipo_pedido"];

    if(empty($tipo_pedido)){
        $tipo_pedido = $_POST["upload_peca_tipo_pedido"];
    }

    if ($areaAdmin === true) {
        $tabela = $_POST["tabela"];
    }

    if ($login_fabrica == 146) {
        $marca = $_POST["marca"];
    }

    if (!empty($arquivo["tmp_name"]) && $arquivo["size"] > 0) {
        $ext = preg_replace("/.+\./", "", $arquivo["name"]);

        if (strtolower($ext) == "csv") {
            $retorno = array(
                "erros" => array(),
                "pecas" => array()
            );

            // file_get_contents($arquivo["tmp_name"]);
            $conteudo = explode("\n", trim(file_get_contents($arquivo["tmp_name"])));

            // Se consegue passar a valida??o do Javascript, bloquear aqui tamb?m.
            if (count($conteudo) > 500)
                die(json_encode(
                    array(
                        "erros" => array(traduz("arquivo.com.mais.de.500.linhas")),
                        "pecas" => array()
                    )
                ));

            if (count($conteudo) > 0) {
                if ($areaAdmin !== true) {
                    $sql = "SELECT uso_consumo, descricao
                            FROM tbl_tipo_pedido
                            WHERE fabrica = $login_fabrica
                            AND tipo_pedido = $tipo_pedido";
                    //die(nl2br($sql));
                    $res_tipo = pg_query($con, $sql);

                    $uso_consumo           = pg_fetch_result($res_tipo, 0, "uso_consumo");
                    $descricao_tipo_pedido = pg_fetch_result($res_tipo, 0, "descricao");

                    if ($uso_consumo != "t") {
                        //$coluna_tabela_preco = "tabela_posto";
                        $coluna_tabela_preco = (in_array($login_fabrica, array(35))) ? "tabela":"tabela_posto";
                    } else {
                        $coluna_tabela_preco = "tabela_bonificacao";

                        if ($login_fabrica == 143) {
                            $coluna_tabela_preco = "tabela_posto";
                        }
                    }

                    $sqlT = "SELECT $coluna_tabela_preco AS tabela
                             FROM tbl_posto_linha
                             INNER JOIN tbl_linha USING(linha)
                             WHERE posto = $login_posto
                             AND fabrica = $login_fabrica
                             LIMIT 1";
                    //die(nl2br($sqlT));                    
                    $resT = pg_query($con, $sqlT);

                    if (pg_num_rows($resT) > 0) {
                        $tabela = pg_fetch_result($resT, 0, 0);                        
                    }
                }

                if ($areaAdmin !== true) {
                    $condicaoItemAparencia = " tbl_posto.posto = $login_posto ";
                }else if(!empty($_POST['upload_posto_codigo'])){
                    $condicaoItemAparencia = " tbl_posto_fabrica.codigo_posto = '".$_POST['upload_posto_codigo']."' ";
                }

                if(strlen($condicaoItemAparencia) > 0){
                    $sql = "SELECT  tbl_posto_fabrica.item_aparencia FROM tbl_posto
                            JOIN tbl_posto_fabrica USING(posto)
                        WHERE {$condicaoItemAparencia}
                            AND tbl_posto_fabrica.fabrica = $login_fabrica";
                    //die(nl2br($sql));
                    $resPosto = pg_query($con,$sql);

                    if (pg_num_rows($resPosto) > 0) {
                        $posto_item_aparencia = pg_fetch_result($resPosto, 0, 'item_aparencia');
                    }
                }else{
                    $posto_item_aparencia = 'f';
                }

                if ($login_farica == 157) {
                    $sql = "SELECT descricao FROM tbl_tipo_pedido WHERE fabrica = $login_fabrica AND tipo_pedido = $tipo_pedido";
                    $res = pg_query($con,$sql);

                    if(pg_num_rows($res) > 0){
                        $descricao_tipo_pedido = pg_fetch_result($res, 0, "descricao");
                        $descricao_tipo_pedido = str_replace("??","ca",$descricao_tipo_pedido);

                    }else{
                        $descricao_tipo_pedido = $tipo_pedido;
                    }

                    if (strtoupper($descricao_tipo_pedido) == "PEDIDO WAP") {
                        $whereLinha = "AND UPPER(fn_retira_especiais (tbl_linha.nome)) != 'LAVADORAS  CASA  JARDIM' AND tbl_linha.linha <> 948 ";
                    } else if (strtoupper($descricao_tipo_pedido) == "VENTILADORES") {
                        $whereLinha = "AND UPPER(fn_retira_especiais (tbl_linha.nome)) = 'LAVADORAS  CASA  JARDIM' AND tbl_linha.linha = 948 ";
                    }else if(strtoupper($descricao_tipo_pedido) == "VENTILACAO"){
                        $whereLinha = " AND tbl_linha.linha = 948 ";
                    }

                    $sql = "SELECT DISTINCT
                                   tbl_peca.peca,
                                   fn_retira_especiais (tbl_peca.descricao) AS descricao,
                                   tbl_peca.item_aparencia,
                                   tbl_peca.multiplo,
                                   tbl_peca.ipi
                              FROM tbl_lista_basica
                        INNER JOIN tbl_produto ON tbl_produto.fabrica_i = $login_fabrica
                                              AND tbl_produto.produto   = tbl_lista_basica.produto
                        INNER JOIN tbl_peca    ON tbl_peca.fabrica      = $login_fabrica
                                              AND tbl_peca.peca         = tbl_lista_basica.peca
                        INNER JOIN tbl_linha   ON tbl_linha.linha       = tbl_produto.linha
                                              AND tbl_linha.fabrica     = $login_fabrica
                             WHERE tbl_lista_basica.fabrica = $login_fabrica
                               AND tbl_peca.ativo IS TRUE
                               AND tbl_peca.produto_acabado IS NOT TRUE
                               AND UPPER(tbl_peca.referencia) = $1
                               $whereLinha
                        ;";
                } else {

                    // Prepara algumas consultas SQL no banco para agilizar
                    if ($login_fabrica == 146) {
                        $joinMarca = "INNER JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca AND tbl_marca.fabrica = {$login_fabrica}";
                        $whereMarca = "AND tbl_produto.marca = {$marca}";
                    }

                    if (!($login_fabrica == 153 && $areaAdmin == true)) {
                        $whereProdutoAcabado = "AND tbl_peca.produto_acabado IS NOT TRUE";
                    }

                    $sql = "SELECT tbl_peca.peca,
                            fn_retira_especiais (tbl_peca.descricao) AS descricao,
                            tbl_peca.multiplo,
                            tbl_peca.item_aparencia,
                            tbl_peca.ipi
                        FROM tbl_peca
                        {$joinMarca}
                        WHERE tbl_peca.fabrica = {$login_fabrica}
                        AND UPPER(tbl_peca.referencia) = $1
                        AND tbl_peca.ativo IS TRUE
                        AND tbl_peca.bloqueada_venda IS NOT TRUE
                        {$whereProdutoAcabado}
                        {$whereMarca}";

                        //die(nl2br($sql));
                }
                // consulta Peça pela refer?ncia. Par?metro: $referencia
                pg_prepare($con, 'pecaPorReferencia', $sql);

                // Consulta de->para. Par?metro: $referencia
                pg_prepare(
                    $con, 'CheckDePara',
                    "SELECT depara, para
                       FROM tbl_depara
                      WHERE de = '$1' AND fabrica = $login_fabrica
                        AND (expira IS NULL
                         OR expira >= CURRENT_TIMESTAMP)"
                );

                // Consulta Peça fora de Linha
                pg_prepare(
                    $con, 'checkPecaforaLinha',
                    "SELECT peca_fora_linha
                       FROM tbl_peca_fora_linha
                      WHERE fabrica = $login_fabrica
                        AND referencia = '$1'"
                );

                // Pre?o da Peça segundo a tabela. Par\E2metros: $tabela, $peca
                pg_prepare(
                    $con, 'precoPecaTabela',
                    "SELECT preco FROM tbl_tabela_item WHERE tabela = $1 AND peca = $2"
                );

                foreach ($conteudo as $peca) {
                    if (empty($peca)) {
                        continue;
                    }

                    list($referencia, $qtde) = explode(";", $peca);

                    if (empty($referencia)) {
                        continue;
                    }

                    $referencia = strtoupper(trim($referencia));
                    $qtde       = trim($qtde);

                    /**
                     * Verifica se existe registro na tbl_depara,
                     * caso exista registro e n?o esteja expirado,
                     * atribue a refer?ncia da Peça "para" na vari?vel $referencia
                     */
                    $sqlDepara = "SELECT depara, para
                       FROM tbl_depara
                      WHERE de = '$referencia' AND fabrica = $login_fabrica
                        AND (expira IS NULL
                         OR expira >= CURRENT_TIMESTAMP)";
					$resDepara = pg_query($con, $sqlDepara);

                    if(pg_num_rows($resDepara) > 0){
                        $referencia_antiga = $referencia;
                        $referencia = pg_fetch_result($resDepara, 0, "para");

                        $retorno["erros"][] = 
                        utf8_encode(traduz("codigo") . " ".  $referencia_antiga . " " . traduz("obsoleto.peca.substituida.de") . " <span style='text-decoration:line-through'>{$referencia_antiga}</span> ".traduz("para")."  {$referencia},".traduz("que.sera.usada.para.o.pedido.revise.outras.ocorrencias.no.arquivo")." .<br/>");
                    }
                    
                    $res = pg_execute($con, 'pecaPorReferencia', array($referencia));

                    if (!pg_num_rows($res)) {
                        $retorno["erros"]['not_found'][] = $referencia;

                        /* if (DEVEL === true and pg_last_error($con)) { */
                        /*  $retorno['erros'][] = pg_last_error($con); */
                        /*  $retorno['erros'][] = $sql; */
                        /*  break; */
                        /* } */
                        continue;
                    }

                    $peca      = pg_fetch_result($res, 0, "peca");
                    $descricao = pg_fetch_result($res, 0, "descricao");
                    $multiplo  = pg_fetch_result($res, 0, "multiplo");
                    $ipi       = pg_fetch_result($res, 0, "ipi");
                    $peca_item_aparencia = pg_fetch_result($res, 0, "item_aparencia");

                    if($posto_item_aparencia == 'f' && $peca_item_aparencia == 't'){
                        $retorno["erros"][] = utf8_encode(traduz("a.peca") . ' '. $referencia . ' ' .traduz("nao.esta.habilitada.para.pedidos"));
                        continue;
                    }

                    /* VERIFICA SE A PE\C7A EST? FORA DE LINHA */
                    $resForaLinha = pg_execute($con, 'checkPecaforaLinha', array($referencia));

                    if(pg_num_rows($resForaLinha) > 0){
                        $retorno["erros"][] = utf8_encode(traduz("peca").' '. $referencia .' ' . traduz("fora.de.linha"));
                        continue;
                    }

                    if (!empty($tabela)) {                        
                        $resT = pg_execute($con, 'precoPecaTabela', array($tabela, $peca));                        

                        $preco = (pg_num_rows($resT) == 1) ? number_format(pg_fetch_result($resT, 0, "preco"), 2, ",", ".") : "";

                        if(!empty($preco)){
                            $retorno["pecas"][] = array(
                                "peca"       => $peca,
                                "referencia" => $referencia,
                                "descricao"  => $descricao,
                                "multiplo"   => $multiplo,
                                "preco"      => $preco,
                                "ipi"        => $ipi,
                                "qtde"       => $qtde
                            );
                        }else{
                            $retorno["erros"]["preco"][] = $referencia;
                        }
                    } else {
                        // $preco = "";
                        $retorno["erros"]['preco'][] = $referencia;
                    }
                }
            } else {
                $retorno["erros"][] = utf8_encode(traduz("arquivo.vazio"));
            }
        } else {
            $retorno["erros"][] = utf8_encode(traduz("deve.ser.selecionado.um.arquivo.csv"));
        }
    } else {
        $retorno["erros"][] = utf8_encode(traduz("selecione.o.arquivo"));
    }

    if (isset($retorno['erros']['preco'])) {
        $pecas_erro = $retorno['erros']['preco'];
        if (count($pecas_erro) > 10) {
            $lista_pecas = implode(', ', $pecas_erro);
            $retorno['erros'][] = utf8_encode(traduz("peca.s") . " {$lista_pecas} " . traduz("sem.preco.nao.serao.inseridas.no.pedido") . ".<br />");
        } else {
            foreach ($pecas_erro as $ref) {
                $retorno['erros'][] = utf8_encode(traduz("peca") . " $ref " . traduz("sem.preco.excluida.do.pedido") . " .<br />");
            }
        }
        unset($retorno['erros']['preco']);
    }

    if (isset($retorno['erros']['not_found'])) {
        $pecas_erro = $retorno['erros']['not_found'];
        if (count($pecas_erro) > 10) {
            $lista_pecas = implode(', ', $pecas_erro);
            $retorno['erros'][] = utf8_encode(traduz("peca.s") . " {$lista_pecas} " . traduz("nao.encontrada.s.ou.nao.habilitada.s.para.esse.tipo.de.pedido") . " .<br />");
        } else {
            foreach ($pecas_erro as $ref) {
                $retorno['erros'][] = utf8_encode(traduz("peca") ." $ref " . traduz("nao.encontrada.ou.nao.habilitada.para.esse.tipo.de.pedido") . " .<br />");
            }
        }
        unset($retorno['erros']['not_found']);
    }

    exit(json_encode($retorno));
}

if($areaAdmin === false){

    $sql = "SELECT pedido_faturado,pedido_em_garantia FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
    $res = pg_query ($con,$sql);

    $pedido_faturado = pg_fetch_result($res,0,'pedido_faturado');
    $pedido_em_garantia = pg_fetch_result($res,0,'pedido_em_garantia');

    //pedido de venda e pedido garantia (bloquar tudo)
    //pedido de venda so mostrar tipo -pedido nao for de garantia
    //pedido de garantia === true  and pedido faturado === false liberar apenas tipo_pedido garantia ;

    if ($pedido_em_garantia == 'f' and $pedido_faturado == 'f' and $areaAdmin==false) {

        $title = traduz("cadastro.de.pedidos.de.pecas");
        include __DIR__.'/cabecalho_new.php';

            echo "<br><br>\n";
            echo "<table class='table table-striped table-bordered table-hover table-large' >";
            echo "<TR>";
            if($login_fabrica == 153 or $login_fabrica == 168) {
                echo "<td><H4 style='text-align:center'>".traduz("desativado.temporariamente")."</H4></td>";
            }else{



                if ($login_fabrica == 151) {
                echo "
                <td style='font-size: 11pt;'>
                    <H4 style='text-align:center'>".traduz("cadastro.de.pedido.bloqueado.pelo.financeiro").".</H4>
                    ".traduz("entre.em.contato.para.esclarecimento.atraves.do")." <b>0800 345 4589</b> - ".traduz("de.segunda.as.sextas.feiras.no.periodo.das.8hs.as.18hs.ou.envie.e.mail.para").":<br /><br />
                <ul>
                <li>".traduz("regioes.norte.nordeste.e.centro.oeste").": Sap1@mondialline.com.br</li>
                <li>".traduz("sul.e.sudeste").": Sap2@mondialline.com.br</li>
                </ul>
                <br />
                <br />
                ".traduz("no.campo.assunto.mencione").": <u>".traduz("bloqueio.de.pedido.codigo.do.posto")."</u></td>";
                }else if ($login_fabrica == 177){
                    echo "<td><H4 style='text-align:center'>".traduz("cadastro.de.pedido.bloqueado.pelo.financeiro").".</H4></td>";
		}else if($login_fabrica == 35){
	       		echo "<td><H4 style='text-align:center'>".traduz("cadastro.de.pedido.bloqueado.pelo.financeiro").".<br /> ".traduz("para.detalhes.envie.um.e.mail.para")." cad-boleto@newellco.com ".traduz("com.o.numero.do.cnpj.da.sua.empresa").".</H4></td>";
		}else {
                    echo "<td><H4 style='text-align:center'>".traduz("cadastro.de.pedido.bloqueado.pelo.financeiro").",<br /> ".traduz("favor.entrar.em.contato.com.a.fabrica.para.envio.do.produto").".</H4></td>";
                }


            }
            echo "</form>";
            echo "</TR>";
            echo "</table><br /><br />";

        // echo "<br /><br /><H4>Cadastro de pedido bloqueado pelo financeiro,<br /> favor entrar em contato com a f?brica.</H4><br /><br />";

        include "rodape.php";
        exit;

    }

    $sql = "SELECT tbl_linha.linha FROM tbl_posto_linha INNER JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha AND tbl_linha.fabrica = {$login_fabrica} WHERE tbl_posto_linha.posto = {$login_posto}";
    $res = pg_query($con, $sql);

    if (!pg_num_rows($res)) {
        include __DIR__."/cabecalho_new.php";
        echo "<div class='alert alert-warning' ><h5>".traduz("pedido.de.venda.nao.configurado.entre.em.contato.com.o.fabricante").".</h5></div>";
        include "rodape.php";
        exit;
    }

    if(in_array($login_fabrica, array(164))){

        $valor_frete_estado = 0;

        $sql = "SELECT contato_estado FROM tbl_posto_fabrica WHERE fabrica = {$login_fabrica} AND posto = {$login_posto}";
        $res = pg_query($con, $sql);

        if(pg_num_rows($res) > 0){

            $estado_posto = pg_fetch_result($res, 0, "contato_estado");

            $sql = "SELECT valor_frete FROM tbl_transportadora_padrao WHERE fabrica = {$login_fabrica} AND estado = '{$estado_posto}' ";
            $res = pg_query($con, $sql);

            if(pg_num_rows($res) > 0){
                $valor_frete_estado = pg_fetch_result($res, 0, "valor_frete");
            }

        }

    }
}

if(isset($_POST["busca_frente_estado"])){

    $posto = trim($_POST["posto"]);

    $valor_frete_estado = 0;

    $sql = "SELECT contato_estado FROM tbl_posto_fabrica WHERE fabrica = {$login_fabrica} AND posto = {$posto}";
    $res = pg_query($con, $sql);

    if(pg_num_rows($res) > 0){

        $estado_posto = pg_fetch_result($res, 0, "contato_estado");

        $sql = "SELECT valor_frete FROM tbl_transportadora_padrao WHERE fabrica = {$login_fabrica} AND estado = '{$estado_posto}' ";
        $res = pg_query($con, $sql);

        if(pg_num_rows($res) > 0){
            $valor_frete_estado = pg_fetch_result($res, 0, "valor_frete");
        }

    }

    exit(number_format($valor_frete_estado, 2, ",", "."));

}

include_once "class/aws/s3_config.php";
include_once S3CLASS;

$s3 = new AmazonTC("pedido", $login_fabrica);

if (in_array($login_fabrica, array(163))) {
    include_once __DIR__ . DIRECTORY_SEPARATOR . 'class/tdocs.class.php';
    $tDocs   = new TDocs($con, $login_fabrica);

    /**
    * Cria a chave do anexo
    */
    if (!strlen(getValue("anexo_chave"))) {
        $anexo_chave = sha1(date("Ymdhi")."{$login_fabrica}".(($areaAdmin === true) ? $login_admin : $login_posto));
    } else {
        $anexo_chave = getValue("anexo_chave");
    }

    /**
    * Inclui no TDocs
    */
    if (isset($_POST['ajax_anexo_upload'])) {

        $posicao = $_POST["anexo_posicao"];
        $chave   = $_POST["anexo_chave"];

        $arquivo = $_FILES["anexo_upload_{$posicao}"];

        $ext = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));

        if ($ext == 'jpeg') {
            $ext = 'jpg';
        }

        if (strlen($arquivo['tmp_name']) > 0) {
            if (!in_array($ext, array('png', 'jpg', 'jpeg', 'bmp', 'pdf', 'doc', 'docx'))) {
                $retorno = array('error' => utf8_encode(traduz("arquivo.em.formato.invalido.sao.aceitos.os.seguintes.formatos.png.jpeg.bmp.pdf.doc.docx")));
            } else {

                // Se enviou um outro arquivo, este substitui o anterior
                if ($_FILES["anexo_upload_{$posicao}"]["tmp_name"]) {

                    $anexoID = $tDocs->sendFile($_FILES["anexo_upload_{$posicao}"]);
                    $arquivo_nome      = json_encode($tDocs->sentData);

                    if (!$anexoID) {
                        $retorno = array('error' => utf8_encode(traduz('erro.ao.anexar.arquivo')));
                    } else {
                        // Se ocorrer algum erro, o anexo est? salvo:
                        if (isset($idExcluir)) {
                            $tDocs->deleteFileById($idExcluir);
                        }
                    }
                }

                if (empty($anexoID)) {
                    $retorno = array('error' => utf8_encode(traduz('erro.ao.anexar.arquivo')));
                }

                if ($ext == 'pdf') {
                    $link = 'imagens/pdf_icone.png';
                } else if(in_array($ext, array('doc', 'docx'))) {
                    $link = 'imagens/docx_icone.png';
                } else {
                    $link = '//api2.telecontrol.com.br/tdocs/document/id/'.$anexoID;
                }

                $href = '//api2.telecontrol.com.br/tdocs/document/id/'.$anexoID;

                if (!strlen($link)) {
                    $retorno = array('error' => utf8_encode(' 2'));
                } else {
                    $retorno = compact('link', 'arquivo_nome', 'href', 'ext', 'posicao');
                }
            }
        } else {
            $retorno = array('error' => utf8_encode(traduz('erro.ao.anexar.arquivo')));
        }
        exit(json_encode($retorno));
    }
}

if ($areaAdmin === false and $login_pede_peca_garantia == 't') {
    if(in_array($login_fabrica, array(143))){
        include __DIR__.'/cabecalho_new.php';

        echo "
        <br />
        <br />
        <div class='container'>
        <div class='alert'>
            <h4><a href= 'cadastro_os.php'> ".traduz('abertura.de.pedidos.bloqueada').", <p> ".traduz("por.favor.fazer.abertura.de.os")." </p> </a></h4>
        </div>
        </div>";

        include "rodape.php";

        exit;
    }

}


if($areaAdmin == true){

    $suframa_status = false;

    function regiao_suframa($set_suframa = false){

        global $suframa_status;

        if($set_suframa == true){
            $suframa_status = true;
        }

        return $suframa_status;

    }

}else{

    function regiao_suframa(){

        global $login_posto, $con;

        $sql_suframa = "SELECT suframa FROM tbl_posto WHERE posto = {$login_posto}";
        $res_suframa = pg_query($con, $sql_suframa);

        $suframa = pg_fetch_result($res_suframa, 0, "suframa");

        return ($suframa == "t") ? true : false;

    }

}

$btn_acao   = strtolower($_REQUEST['btn_acao']);
$hd_chamado = strtolower($_REQUEST['callcenter']);

use Posvenda\Pedido;
$reprocessamento = \Posvenda\Regras::get("reprocessamento", "pedido_venda", $login_fabrica);
$reprocessamento_status = \Posvenda\Regras::get("reprocessamento_status", "pedido_venda", $login_fabrica);
$not = (strlen($reprocessamento_status)>0 ) ?  $reprocessamento_status : 14;

$auditoria_pedido_obrigatoria = \Posvenda\Regras::get("auditoria_pedido_obrigatoria", "pedido_venda", $login_fabrica);

if(in_array($login_fabrica,[35,179])){
    $qtde_item = 5 ;
}else{
    $qtde_item = 20 ;
}

if ($catalogoPedido) {
    $qtde_item = 3;
}


if (isset($_POST["qtde_item"])) {
    $qtde_item = $_POST["qtde_item"];
}

if(!empty($login_posto)) {
    $sql = "SELECT tbl_pedido.pedido
            FROM tbl_pedido
            LEFT JOIN tbl_os_item ON tbl_os_item.pedido = tbl_pedido.pedido
            WHERE tbl_pedido.fabrica = $login_fabrica
            AND tbl_pedido.posto = $login_posto
            AND tbl_pedido.exportado IS NULL
            AND tbl_pedido.pedido_os is not true
            AND tbl_pedido.status_pedido not in (14,{$not})
            AND tbl_pedido.admin is null
            AND tbl_pedido.finalizado is null
            AND tbl_os_item.os_item IS NULL
            ORDER BY tbl_pedido.pedido DESC
            LIMIT 1";
    $res = pg_query($con,$sql);
    if(pg_numrows($res) > 0){
        $cook_pedido = pg_result($res,0,pedido);
        $_COOKIE['cook_pedido'] = $cook_pedido;
        if(!empty($cook_pedido) and count($_POST) == 0 ) {
            $sql = "SELECT count(1) FROM tbl_pedido_item WHERE pedido = $cook_pedido";
            $res = pg_query($con,$sql);
            if(pg_num_rows($res) > 0) {
                $qtde_item = pg_fetch_result($res,0,0);
                $qtde_item = ($qtde_item == 0 ) ? 20:$qtde_item;
            }
        }
    }
}

if(strlen($login_posto) > 0){

    $sql_desconto = "SELECT desconto FROM tbl_posto_fabrica WHERE posto = {$login_posto} AND fabrica  = {$login_fabrica}";
    $res_desconto = pg_query($con, $sql_desconto);
    $desconto_posto = (pg_num_rows($res_desconto) > 0) ? pg_fetch_result($res_desconto, 0, 'desconto') : 0;

}

if(strlen($_GET["pedido"]) && $areaAdmin === true){

    $pedido_id = $_GET["pedido"];

    $sql_desconto = "SELECT desconto FROM tbl_posto_fabrica WHERE posto IN (SELECT posto FROM tbl_pedido WHERE pedido = {$pedido_id} AND fabrica = {$login_fabrica}) AND fabrica = {$login_fabrica}";
    $res_desconto = pg_query($con, $sql_desconto);
    $desconto_posto = (pg_num_rows($res_desconto) > 0) ? pg_fetch_result($res_desconto, 0, 'desconto') : 0;

}

if(isset($_POST["verifica_tipo_pedido"]) && $_POST["verifica_tipo_pedido"] == "ok"){

    if(strlen($_POST['posto']) > 0 || ($login_fabrica == 147 && $areaAdmin === false)) {

        $login_posto = (strlen($_POST['posto']) > 0) ? $_POST['posto'] : $login_posto;

        $sql_desconto = "SELECT desconto FROM tbl_posto_fabrica WHERE posto = {$login_posto} AND fabrica  = {$login_fabrica}";

        $res_desconto = pg_query($con, $sql_desconto);
        $desconto_posto = (pg_num_rows($res_desconto) > 0) ? pg_fetch_result($res_desconto, 0, 'desconto') : 0;

        if (!strlen($desconto_posto)) {
            $desconto_posto = 0;
        }
    }

    if(strlen($_POST['pedido']) > 0 ) {
        $pedido_old = $_POST['pedido'];

        $sql_pedido_tabela = "SELECT tabela FROM tbl_pedido WHERE fabrica = {$login_fabrica} AND pedido = {$pedido_old}";
        $res_tabela = pg_query($con,$sql_pedido_tabela);
        $velha_tabela = pg_fetch_result($res_tabela, 0, "tabela");
    }

    $tipo_pedido = $_POST["tipo_pedido"];

    $sql_tipo_pedido = "SELECT pedido_faturado FROM tbl_tipo_pedido WHERE ativo AND visivel AND tipo_pedido = {$tipo_pedido} AND fabrica = {$login_fabrica}";
    $res_tipo_pedido = pg_query($con, $sql_tipo_pedido);

    if (pg_num_rows($res_tipo_pedido) > 0) {
        if((strlen($pedido_old) or strlen($_POST['tabela_velha'])) && !empty($_POST["pedido"])) {
            $pedidoClass = new Pedido($login_fabrica);
            if ($login_fabrica == 183 AND strlen($_POST['linha']) > 0){
		$linha = $_POST["linha"];
	    	$nova_tabela = $pedidoClass->_model->getTabelaPreco($login_posto, $tipo_pedido, $os, $linha);
	    }else{
	    	$nova_tabela = $pedidoClass->_model->getTabelaPreco($login_posto, $tipo_pedido);
	    }

            if(($nova_tabela <> $velha_tabela && $areaAdmin === false) OR ($nova_tabela <> $_POST['tabela_velha'] && $areaAdmin === true)){
                $retorno["diferente"] = true;
            }else{
                $retorno["diferente"] = false;
            }
        }

        if ($desconto_posto > 0){
            $usa_desconto = \Posvenda\Regras::get("usa_desconto", "pedido_venda", $login_fabrica);
            $usa_desconto_tipo_pedido = \Posvenda\Regras::get("usa_desconto_tipo_pedido", "pedido_venda", $login_fabrica);
            if($usa_desconto == true ){
                 $retorno['desconto'] = $desconto_posto;
                $retorno['usa_desconto'] = 'sim';

                if(is_array($usa_desconto_tipo_pedido) and count($usa_desconto_tipo_pedido) > 0 and !in_array($tipo_pedido, $usa_desconto_tipo_pedido) ){
                    $retorno['usa_desconto'] = 0;
                    $retorno['usa_desconto'] = 'nao';
                }
            }else{
                $retorno['usa_desconto'] = 0;
                $retorno['usa_desconto'] = 'nao';
            }
        } else {
            if($login_fabrica == 160 or $replica_einhell){
                $sql = "select descontos[1] from tbl_tipo_posto inner join tbl_posto_fabrica on tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto and tbl_posto_fabrica.fabrica = $login_fabrica where posto = $login_posto";
                $res = pg_query($con, $sql);
                if(pg_num_rows($res)>0){
                    $desconto_tipo_posto = pg_fetch_result($res, 0, descontos);

                    if($desconto_tipo_posto > 0){
                        $retorno["desconto"] = $desconto_tipo_posto;
                        $retorno["usa_desconto"] = 'sim';
                    }else{
                        $retorno["desconto"] = 0;
                        $retorno["usa_desconto"] = 'nao';
                    }
                }
            }else{
                $retorno["desconto"] = 0;
                $retorno["usa_desconto"] = 'nao';
            }
        }
    }
    exit(json_encode($retorno));
}


if(isset($_POST["verifica_tabela_ideal"]) && $_POST["verifica_tabela_ideal"] == "ok"){

    $posto = $_POST['posto_id'];
    $tipo_pedido = $_POST['tipo_pedido'];


    $pedidoClass = new Pedido($login_fabrica);
    $nova_tabela = $pedidoClass->_model->getTabelaPreco($posto, $tipo_pedido);
    $retorno['tabela'] = $nova_tabela;

    exit(json_encode($retorno));

}

if(isset($_POST["altera_valor_peca"]) && $_POST["altera_valor_peca"] == "ok"){
    $peca = $_POST["peca"];

    if ($areaAdmin === true) {
        $tabela= $_POST['tabela'];
    } else {
        $posto = $_POST['posto_id'];
        $tipo_pedido = $_POST["tipo_pedido"];

        $pedidoClass = new Pedido($login_fabrica);
        $tabela = $pedidoClass->_model->getTabelaPreco($posto, $tipo_pedido);
    }

    if (strlen($tabela)) {
        $sqlT = "SELECT preco FROM tbl_tabela_item WHERE tabela = {$tabela} AND peca = {$peca}";
        $resT = pg_query($con,$sqlT);

        if (pg_num_rows($resT) > 0) {
            $precoAltera = pg_fetch_result($resT,0,preco);
            $retorno = array("preco_peca" => number_format($precoAltera,2,",","."));
        }else{
            $retorno = array("preco_peca" => "0,00");
        }
    }else{
        $retorno = array("preco_peca" => "0,00");
    }

    exit(json_encode($retorno));

}

if ($_POST["ajax_atualiza_total"] == true) {
    $pedido = $_POST["pedido"];

    if (!empty($pedido)) {
        $sql = "SELECT pedido FROM tbl_pedido WHERE fabrica = $login_fabrica AND pedido = $pedido";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $total = str_replace(",", ".", str_replace(".", "", $_POST["total"]));

            $sql = "UPDATE tbl_pedido SET total = $total WHERE fabrica = $login_fabrica AND pedido = $pedido";
            $res = pg_query($con, $sql);
        }
    }

    exit;
}

if($_POST["ajax_deletar_item_pedido"] == "deletar"){
    try {
        $transaction = false;

        $pedido      = $_POST['pedido'];
        $pedido_item = $_POST['pedido_item'];

        $sql = "SELECT finalizado FROM tbl_pedido WHERE fabrica = $login_fabrica AND pedido = $pedido AND exportado IS NULL";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) == 0) {
            throw new Exception(traduz("pedido.nao.encontrado"));
        }
        $sql = "SELECT os_item FROM tbl_os_item WHERE fabrica_i = $login_fabrica AND pedido_item = $pedido_item";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            throw new Exception(traduz("item.consta.na.os.nao.pode.ser.apagada"));
        }

        $transaction = true;

        unset($auditorPedItem);
        $auditorPedItem = new AuditorLog();
        $auditorPedItem->retornaDadosTabela('tbl_pedido_item', array('pedido' => $pedido));

        pg_query($con, "BEGIN");

        if ($login_fabrica == 183){
            $sql = "DELETE FROM tbl_nf_produto_pedido_item WHERE pedido_item = {$pedido_item}";
            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
                throw new Exception(traduz("erro.ao.deletar.item.do.pedido"));
            }
        }

        $sql = "UPDATE tbl_pedido_item set pedido = 0 WHERE pedido_item = $pedido_item AND pedido = $pedido";
        $res = pg_query($con,$sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception(traduz("erro.ao.deletar.item.do.pedido"));
        }

        $retorno = array("sucesso" => true);

        $sql = "SELECT pedido_item FROM tbl_pedido_item WHERE pedido = $pedido";
        $res = pg_query($con, $sql);

        if(!pg_num_rows($res)){
            $sql = "UPDATE tbl_pedido SET fabrica = 0 WHERE pedido = $pedido AND fabrica = $login_fabrica";
            $res = pg_query($con,$sql);

            if (strlen(pg_last_error()) > 0) {
                throw new Exception(traduz("erro.ao.deletar.pedido"));
            }

            $retorno = array("resumo_excluido" => true);
        }

        pg_query($con, "COMMIT");

        $auditorPedItem->retornaDadosTabela()
                        ->enviarLog('delete', "tbl_pedido_item", $login_fabrica."*".$pedido);

        exit(json_encode($retorno));
    } catch(Exception $e) {
        if ($transaction === true) {
            pg_query($con, "ROLLBACK");
        }

        exit(json_encode(array("erro" => utf8_encode($e->getMessage()))));
    }
}

if ($btn_acao == "gravar") {

    if($login_fabrica == 161){

        $suframa_status = (trim($_POST["suframa_status"]) == "t") ? true : false;
        regiao_suframa($suframa_status);

    }
    
    $msg_erro = array();

    $regras = array(
        "posto_id" => array(
            "obrigatorio_admin" => true
        ),
        "tipo_pedido" => array(
            "obrigatorio" => true
        ),
        "condicao" => array(
            "obrigatorio" => true
        ),
        "pedido_cliente" => array(
            "obrigatorio" => true
        ),
        "tabela" => array(
            "obrigatorio_admin" => true
        ),
        "tipo_frete" => array(
            "obrigatorio_admin" => false
        )
    );

    if (in_array($login_fabrica, array(157))) {
	   $regras["condicao"]["obrigatorio"] = false;
    }

    if ($login_fabrica == 183){
        if ($areaAdmin){
            
            $xid_posto = $_POST["posto_id"];
            $sql_tipo_posto = "
                SELECT 
                    tbl_tipo_posto.codigo 
                FROM tbl_posto_fabrica 
                JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica}
                WHERE tbl_posto_fabrica.posto = {$xid_posto}
                AND tbl_posto_fabrica.fabrica = {$login_fabrica}";
            $res_tipo_posto = pg_query($con, $sql_tipo_posto);
            
            if (pg_num_rows($res_tipo_posto) > 0){
                $login_tipo_posto_codigo = pg_fetch_result($res_tipo_posto, 0, "codigo");
            }
        }

        if (in_array($login_tipo_posto_codigo, array("Rev", "Rep"))){
            $regras["condicao"]["obrigatorio"] = true;
        }    

        if (!in_array($login_tipo_posto_codigo, array("Rev", "Rep"))){
            $regras["linha"]["obrigatorio"] = true;
        }

        $regras["pedido_cliente"]["obrigatorio"] = false;
    }

    if (in_array($login_fabrica, array(169,170))) {
        $regras["observacao_pedido"]["obrigatorio"] = true;
    }

    //hd_chamado=2538216
    if($login_fabrica == 143){
        $regras["tipo_frete"]["obrigatorio_admin"] = false;
    }

    if(in_array($login_fabrica, array(153,160,164)) or $replica_einhell){
        $regras["parcial"]["obrigatorio_admin"] = true;
        $regras["parcial"]["obrigatorio"] = true;
    }

    if($login_fabrica == 160){
        $regras["tipo_frete"]["obrigatorio"] = true;
    }

    if($login_fabrica == 156){
        $regras["transportadora"]["obrigatorio"] = true;
    }

    // fim hd_chamado=2538216

    if (in_array($login_fabrica, array(35,139,144,147,150,151,153,157,160,156,162,163,164,167,168,175,184,191,200,203)) or $replica_einhell) {
        $regras["pedido_cliente"]["obrigatorio"] = false;
    }

    if (in_array($login_fabrica, array(157,163))) {
        $regras['entrega']['obrigatorio'] = true;
        if (strlen($_POST['entrega']) > 0 && in_array($login_fabrica, array(163)) && trim($_POST['entrega']) == "TRANSP" ) {
            $regras["tipo_frete"]["obrigatorio_admin"] = true;
            $regras["tipo_frete"]["obrigatorio"] = true;
        }
    }

    if (in_array($login_fabrica, array(157,163))) {
        $regras['entrega']['obrigatorio'] = true;
        if (strlen($_POST['entrega']) > 0 && in_array($login_fabrica, array(163)) && trim($_POST['entrega']) == "TRANSP" ) {
            $regras["tipo_frete"]["obrigatorio_admin"] = true;
            $regras["tipo_frete"]["obrigatorio"] = true;
        }
    }

    // 13 Jun 2016 - MLG - Usando JSON para contornar a limita\E7\E3o de \EDtens no POST
    $itens_pecas = json_decode(stripcslashes($_POST['item_data']), true);
    foreach ($itens_pecas as $i => $peca)
        foreach ($peca as $fn => $fv)
            $_POST[$fn] = $fv;

    foreach ($regras as $input => $input_regras) {
        $input_valor = getValue($input);

        if(in_array($input, array("tipo_frete", "tabela")) && in_array($login_fabrica, array(161,164))){
            continue;
        }

        if(in_array($input, array("tabela")) && in_array($login_fabrica, array(183))){
            continue;
        }

        foreach ($input_regras as $regra => $regra_valor) {
            switch ($regra) {
                case 'obrigatorio':
                    if ($regra_valor === true && empty($input_valor)) {
                        $msg_erro["msg"]["obg"] = traduz("preencha.todos.os.campos.obrigatorios");
                        $msg_erro["campos"][]   = $input;
                    }
                    break;

                case 'obrigatorio_admin':
                    if ($regra_valor === true && $areaAdmin === true && empty($input_valor)) {
                        $msg_erro["msg"]["obg"] = traduz("preencha.todos.os.campos.obrigatorios");
                        $msg_erro["campos"][]   = $input;
                    }
                    break;
            }
        }
    }

    if(in_array($login_fabrica, array(157))){
        $tipo_pedido = $_POST["tipo_pedido"];

        $sql_tipo_pedido = "SELECT LOWER(descricao) AS descricao FROM tbl_tipo_pedido WHERE tipo_pedido = {$tipo_pedido} AND fabrica = {$login_fabrica} and pedido_faturado";
        $res_tipo_pedido = pg_query($con, $sql_tipo_pedido);
		if(pg_num_rows($res_tipo_pedido) > 0) {
			$desc_tipo_pedido = pg_fetch_result($res_tipo_pedido, 0, "descricao");

			$valor_pedido = trim($_POST["total_pecas"]);
			$valor_pedido = str_replace(".", "", $valor_pedido);
			$valor_pedido = str_replace(",", ".", $valor_pedido);

			$valor_pedido_minimo = (strpos($desc_tipo_pedido,"wap")) ? 250.00 : 100.00;

			if($valor_pedido < $valor_pedido_minimo){
				$valor_pedido_minimo = number_format($valor_pedido_minimo, 2, ",", ".");
				$msg_erro["msg"]["obg"] = traduz("o.valor.minimo.para.o.pedido.e.de").": R$ {$valor_pedido_minimo}";
			}
		}
    }

    if(in_array($login_fabrica, array(161,162,167,203))){
        $valor_desconto_fabricante = $_POST["valor_desconto_fabricante"];
        $adicional_fabricante      = $_POST["adicional_fabricante"];
    }

    if (in_array($login_fabrica, array(163))) {
        $anexo_tdocs  = $_POST["anexo"];
        $anexo_tdocs_s3  = $_POST["anexo_s3"];
    }

    if(in_array($login_fabrica, array(163,164))){
        $valor_frete_estado = str_replace(",", ".", $_POST["total_frete_estado"]);
    }

    if ($login_fabrica == 183){
        if (in_array($login_tipo_posto_codigo, array("Rev", "Rep"))){
            $numero_nf                  = $_POST['numero_nf'];
            $id_posto_pedido            = $_POST['id_posto_pedido'];
            $nota_fiscal_posto_pedido   = $_POST['nota_fiscal_posto_pedido'];
            $linha_posto_pedido         = $_POST['linha_posto_pedido'];    
            $codigo_posto_pedido        = $_POST['codigo_posto_pedido'];
            $display_posto_info = "style='display:none;'";
            
    	    if (strlen(trim($seu_pedido)) > 0){
        		$sql_seu_pedido = "SELECT pedido FROM tbl_pedido WHERE fabrica = 183 AND seu_pedido = '$seu_pedido' AND finalizado IS NOT NULL";
        		$res_seu_pedido = pg_query($con, $sql_seu_pedido);

        		if (pg_num_rows($res_seu_pedido) > 0){
        			$msg_erro["msg"][] = "Número do Pedido Representante já cadastrado";
        			$msg_erro["campos"][] = "pedido_representante";
        		}
    	    }
	        if (strlen(trim($id_posto_pedido)) > 0){
                $sql_posto_info = "
                    SELECT 
                        tbl_posto.nome,
                        tbl_posto_fabrica.codigo_posto
                    FROM tbl_posto 
                    JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                    WHERE tbl_posto.posto = {$id_posto_pedido} ";
                $res_posto_info = pg_query($con, $sql_posto_info);

                if (pg_num_rows($res_posto_info) > 0){
                    $codigo_posto_info = pg_fetch_result($res_posto_info, 0, "codigo_posto");
                    $nome_info_posto = pg_fetch_result($res_posto_info, 0, "nome");
                    $display_posto_info = "";
                }
            }

        }else{
            $sql_condicao = "
                SELECT condicao 
                FROM tbl_condicao 
                WHERE condicao = {$condicao} 
                AND fabrica = {$login_fabrica}
                AND visivel_acessorio IS TRUE";
            $res_condicao = pg_query($con, $sql_condicao);
        
            if(pg_num_rows($res_condicao) > 0){
                if ($total_pecas > $saldo_encontro_contas_disponivel){
                    $msg_erro["msg"][] = "Valor do Pedido \E9 maior que o Saldo dispon\EDvel";
                }
            }
        }
    }

    if (!count($msg_erro)) {
        try {
            $inTransaction = false;
            $pedido = $_POST["pedido"];

            if (empty($pedido)) {
                $gravandoPedido = true;
                $pedido = null;
                $pedidoClass = new Pedido($login_fabrica);
            } else {
                $gravandoPedido = false;
                $pedidoClass = new Pedido($login_fabrica, $pedido);

                $recalcula = $_POST["recalcula"];

                if($recalcula == "t" and $areaAdmin === false ){

                    $res_com = pg_query($con,"BEGIN");
                    $tipo_pedido = $_POST["tipo_pedido"];
                    $tabela = $pedidoClass->_model->getTabelaPreco($login_posto, $tipo_pedido);

                    $sql = "SELECT peca,pedido_item from tbl_pedido_item WHERE pedido = {$pedido} ";
                    $res_item = pg_query($con,$sql);

                    for ($l=0; $l < pg_num_rows($res_item); $l++) {

                        $itens_lancados = pg_fetch_result($res_item, $l, "pedido_item");
                        $peca           = pg_fetch_result($res_item, $l, "peca");

                        if(strlen($itens_lancados) == 0 OR $itens_lancados == null ){
                            continue;
                        }

                        if (strlen($peca)){

                            $sql = "SELECT preco FROM tbl_tabela_item WHERE tabela = {$tabela} AND peca = {$peca}";
                            $res = pg_query($con,$sql);

                            $preco_new = pg_fetch_result($res, 0, preco);

                            if (pg_num_rows($res) == 1) {

                                $sql = "UPDATE tbl_pedido_item SET preco = {$preco_new} where pedido = {$pedido} and pedido_item = {$itens_lancados}";
                                $res = pg_query($con,$sql);

                                if(strlen(pg_last_error())>0){
                                    $msg_erro['msg'][] = traduz("erro.ao.alterar.preco.da.peca");
                                }else{
                                    continue;
                                }
                            }else{
                                $msg_erro['msg'][] = traduz("erro.peca.ja.lancada.e.sem.valor");
                            }
                        }else{
                            $msg_erro['msg'][] = traduz("erro.peca.ja.lancada.e.sem.valor");
                        }
                    }
                }
            }

            if (in_array($login_fabrica, array(151)) and strlen(trim($hd_chamado)) > 0) {

                $sql = "SELECT fn_calcula_previsao_retorno(CURRENT_DATE,prazo_dias,$login_fabrica)::DATE AS data_providencia FROM tbl_hd_motivo_ligacao WHERE hd_motivo_ligacao = 77";
                $resP = pg_query($con,$sql);
                $data_providencia = pg_fetch_result($resP, 0, 'data_providencia');

                $sql = "UPDATE tbl_hd_chamado SET data_providencia = '{$data_providencia} 00:00:00' from tbl_hd_chamado_extra WHERE tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado and tbl_hd_chamado.hd_chamado = {$hd_chamado} and tbl_hd_chamado_extra.hd_motivo_ligacao <> 77 ";
                $res = pg_query($con,$sql);

                $sql_prov = "SELECT hd_motivo_ligacao, nome FROM tbl_hd_chamado_extra WHERE hd_chamado = $hd_chamado;";
                $res_prov = pg_query($con,$sql_prov);

                if (pg_num_rows($res) > 0) {
                    $hd_motivo_ligacao_ant = pg_fetch_result($res_prov, 0, hd_motivo_ligacao);
                    $nome_cliente = pg_fetch_result($res_prov, 0, nome);
                }

                $sql = "UPDATE tbl_hd_chamado_extra set hd_motivo_ligacao = 77 where hd_chamado = $hd_chamado";
                $res = pg_query($con,$sql);

                //verifica se vai enviar e-mail ou sms
                $sql = "SELECT descricao, texto_email, texto_email_admin, texto_sms FROM tbl_hd_motivo_ligacao WHERE hd_motivo_ligacao = 77 AND fabrica = {$login_fabrica}";
                $resP = pg_query($con,$sql);

                if(pg_num_rows($resP)>0){
                    $texto_email = pg_fetch_result($resP, 0, 'texto_email');
                    if (!empty($texto_email)) {
                        $texto_email =  textoProvidencia($texto_email,$hd_chamado, $nome_cliente);
                    }

                    $texto_email_admin = pg_fetch_result($resP, 0, 'texto_email_admin');
                    if (!empty($texto_email_admin)) {
                        $texto_email_admin =  textoProvidencia($texto_email_admin,$hd_chamado, $nome_cliente);
                    }

                    $texto_sms   = pg_fetch_result($resP, 0, 'texto_sms');
                    $desc_motivo_ligacao = pg_fetch_result($resP, 0, 'descricao');
                }

                $sql = "SELECT tbl_hd_motivo_ligacao.descricao,
                    tbl_hd_chamado.status
                    FROM tbl_hd_chamado
                    JOIN tbl_hd_chamado_extra USING(hd_chamado)
                    JOIN tbl_hd_motivo_ligacao USING(hd_motivo_ligacao)
                    WHERE tbl_hd_chamado.hd_chamado = {$hd_chamado}";
                $resS = pg_query($con,$sql);
                $desc_motivo_ligacao_ant = pg_fetch_result($resS, 0, 'descricao');
                $xstatus_interacao = pg_fetch_result($resS, 0, 'status');

                $texto_interacao = traduz("a.providencia.do.atendimento.foi.alterada.por")." <b>$login_fabrica</b> ".traduz("de")." <b> $desc_motivo_ligacao_ant </b> ".traduz("para")." <b>$desc_motivo_ligacao</b>";

                $sql = "INSERT INTO tbl_hd_chamado_item(
                         hd_chamado   ,
                         data         ,
                         comentario   ,
                         admin        ,
                         interno      ,
                         status_item
                         ) VALUES(
                         $hd_chamado  ,
                         current_timestamp,
                         E'$texto_interacao',
                         {$login_admin},
                         't',
                         '$xstatus_interacao'
                     )";
                $res = pg_query($con,$sql);

                if(strlen(trim($texto_sms))>0){
                    $sql = "SELECT celular from tbl_hd_chamado_extra where hd_chamado = $hd_chamado";
                    $qry = pg_query($con, $sql);

                    if (pg_num_rows($qry) == 0) {
                        $warning = traduz("nao.foi.possivel.enviar.interacao.por.sms.celular.do.consumidor.nao.cadastrado");
                    } else {
                        $consumidor_celular = pg_fetch_result($qry, 0, 'celular');

                        require $areaAdmin ?
                            '../class/sms/sms.class.php':
                            'class/sms/sms.class.php';
                        $sms = new SMS();

                        // Prepara o texto da mensagem para a API
                        $nome_fab = $sms->nome_fabrica;
                        $sms_msg = ($texto_sms) ? : $_POST['resposta'];

                        if ($sms->enviarMensagem($consumidor_celular, $sua_os, '', $sms_msg)) {
                            $enviou_sms = (empty($enviou_email)) ? 'SMS ' : 'e SMS ';
                            $enviou = true;
                        }
                    }
                }
            }

            if(count($msg_erro)){
                $res_com = pg_query($con,"ROLLBACK");
                throw new Exception("Error Processing Request");
            }else{
                $res_com = pg_query($con,"COMMIT");

                if ($login_fabrica == 151 AND $hd_motivo_ligacao_ant != 77) {

                    $sql_email = "SELECT destinatarios
                                    FROM tbl_hd_motivo_ligacao
                                    WHERE destinatarios is not null
                                    AND fabrica = {$login_fabrica}
                                    AND hd_motivo_ligacao = 77;";
                    $res_email = pg_query($con,$sql_email);

                    if (pg_num_rows($res_email) > 0) {
                        $destinatario = pg_fetch_result($res_email, 0, 'destinatarios');
                        $destinatario = json_decode($destinatario,true);

                        $destinatario = implode(";", $destinatario);

                    }
                    $text =  "Provid?ncia Alterada!";
                    $header = "From: Telecontrol <noreply@telecontrol.com.br>";
                    mail($destinatario,utf8_encode('Altera??o de provid?ncia no atendimento '.$hd_chamado),utf8_encode($texto_email_admin), $header );
                }
            }

            if(isset($_POST["atender_como"])){

                $atender_como = $_POST["atender_como"];

                if($atender_como == "t"){
                    $pedido_via_distribuidor = "t";
                    $distribuidor = "4311";

                }else{
                    $pedido_via_distribuidor = "f";
                    $distribuidor = "null";

                }

            }else{
                $atender_como = $_POST["atender_como"];
                if($telecontrol_distrib and $atender_como == 't') {
                    $pedido_via_distribuidor = "t";
                    $distribuidor = "4311";
                }else{
                    $pedido_via_distribuidor = "f";
                    $distribuidor = "null";
                }
            }

            $dados = array(
                "posto"                          => (strlen($login_posto) > 0) ? $login_posto : $_POST['posto_id'],
                "fabrica"                        => $login_fabrica,
                "condicao"                       => (empty($_POST["condicao"])) ? null : $_POST["condicao"],
                "pedido_cliente"                 => (empty($pedido)) ? "'{$_POST['pedido_cliente']}'" : "{$_POST['pedido_cliente']}",
                "transportadora"                 => (empty($_POST["transportadora"])) ? ((empty($pedido)) ? "null" : null) : $_POST["transportadora"],
                "tipo_pedido"                    => $_POST["tipo_pedido"],
                "obs"                            => (empty($pedido)) ? "E'" . addslashes($_POST['observacao_pedido']) . "'" : "{$_POST['observacao_pedido']}",
                "atende_pedido_faturado_parcial" => ($_POST["parcial"] == "t") ? "true" : "false",
                "distribuidor"                   => ($distribuidor == "null") ? ((empty($pedido)) ? "null" : null) : $distribuidor,
                "pedido_via_distribuidor"        => (empty($pedido)) ? "'$pedido_via_distribuidor'" : $pedido_via_distribuidor,
                "tipo_frete"                     => (empty($pedido)) ? "'{$_POST['tipo_frete']}'" : $_POST['tipo_frete'],
                "total"                          => str_replace(",", ".", str_replace(".", "", $_POST["total_pecas"]))
            );

            $usa_frete = \Posvenda\Regras::get("usa_frete", "pedido_venda", $login_fabrica);

            if(!empty($usa_frete)){
                $dados['valor_frete'] =  str_replace(",", ".", str_replace(".", "", $_POST["valor_frete"]));
            }

            if(in_array($login_fabrica, array(164)) || (in_array($login_fabrica, array(163)) && $areaAdmin === true) ){
                $dados['valor_frete'] =  str_replace(",", ".", $_POST["total_frete_estado"]);
            }

			if(empty($dados['status_pedido']) and empty($pedido)) {
				$dados['status_pedido'] = 1;
			}
            if ((empty($pedido) && in_array($login_fabrica, array(162,165))) || $auditoria_pedido_obrigatoria == true) {
                if ($login_fabrica == 165) {
                    $dados['status_pedido'] = 2;
                } else {
                    $dados['status_pedido'] = 18;
                }
            } else {
                if (!in_array($login_fabrica, array(158)) && $auditoria_pedido_obrigatoria != true) {
                    $dados['status_pedido'] = 1;
                }elseif (in_array($login_fabrica, array(158)) && in_array($_POST["tipo_pedido"], array(344))) {
                    $dados['status_pedido'] = 2;
                }

                if (in_array($login_fabrica, array(168))) {
                    $dados['status_pedido'] = 18;
                }
            }

            if ($login_fabrica == 147) {
                $dados['status_pedido'] = 18;
            }

    	    if(in_array($login_fabrica, [167, 203])){
                $dados['status_pedido'] = 2;
    	    }

			if ((in_array($login_fabrica, [160]) or $replica_einhell)) {
				$sql = "SELECT pedido_faturado FROM tbl_tipo_pedido WHERE fabrica = $login_fabrica AND tipo_pedido = $tipo_pedido and pedido_faturado";
                $res = pg_query($con,$sql);

				if(pg_num_rows($res) > 0) {
					$dados['status_pedido'] = 18;
				}
            }

            if ($areaAdmin === true) {
                $dados['finalizado'] = 'now()';

                if ($FaturamentoManualArquivo) {
                    $dados["status_pedido"] = 2;
                }

                if ($login_fabrica == 163) {
                    $dados['status_pedido'] = 18;
                }
            }
            if($telecontrol_distrib) {
                if($atender_como == 't' or $areaAdmin !== true) {
                    $dados['distribuidor'] = 4311;
                }
            }

            if($login_fabrica == 186 AND !empty($_POST["condicao"])){
                $sql = "SELECT codigo_condicao FROM tbl_condicao WHERE condicao = {$_POST["condicao"]}";
                $res = pg_query($con,$sql);

                if(pg_fetch_result($res, 0, 'codigo_condicao') == "BOL"){
                    $dados['status_pedido'] = 18;
                }
            }

            #***** waker neuson
            # Enquanto nao finalizar o pedido o mesmo nao tem status
            #*********

            $sql = "SELECT pedido_em_garantia FROM tbl_tipo_pedido WHERE ativo AND visivel AND tipo_pedido = {$dados['tipo_pedido']} AND fabrica = {$login_fabrica} ";
            $res = pg_query($con,$sql);
            $pedido_em_garantia = pg_fetch_result($res, 0, 'pedido_em_garantia');

            if(($reprocessamento == true and $pedido_em_garantia == true ) and in_array($login_fabrica,array(143))){
                unset($dados['status_pedido']);
            }
            if (empty($_POST['pedido_cliente']))    unset($dados['pedido_cliente']) ;
            if (empty($_POST['observacao_pedido'])) unset($dados['obs']);

            if (strlen($tabela) > 0 || strlen($_POST["tabela"]) > 0){
                $dados["tabela"] = (strlen($tabela)) ? $tabela : $_POST["tabela"] ;
            }

            if (in_array($login_fabrica, array(146)) AND strlen($_POST['marca'])) {
                // MARCA sem campo definido gravando em obs
                $dados["visita_obs"] = $_POST['marca'];
            }

            if($areaAdmin === true){
                $dados['admin'] = $login_admin;

                if(strlen($_POST['posto_id']) > 0){
                    $dados['posto'] = $_POST['posto_id'];
                }

                if (strlen($_POST['tipo_frete']) > 0) {
                    $dados['tipo_frete'] =  (empty($pedido)) ? "'{$_POST['tipo_frete']}'" : $_POST['tipo_frete'];
                    if(in_array($login_fabrica, array(142))){
                        $dados['tipo_frete'] =  (empty($pedido)) ? "'CIF'" : "CIF" ;
                    }
                }

            }

            if (strlen($_POST['entrega']) > 0 && in_array($login_fabrica, array(157,163))) {
                $dados['entrega'] = (empty($pedido)) ? "'{$_POST['entrega']}'" : $_POST['entrega'];
            }

            if(strlen($_POST['hd_chamado']) > 0){
                $dados['hd_chamado'] = $_POST['hd_chamado'];
                $dados['origem_cliente'] = "true";
            }

            if($login_fabrica == 183 && strlen($_POST['linha']) > 0){
                $dados['linha'] = $_POST['linha'];
            }

            $valor_desconto = $_POST['valor_desconto'];

            if (!strlen($valor_desconto)) {
                $valor_desconto = 0;
            }

            $dados["desconto"] = $valor_desconto;

            if(in_array($login_fabrica, array(161,162,167,183,203))){

                if ($login_fabrica == 183){
                    $valores_adicionais['id_posto_pedido'] = $id_posto_pedido;
                    $valores_adicionais['nota_fiscal_posto_pedido'] = $nota_fiscal_posto_pedido;
                    $valores_adicionais['linha_posto_pedido'] = $linha_posto_pedido;
                    $valores_adicionais['codigo_posto_pedido'] = $codigo_posto_pedido;

                    if (in_array($login_tipo_posto_codigo, array("Rev", "Rep"))){
                        $dados["filial_posto"] = $id_posto_pedido;
                        $dados["linha"] = $linha_posto_pedido;
                    }

                    if (strlen(trim($_POST['seu_pedido'])) > 0){
                        $dados["seu_pedido"] = "'".$_POST['seu_pedido']."'";
                    }

                    $dados['status_pedido'] = 1;
                }else{
                    $valores_adicionais['valor_desconto_fabricante'] = $valor_desconto_fabricante;
                    $valores_adicionais['adicional_fabricante']      = $adicional_fabricante;
                }

                $valores_adicionais = json_encode($valores_adicionais);

                if(empty($pedido)){
                    $dados["valores_adicionais"] = "'{$valores_adicionais}'";
                }else{
                    $dados["valores_adicionais"] = "{$valores_adicionais}";
                }
            }

            if ($login_fabrica == 139) {
                if (!empty($_POST["validade"])) {
                    $dados["validade"] = "'".$_POST["validade"]."'";
                }

                if (!empty($_POST["entrega"])) {
                    $dados["entrega"]  = "'".$_POST["entrega"]."'";
                }

                $sqlTpedido = " SELECT pedido_faturado 
                                FROM tbl_tipo_pedido 
                                WHERE fabrica = $login_fabrica 
                                AND tipo_pedido = $tipo_pedido
                                AND pedido_faturado IS TRUE";
                $resTpedido = pg_query($con, $sqlTpedido);

                if (pg_num_rows($resTpedido) > 0) {

                    $login_posto = (strlen(trim($login_posto)) > 0) ? $login_posto : $_POST['posto_id'];

                    $select_distribuidor = "  SELECT tbl_posto_linha.distribuidor
                                               FROM tbl_linha
                                               JOIN tbl_posto_linha USING(linha)
                                               WHERE tbl_posto_linha.posto = $login_posto
                                               AND tbl_linha.fabrica = $login_fabrica
                                               LIMIT 1";
                    $res_distribuidor = pg_query($con, $select_distribuidor);

                   if (pg_num_rows($res_distribuidor) == 0) {
                        $msg_erro['msg'][] = traduz("distribuidor.nao.cadastrado");
                   } else {
                        $distribuidor = pg_fetch_result($res_distribuidor, 0, "distribuidor");
                        $dados['distribuidor'] = $distribuidor;
                   }
                }
            }

            unset($auditorPedItem);
            $auditorPedItem = new AuditorLog();
            $auditorPedItem->retornaDadosTabela('tbl_pedido_item', array('pedido' => $pedido));

            $pedidoClass->_model->getPDO()->beginTransaction();
            $inTransaction = true;
            $pedido = $pedidoClass->grava($dados, $pedido,$hd_chamado);

            $dadosItens = array();

            unset($_POST['item_data']);

            for ($i = 0; $i < $qtde_item; $i++) {

                if (empty($_POST["pedido_item_{$i}"]) && empty($_POST["peca_id_{$i}"])) {
                    continue;
                }

                if (strlen($_POST["preco_{$i}"]) == 0) $_POST["preco_{$i}"] = "null";
                $verifica_preco = str_replace(",", ".", str_replace(".", "", $_POST["preco_{$i}"]));

                if($pedidoClass->verificaDepara( $_POST["peca_id_{$i}"], $_POST["peca_referencia_{$i}"] ) != 'true') {
                    $erro_depara = $pedidoClass->verificaDepara( $_POST["peca_id_{$i}"], $_POST["peca_referencia_{$i}"] );
                    throw new Exception("{$erro_depara}");
                }

                if(empty($verifica_preco) OR !is_numeric($verifica_preco) OR  $verifica_preco == 0){
                    throw new Exception(traduz("erro.a.peca")." ".$_POST["peca_referencia_{$i}"]." " . traduz("esta.sem.preco"));
                }

                if (in_array($login_fabrica, [175])) {
                    $peca_id    = (int) $_POST["peca_id_{$i}"];
                    $sql_unid   = "SELECT unidade FROM tbl_peca WHERE peca = $peca_id";
                    $query_unid = pg_query($con, $sql_unid);

                    if (pg_num_rows($query_unid) > 0) {
                        $unidade = pg_fetch_result($query_unid, 0, 'unidade');

                        if (!in_array($unidade, ['c', 'm', 'mm', 'cm', 'dm', 'mt', 'dam', 'hm', 'km'])) {
                            if($_POST["multiplo_{$i}"] == 0) {
                               throw new Exception(traduz("erro.a.peca")." ".$_POST["peca_referencia_{$i}"]." " . traduz("com.erro.no.cadastro.de.multiplo.favor.contatar.fabrica"));
                            }
                            
                            if(strlen($_POST["multiplo_{$i}"])>0 AND $_POST["qtde_{$i}"] % $_POST["multiplo_{$i}"] <> 0){
                               throw new Exception(traduz("erro.a.peca")." ".$_POST["peca_referencia_{$i}"]." ".traduz("tem.que.ser.multiplo.de")." ".$_POST["multiplo_{$i}"].". ");
                            }        
                        }
                    }
                } else {
                    if ($login_fabrica <> 183){
                        if($_POST["multiplo_{$i}"] == 0 && $login_fabrica <> 158) {
                           throw new Exception(traduz("erro.a.peca")." ".$_POST["peca_referencia_{$i}"]." " . traduz("com.erro.no.cadastro.de.multiplo.favor.contatar.fabrica"));
                        }
                        if($_POST["multiplo_{$i}"] > 0 && strlen($_POST["multiplo_{$i}"]) > 0 && $_POST["qtde_{$i}"] % $_POST["multiplo_{$i}"] <> 0){
                           throw new Exception(traduz("erro.a.peca")." ".$_POST["peca_referencia_{$i}"]." ".traduz("tem.que.ser.multiplo.de")." ".$_POST["multiplo_{$i}"].". ");
                        }
                    }
                }

                if(strlen($_POST["qtde_{$i}"]) == ""){
                   throw new Exception(traduz("erro.a.peca")." ".$_POST["peca_referencia_{$i}"]." " . traduz("digite.uma.quantidade"));
                }

                if (in_array($login_fabrica, array(143)) && !empty($_POST["pedido_item_{$i}"]) &&  $areaAdmin === false) { //hd_chamado=2538216 $areaAdmin = alterado de true para false
                    continue;
                }

				$sqlTP = "SELECT * from tbl_tipo_pedido where tipo_pedido = {$dados["tipo_pedido"]} AND pedido_faturado";
				$qryTP = pg_query($con, $sqlTP);

				if (pg_num_rows($qryTP) > 0) {
					$peca_id = (int) $_POST["peca_id_{$i}"];
					$sqlBloq = "SELECT referencia FROM tbl_peca WHERE peca = $peca_id AND bloqueada_venda = 't'";
					$qryBloq = pg_query($con, $sqlBloq);

					if (pg_num_rows($qryBloq) > 0) {
						$ref = pg_fetch_result($qryBloq, 0, 'referencia');

						throw new Exception(traduz("peca")." $ref ".traduz("bloqueada.para.venda"));
					}
				}

                /*HD-3844543 01/11/2017*/
                $preco      = str_replace(",", ".",str_replace(".","",$_POST["preco_{$i}"]));
                $qtde       = $_POST["qtde_{$i}"];

                if ($login_fabrica == 183){
                    $causa_defeito = $_POST["causa_defeito_{$i}"];
                    $peca_id_valida = $_POST["peca_id_{$i}"];
		    $xpedido_item = $_POST["pedido_item_{$i}"];

                    if (in_array($login_tipo_posto_codigo, array("Rev", "Rep"))){
                        $qtde_valida = "";
			if (!empty($peca_id_valida) AND !empty($qtde)){
                            $sql_valida_qtde = "
                                SELECT 
                                    SUM (x.total_peca) AS soma_total,
                                    x.referencia AS ref_peca
                                FROM (
                                    SELECT
                                        tbl_venda.produto,
                                        tbl_peca.referencia,
                                        SUM(tbl_venda.qtde * tbl_lista_basica.qtde) AS total_peca
                                    FROM tbl_venda
                                    JOIN tbl_lista_basica USING(produto,fabrica)
                                    JOIN tbl_peca ON tbl_peca.peca = tbl_lista_basica.peca AND tbl_peca.fabrica = {$login_fabrica}
                                    WHERE tbl_venda.fabrica = {$login_fabrica}
                                    AND tbl_lista_basica.peca = {$peca_id_valida}
                                    AND tbl_venda.nota_fiscal = '{$nota_fiscal_posto_pedido}'
                                    GROUP BY tbl_venda.produto, tbl_peca.referencia
                                ) x
                                GROUP BY ref_peca";
                            $res_valida_qtde = pg_query($con, $sql_valida_qtde);

                            if (pg_num_rows($res_valida_qtde) > 0){
                                $total_qtde_peca = pg_fetch_result($res_valida_qtde, 0, 'soma_total');
                                $ref_peca = pg_fetch_result($res_valida_qtde, 0, 'ref_peca');

                                $sql_nf_produto = "
                                    SELECT
                                        SUM(tbl_pedido_item.qtde) AS qtde_pedido
                                    FROM tbl_nf_produto_pedido_item
                                    JOIN tbl_pedido_item ON tbl_pedido_item.pedido_item = tbl_nf_produto_pedido_item.pedido_item
                                    WHERE tbl_nf_produto_pedido_item.nota_fiscal_produto = '{$nota_fiscal_posto_pedido}'
                                    AND tbl_pedido_item.peca = {$peca_id_valida}";
                                $res_nf_produto = pg_query($con, $sql_nf_produto);

                                if (pg_num_rows($res_nf_produto) > 0 AND strlen(trim($xpedido_item)) == 0){
				    $qtde_pedido = pg_fetch_result($res_nf_produto, 0, "qtde_pedido");
                                    $qtde_valida = ($qtde+$qtde_pedido);
                                }else{
                                    $qtde_valida = $qtde;
                                }
                                #echo "$qtde_valida ----> $total_qtde_peca";exit;
                                if ($qtde_valida > $total_qtde_peca){
                                    $msg_erro["msg"][] = "Peça : $ref_peca com quantidade superior ao permitido";
                                    #throw new Exception(traduz("peca")." $ref_peca ".traduz("com.quantidade.superior.ao.permitido"));
                                }
                            }
                        }
                    }
                }

                $total_item = str_replace(",", ".", str_replace(".","",$_POST["sub_total_{$i}"]));
				$peca_referencia = $_POST["peca_referencia_{$i}"];
                if(!empty($tabela)) {
                    $preco_verifica = $pedidoClass->verificaPreco($_POST["peca_id_{$i}"],$tabela) ;
					$preco_verifica = number_format($preco_verifica,2, ".","");
                    if(($preco_verifica > 1000 and $preco < 100) or $preco_verifica <> $preco) {
                            throw new Exception(traduz("peca")." $peca_referencia ".traduz("com.valor.incorreto.favor.excluir.a.peca.e.lancar.novamente"));
					}
				}else{
					$sql = "select tabela from tbl_pedido where pedido = $pedido";
					$resp = pg_query($con,$sql);
					$tabela = pg_fetch_result($resp, 0, 'tabela');
					if(!empty($tabela)) {
						$preco_verifica = $pedidoClass->verificaPreco($_POST["peca_id_{$i}"],$tabela) ;
						$preco_verifica = number_format($preco_verifica,2, ".","");

						if(($preco_verifica > 1000 and $preco < 100) or $preco_verifica <> $preco) {
							throw new Exception(traduz("peca")." $peca_referencia ".traduz("com.valor.incorreto.favor.excluir.a.peca.e.lancar.novamente"));
						}
					}
				}

				if (in_array($login_fabrica, array(147,149,153,156,157,165)) ||($usa_calculo_ipi) || (regiao_suframa() == false && in_array($login_fabrica, array(161)))){
					$ipi  = $_POST["ipi_{$i}"];
				}
                
                $dadosItens[] = array(
					"pedido_item" => $_POST["pedido_item_{$i}"],
					"peca"        => $_POST["peca_id_{$i}"],
					"qtde"        => $qtde,
					"preco"       => $preco,
					"preco_base"  => $preco,
					"ipi"         => (in_array($login_fabrica, array(147,149,153,156,157,165)) || ($usa_calculo_ipi) || (regiao_suframa() == false && in_array($login_fabrica, array(161)))) ? $ipi : 0,
					"total_item"  => $total_item
	            );

				if($login_fabrica == 183) {
					$dadosItens[$i]["causa_defeito"] = $causa_defeito;
                    $dadosItens[$i]["nota_fiscal_posto_pedido"] = $nota_fiscal_posto_pedido;
				}
                
                if(count($dadosItens) > 0 and ($login_fabrica == 160 or $replica_einhell)) {
                    $pecaIntencao = $_POST["peca_id_{$i}"];
                    $postoIntencao = (strlen($login_posto) > 0) ? $login_posto : $_POST['posto_id'];

                    if(verificaEstoque($qtde, $pecaIntencao, $pedidoClass) == false){
                        if(VerificaPecaAlternativa($pecaIntencao, $qtde, $pedidoClass)){
                            gravaIntencaoCompra($pecaIntencao, $postoIntencao, $qtde, $pedido, $pedidoClass, 'alternativa');
                        }else{
                            gravaIntencaoCompra($pecaIntencao, $postoIntencao, $qtde, $pedido, $pedidoClass);
                        }
                    }
                }
	        }            

	        if (empty($dadosItens)) {
				throw new \Exception(traduz("para.gravar.o.pedido.e.necessario.lancar.pecas"));
			}
            
            if(count($dadosItens) > 0) {
                if ($login_fabrica == 183){
                    if (!count($msg_erro)){
                        $pedidoClass->gravaItem($dadosItens, $pedido);
                    }
                }else{
                    $pedidoClass->gravaItem($dadosItens, $pedido);
                }

                if (in_array($login_fabrica, [35])) {
                    $pedidoClass->setPecaCritica($pedido);
                }
			}
            
            if ($areaAdmin === true) {
                if(in_array($login_fabrica, array(151))){
                    $pedidoClass->verificaValorMinimoPosto();
                } else {
				    $pedidoClass->verificaValorMinimo();

                }
			}

			if (in_array($login_fabrica, array(138,146)) && $areaAdmin === true) {
				$pedidoClass->auditoria();
			}

			if ($areaAdmin === true && $login_fabrica == 143 && $gravandoPedido === true) {
				$reprocessamento_status = \Posvenda\Regras::get("reprocessamento_status", "pedido_venda", $login_fabrica);
				$dados = array('status_pedido' => $reprocessamento_status);

				$pedidoClass->grava($dados, $pedido);
			}

            if ($login_fabrica == 162 && !empty($hd_chamado)) {

                $sqlStatus = "
                    SELECT  tbl_hd_chamado.status
                    FROM    tbl_hd_chamado
                    WHERE   tbl_hd_chamado.hd_chamado = $hd_chamado
                ";
                $resStatus = pg_query($con,$sqlStatus);
                $xstatus_interacao = pg_fetch_result($resStatus,0,status);
                // $pedido = $pedidoClass->getPedido();
                $texto_interacao = "Este callcenter gerou um pedido de peças: <br /> N? <b>$pedido</b>";

                $sql = "INSERT INTO tbl_hd_chamado_item(
                            hd_chamado   ,
                            data         ,
                            comentario   ,
                            admin        ,
                            interno      ,
                            status_item
                        ) VALUES(
                            $hd_chamado  ,
                            current_timestamp,
                            E'$texto_interacao',
                            {$login_admin},
                            't',
                            '$xstatus_interacao'
                        )";
                $res = pg_query($con,$sql);

                if (pg_last_error($con)) {//fputt
                    $msg_erro['msg'][] = traduz("nao.foi.possivel.gravar.a.interacao.no.callcenter")." $hd_chamado";
                }
            }

            if (empty($anexo_tdocs[0]) AND $login_fabrica == 163 AND $dados['entrega'] == "'RFAB'" ) {
                throw new \Exception(traduz("favor.anexar.a.autorizacao.de.retirada"));
            }

            if (empty($msg_erro)) {
                $pedidoClass->_model->getPDO()->commit();
            } else {
                throw new \Exception(traduz("Favor verificar o(s) erro(s) e gravar novamente."));
            }

            if(strlen($aux_pedido) > 0) {
                $auditorPedItem->retornaDadosTabela()
                                ->enviarLog('update', "tbl_pedido_item", $login_fabrica."*".$pedido);
            } else {
                $auditorPedItem->retornaDadosTabela()
                                ->enviarLog('insert', "tbl_pedido_item", $login_fabrica."*".$pedido);
            }
            if (in_array($login_fabrica, [35])) {
                unset($auditorPedItem);
                $incrementPedido = 0;
                $continuar = true;
                $JsonAntes = json_decode($_POST['valoresIniciais']);
                $arrayAntes = [];
                $arrayDepois = [];
                foreach ($JsonAntes as $antes) {
                    $arrayAntes[] = 
                    [
                        'peca' => $antes->peca,
                        'referencia' => $antes->referencia,
                        'descricao' => $antes->descricao,
                        'qtde' => $antes->qtde,
                    ];
                }

                while($continuar == true){
                    if (!isset($_POST['peca_id_'.$incrementPedido]) || $_POST['peca_id_'.$incrementPedido] == '') {
                        $continuar = false;
                        continue;
                    }
                    $arrayDepois[] = 
                    [
                        'peca' => $_POST['peca_id_'.$incrementPedido],
                        'referencia' => $_POST['peca_referencia_'.$incrementPedido],
                        'descricao' => $_POST['peca_descricao_'.$incrementPedido],
                        'qtde' => $_POST['qtde_'.$incrementPedido],
                    ];
                    $incrementPedido++;
                }
                $descricaoComunicado = "";
                
                $keyAntes = array_column($arrayAntes, 'peca');
                $keyDepois = array_column($arrayDepois, 'peca');
                $keyDeletado = array_diff($keyAntes, $keyDepois);
                $keyAdcionado = array_diff($keyDepois, $keyAntes);
                $keyAlterado = array_filter($arrayAntes, function($e) use ($arrayDepois) {
                    $pecaAntes = $e['peca'];
                    $pecaDepois = array_filter($arrayDepois, function($f) use ($pecaAntes) {
                        if ($f['peca'] == $pecaAntes) {
                            return true;
                        }
                        return false;
                    });
                    if (empty($pecaDepois)) {
                        return false;
                    }
                    if ($e['qtde'] != $pecaDepois[0]['qtde']) {
                        return true;
                    }
                    return false;
                });

                $keyAlterado = array_map(function($e) use ($arrayDepois) {
                    $pecaAntes = $e['peca'];
                    $pecaDepois = array_filter($arrayDepois, function($f) use ($pecaAntes) {
                        if ($f['peca'] == $pecaAntes) {
                            return true;
                        }
                        return false;
                    });
		            $keys = array_keys($pecaDepois);
                    $e['qtde_depois'] = $pecaDepois[$keys[0]]['qtde'];
                    return $e;
                }, $keyAlterado);

                foreach ($keyAlterado as $alterado) {
                    if ($alterado['qtde'] != $alterado['qtde_depois']) {
                        $descricaoComunicado .=  "Peça foi alterada: Ref.{$alterado['referencia']}-{$alterado['descricao']} -- de {$alterado['qtde']} para {$alterado['qtde_depois']} <br>";   
        	       }
                }
                
                foreach ($arrayAntes as $antes) {
                    if (in_array($antes['peca'], $keyDeletado)) {
                        $descricaoComunicado .=  "Peça foi excluida: Ref.{$antes['referencia']}-{$antes['descricao']} -- quantidade: {$antes['qtde']} <br>";
                    }                     
                }

                foreach ($arrayDepois as $depois) {
                    if (in_array($depois['peca'], $keyAdcionado)) {
                        $descricaoComunicado .=  "Peça foi adicionada: Ref.{$depois['referencia']}-{$depois['descricao']} -- quantidade: {$depois['qtde']} <br>";
                    }                     
                }
                
                $descricaoComunicado .= "<br>";
                $descricaoComunicado .= "<br>";
                $descricaoComunicado .= "Justificativa:" . $_POST['justificativa'] . "";

                $posto      = $_POST['posto_id'];

                $mensagem = "O pedido {$pedido} foi alterado : <br>";
                $mensagem .= $descricaoComunicado;

                $sql_comunicado = "INSERT INTO tbl_comunicado (fabrica, posto, mensagem, ativo, obrigatorio_site) VALUES ($login_fabrica, $posto, '$mensagem', 't', 't')";
                $res_comunicado = pg_query($con, $sql_comunicado);
            }
            
            if (!empty($anexo_tdocs[0])) {

                $arquivos = array();

                foreach ($anexo_tdocs as $key => $value) {
                    if ($anexo_tdocs_s3[$key] != "t" && strlen($value) > 0) {

                        $value = stripcslashes($value);
                        $fileData = json_decode($value,true);

                        if ($fileData['tmp_name']) {

                            $anexoID = $tDocs->setDocumentReference($fileData,$pedido,'anexar', true, 'pedido');

                            if (!$anexoID) {
                                $msg_erro['msg'][] = traduz("erro.ao.salvar.o.arquivo");
                            } else {

                                $_POST['anexo'] = json_encode($tDocs->sentData);

                            }
                        }
                    }
                }
            }

            /*if($login_fabrica == 147 AND $areaAdmin === true){

                include "rotinas/hitachi/exporta-pedido-funcao.php";

                $erro = exportaPedido($pedido);
                if($erro !== true ){
                    $msg_erro["msg"][] = $erro;
                }
            }*/

            if (in_array($login_fabrica, [160,186]) or $replica_einhell) {

                $sql_status = "SELECT tbl_pedido.status_pedido
                               FROM   tbl_pedido
                               JOIN   tbl_status_pedido ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
                               WHERE  tbl_pedido.pedido  = $pedido
                               AND    tbl_pedido.fabrica = $login_fabrica";
                $res_status = pg_query($con, $sql_status);
                if (pg_num_rows($res_status) > 0) {
                    $pedido_status      = pg_fetch_result($res_status, 0, 'status_pedido');

					$sql = "SELECT pedido FROM tbl_pedido_status WHERE pedido = $pedido and  status = $pedido_status "; 
					$res = pg_query($con, $sql);
					if(pg_num_rows($res) ==0) {
						$sql_status_insert = "INSERT INTO tbl_pedido_status (pedido, status, data) VALUES ($pedido, $pedido_status, now()) "; 
						$res_status_insert = pg_query($con, $sql_status_insert);
					}
                }
            } 

            if($areaAdmin === true and empty($msg_erro)){
                include_once '../class/communicator.class.php';
                include_once '../email_pedido.php';

                $posto_id = $_POST['posto_id'];
                if(!empty($pedido)){
                    $sql_posto = "SELECT 
                                    tbl_posto_fabrica.contato_email as contato_email,
                                    tbl_fabrica.nome as fabrica_nome,
                                    tbl_posto.nome as posto_nome 
                                FROM tbl_posto_fabrica 
                                JOIN tbl_fabrica on (tbl_fabrica.fabrica = tbl_posto_fabrica.fabrica) 
                                JOIN tbl_posto on (tbl_posto.posto = tbl_posto_fabrica.posto) 
                                where tbl_posto_fabrica.fabrica = $login_fabrica and tbl_posto_fabrica.posto = $posto_id";
        
                    $res_posto = pg_query($con, $sql_posto);
        
                    $contato_email = pg_fetch_result($res_posto, 0, 'contato_email');
                    $fabrica_nome = pg_fetch_result($res_posto, 0, 'fabrica_nome');
                    $posto_nome = pg_fetch_result($res_posto, 0, 'posto_nome');
                    
                    $assunto       = "Pedido nº ".$pedido. " - ". $fabrica_nome;
                    $corpo         = email_pedido($posto_nome, $fabrica_nome, $pedido, $cook_login, true);

                    $mailTc = new TcComm($externalId);
                    $res = $mailTc->sendMail(
                        $contato_email,
                        $assunto,
                        utf8_encode($corpo),
                        $externalEmail
                    );

                }
            }

            if ($areaAdmin === true and empty($msg_erro)) {
                header("Location: pedido_admin_consulta.php?pedido=$pedido");
            } else if(empty($msg_erro)) {
                header("Location: cadastro_pedido.php");
            }

        } catch(Exception $e) {
            $msg_erro["msg"][] = $e->getMessage();

            if ($inTransaction) {
                $pedidoClass->_model->getPDO()->rollBack();
            }

            if ($gravandoPedido === true) {
                unset($pedido);
            }
        }
    }
}

if($btn_acao == "finalizar"){

    try {
        $pedido = $_POST["pedido"];

        if (!temItemNoPedido($pedido)) {
            throw new \Exception(traduz("Não foram encontrados itens lançados neste pedido."));
        }

        if(in_array($login_fabrica, [169,170])){

            $id_posto = (strlen($login_posto) > 0) ? $login_posto : $_POST['posto_id'];

            $sql_posto = "SELECT parametros_adicionais FROM tbl_posto_fabrica where fabrica = $login_fabrica and posto = ".$id_posto;
            $res_posto = pg_query($con, $sql_posto);

            $parametros_adicionais = pg_fetch_result($res_posto, 0, 'parametros_adicionais');
            $parametros_adicionais = json_decode($parametros_adicionais, true);

            $valores_adicionais['escritorio_venda'] = $parametros_adicionais['escritorio_venda'];
            $valores_adicionais['equipe_venda'] = $parametros_adicionais['equipe_venda']; 

            if (strlen($valores_adicionais['escritorio_venda']) == 0) {
                throw new \Exception(traduz("Falha ao finalizar pedido. <Br> Entrar em contato com fabricante para atualizar as informações de escritório e equipe de vendas. "));
            }
        }

        $inTransaction = false;

        if (empty($pedido)) {
            $pedido = null;
            $pedidoClass = new Pedido($login_fabrica);
        } else {
            $pedidoClass = new Pedido($login_fabrica, $pedido);
        }
        if ($login_fabrica == 157) {
            $informacoes_pedido = $pedidoClass->getInformacaoPedido($pedido);
            $valor_pedido       = $pedidoClass->getValorPedido($pedido);

            if (strpos(strtolower($informacoes_pedido["tipo_pedido"]), "wap") && $valor_pedido["total"] < 250) {
                throw new \Exception(traduz("para.pedidos.do.tipo.pedido.wap.o.valor.minimo.e.de.r.250.00"));
            }

            if (strpos(strtolower($informacoes_pedido["tipo_pedido"]),"ventila??o") && $valor_pedido["total"] < 100) {
                throw new \Exception(traduz("para.pedidos.do.tipo.ventilacao.o.valor.minimo.e.de.r.100.00"));
            }
        }

        if ($login_fabrica == 183){
            $informacoes_pedido = $pedidoClass->getInformacaoPedido($pedido);
            
            $sql = "
                SELECT condicao 
                FROM tbl_condicao 
                WHERE condicao = {$informacoes_pedido['condicao']} 
                AND fabrica = {$login_fabrica} 
                AND UPPER(fn_retira_especiais(descricao)) = UPPER(fn_retira_especiais(trim('boleto'))) ";
            $res = pg_query($con, $sql);
            
            $auditoria_pedido = false;
            if(pg_num_rows($res) > 0){
                $auditoria_pedido = true;
                $dados['status_pedido'] = 18;
            }
        }

        if ($login_fabrica == 194){
            $informacoes_pedido = $pedidoClass->getInformacaoPedido($pedido);
            $pedido_faturado = $informacoes_pedido["pedido_faturado"];
        }

        if ($login_fabrica == 35) {
            $sql_peca = " SELECT peca FROM tbl_pedido_item WHERE pedido = $pedido";
            $res_peca = $pedidoClass->_model->getPDO()->query($sql_peca);

            $arr_pecas = $res_peca->fetchAll(\PDO::FETCH_ASSOC);

            foreach($arr_pecas as $ky => $xpeca){
                unset($arr_valores);
                $v_peca = $xpeca['peca'];
                $sql_valor = " SELECT tbl_tabela_item.tabela 
                               FROM tbl_tabela_item
                               WHERE tbl_tabela_item.peca = $v_peca 
                               AND ROUND(tbl_tabela_item.preco::numeric, 2) IN (
                                                                SELECT ROUND(tbl_pedido_item.preco::numeric, 2)
                                                                FROM tbl_pedido_item 
                                                                WHERE tbl_pedido_item.pedido = $pedido 
                                                                AND   tbl_pedido_item.peca = $v_peca
                                                              ) ";
                $res_valor = $pedidoClass->_model->getPDO()->query($sql_valor);
                $arr_valores = $res_valor->fetchAll(\PDO::FETCH_ASSOC);
                if (!$arr_valores[0]['tabela']) {
                    $sql_ref  = "SELECT referencia FROM tbl_peca WHERE peca = $v_peca AND fabrica = $login_fabrica";
                    $res_ref  = $pedidoClass->_model->getPDO()->query($sql_ref);
                    $ref_peca = $res_ref->fetchAll(\PDO::FETCH_ASSOC);

                    throw new \Exception(traduz("Peça % com pre?o divergente. Favor conferir e lan?ar novamente.", null, null, [$ref_peca[0]['referencia']]));
                }            
            }        
        }        

        $pedidoClass->_model->getPDO()->beginTransaction();
        $inTransaction = true;
        
        $dados = array(
            'finalizado' => 'now()'
        );


        if(in_array($login_fabrica, [169,170])){
            $valores_adicionais = json_encode($valores_adicionais);
            $dados['valores_adicionais'] = $valores_adicionais;
        }

        if($login_fabrica == 160){
            $resposta_intencao = $_POST['resposta_intencao'];
            //buscar as Peças para novamente verificar o estoque
            $sqlPecas = "SELECT tbl_pedido_item.peca, 
                                tbl_pedido_item.qtde, 
                                tbl_pedido.posto,
                                tbl_peca.referencia, 
                                tbl_peca.descricao
                        FROM tbl_pedido_item 
                        join tbl_pedido on tbl_pedido.pedido = tbl_pedido_item.pedido
                        join tbl_peca on tbl_peca.peca = tbl_pedido_item.peca and tbl_peca.fabrica = $login_fabrica 
                        where tbl_pedido_item.pedido = $pedido
                        AND tbl_pedido.fabrica = $login_fabrica "; 
            $resPecas = $pedidoClass->_model->getPDO()->query($sqlPecas);

            $dadosPecas = $resPecas->fetchAll(\PDO::FETCH_ASSOC);

            $dadosPecaIntencao = "";
            foreach($dadosPecas as $pecas){

                $pecaPedido     = $pecas['peca'];
                $pecaQtde       = $pecas['qtde'];
                $pecaPosto      = $pecas['posto'];
                $pecaReferencia = $pecas['referencia'];
                $pecaDescricao  = $pecas['descricao'];

                if(verificaEstoque($pecaQtde, $pecaPedido, $pedidoClass) == false){
                    if(strlen(trim($resposta_intencao))==0){
                        if(!VerificaPecaAlternativa($pecaPedido, $pecaQtde, $pedidoClass)){
                            gravaIntencaoCompra($pecaPedido, $pecaPosto, $pecaQtde, $pedido, $pedidoClass);
                            $dadosPecaIntencao .= "<br> $pecaReferencia - $pecaDescricao ";   
                        }
                    }else{
                        if(!VerificaPecaAlternativa($pecaPedido, $pecaQtde, $pedidoClass)){
                            retiraPecaIndisponivelPedido($pecaPedido, $pedido, $pedidoClass);
                        }
                    }
                }
            }

            $sqlqtd = "select count(1) as qtdepecapedido from tbl_pedido_item where pedido = $pedido";
            $resqtd = $pedidoClass->_model->getPDO()->query($sqlqtd);
            $dadosqtd = $resqtd->fetchAll(\PDO::FETCH_ASSOC);
            $qtdepecapedido = $dadosqtd[0]['qtdepecapedido'];

            if($qtdepecapedido == 0){
                throw new Exception("Falha ao finalizar pedido $pedido. Todas as peças est?o indispon?veis entrar em contato com o fabricante atrav?s do e-mail 'pedido.pecas@einhell.com.br' ou pelo telefone (19) 2136-4497. ");
            }

            if(strlen(trim($resposta_intencao))>0){
                $sqltotal = "UPDATE tbl_pedido set total = (SELECT SUM(tbl_pedido_item.preco * tbl_pedido_item.qtde) 
                            FROM tbl_pedido_item WHERE pedido = $pedido and fabrica = $login_fabrica) 
                            WHERE tbl_pedido.pedido =$pedido ";
                $restotal = $pedidoClass->_model->getPDO()->query($sqltotal);
            }

            if(strlen($dadosPecaIntencao)>0 and strlen(trim($resposta_intencao))==0){
                throw new \Exception("As peças abaixo est?o indispon?veis
                    $dadosPecaIntencao
                 <br> <Br>   
                Deseja continuar seu pedido com os itens dispon?veis? <br>
                <button class='btn btnsim'>Sim</button>  <button class='btn btnnao'>N?o</button>
                <br><br>
                Se necess?rio, entre em contato com o fabricante atrav?s do e-mail 'pedido.pecas@einhell.com.br' ou pelo telefone (19) 2136-4497
                    ");
            }
        }
        
        if ($FaturamentoManualArquivo && $auditoria_pedido_obrigatoria != true) {
            $dados["status_pedido"] = 2;
		}

        if(in_array($login_fabrica, [169,170])){
            $sql = "SELECT tbl_tipo_pedido.tipo_pedido, pedido_faturado FROM tbl_pedido join tbl_tipo_pedido on tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido where tbl_pedido.pedido = $pedido and tbl_pedido.fabrica = $login_fabrica";
            $res = pg_query($con,$sql);
            $pedido_faturado = pg_fetch_result($res, 0, 'pedido_faturado');

            if($pedido_faturado){
                $dados["status_pedido"] = 1;     
            }              
        }            

        if ($login_fabrica == 35 and $areaAdmin != true) {
            $dados['tabela'] = 174;
        }


        if ($login_fabrica == 143) {
            $dados['status_pedido'] = $reprocessamento_status;
        }

        if($login_fabrica == 160 or $replica_einhell){
            $retornoIntervencao = $pedidoClass->IntervencaoPedido($pedido);

            if($retornoIntervencao == TRUE){
                $dados['status_pedido'] = 18;
            }
        }

        if((in_array($login_fabrica, array(162)) || $auditoria_pedido_obrigatoria == true) and empty($pedido)) {
            $dados['status_pedido'] = 18;
        }


        if (in_array($login_fabrica, array(163,184,200)) AND $areaAdmin != true) {
            $dados['status_pedido'] = 18;
        }

        if ($login_fabrica == 147) {
            $dados['status_pedido'] = 18;
        }

        if($login_fabrica == 168){

            $dados['status_pedido'] = 25;
            $dados['tabela'] = 1069;

            $sql  = "SELECT tbl_condicao.codigo_condicao, tbl_condicao.acrescimo_financeiro, tbl_pedido.total
                     FROM tbl_condicao
                        INNER JOIN tbl_pedido ON tbl_pedido.condicao = tbl_condicao.condicao
                     WHERE
                     tbl_condicao.condicao = tbl_pedido.condicao AND tbl_pedido.fabrica = $login_fabrica AND tbl_pedido.pedido = $pedido";
            $res = pg_query($con, $sql);
            if(pg_num_rows($res)> 0){
                $acrescimo_financeiro    = pg_fetch_result($res, 0, 'acrescimo_financeiro');
                $total                   = pg_fetch_result($res, 0, 'total');
                $codigo_condicao = pg_fetch_result($res, 0, codigo_condicao);
            }

            $dados['status_pedido'] = 18;

            if(!empty($acrescimo_financeiro)){
                $acrescimo_financeiro   = ($acrescimo_financeiro - 1) * 100;
                $acrescimo_financeiro   = number_format($acrescimo_financeiro, 2, ".", ".");
                $dados['total']         = ( $total * $acrescimo_financeiro)/100 + $total;
            }
        }

        if($login_fabrica == 186 AND !empty($_POST["condicao"])){
            $sql = "SELECT codigo_condicao FROM tbl_condicao WHERE condicao = {$_POST["condicao"]}";
            $res = pg_query($con,$sql);

            if(pg_fetch_result($res, 0, 'codigo_condicao') == "BOL"){
                $dados['status_pedido'] = 18;
            }
        }


        if ($login_fabrica == 183 AND in_array($login_tipo_posto_codigo, array("Rev", "Rep"))){
            $dados["status_pedido"] = 1;
        }

        if(in_array($login_fabrica, array(151))){

            $tipo_frete = $pedidoClass->buscaTipoFretePosto();

            if($tipo_frete){
                $dados["tipo_frete"] = $tipo_frete; 
            }
        }

        $pedido = $pedidoClass->grava($dados, $pedido);

        if(in_array($login_fabrica, [160,186]) or $replica_einhell){

            $sql_status = "SELECT tbl_pedido.status_pedido
                           FROM   tbl_pedido
                           JOIN   tbl_status_pedido ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
                           WHERE  tbl_pedido.pedido  = $pedido
                           AND    tbl_pedido.fabrica = $login_fabrica";
            $res_status = pg_query($con, $sql_status);
            if (pg_num_rows($res_status) > 0) {
                $pedido_status      = pg_fetch_result($res_status, 0, 'status_pedido');
				$sql = "SELECT pedido FROM tbl_pedido_status WHERE pedido = $pedido and  status = $pedido_status "; 
				$res = pg_query($con, $sql);
				if(pg_num_rows($res) ==0) {
					$sql_status_insert = "INSERT INTO tbl_pedido_status (pedido, status, data) VALUES ($pedido, $pedido_status, now()) "; 
					$res_status_insert = pg_query($con, $sql_status_insert);
				}
             }   

            $resultado = $pedidoClass->getValorPedido($pedido);

            $total_pedido = $resultado['total'];
            $tipo_frete = $resultado['tipo_frete'];

			if($login_fabrica == 160) {
				if($tipo_frete == 'FOB' and $total_pedido >= 165){
					throw new Exception(traduz("o.tipo.de.frete.deve.ser.cif.para.esse.valor"));
				}

				if($tipo_frete == 'CIF' and $total_pedido <= 165){
					throw new Exception(traduz("o.tipo.de.frete.deve.ser.fob.para.esse.valor"));
				}
			}
        }

        if(in_array($login_fabrica, array(151))){
            $pedidoClass->verificaValorMinimoPosto();
        } else {
            $pedidoClass->verificaValorMinimo($pedido);
        }

        $login_posto = (strlen($login_posto) > 0) ? $login_posto : $_POST['posto_id'];

        if ($areaAdmin === false AND in_array($login_fabrica,array(147))) {
            $pedidoClass->verificaCredito($pedido,$login_posto);
        }

        if (in_array($login_fabrica, array(138,146,164,167,173,203))) {
            $pedidoClass->auditoria();
        }

        if ($login_fabrica == 183 AND $auditoria_pedido === true){
            $pedidoClass->auditoria();
        }else if ($login_fabrica == 194 AND $pedido_faturado){
            $pedidoClass->auditoria();
        }

        /*
            Devido a quantidade de chamados de erro relacionados a valores de pedido
            foi criada a valida??o abaixo para verificar se o total do pedido ? o mesmo
            que o total dos itens, e n?o deixar gravar o pedido caso tenha erro de valores
            divergentes.
        */
        $sql = "SELECT
                    SUM(total_item) AS total_item,
                    tbl_pedido.total,
                    tbl_pedido.valor_frete,
                    (tbl_pedido.desconto / 100) as percentual_desconto
                FROM tbl_pedido_item
                JOIN tbl_pedido ON tbl_pedido.pedido = tbl_pedido_item.pedido
                WHERE tbl_pedido_item.pedido = $pedido
                GROUP BY tbl_pedido.total,tbl_pedido.valor_frete,tbl_pedido.desconto";
        $res = pg_query($con, $sql);

        $total_itens            = pg_fetch_result($res, 0, 'total_item');
        $total_pedido           = pg_fetch_result($res, 0, 'total');
        $valor_frete            = pg_fetch_result($res, 0, 'valor_frete');
        $percentual_desconto    = pg_fetch_result($res, 0, 'percentual_desconto');

        if (!empty($percentual_desconto) && $percentual_desconto > 0) {
            $total_itens_desconto   = round(($total_itens * (1 - $percentual_desconto)),2);
        }

        if (in_array($login_fabrica, array(164))) {
            $total_itens += $valor_frete;
        }

        if ($total_itens != $total_pedido) {
            if ($login_fabrica == 35) {
                $botao_recalcular = true;

                throw new Exception(traduz("erro.ao.calcular.o.valor.total.do.pedido.favor.clicar.no.botao.recalcular.caso.erro.persistir.favor.entrar.em.contato.com.a.telecontrol"));
            } else {
                if (!empty($total_itens_desconto) && ($total_itens_desconto != $total_pedido)) {
                    if ($login_fabrica != 168) {
                        //Erro de valores
                        $botao_recalcular = true;

                        throw new Exception(traduz("erro.ao.calcular.o.valor.total.do.pedido.favor.clicar.no.botao.recalcular.caso.erro.persistir.favor.entrar.em.contato.com.a.telecontrol"));
                    }
                }
            }
        }

        $pedidoClass->_model->getPDO()->commit();

        if(!empty($pedido)){
            $sql_posto = "SELECT 
                            tbl_posto_fabrica.contato_email as contato_email,
                            tbl_fabrica.nome as fabrica_nome,
                            tbl_posto.nome as posto_nome 
                        FROM tbl_posto_fabrica 
                        JOIN tbl_fabrica on (tbl_fabrica.fabrica = tbl_posto_fabrica.fabrica) 
                        JOIN tbl_posto on (tbl_posto.posto = tbl_posto_fabrica.posto) 
                        where tbl_posto_fabrica.fabrica = $login_fabrica and tbl_posto_fabrica.posto = $login_posto";


            $res_posto = pg_query($con, $sql_posto);

            $contato_email = pg_fetch_result($res_posto, 0, 'contato_email');
            $fabrica_nome = pg_fetch_result($res_posto, 0, 'fabrica_nome');
            $posto_nome = pg_fetch_result($res_posto, 0, 'posto_nome');

            $assunto       = "Pedido nº ".$pedido. " - ". $fabrica_nome;
            $corpo         = email_pedido($posto_nome, $fabrica_nome, $pedido, $cook_login);

            $mailTc = new TcComm($externalId);
            $res = $mailTc->sendMail(
                $contato_email,
                $assunto,
                utf8_encode($corpo),
                $externalEmail
            );
        }
       

        if(!empty($pedido)) {
            header("Location: pedido_finalizado.php?pedido=$pedido");
        }
        exit;

    } catch(Exception $e) {
        $msg_erro["msg"][] = $e->getMessage();

        if ($inTransaction)
            $pedidoClass->_model->getPDO()->rollBack();
    }

}

$title = traduz("cadastro.de.pedidos.de.pecas");
if($login_fabrica == 161){

    include_once "os_cadastro_unico/fabricas/161/classes/verificaDebitoPosto.php";
    $verificaDebitoPosto = new verificaDebitoPosto($login_posto);
    $dadosRetorno = $verificaDebitoPosto->retornaDebitos();
    
    $dadosRetorno = json_decode($dadosRetorno, true);    

	$total_duplicata = preg_replace("/\D/","",$dadosRetorno['total_venc']) ; 
	if($total_duplicata > 0){ 
        $msg_alerts['danger'][] = "<b>
            ".traduz("favor.entrar.em.contato.com.o.financeiro.da.cristofoli").". <br>
            ".traduz("numero.nota.fiscal").":  ".$dadosRetorno['duplicatas'][0]['NUM_NF']." <br>
            ".traduz("numero.da.duplicata").":  ".$dadosRetorno['duplicatas'][0]['NUM_DUPLI']."<br>
            ".traduz("numero.do.pedido").":  ".$dadosRetorno['duplicatas'][0]['NUM_PEDIDO']."<br>
            ".traduz("data.vencimento.duplicata").":  ".$dadosRetorno['duplicatas'][0]['DAT_VENCTO_S_DESC']."<br>
            ".traduz("data.emissao.duplicata").":  ".$dadosRetorno['duplicatas'][0]['DAT_EMIS']." <br>
            ".traduz("valor.duplicata").":  ".$dadosRetorno['duplicatas'][0]['VALOR']." <br>
        </b>";
    }
}

if ($areaAdmin === true) {
    $layout_menu = "callcenter";
    include_once __DIR__.'/admin/cabecalho_new.php';
} else {
    $layout_menu = 'pedido';
    include_once __DIR__.'/cabecalho_new.php';

    $login_bloqueio_pedido = $_COOKIE['cook_bloqueio_pedido'];

    if($telecontrol_distrib AND $login_bloqueio_pedido){
        echo "<div class='alert alert-error'>";
            echo "<b>".traduz("entre.em.contato.com.acaciaeletro")."</b><br>";
            echo "<b>
                    ".traduz("email")." - contabil@acaciaeletro.com.br <br>
                    ".traduz("telefone").": (011) 4063-0036
                </b>";
        echo "</div>";
            include "rodape.php";
            exit;
    }
}

if (count($msg_erro["msg"]) > 0) {
?>
    <br/>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?
}

$plugins = array(
   "shadowbox",
   "maskedinput",
   "alphanumeric",
   "ajaxform",
   "fancyzoom",
   "price_format",
   "font_awesome",
   "toastr"
);

include __DIR__."/admin/plugin_loader.php";

?>
<style>

    .classeErroEstoque{
        background-color: #e08d8b;
        color: #ffffff;
    }

    .classeErroEstoque2{
        background-color: #e08d8b;
        color: #ffffff;
        margin-bottom: 10px
    }

    .env_disponivel {
        background: #6fab68;
        height: 57px;
        border-radius: 5px;
    }
    .env_saldo_usado {
        background: #ef5a5a;
        height: 57px;
        border-radius: 5px;
    }
    .msg_porcetagem_cadence{
        color: red;
        font-weight: bold;
    }

    #div_trocar_posto,#div_trocar_peca,#div_trocar_transportadora{
        display: none;
        height: 40px;
    }

    #desc-total-pedido, #desc-total-sem-ipi{
        line-height: 30px;
    }

    .carrinho-header, .carrinho-total {
        background-color: #596D9B;
        color: #FFF;
        font-weight: bold;
        border: 1px solid #FFF;
        min-height: unset !important;
        height: auto !important;
    }

    .carrinho-header > div[class^=span] {
        line-height: 15px;
        box-shadow: -1px 0px 0px #FFF, 0px 0px 0px #FFF;
        text-indent: 5px;
    }

    .carrinho-header > div:first-child {
        box-shadow: none;
    }

    .div_pecas > div.row-fluid {
        background-color: #FAFAFA !important;
        min-height: unset !important;
        height: auto !important;
        padding-top: 20px;
        padding-bottom: 20px;
    }

    .div_pecas > div.row-fluid:nth-child(even) {
        background-color: #D9E2EF !important;
    }

    .carrinho-total > div {
        padding-top: 20px;
        padding-bottom: 20px;
    }

    <?php if ($login_fabrica == 143 && $areaAdmin == false) { ?>
    div#div_informacoes_transportadora {
        padding-left: 73px !important;
    }
    <?php } else if ($login_fabrica == 143 && $areaAdmin === true) { ?>
        div#div_informacoes_transportadora {
        padding-left: 57px !important;
    }
    <?php } ?>

</style>

<script type="text/javascript">
var FABRICA = <?=$login_fabrica?>; 

$(window).load(function(){
    var tipo_pedido = $("select[name=tipo_pedido]").val();
    var marca       = $("select[name=marca]").val();

    if (typeof tipo_pedido != "undefined") {
        $("select[name=tipo_pedido]").data("tipo-pedido", tipo_pedido);
        verificaDesconto(tipo_pedido);
    }

    <? if ($areaAdmin === true) { ?>
        var tabela = $("select[name=tabela] option:selected").val();

        if (typeof tabela != "undefined") {
            $("select[name=tabela]").data("tabela", tabela);
        }
    <? } ?>

    <?php
    if (in_array($login_fabrica, array(163))) { ?>
        var entrega = $("select[name=entrega]").val();

        if (entrega == 'TRANSP') {
            $("#div_informacoes_transportadora").show();
            $("#anexo_retirada").hide();
        } else {
            $("transportadora_cnpj").val();
            $("transportadora_nome").val();
            $("#div_informacoes_transportadora").hide();
            $("#anexo_retirada").show();
        }

        if ($("#entrega").val() == '' ) {
            $("#div_informacoes_transportadora").hide();
            $("#anexo_retirada").hide();

        }

        $("#entrega").change(function() {

            var posicao = 0;
            var button = $(".btn_acao_anexo");

            if ($(this).val() == 'TRANSP') {

                $("#div_informacoes_transportadora").show();
                $("#anexo_retirada").hide();

                if($("input[name='anexo["+posicao+"]']").val() != ""){

                    $("input[name='anexo["+posicao+"]']").val("");
                    $("#div_anexo_"+posicao).find(".anexo_thumb").remove();
                    $("#div_anexo_"+posicao).find("a").remove();
                    $("#div_anexo_"+posicao).prepend('<img class="anexo_thumb" style="width: 100px; height: 90px;" src="imagens/imagem_upload.png">');

                }

            } else if ($(this).val() == 'RFAB') {

                $("transportadora_cnpj").val();
                $("transportadora_nome").val();
                $("#div_informacoes_transportadora").hide();
                $("#anexo_retirada").show();

            } else {

                $("transportadora_cnpj").val();
                $("transportadora_nome").val();
                $("#div_informacoes_transportadora").hide();
                $("#anexo_retirada").hide();
                //$('#my_image').attr('src','imagens/imagem_upload.png');

                if($("input[name='anexo["+posicao+"]']").val() != ""){

                    $("input[name='anexo["+posicao+"]']").val("");
                    $("#div_anexo_"+posicao).find(".anexo_thumb").remove();
                    $("#div_anexo_"+posicao).find("a").remove();
                    $("#div_anexo_"+posicao).prepend('<img class="anexo_thumb" style="width: 100px; height: 90px;" src="imagens/imagem_upload.png">');

                }

            }

        });

        /**
        * Inicio anexo S3
        * Eventos para anexar imagem
        * Eventos para anexar/excluir imagem
        **/
        $("button.btn_acao_anexo").click(function(){
            var name = $(this).attr("name");
            if (name == "anexar") {
                $(this).trigger("anexar_s3", [$(this)]);
            }else{
                $(this).trigger("excluir_s3", [$(this)]);
            }
        });

        $("button.btn_acao_anexo").bind("anexar_s3",function(){

            var posicao = $(this).attr("rel");

            var button = $(this);

            $("input[name=anexo_upload_"+posicao+"]").click();
        });

        $("button.btn_acao_anexo").bind("excluir_s3",function(){

            var posicao = $(this).attr("rel");
            var hd_chamado = $("#hd_chamado").val();

            var button = $(this);
            var nome_an_p = $("input[name='anexo["+posicao+"]']").val();
            // alert(nome_an_p);
            // return;
            $.ajax({
                url: "<?php echo $_SERVER['PHP_SELF']; ?>",
                type: "POST",
                data: { ajax_anexo_exclui: true, anexo_nome_excluir: nome_an_p, hd_chamado: hd_chamado },
                beforeSend: function() {
                    $("#div_anexo_"+posicao).find("button").hide();
                    $("#div_anexo_"+posicao).find("img.anexo_thumb").hide();
                    $("#div_anexo_"+posicao).find("img.anexo_loading").show();
                },
                complete: function(data) {
                    data = $.parseJSON(data.responseText);

                    if (data.error) {
                        alert(data.error);
                    } else {
                        $("#div_anexo_"+posicao).find("a[target='_blank']").remove();
                        $("#baixar_"+posicao).remove();
                        $(button).text("Anexar").attr({
                            id:"anexar_"+posicao,
                            class:"btn btn-mini btn-primary btn-block",
                            name: "anexar"
                        });
                        $("input[name='anexo["+posicao+"]']").val("");
                        $("#div_anexo_"+posicao).prepend('<img class="anexo_thumb" style="width: 100px; height: 90px;" src="imagens/imagem_upload.png">');

                        $("#div_anexo_"+posicao).find("img.anexo_loading").hide();
                        $("#div_anexo_"+posicao).find("button").show();
                        $("#div_anexo_"+posicao).find("img.anexo_thumb").show();
                        alert(data.ok);
                    }

                }
            });
        });

        $("form[name=form_anexo]").ajaxForm({
            complete: function(data) {
                data = $.parseJSON(data.responseText);

                if (data.error) {
                    alert(data.error);
                } else {
                    var imagem = $("#div_anexo_"+data.posicao).find("img.anexo_thumb").clone();
                    $(imagem).attr({ src: data.link });

                    $("#div_anexo_"+data.posicao).find("img.anexo_thumb").remove();

                    var link = $("<a></a>", {
                        href: data.href,
                        target: "_blank"
                    });

                    $(link).html(imagem);

                    $("#div_anexo_"+data.posicao).prepend(link);

                    if ($.inArray(data.ext, ["doc", "pdf", "docx"]) == -1) {
                        setupZoom();
                    }

                    $("#div_anexo_"+data.posicao).find("input[rel=anexo]").val(data.arquivo_nome);
                }

                $("#div_anexo_"+data.posicao).find("img.anexo_loading").hide();
                $("#div_anexo_"+data.posicao).find("button").show();
                $("#div_anexo_"+data.posicao).find("img.anexo_thumb").show();
            }
        });

        $("input[name^=anexo_upload_]").change(function() {
            var i = $(this).parent("form").find("input[name=anexo_posicao]").val();

            $("#div_anexo_"+i).find("button").hide();
            $("#div_anexo_"+i).find("img.anexo_thumb").hide();
            $("#div_anexo_"+i).find("img.anexo_loading").show();

            $(this).parent("form").submit();
        });

        //Fim anexo S3
    <?php
    }
    ?>

    <?php if($login_fabrica == 160 or $replica_einhell){ ?>
        $(".btnsim").click(function(){
            $("#resposta_intencao").val('true');
            $("#pedido_finaliza").submit();
        });

        $(".btnnao").click(function(){
            $(".alert-error").hide();
        });

    <?php } ?>

    $("input[name=lupa_config]").each(function(){
        if (typeof tipo_pedido != "undefined") {
            $(this).attr("tipo-pedido",tipo_pedido);
        }

        <? if ($areaAdmin === true) { ?>
            if (typeof tabela != "undefined") {
                $(this).attr("tabela",tabela);
            }
        <? } ?>

        if (typeof marca != "undefined") {
            $(this).attr("marca",marca);
        }
    });

    <? if (strlen($pedido) > 0 && $areaAdmin === true) { ?>
        $("#div_informacoes_posto input, #div_pedido input ,#div_pedido textarea ").each(function() {
            if ($(this).prev("h5").length > 0) {
                $(this).attr({ readonly: "readonly" });

                if ($(this).next("span[rel=lupa]").length > 0) {
                    $(this).next("span[rel=lupa]").hide();
                }
            } else if ($(this).val().length > 0) {
                $(this).attr({ readonly: "readonly" });

                if ($(this).next("span[rel=lupa]").length > 0) {
                    $(this).next("span[rel=lupa]").hide();
                }
            }
        });

        $("#div_informacoes_posto select, #div_pedido select ").each(function() {
            var option_remove = false;

            <?php if ($areaAdmin === true && $login_fabrica == 168) {?>
            if ($(this).attr("name") == "condicao") {
                return;
            }
            <?php }?>

            if ($(this).prev("h5").length > 0 && $(this).attr('rel') != 'noreadonly') {
                $(this).attr({ readonly: "readonly" });
                option_remove = true;

            } else if ($(this).val().length > 0 && $(this).attr('rel') != 'noreadonly') {
                $(this).attr({ readonly: "readonly" });
                option_remove = true;
            }

            if (option_remove == true) {
                $(this).find("option").each(function() {
                    if (!$(this).is(":selected")) {
                        $(this).remove();
                    }
                });
            }
        });
    <? } ?>
});

function verificaDesconto(tp) {

    if (typeof tp != "undefined" && tp != "") {
        <?php
        if ($areaAdmin === true) {
        ?>
            var posto  = $("#posto_id").val();
            var tabela = $("select[name=tabela]").val();

            var dataAjax = {
                verifica_tipo_pedido: "ok",
                tipo_pedido: tp,
                posto: posto,
                tabela_nova: tabela
            };
        <?php
        } else {
        ?>
            var pedido = <?=(!empty($cook_pedido)) ? $cook_pedido : "null"?>;

            var dataAjax = {
                verifica_tipo_pedido: "ok",
                tipo_pedido: tp,
                pedido: pedido
            };
        <?php
        }
        ?>
	<?php if ($login_fabrica == 183){ ?>
		var linha_produto = "";
		if( $("#linha_posto_pedido").val() != "" && $("#linha_pedido_posto").val() != "undefined" ){
			linha_produto = $("#linha_posto_pedido").val();
			dataAjax.linha = linha_produto;
		}
	<?php } ?>	
	$.ajax({
            url: "cadastro_pedido.php",
            type: "POST",
            data: dataAjax,
            complete: function(data) {
            data = JSON.parse(data.responseText);

            if (typeof data == "undefined" || data == null || data.diferente == false) {
                <?php
                if ($areaAdmin === true) {
                ?>
                    $("select[name=tabela]").data("tabela",$("select[name=tabela]").val());

                    $("input[name=lupa_config]").each(function(){
                        $(this).attr("tabela",$("select[name=tabela]").val());
                    });
                <?php
                } else {
                ?>
                    $("select[name=tipo_pedido]").data("tipo-pedido", $("select[name=tipo_pedido]").val());

                    $("input[name=lupa_config]").each(function(){
                        $(this).attr("tipo-pedido", $("select[name=tipo_pedido]").val());
                    });
                <?php
                }
                ?>
            } else if (data.diferente == true) {
                <?php
                if ($areaAdmin === true) {
                ?>
                    var confirmText = "<?php echo traduz("para.a.tabela.selecionado.sera.recalculado.as.pecas.a.serem.lancadas.e.as.ja.lancadas.deseja.prosseguir");?>";
                <?php
                } else {
                ?>
                    var confirmText = "<?php echo traduz("para.o.tipo.de.pedido.selecionado.sera.recalculado.as.pecas.a.serem.lancadas.e.as.ja.lancadas.deseja.prosseguir");?>";
                <?php
                }
                ?>

                if (confirm(confirmText)) {
                    $('#recalcula_pedido').val("t");

                    $("input[rel=peca_id]").each(function() {
                        var peca = $(this).val();
                        var posicao = $(this).attr("posicao");

                        if (typeof peca == "undefined" || peca == "") {
                            $("#preco_"+posicao).val("");
                        } else {
                            <?php
                            if ($areaAdmin === true) {
                            ?>
                                var dataPecaAjax = {
                                    altera_valor_peca: "ok",
                                    peca: peca,
                                    tabela: $("select[name=tabela]").val()
                                };
                            <?php
                            } else {
                            ?>
                                var dataPecaAjax = {
                                    altera_valor_peca: "ok",
                                    peca: peca,
                                    tipo_pedido: tp,
                                    posto_id: "<?=$login_posto?>"
                                };
                            <?php
                            }
                            ?>

                            $.ajax({
                                url : "cadastro_pedido.php",
                                type: "POST",
                                data: dataPecaAjax,
                                complete: function(data){
                                    data = $.parseJSON(data.responseText);

                                    $("#preco_"+posicao).val(data.preco_peca);
                                    fnc_calcula_total(posicao);
                                }
                            });
                        }
                    });

                    <?php
                    if ($areaAdmin === true) {
                    ?>
                        $("select[name=tabela]").data("tabela", $("select[name=tabela]").val());

                        $("input[name=lupa_config]").each(function(){
                            $(this).attr("tabela", $("select[name=tabela]").val());
                        });
                    <?php
                    } else {
                    ?>
                        $("select[name=tipo_pedido]").data("tipo-pedido", $("select[name=tipo_pedido]").val());

                        $("input[name=lupa_config]").each(function(){
                            $(this).attr("tipo-pedido", $("select[name=tipo_pedido]").val());
                        });
                    <?php
                    }
                    ?>
                } else {
                    $('#recalcula_pedido').val("f");

                    <?php
                    if ($areaAdmin === true) {
                    ?>
                        $("select[name=tabela]").val($("select[name=tabela]").data("tabela"));

                        $("input[name=lupa_config]").each(function(){
                            $(this).attr("tabela", $("select[name=tabela]").val());
                        });
                    <?php
                    } else {
                    ?>
                        $("select[name=tipo_pedido]").val($("select[name=tipo_pedido]").data("tipo-pedido"));

                        $("input[name=lupa_config]").each(function(){
                            $(this).attr("tipo-pedido", $("select[name=tipo_pedido]").val());
                        });
                    <?php
                    }
                    ?>
                }
            }

            if (data !== undefined && data !== null) {
                if(data.usa_desconto == "sim") {
                    var desconto = data.desconto;
                    $("#valor_desconto").val(desconto);
                }else{
                    $("#valor_desconto").val(0);
                }
            }


            <?php if ($login_fabrica == 168 && empty($cook_pedido)) { ?>
                fnc_calcula_total(0);
            <?php } ?>

            <?php
            if (in_array($login_fabrica, array(147,149,153,156,157,165)) || ($usa_calculo_ipi) || (regiao_suframa() == false && in_array($login_fabrica, array(161)))) { ?>

                var qtde_itens_pedido = $('#qtde_itens_pedido').val();

                $("input[id^=qtde_]").each(function() {
                    for(var x = 0;x<qtde_itens_pedido;x++) {
                        fnc_calcula_total(x);
                    }
                });
            <?php
            }
            ?>

            }
        });
    }
}

function calcula_desconto_fabrica(){
    var desconto        = 0;
    var total_pecas     = 0;
    total_pecas = $("#total_pecas_hidden").val();
    //desconto = parseFloat($("#valor_desconto_fabricante").val());
    //if(desconto > 0){
        desconto = $("#valor_desconto_fabricante").val();
        if(desconto.length == 0){
            desconto = 0;
            desconto = desconto.toFixed(2);
        }

        desconto = desconto.replace(".", "");
        desconto = desconto.replace(",", ".");

        desconto = parseFloat(desconto);

        total_pecas = total_pecas.replace(".", "");
        total_pecas = total_pecas.replace(",", ".");

        total_pecas = parseFloat(total_pecas);



        total_desconto =  total_pecas * (desconto / 100);

        total_pecas = parseFloat(total_pecas) - parseFloat(total_desconto);

        adicional = $("#adicional_fabricante").val();

        adicional = adicional.replace(".", "");
        adicional = adicional.replace(",", ".");
        adicional = parseFloat(adicional);
        if(adicional > 0){
            total_pecas = parseFloat(total_pecas) + parseFloat(adicional);
        }

        //total_pecas = total_pecas.toFixed(2);

        $("#total_pecas").val(number_format(total_pecas, 2, ",", "."));

        return parseFloat(total_pecas);

    //}
}

function calcula_adicional_fabrica(){

    var adicional       = 0;
    var total_pecas     = 0;
    total_pecas = $("#total_pecas_hidden").val();
    adicional = $("#adicional_fabricante").val();

    if(adicional.length == 0){
        adicional = 0;
        adicional = adicional.toFixed(2);

    }

    adicional = adicional.replace(".", "");
    adicional = adicional.replace(",", ".");

    adicional = parseFloat(adicional);
    desconto = parseFloat($("#valor_desconto_fabricante").val());
    if(desconto > 0){
        total_pecas = calcula_desconto_fabrica();
    }else{
        total_pecas = total_pecas.replace(".", "");
        total_pecas = total_pecas.replace(",", ".");
    }

    total_pecas = parseFloat(total_pecas);

    total_pecas = parseFloat(total_pecas + adicional);

    $("#total_pecas").val(number_format(total_pecas, 2, ",", "."));

}

$(function() {


    $(document).on("change","#linha", function() {

       for (var i = 0; i < $("#qtde_item").val(); i++) {
            $("input[name='total_pecas']").val("0,00");
            $("input[name='total_pecas_hidden']").val("0,00");
            $("input[name='sub_total_"+i+"']").val("0,00");
            $("input[name='pedido_item_"+i+"']").val("");
            $("input[name='peca_id_"+i+"']").val("");
            $("input[name='peca_referencia_"+i+"']").val("");
            $("input[name='peca_referencia_"+i+"']").val("").removeAttr("readonly");
            $("input[name='peca_descricao_"+i+"']").val("");
            $("input[name='peca_descricao_"+i+"']").val("").removeAttr("readonly");
            $("input[name='qtde_"+i+"']").val("");
            $("input[name='multiplo_"+i+"']").val("");
            $("input[name='preco_"+i+"']").val("");
            $("div[name=peca_"+i+"]").find("span[rel=lupa_peca]").show();
            $("button[name=remove_peca_"+i+"]").hide();
            
            <?php if ($login_fabrica == 183){ ?>
                $("select[name='causa_defeito_"+i+"']").val("");
            <?php } ?>
        }

    });

    <?php if(in_array($login_fabrica, array(161,167,203))){ ?>

        $("#adicional_fabricante").blur(function(){
            calcula_adicional_fabrica();
        });

        $("#valor_desconto_fabricante").blur(function(){
            calcula_desconto_fabrica();
        });
    <?php } ?>


    <? if ($areaAdmin === true) { ?>
        var tabela = "";
        var tipo_pedido ="";

        $("select[name=tipo_pedido]").change(function(){
            verificaDesconto($(this).val());

            var posto = $("#posto_id").val();
            var tipo_pedido = $("select[name=tipo_pedido]").val();
            var tabela = $("select[name=tabela]").val();

            <? if (in_array($login_fabrica, array(157))) {  # alterar tamb?m para a ?rea do Posto no "else ($("select[name=tipo_pedido]").change)" ?>
                var pedido = "<?= $pedido; ?>";
                var existe_peca = false;

                if(tipo_pedido == "337"){
                    $(".pedidowapnormal").show();
                    $(".pedidowapventilacao").hide();
                }else if(tipo_pedido == "338"){
                    $(".pedidowapventilacao").show();
                    $(".pedidowapnormal").hide();
                }else{
                    $(".pedidowapnormal").hide();
                    $(".pedidowapventilacao").hide();
                }

                $("input[name^=peca_id_]").each(function() {
                    if ($(this).val() != "") {
                        existe_peca = true;
                    }
                });

                if (pedido.length == 0 && existe_peca) {
                    if (confirm("<?php echo traduz("deseja.realmente.alterar.o.tipo.de.pedido");?>")) {
                        $("button[name^=remove_peca_]").each(function() {
                            var desc_linha = $(this).attr("name").split("_");
                            if ($("input[name=peca_id_"+desc_linha[2]+"]").val() != "") {
                                $(this).trigger('click');
                            }
                        });
                    }
                }
            <? } ?>

            if (tipo_pedido.length > 0 && posto.length > 0 && tabela.length == 0){
                $.ajax({
                    url : "<?= $_SERVER['PHP_SELF']; ?>",
                    type: "POST",
                    data: {
                        verifica_tabela_ideal: "ok",
                        tipo_pedido: tipo_pedido ,
                        posto_id: posto
                    },
                    complete: function(data){
                        data = JSON.parse(data.responseText);

                        if (data.tabela != null) {
                            $("select[name=tabela]").val(data.tabela);
                            $("input[name=lupa_config]").each(function(){
                                $(this).attr("tabela",data.tabela);
                            });
                        }
                    }
                });
            }
            });

        $("select[name=tabela]").change(function() {
            var tabela = $(this).val();

            verificaDesconto($("select[name=tipo_pedido]").val());

            $("input[name=lupa_config]").each(function() {
                $(this).attr("tabela",$("select[name=tabela]").val());
            });

            $("input[rel=peca_id]").each(function() {
                var peca = $(this).val();
                var posicao = $(this).attr("posicao");

                if(peca.length == 0){
                    $("#preco_"+posicao).val("");
                }else{
                    $.ajax({
                        url: "cadastro_pedido.php",
                        type: "POST",
                        data: {
                            altera_valor_peca: "ok",
                            peca: peca,
                            tabela: tabela
                        },
                        complete: function(data){
                            data = $.parseJSON(data.responseText);

                            $("#preco_"+posicao).val(data.preco_peca);
                            fnc_calcula_total(posicao);
                        }
                    });
                }
            });
        });
    <? } else { ?>
        $("select[name=tipo_pedido]").change(function(){
            verificaDesconto($(this).val());
            <? if (in_array($login_fabrica, array(157))) { ?>
                var tipo_pedido = $("select[name=tipo_pedido]").val();
                var pedido = "<?= $pedido; ?>";
                var existe_peca = false;

                if(tipo_pedido == "337"){
                    $(".pedidowapnormal").show();
                    $(".pedidowapventilacao").hide();
                }else if(tipo_pedido == "338"){
                    $(".pedidowapventilacao").show();
                    $(".pedidowapnormal").hide();
                }else{
                    $(".pedidowapnormal").hide();
                    $(".pedidowapventilacao").hide();
                }

                $("input[name^=peca_id_]").each(function() {
                    if ($(this).val() != "") {
                        existe_peca = true;
                    }
                });

                if (pedido.length == 0 && existe_peca) {
                    if (confirm("<?php echo traduz("deseja.realmente.alterar.o.tipo.de.pedido");?>")) {
                        $("button[name^=remove_peca_]").each(function() {
                            var desc_linha = $(this).attr("name").split("_");
                            if ($("input[name=peca_id_"+desc_linha[2]+"]").val() != "") {
                                $(this).trigger('click');
                            }
                        });
                    }
                }
            <? } ?>
        });

        <?
        if ($login_fabrica == 157) {
        ?>
            var tipo_pedido = $("select[name=tipo_pedido]").val();

            if(tipo_pedido == "337"){
                $(".pedidowapnormal").show();
                $(".pedidowapventilacao").hide();
            }else if(tipo_pedido == "338"){
                $(".pedidowapventilacao").show();
                $(".pedidowapnormal").hide();
            }else{
                $(".pedidowapnormal").hide();
                $(".pedidowapventilacao").hide();
            }
        <?php
        }
    }
    ?>

});

function fnc_calcula_total (linha_form) {
    var total = 0;

    var multiplo = parseInt($('#multiplo_'+linha_form).val());
    var preco    = $('#preco_'+linha_form).val();
    var ipi      = parseInt($('#ipi_'+linha_form).val());
    var qtde     = parseInt($('#qtde_'+linha_form).val());

    <?php if (in_array($login_fabrica, [175])) { ?>
        let tipoUnidade = ['c', 'm', 'mm', 'cm', 'dm', 'mt', 'dam', 'hm', 'km'];
        let unidade = $('#unidade_'+linha_form).val();

        if ($.inArray(unidade, tipoUnidade) !== -1 ) {            
            var qtde = parseFloat($('#qtde_'+linha_form).val().replace(",", "."));
            if (qtde > 0) {
                $('#qtde_'+linha_form).val(qtde);
            }
        }
    <?php } ?>

    <?php if ($login_fabrica == 158) { ?>
            var qtde = parseFloat($('#qtde_'+linha_form).val().replace(",", "."));
            if (qtde > 0) {
                $('#qtde_'+linha_form).val(qtde);
            }
    <?php } ?>

    <?php if($login_fabrica == 161 && $areaAdmin == true){ ?>
    if($("#suframa_status").val() == "t"){
        ipi = 0;
    }
    <?php } ?>

    var comma_decimal_separator = /^(\d{1,}\.)*(\d{1,}\,\d+)/g;
    if(comma_decimal_separator.test(preco)){
        preco = parseFloat(preco.replace(".", "").replace(",", "."));
    }

    <?php
    $desconto_valor_peca = (in_array($login_fabrica, array(156,160)) or $replica_einhell) ? true : false;

    if ($desconto_valor_peca === true) {
    ?>
        var valor_desconto = parseFloat($("#valor_desconto").val());
    <?php
    }
    ?>

    if (!isNaN(qtde) && qtde > 0 && !isNaN(preco) && preco > 0) {
        <?php
        if ($desconto_valor_peca === true) {
        ?>
            if (!isNaN(valor_desconto) && valor_desconto > 0) {
                preco -= (preco / 100) * valor_desconto;
            }
        <?php
        }
        ?>

        <?php if($login_fabrica == 157){ ?>
            if (!isNaN(desconto_posto) && desconto_posto > 0) {
                preco -= (preco / 100) * desconto_posto;
            }
        <?php } ?>

        total = qtde * preco;

        <? if(in_array($login_fabrica, array(147,149,153,156,157,165)) || ($usa_calculo_ipi) || (regiao_suframa() == false && in_array($login_fabrica, array(161)))) { ?>

            $("#sub_total_sem_ipi_"+linha_form).val(number_format(total, 2, ",", "."));

        <? } ?>

        if (!isNaN(ipi) && ipi > 0) {
            total += qtde * (preco * (ipi / 100));
        }


        total = number_format(total, 2, ",", ".");
    }

    <?php if(in_array($login_fabrica, array(160,147)) || $replica_einhell || (regiao_suframa() == false && in_array($login_fabrica, array(161)))){?>
        var total_sem_ipi = 0;
        var a = 0;

        $("input[name^='preco_']").each(function(){
            var qtde_peca   = parseInt($('#qtde_'+a).val());
            var vunitario   = $(this).val();

            vunitario       = parseFloat(vunitario.replace(".","").replace(",", "."));

            if(vunitario > 0){
                if (typeof vunitario != "undefined" && vunitario > 0) {
                    total_sem_ipi += vunitario * qtde_peca;
                }
            }

            a = a +1;
        });
        total_sem_ipi = number_format(total_sem_ipi, 2, ",", ".");
        $("#total_sem_ipi").val(total_sem_ipi);

    <?php } ?>

    if((typeof multiplo != 'undefined' && multiplo > 0) && (qtde % multiplo != 0)){
        alert('Quantidade tem que ser multiplo de '+multiplo);
        $('#qtde_'+linha_form).val("");
        return;
    }

    document.getElementById('sub_total_'+linha_form).value = total;

    var total_pecas = 0;

    $("input[rel='total_pecas']").each(function(){
        tot = $(this).val();
        tot = parseFloat(tot.replace(".", "").replace(",", "."));

        if (typeof tot != "undefined" && tot > 0) {
            total_pecas += tot;
        }
    });

    <?
    $usa_desconto = \Posvenda\Regras::get("usa_desconto", "pedido_venda", $login_fabrica);

    if ($usa_desconto == true && !in_array($login_fabrica, array(157,158,160,163)) && !$replica_einhell) {?>
    	$("#total_pecas_desconto").val(number_format(total_pecas, 2, ",", "."));
    <?php 
    }

    $usa_frete    = \Posvenda\Regras::get("usa_frete", "pedido_venda", $login_fabrica);

    if ($usa_desconto == true){
        $desconto_tipo_pedido = (in_array($login_fabrica, array(138, 143))) ? true : false;

        $array_desconto_tipo_pedido = array(
            138 => "VENDA",
            143 => "USO/CONSUMO"
        );

        if ($desconto_tipo_pedido === true) {
        ?>
            var desconto_tipo_pedido = "<?=$array_desconto_tipo_pedido[$login_fabrica]?>";
            var valor_desconto       = parseFloat($("#valor_desconto").val());
            var tipo_pedido          = String($("select[name=tipo_pedido] option:selected").text()).trim().toUpperCase();

            if (!isNaN(valor_desconto) && tipo_pedido == desconto_tipo_pedido) {
                total_pecas -= (total_pecas / 100) * valor_desconto;
            }
        <?php
        } else if ($desconto_valor_peca === false && !in_array($login_fabrica, array(157,147))) {
        ?>
            var valor_desconto = parseFloat($("#valor_desconto").val());

            if (!isNaN(valor_desconto)) {
                total_pecas -= total_pecas * (valor_desconto / 100);
            }
        <?php
        }
    }

    if ($login_fabrica == 168) {
    ?>
        var tipo_de_condicao = $("#condicao_hidden").val();
        var valor_condicao = $("#condicao_hidden").attr('rel');

        if (tipo_de_condicao == 'desconto') {
            porcento = (valor_condicao / 100) * total_pecas;
            total_pecas -= porcento;
        } else if (tipo_de_condicao == 'acrescimo') {
            var qtd_parcelas = $("#nmr_parcelas").val();

            taxa = valor_condicao / 100;

            total_pecas = total_pecas * (Math.pow((1 + taxa), qtd_parcelas));

            //valor total
            //valParcela = number_format(valParcela / qtd_parcelas, 2, ",", ".");
        }

    <?
    }

    if (!empty($usa_frete)){ ?>


        var valor_frete = $("#valor_frete").val();
        valor_frete = parseFloat(valor_frete.replace(',','.'));

        if(!isNaN(valor_frete)){
            total_pecas = total_pecas + valor_frete;
        }
    <?
    }
    ?>

    <?php
    /* Frete por Estado */
    if(in_array($login_fabrica, array(163,164))){
        if($areaAdmin == true OR $login_fabrica == 163){
            ?>
            var valor_frete = $("#total_frete_estado").val();
            valor_frete = parseFloat(valor_frete.replace(",", "."));
            <?php
        }else{
            ?>
            var valor_frete = <?php echo number_format($valor_frete_estado, 2); ?>;
            <?php
        }
    ?>

    document.getElementById('total_parcial_pecas').value = number_format(total_pecas, 2, ",", ".");

    total_pecas += valor_frete;


    <?php
    }

?>


    total_pecas = number_format(total_pecas, 2, ",", ".");
    document.getElementById('total_pecas').value = total_pecas;

    <?php if(in_array($login_fabrica, array(157)) && $areaAdmin === false) { ?>

        var tipo_pedido = $("#tipo_pedido option:selected").text();

        $.ajax({
            url: "cadastro_pedido.php",
            type: "post",
            data: {
                verifica_condicao: true,
                tipo_pedido: tipo_pedido,
                total_pecas: total_pecas
            },
            complete: function(data){

                data = JSON.parse(data.responseText);

                var codigo_condicao    = data.codigo;
                var descricao_condicao = data.descricao;

                $("#condicao_codigo").val(codigo_condicao);
                $("#condicao_desc").val(descricao_condicao);

            }
        });

    <?php } ?>

    <?php if(in_array($login_fabrica, array(162,167,203))){?>
        $("#total_pecas_hidden").val(total_pecas);
        calcula_adicional_fabrica();
        calcula_desconto_fabrica();
    <?php } ?>

}

$(function() {
    /**
     * Inicia o shadowbox, obrigat\F3rio para a lupa funcionar
     */
    Shadowbox.init();

    $("#btn_recalcular").click(function(){
        $("input[name^=peca_id_]").each(function(){
            if ($(this).val() != "") {

                var posicao_peca = $(this).attr("posicao");

                fnc_calcula_total(posicao_peca);

            }
        });

        $("#btn_enviar").click();

    });


    <?php if (!in_array($login_fabrica, [158, 175])) { ?>
        $("input[id^=qtde_]").numeric();
    <?php } ?>

    $(document).on('blur','input[id^=qtde_]',function(){
        var posicao = $(this).data('posicao');
        fnc_calcula_total(posicao);
        <?php if(in_array($login_fabrica, array(162))){?>
            calcula_adicional_fabrica();
            calcula_desconto_fabrica();
        <?php }?>
    });

    <?php
    if (in_array($login_fabrica, array(163))) { ?>
        $(document).on('blur','input[id=total_frete_estado]',function(){
            fnc_calcula_total(0);
        });
    <?php
    }

    if ($login_fabrica == 168) { ?>
        $("#condicao").change(function() {
            var condicao = $(this).val();

            $.ajax({
                url: "cadastro_pedido.php",
                method: 'POST',
                data: {ajax_condicao: true, condicao: condicao}
            }).fail(function(){
                $('#alertaErro').show().find('h4').html('<?php echo traduz("erro.ao.encontrar.parcelas");?>');
            }).done(function(data){
                data = $.parseJSON(data);

                $('#nmr_parcelas').val(data.parcelas);
                $('#condicao_hidden').val(data.tipo_condicao);
                $('#condicao_hidden').attr('rel',data.valor);

                var qtde = 0;
                $('input[name^=sub_total_]').each(function(key) {
                    var valor = $('#sub_total_'+qtde).val();
                    fnc_calcula_total(qtde);
                    qtde++;
                });
            });

        });
    <?
    }

if (in_array($login_fabrica, array(162))) {
?>
    $(document).on('blur','input[id^=preco_]',function(){
        var aux = $(this).attr("id");
        var valores = aux.split("_");
        var posicao = valores[1];
        fnc_calcula_total(posicao);
        calcula_adicional_fabrica();
        calcula_desconto_fabrica();
    });
<?php
}
?>
    $("#transportadora_cnpj").mask("99.999.999/9999-99");

    /**
    ** Evento que chama a fun\E7\E3o de lupa para a lupa clicada
    **/
    $("span[rel=lupa]").click(function() {
        $.lupa($(this));
    });

    /**
     * Evento que chama a lupa de Peça
     */
    <? if ($areaAdmin === true) { ?>
        $(document).on("click", "span[rel=lupa_peca]", function() {
            var tabela       = $("#tabela").val();
            var posto_codigo = $("#posto_codigo").val();
            var posto_nome   = $("#posto_nome").val();
            var tipo_pedido  = $("#tipo_pedido").val();

        <? if (in_array($login_fabrica, array(157,162))) { ?>
            var pedido             = 't';
            var produto_referencia = $("#produto_referencia").val();
            var produto_descricao  = $("#produto_descricao").val();
            if (tipo_pedido.length == 0) {
                alert("<?php echo traduz("selecione.um.tipo.de.pedido");?>");
                return false;
            }
<?
                if ($login_fabrica == 162 && !empty($hd_chamado)) {
?>
            var callcenter = <?=$hd_chamado?>;
<?php
                }
?>
            // if (produto_referencia.length == 0 || produto_descricao.length == 0) {
            //  alert("Selecione um Produto para buscar Peças");
            //  return false;
            // }
        <? } ?>

            if(typeof tabela != "undefined"){

                if (tabela.length == 0) {
                    alert("<?php echo traduz("selecione.a.tabela.de.preco");?>");
                    return false;
                }

            }

            <?php if ($login_fabrica == 153): ?>
            if (tipo_pedido.length == 0) {
                alert("<?php echo traduz("selecione.um.tipo.de.pedido");?>");
                return false;
            }
            <?php endif ?>

            var parametros_lupa_peca = ["posicao","preco","tipo-pedido","marca", "telapedido"];

            $(this).next().attr("posto_codigo", posto_codigo);
            $(this).next().attr("posto_nome", posto_nome);
            $(this).next().attr("tipo-pedido",tipo_pedido);
            $(this).next().attr("tabela",tabela);

<?php
            if (in_array($login_fabrica, array(157,162))) {
?>
                $(this).next().attr("pedido",pedido);
                $(this).next().attr("produto_referencia",produto_referencia);
                $(this).next().attr("produto_descricao",produto_descricao);
<?php
                if ($login_fabrica_nome == 162) {
?>
                $(this).next().attr("callcenter",callcenter);
<?php
                }
            }
?>
            $(this).next().attr("telapedido",true);

            parametros_lupa_peca.push("posto_nome");
            parametros_lupa_peca.push("posto_codigo");
            parametros_lupa_peca.push("tipo-pedido");
            parametros_lupa_peca.push("tabela");

<?
            if (in_array($login_fabrica, array(157,162))) {
?>
                parametros_lupa_peca.push("pedido");
                parametros_lupa_peca.push("produto_referencia");
                parametros_lupa_peca.push("produto_descricao");
                parametros_lupa_peca.push("tipo-pedido");
<?
                if ($login_fabrica == 162) {
?>
                parametros_lupa_peca.push("callcenter");
<?
                }
            }
?>
            <?php if (in_array($login_fabrica, array(183))) { ?>
                var linha         = $("#linha option:selected").val();
                if (linha.length == 0) {
                    alert("Selecione uma Linha");
                    return false;
                }
                $(this).next().attr("linha",linha);
                parametros_lupa_peca.push("linha");
            <?php } ?>
            parametros_lupa_peca.push("telapedido"); 
            <? if(in_array($login_fabrica, array(146))) { ?>

                if ($("select[name=marca] option:selected").val().length == 0) {
                    alert("Por favor informe a Marca! ");
                }else{
                     $.lupa($(this), parametros_lupa_peca);
                }
            <? }else{ ?>
                 $.lupa($(this), parametros_lupa_peca);
            <? } ?>
        });

    <? } else { ?>

        $(document).on("click", "span[rel=lupa_peca]", function() {
            var tipo_pedido         = $("#tipo_pedido").val();

            <? if (in_array($login_fabrica, array(157))) { ?>
                var pedido             = 't';
                var produto_referencia = $("#produto_referencia").val();
                var produto_descricao  = $("#produto_descricao").val();

                /*if (produto_referencia.length == 0 || produto_descricao.length == 0) {
                    alert("Selecione um Produto para buscar Peças");
                    return false;
                }*/
            <? } ?>

            if (tipo_pedido.length == 0) {
                alert("Selecione o Tipo de Pedido");
                return false;
            }
            <? if(in_array($login_fabrica, array(147,149,153,156,157,165)) || ($usa_calculo_ipi) || (regiao_suframa() == false && in_array($login_fabrica, array(161)))) { ?>
                var parametros_lupa_peca = ["posicao","preco","tipo-pedido","marca","ipi", "telapedido"];
            <? } else { ?>
                var parametros_lupa_peca = ["posicao","preco","tipo-pedido","marca"];
            <? } ?>

			$(this).next().attr("tipo-pedido",tipo_pedido);

			<? if (in_array($login_fabrica, array(157))) { ?>
				$(this).next().attr("pedido",pedido);
				$(this).next().attr("produto_referencia",produto_referencia);
				$(this).next().attr("produto_descricao",produto_descricao);

				parametros_lupa_peca.push("pedido");
				parametros_lupa_peca.push("produto_referencia");
				parametros_lupa_peca.push("produto_descricao");
			<? } ?>

			$(this).next().attr("telapedido",true);

			parametros_lupa_peca.push("tipo-pedido");
			parametros_lupa_peca.push("telapedido");
            <?php if (in_array($login_fabrica, array(183))) { ?>
                var linha         = $("#linha option:selected").val();
                if (linha.length == 0) {
                    alert("Selecione uma Linha");
                    return false;
                }
                $(this).next().attr("linha",linha);
                parametros_lupa_peca.push("linha");
            <?php } ?>


			<? if(in_array($login_fabrica, array(146))) { ?>

				if( $("select[name=marca] option:selected").val().length == 0 ){
					alert('<?=traduz("Por favor informe a Marca! ")?>');
				}else{
					 $.lupa($(this), parametros_lupa_peca);
				}
			<? }else{ ?>
				 $.lupa($(this), parametros_lupa_peca);
			<? } ?>
		});
    <? 
    }
    
    if ($catalogoPedido) {
    ?>
        $('#catalogo-pecas').on('click', function() {
            <?php
            if ($areaAdmin === true) {
            ?>
                let posto = $('#posto_id').val();
                
                if (posto.length == 0) {
                    alert('<?=traduz("Selecione o Posto Autorizado")?>');
                    return false;
                }
                
                let tabela = $('#tabela').val();
                
                if (tabela.length == 0) {
                    alert('<?=traduz("Selecione a Tabela de Pre\E7o")?>');
                    return false;
                }
            <?php
            }
            ?>
            
            let tipoPedido = $('#tipo_pedido').val();
            
            if (tipoPedido.length == 0) {
                alert('<?=traduz("Selecione o Tipo de Pedido")?>');
                return false;
            }
            
            let m = $('#modal-catalogo-pecas');
            let iframe = $('<iframe></iframe>', {
                <?php
                if ($areaAdmin === true) {
                ?>
                    src: 'peca_lupa_new.php?telapedido=true&preco=true&catalogo_peca=true&tipo-pedido='+tipoPedido+'&posto='+posto+'&tabela='+tabela,
                <?php
                } else {
                ?>
                    src: 'peca_lupa_new.php?telapedido=true&preco=true&catalogo_peca=true&tipo-pedido='+tipoPedido,
                <?php
                }
                ?>
                css: {
                    width: '100%',
                    height: '100%'
                }
            });

	    $(m).on('shown', function() {
		  $("#loading").show();
	    });

        <?php if (in_array($login_fabrica, [175])) { ?>
        setTimeout(function() { 
             $("#loading").hide();
        }, 3000);
        <?php } ?>
            
            $(m).modal('show');
            $(m).find('.modal-body').html(iframe);
        });
    <?php
    }
    ?>

   $("#trocar_posto").click(function() {

        $("#div_informacoes_posto").find("input").val("");
        $("#div_informacoes_posto").find("input[readonly=readonly]").removeAttr("readonly");
        $("#div_informacoes_posto").find("span[rel=lupa]").show();
        $("#div_trocar_posto").hide();

        $("input[name=lupa_config][tipo=lista_basica]").attr({ posto: "" });

        $("#pedido_cliente").val("");
        $("#condicao").val("");
        $("#tipo_pedido").val("");

        <?php if($login_fabrica == 161 && $areaAdmin == true){ ?>

            $("#suframa_status").val("");
            opcao_ipi("f");

        <?php } ?>

        <? if($login_fabrica == 151 && $areaAdmin === true) { ?>
            VerificaTipoFretePosto();
        <?php } ?>

    });

   $("#trocar_transportadora").click(function() {

        $("#div_informacoes_transportadora").find("input").val("");
        $("#div_informacoes_transportadora").find("input[readonly=readonly]").removeAttr("readonly");
        $("#div_informacoes_transportadora").find("span[rel=lupa]").show();
        $("#div_trocar_transportadora").hide();
    });
    /**
     * Evento que limpa uma linha de Peça
     */
    $(document).on("click", "button[name^=remove_peca_]", function() {
        var posicao = $(this).attr("rel");

        var pedido_item = $("input[name=pedido_item_"+posicao+"]").val();
        var pedido      = $("input[name=pedido]").val();
        var item        = $("input[name='peca_referencia_"+posicao+"']").val();
        var btn         = $(this);

        var erro = false;

        if (typeof pedido_item != "undefined" && pedido_item.length > 0) {
            $.ajax({
                async: false,
                url: "cadastro_pedido.php",
                data: {
                    ajax_deletar_item_pedido: "deletar",
                    pedido: pedido,
                    pedido_item: pedido_item
                },
                beforeSend: function() {
                    $(btn).prop({ disabled: true }).text('<?=traduz("Aguarde...")?>');
                },
                type: "POST",
                timeout: 10000
            }).fail(function(response) {
                alert('<?=traduz("Tempo limite esgotado, tente novamente")?>');
                $(btn).prop({ disabled: false }).text("X");
                erro = true;
            }).done(function(response) {
                response = JSON.parse(response);

                if (response.erro) {
                    alert(response.erro);
                    erro = true;
                } else if (response.resumo_excluido) {
                    alert("<?php echo traduz("o.pedido.foi.deletado.a.pagina.sera.atualizada.para.o.lancamento.de.um.novo.pedido");?>");
                    window.location = "cadastro_pedido.php";
                    return false;
                }

                $(btn).prop({ disabled: false }).text("X");
            });
        }

        if (erro === false) {
            $("input[name='pedido_item_"+posicao+"']").val("");
            $("input[name='peca_id_"+posicao+"']").val("");
            $("input[name='peca_referencia_"+posicao+"']").val("");
            $("input[name='peca_referencia_"+posicao+"']").val("").removeAttr("readonly");
            $("input[name='peca_descricao_"+posicao+"']").val("");
            $("input[name='peca_descricao_"+posicao+"']").val("").removeAttr("readonly");
            $("input[name='qtde_"+posicao+"']").val("");
            $("input[name='multiplo_"+posicao+"']").val("");
            <?php if ($login_fabrica == 183){ ?>
                $("select[name='causa_defeito_"+posicao+"']").val("");
            <?php } ?>

            <?php if($login_fabrica == 160 or $replica_einhell){?>
                $("div[name='peca_"+posicao+"']").removeClass("classeErroEstoque2");
                $("div[name='peca_indisponivel_"+posicao+"']").remove(); 
            <?php
            }
            if ($login_fabrica == 161) {
            ?>
                $("input[name='estoque_"+posicao+"']").val("");
            <?php
            }
            ?>
	        $("input[name='preco_"+posicao+"']").val("");
			<? if(in_array($login_fabrica, array(147,149,153,156,157,165)) || ($usa_calculo_ipi) || (regiao_suframa() == false && in_array($login_fabrica, array(161)))) { ?>
				$("input[name='ipi_"+posicao+"']").val("");
                $("input[name='sub_total_sem_ipi_"+posicao+"']").val("");
			<? } ?>
			$("div[name=peca_"+posicao+"]").find("span[rel=lupa_peca]").show();

			fnc_calcula_total(posicao);

			<?php if(in_array($login_fabrica, array(161,162))){?>
				calcula_desconto_fabrica();
			<?php }?>

			$(this).parents("div.row-fluid").css({ "background-color": "transparent" });
			$(this).hide();

			if (typeof pedido != "undefined" && pedido.length > 0) {
				setTimeout(function() {
					var total = $("#total_pecas").val();

					$.ajax({
						async: true,
						url: "cadastro_pedido.php",
						type: "POST",
						data: {
							ajax_atualiza_total: true,
							pedido: pedido,
							total: total
						}
					});
				}, 1);
			}

            <?php if ($login_fabrica == 183 AND in_array($login_tipo_posto_codigo, array("Rev", "Rep"))){ ?>
                let tem_peca = "false";
                    
                $("button[name^=remove_peca_]").each(function() {
                    let desc_linha = $(this).attr("name").split("_");
                    if ($("input[name=peca_id_"+desc_linha[2]+"]").val() != "") {
                        tem_peca = "true";
                    }
                });

                if (tem_peca == "false" && ($("#produto_descricao").val() == "" || $("#produto_descricao").val() == "undefined")){
                    $("#numero_nf").attr("readonly", false);
                    $("#id_posto_pedido").val("");
                    $("#nota_fiscal_posto_pedido").val("");
                    $("#linha_posto_pedido").val("");
                }
            <?php } ?>
		}
	});

	 $("select[name=marca]").change(function() {
		var marca = $(this).val();

		$("input[name=lupa_config]").each(function(){
			$(this).attr("marca",marca);
		});
	});

<?php if($login_fabrica == 168){ ?>
    $("#consulta_tabela").click(function(){

        var tipo_pedido = $("#tipo_pedido").val();

        var url = "peca_lupa_new.php?parametro=referencia&valor=% %&tipo-pedido=tipo_pedido&ipi=true&telapedido=true&preco=true&tipo-pedido=tipo_pedido&telapedido=true&todas=true";

        Shadowbox.open({
            content: url,
            player: "iframe",
            height: 600,
            width: 800
        });

    });

<?php } ?>



<? if ($login_fabrica != 143) { ?>
    $("#lista_basica").click(function() {
        var referencia = "";
        referencia = $("#produto_referencia").val();

        var descricao = "";
        descricao  = $("#produto_descricao").val();

        <? if ($areaAdmin === true) { ?>
            var tabela = "";
            <? if ($login_fabrica != 161) { ?>
            tabela = $("#tabela option:selected").val();
            if (typeof tabela === 'undefined' || tabela.length == 0) {
                alert("<?php echo traduz("selecione.uma.tabela.para.pesquisar.a.lista.basica");?>");
                return;
            }
        <? } ?>
            var posto_id = "";
            posto_id = $("#posto_id").val();


            if (posto_id.length == 0) {
                alert("<?php echo traduz("selecione.um.posto.para.pesquisar.a.lista.basica");?>");
                return;
            }

            <? if (in_array($login_fabrica,array(153,157,161))) { ?>
                var tipo_pedido = "";
                tipo_pedido = $("#tipo_pedido").val();

                if (tipo_pedido.length == 0) {
                    alert("<?php echo traduz("selecione.um.tipo.de.pedido.para.pesquisar.a.lista.basica");?>");
                    return;
                }
                var url = "lista_basica_lupa_new.php?pedido=t&tabela="+tabela+"&tipo_pedido="+tipo_pedido+"&produto_referencia="+unescape(encodeURIComponent(referencia))+"&produto_descricao="+unescape(encodeURIComponent(descricao))+"&posto_codigo="+$("#posto_codigo").val()+"&posto_nome="+$("#posto_nome").val()+"&posto="+posto_id+"&desconto="+$("#valor_desconto").val();

            <? } else { ?>
                var url = "lista_basica_lupa_new.php?pedido=t&tabela="+tabela+"&produto_referencia="+unescape(encodeURIComponent(referencia))+"&produto_descricao="+unescape(encodeURIComponent(descricao))+"&posto_codigo="+$("#posto_codigo").val()+"&posto_nome="+$("#posto_nome").val()+"&posto="+posto_id+"&desconto="+$("#valor_desconto").val();
            <? } ?>

        <? } else { ?>
                var tipo_pedido = "";
                tipo_pedido = $("#tipo_pedido").val();

                if (tipo_pedido.length == 0) {
                    alert("<?php echo traduz("selecione.um.tipo.de.pedido.para.pesquisar.a.lista.basica");?>");
                    return;
                }

                var url = "lista_basica_lupa_new.php?pedido=t&tipo_pedido="+tipo_pedido+"&vista_explodida=sim&produto_referencia="+unescape(encodeURIComponent(referencia))+"&produto_descricao="+unescape(encodeURIComponent(descricao))+"&desconto="+$("#valor_desconto").val();
            <?php
            }
            ?>

            if (referencia.length > 0 || descricao.length > 0) {
                Shadowbox.open({
                    content: url,
                    player: "iframe",
                    height: 600,
                    width: 800
                });
            } else {
                alert("<?php echo traduz("digite.um.produto.para.pesquisar.sua.lista.basica");?>");
            }
        });
    <?php
    }
    ?>

    $(document).on("click", "button[name^=alterar_peca_]", function() {
        var posicao = $(this).attr("posicao");
        var pedido_item = $("input[name^=pedido_item_"+posicao+"]").val();
        var pedido = $("input[name^=pedido]").val();
        var peca_referencia = $("input[name^=peca_referencia_"+posicao+"]").val();
        var posto_id = $("input[name^=posto_id]").val();
        var tabela = $("select[name=tabela] option:selected").val();
        var qtde = $("input[name^=qtde_"+posicao+"]").val();
        var valor_desconto = $("#valor_desconto").val();
        var url = 'altera_peca_lupa.php?pedido_item='+pedido_item+'&pedido='+pedido+'&peca_referencia='+peca_referencia+"&posto_id="+posto_id+"&tabela="+tabela+"&qtde="+qtde+"&valor_desconto="+valor_desconto;

        Shadowbox.open({
            content: url,
            player: "iframe",
            height: 460,
            width: 800
        });
    });

    $("button.remove_produto").click(function() {
        $("#produto_referencia, #produto_descricao").val("");

        <?php if ($login_fabrica == 183 AND in_array($login_tipo_posto_codigo, array("Rev", "Rep"))){ ?>
            let tem_peca = "false";
                
            $("button[name^=remove_peca_]").each(function() {
                let desc_linha = $(this).attr("name").split("_");
                if ($("input[name=peca_id_"+desc_linha[2]+"]").val() != "") {
                    tem_peca = "true";
                }
            });

            if (tem_peca == "false"){
                $("#numero_nf").attr("readonly", false);
                $("#id_posto_pedido").val("");
                $("#nota_fiscal_posto_pedido").val("");
                $("#linha_posto_pedido").val("");
            }
        <?php } ?>
    });

    $("#adicionar_linha").click(function() {
        adicionaLinha();
    });

    $("#upload_peca").change(function() {
        var fh = new FileReader();
        var type = this.files[0].type;

        //caso nao consiga pegar o tipo do arquivo, ele pega a extensao
        if (typeof type == "undefined" || type == "" || type.length == 0 || type == "application/vnd.ms-excel") {
            type = String(this.files[0].name.replace(/.+\./, '')).toLowerCase();
        }

        //verifica pelo tipo ou extensao
        if (type != "text/csv" && type != "csv") {
            alert("<?php echo traduz("formato.de.arquivo.invalido");?> ("+ type +")! <?php echo traduz("selecione.um.arquivo.csv");?>");
            document.getElementById('upload_peca').value = '';
            return false;
        }

        fh.addEventListener('load', function () {
            var recCount = this.result.trim().split(/\r?\n/).length;
            if (recCount > 500) {
                alert("<?php echo traduz("o.arquivo.selecionado.superou.o.limite.de.500.linhas");?>");
                document.getElementById('upload_peca').value = '';
            }
        });

        fh.readAsText(this.files[0]);
    });

    $("#submit_upload_peca").on("click", function() {
        var arquivo     = $("#upload_peca").val();
        var tipo_pedido = $("#tipo_pedido").val();

        <?php
        if ($login_fabrica == 146) {
        ?>
            var marca = $("#marca").val();

            if (marca.length == 0) {
                alert("<?php echo traduz("selecione.a.marca");?>");
                return false;
            } else {
                $("#upload_peca_marca").val(marca);
            }
        <?php
        }

        if ($areaAdmin === true) {
        ?>
            var tabela = $("#tabela").val();

            if (tabela.length == 0) {
                alert("<?php echo traduz("selecione.a.tabela.de.preco");?>");
                return false;
            } else {
                $("#upload_peca_tabela").val(tabela);
            }
        <? if ($login_fabrica == 157) { ?>
            if (tipo_pedido.length == 0) {
                alert("<?php echo traduz("selecione.o.tipo.de.pedido");?>");
                return false;
            } else {
                $("#upload_peca_tipo_pedido").val(tipo_pedido);
            }
        <? }
        } else { ?>
            if (tipo_pedido.length == 0) {
                alert("<?php echo traduz("selecione.o.tipo.de.pedido");?>");
                return false;
            } else {
                $("#upload_peca_tipo_pedido").val(tipo_pedido);
            }
        <?php } ?>

        if (arquivo.length == 0) {
            alert("<?php echo traduz("selecione.o.arquivo");?>");
            return false;
        }

        $("#form_upload_peca").submit();
    });

    $("#form_upload_peca").ajaxForm({
        beforeSend: function() {
            $("div.mensagem").removeClass("alert alert-error")
            $("#submit_upload_peca").button("loading");

            if ($("#divUploadCSV").find("div.alert-danger").length > 0) {
                $("#divUploadCSV").find("div.alert-danger").remove();
            }
            // console.log("Start AJAX at ", new Date);
        },
        complete: function(data) {
            data = JSON.parse(data.responseText);
            //console.log("Ending AJAX at ", new Date);

            if (data.erro) {
                $("div.mensagem").addClass("alert alert-error");
                $("div.mensagem").html("<h4>"+data.erro+"</h4>");
                // alert(data.erro);
            }

            if (data.pecas) {
                //console.log("Total Peças: ", data.pecas.length);
                $.each(data.pecas, function(key, peca) {

                    if (verifica_peca_lancada(peca.peca) == false) {
                        var div = $("div.row-fluid[name^=peca_]").find("input[rel=peca_id]").filter(function() { return this.value.length == 0; }).parents("div.row-fluid").last();

                        if (div.length == 0) {
                            div = adicionaLinha();
                        }

                        var i = $(div).find("input[name^=peca_id_]").attr("posicao");

                        $(div).find("input[name^=peca_id_]").val(peca.peca);
                        $(div).find("input[name^=peca_referencia_]").val(peca.referencia).attr({ readonly: "readonly" });
                        $(div).find("input[name^=peca_descricao_]").val(peca.descricao).attr({ readonly: "readonly" });
                        $(div).find("input[name^=qtde_]").val(peca.qtde);
                        $(div).find("input[name^=preco_]").val((parseFloat(peca.preco.replace('.','').replace(',','.')).toFixed(2)).replace('.',','));
                        $(div).find("input[name^=multiplo_]").val(peca.multiplo).attr({ readonly: "readonly" });
                        <?php
                        if ($login_fabrica == 161) {
                        ?>
                            $(div).find("input[name^=estoque_]").val(peca.estoque).attr({ readonly: "readonly" });
                        <?php
                        }
                        ?>

						<? if(in_array($login_fabrica, array(147,149,153,156,157,165)) || ($usa_calculo_ipi) || (regiao_suframa() == false && in_array($login_fabrica, array(161)))) { ?>
							$(div).find("input[name^=ipi_]").val(peca.ipi).attr({ readonly: "readonly" });
						<? } ?>

						$(div).find("span[rel=lupa_peca]").hide();
						$(div).find("div[name^=remove_peca_]").find("button[name^=remove_peca_]").show();

						fnc_calcula_total(i);
					} else {
						data.erros.push("Peça "+peca.referencia+" - "+peca.descricao+" já lançada");
					}
				});

				if (data.erros.length > 0) {
					var divErro = $("<div></div>", {
						class: "alert alert-danger",
						css: { "text-align": "left" },
						html: "<button type='button' class='close' data-dismiss='alert'>&times;</button>"
					});

					$.each(data.erros, function(key, erro) {
						$(divErro).append("<strong>"+erro+"</strong><br />");
					});

					$("#divUploadCSV").append(divErro);
				}
			}

			$("#submit_upload_peca").button("reset");
			// console.log("End populating table at ", new Date);
		}
	});

});

<?php
if ($login_fabrica != 143) {
?>
    function retorna_produto(data) {
        $("#produto_referencia").val(data.referencia);
        $("#produto_descricao").val(data.descricao);
    }
<?php
}
?>

/**
 * Fun\E7\E3o de retorno da lupa de Peças
 */

function retorna_peca(retorno, notificacao) {

    if (typeof notificacao == 'undefined') {
        notificacao = false;
    }

    if (verifica_peca_lancada(retorno.peca) === false) {
        if(retorno.posicao == undefined){
            $("input[name^=peca_referencia_]").each(function(key, value){
                if(value.value == null || value.value == ""){
                    retorno.posicao = key;
                    return false;
                }
            });
            
            if (typeof retorno.posicao == 'undefined') {
                adicionaLinha();
                retorna_peca(retorno, notificacao);
                return false;
            }
        }

        $("input[name='peca_id_"+retorno.posicao+"']").val(retorno.peca);
        <?php if(in_array($login_fabrica, array(171))) { ?>
            $("input[name='peca_referencia_"+retorno.posicao+"']").val(retorno.referencia +" / "+ retorno.referencia_fabrica).attr({ readonly: "readonly" });
        <?php } else {?>
        $("input[name='peca_referencia_"+retorno.posicao+"']").val(retorno.referencia).attr({ readonly: "readonly" });
        <? }?>
        $("input[name='peca_descricao_"+retorno.posicao+"']").val(retorno.descricao).attr({ readonly: "readonly" });
        $("input[name='preco_"+retorno.posicao+"']").val(retorno.preco);
        $("input[name='multiplo_"+retorno.posicao+"']").val(retorno.multiplo).attr({ readonly: "readonly" });

        <?php if (in_array($login_fabrica, [175])) { ?>
            var tipoUnidade = ['c', 'm', 'mm', 'cm', 'dm', 'mt', 'dam', 'hm', 'km'];

            if ($.inArray(retorno.unidade, tipoUnidade) !== -1 ) {
                $("input[name='multiplo_"+retorno.posicao+"']").val(0).attr({ readonly: "readonly" });
                $("input[id^=qtde_"+retorno.posicao+"]").numeric({allow:". ,"});
                $("input[id^=unidade_"+retorno.posicao+"]").val(retorno.unidade);
            } else {
                if ($login_fabrica == 158) {
                    $("input[id^=qtde_"+retorno.posicao+"]").numeric({allow:". ,"});
                } else {
                    $("input[id^=qtde_"+retorno.posicao+"]").numeric();
                }
            }
        <?php } ?>

        
        <?php
        if ($catalogoPedido) {
        ?>
            if (retorno.qtde) {
                $("input[name='qtde_"+retorno.posicao+"']").val(retorno.qtde);
            }
        <?php
        }
        
        if ($login_fabrica == 161) {
        ?>
                $("input[name='estoque_"+retorno.posicao+"']").val(retorno.estoque).attr({ readonly: "readonly" });
        <?php
        }
        ?>
		<? if(in_array($login_fabrica, array(147,149,153,156,157,165)) || ($usa_calculo_ipi)  || (regiao_suframa() == false && in_array($login_fabrica, array(161)))) { ?>
			$("input[name='ipi_"+retorno.posicao+"']").val(retorno.ipi).attr({ readonly: "readonly" });
		<? } ?>

        <?php if($login_fabrica == 157){ ?>
            $("input[name='desconto_item_"+retorno.posicao+"']").val(desconto_posto).attr({ readonly: "readonly" });
        <?php } ?>

		$("div[name=peca_"+retorno.posicao+"]").find("span[rel=lupa_peca]").hide();
		$("div[name=remove_peca_"+retorno.posicao+"]").find("button[name=remove_peca_"+retorno.posicao+"]").show();

		$("select[name=marca]").each(function() {
			$(this).find("option").each(function() {
				if (!$(this).is(":selected")) {
					$(this).remove();
				}
			});
        });
        
        if (notificacao == true) {
            toastr.options.closeButton = true;
            toastr.options.positionClass = 'toast-top-left';
            toastr.options.progressBar = true;
            toastr.success("Peça adicionada ao carrinho");
            $("input[name='qtde_"+retorno.posicao+"']").trigger('blur');
        }
    } else {
		alert("Peça "+retorno.referencia+" - "+retorno.descricao+" já lançada!");
		return;
	}
}

function retorna_pecas(data) {
    if (data.length > 0) {
        var erro = [];

        $.each(data, function(key, peca) {
            if (verifica_peca_lancada(peca.peca) == false) {
                var div = $("div.row-fluid[name^=peca_]").find("input[rel=peca_id]").filter(function() { return this.value.length == 0; }).parents("div.row-fluid").last();

                if (div.length == 0) {
                    div = adicionaLinha();
                }
                $(div).find("input[name^=peca_id_]").val(peca.peca);
                <?php if(in_array($login_fabrica, array(171))) { ?>
                    $(div).find("input[name^=peca_referencia_]").val(peca.referencia +" / "+ peca.referencia_fabrica).attr({ readonly: "readonly" });
                <? } else { ?>
                    $(div).find("input[name^=peca_referencia_]").val(peca.referencia).attr({ readonly: "readonly" });
                <? }?>
                $(div).find("input[name^=peca_descricao_]").val(peca.descricao).attr({ readonly: "readonly" });
                <?php if ($login_fabrica == 147) {?>
                    $(div).find("input[name^=preco_]").val(number_format(peca.preco, 2, ",", "."));
                <?php } else { ?>
                    $(div).find("input[name^=preco_]").val(peca.preco);
               <?php  } ?>
                $(div).find("input[name^=multiplo_]").val(peca.multiplo).attr({ readonly: "readonly" });
                <?php
                if ($login_fabrica == 161) {
                ?>
                    $(div).find("input[name^=estoque_]").val(peca.estoque).attr({ readonly: "readonly" });
                <?php
                }
                ?>
				<? if(in_array($login_fabrica, array(147,149,153,156,157,165)) || ($usa_calculo_ipi) || (regiao_suframa() == false && in_array($login_fabrica, array(161)))) { ?>
					$(div).find("input[name^=ipi_]").val(peca.ipi).attr({ readonly: "readonly" });
				<? } ?>

                <?php if($login_fabrica == 157){ ?>
                    $(div).find("input[name^=desconto_item_]").val(desconto_posto).attr({ readonly: "readonly" });
                <?php } ?>

				$(div).find("span[rel=lupa_peca]").hide();
				$(div).find("div[name^=remove_peca_]").find("button[name^=remove_peca_]").show();
			 
                <?php if ($login_fabrica == 183 AND in_array($login_tipo_posto_codigo, array("Rev", "Rep"))){ ?>
                    $("#numero_nf").attr("readonly", true);
                <?php } ?>
            } else {
				erro.push(peca.descricao);
				erro_peca = true;
			}
		});

		if (erro.length > 0) {
			alert("<?php echo traduz("as.seguintes.pecas.ja.foram.lancadas.no.pedido");?>: "+erro.join(", "));
		}
	}
}

function adicionaLinha() {
    var novaLinha = $("div[name^=peca_]").last().clone();
    var posicao   = $("div[name^=peca_]").length;

    var newHtml = $(novaLinha)
        .html()
        .replace(/=(("|')[^0-9]+)_(\d+)\2/mg, "=$1_"+posicao+"$2");

    var so_leitura = [
        "input[name^=estoque_]",
        "input[name^=multiplo_]",
        "input[name^=preco_]",
        "input[name^=sub_total_]",
        "input[name^=ipi_]"
    ];
    var normal = [
        "input[name^=peca_referencia_]",
        "input[name^=peca_descricao_]",
        "input[name^=qtde_]"
    ];


    $(novaLinha).find("button[name^=remove_peca_]").attr({ name: "remove_peca_"+posicao, rel: posicao });
    $(novaLinha).find("input[name^=peca_id_]").attr({ name: "peca_id_"+posicao, posicao: posicao });


    <?php if($login_fabrica == 160 or $replica_einhell){?>
        $(novaLinha).removeClass( "classeErroEstoque2" );
    <?php } ?>


    $(novaLinha)
        .attr('name', 'peca_'+posicao)
        .html(newHtml);
    $(novaLinha).find("input[name=lupa_config]").attr({ posicao: posicao });

    $(novaLinha)
        .find("input, select, textarea").val("")
        .end()
        .find(so_leitura.join(',')).prop({ readonly: true })
        .end()
        .find(normal.join(',')).prop({ readonly: false })
        .end()
        .find("button[rel]").attr('rel', posicao)
        .end()
        .find("input[name=peca_id_"+posicao+"]")
            .attr('posicao', posicao)
            .hide()
        .end()
        .find("span[rel=lupa_peca]").css({ display: "inline-block" })
        .end();

    $("div.div_pecas").append(novaLinha);

    novaLinha = $("div[name^=peca_]").last();

    <?php if (!in_array($login_fabrica, [158, 175])) { ?> 
        $(novaLinha).find("input[name^=qtde_]").numeric();
    <?php } ?>

    $(novaLinha).find("input[name^=qtde_]").data("posicao",posicao);

    <?php if ($login_fabrica == 183){ ?>
        $(novaLinha).find("select[name^=causa_defeito_]").removeAttr('style');
    <?php } ?>
    
    $(novaLinha).find('button[name^=remove_peca_]').hide();

    $("#qtde_item").val(++posicao);

    return novaLinha;
}

function PesquisaLimiteDisponivel(codigoPosto){
    $('#alertaErro').hide();
    $.ajax({
        url: window.location,
        method: 'POST',
        data: {posto:codigoPosto, ajax:'sim'},
        timeout: 5000
    }).fail(function(){
        $('#alertaErro').show().find('h4').html('<?php echo traduz("erro.ao.tentar.consultar.o.limite.disponivel.tempo.esgotado");?>');
    }).done(function(data){
        data = JSON.parse(data);
        if (data.ok !== undefined) {
            $('#limite_disponivel').val(data.ok);
        }
    });
}

function VerificaTipoFretePosto(codigoPosto){
 
    // Verifica se existe um tipo de frete cadastrado pro posto, se sim,
    // habilita somente o tipo de frete cadastrado
    $.ajax({
        url: window.location,
        method: 'POST',
        dataType: 'json',
        data: {codigo_posto:codigoPosto, verifica_frete_posto:true},
        success : function(response){

    
                $("#tipo_frete").find('option').remove();

                if(response.tipo_frete){
                   var newOption = new Option(response.tipo_frete,response.tipo_frete);
                   $('#tipo_frete').append(newOption).trigger('change');
                }else{

                    // Padrão tipo CIF para a fábrica MONDIAL
                    var newOption = new Option("CIF","CIF");
                   $('#tipo_frete').append(newOption).trigger('change');
                }

      
        },
        error: function (request, status, error) {
            console.log(request.responseText);
        }
    })
}

<?php

if($areaAdmin === false){

    $sql_desconto = "SELECT desconto FROM tbl_posto_fabrica WHERE posto = {$login_posto} AND fabrica = {$login_fabrica}";
    $res_desconto = pg_query($con, $sql_desconto);

    if(pg_num_rows($res_desconto) > 0){
       $desconto_posto = str_replace(",", ".", pg_fetch_result($res_desconto, 0, "desconto"));
    }

}

?>

var desconto_posto = '<?php echo $desconto_posto; ?>';

function retorna_posto(retorno) {
    /**
        ** A fun??o define os campos c?digo e nome como readonly e esconde o bot?o
        ** O posto somente pode ser alterado quando clicar no bot?o trocar_posto
        ** O evento do bot?o trocar_posto remove o readonly dos campos e d? um show nas lupas
    **/
    <?php if (in_array($login_fabrica, array(151))) { ?>
    $('#limite_disponivel').val('0');
    PesquisaLimiteDisponivel(retorno.posto);

    <? if($areaAdmin === true){?>
        VerificaTipoFretePosto(retorno.posto);
    <? } ?>
  
    <?php } ?>
    $("#posto_id").val(retorno.posto);
    $("#posto_codigo").val(retorno.codigo).attr({ readonly: "readonly" });
    $("#posto_nome").val(retorno.nome).attr({ readonly: "readonly" });
    $("#div_trocar_posto").show();
    $("#div_informacoes_posto").find("span[rel=lupa]").hide();

    $("#posto_latitude").val(retorno.latitude);
    $("#posto_longitude").val(retorno.longitude);
    $("input[name=lupa_config][tipo=lista_basica]").attr({ posto: retorno.posto });

    $("#upload_posto_codigo").val(retorno.codigo);

    <?php if($login_fabrica == 161 && $areaAdmin == true){ ?>
        $("#suframa_status").val(retorno.suframa);

        opcao_ipi(retorno.suframa);

    <?php } ?>

    <?php if(in_array($login_fabrica, array(164)) && $areaAdmin == true){ ?>
        valor_frete_estado(retorno.posto);
    <?php } ?>

    <?php if(in_array($login_fabrica, array(183)) && $areaAdmin == true){ ?>
        carrega_linha(retorno.posto);
    <?php } ?>

    <?php if($login_fabrica == 157){ ?>
        desconto_posto = retorno.desconto;
    <?php } ?>

}

function carrega_linha(posto){

    $.ajax({
        url: "cadastro_pedido.php",
        type: "post",
        data: {
            ajax_carrega_linha: true,
            posto: posto
        },
        complete: function(data){

            $("#linha").html(data.responseText);

        }
    });

}

<?php
if(in_array($login_fabrica, array(164)) && $areaAdmin == true){
?>

    function valor_frete_estado(posto){

        $.ajax({
            url: "cadastro_pedido.php",
            type: "post",
            data: {
                busca_frente_estado: true,
                posto: posto
            },
            complete: function(data){

                $("#total_frete_estado").val(data.responseText);

            }
        });

    }

<?php
}
?>

<?php
if($login_fabrica == 161 && $areaAdmin == true){
?>

function opcao_ipi(suframa){

    if(suframa == "t"){

        $("#desc-total-pedido").text("Total:");
        $("#desc-total-sem-ipi").hide();
        $("#titulo-ipi").hide();

        $(".campo-ipi").each(function(){

            $(this).find("input").val("");
            $(this).hide();

        });

    }else{

        $("#desc-total-pedido").text("Total c/ IPI:");
        $("#desc-total-sem-ipi").show();
        $("#titulo-ipi").show();

        $(".campo-ipi").each(function(){

            $(this).find("input").val("");
            $(this).show();

        });

    }

}

<?php
}
?>

function retorna_transportadora(data) {
    $("#transportadora_nome").val(data.nome).attr({ readonly: "readonly" });
    $("#transportadora_cnpj").val(data.cnpj).attr({ readonly: "readonly" });
    $("#transportadora_id").val(data.transportadora);
    $("#div_trocar_transportadora").show();
    $("#div_informacoes_transportadora").find("span[rel=lupa]").hide();
}

/**
    * Fun??o que verifica se a Peça j\E1 foi lan\E7ada na OS
 */
function verifica_peca_lancada(peca) {
    var retorno = false;

    $("input[rel=peca_id]").each(function() {
        if ($(this).val() == peca) {
            retorno = true;
            return false;
        }
    });

    $("tr.peca_resumo").each(function() {
        if ($(this).data('peca') == peca) {
            retorno = true;
            return false;
        }
    });


    return retorno;
}

function enviar_frm(frm) {
    if (frm.btn_acao.value == '') {
        frm.btn_acao.value='gravar';

        $("#btn_enviar").val("<?php echo traduz("enviando.aguarde");?>").attr({disabled: 'disabled'});

        var rows = $("div[name^=peca_]"),
            JsonData = {};

        $(rows).each(function(idx, el) {

            var inputs = $(el).find('input'), item = {};

            $(inputs).each(function(i, inp) {
                item[inp.name] = inp.value;
                //inp.disabled   = 'disabled'; // n?o enviar este campo
            });
            JsonData[idx] = item;
        });

        $("#item_data").val(JSON.stringify(JsonData));

        frm.submit();
    } else {
        alert('<?php echo traduz("nao.clique.no.botao.voltar.do.navegador.utilize.somente.os.botoes.da.tela");?>');
    }
}

</script>

<?
$frase = traduz("cadastro.de.pedidos");

// PEGA O ID DO PRODUTO NO HD_CHAMADO.
// Mostra nos campos de produto.

if(in_array($login_fabrica,array(151,162, 183)) and (strlen(trim($hd_chamado))>0)){

    $sql_produto = "SELECT tbl_produto.referencia, tbl_produto.descricao
                    FROM tbl_produto
                    INNER JOIN tbl_hd_chamado_extra on tbl_hd_chamado_extra.produto = tbl_produto.produto
                    WHERE  tbl_produto.fabrica_i = $login_fabrica
                                        AND tbl_hd_chamado_extra.hd_chamado = $hd_chamado";
    $res_produto = pg_query($con, $sql_produto);

    if(pg_num_rows($res_produto) == 0 ){

        $sql_produto = "SELECT tbl_produto.referencia, tbl_produto.descricao
                    FROM tbl_produto
                    INNER JOIN tbl_hd_chamado_item on tbl_hd_chamado_item.produto = tbl_produto.produto
                    WHERE  tbl_produto.fabrica_i = $login_fabrica
                    AND tbl_hd_chamado_item.hd_chamado = $hd_chamado
                    LIMIT 1";
        $res_produto = pg_query($con, $sql_produto);

    }

    if(pg_num_rows($res_produto)> 0 ){
        $produto_referencia = pg_fetch_result($res_produto, 0, 'referencia');
        $produto_descricao  = pg_fetch_result($res_produto, 0, 'descricao');
    }
}

if (strlen($pedido) > 0 || strlen($cook_pedido) > 0) {
    $pedido = (strlen($cook_pedido)) ? $cook_pedido : $pedido;

    if ($areaAdmin === false) {
        $whereLoginPosto = "AND tbl_pedido.posto = {$login_posto}";
    }

    if ($login_fabrica == 146) {
        $column_marca = ", tbl_pedido.visita_obs AS marca";
    }

    $sql = "SELECT
                TO_CHAR(tbl_pedido.data, 'DD/MM/YYYY') AS data,
                tbl_pedido.tipo_frete,
                tbl_pedido.transportadora,
                tbl_transportadora.cnpj AS transportadora_cnpj,
                tbl_transportadora.nome AS transportadora_nome,
                tbl_pedido.pedido_cliente,
                tbl_pedido.tipo_pedido,
                tbl_pedido.linha,
                tbl_pedido.condicao,
                tbl_pedido.obs,
                tbl_pedido.exportado,
                tbl_pedido.permite_alteracao,
                tbl_posto.cnpj,
                tbl_posto.posto,
                tbl_posto.nome,
                tbl_posto_fabrica.codigo_posto,
                tbl_pedido.tabela,
                tbl_pedido.pedido_via_distribuidor,
                tbl_pedido.valor_frete,
                tbl_pedido.validade,
                tbl_pedido.entrega,
                tbl_pedido.promocao,
                tbl_pedido.status_pedido,
                tbl_status_pedido.descricao AS status_desc,
                tbl_pedido.desconto,
                tbl_pedido.seu_pedido,
                tbl_pedido.total,
                tbl_pedido.atende_pedido_faturado_parcial,
                tbl_hd_chamado_extra.hd_chamado,
                tbl_pedido.valores_adicionais,
                tbl_pedido.finalizado
                {$column_marca}
            FROM tbl_pedido
            INNER JOIN tbl_posto ON tbl_posto.posto = tbl_pedido.posto
            INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
            INNER JOIN tbl_status_pedido ON tbl_status_pedido.status_pedido = tbl_pedido.status_pedido
            LEFT JOIN tbl_transportadora ON tbl_transportadora.transportadora = tbl_pedido.transportadora
            LEFT JOIN tbl_transportadora_fabrica ON tbl_transportadora_fabrica.fabrica = $login_fabrica and tbl_transportadora.transportadora = tbl_transportadora_fabrica.transportadora
            LEFT JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.pedido = tbl_pedido.pedido
            WHERE tbl_pedido.pedido = $pedido
            {$whereLoginPosto}
            AND tbl_pedido.fabrica = $login_fabrica";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $_RESULT["data"]                    = pg_fetch_result($res, 0, 'data');
        $_RESULT["transportadora"]          = pg_fetch_result($res, 0, 'transportadora');
        $_RESULT["transportadora_cnpj"]     = pg_fetch_result($res, 0, 'transportadora_cnpj');
        $_RESULT["transportadora_nome"]     = pg_fetch_result($res, 0, 'transportadora_nome');
        $_RESULT["pedido_cliente"]          = pg_fetch_result($res, 0, 'pedido_cliente');
        $_RESULT["tipo_pedido"]             = pg_fetch_result($res, 0, 'tipo_pedido');
        $_RESULT["linha"]                   = pg_fetch_result($res, 0, 'linha');
        $_RESULT["condicao"]                = pg_fetch_result($res, 0, 'condicao');
        $_RESULT["exportado"]               = pg_fetch_result($res, 0, 'exportado');
        $_RESULT["permite_alteracao"]       = pg_fetch_result($res, 0, 'permite_alteracao');
        $_RESULT["observacao_pedido"]       = pg_fetch_result($res, 0, 'obs');

        $_RESULT["cnpj"]                    = pg_fetch_result($res, 0, 'cnpj');

        $_RESULT["tipo_frete"]              = pg_fetch_result($res, 0, 'tipo_frete');
        $_RESULT["aux_valor_frete"]         = pg_fetch_result($res, 0, 'valor_frete');
        $_RESULT["valor_frete"]             = number_format(pg_fetch_result($res, 0, 'valor_frete'),2,',','');
        $_RESULT["pedido_via_distribuidor"] = pg_fetch_result($res, 0, 'pedido_via_distribuidor');
        $_RESULT["validade"]                = pg_fetch_result($res, 0, 'validade');
        $_RESULT["entrega"]                 = pg_fetch_result($res, 0, 'entrega');
        $_RESULT["tabela"]                  = pg_fetch_result($res, 0, 'tabela');
        $_RESULT["promocao"]                = pg_fetch_result($res, 0, 'promocao');
        $_RESULT["desconto"]                = pg_fetch_result($res, 0, 'desconto');
        $_RESULT["status_pedido"]           = pg_fetch_result($res, 0, 'status_pedido');
        $_RESULT["status_pedido_desc"]      = pg_fetch_result($res, 0, 'status_desc');
        $_RESULT["parcial"]                 = pg_fetch_result($res, 0, 'atende_pedido_faturado_parcial');
        $_RESULT["marca"]                   = pg_fetch_result($res, 0, "marca");
        $_RESULT["hd_chamado"]              = pg_fetch_result($res, 0, "hd_chamado");
        $_RESULT["total_pecas"]             = pg_fetch_result($res, 0, "total");
        $_RESULT["finalizado"]              = pg_fetch_result($res, 0, "finalizado");

        //hd_chamado=2538216
        $_RESULT['posto_id']        = pg_fetch_result($res,0,'posto');
        $_RESULT['posto']['codigo'] = pg_fetch_result($res,0,'codigo_posto');
        $_RESULT['posto']['nome']   = pg_fetch_result($res,0,'nome');
        $valores_adicionais         = pg_fetch_result($res, 0, 'valores_adicionais');

        $valores_adicionais         = json_decode($valores_adicionais, true);

        $usa_desconto_fabricante = \Posvenda\Regras::get("usa_desconto_fabricante", "pedido_venda", $login_fabrica);

        if(count($msg_erro) == 0){

            if($usa_desconto_fabricante == true){
                $valor_desconto_fabricante = $valores_adicionais["valor_desconto_fabricante"];
            }

            $usa_adicional_fabricante = \Posvenda\Regras::get("usa_adicional_fabricante", "pedido_venda", $login_fabrica);

            if($usa_adicional_fabricante == true){
                $adicional_fabricante = $valores_adicionais["adicional_fabricante"];
            }

        }

        if ($login_fabrica == 183){
            if ($areaAdmin){
                $xid_posto = $_RESULT["posto_id"];
                $sql_tipo_posto = "
                    SELECT 
                        tbl_tipo_posto.codigo 
                    FROM tbl_posto_fabrica 
                    JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica}
                    WHERE tbl_posto_fabrica.posto = {$xid_posto}
                    AND tbl_posto_fabrica.fabrica = {$login_fabrica}";
                $res_tipo_posto = pg_query($con, $sql_tipo_posto);
                
                if (pg_num_rows($res_tipo_posto) > 0){
                    $login_tipo_posto_codigo = pg_fetch_result($res_tipo_posto, 0, "codigo");
                }
            }

            if (in_array($login_tipo_posto_codigo, array("Rev", "Rep"))){
                $seu_pedido                 = pg_fetch_result($res, 0, 'seu_pedido');
                $numero_nf                  = $valores_adicionais["nota_fiscal_posto_pedido"];
                $id_posto_pedido            = $valores_adicionais["id_posto_pedido"];
                $nota_fiscal_posto_pedido   = $valores_adicionais["nota_fiscal_posto_pedido"];
                $linha_posto_pedido         = $valores_adicionais["linha_posto_pedido"];
                $codigo_posto_pedido        = $valores_adicionais["codigo_posto_pedido"];

                if (strlen(trim($id_posto_pedido)) > 0){
                    $sql_posto_info = "
                        SELECT 
                            tbl_posto.nome,
                            tbl_posto_fabrica.codigo_posto
                        FROM tbl_posto 
                        JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                        WHERE tbl_posto.posto = {$id_posto_pedido} ";
                    $res_posto_info = pg_query($con, $sql_posto_info);

                    if (pg_num_rows($res_posto_info) > 0){
                        $codigo_posto_info = pg_fetch_result($res_posto_info, 0, "codigo_posto");
                        $nome_info_posto = pg_fetch_result($res_posto_info, 0, "nome");
                        $display_posto_info = "";
                    }
                }

            }
        }

        #$_RESULT["nome"]                    = pg_fetch_result($res, 0, 'nome');
        #$_RESULT["posto"]                   = pg_fetch_result($res, 0, 'posto');
        #$_RESULT["codigo"]                  = pg_fetch_result($res, 0, 'codigo_posto');
        // FIM hd_chamado=2538216
    }
}else{
    if(!empty($hd_chamado)){

        $_RESULT["pedido_cliente"]  = $hd_chamado;

        if(in_array($login_fabrica, array(151,183))) {
            $sql = "SELECT tbl_posto_fabrica.codigo_posto,tbl_posto.nome,tbl_posto.posto
                FROM tbl_posto
                JOIN tbl_posto_fabrica USING(posto)
                JOIN tbl_tipo_posto USING(tipo_posto)
                WHERE tbl_tipo_posto.posto_interno IS TRUE
                AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                AND tbl_tipo_posto.fabrica = {$login_fabrica}
                LIMIT 1";
        }else{
            $sql = "SELECT tbl_posto_fabrica.codigo_posto,tbl_posto.nome,tbl_posto.posto
                FROM tbl_posto
                JOIN tbl_posto_fabrica USING(posto)
                JOIN tbl_hd_chamado_extra USING(posto)
                WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
                AND tbl_hd_chamado_extra.hd_chamado = $hd_chamado
                LIMIT 1";
        }
        $res = pg_query($con,$sql);
        $_RESULT['posto_id'] = pg_fetch_result($res,0,'posto');
        $_RESULT['posto']['codigo'] = pg_fetch_result($res,0,'codigo_posto');
        $_RESULT['posto']['nome'] = pg_fetch_result($res,0,'nome');
        $posto_esconde_lupa = "style='display: none;'";
        $posto_readonly     = "readonly='readonly'";
    }
}

if($login_fabrica == 157){
?>

<br>
<div class='container'>

    <div class="alert alert-warning">
        <b><?php echo traduz("atencao");?>:</b>
        <? if($login_fabrica == 143) { ?>
            <?php echo traduz("aguarde.os.calculos.finais.de.seu.pedido.pois.devido.o.icms.substituicao.em.alguns.itens.e.necessario.revisarmos.os.valores.conforme.convenios.e.protocolos.especi?ficos.de.cada.estado");?>
        <? } elseif($login_fabrica == 160 or $replica_einhell) { ?>
            <?php echo traduz("condicoes.de.pagamento");?>:<br>
            <ul style='width:600px; right: 300;font-size:11px' >
            <li align='left'><?php echo traduz("parcela.minima.de.150.reais");?></li>
            <li align='left'><?php echo traduz("para.pedidos.acima.de.100.reais.habilita.se.condicoes.com.desconto.de.3");?></li>
            <li align='left'><?php echo traduz("para.pedidos.acima.de.500.reais.habilita.se.mais.3.de.desconto.na.condicao.antecipado");?></li>
            <li align='left'><?php echo traduz("basta.selecionar.as.condicoes.conforme.o.pedido.colocado");?></li>
            <li align='left'><?php echo traduz("frete.28.reais.para.toda.entrega.pedidos.acima.de.165.reais.cliente.ganha.isencao");?></li>
            </ul
         ?>
         <? } elseif($login_fabrica == 156) { ?>
            <?php echo traduz("todo.pedido.de.pecas.possui.um.valor.de.40.referente.ao.frete");?>
        <? } else { ?>
            <?php echo traduz("pedidos.a.prazo.dependerao.de.analise.do.departamento.de.credito.");?>
        <? } ?>

    </div>

    <div class="alert alert-info pedidowapnormal" style="display:none;">
        <b><?php echo traduz("pedido.wap");?>:</b>
        <p><?php echo traduz("pedido.minimo.de.250");?></p>
        <p><?php echo traduz("pedidos.entre.250.e449.99.faturamento.28.dias.sem.juros.e.frete.pago.pelo.cliente");?>;</p>
        <p><?php echo traduz("pedidos.entre.450.e.799.99.faturamento.28.e.56.dias.sem.juros.e.frete.pago.pelo.cliente");?>;</p>
        <p><?php echo traduz("pedidos.acima.de.800.faturamento.28.56.84.dias.sem.juros.e.frete.pago.pela.fresnomaq");?>.</p>

    </div>
    <div class="alert alert-info pedidowapventilacao" style="display:none;">
        <b><?php echo traduz("ventilacao");?>:</b>
        <p><?php echo traduz("pedido.minimo.de.100");?></p>
        <p><?php echo traduz("pedidos.entre.100.e.249.99.faturamento.28.dias.sem.juros.e.frete.pago.pelo.cliente");?>;</p>
        <p><?php echo traduz("pedidos.entre.250.e.499.99.faturamento.28.e.56.dias.sem.juros.e.frete.pago.pelo.cliente");?>;</p>
        <p><?php echo traduz("pedidos.acima.de.500.faturamento.28.56.84.dias.sem.juros.e.frete.pago.pela.fresnomaq");?>.</p>

    </div>

<?
}

if (in_array($login_fabrica, [144])) { ?>
    <div class="alert alert-info">
        <?= traduz("pedidos.a.prazo.dependerao.de.analise.do.departamento.de.credito.") ?>
    </div>
<?php
}

if($telecontrol_distrib) {
?>
	<br>
	<div class='container'>

    <div class="alert alert-warning">
		<b>Atenção:</b> ao escolher atendimento parcial dos pedidos, o pedido será faturado de acordo com a disponibilidade das peças em estoque, o que acarretará a cobrança de vários fretes.
Para evitar que isso ocorra, escolha o atendimento total do pedido, onde este só será faturado mediante a disponibilidade de todas as peças que compõem o pedido.
 <br>
Em caso de dúvidas, entrar em contato pelo 0800-718-7825
</div>	
</div>
<? } ?>

<?php
if($login_fabrica == 175) {
?>
    <br>
    <div class='container'>

    <div class="alert alert-info">
        <b>Atenção:</b> Impostos ainda não contabilizados
</div>  
</div>
<? } ?>
<?php if (in_array($login_fabrica, array(184,200))) {?>
<br>
<div class='container'>
    <div class="alert alert-warning">
        <p><b><?php echo traduz("Atenção");?>: </b><?php echo traduz("Aqui vai a mensagem da lepono sobre impostos nao calculados, ele v\E3o mandar o modelo da mensagem");?> </p>
    </div>  
</div>
<?php } ?>

<br>
<?php if (in_array($login_fabrica, [190,191])) {?>
    <br>
    <div class='container'>
        <div class="alert alert-error">
            <p><b><?php echo traduz("Atenção");?>: </b><?php echo traduz("O valor do pedido poderá ser alterado devido aos impostos calculados pela Fábrica");?> </p>
        </div>  
    </div>
<?php } ?>

    <div class="alert alert-danger" id='alertaErro' style="display: none;"><h4></h4></div>
    <div class="row" >
        <b class="obrigatorio pull-right">  *  <?php echo traduz("campos.obrigatorios");?></b>
    </div>

<form name="frm_pedido" <?php if($bloqueado_duplicatas == true){ echo " style='display: none;' ";  } ?> method="POST" class="form-search form-inline" enctype="multipart/form-data" >
    <input class="frm" type="hidden" name="pedido" value="<? if(!empty($pedido)) echo $pedido;else echo $cook_pedido; ?>">
    <input class="frm" type="hidden" name="voltagem" value="<? echo $voltagem; ?>">
    <input class="frm" type="hidden" name="produto_referencia" value="<? echo $produto_referencia; ?>">
    <input class="frm" type="hidden" name="produto_descricao" value="<? echo $produto_descricao; ?>">
    <input class="frm" type="hidden" name="hd_chamado" value="<? echo $hd_chamado; ?>">
    <input type='hidden' name='btn_acao' />

    <?php
    if (count($msg_erro["msg"]) > 0 && strlen(getValue("posto_id")) > 0 && empty($pedido)) {
        $posto_readonly     = "readonly='readonly'";
        $posto_esconde_lupa = "style='display: none;'";
        $posto_mostra_troca = "style='display: block;'";
    }

    if (count($msg_erro["msg"]) > 0 && strlen(getValue("transportadora")) > 0 && empty($pedido)) {
        $transportadora_readonly     = "readonly='readonly'";
        $transportadora_esconde_lupa = "style='display: none;'";
        $transportadora_mostra_troca = "style='display: block;'";
    }

    if (strlen($pedido) > 0 && strlen(getValue("posto_id")) > 0) {
        $posto_readonly     = "readonly='readonly'";
        $posto_esconde_lupa = "style='display: none;'";
    }

    if (strlen($pedido) > 0 && strlen(getValue("transportadora[id]")) > 0) {
        $transportadora_readonly     = "readonly='readonly'";
        $transportadora_esconde_lupa = "style='display: none;'";
    }

    if (!empty($hd_chamado)) {
                 $posto_mostra_troca = "style='display: none;'";
        }

    if ($login_fabrica == 147 && $_RESULT['status_pedido'] == 18 && !empty($_RESULT["finalizado"])) {
        $ocultaItens  = "style='display: none;'";
        $desabilitaItens  = "readonly";
    }

    ?>
    
    <?php if ($login_fabrica == 183 AND !in_array($login_tipo_posto_codigo, array("Rev", "Rep"))){ ?>
        <div class="row-fluid" style="margin-bottom: 10px;">
            <div class="env_saldo_usado span3" style="float: right;">
                <i class="icon-minus icon-white" style="float: left; margin-top: 18px; margin-left: 15px;"></i>
                <label style="float: right; color: white; font-style: italic; font-size: 2em; font-weight: bold; margin-right: 10px; margin-top: 10px;">R$ <?=number_format($debito_encontro_contas, 2, ",", ".")?></label>
                <label style="float: right; color: white; font-style: italic; font-weight: bold; margin-right: 10px; margin-top: 6px;">Saldo utilizado</label>
            </div>
            <div class="span1" style="float: right;"></div>
            <div class="env_disponivel span3" style="float: right;">
                <i class="icon-plus icon-white" style="float: left; margin-top: 18px; margin-left: 15px;"></i>
                <label style="float: right; color: white; font-style: italic; font-size: 2em; font-weight: bold; margin-right: 10px; margin-top: 10px;">R$ <?=number_format($saldo_encontro_contas_disponivel, 2, ",", ".")?></label>
                <label style="float: right; color: white; font-style: italic; font-weight: bold; margin-right: 10px; margin-top: 6px;">Saldo dispon\EDvel</label>
            </div>
        </div>
        
        <div class="tc_formulario" >
            <div class="titulo_tabela" ><?php echo traduz("Selecione uma Linha");?></div>
            <div class="row-fluid">
                <div class="span3"></div>
                <div class='control-group  <?=(in_array('linha', $msg_erro['campos'])) ? "error" : "" ?>' >
                    <div class="span6" style='text-align:center'><br>
                            <h5 class="asteristico">*</h5>
                            <select class="span12" name="linha" id="linha">
                            <?php 
                                if (strlen($login_posto) > 0) {
                                    $xposto = $login_posto;
                                } else {
                                    $xposto = getValue('posto_id');
                                }
                                $sqlT = "SELECT DISTINCT tbl_linha.linha, tbl_linha.nome
                                         FROM tbl_posto_linha
                                         JOIN tbl_linha USING(linha)
                                         WHERE tbl_posto_linha.posto = $xposto
                                         AND tbl_linha.fabrica = $login_fabrica
                                         AND tbl_linha.codigo_linha = 'S3' ";
                                $resT = pg_query($con, $sqlT);

                                if (pg_num_rows($resT) > 0) {
                                    foreach (pg_fetch_all($resT) as $key => $row) {
                                        $selected = (getValue("linha") == $row["linha"]) ? "selected" : "";
                                        echo '<option '.$selected.' value="'.$row["linha"].'">'.$row["nome"].'</option>';
                                    }

                                }

                            ?>
                        </select><br><br>
                    </div>
                </div>
            </div>
        </div>
        <br>
        <?php } ?>

    <div class="tc_formulario" id="div_pedido" >
        <div class="titulo_tabela"><? echo $frase; ?></div>

        <br/>
        <?php
        if (in_array($login_fabrica, array(151))) {
        ?>
        <div class="row-fluid"  >
            <div class="span1"></div>
            <div class="span3">
                <label class='control-label' for='tipo_pedido'><?php echo traduz("limite.disponivel");?></label>
                <div class="controls controls-row">
                    <div class="span12">
                        <input class='span12' type="text" name="limite_disponivel" id='limite_disponivel' maxlength="20" value="<?=$limiteDisponivel?>" readonly>
                    </div>
                </div>
            </div>
        </div>
        <?php
        }
        if ($areaAdmin === true) {

            if($login_fabrica == 161){
        ?>
                <input type="hidden" name="suframa_status" id="suframa_status" value="" />
                <?php
            }

        ?>
            <input type="hidden" id="posto_id" name="posto_id" value="<?=getValue('posto_id')?>" />

            <div id="div_informacoes_posto" class="row-fluid">
                <div class="span1"></div>

                <div class="span3">
                    <div class='control-group <?=(in_array('posto_id', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="posto_codigo"><?php echo traduz("posto.codigo");?></label>
                        <div class="controls controls-row">
                            <div class="span12 input-append">
                                <h5 class="asteristico">*</h5>

                                <input id="posto_codigo" name="posto[codigo]" class="span10" type="text" value="<?=getValue('posto[codigo]')?>" <?=$posto_readonly?> />
                                <span class="add-on" rel="lupa" <?=$posto_esconde_lupa?> >
                                    <i class="icon-search"></i>
                                </span>
                                <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="span5">
                    <div class='control-group <?=(in_array('posto_id', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="posto_nome"><?php echo traduz("posto.nome");?></label>
                        <div class="controls controls-row">
                            <div class="span12 input-append">
                                <h5 class="asteristico">*</h5>
                                <input id="posto_nome" name="posto[nome]" class="span12" type="text" value="<?=getValue('posto[nome]')?>" <?=$posto_readonly?> />
                                <span class="add-on" rel="lupa" <?=$posto_esconde_lupa?> >
                                    <i class="icon-search"></i>
                                </span>
                                <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="span1"></div>
            </div>

            <div id="div_trocar_posto" class="row-fluid" <?=$posto_mostra_troca?> >
                <div class="span1"></div>
                <div class="span10">
                    <button type="button" id="trocar_posto" class="btn btn-danger" ><?php echo traduz("alterar.posto.autorizado");?></button>
                </div>
            </div>
        <?php
        }else{
            if($login_fabrica == 35){
        ?>
            <div class="row-fluid" >
                <div class="span1"></div>
                <div class="span10">
                    <span class='msg_porcetagem_cadence'><?php echo traduz("pedido.sujeito.a.analise.do.departamento.de.credito.As.pecas.de.produtos.nacionais.estao.sujeitas.ao.acrescimo.de.ipi.com.percentuais.que.podem.variar.de.10.a.20");?> </span>
                </div>
                <div class="span1"></div>
            </div>
        <?php }else if(in_array($login_fabrica,array(186))){
        ?>
            <div class="row-fluid" >
                <div class="span1"></div>
                <div class="span10">
                    <span class='msg_porcetagem_cadence'><?php echo traduz("O valor do pedido poder? ser alterado devido aos impostos calculados pela F?brica");?> </span>
                </div>
                <div class="span1"></div>
            </div>
        <?php
        } } ?>

          <?php if(in_array($login_fabrica, [169,170])){ ?>
            <div class="row-fluid" >
                <div class="span2"></div>
                <div class="span8" style="color:red; font-weight: bold;">O valor do pedido poder\E1 ser alterado devido aos impostos calculados pela F\E1brica</div> 
                <div class="span2"></div>
            </div>

        <?php }?>

        <div class="row-fluid" >
            <div class="span1"></div>
            <div class="span3">
                <div class='control-group <?=(in_array('tipo_pedido', $msg_erro['campos'])) ? "error" : "" ?>' >
                    <label class='control-label' for='tipo_pedido'><?php echo traduz("tipo.de.pedido");?></label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <h5 class="asteristico">*</h5>
                            <select class='span12' name='tipo_pedido' id='tipo_pedido'>
                                <? if ($login_pede_peca_garantia == 'f' AND $login_fabrica <> 183 ) {
                                    $whereTipoPedidoGarantia = "AND tbl_tipo_pedido.pedido_em_garantia is not true ";
                                }
                                if ($login_fabrica == 147 && $areaAdmin === false) {
                					$sql = "
                						SELECT
                							tbl_tipo_posto.descricao
                						FROM tbl_tipo_posto
                						JOIN tbl_posto_fabrica ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_posto_fabrica.fabrica = tbl_tipo_posto.fabrica
                						WHERE tbl_tipo_posto.fabrica = {$login_fabrica}
                						AND tbl_posto_fabrica.posto = {$login_posto};
                					";
                                    $res = pg_query($con,$sql);
                                    $tipo_posto = pg_fetch_result($res,0,"descricao");
                                    if(strtolower($tipo_posto) != 'locadora'){
                                        $whereTipoPedidoLocadora = "AND UPPER(tbl_tipo_pedido.descricao) <> 'LOCADORA' ";
                                    }
                                }

                                if (!empty($hd_chamado) && $login_fabrica == 151 && $areaAdmin === true) {
                                    $pedido_faturado = "f";
                                    $whereTipoPedidoGarantia = "AND ((UPPER(tbl_tipo_pedido.descricao) <> 'GARANTIA' AND tbl_tipo_pedido.garantia_antecipada IS NOT TRUE ) or tbl_tipo_pedido.tipo_pedido = 329) ";
                                }else if($login_fabrica == 151 && $areaAdmin === true){
                                    $whereTipoPedidoGarantia = "AND UPPER(tbl_tipo_pedido.descricao) <> 'GARANTIA' ";
                                }else if($login_fabrica == 164 && $areaAdmin === false){
                                    $whereTipoPedidoGarantia = "AND UPPER(tbl_tipo_pedido.descricao) <> 'GARANTIA' ";
                                }

                                if (in_array($login_fabrica, array(184,190,200))) {
                                    $whereTipoPedidoGarantia = "AND UPPER(tbl_tipo_pedido.descricao) <> 'GARANTIA' ";

                                    if (in_array($login_fabrica, array(184,200)) AND $areaAdmin === false){
                                        $whereTipoPedidoBonificado = "AND UPPER(tbl_tipo_pedido.codigo) <> 'BON' ";
                                    }
                                }

                                if($pedido_faturado == 'f'){
                                    $whereTipoPedidoFaturado = "AND tbl_tipo_pedido.pedido_faturado IS NOT TRUE ";
                                }
                                
                                if (in_array($login_fabrica, array(175))) {
                                    $whereTipoPedidoFaturado = "AND tbl_tipo_pedido.pedido_faturado IS TRUE";
                                }

                                if (in_array($login_fabrica, array(151,165,171)) && $areaAdmin === false) {
                                    $whereTipoPedidoBonificado = "AND tbl_tipo_pedido.garantia_antecipada IS NOT TRUE ";
                                }

                                if (in_array($login_fabrica, array(149,167,203)) && $areaAdmin === false) {
                                    $whereTipoPedidoBonificado = "AND UPPER(fn_retira_especiais(tbl_tipo_pedido.descricao)) <> 'BONIFICACAO' ";
                                }

                                if ($login_fabrica == 162 && (empty($hd_chamado) || $areaAdmin === false)) {
                                    $whereTipoPedidoBonificado = "AND UPPER(fn_retira_especiais(tbl_tipo_pedido.descricao)) <> 'BONIFICACAO' ";
                                }

                                if ( in_array($login_fabrica, array(35,163,173)) AND $areaAdmin === false) {
                                    $whereTipoPedidoBonificado = "AND UPPER(fn_retira_especiais(tbl_tipo_pedido.descricao)) <> 'BONIFICACAO' AND UPPER(fn_retira_especiais(tbl_tipo_pedido.descricao)) <> 'GARANTIA' ";
                				}

                                if ($login_fabrica == 183){ 
                                    if (in_array($login_tipo_posto_codigo, array("Rev", "Rep"))){
                                        //$whereTipoPedidoBonificado = "AND UPPER(fn_retira_especiais(tbl_tipo_pedido.descricao)) = 'GARANTIA' ";
                                        $whereTipoPedidoGarantia = "AND tbl_tipo_pedido.pedido_em_garantia IS TRUE AND tbl_tipo_pedido.pedido_faturado is not true ";
                                    }else if ($login_tipo_posto_codigo == "Aut"){
                                        $whereTipoPedidoGarantia = "AND tbl_tipo_pedido.pedido_faturado IS TRUE AND tbl_tipo_pedido.pedido_em_garantia is not true ";
                                        //$whereTipoPedidoBonificado = "AND UPPER(fn_retira_especiais(tbl_tipo_pedido.descricao)) <> 'GARANTIA' ";
                                    }
                                }

                				if ($areaAdmin === false) {
                					$whereVisivel = "AND visivel IS TRUE";
                				}

                				$sql = "
                					SELECT *
                                                	FROM tbl_tipo_pedido
                                                	WHERE fabrica = {$login_fabrica}
                					AND ativo IS TRUE
                					{$whereVisivel}    
                					{$whereTipoPedidoGarantia}
                					{$whereTipoPedidoFaturado}
                					{$whereTipoPedidoBonificado}
                					{$whereTipoPedidoLocadora}
                					ORDER BY descricao;
                				";
                                if ($login_fabrica == 151 && isset($_GET['callcenter'])) {
                                    
                                    $sql = "SELECT tipo_pedido, descricao
                                            FROM tbl_tipo_pedido
                                             WHERE tipo_pedido in (318,329)";
                                  
                                } 

                                $res = pg_query($con, $sql);
                                
                                if (pg_num_rows($res) > 1) {
                                ?>
                                    <option value=""><?php echo traduz("selecione");?></option>
                                <?php
                                }

                                while ($tipo_pedido = pg_fetch_object($res)) {
                                    if($login_fabrica == 151 AND $areaAdmin === false) { /*HD-3836140 - 20/10/2017*/
                                            if($login_pede_peca_garantia == 'f') {
                                                if($tipo_pedido->tipo_pedido == '329') {
                                                    continue;
                                                }else {
                                                    $selected = ($tipo_pedido->tipo_pedido == getValue("tipo_pedido")) ? "selected" : ""; ?>
                                                    <option value='<?= $tipo_pedido->tipo_pedido; ?>' <?= $selected; ?>><?= $tipo_pedido->descricao; ?></option>
                                                <? }
                                            }else {
                                                $selected = ($tipo_pedido->tipo_pedido == getValue("tipo_pedido")) ? "selected" : ""; ?>
                                                <option value='<?= $tipo_pedido->tipo_pedido; ?>' <?= $selected; ?>><?= $tipo_pedido->descricao; ?></option>
                                            <? }
                                    } else {
                                        if (in_array($login_fabrica, array(167,174,176,203)) && strtoupper($tipo_pedido->descricao) == 'VENDA' && empty(getValue("tipo_pedido")) && $areaAdmin != true) {
                                            $selected = "selected";
                                        } else {
                                            $selected = ($tipo_pedido->tipo_pedido == getValue("tipo_pedido")) ? "selected" : "";
                                        }

                                        ?>
                                        <option value='<?= $tipo_pedido->tipo_pedido; ?>' <?= $selected; ?>><?= $tipo_pedido->descricao; ?></option>
                                    <? }
                                 } ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <?php $input_condicao = (in_array($login_fabrica, array(157)) && $areaAdmin === false) ? true : false; ?>
            <div class="span3">
                <div class='control-group <?=(in_array('condicao', $msg_erro['campos'])) ? "error" : "" ?>' >
                    <label class='control-label' for='condicao'><?php echo traduz("condicao");?></label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <h5 class="asteristico">*</h5>

                            <?php if($input_condicao === true){ ?>

                            <input type="text" name="condicao_desc" id="condicao_desc" value="" class="span12" readonly="readonly">
                            <input type="hidden" name="condicao" id="condicao_codigo" value="" class="span12">

                            <?php }else{ ?>
                            <select id="condicao" name="condicao" class="span12" <?php echo $readonly; ?>>
                                <option value=""><?php echo traduz("selecione");?></option>
                                <?php

                                if ($areaAdmin === false) {
                                    if ($login_pede_peca_garantia == "t" && $areaAdmin === false) {
                                        $whereCondicao = "AND (tbl_condicao.visivel IS TRUE OR LOWER(tbl_condicao.descricao) ~* 'garantia')";
                                    } else {

                                        if ($login_fabrica == 171) {
                                            $whereCondicao = " AND tbl_condicao.visivel IS TRUE ";
                                        } else {
                                            $whereCondicao = "AND tbl_condicao.visivel IS TRUE AND tbl_condicao.descricao !~* 'garantia' AND tbl_condicao.descricao !~* 'livre' ";
                                        }
                                    }
                                    if($login_fabrica == 157) {
                                        $condJoin = " JOIN tbl_posto_linha ON tbl_posto_linha.tabela_posto = tbl_condicao.tabela and tbl_posto_linha.posto = $login_posto
                                            join tbl_linha on tbl_posto_linha.linha = tbl_linha.linha and tbl_linha.fabrica = $login_fabrica";
                                    }
                                }

                                if($login_fabrica == 151 AND $areaAdmin === true){
                                    $whereCondicao = "AND tbl_condicao.visivel IS TRUE";
                                }

                                if($login_fabrica == 168 AND $desbloqueio == 'f'){
                                    $whereCondicao = " AND tbl_condicao.condicao = 3567";
                                }

                                if ($login_fabrica == 168) {
                                    $whereCondicao .= " AND tbl_condicao.visivel IS TRUE";
                                }

                                if ($login_fabrica == 203) {
                                    $condJoin       = "JOIN tbl_tabela ON tbl_condicao.tabela = tbl_tabela.tabela AND tbl_tabela.fabrica = {$login_fabrica} ";
                                    $whereCondicao  = " AND tbl_condicao.visivel IS TRUE ";
                                    $whereCondicao .= " AND (tbl_tabela.sigla_tabela NOT IN ('GAR','FARCOMP'))";
                                    $whereCondicao .= " AND tbl_condicao.descricao !~* 'garantia' ";
                                }

                                if ($login_fabrica == 183){
                                    if (in_array($login_tipo_posto_codigo, array("Rev", "Rep"))){
                                        $whereCondicao = "AND tbl_condicao.descricao ~* 'garantia'";
                                    }else{
                                        if ($tem_saldo_encontro_contas == false){
                                            $whereCondicao .= "AND tbl_condicao.visivel_acessorio IS NOT TRUE";
                                        }
                                    }
                                }

                                $sql = "SELECT distinct tbl_condicao.condicao, tbl_condicao.descricao FROM tbl_condicao $condJoin WHERE tbl_condicao.fabrica = {$login_fabrica} {$whereCondicao} ORDER BY tbl_condicao.descricao ASC";
;                                if ($login_fabrica == 151 && isset($_GET['callcenter'])) {
                                    $sql = "SELECT condicao, descricao 
                                            FROM tbl_condicao 
                                            WHERE condicao = 3423;";
                                }
                                $res = pg_query($con, $sql);

                                if (pg_num_rows($res) > 0) {
                                    while ($resCondicao = pg_fetch_object($res)) {
                                        $selected = (getValue("condicao") == $resCondicao->condicao) ? "selected" : "";
                                        
                                        if ($login_fabrica == 151 && isset($_GET['callcenter'])) {
                                            $selected = "selected";
                                        }

                                        if ($login_fabrica == 183 AND in_array($login_tipo_posto_codigo, array("Rev", "Rep"))){
                                            $selected = "selected";
                                        }
                                        
                                        echo "<option value='{$resCondicao->condicao}' {$selected}>{$resCondicao->descricao}</option>";
                                    }
                                }
                                ?>
                             </select>
                             <?php } ?>
                             <input type="hidden" name="condicao_hidden" id="condicao_hidden" />
                        </div>
                    </div>
                </div>
            </div>
            <?
            if ($login_fabrica == 168) {
            ?>
            <div class="span1">
                <div class='control-group'>
                    <label class='control-label' for='Parcelas'> <?php echo traduz("parcelas");?></label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <input class='span12' readonly type="text" name="nmr_parcelas" id='nmr_parcelas' maxlength="3">
                        </div>
                    </div>
                </div>
            </div>
            <?
            }
            ?>
            <?php if ($login_fabrica <> 183){ ?>
                <div class="span3">
                    <div class='control-group <?=(in_array('pedido_cliente', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class='control-label' for='pedido_cliente'><?php echo traduz("pedido.do.cliente");?></label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <?php if (!in_array($login_fabrica, array(35,139,144,147,150,151,153,157,160,156,162,163,164,167,168,175,184,191,200,203)) and !$replica_einhell) { ?>
                                <h5 class="asteristico">*</h5>
                                <?php } ?>
                                <input class='span12' type="text" name="pedido_cliente" id='pedido_cliente' maxlength="20" value="<?=getValue('pedido_cliente')?>">
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <?php if ($login_fabrica == 183 AND in_array($login_tipo_posto_codigo, array("Rev", "Rep"))){ ?>
                <div class="span2">
                    <div class='control-group <?=(in_array('pedido_representante', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class='control-label' for='seu_pedido'><?php echo traduz("Pedido Rep.");?></label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <input class='span12' type="text" name="seu_pedido" id='seu_pedido' maxlength="20" value="<?=$seu_pedido?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="span2">
                    <div class='control-group <?= (in_array('tipo_frete', $msg_erro['campos'])) ? "error" : ""; ?>' >
                        <label class='control-label' for='tipo_frete'><?php echo traduz("tipo.de.frete");?></label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <h5 class="asteristico">*</h5>
                                <select id="tipo_frete" name="tipo_frete" class="span12">
                                    <option value="" ><?php echo traduz("selecione");?></option>
                                    <option value="FOB" <?=(getValue("tipo_frete") == "FOB") ? "selected" : ""?> ><?php echo traduz("fob");?></option>
                                    <option value="CIF" <?=(getValue("tipo_frete") == "CIF" OR (empty(getValue("tipo_frete")))) ? "selected" : ""?> ><?php echo traduz("cif");?></option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
            <div class="span1"></div>
        </div>

        <?php if(!in_array($login_fabrica, array(161,164,183))){ ?>

        <? //if ($areaAdmin == true) { ?>
            <div class="row-fluid">
        <? //} ?>
            <div class="span1"></div>
            <?php if(in_array($login_fabrica, [156,160])){ ?>
                <div class="span3">
                    <div class='control-group <?= (in_array('tipo_frete', $msg_erro['campos'])) ? "error" : ""; ?>' >
                        <label class='control-label' for='tipo_frete'><?php echo traduz("tipo.de.frete");?></label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <h5 class="asteristico">*</h5>
                                <select id="tipo_frete" name="tipo_frete" class="span12">
                                    <?php
                                    if ($login_fabrica <> 156) {
                                    ?>
                                    <option value="" ><?php echo traduz("selecione");?></option>
                                    <?php
                                    }
                                    ?>
                                    <option value="FOB" <?=(getValue("tipo_frete") == "FOB") ? "selected" : ""?> ><?php echo traduz("fob");?></option>
                                    <?php
                                    if ($login_fabrica <> 156) {
                                    ?>
                                    <option value="CIF" <?=(getValue("tipo_frete") == "CIF") ? "selected" : ""?> ><?php echo traduz("cif");?></option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <? if ($areaAdmin === true) { ?>
                <div class="span3">
                    <div class='control-group <?=(in_array('tabela', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class='control-label' for='tabela'><?php echo traduz("tabela.de.precos");?></label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <h5 class="asteristico">*</h5>
                                <select id="tabela" name="tabela" class="span12">
                                    <option value="" ><?php echo traduz("selecione");?></option>
                                    <?
                                    $sql = "SELECT tabela, sigla_tabela, descricao FROM tbl_tabela WHERE fabrica = {$login_fabrica} AND ativa IS TRUE";
                                    $res = pg_query($con, $sql);

                                    if (pg_num_rows($res) > 0) {
                                        while ($resTabela = pg_fetch_object($res)) {
                                            $selected = (getValue("tabela") == $resTabela->tabela) ? "selected" : ""; ?>
                                            <option value='<?= $resTabela->tabela; ?>' <?= $selected; ?>><?= $resTabela->sigla_tabela; ?> - <?= $resTabela->descricao; ?></option>
                                        <? }
                                    } ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            <?php if(!in_array($login_fabrica, array(156,160,162,163,183)) and !$replica_einhell){?>
                <div class="span3">
                    <div class='control-group <?=(in_array('tipo_frete', $msg_erro['campos'])) ? "error" : ""; ?>' >
                        <label class='control-label' for='tipo_frete'><?php echo traduz("tipo.de.frete");?></label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <h5 class="asteristico">*</h5>
                                <select id="tipo_frete" name="tipo_frete" class="span12">
                                    <? if (!in_array($login_fabrica, array(151))) { ?>
                                        <option value="" ><?php echo traduz("selecione");?></option>
                                        <option value="FOB" <?=(getValue("tipo_frete") == "FOB") ? "selected" : ""?> ><?php echo traduz("fob");?></option>
                                    <? } ?>
                                    <option value="CIF" <?=(getValue("tipo_frete") == "CIF") ? "selected" : ""?> ><?php echo traduz("cif");?></option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
            <?php if($login_fabrica == 139){
                if (empty($validade)) $validade = "10 dias";
                if (empty($entrega))  $entrega  = "15 dias";
            ?>
                <div class="span2">
                    <div class='control-group'>
                        <label class='control-label' for='validade'><?php echo traduz("validade");?></label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <input class='span12' type="text" name="validade" id='validade' maxlength="20" value="<?=$validade?>">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span2">
                    <div class='control-group'>
                        <label class='control-label' for='entrega'><?php echo traduz("entrega");?></label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <input class='span12' type="text" name="entrega" id='entrega' maxlength="20" value="<?=$entrega?>">
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
            <? }
            if (in_array($login_fabrica, array(157,163))) { ?>
                <div class="span3">
                    <div class='control-group <?= (in_array('entrega', $msg_erro['campos'])) ? "error" : ""; ?>'>
                        <label class='control-label' for='entrega'><?php echo traduz("tipo.de.entrega");?></label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <h5 class="asteristico">*</h5>
                                <select id="entrega" name="entrega" class="span12">
                                    <option value="" ><?php echo traduz("selecione");?></option>
                                    <?php
                                    if (in_array($login_fabrica, array(163))) { ?>
                                        <option value="TRANSP" <?= (getValue("entrega") == "TRANSP") ? "selected" : ""?> ><?php echo traduz("transportadora");?></option>
                                        <option value="RFAB" <?= (getValue("entrega") == "RFAB") ? "selected" : ""?> ><?php echo traduz("retirar.na.fabrica");?></option>
                                    <?php
                                    } else { ?>
					                       <option value="TOTAL" <?= (getValue("entrega") == "TOTAL") ? "selected" : ""?> ><?=($login_fabrica == 157) ? traduz("faturamento.total.do.pedido") : traduz("total");?></option>
					                   <option value="PARCIAL" <?= (getValue("entrega") == "PARCIAL") ? "selected" : ""?> ><?=($login_fabrica == 157) ? traduz("faturamento.parcial.do.pedido") : traduz("parcial");?></option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            <? } ?>
            <div class="span1"></div>
            </div>
        <?php } ?>

        <? if (in_array($login_fabrica, array(143,163))) { ?>

            <div id="div_informacoes_transportadora" class="row-fluid">
                 <? if ($areaAdmin == true) { ?>
                    <div class="span1"></div>
                <? } ?>
                <?php
                if ($login_fabrica == 163) { ?>
                    <div class="span3">
                        <div class='control-group <?=(in_array('tipo_frete', $msg_erro['campos'])) ? "error" : ""; ?>' >
                            <label class='control-label' for='tipo_frete'> <?php echo traduz("tipo.de.frete");?></label>
                            <div class="controls controls-row">
                                <div class="span12">
                                    <h5 class="asteristico">*</h5>
                                    <select id="tipo_frete" name="tipo_frete" class="span12">
                                        <? if (!in_array($login_fabrica, array(151))) { ?>
                                            <option value="" ><?php echo traduz("selecione");?></option>
                                            <option value="FOB" <?=(getValue("tipo_frete") == "FOB") ? "selected" : ""?> ><?php echo traduz("fob");?></option>
                                        <? } ?>
                                        <option value="CIF" <?=(getValue("tipo_frete") == "CIF") ? "selected" : ""?> ><?php echo traduz("cif");?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php
                } ?>
                <div class="span12">
                    <div class='control-group <?=(in_array('transportadora', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class='control-label' for='transportadora_nome'><?php echo traduz("transportadora.nome");?></label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <div class="span10 input-append">
                                    <input type='text' name='transportadora_nome' class='span12' maxlength='50' value="<?=getValue('transportadora_nome')?>" id='transportadora_nome' >
                                    <span class="add-on" rel="lupa" <?=$transportadora_esconde_lupa?> ><i class="icon-search" ></i></span>
                                    <input type="hidden" name="lupa_config" tipo="transportadora" parametro="nome" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span1"></div>
                <input type='hidden' name='transportadora' id="transportadora_id" value="<?=getValue('transportadora')?>">
            </div>

            <div id="div_trocar_transportadora" class="row-fluid" <?=$transportadora_mostra_troca?> >
                <div class="span1"></div>
                <div class="span10">
                    <button type="button" id="trocar_transportadora" class="btn btn-danger" ><?php echo traduz("alterar.transportadora");?></button>
                </div>
            </div>
        <?php
        }

        if (in_array($login_fabrica, array(156, 175))) {
            $sql = "SELECT
                        transportadora, nome
                    FROM tbl_transportadora join tbl_transportadora_fabrica using(transportadora)
                    WHERE fabrica = {$login_fabrica}
                    AND ativo IS TRUE
                    ORDER BY nome";
            $res = pg_query($con, $sql);
            ?>
            <div id="div_informacoes_transportadora" class="row-fluid">
                <div class="span1"></div>
                <div class="span3">
                    <div class='control-group <?=(in_array('transportadora', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class='control-label' for='transportadora_cnpj'><?php echo traduz("tipo.transporte");?></label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <h5 class="asteristico">*</h5>
                                <div class="span12 input-append">
                                    <select  name='transportadora' id='transportadora' class='span10' >
                                        <option value="">- <?php echo traduz("selecione");?></option>
                                        <?php
                                            for ($i=0;$i<pg_num_rows($res);$i++) {
                                                $transportadora = pg_result($res,$i,transportadora);
                                                $nome           = pg_result($res,$i,nome);
                                                $transportadora_id = getValue('transportadora');
                                                $selected = ($transportadora_id == $transportadora) ? "SELECTED" : "";

                                                echo "<option value='$transportadora' $selected >$nome</option>";
                                            }

                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span1"></div>
            </div>

        <?php
        }

        if (in_array($login_fabrica, array(146))) {
            $sql = "SELECT
                        marca, nome
                    FROM tbl_marca
                    WHERE fabrica = {$login_fabrica}
                    AND ativo IS TRUE
                    ORDER BY nome";
            $res = pg_query($con, $sql);
            ?>

            <div class="row-fluid">
                <div class="span1"></div>
                <div class="span3">
                    <div class='control-group <?=(in_array('marca', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class='control-label' for='tipo'><?php echo traduz("marca");?></label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <h5 class="asteristico">*</h5>
                                <select id="marca" name="marca" >
                                    <option value="" ><?php echo traduz("selecione");?></option>
                                    <?php
                                    while ($resMarca = pg_fetch_object($res)) {
                                        $selected = (getValue("marca") == $resMarca->marca) ? "selected" : "";

                                        echo "<option value='{$resMarca->marca}' {$selected} >{$resMarca->nome}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>

        <?php if(in_array($login_fabrica, array(147,153,160,164,168)) || isset($telecontrol_distrib) or $replica_einhell){?>
        <div class="row-fluid">
            <div class="span1"></div>
            <?php if(in_array($login_fabrica, array(147,153,160,164,168)) or $replica_einhell){ ?>
            <div class="span4">
                <div class='control-group  <?=(in_array('parcial', $msg_erro['campos'])) ? "error" : "" ?>' >
                    <label class='control-label' > <?php echo traduz("este.pedido.pode.ser.atendido.parcial");?>?</label>
                    <div class="controls controls-row">
                        <div class="span10">
                            <h5 class="asteristico">*</h5>
                            <select name='parcial' class='span11' <?php echo (in_array($login_fabrica, array(168,147,160)) or $replica_einhell) ? " rel='noreadonly' ": ""?> >
                                <option value=''></option>
                                <option value='t' <?=(getValue("parcial") == "t") ? "selected" : ""?>><?php echo traduz("sim");?></option>
                                <option value='f' <?=(getValue("parcial") == "f") ? "selected" : ""?>><?php echo traduz("nao");?></option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>
            <?php if(isset($telecontrol_distrib) and $areaAdmin === true){ ?>
            <div class="span4">
                <div class='control-group  <?=(in_array('atender_como', $msg_erro['campos'])) ? "error" : "" ?>' >
                    <label class='control-label' > <?php echo traduz("atender.como");?>:</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <select name='atender_como' class='frm'>
                                <option value='t' <?=(getValue("atender_como") == "t") ? "selected" : ""?>> <?php echo traduz("distribuidor");?></option>
                                <option value='f' <?=(getValue("atender_como") == "f") ? "selected" : ""?>> <?php echo traduz("fabrica");?></option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>
            <div class="span1"></div>
        </div>
        <?php } ?>
        <div class="row-fluid">
                <div class="span1"></div>
                <div class="span10">
                    <div class='control-group <?=(in_array('observacao_pedido', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class='control-label' for='observacao_pedido'><?php echo traduz("observacao");?></label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <?php if(in_array($login_fabrica, [169,170])){ ?>
                                    <h5 class="asteristico">*</h5>
                                <?php } ?>
                                <?php
                                
                                $obs_pedido = getValue("observacao_pedido");
                                if (json_decode($obs_pedido) && in_array($login_fabrica, [186])) {
                                    $decode_obs = json_decode($obs_pedido, true);
                                    $decode_obs = array_map(function ($r) {
                                        return $r['os'];
                                    }, $decode_obs);
                                }

                                ?>
                                <textarea
                                    name='observacao_pedido'
                                    class='span12'
                                    style="height:auto !important;"
                                    rows="2"
                                    id='observacao_pedido'
                                ><?= (!empty($decode_obs)) ? implode("\n", $decode_obs) : $obs_pedido ?></textarea>

                            </div>
                        </div>
                    </div>
                </div>
            <div class="span1"></div>
        </div>

        <?
        if($login_fabrica == 143) {
        ?>
            <br /><br />
            <p class="tac" >
                <input type='button' value='<?php echo traduz("lista.basica");?>' class='btn btn-primary' onclick='javascript: window.open("http://products.wackerneuson.com/SpareParts28/wacker_direct.jsp?command=machinesearch&extRegId=w5&extlangId=en&urlAccess=true")'>
            </p>
        <?
        }
        ?>
        <br/>
    </div>

    <br />

    <?php
    if (in_array($login_fabrica, array(163))) { ?>

        <div class="tc_formulario" id="anexo_retirada">
            <div class="titulo_tabela" ><?php echo traduz("autorizacao.de.retirada");?></div>
            <!-- ANexo -->
            <div id="div_anexos" class="tc_formulario">
                <br />
                <div class="tac" >
                <?php
                $fabrica_qtde_anexos = 1;
                if ($fabrica_qtde_anexos > 0) {
                    echo "<input type='hidden' name='anexo_chave' value='{$anexo_chave}' />";
                    $idAnexo = $tDocs->getDocumentsByRef($pedido,'pedido')->attachListInfo;
                    $countDocs = 0;
                    for ($i = 0; $i < $fabrica_qtde_anexos; $i++) {
                        unset($anexo_link);
                        $anexo_item_imagem = "imagens/imagem_upload.png";
                        $anexo_s3     = false;
                        $anexo        = "";

                        if (strlen(getValue("anexo[{$i}]")) > 0 && getValue("anexo_s3[{$i}]") != "t") {
                            $anexos       = $s3->getObjectList(getValue("anexo[{$i}]"), true);
                            $ext = strtolower(preg_replace("/.+\./", "", basename($anexos[0])));
                            if ($ext == "pdf") {
                                $anexo_item_imagem = "imagens/pdf_icone.png";
                            } else if (in_array($ext, array("doc", "docx"))) {
                                $anexo_item_imagem = "imagens/docx_icone.png";
                            } else {
                                $anexo_item_imagem = $s3->getLink("thumb_".basename($anexos[0]), true);
                            }
                            $anexo_link = $s3->getLink(basename($anexos[0]), true);
                            $anexo        = getValue("anexo[$i]");
                         } else if(strlen($pedido) > 0) {

                            if (count($idAnexo) > 0) {
                                foreach($idAnexo as $anexo) {
                                    if ($countDocs != $i) {
                                        continue;
                                    }
                                    $ext_item = pathinfo($anexo['filename'], PATHINFO_EXTENSION);
                                    if ($ext_item == "pdf") {
                                        $anexo_item_imagem = "imagens/pdf_icone.png";
                                    } else if (in_array($ext_item, array("doc", "docx"))) {
                                        $anexo_item_imagem = "imagens/docx_icone.png";
                                    } else {
                                        $anexo_item_imagem = $anexo['link'];
                                    }
                                    $anexo_item_link = $anexo['link'];
                                    // if (!empty($anexo_item_link)) {
                                    //     echo '
                                    //         <a href="'.$anexo_item_link.'" target="_blank" >
                                    //         <img src="'.$anexo_item_imagem.'" class="anexo_thumb" style="width: 100px; height: 90px;" />
                                    //         </a>
                                    //         ';
                                    // }
                                    $countDocs++;
                                }
                                $anexo        = basename($anexos[0]);
                                $anexo_s3     = true;
                            }
                        }
                        ?>
                        <div id="div_anexo_<?=$i?>" class="tac" style="display: inline-block; margin: 0px 5px 0px 5px;">
                            <?php if (isset($anexo_item_link)) { ?>
                                <a href="<?=$anexo_item_link?>" target="_blank" >
                            <?php } ?>
                            <img src="<?=$anexo_item_imagem?>" class="anexo_thumb" style="width: 100px; height: 90px;" />
                            <?php if (isset($anexo_item_link)) { ?>
                                </a>
                                <script>setupZoom();</script>
                            <?php } ?>
                            <?php
                            if ($anexo_s3 === false) {
                            ?>
                                <button id="anexar_<?=$i?>" type="button" class="btn btn-mini btn-primary btn-block btn_acao_anexo" name="anexar" rel="<?=$i?>" ><?php echo traduz("anexar");?></button>
                            <?php
                            }
                            ?>
                            <img src="imagens/loading_img.gif" class="anexo_loading" style="width: 64px; height: 64px; display: none;" />
                            <input type="hidden" rel="anexo" name="anexo[<?=$i?>]" value="<?=$anexo?>" />
                            <input type="hidden" name="anexo_s3[<?=$i?>]" value="<?=($anexo_s3) ? 't' : 'f'?>" />
                            <?php
                            if ($anexo_s3 === true) {?>
                                <!--<button id="excluir_<?=$i?>" type="button" class="btn btn-mini btn-danger btn-block btn_acao_anexo" name="excluir" rel="<?=$i?>" >Excluir</button> -->
                                <button id="baixar_<?=$i?>" type="button" class="btn btn-mini btn-info btn-block" name="baixar" onclick="window.open('<?=$anexo_item_link?>')"><?php echo traduz("baixar");?></button>

                            <?php
                            }
                            ?>
                        </div>
                    <?php
                    }
                }
                ?>
                </div>
                <br />
            </div>
            <!-- Fim anexo-->
        </div>
        <br />
    <?php } ?>

    <?php 
        if($login_fabrica == 183 AND in_array($login_tipo_posto_codigo, array("Rev", "Rep"))){
            //if (!empty($pedido) AND !empty($numero_nf)){
            if (strlen(trim($numero_nf)) > 0){
                $readonly_nf = "readonly";
            }

    ?>
        <div class="tc_formulario" >
            <div class="titulo_tabela" ><?php echo traduz("Buscar Nota Fiscal");?></div>
            <div class="row-fluid">
                <div class="span1"></div>
                <div class="span3">
                    <div class='control-group' >
                        <label class='control-label' for='numero_nf'><?php echo traduz("Número Nota Fiscal");?></label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <input type='text' name='numero_nf' id='numero_nf' class='span10' <?=$readonly_nf?> value="<?=$numero_nf?>" />
                                <input type="hidden" name="id_posto_pedido" id="id_posto_pedido" value="<?=$id_posto_pedido?>">
                                <input type="hidden" name="nota_fiscal_posto_pedido" id="nota_fiscal_posto_pedido" value="<?=$nota_fiscal_posto_pedido?>">
                                <input type="hidden" name="linha_posto_pedido" id="linha_posto_pedido" value="<?=$linha_posto_pedido?>">
                                <input type="hidden" name="codigo_posto_pedido" id="codigo_posto_pedido" value="<?=$codigo_posto_pedido?>">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span3">
                    <div class='control-group' >
                        <label class='control-label' for='numero_nf'></label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <button type='button' id="buscar_nota_fiscal" class="btn btn-primary">Buscar Nota Fiscal</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row-fluid" id='row-info-posto' <?=$display_posto_info?>>
                <div class="span1"></div>
                <div class="span3">
                    <div class='control-group' >
                        <label class='control-label' for='codigo-info-posto'>Código Cliente</label>
                        <div class="controls controls-row">
                           <input class="span12" type="text" readonly="true" name="codigo-info-posto" value="<?=$codigo_posto_info?>">
                        </div>
                    </div>
                </div>
                <div class="span7">
                    <div class='control-group' >
                        <label class='control-label' for='nome_info_posto'>Nome Cliente</label>
                        <div class="controls controls-row">
                           <input class="span12" type="text" readonly="true" name="nome_info_posto" value="<?=$nome_info_posto?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <br/> 
    <?php } ?>

    <?php if (!in_array($login_fabrica, array(143))) { ?>
        <?if($login_fabrica == 168 and !$areaAdmin){ ?>
        <div class="tc_formulario" >
            <div class="titulo_tabela" ><?php echo traduz("consultar.tabela.de.preco");?></div>
            <input type='hidden' name='produto_id' id="produto_id" />
            <div class="row-fluid">
                <div class="span1"></div>
                    <div class="span10" style='text-align:center'>
                        <br>
                        <button type="button" id="consulta_tabela" class="btn btn-primary" ><?php echo traduz("consultar.pecas");?></button>
                    </div>
                <div class="span1"></div>
            </div>
        </div>
        <br>
        <?php } ?>

        <?php
        if (!$catalogoPedido) {
        ?>
            <div class="tc_formulario" >
                <div class="titulo_tabela" ><?php echo traduz("consultar.lista.basica.de.produto");?></div>
                <input type='hidden' name='produto_id' id="produto_id" />
                <div class="row-fluid">
                    <div class="span1">
                        <div class='control-group' >
                            <label>&nbsp;</label>
                            <div class="controls controls-row">
                                <div class="span12 tac">
                                    <button type="button" class="btn btn-mini btn-danger remove_produto" >X</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="span3">
                        <div class='control-group' >
                            <label class='control-label' for='produto_referencia'><?php echo traduz("referencia.produto");?></label>
                            <div class="controls controls-row">
                                <div class="span12">
                                    <div class="span12 input-append">
                                        <input type='text' name='produto_referencia' id='produto_referencia' class='span10' value="<?php echo $produto_referencia; ?>" />
                                        <?php 
                                            if ($login_fabrica == 183 AND $areaAdmin === false){ 
                                                if ($login_tipo_posto_codigo == "Aut"){
                                        ?>
                                                    <span class="add-on" rel="lupa" ><i class="icon-search" ></i></span>
                                                    <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
                                        <?php 
                                                }
                                        ?>
                                        <?php } else { ?>
                                            <span class="add-on" rel="lupa" ><i class="icon-search" ></i></span>
                                            <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="span5">
                        <div class='control-group' >
                            <label class='control-label' for='produto_descricao'><?php echo traduz("descricao.produto");?></label>
                            <div class="controls controls-row">
                                <div class="span12">
                                    <div class="span12 input-append">
                                        <input type='text' name='produto_descricao' id="produto_descricao" class='span10' maxlength='50' value="<?php echo $produto_descricao; ?>" />
                                        <?php 
                                            if ($login_fabrica == 183 AND $areaAdmin === false){ 
                                                if ($login_tipo_posto_codigo == "Aut"){
                                        ?>
                                                    <span class="add-on" rel="lupa" ><i class="icon-search" ></i></span>
                                                    <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
                                        <?php   } ?>
                                        <?php } else { ?>
                                            <span class="add-on" rel="lupa" ><i class="icon-search" ></i></span>
                                            <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="span2"  <?php echo $ocultaItens;?>>
                        <div class='control-group' >
                            <label class='control-label' >&nbsp;</label>
                            <div class="controls controls-row">
                                <div class="span12">
                                    <button type="button" id="lista_basica" class="btn btn-primary" ><?php echo traduz("lista.basica");?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="span1"></div>
                </div>
            </div>

            <br />
        <?php 
        }
    }

    if(!in_array($login_fabrica, [157,169,170,183]) && !$catalogoPedido){
    ?>

    <div id="divUploadCSV" class="tc_formulario" >
        <div class="titulo_tabela" ><?php echo traduz("upload.de.pecas.via.csv");?></div>

        <div class="alert alert-warning">
            <strong>
            <?php echo traduz("o.arquivo.deve.ser.csv.separado.por");?> ;<br />
            <?php echo traduz("o.layout.a.ser.seguido.e.referencia.da.peca.quantidade");?><br />
            <?php echo traduz("o.limite.e.de.500.linhas.por.arquivo");?>
            </strong>
        </div>

        <div class="row-fluid">
            <div class="span2"></div>
            <div class="span5">
                <div class='control-group' >
                    <label class="control-label" for="upload_peca"><?php echo traduz("arquivo");?></label>
                    <div class="controls controls-row">
                        <input type="file" id="upload_peca" name="upload_peca" form="form_upload_peca" />
                    </div>
                </div>
            </div>
            <div class="span2">
                <div class='control-group' >
                    <label class="control-label" >&nbsp;</label>
                    <div class="controls controls-row">
                        <button type="button" id="submit_upload_peca" class="btn btn-primary" data-loading-text="<?php echo traduz("realizando.upload");?>..." style="min-width: 170px;" ><?php echo traduz("upload");?></button>
                    </div>
                </div>
            </div>
        </div>

        <br />

    </div>

    <br />
   <?php
    }
   ?>
    
    <div class="mensagem"></div>

    <br />

    <?php
    if (in_array($login_fabrica, array(147,149,153,156,157,165)) || ($usa_calculo_ipi) || (regiao_suframa() == false && in_array($login_fabrica, array(161)))) {
        $width_form = 'style="width: 120%;position: relative;left: -10%;"';
        $spanSub = "span1";
    } else {
        $spanSub = "span2";
    }
    
    if ($catalogoPedido) {
        $spanSub = "span3";
    }
    
    if ($catalogoPedido) {
    ?>
        <button type="button" id='catalogo-pecas' class="btn btn-warning btn-block btn-large"><i class='fa fa-table'></i> Catálogo de peças</button>
        
        <div class="modal fade hide modal-full-screen" id="modal-catalogo-pecas">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true" style='color: red; opacity: unset;'><i class='fa fa-times'></i> Voltar para a tela do pedido</button>
                        <h4 class="modal-title">Catálogo de peças</h4>
                    </div>
                    <div class="modal-body" style='max-height: 90%;'></div>
                </div>
            </div>
        </div>
        
        <br />
    <?php
    }
    ?>

    <div class='tc_formulario' <?= $width_form ?>>
        <?php
        if ($catalogoPedido) {
        ?>
            <div class="titulo_tabela"><i class='fa fa-shopping-cart'></i> <?=traduz("Carrinho")?></div>
        <?php
        } else {
        ?>
            <div class="titulo_tabela"><?php echo traduz("pecas");?></div>
        <?php
        }
            $spanIpi = (in_array($login_fabrica, array(149) || $usa_calculo_ipi) && $areaAdmin === false) ? "span1" : "span2";
            if ($login_fabrica == 153) {
                $spanIpi = "span1";
            }

            if ((strlen($pedido) > 0 || strlen($cook_pedido) > 0)) {
                $pedido = (strlen($cook_pedido) > 0) ? $cook_pedido : $pedido;
                $sql = "SELECT
                            tbl_pedido_item.pedido_item,
                            tbl_pedido_item.peca,
                            tbl_peca.referencia,
                            tbl_peca.descricao,
                            tbl_peca.multiplo,
                            tbl_peca.estoque,
                            tbl_peca.unidade,
                            tbl_pedido_item.qtde,
                            tbl_pedido_item.preco,
                            tbl_pedido_item.ipi,
                            tbl_pedido_item.total_item,
                            tbl_pedido_item.causa_defeito
                        FROM tbl_pedido
                        JOIN tbl_pedido_item USING (pedido)
                        JOIN tbl_peca USING (peca)
                        WHERE tbl_pedido_item.pedido = $pedido
                        AND tbl_pedido.fabrica = $login_fabrica
                        ORDER BY tbl_pedido_item.pedido_item";

                $res = pg_query($con, $sql);
                // if (($countItens = pg_num_rows($res)) > 20){
                //     $qtde_item = $countItens;
                // }
                
                if (($countItens = pg_num_rows($res)) > 0){
                    $qtde_item = $countItens;
                }
            }

	    $desc_span = (in_array($login_fabrica, array(157,161,183))) ? "span2" : "span3";
	    $desc_span = (in_array($login_fabrica, array(143))) ? "span6" : $desc_span;
        ?>

        <div class="row-fluid carrinho-header" style="height:auto">
            <div class="span1"></div>
            <div class="span2"><?php echo traduz("referencia");?></div>
            <div class="<?php echo $desc_span; ?>"><?php echo traduz("descricao");?></div>

            <?php if ($login_fabrica == 183){ ?>
                <div class="<?php echo $desc_span; ?>"><?php echo traduz("Código Utilização");?></div>
            <?php } ?>
        <?php if ($login_fabrica == 161 && $areaAdmin === true) { ?>
            <div class="span1"><?php echo traduz("estoque");?></div>
            <?php } ?>
            
            <?php if($login_fabrica <> 183){ ?>
                <div class="span1"><?php echo traduz("multiplo");?></div>
            <?php } ?>
			<div class="span1"><?php echo traduz("qtde");?></div>
			<?php
            if ($login_fabrica == 163) { ?>
                <div class="span2"><?php echo traduz("preco");?></div>
            <?php
            } elseif(!in_array($login_fabrica, array(143))) { ?>
                <div class="span1"><?php echo traduz("preco");?></div>
            <?php
            }

            ?>
			<? if (in_array($login_fabrica, array(147,149,153,156,157,165)) || ($usa_calculo_ipi) || (regiao_suframa() == false && in_array($login_fabrica, array(161)))) {
                ?>
				<div class="span1" id="titulo-ipi"><?php echo traduz("ipi");?></div>
                <div class="<?=$spanSub?>"><?php echo traduz("sub.total.s.ipi");?></div>
			<? } ?>
            <?php if(in_array($login_fabrica, array(157))){ ?>
                <div class="span1" id="titulo-ipi"><?php echo traduz("desconto");?> %</div>
            <?php } ?>
	    <?php if(!in_array($login_fabrica, array(143))){ ?>
		<div class="<?=$spanSub?>"><?php echo traduz("sub.total");?></div>
	    <?php } ?>
		</div>

		<div class="div_pecas" >
			<input type="hidden" name="qtde_item" id="qtde_item" value="<?=$qtde_item?>" />
			<input type="hidden" name="item_data" id="item_data" value="" />

			<?php
			$total_sem_ipi = 0;
            $readonlyPreco = "readOnly";


            if ($login_fabrica == 162 && !empty($hd_chamado) && $areaAdmin === TRUE) {
                $readonlyPreco = "";
            }
//         echo "<pre>";
// print_r($_POST);
            $arrayInicial = [];
            foreach (pg_fetch_all($res) as $peca) {
                $arrayInicial[] = 
                [
                    'peca' => $peca['peca'],
                    'referencia' => $peca['referencia'],
                    'qtde' => $peca['qtde'],
                    'descricao' => utf8_encode($peca['descricao']),
                ];
            }
            $json = json_encode($arrayInicial);
            echo "<input type='hidden' value='{$json }' name='valoresIniciais' />";

            for ($i = 0 ; $i < $qtde_item ; $i++) {
                if ((strlen($pedido) > 0 || strlen($cook_pedido) > 0)) {
                    $pedido = (strlen($cook_pedido) > 0) ? $cook_pedido : $pedido;
                    if ($countItens > 0) {
                        $pedido_item        = pg_fetch_result($res, $i, "pedido_item");
                        $peca_id            = pg_fetch_result($res, $i, "peca");
                        $peca_referencia    = pg_fetch_result($res, $i, "referencia");
                        $peca_descricao     = pg_fetch_result($res, $i, "descricao");
                        $multiplo           = pg_fetch_result($res, $i, "multiplo");
                        $qtde               = pg_fetch_result($res, $i, "qtde");
                        $preco              = pg_fetch_result($res, $i, "preco");
                        $ipi                = pg_fetch_result($res, $i, "ipi");
                        $total_item         = pg_fetch_result($res, $i, "total_item");
                        $estoque            = pg_fetch_result($res, $i, "estoque"); 

                        if ($login_fabrica == 183){
                            $causa_defeito   = pg_fetch_result($res, $i, "causa_defeito");
                        }

                        if (in_array($login_fabrica, [175])) {
                            $unidade = pg_fetch_result($res, $i, "unidade");
                            if (in_array($unidade, ['c', 'm', 'mm', 'cm', 'dm', 'mt', 'dam', 'hm', 'km'])) {
                                $multiplo = 0;
                            }
                        }
                    } else {
                        $pedido_item     = $_POST["pedido_item_{$i}"];
                        $peca_referencia = $_POST["peca_referencia_{$i}"];
                        $peca_id         = $_POST["peca_id_{$i}"];
                        $peca_descricao  = $_POST["peca_descricao_{$i}"];
                        $qtde            = $_POST["qtde_{$i}"];
                        $multiplo        = $_POST["multiplo_{$i}"];
                        $estoque          = $_POST["estoque_{$i}"]; 
                        $preco           = $_POST["preco_{$i}"];
                        $total_item     = $_POST["sub_total_{$i}"];

                        if (in_array($login_fabrica, [175])) {
                            $unidade = $_POST["unidade_{$i}"];
                            if (in_array($unidade, ['c', 'm', 'mm', 'cm', 'dm', 'mt', 'dam', 'hm', 'km'])) {
                                $multiplo = 0;
                            }
                        }

                        if ($login_fabrica == 183){
                            $causa_defeito   = $_POST["causa_defeito_{$i}"];
                        }

                        if (in_array($login_fabrica, array(147,149,153,156,157,165)) || ($usa_calculo_ipi) || (regiao_suframa() == false && in_array($login_fabrica, array(161)))) {
							$ipi = $_POST["ipi_{$i}"];
						}
					}
				} else {
                    $pedido_item     = $_POST["pedido_item_{$i}"];
                    $peca_id         = $_POST["peca_id_{$i}"];
                    $peca_referencia = $_POST["peca_referencia_{$i}"];
                    $peca_descricao  = $_POST["peca_descricao_{$i}"];
                    $qtde            = $_POST["qtde_{$i}"];
                    $multiplo        = $_POST["multiplo_{$i}"];
                    $estoque         = $_POST["estoque_{$i}"];
                    $preco           = $_POST["preco_{$i}"];
                    $total_item      = $_POST["sub_total_{$i}"];
                    
                    if ($login_fabrica == 183){
                        $causa_defeito   = $_POST["causa_defeito_{$i}"];
                    }

                    if (in_array($login_fabrica, [175])) {
                        $unidade = $_POST["unidade_{$i}"];
                        if (in_array($unidade, ['c', 'm', 'mm', 'cm', 'dm', 'mt', 'dam', 'hm', 'km'])) {
                            $multiplo = 0;
                        }
                    }

					if (in_array($login_fabrica, array(147,149,153,156,157,165)) || ($usa_calculo_ipi) || (regiao_suframa() == false && in_array($login_fabrica, array(161)))) {
						$ipi = $_POST["ipi_{$i}"];
					}
				}

				$preco = preg_match('/,/', $preco) ?$preco : number_format($preco,2,",",".");
				$total_item = preg_match('/,/',$total_item) ? $total_item : number_format($total_item,2,",",".");
				if (strlen($peca_id) > 0 and empty($_RESULT['exportado'])) {
					$display      = "inline";
					$readonly     = 'readonly';
					$lupa_display = "none";
				} else {
					unset($bgcolor, $readonly, $lupa_display);
					$display = "none";
				}

                if ($login_fabrica == 183 AND in_array($login_tipo_posto_codigo, array("Rev", "Rep"))){
                    $lupa_display = "none";
                }

				if (!empty($peca_id)) {
					$total_sem_ipi += ($preco * $qtde);
				}

				$cor = "";

				if ($linha_erro == $i && strlen($msg_erro) > 0) $cor = '#ffcccc';

                $classeErroEstoque = "";
                if(($login_fabrica == 160 or $replica_einhell) and (strlen($peca_id)>0) ){
                    $sqlintencao = "SELECT * from tbl_intencao_compra_peca where pedido = $pedido and peca = $peca_id  and informado = false";
                    $resIntencao = pg_query($con, $sqlintencao);
                    if(pg_num_rows($resIntencao)>0){ 
                        $classeErroEstoque = " classeErroEstoque2 ";
                        ?>
                        <div style="padding-left:30px" name='peca_indisponivel_<?=$i ?>' class="classeErroEstoque">
                            Peça Indisponível
                            <span class="icon-arrow-down"></span>
                        </div>
                
                <? }else{
                        $classeErroEstoque = "";
                    }
                
                }
				?>

				<div class="row-fluid <?=$classeErroEstoque?>" name="peca_<? echo $i ?>" <? echo $bgcolor; ?> >
					<div class="span1"  <?php echo $ocultaItens;?>>
						<div class='control-group' >
							<div class="controls controls-row">
								<div class="span12 tac" name="remove_peca_<?=$i?>">
									<button type="button" class="btn btn-mini btn-danger" name="remove_peca_<?=$i?>" rel="<?=$i?>" style="display: <? echo $display; ?>; margin-top: 4px;" >X</button>
								</div>
							</div>
						</div>
					</div>
					<div class="span2">
						<div class='control-group' >
							<div class="controls controls-row">
								<div class="span12 input-append" >
									<input type="hidden" name="pedido_item_<?= $i ?>" value="<?= $pedido_item; ?>" />
									<input type="hidden" name="peca_id_<?= $i ?>" rel="peca_id" value="<?=$peca_id?>" posicao="<?= $i ?>" />
									<input type="text"  class="span9" name="peca_referencia_<?= $i ?>"  value="<?= $peca_referencia; ?>" <?= $readonly; ?> />
									<span class="add-on" rel="lupa_peca" style="display: <?= $lupa_display; ?>;"   >
										<i class="icon-search"></i>
									</span>
									<? if (in_array($login_fabrica, array(157))) { ?>
										<input type="hidden" name="lupa_config" tipo="lista_basica" parametro="referencia" preco="true" posicao="<?= $i ?>" />
									<? } else { ?>
										<input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" preco="true" <? if (in_array($login_fabrica,array(147,149,153,156,165)) || ($usa_calculo_ipi) || (regiao_suframa() == false && in_array($login_fabrica, array(161)))) echo 'ipi="true" tela_pedido="true"'; ?> posicao="<?= $i ?>" <?=($login_fabrica == 162) ? "callcenter=$callcenter" : ""?> />
									<? } ?>
								</div>
							</div>
						</div>
					</div>
					<div class="<?php echo $desc_span; ?>">
						<div class='control-group' >
							<div class="controls controls-row">
								<div class="span12 input-append">
									<input class="span10" type="text" name="peca_descricao_<? echo $i ?>"  value="<? echo $peca_descricao ?> "  <?php echo $readonly; ?> >
									<? if (!in_array($login_fabrica,array(143))) { ?>
										<span class="add-on" rel="lupa_peca" style="display: <? echo $lupa_display; ?>"  >
											<i class="icon-search"></i>
										</span>
									<? } ?>
									<? if (in_array($login_fabrica, array(157))) { ?>
										<input type="hidden" name="lupa_config" tipo="lista_basica" parametro="descricao" preco="true" posicao="<?= $i ?>" />
									<? } else { ?>
										<input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" preco="true" <? if (in_array($login_fabrica,array(147,149,153,156,165)) || ($usa_calculo_ipi) || (regiao_suframa() == false && in_array($login_fabrica, array(161)))) echo 'ipi="true" tela_pedido="true"'; ?> posicao="<?= $i ?>" <?=($login_fabrica == 162) ? "callcenter=$callcenter" : ""?> />
									<? } ?>

								</div>
							</div>
						</div>
					</div>
    
                    <?php if ($login_fabrica == 183){
                        // if (count($msg_erro)){
                        //     $causa_defeito = $_POST["causa_defeito_{$i}"];
                        // }
                     ?>
                        <div class="<?php echo $desc_span; ?>">
                            <div class='control-group' >
                                <div class="controls controls-row">
                                    <div class="span12">
                                        <select class='span12' name='causa_defeito_<?= $i; ?>'>
                                            <option value=""><?php echo traduz("selecione");?></option>
                                            <?php 
                                                $sql_utilizacao = "
                                                    SELECT causa_defeito, codigo, descricao 
                                                    FROM tbl_causa_defeito 
                                                    WHERE fabrica = {$login_fabrica} 
                                                    AND ativo IS TRUE;";
                                                $res_utilizacao = pg_query($con, $sql_utilizacao);
                                                
                                                if (pg_num_rows($res_utilizacao) > 0) {

                                                    for($ut = 0; $ut < pg_num_rows($res_utilizacao); $ut++) {
                                                        $desc_motivo   = pg_fetch_result($res_utilizacao, $ut, "descricao");
                                                        $cod_motivo    = pg_fetch_result($res_utilizacao, $ut, "codigo");
                                                        $causa_defeito_banco = pg_fetch_result($res_utilizacao, $ut, "causa_defeito");

                                                        $selected_codigo_utilizacao = "";
                                                        if ($causa_defeito == $causa_defeito_banco) {
                                                            $selected_codigo_utilizacao = "selected";
                                                        }
                                                        echo "<option value='{$causa_defeito_banco}' $selected_codigo_utilizacao>{$cod_motivo} - {$desc_motivo}</option>";
                                                    }
                                                }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
    
                    <?php if ($login_fabrica == 161 && $areaAdmin === true) { ?>
                        <div class="span1">
                            <div class='control-group' >
                                <div class="controls controls-row">
                                    <div class="span12">
                                        <input class="span8" type="text" name="estoque_<?=$i?>"  maxlength='5' value="<?=$estoque > 0 ? $estoque : '0'?>" id='estoque_<?=$i?>' readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                    <?php if ($login_fabrica <> 183){ ?>    
                    <div class="span1">
                        <div class='control-group' >
                            <div class="controls controls-row">
                                <div class="span12">
                                    <input class="span8" type="text" name="multiplo_<?=$i?>"  maxlength='5' value="<?=$multiplo?>" id='multiplo_<?=$i?>' readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                    <div class="span1">
                        <div class='control-group' >
                            <div class="controls controls-row">
                                <div class="span12">
                                    <input class="span8" type="text" <?php echo $desabilitaItens;?> name="qtde_<?=$i?>" linha="<?= $i ?>"  maxlength='5' value="<?=$qtde?>" data-posicao="<?=$i?>"  id='qtde_<?=$i?>' >
                                    <?php if (in_array($login_fabrica, [175])) { ?>
                                            <input type="hidden" name="unidade_<?=$i?>" linha="<?=$i?>" value="<?=$unidade?>" id="unidade_<?=$i?>" />
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                    if ($login_fabrica == 163) { ?>
                        <div class="span2">
                    <?php
                    } else { ?>
                        <div class="span1">
                    <?php
		    }

			if(in_array($login_fabrica,array(143))){
				$display_precos = "style='display:none;'";
			}
                    ?>

			    <div class='control-group' <?=$display_precos?>>
                            <div class="controls controls-row">
                                <div class="span12">
                                    <input class="span12" id="preco_<?= $i ?>" type="text" name="preco_<?= $i ?>"  value="<?= $preco ?>" price="true" <?=$readonlyPreco?>>
                                </div>
                            </div>
                        </div>
                    </div>
                    <? if (in_array($login_fabrica, array(147,149,153,156,157,165)) || ($usa_calculo_ipi) || (regiao_suframa() == false && in_array($login_fabrica, array(161)))) { ?>
                        <div class="span1 campo-ipi">
                            <div class='control-group' >
                                <div class="controls controls-row">
                                    <div class="span12">
                                        <input class="span8" id="ipi_<?=$i?>" type="text" name="ipi_<?=$i?>"  value="<?=$ipi?>" readonly>%
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="span1 campo-subtotal-sem-ipi">
                            <div class='control-group' >
                                <div class="controls controls-row">
                                    <div class="span12">
                                        <input class="span12" price="true" id="sub_total_sem_ipi_<?=$i?>" type="text" name="sub_total_sem_ipi_<?=$i?>"  value="<?= $sub_total_sem_ipi ?>" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <? } ?>

                    <?php
                    if(in_array($login_fabrica, array(157))){

                        if($areaAdmin == true && strlen($_GET["pedido"]) > 0){
                            if(strlen($desconto_posto) > 0 && strlen($peca_referencia) > 0){
                                $desconto_item = $desconto_posto;
                            }else{
                                $desconto_item = "";
                            }
                        }

                        if(strlen($login_posto) > 0){
                            if(strlen($desconto_posto) > 0 && strlen($peca_referencia) > 0){
                                $desconto_item = $desconto_posto;
                            }else{
                                $desconto_item = "";
                            }
                        }

                    ?>
                        <div class="span1 campo-desconto-item">
                            <div class='control-group' >
                                <div class="controls controls-row">
                                    <div class="span12">
                                        <input class="span8" id="desconto_item_<?=$i?>" type="text" name="desconto_item_<?=$i?>" value="<?=$desconto_item?>" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>

		            <div class="<?=$spanSub?>" <?=$display_precos?>>
                        <div class='control-group' >
                            <div class="controls controls-row">
                                <div class="span12">
                                    <?php
                                    $total_peca = $total_item
                                    ?>
                                    <input class="span11" style='text-align:right' rel='total_pecas' name="sub_total_<? echo $i ?>" id="sub_total_<? echo $i ?>" type="text" readonly  value='<?=$total_peca?>' />

                                </div>
                            </div>
                        </div>
                    </div>
                    <?php if($login_fabrica == 138 and $areaAdmin) {
                        if(strlen(trim($peca_id))>0){
                            $mostrar_btn_alterar = " style='display:block' ";
                        }else{
                            $mostrar_btn_alterar = " style='display:none' ";
                        }
                        ?>
                    <div id="div_alterar_pecas_<?=$i?>" <?= $mostrar_btn_alterar ?> >
                        <button type="button" class='btn btn-primary' id="alterar_peca_<?=$i?>" name="alterar_peca_<?=$i?>" posicao="<?=$i?>" class="btn" ><?php echo traduz("alterar");?></button>
                    </div>
                    <? } ?>
                </div>
            <?php
            }
            ?>
        </div>
        <input type="hidden" value="<?= $i ?>" id="qtde_itens_pedido" />
        <?php
        if (!$catalogoPedido) {
        ?>
            <div class='row-fluid' <?php echo $ocultaItens;?>>
                <div class="span1" ></div>
                <div class="span11 tac">
                    <button type="button" id="adicionar_linha" class="btn btn-primary" ><?php echo traduz("adicionar.nova.linha");?></button>
                </div>
            </div>
        <?php
        }
        
        $usa_frete = \Posvenda\Regras::get("usa_frete", "pedido_venda", $login_fabrica);

        if(!empty($usa_frete)){
          ?>
            <div class='row-fluid'>
                <div class='span9 tar'><?php echo traduz("frete");?>:</div>

                <div class='span2 tar'>
                    <div class='control-group' >
                        <div class="controls controls-row">
                            <div class="span12">
                                <input type="text" class="span12" style='text-align:right' name="valor_frete" id="valor_frete" readonly value="<?=number_format($usa_frete, 2, ",", ".")?>" >
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php
        }

            $usa_desconto_fabricante = \Posvenda\Regras::get("usa_desconto_fabricante", "pedido_venda", $login_fabrica);
            if($usa_desconto_fabricante == true && $areaAdmin === true){
                ?>
                <div class='row-fluid'>
                    <div class='span3 tar' <?=$desconto_visibility?> ><?php echo traduz("desconto.fabricante");?> (%):</div>
                    <div class='span3 tar' <?=$desconto_visibility?> >
                        <div class='control-group' >
                            <div class="controls controls-row">
                                <div class="span12">
                                    <input type="text" class="span10" price="true" style='text-align:right' name="valor_desconto_fabricante" id="valor_desconto_fabricante" value="<?php echo $valor_desconto_fabricante; ?>" >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?
            }
            $usa_adicional_fabricante = \Posvenda\Regras::get("usa_adicional_fabricante", "pedido_venda", $login_fabrica);
            if($usa_adicional_fabricante == true && $areaAdmin === true){
                ?>
                <div class='row-fluid'>
                    <div class='span3 tar'><?php echo traduz("adicional.fabricante");?>(R$):</div>
                    <div class="span3 tar">
                        <div class='control-group' >
                            <div class="controls controls-row">
                                <div class="span12">
                                    <input type="text" class="span10" price="true" style='text-align:right' name="adicional_fabricante" id="adicional_fabricante" value="<?php echo $adicional_fabricante; ?>"  />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?
            }
        ?>

        <?php
        if(in_array($login_fabrica, array(163,164))){
            if (in_array($login_fabrica, array(163)) ){
                $valor_frete_estado = getValue('valor_frete');
                if($areaAdmin === true) {
                    $readOnly = "";
                } else {
                    $readOnly = "readonly";
                }
            }
        ?>

        <div class="row-fluid">

            <div class='span1'></div>

            <div class='span6'></div>

            <div class='span2' style="text-align: right; padding-top: 5px;"><?php echo traduz("valor.do.frete");?>:</div>

            <div class="span2">
                <input class="span11" type="text" style='text-align:right' name="total_frete_estado" id="total_frete_estado" <?=$readOnly;?> value="<?php echo number_format($valor_frete_estado, 2, ",", "."); ?>" />
            </div>

        </div>

        <div class="row-fluid">

            <div class='span1'></div>

            <div class='span6'></div>

            <div class='span2' style="text-align: right; padding-top: 5px;"><?php echo traduz("total.de.pecas");?>:</div>

            <div class="span2">
                <input class="span11" type="text" style='text-align:right' name="total_parcial_pecas" id="total_parcial_pecas" readonly value="<?php echo number_format($total_parcial_pecas, 2, ",", "."); ?>" />
            </div>

        </div>

        <?php

        }

        ?>
    <?php  if ($login_fabrica == 143) {
                echo '
                    <div class="row-fluid">
                        <div class="span1"></div>
                        <div class="span10">
                            <div class="alert alert-warning">
                                '.traduz("prezado.posto.apos.finalizar.o.pedido.o.mesmo.ficara.com.o.status.aguardando.reprocessamento.nesse.periodo.o.seu.pedido.sera.recalculado.apos.o.reprocessamento.informaremos.os.valores").'
                            </div>
                        </div>
                        <div class="span1"></div>
                    </div>';
        }
    ?>
        <div class='row-fluid carrinho-total'>
            <?php
            $usa_desconto = \Posvenda\Regras::get("usa_desconto", "pedido_venda", $login_fabrica);

            if($usa_desconto == true && !in_array($login_fabrica, array(157))){
                if ($login_fabrica == 143 && $areaAdmin === false) {
                    $desconto_visibility = "style='visibility: hidden;'";
                }

                $valor_desconto = ($login_fabrica == 147) ? $desconto_posto : $valor_desconto;

                ?>
                <div class="span2"></div>
                <div class='span2 tar' <?=$desconto_visibility?> >
                    <div class='control-group' >
                        <div class="controls controls-row">
                            <div class="span11">
                                <?


				if($login_fabrica <> 160 and !$replica_einhell) {

                                echo (in_array($login_fabrica, array(147,162)))? traduz("desconto.posto") : traduz("desconto");


                                ?>
                                <input type="text" class="span10" style='text-align:right' name="valor_desconto" id="valor_desconto" readonly value="<?=$valor_desconto?>" >% <?php


} else {

?>

                                <input type="hidden" class="span10" style='text-align:right' name="valor_desconto" id="valor_desconto" readonly value="<?=$valor_desconto?>"

<?php
}
?>
                                <input type="hidden" class="span10" style='text-align:right' name="recalcula_desconto" id="recalcula_desconto" >
                            </div>
                        </div>
                    </div>
                </div>
            <?php
            } else if ($catalogoPedido) {
            ?>
                <div class='span8'>
                    <button type="button" id="adicionar_linha" class="btn btn-info" style='margin-top: 10px; margin-left: 10px;' ><i class='fa fa-plus'></i> Adicionar nova linha</button>
                </div>
            <?php
            } else {
            ?>
               <div class='span7'></div>
            <?php
            }

                if(in_array($login_fabrica, array(147,153)) || (regiao_suframa() == false && in_array($login_fabrica, array(161)))){
                    $total_descricao = traduz("total.c.ipi");
                    $spanTotal = "span2";
                }else{
                    $total_descricao = traduz("total");
                    $spanTotal = "span1";
                    echo "<div class='span1'></div>";
                }

            if ($catalogoPedido) {
            ?>
                <div class="span3 tar" <?=$desconto_visibility?>>
            <?php    
            } else {
            ?>
                <div class="span2 tar" <?=$desconto_visibility?>>
            <?php
            }
            ?>
                <div class='control-group' >
                    <div class="controls controls-row">
                        <div class="span12">
                            <?php echo $total_descricao ?>
                            <?php
                            if (isset($_POST["total_pecas"])) {
                                $total_pecas = $total_pecas;
                            } else {
                                $total_pecas = number_format($_RESULT["total_pecas"], 2, ",", ".");
                            }
                            ?>
                            <br />
                            <input class="span11" type="text" style='text-align:right' name="total_pecas" id="total_pecas" readonly value="<?=$total_pecas?>" />

                            <input type="hidden" style='text-align:right' name="total_pecas_hidden" id="total_pecas_hidden" readonly value="<?=$total_pecas?>" />
                        </div>
                    </div>
                </div>
            </div>
            <?php
                 if(in_array($login_fabrica, array(147)) || (regiao_suframa() == false && in_array($login_fabrica, array(161)))){
                ?>
                    <div class="span2 tar" >
                        <div class='control-group' >
                            <?php echo traduz("total.s.ipi");?>:
                            <div class="controls controls-row">
                                <div class="span12">
                                    <?php

                                    if (!empty($total_sem_ipi)) {
                                        if ($usa_desconto === true) {
                                            $total_sem_ipi -= $total_sem_ipi * $valor_desconto / 100;
                                        }

                                        $total_sem_ipi = number_format($total_sem_ipi, 2, ",", ".");
                                    }

                                    ?>
                                    <input class="span13" type="text" style='text-align:right' name="total_sem_ipi" id="total_sem_ipi" readonly value="<?=$total_sem_ipi?>" />
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            <?php
                if ($usa_desconto == true && !in_array($login_fabrica, array(157,158,160,163)) and !$replica_einhell) {
                ?>
                <div class='span1'></div>
                <div class="span3 tar" <?=$desconto_visibility?>>
                    <div class='control-group'>
                        <div class="controls controls-row">
                            <div class="span10">
                                <?php echo traduz("total.pedido.s.desconto");?>
                                <input class="span11" value="0,00" price="true" type="text" style='text-align:right' name="total_pecas_desconto" id="total_pecas_desconto" readonly />
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
        <?php
        if ($login_fabrica == 147 && !empty($cook_pedido)) {
            $sqlLimiteCredito = "SELECT credito FROM tbl_posto_fabrica WHERE fabrica = {$login_fabrica} AND posto = {$login_posto}";
            $resLimiteCredito = pg_query($con, $sqlLimiteCredito);

            $limite_credito = number_format(pg_fetch_result($resLimiteCredito, 0, "credito"), 2, ",", ".");
            ?>
            <div class="alert alert-info">
                <strong><?php echo traduz("limite.de.credito");?>: </strong><?=$limite_credito?>
            </div>
        <?php
        }else if($login_fabrica == 168){
        ?>
            <div class="alert alert-info" >
                <strong><?php echo strtoupper(traduz("calculo.do.frete.sujeito.a.disponibilidade.do.estoque"));?></strong>
            </div>
        <?php
        }
        ?>
    </div>

    <br />
   
	<div class="tac">
		<?php
		if($login_fabrica == 35) { 
		?>
        Justificativa: 
        <br>
         <textarea name="justificativa" id="justificativa" required="required"></textarea>
        <br><br>
        <input type="hidden" name="recalcula" value="" id="recalcula_pedido" >
		<?php
		}
        if ($catalogoPedido) {
        ?>
            <button type='button' class='btn btn-primary btn-large' onclick="enviar_frm(this.form)" ><i class='fa fa-save'></i> Gravar Pedido</button>
        <?php
        } else {
        ?>
	    <input type='button' value='<?= traduz('Gravar Peças') ?>'  class='btn btn-primary' onclick="enviar_frm(this.form)" id="btn_eviar" >
        <?php
        }
        
        if ($botao_recalcular) { ?>

            <input type='button' value='Recalcular'  class='btn btn-danger' id="btn_recalcular" />

        <?php
        } ?>
    </div>
</form>
<?php
if ($areaAdmin === false && !empty($cook_pedido) && temItemNoPedido($cook_pedido)) {
?>
    <div class="alert alert-warning">
        <p align='justify' style='font-size:16px'><b><?php echo traduz("aviso para o pedido ser enviado para a fabrica e necessario finalizar o pedido");?></b></p>
    </div>
    <form name="pedido_finaliza" id="pedido_finaliza" method="post" style="margin: 0 auto; text-align: center;" >
        <input type="hidden" name="btn_acao" value="finalizar" />
        <input type="hidden" name="pedido" value="<?=$pedido?>" />
        <?php
        if ($catalogoPedido) {
        ?>
            <button type="submit" class="btn btn-success btn-large"><i class='fa fa-check'></i> Finalizar Pedido</button>
        <?php
        } else {
        ?>
            <input type="submit" value="Finalizar Pedido" class='btn btn-success' />
        <?php
        }

        if($login_fabrica == 160 or $replica_einhell){
            echo "<input type='hidden' name='resposta_intencao' value='' id='resposta_intencao'>";
        }
	?>
    </form>
<?php
}

if (in_array($login_fabrica, array(163))) {
    for ($i = 0; $i < $fabrica_qtde_anexos; $i++) {
    ?>
        <form name="form_anexo" method="post" action="cadastro_pedido.php" enctype="multipart/form-data" style="display: none;" >
            <input type="file" name="anexo_upload_<?=$i?>" value="" />

            <input type="hidden" name="ajax_anexo_upload" value="t" />
            <input type="hidden" name="anexo_posicao" value="<?=$i?>" />
            <input type="hidden" name="anexo_chave" value="<?=$anexo_chave?>" />
        </form>
    <?php
    }
}
?>

<form id="form_upload_peca" name="form_upload_peca" enctype="multipart/form-data" method="post" stye="display: none;" >
    <input type="hidden" name="ajax_upload_peca" value="true" />
    <input type="hidden" id="upload_peca_tipo_pedido" name="tipo_pedido" value="" />
    <input type="hidden" id="upload_peca_posto" name="posto" value="<?=$login_posto?>" />
    <input type="hidden" id="upload_peca_tabela" name="tabela" value="" />
    <input type="hidden" id="upload_peca_marca" name="marca" value="" />
    <input type="hidden" name="qtde_item" id="qtde_item" value="<?=$qtde_item?>" />
    <input type="hidden" id="upload_posto_codigo" name="upload_posto_codigo" value="" />
</form>
<br />

<script type="text/javascript">
    $(function(){
        <?php if (in_array($login_fabrica, [35])) { ?>
            $('#total_pecas').val(0);
            $("[name^=sub_total_]").each(function(){
                var sub = $(this).val();
                var c_sub = sub.replace(",", ".");
                var tot = $('#total_pecas').val();
                var c_tot = tot.replace(",", ".");
                var new_tot = parseFloat(c_sub) + parseFloat(c_tot);
                $('#total_pecas').val(number_format(new_tot, 2, ",", "."));
            });
        <?php } ?>

        <?php if ($login_fabrica == 183){ ?>
            $(document).on("click", "#buscar_nota_fiscal", function (){
                let nota_fiscal = $("#numero_nf").val();
                let linha_posto_pedido = $("#linha_posto_pedido").val();
                let url = "";

                if (linha_posto_pedido.trim() != "" && linha_posto_pedido != undefined){
                    url = "lista_produto_nota_fiscal.php?nota_fiscal="+nota_fiscal+"&linha_posto_pedido="+linha_posto_pedido;
                }else{
                    url = "lista_produto_nota_fiscal.php?nota_fiscal="+nota_fiscal;
                }

                if (nota_fiscal == "" || nota_fiscal == undefined){
                    alert("<?php echo traduz('informe a nota fiscal para realizar a pesquisa');?>");     
                    return false;
                }

                if (typeof nota_fiscal != "undefined" && nota_fiscal.length > 0) {
                    Shadowbox.open({
                        content: url,
                        player: "iframe",
                        height: 900,
                        width: 1500,
                        options: {
                            // onClose: function() {
                            //     $("select[name^=produto_pecas], select[name^=subproduto_pecas]").css({ visibility: "visible" });
                            // }
                        }
                    });
                } else {
                    alert("<?php echo traduz('selecione.um.produto.para.pesquisar.sua.lista.basica');?>");
                }

            });
        <?php } ?>
    });

    <?php if ($login_fabrica == 183){ ?>
        function retorna_dados_nota_fiscal (dados){
            $("#id_posto_pedido").val(dados.posto);
            $("#produto_referencia").val(dados.referencia);
            $("#produto_descricao").val(dados.descricao);
            $("#linha_posto_pedido").val(dados.linha);
            $("#codigo_posto_pedido").val(dados.codigo_posto);
            $("#nota_fiscal_posto_pedido").val(dados.nota_fiscal);
            $("input[name='codigo-info-posto']").val(dados.codigo_posto);
            $("input[name='nome_info_posto']").val(dados.nome);
            $("#row-info-posto").show();
        }
    <?php } ?>
</script>
<?php  include "rodape.php"; ?>
