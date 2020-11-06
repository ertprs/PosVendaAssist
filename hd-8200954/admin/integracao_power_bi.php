<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

use Posvenda\Helpers\ReportGenerator;

$admin_privilegios = "auditoria";

$gera_automatico = trim($_GET["gera_automatico"]);

if ($gera_automatico != "automatico") {
        include "autentica_admin.php";
}

include 'funcoes.php';

if ($_serverEnvironment == "production") {
    include "gera_relatorio_pararelo_include.php";
}

if (strlen(trim($_REQUEST["btn_acao"])) > 0) $btn_acao = trim($_REQUEST["btn_acao"]);

if ($btn_acao == "submit") {

        $mes = $_REQUEST["mes"];
        $ano = $_REQUEST["ano"];
        $btn_acao = "submit";
        $zestado = $_REQUEST['estado'];

        if(strlen($mes) == 0){
            $msg_erro_form["msg"][]    = "Por favor, insira o Mês";
            $msg_erro_form["campos"][] = "mes";
        }

        if(strlen($ano) == 0){
            $msg_erro_form["msg"][]    = "Por favor, insira o Ano";
            $msg_erro_form["campos"][] = "ano";
        }

    }

    $layout_menu = "callcenter";
    $title = "RELATÓRIO DE ORDENS DE SERVIÇO PARA BI";

    include "cabecalho_new.php";

    $plugins = array();

    include("plugin_loader.php");

    if(strlen($btn_acao) > 0 && count($msg_erro_form["msg"]) == 0){
        if ($_serverEnvironment == "production") {
            include "gera_relatorio_pararelo.php";
        }
    }

    if($gera_automatico != "automatico") {
        if ($_serverEnvironment == "production") {
            include "gera_relatorio_pararelo_verifica.php";
        }
    }

    if (count($msg_erro_form["msg"]) > 0) {
    ?>
        <div class="alert alert-error">
            <h4><?=implode("<br />", $msg_erro_form["msg"])?></h4>
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
                    <div class='control-group <?=(in_array("ano", $msg_erro_form["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='ano'>Ano</label>
                        <div class='controls controls-row'>
                            <div class='span8'>
                                <h5 class='asteristico'>*</h5>
                                <select name="ano" id="ano" class="span12">
                                    <option value=""></option>
                                    <?php

                                    for($i = 2003; $i <= date("Y"); $i++){
                                        $selected = ($ano == $i) ? "SELECTED" : "";
                                        echo "<option value='".$i."' {$selected}>{$i}</option>";
                                    }

                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            <div class='span4'>
                <div class='control-group <?=(in_array("mes", $msg_erro_form["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='mes'>Mês</label>
                    <div class='controls controls-row'>
                        <div class='span8'>
                            <h5 class='asteristico'>*</h5>
                            <select name="mes">
                                <option value=""></option>
                                <?php
                                    $meses = array(
                                            1 => "Janeiro",
                                            2 => "Fevereiro",
                                            3 => "Março",
                                            4 => "Abril",
                                            5 => "Maio",
                                            6 => "Junho",
                                            7 => "Julho",
                                            8 => "Agosto",
                                            9 => "Setembro",
                                            10 => "Outubro",
                                            11 => "Novembro",
                                            12 => "Dezembro"
                                        );

                                    foreach ($meses as $mes_num => $mes_desc) {
                                        $selected = ($mes_num == $mes) ? "SELECTED" : "";
                                        echo "<option value='{$mes_num}' {$selected}>{$mes_desc}</option>";
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
            <div class="span3">
                <div class='control-group'>
                    <label class='control-label' for='mes'>Estado</label>
                    <div class='controls controls-row'>
                        <div class='span8'>
                            <select name="estado">
                                <?php
                                    $selectEstado = "SELECT * FROM tbl_estado WHERE pais = 'BR' AND visivel = 't' ORDER BY NOME ASC";
                                    $resEstado = pg_query($con, $selectEstado);
                                    $estados = pg_fetch_all($resEstado);
                                                        echo "<option value='todos_estados'>Todos os estados</option>";
                                    foreach ($estados as $estado) {
                                        $selected = "";
                                        if ($zestado == $estado['estado'])
                                            $selected = "selected";
                                        echo "<option value='{$estado['estado']}' {$selected} >{$estado['nome']}</option>";
                                    }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <p>
            <br/>
            <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));$('#loading').show();$('#loading-block').show();">Pesquisar</button>
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
        </p>

        <br/>

    </form>

    <?php
        echo "
            <div class='alert tac' id='msg_carregando' style='display:none;'>
                    <b>Aguarde a geração do arquivo.</b> <br />  <br />
                <img src='imagens/ajax-carregando.gif' />
            </div>";

