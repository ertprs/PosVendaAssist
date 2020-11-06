<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "call_center";
include 'autentica_admin.php';
include 'funcoes.php';
include_once '../class/communicator.class.php';
include_once '../email_pedido.php';
include_once __DIR__ . '/../class/AuditorLog.php';

$distrib_posto_pedido_parcial = in_array($login_fabrica, array(81,114,122,123,125));

//VARIAVEL PARA CONTROLAR QUEM IRÁ ATUALIZAR A TABELA DE PRECOS VIA FUNCAO finaliza_pedido()
$vet_sem_tabela = array(101);//HD 677353

use Posvenda\Pedido;

if ($login_fabrica == 1) {
    header ("Location: pedido_cadastro_blackedecker.php");
    exit;
}

if ($login_fabrica == 93) {
    header ("Location: pedido_cadastro_blacktest.php");
    exit;
}

if ($_POST["ajax_calcula_impostos"]) {
    $filialImp      = $_POST["filial"];
    $condicaoImp    = $_POST["condicao"];
    $tipo_pedido    = $_POST["tipo_pedido"];
    $cnpjImp        = preg_replace("/[^0-9]/", "", $_POST["cnpj"]);
    $itensImp       = json_decode($_POST["itens"], true);
    $itensArray     = [];

    foreach ($itensImp as $key => $value) {
        $itensArray[$key]["codigo"] = $value[1];
        $itensArray[$key]["unidademedida"] = (empty($value[3])) ? 'PC' : $value[3];
        $itensArray[$key]["quantidade"] = (int)(empty($value[2])) ? 1 : $value[2];
    }

    $resultImpostos = getImpostosPecas($filialImp, $cnpjImp, $condicaoImp, $itensArray, $tipo_pedido);

    if (empty($resultImpostos)) {
        echo json_encode(["success"=>"Sem Impostos !"]);
        exit();
    }

    $resultImpostos = json_decode($resultImpostos, true);

    if (isset($resultImpostos["erro"]) || isset($resultImpostos["message"]) || isset($resultImpostos["errorCode"])) {
        echo json_encode(["error"=>"Erro ao Buscar os Impostos !"]);
        exit();
    }

    $rows = [];

    foreach ($resultImpostos as $k => $v) {
        $ttlItem = 0;
        foreach ($itensImp as $key => $value) {
            if (trim($v["produto"]) == $value[1]) {
                $rows[$k]["posicao"] = $value[0];
                break;
            }
        }
        $rows[$k]["log"]         = json_encode($v);
        $rows[$k]["referencia"]  = trim($v["produto"]);
        if (count($v["impostos"]) > 0) {
            foreach ($v["impostos"] as $p => $imp) {
                if (trim($imp["descr"]) == "COF (APUR)") {
                    $rows[$k]["COF"] = (float) trim($imp["valor"]);
                } else if (trim($imp["descr"]) == "ICMS-ST") {
                    $rows[$k]["ST"] = (float) trim($imp["valor"]);
                } else if (trim($imp["descr"]) == "PIS (APUR)") {
                    $rows[$k]["PIS"] = (float) trim($imp["valor"]);
                } else {
                    $rows[$k][trim($imp["descr"])] = (float) trim($imp["valor"]);
                }
            }
        }
        $rows[$k]["precoUnit"]    = ((float) trim($v["valornota"]) / trim($v["qtd"]));
        $rows[$k]["precoUnitLiq"] = (float) trim($v["valorunit"]); // preço unitario com imposto
        $rows[$k]["valornota"]    = (float) trim($v["valornota"]);
    }

    echo json_encode($rows);
    exit();
}

if($login_fabrica == 136){

    $callcenter = $_GET["callcenter"];

    $sql_info_posto = "SELECT
                            tbl_posto_fabrica.posto,
                            tbl_posto.cnpj,
                            tbl_posto.nome
                        FROM tbl_posto
                        INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.fabrica = {$login_fabrica} AND tbl_posto_fabrica.posto = tbl_posto.posto
                        WHERE tbl_posto_fabrica.posto = 371814";
    $res_info_posto = pg_query($con, $sql_info_posto);

    if(pg_num_rows($res_info_posto)){

        $dados["posto"] = pg_fetch_result($res_info_posto, 0, "posto");
        $cnpj = pg_fetch_result($res_info_posto, 0, "cnpj");
        $nome = pg_fetch_result($res_info_posto, 0, "nome");

    }
    if (!empty($callcenter)) {
        $tipo_pedido = 273;
        $tabela = 750;
        $condicao = 1956;
        $tipo_frete = 'CIF';

        $sql_p = "SELECT referencia, descricao
                    FROM tbl_hd_chamado_extra
                        JOIN tbl_produto USING(produto)
                    WHERE hd_chamado = {$callcenter}
                        AND tbl_produto.fabrica_i = {$login_fabrica};";
        $res_p = pg_query($con,$sql_p);
        if (pg_num_rows($res_p) > 0) {
            $produto_referencia_lupa = pg_fetch_result($res_p, 0, referencia);
            $produto_nome_lupa = pg_fetch_result($res_p, 0, descricao);
        }
    }

}

if ($login_fabrica == 35) {
    $callcenter = filter_input(INPUT_GET,'callcenter');

    $sqlInterno = "
        SELECT  tbl_posto.posto,
                tbl_posto.cnpj,
                tbl_posto.nome
        FROM    tbl_posto
        JOIN    tbl_posto_fabrica USING(posto)
        JOIN    tbl_fabrica ON  tbl_fabrica.posto_fabrica = tbl_posto.posto
                            AND tbl_fabrica.fabrica = tbl_posto_fabrica.fabrica
        WHERE   tbl_fabrica.fabrica = $login_fabrica
    ";
    $resInterno = pg_query($con,$sqlInterno);

    $posto          = pg_fetch_result($resInterno,0,posto);
    $cnpj           = pg_fetch_result($resInterno,0,cnpj);
    $nome           = pg_fetch_result($resInterno,0,nome);
    $pedido_cliente = $callcenter;

    $retira_lupa = true;
}
 
if ($telaPedido0315) {
    if(strlen($_REQUEST['pedido'])){
        $pedido = $_REQUEST['pedido'];
        header("Location: cadastro_pedido.php?pedido=$pedido");
        exit;
    }
    if(strlen($_REQUEST['callcenter'])){
        $callcenter = $_REQUEST['callcenter'];
        header("Location: cadastro_pedido.php?callcenter=$callcenter");
    }else{
        header("Location: cadastro_pedido.php");
    }
    exit;
}

/**INICIO - Funções para o HD-2017979 da Esmaltec */
require_once('../includes2/pedidosEsmaltec_verificaOrigem.php');
/**FIM - Funções para o HD-2017979 da Esmaltec */

/**
* - RESPOSTA DO AJAX DE OBSERVAÇÃO - HD 1232146
*/
if($login_fabrica == 101){
    if(strlen($_POST['metodo']) == 0){
        if( strlen($_POST['pedido']) > 0 &&
            strlen($_POST['admin'])  > 0 &&
            strlen($_POST['obs'])    > 0 &&
            strlen($_POST['numero']) > 0){

            $ajax_pedido    = $_POST['pedido'];
            $ajax_admin     = $_POST['admin'];
            $ajax_obs       = $_POST['obs'];
            $ajax_numero    = $_POST['numero'];

            $sql = "SELECT  tbl_admin.nome_completo,
                            to_char(now(),'DD/MM/YYYY HH24:MM:SS') AS data
                    FROM    tbl_admin
                    WHERE   tbl_admin.admin = $ajax_admin
            ";
            $res = pg_query($con,$sql);
            $nome_completo = htmlentities(pg_fetch_result($res,0,nome_completo));
?>
<tr id="<?=$ajax_numero?>" width="100%">
    <td id="data"><?=pg_fetch_result($res,0,data)?></td>
    <td id="obs" width="50%"><?=$ajax_obs?></td>
    <td id="nome"><?=$nome_completo?></td>
    <td id="sair"><a href="javascript:void(0)" style="color: #11F !important;" onclick="delObs('<?=$ajax_numero?>')"> X </a></td>
</tr>
<?
            exit;
        }
    }else{
        if( strlen($_POST['pedido']) > 0 &&
            strlen($_POST['obs'])    > 0){

            $ajax_pedido    = $_POST['pedido'];
            $ajax_obs       = trim(htmlentities($_POST['obs'],ENT_QUOTES,'UTF-8'));

            $sql = "SELECT fn_atualiza_obs_pedido({$ajax_pedido},{$login_fabrica},'{$ajax_obs}')";
            #echo $sql;
            $res = pg_query($con,$sql);
            exit;
        }else{
            echo "NÃO VEIO NADA";
            exit;
        }
    }
}

if($_GET['ajax_tabela']){
    $cnpj = $_GET['cnpj'];
    $tipo_pedido = $_GET['tipo_pedido'];

    if($tipo_pedido == 198){
        $campo = " tbl_posto_linha.tabela ";
    }else{
        $campo = " tbl_posto_linha.tabela_bonificacao ";
    }

    $cnpj  = preg_replace('/\D/', '', $cnpj);

    $sql = "SELECT tbl_tabela.tabela, tbl_tabela.sigla_tabela, tbl_tabela.descricao
            FROM tbl_posto_linha
            JOIN tbl_posto ON tbl_posto_linha.posto = tbl_posto.posto
            JOIN tbl_linha ON tbl_posto_linha.linha = tbl_linha.linha
            JOIN tbl_tabela ON $campo = tbl_tabela.tabela
            AND tbl_tabela.fabrica = $login_fabrica
            AND tbl_linha.fabrica = $login_fabrica
            WHERE  tbl_posto.cnpj = '$cnpj'";
    $res = pg_query($con,$sql);

    if(pg_num_rows($res) > 0){
        $tabela         = pg_fetch_result($res, 0, 'tabela');
        $sigla_tabela   = pg_fetch_result($res, 0, 'sigla_tabela');
        $descricao      = pg_fetch_result($res, 0, 'descricao');

        $option = "<option value='{$tabela}'>{$sigla_tabela} - {$descricao}</option>";

    }else{
        $option = "<option value=''>Tabela não encontrada</option>";
    }

    echo $option;

    exit;
}

if($_POST['ajax'] == "sim"){
    /**
    *   Verifica o estado e a região do estado onde o posto se encontra
    */
    $postoCnpj = $_POST['posto'];
    $sqlConfere = "
        SELECT  tbl_posto.capital_interior,
                tbl_posto_fabrica.contato_estado as estado
        FROM    tbl_posto
		JOIN	tbl_posto_fabrica using(posto)
		WHERE   cnpj = '$postoCnpj'
		and		fabrica = $login_fabrica   ";
    $resConfere = pg_query($con,$sqlConfere);
    $posto_regiao = trim(pg_fetch_result($resConfere,0,capital_interior));
    $posto_estado = pg_fetch_result($resConfere,0,estado);

    /**
    *   Verifica as transportadoras que atendem a região do Posto
    */
    $sqlTrans = "
        SELECT  tbl_transportadora_padrao.transportadora_padrao,
                tbl_transportadora_padrao.transportadora
        FROM    tbl_transportadora_padrao
        WHERE   capital_interior    = '$posto_regiao'
        AND     estado              = '$posto_estado'
        AND     fabrica             = $login_fabrica
    ";
    $resTrans = pg_query($con,$sqlTrans);
    if(pg_num_rows($resTrans) > 0){
        $ajaxTransPadrao = pg_fetch_all($resTrans);
    }else{
        return false;
        exit;
    }
    $array_dados = array();
    foreach($_POST['linha'] as $dados){
        /**
        *   Para cada peça cadastrada no pedido, será multiplicada
        *   o peso com a quantidade pedida de peças
        */
        $sqlConta = "
            SELECT  (peso * ".$_POST["qtde"][$dados].") AS peso_mult
            FROM    tbl_peca
            WHERE   referencia = '".$_POST["pecas"][$dados]."'";
        $resConta = pg_query($con,$sqlConta);
        $array_dados[$_POST["pecas"][$dados]] = pg_fetch_result($resConta,0,peso_mult);
    }
    $soma_pecas = array_sum($array_dados);
    foreach($ajaxTransPadrao as $padrao){
        /**
        *   Para cada Transportadora, será feita a seleção
        *   do frete para o peso total do pedido
        */
        $trans_padrao   = $padrao['transportadora_padrao'];
        $trans          = $padrao['transportadora'];
        $sqlProcura = "
            SELECT  tbl_transportadora_valor.valor_kg   ,
                    tbl_transportadora_valor.seguro     ,
                    tbl_transportadora_valor.gris
            FROM    tbl_transportadora_valor
            WHERE   tbl_transportadora_valor.transportadora_padrao  = $trans_padrao
            AND     tbl_transportadora_valor.fabrica                = $login_fabrica
            AND     (
                        tbl_transportadora_valor.kg_inicial < $soma_pecas
                    AND tbl_transportadora_valor.kg_final > $soma_pecas
                    )
        ";
        $resProcura = pg_query($con,$sqlProcura);
        if(pg_num_rows($resProcura) > 0){
            $valor_kg[$trans]   = pg_fetch_result($resProcura,0,valor_kg);
            $seguro[$trans]     = pg_fetch_result($resProcura,0,seguro);
            $gris[$trans]       = pg_fetch_result($resProcura,0,gris);
        }else{
            /**
            *   Se a verificação acima não achar nenhuma faixa,
            *   vai pegar o valor excedente de frete, multiplicar com o peso das peças
            *   e somar com a faixa de frete mais pesada
            */
            $sqlPesado = "
                SELECT  max(tbl_transportadora_valor.kg_final) AS kg_maximo ,
                        tbl_transportadora_valor.valor_acima_kg_final       ,
                        tbl_transportadora_valor.valor_kg                   ,
                        tbl_transportadora_valor.seguro                     ,
                        tbl_transportadora_valor.gris
                FROM    tbl_transportadora_valor
                WHERE   transportadora_padrao               = $trans_padrao
                AND     tbl_transportadora_valor.fabrica    = $login_fabrica
          GROUP BY      valor_kg,
                        valor_acima_kg_final,
                        seguro,
                        gris
            ";
            $res_pesado = pg_query($con,$sqlPesado);
            if(pg_num_rows($res_pesado) > 0){
                $aux = pg_fetch_result($res_pesado,0,kg_maximo);
                if($aux < $soma_pecas){
                    $excedente          = $soma_pecas - $aux;
                    $valor_acima        = pg_fetch_result($res_pesado,0,valor_acima_kg_final);
                    $valor_base         = pg_fetch_result($res_pesado,0,valor_kg);
                    $total_frete        = $valor_base + ($valor_acima * $excedente);
                    $valor_kg[$trans]   = $total_frete;
                    $seguro[$trans]     = pg_fetch_result($res_pesado,0,seguro);
                    $gris[$trans]       = pg_fetch_result($res_pesado,0,gris);
                }
            }
        }
    }

    if(count($valor_kg) > 0){
        if(count($valor_kg) > 1){
            /**
            *   Se houver mais de uma transportadora,
            *   será feita a ordenação do menor valor
            */
            asort($valor_kg);
            $trans_valor = array_slice($valor_kg,0,1,TRUE);
            $passaJson[] = array("posto_estado"=> $posto_estado, "trans"=>key($trans_valor),"valor" => (float)current($trans_valor),"seguro" => (float)$seguro[key($trans_valor)],"gris" => (float)$gris[key($trans_valor)]);
            $mais_barato = json_encode($passaJson);
        }else{
            $passaJson[] = array("posto_estado"=> $posto_estado,"trans"=>key($valor_kg),"valor"=>(float)current($valor_kg),"seguro" => (float)$seguro[key($valor_kg)],"gris" => (float)$gris[key($valor_kg)]);
            $mais_barato = json_encode($passaJson);
        }
    }

    echo $mais_barato;
    exit;
}
if (isset($_POST["busca_condicao_pagamento"])){
        
    $valor = $_POST['valor'];

    if(empty($valor)){
        die(json_encode(["erro" => "Parâmetro valor não informado"]));
    }else{
        $valor = moneyDb($valor);
    }

    $grupos = buscaGruposCondicaoPagamento();
    $limites_max = array("A" => 0, "B" => 0, "C" => 0);
    $condicoes = array("A" => [], "B" => [], "C" => []);

    // Recupera os ids das condições
    foreach(['A', 'B', 'C'] as $grp){

        foreach($grupos[$grp] as $grupo){

            // Libera todas as condições que tenham limite mínimo menor/igual ao valor recebido
            if($grupo['limite_minimo'] <= $valor){

                // Adiciona o id da condição 
                array_push($condicoes[$grp],$grupo["condicao"]);
            }

            // Atribui o limite máximo para cada grupo geral(A, B, C)
            if($grupo['limite_maximo'] > $limites_max[$grp]){
                $limites_max[$grp] = $grupo['limite_maximo'];
            }  
        }
    }

    $prepared_data = [];
    $i = 0;
    foreach(['A', 'B', 'C'] as $grupo){

        $condicoes_grupo = implode(",", $condicoes[$grupo]);
       
        $sql = "SELECT tbl_condicao.condicao, tbl_condicao.descricao 
                FROM tbl_condicao 
                WHERE tbl_condicao.fabrica = $login_fabrica 
                  AND tbl_condicao.visivel = 'TRUE'
                  AND campos_adicionais->>'grupo' = '$grupo'
                AND condicao IN ($condicoes_grupo)
                ORDER BY descricao";

        $qry = pg_query($con, $sql);

        for ($j = 0; $j < pg_num_rows($qry); $j++) {     
            $obj = new stdClass();
            $obj->condicao = pg_fetch_result($qry,$j,"condicao");
            $obj->descricao = utf8_encode(pg_fetch_result($qry,$j,"descricao"));
            $preparedData[$i] = $obj; $i++;
        }
    }

    die(json_encode($preparedData));
} 

if(isset($_POST['busca_todas_condicoes'])){

    $sql = "SELECT * FROM tbl_condicao WHERE fabrica = $login_fabrica $condicao1 ORDER BY lpad(trim(tbl_condicao.codigo_condicao)::text,10,'0')";

    $qry = pg_query($con,$sql);

    $preparedData = [];
    for ($i = 0; $i < pg_num_rows($qry); $i++) {     
       $obj = new stdClass();
       $obj->condicao = pg_fetch_result($qry,$i,"condicao");
       $obj->descricao = utf8_encode(pg_fetch_result($qry,$i,"descricao"));
       $preparedData[$i] = $obj;
    }

    die(json_encode($preparedData));
}

$btn_acao = trim(strtolower($_POST['btn_acao']));
$msg_erro = "";

if (strlen($_GET['pedido']) > 0) {

    $pedido = trim($_GET['pedido']);

    if (in_array($login_fabrica,array(94,108,111))) {

        $sql = "SELECT tbl_tipo_pedido.descricao
                    FROM tbl_pedido
                    JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido AND tbl_tipo_pedido.fabrica = $login_fabrica
                    WHERE tbl_pedido.pedido = $pedido
                    AND tbl_tipo_pedido.descricao ilike'%garantia%'";

        $res = pg_query($con,$sql);

        if (pg_num_rows($res) > 0) {
            header ("Location: pedido_admin_consulta.php?pedido=$pedido");
            exit;
        }

    }

}

if (strlen($_POST['pedido']) > 0) {
    $pedido = trim($_POST['pedido']);
}

// HD-6516657
/*if ($login_fabrica == 11 or $login_fabrica == 172) {#HD 273876
    #HD 318638
    header ("Location: pedido_cadastro_altera.php?pedido=$pedido");
}*/

//HD-900300
$qtde_itens_totais = (empty($_POST['qtde_item'])) ? 15 : $_POST['qtde_item'];

if ($btn_acao == "apagar") {
    if (!$pedido) {
        $msg_erro = "Informe o pedido a excluir!";
    } else {
        $res = pg_query ($con,"BEGIN TRANSACTION");
    }

    $sql = "UPDATE tbl_os_item SET pedido = null
            FROM tbl_pedido
            WHERE  tbl_os_item.pedido = tbl_pedido.pedido
            AND    tbl_os_item.pedido = $pedido
            AND    tbl_pedido.fabrica = $login_fabrica;";

        $res = @pg_query ($con,$sql);
        $msg_erro = pg_errormessage($con);

    if($callcenter != ""){
        $sql = "UPDATE tbl_hd_chamado_extra set pedido = null where hd_chamado = $callcenter";
        $res = @pg_query ($con,$sql);
        $msg_erro = pg_errormessage($con);
    }

    if (strlen($msg_erro) == 0) {

        $sql = "DELETE FROM tbl_os_item
                USING tbl_pedido
                WHERE  tbl_os_item.pedido = tbl_pedido.pedido
                AND    tbl_os_item.pedido = $pedido
                AND    tbl_pedido.fabrica = $login_fabrica;";

        #$res      = @pg_query($con,$sql);
        $msg_erro = @pg_errormessage($con);

    }

    if ($login_fabrica == 24) {

        if (strlen($msg_erro) == 0) {

            $sql      = "UPDATE tbl_pedido SET status_pedido = 14 WHERE tbl_pedido.pedido = $pedido AND tbl_pedido.fabrica = $login_fabrica";
            $res      = @pg_query($con,$sql);
            $msg_erro = @pg_errormessage($con);
        }
    }

    if ($login_fabrica == 7) {
        /*PARA A FILIZOLA É GERADO PEDIDO CLIENTE, E TEM QUE ZERAR OS, OS_ITEM E OS_REVENDA*/
        if (strlen($msg_erro) == 0) {

            $sql      = "UPDATE tbl_os_revenda SET pedido_cliente = null WHERE fabrica =$login_fabrica AND pedido_cliente = $pedido;";
            $res      = @pg_query ($con,$sql);
            $msg_erro = pg_errormessage($con);
        }

        if (strlen($msg_erro) == 0) {
            $sql      = "UPDATE tbl_os_item SET pedido_cliente = null WHERE pedido_cliente = $pedido;";
            $res      = @pg_query($con,$sql);
            $msg_erro = pg_errormessage($con);
        }

        if (strlen($msg_erro) == 0) {

            $sql      = "UPDATE tbl_os SET pedido_cliente = null WHERE fabrica = 7 and pedido_cliente = $pedido;";
            $res      = @pg_query($con,$sql);
            $msg_erro = pg_errormessage($con);
        }
    }

    if (strlen($msg_erro) == 0) {
        $sql      = "SELECT fn_pedido_delete($pedido, $login_fabrica, $login_admin)";
        $res      = @pg_query($con,$sql);
        $msg_erro = pg_errormessage($con);
    }

    if (strlen($msg_erro) == 0) {
        $res = pg_query($con, "COMMIT TRANSACTION");
        header("Location: $PHP_SELF");
        exit;
    } else {
        $res = pg_query ($con,"ROLLBACK TRANSACTION");
    }
}

