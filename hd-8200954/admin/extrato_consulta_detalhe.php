<?php

    include 'dbconfig.php';
    include 'includes/dbconnect-inc.php';

    include 'autentica_admin.php';

    if(isset($_POST["btn_acao"])){

        $data_inicial = $_POST["data_inicial"];
        $data_final   = $_POST["data_final"];

        $extrato      = $_POST["extrato"];

        $posto_codigo = $_POST["posto_codigo"];
        $posto_nome   = $_POST["posto_nome"];

        if(strlen($data_inicial) == 0 && strlen($data_final) == 0 && strlen($extrato) == 0){

            $msg_erro["msg"][]    = "Informe os campos obrigatórios";
            $msg_erro["campos"][] = "data";

        }

        if(strlen($data_inicial) == 0 && strlen($extrato) == 0){

            $msg_erro["msg"][]    = "Informe a Data Inicial";
            $msg_erro["campos"][] = "data";

        }else if(strlen($data_final) == 0 && strlen($extrato) == 0){

            $msg_erro["msg"][]    = "Informe a Data Final";
            $msg_erro["campos"][] = "data";

        }

        if(strlen($data_inicial) > 0 && strlen($data_final) > 0){

            list($di, $mi, $yi) = explode("/", $data_inicial);
            list($df, $mf, $yf) = explode("/", $data_final);

            if (!checkdate($mi, $di, $yi) || !checkdate($mf, $df, $yf)) {
                
                $msg_erro["msg"][]    = "Data Inválida";
                $msg_erro["campos"][] = "data";

            } else {

                $aux_data_inicial = "{$yi}-{$mi}-{$di}";
                $aux_data_final   = "{$yf}-{$mf}-{$df}";

                if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
                    $msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
                    $msg_erro["campos"][] = "data";
                }
            }

            $cond_data = " AND EX.data_geracao BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59' ";

        }

        if(strlen($posto_codigo) > 0 && strlen($posto_nome) > 0){

            $sql_posto = "SELECT 
                            tbl_posto_fabrica.posto 
                        FROM tbl_posto 
                        INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto 
                        WHERE 
                            tbl_posto_fabrica.fabrica = {$login_fabrica} 
                            AND tbl_posto_fabrica.codigo_posto = '{$posto_codigo}' 
                            AND tbl_posto.nome = '{$posto_nome}'

                    ";
            $res_posto = pg_query($con, $sql_posto);

            if(pg_num_rows($res_posto) == 0){

                $msg_erro["msg"][]    = "Posto não encontrado";
                $msg_erro["campos"][] = "posto";

            }else{

                $posto = pg_fetch_result($res_posto, 0, "posto");

                $cond_posto = " AND EX.posto = {$posto} ";

            }

        }

        if(strlen($extrato) > 0){

            $cond_extrato = " AND EX.extrato = {$extrato} ";

        }

        if(count($msg_erro["msg"]) == 0){

            $sql_extratos = "SELECT 
                                EX.extrato,
                                OS.os,
                                TO_CHAR (OS.data_abertura,'dd/mm/yyyy') AS data_abertura_os,
                                TO_CHAR (OS.data_fechamento,'dd/mm/yyyy') AS data_fechamento_os,
                                OS.mao_de_obra AS valor_mo,
                                OS.qtde_km_calculada AS valor_km,
                                OS.serie,
                                DC.descricao AS defeito_constatado,
                                PR.referencia AS produto_referencia,
                                PR.descricao AS produto_nome,
                                FP.descricao AS produto_familia,
                                EI.codigo AS item_servico_codigo,
                                EI.descricao AS item_servico_descricao,
                                PO.posto,
                                PO.nome,
                                PO.cnpj,
                                PF.contato_estado AS estado,
                                PF.contato_email AS email,
                                PF.codigo_posto,
                                TP.descricao AS tipo_posto,
                                TO_CHAR (EX.data_geracao,'dd/mm/yyyy') AS data_geracao 
                            FROM tbl_extrato EX 
                            INNER JOIN tbl_posto PO on PO.posto = EX.posto
                            INNER JOIN tbl_posto_fabrica PF ON EX.posto = PF.posto AND PF.fabrica = {$login_fabrica}
                            INNER JOIN tbl_tipo_posto TP ON TP.tipo_posto = PF.tipo_posto AND TP.fabrica = {$login_fabrica} 
                            INNER JOIN tbl_os_extra OSX ON OSX.extrato = EX.extrato  
                            INNER JOIN tbl_os OS ON OS.os = OSX.os AND OS.fabrica = {$login_fabrica} AND OS.excluida IS NOT TRUE 
                            INNER JOIN tbl_produto PR ON PR.produto = OS.produto AND PR.fabrica_i = {$login_fabrica} 
                            INNER JOIN tbl_familia FP ON FP.familia = PR.familia AND FP.fabrica = {$login_fabrica} 
                            INNER JOIN tbl_defeito_constatado DC ON DC.defeito_constatado = OS.defeito_constatado AND DC.fabrica = {$login_fabrica} 
                            INNER JOIN tbl_esmaltec_item_servico EI ON EI.esmaltec_item_servico = DC.esmaltec_item_servico 
                            WHERE 
                                EX.fabrica = {$login_fabrica} 
                                AND PF.distribuidor IS NULL
                                {$cond_data}
                                {$cond_posto} 
                                {$cond_extrato}
                            ";
            $res_extrato = pg_query($con, $sql_extratos);

            $cont_rows = pg_num_rows($res_extrato);

        }

    }

    $layout_menu = "financeiro";
    $title = "CONSULTA E DETALHAMENTO DE EXTRATOS";

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

