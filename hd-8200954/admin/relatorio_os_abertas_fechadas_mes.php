<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["exec_relatorio_os"]) {
    $dados_pesquisa = $_POST["dados_pesquisa"];
    $mes_pesquisa   = $_POST["mes_pesquisa"];
    $ano_pesquisa   = $_POST["ano_pesquisa"];
    $tipo_pesquisa  = $_POST["tipo_pesquisa"];
    $dados_pesquisa = json_decode($dados_pesquisa, true);
    $data_inicial   = $dados_pesquisa["data_inicial"];
    $data_final     = $dados_pesquisa["data_final"];
    $produto        = $dados_pesquisa["produto"];
    $posto          = $dados_pesquisa["posto"];
    $mae_filha      = $_POST['mae_filha'];

    $tbl_aux = "tbl_os";
    $considera_revenda = false;
    if ($mae_filha == "mae") {
        $considera_revenda = true;
        $tbl_aux = "tbl_os_revenda";
    } else if ($mae_filha == "filha") {
        $join_revenda = "JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os
                         AND tbl_os_campo_extra.os_revenda IS NOT NULL";
    }

    if (!empty($produto)){
        $cond_produto = "AND tbl_produto.produto = $produto";
    }

    if (!empty($posto)){
        $cond_posto = "AND tbl_posto.posto = $posto";
    }

    if (!empty($mes_pesquisa) AND $tipo_pesquisa != "consertada"){
        $cond_mes_pesquisa = "AND DATE_PART('month', {$tbl_aux}.data_abertura) = $mes_pesquisa";
        $cond_ano_pesquisa = "AND DATE_PART('year', {$tbl_aux}.data_abertura) = $ano_pesquisa";
    }

    if (!empty($tipo_pesquisa)){
        if ($tipo_pesquisa == "consertada"){
            if (!$considera_revenda) {
                $cond_tipo_pesquisa = "AND tbl_os.data_conserto IS NOT NULL";
                $cond_consertada = "AND DATE_PART('year', tbl_os.data_conserto) = $ano_pesquisa AND DATE_PART('month', tbl_os.data_conserto) = $mes_pesquisa ";
            } else {
                $cond_tipo_pesquisa = "AND tbl_os_revenda.data_fechamento IS NOT NULL";
                $cond_consertada = "AND DATE_PART('year', tbl_os_revenda.data_fechamento) = $ano_pesquisa AND DATE_PART('month', tbl_os_revenda.data_fechamento) = $mes_pesquisa ";
            }
            
        }
    }

    if (!$considera_revenda) {
        $sql = "
            SELECT
                TO_CHAR(tbl_os.data_abertura, 'DD/MM/YYYY') AS data_abertura,
                TO_CHAR(tbl_os.data_conserto, 'DD/MM/YYYY') AS data_conserto,
                tbl_os.sua_os,
                tbl_os.consumidor_nome,
                tbl_produto.descricao AS produto_descricao,
                tbl_produto.referencia AS produto_referencia,
                tbl_tipo_atendimento.descricao AS tipo_atendimento,
                tbl_posto.nome AS posto_nome
            FROM tbl_os
            JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
            JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
            LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = $login_fabrica
            LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = $login_fabrica
            {$join_revenda}
            WHERE tbl_os.fabrica = $login_fabrica
            AND tbl_os.data_abertura BETWEEN '{$data_inicial}' AND '{$data_final}'
            AND tbl_os.cancelada IS NULL
            AND tbl_os.excluida IS NOT TRUE
            $cond_posto
            $cond_produto
            $cond_tipo_pesquisa
            $cond_mes_pesquisa
            $cond_consertada
            $cond_ano_pesquisa 
            ORDER BY tbl_os.os, tbl_os.consumidor_nome";
    } else {
        $sql = "
            SELECT
                TO_CHAR(tbl_os_revenda.data_abertura, 'DD/MM/YYYY') AS data_abertura,
                TO_CHAR(tbl_os_revenda.data_fechamento, 'DD/MM/YYYY') AS data_conserto,
                tbl_os_revenda.sua_os,
                tbl_os_revenda.consumidor_nome,
                tbl_tipo_atendimento.descricao AS tipo_atendimento,
                tbl_posto.nome AS posto_nome
            FROM tbl_os_revenda
            JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os_revenda.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
            JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
            LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os_revenda.tipo_atendimento AND tbl_tipo_atendimento.fabrica = $login_fabrica
            WHERE tbl_os_revenda.fabrica = $login_fabrica
            AND tbl_os_revenda.data_abertura BETWEEN '{$data_inicial}' AND '{$data_final}'
            $cond_posto
            $cond_consertada
            $cond_tipo_pesquisa
            $cond_mes_pesquisa
            $cond_ano_pesquisa
            ORDER BY os_revenda, consumidor_nome";
    }
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0){
        $dados = pg_fetch_all($res);
        $dados = array_map(function($t) {
            $t['consumidor_nome']   = utf8_encode($t['consumidor_nome']);
            $t['produto_descricao'] = utf8_encode($t['produto_descricao']);
            $t['tipo_atendimento']  = utf8_encode($t['tipo_atendimento']);
            $t['posto_nome']        = utf8_encode($t['posto_nome']);
            return $t;
        }, $dados);
        exit(json_encode(array('dados' => $dados)));
    }else{
        exit(json_encode(array('error' => "ok")));
    }
    exit;
}