if (strlen($btn_acao) > 0 && count($msg_erro_form["msg"]) == 0) {


    

    if ($_serverEnvironment == "production") {

        $arquivoPrincipal  = "/tmp/integracao_power_bi_" . $login_admin . ".csv";
        $arquivo_zip   = "xls/integracao_power_bi_" . $login_admin . ".zip";
        $arquivo_link  = "xls/integracao_power_bi_" . $login_admin . ".zip";
		$arquivoPecas       = "xls/power_bi_pecas_"         . $login_admin . ".csv";
        $arquivoIntervencao = "xls/power_bi_intervencao_"   . $login_admin . ".csv";
        $arquivoInteracao   = "xls/power_bi_interacao_"     . $login_admin . ".csv";
        $arquivoTroca       = "xls/power_bi_troca_produto_" . $login_admin . ".csv";

    } else {

        $arquivoPrincipal   = "xls/power_bi_principal_"     . $login_admin . ".csv";
        $arquivoPecas       = "xls/power_bi_pecas_"         . $login_admin . ".csv";
        $arquivoIntervencao = "xls/power_bi_intervencao_"   . $login_admin . ".csv";
        $arquivoInteracao   = "xls/power_bi_interacao_"     . $login_admin . ".csv";
        $arquivoTroca       = "xls/power_bi_troca_produto_" . $login_admin . ".csv";

        $arquivo_zip   = "xls/integracao_power_bi_" . $login_admin . ".zip";

        #$arquivo_link  = $arquivo_nome;
    }

        if (is_file($arquivo_zip)) {
            unlink($arquivo_zip);
        }

        if (is_file($arquivo_nome)) {
            unlink($arquivo_nome);
        }

    $arquivo_nome  = "xls/power_bi_" . $login_admin . ".csv";
    
    if (empty($msg_erro_form)) {
        $count = 0;
        if (strlen($mes) > 0 AND strlen($ano) > 0) {
            $data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
            $data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
        }

        if (isset($_REQUEST['estado']) && $_REQUEST['estado'] != 'todos_estados') {
            $where_estado = " AND tbl_posto_fabrica.contato_estado = '{$_REQUEST['estado']}' ";
        }
        /** Tabela Principal */

        if (!empty($data_inicial) && !empty($data_final)) {
            $aux_dt_ini = $data_inicial;
            $aux_dt_fin = $data_final;
            $datas = relatorio_data("$data_inicial","$data_final");         
    
            foreach($datas as $cont => $data_pesquisa){
                $data_inicial = $data_pesquisa[0];
                $data_final = $data_pesquisa[1];
                $data_final = str_replace(' 23:59:59', '', $data_final);

                if ($cont == 0) {
                    $tempTableCreate = "create temp table oss_britania as";
                } else if ($cont > 0) {
                    $tempTableCreate = "INSERT INTO oss_britania ";
                }
                $sql_os =  "
                    $tempTableCreate
                    SELECT tbl_os.sua_os,
                            tbl_os.os, 
                            tbl_os.fabrica,
                            tbl_os.consumidor_revenda, 
                            tbl_status_checkpoint.descricao AS status_os,
                            CASE WHEN tbl_os.os_reincidente IS TRUE THEN 'Reincidente'
                                 WHEN tbl_os.data_fechamento IS NULL AND tbl_os.excluida IS NOT TRUE AND tbl_os.cancelada IS NOT TRUE AND (tbl_os.data_abertura::date + INTERVAL '25 days') < current_date THEN 'OS aberta há mais de 25 dias sem data de fechamento'
                                 WHEN tbl_os_status.status_os IN (72,87,116,120,122,140,141) THEN 'OS com intervenção da fábrica. Aguardando liberação'
                                 WHEN tbl_os_status.status_os = 65 THEN 'OS com intervenção da fábrica. Reparo na fábrica'
                                 WHEN tbl_os.data_fechamento IS NULL AND tbl_os_status.status_os IN (64,73,88,117) THEN 'OS liberada pela fábrica'
                                 WHEN tbl_os.cancelada NOTNULL THEN 'OS cancelada pela fábrica'
                                 WHEN tbl_os_troca.ressarcimento IS TRUE THEN 'OS com ressarcimento financeiro'
                                 WHEN tbl_os.data_fechamento IS NULL AND tbl_os.excluida IS NOT TRUE AND tbl_os.cancelada IS NOT TRUE AND (tbl_os.data_abertura::date + INTERVAL '25 days') < current_date AND tbl_os.os_reincidente IS TRUE THEN 'Os reincidente e aberta a mais de 25 dias'
                                 WHEN tbl_os_troca.os_troca NOTNULL AND tbl_os_troca.ressarcimento IS NOT TRUE THEN 'OS com Troca de Produto'
                                 WHEN tbl_os_status.status_os = 174 THEN 'OS com pendência de fotos'
                                 WHEN tbl_os_status.status_os = 175 THEN 'OS com intervenção de display'
                                 ELSE ''
                            END AS desc_status_os,
                            tbl_tipo_atendimento.descricao AS tipo_atendimento, 
                            tbl_tecnico.nome AS tecnico_responsavel, 
                            tbl_posto_fabrica.codigo_posto AS cod_posto, 
                            tbl_posto.nome AS nome_posto, 
                            TO_CHAR(tbl_os.data_nf, 'DD/MM/YYYY')  AS data_nf_compra, 
                            TO_CHAR(tbl_os.data_abertura, 'DD/MM/YYYY')  AS data_abertura, 
                            TO_CHAR(tbl_os.data_digitacao, 'DD/MM/YYYY') AS data_digitacao, 
                            TO_CHAR(tbl_os.data_conserto, 'DD/MM/YYYY')  AS data_conserto, 
                            TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento, 
                            TO_CHAR(tbl_os.finalizada,'DD/MM/YYYY') AS finalizada ,
                            data_abertura::date - tbl_os.data_nf::date AS dias_uso, 
                            CASE WHEN tbl_produto.radical_serie NOTNULL
                                THEN 
                                    REGEXP_REPLACE(REPLACE(tbl_produto.referencia, 'tbl_produto.radical_serie', ''),'\D','','g')
                                ELSE
                                    regexp_replace(tbl_produto.referencia,'\D','','g')
                            END AS cod_comercial, 
                            tbl_produto.referencia AS produto_referencia, 
                            tbl_produto.descricao AS produto_descricao, 
                            tbl_os.serie AS numero_serie, 
                            tbl_defeito_reclamado.descricao AS defeito_reclamado, 
                            tbl_defeito_constatado.descricao AS defeito_constatado, 
                            tbl_solucao.descricao AS solucao, 
                            CASE WHEN tbl_os.defeito_reclamado_descricao NOTNULL AND tbl_os.defeito_reclamado_descricao <> '' AND tbl_os.defeito_reclamado_descricao <> 'null'
                                THEN 
                                    tbl_os.defeito_reclamado_descricao
                                ELSE
                                    ''
                            END AS defeito_info, 
                            tbl_os.aparencia_produto AS aparencia_produto,
                            tbl_os.acessorios AS acessorios, 
                            tbl_os.consumidor_cidade, 
                            tbl_os.consumidor_estado, 
                            tbl_os.revenda_nome, 
                            tbl_os.revenda_cnpj, 
                            tbl_os.nota_fiscal,    
                            tbl_cidade.nome AS revenda_cidade, 
                            tbl_cidade.estado AS revenda_estado, 
                            tbl_os_extra.orientacao_sac AS informacao_sac,
                            replace(tbl_os.obs,'\"','') AS observacao,
                            tbl_os_produto.os_produto
                            FROM tbl_os 
                            JOIN tbl_produto ON (tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = $login_fabrica) 
                            JOIN tbl_revenda ON (tbl_revenda.revenda = tbl_os.revenda) 
                            JOIN tbl_cidade ON (tbl_cidade.cidade = tbl_revenda.cidade)
                            JOIN tbl_os_extra ON (tbl_os_extra.os = tbl_os.os)
                            JOIN tbl_status_checkpoint ON (tbl_status_checkpoint.status_checkpoint = tbl_os.status_checkpoint)
                            JOIN tbl_tipo_atendimento 
                            ON (tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
                            AND tbl_tipo_atendimento.fabrica = tbl_os.fabrica)
                            JOIN tbl_posto_fabrica ON (tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica)
                            JOIN tbl_posto 
                            ON (tbl_posto.posto = tbl_posto_fabrica.posto)
                            JOIN tbl_defeito_reclamado ON tbl_os.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
                            LEFT JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
                            LEFT JOIN tbl_solucao ON tbl_os.solucao_os = tbl_solucao.solucao 
                            LEFT JOIN tbl_tecnico ON tbl_os.tecnico = tbl_tecnico.tecnico AND tbl_tecnico.fabrica = $login_fabrica
                            LEFT JOIN tbl_os_status ON tbl_os.os = tbl_os_status.os AND tbl_os_status.fabrica_status = $login_fabrica
                            LEFT JOIN tbl_os_troca ON tbl_os.os = tbl_os_troca.os AND tbl_os_troca.fabric = $login_fabrica
                            LEFT JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
                            WHERE tbl_os.fabrica = 3
                            AND tbl_os.posto <> 6359
                            AND tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final'
                            {$where_estado};";
                            
                $res_os = pg_query($con,$sql_os);
                if (pg_last_error()) {
                    $msg_erro_form["msg"][] = "Erro ao realizar consulta ";
                    $msg_erro = "Erro ao realizar consulta ";
                }
            }
        }

        if (empty($msg_erro_form)) {

            $query = "SELECT    sua_os,
                                consumidor_revenda, 
                                status_os,
                                desc_status_os, 
                                tipo_atendimento, 
                                tecnico_responsavel, 
                                cod_posto, 
                                nome_posto, 
                                data_nf_compra, 
                                data_abertura, 
                                data_digitacao, 
                                data_conserto, 
                                data_fechamento, 
                                finalizada ,
                                dias_uso, 
                                cod_comercial, 
                                produto_referencia, 
                                produto_descricao, 
                                numero_serie, 
                                defeito_reclamado, 
                                defeito_constatado, 
                                solucao, 
                                defeito_info, 
                                aparencia_produto,
                                acessorios, 
                                consumidor_cidade, 
                                consumidor_estado, 
                                revenda_nome, 
                                revenda_cnpj, 
                                nota_fiscal,    
                                revenda_cidade, 
                                revenda_estado, 
                                informacao_sac,
                                observacao
                                FROM oss_britania;";
            $res = pg_query($con, $query);
        
            if(pg_num_rows($res) == 0){
                $count++;
            }else{
                $body = [];

                for ($i = 0; $i < pg_num_rows($res); $i++) {
                    ini_set(memory_limit, 10000000000);
                    ini_set(max_execution_time, pg_num_rows($res));
                    $linha = pg_fetch_assoc($res);
                    $linha['informacao_sac'] = trim(str_replace([";","\r\n"], ' ', $linha['informacao_sac']));
                    array_push($body, $linha);
                }
        
                $cabecalhos = [
                    "default" => ["Sua OS", "Consumidor/Revenda", "Status da OS", "Situação da OS", "Tipo de Atendimento", "Técnico Responsável", "Código Posto", "Nome Posto", "Data NF Compra", "Data Abertura", "Data Digitação", "Data Conserto", "Data Fechamento", "Data Finalizada", "Dias de Uso", "Código Comercial", "Produto Referência", "Produto Descrição", "Número de Série", "Defeito Reclamado", "Defeito Constatado", "Solução", "Informações Sobre o Defeito", "Aparencia Geral do Aparelho/Produto", "Acessórios Deixados junto com o Aparelho", "Consumidor Cidade", "Consumidor Estado", "Nome Revenda", "CNPJ Revenda", "Nota Fiscal", "Cidade Revenda", "Estado Revenda", "Informações do SAC ao Posto Autorizado", "Observação"],
                ];
        
                gerarcsv($arquivoPrincipal, $cabecalhos['default'], $body);
            }

            /** Tabela Interacao  */

            $query = "SELECT oss_britania.sua_os,
                            ROW_NUMBER() OVER(partition by oss_britania.sua_os ORDER BY oss_britania.sua_os) as num_interacao,
                            TO_CHAR(tbl_os_interacao.data, 'DD/MM/YYYY') AS data_interacao, 
                            tbl_os_interacao.comentario AS mensagem,
                            tbl_admin.nome_completo AS nome_admin
                            FROM oss_britania
                            JOIN tbl_os_interacao ON tbl_os_interacao.os = oss_britania.os AND tbl_os_interacao.fabrica = oss_britania.fabrica
                            LEFT JOIN tbl_admin   ON tbl_admin.admin = tbl_os_interacao.admin AND tbl_admin.fabrica = $login_fabrica
                            WHERE tbl_os_interacao.fabrica = $login_fabrica
                            AND tbl_os_interacao.data >= '$aux_dt_ini'
                            GROUP BY sua_os, tbl_os_interacao.data,tbl_os_interacao.comentario,tbl_admin.nome_completo;";

            $res = pg_query($con, $query);

            if(pg_num_rows($res) == 0){
                $count++;
            }else{
                $body = [];

                for ($i = 0; $i < pg_num_rows($res); $i++) {

                    $linha = pg_fetch_assoc($res);
                    $linha['mensagem'] = trim(str_replace([";","\r\n"], ' ', $linha['mensagem']));
                    array_push($body, $linha);
                }

                $cabecalhos = [

                    "default" => ["Sua OS", "N° Interação", "Data", "Mensagem", "Admin"]
                ];

                gerarcsv($arquivoInteracao, $cabecalhos['default'], $body);
            }
            /** Tabela de Pecas */

            $query = "SELECT oss_britania.sua_os,
                            tbl_peca.referencia AS peca_referencia,
                            tbl_peca.descricao AS peca_descricao,
                            tbl_peca.familia_peca,
                            tbl_os_item.qtde,
                            to_char(tbl_os_item.digitacao_item,'DD/MM/YYYY') AS data_digitacao,
                            tbl_defeito.descricao AS defeito_peca,
                            tbl_servico_realizado.descricao AS servico_realizado,
                            tbl_os_item.pedido AS numero_pedido,
                            to_char(tbl_pedido.data,'DD/MM/YYYY') AS data_pedido,
                            tbl_status_pedido.descricao AS status_pedido,
                            tbl_faturamento.nota_fiscal AS nf_fabricante,
                            to_char(tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao,
                            CASE WHEN tbl_faturamento.cancelada IS NULL THEN 'ATIVO' ELSE 'CANCELADO' END AS status_nf,
                            tbl_pedido_cancelado.motivo AS motivo_cancelamento
                            FROM oss_britania 
                            JOIN tbl_os_item ON tbl_os_item.os_produto = oss_britania.os_produto AND tbl_os_item.fabrica_i = $login_fabrica
                            JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = $login_fabrica
                            JOIN tbl_pedido ON tbl_pedido.pedido = tbl_os_item.pedido AND tbl_pedido.fabrica = $login_fabrica
                            JOIN tbl_defeito ON (tbl_defeito.defeito = tbl_os_item.defeito AND tbl_defeito.ativo IS TRUE AND tbl_defeito.fabrica = $login_fabrica)
                            JOIN tbl_servico_realizado ON (tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.ativo IS TRUE AND tbl_servico_realizado.fabrica = $login_fabrica)
                            LEFT JOIN tbl_status_pedido ON (tbl_status_pedido.status_pedido = tbl_pedido.status_pedido)
                            LEFT JOIN tbl_faturamento_item ON tbl_os_item.pedido = tbl_faturamento_item.pedido AND tbl_os_item.peca = tbl_faturamento_item.peca
                            LEFT JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento AND tbl_faturamento.fabrica = $login_fabrica
                            LEFT JOIN tbl_pedido_cancelado ON tbl_pedido_cancelado.pedido_item = tbl_os_item.pedido_item AND tbl_pedido_cancelado.fabrica = $login_fabrica;";

            $res = pg_query($con, $query);

            if(pg_num_rows($res) == 0){
                $count++;
            }else{
                $body = [];

                for ($i = 0; $i < pg_num_rows($res); $i++) {

                    $linha = pg_fetch_assoc($res);
                    array_push($body, $linha);
                }

                $cabecalhos = [
                    "default" => ["Sua OS", "Peça Referência", "Peça Descrição", "Família Peça", "Quantidade", "Data de Digitação", "Defeito Peça", "Serviço", "Número do Pedido", "Data Pedido", "Status do Pedido", "Número do Pedido", "Data Pedido", "Status Pedido", "NF do Fabricante", "Emissão", "Status NF", "Motivo Cancelamento"]
                ];

                gerarcsv($arquivoPecas, $cabecalhos['default'], $body);
            }
            /** Tabela Troca Produto */

            $query = "SELECT oss_britania.sua_os,
                            tbl_admin.nome_completo AS responsavel,
                            tbl_os_troca.setor AS setor,
                            to_char(tbl_os_troca.data,'DD/MM/YYYY') AS troca_data,
                            tbl_os_troca.situacao_atendimento AS status_atendimento,
                            tbl_os_troca.situacao_atendimento AS situacao_atendimento,
                            CASE 
                                WHEN produto_trocado.referencia NOTNULL THEN produto_trocado.referencia
                                WHEN produto_troca.referencia NOTNULL THEN produto_troca.referencia
                                ELSE ''
                            END AS trocado_por_codigo,
                            CASE 
                                WHEN produto_trocado.descricao NOTNULL THEN produto_trocado.descricao
                                WHEN produto_troca.descricao NOTNULL THEN produto_troca.descricao
                                ELSE ''
                            END AS trocado_por_descricao,
                            tbl_causa_troca.descricao AS causa_troca,
                            tbl_os_troca.obs_causa AS obs
                            FROM oss_britania
                            JOIN tbl_os_troca ON tbl_os_troca.os = oss_britania.os AND tbl_os_troca.fabric = oss_britania.fabrica
                            JOIN tbl_admin ON (tbl_admin.admin = tbl_os_troca.admin AND tbl_admin.fabrica = $login_fabrica)
                            JOIN tbl_causa_troca ON (tbl_causa_troca.causa_troca = tbl_os_troca.causa_troca AND tbl_causa_troca.fabrica = $login_fabrica)
                            LEFT JOIN tbl_produto AS produto_trocado ON produto_trocado.produto = tbl_os_troca.produto AND produto_trocado.fabrica_i = $login_fabrica
                            LEFT JOIN tbl_peca AS produto_troca ON produto_troca.peca = tbl_os_troca.peca AND produto_troca.fabrica = $login_fabrica
                            WHERE tbl_os_troca.fabric = $login_fabrica;";

            $res   = pg_query($con, $query);

            if(pg_num_rows($res) == 0){
                $count++;
            }else{
                $body = [];

                for ($i = 0; $i < pg_num_rows($res) ; $i++) {
                    $linha = pg_fetch_assoc($res);
                    array_push($body, $linha);
                }

                $cabecalhos = [
                    "default" => ["Sua OS", "Responsavel", "Setor", "Data", "Situação do atendimento", "Situação do atendimento", "Trocado por (código)", "Trocado por (Descrição)", "Causa da Troca", "OBS"]
                ];

                gerarcsv($arquivoTroca, $cabecalhos['default'], $body);
            }
            /** Tabela Intervenções */

            $query = "SELECT oss_britania.sua_os,
                            ROW_NUMBER() OVER(partition by oss_britania.sua_os ORDER BY oss_britania.sua_os) as num_interacao,
                            TO_CHAR(tbl_os_status.data, 'DD/MM/YYYY') AS data_intervencao,
                            tbl_status_os.descricao AS status,
                            tbl_os_status.observacao AS justificativa,
                            tbl_admin.nome_completo AS nome_admin
                            FROM oss_britania
                            JOIN tbl_os_status ON tbl_os_status.os = oss_britania.os AND tbl_os_status.fabrica_status = oss_britania.fabrica
                            JOIN tbl_status_os ON tbl_os_status.status_os = tbl_status_os.status_os
                            LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_os_status.admin AND tbl_admin.fabrica = $login_fabrica
                            WHERE tbl_os_status.fabrica_status = $login_fabrica
                            GROUP BY sua_os, tbl_status_os.descricao,tbl_os_status.data,tbl_os_status.observacao ,tbl_admin.nome_completo;";

            $res   = pg_query($con, $query);

            if(pg_num_rows($res) == 0){
                $count++;
            }else{
                $body = [];

                for ($i = 0; $i < pg_num_rows($res) ; $i++) {
                    $linha = pg_fetch_assoc($res);
                    array_push($body, $linha);
                }

                $cabecalhos = [
                    "default" => ["Sua OS", "N° Interação", "Data", "Tipo/Status", "Justificativa", "Admin"]
                ];

                gerarcsv($arquivoIntervencao, $cabecalhos['default'], $body);
            }
            if($count == 5){
                echo "<div class='alert alert-warning'><h4>Nenhum Resultado Encontrado</h4></div>";
            }else{
                if (pg_last_error() || !empty($msg_erro_form)) {
                    $msg_erro_form["msg"][] = "Erro ao montar arquivo ";
                    $msg_erro = "Erro ao montar arquivo ";
                    echo "<div class='tac alert alert-error'>";
                        echo "<h4>Erro ao montar arquivo</h4>";
                    echo "</div>";
                    echo "<script>document.getElementById('msg_carregando').style.display='none';</script>";
                } else {
                    system("zip $arquivo_zip $arquivoPrincipal $arquivoInteracao $arquivoIntervencao $arquivoTroca $arquivoPecas > /dev/null");

                    $h = popen("cd /xls && zip $arquivo_zip $arquivoPrincipal","r");
                    pclose($h);
                    system("cd xls/ && zip $arquivo_zip $arquivo_nome2 > /dev/null");
                    echo "<div class='tac'>";
                        echo "<input type='button' class='btn' value='Download do Arquivo' onclick=\"window.location='".$arquivo_zip."'\">";
                    echo "</div>";
                    echo "<script>document.getElementById('msg_carregando').style.display='none';</script>";
                    ob_flush();
                    flush();
                }
            }
        }
    } 
}

echo "<br /> <br /> <br />";

function gerarcsv($filePath, $headArray, $bodyArray) {

    $body = "";

    $head = implode(';', $headArray);
    $head .= " \n";

    $body = array_reduce($bodyArray, function($acumulator, $actualValue) {

        $acumulator .= implode(';', $actualValue);
        $acumulator .= " \n";

        return $acumulator;

    }, '');
    
    try {

        $file = fopen($filePath, 'w+');
        fwrite($file, ($head));
        fwrite($file, ($body));
        fclose($file);

        return $filePath;

    } catch (\Exception $e) {
        return $e;
    }
}

include "rodape.php";

?>