if ($btn_acao == "gravar" && in_array($login_fabrica, array(138,142,143,145))) {
    try {
        $pedido = $_POST["pedido"];

        if (empty($pedido)) {
            $pedido = null;

            $pedidoClass = new Pedido($login_fabrica);
        } else {
            $pedidoClass = new Pedido($login_fabrica, $pedido);
        }

        $tipo_pedido = (empty($_POST["tipo_pedido"])) ? "Faturado" : $_POST["tipo_pedido"];

        $dados = array(
            "posto"                          => $_POST["posto_codigo"],
            "fabrica"                        => $login_fabrica,
            "condicao"                       => (empty($_POST["condicao"])) ? ($pedido == null) ? "null" : null : $_POST["condicao"],
            "pedido_cliente"                 => "{$_POST['pedido_cliente']}",
            "transportadora"                 => (empty($_POST["transportadora"])) ? ($pedido == null) ? "null" : null : $_POST["transportadora"],
            "tipo_pedido"                    => $tipo_pedido,
            "tabela"                         => $_POST["tabela"],
            "status_pedido"                  => 1,
            "tipo_frete"                     => ($pedido == null) ? "'{$_POST["tipo_frete"]}'" : "{$_POST["tipo_frete"]}",
            "validade"                       => "'{$_POST["validade"]}'",
            "entrega"                        => "'{$_POST["entrega"]}'",
            "obs"                            => "'{$_POST['observacao_pedido']}'",
            "atende_pedido_faturado_parcial" => ($_POST["parcial"] == "t") ? "true" : "false",
            "status_pedido"                  => 1
        );

        $pedidoClass->_model->getPDO()->beginTransaction();

        $pedidoClass->grava($dados, $pedido);

        $dadosItens = array();

        for ($i = 0; $i < $qtde_itens_totais; $i++) {
            if (empty($_POST["pedido_item_{$i}"]) && empty($_POST["peca_referencia_{$i}"])) {
                continue;
            }

            $dadosItens[] = array(
                "pedido_item"     => $_POST["item{$i}"],
                "peca_referencia" => $_POST["peca_referencia_{$i}"],
                "qtde"            => $_POST["qtde_{$i}"],
                "preco"           => str_replace(",", ".", str_replace(".", "", $_POST["preco_{$i}"])),
                "preco_base"      => str_replace(",", ".", str_replace(".", "", $_POST["preco_{$i}"])),
                "ipi"             => 0
            );
        }

        $pedidoClass->gravaItem($dadosItens);

        if (in_array($login_fabrica, array(138,142,143,145)) or $login_fabrica > 145) {
            $pedidoClass->verificaValorMinimo();
        }

        if (in_array($login_fabrica, array(138))) {
            $pedidoClass->auditoria();
        }

        $pedidoClass->_model->getPDO()->commit();

        if(!empty($pedido)){
            $id_posto = $_POST['posto_codigo'];
            $sql_posto = "SELECT 
                            tbl_posto_fabrica.contato_email as contato_email,
                            tbl_fabrica.nome as fabrica_nome,
                            tbl_posto.nome as posto_nome 
                        FROM tbl_posto_fabrica 
                        JOIN tbl_fabrica on (tbl_fabrica.fabrica = tbl_posto_fabrica.fabrica) 
                        JOIN tbl_posto on (tbl_posto.posto = tbl_posto_fabrica.posto) 
                        where tbl_posto_fabrica.fabrica = $login_fabrica and tbl_posto_fabrica.posto = $id_posto";

            $res_posto = pg_query($con, $sql_posto);

            $contato_email = pg_fetch_result($res_posto, 0, 'contato_email');
            $fabrica_nome = pg_fetch_result($res_posto, 0, 'fabrica_nome');
            $posto_nome = pg_fetch_result($res_posto, 0, 'posto_nome');

            $assunto       = "Pedido nº ".$pedido. " - ". $fabrica_nome;
            $corpo         = email_pedido($posto_nome, $fabrica_nome, $pedido, $cook_login, true, 'antiga');

            $mailTc = new TcComm($externalId);
            $res = $mailTc->sendMail(
                $contato_email,
                $assunto,
                utf8_encode($corpo),
                $externalEmail
            );
        }
        
        header("Location: pedido_finalizado.php?pedido={$pedidoClass->getPedido()}&loc=1&msg=Gravado com Sucesso!");
        exit;
    } catch(Exception $e) {
        $msg_erro = $e->getMessage();

        $pedidoClass->_model->getPDO()->rollBack();
    }
}

$auxiliar_callcenter = $callcenter;
if ($btn_acao == "gravar" && !in_array($login_fabrica, array(138,143))) {

    unset($msg_erro);
    $xtipo_pedido = "'Faturado'";
    if (!function_exists('checaCPF')) {
        function checaCPF ($cpf,$return_str = true) {
            global $con, $login_fabrica;// Para conectar com o banco...
            $cpf = preg_replace("/\D/","",$cpf);   // Limpa o CPF
            if (!$cpf or $cpf == '' or (strlen($cpf) != 11 and strlen($cpf) != 14)) false;

            if(strlen($cpf) > 0){
                $res_cpf = @pg_query($con,"SELECT fn_valida_cnpj_cpf('$cpf')");
                if ($res_cpf === false) {
                    return ($return_str) ? pg_last_error($con) : false;
                }
            }
            return ($return_str) ? $cpf : true;
        }
    }

    if(empty($_POST['forma_envio']) AND in_array($login_fabrica, array(94))) {
        $msg_erro .= "Por favor, informe a forma e envio!<br>";
    }

    if (strlen($_POST['tipo_pedido']) > 0) {
        $xtipo_pedido = "'". $_POST['tipo_pedido'] ."'";
    } else {
        $msg_erro = "Selecione o Tipo de Pedido";
        $xtipo_pedido = "null";
    }
    if (strlen($_POST['tipo_peca']) > 0) {
        $xtipo_peca = "'". $_POST['tipo_peca'] ."'";
    }

    if (strlen($_POST['condicao']) > 0) {
        $xcondicao = "'". $_POST['condicao'] ."'";
    } else {
        $xcondicao = "null";
    }

    if (strlen($_POST['promocao']) > 0) {
        $xpromocao = "'". $_POST['promocao'] ."'";
    } else {
        $xpromocao = "null";
    }

    if (strlen(trim($_POST['desconto'])) > 0) {
        $xdesconto = trim($_POST['desconto']);
        $xdesconto = str_replace(",",".",$xdesconto);
    } else {
        $xdesconto = "null";
    }

    if (strlen($_POST['tipo_frete']) > 0) {
        $xtipo_frete = "'". $_POST['tipo_frete'] ."'";
    } else {
        $xtipo_frete = "null";
    }

    if (strlen($_POST['valor_frete']) > 0) {
        $xvalor_frete =  $_POST['valor_frete'];
        $xvalor_frete = str_replace(",",".",$xvalor_frete);
    } else {
        $xvalor_frete = "0";
    }

    if (isset($_POST["valor_frete_hidden"]) && strlen(trim($_POST["valor_frete"])) == 0) {

        $valor_frete_hidden = $_POST["valor_frete_hidden"];
        $valor_frete_hidden = str_replace(".", "", $valor_frete_hidden);
        $valor_frete_hidden = str_replace(",", ".", $valor_frete_hidden);
        $xvalor_frete       = $valor_frete_hidden;

        if(strlen(trim($xvalor_frete))==0){
            $xvalor_frete = '0';
        }

    }

    if (strlen($_POST['linha']) > 0) {
        $xlinha = "'". $_POST['linha'] ."'";
    } else {
        $xlinha = "null";
    }

    if (strlen($_POST['pedido_cliente']) > 0) {
        $xpedido_cliente = "'". $_POST['pedido_cliente'] ."'";
    } else {
        $xpedido_cliente = "null";
    }

    if (strlen($_POST['validade']) > 0) {
        $xvalidade = "'". $_POST['validade'] ."'";
    } else {
        $xvalidade = "null";
    }

    if (strlen($_POST['entrega']) > 0) {
        $xentrega = "'". $_POST['entrega'] ."'";
    } else {
        $xentrega = "null";
    }

    if (strlen($_POST['tabela']) > 0) {
        $xtabela = "'". $_POST['tabela'] ."'";
    } else {
        $xtabela = "null";
    }

    if (strlen($_POST['transportadora']) > 0) {
        $xtransportadora = $_POST['transportadora'] ;
    } else {
        $xtransportadora = "null";
    }

    if (strlen($_POST['forma_envio']) > 0) {
        $xforma_envio = $_POST['forma_envio'] ;
    } else {
        $xforma_envio = "null";
    }


    if(strlen($_REQUEST['callcenter']) > 0){
        $callcenter = $_REQUEST['callcenter'];
    }else{
        $callcenter = "";
    }

    if(strlen($_POST['parcial']) == 0 AND $telecontrol_distrib){
	   $msg_erro = "Informe se o pedido será atendido parcialmente";
    }else{
	    $parcial           = ($_POST['parcial']=='t')?"true":"false";
    }

    if ($login_fabrica == 24 and $pedido) $pedido_reexportar = substr($_POST['reexportar'], 0, 1);

    if (strlen($_POST['cnpj']) >= 11) {
        $cnpj  = preg_replace('/\D/', '', $_POST['cnpj']);
        $xcnpj = "'". $cnpj ."'";
    } else {
        $xcnpj = 'null';
    }

    if (strlen($_POST['obs']) > 0) {
        $xobs = "'". $_POST['obs'] ."'";
        
        if (in_array($login_fabrica, [42])) {
            $query_obs = "SELECT obs FROM tbl_pedido WHERE fabrica = {$login_fabrica} AND pedido = {$pedido}";
            $res_obs = pg_query($con, $query_obs);

            $obs_decoded = utf8_encode(pg_fetch_result($res_obs, 0, 'obs'));
            $obs_decoded = json_decode($obs_decoded, true);

            $obs_decoded['observacao'] = utf8_encode($_POST['obs']);

            $xobs = "'" . json_encode($obs_decoded) . "'";
        }
    } else {
        $xobs = "null";
    }

    #1 401553
    if ($login_fabrica==42){
        if ( strlen($_POST['filial_posto'])>0 ){
            $filial_posto = $_POST['filial_posto'];
        }else{
            $msg_erro='Escolha uma Filial';
        }
    }

    if (strlen($_POST['referencia']) > 0) {

        $xreferencia = $_POST['referencia'] ;
        $xreferencia = str_replace (".","",$xreferencia);
        $xreferencia = str_replace ("-","",$xreferencia);
        $xreferencia = str_replace ("/","",$xreferencia);
        $xreferencia = str_replace (" ","",$xreferencia);
        $xreferencia = "'".$xreferencia."'";

        $sql = "SELECT produto FROM tbl_produto WHERE  referencia_pesquisa = $xreferencia";
        $res = pg_query($con,$sql);
        if (pg_num_rows($res) == 0) $produto = pg_fetch_result($res,0,0);

    } else {
        $xreferencia = "null";
    }

    if ($xcnpj <> "null") {
        $sql = "SELECT tbl_posto.posto, credenciamento
                FROM   tbl_posto
                JOIN   tbl_posto_fabrica USING (posto)
                WHERE  tbl_posto.cnpj            = $xcnpj
                AND    tbl_posto_fabrica.fabrica = $login_fabrica;";

        $res = pg_query($con,$sql);
        if (pg_num_rows($res) == 0) {

            $sql = "SELECT tbl_posto.posto,credenciamento
                    FROM   tbl_posto
                    JOIN   tbl_posto_fabrica USING (posto)
                    WHERE  tbl_posto_fabrica.codigo_posto = $xcnpj
                    AND    tbl_posto_fabrica.fabrica      = $login_fabrica;";

            $res = pg_query($con,$sql);

            if (pg_num_rows($res) == 0) {

                if ($login_fabrica == 7) {
                    $sql = "SELECT tbl_posto.posto,' ' as credenciamento
                            FROM   tbl_posto
                            JOIN   tbl_posto_consumidor USING (posto)
                            WHERE  tbl_posto.cnpj               = $xcnpj
                            AND    tbl_posto_consumidor.fabrica = $login_fabrica;";

                    $res = pg_query($con,$sql);

                    if (pg_num_rows($res) == 0) {
                        $msg_erro = "CNPJ ou Código não cadastrado";
                    }

                } else {
                    $msg_erro = "CNPJ ou Código não cadastrado";
                }
            }
        }

        $posto = @pg_fetch_result($res,0,0);
        $credenciamento = @pg_fetch_result($res,0,1);
    } else {
        $msg_erro = "CNPJ ou Código não informados";
    }

    if($credenciamento=='DESCREDENCIADO') {
        $msg_erro = "POSTO DESCREDENCIADO";
    }

    if ($xtipo_pedido <> "null") {
        $sql = "SELECT tipo_pedido
                FROM   tbl_tipo_pedido
                WHERE  tipo_pedido = $xtipo_pedido
                AND    fabrica     = $login_fabrica";

        $res = pg_query($con,$sql);

        if (pg_num_rows($res) == 0) $msg_erro = "Tipo de Pedido não cadastrado";

    } else {
        $msg_erro = "Tipo de Pedido não informado.";
    }

    /* FILIZOLA: a tabela de preço é de acordo com a Condição de Pagamento - HD 40324 */
    if ($login_fabrica == 7) {
        if (strlen($xcondicao) > 0 AND $xcondicao != 'null') {

            $sql = "SELECT tbl_condicao.condicao, tbl_condicao.tabela
                    FROM   tbl_condicao
                    WHERE  tbl_condicao.condicao = $xcondicao
                    AND    tbl_condicao.fabrica  = $login_fabrica";

            $res = pg_query ($con,$sql);

            if (pg_num_rows ($res) >0) {
                $xtabela   = pg_fetch_result($res, 0, 'tabela');
            }

        }

    }
    if(in_array($login_fabrica,array(88,120,201))){
        $frete_calculado    = $_POST['frete_calculado'];
        $total_pecas        = $_POST['total_pecas'];
        $total_pecas        = str_replace(",",".",$total_pecas);

        $sqlEstadoPosto = "SELECT contato_estado FROM tbl_posto_fabrica 
                            WHERE fabrica = $login_fabrica and posto = $posto";
        $resEstadoPosto = pg_query($con, $sqlEstadoPosto);
        if(pg_num_rows($resEstadoPosto)){
            $contato_estado = pg_fetch_result($resEstadoPosto, 0, "contato_estado");
        }

        if($login_fabrica == 88 ){

            if(strlen($frete_calculado) == 0){
                if($total_pecas < 1000 ){
                    $msg_erro .= "O pedido não foi gravado por falta de cálculo do frete";
                }elseif(in_array($contato_estado, ["MA", "PI", "CE", "RN", "PE", "PB", "SE", "AL"])){
                    $msg_erro .= "O pedido não foi gravado por falta de cálculo do frete";
                }                         
            }           
        }else{
            if(strlen($frete_calculado) == 0){
                $msg_erro .= "O pedido não foi gravado por falta de cálculo do frete";
            }
        }

        
    }
    if ($xcondicao <> "null") {

        $sql = "SELECT tbl_condicao.condicao
                FROM   tbl_condicao
                WHERE  tbl_condicao.condicao = $xcondicao
                AND    tbl_condicao.fabrica  = $login_fabrica";

        $res = pg_query($con, $sql);

        if (pg_num_rows($res) == 0) $msg_erro = "Condição de Pagamento não cadastrada";

    } else {

        $msg_erro = "Condição de Pagamento não informada";

    }

    if ($xtabela <> "null") {

        $sql = "SELECT tbl_tabela.tabela
                FROM   tbl_tabela
                WHERE  tbl_tabela.tabela  = $xtabela
                AND    tbl_tabela.fabrica = $login_fabrica
                AND    tbl_tabela.ativa   IS TRUE ;";

        $res = pg_query($con,$sql);
        if (pg_num_rows($res) == 0) {
            $msg_erro = "Tabela de Preços não cadastrada";
        }

    } else {

        if (!in_array($login_fabrica, $vet_sem_tabela)) $msg_erro = "Tabela de Preços não informada";

    }

    if (in_array($login_fabrica, array(139))) {
        $distribuidor = 0;
        $select_distribuidor = "SELECT tbl_posto_linha.distribuidor
                                FROM tbl_linha
                                INNER JOIN tbl_posto_linha USING(linha)
                                WHERE tbl_posto_linha.posto = $posto
                                AND tbl_linha.fabrica = $login_fabrica
                                AND tbl_posto_linha.distribuidor NOTNULL
                                LIMIT 1";
        $res_distribuidor = pg_query($con, $select_distribuidor);

        if (!pg_num_rows($res_distribuidor)) {
            $msg_erro = "Distribuidor não cadastrado";
        } else {
            $distribuidor = pg_fetch_result($res_distribuidor, 0, "distribuidor");
        }
    }

    if($login_fabrica == 94 AND strlen($msg_erro) == 0){

        if (!empty($_POST['forma_envio']) AND $_POST['forma_envio'] != 2) {
            $sqlEnv = "SELECT descricao FROM tbl_forma_envio WHERE forma_envio = {$_POST['forma_envio']} AND fabrica = {$login_fabrica};";
            $resEnv = pg_query($con,$sqlEnv);

            if (pg_num_rows($resEnv) > 0) {
                $descricaoEnv = pg_fetch_result($resEnv, 0, descricao);
                if ($descricaoEnv == "CORREIOS PAC") {
                    $insert_aprovacao_tipo = "aprovacao_tipo,";
                    $aprovacao_tipo = "'750',";
                    $update_aprovacao_tipo = "aprovacao_tipo = '750',";
                }elseif ($descricaoEnv == "CORREIOS SEDEX") {
                    $insert_aprovacao_tipo = "aprovacao_tipo,";
                    $aprovacao_tipo = "'159',";
                    $update_aprovacao_tipo = "aprovacao_tipo = '159',";
                }
            }
        }
    }

    if (strlen ($msg_erro) == 0) {
        $msg_erro = '';
        $garantia_antecipada = "f";

        if ($login_fabrica == 3 AND $tipo_pedido == "3") {
            $garantia_antecipada = "t";
        }

        if (strlen($pedido) == 0) {
            /*Inicia o AuditorLog Pedido */

            $objLog = new AuditorLog('insert');
            $objItem = new AuditorLog('insert');
            $tpAuditor = "insert";
        } else {
            /*Inicia o AuditorLog Pedido */
            $objLog = new AuditorLog();
            $objItem = new AuditorLog();
            $objLog->retornaDadosTabela('tbl_pedido', array('pedido'=>$pedido, 'fabrica'=>$login_fabrica) );
            
            $objItem->retornaDadosSelect("select peca,qtde,preco,status_pedido,qtde_faturada,qtde_cancelada,produto_locador,nota_fiscal_locador,data_nf_locador,qtde_faturada_distribuidor,preco_base,acrescimo_financeiro,acrescimo_tabela_base,icms,troca_produto,ipi,tabela,obs,servico,serie_locador,defeito,pedido_item_atendido,condicao,estoque,total_item,peca_alternativa from tbl_pedido_item where pedido = $pedido");
            $tpAuditor = "update";
        }

        

        $res = pg_query($con, "BEGIN TRANSACTION");

       if (strlen($pedido) == 0) {
            if (in_array($login_fabrica, array(139))) {
                $coluna_adicional["coluna"] = ", distribuidor";
                $coluna_adicional["valor"]  = ", $distribuidor";
            }

           #-------------- insere pedido ------------
            $sql = "INSERT INTO tbl_pedido (
                        posto         ,
                        fabrica       ,
                        condicao      ,
                        tabela        ,
                        admin         ,
                        tipo_pedido   ,
                        pedido_cliente,
                        validade      ,
                        entrega       ,
                        {$insert_aprovacao_tipo}
            ";
            if ($login_fabrica == 104) {
                $sql .= " pedido_acessorio    ,";
            }
            if ($login_fabrica != 101){
                $sql .= "   obs           ,";
            }

            $sql .= "
                        linha         ,
                        transportadora,
                        tipo_frete    ,
                        valor_frete   ,
                        garantia_antecipada,
                        promocao      ,";

            if ($login_fabrica == 40){
                $sql .= "   status_pedido  ,";
            }

            if (in_array($login_fabrica, [11, 172])) {
                $sql .= " status_pedido, distribuidor, exportado, ";  
            }
            /*if ($login_fabrica == 122)
            {
                $id_posto = $_POST['posto_codigo'];
                if($id_posto == 20682){
                    $sql .= "   status_pedido   ,";
                }
            }*/
            if ($login_fabrica == 42){
                $sql .= "   filial_posto  ,";
            }
            if ($telecontrol_distrib and strlen($callcenter)>0){
                $sql .= "   troca  ,";
            }

            $sql .= "
                        desconto      ,
                        forma_envio   ,
                        atende_pedido_faturado_parcial
                        {$coluna_adicional['coluna']}
                    ) VALUES (
                        $posto           ,
                        $login_fabrica   ,
                        $xcondicao       ,
                        $xtabela         ,
                        $login_admin     ,
                        $tipo_pedido     ,
                        $xpedido_cliente ,
                        $xvalidade       ,
                        $xentrega        ,
                        {$aprovacao_tipo}";
                        if ($login_fabrica == 104) {
                            $sql .= " $xtipo_peca    ,";
                        }

                        if ($login_fabrica != 101){
                            $sql .= "   $xobs            ,";
                        }

            $sql .= "
                        $xlinha          ,
                        $xtransportadora ,
                        $xtipo_frete     ,
                        $xvalor_frete    ,
                        '$garantia_antecipada',
                        $xpromocao         ,";

            if ($login_fabrica == 40){
                $sql .= "   2  ,";
            }

            if (in_array($login_fabrica, [11,172])) {
                $sql .= " 18, 4311, now(), ";
            }

            /*
            if ($login_fabrica == 122){
                $id_posto = $_POST['posto_codigo'];
                if($id_posto == 20682){
                    $sql .= "   18  ,";
                }
            }*/
            if ($login_fabrica == 42){
                $sql .= "   $filial_posto  ,";
            }
            if ($telecontrol_distrib and strlen($callcenter)>0){
                $sql .= "   true  ,";
            }

            $sql .= "
                        $xdesconto        ,
                        $xforma_envio     ,
                        '$parcial'
                        {$coluna_adicional['valor']}
                    ) RETURNING pedido";
        } else {

            $sql_exporta = '';

            if ($login_fabrica == 24) {

                $sql_admin  = "admin_alteracao= $login_admin     ";

                if ($pedido_reexportar == 't') { //MLG 01/12/2010 - HD 332453
                    $sql_exporta= "exportado       = NULL            ,
                        status_pedido  = 1               ,
                        exportar_novamente_admin = $login_admin,
                        exportar_novamente_data  = CURRENT_TIMESTAMP ,";
                }

            } else {
                $sql_admin  = "admin          = $login_admin     ";
            }

            $sql = "UPDATE tbl_pedido SET
                        posto          = $posto          ,
                        fabrica        = $login_fabrica  ,
                        condicao       = $xcondicao      ,
                        tabela         = $xtabela        ,
                        tipo_pedido    = $tipo_pedido    ,
                        pedido_cliente = $xpedido_cliente,
                        validade       = $xvalidade      ,
                        entrega        = $xentrega       ,
                        {$update_aprovacao_tipo}";

            if ($login_fabrica != 101){
                $sql .= "obs            = $xobs           ,";
            }

            $sql .= "
                        linha          = $xlinha         ,
                        transportadora = $xtransportadora,
                        tipo_frete     = $xtipo_frete    ,
                        valor_frete    = $xvalor_frete   ,
                        promocao       = $xpromocao      ,
                        desconto       = $xdesconto      ,
                        atende_pedido_faturado_parcial = '$parcial',";

            if ($login_fabrica == 42){
                $sql .= "   filial_posto   = $filial_posto   ,";
            }

            $sql .= "
                        $sql_exporta
                        $sql_admin
                    WHERE tbl_pedido.pedido  = $pedido
                    AND   tbl_pedido.fabrica = $login_fabrica";
        }

        $res           = @pg_query($con,$sql);

        if (strlen($msg_erro) == 0 and strlen($pedido) == 0) {
            $pedido_normal = pg_fetch_result($res, 0, 'pedido');
        }
        $msg_erro      = pg_errormessage($con);

        if (strlen($msg_erro) == 0 and strlen($pedido) == 0) {

            $res      = pg_query($con,"SELECT CURRVAL ('seq_pedido')");
            $pedido   = pg_fetch_result($res,0,0);
            $msg_erro = pg_errormessage($con);

        }

        if($callcenter != "" and $pedido != ""){

            if ($telecontrol_distrib || in_array($login_fabrica, [174])) {
                $sqlInteracao = "INSERT INTO tbl_hd_chamado_item(
                                        hd_chamado   ,
                                        data         ,
                                        comentario   ,
                                        admin        ,
                                        interno      , 
                                        status_item
                                ) values (
                                        $hd_chamado       ,
                                        current_timestamp ,
                                        '<strong>Gerado pedido para este atendimento</strong>',
                                        $login_admin      ,
                                        't'               ,
                                        'Pedido gerado'
                                )";
            }

            $sql = "UPDATE tbl_hd_chamado_extra set pedido = $pedido where hd_chamado = $callcenter";
            $res = pg_query($sql);
            $msg_erro = pg_errormessage($con);

            $sql = "UPDATE tbl_pedido set origem_cliente = true where pedido = $pedido ";
            $res = pg_query($sql);
            $msg_erro = pg_errormessage($con);
        }

        $qtde_item = $_POST['qtde_item'];

        //HD-900300