<script>

    $(function(){

        $.datepickerLoad(Array("data_final", "data_inicial"));
        $.autocompleteLoad(Array("posto"));
        $(".numeric").numeric();

        Shadowbox.init();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });

    });

    function retorna_posto(retorno){
        $("#posto_codigo").val(retorno.codigo);
        $("#posto_nome").val(retorno.nome);
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
    <strong class="obrigatorio pull-right"> * Campos obrigatórios </strong>
</div>

<form name='frm_posto' method='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
    
    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>

    <br />

    <div class='row-fluid'>

        <div class='span2'></div>

        <div class='span3'>
            <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_inicial'>Data Inicial</label>
                <div class='controls controls-row'>
                    <div class='span10'>
                        <h5 class="asteristico">*</h5>
                        <input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$_POST['data_inicial']?>">
                    </div>
                </div>
            </div>
        </div>

        <div class='span3'>
            <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_final'>Data Final</label>
                <div class='controls controls-row'>
                    <div class='span10'>
                        <h5 class="asteristico">*</h5>
                        <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value= "<?=$_POST['data_final']?>">
                    </div>
                </div>
            </div>
        </div>

        <div class='span2'>
            <div class='control-group <?=(in_array("extrato", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='extrato'>Extrato</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <input type="text" name="extrato" id="extrato" size="12" maxlength="12" class='span12 numeric' value= "<?=$_POST['extrato']?>">
                    </div>
                </div>
            </div>
        </div>

        <div class='span2'></div> 

    </div>

    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span3'>
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='codigo_posto'>Código Posto</label>
                <div class='controls controls-row'>
                    <div class='span10 input-append'>
                        <input type="text" name="posto_codigo" id="posto_codigo" class='span12' value="<? echo $posto_codigo ?>" >
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span5'>
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='descricao_posto'>Nome Posto</label>
                <div class='controls controls-row'>
                    <div class='span11 input-append'>
                        <input type="text" name="posto_nome" id="posto_nome" class='span12' value="<? echo $posto_nome ?>" >&nbsp;
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>

    <p>
        <br />
        <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
    </p>

    <br />

</form>

</div>

<?php

    if(isset($cont_rows)){

        if($cont_rows > 0){

            /*
    
            - Número do Extrato;
            - Data do extrato (dia, mês e ano);
            - Número da ordem de serviço;
            - Data de abertura da Ordem de serviço;
            - Data de fechamento da ordem de serviço;
            - UF Posto;
            - Nome do Posto;
            - Descrição do produto;
            - Número de série do produto;
            - Defeito constatado;
            - Valor do defeito constatado;
            - Valor do km (caso tenha km na Ordem de serviço);
            - Nome do item de serviço;
            - Família do produto.

            */

            ?>

            <br />

            <div style="padding: 6px;">

                <table id="relatorio_extratos" class='table table-striped table-bordered table-fixed' style="min-width: 1400px;" >
                    <thead>
                        <tr class="titulo_tabela">
                            <th colspan="14"> Detalhamento de Extratos </th>
                        </tr>
                        <tr class='titulo_coluna'>
                            <th>Extrato</th>
                            <th nowrap>Data Extrato</th>
                            <th>OS</th>
                            <th nowrap>AB OS</th>
                            <th nowrap>FC OS</th>
                            <th width="300" nowrap>Posto</th>
                            <th nowrap>UF Posto</th>
                            <th width="300" nowrap>Produto</th>
                            <th>Série</th>
                            <th>Família</th>
                            <th nowrap>Defeito Constatado</th>
                            <th nowrap>Valor Defeito C.</th>
                            <th nowrap>Valor KM</th>
                            <th width="300" nowrap>Item Serviço</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php

                        $file_name = "relatorio_detalhamento_extratos_{$login_fabrica}_".date("d_m_Y_H_i").".csv";
                        $file      = fopen("/tmp/{$file_name}", "w");

                        header('Content-Type: application/csv; charset=iso-8859-1');
                        header('Content-Disposition: attachment; filename="/tmp/{$file_name}"');

                        $head_file = "Extrato; Data do Extrato; OS; Abertura da OS; Fechamento da OS; Posto; UF do Posto; Produto; Série; Família; Defeito Constatado; Valor do Defeito do Constatado; Valor de KM; Item de Serviço\n";

                        fwrite($file, $head_file);
                        $body_file = "";

                        for ($i = 0; $i < $cont_rows; $i++) { 

                            $extrato                  = pg_fetch_result($res_extrato, $i, "extrato");
                            $data_geracao             = pg_fetch_result($res_extrato, $i, "data_geracao");
                            $os                       = pg_fetch_result($res_extrato, $i, "os");
                            $data_abertura_os         = pg_fetch_result($res_extrato, $i, "data_abertura_os");
                            $data_fechamento_os       = pg_fetch_result($res_extrato, $i, "data_fechamento_os");
                            $data_fechamento_os       = pg_fetch_result($res_extrato, $i, "data_fechamento_os");
                            $valor_defeito_constatado = number_format(pg_fetch_result($res_extrato, $i, "valor_mo"), 2, ",", ".");
                            $valor_km                 = number_format(pg_fetch_result($res_extrato, $i, "valor_km"), 2, ",", ".");
                            $produto_referencia       = pg_fetch_result($res_extrato, $i, "produto_referencia");
                            $produto_nome             = pg_fetch_result($res_extrato, $i, "produto_nome");
                            $produto_serie            = pg_fetch_result($res_extrato, $i, "serie");
                            $produto_familia          = pg_fetch_result($res_extrato, $i, "produto_familia");
                            $posto_codigo             = pg_fetch_result($res_extrato, $i, "codigo_posto");
                            $posto_nome               = pg_fetch_result($res_extrato, $i, "nome");
                            $posto_estado             = pg_fetch_result($res_extrato, $i, "estado");
                            $defeito_constatado       = pg_fetch_result($res_extrato, $i, "defeito_constatado");
                            $item_servico_codigo      = pg_fetch_result($res_extrato, $i, "item_servico_codigo");
                            $item_servico_descricao   = pg_fetch_result($res_extrato, $i, "item_servico_descricao");
                            
                            echo "
                            <tr>
                                <td class='tac'> <a href='extrato_consulta_os.php?extrato={$extrato}' target='_blank'> {$extrato} </a> </td>
                                <td class='tac'>{$data_geracao}</td>
                                <td class='tac'> <a href='os_press.php?os={$os}' target='_blank' > {$os} </a> </td>
                                <td class='tac'>{$data_abertura_os}</td>
                                <td class='tac'>{$data_fechamento_os}</td>
                                <td>{$posto_codigo} - {$posto_nome}</td>
                                <td class='tac'>{$posto_estado}</td>
                                <td>{$produto_referencia} - {$produto_nome}</td>
                                <td>{$produto_serie}</td>
                                <td>{$produto_familia}</td>
                                <td>{$defeito_constatado}</td>
                                <td class='tac'>{$valor_defeito_constatado}</td>
                                <td class='tac'>{$valor_km}</td>
                                <td>{$item_servico_codigo} - {$item_servico_descricao}</td>
                            </tr>
                            ";

                            $body_file .= " {$extrato}; {$data_geracao}; {$os}; {$data_abertura_os}; {$data_fechamento_os}; {$posto_codigo} - {$posto_nome}; {$posto_estado}; {$produto_referencia} - {$produto_nome}; {$produto_serie}; {$produto_familia}; {$defeito_constatado}; {$valor_defeito_constatado}; {$valor_km}; {$item_servico_codigo} - {$item_servico_descricao}\n";

                        }

                        ?>
                        
                    </tbody>

                </table>

            </div>

            <?php
            if ($cont_rows > 50) {
            ?>
                <script>
                    $.dataTableLoad({ table: "#relatorio_extratos" });
                </script>
            <?php
            }

            fwrite($file, $body_file);
            fclose($file);

            if (file_exists("/tmp/{$file_name}")) {
                system("mv /tmp/{$file_name} xls/{$file_name}");
            }

            echo "

            <br />

            <div class='container tac'>
                <a href='xls/$file_name' class='btn btn-success'>Download Arquivo CSV</a>
            </div>
            ";

        }else{

            echo "
                <div class='alert alert-warning'>
                    <h4 class='tac'> Nenhum resultado encontrado! </h4>
                </div>
            ";

        }

    }

?>

<br /> <br />

<?php include "rodape.php"; ?>