<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

/**
 * - os_congeladas.php
 *
 * Realiza a pesquisa de OS congeladas pelo admin
 * para efeito de bloquear seu trâmite em relação
 * ao posto autorizado
 *
 * @author William Ap. Brandino
 * @since 2016-12-21
 */
if($login_fabrica == 151) {
    require_once "./os_cadastro_unico/fabricas/151/classes/CancelarPedido.php";
    $cancelaPedidoClass = new CancelarPedido($login_fabrica);
}


function VerificaPedidoSemFaturamento($os)
{
    global $login_fabrica, $con, $login_admin , $msg_erro , $cancelaPedidoClass;
    $sql_busca = "SELECT tbl_faturamento_item.faturamento, tbl_os.os, tbl_os_item.pedido, tbl_pedido_item.pedido_item, tbl_pedido_item.peca, tbl_os_item.qtde as qtde_os_item, tbl_os_item.os_item
        FROM tbl_os
        INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
        INNER JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
        LEFT JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido = tbl_os_item.pedido and tbl_faturamento_item.peca = tbl_os_item.peca
        INNER JOIN tbl_pedido ON tbl_pedido.pedido = tbl_os_item.pedido AND tbl_pedido.fabrica = $login_fabrica
        INNER JOIN tbl_pedido_item on tbl_pedido_item.pedido = tbl_pedido.pedido and tbl_pedido_item.peca = tbl_os_item.peca
        AND tbl_os_item.fabrica_i = $login_fabrica
        AND tbl_faturamento_item.faturamento is null
        WHERE tbl_os.os = $os
        AND tbl_os.fabrica = $login_fabrica";
    $res_busca = pg_query($con, $sql_busca);
    for($i =0; $i<pg_num_rows($res_busca); $i++){
        $pedido = pg_fetch_result($res_busca, $i, pedido);
        $faturamento = pg_fetch_result($res_busca, $i, faturamento);
        $pedido_item = pg_fetch_result($res_busca, $i, pedido_item);
        $peca = pg_fetch_result($res_busca, $i, peca);
        $qtde_os_item = pg_fetch_result($res_busca, $i, qtde_os_item);
        $os_item = pg_fetch_result($res_busca, $i, os_item);

        $aux_motivo = "'Congelamento de OS'";
        $motivo = "Congelamento de OS";

		if($login_fabrica == 151) {
			$retorno_cancelamento_send = $cancelaPedidoClass->cancelaPedidoItem($pedido,$pedido_item,$motivo);

			if(!is_bool($retorno_cancelamento_send)){
				$msg_erro = utf8_decode($retorno_cancelamento_send);
			}else{
				$sql  = "SELECT fn_pedido_cancela_garantia(null,$login_fabrica,$pedido,$peca,$os_item,$aux_motivo,$login_admin)";
			}
		}else{
			$sql  = "SELECT fn_pedido_cancela_garantia(null,$login_fabrica,$pedido,$peca,$os_item,$aux_motivo,$login_admin)";
		}

        $res = pg_query ($con,$sql);
        $msg_erro = pg_last_error($con);
    }
}

/**
 * congelaOs()
 * - Realiza o congelamento / descongelamento
 * da OS selecionada e grava no Auditor Log
 *
 * @param $con Conexão com banco de dados
 * @param $login_fabrica ID da fábrica
 * @param $acao Tipo de ação a se realizar (congelar || descongelar)
 * @param $os Ordem de serviço que sofrerá a alteração
 *
 * @return ação de sucesso
 */
function congelaOs($con,$login_fabrica,$acao,$os)
{
    global $login_login, $msg_erro;
    $hoje = date('d/m/Y');
    $congelar = ($acao == "congelar")
        ? "TRUE"
        : "FALSE";

    $antes = ($acao == "congelar")
        ? array("os"=>$os,"Situacao_os"=>"OS Normal")
        : array("os"=>$os,"Situacao_os"=>"OS Congelada");

    $depois = ($acao == "congelar")
        ? array("Situacao_os"=>"OS Congelada","data_alteracao"=>$hoje)
        : array("Situacao_os"=>"OS Normal","data_alteracao"=>$hoje);
    $novo = ($acao == "congelar")
        ? "descongelar"
        : "congelar";

    $action = "UPDATE";
    pg_query($con,"BEGIN TRANSACTION");

    if($acao == "congelar"){
        VerificaPedidoSemFaturamento($os);
    }

    /*
     * - Verificação de existência
     * na tbl_os_campo_extra.
     * Caso exista, UPDATE
     * senão, INSERT
     */
    $sqlSel = " SELECT  COUNT(1)
                FROM    tbl_os_campo_extra
                WHERE   os      = $os
                AND     fabrica = $login_fabrica
    ";
    $resSel = pg_query($con,$sqlSel);

    $param_adicionais = array("admin" => $login_login, "data" => date("d/m/Y"));
    $param_adicionais = json_encode($param_adicionais);

    if (pg_fetch_result($resSel,0,0) > 0) {
        $sqlUp = "
            UPDATE  tbl_os_campo_extra
            SET     os_bloqueada = $congelar,
            campos_adicionais = '$param_adicionais'
            WHERE   os      = $os
            AND     fabrica = $login_fabrica
        ";
        $resUp = pg_query($con,$sqlUp);
    } else {
        $sqlIns = "
            INSERT INTO tbl_os_campo_extra (
                os,
                fabrica,
                os_bloqueada,
                campos_adicionais
            ) VALUES (
                $os,
                $login_fabrica,
                $congelar,
                '$param_adicionais'
            )
        ";
        $resIns = pg_query($con,$sqlIns);
        $action = "INSERT";
    }

    $sql = "SELECT os FROM tbl_os WHERE os = {$os} AND finalizada IS NULL";
    $res = pg_query($con,$sql);

    if(pg_num_rows($res) > 0){
	    if($acao == "congelar"){
		/*
		 * - Retira a obrigatoriedade de devolução de LGR das peças que foram solicitadas automaticamente e não por um Admin
		*/

		$sql = "UPDATE tbl_os_item SET parametros_adicionais = jsonb_set(tbl_os_item.parametros_adicionais::jsonb,'{bloqueio}','false') 
			FROM tbl_os_produto 
			WHERE tbl_os_item.os_produto = tbl_os_produto.os_produto 
			AND tbl_os_produto.os = {$os} 
			AND tbl_os_item.peca_obrigatoria IS TRUE
			AND tbl_os_item.parametros_adicionais::jsonb->'admin' IS NULL
			AND tbl_os_item.parametros_adicionais::jsonb->'bloqueio' IS NOT NULL";
	    }else{
		/*
		 * - Retornando a obrigatoriedade de devolução de LGR das peças que foram solicitadas automaticamente e não por um Admin
		*/

		$sql = "UPDATE tbl_os_item SET parametros_adicionais = jsonb_set(tbl_os_item.parametros_adicionais::jsonb,'{bloqueio}','true') 
			FROM tbl_os_produto 
			WHERE tbl_os_item.os_produto = tbl_os_produto.os_produto 
			AND tbl_os_produto.os = {$os} 
			AND tbl_os_item.peca_obrigatoria IS TRUE
			AND tbl_os_item.parametros_adicionais::jsonb->'admin' IS NULL
			AND tbl_os_item.parametros_adicionais::jsonb->'bloqueio' IS NOT NULL";
	    }

	    $res = pg_query($con,$sql);
    }

    if (pg_last_error($con)) {
        $erro = pg_last_error($con);
        pg_query($con,"ROLLBACK TRANSACTION");
        return "erro: ".$erro;
    }

	if (strlen($msg_erro) > 0) {
        pg_query($con,"ROLLBACK TRANSACTION");
        return "erro: ".$msg_erro;
    }

    /*
     * - Caso tenha gravado corretamente
     * será gravado no auditor_log a interação
     * realizada
     */
    pg_query($con,"COMMIT TRANSACTION");
    //auditorLog($os,$antes,$depois,"tbl_os","admin/os_congelada.php",$action);
    return json_encode(array("ok" => true,"novo"=>$novo));
}

