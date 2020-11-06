<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
 if (in_array($login_fabrica, [189])) {
    $label_origem           = "Depto. Gerador da RRC";
    $label_classificacao    = "Registro Ref. a";
    $label_subclassificacao = "Espec. de Ref. de Registro";
    $label_providencia      = "Ação";
} else {
    $label_origem           = "Origem";
    $label_classificacao    = "Classificação";
    $label_subclassificacao = "Subclassificação";
    $label_providencia      = "Providência";
}

if ($_POST["btn_acao"] == "submit" && !$_GET["listar"]) {

    $tipo_relacao           = $_POST['tipo_relacao'];
    $hd_chamado_origem      = $_POST['hd_chamado_origem'];
    $hd_classificacao       = $_POST['hd_classificacao'];
    $hd_classificacao_2     = $_POST['hd_classificacao_2'];
    $hd_subclassificacao_2  = $_POST['hd_subclassificacao_2'];
    $hd_subclassificacao    = $_POST['hd_subclassificacao'];
    $hd_motivo_ligacao      = $_POST['hd_motivo_ligacao'];


    if (empty($tipo_relacao)) {
        $msg_erro["msg"][]    = "Selecione um Tipo de Relação";
        $msg_erro["campos"][] = "tipo_relacao";
    }

    if (count($msg_erro) == 0) {

        if ($tipo_relacao == 1) {
            if (empty($hd_chamado_origem)) {
                $msg_erro["msg"][]    = "Selecione um $label_origem ";
                $msg_erro["campos"][] = "hd_chamado_origem";
            }

            if (empty($hd_classificacao)) {
                $msg_erro["msg"][]    = "Selecione um $label_classificacao ";
                $msg_erro["campos"][] = "hd_classificacao";
            }
        } elseif ($tipo_relacao == 2) {
            if (empty($hd_classificacao_2)) {
                $msg_erro["msg"][]    = "Selecione um $label_classificacao";
                $msg_erro["campos"][] = "hd_classificacao_2";
            }

            if (empty($hd_subclassificacao_2)) {
                $msg_erro["msg"][]    = "Selecione um $label_subclassificacao";
                $msg_erro["campos"][] = "hd_subclassificacao_2";
            }
        } elseif ($tipo_relacao == 3) {
            if (empty($hd_subclassificacao)) {
                $msg_erro["msg"][]    = "Selecione um $label_subclassificacao";
                $msg_erro["campos"][] = "hd_subclassificacao";
            }

            if (empty($hd_motivo_ligacao)) {
                $msg_erro["msg"][]    = "Selecione um $label_providencia";
                $msg_erro["campos"][] = "hd_motivo_ligacao";
            }
        }

    }

    if (count($msg_erro) == 0) {

        $res = pg_query($con,"BEGIN");

        if ($tipo_relacao == 1) {
            $campo1 = "hd_chamado_origem";
            $campo2 = "hd_classificacao";
            $valor1 = $hd_chamado_origem;
            $valor2 = $hd_classificacao;

        } elseif ($tipo_relacao == 2) {
            $campo1 = "hd_classificacao";
            $campo2 = "hd_subclassificacao";
            $valor1 = $hd_classificacao_2;
            $valor2 = $hd_subclassificacao_2;
        } elseif ($tipo_relacao == 3) {
            $campo1 = "hd_subclassificacao";
            $campo2 = "hd_motivo_ligacao";
            $valor1 = $hd_subclassificacao;
            $valor2 = $hd_motivo_ligacao;
        }

        if (!validaDuplicidade($campo1, $campo2,$valor1, $valor2)) {

            $retorno = gravaFluxo($campo1, $campo2,$valor1, $valor2);
            if ($retorno["erro"]) {
                $msg_erro["msg"][] = $retorno["msg"];
            } else {
               $msg_sucesso["msg"][] = 'Relacionamento efetuado com sucesso!';
            }
        } else {
            $msg_erro["msg"][] = "Já existe um relacionamento entre esses campos";
        }

        if (count($msg_erro) > 0) {
            $res = pg_query($con,"ROLLBACK");
        } else {
            $res = pg_query($con,"COMMIT");
            echo $url_redir;
        }
    }
}

