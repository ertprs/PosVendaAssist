<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if( isset ( $_GET['linha_form'] ) && isset($_GET['produto_referencia'] ) ) {
    if (empty($_GET['produto_referencia']) )
        exit;

    $referencia = $_GET['produto_referencia'];

    $sql = "  SELECT tbl_tabela_item.preco, tbl_peca.ipi
                FROM tbl_tabela_item
                JOIN tbl_tabela USING (tabela)
		JOIN tbl_peca USING (peca,fabrica)
		JOIN tbl_posto_linha ON tbl_tabela.tabela = tbl_posto_linha.tabela_posto and tbl_posto_linha.posto = $login_posto
		JOIN tbl_linha using(linha,fabrica)
               WHERE referencia         = '$referencia'
                 AND tbl_peca.fabrica   = $login_fabrica
                 AND tbl_tabela.fabrica = $login_fabrica ";
    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {
        $preco = pg_fetch_result($res,0,preco);
        $ipi   = pg_fetch_result($res,0,ipi);
        $preco_ipi = $preco + (($preco * $ipi) / 100);
        echo $_GET['linha_form'] . '|' . $preco . '|' . $ipi . '|' . $preco_ipi;
    }
    exit;
}

if (strlen($_POST["pesquisaPeca"]) > 10){
    $referencia = $_POST["referencia"];
    $posicao = $_POST["posicao"];
    /*
    ** Retornos AJAX: 0 - Referencia Invalida; 1 - Mais de uma componente; 2 - Peça não existe;
    */

    if (strlen($referencia)>0){
        $sql = "    SELECT
                        referencia,
                        descricao
                    FROM
                        tbl_peca
                    WHERE
                        referencia_pesquisa = '$referencia'
                        AND fabrica = $login_fabrica
                    ORDER BY
                        referencia ASC LIMIT 2;";
        $res = pg_query($con,$sql);

        if (pg_num_rows($res) == 1) {
            $referencia     = trim(pg_fetch_result($res, 0, 'referencia'));
            $descricao      = trim(pg_fetch_result($res, 0, 'descricao'));

            echo "$referencia|$descricao|$posicao";
        }elseif(pg_num_rows($res) > 1)
            echo 1;
        else
            echo 2;
    }else{
        echo 0;
    }
    exit;
}


if (strlen($_POST["verificaMultiplo"]) > 5){
    $referencia = $_POST["referencia"];
    $qtde       = $_POST["qtde"];
    $posicao    = $_POST["posicao"];

    if (strlen($referencia)>0){
        $sql = "SELECT  multiplo
                FROM    tbl_peca
                WHERE   referencia  = '$referencia'
                AND     fabrica     = $login_fabrica;
        ";
        $res = pg_query($con,$sql);

        if (pg_num_rows($res) > 0) {
            $multiplo = trim(pg_fetch_result($res, 0, 'multiplo'));

            if($qtde % $multiplo != 0)
                echo "$multiplo|$posicao";
            else
                echo 1;
        }else
            echo 0;
    }else
        echo 0;
    exit;
}

if ($_POST["apagarPedido"] == 'apagarPedidoAJAX'){
    $pedido = $_POST["pedido"];
    $item   = $_POST["item"];

    if (strlen($pedido) > 0 AND strlen($pedido) > 0){
        $sql = "DELETE FROM tbl_pedido_item WHERE pedido_item = $item AND pedido = $pedido";
        if(pg_query($con,$sql))
            echo 1;
        else
            echo 0;
    }
    exit;
}


$sql = "SELECT pedido_faturado FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
$res = pg_query($con,$sql);
if (pg_fetch_result($res,0,0) == 'f') {

    $title       = traduz('cadastro.de.pedidos.de.pecas', $con);
    $layout_menu = 'pedido';
    include "cabecalho.php";

    echo '<h4>' . traduz('cadastro.de.pedidos.faturados.bloqueado', $con) . '</h4>';

    include "rodape.php";
    exit;
}


#-------- Libera digitação de PEDIDOS pelo distribuidor ---------------
$posto = $login_posto ;
$limit_pedidos = 2;

$btn_acao = strtolower ($_POST['btn_acao']);

$msg_erro = "";
$msg_debug = "";

if (!$_POST["qtde_item"]) {
    $qtde_item = 40;
}

//Seleciona os dados para existe na tela...
if(!$_GET[ 'delete']) {
    $sql = "
            SELECT  tbl_pedido.pedido,
                    tbl_pedido.pedido_blackedecker,
                    tbl_pedido.condicao,
                    tbl_pedido.pedido_cliente,
                    tbl_pedido.tipo_frete,
                    tbl_pedido.tipo_pedido,
                    tbl_condicao.descricao,
                    tbl_tipo_pedido.descricao                 AS tipo_pedido_descricao,
                    tbl_pedido.seu_pedido
            FROM    tbl_pedido
       LEFT JOIN    tbl_condicao    USING (condicao)
            JOIN    tbl_tipo_pedido USING (tipo_pedido)
            WHERE   tbl_pedido.exportado    IS NULL
            AND     tbl_pedido.admin        IS NULL
            AND     tbl_pedido.posto        =  $login_posto
            AND     tbl_pedido.fabrica      =  $login_fabrica
            AND     tbl_pedido.finalizado   IS NULL
            AND     (
                        tbl_pedido.status_pedido IS NULL
                    OR  tbl_pedido.status_pedido <> 14
                    );
    ";

    $res = pg_query ($con,$sql);

    if (pg_num_rows($res) > 0) {
        $cook_pedido           = trim(pg_fetch_result($res, 0, 'pedido'));
        $condicao              = trim(pg_fetch_result($res, 0, 'condicao'));
        $descricao_condicao    = trim(pg_fetch_result($res, 0, 'descricao'));
        $pedido_cliente        = trim(pg_fetch_result($res, 0, 'pedido_cliente'));
        $tipo_frete            = trim(pg_fetch_result($res, 0, 'tipo_frete'));
        $tipo_pedido           = trim(pg_fetch_result($res, 0, 'tipo_pedido'));
        $tipo_pedido_descricao = trim(pg_fetch_result($res, 0, 'tipo_pedido_descricao'));
    }

}