/*
    P R  R G M R T M Q E P N A

    1º

    + 100 Linhas

  */

        for ($i = 0 ; $i < $qtde_item ; $i++) {

            $peca_referencia = $_POST['peca_referencia_' . $i];

            if(!empty($peca_referencia)){
                for($y = 0 ; $y < $qtde_item ; $y++){
                    $peca_referencia_y = $_POST['peca_referencia_' . $y];
                    if($peca_referencia == $peca_referencia_y && $i != $y){
                        $sqlVerf = "SELECT pedido, peca FROM tbl_pedido_cancelado
                                JOIN tbl_peca USING(peca) WHERE tbl_peca.referencia = '".$peca_referencia_y."'
                                AND tbl_pedido_cancelado.fabrica = ".$login_fabrica." AND tbl_pedido_cancelado.pedido = ".$pedido.";";
                        $resVerf = pg_query($con,$sqlVerf);
                        $pedidoVerf = pg_fetch_result($resVerf, 0, pedido);
                        if(!empty($pedidoVerf)){
                            $msg_erro = "Você não pode colocar uma peça cancelada";
                        }else{
                            $msg_erro = "Peça ".$peca_referencia_y." já consta no pedido";
                        }
                    }
                }
            }
            if($login_fabrica == 104 and strlen($peca_referencia) > 0 ){

                $sqlPeca = "SELECT acessorio FROM tbl_peca WHERE referencia = '$peca_referencia' AND fabrica = $login_fabrica";
                $resPeca = pg_query($con,$sqlPeca);
                $peca_acessorio = pg_fetch_result($resPeca,0,acessorio);

                $sqlPedido = "SELECT pedido_acessorio FROM tbl_pedido WHERE pedido = $pedido";
                $resPedido = pg_query($con,$sqlPedido);
                $tipo_pecas_pedido = pg_fetch_result($resPedido,0,pedido_acessorio);
                //if(empty($peca_acessorio)) $peca_acessorio = 'f';

                if($peca_acessorio != $tipo_pecas_pedido ){
                    if($tipo_pecas_pedido == "f"){
                        // peça
                        $tipo_pedido_acessorio = "PEÇAS";
                    }else{
                        //acessorios
                        $tipo_pedido_acessorio = "ACESSORIOS";
                    }
                    $msg_erro = "Item '$peca_referencia' não pode ser lançada no pedido com o tipo de peças $tipo_pedido_acessorio";
                    break;
                }
            }
        }

        if (strlen($msg_erro) == 0) {

            $qtde_item = $_POST['qtde_item'];

            $nacional  = 0;
            $importado = 0;
            $array_pecas_monitoradas = [];

            for ($i = 0 ; $i < $qtde_item ; $i++) {

                $novo            = $_POST["novo".$i];
                $item            = $_POST["item".$i];
                $peca_referencia = $_POST['peca_referencia_' . $i];
                $qtde            = $_POST['qtde_'            . $i];
                $logItem         = $_POST['logItem_'         . $i];
                $precoUnitLiq    = $_POST['precoUnitLiq_'         . $i];
                $precoUnitImp    = $_POST['precoUnitImp_'         . $i];

                if(in_array($login_fabrica, array(11, 42, 81, 114, 119, 122, 123, 125, 128, 136,140, 172))){
                  $preco = $_POST['preco_'.$i];
                  $preco = str_replace(',','.',$preco);
                }

				if($login_fabrica == 140 and $preco == 0 and !empty($peca_referencia)) {
					$msg_erro = "Não pode gravar peça com preço zerado ";
					break;
				}
                if (empty($peca_referencia) AND $novo != 'f'){
                    continue;
                }

                if ((empty($qtde) || $qtde == 0) && !empty($peca_referencia)) {
                    $msg_erro = "Não pode gravar peça com quantidade inferior a 1 ";
                    break;
                }

                for($x = $i ; $x < $qtde_item ; $x++){
                    $verf_data = $_POST['peca_referencia_' . $x];

                    if(!empty($verf_data)){
                           $i = $x;
                           $item_verf = TRUE;
                           break;
                    }
                }


                if($login_fabrica == 50){
                    if($i == 0){
                    $devolucao_obrigatoria = "nao";
                    $sqlPecaDev = "SELECT devolucao_obrigatoria FROM tbl_peca WHERE referencia = '$peca_referencia' AND fabrica = $login_fabrica";
                    $resPecaDev = pg_query($con,$sqlPecaDev);
                    if(pg_num_rows($resPecaDev) > 0){
                        $devolucao_obrigatoria = (pg_fetch_result($resPecaDev,0,'devolucao_obrigatoria') == 't') ? "sim" : "nao";
                        $tipo_devolucao = (pg_fetch_result($resPecaDev,0,'devolucao_obrigatoria') == 'f') ? "é de devolução obrigatória" : "não é de devolução obrigatória";
                    }
                    }else{
                    $sqlPecaDev = "SELECT devolucao_obrigatoria FROM tbl_peca WHERE referencia = '$peca_referencia' AND fabrica = $login_fabrica";
                    $resPecaDev = pg_query($con,$sqlPecaDev);
                    if(pg_num_rows($resPecaDev) > 0){
                        $verifica_devolucao_obrigatoria = (pg_fetch_result($resPecaDev,0,'devolucao_obrigatoria') == 't') ? "sim" : "nao";
                        if($verifica_devolucao_obrigatoria != $devolucao_obrigatoria){
                        $msg_erro = "A peça {$peca_referencia} {$tipo_devolucao} e deve ser lançanda em um outro pedido";
                        $linha_erro = $i;
                        break;
                        }
                    }
                    }
                }

                if (strlen($qtde) == 0 OR strlen($peca_referencia) == 0 && empty($msg_erro) ) {

                    if (strlen($item) > 0 AND $novo == 'f') {
                        $sql      = "DELETE FROM tbl_pedido_item WHERE pedido = $pedido AND pedido_item = $item";
                        if(!empty($pedido) && !empty($item)){
                            $res      = @pg_query($con,$sql);
                            $msg_erro = pg_errormessage($con);
                        }
                    }
                }

                if ($login_fabrica == 85){
                    if (strlen($qtde)==0 or $qtde == 0){
                        $msg_erro = "Informe a quantidade da peça $peca_referencia";
                    }
                }

                if (strlen($msg_erro) == 0) {
                    if (strlen ($peca_referencia) > 0) {
                        $peca_referencia = strtoupper($peca_referencia);
                        $peca_referencia = str_replace("-","",$peca_referencia);
                        $peca_referencia = str_replace(".","",$peca_referencia);
                        $peca_referencia = str_replace("/","",$peca_referencia);
                        $peca_referencia = str_replace(" ","",$peca_referencia);

                        $sql = "SELECT  tbl_peca.peca,";

                        #HD 363162 - Pedidos para linha AUTOMATICA (545) colormaq
                        if ($login_fabrica == 50) {

                            $sql .= "(SELECT DISTINCT tbl_produto.linha
                                    FROM tbl_lista_basica
                                    JOIN tbl_produto USING(produto)
                                    WHERE tbl_lista_basica.peca = tbl_peca.peca
                                    ORDER BY linha
                                    limit 1
                                ) AS linha,";

                        }
                        $sql .= "tbl_peca.origem,
                            tbl_peca.ativo
                                FROM    tbl_peca
                                WHERE   (tbl_peca.referencia_pesquisa = '$peca_referencia' or tbl_peca.referencia = '$peca_referencia')
                                AND     tbl_peca.fabrica    = $login_fabrica ";

                        $res = pg_query ($con,$sql);
                        if (pg_num_rows($res) == 0) {
                            $msg_erro   = "Peça $peca_referencia não cadastrada";
                            $linha_erro = $i;
/*
A A P O R A E  U E S R

2º

*/
                        } else {
                            $peca   = pg_fetch_result($res,0,peca);
                            $origem = trim(pg_fetch_result($res,0,origem));
                            $ativo = pg_fetch_result($res,0,'ativo');
                        }

                        if(($login_fabrica == 120 or $login_fabrica == 201) && strlen($peca) > 0){ //hd_chamado=2765193
                            $sql = "SELECT linha
                                        FROM tbl_produto
                                        JOIN tbl_lista_basica ON tbl_lista_basica.produto = tbl_produto.produto
                                        JOIN tbl_peca ON tbl_peca.peca = tbl_lista_basica.peca
                                        WHERE tbl_produto.fabrica_i = $login_fabrica
                                        and tbl_peca.peca = $peca
                                        and tbl_produto.linha = $linha";
                            $res = pg_query($con, $sql);

                            if(pg_num_rows($res) == 0){
                                $linha_erro = $i;
                                $msg_erro = "Existem peças que não pertem a linha selecionada";
                            }
                        }

                        if ($login_fabrica == 91 && !empty($peca)) {
                            $sql_depara = "SELECT de, para, peca_de, peca_para
                                            FROM tbl_depara
                                            WHERE fabrica = $login_fabrica
                                            AND (expira IS NULL OR CURRENT_TIMESTAMP < expira)
                                            AND peca_de = $peca";
                            $res_depara = pg_query($con, $sql_depara);

                            if (pg_num_rows($res_depara) > 0) {
                                $msg_erro = "Peça ".pg_fetch_result($res_depara, 0, "de")." não disponível, modificada para ".pg_fetch_result($res_depara, 0, "para");
                            }

                            // if ($tipo_pedido == 181) {
                            //     $sql_critica = "SELECT peca FROM tbl_peca WHERE fabrica = {$login_fabrica} AND peca = {$peca} AND peca_critica IS TRUE";
                            //     $res_critica = pg_query($con, $sql_critica);

                            //     if (pg_num_rows($res_critica) > 0) {
                            //         $msg_erro = "Peça {$peca_referencia} é uma peça crítica e não pode ser lançada em um pedido faturado";
                            //     }
                            // }
                        }


                        if($ativo == 'f') {
                            $msg_erro = "Peça $peca_referencia inativa";
                        }

                        if (strlen ($msg_erro) == 0) {

                            $qtde_anterior = 0;

                            if (strlen($item) > 0 AND $login_fabrica == 3) {

                                $sql = "SELECT qtde FROM tbl_pedido_item WHERE pedido_item = $item";

                                $res = @pg_query($con,$sql);
                                if (@pg_num_rows($res) > 0) {
                                    $qtde_anterior = pg_fetch_result($res, 0, 'qtde');
                                }

                            }


                            if(!in_array($login_fabrica, array(11, 42, 81, 114, 119, 122, 123, 125, 128, 136, 172))){
                              $sql = "SELECT preco FROM tbl_tabela_item WHERE peca = $peca AND tabela = $xtabela";
                              $res = pg_query($con,$sql);

                              if(pg_num_rows($res) > 0){
                                  $preco = pg_fetch_result($res,0,0);
                              } else{
                                  $preco = "";
                                  $msg_erro = "Peça $peca_referencia sem preço";
                              }
                            }

                            if($login_fabrica == 136){

                                $sql_posto = "SELECT posto FROM tbl_pedido WHERE pedido = {$pedido} AND fabrica = {$login_fabrica}";
                                $res_posto = pg_query($con, $sql_posto);

                                $posto_pedido = pg_fetch_result($res_posto, 0, "posto");

                                $sql_posto_interno = "SELECT
                                                            tbl_tipo_posto.posto_interno
                                                        FROM tbl_posto_fabrica
                                                        INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
                                                        WHERE
                                                            tbl_posto_fabrica.posto = {$posto_pedido}
                                                            AND tbl_posto_fabrica.fabrica = {$login_fabrica}";
                                $res_posto_interno = pg_query($con, $sql_posto_interno);

                                $posto_interno = pg_fetch_result($res_posto_interno, 0, "posto_interno");

                                if($posto_interno == "t"){
                                    $preco = 0;
                                }

                            }

                            if ($login_fabrica == 42) {
                                $precoValor = trim(str_replace("R$", '', $_POST['preco_'.$i]));
                                if (strlen ($precoValor) == 0) $precoValor = "null";
                                $precoValor = str_replace (".","",$precoValor);
                                $precoValor = str_replace (",",".",$precoValor);
                                $precoValor = floatval(preg_replace("/[^-0-9\.]/","",$precoValor));
                                $logCampo = ", valores_adicionais";

                                $logItemArray = json_decode($logItem, true);

                                $preco_item_imposto = '';
                                $preco_item_imposto = floatval($precoUnitImp);

                                $valoresAdd = [
                                                "peca_unidade"=>$_POST["peca_unidade_".$i],
                                                "ipi"=>utf8_encode($_POST["ipi_".$i]),
                                                "icms"=>utf8_encode($_POST["icms_".$i]),
                                                "icmsSt"=>utf8_encode($_POST["icmsSt_".$i]),
                                                "pis"=>utf8_encode($_POST["pis_".$i]),
                                                "cofins"=>utf8_encode($_POST["cofins_".$i]),
                                                "sub_total"=>utf8_encode($_POST["sub_total_".$i]),
                                                "logItem"=>$logItemArray,
                                                "precoUnitImp"=>$preco_item_imposto
                                              ];

                                $valoresAdd = json_encode($valoresAdd);
                                $valoresAdd = str_replace('\\u', '\\\\u', $valoresAdd);

                                $logUpdade = ", valores_adicionais = '$valoresAdd'";

                                $logValor = ", '$valoresAdd'";

                                $preco_item_liquido = '';
                                $preco_item_liquido = floatval($precoUnitLiq);
                            } else {
                                $precoValor = $preco;
                            }

                            if (strlen($pedido) == 0 OR $novo == 't' && strlen($msg_erro) == 0 ) {

                                if (strlen($qtde) == 0) {
                                    $qtde = 1;
                                }

                                $campoBase = "";
                                $valorBase = "";

                                if (!empty($preco_item_liquido) && $login_fabrica == 42) {
                                    $campoBase = " preco_base ,";
                                    $valorBase = $preco_item_liquido." , ";
                                }

                                $sql = "INSERT INTO tbl_pedido_item (
                                            pedido,
                                            peca  ,
                                            qtde  ,
                                            $campoBase
                                            preco
                                            $logCampo
                                        ) VALUES (
                                            $pedido,
                                            $peca  ,
                                            $qtde  ,
                                            $valorBase
                                            $precoValor
                                            $logValor
                                        )";
                                $res      = @pg_query($con,$sql);
                                $msg_erro = pg_errormessage($con);
                                if (strlen($msg_erro) == 0) {
                                    $res         = @pg_query($con,"SELECT CURRVAL ('seq_pedido_item')");
                                    $pedido_item = @pg_fetch_result($res,0,0);
                                    $msg_erro    = @pg_errormessage($con);
                                }
                            } else if ( empty ($msg_erro) and !empty($item) ) {

                                $campoBaseValor = "";

                                if (!empty($preco_item_liquido) && $login_fabrica == 42) {
                                    $campoBaseValor = " preco_base = $preco_item_liquido , ";
                                }

                                $sql = "UPDATE tbl_pedido_item SET
                                            peca = $peca,
                                            $campoBaseValor
                                            qtde = $qtde
                                            $logUpdade
                                        WHERE  pedido      = $pedido
                                        AND    pedido_item = $item";
                                    if($item_verf == TRUE){
                                            $res      = @pg_query($con, $sql);
                                            $msg_erro = @pg_errormessage($con);
                                    }
                            }

                            if ($login_fabrica == 42) {
                                $sql_peca_monitorada = "SELECT JSON_FIELD('peca_monitorada', parametros_adicionais) AS peca_monitorada, 
                                                               JSON_FIELD('email_peca_monitorada', parametros_adicionais) AS email_peca_monitorada,
                                                               referencia,
                                                               descricao
                                                        FROM tbl_peca
                                                        WHERE peca = $peca
                                                        AND fabrica = $login_fabrica";
                                $res_peca_monitorada = pg_query($con, $sql_peca_monitorada);
                                if (pg_fetch_result($res_peca_monitorada, 0, 'peca_monitorada') == "t") {
                                    $email_peca_monitorada = trim(pg_fetch_result($res_peca_monitorada, 0, 'email_peca_monitorada'));
                                    $referencia_monitorada = pg_fetch_result($res_peca_monitorada, 0, 'referencia');
                                    $descricao_monitorada  = pg_fetch_result($res_peca_monitorada, 0, 'descricao');

                                    $array_pecas_monitoradas["email"]["$email_peca_monitorada"][] = array("referencia" => $referencia_monitorada, "descricao" => $descricao_monitorada, "qtde" => $qtde);
                                }
                            }

                            /* Tira do estoque disponivel - HD 11337 */
                            if ($login_fabrica == 3) {

                                $sql = "UPDATE tbl_peca
                                           SET qtde_disponivel_site = qtde_disponivel_site + $qtde_anterior - $qtde
                                         WHERE peca     = $peca
                                           AND fabrica  = $login_fabrica
                                           AND promocao_site IS TRUE
                                           AND qtde_disponivel_site IS NOT NULL";
                                $res       = pg_query($con, $sql);
                                $msg_erro .= pg_errormessage($con);

                            }

                            if (strlen($msg_erro) == 0) {

                                $sql      = "SELECT fn_valida_pedido_item ($pedido, $peca, $login_fabrica)";
                                $res      = pg_query($con,$sql);
                                $msg_erro = pg_errormessage($con);
                            }

                            if (strlen ($msg_erro) > 0) {
                                break ;
                            }
                        }
                    }
                }

                if (!empty($msg_erro)){
                    break;

                }
            }

            if($login_fabrica == 50 and empty($msg_erro)){
                if($tipo_pedido == 173 AND $devolucao_obrigatoria == 'sim'){
                $msg_erro = "O tipo de pedido não pode ser Doação";
                }

                if($tipo_pedido == 129 AND $devolucao_obrigatoria == 'nao'){
                $msg_erro = "O tipo de pedido não pode ser Garantia";
                }

            }
        }
    }
    if ($login_fabrica == 30 && strlen($msg_erro) == 0){
        $pedidos = array($pedido);
        try{
            //função definida em: ../includes2/pedidosEsmaltec_verificaOrigem.php
            $novoPedido = verificaOrigemPecas($pedido);
            if($novoPedido != false){
                $pedidos[] = $novoPedido;
            }
            $valorTotalPedidos = 0;
            foreach($pedidos as $item){

                $sql = "SELECT fn_pedido_finaliza ($item,$login_fabrica)";
                $res = pg_query($con,$sql);
                $msg_erro = pg_errormessage($con);

                $sql = "SELECT total,desconto from tbl_pedido where pedido=$item";
                $res = pg_query($con,$sql);
                if(pg_num_rows($res) > 0){
                    $total = pg_fetch_result($res,0,total);

                    $valorTotalPedidos += $total;
                }

                $isPostoTipoAtende = isPostoTipoAtende($pedido);
                if(!$isPostoTipoAtende){
                    if(empty($msg_erro)){
                        if($total < 60){
                            $msg_erro="O pedido deve ser maior que R$ 60,00";
                        }
                    }
                }
            }
            if($isPostoTipoAtende){
                if($valorTotalPedidos < 60){
                    $msg_erro="O pedido deve ser maior que R$ 60,00";
                }

            }


            if(strlen($msg_erro) == 0){

                pg_query($con, "COMMIT TRANSACTION");
            }else{
                pg_query($con, "ROLLBACK TRANSACTION");
            }

        }catch(Exception $ex){

            var_dump('rollback exception'.$ex->getMessage());exit;
            pg_query($con, "ROLLBACK TRANSACTION");

            $msg_erro = $ex->getMessage();
        }

    }elseif (strlen($msg_erro) == 0) {
        $sql      = "SELECT fn_pedido_finaliza($pedido, $login_fabrica)";
        $res      = @pg_query($con,$sql);
        $msg_erro = @pg_errormessage($con);
    }

    #---------- Pedido Via DISTRIBUIDOR (forçado) ----------#
    $pedido_via_distribuidor = $_POST['pedido_via_distribuidor'];
    if ($pedido_via_distribuidor == "f") {
        $sql = "UPDATE tbl_pedido SET pedido_via_distribuidor = 'f' , distribuidor = null WHERE pedido = $pedido";
        $res = pg_query ($con,$sql);
    }

    // Todo pedido Lenoxx deve entrar em auditoria
    if (strlen($msg_erro) == 0 && in_array($login_fabrica, [11,172])) {
        $sql_auditoria = "UPDATE tbl_pedido SET status_pedido = 18 WHERE pedido = $pedido";
        $res_auditoria = pg_query($con, $sql_auditoria);
    }

    if($login_fabrica == 122){
        $id_posto = $_POST['posto_codigo'];
        if($id_posto == 20682 && $auxiliar_callcenter != ""){
            $sql = "UPDATE tbl_pedido SET status_pedido = 18 WHERE pedido = $pedido";
            $res           = @pg_query($con,$sql);
            $msg_erro      .= pg_errormessage($con);
        }
    }
    
    //Status de pedido para fabricas de gestao interna
    if ($telecontrol_distrib) {

        $sqlAud = "SELECT pedido_status FROM tbl_pedido_status WHERE pedido = $pedido AND observacao = 'Pedido Aprovado'";
        $resAud = pg_query($con, $sqlAud);

        if (pg_num_rows($resAud) == 0) {
            if (!empty($pedido_normal)) {
                $updateStatus = "UPDATE tbl_pedido SET status_pedido = 18 WHERE pedido = $pedido";
                pg_query($con, $updateStatus);
                $aux_sql = " INSERT INTO tbl_pedido_status(pedido, data, status,  observacao) VALUES ($pedido, current_timestamp, 18, 'Aguardando Aprovação')";
                $aux_res = pg_query($con, $aux_sql);
            } else {
                $updateStatus = "UPDATE tbl_pedido SET status_pedido = 18 WHERE pedido = $pedido";
                pg_query($con, $updateStatus);
            }
        }
    }

    if (strlen ($msg_erro) == 0) {
       
        $res = pg_query ($con,"COMMIT TRANSACTION");

        if ($tpAuditor == 'insert') {
            $objLog->retornaDadosTabela('tbl_pedido', array('pedido'=>$pedido, 'fabrica'=>$login_fabrica))
                    ->enviarLog($tpAuditor, 'tbl_pedido', $login_fabrica.'*'.$pedido);
            $objItem->retornaDadosSelect("select peca,qtde,preco,status_pedido,qtde_faturada,qtde_cancelada,produto_locador,nota_fiscal_locador,data_nf_locador,qtde_faturada_distribuidor,preco_base,acrescimo_financeiro,acrescimo_tabela_base,icms,troca_produto,ipi,tabela,obs,servico,serie_locador,defeito,pedido_item_atendido,condicao,estoque,total_item,peca_alternativa from tbl_pedido_item where pedido = $pedido")
                    ->enviarLog($tpAuditor, "tbl_pedido_item", $login_fabrica."*".$pedido);
        } else {
            $objLog->retornaDadosTabela()->enviarLog($tpAuditor, "tbl_pedido", $login_fabrica."*".$pedido);
            $objItem->retornaDadosSelect()->enviarLog($tpAuditor, "tbl_pedido_item", $login_fabrica."*".$pedido);
        }

        if ($login_fabrica == 42 && count($array_pecas_monitoradas) > 0) {

            include dirname(__FILE__) . '/../class/communicator.class.php';

            $msg = []; 
            $msg_pronta = [];
            

            $sql_nome_posto = "SELECT tbl_posto.nome, 
                                          tbl_posto.cnpj 
                                   FROM tbl_posto 
                                   JOIN tbl_posto_fabrica USING(posto) 
                                   WHERE tbl_posto.posto = $posto 
                                   AND tbl_posto_fabrica.fabrica = $login_fabrica";
            $res_nome_posto = pg_query($con, $sql_nome_posto);
            $nome_posto = pg_fetch_result($res_nome_posto, 0, 'nome');
            $cnpj_posto = pg_fetch_result($res_nome_posto, 0, 'cnpj');

            foreach ($array_pecas_monitoradas as $chave => $value_chave) {
                if ($chave == 'email') {
                    foreach ($value_chave as $nome_campo => $value_campo) {
                        foreach ($value_campo as $nomes => $values) {                            
                            $msg[$nome_campo][] = "Peça: ".$values['referencia'].", Descrição: ".$values['descricao']." e Quantidade: ".$values['qtde'];
                        }
                    }
                }
            }
                      
            foreach ($msg as $email => $vl) {
                $ms = "Pedido: $pedido<br><br>Posto: $nome_posto - CNPJ: $cnpj_posto<br><br>".implode("<br>", $vl)."<br><br>";
                $msg_pronta[$email] = $ms;
            }
            
            foreach ($msg_pronta as $key => $value) {
                $email = $key;
                $mailTc = new TcComm($externalId);
                $res = $mailTc->sendMail(
                    $email,
                    utf8_encode('Telecontrol - Peças Monitoradas'),
                    utf8_encode($value),
                    'noreply@telecontrol.com.br'
                );
            }
        } 

        if(!empty($pedido)){
            $id_posto = $_POST['posto_codigo'];
            $sql_posto = "SELECT 
                            tbl_posto_fabrica.contato_email as contato_email,
                            tbl_fabrica.nome as fabrica_nome,
                            tbl_posto.nome as posto_nome 
                        FROM tbl_posto_fabrica 
                        JOIN tbl_fabrica on (tbl_fabrica.fabrica = tbl_posto_fabrica.fabrica) 
                        JOIN tbl_posto on (tbl_posto.posto = tbl_posto_fabrica.posto) 
                        where tbl_posto_fabrica.fabrica = $login_fabrica and tbl_posto_fabrica.posto = $id_posto";

            $res_posto = pg_query($con, $sql_posto);

            $contato_email = pg_fetch_result($res_posto, 0, 'contato_email');
            $fabrica_nome = pg_fetch_result($res_posto, 0, 'fabrica_nome');
            $posto_nome = pg_fetch_result($res_posto, 0, 'posto_nome');

            $assunto       = "Pedido nº ".$pedido. " - ". $fabrica_nome;
            $corpo         = email_pedido($posto_nome, $fabrica_nome, $pedido, $cook_login, true, 'antiga');

            $mailTc = new TcComm($externalId);
            $res = $mailTc->sendMail(
                $contato_email,
                $assunto,
                utf8_encode($corpo),
                $externalEmail
            );
        }
        
        if ($login_fabrica == 7 or $login_fabrica == 104) {
            header ("Location: pedido_admin_consulta.php?pedido=$pedido");
            exit;
        }

        if (($pedido_aut != $pedido_normal) && $login_fabrica == 50) {

            if (!$pedido_aut) {
                header("Location: pedido_cadastro.php?ok=s&n_pedido=$pedido");
            } else {
                header("Location: pedido_cadastro.php?ok=s&pa=$pedido_aut&pn=$pedido_normal");
            }

        } else if ($login_fabrica == 50 && ($pedido_aut == $pedido_normal)) {
            header("Location: pedido_cadastro.php?ok=s");
        }

        if ($login_fabrica <> 50) {
            header("Location: pedido_admin_consulta.php?pedido=$pedido");
        }

        echo "<script language='javascript'>";
        echo "window.open ('pedido_finalizado.php?pedido=$pedido','pedido', 'toolbar=yes, location=no, status=no, scrollbars=yes, directories=no, width=500, height=400')";
        echo "</script>";

        exit;

    } else {
        $res = pg_query($con, "ROLLBACK TRANSACTION");
    }
}