/**
 * - congelaTodasOs()
 * Realizar o congelamento / descongelamento
 * geral das OS selecionadas mediante busca
 *
 * @param $con Conexão com banco de dados
 * @param $login_fabrica ID da fabrica
 * @param $dados Conjunto de OS selecionadas para congelamento / descongelamento
 *
 * @return Ação de sucesso
 */
function congelaTodasOs($con,$login_fabrica,$acao,$dados)
{
    $novo = ($acao == "congelarTodas")
        ? "congelar"
        : "descongelar";

    foreach ($dados as $os) {
        $retorno = congelaOs($con,$login_fabrica,$novo,$os);

        $retorno = json_decode($retorno,TRUE);

        if (!is_array($retorno)) {
            return "erro2";
        }
        $retorno = "";
    }

    return json_encode(array("ok"=>true));
}

/**
 * - fechaOs()
 * Realiza o fechamento da OS
 *
 * @param $con Conexão com banco de dados
 * @param $login_fabrica ID da fábrica
 * @param $os Ordem de serviço que sofrerá o fechamento
 *
 * @return ação de sucesso
 */
function fechaOs($con,$login_fabrica,$os)
{
    pg_query($con,"BEGIN TRANSACTION");

    $sql = "
        UPDATE  tbl_os
        SET     data_fechamento = CURRENT_DATE,
                finalizada = CURRENT_TIMESTAMP
        WHERE   fabrica = $login_fabrica
        AND     os      = $os
    ";
    $res = pg_query($con,$sql);

    if (pg_last_error($con)) {
        $erro = pg_last_error($con);
        pg_query($con,"ROLLBACK TRANSACTION");
        return "erro: ".$erro;
    }

    /*
     * - Caso tenha fechado corretamente
     * será gravado no auditor_log a interação
     * realizada
     */
    pg_query($con,"COMMIT TRANSACTION");
    auditorLog($os,array("Aberta"),array("Fechada"),"tbl_os","admin/os_congelada.php","UPDATE");
    return json_encode(array("ok" => true));
}