if ($btn_acao == "gravar"){

    if (strlen( $cook_pedido ) == 0 ){
        unset( $cook_pedido );
        setcookie ('cook_pedido');
        $cook_pedido = "";
    }
    $pedido             = $_POST['pedido'];
    $condicao           = $_POST['condicao'];
    $tipo_pedido        = $_POST['tipo_pedido'];
    $pedido_cliente     = $_POST['pedido_cliente'];
    $linha              = $_POST['linha'];
    $observacao_pedido  = $_POST['observacao_pedido'];
    $qtde_item          = $_POST['qtde_item'];
    $retirada_local     = $_POST['retirada_local'];
    $tipo_frete         = $_POST['tipo_frete'];

    for( $i = 0; $i < 30 ; $i++ ){
        $peca_descricao[$i] = trim($_POST["peca_referencia" ]);
        $peca_descricao[$i];
    }

    if(empty($peca_descricao)){
    //  $msg_erro = "Não foi digitada a descrição ou referência do produto";
    }

    $aux_condicao           = (strlen($condicao)            == 0) ? "null" : $condicao ;
    $aux_pedido_cliente     = (strlen($pedido_cliente)      == 0) ? "null" : "'$pedido_cliente'";
    $aux_observacao_pedido  = (strlen($observacao_pedido)   == 0) ? "null" : "'$observacao_pedido'" ;
    $aux_tipo_frete         = (strlen($tipo_frete)          == 0) ? "null" : "'$tipo_frete'" ;

    //Valida Tipo de Pedido
    if (($tipo_pedido == 0 OR strlen($tipo_pedido) == 0) and strlen($cook_pedido)==0 and strlen($msg_erro)==0) {
        $msg_erro = traduz('selecione.um.tipo.de.pedido', $con);
    }

    //Valida Condicao de Pagamento
    if ((strlen($condicao) == 0 or $condicao == 0) and strlen($msg_erro)==0) {
        $msg_erro = traduz('selecione.uma.condicao.de.pagamento', $con);
    }


    if (strlen($tipo_pedido) <> 0) {
        $aux_tipo_pedido = "'". $tipo_pedido ."'";
    }else{
        $sql = "SELECT  tipo_pedido
                FROM    tbl_tipo_pedido
                WHERE   descricao IN ('Faturado','Venda')
                AND     fabrica = $login_fabrica";
        $res = pg_query($con,$sql);
        $aux_tipo_pedido = "'". pg_fetch_result($res,0,tipo_pedido) ."'";
    }

    if (strlen($linha) == 0) {
        $aux_linha = "null";
    }else{
        $aux_linha = $linha ;
    }

    #----------- PEDIDO digitado pelo Distribuidor -----------------
    $digitacao_distribuidor = "null";

    if ($distribuidor_digita == 't'){
        $codigo_posto = strtoupper (trim ($_POST['codigo_posto']));
        $codigo_posto = str_replace (" ","",$codigo_posto);
        $codigo_posto = str_replace (".","",$codigo_posto);
        $codigo_posto = str_replace ("/","",$codigo_posto);
        $codigo_posto = str_replace ("-","",$codigo_posto);

        if (strlen ($codigo_posto) > 0) {
            $sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '$codigo_posto' AND credenciamento = 'CREDENCIADO'";
            $res = @pg_query($con,$sql);
            if (pg_num_rows($res) <> 1) {
                $msg_erro = traduz('posto.%.nao.cadastrado', $con, $cook_idioma, $codigo_posto);
                $posto = $login_posto;
            }else{
                $posto = pg_fetch_result($res,0,0);
                if ($posto <> $login_posto) {
                    $sql = "SELECT posto FROM tbl_posto_linha WHERE posto = $posto AND distribuidor = $login_posto AND linha = $linha";
                    $res = @pg_query($con,$sql);
                    if (pg_num_rows($res) <> 1) {
                        $msg_erro = traduz('posto.%.nao.pertence.a.sua.regiao', $con, $cook_idioma, $codigo_posto);
                        $posto = $login_posto;
                    }else{
                        $posto = pg_fetch_result($res,0,0);
                        $digitacao_distribuidor = $login_posto;
                    }
                }
            }
        }
    }

    if(strlen($msg_erro)==0){
        $res = pg_query($con,"BEGIN TRANSACTION");

        if ($aux_tipo_pedido == "'203'") {
            $validade_campo = ", data_validade ";
            $validade_valor = ", (SELECT current_date + interval '30 days')";
            $update_validade = ", data_validade = (SELECT current_date + interval '30 days')";
        } else {
            $validade_campo = '';
            $validade_valor = '';
            $update_validade = '';
        }

        if (strlen ($pedido) == 0 and strlen($cook_pedido)==0) {
            #-------------- insere pedido ------------
            $sql = "INSERT INTO tbl_pedido (
                        posto          ,
                        fabrica        ,
                        condicao       ,
                        pedido_cliente ,
                        linha          ,
                        tipo_frete     ,
                        tipo_pedido    ,
                        digitacao_distribuidor,
                        obs
                        $sql_campo
                        $validade_campo
                    ) VALUES (
                        $posto              ,
                        $login_fabrica      ,
                        $aux_condicao       ,
                        $aux_pedido_cliente ,
                        $aux_linha          ,
                        $aux_tipo_frete    ,
                        $aux_tipo_pedido    ,
                        $digitacao_distribuidor,
                        $aux_observacao_pedido
                        $sql_valor
                        $validade_valor
                    )";
            $res = pg_query($con,$sql);
            $msg_erro = pg_last_error($con);

            if (strlen($msg_erro) == 0){
                $res = pg_query($con,"SELECT CURRVAL ('seq_pedido')");
                $cook_pedido = pg_fetch_result ($res,0,0);
            }
        }else{
            $sql = "UPDATE  tbl_pedido
                    SET     condicao       = $aux_condicao       ,
                            pedido_cliente = $aux_pedido_cliente ,
                            linha          = $aux_linha          ,
                            tipo_pedido    = $aux_tipo_pedido   ,
                            tipo_frete     = $aux_tipo_frete
                            $update_validade
                    WHERE   pedido  = $cook_pedido
                    AND     posto   = $login_posto
                    AND     fabrica = $login_fabrica";
            $res = @pg_query($con,$sql);
            $msg_erro = pg_last_error($con);
        }
    }

    if (strlen ($msg_erro) == 0) {

        $msg_erro_peca = "";
        $erro_de_para = "";
        for ($i = 0 ; $i < $qtde_item ; $i++) {
            $erro_peca = "";

            $pedido_item     = trim($_POST['pedido_item_'.$i]);
            $peca_referencia = trim($_POST['peca_referencia_'.$i]);
            $peca_descricao  = trim($_POST['peca_descricao_'.$i]);
            $qtde            = (int)trim($_POST['qtde_'.$i]);
            $preco           = trim($_POST['preco_'. $i]);

            if ($peca_descricao == 'Não encontrado' or $peca_descricao_ == traduz('nao.encontrado', $con)) {
                $peca_referencia = '';
            }

            if(strlen ($peca_referencia) == 0){
                $erro_peca = "ERRO";
            }

            if (strlen ($peca_referencia) > 0 AND (strlen($preco)==0 or $preco == '0,00')){
                $pedido_session[] = Array("referencia"=>"$peca_referencia","qtd"=>"$qtde","erro"=>"1");
                $erro_peca = "ERRO";
            }

            if((is_int($qtde) == false OR $qtde < 1) AND strlen($erro_peca) == 0){
                $pedido_session[] = Array("referencia"=>"$peca_referencia","qtd"=>"$qtde","erro"=>"1");
                $erro_peca = "ERRO";
            }

            if (strlen($peca_referencia) > 0){
                $sql = "SELECT  multiplo
                        FROM    tbl_peca
                        WHERE   referencia  = '$peca_referencia'
                        AND     fabrica     = $login_fabrica;";
                $res = pg_query($con,$sql);

                if (pg_num_rows($res) > 0) {
                    $multiplo = pg_fetch_result($res, 0, 'multiplo');

                    if($qtde % $multiplo != 0){
                        $pedido_session[] = Array("referencia"=>"$peca_referencia","qtd"=>"$qtde","erro"=>"2");
                        $erro_peca = "ERRO";
                    }
                }
            }

            if (strlen ($pedido_item) > 0 AND strlen ($peca_referencia) == 0 || $_GET[ 'delete' ] ){

                $sql = "DELETE  FROM tbl_pedido_item
                        WHERE   pedido_item = $pedido_item
                        AND     pedido = $cook_pedido";
                $res = pg_query($con,$sql);

                $sql = "SELECT pedido from tbl_pedido_item where pedido = $cook_pedido";
                $res = pg_query($con,$sql);

                if (pg_num_rows($res)==0) {
                    $sql = "DELETE  FROM tbl_pedido
                            WHERE   pedido = $cook_pedido";
                        $res = pg_query($con,$sql);
                }

                setcookie ($cook_pedido, "", time() - 3600);
                unset($cook_pedido);
                header( "Location : $PHP_SELF" );
            }

            if (strlen ($peca_referencia) > 0 AND strlen($erro_peca) == 0) {
                $peca_referencia = trim (strtoupper ($peca_referencia));
                $peca_referencia = str_replace ("-","",$peca_referencia);
                $peca_referencia = str_replace (".","",$peca_referencia);
                $peca_referencia = str_replace ("/","",$peca_referencia);
                $peca_referencia = str_replace (" ","",$peca_referencia);

                $sql = "SELECT  tbl_peca.peca   ,
                                tbl_peca.origem ,
                                tbl_peca.promocao_site,
                                tbl_peca.qtde_disponivel_site ,
                                tbl_peca.qtde_max_site,
                                tbl_peca.multiplo_site
                        FROM    tbl_peca
                        WHERE   tbl_peca.referencia_pesquisa = '$peca_referencia'
                        AND     tbl_peca.fabrica             = $login_fabrica";
                $res = pg_query($con,$sql);


                if (pg_num_rows($res) == 0) {
                    $peca = 0;
                    $pedido_session[] = Array("referencia"=>"$peca_referencia","qtd"=>"$qtde","erro"=>"1");
                    $erro_peca = "ERRO";
                    //exit;
                    //$msg_erro = "Peça $peca_referencia não cadastrada";
                    //$linha_erro = $i;
                    //break;
                }else{
                    $peca          = pg_fetch_result($res, 0, 'peca');
                    $promocao_site = pg_fetch_result($res, 0, 'promocao_site');
                    $qtde_disp     = pg_fetch_result($res, 0, 'qtde_disponivel_site');
                    $qtde_max      = pg_fetch_result($res, 0, 'qtde_max_site');
                    $qtde_multi    = pg_fetch_result($res, 0, 'multiplo_site');
                    $origemi       = trim(pg_fetch_result($res, 0, 'origem'));
                }


                if (strlen($preco)== 0 or $preco == '0,00'){
                    $preco = "null";
                }else{
                    $preco = str_replace (".","",$preco);
                    $preco = str_replace (",",".",$preco);
                }


                if (strlen ($msg_erro) == 0 AND strlen($peca) > 0 and strlen($erro_peca) == 0) {
                    if ($peca <> 0 AND $preco != 'null'){
                        if (strlen($pedido_item) == 0){
                             $sql = "INSERT INTO tbl_pedido_item (
                                        pedido ,
                                        peca   ,
                                        qtde   ,
                                        preco,
                                        estoque
                                    ) VALUES (
                                        $cook_pedido ,
                                        $peca   ,
                                        $qtde   ,
                                        $preco,
                                        NULL
                                    )";
                        }else{
                            $sql = "UPDATE  tbl_pedido_item
                                    SET     peca = $peca,
                                            qtde = $qtde
                                    WHERE   pedido_item = $pedido_item";
                        }
                    }else{
                        $erro_peca = traduz('a.peca.%.nao.foi.cadastrada', $con, $cook_idioma, $peca_referencia);
                    }

                    $res = @pg_query($con,$sql);
                    $msg_erro = pg_last_error($con);

                    if (strlen($msg_erro) == 0 AND strlen($pedido_item) == 0 AND strlen($erro_peca) == 0) {
                        $res         = pg_query($con,"SELECT CURRVAL ('seq_pedido_item')");
                        $pedido_item = pg_fetch_result($res,0,0);
                        $msg_erro = pg_last_error($con);
                    }

                    if (strlen($msg_erro) == 0 AND strlen($erro_peca) == 0) {
                        $sql = "SELECT fn_valida_pedido_item($cook_pedido,$peca,$login_fabrica)";
                        $res = @pg_query($con,$sql);
                        $msg_erro = pg_last_error($con);
                    }

                    if (strlen ($msg_erro) > 0) {
                        break ;
                    }
                }
            }
            $msg_erro_peca .= $erro_peca;
        } //fim for
        $_SESSION['pedido_session'] = $pedido_session;
    }


    if (strlen ($msg_erro) == 0) {
        //$sql = "SELECT fn_pedido_finaliza ($pedido,$login_fabrica)";
        $res = @pg_query($con,$sql);
        $msg_erro = pg_last_error($con);
    }

    if (strlen ($msg_erro) == 0) {
        $res = pg_query($con,"COMMIT TRANSACTION");
        echo "<script type='text/javascript'>";
            echo "window.location= '$PHP_SELF';";
        echo "</script>";
        exit;
    }else{
        $res = pg_query($con,"ROLLBACK TRANSACTION");
    }
}

if ( $_GET[ 'delete' ] ){
    $pedido = $_GET[ 'pedido' ];
    $pedido_item = $_GET[ 'delete' ];

     $sql = "DELETE FROM    tbl_pedido_item
            WHERE   pedido  = $pedido
            AND pedido_item = $pedido_item";
    $res = pg_query($con,$sql);

    $sql = "SELECT pedido from tbl_pedido_item where pedido = $pedido";
    $res = pg_query($con,$sql);

    if (pg_num_rows($res)==0) {
         $sql = "UPDATE tbl_pedido set fabrica = 0 WHERE pedido = $pedido";
        $res = pg_query($con,$sql);
    }

    setcookie ($cook_pedido, "", time() - 3600);
    unset($cook_pedido);
    echo "<script>window.location.href='$PHP_SELF'</script>";
}

$btn_acao = $_GET['btn_acao'];

if (strlen($btn_acao=='Finalizar')) {

    if (strlen ($cook_pedido) > 0) {
        $res = pg_query($con,"BEGIN TRANSACTION");
        $sql = "SELECT fn_pedido_finaliza($cook_pedido,$login_fabrica)";
        $res = @pg_query($con,$sql);
        $msg_erro = pg_last_error($con);

        if (strlen ($msg_erro) == 0) {
            $res = pg_query($con,"COMMIT TRANSACTION");
        header ("Location: pedido_finalizado.php?pedido=$cook_pedido&loc=1");
        }
    }
}


#------------ Le Pedido da Base de dados ------------#
$pedido = $_GET['pedido'];

if (strlen ($pedido) > 0) {
    $sql = "SELECT  TO_CHAR(tbl_pedido.data, 'DD/MM/YYYY')    AS data                 ,
                    tbl_pedido.tipo_frete                                             ,
                    tbl_pedido.pedido_cliente                                         ,
                    tbl_pedido.tipo_pedido                                            ,
                    tbl_pedido.produto                                                ,
                    tbl_produto.referencia                    AS produto_referencia   ,
                    tbl_produto.descricao                     AS produto_descricao    ,
                    tbl_pedido.linha                                                  ,
                    tbl_pedido.condicao                                               ,
                    tbl_pedido.obs                                                    ,
                    tbl_pedido.exportado                                              ,
                    tbl_pedido.total_original                                         ,
                    tbl_pedido.permite_alteracao
            FROM    tbl_pedido
       LEFT JOIN    tbl_produto        USING (produto)
            WHERE   tbl_pedido.pedido   = $pedido
            AND     tbl_pedido.posto    = $login_posto
            AND     tbl_pedido.fabrica  = $login_fabrica ";
    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {
        $data                  = trim(pg_fetch_result($res, 0, 'data'));
        $pedido_cliente        = trim(pg_fetch_result($res, 0, 'pedido_cliente'));
        $tipo_pedido           = trim(pg_fetch_result($res, 0, 'tipo_pedido'));
        $produto               = trim(pg_fetch_result($res, 0, 'produto'));
        $produto_referencia    = trim(pg_fetch_result($res, 0, 'produto_referencia'));
        $produto_descricao     = trim(pg_fetch_result($res, 0, 'produto_descricao'));
        $linha                 = trim(pg_fetch_result($res, 0, 'linha'));
        $condicao              = trim(pg_fetch_result($res, 0, 'condicao'));
        $exportado             = trim(pg_fetch_result($res, 0, 'exportado'));
        $total_original        = trim(pg_fetch_result($res, 0, 'total_original'));
        $permite_alteracao     = trim(pg_fetch_result($res, 0, 'permite_alteracao'));
        $observacao_pedido     = @pg_fetch_result($res, 0, 'obs');
    }
}