$pedido = $_REQUEST["pedido"];

#------------ Le Pedido da Base de dados ------------#
if (strlen ($pedido) > 0) {

    $sql = "SELECT  tbl_posto.cnpj           ,
                    tbl_posto.posto           ,
                    tbl_posto.nome           ,
                    tbl_pedido.condicao      ,
                    tbl_pedido.tabela        ,
                    tbl_pedido.obs           ,
                    tbl_pedido.tipo_pedido   ,
                    tbl_pedido.pedido_via_distribuidor  ,
                    tbl_pedido.tipo_frete    ,
                    tbl_pedido.valor_frete   ,
                    tbl_pedido.pedido_cliente,
                    tbl_pedido.validade      ,
                    tbl_pedido.entrega       ,
                    tbl_pedido.exportado     ,
                    tbl_pedido.linha         ,
                    tbl_pedido.transportadora,
                    tbl_pedido.promocao      ,
                    tbl_pedido.status_pedido ,
                    tbl_status_pedido.descricao AS status_desc,
                    tbl_pedido.desconto      ,
					atende_pedido_faturado_parcial,
					(select count(1) from tbl_pedido_item where tbl_pedido_item.pedido = tbl_pedido.pedido) as qtde_item
            FROM    tbl_pedido
            JOIN    tbl_posto         USING (posto)
            JOIN    tbl_status_pedido USING(status_pedido)
            WHERE   tbl_pedido.pedido  = $pedido
            AND     tbl_pedido.fabrica = $login_fabrica";

    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {

        $condicao                = trim(pg_fetch_result($res, 0, 'condicao'));
        $posto                   = trim(pg_fetch_result($res, 0, 'posto'));
        $tipo_frete              = trim(pg_fetch_result($res, 0, 'tipo_frete'));
        $aux_valor_frete         = pg_fetch_result($res, 0, 'valor_frete');
        $valor_frete             = number_format(pg_fetch_result($res, 0, 'valor_frete'),2,',','');
        $tipo_pedido             = trim(pg_fetch_result($res, 0, 'tipo_pedido'));
        $pedido_cliente          = trim(pg_fetch_result($res, 0, 'pedido_cliente'));
        $pedido_via_distribuidor = trim(pg_fetch_result($res, 0, 'pedido_via_distribuidor'));
        $validade                = trim(pg_fetch_result($res, 0, 'validade'));
        $entrega                 = trim(pg_fetch_result($res, 0, 'entrega'));
        $tabela                  = trim(pg_fetch_result($res, 0, 'tabela'));
        $nome                    = trim(pg_fetch_result($res, 0, 'nome'));
        $cnpj                    = trim(pg_fetch_result($res, 0, 'cnpj'));
        $cnpj                    = ($login_fabrica == 24) ? $cnpj : preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
        $obs                     = trim(pg_fetch_result($res, 0, 'obs'));
        $linha                   = trim(pg_fetch_result($res, 0, 'linha'));
        $transportadora          = trim(pg_fetch_result($res, 0, 'transportadora'));
        $promocao                = trim(pg_fetch_result($res, 0, 'promocao'));
        $desconto                = trim(pg_fetch_result($res, 0, 'desconto'));
        $status_pedido           = trim(pg_fetch_result($res, 0, 'status_pedido'));
        $status_pedido_desc      = trim(pg_fetch_result($res, 0, 'status_desc'));
        $parcial                 = trim(pg_fetch_result($res, 0,'atende_pedido_faturado_parcial'));
        $qtde_item_pedido		 = trim(pg_fetch_result($res, 0,'qtde_item'));

    }

    if($login_fabrica == 101){
        $sql = "SELECT  tbl_pedido.obs
                FROM    tbl_pedido
                WHERE   tbl_pedido.pedido  = $pedido
                AND     tbl_pedido.fabrica = $login_fabrica
        ";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $obs = trim(html_entity_decode(stripslashes(pg_fetch_result($res, 0, obs))));
            $numero = substr_count($obs,"<tr");
        }
    }
}

#---------------- Recarrega Form em caso de erro -------------
if (strlen ($msg_erro) > 0) {
    $pedido         = $_POST['pedido'];
    $cnpj           = $_POST['cnpj'];
    $nome           = $_POST['nome'];
    $condicao       = $_POST['condicao'];
    $tipo_frete     = $_POST['tipo_frete'];
    $tipo_pedido    = $_POST['tipo_pedido'];
    $tipo_peca    = $_POST['tipo_peca'];
    $pedido_cliente = $_POST['pedido_cliente'];
    $validade       = $_POST['validade'];
    $entrega        = $_POST['entrega'];
    $tabela         = $_POST['tabela'];
    $cnpj           = $_POST['cnpj'];
    $obs            = $_POST['obs'];
    $linha          = $_POST['linha'];
    $desconto       = $_POST['desconto'];
    $pedido_via_distribuidor = $_POST['pedido_via_distribuidor'];
    $parcial        = $_POST['parcial'];
}

if($login_fabrica <> 108 and $login_fabrica <> 111){
$layout_menu = "callcenter";
} else {
$layout_menu = "gerencia";
}
$title       = "CADASTRO DE PEDIDOS DE PEÇAS";
$body_onload = "javascript: document.frm_pedido.condicao.focus()";



