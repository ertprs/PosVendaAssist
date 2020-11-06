<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = 'cadastro';

include 'autentica_admin.php';
include 'funcoes.php';


if ($_POST['btn_acao']) {
    $produto_id    = $_POST['produto_id'];
    $ordem         = trim($_POST['ordem']);
    $comentario    = $_POST['comentario'];
    $ativo         = $_POST['ativo'];
    $laudo_tecnico = $_POST['laudo_tecnico'];
    $tipo_atendimento = $_POST['tipo_atendimento'];

    $arrComentario = json_decode(utf8_encode($comentario), true);
    $arrComentario = array_map_recursive("strip_tags", $arrComentario);

    $comentario = utf8_decode(json_encode($arrComentario));
    
    if (!strlen($ativo)) {
        $ativo = 'f';
    }

    if (empty($produto_id) && !in_array($login_fabrica, [190])) {
        $msg_erro['msg']['obg'] = 'Preencha os campos obrigatórios';
        $msg_erro['campos'][]   = 'produto';
    }

    if (empty($tipo_atendimento) && in_array($login_fabrica, [190])) {
        $msg_erro['msg']['obg'] = 'Preencha os campos obrigatórios';
        $msg_erro['campos'][]   = 'tipo_atendimento';
    }
    
    if (in_array($login_fabrica, array(175))) {
        if (!strlen($ordem)) {
            $msg_erro['msg']['obg'] = 'Preencha os campos obrigatórios';
            $msg_erro['campos'][]   = 'ordem';
        } else {
            $ordem = $ordem;
            
            if (strlen($laudo_tecnico) > 0) {
                $whereLaudoTecnico = "AND laudo_tecnico != {$laudo_tecnico}";
            }
            
            $sql = "
                SELECT laudo_tecnico
                FROM tbl_laudo_tecnico
                WHERE fabrica = {$login_fabrica}
                AND produto = {$produto_id}
                AND ordem_producao = '{$ordem}'
                {$whereLaudoTecnico}
            ";
            $res = pg_query($con, $sql);
            
            if (pg_num_rows($res) > 0) {
                $msg_erro['msg'][] = 'Já existe um laudo técnico cadastrado para produto e ordem informados';
                $msg_erro['campos'][] = 'ordem';
                $msg_erro['campos'][] = 'produto';
            }
        }
    }
    
    if (!strlen($comentario)) {
        $msg_erro['msg'][] = 'É necessário criar o formulário do laudo técnico';
    } else if (!count(json_decode(utf8_decode($comentario), true))) {
        $msg_erro['msg'][] = 'É necessário criar o formulário do laudo técnico';
    }
    
    if (!count($msg_erro['msg'])) {
        if (!strlen($laudo_tecnico)) {
            if (in_array($login_fabrica, array(175))) {
                $colunaOrdem = ", ordem_producao";
                $valorOrdem = ", '{$ordem}'";
            }
            $campoProdutoTipo = "produto,";
            $valorProdutoTipo = "{$produto_id}, ";

            if (in_array($login_fabrica, array(190))) {
                $campoProdutoTipo = " tipo_atendimento,";
                $valorProdutoTipo = " '{$tipo_atendimento}',";
            }

            $sql = "
                INSERT INTO tbl_laudo_tecnico
                (titulo,  comentario, {$campoProdutoTipo} afirmativa, admin, fabrica {$colunaOrdem})
                VALUES
                ('Laudo Técnico', '{$comentario}', {$valorProdutoTipo} '{$ativo}', {$login_admin}, {$login_fabrica} {$valorOrdem})
            ";
        } else {
            if (in_array($login_fabrica, array(175))) {
                $colunaOrdem = ", ordem_producao = '{$ordem}'";
            }
          
            $campoProdutoTipo = "produto={$produto_id},";

            if (in_array($login_fabrica, array(190))) {
                $campoProdutoTipo = "tipo_atendimento={$tipo_atendimento},";
            }

            $sql = "
                UPDATE tbl_laudo_tecnico SET
                    {$campoProdutoTipo}
                    afirmativa = '{$ativo}',
                    comentario = '{$comentario}', 
                    admin = {$login_admin}
                    {$colunaOrdem}
                WHERE fabrica = {$login_fabrica}
                AND laudo_tecnico = {$laudo_tecnico}
            ";
        }
        
        $res = pg_query($con, $sql);
        if (strlen(pg_last_error()) > 0) {
            $msg_erro['msg'][] = 'Erro ao gravar laudo técnico';
        } else {
            $msg_success = true;
            unset($_POST);
        }
    }
}