#---------------- Recarrega Form em caso de erro -------------
if (strlen ($msg_erro) > 0) {
    $pedido                = $_POST['pedido'];
    $condicao              = $_POST['condicao'];
    $tipo_pedido           = $_POST['tipo_pedido'];
    $pedido_cliente        = $_POST['pedido_cliente'];
    $linha                 = $_POST['linha'];
    $codigo_posto          = $_POST['codigo_posto'];
}

$title       = traduz('cadastro.de.pedidos.de.pecas', $con);
$layout_menu = 'pedido';

if(!empty($cook_pedido)) {

    $sql = "SELECT pedido
            FROM tbl_pedido
            WHERE pedido = $cook_pedido
            AND   fabrica = $login_fabrica";
    //echo $sql;
    $res = pg_query($con,$sql);
    if(pg_num_rows($res) == 0){
        unset( $cook_pedido );
        setcookie ('cook_pedido');
        $cook_pedido = "";
    }
}
include "cabecalho.php";
?>

<style type="text/css">
    .menu_top {
        text-align: center;
        font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
        font-size: 10px;
        font-weight: bold;
        border: 0px solid;
        color:'#ffffff';
        background-color: '#596D9B';
    }
    .table_line1 {
        font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
        font-size: 11px;
        font-weight: normal;
        border: 0px solid;
    }

    #footer{
        clear: both;
    }


    body {
        font: 80% Verdana,Arial,sans-serif;
        background: #FFF;
    }

    .titulo {
        background:#7392BF;
        width: 650px;
        text-align: center;
        padding: 1px 1px; /* padding greater than corner height|width */
        font-size:12px;
        color:#FFFFFF;
    }
    .titulo h1 {
        color:white;
        font-size: 120%;
    }

    .subtitulo {
        background:#FCF0D8;
        width: 600px;
        text-align: center;
        padding: 2px 2px; /* padding greater than corner height|width */
        margin: 10px auto;
        color:#392804;
        text-transform: uppercase;
    }
    .subtitulo h1 {
        color:black;
        font-size: 120%;
    }

    .content {
        background:#CDDBF1;
        width: 600px;
        text-align: center;
        padding: 5px; /* padding greater than corner height|width */
        margin: 1em 0.25em;
        color:black;
    }

    .content h1 {
        color:black;
        font-size: 120%;
    }

    .extra {
        background:#BFDCFB;
        width: 600px;
        text-align: center;
        padding: 2px 2px; /* padding greater than corner height|width */
        margin: 1em 0.25em;
        color:#000000;
        text-align:left;
    }
    .extra span {
        color:#FF0D13;
        font-size:14px;
        font-weight:bold;
        padding-left:30px;
    }

    .error {
        background:#ED1B1B;
        width: 600px;
        text-align: center;
        padding: 2px 2px; /* padding greater than corner height|width */
        margin: 1em 0.25em;
        color:#FFFFFF;
        font-size:12px;
    }
    .error h1 {
        color:#FFFFFF;
        font-size:14px;
        font-size:normal;
        text-transform: capitalize;
    }

    .inicio {
        background:#8BBEF8;
        width: 600px;
        text-align: center;
        padding: 1px 2px; /* padding greater than corner height|width */
        margin: 0;
        color:#FFFFFF;
    }
    .inicio h1 {
        color:white;
        font-size: 105%;
        font-weight:bold;
    }

    .subinicio {
        background:#E1EEFD;
        width: 550px;
        text-align: center;
        padding: 1px 2px; /* padding greater than corner height|width */
        margin: 0.0em 0.0em;
        color:#FFFFFF;
    }
    .subinicio h1 {
        color:white;
        font-size: 105%;
    }

    #tabela {
        font-size:12px;
    }
    #tabela td{
        font-weight:bold;
    }

    .xTabela{
        font-family: Verdana, Arial, Sans-serif;
        font-size:12px;
        padding:3px;
    }

    #layout{
        width: 700px;
        margin:0 auto;
    }

    ul#split, ul#split li{
        margin:0 auto;
        padding:0;
        width:700px;
        list-style:none
    }

    ul#split li{
        float:left;
        width:700px;
    }

    ul#split h3{
        font-size:14px;
        margin:0px;
        padding: 5px 0 0;
        text-align:center;
        font-weight:bold;
        color:white;
    }
    ul#split h4{
        font-size:90%
        margin:0px;
        padding-top: 1px;
        padding-bottom: 1px;
        text-align:center;
        font-weight:bold;
        color:white;
    }

    ul#split p{
        margin:0;
    }
    p{
        background-color:# D9E2EF;
    }
    ul#split div{
        background: #D9E2EF;
    }

    li#one{
        text-align:center;
    }

    li#one div{
        border:1px solid #D9E2EF
    }
    li#one h3{
        background: #D9E2EF
    }

    li#one h4{
        background: #D9E2EF
    }

    .coluna1{
        width:150px;
        font-weight:bold;
        font-size:11px;
        display: inline;
        float:left;

    }
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
        text-transform: capitalize;
    }

    .texto_avulso{
        font: 14px Arial; color: rgb(89, 109, 155);
        background-color: #d9e2ef;
        text-align: center;
        width: 700px;
        margin: 0 auto;
        padding: 2px 0;
        border:1px solid #596d9b;
    }

    .formulario{
        background-color:#D9E2EF;verificaMultiplo
        font:11px Arial;
    }

    .subtitulo{
        background-color: #7092BE;
        font:bold 11px Arial;
        color: #FFFFFF;
    }

    table.tabela tr td{
        font-family: verdana;
        font-size: 11px;
        border-collapse: collapse;
        border:1px solid #596d9b;

    }

    .msg_sucesso{
        background-color: green;
        font: bold 16px "Arial";
        color: #FFFFFF;
        text-align:center;
        width: 700px;
        margin: 0 auto;
    }

    .msg_erro{
        background-color:#FF0000;
        font: bold 16px "Arial";
        color:#FFFFFF;
        text-align:center;
        width: 700px;
        margin: 0 auto;
    }

    .condicao_venda p{
        color: #000;
        font-size: 12px;
        margin: 0;
        padding: 0;
    }

    .condicao_venda li{
        margin: 0;
        padding: 2px 12px;
        text-align: left;
        font-size: 11px;
        color: #000;
    }
</style>

<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="screen">
<script type="text/javascript" src="js/jquery-1.8.3.min.js"></script>
<script type="text/javascript" src="js/php.default.min.js"></script>
<script type='text/javascript' src='js/jquery.bgiframe.js'></script>
<script type='text/javascript' src='js/jquery.dimensions.js'></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
<script type="text/javascript" src="plugins/shadowbox/shadowbox.js"></script>
<? include "javascript_pesquisas_novo.php" ?>
<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>
<script src="js/jquery.json-2.4.min.js"></script>
<script src="js/jquery.blockUI_2.39.js" ></script>
<script type="text/javascript">

    var LF = chr(10);
    var traducao = {
        acertar_quantidade:           "<?=traduz('acertar.quantidade', $con)?>",
        aguarde_submissao:            "<?=traduz('aguarde.submissao', $con)?>",
        apos_inserir_clique_finalizar:"<?=traduz('apos.inserir.todos.os.itens.desejados.clique.em.finalizar', $con)?>",
        aviso_condicao_de_pagamento:  "<?=traduz('atencao.a.condicao.de.pagamento.pode.influenciar.no.preco.das.pecas', $con)?>",
        aviso_perde_dados_digitados:  "<?=traduz('se.mudar.a.condicao.os.dados.digitados.serao.perdidos', $con)?>",
        click_em_acertar_qtde:        "<?=traduz('clique.em.acertar.quantidade.para.corrigir', $con)?>",
        codigo_de_substituido:        "<?=str_replace(chr(10), ' ', traduz(array('o.codigo','%','foi.substituido.pelo.codigo.acima'), $con, null, '%'))?>",
        confirma_condicao:            "<?=traduz('tem.certeza.que.deseja.a.condicao.%', $con)?>",
        erro_ao_excluir_peca:         "<?=traduz(array('erro.ao.excluir.a.peca', 'sep'=> ', ', 'tente.novamente'), $con)?>",
        informar_parte_para_pesquisa: "<?=traduz('informar.toda.parte.informacao.para.realizar.pesquisa', $con)?>",
        msg_cond_upload:              "<?=traduz(array('selecione.uma.condicao.de.pagamento','para.fazer.o.upload'), $con)?>",
        peca_deve_ser_multiplo:       "<?=traduz('a.peca.%.precisa.ser.multiplo.de.%', $con)?>",
        pecas_nao_encontradas:        "<?=traduz('as.seguintes.pecas.nao.foram.encontradas', $con)?>",
        tela_de_pesquisa:             "<?=traduz('tela.de.pesquisa', $con)?>",
        verificar_codigo_pecas:       "<?=traduz('favor.verificar.se.o.codigo.esta.correto', $con)?>"
    }

    $('input[name*="qtde_"]').numeric();
