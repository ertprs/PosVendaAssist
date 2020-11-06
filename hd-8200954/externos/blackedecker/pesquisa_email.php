<?php
include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';

$os_email = $_GET['os'];
$posto_email = "";

if($os_email != ""){
    $sqlEmail = "SELECT tbl_os.os,
                tbl_os.posto,
                tbl_os.sua_os,
                tbl_posto_fabrica.codigo_posto,
                tbl_produto.descricao,
                tbl_posto_fabrica.contato_cidade AS cidade
            FROM tbl_os
                JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = 1
                JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
                    AND tbl_produto.linha IN (866,867,863,869,198,200,467,865,923,924,925)
            WHERE tbl_os.fabrica = 1 AND tbl_os.os = $os_email";

    $resEmail = pg_query($con,$sqlEmail);
    $sua_os_email = pg_fetch_result($resEmail,0,sua_os);
    $posto_email  = pg_fetch_result($resEmail,0,posto);
    $codigo_posto = pg_fetch_result($resEmail,0,codigo_posto);
    $cidade_email = pg_fetch_result($resEmail,0,cidade);
    $produto_descricao  = pg_fetch_result($resEmail,0,descricao);
    $sqlEmail = "";
    $resEmail = "";
}

if (!empty($os_email)) {

    $sql = "
        SELECT  tbl_laudo_tecnico_os.os
        FROM    tbl_laudo_tecnico_os
        WHERE   os = $os_email
    ";

    $res = pg_query($con,$sql);

    if(pg_num_rows($res) > 0){
        $msg = "Pesquisa já respondida.";
    }
}

if($_POST['ajax']){
    $ajaxType = $_POST['ajaxType'];
    switch($ajaxType){
        case "buscaCidade":
            $pais = $_POST['pais'];
            if($pais == 'BR'){
                $sql = "
                    SELECT  DISTINCT
                            tbl_posto_fabrica.contato_cidade AS cidade
                    FROM    tbl_posto
                    JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto = tbl_posto.posto
                                                AND tbl_posto_fabrica.fabrica = 1
                                                AND tbl_posto.pais = '$pais'
              ORDER BY      tbl_posto_fabrica.contato_cidade
                ";
            }else{
                $sql = "
                    SELECT  DISTINCT
                            tbl_posto.cidade
                    FROM    tbl_posto
                    JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto = tbl_posto.posto
                                                AND tbl_posto_fabrica.fabrica = 1
                                                AND tbl_posto.pais = '$pais'
              ORDER BY      tbl_posto.cidade
                ";

            }
            $res = pg_query($con,$sql);
            $conta = pg_num_rows($res);
            for($c = 0; $c < $conta; $c++){
                $cidade = pg_fetch_result($res,$c,cidade);
                $cidade = htmlentities($cidade);
                $retorno[] = array("cidade" => $cidade);
            }
        break;
        case "buscaPosto":
            $cidade = utf8_decode($_POST['cidade']);
            $posto_email = $_POST['posto_email'];
            $sql = "
                SELECT  DISTINCT
                        tbl_posto.nome,
                        tbl_posto.posto
                FROM    tbl_posto_fabrica
                JOIN    tbl_posto   ON  tbl_posto.posto             = tbl_posto_fabrica.posto
                                    AND tbl_posto_fabrica.fabrica   = 1
                                    AND (
                                            tbl_posto.cidade                    = '$cidade'
                                        OR  tbl_posto_fabrica.contato_cidade    = '$cidade'
                                        )
                                    AND tbl_posto_fabrica.credenciamento <> 'DESCREDENCIADO'
          ORDER BY      tbl_posto.nome
            ";
            $res = pg_query($con,$sql);
            $conta = pg_num_rows($res);
            for($c = 0; $c < $conta; $c++){
                $posto = pg_fetch_result($res,$c,nome);
                $posto_banco = pg_fetch_result($res,$c,posto);
                $checked = "";
                if($posto_email == $posto_banco){
                    $checked = "selected";
                }
                $posto = htmlentities($posto);
                $retorno[] = array("nome" => $posto, "selecionado" => $checked);
            }
        break;
    }

    echo json_encode($retorno);
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Language" content="pt-BR">
<link href="../../plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" />
<link href="../../admin/bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
<link href="../../admin/bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
<link href="../../admin/bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
<link href="../../css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
<script src="../../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="../../plugins/bootstrap/js/bootstrap.min.js"></script>
<script src="../../plugins/tooltip.js"></script>

<style type="text/css">
    body{
        margin: 0;
        padding:0;
    }

    #tudo{
        width:800px;
        margin:0 auto;
    }

    header h1{
        background: url("images/topo_pt.png") no-repeat scroll 0 0 /100% auto;
        display:block;
        height:310px;
        width:695px;
        margin:0;
        text-align:center;
    }

    .td_radio{
        text-align:center;
        vertical-align:middle;
    }

    .bd{
        font-weight:bold;
    }

    .container{
        width:100%;
    }

    .container table{
        margin-left:60px;
    }

    .td_btn_gravar_pergunta{
        display:none;
        margin:0;
    }

    main{
        display:none;
    }

    #comecar{
        margin-left:530px;
        margin-top:255px;
    }

