<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios = "call_center";
include 'autentica_admin.php';

include 'funcoes.php';

$btn_acao = $_POST['btn_acao'];
if(strlen($btn_acao)>0){
    $new_relatorio = false;
    if ($login_fabrica == 24 && $_POST['relatorio_detalhado'] != 't') {
        $_POST['relatorio_detalhado'] = 't';
        $new_relatorio = true;
    } elseif ($login_fabrica == 24) {
        $new_relatorio = true;
    }
    $data_inicial = $_POST['data_inicial'];
    $data_final   = $_POST['data_final'];
    $linha        = $_POST['linha'];
    $familia      = $_POST['familia'];
    $produto_referencia = $_POST['produto_referencia'];
    $produto_descricao  = $_POST['produto_descricao'];
    $motivo_sintetico   = $_POST['motivo_sintetico'];
    $motivo_analitico   = $_POST['motivo_analitico'];
    $defeito_constatado = $_POST['defeito_constatado'];
    $analise_produto    = $_POST['analise_produto'];

    $msg_erro = "";

    if (empty($data_inicial) or empty($data_final)) {
        $msg_erro = 'Data inválida!';
    }

    if(strlen($msg_erro)==0){
        $xdata_inicial = dateFormat($data_inicial, 'dmy', 'y-m-d');
        $xdata_final   = dateFormat($data_final,   'dmy', 'y-m-d');

        $xdata_inicial .= " 00:00:00";
        $xdata_final .= " 23:59:59";
    }

    if(strlen($produto_referencia)>0){
        $sql = "SELECT produto 
                FROM tbl_produto 
                WHERE referencia='$produto_referencia' 
                AND fabrica_i = $login_fabrica 
                LIMIT 1";
        $res = pg_exec($con,$sql);
        if(pg_numrows($res)>0){
            $produto = pg_result($res,0,'produto');
            $cond_referencia = "AND tbl_os_laudo.produto = $produto ";
        } else {
            $msg_erro = "Produto não encontrado";
        }
    }

    if (!empty($xdata_inicial) && !empty($xdata_final)) {
        $cond_periodo = "AND (tbl_os_laudo.data_digitacao BETWEEN '$xdata_inicial' AND '$xdata_final') ";
    }

    if(count($linha)>0){
        $xlinha = implode(",",$linha);
        $cond_linha = "AND tbl_produto.linha IN({$xlinha}) ";
    }

    if(count($familia)>0){
        $xfamilia = implode(",",$familia);
        $cond_familia = "AND tbl_produto.familia IN({$xfamilia}) ";
    }

    if(count($motivo_sintetico)>0){
        $xmotivo_sintetico = implode(",",$motivo_sintetico);
        $cond_sintetico = "AND tbl_os_laudo.motivo_sintetico IN({$xmotivo_sintetico}) ";
    }

    if(count($motivo_analitico)>0){
        $xmotivo_analitico = implode(",",$motivo_analitico);
        $cond_analitico = "AND tbl_os_laudo.motivo_analitico IN({$xmotivo_analitico}) ";
    }

    if(count($defeito_constatado)>0){
        $xdefeito_constatado = implode(",",$defeito_constatado);
        $cond_defeito = "AND tbl_os_laudo.defeito_constatado IN({$xdefeito_constatado}) ";
    }

    if(count($analise_produto)>0){
        $xanalise_produto = implode(",",$analise_produto);
        $cond_analise = "AND tbl_os_laudo.analise_produto IN({$xanalise_produto}) ";
    }

    if (empty($msg_erro)) {

    if ($login_fabrica == 24) {
        /*$campos = "
            ,TO_CHAR(tbl_os_laudo.data_recebimento, 'DD/MM/YYYY') AS data_recebimento
            ,TO_CHAR(tbl_os_laudo.data_digitacao, 'DD/MM/YYYY') AS data_digitacao
            ,tbl_os_laudo.nome_cliente 
            ,tbl_os_laudo.nota_fiscal 
            ,tbl_os_laudo.motivo_sintetico 
            ,tbl_os_laudo.motivo_analitico
            ,tbl_analise_produto.descricao as analise_produto_descricao
            ,tbl_os_laudo.serie 
            ,tbl_os_laudo.defeito_constatado 
            ,tbl_os_laudo.os_laudo AS os_laudo_id
            ,tbl_produto.produto AS id_produto
            ,tbl_os_laudo.serie
        ";
        $group_by = "
            ,data_recebimento 
            ,tbl_analise_produto.descricao 
            ,tbl_os_laudo.nome_cliente 
            ,tbl_os_laudo.nota_fiscal 
            ,tbl_os_laudo.motivo_sintetico 
            ,tbl_os_laudo.motivo_analitico 
            ,tbl_os_laudo.serie 
            ,tbl_os_laudo.defeito_constatado
            ,os_laudo_id
            ,id_produto
            ,tbl_os_laudo.serie
        ";*/
    }

    $sql_laudo = "SELECT
            COUNT(tbl_produto.referencia) as ocorrencias, 
            tbl_produto.descricao as descricao_produto,
            tbl_produto.referencia,
            tbl_familia.familia,
            tbl_familia.descricao as descricao_familia,
            tbl_linha.nome
            ,tbl_produto.produto AS id_produto
            
            FROM tbl_produto
            JOIN tbl_os_laudo ON tbl_os_laudo.produto = tbl_produto.produto
            JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
            JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia
            JOIN tbl_motivo_sintetico ON tbl_motivo_sintetico.motivo_sintetico = tbl_os_laudo.motivo_sintetico
            JOIN tbl_motivo_analitico ON tbl_motivo_analitico.motivo_analitico = tbl_os_laudo.motivo_analitico
            LEFT JOIN tbl_analise_produto ON tbl_analise_produto.analise_produto = tbl_os_laudo.analise_produto
            LEFT JOIN tbl_defeito_constatado ON tbl_os_laudo.defeito_constatado = tbl_defeito_constatado.defeito_constatado
            WHERE tbl_os_laudo.fabrica = $login_fabrica
            $cond_periodo
            $cond_linha
            $cond_familia
            $cond_sintetico
            $cond_analitico
            $cond_defeito
            $cond_analise
            $cond_referencia
            GROUP BY tbl_produto.referencia,
                     tbl_produto.descricao,
                     tbl_familia.familia,
                     tbl_familia.descricao,
                     tbl_linha.nome
                     , id_produto

            ORDER BY tbl_produto.referencia,
                     tbl_produto.descricao
            ";    
        $res_laudo = pg_query($con, $sql_laudo);

        //Foi necessario para pegar os números de series. 
        $sql_numero_serie = "SELECT
            tbl_os_laudo.os_laudo,
            tbl_produto.descricao as descricao_produto,
            tbl_produto.referencia,
            tbl_familia.familia,
            tbl_os_laudo.serie,
            tbl_familia.descricao as descricao_familia,
            tbl_linha.nome
            FROM tbl_produto
            JOIN tbl_os_laudo ON tbl_os_laudo.produto = tbl_produto.produto
            JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
            JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia
            JOIN tbl_motivo_sintetico ON tbl_motivo_sintetico.motivo_sintetico = tbl_os_laudo.motivo_sintetico
            JOIN tbl_motivo_analitico ON tbl_motivo_analitico.motivo_analitico = tbl_os_laudo.motivo_analitico
            LEFT JOIN tbl_analise_produto ON tbl_analise_produto.analise_produto = tbl_os_laudo.analise_produto
            LEFT JOIN tbl_defeito_constatado ON tbl_os_laudo.defeito_constatado = tbl_defeito_constatado.defeito_constatado
            WHERE tbl_os_laudo.fabrica = $login_fabrica
            $cond_periodo
            $cond_linha
            $cond_familia
            $cond_sintetico
            $cond_analitico
            $cond_defeito
            $cond_analise
            $cond_referencia";
        $res_numero_serie = pg_query($con, $sql_numero_serie);
        for($ns = 0; $ns<pg_num_rows($res_numero_serie); $ns++){
            $serie = pg_fetch_result($res_numero_serie, $ns, serie);

            if ($login_fabrica == 24) {
                $serie_primeiro_dig = $serie;
                $serie_primeiro_dig = substr($serie, 0, 4);
                $serie_primeiro_dig = str_pad($serie_primeiro_dig, 4, "0", STR_PAD_LEFT);

                $serie_primeiro_dig_ano = substr($serie_primeiro_dig, 2, 4);
                $serie_primeiro_dig_mes = substr($serie_primeiro_dig, 0, 2);
            } else {
                $serie_primeiro_dig = substr($serie, 0, 4);
                $serie_primeiro_dig = str_pad($serie_primeiro_dig, 4, "0", STR_PAD_LEFT);
            }

            if ($login_fabrica == 24) {
                if(isset($dados_numero_serie[$serie_primeiro_dig_ano][$serie_primeiro_dig_mes])){
                    $dados_numero_serie[$serie_primeiro_dig_ano][$serie_primeiro_dig_mes] += 1;
                }else{
                    $dados_numero_serie[$serie_primeiro_dig_ano][$serie_primeiro_dig_mes] = 1;
                }
            } else {
                if(isset($dados_numero_serie[$serie_primeiro_dig])){
                    $dados_numero_serie[$serie_primeiro_dig] += 1;
                }else{
                    $dados_numero_serie[$serie_primeiro_dig] = 1;
                }                    
            }
        }
    }
}