function buscaGruposCondicaoPagamento($maximo){

    global $con, $login_fabrica;

    // Retorna o limite máximo para cada um dos grupos gerais (A, B, C)
    if($maximo){

        $sql_grupo = 
            "SELECT DISTINCT campos_adicionais->'grupo' as grupo, 
                MAX((campos_adicionais#>>'{limite_maximo}') :: float) as limite_maximo 
            FROM tbl_condicao WHERE tbl_condicao.fabrica = $login_fabrica
                                    AND tbl_condicao.visivel = 'TRUE'
                                    AND campos_adicionais->'grupo' IS NOT NULL 
                                    AND campos_adicionais->'limite_maximo' IS NOT NULL
            GROUP BY campos_adicionais->'grupo'";

        $qry_grupo = pg_query ($con, $sql_grupo);

        $grupos = array(
            "A" => 0, 
            "B" => 0, 
            "C" => 0
        );

        if(pg_num_rows($qry_grupo) > 0){

            $res_grupo = pg_fetch_all($qry_grupo);

            foreach($res_grupo as $r){

                $grupo = str_replace('"', "", $r["grupo"]);

                foreach(['A', 'B', 'C'] as $grp){

                    if($grupo == $grp){

                        $grupos[$grp] = $r["limite_maximo"];
                    }
                }
            }
        }

    // Retorna os limites para cada grupo com seus respectivos subgrupos
    }else{
    
        $sql_grupo =
            "SELECT condicao,
                    campos_adicionais->'grupo' as grupo, 
                    campos_adicionais#>>'{limite_maximo}' as limite_maximo,
                    ROUND((limite_minimo)::numeric,2) as limite_minimo
            FROM tbl_condicao WHERE tbl_condicao.fabrica = $login_fabrica
                                AND tbl_condicao.visivel = 'TRUE'
                                AND campos_adicionais->'grupo' IS NOT NULL 
                                AND campos_adicionais->'limite_maximo' IS NOT NULL 
             ORDER BY CAST(campos_adicionais->>'limite_maximo' as float) ASC";

        $qry_grupo = pg_query ($con, $sql_grupo);
        $res_grupo = pg_fetch_all($qry_grupo);

        $grupos = array(
            "A" => [], 
            "B" => [], 
            "C" => []
        );

        foreach($res_grupo as $r){

            $grupo = str_replace('"', "", $r["grupo"]);

            foreach(['A', 'B', 'C'] as $grp){

                if($grupo == $grp){

                    array_push($grupos[$grp], [
                        'condicao' =>      $r["condicao"], 
                        'limite_maximo' => $r["limite_maximo"], 
                        'limite_minimo' => $r["limite_minimo"],
                    ]);
                }
            }  
        }
    }

    return $grupos;
}


include "cabecalho.php";?>

<script type='text/javascript' src='ajax.js'></script>
<script type='text/javascript' src='ajax_cep.js'></script>
<script type='text/javascript' src='ajax_produto.js'></script>
<script type='text/javascript' src='js/bibliotecaAJAX.js'></script>
<script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script type='text/javascript' src='js/jquery.alphanumeric.js'></script>
<script type="text/javascript" src="../admin/js/jquery.maskmoney.js"></script>
<script src="../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<script type='text/javascript'>
/* ============= Função PESQUISA DE PRODUTOS ====================
Nome da Função : fnc_pesquisa_produto (codigo,descricao)
        Abre janela com resultado da pesquisa de Produtos pela
        referência (código) ou descrição (mesmo parcial).
=================================================================*/

$().ready(function(){
    Shadowbox.init();

<?
    if(in_array($login_fabrica,array(81,114,119,122,123,125,128,136))){?>
      $("input[name*=preco_]").maskMoney({symbol:"", decimal:".", thousands:'', precision:2, maxlength: 15});
<?
    }
    if(in_array($login_fabrica, array(120,201))){
?>
    $("#desconto").css("text-align","right");
    $("#desconto").maskMoney({
        symbol:"",
        decimal:",",
        thousands:'.',
        precision:2,
        maxlength: 15
    });
<?
    }
?>
    $("input[name*=qtde_]").numeric();

    <?php if($login_fabrica == 74){ ?>

    $("select[name=tipo_pedido]").change(function(){

        if($('input[name=nome]').val() == ""){
            $("select[name=tipo_pedido]").val("");
            alert("Por favor insira o Posto / CNPJ");
            return;
        }

        $.ajax({
            url : "pedido_cadastro.php",
            type : "GET",
            data : {ajax_tabela : "sim", cnpj : $("#cnpj").val(), tipo_pedido : $(this).val()},
            complete : function(data){
                $("select[name=tabela]").html(data.responseText);
            }
        });
    });
<?
    }

    if(in_array($login_fabrica,array(88,120,201))){
?>
    var login_fabrica = <?=$login_fabrica?>;
    $('button[id=calcular_frete]').click(function(){
        var pecas           = new Array();
        var qtde            = new Array();
        var linha           = new Array();
        var posto_cnpj      = $('#cnpj').val();
        var frete_calculado = $("#frete_calculado").val();
        posto_cnpj          = posto_cnpj.replace(/\D/g,'');
        $('input[name^=peca_referencia_]').each(function(index){
            if($(this).val() != ""){
                linha.push(index);
                pecas.push($(this).val());
                qtde.push($('input[name=qtde_'+index+']').val());
            }
        });
        if(frete_calculado != "sim"){
            $.ajax({
                url:"<?$PHP_SELF?>",
                type:"POST",
                dataType:"json",
                data:{
                    ajax:"sim",
                    posto:posto_cnpj,
                    linha:linha,
                    pecas:pecas,
                    qtde:qtde
                }
            })
            .done(function(result){
                var transportadora  = result[0].trans;
                var frete           = result[0].valor;
                var seguro          = result[0].seguro;
                var gris            = result[0].gris;
                var estadoPosto     = result[0].posto_estado;
                var freteForm;
                var calculoSeguro;
                var calculoGris;
                var totalPedido;
                var total = $("input[id=total_pecas]").val();

                var estadoNordeste = ["MA", "PI", "CE", "RN", "PE", "PB", "SE", "AL"];

                total = total.replace(".","");
                total = total.replace(",",".");

                if(login_fabrica == 88 ){
                    if(total < 1000.00 || $.inArray(estadoPosto, estadoNordeste) != -1){

                        totalPedido = parseFloat(frete) + parseFloat(total);
                        frete       = parseFloat(frete).toFixed(2);
                        freteForm   = frete.replace('.',',');

                    }else{

                        totalPedido = parseFloat(total);
                        freteForm   = "0,00";
                        alert("Pedido com valor acima de R$ 1000,00 terá frete grátis");

                    }

                }else{
                    calculoSeguro   = (parseFloat(total) * (parseFloat(seguro) / 100));
                    calculoGris     = (parseFloat(total) * (parseFloat(gris) / 100));
                    totalPedido = parseFloat(frete) + calculoSeguro + calculoGris + parseFloat(total);
                    frete = parseFloat(frete).toFixed(2);
                    freteForm = frete.replace('.',',');
                }

                totalPedido = totalPedido.toFixed(2);
                totalPedido = totalPedido.replace('.',',');

                $("input[id=valor_frete]").val(freteForm);
                $("input[id=valor_frete_hidden]").val(freteForm);
                $("input[id=valor_total_frete]").val(totalPedido);
                $("input[id=transportadora]").val(transportadora);
                $("input[id=frete_calculado]").val("sim");
            })
            .fail(function(result){
                alert("Não foi possível encontrar transportadora, peso de peças ou faixa de frete");
            });
        }else{
            alert("Frete calculado para os valores atuais.\n Modifique os dados para recálculo.");
        }
    });

    $("#itens_pedido").find("input").change(function(){
        $("input[id=valor_frete]").val("");
        $("input[id=valor_frete_hidden]").val("");
        $("input[id=frete_calculado]").val("");

        setTimeout(function(){
            $("input[id=valor_total_frete]").val($("input[id=total_pecas]").val());
        }, 1000);

    });
<?
}

if ($login_fabrica == 104) {?>


    function criaOptions(response){
    
        var select = $("select[name='condicao']");
        select.empty();
        var option = new Option("", "");
        select.append(option);
        $(response).each(function (index, item) {
            var option = new Option(item.descricao, item.condicao);
            select.append(option);
        });
        select.attr('disabled', false);
    }

    function ajaxBuscaCondicoes(data, callback){

        $.ajax({
            url : "pedido_cadastro.php",
            type: "POST",
            dataType: "json",
            data: data,
            success: function(response){;
               callback(response);
            },
            error: function (request, status, error) {
                console.log(error);
            }
        })
    }
    
    if($("select[name='tipo_pedido'] option:selected").text() == "Faturado"){

        var valor = $("#total_pecas").val();
   
        if(valor != "" && valor != "0,00" && parseInt(valor) > 0){
            $("select[name='condicao']").attr('disabled', false);
            var data = {busca_condicao_pagamento: true, valor : parseFloat(valor)}
            ajaxBuscaCondicoes(data, function(response){criaOptions(response);});
        }else{
            $("select[name='condicao']").attr('disabled', true);
        }
    }

    $("select[name='tipo_pedido']").change(function(){
        
        var valor = $("#total_pecas").val();

        if($(this).find('option:selected').text() == "Faturado"){

            if(valor != "" && valor != "0,00" && parseInt(valor) > 0){
                var valor = $("#total_pecas").val();
                var data = {busca_condicao_pagamento: true, valor : parseFloat(valor)}
                ajaxBuscaCondicoes(data, function(response){criaOptions(response);});
            }else{
                $("select[name='condicao']").attr('disabled', true);
                $("select[name='condicao']").val($("select[name='condicao'] option:first").val());
            }    
        }else{
            var data = {busca_todas_condicoes: true}
            ajaxBuscaCondicoes(data, function(response){criaOptions(response);});
        }
    })

    $("#total_pecas").change(function(){

        var valor = $(this).val();

        if($("select[name='tipo_pedido'] option:selected").text() == "Faturado"){

            if(valor != "" && valor != "0,00" && parseInt(valor) > 0){

                var data = {busca_condicao_pagamento: true, valor : parseFloat(valor)}
                ajaxBuscaCondicoes(data, function(response){criaOptions(response);});
 
            }else{
                $("select[name='condicao']").attr('disabled', true);
            }
        }
    });

<?php
}
?>

    $("#btn-calcula-imposto").click(function () {
        let filial   = $("#filial_posto option:selected").attr('filialCnpj');
        let condicao = $("select[name=condicao] option:selected").attr('condMK'); 
        let tipo_pedido = $("select[name=tipo_pedido] option:selected").val();
        let cnpj     = $("#cnpj").val();

        if (filial == '' || filial  == undefined) {
            alert("Selecione a Filial");
            return false;
        }

        if (tipo_pedido == '' || tipo_pedido    == undefined) {
            alert("Selecione o Tipo de Pedido");
            return false;
        } 

        let itens = [];

        $(".itensLinha").find('input').each(function(p) {
            let item  = [];
            if ($("input[name=peca_referencia_"+p+"]").val() != '' && $("input[name=peca_referencia_"+p+"]").val() != undefined) {
                item.push(p);
                item.push($("input[name=peca_referencia_"+p+"]").val());
                item.push(parseInt($("input[name=qtde_"+p+"]").val()));
                item.push($("#peca_unidade_"+p).val());
                itens.push(item);
            }
        });

        if (itens.length > 0) {
            $.ajax({
                url: "<?php echo $_SERVER['PHP_SELF']; ?>",
                type: "POST",
                dataType: "json",
                data: { ajax_calcula_impostos: true, filial: filial, tipo_pedido: tipo_pedido, condicao: condicao, itens: JSON.stringify(itens), cnpj: cnpj },
                beforeSend: function() {
                    $("#btn-calcula-imposto").val('Calculando...');
                },
                complete: function(data) {
                    data = JSON.parse(data.responseText);
        
                    if (data.error) {
                        alert(data.error);
                        $("#btn-calcula-imposto").val('Calcular Valores');
                        return false;
                    } else if (data.success) {
                        $("#btn-calcula-imposto").val('Calcular Valores');
                        $("#btn_gravar").show();
                    } else if (data.length > 0) {
                        let totalGeral = 0;
                        let formatar = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
                        $.each(data, function(posicao, dados) {
                            $("#ipi_"+dados.posicao).val('0,00');
                            $("#icms_"+dados.posicao).val('0,00');
                            $("#icmsSt_"+dados.posicao).val('0,00');
                            $("#pis_"+dados.posicao).val('0,00');
                            $("#cofins_"+dados.posicao).val('0,00');

                            $("#preco_"+dados.posicao).val(formatar.format(dados.precoUnit));
                            $("#logItem_"+dados.posicao).val(dados.log);
                            $("#precoUnitLiq_"+dados.posicao).val(dados.precoUnitLiq);
                            $("#precoUnitImp_"+dados.posicao).val(dados.precoUnit);
                            if (dados.IPI) {
                                $("#ipi_"+dados.posicao).val(formatar.format(dados.IPI));
                            }
                            if (dados.ICMS) {
                                $("#icms_"+dados.posicao).val(formatar.format(dados.ICMS));
                            }
                            if (dados.ST) {
                                $("#icmsSt_"+dados.posicao).val(formatar.format(dados.ST));
                            }
                            if (dados.PIS) {
                                $("#pis_"+dados.posicao).val(formatar.format(dados.PIS));
                            }
                            if (dados.COF) {
                                $("#cofins_"+dados.posicao).val(formatar.format(dados.COF));
                            }
                            $("#sub_total_"+dados.posicao).val(formatar.format(dados.valornota));
                            totalGeral += dados.valornota;
                        });
                        $("#total_pecas").val(formatar.format(totalGeral));
                        $("#btn-calcula-imposto").val('Calcular Valores');
                        $("#gravar").show();
                    }
                }
            });
        }
    });

});

function adiciona_linha(valor){
    var linha;
    var qtde_linha = parseInt($("#formValorTotal").val());
    valor = parseInt(valor) ;
    linha = valor+1;
    var color = linha % 2 ? ' bgcolor="#F7F5F0" ': ' bgcolor="#F1F4FA" ';
    if (valor + 1 == qtde_linha) {
        $("tr[name=linha_"+valor+"]").after("<tr name='linha_"+linha+"'"+color+">" + $("tr[name=linha_"+valor+"]").clone().html().replace(/_\d\d?/g,'_'+linha) + "</tr>");
        $("input[name=qtde_"+linha+"]").attr('alt',linha);
        $("#formValorTotal").val(parseInt(qtde_linha)+1);
    }
}
function fnc_calcula_total (linha_form) {
    var login_fabrica = <?=$login_fabrica?>;
    var total = 0;
    preco = $('#preco_'+linha_form).val();
    qtde  = $('input[name=qtde_'+linha_form+']').val();

    if (qtde == ''){
        qtde = 0;
    }

    preco = preco.replace(',','.');

    if (qtde != undefined && preco){
        total = qtde * preco;
        total = total.toFixed(2);
    }

    if (total > 0) {
        total = total.replace(".",",");
        $('#sub_total_'+linha_form).val(total);

        //Totalizador
        var total_pecas = 0;
        $("input[rel='total_pecas']").each(function(){
            if ($(this).val()){
                tot         = $(this).val();
                tot         = parseFloat(tot.replace(',','.'));
                total_pecas += tot;
            }
        });
        if(login_fabrica == 120 || login_fabrica == 201){
            var desconto = $("#desconto").val();
            desconto = desconto.replace(',','.');
            total_pecas = total_pecas - (total_pecas * (desconto / 100));
        }
        total_pecas = total_pecas.toFixed(2);
        total_pecas = total_pecas.replace('.',',');
        $('#total_pecas').val(total_pecas);
        $("#total_pecas").change();
    }

	var qtde_form = <? echo ($qtde_item_pedido > 0) ? $qtde_item_pedido : 14 ; ?>;
    if(linha_form > qtde_form){
        $('#qtde_item').val(linha_form);
        $('tr[name=linha_'+linha_form+']').append("<input type='hidden' name='novo"+linha_form+"' value='t'>");
        $('tr[name=linha_'+linha_form+']').append("<input type='hidden' name='item"+linha_form+"' >");
    }
}

function defeitoLista(peca,linha) {
    try {ajax = new ActiveXObject("Microsoft.XMLHTTP");}
    catch(e) {
        try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
        catch(ex) { try {ajax = new XMLHttpRequest();}
            catch(exc) {alert("Esse browser não tem recursos para uso do Ajax"); ajax = null;}
        }
    }
    if(peca.length > 0) {
        if(ajax) {
            var defeito = "defeito_"+linha;
            var op = "op_"+linha;
            eval("document.forms[0]."+defeito+".options.length = 1;");
            idOpcao  = document.getElementById(op);
            ajax.open("GET","ajax_defeito2.php?peca="+peca);
            ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            ajax.onreadystatechange = function() {
                if(ajax.readyState == 1) {idOpcao.innerHTML = "Carregando...!";}
                if(ajax.readyState == 4 ) {
                    if(ajax.responseXML) {
                        montaComboDefeito(ajax.responseXML,linha);
                    }
                    else {
                        idOpcao.innerHTML = "Selecione a peça";
                    }
                }
            }
            ajax.send(null);
        }
    }
}

function montaComboDefeito(obj,linha){
    var defeito = "defeito_"+linha;
    var op = "op_"+linha;
    var dataArray   = obj.getElementsByTagName("produto");

    if (dataArray.length > 0) {

        for (var i = 0 ; i < dataArray.length ; i++) {
            var item = dataArray[i];
            var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
            var nome =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
            idOpcao.innerHTML = "Selecione o defeito";
            var novo = document.createElement("option");
            novo.setAttribute("id", op);//atribui um ID a esse elemento
            novo.value = codigo;        //atribui um valor
            novo.text  = nome;//atribui um texto
            eval("document.forms[0]."+defeito+".options.add(novo);");//adiciona
        }

    } else {
        idOpcao.innerHTML = "Selecione o defeito";//caso o XML volte vazio, printa a mensagem abaixo
    }

}

function fnc_pesquisa_produto(campo, campo2, tipo) {

    if (tipo == "referencia") {
        var xcampo = campo;
    }

    if (tipo == "descricao") {
        var xcampo = campo2;
    }

    if (xcampo.value != "") {
        var url = "";
        url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
        janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
        janela.referencia   = campo;
        janela.descricao    = campo2;
        janela.focus();
    } else {
        alert("Informe toda ou parte da informação para realizar a pesquisa!");
    }

}
var referencia;
var descricao;
var preco;

function fnc_pesquisa_peca_preco(campo, campo2, peca_preco, tipo) {
    var login_fabrica = <?=$login_fabrica?>;
    var tipo_peca = '';

    if (tipo == "referencia") {
        var xcampo = campo.value;
    }
    if (tipo == "descricao") {
        var xcampo = campo2.value;
    }
    if (login_fabrica == 104) {
       tipo_peca = document.getElementById('tipo_peca').value;
       //console.log(tipo_peca);
	}

    if (document.getElementById('tabela').value == ''){
        alert("Selecione por favor uma tabela de preços");
    }else if (document.getElementById('tabela').value != ''){

        tabela = document.getElementById('tabela').value;

        if (xcampo != "") {

            if ($('input[name=referencia]').val() != ''){
                var produto = "&prod_referencia="+$('input[name=referencia]').val();
            }else{
                var produto = "&prod_referencia=";
            }

            tipo_peca = "&tipo_peca="+tipo_peca;

            var desconto = 0;

            if(login_fabrica == 88){
                desconto = $("#desconto").val();
            }

            var url = "";
            url     = "peca_pesquisa_2.php?peca_pedido=t&campo=" + xcampo + "&tipo=" + tipo + "&tabela="+tabela+produto+tipo_peca+"&desconto="+desconto ;
            janela  = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");

            peca_referencia = campo;
            peca_descricao  = campo2;
            preco           = peca_preco;

            janela.focus();
        } else {
            alert("Informe toda ou parte da informação para realizar a pesquisa!");
        }

    }
}

//função para a ELLO pesquisar filtrando lista basica
function pesquisaPeca(campo_peca,tipo,posicao){

    var campo_peca = $.trim(campo_peca.value);
    var kit = "";
    var os = "";
    var login_fabrica = <?=$login_fabrica?>;
    var campo_prod = $('input[name=referencia]').val();

    if (campo_peca.length > 2 || tipo == 'tudo'){
        Shadowbox.open({
            content :   "peca_pesquisa_lista_nv.php?produto="+campo_prod+"&"+tipo+"="+campo_peca+"&tipo="+tipo+"&input_posicao="+posicao+"&kit_peca="+kit+"&os="+os,
            player  :   "iframe",
            title   :   "Pesquisa de peça",
            width   :   800,
            height  :   500
        });
    }else{
        alert("Informar toda ou parte da informação para realizar a pesquisa");
    }
}

function retorna_lista_peca(referencia_antiga,posicao,codigo_linha,peca_referencia,peca_descricao,preco,peca,type,input_posicao,kit_peca){
    var login_fabrica = <?=$login_fabrica?>;

    $('#peca_'+input_posicao).blur();
    gravaDados("peca_referencia_"+input_posicao,peca_referencia);
    gravaDados("peca_descricao_"+input_posicao,peca_descricao);
    gravaDados("preco_"+input_posicao,preco);
}

function gravaDados(name, valor){
     try {
        $("input[name="+name+"]").val(valor);
    } catch(err){
        return false;
    }
}
//fim

function fnc_pesquisa_peca(campo, campo2, tipo) {

    if (tipo == "referencia") {
        var xcampo = campo.value;
    }

    if (tipo == "descricao") {
        var xcampo = campo2.value;
    }

    var tipo_pedido = $("select[name=tipo_pedido]").val();

    <? if($login_fabrica == 50){ ?>
            if(tipo_pedido == ""){
                alert("Informe o Tipo do Pedido");
                return false;
            }
    <? } ?>
    if (xcampo != "") {
        var url = "";
        url = "peca_pesquisa_2.php?campo=" + xcampo + "&tipo=" + tipo + "&tipo_pedido=" + tipo_pedido;
        janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
        janela.referencia   = campo;
        janela.descricao    = campo2;
        janela.focus();
    } else {
        alert("Informe toda ou parte da informação para realizar a pesquisa!");
    }

}

function fnc_pesquisa_posto(campo, campo2, tipo) {

    if (tipo == "nome" ) {
        var xcampo = campo;
    }

    if (tipo == "cnpj" ) {
        var xcampo = campo2;
    }

    if (xcampo.value != "") {
        var url = "";
        url = "posto_pesquisa.php?campo=" + xcampo.value + "&tipo=" + tipo + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
        janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=300, top=0, left=0");

        janela.retorno               = "<? echo $PHP_SELF ?>";
        janela.nome                  = campo;
        janela.cnpj                  = campo2;
        janela.posto_codigo          = document.frm_pedido.posto_codigo;
        janela.transportadora        = document.frm_pedido.transportadora;
        janela.transportadora_nome   = document.frm_pedido.transportadora_nome;
        janela.transportadora_codigo = document.frm_pedido.transportadora_codigo;
        janela.transportadora_cnpj   = document.frm_pedido.transportadora_cnpj;
        janela.desconto              = document.frm_pedido.desconto;

        janela.focus();
    } else {
        alert("Informe toda ou parte da informação para realizar a pesquisa!");
    }

}

function fnc_pesquisa_transportadora(xcampo, tipo) {

    if (xcampo.value != "") {
        var url = "";
        url = "pesquisa_transportadora.php?campo=" + xcampo.value + "&tipo=" + tipo ;
        janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
        janela.transportadora = document.frm_pedido.transportadora;
        janela.nome           = document.frm_pedido.transportadora_nome;
        janela.codigo         = document.frm_pedido.transportadora_codigo;
        janela.cnpj           = document.frm_pedido.transportadora_cnpj;
        janela.focus();
    } else {
        alert("Informe toda ou parte da informação para realizar a pesquisa!");
    }

}

function verificaFrete(campo){

    if (campo.value == 'CIF'){
    //  $("#valor_frete").show();
        $("#valor_frete").attr('disabled',false);
    //  $("#text_valor_frete").html('');
    }else{
    //  $("#valor_frete").hide();
        $("#valor_frete").attr('disabled',true);
    //  $("#text_valor_frete").html('-');
    }
}

function createRequestObject() {

    var request_;
    var browser = navigator.appName;

    if (browser == "Microsoft Internet Explorer"){
         request_ = new ActiveXObject("Microsoft.XMLHTTP");
    } else {
         request_ = new XMLHttpRequest();
    }
    return request_;
}

var http5 = new Array();
var http6 = new Array();

function calcular_frete(){

    var arrayReferencias = new Array();
    var listaReferencias = "";
    var cliente_cnpj     = $("#cnpj").val();

    $("input[@rel='peca']").each( function (){
        if (this.value.length > 0){
            var qtde_peca = $("input[@name='qtde_"+$(this).attr('alt')+"']").val();
            if (qtde_peca.length == 0){
                qtde_peca = 1;
            }
            arrayReferencias.push( this.value +"|"+qtde_peca );
        }
    });

    listaReferencias = arrayReferencias.join("@");

    if (listaReferencias.length > 0 && cliente_cnpj.length > 0 ) {
        var curDateTime = new Date();
        http5[curDateTime] = createRequestObject();
        url = "pedido_cadastro_ajax.php?calcula_frete=true&relacao_pecas="+listaReferencias+'&cliente_cnpj='+cliente_cnpj+'&data='+curDateTime;
        http5[curDateTime].open('GET',url);
        http5[curDateTime].onreadystatechange = function(){
            if (http5[curDateTime].readyState == 4){
                if (http5[curDateTime].status == 200 || http5[curDateTime].status == 304){
                    var results = http5[curDateTime].responseText.split("|");
                    if (results[0] == 'ok') {
                        if(results[1] == 0){
                            alert('CEP não calculado. Provavelmente não é possível o envio devido ao peso das peças');
                        }else{
                            alert ('Valor do Frete calculado: R$ '+results[1]);
                            $('input[name=valor_frete]').val(results[1]);
                        }
                    }else{
                        if (results[0] == 'nao') {
                            alert(results[1]);
                        }
                    }
                }
            }
        }
        http5[curDateTime].send(null);
    }
}

function fnc_pesquisa_lista_basica(produto_referencia, peca_referencia, peca_descricao, tipo, preco, linha) {

    var url = "";

    if (tipo == "referencia") {
        url = "peca_pesquisa_lista.php?produto=" + produto_referencia.value + "&peca=" + peca_referencia.value + "&tipo=" + tipo + "&exibe=<?=$_SERVER['REQUEST_URI'];?>";
    }

    <?php
    if($telecontrol_distrib){
    ?>
        tabela = document.getElementById('tabela').value;
        if(tabela != ""){
            url = url+"&tabela="+tabela;
        }
    <?php
    }
    ?>

    if(linha != -1){
        url += "&posicao="+linha;
    }

    janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
    janela.produto    = produto_referencia;
    janela.referencia = peca_referencia;
    janela.descricao  = peca_descricao;
    janela.preco      = preco;
    janela.focus();

}

function busca_preco(pos){

    setTimeout(function(){

        $("#lupa_peca_referencia_"+pos).click();

    }, 100);

}

<?
if($login_fabrica == 101){
?>
function gravaObs(valor){
    var pedido = '<?=$pedido?>';
    if (pedido.length == 0){
        return;
    }
    $.ajax({
        type:"POST",
        url: "<? echo $_SERVER['PHP_SELF'];?>",
        data:{
                pedido:pedido   ,
                obs:valor       ,
                metodo:"gravarObs"
             },
        cache: false

    });
}

function addObs(){
    document.getElementById('obs').style.display = 'table-row';
}
function delObs(item){
    $('#'+item).remove();
    var valor = $("#frm_obs tbody").html();
    gravaObs(valor);
}

function salvaObs(){
    var numero = $('#numeroObs').val();
    var obs = $('#observacao').val();
    var admin = '<?=$login_admin?>';
    var pedido = '<?=$pedido?>';
    if (pedido.length == 0){
        return;
    }

    if(obs.length > 0){
        $.ajax({
            type:"POST",
            url: "<?php echo $_SERVER['PHP_SELF']; ?>",
            data:{
                    pedido:pedido   ,
                    admin:admin     ,
                    obs:obs         ,
                    numero:numero
                 },
            cache: false,
            success: function(data){
                $(".naoObs").remove();
                numero++;
                $('#numeroObs').val(numero);
                $("#frm_obs tbody").append(data);
                $('#observacao').val('');
                var valor = $("#frm_obs tbody").html();
                gravaObs(valor);
                document.getElementById('obs').style.display = 'none';
            }
        });
    }else{
        alert("Favor, colocar uma observação");
    }
}

function textCounter(field, countfield, maxlimit) {
    if (field.value.length > maxlimit){
        field.value = field.value.substring(0, maxlimit);
    }else{
        countfield.value = maxlimit - field.value.length;
    }
}
<?
}
?>
</script>

<style type="text/css">

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}
.formulario td span, .formulario label {
    background-color: transparent;
}
.subtitulo{

    background-color: #7092BE;
    font:bold 11px Arial;
    color: #FFFFFF;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

button {
    margin-right: 1ex;
    margin-top: 0.5em;
}

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

a{
    text-decoration:underline !important;
}

a:hover{
    color: #C6E2FF !important;
    text-decoration:underline;
}

.button, .button span {
    display: inline-block;
    -webkit-border-radius: 4px;
    -moz-border-radius: 4px;
    border-radius: 4px;
}

.button {
    white-space: nowrap;
    line-height:1em;
    position:relative;
    outline: none;
    overflow: visible;
    cursor: pointer;
    border: 1px solid #999;
    border: rgba(0, 0, 0, .2) 1px solid;
    border-bottom:rgba(0, 0, 0, .4) 1px solid;
    -webkit-box-shadow: 0 1px 2px rgba(0,0,0,.2);
    -moz-box-shadow: 0 1px 2px rgba(0,0,0,.2);
    box-shadow: 0 1px 2px rgba(0,0,0,.2);
    background: -moz-linear-gradient(
        center top,
        rgba(255, 255, 255, .1) 0%,
        rgba(0, 0, 0, .1) 100%
    );
    background: -webkit-gradient(
        linear,
        center bottom,
        center top,
        from(rgba(0, 0, 0, .1)),
        to(rgba(255, 255, 255, .1))
    );
    -moz-user-select: none;
    -webkit-user-select:none;
    -khtml-user-select: none;
    user-select: none;
    margin-bottom:10px;
}

.button.full, .button.full span {
    display: block;
}

.button:hover, .button.hover {
    background: -moz-linear-gradient(
        center top,
        rgba(255, 255, 255, .2) 0%,
        rgba(255, 255, 255, .1) 100%
    );
    background: -webkit-gradient(
        linear,
        center bottom,
        center top,
        from(rgba(255, 255, 255, .1)),
        to(rgba(255, 255, 255, .2))
    );
}

.button:active, .button.active {
   top:1px;
}

.button span {
    position: relative;
    color:#333;
    text-shadow:0 1px 1px rgba(0, 0, 0, 0.25);
    border-top: rgba(255, 255, 255, .2) 1px solid;
    padding:0.6em 1.3em;
    line-height:1em;
    text-align:center;
    white-space: nowrap;
}

.button.pequeno span {
    font-size:12px;
}

.button.azul {
    background-color: #CCC;
}

.sl {
   pointer-events: none;
  touch-action: none;
}
</style>
<?php

// retira palavra ERROR:
if (strpos($msg_erro,"ERROR: ") !== false) {
    $erro = "Foi detectado o seguinte erro:<br>";
    $msg_erro = substr($msg_erro, 6, strlen($msg_erro)-6);
}

// retira CONTEXT:
if (strpos($msg_erro,"CONTEXT:")) {
    $x = explode('CONTEXT:',$msg_erro);
    $msg_erro = $x[0];
}

if (strlen($msg_erro) > 0) {

    if (strpos($msg_erro,"Cannot insert a duplicate key into unique index tbl_os_sua_os") > 0) $msg_erro = "Esta ordem de serviço já foi cadastrada"; ?>

    <table align='center' width="700" border="0" cellpadding="0" cellspacing="0" >
        <tr class='msg_erro'>
            <td>
                <? echo $msg_erro ?>
            </td>
        </tr>
    </table>
<?php

}

#HD 363162 INICIO
if ($_GET['pa'] && $_GET['pn'] && $_GET['ok'] && $login_fabrica == 50) {

    $pedido_aut = $_GET['pa'];
    $pedido_normal = $_GET['pn'];
?>

    <table align="center" width="700px" border="0" cellpadding="1" cellspacing="1" class="sucesso">
        <tr>
            <td align="center">
                Foram gravados os pedidos:<br />Pedido normal:
                <a target="_blank" href="pedido_admin_consulta.php?pedido=<?=$pedido_normal?>"><?=$pedido_normal?></a>
                <br>Pedido para produtos da linha automática:
                <a target="_blank" href="pedido_admin_consulta.php?pedido=<?=$pedido_aut?>"><?=$pedido_aut?></a>
            </td>
        </tr>
    </table>
<?php

} else if ($_GET['ok'] && !$_GET['pa'] && !$_GET['pn']) {

    if ($login_fabrica == 50) {
?>
    <table align='center' width='700px' border='0' cellpadding='1' cellspacing='1' class='sucesso'>
        <tr>
            <td>
                Pedido Gravado com sucesso!<br>
<?
        if(strlen($_GET['n_pedido']) > 0){
            echo "Pedido Nº ".$_GET['n_pedido'];
        }
?>
            </td>
        </tr>
    </table>
<?
    } else {
?>
    <table align="center" width="700px" border="0" cellpadding="1" cellspacing="1" class="sucesso">
        <tr>
            <td align="center"><?echo "Gravado com Sucesso!"?></td>
        </tr>
    </table>
<?php
    }

} #HD 363162 FIM
?>

<? if($login_fabrica == 104) { $grupos = buscaGruposCondicaoPagamento(true);?>

    <table align="center" id="tabela_grupo" class="table-bordered table-fixed" style="width:400px; font-size:12px; margin-bottom:20px">
        <thead>
            <tr class="titulo_tabela">
                <th style="padding: 8px;" colspan="3">Grupos de Tipo de Pagamento</th>
            </tr>
            <tr class="titulo_tabela">
                <td style="padding: 5px;">Grupo</td>
                <td style="padding: 5px;">Valor Máximo</td>
            </tr>
        </thead>
        <tbody>
            <? foreach($grupos as $key => $value) : ?>
             <tr>
                <td style="padding: 5px; text-align: center; font-weight: bold;border-bottom: 1px solid #00000026;"><?=$key?></td>
                <td style="padding: 5px; text-align: center; font-weight: bold;color:red;border-bottom: 1px solid #00000026;">R$<?=$value?></td>
             </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
  
<? } ?>

<!-- ------------- Formulário ----------------- -->
<form name="frm_pedido" method="post" action="<?=$PHP_SELF?>">
<input class="frm" type="hidden" name="pedido" value="<?=$pedido?>">
<input class="frm" type="hidden" name="callcenter" value="<?=$callcenter?>">

<?// HD 2471 - IGOR -PARA LATINATEC SOLICITARAM A MENSAGEM NO INICIO
if ($login_fabrica == 15) {?>
    <table width='700' align='center' border='0' cellspacing='3' cellpadding='3' class='formulario'>
        <tr>
            <td align='center'>
                Observação da Assistência Técnica:
            </td>
        </tr>
        <tr>
            <td align='center'>
                <input type="text" name="obs" size="50" value="<? echo $obs ?>" class="frm">
            </td>
        </tr>
    </table><?php
}?>

<table width='700' align='center' border='0' cellspacing='3' cellpadding='3' class='formulario'>
<tr class='titulo_tabela'><td colspan='3'>Cadastro de Pedido</td></tr>
<tr>
    <td width="10">&nbsp;</td>
    <td width="223"> Código ou CNPJ </td>
    <td> Razão Social </td>
</tr>

<tr>
    <?php
    if (in_array($login_fabrica,array(119)) and !empty($_REQUEST['callcenter'])) {
        $hd_chamado = (int) $_REQUEST["callcenter"];
        $qry_callcenter_posto = pg_query($con, "SELECT cnpj, tbl_posto.nome, posto
            FROM tbl_posto
            JOIN tbl_hd_chamado_extra USING(posto)
            WHERE hd_chamado = $hd_chamado");

        if (pg_num_rows($qry_callcenter_posto)) {
            $posto = pg_fetch_result($qry_callcenter_posto, 0, 'posto');
            $cnpj = pg_fetch_result($qry_callcenter_posto, 0, 'cnpj');
            $nome = pg_fetch_result($qry_callcenter_posto, 0, 'nome');
        }
    }
    if (in_array($login_fabrica,array(11,74,172)) and !empty($_REQUEST['callcenter'])) {
        $hd_chamado = (int) $_REQUEST["callcenter"];
        $ps = ($login_fabrica == 74) ? 386674 : 20682;
        $sql_callcenter_posto = "SELECT tbl_posto.nome, tbl_posto.cnpj, tbl_posto_fabrica.posto from tbl_posto_fabrica inner join tbl_posto on tbl_posto.posto = tbl_posto_fabrica.posto where tbl_posto_fabrica.posto = $ps and fabrica = $login_fabrica";
        $qry_callcenter_posto = pg_query($con, $sql_callcenter_posto);
        $retira_lupa = true;

        if (pg_num_rows($qry_callcenter_posto)) {
            $posto = pg_fetch_result($qry_callcenter_posto, 0, 'posto');
            $cnpj = pg_fetch_result($qry_callcenter_posto, 0, 'cnpj');
            $nome = pg_fetch_result($qry_callcenter_posto, 0, 'nome');
        }
        if ($login_fabrica == 74) {
            $cond_tipo_pedido = "  AND descricao = 'Cliente' ";
        }

        if (in_array($login_fabrica, [11,172])) {
            $r = 'readonly=readonly';
        }
    }
    ?>
    <td width="10">&nbsp;</td>
    <td width="215">
        <input type="hidden" name="posto_codigo" id="posto_codigo" value="<?=(!empty($dados['posto'])) ? $dados['posto'] : $posto?>" />
        <input type="text" name="cnpj" id="cnpj" size="18" maxlength="18" <?=$r?> value="<?=$cnpj?>" class="frm" style="width:150px" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_pedido.nome,document.frm_pedido.cnpj,'cnpj')" <? } ?>>&nbsp; <?php if($retira_lupa != true){ ?> <img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_pedido.nome,document.frm_pedido.cnpj,'cnpj')" style="cursor:pointer;"> <?php } ?>
    </td>
    <td>
        <input type="text" name="nome" size="50" maxlength="60" <?=$r?> value="<?=$nome?>" class="frm" style="width:300px" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_pedido.nome,document.frm_pedido.cnpj,'nome')" <? } ?>>&nbsp; <?php if($retira_lupa != true){ ?> <img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_pedido.nome,document.frm_pedido.cnpj,'nome')" style="cursor:pointer;"><?php } ?>
    </td>
