<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'funcoes.php';
include "autentica_admin.php";

$admin_privilegios = "gerencia";
$layout_menu = "gerencia";

if ($_POST["ajax_atualiza_procon_juizado"] == true) { 

    $mes_ano        = $_POST["mes_ano"];
    $procon_juizado = $_POST["procon_juizado"];
    $posicao        = $_POST["posicao"];
    $tm_conserto    = $_POST["tmconserto"];
    $tm_analise     = $_POST["tmanalise"];
    $procon         = $_POST["procon"];
    $nps            = $_POST["nps"];
    $posto          = $_POST["posto"];

    $dadosProconPosto = getPostoFabrica($posto);
    $dadosProconPosto["procon_juizado"][$mes_ano] = $procon_juizado;

    $upPF = "UPDATE tbl_posto_fabrica 
                SET parametros_adicionais='".json_encode($dadosProconPosto)."' 
              WHERE posto = {$posto} 
                AND fabrica = {$login_fabrica}
                ";
    $upRes = pg_query($con, $upPF);

    if (pg_last_error()) {
        exit(json_encode(["erro" => true, "msg" => "Erro ao atualizar Procon / Juizado"]));
    }

    $retornoClassificacao = calculaClassificacao($tm_conserto,$tm_analise,$procon,$nps, $posto, $mes_ano);
    if (isset($retornoClassificacao["classificacao"]) && strlen($retornoClassificacao["classificacao"]) > 0) {
        exit(json_encode(["erro" => false, "classificacao" => $retornoClassificacao["classificacao"]]));
    }
    exit(json_encode(["erro" => true, "msg" => "Erro ao atualizar Procon / Juizado"]));

}

