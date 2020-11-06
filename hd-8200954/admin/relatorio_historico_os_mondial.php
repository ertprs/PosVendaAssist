<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";

include 'autentica_admin.php';
include 'funcoes.php';

$usaExcel = (in_array($login_fabrica, [203])) ? true : false;

if ($_POST["btn_acao"] == "submit") {
    $data_inicial       = $_POST["data_inicial"];
    $data_final         = $_POST["data_final"];
    $os                 = $_POST["os"];
    $descricao_posto    = $_POST["descricao_posto"];
    $produto_referencia = $_POST["produto_referencia"];
    $produto_descricao  = $_POST["produto_descricao"];
    $cpf                = $_POST["cpf"];
    $cliente            = $_POST["cliente"];

    # Validações
    if ((empty($_POST['data_inicial']) || empty($_POST['data_final'])) && (empty($_POST['cliente']) && empty($_POST['os']) && empty($_POST['cpf']))) {
        $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][]   = "data";
    }

    if ($data_inicial > $data_final) {
        $data_inicial = $data_final;
    }

    if (!empty($data_inicial) && !empty($data_final)) {

        list($d,$m,$y) = explode("/", $data_inicial);

        if (!checkdate($m, $d, $y)) {
            $msg_erro['msg'] = "Data inicial inválida";
            $msg_erro["campos"][]   = "data";
        } else {
            $data_inicial_formatada = "$y-$m-$d 00:00:00";
        }

        list($d,$m,$y) = explode("/", $data_final);

        if (!checkdate($m, $d, $y)) {
            $msg_erro['msg'] = "Data final inválida";
            $msg_erro["campos"][]   = "data";
        } else {
            $data_final_formatada = "$y-$m-$d 23:59:59";
        }

        $date = new DateTime($data_inicial_formatada); 
        $intervalo = $date->diff(new DateTime($data_final_formatada));
        $ano = $intervalo->format('%Y')*12;
        $mes = $intervalo->format('%m');
        $total_meses = $ano+$mes;
        if ($total_meses > 3) {
            $msg_erro["msg"]["obg"] = "Data Inicial e Data Final, deve estar em um intervalo de no maximo 3 meses.";
            $msg_erro["campos"][]   = "data";
        }

        $cond .= ($login_fabrica == 203) ? " AND tbl_os.data_abertura BETWEEN '$data_inicial_formatada' and '$data_final_formatada' " : " AND tbl_mondial_os.data_abertura BETWEEN '$data_inicial_formatada' and '$data_final_formatada' ";
    }

    if (!empty($os)) {
        $cond .= ($login_fabrica == 203) ?  " AND tbl_os.os='$os'" : " AND tbl_mondial_os.os='$os'";
    }

    if (!empty($descricao_posto)) {
        $cond .= ($login_fabrica == 203) ? " AND tbl_posto.nome ilike '%$descricao_posto%' " : " AND tbl_mondial_os.posto ilike '%$descricao_posto%' ";
    }

    if (!empty($cliente)) {
        $cond .= ($login_fabrica == 203) ? " AND tbl_os.consumidor_nome ilike '%$cliente%' " : " AND tbl_mondial_os.nome_razao ilike '%$cliente%' ";
    }

    if (!empty($cpf)) {
        $cpf = str_replace("-", "", $cpf);
        $cpf = str_replace(".", "", $cpf);
        $cpf = str_replace("/", "", $cpf);

        $cond .= ($login_fabrica == 203) ? " AND tbl_os.consumidor_cpf ilike '%$cpf%' " : " AND tbl_mondial_os.consumidor_cpf_cnpj ilike '%$cpf%' ";
    }

    if (!empty($produto_referencia)) {
        $cond .= ($login_fabrica == 203) ? " AND tbl_produto.referencia ilike '$produto_referencia%' " : " AND tbl_mondial_os_produto.produto_codigo ilike '$produto_referencia%' ";
    }

    if (!empty($produto_descricao)) {
        $cond .= ($login_fabrica == 203) ? " AND tbl_produto.descricao ilike '%$produto_descricao%' " : " AND tbl_mondial_os_produto.produto_descricao ilike '%$produto_descricao%' ";
    }

    if (count($msg_erro['msg']) == 0) {

        if ($login_fabrica == 203) {
            $sql = "SELECT  tbl_os.os,
                            tbl_os.consumidor_nome AS nome_razao,
                            tbl_os.consumidor_cpf AS consumidor_cpf_cnpj,
                            tbl_os.data_abertura,
                            tbl_posto.nome AS posto,
                            tbl_produto.referencia AS produto_codigo,
                            tbl_produto.descricao AS produto_descricao
                    FROM tbl_os 
                    LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os AND tbl_os_produto.produto = tbl_os.produto
                    LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
                    LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = 167
                    LEFT JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
                    WHERE tbl_os.fabrica = 167
                    AND tbl_os.finalizada IS NOT NULL
                    $cond ";
            //echo nl2br($sql);die;
        } else {
            $sql = "SELECT  tbl_mondial_os.os,
                        tbl_mondial_os.nome_razao,
                        tbl_mondial_os.consumidor_cpf_cnpj,
                        tbl_mondial_os.data_abertura,
                        tbl_mondial_os.posto,
                        tbl_mondial_os_produto.produto_codigo,
                        tbl_mondial_os_produto.produto_descricao
                    FROM tbl_mondial_os 
                    LEFT JOIN tbl_mondial_os_produto ON tbl_mondial_os_produto.os = tbl_mondial_os.os
                    WHERE 1 = 1 $cond ";
                    //echo nl2br($sql);die;
        }

        $resSubmit = pg_query($con,$sql);
    }
}