$array_verf_defeito_peca = array();
$array_ver_produto = array();
$id_defeito = array();

$layout_menu = "callcenter";
$title = "RELATÓRIO DE DEVOLUÇÕES";

include "cabecalho_new.php";



?>

<!--
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->
<? 
$plugins = array( 
    "mask", 
    "datepicker",
    "shadowbox",
    "dataTable",
    "autocomplete",
    "multiselect"
 );

include "plugin_loader.php"; 
?>
<script type="text/javascript" charset="utf-8">
    $(function(){

        $("#data_inicial").datepicker().mask("99/99/9999");
        $("#data_final").datepicker().mask("99/99/9999");

        Shadowbox.init();
        $("#linha,#familia,#motivo_sintetico,#motivo_analitico,#defeito_constatado,#analise_produto").multiselect({
            selectedText: "selecionados # de #"
        });

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });

        $(".exibir_pecas").click(function(){
            var posicao = $(this).data("posicao");

            if ($(this).text() == "+") {
                $(".linha_"+posicao).show();
                $(this).text("-");
                $(this).removeClass('btn-success');
                $(this).addClass('btn-danger');
            } else {
                $(".linha_"+posicao).hide();
                $(this).text("+");
                $(this).removeClass('btn-danger');
                $(this).addClass('btn-success');
            }

        });

        $(".produto").click(function(){
            var data_inicial = $("#data_inicial").val();
            var data_final   = $("#data_final").val();
            var produto = $(this).attr("produto");
            var produto_descricao = $("#produto_descricao").val();

            var linha = [];

            $("input[name=multiselect_linha]").each(function(){
                if ($(this).is(':checked') == true) {
                    linha.push($(this).val());
                }
            });

            var familia = [];

            $("input[name=multiselect_familia]").each(function(){
                if ($(this).is(':checked') == true) {
                    familia.push($(this).val());
                }
            });

            var motivo_sintetico = [];

            $("input[name=multiselect_motivo_sintetico]").each(function(){
                if ($(this).is(':checked') == true) {
                    motivo_sintetico.push($(this).val());
                }
            });

            var motivo_analitico = [];

            $("input[name=multiselect_motivo_analitico]").each(function(){
                if ($(this).is(':checked') == true) {
                    motivo_analitico.push($(this).val());
                }
            });

            var defeito_constatado = [];

            $("input[name=multiselect_defeito_constatado]").each(function(){
                if ($(this).is(':checked') == true) {
                    defeito_constatado.push($(this).val());
                }
            });

            var analise_produto = [];

            $("input[name=multiselect_analise_produto]").each(function(){
                if ($(this).is(':checked') == true) {
                    analise_produto.push($(this).val());
                }
            });

            produto_detalhes(produto,linha,familia,motivo_sintetico,motivo_analitico,defeito_constatado,analise_produto,data_inicial,data_final);

        });

    });

    function produto_detalhes(produto,linha,familia,motivo_sintetico,motivo_analitico,defeito_constatado,analise_produto,data_inicial,data_final){

        Shadowbox.open({
            content: "detalhe_produto.php?produto="+produto+"&linha="+linha+"&familia="+familia+"&motivo_sintetico="+motivo_sintetico+"&motivo_analitico="+motivo_analitico+"&defeito_constatado="+defeito_constatado+"&analise_produto="+analise_produto+"&data_inicial="+data_inicial+"&data_final="+data_final+"",
            player: "iframe",
            width: 1000,
            height: 500,
            options: {
                modal: true
            }
        });
    }

    function detalhes_defeito_peca_produto(valor, tipo){

        if (valor != undefined && tipo != undefined) {

            if (tipo == "peca_lancada") {
                
                var tipo_url = "peca";

            } else if (tipo == "porcentagem") {
                
                var tipo_url = "produto";

            } else if (tipo == "peca_defeito"){

                var tipo_url = "peca_defeito";

            } else if (tipo == 'numero_serie') {

                var tipo_url = "numero_serie";

            }

            var xlinha = [];

            $("input[name=multiselect_linha]").each(function(){
                if ($(this).is(':checked') == true) {
                    xlinha.push($(this).val());
                }
            });

            var xfamilia = [];

            $("input[name=multiselect_familia]").each(function(){
                if ($(this).is(':checked') == true) {
                    xfamilia.push($(this).val());
                }
            });

            var xmotivo_sintetico = [];

            $("input[name=multiselect_motivo_sintetico]").each(function(){
                if ($(this).is(':checked') == true) {
                    xmotivo_sintetico.push($(this).val());
                }
            });

            var xmotivo_analitico = [];

            $("input[name=multiselect_motivo_analitico]").each(function(){
                if ($(this).is(':checked') == true) {
                    xmotivo_analitico.push($(this).val());
                }
            });

            var xdefeito_constatado = [];

            $("input[name=multiselect_defeito_constatado]").each(function(){
                if ($(this).is(':checked') == true) {
                    xdefeito_constatado.push($(this).val());
                }
            });

            var xanalise_produto = [];

            $("input[name=multiselect_analise_produto]").each(function(){
                if ($(this).is(':checked') == true) {
                    xanalise_produto.push($(this).val());
                }
            });


            if (tipo_url != "") {
                Shadowbox.open({
                    content: "detalhe_defeito_peca.php?"+tipo_url+"="+valor+"&data_inicial="+$("#data_inicial").val()+"&data_final="+$("#data_final").val()+"&linha="+xlinha+"&familia="+xfamilia+"&motivo_sintetico="+xmotivo_sintetico+"&motivo_analitico="+xmotivo_analitico+"&defeito_constatado="+xdefeito_constatado+"&analise_produto="+xanalise_produto,
                    player: "iframe",
                    width: 1000,
                    height: 500,
                    options: {
                        modal: true
                    }
                });
            }
        }
    }

    function retorna_produto (retorno) {
        $("#produto_referencia").val(retorno.referencia);
        $("#produto_descricao").val(retorno.descricao);
    }
