<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios = "cadastros";
include 'autentica_admin.php';
include_once "../helpdesk.inc.php";

function existeCategoriaTipoPosto($admin){
    global $con;
    global $login_fabrica;
    $sql = "SELECT admin, categoria_posto, tipo_posto
        FROM tbl_admin_atendente_estado
        WHERE fabrica = {$login_fabrica} AND
                            admin = {$admin} AND
          (categoria_posto IS NOT NULL
                            OR tipo_posto IS NOT NULL);";
    $res = pg_query($con, $sql);

    if(pg_num_rows($res) > 0){
    return pg_fetch_all($res);
    }else{
    return false;
    }
}

function buscaTipoPosto($conn = null, $login_fabrica = null, $tipo_posto = null) {

    $sqlTipoPosto = "SELECT tipo_posto, descricao FROM tbl_tipo_posto WHERE fabrica = {$login_fabrica} AND ativo = TRUE";

    if ($tipo_posto != null) {
        $sqlTipoPosto .= " AND tipo_posto = {$tipo_posto};";
    } else {
        $sqlTipoPosto .= " ORDER BY descricao ASC;";
    }

    $resTipoPosto = pg_query($conn, $sqlTipoPosto);
    $resTipoPosto = pg_fetch_all($resTipoPosto);

    return $resTipoPosto;

}

if ($_POST["apagarAtendente"] == "true") {
    $admin_atendente_estado = $_POST["admin_atendente_estado"];

    if (strlen($admin_atendente_estado) > 0) {
        $sql = "SELECT tbl_admin_atendente_estado.admin,
                       categoria_posto,
                       tipo_posto, 
                       tbl_admin.nome_completo
                FROM tbl_admin_atendente_estado
                INNER JOIN tbl_admin ON tbl_admin.admin = tbl_admin_atendente_estado.admin
                WHERE admin_atendente_estado = {$admin_atendente_estado}
                AND tbl_admin_atendente_estado.fabrica = {$login_fabrica}";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $admin_a      = pg_fetch_result($res, 0, "admin");
            $cat_posto_a  = strtoupper(pg_fetch_result($res, 0, "categoria_posto"));
            $tipo_posto_a = pg_fetch_result($res, 0, "tipo_posto");
            $nome_completo_anterior = pg_fetch_result($res, 0, "nome_completo");

            pg_query($con,'BEGIN');

            $sql = "DELETE FROM tbl_admin_atendente_estado
                            WHERE admin_atendente_estado = {$admin_atendente_estado}
                            AND fabrica = {$login_fabrica}";
            $res = pg_query($con, $sql);

            if ($login_fabrica == 1 AND strlen(pg_last_error()) == 0 ) {                
                if (strlen($cat_posto_a)> 0) {
                    $cond_cpa = "AND upper(tbl_posto_fabrica.categoria) = upper('$cat_posto_a')";
                }
                if (strlen($tipo_posto_a) > 0) {                  
                    $cond_tpa = "AND tbl_posto_fabrica.tipo_posto = $tipo_posto_a";
                }
                
                $sql_d = "SELECT DISTINCT tbl_hd_chamado.categoria,
                                          tbl_hd_chamado.posto,
                                          tbl_hd_chamado.hd_chamado,
                                          tbl_hd_chamado.status
                            FROM tbl_hd_chamado
                            JOIN tbl_posto_fabrica ON tbl_hd_chamado.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                            WHERE tbl_hd_chamado.fabrica = $login_fabrica
                                AND tbl_hd_chamado.atendente = $admin_a
                                $cond_cpa
                                $cond_tpa
                                AND status not in ('Resolvido','Resolvido Posto','Cancelado');";
                $res_d = pg_query($con,$sql_d);

                if (pg_num_rows($res_d) > 0) {
                    for ($i=0; $i < pg_num_rows($res_d) ; $i++) { 
                        $posto_u = pg_fetch_result($res_d, $i, posto);
                        $categoria_u = pg_fetch_result($res_d, $i, categoria);
                        $hd_chamado_u = pg_fetch_result($res_d, $i, hd_chamado);
                        $status_u = pg_fetch_result($res_d, $i, status);

                        $atendente_u = $categorias[$categoria_u]['atendente'];
                        $atendente_u = (is_numeric($atendente_u)) ? $atendente_u : hdBuscarAtendentePorPosto($posto_u,$categoria_u);

                        if ($admin_a != $atendente_u) {

                            $sql_nome_novo_atendente = "select nome_completo from tbl_admin where admin = $atendente_u";
                            $res_nome_novo_atendente = pg_query($con, $sql_nome_novo_atendente);
                            if(pg_num_rows($res_nome_novo_atendente) > 0 ){
                                $nome_completo_novo = pg_fetch_result($res_nome_novo_atendente, 0, 'nome_completo');
                            }

                            $sql_u = "UPDATE tbl_hd_chamado SET
                                    atendente = {$atendente_u}
                                    WHERE atendente = {$admin_a}
                                        AND fabrica = {$login_fabrica}
                                        AND posto = {$posto_u}
                                        AND hd_chamado = {$hd_chamado_u}
                                        AND status not in ('Resolvido','Resolvido Posto','Cancelado');";
                            $res_u = pg_query($con,$sql_u);

                            if(strlen(trim(pg_last_error($con)))==0){
                                $frase_transferencia = "Chamado transferido automaticamente: de ". $nome_completo_anterior ." para ". $nome_completo_novo ." <br>Atendente anterior excluído!";
                            }

                            $hd_chamado_item_u = hdCadastrarResposta($hd_chamado_u, $frase_transferencia,true, $status_u, $login_admin);
                        }
                    }
                }
            }

            if (strlen(pg_last_error()) > 0) {
                pg_query($con,'ROLLBACK');
                echo "erro";
            }else{
                pg_query($con,'COMMIT');
            }            
        } else {
            echo "erro";
        }
    } else {
        echo "erro";
    }
    exit;
}

