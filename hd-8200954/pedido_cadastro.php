<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';

include_once __DIR__ . '/class/AuditorLog.php';

$token_cookie = $_COOKIE['sess'];
$cookie_login = get_cookie_login($token_cookie);
require_once 'class/email/mailer/class.phpmailer.php';
include_once './class/communicator.class.php';
use Posvenda\Pedido;
include_once './email_pedido.php';

$vet_ipi = array(94,101,104,105,106, 115, 116,117,120,121,122,123,124,126,127,128,129,131,134,136,138,140,141,144,145);

if ($S3_sdk_OK) {
    include_once S3CLASS;
}

if (isFabrica(141,144)) {
    # A class AmazonTC está no arquivo assist/class/aws/anexaS3.class.php
    $amazonTC = new AmazonTC("pedido", $login_fabrica);
}

$login_bloqueio_pedido = $cookie_login['cook_bloqueio_pedido'];

/**
 * Fábricas com tela própria de pedido
 */
$fabrica_link_pedido = array(
	01 => 'pedido_blackedecker_cadastro.php',
	03 => 'pedido_cadastro_normal.php',
	06 => 'pedido_cadastro_normal.php',
	30 => 'pedido_cadastro_normal.php',
	42 => 'pedido_makita_cadastro.php',
	46 => 'pedido_vista_explodida.php',
	87 => 'pedido_jacto_cadastro.php', //HD 373202
	93 => 'pedido_blacktest_cadastro.php'
);

if ($link = $fabrica_link_pedido[$login_fabrica]) {
    header('Location: '. $link);
    exit;
}

if ($telaPedido0315) {
    header("Location: cadastro_pedido.php");
    exit;
}

if (isFabrica(138)) {
    $sql_desconto = "SELECT desconto FROM tbl_posto_fabrica WHERE posto = {$login_posto} AND fabrica  = {$login_fabrica}";
    $res_desconto = pg_query($con, $sql_desconto);
    $desconto_posto = (pg_num_rows($res_desconto) > 0) ? pg_fetch_result($res_desconto, 0, 'desconto') : 0;
}


if($verifica_estoque_peca_kit == true){

    $referencias = $_POST["referencias"];
    $pecaSemEstoque = array();
    $temkit = false;

    foreach($referencias as $value){
        $value = explode("-", $value);

        $sqlTemKit = "SELECT tbl_kit_peca_peca.kit_peca from tbl_kit_peca_peca join tbl_peca on tbl_peca.peca = tbl_kit_peca_peca.peca where  tbl_peca.referencia ='".$value[0]."' ";
        $resTemKit = pg_query($con, $sqlTemKit);
        if(pg_num_rows($resTemKit)==0){
            continue;
        }

        $sqlVerEstoquePeca = "SELECT tbl_posto_estoque.* 
                                from tbl_posto_estoque 
                                join tbl_peca on tbl_peca.peca = tbl_posto_estoque.peca where tbl_peca.referencia = '".$value[0]."' and posto = 4311 and  tbl_posto_estoque.qtde >= ".$value[1];
        $resVerEstoquePeca = pg_query($con, $sqlVerEstoquePeca);
        if(pg_num_rows($resVerEstoquePeca)==0){
            $pecaSemEstoque[] = $value[0];
        }
    }

        $referencia_peca = implode("', '", $pecaSemEstoque);

        $sqlkit = "SELECT DISTINCT tbl_kit_peca.peca as peca_kit, 
                    tbl_kit_peca.descricao as descricao_kit,
                    tbl_kit_peca.referencia  
                    FROM tbl_kit_peca
                    JOIN tbl_kit_peca_peca ON tbl_kit_peca.kit_peca = tbl_kit_peca_peca.kit_peca
                    join tbl_peca on tbl_kit_peca_peca.peca = tbl_peca.peca and tbl_peca.fabrica = $login_fabrica
                    WHERE tbl_kit_peca.fabrica = $login_fabrica
                    AND tbl_peca.referencia in ('". $referencia_peca."')" ;
        $reskit =pg_query($con, $sqlkit);
  
        for($z=0; $z<pg_num_rows($reskit); $z++){
            $peca_principal_kit = pg_fetch_result($reskit, $z, 'peca_kit');
            $descricao_kit = pg_fetch_result($reskit, $z, 'descricao_kit');
            $referencia_peca_kit = pg_fetch_result($reskit, $z, 'referencia');

            $sqlVerEstoqueKit = "SELECT * FROM tbl_posto_estoque WHERE posto = 4311 and qtde > 0  and peca = $peca_principal_kit";
            $resVerEstoquekit = pg_query($con, $sqlVerEstoqueKit);
            if(pg_num_rows($resVerEstoquekit)>0){
                $temkit = true;
                $estoque = pg_fetch_result($resVerEstoquekit, 0, 'qtde');

                $arrkit['kit'][]['referencia']= $referencia_peca_kit;
                $arrkit['descricao'][$referencia_peca_kit] = $descricao_kit;
                $arrkit['estoque'][$referencia_peca_kit] = $estoque;
            }
        }
        if($temkit == true){
            $arrkit['pecas'] = $pecaSemEstoque;
            $arrkit['semEstoque'] = true;
        }else{
            $arrkit['semEstoque'] = false;
        }        
       
        echo json_encode($arrkit);

    
    
    exit;
}

