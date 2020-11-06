<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include '../token_cookie.php';
$token_cookie = $_COOKIE['sess'];

$cookie_login = get_cookie_login($token_cookie);

if (strlen($cookie_login["cook_login_posto"]) > 0) {
    include 'autentica_usuario.php';
} else {
    include 'autentica_admin.php';
}

if ($_POST['ajax_editar'] == true ) {
    $ajax_editar = $_POST['ajax_editar'];
    $posicao = (int) trim ($_POST["posicao"]);
    $editar = $_POST['editar'];

    $editar = json_decode($editar,true);
    $perg_descricao = utf8_decode($editar['descricao']);
    $perg_ativo = $editar['ativo'];
    $perg_obrigatorio = $editar['obrigatorio'];
    $perg_texto_ajuda = utf8_decode($editar['texto_ajuda']);
    $perg_tipo_descricao = $editar['tipo_descricao'];
    $perg_peso = $editar['peso'];

    $perg_inicio = $editar['inicio'];
    $perg_fim = $editar['fim'];
    $perg_intervalo = $editar['intervalo'];
    $perg_ordem = $editar['ordem'];

}

/* Início excluir Resposta */
if ( isset($_POST['excluir']) AND isset($_POST['ajax_exclui_alternativa'])) {

    $id_alternativa = (int) $_POST['excluir'];

    if(!empty($id_alternativa)) {

        pg_query($con,"BEGIN TRANSACTION");

        $sql = "SELECT resposta
                    FROM tbl_resposta
                        JOIN tbl_tipo_resposta_item USING(tipo_resposta_item)
                    WHERE tbl_tipo_resposta_item.tipo_resposta_item = {$id_alternativa};";
        $res = pg_query($con,$sql);

        if (pg_num_rows($res) > 0) {
            $retorno = array("error" => utf8_encode("Alternativa não pode ser excluída, respostas existentes!"));
            exit(json_encode($retorno));
        } else {
            $sql = "SELECT  tbl_tipo_resposta_item.tipo_resposta_item
                    FROM tbl_tipo_resposta_item
                    WHERE tipo_resposta_item = {$id_alternativa}";
            $res = pg_query($con,$sql);

            // Delete o tipo resposta item
            if (pg_num_rows($res) > 0) {

                $sqlDell = "DELETE FROM tbl_tipo_resposta_item WHERE tipo_resposta_item = {$id_alternativa}";
                $resDell = pg_query($con,$sqlDell);

                if (pg_last_error($con)) {
                    pg_query($con,"ROLLBACK TRANSACTION");
                    $retorno = array("error" => utf8_encode("Alternativa não pode ser excluída!"));
                    exit(json_encode($retorno));
                }

            }
        }
        //echo pg_last_error();
        if ( pg_last_error($con)) {
            $retorno = array("error" => utf8_encode(pg_last_error($con)));
        } else {
            pg_query($con,"COMMIT TRANSACTION");
            // pg_query($con,"ROLLBACK TRANSACTION");
            $retorno = array("ok" => utf8_encode("Alternativa excluída com sucesso!"));
        }
    }
    exit(json_encode($retorno));
}
/* Fim excluir Resposta */

