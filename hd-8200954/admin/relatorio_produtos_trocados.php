<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["btn_acao"] == "submit") {

    $os                 = filter_input(INPUT_POST,"os");
    $resposnsavel       = filter_input(INPUT_POST,"responsavel");
    $data_inicial       = filter_input(INPUT_POST,'data_inicial');
    $data_final         = filter_input(INPUT_POST,'data_final');
    $codigo_cliente     = filter_input(INPUT_POST,'codigo_cliente');
    $nome_cliente       = filter_input(INPUT_POST,"nome_cliente");
    $descricao_posto    = filter_input(INPUT_POST,'descricao_posto');
    $codigo_posto       = filter_input(INPUT_POST,"codigo_posto");
    $produto_referencia = filter_input(INPUT_POST,'produto_referencia');
    $produto_descricao  = filter_input(INPUT_POST,"produto_descricao");
    $causa_troca        = filter_input(INPUT_POST,"causa_troca");

    if (empty($os)) {
        if(strlen($data_inicial)>0 and $data_inicial <> "dd/mm/aaaa"){
            $xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
            $xdata_inicial = str_replace("'","",$xdata_inicial);
        }else{
            $msg_erro["msg"][]    = traduz("Data Inicial Inválida");
            $msg_erro["campos"][] = "data_inicial";
        }

        if(strlen($data_final)>0 and $data_final <> "dd/mm/aaaa"){
            $xdata_final =  fnc_formata_data_pg(trim($data_final));
            $xdata_final = str_replace("'","",$xdata_final);
        }else{
            $msg_erro["msg"][]    = traduz("Data Final Inválida");
            $msg_erro["campos"][] = "data_final";
        }


        if(!count($msg_erro["msg"])){
            $dat = explode ("/", $data_inicial );//tira a barra
            $d = $dat[0];
            $m = $dat[1];
            $y = $dat[2];

            if(!checkdate($m,$d,$y)){
                $msg_erro["msg"][]    = traduz("Data Inválida");
                $msg_erro["campos"][] = traduz("data_inicial");
            }
        }
        if(!count($msg_erro["msg"])){
            $dat = explode ("/", $data_final );//tira a barra
            $d = $dat[0];
            $m = $dat[1];
            $y = $dat[2];
            if(!checkdate($m,$d,$y)){
                $msg_erro["msg"][]    = traduz("Data Inválida");
                $msg_erro["campos"][] = traduz("data_final");
            }
        }

        if($xdata_inicial > $xdata_final) {
            $msg_erro["msg"][]    = traduz("Data Inicial maior que final");
            $msg_erro["campos"][] = traduz("data_inicial");
        }

        if(strlen(trim($data_inicial))>0 and strlen(trim($data_final))>0){
            $whereData = " AND ot.data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59'";
        }

        if (!empty($produto_referencia) && !empty($produto_descricao)) {
            $whereProduto = " AND prod.referencia = '$produto_referencia' AND prod.descricao = '$produto_descricao'";
        }

        if (!empty($responsavel)) {
            $whereResponsavel = " AND ot.admin = $responsavel";
        }

        if (!empty($codigo_cliente)) {
            $retirar = array(".", '-');
            $cpf_consumidor = str_replace($retirar, "", $codigo_cliente);

            $whereCpf = " AND o.consumidor_cpf = '$cpf_consumidor'";
        }

        if (!empty($nome_cliente)) {
            $whereNome = " AND o.consumidor_nome ILIKE '%$nome_cliente%'";
        }

        if(strlen(trim($codigo_posto))>0){
            $wherePosto =  " AND pf.codigo_posto = '$codigo_posto'";
        }

        if(strlen(trim($causa_troca))>0){
            $whereCausaTroca = " AND ct.causa_troca = {$causa_troca}";
        }
    }

    if(strlen(trim($os))>0){
        $whereOS .= " AND o.sua_os = '$os'\n";
    }

    if(in_array($login_fabrica, array(104,164))){

        $campos_revenda = ", r.cnpj AS revenda_cnpj, r.nome AS revenda_nome, pf.contato_cidade AS posto_cidade, pf.contato_estado AS posto_estado ";
        $left_join_revenda = " LEFT JOIN tbl_revenda r ON r.revenda = o.revenda ";

    }

    if($login_fabrica == 164){
        $campos_pecas = ",(
                            SELECT 
                            ARRAY_TO_STRING(ARRAY_AGG(tbl_peca.referencia || ' - ' ||   tbl_peca.descricao), '<br />')
                            FROM tbl_os_item                
                            JOIN tbl_peca USING(peca) 
                            WHERE tbl_os_item.os_produto = op.os_produto
							AND tbl_peca.produto_acabado is not true
                        ) AS peca_descricao";
    }

    if (in_array($login_fabrica, array(162))) {
        $campos_nf = ", hc.descricao as classificacao_descricao,
                        o.nota_fiscal as cli_nota_fiscal, 
                        TO_CHAR(o.data_nf,'DD/MM/YYYY') as data_nota_fiscal,
                        o.revenda_nome ";
        $left_join_nf = " LEFT JOIN tbl_hd_chamado hd ON hd.hd_chamado = o.hd_chamado
                            AND hd.fabrica = {$login_fabrica}
                        LEFT JOIN tbl_hd_classificacao hc ON hc.hd_classificacao = hd.hd_classificacao
                            AND hc.fabrica = {$login_fabrica} ";
    }

    if (in_array($login_fabrica, array(169,170))) {
        $whereRessarcimento = "AND (r.ressarcimento IS NULL OR r.aprovado IS NOT NULL)";
    }

    if ($login_fabrica == 177){
        $join_atendimento = " JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = $login_fabrica ";
        $campo_atendimento = " ta.descricao AS tipo_atendimento,";
    }

    if(count($msg_erro)==0){
        if (in_array($login_fabrica, array(169,170))) {
            $sql = "
                SELECT 
                    xxxx.*
                FROM (
                    SELECT 
                        xxx.*,
                        p.produto AS produto_troca,
                        p.referencia AS produto_troca_referencia,
                        p.descricao AS produto_troca_descricao,
                        pi.preco AS produto_troca_preco,
                        TO_CHAR(r.previsao_pagamento,'DD/MM/YYYY') AS previsao_pagamento,
                        TO_CHAR(r.liberado,'DD/MM/YYYY') AS data_pagamento,
                        r.valor_original AS valor_ressarcimento,
                        a.nome_completo AS responsavel,
                        ct.descricao AS causa_troca,
                        (SELECT ult.liberada FROM tbl_auditoria_os ult WHERE ult.os = xxx.os AND LOWER(observacao) ~ 'os em auditoria de troca de produto|os em auditoria de ressarcimento' ORDER BY data_input DESC LIMIT 1) AS ultima_auditoria
                    FROM (
                        SELECT 
                            xx.*
                        FROM (
                            SELECT 
                                x.*,
                                (SELECT ult.os_troca FROM tbl_os_troca ult WHERE ult.os = x.os ORDER BY ult.data DESC LIMIT 1) AS ultima_troca_id
                            FROM (
                                SELECT 
                                    ot.os_troca,
                                    ot.os,
                                    TO_CHAR(ot.data,'DD/MM/YYYY') AS data_troca,
                                    p.nome AS posto_nome,
                                    o.consumidor_nome,
									o.sua_os,
                                    o.consumidor_cpf,
                                    TO_CHAR(o.data_abertura,'DD/MM/YYYY') AS data_abertura,
                                    prod.referencia_fabrica AS produto_referencia_fabrica,
                                    prod.produto,
                                    prod.referencia AS produto_referencia,
                                    prod.descricao AS produto_descricao,
                                    prod.valor_troca AS valor_base_troca
                                    {$campos_revenda}
                                    {$campos_nf}
                                FROM tbl_os_troca ot
                                INNER JOIN tbl_os o ON o.os = ot.os AND o.fabrica = {$login_fabrica}
                                INNER JOIN tbl_os_produto op ON op.os = o.os
                                INNER JOIN tbl_produto prod ON prod.produto = op.produto AND prod.fabrica_i = {$login_fabrica}
                                INNER JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = {$login_fabrica}
                                INNER JOIN tbl_posto p ON p.posto = pf.posto
                                {$left_join_revenda}
                                {$left_join_nf}
                                WHERE ot.fabric = {$login_fabrica}
                                AND o.excluida IS NOT TRUE
                                {$whereData}
                                {$whereProduto}
                                {$whereCpf}
                                {$whereNome}
                                {$whereOS}
                                {$wherePosto}
                                ORDER BY ot.os, ot.data DESC
                            ) x
                        ) xx
                        WHERE xx.os_troca = xx.ultima_troca_id
                    ) xxx
                    INNER JOIN tbl_os_troca ot ON ot.os_troca = xxx.os_troca AND ot.fabric = {$login_fabrica}
                    LEFT JOIN tbl_peca p ON p.peca = ot.peca AND p.fabrica = {$login_fabrica}
                    LEFT JOIN tbl_os_produto op ON op.os = ot.os
                    LEFT JOIN tbl_os_item oi ON oi.os_produto = op.os_produto AND oi.peca = p.peca AND oi.servico_realizado = (SELECT sr.servico_realizado FROM tbl_servico_realizado sr WHERE sr.fabrica = {$login_fabrica} AND sr.troca_produto IS TRUE)
                    LEFT JOIN tbl_pedido_item pi ON pi.pedido_item = oi.pedido_item
                    LEFT JOIN tbl_ressarcimento r ON r.os_troca = ot.os_troca AND r.fabrica = {$login_fabrica}
                    INNER JOIN tbl_admin a ON a.admin = ot.admin AND a.fabrica = {$login_fabrica}
                    INNER JOIN tbl_causa_troca ct ON ct.causa_troca = ot.causa_troca AND ct.fabrica = {$login_fabrica}
                    {$whereResponsavel}
                    {$whereRessarcimento}                    
                ) xxxx
                WHERE xxxx.ultima_auditoria IS NOT NULL
            ";
        } else {
            $sql = "
                SELECT
                    xxx.*,
                    p.referencia AS produto_troca_referencia,
                    p.descricao AS produto_troca_descricao,
                    TO_CHAR(r.previsao_pagamento,'DD/MM/YYYY') AS previsao_pagamento,
                    TO_CHAR(r.liberado,'DD/MM/YYYY') AS data_pagamento,
                    r.valor_original AS valor_ressarcimento,
                    a.nome_completo AS responsavel,
                    ct.descricao AS causa_troca                    
                FROM (
                    SELECT 
                        xx.*
                    FROM (
                        SELECT 
                            x.*,
                            (SELECT ult.os_troca FROM tbl_os_troca ult WHERE ult.os = x.os ORDER BY ult.data DESC LIMIT 1) AS ultima_troca_id
                        FROM (
                            SELECT 
                                ot.os_troca,
                                ot.os,
                                p.nome AS posto_nome,
								o.consumidor_nome,
								o.sua_os,
                                o.consumidor_cpf,
                                {$campo_atendimento}
                                TO_CHAR(o.data_abertura,'DD/MM/YYYY') AS data_abertura,
                                TO_CHAR(ot.data,'DD/MM/YYYY') AS data_troca,
                                prod.referencia_fabrica AS produto_referencia_fabrica,
                                prod.referencia AS produto_referencia,
                                prod.descricao AS produto_descricao,
                                prod.valor_troca AS valor_base_troca
                                {$campos_revenda}
                                {$campos_nf}
                                {$campos_pecas}
                            FROM tbl_os_troca ot
                            INNER JOIN tbl_os o ON o.os = ot.os AND o.fabrica = {$login_fabrica}
                            INNER JOIN tbl_os_produto op ON op.os = o.os                            
                            INNER JOIN tbl_produto prod ON prod.produto = op.produto AND prod.fabrica_i = {$login_fabrica}
                            INNER JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = {$login_fabrica}
                            INNER JOIN tbl_posto p ON p.posto = pf.posto
                            {$left_join_revenda}
                            {$join_atendimento}
                            {$left_join_nf}
                            WHERE ot.fabric = {$login_fabrica}
                            AND o.excluida IS NOT TRUE
                            {$whereData}
                            {$whereProduto}
                            {$whereCpf}
                            {$whereNome}
                            {$whereOS}
                            {$wherePosto}
                            ORDER BY ot.os, ot.data DESC
                        ) x
                    ) xx
                    WHERE xx.os_troca = xx.ultima_troca_id
                ) xxx
                INNER JOIN tbl_os_troca ot ON ot.os_troca = xxx.os_troca AND ot.fabric = {$login_fabrica}
                LEFT JOIN tbl_peca p ON p.peca = ot.peca AND p.fabrica = {$login_fabrica}
                LEFT JOIN tbl_ressarcimento r ON r.os_troca = ot.os_troca AND r.fabrica = {$login_fabrica}                
                INNER JOIN tbl_admin a ON a.admin = ot.admin AND a.fabrica = {$login_fabrica}
                INNER JOIN tbl_causa_troca ct ON ct.causa_troca = ot.causa_troca AND ct.fabrica = {$login_fabrica}
                {$whereResponsavel}
                {$whereRessarcimento}
                {$whereCausaTroca}
            ";
        }

        //die(nl2br($sql));

        $resSubmit = pg_query($con,$sql);

        if(strlen(trim(pg_last_error($con)))>0){
            $msg_erro .= traduz("Erro ao pesquisar. ");
        }
    }
}

