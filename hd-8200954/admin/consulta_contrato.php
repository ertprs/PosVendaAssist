<?php
$areaAdminRepresentante = preg_match('/\/admin_representante\//',$_SERVER['PHP_SELF']) > 0 ? true : false;
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;
$areaClienteAdmin = preg_match('/\/admin_cliente\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include_once 'fn_traducao.php';

if (!$moduloGestaoContrato) {
    echo "<meta http-equiv=refresh content=\"0;URL=menu_gerencia.php\">";
}

use GestaoContrato\Contrato;
use GestaoContrato\ContratoStatus;
use GestaoContrato\ContratoStatusMovimento;
use GestaoContrato\Comunicacao;

$objContratoStatusMovimento = new ContratoStatusMovimento($login_fabrica, $con);
$objContratoStatus = new ContratoStatus($login_fabrica, $con);
$objContrato       = new Contrato($login_fabrica, $con);
$objComunicacao    = new Comunicacao($externalId, $con,$login_fabrica);
$status_contrato   = $objContratoStatus->get();
$url_redir         = "<meta http-equiv=refresh content=\"0;URL=consulta_contrato.php\">";


if ($_GET["ajax_aprova_reprova_proposta_fabrica"] == true) {

    $tipo     = $_POST["tipo"];
    $contrato = $_POST["contrato"];

    if ($tipo == "Aprovar") {
        $result   = $objContrato->aprova_reprova_proposta_fabrica($contrato,"Aprovar");
         if ($result) {
            $novoStatus = $objContratoStatus->get(null, "Aguardando Assinatura");
            $objContratoStatusMovimento->add($contrato, $novoStatus["contrato_status"]);

            $dadosContrato = $objContrato->get($contrato);
            $expira = date('Y-m-d H:i:s', strtotime("+15 days",strtotime($dadosContrato[0]["data_aprovacao_fabrica"]))); 
            $token = trim($dadosContrato[0]["contrato"])."|".trim($login_fabrica)."|".trim($dadosContrato[0]["cliente_email"])."|".trim($expira);
            $dadosContrato[0]["token"] = base64_encode($token);
            $objComunicacao->enviaPropostaAprovacaoCliente($dadosContrato[0]);

            exit(json_encode(["erro" => false, "msg" => traduz("Proposta Aprovada com sucesso")]));
        }
        exit(json_encode(["erro" => true, "msg" => traduz("Não foi possível $tipo a proposta")]));
   } else {
        $result   = $objContrato->aprova_reprova_proposta_fabrica($contrato, "Reprovar");
        if ($result) {
            $novoStatus = $objContratoStatus->get(null, "Cancelado");
            $objContratoStatusMovimento->add($contrato, $novoStatus["contrato_status"]);
            exit(json_encode(["erro" => false, "msg" => traduz("Proposta Reprovada com sucesso")]));
        }
        exit(json_encode(["erro" => true, "msg" => traduz("Não foi possível $tipo a proposta")]));
    }
}


if ($_GET["ajax_aprova_reprova_proposta_cliente"] == true) {

    $tipo                                   = $_POST["tipo"];
    $contrato                               = $_POST["contrato"];
    $aprovacao_cliente["cliente_email"]     = $login_cliente_admin_email;
    $aprovacao_cliente["cliente_ip"]        = $_SERVER["REMOTE_ADDR"];
    $aprovacao_cliente["data_reprovacao"]   = date("Y-m-d H:i:s");
    $dadosContrato = $objContrato->get($contrato)[0];

    if ($tipo == "Aprovar") {
        $result   = $objContrato->aprova_reprova_proposta_cliente($contrato, "Aprovar",$aprovacao_cliente);
         if ($result) {
            $objComunicacao->enviaNotificacaoPropostaAprovadaReprovadaPorCliente($dadosContrato, "Aprovada");
            exit(json_encode(["erro" => false, "msg" => traduz("Proposta Aprovada com sucesso")]));
        }
        exit(json_encode(["erro" => true, "msg" => traduz("Não foi possível $tipo a proposta")]));
   } else {
        $result   = $objContrato->aprova_reprova_proposta_cliente($contrato, "Reprovar",$aprovacao_cliente);
        if ($result) {
            $objComunicacao->enviaNotificacaoPropostaAprovadaReprovadaPorCliente($dadosContrato, "Reprovada");
            exit(json_encode(["erro" => false, "msg" => traduz("Proposta Reprovada com sucesso")]));
        }
        exit(json_encode(["erro" => true, "msg" => traduz("Não foi possível $tipo a proposta")]));
    }
}

if ($_POST) {

    $contrato_status          = filter_input(INPUT_POST, 'contrato_status');
    $genero_contrato          = filter_input(INPUT_POST, 'genero_contrato');
    $data_inicial             = filter_input(INPUT_POST, 'data_ini');
    $data_final               = filter_input(INPUT_POST, 'data_fim');
    $numero_contrato          = filter_input(INPUT_POST, 'numero_contrato', FILTER_SANITIZE_NUMBER_INT);
    $cliente_admin            = filter_input(INPUT_POST, 'cliente', FILTER_SANITIZE_NUMBER_INT);
    $representante_admin      = filter_input(INPUT_POST, 'representante', FILTER_SANITIZE_NUMBER_INT);

    if (strlen($numero_contrato) > 0) {

        $dadosContratos   = $objContrato->get($numero_contrato);

    } else {

        if (strlen($data_inicial) > 0 AND strlen($data_final) > 0){
            $data_inicial_temp = explode('/', $data_inicial);
            $data_final_temp   = explode('/', $data_final);

            if( !checkdate($data_inicial_temp[1], $data_inicial_temp[0], $data_inicial_temp[2]) OR !checkdate($data_final_temp[1], $data_final_temp[0], $data_final_temp[2]) ) {
                $data_inicial = $data_final = null;
                $msg_erro["msg"][]    = traduz("Data Inicial ou Final Inválida");
                $msg_erro["campos"][] = "data_ini";
                $msg_erro["campos"][] = "data_fim";
            } else {
                $zdata_inicial = DateTime::createFromFormat('d/m/Y', $data_inicial); 
                $zdata_final   = DateTime::createFromFormat('d/m/Y', $data_final);

                if( $zdata_final->diff($zdata_inicial)->m > 6 ){
                    $msg_erro["msg"][]    = traduz("A diferença de datas não pode ser maior que 6 meses");
                    $msg_erro["campos"][] = "data_ini";
                    $msg_erro["campos"][] = "data_fim";
                }
            }
        } else {
            $msg_erro["msg"][] = traduz("Campo Data Inicial e Data Final é obrigatório");
            $msg_erro["campos"][] = "data_ini";
            $msg_erro["campos"][] = "data_fim";
        }
        if (count($msg_erro["msg"]) == 0) {
            $dadosContratos   = $objContrato->get(null, $cliente_admin, geraDataBD($data_inicial), geraDataBD($data_final), $contrato_status, $representante_admin, $genero_contrato);
        }

    }
}
function geraDataTimeNormal($data) {
    list($ano, $mes, $vetor) = explode("-", $data);
    $resto = explode(" ", $vetor);
    $dia = $resto[0];
    return $dia."/".$mes."/".$ano;
}
function geraDataBD($data) {
    list($dia, $mes, $ano) = explode("/", $data);
    return $ano."-".$mes."-".$dia;
}

if ($areaAdminRepresentante) {
    $layout_menu       = "contrato";
    $admin_privilegios = "contrato";
} else {
    $layout_menu       = "gerencia";
    $admin_privilegios = "gerencia";
 
}


$title = traduz("Consulta de Contratos");
if ($areaClienteAdmin) {
    include_once 'cabecalho_novo.php';
} else {
    include_once 'cabecalho_new.php';
}
$legenda_status_cores = [
"fefd9c57" => "Aguardando Aprovação do Cliente",
"fcbbba57" => "Proposta em auditoria de Fabrica",
"face9c57" => "Aguardando Assinatura",
"9cd0fa57" => "Aguardando Treinamento Técnico",
"9cfa9d57" => "Ativo",
"aaaaaa57" => "Finalizado",
"ebfe9a57" => "Aguardando Aprovação da Proposta",
"9cf3fa57" => "Aguardando Renovação",
"fa909057" => "Cancelado",
"ecf3e157" => "Aguardando Transporte",
"a9a9ff57" => "Proposta Reprovado pelo Cliente",
"9393ff57" => "Proposta Reprovado pela Fabrica",
];
$plugins = array(
    "dataTable",
   "multiselect",
   "datepicker",
   "shadowbox",
   "alphanumeric",
   "ajaxform",
   "fancyzoom",
   "price_format",
   "tooltip",
   "select2",
   "leaflet",
   "font_awesome",
   "autocomplete"
);

include("plugin_loader.php");
?>
<style>
    .icon-edit {
        background-position: -95px -75px;
    }
    .icon-remove {
        background-position: -312px -3px;
    }
    .icon-search {
        background-position: -48px -1px;
    }
    .titulo_th th{
        background: #333c51 !important;
        color: #fff;
    }
    .dropdown-menu {
        left: -95px !important;
    }
</style>
<script type="text/javascript" src="../externos/institucional/lib/mask/mask.min.js"></script>

<script language="javascript">
    var hora = new Date();
    var engana = hora.getTime();

    $(function() {
        Shadowbox.init();
        $(".multiple").multiselect({
           selectedText: "# of # selected"
        });
        $("#data_ini").datepicker({ minDate: "01/01/2000", dateFormat: "dd/mm/yy" }).mask("99/99/9999");
        $("#data_fim").datepicker({ minDate: "01/01/2000", dateFormat: "dd/mm/yy" }).mask("99/99/9999");

        $(document).on("click", "span[rel=lupa]", function () {
            $.lupa($(this));

        });
        $("#cliente_cpf").focus(function(){
           $(this).unmask();
           $(this).mask("99999999999999");
        });
        $("#cliente_cpf").blur(function(){
           var el = $(this);
           el.unmask();
           
           if(el.val().length > 11){
               el.mask("99.999.999/9999-99");
           }

           if(el.val().length <= 11){
               el.mask("999.999.999-99");
           }
        });
        $(document).on("click", ".btn-ver-produtos", function() {
            var posicao  = $(this).data("posicao");
            if( $(".mostra_pd_"+posicao).is(":visible")){
              $(".mostra_pd_"+posicao).hide();
            }else{
              $(".mostra_pd_"+posicao).show();
            }
        });

        $(document).on("click", ".lupa_representante", function () {
            var parametro    = $(this).data('parametro');
            var nome    = $("input[name=representante_nome]").val();
            var codigo   = $("input[name=representante_codigo]").val();
        
            if (parametro == "codigo") {
                if (codigo == "") {
                    alert('<?php echo traduz("Digite o Código Representante");?>');
                    $("input[name=representante_codigo]").focus();
                    return false;
                } else if (codigo.length < 3) {
                    alert('<?php echo traduz("Digite a ao menos 3 caracteres no Código");?>');
                    $("input[name=representante_codigo]").focus();
                    return false;
                }
                var valor = codigo;

            }
            if (parametro == "nome") {
                if (nome == "") {
                    alert('<?php echo traduz("Digite o Nome/Razão Social Representante");?>');
                    $("input[name=representante_nome]").focus();
                    return false;
                } else if (nome.length < 3) {
                    alert('<?php echo traduz("Digite a ao menos 3 caracteres no Nome/Razão Social Representante");?>');
                    $("input[name=representante_nome]").focus();
                    return false;
                }
                var valor = nome;
            }

            Shadowbox.open({
                content: "representante_pesquisa_new.php?parametro="+parametro+"&valor="+valor,
                player: "iframe",
                title:  '<?php echo traduz("Busca de Representantes");?>',
                width:  800,
                height: 500
            });

        });

        
        $(document).on("click", ".lupa_cliente", function () {
            var parametro    = $(this).data('parametro');
            var nome    = $("input[name=cliente_nome]").val();
            var cpf   = $("input[name=cliente_cpf]").val();
        
            if (parametro == "cpf") {
                if (cpf == "") {
                    alert('<?php echo traduz("Digite o CPF/CNPJ");?>');
                    $("input[name=cliente_cpf]").focus();
                    return false;
                } else if (cpf.length < 3) {
                    alert('<?php echo traduz("Digite a ao menos 3 caracteres no CPF/CNPJ");?>');
                    $("input[name=cliente_cpf]").focus();
                    return false;
                }
                var valor = cpf;

            }
            if (parametro == "nome") {
                if (nome == "") {
                    alert('<?php echo traduz("Digite o Nome/Razão Social Cliente");?>');
                    $("input[name=cliente_nome]").focus();
                    return false;
                } else if (nome.length < 3) {
                    alert('<?php echo traduz("Digite a ao menos 3 caracteres no Nome/Razão Social Cliente");?>');
                    $("input[name=cliente_nome]").focus();
                    return false;
                }
                var valor = nome;
            }

            Shadowbox.open({
                content: "cliente_admin_pesquisa_new.php?parametro="+parametro+"&valor="+valor,
                player: "iframe",
                title:  '<?php echo traduz("Busca de Clientes");?>',
                width:  800,
                height: 500
            });

        });


    });

    function retorna_representante(dados){
        $("#representante_codigo").val(dados.codigo);
        $("#representante_nome").val(dados.nome);
        $("#representante").val(dados.representante);

    }

    function retorna_cliente(dados){
        $("#cliente").val(dados.cliente_admin);
        $("#cliente_cpf").val(dados.cnpj);
        $("#cliente_nome").val(dados.nome);
    }

    function aprova_reprova_proposta_fabrica(contrato, tipo) {
        if (contrato == "") {
            alert('<?php echo traduz("Proposta não encontrada");?>');
            return false;
        }
        if (confirm('Deseja '+tipo+' essa Proposta?')) {

            $.ajax({
                url: 'consulta_contrato.php?ajax_aprova_reprova_proposta_fabrica=true',
                type: 'POST',
                dataType: 'JSON',
                data: {contrato: contrato,tipo:tipo},
            })
            .done(function(data) {
                alert(data.msg);
                if (data.erro) {
                    return false;
                } else {
                    window.location.reload();
                }
            })
            .fail(function() {
                alert("Não foi possível "+tipo);
                return false;
            });
        }
        return false;

    }
    function aprova_reprova_proposta_cliente(contrato, tipo) {
        if (contrato == "") {
            alert('<?php echo traduz("Proposta não encontrada");?>');
            return false;
        }
        if (confirm('Deseja '+tipo+' essa Proposta?')) {

            $.ajax({
                url: 'consulta_contrato.php?ajax_aprova_reprova_proposta_cliente=true',
                type: 'POST',
                dataType: 'JSON',
                data: {contrato: contrato,tipo:tipo},
            })
            .done(function(data) {
                alert(data.msg);
                if (data.erro) {
                    return false;
                } else {
                    window.location.reload();
                }
            })
            .fail(function() {
                alert('<?php echo traduz("Não foi possível");?> '+tipo);
                return false;
            });
        }
        return false;

    }

</script>
    <?php if (count($msg_erro["msg"]) > 0) {?>
        <div class="alert alert-error">
            <h4><?php echo implode("<br />", $msg_erro["msg"]);?></h4>
        </div>
    <?php }?>

    <?php if (count($msg_sucesso["msg"]) > 0){?>
        <div class="alert alert-success">
            <h4><?php echo implode("<br />", $msg_sucesso["msg"]);?></h4>
        </div>
    <?php }?>

    <div class="row">
        <b class="obrigatorio pull-right">  * <?php echo traduz("Campos obrigatórios");?> </b>
    </div>

    <form name='frm_relatorio' METHOD='POST' enctype="multipart/form-data" ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
        <div class='titulo_tabela '><?php echo traduz("Parâmetros de Pesquisa");?></div>
        <br/>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span3'>
                <div class='control-group <?=(in_array("numero_contrato", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'><?php echo traduz("Número do Contrato");?></label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" class="span12" value="<?php echo $numero_contrato;?>" name="numero_contrato" id="numero_contrato">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'>
                <div class='control-group <?=(in_array("data_ini", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'><?php echo traduz("Data Inicial");?></label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" class="span12" value="<?php echo $data_ini;?>" name="data_ini" id="data_ini">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'>
                <div class='control-group <?=(in_array("data_fim", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'><?php echo traduz("Data Final");?></label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" class="span12" value="<?php echo $data_fim;?>" name="data_fim" id="data_fim">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row-fluid">
            <div class="span2"></div>
            <div class='span3'>
                <div class='control-group'>
                    <label class='control-label'><?php echo traduz("Genêro do Contrato");?></label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <select class="span12" name="genero_contrato">
                                <option value=""><?php echo traduz("Selecione");?>...</option>
                                <option value="L" <?php echo ($genero_contrato == "L") ? "selected" : "";?>><?php echo traduz("Locação");?></option>
                                <option value="M" <?php echo ($genero_contrato == "M") ? "selected" : "";?>><?php echo traduz("Manutenção");?></option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label'><?php echo traduz("Status Contrato");?></label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <select class="span12" name="contrato_status">
                                <option value=""><?php echo traduz("Selecione");?>...</option>
                                <?php 
                                    foreach ($status_contrato as $key => $rows) {
                                        $selected = ($contrato_status == $rows["contrato_status"]) ? "selected" : "";
                                        echo '<option '.$selected.' value="'.$rows["contrato_status"].'">'.$rows["descricao"].'</option>';
                                    }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php if (!$areaClienteAdmin) {?>
        <div class="row-fluid">
            <div class="span2"></div>
            <div class="span3">
                <div class='control-group <?=(in_array("cliente_cpf", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class="control-label" for="cliente_cpf"> <?php echo traduz("CPF/CNPJ Cliente");?></label>
                    <div class="controls controls-row">
                        <div class="span11 input-append ">
                            <input type="text" value="<?php echo $cliente_cpf;?>" id="cliente_cpf" name="cliente_cpf" class="span11">
                            <span class="add-on lupa_cliente"  data-parametro="cpf"><i class="icon-search"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span7'>
                <div class='control-group <?=(in_array("cliente_nome", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='cliente_nome'> <?php echo traduz("Nome/Razão Social Cliente");?></label>
                    <div class="controls controls-row">
                        <div class="span6 input-append">
                            <input type="text" value="<?php echo $cliente_nome;?>" name="cliente_nome" id="cliente_nome" class="span12">
                            <span class="add-on lupa_cliente" data-parametro="nome"><i class="icon-search"></i></span>
                        </div>
                        <input type="hidden" value="<?php echo $cliente;?>" name="cliente" id="cliente">
                    </div>
                </div>
            </div>
        </div>
        <?php } else {?>
            <input type="hidden" value="<?php echo $login_cliente_admin;?>" name="cliente" id="cliente">
        <?php }?>
        <?php if (!$areaAdminRepresentante) {?>
        <div class="row-fluid">
            <div class="span2"></div>
            <div class="span3">
                <div class='control-group <?=(in_array("representante_codigo", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class="control-label" for="representante_codigo"> <?php echo traduz("Código Representante");?></label>
                    <div class="controls controls-row">
                        <div class="span11 input-append">
                            <input type="text" id="representante_codigo" value="<?php echo $representante_codigo;?>" name="representante_codigo" class="span11">
                            <span class="add-on lupa_representante" data-parametro="codigo"><i class="icon-search"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span7'>
                <div class='control-group <?=(in_array("representante_nome", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='representante_nome'> <?php echo traduz("Nome/Razão Social Representante");?></label>
                    <div class="controls controls-row">
                        <div class="span6 input-append">
                            <input type="text"  name="representante_nome" id="representante_nome" value="<?php echo $representante_nome;?>" class="span12">
                            <span class="add-on lupa_representante" data-parametro="nome"><i class="icon-search"></i></span>
                        </div>
                        <input type="hidden" value="<?php echo $representante;?>" name="representante" id="representante">
                    </div>
                </div>
            </div>
        </div>
        <?php } else {?>
            <input type="hidden" value="<?php echo $representante_admin;?>" name="representante" id="representante">
        <?php }?>
        <p><br/>
                <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));"><?php echo traduz("Pesquisar");?></button>
                <input type='hidden' id="btn_click" name='btn_acao' value='' />
                <?php if (!$areaClienteAdmin) {?>
                <a href="cadastro_contrato.php?tipo=proposta" class="btn btn-primary"> <?php echo traduz("Cadastrar Proposta");?></a>
            <?php }?>
            </p><br/>
    </form> <br />
