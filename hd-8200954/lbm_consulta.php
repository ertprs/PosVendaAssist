<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include_once "class/tdocs.class.php";
$tDocs = new TDocs($con, $login_fabrica);

if ($login_fabrica == 6) {
    include "lista_basica_consulta.php";
    exit;
}

include 'funcoes.php';

function busca_arquivo($dir, $nome) {
    if ($dirlist = glob($dir . "$nome.*")) {
        return basename($dirlist[0]);
    }
    return false;
}

if ($S3_sdk_OK) {
	include_once S3CLASS;

	$s3ve  = new anexaS3('ve', (int) $login_fabrica);
	//$s3img = new anexaS3('pc', (int) $login_fabrica);
	$S3_online = is_object($s3ve);
}

$qtde_linhas = 450 ;
$msg_erro = "";

$btn_acao = trim(strtolower($_POST['btn_acao']));
$lbm      = trim(strtolower($_POST['lbm']));

if (strlen($_POST['btn_lista']) > 0) {//se o botão foi clicado
    $referencia = $_POST['referencia'];

    if (strlen($referencia) == 0) {
        $msg_erro = "Preencha a referência do produto";
    }
}

$layout_menu = "callcenter";
if ($login_fabrica == 1) {
    $title = "Consulta de Vista Explodida";
}else{
    $title = "Consulta de Lista Básica";
}

include 'cabecalho.php';

?>

<script type="text/javascript" src="js/jquery-latest.js"></script>
<script type="text/javascript" src="js/jquery.tablesorter.js"></script>
<script>

$(document).ready(function(){
    $("#relatorio").tablesorter();
});

function fnc_pesquisa_produto (campo, campo2, tipo) {
    if (tipo == "referencia" ) {
        var xcampo = campo;
    }

    if (tipo == "descricao" ) {
        var xcampo = campo2;
    }

    if (xcampo.value != "") {
        var url = "";
        url = "produto_pesquisa.php?campo=" + xcampo.value + "&tipo=" + tipo ;
        janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
        janela.referencia = campo;
        janela.descricao  = campo2;
        janela.focus();
    }
}

function fnc_pesquisa_peca (campo, campo2, tipo) {
    if (tipo == "referencia" ) {
        var xcampo = campo;
    }

    if (tipo == "descricao" ) {
        var xcampo = campo2;
    }

    if (xcampo.value != "") {
        var url = "";
        url = "peca_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
        janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
        janela.referencia    = campo;
        janela.descricao    = campo2;
        janela.focus();
    }
}

</script>

<body>

<form name="frm_lbm" method="post" action="<?=$PHP_SELF ?>">

<div class='esconder_com_familia'>
<?php
if (strlen($msg_erro) > 0) {
    echo $msg_erro;
}?>
</div>

<p>
<div class='esconder_com_familia'>
<font face='arial' size='-1' color='#6699FF'><b>Para pesquisar um produto, informe parte da referência ou descrição do produto.</b></font>
</div>
<?php


$referencia = $_POST['referencia'];

if (strlen($referencia) > 0) {
    $sql = "SELECT produto, descricao FROM tbl_produto JOIN tbl_linha USING(linha) WHERE tbl_linha.fabrica=$login_fabrica AND referencia = '$referencia'";
    $res = pg_exec($con,$sql);
    if (pg_numrows($res) == 0) {
        $msg_erro  = "Produto $referencia não cadastrado";
        $descricao = "";
        $produto   = "";
    } else {
        $descricao = pg_result($res,0,descricao);
        $produto   = pg_result($res,0,produto);
    }
}?>