$layout_menu = "callcenter";
$title = "Consulta Ordens de Serviço Antigas";

include 'cabecalho_new.php';

$plugins = array(
    "shadowbox",
    "datepicker",
    "mask",
    "dataTable"
);

include("plugin_loader.php");
?>
    <script src="js/novo_highcharts.js"></script>
    <script src="js/modules/exporting.js"></script>

    <script>
        $(function(){
            Shadowbox.init();
            $.datepickerLoad(Array("data_final", "data_inicial"));
            <?php if ($login_fabrica == 203) { ?>
                $("#data_inicial").datepicker();
                $('#data_final').datepicker();
            <?php } else { ?>
                $("#data_inicial").datepicker(
                    "option", {
                        minDate: "01/01/2001",
                        maxDate: "31/12/2015"
                });

                $('#data_final').datepicker(
                    "option", {
                        minDate: "01/01/2001",
                        maxDate: "31/12/2015"
                });
            <?php } ?>

            $('#btn_acao').click(function(){
                $.ajax( {
                    type : "POST",
                    url : location.href,
                    beforeSend : function() {
                        $("#loading-block").css({    display:"block"   });
                        $("#loading").css({    display:"block"   });
                    }
                });
            });

            $("span[rel=descricao_posto]").click(function () {
                buscaporlupa($(this));
            });

            $("span[rel=produto_referencia]").click(function () {
                buscaporlupa($(this));

            });

            $("span[rel=produto_descricao]").click(function () {
                buscaporlupa($(this));
            });

            $("span[rel=nome_cliente]").click(function () {
                buscaporlupa($(this));

            });

            $("span[rel=cpf_cliente]").click(function () {
                buscaporlupa($(this));
            });

            function buscaporlupa(that) {
                var dataLupa    = $(that).parent("div").find("input[name=lupaData]");
                var tipo        = $(dataLupa).attr("tipo");
                var parametro   = $(dataLupa).attr("parametro");
                var valor       = $.trim($(that).prev("input").val());

                valor = valor;

                if(typeof num_aux == "undefined"){
                    num_aux = 3;
                }

                if (valor.length >= num_aux) {
                    Shadowbox.open({
                        content: tipo+"_legado_lupa_new.php?parametro="+parametro+"&valor="+valor,
                        player: "iframe",
                        width: 850,
                        height: 600
                    });
                } else {
                    alert("Informe toda ou parte da informação para pesquisar!");
                }
            };
        });

        function retorna_posto(retorno){
            $("#descricao_posto").val(retorno.nome);
        }

        function retorna_produto (retorno) {
            $("#produto_referencia").val(retorno.referencia);
            $("#produto_descricao").val(retorno.descricao);
        }

        function retorna_cliente (retorno) {
            $("#cliente").val(retorno.nome);
            $("#cpf").val(retorno.cpf);
        }
    </script>
    <div class="container">
            <?= ($login_fabrica != 203) ? "
            <div class='alert'>
                <h4>O período disponível para consulta é de 01/01/2001 até 31/12/2015.</h4>
            </div>" : "" ; ?>
            <?php
            /* Erro */
            if (count($msg_erro["msg"]) > 0) {
            ?>
                <div class="alert alert-error">
                    <h4><?php echo implode("<br />", $msg_erro["msg"])?></h4>
                </div>
            <?php } ?>
            <div class="container">
                <strong class="obrigatorio pull-right"> * Campos obrigatórios </strong>
            </div>
        <form name='frm_relatorio' method='POST' action='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
            <div class='titulo_tabela'>Parâmetros de Pesquisa</div> <br/>
            <div class="row-fluid">
                <div class="span2"></div>
                <div class="span8">
                    <div class="control-group ">
                        <label class="control-label" for="os">Número da OS</label>
                        <div class="controls controls-row">
                            <div class="span5">
                                <input type="text" id="os"  value="<?=$os?>" name="os" class="span12 ">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span2"></div>
            </div>
            <div class="row-fluid">
                <div class="span2"></div>
                <div class="span4">
                    <div class="control-group <?php echo (in_array("data", $msg_erro["campos"])) ? "error" : "";?>">
                        <label class="control-label" for="data_inicial">Data Inicial</label>
                        <div class="controls controls-row">
                            <div class="span5">
                                <h5 class="asteristico">*</h5>
                                <input type="text" id="data_inicial" name="data_inicial" class="span12" maxlength="10" value="<?=$data_inicial?>" autocomplete="off">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span4">
                    <div class="control-group <?php echo (in_array("data", $msg_erro["campos"])) ? "error" : "";?>">
                        <label class="control-label" for="data_final">Data Final</label>
                        <div class="controls controls-row">
                            <div class="span5">
                                <h5 class="asteristico">*</h5>
                                <input type="text" id="data_final" value="<?=$data_final?>" name="data_final" class="span12" maxlength="10" autocomplete="off">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span2"></div>
            </div>
            <div class="row-fluid">
                <div class="span2"></div>
                <div class="span4">
                    <div class="control-group ">
                        <label class="control-label" for="descricao_posto">Posto</label>
                        <div class="controls controls-row">
                            <div class="span10  input-append">
                                <input type="text" value="<?=$descricao_posto?>" id="descricao_posto" name="descricao_posto" class="span12">
                                <span class='add-on' rel="descricao_posto"><i class='icon-search'></i></span>
                                <input type="hidden" name="lupaData" tipo="mondial_posto" parametro="descricao" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span6"></div>
            </div>
            <div class="row-fluid">
                <div class="span2"></div>
                <div class="span4">
                    <div class="control-group ">
                        <label class="control-label" for="produto_referencia">Referência Produto</label>
                        <div class="controls controls-row">
                            <div class="span6 input-append">
                                <input type="text" value="<?=$produto_referencia?>" id="produto_referencia" name="produto_referencia" class="span12">
                                <span class='add-on' rel='produto_referencia'><i class='icon-search'></i></span>
                                 <input type="hidden" name="lupaData" tipo="mondial_produto" parametro="referencia" />
                                
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span4">
                    <div class="control-group ">
                        <label class="control-label" for="produto_descricao">Descrição Produto</label>
                        <div class="controls controls-row">
                            <div class="span12  input-append">
                                <input type="text" value="<?=$produto_descricao?>" id="produto_descricao" name="produto_descricao" class="span12">
                                <span class='add-on' rel='produto_descricao'><i class='icon-search'></i></span>
                                <input type="hidden" name="lupaData" tipo="mondial_produto" parametro="descricao" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span2"></div>
            </div>
            <div class="row-fluid">
                <div class="span2"></div>
                <div class="span4">
                    <div class="control-group ">
                        <label class="control-label" for="cliente">Nome Cliente</label>
                        <div class="controls controls-row">
                            <div class="span10  input-append">
                                <input type="text" value="<?=$cliente?>" id="cliente" name="cliente" class="span12">
                                <span class='add-on' rel="nome_cliente"><i class='icon-search'></i></span>
                                <input type="hidden" name="lupaData" tipo="mondial_cliente" parametro="nome" />

                            </div>
                        </div>
                    </div>
                </div>
                <div class="span4">
                    <div class="control-group ">
                        <label class="control-label" for="cpf">CPF Cliente</label>
                        <div class="controls controls-row">
                            <div class="span10  input-append">
                                <input type="text" value="<?=$cpf?>" id="cpf" name="cpf" class="span12">
                                <span class='add-on' rel="cpf_cliente"><i class='icon-search'></i></span>
                                <input type="hidden" name="lupaData" tipo="mondial_cliente" parametro="cpf" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span2"></div>
            </div>         
            <p>
                <br/>
                <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
                <input type='hidden' id="btn_click" name='btn_acao' value='' />
            </p>
            <br/>  
        </form>
    </div>
    </div>
