<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';


$admin_privilegios="cadastros";
include 'autentica_admin.php';

include 'funcoes.php';

$layout_menu = "cadastro";
$title = "CADASTRO DE SOLUÇÃO X MARCA";


if(isset($_POST['ativar'])){
    $diagnostico = $_POST["diagnostico"];

    $sql = "UPDATE tbl_diagnostico SET ativo = true where diagnostico = $diagnostico and fabrica = $login_fabrica";
    $res = pg_query($con, $sql);
    if(strlen(pg_last_error($con))>0){  
        $retorno = array("erro" => 'Falha ao ativar diagnostico.');
    }else{
        $retorno = array("sucesso" => 'Diagnostico ativado com sucesso.');
    }
exit(json_encode($retorno));
}

if(isset($_POST['inativar'])){
    $diagnostico = $_POST["diagnostico"];

    $sql = "UPDATE tbl_diagnostico SET ativo = false where diagnostico = $diagnostico and fabrica = $login_fabrica";
    $res = pg_query($con, $sql);
    if(strlen(pg_last_error($con))>0){  
        $retorno = array("erro" => 'Falha ao inativar diagnostico.');
    }else{
        $retorno = array("sucesso" => 'Diagnostico inativado com sucesso.');
    }

exit(json_encode($retorno));
}


include "cabecalho_new.php";

$plugins = array( 
                "price_format",
                "tooltip",
                "multiselect",
                "mask"
             );

include ("plugin_loader.php");

if (strlen($_POST["btn_acao"]) > 0) {
    $btnacao = trim($_POST["btn_acao"]);
}


if ($btnacao == "gravar") {    
    $solucao = $_POST["solucao"];
    $marca = $_POST["marca"];
    $ativo = ($_POST["ativo"] == 't') ? "'t'" : "'f'" ;

    if(strlen(trim($solucao))==0){
        $msg_erro .= "Informe a solução. ";
    }

    if( count($marca) == 0 ){
        $msg_erro .= "Informe uma marca. ";
    }

    if(strlen(trim($msg_erro))==0){
        
        foreach ($marca as $linhaMarca) { 
            $sqlVer = "SELECT diagnostico from tbl_diagnostico where marca = $linhaMarca and solucao = $solucao and fabrica = $login_fabrica";
            $resVer = pg_query($con, $sqlVer);
            if(pg_num_rows($resVer)>0){
                $diagnostico = pg_fetch_result($resVer, 0, 'diagnostico');
                $sql = "UPDATE tbl_diagnostico SET ativo = $ativo WHERE diagnostico = $diagnostico AND solucao = $solucao AND marca = $linhaMarca";
            }else{
                $sql = " INSERT INTO tbl_diagnostico (solucao, ativo, marca, fabrica) VALUES ($solucao, $ativo, $linhaMarca, $login_fabrica) ";    
            }  
            $res = pg_query($con, $sql);
            $erro .= pg_last_error($con);
        }   
        
        if(strlen(trim($erro))==0){
            $ok = 'Cadastro realizado com sucesso.';
        }else{
            $msg_erro .= "Falha ao realizar cadastro.";
        }
    }
}

?>
<style>
table tr>td:first-of-type {
    text-align: right;
    padding-right: 1em;
}
.ui-multiselect{
    line-height: 15px;
}
</style>
<?

    // if(!isset($semcab))include 'cabecalho.php';
?>
<script>
$(function(){
    $("#marca").multiselect({
       selectedText: "selecionados # de #",
    });   

    $(".ativar").click(function(){
        var diagnostico = $(this).data('diagnostico');

        $.ajax({
            url: "solucao_marca.php",
            type: "POST",
            dataType:"JSON",
            data: {
                ativar: true,
                diagnostico: diagnostico                
            }
        }).done(function(data) {
            if (data.erro) {
                alert(data.erro);
            } else {
                $('#ativar_'+diagnostico).hide();
                $('#inativar_'+diagnostico).show();
                $('.ativo_'+diagnostico).show();
                $('.inativo_'+diagnostico).hide();
            }
        });
    });


    $(".inativar").click(function(){
        var diagnostico = $(this).data('diagnostico');

        $.ajax({
            url: "solucao_marca.php",
            type: "POST",
            dataType:"JSON",
            data: {
                inativar: true,
                diagnostico: diagnostico                
            }
        }).done(function(data) {
            if (data.erro) {
                alert(data.erro);
            } else {
                $('#ativar_'+diagnostico).show();
                $('#inativar_'+diagnostico).hide();
                $('.inativo_'+diagnostico).show();
                $('.ativo_'+diagnostico).hide();
            }
        });

    });
});

</script>

<? if (strlen($msg_erro) > 0) { ?>
    <div class="alert alert-error">
        <h4><?echo $msg_erro;?></h4>
    </div>
<? } ?>
<? if (strlen($ok) > 0) { ?>
    <div class="alert alert-success">
        <h4><?echo $ok;?></h4>
    </div>
<? } ?>
<br/>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form class='form-search form-inline tc_formulario' name="frm_familia" method="post" action="<? echo $PHP_SELF;if(isset($semcab))echo "?semcab=yes"; ?>">
<input type="hidden" name="familia" value="<? echo $familia ?>" />

<div class="titulo_tabela">Solução X Marca</div>
<br/>
<?
    if ($login_fabrica == 19) {
        $span = 1;
    } else {
        $span = 2;
    }