</div>
    <?php
        if ($dadosContratos["erro"]) {
            echo '<div class="alert alert-waring"><h4>'.$dadosContratos["msn"].'</h4></div>';
        } else {
        if (count($dadosContratos) > 0) {
    ?>
<div class="container-fluid">
    <div class="row-fluid">
        <div class="span12 tac">
            <h4>Legenda Status do Contrato</h4>
        </div>
    </div>

    <div class="row-fluid">
        <?php 
        $i = 0;
        foreach ($legenda_status_cores as $key => $value) {
            $style = '';
            if ($i % 4 == 0) {
                $style = "style='margin-left:0px;'";
            }
        ?>
        <div class="span3" <?php echo $style;?>>
            <span style="display: inline-block;width: 40px;height: 10px;background-color: #<?php echo $key;?>"></span>
            <?php echo $value;?></div>
        <?php $i++;}?>

    </div>

    <table class='table table-striped table-bordered table-fixed'>
        <thead>
            <tr class='titulo_coluna' >
                <th nowrap width="11%" align="left"><?php echo traduz("Nº Contrato");?></th>
                <th nowrap width="11%" align="left"><?php echo traduz("Data da Vigência");?></th>
                <th nowrap class="tal"><?php echo traduz("Representante");?></th>
                <th nowrap class="tal"><?php echo traduz("Cliente");?></th>
                <th nowrap><?php echo traduz("Genêro");?></th>
                <th nowrap><?php echo traduz("Valor");?></th>
                <th nowrap><?php echo traduz("Qtde Preventiva");?></th>
                <th nowrap><?php echo traduz("Qtde Corretivas");?></th>
                <th nowrap><?php echo traduz("Status");?></th>
                <th nowrap class="tac"><?php echo traduz("Produtos/Serviços");?></th>
                <th nowrap class="tac"><?php echo traduz("O.S.");?></th>
                <th nowrap><?php echo traduz("Ação");?></th>
            </tr>
        </thead>
        <tbody>
        <?php 
            $xlegenda_status_cores = array_flip($legenda_status_cores);
	    foreach ($dadosContratos as $k => $rows) {
                $mostra_aprova_cliente = false;
                if ($rows["genero_contrato"] == "L") {
                    $genero_contrato = traduz("Locação");
                }
                if ($rows["genero_contrato"] == "M") {
                    $genero_contrato = traduz("Manutenção");
                }

                /*if (strlen($rows["data_aprovacao_cliente"]) == 0 && strlen($rows["data_aprovacao_fabrica"]) == 0 && strlen($rows["data_cancelado"]) == 0) {
                    $nome_status = traduz("Proposta em auditoria de Fabrica");
                    $cor = 'style="background-color: #'.$xlegenda_status_cores["Proposta em auditoria de Fabrica"].' !important"';
                
		} else*/
		if($rows['nome_status'] == "Ativo"){
			$nome_status = $rows["nome_status"];
			$cor = 'style="background-color: #'.$xlegenda_status_cores[$rows["nome_status"]].' !important"';
		}elseif (strlen($rows["data_aprovacao_fabrica"]) > 0 && strlen($rows["data_aprovacao_cliente"]) == 0  && strlen($rows["data_cancelado"]) == 0) {
                    $nome_status = traduz("Aguardando Aprovação do Cliente");
                    if ($areaClienteAdmin) {
                        $mostra_aprova_cliente = true;    
                    }
                    $cor = 'style="background-color: #'.$xlegenda_status_cores["Aguardando Aprovação do Cliente"].' !important"';
                
                } elseif (strlen($rows["data_cancelado"]) > 0 && strlen($rows["aprovacao_cliente"]) > 0) {
                    $nome_status = traduz("Proposta Reprovado pelo Cliente");
                    $cor = 'style="background-color: #'.$xlegenda_status_cores["Proposta Reprovado pelo Cliente"].' !important"';

                } elseif (strlen($rows["data_cancelado"]) > 0 && strlen($rows["aprovacao_cliente"]) == 0) {
                    $nome_status = traduz("Proposta Reprovado pela Fabrica");
                    $cor = 'style="background-color: #'.$xlegenda_status_cores["Proposta Reprovado pela Fabrica"].' !important"';
    
                } else {
                    $nome_status = $rows["nome_status"];
                    $cor = 'style="background-color: #'.$xlegenda_status_cores[$rows["nome_status"]].' !important"';

                }
                
        ?>
            <tr >
                <td <?php echo $cor;?> class='tac'><?php echo $rows["contrato"];?></td>
                <td <?php echo $cor;?> class='tac'><?php echo geraDataTimeNormal($rows["data_vigencia"]);?></td>
                <td <?php echo $cor;?> nowrap class='tal'><?php echo $rows["representante_nome"];?></td>
                <td <?php echo $cor;?> nowrap class='tal'><?php echo $rows["cliente_nome"];?></td>
                <td <?php echo $cor;?> class='tac'><?php echo $genero_contrato;?></td>
                <td <?php echo $cor;?> class='tac' nowrap><?php echo 'R$ '.number_format($rows["valor_contrato"], 2, ',', '.');?></td>
                <td <?php echo $cor;?> class='tac'><?php echo $rows["qtde_preventiva"];?></td>
                <td <?php echo $cor;?> class='tac'><?php echo $rows["qtde_corretiva"];?></td>
                <td <?php echo $cor;?> nowrap class='tac'><?php echo $nome_status;?></td>
                <td <?php echo $cor;?> class='tac'>
                    <button type="button" data-posicao="<?php echo $rows["contrato"];?>" class="btn btn-info btn-ver-produtos btn-mini" title="<?php echo traduz("Ver produtos");?>"><i class="icon-search icon-white"></i>  <?php echo traduz("Ver produtos");?></button>
                </td>
                <td <?php echo $cor;?> class='tac'><?php echo strlen($rows["os"]) > 0 ? "<a href='os_press.php?os=".$rows["os"]."' target='_blank'><b>".$rows["os"]."</b></a>" : "";?></td>
                <td <?php echo $cor;?> width="5%" class='tac' align="center">
                    <div class="btn-group btn-block">
                      <button class="btn btn-smal"><i class="icon-list"></i></button>
                      <button class="btn btn-smal dropdown-toggle" data-toggle="dropdown">
                        <span class="caret"></span>
                      </button>
                      <ul class="dropdown-menu">
                            <?php if ($areaAdmin === true && $objContrato->verificaAuditoriaContrato($rows["contrato"])) {?>
                                <li><a href="#" onclick="aprova_reprova_proposta_fabrica(<?php echo $rows["contrato"];?>,'Reprovar');"><span class="icon-remove"></span>  <?php echo traduz("Reprovar Proposta");?></a></li>
                                <li class="divider"></li>
                                <li><a href="#" onclick="aprova_reprova_proposta_fabrica(<?php echo $rows["contrato"];?>,'Aprovar');"><span class="icon-check"></span>  <?php echo traduz("Aprovar Proposta");?></a></li>
                                <li class="divider"></li>
                            <?php }?>
                            <?php if ($mostra_aprova_cliente) {?>
                                <li><a href="#" onclick="aprova_reprova_proposta_cliente(<?php echo $rows["contrato"];?>,'Aprovar');"><span class="icon-check"></span>  <?php echo traduz("Aprovar Proposta");?></a></li>
                                <li class="divider"></li>
                            <?php }?>
                            <?php if (!$objContrato->verificaAuditoriaContrato($rows["contrato"])) {?>
                                <?php if (!$areaClienteAdmin && in_array(trim($rows["nome_status"]), ["Aguardando Aprovação da Proposta"])) {?>
                                    <li><a target="_blank" href="cadastro_contrato.php?tipo=proposta&contrato=<?php echo $rows["contrato"];?>"><span class="icon-edit"></span>  <?php echo traduz("Alterar");?></a></li>
                                    <li class="divider"></li>
                                <?php }?>
                                <?php if ($areaAdmin === true && !in_array(trim($rows["nome_status"]), ["Aguardando Aprovação da Proposta"])) {?>
                                    <li><a target="_blank" href="cadastro_contrato.php?contrato=<?php echo $rows["contrato"];?>"><span class="icon-edit"></span>  <?php echo traduz("Alterar");?></a></li>
                                    <li class="divider"></li>
                                <?php }?>
                                    <li><a target="_blank" href="print_contrato.php?tipo=proposta&contrato=<?php echo $rows["contrato"];?><?php echo $areaClienteAdmin ? '&pg=print' : '';?>"><span class="icon-print"></span>  <?php echo traduz("Imprimir Proposta");?></a></li>
                                <?php if (!in_array($rows["nome_status"], ["Cancelado", "Aguardando Aprovação da Proposta"])) {?>
                                    <li class="divider"></li>
                                    <li><a target="_blank" href="print_contrato.php?tipo=contrato&contrato=<?php echo $rows["contrato"];?><?php echo $areaClienteAdmin ? '&pg=print' : '';?>"><span class="icon-print"></span>  <?php echo traduz("Imprimir Contrato");?></a></li>
                                <?php }?>
                            <?php }?>
                      </ul>
                    </div>
                </td>
            </tr>
            <?php 
            $dadosItens = $objContrato->getItens($rows["contrato"]);
                if (count($dadosItens) > 0 && !isset($dadosItens["erro"])) {
            ?>
            <tr class="mostra_pd_<?=$rows["contrato"];?>" style="display: none;">
                <td colspan="12">
                    <table class="table striped table-bordered table-fixed">
                        <thead>
                            <tr class="titulo_th">
                                <th width="10%"><?php echo traduz("Referencia");?></th>
                                <th class="tal"><?php echo traduz("Descrição");?></th>
                                <th width="10%" nowrap><?php echo traduz("Preço");?></th>
                                <th width="10%"><?php echo traduz("Horimetro");?></th>
                                <th width="10%"><?php echo traduz("Preventiva");?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dadosItens as $k => $value) {?>
                            <tr>
                                <td class="tac"><?=$value["referencia_produto"];?></td>
                                <td class="tal"><?=$value["nome_produto"];?></td>
                                <td class="tac" nowrap><?php echo  'R$ '.number_format($value["preco"], 2, ',', '.');?></td>
                                <td class="tac"><?=$value["horimetro"];?></td>
                                <td class="tac"><?=($value["preventiva"] == "t") ? "Sim" : "Não";?></td>
                            </tr>
                            <?php }?>
                        </tbody>
                    </table>
                </td>
            </tr>
            <?php }?>
        <?php }?>
        </tbody>
    </table>
    <?php } else {
            echo '<div class="alert alert-waring"><h4>Nenhum registro encontrado.</h4></div>';
        }

        ?>
    <?php }?>
</div>
</div> 
<?php include 'rodape.php';?>
