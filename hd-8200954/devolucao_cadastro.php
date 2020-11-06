<?php
include __DIR__.'/dbconfig.php';
include __DIR__.'/includes/dbconnect-inc.php';

/*ini_set("display_errors", 1);
error_reporting(E_ALL);*/
include __DIR__.'/autentica_usuario.php';

include_once 'helpdesk/mlg_funciones.php';
include __DIR__.'/funcoes.php';

include_once S3CLASS;

$s3 = new AmazonTC("devolucao", $login_fabrica);

function limpaCPF_CNPJ($valor){
    $valor = trim($valor);
    $valor = str_replace(".", "", $valor);
    $valor = str_replace(",", "", $valor);
    $valor = str_replace("-", "", $valor);
    $valor = str_replace("/", "", $valor);
    return $valor;
}

if (isset($_POST["ajax_busca_defeito_peca"])) {
    $retorno = "";
    $peca = $_POST['peca'];
    $posicao = $_POST['posicao'];
    $id_defeito_selecionado = $_POST['id'];

    $sql = "
        SELECT  tbl_peca.peca,
                tbl_defeito.descricao       AS defeito_descricao,
                tbl_defeito.defeito         AS defeito_id       ,
                tbl_defeito.codigo_defeito                ,
                tbl_peca_defeito.ativo
        FROM    tbl_peca_defeito
        JOIN    tbl_defeito USING(defeito)
        JOIN    tbl_peca    ON  tbl_peca.peca = tbl_peca_defeito.peca
                            AND tbl_peca.fabrica = $login_fabrica
                            AND tbl_peca.referencia = '$peca'
        WHERE   tbl_defeito.ativo IS TRUE
  ORDER BY      tbl_peca.descricao,
                tbl_defeito.descricao";
//         echo $sql;exit;
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $retorno .= "<option value=''>Selecione</option>";
        for ($x=0;$x < pg_num_rows($res);$x++) {

            $defeito_id = pg_fetch_result($res, $x, "defeito_id");
            $descricao = pg_fetch_result($res, $x, "defeito_descricao");

            if ($defeito_id == $id_defeito_selecionado) {
                $selected = "selected";
            } else {
                $selected = "";
            }

            $retorno .= "<option $selected value='{$defeito_id}'>{$descricao}</option>";
        }

    } else {
        $retorno = "<option value=''>Sem defeitos!</option>";
    }


    exit($retorno);
}

if (isset($_POST["ajax_busca_servico_realizado"])) {
    $retorno = "";
    $produto = $_POST['produto'];
    $id_servico_selecionado = $_POST['id'];

    $sql = "SELECT  tbl_servico_realizado.servico_realizado,
                    tbl_servico_realizado.descricao,
                    tbl_servico_realizado.linha
            FROM    tbl_servico_realizado
            WHERE   tbl_servico_realizado.ativo IS TRUE
            AND     tbl_servico_realizado.servico_realizado NOT IN (4247,4289,733)
            AND     fabrica = $login_fabrica
      ORDER BY      descricao";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $retorno .= "<option value=''>Selecione</option>";
        for ($x=0;$x < pg_num_rows($res);$x++) {

            $servico_id = pg_fetch_result($res, $x, "servico_realizado");
            $descricao = pg_fetch_result($res, $x, "descricao");

            if ($id_servico_selecionado == $servico_id) {
                $selected = "selected";
            } else {
                $selected = "";
            }

            $retorno .= "<option $selected value='{$servico_id}'>{$descricao}</option>";
        }

    } else {
        $retorno = "<option value=''>Sem serviços!</option>";
    }

    exit($retorno);
}



/**
* Cria a chave do anexo
*/
if (!strlen(getValue("anexo_chave"))) {
    $anexo_chave = sha1(date("Ymdhi")."{$login_fabrica}".(($areaAdmin === true) ? $login_admin : $login_posto));
} else {
    $anexo_chave = getValue("anexo_chave");
}
/**
* Inclui o arquivo no s3
*/
if (isset($_POST["ajax_anexo_upload"])) {
    $posicao = $_POST["anexo_posicao"];
    $chave   = $_POST["anexo_chave"];

    $arquivo = $_FILES["anexo_upload_{$posicao}"];

    $ext = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));

    if ($ext == "jpeg") {
        $ext = "jpg";
    }

    if (strlen($arquivo["tmp_name"]) > 0) {
        if (!in_array($ext, array("png", "jpg", "jpeg", "bmp", "pdf", "doc", "docx"))) {
            $retorno = array("error" => utf8_encode("Arquivo em formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp, pdf, doc, docx"));
        } else {
            $arquivo_nome = "{$chave}_{$posicao}";

            $s3->tempUpload("{$arquivo_nome}", $arquivo);

            if($ext == "pdf"){
                $link = "imagens/pdf_icone.png";
            } else if(in_array($ext, array("doc", "docx"))) {
                $link = "imagens/docx_icone.png";
            } else {
                $link = $s3->getLink("thumb_{$arquivo_nome}.{$ext}", true);
            }

            $href = $s3->getLink("{$arquivo_nome}.{$ext}", true);

            if (!strlen($link)) {
                $retorno = array("error" => utf8_encode("Erro ao anexar arquivo"));
            } else {
                $retorno = array("link" => $link, "arquivo_nome" => "{$arquivo_nome}.{$ext}", "href" => $href, "ext" => $ext);
            }
        }
    } else {
        $retorno = array("error" => utf8_encode("Erro ao anexar arquivo"));
    }

    $retorno["posicao"] = $posicao;

    exit(json_encode($retorno));
}

/**
* Excluir anexo
*/
if (isset($_POST["ajax_anexo_exclui"])) {
    $anexo_nome_excluir = $_POST['anexo_nome_excluir'];
    $numero_processo = $_POST['numero_processo'];

    $sql_ex = "SELECT data_input FROM tbl_processo WHERE numero_processo = '$numero_processo';";
    $res_ex = pg_query($con,$sql_ex);
    if (pg_num_rows($res_ex)> 0) {
        $data_ex = pg_fetch_result($res_ex, 0, data_input);

        list($data_ex, $hora_ex) = explode(" ",$data_ex);
        list($ano_ex,$mes_ex,$dia_ex) = explode("-",$data_ex) ;

    }

    if (count($anexo_nome_excluir) > 0) {
        $s3->deleteObject($anexo_nome_excluir, false, $ano_ex, $mes_ex);
        $retorno = array("ok" => utf8_encode("Excluído com sucesso!"));
    }else{
        $retorno = array("error" => utf8_encode("Erro ao excluir arquivo"));
    }
     exit(json_encode($retorno));
}

$anexo_processo  = $_POST["anexo"];
$anexo_processo_s3  = $_POST["anexo_s3"];

$btn_acao = $_POST['gravar'];

$os_laudo      = trim($_REQUEST["os_laudo"]);
$os_laudo_info = trim($_REQUEST["os_laudo_info"]);