</style>

<script type="text/javascript">
$(function(){
    $("#comecar").click(function(event){
        event.preventDefault();
        $("#comecar").hide();
        $("main").show();
        $("header h1").css({
            "background": "url('images/logo_black_surv.png') no-repeat scroll 0 0 /100% auto",
            "height":"105px",
            "width":"100%"
        });
    });

    <?php if(strlen($sua_os_email) > 0){ ?>
    $(document).ready(function(){
        var pais = "BR";
        var retornoCidade = [];
        var cidade = "<?=$cidade_email?>";
        var nomeCorrigido;
        $.ajax({
            type:"POST",
            url:"<?=$PHP_SELF?>",
            dataType:"json",
            data:{
                ajax:true,
                ajaxType:"buscaCidade",
                pais:pais
            }
        })
        .done(function(data){
            retornoCidade.push("<option value=''></option>");
            $(data).each(function(key,val){
                nomeCorrigido = $('<div/>').html(val.cidade).text();
                retornoCidade.push("<option value='"+nomeCorrigido+"'>"+nomeCorrigido+"</option>");
            });
            $("#cidade").html(retornoCidade);
        });

        var retornoPosto = [];
        $.ajax({
            type:"POST",
            url:"<?=$PHP_SELF?>",
            dataType:"json",
            data:{
                ajax:true,
                ajaxType:"buscaPosto",
                posto_email: '<?=$posto_email?>',
                cidade:cidade
            }
        })
        .done(function(data){
            // retornoPosto.push("<option value=''></option>");
            $(data).each(function(key,val){
                if(val.selecionado != ""){
                    retornoPosto.push("<option "+val.selecionado+" value='"+val.nome+"'>"+val.nome+"</option>");
                }
            });
            $("#posto").html(retornoPosto);
        });
    });
    <?php } ?>

    $("#pais").change(function(){
        var pais = $(this).val();
        var retorno = [];
        var nomeCorrigido;
        $.ajax({
            type:"POST",
            url:"<?=$PHP_SELF?>",
            dataType:"json",
            data:{
                ajax:true,
                ajaxType:"buscaCidade",
                pais:pais
            }
        })
        .done(function(data){
            retorno.push("<option value=''></option>");
            $(data).each(function(key,val){
                nomeCorrigido = $('<div/>').html(val.cidade).text();
                retorno.push("<option value='"+nomeCorrigido+"'>"+nomeCorrigido+"</option>");
            });
            $("#cidade").html(retorno);
        });
    });

    <?php if(strlen($sua_os_email) == 0){ ?>
    $("#cidade").change(function(){
        var cidade = $(this).val();
        var retorno = [];
        $.ajax({
            type:"POST",
            url:"<?=$PHP_SELF?>",
            dataType:"json",
            data:{
                ajax:true,
                ajaxType:"buscaPosto",
                posto_email: '<?=$posto_email?>',
                cidade:cidade
            }
        })
        .done(function(data){
            retorno.push("<option value=''></option>");
            $(data).each(function(key,val){
                retorno.push("<option "+val.selecionado+" value='"+val.nome+"'>"+val.nome+"</option>");
            });
            $("#posto").html(retorno);
        });
    });
    <?php } ?>

    $("#gravar").click(function(event){
        event.preventDefault();
        $.ajax({
            type:"POST",
            url:"respostas_pesquisa.php",
            dataType:"json",
            data:{
                ajax:true,
                input:$("#frm_pesquisa").find("input").serialize(),
                textarea:$("#frm_pesquisa").find("textarea").serialize(),
                select:$("#frm_pesquisa").find("select").serialize()
            },
            beforeSend:function(){
                $('#gravar').hide();
                $('.alert-error').hide();
                $('.td_btn_gravar_pergunta').show();
                $('.td_btn_gravar_pergunta').html("Gravando...<br><img src='../../imagens/loading_bar.gif'> ")
                .css({
                    "text-align":"center",
                    "font-size":"12"
                });
            },
        })
        .done(function(data){
            if(data.status == "ok"){
                $("#frm_pesquisa").find("input").attr('disabled',true);
                $("#frm_pesquisa").find("textarea").attr('disabled',true);
                $("#frm_pesquisa").find("select").attr('disabled',true);

                $('.agradecimentosPesquisa').show();
                $('.td_btn_gravar_pergunta').hide();
            }else{
                var motivo = "Exceto a pergunta 8, todas as perguntas são de respostas obrigatórias!";
                $('.alert-error').show().html("");
                $('.alert-error').show().append(motivo);
                $('.td_btn_gravar_pergunta').hide();
                $('#gravar').show();
            }
        });
    });
});