<table width='550' align='center' border='0'>
    <tr class='esconder_com_familia'>
        <td align='left'>
            <b>Referência</b>
        </td>
        <td align='left'>
            <b>Descrição</b>
        </td>
    </tr>
    <tr class='esconder_com_familia'>
        <td align='left'>
            <input type="text" name="referencia" id="referencia" value="<? echo $referencia ?>" size="15" maxlength="20">&nbsp;<img src='imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_lbm.referencia,document.frm_lbm.descricao,'referencia')">
        </td>
        <td align='left'>
            <input type="text" name="descricao" id="descricao" value="<? echo $descricao ?>" size="50" maxlength="50">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' style="cursor:pointer" align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_lbm.referencia,document.frm_lbm.descricao,'descricao')">
        </td>
    </tr>
    <style>
        .grupo_familia {
            display: none;
        }
    </style>
    <script>
        $(document).ready(function(){
            $("#btn_familia").click(function() {
                var value = $("#aux_familia").val();
                if (value == "0") {
                    $(".grupo_familia").css("display", "block");
                    $(".esconder_com_familia").css("display", "none");
                    $("#aux_familia").val("1");
                } else {
                    $(".esconder_com_familia").css("display", "block");
                    $(".grupo_familia").css("display", "none");
                    $("#aux_familia").val("0");
                }
            });
        });

    </script>
    <? if ($login_fabrica == 1) { ?>
    <input type="hidden" id="aux_familia" name="aux_familia" value="0">
    <tr class='grupo_familia'>
        <td align='left' colspan="2">
            <b>Família</b>
        </td>
    </tr>
    <tr class='grupo_familia'>
        <td align='left' colspan="2">
            <select id="familia" name="familia" class="frm">
                <option value="" >Selecione</option>
                <?php
                $sql_f = "SELECT familia, descricao,codigo_familia FROM tbl_familia WHERE fabrica = {$login_fabrica} AND ativo = 't' ORDER BY descricao;";
                $res_f = pg_query($con,$sql_f);
                //echo nl2br($sql_f);
                if (pg_num_rows($res_f) > 0) {
                    while ($result = pg_fetch_object($res_f)) {
                        $selected  = (trim($result->familia) == trim($_POST["familia"])) ? "SELECTED" : "";

                        echo "<option value='{$result->familia}' {$selected} >{$result->descricao} </option>";
                    }
                }
                ?>
            </select>            
        </td>
    </tr>
    <tr class='grupo_familia'>
        <td colspan="2"">
            <br>
            <center>
                <button onclick='document.frm_lbm.btn_lista.value="listar"; document.frm_lbm.submit();'>Pesquisar</button>
            </center>
        </td>
    </tr>
    <? } ?>
</table>
<?php if ($login_fabrica == 1) { /*HD-4074490*/ ?>
    <a id="btn_familia"><font face='arial' size='-1' color='#FF0000'><b><p>Para fazer Download das vistas explodidas por Família, clique aqui.</p></b></font></a>
    <br> <br>
<?php } else {?>
    <font face='arial' size='-1' color='#FF0000'><b><p>Para fazer Download apenas por Família, selecionar somente o campo Família.</p></b></font>
<?php } ?>

<input type='hidden' name='btn_lista' value='' />
<? if ($login_fabrica == 1) { ?>
    <p class='esconder_com_familia' align='center'><img src='imagens/btn_vistaexplodidademateriais.gif' onclick='document.frm_lbm.btn_lista.value="listar"; document.frm_lbm.submit();' style="cursor:pointer;">    
<? } else { ?>
    <p align='center'><img src='imagens/btn_listabasicademateriais.gif' onclick='document.frm_lbm.btn_lista.value="listar"; document.frm_lbm.submit();' style="cursor:pointer;">
<? } ?>

</form>

<br />

