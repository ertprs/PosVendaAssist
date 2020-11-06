<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="cadastros";
include "autentica_admin.php";
include "funcoes.php";
include dirname(__FILE__) . '/../loja/integracoes/correios-sigep/src/PhpSigep/Bootstrap.php';

use Lojavirtual\Loja;
$objLoja = new Loja();
$url_redir = "<meta http-equiv=refresh content=\"0;URL=loja_configuracao_forma_envio.php\">";

if ($_GET["editar_formas"] == true) {
    $xloja_b2b            = $_GET["xloja_b2b"];
    $configLojaPagamento  = $objLoja->getConfigLoja($xloja_b2b);
    $formas               = json_decode($configLojaPagamento["pa_forma_envio"], 1);
    $loja_escolhida       = $configLojaPagamento["loja_b2b"];
    $xmeio                = $_GET["xmeio"];

    foreach ($formas as $meio => $row) {
        foreach ($row as $nome_forma => $rows) {
            //correios
            if ($nome_forma == "correios") {
                $forma_correios                 = "correios";
                $nome_correios                  = $rows["nome"];
                $ambiente_correios              = $rows["ambiente"];
                $status_correios                = $rows["status"];
                $codAdministrativo_correios     = $rows["codAdministrativo"];
                $usuario_correios               = $rows["usuario"];
                $senha_correios                 = $rows["senha"];
                $cartaoPostagem_correios        = $rows["cartaoPostagem"];
                $cnpjEmpresa_correios           = $rows["cnpjEmpresa"];
                $numeroContrato_correios        = $rows["numeroContrato"];
                $anoContrato_correios           = $rows["anoContrato"];
                $servicos_usados_correios       = $rows["servicos_usados"];
            } 
        }
    }
}


if ($_POST["btn_acao"] == "submit") {

    $msg_erro    = array();
    $msg_success = array();

    $loja_escolhida  = $_POST["loja_escolhida"];
    $forma_escolhida = $_POST["forma_escolhida"];

    if (strlen($loja_escolhida) == 0) {
        $msg_erro["msg"][] = "Escolha Fábrica (Loja)";
    }

    if (count($msg_erro["msg"]) == 0) {
        $retorno = $objLoja->gravaConfigEnvio($_POST);
        if ($retorno["erro"]) {
            $msg_erro["msg"][] = $retorno["msn"];
        } else {
            $msg_success["msg"][] = $retorno["msn"];
            echo $url_redir;
        }
    }
}

$layout_menu = "cadastro";
$title       = "Configuração Forma de Envio";

include "cabecalho_new.php";

$plugins = array(
    "shadowbox",
    "price_format",
    "dataTable"
);

include("plugin_loader.php");
?>

<script type="text/javascript">
    $(function () {
        Shadowbox.init();

        $(".btn-ver-detalhe").click(function(){
            var xloja_b2b = $(this).data("id");
            var nome_forma = $(this).data("nome");
            Shadowbox.open({
                content: "loja_configuracao_forma_envio.php?ajax_ver_detalhes=true&xloja_b2b="+xloja_b2b+"&nome_forma="+nome_forma,
                player: "iframe",
                height: 600
            });
        });
    });
</script>

<?php if (count($msg_erro["msg"]) > 0) {?>
    <div class="alert alert-error">
        <h4><?php echo implode("<br />", $msg_erro["msg"]);?></h4>
    </div>
<?php }?>
<?php if (count($msg_success["msg"]) > 0){?>
    <div class="alert alert-success">
        <h4><?php echo implode("<br />", $msg_success["msg"]);?></h4>
    </div>
<?php }?>
<style>
    .tal{text-align: left;}
    ul.bandeiras{
        list-style-type: none;
    }
    ul.bandeiras li{
        display: inline-block;
        background: #fff;
        margin-bottom: 10px;
        border-radius: 5px;
    }
    ul.bandeiras li:hover{
        display: inline-block;
        background: #f5f5f5;
        margin-bottom: 10px;
        border-radius: 5px;
    }
    .tabs-left > .nav-tabs {
        float: left;
        margin-right: -1px !important;
        border-right: 1px solid #ddd !important;
    }
    .tab-content {
        overflow: hidden !important;
        margin-left: -23px !important;
        padding: 10px;
        border: solid 1px #ddd;
    }