</script>
<title>Pesquisa de Satisfação - Black&Decker</title>
</head>
<body>
<div id="tudo">
    <header>
<?
if(strlen($msg) == 0){
?>
        <h1>
            <button class='btn btn-primary' id="comecar" />Começar</button>
        </h1>
<?
}else{
    echo "<div class='alert alert-success'>".$msg."</div>";
    exit;
}
?>
    </header>
    <main>
        <form id="frm_pesquisa">
            <input type="hidden" name="os_email" value="<?=$os_email?>" />
            <input type="hidden" name="language" value="pt" />
            <div class='' style="width: 900px;">
                <div id="conteudo">
                    <div class='row-fluid'>
                        <div class="span1"></div>
                        <div class="span5">
                            <div class='control-group'>
                                <label class='control-label' for='pais'>1 - Selecione seu país</label>
                                <select id="pais" name="pais">
                                    <option value="">&nbsp;</option>
<?
    $sql = "
        SELECT  DISTINCT
                tbl_pais.nome,
                tbl_pais.pais
        FROM    tbl_pais
        JOIN    tbl_posto           ON  tbl_posto.pais = tbl_pais.pais
        JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto = tbl_posto.posto
                                    AND tbl_posto_fabrica.fabrica = 1
  ORDER BY      tbl_pais.nome;
    ";
    $res = pg_query($con,$sql);
    $paises = pg_fetch_all($res);

    if(strlen($sua_os_email) > 0){
        $selected_email = "BR";
    }else{
        $selected_email = "";
    }

    foreach($paises as $pais){
?>
                                        <option value="<?=$pais['pais']?>" <?php echo $pais['pais'] == $selected_email ? 'selected' : '' ; ?> ><?=$pais['nome']?></option>
<?
    }