<?php
if (isset($resSubmit)) {
        $countRowsSubmit = pg_num_rows($resSubmit);
        if ($countRowsSubmit > 0) {

?>
            <table align="center" id="resultado_os" class='table table-striped table-bordered table-hover table-large' >
                <thead>
                    <tr class='titulo_coluna'>
                        <th>OS</th>
                        <th>Posto</th>
                        <th width="25%">Produto</th>
                        <th>Cliente</th>
                        <th width="15%">CPF/CNPJ</th>
                        <th width="15%">Data Abertura</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                    /* EXCEL */
                    if ($usaExcel) {
                        $data     = date("d-m-Y-H:i");
                        $fileName = "relatorio_os_antigas_{$data}.xls";

                        $file  = fopen("/tmp/{$fileName}", "w");
                        $thead = "
                            <table border='1'>
                                <thead>
                                    <tr>
                                        <th colspan='6' bgcolor='#D9E2EF' style='color: #333333 !important;'>
                                            ".traduz('RELATÓRIO DE OS ANTIGAS')."
                                        </th>
                                    </tr>
                                    <tr>
                                        <th bgcolor='#596D9B'><font color='#ffffff'>".traduz('Os')."</font></th>
                                        <th bgcolor='#596D9B'><font color='#ffffff'>".traduz('Posto')."</font></th>
                                        <th bgcolor='#596D9B'><font color='#ffffff'>".traduz('Produto')."</font></th>
                                        <th bgcolor='#596D9B'><font color='#ffffff'>".traduz('Cliente')."</font></th>
                                        <th bgcolor='#596D9B'><font color='#ffffff'>".traduz('CPF/CNPJ')."</font></th>
                                        <th bgcolor='#596D9B'><font color='#ffffff'>".traduz('Data Abertura')."</font></th>
                                    </tr>
                                <thead>
                                <tbody>";
                        fwrite($file, $thead);
                    }

                    for ($i=0; $i < $countRowsSubmit; $i++) {

                        $os                 = pg_fetch_result($resSubmit, $i, 'os');
                        $posto_autorizado   = pg_fetch_result($resSubmit, $i, 'posto');
                        $codigo_produto     = pg_fetch_result($resSubmit, $i, 'produto_codigo');
                        $descricao_produto  = pg_fetch_result($resSubmit, $i, 'produto_descricao');
                        $nome_razao         = pg_fetch_result($resSubmit, $i, 'nome_razao');
                        $consumidor_cpf_cnpj= pg_fetch_result($resSubmit, $i, 'consumidor_cpf_cnpj');

                        if (!empty($codigo_produto) || !empty($descricao_produto)) {
                            $produto = $codigo_produto . ' - ' . $descricao_produto;
                        } else {
                            $produto = 'OS sem produto.';
                        }


                        if (!empty($nome_razao)) {
                            $nome_cliente = $nome_razao;
                        } else {
                            $nome_cliente = '';
                        }

                        if (empty($consumidor_cpf_cnpj) || strtolower($consumidor_cpf_cnpj) == 'null') {
                            $cpf_cliente = '';
                        } else {
                            $cpf_cliente = $consumidor_cpf_cnpj;
                        }

                        $data_abertura = pg_fetch_result($resSubmit, $i, 'data_abertura');
                        if ((empty($data_abertura) || $data_abertura == 'NULL')) {
                            $data_abertura = '';
                        } else {
                            $data_abertura = explode(" ", $data_abertura);
                            list($y,$m,$d) = explode("-", $data_abertura[0]);
                            $data_abertura      = "$d/$m/$y";
                        }

                        $link_os = ($login_fabrica == 203) ? "os_press" : "os_historico_detalhada_mondial";
                        $add     = ($login_fabrica == 203) ? "&v=dHJ1ZQ==" : "";
                        echo '<tr> 
                                <td>
                                    <button class="btn btn-link" onclick="window.open(&quot;'.$link_os.'.php?os='.$os.''.$add.'&quot;)">' . $os . '
                                    </button>
                                </td>
                                <td>' . utf8_decode($posto_autorizado).  '</td>
                                <td>' . utf8_decode($produto) . '</td>
                                <td>' . utf8_decode($nome_cliente) . '</td>
                                <td class="tac">' . utf8_decode($cpf_cliente) . '</td>
                                <td class="tac">' . $data_abertura . '</td>
                             </tr>';

                        if ($usaExcel) {
                            $excelTrows .="
                                    <tr>
                                        <td nowrap align='center'>{$os}</td>
                                        <td nowrap align='center' > " . utf8_decode($posto_autorizado). "</td>
                                        <td nowrap align='center' >" . utf8_decode($produto) ."</td>
                                        <td nowrap align='center' > " . utf8_decode($nome_cliente) . "</td>
                                        <td nowrap align='center' >" . utf8_decode($cpf_cliente) ."</td>
                                        <td nowrap align='center' >{$data_abertura}</td>
                                    </tr>";

                            fwrite($file, $excelTrows);
                        }
                    }

                    /* EXCEL */
                    if ($usaExcel) {
                        fwrite($file, " </tbody> </table>");

                        fclose($file);

                        if (file_exists("/tmp/{$fileName}")) {
                            system("mv /tmp/{$fileName} xls/{$fileName}");
                        }
                    }
                ?>
                </tbody>
            </table>
            <?php
                if ($usaExcel) {
                    echo "<div id='gerar_excel' style='padding-left: 44%;'>
                            <a href='xls/{$fileName}' download>
                                <span><img src='imagens/excel.png' style='width: 35px;'/></span>
                                Gerar Arquivo Excel
                            </a>
                        </div>";
                }

                } else {
                    echo '
                    <div class="container">
                        <div class="alert">
                            <h4>Nenhum resultado encontrado</h4>
                        </div>
                    </div>';
                }
            ?>
                <script>
                    $.dataTableLoad({ table: "#resultado_os" });
                </script>
            <br />         
<?php
        

    }//fecha if btn_acao

/**
* include do rodape
*/

include("rodape.php");

?>




            