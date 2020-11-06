<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

if (filter_input(INPUT_POST,'ajax',FILTER_VALIDATE_BOOLEAN)) {
    $peca       = filter_input(INPUT_POST,'peca');
    $qtde       = filter_input(INPUT_POST,'qtde');
    $referencia = filter_input(INPUT_POST,'produto_referencia');

    /*
     * - Verifica peça
     */
    $sqlVerPeca = "
        SELECT  tbl_peca.peca
        FROM    tbl_peca
        WHERE   tbl_peca.fabrica = $login_fabrica
        AND     tbl_peca.referencia = '$peca'
    ";
    $resVerPeca = pg_query($con,$sqlVerPeca);

    if (pg_num_rows($resVerPeca) == 0) {
        $arrayErro = array(
            "ok"    => false,
            "motivo"=>utf8_encode("Peça Não encontrada"));
        $envioErro = json_encode($arrayErro);
        echo $envioErro;
        exit;
    }

    /*
     * - Verificar Produto
     */

    $sqlVerProduto = "
        SELECT  tbl_produto.produto
        FROM    tbl_produto
        WHERE   tbl_produto.fabrica_i = $login_fabrica
        AND     tbl_produto.referencia = '$referencia'
    ";
    $resVerProduto = pg_query($con,$sqlVerProduto);

    if (pg_num_rows($resVerProduto) == 0) {
        $arrayErro = array(
            "ok"    => false,
            "motivo"=>utf8_encode("Produto Não encontrada"));
        $envioErro = json_encode($arrayErro);
        echo $envioErro;
        exit;
    }

    $produto = pg_fetch_result($resVerProduto,0,produto);

    $idPeca = pg_fetch_result($resVerPeca,0,peca);

    /*
     * - Verifica se Já existe
     * a peça em lista básica
     */

    $sqlVerLista = "
        SELECT  COUNT(tbl_lista_basica.peca) AS temPeca
        FROM    tbl_lista_basica
        JOIN    tbl_produto USING(produto)
        WHERE   tbl_lista_basica.fabrica    = $login_fabrica
        AND     tbl_produto.fabrica_i       = $login_fabrica
        AND     tbl_produto.produto         = $produto
        AND     tbl_lista_basica.peca       = $idPeca
    ";
    $resVerLista = pg_query($con,$sqlVerLista);

    if (pg_fetch_result($resVerLista,0,temPeca) == 1) {
        $arrayErro = array(
            "ok"    => false,
            "motivo"=>utf8_encode("Peça já existente em Lista Básica"));
        $envioErro = json_encode($arrayErro);
        echo $envioErro;
        exit;
    }

    pg_query($con,"BEGIN TRANSACTION");

    $sqlInsLista = "
        INSERT INTO tbl_lista_basica (
            fabrica       ,
            produto       ,
            peca          ,
            qtde          ,
            admin
        ) VALUES (
            $login_fabrica,
            $produto,
            $idPeca,
            $qtde,
            $login_admin
        )
    ";

    $resInsLista = pg_query($con,$sqlInsLista);
    if (pg_last_error($con)) {
        pg_query($con,"ROLLBACK TRANSACTION");
        echo "erro";
        exit;
    }

    pg_query($con,"COMMIT TRANSACTION");

    echo json_encode(array("ok"=>true));
    exit;

}
?>
<!DOCTYPE html />
<html>
<head>
<meta http-equiv=pragma content=no-cache>
<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
<link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
<link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />
<link href="plugins/shadowbox_lupa/shadowbox.css" type="text/css" rel="stylesheet" />

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script src="plugins/bootstrap/js/bootstrap.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/jquery.ui.autocomplete.js"></script>
<script src="plugins/dataTable.js"></script>
<script src="plugins/resize.js"></script>
<script src="plugins/shadowbox_lupa/lupa.js"></script>

<script type="text/javascript">

$(function() {

    $.autocompleteLoad(Array("produto"), Array("produto"));

//     $(document).on("click", "span[rel=lupa]", function () {
//         $.lupa($(this),Array('produto'));
//     });

    $("#btn_acao").on("click",function(e) {
        e.preventDefault();

        var produto_referencia  = $("#produto_referencia").val();
        var produto_descricao   = $("#produto_descricao").val();
        var peca                = $("#peca").val();
        var qtde                = $("#qtde").val();

        if (produto_referencia != "" && produto_descricao != "") {
            $.ajax({
                url:"peca_lista_basica.php",
                type:"POST",
                dataType:"JSON",
                data:{
                    ajax:true,
                    peca:peca,
                    produto_referencia:produto_referencia,
                    qtde:qtde
                }
            })
            .done(function(data){
                if (data.ok) {
                    alert("Peça incluída na lista básica do produto "+produto_referencia+" - "+produto_descricao)
                    window.parent.location.reload();
                } else {
                    alert(data.motivo);
                    window.parent.Shadowbox.close();
                }
            })
            .fail(function(){
                alert("Não foi possível gravar a peça na Lista Básica");
                window.parent.Shadowbox.close();
            });
        } else {
            alert("Favor, digitar todos os dados do produto!!");
        }
    });
});

function retorna_produto (retorno) {
    $("#produto").val(retorno.produto);
    $("#produto_referencia").val(retorno.referencia);
    $("#produto_descricao").val(retorno.descricao);
    if (typeof retorno.serie_produto != "undefined") {
        $("#produto_serie").val(retorno.serie_produto);
    }
}
</script>

</head>
<body>
    <form name='frm_lbm' METHOD='POST'  align='center' class='form-search form-inline tc_formulario' >
        <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
        <br/>

        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='produto_referencia'>Ref. Produto</label>
                    <div class='controls controls-row'>
                        <div class='span7 input-append'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<?= $produto_referencia ?>" >
                            <!--<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                            <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />-->
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='produto_descricao'>Descrição Produto</label>
                    <div class='controls controls-row'>
                        <div class='span12 input-append'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<?= $produto_descricao; ?>" >
                            <!--<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
                            <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />-->
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <p><br/>
            <button class='btn' id="btn_acao" type="button" >Gravar</button>
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
            <input type='hidden' id="peca" name='peca' value='<?=filter_input(INPUT_GET,'peca')?>' />
            <input type='hidden' id="qtde" name='qtde' value='<?=filter_input(INPUT_GET,'qtde')?>' />
        </p><br/>
    </form>
</body>
</html>