$layout_menu = "callcenter";
$title = traduz("RELATÓRIO DE PRODUTOS TROCADOS");

include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable",
    "alphanumeric"
);

include("plugin_loader.php");
?>

<script type="text/javascript">


function AbreCallcenter(data_inicial,data_final,produto,natureza,status,tipo,posto,atendente,tipo_data,tipo_cliente, motivo_atendimento,linhaDeProduto,tipo_atendimento,origem){
    janela = window.open("callcenter_relatorio_atendimento_callcenter.php?data_inicial=" +data_inicial+ "&data_final=" +data_final+ "&produto=" +produto+ "&natureza=" +natureza+ "&status=" +status+"&tipo="+tipo+"&atendente="+atendente+"&posto="+posto+"&tipo_data="+tipo_data+"&motivo_atendimento="+motivo_atendimento+"&linhaDeProduto="+linhaDeProduto+"&tipo_atendimento="+tipo_atendimento+"&tipo_cliente="+tipo_cliente+"&origem="+origem, "Callcenter",'scrollbars=yes,width=750,height=450,top=315,left=0');
    janela.focus();
}

function retorna_produto (retorno) {
    $("#produto_referencia").val(retorno.referencia);
    $("#produto_descricao").val(retorno.descricao);
}

function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
    $("#descricao_posto").val(retorno.nome);
}