if ($_POST["btn_acao"] == "pesquisar") { 

    $posto         = "";
    $condPosto     = "";
    $msg_erro      = [];
    $codigo_posto  = $_POST['codigo_posto'];
    $mes_inicial   = $_POST['mes_inicial'];
    $ano_inicial   = $_POST['ano_inicial'];
    $mes_final     = $_POST['mes_final'];
    $ano_final     = $_POST['ano_final'];
    $xano          = $_POST['ano'];
    $xmes          = $_POST['mes'];

    if (strlen($mes_inicial) == 0) {
        $msg_erro["msg"][]   = "Campo MÊS é obrigatório";
        $msg_erro["campo"][] = "mes_inicial";
    }

    if (strlen($ano_inicial) == 0) {
        $msg_erro["msg"][]   = "Campo ANO é obrigatório";
        $msg_erro["campo"][] = "ano_inicial";
    }

    if (strlen($codigo_posto) > 0) {
        $sqlPosto = "SELECT posto 
                       FROM tbl_posto_fabrica 
                      WHERE fabrica={$login_fabrica} 
                        AND codigo_posto='{$codigo_posto}'";
        $resPosto = pg_query($con, $sqlPosto);

        if (pg_num_rows($resPosto) > 0) {
            $posto = pg_fetch_result($resPosto, 0, 'posto');
        }

    }

    if (count($msg_erro["msg"]) == 0) {
        
        $mostra_procon = false;

        if ($mes_inicial == $mes_final  && $ano_inicial == $ano_final) {
            $mostra_procon = true;
            $xmes = $mes_inicial;
            $xano = $ano_final;
        }

        $data_ini = $ano_inicial."-".$mes_inicial."-01";
        $data_f   = $ano_final."-".$mes_final."-01";
        $data_fim = date("Y-m-t", strtotime($data_f));


        if (strlen($posto) > 0) {
            $condPosto = " AND pf.posto = {$posto}";
        }
        $dadosConsulta = [];
        $sql = "WITH postos AS (
                    SELECT  dados.posto,
                            dados.codigo_posto,
                            dados.nome,
                            dados.qtde_linhas,
                           COALESCE(
                                cast(
                                    AVG( (DATE_PART('day',dados.digitacao_peca - dados.data_abertura::timestamp) * 24 + DATE_PART('hour',dados.digitacao_peca - dados.data_abertura::timestamp)) / 24) 
                                        FILTER (
                                            WHERE dados.digitacao_peca IS NOT NULL 
                                                and dados.data_conserto IS NOT NULL
                                            ) 
                                    AS NUMERIC(15,2)
                                ), 
                                0
                            ) as tm_analise,
                            COALESCE(
                                cast(
                                    AVG(COALESCE(dados.data_conserto::date-dados.previsao_chegada::date, 0))
                                        FILTER (
                                            WHERE dados.digitacao_peca IS NOT NULL 
                                                and dados.data_conserto IS NOT NULL
                                        ) 
                                    AS NUMERIC(15,2)
                                ), 
                                0
                            ) as tm_conserto_com,
                            COALESCE(
                                cast(
                                    AVG(COALESCE(dados.data_conserto::date-dados.data_abertura::date, 0))
                                        FILTER (
                                            WHERE dados.digitacao_peca IS NULL
                                                and dados.data_conserto IS NOT NULL
                                        ) 
                                    AS NUMERIC(15,2)
                                ),
                                0
                            ) as tm_conserto_sem,
                            COALESCE(
                                AVG(dados.nota) 
                                    FILTER (WHERE dados.nota < 7)
                                ,
                                0
                            ) as nps_detratores,
                            COALESCE(
                                AVG(dados.nota) 
                                    FILTER (WHERE dados.nota > 8) 
                                ,
                                0
                            ) as nps_promotores,
                            COUNT(1) FILTER(WHERE dados.nota IS NOT NULL) as nps_total,
                            COUNT(1) FILTER(WHERE dados.procon IS NOT NULL) as procon,
                            COUNT(*) as qtde_os,
                            COUNT(1) FILTER (WHERE dados.consumidor_revenda = 'R') as qtde_os_revenda,
                            COUNT(1) FILTER (WHERE dados.qtde_os_demora > 25 ) as qtde_os_demora,
                            COUNT(1) FILTER (WHERE dados.causa_troca = 23) as troca_motivo_posto
                    FROM (
                        SELECT
                            o.os,
                            o.data_conserto,
                            o.data_abertura,
                            pf.posto,
                            pf.codigo_posto,
                            p.nome,
                            (
                                SELECT tbl_os_item.digitacao_item
                                FROM tbl_os_item
                                JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                                WHERE tbl_os_item.fabrica_i = {$login_fabrica}
                                AND tbl_os_produto.os = o.os
                                ORDER BY tbl_os_item.digitacao_item DESC
                                LIMIT 1
                            ) as digitacao_peca,
                            (
                                SELECT tbl_faturamento.previsao_chegada::date
                                FROM tbl_os_item
                                JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                                JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido = tbl_os_item.pedido
                                JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                                AND tbl_faturamento.fabrica = {$login_fabrica}
                                WHERE tbl_os_item.fabrica_i = {$login_fabrica}
                                AND tbl_os_produto.os = o.os
                                ORDER BY tbl_os_item.digitacao_item DESC
                                LIMIT 1
                            ) as previsao_chegada,
                            
                            r.nota as nota,
                            tbl_processo.processo as procon,
                            COUNT(o.os) as qtde_os,
                            o.consumidor_revenda,
                            o.data_fechamento - o.data_abertura as qtde_os_demora,
                            ot.causa_troca,
                            (
                                SELECT COUNT(*)
                                FROM tbl_posto_linha
                                JOIN tbl_linha ON tbl_posto_linha.linha = tbl_linha.linha
                                AND  tbl_linha.fabrica = {$login_fabrica}
                                AND tbl_linha.ativo
                                WHERE tbl_posto_linha.posto = pf.posto
                                LIMIT 1
                            ) as qtde_linhas
                        FROM
                            tbl_os o
                            JOIN tbl_posto_fabrica pf USING (posto, fabrica)
                            JOIN tbl_posto p USING (posto)
                            LEFT JOIN tbl_processo USING (os)
                            LEFT JOIN tbl_os_troca ot USING (os)
                            LEFT JOIN tbl_os_produto op USING (os)
                            LEFT JOIN tbl_resposta r USING (os)
                        WHERE 
                            o.fabrica = {$login_fabrica}
                            AND o.data_fechamento between '{$data_ini}' and '{$data_fim}'
                            $condPosto
                        GROUP BY
                            pf.posto,
                            pf.codigo_posto,
                            p.nome,
                            o.os,
                            r.nota,
                            tbl_processo.processo,
                            o.consumidor_revenda,
                            ot.causa_troca
                    ) AS dados
                    GROUP BY
                            dados.posto,
                            dados.codigo_posto,
                            dados.nome,
                            dados.qtde_linhas
                )
                SELECT *, (
                    SELECT COUNT(*)
                    FROM tbl_os
                    WHERE data_fechamento between '{$data_ini}' and '{$data_fim}'
                    AND tbl_os.fabrica = {$login_fabrica}
                    AND finalizada IS NOT NULL
                ) AS total_os
                FROM postos;";
        $res = pg_query($con, $sql);
        if (pg_last_error()) {
            $msg_erro["msg"][]   = "Erro ao efetuar a consulta";
        } else {
            $dadosConsulta = pg_fetch_all($res);
        }
    }
}