if (strlen($_GET["laudo_tecnico"])) {
    $laudo_tecnico = $_GET["laudo_tecnico"];
    $joinProdutoTipo = " INNER JOIN tbl_produto p ON p.produto = lt.produto AND p.fabrica_i = {$login_fabrica}";
    $campoProdutoTipo = ",
            p.referencia,
            p.descricao ";

    if (in_array($login_fabrica, array(190))) {
        $joinProdutoTipo = " INNER JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = lt.tipo_atendimento AND ta.fabrica = {$login_fabrica}";
        $campoProdutoTipo = ",ta.descricao AS tipo_atendimento_descricao";
    }

    $sql = "
        SELECT 
            lt.* {$campoProdutoTipo}
        FROM tbl_laudo_tecnico lt 
        {$joinProdutoTipo}
        WHERE lt.fabrica = {$login_fabrica} 
        AND lt.laudo_tecnico = {$laudo_tecnico}
    ";
    $res = pg_query($con, $sql);
    
    if (!pg_num_rows($res)) {
        $msg_erro["msg"][] = "Laudo Técnico não encontrado";
    } else {
        $_RESULT = array(
            "laudo_tecnico"      => $laudo_tecnico,
            "comentario"         => pg_fetch_result($res, 0, "comentario"),
            "produto_id"         => pg_fetch_result($res, 0, "produto"),
            "produto_referencia" => pg_fetch_result($res, 0, "referencia"),
            "produto_descricao"  => pg_fetch_result($res, 0, "descricao"),
            "ativo"              => pg_fetch_result($res, 0, 'afirmativa')
        );
        
        if (in_array($login_fabrica, array(175))) {
            $_RESULT["ordem"] = pg_fetch_result($res, 0, "ordem_producao");
        }
        if (in_array($login_fabrica, array(190))) {
            $_RESULT["tipo_atendimento_descricao"] = pg_fetch_result($res, 0, "tipo_atendimento_descricao");
            $_RESULT["tipo_atendimento"] = pg_fetch_result($res, 0, "tipo_atendimento");
        }
    }
}

$layout_menu = 'cadastro';
$title       = 'Cadastro de Laudo Técnico';
$title_page  = 'Cadastro';

include 'cabecalho_new.php';

$plugins = array(
    'shadowbox',
    'alphanumeric',
    'font_awesome',
    'dataTable'
);
include 'plugin_loader.php';
?>

<style>
    
iframe {
    border: 0px;
}

#iframe-visualizar-laudo {
    overflow: hidden;
}

#modal-editar-laudo {
    z-index: 99999;
}
    
</style>

<?php
if ($msg_success) {
?>
    <div class='alert alert-success' >
        <h4>Laudo Técnico, gravado com sucesso</h4>
    </div>
<?php
}

if (count($msg_erro['msg']) > 0) {
?>
    <div class='alert alert-error' >
        <h4><?=implode('<br />', $msg_erro['msg'])?></h4>
    </div>
<?php
}

$joinProdutoTipoCad  = " INNER JOIN tbl_produto p ON p.produto = lt.produto AND p.fabrica_i = {$login_fabrica}";
$campoProdutoTipoCad = ",p.referencia, p.descricao ";
$orderProdutoTipoCad = "p.referencia, lt.ordem_producao::numeric";

if (in_array($login_fabrica, array(190))) {
    $joinProdutoTipoCad  = " INNER JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = lt.tipo_atendimento AND ta.fabrica = {$login_fabrica}";
    $campoProdutoTipoCad = ",ta.descricao AS tipo_atendimento_descricao";
    $orderProdutoTipoCad = "lt.laudo_tecnico";
}