if ($_POST["btn_acao"] == "submit") {
    $data_inicial       = $_POST['data_inicial'];
    $data_final         = $_POST['data_final'];
    $produto_referencia = $_POST['produto_referencia'];
    $produto_descricao  = $_POST['produto_descricao'];
    $codigo_posto       = $_POST['codigo_posto'];
    $descricao_posto    = $_POST['descricao_posto'];
    $dados_pesquisa     = array();
    $cond_pesquisa_produto = "AND (
                (UPPER(referencia) = UPPER('{$produto_referencia}'))
                OR
                (UPPER(descricao) = UPPER('{$produto_descricao}'))
            )";

    $mae_filha      = $_POST['mae_filha'];

    $tbl_aux = "tbl_os";
    $considera_revenda = false;
    if ($mae_filha == "mae") {
        $considera_revenda = true;
        $tbl_aux = "tbl_os_revenda";
    } else if ($mae_filha == "filha") {
        $join_revenda = "JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = o.os
                         AND tbl_os_campo_extra.os_revenda IS NOT NULL";
    }

    if (strlen($produto_referencia) > 0 or strlen($produto_descricao) > 0){
        $sql = "SELECT produto
                FROM tbl_produto
                WHERE fabrica_i = {$login_fabrica}
                $cond_pesquisa_produto
                ";
        $res = pg_query($con ,$sql);

        if (!pg_num_rows($res)) {
            $msg_erro["msg"][]    = "Produto não encontrado";
            $msg_erro["campos"][] = "produto";
        } else {
            $produto = pg_fetch_result($res, 0, "produto");
            $dados_pesquisa["produto"] = $produto;
        }
    }
    
    if (strlen($codigo_posto) > 0 or strlen($descricao_posto) > 0){
        $sql = "SELECT tbl_posto_fabrica.posto
                FROM tbl_posto
                JOIN tbl_posto_fabrica USING(posto)
                WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
                AND (
                    (UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}'))
                    OR
                    (TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('{$descricao_posto}'), 'LATIN-9'))
                )";
        $res = pg_query($con ,$sql);

        if (!pg_num_rows($res)) {
            $msg_erro["msg"][]    = "Posto não encontrado";
            $msg_erro["campos"][] = "posto";
        } else {
            $posto = pg_fetch_result($res, 0, "posto");
            $dados_pesquisa["posto"] = $posto;
        }
    }

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

            $dados_pesquisa["data_inicial"] = $aux_data_inicial;
            $dados_pesquisa["data_final"] = $aux_data_final;

            if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
                $msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
                $msg_erro["campos"][] = "data";
            }
        }
    }

    if (!count($msg_erro["msg"])) {
        if (!empty($produto)){
            $cond_produto = " AND p.produto = {$produto} ";
            $cond_produto_of = " AND of.produto = {$produto} ";
            $join_produto = " 
                JOIN tbl_os_produto op ON op.os = o.os
                JOIN tbl_produto p ON p.produto = op.produto AND p.fabrica_i = {$login_fabrica} ";
        }

        if (!empty($posto)) {
            $cond_posto = " AND o.posto = {$posto} ";
            $cond_posto_of = " AND of.posto = {$posto} ";
            $join_posto = " JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = {$login_fabrica} ";
        }

        if (!empty($data_inicial) AND !empty($data_final)){
            $interval_label = "período {$data_inicial} - {$data_final}";
        }else{
            $interval_label = "período ".date( "d/m/Y", strtotime("-6 month"))." - ".date('d/m/Y');
        }

        $dados_pesquisa = json_encode($dados_pesquisa);

        if (!$considera_revenda) { 
            $sql = "
                SELECT
                    qtde_consertadas,
                    (qtde_abertas - qtde_consertadas) AS qtde_abertas_real,
                    qtde_abertas,
                    mes,
                    ano
                FROM (
                    SELECT
                        COUNT(of.os) AS qtde_consertadas,
                        x.qtde_abertas,
                        x.mes,
                        x.ano
                    FROM (
                        SELECT
                            COUNT(o.os) AS qtde_abertas,
                            DATE_PART('month', o.data_abertura) AS mes,
                            DATE_PART('year', o.data_abertura) AS ano
                        FROM tbl_os o
                        $join_posto
                        $join_produto
                        $join_revenda
                        WHERE o.fabrica = {$login_fabrica}
                        AND o.excluida IS NOT TRUE
                        AND o.data_abertura BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'
                        {$cond_posto}
                        {$cond_produto}
                        GROUP BY mes, ano
                    ) x
                    LEFT JOIN tbl_os of ON DATE_PART('year', of.data_conserto) = x.ano AND DATE_PART('month', of.data_conserto) = x.mes AND of.fabrica = {$login_fabrica} AND of.excluida IS NOT TRUE
                    $cond_produto_of
                    $cond_posto_of
                    GROUP BY qtde_abertas, mes, ano
                ) xx
                ORDER BY mes, ano";
        } else {
            $sql = "
                SELECT
                    qtde_consertadas,
                    (qtde_abertas - qtde_consertadas) AS qtde_abertas_real,
                    qtde_abertas,
                    mes,
                    ano
                FROM (
                    SELECT
                        COUNT(of.os_revenda) AS qtde_consertadas,
                        x.qtde_abertas,
                        x.mes,
                        x.ano
                    FROM (
                        SELECT
                            COUNT(o.os_revenda) AS qtde_abertas,
                            DATE_PART('month', o.data_abertura) AS mes,
                            DATE_PART('year', o.data_abertura) AS ano
                        FROM tbl_os_revenda o
                        $join_posto
                        $join_produto
                        WHERE o.fabrica = {$login_fabrica}
                        AND o.data_abertura BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'
                        {$cond_posto}
                        GROUP BY mes, ano
                    ) x
                    LEFT JOIN tbl_os_revenda of ON DATE_PART('year', of.data_fechamento) = x.ano AND DATE_PART('month', of.data_fechamento) = x.mes AND of.fabrica = {$login_fabrica}
                    $cond_produto_of
                    $cond_posto_of
                    GROUP BY qtde_abertas, mes, ano
                ) xx
                ORDER BY mes, ano";
        }

        $res = pg_query($con, $sql);

        $count_res = pg_num_rows($res);
        $array_dados = pg_fetch_all($res);

        if ($count_res > 0) {
            $data_series = array();
            $serie_data = array();
            $mesesx = array(
                "1" => "Jan.", "2" => "Fev.", "3" => "Mar.", "4" => "Abr.", "5" => "Mai.", "6" => "Jun.", 
                "7" => "Jul.", "8" => "Ago.", "9" => "Set.", "10" => "Out.", "11" => "Nov.", "12" => "Dez."
            );

            for ($i=0; $i < pg_num_rows($res); $i++) { 
                $qtdes = pg_fetch_result($res, $i, "qtde_abertas");
                $soma_total += $qtdes;
            }
            
            $soma_total = (int) $soma_total;
            
            for ($x=0; $x < pg_num_rows($res); $x++) {
                $total              = $soma_total;
                $qtde_consertadas   = pg_fetch_result($res, $x, "qtde_consertadas");
                $qtde_abertas       = pg_fetch_result($res, $x, "qtde_abertas");
                $qtde_abertas_real  = pg_fetch_result($res, $x, "qtde_abertas_real");
                $mes                = pg_fetch_result($res, $x, "mes");
                $ano                = pg_fetch_result($res, $x, "ano");
                $qtde_consertadas   = (int) $qtde_consertadas;
                $qtde_abertas       = (int) $qtde_abertas;
                $qtde_abertas_real  = (int) $qtde_abertas_real;
                $total              = (int) $total;

                $total_porcentagem  = ($qtde_abertas / $soma_total) * 100;
                $total_porcentagem  = number_format($total_porcentagem,2,".",",");
                $total_porcentagem  = floatval($total_porcentagem);
                
                $data_series[] = array(
                    "name" => $mesesx[$mes].$ano,
                    "y" => $total_porcentagem,
                    "drilldown" => $mesesx[$mes].$ano
                );
                
                $porcentagem_abertas = ($qtde_abertas_real / $qtde_abertas) * 100;
                $porcentagem_abertas = number_format($porcentagem_abertas,2,".",",");
                $porcentagem_abertas = floatval($porcentagem_abertas);
                
                $porcentagem_consertadas = ($qtde_consertadas / $qtde_abertas) * 100;
                $porcentagem_consertadas = number_format($porcentagem_consertadas,2,".",",");
                $porcentagem_consertadas = floatval($porcentagem_consertadas);

                $series_data[] = array(
                    "tooltip" => array("pointFormat" => "<span style='color:{point.color}' {point.name}</span>: <b>{point.y} {point.name}</b><br/>"),
                    "name" => $mesesx[$mes].$ano,
                    "id" => $mesesx[$mes].$ano,
                    "data" => array(
                        array("% Abertas",$porcentagem_abertas),
                        array("% Consertada",$porcentagem_consertadas)
                    )
                );
            }     
            
            $data_series = json_encode($data_series);
            $series_data = json_encode($series_data);
        }
    }
}