function tem_procon_periodo($postoId, $inicio, $fim) {
    global $con, $login_fabrica;

    $dadosProconPosto = getPostoFabrica($postoId);

    $data_inicio = "01-".date("m-Y", $inicio);
    $data_fim    = "01-".date("m-Y", $fim);

    $sqlMeses = "SELECT TO_CHAR(meses, 'mm-yyyy') as mes_ano
                 FROM generate_series(
                    '{$inicio}'::date,
                    '{$fim}'::date,
                    INTERVAL '1 month'
                 ) as meses";
    $resMeses = pg_query($con, $sqlMeses);

    $temProcon = false;
    while ($dados = pg_fetch_object($resMeses)) {

        if (isset($dadosProconPosto["procon_juizado"][$dados->mes_ano])) {

            $temProcon = true;

        }

    }

    return $temProcon;

}

function getNPS($mes, $ano, $posto) {
    global $con, $login_fabrica;
   
    $data_ini = $ano."-".$mes."-01";
    $data_fim = date("Y-m-t", strtotime($data_ini));

    $sql = "
        SELECT  r.sem_resposta, 
                r.txt_resposta, 
                r.pesquisa, 
                pf.versao, 
                pf.formulario, 
                p.descricao AS pesquisa_titulo, 
                o.sua_os, 
                r.os, 
                pf.pesquisa_formulario, 
                o.data_abertura, 
                r.data_input::date AS data_envio,
                o.data_fechamento, 
                o.consumidor_nome,
                paf.codigo_posto || ' - ' || pa.nome AS nome_posto,
                o.consumidor_estado, 
                o.consumidor_cidade
          FROM tbl_resposta r
          JOIN tbl_os o ON o.os = r.os AND o.fabrica = {$login_fabrica}
          JOIN tbl_posto_fabrica paf ON paf.posto = o.posto AND paf.fabrica = {$login_fabrica}
          JOIN tbl_posto pa ON pa.posto = paf.posto 
          JOIN tbl_pesquisa_formulario pf ON pf.pesquisa_formulario = r.pesquisa_formulario
          JOIN tbl_pesquisa p ON p.pesquisa = r.pesquisa AND p.fabrica = {$login_fabrica}
         WHERE (r.data_input BETWEEN '$data_ini 00:00:00' AND '$data_fim 23:59:59')
           AND r.sem_resposta = 'f'
           AND paf.posto = $posto
           AND p.categoria in('os', 'os_email')
      ORDER BY o.data_abertura DESC";
    $res = pg_query($con, $sql);
    if (pg_last_error()) {
        return 0;
    }

    if (pg_num_rows($res) > 0) {

        $enviadas    = array( 'geral' => 0 );
        $respondidas = array( 'geral' => 0 );
        $pesquisas   = array();
        $formularios = array();
        $respostas   = array();
        $perguntas   = array();
        
        foreach (pg_fetch_all($res) as $k => $r) {
            $formularios[$r['pesquisa']][$r['versao']] = json_decode(utf8_decode($r['formulario']), true);
            $resposta = json_decode(utf8_encode($r['txt_resposta']), true);
            $pesquisas[$r['pesquisa']] = $r['pesquisa_titulo'];

            foreach ($resposta as $key => $value) {
                if (!array_key_exists($key, $respostas[$r['pesquisa']])) {
                    $respostas[$r['pesquisa']][$key] = array();
                }

                if (is_array($value)) {
                    foreach ($value as $v) {
                        $respostas[$r['pesquisa']][$key][] = $v;
                    }
                } else {
                    $respostas[$r['pesquisa']][$key][] = $value;
                }
            }
        }

        foreach ($formularios as $pesquisa => $versoes) {
            if (!array_key_exists($pesquisa, $perguntas)) {
                $perguntas[$pesquisa] = array();
            }
            
            foreach ($versoes as $formulario) {
                foreach($formulario as $pergunta) {
                    if (strtolower($pergunta['label'])  != "nps") {
                        continue;
                    }
                    if (!array_key_exists($pergunta['name'], $perguntas[$pesquisa])) {

                        $perguntas[$pesquisa][$pergunta['name']] = array(
                            'label'  => utf8_decode($pergunta['label']),
                            'type'   => $pergunta['type'],
                            'values' => array()
                        );
                    }
                    
                    if (is_array($pergunta['values'])) {
                        foreach($pergunta['values'] as $valor) {
                            $perguntas[$pesquisa][$pergunta['name']]['values'][$valor['value']] = utf8_decode($valor['label']);
                        }
                        
                        if (array_key_exists('other', $pergunta) && $pergunta['other'] == 1) {
                            $perguntas[$pesquisa][$pergunta['name']]['values']['outro'] = 'Outro';
                        }
                        
                        $perguntas[$pesquisa][$pergunta['name']]['values'] = array_unique($perguntas[$pesquisa][$pergunta['name']]['values']);
                    }
                }
            }
        }

        foreach ($pesquisas as $id => $titulo) {
            foreach ($perguntas[$id] as $name => $pergunta) {

                $total = count($respostas[$id][$name]);
                $r = array();

                foreach ($pergunta['values'] as $value_id => $value_label) {
                    $r[$value_id] = 0;
                }

                foreach ($respostas[$id][$name] as $resposta) {
                    $r[$resposta]++;
                }
                $maior = $r;
                arsort($maior);
                $maior = key($maior);
                $detratores = array();
                $neutros = array();
                $promotores = array();
                foreach ($pergunta['values'] as $value_id => $value_label) {
                    $class = null;
                    $p = ($r[$value_id] / $total) * 100;
                    
                    if ($value_id <= 6) {
                        $detratores[] = $r[$value_id];
                    } else if ($value_id > 6 && $value_id <= 8) {
                        $neutros[] = $r[$value_id];
                    } else {
                        $promotores[] = $r[$value_id];
                    }
                }
                $totalDetratores = array_sum($detratores);
                $totalNeutros    = array_sum($neutros);
                $totalPromotores = array_sum($promotores);
               
                $totalDetratoresPor = ($totalDetratores / $total) * 100 ;
                $totalNeutrosPor    = ($totalNeutros / $total) * 100 ;
                $totalPromotoresPor = ($totalPromotores / $total) * 100;

                $mediaPD = $totalPromotores-$totalDetratores;
                $score = ($mediaPD / $total) * 100;
                return number_format($score, 2, '.', '');
            }
        }   
    }

    return 0;

}

