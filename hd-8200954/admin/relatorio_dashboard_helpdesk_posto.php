<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "call_center";
include 'autentica_admin.php';
include "../helpdesk.inc.php";
include '../helpdesk/mlg_funciones.php';
include 'funcoes.php';

$jsonPOST = excelPostToJson($_POST);

if ($_POST["btn_acao"] == "submit") {
    $data_inicial       = $_POST['data_inicial'];
    $data_final         = $_POST['data_final'];
    $posto_codigo       = $_POST['codigo_posto'];
    $descricao_posto    = $_POST['descricao_posto'];
    $atendimento        = $_POST['atendimento'];
    $atendente          = $_POST['atendente'];
    $regiao             = $_POST['regiao'];
    $categoria          = $_POST['categoria'];
    $nota               = $_POST['nota'];

    if (strlen($posto_codigo) > 0 || strlen($descricao_posto) > 0){
        $sql = "SELECT tbl_posto_fabrica.posto
                FROM tbl_posto
                JOIN tbl_posto_fabrica USING(posto)
                WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
                AND (
                    (UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$posto_codigo}'))
                    OR
                    (TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('{$descricao_posto}'), 'LATIN-9'))
                )";
        $res = pg_query($con ,$sql);

        if (!pg_num_rows($res)) {
            $msg_erro["msg"][]    = "Posto não encontrado";
            $msg_erro["campos"][] = "posto";
        } else {
            $posto = pg_fetch_result($res, 0, "posto");
        }
    }

    if ((empty($data_inicial) || empty($data_final)) && empty($atendimento)) {
        $msg_erro["msg"][]    = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "data";
        $msg_erro["campos"][] = "atendimento";
    } else if (!empty($data_inicial) && !empty($data_final)) {
        list($di, $mi, $yi) = explode("/", $data_inicial);
        list($df, $mf, $yf) = explode("/", $data_final);

        if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
            $msg_erro["msg"][]    = "Data Inválida";
            $msg_erro["campos"][] = "data";
        } else {
            $aux_data_inicial = "{$yi}-{$mi}-{$di}";
            $aux_data_final   = "{$yf}-{$mf}-{$df}";

            if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
                $msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
                $msg_erro["campos"][] = "data";
            }

            $sql = "SELECT '$aux_data_final'::date - INTERVAL '6 MONTHS' > '$aux_data_inicial'::date ";
            $res = pg_query ($con,$sql);
            if (pg_fetch_result($res,0,0) == 't') {
                $msg_erro["msg"][]    = "A data de consulta deve ser no máximo de 6 meses.";
                $msg_erro["campos"][] = "data";
            }

            if (count($msg_erro) == 0) {
                $cond_data = " AND tbl_hd_chamado.data BETWEEN '$aux_data_inicial 00:00' AND '$aux_data_final 23:59' ";
            }
        }
    }

    if (!empty($atendimento)) {
        $cond_atendimento = " AND tbl_hd_chamado.hd_chamado = $atendimento ";
    }

    if (!empty($atendente)){
        $cond_atendente = " AND tbl_hd_chamado.atendente = $atendente ";
    }

    if (!empty($regiao)) {
        $sql_regiao = "SELECT estados_regiao FROM tbl_regiao WHERE regiao = $regiao AND fabrica = $login_fabrica";
        $res_regiao = pg_query($con, $sql_regiao);
        if (pg_num_rows($res_regiao) > 0) {
            $regioes = strtoupper(pg_fetch_result($res_regiao, 0, 'estados_regiao'));
            $reg = explode(',', $regioes);
            $regioes = '';
            foreach ($reg as $key => $value) {
                if ($key == 0) {
                    $regioes .= "'".trim($value)."'"; 
                } else {
                    $regioes .= ", '".trim($value)."'"; 
                }
            }
            $cond_regiao = " AND UPPER(tbl_posto.estado) IN ($regioes) ";    
        }
    }

    if (!empty($categoria)) {
        $cond_categoria = " AND tbl_hd_chamado.categoria = '$categoria' ";
    }

    if (!empty($nota)) {
        if ($nota == 'ruim') {
            $cond_nota = " AND JSON_FIELD('avaliacao_pontuacao', tbl_hd_chamado_extra.array_campos_adicionais)::int <= 4 ";
        } else if ($nota == 'regular') {
            $cond_nota = " AND JSON_FIELD('avaliacao_pontuacao', tbl_hd_chamado_extra.array_campos_adicionais)::int <= 6 AND JSON_FIELD('avaliacao_pontuacao', tbl_hd_chamado_extra.array_campos_adicionais)::int >= 5 ";
        } else if ($nota == 'bom') {
            $cond_nota = " AND JSON_FIELD('avaliacao_pontuacao', tbl_hd_chamado_extra.array_campos_adicionais)::int <= 8 AND JSON_FIELD('avaliacao_pontuacao', tbl_hd_chamado_extra.array_campos_adicionais)::int >= 7 ";
        } else {
            $cond_nota = " AND JSON_FIELD('avaliacao_pontuacao', tbl_hd_chamado_extra.array_campos_adicionais)::int >= 9 ";
        }
    }

    if (!count($msg_erro["msg"])) {
        
        if (!empty($posto)) {
            $cond_posto = " AND tbl_hd_chamado.posto = {$posto} ";
        } else if (empty($posto) && empty($atendimento)) {
            $cond_posto = " AND tbl_hd_chamado.posto <> 6359 ";
        }

        $res = pg_query($con, "DROP TABLE tmp_hd_hd;");

        $sql_dados = "
            SELECT tbl_hd_chamado.hd_chamado,
                   TO_CHAR(tbl_hd_chamado.data, 'DD/MM/YYYY') AS data,
                   tbl_hd_chamado.status,
                   tbl_posto_fabrica.codigo_posto,
                   tbl_posto.nome,
                   tbl_admin.nome_completo,
                   tbl_posto.estado,
                   tbl_hd_chamado.categoria,
                   CASE WHEN tbl_hd_chamado.data_resolvido NOTNULL 
                        THEN
                            (tbl_hd_chamado.data_resolvido - tbl_hd_chamado.data)
                    END AS fechamento,
                   tbl_hd_chamado_extra.array_campos_adicionais                   
                   INTO TEMP tmp_hd_hd
            FROM tbl_hd_chamado
            LEFT JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
            JOIN tbl_posto ON tbl_hd_chamado.posto = tbl_posto.posto
            JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
            JOIN tbl_admin ON tbl_hd_chamado.atendente = tbl_admin.admin AND tbl_admin.fabrica = $login_fabrica
            WHERE tbl_hd_chamado.fabrica = $login_fabrica
            AND tbl_hd_chamado.posto NOTNULL
            {$cond_atendimento}
            {$cond_atendente}
            {$cond_regiao}
            {$cond_categoria}
            {$cond_nota}
            {$cond_data}
            {$cond_posto}
            ORDER BY data DESC;

            SELECT * FROM tmp_hd_hd;";
        $res_dados = pg_query($con, $sql_dados);
    }
}