</style>

    <div class="row">
        <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
    </div>

    <form name='frm_relatorio' METHOD='POST' enctype="multipart/form-data" ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
        <?php if ($_GET["editar_formas"] == true && strlen($xloja_b2b) > 0) {?>
            <input type="hidden" name="xloja_b2b" value="<?php echo $_GET["xloja_b2b"];?>">
        <?php }?>
        <div class='titulo_tabela '>Forma de Envio</div>
        <br/>
      
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span8'>
                <div class='control-group'>
                    <label class='control-label'>Fábrica (Loja)</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <select class='span12' name="loja_escolhida" id="loja_escolhida">
                                <option value="">Selecione ...</option>
                                <?php foreach ($objLoja->getByLoja() as $key => $rows) {
                                    if ($rows["checkout"] == 'f') {
                                        continue;
                                    }
                                ?>
                                <option <?php echo ($rows["loja_b2b"] == $loja_escolhida) ? "selected" : "";?> value="<?php echo $rows["loja_b2b"];?>"><?php echo $rows["fabrica_nome"];?></option>
                                <?php }?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div><br />
        <div class="tabbable tabs-left" style="background: #fff;margin:10px;padding:20px">
            <ul class="nav nav-tabs">
                <li class="active"><a href="#correios" data-toggle="tab">Correios - Sigep</a></li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane active" id="correios">
                    <!-- INICIO CONFIGURAÇÃO DO CORREIOS -->
                        <div class='titulo_tabela '>Configuração da Correios - Sigep</div><br/>
                        <div class='row-fluid'>
                            <div class='span12 tac' align="center">
                                <?php echo "<img width='200' src='../loja/layout/img/envios/correios.png' />";?>

                            </div>
                        </div>
                        <div class='row-fluid'>
                            <div class='span7'>
                                <div class='control-group'>
                                    <label class='control-label'>Descrição</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="text" class='span12' name="correios[nome]" value="<?php echo (!empty($nome_correios)) ? $nome_correios : "Correios - Sigep";?>" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class='span3'>
                                <div class='control-group'>
                                    <label class='control-label'>Ambiente</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <select class='span12' name="correios[ambiente]">
                                                <option value="">Selecione ...</option>
                                                <option value="producao" <?php echo ($ambiente_correios == "producao") ? "selected" : "";?>>Produção</option>
                                                <option value="sandbox" <?php echo ($ambiente_correios == "sandbox") ? "selected" : "";?>>Sandbox (Teste)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class='span2'>
                                <div class='control-group'>
                                    <label class='control-label'>Habilitar</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <select class='span12' name="correios[status]">
                                                <option value="">Selecione ...</option>
                                                <option value="1" <?php echo ($status_correios == "1") ? "selected" : "";?>>Sim</option>
                                                <option value="0" <?php echo ($status_correios == "0") ? "selected" : "";?>>Não</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='row-fluid'>
                            <div class='span4'>
                                <div class='control-group'>
                                    <label class='control-label'>Código Administrativo</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="text" class='span12' value="<?php echo $codAdministrativo_correios;?>" name="correios[codAdministrativo]" >
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class='span4'>
                                <div class='control-group'>
                                    <label class='control-label'>Usuário</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="text" class='span12' value="<?php echo $usuario_correios;?>" name="correios[usuario]" >
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class='span4'>
                                <div class='control-group'>
                                    <label class='control-label'>Senha</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="password" class='span12' value="<?php echo $senha_correios;?>" name="correios[senha]" >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='row-fluid'>
                            <div class='span3'>
                                <div class='control-group'>
                                    <label class='control-label'>Cartão de Postagem</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="text" class='span12' value="<?php echo $cartaoPostagem_correios;?>" name="correios[cartaoPostagem]" >
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class='span3'>
                                <div class='control-group'>
                                    <label class='control-label'>CNPJ da Empresa</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="text" class='span12' value="<?php echo $cnpjEmpresa_correios;?>" name="correios[cnpjEmpresa]" >
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class='span3'>
                                <div class='control-group'>
                                    <label class='control-label'>Número do Contrato</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="text" class='span12' value="<?php echo $numeroContrato_correios;?>" name="correios[numeroContrato]" >
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class='span3'>
                                <div class='control-group'>
                                    <label class='control-label'>Ano do Contrato</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="text" class='span12' value="<?php echo $anoContrato_correios;?>" name="correios[anoContrato]" >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='row-fluid'>
                            <div class='span12'>
                                <div class='control-group'>
                                    <label class='control-label'>Serviços</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <select class="span12" name="correios[servicos_usados][]" multiple="" >
                                                <?php foreach (\PhpSigep\Model\ServicoDePostagem::getAll() as $key => $rows) {?>
                                                <option value="<?php echo $rows->getCodigo();?>" <?php echo (in_array($rows->getCodigo(), $servicos_usados_correios)) ? "selected" : "";?>><?php echo $rows->getCodigo();?> - <?php echo utf8_decode($rows->getNome());?></option>
                                                <?php }?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <!-- FIM CONFIGURAÇÃO DO CORREIOS -->
                </div>
               
            </div>
        </div>
        <p><br/>
                <button class='btn btn-primary' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
                <input type='hidden' id="btn_click" name='btn_acao' value='' />
                <a href="loja_configuracao_forma_envio.php" class="btn">Listagem</a>
            </p><br/>
    </form> <br />
    <table class="table table-bordered table-striped table-hover table-fixed">
        <thead>
            <tr class='titulo_coluna' >
                <th>FORMA DE ENVIO</th>
                <th class="tal">LOJA (FÁBRICA)</th>
                <th>AÇÕES</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($objLoja->getAllConfigLoja() as $key => $configLojaPagamento) {
                $formas = json_decode($configLojaPagamento["pa_forma_envio"], 1);
                $dadosLoja = $objLoja->getByLoja($configLojaPagamento["loja_b2b"]);
            ?>
            <tr>
                <td class="tac">
                    <?php 
                        foreach ($formas["meio"] as $kforma => $forma) {
                            if ($forma["status"] <> "1") {
                                continue;
                            }
                            echo "<img width='100' src='../loja/layout/img/envios/{$kforma}.png' />";
                        }
                    ?>
                </td>
                <td class="tal"><?php echo $dadosLoja["fabrica_nome"];?></td>
                <td class="tac">
                    <a href="loja_configuracao_forma_envio.php?editar_formas=true&xloja_b2b=<?php echo $configLojaPagamento["loja_b2b"];?>"  class="btn btn-warning btn-mini"><i class="icon-edit icon-white"></i> Editar</a>
                </td>
            </tr>
            <?php }?>
        </tbody>
    </table>
<?php  include "rodape.php"; ?>