if (!empty($btn_acao)) {
    $data_recebimento  = trim($_POST["data_recebimento"]);

    $aparencia_produto = (empty(trim($_POST["aparencia_produto"]))) ? "null" : trim($_POST["aparencia_produto"]);

    $nota_fiscal       = trim($_POST["nota_fiscal"]);
    $emissao_nota      = (!empty($_POST["emissao_nota"])) ? "'".trim($_POST["emissao_nota"])."'" : "null";
    $motivo_analitico  = trim($_POST["motivo_analitico"]);
    $motivo_sintetico  = trim($_POST["motivo_sintetico"]);
    $senha_autorizacao = trim($_POST["senha_autorizacao"]);
    $nome_cliente      = trim($_POST["nome_cliente"]);
    $cpf_cnpj          = trim($_POST["cpf_cnpj"]);
    $telefone          = trim($_POST["telefone"]);
    $celular           = trim($_POST["celular"]);
    $produto_id         = trim($_POST["produto_id"]);
    $produto_referencia = trim($_POST["produto_referencia"]);
    $produto_descricao  = trim($_POST["produto_descricao"]);
    $voltagem           = trim($_POST["voltagem"]);
    $numero_serie       = trim($_POST["numero_serie"]);


    function validaNumeroSerie($numero_serie, $referencia)
    {
        global $con, $login_fabrica;

        $sql = "SELECT * from tbl_numero_serie where fabrica = $login_fabrica and referencia_produto = '$referencia' and serie = '$numero_serie'";
        $res = pg_query($con, $sql);

        if(pg_num_rows($res) ==0) {
            return false;
        }
        return true;
    }


    if(!validaNumeroSerie($numero_serie, $produto_referencia)){
        $msg_erro['msg'] = "Número de série inválido. ";
        $msg_erro['campos'][] = "numero_serie";
    }

    
    $sql_os_laudo_serie = "SELECT serie FROM tbl_os_laudo WHERE serie = '$numero_serie' and fabrica = $login_fabrica";
    $res_os_laudo_serie = pg_query($con, $sql_os_laudo_serie);
    if(pg_num_rows($res_os_laudo_serie)>0){
        $msg_erro['msg'] = "Já existe uma devolução com esse número de série.";
        $msg_erro['campos'][] = "numero_serie";   
    }


    $defeito_constatado     = (!empty($_POST["defeito_constatado"])) ? "'".trim($_POST["defeito_constatado"])."'" : "null";
    $solucao                = (!empty($_POST["solucao"])) ? "'".trim($_POST["solucao"])."'" : "null";
    $analise_produto        = (empty(trim($_POST["analise_produto"])))  ? "null" : trim($_POST["analise_produto"]);
    $responsavel_analise    = trim($_POST["responsavel_analise"]);

    $posicao = 0;
    foreach ($_POST['produto_pecas'] as $value) {
        $posicao_acrescenta = $posicao + 1;
        $peca_id =      $value["id"];
        $referencia =   $value["referencia"];
        $descricao =    $value["descricao"];
        $qtde   =       $value["qtde"];
        $defeito_peca = $value["defeito_peca"];
        $servico_peca = $value["servico_peca"];

        if (!empty($peca_id)) {
            if (empty($referencia)) {
                $msg_erro['msg'] ="Por favor preencha a referência da".$posicao_acrescenta."ª peça";
                $msg_erro['peca'][$posicao][] = "referencia";
            }

            if (empty($descricao)) {
                $msg_erro['msg'] ="Por favor preencha a descrição da ".$posicao_acrescenta."ª peça";
                $msg_erro['peca'][$posicao][] = "descricao";
            }

            if (empty($qtde)) {
                $msg_erro['msg'] ="Por favor preencha a quantidade da ".$posicao_acrescenta."ª peça";
                $msg_erro['peca'][$posicao][] = "qtde";
            }

            if (empty($defeito_peca)) {
                $msg_erro['msg'] ="Por favor preencha o defeito da ".$posicao_acrescenta."ª peça";
                $msg_erro['peca'][$posicao][] = "defeito_peca";
            }
        }

        $posicao++;
    }


    if (empty($data_recebimento)) {
        $msg_erro['msg'] = "Por favor preencha todos os campos obrigatórios";
        $msg_erro['campos'][] = "data_recebimento";
    }

    /*if (empty($aparencia_produto)) {
        $msg_erro['msg'] ="Por favor preencha todos os campos obrigatórios";
        $msg_erro['campos'][] = "aparencia_produto";
    }*/

    if (empty($nota_fiscal)) {
        $msg_erro['msg'] ="Por favor preencha todos os campos obrigatórios";
        $msg_erro['campos'][] = "nota_fiscal";
    }

    if (empty($emissao_nota) or $emissao_nota == 'null') {
        $msg_erro['msg'] ="Por favor preencha todos os campos obrigatórios";
        $msg_erro['campos'][] = "emissao_nota";
    }

    if (empty($motivo_analitico)) {
        $msg_erro['msg'] ="Por favor preencha todos os campos obrigatórios";
        $msg_erro['campos'][] = "motivo_analitico";
    }

    if (empty($motivo_sintetico)) {
        $msg_erro['msg'] ="Por favor preencha todos os campos obrigatórios";
        $msg_erro['campos'][] = "motivo_sintetico";
    }

    if (empty($senha_autorizacao)) {
        $msg_erro['msg'] ="Por favor preencha todos os campos obrigatórios";
        $msg_erro['campos'][] = "senha_autorizacao";
    }

    if (empty($nome_cliente)) {
        $msg_erro['msg'] ="Por favor preencha todos os campos obrigatórios";
        $msg_erro['campos'][] = "nome_cliente";
    }

    if (empty($cpf_cnpj)) {
        $msg_erro['msg'] ="Por favor preencha todos os campos obrigatórios";
        $msg_erro['campos'][] = "cpf_cnpj";
    }

    if (empty($produto_referencia)) {
        $msg_erro['msg'] ="Por favor preencha todos os campos obrigatórios";
        $msg_erro['campos'][] = "produto_referencia";
    }

    if (empty($produto_descricao)) {
        $msg_erro['msg'] ="Por favor preencha todos os campos obrigatórios";
        $msg_erro['campos'][] = "produto_descricao";
    }

    if (empty($numero_serie)) {
        $msg_erro['msg'] ="Por favor preencha todos os campos obrigatórios";
        $msg_erro['campos'][] = "numero_serie";
    }

    /*HD - 4093050*/
    /*if ($login_fabrica == 24 && (empty($analise_produto) || $analise_produto == "null")) {
        $msg_erro['msg'] ="Por favor preencha todos os campos obrigatórios";
        $msg_erro['campos'][] = "analise_produto";
    }*/

    /*if (empty($analise_produto)) {
        $msg_erro['msg'] ="Por favor preencha todos os campos obrigatórios";
        $msg_erro['campos'][] = "analise_produto";
    }*/

    if (count($msg_erro["msg"]) == 0) {
        pg_query($con, "BEGIN TRANSACTION");

        $cpf_cnpj_banco = limpaCPF_CNPJ($cpf_cnpj);

        if (empty($os_laudo)) {
            $sql = "INSERT INTO tbl_os_laudo (
                        fabrica,
                        data_recebimento,
                        data_digitacao,
                        nota_fiscal,
                        data_nf,
                        motivo_analitico,
                        motivo_sintetico,
                        senha_autorizacao,
                        nome_cliente,
                        cpf_cliente,
                        fone_cliente,
                        celular_cliente,
                        produto,
                        serie,
                        defeito_constatado,
                        solucao,
                        analise_produto,
                        responsavel_analise,
                        aparencia_produto
                    ) VALUES (
                        $login_fabrica,
                        '$data_recebimento',
                        current_timestamp,
                        '$nota_fiscal',
                        $emissao_nota,
                        $motivo_analitico,
                        $motivo_sintetico,
                        '$senha_autorizacao',
                        '$nome_cliente',
                        '$cpf_cnpj_banco',
                        '$telefone',
                        '$celular',
                        $produto_id,
                        '$numero_serie',
                        $defeito_constatado,
                        $solucao,
                        $analise_produto,
                        '$responsavel_analise',
                        '$aparencia_produto'
                    ) RETURNING os_laudo";
            $res = pg_query($con,$sql);

            $id_os_laudo = pg_fetch_result($res,0,"os_laudo");
        } else {
            $sql = "
                UPDATE  tbl_os_laudo
                SET     data_recebimento    = '$data_recebimento',
                        nota_fiscal         = '$nota_fiscal',
                        data_nf             = $emissao_nota,
                        motivo_analitico    = $motivo_analitico,
                        motivo_sintetico    = $motivo_sintetico,
                        senha_autorizacao   = '$senha_autorizacao',
                        nome_cliente        = '$nome_cliente',
                        cpf_cliente         = '$cpf_cnpj_banco',
                        fone_cliente        = '$telefone',
                        celular_cliente     = '$celular',
                        produto             = $produto_id,
                        serie               = '$numero_serie',
                        defeito_constatado  = $defeito_constatado,
                        solucao             = $solucao,
                        analise_produto     = $analise_produto,
                        responsavel_analise = '$responsavel_analise',
                        aparencia_produto   = '$aparencia_produto'
                WHERE   fabrica     = $login_fabrica
                AND     os_laudo    = $os_laudo

            ";
            $res = pg_query($con,$sql);
            $id_os_laudo = $os_laudo;
        }

        $sqlProcura = "
            DELETE
            FROM    tbl_os_laudo_peca
            WHERE   os_laudo = $id_os_laudo
        ";
        $resProcura = pg_query($con,$sqlProcura);

        foreach ($_POST['produto_pecas'] as $value) {
            $peca_id =      $value["id"];
            $qtde   =       $value["qtde"];
            $defeito_peca = $value["defeito_peca"];
            $servico_peca = (!empty($value["servico_peca"])) ? $value["servico_peca"]: "null";

            if (!empty($peca_id)) {

                $sql = "INSERT INTO tbl_os_laudo_peca (
                            os_laudo,
                            peca,
                            qtde,
                            defeito,
                            servico_realizado,
                            data_digitacao
                        ) VALUES (
                            $id_os_laudo,
                            $peca_id,
                            $qtde,
                            $defeito_peca,
                            $servico_peca,
                            CURRENT_TIMESTAMP
                        )";
    //                     exit(nl2br($sql));
                pg_query($con,$sql);

            }
        }

        if (pg_last_error()) {
            pg_query($con,"ROLLBACK TRANSACTION");
            $msg_erro['msg'] = "Houve um erro na execução do cadastro";
        } else {
            pg_query($con,"COMMIT TRANSACTION");
            if (count($anexo_processo)>0) {
                    $sql_inp = "SELECT data_digitacao FROM tbl_os_laudo WHERE os_laudo = '$id_os_laudo'";
                    $res_inp = pg_query($con,$sql_inp);
                    if (pg_num_rows($res_inp)> 0) {
                        $data_inp = pg_fetch_result($res_inp, 0, 'data_digitacao');

                        list($data_inp, $hora_inp) = explode(" ",$data_inp);
                        list($ano,$mes,$dia) = explode("-",$data_inp) ;

                    }
                    //list($dia, $mes, $ano) =  explode("/",date('d/m/Y')) ;

                    $arquivos = array();

                    foreach ($anexo_processo as $key => $value) {
                        if ($anexo_processo_s3[$key] != "t" && strlen($value) > 0) {
                            $ext = preg_replace("/.+\./", "", $value);
                            $arquivos[] = array(
                                "file_temp" => $value,
                                "file_new"  => "{$login_fabrica}_{$id_os_laudo}_{$key}.{$ext}"
                            );
                        }
                    }

                    if (count($arquivos) > 0) {
                        $s3->moveTempToBucket($arquivos, $ano, $mes, false);
                    }
                }


            header('Location: informacao_devolucao.php?os_laudo='.$id_os_laudo.'');
            unset($_POST);
        }

    }

} else if (!empty($_GET["os_laudo"]) || !empty($_GET["os_laudo_info"])) {

    $os_pesquisa = (empty($os_laudo_info)) ? $os_laudo : $os_laudo_info;

    $sql_laudo = "
        SELECT  tbl_os_laudo.os_laudo,
                to_char(tbl_os_laudo.data_digitacao, 'DD/MM/YYYY HH24:MI') as data_digitacao,
                tbl_os_laudo.nome_cliente,
                tbl_os_laudo.data_recebimento,
                tbl_os_laudo.nota_fiscal,
                tbl_os_laudo.aparencia_produto,
                tbl_os_laudo.data_nf,
                tbl_os_laudo.senha_autorizacao,
                tbl_os_laudo.nome_cliente,
                tbl_os_laudo.cpf_cliente,
                tbl_os_laudo.fone_cliente,
                tbl_os_laudo.celular_cliente,
                tbl_os_laudo.serie,
                tbl_os_laudo.responsavel_analise,
                tbl_os_laudo.motivo_analitico,
                tbl_os_laudo.motivo_sintetico,
                tbl_os_laudo.defeito_constatado,
                tbl_os_laudo.solucao,
                tbl_os_laudo.analise_produto,
                tbl_produto.produto,
                tbl_produto.referencia,
                tbl_produto.descricao,
                tbl_produto.voltagem
        FROM    tbl_os_laudo
        JOIN    tbl_produto USING(produto)
        WHERE   tbl_os_laudo.fabrica    = $login_fabrica
        AND     tbl_produto.fabrica_i   = $login_fabrica
        AND     tbl_os_laudo.os_laudo   = $os_pesquisa";

    $res_laudo = pg_query($con,$sql_laudo);

    $data_recebimento       = trim(pg_result($res_laudo,0,'data_recebimento'));
    $nota_fiscal            = trim(pg_result($res_laudo,0,'nota_fiscal'));
    $aparencia_produto      = trim(pg_result($res_laudo,0,'aparencia_produto'));
    $nome_cliente           = trim(pg_result($res_laudo,0,'nome_cliente'));
    $cpf_cliente            = trim(pg_result($res_laudo,0,'cpf_cliente'));
    $fone_cliente           = trim(pg_result($res_laudo,0,'fone_cliente'));
    $celular_cliente        = trim(pg_result($res_laudo,0,'celular_cliente'));
    $data_nf                = trim(pg_result($res_laudo,0,'data_nf'));
    $motivo_analitico_db    = trim(pg_result($res_laudo,0,'motivo_analitico'));
    $motivo_sintetico_db    = trim(pg_result($res_laudo,0,'motivo_sintetico'));
    $senha_autorizacao      = trim(pg_result($res_laudo,0,'senha_autorizacao'));
    if (empty($_GET["os_laudo_info"])) {
        $produto_referencia     = trim(pg_result($res_laudo,0,'referencia'));
        $produto                = trim(pg_result($res_laudo,0,'produto'));
        $produto_descricao      = trim(pg_result($res_laudo,0,'descricao'));
        $produto_voltagem       = trim(pg_result($res_laudo,0,'voltagem'));
        $defeito_constatado     = trim(pg_result($res_laudo,0,'defeito_constatado'));
        $solucao_mb                = trim(pg_result($res_laudo,0,'solucao'));
        $analise_produto_mb        = trim(pg_result($res_laudo,0,'analise_produto'));
        $serie                  = trim(pg_result($res_laudo,0,'serie'));
    }
}

