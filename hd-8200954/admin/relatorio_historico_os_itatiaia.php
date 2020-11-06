<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";

include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["btn_acao"] == "submit") {
    $data_inicial       = $_POST["data_inicial"];
    $data_final         = $_POST["data_final"];
    $os                 = $_POST["os"];
    $descricao_posto    = $_POST["descricao_posto"];
    
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
		$data_inicial_formatada = (int) $d."-". (int) $m."-".$y." 00:00:00";
		$xdata_inicial_formatada = "$y-$m-$d 00:00:00";
        }
        list($d,$m,$y) = explode("/", $data_final);

        if (!checkdate($m, $d, $y)) {
            $msg_erro['msg'] = "Data final inválida";
            $msg_erro["campos"][]   = "data";
        } else {
		$data_final_formatada = (int) $d."-". (int) $m."-".$y." 23:59:59";
		$xdata_final_formatada = "$y-$m-$d 23:59:59";
        }

        $date = new DateTime($xdata_inicial_formatada); 
        $intervalo = $date->diff(new DateTime($xdata_final_formatada));
        $ano = $intervalo->format('%Y')*12;
        $mes = $intervalo->format('%m');
        $total_meses = $ano+$mes;
        if ($total_meses > 3) {
            $msg_erro["msg"]["obg"] = "Data Inicial e Data Final, deve estar em um intervalo de no maximo 3 meses.";
            $msg_erro["campos"][]   = "data";
        }
        $cond .= " AND tbl_os_itatiaia.dados->>'data_atendimento' BETWEEN '$data_inicial_formatada' and '$data_final_formatada' ";

    }

    if (!empty($os)) {
        $cond .= " AND tbl_os_itatiaia.os = '{$os}'";
    }

    if (!empty($descricao_posto)) {
        $cond .= " AND tbl_os_itatiaia.dados->>'posto' ilike '%$descricao_posto%' ";
    }
  
    if (count($msg_erro['msg']) == 0) {

	   $sql = "SELECT dados, km_qtde_calculada
		    FROM tbl_os_itatiaia
		    WHERE 1 = 1
		    $cond";
                    //echo nl2br($sql);die;
        $resSubmit = pg_query($con,$sql);
    }
}

$layout_menu = "callcenter";
$title = "Consulta Legado Ordens de Serviço";

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
            $("#data_inicial").datepicker(
                "option", {
                    minDate: "01/01/2001",
                   // maxDate: "31/12/2015"
            });

            $('#data_final').datepicker(
                "option", {
                    minDate: "01/01/2001",
                    //maxDate: "31/12/2015"
            });

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
                        <th>Cliente</th>
                        <th width="15%">Qtde Km</th>
                        <th width="15%">Data Atendimento</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                
		for ($i=0; $i < $countRowsSubmit; $i++) {

			$dados_os = json_decode(pg_fetch_result($resSubmit, $i, 'dados'),true);
			$km_qtde_calculada = pg_fetch_result($resSubmit,$i,'km_qtde_calculada');
		
			extract($dados_os);

			$data_abertura = $data_atendimento;

                        if ((empty($data_abertura) || $data_abertura == 'NULL')) {
                            $data_abertura = '';
                        } else {
                            $data_abertura = explode(" ", $data_abertura);
                            list($d,$m,$y) = explode("-", $data_abertura[0]);
                            $data_abertura      = str_pad($d,2,'0',STR_PAD_LEFT)."/".str_pad($m,2,'0',STR_PAD_LEFT)."/".$y;
                        }
                        echo '<tr>
                                <td>
                                    <button class="btn btn-link" onclick="window.open(&quot;os_historico_detalhada_itatiaia.php?os='.$os.'&quot;)">' . $os . '
                                    </button>
                                </td>
                                <td>' . utf8_decode($posto).  '</td>
                                <td>' . utf8_decode($cliente) . '</td>
                                <td class="tac">' . number_format($km_qtde_calculada,2,',','.') . '</td>
                                <td class="tac">' . $data_abertura . '</td>
                             </tr>';
                    }
                ?>
                </tbody>
            </table>
            <?php
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




            
