<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

if (!$moduloGestaoContrato) {
    echo "<meta http-equiv=refresh content=\"0;URL=menu_gerencia.php\">";
}
use GestaoContrato\AuditoriaContrato;
use GestaoContrato\Contrato;
use GestaoContrato\ContratoStatus;
use GestaoContrato\ContratoStatusMovimento;
use GestaoContrato\Comunicacao;

$objComunicacao             = new Comunicacao($login_fabrica, $con);
$objContratoStatusMovimento = new ContratoStatusMovimento($login_fabrica, $con);
$objContratoStatus          = new ContratoStatus($login_fabrica, $con);
$objContrato                = new Contrato($login_fabrica, $con);
$status_contrato            = $objContratoStatus->get();
$objAudContratos            = new AuditoriaContrato($login_fabrica, $con);
$url_redir                  = "<meta http-equiv=refresh content=\"0;URL=auditoria_contrato.php\">";


if ($_GET["ajax_aprova_reprova_proposta_fabrica"] == true) {

    $tipo     = $_POST["tipo"];
    $contrato = $_POST["contrato"];
    $motivo = $_POST["motivo"];

    if ($tipo == "Aprovar") {
        $result   = $objContrato->aprova_reprova_proposta_fabrica($contrato,"Aprovar",$motivo);
         if ($result) {
            $novoStatus = $objContratoStatus->get(null, "Aguardando Assinatura");
            $objContratoStatusMovimento->add($contrato, $novoStatus["contrato_status"]);

            $dadosContrato = $objContrato->get($contrato);
            $expira = date('Y-m-d H:i:s', strtotime("+15 days",strtotime($dadosContrato[0]["data_aprovacao_fabrica"]))); 
            $token = trim($dadosContrato[0]["contrato"])."|".trim($login_fabrica)."|".trim($dadosContrato[0]["cliente_email"])."|".trim($expira);
            $dadosContrato[0]["token"] = base64_encode($token);
            $objComunicacao->enviaPropostaAprovacaoCliente($dadosContrato[0]);
            exit(json_encode(["erro" => false, "msg" => "Proposta Aprovada com sucesso"]));
        }
        exit(json_encode(["erro" => true, "msg" => "Não foi possível $tipo a proposta"]));
   } else {
        $result   = $objContrato->aprova_reprova_proposta_fabrica($contrato, "Reprovar",$motivo);
        if ($result) {
            $novoStatus = $objContratoStatus->get(null, "Cancelado");
            $objContratoStatusMovimento->add($contrato, $novoStatus["contrato_status"]);

            $dadosContrato = $objContrato->get($contrato);
            $objComunicacao->enviaPropostaReprovadaAuditoriaRepresentante($dadosContrato[0]);
            exit(json_encode(["erro" => false, "msg" => "Proposta Reprovada com sucesso"]));
        }
        exit(json_encode(["erro" => true, "msg" => "Não foi possível $tipo a proposta"]));
    }
}