if (isset($_POST["busca_condicao_pagamento"])){
        
    $valor = $_POST['valor'];
    $total = $_POST['total_pedidos'];

    if(empty($valor)){
        die(json_encode(["erro" => "Parâmetro valor não informado"]));
    }else{
        $valor = moneyDb($valor);
    }

    if(!empty($total)){
        $total = moneyDb($total);
        $valor += $total;
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

if(isset($_POST['busca_qtde_parcelas'])){

    $condicao = $_POST['condicao'];

    if(empty($condicao)){
        exit(json_encode(['result' => false]));
    }

    $sql = "SELECT parcelas FROM tbl_condicao WHERE fabrica = $login_fabrica AND condicao = $condicao";
    $qry = pg_query($con, $sql);

    exit(json_encode(['result' => pg_fetch_result($qry, 0, "parcelas")]));
}

if (isset($_POST["verifica_tipo_pedido"]) && $_POST["verifica_tipo_pedido"] == "ok") {

    $tipo_pedido = $_POST["tipo_pedido"];

    $sql_tipo_pedido = "SELECT pedido_faturado FROM tbl_tipo_pedido WHERE tipo_pedido = {$tipo_pedido} AND fabrica = {$login_fabrica}";
    $res_tipo_pedido = pg_query($con, $sql_tipo_pedido);

    if (pg_num_rows($res_tipo_pedido) > 0) {
        echo (pg_fetch_result($res_tipo_pedido, 0, "pedido_faturado") == "t") ? "sim" : "nao";
    }

    exit;

}

if (isFabrica(15) and $login_posto <> 6359) {

    $layout_menu = 'pedido';
    $title       = "Cadastro de Pedidos de Peças";

    include "cabecalho.php";
    /*Desativado conforme solicitacao Rodrigo latina hd 5086 takashi 28/09/07*/
    echo "<BR><BR><center>Desativado Temporariamente</center><BR><BR>";
    include "rodape.php";
    exit;

}

if (isFabrica(50) and $login_posto <> 6359 and 1==2) {
    $layout_menu = 'pedido';
    $title       = "Cadastro de Pedidos de Peças";
    include "cabecalho.php";
    // HD  36995
    echo "<BR><BR><center><b>Pedidos faturados bloqueado, favor pedir peças para compra , através do e-mail:</b> <u>carina@colormaq.com.br</u></center><BR><BR>";
    include "rodape.php";
    exit;
}

if(in_array($login_fabrica, array(11,172)) && $_GET["alterar_fabrica"]){

    $fabrica = $_GET["fabrica"];

    $self = $_SERVER['PHP_SELF'];
    $self = explode("/", $self);

    unset($self[count($self)-1]);

    $page = implode("/", $self);
    $page = "http://".$_SERVER['HTTP_HOST'].$page."/token_cookie_changes.php";
    $pageReturn = "http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];

    $params = "?cook_admin=&cook_fabrica={$fabrica}&page_return={$pageReturn}";
    $page = $page.$params;

    header("Location: {$page}");
    exit;

}

if (isFabrica(14)) {
    $layout_menu = 'pedido';
    $title       = "Cadastro de Pedidos de Peças";
    include "cabecalho.php";
    echo "<H4>CADASTRO DE PEDIDO INDISPONÍVEL.</H4>";
    include "rodape.php";
    exit;
}

// HD 221731 - Para postos atendidos pelo Distrib, dar opção ao posto a ter o pedido atendido parcial
// HD 907550 - Adicionar Cobimex
$distrib_posto_pedido_parcial = isFabrica(11,51,81,104,114,122,123,125,128,153,172);

/*
81 - Bestway
125 - Saint Gobain
153 - Positron
123 - Positec
114 - Cobimex
122 - Wurth
*/

$sql = "SELECT pedido_faturado FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
$res = pg_query ($con,$sql);

if (pg_fetch_result ($res,0,0) == 'f') {

    //hd 17625 - Suggar faz pedido em garantia manual
    if (pg_fetch_result ($res,0,0) == 'f' and isFabrica(24)) {
        $sql = "SELECT pedido_em_garantia FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
        $res = pg_query ($con,$sql);
    }

    if (pg_fetch_result ($res,0,0) == 'f') {

        $title = "Cadastro de Pedidos de Peças";
        include "cabecalho.php";
        echo "<H4>Cadastro de pedido bloqueado pelo financeiro, <br /> favor entrar em contato com a fábrica.</H4>";

        if (isFabrica(90)) {

            echo " Para compra de peças,entre em contato através do telefone:<br/>
                    <u>VENDAS DE PEÇAS</u><br/>
                    ADIR : (11) 2118-2152<br/>
                    BRUNO : (11) 2118-2155<br/>
                    EDSON : (11) 2118-2153<br/><br/>

                    // <u>DISK-REFIL</u><br/>
                    ANDRÉA : (11) 2118-2121<br/>";

        }

        include "rodape.php";
        exit;

    }

}

// BLOQUEIO DE PEDIDO FATURADO PARA O GM TOSCAN
if (isFabrica(3) and $login_posto == 970) {
    include "cabecalho.php";
    echo "<H4>CADASTRO DE PEDIDOS FATURADOS BLOQUEADO</H4>";
    include "rodape.php";
    exit;
}

// BLOQUEIO DE PEDIDO FATURADO PARA AA ELETRONICA(PEDIDO DO TULIO)
if (isFabrica(51) and $login_posto == 554) {
    include "cabecalho.php";
    echo "<H4>CADASTRO DE PEDIDOS FATURADOS BLOQUEADO</H4>";
    include "rodape.php";
    exit;
}

if (isFabrica(3) and $login_bloqueio_pedido == 't') {

    $sql = "SELECT tbl_posto_linha.posto
            FROM   tbl_posto_linha
            WHERE  tbl_posto_linha.posto        = $login_posto
            AND    tbl_posto_linha.linha NOT IN (2,4);";
    $res = pg_query ($con,$sql);

    if (pg_num_rows($res) == 0) {
        $layout_menu = 'pedido';
        $title       = "Cadastro de Pedidos de Peças";
        include "cabecalho.php";
        include "rodape.php";
        exit;
    }

}

#-------- Libera digitação de PEDIDOS pelo distribuidor ---------------
$posto = $login_posto ;
if (isFabrica(3)) {
    $sql = "SELECT tbl_tipo_posto.distribuidor FROM tbl_tipo_posto JOIN tbl_posto_fabrica USING (tipo_Posto) WHERE tbl_posto_fabrica.posto = $login_posto AND tbl_posto_fabrica.fabrica = $login_fabrica";
    $res = @pg_query($con,$sql);
    $distribuidor_digita = pg_fetch_result ($res,0,0);
    if (strlen ($posto) == 0) $posto = $login_posto;
}

$limit_pedidos = 2;

/* Suggar liberou até 4 pedido em garantia - HD 22397 23862*/ // HD 33373 // HD 60077
$limite_posto = array(720,20235,476);
if (isFabrica(24) AND in_array($login_posto,$limite_posto)) {
    $limit_pedidos = 4;
}

if ($login_posto == 2474) {
    $limit_pedidos = 4;
}

if ($login_posto == 19566) {
    $limit_pedidos = 99;
}

#Redireciona para a Loja Virtual - Desabilitado pois ainda vai utilizar este cadastro
if (isFabrica(3)) {

    $sql = "SELECT estado FROM tbl_posto WHERE posto = $login_posto";
    $res = pg_query($con,$sql);
    $estado = pg_fetch_result ($res,0,0);

    if ($estado == 'SP') {
        //header("Location: loja_completa.php");
        //exit;
    }

}

if (isFabrica(104,141,144)) {
    $sql = "SELECT pedido
            FROM tbl_pedido
            WHERE fabrica = $login_fabrica
            AND posto = $login_posto
            AND exportado IS NULL
	    AND finalizado is NULL
            AND pedido_os is not true
            AND (status_pedido <> 14 OR status_pedido IS NULL)
            ORDER BY pedido DESC
            LIMIT 1";
    $res = pg_query($con,$sql);
    if (pg_numrows($res) > 0) {
        $cook_pedido = pg_result($res,0,pedido);
        $cookie_login['cook_pedido'] = $cook_pedido;
    }
}

if (isset($_POST["verifica_tipo_pecas"]) && $_POST["verifica_tipo_pecas"] == "ok") {
    $tipo_peca = $_POST["tipo_pecas"];
    //print_r($_POST);

    if (strlen($cook_pedido) > 0) {
        $sqltp = "SELECT pedido_acessorio FROM tbl_pedido WHERE pedido = {$cook_pedido};";
        $restp = pg_query($con,$sqltp);
        //echo nl2br($sqltp);
        // echo $tipo_peca." = ";
        // echo pg_fetch_result($restp, 0, "pedido_acessorio");

        if (pg_num_rows($restp) > 0) {
            $tipo_peca_pedido = pg_fetch_result($restp, 0, "pedido_acessorio");
            if ($tipo_peca_pedido == $tipo_peca) {
                echo "sim";
            }else{
                if ($tipo_peca_pedido == 't') {
                    echo "Para lançar um pedido de ACESSÓRIOS por favor finalize o pedido de PEÇAS existente.";
                }else{
                    echo "Para lançar um pedido de PEÇAS por favor finalize o pedido de ACESSÓRIOS existente.";
                }
            }
        }
    }else{
        echo "sim";
    }

    exit;
}

$btn_acao = strtolower ($_POST['btn_acao']);

$msg_erro  = "";
$msg_debug = "";
$qtde_item = 40;
 
if ($login_posto == 2474) {
    $qtde_item = 70;
}

if (isFabrica(11,172)) {
    $qtde_item = 30;
}

/*HD:22543 - IGOR*/
if (isFabrica(50)) {
    $qtde_item = 18;
}

/*HD 70768 - Esmaltec 50 ítens  */
if (isFabrica(30)) {
    $qtde_item=50;
}

/*HD 1778245 - 20 ítens  */
if (isFabrica(88)) {
    $qtde_item=20;
}

if (isset($_POST['qtde_item_combo']) && $_POST['qtde_item_combo'] != '') {
    $qtde_item = $_POST['qtde_item_combo'];
} else if (isset($_POST['qtde_item_combo_hidden']) && $_POST['qtde_item_combo_hidden'] != '') {
    $qtde_item = $_POST['qtde_item_combo_hidden'];
}

if ($_POST['ajax'] == "sim") {
    /**
    *   Verifica o estado e a região do estado onde o posto se encontra
    */
    $sqlConfere = "
        SELECT  tbl_posto.capital_interior,
                tbl_posto_fabrica.contato_estado as estado
        FROM    tbl_posto
		JOIN    tbl_posto_fabrica USING(posto)
		WHERE   posto = $login_posto
		and     fabrica = $login_fabrica
    ";
    $resConfere = pg_query($con,$sqlConfere);
    $posto_regiao = trim(pg_fetch_result($resConfere,0,capital_interior));
    $posto_estado = pg_fetch_result($resConfere,0,estado);

    /**
    *   Verifica as transportadoras que atendem a região do Posto
    */
    $sqlTrans = "
        SELECT  DISTINCT tbl_transportadora_padrao.transportadora_padrao,
                tbl_transportadora_padrao.transportadora
        FROM    tbl_transportadora_padrao
		JOIN	tbl_transportadora_fabrica USING(transportadora,fabrica)
        WHERE   capital_interior    = '$posto_regiao'
        AND     estado              = '$posto_estado'
		AND     fabrica             = $login_fabrica
		AND		ativo   ";
    $resTrans = pg_query($con,$sqlTrans);
    if (pg_num_rows($resTrans) > 0) {
        $ajaxTransPadrao = pg_fetch_all($resTrans);
    }else{
        /**
        *   Se não for encontrada, retornará o erro e o Ajax gerará msg
        *   de erro para o posto pedir à fabrica o cadastro de uma transportadora
        */
        return false;
        exit;
    }
    $array_dados = array();
    foreach ($_POST['linha'] as $dados) {
        /**
        *   Para cada peça cadastrada no pedido, será multiplicada
        *   o peso com a quantidade pedida de peças
        */
        $sqlConta = "
            SELECT  (peso * ".$_POST["qtde"][$dados].") AS peso_mult
            FROM    tbl_peca
			WHERE   fabrica = $login_fabrica
			AND referencia = '".$_POST["pecas"][$dados]."'";
        $resConta = pg_query($con,$sqlConta);
        $array_dados[$_POST["pecas"][$dados]] = pg_fetch_result($resConta,0,peso_mult);
    }
    $soma_pecas = array_sum($array_dados);

    foreach ($ajaxTransPadrao as $padrao) {
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
            WHERE   tbl_transportadora_valor.transportadora_padrao = $trans_padrao
            AND     (
                        tbl_transportadora_valor.kg_inicial < $soma_pecas
                    AND tbl_transportadora_valor.kg_final >= $soma_pecas
                    )
        ";
        $resProcura = pg_query($con,$sqlProcura);
        if (pg_num_rows($resProcura) > 0) {
            $valor_kg[$trans] = pg_fetch_result($resProcura,0,valor_kg);
            $seguro[$trans]     = pg_fetch_result($resProcura,0,seguro);
            $gris[$trans]       = pg_fetch_result($resProcura,0,gris);
        }else{
             /**
            *   Se a verificação acima não achar nenhuma faixa,
            *   vai pegar o valor excedente de frete, multiplicar com o peso das peças
            *   e somar com a faixa de frete mais pesada
            */
            $sqlPesado = "
                SELECT  max(tbl_transportadora_valor.kg_final) AS kg_maximo,
                        valor_acima_kg_final,
                        valor_kg,
                        tbl_transportadora_valor.seguro                     ,
			tbl_transportadora_valor.gris,
			tbl_transportadora_valor.kg_inicial
                FROM    tbl_transportadora_valor
                WHERE   transportadora_padrao = $trans_padrao
          GROUP BY      tbl_transportadora_valor.valor_kg,
                        tbl_transportadora_valor.valor_acima_kg_final,
                        tbl_transportadora_valor.seguro,
			tbl_transportadora_valor.gris,
			tbl_transportadora_valor.kg_inicial
			order by 1 desc limit 1;
            ";
            $res_pesado = pg_query($con,$sqlPesado);
            if (pg_num_rows($res_pesado) > 0) {
                $aux = pg_fetch_result($res_pesado,0,kg_maximo);
                if ($aux < $soma_pecas) {
                    $excedente      = $soma_pecas - $aux;
                    $valor_acima    = pg_fetch_result($res_pesado,0,valor_acima_kg_final);
                    $valor_base     = pg_fetch_result($res_pesado,0,valor_kg);
                    $total_frete    = $valor_base + ($valor_acima * $excedente);
                    $valor_kg[$trans] = $total_frete;
                    $seguro[$trans]     = pg_fetch_result($res_pesado,0,seguro);
                    $gris[$trans]       = pg_fetch_result($res_pesado,0,gris);
                }
            }
        }
    }

    if (count($valor_kg) > 0) {
        if (count($valor_kg) > 1) {
            /**
            *   Se houver mais de uma transportadora,
            *   será feita a ordenação do menor valor
            */
            asort($valor_kg);
            $trans_valor = array_slice($valor_kg,0,1,TRUE);
            $passaJson[] = array("posto_estado" => $posto_estado, "trans"=>key($trans_valor),"valor" => (float)current($trans_valor),"seguro" => (float)$seguro[key($trans_valor)],"gris" => (float)$gris[key($trans_valor)]);
            $mais_barato = json_encode($passaJson);
        }else{
            $passaJson[] = array("posto_estado" => $posto_estado, "trans"=>key($valor_kg),"valor"=>(float)current($valor_kg),"seguro" => (float)$seguro[key($valor_kg)],"gris" => (float)$gris[key($valor_kg)]);
            $mais_barato = json_encode($passaJson);
        }
    }

    echo $mais_barato;
    exit;
}

if ($btn_acao == "gravar" && isFabrica(138,142,143,145) && $_POST['qtde_item_combo'] == '') {
    try {
        $pedido = $_POST["pedido"];

        if (empty($pedido)) {
            $pedido = null;

            $pedidoClass = new Pedido($login_fabrica);
        } else {
            $pedidoClass = new Pedido($login_fabrica, $pedido);
        }

        if (isFabrica(138)) {
            $qtde_item = $_POST["qtde_item_hidden"];
        }

        if (isFabrica(142)) {
            $tipo_frete = "CIF";
        }

        $dados = array(
            "posto"                          => $login_posto,
            "fabrica"                        => $login_fabrica,
            "condicao"                       => (empty($_POST["condicao"])) ? ($pedido == null) ? "null" : null : $_POST["condicao"],
            "pedido_cliente"                 => "'{$_POST['pedido_cliente']}'",
            "transportadora"                 => (empty($_POST["transportadora"])) ? ($pedido == null) ? "null" : null : $_POST["transportadora"],
            "linha"                          => (empty($_POST["linha"])) ? "null" : $_POST["linha"],
            "tipo_pedido"                    => $_POST["tipo_pedido"],
            "obs"                            => "'{$_POST['observacao_pedido']}'",
            "atende_pedido_faturado_parcial" => ($_POST["parcial"] == "t") ? "true" : "false",
            "tipo_frete"                     => "'{$tipo_frete}'",
            "status_pedido"                  => 1
        );

        $pedidoClass->_model->getPDO()->beginTransaction();

        $pedidoClass->grava($dados, $pedido);

        $dadosItens = array();

        for ($i = 0; $i < $qtde_item; $i++) {
            if (empty($_POST["pedido_item_{$i}"]) && empty($_POST["peca_referencia_{$i}"])) {
                continue;
            }

            $dadosItens[] = array(
                "pedido_item"     => $_POST["pedido_item_{$i}"],
                "peca_referencia" => $_POST["peca_referencia_{$i}"],
                "qtde"           => str_replace(",", ".", str_replace(".", "", $_POST["qtde_{$i}"])),
                "preco"           => str_replace(",", ".", str_replace(".", "", $_POST["preco_{$i}"])),
                "preco_base"      => str_replace(",", ".", str_replace(".", "", $_POST["preco_{$i}"])),
                "ipi"             => 0
            );
        }

        $pedidoClass->gravaItem($dadosItens);

        if (ifFabrica(138, 142, 143, '145...')) {
            $pedidoClass->verificaValorMinimo();
        }

        if (isFabrica(138)) {
            $pedidoClass->auditoria();
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
                        where tbl_posto_fabrica.fabrica = $login_fabrica and tbl_posto_fabrica.posto = $posto_id";

            $res_posto = pg_query($con, $sql_posto);

            $contato_email = pg_fetch_result($res_posto, 0, 'contato_email');
            $fabrica_nome = pg_fetch_result($res_posto, 0, 'fabrica_nome');
            $posto_nome = pg_fetch_result($res_posto, 0, 'posto_nome');

            $assunto       = "Pedido nº ".$pedido. " - ". $fabrica_nome;
            $corpo         = email_pedido($posto_nome, $fabrica_nome, $pedido, $cook_login, false, 'antiga');

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

if ($btn_acao == "gravar" && !isFabrica(138,142,143,145) && $_POST['qtde_item_combo'] == '') {

    $pedido            = $_POST['pedido'];
    $condicao          = $_POST['condicao'];
    $tipo_pedido       = $_POST['tipo_pedido'];
    $pedido_cliente    = $_POST['pedido_cliente'];
    $tipo_pecas    = $_POST['tipo_pecas'];
    $transportadora    = $_POST['transportadora'];
    $valor_frete       = $_POST['valor_frete'];
    $linha             = (isFabrica(120)) ? $_POST["linha_produto"] : $_POST['linha'];
    $linha_produto     = $_POST['linha_produto']; //hd_chamado=2765193
    $familia           = $_POST['familia'];
    $observacao_pedido = $_POST['observacao_pedido'];
    $parcial           = $_POST['parcial'];
    $forma_envio       = $_POST['forma_envio'];
    $total_pecas       = $_POST['total_pecas'];
    $gravakit          = $_POST['gravakit'];
    $qtde_parcelas     = $_POST['qtde_parcelas'];

    $insert_aprovacao_tipo = "";
    $aprovacao_tipo = "";
    $update_aprovacao_tipo = "";

    if($login_fabrica == 104){
        atualizavalor($pedido);
    }

    if (empty($forma_envio)AND isFabrica(94))  {
        $msg_erro .= "Por favor, informe a forma e envio!<br>";
    }

    if (isFabrica(24) && qual_tipo_posto($login_posto) == 696) {
        if (isset($_POST['registro_funcionario'])) {
            if ($_POST['registro_funcionario'] == "") {
                $msg_erro .= "Por favor, informe o registro <br>";
            } else {
                $registro_funcionario = utf8_encode($_POST['registro_funcionario']);
            }
        }

        if (isset($_POST['departamento_funcionario'])) {
            if ($_POST['departamento_funcionario'] == "") {
                $msg_erro .= "Por favor, informe o departamento <br>";
            } else {
                $departamento_funcionario = utf8_encode($_POST['departamento_funcionario']);
            }
        }

        if (isset($_POST['nome_funcionario'])) {
            if ($_POST['nome_funcionario'] == "") {
                $msg_erro .= "Por favor, informe o nome <br>";
            } else {
                $nome_funcionario = utf8_encode($_POST['nome_funcionario']);
            }
        }
    }

    if (isFabrica(94) and strlen($msg_erro) == 0) {
        if (strlen($pedido_cliente) == 0) {
            $msg_erro .= "Por favor, informe o número do Pedido Cliente <br>"; 
        }

        if ($_POST['forma_envio'] == 2 AND strlen($transportadora) == 0) {
            $msg_erro .= "Por favor, informe a Transportadora para envio <br>";
        }

        if (!empty($forma_envio) AND $forma_envio != 2) {
            $sqlEnv = "SELECT descricao FROM tbl_forma_envio WHERE forma_envio = {$forma_envio} AND fabrica = {$login_fabrica};";
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

    if (strlen($condicao) == '') {
        $msg_erro .=traduz("Por favor, informar a condição de pagamento")." <br/>";
    }

    if (strlen($parcial) == 0 AND $distrib_posto_pedido_parcial) {
        $msg_erro .= traduz("Por favor, informe se o pedido será atendido parcial");
    }

    if (isFabrica(11,172)) {
        $insumos = $_POST["insumos"];
        $disable_insumos = 'disabled="disabled"';
        $tipo_pedido_desc = '';

        if (empty($tipo_pedido)) {
            $msg_erro .= "Favor selecionar Tipo de Pedido<br/>";
        } else {
            $qry_tp = pg_query($con, "SELECT descricao FROM tbl_tipo_pedido WHERE tipo_pedido = $tipo_pedido");
            $tipo_pedido_desc = pg_fetch_result($qry_tp, 0, "descricao");

            if ($tipo_pedido_desc == "Insumo") {
                $disable_insumos = '';

                if (empty($insumos)) {
                    $msg_erro .= "Favor selecionar uma opção no campo Insumos<br/>";
                }
            }
        }

        /*HD-3622818*/
        $sql = "SELECT transportadora FROM tbl_posto_fabrica WHERE posto = $posto AND fabrica = {$login_fabrica} ";
        $res = pg_exec ($con,$sql);
        if (pg_numrows ($res) > 0) {
            $transportadora = @pg_result ($res,0,0);
        }
    }

    $aux_condicao          = (strlen($condicao) == 0) ? "null" : $condicao ;
    $aux_pedido_cliente    = (strlen($pedido_cliente) == 0) ? "null" : "'". $pedido_cliente ."'";
    $aux_transportadora    = (strlen($transportadora) == 0) ? "null" : $transportadora ;
    $aux_frete             = (strlen($valor_frete) == 0) ? "null" : str_replace(",",".",$valor_frete);
    $aux_observacao_pedido = (strlen($observacao_pedido) == 0) ? "null" : "'$observacao_pedido'" ;
    $aux_forma_envio = (empty($forma_envio)) ? "null" : $forma_envio ;

    if (isFabrica(35)) {
    	if (strlen($condicao) > 0){
    		$sql = "select condicao, codigo_condicao, descricao, limite_minimo from tbl_condicao where condicao = $condicao and fabrica = $login_fabrica";
    		$res = pg_query($con, $sql);
    		if (pg_num_rows($res) > 0) {
    			$limite_minimo = pg_fetch_result($res, 0, 'limite_minimo');
    		}
    	}
		$total_pecas = str_replace(".","",$total_pecas);
        $limite_minimo  = number_format($limite_minimo, 2, '.', '');
        $total_pecas    = number_format($total_pecas, 2, '.', '');

        if ($limite_minimo > $total_pecas) {
            $msg_erro = "Pedido não atingiu o valor mínimo de R$ $limite_minimo  <Br>";
        }
    }

    if (strlen($tipo_pedido) <> 0) {
        $aux_tipo_pedido = "'". $tipo_pedido ."'";
    } else {
        $sql = "SELECT  tipo_pedido
                FROM    tbl_tipo_pedido
                WHERE   descricao IN ('Faturado','Venda')
                AND     fabrica = $login_fabrica";
        $res = pg_query ($con,$sql);
        $aux_tipo_pedido = "'". pg_fetch_result($res,0,tipo_pedido) ."'";
    }

    if (strlen($linha) == 0) {
        $aux_linha = "null";
        if (isFabrica(3)) {
            $msg_erro="Por favor, informar a linha para este pedido";
        }
    } else {
        $aux_linha = $linha ;
    }

    //hd_chamado=2765193

    if (strlen($linha_produto) == 0 AND isFabrica(120)) {
        $msg_erro .="Por favor, informar a linha para este pedido <br/>";
    }

    if (isFabrica(5) AND strlen ($pedido) > 0) {

        $sql = "SELECT exportado
                FROM tbl_pedido
                WHERE pedido = $pedido
                AND fabrica = $login_fabrica;";

        $res = @pg_query ($con,$sql);

        if (pg_num_rows($res) > 0) {

            $exportado = pg_fetch_result($res,0,exportado);

            if (strlen($exportado)>0){
                $msg_erro="Não é possível alterar. Pedido já exportado.";
            }

        }

    }

    #----------- PEDIDO digitado pelo Distribuidor -----------------
    $digitacao_distribuidor = "null";

    if ($distribuidor_digita == 't') {

        $codigo_posto = strtoupper(trim($_POST['codigo_posto']));
        $codigo_posto = str_replace(" ", "", $codigo_posto);
        $codigo_posto = str_replace(".", "", $codigo_posto);
        $codigo_posto = str_replace("/", "", $codigo_posto);
        $codigo_posto = str_replace("-", "", $codigo_posto);

        if (strlen ($codigo_posto) > 0) {

            $sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '$codigo_posto' AND credenciamento = 'CREDENCIADO'";
            $res = @pg_query($con,$sql);
            if (pg_num_rows ($res) <> 1) {
                $msg_erro = "Posto $codigo_posto não cadastrado";
                $posto = $login_posto;
            } else {
                $posto = pg_fetch_result ($res,0,0);
                if ($posto <> $login_posto) {
                    $sql = "SELECT posto FROM tbl_posto_linha WHERE posto = $posto AND distribuidor = $login_posto AND linha = $linha";
                    $res = @pg_query($con,$sql);
                    if (pg_num_rows ($res) <> 1) {
                        $msg_erro = "Posto $codigo_posto não pertence a sua região";
                        $posto = $login_posto;
                    } else {
                        $posto = pg_fetch_result ($res,0,0);
                        $digitacao_distribuidor = $login_posto;
                    }
                }
            }
        }
    }


    $res = pg_query ($con,"BEGIN TRANSACTION");

    if (isFabrica(24) and $tipo_pedido == 104 and $login_posto <> 6359) {

        $sql = "SELECT  to_char(current_date,'MM')::INTEGER as mes,
                        to_char(current_date,'YYYY') AS ano";
        $res = pg_query($con,$sql);
        $mes = pg_fetch_result($res,0,mes);
        $ano = pg_fetch_result($res,0,ano);

        if (strlen($mes) > 0) {
            $data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
            $data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
            /*HD: 108583 - RETIRADO PEDIDO DO ADMIN E COM STATUS CANCELADO (14)*/
            $sql = "SELECT  count(pedido) as qtde
                    FROM tbl_pedido
                    WHERE fabrica = $login_fabrica
                    AND posto = $login_posto
                    AND admin is NULL
                    AND status_pedido <> 14
                    AND data BETWEEN '$data_inicial' AND '$data_final'
                    AND tipo_pedido = 104";
            $res = pg_query($con,$sql);
            $qtde = pg_fetch_result($res,0,qtde);
            if ($qtde >= $limit_pedidos) {
                $msg_erro = "Seu PA já fez $limit_pedidos pedidos de garantia este mês, por favor entre em contato com o fabricante";
            }
        }
    }
    if (isFabrica(88,120)) {
        $frete_calculado = $_POST['frete_calculado'];
        $total_pecas = $_POST['total_pecas'];
        $total_pecas  = str_replace(".","",$total_pecas);
        $total_pecas  = str_replace(",",".",$total_pecas);
        $frete_calculou = $_POST["frete_calculou"];

        if (isFabrica(88)) {

            $sqlEstadoPosto = "SELECT contato_estado FROM tbl_posto_fabrica 
                            WHERE fabrica = $login_fabrica and posto = $posto";
            $resEstadoPosto = pg_query($con, $sqlEstadoPosto);
            if(pg_num_rows($resEstadoPosto)){
                $contato_estado = pg_fetch_result($resEstadoPosto, 0, "contato_estado");
            }

            if(strlen($frete_calculado) == 0){
                if($total_pecas < 1000 ){
                    $msg_erro .= "O pedido não foi gravado por falta de cálculo do frete";
                }elseif(in_array($contato_estado, ["MA", "PI", "CE", "RN", "PE", "PB", "SE", "AL"])){
                    $msg_erro .= "O pedido não foi gravado por falta de cálculo do frete";
                }                         
            } 
            /*if (strlen($frete_calculado) == 0 and $total_pecas < 1000) {
                $msg_erro .= "O pedido não foi gravado por falta de cálculo do frete";
            }*/
        }

        if (isFabrica(120) AND strlen(trim($frete_calculou))==0) {
            if (strlen($frete_calculado) == 0) {
                $msg_erro .= "O pedido não foi gravado por falta de cálculo do frete";
            }
        }
    }

    if (isFabrica(139)) {
        $select_distribuidor = "SELECT tbl_posto_linha.distribuidor
                                FROM tbl_linha
                                INNER JOIN tbl_posto_linha USING(linha)
                                WHERE tbl_posto_linha.posto = $login_posto
                                AND tbl_linha.fabrica = $login_fabrica
                                LIMIT 1";
        $res_distribuidor = pg_query($con, $select_distribuidor);

        if (!pg_num_rows($res_distribuidor)) {
            $msg_erro = "Distribuidor não cadastrado";
        } else {
            $distribuidor = pg_fetch_result($res_distribuidor, 0, "distribuidor");
        }

    }
    // Valida quantidade de itens do pedido
    if (strlen ($msg_erro) == 0) {
        $sem_peca = true;
        for ($w = 0 ; $w < $qtde_item ; $w++) {
            $pedido_item_v     = trim($_POST['pedido_item_'     . $w]);
            $peca_referencia_v = trim($_POST['peca_referencia_' . $w]);

            if (!empty($pedido_item_v) OR !empty($peca_referencia_v)) {
                $sem_peca = false;
            }

            if (isFabrica(11,104,172)) {
                $qtde           = trim($_POST['qtde_'. $w]);
                $qtde_demanda   = trim($_POST['qtde_demanda_'. $w]);

                if ($qtde_demanda != "" && (int)$qtde_demanda < $qtde) {
                    $msg_erro = "Quantidade indicada é maior que a máxima determinada pela fábrica";
                }
            }
        }
        if ($sem_peca) {
            $msg_erro = "Não foi encontrado nenhum item para realizar o Pedido!";
        }
    }
    if (strlen($msg_erro)==0) {

        if (isFabrica(24)) {

            if(!empty($nome_funcionario)){

                if (strlen(trim($pedido)) == 0 ) {

                    $valores_adicionais = array("registro_funcionario" => $registro_funcionario, "departamento_funcionario" => $departamento_funcionario, "nome_funcionario" => $nome_funcionario);

                    $campo_valor_add = ", valores_adicionais, exportado, status_pedido";
                    $valores_adicionais_add = ", '".json_encode($valores_adicionais)."', now(), 2";
                } 
            }

            if(!empty($qtde_parcelas)){

                $qtde_parcelas = (int)$qtde_parcelas;

                if(!empty($valores_adicionais)){
                    $valores_adicionais["qtde_parcelas"] = $qtde_parcelas;
                }else{
                    $valores_adicionais = array("qtde_parcelas" => $qtde_parcelas);
                }
               
                if(empty($campo_valor_add)){
                    $campo_valor_add = ", valores_adicionais";
                }

                if(empty($valores_adicionais_add)){
                    $valores_adicionais_add = ", '".json_encode($valores_adicionais)."'";
                }else{
                    $valores_adicionais_add = ", '".json_encode($valores_adicionais)."', now(), 2";
                }
            } 
        }

        if (strlen ($pedido) == 0 ) {
            // HD  80338
            if (isFabrica(24,85,98,101,120,121,124,127, 129,134,131,141,144))  {
                $sql_campo = " ,tipo_frete ";
                if (isFabrica(24,98,101,121,124,127, 129,134,141,144)) {
                    $sql_valor = " ,'CIF' ";
                } else {
                    $sql_valor = " ,'FOB' ";
                }
            }

            if (isFabrica(104,105))  {

                $sql = "SELECT frete FROM tbl_condicao WHERE condicao = $aux_condicao";
                $res = pg_query($con,$sql);
                if (pg_numrows($res) > 0) {
                    $sql_campo = " ,tipo_frete ";
                    $frete = pg_result($res,0,0);
                    $sql_valor = " ,'$frete'";
                }
            }

            if (isFabrica(88) || (isFabrica(125) && $login_tipo_posto == 399)) {
                $sql_campo = " ,tipo_frete ";
                $frete = $_POST['tipo_frete'];
                $sql_valor = " ,'$frete'";

            }

            if (isFabrica(104,141,144)) {

                if (isset($cook_pedido)) {
                    $sql = "SELECT tbl_pedido.pedido
                        FROM   tbl_pedido
                        WHERE  tbl_pedido.exportado IS NULL
                        AND    tbl_pedido.pedido = $cook_pedido";
                    $res = pg_query ($con,$sql);

                    if (pg_num_rows($res) == 0) {
                        $msg_erro .= "Pedido não pode ser mais alterado pois já foi exportado.";
                    }else{
                        $sql_campo .= ", pedido ";
                        $sql_valor .= ", $cook_pedido ";
                    }
                }
                if (isFabrica(104)) {
                    $coluna_adicional["coluna"] = ", pedido_acessorio";
                    $coluna_adicional["valor"]  = ", '$tipo_pecas'";
                }
            }

            if (isFabrica(139)) {
                $coluna_adicional["coluna"] = ", distribuidor";
                $coluna_adicional["valor"]  = ", $distribuidor";
            }
            $campo_status_pedido_masterfrio = '';
            $status_pedido_masterfrio = '';
			if (isFabrica(40)){
                $campo_status_pedido_masterfrio = "status_pedido,";
                $status_pedido_masterfrio = "2,";
            }

            if (isFabrica(11,172)) {
                $campo_status_pedido_masterfrio = "status_pedido,";
                $status_pedido_masterfrio       = "18,";
            } 


            #-------------- insere pedido ------------
            $sql = "INSERT INTO tbl_pedido (
                        posto                          ,
                        fabrica                        ,
                        condicao                       ,
                        pedido_cliente                 ,
                        transportadora                 ,
                        {$insert_aprovacao_tipo}
                        valor_frete                    ,
                        linha                          ,
                        tipo_pedido                    ,
                        digitacao_distribuidor         ,
                        obs                            ,
                        {$campo_status_pedido_masterfrio}
                        atende_pedido_faturado_parcial ,
                        forma_envio
                        $sql_campo
                        {$coluna_adicional['coluna']}
                        $campo_valor_add
                    ) VALUES (
                        $posto                         ,
                        $login_fabrica                 ,
                        $aux_condicao                  ,
                        $aux_pedido_cliente            ,
                        $aux_transportadora            ,
                        {$aprovacao_tipo}
                        $aux_frete                     ,
                        $aux_linha                     ,
                        $aux_tipo_pedido               ,
                        $digitacao_distribuidor        ,
                        $aux_observacao_pedido         ,
                        {$status_pedido_masterfrio}
                        '$parcial'                     ,
                        $aux_forma_envio
                        $sql_valor
                        {$coluna_adicional['valor']}
                        $valores_adicionais_add
                    )RETURNING pedido"; #HD 363162 (RETURNING pedido)
            $res = pg_query ($con,$sql);
            $pedido_normal = pg_result($res,0,pedido); #HD 363162
            $msg_erro = pg_errormessage($con);
            if (strlen($msg_erro) == 0){
                $res = @pg_query ($con,"SELECT CURRVAL ('seq_pedido')");
                $pedido  = @pg_fetch_result ($res,0,0);
            }

            if (isFabrica(104)) {
                $sql = "SELECT  tbl_pedido_item.pedido_item
                FROM  tbl_pedido
                JOIN  tbl_pedido_item USING (pedido)
                WHERE tbl_pedido.posto   = {$posto}
                AND   tbl_pedido.fabrica = {$login_fabrica}
                AND   tbl_pedido_item.pedido = {$pedido}
                ORDER BY tbl_pedido_item.pedido_item";
                $res = pg_query ($con,$sql);

                if (pg_num_rows($res) >= 95) {
                    $msg_erro .= "Pedido com o limite máximo de itens. Para lançar novos itens, finalize este pedido e inicie um novo.<br />";
                }
            }
        }else{
                
            $sql = "UPDATE tbl_pedido SET
                        condicao       = $aux_condicao       ,
                        pedido_cliente = $aux_pedido_cliente ,
                        transportadora = $aux_transportadora ,
                        {$update_aprovacao_tipo}
                        valor_frete    = $aux_frete          ,
                        linha          = $aux_linha          ,
                        tipo_pedido    = $aux_tipo_pedido    ,
                        forma_envio    = $aux_forma_envio
                    WHERE pedido  = $pedido
                    AND   posto   = $login_posto
                    AND   fabrica = $login_fabrica";
            $res = @pg_query ($con,$sql);
            $msg_erro = pg_errormessage($con);
        }

        $objLog = new AuditorLog();
        $objItem = new AuditorLog();
        $objLog->retornaDadosTabela('tbl_pedido', ['pedido'=>$pedido, 'fabrica'=>$login_fabrica]);
        $objItem->retornaDadosSelect("SELECT tbl_pedido_item.pedido_item, tbl_pedido_item.peca, tbl_pedido_item.qtde, tbl_pedido_item.preco, tbl_pedido_item.qtde_faturada, tbl_pedido_item.qtde_cancelada
        FROM tbl_pedido_item
        WHERE tbl_pedido_item.pedido = $pedido");   
       
        if(empty($msg_erro) and !empty($pedido)){
            $sql = "SELECT fn_valida_pedido($pedido,$login_fabrica);";
            $res = pg_query($con,$sql);
            $msg_erro = pg_errormessage($con);
        }

    }

    if (strlen ($msg_erro) == 0) {

        $nacional  = 0;
        $importado = 0;
        $interacaoEntrega = [];
        $temIndisponivel = false;

        for ($i = 0 ; $i < $qtde_item ; $i++) {
            $pedido_item     = trim($_POST['pedido_item_'     . $i]);
            $peca_referencia = trim($_POST['peca_referencia_' . $i]);
            $qtde            = trim($_POST['qtde_'            . $i]);
            $preco           = trim($_POST['preco_'           . $i]);
            if ($login_fabrica == 123) {
                $prevEntrega     = trim($_POST['prevEntrega_'     . $i]);
                $disponibilidade = trim($_POST['disponibilidade_' . $i]);
                $peca_descricao  = trim($_POST['peca_descricao_' . $i]);
            }

            if (isFabrica(91)) {
                $sql_bloqueada = "SELECT peca FROM tbl_peca
                    WHERE referencia = '$peca_referencia'
                    AND fabrica = $login_fabrica
                    AND bloqueada_venda IS TRUE";
                $qry_bloqueada = pg_query($con, $sql_bloqueada);

                if (pg_num_rows($qry_bloqueada) > 0) {
                    $msg_erro = "Peça $peca_referencia Critica, contatar a fábrica pelo 0800 para possível liberação da mesma em seu pedido de compra.";
                    break;
                }
            }

            if (isFabrica(51)) {
                $sqlPeca = "SELECT peca_critica, troca_obrigatoria FROM tbl_peca WHERE referencia = '$peca_referencia' AND fabrica = $login_fabrica";
                $resPeca = pg_query($con,$sqlPeca);
                $peca_critica = pg_result($resPeca,0,peca_critica);
                $troca_obrigatoria = pg_result($resPeca,0,troca_obrigatoria);

                if ($peca_critica == "t" OR $troca_obrigatoria == "t") {
                    $msg_erro = "Peça '$peca_referencia' indisponível no momento, qualquer dúvida entrar em contato com o SAC 08007244262";
                    break;
                }
            }
            if (isFabrica(104) and strlen($peca_referencia) > 0 ) {

                $sqlPeca = "SELECT acessorio FROM tbl_peca WHERE referencia = '$peca_referencia' AND fabrica = $login_fabrica";
                $resPeca = pg_query($con,$sqlPeca);
                $peca_acessorio = pg_result($resPeca,0,acessorio);

                $sqlPedido = "SELECT pedido_acessorio FROM tbl_pedido WHERE pedido = $pedido";
                $resPedido = pg_query($con,$sqlPedido);
                $tipo_pecas_pedido = pg_result($resPedido,0,pedido_acessorio);
				if (empty($peca_acessorio))  $peca_acessorio = 'f';

                if ($peca_acessorio != $tipo_pecas_pedido ) {
                    if ($tipo_pecas_pedido == "f") {
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

			if (strlen ($preco) == 0) $preco = "null";

            $preco = str_replace (".","",$preco);
			$preco = str_replace (",",".",$preco);
			$qtde  = str_replace (".","",$qtde);
			$qtde  = str_replace (",",".",$qtde);

            if (strlen ($peca_referencia) > 0 AND ( strlen($qtde) == 0 OR $qtde < 1 ) ) {
                $msg_erro = "Não foi digitada a quantidade para a Peça $peca_referencia.";
                $linha_erro = $i;
                break;
            }

            if ($preco == '0.00') {
                $preco = '';
            }

            if (strlen ($peca_referencia) > 0 AND strlen($preco) == 0) {
                $msg_erro = "A peça $peca_referencia está sem preço!";
                $linha_erro = $i;
                break;
            }

            # hd 142245
            if (strlen ($peca_referencia) > 0 AND strlen($preco) == 0 AND (isFabrica(30) OR isFabrica(5) OR isFabrica(40))) {
                $msg_erro = "A peça $peca_referencia está sem preço!";
                $linha_erro = $i;
                break;
            }

            //verifica se a peça tem o valor da peca caso nao tenha exibe a msg
            //só verifica os precos dos campos que tenha a referencia da peça.
            if (isFabrica(15) AND strlen($peca_referencia) > 0 ) {
                if ($tipo_pedido <> '90')  {
                    if (strlen($preco) == 0)  {
                        $msg_erro = 'Existem peças sem preço.<br>';
                        $linha_erro = $i;
                        break;
                    }
                }
            }
            //Adicionado a Gama Italy HD20369
            if ((isFabrica(6) OR (isFabrica(51) AND $login_posto <> 4311)) and strlen($peca_referencia) > 0 and strlen($preco)==0) {
                $msg_erro = 'Existem peças sem preço.<br>';
                $linha_erro = $i;
                break;
            }

            if (isFabrica(45) and strlen($peca_referencia) > 0 and strlen($preco)==0) {
                $msg_erro = 'Existem peças sem preço.<br>';
                $linha_erro = $i;
                break;
            }

            $qtde_anterior = 0;
            $peca_anterior = "";
            if (strlen($pedido_item) > 0 AND isFabrica(3)){
                $sql = "SELECT peca,qtde
                        FROM tbl_pedido_item
                        WHERE pedido_item = $pedido_item";
                $res = @pg_query ($con,$sql);
                $msg_erro .= pg_errormessage($con);
                if (pg_num_rows ($res) > 0){
                    $peca_anterior = pg_fetch_result($res,0,peca);
                    $qtde_anterior = pg_fetch_result($res,0,qtde);
                }
            }

            if (strlen ($pedido_item) > 0 AND strlen ($peca_referencia) == 0) {

				$sql = "UPDATE tbl_pedido_item SET pedido = 0 WHERE pedido_item = $pedido_item ";
                $res = pg_query ($con,$sql);

                /* Tira do estoque disponivel */
                if (isFabrica(3)){
                    $sql = "UPDATE tbl_peca
                            SET   qtde_disponivel_site = qtde_disponivel_site + $qtde_anterior
                            WHERE peca     = $peca_anterior
                            AND   fabrica  = $login_fabrica
                            AND   promocao_site IS TRUE
                            AND qtde_disponivel_site IS NOT NULL";
                    $res = pg_query ($con,$sql);
                    $msg_erro .= pg_errormessage($con);
                }
            }

            if (strlen ($peca_referencia) > 0) {
                $peca_referencia = trim (strtoupper ($peca_referencia));

                $sql = "SELECT  tbl_peca.peca   ,
                                tbl_peca.origem ,
                                tbl_peca.promocao_site,
                                tbl_peca.qtde_disponivel_site ,
                                tbl_peca.qtde_max_site,";
                if (isFabrica(50)){
                    $sql.="
                        (SELECT DISTINCT tbl_produto.linha
                            FROM tbl_lista_basica
                            JOIN tbl_produto USING(produto)
                            WHERE tbl_lista_basica.peca = tbl_peca.peca
                            ORDER BY linha
                            limit 1
                        ) AS linha,";
                }

                $sql .="        tbl_peca.multiplo_site
                        FROM    tbl_peca
                        WHERE   upper(tbl_peca.referencia) = '$peca_referencia'
                        AND     tbl_peca.fabrica             = $login_fabrica";
                $res = pg_query ($con,$sql);
                $peca          = pg_fetch_result ($res,0,peca);
                $promocao_site = pg_fetch_result ($res,0,promocao_site);
                $qtde_disp     = pg_fetch_result ($res,0,qtde_disponivel_site);
                $qtde_max      = pg_fetch_result ($res,0,qtde_max_site);
                $qtde_multi    = pg_fetch_result ($res,0,multiplo_site);

                if (pg_num_rows ($res) == 0) {
                    $msg_erro = "Peça $peca_referencia não cadastrada";
                    $linha_erro = $i;
                    break;
                }else{
                    $peca   = pg_fetch_result ($res,0,peca);
                    $origem = trim(pg_fetch_result ($res,0,origem));

                    if (isFabrica(50)){
                        $linha = trim(pg_result ($res,0,linha));
                        if (isFabrica(50) && $linha == 545) {
                            $pedido_automatico[$i]['peca'] = $peca;
                            $pedido_automatico[$i]['qtde'] = $qtde;
                            $pedido_automatico[$i]['preco'] = $preco;
                            continue;
                        }else if (isFabrica(50) && $linha != 545) {
                            $pedidos_linha = true;
                        }
                    }

                }

                if ($origem == "NAC" or $origem == "1") {
                    $nacional = $nacional + 1;
                }

                if ($origem == "IMP" or $origem == "2") {
                    $importado = $importado + 1;

                }
                #hd 16782
                if ($nacional > 0 and $importado > 0 and !isFabrica(3,5,6,8,24,40,51,72,80,35) or ifFabrica('90..')) {
                #   $msg_erro = "Não é permitido realizar um pedido com peça Nacional e Importada";
                #   $linha_erro = $i;
                #   break;
                }

                /*
                if (isFabrica(3) && strlen($peca_referencia) > 0) {
                    $sqlX = "SELECT referencia
                            FROM tbl_peca
                            WHERE referencia_pesquisa = UPPER('$peca_referencia')
                            AND   fabrica = $login_fabrica
                            AND   previsao_entrega > date(current_date + INTERVAL '20 days');";
                    $resX = pg_query($con,$sqlX);
                    if (pg_num_rows($resX) > 0) {
                        $peca_previsao = pg_fetch_result($resX,0,0);
                        $msg_erro = "Não há previsão de chegada da Peça $peca_previsao. Favor encaminhar e-mail para <a href='mailto:leila.beatriz@britania.com.br'>leila.beatriz@britania.com.br</a>, informando o número da ordem de serviço. Somente serão aceitas requisições via email! Não utilizar o 0800.";
                    }
                }
                */

                if (isFabrica(91) && !empty($peca)) {
                    if ($tipo_pedido == 181) {
                        $sql_critica = "SELECT peca FROM tbl_peca WHERE fabrica = {$login_fabrica} AND peca = {$peca} AND peca_critica IS TRUE";
                        $res_critica = pg_query($con, $sql_critica);

                        if (pg_num_rows($res_critica) > 0) {
                            $msg_erro = "Peça {$peca_referencia} é uma peça crítica e não pode ser lançada em um pedido faturado";
                        }
                    }
                }


                /* HD 27857 - Não permitir duas peças iguais no mesmo pedido */
                if (isFabrica(3,74) && strlen($peca) > 0 AND strlen($pedido_item)==0) {
                    $sqlX = "SELECT pedido_item
                            FROM tbl_pedido_item
                            WHERE pedido = $pedido
                            AND   peca = $peca";
                    $resX = pg_query($con,$sqlX);
                    if (pg_num_rows($resX) > 0) {
                        $msg_erro = "Peça $peca_referencia  já selecionada. Não é permitido duas peças iguais no mesmo pedido. Altere sua quantidade.";
                    }
                }

                if (isFabrica(45) && strlen($peca) > 0 AND strlen($pedido_item)==0) {
                    $sqlX = "SELECT count(1), troca_obrigatoria
                            FROM tbl_produto
                            JOIN tbl_lista_basica USING(produto)
                            WHERE fabrica = $login_fabrica
                            AND   peca = $peca
                            GROUP BY troca_obrigatoria";
                    $resX = pg_query($con,$sqlX);
                    if (pg_num_rows($resX) == 1 and pg_fetch_result($resX,0,'troca_obrigatoria') == 't') {
                        $msg_erro = "A peça $peca_referencia indisponível, por favor entre contato com Fabricante!";
                        break;
                    }
                }
                /**
                 * @hd 764395 - Gravar IPI na tbl_pedido_item
                 */
                $ipi = "null";

                if (isFabrica(101)) {
                    $sqlIPI = "SELECT ipi FROM tbl_peca WHERE peca = $peca";
                    $resIPI = pg_query($con, $sqlIPI);

                    if (pg_num_rows($resIPI) == 1) {
                        $ipi = pg_fetch_result($resIPI, 0, 'ipi');
                    }
                }
                if (isFabrica(120)) { //hd_chamado=2765193
                    $sql = "SELECT linha
                                FROM tbl_produto
                                JOIN tbl_lista_basica ON tbl_lista_basica.produto = tbl_produto.produto
                                JOIN tbl_peca ON tbl_peca.peca = tbl_lista_basica.peca
                                WHERE tbl_produto.fabrica_i = $login_fabrica
                                and tbl_peca.peca = $peca
                                and tbl_produto.linha = $linha_produto";
                    $res = pg_query($con, $sql);

                    if (pg_num_rows($res) == 0) {
                        $linha_erro = $i;
                        $msg_erro = "Existem peças que não pertem a linha selecionada";
                    }
                }

                if (isFabrica(11,172) and $tipo_pedido_desc == 'Insumo' and $insumos == "embalagens" and !empty($peca)) {
                    $qry_pa = pg_query($con, "SELECT parametros_adicionais FROM tbl_peca WHERE peca = $peca");
                    $parametros_adicionais = json_decode(pg_fetch_result($qry_pa, 0, 'parametros_adicionais'), true);

                    $erro_insumos = false;

                    if (empty($parametros_adicionais) or !array_key_exists("embalagens", $parametros_adicionais)) {
                        $erro_insumos = true;
                    } elseif ($parametros_adicionais["embalagens"] <> "t") {
                        $erro_insumos = true;
                    }

                    if (true === $erro_insumos) {
                        $linha_erro = $i;
                        $msg_erro = "Peça não permitida para o Insumo selecionado";
                    }
                }

                if (strlen ($msg_erro) == 0 AND strlen($peca) > 0) {

                    if (isFabrica(139)) {

                        $sqlLinha = "SELECT tbl_produto.linha,tbl_lista_basica.peca, tbl_lista_basica.produto
                                      FROM tbl_lista_basica
                                      JOIN tbl_produto ON tbl_produto.produto=tbl_lista_basica.produto
                                      JOIN tbl_posto_linha ON tbl_posto_linha.linha=tbl_produto.linha AND tbl_produto.fabrica_i=$login_fabrica
                                      JOIN tbl_peca ON tbl_peca.peca = tbl_lista_basica.peca
                                     WHERE tbl_lista_basica.fabrica=$login_fabrica
                                       AND tbl_posto_linha.posto=$login_posto
                                       AND tbl_lista_basica.peca=$peca";
                        $resLinha = pg_query($con, $sqlLinha);

                        if (!pg_num_rows($resLinha)) {
                            $msg_erro = "Existem peças que não pertem a linha";
                        }

                    }

                    if (strlen($pedido_item) == 0){
                        $sql_comp = "SELECT pedido FROM tbl_pedido_item WHERE pedido = {$pedido} AND peca = {$peca};";
                        $res_comp = pg_query($con,$sql_comp);
                        if (isFabrica(104)) {
                            $sql = "SELECT  tbl_pedido_item.pedido_item
                            FROM  tbl_pedido
                            JOIN  tbl_pedido_item USING (pedido)
                            WHERE tbl_pedido.posto   = {$posto}
                            AND   tbl_pedido.fabrica = {$login_fabrica}
                            AND   tbl_pedido_item.pedido = {$pedido}
                            ORDER BY tbl_pedido_item.pedido_item";
                            $res = pg_query ($con,$sql);
                            if (pg_num_rows($res) >= 95) {
                                $msg_erro .= "Pedido com o limite máximo de itens. Para lançar novos itens, finalize este pedido e inicie um novo.<br />";
                            }
                        }

                        //echo nl2br($sql_comp);
                        if (strlen($msg_erro) == 0) {
                            if (pg_num_rows($res_comp) == 0 ) {
                                $sql = "INSERT INTO tbl_pedido_item (
                                        pedido ,
                                        peca   ,
                                        qtde   ,
                                        preco,
                                        ipi
                                    ) VALUES (
                                        $pedido ,
                                        $peca   ,
                                        $qtde   ,
                                        $preco,
                                        $ipi
                                    )";
                                $res = @pg_query ($con,$sql);
                                $msg_erro = pg_errormessage($con);

                                if ($login_fabrica == 123) {
                                    $obsPrev = (empty($prevEntrega)) ? "Sem observações" : $prevEntrega;
                                    $interacaoEntrega[] = $i.') '. $peca_referencia.' - '.$peca_descricao.' >>> '.$obsPrev;

                                    if ($disponibilidade == "Indisponível") {
                                        $temIndisponivel = true;
                                    }
                                }
                            }else{
                                $msg_erro = "Existem itens duplicados no pedido.";
                            }
                        }

                    }else{
                        $sql = "UPDATE tbl_pedido_item SET
                                    peca = $peca,
                                    qtde = $qtde
                                WHERE pedido_item = $pedido_item";
                        $res = @pg_query ($con,$sql);
                        $msg_erro = pg_errormessage($con);
                    }

                    #HD 15017
                    #HD 16686
                    if (isFabrica(3) AND $promocao_site=='t'){
                        ########## Validação de Quantidade #########
                        $sql = "SELECT SUM(tbl_pedido_item.qtde) AS qtde
                                FROM tbl_pedido
                                JOIN tbl_pedido_item USING(pedido)
                                WHERE  tbl_pedido.fabrica     = $login_fabrica
                                AND    tbl_pedido.posto       = $login_posto
                                AND    tbl_pedido.pedido      = $pedido
                                AND    tbl_pedido_item.peca   = $peca";
                        $res = pg_query ($con,$sql);
                        $pedido_item = "";
                        if (pg_num_rows ($res) > 0) {
                            $qtde_pedido = pg_fetch_result ($res,0,qtde);

                            if (strlen($msg_erro)==0 AND strlen($qtde_max)>0 AND $qtde_pedido > $qtde_max){
                                $msg_erro .= "Quantidade máxima permitida para a peça $peca_referencia é de $qtde_max.";
                            }
                            if (strlen($msg_erro)==0 AND strlen($qtde_disp)>0 AND $qtde_pedido > $qtde_disp){
                                $msg_erro .= "A peça $peca_referencia tem $qtde_disp unidades disponíveis.";
                            }
                            if (strlen($msg_erro)==0 AND strlen($qtde_multi)>0 AND $qtde_pedido % $qtde_multi <> 0){
                                $msg_erro .= "Para a peça $peca_referencia a quantidade deve ser múltiplo de $qtde_multi.";
                            }
                        }
                    }

                    /* Tira do estoque disponivel */
                    if (isFabrica(3) AND $promocao_site=='t' AND strlen($pedido_item) > 0 AND $peca_anterior <> $peca){
                        $sql = "UPDATE tbl_peca
                                SET   qtde_disponivel_site = qtde_disponivel_site + $qtde_anterior
                                WHERE peca     = $peca_anterior
                                AND   fabrica  = $login_fabrica
                                AND   promocao_site IS TRUE
                                AND qtde_disponivel_site IS NOT NULL";
                        $res = pg_query ($con,$sql);
                        $msg_erro .= pg_errormessage($con);
                        $qtde_anterior = 0;
                    }

                    if (isFabrica(3) AND $promocao_site=='t'){
                        $sql = "UPDATE tbl_peca
                                SET   qtde_disponivel_site = qtde_disponivel_site + $qtde_anterior -$qtde
                                WHERE peca     = $peca
                                AND   fabrica  = $login_fabrica
                                AND   promocao_site IS TRUE
                                AND qtde_disponivel_site IS NOT NULL";
                        $res = pg_query ($con,$sql);
                        $msg_erro .= pg_errormessage($con);
                    }

                    if (strlen($msg_erro) == 0 AND strlen($pedido_item) == 0) {
                        $res         = pg_query ($con,"SELECT CURRVAL ('seq_pedido_item')");
                        $pedido_item = pg_fetch_result ($res,0,0);
                        $msg_erro = pg_errormessage($con);
                    }

                    if (strlen($msg_erro) == 0) {
                        $sql = "SELECT fn_valida_pedido_item ($pedido,$peca,$login_fabrica)";
                        $res = @pg_query ($con,$sql);
                        $msg_erro = pg_errormessage($con);
                    }

                    if (strlen ($msg_erro) > 0) {
                        break ;
                    }
                }//faz a somatoria dos valores das peças para verificar o total das pecas pedidas
                //Apenas para Latina.
                if (isFabrica(15) || isFabrica(127))  {
                    if ( strlen($preco) > 0 AND strlen($qtde) > 0) {
                        $total_valor = (($total_valor) + ( str_replace( "," , "." ,$preco) * $qtde));
                    }
                }
            }
        }
        #HD 363162 INICIO
            if (isFabrica(50)){

                if (!empty($pedido_automatico)) {

                    if ($pedidos_linha) 
                    {
                        $res = pg_exec ($con,"BEGIN TRANSACTION");

                        $sql = "INSERT INTO tbl_pedido (
                                posto                          ,
                                fabrica                        ,
                                condicao                       ,
                                pedido_cliente                 ,
                                transportadora                 ,
                                linha                          ,
                                tipo_pedido                    ,
                                digitacao_distribuidor         ,
                                obs                            ,
                                atende_pedido_faturado_parcial
                                $sql_campo
                                ) VALUES (
                                $posto                         ,
                                $login_fabrica                 ,
                                $aux_condicao                  ,
                                $aux_pedido_cliente            ,
                                $aux_transportadora            ,
                                $aux_linha                     ,
                                $aux_tipo_pedido               ,
                                $digitacao_distribuidor        ,
                                $aux_observacao_pedido         ,
                                '$parcial'
                                $sql_valor
                                )RETURNING pedido";
                        $res = pg_exec ($con,$sql);
                        $msg_erro .= pg_errormessage($con);
                        $pedido_aut = pg_result($res,0,pedido);


                    }else{

                        $pedido_aut = $pedido;

                    }

                    foreach ($pedido_automatico as $item_automatico) 
                    {
                        $sql_automatico = "
                            INSERT INTO tbl_pedido_item (
                                pedido ,
                                peca   ,
                                qtde   ,
                                preco
                            ) VALUES (
                                $pedido_aut,
                                $item_automatico[peca],
                                $item_automatico[qtde],
                                $item_automatico[preco]
                            )
                        ";

                        $res = pg_exec ($con,$sql_automatico);
                        $msg_erro .= pg_errormessage($con);
                    }
                }
            }

                 


        #HD 363162 FIM

        //modificado para a Latina pois o valor nao pode ser menor do que R$80,00 reias.
        if (isFabrica(15)) {
            if ($tipo_pedido <> '90') 
                {
                if ($total_valor < 80) {
                    $msg_erro .= 'O valor mínimo não foi atingido ';
                }else{
                    //condicoes de pagamento depedendo do valor não se pode escolher a forma de pagamento
                    if ($condicao == 75 AND $total_valor < 200) {
                        $msg_erro = 'O total do pedido não atinge o valor mínimo da condição de pagamento';
                    }
                    if ($condicao == 98 AND $total_valor < 350) {
                        $msg_erro = 'O total do pedido não atinge o valor mínimo da condição de pagamento';
                    }
                    if ($condicao == 99 AND $total_valor < 600) {
                        $msg_erro = 'O total do pedido não atinge o valor mínimo da condição de pagamento';
                    }
                }
            }
        }

        if (isFabrica(127)) {
            $sql = "SELECT pedido_faturado FROM tbl_tipo_pedido WHERE fabrica = $login_fabrica AND tipo_pedido = $tipo_pedido";
            $res = pg_query($con, $sql);

            $pedido_faturado = pg_fetch_result($res, 0, "pedido_faturado");

            if ($pedido_faturado == "t" && $total_valor < 50) {
                $msg_erro .= 'O valor mínimo de R$ 50,00 não foi atingido ';
            }
        }
    }

    if (strlen ($msg_erro) == 0 AND !isFabrica(104,141,144)) {
        $sql = "SELECT fn_pedido_finaliza($pedido,$login_fabrica)";
        $res = pg_query ($con,$sql);
        $msg_erro = pg_errormessage($con);
    }
    
    //Status de pedido para fabricas de gestao interna
    if ($telecontrol_distrib) {
        $updateStatus = "UPDATE tbl_pedido SET status_pedido = 18 WHERE pedido = $pedido";
        pg_query($con, $updateStatus);
        $aux_sql = " INSERT INTO tbl_pedido_status(pedido, data, status,  observacao) VALUES ($pedido, current_timestamp, 18, 'Aguardando Aprovação')";
            $aux_res = pg_query($con, $aux_sql);
    }

    //HD 15482 //HD 27679 23/7/2008 GAMA  //HD 34765
    if ((isFabrica(98,51,122,123,124,128)) and strlen($msg_erro)==0) {
        $sql="SELECT sum((preco+(preco *(tbl_peca.ipi/100))) * qtde) AS total FROM tbl_pedido_item JOIN tbl_peca using(peca) WHERE pedido=$pedido";
        $res=@pg_query($con,$sql);
        if (pg_num_rows($res)>0) {
            $total=pg_fetch_result($res,0,total);
            if ($total < 30) {
                $msg_erro="O pedido faturado deve ser maior que R$ 30,00";
            }
        }
    }

    if ((isFabrica(80)) and strlen($msg_erro)==0) {
        $sql="SELECT total from tbl_pedido where pedido=$pedido";
        $res=@pg_query($con,$sql);
        if (pg_num_rows($res)>0) {
            $total=pg_fetch_result($res,0,total);
            if ($total < 25) {
                $msg_erro="O pedido faturado deve ser maior que R$ 25,00";
            }
        }
    }

    if ((isFabrica(30)) and strlen($msg_erro)==0) { // HD 70768
        $sql="SELECT total from tbl_pedido where pedido=$pedido";
        $res=@pg_query($con,$sql);
        if (pg_num_rows($res)>0) {
            $total=pg_fetch_result($res,0,total);
            if ($total < 60) {
                $msg_erro="O pedido deve ser maior que R$ 60,00";
            }
        }
    }
    
    if (strlen(trim($msg_erro)) == 0 && isFabrica(11,172)) {
        $sql_status = "UPDATE tbl_pedido SET status_pedido = 18 WHERE pedido = $pedido AND fabrica = $login_fabrica";
        $res_status = pg_query($con, $sql_status);
        $msg_erro .= pg_errormessage($con);
    }


    if (strlen ($msg_erro) == 0) {
            pg_query($con,"COMMIT TRANSACTION");
            $objLog->retornaDadosTabela()->enviarLog('update', "tbl_pedido", $login_fabrica."*".$pedido);
            $objItem->retornaDadosSelect()->enviarLog('update', "tbl_pedido", $login_fabrica."*".$pedido);
             
        if ($login_fabrica == 123 && $temIndisponivel) {
            $textMsg = "Prezada Assistência Técnica, confirmamos recebimentos de seu pedido $pedido, lembrando que existem considerações que foram demonstradas em tela quando gravou as peças:<br>".implode("<br>", $interacaoEntrega);

            $sqlMsg = "INSERT INTO tbl_interacao (fabrica, posto, contexto, registro_id, comentario, programa) VALUES ($login_fabrica, $login_posto, 2, $pedido, '$textMsg', '$PHP_SELF')";
            $resMsg = pg_query($con, $sqlMsg);
        }

        if($login_fabrica == 45){
            $sql = "SELECT codigo_posto, contato_email FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
            $res = pg_query($con,$sql);
            if (!pg_last_error($con)) {
                $codigo_posto  = pg_fetch_result($res, 0, 'codigo_posto');
                $contato_email = pg_fetch_result($res, 0, 'contato_email');

                $mailer = new PHPMailer(); //Class para envio de email com autenticação no servidor

                // Envia e-mail
                $email_para = array("auxadmastec@nksonline.com.br");
                $assunto    = "PEDIDO FATURADO";
                $mensagem  = "<p>Posto $codigo_posto realizou o seguinte pedido faturado $pedido.</p>";
                $mensagem .= "<p>Data do pedido: ".date("d/m/Y")."</p>";
                $mensagem .= "<p>Email do posto: {$contato_email}</p>";

                $mailer->IsSMTP();
                $mailer->IsHTML();

                foreach ($email_para as $email) {
                    $mailer->AddAddress($email);
                }

                $mailer->Subject = $assunto;
                $mailer->Body    = $mensagem;

                if (!$mailer->Send()) {
                    $msg_erro = "Ocorreu um erro durante o envio de e-mail.";
                }
            }
        }

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
            $corpo         = email_pedido($posto_nome, $fabrica_nome, $pedido, $cook_login, false, 'antiga');

            $mailTc = new TcComm($externalId);
            $res = $mailTc->sendMail(
                $contato_email,
                $assunto,
                utf8_encode($corpo),
                $externalEmail
            );
        }

        if ( ($pedido_aut != $pedido_normal) && isFabrica(50)){
            if (!$pedido_aut){
                header ("Location: pedido_finalizado.php?pedido=$pedido&loc=1&msg=Gravado com Sucesso!");
            } else {
                header ("Location: pedido_cadastro.php?ok=s&pa=$pedido_aut&pn=$pedido_normal");
            }
        } else if ( isFabrica(50) && ($pedido_aut == $pedido_normal) ){
            header ("Location: pedido_finalizado.php?pedido=$pedido&loc=1&msg=Gravado com Sucesso!");
        }
        if (!isFabrica(50,104,141,144) AND strlen($msg_erro) == 0){
            header ("Location: pedido_finalizado.php?pedido=$pedido&loc=1&msg=Gravado com Sucesso!");
        }

        if (isFabrica(141,144)) {
            header("Location: $PHP_SELF?listar=$pedido");
        }

        if (isFabrica(104)) {
            header("Location: $PHP_SELF?pedido=$pedido");
        }

    } else {
        $res = pg_query ($con,"ROLLBACK TRANSACTION");
    }
}


if ($btn_acao == "finalizar") {

    $pedido = $_REQUEST['pedido'];

    if (!isFabrica(104)) {
        $types = array("png", "jpg", "jpeg", "bmp", "pdf", 'doc', 'docx', 'odt');

        if (strlen($_FILES["comprovante_pedido"]["name"]) == 0 ) {
          $msg_erro = "Por favor inserir o comprovante de pagamento <br />";
        }
    }
    if (empty($msg_erro)) {

        if (!isFabrica(104)) {
            foreach ($_FILES as $key => $imagem) {

              if ((strlen(trim($imagem["name"])) > 0) && ($imagem["size"] > 0)) {
                if ($key == "comprovante_pedido") {
                  $type  = strtolower(preg_replace("/.+\//", "", $imagem["type"]));
                  if (!in_array($type, $types)) {
                    $pathinfo = pathinfo($imagem["name"]);
                    $type = $pathinfo["extension"];
                  }
                  if (!in_array($type, $types)) {

                    $msg_erro .= "Formato inválido, são aceitos os seguintes formatos: png, jpg, jpeg, bmp, doc, odt e pdf";
                    break;

                  } else {

                    $fileName = "comprovante_pedido_{$login_fabrica}_{$pedido}";

                    $amazonTC->upload($fileName, $imagem, "", "");

                    $link = $amazonTC->getLink("$fileName.{$type}", false, "", "");
                  }
                }
              }
            }
        }
        pg_query($con, "BEGIN TRANSACTION");

        $sql = "SELECT fn_pedido_finaliza ($pedido,$login_fabrica)";
        $res = pg_exec($con,$sql);
        $msg_erro = pg_errormessage($con);

        if (empty($msg_erro)) {

            pg_query($con, "COMMIT");



	     header ("Location: pedido_finalizado.php?pedido=$pedido&loc=1");

        } else {

            pg_query($con, "ROLLBACK");

        }
    }

}

if($login_fabrica == 104){
    if ($btn_acao == "atualiza_valor") {

        $pedido = $_REQUEST['pedido'];

        $msg_erro = atualizavalor($pedido);

        if (empty($msg_erro)) {

            header("Location: $PHP_SELF?pedido=$pedido");
        }
    
    }
}

function atualizavalor($pedido){
    global $con, $login_fabrica, $login_posto;

    if(!empty($pedido)){
        $sql_tabela = "SELECT tabela FROM tbl_posto_linha JOIN tbl_linha USING(linha) WHERE fabrica = $login_fabrica AND posto = $login_posto LIMIT 1";
        $res_tabela = pg_query($con,$sql_tabela);
    
        $sql_desconto = "SELECT desconto FROM tbl_posto_fabrica WHERE posto = {$login_posto} AND fabrica  = {$login_fabrica}";
        $res_desconto = pg_query($con, $sql_desconto);
        $desconto_posto = (pg_num_rows($res_desconto) > 0) ? pg_fetch_result($res_desconto, 0, 'desconto') : null;
    
        $valor_desconto = ($desconto_posto != '0' && $desconto_posto != null)  ? "-(tbl_tabela_item.preco*(1 + (tbl_peca.ipi / 100)) / 100 * $desconto_posto)" : null;
        
        if(pg_num_rows($res_tabela) > 0){
            $tabela = pg_fetch_result($res_tabela, 'tabela');
            $update_valor = "UPDATE tbl_pedido_item SET
                            preco = ((tbl_tabela_item.preco*(1 + (tbl_peca.ipi / 100))) $valor_desconto)
                            FROM tbl_tabela_item, tbl_peca
                            WHERE tbl_pedido_item.pedido = $pedido
                            AND tbl_tabela_item.tabela = $tabela
                            AND tbl_tabela_item.peca = tbl_pedido_item.peca
                            AND tbl_peca.peca = tbl_pedido_item.peca";
            
            $res_update_valor = pg_query($con,$update_valor);
        }
    
        
        return pg_errormessage($con);
    }else{
        return false;
    }  
}


function buscaGruposCondicaoPagamento(){

    global $con, $login_fabrica;

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

    return $grupos;
}


$btn_acao = $_GET['btn_acao'];

if ($btn_acao == "deletar") {

    $pedido = $_GET['pedido'];
    $pedido_item = $_GET['pedido_item'];

    $objLog = new AuditorLog();
    $objItem = new AuditorLog();
    $objLog->retornaDadosTabela('tbl_pedido', ['pedido'=>$pedido, 'fabrica'=>$login_fabrica]);
    $objItem->retornaDadosSelect("SELECT tbl_pedido_item.pedido_item, tbl_pedido_item.peca, tbl_pedido_item.qtde, tbl_pedido_item.preco, tbl_pedido_item.qtde_faturada, tbl_pedido_item.qtde_cancelada
    FROM tbl_pedido_item
    WHERE tbl_pedido_item.pedido = $pedido");

    $sql_verifica = "SELECT finalizado from tbl_pedido where tbl_pedido.pedido = $pedido";
    $res_verifica = pg_query($con,$sql_verifica);

    if (pg_num_rows($res_verifica) > 0){

        $finalizado = pg_fetch_result($res_verifica, 0, 0);

        if (!empty($finalizado)){

            $msg_erro = "Pedido já finalizado";

        }else{

            $res = pg_query($con,"BEGIN TRANSACTION");

			$sqlD = "UPDATE tbl_pedido_item SET pedido = 0 WHERE pedido_item = $pedido_item ";
            $resD = pg_query($con,$sqlD);
            $msg_erro = pg_errormessage($con);

            if (empty($msg_erro)) {

                $sql = "SELECT pedido_item FROM tbl_pedido_item WHERE pedido = $pedido";
                $res = pg_query($con,$sql);

                if (pg_numrows($res) == 0) {

                    if (empty($msg_erro)) {

                        $sqlP = "UPDATE tbl_pedido SET fabrica = 0 WHERE pedido = $pedido AND fabrica = $login_fabrica";
                        $resP = pg_query($con,$sqlP);
                        $msg_erro = pg_errormessage($con);

                    }

                }

            }

            if (strlen ( pg_errormessage ($con) ) > 0) {
                $res = pg_query($con,"ROLLBACK TRANSACTION");
                $msg_erro = pg_errormessage ($con) ;
            }else{
                $res = pg_query($con,"COMMIT TRANSACTION");

                $objLog->retornaDadosSelect()->enviarLog('update', "tbl_pedido", $login_fabrica."*".$pedido);
                $objItem->retornaDadosSelect()->enviarLog('update', "tbl_pedido", $login_fabrica."*".$pedido);
                header ("Location: $PHP_SELF");
                exit;
            }

        }

    }


}

#------------ Le Pedido da Base de dados ------------#
$pedido = $_GET['pedido'];

if (isFabrica(104,141,144)) {
    if (strlen($pedido) == 0 && isset($_GET['listar'])) {
        $pedido = $_GET['listar'];
    }
}
if (!empty($cook_pedido) && isFabrica(104)) {
    $pedido = $cook_pedido;
}

setcookie("cook_pedido", $pedido, time()+(3600*120));
$cook_pedido = $cookie_login['cook_pedido'];

if (strlen ($pedido) > 0) {

    $sql = "SELECT
                TO_CHAR(pedido.data, 'DD/MM/YYYY') AS data,
                pedido.pedido_cliente,
                pedido.tipo_frete,
                pedido.transportadora,
                transp.cnpj AS transp_cnpj,
                transp.nome AS transp_nome,
                transp_f.codigo_interno AS transp_codigo,
                transp_v.valor_kg AS transp_frete,
                pedido.tipo_pedido,
                pedido.produto,
                produto.referencia AS produto_referencia,
                produto.descricao AS produto_descricao,
                pedido.linha,
                pedido.condicao,
                pedido.obs,
                pedido.exportado,
                pedido.forma_envio,
                pedido.total_original,
                pedido.permite_alteracao,
                pedido.valores_adicionais
            FROM
                tbl_pedido pedido
            LEFT JOIN
                tbl_transportadora_fabrica transp_f ON transp_f.transportadora = pedido.transportadora AND transp_f.fabrica = $login_fabrica
            LEFT JOIN
                tbl_transportadora transp ON transp.transportadora = transp_f.transportadora
            LEFT JOIN
                tbl_produto produto ON produto.produto = pedido.produto
            LEFT JOIN tbl_transportadora_valor transp_v ON transp.transportadora = transp_v.transportadora AND transp_v.fabrica = $login_fabrica
            WHERE
                pedido.pedido = $pedido
                AND
                    pedido.fabrica = $login_fabrica
                AND
                    pedido.posto = $login_posto";
    $res = pg_query ($con,$sql);

    if (pg_num_rows ($res) > 0) {
        $data                  = trim(pg_fetch_result ($res,0,data));
        $transportadora        = trim(pg_fetch_result ($res,0,transportadora));
        $transportadora_cnpj   = trim(pg_fetch_result ($res,0,transp_cnpj));
        $transportadora_codigo = trim(pg_fetch_result ($res,0,transp_codigo));
        $transportadora_nome   = trim(pg_fetch_result ($res,0,transp_nome));
        $transportadora_frete  = trim(pg_fetch_result ($res,0,transp_frete));
        $pedido_cliente        = trim(pg_fetch_result ($res,0,pedido_cliente));
        $tipo_pedido           = trim(pg_fetch_result ($res,0,tipo_pedido));
        $produto               = trim(pg_fetch_result ($res,0,produto));
        $produto_referencia    = trim(pg_fetch_result ($res,0,produto_referencia));
        $produto_descricao     = trim(pg_fetch_result ($res,0,produto_descricao));
        $linha                 = trim(pg_fetch_result ($res,0,linha));
        $condicao              = trim(pg_fetch_result ($res,0,condicao));
        $exportado             = trim(pg_fetch_result ($res,0,exportado));
        $total_original        = trim(pg_fetch_result ($res,0,total_original));
        $permite_alteracao     = trim(pg_fetch_result ($res,0,permite_alteracao));
        $forma_envio     = trim(pg_fetch_result ($res,0,forma_envio));
        $observacao_pedido     = @pg_fetch_result ($res,0,obs);
        if (isFabrica(24) && qual_tipo_posto($login_posto) == 696) {
            $valores_adicionais = json_decode(pg_fetch_result($res, 0, 'valores_adicionais'), true);
            $registro_funcionario = $valores_adicionais['registro_funcionario'];
            $departamento_funcionario = $valores_adicionais['departamento_funcionario'];
            $nome_funcionario = $valores_adicionais['nome_funcionario'];
            $qtde_parcelas = $valores_adicionais['qtde_parcelas'];
        }
    }
}

#---------------- Recarrega Form em caso de erro -------------
if (strlen ($msg_erro) > 0) {
    $pedido         = $_POST['pedido'];
    $condicao       = $_POST['condicao'];

    if (isFabrica(104)) { //3084076
        $pedido_condicao = $_POST['pedido_condicao'];
        if (strlen(trim($pedido_condicao)) > 0) {
            $condicao = $pedido_condicao;
        }
    }

    $tipo_pedido    = $_POST['tipo_pedido'];
    $pedido_cliente = $_POST['pedido_cliente'];
    $transportadora = $_POST['transportadora'];
    $linha          = $_POST['linha'];
    $linha_produto  = $_POST['linha_produto']; //hd_chamado=2765193
    $codigo_posto   = $_POST['codigo_posto'];
    $parcial        = $_POST['parcial'];

    if (isFabrica(24) && qual_tipo_posto($login_posto) == 696) {
        $registro_funcionario     = $_POST['registro_funcionario'];
        $departamento_funcionario = $_POST['departamento_funcionario'];
        $nome_funcionario         = $_POST['nome_funcionario'];
        $qtde_parcelas            = $_POST['qtde_parcelas'];
    }
}

$msg = $_GET['msg'];
$title       = "Cadastro de Pedidos de Peças";
$layout_menu = 'pedido';

include "cabecalho.php";

if ($telecontrol_distrib && $login_bloqueio_pedido) {
    echo "<p>";
    echo "<table border=1 align='center'><tr><td align='center'>";
    echo "<font face='verdana' size='2' color='FF0000'>";
    echo "<b>Entre em contato com ACACIAELETRO</b><br>";
    echo "<b>
    E-mail - contabil@acaciaeletro.com.br <br>
    Telefone: (011) 4063-0036
    </b></font>";
    echo "</td></tr></table>";
    echo "<p>";

    include "rodape.php";
    exit;
}

?>

<SCRIPT LANGUAGE="JavaScript">
function exibeTipo(){
    f = document.frm_pedido;
    if (f.linha.value == 3) {
        f.tipo_pedido.disabled = false;
    }else{
        f.tipo_pedido.selectedIndex = 0;
        f.tipo_pedido.disabled = true;
    }
}

/* FUNÇÃO PARA INTELBRAS POIS TEM POSIÇÃO PARA SER PESQUISADA */
function fnc_pesquisa_peca_lista_intel (produto_referencia, peca_referencia, peca_descricao, peca_posicao, tipo) {

    var url = "";

    if (tipo == "tudo") {
        url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_referencia.value + "&tipo=" + tipo + "&faturado=sim" ;
    }

    if (tipo == "referencia") {
        url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&peca=" + peca_referencia.value + "&tipo=" + tipo + "&faturado=sim" ;
    }

    if (tipo == "descricao") {
        url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&tipo=" + tipo + "&faturado=sim" ;
    }


    if (peca_referencia.value.length >= 3 || peca_descricao.value.length >= 3) {
        janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
        janela.produto      = produto_referencia;
        janela.referencia   = peca_referencia;
        janela.descricao    = peca_descricao;
        janela.posicao      = peca_posicao;
        janela.focus();
    } else {
        if (document.getElementById('controle_blur').value == 0) {
            alert("Digite pelo menos 3 caracteres!");
        }
    }
}

function verificaDispPeca(i) {
    let prevEntrega = $("#prevEntrega_"+i).val();
    let disponibilidade = $("#disponibilidade_"+i).val();

    if ($("#peca_referencia_"+i).val() == "" && $("#peca_descricao_"+i).val() == "") {
        $(".msg_erro_disp_"+i).hide();
        $(".class_tr_"+i).css('background-color', '#d9e2ef');
        $("#prevEntrega_"+i).val('');
        $("#disponibilidade_"+i).val('');
    } else {
        if (disponibilidade == "Indisponível") {
            $(".class_tr_"+i).css('background-color', '#e0adad');
            $(".msg_erro_disp_"+i).css('background-color', '#e0adad').show();
            $(".msg_erro_txt_"+i).css('text-align', 'center');

            if (prevEntrega.length > 15) {
                $(".msg_erro_txt_"+i).html('<b>'+prevEntrega+'<b>');
                $("#prevEntrega_"+i).val(prevEntrega)
            } else {
                $(".msg_erro_txt_"+i).html('<b>Previsão de Recebimento dessa Peça em '+prevEntrega+', Sujeito a Alteração<b>');
                $("#prevEntrega_"+i).val('Previsão de Recebimento dessa Peça em '+prevEntrega+', Sujeito a Alteração')
            }
        }
    }

}

function verificaTab(event) {

    var tecla = event.keyCode ? event.keyCode : event.which ? event.which : event.charCode;

    if (tecla == 9) {
        return true;
    } else {
        return false;
    }

}

function VerificakitPeca(){
   
    var referencia = new Array();
    var resposta = $("#gravakit").val();
    if(resposta == 'sim'){
        if (document.frm_pedido.btn_acao.value == '' ) { 
            document.frm_pedido.btn_acao.value='gravar' ; document.frm_pedido.submit(); 
        }
    }else{ 
        $ ('input[name^="peca_referencia_"]') .each(function(index) {
            if($(this).val() != ""){
                referencia.push( $(this).val() +"-"+ $("#qtde_"+index).val());
            }      
        });

        if(referencia.length > 0){
            $.ajax({
                url : "<?php echo $_SERVER['PHP_SELF']; ?>",
                type: "POST",
                data: {
                    verifica_estoque_peca_kit: true,
                    referencias : referencia
                },
                complete: function(data){
                    data = data.responseText;
                    $("#valoresKit").val(data);

                    var dados = $.parseJSON(data);
                    if(dados.semEstoque == true){
                        
                        Shadowbox.open({
                            content:    "verificaKitPeca.php?dados="+ data,
                            player: "iframe",
                            title:      "Kit Peca",
                            width:  800,
                            height: 500
                        });
                    }else{
                        if (document.frm_pedido.btn_acao.value == '' ) { 
                            document.frm_pedido.btn_acao.value='gravar' ; document.frm_pedido.submit() 
                        }
                    }
                }
            });
        }    
    }
}

function retorno_troca_kit(resposta){
    if(resposta == 'nao'){
        if (document.frm_pedido.btn_acao.value == '' ) { 
            document.frm_pedido.btn_acao.value='gravar' ; document.frm_pedido.submit() 
        }
    }else{ 
        $("#gravakit").val(resposta);
        var dados = $.parseJSON($("#valoresKit").val());
        var kit = dados.kit;
        var pecas = dados.pecas;
        var cont = 0;

        $ ('input[name^="peca_referencia_"]').each(function(index) {
            if($(this).val() != ""){
                if( $.inArray( $(this).val(), pecas) !== -1 ){
                    $("#produto_referencia_"+index).val('');
                    $("#produto_descricao_"+index).val('');
                    $("#peca_referencia_"+index).val('');
                    $("#peca_descricao_"+index).val('');
                    $("#qtde_"+index).val('');
                    $("#preco_"+index).val('');
                    $("#sub_total_"+index).val('');              
                }      
            }else{
                if( typeof(kit[cont]) !== "undefined"){
                    $("#peca_referencia_"+index).val(kit[cont].referencia);
                    cont++;
                }
            }
            $( "#peca_referencia_0" ).focus();
        });
    }
}

</SCRIPT>


<style type="text/css">
body {
    font: 80% arial;
    /* An explicit background color needed for the Safari browser. */
    /* Without this, Safari users will see black in the corners. */
    background: #FFF;
}

/* The styles below are NOT needed to make .corner() work in your page. */
/*

h1 {
    font: bold 150% Verdana,Arial,sans-serif;
    margin: 0 0 0.25em;
    padding: 0;
    color: #009;
}
h2 {
    font: bold 100% Verdana,Arial,sans-serif;
    margin: 0.75em 0 0.25em;
    padding: 0;
    color: #006;
}
ul {
    margin-top: 0.25em;
    padding-top: 0;
}
code {
    font: 90% Courier New,monospace;
    color: #33a;
    font-weight: bold;
}
#demo {

}*/



.titulo {
    background:#7392BF;
    width: 650px;
    text-align: center;
    padding: 1px 1px; /* padding greater than corner height|width */
/*  margin: 1em 0.25em;*/
    font-size:12px;
    color:#FFFFFF;
    font-family: arial;
}
.titulo h1 {
    color:white;
    font-size: 120%;
    font-family: arial;
}

.subtitulo {
    background:#FCF0D8;
    width: 600px;
    text-align: center;
    padding: 2px 2px; /* padding greater than corner height|width */
    margin: 10px auto;
    color:#392804;
    font-family: arial;
}
.subtitulo h1 {
    color:black;
    font-size: 120%;
    font-family: arial;
}

.content {
    background:#CDDBF1;
    width: 600px;
    text-align: center;
    padding: 5px; /* padding greater than corner height|width */
    margin: 1em 0.25em;
    color:black;
    font-family: arial;
}

.content h1 {
    color:black;
    font-size: 120%;
    font-family: arial;
}

.extra {
    background:#BFDCFB;
    width: 600px;
    text-align: center;
    padding: 2px 2px; /* padding greater than corner height|width */
    margin: 1em 0.25em;
    color:#000000;
    text-align:left;
    font-family: arial;
}
.extra span {
    color:#FF0D13;
    font-size:14px;
    font-weight:bold;
    padding-left:30px;
    font-family: arial;
}

.error {
    background:#ED1B1B;
    width: 600px;
    text-align: center;
    padding: 2px 2px; /* padding greater than corner height|width */
    margin: 1em 0.25em;
    color:#FFFFFF;
    font-size:12px;
    font-family: arial;
}


.error h1 {
    color:#FFFFFF;
    font-size:14px;
    font-size:normal;
    text-transform: capitalize;
    font-family: arial;
}

.inicio {
    background:#8BBEF8;
    width: 600px;
    text-align: center;
    padding: 1px 2px; /* padding greater than corner height|width */
    margin: 0.0em 0.0em;
    color:#FFFFFF;
    font-family: arial;
}
.inicio h1 {
    color:white;
    font-size: 105%;
    font-weight:bold;
    font-family: arial;
}

.subinicio {
    background:#E1EEFD;
    width: 550px;
    text-align: center;
    padding: 1px 2px; /* padding greater than corner height|width */
    margin: 0.0em 0.0em;
    color:#FFFFFF;
    font-family: arial;
}
.subinicio h1 {
    color:white;
    font-size: 105%;
    font-family: arial;
}

#tabela {
    font-size:12px;
    font-family: arial;
}
#tabela td{
    font-weight:bold;
    font-family: arial;
}

.xTabela{
    font-family: Verdana, Arial, Sans-serif;
    font-size:12px;
    padding:3px;
    font-family: arial;
}

.xTabela td{
    font-family: arial;
    /*border-bottom:2px solid #9E9E9E;*/
}

.titulo_coluna_lenoxx th {
    padding-left: 15px !important;
    padding-right: 14px !important;
}

</style>

<style type="text/css">

#layout{
    width: 780px;
    margin:0 auto;
}

ul#split, ul#split li{
    margin:50px;
    margin:0 auto;
    padding:0;
    width:800px;
    list-style:none
}

ul#split li{
    float:left;
    width:800px;
    margin-left: 10px;
}

ul#split h3{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    margin:0px;
    padding: 5px 0 0;
    text-align:center;
    font-weight:bold;
    color:white;
}
ul#split h4{
    font-size:90%
    margin:0px;
    padding-top: 1px;
    padding-bottom: 1px;
    text-align:center;
    font-weight:bold;
    color:white;
}

ul#split p{
    margin:0;
    padding:5px 8px 2px
}