$layout_menu = "gerencia";
$title = "RELATÓRIO DE ORDEM DE SERVIÇO";

include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "highcharts_v7",
    "mask",
    "dataTable"
);

include("plugin_loader.php");
?>

<script type="text/javascript">
    $(function() {
        $.datepickerLoad(Array("data_final", "data_inicial"));
        $.autocompleteLoad(Array("produto", "posto"));
        Shadowbox.init();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });
	});

    function retorna_produto (retorno) {
        $("#produto_referencia").val(retorno.referencia);
        $("#produto_descricao").val(retorno.descricao);
    }

    function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
        $("#descricao_posto").val(retorno.nome);
    }

    // ----- SCRIPT MODAL -----//
    $(function() {
        $(".visualizar-dados").on("click", function() {
            $('#tabela_resultado').dataTable().fnClearTable();
            $('#tabela_resultado').dataTable().fnDestroy();

            let modal_relatorio_os  = $("#modal-relatorio-os");
            let dados_pesquisa      = $(this).data("dados_pesquisa");
            let mes_pesquisa        = $(this).data("mes_pesquisa");
            let tipo_pesquisa       = $(this).data("tipo_pesquisa");
            let ano_pesquisa        = $(this).data("ano_pesquisa");
            let mae_filha           = $(this).data("maefilha");
            let label_pesquisa      = "";

            dados_pesquisa = JSON.stringify(dados_pesquisa);
            switch(mes_pesquisa){
                case 1:
                    label_pesquisa = "Janeiro - "+ano_pesquisa;
                break;
                case 2:
                    label_pesquisa = "Fevereiro - "+ano_pesquisa;
                break;
                case 3:
                    label_pesquisa = "Março - "+ano_pesquisa;
                break;
                case 4:
                    label_pesquisa = "Abril - "+ano_pesquisa;
                break;
                case 5:
                    label_pesquisa = "Maio - "+ano_pesquisa;
                break;
                case 6:
                    label_pesquisa = "Junho - "+ano_pesquisa;
                break;
                case 7:
                    label_pesquisa = "Julho - "+ano_pesquisa;
                break;
                case 8:
                    label_pesquisa = "Agosto - "+ano_pesquisa;
                break;
                case 9:
                    label_pesquisa = "Setembro - "+ano_pesquisa;
                break;
                case 10:
                    label_pesquisa = "Outubro - "+ano_pesquisa;
                break;
                case 11:
                    label_pesquisa = "Novembro - "+ano_pesquisa;
                break;
                case 12:
                    label_pesquisa = "Dezembro - "+ano_pesquisa;
                break;
            }

            $(".th_titulo").text(label_pesquisa);

            var data_ajax = {
                exec_relatorio_os: true,
                dados_pesquisa: dados_pesquisa,
                mes_pesquisa: mes_pesquisa,
                ano_pesquisa: ano_pesquisa,
                tipo_pesquisa: tipo_pesquisa,
                mae_filha: mae_filha
            };

            $.ajax({
                url: "relatorio_os_abertas_fechadas_mes.php",
                type: "post",
                data: data_ajax,
                async: false,
                timeout: 10000
            }).done(function(res) {
                res = JSON.parse(res);
                var tabela = "";
                
                if (res.dados){
                    $(res.dados).each(function(x,y){
                        if (y.data_conserto == "" || y.data_conserto == undefined){
                            y.data_conserto = "";
                        }
                        tabela +="<tr>";
                        tabela +="<td>"+y.data_abertura+"</td><td>"+y.data_conserto+"</td><td>"+y.sua_os+"</td><td>"+y.consumidor_nome+"</td><td>"+y.produto_descricao+"</td><td>"+y.tipo_atendimento+"</td><td>"+y.posto_nome+"</td>";
                        tabela +="</tr>";
                    });
                    $(".tabela_tbody").html(tabela);
                }
            });
            $(modal_relatorio_os).modal("show");
            $.dataTableLoad({ table: "#tabela_resultado" });
        });

        $("#btn-close-modal-relatorio-os").on("click", function(){
            let modal_relatorio_os  = $("#modal-relatorio-os");
            $(modal_relatorio_os).modal("hide");
        });
    });