$(function() {
    $.datepickerLoad(Array("data_final", "data_inicial"));
    $.autocompleteLoad(Array("produto", "posto"));
    Shadowbox.init();

    $("span[rel=lupa]").click(function () {
        $.lupa($(this));
    });

    <?php if (!in_array($login_fabrica,[180, 181, 182])) { ?>
    $("#codigo_cliente").mask("999.999.999-99");
    <?php } ?>
    $("#os").numeric();
});

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

<!-- <script type="text/javascript" src="js/grafico/highcharts.js"></script> -->
<script type="text/javascript" src="js/novo_highcharts.js"></script>
<script type="text/javascript" src="js/modules/exporting.js"></script>

<div class="row">
    <b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios ')?></b>
</div>
<form name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>
    <div class='titulo_tabela '><?=traduz('Parâmetros de Pesquisa')?></div>
    <br/>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='os'>OS</label>
                <div class='controls controls-row'>
                    <div class='span8'>
                            <input type="text" name="os" id="os"  maxlength="10" class='span12' value= "<?=$os?>">
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='responsavel'><?=traduz('Responsável pela troca')?></label>
                <div class='controls controls-row'>
                    <div class='span8'>
                        <select id="responsavel" name="responsavel">
                            <option value=""><?=traduz('SELECIONE')?></option>
                            <?php
                            $sql_busca = "
                                SELECT  admin,
                                        nome_completo
                                FROM    tbl_admin
                                WHERE   fabrica = $login_fabrica
                                AND     ativo IS TRUE
                            ";
                            $res_busca = pg_query($con,$sql_busca);

                            foreach (pg_fetch_all($res_busca) as $resultado) {
                            ?>
                            <option value="<?=$resultado['admin']?>" <?=($resultado['admin'] == $responsavel) ? "selected" : ""?> style="text-transform: uppercase;" ><?=$resultado['nome_completo']?></option>
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
            <div class='control-group <?=(in_array("data_inicial", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_inicial'><?=traduz('Data Inicial')?></label>
                <div class='controls controls-row'>
                    <div class='span6'>
                        <h5 class='asteristico'>*</h5>
                            <input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("data_final", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_final'><?=traduz('Data Final')?></label>
                <div class='controls controls-row'>
                    <div class='span6'>
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
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='codigo_posto'><?=traduz('Código Posto')?></label>
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
                <label class='control-label' for='descricao_posto'><?=traduz('Nome Posto')?></label>
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
            <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='produto_referencia'><?=traduz('Código Produto')?></label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="text" name="produto_referencia" id="produto_referencia" class='span12' value="<?=$produto_referencia?>" >
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='produto_descricao'><?=traduz('Nome Produto')?></label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <input type="text" name="produto_descricao" id="produto_descricao" class='span12' value="<?=$produto_descricao?>" >&nbsp;
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
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
            <div class='control-group '>
                <label class='control-label' for='codigo_cliente'><?=traduz('CPF Cliente')?></label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="text" name="codigo_cliente" id="codigo_cliente" class='span12' value="<?=$codigo_cliente?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group '>
                <label class='control-label' for='nome_cliente'><?=traduz('Nome Cliente')?></label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <input type="text" name="nome_cliente" id="nome_cliente" class='span12' value="<?=$nome_cliente?>" >&nbsp;
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
                <label class='control-label' for='responsavel'>Causa Troca</label>
                <div class='controls controls-row'>
                    <div class='span8'>
                        <select id="causa_troca" name="causa_troca">
                            <option value="">Selecione</option>
                            <?php
                            $sql_busca = "
                                SELECT  descricao,
                                        causa_troca
                                FROM    tbl_causa_troca
                                WHERE   fabrica = $login_fabrica
                                AND     ativo IS TRUE
                                ORDER BY descricao
                            ";
                            $res_busca = pg_query($con,$sql_busca);

                            foreach (pg_fetch_all($res_busca) as $resultado) {
                            ?>
                            <option value="<?=$resultado['causa_troca']?>" <?=($resultado['causa_troca'] == $causa_troca) ? "selected" : ""?> style="text-transform: uppercase;" ><?=$resultado['descricao']?></option>
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
    <p><br/>
        <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));"><?=traduz('Pesquisar')?></button>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
    </p><br/>
</FORM>
<?php
if (isset($resSubmit)) {
    if (pg_num_rows($resSubmit) > 0) {
        $data     = date("d-m-Y-H:i");
        $fileName = "csv_produtos_trocados-{$data}.csv";
        $file     = fopen("/tmp/{$fileName}", "w");

        header('Content-Type: application/csv; charset=iso-8859-1');
        header('Content-Disposition: attachment; filename="/tmp/{$fileName}"');

        echo "<br />";
        $count = pg_num_rows($resSubmit);

        $rowspan = "";
        
        if($login_fabrica == 164){
            $rowspan = " rowspan='2' class='tac' ";
        }
        
        if (in_array($login_fabrica, array(169,170))) {
            $display_table = 'style="display: none;"';
            
            $table_produtos_trocados = array();
            $table_produtos_order = array();
            
            while ($row = pg_fetch_object($resSubmit)) {
                if (!array_key_exists($row->produto, $table_produtos_trocados)) {
                    $table_produtos_trocados[$row->produto] = array(
                        'produto'      => $row->produto,
                        'referencia'   => $row->produto_referencia,
                        'descricao'    => $row->produto_descricao,
                        'qtde_trocas'  => 1,
                        'valor_trocas' => ((!empty($row->produto_troca)) ? (double) $row->produto_troca_valor : (double) $row->valor_ressarcimento)
                    );
                    $table_produtos_order[$row->produto] = 1;
                } else {
                    $table_produtos_trocados[$row->produto]['qtde_trocas']  += 1;
                    $table_produtos_trocados[$row->produto]['valor_trocas'] += (!empty($row->produto_troca)) ? (double) $row->produto_troca_valor : (double) $row->valor_ressarcimento;
                    $table_produtos_order[$row->produto] += 1;
                }
            }
            
            array_multisort($table_produtos_order, SORT_DESC, $table_produtos_trocados);
            ?>
            
            <table id='callcenter_relatorio_atendimento_agrupado' class="table table-bordered table-hover table-fixed">
                <thead>
                    <tr class='titulo_coluna'>
                        <th><?=traduz('Produto referência')?></th>
                        <th><?=traduz('Produto descrição')?></th>
                        <th><?=traduz('Ocorrências')?></th>
                        <th><?=traduz('Custo total')?></th>
                        <th><?=traduz('Ações')?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total_ocorrencias = 0;
                    $total_custo_total = 0;
                    
                    foreach ($table_produtos_trocados as $i => $produto) {
                        $total_ocorrencias += $produto['qtde_trocas'];
                        $total_custo_total += $produto['valor_trocas'];
                        ?>
                        <tr>
                            <td><?=$produto['referencia']?></td>
                            <td><?=$produto['descricao']?></td>
                            <td class='tac'><?=$produto['qtde_trocas']?></td>
                            <td class='tar'><?=number_format($produto['valor_trocas'], 2, ',', '.')?></td>
                            <td class='tac'>
                                <button type="button" class="btn btn-small btn-info visualizar-os" data-produto-id="<?=$produto['produto']?>"><i class='icon-list icon-white'></i> Ordes de Serviço</button>
                            </td>
                        </tr>
                    <?php
                    }
                    ?>
                </tbody>
                <tfoot>
                    <tr class='titulo_coluna'>
                        <th colspan='2'>Total</th>
                        <th><?=$total_ocorrencias?></th>
                        <th><?=number_format($total_custo_total, 2, ',', '.')?></th>
                        <th>&nbsp;</th>
                    </tr>
                </tfoot>
            </table>
            
            <?php
            pg_result_seek($resSubmit, 0);
        }
        ?>
</div>
<table id="callcenter_relatorio_atendimento" class='table table-striped table-bordered table-hover table-fixed' <?=$display_table?> >
    <thead>
        <tr class="titulo_coluna">
            <th <?=$rowspan?>>OS</th>
            <?php if ($login_fabrica == 177){ ?>
                <th><?=traduz('Tipo Atendimento')?></th>
            <?php } ?>
            <?php if(in_array($login_fabrica, array(164))){ ?>
                <th <?=$rowspan?>><?=traduz('Observação troca de produto OS')?></th>
                <th <?=$rowspan?>><?=traduz('Nome do Posto')?></th>
            <?php } ?>
            <th <?=$rowspan?>><?=traduz('Data Abertura')?></th>
            <th <?=$rowspan?>><?=traduz('Data Troca')?></th>            
            <?php if(in_array($login_fabrica, array(162))){ ?>
                <th><?=traduz('Nota Fiscal')?></th>
                <th><?=traduz('Data Nota Fiscal')?></th>
                <th><?=traduz('Revenda')?></th>
                <th><?=traduz('Classificação')?></th>
                <th><?=traduz('Data Pagamento')?></th>
            <?php }else if ($login_fabrica != 171) { ?>
                <th <?=$rowspan?>><?=traduz('Previsão Pagamento')?></th>
            <?php } ?>
            <th <?=$rowspan?>><?=traduz('Nome Consumidor')?></th>
            <th <?=$rowspan?>><?=traduz('CPF Consumidor')?></th>
            <?php if (in_array($login_fabrica, [164])) { ?>
                <th <?=$rowspan?>><?=traduz('Nome Revenda')?></th>
                <th <?=$rowspan?>><?=traduz('CNPJ Revenda')?></th> 
            <?php } ?>
            <?php if(in_array($login_fabrica, array(171))){ ?>
            <th><?=traduz('Referência Fábrica')?></th>
            <?php }?>
            <?php if(in_array($login_fabrica, array(164))){ ?>
                <th <?=$rowspan?>><?=traduz('Referência Produto')?></th>
                <th <?=$rowspan?>><?=traduz('Descrição Produto')?></th>
                <th <?=$rowspan?>><?=traduz('Peças')?></th>
            <?php } ?>
            <?php if(in_array($login_fabrica, array(104))){ ?>
            <th><?=traduz('Cód. Produto')?></th>
            <th><?=traduz('Desc. Produto')?></th>
            <?php }else if(!in_array($login_fabrica, array(164))){ ?>
                <th><?=traduz('Produto')?></th>
            <?php } ?>
            <th <?=$rowspan?>><?=traduz('Causa Troca')?></th>
            <th <?=$rowspan?>><?=traduz('Responsável Troca')?></th>
            <?php if(in_array($login_fabrica, array(164))){ ?>
                <th colspan="2"><?=traduz('Troca / Ressarcimento')?></th>
            <?php } ?>
            <?php if(in_array($login_fabrica, array(104))){ ?>
            <th><?=traduz('Ressarcimento')?></th>
            <th><?=traduz('Cód. Produto Troca')?></th>
            <th><?=traduz('Desc. Produta Troca')?></th>
            <th><?=traduz('UF do Posto ')?></th>
            <th><?=traduz('Cidade do Posto')?></th>
            <th><?=traduz('CNPJ da Revenda')?></th>
            <th><?=traduz('Nome da Revenda')?></th>
            <?php }else if($login_fabrica != 164){ ?>
             <th><?=traduz('Troca / Ressarcimento')?></th>
            <?php } ?>
        </tr>
        <?php if(in_array($login_fabrica, array(164))){ ?>
            <tr class="titulo_coluna">
                <th><?=traduz('Referência Produto')?></th>
                <th><?=traduz('Descrição Produto')?></th>
            </tr>
        <?php } ?>
    </thead>
    <tbody>
<?php

        if($login_fabrica == 104){

            $cabecalho = array(
                "OS",
                "Data Abertura",
                "Data Troca",
                "Previsão Pagamento",
                "Nome Consumidor",
                "CPF Consumidor",
                "Cód. Produto",
                "Desc. Produto",
                "Causa Troca",
                "Responsável Troca",
                "Ressarcimento",
                "Cód. Produto Troca",
                "Desc. Produto Troca",
                "UF do Posto",
                "Cidade do Posto",
                "CNPJ da Revenda",
                "Nome da Rvenda"
            );

        }else if($login_fabrica == 162){
        	$cabecalho = array(
        		"OS",
        		"Data Abertura",
        		"Data Troca",
                "Nota Fiscal",
                "Data Nota Fiscal",
                "Revenda",
                "Classificação",
        		"Previsão Pagamento",
        		"Data Pagamento",
        		"Nome Consumidor",
        		"CPF Consumidor",
        		"Produto",
        		"Causa Troca",
        		"Responsável Troca",
        		"Troca / Ressarcimento"
        	);
        }else if($login_fabrica == 164){
            $cabecalho = array(
                "OS",
                "Observação troca de produto OS",
                "Nome Posto",
                "Data Abertura",
                "Data Troca",
                "Previsão Pagamento",
                "Nome Consumidor",
                "CPF Consumidor",
                "Nome Revenda",
                "CNPJ Revenda",
                "Referência Produto",
                "Descrição Produto",
                "Causa Troca",
                "Responsável Troca",
                "Troca / Referência Produto",
                "Troca / Descrição Produto",
                "Peças"
            );
        }elseif($login_fabrica == 171){
            $cabecalho = array(
                "OS",
                "Data Abertura",
                "Data Troca",
                "Previsão Pagamento",
                "Nome Consumidor",
                "CPF Consumidor",
                "Referência Fábrica",
                "Produto",
                "Causa Troca",
                "Responsável Troca",
                "Troca / Ressarcimento"
            );
        }else if ($login_fabrica == 177){
            $cabecalho = array(
                "OS",
                "Tipo Atendimento",
                "Data Abertura",
                "Data Troca",
                "Previsão Pagamento",
                "Nome Consumidor",
                "CPF Consumidor",
                "Referência Fábrica",
                "Produto",
                "Causa Troca",
                "Responsável Troca",
                "Troca / Ressarcimento"
            );
        }else{
            $cabecalho = array(
                "OS",
                "Data Abertura",
                "Data Troca",
                "Previsão Pagamento",
                "Nome Consumidor",
                "CPF Consumidor",
                "Produto",
                "Causa Troca",
                "Responsável Troca",
                "Troca / Ressarcimento"
            );
        }

        for($a=0;$a<pg_num_rows($resSubmit); $a++){
            $os                         = pg_fetch_result($resSubmit, $a, "os");
            $consumidor_nome            = pg_fetch_result($resSubmit, $a, "consumidor_nome");
            $consumidor_cpf             = pg_fetch_result($resSubmit, $a, "consumidor_cpf");
            $data_abertura              = pg_fetch_result($resSubmit, $a, "data_abertura");
            $data_troca                 = pg_fetch_result($resSubmit, $a, "data_troca");
            $valor_base_troca           = pg_fetch_result($resSubmit, $a, "valor_base_troca");
            $previsao_pagamento         = pg_fetch_result($resSubmit, $a, "previsao_pagamento");
            $data_pagamento             = pg_fetch_result($resSubmit, $a, "data_pagamento");
            $produto_referencia_fabrica = pg_fetch_result($resSubmit, $a, "produto_referencia_fabrica");
            $produto_referencia         = pg_fetch_result($resSubmit, $a, "produto_referencia");
            $produto_descricao          = pg_fetch_result($resSubmit, $a, "produto_descricao");
            $produto_troca_referencia   = pg_fetch_result($resSubmit, $a, "produto_troca_referencia");
            $produto_troca_descricao    = pg_fetch_result($resSubmit, $a, "produto_troca_descricao");
            $responsavel                = pg_fetch_result($resSubmit, $a, "responsavel");
            $causa_troca                = pg_fetch_result($resSubmit, $a, "causa_troca");
            $valor_ressarcimento        = pg_fetch_result($resSubmit, $a, "valor_ressarcimento");
            $produto                    = pg_fetch_result($resSubmit, $a, "produto");
            $sua_os                     = pg_fetch_result($resSubmit, $a, "sua_os");
            $descricao_peca             = pg_fetch_result($resSubmit, $a, "peca_descricao");            

            if(in_array($login_fabrica, array(104))){

                $revenda_cnpj = pg_fetch_result($resSubmit, $a, "revenda_cnpj");
                $revenda_nome = pg_fetch_result($resSubmit, $a, "revenda_nome");
                $posto_cidade = pg_fetch_result($resSubmit, $a, "posto_cidade");
                $posto_estado = pg_fetch_result($resSubmit, $a, "posto_estado");

            }

            if ($login_fabrica == 177){
                $tipo_atendimento = pg_fetch_result($resSubmit, $a, 'tipo_atendimento');
            }

            if(in_array($login_fabrica, array(164))){
                $revenda_cnpj = pg_fetch_result($resSubmit, $a, "revenda_cnpj");
                $revenda_nome = pg_fetch_result($resSubmit, $a, "revenda_nome");
                $troca_observacao = pg_fetch_result($resSubmit, $a, "troca_observacao");
                $posto_nome = pg_fetch_result($resSubmit, $a, "posto_nome");
            }

            if ($login_fabrica == 165 && $causa_troca == "Base de Troca") {
                $produto_troca_descricao .= traduz("<br />Valor Base de Troca: R$ ").number_format($valor_base_troca,2,',','');
            }

            if($login_fabrica == 104){

                $dados = array(
                    $sua_os,
                    $data_abertura,
                    $data_troca,
                    $previsao_pagamento,
                    $consumidor_nome,
                    $consumidor_cpf,
                    $produto_referencia,
                    $produto_descricao,
                    $causa_troca,
                    $responsavel
                );

            }else if($login_fabrica == 162){
                
                $classificacao_descricao = pg_fetch_result($resSubmit, $a, "classificacao_descricao");
                $cli_nota_fiscal         = pg_fetch_result($resSubmit, $a, "cli_nota_fiscal");
                $data_nota_fiscal        = pg_fetch_result($resSubmit, $a, "data_nota_fiscal");
                $revenda_nome            = pg_fetch_result($resSubmit, $a, "revenda_nome");

    		    $dados = array(
        			$sua_os,
        			$data_abertura,
        			$data_troca,
                    $cli_nota_fiscal,
                    $data_nota_fiscal,
                    $revenda_nome,
                    $classificacao_descricao,
        			$previsao_pagamento,
        			$data_pagamento,
        			$consumidor_nome,
        			$consumidor_cpf,
        			$produto_referencia. " - ". $produto_descricao,
        			$causa_troca,
        			$responsavel
    		   );
    	    }else if($login_fabrica == 164){

				$desc_peca = explode('<br />',  $descricao_peca);

                $dados = array(
                    $sua_os,
                    $troca_observacao,
                    $posto_nome,
                    $data_abertura,
                    $data_troca,
                    $previsao_pagamento,
                    $consumidor_nome,
                    $consumidor_cpf,
                    $revenda_nome,
                    $revenda_cnpj,
                    $produto_referencia,
                    $produto_descricao,
                    $causa_troca,
                    $responsavel,
                    $produto_troca_referencia,
                    $produto_troca_descricao
                );
				
				foreach($desc_peca as $pecas) {
					foreach(explode(' - ',$pecas) as $peca) {
						$dados[] = $peca;
					}
				}

            }elseif($login_fabrica == 171){
                $dados = array(
                    $sua_os,
                    $data_abertura,
                    $data_troca,
                    $previsao_pagamento,
                    $consumidor_nome,
                    $consumidor_cpf,
                    $produto_referencia_fabrica,
                    $produto_referencia. " - ". $produto_descricao,
                    $causa_troca,
                    $responsavel
                );
            }else if ($login_fabrica == 177){
                $dados = array(
                    $sua_os,
                    $tipo_atendimento,
                    $data_abertura,
                    $data_troca,
                    $previsao_pagamento,
                    $consumidor_nome,
                    $consumidor_cpf,
                    $produto_referencia. " - ". $produto_descricao,
                    $causa_troca,
                    $responsavel
                );
            }else{
                $dados = array(
                    $sua_os,
                    $data_abertura,
                    $data_troca,
                    $previsao_pagamento,
                    $consumidor_nome,
                    $consumidor_cpf,
                    $produto_referencia. " - ". $produto_descricao,
                    $causa_troca,
                    $responsavel
                );
            }

            if(in_array($login_fabrica, array(104))){

                $dados[] = $valor_ressarcimento;
                $dados[] = $produto_troca_referencia;
                $dados[] = $produto_troca_descricao;
                $dados[] = $posto_estado;
                $dados[] = $posto_cidade;
                $dados[] = $revenda_cnpj;
                $dados[] = $revenda_nome;

            }else if($login_fabrica != 164){

                $dados[] = (empty($valor_ressarcimento)) ? $produto_troca_referencia.' - '.$produto_troca_descricao : "R$ ".number_format($valor_ressarcimento,2,',','');

            }

            $linha .= implode(';',$dados)."\r\n";
            unset($dados);

    ?>
        <tr class='<?=$produto?>' >
            <td class="tac">
                <a href="os_press.php?os=<?=$os?>" target="_blanck"><?=$sua_os?></a>
            </td>
            <?php if ($login_fabrica == 177){ ?>
            <td class='tac'><?=$tipo_atendimento?></td>
            <?php } ?>
            <?php if($login_fabrica == 164){ ?>
                <td class="tac"><?=$troca_observacao?></td>
                <td class="tac"><?=$posto_nome?></td>
            <?php } ?>
            <td class="tac"><?=$data_abertura?></td>
            <td class="tac"><?=$data_troca?></td>	        
        	<?php if($login_fabrica == 162){ ?>

                <td class="tac"><?=$cli_nota_fiscal?></td>
                <td class="tac"><?=$data_nota_fiscal?></td>
                <td class="tac"><?=$revenda_nome?></td>
                <td class="tac"><?=$classificacao_descricao?></td>
        	    <td class="tac"><?=$previsao_pagamento?></td>
                <td class="tac"><?=$data_pagamento?></td>
        	<?php } else if ($login_fabrica != 171) { ?>
                <td class="tac"><?=$previsao_pagamento?></td>
            <?php } ?>
            <td class="tac"><?=$consumidor_nome?></td>
            <td class="tac"><?=$consumidor_cpf?></td>
            <?php if (in_array($login_fabrica, [164])) { ?>
                <td class="tac"><?=$revenda_nome?></td>
                <td class="tac"><?=$revenda_cnpj?></td>
            <?php } ?>
            <?php if(in_array($login_fabrica, array(171))){ ?>
            <td class="tac"><?=$produto_referencia_fabrica?></td>
            <?php }?>

            <?php if(in_array($login_fabrica, array(104, 164))){ ?>
            <td class="tac"><?=$produto_referencia?></td>
            <td class="tac"><?=$produto_descricao?></td>
            <?php }else{ ?>
            <td class="tac"><?=$produto_referencia. " - ". $produto_descricao?></td>
            <?php } 
                if($login_fabrica == 164) {                  
            ?>
                <td class="tac"><?=$descricao_peca ?></td>
            <?php } ?>
            <td class="tac"><?=$causa_troca?></td>
            <td class="tac"><?=$responsavel?></td>
            <?php if(in_array($login_fabrica, array(164))){ ?>
                <td class="tac"><?=$produto_troca_referencia?></td>
                <td class="tac"><?=$produto_troca_descricao?></td>
            <?php }
            if(in_array($login_fabrica, array(104))){
            ?>
            <td class="tac"><?=$valor_ressarcimento?></td>
            <td class="tac"><?=$produto_troca_referencia?></td>
            <td class="tac"><?=$produto_troca_descricao?></td>
            <td class="tac"><?=$posto_estado?></td>
            <td class="tac"><?=$posto_cidade?></td>
            <td class="tac"><?=$revenda_cnpj?></td>
            <td class="tac"><?=$revenda_nome?></td>
            <?php
            }else if($login_fabrica != 164){
            ?>
            <td class="tac"><?=(empty($valor_ressarcimento)) ? $produto_troca_referencia.' - '.$produto_troca_descricao : "R$ ".number_format($valor_ressarcimento,2,',','')?></td>
            <?php
            }
            ?>
        </tr>
<?php


        }
        $arquivo = implode(';',$cabecalho)."\r\n".$linha;

        fwrite($file, $arquivo);
        fclose($file);

        if (file_exists("/tmp/{$fileName}")) {
            system("mv /tmp/{$fileName} xls/{$fileName}");
        }
?>

    </tbody>
    <tfoot>
        <tr>
            <?php

            switch ($login_fabrica) {
                case 104: $colspan = "17"; break;
                case 162: $colspan = "11"; break;
                default: $colspan = "10"; break;
            }

            ?>
            <td colspan="<?php echo $colspan; ?>" class="tac">
                <a class="btn btn-success" href="xls/<?=$fileName?>" role="button"><?=traduz('Gerar Arquivo CSV')?></a>
            </td>
        </tr>
    </tfoot>
</table>

        <?php
        if (!in_array($login_fabrica, array(169,170))) {
            if ($count > 50) {
            ?>
                <script>
                    $.dataTableLoad({ table: "#callcenter_relatorio_atendimento" });
                </script>
            <?php
            }
        } else {
        ?>
            <script>
            
            $.dataTableLoad({
                table: '#callcenter_relatorio_atendimento_agrupado',
                type: 'custom',
                config: ['pesquisa', 'info'],
                aaSorting: [[3, 'desc']],
                aoColumns: [
                    null,
                    null,
                    null,
                    { sType: 'numeric' },
                    null
                ]
            });
            
            $(document).on('click', 'button.visualizar-os', function() {
                if ($(dataTableGlobal).attr('id') == 'callcenter_relatorio_atendimento') {
                    dataTableGlobal.fnDestroy(false);
                }
                
                let produto_id = $(this).data('produto-id');
                
                $('#callcenter_relatorio_atendimento > tbody').find('tr').hide();
                $('#callcenter_relatorio_atendimento > tbody').find('tr').filter(function(i, e) {
                    if ($(e).hasClass(produto_id)) {
                        return true;
                    } else {
                        return false;
                    }
                }).show();
                $('#callcenter_relatorio_atendimento').css({ display: 'table' });
                
                $.dataTableLoad({
                    table: '#callcenter_relatorio_atendimento',
                    type: 'custom',
                    config: ['pesquisa'],
                    aoColumns: [
                        null,
                        { sType: 'date' },
                        { sType: 'date' },
                        { sType: 'date' },
                        null,
                        null,
                        null,
                        null,
                        null,
                        null
                    ]
                });
                
                $(window).scrollTop($('#callcenter_relatorio_atendimento').offset().top);
            });
            
            </script>
        <?php
        }
        ?>
        <br />

            <?php

        }else{
            echo traduz("<div class='container'>
            <div class='alert'>
                    <h4>Nenhum resultado encontrado</h4>
            </div>
            </div>");
        }
    }

include "rodape.php"
?>