ul#split div{
    background: #D9E2EF
}

li#one{
    text-align:left;

}

li#one div{
    border:1px solid #596D9B
}
li#one h3{
    background: #596d9b;
}

li#one h4{
    background: #7092BE;
}

.coluna1{
    width:250px;
    font-weight:bold;
    display: inline;
    float:left;
}

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
    font-family: arial;
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
    font-family: arial;
}

.subtitulo{
    background-color: #7092BE;
    font:bold 11px Arial;
    color: #FFFFFF;
    font-family: arial;
}

table.tabela tr td{
    font-family: arial;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:800px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
    padding: 10px;
}
.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

table.sucesso td a{
    color:white !important;
    text-decoration:underline !important;
}

table.sucesso td a:hover{
    color: #C6E2FF !important;
    text-decoration:underline;
}

</style>

<!-- Bordas Arredondadas para a JQUERY -->
<script type="text/javascript" src="js/jquery-1.2.1.pack.js"></script>
<script type="text/javascript" src="js/jquery.corner.js"></script>
<script type="text/javascript">
</script>

<!-- Bordas Arredondadas para a NIFTY -->
<script type="text/javascript" src="js/niftycube.js"></script>
<script type="text/javascript">
    window.onload=function(){
        Nifty("ul#split h3","top");
        Nifty("ul#split div","none same-height");
    }
