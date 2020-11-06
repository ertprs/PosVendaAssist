<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';


if (strlen($_GET["defeito"]) > 0) {
    $defeito = trim($_GET["defeito"]);
}

if (strlen($_POST["defeito"]) > 0) {
    $defeito = trim($_POST["defeito"]);
}

if (strlen($_POST["qtde_item"]) > 0) {
    $qtde_item = trim($_POST["qtde_item"]);
}

if (strlen($_POST["btn_acao"]) > 0) {
    $btnacao = trim($_POST["btn_acao"]);
}

if ($btnacao == "gravar") {
    $res = pg_query($con,"BEGIN TRANSACTION");
    
    if (strlen($msg_erro) == 0){
        for ($i = 0 ; $i < $qtde_item ; $i++) {
            $novo                  = $_POST['novo_'.                   $i];
            $defeito_causa_defeito = $_POST['defeito_causa_defeito_'.  $i];
            $causa_defeito         = $_POST['causa_defeito_'.          $i];
            
            if ($novo == 'f' and strlen($causa_defeito) == 0) {
                $sql = "DELETE FROM tbl_defeito_causa_defeito
                        WHERE       tbl_defeito_causa_defeito.defeito_causa_defeito = $defeito_causa_defeito";
                $res = pg_query($con,$sql);
                if (strlen(pg_last_error($con)) > 0) {
                    $msg_erro["msg"][] = pg_last_error($con);
                }
            }
            
            if (strlen ($msg_erro) == 0) {
                if (strlen($causa_defeito) > 0) {
                    if ($novo == 't'){
                        $sql = "INSERT INTO tbl_defeito_causa_defeito (
                                    defeito       ,
                                    causa_defeito
                                ) VALUES (
                                    $defeito      ,
                                    $causa_defeito
                                )";
                    }else{
                        $sql = "UPDATE tbl_defeito_causa_defeito SET
                                    defeito       = $defeito      ,
                                    causa_defeito = $causa_defeito
                                WHERE  tbl_defeito_causa_defeito.defeito_causa_defeito = $defeito_causa_defeito
                                AND    tbl_defeito_causa_defeito.defeito               = $defeito ";
                    }
                    $res = pg_query($con,$sql);
                    if (strlen(pg_last_error($con)) > 0) {
                        $msg_erro["msg"][] = pg_last_error($con);
                    }
                }
            }
        }
    }
    
    if (count($msg_erro) == 0) {
        ###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
        $res = pg_query($con,"COMMIT TRANSACTION");
        
        $msg_sucesso["msg"][] = "Gravado com sucesso";
        echo "<meta http-equiv=refresh content=\"2;URL=defeito_causa_defeito_cadastro.php?defeito=$defeito\">";
    }else{
        ###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
        $res = pg_query($con,"ROLLBACK TRANSACTION");
    }
}


$layout_menu = "cadastro";
$title = "CADASTRO DE CAUSAS DE DEFEITO POR DEFEITO";
include 'cabecalho_new.php';

$plugins = array(
    "dataTable",
);

include("plugin_loader.php");

?>
<style type="text/css">
.listas{
    list-style-type: none;
    margin-top: 20px;
}
.listas li{
    display: inline;
    width: 50%;
    FLOAT: LEFT;
    text-align: left;
    font-size: 12px;
}
.listas li div{

    min-width: 30px;
    display: inline-block;
}
</style>
<script language="javascript">
    var hora = new Date();
    var engana = hora.getTime();

    $(function() {
        $.dataTableLoad("#tabela");

    });