function getPostoFabrica($posto) {
    global $con, $login_fabrica;

    $xxparametros_adicionais = [];
    
    $sqlPF = "SELECT parametros_adicionais 
                FROM tbl_posto_fabrica 
               WHERE fabrica={$login_fabrica} 
                 AND posto={$posto}";
    $resPF = pg_query($con, $sqlPF);
    
    if (pg_num_rows($resPF) > 0) {
        $xxparametros_adicionais = json_decode(pg_fetch_result($resPF, 0, 'parametros_adicionais'),1);
    }

    return $xxparametros_adicionais;

}

function calculaClassificacao($tm_conserto, $tm_analise, $procon, $nps, $posto, $mes_ano, $mostra_procon = false, $inicio = null, $fim = null) {
    global $con, $login_fabrica;
    $calc_procon = 0;
    $calc_tm     = 0;
    $calc_nps    = 0;

    if ($procon == 0 ) {
        $calc_procon = 20;
    }

    $tm_total = $tm_analise + $tm_conserto;

    if ($tm_total <= 2.75) {
        $calc_tm = 60;
    } else if ($tm_total <= 3.70) {
        $calc_tm = 40;
    } else if ($tm_total <= 4.80) {
        $calc_tm = 30;
    }

    if ($nps == 0) {
        $calc_nps = 20;
    } else if ($nps <= 30) {
        $calc_nps = 0;
    } else if ($nps > 30 && $nbs <= 49) {
        $calc_nps = 5;
    } else if ($nps > 49 && $nps <= 64) {
        $calc_nps = 10;
    } else if ($nps > 64) {
        $calc_nps = 20;
    }

    $total_classificacao = $calc_nps + $calc_tm + $calc_procon;

    $combo_procon = getPostoFabrica($posto);

    if ($mostra_procon) {
        if (isset($combo_procon["procon_juizado"][$mes_ano]) && !empty($combo_procon["procon_juizado"][$mes_ano])) {
            $total_classificacao = $total_classificacao - 20;
        }
    } else {

        $temProcon = tem_procon_periodo($posto, $inicio, $fim);

        if ($temProcon) {
            $total_classificacao = $total_classificacao - 20;
        }

    }

    $classificacao = 'Péssimo';
    if ($total_classificacao == 100) {
        $classificacao = 'Top';
    } else if ($total_classificacao >= 90 ) {
        $classificacao = 'Ótimo';
    } else if ($total_classificacao >= 70 ) {
        $classificacao = 'Bom';
    } else if ($total_classificacao >= 50 ) {
        $classificacao = 'Desenvolvimento';
    }

    return ["classificacao" => $classificacao, "combo_procon" => $combo_procon, "pontuacao" => $total_classificacao];
}
$title = "RELATÓRIO DE ACOMPANHAMENTO DE NPS";
include 'cabecalho_new.php';