</script>


<? include "javascript_pesquisas_novo.php" ?>

<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>
<script src="plugins/shadowbox/shadowbox.js"    type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">
<script src="admin/plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script type="text/javascript" src="admin/js/jquery.maskmoney.js"></script>
<script type="text/javascript">

function somente_numero(e){
    var tecla=(window.event)?event.keyCode:e.which;
    if ((tecla>47 && tecla<58))  return true;
    else{
        if (tecla==8 || tecla==0) return true;
    else  return false;
    }
}


function fnc_makita_preco (linha_form) {

    condicao = window.document.frm_pedido.condicao.value ;
    posto    = <?= $login_posto ?>;

    campo_preco = 'preco_' + linha_form;
    document.getElementById(campo_preco).value = "0,00";

    peca_referencia = 'peca_referencia_' + linha_form;
    peca_referencia = document.getElementById(peca_referencia).value;

    url = 'makita_valida_regras.php?linha_form=' + linha_form + '&posto=<?= $login_posto ?>&produto_referencia=' + peca_referencia + '&condicao=' + condicao + '&cache_bypass=<?= $cache_bypass ?>';
    requisicaoHTTP ('GET', url , true , 'fnc_makita_responde_preco');

}

function fnc_makita_responde_preco (campos) {
    campos = campos.substring (campos.indexOf('<preco>')+7,campos.length);
    campos = campos.substring (0,campos.indexOf('</preco>'));
    campos_array = campos.split("|");

    preco      = campos_array[0] ;
    linha_form = campos_array[1] ;

    campo_preco = 'preco_' + linha_form;
    document.getElementById(campo_preco).value = preco;
    fnc_calcula_total(linha_form);
}
function fnc_calcula_total (linha_form) {
    var total = 0;
    preco = document.getElementById('preco_'+linha_form).value;
    qtde = document.getElementById('qtde_'+linha_form).value;
    qtde_demanda = "";
    somar = true;

<?php
if (isFabrica(11,104,172)) {
?>
    qtde_demanda = document.getElementById('qtde_demanda_'+linha_form).value;

<?php
}
?>
    if (qtde_demanda.length > 0) {
        if (parseInt(qtde_demanda) < qtde) {
            somar = false;
        }
    }

    if (somar === true) {
        if (preco.search(/\d{1,3},\d{1,4}$/) != -1) { // Se o preço estiver formatado...
            preco = preco.replace('.','');
            preco = preco.replace(',','.');
        }
        if (qtde && preco){
            total = qtde * preco;
            <? if (isFabrica(94)) {?>
                total = total.toFixed(3);
            <? }else{?>
                total = total.toFixed(2);
                total = total.replace('.',',');
            <? } ?>
        }
        document.getElementById('sub_total_'+linha_form).value = total;


        //Totalizador
        var total_pecas = 0;
        $ ("input[rel='total_pecas']").each(function(){
            if ($(this).val()){
                tot = $(this).val();
                tot = tot.replace(',','.');
                tot = parseFloat(tot);
                total_pecas += tot;
            }
        });

        <?php
        if (isFabrica(138)) {
        ?>
        if ($("#valor_desconto").val() != "") {
            var desconto = parseFloat($("#valor_desconto").val());
            total_pecas -= (total_pecas / 100) * desconto;
        }
        <?php
        }
        ?>

        <?if (!isFabrica(24,30,94)) { ?>
        total_pecas = total_pecas.toFixed(2);
        total_pecas = total_pecas.replace('.',',');
        document.getElementById('total_pecas').value = total_pecas;
        $("#total_pecas").change();
        <?}?>

        <?php
        if (isFabrica(94)) {
        ?>
        if ($("#valor_frete").val() != "") {
            var frete = parseFloat($("#valor_frete").val());
            var total_pecas_frete = total_pecas + frete;

            total_pecas = total_pecas.toFixed(2);
            total_pecas = total_pecas.replace('.',',');
            document.getElementById('total_pecas').value = total_pecas;

            total_pecas_frete = total_pecas_frete.toFixed(2);
            total_pecas_frete = total_pecas_frete.replace('.',',');
            $("#valor_total_frete").val(total_pecas_frete);
        }
        <?php
        }
        ?>
    } else {
        alert("Quantidade indicada é maior que a máxima determinada pela fábrica.");
        document.getElementById('qtde_'+linha_form).value = "";
    }
}

function atualiza_proxima_linha(linha_form){
    var produto_referencia = document.getElementById('produto_referencia_'+linha_form).value;
    var produto_descricao  = document.getElementById('produto_descricao_'+linha_form).value;

    var proxima_linha = linha_form + 1;

    if ( document.getElementById('qtde_'+linha_form).value > 0) {
        if (document.getElementById('produto_descricao_'+proxima_linha)){
            if (! document.getElementById('produto_descricao_'+proxima_linha).value){
                document.getElementById('produto_referencia_'+proxima_linha).value = produto_referencia;
                document.getElementById('produto_descricao_'+proxima_linha).value = produto_descricao;
            }
        }
    }
    $("input[name=peca_referencia_"+proxima_linha+"]").focus();
}