if (filter_input(INPUT_POST,"btn_acao") == "submit") {

    $os                 = filter_input(INPUT_POST,"os");
    $status_os          = filter_input(INPUT_POST,"status_os");
    $pedido             = filter_input(INPUT_POST,"pedido");
    $tipo_pedido        = filter_input(INPUT_POST,"tipo_pedido");
    $data_inicial       = filter_input(INPUT_POST,"data_inicial");
    $data_final         = filter_input(INPUT_POST,"data_final");
    $tipo_data          = filter_input(INPUT_POST,"tipo_data");
    $linhas             = filter_input(INPUT_POST,"linha");
    $familia            = filter_input(INPUT_POST,"familia");
    $produto_referencia = filter_input(INPUT_POST,"produto_referencia");
    $produto_descricao  = filter_input(INPUT_POST,"produto_descricao");
    $peca_referencia    = filter_input(INPUT_POST,"peca_referencia");
    $peca_descricao     = filter_input(INPUT_POST,"peca_descricao");
    $posto_referencia   = filter_input(INPUT_POST,"codigo_posto");
    $posto_descricao    = filter_input(INPUT_POST,"descricao_posto");
    $pais               = filter_input(INPUT_POST,"pais");
    $estado             = filter_input(INPUT_POST,"estado");
    $situacao_os        = filter_input(INPUT_POST,"situacao_os");
    $situacao_pedido    = filter_input(INPUT_POST,"situacao_pedido");
    $centro_distribuicao = $_POST['centro_distribuicao'];

    if(empty($os)){
        if (!strlen($data_inicial) or !strlen($data_final)) {
            $msg_erro["msg"][]    = "Preencha os campos obrigatórios";
            $msg_erro["campos"][] = "data";
        } else {
            list($di, $mi, $yi) = explode("/", $data_inicial);
            list($df, $mf, $yf) = explode("/", $data_final);

            if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
                $msg_erro["msg"][]    = "Data Inválida";
                $msg_erro["campos"][] = "data";
            } else {

                $aux_data_inicial = "{$yi}-{$mi}-{$di}";
                $aux_data_final   = "{$yf}-{$mf}-{$df}";

                if (strtotime($aux_data_inicial."+6 months" ) < strtotime($aux_data_final)) {
                    $msg_erro["msg"][]    = "Intervalo de pesquisa não pode ser no do que 6 meses.";
                    $msg_erro["campos"][] = "data";
                } else {
                    if(strlen($tipo_data) > 0){
                        switch ($tipo_data) {
                            case "data_digitacao":
                            case "data_abertura":
                            case "data_fechamento":
                            case "finalizada":
                                $condData = "\nAND tbl_os.{$tipo_data} BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'";
                                break;
                            case "extrato_geracao":
                                $condData = "\nAND tbl_extrato.data_geracao BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'";
                                break;
                            case "extrato_aprovacao":
                                $condData = "\nAND tbl_extrato.aprovado BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'";
                                break;
                            default:
                                $condData = "\nAND tbl_os.data_abertura BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'";
                                break;
                        }
                    }
                }
            }

            if (!empty($status_os)) {
                $condStatusOs = "\nAND tbl_os.status_checkpoint = $status_os";
            }
            if (!empty($pedido)) {
                $condPedido = "\nAND tbl_pedido.pedido = $pedido";
            }
            if (!empty($tipo_pedido)) {
                $condTipoPedido = "\nAND tbl_pedido.tipo_pedido = $tipo_pedido";
            }
            if (!empty($situacao_pedido)) {
                $condSituacaoPedido = "\nAND tbl_pedido.status_pedido = $situacao_pedido";
            }
            if (count($linhas) > 0) {
                $arrLinhas = implode(",",$linhas);
                $condLinhas = "\nAND tbl_produto.linha IN ($arrLinhas)";
            }
            if (!empty($familia)) {
                $condFamilia = "\nAND tbl_produto.familia = $familia";
            }
            if (!empty($produto_referencia) && !empty($produto_descricao)) {
                $condProduto = "\nAND tbl_produto.referencia = '$produto_referencia'";
            }
            if (!empty($peca_referencia) && !empty($peca_descricao)) {
                $condPeca = "\nAND tbl_peca.referencia = '$peca_referencia'";
            }
            if (!empty($posto_referencia) && !empty($posto_descricao)) {
                $condPosto = "\nAND tbl_posto_fabrica.codigo_posto = '$posto_referencia'";
            }
            if (!empty($estado)) {
                $condUf = "\nAND tbl_posto.estado = '$estado'";
            }

            if (!empty($situacao_os)) {
                switch ($situacao_os) {
                    case "a":
                        $condSituacaoOs = "\nAND tbl_os.data_fechamento IS NULL
                                             AND tbl_os.finalizada      IS NULL
                        ";
                        break;
                    case "c":
                        $condSituacaoOs = "\nAND tbl_os.data_fechamento IS NULL
                                             AND tbl_os.finalizada      IS NULL
                                             AND tbl_os.data_conserto   IS NOT NULL
                        ";
                        break;
                    case "f":
                        $condSituacaoOs = "\nAND tbl_os.data_fechamento IS NOT NULL
                                             AND tbl_os.finalizada      IS NOT NULL
                        ";
                        break;
                    case "cong":
                        $condSituacaoOs = "\nAND tbl_os_campo_extra.os_bloqueada    IS TRUE
                                             AND tbl_os.finalizada                  IS NULL
                        ";
                        break;
                    case "descong":
                        $condSituacaoOs = "\nAND (
                                                    tbl_os_campo_extra.os_bloqueada    IS NULL
                                                 OR tbl_os_campo_extra.os_bloqueada    IS NOT TRUE
                                                 )
                                             AND tbl_os.finalizada                  IS NULL
                        ";
                        break;
                }
            }
        }
    } else {
        $condOs = "\nAND tbl_os.sua_os = '$os'";
    }

    if (count($msg_erro['msg']) == 0) {
        $quinhentos = ($_POST['gerar_excel']) ? " " : " LIMIT 500";

        if($_POST["BuscaTodasIdOs"]){
            $quinhentos = " ";
        }

        if($login_fabrica == 151){
            if($centro_distribuicao != "mk_vazio"){
                $campo_p_adicionais = ",tbl_produto.parametros_adicionais::json->>'centro_distribuicao' AS centro_distribuicao";
                $p_adicionais = " AND tbl_produto.parametros_adicionais::json->>'centro_distribuicao' = '$centro_distribuicao'";
            }            
        }


        $order = " ORDER BY data_abertura ";
        $sqlBusca = "
            SELECT  DISTINCT
                    tbl_os.os                                                                       ,
                    tbl_os.sua_os                                                                  ,
                    tbl_os.finalizada                                                               ,
                    tbl_os_campo_extra.os_bloqueada                             AS os_congelada     ,
                    TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')                  AS data_abertura    ,
                    TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY')                AS data_fechamento  ,
                    tbl_posto_fabrica.codigo_posto || ' - ' || tbl_posto.nome   AS posto            ,
                    tbl_produto.referencia || ' - ' || tbl_produto.descricao    AS produto          ,                    
                    tbl_pedido.pedido
                    $campo_p_adicionais
            FROM    tbl_os
            JOIN    tbl_posto           USING(posto)
            JOIN    tbl_posto_fabrica   USING(posto,fabrica)
       LEFT JOIN    tbl_os_campo_extra  USING(os)
       LEFT JOIN    tbl_os_produto      USING(os)
       LEFT JOIN    tbl_produto         ON tbl_produto.produto  = tbl_os_produto.produto
       LEFT JOIN    tbl_os_item         USING(os_produto)
       LEFT JOIN    tbl_peca            USING(peca)
       LEFT JOIN    tbl_pedido_item     USING(pedido_item)
       LEFT JOIN    tbl_pedido          ON tbl_pedido.pedido    = tbl_pedido_item.pedido
            WHERE   tbl_os.fabrica = $login_fabrica
            $condOs
            $condData
            $p_adicionais
            $condStatusOs
            $condSituacaoOs
            $condPedido
            $condTipoPedido
            $condSituacaoPedido
            $condLinhas
            $condFamilia
            $condProduto
            $condPeca
            $condPosto
            $condUf            
            $order
            $quinhentos            
        ";

        //die(nl2br($sqlBusca));

        $resBusca   = pg_query($con,$sqlBusca);

        $count      = pg_num_rows($resBusca);
        if ($_POST["BuscaTodasIdOs"]) {
            $todasOs = array();
            while ($resultado = pg_fetch_object($resBusca)) {
                $sua_os         = $resultado->os;
                $todasOs[] = $sua_os;
            }

            exit(json_encode($todasOs));
        }

        if ($_POST["gerar_excel"]) {
            if (pg_num_rows($resBusca) > 0) {

                $data = date("d-m-Y-H:i");

                $fileName = "relatorio_os_congeladas-{$login_fabrica}-{$data}.xls";

                $file = fopen("/tmp/{$fileName}", "w");

                if($login_fabrica == 151){
                    $thead = "OS;Posto;Produto;Pedido;Data Abertura;Status OS;Centro Distribuicao\n";
                }else{
                    $thead = "OS;Posto;Produto;Pedido;Data Abertura\n";
                }

                fwrite($file, $thead);

                while ($resultado = pg_fetch_object($resBusca)) {
                    $sua_os         = $resultado->os;
                    $posto          = $resultado->posto;
                    $produto        = $resultado->produto;
                    $pedido         = $resultado->pedido;
                    $data_abertura  = $resultado->data_abertura;

                    if($login_fabrica == 151){
                        $os_bloqueada   = $resultado->os_congelada;
                        $finalizada     = $resultado->finalizada;

                        if($os_bloqueada == 't' and empty($finalizada)){
                            $os_bloqueada= "Congelada";
                        }elseif($os_bloqueada != 't' and empty($finalizada)){
                            $os_bloqueada= "Descongelada";
                        }
                                                               
                        if($resultado->centro_distribuicao == "mk_nordeste"){
                            $campo_p_adicionais = "MK Nordeste";
                        }else if($resultado->centro_distribuicao == "mk_sul") {
                            $campo_p_adicionais = "MK Sul";    
                        } else{
                            $campo_p_adicionais = "&nbsp;";    
                        }                  
                
                        $tbody = "$sua_os;".utf8_encode($posto).";".utf8_encode($produto).";$pedido;$data_abertura;$os_bloqueada;$campo_p_adicionais\n";
                    }else{
                        $tbody = "$sua_os;".utf8_encode($posto).";".utf8_encode($produto).";$pedido;$data_abertura\n";
                    }
                    fwrite($file, $tbody);
                }

                fclose($file);

                if (file_exists("/tmp/{$fileName}")) {
                    system("mv /tmp/{$fileName} xls/{$fileName}");

                    echo "xls/{$fileName}";
                }
            }
            exit;
        }
    }
}

