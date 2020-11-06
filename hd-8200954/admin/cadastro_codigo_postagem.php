<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = "cadastro";
$title = "CADASTRO DE CÓDIGOS DE POSTAGENS X UF";

$posto_fabrica_ibge = (isset($_GET['posto_fabrica_ibge'])) ? $_GET['posto_fabrica_ibge'] : "";
if ($_POST['btn_acao'] == "excluir") {

    $posto_fabrica_ibge = trim($_POST["posto_fabrica_ibge"]);

    if (strlen($posto_fabrica_ibge) == 0) {
        $msg_erro .= "Não foi possível apagar o registro.<br />";
    }

    if (strlen($msg_erro) == 0) {

        $res = pg_query($con,"BEGIN TRANSACTION");

        $sqlFabricaIBGE = "DELETE FROM tbl_posto_fabrica_ibge  WHERE fabrica={$login_fabrica} AND posto_fabrica_ibge={$posto_fabrica_ibge}";

        $resFabricaIBGE = pg_query($con, $sqlFabricaIBGE);

        if (strlen($msg_erro) == 0 && !pg_last_error()) {
            $res = pg_query($con, "COMMIT TRANSACTION");
            $msg = "Apagado com Sucesso!";
            $estado             = "";
            $cidade             = "";
            $codigo_posto       = "";
            $descricao_posto    = "";
            $posto_fabrica_ibge = "";
        } else {
            $res = pg_query($con, "ROLLBACK TRANSACTION");
            $estado          = trim($_POST["estado"]);
            $cidade          = trim($_POST["cidade"]);
            $codigo_posto    = trim($_POST["codigo_posto"]);
            $descricao_posto = trim($_POST["descricao_posto"]);
            $posto_fabrica_ibge = trim($_POST["posto_fabrica_ibge"]);
            $msg_erro        = pg_last_error();
        }
    }
}


if ($_POST['btn_acao'] == "gravar") {
    $estado          = trim($_POST["estado"]);
    $cidade          = trim($_POST["cidade"]);
    $codigo_posto    = trim($_POST["codigo_posto"]);
    $descricao_posto = trim($_POST["descricao_posto"]);
    $posto_fabrica_ibge = trim($_POST["posto_fabrica_ibge"]);
    $cidade = (strlen($cidade) == 0) ? "null" : $cidade;

    if (strlen($codigo_posto) == 0 || strlen($descricao_posto) == 0) {
        $msg_erro .= "O campo Posto é obrigatório.<br />";
    }

    if (strlen($estado) == 0) {
        $msg_erro .= "O campo Estado é obrigatório.<br />";
    }

    $sqlPosto = "SELECT tbl_posto.posto
                      FROM tbl_posto
                      JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto 
                      WHERE tbl_posto_fabrica.codigo_posto = '{$codigo_posto}' 
                      AND tbl_posto_fabrica.fabrica = {$login_fabrica}";
    $resPosto = pg_query($con, $sqlPosto);
    $posto    = pg_fetch_result($resPosto, 0, 'posto');

    if (pg_num_rows($resPosto) == 0) {
        $msg_erro .= "Posto não encontrado.<br />";
    }

    $sqlIBGETipo = "SELECT tbl_posto_fabrica_ibge_tipo.posto_fabrica_ibge_tipo
                      FROM tbl_posto_fabrica_ibge_tipo
                      WHERE tbl_posto_fabrica_ibge_tipo.nome='Postagem' 
                      AND tbl_posto_fabrica_ibge_tipo.fabrica = {$login_fabrica}";
    $resIBGETipo = pg_query($con, $sqlIBGETipo);
    $posto_fabrica_ibge_tipo = pg_fetch_result($resIBGETipo, 0, 'posto_fabrica_ibge_tipo');

    if (strlen($msg_erro) == 0) {

        $res = pg_query($con,"BEGIN TRANSACTION");
        
        if (strlen($posto_fabrica_ibge) == 0) {
            $sqlValida = "SELECT tbl_posto_fabrica_ibge.cidade
                              FROM tbl_posto_fabrica_ibge
                              WHERE tbl_posto_fabrica_ibge.fabrica = {$login_fabrica}
                              AND tbl_posto_fabrica_ibge.cidade = {$cidade}
                              AND tbl_posto_fabrica_ibge.estado = '{$estado}'
                              ";
            $resValida = pg_query($con, $sqlValida);
            if (pg_num_rows($resValida) >= 1) {
                $msg_erro .= "Já possui Posto cadastrado nessa Cidade/UF.<br />";
            }

            $sqlFabricaIBGE = "INSERT INTO tbl_posto_fabrica_ibge (
                                    posto,
                                    fabrica,
                                    posto_fabrica_ibge_tipo,
                                    cidade,
                                    estado
                                ) VALUES (
                                    $posto,
                                    $login_fabrica,
                                    $posto_fabrica_ibge_tipo,
                                    $cidade,
                                    '$estado'
                                )";

            $resFabricaIBGE = pg_query($con, $sqlFabricaIBGE);
        } else {
            $sqlFabricaIBGE = "UPDATE tbl_posto_fabrica_ibge  SET 
                                    posto={$posto},
                                    posto_fabrica_ibge_tipo={$posto_fabrica_ibge_tipo},
                                    cidade={$cidade},
                                    estado='$estado'
                                WHERE fabrica={$login_fabrica} 
                                  AND posto_fabrica_ibge={$posto_fabrica_ibge}
                                ";

            $resFabricaIBGE = pg_query($con, $sqlFabricaIBGE);
        }
#echo nl2br($sqlFabricaIBGE); exit;
        if (strlen($msg_erro) == 0 && !pg_last_error()) {
            $res = pg_query($con, "COMMIT TRANSACTION");
            $msg = "Gravado com Sucesso!";
            $estado             = "";
            $cidade             = "";
            $codigo_posto       = "";
            $descricao_posto    = "";
            $posto_fabrica_ibge = "";
       } else {
            $res = pg_query($con, "ROLLBACK TRANSACTION");
            $msg_erro        = pg_last_error();
        }
    }
}