$plugins = array(
    "autocomplete",
    "shadowbox",
    "mask",
    "alphanumeric",
    "dataTable",
);

include("plugin_loader.php");
?>

<script type="text/javascript" charset="utf-8">
    $(function() {
        $("span[rel=lupa]").click(function () {
                    $.lupa($(this));
        });
        Shadowbox.init();
        $.autocompleteLoad(Array("posto"));

        $(document).on("change", ".xcombo_procon", function(){
            var valores     = $(this).val();
            var posicao     = $(this).data("posicao");
            var tmconserto  = $(this).data("tmconserto");
            var tmanalise   = $(this).data("tmanalise");
            var procon      = $(this).data("procon");
            var nps         = $(this).data("nps");
            var posto       = $(this).data("posto");
            var mes_ano     = $(this).data("mesano");

            if (valores != '') {

                $.ajax({
                    type: 'POST',
                    dataType:"JSON",
                    url: 'relatorio_acompanhamento_posto_nps.php',
                    data: {
                        ajax_atualiza_procon_juizado: true,
                        mes_ano:mes_ano,
                        procon_juizado:valores,
                        tmconserto : tmconserto ,
                        tmanalise  : tmanalise  ,
                        procon     : procon     ,
                        nps        : nps        ,
                        posto      : posto 
                    },
                }).done(function(data) {
                    if(data.erro){
                        alert(data.msg);
                        return false;
                    } else {
                        $(".classic-"+posicao).text(data.classificacao);
                    }
                });

            }


        });

    });

    function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
        $("#descricao_posto").val(retorno.nome);
    }

</script>
<?php 
    if (count($msg_erro["msg"]) > 0) {
        echo "
            <div class='alert alert-danger'>
                <h4>".implode("<br>", $msg_erro["msg"])."</h4>
            </div>";
    }