if ( $_POST['gerar_excel'] == 'true' ) {
    if (pg_num_rows($res_dados) > 0){
        
        $data = date("d-m-Y-H:i");
        $fileName = "relatorio_dashboard_posto-{$data}.csv";
        $file = fopen("/tmp/{$fileName}", "w");

        $headers = array(
            "Atendimento",
            "Data",
            "Status",
            "Posto",
            "Nome Posto",
            "Atendente",
            "Região",
            "Tipo de Solicitação",
            "Média do Tempo entre Interações",
            "Média do Tempo de Fechamento",
            "Nota da Avaliação",
            "Mensagem"
        );
        fwrite($file, implode(";", $headers)."\n");
            
        for ($i=0; $i < pg_num_rows($res_dados); $i++) { 
            $hd_chamado    = pg_fetch_result($res_dados, $i, 'hd_chamado'); 
            $data          = pg_fetch_result($res_dados, $i, 'data'); 
            $status        = pg_fetch_result($res_dados, $i, 'status'); 
            $codigo_posto  = pg_fetch_result($res_dados, $i, 'codigo_posto'); 
            $nome          = pg_fetch_result($res_dados, $i, 'nome');  
            $nome_completo = pg_fetch_result($res_dados, $i, 'nome_completo');  
            $estado        = pg_fetch_result($res_dados, $i, 'estado');  

            if (!empty($estado)) {
                $sql_regiao = "SELECT descricao FROM tbl_regiao WHERE estados_regiao ilike '%$estado%' AND fabrica = $login_fabrica";
                $res_regiao = pg_query($con, $sql_regiao);
                $regiao_desc = pg_fetch_result($res_regiao, 0, 'descricao'); 
            }

            $categoria     = pg_fetch_result($res_dados, $i, 'categoria');  

            if (!empty($categoria)) {
                $categoria_desc = "";
                $tipos_extras = array(
                    "nova_duvida_pecas"   => "Dúvida sobre peças",
                    "nova_duvida_pedido"  => "Dúvidas sobre Pedido",
                    "nova_duvida_produto" => "Dúvidas sobre produtos",
                    "nova_erro_fecha_os"  => "Problemas no fechamento da O.S.",
                );

                foreach ($tipos_extras as $key => $value) {
                    if ($categoria == $key) {
                        $categoria_desc = $value;
                        break;
                    }
                }

                if (!empty($categoria_desc)) {
                    foreach ($categorias as $key => $value) {
                        if ($value["no_fabrica"]) {
                            if (in_array($login_fabrica, $value["no_fabrica"])) {
                                continue;
                            }
                        }

                        if ($categoria == $key) {
                            $categoria_desc = $value['descricao'];
                            break;
                        }
                    }
                }
                
            }

            $sql_interacoes = " WITH itens1 AS (
                                            SELECT
                                            row_number() OVER(ORDER BY hd_chamado_item) AS ordem,
                                            tbl_hd_chamado_item.hd_chamado_item,
                                            tbl_hd_chamado_item.data,
                                            tbl_hd_chamado_item.hd_chamado
                                            FROM tbl_hd_chamado_item
                                            WHERE tbl_hd_chamado_item.hd_chamado = $hd_chamado
                                            UNION
                                            SELECT 0 AS ordem,
                                            0 AS hd_chamado_item ,
                                            data,
                                            hd_chamado
                                            FROM tbl_hd_chamado
                                            WHERE hd_chamado = $hd_chamado
                                    ),
                                    itens2 AS (
                                            SELECT
                                            row_number() OVER(ORDER BY hd_chamado_item) AS ordem,
                                            tbl_hd_chamado_item.hd_chamado_item,
                                            tbl_hd_chamado_item.data,
                                            tbl_hd_chamado_item.hd_chamado
                                            FROM tbl_hd_chamado_item
                                            WHERE tbl_hd_chamado_item.hd_chamado = $hd_chamado
                                    ),
                                    itens3 AS (
                                            SELECT  itens1.hd_chamado_item AS item1, 
                                                    itens2.hd_chamado_item AS item2,
                                                    itens2.data AS data2, 
                                                    itens1.data AS data1 
                                            FROM itens2 
                                            JOIN itens1 USING(hd_chamado) 
                                            WHERE itens1.ordem = itens2.ordem - 1 
                                            ORDER BY itens1.hd_chamado_item
                                    ),
                                    itens4 AS (
                                                SELECT item1, 
                                                      item2, 
                                                      data2 - data1 AS intervalo 
                                                FROM itens3
                                                )
                                    SELECT SUM(intervalo)/(SELECT COUNT(1) FROM itens4) AS media FROM itens4";
            $res_interacoes = pg_query($con, $sql_interacoes);
            
            $interacao     = pg_fetch_result($res_interacoes, 0, 'media');    
            $interacao    = str_replace(['days','Days'], 'Dias', $interacao);
            $interacao    = str_replace(['day','Day'], 'Dia', $interacao);
            $interacao    = preg_replace('/\.\d+$/','',$interacao);


            $fechamento    = pg_fetch_result($res_dados, $i, 'fechamento');  
            $fechamento    = str_replace(['days','Days'], 'Dias', $fechamento);
            $fechamento    = str_replace(['day','Day'], 'Dia', $fechamento);
            $fechamento    = preg_replace('/\.\d+$/','',$fechamento);

            $arry_campos   = json_decode(pg_fetch_result($res_dados, $i, 'array_campos_adicionais'), true); 
            $nota          = $arry_campos['avaliacao_pontuacao']; 
            $mensagem      = (mb_check_encoding($arry_campos['avaliacao_mensagem'], 'UTF-8')) ? utf8_decode($arry_campos['avaliacao_mensagem']) : $arry_campos['avaliacao_mensagem']; 

            $values = array(
                "{$hd_chamado}",
                "{$data}",
                "{$status}",
                "{$codigo_posto}",
                "{$nome}",
                "{$nome_completo}",
                "{$regiao_desc}",
                "{$categoria_desc}",
                "{$interacao}",
                "{$fechamento}",
                "{$nota}",
                "{$mensagem}"
            );
            fwrite($file, implode(";", $values)."\n");
        }

        $sql_total_interacao = "    WITH    itens1 AS (
                                                    SELECT
                                                    row_number() OVER(ORDER BY hd_chamado, hd_chamado_item) AS ordem,
                                                    tbl_hd_chamado_item.hd_chamado_item,
                                                    tbl_hd_chamado_item.data,
                                                    tbl_hd_chamado_item.hd_chamado
                                                    FROM tbl_hd_chamado_item
                                                    WHERE tbl_hd_chamado_item.hd_chamado IN (SELECT hd_chamado FROM tmp_hd_hd)
                                                    UNION
                                                    SELECT 0 AS ordem,
                                                    0 AS hd_chamado_item ,
                                                    data,
                                                    hd_chamado
                                                    FROM tbl_hd_chamado
                                                    WHERE hd_chamado IN (SELECT hd_chamado FROM tmp_hd_hd)
                                                    ORDER BY hd_chamado, hd_chamado_item
                                            ),
                                            itens2 AS (
                                                    SELECT
                                                    row_number() OVER(ORDER BY hd_chamado, hd_chamado_item) AS ordem,
                                                    tbl_hd_chamado_item.hd_chamado_item,
                                                    tbl_hd_chamado_item.data,
                                                    tbl_hd_chamado_item.hd_chamado
                                                    FROM tbl_hd_chamado_item
                                                    WHERE tbl_hd_chamado_item.hd_chamado IN (SELECT hd_chamado FROM tmp_hd_hd)
                                                    ORDER BY hd_chamado, hd_chamado_item
                                            ),
                                            itens3 AS (
                                              SELECT  itens1.hd_chamado_item AS item1,
                                                itens2.hd_chamado_item AS item2,
                                                itens2.data AS data2,
                                                itens1.data AS data1
                                              FROM itens2
                                              JOIN itens1 USING(hd_chamado)
                                              WHERE itens1.ordem = itens2.ordem - 1
                                              ORDER BY itens1.hd_chamado, itens1.hd_chamado_item
                                            ),
                                            itens4 AS (
                                               SELECT item1,
                                                  item2,
                                                  data2 - data1 AS intervalo
                                               FROM itens3
                                               )
                                            SELECT SUM(intervalo)/(SELECT COUNT(1) FROM itens4) AS media FROM itens4";
        $res_total_interacao =  pg_query($con, $sql_total_interacao);
        $total_interacao = pg_fetch_result($res_total_interacao, 0, 'media');
        $total_interacao = str_replace(['days','Days'], 'Dias', $total_interacao);
        $total_interacao = str_replace(['day','Day'], 'Dia', $total_interacao);
        $total_interacao = preg_replace('/\.\d+$/','',$total_interacao);

        $sql_total_fechamento = "SELECT SUM(fechamento)/(SELECT COUNT(1) FROM tmp_hd_hd WHERE fechamento NOTNULL) AS total_fechamento FROM tmp_hd_hd";
        $res_total_fechamento = pg_query($con, $sql_total_fechamento);
        $total_fechamento = pg_fetch_result($res_total_fechamento, 0, 'total_fechamento');
        $total_fechamento = str_replace(['days','Days'], 'Dias', $total_fechamento);
        $total_fechamento = str_replace(['day','Day'], 'Dia', $total_fechamento);
        $total_fechamento = preg_replace('/\.\d+$/','',$total_fechamento);
        
        $total_coluna = array(
                            "",
                            "",
                            "",
                            "",
                            "",
                            "",
                            "",
                            "Média Total: ",
                            "$total_interacao",
                            "$total_fechamento",
                            "",
                            ""
                        );

        fwrite($file, implode(";", $total_coluna)."\n");

        fclose($file);
        if (file_exists("/tmp/{$fileName}")) {
            system("mv /tmp/{$fileName} xls/{$fileName}");
            echo "xls/{$fileName}";
        }
    }
    exit;
}

