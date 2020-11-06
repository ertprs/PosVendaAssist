<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
include_once "../class/tdocs.class.php";
use Lojavirtual\Fornecedor;

$array_estados = $array_estados();

$objfornecedor   = new Fornecedor();

$dadosfornecedor = $objfornecedor->get();

$b2b_fornecedor = $_REQUEST['b2b_fornecedor'];

$url_redir = "<meta http-equiv=refresh content=\"2;URL=loja_fornecedor.php\">";

function retira_especiais($texto){
    return str_replace("-", "" ,str_replace(array(".", ",", "(",")"," ","/"), "", $texto));
}

if ($_GET["acao"] == "delete" && !empty($b2b_fornecedor)) {

    if (empty($b2b_fornecedor)) {
        $msg_erro["msg"][]    = "fornecedor não encontrado!";
    }

    if (count($msg_erro) == 0) {

        $res = pg_query($con,"BEGIN");

        if (!empty($objfornecedor)) {
            $retorno = $objfornecedor->delete($b2b_fornecedor);
            if ($retorno["erro"]) {
                $msg_erro["msg"][] = $retorno["msn"];
            } else {
                $msg_sucesso["msg"][] = 'fornecedor removida com sucesso!';
            }
        }

        if (count($msg_erro) > 0) {
            $res = pg_query($con,"ROLLBACK");
            echo $url_redir;
        } else {
            $res = pg_query($con,"COMMIT");
            echo $url_redir;
        }
    }
}

if ($_GET["acao"] == "edit" && !empty($b2b_fornecedor)) {

    $dataCat     = $objfornecedor->get($b2b_fornecedor);

    $cnpj        = $dataCat['cnpj'];
    $nome        = $dataCat['nome'];
    $email       = $dataCat['email'];
    $celular     = $dataCat['celular'];
    $telefone    = $dataCat['fone'];
    $cep         = $dataCat['cep'];
    $endereco    = $dataCat['endereco'];
    $numero      = $dataCat['numero'];
    $bairro      = $dataCat['bairro'];
    $estado      = $dataCat['estado'];
    $cidade      = $dataCat['cidade'];
    $ativo       = $dataCat['ativo'];

    $tipo_acao   = "edit";
}

if ($_POST["btn_acao"] == "submit" && $tipo_acao  != "edit") {

    $cnpj        = retira_especiais($_POST['cnpj']);
    $nome        = $_POST['nome'];
    $email       = $_POST['email'];
    $celular     = $_POST['celular'];
    $telefone    = $_POST['telefone'];
    $cep         = retira_especiais($_POST['cep']);
    $endereco    = $_POST['endereco'];
    $numero      = $_POST['numero'];
    $bairro      = $_POST['bairro'];
    $estado      = $_POST['estado'];
    $cidade      = $_POST['cidade'];
    $ativo       = ($_POST['ativo'] == 't') ? $_POST['ativo'] : 'f'; 

    if (empty($email)) {
        $msg_erro["msg"][]    = "Preencha o e-email";
        $msg_erro["campos"][] = "email";
    }

    if (empty($cep)) {
        $msg_erro["msg"][]    = "Preencha o CEP";
        $msg_erro["campos"][] = "cep";
    }

    if (empty($cnpj)) {
        $msg_erro["msg"][]    = "Preencha o CNPJ do fornecedor";
        $msg_erro["campos"][] = "cnpj";
    }

    if (empty($nome)) {
        $msg_erro["msg"][]    = "Preencha o nome do fornecedor";
        $msg_erro["campos"][] = "nome";
    }

	$retorno = $objfornecedor->getFornecedor($cnpj);

	if($retorno) {
	    $msg_erro["msg"][]    = "CNPJ já cadastrado no sistema";
        $msg_erro["campos"][] = "cnpj";
	}
    if (count($msg_erro) == 0) {

        $res = pg_query($con,"BEGIN");
        $dataSave = array(
                        "cnpj" => $cnpj,
                        "nome" => $nome,
                        "email" => $email,
                        "celular" => $celular,
                        "telefone" => $telefone,
                        "cep" => $cep,
                        "endereco" => $endereco,
                        "numero" => $numero,
                        "bairro" => $bairro,
                        "estado" => $estado,
                        "cidade" => $cidade,
                        "ativo" => $ativo
                     );

        if (!empty($objfornecedor)) {
            $retorno = $objfornecedor->save($dataSave);
            if ($retorno["erro"]) {
                $msg_erro["msg"][] = $retorno["msn"];
            } else {
                $msg_sucesso["msg"][] = 'fornecedor cadastrado com sucesso!';
            }
        }

        if (count($msg_erro) > 0) {
            $res = pg_query($con,"ROLLBACK");
        } else {
            $res = pg_query($con,"COMMIT");
            echo $url_redir;
        }
    }
}