?>
                                </select>
                            </div>
                        </div>
                        <div class="span5">
                            <div class='control-group'>
                                <label class='control-label' for='cidade'>2 - Selecione sua cidade</label>
                                <select id="cidade" name="cidade">
                                    <option value="">&nbsp;</option>
                                </select>
                            </div>
                        </div>
                        <div class="span1"></div>
                    </div>
                    <br />
                    <div class='row-fluid'>
                        <div class="span1"></div>
                        <div class="span5">
                            <div class='control-group'>
                                <label class='control-label' for='posto'>3 - Assistência Técnica</label>
                                <select id="posto" name="posto">
                                    <option value="">&nbsp;</option>
                                </select>
                            </div>
                        </div>
                        <div class="span5">
                            <div class='control-group'>
                                <label class='control-label' for='os'>4 - Ordem de serviço</label>
                                <div class='input-append'>
                                    <input type='text' name='os' id='os' value="<?php echo $codigo_posto.''.$sua_os_email; ?>"/>
                                    <span class="add-on">
                                        <i id="btnPopover" rel="popover" data-placement="top" data-trigger="hover" data-delay="500" title="OS" data-content="É o número que aparece no relatório de conserto que vem junto à máquina" class="icon-question-sign"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="span1"></div>
                    </div>
                    <br />
                    <div class="container">
                        <div class='row-fluid'>
                            <div class="span1"></div>
                            <div class="span5">
                                <div class='control-group'>
                                    5 - Equipamento
                                    <br />
                                    <div class="radio">
                                        <label>
                                        <input type="radio" name="equipamento" id="martelo" value="martelo" />Martelo<br />
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                        <input type="radio" name="equipamento" id="furadeira" value="furadeira" />Furadeira/Furadeira sem fio<br />
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                        <input type="radio" name="equipamento" id="mecanica" value="mecanica" />Metal - Mecânica<br />
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                        <input type="radio" name="equipamento" id="madeira" value="madeira" />Madeira<br />
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                        <input type="radio" name="equipamento" id="serras" value="serras" />Serras<br />
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                        <input type="radio" name="equipamento" id="jardinagem" value="jardinagem" />Jardinagem<br />
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                        <input type="radio" name="equipamento" id="gasolina" value="gasolina" />Gasolina<br />
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                        <input type="radio" name="equipamento" id="pneumatica" value="pneumatica" />Pneumática<br />
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                        <input type="radio" name="equipamento" id="outro" value="outro" />Outros<br />
                                        </label>
                                    </div>
                                    </label>
                                </div>
                            </div>
                            <div class="span5">
                                <div class='control-group'>
                                    <label class='control-label' for='marca'>Marca</label>
                                    <select id="marca" name="marca">
                                        <option value="">&nbsp;</option>
<?
    $sql = "
        SELECT  marca,
                nome
        FROM    tbl_marca
        WHERE   fabrica = 1
        AND     marca <> 238
    ";
    $res = pg_query($con,$sql);
    $resultado = pg_fetch_all($res);
    foreach($resultado as $valor){
?>
                                        <option value="<?=$valor['marca']?>"><?=$valor['nome']?></option>
<?
    }
