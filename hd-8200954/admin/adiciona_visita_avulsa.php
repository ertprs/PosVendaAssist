<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

if (isset($_REQUEST["roteiro"]) && strlen($_REQUEST["roteiro"]) > 0) {
    $roteiro = $_REQUEST["roteiro"];
    $retorno  = getResultado($roteiro);
}

if ($_POST["gravar"] == true) {

    $msg_erro          = [];
    $tipo_contato      = $_POST["tipo_contato"];
    $codigo            = $_POST["codigo"];
    $tipo_visita       = $_POST["tipo_visita"];
    $data_visita_posto = $_POST["data_visita_posto"];
    $qtde_horas_posto  = $_POST["qtde_horas_posto"];


    if (strlen($tipo_contato) == 0) {
        $msg_erro["campos"][] = "tipo_contato";
        $msg_erro["msg"][] = "Preencha o campo Tipo de Contato, é obrigatório";
    }
    if (strlen($tipo_visita) == 0) {
        $msg_erro["campos"][] = "tipo_visita";
        $msg_erro["msg"][] = "Preencha o campo Tipo de Visita, é obrigatório";
    }

    if (strlen($data_visita_posto) == 0) {
        $msg_erro["campos"][] = "data_visita_posto";
        $msg_erro["msg"][] = "Preencha o campo Data Visita, é obrigatório";
    }


    if (strlen($tipo_contato) > 0) {

        if ($tipo_contato == "CL") {
            if (strlen($codigo) == 0) {
                $msg_erro["campos"][] = "codigo";
                $msg_erro["campos"][] = "descricao";
                $msg_erro["msg"][] = "Preencha o campo CPF/CNPJ Cliente, é obrigatório";
                $msg_erro["msg"][] = "Preencha o campo Nome do Cliente, é obrigatório";
            }
        }
        if ($tipo_contato == "RV") {
            if (strlen($codigo) == 0) {
                $msg_erro["campos"][] = "codigo";
                $msg_erro["campos"][] = "descricao";
                $msg_erro["msg"][] = "Preencha o campo CNPJ Revenda, é obrigatório";
                $msg_erro["msg"][] = "Preencha o campo Nome da Revenda, é obrigatório";
            }
        }
        if ($tipo_contato == "PA") {
            if (strlen($codigo) == 0) {
                $msg_erro["campos"][] = "codigo";
                $msg_erro["campos"][] = "descricao";
                $msg_erro["msg"][] = "Preencha o campo CNPJ do Posto, é obrigatório";
                $msg_erro["msg"][] = "Preencha o campo Nome do Posto, é obrigatório";
            }
        }
    }
    if (strlen($qtde_horas_posto) == 0) {
        $qtde_horas_posto = "00:00:00";
    }
    if (count($msg_erro["msg"]) == 0) {

        $dados['roteiro']      = $roteiro;
        $dados['tipo_de_visita'] = $tipo_visita;
        $dados['tipo_de_local']  = $tipo_contato;
        $dados['codigo']      = $codigo;
        $dados['data_visita'] = geraDataBD($data_visita_posto);
        $dados['qtde_horas'] =  (strlen($qtde_horas_posto) == 1) ? "0".$qtde_horas_posto.":00" : $qtde_horas_posto;

        $retorno = insertVisita($dados);
            
        if (!$retorno["erro"]) {
            $msg_sucesso["msg"][] = $retorno["msg"];
            echo "<meta http-equiv=refresh content=\"0;URL=adiciona_visita_avulsa.php?roteiro={$roteiro}\">";
        } else {
            $msg_erro["msg"][] = $retorno["msg"];
        }

    }

}

function trataTime($diff) {
    if (strlen($diff->h) == 1) {
        $hora = "0".$diff->h;
    } else {
        $hora = $diff->h;
    }
    if (strlen($diff->i) == 1) {
        $min = "0".$diff->i;
    } else {
        $min = $diff->i;
    }
    if (strlen($diff->s) == 1) {
        $seg = "0".$diff->s;
    } else {
        $seg = $diff->s;
    }

    if ($diff->d > 0) {
        $hora = ($diff->d*24);
    }
    return $hora.":".$min.":".$seg;

}
function validateDate($date, $format = 'Y-m-d H:i:s')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}
function geraDataNormal($data) {
    $vetor = explode('-', $data);
    $dataTratada = $vetor[2] . '/' . $vetor[1] . '/' . $vetor[0];
    return $dataTratada;
}

function geraDataTimeNormal($data) {
    $vetor = explode('-', $data);
    $vetor2 = explode(' ', $vetor[2]);
    $dataTratada = $vetor2[0] . '/' . $vetor[1] . '/' . $vetor[0] . ' ' . $vetor2[1];
    return $dataTratada;
}