<center>
<? if (strlen($produto) > 0 AND strlen($familia) == 0) {

    if($login_fabrica == 1){
        $sqlVerifica = "SELECT inibir_lista_basica FROM tbl_produto WHERE produto = $produto;";
        $resVerifica = pg_query($con,$sqlVerifica);
        $inibir_lista_basica = pg_fetch_result($resVerifica,0,inibir_lista_basica);
    }

    if($inibir_lista_basica == 't'){
        echo "Posto não pode visualizar lista básica e vista explodida deste produto!";
    }else{
        $sql = "SELECT DISTINCT comunicado,extensao
                FROM tbl_comunicado
                LEFT JOIN tbl_comunicado_produto USING(comunicado)
                WHERE fabrica = $login_fabrica
                AND (tbl_comunicado.produto = $produto  OR tbl_comunicado_produto.produto = $produto)
                AND tipo = 'Vista Explodida'";
        $res = pg_query($con,$sql);

        if (pg_num_rows($res) > 0) {
            $vista_explodida = pg_fetch_result($res,0,comunicado);
            $ext             = pg_fetch_result($res,0,extensao);
            $arq_vista_explodida = $s3ve->temAnexos($vista_explodida) ? $s3ve->url : 'comunicados/'.$vista_explodida.'.'.$ext;
            if (!$S3_online or !$s3ve->temAnexo) {
                $arq_vista_explodida = file_exists($arq_vista_explodida) ? $arq_vista_explodida : false;
            }
        }

        if (strlen($vista_explodida) > 0) {

            if ($arq_vista_explodida) {
                if($login_fabrica == 1){?>                
                    <table align="center" width="700" class="formulario">
                        <tr>
                            <td align="center">                            
                                <input type="button" onclick="window.open('lbm_impressao.php?produto=<?=urlencode($produto)?>')" value="Download Vista Explodida">
                            </td>
                        </tr>
                    </table>
                    <br />
                <? } else { ?>
                    <table align="center" width="700" class="formulario">
                        <tr>
                            <td align="center">
                                <input type="button" onclick="window.open('<?=$arq_vista_explodida?>')" value="Ver Vista Explodida">
                            </td>
                        </tr>
                    </table>
                    <table align="center" width="700" class="formulario">
                        <tr>
                            <td align="center">                            
                                <input type="button" onclick="window.open('comunicado_download.php?arquivo=<?=urlencode($arq_vista_explodida)?>')" value="Download Vista Explodida">
                            </td>
                        </tr>
                    </table>
                <? }
            }
        } else {
            echo "Produto sem vista explodida";
        }
        if ($login_fabrica != 1) {
            echo '<br />';
            echo '<br />';
            echo '<a href="lbm_impressao.php?produto='.$produto.'" target="_blank">Versão para impressão</a>';
            echo '<br />';
            echo '<br />';
        }
    }
}?>
</center>
<?
$btn_lista = $_POST['btn_lista'];

