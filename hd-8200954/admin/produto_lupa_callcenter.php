<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

if (isset($_POST['ajax'])) {
    $observacao = null;
    if ($_POST['action'] == 'lista_familia') {
        $sql = "SELECT familia, descricao FROM tbl_familia WHERE fabrica = {$login_fabrica} AND ativo IS TRUE ORDER BY descricao ASC";
        $res = pg_query($con, $sql);
        $rows = pg_num_rows($res);
        if ($rows > 0) {
            $retorno = "<option value=''></option>";
            for ($i = 0; $i < $rows; $i++) {
                $familia   = pg_fetch_result($res, $i, "familia");
                $descricao = utf8_encode(pg_fetch_result($res, $i, "descricao"));

                $retorno .= "<option value='{$familia}'>{$descricao}</option>";
            }
        }
    }elseif ($_POST['action'] == 'lista_marca') {
        $sql = "SELECT marca, nome FROM tbl_marca WHERE fabrica = {$login_fabrica} AND ativo IS TRUE ORDER BY nome ASC";
        $res = pg_query($con, $sql);
        $rows = pg_num_rows($res);
        if ($rows > 0) {
            $retorno = "<option value=''></option>";
            for ($i = 0; $i < $rows; $i++) {
                $marca   = pg_fetch_result($res, $i, "marca");
                utf8_encode($nome = pg_fetch_result($res, $i, "nome"));

                $retorno .= "<option value='{$marca}'>{$nome}</option>";
            }
        }
    }elseif ($_POST['action'] == 'pesquisar') {
        $familia_pesq   = (isset($_POST['familia']) && !empty($_POST['familia'])) ? ' AND p.familia = '.$_POST['familia'] : '';
        $marca_pesq     = (isset($_POST['marca']) && !empty($_POST['marca'])) ? ' AND p.marca = '.$_POST['marca'] : '';
        $referencia_pesq = (isset($_POST['referencia']) && !empty($_POST['referencia'])) ? "AND p.referencia ILIKE '%".trim($_POST['referencia'])."%'" : '';
        $descricao_pesq = (isset($_POST['nome']) && !empty($_POST['nome'])) ? " AND p.descricao ILIKE '%".trim($_POST['nome'])."%'" : '';

        if (empty($familia_pesq) && empty($marca_pesq) && empty($referencia_pesq) && empty($descricao_pesq)) {
            exit(json_encode(array("ok" => null)));
        }

        $where_prod_pricipal = '';
        if (!in_array($login_fabrica, array(169,170,171))) {
            $where_prod_pricipal = 'AND p.produto_principal IS NOT TRUE';
        }

        $sql = "SELECT
                    p.produto,
                    p.referencia,
                    p.descricao,
                    p.garantia,
                    p.voltagem,
                    p.observacao,
                    f.descricao AS familia,
                    JSON_FIELD('garantia_estendida', p.parametros_adicionais) AS garantia_adicional,
                    m.nome AS marca
                FROM tbl_produto p
                    INNER JOIN tbl_familia f ON f.familia = p.familia AND f.fabrica = $login_fabrica
                    LEFT JOIN tbl_marca m ON m.marca = p.marca AND m.fabrica = $login_fabrica
                WHERE p.fabrica_i = {$login_fabrica} {$referencia_pesq} {$familia_pesq} {$marca_pesq} {$descricao_pesq}
                    {$where_prod_pricipal}";

        $res = pg_query($con, $sql);
        $rows = pg_num_rows($res);
        if ($rows > 0) {
            $retorno = "
                <table style='width:100%; border: 1;' cellspacing='1' class='lp_tabela' id='gridRelatorio'>
                <thead>
                <tr>
                <th></th>
                <th>".utf8_encode("Referência")."</th>
                <th>Produto</th>";
            if(in_array($login_fabrica, array(169,170))){
               $retorno .= "<th>Garantia (qtde meses)</th><th>Garantia Adicional</th>";
            }
            $retorno .="</tr>
                </thead>
                <tbody>";
            for ($count=0; $count < $rows; $count++) {
                $cor        = ($count % 2 == 0) ? '#DDD' : '#EEE';
                $produto    = pg_fetch_result($res, $count, "produto");
                $referencia = pg_fetch_result($res, $count, "referencia");
                $descricao  = utf8_encode(pg_fetch_result($res, $count, "descricao"));
                $voltagem   = utf8_encode(pg_fetch_result($res, $count, "voltagem"));
                $garantia   = pg_fetch_result($res, $count, "garantia");

                $retorno .= "<tr bgcolor='$cor'>";
                $retorno .= "<td style='text-align: center;'><input type='radio' name='seleciona-produto' data-referencia='{$referencia}' data-descricao='{$descricao}' data-produto='{$produto}' data-voltagem='{$voltagem}' data-garantia='{$garantia}' ></td>";
                $retorno .= "<td>{$referencia}</td>";
                $retorno .= "<td>{$descricao}</td>";
                if(in_array($login_fabrica, array(169,170))){
                    $garantia_adicional = (int) pg_fetch_result($res, $count, "garantia_adicional");
                    $retorno .= "<td>{$garantia} {$meses}</td><td>{$garantia_adicional}</td>";
                }
                $retorno .= "</tr>";
            }
            $retorno .= '</table>';
        } else {
            exit(json_encode(array("ok" => null)));
        }
    }elseif ($_POST['action'] == 'pesquisa_imagem') {
        include_once 'class/aws/s3_config.php';
        include_once S3CLASS;

        $produto = (int) $_POST['produto'];

        $s3 = new AmazonTC("produto", $login_fabrica);
        $imagem_produto = $s3->getObjectList($produto);
        $imagem_produto = basename($imagem_produto[0]);
        $retorno = $s3->getLink($imagem_produto);

        $sql = "SELECT
                    p.observacao
                FROM tbl_produto p
                    INNER JOIN tbl_familia f ON f.familia = p.familia AND f.fabrica = $login_fabrica
                    LEFT JOIN tbl_marca m ON m.marca = p.marca AND m.fabrica = $login_fabrica
                WHERE p.fabrica_i = {$login_fabrica} AND p.produto = {$produto}";

        $res = pg_query($con, $sql);
        if (pg_num_rows($res) > 0) {
            $observacao = pg_fetch_result($res, 0, "observacao");
        }
    }elseif ($_POST['action'] == 'busca_tipo_atendimento') {
        $produto = (int) $_POST['produto'];
        $sql = "SELECT
                    tbl_linha.deslocamento,
                    tbl_familia.setor_atividade,
                    tbl_linha.linha,
                    tbl_linha.nome AS linha_nome,
                    tbl_familia.black,
                    JSON_FIELD('garantia_estendida', tbl_produto.parametros_adicionais) AS garantia_adicional
                FROM tbl_produto
                    JOIN tbl_linha ON(tbl_produto.linha = tbl_linha.linha AND tbl_linha.fabrica = {$login_fabrica})
                    JOIN tbl_familia ON(tbl_produto.familia = tbl_familia.familia AND tbl_familia.fabrica = {$login_fabrica})
                WHERE tbl_produto.fabrica_i = {$login_fabrica} AND tbl_produto.produto = {$produto}";
        $res = pg_query($con, $sql);

        $deslocamento    = pg_fetch_result($res, 0, "deslocamento");
        $setor_atividade = pg_fetch_result($res, 0, "setor_atividade");
        $linha           = pg_fetch_result($res, 0, "linha");
        $linha_nome      = utf8_encode(pg_fetch_result($res, 0, 'linha_nome'));

        if (in_array($login_fabrica, array(169, 170))) {
            $garantia_estendida = pg_fetch_result($res, 0, "black");
            $garantia_estendida_tempo = (int) pg_fetch_result($res, 0, "garantia_adicional");
        }

        if ($deslocamento == 't') {
            $sql = "SELECT
                        tipo_atendimento
                    FROM tbl_tipo_atendimento
                    WHERE km_google IS TRUE
                        AND fora_garantia IS NOT TRUE
                        AND grupo_atendimento = 'P'
                        AND descricao NOT ILIKE '%Nacionais%'
                        AND fabrica = {$login_fabrica};";
        }else{
            $sql = "SELECT
                        tipo_atendimento
                    FROM tbl_tipo_atendimento
                    WHERE km_google IS NOT TRUE
                        AND fora_garantia IS NOT TRUE
                        AND grupo_atendimento = 'P'
                        AND fabrica = {$login_fabrica};";
        }
        $res = pg_query($con, $sql);

        $retorno = array(
            "tipo_atendimento" => pg_fetch_result($res, 0, "tipo_atendimento"),
            "setor_atividade"  => $setor_atividade,
            "deslocamento"     => $deslocamento,
            "linha"            => $linha,
            "linha_nome"       => $linha_nome
        );

        if (in_array($login_fabrica, array(169, 170))) {
            $retorno["garantia_estendida"] = $garantia_estendida;
            $retorno["garantia_estendida_tempo"] = $garantia_estendida_tempo;
        }
    }

    exit(json_encode(array("ok" => $retorno, 'observacao' => utf8_encode($observacao))));
}