</script>
<style type="text/css">
    div#modal-relatorio-os {
        width: 950px;
        margin-left: -475px;
    }
</style>
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
<form name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>
    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
    <br/>

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
                            <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>

	<div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='produto_referencia'>Ref. Produto</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                        <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='produto_descricao'>Descrição Produto</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
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
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='codigo_posto'>Código Posto</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
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
                        <input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <?php
    if ($login_fabrica == 178) { ?>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class="span6">
                <div class='control-group' >
                    <label class="control-label" for="mae_filha"></label>
                    <div class="controls controls-row">
                        <div class="span3">
                            <input type="radio" name="mae_filha" value="ambas" <?= ($mae_filha == "ambas" || !isset($_POST["btn_acao"])) ? "checked" : "" ?> /> Ambas
                        </div>
                        <div class="span3">
                            <input type="radio" name="mae_filha" value="mae" <?= ($mae_filha == "mae") ? "checked" : "" ?> /> OSs Mães
                        </div>
                        <div class="span4">
                            <input type="radio" name="mae_filha" value="filha" <?= ($mae_filha == "filha") ? "checked" : "" ?> /> OSs Filhas
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php
    } ?>
    <p><br/>
        <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
    </p><br/>
</form>

<?php 
if (isset($_POST["btn_acao"]) AND !count($msg_erro)) {
    if ($count_res > 0){ 

        $lbl_qtde = ($mae_filha == "mae") ? "Qtde. Fechadas" : "Qtde. Consertada";

    if (in_array($login_fabrica, [178])) {
        ob_start();
    }
    ?>
    <table id="resultado_os_atendimento" class='table table-striped table-bordered table-hover table-fixed' >
        <thead>
            <tr class='titulo_tabela'>
                <th colspan="3" >Relatório OSs Aberta x Fechadas</th>
            </tr>
            <tr class="titulo_coluna">
                <th>Mes</th>
                <th>Qtde. Aberta</th>
                <th><?= $lbl_qtde ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
                foreach ($array_dados as $key => $value) {
            ?>
                    <tr>
                        <td><?=$mesesx[$value["mes"]].' '.$value["ano"]?></td>
                        <td class="tac visualizar-dados" data-maefilha="<?= $mae_filha ?>" data-dados_pesquisa='<?=$dados_pesquisa?>' data-mes_pesquisa='<?=$value["mes"]?>' data-ano_pesquisa="<?=$value["ano"]?>" data-tipo_pesquisa='aberta' style="cursor: pointer;"><?=$value["qtde_abertas"]?></td>
                        <td class="tac visualizar-dados" data-maefilha="<?= $mae_filha ?>" data-dados_pesquisa='<?=$dados_pesquisa?>' data-mes_pesquisa='<?=$value["mes"]?>' data-ano_pesquisa="<?=$value["ano"]?>" data-tipo_pesquisa='consertada' style="cursor: pointer;"><?=$value["qtde_consertadas"]?></td>
                    </tr>
            <?php
                }
            ?>
        </tbody>
    </table>
    <?php
    if (in_array($login_fabrica, [178])) {

        $excel = ob_get_contents();

        $data = date("d-m-Y-H:i");

        $fileName = "xls/relatorio-abertas-fechadas-{$data}.xls";

        $file = fopen("{$fileName}", "w");

        fwrite($file, $excel);
        fclose($file); ?>
    <br />
    <div class='btn_excel' onclick="window.open('<?= $fileName ?>')">
        <span><img src='imagens/excel.png' /></span>
        <span class="txt">Arquivo Excel</span>
    </div>
    <?php
    }

}else{ ?>
    <div class="container">
        <div class="alert">
            <h4>Nenhum resultado encontrado</h4>
        </div>
    </div>
<?php 
    } 
}
?>
<div id="container" style="width: 700px; height: 400px; margin: 0 auto"></div>