$layout_menu = "callcenter";
$title = "RELATÓRIO DASHBOARD HELPDESK POSTO X FÁBRICA";
include 'cabecalho_new.php';


$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable",
    "select2"
);

include("plugin_loader.php");
?>

<script type="text/javascript">
    var hora = new Date();
    var engana = hora.getTime();

    $(function() {
        $.datepickerLoad(Array("data_final", "data_inicial"));
        $.autocompleteLoad(Array("posto"));
        Shadowbox.init();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });

        $("select").select2();

        $.dataTableLoad({ 
            table: "#result_dados", 
        });

        $("#btn_dashboard").click(function(){

            var url = "";

            if ($("#data_inicial").val() != "" && $("#data_inicial").val() != undefined) {
                url = url + "data_inicial="+$("#data_inicial").val();
            }

            if ($("#data_final").val() != "" && $("#data_final").val() != undefined) {
                url = url + "&data_final="+$("#data_final").val();
            }

            if ($("#codigo_posto").val() != "" && $("#codigo_posto").val() != undefined) {
                url = url + "&codigo_posto="+$("#codigo_posto").val();
            }

            if ($("#descricao_posto").val() != "" && $("#descricao_posto").val() != undefined) {
                url = url + "&descricao_posto="+$("#descricao_posto").val();
            }

            if ($("#atendimento").val() != "" && $("#atendimento").val() != undefined) {
                if (url == "") {
                    url = "atendimento="+$("#atendimento").val();    
                } else {
                    url = url + "atendimento="+$("#atendimento").val();
                }
            }

            if ($("#atendente option:selected").val() != "" && $("#atendente option:selected").val() != undefined) {
                url = url + "&atendente="+$("#atendente option:selected").val();
            }

            if ($("#regiao option:selected").val() != "" && $("#regiao option:selected").val() != undefined) {
                url = url + "&regiao="+$("#regiao option:selected").val();
            }

            if ($("#categoria option:selected").val() != "" && $("#categoria option:selected").val() != undefined) {
                url = url + "&categoria="+$("#categoria option:selected").val();
            }

            if ($("#nota option:selected").val() != "" && $("#nota option:selected").val() != undefined) {
                url = url + "nota="+$("#nota option:selected").val();
            }
    
            window.open("dashboard_helpdesk_posto.php?"+url);

        });

    });

    function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
        $("#descricao_posto").val(retorno.nome);
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
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
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
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='codigo_posto'>Código Posto</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $posto_codigo ?>" >
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
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class="span2">
            <div class='control-group <?=(in_array("atendimento", $msg_erro["campos"])) ? "error" : ""?>' >
                <label class="control-label" >Atendimento</label>
                <div class="controls controls-row" >
                <h5 class='asteristico'>*</h5>
                    <div class="span12" >
                        <input type="text" class="span12" name="atendimento" id="atendimento" value="<? echo $atendimento ?>">
                    </div>
                </div>
            </div>
        </div>
        <div class="span4">
            <div class="control-group" >
                <label class="control-label" >Atendente</label>
                <div class="controls controls-row" >
                    <div class="span12" >
                        <select class="span12" id="atendente" name="atendente" >
                            <option value="" >Selecione</option>
                            <?php
                            $cond_atendente = ($login_fabrica == 1) ? "(admin_sap OR fale_conosco)" : "admin_sap";
                            $sqlAtendente = "   SELECT admin, 
                                                       nome_completo
                                                FROM tbl_admin
                                                WHERE fabrica = $login_fabrica
                                                AND ativo is true
                                                AND $cond_atendente
                                                ORDER BY nome_completo, login";
                            $resAtendente = pg_query($con, $sqlAtendente);

                            while ($row = pg_fetch_object($resAtendente)) {
                                $selected = ($row->admin == $_POST["atendente"]) ? "selected" : "";
                                echo "<option value='{$row->admin}' {$selected} >{$row->nome_completo}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="span4" >
            <div class="control-group" >
                <label class="control-label" >Região</label>
                <div class="controls controls-row" >
                    <div class="span10" >
                        <select class="span10" id="regiao" name="regiao" >
                            <option value="" >Selecione</option>
                            <?php
                            $sqlRegiao = "
                                SELECT regiao, descricao
                                FROM tbl_regiao
                                WHERE fabrica = {$login_fabrica}
                                AND ativo IS TRUE
                                ORDER BY descricao
                            ";
                            $resRegiao = pg_query($con, $sqlRegiao);

                            while ($row = pg_fetch_object($resRegiao)) {
                                $selected = ($row->regiao == $_POST["regiao"]) ? "selected" : "";
                                echo "<option value='{$row->regiao}' {$selected} >{$row->descricao}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span4" >
            <div class="control-group" >
                <label class="control-label" >Tipo de Solicitação</label>
                <div class="controls controls-row" >
                    <div class="span12" >
                        <select class="span12" id="categoria" name="categoria" >
                            <option value="" >Selecione</option>
                            <?php
        
                                foreach ($categorias as $categoria => $config) {
                                    if ($config["no_fabrica"]) {
                                        if (in_array($login_fabrica, $config["no_fabrica"])) {
                                            continue;
                                        }
                                    }

                                    $categoriaSelected = ($categoria == $_POST['categoria']) ? $_POST['categoria'] : "";

                                    echo CreateHTMLOption($categoria, $config['descricao'],$categoriaSelected);
                                } 

                                if ($login_fabrica == 1) {
                                    $tipos_extras = array(
                                        "nova_duvida_pecas"   => "Dúvida sobre peças",
                                        "nova_duvida_pedido"  => "Dúvidas sobre Pedido",
                                        "nova_duvida_produto" => "Dúvidas sobre produtos",
                                        "nova_erro_fecha_os"  => "Problemas no fechamento da O.S.",
                                    );

                                    foreach ($tipos_extras as $categoria => $descricao_categ) {
                                        if($categoria == $_POST['categoria']){
                                            $selected = " selected ";
                                        }else{
                                            $selected = "";
                                        }
                                      
                                        echo "<option value='$categoria' $selected >$descricao_categ</option>";
                                    }
                                }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="span2"></div>
        <div class="span4" >
            <div class="control-group" >
                <label class="control-label" >Nota de Satisfação</label>
                <div class="controls controls-row" >
                    <div class="span10" >
                        <select class="span10" id="nota" name="nota" >
                            <option value="" >Selecione</option>
                            <?php
                                $array_nota = array("ruim"=>"Ruim","regular"=>"Regular","bom"=>"Bom","otimo"=>"Ótimo");

                                foreach ($array_nota as $key => $value) {
                                    if($key == $_POST['nota']){
                                        $selected = " selected ";
                                    }else{
                                        $selected = "";
                                    }    
                                    
                                    echo "<option value='$key' $selected >$value</option>";
                                }  
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <p><br/>
        <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
    </p><br/>
</form>
</div>

<?php
if (pg_num_rows($res_dados) > 0){
?>
    <div class="container-fluid">
        <table id="result_dados" class='table table-striped table-bordered table-hover table-fixed' >
            <thead>
                <tr class='titulo_coluna' >
                    <th>Atendimento</th>
                    <th>Data</th>
                    <th>Status</th>
                    <th>Posto</th>
                    <th>Nome Posto</th>
                    <th>Atendente</th>
                    <th>Região</th>
                    <th>Tipo de Solicitação</th>
                    <th>Média do Tempo entre Interações</th>
                    <th>Média do Tempo de Fechamento</th>
                    <th>Nota da Avaliação</th>
                    <th>Mensagem</th>
                </tr>
            </thead>
            <tbody id="tbdoy_result_dados">
            <?php 
                for ($i=0; $i < pg_num_rows($res_dados); $i++) { 
                    $hd_chamado    = pg_fetch_result($res_dados, $i, 'hd_chamado'); 
                    $data          = pg_fetch_result($res_dados, $i, 'data'); 
                    $status        = pg_fetch_result($res_dados, $i, 'status'); 
                    $codigo_posto  = pg_fetch_result($res_dados, $i, 'codigo_posto'); 
                    $nome          = pg_fetch_result($res_dados, $i, 'nome');  
                    $nome_completo = pg_fetch_result($res_dados, $i, 'nome_completo');  
                    $estado        = pg_fetch_result($res_dados, $i, 'estado');  

                    if (!empty($estado)) {
                        $sql_regiao = "SELECT descricao FROM tbl_regiao WHERE estados_regiao ilike '%$estado%' AND fabrica = $login_fabrica";
                        $res_regiao = pg_query($con, $sql_regiao);
                        $regiao_desc = pg_fetch_result($res_regiao, 0, 'descricao'); 
                    }

                    $categoria     = pg_fetch_result($res_dados, $i, 'categoria');  

                    if (!empty($categoria)) {
                        $categoria_desc = "";
                        $tipos_extras = array(
                            "nova_duvida_pecas"   => "Dúvida sobre peças",
                            "nova_duvida_pedido"  => "Dúvidas sobre Pedido",
                            "nova_duvida_produto" => "Dúvidas sobre produtos",
                            "nova_erro_fecha_os"  => "Problemas no fechamento da O.S.",
                        );

                        foreach ($tipos_extras as $key => $value) {
                            if ($categoria == $key) {
                                $categoria_desc = $value;
                                break;
                            }
                        }

                        if (!empty($categoria_desc)) {
                            foreach ($categorias as $key => $value) {
                                if ($value["no_fabrica"]) {
                                    if (in_array($login_fabrica, $value["no_fabrica"])) {
                                        continue;
                                    }
                                }

                                if ($categoria == $key) {
                                    $categoria_desc = $value['descricao'];
                                    break;
                                }
                            }
                        }  
                    }

                    $sql_interacoes = " WITH itens1 AS (
                                                    SELECT
                                                    row_number() OVER(ORDER BY hd_chamado_item) AS ordem,
                                                    tbl_hd_chamado_item.hd_chamado_item,
                                                    tbl_hd_chamado_item.data,
                                                    tbl_hd_chamado_item.hd_chamado
                                                    FROM tbl_hd_chamado_item
                                                    WHERE tbl_hd_chamado_item.hd_chamado = $hd_chamado
                                                    UNION
                                                    SELECT 0 AS ordem,
                                                    0 AS hd_chamado_item ,
                                                    data,
                                                    hd_chamado
                                                    FROM tbl_hd_chamado
                                                    WHERE hd_chamado = $hd_chamado
                                            ),
                                            itens2 AS (
                                                    SELECT
                                                    row_number() OVER(ORDER BY hd_chamado_item) AS ordem,
                                                    tbl_hd_chamado_item.hd_chamado_item,
                                                    tbl_hd_chamado_item.data,
                                                    tbl_hd_chamado_item.hd_chamado
                                                    FROM tbl_hd_chamado_item
                                                    WHERE tbl_hd_chamado_item.hd_chamado = $hd_chamado
                                            ),
                                            itens3 AS (
                                                    SELECT  itens1.hd_chamado_item AS item1, 
                                                            itens2.hd_chamado_item AS item2,
                                                            itens2.data AS data2, 
                                                            itens1.data AS data1 
                                                    FROM itens2 
                                                    JOIN itens1 USING(hd_chamado) 
                                                    WHERE itens1.ordem = itens2.ordem - 1 
                                                    ORDER BY itens1.hd_chamado_item
                                            ),
                                            itens4 AS (
                                                        SELECT item1, 
                                                              item2, 
                                                              data2 - data1 AS intervalo 
                                                        FROM itens3
                                                        )
                                            SELECT SUM(intervalo)/(SELECT COUNT(1) FROM itens4) AS media FROM itens4";
                    $res_interacoes = pg_query($con, $sql_interacoes);
                        
                    $interacao    = pg_fetch_result($res_interacoes, 0, 'media');        
                    $interacao    = str_replace(['days','Days'], 'Dias', $interacao);
                    $interacao    = str_replace(['day','Day'], 'Dia', $interacao);
                    $interacao    = preg_replace('/\.\d+$/','',$interacao);

                    $fechamento    = pg_fetch_result($res_dados, $i, 'fechamento');  
                    $fechamento    = str_replace(['days','Days'], 'Dias', $fechamento);
                    $fechamento    = str_replace(['day','Day'], 'Dia', $fechamento);
                    $fechamento    = preg_replace('/\.\d+$/','',$fechamento);

                    $arry_campos   = json_decode(pg_fetch_result($res_dados, $i, 'array_campos_adicionais'), true); 
                    $nota          = $arry_campos['avaliacao_pontuacao']; 
                    $mensagem      = (mb_check_encoding($arry_campos['avaliacao_mensagem'], 'UTF-8')) ? utf8_decode($arry_campos['avaliacao_mensagem']) : $arry_campos['avaliacao_mensagem']; 
            ?>
                <tr>
                    <td class='tal'><a href="helpdesk_cadastrar.php?hd_chamado=<?=$hd_chamado?>"><?=$hd_chamado?></a></td>
                    <td class='tal'><?=$data?></td>
                    <td class="tal"><?=$status?></td>
                    <td class="tal"><?=$codigo_posto?></td>
                    <td class='tal'><?=$nome?></td>
                    <td class="tal"><?=$nome_completo?></td>
                    <td class="tal"><?=$regiao_desc?></td>
                    <td class="tal"><?=$categoria_desc?></td>
                    <td class="tal"><?=$interacao?></td>
                    <td class="tal"><?=$fechamento?></td>
                    <td class="tal"><?=$nota?></td>
                    <td class="tal"><?=$mensagem?></td>
                </tr>    
            <?php
                }
            ?>
            </tbody>
            <tfoot>
               <tr class="titulo_coluna" >
                <th>&nbsp;</th>
                <th>&nbsp;</th>
                <th>&nbsp;</th>
                <th>&nbsp;</th>
                <th>&nbsp;</th>
                <th>&nbsp;</th>
                <th>&nbsp;</th>
                <td class="vam" >Média Total:</td>
                <?php

                $sql_total_interacao = "    WITH    itens1 AS (
                                                            SELECT
                                                            row_number() OVER(ORDER BY hd_chamado, hd_chamado_item) AS ordem,
                                                            tbl_hd_chamado_item.hd_chamado_item,
                                                            tbl_hd_chamado_item.data,
                                                            tbl_hd_chamado_item.hd_chamado
                                                            FROM tbl_hd_chamado_item
                                                            WHERE tbl_hd_chamado_item.hd_chamado IN (SELECT hd_chamado FROM tmp_hd_hd)
                                                            UNION
                                                            SELECT 0 AS ordem,
                                                            0 AS hd_chamado_item ,
                                                            data,
                                                            hd_chamado
                                                            FROM tbl_hd_chamado
                                                            WHERE hd_chamado IN (SELECT hd_chamado FROM tmp_hd_hd)
                                                            ORDER BY hd_chamado, hd_chamado_item
                                                    ),
                                                    itens2 AS (
                                                            SELECT
                                                            row_number() OVER(ORDER BY hd_chamado, hd_chamado_item) AS ordem,
                                                            tbl_hd_chamado_item.hd_chamado_item,
                                                            tbl_hd_chamado_item.data,
                                                            tbl_hd_chamado_item.hd_chamado
                                                            FROM tbl_hd_chamado_item
                                                            WHERE tbl_hd_chamado_item.hd_chamado IN (SELECT hd_chamado FROM tmp_hd_hd)
                                                            ORDER BY hd_chamado, hd_chamado_item
                                                    ),
                                                    itens3 AS (
                                                      SELECT  itens1.hd_chamado_item AS item1,
                                                        itens2.hd_chamado_item AS item2,
                                                        itens2.data AS data2,
                                                        itens1.data AS data1
                                                      FROM itens2
                                                      JOIN itens1 USING(hd_chamado)
                                                      WHERE itens1.ordem = itens2.ordem - 1
                                                      ORDER BY itens1.hd_chamado, itens1.hd_chamado_item
                                                    ),
                                                    itens4 AS (
                                                       SELECT item1,
                                                          item2,
                                                          data2 - data1 AS intervalo
                                                       FROM itens3
                                                       )
                                                    SELECT SUM(intervalo)/(SELECT COUNT(1) FROM itens4) AS media FROM itens4";
                $res_total_interacao =  pg_query($con, $sql_total_interacao);
                $total_interacao = pg_fetch_result($res_total_interacao, 0, 'media');
                $total_interacao = str_replace(['days','Days'], 'Dias', $total_interacao);
                $total_interacao = str_replace(['day','Day'], 'Dia', $total_interacao);
                $total_interacao = preg_replace('/\.\d+$/','',$total_interacao);

                $sql_total_fechamento = "SELECT SUM(fechamento)/(SELECT COUNT(1) FROM tmp_hd_hd WHERE fechamento NOTNULL) AS total_fechamento FROM tmp_hd_hd";
                $res_total_fechamento = pg_query($con, $sql_total_fechamento);
                $total_fechamento = pg_fetch_result($res_total_fechamento, 0, 'total_fechamento');
                $total_fechamento = str_replace(['days','Days'], 'Dias', $total_fechamento);
                $total_fechamento = str_replace(['day','Day'], 'Dia', $total_fechamento);
                $total_fechamento = preg_replace('/\.\d+$/','',$total_fechamento);

                ?>
                <td class="tac vam" ><?=(empty($total_interacao)) ? 0 : $total_interacao?></td>
                <td class="tac vam" ><?=(empty($total_fechamento)) ? 0 : $total_fechamento?></td>
                <th>&nbsp;</th>
                <th>&nbsp;</th>
            </tr>
            </tfoot>
        </table>
    </div>
    <br />
    <br />
    <div class="row-fluid">
        <div class="span12 tac">  
        <div class="span4"></div>
            <div class="span2">
                <input type="hidden" id="jsonPOST" value='<?php echo $jsonPOST ?>' />
                <div id='gerar_excel' class="btn_excel">
                    <span><img src='imagens/excel.png' /></span>
                    <span class="txt">Gerar Arquivo CSV</span>
                </div>
            </div>           
            <div class="span2">
                <button type="button" class="btn btn-info" id="btn_dashboard">DashBoard</button>
            </div>
        <div class="span4"></div>
        </div>
    </div>
<?php
}

if (pg_num_rows($res_dados) == 0 && $_POST['btn_acao'] && count($msg_erro) == 0){
    echo '
        <div class="container">
            <div class="alert">
                    <h4>Nenhum resultado encontrado</h4>
            </div>
            </div>
    ';
}

include 'rodape.php';
?>