$(document).ready(function(){
    Shadowbox.init();

});
    function abrirPop(pagina,largura,altura) {
        w = screen.width;
        h = screen.height;

        meio_w = w/2;
        meio_h = h/2;

        altura2 = altura/2;
        largura2 = largura/2;
        meio1 = meio_h-altura2;
        meio2 = meio_w-largura2;

        // window.open(pagina,'pedido','height=' + altura + ', width=' + largura + ', top='+meio1+', left='+meio2+',scrollbars=yes, resizable=no, toolbar=no');
        window.open(pagina,'pedido','height=' + h + ', width=' + w + ',scrollbars=yes, resizable=no, toolbar=no');
    }


    function exibeTipo(){
        f = document.frm_pedido;
        if(f.linha.value == 3){
            f.tipo_pedido.disabled = false;
        }else{
            f.tipo_pedido.selectedIndex = 0;
            f.tipo_pedido.disabled = true;
        }
    }

    function confirmaCondicao(condicao) {
        var valida = $('#validacondicao');
        var condicaoanterior = $('#condicaoanterior');
        var msg = traducao.aviso_condicao_de_pagamento + LF;
            msg += traducao.confirma_condicao.replace('%', condicao) + LF;
            msg += traducao.aviso_perde_dados_digitados;

        if(confirm(msg)==true) {
            if (valida.val()=='sim') {
                var qtde = $('#qtde_item').val();
            }
            valida.val('sim');
            condicaoanterior.val($('#condicao').val());
        } else {
            if (valida.val()=='sim') {
                valida.val('nao');
                var seleciona = "option[value="+"'"+condicaoanterior.val()+"']";
                $("#condicao "+seleciona).attr('selected', 'selected');
            } else {
                valida.val('nao');
            }
        }
    }

    function adicionarLinha(linha) {

        var total_input = $('#tabela_itens > tbody > tr[name=peca] > td > input[name*="peca_referencia_"]').size();
        linha = parseFloat(total_input);

        if($.trim($('#tabela_itens > tbody > tr[name=peca] > td > input[name^=peca_referencia_]').last("input[name^=peca_referencia_").val().length) > 0) {
            $("#tabela_itens > tbody").append("<tr name='peca' rel='"+linha+"'>"+$("tr[id=modelo]").clone().html().replace(/__modelo__/g, linha)+"</tr>");
            $("#tabela_itens > tbody").append("<tr>"+$("tr[id=modelo_mudou]").clone().html().replace(/__modelo__/g, linha)+"</tr>");

            $('#qtde_item').val(linha);
            return;

            /*se ainda na criou a linha de item */
            if (!document.getElementById('peca_referencia_'+linha)) {
                var tbl = document.getElementById('tabela_itens');

                /*Criar TR - Linha*/
                var nova_linha = document.createElement('tr');
                nova_linha.setAttribute('rel', linha);


                /********************* COLUNA APAGAR ****************************/
                var celula = criaCelula('');
                celula.style.cssText = 'width: 10px;';

                var linha_nova = $(celula).append("<a href='javascript:void(0);' onclick='apagaLinha("+linha+");'><img src='imagens/icone_deletar.png' alt='Excluir Item' width='10' style='padding: 0; margin: 0;' /></a>");
                celula.linha_nova;
                nova_linha.appendChild(celula);


                /********************* COLUNA 1 ****************************/
                /*Cria TD */
                var celula = criaCelula('');
                celula.style.cssText = 'text-align: left;' ;

                var linha_nova = $(celula).append("<input style='width: 80px; text-align: left;' type='text' class='frm' name='peca_referencia_"+linha+"' id='peca_referencia_"+linha+"' value='' onblur='pesquisaPeca(this,"+linha+");' tabindex='"+(linha+1)+"' rel='"+linha+"' /> <img width='16px' border='0' align='absmiddle' style='cursor: pointer' onclick='buscaPeca(document.frm_pedido.peca_referencia_"+linha+","+linha+",\"referencia\");' src='imagens/lupa.png' />");

                celula.linha_nova;
                nova_linha.appendChild(celula);

                /*Cria TD */
                var celula = criaCelula('');
                celula.style.cssText = 'text-align: left;' ;

                var linha_nova = $(celula).append("<input style='width: 220px; text-align: left;' type='text' class='frm' name='peca_descricao_"+linha+"' id='peca_descricao_"+linha+"' value='' tabindex='"+(linha+1)+"' rel='"+linha+"' /> <img width='16px' border='0' align='absmiddle' style='cursor: pointer' onclick='buscaPeca(document.frm_pedido.peca_descricao_"+linha+","+linha+",\"descricao\");' src='imagens/lupa.png' />");

                celula.linha_nova;
                nova_linha.appendChild(celula);

                /*Cria TD */
                var celula = criaCelula('');
                celula.style.cssText = 'text-align: center;';

                var linha_nova = $(celula).append("<input style='width: 30px; text-align: left;' type='text' class='frm numeric' name='qtde_"+linha+"' id='qtde_"+linha+"' value='' onblur='fnc_preco("+linha+"); ' onkeyup='pulaCampo(\"quantidade\","+linha+",event); adicionarLinha("+linha+");' tabindex='"+(linha+1)+"' /><input type='hidden' class='frm' name='multiplo_"+linha+"' id='multiplo_"+linha+"' value='' />");
                celula.linha_nova;
                nova_linha.appendChild(celula);

                /*Cria TD */
                var celula = criaCelula('');
                celula.style.cssText = 'text-align: center;';

                var linha_nova = $(celula).append("<input style='width: 60px; text-align: right;' type='text' class='frm' name='preco_"+linha+"' id='preco_"+linha+"' readonly='readonly' onfocus='ignoraCampo("+linha+");' />");
                celula.linha_nova;
                nova_linha.appendChild(celula);

                /*Cria TD */
                var celula = criaCelula('');
                celula.style.cssText = 'text-align: center;';

                var linha_nova = $(celula).append("<input style='width: 60px; text-align: right;' type='text' class='frm' name='ipi_"+linha+"' id='ipi_"+linha+"' readonly='readonly' onfocus='ignoraCampo("+linha+");' rel='total_pecas' />");
                celula.linha_nova;
                nova_linha.appendChild(celula);

                /*Cria TD */
                var celula = criaCelula('');
                celula.style.cssText = 'text-align: center;';

                var linha_nova = $(celula).append("<input style='width: 60px; text-align: right;' type='text' class='frm' name='preco_ipi_"+linha+"' id='preco_ipi_"+linha+"' readonly='readonly' onfocus='ignoraCampo("+linha+");' rel='total_pecas' />");
                celula.linha_nova;

                nova_linha.appendChild(celula);

                /*Cria TD */
                var celula = criaCelula('');
                celula.style.cssText = 'text-align: center;';

                var linha_nova = $(celula).append("<input style='width: 60px; text-align: right;' type='text' class='frm' name='sub_total_"+linha+"' id='sub_total_"+linha+"' readonly='readonly' onfocus='ignoraCampo("+linha+");' rel='total_pecas' />");
                celula.linha_nova;

                nova_linha.appendChild(celula);

                /************ FINALIZA LINHA DA TABELA ***********/
                var tbody = document.createElement('TBODY');
                tbody.appendChild(nova_linha);
                tbl.appendChild(tbody);

                $('#qtde_item').val(linha);

                adicionarLinha2(linha);

            }
        }
    }

    function criaCelula(texto) {
        var celula = document.createElement('td');
        var textoNode = document.createTextNode(texto);
        celula.appendChild(textoNode);
        return celula;
    }

    function adicionarLinha2(linha) {

        linha = parseInt(linha);

        var tbl = document.getElementById('tabela_itens');

        var nova_linha = document.createElement('tr');
        $(nova_linha).append('<td colspan="8"><div id="mudou_'+linha+'"></div></td>');

        var tbody = document.createElement('TBODY');
        tbody.appendChild(nova_linha);
        tbl.appendChild(tbody);
    }

    function Trim(s){
        var l=0;
        var r=s.length -1;

        while(l < s.length && s[l] == ' '){
            l++;
        }
        while(r > l && s[r] == ' '){
            r-=1;
        }
        return s.substring(l, r+1);
    }

    function formatItem(row) {
        return row[0] + " - " + row[1];
    }

    function formatResult(row) {
        return row[0];
    }


    function apagaItemPedido(pedido, item) {
        if (item > 0 && pedido > 0) {

            $.ajax({
                url: "<?php echo $PHP_SELF;?>",
                type: "POST",
                data: {
                    apagarPedido:"apagarPedidoAJAX",
                    pedido:pedido,
                    item:item
                }
            })
            .done(function(resposta){
                if (resposta == 1) {

                    $("#"+item).remove();

                    var total = 0;
                    $('td.TotalAjax').each(function(index) {
                        total = total + parseFloat(($(this).text().replace(".", "")).replace(",", "."));
                    });

                    if (total == 0) {
                        $("#resumo_pedido").remove();
                        $("#btn_resumo_pedido").remove();
                    }

                    $("#printTotalAjax").html(number_format(total, 2 , ',','.'));

                } else {
                    alert(traducao.erro_ao_excluir_peca);
                }
            });

        }
    }


function pulaCampo(campo, posicao, e){

    var key = e.keyCode || e.which;

    if(campo == 'referencia'){
        if (key == '13') {
            $('#peca_descricao_'+posicao).focus();
        }
    }

    if(campo == 'descricao'){
        if (key == '13') {
            $('#qtde_'+posicao).focus();
        }
    }

    if(campo == 'quantidade'){
        var nova_posicao = posicao;
        var posicao = posicao + 1;

        if(posicao > 37)
            adicionarLinha(posicao-1);

        if (key == '13') {
            $("#peca_referencia_"+posicao).focus();
        }
    }
}

function ignoraCampo(posicao){
    var posicao = posicao;
    $('#peca_referencia_'+posicao).focus();
}

function verificaMultiplo(posicao){
    var referencia = $('#peca_referencia_'+posicao).val();
    var qtde = $('#qtde_'+posicao).val();

    if(referencia.length > 0 && qtde.length > 0){
        $.ajax({
            url: "<?php echo $PHP_SELF;?>",
            type: "POST",
            data: {
                verificaMultiplo:"verificaMultiplo",
                referencia:referencia,
                qtde:qtde,
                posicao:posicao
            }
        })
        .done(function(resposta){
            if(resposta != 0 && resposta != 1){
                dados = resposta.split("|");
                eval("document.frm_pedido.multiplo_"+posicao+".value = '"+dados[0]+"';");

                $('#acerta_quantidade_todas').css('display','block');
                $('#mudou_'+posicao).css('display','block');
                $('#mudou_'+posicao).css('background-color','#118A3A');

                var msg = traducao.acertar_quantidade + '</a> - ' + LF +
                    traducao.peca_deve_ser_multiplo + ' ' +
                    traducao.click_em_acertar_qtde;

                var acertaPecas = "<a href='javascript:void(0);' onclick='acertarQuantidade("+posicao+")' style='color: #FFF;'>" +
                    msg.replace('%', referencia).replace('%', dados[0]);
                $('#mudou_'+posicao).html(acertaPecas);

                eval("document.frm_pedido.peca_referencia_"+(parseInt(posicao)+1)+".focus();");
            }

            if(resposta == 1){
                $('#mudou_'+posicao).css('display','none');
            }
        });
    }
}

function fnc_preco (linha_form) {
    var posto    = <?= $login_posto ?>;
    var referencia = jQuery.trim($("#peca_referencia_"+linha_form).val());
    var qtde = jQuery.trim($("#qtde_"+linha_form).val());

    if(referencia.length > 0){
        campo_preco     = 'preco_' + linha_form;
        campo_ipi       = 'ipi_' + linha_form;
        campo_preco_ipi = 'preco_ipi_' + linha_form;
        document.getElementById(campo_preco).value = "";

        $.ajax({
		url: "<?$PHP_SELF?>",
            type: "GET",
            data: {
                linha_form:linha_form,
                produto_referencia:referencia
            }
        })
        .done(function (campos) {
            campos_array    = campos.split("|");
            ipi             = campos_array[2] ;
            preco           = campos_array[1];
            preco           = number_format( preco, 2 , ',','.' ) ;
            preco_ipi       = number_format( campos_array[3], 2 , ',','.' ) ;
            linha_form      = campos_array[0] ;

            document.getElementById(campo_preco).value = preco;
            document.getElementById(campo_ipi).value = ipi;
            document.getElementById(campo_preco_ipi).value = preco_ipi;

            fnc_calcula_total(linha_form);
        });
    }

}

function fnc_calcula_total (linha_form) {
    var total = 0;
    preco = document.getElementById('preco_'+linha_form).value;
    qtde = document.getElementById('qtde_'+linha_form).value;

    if (preco.search(/\d{1,3},\d{1,4}$/) != -1) { // Se o preço estiver formatado...
        preco = preco.replace('.','');
        preco = preco.replace(',','.');
    }
    if (qtde && preco){

        total = qtde * preco;
        <? if($login_fabrica == 94){?>
            total = total.toFixed(3);
        <? }else{?>
            total = total.toFixed(2);
            total = total.replace('.',',');
        <? } ?>
    }
    document.getElementById('sub_total_'+linha_form).value = total;

    //Totalizador
    var total_pecas = 0;
    $("input[id^='sub_total_']").each(function(){
        if ($(this).val()){
            tot = $(this).val();
            tot = tot.replace(',','.');
            tot = parseFloat(tot);
            total_pecas += tot;
        }
    });

    <?if (!in_array($login_fabrica,array(24,30))) { ?>
    total_pecas = total_pecas.toFixed(2);
    total_pecas = total_pecas.replace('.',',');
    document.getElementById('total_pecas').value = total_pecas;
    <?}?>
}