?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form class='form-search form-inline tc_formulario' name="frm_rel" method="POST" action="relatorio_acompanhamento_posto_nps.php">
    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
    <br/>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class='span2'>
            <div class='control-group  <?=(in_array("mes_inicial", $msg_erro["campo"])) ? "error" : ""?>'>
                <label class='control-label' for='mes_inicial'>Mês Inicial</label>
                <div class='controls controls-row'>
                    <h5 class='asteristico'>*</h5>
                    <select class="span12" name="mes_inicial" id="mes_inicial">
                        <option value="">Selecione ...</option>
                        <?php 
                            for ($i=1; $i <= 12 ; $i++) { 
                                $mes_inicial = str_pad($i,2,"0", STR_PAD_LEFT);
                                $selected = ($mes_inicial == $_POST["mes_inicial"]) ? "selected" : "";
                                echo "<option value='{$mes_inicial}' {$selected }>{$mes_inicial}</option>";
                            }
                        ?>
                    </select>
                </div>
            </div>
        </div>
        <div class='span2'>
            <div class='control-group  <?=(in_array("ano_inicial", $msg_erro["campo"])) ? "error" : ""?>'>
                <label class='control-label' for='ano_inicial'>Ano Inicial</label>
                <div class='controls controls-row'>
                    <h5 class='asteristico'>*</h5>
                    <select class="span12" name="ano_inicial" id="ano_inicial">
                        <option value="">Selecione ...</option>
                        <?php 
                            for ($ano_inicial = 2018; $ano_inicial <= date("Y") ; $ano_inicial++) { 
                                $selected = ($ano_inicial == $_POST["ano_inicial"]) ? "selected" : "";
                                echo "<option value='{$ano_inicial}' {$selected }>{$ano_inicial}</option>";
                            }
                        ?>
                    </select>
                </div>
            </div>
        </div>

        <div class='span2'>
            <div class='control-group'>
                <label class='control-label' for='mes_final'>Mês Final</label>
                <div class='controls controls-row'>
                    <select class="span12" name="mes_final" id="mes_final">
                        <option value="">Selecione ...</option>
                        <?php 
                            for ($i=1; $i <= 12 ; $i++) { 
                                $mes_final = str_pad($i,2,"0", STR_PAD_LEFT);
                                $selected = ($mes_final == $_POST["mes_final"]) ? "selected" : "";
                                echo "<option value='{$mes_final}' {$selected }>{$mes_final}</option>";
                            }
                        ?>
                    </select>
                </div>
            </div>
        </div>
        <div class='span2'>
            <div class='control-group'>
                <label class='control-label' for='ano_final'>Ano Final</label>
                <div class='controls controls-row'>
                    <select class="span12" name="ano_final" id="ano_final">
                        <option value="">Selecione ...</option>
                        <?php 
                            for ($ano_final = 2018; $ano_final <= date("Y") ; $ano_final++) { 
                                $selected = ($ano_final == $_POST["ano_final"]) ? "selected" : "";
                                echo "<option value='{$ano_final}' {$selected }>{$ano_final}</option>";
                            }
                        ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="span2"></div>
    </div>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span4">
            <div class="control-group">
                <label class="control-label" for="">Código do Posto</label>
                <div class="controls controls-row">
                    <div class="input-append">
                        <INPUT TYPE="text" class="frm" NAME="codigo_posto" value="<?=$codigo_posto?>" id="codigo_posto">
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span4">
            <div class="control-group">
                <label class="control-label" for="">Posto Autorizado</label>
                <div class="controls controls-row">
                    <div class="input-append">
                        <INPUT TYPE="text" class="frm" NAME="descricao_posto" value="<?=$descricao_posto?>" id="descricao_posto">
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span2"></div>
    </div><br />
    <input type="hidden" name="btn_acao" value="pesquisar" />
    <input class="btn btn-primary" type="submit" value="Pesquisar">
    <br /><br />
</form>
</div>