?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
    <head>
        <title> Pesquisa Produto... </title>
        <meta name="Author" content="">
        <meta name="Keywords" content="">
        <meta name="Description" content="">
        <meta http-equiv=pragma content=no-cache>
            <link href="css/estilo_cad_prod.css" rel="stylesheet" type="text/css">
            <link href="css/posicionamento.css" rel="stylesheet" type="text/css">
            <link rel="stylesheet" type="text/css" href="js/thickbox.css" media="screen">
            <script src="js/jquery-1.3.2.js"    type="text/javascript"></script>
            <script src="js/thickbox.js"        type="text/javascript"></script>
            <script src="../plugins/jquery/jquery.tablesorter.min.js" type="text/javascript"></script>
            <script src="../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>

        <style type="text/css">
        .titulo_tabela{
            background-color:#596d9b;
            font: bold 14px "Arial";
            color:#FFFFFF;
            text-align:center;
        }


        .titulo_coluna{
            background-color:#596d9b;
            font: bold 11px "Arial";
            color:#FFFFFF;
            text-align:center;
        }

        table.tabela tr td {
            font-family: verdana;
            font: bold 11px "Arial";
            border-collapse: collapse;
            border:1px solid #596d9b;
        }

        .label-form{
            font-size: 14px;
        }

        .div-descricao-produto {
            height: 50%;
            overflow-y: auto;
        }

        #Result-table{
            max-height: 56%;
            overflow: auto;
            margin-bottom: 10px;
            width: 60%;
            margin-left: 1%;
            float: left;
        }

        #produto-info{
            width: 38%;
            display: none;
            height: 60%;
            float: right;
        }

        #produto-foto{
            border: 1px solid #ccc;
            text-align: center;
            height: 196px;
        }

        #Result-descricao{
            border: 1px solid #ccc;
            height: 102px;
            border-top: 0px;
            border-bottom: 0px;
        }

        #selecionar-produto{
            text-align: center;
            padding: 0px;
            border-top: 1px solid #ccc;
            width: 100%;
            float: left;
            display: none;
        }

        #msg_erro{
            display: none;
            width: 100%;
        }

        #carregando{
            display: none;
            height: 30%;
            width: 20%;
            vertical-align: middle;
            margin-top: 14%;
        }

        .titulo-div{
            background-color: #596d9b;
            color: white;
            font-size: 16px;
            padding: 5px;
            text-align: center;
        }

        #Result-img{
            width: auto;
            max-height: 169px;
            max-width: 160px;
        }

        #Descricao-prod{
            padding: 5px;
            font-size: 14px;
            height: 62px;
            overflow: auto;
        }
        </style>
        <style>
        @import "../css/lupas/lupas.css";
        body {
            margin: 0;
            font-family: Arial, Verdana, Times, Sans;
            background: #fff;
        }
        </style>
    </head>
    <body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>
        <div class="lp_header">
            <?php if (in_array($login_fabrica, array(169, 170))) { ?>
            <img src="../logos/logo_midea.png" style="width: 28%;margin-top: 10px;">
            <?php } ?>
            <a href='' onclick='window.parent.Shadowbox.close();' style='border: 0;'>
                <img src='css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar' />
            </a>
        </div>
        <br />
        <div class='lp_nova_pesquisa' style="text-align: center;">
            <form id="form-pesquisa" name="nova_pesquisa">
                <label class='label-form'>Família: </label>
                <select name="familia" id="familia" style="max-width: 100px;" >
                </select>

                <label class='label-form'>Referência: </label>
                <input type="text" name="referencia" value="<?=$referencia;?>" style="width: 100px;" />

                <label class='label-form'><?=(in_array($login_fabrica, array(169,170))) ? 'Descrição:' : 'Nome:'?> </label>
                <input type="text" name="nome" value="<?=$nome;?>" style="width: 100px;" />

                <?php if (in_array($login_fabrica, array(169, 170))) { ?>
                <label class='label-form'>Marca: </label>
                <select name="marca" id="marca" style="max-width: 100px;">
                </select>
                <?php } ?>

                <button type="button" id="btn-pesquisa" name="pesquisar">Pesquisar</button>
                <img id='loading' src="imagens/loading_img.gif" style="z-index:11;height: 14px; width: 18px; display: none;" />
            </form>
        </div>
        <br />
        <div id='msg_erro'>
            <div class="lp_msg_erro">Nenhum Produto encontrado</div>
        </div>
        <div id='Result-table'></div>
        <div id="produto-info">
            <div id="produto-foto">
                <h4 class='titulo-div'>Foto</h4>
                <img id='carregando' src="imagens/loading_img.gif">
                <img id="Result-img" style="display: none;" src=''>
            </div>
            <div id='Result-descricao'>
                <h4 class='titulo-div'>Características do Produto</h4>
                <div id='Descricao-prod'></div>
            </div>
        </div>
        <div id="selecionar-produto">
            <button type="button" name="btn_selecionar" id="btn_selecionar" style="margin: 10px; padding: 5px;">Selecionar Produto</button>
        </div>
    </body>