if ($_POST["btn_acao"] == "submit" && $tipo_acao  == "edit") {

    $cnpj        = retira_especiais($_POST['cnpj']);
    $nome        = $_POST['nome'];
    $email       = $_POST['email'];
    $celular     = $_POST['celular'];
    $telefone    = $_POST['telefone'];
    $cep         = retira_especiais($_POST['cep']);
    $endereco    = $_POST['endereco'];
    $numero      = $_POST['numero'];
    $bairro      = $_POST['bairro'];
    $estado      = $_POST['estado'];
    $cidade      = $_POST['cidade'];
    $ativo       = ($_POST['ativo'] == 't') ? $_POST['ativo'] : 'f';

    if (empty($email)) {
        $msg_erro["msg"][]    = "Preencha o e-email";
        $msg_erro["campos"][] = "email";
    }

    if (empty($cep)) {
        $msg_erro["msg"][]    = "Preencha o CEP";
        $msg_erro["campos"][] = "cep";
    }

    if (empty($cnpj)) {
        $msg_erro["msg"][]    = "Preencha o CNPJ do fornecedor";
        $msg_erro["campos"][] = "cnpj";
    }

    if (empty($nome)) {
        $msg_erro["msg"][]    = "Preencha o nome do fornecedor";
        $msg_erro["campos"][] = "nome";
    }

    if (count($msg_erro) == 0) {

        $res = pg_query($con,"BEGIN");
        $dataSave = array(
                        "cnpj" => $cnpj,
                        "nome" => $nome,
                        "email" => $email,
                        "celular" => $celular,
                        "telefone" => $telefone,
                        "cep" => $cep,
                        "endereco" => $endereco,
                        "numero" => $numero,
                        "bairro" => $bairro,
                        "estado" => $estado,
                        "cidade" => $cidade,
                        "ativo" => $ativo,
                        "b2b_fornecedor" => $b2b_fornecedor
                     );

        if (!empty($objfornecedor)) {
            $retorno = $objfornecedor->update($dataSave);

            if ($retorno["erro"]) {
                $msg_erro["msg"][] = $retorno["msn"];
            } else {
                $msg_sucesso["msg"][] = 'fornecedor atualizada com sucesso!';
            }
        }

        if (count($msg_erro) > 0) {
            $res = pg_query($con,"ROLLBACK");
            $fornecedor   = $_POST['fornecedor'];
            $descricao   = $_POST['descricao'];
        } else {
            $res = pg_query($con,"COMMIT");
            echo $url_redir;
        }
    }
}

$layout_menu = "cadastro";
$title = "fornecedor de Produtos - Loja Virtual";
include 'cabecalho_new.php';