</script>

    <? if(strlen($msg_erro)>0){ ?>
        <div class='alert alert-danger'><h4><? echo $msg_erro; ?></h4></div>
    <? } ?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<FORM class='form-search form-inline tc_formulario' name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

        <div class="titulo_tabela">Parâmetros de Pesquisa</div>
            <br />
            <div class="row-fluid">
                <div class="span2"></div>
                <div class="span4">
                    <div class="control-group">
                        <label class="control-label" for=''>Data Inicial</label>
                        <div class='controls controls-row'>
                            <h5 class='asteristico'>*</h5>
                            <input class="span4" type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='frm' value="<?= $_POST['data_inicial'] ?>">
                            <!--<img border="0" src="imagens/lupa.png" align="absmiddle" onclick="javascript:showCal('DataInicial')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">-->
                        </div>  
                    </div>  
                </div>
                <div class="span4"> 
                    <div class="control-group"> 
                        <label class="control-label" for=''>Data Final</label>
                        <div class='controls controls-row'>
                            <h5 class='asteristico'>*</h5>
                            <input class="span4" type="text" name="data_final" id="data_final" size="12" maxlength="10" class='frm' value="<?= $_POST['data_final'] ?>">
                            <!--<img border="0" src="imagens/lupa.png" align="absmiddle"    onclick="javascript:showCal('DataFinal')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">-->
                        </div>
                    </div>  
                </div>
                <div class="span2"></div>       
            </div>
            <div class="row-fluid">
            <div class="span2"></div>
                <div class="span4"> 
                    <div class="control-group">     
                        <label class="control-label" for='linha'>Linha</label>
                        <div class='controls-row'>
                            <select id="linha" name="linha[]" multiple="multiple">
                            <?
                                    $sql_linha = "SELECT  linha,
                                                        nome,
                                                        ativo
                                                FROM    tbl_linha
                                                WHERE ativo IS TRUE
                                                AND fabrica = $login_fabrica
                                                ORDER BY nome";       
                                    $res_linha = pg_query($con,$sql_linha);

                                    for($x=0;$x < pg_num_rows($res_linha);$x++) {
                                        $linha           = trim(pg_result($res_linha,$x,'linha'));
                                        $descricao_linha = trim(pg_result($res_linha,$x,'nome'));

                                        $selected = (in_array($linha, $_POST["linha"])) ? "selected" : "";

                                        ?>
                                        <option value="<?= $linha ?>"<?= $selected ?>><?= $descricao_linha ?></option>
                                <?        
                                    }
                                ?>
                            </select>
                        </div>  
                    </div>
                </div>
                <div class="span4">
                    <div class="control-group">
                        <label class="control-label" for='familia'>Família</label>
                        <div class='controls-row'>
                            <select id="familia" name="familia[]" multiple="multiple">
                            <?
                                    $sql_familia = "SELECT  familia,
                                                            descricao,
                                                            ativo
                                                    FROM    tbl_familia
                                                    WHERE ativo IS TRUE
                                                    AND fabrica = $login_fabrica
                                                    ORDER BY descricao";       
                                    $res_familia = pg_query($con,$sql_familia);

                                    for($x=0;$x < pg_num_rows($res_familia);$x++) {
                                        $familia           = trim(pg_result($res_familia,$x,'familia'));
                                        $descricao_familia = trim(pg_result($res_familia,$x,'descricao'));

                                        $selected = (in_array($familia, $_POST["familia"])) ? "selected" : "";

                                        ?>
                                        <option value="<?= $familia ?>" <?= $selected ?>><?= $descricao_familia ?></option>
                            <?        
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
                        <label class="control-label" for='produto_referencia'>Ref. Produto</label>
                        <div class='controls-row input-append'>
                            <input type="text" id="produto_referencia" name="produto_referencia" size="12" class='frm' maxlength="20" value="<?= $_POST['produto_referencia'] ?>" >
                            <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                            <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
                        </div>  
                    </div>
                </div>
                <div class="span4">
                    <div class="control-group">
                        <label class="control-label" for='produto_descricao'>Descrição Produto</label>
                        <div class='controls-row input-append'>
                            <input type="text" id="produto_descricao" name="produto_descricao" size="30" class='frm' value="<?= $_POST['produto_descricao'] ?>" >
                            <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
                            <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="row-fluid">
                <div class="span2"></div>
                    <div class="span4"> 
                        <div class="control-group">     
                            <label class="control-label" for='motivo_sintetico'>Motivo Sintético</label>
                            <div class='controls-row'>
                                <select id="motivo_sintetico" name="motivo_sintetico[]" multiple="multiple">
                                <?
                                    $sql_sintetico = "SELECT  motivo_sintetico,
                                                        codigo,
                                                        descricao,
                                                        ativo
                                                        FROM    tbl_motivo_sintetico
                                                        WHERE ativo IS TRUE
                                                        ORDER BY codigo";
                                    $res_sintetico = pg_query($con,$sql_sintetico);

                                    for($x=0;$x < pg_num_rows($res_sintetico);$x++) {
                                        $motivo_sintetico           = trim(pg_result($res_sintetico,$x,'motivo_sintetico'));
                                        $descricao_sintetico         = trim(pg_result($res_sintetico,$x,'descricao'));

                                        $selected = (in_array($motivo_sintetico, $_POST["motivo_sintetico"])) ? "selected" : "";

                                        ?>
                                        <option value="<?= $motivo_sintetico ?>" <?= $selected ?>><?= $descricao_sintetico ?></option>
                                <?        
                                    }
                                ?>
                                </select>
                            </div>  
                        </div>
                    </div>
                    <div class="span4">
                        <div class="control-group">
                            <label class="control-label" for='motivo_analitico'>Motivo Analítico</label>
                            <div class='controls-row'>
                                <select id="motivo_analitico" name="motivo_analitico[]" multiple="multiple">
                                <?
                                    $sql_motivo = "SELECT  motivo_analitico,
                                                        codigo,
                                                        descricao,
                                                        ativo
                                                FROM    tbl_motivo_analitico
                                                WHERE ativo IS TRUE
                                                ORDER BY codigo";
                                    $res_motivo = pg_query($con,$sql_motivo);

                                    for($x=0;$x < pg_num_rows($res_motivo);$x++) {
                                        $motivo_analitico           = trim(pg_result($res_motivo,$x,'motivo_analitico'));
                                        $descricao_analitico         = trim(pg_result($res_motivo,$x,'descricao'));

                                        $selected = (in_array($motivo_analitico, $_POST["motivo_analitico"])) ? "selected" : "";

                                        ?>
                                        <option value="<?= $motivo_analitico ?>"<?= $selected ?>><?= $descricao_analitico ?></option>
                                <?        
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
                            <label class="control-label" for='defeito'>Defeito Constatado</label>
                            <div class='controls-row'>
                                <select id="defeito_constatado" name="defeito_constatado[]" multiple="multiple">
                                <?
                                    $sql_defeito = "SELECT  defeito_constatado,
                                                            descricao,
                                                            ativo
                                                    FROM    tbl_defeito_constatado
                                                    WHERE ativo IS TRUE
                                                    AND fabrica = $login_fabrica
                                                    ORDER BY descricao";       
                                    $res_defeito = pg_query($con,$sql_defeito);

                                    for($x=0;$x < pg_num_rows($res_defeito);$x++) {
                                        $defeito_constatado           = trim(pg_result($res_defeito,$x,'defeito_constatado'));
                                        $descricao_defeito = trim(pg_result($res_defeito,$x,'descricao'));

                                        $selected = (in_array($defeito_constatado, $_POST["defeito_constatado"])) ? "selected" : "";

                                        ?>
                                        <option value="<?= $defeito_constatado ?>" <?= $selected ?>><?= $descricao_defeito ?></option>
                            <?        
                                }
                            ?>
                                </select>
                            </div>  
                        </div>
                    </div>
                    <div class="span4">
                        <div class="control-group">
                            <label class="control-label" for='analise'>Análise Produto</label>
                            <div class='controls-row'>
                                <select id="analise_produto" name="analise_produto[]" multiple="multiple">
                                <?
                                $sql_analise = "SELECT  analise_produto,
                                                        codigo,
                                                        descricao,
                                                        ativo
                                                FROM    tbl_analise_produto 
                                                WHERE ativo IS TRUE
                                                ORDER BY codigo";
                                    $res_analise = pg_query($con,$sql_analise);

                                    for($x=0;$x < pg_num_rows($res_analise);$x++) {
                                        $analise_produto           = trim(pg_result($res_analise,$x,'analise_produto'));
                                        $descricao_analise         = trim(pg_result($res_analise,$x,'descricao'));

                                        $selected = (in_array($analise_produto, $_POST["analise_produto"])) ? "selected" : "";

                                        ?>
                                        <option value="<?= $analise_produto ?>" <?= $selected ?>><?= $descricao_analise ?></option>
                                <?        
                                    }
                                ?>
                                </select>
                            </div>
                        </div>
                    </div>
                <div class="span2"></div>    
            </div>
            <br />
            <div class="row-fluid">
                <div class="span2"></div>
                <div class="span3">
                    <!-- <input type="checkbox" <?= ($_POST["relatorio_detalhado"] == 't') ? "checked" : "" ?> name="relatorio_detalhado" value="t" /> Relatório Detalhado -->
                </div> 
                <div class="span2">
                    <input class="btn" type='submit' style="cursor:pointer" name='btn_acao' value='Consultar'>
                </div>
                <div class="span5"></div>
            </div>
</FORM>
<br />
</div>
<?
if (!empty($_POST['btn_acao'])) { ?>    
            <?
            if (pg_num_rows($res_laudo) > 0) { 
                ?>
                   <table class="table table-bordered table-hover table-fixed table-striped">
                    <thead>
                        <tr class="titulo_tabela">
                            <th colspan="8">Lista de produtos</th>
                        </tr>
                        <tr class="titulo_coluna">
                            <th>Referência Produto</th>
                            <th>Descrição Produto</th>
                            <th>Linha</th>
                            <th>Família</th>
                            <th>Ocorrência</th>
                            <th>Porcentagem Do Total</th>
                            <th>Qtde. Peças Trocadas</th>
                            <th>Qtde. Total de Peças</th>
                        </tr>
                    </thead>
                    <tbody>   
                <?
                $sql_total = "SELECT COUNT(os_laudo) as total 
                              FROM tbl_os_laudo
                              WHERE tbl_os_laudo.fabrica = $login_fabrica
                              $cond_periodo";
                $res_total = pg_query($con, $sql_total);

                $total = pg_fetch_result($res_total, 0, "total");

                $dados[] = array("Produto", 'percentual');
                $peca_lancada[] = array('qtde', utf8_encode('Qtde Peças Lançadas'));
                $peca_defeito[] = array('qtde', utf8_encode('Ocorrencias de Defeitos'));
                $produto_numero_serie[] = array('qtde', utf8_encode('Ocorrencias por Número de Série'));
                                               
                for ($i=0;$i < pg_num_rows($res_laudo);$i++) {
                    $os_laudo               = pg_fetch_result($res_laudo, $i, "os_laudo");
                    $produto_referencia     = pg_fetch_result($res_laudo, $i, "referencia");
                    $descricao_produto      = pg_fetch_result($res_laudo, $i, "descricao_produto");
                    $descricao_linha        = pg_fetch_result($res_laudo, $i, "nome");
                    $descricao_familia      = pg_fetch_result($res_laudo, $i, "descricao_familia");
                    $ocorrencias            = pg_fetch_result($res_laudo, $i, "ocorrencias");
                    $id_produto            = pg_fetch_result($res_laudo, $i, "id_produto");

                    $total_ocorrencias += $ocorrencias; 

                    $porcentagem = ($ocorrencias * 100) / $total;

                    $total_porcentagem += $porcentagem ;

                    
                    if ($login_fabrica == 24) {
                        $achou = false;
                        if (!empty($dados)) {  
                            $p_d = "$produto_referencia - $descricao_produto";
                            foreach ($dados as $chave => $vlr) {
                                if ($p_d == $vlr[0]) {
                                    $ocorrencias = $ocorrencias + 1;
                                    $dados[$chave] = array(utf8_encode("$produto_referencia - $descricao_produto"), (int)$ocorrencias);
                                    $achou = true;
                                }
                            }
                            if ($achou === false) {
                                $dados[] = array(utf8_encode("$produto_referencia - $descricao_produto"), (int)$ocorrencias);
                                $produtos_porcentagen[] = $id_produto;
                            }
                        }                        
                    } else {
                        $dados[] = array(utf8_encode("$produto_referencia - $descricao_produto"), (int)$ocorrencias);
                    }

                    $produto_numero_serie[] = array(utf8_encode("$produto_referencia - $descricao_produto"), (int)$ocorrencias);

                    $qtde_pecas_trocadas = 0;

                    $sql_qtde_pecas = "SELECT tbl_os_laudo.os_laudo,
                                         tbl_os_laudo_peca.qtde,
                                         tbl_os_laudo_peca.peca,
                                         tbl_servico_realizado.troca_de_peca,
                                         tbl_servico_realizado.descricao,
                                         tbl_servico_realizado.servico_realizado
                                  FROM tbl_os_laudo
                                  JOIN tbl_produto ON tbl_produto.produto = tbl_os_laudo.produto
                                  LEFT JOIN tbl_os_laudo_peca ON tbl_os_laudo_peca.os_laudo = tbl_os_laudo.os_laudo
                                  LEFT JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_laudo_peca.servico_realizado
                                  WHERE tbl_produto.referencia =    '$produto_referencia'
                                  AND tbl_servico_realizado.troca_de_peca IS TRUE
                                  AND tbl_os_laudo.fabrica = $login_fabrica
                                  $cond_periodo";         
                    $res_qtde_pecas = pg_query($con, $sql_qtde_pecas);

                    for ($x=0;$x < pg_num_rows($res_qtde_pecas);$x++){
                        $qtde_pecas_trocadas += pg_fetch_result($res_qtde_pecas, $x, 'qtde');
                    }              
                                 
                    $sql = "SELECT produto 
                                FROM tbl_produto 
                                WHERE referencia='$produto_referencia' 
                                AND fabrica_i = $login_fabrica 
                                LIMIT 1";
                    $res = pg_exec($con,$sql);

                    $produto = pg_fetch_result($res, 0, "produto");

                    $sql_qtde_total = "SELECT SUM(tbl_os_laudo_peca.qtde) as qtde_total
                                  FROM tbl_os_laudo
                                  JOIN tbl_produto ON tbl_produto.produto = tbl_os_laudo.produto
                                  LEFT JOIN tbl_os_laudo_peca ON tbl_os_laudo_peca.os_laudo = tbl_os_laudo.os_laudo
                                  WHERE tbl_produto.referencia =  '$produto_referencia'
                                  AND tbl_os_laudo.fabrica = $login_fabrica
                                  $cond_periodo";         
                    $res_qtde_total = pg_query($con, $sql_qtde_total);

                    $total_pecas = pg_fetch_result($res_qtde_total, 0, "qtde_total");
            ?>
                <tr>
                    <td class="tal">
                        <?php if ($_POST["relatorio_detalhado"] == "t") { 

                            $desabilitado = ($qtde_pecas_trocadas == 0 && $total_pecas == 0) ? "disabled" : "";
                            ?>
                            <button <?= $desabilitado ?> data-posicao="<?= $i ?>" class="btn btn-small btn-success exibir_pecas">+</button> &nbsp;
                        <?php } ?>
                        <a href="#" class="produto" produto="<?= $produto ?>">
                            <?= $produto_referencia ?>
                        </a>
                    </td>
                    <td><a href="#" class="produto" produto="<?= $produto ?>"><?= $descricao_produto ?></a></td>
                    <td><?= $descricao_linha    ?></td>
                    <td><?= $descricao_familia  ?></td>
                    <td class="tac"><?= $ocorrencias ?></td>
                    <td class="tac"><?= number_format($porcentagem,2) ?>%</td>
                    <td class="tac"><?= $qtde_pecas_trocadas?></td>
                    <td class="tac"><?= $total_pecas ?></td>
                </tr>
                <?php

                if ($_POST["relatorio_detalhado"] == "t") { ?>
                    <tr class="linha_<?= $i ?>" style="display: none;margin: 10px;">
                        <th></th>
                        <th>Devolução</th>
                        <th colspan="2">Peça</th>
                        <th>Qtde</th>
                        <th>Defeito</th>
                        <th>Serviço</th>
                        <th></th>
                    </tr>   
                    <?

                        /*HD - */
                        if ($login_fabrica == 24) {
                            $executar_query = true;

                            if (in_array($produto_referencia, $array_verf_defeito_peca)) {
                                $executar_query = false;
                            } else {
                                $array_verf_defeito_peca[] = $produto_referencia;
                                $executar_query = true;
                            }
                        }

                        $sql_pecas = "SELECT tbl_os_laudo.os_laudo,
                                       tbl_produto.produto, 
                                       tbl_peca.referencia,
                                       tbl_peca.descricao as descricao_peca,
                                       tbl_os_laudo.defeito_constatado,
                                       tbl_os_laudo_peca.servico_realizado,
                                       tbl_os_laudo_peca.qtde,
                                       tbl_os_laudo.serie,
                                       tbl_os_laudo_peca.defeito,
                                       tbl_servico_realizado.descricao as descricao_servico,
                                       tbl_defeito.defeito,
                                       tbl_defeito.descricao as descricao_defeito
                                FROM tbl_os_laudo
                                JOIN tbl_produto ON tbl_os_laudo.produto = tbl_produto.produto
                                LEFT JOIN tbl_os_laudo_peca ON tbl_os_laudo_peca.os_laudo = tbl_os_laudo.os_laudo
                                LEFT JOIN tbl_servico_realizado ON tbl_os_laudo_peca.servico_realizado = tbl_servico_realizado.servico_realizado
                                JOIN tbl_defeito ON tbl_os_laudo_peca.defeito = tbl_defeito.defeito
                                JOIN tbl_peca ON tbl_os_laudo_peca.peca = tbl_peca.peca
                                WHERE tbl_produto.referencia = '{$produto_referencia}'
                                AND tbl_os_laudo.fabrica = {$login_fabrica}
                                $cond_periodo";

                        $res_pecas = pg_query($con, $sql_pecas);                          

                         for ($y=0;$y < pg_num_rows($res_pecas);$y++) {
                            $os_laudo            = pg_fetch_result($res_pecas, $y, "os_laudo");
                            $serie            = pg_fetch_result($res_pecas, $y, "serie");

                            $peca_referencia     = pg_fetch_result($res_pecas, $y, "referencia");
                            $peca_descricao      = pg_fetch_result($res_pecas, $y, "descricao_peca");
                            $servico_realizado   = pg_fetch_result($res_pecas, $y, "descricao_servico");
                            $qtde                = pg_fetch_result($res_pecas, $y, "qtde");   
                            $defeito             = pg_fetch_result($res_pecas, $y, "descricao_defeito");
                            $defeito_id          = pg_fetch_result($res_pecas, $y, "defeito");

                            if (($login_fabrica == 24 && $executar_query == true) || ($login_fabrica != 24)) {
                                $peca_defeito[$defeito_id] =  array(
                                    utf8_encode("$defeito"),  $peca_defeito[$defeito_id][1] + 1,  $peca_defeito[$defeito_id][1] + 1
                                );

                                if (!in_array($defeito_id, $id_defeito)) {
                                    $id_defeito[] = $defeito_id;
                                }
                            }
                       
                    ?>
                            <tr class="linha_<?= $i ?>" style="display: none;margin: 10px;">
                                <td></td>
                                <td class="tac"><?= $os_laudo ?></td>
                                <td colspan="2"><?= $peca_referencia." - ".$peca_descricao ?></td>
                                <td class="tac"><?= $qtde ?></td>
                                <td><?= $defeito ?></td>
                                <td><?= $servico_realizado ?></td>
                                <td></td>
                            </tr>
                <?php   } ?>
                <?php }

                }             

                /*if($total_ocorrencias != $total){
                    $dados[] = array('Outros', $total-$total_ocorrencias);
                }*/

                $jsonData = json_encode($dados, true);

                 ?>        
        </tbody>
    </table>
     <?
        flush();

        $xlsdata = date ("d/m/Y H:i:s");

        system("rm /tmp/assist/relatorio-relatorio-devolucoes-$login_fabrica.csv");
        $fp = fopen ("/tmp/assist/relatorio-devolucoes-$login_fabrica.csv","w");

        fputs ($fp,"Relatório de Devoluções\n");

        $cabecalho = array();

        $cabecalho[] = "Referência Produto";
        $cabecalho[] = "Descrição Produto";
        $cabecalho[] = "Linha";
        $cabecalho[] = "Família";
        $cabecalho[] = "Ocorrências";
        $cabecalho[] = "Porcentagem Do Total";
        $cabecalho[] = "Qtde. Peças Trocadas";
        $cabecalho[] = "Qtde. Total de Peças";

        if ($login_fabrica == 24) {

            $id_produto = array();
            $nserie     = array();

            if ($_POST["relatorio_detalhado"] != "t" || $new_relatorio == true) {
                
                $cabecalho[] = "Data Digitação";
                $cabecalho[] = "Data Recebimento";
                $cabecalho[] = "Nome do cliente";
                $cabecalho[] = "Análise Produto";
                $cabecalho[] = "Nota Fiscal";
                $cabecalho[] = "Motivo Sintético";
                $cabecalho[] = "Motivo Analítico";
                $cabecalho[] = "Numero de Serie";
                $cabecalho[] = "Defeito Constatado";
                $cabecalho[] = "Devolução";
                $cabecalho[] = "Peça lançada";
                $cabecalho[] = "Qtde Peça";
                $cabecalho[] = "Defeito Peça";
                
            }
        }

        fputs ($fp, implode(";", $cabecalho)."\n");

        $sql_total = "SELECT COUNT(*) as total
                              FROM tbl_os_laudo
                              WHERE tbl_os_laudo.fabrica = $login_fabrica
                              $cond_periodo";
        $res_total = pg_query($con, $sql_total);

        $total = pg_fetch_result($res_total, 0, "total");
        
        for ($i = 0; $i < pg_num_rows($res_laudo); $i++){
            $os_laudo               = pg_fetch_result($res_laudo, $i, "os_laudo_id");
            $produto_referencia     = pg_fetch_result($res_laudo, $i, "referencia");
            $descricao_produto      = pg_fetch_result($res_laudo, $i, "descricao_produto");
            $descricao_linha        = pg_fetch_result($res_laudo, $i, "nome");
            $descricao_familia      = pg_fetch_result($res_laudo, $i, "descricao_familia");
            $ocorrencias            = pg_fetch_result($res_laudo, $i, "ocorrencias");

            $porcentagem = ($ocorrencias * 100) / $total;

            $qtde_pecas_trocadas = 0;

            /*HD-4093050*/
            if ($login_fabrica == 24) {
                $os_laudo_id = pg_fetch_result($res_laudo, $i, 'os_laudo_id');
                $aux_sql = "SELECT DISTINCT(tbl_defeito.defeito) AS defeito_peca
                        FROM tbl_os_laudo_peca
                        JOIN tbl_peca ON tbl_peca.peca = tbl_os_laudo_peca.peca
                        JOIN tbl_defeito ON tbl_defeito.defeito = tbl_os_laudo_peca.defeito
                        JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_laudo_peca.servico_realizado
                        WHERE tbl_os_laudo_peca.os_laudo = $os_laudo_id";
                $aux_res = pg_query($con,$aux_sql);

                $id_produto[] = pg_fetch_result($res_laudo, $i, "id_produto");
                $nserie[]     = pg_fetch_result($res_laudo, $i, "serie");
            }

            $sql_qtde_pecas = "SELECT tbl_os_laudo.os_laudo,
                                         tbl_os_laudo_peca.qtde,
                                         tbl_os_laudo_peca.peca,
                                         tbl_servico_realizado.troca_de_peca,
                                         tbl_servico_realizado.descricao,
                                         tbl_servico_realizado.servico_realizado
                                  FROM tbl_os_laudo
                                  JOIN tbl_produto ON tbl_produto.produto = tbl_os_laudo.produto
                                  JOIN tbl_os_laudo_peca ON tbl_os_laudo_peca.os_laudo = tbl_os_laudo.os_laudo
                                  JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_laudo_peca.servico_realizado
                                  WHERE tbl_produto.referencia =    '$produto_referencia'
                                  AND tbl_servico_realizado.troca_de_peca IS TRUE
                                  AND tbl_os_laudo.fabrica = $login_fabrica
                                  $cond_periodo";         
            $res_qtde_pecas = pg_query($con, $sql_qtde_pecas);

            for ($x=0;$x < pg_num_rows($res_qtde_pecas);$x++){
                $qtde_pecas_trocadas += pg_fetch_result($res_qtde_pecas, $x, 'qtde');
            } 

            $sql_qtde_total = "SELECT SUM(tbl_os_laudo_peca.qtde) as qtde_total
                                  FROM tbl_os_laudo
                                  JOIN tbl_produto ON tbl_produto.produto = tbl_os_laudo.produto
                                  LEFT JOIN tbl_os_laudo_peca ON tbl_os_laudo_peca.os_laudo = tbl_os_laudo.os_laudo
                                  WHERE tbl_produto.referencia =  '$produto_referencia'
                                  AND tbl_os_laudo.fabrica = $login_fabrica
                                  $cond_periodo";         
            $res_qtde_total = pg_query($con, $sql_qtde_total);

            $total_pecas = pg_fetch_result($res_qtde_total, 0, "qtde_total");

            $linha = array();

            $linha[] = "$produto_referencia";
            $linha[] = "$descricao_produto";
            $linha[] = "$descricao_linha";
            $linha[] = "$descricao_familia";
            $linha[] = "$ocorrencias";
            $linha[] = "".number_format($porcentagem,2)."";
            $linha[] = "$qtde_pecas_trocadas";
            $linha[] = "$total_pecas";

            if ($login_fabrica != 24) fputs($fp, implode(";", $linha)."\n");

            if ($login_fabrica == 24 && ($_POST["relatorio_detalhado"] != "t" || $new_relatorio == true)) {

                $analise_produto_descricao = pg_fetch_result($res_laudo, $i, 'analise_produto_descricao');                 

                /*Motivo Sintético*/
                $aux_sql = "SELECT descricao AS motivo_sintetico FROM tbl_motivo_sintetico WHERE motivo_sintetico = $motivo_sintetico LIMIT 1";
                $aux_res = pg_query($con, $aux_sql);
                $motivo_sintetico = pg_fetch_result($aux_res, 0, 'motivo_sintetico');

                /*Motivo Analitíco*/
                $aux_sql = "SELECT descricao AS motivo_analitico FROM tbl_motivo_analitico WHERE motivo_analitico = $motivo_analitico LIMIT 1";
                $aux_res = pg_query($con, $aux_sql);
                $motivo_analitico = pg_fetch_result($aux_res, 0, 'motivo_analitico');

                $campos = "
                    ,TO_CHAR(tbl_os_laudo.data_recebimento, 'DD/MM/YYYY') AS data_recebimento
                    ,TO_CHAR(tbl_os_laudo.data_digitacao, 'DD/MM/YYYY') AS data_digitacao
                    ,tbl_os_laudo.nome_cliente 
                    ,tbl_os_laudo.nota_fiscal 
                    ,tbl_os_laudo.motivo_sintetico 
                    ,tbl_os_laudo.motivo_analitico
                    ,tbl_analise_produto.descricao as analise_produto_descricao
                    ,tbl_os_laudo.serie 
                    ,tbl_os_laudo.defeito_constatado 
                    ,tbl_os_laudo.os_laudo AS os_laudo_id
                    ,tbl_produto.produto AS id_produto
                    ,tbl_os_laudo.serie
                ";

                /*Peça*/
                $aux_sql = "SELECT 
                tbl_os_laudo.os_laudo, 
                tbl_peca.referencia || ' - ' || tbl_peca.descricao AS descricao_peca, 
                tbl_peca.referencia,
                tbl_peca.descricao, 
                tbl_os_laudo_peca.qtde, 
                tbl_peca.peca, 
                tbl_defeito.descricao AS descricao_defeito

            ,TO_CHAR(tbl_os_laudo.data_recebimento, 'DD/MM/YYYY') AS data_recebimento
            ,TO_CHAR(tbl_os_laudo.data_digitacao, 'DD/MM/YYYY') AS data_digitacao
            ,tbl_os_laudo.nome_cliente 
            ,tbl_os_laudo.nota_fiscal 
            ,tbl_os_laudo.motivo_sintetico 
            ,tbl_os_laudo.motivo_analitico
            ,tbl_os_laudo.serie 
            ,tbl_os_laudo.defeito_constatado 

            

                FROM tbl_os_laudo_peca
    join tbl_os_laudo on tbl_os_laudo.os_laudo = tbl_os_laudo_peca.os_laudo and tbl_os_laudo.fabrica = $login_fabrica
    join tbl_produto on tbl_produto.produto = tbl_os_laudo.produto and tbl_produto.fabrica_i = $login_fabrica 
                        JOIN tbl_peca ON tbl_peca.peca = tbl_os_laudo_peca.peca
                        JOIN tbl_defeito ON tbl_defeito.defeito = tbl_os_laudo_peca.defeito
                        LEFT JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_laudo_peca.servico_realizado
                        WHERE tbl_produto.referencia = '$produto_referencia'
                        AND tbl_os_laudo.fabrica = $login_fabrica
                        $cond_periodo ";
                $aux_res = pg_query($con,$aux_sql);

                $total   = pg_num_rows($aux_res); 
                if ($total > 0) {
                    fputs($fp, implode(";", $linha));
                    for ($z = 0; $z < $total; $z++) {
                  
                        if ($z > 0) {
                            fputs($fp, implode(";", $linha)); 
                        }
                        $sub_linha = array("");
                        $os_laudo          = pg_fetch_result($aux_res, $z, 'os_laudo');
                        $descricao_peca    = pg_fetch_result($aux_res, $z, 'descricao_peca');
                        $qtde              = pg_fetch_result($aux_res, $z, 'qtde');
                        $descricao_defeito = pg_fetch_result($aux_res, $z, 'descricao_defeito');
                        $peca_referencia   = pg_fetch_result($aux_res, $z, 'referencia');
                        $peca_descricao    = pg_fetch_result($aux_res, $z, 'descricao');
                        $peca              = pg_fetch_result($aux_res, $z, 'peca');
                        $data_digitacao     = pg_fetch_result($aux_res, $z, 'data_digitacao'); 
                        $data_recebimento   = pg_fetch_result($aux_res, $z, 'data_recebimento'); 
                        $nome_cliente       = pg_fetch_result($aux_res, $z, 'nome_cliente'); 
                        $nota_fiscal        = pg_fetch_result($aux_res, $z, 'nota_fiscal'); 
                        $motivo_sintetico   = pg_fetch_result($aux_res, $z, 'motivo_sintetico'); 
                        $motivo_analitico   = pg_fetch_result($aux_res, $z, 'motivo_analitico');
                        $serie              = pg_fetch_result($aux_res, $z, 'serie');
                        $defeito_constatado = pg_fetch_result($aux_res, $z, 'defeito_constatado');

                        if(!empty($defeito_constatado)){        
                            /*Defeito Constatado*/
                            $sql_const = "SELECT descricao AS defeito_constatado FROM tbl_defeito_constatado WHERE defeito_constatado = $defeito_constatado AND fabrica = $login_fabrica LIMIT 1";
                            $res_const = pg_query($con, $sql_const);
                            $defeito_constatado = pg_fetch_result($res_const, 0, 'defeito_constatado');
                        }

                        if(!empty(trim($peca))){
                            $peca_lancada[$peca] =  array(
                                utf8_encode("$peca_referencia - $peca_descricao"),  $peca_lancada[$peca][1] + 1,  $peca_lancada[$peca][1] + 1
                            );    
                        }

                        $sub_linha[] = $data_digitacao;
                        $sub_linha[] = $data_recebimento;
                        $sub_linha[] = $nome_cliente;
                        $sub_linha[] = $analise_produto_descricao;
                        $sub_linha[] = $nota_fiscal;
                        $sub_linha[] = $motivo_sintetico;
                        $sub_linha[] = $motivo_analitico;
                        $sub_linha[] = $serie;
                        $sub_linha[] = $defeito_constatado;



                        $sub_linha[] = $os_laudo;
                        $sub_linha[] = "\"$descricao_peca\"";
                        $sub_linha[] = $qtde;
                        $sub_linha[] = $descricao_defeito;
                        
                        fputs($fp, implode(";", $sub_linha)."\n");
                        unset($sub_linha);
                    }
                } else {
                    fputs($fp, implode(";", $linha)."\n");
                }
            }

            /*HD - 4093050*/
            if ($login_fabrica == 24) {
                $executar_query = true;

                if (in_array($produto_referencia, $array_ver_produto)) {
                    $executar_query = false;
                } else {
                    $array_ver_produto[] = $produto_referencia;
                    $executar_query = true;
                }
            }

            if ($_POST["relatorio_detalhado"] == "t" && $new_relatorio == false) {
                $cabecalho = array();

                $cabecalho[] = " ";
                $cabecalho[] = "Devolução";
                $cabecalho[] = "Peça";
                $cabecalho[] = "Qtde";
                $cabecalho[] = "Defeito";
                $cabecalho[] = "Serviço";
                $cabecalho[] = " ";
                $cabecalho[] = " ";

                fputs ($fp, implode(";", $cabecalho)."\n");

                $sql_pecas = "SELECT tbl_os_laudo.os_laudo,
                               tbl_produto.produto, 
                               tbl_peca.peca,
                               tbl_peca.referencia,
                               tbl_peca.descricao as descricao_peca,
                               tbl_os_laudo.defeito_constatado,
                               tbl_os_laudo_peca.servico_realizado,
                               tbl_os_laudo_peca.qtde,
                               tbl_os_laudo_peca.defeito,
                               tbl_servico_realizado.descricao as descricao_servico,
                               tbl_defeito.defeito, 
                               tbl_defeito.descricao as descricao_defeito
                        FROM tbl_os_laudo
                        JOIN tbl_produto ON tbl_os_laudo.produto = tbl_produto.produto
                        LEFT JOIN tbl_os_laudo_peca ON tbl_os_laudo_peca.os_laudo = tbl_os_laudo.os_laudo
                        LEFT JOIN tbl_servico_realizado ON tbl_os_laudo_peca.servico_realizado = tbl_servico_realizado.servico_realizado
                        JOIN tbl_defeito ON tbl_os_laudo_peca.defeito = tbl_defeito.defeito
                        JOIN tbl_peca ON tbl_os_laudo_peca.peca = tbl_peca.peca
                        WHERE tbl_produto.referencia = '{$produto_referencia}'
                        AND tbl_os_laudo.fabrica = {$login_fabrica}
                        $cond_periodo";

                 $res_pecas = pg_query($con, $sql_pecas); 

                 for ($y=0;$y < pg_num_rows($res_pecas);$y++) {
                    $os_laudo            = pg_fetch_result($res_pecas, $y, "os_laudo");
                    $peca                = pg_fetch_result($res_pecas, $y, "peca");

                    $peca_referencia     = pg_fetch_result($res_pecas, $y, "referencia");
                    $peca_descricao      = str_replace(",",".",pg_fetch_result($res_pecas, $y, "descricao_peca"));
                    $servico_realizado   = pg_fetch_result($res_pecas, $y, "descricao_servico");
                    $qtde                = pg_fetch_result($res_pecas, $y, "qtde");   
                    $defeito             = pg_fetch_result($res_pecas, $y, "descricao_defeito");
                    $defeito_id          = pg_fetch_result($res_pecas, $y, "defeito");                  

                    if (($login_fabrica == 24 && $executar_query == true) || ($login_fabrica != 24)) {
                        if(!empty($peca)){
                            $peca_lancada[$peca] =  array(
                                utf8_encode("$peca_referencia - $peca_descricao"),  $peca_lancada[$peca][1] + 1,  $peca_lancada[$peca][1] + 1

                            );
                        }
                    }

                    $linha = array();

                    $linha[] = " ";
                    $linha[] = "$os_laudo";
                    $linha[] = "$peca_referencia - $peca_descricao";
                    $linha[] = "$qtde";
                    $linha[] = "$defeito";
                    $linha[] = "$servico_realizado";
                    $linha[] = " ";
                    $linha[] = " ";

                    fputs($fp, implode(";", $linha)."\n");
               }
            }
        }

        fclose ($fp);

        function cmp($a, $b)
        {   
            if (is_numeric($a[1])) {
                return $a[1] < $b[1];
            }
        }

        //ordenar array pelo valor
        //uasort($peca_lancada, "cmp");
        #uasort($peca_defeito, "cmp");

        // Limpar o array
        foreach ($peca_lancada as $peca => $arr) {
            $peca_lancada_arr[] = $arr;
            $id_peca_lancada[]  = $peca;
        }

        foreach ($peca_defeito as $defeito => $arr){
            $peca_defeito_arr[] = $arr;
            $id_peca_defeito[]  = $defeito;
        }

        $produto_numero_serie_arr[] = array('qtde', utf8_encode('Ocorrencias por Número de Série'));
        if ($login_fabrica == 24) {
            ksort($dados_numero_serie);
        }
        $xz = 0;
        if ($login_fabrica == 24) {
            foreach ($dados_numero_serie as $num_ano => $arr_mes){
                ksort($arr_mes);
                foreach ($arr_mes as $k_mes => $vl) {
                    $produto_numero_serie_arr[] = array($k_mes.$num_ano, $vl,$vl);
                    $nserie[$xz] = $k_mes.$num_ano;
                    $xz++;
                    
                }
            }
        } else {
            foreach ($dados_numero_serie as $num_serie => $arr){
                $produto_numero_serie_arr[] = array("$num_serie", $arr,$arr);
                $nserie[$xz] = "$num_serie";
                $xz++;
            }
        }

        $peca_lancada_json = json_encode($peca_lancada_arr, true);
		$peca_lancada_json = str_replace('das"],', 'das",{role:"annotation"}],',$peca_lancada_json);

        $id_peca_lancada_json = json_encode($id_peca_lancada, true);

        $peca_defeito_json = json_encode($peca_defeito_arr, true);
		$peca_defeito_json = str_replace('tos"],', 'tos",{role:"annotation"}],',$peca_defeito_json);

        $produto_numero_serie_json = json_encode($produto_numero_serie_arr, true);
		$produto_numero_serie_json = str_replace('rie"],', 'rie",{role:"annotation"}],',$produto_numero_serie_json);

        /*HD-4093050*/
        if ($login_fabrica == 24) {
            $id_produto_jason        = json_encode($produtos_porcentagen, true);
            $id_defeito_lancado_json = json_encode($id_peca_defeito, true);
            $id_nserie_json          = json_encode($nserie, true);
        }
        $data = date("Y-m-d").".".date("H-i-s");

        rename("/tmp/assist/relatorio-devolucoes-$login_fabrica.csv", "xls/relatorio-devolucoes-$login_fabrica.$data.csv");

        ?>
        <div class="container">
            <div class="row">
                <div class="tac"> 
                    <h4>Gráficos:</h4>                   
                    <?php if ($_POST["relatorio_detalhado"] == "t") {  ?>
                        <input type="hidden" id="aux_grafico" name="aux_grafico">
                        <button type="button" class="btn" onclick="ocorrencia('porcentagem')">% Ocorrência</button>
                        <button type="button" class="btn" onclick="ocorrencia('peca_lancada')">Peças Lancadas</button>
                        <button type="button" class="btn" onclick="ocorrencia('peca_defeito')">Defeito Peças</button>
                        <button type="button" class="btn" onclick="ocorrencia('numero_serie')">Ocorrência por N° de Série</button>
                    <?php } ?>
                </div>
            </div>
        </div>
        <?php


            echo"<br />
                <table width='300' border='0' cellspacing='2' cellpadding='2' align='center' style='cursor: pointer; font-size: 12px;'>
                    <tr>
                        <td align='left' valign='absmiddle'>
                            <a href='xls/relatorio-devolucoes-$login_fabrica.$data.csv' target='_blank'>
                                <img src='imagens/excel.png' height='20px' width='20px' align='absmiddle'>    Download do Arquivo CSV
                            </a>
                        </td>
                    </tr>
                </table>
            ";


            //exit(var_dump($jsonData));
        ?>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
google.charts.load('current', {'packages':['corechart']});

 
var grafico1 = {'titulo': 'Percentual de Ocorrência dos Produtos', 'dados': <?=$jsonData?>} ;
var grafico2 = {'titulo': 'Peças Lançadas', 'dados': <?=$peca_lancada_json?>} ;
var grafico3 = {'titulo': 'Ocorrencias de Defeitos', 'dados': <?=$peca_defeito_json?>} ;
var grafico4 = {'titulo': 'Ocorrencias por Número de Série', 'dados': <?=$produto_numero_serie_json?>} ;

function drawChart(grafico,click=false) {
    var data = google.visualization.arrayToDataTable(grafico.dados);
    
    chart.draw(data,
        {title: grafico.titulo, is3D: true}
    );

    if (click) {
        google.visualization.events.addListener(chart, 'select', selectHandler);
    }

    function selectHandler() {
        var objetoSelecionado = chart.getSelection();
        var tipo = $("#aux_grafico").val();
        var id_peca_produto = getPecaProduto();

        if (tipo == 'peca_lancada') {
            //retirando a primeira posição vazia do objeto
            delete id_peca_produto.peca[0];
            var pecas_lancadas = [];

            //criando um novo array com as peças do gráfico
            for (var x=1;x < id_peca_produto.peca.length;x++) {
                pecas_lancadas.push(id_peca_produto.peca[x]);
            }

            //verifica o número da coluna selecionada para comparar com o index da peça
            var colunaSelecionada = objetoSelecionado[0].row;

            //pega o id da peça de acordo com peça da coluna selecionada
            var valor             = pecas_lancadas[colunaSelecionada];

            detalhes_defeito_peca_produto(valor, tipo);
        } else if (tipo == 'porcentagem') {
            var produtos_lancados = [];

            for (var x=0; x < id_peca_produto.produto.length; x++) {
                produtos_lancados.push(id_peca_produto.produto[x]);
            }

            var colunaSelecionada = objetoSelecionado[0].row;
            var valor             = produtos_lancados[colunaSelecionada];

            detalhes_defeito_peca_produto(valor, tipo);
        } else if (tipo == 'peca_defeito'){
            var defeitos_lancados = [];

            delete id_peca_produto.peca[0];

            for (var x=1; x < id_peca_produto.peca.length; x++) {
                defeitos_lancados.push(id_peca_produto.peca[x]);
            }

            var colunaSelecionada = objetoSelecionado[0].row;
            var valor             = defeitos_lancados[colunaSelecionada];

            detalhes_defeito_peca_produto(valor, tipo);
        } else if (tipo == 'numero_serie') {
            var nserie_lancadas = [];

            for (var x=0; x < id_peca_produto.nserie.length; x++) {
                nserie_lancadas.push(id_peca_produto.nserie[x]);
            }

            var colunaSelecionada = objetoSelecionado[0].row;
            var valor             = nserie_lancadas[colunaSelecionada];

            detalhes_defeito_peca_produto(valor, tipo);
        }


        //limpando o objeto, para caso o usuário clique novamente
        chart.setSelection();
    }
}

function getPecaProduto(){
    var tipo = $("#aux_grafico").val();
    var id_peca_produto;

    switch (tipo) {
        case 'porcentagem':
            id_peca_produto =  {"produto" : <?= $id_produto_jason ?>}
        break;

        case 'peca_defeito':
            id_peca_produto =  {"peca" : <?= $id_defeito_lancado_json ?>}
        break;

        case 'numero_serie':
            id_peca_produto =  {"nserie" : <?= $id_nserie_json ?>}
        break;

        default:
            id_peca_produto =  {"peca" : <?= $id_peca_lancada_json ?>}
    }

    return id_peca_produto;
}

function ocorrencia(tipo){
    $('#grafico1').css('width', 1000);
    $('#grafico1').css('height', 500);
    if(tipo == 'porcentagem'){
        $("#aux_grafico").val('porcentagem');
        chart = new google.visualization.PieChart(document.getElementById('grafico1'));    
        google.charts.setOnLoadCallback(drawChart(grafico1, true));
    }
    else if(tipo == 'peca_lancada') {
        $("#aux_grafico").val('peca_lancada');
        chart = new google.visualization.ColumnChart(document.getElementById('grafico1'));    
        google.charts.setOnLoadCallback(drawChart(grafico2, true));
    }
    else if(tipo == 'peca_defeito') {
        $("#aux_grafico").val('peca_defeito');
        chart = new google.visualization.ColumnChart(document.getElementById('grafico1'));    
        google.charts.setOnLoadCallback(drawChart(grafico3, true));
    }
    else if(tipo == 'numero_serie') {
        $("#aux_grafico").val('numero_serie');
        chart = new google.visualization.ColumnChart(document.getElementById('grafico1'));    
        google.charts.setOnLoadCallback(drawChart(grafico4, true));
    }    

    
}

</script>
<div class="container">
    <div class='row'>
        <div id="grafico1"></div>
    </div>
</div>    

     <?php } else { ?>
        <div class="alert alert-warning"><h4>Nenhum resultado encontrado</h4></div>
    <?   
    }

}
?>

<p>

<? include "rodape.php" ?>
