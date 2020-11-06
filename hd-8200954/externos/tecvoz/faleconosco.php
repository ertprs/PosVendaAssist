<?php
include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';
include '../../funcoes.php';

function getProdutoNumeroSerie($con,$login_fabrica,$serie)
{
    $sql = "
        SELECT  tbl_produto.referencia,
                tbl_produto.descricao,
                tbl_produto.produto AS produto_id
        FROM    tbl_produto
        JOIN    tbl_numero_serie USING(produto)
        WHERE   tbl_produto.fabrica_i = $login_fabrica
        AND     tbl_numero_serie.fabrica = $login_fabrica
        AND     tbl_numero_serie.serie = '$serie'
    ";
    $res = pg_query($con,$sql);

    $resultado["referencia"]    = pg_fetch_result($res,0,referencia);
    $resultado["descricao"]     = pg_fetch_result($res,0,descricao);
    $resultado["produto_id"]    = pg_fetch_result($res,0,produto_id);

    return json_encode(array("ok" => true,"resultado"=>$resultado));
}

function getCidades($con,$estadoBusca)
{
    $sql = "
        SELECT  tbl_cidade.cidade,
                tbl_cidade.nome AS cidade_nome
        FROM    tbl_cidade
        WHERE   tbl_cidade.cod_ibge IS NOT NULL
        AND     tbl_cidade.estado = '$estadoBusca'
  ORDER BY      cidade_nome
    ";
    $res = pg_query($con,$sql);

    while ($resultado = pg_fetch_object($res)) {
        $cidades[] = array("cidade_id" => $resultado->cidade, "cidade_nome" => $resultado->cidade_nome);
    }

    return json_encode(array("ok"=>true,"cidades" => $cidades));
}

if (filter_input(INPUT_POST,'ajax',FILTER_VALIDATE_BOOLEAN)) {
    $ajaxType       = filter_input(INPUT_POST,'ajaxType');
    $serie          = filter_input(INPUT_POST,'serie');
    $estadoBusca    = filter_input(INPUT_POST,'estado');

    switch ($ajaxType) {
        case "numero_serie":
            echo getProdutoNumeroSerie($con,165,$serie);
            break;
        case "buscaCidades":
            echo getCidades($con,$estadoBusca);
            break;
    }
    exit;
}