function acertarQuantidade(posicao){
    var qtde = $('#qtde_'+posicao).val();
    var referencia = $('#peca_referencia_'+posicao).val();
    var multiplo = $('#multiplo_'+posicao).val();

    if(referencia.length > 0 && qtde.length > 0 && multiplo.length > 0){
        var resultado = Math.ceil(qtde/multiplo)*multiplo;

        apagaLinhaAcertoPecas(posicao);
        $('#qtde_'+posicao).val(resultado);
        fnc_calcula_total(posicao);
    }

}

function acertaQuantidadeTodas(){
    var total = $('#qtde_item').val();

    for(i = 0; i < total; i++){
        acertarQuantidade(i);
    }

    $('#acerta_quantidade_todas').css('display','none');

}

function getLastTr () {
    var linha = -1;

    $("#tabela_itens > tbody > tr[name=peca]").each(function () {
        if ($.trim($(this).find("input[name^=peca_referencia_]").val()).length > 0) {
            linha = $(this).attr("rel");
        }
    });

    if (linha == -1) { return false; };

    linha++;

    return linha;
}

function isNumeric(n) {
    return !isNaN(parseFloat(n)) && isFinite(n);
}

function procuraPeca (ref) {
    var achou = false;

    $("#tabela_itens > tbody > tr").each(function () {
        if ($.trim($(this).find("input[name^=peca_referencia_]").val()) == ref) {
            achou = true;
        }
    });

    return achou;
}

function importaExcel () {
    $.blockUI({ message: "Importando peças aguarde..." });

    setTimeout(function () {
        var arrayPecas = $.trim($("#pecas_excel").val()).split("\n");
        var pecas = {};
        var ref;
        var qtde;
        var linha;
        var totalPecas = 0;

        if (arrayPecas.length > 500) {
            alert("O maximo de peças que pode ser importada é 500 peças");
            $.unblockUI();
            return;
        }

        document.getElementById('div_importa_excel').style.display='none';

        for (var i in arrayPecas) {
            if ($.trim(arrayPecas[i]).length > 0) {
                linha = $.trim(arrayPecas[i]).split("\t");

                if (linha.length > 0) {
                    ref = linha[0];
                    qtde = linha[1];

                    if (!isNumeric(qtde)) {
                        qtde = 0;
                    }

                    if (procuraPeca(ref) == false) {
                        pecas[i] = { ref: $.trim(ref), qtde: $.trim(qtde) };

                        totalPecas++;
                    }
                }
            }
        }

        var linhas = 0;
        $("#tabela_itens > tbody > tr[name=peca]").each(function () {
            if ($.trim($(this).find("input[name^=peca_referencia_]").val()).length == 0) {
                linhas++;
            }
        });

        if (totalPecas > linhas) {
            $("#qtde_item").val(parseInt($("#qtde_item").val()) + ((totalPecas - linhas) + 1));
            var x = $("#tabela_itens > tbody > tr[name=peca]").length;

            for (var i = linhas; i <= totalPecas; i++) {
                $("#tabela_itens > tbody").append("<tr name='peca' rel='"+x+"'>"+$("tr[id=modelo]").clone().html().replace(/__modelo__/g, x)+"</tr>");
                $("#tabela_itens > tbody").append("<tr>"+$("tr[id=modelo_mudou]").clone().html().replace(/__modelo__/g, x)+"</tr>");
                x++;
            }
        } else if (totalPecas == linhas) {
            $("#qtde_item").val(parseInt($("#qtde_item").val()) + ((totalPecas - linhas) + 1));
            var x = $("#tabela_itens > tbody > tr[name=peca]").length + 1;

            $("#tabela_itens > tbody").append("<tr name='peca' rel='"+x+"'>"+$("tr[id=modelo]").clone().html().replace(/__modelo__/g, x)+"</tr>");
            $("#tabela_itens > tbody").append("<tr>"+$("tr[id=modelo_mudou]").clone().html().replace(/__modelo__/g, x)+"</tr>");
        }

        var last = getLastTr();

        if (last == false) { last = 0; };

        $.ajaxSetup({
            async: false
        });

        $.each(pecas, function (key, peca) {
            setTimeout(function () {
                if (window.navigator.appName == "Microsoft Internet Explorer" && window.navigator.appVersion.match(/MSIE 8.0/g) && key == "indexOf") {
                    return;
                }

                $.ajax({
                    url: "pecas_importa_excel.php",
                    data: peca,
                    dataType: "JSON",
                    type: "GET",
                })
                .done(function (xpeca) {
                    if (window.navigator.appName != "Microsoft Internet Explorer" && !window.navigator.appVersion.match(/MSIE 8.0/g)) {
                        var offset = $("#tabela_itens > tbody > tr[rel="+last+"]").offset();
                        $(window).scrollTop(parseInt(offset.top) - 100);
                    }

                    if (!xpeca.erro) {
                        $("#tabela_itens > tbody > tr[rel="+last+"]").find("input[name^=peca_referencia_]").val(xpeca.ref);
                        $("#tabela_itens > tbody > tr[rel="+last+"]").find("input[name^=qtde_]").val(xpeca.qtde);
                        $("#tabela_itens > tbody > tr[rel="+last+"]").find("input[name^=peca_descricao_]").val($("<div/>").html(xpeca.desc).text());
                        $("#tabela_itens > tbody > tr[rel="+last+"]").find("input[name^=preco_]").val(xpeca.preco);
                        $("#tabela_itens > tbody > tr[rel="+last+"]").find("input[name^=ipi_]").val(xpeca.ipi);

                       // verificaDePara(xpeca.ref, last);
                        verificaMultiplo(last);
                        fnc_preco(last);
                    } else {
                        $("#tabela_itens > tbody > tr[rel="+last+"]").find("input[name^=peca_referencia_]").val(peca.ref);
                        $("#tabela_itens > tbody > tr[rel="+last+"]").find("input[name^=qtde_]").val(peca.qtde);
                        $("#tabela_itens > tbody > tr[rel="+last+"]").css("background-color", "#f00");
                    }

                    last++;
                });
            }, 500);
        });

        $.unblockUI();
    }, 500);
}

function pesquisaPeca(campo, linha){

    var referencia = campo;//$(campo).attr('value');
    var posicao = linha; //$(campo).attr('rel');
    //alert(referencia);
    if (jQuery.trim(referencia.value).length > 1){
        $.ajax({
            url: "<?php echo $PHP_SELF;?>",
            type: "POST",
            data: {
                pesquisaPeca:"pesquisaPeca",
                referencia:referencia,
                posicao:posicao
            }
        })
        .done(function(resposta){
            if (jQuery.trim(resposta).length > 3){
                var retorno = resposta.split('|');
                retorna_peca(retorno[0],retorno[1],retorno[2]);
            }
        });
    }
}

function retorna_peca(referencia,descricao,posicao){
    eval("document.frm_pedido.peca_referencia_"+posicao+".value = '"+referencia+"';");
    eval("document.frm_pedido.peca_descricao_"+posicao+".value = '"+descricao+"';");

    //verificaDePara(referencia, posicao);

    eval("document.frm_pedido.qtde_"+posicao+".focus();");
}

function buscaPeca(valor,posicao,tipo){
    var peca = valor.value ;
    //alert(peca);

    if (jQuery.trim(peca).length > 1){
        Shadowbox.open({
            content: "pesquisa_peca_jacto.php?"+tipo+"="+peca+"&posicao="+posicao+"&tipo="+tipo,
            player:	 "iframe",
            title:	 traducao.tela_de_pesquisa,
            width:   800,
            height:  500
        });
    }else
        alert(traducao.informar_parte_para_pesquisa);
}

function apagaLinha(linha){
    var ref = $("input[name=peca_referencia_" + linha + "]").val();

    //if (ref && token) { deletesRefbyToken(ref, token); };

    $("#mudou_"+linha).html('');
    $("#mudou_"+linha).css('display','none');

    $("input[name=pedido_item_"     + linha + "]").val('');
    $("input[name=peca_referencia_" + linha + "]").val('');
    $("input[name=peca_descricao_"  + linha + "]").val('');
    $("input[name=qtde_"            + linha + "]").val('');
    $("input[name=preco_"           + linha + "]").val('');
    $("input[name=ipi_"             + linha + "]").val('');
    $("input[name=preco_ipi_"       + linha + "]").val('');
    $("input[name=sub_total_"       + linha + "]").val('');
    $('#peca_referencia_'           + linha).parent().parent().css('background-color','#D9E2EF');
    $("input[name=peca_referencia_" + linha + "]").focus();
}


</script>


<?
if($dados_array > 0){
    if($dados_array == 1)
        $msg_erro = traduz('nao.foi.cadastrada.a.peca.no.pedido.verifique.a.quantidade.ou.referencia', $con);
    else
        $msg_erro = traduz('nao.foram.cadastradas.%.pecas.no.pedido.verifique.a.quantidade.ou.referencia', $con, $cook_idioma, $dados_array);
}

if (strlen ($msg_erro) > 0) {

    if (strpos ($msg_erro,"Cannot insert a duplicate key into unique index tbl_os_sua_os") > 0) {
        $msg_erro = traduz('esta.ordem.de.servico.ja.foi.cadastrada', $con);
    }
    // retira palavra ERROR:
    if (strpos($msg_erro,"ERROR: ") !== false) {

        $erro = traduz('foi.detectado.o.seguinte.erro', $con) . '<br />';
        $msg_erro = substr($msg_erro, 6);
    }
    // retira CONTEXT:
    if (strpos($msg_erro,"CONTEXT:")) {
        $x = explode('CONTEXT:',$msg_erro);
        $msg_erro = $x[0];
    }
}
?>

<form name="frm_pedido" method="post" action="">
<input class="frm" type="hidden" name="pedido" value="<? echo $pedido; ?>">
<input class="frm" type="hidden" name="voltagem" value="<? echo $voltagem; ?>">

<? if ($distribuidor_digita == 't') { ?>
    <table width="100%" border="0" cellspacing="5" cellpadding="0">
    <tr valign='top' style='font-size:12px'>
        <td nowrap align='center'>
            <?fecho('distribuidor.pode.digitar.pedidos.para.seus.postos', $con);?>
            <br>
            <?fecho('digite.o.codigo.do.posto', $con, $cook_idioma);?>
            <input type='text' name='codigo_posto' size='5' maxlength='10' value='<? echo $codigo_posto ?>'>
            <?fecho('ou.deixe.em.branco.para.seus.proprios.pedidos', $con);?>
        </td>
    </tr>
    </table>
<? } ?>

<ul id="split"  style="width:700px;margin: 0; padding: 0;" bgcolor="#D9E2EF">
<li id="one" style='margin: 0; padding: 0;'>