if (strlen($posto_fabrica_ibge) > 0) {
    $sqlIbge = "SELECT 
                        tbl_posto_fabrica_ibge.posto_fabrica_ibge,
                        tbl_posto_fabrica.codigo_posto,
                        tbl_posto.nome,
                        tbl_posto_fabrica_ibge.estado, 
                        tbl_posto_fabrica_ibge.cidade
                    FROM tbl_posto_fabrica_ibge 
                    JOIN tbl_posto_fabrica_ibge_tipo ON tbl_posto_fabrica_ibge_tipo.posto_fabrica_ibge_tipo=tbl_posto_fabrica_ibge.posto_fabrica_ibge_tipo  AND tbl_posto_fabrica_ibge_tipo.fabrica={$login_fabrica}
                    JOIN tbl_posto ON tbl_posto.posto=tbl_posto_fabrica_ibge.posto 
                    JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto=tbl_posto.posto AND tbl_posto_fabrica.fabrica={$login_fabrica}
                    WHERE 
                        tbl_posto_fabrica_ibge_tipo.nome='Postagem' 
                    AND 
                        tbl_posto_fabrica_ibge.fabrica={$login_fabrica}
                    AND 
                        tbl_posto_fabrica_ibge.posto_fabrica_ibge = {$posto_fabrica_ibge}";

    $resIbge = pg_query($con, $sqlIbge);
    if (pg_num_rows($resIbge)) {
        $estado         = pg_result ($resIbge, 0, estado);
        $cidade         = pg_result ($resIbge, 0, cidade);
        $codigo_posto   = pg_result ($resIbge, 0, codigo_posto);
        $descricao_posto= pg_result ($resIbge, 0, nome);
    } else {
        $estado          = trim($_POST["estado"]);
        $cidade          = trim($_POST["cidade"]);
        $codigo_posto    = trim($_POST["codigo_posto"]);
        $descricao_posto = trim($_POST["descricao_posto"]);
    }

}

