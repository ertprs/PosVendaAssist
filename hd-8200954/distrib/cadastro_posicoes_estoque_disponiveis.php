<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$title = "Conferência de Recebimento";

$msg_erro = array();

if(isset($_GET["deletar"])){

    $posto_estoque_posicao = $_GET["posto_estoque_posicao"];

    $sql = "DELETE FROM tbl_posto_estoque_posicao WHERE posto_estoque_posicao = {$posto_estoque_posicao}";
    $res = pg_query($con, $sql);

    header("Location: cadastro_posicoes_estoque_disponiveis.php?listar=sim");
    exit;

}

if(isset($_GET["editar"])){

    $posto_estoque_posicao = $_GET["posto_estoque_posicao"];

    $sql = "SELECT * FROM tbl_posto_estoque_posicao WHERE posto_estoque_posicao = {$posto_estoque_posicao}";
    $res = pg_query($con, $sql);

    if(pg_num_rows($res) > 0){

        $deposito     = pg_fetch_result($res, 0, "deposito");
        $posicao      = pg_fetch_result($res, 0, "posicao");
        $largura      = pg_fetch_result($res, 0, "largura");
        $altura       = pg_fetch_result($res, 0, "altura");
        $profundidade = pg_fetch_result($res, 0, "profundidade");

    }

}

if(isset($_POST["cadastrar"])){

    $posto_estoque_posicao = $_POST["posto_estoque_posicao"];
    $largura               = $_POST["largura"];
    $altura                = $_POST["altura"];
    $profundidade          = $_POST["profundidade"];
    $deposito              = $_POST["deposito"];
    $posicao               = $_POST["posicao"];

    if(!is_numeric($deposito)){
        $msg_erro[] = "Por favor, insira apenas números no campo depósito!";
    }

    if(!is_numeric($altura)){
        $msg_erro[] = "Por favor, insira apenas números no campo altura!";
    }

    if(!is_numeric($largura)){
        $msg_erro[] = "Por favor, insira apenas números no campo largura!";
    }

    if(!is_numeric($profundidade)){
        $msg_erro[] = "Por favor, insira apenas números no campo profundidade!";
    }

    if(strlen($deposito) > 0 && strlen($posicao) > 0 && strlen($posto_estoque_posicao) == 0){

        $sql = "SELECT posto_estoque_posicao FROM tbl_posto_estoque_posicao WHERE posto = 4311 AND deposito = {$deposito} AND posicao = '{$posicao}' ";
        $res = pg_query($con, $sql);

        if(pg_num_rows($res) > 0){
            $msg_erro[] = "Já existe um cadastro para esse Depósito e Posição";
        }

    }

    if(count($msg_erro) == 0){

        if(strlen($posto_estoque_posicao) == 0){

            $sql = "INSERT INTO tbl_posto_estoque_posicao
                    (
                        posto,
                        deposito,
                        posicao,
                        largura,
                        altura,
                        profundidade
                    ) 
                    VALUES 
                    (
                        4311,
                        {$deposito},
                        '{$posicao}',
                        {$largura},
                        {$altura},
                        {$profundidade}
                    )";

            $msg_sucesso = 1;

        }else{

            $sql = "UPDATE tbl_posto_estoque_posicao SET 
                        deposito = {$deposito},
                        posicao = '{$posicao}',
                        largura = {$largura},
                        altura  = {$altura},
                        profundidade = {$profundidade} 
                    WHERE 
                        posto_estoque_posicao = {$posto_estoque_posicao}
                    ";

            $msg_sucesso = 2;

        }

        $res = pg_query($con, $sql);

        header("Location: cadastro_posicoes_estoque_disponiveis.php?msg={$msg_sucesso}");
        exit;

    }

}

include '../funcoes.php';

include "menu.php";

?>

<link href="../bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" />
<link href="../bootstrap/css/extra.css" type="text/css" rel="stylesheet" />
<link href="../css/tc_css.css" type="text/css" rel="stylesheet" />
<link href="../plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" >
<link href="../bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" />
<link href='../plugins/shadowbox_lupa/shadowbox.css' type='text/css' rel='stylesheet' />


<script src="../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="../plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script src="../bootstrap/js/bootstrap.js"></script>
<script src='../plugins/jquery.alphanumeric.js'></script>
<script src='../plugins/jquery.maskedinput_new.js'></script>
<script src='../plugins/jquery.form.js'></script>
<script src='../plugins/FancyZoom/FancyZoom.js'></script>
<script src='../plugins/FancyZoom/FancyZoomHTML.js'></script>
<script src='../plugins/price_format/jquery.price_format.1.7.min.js'></script>
<script src='../plugins/price_format/config.js'></script>
<script src='../plugins/price_format/accounting.js'></script>
<script src='../plugins/shadowbox_lupa/shadowbox.js'></script>
<script src='../js/jquery.numeric.js'></script>

<script type="text/javascript">
    
    $(function(){

        $("#deposito").numeric();
        $("#largura").numeric();
        $("#altura").numeric();
        $("#profundidade").numeric();

    });

    function deletar_item(item = ""){

        var r = confirm("Deseja realmente excluir esse registro");

        if(r == true){

            if(item != ""){

                location.href = "cadastro_posicoes_estoque_disponiveis.php?deletar=sim&posto_estoque_posicao="+item;

            }

        }

    }

</script>