$ajax = filter_input(INPUT_POST,"ajax",FILTER_VALIDATE_BOOLEAN);

if ($ajax) {
    $acao       = filter_input(INPUT_POST,"acao");
    $ajax_os    = filter_input(INPUT_POST,"os");
    $dados      = filter_input(INPUT_POST,"dados",FILTER_UNSAFE_RAW,FILTER_REQUIRE_ARRAY);

    switch($acao) {
        case "congelar":
        case "descongelar":
            echo congelaOs($con,$login_fabrica,$acao,$ajax_os);
            break;
        case "congelarTodas":
        case "descongelarTodas":
            echo congelaTodasOs($con,$login_fabrica,$acao,$dados);
            break;
        case "fechar":
            echo fechaOs($con,$login_fabrica,$ajax_os);
            break;
    }
    exit;
}

$layout_menu = "callcenter";
$title= "CONSULTA DAS ORDENS DE SERVIÇO CONGELADAS";
include "cabecalho_new.php";
$plugins = array(
    "datepicker",
    "mask",
    "alphanumeric",
    "dataTable",
    "shadowbox",
    "multiselect"
);
include("plugin_loader.php");

?>
<script type="text/javascript">
$(function() {
    $.datepickerLoad(Array("data_final", "data_inicial"));
    $.autocompleteLoad(Array("produto","peca", "posto"), Array("produto","peca","posto"), null, "../");
    $("#os").numeric({ allow: "-"});
    $("#pedido").numeric();
    Shadowbox.init();

    $("#linha").multiselect({
        selectedText: "selecionados # de #"
    });

    $("span[rel=lupa]").click(function () {
        $.lupa($(this));
    });

    $("#arrOsTodas").click(function(){
        var linhas = dataTableGlobal._fnGetTrNodes();
        if ($("#arrOsTodas").prop("checked") == true) {
            $(linhas).find("input[type=checkbox]").prop("checked",true);
            /*$(".arrOs").each(function(k,val){
                $(val).prop({"checked":true});
            });*/
        } else {
             $(linhas).find("input[type=checkbox]").prop("checked",false);
            /*$(".arrOs").each(function(k,val){
                $(val).prop({"checked":false});
            });*/
        }
    });

    $("button.acao_fechar").click(function(e){
        e.preventDefault();
        var dados = $(this).attr("id");
        var separa = dados.split("_");
        var acao = separa[0];
        var os = separa[1];

        if (confirm("Deseja realmente fechar esta OS?")) {
            $.ajax({
                url:"os_congeladas.php",
                type:"POST",
                dataType:"JSON",
                data:{
                    ajax:true,
                    acao:acao,
                    os:os
                },
                beforeSend:function(){
                    $("button[id$=_"+os+"]").hide();
                }
            })
            .done(function(data){
                if (data.ok) {
                    alert("A Ordem de Serviço foi finalizada com sucesso.");
                    $("#tr_"+os).detach();
                }
            })
            .fail(function(){
                $("button[id$=_"+os+"]").show();
            });
        }
    });

    $("button.acao_congelar").click(function(e){
        e.preventDefault();
        var dados = $(this).attr("id");
        var separa = dados.split("_");
        var acao = separa[0];
        var os = separa[1];

        $.ajax({
            url:"os_congeladas.php",
            type:"POST",
            dataType:"JSON",
            data:{
                ajax:true,
                acao:acao,
                os:os
            },
            beforeSend:function(){
                $("#"+acao+"_"+os).hide();
            }
        })
        .done(function(data){
            if (data.ok) {
                $("#"+data.novo+"_"+os).show();
            }
        })
        .fail(function(){
            $(this).show();
        });
    });

    function BuscaTodasIdOs(){
        var json = $.parseJSON($("#arrayToJson").val());
        json["BuscaTodasIdOs"] = true;

        var data;

        $.ajax({
            url: "<?=$_SERVER['PHP_SELF']?>",
            type: "POST",
            async: false,
            data: json,
            success: function (response) {
                 data = $.parseJSON(response);
            }
        });
        return data;
    }

    $("button.acao_congelar_todas").click(function(e){
        e.preventDefault();
        var dados = [];
        var acao  = $(this).attr("id");
        var full  = $(this).attr("class");

        if(full.match(/full/)){
          full = 't';
        }else{
            full = 'f';
        }

        if(full == 'f'){
            $(".arrOs").each(function(k,val){
                if ($(val).is(":checked")) {
                    dados.push($(this).val());
                }
            });
        }else{
            dados = BuscaTodasIdOs();
        }

        var qtde_os = dados.length;
        if(acao == "descongelarTodas"){
            var acao_msg = " descongelada(s) ";
            msg = " Foram"+ acao_msg + qtde_os +" O.S.";
        }else if( acao == 'congelarTodas'){
            var acao_msg = " congelada(s) ";
            msg = " Foram"+ acao_msg + qtde_os +" O.S.";
        }

        $.ajax({
            url:"os_congeladas.php",
            type:"POST",
            dataType:"JSON",
            data:{
                ajax:true,
                acao:acao,
                dados:dados
            },
            beforeSend:function(){
                $("button.acao_congelar_todas").hide();
                $("button.acao_congelar").prop({"disabled":"disabled"});
                $("button.acao_fechar").prop({"disabled":"disabled"});
                $("tfoot > tr > td").html("<div class='alert alert-block text-center'>Por Favor, aguarde requisição</div>");
            }
        })
        .done(function(data){
            if (data.ok) {
                alert("Ação Realizada com sucesso!\nA página será reiniciada.\n"+msg);
                location.href='os_congeladas.php';
            }
        })
        .fail(function(){
            alert("Não foi possível realizar a requisição");
            $("tfoot > tr > td > div").hide();
            $("button.acao_fechar").prop({"disabled":""});
            $("button.acao_congelar").prop({"disabled":""});
            $("button.acao_congelar_todas").show();
        });
    });
});

