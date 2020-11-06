<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
//$admin_privilegios="call_center,gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

if($_POST["btn_acao"] == "submit") {
    $data_inicial       = $_POST['data_inicial'];
    $data_final         = $_POST['data_final'];
    $produto_descricao  = $_POST['produto_descricao'];
    $produto_referencia = $_POST['produto_referencia'];

    if ( (empty($data_inicial) || empty($data_final)) && (empty($produto_referencia) || empty($produto_descricao)) ) {
        $msg_erro["msg"][]    = "Preencha os campos obrigatórios Data ou Produto";
        $msg_erro["campos"][] = "data";
        $msg_erro["campos"][] = "produto";
    } else {
        if (!empty($data_inicial) && !empty($data_final)) {    
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

                if($telecontrol_distrib){
                    if(strtotime($aux_data_inicial.'+6 months') < strtotime($aux_data_final) ) {
                        $msg_erro["msg"][]    = "O intervalo entre as datas não pode ser maior que 6 meses";
                        $msg_erro["campos"][] = "data"; 
                    }
                }else{
                    if(strtotime($aux_data_inicial.'+3 months') < strtotime($aux_data_final) ) {
                        $msg_erro["msg"][]    = "O intervalo entre as datas não pode ser maior que 3 meses";
                        $msg_erro["campos"][] = "data"; 
                    }
                }
            }
        }
    }

    if(!count($msg_erro["msg"])) {
        $cond_produto = '';
        $cond_data    = ''; 
        
        if (!empty($produto_descricao) && !empty($produto_referencia)) {
            $cond_produto = " AND tbl_produto.referencia = '$produto_referencia' ";
        }

        if (!empty($data_inicial) && !empty($data_final)) {
            $cond_data = "AND tbl_os.data_digitacao BETWEEN '$data_inicial' and '$data_final'";
        }



        $sql = " WITH os_ativacao AS(
                    SELECT referencia,descricao, parametros_adicionais::jsonb->>'os_ativacao' AS os
                    FROM tbl_produto
                    WHERE fabrica_i = $login_fabrica
                    AND parametros_adicionais::json->'os_ativacao' IS NOT NULL
                    $cond_produto
                    ),
                    os_periodo AS(
                    SELECT tbl_os.os,tbl_os.sua_os, tbl_os.serie, to_char(tbl_os.data_digitacao,'DD/MM/YYYY HH24:MI') AS data_digitacao, tbl_posto_fabrica.codigo_posto, tbl_posto.nome
                    FROM tbl_os
                    JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
                    JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                    WHERE tbl_os.fabrica = $login_fabrica
                    $cond_data
                    )

                    SELECT os_ativacao.referencia, os_ativacao.descricao, os_periodo.os,os_periodo.sua_os, os_periodo.codigo_posto, os_periodo.nome, os_periodo.serie, os_periodo.data_digitacao
                    FROM os_periodo
                    JOIN os_ativacao ON os_periodo.os = os_ativacao.os::numeric"; 
        $resSubmit = pg_query($con, $sql);
    }

    if($_POST["gerar_excel"]){
        if(pg_num_rows($resSubmit) > 0){
            $data = date("d-m-Y-H:i");
            $fileName = "relatorio_produto_ativacao_automatica-{$data}.xls";
            $file = fopen("/tmp/{$fileName}", "w");

            $thead = "
            <table border='1'>
                <thead>
                    <tr>
                        <th colspan='7' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
                            RELATÓRIO DOS PRODUTOS COM ATIVAÇÃO AUTOMÁTICA
                        </th>
                    </tr>
                    <tr>
                        <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>OS</th>
                        <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Série</th>
                        <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Produto Ref.</th>
                        <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Produto Desc.</th>
                        <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Código do Posto</th>
                        <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Posto</th>
                        <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Digitação OS</th>
                    </tr>
                </thead>
                <tbody>
                ";
                fwrite($file, $thead);

                for ($i = 0; $i < pg_num_rows($resSubmit); $i++) {
                    $os                 = pg_result($resSubmit,$i,'os');
                    $sua_os             = pg_result($resSubmit,$i,'sua_os');
                    $posto_nome         = pg_result($resSubmit,$i,'nome');
                    $codigo_posto       = pg_result($resSubmit,$i,'codigo_posto');
                    $produto_descricao  = pg_result($resSubmit,$i,'descricao');
                    $produto_referencia = pg_result($resSubmit,$i,'referencia');
                    $serie              = pg_result($resSubmit,$i,'serie');
                    $data_digitacao     = pg_result($resSubmit,$i,'data_digitacao');
                    
                    $body .="  
                    <tr>
                        <td nowrap align='center' valign='top'>{$sua_os}</td>
                        <td nowrap align='center' valign='top'>{$serie}</td>
                        <td nowrap align='center' valign='top'>{$produto_referencia}</td>
                        <td nowrap align='center' valign='top'>{$produto_descricao}</td>
                        <td nowrap align='center' valign='top'>{$codigo_posto}</td>
                        <td nowrap align='center' valign='top'>{$posto_nome}</td>
                        <td nowrap align='center' valign='top'>{$data_digitacao}</td>
                    </tr>";
                }
                fwrite($file, $body);
                fwrite($file, "
                    <tr>
                        <th colspan='7' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de ".pg_num_rows($resSubmit)." registros</th>
                    </tr>
                </tbody>
            </table>
            ");
            fclose($file);

            if (file_exists("/tmp/{$fileName}")) {
                system("mv /tmp/{$fileName} xls/{$fileName}");

                echo "xls/{$fileName}";
            }
                    
        }
        exit;
    }
}