if ($_POST['ajax_carrega_cidade'] == true) {
    $estado  = $_POST['estado'];

    if (strlen($estado) == 0) {
        exit(json_encode(array('erro' => true, 'msg' => 'Escolha um Estado')));
    }

    $sql = "SELECT
                tbl_cidade.cidade,
                tbl_cidade.nome
             FROM tbl_cidade
            WHERE tbl_cidade.estado='{$estado}' 
            AND tbl_cidade.nome IS NOT NULL
            ORDER BY tbl_cidade.nome ASC";

    $res = pg_query($con, $sql);
    $retorno .= "<option value=''>Selecione uma Cidade.</option>";
    if (pg_num_rows($res) > 0) {
        for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
            $cidade      = pg_result ($res, $i, cidade);
            $cidade_nome = pg_result ($res, $i, nome);
            $retorno .= "<option value='$cidade'>$cidade_nome</option>";
        }
    } else {    
        $retorno .= "<option value=''>Nenhuma cidade encontrada.</option>";
    }
    echo $retorno;
    exit;
}

include "cabecalho_new.php";

$plugins = array(
                "multiselect",
                "autocomplete",
                "select2",
                "shadowbox",
                "mask",
                "dataTable"
                );
include ("plugin_loader.php");

?>
<script language="JavaScript">
    $(function (){ 
        $.dataTableLoad();
        $.autocompleteLoad(Array("posto"));

        Shadowbox.init();
        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });
        $("select").select2();

        $("select[name=estado]").on("click, change", function() {
            var  estado = $(this).val();
            $.ajax({
                type: "POST",
                url:  "<?php echo $_SERVER['PHP_SELF'];?>",
                data:{
                    ajax_carrega_cidade: true,
                    estado: estado
                },
                complete: function(data){
                    $("select[name=cidade]").removeAttr('disabled');
                    $("select[id=cidade]").html(data.responseText);
               }
            });
        });
    });
    function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
        $("#descricao_posto").val(retorno.nome);
    }
</script>

<?php if (strlen($msg_erro) > 0) { ?>
<div class="alert alert-error">
    <h4><?php echo $msg_erro;?></h4>
</div>
<?php } ?>

<?php if (strlen($msg) > 0) {?>
<div class="alert alert-success">
    <h4><?php echo $msg;?></h4>
</div>
<?php } ?>

<br/>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form class="form-search form-inline tc_formulario" method="post" action="<?php echo $PHP_SELF;?>">
    <input type="hidden" name="posto_fabrica_ibge" value="<?php echo $posto_fabrica_ibge;?>">
    <div class="titulo_tabela">Cadastro de Postagem X UF</div><br/>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span3">
            <div class="control-group <?php echo (strpos($msg_erro, "Estado") !== false) ? "error" : ""; ?>">
                <label class="control-label" for="estado">Estado</label>
                <div class="controls controls-row">
                    <h5 class="asteristico">*</h5>
                    <select name="estado" class="span12" id="estado">
                        <option value="">Escolha um Estado</option>
                        <?php foreach ($array_estados() as $keyUF => $valueUF) {?>
                        <option value="<?php echo $keyUF;?>" <?php if ($keyUF == $estado) {echo 'selected=""';}?>><?php echo $valueUF;?></option>
                        <?php }?>
                    </select>
                </div>
            </div>
        </div>
        <div class="span5">
            <div class="control-group">
                <label class="control-label" for="cidade">Cidade</label>
                <select name="cidade" class="span12 cidade" id="cidade">
                    <?php
                        if (strlen($msg_erro) > 0 || strlen($msg) > 0 || strlen($posto_fabrica_ibge) > 0) {
                            $sql = "SELECT
                                        tbl_cidade.cidade,
                                        tbl_cidade.nome
                                     FROM tbl_cidade
                                    WHERE tbl_cidade.estado='{$estado}' 
                                    AND tbl_cidade.nome IS NOT NULL
                                    ORDER BY tbl_cidade.nome ASC";
                            $res = pg_query($con, $sql);

			    if (pg_num_rows($res) > 0) {
				    echo "<option value=''></option>";
                                for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
                                    $id_cidade   = pg_result ($res, $i, cidade);
                                    $nome_cidade = pg_result ($res, $i, nome);
                                    $selected = ($id_cidade == $cidade) ? "selected='selected'" : "";
                                    $retorno .= "<option value='$id_cidade' {$selected }>$nome_cidade</option>";
                                }
                                echo $retorno;
                            } else {    
                                echo "<option value=''>Nenhuma cidade encontrada.</option>";
                            }
                        } else {
                            echo "<option value=''>Escolha uma Cidade</option>";
                        }
                    ?>
                </select>
            </div>
        </div>
        <div class="span2"></div>
    </div>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span3">
            <div class="control-group <?php echo (strpos($msg_erro, "Posto") !== false) ? "error" : ""; ?>">
                <label class="control-label" for="codigo_posto">Código Posto</label>
                <div class="controls controls-row">
                    <h5 class="asteristico">*</h5>
                    <div class="span10 input-append">
                        <input type="text" id="codigo_posto" value="<?php echo $codigo_posto;?>" name="codigo_posto" class="span12">
                        <span class="add-on" rel="lupa"><i class="icon-search"></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span5">
            <div class="control-group <?php echo (strpos($msg_erro, "Posto") !== false) ? "error" : ""; ?>">
                <label class="control-label" for="descricao_posto">Posto</label>
                <div class="controls controls-row">
                    <div class="span12 input-append">
                        <input type="text" id="descricao_posto" value="<?php echo $descricao_posto;?>" name="descricao_posto" class="span12" >
                        <span class="add-on" rel="lupa"><i class="icon-search"></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span2"></div>
    </div><br/>
    <div class="row-fluid">
        <div class="span4"></div>
        <div class="span4 tac">
            <button type="button" class="btn"  onclick="submitForm($(this).parents('form'),'gravar');" alt="Gravar formulário" >Gravar</button>
            <?php if (strlen($posto_fabrica_ibge) > 0 && strlen($msg) == 0) {?>
            <button type="button" class="btn btn-danger"  onclick="submitForm($(this).parents('form'),'excluir');" alt="Apagar">Apagar</button>
            <?php }?>
            <input type="hidden" id="btn_click" name="btn_acao" value="" />     
        </div>
        <div class="span4"></div>
    </div><br/>