if($_POST["btn_acao"] == "submit") {
    $admin_atendente_estado = $_POST["admin_atendente_estado"];
    $atendente              = $_POST["atendente"];
    $categoria_posto = $_POST["categoria_posto"];
    $tipo_posto = $_POST["tipo_posto"];

    if (empty($atendente)) {
        $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][]   = "atendente";
    }
    if (empty($categoria_posto) && empty($tipo_posto)) {
        $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][]   = "categoria_posto";
        $msg_erro["campos"][]   = "tipo_posto";
    }

    if (count($msg_erro) == 0) {
        if ($tipo_posto == "") $tipo_posto = null;
        pg_query($con, "BEGIN");
        if (empty($admin_atendente_estado)) {

            $resAtendente = existeCategoriaTipoPosto($atendente);

            $temCategoria = false;
            $temTipo = false;
            if ($resAtendente != false) {
                foreach ($resAtendente as $keyAtendente) {
                    if ($keyAtendente['categoria_posto'] == $categoria_posto) {
                        $temCategoria = true;
                    }
                    if ($keyAtendente['tipo_posto'] == $tipo_posto) {
                        $temTipo = true;
                    }
                }
            }

            if (!empty($categoria_posto) && !$temCategoria) {
                $sql = "INSERT INTO tbl_admin_atendente_estado
                                                        (admin, fabrica, categoria_posto)
                                                        VALUES
                                                        ({$atendente}, {$login_fabrica}, '{$categoria_posto}')";
            } else if (!empty($tipo_posto) && !$temTipo) {
                $sql = "INSERT INTO tbl_admin_atendente_estado
                                                        (admin, fabrica, tipo_posto)
                                                        VALUES
                                                        ({$atendente}, {$login_fabrica}, '{$tipo_posto}')";
            } else {
                $msg_erro["msg"][] = "Esse atendente já está cadastrado para essa categoria ou esse tipo de posto!";
                unset($admin_atendente_estado);
                unset($atendente);
                unset($categoria_posto);
                unset($tipo_posto);
            }
        } else {
            $resAtendente = existeCategoriaTipoPosto($atendente);
            $resCatPosto = "";
            $resTipoPosto = "";
            if ($resAtendente != false) {
                foreach ($resAtendente as $keyAtendente) {
                    if ($keyAtendente['categoria_posto'] == $categoria_posto) {
                        $resCatPosto =  $keyAtendente['categoria_posto'];
                    }
                    if ($keyAtendente['tipo_posto'] == $tipo_posto) {
                        $resTipoPosto = $keyAtendente['tipo_posto'];
                    }
                }
            }

            if ($login_fabrica == 1) {

                $sql_at = " SELECT admin,estado,cod_ibge 
                                FROM tbl_admin_atendente_estado 
                                WHERE admin_atendente_estado = {$admin_atendente_estado}
                                    AND fabrica = {$login_fabrica};";
                $res_at = pg_query($con,$sql_at);
                if (pg_num_rows($res_at) > 0) {
                    $atendente_atual = pg_fetch_result($res_at, 0, admin);
                    $estado_atual = pg_fetch_result($res_at, 0, estado);                        
                    $cod_ibge_atual = pg_fetch_result($res_at, 0, cod_ibge);
                    if (strlen($cod_ibge_atual) > 0) {
                        $cond_ibge = " AND tbl_ibge.cod_ibge = {$cond_ibge} ";
                    }

                    if ($atendente_atual != $atendente) {
                        $sql_up = " UPDATE tbl_hd_chamado 
                                        SET 
                                            atendente = {$atendente}
                                    WHERE fabrica = {$login_fabrica} 
                                        AND atendente = $atendente_atual 
                                        AND status ilike 'Ag.%'
                                        AND hd_chamado in (
                                                SELECT
                                                        tbl_hd_chamado_extra.hd_chamado
                                                    FROM tbl_hd_chamado_extra
                                                        JOIN tbl_cidade on tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
                                                        JOIN tbl_ibge on tbl_cidade.cod_ibge = tbl_ibge.cod_ibge
                                                    where tbl_hd_chamado_extra.fabrica = {$login_fabrica}
                                                        AND tbl_cidade.estado = {$estado_atual}
                                                        $cond_ibge
                                                );";
                        $res_up = pg_query($con,$sql_up);                       
                    }
                }

                if (strlen(pg_last_error($con)) > 0) {
                    $msg_erro["msg"][] = "Erro ao Atualizar Atendente nos Chamados !";
                }

                if ($resCatPosto == "" && !empty($categoria_posto)) {
                    $sql = "UPDATE tbl_admin_atendente_estado
                                SET
                                   admin    = {$atendente},
                                   categoria_posto = '{$categoria_posto}'
                                WHERE admin_atendente_estado = {$admin_atendente_estado}
                                    AND fabrica = {$login_fabrica}";
                } else if ($resTipoPosto == "" && !empty($tipo_posto)) {
                    $sql = "UPDATE tbl_admin_atendente_estado
                                SET
                                    admin    = {$atendente},
                                    tipo_posto = '{$tipo_posto}'
                                WHERE admin_atendente_estado = {$admin_atendente_estado}
                                    AND fabrica = {$login_fabrica}";
                } else {
                    $msg_erro["msg"][] = "Não foi possível efetuar a alteração dos dados!";
                }
            }else{
                if ($resCatPosto == "" && !empty($categoria_posto)) {
                    $sql = "UPDATE tbl_admin_atendente_estado
                                SET
                                   admin    = {$atendente},
                                   categoria_posto = '{$categoria_posto}'
                                WHERE admin_atendente_estado = {$admin_atendente_estado}
                                    AND fabrica = {$login_fabrica}";
                } else if ($resTipoPosto == "" && !empty($tipo_posto)) {
                    $sql = "UPDATE tbl_admin_atendente_estado
                                SET
                                    admin    = {$atendente},
                                    tipo_posto = '{$tipo_posto}'
                                WHERE admin_atendente_estado = {$admin_atendente_estado}
                                    AND fabrica = {$login_fabrica}";
                } else {
                    $msg_erro["msg"][] = "Não foi possível efetuar a alteração dos dados!";
                }
            }
        }

        /* echo $sql;exit; */
        if (!count($msg_erro["msg"])) {
            $res = pg_query($con, $sql);

            if (!pg_last_error()) {
                $msg_success = true;
                unset($_POST);
                unset($admin_atendente_estado);
                unset($atendente);
                unset($categoria_posto);
                unset($tipo_posto);
                pg_query($con, "COMMIT");
            } else {
              $rollback = pg_query($con, "ROLLBACK");
                if (strlen(pg_last_error()) > 0) {
                    $msg_erro["msg"][] = "Erro ao gravar as informações do atendente";
                }
            }
        } else {
            pg_query($con, "ROLLBACK");
        }
    }
}
if(strlen($_GET["admin_atendente_estado"]) > 0){
    $sql = "SELECT admin_atendente_estado, admin, categoria_posto, tipo_posto from tbl_admin_atendente_estado where admin_atendente_estado = {$_GET['admin_atendente_estado']};";
    $res = pg_query($con,$sql);
    if(pg_num_rows($res) > 0){
    $admin_atendente_estado = pg_fetch_result($res, 0, "admin_atendente_estado");
    $atendente = pg_fetch_result($res, 0, "admin");
    $categoria_posto = pg_fetch_result($res, 0, "categoria_posto");
                $tipo_posto = pg_fetch_result($res, 0, "tipo_posto");
    }
}
$layout_menu = "cadastro";
$title = "ATENDENTE POR CATEGORIA/TIPO DE POSTO AUTORIZADO";