</tr>

<tr><td>&nbsp;</td></tr>
<?if ($login_fabrica == 42){?>
<tr>
    <td width="10">&nbsp;</td>
    <td colspan='2'>Filial &nbsp;

        <select name="filial_posto" id="filial_posto" class='frm'>
            <option value=""></option>
            <?



            $sql = "SELECT
                        tbl_posto_fabrica.nome_fantasia,
                        tbl_posto_fabrica.posto,
                        tbl_posto.cnpj
                    FROM tbl_posto_fabrica
                    JOIN tbl_tipo_posto ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
                    JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
                    WHERE tbl_tipo_posto.fabrica=$login_fabrica
                    AND tbl_posto_fabrica.filial is true
                    AND tbl_posto_fabrica.posto <> 6359
                    order by posto
            ";

            $res = pg_query($con,$sql);

            if (pg_num_rows($res)>0){
                for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
                    $nome_fantasia = pg_fetch_result ($res,$i,'nome_fantasia');
                    $posto_distribuidor = pg_fetch_result ($res,$i,'posto');
                    $filialCnpj = pg_fetch_result($res, $i, 'cnpj');

                    $selected_filial = ($filial_posto == $posto_distribuidor) ? "SELECTED" : null;

                    echo "<option filialCnpj='$filialCnpj' value='$posto_distribuidor' $selected_filial>";
                        echo $nome_fantasia;
                    echo "</option>";
                }
            }
            ?>
        </select>
    </td>
</tr>

<tr><td>&nbsp;</td></tr>
<?
}
?>

<?if ($login_fabrica <> 5 AND $login_fabrica <> 7 AND $login_fabrica <> 50) {?>
    <tr class='subtitulo'>
        <td colspan='3' align='center'>
            Para efetuar um pedido por modelo do produto, informe a referência <br> ou descrição e clique na lupa, ou simplesmente clique na lupa.
        </td>
    </tr><?php
}?>

</table><?php



if ($login_fabrica == 3 or $telecontrol_distrib) {
    if(isset($_REQUEST['callcenter'])){
        $callcenter = $_REQUEST['callcenter'];
    }else{
        if($telecontrol_distrib){
            $sql = "select hd_chamado from tbl_hd_chamado_extra join tbl_hd_chamado using(hd_chamado) where pedido = $pedido and tbl_hd_chamado.fabrica = $login_fabrica";
            $res = pg_query($con,$sql);
            $callcenter = pg_fetch_result($res,0,hd_chamado);
            if(strlen($callcenter) == 0){
                $callcenter = "";
            }
        }else{
            $callcenter = "";
        }
    }
    if($callcenter != ""){
        $sql = "SELECT tbl_hd_chamado_extra.nome,tbl_hd_chamado.data from tbl_hd_chamado
        join tbl_hd_chamado_extra using(hd_chamado)
        where tbl_hd_chamado.hd_chamado = $callcenter";

        $res = pg_query($con,$sql);
        if(pg_num_rows($res) > 0 and pg_fetch_result($res,0,data) != ""){
            $nomeCliente = pg_fetch_result($res,0,nome);
            $dataChamado = pg_fetch_result($res,0,data);
            ?>
            <table width='700' align='center' border='0' cellspacing='3' cellpadding='3' class='formulario'>
                <tr>
                    <td align='center' ><b>Nome do cliente:</b> <?=$nomeCliente?></td>
                    <td align='center' ><b>Data do chamado:</b> <?=date('d-m-Y',strtotime($dataChamado))?></td>
                    <input type="hidden" name="callcenter" value="<?=$callcenter?>">
                </tr>
            </table>
            <?php
        }
    }
    ?>


    <table width='700' align='center' border='0' cellspacing='3' cellpadding='3' class='formulario'>
        <tr>
            <?php if (in_array($login_fabrica, [11,172])) { ?>
                    <td align='left' ><input type="radio" name="pedido_via_distribuidor" value='t'  checked> Atendimento Via Distribuidor</td>
            <?php } else { ?>
                    <td align='left' ><input type="radio" name="pedido_via_distribuidor" value='t' <? if ($pedido_via_distribuidor == 't') echo " checked "; ?>> Atendimento Via Distribuidor</td>
                    <td align='left' ><input type="radio" name="pedido_via_distribuidor" value='f' <? if ($pedido_via_distribuidor == 'f') echo " checked "; ?>> Atendimento DIRETO (via Fábrica)</td>
            <?php } ?>
        </tr>
    </table><?php
}?>

<table width='700' align='center' border='0' cellspacing='3' cellpadding='3' class='formulario'>
<tr>
    <td width="10">&nbsp;</td>
    <? if($login_fabrica<>7) echo '<td>'; else echo '<td>'; ?>
        Tipo do Pedido
    </td><?php
    if ($login_fabrica == 104) { ?>
        <td width="170"> Tipo de Peças</td>
    <?php
    }

    if ($login_fabrica == 7)
        $tam = 100;
    else
        $tam = 153;

    ?>
    <td width="<? echo $tam; ?>"> Tabela de Preços </td>

    <td> Condição de Pagamento </td><?php
    if ($login_fabrica == 7) {?>
        <td align='center'> Promocional </td>
        <td> Desconto </td>
<?php
    }
    if(in_array($login_fabrica, array(88,120,201))){
?>
        <td> Desconto (%)</td>
<?
    }

    if(!in_array($login_fabrica,array(50,94,95,98,99,101))){
    ?>
        <td> Tipo de Frete </td><?php
        if ($login_fabrica == 7) {?>
            <td align='center'> Valor do Frete </td><?php
        }
    }
    ?>

    <? if ($login_fabrica==50) { ?>
        <td width="*"> Pedido Cliente </td>
    <? } ?>
</tr>

