<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$tipo = ($tipo == "video") ? "Vídeos de Treinamento" : $tipo;
$tipo  = urldecode($tipo);
$title = $tipo;

if ($tipo=='Vista Explodida'){    $layout_menu = 'tecnica'; }
if ($tipo=='Promoções'){          $layout_menu = 'promocoes'; }
if ($tipo=='Lançamentos'){        $layout_menu = 'lancamentos'; }
if ($tipo=='Peças de Reposição'){ $layout_menu = 'reposicao'; }
if ($tipo=='Lançamentos'){        $layout_menu = 'lancamentos'; }
if ($tipo=='Produtos'){           $layout_menu = 'produtos'; }

include "cabecalho.php";

$fabrica_comunicado = $login_fabrica == 168 ? 151 : $login_fabrica;

if ($S3_sdk_OK) {
	include_once S3CLASS;
	$s3 = new anexaS3('ve', (int) $login_fabrica);
	$S3_online = is_object($s3);
}
?>

<style>
    span.versao {
        margin-left: 1ex;
        font-style: italic;
        color: darkslategray;
    }

    .titulo {
        font-family: Arial;
        font-size: 9pt;
        text-align: center;
        font-weight: bold;
        color: #FFFFFF;
        background: #408BF2;
    }
    .titulo2 {
        font-family: Arial;
        font-size: 12pt;
        text-align: center;
        font-weight: bold;
        color: #FFFFFF;
        background: #408BF2;
    }

    .conteudo {
        font-family: Arial;
        FONT-SIZE: 8pt;
        text-align: left;
    }
    .Tabela{
        border:1px solid #485989;

    }
    img{
        border: 0px;
    }
</style>

<script src="js/jquery-1.6.2.js" ></script>
<script src="js/jquery.blockUI_2.39.js" ></script>
<script src="plugins/shadowbox/shadowbox.js"></script>
<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" >
<script>
	var fabrica = <?=$fabrica_comunicado?>;
    $(function () {
        $("a[name=prod_ve]").click(function () {
            var comunicado = $(this).attr("rel");

            $.ajaxSetup({
                async: true
            });

            $.blockUI({ message: "Aguarde..." });

			$.get("verifica_s3_comunicado.php", {
                comunicado: comunicado,
                tipo: 've',
                fabrica: fabrica
            }, function (data) {
                if (data.length > 0) {
                    Shadowbox.init();

                 //   var imagem = new Image();
                 //   imagem.onload = function() {
                 //       var height = this.height,
                 //          width = this.width;

                 //       var porcentagem = (width * 15) / 100;
                 //          width = width + porcentagem;
                 //          Shadowbox.open({
                 //               content :   data,
                 //               player  :   "iframe",
                 //               title   :   "Vista Explodida",
                 //               width   :   width
                 //           });
                 //   }
                 //   imagem.src = data;

                    Shadowbox.open({
                        content :   data,
                        player  :   "iframe",
                        title   :   "Vista Explodida",
                        width   : 900
                    });


                } else {
                    alert("Arquivo não encontrado!");
                }

                $.unblockUI();
            });
        });
    });

    var popupBlockerChecker = {
        check: function(popup_window) {
            var _scope = this;

            if (popup_window) {
                if (/chrome/.test(navigator.userAgent.toLowerCase())) {
                    setTimeout(function() {
                        _scope._is_popup_blocked(_scope, popup_window);
                    }, 500);
                }else{
                    popup_window.onload = function() {
                        _scope._is_popup_blocked(_scope, popup_window);
                    };
                }
            }else{
                _scope._displayMsg();
            }
        },
        _is_popup_blocked: function(scope, popup_window){
            if ((popup_window.screenX > 0) == false) {
                scope._displayMsg();
            }
        },
        _displayMsg: function() {
            Shadowbox.init();

            Shadowbox.open({
                content :   "popup_bloqueado.php",
                player  :   "iframe",
                title   :   "POPUP BLOQUEADO",
                width   :   800,
                height  :   600
            });
        }
    };
</script>

<?
include "verifica_adobe.php";
?>

<?php
$tipo    = $_GET ['tipo'];
$familia = $_GET ['familia'];
$linha   = $_GET ['linha'];