<?
    //VERIFICA SE POSTO PODE PEDIR PECA EM GARANTIA ANTECIPADA
    $sql = "SELECT garantia_antecipada FROM tbl_posto_fabrica WHERE fabrica=$login_fabrica AND posto=$login_posto";

    $res = pg_query($con,$sql);
    if (pg_num_rows($res) > 0) {
        $garantia_antecipada = pg_fetch_result($res,0,0);
        if($garantia_antecipada <> "t") {
            $garantia_antecipada ="f";
        }
    }

    if(strlen($msg_erro) != 0){
        echo "<div class='msg_erro' style='background: #FF0000; padding: 2px 0'>";
            if (strpos ($msg_erro,"(mudou)") > 0)
                fecho(array('atencao','<br />','verifique.as.referencias.abaixo.pois.ocorreram.mudancas.de.de.para'), $con);
            else
                echo $erro.$msg_erro;
        echo "</div>";
    }

    ?>
    <table width='700' class='formulario' align='center' cellspacing='2' cellpadding='2' border='0'>
        <tr>
            <td class="titulo_tabela" colspan='3'><?fecho('cadastro.de.pedidos', $con)?></td>
        </tr>
        <tr class='subtitulo'>
            <td align="center" colspan='3'><? echo $frase; ?> </td>
        </tr>
        <tr style='text-transform: capitalize'>
            <td style='padding-left: 50px; text-align: left'>
                <?fecho('ordem.de.compra', $con)?><br />
                <input class="frm" type="text" name="pedido_cliente" maxlength="20" value="<? echo $pedido_cliente ?>" style='width: 150px'>
            </td>
            <td style=' text-align: left'>
                <?fecho('tipo.de.pedido', $con)?><br />
            <?
                $cond_locadora = "AND tbl_tipo_pedido.tipo_pedido in (196)";

                if (pg_num_rows($res) > 0) {
                    echo "<select size='1' name='tipo_pedido' class='frm' style='width: 150px'>";
            $sql = "SELECT tipo_pedido,
                            descricao
                    FROM tbl_tipo_pedido
                    JOIN tbl_posto_fabrica ON tbl_posto_fabrica.fabrica = tbl_tipo_pedido.fabrica
                    WHERE ((tbl_posto_fabrica.pedido_em_garantia IS TRUE AND tbl_tipo_pedido.pedido_em_garantia IS TRUE)
                    OR (tbl_posto_fabrica.pedido_faturado IS TRUE AND tbl_tipo_pedido.pedido_faturado IS TRUE))
                    AND tbl_posto_fabrica.posto = $login_posto
                    AND tbl_posto_fabrica.fabrica = $login_fabrica";

            $res = pg_query ($con,$sql);

                    $res = pg_query($con,$sql);

                    if (strlen($cook_pedido) == 0)
                        echo "<option value='0' selected>- " . traduz('selecione', $con) . "</option>";

                    for ($i = 0 ; $i < pg_num_rows($res) ; $i++ ) {
                        $selected = $tipo_pedido == pg_fetch_result($res,$i,tipo_pedido) ? " selected " : "";
                        echo "<option value='" . pg_fetch_result($res,$i,tipo_pedido) . "' $selected>" . pg_result ($res,$i,descricao). "</option>";
                    }
                    echo "</select>";
                }else{
                    echo "<select size='1' name='tipo_pedido' class='frm' ";
                    echo " style='width: 150px'>";
                    $sql = "SELECT   *
                            FROM    tbl_tipo_pedido
                            WHERE   (tbl_tipo_pedido.descricao ILIKE '%Faturado%'
                               OR   tbl_tipo_pedido.descricao ILIKE '%Venda%')
                            AND     tbl_tipo_pedido.fabrica = $login_fabrica
                            AND     (garantia_antecipada is false or garantia_antecipada is null)
                            ORDER BY tipo_pedido;";

                    #HD 47695
                    if (strlen($tipo_pedido)> 0 AND $permite_alteracao == 't'){
                        $sql = "SELECT   *
                                FROM     tbl_tipo_pedido
                                WHERE    fabrica = $login_fabrica ";
                        if (strlen($tipo_pedido)>0){
                            $sql .= " AND      tbl_tipo_pedido.tipo_pedido = $tipo_pedido ";
                        }
                        $sql .= " ORDER BY tipo_pedido;";
                    }

                    $res = pg_query($con,$sql);

                    for ($i = 0 ; $i < pg_num_rows($res) ; $i++ ) {
                        $selected = $tipo_pedido == pg_fetch_result($res,$i,tipo_pedido) ? " selected " : "";
                        echo "<option value='" . pg_fetch_result($res,$i,tipo_pedido) . "' $selected>" . pg_result ($res,$i,descricao). "</option>";
                    }

                    if($garantia_antecipada=="t"){
                        $sql = "SELECT   *
                                FROM     tbl_tipo_pedido
                                WHERE    fabrica = $login_fabrica
                                AND garantia_antecipada is true
                                ORDER BY tipo_pedido ";
                        $res = pg_query($con,$sql);

                        for ($i = 0 ; $i < pg_num_rows($res) ; $i++ ) {
                            $selected = $tipo_pedido == pg_fetch_result($res,$i,tipo_pedido) ? " selected " : "";
                            echo "<option value='" . pg_fetch_result($res,$i,tipo_pedido) . "' $selected>" . pg_result ($res,$i,descricao). "</option>";
                        }
                    }
                    echo "</select>";
                }
            ?>
            </td>
            <td style=' text-align: left'>
                <?fecho('condicao.de.pagamento', $con);?><br>
                <input type='hidden' id='validacondicao' name='validacondicao' value=''>
                <input type='hidden' id='condicaoanterior' name='condicaoanterior' value=''>
                <select size='1' id='condicao' name='condicao' class='frm'  onchange='confirmaCondicao(this.options[this.selectedIndex].text)' style='width: 150px' >
                <?
                     $sql1 = "SELECT  tbl_condicao.*
                            FROM    tbl_condicao
                            JOIN    tbl_posto_condicao USING (condicao)
                            JOIN    tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto_condicao.posto and tbl_posto_fabrica.fabrica = $login_fabrica
                            WHERE   tbl_posto_condicao.posto = $login_posto
                            AND     tbl_condicao.fabrica     = $login_fabrica
                            AND     tbl_condicao.visivel IS TRUE
                            AND     tbl_condicao.descricao ILIKE '%garantia%'
                            ORDER BY lpad(trim(tbl_condicao.codigo_condicao)::text, 10,'0') ";
                    $res = pg_query($con,$sql1);

                    if (pg_num_rows($res) == 0 ) {
                        $sql = "SELECT tbl_condicao.*
                                FROM tbl_condicao
                                WHERE tbl_condicao.fabrica = $login_fabrica
                                AND tbl_condicao.visivel IS TRUE ";
                        $res = pg_query($con,$sql);
                    }

                    if (strlen($cook_pedido)==0)
                        echo "<option value='0' selected >- selecione</option>";

                    for ($i = 0 ; $i < pg_num_rows($res) ; $i++ ) {
                        $selected = $condicao == pg_fetch_result($res,$i,condicao) ? " selected " : "";
                        echo "<option value='" . pg_fetch_result($res,$i,condicao) . "' $selected>" . pg_result ($res,$i,descricao). "</option>";
                    }
                ?>
                </select>
            </td>
        </tr>

        <tr style='background: #C9D5E5; text-align: left'>
            <td style='padding-left: 50px' colspan="4">
<?
            if($login_fabrica == 125 && $login_tipo_posto == 399){
?>
            <?fecho('tipo.frete', $con);?><br>
            <select name="tipo_frete" class="frm">
                <option value="CIF" <?php if($tipo_frete == 'CIF') {echo "selected"; }?>>CIF</option>
                <option value="FOB" <?php if($tipo_frete == 'FOB') {echo "selected"; }?>>FOB</option>
            </select>
<?
        }else{
?>
            &nbsp;
<?
        }