function geraDataBD($data) {
 
    list($dia, $mes, $anox) = explode('/', $data);

    $dataTratada = $anox . '-' . $mes . '-' . $dia;
    return $dataTratada;
}

function insertVisita($dados = array()) {
    global $login_fabrica, $con;

    if (empty($dados)) {
        return array("erro" => true, "msg" => "Dados da visita, não enviado");
    }

    $sql = "INSERT INTO tbl_roteiro_posto (
                                            roteiro, 
                                            tipo_de_visita, 
                                            tipo_de_local, 
                                            codigo, 
                                            status, 
                                            data_visita, 
                                            qtde_horas
                                        ) VALUES (
                                            ".$dados['roteiro'].", 
                                            '".$dados['tipo_de_visita']."', 
                                            '".$dados['tipo_de_local']."', 
                                            '".$dados['codigo']."', 
                                            'CF', 
                                            '".$dados['data_visita']."', 
                                            '".$dados['qtde_horas']."'
                                        )";
    $res = pg_query($con, $sql);
    if (pg_last_error()) {
        return array("erro" => true, "msg" => "Erro ao gravar a visita" . pg_last_error());
    }

    return array("erro" => false, "msg" => "Visita gravada com sucesso");
    
}

function getLegendaTipoContato($sigla) {
    $arr =  array("CL" => "Cliente","RV" => "Revenda","PA" => "Posto Autorizado");
    return $arr[$sigla];
}

function getResultado($roteiro) {
    global $login_fabrica, $con;

    $sql = "SELECT tbl_roteiro.*, 
                   tbl_roteiro_tecnico.tecnico
                 FROM tbl_roteiro
                 JOIN tbl_roteiro_tecnico ON tbl_roteiro.roteiro = tbl_roteiro_tecnico.roteiro
                 JOIN tbl_tecnico ON tbl_roteiro.roteiro = tbl_roteiro_tecnico.roteiro
                WHERE tbl_roteiro.fabrica = {$login_fabrica} 
                  AND tbl_roteiro.roteiro = {$roteiro}
               
                ";
    $res = pg_query($con, $sql);
    if (pg_last_error()) {
        return array();
    }
    $dados = pg_fetch_assoc($res);
    $dados["dados_tecnico"] = getTecnicos($dados["tecnico"]);
    
    return $dados;
    
}


function getTecnicos($tecnico = null) {
    global $con,$login_fabrica;
    $cond = "";
    if (strlen($tecnico) > 0) {
        $cond = " AND tbl_tecnico.tecnico = {$tecnico}";
    }

    $sql = "SELECT  tecnico, nome
              FROM tbl_tecnico
             WHERE tbl_tecnico.ativo IS TRUE
               AND tipo_tecnico = 'TF'
               AND tbl_tecnico.fabrica = {$login_fabrica} {$cond} ORDER BY nome ASC";

    $res = pg_query($con, $sql);

    if (strlen($tecnico) > 0) {
        return pg_fetch_object($res);
    }
    return pg_fetch_all($res);
}

function getCliente($cpf){
    global $con,$login_fabrica;

    $sql = "SELECT cpf
                 FROM tbl_cliente
                WHERE tbl_cliente.fabrica = {$login_fabrica}
                  AND tbl_cliente.cpf = '$cpf'";
    $res = pg_query($con, $sql);
    if (pg_last_error() || pg_num_rows($res) == 0) {
        return array();
    }

    return pg_fetch_assoc($res);
}
function getRevenda($cnpj){
    global $con,$login_fabrica;

    $sql = "SELECT cnpj
                 FROM tbl_revenda
                WHERE tbl_revenda.cnpj = '$cnpj'";
    $res = pg_query($con, $sql);
    if (pg_last_error() || pg_num_rows($res) == 0) {
        return array();
    }

    return pg_fetch_assoc($res);
}

function getPosto($cnpj){
    global $con,$login_fabrica;

    $sql = "SELECT tbl_posto_fabrica.cnpj
             FROM tbl_posto_fabrica
             JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto 
              AND tbl_posto_fabrica.fabrica = {$login_fabrica}
            WHERE tbl_posto_fabrica.credenciamento = 'CREDENCIADO' 
              AND tbl_posto.cnpj = '$cnpj'";

    $res = pg_query($con, $sql);
    if (pg_last_error() || pg_num_rows($res) == 0) {
        return array();
    }

    return pg_fetch_assoc($res);
}