if (filter_input(INPUT_POST,'btn_submit')) {


    if (!filter_input(INPUT_POST,"consumidor_nome")) {
        $msg_erro["msg"]["obrigatorio"] = "Preencha os dados corretamente";
        $msg_erro['campos'][] = "consumidor_nome";
    }

    if (!filter_input(INPUT_POST,"consumidor_email",FILTER_VALIDATE_EMAIL)) {
        $msg_erro["msg"]["obrigatorio"] = "Preencha os dados corretamente";
        $msg_erro['campos'][] = "consumidor_email";
    }

    if (!filter_input(INPUT_POST,"consumidor_estado")) {
        $msg_erro["msg"]["obrigatorio"] = "Preencha os dados corretamente";
        $msg_erro['campos'][] = "consumidor_estado";
    }
    if (!filter_input(INPUT_POST,"consumidor_cidade")) {
        $msg_erro["msg"]["obrigatorio"] = "Preencha os dados corretamente";
        $msg_erro['campos'][] = "consumidor_cidade";
    }
    if (!filter_input(INPUT_POST,"consumidor_celular")) {
        $msg_erro["msg"]["obrigatorio"] = "Preencha os dados corretamente";
        $msg_erro['campos'][] = "consumidor_celular";
    }
    if (!filter_input(INPUT_POST,"produto_referencia")) {
        $msg_erro["msg"]["obrigatorio"] = "Preencha os dados corretamente";
        $msg_erro['campos'][] = "produto_referencia";
    }
    if (!filter_input(INPUT_POST,"produto_descricao")) {
        $msg_erro["msg"]["obrigatorio"] = "Preencha os dados corretamente";
        $msg_erro['campos'][] = "produto_descricao";
    }

    if (!is_array($msg_erro)) {
        $consumidor_nome    = trim(filter_input(INPUT_POST,"consumidor_nome",FILTER_SANITIZE_SPECIAL_CHARS));
        $consumidor_email   = trim(filter_input(INPUT_POST,"consumidor_email",FILTER_SANITIZE_EMAIL));
        $consumidor_estado  = trim(filter_input(INPUT_POST,"consumidor_estado"));
        $consumidor_cidade  = trim(filter_input(INPUT_POST,"consumidor_cidade"));
        $consumidor_celular = trim(filter_input(INPUT_POST,"consumidor_celular",FILTER_SANITIZE_NUMBER_INT));
        $produto_serie      = trim(filter_input(INPUT_POST,"produto_serie",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
        $produto_id         = trim(filter_input(INPUT_POST,"produto_id"));
        $produto_referencia = trim(filter_input(INPUT_POST,"produto_referencia"));
        $produto_descricao  = trim(filter_input(INPUT_POST,"produto_descricao",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
        $revenda_nome       = trim(filter_input(INPUT_POST,"revenda_nome",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
        $defeito_descricao  = trim(filter_input(INPUT_POST,"defeito_descricao",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));

        $consumidor_celular = str_replace("-","",$consumidor_celular);

        $res = pg_query($con,"BEGIN TRANSACTION");

        $sqlInsHd = "
            INSERT INTO tbl_hd_chamado (
                fabrica,
                fabrica_responsavel,
                status,
                titulo
            ) VALUES (
                165,
                165,
                'Aberto',
                'Atendimento Fale Conosco site'
            ) RETURNING hd_chamado;
        ";
        $resInsHd = pg_query($con,$sqlInsHd);
        $erro = pg_last_error($con);

        $hd_chamado = pg_fetch_result($resInsHd,0,hd_chamado);

        $sqlInsEx = "
            INSERT INTO tbl_hd_chamado_extra (
                hd_chamado,
                origem,
                produto,
                serie,
                nome,
                email,
                cidade,
                celular,
                revenda_nome,
                reclamado
            ) VALUES (
                $hd_chamado,
                'faleconosco',
                $produto_id,
                '$produto_serie',
                '$consumidor_nome',
                '$consumidor_email',
                $consumidor_cidade,
                '$consumidor_celular',
                '$revenda_nome',
                '$defeito_descricao'
            )
        ";
        $resInsEx = pg_query($con,$sqlInsEx);
        $erro = pg_last_error($con);

        $sqlInsItem = "
            INSERT INTO tbl_hd_chamado_item (
                hd_chamado,
                comentario,
                status_item
            ) VALUES (
                $hd_chamado,
                'Abertura de chamado via Fale Conosco',
                'Aberto'
            )
        ";
        $resInsItem = pg_query($con,$sqlInsItem);
        $erro = pg_last_error($con);

        if (!empty($erro)) {
            $res = pg_query($con,"ROLLBACK TRANSACTION");
            $msg_erro["msg"]["obrigatorio"] = "Erro ao fazer o registro do atendimento.";
        } else {
            $res = pg_query($con,"COMMIT TRANSACTION");
            $msg = "Obrigado! Em breve nossa equipe entrará em contato.";
        }
    }
}

?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<link href="../../admin/bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
<link href="../../admin/bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
<link href="../../admin/bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
<link href="../../css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />

<script src="../../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js" type='text/javascript'></script>
<script src="../../plugins/bootstrap/js/bootstrap.min.js" type='text/javascript'></script>
<script src="../../js/jquery.alphanumeric.js" type='text/javascript'></script>
<script src="../../admin/plugins/jquery.maskedinput_new.js" type='text/javascript'></script>
<script src="../../plugins/tooltip.js" type='text/javascript'></script>

<style type="text/css">
     body{
        margin: 0;
        padding:0;
    }

    p {
        margin-top:30px;
        margin-left:15px;
    }

    #tudo{
        width:800px;
        margin:0 auto;
    }
    header,.dividir {
        border-bottom:1px dotted #CCC;
    }

    .dividir {
        margin-bottom:20px;
    }

    span#esq {
        margin-left:5px;
    }
    span#dir {
        float:right;
        margin-right:5px;
    }

    input[type="text"],textarea {
        width: 600px;
        height: 25px;
        background-color: #EAEAEA;
        line-height: 25px;
        padding: 0 5px;
    }

    textarea {
        height:100px;
    }
    select {
        background-color: #EAEAEA;
        padding: 0 5px;
    }
</style>

<script type="text/javascript">
$(function(){
    $("#consumidor_celular").mask("(99) 9 9999-9999");

    $("#consumidor_nome,#produto_serie").keyup(function(e){
        $(this).val($(this).val().toUpperCase());
    });

    $("#pesquisa_serie").click(function(e){
        e.preventDefault();

        var serie = $("#produto_serie").val();

        if (serie.length > 0) {
            $.ajax({
                url:"faleconosco.php",
                type:"POST",
                dataType:"JSON",
                data:{
                    ajax:true,
                    ajaxType:"numero_serie",
                    serie:serie
                }
            })
            .done(function(data){
                if (data.ok) {
                    $("#produto_referencia").val(data.resultado.referencia).attr("readOnly","true");
                    $("#produto_descricao").val(data.resultado.descricao).attr("readOnly","true");
                    $("#produto_id").val(data.resultado.produto_id);
                }
            })
            .fail(function(){
                alert("Número de série inválido");
            });
        }
    });

    $("#consumidor_estado").change(function(){
        var options = "";
        $.ajax({
            url:"faleconosco.php",
            type:"POST",
            dataType:"JSON",
            data:{
                ajax:true,
                ajaxType:"buscaCidades",
                estado:$(this).val()
            }
        })
        .done(function(data){
            if (data.ok) {
                $.each(data.cidades,function(k,v){
                    options += "<option value='"+v.cidade_id+"'>"+v.cidade_nome+"</option>";
                });
                $("#consumidor_cidade").html(options);
            }
        });
    });
});
</script>

<title>Fale Conosco - Tecvoz</title>
</head>
<body>
<div id="tudo">
    <header>
        <span id="esq">Fale com a TecVoz</span>
        <span id="dir">Campos indicados com <b>asterisco (*)</b> são de preenchimento obrigatório</span>
    </header>
    <main>
<?php
if (count($msg_erro["msg"]) > 0) {
?>
        <br />
        <div class="alert alert-error">
            <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
        </div>
<?php
}

if (!empty($msg)) {
?>
        <br />
        <div class="alert alert-success">
            <h4><?=$msg?></h4>
        </div>
<?php
}
?>
        <p>Utilize o formulário abaixo para falar com nosso <b>Atendimento Técnico</b></p>
        <form id="frmFaleConosco" method="POST">
            <div id="conteudo">
                <div class='row-fluid'>
                    <div class="span1"></div>
                    <div class="span10">
                        <div class='control-group <?=(in_array('consumidor_nome', $msg_erro['campos'])) ? "error" : "" ?>'>
                            <label class='control-label' for='consumidor_nome'>Nome</label>
                            <h5 class="asteristico">*</h5>
                            <div class='controls controls-row'>
                                <input type='text' name='consumidor_nome' id='consumidor_nome' value=""/>
                            </div>
                        </div>
                    </div>
                    <div class="span1"></div>
                </div>
                <div class='row-fluid'>
                    <div class="span1"></div>
                    <div class="span10">
                        <div class='control-group <?=(in_array('consumidor_email', $msg_erro['campos'])) ? "error" : "" ?>'>
                            <label class='control-label' for='consumidor_email'>E-mail</label>
                            <h5 class="asteristico">*</h5>
                            <div class='controls controls-row'>
                                <input type='text' name='consumidor_email' id='consumidor_email' value=""/>
                            </div>
                        </div>
                    </div>
                    <div class="span1"></div>
                </div>
                <div class='row-fluid'>
                    <div class="span1"></div>
                    <div class="span2">
                        <div class='control-group <?=(in_array('consumidor_estado', $msg_erro['campos'])) ? "error" : "" ?>'>
                            <label class='control-label' for='consumidor_estado'>UF</label>
                            <h5 class="asteristico">*</h5>
                            <select id="consumidor_estado" name="consumidor_estado" class="span8">
                                <option value="">--</option>
<?php
foreach ($array_estados() as $uf=>$estado) {
?>
                                <option value="<?=$uf?>"><?=$estado?></option>
<?php
}
?>
                            </select>
                        </div>
                    </div>
                    <div class="span8">
                        <div class='control-group <?=(in_array('consumidor_cidade', $msg_erro['campos'])) ? "error" : "" ?>'>
                            <label class='control-label' for='consumidor_cidade'>Cidade</label>
                            <h5 class="asteristico">*</h5>
                            <select id="consumidor_cidade" name="consumidor_cidade" class="span12">
                                <option value="">--</option>
                            </select>
                        </div>
                    </div>
                    <div class="span1"></div>
                </div>
                <div class='row-fluid'>
                    <div class="span1"></div>
                    <div class="span10">
                        <div class='control-group <?=(in_array('consumidor_celular', $msg_erro['campos'])) ? "error" : "" ?>'>
                            <label class='control-label' for='consumidor_celular'>Celular</label>
                            <h5 class="asteristico">*</h5>
                            <div class='controls controls-row'>
                                <input class="span4" type='text' name='consumidor_celular' id='consumidor_celular' value=""/>
                            </div>
                        </div>
                    </div>
                    <div class="span1"></div>
                </div>

                <div class="dividir">&nbsp;</div>

                <div class='row-fluid'>
                    <div class="span1"></div>
                    <div class="span3">
                        <div class='control-group'>
                            <label class='control-label' for='produto_serie'>Nº de Série</label>
                            <div class='controls controls-row'>
                                <input class="span12" type='text' name='produto_serie' id='produto_serie' value="" placeholder="Pesquisar por série" />
                            </div>
                        </div>
                    </div>
                    <div class="span5" >
                        <div class='control-group' >
                            <br>
                            <a class="btn btn-info btn-small" role="button" id="pesquisa_serie" >Pesquisar</a>
                        </div>
                    </div>
                    <div class="span1"></div>
                </div>
                <div class='row-fluid'>
                    <div class="span1"></div>
                    <div class="span2">
                        <div class='control-group <?=(in_array('produto_referencia', $msg_erro['campos'])) ? "error" : "" ?>'>
                            <label class='control-label' for='produto_referencia'>Referência</label>
                            <h5 class="asteristico">*</h5>
                            <div class='controls controls-row'>
                                <input type='hidden' name='produto_id' id='produto_id' value="" />
                                <input class="span12" type='text' name='produto_referencia' id='produto_referencia' value="" />
                            </div>
                        </div>
                    </div>
                    <div class="span5">
                        <div class='control-group <?=(in_array('produto_descricao', $msg_erro['campos'])) ? "error" : "" ?>'>
                            <label class='control-label' for='produto_descricao'>Produto</label>
                            <h5 class="asteristico">*</h5>
                            <div class='controls controls-row'>
                                <input class="span12" type='text' name='produto_descricao' id='produto_descricao' value="" />
                            </div>
                        </div>
                    </div>
                    <div class="span1"></div>
                </div>

                <div class='row-fluid'>
                    <div class="span1"></div>
                    <div class="span10">
                        <div class='control-group'>
                            <label class='control-label' for='revenda_nome'>Revenda</label>
                            <div class='controls controls-row'>
                                <input class="span12" type='text' name='revenda_nome' id='revenda_nome' value=""/>
                            </div>
                        </div>
                    </div>
                    <div class="span1"></div>
                </div>
                <div class='row-fluid'>
                    <div class="span1"></div>
                    <div class="span10">
                        <div class='control-group'>
                            <label class='control-label' for='defeito_descricao'>Descreva o defeito do produto</label>
                            <div class='controls controls-row'>
                                <textarea class="span12" id="defeito_descricao" name="defeito_descricao"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="span1"></div>
                </div>
                <div class='row-fluid'>
                    <div class="span12" align="center" >
                        <p class="tac">
                            <input type="submit" id="btn_submit" class="btn btn-primary" value="Gravar" name="btn_submit" >
                        </p>
                    </div>
                </div>
            </div>
        </form>
    </main>
</div>
</body>
</html>