<tr>
    <td width="10">&nbsp;</td>
    <td><?php

        $sql = "SELECT * FROM tbl_tipo_pedido WHERE fabrica = $login_fabrica";

        if ($login_fabrica == 3) {
            $sql = "SELECT * FROM tbl_tipo_pedido WHERE fabrica = $login_fabrica AND tipo_pedido IN (2,3)";
        }
        if ($login_fabrica == 5) {
            $sql = "SELECT * FROM tbl_tipo_pedido WHERE fabrica = $login_fabrica AND tipo_pedido in(41)";
        }
        if ($login_fabrica == 6) {
            $sql = "SELECT * FROM tbl_tipo_pedido WHERE fabrica = $login_fabrica AND tipo_pedido in(4,112)";
        }
        if ($login_fabrica == 50) {//HD 333889
            $sql = "SELECT * FROM tbl_tipo_pedido WHERE fabrica = $login_fabrica AND tipo_pedido in(129, 173)";
        }

        if($login_fabrica == 74){
            $sql = "SELECT * FROM tbl_tipo_pedido WHERE fabrica = $login_fabrica $cond_tipo_pedido";
        }

        if(in_array($login_fabrica,array(94,95,98,99,101))){
            $sql = "SELECT * FROM tbl_tipo_pedido WHERE fabrica = $login_fabrica AND upper(descricao) <> 'GARANTIA'";

        }
        $res = pg_query($con, $sql);

        if (pg_num_rows($res)) {
            $sl = "";
            $sll = "";
            if (in_array($login_fabrica, [11,172]) && !empty($callcenter)) {
                $tipo_pedido = ($login_fabrica == 11) ? 431 : 432;
                $sl = 'sl';
                $sll = " tabindex='-1' aria-disabled='true'";
            }

            echo "<select name='tipo_pedido' size='1' class='frm $sl' $sll>";
            echo "<option selected> </option>";

            for ($i = 0; $i < pg_num_rows($res); $i++) {
                echo "<option value='" . pg_fetch_result ($res, $i, 'tipo_pedido') . "' ";
                if ($tipo_pedido == pg_fetch_result($res, $i, 'tipo_pedido') ) echo " selected ";
                echo ">";
                echo pg_fetch_result ($res, $i, 'descricao');
                echo "</option>";
            }
            echo "</select>";
            if($login_fabrica == 50){
                echo "&nbsp;<img src='imagens/help.png' title='O tipo de pedido Doação poderá lançar apenas peças de devolução não obrigatória. O tipo de pedido Garantia poderá lançar apenas peças de devolução obrigatória'>";
            }
        }?>
    </td><?php
    if ($login_fabrica == 104) {
        //echo $tipo_peca;
        ?>
        <td>
            <select id='tipo_peca' name='tipo_peca' size='1' class='frm'>
                <option value='f' <?php if($tipo_peca == 'f'){ echo "selected";}?>>Peça</option>";
                <option value='t' <?php if($tipo_peca == 't'){ echo "selected";}?>>Acessório</option>";
            </select>
        </td>
    <?php
    }
        echo '<td width="100">';

            if ($login_fabrica == 7) {
                echo "<input name='tabela' size=5 id='tabela' value='$tabela' type='hidden'>Junto com <br>a Condição";
            } else{

                if (!$pedido){

                    $sql = "SELECT * FROM tbl_tabela WHERE fabrica = $login_fabrica AND ativa IS TRUE";
                    if ($login_fabrica == 50) {//HD 333889
                        $sql = "SELECT * FROM tbl_tabela WHERE fabrica = $login_fabrica AND ativa IS TRUE AND tabela = 396";
                    }

                    $res = pg_query($con,$sql);
                    if (pg_num_rows($res) > 0) {
                        $sl = "";
                        $sll = "";
                        if (in_array($login_fabrica, [11,172]) && !empty($callcenter)) {
                            $tabela = ($login_fabrica == 11) ? 34 : 1095;
                            $sl = 'sl';
                            $sll = " tabindex='-1' aria-disabled='true'";
                        }
                        echo "<select name='tabela' id='tabela' size='1' class='frm $sl' $sll>";
                        echo "<option selected> </option>";
                        if($login_fabrica != 74){
                            for ($i = 0; $i < pg_num_rows($res); $i++) {
                                echo "<option value='" . pg_fetch_result($res, $i, 'tabela') . "' ";
                                if ($tabela == pg_fetch_result($res, $i, 'tabela')) echo " selected ";
                                echo ">";
                                if($login_fabrica ==74 || $login_fabrica == 145){
                                    echo pg_fetch_result($res, $i, 'sigla_tabela')." - ".pg_fetch_result($res, $i, 'descricao');
                                }else{
                                    echo pg_fetch_result($res, $i, 'descricao');
                                }
                                echo "</option>";
                            }
                        }

                        echo "</select>";

                    }
                }else{

                    $sql = "SELECT tbl_tabela.tabela,tbl_tabela.descricao from tbl_pedido join tbl_tabela using(tabela) where tbl_pedido.pedido = $pedido";
                    $res = pg_query($con,$sql);

                    $tabela_desc = (pg_num_rows($res)>0) ? pg_fetch_result($res, 0, 1) : null ;
                    $tabela = (pg_num_rows($res)>0) ? pg_fetch_result($res, 0, 0) : null ;
                    echo "<label style='font:bold 12px Arial'>$tabela_desc</label>";
                    echo "<input type='hidden' name='tabela' id='tabela' value='$tabela' />";



                }

            }

        echo '</td>';

    ?>
    <td><?php

        if ($login_fabrica == 5) {
            $condicao1 = " AND visivel IS TRUE ";
        }

        $sql = "SELECT * FROM tbl_condicao WHERE fabrica = $login_fabrica $condicao1 ORDER BY lpad(trim(tbl_condicao.codigo_condicao)::text,10,'0');";

        if ($login_fabrica == 50) {//HD 333889
            $sql = "SELECT * FROM tbl_condicao WHERE fabrica = $login_fabrica AND condicao = 1025 ORDER BY lpad(trim(tbl_condicao.codigo_condicao)::text,10,'0');";
        }

        $res = pg_query($con,$sql);
        if (pg_num_rows($res) > 0) {
            $sl = "";
            $sll = "";
            if (in_array($login_fabrica, [11,172]) && !empty($callcenter)) {
                $condicao = ($login_fabrica == 11) ? 67 : 3593;
                $sl = 'sl';
                $sll = " tabindex='-1' aria-disabled='true'";
            }
            echo "<select style='min-width:80px;'name='condicao' size='1' class='frm $sl' $sll> ";
            echo "<option selected> </option>";
            for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
                $condMK = "";
                if ($login_fabrica == 42) {
                    $condMK = "condMK='".pg_fetch_result($res, $i, 'codigo_condicao')."'";
                }

                echo "<option $condMK value='" . pg_fetch_result ($res,$i,condicao) . "' ";
                if ($condicao == pg_fetch_result ($res,$i,condicao) ) echo " selected ";
                echo ">";
                echo pg_fetch_result ($res,$i,descricao);
                echo "</option>";
            }
            echo "</select>";
        }?>
    </td><?php
    if ($login_fabrica == 7) {?>
        <td align='center'>
            <input type='checkbox' name="promocao" id="promocao" value='t' <? if ($promocao == "t") echo " CHECKED " ?>>
        </td>
        <td align='center'><?php
        $sql = "SELECT * FROM tbl_desconto_pedido WHERE fabrica = $login_fabrica AND CURRENT_DATE >= data_vigencia AND termino_vigencia >= CURRENT_DATE ORDER BY data_vigencia";
        $res = pg_query ($con,$sql);
        if (pg_num_rows ($res) > 0) {
            echo "<select name='desconto' size='1' class='frm'>";
            echo "<option selected> </option>";
            for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
                echo "<option value='" . pg_fetch_result ($res,$i,desconto) . "' ";
                if ($desconto == pg_fetch_result ($res,$i,desconto) ) echo " selected ";
                echo ">";
                echo pg_fetch_result ($res,$i,desconto)." %";
                echo "</option>";
            }
            echo "</select>";
        } else {
            echo "<p>-</p>";
        }?>
        </td><?php
    }
    if(in_array($login_fabrica, array(88,120,201))){
?>
        <td>
            <input type="text" name="desconto" id="desconto" size="5" maxlength="6" value="<?=$desconto?>" class="frm" <?php echo ($login_fabrica == 88) ? "readonly='readonly'" : ""; ?> />
        </td>
<?
    }
    if(!in_array($login_fabrica,array(50,94,95,98,99,101))){
        ?>
            <td>
                <SELECT name="tipo_frete" size="1" onChange='javascript:verificaFrete(this)' class='frm'>
                    <option selected> </option><?php
                    if ($login_fabrica != 50) {//HD 333889?>
                        <option value="FOB" <? if ($tipo_frete == "FOB") echo " selected " ?> >FOB</option><?php
                    }?>
                    <option value="CIF" <? if ($tipo_frete == "CIF" or (empty($tipo_frete)) && in_array($login_fabrica, array(142))) echo " selected " ?> >CIF</option>
                </SELECT>
            </td>
        <?
    }
        # fabio colocar um campo de valor de frete que ira calcular automatico enquanto esta digitando o pedido. Quando CIF....a transportadora é correio, quando fob, pedir para incluir o codigo da transportadora do DATASUL.

            if ($login_fabrica==7) { ?>
                <td>
                    <span id='text_valor_frete'></span>
                    <input type='text' name="valor_frete" id="valor_frete" size='10' value='<?=$valor_frete?>' <? if ($tipo_frete == "FOB") echo " DISABLED " ?> class='frm'>
                </td>
        <?
            }


        if ($login_fabrica == 50) {?>
            <td>
                <input type="text" name="pedido_cliente" size="10" maxlength="20" value="<?php echo $pedido_cliente ?>" class="frm">
            </td>
        <? } ?>

</tr>
</table>

<? if($login_fabrica != 50){ ?>
<table class="formulario" width='700' align='center' border='0' cellspacing='1' cellpadding='3'>
    <tr>
        <td width="10">&nbsp;</td>
        <? if ($login_fabrica!=7 AND $login_fabrica!=50) { ?>
            <td width="*"> Pedido Cliente </td>
        <? } ?>
        <?
        if(!in_array($login_fabrica,array(50,95,98,99,101,104,105))){
            if($login_fabrica==7) $tam=113; else $tam=153; ?>
            <? if($login_fabrica <> 94){ ?>
                <td width="<? echo $tam; ?>"> Validade </td>
                <td> Entrega </td><?php
             }
            if ($login_fabrica == 94) { ?>
                <td width="490" > Forma De Envio </td><?php

            }
        }


            $sql = "SELECT  tbl_fabrica.pedido_escolhe_transportadora
                    FROM    tbl_fabrica
                    WHERE   tbl_fabrica.fabrica = $login_fabrica";

            $res = pg_query($con,$sql);

            if (pg_num_rows($res) > 0) {
                $pedido_escolhe_transportadora = trim(pg_fetch_result($res, 0, 'pedido_escolhe_transportadora'));
            }

            if ($pedido_escolhe_transportadora == 't' && $login_fabrica != 88) {?>
                <td> Transportadora </td><?php
            }

        ?>
    </tr>

    <tr>
        <td width="10">&nbsp;</td><?php
        if ($login_fabrica != 7 AND $login_fabrica != 50) {
            if(empty($pedido) AND !empty($hd_chamado)){

                $pedido_cliente = ($telecontrol_distrib) ? "at{$hd_chamado}" : $hd_chamado;

            }

            if ($login_fabrica == 119) {
                $pedido_cliente = '';
            }
        ?>
            <td>
                <input type="text" name="pedido_cliente" size="10" maxlength="20" value="<?=$pedido_cliente?>" class="frm">
            </td><?php
        }
        if(!in_array($login_fabrica,array(50,94,95,98,99,101,104,105))){
            if (strlen($validade) == 0) $validade = "10 dias";
            if (strlen($entrega) == 0)  $entrega  = "15 dias";?>

            <td>
                <input type="text" name="validade" size="10" maxlength="20" value="<? echo $validade ?>" class="frm">
            </td>
            <td>
                <input type="text" name="entrega" size="10" maxlength="20" value="<? echo $entrega ?>" class="frm">
            </td>
        <?php
        }
        if ($login_fabrica == 94) { ?>
            <td width="170"><?php
                echo "<select name='forma_envio' class='frm'>";
                echo "<option value=''>Selecione...</option>";
                $sql = " SELECT forma_envio, descricao
                        FROM tbl_forma_envio
                        WHERE fabrica = $login_fabrica
                        AND   ativo
                        ORDER BY descricao";
                $res = pg_query($con,$sql);
                if(pg_num_rows($res) > 0){

                    for($i =0;$i<pg_num_rows($res);$i++) {
                        $result_forma_envio = pg_fetch_result($res,$i,'forma_envio');
                        $selected = "";
                        if($forma_envio == $result_forma_envio){
                            $selected = "selected";
                        }
                        echo "<option ".$selected." value='".pg_fetch_result($res,$i,'forma_envio')."'>".pg_fetch_result($res,$i,'descricao')."</option>";
                    }
                }
                echo "</select>";?>
            </td><?php
        }

        if ($pedido_escolhe_transportadora == 't' && $login_fabrica != 88) {?>
            <td><?php

            if (strlen($transportadora) == 0)
            {
                $sql = "SELECT  tbl_transportadora.transportadora        ,
                            tbl_transportadora.cnpj                  ,
                            tbl_transportadora.nome                  ,
                            tbl_transportadora_fabrica.codigo_interno
                    FROM    tbl_transportadora
                    JOIN    tbl_transportadora_fabrica USING(transportadora)
                    WHERE   tbl_transportadora_fabrica.fabrica = $login_fabrica
                    AND     tbl_transportadora_fabrica.ativo  = 't' ";

                $res = pg_query($con,$sql);
            }
            else
            {
                $sql = "SELECT  tbl_transportadora.transportadora        ,
                            tbl_transportadora.cnpj                  ,
                            tbl_transportadora.nome                  ,
                            tbl_transportadora_fabrica.codigo_interno
                    FROM    tbl_transportadora
                    JOIN    tbl_transportadora_fabrica USING(transportadora)
                    WHERE   tbl_transportadora_fabrica.fabrica = $login_fabrica
                    AND     tbl_transportadora_fabrica.transportadora  = $transportadora ";

                $res = pg_query($con,$sql);

                $transportadora         = pg_fetch_result($res, 0, "transportadora");
                $transportadora_cnpj    = pg_fetch_result($res, 0, "cnpj");
                $transportadora_nome    = pg_fetch_result($res, 0, "nome");
                $transportadora_codigo  = pg_fetch_result($res, 0, "codigo_interno");
            }



            if (pg_num_rows($res) > 0) {

                if (pg_num_rows($res) <= 20 and strlen($transportadora) == 0) {

                    echo "<select name='transportadora' class='frm'>";
                    echo "<option selected></option>";
                    for ($i = 0; $i < pg_num_rows($res); $i++) {
                        echo "<option value='".pg_fetch_result($res, $i, 'transportadora')."' ";
                        if ($transportadora == pg_fetch_result($res, $i, 'transportadora') ) echo " selected ";
                        echo ">";
                        echo pg_fetch_result($res, $i, 'codigo_interno') ." - ".pg_fetch_result($res, $i, 'nome');
                        echo "</option>\n";
                    }
                    echo "</select>";

                } else {

                    echo "<input type='hidden' name='transportadora' value='$transportadora'>";

                    echo "<input type='text'   name='transportadora_codigo' size='6' maxlength='10' value='$transportadora_codigo' class='frm' "; if ($login_fabrica == 5) echo " onblur=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_codigo,'codigo')\"";

                    echo ">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_codigo,'codigo')\" style='cursor:pointer;'>";

                    echo "<input type='hidden' name='transportadora_cnpj' value='$transportadora_cnpj' class='textbox' >";

                    echo "<input type='text' name='transportadora_nome' size='15' maxlength='50' value='$transportadora_nome' class='frm' "; if ($login_fabrica == 5) echo " onblur=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_nome,'nome')\"";

                    echo ">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_nome,'nome')\" style='cursor:pointer;'>";

                }

            } else {

                echo " - - - ";

            }?>
            </td><?php
        }else if (in_array($login_fabrica,array(88,120,201))){
            echo "<input type='hidden' name='transportadora' id='transportadora' value='$transportadora'>";
        }

        if ($telecontrol_distrib) {
            $selected_sim = ($parcial == "t") ? "selected" : "";
            $selected_nao = ($parcial == "f") ? "selected" : "";
            echo "<td>";
                echo "<p><span class='coluna1'>Este pedido pode ser atendido parcial?</span>";
	    echo "<select name='parcial' class='frm'>";
	    	    echo "<option value=''></option>";
                    echo "<option value='t' $selected_sim>Sim</option>";
                    echo "<option value='f' $selected_nao>Não</option>";
                echo "</select>";
        }
        echo "</td>";
        ?>

        <?php
        if($login_fabrica == 120 or $login_fabrica == 201){
        ?>
            <td>
                Linha <br />
                <select name="linha" id="linha" class="linha">
                    <?php

                    $sql = "SELECT linha, nome
                            FROM tbl_linha
                            WHERE fabrica = $login_fabrica
                            AND ativo";
                    $res = pg_query($con,$sql);

                    if(pg_num_rows($res) > 0){

                        for($i = 0; $i < pg_num_rows($res); $i++){

                            $linha = pg_fetch_result($res, $i, "linha");
                            $nome = pg_fetch_result($res, $i, "nome");
                            $selected = ($_POST["linha"] == $linha) ? "SELECTED" : "";

                            echo "<option value='".$linha."' $selected >".$nome."</option>";

                        }

                    }

                    ?>
                </select>
            </td>
        <?php
        }
        ?>

    </tr>
</table><?php
}

if (!in_array($login_fabrica,array(15,94,95,98,99,101,104,105))) {?>
    <table class="formulario" width='700' align='center' border='0' cellspacing='3' cellpadding='3'>
        <tr>
            <td width="10">&nbsp;</td>
            <td> Mensagem </td>
        </tr>
        <tr>
            <td width="10">&nbsp;</td>
            <td>
            <?php if (in_array($login_fabrica, [42]) && !isset($_POST['obs'])) {
                $obs = json_decode(utf8_encode($obs), true)['observacao'];
            } ?>
                <input type="text" name="obs" size="80" value="<? echo $obs ?>" class="frm">
            </td>
        </tr>
    </table><?php
}
if ($login_fabrica == 136 AND !empty($produto_referencia_lupa)) { ?>
<table class="formulario" width='700' align='center' border='0' cellspacing='3' cellpadding='3'>
    <TR>
        <td width="10">&nbsp;</td>
        <TD>Referência</TD>
        <TD>Descrição</TD>
</TR>
<TR>
    <td width="10">&nbsp;</td>
    <TD>
        <INPUT TYPE="text" NAME="produto_referencia_lupa" SIZE="20" class='frm' value="<? echo $produto_referencia_lupa ?>">
        <INPUT TYPE="hidden" NAME="referencia" SIZE="20" class='frm' value="<? echo $produto_referencia_lupa ?>">
    </TD>
    <TD>
        <INPUT TYPE="text" NAME="produto_nome_lupa" size="37" class='frm' value="<? echo $produto_nome_lupa ?>">
    </TD>

</TR>
</table><?php
}

if ($login_fabrica == 7) {?>
    <br>
    <input class='frm' type='button' onClick='javascript:calcular_frete();' value='Atualizar Valor do Frete'><?php
}?>
<br />
<table width="100%">
    <tr>
        <td align="center"><?php
    if (in_array($login_fabrica, array(87,85,106))) {
        $width = 700;
    } else {
        $width = 1000;
    }?>

            <table width="<?=$width?>" border="0" cellspacing="2" cellpadding="0" align='center' id="itens_pedido" class='formulario'>
                <tr height="20" class="titulo_coluna">
                    <?php
                    if($telecontrol_distrib or $login_fabrica == 50){
                    ?>
                    <td>Ref. Produto</td>
                    <td>Descrição do Produto</td>
                    <td>Lista Basica</td>
                    <?php
                    }
                     ?>
                    <td>Ref. Componente</td>
                    <td>Desc. Componente</td>
                    <td width="69px">Qtde</td>
                    <td width="69px">Preço Unit.</td>
                    <?php if ($login_fabrica == 42) { ?>
                            <td readonly width="69px">IPI</td>
                            <td readonly width="69px">ICMS</td>
                            <td readonly width="69px">ICMS ST</td>
                            <td readonly width="69px">PIS</td>
                            <td readonly width="69px">COFINS</td>
                    <?php } ?>
                    <td width="69px">Total</td>
                </tr>