$tableHead  = "Cadastro";
if ($atendente != "") {
    $tableHead = "Alteração de Cadastro";
}

include "cabecalho_new.php";

?>

<script>
 $(function () {

     $("button[name=apagar]").click(function () {
     var tr                     = $(this).parents("tr");
     var admin_atendente_estado = $(this).parent("td").find("input[name=admin_atendente_estado_resultado]").val();

     if (admin_atendente_estado.length > 0) {
         if (ajaxAction()) {
         $.ajax({
             url: "cadastro_atendente_categoria_posto.php",
             type: "POST",
             data: { apagarAtendente: true, admin_atendente_estado: admin_atendente_estado },
             beforeSend: function () {
             loading("show");
             },
             complete: function (data) {
             if (data.responseText == "erro") {
                 alert("Erro ao deletar");
             } else {
                 $(tr).remove();
                 alert("Atendente apagado com sucesso");
             }

             loading("hide");
             }
         });
         }
     }
     });
 });

</script>
<?php if ($atendente == "") { ?>
<script>

$(function () {

    $("select[name=tipo_posto]").change(function() {
       if ($(this).val() != "") {
            $("select[name=categoria_posto]").attr("disabled", true);
       } else {
            $("select[name=categoria_posto]").attr("disabled", false);
       }
     });

     $("select[name=categoria_posto]").change(function() {
       if ($(this).val() != "") {
            $("select[name=tipo_posto]").attr("disabled", true);
       } else {
            $("select[name=tipo_posto]").attr("disabled", false);
       }
     });

 });

</script>

<?php
}
if ($msg_success) {
?>
<div class="alert alert-success">
    <h4>Gravado com sucesso</h4>
</div>
<?php
}