<style>
    .container{
        width: 60% !important;
        margin: 0 auto;
    }
    .btn, .btn:hover{
        font-size: 14px !important;
        font-weight: normal;
    }
</style>

<div class="container">

    <?php

    if(count($msg_erro) > 0){

        echo " <br /> <div class='alert alert-danger'><h4>".implode("<br />", $msg_erro)."</h4></div>";

    }

    if(strlen($_GET["msg"]) > 0){

        echo " <br /> <div class='alert alert-success'> <h4> ".($_GET["msg"] == 1 ? "Cadastro realizado com Sucesso!" : "Alteração realizada com Sucesso!")." </h4> </div> ";

    }

    ?>

    <div class="row">
        <strong class="obrigatorio pull-right">  * Campos obrigatórios </strong>
    </div>

    <form method="post" class="form-search form-inline tc_formulario">

        <input type="hidden" name="cadastrar" value="sim">
        <input type="hidden" name="posto_estoque_posicao" value="<?php echo $posto_estoque_posicao; ?>">
        
        <div class='titulo_tabela'> Cadastro de posições de estoque disponíveis </div>

        <br />

        <div class='tc_container'>

            <div class="row-fluid">

                <div class="span2"></div>

                <div class="span4">
                    <div class="control-group" >
                        <label class="control-label" for="deposito" >Depósito</label>
                        <div class="controls controls-row" >
                            <h5 class='asteristico'>*</h5>
                            <input type="text" name="deposito" id="deposito" class="span5" value="<?=$deposito?>" maxlength="5" />
                        </div>
                    </div>
                </div>

                <div class="span4">
                    <div class="control-group" >
                        <label class="control-label" for="posicao" >Posição</label>
                        <div class="controls controls-row" >
                            <h5 class='asteristico'>*</h5>
                            <input type="text" name="posicao" id="posicao" class="span5" value="<?=$posicao?>" maxlength="10" />
                        </div>
                    </div>
                </div>

            </div>

            <div class="row-fluid">

                <div class="span2"></div>

                <div class="span3">
                    <div class="control-group" >
                        <label class="control-label" for="largura" >Largura (cm)</label>
                        <div class="controls controls-row" >
                            <h5 class='asteristico'>*</h5>
                            <input type="text" name="largura" id="largura" class="span8" value="<?=$largura?>" maxlength="5" />
                        </div>
                    </div>
                </div>

                <div class="span3">
                    <div class="control-group" >
                        <label class="control-label" for="altura" >Altura (cm)</label>
                        <div class="controls controls-row" >
                            <h5 class='asteristico'>*</h5>
                            <input type="text" name="altura" id="altura" class="span8" value="<?=$altura?>" maxlength="5" />
                        </div>
                    </div>
                </div>

                <div class="span3">
                    <div class="control-group" >
                        <label class="control-label" for="profundidade" >Profundidade (cm)</label>
                        <div class="controls controls-row" >
                            <h5 class='asteristico'>*</h5>
                            <input type="text" name="profundidade" id="profundidade" class="span8" value="<?=$profundidade?>" maxlength="5" />
                        </div>
                    </div>
                </div>

            </div>

            <br />

            <div class="row-fluid">
                <div class="span12">
                    <div class="control-group">
                        <div class="controls controls-row tac">
                            <input type="submit" class="btn btn-primary" value="Cadastrar"/>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </form>

    <?php

    if(strlen($_GET["listar"]) == 0){

        echo "
            <div class='tac'>
                <a href='cadastro_posicoes_estoque_disponiveis.php?listar=sim' class='btn btn-primary'> Listar Todos </a>
            </div>
        ";

    }else{

        $sql = "SELECT * FROM tbl_posto_estoque_posicao WHERE posto = 4311 ORDER BY posto_estoque_posicao";
        $res = pg_query($con, $sql);

        $cont = pg_num_rows($res);

        if($cont > 0){

            echo "
                <table class='table table-striped table-bordered table-large'>
                    <thead>
                        <tr class='titulo_coluna'>
                            <th>Depósito</th>
                            <th>Posição</th>
                            <th>Largura</th>
                            <th>Altura</th>
                            <th>Profundidade</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                ";

            for ($i = 0; $i < $cont; $i++) {

                $id           = pg_fetch_result($res, $i, "posto_estoque_posicao");
                $deposito     = pg_fetch_result($res, $i, "deposito");
                $posicao      = pg_fetch_result($res, $i, "posicao");
                $largura      = pg_fetch_result($res, $i, "largura");
                $altura       = pg_fetch_result($res, $i, "altura");
                $profundidade = pg_fetch_result($res, $i, "profundidade");

                echo "
                    <tr>
                        <td>{$deposito}</td>
                        <td>{$posicao}</td>
                        <td>{$largura}</td>
                        <td>{$altura}</td>
                        <td>{$profundidade}</td>
                        <td class='tac'>
                            <a href='cadastro_posicoes_estoque_disponiveis.php?editar=sim&posto_estoque_posicao={$id}' class='btn btn-primary'> Alterar </a> 
                            <a href='javascript: deletar_item({$id});' class='btn btn-danger'> Excluir </a> 
                        </td>
                    </tr>
                ";

            }

            echo "</tbody></table>";

        }else{

            echo "<div class='alert alert-warning'> <h4> Nenhum registro encontrado! </h4> </div>";

        }

    }

    ?>

</div>