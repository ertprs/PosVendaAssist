<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
 $admin_privilegios="cadastros";
include 'autentica_admin.php';
include_once '../helpdesk/mlg_funciones.php';
 $layout_menu = "cadastro";
$title = "CADASTRO DE TABELA GARANTIA";
 include 'cabecalho_new.php';
 $plugins = array(
    "autocomplete",
    "shadowbox",
    "mask",
    "dataTable",
    "alphanumeric"
);
 include("plugin_loader.php");
?>
<head>
	<style type="text/css">
		.text-center {
			text-align:center;
		}
	</style>
</head>
<form name="frm_revenda" method="POST" class="form-search form-inline tc_formulario">
    <div class='titulo_tabela '><?=$title?></div>
    <div class="offset1 span9 text-info">
        
    </div>
    <input type="hidden" name="cliente_admin" value="<? echo $cliente_admin ?>">
    <p>&nbsp;</p>
    <!--<fieldset>
        <legend class="titulo_tabela">Informações cadastrais</legend> -->
    <div class="row-fluid">
        <div class="offset2 span8">
            <div class="control-group">
                <label for="defeito">Defeito</label>
                <div class="input-append span12">
                    <select class="span12">
                    	<option>Selecione...</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="span1"></div>
    </div>
    <div class="row-fluid">
        <div class="offset2 span2">
            <div class="control-group">
                <label for="defeito">Ano Fabricação</label>
                <div class="input-append">
                    <input type="text" class="span12">
                </div>
            </div>
        </div>
         <div class="offset1 span2">
            <div class="control-group">
                <label for="defeito">Mão de Obra(meses)</label>
                <div class="input-append">
                    <input type="text" class="span12">
                </div>
            </div>
        </div>
         <div class="offset1 span2">
            <div class="control-group">
                <label for="defeito">Peça (meses)</label>
                <div class="input-append">
                    <input type="text" class="span12">
                </div>
            </div>
        </div>
        <div class="span1"></div>
    </div>
    <div class="row-fluid">
        <div class="span12 text-center">
            <br />
            <button id="btn_gravar"  class="btn btn-primary" type="submit" name="btn_acao" value="gravar">Gravar</button>
            <span class="inptc5">&nbsp;</span>
            <button id="btn_excluir" class="btn btn-danger"  type="submit" name="btn_acao" value="excluir">Excluir</button>
            <span class="inptc5">&nbsp;</span>
            <a href="<?=$_SERVER['PHP_SELF']?>" id="btn_reset" class="btn btn-warning" name="btn_reset" value="limpar">Limpar</a>
            <p>&nbsp;<p>
        </div>
    </div>
</form>
<?php if (!isset($_GET['listar'])): ?>
    <div class="row-fluid">
        <div class="span12 text-center">
            <br />
            <a href='<?=$_SERVER['PHP_SELF']?>?listar=todos' class="btn btn-info">Clique aqui para listar todos os registros já Cadastrados</a>
            <p>&nbsp;<p>
        </div>
        <div class="span3"></div>
    </div>
<?php endif; ?>
<?php include "rodape.php"; ?>