function getLegendaTipoVisita($sigla) {
    $legenda = array("VT" => "Visita Técnica","VC" => "Visita Comercial","VA" => "Visita Administrativa","CM" => "Clínica Makita","FE" => "Feira/Evento","TN" => "Treinamento");
    return $legenda[$sigla];
}
$layout_menu = "tecnica";
$title = "Cadastrar Visita na agenda";
include 'cabecalho_new.php';
$plugins = array(
    "datepicker",
    "shadowbox",
    "mask",
    "ajaxform",
    "fancyzoom",
    "dataTable"
);

include("plugin_loader.php");
?>

    <script language="javascript">

        $(function() {
           
            Shadowbox.init();
            $(document).on("click", "span[rel=lupa]", function () {
                $.lupa($(this));
            });


            var datePickerConfig = {maxDate: null, dateFormat: "dd/mm/yy",dayNames: ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'], dayNamesMin: ['D','S','T','Q','Q','S','S','D'], dayNamesShort: ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb','Dom'], monthNames: ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'], monthNamesShort: ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'], nextText: 'Próximo', prevText: 'Anterior'};
            
            $(".horas").mask("99:99");
            $("#data_visita_posto").datepicker(datePickerConfig).mask("99/99/9999");

            $(document).on("change", "select[name=tipo_contato]", function () {
                var tipo_contato = $(this).val();
                var label_codigo = "";
                var label_descricao = "";
                $("#codigo").removeAttr("disabled");
                $("#descricao").removeAttr("disabled");

                if (tipo_contato == 'CL') {
                    label_codigo = "CPF/CNPJ Cliente";
                    label_descricao = "Nome do Cliente";
                    $("input[name=lupa_config]").attr("tipo", "consumidor");
                    $(".lup_cod").attr("parametro", "cnpj");
                    $(".lup_desc").attr("parametro", "nome_consumidor");
                } else if (tipo_contato == 'RV') {
                    label_codigo = "CNPJ Revenda";
                    label_descricao = "Nome da Revenda";
                    $("input[name=lupa_config]").attr("tipo", "revenda");
                    $(".lup_cod").attr("parametro", "cnpj");
                    $(".lup_desc").attr("parametro", "razao_social");
                } else if (tipo_contato == 'PA') {
                    label_codigo = "CNPJ do Posto";
                    label_descricao = "Nome do Posto";
                    $("input[name=lupa_config]").attr("tipo", "posto");
                    $(".lup_cod").attr("parametro", "codigo");
                    $(".lup_desc").attr("parametro", "nome");
                } else {
                    label_codigo = "Código";
                    label_descricao = "Nome";
                    $("#codigo").attr("disabled", true);
                    $("#descricao").attr("disabled", true);
                }

                $(".label_codigo").html(label_codigo);
                $(".label_descricao").html(label_descricao);

            });


        });
        function retorna_consumidor(retorno){       
            $("#descricao").val(retorno.nome);
            $("#codigo").val(retorno.cpf);
        }

        function retorna_posto(retorno){
            $("#codigo").val(retorno.cnpj);
            $("#descricao").val(retorno.nome);
        }

        function retorna_revenda(retorno){
            $("#codigo").val(retorno.cnpj);
            $("#descricao").val(retorno.razao);
        }



    </script>