$plugins = array(
    "shadowbox",
    "dataTable",
    "multiselect",
    "datepicker",
    "mask"
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
</style>
<script language="javascript">
    var hora = new Date();
    var engana = hora.getTime();

    $(function() {
        Shadowbox.init();
        $.dataTableLoad("#tabela");
        $(".multiple").multiselect({
           selectedText: "# of # selected"
        });

        $("#cnpj").mask("99.999.999/9999-99");
        $("#celular").mask("(99) 99999-9999");
        $("#telefone").mask("(99) 9999-9999");

        $("#cep").blur(function() {
            busca_cep($("#cep").val());
        });

    });

    function busca_cep(cep, method = 'webservice') {
        if (cep.length > 0) {

            $.ajax({
                async: true,
                timeout: 30000,
                url: "ajax_cep.php",
                type: "get",
                data: { method: method, cep: cep }
            }).fail(function(r) {
                if (method == "webservice") {
                    busca_cep(cep, "database");
                } else {
                    alert("Erro ao consultar CEP, tempo limite esgotado");
                }
            }).done(function(r) {
                data = r.split(";");

                if (data[0] != "ok" && method == "webservice") {
                    busca_cep(cep, familia, "database");
                } else if (data[0] != "ok") {
                    if (data[0].length > 0) {
                        alert(data[0]);
                    } else {
                        alert("Erro ao buscar CEP");
                    }
                } else {
                    var estado, cidade, end, bairro;

                    if (data[4] != undefined) estado = data[4];
                    if (data[3] != undefined) cidade = data[3];
                    if (data[1] != undefined && data[1].length > 0) end = data[1];
                    if (data[2] != undefined && data[2].length > 0) bairro = data[2];

                    $("#endereco").val(end);
                    $("#bairro").val(bairro);
                    $("#cidade").val(cidade);

                    var option = $("#estado").find("option[value="+estado+"]");
                    
                    $(option).prop("selected", true);
                }

            });
        } 
    }
</script>
    <?php
        if (empty($objfornecedor->_loja)) {
            exit('<div class="alert alert-error"><h4>Loja não encontrada.</h4></div>');
        }
    ?>
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
        <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
    </div>

    <form name='frm_relatorio' METHOD='POST' enctype="multipart/form-data" ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
        <?php if ($tipo_acao == "edit") {?>
        <input type="hidden" name="tipo_acao" value="edit">
        <input type="hidden" name="b2b_fornecedor" value="<?= $b2b_fornecedor ?>">
        <?php } else {?> 
        <input type="hidden" name="tipo_acao" value="add">
        <?php }?> 
        <div class='titulo_tabela '>Cadastro</div>
        <br/>

        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("cnpj", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>CNPJ</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" class="span8" value="<?php echo $cnpj;?>" name="cnpj" id="cnpj">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group <?=(in_array("nome", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Nome do Fornecedor</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" class="span12" value="<?php echo $nome;?>" name="nome" id="nome">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("email", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>E-mail</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" class="span10" value="<?php echo $email;?>" name="email" id="email">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group <?=(in_array("celular", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Celular</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type="text" class="span9" value="<?php echo $celular;?>" name="celular" id="celular">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("telefone", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Telefone</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type="text" class="span9" value="<?php echo $telefone;?>" name="telefone" id="telefone">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group <?=(in_array("cep", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>CEP</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" class="span9" value="<?php echo $cep;?>" name="cep" id="cep">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("endereco", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Endereço</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type="text" class="span12" value="<?php echo $endereco;?>" name="endereco" id="endereco">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group <?=(in_array("numero", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Nº</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type="text" class="span6" value="<?php echo $numero;?>" name="numero" id="numero">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("bairro", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Bairro</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type="text" class="span12" value="<?php echo $bairro;?>" name="bairro" id="bairro">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group <?=(in_array("estado", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Estado</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <select id="estado" name="estado" class="span7" >
                                    <option value="" >Selecione</option>
                                    <?php
                                    #O $array_estados está no arquivo funcoes.php
                                    foreach ($array_estados as $sigla => $nome_estado) {
                                        $selected = ($sigla == $estado) ? "selected" : "";

                                        echo "<option value='{$sigla}' {$selected} >" . $nome_estado . "</option>";
                                    }
                                    ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("cidade", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Cidade</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type="text" class="span10" value="<?php echo $cidade ?>" name="cidade" id="cidade">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group <?=(in_array("cidade", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'></label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <label>
                                <input type="checkbox" value="t" name="ativo" id="ativo" <?= ($ativo == 't') ? 'checked' : '' ?> />
                                Ativo
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <p><br/>
                <button class='btn' id="btn_acao">
                    Gravar
                </button>
                <input type='hidden' id="btn_click" name='btn_acao' value='submit' />
            </p><br/>
    </form> <br />
    <?php
        if ($dadosfornecedor["erro"]) {
            echo '<div class="alert alert-error"><h4>'.$dadosfornecedor["msn"].'</h4></div>';
        } else {
    ?>
    <table class='table table-striped table-bordered table-hover table-fixed' id='tabela'>
        <thead>
            <tr class='titulo_coluna' >
                <th align="left">CNPJ</th>
                <th class='tal'>Nome</th>
                <th>E-mail</th>
                <th>Celular</th>
                <th>CEP</th>
                <th>Ativo</th>
            </tr>
        </thead>
        <tbody>
        <?php 
        foreach ($dadosfornecedor as $kfornecedor => $rowsfornecedor) {
        ?>
        <tr>
            <td class='tac'><a href="<?= $_SERVER['PHP_SELF'] ?>?b2b_fornecedor=<?= $rowsfornecedor['loja_b2b_fornecedor'] ?>&acao=edit"><?php echo  $rowsfornecedor["cnpj"];?></td>
            <td class='tal'><?php echo  $rowsfornecedor["nome"];?></td>
            <td class='tal'><?php echo  $rowsfornecedor["email"];?></td>
            <td class='tal'><?php echo  $rowsfornecedor["celular"];?></td>
            <td class='tal'><?php echo  $rowsfornecedor["cep"];?></td>
            <td class='tac'>
                <?php echo ($rowsfornecedor["ativo"] == 't') ? '<span class="label label-success">Ativo</span>' : '<span class="label label-important">Inativo</span>';?>
            </td>
        </tr>
        <?php }?>
        </tbody>
    </table>
    <?php }?>
</div> 
<?php include 'rodape.php';?>