$sqlLaudoCadastrados = "
    SELECT lt.laudo_tecnico, 
           lt.ordem_producao, 
           lt.afirmativa, 
           lt.comentario
           {$campoProdutoTipoCad}
    FROM tbl_laudo_tecnico lt
    {$joinProdutoTipoCad }
    WHERE lt.fabrica = {$login_fabrica}
    ORDER BY {$orderProdutoTipoCad}
";
$resLaudoCadastrados = pg_query($con, $sqlLaudoCadastrados);
?>

<div class='row' >
    <b class='obrigatorio pull-right' >  * Campos obrigatórios </b>
</div>

<form method='POST' class='form-search form-inline tc_formulario' action='os_laudo_tecnico_cadastro.php' >
    <div class='titulo_tabela' ><?=$title_page?></div>
    <input type='hidden' name='laudo_tecnico' value='<?=getValue("laudo_tecnico")?>' />
    <input type='hidden' name='comentario' value='<?=getValue("comentario")?>' />
    <br />

    <div class='row-fluid' >
        <div class='span1' ></div>
        <?php
        if ($login_fabrica != 190) {
            if (strlen(getValue('produto_id')) > 0) {
                $produto_input_readonly     = 'readonly';
                $produto_span_rel           = 'trocar_produto';
                $produto_input_append_icon  = 'remove';
                $produto_input_append_title = 'title="Trocar produto"';
            } else {
                $produto_input_readonly     = '';
                $produto_span_rel           = 'lupa';
                $produto_input_append_icon  = 'search';
                $produto_input_append_title = '';
            }
        ?>
        <div class='span3' >
            <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : "" ?>' >
                <label class='control-label' for='produto_referencia' >Referência do Produto</label>
                <div class='controls controls-row'>
                    <div class='span10 input-append'>
                        <h5 class='asteristico'>*</h5>
                        <input id='produto_referencia' name='produto_referencia' class='span12' type='text' value='<?=getValue("produto_referencia")?>' <?=$produto_input_readonly?> />
                        <span class='add-on' rel='<?=$produto_span_rel?>' >
                            <i class='icon-<?=$produto_input_append_icon?>' <?=$produto_input_append_title?> ></i>
                        </span>
                        <input type='hidden' name='lupa_config' tipo='produto' parametro='referencia' />
                        <input type='hidden' id='produto_id' name='produto_id' value='<?=getValue("produto_id")?>' />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4' >
            <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : "" ?>' >
                <label class='control-label' for='produto_descricao' >Descrição do Produto</label>
                <div class='controls controls-row' >
                    <div class='span10 input-append' >
                        <h5 class='asteristico'>*</h5>
                        <input id='produto_descricao' name='produto_descricao' class='span12' type='text' value='<?=getValue("produto_descricao")?>' <?=$produto_input_readonly?> />
                        <span class='add-on' rel='<?=$produto_span_rel?>' >
                            <i class='icon-<?=$produto_input_append_icon?>' <?=$produto_input_append_title?> ></i>
                        </span>
                        <input type='hidden' name='lupa_config' tipo='produto' parametro='descricao' />
                    </div>
                </div>
            </div>
        </div>
        <?php } else {?>
            <div class='span4' >
            <div class='control-group <?=(in_array("tipo_atendimento", $msg_erro["campos"])) ? "error" : "" ?>' >
                <label class='control-label' for='tipo_atendimento' >Tipo de Atendimento</label>
                <div class='controls controls-row' >
                    <div class='span10 input-append' >
                        <h5 class='asteristico'>*</h5>
                        <select name="tipo_atendimento" id="tipo_atendimento">
                            <option value="">Escolha ...</option>
                            <?php 

                                $sql = "SELECT tipo_atendimento, descricao
                                          FROM tbl_tipo_atendimento
                                         WHERE fabrica = {$login_fabrica}
                                           AND ativo IS TRUE
                                      ORDER BY descricao";
                                $res = pg_query($con, $sql);
                                if (pg_num_rows($res) > 0) {

                                    $tipos_atendimento = pg_fetch_all($res);
                                    foreach ($tipos_atendimento as $key => $row) {
                                        $selectec = (getValue("tipo_atendimento") == $row["tipo_atendimento"]) ? 'selected' : '';
                                        echo '<option '.$selectec.' value="'.$row["tipo_atendimento"].'">'.$row["descricao"].'</option>';
                                    }
                                }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <?php }?>
        <div class='span1' >
            <div class='control-group' >
                <label class='control-label' for='ativo' >Ativo</label>
                <div class='controls controls-row' >
                    <div class='span12' >
                        <label class='checkbox' >
                            <input type='checkbox' id='ativo' name='ativo' value='t' <?=(getValue("ativo") == "t" || (!$_POST && !$_RESULT)) ? "checked" : ""?> />
                        </label>
                    </div>
                </div>
            </div>
        </div>
    
        <?php
        if (in_array($login_fabrica, array(175))) {
        ?>
            <div class='span2' >
                <div class='control-group <?=(in_array("ordem", $msg_erro["campos"])) ? "error" : "" ?>' >
                    <label class='control-label' for='ordem' >Ordem de Produção</label>
                    <div class='controls controls-row' >
                        <div class='span12' >
                            <h5 class='asteristico'>*</h5>
                            <input type='text' id='ordem' name='ordem' class='span12' value='<?=getValue("ordem")?>' />
                        </div>
                    </div>
                </div>
            </div>
        <?php
        }
        ?>
    </div>

    <?php
    if (pg_num_rows($resLaudoCadastrados) > 0) {
    ?>
        <br />
        <div class='row-fluid' >
            <div class='span1' ></div>
            <div class='span5' >
                <div class='control-group' >
                    <div class='controls controls-row' >
                        <button type="button" id="btn-editar-laudo" class="btn btn-block btn-info"><i class="fa fa-pencil-alt" ></i> Editar Laudo Técnico</button>
                    </div>
                </div>
            </div>
            <div class='span5' >
                <div class='control-group' >
                    <div class='controls controls-row' >
                        <button type="button" id="btn-copiar-laudo" class="btn btn-block btn-warning"><i class="fa fa-copy" ></i> Copiar Laudo Técnico</button>
                    </div>
                </div>
            </div>
        </div>
    <?php
    } else {
    ?>
        <br />
        <div class='row-fluid' >
            <div class='span1' ></div>
            <div class='span10' >
                <div class='control-group' >
                    <div class='controls controls-row' >
                        <button type="button" id="btn-editar-laudo" class="btn btn-block btn-info"><i class="fa fa-pencil-alt" ></i> Editar Laudo Técnico</button>
                    </div>
                </div>
            </div>
        </div>
    <?php
    }
    ?>
    
    <div class='row-fluid' >
        <div class='span1' ></div>
        <div class='span10' >
            <div class="panel panel-info">
                  <div class="panel-heading">
                        <h5 class="panel-title tac"><i class="fa fa-clipboard-check" ></i> Visualização do Laudo Técnico</h5>
                  </div>
                  <div class="panel-body">
                    <iframe src="os_laudo_tecnico.php" id="iframe-visualizar-laudo" frameborder="0" style="width: 100%; height: 100%;" ></iframe>
                  </div>
            </div>
        </div>
    </div>

    <p>
        <br />
        <input type='hidden' id='btn_click' name='btn_acao' />
        <button class='btn' type='button' onclick='submitForm($(this).parents("form"));' ><i class='fa fa-save' ></i> Gravar</button>
        <?php
        if (strlen(getValue('laudo_tecnico')) > 0) {
        ?>
            <button class='btn btn-warning' type='button' onclick='window.location = "<?=$_SERVER["PHP_SELF"]?>";' >Limpar</button>
        <?php
        }
        ?>
    </p>
    <br />
