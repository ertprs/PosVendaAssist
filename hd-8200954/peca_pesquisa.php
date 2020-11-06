<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

#include 'cabecalho_pop_pecas.php';
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

$headerImg = ($cook_idioma == 'es') ? 'pesquisa_pecas_es.gif' : 'pesquisa_pecas.gif';
$multipeca = $_GET['multipeca'];
if (isset($multipeca) && strlen($multipeca) > 0) {
    $multipeca = true;
    $posicao   = $_GET["posicao"];
} else {
    $multipeca = false;
}

?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title><?=traduz(array('pesquisar', 'pecas'), $con)?>...</title>
<meta name="Author"       content="">
<meta name="Keywords"     content="">
<meta name="Description"  content="">
<meta http-equiv="pragma" content="no-cache">


    <link href="css/css.css" rel="stylesheet" type="text/css" />
<!--    <link href="css/posicionamento.css" rel="stylesheet" type="text/css" /> -->
<script type="text/javascript">
//var descricao = window.opener.descricao_peca;
//var referencia = window.opener.referencia_peca;
//var descricao = window.opener.descricao_pesquisa_peca;
//var referencia = window.opener.referencia_pesquisa_peca;
function Retorna (descricao,referencia){
    <?php if (in_array($login_fabrica, [1,141]) && !empty($multipeca)) {   ?>
        var multipeca = '<?=$multipeca;?>';

        if (multipeca) {
            var posicao   = '<?=$posicao;?>';
            opener.parent.retorna_peca(referencia, descricao, posicao);
        }
        window.close();
        return false;
    <?php } else { ?>
    // <?php
    // if (!isset($_GET["usa_var"])) {
    // ?>
    //     window.opener.parent.document.forms['frm_tabela'].descricao_peca.value = descricao;
    //     window.opener.parent.document.forms['frm_tabela'].referencia_peca.value = referencia;
    // <?php
    // } else {
    // ?>
    //     window.opener.descricao_pesquisa_peca.value = descricao;
    //     window.opener.referencia_pesquisa_peca.value = referencia;
    // <?php
    // }
    // ?>
    //var anterior = window.opener.location.href.replace(/.*\/(\w+\.php)$/,'$1');

    switch(window.opener.location.href.replace(/.*\/(\w+\.php)$/,'$1')) {
    case 'consulta_pecas_pedido_pendente.php':
        opener.document.forms[0].peca.value = referencia;
        opener.document.forms[0].descricao.value  = descricao;
        opener.document.forms[0].descricao.focus()
        break;
    case 'estoque_posto_movimento.php':
        opener.document.forms[0].referencia_peca.value = referencia;
        opener.document.forms[0].descricao_peca.value  = descricao;
        opener.document.forms[0].descricao_peca.focus()
        break;
    case 'helpdesk_cadastrar.php':
       <?php 
       if(in_array($login_fabrica, [1,42])) { ?>
            opener.document.forms[0].peca_referencia_multi2.value = referencia;
            opener.document.forms[0].peca_descricao_multi2.value  = descricao;
      <?php
      }else{
      ?>
            opener.document.forms[0].peca_referencia_multi.value = referencia;
            opener.document.forms[0].peca_descricao_multi.value  = descricao;
            opener.document.forms[0].peca_descricao_multi.focus();
      <?php
      }
      ?>
        break;
    case 'os_troca_black_aviso.php':
        opener.document.forms[0].referencia.value = referencia;
        opener.document.forms[0].descricao.value  = descricao;
        opener.document.forms[0].descricao.focus()
        break;
    case 'peca_cadastro_test.php':
        opener.document.forms[0].referencia.value = referencia;
        opener.document.forms[0].descricao.value  = descricao;
        opener.document.forms[0].descricao.focus()
        break;
    case 'peca_consulta_dados.php':    
        opener.document.forms[0].referencia.value = referencia;
        opener.document.forms[0].descricao.value  = descricao;
        opener.document.forms[0].descricao.focus()
        break;
    case 'pedido_blackedecker_cadastro_test.php':
        opener.document.forms[0].referencia.value = referencia;
        opener.document.forms[0].descricao.value  = descricao;
        opener.document.forms[0].descricao.focus()
        break;
    case 'pedido_relacao_test.php':
        opener.document.forms[0].posto_codigo.value = referencia;
        opener.document.forms[0].posto_descricao.value  = descricao;
        opener.document.forms[0].posto_descricao.focus()
        break;
    // case 'tabela_precos_blackedecker_consulta_test.php':
    //     opener.document.forms[0].referencia_peca.value = referencia;
    //     opener.document.forms[0].descricao_peca.value  = descricao;
    //     opener.document.forms[0].descricao_peca.focus()
    //     break;
    case 'tabela_precos_intelbras.php':
        opener.document.forms[0].referencia.value = referencia;
        opener.document.forms[0].descricao.value  = descricao;
        opener.document.forms[0].descricao.focus()
        break;
    default:
    console.log(opener.document.forms[0].referencia_peca);
        opener.document.forms[0].referencia_peca.value = referencia;
        opener.document.forms[0].descricao_peca.value  = descricao;
        opener.document.forms[0].descricao_peca.focus()
    }
      
    
    window.close();

    //window.close();
  <?php } ?>
}
</script>
</head>