<?php if ($_POST["btn_acao"] == "pesquisar" && count($msg_erro["msg"]) == 0) { 

    $arquivoPostos      = "xls/relatorio-nps-{$login_fabrica}-".date('Y-m-d h:i:s').".xls";

    ob_start();
?>
<div class='gerar_excel_os btn_excel' onclick="window.open('<?= $arquivoPostos ?>')">
    <span><img src='imagens/excel.png' /></span>
    <span class="txt">Gerar Arquivo Excel</span>
</div>
<table class='table table-striped table-bordered table-hover ' id='tabela'>
	<thead>
		<tr class='titulo_coluna'>
			<th nowrap>CódigoPosto</th>
            <th nowrap>Nome Posto</th>
            <th nowrap>TM Análise</th>
            <th nowrap>TM Conserto</th>
            <th nowrap>NPS</th>
            <th nowrap>Pontuação Total</th>
            <th nowrap>Classificação</th>
            <th nowrap>Qtde. OS</th>
            <th nowrap>Qtde. OS Revenda</th>
            <th nowrap>% OS Aberta</th>
            <th nowrap>% + 25 dias</th>
            <th nowrap>Linhas Atendidas</th>
            <th nowrap>Atraso PA</th>
            <th nowrap>Procon/Juizado</th>
		</tr>
	</thead>
	<tbody>
<?php 
            if (count($dadosConsulta) > 0) {
		    $k = 0;
                foreach ($dadosConsulta as $posto) {
			        $tm_conserto = ($posto['tm_conserto_sem'] + $posto['tm_conserto_com']) / 2 ;
		            //$tm_conserto =  $posto['tm_conserto_com'];
                    $detratores =  floatval($posto['nps_detratores']) / floatval($posto['nps_total']);
                    $promotores =  floatval($posto['nps_promotores']) / floatval($posto['nps_total']);
                    $nps = getNPS($xmes, $xano, $posto["posto"]);
                    $os_antiga = number_format(($posto['qtde_os_demora'] / $posto['qtde_os']) * 100, 2);
                    $os_total  = number_format(($posto['qtde_os'] / $posto['total_os']) * 100, 2);
                    //Calculos da classificação

                    $retornoClassificacao = calculaClassificacao($tm_conserto,$posto['tm_analise'],$posto['procon'], $nps, $posto["posto"], $xmes.'-'.$xano, $mostra_procon, $data_ini, $data_fim);
                    echo "<tr class='tr-{$k}'>";
                        echo "<td nowrap>{$posto['codigo_posto']}</td>";
                        echo "<td nowrap>{$posto['nome']}</td>";
                        echo "<td nowrap class='tac'>{$posto['tm_analise']}</td>";
                        echo "<td nowrap class='tac'>{$tm_conserto}</td>";
                        echo "<td nowrap class='tac'>{$nps} % </td>";
                        echo "<td nowrap class='tac'>{$retornoClassificacao['pontuacao']}</td>";
                        echo "<td nowrap class='tac'><span class='classic-{$k}'>{$retornoClassificacao['classificacao']}</span></td>";
                        echo "<td nowrap class='tac'>{$posto['qtde_os']}</td>";
                        echo "<td nowrap class='tac'>{$posto['qtde_os_revenda']}</td>";
                        echo "<td nowrap class='tac'>{$os_total}% </td>";
                        echo "<td nowrap class='tac'>{$os_antiga}% </td>";
                        echo "<td nowrap class='tac'>{$posto['qtde_linhas']}</td>";
                        echo "<td nowrap class='tac'>{$posto['troca_motivo_posto']}</td>";
                        $selectedProcon  = "";
                        $selectedJuizado = "";
                        if ($mostra_procon) {

                            if (isset($retornoClassificacao["combo_procon"]["procon_juizado"][$xmes.'-'.$xano])) {

                                if ($retornoClassificacao["combo_procon"]["procon_juizado"][$xmes.'-'.$xano] == 'juizado') {
                                    $selectedJuizado = "selected";
                                    $textProconJuizado = "Juizado";
                                } else if ($retornoClassificacao["combo_procon"]["procon_juizado"][$xmes.'-'.$xano] == 'procon') {
                                    $selectedProcon = "selected";
                                    $textProconJuizado = "Procon";
                                } else {
                                    $selectedJuizado   = "";
                                    $textProconJuizado = "";
                                }
                            } else {
                                $selectedJuizado   = "";
                                $textProconJuizado = "";
                            }
                            
                            echo "
                            <td nowrap class='tac juizado_procon'>
                                {$textProconJuizado}
                                <select name='combo_procon' data-mesano='".$xmes.'-'.$xano."' data-tmconserto='{$tm_conserto}' data-tmanalise='{$posto['tm_analise']}'  data-procon='{$posto['procon']}' data-nps='{$nps}' data-posto='{$posto["posto"]}' data-posicao='{$k}' style='width:100px' class='xcombo_procon'>
                                    <option value=''>Selecione ...</option>
                                    <option value='procon' {$selectedProcon}>Procon</option>
                                    <option value='juizado' {$selectedJuizado}>Juízado</option>
                                </select>
                            </td>";
                        } else {

                            if (isset($retornoClassificacao["combo_procon"]["procon_juizado"][$xmes.'-'.$xano]) ) {
                                if ($retornoClassificacao["combo_procon"]["procon_juizado"][$xmes.'-'.$xano] == 'juizado') {
                                    $textProconJuizado = "Juizado";
                                } else if ($retornoClassificacao["combo_procon"]["procon_juizado"][$xmes.'-'.$xano] == 'procon') {
                                    $textProconJuizado = "Procon";
                                } else {
                                    $selectedJuizado   = "";
                                    $textProconJuizado = "";
                                }
                            } else {
                                $selectedJuizado   = "";
                                $textProconJuizado = "";
                            }

                            echo "<td nowrap class='tac'>{$textProconJuizado}</td>";

                        }
                    echo "</tr>";
                    $k++;
                } 
            }
        ?>
	</tbody>
</table>
<?php 

    $excel = ob_get_contents();
    $fp = fopen($arquivoPostos,"w");
    fwrite($fp, $excel);
    fclose($fp);

} ?>
<script type="text/javascript">

    $(".juizado_procon").contents().filter(function(){
        return this.nodeType === 3;
    }).remove();

	$.dataTableLoad({ table: "#tabela" });
</script>

<?php include "rodape.php"; ?>