if (count($msg_erro["msg"]) > 0) {
?>
<div class="alert alert-error">
    <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
</div>
<?php
}
?>

<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_atendente_categoria_posto' METHOD='POST' ACTION='<?=$PHP_SELF?>' class='form-search form-inline tc_formulario'>
    <input type="hidden" name="admin_atendente_estado" value="<?=$admin_atendente_estado?>" />

    <div class='titulo_tabela '><?=$tableHead?></div>

    <br />

    <div class='row-fluid'>
    <div class='span2'></div>

    <div class='span3'>
        <div class='control-group <?=(in_array("atendente", $msg_erro["campos"])) ? "error" : ""?>'>
        <label class='control-label' for='atendente'>Atendente</label>
        <div class='controls controls-row'>
            <div class='span12'>
            <h5 class='asteristico'>*</h5>
            <select name='atendente' class='span12'>
                <option></option>
                <?php
                $sql = "SELECT admin, nome_completo
                FROM tbl_admin
                WHERE fabrica = {$login_fabrica}
                AND admin_sap IS TRUE
                ORDER BY nome_completo";
                $res = pg_query($con, $sql);

                if (pg_num_rows($res) > 0) {

                for ($i = 0; $i < pg_num_rows($res); $i++) {
                    $admin = pg_fetch_result($res, $i, "admin");
                    $nome_completo = pg_fetch_result($res, $i, "nome_completo");

                    $selected = ($admin == $atendente) ? "selected" : "";

                    echo "<option value='{$admin}' {$selected}>{$nome_completo}</option>";
                }
                }
                ?>
            </select>
            </div>
        </div>
        </div>
    </div>

     <?php if ($login_fabrica == 1){

                $checkedA = (strtolower($categoria_posto) == 'autorizada') ? "SELECTED" : "";
                $checkedL = (strtolower($categoria_posto) == 'locadora') ? "SELECTED" : "";
                $checkedAL = (strtolower($categoria_posto) == 'locadora autorizada') ? "SELECTED" : "";
                $checkedPC = (strtolower($categoria_posto) == "pré cadastro") ? "SELECTED" : "";
                $checkedMP = (strtolower($categoria_posto) == "mega projeto") ? "SELECTED" : "";

        ?>

    <div class='span2'>
        <div class='control-group <?=(in_array("categoria_posto", $msg_erro["campos"])) ? "error" : ""?>'>
        <label class='control-label' for='categoria_posto'>Categoria Posto</label>
        <div class='controls controls-row'>
            <div class='span12'>
            <h5 class='asteristico'>*</h5>
                    <select name="categoria_posto" class="span12">
                        <option value=""></option>
                        <option value="Autorizada" <?=$checkedA?>>Autorizada</option>
                        <option value="Locadora" <?=$checkedL?>>Locadora</option>
                        <option value="Locadora Autorizada" <?=$checkedAL?>>Locadora Autorizada</option>
                        <option value="Pr&eacute; Cadastro" <?=$checkedPC?>>Pré Cadastro</option>
                        <option value="mega projeto" <?=$checkedMP?>>Industria/Mega Projeto</option>
                    </select>
            </div>
        </div>
        </div>
    </div>

        <div class='span3'>
            <div class='control-group <?=(in_array("tipo_posto", $msg_erro["campos"])) ? "error" : ""?>'>
            <label class='control-label' for='tipo_posto'>Tipo Posto</label>
            <div class='controls controls-row'>
                <div class='span12'>
                <h5 class='asteristico'>*</h5>
                        <select name="tipo_posto" class="span12">
                            <option value=""></option>
                            <?php

                                $tipos_posto = buscaTipoPosto($con,$login_fabrica);

                                foreach ($tipos_posto as $tipo) {
                                    ($tipo['tipo_posto'] == $tipo_posto) ? $checkedtp = " SELECTED" : $checkedtp = "";
                                    echo "<option value=\"".$tipo['tipo_posto']."\"$checkedtp>".$tipo['descricao']."</option>";
                                }

                            ?>
                        </select>
                </div>
            </div>
        </div>
    </div>

    <? } ?>
    <div class='span1'></div>
    </div>

    <br />

    <p><br/>
    <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
    <input type='hidden' id="btn_click" name='btn_acao' value='' />
    <?php
    if (strlen($_GET["admin_atendente_estado"]) > 0) {
    ?>
    <button class='btn btn-warning' type="button"  onclick="window.location = '<?=$_SERVER["PHP_SELF"]?>';">Limpar</button>
    <?php
    }
    ?>
    </p><br/>