<?php if ($erro == true) {?>
    <div class="alert alert-error">
        <h4>Nenhuma visita encontrada</h4>
    </div>
<?php exit;}?>
<?php if (count($msg_erro["msg"]) > 0) {?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php }?>
<?php if (count($msg_erro["msg"]) == 0 && count($msg_sucesso["msg"]) > 0) {?>
    <div class="alert alert-success">
        <h4><?=implode("<br />", $msg_sucesso["msg"])?></h4>
    </div>
<?php }?>
    <div class="row">
        <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
    </div>
    <form name='frm_relatorio' METHOD='POST' ACTION='adiciona_visita_avulsa.php?roteiro=<?php echo $roteiro;?>' align='center' class='form-search form-inline tc_formulario' >
        <input type="hidden" name="gravar" value="true">
        <input type="hidden" name="roteiro" value="<?php echo $retorno["roteiro"]; ?>">
        <div class='titulo_tabela '>Cadastrar Visita na Agenda</div>
        <br/>
         <div class="row-fluid">
            <div class="span1"></div>
            <div class='span2'>
                <div class='control-group'>
                    <label class='control-label'>Roteiro</label>
                    <div class='controls controls-row'>
                        <div class='span10 input-append'>
                            <input type="text" disabled class='span12' value="<?php echo $retorno["roteiro"]; ?>" >
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label'>Solicitante</label>
                    <div class='controls controls-row'>
                        <div class='span10 input-append'>
                            <input type="text" disabled class='span12' value="<?php echo $retorno["solicitante"]; ?>" >
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label'>Técnico</label>
                    <div class='controls controls-row'>
                        <div class='span10 input-append'>
                            <input type="text" disabled class='span12' value="<?php echo $retorno["dados_tecnico"]->nome; ?>" >
                        </div>
                    </div>
                </div>
            </div>
            <div class="span1"></div>
        </div>
        <div class="row-fluid">
            <div class="span1"></div>
            <div class='span2'>
                <div class='control-group <?php echo (in_array("tipo_contato", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='tipo_contato'>Tipo de Contato</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <?php
                                $disabled = "disabled";
                                if (strlen($tipo_contato) > 0) {

                                    if ($tipo_contato == "CL") {
                                        $label_cod  = "CPF/CNPJ Cliente";
                                        $label_desc = "Nome do Cliente";
                                        $disabled = "";
                                    } elseif ($tipo_contato == "RV") {
                                        $label_cod  = "CNPJ Revenda";
                                        $label_desc = "Nome da Revenda";
                                        $disabled = "";
                                    } elseif ($tipo_contato == "PA") {
                                        $label_cod  = "CNPJ Posto";
                                        $label_desc = "Nome do Posto";
                                        $disabled = "";
                                    } else {
                                        $label_cod  = "Código";
                                        $label_desc = "Nome";
                                    }
                                } else {
                                    $label_cod  = "Código";
                                    $label_desc = "Nome";
                                }
                            ?>
                            <select name="tipo_contato" id="tipo_contato"  class='span12' >
                                <option value="">Selecione ...</option>
                                <option value="CL" <?php echo ($tipo_contato == "CL") ? "selected" : "";?>>Cliente</option>
                                <option value="RV" <?php echo ($tipo_contato == "RV") ? "selected" : "";?>>Revenda</option>
                                <option value="PA" <?php echo ($tipo_contato == "PA") ? "selected" : "";?>>Posto Autorizado</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span3'>
                <div class='control-group'>
                    <label class='control-label label_codigo' for='codigo'><?php echo $label_cod;?> </label>
                    <div class='controls controls-row'>
                        <div class='span10 input-append'>
                            <input type="text" name="codigo" <?php echo $disabled;?> id="codigo" class='span12' value="<?php echo $codigo ?>" >
                            <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                            <input type="hidden" class="lup_cod" name="lupa_config" tipo="posto" parametro="codigo" cidade="" refe="roteiro" estado=""/>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label label_descricao' for='descricao'><?php echo $label_desc;?> </label>
                    <div class='controls controls-row'>
                        <div class='span11 input-append'>
                            <input type="text" name="descricao" <?php echo $disabled;?> id="descricao" class='span12' value="<?php echo $descricao ?>" >
                            <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                            <input type="hidden" class="lup_desc" name="lupa_config" tipo="posto" parametro="nome" cidade="" refe="roteiro" estado=""/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class='row-fluid'>
            <div class='span1'></div>
            <div class='span2'>
                <div class='control-group <?php echo (in_array("qtde_horas_posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='qtde_horas_posto'>Qtde Horas</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type="text" name="qtde_horas_posto" id="qtde_horas_posto" size="12" maxlength="10" class='span12 horas' value= "<?php echo $qtde_horas_posto;?>">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group <?php echo (in_array("tipo_visita", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='tipo_visita'>Tipo de Visita</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <select name="tipo_visita" id="tipo_visita" class='span12'>
                                <option value="">Selecione ...</option>
                                <option value="VA" <?php echo ($tipo_visita == "VA") ? "selected" : "";?>>Visita Admistrativa</option>
                                <option value="VC" <?php echo ($tipo_visita == "VC") ? "selected" : "";?>>Visita Comercial</option>
                                <option value="VT" <?php echo ($tipo_visita == "VT") ? "selected" : "";?>>Visita Técnica</option>
                                <option value="CM" <?php echo ($tipo_visita == "CM") ? "selected" : "";?>>Clínica Makita</option>
                                <option value="FE" <?php echo ($tipo_visita == "FE") ? "selected" : "";?>>Feira/Evento</option>
                                <option value="TN" <?php echo ($tipo_visita == "TN") ? "selected" : "";?>>Treinamento</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'>
                <div class='control-group <?php echo (in_array("data_visita_posto", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_visita_posto'>Data Visita</label>
                    <h5 class='asteristico'>*</h5>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type="text" name="data_visita_posto" id="data_visita_posto" class='span12' value= "<?php echo $data_visita_posto;?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <p><br/>
            <button class='btn btn-success' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
            <a href="listagem_roteiros.php" class="btn">Listagem de Roteiros</a>
        </p><br/>
    </form> <br />
  </div>
</div> 
<?php include "rodape.php";