</form>
<table class="table table-striped table-bordered table-hover table-fixed">
    <thead>
        <tr class="titulo_tabela">
            <th colspan="4">Relação das Postagens Cadastradas</th>
        </tr>
        <tr class="titulo_coluna">
            <th>Estado</th>
            <th>Postos</th>
            <th>Cidades</th>
        </tr>
    </thead>
    <tbody>
        <?php 
            $sql = "SELECT 
                        tbl_posto_fabrica_ibge.posto_fabrica_ibge,
                        tbl_posto_fabrica.codigo_posto,
                        tbl_posto.nome,
                        tbl_posto_fabrica_ibge.estado, 
                        tbl_cidade.nome AS nome_cidade 
                    FROM tbl_posto_fabrica_ibge 
                    JOIN tbl_posto_fabrica_ibge_tipo ON tbl_posto_fabrica_ibge_tipo.posto_fabrica_ibge_tipo=tbl_posto_fabrica_ibge.posto_fabrica_ibge_tipo  AND tbl_posto_fabrica_ibge_tipo.fabrica={$login_fabrica}
                    JOIN tbl_posto ON tbl_posto.posto=tbl_posto_fabrica_ibge.posto 
                    JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto=tbl_posto.posto and tbl_posto_fabrica.fabrica={$login_fabrica}

                    LEFT JOIN tbl_cidade ON tbl_cidade.cidade=tbl_posto_fabrica_ibge.cidade 
                    WHERE 
                        tbl_posto_fabrica_ibge_tipo.nome='Postagem' 
                    AND 
                        tbl_posto_fabrica_ibge.fabrica={$login_fabrica}";
            $res = pg_query($con,$sql);
            for ($x = 0 ; $x < pg_numrows($res); $x++) {
                $posto       = trim(pg_result($res, $x, codigo_posto)) .' - '.trim(pg_result($res, $x, nome));
                $estado      = trim(pg_result($res, $x, estado));
                $nome_cidade = trim(pg_result($res, $x, nome_cidade));
                $xposto_fabrica_ibge = trim(pg_result($res, $x, posto_fabrica_ibge));
        ?>
        <tr >
            <td class="tac"><?php echo $estado;?></td>
            <td><a href="./cadastro_codigo_postagem.php?posto_fabrica_ibge=<?php echo $xposto_fabrica_ibge;?>"><?php echo $posto;?></a></td>
            <td class="tac"><a href="./cadastro_codigo_postagem.php?posto_fabrica_ibge=<?php echo $xposto_fabrica_ibge;?>"><?php echo $nome_cidade;?></a></td>
        </tr>
        <?php } ?>
    </tbody>
</table>
<?php include "rodape.php";?>