<!--<body onblur="setTimeout('window.close()',2500);">-->
<body >


<br>

<img src="imagens/<?=$headerImg?>">

<?
$tipo = trim (strtolower ($_GET['tipo']));
if ($tipo == "descricao") {
    $descricao = trim (strtoupper($_GET["campo"]));

    $caption = traduz('pesquisando.pela.descricao', $con, $cook_idioma, $descricao);
    echo "<h4>$caption</h4>";
    echo "<p>";

$sql =  "SELECT z.peca                                ,
                z.referencia       AS peca_referencia ,
                z.descricao        AS peca_descricao  ,
                z.bloqueada_garantia                  ,
                z.peca_fora_linha                     ,
                z.de                                  ,
                z.para                                ,
                z.peca_para                           ,
                tbl_peca.descricao AS para_descricao ,
                z.libera_garantia
         FROM   (
                    SELECT  y.peca               ,
                            y.referencia         ,
                            y.descricao          ,
                            y.bloqueada_garantia ,
                            y.peca_fora_linha    ,
                            tbl_depara.de        ,
                            tbl_depara.para      ,
                            tbl_depara.peca_para ,
                            y.libera_garantia
                    FROM    (
                                SELECT  x.peca                                      ,
                                        x.referencia                                ,
                                        x.descricao                                 ,
                                        x.bloqueada_garantia                        ,
                                        tbl_peca_fora_linha.peca AS peca_fora_linha,
                                        tbl_peca_fora_linha.libera_garantia
                                FROM    (
                                            SELECT  tbl_peca.peca       ,
                                                    tbl_peca.referencia ,
                                                    tbl_peca.descricao  ,
                                                    tbl_peca.bloqueada_garantia
                                            FROM    tbl_peca
                                            WHERE   fabrica = $login_fabrica
                                            AND     ativo IS TRUE
                                            AND     tbl_peca.produto_acabado IS NOT TRUE
                                            AND     descricao ~* '$descricao'
                                        ) AS x
                           LEFT JOIN    tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca
                            ) AS y
               LEFT JOIN    tbl_depara ON tbl_depara.peca_de = y.peca
                ) AS z
    LEFT JOIN   tbl_peca ON tbl_peca.peca = z.peca_para
   ORDER BY     z.descricao";
    $res = pg_query($con,$sql);

    if (@pg_num_rows($res) == 0) {
        echo "<h1>" . traduz('peca.%.nao.encontrada', $con, $cook_idioma, array($descricao)) . "</h1>";
        echo "<script language='javascript'>";
        echo "setTimeout('window.close()',2500);";
        echo "</script>";
        exit;
    }
}