if (isset($_REQUEST["edit"])) {
    try {
        $pergunta   = (int) trim ($_REQUEST["edit"]);
        $posicao    = (int) trim ($_REQUEST["posicao"]);

        if ( empty($msg_erro) && !empty($pergunta) ) {
            $sqlPergunta = "
                  SELECT tbl_pergunta.pergunta
                       , tbl_pergunta.descricao
                       , tbl_pergunta.texto_ajuda
                       , tbl_pergunta.tipo_resposta
                       , tbl_pergunta.ativo
                       , tbl_tipo_resposta.label_inicio
                       , tbl_tipo_resposta.label_fim
                       , tbl_tipo_resposta.label_intervalo
                       , tbl_tipo_resposta.obrigatorio
                       , tbl_tipo_resposta.peso
                       , tbl_tipo_resposta.tipo_descricao
                       , tbl_pesquisa_pergunta.ordem
                    FROM tbl_pergunta
                    JOIN tbl_tipo_resposta USING (tipo_resposta)
               LEFT JOIN tbl_pesquisa_pergunta
                      ON tbl_pergunta.pergunta = tbl_pesquisa_pergunta.pergunta
                   WHERE tbl_pergunta.pergunta = {$pergunta}
                     AND tbl_pergunta.fabrica  = {$login_fabrica};";
            $resPergunta = pg_query($con,$sqlPergunta);
            echo $sqlPergunta;

            if ( pg_num_rows($resPergunta) == 0 ) {
                throw new Exception("Pesquisa " . $pesquisa . " não Encontrada.", 1);
            }

            if (pg_num_rows($resPergunta)) {
                $pergunta            = pg_fetch_result($resPergunta, 0, 'pergunta');
                $perg_descricao      = pg_fetch_result($resPergunta, 0, 'descricao');
                $perg_texto_ajuda    = pg_fetch_result($resPergunta, 0, 'texto_ajuda');
                $perg_tipo_descricao = pg_fetch_result($resPergunta, 0, 'tipo_descricao');
                $perg_ativo          = pg_fetch_result($resPergunta, 0, 'ativo');
                $perg_inicio         = pg_fetch_result($resPergunta, 0, 'label_inicio');
                $perg_fim            = pg_fetch_result($resPergunta, 0, 'label_fim');
                $perg_intervalo      = pg_fetch_result($resPergunta, 0, 'label_intervalo');
                $perg_obrigatorio    = pg_fetch_result($resPergunta, 0, 'obrigatorio');
                $perg_peso           = pg_fetch_result($resPergunta, 0, 'peso');
                $tipo_resposta       = pg_fetch_result($resPergunta, 0, 'tipo_resposta');
                $perg_ordem          = pg_fetch_result($resPergunta, 0, 'ordem');
            }
        }

    } catch (Exception $e) {
        $msg_erro["msg"][] = $e->getMessage();
    }

}