</form>

<?php
if (pg_num_rows($resLaudoCadastrados) > 0) {
?>
    <div class="modal hide fade" id="modal-copiar-laudo">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title">Copiar Laudo Técnico</h4>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <?php if (in_array($login_fabrica, array(190))) {?>
                                <th class="tal">Tipo de Atendimento</th>
                                <?php } else {?>
                                <th>Produto Referência</th>
                                <th>Produto Descrição</th>
                                <?php }?>
                                <th>Ativo</th>
                                <?php
                                if (in_array($login_fabrica, array(175))) {
                                ?>
                                    <th>Ordem de Produção</th>
                                <?php
                                }
                                ?>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            while ($row = pg_fetch_object($resLaudoCadastrados)) {
                                if ($row->afirmativa == 't') {
                                    $ativo = "<i class='fa fa-check text-success' ></i>";
                                } else {
                                    $ativo = "<i class='fa fa-ban text-error' ></i>";
                                }
                                ?>
                                <tr>
                                    <?php  if (in_array($login_fabrica, array(190))) {?>
                                    <td class="tal"><?=$row->tipo_atendimento_descricao?></td>
                                    <?php  } else {?>
                                    <td><?=$row->produto_referencia?></td>
                                    <td><?=$row->produto_descricao?></td>
                                    <?php  }?>
                                    <td class='tac' ><?=$ativo?></td>
                                    <?php
                                    if (in_array($login_fabrica, array(175))) {
                                    ?>
                                        <td><?=$row->ordem_producao?></td>
                                    <?php
                                    }
                                    ?>
                                    <td class='tac' nowrap >
                                        <button type="button" class="btn btn-primary btn-copiar" data-id="<?=$row->laudo_tecnico?>" title='Copiar' ><i class='fa fa-copy' ></i></button>
                                        <input type='hidden' name='laudo_tecnico' value='<?=$row->comentario?>' />
                                    </td>
                                </tr>
                            <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <table id='table-result' class='table table-striped table-bordered table-hover table-fixed' >
        <thead>
            <tr class='titulo_coluna' >
                <?php
                switch ($login_fabrica) {
                    case 175:
                        $colspan = 5;
                        break;
                    
                    default:
                        $colspan = 4;
                        break;
                }
                ?>
                <th colspan='<?=$colspan?>' >Laudos Técnicos gravados</th>
            </tr>
            <tr class='titulo_coluna' >
                <?php if (in_array($login_fabrica, array(190))) {?>
                <th class="tal">Tipo de Atendimento</th>
                <?php } else {?>
                <th>Produto Referência</th>
                <th>Produto Descrição</th>
                <?php }?>
                <th>Ativo</th>
                <?php
                if (in_array($login_fabrica, array(175))) {
                ?>
                    <th>Ordem de Produção</th>
                <?php
                }
                ?>
                <th>Ação</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $i = 0;
            
            pg_result_seek($resLaudoCadastrados, 0);
            while ($row = pg_fetch_object($resLaudoCadastrados)) {
                if ($row->afirmativa == 't') {
                    $ativo = "<i class='fa fa-check text-success' ></i>";
                } else {
                    $ativo = "<i class='fa fa-ban text-error' ></i>";
                }
                ?>
                <tr data-index='tr-<?=$i?>' >
                    <?php  if (in_array($login_fabrica, array(190))) {?>
                    <td class="tal"><?=$row->tipo_atendimento_descricao?></td>
                    <?php  } else {?>
                    <td><?=$row->referencia?></td>
                    <td><?=$row->descricao?></td>
                    <?php  }?>
                    <td class='tac' ><?=$ativo?></td>
                    <?php
                    if (in_array($login_fabrica, array(175))) {
                    ?>
                        <td><?=$row->ordem_producao?></td>
                    <?php
                    }
                    ?>
                    <td class='tac' nowrap >
                        <button type="button" class="btn btn-primary btn-editar" data-id="<?=$row->laudo_tecnico?>" ><i class='fa fa-edit' ></i> Editar</button>
                    </td>
                </tr>
                <?php
                $i++;
            }
            ?>
        </tbody>
    </table>
<?php
}
?>