if ($tipo == "referencia") {
    $referencia = trim (strtoupper($_GET["campo"]));
    $referencia = str_replace (".","",$referencia);
    $referencia = str_replace (",","",$referencia);
    $referencia = str_replace ("-","",$referencia);
    $referencia = str_replace ("/","",$referencia);
    $referencia = str_replace (" ","",$referencia);

    $caption = traduz('pesquisando.pela.referencia', $con, $cook_idioma, $referencia);
    echo "<font face='Arial, Verdana, Times, Sans' size='2'>$caption</font>";
    echo "<p>";
//FOI ADICIONADO            AND      tlb_peca.ativo IS TRUE POIS SÓ PODE EXIBIR PEÇAS ATIVAS
        $sql =  "SELECT z.peca                                ,
                        z.referencia       AS peca_referencia ,
                        z.descricao        AS peca_descricao  ,
                        z.bloqueada_garantia                  ,
                        z.peca_fora_linha                     ,
                        z.de                                  ,
                        z.para                                ,
                        z.peca_para                           ,
                        tbl_peca.descricao AS para_descricao  ,
                        z.libera_garantia
                FROM (
                        SELECT  y.peca               ,
                                y.referencia         ,
                                y.descricao          ,
                                y.bloqueada_garantia ,
                                y.peca_fora_linha    ,
                                tbl_depara.de        ,
                                tbl_depara.para      ,
                                tbl_depara.peca_para ,
                                y.libera_garantia
                        FROM (
                                SELECT  x.peca                                      ,
                                        x.referencia                                ,
                                        x.descricao                                 ,
                                        x.bloqueada_garantia                        ,
                                        tbl_peca_fora_linha.peca AS peca_fora_linha,
                                        tbl_peca_fora_linha.libera_garantia
                                FROM (
                                        SELECT  tbl_peca.peca              ,
                                                tbl_peca.referencia        ,
                                                tbl_peca.descricao         ,
                                                tbl_peca.bloqueada_garantia
                                        FROM tbl_peca
                                        WHERE fabrica = $login_fabrica
                                        AND ativo IS TRUE
                                        AND   tbl_peca.produto_acabado IS NOT TRUE
                                        AND   referencia_pesquisa ~* '$referencia'
                                ) AS x
                                LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca
                            ) AS y
                        LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca
                    ) AS z
                LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para
                ORDER BY z.descricao";
    $res = pg_exec ($con,$sql);

    if (@pg_numrows ($res) == 0) {
        echo "<h1>" . traduz('peca.%.nao.encontrada', $con, $cook_idioma, array($referencia)) . "</h1>";
        echo "<script language='javascript'>";
        echo "setTimeout('window.close()',2500);";
        echo "</script>";

        exit;
    }
}


    echo "<script language='JavaScript'>\n";
    echo "<!--\n";
    echo "this.focus();\n";
    echo "// -->\n";
    echo "</script>\n";

    echo "<table width='100%' border='0'>\n";

    for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
        $peca       = trim(pg_result($res,$i,peca));
        $descricao  = trim(pg_result($res,$i,peca_descricao));
        $referencia = trim(pg_result($res,$i,peca_referencia));
        $descricao = str_replace ('"','',$descricao);
        $peca_para       = trim(@pg_result($res,$i,peca_para));
        $para            = trim(@pg_result($res,$i,para));
        $para_descricao  = trim(@pg_result($res,$i,para_descricao));
		$para_descricao  = str_replace("'","", $para_descricao);

        $contax=1;
        // HD 52781
        if(strlen($peca_para) > 0) {
            for($xx=0;$xx<$contax;$xx++){
		   $peca_parax= $peca_para;
		if(!empty($peca_parax)) {
			$sql_para=" SELECT  peca_para   ,
					    para        ,
					    (
						SELECT  descricao
						FROM    tbl_peca
						WHERE   tbl_peca.peca = tbl_depara.peca_para
					    ) AS descricao
				    FROM    tbl_depara
				    JOIN    tbl_peca ON tbl_peca.peca = tbl_depara.peca_de
			       LEFT JOIN    tbl_peca_fora_linha USING(peca)
				    WHERE   tbl_depara.fabrica  = $login_fabrica
				    AND     peca_de             = $peca_parax
				    AND     peca_fora_linha     IS NULL";
			$res_para=pg_exec($con,$sql_para);
			if(pg_numrows($res_para) >0){
			    $peca_para       = trim(@pg_result($res_para,0,peca_para));
			    $para            = trim(@pg_result($res_para,0,para));
			    $para_descricao  = trim(@pg_result($res_para,0,descricao));
			    $para_descricao  = str_replace("'","", $para_descricao);
			    $contax++;
			}
		}
            }
        }
        echo "<tr>\n";
        echo "<td>\n";
        echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$referencia</font>\n";
        echo "</td>\n";

        if(strlen($para) == 0) {
            echo "<td>\n";
            echo "<a href=\"javascript: Retorna('$descricao','$referencia'); \" >";
            echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$descricao</font>\n";
            echo "</a>\n";
            echo "</td>\n";
        }else{
            echo "<td>\n";
            echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'>$descricao</font>\n";
            echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'><b>Mudou Para:</b></font>";
            //echo " <a href=\"javascript: referencia.value='$para'; descricao.value='$para_descricao'; this.close();\"><font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$para_descricao</font></a>";
             echo "<a href=\"javascript: Retorna('$para_descricao','$para'); this.close();\" ><font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$para_descricao</font></a>";
            echo "</td>\n";
        }
        echo "</tr>\n";
    }
    echo "</table>\n";
?>
</body>
</html>

<?php
// Fechando explicitamente a conexão com o BD
if (is_resource($con)) {
    pg_close($con);
}
?>