if (isset($_GET["listar_todas"]) && $_GET["listar_todas"] == true) {
    $cond .= " AND tbl_contrato_auditoria.aprovado IS NULL AND tbl_contrato_auditoria.reprovado IS NULL";
    $dadosAud   = $objAudContratos->get($cond);

} elseif ($_POST["btn_acao"] == "submit") {
    

    $numero_contrato    = $_POST["numero_contrato"];
    $data_ini           = $_POST["data_ini"];
    $data_fim           = $_POST["data_fim"];
    $cliente_cpf   = $_POST["cliente_cpf"];
    $cliente_nome       = $_POST["cliente_nome"];
    $status             = $_POST["status"];

    if (empty($numero_contrato) && (empty($data_ini) || empty($data_fim))) {
        $msg_erro["msg"][]    = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "data_ini";
        $msg_erro["campos"][] = "data_fim";
    }

    if (empty($numero_contrato) && (!empty($data_ini) && !empty($data_final))) {
        list($dia, $mes, $ano) = explode("/", $data_ini);
        if (!strtotime("$ano-$mes-$dia")) {
            $msg_erro["msg"][]      = "Data inválida";
            $msg_erro["campos"][]   = "data_ini";
        } else {
            $data_ini = "$ano-$mes-$dia";
        }

        list($dia, $mes, $ano) = explode("/", $data_fim);
        if (!strtotime("$ano-$mes-$dia")) {
            $msg_erro["msg"][]      = "Data inválida";
            $msg_erro["campos"][]   = "data_fim";
        } else {
            $data_fim = "$ano-$mes-$dia";
        }

        if (count($msg_erro["msg"]) == 0 && strtotime($data_fim) < strtotime($data_ini)) {
            $msg_erro["msg"][]      = "Data Final não pode ser maior que a Data Inicial";
            $msg_erro["campos"][]   = "data_ini";
            $msg_erro["campos"][]   = "data_fim";
        }
    }

    if (count($msg_erro["msg"]) == 0){
        $cond = "";

        if (strlen($data_ini) > 0 && strlen($data_fim) > 0) {
            list($diaI, $mesI, $anoI) = explode("/", $data_ini);
            $dataIni = "$anoI-$mesI-$diaI";
            list($diaF, $mesF, $anoF) = explode("/", $data_fim);
            $dataFim = "$anoF-$mesF-$diaF";
            $cond .= " AND tbl_contrato_auditoria.data_input BETWEEN '{$dataIni} 00:00:00' AND '{$dataFim} 23:59:59'";
        }

        if (strlen($status) > 0 &&  $status == "pendente") {
            $cond .= " AND tbl_contrato_auditoria.aprovado IS NULL AND tbl_contrato_auditoria.reprovado IS NULL";
        } elseif (strlen($status) > 0 &&  $status == "aprovado") {
            $cond .= " AND tbl_contrato_auditoria.aprovado IS NOT NULL AND tbl_contrato_auditoria.reprovado IS NULL";
        } elseif (strlen($status) > 0 &&  $status == "reprovado") {
            $cond .= " AND tbl_contrato_auditoria.reprovado IS NOT NULL AND tbl_contrato_auditoria.aprovado IS NULL";
        }

        if (strlen($cliente_cpf) > 0) {
            $cond .= " AND tbl_cliente_admin.cnpj = '{$cliente_cpf}'";
        }

        if (strlen($numero_contrato) > 0) {
            $cond = " AND tbl_contrato.contrato = {$numero_contrato}";
        }

        $dadosAud   = $objAudContratos->get($cond);
    }

} 

$layout_menu       = "gerencia";
$admin_privilegios = "gerencia";
$title             = traduz("Auditorias de Contratos");
include 'cabecalho_new.php';