<div id="modal-relatorio-os" class="modal hide fade" data-backdrop="static" data-keyboard="false" >
    <div class="modal-body">
        <table id="tabela_resultado" class="table table-bordered table-hover table-fixed" >
            <thead>
                <tr class='titulo_tabela'>
                    <th colspan="7" class="th_titulo"></th>
                </tr>
                <tr class="titulo_coluna">
                    <th>Data Abertura</th>
                    <th>Data Conserto</th>
                    <th>OS</th>
                    <th>Consumidor/Revenda</th>
                    <th>Produto</th>
                    <th>Tipo Atendimento</th>
                    <th>Posto</th>
                </tr>
            </thead>
            <tbody class="tabela_tbody">
            
            </tbody>
        </table>
    </div>
    <div class="modal-footer">
        <button type="button" id="btn-close-modal-relatorio-os" class="btn">Fechar</button>
    </div>
</div>

<script type="text/javascript">
    <?php if ($count_res > 0){ ?>
    Highcharts.setOptions({
        lang: {
            drillUpText: '<b>Voltar</b>'
        }
    });
    Highcharts.chart('container', {
        chart: {
            type: 'pie'
        },
        title: {
            text: '<?=$interval_label?>'
        },
        subtitle: {
            text: 'Relatório de OS Abertas e Consertadas'
        },
        plotOptions: {
            series: {
                dataLabels: {
                    enabled: true,
                    format: '{point.name} {point.y}%'
                }
            }
        },
        tooltip: {
            headerFormat: '<span style="font-size:11px">{series.name}</span><br>'
        },
        series: [
            {
                name: "Mes",
                colorByPoint: true,
                tooltip: {
                    pointFormat: '<span style="color:{point.color}">{point.name}</span>: <b>{point.y} %Mês</b><br/>'
                },
                data: <?=$data_series?>
            }
        ],
        drilldown: {
            series: <?=$series_data?>
        }
    });
    <?php } ?>
</script>

<?php include "rodape.php" ?>