</form>

<?php

if ($tipo_posto != "") {
    echo "<script>$(\"select[name=categoria_posto]\").attr(\"disabled\", true);</script>";
} else if ($categoria_posto != "") {
    echo "<script>$(\"select[name=tipo_posto]\").attr(\"disabled\", true);</script>";
}

?>

<br />

<table id="atendente_cadastrados" class='table table-striped table-bordered table-hover table-fixed' >
    <thead>
    <tr class="titulo_coluna" >
        <th>Atendente</th>
        <th>Categoria</th>
                <th>Tipo</th>
        <th></th>
    </tr>
    </thead>
    <tbody>
    <?php
    $sql = "SELECT
            tbl_admin_atendente_estado.admin_atendente_estado,
            tbl_admin_atendente_estado.categoria_posto,
            tbl_admin_atendente_estado.tipo_posto,
            tbl_admin.nao_disponivel,
            tbl_admin.nome_completo
        FROM tbl_admin_atendente_estado
        JOIN tbl_admin ON tbl_admin.admin = tbl_admin_atendente_estado.admin
        AND tbl_admin.fabrica = {$login_fabrica}
        WHERE (tbl_admin_atendente_estado.categoria_posto IS NOT NULL
                            OR tbl_admin_atendente_estado.tipo_posto IS NOT NULL)
        AND tbl_admin_atendente_estado.fabrica = {$login_fabrica}
        AND tbl_admin.ativo IS TRUE";
    $res = pg_query($con, $sql);

    $rows = pg_num_rows($res);

    if ($rows > 0) {
        for ($i = 0; $i < $rows; $i++) {
        $admin_atendente_estado = pg_fetch_result($res, $i, "admin_atendente_estado");
        $nome_completo                  = pg_fetch_result($res, $i, "nome_completo");
        $nao_disponivel                  = pg_fetch_result($res, $i, "nao_disponivel");
        $categoria_posto                 = pg_fetch_result($res, $i, "categoria_posto");
                            $tipo_posto                 = pg_fetch_result($res, $i, "tipo_posto");

                            if ($tipo_posto != "") {

                                $tipo_posto = buscaTipoPosto($con,$login_fabrica,$tipo_posto);
                                $tipo_posto = $tipo_posto[0]['descricao'];

                            }
    ?>

    <tr>
        <td><a href="<?=$_SERVER['PHP_SELF']?>?admin_atendente_estado=<?=$admin_atendente_estado?>" ><?=$nome_completo?></a> <?=(!empty($nao_disponivel)?'(indisponível)':'')?></td>
        <td><?=$categoria_posto?></td>
                <td><?=$tipo_posto?></td>
        <td class="tac" >
        <input type="hidden" name="admin_atendente_estado_resultado" value="<?=$admin_atendente_estado?>" />
        <button type='button' name='apagar' class='btn btn-small btn-danger' title='Apagar o atendente' >Apagar</button>
        </td>
    </tr>

    <?php   }
    }
    ?>
    </tbody>
</table>

<?php

include "rodape.php";

?>
