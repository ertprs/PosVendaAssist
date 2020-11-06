<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

#include 'cabecalho_pop_pecas.php';
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

$os             = trim(strtoupper($_GET['os']));
$linha          = trim(strtoupper($_GET['linha']));
$tipo           = trim(strtoupper($_GET['tipo']));
$peca           = trim(strtoupper($_GET['peca']));
$descricao      = trim(strtoupper($_GET['descricao']));
$produto_serie  = trim(strtoupper($_GET['produto_serie']));
$ref_produto    = trim(strtoupper($_GET['produto']));
$versao_produto = trim(strtoupper($_GET['versao_produto']));

if($tipo=='REFERENCIA') {
	$cond = " AND (z.referencia LIKE '%$peca%'
                OR (z.para LIKE '%$peca%'
                    AND z.data_inicio IS NOT NULL
                    )
                )
	";
}

if($tipo=='DESCRICAO') {
	$cond = " AND (upper(z.descricao) LIKE '%$descricao%'
                OR (upper(tbl_peca.descricao) LIKE '%$descricao%'
                    AND z.data_inicio IS NOT NULL
                    )
                )
	";
}

if ($usa_versao_produto and !empty($versao_produto)) {
	// $joinVer = "JOIN tbl_lista_basica ON tbl_lista_basica.peca = tbl_peca.peca AND tbl_lista_basica.fabrica = {$login_fabrica} ";
	$cond .= "AND (tbl_lbm.type IS NULL OR tbl_lbm.type = '$versao_produto') ";
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title> Pesquisar Peças... </title>
<meta http-equiv='pragma' content='no-cache'>
<style type="text/css">
body {
    margin: 0;
    font-family: Arial, Verdana, Times, Sans;
    background: #fff;
}
</style>
<script type="text/javascript" src="js/jquery-1.8.3.min.js"></script>
<script src="plugins/jquery/jquery.tablesorter.min.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="css/lupas/lupas.css">
<script type='text/javascript'>
//função para fechar a janela caso a tecla ESC seja pressionada!
$(window).keypress(function(e) {
    if(e.keyCode == 27) {
        window.parent.Shadowbox.close();
    }
});

$(function() {
    $("#gridLista").tablesorter();
});
</script>
</head>

<body>
<div class="lp_header">
    <a href='javascript:window.parent.Shadowbox.close();' style='border: 0;'>
        <img src='css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar' />
    </a>
</div>

<?php

    echo "<h4>Pesquisando por <b>série de produção da peça</b>: <i>$produto_serie</i></h4>";
    echo "<p>";

    /**
    * Buscando data de fabricação do produto
    */

    if (!empty($produto_serie)) {
        $sqlFab = " SELECT  tbl_numero_serie.data_fabricacao AS data_fabricacao,
                            tbl_numero_serie.produto
                    FROM    tbl_numero_serie
                    JOIN tbl_produto USING(produto)
                    WHERE   fabrica  = $login_fabrica
                    AND     serie    = '$produto_serie'
                    AND tbl_produto.referencia = '$ref_produto'
        ";
        $resFab = pg_query($con,$sqlFab);
        if(pg_num_rows($resFab) > 0){
            $data_fabricacao = pg_fetch_result($resFab,0,data_fabricacao);
            $produto         = pg_fetch_result($resFab,0,produto);
        }else{
            $sqlFab = " SELECT  produto
                        FROM    tbl_produto
                        WHERE   fabrica_i  = $login_fabrica
                        AND     referencia    = '$ref_produto'
            ";
            $resFab = pg_query($con,$sqlFab);
            if(pg_num_rows($resFab) > 0){

                $produto = pg_fetch_result($resFab,0,produto);
            }
        }

    }else{
        $sqlFab = " SELECT  produto
                        FROM    tbl_produto
                        WHERE   fabrica_i  = $login_fabrica
                        AND     referencia    = '$ref_produto'
            ";

            $resFab = pg_query($con,$sqlFab);
            if(pg_num_rows($resFab) > 0){

                $produto = pg_fetch_result($resFab,0,produto);
            }
    }



    $sql = "SELECT  DISTINCT
                    z.peca,
                    z.referencia       AS peca_referencia,
                    z.descricao        AS peca_descricao,
                    z.bloqueada_garantia,
                    z.type,
                    z.peca_fora_linha,
                    z.promocao_site,
                    z.para,
                    z.peca_para,
                    z.expira            AS data_de,
                    z.data_inicio       AS data_para,
                    z.libera_garantia,
                    tbl_peca.descricao  AS para_descricao,
                    z.peca_critica,
                    z.troca_obrigatoria,
                    z.lista_basica
            FROM    (
                        SELECT  y.peca,
                                y.referencia,
                                y.descricao,
                                y.bloqueada_garantia,
                                y.type,
                                y.peca_fora_linha,
                                y.promocao_site,
                                tbl_depara.para,
                                tbl_depara.peca_para,
                                tbl_depara.expira,
                                tbl_depara.data_inicio,
                                y.libera_garantia,
                                y.peca_critica,
                                y.troca_obrigatoria,
                                y.lista_basica
                        FROM    (
                                    SELECT  x.peca,
                                            x.referencia,
                                            x.descricao,
                                            x.bloqueada_garantia,
                                            x.type,
                                            tbl_peca_fora_linha.peca AS peca_fora_linha,
                                            x.promocao_site,
                                            tbl_peca_fora_linha.libera_garantia,
                                            x.peca_critica,
                                            x.troca_obrigatoria,
                                            x.lista_basica
                                    FROM    (
                                                SELECT  DISTINCT
                                                        tbl_peca.peca,
                                                        tbl_peca.referencia,
                                                        tbl_peca.descricao,
                                                        tbl_peca.bloqueada_garantia,
                                                        tbl_lista_basica.type,
                                                        tbl_peca.promocao_site,
                                                        tbl_peca.peca_critica,
                                                        tbl_peca.troca_obrigatoria,
                                                        tbl_lista_basica.lista_basica
                                                FROM    tbl_peca
                                                JOIN    tbl_lista_basica    ON  tbl_lista_basica.peca       = tbl_peca.peca
                                                                            AND tbl_lista_basica.fabrica    = $login_fabrica
                                           LEFT JOIN    tbl_produto         ON  tbl_produto.produto         = tbl_lista_basica.produto
                                                                            AND tbl_produto.fabrica_i       = $login_fabrica
												WHERE   tbl_peca.fabrica    = $login_fabrica
												and     tbl_peca.ativo
                                                AND     tbl_produto.produto = $produto
                                            ) AS x
                               LEFT JOIN    tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca
                                ) AS y
                   LEFT JOIN    tbl_depara ON tbl_depara.peca_de = y.peca
                    ) AS z
       LEFT JOIN    tbl_peca                    ON  tbl_peca.peca       = z.peca_para
            JOIN    tbl_lista_basica AS tbl_lbm ON  tbl_lbm.peca        = z.peca
                                                AND tbl_lbm.produto     = $produto
            JOIN    tbl_produto                 ON  tbl_produto.produto = tbl_lbm.produto
                                                AND tbl_produto.produto = $produto
                    $cond
    ";

	// echo nl2br($sql);
    $res = @pg_query($con,$sql);

	if (SERVER_ENV === 'DEVELOPMENT' and !is_resource($res))
		die("<pre>$sql</pre>");

    if (!is_resource($res) or pg_num_rows($res) == 0) {
        echo "<h1>Não foi encontrada nenhuma peça para essa pesquisa</h1>";
        exit;
    }

    /**
    * - Faço a captura de todas as peças PARA
    * onde eu faço a eliminação das peças que
    * realizam a substituição de outra, mas aparecem
    * na Query do banco
    */
    $arr = pg_fetch_all_columns($res,7);
    $dtP = pg_fetch_all_columns($res,10);
    $valores = array_combine($arr,$dtP);

//     echo "<pre>";
//     print_r($valores);
//     echo "</pre>";

?>
<table width='100%' border='0' cellspacing='1' cellspading='0' class='lp_tabela' id='gridRelatorio'>
    <thead>
        <tr>
            <th>Referência</th>
            <th>Descrição</th>
        </tr>
    </thead>
    <tbody>
<?php
    for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
        $peca_referencia    = trim(@pg_fetch_result($res, $i, 'peca_referencia'));
        $peca_busca         = trim(@pg_fetch_result($res, $i, 'peca'));
        $peca_descricao     = trim(@pg_fetch_result($res, $i, 'peca_descricao'));
        $peca_descricao_js  = strtr($peca_descricao, array('"'=>'&rdquo;', "'"=>'&rsquo;')); //07/05/2010 MLG - HD 235753
        $type               = trim(@pg_fetch_result($res, $i, 'type'));
        $posicao            = trim(@pg_fetch_result($res, $i, 'posicao'));
        $ordem              = trim(@pg_fetch_result($res, $i, 'ordem'));
        $somente_kit        = trim(@pg_fetch_result($res, $i, 'somente_kit'));//HD 335675
        $peca_fora_linha    = trim(@pg_fetch_result($res, $i, 'peca_fora_linha'));
        $peca_para          = trim(@pg_fetch_result($res, $i, 'peca_para'));
        $para               = trim(@pg_fetch_result($res, $i, 'para'));
        $para_descricao     = trim(@pg_fetch_result($res, $i, 'para_descricao'));
        $data_de            = trim(@pg_fetch_result($res, $i, 'data_de'));
        $data_para          = trim(@pg_fetch_result($res, $i, 'data_para'));
        $bloqueada_garantia = trim(@pg_fetch_result($res, $i, 'bloqueada_garantia'));
        $libera_garantia    = trim(@pg_fetch_result($res, $i, 'libera_garantia'));

        /*HD - 4292944*/
        if ($login_fabrica == 120 or $login_fabrica == 201) {
            $aux_sql = "SELECT produto FROM tbl_os WHERE fabrica = $login_fabrica AND os = $os";
            $aux_res = pg_query($con, $aux_sql);

            if (pg_num_rows($aux_res) > 0) {
                $aux_produto = pg_fetch_result($aux_res, 0, 'produto');

                $aux_sql = "SELECT serie_inicial, serie_final FROM tbl_lista_basica WHERE fabrica = $login_fabrica AND produto = $aux_produto AND peca = $peca_busca";
            } else {
                $lista_basica  = trim(pg_fetch_result($res, $i, 'lista_basica'));

                $aux_sql = "SELECT serie_inicial, serie_final FROM tbl_lista_basica WHERE lista_basica = $lista_basica";
            }

            $serie_produto = (int) trim(strtoupper($_GET['produto_serie']));

            $aux_res = pg_query($con, $aux_sql);

            if (pg_num_rows($aux_res) > 0) {
                $serie_inicial = (int) trim(pg_fetch_result($aux_res, 0, 'serie_inicial'));
                $serie_final   = (int) trim(pg_fetch_result($aux_res, 0, 'serie_final'));

                if ($serie_final > 0) {
                    if (!($serie_produto >= $serie_inicial && $serie_produto <= $serie_final)) {
                        continue;
                    }
                } else if ($serie_inicial > 0 && $serie_final <= 0) {
                    if (!($serie_produto >= $serie_inicial)) {
                        continue;
                    }
                }
            }
        }

        $descricao = str_replace ('"','',$descricao);
        $cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
        if(strlen($peca_para) == 0){
            if(array_key_exists($peca_referencia,$valores)){
                if($valores[$peca_referencia] != ""){
                    continue;
                }
            }
?>
        <tr style='background: <?=$cor?>'>
            <td><?=$peca_referencia?></td>
            <td><a href="javascript:window.parent.retorna_pecas_lbm('<?=$peca_referencia?>','<?=$peca_descricao_js?>','','<?=$linha?>');"><?=$peca_descricao?></a></td>
        </tr>
<?
        }else{
            if(strlen($data_de) == 0 ){
?>
        <tr style='background: <?=$cor?>'>
            <td colspan="2">
                <table width='100%' border='0' cellspacing='1' cellspading='0'>
                    <tr>
                        <th>Peça Antiga</th>
                        <th>Peça Nova</th>
                    </tr>
                    <tr>
                        <td><?=$peca_referencia." - ".$peca_descricao?></td>
                        <td><a href="javascript:window.parent.retorna_pecas_lbm('<?=$para?>','<?=$para_descricao?>','','<?=$linha?>');"><?=$para." - ".$para_descricao?></a></td>
                    </tr>
                </table>
            </td>
        </tr>
<?
            }else{
                if(strtotime($data_fabricacao) <= strtotime($data_de)){

?>
        <tr style='background: <?=$cor?> font-height:bold;'>
            <td><?=$peca_referencia?></td>
            <td><a href="javascript:window.parent.retorna_pecas_lbm('<?=$peca_referencia?>','<?=$peca_descricao?>','1','<?=$linha?>');" ><?=$peca_descricao?></a></td>
        </tr>
<?
                }elseif(strtotime($data_fabricacao) >= strtotime($data_para)){
?>
        <tr style='background: <?=$cor?> font-height:bold;'>
            <td><?=$para?></td>
            <td><a href="javascript:window.parent.retorna_pecas_lbm('<?=$para?>','<?=$para_descricao?>','1','<?=$linha?>');" ><?=$para_descricao?></a></td>
        </tr>
<?
                }
            }
        }
    }
?>
    </tbody>
</table>

</body>
</html>
