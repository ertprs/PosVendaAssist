<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
include_once "../class/tdocs.class.php";
$tDocs = new TDocs($con, $login_fabrica);


$url_path = $_SERVER['SERVER_NAME'] . substr($_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'], 'admin'));

if ($S3_sdk_OK) {
	include_once S3CLASS;
	$s3ve = new anexaS3('ve', (int) $login_fabrica);
	$S3_online = is_object($s3ve);
}

$peca_abs_thumb = $peca_rel_thumb = "../imagens_pecas/$login_fabrica/pequena/";

if ($S3_online)
	$s3ve->temAnexos($img);

$imagem = ($S3_online and $s3ve->temAnexo) ? $s3ve->url : busca_arquivo($destino, $img);

function busca_arquivo($dir, $nome) {

    if ($handle = opendir($dir)) {
        while (false !== ($file = readdir($handle))) {
            if ($file != "." && $file != "..") {
                if (substr($file,0,-4) == trim($nome)) {
                    return $file;
                }
            }
        }
        closedir($handle);
    }

    return false;

}

?>
<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
    <title>Impressão Lista Básica</title>
    <meta http-equiv=pragma content=no-cache>
    <style>
        body {
            font-family: segoe ui,arial,helvetica,verdana,sans-serif;
            font-size: 12px;
            margin:0px;
        }
        table {
            font-size: 12px;
        }
        a {
            text-decoration: none;
            color: #000000;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
    <script src="js/jquery-latest.pack.js" type="text/javascript"></script>
</head>

<body><?php

$produto = $_GET['produto'];

$referencia_produto_campo = '';
$join_produto = '';

if ($login_fabrica == 175){
    $ordem_producao = $_GET["ordem_producao"];

    if (strlen($ordem_producao) > 0){
        $cond_ordem = "AND tbl_lista_basica.ordem_producao = '$ordem_producao' ";
    }
}

if ($login_fabrica == 156) {
    $referencia_produto_campo = ', tbl_produto.referencia AS referencia_produto';
    $join_produto = 'JOIN tbl_produto ON tbl_lista_basica.produto = tbl_produto.produto';
}

$sql = "SELECT tbl_lista_basica.ordem         ,
               tbl_lista_basica.posicao       ,
               tbl_lista_basica.qtde          ,
               tbl_peca.referencia            ,
               tbl_peca.referencia_fabrica    ,
               tbl_peca.descricao             ,
               tbl_peca.peca
               $referencia_produto_campo
          FROM tbl_lista_basica
          JOIN tbl_peca USING (peca)
          $join_produto
          $join_preco
         WHERE tbl_lista_basica.fabrica = $login_fabrica
           AND tbl_lista_basica.produto = $produto
           {$cond_ordem}
         ORDER BY tbl_lista_basica.ordem";
$res = @pg_query($con,$sql);

if($login_fabrica == 45){
    //PEGA IMAGEM PRODUTO
    $sql_comu = "SELECT DISTINCT(comunicado)
                FROM tbl_comunicado
                LEFT JOIN tbl_comunicado_produto USING(comunicado)
                WHERE fabrica = $login_fabrica
                    AND (tbl_comunicado.produto = $produto  OR tbl_comunicado_produto.produto = $produto)
                    AND tipo = 'Foto'";

    $res_comu = @pg_query($con,$sql_comu);

    if (@pg_num_rows($res_comu) > 0) {

        $img  = pg_fetch_result($res_comu,0,'comunicado');
        $tipo = 1;

    } else {

        $sql_comu = "SELECT DISTINCT(comunicado)
                    FROM tbl_comunicado
                    LEFT JOIN tbl_comunicado_produto USING(comunicado)
                    WHERE fabrica = $login_fabrica
                        AND (tbl_comunicado.produto = $produto OR tbl_comunicado_produto.produto = $produto)
                        AND tipo = 'Vista Explodida'";

        $res_comu = @pg_query($con,$sql_comu);

        if (@pg_num_rows($res_comu) > 0) {
            $img  = pg_fetch_result($res_comu,0,'comunicado');
            $tipo = 2;
        }

    }

    $destino = "/www/assist/www/comunicados/";
    $caminho = "../comunicados/";


    //MOSTRA TITULO DO PRODUTO
    $sql_prod = "SELECT *
                FROM tbl_produto
                WHERE produto = $produto";

    $res_prod = @pg_query($con,$sql_prod);

    if (@pg_num_rows($res_prod) > 0) {

        //echo "<center><img src='/assist/logos/$login_fabrica_logo' alt='$login_fabrica_site' border='0' height='40' /></center>";
        echo '<h2 align="center">'.pg_fetch_result($res_prod,0,'referencia').' - '.pg_fetch_result($res_prod,0,'descricao').'</h2>';

    }

    if ($imagem) {
        if ($S3_online and $s3ve->temAnexos($img)) {
            $src = $s3ve->url;
        } else {
            $src = $url_path . "comunicados/$imagem";
        }

        if ($tipo == 1) {
            echo '<center><img src="'.$src.'" border="0" /></center>';
        } else if ($tipo == 2) {
            echo '<center><iframe id="pdf" name="pdf" src="https://docs.google.com/viewer?url='.$src.'&embedded=true" width="900px" height="3600" scrolling="no" frameborder="0"></iframe></center>';
        }
    }
}

if (@pg_num_rows($res) > 0) {?>

    <table cellpadding="5" cellspacing="0" width="900px" border="1" align="center">
        <tr bgcolor="#CCCCCC">
            <?php
            if ($login_fabrica == 156) {
                echo '<th>Referência Produto</th>';
            }
            ?>
            <th>Ordem</th>
            <?php
            if ($login_fabrica != 138) {
            ?>
            <th>Posição</th>
            <?php
            }
            ?>

            <?php if ($login_fabrica == 171) {?>
            <th>Referência Fábrica</th>
            <?php }?>

            <th>Peça</th>
            <th>Descrição</th>
            <th>Qtde</th>
<?
if($login_fabrica != 129){
?>
            <th>Imagem</th>
<?
}
?>
        </tr><?php
        for ($i = 0; $i < pg_num_rows($res); $i++) {
            $cor = ($i % 2 == 0) ? '#FFFFFF' : '#EEEEEE';
            echo '<tr bgcolor="'.$cor.'">';
                if ($login_fabrica == 156) {
                    echo '<td align="center">' . pg_fetch_result($res, $i, 'referencia_produto') . '</td>';
                }

                echo '<td align="center">&nbsp;'.pg_fetch_result($res,$i,'ordem').'</td>';
                if ($login_fabrica != 138) {
                echo '<td align="center">&nbsp;'.pg_fetch_result($res,$i,'posicao').'</td>';
                }

                if ($login_fabrica == 171) {
                    echo '<td align="center">&nbsp;'.pg_fetch_result($res,$i,'referencia_fabrica').'</td>';
                }

                echo '<td align="center">&nbsp;'.pg_fetch_result($res,$i,'referencia').'</td>';
                echo '<td>&nbsp;'.pg_fetch_result($res,$i,'descricao').'</td>';
                echo '<td align="center">&nbsp;'.pg_fetch_result($res,$i,'qtde').'</td>';


                $img_peca = busca_arquivo($peca_abs_thumb, pg_fetch_result($res,$i,'peca'));

                if($login_fabrica != 129){

                $xpecas  = $tDocs->getDocumentsByRef(pg_fetch_result($res,$i,'peca'), "peca");
                if (!empty($xpecas->attachListInfo)) {

                    $a = 1;
                    foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
                        $fotoPeca = $vFoto["link"];
                        if ($a == 1){break;}
                    }
                    echo "<td><img src='$fotoPeca' width='50' border='0'></td>";

                } else {
                    if ($img_peca !== false) {
                        echo '<td><img src='.($peca_rel_thumb.$img_peca).' border="0" /></td>';
                    } else {
                        echo '<td>Sem Imagem</td>';
                    }
                }




                }
            echo '</tr>';
        }?>
    </table>
    <center>
        <br />
        <a href="javascript:window.print();">Clique aqui para imprimir</a>
        <br />
        <br />
    </center><?php

} else {

    echo '<h2 align="center">Nenhum registro encontrado!</h2>';

}?>

</body>
</html>