?>
<div class="row-fluid">
    <!-- Margem -->
    <div class="span<?= $span ?>"></div>
    <div class="span4">
        <div class="control-group">
            <label class="control-label" for=''>Solucao</label>
            <div class='controls controls-row'>
                <h5 class="asteristico">*</h5>
                <select name="solucao">
                    <option value=""></option>
                    <?php 
                        $sql = "SELECT solucao, descricao from tbl_solucao where fabrica = $login_fabrica and ativo = 't' order by descricao ";
                        $res = pg_query($con, $sql);
                        for($i = 0; $i<pg_num_rows($res); $i++){   
                            $solucao = pg_fetch_result($res, $i, 'solucao');
                            $descricao = pg_fetch_result($res, $i, 'descricao');

                            echo "<option value='$solucao'>$descricao</option>";
                        }
                    ?>
                </select>
            </div>
        </div>
    </div>
    <div class="span4">
        <div class='control-group <?=(strpos($msg_erro,"marca") !== false) ? "error" : "" ?>'>
            <label class="control-label" for="">Marca</label>
            <div class="controls controls-row">
                <h5 class="asteristico">*</h5>
                 <select name="marca[]" id="marca" multiple="multiple"> 
                    <?php 
                        $sql = "SELECT marca, nome from tbl_marca where fabrica = $login_fabrica and ativo = 't' order by nome";
                        $res = pg_query($con, $sql);
                        for($i = 0; $i<pg_num_rows($res); $i++){   
                            $marca = pg_fetch_result($res, $i, 'marca');
                            $nome = pg_fetch_result($res, $i, 'nome');

                            echo "<option value='$marca'>$nome</option>";
                        }
                    ?>
                </select>
            </div>
        </div>
    </div>
    <div class="span1">
        <div class="control-group tac">
            <label class="control-label" for="">&nbsp;</label>
            <div class="controls controls-row tac">
                <label class="checkbox" >
                    <input type='checkbox' name='ativo' id='ativo' value='t' <?if($ativo == 't' || $ativo == 'TRUE') echo "CHECKED";?> /> Ativo
                </label>
            </div>
        </div>
    </div>
    <!-- Margem -->
    <div class="span2"></div>
</div> 

    <br/>
    <div class="row-fluid">
        <!-- Margem -->
        <div class="span4"></div>

        <div class="span4 tac">
            <button type="button" class="btn"  onclick="submitForm($(this).parents('form'),'gravar');" alt="Gravar formulário" >Gravar</button>
            <? if(strlen($familia) > 0){ ?>
                <button type="button" class="btn btn-danger"  onclick="submitForm($(this).parents('form'),'deletar');" alt="Apagar familia" >Apagar</button>
            <? } ?>
                <input type='hidden' id="btn_click" name='btn_acao' value='' />     
        
        </div>
        <!-- Margem -->
        <div class="span4"></div>
    </div>
    <br/>
<?
 ?>
</form>
<table class='table table-striped table-bordered table-hover table-fixed'>
    <thead>
        <tr class='titulo_tabela'>
            <th colspan='4'>Solução X Marcas Cadastradas</th>
        </tr>
        <tr class='titulo_coluna'>
            <th>Marca</th>
            <th>Solução</th>
            <th>Ativo</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php 
            $sql = " SELECT 
                        diagnostico, 
                        solucao, 
                        marca, 
                        tbl_marca.nome as marca_descricao, 
                        tbl_solucao.descricao as descricao_solucao, 
                        tbl_diagnostico.ativo 
                    from tbl_diagnostico 
                    inner join tbl_solucao using(solucao) 
                    inner join tbl_marca using(marca)  
                    where tbl_diagnostico.fabrica = $login_fabrica 
                    and solucao is not null 
                    and marca is not null";
            $res = pg_query($con, $sql);
            for($i=0; $i<pg_num_rows($res); $i++){
                $diagnostico            = pg_fetch_result($res, $i, diagnostico);
                $marca                  = pg_fetch_result($res, $i, marca);
                $solucao                = pg_fetch_result($res, $i, solucao);
                $marca_descricao        = trim(pg_fetch_result($res, $i, marca_descricao));
                $descricao_solucao      = trim(pg_fetch_result($res, $i, descricao_solucao));
                $ativo                  = pg_fetch_result($res, $i, ativo);

                $status_verde = ($ativo == 't') ? 'display:block' : 'display:none';
                $status_vermelho = ($ativo == 'f') ? 'display:block' : 'display:none';
              
                echo "<tr>
                        <td style='text-align:center'>$marca_descricao</td>
                        <td>$descricao_solucao</td>
                        <td style='text-align:center' class='tac'>";                              
                            echo "<center> 
                            <img name='ativo' class='ativo_$diagnostico' src='imagens/status_verde.png' title='Ativo' style='$status_verde'>";
                            echo "<img name='ativo' class='inativo_$diagnostico' src='imagens/status_vermelho.png' title='Inativo' style='$status_vermelho'>
                            </center>";                        
                        echo "</td>
                        <td style='text-align:center; width:50px;' >";
                            
                            if($ativo == 'f'){
                                $displayA = " style='display:block' ";
                                $displayI = " style='display:none' ";
                            }else{
                                $displayA = " style='display:none' ";
                                $displayI = " style='display:block' ";
                            }

                            echo "<center><button type='button' class='btn btn-primary ativar' $displayA data-diagnostico='$diagnostico' id='ativar_$diagnostico'>Ativar</button>"    ;
                            echo "<button type='button' class='btn btn-danger inativar' $displayI data-diagnostico='$diagnostico'  id='inativar_$diagnostico'>Inativar</button></center>";
                        echo "</td>
                    </tr>";
            }
        ?>
        
    </tbody>
</table>
<?if(!isset($semcab))include "rodape.php";
?>