function retorna_posto (retorno){
    $("#codigo_posto").val(retorno.codigo);
    $("#descricao_posto").val(retorno.nome);
}

function retorna_produto (retorno) {
    $("#produto").val(retorno.produto);
    $("#produto_referencia").val(retorno.referencia);
    $("#produto_descricao").val(retorno.descricao);
}

function retorna_peca (retorno) {
    $("#peca").val(retorno.peca);
    $("#peca_referencia").val(retorno.referencia);
    $("#peca_descricao").val(retorno.descricao);
}
</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
<div class="alert alert-error">
    <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
</div>
<?php
}
?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
    <div class ='titulo_tabela'>Parametros de Pesquisa </div>
    <br/>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("os", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='os'>OS</label>
                <div class='controls controls-row'>
                    <div class='span6'>
                        <input type="text" name="os" id="os" class='span12 numeric' value= "<?=$os?>">
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='status_os'>Status da OS</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <select name="status_os" id="status_os">
                            <option value="">ESCOLHA</option>
<?php
                        $condicao_status = '0,1,2,3,4,8,9';
                        $sql = "SELECT  status_checkpoint AS status_os,
                                        descricao
                                FROM    tbl_status_checkpoint
                                WHERE   status_checkpoint IN ($condicao_status)
                          ORDER BY      status_checkpoint
                        ";
                        $res = pg_query($con,$sql);

                        while ($condicoes = pg_fetch_object($res)) {
?>
                            <option value="<?=$condicoes->status_os?>" <?=($status_os == $condicoes->status_os) ? "SELECTED" : ""?>><?=$condicoes->descricao?></option>
<?php
                        }
?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group '>
                <label class='control-label' for='pedido'>Pedido</label>
                <div class='controls controls-row'>
                    <div class='span6'>
                        <input type="text" name="pedido" id="pedido" class='span12' value= "<?=$pedido?>">
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='tipo_pedido'>Tipo Pedido</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <select name="status_pedido" id="status_pedido">
                            <option value="">ESCOLHA</option>
<?php
                            $sql = "SELECT  tbl_tipo_pedido.tipo_pedido,
                                            tbl_tipo_pedido.descricao
                                    FROM    tbl_tipo_pedido
                                    WHERE   tbl_tipo_pedido.fabrica = $login_fabrica
                              ORDER BY tbl_tipo_pedido.descricao;
                            ";
                            $res = pg_query($con,$sql);

                            while ($tipos = pg_fetch_object($res)) {
?>
                            <option value="<?=$tipos->tipo_pedido?>" <?=($status_pedido == $tipos->tipo_pedido) ? "SELECTED" : ""?>><?=$tipos->descricao?></option>
<?php
                            }
?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_inicial'>Data Inicial</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <h5 class='asteristico'>*</h5>
                            <input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_final'>Data Final</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <h5 class='asteristico'>*</h5>
                            <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12 text-center' value= "<?=$data_final?>">
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span8'>
            Data de Referência
        </div>
        <div class='span2'></div>
    </div>

    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span2'>
            <label class="radio">
                <input type="radio" name="tipo_data" id="optionsRadios1" value="data_digitacao" <?if($tipo_data=="data_digitacao") echo "checked";?>>
                Digitação
            </label>
        </div>
        <div class='span3'>
            <label class="radio">
                <input type="radio" name="tipo_data" id="optionsRadios1" value="data_abertura" <?if($tipo_data=="data_abertura") echo "checked";?>>
                Abertura
            </label>
        </div>
        <div class='span3'>
                <label class="radio">
                <input type="radio" name="tipo_data" id="optionsRadios1" value="data_fechamento" <?if($tipo_data=="data_fechamento" or $tipo_data=="") echo "checked";?> >
                Fechamento
            </label>
        </div>
        <div class='span2'></div>
    </div>

    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span2'>
            <label class="radio">
                <input type="radio" name="tipo_data" id="optionsRadios1" value="finalizada" <?if($tipo_data=="finalizada") echo "checked";?> >
                Finalizada
            </label>
        </div>
        <div class='span3'>
            <label class="radio">
                <input type="radio" name="tipo_data" id="optionsRadios1" value="extrato_geracao" <?if($tipo_data=="extrato_geracao") echo "checked";?>>
                Geração de Extrato
            </label>
        </div>
        <div class='span3'>
            <label class="radio">
                <input type="radio" name="tipo_data" id="optionsRadios1" value="extrato_aprovacao" <?if($tipo_data=="extrato_aprovacao") echo "checked";?>>
                Aprovação do Extrato
            </label>
        </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='linha'>Linha</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <?php
                        $sql_linha = "SELECT
                        linha,
                        nome
                        FROM tbl_linha
                        WHERE tbl_linha.fabrica = $login_fabrica
                        ORDER BY tbl_linha.nome ";
                        $res_linha = pg_query($con, $sql_linha); ?>
                        <select name="linha[]" id="linha" multiple="multiple" class='span12'>
                        <?php

                        $selected_linha = array();
                        foreach (pg_fetch_all($res_linha) as $key) {
                            if(isset($linhas)){
                                foreach ($linhas as $id) {
                                    if ( isset($linhas) && ($id == $key['linha']) ){
                                        $selected_linha[] = $id;
                                    }
                                }
                            } ?>

                            <option value="<?=$key['linha']?>" <?php if( in_array($key['linha'], $selected_linha)) echo "SELECTED"; ?> >

                            <?=$key['nome']?>

                            </option>
                        <?php } ?>
                        </select>

                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='familia'>Família</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <select name="familia" id="familia">
<?php
                        $sql = "SELECT  *
                            FROM    tbl_familia
                            WHERE   tbl_familia.fabrica = $login_fabrica
                            ORDER BY tbl_familia.descricao;";
                        $res = pg_query ($con,$sql);

                        if (pg_num_rows($res) > 0) {
?>
                            <option value=''>ESCOLHA</option>
<?php
                            for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
                                $aux_familia   = trim(pg_fetch_result($res,$x,familia));
                                $aux_descricao = trim(pg_fetch_result($res,$x,descricao));
?>
                            <option value='<?=$aux_familia?>'<?=($familia == $aux_familia) ? " SELECTED " : ""?>><?=$aux_descricao?></option>
<?php
                            }
                        }