</html>
<script type="text/javascript">
    $(function(){
        ajax_busca('familia', { ajax: 'sim', action: 'lista_familia' });
        if (typeof $('#marca') == 'object') {
            ajax_busca('marca', { ajax: 'sim', action: 'lista_marca' });
        }

        $('#btn-pesquisa').on('click', function(){
            $('#Result-img').attr("src", "").hide();
            $('#Descricao-prod').html("");
            ajax_busca('Result-table', $('#form-pesquisa').serialize()+"&ajax=sim&action=pesquisar");
        });

        $(document).on('click', '#btn_selecionar', function(){
            $('.lp_tabela tr input[name=seleciona-produto]').each(function(i){
                if ($(this).is(':checked')) {
                    var produto    = $(this).data('produto');
                    var referencia = $(this).data('referencia');
                    var descricao  = $(this).data('descricao');
                    var voltagem   = $(this).data('voltagem');
                    var garantia   = $(this).data('garantia');

                    $.ajax({
                        url: window.location.href,
                        method: 'POST',
                        data: { ajax: 'sim', action: 'busca_tipo_atendimento', produto: $(this).data('produto') },
                        timeout: 10000
                    }).fail(function(){
                        alert('Ocorreu um problema ao tentar selecionar o registro. Tente novamente!');
                    }).done(function(data){
                        data = JSON.parse(data);
                        if (data.ok !== undefined) {
                            data.ok.produto    = produto;
                            data.ok.referencia = referencia;
                            data.ok.descricao  = descricao;
                            data.ok.voltagem   = voltagem;
                            data.ok.garantia   = garantia;
                            window.parent.retorna_prod_generico(data.ok);
                            window.parent.Shadowbox.close();
                        }else{
                            alert('Ocorreu um problema ao tentar selecionar o registro. Tente novamente!');
                        }
                    });
                }
            });
        });

        $(document).on('click', 'input[name=seleciona-produto]', function(){
            $('.lp_tabela tr').each(function(i){
                if (i % 2 == 0) {
                    $(this).css('background-color', '#EEE');
                }else{
                    $(this).css('background-color', '#DDD');
                }
            });
            $(this).parent().parent().css('background-color', '#79cc78');

            $('#Descricao-prod').html("");
            $('#Result-img').attr("src", "").hide();
            $('#carregando').show();
            $.ajax({
                url: window.location.href,
                method: 'POST',
                data: { ajax: 'sim', action: 'pesquisa_imagem', produto: $(this).data('produto') },
                timeout: 8000
            }).fail(function(){
                alert('Não foi possível carregar a imagem!');
            }).done(function(data){
                data = JSON.parse(data);
                if (data.observacao !== '') {
                    $('#Descricao-prod').html(data.observacao);
                }else{
                    $('#Descricao-prod').html('<div class="lp_msg_erro">Sem descrição</div>');
                }
                $('#carregando').hide();
                if (data.ok !== null && data.ok !== undefined && data.ok !== '' && data.ok !== false) {
                    $('#Result-img').attr("src", data.ok).show();
                }else{
                    $('#Result-img').attr("src", "../imagens/sem_imagem.jpg").show();
                }
            });
        });
    });

    function ajax_busca(campo, data){
        $('#loading').show();
        $.ajax({
            url: window.location.href,
            method: 'POST',
            data: data,
            timeout: 8000
        }).fail(function(){
            $('#loading').hide();
            alert('Não foi possível carregar o campo '+campo+'.');
        }).done(function(data){
            data = JSON.parse(data);
            if (data.ok !== null) {
                $('#'+campo).html(data.ok);
            }else{
                $('#'+campo).html('');
            }
            $('#loading').hide();

            if (campo == 'Result-table') {
                if (data.ok !== '' && data.ok !== null) {
                    $('#produto-info').show();
                    $('#selecionar-produto').show();
                    $('#msg_erro').hide();
                }else{
                    $('#produto-info').hide();
                    $('#selecionar-produto').hide();
                    if (data.ok == null) {
                     $('#msg_erro').show();
                  }
                }
            }
        });
    }
</script>