?>
            </td>
        </tr>
        <tr>
            <td colspan='3' style='text-align: center; padding: 10px 0;'>
                <input style='width: 180px; margin: 0 12px;cursor: pointer' type='button' value='<?=ucwords(traduz('importa.pecas.do.excel', $con))?>' onclick="document.getElementById('div_importa_excel').style.display='block' ;" />
                <input style='width: 180px; margin: 0 12px;cursor: pointer' type='button' value='<?=ucfirst(traduz('gravar', $con))?>' onclick="if (document.frm_pedido.btn_acao.value == '' ) { document.frm_pedido.btn_acao.value='gravar' ; alert(traducao.apos_inserir_clique_finalizar); document.frm_pedido.submit() } else { alert (traducao.aguarde_submissao) } " border='0' />
                <?php
                    $sqlCnpj = "SELECT cnpj FROM tbl_posto WHERE posto = $login_posto";
                    $resCnpj = pg_query($con,$sqlCnpj);
                    echo "<input type='hidden' name='cnpj_posto' value='".pg_fetch_result($resCnpj, 0, 'cnpj')."'>"
                ?>
                <input type='hidden' name='token_pedido' value=''>
            </td>
        </tr>
        <tr>
            <td width='270'>&nbsp;</td>
            <td width='233'>&nbsp;</td>
            <td width='*'>&nbsp;</td>
        </tr>
        <tr class='subtitulo'>
            <td align="center"  colspan='3'> <?=traduz('pecas', $con)?> </td>
        </tr>
    </table>


        <?#-------------------- Linha do pedido -------------------

        #HD 47695 - Para alterar o pedido, mas nao a linha, por causa da tabela de preço
        if ($permite_alteracao == 't' and strlen($linha)>0){
            ?><input type="hidden" name="linha" value="<? echo $linha; ?>"><?
        }else{
            $sql = "SELECT  tbl_linha.linha            ,
                            tbl_linha.nome
                    FROM    tbl_linha
                    JOIN    tbl_fabrica USING(fabrica)
                    JOIN    tbl_posto_linha  ON tbl_posto_linha.posto = $login_posto
                                            AND tbl_posto_linha.linha = tbl_linha.linha
                    WHERE   tbl_fabrica.linha_pedido is true
                    AND     tbl_linha.fabrica = $login_fabrica ";

            #permite_alteracao - HD 47695
            if (strlen($tipo_pedido)> 0 AND $permite_alteracao == 't' and strlen($linha)>0){
                $sql .= " AND tbl_linha.linha = $linha ";
            }
            $res = pg_query($con,$sql);
            if (pg_num_rows($res) > 0) {
            ?>
                <p><span class='coluna1'><?=traduz('linha', $con)?></span>
                        <?
                        echo "<select name='linha' class='frm' ";
                        echo ">";
                        echo "<option></option>";
                        for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
                            echo "<option value='".pg_fetch_result($res,$i,linha)."' ";
                            if ($linha == pg_fetch_result($res,$i,linha) ) echo " selected";
                            echo ">";
                            echo pg_fetch_result($res,$i,nome);
                            echo "</option>\n";
                        }
                        echo "</select>";
                        ?>
                </p>
            <?
            }
        }
        ?>
        <input class="frm" type="hidden" name="produto_referencia" value="<? echo $produto_referencia; ?>">

        <table border="0" width='700' cellspacing="1" cellpadding="0" align="center" class='formulario'  name="tabela_itens" id="tabela_itens">
            <thead>
                <tr height="20" class='titulo_coluna' nowrap>
                    <th align='left' colspan='2' style='padding-left: 15px'><?=traduz('referencia', $con)?></th>
                    <th align='left'><?=traduz('descricao.componente', $con)?></th>
                    <th align='center'><?=traduz('qtde', $con)?></th>
                    <th align='center'><?=traduz(array('preço', 'sem.ipi'), $con)?></th>
                    <th align='center'>% IPI</th>
                    <th align='center'><?=traduz(array('preço', 'com.ipi'), $con)?></th>
                    <th align='center'><?=traduz('total', $con)?></th>
                </tr>
            </thead>
            <tbody>
                <tr id="modelo" rel="__modelo__" style="visibility:hidden;">
                    <td style='width: 10px'>
                        <a href='javascript:void(0);' onclick="apagaLinha(__modelo__);";><img src='imagens/icone_deletar.png' alt='Excluir Item' width='10' style='padding: 0; margin: 0;' /></a>
                    </td>
                    <td align='left'>
                        <input class="frm" type="text" name="peca_referencia___modelo__" id="peca_referencia___modelo__" value="" style='width: 80px'  onblur="pesquisaPeca(this, __modelo__);" onkeyup="pulaCampo('referencia',__modelo__,event);" rel='__modelo__'>
                        <input type="hidden" name="posicao">
                        <img src='imagens/lupa.png' border='0' align='absmiddle' onclick="fnc_pesquisa_produto(document.frm_pedido.peca_referencia___modelo__,__modelo__,'referencia');" style='cursor: pointer' width='16px' />
                    </td>
                    <td align='left'>
                        <input class="frm" type="text" id="peca_descricao___modelo__" name="peca_descricao___modelo__" value="" style='width: 220px'  onkeyup="pulaCampo('descricao',__modelo__,event);" rel='__modelo__'>
                        <img src='imagens/lupa.png' border='0' align='absmiddle' onclick="fnc_pesquisa_produto(document.frm_pedido.peca_descricao___modelo__,__modelo__,'descricao');" style='cursor: pointer' width='16px' />
                    </td>
                    <td align='center'>
                        <?php
                            if ($i > 37) {
                                $comando = "adicionarLinha(__modelo__-1);";
                            }
                        ?>
                        <input class="frm numeric" type="text" name="qtde___modelo__" id="qtde___modelo__" style='width: 30px'  maxlength='5' value=""
                        <? echo "onblur='verificaMultiplo(__modelo__); fnc_preco (__modelo__); $comando'"; ?> onkeyup="pulaCampo('quantidade',__modelo__,event);">
                        <input class="frm" type="hidden" name="multiplo___modelo__" id="multiplo___modelo__" value="">
                    </td>

                    <td align='center'>
                        <input class="frm" id="preco___modelo__" type="text" name="preco___modelo__" style='width: 60px'   value="" readonly  style='text-align:right; color:#000;' onfocus="ignoraCampo(__modelo__);">
                    </td>

                    <td align='center'>
                        <input class="frm" id="ipi___modelo__" type="text" name="ipi___modelo__" style='width: 60px'   value="" readonly  style='text-align:right; color:#000;' onfocus="ignoraCampo(__modelo__);">
                    </td>


                    <td align='center'>
                        <input class="frm" id="preco_ipi___modelo__" type="text" name="preco_ipi___modelo__" style='width: 60px'  value="" readonly  style='text-align:right; color:#000;' onfocus="ignoraCampo(__modelo__);">
                    </td>

                    <td align='center'>
                        <input class="frm" name="sub_total___modelo__" id="sub_total___modelo__" type="text" style='width: 60px'  rel='total_pecas' readonly  style='text-align:right; color:#000;' value='' onfocus="ignoraCampo(__modelo__);">
                    </td>

                </tr>
                <tr id='modelo_mudou' style="visibility:hidden;">
                    <td colspan='8'><div id='mudou___modelo__' style='display: none; text-align: left; padding: 2px 10px' ></div></td>
                </tr>
            <?
            $total_geral = 0;

            echo "<input type='hidden' name='qtde_item' value='$qtde_item' id='qtde_item'>";
            for ($i = 0 ; $i < $qtde_item ; $i++) {

                    /*
                        Esse script inserido trabalhalha com os campos das peças, ele apaga todos os campos
                        quando a descrição não está inserida, não deixa multiplicar a quantidade por preço caso a quantidade não seja
                        digitada e limpa todos os campos caso seja apagada a descrição da peça.
                    */
                    echo "<script>
                                $( document ).ready( function(){

                                    $('#total_pecas').each(function() {
                                        var total_pecas = $('#total_pecas').val();
                                        total_pecas = total_pecas.replace('.' ,',');
                                        $('#total_pecas').val(total_pecas);

                                    });

                                    $('#qtde_$i').numeric();
                                        $( '#qtde_$i' ).blur( function(e){
                                            if( $( '#qtde_$i' ).val() == '' || $( '#qtde_$i' ).val() == null || $( '#qtde_$i' ).val() == 0 )
                                            {
                                                if( $( '#peca_referencia_$i' ).val() != '' && $( '#peca_referencia_$i' ).val() != null  && e.which  != 8 && e.which != 46 )
                                                {
                                                    $( '#qtde_$i' ).val( 1 );

                                                }
                                            }
                                        } );

                                $( '#peca_referencia_$i' ).blur( function(){

                                    if( $( '#peca_referencia_$i' ).val() == '' )    {

                                        $( '#qtde_$i' ).val( '' );
                                        $( '#preco_$i' ).val( '' ) ;
                                        fnc_calcula_total($i);
                                        $( '#sub_total_$i' ).val( '' );
                                        $( '#produto_referencia_$i' ).val( '' );
                                        $( '#peca_referencia_$i' ).val( '' );
                                    }
                                } )

                            } );
                     </script>";


                if (strlen($pedido) > 0){   // AND strlen ($msg_erro) == 0
                    $sql = "SELECT  tbl_pedido_item.pedido_item,
                                    tbl_peca.referencia        ,
                                    tbl_peca.descricao         ,
                                    tbl_pedido_item.qtde       ,
                                    tbl_pedido_item.preco      ,
                                    tbl_pedido_item.ipi
                            FROM  tbl_pedido
                            JOIN  tbl_pedido_item USING (pedido)
                            JOIN  tbl_peca        USING (peca)
                            WHERE tbl_pedido_item.pedido = $pedido
                            AND   tbl_pedido.posto   = $login_posto
                            AND   tbl_pedido.fabrica = $login_fabrica
                            ORDER BY tbl_pedido_item.pedido_item";

                    $res = pg_query($con,$sql);

                    if (pg_num_rows($res) > 0) {
                        $pedido_item     = trim(@pg_fetch_result($res,$i,pedido_item));
                        $peca_referencia = trim(@pg_fetch_result($res,$i,referencia));
                        $peca_descricao  = trim(@pg_fetch_result($res,$i,descricao));
                        $qtde            = trim(@pg_fetch_result($res,$i,qtde));
                        $preco           = trim(@pg_fetch_result($res,$i,preco));
                        $ipi             = trim(@pg_fetch_result($res,$i,ipi));

                        if (strlen($preco) > 0) $preco = number_format($preco,2,',','.');

                        $produto_referencia = '';
                        $produto_descricao  = '';
                    }else{
                        $produto_referencia = $_POST["produto_referencia_" . $i];
                        $produto_descricao  = $_POST["produto_descricao_"  . $i];
                        $pedido_item        = $_POST["pedido_item_"        . $i];
                        $peca_referencia    = $_POST["peca_referencia_"    . $i];
                        $peca_descricao     = $_POST["peca_descricao_"     . $i];
                        $qtde               = $_POST["qtde_"               . $i];
                        $preco              = $_POST["preco_"              . $i];
                        $ipi                = $_POST["ipi_"                . $i];
                        $preco_ipi          = $_POST["preco_ipi_"          . $i];
                    }
                }else{
                    $produto_referencia = $_POST["produto_referencia_" . $i];
                    $produto_descricao  = $_POST["produto_descricao_"  . $i];
                    $pedido_item        = $_POST["pedido_item_"        . $i];
                    $peca_referencia    = $_POST["peca_referencia_"    . $i];
                    $peca_descricao     = $_POST["peca_descricao_"     . $i];
                    $qtde               = $_POST["qtde_"               . $i];
                    $preco              = $_POST["preco_"              . $i];
                    $ipi                = $_POST["ipi_"                . $i];
                    $preco_ipi          = $_POST["preco_ipi_"          . $i];
                }

                $peca_referencia = trim ($peca_referencia);

                #--------------- Valida Peças em DE-PARA -----------------#
                $tem_obs = false;
                $linha_obs = "";

                $sql = "SELECT para FROM tbl_depara WHERE de = '$peca_referencia' AND fabrica = $login_fabrica";
                $resX = pg_query($con,$sql);

                if (pg_num_rows($resX) > 0) {
                    $linha_obs = traduz('peca.original.%.mudou.para.o.codigo.acima', $con, $cook_idioma, (array) $peca_referencia);
                    $peca_referencia = pg_fetch_result($resX,0,0);
                    $tem_obs = true;
                }

                #--------------- Valida Peças Fora de Linha -----------------#
                $sql = "SELECT * FROM tbl_peca_fora_linha WHERE referencia = '$peca_referencia' AND fabrica = $login_fabrica";

                $resX = pg_query($con,$sql);
                if (pg_num_rows($resX) > 0) {
                    $libera_garantia = pg_fetch_result($resX,0,libera_garantia);
                    $linha_obs .= traduz('peca.acima.esta.fora.de.linha', $con);
                    $tem_obs = true;
                }

                if (strlen ($peca_referencia) > 0) {
                    $sql = "SELECT descricao FROM tbl_peca WHERE referencia = '$peca_referencia' AND fabrica = $login_fabrica";
                    $resX = pg_query($con,$sql);
                    if (pg_num_rows($resX) > 0) {
                        $peca_descricao = pg_fetch_result($resX,0,0);
                    }
                }

                $peca_descricao = trim ($peca_descricao);

                $cor="";
                //if ($linha_erro == $i and strlen ($msg_erro) > 0) $cor='#ffcccc';
                //if ($linha_erro == $i and strlen ($msg_erro) > 0) $cor='#ffcccc';
                //if ($tem_obs) $cor='#FFCC33';

                $tabindex = $i + 1;
            ?>
                <tr name="peca" rel="<?=$i?>" bgcolor="<? echo $cor ?>" nowrap>
                    <td style='width: 10px'>
                        <a href="javascript:void(0);" onclick="apagaLinha(<?=$i?>);"><img src='imagens/icone_deletar.png' alt='Excluir Item' width='10' style='padding: 0; margin: 0;' /></a>
                    </td>
                    <td align='left'>
                        <input class="frm" type="text" name="peca_referencia_<?=$i?>" id="peca_referencia_<?=$i?>" value="<? echo $peca_referencia; ?>" style='width: 80px'  tabindex='<?php echo $tabindex;?>'  rel='<?php echo $i?>'>
                        <input type="hidden" name="posicao">
                        <img src='imagens/lupa.png' border='0' align='absmiddle' onclick="buscaPeca(document.frm_pedido.peca_referencia_<?php echo $i?>,<?php echo $i?>,'referencia');" style='cursor: pointer' width='16px' />
                    </td>
                    <td align='left'>
                        <input class="frm" type="text" id="peca_descricao_<? echo $i ?>" name="peca_descricao_<? echo $i ?>" value="<? echo $peca_descricao ?>" style='width: 220px'  tabindex='<?php echo $tabindex;?>'  rel='<?php echo $i?>'>
                        <img src='imagens/lupa.png' border='0' align='absmiddle' onclick="buscaPeca(document.frm_pedido.peca_descricao_<?php echo $i?>,<?php echo $i?>,'descricao');" style='cursor: pointer' width='16px' />
                    </td>
                    <td align='center'>
                        <?php
                            if ($i > 37) {
                                $comando = "adicionarLinha($i-1);";
                            }
                        ?>
                        <input class="frm numeric" type="text" name="qtde_<? echo $i ?>" id="qtde_<? echo $i ?>" style='width: 30px'  maxlength='5' value="<? echo $qtde ?>"
                        <? echo "onblur='verificaMultiplo($i); fnc_preco ($i); $comando'"; ?> tabindex='<?php echo $tabindex;?>' onkeyup="pulaCampo('quantidade',<?php echo $i?>,event);">
                        <input class="frm" type="hidden" name="multiplo_<? echo $i ?>" id="multiplo_<? echo $i ?>" value="">
                    </td>

                    <td align='center'>
                        <input class="frm" id="preco_<? echo $i ?>" type="text" name="preco_<? echo $i ?>" style='width: 60px'   value="<? echo $preco ?>" readonly  style='text-align:right; color:#000;' onfocus="ignoraCampo(<?php echo $i?>);">
                    </td>

                    <td align='center'>
                        <input class="frm" id="ipi_<? echo $i ?>" type="text" name="ipi_<? echo $i ?>" style='width: 60px'   value="<? echo $ipi ?>" readonly  style='text-align:right; color:#000;' onfocus="ignoraCampo(<?php echo $i?>);">
                    </td>


                    <td align='center'>
                        <input class="frm" id="preco_ipi_<? echo $i ?>" type="text" name="preco_ipi_<? echo $i ?>" style='width: 60px'  value="<? echo $preco_ipi ?>" readonly  style='text-align:right; color:#000;' onfocus="ignoraCampo(<?php echo $i?>);">
                    </td>

                    <td align='center'>
                        <input class="frm" name="sub_total_<? echo $i ?>" id="sub_total_<? echo $i ?>" type="text" style='width: 60px'  readonly  style='text-align:right; color:#000;' value='<?
                            if ($qtde &&  $preco_ipi) {
                                if( $preco_ipi == '' || $preco_ipi == 0 || $preco_ipi == null ){
                                    $preco_ipi = 1;
                                }
                                $preco_ipi = str_replace(',', '.', str_replace('.', '', $preco_ipi));
                                $total_geral += $preco_ipi * $qtde;

                                $preco_ipi = $preco_ipi * $qtde;
                                $preco_ipi = number_format($preco_ipi,2,',','.');
                                echo $preco_ipi;
                            }
                            ?>' onfocus="ignoraCampo(<?php echo $i?>);">
                            <?php ?>
                    </td>

                </tr>

                <?
                if ($tem_obs) {
                    echo "<tr bgcolor='$cor' style='font-size:12px'>";
                        echo "<td colspan='8'>$linha_obs</td>";
                    echo "</tr>";
                }
                echo "<tr>";
                    echo "<td colspan='8'><div id='mudou_$i' style='display: none; text-align: left; padding: 2px 10px' ></div></td>";
                echo "</tr>";
            }
            ?>
            </table>
            <?
                echo "<table border='0' cellspacing='0' cellpadding='2' align='center' class='xTabela formulario' width='700px'>";
                echo "<tr style='font-size:12px' align='right'>";
                echo "<td colspan='7' allign='right'><b>Total</b>: <INPUT TYPE='text' size='10' style='text-align:right;' class='frm' id='total_pecas'";
                    if(strlen($total_geral) > 0){
                        $total_geral = number_format($total_geral,2,',','.');
                        echo " value='$total_geral'";
                    }
                echo "></td>";
                echo "</tr>";
                echo "</table>";
            ?>