?>
<!-- <!DOCTYPE html />
<html>
    <head>
        <meta http-equiv=pragma content=no-cache>
        <link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
        <link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
        <link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
        <link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

        <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
        <script src="bootstrap/js/bootstrap.js"></script>
 -->
        <script type="text/javascript">
            $(document).on('click','.addAlternativa',function(){
                var conti = 0;
                $("div[id^=perg_item_alternativa_]").each(function(){
                    conti++;
                    var contItem = conti-1;
                    $(this).attr('id','perg_item_alternativa_'+contItem);
                    $(this).find("input[name^='perg_item[']").attr('name',"perg_item["+contItem+"]");
                    $(this).find("input[name^='perg_peso_item[']").attr('name',"perg_peso_item["+contItem+"]");
                    if (contItem > 0) {
                        $(this).find("input[type='button']").attr('onclick',"deleteAlternativa('perg_item_alternativa_"+contItem+"')");
                    }
                });

            });

            function addAlternativa() {
                var perg_item = $("input[name='perg_item[0]']");
                var perg_peso_item = $("input[name='perg_peso_item[0]']");

                var htm_input = '<div class="row-fluid" id="perg_item_alternativa_">\
                                    <div class="span1"></div>';

                if (perg_item.val() != '' && perg_item.val() != undefined)  {
                    <?php
                    if (!in_array($login_fabrica, array(129))) { ?>
                        htm_input += '<div class="span7">\
                                        <div class="control-group">\
                                            <label class="control-label">Alternativa</label>\
                                            <div class="controls controls-row">\
                                                <div class="span12">\
                                                    <input type="text" value="'+perg_item.val()+'" name="perg_item[]" class="span12" />\
                                                </div>\
                                            </div>\
                                        </div>\
                                    </div>';
                    <?php
                    } else { ?>

                        if (perg_peso_item.val() == '' || perg_peso_item.val() == undefined)  {
                            alert('Favor Adicionar o Peso da Alternativa!');
                            return 0;
                        }

                        htm_input += '<div class="span6">\
                                        <div class="control-group">\
                                            <label class="control-label">Alternativa</label>\
                                            <div class="controls controls-row">\
                                                <div class="span12">\
                                                    <input type="text" value="'+perg_item.val()+'" name="perg_item[]" class="span12" />\
                                                </div>\
                                            </div>\
                                        </div>\
                                    </div>\
                                    <div class="span1">\
                                        <div class="control-group">\
                                            <label class="control-label">Peso da Alternativa</label>\
                                            <div class="controls controls-row">\
                                                <div class="span12">\
                                                    <input type="text" name="perg_peso_item[]" value="'+perg_peso_item.val()+'" class="span12" />\
                                                </div>\
                                            </div>\
                                        </div>\
                                    </div>';
                    <?php
                    } ?>

                    htm_input +=    '<div class="span3">\
                                        <div class="control-group">\
                                            <label class="control-label">&nbsp;</label>\
                                            <div class="controls controls-row">\
                                                <div class="span12">\
                                                    <input type="button" class="btn btn-small btn-danger" value="Remover Alternativa" onclick="deleteAlternativa()" />\
                                                </div>\
                                            </div>\
                                        </div>\
                                    </div>\
                                    <div class="span1"></div>\
                                </div>';

                    $(htm_input).appendTo("#options");
                } else {
                    alert('Favor Adicionar a Descrição da Alternativa!');
                }
                perg_item.val('');
                perg_peso_item.val('');
            }

            function deleteAlternativa(id,id_alternativa) {
                if ( confirm("Deseja mesmo excluir a alternativa?") ){
                    if (id_alternativa != 'undefined' && id_alternativa != '') {
                        $.ajax({
                            async: false,
                            url: "<?=$PHP_SELF?>",
                            type: "POST",
                            data: {
                                ajax_exclui_alternativa: true,
                                excluir: id_alternativa
                            },beforeSend : function() {
                                $("#loading-block").show();
                                $("#loading").show();
                            },
                            complete: function(data) {
                                $("#loading-block").hide();
                                $("#loading").hide();

                                data = $.parseJSON(data.responseText);
                                if (data.error) {
                                    alert(data.error);
                                } else {
                                    alert(data.ok);
                                    $("#"+id).remove();
                                }
                            }
                        });
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            }

            function verificaTipo() {
                var val = $("#perg_tipo_descricao").val();
                $("#range, #options").hide();

                if (val === 'range') {
                    $("#range").show();

                    var conti = 0;
                    $("div[id^=perg_item_alternativa_]").each(function(){
                        conti++;
                        var contItem = conti-1;
                        if (contItem > 0) {
                            $(this).remove();
                        }
                    });
                } else if (val === 'checkbox' || val === 'radio') {
                    $("#options").show();

                    $("#perg_inicio").val('');
                    $("#perg_fim").val('');
                    $("#perg_intervalo").val('');
                } else if (val === 'text' || val === 'date' || val === 'textarea') {
                    $("#perg_inicio").val('');
                    $("#perg_fim").val('');
                    $("#perg_intervalo").val('');

                    var conti = 0;
                    $("div[id^=perg_item_alternativa_]").each(function(){
                        conti++;
                        var contItem = conti-1;
                        if (contItem > 0) {
                            $(this).remove();
                        }
                    });
                }
            }

            function addPerguntaTabela() {

                // console.log('addPerguntaTabela');
                var pergunta = new Object();

                var perg_descricao = $('#perg_descricao').val();
                var perg_tipo_resp = $('#perg_tipo_resp').val();
                var perg_tipo_descricao = $('#perg_tipo_descricao').val();
                var perg_obrigatorio = $('#perg_obrigatorio').val();
                var perg_ativo = $('#perg_ativo').val();

                pergunta.descricao = perg_descricao;
                pergunta.tipo_desc = $('#perg_tipo option:selected').val();
                pergunta.tipo_resp = perg_tipo_resp;
                pergunta.tipo_descricao = perg_tipo_descricao;
                pergunta.obrigatorio = perg_obrigatorio;
                pergunta.peso = $('#perg_peso').val();
                pergunta.inicio = $('#perg_inicio').val();
                pergunta.fim = $('#perg_fim').val();
                pergunta.intervalo = $('#perg_intervalo').val();
                pergunta.ativo = perg_ativo;
                pergunta.texto_ajuda = $('#perg_texto_ajuda').val();

                var conti = 0;
                var contItem = 0;
                var add_respostas_item = [];
                var add_respostas = [];
                var add_peso = [];
                $("div[id^=perg_item_alternativa_]").each(function(){
                    conti++;
                    contItem = conti-1;
                    if (contItem > 0) {
                        add_respostas_item.push( $(this).find("input[name='respostas_item["+contItem+"]']").val() );
                        add_respostas.push( $(this).find("input[name='perg_item["+contItem+"]']").val() );
                        add_peso.push( $(this).find("input[name='perg_peso_item["+contItem+"]']").val() );
                    }
                });

                pergunta.resposta_item = add_respostas_item;
                pergunta.resposta = add_respostas;
                pergunta.resposta_peso = add_peso;
                pergunta.posicao = '';
                pergunta.id = '';

                pergunta.posicao = '<?=$posicao;?>';
                pergunta.id = '<?=$pergunta;?>';

                if ( perg_descricao  == '' || perg_tipo_descricao == '' || perg_ativo == '' || perg_obrigatorio == '' ) {

                    alert('Para o cadastro da Pergunta é necessário o preenchimento dos campos Pergunta, Ativo, Obrigatória e Tipo Resposta !');

                    $("#perg_descricao").parents(".control-group").addClass('error');
                    $("#perg_tipo_descricao").parents(".control-group").addClass('error');
                    $("#perg_ativo").parents(".control-group").addClass('error');
                    $("#perg_obrigatorio").parents(".control-group").addClass('error');

                    return false;
                }

                if ((perg_tipo_descricao == 'radio' || perg_tipo_descricao == 'checkbox') && contItem == 0)  {
                    alert('É necessário cadastrar uma alternativa para a resposta!');
                    return false;
                }
                // console.log(pergunta.posicao);
                window.parent.retorna_pergunta(pergunta);
                window.parent.Shadowbox.close();

            }

            function editPerguntaTabela() {
                // console.log('editPerguntaTabela');
                var pergunta = new Object();

                var perg_descricao = $('#perg_descricao').val();
                var perg_tipo_resp = $('#perg_tipo_resp').val();
                var perg_tipo_descricao = $('#perg_tipo_descricao').val();
                var perg_obrigatorio = $('#perg_obrigatorio').val();
                var perg_ativo = $('#perg_ativo').val();

                pergunta.descricao = perg_descricao;
                pergunta.tipo_desc = $('#perg_tipo option:selected').val();
                pergunta.tipo_resp = perg_tipo_resp;
                pergunta.tipo_descricao = perg_tipo_descricao;
                pergunta.obrigatorio = perg_obrigatorio;
                pergunta.peso = $('#perg_peso').val();
                pergunta.inicio = $('#perg_inicio').val();
                pergunta.fim = $('#perg_fim').val();
                pergunta.intervalo = $('#perg_intervalo').val();
                pergunta.ativo = perg_ativo;
                pergunta.texto_ajuda = $('#perg_texto_ajuda').val();
                pergunta.ordem = $('#perg_ordem').val();

                var conti = 0;
                var add_respostas_item = [];
                var add_respostas = [];
                var add_peso = [];
                $("div[id^=perg_item_alternativa_]").each(function(){
                    conti++;
                    var contItem = conti-1;
                    if (contItem > 0) {
                        add_respostas_item.push( $(this).find("input[name='respostas_item["+contItem+"]']").val() );
                        add_respostas.push( $(this).find("input[name='perg_item["+contItem+"]']").val() );
                        add_peso.push( $(this).find("input[name='perg_peso_item["+contItem+"]']").val() );
                    }
                });

                pergunta.resposta_item = add_respostas_item;
                pergunta.resposta = add_respostas;
                pergunta.resposta_peso = add_peso;
                pergunta.posicao = '';
                pergunta.id = '';

                pergunta.posicao = '<?=$posicao;?>';
                pergunta.id = '<?=$pergunta;?>';

                if ( perg_descricao  === '' || perg_tipo_descricao == '' || perg_ativo == '' || perg_obrigatorio == '' ) {

                    alert('Para o cadastro da Pergunta é necessário o preenchimento dos campos Pergunta, Ativo, Obrigatória e Tipo Resposta !');

                    $("#perg_descricao").parents(".control-group").addClass('error');
                    $("#perg_tipo_descricao").parents(".control-group").addClass('error');
                    $("#perg_ativo").parents(".control-group").addClass('error');
                    $("#perg_obrigatorio").parents(".control-group").addClass('error');

                    return false;
                }
                // console.log(pergunta.posicao);
                window.parent.retorna_editpergunta(pergunta);
                window.parent.Shadowbox.close();

            }

            $(document).ready(function(){

                $("#addPerguntaTabela").click(function(e){
                    addPerguntaTabela();
                    e.preventDefault();
                });

                $("#editPerguntaTabela").click(function(e){
                    editPerguntaTabela();
                    e.preventDefault();
                });

                $("#cancelPerguntaTabela").click(function(e){
                    window.parent.cancelPerguntaTabela();
                    window.parent.Shadowbox.close();
                });

                $("#perg_tipo_descricao").change(function(){
                    verificaTipo();
                });

                verificaTipo();
            });
        </script>
<!--     </head> -->

<!--     <body> -->
        <div id="div_pergunta" class="tc_formulario">
            <div class="titulo_tabela">Adicionar Perguntas</div>
            <input type="hidden" id="perg_ordem" value="<?=$perg_ordem?>" name="perg_ordem"  />
            <input type="hidden" id="perg_tipo_resp" value="<?=$tipo_resposta?>" name="perg_tipo_resp"  />
            <br>
            <div class='row-fluid'>
                <div class='span1'></div>
                <div class='span6'>
                    <div class='control-group <?=(in_array("perg_descricao", $msg_erro["campos"])) ? "error" : "" ?>'>
                        <label class='control-label'>Pergunta</label>
                        <div class='controls controls-row'>
                            <div class='span12'>
                                <h5 class='asteristico'>*</h5>
                                <input type="text" name="perg_descricao" id="perg_descricao"  class='span12' value="<?=$perg_descricao?>" maxlength="250" >
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span2'>
                    <div class='control-group <?=(in_array("perg_ativo", $msg_erro["campos"])) ? "error" : "" ?>'>
                        <label class='control-label'>Ativo</label>
                        <div class='controls controls-row'>
                            <div class='span12'>
                                <h5 class='asteristico'>*</h5>
                                <select name="perg_ativo" id="perg_ativo" class="span12">
                                    <option value=""></option>
                                    <option value="t" <?=($perg_ativo=='t') ? 'selected' : ''?>>Ativo</option>
                                    <option value="f" <?=($perg_ativo=='f') ? 'selected' : ''?>>Inativo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span2'>
                    <div class='control-group <?=(in_array("perg_obrigatorio", $msg_erro["campos"])) ? "error" : "" ?>'>
                        <label class='control-label'>Obrigatória</label>
                        <div class='controls controls-row'>
                            <div class='span12'>
                                <h5 class='asteristico'>*</h5>
                                <select name="perg_obrigatorio" id="perg_obrigatorio" class="span12">
                                    <option value=""></option>
                                    <option value="t" <?=($perg_obrigatorio=='t') ? 'selected' : ''?>>Sim</option>
                                    <option value="f" <?=($perg_obrigatorio=='f') ? 'selected' : ''?>>Não</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span1"></div>
            </div>
            <div class='row-fluid'>
                <div class='span1'></div>
                <div class='span10'>
                    <div class='control-group'>
                        <label class='control-label'>Informação de ajuda sobre a pergunta:</label>
                        <div class='controls controls-row'>
                            <div class='span12'>
                                <textarea name="perg_texto_ajuda" id="perg_texto_ajuda" value="<?=$perg_texto_ajuda;?>" class="span12" ><?=$perg_texto_ajuda;?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span1"></div>
            </div>
            <div class="row-fluid">
                <div class="span1"></div>
                <div class='span3'>
                    <div class='control-group <?=(in_array("perg_tipo_descricao", $msg_erro["campos"])) ? "error" : "" ?>'>
                        <label class='control-label'>Tipo Resposta</label>
                        <div class='controls controls-row'>
                            <div class='span12'>
                                <h5 class='asteristico'>*</h5>
                                <select name="perg_tipo_descricao" id="perg_tipo_descricao" class="span12">
                                    <option value=""></option>
                                    <option value="text" <? echo $perg_tipo_descricao == 'text' ? 'selected' : ''; ?> >Caixa de Texto</option>
                                    <option value="date" <? echo $perg_tipo_descricao == 'date' ? 'selected' : ''; ?> >Data</option>
                                    <?php
                                    if(!in_array($login_fabrica,array(129))){ ?>
                                        <option value="range" <? echo $perg_tipo_descricao == 'range' ? 'selected' : ''; ?> >Escala</option>
                                    <?php
                                    }?>
                                    <option value="radio" <? echo $perg_tipo_descricao == 'radio' ? 'selected' : ''; ?> >Escolha Única</option>
                                    <option value="checkbox" <? echo $perg_tipo_descricao == 'checkbox' ? 'selected' : ''; ?> >Múltipla Escolha</option>
                                    <option value="textarea" <? echo $perg_tipo_descricao == 'textarea' ? 'selected' : ''; ?>>Parágrafo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
               <?php
                if($login_fabrica == 129){ ?>
                    <div class="span2">
                        <div class="control-group">
                            <label class="control-label">Peso</label>
                            <div class="controls controls-row">
                                <div class="span12">
                                    <input type="text" name="perg_peso" class="span12" id="perg_peso" value="<?=$perg_peso;?>" <?=$donly;?> />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="span6"></div>
                <?php
                } else { ?>
                    <div class="span8"></div>
                <?php
                } ?>
            </div>
            <div id="range" class='row-fluid'>
                <div class='span1'></div>
                <div class='span2'>
                    <div class='control-group'>
                        <label class='control-label'>Início</label>
                        <div class='controls controls-row'>
                            <div class='span12'>
                                <input type="text" name="perg_inicio" id="perg_inicio"  class='span12' value="<?=$perg_inicio?>">
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span2'>
                    <div class='control-group'>
                        <label class='control-label'>Fim</label>
                        <div class='controls controls-row'>
                            <div class='span12'>
                                <input type="text" name="perg_fim" id="perg_fim"  class='span12' value="<?=$perg_fim?>">
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span2'>
                    <div class='control-group'>
                        <label class='control-label'>Intervalo</label>
                        <div class='controls controls-row'>
                            <div class='span12'>
                                <input type="text" name="perg_intervalo" id="perg_intervalo"  class='span12' value="<?=$perg_intervalo?>">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span5"></div>
            </div>
            <div id="options">
                <div class='row-fluid' id="perg_item_alternativa_0" >
                    <div class='span1'></div>
                    <?php
                    if($login_fabrica != 129){ ?>
                        <div class='span7'>
                            <div class='control-group'>
                                <label class="control-label">Alternativa</label>
                                <div class='controls controls-row'>
                                    <div class='span12'>
                                        <input type="text" value="<?=$_POST['perg_item'][0] ?>" name="perg_item[0]" class="span12" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php
                    } else { ?>
                        <div class='span6'>
                            <div class='control-group'>
                                <label class="control-label">Alternativa</label>
                                <div class='controls controls-row'>
                                    <div class='span12'>
                                        <input type="text" value="<?=$_POST['perg_item'][0] ?>" name="perg_item[0]" class="span12" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='span1'>
                            <div class='control-group'>
                                <label class="control-label">Peso da Alternativa</label>
                                <div class='controls controls-row'>
                                    <div class='span12'>
                                        <input type="text" name="perg_peso_item[0]" value="<?=$_POST['perg_peso_item'][0]?>" class="span12" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php
                    } ?>
                    <div class='span3'>
                        <div class='control-group'>
                            <label class="control-label">&nbsp;</label>
                            <div class='controls controls-row'>
                                <div class='span12'>
                                    <input type="button" class="btn btn-small btn-info addAlternativa" value="Adicionar Alternativa" onclick="addAlternativa()" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="span1"></div>
                </div>
                <?php
                if (count($editar['resposta']) > 0) {
                    for ($i = 0; $i < count($editar['resposta']); $i++) { ?>
                        <div class='row-fluid' id="perg_item_alternativa_<?=$i?>" >
                            <div class='span1'></div>
                            <?php
                            if($login_fabrica != 129){ ?>
                                <div class='span7'>
                                    <div class='control-group'>
                                        <label class="control-label">Alternativa</label>
                                        <div class='controls controls-row'>
                                            <div class='span12'>
                                                <input type="text" value="<?=utf8_decode($editar['resposta'][$i])?>" name="perg_item[<?=$i+1;?>]" class="span12" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php
                            } else { ?>
                                <div class='span6'>
                                    <div class='control-group'>
                                        <label class="control-label">Alternativa</label>
                                        <div class='controls controls-row'>
                                            <div class='span12'>
                                                <input type="text" value="<?=utf8_decode($editar['resposta'][$i])?>" name="perg_item[<?=$i+1;?>]" class="span12" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class='span1'>
                                    <div class='control-group'>
                                        <label class="control-label">Peso da Alternativa</label>
                                        <div class='controls controls-row'>
                                            <div class='span12'>
                                                <input type="text" name="perg_peso_item[<?=$i+1;?>]" value="<?=utf8_decode($editar['resposta_peso'][$i])?>" class="span12" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php
                            } ?>
                            <div class='span3'>
                                <div class='control-group'>
                                    <label class="control-label">&nbsp;</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="button" class="btn btn-small btn-danger" value="Remover Alternativa" onclick='deleteAlternativa("teste1_perg_item_alternativa_<?=$i+1;?>")' />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="span1"></div>
                        </div>
                    <?php
                    }
                } elseif (count($_POST['item']) > 0) {
                    for ($i = 1; $i < count($_POST['item']); $i++) { ?>
                        <div class='row-fluid' id="perg_item_alternativa_<?=$i?>" >
                            <div class='span1'></div>
                            <?php
                            if($login_fabrica != 129){ ?>
                                <div class='span7'>
                                    <div class='control-group'>
                                        <label class="control-label">Alternativa</label>
                                        <div class='controls controls-row'>
                                            <div class='span12'>
                                                <input type="text" value="<?=$_POST['item'][$i] ?>" name="perg_item[<?=$i;?>]" class="span12" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php
                            } else { ?>
                                <div class='span6'>
                                    <div class='control-group'>
                                        <label class="control-label">Alternativa</label>
                                        <div class='controls controls-row'>
                                            <div class='span12'>
                                                <input type="text" value="<?=$_POST['item'][$i] ?>" name="perg_item[<?=$i;?>]" class="span12" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class='span1'>
                                    <div class='control-group'>
                                        <label class="control-label">Peso da Alternativa</label>
                                        <div class='controls controls-row'>
                                            <div class='span12'>
                                                <input type="text" name="perg_peso_item[<?=$i;?>]" value="<?=$_POST['peso_item'][$i]?>" class="span12" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php
                            } ?>
                            <div class='span3'>
                                <div class='control-group'>
                                    <label class="control-label">&nbsp;</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="button" class="btn btn-small btn-danger" value="Remover Alternativa" onclick='deleteAlternativa("teste2_perg_item_alternativa_<?=$i;?>")' />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="span1"></div>
                        </div>
                    <?php
                    }
                } elseif ($tipo_resposta) {
                    $sql = "SELECT *
                                FROM tbl_tipo_resposta_item
                                WHERE tipo_resposta = $tipo_resposta";
                    $res = pg_query($con,$sql);
                    $j = 0;
                    for ($i=0;$i < pg_num_rows($res); $i++) {
                        $j++;
                        $tipo_resposta_item = pg_result($res,$i,'tipo_resposta_item');
                        $descricao_resposta_item = pg_result($res,$i,'descricao');
                        $peso = pg_result($res,$i,'peso'); ?>

                        <div class='row-fluid' id="perg_item_alternativa_<?=$j?>" >
                            <div class='span1'></div>
                            <input type="hidden" id="resposta_item" value="<?=$tipo_resposta_item?>" name="resposta_item"  />
                            <?php
                            if($login_fabrica != 129){ ?>
                                <div class='span7'>
                                    <div class='control-group'>
                                        <label class="control-label">Alternativa</label>
                                        <div class='controls controls-row'>
                                            <div class='span12'>
                                                <input type="text" value="<?=$descricao_resposta_item;?>" name="perg_item[<?=$tipo_resposta_item;?>]" class="span12" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php
                            } else { ?>
                                <div class='span6'>
                                    <div class='control-group'>
                                        <label class="control-label">Alternativa</label>
                                        <div class='controls controls-row'>
                                            <div class='span12'>
                                                <input type="text" value="<?=$descricao_resposta_item;?>" name="perg_item[<?=$tipo_resposta_item;?>]" class="span12" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class='span1'>
                                    <div class='control-group'>
                                        <label class="control-label">Peso da Alternativa</label>
                                        <div class='controls controls-row'>
                                            <div class='span12'>
                                                <input type="text" name="perg_peso_item[<?=$tipo_resposta_item;?>]" value="<?=$peso;?>" class="span12" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php
                            } ?>
                            <div class='span3'>
                                <div class='control-group'>
                                    <label class="control-label">&nbsp;</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="button" class="btn btn-small btn-danger" value="Remover Alternativa" onclick="deleteAlternativa('perg_item_alternativa_<?=$j?>',<?=$tipo_resposta_item?>)"  />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="span1"></div>
                        </div>
                    <?php
                    }
                } ?>
            </div>
            <br />
            <div class='row-fluid tac'>
                <?php
                if (!empty($pergunta) OR $ajax_editar == true) { ?>
                    <button type="button" id="editPerguntaTabela">Editar Pergunta</button>
                <?php
                } else { ?>
                    <button type="button" id="addPerguntaTabela">Adicionar Pergunta</button>
                <?php
                } ?>

                <button class="btn btn-warning" type="button" id="cancelPerguntaTabela">Cancelar</button>
            </div>
        </div>
    <!-- </body>
</html> -->