$(document).ready(function() {
    Shadowbox.init();

    var forma_envio = $("#forma_envio").val();
    if (forma_envio == 2) {
        document.getElementById('linha_transportadora').style.display = 'block';
    }

<?php
    if (isFabrica(104)) {
?>

    function buscaCondicoesPagamento(valor, onReady){

        var total = $("#total_pedidos").val();
        var existe_valor = false;

        if(valor != "" && valor != "0,00" && parseInt(valor) > 0){
            existe_valor = true;
        }

        if(total != "" && total != "0,00" && parseInt(total) > 0){
            existe_valor = true;
        }

        if(existe_valor){

            $.ajax({
                url : "pedido_cadastro.php",
                type: "POST",
                dataType: "json",
                data: {
                    busca_condicao_pagamento: true,
                    valor : valor,
                    total_pedidos : total
                },
                success: function(response){  

                    if(response){

                        var select = $("select[name='condicao']");
                        var condicao = select.data('condicao');

                        select.empty();
                        var option = new Option("Selecione", "");
                        select.append(option);

                        $(response).each(function (index, item) {

                            var option = null;

                            // Só irá entrar na condição caso a função seja chamada ao recarregar a página
                            if(item.condicao == condicao && onReady){
                                // Cria option já selecionado (selected)
                                option = new Option(item.descricao, item.condicao, false, true);
                            
                            }else{
                                option = new Option(item.descricao, item.condicao);
                            }

                            select.append(option);
                        });
                        select.attr('disabled', false);
                    }else{
                        $("select[name='condicao']").attr('disabled', true);
                    }
             
                },
                error: function (request, status, error) {
                    console.log(error);
                }
            });
        }else{
            $("select[name='condicao']").attr('disabled', true);
        }
    }
        
   /* if($("select[name='condicao']").val() != ""){
        $("select[name='condicao']").prop('disabled', true);
    }else{
        $("select[name='condicao']").prop('disabled', false);
    }*/

    var total_pedidos = $("#total_pedidos").val();
    var total_pecas = $("#total_pecas").val();

    if(total_pecas != "" || total_pedidos != ""){
        var valor = $("#total_pecas").val();
        buscaCondicoesPagamento(valor, true);
    }else{
         $("select[name='condicao']").attr('disabled', true);
    }

    $("#total_pecas").change(function(){
        var valor = $(this).val(); 
        buscaCondicoesPagamento(valor);
    });

<?php
}
?>
<?php
    if (isFabrica(11,172)) {
?>
    $('select[name="tipo_pedido"]').on("change", function(){
        var selected = $('select[name="tipo_pedido"]').find(":selected").text();

        if (selected == "Insumo") {
            $("#insumos").removeAttr("disabled");
        } else {
            $("#insumos").prop("disabled", "disabled");
        }

        if (!$('input[name="pedido"]').val()) {
            limpaInputPecas();
        }
    });

    $("#insumos").on("change", function() {
        if (!$('input[name="pedido"]').val()) {
            limpaInputPecas();
        }
    });

    function limpaInputPecas()
    {
        $ ('input[name^="produto_referencia_"]') .each(function() {
          $(this).val("");
        });

        $ ('input[name^="produto_descricao_"]') .each(function() {
          $(this).val("");
        });

        $ ('input[name^="peca_referencia_"]') .each(function() {
          $(this).val("");
        });

        $ ('input[name^="peca_descricao_"]') .each(function() {
          $(this).val("");
        });

        $ ('input[name^="qtde_"]') .each(function() {
          $(this).val("");
        });

        $ ('input[name^="preco_"]') .each(function() {
          $(this).val("");
        });

        $ ('input[name^="sub_total_"]') .each(function() {
          $(this).val("");
        });

        $("#total_pecas").val("");
    }
<?php

    }
    if (isFabrica(74)) {?>

        $('input[name*="qtde_"]').blur( function(e){
            var referencia = new Array();

            $ ('input[name*="qtde_"]') .each(function(indice){
                var linha           = $(this).attr('rel');
                var qtde            = $(this).val();
                var peca_referencia = jQuery.trim($("#peca_referencia_"+linha).val());
                var peca_descricao  = jQuery.trim($("#peca_descricao_"+linha).val());

                if (peca_referencia.length > 0) {
                    if (Array.indexOf(referencia, peca_referencia) == -1) {
                        referencia[indice] = peca_referencia;
                    }else{
                        alert("A peça '"+peca_referencia+" - "+peca_descricao+"' já selecionada!\n\nNão é permitido duas peças iguais no mesmo pedido. Altere sua quantidade.");
                        $("#peca_referencia_"+indice).val('');
                        $("#peca_descricao_"+indice).val('');
                        $("#qtde_"+indice).val('');
                        $("#preco_"+indice).val('');
                        $("#sub_total_"+indice).val('');

                        fnc_calcula_total(indice);

                        $("#peca_referencia_"+indice).focus();
                    }
                }
            });

            //alert(referencia.toString());
        });

    <?php }
if (isFabrica(88,120)) {
?>
    var login_fabrica = <?=$login_fabrica?>;
    $('button[id=calcular_frete]').click(function(){
        var pecas = new Array();
        var qtde  = new Array();
        var linha = new Array();
        $ ('input[id^=peca_referencia_]') .each(function(index){
            if ($(this).val() != "") {
                linha.push(index);
                pecas.push($(this).val());
                qtde.push($('input[id=qtde_'+index+']').val());
            }
        });
        $.ajax({
            url:"<?$PHP_SELF?>",
            type:"POST",
            dataType:"json",
            data:{
                ajax:"sim",
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
            var posto_estado    = result[0].posto_estado;
            var freteForm;
            var calculoSeguro;
            var calculoGris;
            var totalPedido;
            var total = $("input[id=total_pecas]").val();

            var estadoNordeste = ["MA", "PI", "CE", "RN", "PE", "PB", "SE", "AL"];

            total = total.replace(".","");
            total = total.replace(",",".");
            if (login_fabrica == 88) {
                if (total < 1000.00 || $.inArray(posto_estado, estadoNordeste) != -1) {
                    totalPedido = parseFloat(frete) + parseFloat(total);
                    frete = parseFloat(frete).toFixed(2);
                    freteForm = frete.replace('.',',');
                }else{
                    totalPedido = parseFloat(total);
                    freteForm = "0,00";
                    alert("Pedido com valor acima de R$ 1000,00 terá frete grátis");
                }
            }else{
                calculoSeguro   = (parseFloat(total) * (parseFloat(seguro) / 100));
				calculoGris     = (parseFloat(total) * (parseFloat(gris) / 100));
				frete           = frete + calculoSeguro + calculoGris
                totalPedido     = parseFloat(frete) + parseFloat(total);
                frete = parseFloat(frete).toFixed(2);
                freteForm = frete.replace('.',',');
            }
            totalPedido = totalPedido.toFixed(2);
            totalPedido = totalPedido.replace('.',',');

            $("input[id=valor_frete]").val(freteForm);
            $("input[id=valor_total_frete]").val(totalPedido);
            $("input[id=transportadora]").val(transportadora);
            $("input[id=frete_calculado]").val("sim");
        })
        .fail(function(result){
            <?php if (isFabrica(88)) {?>
                alert("Não foi possível encontrar transportadora, peso de peças ou faixa de frete, contate a fábrica para verificação");
            <?php } ?>
            <?php if (isFabrica(120)) {?>
                $("#valor_total_frete").val($("#total_pecas").val());
                $("#frete_calculou").val('nao');
            <?php } ?>
        });

        $("#itens_pedido").find("input").change(function(){
            $("input[id=valor_frete]").val("");
            $("input[id=frete_calculado]").val("");
            $("input[id=valor_total_frete]").val($("input[id=total_pecas]").val());
        });
    });
<?
}
?>

<?php if (isFabrica(24) && qual_tipo_posto($login_posto) == 696) { ?>

    function buscaQtdeParcelas(condicao, onReady){

        $.ajax({

            url: window.location,
            type : 'POST',
            dataType : 'json',
            data : { busca_qtde_parcelas : true, condicao : condicao},
            success : function(response){
  
                var select = $("select[name=qtde_parcelas]");
                var parcelas = select.data('parcelas');

                select.empty();
                select.append(new Option("Selecione", ""));

                var qtde_parcelas = response.result;

                if(qtde_parcelas){

                    for(i = 1; i <= qtde_parcelas; i++){

                         var option = null;

                         // Cria option já selecionado (selected)
                        if(i == parcelas && onReady){
                            option = new Option(i, i, false, true);
                        }else{
                            option = new Option(i, i);
                        }

                        select.append(option);
                    }
                }
            },
            error: function (request, status, error) {
                console.log(error);
            }
        });

    }

    if($("select[name=condicao]").val() != ''){
        var condicao = $("select[name=condicao]").val();
        buscaQtdeParcelas(condicao, true);
    }

    $("select[name=condicao]").change(function(){
        buscaQtdeParcelas($(this).val());
    });

<? } ?>

});

<?php if (isFabrica(120)) {//hd_chamado=2765193 ?>

    function linhaProduto(){
        var linha = $("#linha_produto").val();
        var campo_linha = $("#campo_linha_produto").val();

        if (campo_linha == '') {
            $("#campo_linha_produto").val(linha);
        }else if (linha != campo_linha) {
            if (confirm("Caso você tenha digitado PRODUTOS/INTES eles serão perdidos")==true){
                $("#campo_linha_produto").val(linha);
                $("#itens_pedido").find('input').val('');
            }else{
                $("#linha_produto").val(campo_linha);
            }
        }
    }

<?php } ?>

function mostraTransp(){
    var transp = $('#forma_envio').val();
    if (transp == 2) {
        document.getElementById('linha_transportadora').style.display = 'block';
    } else {
        document.getElementById('linha_transportadora').style.display = 'none';
        $("input[name='transportadora_nome']").val('');
    }
}

function fnc_pesquisa_transportadora(campo,tipo){
    var campo = campo.value;

    if (jQuery.trim(campo).length > 2){
        Shadowbox.open({
            content:    "pesquisa_transportadora_nv.php?"+tipo+"="+campo+"&tipo="+tipo,
            player: "iframe",
            title:      "Pesquisa Transportadora",
            width:  800,
            height: 500
        });
    }else
        alert("Informar toda ou parte da informação para realizar a pesquisa!");
}

function alterar_fabrica(fabrica){

    location.href = "pedido_cadastro.php?alterar_fabrica=sim&fabrica="+fabrica;

}

function retorna_transportadora(transportadora,nome,cnpj,fantasia,codigo_interno,frete){
    gravaDados("transportadora_nome",nome);
    gravaDados("transportadora_codigo",codigo_interno);
    gravaDados("transportadora",transportadora);
    gravaDados("transportadora_cnpj",cnpj);
    gravaDados("transportadora_frete",frete);

    <?php if (isFabrica(94)) {?>
            $("#valor_frete").val(frete);
    <?php } ?>
}

function gravaDados(name, valor){
    try {
        $("input[name="+name+"]").val(valor);
    } catch(err){
        return false;
    }
}

var num_qtde = 0;

function addLinha(qtde_item){

    if (num_qtde == 0) {
        num_qtde = qtde_item;
    }else{
        num_qtde = parseInt(num_qtde + 1);
        $('input[name=qtde_item_hidden]').val(num_qtde);
    }

    var html = " \
        <tr bgcolor='' nowrap='' rel='tr_"+num_qtde+"'>\
            <td align='left'>\
                <input style='width:55px' class='frm' type='text' name='produto_referencia_"+num_qtde+"' onfocus='this.select()' id='produto_referencia_"+num_qtde+"' size='7' maxlength='20' value=''>&nbsp;<img src='imagens/lupa.png' style='cursor:pointer' border='0' alt='Clique para pesquisar pela referência do produto' align='absmiddle' onclick='javascript: fnc_pesquisa_produto (\"\",document.frm_pedido.produto_referencia_"+num_qtde+","+num_qtde+")'>\
            </td>\
            <td align='left'>\
                <input style='width:135px' class='frm' type='text' name='produto_descricao_"+num_qtde+"' onfocus='this.select()' id='produto_descricao_"+num_qtde+"' size='12' value=''>&nbsp;<img src='imagens/lupa.png' style='cursor:pointer' border='0' align='absmiddle' alt='Clique para pesquisar pela descrição do produto' onclick='javascript: fnc_pesquisa_produto (document.frm_pedido.produto_descricao_"+num_qtde+",\"\","+num_qtde+")'>\
                <input type='hidden' name='produto_voltagem_"+num_qtde+"'>\
            </td>\
            <td align='left' nowrap=''>\
                <input type='hidden' name='pedido_item_"+num_qtde+"' size='15' value=''>\
                <input style='width:65px' class='frm' type='text' name='peca_referencia_"+num_qtde+"' id='peca_referencia_"+num_qtde+"' size='15' value='' onkeydown='if (verificaTab(event)) {document.getElementById(\"controle_blur\").value = 0; pesquisaPeca (document.frm_pedido.produto_referencia_"+num_qtde+",document.frm_pedido.peca_referencia_"+num_qtde+",\"peca\","+num_qtde+", \"t\",document.frm_pedido.tipo_pedido)}'>\
                <img src='imagens/lupa.png' style='cursor: pointer;' alt='Clique para pesquisar por referência do componente' border='0' hspace='5' align='absmiddle' onclick='javascript: document.getElementById(\"controle_blur\").value = 1; pesquisaPeca (document.frm_pedido.produto_referencia_"+num_qtde+",document.frm_pedido.peca_referencia_"+num_qtde+",\"peca\", "+num_qtde+", \"t\",document.frm_pedido.tipo_pedido)'>\
            </td>\
            <td><img src='imagens/btn_lista.gif' hspace='12' align='center' style='cursor: pointer;' onclick='javascript: document.getElementById(\"controle_blur\").value = 1; pesquisaPeca (document.frm_pedido.produto_referencia_"+num_qtde+",document.frm_pedido.peca_referencia_"+num_qtde+",\"lista_basica\","+num_qtde+", \"t\",document.frm_pedido.tipo_pedido)'></td>\
            <td align='left' nowrap=''>\
                <input type='hidden' name='posicao'>\
                <input class='frm' style='width:135px' type='text' name='peca_descricao_"+num_qtde+"' id='peca_descricao_"+num_qtde+"' size='20' value='' onkeydown='if (verificaTab(event)) {document.getElementById(\"controle_blur\").value = 0; document.getElementById(\"controle_blur\").value = 1; pesquisaPeca (document.frm_pedido.produto_referencia_"+num_qtde+",document.frm_pedido.peca_descricao_"+num_qtde+",\"descricao\","+num_qtde+", \"t\",document.getElementById(\"tipo_pedido\").value)}'>\
                <img src='imagens/lupa.png' style='cursor: pointer;' alt='Clique para pesquisar por descrição do componente' border='0' hspace='5' align='absmiddle' onclick='javascript: document.getElementById(\"controle_blur\").value = 1; pesquisaPeca (document.frm_pedido.produto_referencia_"+num_qtde+",document.frm_pedido.peca_descricao_"+num_qtde+",\"descricao\","+num_qtde+", \"t\",document.frm_pedido.tipo_pedido)'>\
            </td>\
        ";
<?php
if (isFabrica(11,104,172)) {
?>
        html += "<td align='center'>\
                <input class='frm' type='text' style='width:45px;text-align:center;' name='qtde_demanda_"+num_qtde+"' id='qtde_demanda_"+num_qtde+"' size='5' maxlength='5' value='' rel='"+num_qtde+"' />\
            </td>\
        ";
<?php
}
?>
            html += "<td align='center'>\
                <input class='frm' type='text' style='width:45px;text-align:center;' name='qtde_"+num_qtde+"' id='qtde_"+num_qtde+"' size='5' maxlength='5' value='' rel='"+num_qtde+"' tabindex='159' onblur='javascript: fnc_calcula_total("+num_qtde+"); atualiza_proxima_linha("+num_qtde+")'>\
            </td>\
            <td align='center'>\
                <input style='width:57px;text-align:right;' class='frm' id='preco_"+num_qtde+"' type='text' name='preco_"+num_qtde+"' size='10' value='' readonly=''>\
            </td>\
            <td align='center'>\
                <input style='width:57px;text-align:right;' class='frm' name='sub_total_"+num_qtde+"' id='sub_total_"+num_qtde+"' type='text' size='10' rel='total_pecas' readonly='' value=''>\
            </td>\
         </tr>\
    ";

    $("table#itens_pedido > tbody").append(html);

}

$(function(){

    $(document).on("change", ".combo_item_qtde", function() {
        $("form[name=frm_pedido]").submit();
    });

    var tipo_pedido = "";

    $(window).load(function(){
        tipo_pedido = $("select[name=tipo_pedido] option:selected").val();

        verirficaDesconto(tipo_pedido);

    });

    <?php if (isFabrica(11,172)): ?>
    if ($("select[name=tipo_pedido] > option:selected").html() == "Insumo") {
        var condicao_insumo = $("#condicao_insumo").val();

        var html = '<option value="' + condicao_insumo + '">Insumo</option>';

        $("select[name=condicao]").html(html);
    } else if ($("select[name=condicao] > option:selected").html() == "Insumo") {
        var condicoes_pgto = $("#condicoes_pgto").html();
        $("select[name=condicao]").html(condicoes_pgto);
    }
    <?php endif ?>

    $("select[name=tipo_pedido]").change(function(){
        <?php if (isFabrica(11,172)): ?>
        if ($("select[name=tipo_pedido] > option:selected").html() == "Insumo") {
            var condicao_insumo = $("#condicao_insumo").val();

            var html = '<option value="' + condicao_insumo + '">Insumo</option>';

            $("select[name=condicao]").html(html);
        } else if ($("select[name=condicao] > option:selected").html() == "Insumo") {
            var condicoes_pgto = $("#condicoes_pgto").html();
            $("select[name=condicao]").html(condicoes_pgto);
        }
        <?php endif ?>
        verirficaDesconto($(this).val());
        verificaConteudo($(this).val());
    });
    $("input[name=tipo_pecas]").change(function(){
        verificaConteudo($(this).val());
        var aux_tipo_pecas = $(this).val();
        $("#aux_tipo_pecas").val(aux_tipo_pecas);
    });

});

function verificaConteudo(tp){
    var radio = $("input[name=tipo_pecas]");

    if (tp != "") {
        $.ajax({
            url : "<?php echo $_SERVER['PHP_SELF']; ?>",
            type: "POST",
            data: {
                verifica_tipo_pecas: "ok",
                tipo_pecas : tp
            },
            complete: function(data){
                data = data.responseText;

                if (data == "sim") {
                    //alert('Acessório');
                }else{
                    //alert(data);
                    if (tp == 'f') {
                        $(radio[0])[0].checked = false;
                        $(radio[1])[0].checked = true;
                    }else{
                        $(radio[0])[0].checked = true;
                        $(radio[1])[0].checked = false;
                    }
                }

            }
        });
    }
}

function verirficaDesconto(tp){

    if (tp != "") {

        $.ajax({
            url : "<?php echo $_SERVER['PHP_SELF']; ?>",
            type: "POST",
            data: {
                verifica_tipo_pedido: "ok",
                tipo_pedido : tp
            },
            complete: function(data){
                data = data.responseText;

                if (data == "sim") {
                    $("#box_deconto").html("Desconto: <strong><?php echo $desconto_posto; ?>%</strong>");
                    $("#valor_desconto").val("<?php echo $desconto_posto; ?>");
                }else{
                    $("#box_deconto").html("");
                    $("#valor_desconto").val("");
                }

            }
        });

    }

}

</script>

<!-- Mensagem de Erro--> <?php

if (strlen ($msg_erro) > 0) {
    echo "<script> $(window).scrollTop(); </script>";
    if (strpos ($msg_erro,"Cannot insert a duplicate key into unique index tbl_os_sua_os") > 0) {
        $msg_erro = "Esta ordem de serviço já foi cadastrada";
    }

    if (strpos($msg_erro,"ERROR: ") !== false) {//Retira palavra ERROR:
        $erro = "Foi detectado o seguinte erro:<br>";
        $msg_erro = substr($msg_erro, 6);
    }

    if (strpos($msg_erro,"CONTEXT:")) {//Retira CONTEXT:
        $x = explode('CONTEXT:',$msg_erro);
        $msg_erro = $x[0];
    }?>

    <div id="layout" style="width:700px;">
        <div class="msg_erro"><?=$msg_erro;?></div>
    </div><?php

}

if (strlen ($msg) > 0) {?>
    <div id="layout">
        <div class="sucesso"> <? echo $msg; ?> </div>
    </div><?php
}

$sql = "SELECT  tbl_condicao.*
        FROM    tbl_condicao
        JOIN    tbl_posto_condicao USING (condicao)
        WHERE   tbl_posto_condicao.posto = $login_posto
        AND     tbl_condicao.fabrica     = $login_fabrica
        AND     tbl_condicao.visivel IS TRUE
        AND     tbl_condicao.descricao ilike '%garantia%'
        ORDER BY lpad(trim(tbl_condicao.codigo_condicao)::text, 10,'0') ";

$res = pg_query($con, $sql);

if (pg_num_rows($res) > 0) {
    $frase = "PREENCHA SEU PEDIDO DE COMPRA/GARANTIA";
} else {
    $frase = traduz("PREENCHA SEU PEDIDO DE COMPRA");
}?>

<br>

<?php
    if ($login_fabrica == 104) {
        $style = "style = 'width: auto !important;'";
    } else {
        $style = "";
    }
?>

<div id="layout">

    <?php if(in_array($login_fabrica, array(11,172))){ ?>
    <div style="text-align: right;">
        Logar em: 
        <select class="frm" style="width: 120px;" onchange="alterar_fabrica(this.value);">
            <option value="11" <?php echo ($login_fabrica == 11) ? "selected" : ""; ?> >Aulik</option>
            <option value="172" <?php echo ($login_fabrica == 172) ? "selected" : ""; ?> >Pacific</option>
        </select>
    </div>
    <br />
    <?php } ?>

    <div <?=$style;?> class="texto_avulso"><?php
    if (isFabrica(51,94,99,101,106,123,124,125,127,128,129,134,136)) {

        if (isFabrica(51,99,101,106,123,127,128,136)) {
            $valor_minimo = 'R$30,00';
        }

		if (isFabrica(134,129)) {
            $valor_minimo = 'R$200,00';
        }
        
        if (isFabrica(94,124,127) || $telecontrol_distrib)  {
            $valor_minimo = 'R$50,00';
        }

        if (!isFabrica(101)) { // HD 941541
            if (!isFabrica(134)) {
                echo "
                <font size='4' face='Geneva, Arial, Helvetica, san-serif' color='#FF0000'><b>*** ".traduz('Atenção esta tela é somente para pedidos fora de garantia')." ***</b></font>
                <br><br>";

                if (isFabrica(94)) {
                    echo "<font size='4' face='Geneva, Arial, Helvetica, san-serif' color='#FF0000'><b>
                        Os valores são aproximados.<br><br></font>";

                    echo "<div style='padding:0px 50px; text-align: left'>
                        <font size='3' face='Geneva, Arial, Helvetica, san-serif' color='#FF0000'><b>
                        Alguns itens possuem impostos que não serão visualizados no ato desta implantação, somente na emissão da N.F.<br>
                    </b></font>
                    </div>";
                }
            }

            if (!isFabrica(129)) {
            echo "<font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#990000'><b>*** ".traduz('Pedidos deverão ter valor mínimo de')." $valor_minimo ***</b></font>
                <br><br>";
            }

        }

    }

    if (isFabrica(98)) {
        echo "  <p>Pedidos de R$30,00  a R$199,99 - 30 DIAS</p>
                <p>À Partir de R$200,00           - 30/60 DIAS</p>";
    }

    if (isFabrica(40)) {
        echo "<p>MÍNIMO PARA FATURAMENTO R$90,00</p>
                <p>À PARTIR DE R$90,00 - 30 DIAS</p>
                <p>À PARTIR DE R$241,00 - 30/60 DIAS</p>
                <p>À PARTIR DE R$450,00 - 30/60/90 DIAS</p>";
    }

    if (isFabrica(3)) {?>
        <!--<b>Atenção Linha Áudio e Vídeo:</b> Pedidos de peças para linha de áudio e vídeo feitos nesta tela devem ser para uso em consertos fora da garantia, e gerarão fatura e duplicata.<br>Pedidos para conserto em garantia serão gerados automaticamente pela Ordem de Serviço.<br>Leia o Manual e a Circular na primeira página.
        <br><br>
        <font size="2" face="Geneva, Arial, Helvetica, san-serif" color='#990000'><b>*** Pedidos realizados no valor abaixo de R$30,00 não serão faturados ***</b></font>
        <br><br>
        -->
        Não há Valor Mínimo de Pedido de Compra de Peças. <br>
        A restrição será no faturamento e envio de peças pelo depósito da Britânia.<br>
        <b>Valor mínimo de faturamento R$ 30,00.</b> <br>
        Quando houver pedido de peças em garantia será utilizado o mesmo frete.<br>
        Pedidos pendentes de compra superiores a 60 dias serão avaliados e poderão ser excluídos.<br>
    </td><?php
    } else if (isFabrica(15)) { ?>
        <b>AVISO</b> <br>Peças <b>plásticas</b> em garantia, somente para produtos com até <b>90 dias</b> da compra.
        <br>
        <br>
        <b>Condições de Pagamento:</b> <br> Até R$ 200,00 30 dias ; Até R$ 350,00 30-45 dias <br> Até R$ 600,00 , 30-60 dias ; Acima de R$ 600,00 , 30-60-90 dias
        <br>
        <br>
        <b>*** Pedidos abaixo de R$80,00 não serão faturados ***</b>
        <br>
        <br>
        <b>Despesas de frete de peças faturadas serão por conta do Posto Autorizado.</b>
        <br>
            Sudeste/Sul: R$ 28,36<br>
            Centroeste: R$ 30,00<br>
            Norte/Nordeste: R$ 33.80<br>
        <br>
        <b>Despesas de frete de peças em garantia serão por conta da LATINATEC.</b>
        <br>
        <br><?php

    } else if (!isFabrica(74,94,101,104,105,106,122,123,126,127,128,131,134,136,140,141,144,145)) {?>

        <b><?=traduz("Atenção")?>:</b> <?=traduz("Pedidos a prazo dependerão de análise do departamento de crédito.")?><?php
    } else if (isFabrica(74)) { ?>
        <font color="#FF0000"><b>Atenção: Pedidos de peças realizados nesta tela serão faturados, por se tratarem de atendimentos fora de garantia.</b></font>
    <? } ?>

    </div>
</div>

<br />
<!-- OBSERVAÇÕES -->
<div id="layout"><?php

if (!isFabrica(30,74,94,101,106,115,116,117,120,121,122,123,124,126,127,128,129,131,134,136)) {//HD 70768-1 - Retirar mensagem na Esmaltec?>

    <div <?=$style;?> class="texto_avulso"><?php

    if (!isFabrica(24)) {?>

        <?=traduz("Para efetuar um pedido por modelo do produto, informe a referência")." <br>".traduz("ou descrição e clique na lupa, ou simplesmente clique na lupa.")?><?php

    } else {

        echo "O fabricante limita em $limit_pedidos pedidos de garantia por mês.<br />";

        $sql = "SELECT  to_char(current_date,'MM')::INTEGER as mes,
                        to_char(current_date,'YYYY') AS ano";

        $res = pg_query($con,$sql);
        $mes = pg_fetch_result($res, 0, 'mes');
        $ano = pg_fetch_result($res, 0, 'ano');

        if (strlen($mes) > 0) {

            $data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
            $data_final   = date("Y-m-t 23:59:59",  mktime(0, 0, 0, $mes, 1, $ano));

            $sql = "SELECT  count(pedido) as qtde
                    FROM tbl_pedido
                    WHERE fabrica = $login_fabrica
                    AND posto = $login_posto
                    AND data BETWEEN '$data_inicial' AND '$data_final'
                    AND tipo_pedido = 104";

            $res  = pg_query($con,$sql);
            $qtde = pg_fetch_result($res, 0, 'qtde');

            if ($qtde < 2) {
                echo "<b>Seu PA fez <B>$qtde</B> pedido(s) em garantia este mês</b>";
            } else {
                echo "<b>Seu PA fez <B>$qtde</B> pedido(s) em garantia este mês, caso necessite de outro pedido em garantia por favor entre em contato com o fabricante.</b>";
            }

        }

    }

    echo '</div>';

} // fim HD 70768-1

//alterado por Wellington 13-10-2006 chamado 575
if (isFabrica(11,172)) {?>
	<br /> <div class="texto_avulso">
    <span> Somente Pedidos de Venda </span><?php
	echo "Nesta tela devem ser digitados somente pedidos de <B>VENDA</B>. Pedidos de peça na <B>GARANTIA</B> devem ser feitos somente através da abertura da Ordem de Serviço.";
echo "</div>";
} 
if($telecontrol_distrib && !in_array($login_fabrica, [11,172])) {
?>
<br><div class='texto_avulso'><?=traduz("Atenção, ao escolher atendimento parcial dos pedidos, o pedido será faturado de acordo com a disponibilidade das peças em estoque, o que acarretará a cobrança de vários fretes.
Para evitar que isso ocorra, escolha o atendimento total do pedido, onde este só será faturado mediante a disponibilidade de todas as peças que compõem o pedido.")?>
 <br>
<?=traduz("Em caso de dúvidas, entrar em contato pelo 0800-718-7825")?>
</div>

<?php
}

/*if ($telecontrol_distrib) {
?>
<br><div class='texto_avulso'>Atenção, ao escolher atendimento parcial dos pedidos, o pedido será faturado de acordo com a disponibilidade das peças em estoque, o que acarretará a cobrança de vários fretes.
Para evitar que isso ocorra, escolha o atendimento total do pedido, onde este só será faturado mediante a disponibilidade de todas as peças que compõem o pedido.
</div>
<br />

<?php  
}*/

 if (isFabrica(7) AND $total_original > 0 AND $permite_alteracao == 't') {?>
    <br><br><b>Atenção:</b> o pedido deve ser superior a R$ <?php echo number_format($total_original,2,",",".");
}
if (isFabrica(30)){ //HD 707682-3 - Aviso valor mínimo ?>
    <DIV class='content'>
        <H1 style='font-size: 1em;color:#B00'>
            <b>Atenção:</b> O pedido só será incluído se tiver no mínimo <B>R$ 60,00</B>
        </H1>
    </DIV><?php
}?>

</div><?php

if ($distrib_posto_pedido_parcial) {// HD 221731

    $sql2    = "SELECT tbl_posto_extra.atende_pedido_faturado_parcial
                  FROM tbl_posto_extra
                  JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = $login_posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                 WHERE tbl_posto_extra.posto = $login_posto";

    $rs2     = pg_exec($con,$sql2);
    $parcial = trim(pg_result($rs2,0,'atende_pedido_faturado_parcial'));

    if ($parcial == 'f') {//HD 221731?>
        <div id="layout">
            <div class="subtitulo">
                <p>
                    <b><?=traduz("Dica")?>:</b> <?=traduz("Caso você tenha algumas peças que não podem ser atendidas parcialmente,")?>
                    <br />
                    <?=traduz("favor fazer um pedido separado somente com estas peças e selecione a opção") .'<u>'.traduz(" atendimento parcial!").'</u>'?>
                </p>
            </div>
        </div><?php
    }

    if ($parcial == 'f') {//HD 221731?>
        <div id="layout">
            <div class="content">
                <p>
                    <b>Nota: </b> <?=traduz("Colocando que não pode ser atendido parcial, somente será faturado o pedido")?>
                    <br />
                    <?=traduz("se todas as peças estiverem em nossos estoques, caso contrário,")?>
                    <br />
                    <?=traduz("em 60 dias será cancelado automaticamente este pedido!")?>
                </p>
            </div>
        </div><?php
    }

}

if ($_GET['pa'] && $_GET['pn'] && $_GET['ok'])
{
    $pedido_aut = $_GET['pa'];
    $pedido_normal = $_GET['pn'];
    ?>
<br>
    <table align="center" width="700px" border="0" cellpadding="1" cellspacing="1" class="sucesso">
        <tr>
            <td align="center">
                <?

                echo "Foram gravados os pedidos:<br />Pedido normal: ";
                echo "<a target='_blank' href='pedido_finalizado.php?pedido=$pedido_normal&loc=1'>$pedido_normal</a>";
                echo "<br>Pedido para produtos da linha automática: ";
                echo "<a target='_blank' href='pedido_finalizado.php?pedido=$pedido_aut&loc=1'>$pedido_aut</a>";

                ?>
            </td>
        </tr>
    </table>
<?
}
?>
<form name="frm_pedido" method="post" action="<? echo $PHP_SELF ?>">
<input class="frm" type="hidden" name="pedido" value="<? if (!empty($pedido))  echo $pedido;else echo $cook_pedido; ?>">
<input class="frm" type="hidden" name="voltagem" id="voltagem" value="<? echo $voltagem; ?>">
<input class="frm" type="hidden" name="qtde_item_combo_hidden" value="<? echo $_POST['qtde_item_combo']; ?>">
<?php
/**
 * HD 254266
 * Campo criado para controlar quando é executado a ação da função fnc_pesquisa_peca_lista
 * Se é no onblur ou onclick, não pude alterar muito a função pois existiam outros arquivos usando
 * Senao teria passado como parametro dentro da função.
 */
?>
<input type="hidden" name="controle_blur" id="controle_blur" value="1" />

<center>
<p>
<? if ($distribuidor_digita == 't' AND $ip == '201.0.9.216') { ?>
    <table width="99%" border="0" cellspacing="5" cellpadding="0">
        <tr valign='top' style='font-size:12px'>
            <td nowrap align='center'>
            Distribuidor pode digitar pedidos para seus postos.
            <br />
            Digite o código do posto
            <input type='text' name='codigo_posto' size='5' maxlength='10' value='<? echo $codigo_posto ?>'>
            ou deixe em branco para seus próprios pedidos.
            </td>
        </tr>
    </table>
<? } ?>
</center>

<br />

<?php
    if ($login_fabrica == 104) {
        $style = "style = 'width: 105%; float: right;'";
    } else {
        $style = "";
    }
?>
<!-- INICIA DIVISÃO -->
<ul id="split" >
<li id="one">
    <h3 <?=$style;?> ><? echo $frase; ?></h3>
<div <?=$style;?> >

    <? if (!isFabrica(24, 30)) { //HD 70768-2 Retirar campo 'pedido do cliente' ?>
        <p><span class='coluna1'><?=traduz("Pedido do Cliente")?></span>
            <input class="frm" type="text" name="pedido_cliente" size="15" maxlength="20" value="<? echo $pedido_cliente ?>">
        </p>
    <?}?>

    <?
    $res = pg_query ($con, "SELECT pedido_escolhe_condicao FROM tbl_fabrica WHERE fabrica = $login_fabrica");
    #permite_alteracao - HD 47695
    if (pg_fetch_result ($res,0,'pedido_escolhe_condicao') == 'f' OR $permite_alteracao == 't') {
        echo "<input type='hidden' name='condicao' value=''>";
    }else{
    ?>

    <p><span class='coluna1'><?=traduz("Condição Pagamento")?></span>
        <select size='1' name='condicao' class='frm' data-condicao="<?=$condicao?>">
        <option value=""><?=traduz("Selecione")?></option>
        <?php
        if (isFabrica(11,172)) {
            $condicoes_pgto = '<option value="">Selecione</option>';
        }
            //hd 17625
            if (ifFabrica(24, 81, '86..')) {
                $sql = "SELECT pedido_em_garantia, pedido_faturado
                        FROM tbl_posto_fabrica
                        WHERE fabrica = $login_fabrica
                        AND   posto   = $login_posto;";
                $res = pg_query($con,$sql);

                $pede_em_garantia = pg_fetch_result($res,0,pedido_em_garantia);
                $pede_faturado    = pg_fetch_result($res,0,pedido_faturado);
            }
            if ($login_posto == 4311) {
                $sql = "SELECT   tbl_condicao.*
                        FROM     tbl_condicao
                        JOIN     tbl_posto_condicao USING (condicao)
                        WHERE    tbl_posto_condicao.posto = $login_posto
                        AND      tbl_condicao.fabrica     = $login_fabrica";
            } else {
                $sql = "select * from tbl_posto_condicao
                        join tbl_condicao using(condicao)
                        WHERE posto = $posto
                        AND   tbl_condicao.visivel
                        and fabrica = $login_fabrica";
            }

            //hd 17625
            if (isFabrica(24) and $pede_em_garantia == 't' and $pede_faturado == 'f') {
                $sql .= " AND tbl_condicao.condicao = 928 ";
            }
            if (isFabrica(81,114) and $pede_em_garantia == 't' and $pede_faturado == 'f') {
                $sql .= " AND tbl_condicao.condicao = 1397 ";
            }

            $sql .= "ORDER BY lpad(trim(tbl_condicao.codigo_condicao)::text, 10,'0') ";
            $xxx  = $sql;
            $res = pg_query ($con,$sql);

			if (pg_num_rows ($res) == 0 or isFabrica(2)) {
				if (isFabrica(24) && qual_tipo_posto($login_posto) == 696) {
                    $cond_condicao = " AND tbl_condicao.condicao = 3769 ";
				}else{
                    $cond_condicao = " AND tbl_condicao.visivel ";
				}

                $sql = "SELECT   tbl_condicao.*
                        FROM     tbl_condicao
						WHERE    tbl_condicao.fabrica = $login_fabrica
						$cond_condicao ";
                //hd 17625
                if (isFabrica(24) and $pede_em_garantia == 't' and $pede_faturado == 'f') {
                    $sql .= " AND tbl_condicao.condicao = 928 ";
                }


                if (isFabrica(81,114) and $pede_em_garantia == 't' and $pede_faturado == 'f') {
                    $sql .= " AND tbl_condicao.condicao = 1397 ";
                }

                $sql .= "ORDER BY lpad(trim(tbl_condicao.codigo_condicao)::text, 10,'0') ";
                $res = pg_query ($con,$sql);
            }

            for ($i = 0; $i < pg_num_rows($res); $i++) {
                #HD 107982
                if (isFabrica(24)) {
                    if (pg_fetch_result($res,$i,condicao) <> 928) {
                        echo "<option value='" . pg_fetch_result ($res,$i,condicao) . "'";
                        if (pg_fetch_result($res,$i,condicao) == $condicao) echo " selected";
                        echo ">" . pg_fetch_result ($res,$i,descricao) . "</option>";
                    }
                } else {

                    if($login_fabrica == 104){
                        break;
                    }

                    echo "<option value='" . pg_fetch_result ($res,$i,condicao) . "'";
                    if (pg_fetch_result ($res,$i,condicao) == $condicao) echo " selected";
                    echo ">" . pg_fetch_result ($res,$i,descricao) . "</option>";

                    if (isFabrica(11,172)) {
                        $condicoes_pgto .= '<option value="';
                        $condicoes_pgto .= pg_fetch_result($res, $i, 'condicao');
                        $condicoes_pgto .= '">';
                        $condicoes_pgto .= pg_fetch_result($res, $i, 'descricao');
                        $condicoes_pgto .= '</option>';
                    }
                }
            }?>
        </select>
    </p><?php

    if (isFabrica(11,172)) {
        echo '<div id="condicoes_pgto" style="display: none">' . $condicoes_pgto . '</div>';
    }

}

        //VERIFICA SE POSTO PODE PEDIR PECA EM GARANTIA ANTECIPADA
        $sql = "SELECT garantia_antecipada FROM tbl_posto_fabrica WHERE fabrica=$login_fabrica AND posto=$login_posto";
        $res = pg_query ($con,$sql);
        if (pg_num_rows($res) > 0) {
            $garantia_antecipada = pg_fetch_result($res,0,0);
            if ($garantia_antecipada <> "t")  {
                $garantia_antecipada ="f";
            }
        }?>

        <?php if (isFabrica(24) && qual_tipo_posto($login_posto) == 696) { 
                $coluna1 = "style='width:150px !important; margin-left: 106px !important;'";
        } ?>


        <p><span class='coluna1' <?=$coluna1?>><?=traduz("Tipo de Pedido")?></span><?php
        //se posto pode escolher tipo_pedido

        if (!isFabrica(172) and ifFabrica('99...')) {

            $sql = "SELECT tipo_pedido,
                            descricao
                    FROM tbl_tipo_pedido
                    JOIN tbl_posto_fabrica ON tbl_posto_fabrica.fabrica = tbl_tipo_pedido.fabrica
                    WHERE ((tbl_posto_fabrica.pedido_em_garantia IS TRUE AND tbl_tipo_pedido.pedido_em_garantia IS TRUE)
                    OR (tbl_posto_fabrica.pedido_faturado IS TRUE AND tbl_tipo_pedido.pedido_faturado IS TRUE))
                    AND tbl_posto_fabrica.posto = $login_posto
                    AND tbl_posto_fabrica.fabrica = $login_fabrica";

            if (isFabrica(104,141,144)) {
                if (!empty($cook_pedido)) {
                    $auxpedido = $cook_pedido;
                    $sql = "select tipo_pedido,descricao from tbl_pedido join tbl_tipo_pedido using(tipo_pedido) where pedido = $auxpedido";
                    $res = pg_query($sql);
                    $tipo_pedido = pg_result($res,0,tipo_pedido);
                    $descricao   = pg_result($res,0,descricao);
                    $readonly = "readonly";
                }
            }

            $res = pg_query ($con,$sql);

            if (pg_num_rows($res) > 0) {

                echo "<select size='1' name='tipo_pedido' class='frm'>";

                for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {

                    $tipo_pedido_aux = pg_fetch_result($res,$i,tipo_pedido);
                    $desc_pedido = pg_fetch_result($res,$i,descricao);

                    echo "<option value='" . $tipo_pedido_aux . "'";
                    if ($tipo_pedido_aux == $tipo_pedido){
                        echo " selected";
                    }
                    echo ">" . $desc_pedido . "</option>";
                }

                echo "</select>";

            }

        } else {

            $sql = "SELECT   *
                    FROM     tbl_posto_fabrica
                    WHERE    tbl_posto_fabrica.posto   = $login_posto
                    AND      tbl_posto_fabrica.fabrica = $login_fabrica";

            if (!isFabrica(24)) {
                $sql .= " AND      tbl_posto_fabrica.pedido_em_garantia IS TRUE;";
            }

            $res = pg_query ($con, $sql);

            if (pg_num_rows($res) > 0) {

                echo "<select size='1' name='tipo_pedido' class='frm'>";
                $sql = "SELECT   *
                        FROM     tbl_tipo_pedido
                        WHERE    fabrica = $login_fabrica ";

                if (isFabrica(24)) {
                    $sql .= " AND tipo_pedido not in(107,104)";

                    #HD 17625
                    if ($pede_faturado == 'f') {
                        $sql .= " AND tipo_pedido <> 103 ";
                    }
                }

                $sql .= " ORDER BY tipo_pedido ";
                $res = pg_query ($con,$sql);
                $xxx = $sql;

                # AND      (garantia_antecipada is false or garantia_antecipada is null)
                # takashi -  coloquei -> AND      (garantia_antecipada is false or garantia_antecipada is null)
                # efetuei testes em suggar, lenoxx, latina caso ocorra erro retirar linha a cima

                for ($i = 0; $i < pg_num_rows($res) ; $i++) {

                    if (isFabrica(24)) { #HD 107982

                        if (pg_fetch_result ($res,$i,tipo_pedido) <> 104) {
                            // Deve por ID do tipo_pedido de Produção  --------------------------------------------------------
                            if ((qual_tipo_posto($login_posto) == 696 && pg_fetch_result($res, $i, 'tipo_pedido') <> 426) || (qual_tipo_posto($login_posto) != 696 && pg_fetch_result($res, $i, 'tipo_pedido') == 426)) {
                                continue;
                            }  

                            echo "<option value='" . pg_fetch_result($res,$i,tipo_pedido) . "'";
                            if (pg_fetch_result ($res,$i,tipo_pedido) == $tipo_pedido){
                                echo " selected";
                            }
                            echo ">" . pg_fetch_result($res,$i,descricao) . "</option>";
                        }

                    } else {

                        echo "<option value='" . pg_fetch_result($res,$i,tipo_pedido) . "'";
                        if (pg_fetch_result ($res,$i,tipo_pedido) == $tipo_pedido){
                            echo " selected";
                        }
                        echo ">" . pg_fetch_result($res,$i,descricao) . "</option>";

                    }

                }

                if ($garantia_antecipada == "t") {

                    //takashi - efetuei testes em suggar, lenoxx, latina caso ocorra erro retirar esse if
                    $sql = "SELECT   *
                            FROM     tbl_tipo_pedido
                            WHERE    fabrica = $login_fabrica
                            AND garantia_antecipada is true ";
                    if (isFabrica(24)) {
                        $sql .= " and tipo_pedido <> 107";
                    }

                    $sql .= " ORDER BY tipo_pedido ";
                    $xxl =  $sql;
                    $res = pg_query ($con,$sql);

                    for ($i = 0; $i < pg_num_rows($res); $i++) {

                        if (isFabrica(24)) { #HD 107982

                            if (pg_fetch_result($res,$i,tipo_pedido) <> 104) {
                                echo "<option value='" . pg_fetch_result($res, $i, 'tipo_pedido') . "'";
                                if (pg_fetch_result($res, $i, 'tipo_pedido') == $tipo_pedido){
                                    echo " selected";
                                }
                                echo ">" . pg_fetch_result($res, $i, 'descricao') . "</option>";
                            }

                        } else {

                            echo "<option value='" . pg_fetch_result($res, $i, 'tipo_pedido') . "'";
                            if (pg_fetch_result($res, $i, 'tipo_pedido') == $tipo_pedido) {
                                echo " selected";
                            }
                            echo ">" . pg_fetch_result($res,$i,descricao) . "</option>";

                        }

                    }

                }
                echo "</select>";

            } else {

                echo "<select size='1' name='tipo_pedido' class='frm' ";
                if (isFabrica(3)) {
                    echo "disabled";
                }
                echo ">";

                $cond_insumo = '';
                $select_insumo = '';

                if (isFabrica(11,172)) {
                    $sql = "SELECT atendimento FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
                    $qry = pg_query($con, $sql);

                    $posto_atendimento = pg_fetch_result($qry, 0, 'atendimento');

                    if (pg_fetch_result($qry, 0, 'atendimento') == 't') {
                        $cond_insumo = " OR tbl_tipo_pedido.descricao = 'Insumo' ";
						$select_insumo = array2select(
							'insumos', 'insumos',
							array(
								'pecas' => 'Peças',
								'embalagens' => 'Embalagens/Calços'
							),
							$insumos, ' class="frm"', ' ', true
						);
                    }
                }

                $sql = "SELECT   *
                        FROM    tbl_tipo_pedido
                        WHERE   (tbl_tipo_pedido.descricao ILIKE '%Faturado%'
                           OR   tbl_tipo_pedido.descricao ILIKE '%Venda%' $cond_insumo)
                        AND     tbl_tipo_pedido.fabrica = $login_fabrica
                        AND     (garantia_antecipada is false or garantia_antecipada is null)
                        ORDER BY tipo_pedido";

                #HD 47695
                if (strlen($tipo_pedido)> 0 AND $permite_alteracao == 't') {

                    $sql = "SELECT   *
                            FROM     tbl_tipo_pedido
                            WHERE    fabrica = $login_fabrica ";

                    if (strlen($tipo_pedido) > 0) {
                        $sql .= " AND      tbl_tipo_pedido.tipo_pedido = $tipo_pedido ";
                    }

                    $sql .= " ORDER BY tipo_pedido;";

                }

                $res = pg_query($con, $sql);

                # takashi -  coloquei : AND      (garantia_antecipada is false or garantia_antecipada is null)
                # efetuei testes em suggar, lenoxx, latina caso ocorra erro retirar linha a cima

                if (pg_num_rows($res) > 1) {
                    echo '<option value="">Selecione</option>';
                }

                for ($i = 0; $i < pg_num_rows($res); $i++) {

                    echo "<option value='" . pg_fetch_result($res, $i, 'tipo_pedido') . "'";
                    if (pg_fetch_result ($res, $i, 'tipo_pedido') == $tipo_pedido) echo " selected";
                    echo ">" . pg_fetch_result($res, $i, 'descricao') . "</option>";

                }

                if ($garantia_antecipada == "t") {

                    #takashi - efetuei testes em suggar, lenoxx, latina caso ocorra erro retirar esse if
                    $sql = "SELECT   *
                            FROM     tbl_tipo_pedido
                            WHERE    fabrica = $login_fabrica
                            AND garantia_antecipada is true
                            ORDER BY tipo_pedido ";
                    $res = pg_query ($con,$sql);

                    for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {

                        echo "<option value='" . pg_fetch_result($res,$i,tipo_pedido) . "'";
                        if (pg_fetch_result ($res,$i,tipo_pedido) == $tipo_pedido){
                            echo " selected";
                        }
                        echo ">" . pg_fetch_result($res,$i,descricao) . "</option>";

                    }

                }

                echo "</select>";

                if (isFabrica(11,172)) {
                    $qry_insumo = pg_query(
                        $con,
                        "SELECT condicao FROM tbl_condicao
                        WHERE fabrica = $login_fabrica
                        AND descricao = 'Insumo'"
                    );

                    $condicao_insumo = pg_fetch_result($qry_insumo, 0, 'condicao');

                    echo '<input type="hidden" id="condicao_insumo" name="condicao_insumo" value="' . $condicao_insumo . '" />';
                }
            }

        }?>
        </p>

        <?php if (!empty($select_insumo)): ?>
        <p>
            <span class='coluna1'>Insumos</span>
            <?php echo $select_insumo ?>
        </p>
        <?php endif ?>

        <?php if (isFabrica(120)) {//hd_chamado=2765193 ?>
            <p>
                <span class='coluna1'>Linha</span>
                <input name='campo_linha_produto' id='campo_linha_produto' type="hidden" value="<?=$linha_produto?>"></input>
                <select name="linha_produto" class='frm' id="linha_produto" onchange="linhaProduto();" >
                    <option value="">Selecione</option>
                    <?php
                    $sql = "SELECT linha, nome
                            FROM tbl_linha
                            WHERE fabrica = $login_fabrica
                            AND ativo";
                    $res = pg_query($con,$sql);

                    foreach (pg_fetch_all($res) as $key) {
                        $selected_linha = ( isset($linha_produto) and ($linha_produto == $key['linha']) ) ? "SELECTED" : '' ;

                    ?>
                        <option value="<?php echo $key['linha']?>" <?php echo $selected_linha ?> >

                            <?php echo $key['nome']?>

                        </option>
                    <?php
                    }
                    ?>
                </select>
            </p>
        <?php } ?>
        <?
        if (isFabrica(104)) {
            if (!empty($cook_pedido)) {
                $sql_p = "SELECT pedido_acessorio FROM tbl_pedido WHERE fabrica = $login_fabrica AND pedido = $cook_pedido;";
                $res_p = pg_query($con,$sql_p);

                $tipo_acessorio = pg_fetch_result($res_p, 0, pedido_acessorio);
            }
            ?>
              <p><span class='coluna1'>Tipo de Peças</span>
                    <input name="aux_tipo_pecas" id="aux_tipo_pecas" value='f' type="hidden">
                    <label>
                        <input name ="tipo_pecas" type="radio" value="f" <? echo  ($tipo_acessorio !='t' or empty($tipo_acessorio)) ? "checked='checked'": ""  ; ?>>
                        Peça
                    </label>
                    <label>
                        <input name ="tipo_pecas" type="radio" value="t" <? echo ($tipo_acessorio=='t') ? "checked='checked'":""  ;?>>
                        Acessorios
                    </label>
            </p>
            <?
        }
        ?>

        <p>
            <span class='coluna1'><?=traduz("Quantidade de Itens")?></span>
            <select name="qtde_item_combo" class="combo_item_qtde">
                <option value=""><?=traduz("Selecione")?></option>
                <?php 
                    // Não resgatar o POST deste campo, validando o Selected aqui.
                    for ($i=1; $i <= 500; $i++) { 
                        if ($i % 50 == 0) {
                            echo "<option value='$i'>".$i."</option>";
                        }
                    } 
                ?>
            </select>
            <?php 

            ?>
        </p>
        <? if(isFabrica(24) && qual_tipo_posto($login_posto) == 696) : ?>

            <p style="padding:0">
                <span class='coluna1' style="width: 150px !important;margin-left: 155px !important;"><?=traduz("Qtde. Parcelas")?></span>
                <select name="qtde_parcelas" class="frm" data-parcelas="<?=$qtde_parcelas?>">
                    <option value=""><?=traduz("Selecione")?></option>   
                </select>
            </p>

        <? endif; ?>

<?

        if (isFabrica(24) && qual_tipo_posto($login_posto) == 696) {
?>
            <p>
                <!--<span class='coluna1' style="width: 107px; margin-left: 189px;">Registro</span>-->
                <span class='coluna1'>Registro</span>
                <input type="text" name="registro_funcionario" size="10" maxlength="30" value="<?=$registro_funcionario?>">
            </p>
            <p>
                <span class='coluna1'>Departamento</span>
                <input type="text" name="departamento_funcionario" size="20" maxlength="60" value="<?=$departamento_funcionario?>">
            </p>
            <p>
                <span class='coluna1'>Nome Funcionário</span>
                <input type="text" name="nome_funcionario" size="30" maxlength="80" value="<?=$nome_funcionario?>">
            </p>
<?php   
        }


        if (strlen($pedido) == 0)
        {
            $sql = "SELECT transportadora FROM tbl_posto_fabrica WHERE posto = $posto AND fabrica = $login_fabrica";
            $res = pg_query($con, $sql);
            if (pg_num_rows($res) > 0)
            {
                $transportadora = pg_result($res, 0, "transportadora");

                if (strlen($transportadora) > 0)
                {
                    $sql = "SELECT
                                t.nome, t.cnpj, tf.codigo_interno
                            FROM
                                tbl_transportadora t
                            JOIN
                                tbl_transportadora_fabrica tf
                                ON
                                    tf.transportadora = t.transportadora
                            WHERE
                                tf.fabrica = $login_fabrica
                                AND
                                    t.transportadora = $transportadora";
                    $res = pg_query($con, $sql);

                    $transportadora_cnpj   = pg_result($res, 0, "cnpj");
                    $transportadora_codigo = pg_result($res, 0, "codigo_interno");
                    $transportadora_nome   = pg_result($res, 0, "nome");
                }
            }

            if (isFabrica(94)) {
                if (strlen($transportadora) == 0) {
                    $transportadora = $_POST['transportadora'];
                }
            }

        }

        #-------------------- Transportadora -------------------

        #HD 47695 - Para pedidos a serem alterados, nao mostrar a transportadora.
        if ($permite_alteracao != 't' && !isFabrica(88,120)) {

            $sql = "SELECT  tbl_transportadora.transportadora        ,
                            tbl_transportadora.cnpj                  ,
                            tbl_transportadora.nome                  ,
                            tbl_transportadora_fabrica.codigo_interno
                    FROM    tbl_transportadora
                    JOIN    tbl_transportadora_fabrica USING(transportadora)
                    JOIN    tbl_fabrica USING(fabrica)
                    WHERE   tbl_transportadora_fabrica.fabrica        = $login_fabrica
                    AND     tbl_transportadora_fabrica.ativo          = 't'
                    AND     tbl_fabrica.pedido_escolhe_transportadora = 't'";

            $res = pg_query($con,$sql);

            if (pg_num_rows($res) > 0) {?>

                <p><span class='coluna1'>Transportadora</span><?php

                    if (pg_num_rows ($res) <= 20) {
                        echo "<select name='transportadora' class='frm'>";
                        echo "<option selected></option>";
                        for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
                            echo "<option value='".pg_fetch_result($res,$i,transportadora)."' ";
                            if ($transportadora == pg_fetch_result($res,$i,transportadora) ) echo " selected ";
                            echo ">";
                            echo pg_fetch_result($res,$i,codigo_interno) ." - ".pg_fetch_result($res,$i,nome);
                            echo "</option>\n";
                        }
                        echo "</select>";
                    }else{
                        echo "<input type='hidden' name='transportadora' value='$transportadora'>";
                        echo "<input type='hidden' name='transportadora_cnpj' value='$transportadora_cnpj'>";
                        echo "<input type='hidden' name='transportadora_frete' value='$transportadora_frete'>";

                        #echo "<input type='text' name='transportadora_cnpj' size='20' maxlength='18' value='$transportadora_cnpj' class='textbox' >&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_cnpj,'cnpj')\" style='cursor:pointer;'>";

                        echo "<input type='text' name='transportadora_codigo' size='5' maxlength='10' value='$transportadora_codigo' class='textbox' onblur='javascript: lupa_transportadora_codigo.click()'>&nbsp;<img id='lupa_transportadora_codigo' src='imagens/lupa.png' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_codigo,'codigo')\" style='cursor:pointer;'>";

                        //echo "<input type='text' name='transportadora_nome' size='35' maxlength='50' value='$transportadora_nome' class='textbox' onblur='javascript: lupa_transportadora_nome.click()'>&nbsp;<img id='lupa_transportadora_nome' src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_nome,'nome')\" style='cursor:pointer;'>";
                        echo "<input type='text' name='transportadora_nome' size='35' maxlength='50' value='$transportadora_nome' class='textbox' >&nbsp;<img id='lupa_transportadora_nome' src='imagens/lupa.png' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_nome,'nome')\" style='cursor:pointer;'>";
                    }?>
                </p>
        <? }
        }else{
            echo "<input type='hidden' name='transportadora' id='transportadora' value='$transportadora'>";
        }

        ?>

        <?#-------------------- Linha do pedido -------------------

        #HD 47695 - Para alterar o pedido, mas nao a linha, por causa da tabela de preço
        if ($permite_alteracao == 't' and strlen($linha)>0){
            ?><input type="hidden" name="linha" value="<? echo $linha; ?>"><?
        }else{
            $sql = "SELECT  tbl_linha.linha            ,
                            tbl_linha.nome
                    FROM    tbl_linha
                    JOIN    tbl_fabrica USING(fabrica)
                    JOIN    tbl_posto_linha  ON tbl_posto_linha.posto = $login_posto
                                            AND tbl_posto_linha.linha = tbl_linha.linha
                    WHERE   tbl_fabrica.linha_pedido is true
                    AND     tbl_linha.fabrica = $login_fabrica ";

            // BLOQUEIO DE PEDIDOS PARA A LINHA ELETRO E BRANCA EM
            // CASO DE INADIMPLÊNCIA
            // Não bloqueia pedidos do JANGADA - CARMEM LUCIA
            if (isFabrica(3) and $login_bloqueio_pedido == 't' and $login_posto <> 1053) {
                $sql .= "AND tbl_linha.linha NOT IN (2,4)";
            }
            if (isFabrica(51)) {
                $sql .= " AND tbl_linha.ativo IS TRUE ";
            }
            #permite_alteracao - HD 47695
            if (strlen($tipo_pedido)> 0 AND $permite_alteracao == 't' and strlen($linha)>0){
                $sql .= " AND tbl_linha.linha = $linha ";
            }
            $res = pg_query ($con,$sql);
            if (pg_num_rows ($res) > 0) {?>
                <p><span class='coluna1'>Linha</span><?php
                        echo "<select name='linha' class='frm' ";
                        if (isFabrica(3)) echo " onChange='exibeTipo()'";
                        echo ">";
                        echo "<option selected></option>";
                        for ($i = 0; $i < pg_num_rows($res); $i++) {
                            echo "<option value='".pg_fetch_result($res,$i,'linha')."' ";
                            if ($linha == pg_fetch_result($res,$i,'linha') ) echo " selected";
                            echo ">";
                            echo pg_fetch_result($res,$i,'nome');
                            echo "</option>\n";
                        }
                        echo "</select>";?>
                </p><?php
            }
        }


        if ($distrib_posto_pedido_parcial) {

            if ($parcial == 'f') {//HD 221731

                if (isFabrica(104) && !empty($pedido)) {
                    $sql_parcial = "SELECT atende_pedido_faturado_parcial FROM tbl_pedido WHERE pedido = $pedido AND fabrica = $login_fabrica AND atende_pedido_faturado_parcial IS TRUE";
                    $res_parcial = pg_query($con, $sql_parcial);
                    if (pg_num_rows($res_parcial) > 0) {
                        $parcial = 't';
                    }
                }

                $check_false = "";
                $check_true = "";

                if ($parcial == 'f') {
                    $check_false = "SELECTED";
                } else if ($parcial == 't') {
                    $check_true = "SELECTED";
                }

            echo "<p><span class='coluna1'>".traduz('Este pedido pode ser atendido parcial?')."</span>";
		    echo "<select name='parcial' class='frm'>";
		    echo "<option value=''></option>";
                    echo "<option $check_true value='t'>".traduz('Sim')."</option>";
                    echo "<option $check_false value='f'>".traduz('Não')."</option>";
                echo "</select>";

            } else {

                echo "<input type='hidden' name='parcial' id='parcial' value='t' />";

            }

        } else {

            echo "<input type='hidden' name='parcial' id='parcial' value='t' />";

        }


        if (isFabrica(94)) { //HD 414845
            echo "<p><span class='coluna1'>Forma de Envio</span>";
                echo "<select name='forma_envio' id='forma_envio' class='frm' onchange='mostraTransp();'>";
                echo "<option value=''>Selecione...</option>";
                $sql = " SELECT forma_envio, descricao
                        FROM tbl_forma_envio
                        WHERE fabrica = $login_fabrica
                        AND   ativo
                        ORDER BY descricao";
                $res = pg_query($con,$sql);
                if (pg_num_rows($res) > 0) {

                    for ($i =0;$i<pg_num_rows($res);$i++)  {
                        $result_forma_envio = pg_fetch_result($res,$i,'forma_envio');
                        $selected = "";
                        if ($forma_envio == $result_forma_envio) {
                            $selected = "selected";
                        }
                        echo "<option ".$selected." value='".pg_fetch_result($res,$i,'forma_envio')."'>".pg_fetch_result($res,$i,'descricao')."</option>";
                    }
                }
                echo "</select></p>";

            echo "<p id='linha_transportadora' style='display:none;'><span class='coluna1'>Transportadora</span>";
            echo "<input type='text' name='transportadora_nome' size='30' maxlength='50' value='$transportadora_nome' class='textbox' >&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_nome,'nome')\" style='cursor:pointer;'></p>";

            echo "<input type='hidden' name='transportadora' value='$transportadora'>";
            echo "<input type='hidden' name='transportadora_cnpj' value='$transportadora_cnpj'>";
            echo "<input type='hidden' name='transportadora_frete' value='$transportadora_frete'>";

        }

        if (isFabrica(88)) {
        ?>
            <p><span class='coluna1'>Tipo de Frete</span>
            <select name="tipo_frete" class="frm">
                <option value="NOR">Normal</option>
                <option value="URG">Urgente</option>
            </select>
        <?
        }

        if (isFabrica(125) && $login_tipo_posto == 399) {
        ?>
            <p><span class='coluna1'><?=traduz("Tipo de Frete")?></span>
            <select name="tipo_frete" class="frm">
                <option value="CIF">CIF</option>
                <option value="FOB">FOB</option>
            </select>
        <?
        }if (isFabrica(131)) {
        ?>
            <p><span class='coluna1'>Tipo de Frete</span>
            <select name="tipo_frete"  class="frm">
                <option value="FOB">FOB</option>
            </select>
        <?
        }

        if (!isFabrica(24,30,42,104)) { ?>
        <h4><?=traduz("Peças")?></h4>
<?php
        if($login_fabrica == 125 AND empty($gravakit)){
?>
	        <p><center><input type='button' value='<?=traduz("Gravar")?>' onclick="VerificakitPeca()" ALT='<?=traduz("Gravar pedido")?>' border='0' style='cursor: pointer'></p></center>

<?php }else{  ?>

        <p><center><input type='button' value='Gravar' onclick="javascript: if (document.frm_pedido.btn_acao.value == '' ) { document.frm_pedido.btn_acao.value='gravar' ; document.frm_pedido.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar pedido" border='0' style='cursor: pointer'></p></center>
        <?php
    }
        if (isFabrica(140)) { ?>
            <br />
            <font style="color: #ff0000; font-size: 15px;"><center><b>Todo pedido na hora do faturamento terão acréscimos de Impostos.</b> </font>
            <br /><br />
        <?php } ?>

        <?php  if (!isFabrica(115,116,117,120,121)) { ?>
                <br />
                <p style='color:red;font-weight:bold;text-align:center'>
            <?php  if (isFabrica(35)) { ?>
                    Pedido sujeito a análise do departamento de crédito.
                    As peças de produtos nacionais estão <br>sujeitas ao acréscimo de IPI, com percentuais que podem variar de 10 a 20%
            <?php } else {  ?>
                   <?=traduz("Pedidos a prazo dependerão de análise do departamento de crédito.")?>
            <?php if (isFabrica(137)) { ?>
                    <br /> Valores referentes aos impostos serão calculados no faturamento.
            <?php }
            } ?>
            <br /><br />
        <? } ?>

        <font color='red'><center><b><?=traduz("ATENÇÃO")?>:</b> <?=traduz("Utilize o produto para facilitar a pesquisa da peça! Ao escolher o produto a pesquisa restringe")?>
        <br />
        <?=traduz("a lista básica (vista explodida) de peças do produto escolhido. Pode escolher mais de um produto por pedido!")?></font>
        <?  if (isFabrica(91)) { ?>
            <font color='red'><center><b>ATENÇÃO:</b>Pedidos á vista terão prazo de 15 dias para depósito, após serão automaticamente cancelados
        <? } ?>
        <? }

        if (isFabrica(104)) { ?>
        <h4>Peças</h4>
        <br/>
        <center>
        <p style='color:red;text-align:center;font-weight:bold'>ATENÇÃO!!! Valor do Frete não incluso no preço das peças.<br/>
Pedidos a prazo dependerão de análise de crédito.</p>
        <br />
        <p style='color:red;text-align:center;'><b>DICAS!!!</b><br/>Utilize a Ref. do produto (código) para facilitar a pesquisa da peça!<br/>
Utilize a Ref. do Componente , com no mínimo 3 dígitos, para apresentar todos os componentes da Lista Básica do produto Informado.</p>
        </center>
        <? } ?>
        <br />

        <? if (isFabrica(24) or isFabrica(42) or isFabrica(30)) { //HD 70768-Retirar estes campos para a Esmaltec ?>
            <input class="frm" type="hidden" name="produto_referencia" value="<? echo $produto_referencia; ?>">
        <? } ?>

        <?php

        if (isFabrica(45)) {
            ?>
                <div id="recado_peca_nks" style='display:none; width: 97%; background-color: #FF0000; font: bold 16px Arial; color: #FFFFFF; text-align: center; margin: 0 auto; height: 25px !important; margin-top: 10px; margin-bottom: 10px;'>A peça indisponível, por favor entre contato com Fabricante!</div>
            <?php
        }

        if (isFabrica(145)) {
             $sql = "SELECT
                        codigo_condicao,
                        descricao,
                        limite_minimo
                    FROM tbl_condicao
                    WHERE fabrica = $login_fabrica
                    AND visivel IS TRUE";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
            ?>
                <br />
                <table class="tabela" width="99%" align="center">
                    <thead>
                        <tr class="titulo_tabela">
                            <th colspan="3">Descrição das Condições de Pagamento</th>
                        </tr>
                        <tr class="titulo_tabela">
                            <td>Código</td>
                            <td>Descrição</td>
                            <td>Valor Mínimo</td>
                        </tr>
                    </thead>
                    <tbody>
                    <?php


                        $rows = pg_num_rows($res);
                        for ($i = 0; $i < $rows; $i++) {
                            $codigo_condicao    = pg_fetch_result($res, $i, "codigo_condicao");
                            $descricao          = pg_fetch_result($res, $i, "descricao");
                            $limite_minimo      = number_format(pg_fetch_result($res, $i, "limite_minimo"), 2, ",", ".");

                            echo "
                                <tr>
                                    <td align='center' style='background-color: #fff; padding: 5px;'>$codigo_condicao</td>
                                    <td align='center' style='background-color: #fff;'>$descricao</td>
                                    <td align='center' style='background-color: #fff;'>R$ $limite_minimo</td>
                                </tr>
                            ";

                        }

                    ?>
                    </tbody>
                </table>
                <br />
            <?php
            }

        }

        $titulo_coluna_lenoxx = (in_array($login_fabrica, [11,172])) ? "titulo_coluna_lenoxx" : "";

        $porcento = ($login_fabrica == 123) ? '100%' : '99%';

        ?>

        <!-- Peças -->
        <p>
        <table width="<?=$porcento?>" border="0" cellspacing="1" cellpadding="2" align="center" id="itens_pedido" class='tabela'>
            <thead>
                <tr height="20" class='titulo_coluna <?=$titulo_coluna_lenoxx?>' nowrap>
                    <?
                    //HD 142667
                    if (!isFabrica(24, 30, 42)) { ?>
                    <th width="95px"><?=traduz("Ref. Produto")?></th>
                    <th width='170px'><?=traduz("Desc. Produto")?></th>
                    <?}?>
                    <?php if (isFabrica(104,139)) {?>
		            <th width='5px'><? echo ($login_fabrica == 139) ? "Lista Básica" : "LB";?></font></th>
                    <?php } ?>
                    <th width='100px'><?=(isFabrica(6)) ? traduz("Código Componente") : traduz("Ref. Componente")?></th>
                    <?php if (!isFabrica(14,24,104,139)) { ?>
                    <th width='5px'><?=traduz("Lista Básica")?></font></th>
                    <? } ?>
                    <th width='170px'><?=traduz("Descrição Componente")?></font></th>
                    <?php if (isFabrica(11,104,172)) { ?>
                            <th><?=traduz("Qtde Máxima")?></th>
                    <?php } ?>
                    <th ><?=traduz("Qtde")?></th><?php
                    if (!isFabrica(14, 24)) {
                        if (isFabrica($vet_ipi)) {//HD 677442 - Valor com IPI?>
                    <th> <?=((isFabrica(120)) ? "Preço s/ IPI" : "Preço c/ IPI")?> </th>
                        <?} else{?>
                    <th><?=traduz("Preço Unit.")?></th><?php
                        }?>
                    <th><?=traduz("Total")?></th><?php
                    }?>
                </tr>
            </thead>
            <tbody>
            <?php

            $total_geral = 0;

            for ($i = 0 ; $i < $qtde_item ; $i++) {

                $prevEntrega = "";
                $disponibilidade = "";

                if (strlen($pedido) > 0 && !isFabrica(104)) {   // AND strlen ($msg_erro) == 0
                    $sql = "SELECT  tbl_pedido_item.pedido_item,
                                    tbl_peca.referencia        ,
                                    tbl_peca.descricao         ,
                                    tbl_peca.peca              ,
                                    tbl_peca.parametros_adicionais,
                                    tbl_pedido_item.qtde       ,
                                    tbl_pedido_item.preco
                            FROM  tbl_pedido
                            JOIN  tbl_pedido_item USING (pedido)
                            JOIN  tbl_peca        USING (peca)
                            WHERE tbl_pedido_item.pedido = $pedido
                            AND   tbl_pedido.posto   = $login_posto
                            AND   tbl_pedido.fabrica = $login_fabrica
                            ORDER BY tbl_pedido_item.pedido_item";

                    $res = pg_query ($con,$sql);

                    if (pg_num_rows($res) > 0) {
                        $pedido_item     = trim(@pg_fetch_result($res,$i,pedido_item));
                        $peca_referencia = trim(@pg_fetch_result($res,$i,referencia));
                        $peca_descricao  = trim(@pg_fetch_result($res,$i,descricao));
                        $qtde            = trim(@pg_fetch_result($res,$i,qtde));
                        $preco           = trim(@pg_fetch_result($res,$i,preco));
                        if (strlen($preco) > 0) $preco = number_format($preco,2,',','.');

                        if ($login_fabrica == 123) {
                            $parametros_adicionais = pg_fetch_result($res, $i, 'parametros_adicionais');
                            if (!empty($parametros_adicionais)) {
                                $parametros_adicionais = json_decode($parametros_adicionais, true);
                                $peca = pg_fetch_result($res, $i, 'peca');
                                $sqlEstoque = " SELECT tbl_posto_estoque.peca,
                                                    sum(tbl_posto_estoque.qtde) as qtde
                                                FROM tbl_posto_estoque
                                                INNER JOIN tbl_peca ON tbl_peca.peca = tbl_posto_estoque.peca  AND tbl_peca.fabrica = $login_fabrica
                                                WHERE qtde > 0
                                                AND tbl_posto_estoque.peca = $peca
                                                GROUP BY 1";                        
                                $resEstoque = pg_query($con, $sqlEstoque);
                                if (pg_num_rows($resEstoque) > 0) {
                                    $disponibilidadeLabel = "Disponível";
                                } else {
                                    $disponibilidadeLabel = "Indisponível";
                                    if (isset($parametros_adicionais["previsaoEntrega"])) {
                                        $dtEntrega = date("d/m/Y", strtotime($parametros_adicionais["previsaoEntrega"]));
                                        if (strtotime(date("Y-m-d")) > strtotime($parametros_adicionais["previsaoEntrega"])) {
                                            $prevEntrega = "Previsão de Recebimento dessa Peça em 90 dias, sujeito a alteração";
                                        } else {
                                            $prevEntrega = "Previsão de Recebimento dessa Peça em $dtEntrega, Sujeito a Alteração";
                                        }
                                    }
                                }
                            }
                        }

                        $produto_referencia = '';
                        $produto_descricao  = '';
                    }else{
                        $produto_referencia= $_POST["produto_referencia_" . $i];
                        $produto_descricao = $_POST["produto_descricao_" . $i];
                        $pedido_item     = $_POST["pedido_item_"     . $i];
                        $peca_referencia = $_POST["peca_referencia_" . $i];
                        $peca_descricao  = $_POST["peca_descricao_"  . $i];
                        $qtde            = $_POST["qtde_"            . $i];
                        $preco           = $_POST["preco_"           . $i];
                        if ($login_fabrica == 123) {
                            $prevEntrega      = $_POST["prevEntrega_" . $i];
                            $disponibilidade  = $_POST["disponibilidade_"  . $i];
                        }
                    }
                }else{
                    $produto_referencia= $_POST["produto_referencia_"     . $i];
                    $produto_descricao = $_POST["produto_descricao_" . $i];
                    $pedido_item     = $_POST["pedido_item_"     . $i];
                    $peca_referencia = $_POST["peca_referencia_" . $i];
                    $peca_descricao  = $_POST["peca_descricao_"  . $i];
                    $qtde            = $_POST["qtde_"            . $i];
                    $preco           = $_POST["preco_"           . $i];
                    if ($login_fabrica == 123) {
                        $prevEntrega      = $_POST["prevEntrega_" . $i];
                        $disponibilidade  = $_POST["disponibilidade_"  . $i];
                    }
                }

                $peca_referencia = trim ($peca_referencia);

                #--------------- Valida Peças em DE-PARA -----------------#
                $tem_obs = false;
                $linha_obs = "";

		if (strlen ($peca_referencia) > 0) {
	                $sql = "SELECT para FROM tbl_depara WHERE de = '$peca_referencia' AND fabrica = $login_fabrica";
        	        $resX = pg_query ($con,$sql);

                	if (pg_num_rows ($resX) > 0) {
	                    $linha_obs = "Peça original " . $peca_referencia . " mudou para o código acima <br>&nbsp;";
        	            $peca_referencia = pg_fetch_result ($resX,0,0);
                	    $tem_obs = true;
                	}
		}

                #--------------- Valida Peças Fora de Linha -----------------#
		if (strlen ($peca_referencia) > 0) {
	                $sql = "SELECT * FROM tbl_peca_fora_linha WHERE referencia = '$peca_referencia' AND fabrica = $login_fabrica";

        	        $resX = pg_query ($con,$sql);
                	if (pg_num_rows ($resX) > 0) {
	                    $libera_garantia = pg_fetch_result ($resX,0,libera_garantia);
        	            #17624
                	    if (isFabrica(3) AND $libera_garantia=='t'){
                        	$linha_obs .= "Peça acima está fora de linha. Disponível somente para garantia. Caso necessário, favor contatar a Assistência Técnica Britânia <br>&nbsp;";
	                    }else{
        	                $linha_obs .= "Peça acima está fora de linha <br>&nbsp;";
                	    }
	                    $tem_obs = true;
        	        }
		}

                if (strlen ($peca_referencia) > 0) {
                    $sql = "SELECT descricao FROM tbl_peca WHERE referencia = '$peca_referencia' AND fabrica = $login_fabrica";
                    $resX = pg_query ($con,$sql);
                    if (pg_num_rows ($resX) > 0) {
                        $peca_descricao = pg_fetch_result ($resX,0,0);
                    }
                }

                $peca_descricao = trim ($peca_descricao);

                $cor="";
                if ($linha_erro == $i and strlen ($msg_erro) > 0) $cor='#ffcccc';
                if ($linha_erro == $i and strlen ($msg_erro) > 0) $cor='#ffcccc';
                if ($tem_obs) $cor='#FFCC33';

                if (isFabrica(24)) {
                    $width_ref = "100px";
                    $width_desc = "500px";
                } else if (isFabrica(104)) {
                    $width_ref = "95px";
                    $width_desc = "156px";
                }else{
                    $width_ref = "65px";
                    $width_desc = "135px";
                }
            ?>
                <tr bgcolor="<? echo $cor ?>" nowrap class="class_tr_<?=$i?>" rel='tr_<?php echo $i; ?>'>
                    <? if (!isFabrica(24, 30, 42)) {
                        $width_referencia = 'width:55px';
                        $nowrap = 'nowrap';
                        if (isFabrica(104)) {
                            $width_referencia = 'width:95px';
                            $nowrap = '';
                        }

                        $usa_br = ($login_fabrica == 104) ? "<br />" : "&nbsp;";

                        ?>
                        <td align='left'>
                            <?php if (isFabrica(120)) { ?>
                                 <input style="width:55px" class="frm" type="text" name="produto_referencia_<?=$i?>" onFocus="this.select()" id="produto_referencia_<?=$i?>" size="7" maxlength="20" value="<? echo $produto_referencia ?>">&nbsp;<img src='imagens/lupa.png' style="cursor:pointer" border='0' alt="Clique para pesquisar pela referência do produto" align='absmiddle' onclick="javascript: if($('#linha_produto').val() == ''){alert('Selecione a linha para o pedido.')}else{ fnc_pesquisa_produto ('',document.frm_pedido.produto_referencia_<?=$i?>,<?=$i?>,document.frm_pedido.linha_produto)}"> 
                            <?php }else{?>
                                <input style="<?=$width_referencia?>" class="frm" type="text" name="produto_referencia_<?=$i?>" onFocus="this.select()" id="produto_referencia_<?=$i?>" size="7" maxlength="20" value="<? echo $produto_referencia ?>"><?=$usa_br?><img src='imagens/lupa.png' style="cursor:pointer" border='0' alt="Clique para pesquisar pela referência do produto" align='absmiddle' onclick="javascript: fnc_pesquisa_produto ('',document.frm_pedido.produto_referencia_<?=$i?>,<?=$i?>,document.frm_pedido.linha_produto)">
                            <?php } ?>
                        </td>
                        <td align='left'>
                            <?php if (isFabrica(120)) { ?>
                                 <input style="width:135px" class="frm" type="text" name="produto_descricao_<?=$i?>" onFocus="this.select()" id="produto_descricao_<?=$i?>" size="12" value="<? echo $produto_descricao ?>">&nbsp;<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' alt="Clique para pesquisar pela descrição do produto" onclick="javascript: if($('#linha_produto').val() == ''){alert('Selecione a linha para o pedido.')}else{ fnc_pesquisa_produto (document.frm_pedido.produto_descricao_<?=$i?>,'',<?=$i?>,document.frm_pedido.linha_produto)}"> 
                            <?php }else{?>
                                <input style="width:135px" class="frm" type="text" name="produto_descricao_<?=$i?>" onFocus="this.select()" id="produto_descricao_<?=$i?>" size="12" value="<? echo $produto_descricao ?>"><?=$usa_br?><img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' alt="Clique para pesquisar pela descrição do produto" onclick="javascript: fnc_pesquisa_produto (document.frm_pedido.produto_descricao_<?=$i?>,'',<?=$i?>,document.frm_pedido.linha_produto)">
                            <?php } ?>
                            <input type="hidden" name="produto_voltagem_<?=$i?>">
                        </td>
                    <?}?>
                    <?php if (isFabrica(104,139)) { ?>
                    <td><img src="imagens/btn_lista.gif" hspace='12' align='center' style="cursor: pointer;" onclick="javascript: document.getElementById('controle_blur').value = 1; pesquisaPeca (document.frm_pedido.produto_referencia_<?=$i?>,document.frm_pedido.peca_referencia_<?=$i?>,'lista_basica',<?=$i?>, document.frm_pedido.tipo_pecas,document.frm_pedido.tipo_pedido, document.frm_pedido.linha_produto)" ></td>
<?php
                    }
?>
                    <td align='left' <?=$nowrap?> >
                        <input type="hidden" name="pedido_item_<? echo $i ?>" size="15" value="<? echo $pedido_item; ?>">
                        <input style="width:<?=$width_ref?>" class="frm" type="text" name="peca_referencia_<?=$i?>" id="peca_referencia_<?=$i?>" size="15" value="<? echo $peca_referencia; ?>" <?php
                        //HD 254266
                        if (isFabrica(14)) {?>
                            onkeyup="if (verificaTab(event)) {document.getElementById('controle_blur').value = 0; fnc_pesquisa_peca_lista_intel (document.frm_pedido.produto_referencia_<?=$i?>.value , document.frm_pedido.peca_referencia_<?=$i?> , document.frm_pedido.peca_descricao_<?=$i?> , document.frm_pedido.posicao, 'referencia')}" <?
                        } else if (isFabrica(24,30,42)) {?>
                            onkeyup="if (verificaTab(event)) {document.getElementById('controle_blur').value = 0; fnc_pesquisa_peca_lista (document.getElementById('produto_referencia_<?=$i?>').value, document.getElementById('peca_referencia_<?=$i?>'), document.getElementById('peca_descricao_<?=$i?>'), document.getElementById('preco_<?=$i?>'), document.getElementById('voltagem'), 'referencia', document.getElementById('qtde_<?=$i?>').value)}" <?
                        }elseif (isFabrica(104)) { ?>
                         onkeydown="if(verificaTab(event)) {document.getElementById('controle_blur').value = 1; pesquisaPeca (document.frm_pedido.produto_referencia_<?=$i?>,document.frm_pedido.peca_referencia_<?=$i?>,'peca',<?=$i?>, document.frm_pedido.tipo_pecas ,document.frm_pedido.tipo_pedido)}" 
                        <?}elseif (isFabrica(120)) { ?>
                             onkeydown="if (verificaTab(event)) {document.getElementById('controle_blur').value = 0; if( $('#linha_produto').val() == ''){alert('Selecione a linha para o pedido.')}else{pesquisaPeca (document.frm_pedido.produto_referencia_<?=$i?>,document.frm_pedido.peca_referencia_<?=$i?>,'peca',<?=$i?>, 't',document.frm_pedido.tipo_pedido,document.frm_pedido.linha_produto)} }" 
                        <? }else{ ?>
                            onkeydown="if (verificaTab(event)) {document.getElementById('controle_blur').value = 0; pesquisaPeca (document.frm_pedido.produto_referencia_<?=$i?>,document.frm_pedido.peca_referencia_<?=$i?>,'peca',<?=$i?>, 't',document.frm_pedido.tipo_pedido,document.frm_pedido.linha_produto)}" <?php
                        }

                        if ($login_fabrica == 123) {
                        ?>
                            onblur="verificaDispPeca(<?=$i?>)";
                        <?php
                        }
                        ?>
                        />
                        <img src='imagens/lupa.png' style="cursor: pointer;" alt="Clique para pesquisar por referência do componente" border='0' hspace='5' align='absmiddle'
                        <? if (isFabrica(14) ) { ?> onclick="javascript: document.getElementById('controle_blur').value = 1; fnc_pesquisa_peca_lista_intel (document.frm_pedido.produto_referencia_<?=$i?>.value , document.frm_pedido.peca_referencia_<?=$i?>, document.frm_pedido.peca_descricao_<?=$i?> , document.frm_pedido.posicao, 'referencia')" <?
                         }elseif(isFabrica(24,30,42)){ ?> onclick="javascript: document.getElementById('controle_blur').value = 1; fnc_pesquisa_peca_lista (window.document.frm_pedido.produto_referencia.value, window.document.frm_pedido.peca_referencia_<?=$i?>, window.document.frm_pedido.peca_descricao_<?=$i?>, window.document.frm_pedido.preco_<?=$i?>, window.document.frm_pedido.voltagem,'referencia', document.getElementById('qtde_<?=$i?>').value)" <? 
                        }elseif (isFabrica(104)) { ?>
                        onclick="javascript: document.getElementById('controle_blur').value = 1; var aux_tipo_pecas = document.getElementById('aux_tipo_pecas').value; pesquisaPeca (document.frm_pedido.produto_referencia_<?=$i?>,document.frm_pedido.peca_referencia_<?=$i?>,'peca',<?=$i?>, aux_tipo_pecas, document.frm_pedido.tipo_pedido)"
                        <?}elseif (isFabrica(120)) { ?>
                           onclick="javascript: document.getElementById('controle_blur').value = 1; if($('#linha_produto').val() == ''){alert('Selecione a linha para o pedido.')}else{ pesquisaPeca (document.frm_pedido.produto_referencia_<?=$i?>,document.frm_pedido.peca_referencia_<?=$i?>,'peca',<?=$i?>, 't',document.frm_pedido.tipo_pedido,document.frm_pedido.linha_produto)}" 
                        <? }else{ ?>
                          onclick="javascript: document.getElementById('controle_blur').value = 1; pesquisaPeca (document.frm_pedido.produto_referencia_<?=$i?>,document.frm_pedido.peca_referencia_<?=$i?>,'peca',<?=$i?>, 't',document.frm_pedido.tipo_pedido,document.frm_pedido.linha_produto)"
                           <? } ?> >
                    </td>
                    <? 
                    if (!isFabrica(24,104,120,139)) { ?>
                    <td><img src="imagens/btn_lista.gif" hspace='12' align='center' style="cursor: pointer;" onclick="javascript: document.getElementById('controle_blur').value = 1; pesquisaPeca (document.frm_pedido.produto_referencia_<?=$i?>,document.frm_pedido.peca_referencia_<?=$i?>,'lista_basica',<?=$i?>, 't',document.frm_pedido.tipo_pedido, document.frm_pedido.linha_produto)" ></td>
                    <? } ?>
                    <?php if (isFabrica(120)) { ?>
                         <td><img src="imagens/btn_lista.gif" hspace='12' align='center' style="cursor: pointer;" onclick="javascript: document.getElementById('controle_blur').value = 1; if($('#linha_produto').val() == ''){alert('Selecione a linha para o pedido.')}else{ pesquisaPeca (document.frm_pedido.produto_referencia_<?=$i?>,document.frm_pedido.peca_referencia_<?=$i?>,'lista_basica',<?=$i?>, 't',document.frm_pedido.tipo_pedido, document.frm_pedido.linha_produto)}" ></td> 
                    <?php } ?>
                    <td align='left' <?=$nowrap?> >
                        <input type="hidden" name="posicao">
                        <input class="frm" style="width:<?=$width_desc?>" type="text" name="peca_descricao_<? echo $i ?>" id="peca_descricao_<? echo $i ?>" size="20" value="<? echo $peca_descricao ?>" <?php
                        //HD 254266
                        if (isFabrica(14)) {?>
                            onkeyup="if (verificaTab(event)) {document.getElementById('controle_blur').value = 0; fnc_pesquisa_peca_lista_intel (document.getElementById('produto_referencia_<?=$i?>').value, document.frm_pedido.peca_referencia_<?=$i?>, document.frm_pedido.peca_descricao_<?=$i?>, document.frm_pedido.posicao, 'descricao')}" <?
                        } else if (isFabrica(24,30,42)) {?>
                            onkeyup="if (verificaTab(event)) {document.getElementById('controle_blur').value = 0; fnc_pesquisa_peca_lista (document.getElementById('produto_referencia').value, document.getElementById('peca_referencia_<?=$i?>'), document.getElementById('peca_descricao_<?=$i?>'), document.getElementById('preco_<?=$i?>'), document.getElementById('voltagem'), 'descricao', document.getElementById('qtde_<?=$i?>').value)}" <?
                            }elseif (isFabrica(104)) { ?>

                        onkeydown="if (verificaTab(event)) {document.getElementById('controle_blur').value = 0; document.getElementById('controle_blur').value = 0; pesquisaPeca (document.frm_pedido.produto_referencia_<?=$i?>,document.frm_pedido.peca_descricao_<?=$i?>,'descricao',<?=$i?>, document.frm_pedido.tipo_pecas ,document.frm_pedido.tipo_pedido)}"
                        <?}elseif (isFabrica(120)) {?>
                             onkeydown="if (verificaTab(event)) {document.getElementById('controle_blur').value = 0; document.getElementById('controle_blur').value = 1; if($('#linha_produto').val() == ''){alert('Selecione a linha para o pedido.')}else{ pesquisaPeca (document.frm_pedido.produto_referencia_<?=$i?>,document.frm_pedido.peca_descricao_<?=$i?>,'descricao',<?=$i?>, 't',document.frm_pedido.tipo_pedido,document.frm_pedido.linha_produto)} }" 
                        <? }else{ ?>
                            onkeydown="if (verificaTab(event)) {document.getElementById('controle_blur').value = 0; document.getElementById('controle_blur').value = 1; pesquisaPeca (document.frm_pedido.produto_referencia_<?=$i?>,document.frm_pedido.peca_descricao_<?=$i?>,'descricao',<?=$i?>, 't',document.frm_pedido.tipo_pedido,document.frm_pedido.linha_produto)}"<?php
                        }

                        if ($login_fabrica == 123) {
                        ?>
                            onblur="verificaDispPeca(<?=$i?>)";
                        <?php
                        }
                        ?>
                        />
                        <?php if (!isFabrica(140)) { ?>
                             <img src='imagens/lupa.png' style="cursor: pointer;" alt="Clique para pesquisar por descrição do componente" border='0' hspace='5' align='absmiddle' <? if (isFabrica(14)) { ?> onclick="javascript: document.getElementById('controle_blur').value = 1; fnc_pesquisa_peca_lista_intel (document.frm_pedido.produto_referencia_<?=$i?>.value , document.frm_pedido.peca_referencia_<?=$i?> , document.frm_pedido.peca_descricao_<?=$i?> , document.frm_pedido.posicao, 'descricao')" <? }elseif(isFabrica(24,30,42)){ ?> onclick="javascript: document.getElementById('controle_blur').value = 1; fnc_pesquisa_peca_lista (window.document.frm_pedido.produto_referencia.value, window.document.frm_pedido.peca_referencia_<?=$i?>, window.document.frm_pedido.peca_descricao_<?=$i?>, window.document.frm_pedido.preco_<?=$i?>, window.document.frm_pedido.voltagem,'descricao', document.getElementById('qtde_<?=$i?>').value)" 
                        <? }elseif (isFabrica(104)) { ?>
                            onclick="javascript: document.getElementById('controle_blur').value = 1; pesquisaPeca (document.frm_pedido.produto_referencia_<?=$i?>,document.frm_pedido.peca_descricao_<?=$i?>,'descricao',<?=$i?>, document.frm_pedido.tipo_pecas ,document.frm_pedido.tipo_pedido)"
                        <?}elseif (isFabrica(120)) {?>
                             onclick="javascript: document.getElementById('controle_blur').value = 1; if($('#linha_produto').val() == ''){alert('Selecione a linha para o pedido.')}else{ pesquisaPeca (document.frm_pedido.produto_referencia_<?=$i?>,document.frm_pedido.peca_descricao_<?=$i?>,'descricao',<?=$i?>, 't',document.frm_pedido.tipo_pedido,document.frm_pedido.linha_produto) }" 
                        <?}else{ ?>
                            onclick="javascript: document.getElementById('controle_blur').value = 1; pesquisaPeca (document.frm_pedido.produto_referencia_<?=$i?>,document.frm_pedido.peca_descricao_<?=$i?>,'descricao',<?=$i?>, 't',document.frm_pedido.tipo_pedido,document.frm_pedido.linha_produto)"
                            <?} ?>>
                        <?php } ?>
                    </td>
<?php
                    if (isFabrica(11,104,172)) {
?>
                    <td align='center'>
                        <input class='frm' type='text' style='width:45px;text-align:center;' name='qtde_demanda_<?=$i?>' id='qtde_demanda_<?=$i?>' size='5' maxlength='5' value='' rel='<?=$i?>' readOnly />
                    </td>

<?php
                    }
?>
<?php
                    if (isFabrica(123)) {
?>
                        <input class='frm' type='hidden' style='width:45px;text-align:center;' name='prevEntrega_<?=$i?>' id='prevEntrega_<?=$i?>' size='5' maxlength='15' value='<?=$prevEntrega?>' rel='<?=$i?>'/>
                        <input class='frm' type='hidden' style='width:45px;text-align:center;' name='disponibilidade_<?=$i?>' id='disponibilidade_<?=$i?>' size='5' maxlength='50' value='<?=$disponibilidade?>' rel='<?=$i?>'/>
<?php
                    }
?>
                    <td align='center'>
                        <input class="frm" type="text" style="width:45px;text-align:center;" onKeyPress="return somente_numero(event)"  name="qtde_<? echo $i ?>" id="qtde_<? echo $i ?>" size="5" maxlength='5' value="<? echo $qtde ?>"  rel="<?php echo $i;?>" tabindex="<?=($i*4)+3?>"<?php
                        if (isFabrica(42)) {
                            echo " onblur='javascript: fnc_makita_preco ($i)' ";
                        } else {
                            echo " onblur='javascript: fnc_calcula_total($i); atualiza_proxima_linha($i)' ";
                        }?>
                        />
                    </td>

                    <? if (!isFabrica(14, 24)) { ?>
                    <td align='center'>
                        <input style="width:57px;text-align:right;" class="frm" id="preco_<? echo $i ?>" type="text" name="preco_<? echo $i ?>" size="10"  value="<? echo $preco ?>" readonly style='text-align:right'>
                    </td>
                    <td align='center'>
                        <input style="width:57px;text-align:right;" class="frm" name="sub_total_<? echo $i ?>" id="sub_total_<? echo $i ?>" type="text" size="10" rel='total_pecas' readonly style='text-align:right' value='<?
                                $preco = str_replace(",",".",$preco);
                                if ($qtde &&  $preco) {
                                    $total_geral += $preco * $qtde;
                                    echo  $preco * $qtde;
                                }
                            ?>'>
                    </td>
                    <? } ?>

                    <? if (isFabrica(24)){ ?>
                    <input type="hidden" name="preco_<? echo $i ?>" value="<? echo $preco ?>">
                     <? } ?>
                </tr>
                <?php 
                    if ($login_fabrica == 123) {
                        $stylePrev = "style='display: none;'";
                        if ($disponibilidade == "Indisponível") {
                            $stylePrev = "";
                        }
                ?>
                    <tr class="msg_erro_disp_<?=$i?>" <?=$stylePrev?> >
                        <td colspan="100%" class="msg_erro_txt_<?=$i?>"><b><?=$prevEntrega?></b></td>
                    </tr> 
                    <script type="text/javascript">
                        verificaDispPeca(<?=$i?>);
                    </script>
                <?php } ?>

                <?
                if ($tem_obs) {
                    echo "<tr bgcolor='#FFCC33' style='font-size:12px'>";
                    echo "<td colspan='4'>$linha_obs</td>";
                    echo "</tr>";
                }
                ?>

            <?

            }

            ?>
            </tbody>

            <tfoot>
            <?php

            if (!isFabrica(24, 30)) {
                echo "<tr style='font-size:12px' align='right'>";

                $colspan = (isFabrica(138)) ? 6 : 7;
                if (isFabrica(145)) {
                    $colspan = 8;
                } else if (isFabrica(104)) {
                    $colspan = 9;
                }

                if (isFabrica(138) OR isFabrica(74)) {

                    $box_deconto = "<div id='box_deconto' style='border: 0px; width: 350px; float: left; padding-top: 5px; color: #ff0000;'></div>";

                    echo "<input type='hidden' name='valor_desconto' id='valor_desconto' />";
                    echo "<input type='hidden' name='qtde_item_hidden' value='{$qtde_item}' />";
                    echo "<td colspan='2' align='center'><button type='button' onclick='addLinha({$qtde_item})'>Adicionar Linha +</button></td>";
                }
                if (isFabrica(120)) {
                    $readonly = 'readonly';
                }
                echo "<td colspan='{$colspan}'>{$box_deconto} <b>Total</b>: <INPUT TYPE='text' size='15' style='text-align:right' $readonly name='total_pecas' id='total_pecas'";
                    if (strlen($total_geral) > 0)  echo " value='".number_format($total_geral,2,',','.')."'";
                echo "></td>";
                echo "</tr>";
            }
            if (isFabrica(15) || isFabrica(85)) {
                echo "<tr style='font-size:12px' align='center'>";
                echo "<td colspan='7'><b>Observação</b>: <br /><textarea NAME='observacao_pedido' class='frm' cols='30' rows='7'>";
                    if (strlen($observacao_pedido) > 0)  echo $observacao_pedido; echo "</textarea>";
                echo "</td>";
                echo "</tr>";
            }
            if (isFabrica(88,94,120)) {
?>
                <tr style='font-size:12px' align='right'>
                    <td colspan='7'>
                        <b>Valor Frete</b>: <INPUT TYPE='text' <?=$readonly?> size='15' style='text-align:right' id='valor_frete' name='valor_frete' value='<?=number_format($valor_frete,2,',','.')?>' />
                        <input type="hidden" name="frete_calculou" id="frete_calculou" value="">
                    </td>
                </tr>
                <tr style='font-size:12px' align='right'>
                    <td colspan='7'>
                        <b>Total + Frete</b>: <INPUT TYPE='text' <?=$readonly?> size='15' style='text-align:right' id='valor_total_frete' value='<?=number_format($valor_total_frete,2,',','.')?>' />
                    </td>
                </tr>
<?
            }