</script>
<?php if (strlen($defeito) == 0) {?>
    <table class='table table-striped table-bordered table-hover table-fixed' id='tabela'>
        <thead>
           <tr class='titulo_tabela'>
                <th colspan='4'>Relação dos Defeitos Cadastrados</th>
            </tr>   
            <tr class='titulo_coluna' >
                <th align='left'>CÓDIGO</th>
                <th align='left'>DESCRIÇÃO</th>
                <th align='left'></th>
            </tr>
        </thead>
        <tbody>
            <?php
                $orderBy = " lpad(tbl_defeito.codigo_defeito::text,10,'0');";
                if ($login_fabrica == 131) {
                    $orderBy = " tbl_defeito.codigo_defeito::integer ASC";
                }
                $sql = "SELECT  tbl_defeito.defeito       ,
                                tbl_defeito.codigo_defeito,
                                tbl_defeito.descricao
                        FROM    tbl_defeito
                        WHERE   tbl_defeito.fabrica = $login_fabrica
                        ORDER BY $orderBy";
                $res = pg_query($con, $sql);
                for ($x = 0 ; $x < pg_num_rows($res) ; $x++) {
                    $defeito        = trim(pg_result($res,$x,defeito));
                    $codigo_defeito = trim(pg_result($res,$x,codigo_defeito));
                    $descricao      = trim(pg_result($res,$x,descricao));
            ?>
            <tr>
                <td><?php echo $codigo_defeito;?></td>
                <td><?php echo $descricao;?></td>
                <td>
                    <a href="defeito_causa_defeito_cadastro.php?defeito=<?php echo $defeito;?>" class="btn btn-info">Editar</a>
                </td>
            </tr>
            <?php }?>
        </tbody>
    </table>
<?php } else {

    if (count($msg_erro) > 0) { 
        echo "<div class='alert alert-important'>
        <h4>".implode("<br/>", $msg_erro["msg"])."</h4>
        </div>";
    }

    if (count($msg_sucesso) > 0 && count($msg_erro) == 0) {
        echo "<div class='alert alert-success'>
        <h4>".implode("<br/>", $msg_sucesso["msg"])."</h4>
        </div>";    
    }   
?>


<form name='frm_defeito' METHOD='POST' enctype="multipart/form-data" ACTION='<?=$PHP_SELF?>?defeito=<?=$defeito?>' align='center' class='form-search form-inline tc_formulario' >
    <input type="hidden" name="defeito" value="<? echo $defeito ?>">
    
<?php
    $sql = "SELECT descricao
              FROM tbl_defeito
             WHERE fabrica = $login_fabrica
               AND defeito = $defeito";
    $res = pg_query($con, $sql);
    $descricao = trim(pg_result($res,0,descricao));

    echo '<div class="titulo_tabela">Selecione as Causas para o Defeito <br /> "'.$descricao.'"</div>';
    $orderBy = " lpad(codigo::text,5,' ') ASC";
    if ($login_fabrica == 131) {
        $orderBy = " codigo::integer ASC";
    }
    $sql = "SELECT tbl_causa_defeito.causa_defeito,
                   tbl_causa_defeito.codigo,
                   tbl_causa_defeito.descricao
              FROM tbl_causa_defeito
             WHERE tbl_causa_defeito.fabrica = $login_fabrica
          ORDER BY $orderBy";
    $res = pg_query($con, $sql);
    echo "<ul class='listas'>";
    if (pg_num_rows($res) > 0) {
        $y=1;
    
        for($i=0; $i < pg_num_rows($res); $i++) {
            $causa_defeito = trim(pg_result($res,$i,causa_defeito));
            $codigo        = trim(pg_result($res,$i,codigo));
            $descricao     = trim(pg_result($res,$i,descricao));
            
            if (strlen($defeito) > 0) {
                $sql = "SELECT tbl_defeito_causa_defeito.defeito_causa_defeito,
                               tbl_defeito_causa_defeito.defeito              ,
                               tbl_defeito_causa_defeito.causa_defeito
                          FROM tbl_defeito_causa_defeito
                         WHERE tbl_defeito_causa_defeito.defeito       = $defeito
                           AND tbl_defeito_causa_defeito.causa_defeito = $causa_defeito";
                $res2 = pg_query($con, $sql);
                
                if (pg_num_rows($res2) > 0) {
                    $novo                  = 'f';
                    $defeito_causa_defeito = trim(pg_result($res2,0,defeito_causa_defeito));
                    $xcausa_defeito        = trim(pg_result($res2,0,causa_defeito));
                } else {
                    $novo                  = 't';
                    $defeito_causa_defeito = "";
                    $xcausa_defeito        = "";
                }
            } else {
                $novo                  = 't';
                $defeito_causa_defeito = "";
                $xcausa_defeito        = "";
            }

            $resto = $y % 2;
            $y++;

            if ($xcausa_defeito == $causa_defeito)
                $check = " checked ";
            else
                $check = "";
            echo "<li>
                    <input type='hidden' name='novo_$i' value='$novo'>
                    <input type='hidden' name='defeito_causa_defeito_$i' value='$defeito_causa_defeito'>
                    <input type='checkbox' name='causa_defeito_$i'value='$causa_defeito' $check>
                    <div>$codigo</div> - $descricao 
                <li>";
        }
    }
    echo "<input type='hidden' name='qtde_item' value='$i'>\n";
    echo "</ul>";

?>
<div class='row-fluid'>
    <div class='span2'></div>
    <div class='span8 tac'>
            <br/>
            <button class='btn' id="btn_acao" type="button"  onclick="$('#btn_click').val('gravar');submit('frm_causa_defeito');">Gravar</button>
            <input type='hidden' id="btn_click" name='btn_acao' value='' /><br/><br/>
            
    </div>
    <div class='span2'></div>
</div>

</div>
</form>
<?php }
include "rodape.php";
?>