?>
                                    </select>
                                    <br />
                                    <label class='control-label' for='produto'>Produto</label>
                                    <div class='controls controls-row'>
                                        <input type='text' name='produto' id='produto' value="<?=$produto_descricao?>"/>
                                    </div>
                                </div>
                            </div>
                            <div class="span1"></div>
                        </div>
                    </div>
                    <br />
                    <div class='row-fluid'>
                        <div class="span1"></div>
                        <div class="span10">
                            <label class='control-label' for='outro_qual'>Qual</label>
                            <div class='controls controls-row'>
                                <input type='text' name='outro_qual' id='outro_qual' value=""/>
                            </div>
                        </div>
                        <div class="span1"></div>
                    </div>
                    <br />
                    <div class='row-fluid'>
                        <div class="span1"></div>
                        <div class="span10">
                            <div class='control-label'>
                                6 - Com base na experiência com o reparo do seu produto você recomendaria a <span class="bd">Stanley Black and Decker</span> aos seus colegas, familiares ou amigos?
                                <span class="add-on">
                                    <i id="btnPopover6" rel="popover" data-placement="top" data-trigger="hover" data-delay="500" title="RECOMENDAÇÃO" data-content="Escolha um número entre 10 e 0, onde 10 significa que é 100% certo de que você recomendaria aos seus colegas, amigos e familiares o serviço pós-venda de Stanley Black & Decker" class="icon-question-sign"></i>
                                </span>
                            </div>
                        </div>
                        <div class="span1"></div>
                    </div>
                    <div class='row-fluid'>
                        <div class="span1"></div>
                        <div class="span10">
                            <div class='control-group'>
                                <table border="0">
                                    <tr>
                                        <td rowspan="2" style="vertical-align:bottom;" nowrap>
                                            Muito provável
                                        </td>
                                        <td style="text-align:center;width:25px;">10</td>
                                        <td style="text-align:center;width:25px;">9</td>
                                        <td style="text-align:center;width:25px;">8</td>
                                        <td style="text-align:center;width:25px;">7</td>
                                        <td style="text-align:center;width:25px;">6</td>
                                        <td style="text-align:center;width:25px;">5</td>
                                        <td style="text-align:center;width:25px;">4</td>
                                        <td style="text-align:center;width:25px;">3</td>
                                        <td style="text-align:center;width:25px;">2</td>
                                        <td style="text-align:center;width:25px;">1</td>
                                        <td style="text-align:center;width:25px;">0</td>
                                        <td rowspan="2" style="vertical-align:bottom;" nowrap>
                                            Pouco Provável
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="text-align:center;"><input type="radio" name="recomendacao" value="10"/></td>
                                        <td style="text-align:center;"><input type="radio" name="recomendacao" value="9" /></td>
                                        <td style="text-align:center;"><input type="radio" name="recomendacao" value="8" /></td>
                                        <td style="text-align:center;"><input type="radio" name="recomendacao" value="7" /></td>
                                        <td style="text-align:center;"><input type="radio" name="recomendacao" value="6" /></td>
                                        <td style="text-align:center;"><input type="radio" name="recomendacao" value="5" /></td>
                                        <td style="text-align:center;"><input type="radio" name="recomendacao" value="4" /></td>
                                        <td style="text-align:center;"><input type="radio" name="recomendacao" value="3" /></td>
                                        <td style="text-align:center;"><input type="radio" name="recomendacao" value="2" /></td>
                                        <td style="text-align:center;"><input type="radio" name="recomendacao" value="1" /></td>
                                        <td style="text-align:center;"><input type="radio" name="recomendacao" value="0" /></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="span1"></div>
                    </div>
                    <br />
                    <div class="container">
                        <div class='row-fluid'>
                            <div class="span1"></div>
                                <div class="span10">
                                    <div class='control-group'>
                                        7 - Selecione a principal razão para a sua pontuação
                                        <span class="add-on">
                                            <i id="btnPopover7" rel="popover" data-placement="top" data-trigger="hover" data-delay="500" title="MOTIVO DA PONTUAÇÃO" data-content="Selecione o motivo mais importante para você quando você respondeu à pergunta anterior." class="icon-question-sign"></i>
                                        </span>
                                            <br />
                                        <?php
                                        if(date("Y") <= "2015"){
                                            ?>
                                            <div  class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" id="servico_assistencia" value="servico_assistencia" />Serviço da assistência técnica<br />
                                                </label>
                                            </div>
                                            <div  class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" id="suporte"             value="suporte"             />Suporte e atenção<br />
                                                </label>
                                            </div>
                                            <div  class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" id="tempo_resposta"      value="tempo_resposta"      />Tempo de resposta<br />
                                                </label>
                                            </div>
                                            <div  class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" id="padrao_servico"      value="padrao_servico"      />Padrões de qualidade e serviço<br />
                                                </label>
                                            </div>
                                            <div  class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" id="qualidade_reparo"    value="qualidade_reparo"    />Qualidade de reparação<br />
                                                </label>
                                            </div>
                                            <div  class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" id="tempo_reparo"        value="tempo_reparo"        />Tempo de reparação<br />
                                                </label>
                                            </div>
                                            <div  class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" id="falha_equipamento"   value="falha_equipamento"   />Falha precoce do equipamento<br />
                                                </label>
                                            </div>
                                            <div  class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" id="qualidade_produto"   value="qualidade_produto"   />Qualidade do produto<br />
                                                </label>
                                            </div>
                                            <div  class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" id="atencao_suporte"     value="atencao_suporte"     />Atenção de Suporte ( Via telefone )<br />
                                                </label>
                                            </div>
                                            <div  class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" id="orcamento"           value="orcamento"           />Orçamento de custos e / ou reparação<br />
                                                </label>
                                            </div>
                                            <div  class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" id="acompanhamento"      value="acompanhamento"      />Acompanhamento de reparo<br />
                                                </label>
                                            </div>
                                            <?php
                                        }else{
                                            ?>
                                            <div class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" value="atencao_suporte_telefonico"/>Atenção e suporte (Telefônico)<br />
                                                </label>
                                            </div>
                                            <div class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" value="atencao_suporte_recepcao"/>Atenção e suporte (Recepção da assistência técnica)<br />
                                                </label>
                                            </div>
                                            <div class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" value="falha_precoce_ferramenta"/>Falha precoce da ferramenta<br />
                                                </label>
                                            </div>
                                            <div class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" value="qualidade_produto" />A qualidade do produto<br />
                                                </label>
                                            </div>
                                            <div class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" value="tempo_de_resposta"/>Tempo de resposta<br />
                                                </label>
                                            </div>
                                            <div class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" value="custo_orcamento_reparo"/>Custo e/ou orçamento do reparo<br />
                                                </label>
                                            </div>
                                            <div class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" value="rastreamento_reparacao"/>Rastreamento de reparação<br />
                                                </label>
                                            </div>
                                            <div class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" value="tempo_repado"/>Tempo de reparo<br />
                                                </label>
                                            </div>
                                            <div class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" value="qualidade_reparacao"/>Qualidade da reparação<br />
                                                </label>
                                            </div>
                                            <div class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" value="servico_prestado_centro"/>Serviço prestado pelo centro de serviço<br />
                                                </label>
                                            </div>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                </div>
                            <div class="span1"></div>
                        </div>
                    </div>
                    <br />
                    <div class='row-fluid'>
                        <div class="span1"></div>
                        <div class="span10">
                            <div class='control-group'>
                                8 - Você quer expandir sua pontuação?
                                <span class="add-on">
                                    <i id="btnPopover8" rel="popover" data-placement="top" data-trigger="hover" data-delay="500" title="OUTROS MOTIVOS" data-content="Se as opções na pergunta anterior não foram suficientes, por favor, use suas próprias palavras para descrever a razão para a sua classificação." class="icon-question-sign"></i>
                                </span>
                                <div class='controls controls-row'>
                                    <textarea name='complemento_classificacao' id='complemento_classificacao' value="" style="width:600px;" ></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="span1"></div>
                    </div>
                    <br />
                    <div class="container">
                        <div class='row-fluid'>

                            <div class="span10">
                                <div class='control-group'>
                                    <table style="border: 1px solid #CCC" id="tabela">
                                        <tr>
                                            <th>
                                                Qual é o seu nível de satisfação com relação aos seguintes aspectos.
                                            </th>
                                            <td style="text-align:center;">Totalmente Satisfeito</td>
                                            <td style="text-align:center;">Bastante Satisfeito</td>
                                            <td style="text-align:center;">Neutro</td>
                                            <td style="text-align:center;">Pouco Satisfeito</td>
                                            <td style="text-align:center;">Nada Satisfeito</td>
                                            <td style="text-align:center;vertical-align:bottom;">Numero de dias na assistência
                                                <span class="add-on">
                                                    <i id="btnPopover9" rel="popover" data-placement="top" data-trigger="hover" data-delay="500" title="DIAS DE ATENDIMENTO" data-content="Escolha da lista quantos dias que durou o reparo." class="icon-question-sign"></i>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td nowrap>9 - Tempo de reparo</td>
                                            <td class="td_radio"><input type="radio" name="nota_tempo_reparo" id="nota_tempo_reparo" value="plenamente_satisfeito"   /></td>
                                            <td class="td_radio"><input type="radio" name="nota_tempo_reparo" id="nota_tempo_reparo" value="muito_satisfeito"        /></td>
                                            <td class="td_radio"><input type="radio" name="nota_tempo_reparo" id="nota_tempo_reparo" value="satisfeito"              /></td>
                                            <td class="td_radio"><input type="radio" name="nota_tempo_reparo" id="nota_tempo_reparo" value="pouco_satisfeito"        /></td>
                                            <td class="td_radio"><input type="radio" name="nota_tempo_reparo" id="nota_tempo_reparo" value="insatisfeito"            /></td>
                                            <td rowspan="7" style="vertical-align:top;">
                                                <select id="numero_dias" name="numero_dias" style="width:55px;">
                                                    <option value="1">1</option>
                                                    <option value="2">2</option>
                                                    <option value="3">3</option>
                                                    <option value="4">4</option>
                                                    <option value="5">5</option>
                                                    <option value="6">6</option>
                                                    <option value="7">7</option>
                                                    <option value="8">8</option>
                                                    <option value="mais"> > 8</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td nowrap>10 - Preço do reparo</td>
                                            <td class="td_radio"><input type="radio" name="nota_preco_reparo" id="nota_preco_reparo" value="plenamente_satisfeito"   /></td>
                                            <td class="td_radio"><input type="radio" name="nota_preco_reparo" id="nota_preco_reparo" value="muito_satisfeito"        /></td>
                                            <td class="td_radio"><input type="radio" name="nota_preco_reparo" id="nota_preco_reparo" value="satisfeito"              /></td>
                                            <td class="td_radio"><input type="radio" name="nota_preco_reparo" id="nota_preco_reparo" value="pouco_satisfeito"        /></td>
                                            <td class="td_radio"><input type="radio" name="nota_preco_reparo" id="nota_preco_reparo" value="insatisfeito"            /></td>
                                        </tr>
                                        <tr>
                                            <td nowrap>11 - Qualidade do reparo</td>
                                            <td class="td_radio"><input type="radio" name="nota_qualidade_reparo" id="nota_qualidade_reparo" value="plenamente_satisfeito"   /></td>
                                            <td class="td_radio"><input type="radio" name="nota_qualidade_reparo" id="nota_qualidade_reparo" value="muito_satisfeito"        /></td>
                                            <td class="td_radio"><input type="radio" name="nota_qualidade_reparo" id="nota_qualidade_reparo" value="satisfeito"              /></td>
                                            <td class="td_radio"><input type="radio" name="nota_qualidade_reparo" id="nota_qualidade_reparo" value="pouco_satisfeito"        /></td>
                                            <td class="td_radio"><input type="radio" name="nota_qualidade_reparo" id="nota_qualidade_reparo" value="insatisfeito"            /></td>
                                        </tr>
                                        <tr>
                                            <td nowrap>12 - Atenção do atendente</td>
                                            <td class="td_radio"><input type="radio" name="nota_atencao" id="nota_atencao" value="plenamente_satisfeito"   /></td>
                                            <td class="td_radio"><input type="radio" name="nota_atencao" id="nota_atencao" value="muito_satisfeito"        /></td>
                                            <td class="td_radio"><input type="radio" name="nota_atencao" id="nota_atencao" value="satisfeito"              /></td>
                                            <td class="td_radio"><input type="radio" name="nota_atencao" id="nota_atencao" value="pouco_satisfeito"        /></td>
                                            <td class="td_radio"><input type="radio" name="nota_atencao" id="nota_atencao" value="insatisfeito"            /></td>
                                        </tr>
                                        <tr>
                                            <td nowrap>13 - Explicação do reparo
                                                <span class="add-on">
                                                    <i id="btnPopover13" rel="popover" data-placement="top" data-trigger="hover" data-delay="500" title="EXPLICAÇÃO" data-content="A pessoa que fez a entrega da ferramenta explicou corretamente o trabalho que foi feito?." class="icon-question-sign"></i>
                                                </span>
                                            </td>
                                            <td class="td_radio"><input type="radio" name="nota_explicacao" id="nota_explicacao" value="plenamente_satisfeito"   /></td>
                                            <td class="td_radio"><input type="radio" name="nota_explicacao" id="nota_explicacao" value="muito_satisfeito"        /></td>
                                            <td class="td_radio"><input type="radio" name="nota_explicacao" id="nota_explicacao" value="satisfeito"              /></td>
                                            <td class="td_radio"><input type="radio" name="nota_explicacao" id="nota_explicacao" value="pouco_satisfeito"        /></td>
                                            <td class="td_radio"><input type="radio" name="nota_explicacao" id="nota_explicacao" value="insatisfeito"            /></td>
                                        </tr>
                                        <tr>
                                            <td nowrap>14 - Aspecto visual da Assistência</td>
                                            <td class="td_radio"><input type="radio" name="nota_aspecto" id="nota_aspecto" value="plenamente_satisfeito"   /></td>
                                            <td class="td_radio"><input type="radio" name="nota_aspecto" id="nota_aspecto" value="muito_satisfeito"        /></td>
                                            <td class="td_radio"><input type="radio" name="nota_aspecto" id="nota_aspecto" value="satisfeito"              /></td>
                                            <td class="td_radio"><input type="radio" name="nota_aspecto" id="nota_aspecto" value="pouco_satisfeito"        /></td>
                                            <td class="td_radio"><input type="radio" name="nota_aspecto" id="nota_aspecto" value="insatisfeito"            /></td>
                                        </tr>
                                        <tr>
                                            <td nowrap>15 - Satisfação geral</td>
                                            <td class="td_radio"><input type="radio" name="nota_geral" id="nota_geral" value="plenamente_satisfeito"   /></td>
                                            <td class="td_radio"><input type="radio" name="nota_geral" id="nota_geral" value="muito_satisfeito"        /></td>
                                            <td class="td_radio"><input type="radio" name="nota_geral" id="nota_geral" value="satisfeito"              /></td>
                                            <td class="td_radio"><input type="radio" name="nota_geral" id="nota_geral" value="pouco_satisfeito"        /></td>
                                            <td class="td_radio"><input type="radio" name="nota_geral" id="nota_geral" value="insatisfeito"            /></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            <div class="span1"></div>
                        </div>
                    </div>
                </div>
                <?php
                if (!$_GET["imprimir"]) {
                ?>
                    <br />
                    <p class="tac" style="margin-bottom: 0px;">
                        <button class='btn btn-primary' id="gravar" />Gravar</button>
                        <div class="td_btn_gravar_pergunta"></div>
                        <div class='agradecimentosPesquisa alert alert-success' style='display:none'>
                            <div class="alert alert-success">
                                <strong>Gravado com Sucesso!</strong><br />
                                EM NOME DA <b>Stanley Black&Decker</b>, GOSTARIAMOS DE AGRADECER SUA ATENÇÃO, E DESEJAMOS-LHE UM EXCELENTE DIA.
                            </div>
                        </div>
                        <div class='erroPesquisa alert alert-error' style='display:none'>
                        </div>
                    </p>
                    <br />
                <?php
                }
                ?>
            </div>
        </form>
    </main>
    <footer>
    </footer>
</div>
<script type="text/javascript">
    $('#btnPopover').popover();
    $('#btnPopover6').popover();
    $('#btnPopover7').popover();
    $('#btnPopover8').popover();
    $('#btnPopover9').popover();
    $('#btnPopover13').popover();
</script>
</body>
</html>