$layout_menu = "gerencia";

$title = "RELATÓRIO DE PRODUTOS DE ATIVAÇÃO AUTOMÁTICA";
include 'cabecalho_new.php';

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable"
);

include("plugin_loader.php");
?>

<script type="text/javascript">
    var hora = new Date();
    var engana = hora.getTime();

    $(function() {
        $.datepickerLoad(Array("data_final", "data_inicial"));
        $.autocompleteLoad(Array("produto"));
        Shadowbox.init();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });

    var table = new Object();
    table['table'] = '#resultado_produto_ativado';
    table['type'] = 'full';
    $.dataTableLoad(table);
    });

    function retorna_produto (retorno) {
        $("#produto").val(retorno.produto);
        $("#produto_referencia").val(retorno.referencia);
        $("#produto_descricao").val(retorno.descricao);
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
    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
    <br/>

    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_inicial'>Data Inicial</label>
                <div class='controls controls-row'>
                    <div class='span4'>
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
                        <div class='span8 input-append'>
                            <input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
                            <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
                            <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <br/>
        <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
    </p><br/>
</form>
</div>
<?php
if(isset($resSubmit)) {
    if(pg_num_rows($resSubmit) > 0) {
        echo "<br />";
        $count = pg_num_rows($resSubmit);
?>
    <table id="resultado_produto_ativado" class='table table-striped table-bordered table-hover table-large' >
        <thead>
            <tr class='titulo_coluna' >
                <th>OS</th>
                <th>Série</th>
                <th>Produto Ref.</th>
                <th>Produto Desc.</th>
                <th>Código do Posto</th>
                <th>Posto</th>
                <th>Data Digitação OS</th>
        </thead>
        <tbody>
        <?php
            for($i = 0; $i < $count; $i++) {
                $os                 = pg_result($resSubmit,$i,'os');
                $sua_os             = pg_result($resSubmit,$i,'sua_os');
                $posto_nome         = pg_result($resSubmit,$i,'nome');
                $codigo_posto       = pg_result($resSubmit,$i,'codigo_posto');
                $produto_descricao  = pg_result($resSubmit,$i,'descricao');
                $produto_referencia = pg_result($resSubmit,$i,'referencia');
                $serie              = pg_result($resSubmit,$i,'serie');
                $data_digitacao     = pg_result($resSubmit,$i,'data_digitacao');
                
               
                    $body = "  
                        <tr>
                            <td class='tac' nowrap><a href='os_press.php?os={$os}' target='_blank' >{$sua_os}</a></td>
                            <td class='tac'>{$serie}</td>
                            <td class='tac'>{$produto_referencia}</td>
                            <td class='tac'>{$produto_descricao}</td>
                            <td class='tac'>{$codigo_posto}</td>
                            <td class='tac'>{$posto_nome}</td>
                            <td class='tac'>{$data_digitacao}</td>
                        </tr>
                    ";
                echo $body;
            }
        ?>
        </body>
    </table>
    <br />
    <?php
        $jsonPOST = excelPostToJson($_POST);
    ?>
        <div id='gerar_excel' class="btn_excel">
            <input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
            <span><img src='imagens/excel.png' /></span>
            <span class="txt">Gerar Arquivo Excel</span>
        </div>

<?php
    }else{
        echo '
            <div class="container">
            <div class="alert">
                    <h4>Nenhum resultado encontrado</h4>
            </div>
            </div>';
    }
}

include 'rodape.php';?>