?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>

    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='produto_referencia'>Ref. Produto</label>
                <div class='controls controls-row'>
                    <div class='span10 input-append'>
                        <input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<?=$produto_referencia?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                        <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='produto_descricao'>Descrição Produto</label>
                <div class='controls controls-row'>
                    <div class='span10 input-append'>
                        <input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<?=$produto_descricao?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='peca_referencia'>Ref. Peça</label>
                <div class='controls controls-row'>
                    <div class='span10 input-append'>
                        <input type="text" id="peca_referencia" name="peca_referencia" class='span12' maxlength="20" value="<?=$peca_referencia?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                        <input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='peca_descricao'>Descrição Peça</label>
                <div class='controls controls-row'>
                    <div class='span10 input-append'>
                        <input type="text" id="peca_descricao" name="peca_descricao" class='span12' value="<?=$peca_descricao ?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='pais'>País</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <select name="pais" id="pais">
                            <?
                            $sql = "SELECT  *
                                    FROM    tbl_pais
                                    where america_latina is TRUE
                                    ORDER BY tbl_pais.nome;";
                            $res = pg_query ($con,$sql);

                            if (pg_num_rows($res) > 0) {
                                if(strlen($pais) == 0 ) $pais = 'BR';

                                for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
                                    $aux_pais  = trim(pg_fetch_result($res,$x,pais));
                                    $aux_nome  = trim(pg_fetch_result($res,$x,nome));

                                    echo "<option value='$aux_pais'";
                                    if ($pais == $aux_pais){
                                        echo " SELECTED ";
                                    }
                                    echo ">$aux_nome</option>\n";
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='estado'>Por Região</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <select name="estado" id="estado">
                            <option value=""   <? if (strlen($estado) == 0)    echo " selected "; ?>>TODOS OS ESTADOS</option>
                            <option value="AC" <? if ($estado == "AC") echo " selected "; ?>>AC - Acre</option>
                            <option value="AL" <? if ($estado == "AL") echo " selected "; ?>>AL - Alagoas</option>
                            <option value="AM" <? if ($estado == "AM") echo " selected "; ?>>AM - Amazonas</option>
                            <option value="AP" <? if ($estado == "AP") echo " selected "; ?>>AP - Amapá</option>
                            <option value="BA" <? if ($estado == "BA") echo " selected "; ?>>BA - Bahia</option>
                            <option value="CE" <? if ($estado == "CE") echo " selected "; ?>>CE - Ceará</option>
                            <option value="DF" <? if ($estado == "DF") echo " selected "; ?>>DF - Distrito Federal</option>
                            <option value="ES" <? if ($estado == "ES") echo " selected "; ?>>ES - Espírito Santo</option>
                            <option value="GO" <? if ($estado == "GO") echo " selected "; ?>>GO - Goiás</option>
                            <option value="MA" <? if ($estado == "MA") echo " selected "; ?>>MA - Maranhão</option>
                            <option value="MG" <? if ($estado == "MG") echo " selected "; ?>>MG - Minas Gerais</option>
                            <option value="MS" <? if ($estado == "MS") echo " selected "; ?>>MS - Mato Grosso do Sul</option>
                            <option value="MT" <? if ($estado == "MT") echo " selected "; ?>>MT - Mato Grosso</option>
                            <option value="PA" <? if ($estado == "PA") echo " selected "; ?>>PA - Pará</option>
                            <option value="PB" <? if ($estado == "PB") echo " selected "; ?>>PB - Paraíba</option>
                            <option value="PE" <? if ($estado == "PE") echo " selected "; ?>>PE - Pernambuco</option>
                            <option value="PI" <? if ($estado == "PI") echo " selected "; ?>>PI - Piauí</option>
                            <option value="PR" <? if ($estado == "PR") echo " selected "; ?>>PR - Paraná</option>
                            <option value="RJ" <? if ($estado == "RJ") echo " selected "; ?>>RJ - Rio de Janeiro</option>
                            <option value="RN" <? if ($estado == "RN") echo " selected "; ?>>RN - Rio Grande do Norte</option>
                            <option value="RO" <? if ($estado == "RO") echo " selected "; ?>>RO - Rondônia</option>
                            <option value="RR" <? if ($estado == "RR") echo " selected "; ?>>RR - Roraima</option>
                            <option value="RS" <? if ($estado == "RS") echo " selected "; ?>>RS - Rio Grande do Sul</option>
                            <option value="SC" <? if ($estado == "SC") echo " selected "; ?>>SC - Santa Catarina</option>
                            <option value="SE" <? if ($estado == "SE") echo " selected "; ?>>SE - Sergipe</option>
                            <option value="SP" <? if ($estado == "SP") echo " selected "; ?>>SP - São Paulo</option>
                            <option value="TO" <? if ($estado == "TO") echo " selected "; ?>>TO - Tocantins</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>

    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='codigo_posto'>Código Posto</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                            <input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<?=$codigo_posto?>" >
                            <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                            <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='descricao_posto'>Nome Posto</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<?=$descricao_posto?>" >&nbsp;
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>

    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='situacao_os'>Situação OS</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <select name="situacao_os" id="situacao_os">
                            <option value="">ESCOLHA</option>
                            <option value="a" <?php if($situacao_os == 'a') echo "SELECTED"; ?>>Abertas</option>
                            <option value="c" <?php if($situacao_os == 'c') echo "SELECTED"; ?>>Consertadas</option>
                            <option value="cong" <?php if($situacao_os == 'cong') echo "SELECTED"; ?>>Congeladas</option>
                            <option value="descong" <?php if($situacao_os == 'descong') echo "SELECTED"; ?>>Descongeladas</option>
                            <option value="f" <?php if($situacao_os == 'f') echo "SELECTED"; ?>>Fechadas</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='situacao_pedido'>Situação Pedido</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <select name="situacao_pedido" id="situacao_pedido">
                            <option value="">ESCOLHA</option>
<?php
                        $situacao_pedido = '1,4,5,14';
                        $sql = "SELECT  status_pedido AS situacao_pedido,
                                        descricao
                                FROM    tbl_status_pedido
                                WHERE   status_pedido IN ($situacao_pedido)
                          ORDER BY      status_pedido
                        ";
                        $res = pg_query($con,$sql);

                        while ($condicoes = pg_fetch_object($res)) {
?>
                            <option value="<?=$condicoes->situacao_pedido?>" <?=($situacao_pedido == $condicoes->situacao_pedido) ? "SELECTED" : ""?>><?=$condicoes->descricao?></option>
<?php
                        }
?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <?php if($login_fabrica == 151){ ?>   
        <div class='row-fluid'>
            <div class='span2'></div>                                  
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='centro_distribuicao'>Centro Distribuição</label>
                    <div class='controls controls-row'>
                        <div class='span12 input-append'>
                            <select name="centro_distribuicao" id="centro_distribuicao">
                                <option value="mk_vazio" name="mk_vazio" <?php echo ($centro_distribuicao == "mk_vazio") ? "SELECTED" : ""; ?>>ESCOLHA</option>
                                <option value="mk_nordeste" name="mk_nordeste" <?php echo ($centro_distribuicao == "mk_nordeste") ? "SELECTED" : ""; ?>>MK Nordeste</option>
                                <option value="mk_sul" name="mk_sul" <?php echo ($centro_distribuicao == "mk_sul") ? "SELECTED" : ""; ?>>MK Sul</option>    
                            </select>
                        </div>                          
                    </div>                      
                </div>
            </div>
        </div>
    <?php } ?>
    <p><br />
        <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
    </p><br />
</form>

<?php
if($btn_acao == "submit"){
    if(strlen ($msg_erro["msg"]) == 0  && $count > 0){
        if($count == 500){
?>
<div class="alert alert-block text-center" style="width:850px;margin:0 auto;">
    A busca foi condicionada a mostrar apenas 500 resultados na tela. <br />Procure ser mais objetivo nos requisitos de busca para melhor filtragem de dados.
</div>
<br />
<?php
            }
?>
</div>
<table id="resultado_os" class = 'table table-striped table-bordered table-hover table-large'>
    <thead>
        <tr class = 'titulo_coluna'>
            <th>
<?php
        if (!empty($situacao_os) && in_array($situacao_os,array('cong','descong'))) {
?>
                <input type="checkbox" id="arrOsTodas" nome="arrOsTodas" value="todas" />
<?php
            }
?>
            </th>
            <th>OS</th>
            <th>Posto</th>
            <th>Produto</th>
            <th>Pedido</th>
            <th>Data Abertura</th>
            <?php if($login_fabrica == 151) { ?>
                <th>Centro Distribuição</th>
            <? } ?>  
            <th>Ações</th>          
        </tr>
    </thead>
    <tbody>
<?php
        while ($resultado = pg_fetch_object($resBusca)) {
?>
        <tr id="tr_<?=$resultado->os?>">
            <td class="tac">
<?php
            if (!empty($situacao_os) && in_array($situacao_os,array('cong','descong'))) {
?>
                <input type="checkbox" class="arrOs" name="os[]" value="<?=$resultado->os?>" />
<?php
            }
?>
            </td>
            <td><a href="os_press.php?os=<?=$resultado->os?>" target="_blank"><?=$resultado->sua_os?></a></td>
            <td><?=$resultado->posto?></td>
            <td><?=$resultado->produto?></td>
            <td><?=$resultado->pedido?></td>
            <td><?=$resultado->data_abertura?></td>            
            <?php
                if($login_fabrica == 151){
                    echo "<td>";                    
                    if($resultado->centro_distribuicao == "mk_nordeste"){
                        $campo_p_adicionais = "MK Nordeste";
                    }else if($resultado->centro_distribuicao == "mk_sul") {
                        $campo_p_adicionais = "MK Sul";    
                    } else{
                        $campo_p_adicionais = "&nbsp;";    
                    }
                    echo $campo_p_adicionais . "</td>";
                }
            ?>                        
            <td style="width:200px;" class="tac">
<?php
            $style_congelar     = "display:none;";
            $style_descongelar  = "display:none;";
            $style_fechar       = "display:none;";

            if (strlen($resultado->data_fechamento) == 0) {
                if ($resultado->os_congelada == 't') {
                    $style_congelar     = "display:none;";
                    $style_descongelar  = "display:inline;";
                } else {
                    $style_congelar     = "display:inline;";
                    $style_descongelar  = "display:none;";
                }
                $style_fechar = "display:inline;";
            }

            /*HD - 4250799*/
            if (in_array($login_fabrica, array(151)) && strlen($resultado->data_fechamento) > 0) {
                if ($resultado->os_congelada == 't') {
                    $style_congelar     = "display:none;";
                    $style_descongelar  = "display:inline;";
                } else {
                    $style_congelar     = "display:inline;";
                    $style_descongelar  = "display:none;";
                }
            }
?>
                <button style="<?=$style_congelar?>"    class='btn btn-small btn-success acao_congelar' id="congelar_<?=$resultado->os?>">Congelar</button>
                <button style="<?=$style_descongelar?>" class='btn btn-small btn-warning acao_congelar' id="descongelar_<?=$resultado->os?>">Descongelar</button>
                <button style="<?=$style_fechar?>"      class='btn btn-small btn-danger acao_fechar'    id="fechar_<?=$resultado->os?>">Fechar</button>

            </td>
        </tr>
<?php
        }
?>
    </tbody>
    <tfoot>
<?php
        switch ($situacao_os) {
            case "cong":
?>
        <tr>
            <td colspan="7" class="tac"><button class='btn btn-small btn-warning acao_congelar_todas' id="descongelarTodas">Descongelar Selecionadas</button>
                <?php
                    $arrayToJson = arrayToJson($_POST);
                ?>
                <input type="hidden" id="arrayToJson" value='<?=$arrayToJson?>'>
                <button class='btn btn-small btn-warning acao_congelar_todas full' id="descongelarTodas">Descongelar Todas</button>
            </td>
        </tr>
<?php
                break;
            case "descong":
?>
        <tr>
            <td colspan="7" class="tac">
                <button class='btn btn-small btn-success acao_congelar_todas' id="congelarTodas">Congelar Selecionadas</button>
                <?php
                    $arrayToJson = arrayToJson($_POST);
                ?>
                <input type="hidden" id="arrayToJson" value='<?=$arrayToJson?>'>
                <button class='btn btn-small btn-success acao_congelar_todas full' id="congelarTodas">Congelar Todas</button>

            </td>
        </tr>
<?php
                break;
                case "a":
?>
        <tr>
            <td colspan="7" class="tac">
                <?php
                    $arrayToJson = arrayToJson($_POST);
                ?>
                <input type="hidden" id="arrayToJson" value='<?=$arrayToJson?>'>
                <button class='btn btn-small btn-success acao_congelar_todas full' id="congelarTodas">Congelar Todas</button>
            </td>
        </tr>
<?php
                break;

            default:
?>

<?php
                break;
        }
?>
    </tfoot>
</table>
<?php
        if ($count > 50) {
?>
<script type="text/javascript">
    $.dataTableLoad({
        table: "#resultado_os",
        type:"basic",
        sorting:false
    });
</script>
<?php
        }

        $jsonPOST = excelPostToJson($_POST);
?>
<br />

<div id='gerar_excel' class="btn_excel">
    <input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
    <span><img src='imagens/excel.png' /></span>
    <span class="txt">Gerar Arquivo Excel</span>
</div>
<?php
    } else {
?>
<div class="container">
    <div class="alert">
            <h4>Nenhum resultado encontrado</h4>
    </div>
</div>
<?php
    }
}
include 'rodape.php';
?>
