<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
include_once "../class/tdocs.class.php";
use Lojavirtual\Banner;

$objBanner   = new Banner();
$tDocs       = new TDocs($con, $login_fabrica);

$dadosBanner = $objBanner->get();

$url_redir = "<meta http-equiv=refresh content=\"0;URL=loja_banner.php\">";

if ($_GET["acao"] == "delete" && $_GET["loja_b2b_banner"] > 0) {
    
    $loja_b2b_banner = $_GET['loja_b2b_banner'];

    if (empty($loja_b2b_banner)) {
        $msg_erro["msg"][]    = "Banner não encontrado";
    }

    if (count($msg_erro) == 0) {

        $res = pg_query($con,"BEGIN");

        if (!empty($objBanner)) {
            $retorno = $objBanner->delete($loja_b2b_banner);
            if ($retorno["erro"]) {
                $msg_erro["msg"][] = $retorno["msn"];
            } else {
                $msg_sucesso["msg"][] = 'Banner removido com sucesso!';
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

if ($_GET["acao"] == "edit" && $_GET["loja_b2b_banner"] > 0) {
    
    $loja_b2b_banner = $_GET['loja_b2b_banner'];
    $dataBanner  = $objBanner->get($loja_b2b_banner);
    $categoria   = 0;
    $link        = $dataBanner['link'];
    $descricao   = $dataBanner['descricao'];
    $ativo       = $dataBanner['ativo'];
    $tipo_acao   = "edit";

}

if ($_POST["btn_acao"] == "submit" && $tipo_acao  != "edit") {

    $categoria   = 0;
    $descricao   = $_POST['descricao'];
    $link        = $_POST['link'];
    $ativo       = empty($_POST['ativo']) ? 'f' : $_POST['ativo'];
    $banner      = $_FILES["banner"];

    if (empty($descricao) || $banner["error"]) {
        $msg_erro["msg"][]    = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "descricao";
        $msg_erro["campos"][] = "banner";
    }

    $ext = strtolower(preg_replace("/.+\./", "", $banner["name"]));
    if (!in_array($ext, array("gif", "png", "jpg", "jpeg"))) {
        $msg_erro["msg"][] = "Tipo de Imagem não permitido";
    }

    if (count($msg_erro) == 0) {

        $res = pg_query($con,"BEGIN");
        $dataSave = array(
                        "categoria" => $categoria,
                        "link"      => $link,
                        "ativo"     => $ativo,
                        "descricao" => $descricao
                     );

        if (!empty($objBanner)) {
            $retorno = $objBanner->save($dataSave);

            if ($retorno["erro"]) {
                $msg_erro["msg"][] = $retorno["msn"];
            } else {

                if (!empty($banner)) {
                    $tDocs->setContext("loja", "banner");
                    $anexoID = $tDocs->uploadFileS3($banner, $retorno["loja_b2b_banner"], true, "", "");
                    if (!$anexoID) {
                        $msg_erro["msg"][] = 'Erro ao fazer upload do banner!';
                    } else {
                        $msg_sucesso["msg"][] = 'Banner cadastrado com sucesso!';
                    }
                }
            }
        }

        if (count($msg_erro) > 0) {
            $res = pg_query($con,"ROLLBACK");
            $categoria   = $_POST['categoria'];
            $link        = $_POST['link'];
            $descricao   = $_POST['descricao'];
            $ativo       = $_POST['ativo'];
        } else {
            $res = pg_query($con,"COMMIT");
            echo $url_redir;
        }
    }
}

if ($_POST["btn_acao"] == "submit" && $tipo_acao  == "edit") {

    $categoria   = 0;
    $descricao   = $_POST['descricao'];
    $link        = $_POST['link'];
    $ativo       = empty($_POST['ativo']) ? 'f' : $_POST['ativo'];
    $loja_b2b_banner = $_POST['loja_b2b_banner'];
    $banner      = $_FILES["banner"];


    if (empty($descricao)) {
        $msg_erro["msg"][]    = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "descricao";
        $msg_erro["campos"][] = "banner";
    }

    if (count($msg_erro) == 0) {

        $res = pg_query($con,"BEGIN");
        $dataSave = array(
                        "loja_b2b_banner"   => $loja_b2b_banner,
                        "categoria"     => $categoria,
                        "link"          => $link,
                        "ativo"         => $ativo,
                        "descricao"     => $descricao
                     );

        if (!empty($objBanner)) {
            $retorno = $objBanner->update($dataSave);

            if ($retorno["erro"]) {
                $msg_erro["msg"][] = $retorno["msn"];
            } else {
                
                if ($banner["size"] > 0) {
                    $tDocs->setContext("loja", "banner");
                    $anexoID = $tDocs->uploadFileS3($banner, $retorno["loja_b2b_banner"], true, "", "");
                    if (!$anexoID) {
                        $msg_erro["msg"][] = 'Erro ao fazer upload do banner!';
                    } else {
                        $msg_sucesso["msg"][] = 'Banner atualizado com sucesso!';
                    }
                } 
            }
        }

        if (count($msg_erro) > 0) {
            $res = pg_query($con,"ROLLBACK");
            $categoria   = 0;
            $descricao   = $_POST['descricao'];
            $link        = $_POST['link'];
            $ativo       = $_POST['ativo'];
        } else {
            $res = pg_query($con,"COMMIT");
            echo $url_redir;
        }
    }
}

$layout_menu = "cadastro";
$title = "Banners - Loja Virtual";
include 'cabecalho_new.php';

$plugins = array(
    "shadowbox",
    "dataTable",
    "multiselect"
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

    });
</script>
    <?php
        if (empty($objBanner->_loja)) {
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
        <input type="hidden" name="loja_b2b_banner" value="<?php echo $loja_b2b_banner;?>">
        <?php } else {?> 
        <input type="hidden" name="tipo_acao" value="add">
        <?php }?> 
        <div class='titulo_tabela '>Cadastro</div>
        <br/>

        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span8'>
                <div class='control-group <?=(in_array("descricao", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Titulo do banner</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" class="span12" value="<?php echo $descricao;?>" name="descricao" id="descricao">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span8'>
                <div class='control-group'>
                    <label class='control-label'>Link do banner</label>
                    <div class='controls controls-row'>
                    <div class="input-prepend">
                      <span class="add-on">https://posvenda.telecontrol.com.br/assist/</span>
                       <input type="text" class="span7" value="<?php echo $link;?>" name="link" id="link">
                    </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <div class="alert"><b>Recomendamos utilizar o tamanho do banner:  1500px de largura   por 400px altura</b></div>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span5'>
                <div class='control-group <?=(in_array("banner", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Banner</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <input type="file" name="banner" id="banner"><br />
                        </div>
                    </div>
                </div>
            </div>
            <div class='span3'>
                <div class='control-group'>
                    <label class='control-label' for='ativo'>Ativo</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type="checkbox" name="ativo" <?php if ($ativo == 't' ) {echo 'checked';}?> id="ativo" value="t"> Sim
                            <div><strong></strong></div>
                        </div>  
                    </div>
                </div>
            </div>

            <div class='span2'></div>
        </div>

        <p><br/>
                <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
                <input type='hidden' id="btn_click" name='btn_acao' value='' />
            </p><br/>
    </form> <br />
    <?php
        if ($dadosBanner["erro"]) {
            echo '<div class="alert alert-error"><h4>'.$dadosBanner["msn"].'</h4></div>';
        } else {
    ?>
    <table class='table table-striped table-bordered table-hover table-fixed' id='tabela'>
        <thead>
            <tr class='titulo_coluna' >
                <th align="left">Titulo</th>
                <th align="left">Banner</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php 
        foreach ($dadosBanner as $kbanner => $rowsBanner) {
            $xbanner = $tDocs->getDocumentsByRef($rowsBanner["loja_b2b_banner"],"loja","banner");
        ?>
        <tr>
            <td class='tal'><?php echo $rowsBanner["descricao"];?></td>
            <td class='tac'>
            <?php if (!empty($xbanner->url)) {?>
                <a href="<?php echo $xbanner->url;?>" target="_blank" class="btn btn-mini"><i class="icon-search"></i> Visualizar</a>
            <?php }?>

            </td>
            <td class='tac'>
                <?php echo ($rowsBanner["ativo"] == 't') ? '<span class="label label-success">Ativo</span>' : '<span class="label label-important">Inativo</span>';?>
            </td>
            <td class='tac'>
                <a href="loja_banner.php?acao=edit&loja_b2b_banner=<?php echo $rowsBanner["loja_b2b_banner"];?>" class="btn btn-info btn-mini" title="Editar"><i class="icon-edit icon-white"></i></a>
                <a onclick="if (confirm('Deseja remover este registro?')) window.location='loja_banner.php?acao=delete&loja_b2b_banner=<?php echo $rowsBanner["loja_b2b_banner"];?>';return false;" href="#" class="btn btn-danger btn-mini" title="Remover"><i class="icon-remove icon-white"></i></a>
            </td>
        </tr>
        <?php }?>
        </tbody>
    </table>
    <?php }?>
</div> 
<?php include 'rodape.php';?>