?>
            </tfoot>
            </table>
        </p>
        <p><center>
        <input type="hidden" name="btn_acao" value="">

<?
        if (isFabrica(88,120)) {

			$valorF = str_replace(',', '.', $valor_frete);
			$valor_frete = floatval($valorF);
			if (!empty($valor_frete)) {
				$sim = "sim";
			}

?>
        <input type="hidden" name="frete_calculado" id="frete_calculado" value="<?=$sim?>">
        <button type="button" value="" id='calcular_frete' title="Calcular Frete">Calcular Frete</button>
<?
        }
        if($login_fabrica == 125 AND empty($gravakit)){
?>
	        <input type='button' value='<?=traduz("Gravar")?>' onclick="VerificakitPeca()" ALT="Gravar pedido" border='0' style='cursor: pointer'>
	        <input type="hidden" name="valoresKit" id="valoresKit" valeu="">
	        <input type="hidden" name="gravakit" id="gravakit" valeu="<?=$gravakit?>">

<?php }else{  ?>
        <input type='button' value='Gravar' onclick="javascript: if (document.frm_pedido.btn_acao.value == '' ) { document.frm_pedido.btn_acao.value='gravar' ; document.frm_pedido.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar pedido" border='0' style='cursor: pointer'>
        </center>
        </p>
    <?php } ?>