<p class='formulario' style='padding: 20px; text-align: center'>
        <input type='button' id='acerta_quantidade_todas' value='<?=traduz('acertar.quantidade.de.todas.as.pecas', $con)?>' onclick="acertaQuantidadeTodas();" border='0' style='cursor: pointer; display: none' />
            <input type="hidden" name="btn_acao" value="">
                <input type='button' value='<?=traduz('gravar', $con)?>' onclick="if (document.frm_pedido.btn_acao.value == '' ) { document.frm_pedido.btn_acao.value='gravar' ; alert(traducao.apos_inserir_clique_finalizar); document.frm_pedido.submit() } else { alert (traducao.aguarde_submissao) } " border='0' style='cursor: pointer' />
        </p>

        </li>
        </ul>
</form>

    <div id='div_importa_excel' style='display: none ; position: absolute ; top: 300px ; left: 28%; background-color:#D9E2EF ; width: 600px ; border:solid 1px #330099 ' onkeypress="if(event.keyCode==27){document.getElementById('div_importa_excel').style.display='none'; frm_pedido.item_descricao0.focus()}">
        <div id="div_lanca_peca_fecha" style="float:right ; align:center ; width:20px ; background-color:#FFFFFF " onclick="document.getElementById('div_importa_excel').style.display='none' ; frm_pedido.item_descricao0.focus()" onmouseover="this.style.cursor='pointer'">
            <center><b>X</b></center>
        </div>

        <br />

        <h6><b><?=traduz('importa.pecas.do.excel', $con)?></b></h6>

        <br />

        <p>
            <?=traduz(array("para.importar.pecas.do.excel.formate.uma.planilha.apenas.com.2.colunas", "(", "codigo", "e", "quantidade", ")", "copie.estas.colunas.no.campo.abaixo"), $con)?>

            <br />

            <textarea id="pecas_excel" rows="10" cols="50"></textarea>
        </p>

        <br />

        <button type='button' onclick="importaExcel();">Importar</button>

        <br /><br />
    </div>

<div id='divAguarde' style='position:absolute; display:none; top:500px; left:350px; background-color: #99CCFF; width: 300px; height:100px;'>
    <center>
        <?=ucfirst(traduz(array('aguarde', 'carregando', 'sep'=>', '), $con))?><br>
        <img src='imagens/ajax-azul.gif'>
    </center>
</div>

<?php

    if($dados_array > 0){
        ?>
        <script type='text/javascript'>
            var retorno_pecas = new Array();
            var contador = 0;
        </script>
        <?
        for( $i = 0; $i < $dados_array ; $i++ ){
            $importa_dados = $pedido_session[$i]['referencia']."|".$pedido_session[$i]['qtd'];//Array();
            ?>
            <script type='text/javascript'>
                retorno_pecas[contador] = '<?php echo $importa_dados;?>';
                contador ++;
            </script>
            <?
        }
        ?>

        <?
        @session_destroy();
    }

    $pedido = $cook_pedido;

    if (strlen ($cook_pedido) > 0 ) {
        $sql = "SELECT a.oid ,
                a.* ,
                referencia,
                descricao,
                tbl_peca.ipi AS ipi_peca,
                round((a.preco+(a.preco*tbl_peca.ipi/100))::numeric,2) as preco_com_ipi
                FROM tbl_peca
                JOIN (
                SELECT  tbl_pedido_item.oid,
                    tbl_pedido_item.pedido_item,
                    tbl_pedido_item.preco,
                    tbl_pedido_item.qtde,
                    tbl_pedido_item.peca
                FROM tbl_pedido_item
                JOIN tbl_pedido USING(pedido)
                WHERE pedido = $pedido
                AND fabrica = $login_fabrica
                )
                a ON tbl_peca.peca = a.peca
                ORDER BY a.pedido_item";

    //  echo nl2br($sql);
        $res = @pg_query ($con,$sql);
        $total = 0;
        if( @pg_num_rows( $res ) > 0 )
        {
?>
</form>
<br />
<table width="700" border="0" cellpadding="5" cellspacing="2" align="center" class='texto_avulso'>
    <tr>
        <td align="center">
            <p><?=traduz('instrucoes.pedido.1', $con)?></p>
            <p><?=traduz('instrucoes.pedido.2', $con)?></p>
        </td>
    </tr>
</table>

<br>
<table width="700" border="0" cellpadding="3" class='tabela' cellspacing="1" align="center" id='resumo_pedido'>
    <thead>
        <tr>
            <th colspan="8" align="center" class='titulo_tabela'><?=traduz('resumo.do.pedido', $con)?></th>
        </tr>
        <tr class='titulo_coluna'>
            <th width="25%" align='center'><?=traduz('referencia', $con)?></th>
            <th width="40%" align='center'><?=traduz('descricao', $con)?></th>
            <th width="15%" align='center'><?=traduz('quantidade', $con)?></th>
            <th width="10%" align='center'><?=traduz(array('preco','sem.ipi'), $con)?></th>
            <th width="10%" align='center'>% IPI</th>
            <th width="10%" align='center'><?=traduz(array('preco','com.ipi'), $con)?></th>
            <th width="10%" align='center'><?=traduz(array('total','item'), $con)?></th>
            <th width="10%" align='center'><?=traduz('acao', $con)?></th>
        </tr>
    </thead>

<?php
//var_dump($sql);
    for ($i = 0 ; $i < @pg_num_rows ($res) ; $i++) {
        $pedido_item = pg_fetch_result ($res,$i,pedido_item);
        $cor = ($i % 2 == 0) ? '#F1F4FA' : "#F7F5F0";

        echo "<tr bgcolor='$cor' id='$pedido_item'>";

        echo "<td width='25%' >";
            echo pg_fetch_result ($res,$i,referencia);
        echo "</td>";

        echo "<td width='50%' align='left'>";
            echo pg_fetch_result ($res,$i,descricao);
        echo "</td>";

        echo "<td width='15%' align='center'>";
            echo $qtde = pg_fetch_result ($res,$i,qtde);
        echo "</td>";

        echo "<td width='10%' align='right'>";
            $preco = number_format (pg_fetch_result ($res,$i,preco),2,",",".");
            //$preco = str_replace('.',',',$preco);
        echo $preco;
        echo "</td>";

        echo "<td width='10%' align='center'>";
            echo pg_fetch_result ($res,$i,ipi_peca);
        echo "</td>";

        echo "<td width='10%' align='right'>";
            $preco_com_ipi = pg_fetch_result ($res,$i,preco_com_ipi);
            echo number_format ($preco_com_ipi,2,",",".");
        echo "</td>";

        echo "<td width='10%' align='right' class='TotalAjax'>";
            $total_item = $preco_com_ipi*$qtde;
            echo number_format ($total_item ,2,",",".");
        echo "</td>";

        echo "<td width='10%' align='center' nowrap>";
            echo "<input type='button' value='" . traduz('excluir', $con) . "' onclick=\"apagaItemPedido($pedido,$pedido_item);\" />";
        echo "</td>";

        echo "</tr>";

        $total = $total + ($preco_com_ipi * pg_fetch_result($res,$i,qtde));
    }
?>
    <tr>
        <td align="center" colspan="6">
            T O T A L
        </td>
        <td align='right' style='text-align:right'>
            <b id='printTotalAjax'>
            <?php
                $total = number_format ($total,2,",",".");
                //$total = str_replace('.',',',$total);
                echo $total;
            ?>
            </b>
        </td>
    </tr>
</table>
<?php
}
?>
<table width="700" border="0" cellpadding="2" cellspacing="2" align="center" class='formulario' id='btn_resumo_pedido'>
    <tr>
        <td align='center'>
            <br><input type="button" value="<?=traduz('finalizar', $con)?>" onclick="window.location.href=window.location.pathname+'?btn_acao=Finalizar'"><br><br>
        </td>
    </tr>
</table>

<?
} //var_dump($cook_pedido);

 include "rodape.php"; ?>