$title = "CADASTRO DE DEVOLUÇÕES";
include __DIR__.'/cabecalho_new.php';

$plugins = array(
   "datepicker",
   "shadowbox",
   "maskedinput",
   "alphanumeric",
   "ajaxform",
   "fancyzoom",
   "price_format",
   "tooltip",
   "autocomplete"
);

include __DIR__.'/admin/plugin_loader.php';

?>
<script type="text/javascript">
    $(function() {

    $("#celular").mask("(99)99999-9999",{placeholder:""});
    $("#telefone").mask("(99)9999-9999",{placeholder:""});

    $(".cpf_cnpj_radio").change(function(){
        var tipo = $(this).val();
        $("#input_cnpj_cpf").unmask();
        if(tipo == 'cnpj'){
            $("#input_cnpj_cpf").mask("99.999.999/9999-99",{placeholder:""});
        }else{
            $("#input_cnpj_cpf").mask("999.999.999-99",{placeholder:""});
        }
    });

        /**
    * Eventos para anexar/excluir imagem
    */
    $("button.btn_acao_anexo").click(function(){
        var name = $(this).attr("name");
        if (name == "anexar") {
            $(this).trigger("anexar_s3", [$(this)]);
        }else{
            $(this).trigger("excluir_s3", [$(this)]);
        }
    });

    $("button.btn_acao_anexo").bind("anexar_s3",function(){
        console.log("1");
        var posicao = $(this).attr("rel");

        var button = $(this);

        $("input[name=anexo_upload_"+posicao+"]").click();
    });

    $("button.btn_acao_anexo").bind("excluir_s3",function(){
        var posicao = $(this).attr("rel");
        var numero_processo = $("#num_processo").val();

        var button = $(this);
        var nome_an_p = $("input[name='anexo["+posicao+"]']").val();
        // alert(nome_an_p);
        // return;
        $.ajax({
            url: "devolucao_cadastro.php",
            type: "POST",
            data: { ajax_anexo_exclui: true, anexo_nome_excluir: nome_an_p, numero_processo: numero_processo },
            beforeSend: function() {
                $("#div_anexo_"+posicao).find("button").hide();
                $("#div_anexo_"+posicao).find("img.anexo_thumb").hide();
                $("#div_anexo_"+posicao).find("img.anexo_loading").show();
            },
            complete: function(data) {
                data = $.parseJSON(data.responseText);

                if (data.error) {
                    alert(data.error);
                } else {
                    $("#div_anexo_"+posicao).find("a[target='_blank']").remove();
                    $("#baixar_"+posicao).remove();
                    $(button).text("Anexar").attr({
                        id:"anexar_"+posicao,
                        class:"btn btn-mini btn-primary btn-block",
                        name: "anexar"
                    });
                    $("input[name='anexo["+posicao+"]']").val("f");
                    $("#div_anexo_"+posicao).prepend('<img class="anexo_thumb" style="width: 100px; height: 90px;" src="imagens/imagem_upload.png">');

                    $("#div_anexo_"+posicao).find("img.anexo_loading").hide();
                    $("#div_anexo_"+posicao).find("button").show();
                    $("#div_anexo_"+posicao).find("img.anexo_thumb").show();
                    alert(data.ok);
                }

            }
        });
    });




            /**
    * Eventos para anexar imagem
    */
        $("form[name=form_anexo]").ajaxForm({
            complete: function(data) {
                data = $.parseJSON(data.responseText);

                if (data.error) {
                    alert(data.error);
                } else {
                    var imagem = $("#div_anexo_"+data.posicao).find("img.anexo_thumb").clone();
                    $(imagem).attr({ src: data.link });

                    $("#div_anexo_"+data.posicao).find("img.anexo_thumb").remove();

                    var link = $("<a></a>", {
                        href: data.href,
                        target: "_blank"
                    });

                    $(link).html(imagem);

                    $("#div_anexo_"+data.posicao).prepend(link);

                    if ($.inArray(data.ext, ["doc", "pdf", "docx"]) == -1) {
                        setupZoom();
                    }

                    $("#div_anexo_"+data.posicao).find("input[rel=anexo]").val(data.arquivo_nome);
                }

                $("#div_anexo_"+data.posicao).find("img.anexo_loading").hide();
                $("#div_anexo_"+data.posicao).find("button").show();
                $("#div_anexo_"+data.posicao).find("img.anexo_thumb").show();
            }
        });

        $("input[name^=anexo_upload_]").change(function() {
            var i = $(this).parent("form").find("input[name=anexo_posicao]").val();

            $("#div_anexo_"+i).find("button").hide();
            $("#div_anexo_"+i).find("img.anexo_thumb").hide();
            $("#div_anexo_"+i).find("img.anexo_loading").show();

            $(this).parent("form").submit();
        });
    //fim anexo

        $.datepickerLoad(Array("data_recebimento", "emissao_nota"));
        Shadowbox.init();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });

        $("#trocar_produto").click(function () {
            $("#div_alterar_produto").hide();
            $("#vista_explodida").hide();

            $("#produto_referencia").prop("disabled", false);
            $("#produto_descricao").prop("disabled", false);

            $("#produto_referencia").val("");
            $("#produto_descricao").val("");
            $("#defeito_constatado").html("<option value=''>Selecione</option>");
            $("#voltagem").val("");

        });


        $(document).on('click', "button[name^=remove_peca_]", function(){
            var posicao = $(this).attr("rel");

            $("#peca_id_"+posicao).val("");
            $("#pecas_referencia_"+posicao).find(".produto_referencia").val("");
            $("#pecas_descricao_"+posicao).find(".produto_descricao").val("");
            $("#qtde_"+posicao).find(".produto_qtde").val("");

            $("#defeito_"+posicao).find("#defeito_peca_"+posicao).html("<option value='' selected>Selecione</option>");
            $("#servico_"+posicao).find("#servico_peca_"+posicao).html("<option value='' selected>Selecione</option>");

            $("#btn_excluir_"+posicao).val("");
            $(this).hide();
        });

        $(document).on("click", "span[rel=cliente_laudo]", function() {
            var parametros_lupa = ["consumidor_nome", "cpf_cnpj", "devolucao"];
            var nome_cliente = $("#nome_cliente").val();
            var cpf_cnpj = $("#input_cnpj_cpf").val();

            if(nome_cliente.length == 0 && cpf_cnpj.length == 0){
                alert("Informe toda ou parte da informação para pesquisar!");
            }else{
                $(this).next().attr({ nome_consumidor: nome_cliente , cpf_cnpj:cpf_cnpj, devolucao:'sim' });
                $.lupa($(this), parametros_lupa);
            }
        });

        $(document).on("click", "span[rel=lupa_peca]", function() {
            var parametros_lupa_peca = ["produto", "posicao"];

            var defeito_constatado = $("#defeito_constatado").val();

        <?php if (!($login_fabrica == 24 && $login_posto == 428725)) {?>
            if (defeito_constatado.length == 0) {
                alert("Informe o defeito constatado para lançar peças");
                return false;
            }
        <?php } ?>

            var produto = $("#produto_id").val();

            if (produto.length > 0) {
                $(this).next().attr({ produto: produto});

                $.lupa($(this), parametros_lupa_peca);
            } else {
                alert("Selecione um produto para pesquisar a peça");
            }
        });

        $("#add_linha").click(function () {
            adicionar_linha();
        });

    $("button[name=lista_basica]").click(function() {
        var usa_versao = false;

        var defeito_constatado = $("#defeito_constatado").val();

        <?php if (!($login_fabrica == 24 && $login_posto == 428725)) {?>
            if (defeito_constatado.length == 0) {
                alert("Informe o defeito constatado para lançar peças");
                return false;
            }
        <?php } ?>

            var produto = $("#produto_id").val();

            var url = "lista_basica_lupa_new.php?produto="+produto;

            if (typeof produto != "undefined" && produto.length > 0) {
                Shadowbox.open({
                    content: url,
                    player: "iframe",
                    height: 600,
                    width: 800,
                    options: {
                        onClose: function() {
                            $("select[name^=produto_pecas]").css({ visibility: "visible" });
                        }
                    }
                });
            } else {
                alert("Selecione um produto para pesquisar sua lista básica");
            }
    });


    });

    function adicionar_linha() {
        var nova_linha = $("#modelo_peca").clone();
        var qtde = $("#qtd_linhas").val();

        $(nova_linha).find("input[name^=produto_pecas]").attr("name","produto_pecas["+qtde+"]");

        $(nova_linha).find("input[id^=btn_excluir_]").val("");

        $(nova_linha).find("input[id^=btn_excluir_]").attr("id","btn_excluir_"+qtde);

        $(nova_linha).find("input[id^=btn_excluir_]").attr("posicao",qtde);

        $(nova_linha).find("button[name^=remove_peca_]").attr("rel",qtde);

        $(nova_linha).find("button[name^=remove_peca_]").attr("name","remove_peca_"+qtde);

        $(nova_linha).find("div[id^=pecas_referencia_]").attr("id","pecas_referencia_"+qtde);

        $(nova_linha).find(".produto_referencia").attr("name","produto_pecas["+qtde+"][referencia]");

        $(nova_linha).find("div[id^=pecas_descricao_]").attr("id","pecas_descricao_"+qtde);

        $(nova_linha).find(".produto_descricao").attr("name","produto_pecas["+qtde+"][descricao]");

        $(nova_linha).find("input[name=lupa_config]").attr("posicao",qtde);

        $(nova_linha).find("div[id^=qtde_]").attr("id","qtde_"+qtde);

        $(nova_linha).find(".produto_qtde").attr("name","produto_pecas["+qtde+"][qtde]");

        $(nova_linha).find(".produto_qtde").val("");

        $(nova_linha).find("div[id^=defeito_]").attr("id","defeito_"+qtde);

        $(nova_linha).find("select[id^=defeito_peca_]").attr("id","defeito_peca_"+qtde);

        $(nova_linha).find("select[id^=defeito_peca_]").attr("name","produto_pecas["+qtde+"][defeito_peca]");

        $(nova_linha).find("select[id^=servico_peca_]").attr("name","produto_pecas["+qtde+"][servico_peca]");

        $(nova_linha).find("div[id^=servico_]").attr("id","servico_"+qtde);

        $(nova_linha).find("select[id^=servico_peca_]").attr("id","servico_peca_"+qtde);

        $(nova_linha).find(".produto_referencia").val("");

        $(nova_linha).find(".produto_descricao").val("");

        $(nova_linha).find("button[name^=remove_peca_]").hide();

        $(nova_linha).find("input[id^=peca_id_]").attr("name","produto_pecas["+qtde+"][id]");

        $(nova_linha).find("input[id^=peca_id_]").attr("id","peca_id_"+qtde);

        $(nova_linha).find("input[id^=peca_id_"+qtde+"]").val("");

        nova_qtd = parseInt(qtde);
        nova_qtd += 1;

        $("#qtd_linhas").val(nova_qtd);

        $('#pecas').append(nova_linha);
    }

    function busca_defeito_constatado(produto,defeito = "") {

        $.ajax({
            url: "cadastro_os.php",
            type: "POST",
            data: { ajax_busca_defeito_constatado: true, produto: produto},
            beforeSend: function() {
                $("#defeito_constatado").hide().after($("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } }));
            },
            complete: function(data) {
                data = $.parseJSON(data.responseText);
                    if (data.defeitos_constatados) {
                        $("#defeito_constatado > option").first().nextAll().remove();

                        defeito_constatado_json = [];

                        $.each(data.defeitos_constatados, function(key, value) {
                            var descricao = value.descricao;

                            if (value.defeito_constatado == defeito) {
                                var selected = "selected";
                            } else {
                                var selected = "";
                            }

                            var option = $("<option value=\""+value.defeito_constatado+"\" "+selected+" >"+descricao+"</option>");

                            $("#defeito_constatado").append(option);

                            defeito_constatado_json.push({ defeito_constatado: value.defeito_constatado, descricao: descricao });
                        });
                    }

                    $("#defeito_constatado").show().next().remove();
            }
        });
    }

    function busca_vista_explodida(produto, subproduto) {
        if (typeof subproduto == "undefined") {
            subproduto = false;
        }
        var versao = '';

        <?php  if (isset($usa_versao_produto)) {  ?>
            versao = $("#produto_versao").val();
        <?php } ?>

        if (typeof produto != "undefined" && produto.length > 0) {
            $.ajax({
                url: "cadastro_os.php",
                type: "get",
                data: { ajax_busca_vista_explodida: true, produto: produto , produto_versao: versao},
                beforeSend: function() {
                    if (subproduto === false) {
                        $("#vista_explodida_link").removeAttr("href").hide();
                    } else {
                        $("#vista_explodida_subproduto").removeAttr("href").hide();
                    }
                }
            }).always(function(data) {
                data = $.parseJSON(data);

                if (data.link) {
                    if (subproduto === false) {
                        $("#vista_explodida_link").attr("href", data.link).show();
                    } else {
                        $("#vista_explodida_subproduto").attr("href", data.link).show();
                    }
                }
            });
        }
    }

    function retorna_produto (retorno) {
        $("#produto_referencia").val(retorno.referencia);
        $("#produto_descricao").val(retorno.descricao);
        $("#voltagem").val(retorno.voltagem);
        $("#produto_id").val(retorno.produto);

        $("#div_alterar_produto").show();
        $("#vista_explodida").show();

        busca_defeito_constatado(retorno.produto);
        busca_vista_explodida(retorno.produto);
    }

    function retorna_consumidor_os(retorno) {
        $("#nome_cliente").val(retorno.nome);
        $("#input_cnpj_cpf").val(retorno.cpf);
        $("#telefone").val(retorno.fone);
        $("#celular").val(retorno.celular);
    }

    function retorna_laudo_os(retorno) {
        $("#nome_cliente").val(retorno.nome_cliente);
        $("#input_cnpj_cpf").val(retorno.cpf_cliente);
        $("#telefone").val(retorno.fone_cliente);
        $("#celular").val(retorno.celular_cliente);
    }

    function ajax_defeito_peca(peca,posicao,id = 0) {
        $.ajax({
            url: "devolucao_cadastro.php",
            type: "POST",
            data: { ajax_busca_defeito_peca: true, peca: peca, posicao: posicao,id:id },
            complete: function(data) {
                $("#defeito_peca_"+posicao).html(data.responseText);
            }
        });
    }

    function ajax_servico_realizado(produto,posicao,id = 0) {
        $.ajax({
            url: "devolucao_cadastro.php",
            type: "POST",
            data: { ajax_busca_servico_realizado: true, produto: produto, posicao: posicao, id:id },
            complete: function(data) {

                $('#servico_peca_'+posicao).html(data.responseText);

            }
        });
    }

    function retorna_peca(retorno) {

        $("input[name='produto_pecas["+retorno.posicao+"][id]']").val(retorno.peca);
        $("input[name='produto_pecas["+retorno.posicao+"][referencia]']").val(retorno.referencia);
        $("input[name='produto_pecas["+retorno.posicao+"][descricao]']").val(retorno.descricao);

        $("#pecas").find("input[rel=peca_id][value='']").first().val("t");
        $("button[name=remove_peca_"+retorno.posicao+"]").show();

        ajax_defeito_peca(retorno.referencia,retorno.posicao);
        ajax_servico_realizado(retorno.peca,retorno.posicao);

    }

    function retorna_pecas(retorno) {
        if (retorno.length > 0) {
            var erro = [];

            $.each(retorno, function(key, peca) {
                var erro_peca = false;

                var cont = 0;
                $("#pecas").find("input[id^=peca_id_]").each(function() {
                    if ($(this).val() == "") {
                        cont += 1;
                    }
                });

                if (cont == 0) {
                    adicionar_linha();
                }

                peca.posicao = $("#pecas").find("input[rel=peca_id][value='']").first().attr("posicao");


                retorna_peca(peca);

            });

            if (erro.length > 0) {
                alert("As seguintes peças já foram lançdas na Ordem de Serviço: "+erro.join(", "));
            }
        }
    }

</script>
<?
$inputs = array(
    "devolucao" => array(
        "data_recebimento" => array(
            "span"      => 3,
            "label"     => "Data Recebimento",
            "type"      => "input/text",
            "width"     => 7,
            "required"  => true,
            "extra"     => ""
        ),
        "aparencia_produto" => array(
            "span"      => 4,
            "label"     => "Aparência do Produto",
            "type"      => "input/text",
            "width"     => 12,
            "maxlength" => 50,
            "required"  => false
        ),
        "nota_fiscal" => array(
            "span"      => 3,
            "label"     => "Nota Fiscal",
            "type"      => "input/text",
            "width"     => 10,
            "maxlength" => 20,
            "required"  => true,
            "extra"     => ""
        ),
        "emissao_nota" => array(
            "span"      => 3,
            "label"     => "Emissão da Nota Fiscal",
            "type"      => "input/text",
            "width"     => 7,
            "maxlength" => 40,
            "required"  => true,
            "extra"     => ""
        )
    )
);

$inputs["devolucao"]["motivo_sintetico"] = array(
    "span"      => 4,
    "label"     => "Motivo Sintético",
    "type"      => "select",
    "options"   => array(),
    "width"     => 10,
    "maxlength" => 40,
    "required"  => true
);

$sql_sintetico = "SELECT  motivo_sintetico,
                    codigo,
                    descricao,
                    ativo
            FROM    tbl_motivo_sintetico
            WHERE ativo IS TRUE
            ORDER BY codigo";
$res_sintetico = pg_query($con,$sql_sintetico);

$array_sintetico = array();

$inputs["devolucao"]["motivo_analitico"] = array(
    "span"      => 4,
    "label"     => "Motivo Analítico",
    "type"      => "select",
    "options"   => array(),
    "width"     => 10,
    "required"  => true
);

$sql_analitico = "SELECT  motivo_analitico,
                    codigo,
                    descricao,
                    ativo
            FROM    tbl_motivo_analitico
            WHERE ativo IS TRUE
            ORDER BY codigo";
$res_analitico = pg_query($con,$sql_analitico);

$array_analitico = array();


for($x=0;$x < pg_num_rows($res_analitico);$x++) {
    $motivo_analitico           = trim(pg_result($res_analitico,$x,'motivo_analitico'));
    $descricao_analitico        = trim(pg_result($res_analitico,$x,'descricao'));

    $inputs["devolucao"]["motivo_analitico"]["options"][$motivo_analitico]['label'] = $descricao_analitico;

    if($motivo_analitico == $motivo_analitico_db){
         $inputs["devolucao"]["motivo_analitico"]["options"][$motivo_analitico]['extra'] = array('selected' => 'selected');
    }else{
        $inputs["devolucao"]["motivo_analitico"]["options"][$motivo_analitico]['extra'] = '';
    }
}



for($x=0;$x < pg_num_rows($res_sintetico);$x++) {
    $motivo_sintetico           = trim(pg_result($res_sintetico,$x,'motivo_sintetico'));
    $descricao_sintetico        = trim(pg_result($res_sintetico,$x,'descricao'));

    $inputs["devolucao"]["motivo_sintetico"]["options"][$motivo_sintetico]['label'] = $descricao_sintetico;

    if($motivo_sintetico == $motivo_sintetico_db){
         $inputs["devolucao"]["motivo_sintetico"]["options"][$motivo_sintetico]['extra'] = array('selected' => 'selected');
    }else{
        $inputs["devolucao"]["motivo_sintetico"]["options"][$motivo_sintetico]['extra'] = '';
    }
}

$inputs["devolucao"]["senha_autorizacao"] = array(
    "span"      => 4,
    "label"     => "Senha de Autorização",
    "type"      => "input/text",
    "width"     => 8,
    "maxlength" => 30,
    "required"  => true
);

$inputs["informacao_cliente"] = array(
        "nome_cliente" => array(
            "span"      => 4,
            "label"     => "Nome do Cliente",
            "type"      => "input/text",
            "width"     => 10,
            "maxlength" => 50,
            "required"  => true,
            "lupa" => array(
                "name" => "cliente_laudo",
                "tipo" => "cliente_laudo_os",
                "parametro" => "nome_consumidor",
                "extra" => array(
                    "ativo" => true
                )
            )
        ),


        "cpf_cnpj" => array(
            "span"      => 3,
            "label"     => 'CPF <input type="radio" id="cpf" class="cpf_cnpj_radio" name="cpf_cnpj_radio"  value="cpf" />
                            /CNPJ <input type="radio" id="cnpj" class="cpf_cnpj_radio" name="cpf_cnpj_radio" value="cnpj" />',
            "type"      => "input/text",
            "width"     => 10,
            "maxlength" => 14,
            "required"  => true,
            "id"  => "input_cnpj_cpf",
            "extra"     => "",
            "lupa" => array(
                "name" => "cliente_laudo",
                "tipo" => "cliente_laudo_os",
                "parametro" => "cpf_cnpj",
                "extra" => array(
                    "ativo" => true
                )
            )
        ),
        "telefone" => array(
            "span"      => 3,
            "label"     => "Telefone",
            "type"      => "input/text",
            "width"     => 9,
            "maxlength" => 11,
            "required"  => false,
            "extra"     => ""
        ),
        "celular" => array(
            "span"      => 3,
            "label"     => "Celular",
            "type"      => "input/text",
            "width"     => 9,
            "maxlength" => 11,
            "required"  => false,
            "extra"     => ""
        )
);

$inputs["informacao_produto"] = array(
        "produto_referencia" => array(
            "id"        => "produto_referencia",
            "span"      => 4,
            "label"     => "Referência",
            "type"      => "input/text",
            "width"     => 7,
            "required"  => true,
            "lupa" => array(
                "name" => "lupa",
                "tipo" => "produto",
                "parametro" => "referencia",
                "extra" => array(
                    "ativo" => true
                )
            )
        ),
        "produto_descricao" => array(
            "id"        => "produto_descricao",
            "span"      => 4,
            "label"     => "Descrição",
            "type"      => "input/text",
            "width"     => 10,
            "required"  => true,
            "lupa" => array(
                "name" => "lupa",
                "tipo" => "produto",
                "parametro" => "descricao",
                "extra" => array(
                    "ativo" => true
                )
            )
        ),
        "voltagem" => array(
            "id"        => "voltagem",
            "span"      => 4,
            "label"     => "Voltagem",
            "type"      => "input/text",
            "width"     => 4,
            "extra" => array(
                "disabled" => "disabled"
            )
        ),
        "numero_serie" => array(
            "id"        => "numero_serie",
            "span"      => 4,
            "label"     => "Número de Série",
            "type"      => "input/text",
            "width"     => 10,
            "maxlength" => 20,
            "required"  => true
        )

);

$inputs["informacao_produto"]["defeito_constatado"] = array(
    "id"        => "defeito_constatado",
    "span"      => 4,
    "label"     => "Defeito Constatado",
    "options"   => "",
    "type"      => "select",
    "width"     => 10,
    "required"  => false
);

    $buscaProduto = (isset($_POST['produto_id'])) ? $_POST['produto_id'] : $produto;
    $buscaDefeito = (isset($_POST['defeito_constatado'])) ? $_POST['defeito_constatado'] : $defeito_constatado;
?>
    <script>
        busca_defeito_constatado(<?=$buscaProduto?>,"<?=$buscaDefeito?>");
    </script>
<?

$inputs["informacao_produto"]["solucao"] = array(
    "id"        => "solucao",
    "span"      => 4,
    "label"     => "Solução",
    "type"      => "select",
    "options"   => array(),
    "width"     => 10,
    "required"  => false
);

$sql_solucao = "SELECT  solucao,
                        codigo,
                        descricao,
                        ativo
                FROM    tbl_solucao
                WHERE ativo IS TRUE
                AND fabrica = $login_fabrica
                ORDER BY codigo";
$res_solucao = pg_query($con,$sql_solucao);

for($x=0;$x < pg_num_rows($res_solucao);$x++) {
    $solucao          = trim(pg_result($res_solucao,$x,'solucao'));
    $descricao_solucao        = trim(pg_result($res_solucao,$x,'descricao'));

    $inputs["informacao_produto"]["solucao"]["options"][$solucao]['label'] = $descricao_solucao;

    if($solucao == $solucao_mb){
        $inputs["informacao_produto"]["solucao"]["options"][$solucao]['extra'] = array('selected' => 'selected');
    }else{
        $inputs["informacao_produto"]["solucao"]["options"][$solucao]['extra'] = '';
    }
}

$inputs["informacao_produto"]["analise_produto"] = array(
    "id"        => "analise_produto",
    "span"      => 4,
    "label"     => "Análise Produto",
    "type"      => "select",
    "options"   => array(),
    "width"     => 10,
    "required"  =>  false
);

$inputs["responsavel_analise"]["responsavel_analise"] = array(
    "id"        => "responsavel_analise",
    "span"      => 8,
    "label"     => "Responsável Análise",
    "type"      => "textarea",
    "required"  => false
);

$sql_analise = "SELECT  analise_produto,
                    codigo,
                    descricao,
                    ativo
            FROM    tbl_analise_produto
            WHERE ativo IS TRUE
            ORDER BY codigo";
$res_analise = pg_query($con,$sql_analise);

if ($login_fabrica == 24) {
    $aux_sql = "SELECT responsavel_analise FROM tbl_os_laudo WHERE os_laudo = $os_laudo LIMIT 1";
    $aux_res = pg_query($con, $aux_sql);
    $aux_osl = pg_fetch_result($aux_res, 0, 0);

    if (!empty($aux_osl)) {
        $_RESULT["responsavel_analise"] = $aux_osl;
    }
}

for($x=0;$x < pg_num_rows($res_analise);$x++) {
    $analise_produto           = trim(pg_result($res_analise,$x,'analise_produto'));
    $descricao_analise         = trim(pg_result($res_analise,$x,'descricao'));

    $inputs["informacao_produto"]["analise_produto"]["options"][$analise_produto]['label'] = $descricao_analise;

    if($analise_produto == $analise_produto_mb){
        $inputs["informacao_produto"]["analise_produto"]["options"][$analise_produto]['extra'] = array('selected' => 'selected');
    }else{
        $inputs["informacao_produto"]["analise_produto"]["options"][$analise_produto]['extra'] = '';
    }

}
$data_recebimento           = trim(pg_result($res_laudo,0,'data_recebimento'));
    $nota_fiscal                = trim(pg_result($res_laudo,0,'nota_fiscal'));
    $nome_cliente               = trim(pg_result($res_laudo,0,'nome_cliente'));
    $cpf_cliente                = trim(pg_result($res_laudo,0,'cpf_cliente'));
    $fone_cliente               = trim(pg_result($res_laudo,0,'fone_cliente'));
    $celular_cliente            = trim(pg_result($res_laudo,0,'celular_cliente'));

if ((!empty($_GET["os_laudo"]) || !empty($_GET["os_laudo_info"])) && empty($btn_acao)) {

    $inputs["devolucao"]["data_recebimento"]["extra"]       = array("value" => mostra_data($data_recebimento));
    $inputs["devolucao"]["nota_fiscal"]["extra"]            = array("value" => $nota_fiscal);
    $inputs["devolucao"]["aparencia_produto"]["extra"]            = array("value" => $aparencia_produto);

    $inputs["devolucao"]["motivo_analitico"]["extra"]            = array("value" => $motivo_analitico);
    $inputs["devolucao"]["motivo_sintetico"]["extra"]            = array("value" => $motivo_sintetico);

    $inputs["devolucao"]["senha_autorizacao"]["extra"]            = array("value" => $senha_autorizacao);

    $inputs["devolucao"]["emissao_nota"]["extra"]           = array("value" => mostra_data($data_nf));
    $inputs["informacao_cliente"]["nome_cliente"]["extra"]  = array("value" => $nome_cliente);
    $inputs["informacao_cliente"]["cpf_cnpj"]["extra"]      = array("value" => $cpf_cliente);
    $inputs["informacao_cliente"]["telefone"]["extra"]      = array("value" => $fone_cliente);
    $inputs["informacao_cliente"]["celular"]["extra"]       = array("value" => $celular_cliente);

    $inputs["informacao_produto"]["produto_referencia"]["extra"] = array("value" => $produto_referencia);
    $inputs["informacao_produto"]["produto_descricao"]["extra"] = array("value" => $produto_descricao);
    $inputs["informacao_produto"]["voltagem"]["extra"] = array("value" => $produto_voltagem,"disabled" => "disabled");
    $inputs["informacao_produto"]["numero_serie"]["extra"] = array("value" => $serie);
}

if (count($msg_erro["msg"]) > 0) { ?>
    <br />
    <div class="alert alert-error">
        <h4><?= $msg_erro["msg"] ?></h4>
    </div>
<?php
} else if (count($msg_success["msg"]) > 0) { ?>
    <br />
    <div class="alert alert-success">
        <h4><?= $msg_success["msg"] ?></h4>
    </div>
<?
}
?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name="frm_condicao" method="POST" class="form-search form-inline" action="devolucao_cadastro.php" >
<div class="tc_formulario">
    <div class='titulo_tabela'>Informações da devolução</div>
    <br />
<?
echo montaForm($inputs["devolucao"]);
?>
<br />
</div>
<br>
<div class="tc_formulario">
    <div class='titulo_tabela'>Informações do Cliente</div>
    <br />
<?
echo montaForm($inputs["informacao_cliente"]);
?>
<br />
</div>
<br>
<div class="tc_formulario">
    <div class='titulo_tabela'>Informações do Produto</div>
    <br />
<?
echo montaForm($inputs["informacao_produto"]);
?>
<input type="hidden" id="produto_id" name="produto_id" value="<?=$buscaProduto?>" />
<br />
    <div class="row row-fluid" id="div_alterar_produto" style="display: none;">
        <div class="tac">
            <button type="button" id="trocar_produto" class="btn btn-danger">Alterar Produto</button>
         </div>
    </div>
</div>
<br />
<div class="tc_formulario">
    <div class='titulo_tabela'>Peças do Produto</div>
    <br />
    <p class="tac">
        <button type="button" name="lista_basica" class="btn">Lista Básica</button>

        <a href="" id="vista_explodida_link" target="_blank"><button type="button" id="vista_explodida"  name="vista_explodida" class="btn btn-info" style="display: none;">Vista Explodida</button></a>
    </p>
    <div id="pecas">

        <?php

        $qtde_linhas = (isset($_POST["qtd_linhas"])) ? $_POST["qtd_linhas"] : 3;
        if (!empty($os_laudo) && empty($os_laudo_info)) {
            $sqlPecas = "
                SELECT  tbl_peca.peca,
                        tbl_peca.referencia,
                        tbl_peca.descricao,
                        tbl_os_laudo_peca.qtde,
                        tbl_defeito.descricao as defeito_descricao,
                        tbl_defeito.defeito   as defeito_id       ,
                        tbl_servico_realizado.descricao AS servico_descricao,
                        tbl_servico_realizado.servico_realizado
                FROM    tbl_os_laudo_peca
                JOIN    tbl_peca USING(peca)
                JOIN    tbl_defeito USING(defeito)
                left JOIN    tbl_servico_realizado USING(servico_realizado)
                WHERE   tbl_os_laudo_peca.os_laudo = $os_laudo
            ";
            $resPecas = pg_query($con,$sqlPecas);

            $qtde_linhas_busca = pg_num_rows($resPecas);
        }

        $qtde_linhas =  ($qtde_linhas > $qtde_linhas_busca) ? $qtde_linhas : $qtde_linhas_busca;

        for ($i = 0;$i < $qtde_linhas;$i++) {
            $produto_pecas_id = (isset($_POST["produto_pecas"][$i]["id"]) && !empty($_POST["produto_pecas"][$i]["id"]))
                ? $_POST["produto_pecas"][$i]["id"]
                : pg_fetch_result($resPecas,$i,peca);

            $produto_pecas_referencia = (isset($_POST["produto_pecas"][$i]["referencia"]) && !empty($_POST["produto_pecas"][$i]["referencia"]))
                ? $_POST["produto_pecas"][$i]["referencia"]
                : pg_fetch_result($resPecas,$i,referencia);

            $produto_pecas_descricao = (isset($_POST["produto_pecas"][$i]["descricao"]) && !empty($_POST["produto_pecas"][$i]["descricao"]))
                ? $_POST["produto_pecas"][$i]["descricao"]
                : pg_fetch_result($resPecas,$i,descricao);

            $produto_pecas_qtde = (isset($_POST["produto_pecas"][$i]["qtde"]) && !empty($_POST["produto_pecas"][$i]["qtde"]))
                ? $_POST["produto_pecas"][$i]["qtde"]
                : pg_fetch_result($resPecas,$i,qtde);

            $produto_pecas_defeito_peca = (isset( $_POST["produto_pecas"][$i]['defeito_peca']) && !empty( $_POST["produto_pecas"][$i]['defeito_peca']))
                ?  $_POST["produto_pecas"][$i]['defeito_peca']
                : pg_fetch_result($resPecas,$i,defeito_id);

            $produto_pecas_servico_peca = (isset($_POST["produto_pecas"][$i]['servico_peca'] ) && !empty($_POST["produto_pecas"][$i]['servico_peca']))
                ? $_POST["produto_pecas"][$i]['servico_peca']
                : pg_fetch_result($resPecas,$i,servico_realizado);
?>

            <script>
                ajax_defeito_peca("<?=$produto_pecas_referencia?>", "<?=$i?>", "<?=$produto_pecas_defeito_peca?>");

                ajax_servico_realizado("<?=$produto_pecas_id?>", "<?= $i ?>", "<?=$produto_pecas_servico_peca?>");
            </script>

        <div id="modelo_peca">
            <input type="hidden" name="produto_pecas[<?=$i?>][id]" id='peca_id_<?= $i ?>' value="<?=$produto_pecas_id?>"/>
            <div class="row-fluid">
                <div class="span1">
                    <div class='control-group'>
                        <br />
                        <div class="controls controls-row">
                            <div class="span12 tac" >
                                <input type="hidden" name="produto_pecas[<?=$i?>]" rel="peca_id" id='btn_excluir_<?=$i?>' value="<?=$produto_pecas_id?>" posicao="<?=$i?>" disabled="disabled" />
                                <button type="button" class="btn btn-mini btn-danger" name="remove_peca_<?= $i ?>" rel="<?=$i?>" style="<?=($produto_pecas_id) ? "" : "display: none;"; ?>" >X</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span2">
                    <div class="" id="pecas_referencia_<?= $i ?>">
                        <div class='control-group' <?= (in_array("referencia", $msg_erro["peca"][$i])) ? "error" : ""; ?> >
                            <label class="control-label">Referência</label>
                            <div class="controls controls-row">
                                <div class="span10 input-append">
                                    <input  name="produto_pecas[<?=$i?>][referencia]" class="span12 produto_referencia" value="<?=$produto_pecas_referencia?>" type="text" />
                                    <span class="add-on" rel="lupa_peca">
                                        <i class="icon-search"></i>
                                    </span>
                                    <input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" posicao="<?= $i ?>" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span3">
                    <div class="" id="pecas_descricao_<?= $i ?>">
                        <div class='control-group  <?= (in_array("descricao", $msg_erro["peca"][$i])) ? "error" : ""; ?>' >
                            <label class="control-label">Descrição</label>
                            <div class="controls controls-row">
                                <div class="span10 input-append">
                                    <input  name="produto_pecas[<?= $i ?>][descricao]" class="span12 produto_descricao" type="text" value="<?=$produto_pecas_descricao?>" />
                                    <span class="add-on" rel="lupa_peca">
                                        <i class="icon-search"></i>
                                    </span>
                                    <input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" posicao="<?= $i ?>" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span1">
                    <div class="" id="qtde_<?= $i ?>">
                        <div class='control-group  <?= (in_array("qtde", $msg_erro["peca"][$i])) ? "error" : ""; ?>' >
                            <label class="control-label">Qtde</label>
                            <div class="controls controls-row">
                                <div class="span10 input-append">
                                    <input  name="produto_pecas[<?= $i ?>][qtde]" class="span12 produto_qtde" type="text" value="<?=$produto_pecas_qtde?>" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span2" id="defeito_<?= $i ?>">
                    <div class='control-group  <?= (in_array("defeito_peca", $msg_erro["peca"][$i])) ? "error" : ""; ?>' >
                        <label class="control-label" >Defeito</label>
                        <div class="controls controls-row">
                            <div class="span12 tal">
                                <select class="span12" id="defeito_peca_<?= $i ?>" name="produto_pecas[<?= $i ?>][defeito_peca]">
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span2" id="servico_<?= $i ?>">
                    <div class='control-group' >
                        <label class="control-label" >Serviço</label>
                        <div class="controls controls-row">
                            <div class="span12 tal">
                                <select class="span12" id="servico_peca_<?= $i ?>" name="produto_pecas[<?= $i ?>][servico_peca]">
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <? } ?>
    </div>
    <input type="hidden" name="qtd_linhas" value="<?= $i ?>" id="qtd_linhas" />
    <br />
    <button type="button" id="add_linha" class="btn btn-primary" >Adicionar nova linha</button>
    <br />
<br />
</div>
<br>
<div class="tc_formulario">
    <div class='titulo_tabela'>Responsável Pela Análise</div>
    <?
    echo montaForm($inputs["responsavel_analise"]);
    ?>
    <br />
</div>
<br />
<div id="div_anexos" class="tc_formulario">
        <div class="titulo_tabela">
            Anexo(s)
        </div>
        <br />

        <div class="tac" >
        </br>
            <div class="tac" >
            <?php
            $fabrica_qtde_anexos = 3;
            if ($fabrica_qtde_anexos > 0) {
                if (strlen(getValue("data_input"))> 0) {
                    list($data_inp, $hora_inp) = explode(" ",getValue("data_input"));
                    list($dia,$mes,$ano) = explode("/",$data_inp) ;
                    //echo $dia."/".$mes."//".$ano;
                }

                echo "<input type='hidden' name='anexo_chave' value='{$anexo_chave}' />";

                for ($i = 0; $i < $fabrica_qtde_anexos; $i++) {
                    unset($anexo_link);

                    $anexo_imagem = "imagens/imagem_upload.png";
                    $anexo_s3     = false;
                    $anexo        = "";

                    if (strlen(getValue("anexo[{$i}]")) > 0 && getValue("anexo_s3[{$i}]") != "t") {

                        $anexos = $s3->getObjectList(getValue("anexo[{$i}]"), true);

                        $ext = strtolower(preg_replace("/.+\./", "", basename($anexos[0])));

                        if ($ext == "pdf") {
                            $anexo_imagem = "imagens/pdf_icone.png";
                        } else if (in_array($ext, array("doc", "docx"))) {
                            $anexo_imagem = "imagens/docx_icone.png";
                        } else {
                            $anexo_imagem = $s3->getLink("thumb_".basename($anexos[0]), true);
                        }

                        $anexo_link = $s3->getLink(basename($anexos[0]), true);

                        $anexo        = getValue("anexo[$i]");
                     } else if (count($msg_success["msg"]) == 0) {

                        $anexos = $s3->getObjectList("{$login_fabrica}_{$id_os_laudo}_{$i}", false, $ano, $mes);

                        if (count($anexos) > 0) {

                            $ext = strtolower(preg_replace("/.+\./", "", basename($anexos[0])));
                            if ($ext == "pdf") {
                                $anexo_imagem = "imagens/pdf_icone.png";
                            } else if (in_array($ext, array("doc", "docx"))) {
                                $anexo_imagem = "imagens/docx_icone.png";
                            } else {
                                $anexo_imagem = $s3->getLink("thumb_".basename($anexos[0]), false, $ano, $mes);
                            }

                            $anexo_link = $s3->getLink(basename($anexos[0]), false, $ano, $mes);

                            $anexo        = basename($anexos[0]);
                            $anexo_s3     = true;
                        }
                    }
                    ?>
                    <div id="div_anexo_<?=$i?>" class="tac" style="display: inline-block; margin: 0px 5px 0px 5px;">
                        <?php if (isset($anexo_link)) { ?>
                            <a href="<?=$anexo_link?>" target="_blank" >
                        <?php } ?>

                        <img src="<?=$anexo_imagem?>" class="anexo_thumb" style="width: 100px; height: 90px;" />

                        <?php if (isset($anexo_link)) { ?>
                            </a>
                            <script>setupZoom();</script>
                        <?php } ?>

                        <?php
                        if ($anexo_s3 === false) {
                        ?>
                            <button id="anexar_<?=$i?>" type="button" class="btn btn-mini btn-primary btn-block btn_acao_anexo" name="anexar" rel="<?=$i?>" >Anexar</button>
                        <?php
                        }
                        ?>

                        <img src="imagens/loading_img.gif" class="anexo_loading" style="width: 64px; height: 64px; display: none;" />

                        <input type="hidden" rel="anexo" name="anexo[<?=$i?>]" value="<?=$anexo?>" />
                        <input type="hidden" name="anexo_s3[<?=$i?>]" value="<?=($anexo_s3) ? 't' : 'f'?>" />
                        <?php
                        if ($anexo_s3 === true) {?>
                            <button id="excluir_<?=$i?>" type="button" class="btn btn-mini btn-danger btn-block btn_acao_anexo" name="excluir" rel="<?=$i?>" >Excluir</button>
                            <button id="baixar_<?=$i?>" type="button" class="btn btn-mini btn-info btn-block" name="baixar" onclick="window.open('<?=$anexo_link?>')">Baixar</button>

                        <?php
                        }
                        ?>
                    </div>
                <?php
                }
            }
            ?>
            </div>

        <br />

        </div>
    </div>
    <br />
    <center>
        <input type="hidden" name="os_laudo" value="<?=$os_laudo?>" />
        <input type="hidden" name="os_laudo_info" value="<?=$os_laudo_info?>" />
        <input type="submit" class="btn btn-large" name="gravar" value="Gravar" id="Gravar" />
    </center>
</form>
<?
if ($fabrica_qtde_anexos > 0) {
    for ($i = 0; $i < $fabrica_qtde_anexos; $i++) {
    ?>
        <form name="form_anexo" method="post" action="devolucao_cadastro.php" enctype="multipart/form-data" style="display: none;">
            <input type="file" name="anexo_upload_<?=$i?>" value="" />

            <input type="hidden" name="ajax_anexo_upload" value="t" />
            <input type="hidden" name="anexo_posicao" value="<?=$i?>" />
            <input type="hidden" name="anexo_chave" value="<?=$anexo_chave?>" />
        </form>
    <?php
    }
}

include "rodape.php";
?>