<div class="modal hide fade" id="modal-editar-laudo">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Edição do Laudo Técnico</h4>
            </div>
            <div class="modal-body">
                <iframe src="os_laudo_tecnico.php" id="iframe-editar-laudo" frameborder="0" style="width: 100%; height: 100%;" ></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success btn-salvar-alteracoes-laudo"><i class="fa fa-save"></i> Salvar Alterações</button>
            </div>
        </div>
    </div>
</div>

<script>

var iframeEditar     = document.querySelector('#iframe-editar-laudo');
var iframeVisualizar = document.querySelector('#iframe-visualizar-laudo');

$(function(){
    Shadowbox.init();
 
    $(document).on('click', 'span[rel=lupa]', function() {
        $.lupa($(this));
    }); 
});
 
$(document).on('click', 'span[rel=trocar_produto]', function() {
    $('#produto_id, #produto_referencia, #produto_descricao').val('');

    $('#produto_referencia, #produto_descricao')
    .prop({ readonly: false })
    .next('span[rel=trocar_produto]')
    .attr({ rel: 'lupa' })
    .find('i')
    .removeClass('icon-remove')
    .addClass('icon-search')
    .removeAttr('title');
});

window.retorna_produto = function(retorno) {
    $('#produto_id').val(retorno.produto);
    $('#produto_referencia').val(retorno.referencia);
    $('#produto_descricao').val(retorno.descricao);

    $('#produto_referencia, #produto_descricao')
    .prop({ readonly: true })
    .next('span[rel=lupa]')
    .attr({ rel: 'trocar_produto' })
    .find('i')
    .removeClass('icon-search')
    .addClass('icon-remove')
    .attr({ title: 'Trocar Produto' });
}