$plugins = array(
    "multiselect",
    "datepicker",
    "shadowbox",
    "maskedinput",
    "alphanumeric",
    "price_format",
    "select2",
    "tooltip"
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
    .lista-titulo{
        background: #eee;
        padding: 0px 20px;
        border: solid 2px #ccc;
        cursor: pointer;
        margin-bottom: 10px;
    }
    .lista-titulo:hover{
        background: #ddd;
    }
    .lista-conteudo{
        padding: 20px;
        margin-top: -10px;
        margin-bottom: 10px;
        border: solid 2px #ccc;
        border-top: none;
    }
    .linha-titulo{
        min-height: 0px !important;
        margin-top: 10px;
    }
    .linha-subtitulo{
        min-height: 0px !important;
    }
    .titulo_th th{
        background: #333c51 !important;
        color: #fff;
    }
    .lupa_cliente{
        cursor: pointer;
    }
</style>
<script language="javascript">
    var hora = new Date();
    var engana = hora.getTime();

    $(function() {
        $("#data_ini").datepicker({ minDate: "01/01/2000", dateFormat: "dd/mm/yy" }).mask("99/99/9999");
        $("#data_fim").datepicker({ minDate: "01/01/2000", dateFormat: "dd/mm/yy" }).mask("99/99/9999");
        Shadowbox.init();
        $(".multiple").multiselect({
           selectedText: "# of # selected"
        });
        $(".lista-titulo").on("click", function(){
            
            var posicao = $(this).data("posicao");

            if( $(".lista-conteudo-"+posicao).is(":visible")){
                $(".lista-conteudo-"+posicao).hide();
            }else{
                $(".lista-conteudo-"+posicao).show();
            }
        });

        $(document).on("click", ".lupa_cliente", function () {
            var parametro    = $(this).data('parametro');
            var nome    = $("input[name=cliente_nome]").val();
            var cpf   = $("input[name=cliente_cpf]").val();
        
            if (parametro == "cpf") {
                if (cpf == "") {
                    alert("Digite o CPF/CNPJ");
                    $("input[name=cliente_cpf]").focus();
                    return false;
                } else if (cpf.length < 3) {
                    alert("Digite a ao menos 3 caracteres no CPF/CNPJ");
                    $("input[name=cliente_cpf]").focus();
                    return false;
                }
                var valor = cpf;

            }
            if (parametro == "nome") {
                if (nome == "") {
                    alert("Digite o Nome/Razão Social Cliente");
                    $("input[name=cliente_nome]").focus();
                    return false;
                } else if (nome.length < 3) {
                    alert("Digite a ao menos 3 caracteres no Nome/Razão Social Cliente");
                    $("input[name=cliente_nome]").focus();
                    return false;
                }
                var valor = nome;
            }

            Shadowbox.open({
                content: "cliente_admin_pesquisa_new.php?parametro="+parametro+"&valor="+valor,
                player: "iframe",
                title:  "Busca de Clientes ",
                width:  800,
                height: 500
            });

        });
    });
    

    function retorna_cliente(dados){
        $("#cliente").val(dados.cliente_admin);
        $("#cliente_cpf").val(dados.cnpj);
        $("#cliente_nome").val(dados.nome);
    }

    function aprova_reprova_proposta_fabrica(contrato, tipo) {
        if (contrato == "") {
            alert("Proposta não encontrada");
            return false;
        }
        if (confirm('Deseja '+tipo+' essa Proposta?')) {

            var motivo = prompt("Digite o motivo:");
	    if (motivo != "") {
		   
		    $.ajax({
			url: 'auditoria_contrato.php?ajax_aprova_reprova_proposta_fabrica=true',
			type: 'POST',
			dataType: 'JSON',
			data: {contrato: contrato,tipo:tipo, motivo:motivo},
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
	     } else {
		alert('Preencha o motivo');
		return false;
	
	     }
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
                    <label class="control-label" for="numero_contrato"><?php echo traduz("Número do Contrato");?></label>
                    <div class="controls controls-row">
                        <div class="span11 input-append ">
                            <input type="text" value="<?php echo $numero_contrato;?>" id="numero_contrato" name="numero_contrato" class="span11">
                            <span class="add-on" title="<?php echo traduz("Para pesquisar por Contratos não é necessário informar as datas");?>">
                                    <i class="icon-info-sign"></i>
                                </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span3'>
                <div class='control-group <?=(in_array("data_ini", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'><?php echo traduz("Data Inicial");?></label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" class="span8" value="<?php echo $data_ini;?>" name="data_ini" id="data_ini">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span3'>
                <div class='control-group <?=(in_array("data_fim", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'><?php echo traduz("Data Final");?></label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" class="span8" value="<?php echo $data_fim;?>" name="data_fim" id="data_fim">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row-fluid">
            <div class="span2"></div>
            <div class="span3">
                <div class='control-group <?=(in_array("cliente_cpf", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class="control-label" for="cliente_cpf"><?php echo traduz("CPF/CNPJ Cliente");?></label>
                    <div class="controls controls-row">
                        <div class="span11 input-append ">
                            <input type="text" value="<?php echo $cliente_cpf;?>" id="cliente_cpf" name="cliente_cpf" class="span11">
                            <span class="add-on lupa_cliente"  data-parametro="cpf"><i class="icon-search"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class='spa87'>
                <div class='control-group <?=(in_array("cliente_nome", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='cliente_nome'><?php echo traduz("Nome/Razão Social Cliente");?></label>
                    <div class="controls controls-row">
                        <div class="span5 input-append">
                            <input type="text" value="<?php echo $cliente_nome;?>" name="cliente_nome" id="cliente_nome" class="span12">
                            <span class="add-on lupa_cliente" data-parametro="nome"><i class="icon-search"></i></span>
                        </div>
                        <input type="hidden" value="<?php echo $cliente;?>" name="cliente" id="cliente">
                    </div>
                </div>
            </div>
        </div>
        <div class="row-fluid">
            <div class="span2"></div>
            <div class='span8'>
                <div class='control-group'>
                    <span class="label label-info" >
                        <label class="radio" >
                            <input type="radio" class="status-auditoria-pesquisa" name="status" value="pendente" checked /> <?php echo traduz("Auditoria Pendente");?> 
                        </label>
                    </span>

                    <span class="label label-success" >
                        <label class="radio" >
                            <input type="radio" class="status-auditoria-pesquisa" name="status" value="aprovado" <?=(getValue("status") == "aprovado") ? "checked" : ""?> /> <?php echo traduz("Auditoria Aprovada");?>  
                        </label>
                    </span>
                    <span class="label label-important" >
                        <label class="radio" >
                            <input type="radio" class="status-auditoria-pesquisa" name="status" value="reprovado" <?=(getValue("status") == "reprovado") ? "checked" : ""?> /><?php echo traduz("Auditoria Reprovada");?> 
                        </label>
                    </span>
                </div>
            </div>
        </div>
       
        <p><br/>
                <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));"><?php echo traduz("Pesquisar");?></button>
                <input type='hidden' id="btn_click" name='btn_acao' value='' />
                <a class="btn btn-primary" href="auditoria_contrato.php?listar_todas=true" title="<?php echo traduz("Listar Todas");?>"><?php echo traduz("Listar Todas");?></a>
            </p><br/>
    </form> <br />

    <?php
        if ($dadosAud["erro"]) {
            echo '<div class="alert alert-waring"><h4>'.$dadosAud["msn"].'</h4></div>';
        } else {
    ?>

        <?php 

            foreach ($dadosAud as $i => $rows) {
                if ($rows["aprovado"]) {
                    $status = "aprovado";
                    $label_status =  '<label class="label label-success">'.traduz("Auditoria Aprovado").'</label>';
                } elseif ($rows["reprovado"]) {
                    $status = "reprovado";
                    $label_status =  '<label class="label label-important">'.traduz("Auditoria Reprovado").'</label>';
                } else {
                    $status = "pendente";
                    $label_status =  '<label class="label label-info">'.traduz("Auditoria Pendente").'</label>';
                }


                $dadosContrato = $objContrato->get($rows["contrato"])[0];
                $campo_extra = json_decode($dadosContrato["campo_extra"],1);
                extract($campo_extra);

            ?>
            <div class="lista-titulo" data-posicao="<?php echo $i;?>">
                <div class="row-fluid linha-titulo">
                    <div class="span6">
                        <b>Contrato: #<?php echo $rows["contrato"];?></b> -  <span style="padding-left: 10px;"><?php echo strip_tags($rows["obs"]);?></span>
                    </div>
                    <div class="span2"></div>
                    <?php if ($status == "pendente") {?>
                    <div class="span2">
                        <button type="button" onclick="aprova_reprova_proposta_fabrica(<?php echo $rows["contrato"];?>,'Aprovar');" class="btn btn-success btn-small btn-block"><i class="icon-ok-circle icon-white"></i> <?php echo traduz("Aprovar");?></button>
                    </div>
                    <div class="span2">
                        <button type="button" onclick="aprova_reprova_proposta_fabrica(<?php echo $rows["contrato"];?>,'Reprovar');" class="btn btn-danger btn-small btn-block"><i class="icon-remove-circle icon-white"></i> <?php echo traduz("Reprovar");?></button>
                    </div>
                    <?php }?>
                </div>
                <div class="row-fluid linha-subtitulo">
                    <div class="span9">
                        <?php echo $rows["data"];?> - <?php echo $rows["nome"];?>, <?php echo $rows["cidade"];?>/<?php echo $rows["estado"];?>
                        <p><b><em>Representante:</em></b>  <?php echo $rows["nome_repre"];?></p>
                    </div>
                    <div class="span3" style="text-align: right;"> <?php echo traduz("Status");?>: 
                        <?php echo $label_status;?>
                    </div>
                </div>
            </div>
            <?php if (count($dadosContrato["itens"]) > 0) {?>
            <div class="lista-conteudo lista-conteudo-<?php echo $i;?>" style="display: none;">
                <table class="table striped table-bordered table-fixed">
                        <thead>
                            <tr class="titulo_th">
                                <th width="10%">Referencia</th>
                                <th class="tal">Descrição</th>
                                <th width="10%">Horimetro</th>
                                <th width="10%">Preventiva</th>
                                <th width="10%" nowrap>Preço</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $subtotal = 0;
                            foreach ($dadosContrato["itens"] as $k => $value) {
                                $subtotal += $value["preco"];
                                ?>
                            <tr>
                                <td class="tac"><?=$value["referencia_produto"];?></td>
                                <td class="tal"><?=$value["nome_produto"];?></td>
                                <td class="tac"><?=$value["horimetro"];?></td>
                                <td class="tac"><?=($value["preventiva"] == "t") ? "Sim" : "Não";?></td>
                                <td class="tac" nowrap><?php echo  'R$ '.number_format($value["preco"], 2, ',', '.');?></td>
                            </tr>
                            <?php }?>
                            <tr>
                                <td class="tar" colspan="4"><b>Subtotal</b></td>
                                <td class="tac"><?php echo  'R$ '.number_format($subtotal, 2, ',', '.');?></td>
                            </tr>
                            <tr>
                                <td class="tar" colspan="4"><b>Desconto</b></td>
                                <td class="tac"><?php echo  $desconto_representante."%";?></td>
                            </tr>
                            <tr>
                                <td class="tar" colspan="4"><b>Valor Final</b></td>
                                <td class="tac"><?php echo  'R$ '.number_format($dadosContrato["valor_contrato"], 2, ',', '.');?></td>
                            </tr>
                        </tbody>
                    </table>

		<div class="row-fluid">
			<div class="span12 tac">
				<a href="print_contrato.php?tipo=proposta&contrato=<?php echo $rows["contrato"];?>" class="btn btn-info" target="_blank"><i class="icon-print icon-white"></i> Imprimir</a>	
			</div>
		</div>
            </div>
            <?php }?>
        <?php }?>
    <?php }?>
</div> 
<?php include 'rodape.php';?>