<?php
    if (strlen($pedido) > 0) {
        $sql = "SELECT  tbl_peca.peca
                FROM    tbl_pedido_item
                JOIN    tbl_peca   USING (peca)
                JOIN    tbl_pedido USING (pedido)
                WHERE   tbl_pedido_item.pedido = $pedido
          ORDER BY      tbl_peca.referencia, tbl_pedido_item.pedido_item;";

        $ped = pg_query($con,$sql);
        $qtde_peca = pg_num_rows($ped);
    }

        //HD-900300
    if(empty($qtde_peca)){
        if($qtde_itens_totais>$qtde_peca){
            $qtde_item = (($qtde_itens_totais-$qtde_peca)+($qtde_peca*2));
        }else{
            $qtde_item = (($qtde_peca-$qtde_itens_totais)+($qtde_peca*2));
        }
    }else{
        if($qtde_itens_totais>$qtde_peca){
            $qtde_item = (($qtde_itens_totais-$qtde_peca)+($qtde_peca*2));
        }else{
            $qtde_item = (($qtde_peca-$qtde_itens_totais)+($qtde_peca*2));
        }
    }
    echo "<input type=\"hidden\" value=\"". $qtde_item ."\" id=\"formValorTotal\" name=\"qtde_item\"/>";
    for ($i = 0; $i < $qtde_item; $i++) {
        if (strlen($pedido) > 0) {
            if ($qtde_peca > $i) {
                $peca = trim(pg_fetch_result($ped,$i,'peca'));
            } else {
                $peca='';
            }
            if (strlen($peca) > 0) {
                $sql = "SELECT  tbl_pedido_item.pedido_item,
                                tbl_pedido_item.qtde       ,
                                tbl_pedido_item.preco      ,
                                tbl_peca.referencia        ,
                                tbl_peca.origem            ,
                                tbl_peca.descricao
                        FROM    tbl_pedido_item
                        JOIN    tbl_peca USING (peca)
                        WHERE   tbl_pedido_item.pedido = $pedido
                        AND     tbl_pedido_item.peca   = $peca
                  ORDER BY      tbl_peca.referencia";

                $aux_ped         = pg_query($con,$sql);
                $novo            = 'f';
                $item            = trim(pg_fetch_result($aux_ped, 0, 'pedido_item'));
                $peca_referencia = trim(pg_fetch_result($aux_ped, 0, 'referencia'));
                $peca_descricao  = trim(pg_fetch_result($aux_ped, 0, 'descricao'));
                $qtde            = trim(pg_fetch_result($aux_ped, 0, 'qtde'));
                $preco           = trim(pg_fetch_result($aux_ped, 0, 'preco'));
                $origem          = trim(pg_fetch_result($aux_ped, 0, 'origem'));
            } else {

                $novo               = 't';
                $item               = $HTTP_POST_VARS["item".     $aux];
                $produto_referencia = $HTTP_POST_VARS["produto_referencia_" . $i];
                $produto_descricao  = $HTTP_POST_VARS["produto_descricao_" . $i];
                $peca_referencia    = $HTTP_POST_VARS["peca_referencia_" . $i];
                $peca_descricao     = $HTTP_POST_VARS["peca_descricao_"  . $i];
                $qtde               = $HTTP_POST_VARS["qtde_"            . $i];
                $preco              = $HTTP_POST_VARS["preco_"           . $i];
                $unidade            = $HTTP_POST_VARS["peca_unidade_" . $i];
                $logItem            = $HTTP_POST_VARS["logItem_" . $i];
                $precoUnitLiq       = $HTTP_POST_VARS["precoUnitLiq_" . $i];
                $precoUnitImp       = $HTTP_POST_VARS["precoUnitImp_" . $i];
                $ipi                = $HTTP_POST_VARS["ipi_  "           . $i];
                $icms               = $HTTP_POST_VARS["icms_  "          . $i];
                $icmsSt             = $HTTP_POST_VARS["icmsSt_  "        . $i];
                $pis                = $HTTP_POST_VARS["pis_  "           . $i];
                $cofins             = $HTTP_POST_VARS["cofins_  "        . $i];
                $sub_total          = $HTTP_POST_VARS["sub_total_  "        . $i];

            }

        } else {
            $novo               = 't';
            $item               = $_POST["item".$aux];
            $produto_referencia = $_POST["produto_referencia_".$i];
            $produto_descricao  = $_POST["produto_descricao_".$i];
            $peca_referencia    = $_POST["peca_referencia_".$i];
            $peca_descricao     = $_POST["peca_descricao_".$i];
            $qtde               = $_POST["qtde_".$i];
            $preco              = $_POST["preco_".$i];
            $unidade            = $_POST["peca_unidade_".$i];
            $precoUnitLiq       = $_POST["precoUnitLiq_".$i];
            $precoUnitImp       = $_POST["precoUnitImp_".$i];
            $ipi                = $_POST["ipi_".$i];
            $icms               = $_POST["icms_".$i];
            $icmsSt             = $_POST["icmsSt_".$i];
            $pis                = $_POST["pis_".$i];
            $cofins             = $_POST["cofins_".$i];
            $sub_total          = $_POST["sub_total_".$i];
        }

        $qtdeDisabled = "";

        if ((strlen($peca) > 0) AND (strlen($pedido)) ){
            $cmdSQLSelectCancelado = "SELECT * FROM tbl_pedido_cancelado WHERE peca = ".$peca." AND pedido = ".$pedido.";";
            $resSelectCancelado = pg_query($con, $cmdSQLSelectCancelado);
            $fetchAllCancelado = pg_fetch_all($resSelectCancelado);
            $qtdeDisabled = (!empty($fetchAllCancelado[0]['pedido'])) ? "disabled = 'disabled'" : "";
        }

        $cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
?>
                <input type="hidden" name="novo<?=$i?>" value="<?=$novo?>" />
                <input type="hidden" name="item<?=$i?>" value="<?=$item?>" />
                <tr <? if ($linha_erro == $i and strlen ($msg_erro) > 0) {echo "bgcolor='#ffcccc'";} else {echo "bgcolor='$cor'";} ?> class="itensLinha" name='linha_<?=$i?>'>
                    <input type="hidden" name="peca_unidade_<?=$i?>" id="peca_unidade_<?=$i?>" value="<?=$peca_unidade?>">
                    <input type="hidden" name="logItem_<?=$i?>" id="logItem_<?=$i?>" value="<?=$logItem?>">
                    <input type="hidden" name="precoUnitLiq_<?=$i?>" id="precoUnitLiq_<?=$i?>" value="<?=$precoUnitLiq?>">
                    <input type="hidden" name="precoUnitImp_<?=$i?>" id="precoUnitImp_<?=$i?>" value="<?=$precoUnitImp?>">
<?php
        if ($login_fabrica == 50 or $telecontrol_distrib) {//HD 333889?>
                    <td align='center' nowrap="nowrap"><input class="frm" type="text" name="produto_referencia_<?=$i?>" size="15" rel='peca' alt='<?=$i?>' value="<?= $produto_referencia?>" /><img id='lupa_produto_referencia_<?=$i?>' src='imagens/lupa.png' alt="Clique para pesquisar por referência do produto" border='0' hspace='5' align='absmiddle' onclick="fnc_pesquisa_produto(document.frm_pedido.produto_referencia_<?=$i?>, document.frm_pedido.produto_descricao_<?=$i?>, 'referencia')" style="cursor:pointer;" value="<?=$produto_referencia?>" /></td>
                    <td align='center' nowrap="nowrap"><input class="frm" type="text" name="produto_descricao_<?=$i?>" size="30" rel='peca' alt='<?=$i?>' value="<?= $produto_descricao?>" /><img id='lupa_produto_descricao_<?=$i?>' src='imagens/lupa.png' alt="Clique para pesquisar por descrição do produto" border='0' hspace='5' align='absmiddle' onclick="fnc_pesquisa_produto(document.frm_pedido.produto_referencia_<?=$i?>, document.frm_pedido.produto_descricao_<?=$i?>, 'descricao')" style="cursor:pointer;" value="<?=$produto_descricao?>" /></td>
                    <?php
                    if($telecontrol_distrib){
                        ?>
                        <td align="center"><img src="imagens/btn_lista.gif" border="0" style="cursor:pointer" onclick="fnc_pesquisa_lista_basica(document.frm_pedido.produto_referencia_<?=$i?>, document.frm_pedido.peca_referencia_<?=$i?>, document.frm_pedido.peca_descricao_<?=$i?>, 'referencia', document.frm_pedido.preco_<?=$i?>, <?=$i?>)" /></td>
                        <?php

                    }else{
                        ?>
                        <td align="center"><img src="imagens/btn_lista.gif" border="0" style="cursor:pointer" onclick="fnc_pesquisa_lista_basica(document.frm_pedido.produto_referencia_<?=$i?>, document.frm_pedido.peca_referencia_<?=$i?>, document.frm_pedido.peca_descricao_<?=$i?>, 'referencia', document.frm_pedido.preco.value, <?=$i?>)" /></td>
                        <?php
                    }
        }

        if (in_array($login_fabrica, $usam_preco_total) or  1==1) {
?>

                    <td align='center' nowrap="nowrap">
                        <input class="frm" type="text" name="peca_referencia_<? echo $i ?>" size="15" rel='peca' alt='<?=$i?>' value="<? echo $peca_referencia ?>"z >
                        <img id='lupa_peca_referencia_<? echo $i ?>' src='imagens/lupa.png' alt="Clique para pesquisar por referência do componente" border='0' hspace='5' align='absmiddle' <?php
                        if ($login_fabrica == 136 and !empty($produto_referencia_lupa)) {?>
                            onclick="pesquisaPeca (window.document.frm_pedido.peca_referencia_<? echo $i ?>,'peca',<?=$i?>)" style="cursor:pointer;">
                        <?php
                        }else{?>
                            onclick="fnc_pesquisa_peca_preco (window.document.frm_pedido.peca_referencia_<? echo $i ?> , window.document.frm_pedido.peca_descricao_<? echo $i ?> , window.document.frm_pedido.preco_<? echo $i ?> , 'referencia')" style="cursor:pointer;">
                        <?php
                        }?>
                    </td>

                    <td align='center' nowrap="nowrap">
                        <input class="frm" type="text" name="peca_descricao_<? echo $i ?>" size="30" value="<? echo $peca_descricao ?>" >
                        <img id='lupa_peca_descricao_<? echo $i ?>' src='imagens/lupa.png' alt="Clique para pesquisar por descrição do componente" border='0' hspace='5' align='absmiddle'
                        <?php
                        if ($login_fabrica == 136 and !empty($produto_referencia_lupa)) {?>
                            onclick="pesquisaPeca (window.document.frm_pedido.peca_descricao_<? echo $i ?> ,'descricao',<?=$i?>)" style="cursor:pointer;">
                        <?php
                        }else{?>
                            onclick="fnc_pesquisa_peca_preco ( window.document.frm_pedido.peca_referencia_<? echo $i ?> , window.document.frm_pedido.peca_descricao_<? echo $i ?>,window.document.frm_pedido.preco_<? echo $i ?> ,'descricao')" style="cursor:pointer;">
                        <?php
                        }?>

                    </td>

                    <td align='center'>
<?php
            if(!empty($qtdeDisabled)){
 ?>
                        <input type="hidden" name="qtde_<? echo $i ?>" value="<? echo $qtde ?>">
                    <input class="frm" type="text" size="5" <?php echo $qtdeDisabled; ?>  value="<? echo $qtde ?>" style="text-align:right" onblur="fnc_calcula_total(<?=$i?>);">
<?php
            }else{
?>
                        <input class="frm" type="text" name="qtde_<? echo $i ?>" size="5" alt='<?=$i?>' <?php echo $qtdeDisabled; ?>  value="<? echo $qtde ?>" style="text-align:right" onblur="fnc_calcula_total($(this).attr('alt'));adiciona_linha($(this).attr('alt'));">
<?php
            }
?>
                    </td>
<?php
        }else{
?>
                    <td align='center' nowrap="nowrap">
                        <input class="frm" type="text" name="peca_referencia_<? echo $i ?>" size="15" rel='peca' alt='<?=$i?>' value="<? echo $peca_referencia ?>" onblur='<? if ($login_fabrica == 5) { echo " document.frm_pedido.lupa_peca_referencia_$i.click(); " ; } ?>;' ><img id='lupa_peca_referencia_<? echo $i ?>' src='imagens/lupa.png' alt="Clique para pesquisar por referência do componente" border='0' hspace='5' align='absmiddle' onclick="fnc_pesquisa_peca (window.document.frm_pedido.peca_referencia_<? echo $i ?> , window.document.frm_pedido.peca_descricao_<? echo $i ?>, 'referencia')" style="cursor:pointer;">
                    </td>
                    <td align='center' nowrap="nowrap">
                        <input class="frm" type="text" name="peca_descricao_<? echo $i ?>" size="30" value="<? echo $peca_descricao ?>" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_peca ( window.document.frm_pedido.peca_referencia_<? echo $i ?> , window.document.frm_pedido.peca_descricao_<? echo $i ?>,'descricao')" <? } ?>><img id='lupa_peca_descricao_<? echo $i ?>' src='imagens/lupa.png' alt="Clique para pesquisar por descrição do componente" border='0' hspace='5' align='absmiddle' onclick="fnc_pesquisa_peca ( window.document.frm_pedido.peca_referencia_<? echo $i ?> , window.document.frm_pedido.peca_descricao_<? echo $i ?>,'descricao')" style="cursor:pointer;">
                    </td>

                    <td align='center'>
<?php
            if(!empty($qtdeDisabled)){
?>
                        <input type="hidden" name="qtde_<? echo $i ?>" value="<? echo $qtde ?>">
                        <input class="frm" type="text" size="5" <?php echo $qtdeDisabled; ?>  value="<? echo $qtde ?>" style="text-align:right" onblur="fnc_calcula_total(<?=$i?>);">
<?php
            }else{
?>
                        <input class="frm" type="text" name="qtde_<? echo $i ?>" size="5" <?php echo $qtdeDisabled; ?>  value="<? echo $qtde ?>" style="text-align:right" onblur="fnc_calcula_total(<?=$i?>);">
<?php
            }
?>
                    </td>
<?
        }
?>
<?
            if (in_array($login_fabrica, array(10,81,122,123,125,114,128,119))){
              $preco_exibe = $preco;
?>
                <td align="center">
                  <input type="text" name="preco_<?=$i?>" id="preco_<?=$i?>" class="frm" style="width:55px;text-align:right" value="<? echo $preco_exibe ?>" onblur="fnc_calcula_total(<?=$i?>);" >
                </td>

                <!-- <td align="center">
                    <input type="text" name="preco_<?=$i?>" id="preco_<?=$i?>" class="frm" style="width:55px;text-align:right" value="<? echo $preco_exibe ?>" readonly="readonly" >
                </td> -->
            <td align="center">
<?php

            if (!empty($qtde) && !empty($preco)) {
                $preco = str_replace(',', '.', $preco);

                $total_geral += $preco * $qtde;
                $total_peca  = $preco * $qtde;
                $total_peca = str_replace('.', ',', $total_peca);
            }else{
                $total_peca  = null;
            }
?>
                        <input type="text" name="sub_total_<?=$i?>" id="sub_total_<?=$i?>" class="frm" style="text-align:right;width:55px" readonly="readonly" rel='total_pecas' value="<?=$total_peca?>" >
                    </td>
<?
        }else{
?>

<?
        if (in_array($login_fabrica, $usam_preco_total)  or 1 == 1) {
            $preco_exibe = str_replace('.', ',', $preco);
?>
                    <td align="center">
                        <input type="text" name="preco_<?=$i?>" id="preco_<?=$i?>" class="frm" style="width:55px;text-align:right" value="<? echo $preco_exibe ?>" readonly="readonly" >
                    </td>
                <?php if ($login_fabrica == 42) { ?>
                        <td align="center">
                            <input type="text" name="ipi_<?=$i?>" id="ipi_<?=$i?>" class="frm impostos valorInput_<?=$i?>" style="width:55px;text-align:right" value="<?=$ipi?>" readonly="readonly" >
                        </td>
                        <td align="center">
                            <input type="text" name="icms_<?=$i?>" id="icms_<?=$i?>" class="frm impostos valorInput_<?=$i?>" style="width:55px;text-align:right" value="<?=$icms?>" readonly="readonly" >
                        </td>
                        <td align="center">
                            <input type="text" name="icmsSt_<?=$i?>" id="icmsSt_<?=$i?>" class="frm impostos valorInput_<?=$i?>" style="width:55px;text-align:right" value="<?=$icmsSt?>" readonly="readonly" >
                        </td>
                        <td align="center">
                            <input type="text" name="pis_<?=$i?>" id="pis_<?=$i?>" class="frm impostos valorInput_<?=$i?>" style="width:55px;text-align:right" value="<?=$pis?>" readonly="readonly" >
                        </td>
                        <td align="center">
                            <input type="text" name="cofins_<?=$i?>" id="cofins_<?=$i?>" class="frm impostos valorInput_<?=$i?>" style="width:55px;text-align:right" value="<?=$cofins?>" readonly="readonly" >
                        </td>
                <?php } ?>

                    <td align="center">
<?php
    
             if ($login_fabrica == 42) {
                $total_peca  = $sub_total;
                $sub_totalVl = trim(str_replace('R$', '', $sub_total));
                $sub_totalVl = trim(str_replace(',', '.', $sub_totalVl));
                $sub_totalVl = floatval(preg_replace("/[^-0-9\.]/","",$sub_totalVl));
                $total_geral += $sub_totalVl;
            } else {
                if (!empty($qtde) && !empty($preco)) {
                    $preco = str_replace(',', '.', $preco);

                    $total_geral += $preco * $qtde;
                    $total_peca  = $preco * $qtde;
                    $total_peca = str_replace('.', ',', $total_peca);
                }else{
                    $total_peca  = null;
                }
            }

?>
                        <input type="text" name="sub_total_<?=$i?>" id="sub_total_<?=$i?>" class="frm" style="text-align:right;width:55px" readonly="readonly" rel='total_pecas' value="<?=$total_peca?>" >
                    </td>
<?
        }
    }
?>
                </tr>
<?
}
                if(in_array($login_fabrica, array(120,201))){
                    $total_geral = $total_geral - ($total_geral * ($desconto / 100));
                }
?>
            </table>
        <div id="formItens">
        </div>
        </td>
    </tr>

<?php
    $valor_total_frete = $aux_valor_frete + $total_geral;
    $valor_total_frete = number_format($valor_total_frete,2,',','');
    $total_geral       = number_format($total_geral,2, ',', '');
?>
    <tr >
        <td>

            <?php if($login_fabrica == 88){ ?>
            <p style="text-align: center;">
                (O desconto está sendo aplicado em todos os cálculos.)
            </p>
            <?php } ?>

            <label style="font:bold 12px Arial">Total:</label>
            <input type="text" name="total_pecas" id="total_pecas" style="width:120px;text-align:right"  readonly="readonly" value="<?=$total_geral?>" class="frm" >
        </td>
    </tr>
<?
        if(in_array($login_fabrica,array(88,120,201))){
?>
    <tr >
        <td>
            <label style="font:bold 12px Arial">Valor Frete:</label>
            <input type="text" name="valor_frete" id="valor_frete" style="width:120px;text-align:right"  readonly="readonly" value="<?=$valor_frete?>" class="frm" >
            <input type="hidden" name="valor_frete_hidden" id="valor_frete_hidden" value="<?=$valor_frete?>" >
        </td>
    </tr>
    <tr >
        <td>
            <label style="font:bold 12px Arial">Total + Frete:</label>
            <input type="text" name="valor_total_frete" id="valor_total_frete" style="width:120px;text-align:right"  readonly="readonly" value="<?=$valor_total_frete?>" class="frm" >
        </td>
    </tr>
<?
        }
?>
<?
        if($login_fabrica == 101){
?>
    <tr>
        <td>
            <input type="hidden" id="numeroObs" value="<?php echo ($numero+1);?>" />
            <a class="button pequeno azul" href="javascript:void(0)" onclick="addObs()" ><span>Adicionar Observação</span></a>
        </td>
    </tr>
    <tr id="obs" style="display:none;">
        <td>
            <table border="0" cellspacing="2" cellpadding="0" align='center' class='formulario'>
                <tr id="obs" >
                    <td>
                        <textarea name="obs[]" id="observacao" cols="50" rows="6" wrap="phisical" onKeyDown="textCounter(this,document.forms[0].remLen,500);" onKeyUp="textCounter(this,document.forms[0].remLen,500);"></textarea>
                    </td>
                    <td valign="middle" align="center">
                        <a href="javascript:void(0)" style="color: #11F !important;" onclick="document.getElementById('obs').style.display='none'"> X </a>
                    </td>
                </tr>
                <tr>
                    <td valign="middle" align="center">
                        faltam&nbsp;<input readonly type=text name=remLen size="3" maxlength="3" value="500"></font>
                    </td>
                    <td colspan="3" style="text-align:right;">
                        <a class="button pequeno azul" href="javascript:void(0)" onclick="salvaObs()" ><span>Salvar Observação</span></a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td>
            <table border="0" cellspacing="2" cellpadding="0" align='center' id='frm_obs' class="formulario">
                <thead>
                <tr>
                    <!--<th>id</th>-->
                    <th>Data</th>
                    <th>Observação</th>
                    <th>Autor</th>
                    <th>Excluir</th>
                </tr>
                </thead>
                <tbody>
<?
echo $obs;
?>
                </tbody>
            </table>
        </td>
    </tr>
<?
    }
?>
</table>
<br>
<tr>
    <td><img height="1" width="16" src="imagens/spacer.gif"></td>
</tr>
<tr>
    <td height="27" valign="middle" align="center" colspan="3" >
        <input type='hidden' name='btn_acao' value=''>
<?
        if(in_array($login_fabrica,array(88,120,201))){
?>
        <input type='hidden' name='frete_calculado' id='frete_calculado' value='<?=strlen($valor_frete) > 0 ? "sim" : ""?>'>
        <button type="button" value="" id='calcular_frete' title="Calcular Frete">Calcular Frete</button>
<?
        }

        if ($login_fabrica == 42) {
            $escondeGravar = "style='display: none;'";
?>
            <input type="button" value="Calcular Valores" id="btn-calcula-imposto" style="cursor: pointer" />
<?php
        }
?>
        <button type="button" value="" id='gravar' title="Gravar formulário" <?=$escondeGravar?>>Gravar</button>
        <? if(!in_array($login_fabrica,array(74,85,94,95,98,99,101,104,105,108,111,106,123,125,127))){?>
                <button type="button" value="" id='apagar' title="Apagar Pedido">Apagar</button>
                <button type="button" value="" id='reset'  title="Limpar formulário">Limpar</button><?php
            }
        if ($login_fabrica == 24 and in_array($status_pedido, array(2, 3, 9, 14, 17, 18))) {
            $chkd = ($reexportar == 't') ? ' checked':'';   ?>
            <span style='font-size:11px'>Pedido <?=$status_pedido_desc?></span>
            <input type="checkbox" value="t" id='reexportar'<?=$chkd?> name='reexportar' title="Exportar novamente o pedido">
            <label style='font-size:11px' for="reexportar">Exportar novamente</label><?php
        }?>
    </td>
</tr>
<input type="hidden" name="preco" id="preco" />
</table>
</form>
<script type="text/javascript">
<?
if(in_array($login_fabrica,array(88,120,201))){
?>
    $('button[id!=calcular_frete]').click(function() {
<?
}else{
?>
    $('button').click(function() {
<?
}
?>
        var id       = $(this).attr('id');
        var btn_acao = $('input[name=btn_acao]');
        if (btn_acao.val() != '') {
            alert ('Dados já gravados. Se você clicou em Voltar no seu browser ou clicou mais de uma vez no botão, acesse novamente a tela pelos Menus do sistema.');
            return false;
        }
        if (id == 'limpar') {
            window.location.reload();
        } else {
            btn_acao.val(id);
            document.frm_pedido.submit();
        }
    });
</script>

<? include "rodape.php"; ?>