if (strlen($_POST['btn_lista']) > 0 && $inibir_lista_basica != 't') {

    $referencia = $_POST['referencia'];
    $familia = $_POST['familia'];

    if (strlen($produto) > 0 AND strlen($familia) == 0) {

        $and_tipo = ($login_fabrica == 1) ? "AND tipo IN ('Foto','Esquema Elétrico','Informativo','Informativo tecnico','Manual','Manual Técnico','Vista Explodida','Alterações Técnicas') AND ativo IS TRUE" : "AND tipo = 'Foto'";
        $_tipo = ($login_fabrica == 1) ? ", tipo" : "";

        /*
        * Vista Explodida
        */
        //PEGA IMAGEM PRODUTO
        $sql_comu = "SELECT DISTINCT(comunicado) $_tipo
                       FROM tbl_comunicado
                       LEFT JOIN tbl_comunicado_produto USING(comunicado)
                      WHERE fabrica = $login_fabrica
                        AND (tbl_comunicado.produto = $produto  OR tbl_comunicado_produto.produto = $produto)
                        $and_tipo";

        $res_comu = @pg_query($con,$sql_comu);

        if (@pg_num_rows($res_comu) > 0) {
            if ($login_fabrica == 1) {
                $img = [];
                for ($i = 0; $i < pg_num_rows($res_comu); $i++) {
                  $img[$i]['comunicado'] = pg_fetch_result($res_comu,$i,'comunicado');
                  $img[$i]['tipo'] = pg_fetch_result($res_comu,$i,'tipo');
                }
                $tipo = 1;
            } else {
                $img  = pg_fetch_result($res_comu,0,'comunicado');
                $tipo = 1;
            }

        } else {

            $sql_comu = "SELECT DISTINCT(comunicado)
                           FROM tbl_comunicado
                           LEFT JOIN tbl_comunicado_produto USING(comunicado)
                          WHERE fabrica = $login_fabrica
                            AND (tbl_comunicado.produto = $produto  OR tbl_comunicado_produto.produto = $produto)
                            AND tipo = 'Vista Explodida'";

            $res_comu = @pg_query($con,$sql_comu);

            if (@pg_num_rows($res_comu) > 0) {
                $img  = pg_fetch_result($res_comu,0,'comunicado');
                $tipo = 2;
            }

        }

        $destino = __DIR__ . "/comunicados/";
        $caminho = "comunicados/";

        $peca_abs_thumb = __DIR__ . "/imagens_pecas/$login_fabrica/pequena/";
        $peca_rel_thumb = "imagens_pecas/$login_fabrica/pequena/";

        if ($login_fabrica == 1 && $tipo == 1) {
            foreach ($img as $key => $value) {
              $imagem = "";
              $img_style = "";

              $imagem = ($S3_online and $s3ve->temAnexos($value['comunicado'])) ? $s3ve->url : $caminho . busca_arquivo($destino, $value['comunicado']);
  
              if ($imagem == $caminho) {
                continue;
              }
              
              $img_style = 'style="width: 900px; height: 600px;"';
              
            echo "<br />"; 
            echo "<h4><b>".$value['tipo']."</b></h4>"; 
            echo "<br />"; 
            echo '<center><iframe ' . $img_style . ' src="' . $imagem . '" border="0"></iframe></center>';
            }
            echo "<br />"; 
        } else {

            $imagem = ($S3_online and $s3ve->temAnexos($img)) ? $s3ve->url : $caminho . busca_arquivo($destino, $img);
            $img_style = '';

            // Se não existe o arquivo no S3, tentar no diretório 'local'
            if ($S3_online and $imagem == false)
                $imagem = $caminho . busca_arquivo($destino, $img);

            // Se não existe arquivo no S3 nem no diretório local, $imagem = false
            if ($imagem == $caminho)
                $imagem = false;

            if ($imagem !== false) {
                $imgExpl = explode('?', $imagem);

                $s3Path = pathinfo($imgExpl[0]);

                $imagens_ext = array(
                    'jpg', 'jpeg', 'gif', 'png', 'bmp'
                );

                if (in_array($s3Path["extension"], $imagens_ext)) {
                    $tipo = 1;

                    $img_style = 'style="max-width: 900px"';
                }
            }
        }


        if ($imagem !== false && $tipo == 1 && $login_fabrica != 1) {

            echo '<center><img ' . $img_style . ' src="' . $imagem . '" border="0" /></center>';

        } else if ($imagem !== false && $tipo == 2) {

            echo '<center><iframe id="pdf" name="pdf"  src="https://docs.google.com/viewer?url='.urlencode($imagem).'&embedded=true" width="900px" height="1200px" scrolling="no" frameborder="0"></iframe></center>';

        }
        /*
        * Fim Vista Explodida
        */

        $sql = "SELECT produto FROM tbl_produto JOIN tbl_linha USING(linha) WHERE tbl_linha.fabrica = $login_fabrica AND referencia = '$referencia'";
        $res = pg_exec($con,$sql);
        if (pg_numrows($res) > 0) {
            $produto = pg_result($res,0,0);
        } else {
            $produto = 0;
        }
        if ($login_fabrica == 1) {
            $indspl = "AND (upper(informacoes) != 'INDISPL' or informacoes is null)";
        }

        $sql = "SELECT  tbl_lista_basica.lista_basica                        ,
                        tbl_lista_basica.posicao                             ,
                        tbl_lista_basica.ordem                               ,
                        tbl_lista_basica.qtde                                ,
                        tbl_peca.referencia                                  ,
                        tbl_peca.peca                                        ,
                        tbl_peca.descricao                                   ,
                        tbl_peca.peca                                        ,
                        tbl_peca.garantia_diferenciada   AS desgaste         ,
                        tbl_lista_basica.serie_inicial                       ,
                        tbl_lista_basica.serie_final                         ,
                        tbl_lista_basica.type                                ,
                        tbl_lista_basica.garantia_peca                       ,
                        tbl_produto.garantia                                 ,
                        tbl_peca.garantia_diferenciada
                FROM    tbl_lista_basica
                JOIN    tbl_peca USING (peca)
                JOIN    tbl_produto ON tbl_produto.produto = tbl_lista_basica.produto
                WHERE   tbl_lista_basica.fabrica = $login_fabrica
                $indspl
                AND     tbl_lista_basica.produto = $produto ";
        if ($login_fabrica == 45) {
            $sql .= " ORDER BY tbl_lista_basica.posicao";
        } elseif ($login_fabrica == 1) {
            $sql .= " ORDER BY tbl_lista_basica.ordem";
        } else {
            $sql .= " ORDER BY tbl_peca.referencia, tbl_peca.descricao";
        }

        $res = pg_exec($con,$sql);
        if ($login_fabrica != 1) {
            echo "<table border='0' width='300' align='center'>";
                echo "<tr>";
                    echo "<td bgcolor='#91C8FF' nowrap>&nbsp;&nbsp;&nbsp;</td><td nowrap> Alternativa</td>";
                    echo "<td width='100%' nowrap>&nbsp;&nbsp;&nbsp;</td>";
                    echo "<td bgcolor='#FF0099' nowrap>&nbsp;&nbsp;&nbsp;</td><td nowrap> De-Para</td>";
                echo "</tr>";
            echo "</table>";

            echo "<br />";
        
            echo "<table width='400' border='0' align='center' name='relatorio' id='relatorio' class='tablesorter'>";
                echo "<thead>";
                    echo "<tr bgcolor='#6633CC'>";
                        echo "<th align='center' nowrap><font color='#ffffff'><b>";
                        // HD38821
                        if ($login_fabrica == 3) echo "Localização";
                        else echo "Posição";
                        echo "</b></font></th>";
                        if ($login_fabrica == 45) {
                            echo "<th align='center' nowrap><font color='#ffffff'><b>Ordem</b></font></th>";
                        }
                        if ($login_fabrica <> 45) {
                            echo "<th align='center' nowrap><font color='#ffffff'><b>Série inicial</b></font></th>";
                            echo "<th align='center' nowrap><font color='#ffffff'><b>Série final</b></font></th>";
                        }
                        echo "<th align='center' nowrap><font color='#ffffff'><b>Peça</b></font></th>";
                        echo "<th align='center' nowrap><font color='#ffffff'><b>Referência</b></font></th>";

                        echo "<th align='center' nowrap><font color='#ffffff'><b>Qtde</b></font></th>";
                        if ($login_fabrica == 45) {
                            echo "<th align='center' nowrap><font color='#ffffff'><b>Imagem</b></font></th>";
                        }
                    echo "</tr>";
                echo "</thead>";
            echo "<tbody>";

            for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
                $posicao        = pg_fetch_result($res,$i,'posicao');
                $ordem          = pg_fetch_result($res,$i,'ordem');
                $serie_inicial  = pg_fetch_result($res,$i,'serie_inicial');
                $serie_final    = pg_fetch_result($res,$i,'serie_final');
                $peca           = pg_fetch_result($res,$i,'referencia');
                $peca_id        = pg_fetch_result($res,$i,'peca');
                $desgaste       = pg_fetch_result($res,$i,'desgaste');
                $descricao      = pg_fetch_result($res,$i,'descricao');
                $qtde           = pg_fetch_result($res,$i,'qtde');
    //             $alternativa    = pg_fetch_result($res,$i,'alternativa');
    //             $alt_descricao  = pg_fetch_result($res,$i,'alt_descricao');
    //             $para           = pg_fetch_result($res,$i,'para');
    //             $para_descricao = pg_fetch_result($res,$i,'para_descricao');

                $cor = '#ffffff';

                $sqlA = "SELECT  tbl_peca_alternativa.de,
                                tbl_peca.descricao
                        FROM    tbl_peca_alternativa
                        JOIN    tbl_peca ON tbl_peca_alternativa.peca_de = tbl_peca.peca
                        WHERE   tbl_peca_alternativa.de    = '$peca'
			AND     tbl_peca_alternativa.status IS TRUE
                        AND     tbl_peca_alternativa.fabrica = $login_fabrica;";
                $resA = pg_query ($con,$sqlA);

                if (pg_num_rows($resA) > 0) {
                    $cor = "#91C8FF";
                    $peca = pg_fetch_result($resA,0,de);
                    $descricao = pg_fetch_result($resA,0,descricao);
                }

                $sqlD = "SELECT  tbl_depara.de,
                                tbl_peca.descricao,
                                tbl_peca.referencia,
                                (SELECT referencia FROM tbl_peca WHERE fabrica = {$login_fabrica} AND peca = peca_para) AS peca_para
                        FROM    tbl_depara
                        JOIN    tbl_peca ON tbl_peca.referencia = tbl_depara.de AND tbl_peca.fabrica = $login_fabrica
                        WHERE   tbl_depara.de    = '$peca'
                        AND     tbl_depara.fabrica = $login_fabrica;";
                $resD = pg_query ($con,$sqlD);

                if (pg_num_rows($resD) > 0) {
                    $cor = "#FF0099";
                    $peca = pg_fetch_result($resD,0,de);
                    $descricao = pg_fetch_result($resD,0,descricao);
                    
                }

                echo "<tr bgcolor='$cor' style='font-size:8pt'>";
                echo "<td align='left' nowrap>$posicao</td>";
                if ($login_fabrica == 45) {
                    echo "<td align='left' nowrap>$ordem</td>";
                }
                if ($login_fabrica <> 45) {
                    echo "<td align='left' nowrap>$serie_inicial</td>";
                    echo "<td align='left' nowrap>$serie_final</td>";
                }
                echo "<td align='left' nowrap>$peca</td>";
                echo "<td align='left' nowrap>$descricao</td>";

                echo "<td align='right' nowrap>$qtde</td>";
                if ($login_fabrica == 45) {
                echo "<td align='right' nowrap>";
                $xpecas = $tDocs->getDocumentsByRef($peca_id, "peca");
                if (!empty($xpecas->attachListInfo)) {

                    $a = 1;
                    foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
                        $fotoPeca = $vFoto["link"];
                        if ($a == 1){break;}
                    }
                    echo "   <a href='$fotoPeca' title='$descricao' class='thickbox'>
                                        <img src='$fotoPeca' border='0' width='80' height='50'>
                                    </a>";
                } else {


                    if ($dh = opendir("imagens_pecas/$login_fabrica/pequena/")) {
                        while (false !== ($filename = readdir($dh))) {
                            $xpeca = $peca_id.'.';
                            if (strpos($filename,$peca_id) !== false) {
                                $po = strlen($xpeca);
                                if (substr($filename, 0,$po) == $xpeca) {
                                    $contador++;
                                    $url_img_pq = "imagens_pecas/$login_fabrica/pequena/$filename";
                                    $url_img_md = "imagens_pecas/$login_fabrica/media/$filename";?>
                                    <a href='<?=$url_img_md ?>' title='<?=$descricao?>' class='thickbox'>
                                        <img src='<?=$url_img_pq ?>' border='0' width='80' height='50'>
                                    </a><?php
                                }
                            }
                        }
                    }
                }
                echo "</td>";
                }
                echo "</tr>";
            }
            echo "</tbody>";
            echo "</table>";
        }else{?>
            <table cellpadding="5" cellspacing="0" width="900px" border="1" align="center" style="font-size : 80%;">
                <tr bgcolor="#CCCCCC">
                    <th>Posição</th>
                    <th>Peça</th>
                    <th>Referência</th>
                    <th>Type</th>
                    <th>Qtde</th>
                    <th>Garantia da peça / Meses</th>
                </tr>
                <?php
                for ($i = 0; $i < pg_num_rows($res); $i++) {
                    $peca           = pg_fetch_result($res,$i,'referencia');
                    $descricao      = pg_fetch_result($res,$i,'descricao');
                    $type = pg_fetch_result($res, $i, 'type');

                    $sqlA = "SELECT  tbl_peca_alternativa.de,
                                    tbl_peca.descricao
                            FROM    tbl_peca_alternativa
                            JOIN    tbl_peca ON tbl_peca_alternativa.peca_de = tbl_peca.peca
                            WHERE   tbl_peca_alternativa.de    = '$peca'
                            AND     tbl_peca_alternativa.fabrica = $login_fabrica;";
                    $resA = pg_query ($con,$sqlA);
                    //echo "<hr>";
                    //thiago
                    //echo nl2br($sqlA);

                    if (pg_num_rows($resA) > 0) {                        
                        $peca = pg_fetch_result($resA,0,de);
                        $descricao = pg_fetch_result($resA,0,descricao);
                    }

                    $sqlD = "SELECT  tbl_depara.de,
                                    tbl_peca.descricao,
                                    tbl_peca.referencia,
                                    (select referencia from tbl_peca where fabrica = {$login_fabrica} and peca = peca_para) as peca_para
                            FROM    tbl_depara
                            JOIN    tbl_peca on tbl_peca.referencia = tbl_depara.de and tbl_peca.fabrica = $login_fabrica
                            WHERE   tbl_depara.de    = '$peca'
                            AND     tbl_depara.fabrica = $login_fabrica;";
                    $resD = pg_query ($con,$sqlD);
                    
                    //thiago                    
                    //echo nl2br($sqlD);

                    if (pg_num_rows($resD) > 0) {                        
                        $peca = pg_fetch_result($resD,0,de);                        
                        $descricao = pg_fetch_result($resD,0,descricao)."<br>Mudou para: ".pg_fetch_result($resD,0,peca_para) ;
                    }

                    $cor = ($i % 2 == 0) ? '#FFFFFF' : '#EEEEEE';                    
                    echo '<tr bgcolor="'.$cor.'">';
                    
                    $aux_posicao = pg_fetch_result($res,$i,'posicao');
                    $id_peca = pg_fetch_result($res, $i, 'peca');

                    if ($login_fabrica == 1 && empty($aux_posicao)) {
                        $aux_posicao = pg_fetch_result($res,$i,'ordem');
                    }

                        echo '<td align="center">&nbsp;'.$aux_posicao.'</td>';
                        echo '<td align="center">&nbsp;'.pg_fetch_result($res,$i,'referencia').'</td>';                      
                        echo '<td>&nbsp;'.$descricao.'</td>';
                        echo "<td align='center' nowrap>$type</td>";
                        echo '<td align="center">&nbsp;'.pg_fetch_result($res,$i,'qtde').'</td>';
                        $desgaste = pg_fetch_result($res, $i, desgaste);
                        
                        /* HD-4217476 */
                        if($login_fabrica == 1){

                            $garantia_meses = pg_fetch_result($res, $i, 'garantia_peca');

                            if (empty($garantia_meses)) {

                                $garantia_meses = pg_fetch_result($res, $i, 'garantia_diferenciada');

                                if (empty($garantia_meses)) {

                                    $garantia_meses = pg_fetch_result($res, $i, 'garantia');

                                }

                            }

                        }

                        echo '<td align="center">&nbsp;'.$garantia_meses.'</td>';                    
                    echo '</tr>';
                }?>
            </table>
            <?php
        }
    }
    if ($login_fabrica == 1 AND strlen($familia) > 0 AND strlen($referencia) == 0) {
            $s3ve->set_tipo_anexoS3("ve_familia");
            $arq_zip_linha = $s3ve->temAnexos($familia) ? $s3ve->url : '';
            // echo $arq_zip_linha;
            // exit;
        
            if (!$S3_online or !$s3ve->temAnexo) {
                $arq_zip_linha = file_exists($arq_zip_linha) ? $arq_zip_linha : false;
            }
        
        if ($arq_zip_linha) {
            ?>                
                <table align="center" width="700" class="formulario">
                    <tr>
                        <td align="center">                            
                            <input type="button" onclick="window.open('comunicado_download.php?arquivo=<?=urlencode($arq_zip_linha)?>')" value="Download Vista Explodida">
                        </td>
                    </tr>
                </table>
            <?
        } else {
            echo " Família sem vista(s) explodida(s)";
        }
        $s3ve->set_tipo_anexoS3('ve'); // volta para reutilizar
    }
    if ($login_fabrica == 1 AND strlen($familia) > 0 AND strlen($referencia) > 0) {
        echo "Selecionar somente uma das Opções( Produto ou Família).";
    }

}

include "rodape.php"; ?>

</body>
</html>