function gravaFluxo($campo1, $campo2,$valor1, $valor2){
    global $con, $login_fabrica;
    
    $sql = "INSERT INTO tbl_hd_fluxo_atendimento($campo1,$campo2,fabrica) VALUES(".$valor1.", ".$valor2.",".$login_fabrica.")";
    $res = pg_query($con,$sql);

    if (pg_last_error()) {
        return ["erro" => true, "msg" => "Erro ao relacionar"];
    }
    return ["erro" => false];
}

function validaDuplicidade($campo1, $campo2,$valor1, $valor2){
    global $con, $login_fabrica;
    
    $sql = "SELECT $campo1,$campo2 
              FROM tbl_hd_fluxo_atendimento 
             WHERE $campo1 = ".$valor1." 
               AND $campo2 = ".$valor2." 
               AND fabrica = ".$login_fabrica;
    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {
        return true;
    }
    return false;
}

$layout_menu = "cadastro";
$title = "Fluxo de Atendimento - Relacionamentos";
include 'cabecalho_new.php';

$plugins = array(
    "shadowbox",
    "dataTable",
    "select2",
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
        $(".select2").select2({width: "100%"});
        $("select[name=tipo_relacao]").change(function(){
            var tipo_relacao = $("select[name=tipo_relacao] option:selected").val()

            if (tipo_relacao == 1) {
                $(".mostra_1").show();
                $(".mostra_2").hide();
                $(".mostra_3").hide();
            } else if (tipo_relacao == 2) {
                $(".mostra_2").show();
                $(".mostra_1").hide();
                $(".mostra_3").hide();
            } else if (tipo_relacao == 3) {
                $(".mostra_3").show();
                $(".mostra_1").hide();
                $(".mostra_2").hide();
            } else {
                $(".mostra_1").hide();
                $(".mostra_2").hide();
                $(".mostra_3").hide();
            }
            
        });
    });
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
        <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
    </div>
 <?php 
       
        if ($_GET["listar"] != "ok") {
    ?>
    <form name='frm_relatorio' METHOD='POST' enctype="multipart/form-data" ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
        <?php if ($tipo_acao == "edit") {?>
        <input type="hidden" name="tipo_acao" value="edit">
        <input type="hidden" name="hd_fluxo_atendimento" value="<?php echo $hd_fluxo_atendimento;?>">
        <?php } else {?> 
        <input type="hidden" name="tipo_acao" value="add">
        <?php }?> 
        <div class='titulo_tabela '>Cadastro</div>
        <br/>
       
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span8'>
                <div class='control-group  <?=(in_array("tipo_relacao", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Tipo de Relação</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <select name="tipo_relacao" class='span12' id="tipo_relacao">
                                <option value=""> </option>
                                <option value="1" <?php echo ($tipo_relacao == 1) ? "selected": "";?>> <?php echo $label_origem;?> x <?php echo $label_classificacao;?> </option>
                                <option value="2" <?php echo ($tipo_relacao == 2) ? "selected": "";?>> <?php echo $label_classificacao;?> x <?php echo $label_subclassificacao;?> </option>
                                <option value="3" <?php echo ($tipo_relacao == 3) ? "selected": "";?>> <?php echo $label_subclassificacao;?> x <?php echo $label_providencia;?> </option>
                                
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class='row-fluid mostra_1' style="display: <?php echo ($tipo_relacao == 1) ? "block": "none";?>;">
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("hd_chamado_origem", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'><?php echo $label_origem;?></label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <select name="hd_chamado_origem" class='span12 select2' id="hd_chamado_origem">
                                <option value="" selected="selected">  </option>
                                <?php 
                                    $sql = "SELECT * FROM tbl_hd_chamado_origem WHERE fabrica = {$login_fabrica} ORDER BY descricao ASC";
                                    $res = pg_query($con, $sql);
                                    if (pg_num_rows($res) > 0) {
                                        foreach (pg_fetch_all($res) as $key => $rows) {
                                ?>
                                <option value="<?php echo $rows['hd_chamado_origem'];?>" <?php echo $selected;?>><?php echo $rows['descricao'];?></option>
                                <?php  
                                        }
                                    }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group <?=(in_array("hd_classificacao", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'><?php echo $label_classificacao;?></label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <select name="hd_classificacao" class='span12 select2' id="hd_classificacao">
                                <option value="" selected="selected"> </option>
                                <?php 
                                    $sql = "SELECT * FROM tbl_hd_classificacao WHERE fabrica = {$login_fabrica} ORDER BY descricao ASC";
                                    $res = pg_query($con, $sql);
                                    if (pg_num_rows($res) > 0) {
                                        foreach (pg_fetch_all($res) as $key => $rows) {
                                ?>
                                <option value="<?php echo $rows['hd_classificacao'];?>" <?php echo $selected;?>><?php echo $rows['descricao'];?></option>
                                <?php  
                                        }
                                    }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class='row-fluid mostra_2' style="display: <?php echo ($tipo_relacao == 2) ? "block": "none";?>;">
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("hd_classificacao_2", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'><?php echo $label_classificacao;?></label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <select name="hd_classificacao_2" class='span12 select2' id="hd_classificacao_2">
                                <option value="" selected="selected"> </option>
                                <?php 
                                    $sql = "SELECT * FROM tbl_hd_classificacao WHERE fabrica = {$login_fabrica} ORDER BY descricao ASC";
                                    $res = pg_query($con, $sql);
                                    if (pg_num_rows($res) > 0) {
                                        foreach (pg_fetch_all($res) as $key => $rows) {
                                ?>
                                <option value="<?php echo $rows['hd_classificacao'];?>" <?php echo $selected;?>><?php echo $rows['descricao'];?></option>
                                <?php  
                                        }
                                    }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group <?=(in_array("hd_subclassificacao_2", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'><?php echo $label_subclassificacao;?></label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <select name="hd_subclassificacao_2" class='span12 select2' id="hd_subclassificacao_2">
                                <option value="" selected="selected">  </option>
                                <?php 
                                    $sql = "SELECT * FROM tbl_hd_subclassificacao WHERE fabrica = {$login_fabrica} ORDER BY descricao ASC";
                                    $res = pg_query($con, $sql);
                                    if (pg_num_rows($res) > 0) {
                                        foreach (pg_fetch_all($res) as $key => $rows) {
                                ?>
                                <option value="<?php echo $rows['hd_subclassificacao'];?>" <?php echo $selected;?>><?php echo $rows['descricao'];?></option>
                                <?php  
                                        }
                                    }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <div class='row-fluid mostra_3' style="display: <?php echo ($tipo_relacao == 3) ? "block": "none";?>;">
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group  <?=(in_array("hd_subclassificacao", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'><?php echo $label_subclassificacao;?></label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <select name="hd_subclassificacao" class='span12 select2' id="hd_subclassificacao">
                                <option value="" selected="selected">  </option>
                                <?php 
                                    $sql = "SELECT * FROM tbl_hd_subclassificacao WHERE fabrica = {$login_fabrica} ORDER BY descricao ASC";
                                    $res = pg_query($con, $sql);
                                    if (pg_num_rows($res) > 0) {
                                        foreach (pg_fetch_all($res) as $key => $rows) {
                                ?>
                                <option value="<?php echo $rows['hd_subclassificacao'];?>" <?php echo $selected;?>><?php echo $rows['descricao'];?></option>
                                <?php  
                                        }
                                    }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group  <?=(in_array("hd_motivo_ligacao", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'><?php echo $label_providencia;?></label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <select name="hd_motivo_ligacao" class='span12 select2' id="hd_motivo_ligacao">
                                <option value="" selected="selected">  </option>
                                <?php 
                                    $sql = "SELECT * FROM tbl_hd_motivo_ligacao WHERE fabrica = {$login_fabrica} ORDER BY descricao ASC";
                                    $res = pg_query($con, $sql);
                                    if (pg_num_rows($res) > 0) {
                                        foreach (pg_fetch_all($res) as $key => $rows) {
                                ?>
                                <option value="<?php echo $rows['hd_motivo_ligacao'];?>" <?php echo $selected;?>><?php echo $rows['descricao'];?></option>
                                <?php  
                                        }
                                    }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <p><br/>
                <button class='btn btn-success' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
                <input type='hidden' id="btn_click" name='btn_acao' value='' />
                <a href="fluxo_atendimento.php?listar=ok" class="btn"> Listagem de Fluxo</a>
            </p><br/>
    </form> <br />
    <?php
        if ($msg_erro["erro"]) {
            echo '<div class="alert alert-error"><h4>'.$msg_erro["msn"].'</h4></div>';
        } 
    } else {
    ?>

    <form name='frm_lista' METHOD='POST' enctype="multipart/form-data" ACTION='fluxo_atendimento.php?listar=ok' align='center' class='form-search form-inline tc_formulario' >
        <div class='titulo_tabela '>Paramentros de Pesquisas</div>
        <br/>
       
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span8'>
                <div class='control-group'>
                    <label class='control-label'>Filtrar por</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type="radio" <?php echo (isset($_POST["filtra_por"]) && $_POST["filtra_por"] == 1) ? 'checked' : '';?> name="filtra_por" value="1"> <?php echo $label_origem;?> x <?php echo $label_classificacao;?><br>
                            <input type="radio" <?php echo (isset($_POST["filtra_por"]) && $_POST["filtra_por"] == 2) ? 'checked' : '';?> name="filtra_por" value="2"> <?php echo $label_classificacao;?> x <?php echo $label_subclassificacao;?><br>
                            <input type="radio" <?php echo (isset($_POST["filtra_por"]) && $_POST["filtra_por"] == 3) ? 'checked' : '';?> name="filtra_por" value="3"> <?php echo $label_subclassificacao;?> x <?php echo $label_providencia;?>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
       
        <p><br/>
                <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
                <input type='hidden' id="btn_click" name='btn_acao' value='' />
                <a href="fluxo_atendimento.php" class="btn btn-success"> Novo Fluxo</a>
            </p><br/>
    </form> <br />


<?php if ($_POST["filtra_por"]) {?>
    <table class='table table-striped table-bordered table-hover table-fixed' id='tabela'>
        <thead>
            <tr class='titulo_coluna' >
                <?php if ($_POST["filtra_por"] == 1) {?>
                <th align="left"><?php echo $label_origem ;?></th>
                <th align="left"><?php echo $label_classificacao ;?></th>
                <?php } elseif ($_POST["filtra_por"] == 2) {?>
                <th align="left"><?php echo $label_classificacao ;?></th>
                <th align="left"><?php echo $label_subclassificacao ;?></th>
                <?php } else {?>
                <th align="left"><?php echo $label_subclassificacao ;?></th>
                <th align="left"><?php echo $label_providencia ;?></th>
                <?php }?>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php 
        if ($_POST["filtra_por"] == 1) {
            $sql = "SELECT tbl_hd_chamado_origem.descricao      AS NOME_ORIGEM,
                           tbl_hd_classificacao.descricao       AS NOME_CLASS,
                           tbl_hd_fluxo_atendimento.hd_fluxo_atendimento
                      FROM tbl_hd_fluxo_atendimento
                      JOIN tbl_hd_chamado_origem    ON  tbl_hd_fluxo_atendimento.hd_chamado_origem    = tbl_hd_chamado_origem.hd_chamado_origem       AND tbl_hd_chamado_origem.fabrica = {$login_fabrica}
                      JOIN tbl_hd_classificacao     ON  tbl_hd_fluxo_atendimento.hd_classificacao     = tbl_hd_classificacao.hd_classificacao        AND tbl_hd_classificacao.fabrica = {$login_fabrica}
                     WHERE tbl_hd_fluxo_atendimento.fabrica = {$login_fabrica}
                     ORDER BY tbl_hd_chamado_origem.descricao";
        } else if ($_POST["filtra_por"] == 2) {
            $sql = "SELECT tbl_hd_classificacao.descricao       AS NOME_CLASS,
                           tbl_hd_subclassificacao.descricao    AS NOME_SUBCLASS,
                           tbl_hd_fluxo_atendimento.hd_fluxo_atendimento
                      FROM tbl_hd_fluxo_atendimento
                      JOIN tbl_hd_classificacao     ON  tbl_hd_fluxo_atendimento.hd_classificacao     = tbl_hd_classificacao.hd_classificacao        AND tbl_hd_classificacao.fabrica = {$login_fabrica}
                      JOIN tbl_hd_subclassificacao  ON  tbl_hd_fluxo_atendimento.hd_subclassificacao  = tbl_hd_subclassificacao.hd_subclassificacao  AND tbl_hd_subclassificacao.fabrica = {$login_fabrica}
                     WHERE tbl_hd_fluxo_atendimento.fabrica = {$login_fabrica}
                     ORDER BY tbl_hd_classificacao.descricao";
        } else {
            $sql = "SELECT tbl_hd_subclassificacao.descricao    AS NOME_SUBCLASS,
                           tbl_hd_motivo_ligacao.descricao      AS NOME_PROVI,
                           tbl_hd_fluxo_atendimento.hd_fluxo_atendimento
                      FROM tbl_hd_fluxo_atendimento
                      JOIN tbl_hd_subclassificacao  ON  tbl_hd_fluxo_atendimento.hd_subclassificacao  = tbl_hd_subclassificacao.hd_subclassificacao  AND tbl_hd_subclassificacao.fabrica = {$login_fabrica}
                      JOIN tbl_hd_motivo_ligacao    ON  tbl_hd_fluxo_atendimento.hd_motivo_ligacao    = tbl_hd_motivo_ligacao.hd_motivo_ligacao      AND tbl_hd_motivo_ligacao.fabrica = {$login_fabrica}
                     WHERE tbl_hd_fluxo_atendimento.fabrica = {$login_fabrica}

                     ORDER BY tbl_hd_subclassificacao.descricao";
        }
        $res = pg_query($con, $sql);
        foreach (pg_fetch_all($res) as $k => $rows) {
        ?>
        <tr>
            <?php if ($_POST["filtra_por"] == 1) {?>
            <td class='tal'><?php echo $rows["nome_origem"];?></td>
            <td class='tal'><?php echo $rows["nome_class"];?></td>
            <?php } elseif ($_POST["filtra_por"] == 2) {?>
            <td class='tal'><?php echo $rows["nome_class"];?></td>
            <td class='tal'><?php echo $rows["nome_subclass"];?></td>
            <?php } else {?>
            <td class='tal'><?php echo $rows["nome_subclass"];?></td>
            <td class='tal'><?php echo $rows["nome_provi"];?></td>
            <?php }?>
            <td class='tac'>
                <a href="fluxo_atendimento.php?acao=edit&hd_fluxo_atendimento=<?php echo $rows["hd_fluxo_atendimento"];?>" class="btn btn-info btn-mini" title="Editar"><i class="icon-edit icon-white"></i></a>
            </td>
        </tr>
        <?php }?>
        </tbody>
    </table>
    <?php }?>
<?php }?>
</div> 
<?php include 'rodape.php';?>