</div>
</li>
</ul>
<!-- Fecha Divisão-->

</form>


<br clear='both'>
<p>

<?php
    if (isFabrica(104,141,144)) {
?>
        <form method="POST" enctype="multipart/form-data" action="<? echo $PHP_SELF ?>">
            <table width="700" align="center">
                <?php if (isFabrica(104)) { ?>
                    <tr>
                        <td align='center' bgcolor='#f4f4f4'>
                            <p align='justify'><font size='1' color='#FF0000'><b>AVISO: APÓS GRAVAR O SEU PEDIDO, IRÁ APARECER O RESUMO DOS ITENS LANÇADOS E ABAIXO DESTE RESUMO, TERÁ O BOTÃO DE FINALIZAÇÃO QUE SOMENTE SERÁ USADO QUANDO NÃO EXISTIREM MAIS ITENS A SEREM LANÇADOS NESTE PEDIDO.</b></font></p>
                        </td>
                    </tr>
                <?php }else{ ?>
                    <tr>
                        <td align='center' bgcolor='#f4f4f4'>
                            <p align='justify'><font size='1' color='#FF0000'><b>AVISO: APÓS GRAVAR O SEU PEDIDO, IRÁ APARECER O RESUMO DOS ITENS LANÇADOS E ABAIXO DESTE RESUMO, TERÁ O BOTÃO DE FINALIZAÇÃO QUE SOMENTE SERÁ USADO QUANDO NÃO EXISTIREM MAIS ITENS A SEREM LANÇADOS NESTE PEDIDO E ANEXADO O COMPROVANTE DE PAGAMENTO.</b></font></p>
                        </td>
                    </tr>
                    <tr>
                        <td align='center' bgcolor='#f4f4f4'>
                            <p align='justify'><font size='1'><b>CASO JÁ TENHA TERMINADO DE DIGITAR OS ITENS E ANEXADO O COMPROVANTE DE PAGAMENTO E QUEIRA PASSAR PARA A PRÓXIMA TELA, CLIQUE EM FINALIZAR</b></font></p>
                        </td>
                    </tr>
                <?php } ?>
            </table> <br />
<?php
    if (!empty($cook_pedido)) {
        $pedido = $cook_pedido;

        $sqlT="SELECT case when tbl_posto_linha.tabela notnull then tbl_posto_linha.tabela else tabela_posto end as tabela
            FROM tbl_posto_linha
            JOIN tbl_tabela using (tabela)
            WHERE fabrica = $login_fabrica and posto = $login_posto";
        $resT = pg_query($con,$sqlT);

        if (pg_numrows($resT) > 0) {
            $tabela = pg_result($resT,0,tabela);
        }


        if (!isFabrica(104)) { // HD-2416482
            $pedido_tipo_acessorio = "";
            $sql = "SELECT tbl_peca.referencia,
            tbl_peca.descricao,
            tbl_pedido_item.pedido_item,
            tbl_pedido_item.qtde,
            tbl_tabela_item.preco
            FROM tbl_pedido_item
            JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca AND tbl_peca.fabrica = $login_fabrica
            JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_pedido_item.peca AND tbl_tabela_item.tabela = $tabela
            WHERE tbl_pedido_item.pedido = $pedido";
        }else{
            $sqlA = "SELECT pedido_acessorio FROM tbl_pedido WHERE pedido = $pedido;";
            $resA = pg_query($con,$sqlA);
            $pedido_acessorio_tipo = pg_fetch_result($resA, 0, pedido_acessorio);
            if ($pedido_acessorio_tipo == 't') {
                $pedido_tipo_acessorio = " de Acessórios";
            }else{
                $pedido_tipo_acessorio = " de Peças";
            }

            $sql = "SELECT  tbl_pedido_item.pedido_item,
                tbl_peca.referencia        ,
                tbl_peca.descricao         ,
                tbl_pedido_item.qtde       ,
                tbl_pedido_item.preco      ,
                tbl_pedido.condicao
                FROM  tbl_pedido
                JOIN  tbl_pedido_item USING (pedido)
                JOIN  tbl_peca        USING (peca)
                WHERE tbl_pedido_item.pedido = $pedido
                AND   tbl_pedido.posto   = $login_posto
                AND   tbl_pedido.fabrica = $login_fabrica
                ORDER BY tbl_pedido_item.pedido_item";
        }
        $res = pg_query($con,$sql);
        if (pg_numrows($res) > 0) {
            $total_itens = pg_numrows($res);
        ?>

            <table width="700" align="center" cellspacing="1" class="tabela">
            <caption class="titulo_tabela">Itens do Pedido<?=$pedido_tipo_acessorio?></caption>
            <tr class="titulo_coluna">
                <td>Ação</td>
                <td>Referencia</td>
                <td>Descrição</td>
                <td>Qtde</td>
                <td>Valor</td>
            </tr>
            <?php
                $total = 0;
                for ($i=0; $i < $total_itens; $i++) {
                    $pedido_item = pg_result($res,$i,pedido_item);
                    $qtde        = pg_result($res,$i,qtde);
                    $preco       = pg_result($res,$i,preco);
                    $referencia  = pg_result($res,$i,referencia);
                    $descricao   = pg_result($res,$i,descricao);
                    $pedido_condicao = pg_fetch_result($res, $i, 'condicao');
                    $valor = $preco * $qtde;
                    $total += $valor;
                     $cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
            ?>
                    <tr bgcolor="<?php echo $cor; ?>">
                        <?php if (isFabrica(104)) { //3084076 ?>
                            <input type='hidden' name='pedido_condicao' value='<?=$pedido_condicao?>'>
                        <?php } ?>
                        <td align="center"> <input type="button" value="Excluir" onclick="window.location='<?php echo $PHP_SELF;?>?btn_acao=deletar&pedido=<?php echo $pedido;?>&pedido_item=<?php echo $pedido_item;?>'"> </td>
                        <td> <?php echo $referencia;?> </td>
                        <td> <?php echo $descricao;?> </td>
                        <td align="center"> <?php echo $qtde;?> </td>
                        <?php
                            if (!isFabrica(104)) { // HD-2416482
                        ?>
                                <td align="right"> <?php echo number_format($valor,2,',','.');?> </td>
                        <?php
                            }else{
                        ?>
                                <td align="right"> <?php echo number_format($valor,2,',','.');?> </td>
                        <?php
                            }
                        ?>
                    </tr>
            <?php
                }
            ?>
            <tr class="titulo_coluna">
                <td colspan="4" align="right"> Total </td>
                <td align="right"> <?php echo number_format($total,2,',','.');?> </td>
            </tr>
            <?php if (!isFabrica(104)) { ?>
                <tr>
                    <td  colspan="5" align="center" width="100px" >
                        <label style="position:relative;top:-3px;font-size:10px;font-family:verdana,arial,helvetica,sans-serif; margin-left:-176px;"> Inserir Comprovante de Pagamento: </label>
                        <input type="file" class="frm" name="comprovante_pedido" id="comprovante_pedido"/>
                    </td>
                </tr>
            <?php } ?>
            <tr>
                <?php if($login_fabrica == 104) : ?>
                <td colspan="5" align="center">
                    <input type="hidden" name="pedido" value="<?=$pedido?>">
                    <input type="hidden" id="total_pedidos" value="<?=number_format($total,2,',','.')?>">
                    <?php if($_GET['pedido'] == null):?>
                        <input type="hidden" name="btn_acao" value="atualiza_valor">
                        <input type="submit" id="atualizar_valor" value="Atualizar Valor">
                    <?php else: ?>
                        <input type="hidden" name="btn_acao" value="finalizar">
                        <input type="submit" id="finalizar" value="Finalizar">
                    <?php endif;?>
                </td>
                <?php else:?>
                <td colspan="5" align="center">
                    <input type="hidden" name="btn_acao" value="finalizar">
                    <input type="hidden" name="pedido" value="<?=$pedido?>">
                    <input type="submit" value="Finalizar">
                </td>
                <?php endif;?>
            </tr>

        </table>
        <?php
        }
    }
    echo "</form>";
}
?>

<? include "rodape.php"; ?>