$('#ordem').numeric();

$('#btn-editar-laudo').on('click', function() {
    $('#modal-editar-laudo').addClass('modal-full-screen').modal('show');
    let comentario = $('input[name=comentario]').val();
    
    if (comentario.length > 0) {
        iframeEditar.contentWindow.postMessage('setFbData|'+comentario, '*');
    } else {
        iframeEditar.contentWindow.postMessage('clearFbData', '*');
    }
});

$('.btn-salvar-alteracoes-laudo').on('click', function() {
    iframeEditar.contentWindow.postMessage('getFbData', '*');
});

window.addEventListener('message', function(e) {
    [action, data] = e.data.split("|");

    if (action == 'getFbData') {
        let dataJSON = JSON.parse(data);
        
        if (typeof dataJSON === "object" && dataJSON.length > 0) {
            let errors = false;
            dataJSON.forEach(function (e, i) {
                if (e.type === "checkbox-group") {
                    e.values.forEach(function (t, l) {
                        if (t.label.length === 0)
                            errors = true;
                    })
                }
            });

            if (errors) return alert("Existem caixas de seleção com opções sem rótulos.");
        }

        $('input[name=comentario]').val(data);
        $('#modal-editar-laudo').modal('hide');
        e.source.postMessage('clearFbData', '*');
        
        iframeVisualizar.contentWindow.postMessage('setFbData|'+data, '*');
        iframeVisualizar.contentWindow.postMessage('toggleFbEdit|'+JSON.stringify({ edit: true }), '*');
        iframeVisualizar.contentWindow.postMessage('toggleFbEdit|'+JSON.stringify({ edit: false, title: 'Laudo Técnico', logo: '<?=$url_logo?>', noActions: true }), '*');
        iframeVisualizar.contentWindow.postMessage('getFbHeight', '*');
    }
    
    if (action == 'getFbHeight') {
        iframeVisualizar.style.height = (parseInt(data)+100)+'px';
    }
}, false);

$('#iframe-visualizar-laudo').on('load', function() {
    let data = JSON.stringify({ edit: false, title: 'Laudo Técnico', logo: '<?=$url_logo?>', noActions: true });
    
    let laudoForm = $('input[name=comentario]').val();
    if (laudoForm.length > 0) {
        iframeVisualizar.contentWindow.postMessage('setFbData|'+laudoForm, '*');
    }
    
    iframeVisualizar.contentWindow.postMessage('toggleFbEdit|'+data, '*');  
    iframeVisualizar.contentWindow.postMessage('getFbHeight', '*');
});

$('.btn-editar').on('click', function() {
    let id = $(this).data('id');
    window.location = 'os_laudo_tecnico_cadastro.php?laudo_tecnico='+id;
});

if ($('#table-result').length > 0) {
    $.dataTableLoad({ table: '#table-result' });

    $('#btn-copiar-laudo').on('click', function() {
        $('#modal-copiar-laudo').modal('show');
    });

    $('.btn-copiar').on('click', function() {
        let formulario = $(this).next('input').val();

        iframeEditar.contentWindow.postMessage('setFbData|'+formulario, '*');
        iframeEditar.contentWindow.postMessage('getFbData', '*');
        $('#modal-copiar-laudo').modal('hide');
    });
}
    
</script>

<?php
include 'rodape.php';
?>