$tipo = ($tipo == "video") ? "Vídeo" : $tipo;

$xtipo = utf8_decode($tipo);
# SELECIONA A FAMÍLIA DO POSTO
$sql = "SELECT familia FROM tbl_posto_linha WHERE posto = $login_posto";
$res = pg_exec($con,$sql);

$familia_posto = '';

for ($i = 0; $i < pg_numrows($res); $i++) {
    if (strlen(pg_result ($res,$i,0))) {
        $familia_posto .= pg_result ($res,$i,0);
        $familia_posto .= ", ";
    }
}

# SELECECIONA O TIPO DE COMUNICADO DO POSTO
$sql2 = "SELECT tbl_posto_fabrica.codigo_posto        ,
                tbl_posto_fabrica.tipo_posto
           FROM tbl_posto
      LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
          WHERE tbl_posto_fabrica.fabrica = $login_fabrica
            AND tbl_posto.posto   = $login_posto ";

$res2 = pg_exec($con,$sql2);

if (pg_numrows($res2) > 0) {
    $tipo_posto = trim(pg_result($res2,0,tipo_posto));
}

#SELECIONA O COMUNICADO
if (strlen ($tipo) > 0 ) {
    $tipo = urldecode($tipo);

    if (!isFabrica(1, 15, 42, 168))
        $sqlPostoLinha = "
                        AND (tbl_comunicado.linha IN
                                (
                                    SELECT tbl_linha.linha
                                    FROM tbl_posto_linha
                                    JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha
                                    WHERE fabrica =$login_fabrica
                                        AND posto = $login_posto
                                )
                                OR (
                                        tbl_comunicado.produto IS NULL AND
                                        tbl_comunicado.comunicado IN (
                                            SELECT tbl_comunicado_produto.comunicado
                                            FROM tbl_comunicado_produto
                                            JOIN tbl_produto ON tbl_comunicado_produto.produto = tbl_produto.produto
                                            JOIN tbl_posto_linha on tbl_posto_linha.linha = tbl_produto.linha
                                            WHERE fabrica_i =$login_fabrica AND
                                                  tbl_posto_linha.posto = $login_posto

                                        )

                                )
                                OR
                                    (
                                    tbl_comunicado.linha IS NULL AND
                                    tbl_comunicado.produto in
                                        (
                                            SELECT tbl_produto.produto
                                            FROM tbl_produto
                                            JOIN tbl_posto_linha ON tbl_produto.linha = tbl_posto_linha.linha
                                            WHERE fabrica_i = $login_fabrica AND
                                            posto = $login_posto
                                        )
                                    )

                                 OR (tbl_comunicado.linha IS NULL AND tbl_comunicado.produto IS NULL AND
                                        tbl_comunicado.comunicado IN (
                                            SELECT tbl_comunicado_produto.comunicado
                                            FROM tbl_comunicado_produto
                                            JOIN tbl_produto ON tbl_comunicado_produto.produto = tbl_produto.produto
                                            JOIN tbl_posto_linha on tbl_posto_linha.linha = tbl_produto.linha
                                            WHERE fabrica_i =$login_fabrica AND
                                                  tbl_posto_linha.posto = $login_posto

                                            )

                            )
                    )";

    $sql = "SELECT tbl_comunicado.comunicado,
                   tbl_comunicado.descricao,
                   tbl_comunicado.mensagem,
                   tbl_comunicado.serie AS serie_comunicado,
                   CASE WHEN tbl_comunicado.produto IS NULL THEN prod.produto               ELSE tbl_produto.produto                END AS produto,
                   CASE WHEN tbl_comunicado.produto IS NULL THEN prod.referencia            ELSE tbl_produto.referencia             END AS referencia,
                   CASE WHEN tbl_comunicado.produto IS NULL THEN prod.descricao             ELSE tbl_produto.descricao              END AS descricao_produto,
                   CASE WHEN tbl_comunicado.produto IS NULL THEN prod.inibir_lista_basica   ELSE tbl_produto.inibir_lista_basica    END AS inibir_lista_basica,
                   TO_CHAR(tbl_comunicado.data,'DD/MM/YYYY') AS data,
                   tbl_produto.produto,
                   CASE WHEN length(trim(tbl_comunicado.video)) > 0 THEN tbl_comunicado.video ELSE tbl_comunicado.link_externo END AS video,
                   tbl_comunicado.versao,
                   tbl_comunicado.extensao
              FROM tbl_comunicado
              LEFT JOIN tbl_comunicado_produto ON tbl_comunicado_produto.comunicado = tbl_comunicado.comunicado
              LEFT JOIN tbl_produto            ON tbl_produto.produto               = tbl_comunicado.produto
              LEFT JOIN tbl_produto prod       ON prod.produto                      = tbl_comunicado_produto.produto
             WHERE tbl_comunicado.fabrica     = $fabrica_comunicado
               AND (tbl_comunicado.tipo_posto = $tipo_posto   OR  tbl_comunicado.tipo_posto IS NULL)
               AND ((tbl_comunicado.posto     = $login_posto) OR (tbl_comunicado.posto      IS NULL))
               ".$sqlPostoLinha."
               AND tbl_comunicado.ativo      IS TRUE ";

        if ($login_fabrica == 5) {
            $sql .=" AND (tbl_produto.ativo IS TRUE OR prod.ativo IS TRUE) ";
        }
        if ($tipo == 'zero') {
            $tipo = "Sem Título";
            $sql .= "AND tbl_comunicado.tipo IS NULL ";
        } else {
            $sql .= "AND tbl_comunicado.tipo in ('$tipo','$xtipo') ";
        }

        if ($linha)   $sql .= "AND (tbl_produto.linha = $linha OR prod.linha = $linha OR tbl_comunicado.linha = $linha) ";
        if ($familia) $sql .= "AND (tbl_produto.familia = $familia OR prod.familia = $familia) ";

        //if($login_fabrica==19 AND $login_posto==6359 AND $linha = 261){ # Metais
            //$sql .= "AND tbl_comunicado.produto is not null ";
        //}

    $sql .= "ORDER BY tbl_produto.descricao DESC,tbl_comunicado.descricao , tbl_produto.referencia " ;
	// echo nl2br($sql);

if($login_fabrica == 91 and $tipo =='Vídeo') {
	$sql = " SELECT tbl_comunicado.comunicado,
                    tbl_comunicado.descricao,
                    ARRAY_TO_STRING(ARRAY_AGG(CASE WHEN tbl_comunicado.produto IS NULL THEN prod.produto    ELSE tbl_produto.produto    END),' - ') AS produto,
                    ARRAY_TO_STRING(ARRAY_AGG(CASE WHEN tbl_comunicado.produto IS NULL THEN prod.referencia ELSE tbl_produto.referencia END),' - ') AS referencia,
                    ARRAY_TO_STRING(ARRAY_AGG(CASE WHEN tbl_comunicado.produto IS NULL THEN prod.descricao  ELSE tbl_produto.descricao  END),' - ') AS descricao_produto,
                    TO_CHAR(tbl_comunicado.data,'dd/mm/yyyy') AS data,
                    tbl_comunicado.video
               FROM tbl_comunicado
          LEFT JOIN tbl_comunicado_produto ON tbl_comunicado_produto.comunicado = tbl_comunicado.comunicado
          LEFT JOIN tbl_produto            ON tbl_produto.produto = tbl_comunicado.produto
          LEFT JOIN tbl_produto prod       ON prod.produto        = tbl_comunicado_produto.produto
              WHERE tbl_comunicado.fabrica     = $login_fabrica
                AND (tbl_comunicado.tipo_posto = $tipo_posto  OR tbl_comunicado.tipo_posto IS NULL)
                AND (tbl_comunicado.posto      = $login_posto OR tbl_comunicado.posto      IS NULL)
                AND tbl_comunicado.ativo IS TRUE
                AND tbl_comunicado.tipo in ('$tipo', '$xtipo') ";
        if ($linha)   $sql .= "AND (tbl_produto.linha = $linha OR prod.linha = $linha OR tbl_comunicado.linha = $linha) ";
        if ($familia) $sql .= "AND (tbl_produto.familia = $familia OR prod.familia = $familia) ";

	$sql .= " GROUP BY tbl_comunicado.comunicado,
			tbl_comunicado.data,
			tbl_comunicado.descricao,
			tbl_comunicado.video";

}

    $res = pg_exec($con,$sql);
    // include 'helpdesk/mlg_funciones.php';
    // die(array2table($res, 'Comunicados'));

	$tipo_titulo = ($tipo == "Vídeo") ? "Vídeos de Treinamento" : $tipo;

    if (pg_numrows($res) > 0) {
        echo "<table width='700' align='center' class='Tabela' cellspacing='0' cellpadding='3' border='1' style='border-collapse: collapse' bordercolor='#d2e4fc'>";
        echo "<tr class='titulo2'>";
        echo "<td colspan='4' background='admin/imagens_admin/laranja.gif' height='25'>$tipo_titulo</td>";
        echo "</tr>";

        if(!in_array($tipo,array("Video","Vídeo"))){
            echo "<tr bgcolor='#ffffff'>";
            echo "<td align='center' colspan='4'><font color='#000000' size='0'><b>Se você não possui o Acrobat Reader&reg;, <a href='http://www.adobe.com/products/acrobat/readstep2.html' target='_blank'>instale agora</a>.</b></font></td>";
            echo "</tr>";
        }
        if ($tipo != 'Esquema Elétrico' && $login_fabrica == 19) {
            echo "<tr bgcolor='#ffffff'>";
            echo "<td align='center' colspan='4'><b>Você está em ";

            $sql1 = "SELECT nome FROM tbl_linha WHERE linha=$linha";
            $res1 = pg_exec($con,$sql1);
            echo trim(pg_result($res1,0,nome));
        }
        if (strlen($familia) > 0) {
            $sql2="SELECT descricao FROM tbl_familia WHERE familia=$familia";
            $res2 = pg_exec($con,$sql2);
            echo " - ".trim(pg_result($res2,0,descricao));
        }
        if ($tipo_lista=='produto' && $login_fabrica == 19) {
            echo " - Produtos";
        }
        if ($tipo_lista == 'reposicao' && $login_fabrica == 19) {
            echo " - Peças de Reposição";
        }

        echo "</b></td>";
        echo "</tr>";

        echo "<tr class='titulo' >";
        echo "<td background='admin/imagens_admin/azul.gif'>";
        echo ($login_fabrica == 91 and $tipo =='Vídeo') ? 'Descrição':'Referência';
        echo "</td>";
        if ($login_fabrica == 11 || $login_fabrica == 15) { //HD 198907
            echo "<td background='admin/imagens_admin/azul.gif'>Descrição/Titulo</td>";
        }

        if ($login_fabrica == 19) $colspan = " colspan = '2'";

        echo "<td background='admin/imagens_admin/azul.gif' $colspan>";
        echo ($login_fabrica == 91 and $tipo =='Vídeo') ? 'Referências':'Produto';
        echo "</td>";

        if (in_array($login_fabrica, array(169,170))){
            echo "<td background='admin/imagens_admin/azul.gif' $colspan>";
            echo "Número de série";
            echo "</td>";
        }

        if ($login_fabrica == 175){
            echo "<td background='admin/imagens_admin/azul.gif' $colspan>";
            echo "Ordem de produção";
            echo "</td>";
        }
        
        if ($tipo == 'Atualização de Software') {
            echo "<td background='admin/imagens_admin/azul.gif'>Título</td>";
        }

        if(in_array($tipo,array("Video","Vídeo"))){
            echo "<td background='admin/imagens_admin/azul.gif'>Vídeo</td>";
        }

        echo "</tr>";

        $total = pg_num_rows($res);
        $file_types = array("gif", "jpg", "pdf", "doc", "rtf", "xls", "ppt", "zip");

        for ($i = 0; $i < $total; $i++) {
            $Xcomunicado          = pg_fetch_result($res, $i, 'comunicado');
            $produto              = pg_fetch_result($res, $i, 'produto');
            $referencia           = pg_fetch_result($res, $i, 'referencia');
            $titulo               = pg_fetch_result($res, $i, 'descricao');
            $descricao            = pg_fetch_result($res, $i, 'descricao_produto');
            $produto_versao       = pg_fetch_result($res, $i, 'versao');
            $inibir_lista_basica  = pg_fetch_result($res, $i, 'inibir_lista_basica');
            $comunicado_descricao = pg_fetch_result($res, $i, 'descricao');
            $video                = pg_fetch_result($res, $i, 'video');
            $extensao             = pg_fetch_result($res, $i, 'extensao');
            $serie_comunicado     = pg_fetch_result($res, $i, 'serie_comunicado');
            $cor = ($i % 2 == 0) ? "#EEF" : "#FFF";

            echo "<tr class='conteudo' style='background-color: $cor;'>
                <td align='center'>" . (($login_fabrica == 91 and $tipo =='Vídeo') ? "$titulo" : "$referencia") . "</td>";

            if ($login_fabrica == 11 or $login_fabrica == 15) {
                echo "<td align='center'>$titulo</td>";
            }

            //SOMENTE PARA LINHA METAIS HD 100696
            echo "<td nowrap>";

            if ($login_fabrica != 45 or ($login_fabrica == 45 AND $tipo == 'Esquema Elétrico')) {
                if ($S3_online) {

                    // $s3->temAnexos((int) $Xcomunicado);

                    // $link = $data["url"] = $s3->url;

                    if (!empty($extensao)) {
                        if ($login_fabrica == 1 && $inibir_lista_basica == 't') {
                            $prod_ve_link = "";
                        }else {
                            $prod_ve_link = "<a href='JavaScript:void(0);' name='prod_ve' rel='$Xcomunicado'>";
                        }
                    }
                } else {
                    foreach ($file_types as $type) {
                        if (file_exists("comunicados/$Xcomunicado.$type")) $prod_ve_link = "<a href='comunicados/$Xcomunicado.$type' target='_blank'>";
                    }
                }

                if (strlen($descricao) > 0) {
                    $prod_ve_descricao = ($login_fabrica == 91 and $tipo =='Vídeo') ? "$referencia" : "$descricao";
                } else {
                    $prod_ve_descricao = $comunicado_descricao;
                }

                if ($login_fabrica == 14) $prod_ve_descricao .= " - " . $comunicado_descricao;

                if ($usa_versao_produto and strlen($produto_versao)) {
                    $span_versao = "<span class='versao'>(versão $produto_versao)</span>";
                }

                echo "&nbsp; $prod_ve_link $prod_ve_descricao" . (($prod_ve_link) ? "</a>" : "");
                } else {
                    echo "<a href='lbm_impressao.php?produto=$produto' target='_blank'>$descricao</a>";
                }

                echo "$span_versao</td>";

                if (in_array($login_fabrica, array(169,170))){
                    echo "<td align='center'>";
                    echo $serie_comunicado;
                    echo "</td>";
                }

                if ($login_fabrica == 175){
                    echo "<td align='center'>";
                    echo $produto_versao;
                    echo "</td>";
                }

                if ($tipo == 'Atualização de Software') {
                    echo "<td align='left'>$comunicado_descricao</td>";
                }

                if ($login_fabrica == 19) {
                    echo "<td>";
                    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
                    if ($tipo_lista == "produto") {
                        if (strlen($produto) > 0) {?>
                            <a href='peca_pesquisa_lista.php?produto=<?=$produto; ?>&tipo=referencia&linha=261' target='_blank' onClick="window.open(this.href, this.target, 'width=500,height=500,scrollbars=yes'); return false;">Peças</a><?php
                        }
                    }
                    if ($tipo_lista == "reposicao") {
                        if (strlen($produto) > 0) {?>
                            <a href='peca_pesquisa_lista.php?produto=<?=$produto; ?>&tipo=referencia&linha=261&peca_reposicao=t' target='_blank' onClick="window.open(this.href, this.target, 'width=500,height=500,scrollbars=yes'); return false;">Peças</a><?php
                        }
                    }
                    echo "</td>";
                }

                if(in_array($tipo,array("Video","Vídeo"))){
                    echo "<td>";
                    echo "<a href='$video' target='_blank'>Vídeo</a>";
                    echo "</td>";
                }

                echo "</tr>\n";

                }

                echo "</form>\n";
                echo "</table>\n";

                echo "<hr />";

    } else {
        echo "<center>Nenhum $tipo cadastrado</center>";
    }
}
?>
