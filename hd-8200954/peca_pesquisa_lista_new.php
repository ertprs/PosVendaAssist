 <?php
 include "dbconfig.php";
 include "includes/dbconnect-inc.php";
 include 'autentica_usuario.php';
include_once "class/tdocs.class.php";
$tDocs = new TDocs($con, $login_fabrica);


 /*HD 16027 Produto acabado, existia algumas selects sem a validação*/
 #include 'cabecalho_pop_pecas.php';
 header("Expires: 0");
 header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
 header("Pragma: no-cache, public");

     $caminho = "imagens_pecas";

     if(!in_array($login_fabrica, array(10,172))){
        $caminho = $caminho."/".$login_fabrica;
     }

     if(in_array($login_fabrica, array(172))){
        $caminho = $caminho."/11";
     }


 $ajax = $_GET['ajax'];
 $ajax_kit    = $_GET['ajax_kit'];
 $kit_peca_id = $_GET['kit_peca_id'];
 $kit_peca    = $_GET['kit_peca'];
 $input_posicao = $_GET['input_posicao'];
if($login_fabrica == 3){
    if($_GET['tipo'] == 'referencia'){
        $referencia = $_GET['peca'];

    }else if($_GET['tipo'] == 'descricao'){
        $descricao = $_GET["descricao"];
    }
}
 if(strlen($ajax)>0){
 $arquivo = $_GET['arquivo'];
     echo "<table align='center' style='background-color: #b8b7af;'>";
     echo "<tr>";
     echo "<td>
            <span style='width: 100%;text-align: right;'><a href=\"javascript:escondePeca();\"><FONT size='1'><B>&nbsp;&nbsp;FECHAR</B></font></a></span>";
     echo "</td>";
     echo "</tr>";
     if ($login_fabrica == 85) {
        echo "<tr><td align='center'>Clique na imagem para ampliá-la</td></tr>";
     }
     echo "<tr>";
     echo "<td align='center'>";
    $idpeca = $_GET['idpeca'];

    $xpecas  = $tDocs->getDocumentsByRef($idpeca, "peca");
    if (!empty($xpecas->attachListInfo)) {

        $a = 1;
        foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
            $fotoPeca = $vFoto["link"];
            if ($a == 1){break;}
        }

        if ($login_fabrica == 85) {
            $abreTela = '"'.$fotoPeca.'"';
            $targetBlank = "target='_blank'";
        } else {
            $abreTela = "\"javascript:escondePeca();\"";
        }

        echo "<a href=$abreTela $targetBlank>";
        echo "<img src='$fotoPeca' height='297' border='0'>";
    } else {
        if ($login_fabrica == 85) {
            $abreTela = '"$caminho/media/$arquivo"';
            $targetBlank = "target='_blank'";
        } else {
            $abreTela = "\"javascript:escondePeca();\"";
        }

       echo "<a href=$abreTela $targetBlank>";
       echo "<img src='$caminho/media/$arquivo' height='297' border='0'>";
    }
     echo "</a>";
     echo "</td>";
     echo "</tr>";
     echo "</table>";
     exit;

 }

 if ($login_fabrica == 3 or $login_fabrica == 35) {
     $linha_i = $_GET["linha_i"];
 }


 $exibe_mensagem = 't';
 if (strpos($_GET['exibe'],'pedido') !== false) $exibe_mensagem = 'f';

 # verifica se posto pode ver pecas de itens de aparencia
 $sql = "SELECT   tbl_posto_fabrica.item_aparencia,
          tbl_posto_fabrica.tabela,
          tbl_posto_fabrica.pedido_em_garantia
     FROM     tbl_posto
     JOIN     tbl_posto_fabrica USING(posto)
     WHERE    tbl_posto.posto           = $login_posto
     AND      tbl_posto_fabrica.fabrica = $login_fabrica";
 $res = pg_query ($con,$sql);

 if (pg_num_rows ($res) > 0) {
     $item_aparencia = pg_fetch_result($res,0,item_aparencia);
     $tabela         = pg_fetch_result($res,0,tabela);
     $pedido_em_garantia = pg_result($res,0,'pedido_em_garantia');
 }

 /*Modificado por Fernando
 Pedido de Leandro da Tectoy por E-mail. Modificação foi feita para que os postos
 que não podem fazer pedido em garantia (OS) de peças, cadastradas como item aparencia, possa
 fazer pedido faturado através da tela "pedido_cadastro.php".
 */
 ##### INICIO ######
 if($login_fabrica == 6){
     $faz_pedido = $_GET['exibe'];

     if(preg_match("pedido_cadastro_normal.php", $faz_pedido)){
         $item_aparencia = 't';
     }

     if(preg_match("os_item_new.php", $faz_pedido)){
             $libera_bloqueado = 't';
     }
 }
 /*Modificado por Fernando
 Pedido de Leandro da Tectoy por E-mail. Modificação foi feita para que os postos
 que não podem fazer pedido em garantia (OS) de peças, cadastradas como item aparencia, possa
 fazer pedido faturado através da tela "pedido_cadastro.php".
 */
 ##### INICIO ######
 if($login_fabrica == 6){
     $faz_pedido = $_GET['exibe'];

     if(preg_match("pedido_cadastro_normal.php", $faz_pedido)){
         $item_aparencia = 't';
     }

     #Fabio - HD 3921 - Para PA fazer pedido
     if(preg_match("tabela_precos_tectoy.php", $faz_pedido)){
         $item_aparencia = 't';
     }
 }
 ##### FIM ######

 if($login_fabrica == 3){
     $faz_pedido = $_GET['exibe'];
     if(preg_match("os_item_new.php", $faz_pedido)){
             $libera_bloqueado = 't';
     }
 }
 ##### FIM ######
 if ( !empty($ajax_kit) ) {

     $sql = " SELECT tbl_peca.peca      ,
                     tbl_peca.referencia,
                     tbl_peca.descricao,
                     tbl_kit_peca_peca.qtde
             FROM    tbl_kit_peca_peca
             JOIN    tbl_peca USING(peca)
             WHERE   fabrica = $login_fabrica
             AND     kit_peca = $kit_peca_id
             ORDER BY tbl_peca.peca";

     $res = pg_query($con, $sql);
     $resultado = "";

     if (pg_num_rows($res) > 0) {

         $resultado = "<table borde=1>";
         $resultado .="<tr><td colspan='100%'><input type='hidden' name='kit_$kit_peca' id='kit_$kit_peca' value='$kit_peca_id'></td></tr>";

         for ($i = 0; $i < pg_num_rows($res); $i++) {

             $peca     = pg_fetch_result($res, $i, 'peca');
             $qtde_kit = pg_fetch_result($res, $i, 'qtde');

             $resultado .=   "<tr style='font-size: 11px'>".
                             "<td>".
                 "<input type='".(($login_fabrica == 15 OR $login_fabrica == 91 || $login_fabrica == 3) ? 'hidden' : 'checkbox')."' name='kit_peca_$peca' value='$peca' CHECKED > ".
                             "<input type='text' name='kit_peca_qtde_$peca' id='kit_peca_qtde_$peca' size='5' value='$qtde_kit' onkeyup=\"re = /\D/g; this.value = this.value.replace(re, '');\" readonly='readonly'> x ".
                             pg_fetch_result($res,$i,'referencia').
                             "</td>".
                             "<td> - ".
                             pg_fetch_result($res,$i,'descricao').
                             "</td>".
                             "</tr>";
         }

         $resultado .= "</table>";

         echo "ok|$resultado";

     }

     exit;

 }
 ?>

 <!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
 <html>
 <head>
 <title> Pesquisa Peças pela Lista Básica ... </title>
 <meta name="Author" content="">
 <meta name="Keywords" content="">
 <meta name="Description" content="">
 <meta http-equiv=pragma content=no-cache>
 <link href="css/posicionamento.css" rel="stylesheet" type="text/css" />
 </head>
 <style>.Div{
     BORDER-RIGHT:     #6699CC 1px solid;
     BORDER-TOP:       #6699CC 1px solid;
     BORDER-LEFT:      #6699CC 1px solid;
     BORDER-BOTTOM:    #6699CC 1px solid;
     FONT:             10pt Arial ;
     COLOR:            #000;
     BACKGROUND-COLOR: #FfFfFF;
 }</style>
 <script type="text/javascript"    src="js/jquery-1.7.2.js"></script>
 <script>
 function onoff(id) {
 var el = document.getElementById(id);
 el.style.display = (el.style.display=="") ? "none" : "";
 }
 function createRequestObject(){
     var request_;
     var browser = navigator.appName;
     if(browser == "Microsoft Internet Explorer"){
          request_ = new ActiveXObject("Microsoft.XMLHTTP");
     }else{
          request_ = new XMLHttpRequest();
     }
     return request_;
 }

 function escondePeca(){
     if (document.getElementById('div_peca')){
         var style2 = document.getElementById('div_peca');
         if (style2==false) return;
         if (style2.style.display=="block"){
             style2.style.display = "none";
         }else{
             style2.style.display = "block";
         }
     }
 }
 function mostraPeca(arquivo, peca) {
 //alert(arquivo);
 var el = document.getElementById('div_peca');
     el.style.display = (el.style.display=="") ? "none" : "";
     imprimePeca(arquivo,peca);

 }
 var http3 = new Array();
 function imprimePeca(arquivo,peca){

     var curDateTime = new Date();
     http3[curDateTime] = createRequestObject();

     url = "peca_pesquisa_lista_new.php?ajax=true&idpeca="+peca+"&arquivo="+ arquivo;
     http3[curDateTime].open('get',url);
     var campo = document.getElementById('div_peca');
     Page.getPageCenterX();
     campo.style.top = (Page.top + Page.height/2)-160;
     campo.style.left = Page.width/2-220;
     http3[curDateTime].onreadystatechange = function(){
         if(http3[curDateTime].readyState == 1) {
             campo.innerHTML = "<font size='1' face='verdana'>Aguarde..</font>";
         }
         if (http3[curDateTime].readyState == 4){
             if (http3[curDateTime].status == 200 || http3[curDateTime].status == 304){

                 var results = http3[curDateTime].responseText;
                 campo.innerHTML   = results;
             }else {
                 campo.innerHTML = "Erro";
             }
         }
     }
     http3[curDateTime].send(null);

 }
 var Page = new Object();
 Page.width;
 Page.height;
 Page.top;

 Page.loadOut = function (){
     document.getElementById('div_peca').innerHTML ='';
 }
 Page.getPageCenterX = function (){
     var fWidth;
     var fHeight;
     //For old IE browsers
     if(document.all) {
         fWidth = document.body.clientWidth;
         fHeight = document.body.clientHeight;
     }
     //For DOM1 browsers
     else if(document.getElementById &&!document.all){
             fWidth = innerWidth;
             fHeight = innerHeight;
         }
         else if(document.getElementById) {
                 fWidth = innerWidth;
                 fHeight = innerHeight;
             }
             //For Opera
             else if (is.op) {
                     fWidth = innerWidth;
                     fHeight = innerHeight;
                 }
                 //For old Netscape
                 else if (document.layers) {
                         fWidth = window.innerWidth;
                         fHeight = window.innerHeight;
                     }
     Page.width = fWidth;
     Page.height = fHeight;
     Page.top = window.document.body.scrollTop;
 }
 function kitPeca(kit_peca_id,kit_peca,i) {

     var id_defeito = kit_peca.replace('kit_peca_', '');
     var login_fabrica = "<?=$login_fabrica?>";

     $.ajax({
         type: 'GET',
         url: '<?=$PHP_SELF?>',
         data: 'kit_peca_id='+kit_peca_id+'&kit_peca='+kit_peca+'&ajax_kit=sim',
         beforeSend: function(){
             window.opener.$('#'+kit_peca).html(' ');
         },
         complete: function(resposta) {

             resultado = resposta.responseText.split('|');

             if (resultado[0].trim() == 'ok') {
                 console.log(kit_peca);
                 window.opener.$('#'+kit_peca).append(resultado[1]);

                 if (login_fabrica == 91 || login_fabrica == 3 || login_fabrica == 30 ) {
                     window.opener.$("input[name=kit_kit_peca_"+i+"]").val(kit_peca_id);
                 }

             } else {

                 window.opener.$('#'+kit_peca).html(' ');

             }

             window.close();

         }
     });
 }

 </script>
 <body leftmargin="0" >
 <!--onblur="setTimeout('window.close()',10000);"-->
 <br>

 <img src="imagens/pesquisa_pecas.gif">

 <?
 $tipo = trim (strtolower ($_GET['tipo']));

 $tipo_pedido = trim($_GET['tipo_pedido']);


 $produto = $_GET['produto'];
 if ( in_array($login_fabrica, array(11,172)) ) $produto = "";

 if ($login_fabrica == 6) {
     $os= $_GET['os'];
     if(strlen($os)>0){
         $sql   = "SELECT serie from tbl_os where os = $os and fabrica = $login_fabrica";
         $res   = @pg_query($con,$sql);
         $serie = @pg_fetch_result($res,0,serie);
     }
 }

 if (strlen ($produto) > 0) {
     $produto_referencia = trim($_GET['produto']);
     $produto_referencia = str_replace(".","",$produto_referencia);
     $produto_referencia = str_replace(",","",$produto_referencia);
     $produto_referencia = str_replace("-","",$produto_referencia);
     $produto_referencia = str_replace("/","",$produto_referencia);
     $produto_referencia = str_replace(" ","",$produto_referencia);
     $produto_referencia2 = strtoupper($produto_referencia);

     $voltagem = trim(strtoupper($_GET["voltagem"]));

     $sql = "SELECT tbl_produto.produto, tbl_produto.descricao
             FROM   tbl_produto
             JOIN   tbl_linha USING (linha)
             WHERE  tbl_produto.referencia_pesquisa = '$produto_referencia2' ";

     if (strlen($voltagem) > 0 AND $login_fabrica == "1" ) $sql .= " AND UPPER(tbl_produto.voltagem) = UPPER('$voltagem') ";

     $sql .= "AND    tbl_linha.fabrica = $login_fabrica ";

     if($login_fabrica <> 3)	$sql .= "AND    tbl_produto.ativo IS TRUE";

 //if ($ip == '201.13.179.45') echo ($sql); echo ($produto_referencia); echo ($produto);

     $res = pg_query ($con,$sql);

     if (pg_num_rows($res) > 0) {
         $produto_descricao = pg_fetch_result ($res,0,descricao);
         $produto = pg_fetch_result ($res,0,produto);
     }else{
         $produto = '';
     }
 }
 $cond_produto =" 1=1 ";
 if($login_fabrica <> 3 ) $cond_produto = " tbl_produto.ativo IS TRUE " ;

 if($login_fabrica == 30 ){
     $join_busca_referencia = 'LEFT JOIN tbl_esmaltec_referencia_antiga ON (tbl_peca.referencia = tbl_esmaltec_referencia_antiga.referencia) ';

     if (!empty($tipo_pedido)){
         $sql_descobre_tipo = "SELECT upper(descricao) as descricao from tbl_tipo_pedido where tipo_pedido = $tipo_pedido";

         $res_descobre_tipo = pg_query($con,$sql_descobre_tipo);

         if (pg_num_rows($res_descobre_tipo)>0) {
             $descricao_tipo = pg_result($res_descobre_tipo,0,descricao);
             if($pedido_em_garantia == 'f') {
                 if ($descricao_tipo == 'REMESSA EM GARANTIA') {
                     $sql_tipo_pedido = " AND tbl_peca.remessa_garantia is true ";
                 } else if ($descricao_tipo == 'REMESSA EM GAR DE COMPRESSOR')  {
                     $sql_tipo_pedido = " AND tbl_peca.remessa_garantia_compressor is true ";
                 } else {
                     $sql_tipo_pedido = "  ";
                 }
             }
         }
     }
     //echo $sql_tipo_pedido;
 }


 //if ($ip == '201.0.9.216') echo "Xii<br>";
    if (strlen ($produto) > 0) {
         if ($login_fabrica == 15 || $login_fabrica == 24 || $login_fabrica == 91 || $login_fabrica == 3) {//HD 258901 - KIT

             if ($login_fabrica == 24 ) {

                 $sql = " SELECT tbl_kit_peca.referencia,
                          tbl_kit_peca.descricao,
                          tbl_kit_peca.kit_peca
                  FROM    tbl_kit_peca
                  WHERE   tbl_kit_peca.fabrica = $login_fabrica ";

                 if (!empty($produto)) {
                     $sql .=	" AND tbl_kit_peca.produto = $produto";
                 }

             } else if ($login_fabrica == 15 || $login_fabrica == 91) {

                 $sql = "SELECT tbl_kit_peca.referencia,
                         tbl_kit_peca.descricao,
                         tbl_kit_peca.kit_peca
                    FROM tbl_kit_peca
                    JOIN tbl_kit_peca_produto ON tbl_kit_peca_produto.kit_peca = tbl_kit_peca.kit_peca
                   WHERE tbl_kit_peca_produto.fabrica = $login_fabrica ";

                 if (!empty($produto)) {
                     $sql .=	" AND tbl_kit_peca_produto.produto = $produto";
                 }
                 if (strlen($descricao) > 0)  $sql .= " AND UPPER(TRIM(tbl_kit_peca.descricao))  LIKE UPPER(TRIM('%$descricao%'))";
                 if (strlen($referencia) > 0) $sql .= " AND UPPER(TRIM(tbl_kit_peca.referencia)) LIKE UPPER(TRIM('%$referencia%'))";
             }else if($login_fabrica == 3){
                 $sql = "SELECT DISTINCT tbl_kit_peca.referencia,
                         tbl_kit_peca.descricao,
                         tbl_kit_peca.kit_peca
                    FROM tbl_kit_peca
                    INNER JOIN tbl_kit_peca_peca using(kit_peca)
                    INNER JOIN tbl_peca on tbl_kit_peca_peca.peca = tbl_peca.peca
                    JOIN tbl_kit_peca_produto ON tbl_kit_peca_produto.kit_peca = tbl_kit_peca.kit_peca
                   WHERE tbl_kit_peca_produto.fabrica = $login_fabrica
                   ";
                 if (strlen($descricao) > 0)  {
                     $sql .= " AND ( UPPER(TRIM(tbl_peca.descricao))  LIKE UPPER(TRIM('%$descricao%')) OR ";
                 }

                 if (strlen($referencia) > 0) {
                     $sql .= " AND ( UPPER(TRIM(tbl_peca.referencia)) LIKE UPPER(TRIM('%$referencia%')) OR";
                 }

                if (strlen($descricao) > 0)  $sql .= " UPPER(TRIM(tbl_kit_peca.descricao))  LIKE UPPER(TRIM('%$descricao%')) )";
                if (strlen($referencia) > 0) $sql .= " UPPER(TRIM(tbl_kit_peca.referencia)) LIKE UPPER(TRIM('%$referencia%')) )";
                 if (!empty($produto)) {
                     $sql .=	" AND tbl_kit_peca_produto.produto = $produto";

                 }
             }

             $sql .= " ORDER BY tbl_kit_peca.descricao ";

             $res = pg_query($con,$sql);

             if (pg_num_rows($res) > 0) {

                 $kit_peca_sim = "sim";
                 echo "KIT de Peças";
                 if($login_fabrica == 3){

                     $kit_peca ='kit_peca_'.$linha_i;
                 }
                 echo "<table width='100%' border='1'>";

                 for ($i = 0; $i < pg_num_rows($res); $i++) {

                     $kit_peca_id    = pg_fetch_result($res, $i, 'kit_peca');
                     $descricao_kit  = pg_fetch_result($res, $i, 'descricao');
                     $referencia_kit = pg_fetch_result($res, $i, 'referencia');

                     echo "<tr>";
                     echo "<td>$referencia_kit</td>";
                     echo "<td>";
                     echo "<a href=\"javascript: ";
                     echo " window.opener.referencia.value='$referencia_kit'; window.opener.descricao.value='$descricao_kit'; ";
                     echo " window.opener.preco.value='';";
                     echo "kitPeca('$kit_peca_id','$kit_peca','$linha_i'); \">$descricao_kit</a>";
                     echo "</td>";
                     echo "</tr>";

                 }

                 echo "</table>";
                 echo "<br />";

             }

             if($login_fabrica == 3 && pg_num_rows($res) > 0){
                 $verificaExistePeca = "SELECT *
                                        FROM tbl_lista_basica
                                        INNER JOIN tbl_peca USING (peca)
                                        INNER JOIN tbl_produto ON tbl_lista_basica.produto = tbl_produto.produto
                                        WHERE tbl_lista_basica.fabrica = $login_fabrica AND ";
                 if (!empty($produto)) {
                     $verificaExistePeca .=	"tbl_lista_basica.produto = {$produto} AND";
                 }

                 if (strlen($descricao) > 0)  {
                     $verificaExistePeca .= " UPPER(TRIM(tbl_peca.descricao))  LIKE UPPER(TRIM('%$descricao%')) ";
                 }

                 if (strlen($referencia) > 0) {
                     $verificaExistePeca .= " UPPER(TRIM(tbl_peca.referencia)) LIKE UPPER(TRIM('%$referencia%'))";
                 }

                 $resVerificaExistePeca = pg_query($con,$verificaExistePeca);
                 if(pg_num_rows($resVerificaExistePeca) > 0){
                     $mostraApenasKit = false;
                 }else{
                     $mostraApenasKit = true;
                 }
             }else{
                 $mostraApenasKit = false;
             }
         }
    }else{
        if($login_fabrica == 30){
            $sql = "
                SELECT  tbl_kit_peca.referencia,
                        tbl_kit_peca.descricao,
                        tbl_kit_peca.kit_peca
                FROM    tbl_kit_peca
                WHERE   tbl_kit_peca.fabrica = $login_fabrica
            ";
            if (strlen($descricao) > 0)  $sql .= " AND UPPER(TRIM(tbl_kit_peca.descricao))  LIKE UPPER(TRIM('%$descricao%'))";
            if (strlen($referencia) > 0) $sql .= " AND UPPER(TRIM(tbl_kit_peca.referencia)) LIKE UPPER(TRIM('%$referencia%'))";

            $sql .= " ORDER BY tbl_kit_peca.descricao ";

            $res = pg_query($con,$sql);

            if (pg_num_rows($res) > 0) {

                $kit_peca_sim = "sim";
                $kit_peca ='kit_peca_'.$linha_i;
                echo "KIT de Peças";
                echo "<table width='100%' border='1'>";

                for ($i = 0; $i < pg_num_rows($res); $i++) {

                    $kit_peca_id    = pg_fetch_result($res, $i, 'kit_peca');
                    $descricao_kit  = pg_fetch_result($res, $i, 'descricao');
                    $referencia_kit = pg_fetch_result($res, $i, 'referencia');

                    echo "<tr>";
                    echo "<td>$referencia_kit</td>";
                    echo "<td>";
                    echo "<a href=\"javascript: ";
                    echo " window.opener.referencia.value='$referencia_kit'; window.opener.descricao.value='$descricao_kit'; ";
                    echo " window.opener.preco.value='';";
                    echo "kitPeca('$kit_peca_id','$kit_peca','$linha_i'); \">$descricao_kit</a>";
                    echo "</td>";
                    echo "</tr>";

                }

                echo "</table>";
                echo "<br />";
            }
        }
    }

 if ($tipo == "tudo" && !$mostraApenasKit) {

     $descricao = trim(strtoupper($_GET["descricao"]));

     echo "<h4>Pesquisando toda a lista básica do produto: <br><i>$produto_referencia - $produto_descricao</i></h4>";

     echo "<br><br>";

     if (strlen($produto) > 0) {
         $sql =	"SELECT distinct z.peca                                ,
                         z.referencia       AS peca_referencia ,
                         z.descricao        AS peca_descricao  ,
                         z.bloqueada_garantia                  ,
                         z.type                                ,
                         z.posicao                             ,
                         z.peca_fora_linha                     ,
                         z.de                                  ,
                         z.para                                ,
                         z.promocao_site                       ,
                         z.peca_para                           ,";
         if (strlen($produto) > 0 AND $login_fabrica <> 43 and $login_fabrica <> 96) {
             $sql .=	"	tbl_lbm.somente_kit                   ,";
         }
         $sql .=" tbl_peca.descricao AS para_descricao  ,
                         z.libera_garantia
                 FROM (
                         SELECT  y.peca               ,
                                 y.referencia         ,
                                 y.descricao          ,
                                 y.bloqueada_garantia ,
                                 y.type               ,
                                 y.posicao            ,
                                 y.peca_fora_linha    ,
                                 tbl_depara.de        ,
                                 tbl_depara.para      ,
                                 y.promocao_site                       ,
                                 tbl_depara.peca_para,
                                 y.libera_garantia
                         FROM (
                                 SELECT  x.peca                                      ,
                                         x.referencia                                ,
                                         x.descricao                                 ,
                                         x.bloqueada_garantia                        ,
                                         x.type                                      ,
                                         x.posicao                                   ,
                                         tbl_peca_fora_linha.peca AS peca_fora_linha,
                                         x.promocao_site                       ,
                                         tbl_peca_fora_linha.libera_garantia
                                 FROM (
                                         SELECT  tbl_peca.peca            ,
                                                 tbl_peca.referencia      ,
                                                 tbl_peca.descricao       ,
                                                 tbl_peca.bloqueada_garantia,
                                                 tbl_lista_basica.type    ,
                                                 tbl_peca.promocao_site                       ,
                                                 tbl_lista_basica.posicao
                                         FROM tbl_peca
                                         JOIN tbl_lista_basica ON tbl_peca.peca = tbl_lista_basica.peca AND tbl_lista_basica.fabrica=$login_fabrica
                                         $join_busca_referencia
                                         JOIN tbl_produto ON tbl_lista_basica.produto=tbl_produto.produto AND tbl_produto.fabrica_i=$login_fabrica ";
                                         if($login_fabrica == 20 AND $login_pais <>'BR') $sql .= "JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca AND tabela = $tabela ";
                                         $sql .= " WHERE tbl_peca.fabrica = $login_fabrica
                                         AND   tbl_produto.produto = $produto
                                         $sql_tipo_pedido
                                         AND   tbl_peca.ativo IS TRUE
                                         AND $cond_produto
                                         AND    tbl_peca.produto_acabado IS NOT TRUE";
                                         if ($login_fabrica == 14) $sql .= " AND tbl_lista_basica.ativo IS NOT FALSE";
                                         if (strlen($descricao) > 0) $sql .= " AND ( UPPER(TRIM(tbl_peca.descricao)) ILIKE UPPER(TRIM('%$descricao%')) OR UPPER(TRIM(tbl_peca.referencia)) ILIKE UPPER(TRIM('%$descricao%')) )";
                                         if ($item_aparencia == 'f') $sql .= " AND tbl_peca.item_aparencia IS FALSE";
                                         if ($login_fabrica==6 and strlen($serie)>0) {
                                             $sql .= " and tbl_lista_basica.serie_inicial < '$serie'
                                                       and tbl_lista_basica.serie_final > '$serie'";
                                         }
                                         $sql .= "					) AS x
                                 LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca AND tbl_peca_fora_linha.fabrica=$login_fabrica
                             ) AS y
                         LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca AND tbl_depara.fabrica=$login_fabrica
                     ) AS z
                 LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para AND tbl_peca.fabrica=$login_fabrica";
                 if (strlen($produto) > 0 AND $login_fabrica <> 43 and $login_fabrica <> 96) {
                      $sql .= " JOIN tbl_lista_basica AS tbl_lbm ON (tbl_lbm.peca = z.peca AND tbl_lbm.produto = $produto)
                                JOIN tbl_produto ON (tbl_produto.produto = tbl_lbm.produto AND tbl_produto.produto = $produto)";
                 }
                 $sql .= "ORDER BY";
                 if($login_fabrica == 45)$sql .= " z.referencia,";//14613 25/2/2008
                 $sql .= " z.descricao";
     }else{
         $sql = "SELECT distinct z.peca                                ,
                         z.referencia       AS peca_referencia ,
                         z.descricao        AS peca_descricao  ,
                         z.bloqueada_garantia                  ,
                         z.peca_fora_linha                     ,
                         z.de                                  ,
                         z.para                                ,
                         z.promocao_site                       ,
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
                                 y.promocao_site                       ,
                                 tbl_depara.peca_para,
                                 y.libera_garantia
                         FROM (
                                 SELECT  x.peca                                      ,
                                         x.referencia                                ,
                                         x.descricao                                 ,
                                         x.bloqueada_garantia                        ,
                                         tbl_peca_fora_linha.peca AS peca_fora_linha ,
                                         x.promocao_site                       ,
                                         tbl_peca_fora_linha.libera_garantia
                                 FROM (
                                         SELECT DISTINCT tbl_peca.peca       ,
                                                 tbl_peca.referencia ,
                                                 tbl_peca.descricao  ,
                                                 tbl_peca.promocao_site                       ,
                                                 tbl_peca.bloqueada_garantia
                                         FROM tbl_peca
                                         $join_busca_referencia";
                                         if($login_fabrica == 45) $sql .= " JOIN tbl_lista_basica ON tbl_lista_basica.peca = tbl_peca.peca AND tbl_lista_basica.fabrica = $login_fabrica ";
                                         if($login_fabrica == 20 AND $login_pais <>'BR') $sql .= "JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca AND tabela = $tabela ";
                                         $sql .= " WHERE tbl_peca.fabrica = $login_fabrica
                                         AND tbl_peca.ativo IS TRUE
                                         $sql_tipo_pedido
                                         AND tbl_peca.produto_acabado IS NOT TRUE";
         if (strlen($descricao) > 0) $sql .= " AND ( UPPER(TRIM(descricao)) ILIKE UPPER(TRIM('%$descricao%')) OR UPPER(TRIM(referencia)) ILIKE UPPER(TRIM('%$descricao%')) )";
         if ($item_aparencia == 'f') $sql .= " AND item_aparencia IS FALSE";
         if ($login_fabrica==6 and strlen($serie)>0) {
             $sql .= " and tbl_lista_basica.serie_inicial < '$serie'
                       and tbl_lista_basica.serie_final > '$serie'";
         }
         $sql .= "					) AS x
                                 LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca AND tbl_peca_fora_linha.fabrica=$login_fabrica
                             ) AS y
                         LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca AND tbl_depara.fabrica = $login_fabrica
                     ) AS z
                 LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para AND tbl_peca.fabrica = $login_fabrica
                 ORDER BY z.descricao";
     }

     $res = pg_query ($con,$sql);
     if (@pg_num_rows ($res) == 0) {
         echo "<h1>Nenhuma lista básica de peças encontrada para este produto</h1>";
         echo "<script language='javascript'>";
         echo "setTimeout('window.close()',10000);";
         echo "</script>";
         exit;
     }
 }

     echo "<div id='div_peca' style='display:none; Position:absolute; border: 1px solid #949494;width:410px; heigth:400px'>";

     echo "</div>";
 if ($tipo == "descricao" && !$mostraApenasKit) {
     $descricao = trim(strtoupper($_GET["descricao"]));

     echo "<h4>Pesquisando por <b>descrição da peça</b>: <i>$descricao</i></h4>";
     echo "<p>";


     if (strlen($produto) > 0 ) {
         $sql =	"SELECT distinct  z.peca                                ,
                         z.referencia       AS peca_referencia ,
                         z.descricao        AS peca_descricao  ,
                         z.bloqueada_garantia                  ,
                         z.type                                ,
                         z.posicao                             ,
                         z.peca_fora_linha                     ,
                         z.de                                  ,
                         z.promocao_site                       ,
                         z.para                                ,
                         z.peca_para                           ,";
         if (strlen($produto) > 0 AND $login_fabrica <> 43 and $login_fabrica <> 96) {
             $sql .=	"	tbl_lbm.somente_kit                   ,";
         }
         $sql .= " tbl_peca.descricao AS para_descricao  ,
                         z.libera_garantia
                 FROM (
                         SELECT  y.peca               ,
                                 y.referencia         ,
                                 y.descricao          ,
                                 y.bloqueada_garantia ,
                                 y.type               ,
                                 y.posicao            ,
                                 y.peca_fora_linha    ,
                                 tbl_depara.de        ,
                                 tbl_depara.para      ,
                                 y.promocao_site                       ,
                                 tbl_depara.peca_para ,
                                 y.libera_garantia
                         FROM (
                                 SELECT  x.peca                                      ,
                                         x.referencia                                ,
                                         x.descricao                                 ,
                                         x.bloqueada_garantia                        ,
                                         x.type                                      ,
                                         x.posicao                                   ,
                                         tbl_peca_fora_linha.peca AS peca_fora_linha,
                                         x.promocao_site                       ,
                                         tbl_peca_fora_linha.libera_garantia
                                 FROM (
                                         SELECT  tbl_peca.peca              ,
                                                 tbl_peca.referencia        ,
                                                 tbl_peca.descricao         ,
                                                 tbl_peca.bloqueada_garantia,
                                                 tbl_peca.promocao_site                       ,
                                                 tbl_lista_basica.type      ,
                                                 tbl_lista_basica.posicao
                                         FROM tbl_peca
                                         $join_busca_referencia
                                         JOIN tbl_lista_basica ON tbl_peca.peca = tbl_lista_basica.peca AND tbl_lista_basica.fabrica=$login_fabrica
                                         JOIN tbl_produto ON tbl_lista_basica.produto=tbl_produto.produto AND tbl_produto.fabrica_i=$login_fabrica ";
                                         if($login_fabrica == 20 AND $login_pais <>'BR') $sql .= "JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca AND tabela = $tabela ";
                                         $sql .= " WHERE tbl_peca.fabrica = $login_fabrica
                                         AND   tbl_produto.produto = $produto
                                         $sql_tipo_pedido
                                         AND   tbl_peca.ativo IS TRUE
                                         AND   tbl_peca.produto_acabado IS NOT TRUE
                                         AND   $cond_produto ";
                                         if ($login_fabrica == 14) $sql .= " AND tbl_lista_basica.ativo IS NOT FALSE";
                                         if (strlen($descricao) > 0) $sql .= " AND UPPER(TRIM(tbl_peca.descricao)) ILIKE UPPER(TRIM('%$descricao%'))";
                                         if ($item_aparencia == 'f') $sql .= " AND tbl_peca.item_aparencia IS FALSE";
                                         if ($login_fabrica==6 and strlen($serie)>0) {
                                             $sql .= " and tbl_lista_basica.serie_inicial < '$serie'
                                                       and tbl_lista_basica.serie_final > '$serie'";
                                         }
                                         $sql .= "					) AS x
                                 LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca AND tbl_peca_fora_linha.fabrica=$login_fabrica
                             ) AS y
                         LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca AND tbl_depara.fabrica = $login_fabrica
                     ) AS z
                 LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para AND tbl_peca.fabrica=$login_fabrica";
                                 if (strlen($produto) > 0 AND $login_fabrica <> 43 and $login_fabrica <> 96) {
                     $sql .= " JOIN tbl_lista_basica AS tbl_lbm ON (tbl_lbm.peca = z.peca AND tbl_lbm.produto = $produto)
                               JOIN tbl_produto ON (tbl_produto.produto = tbl_lbm.produto AND tbl_produto.produto = $produto)";
                }

				$sql .= " ORDER BY z.descricao ";
	}else{
		$sql =	"SELECT distinct z.peca                                ,
						z.referencia       AS peca_referencia ,
						z.descricao        AS peca_descricao  ,
						z.bloqueada_garantia                  ,
						z.peca_fora_linha                     ,
						z.de                                  ,
						z.para                                ,
                        z.promocao_site                       ,
						z.peca_para                           ,
						tbl_peca.descricao AS para_descricao ,
						z.libera_garantia
				FROM (
						SELECT  y.peca               ,
								y.referencia         ,
								y.descricao          ,
								y.bloqueada_garantia ,
								y.peca_fora_linha    ,
								tbl_depara.de        ,
								tbl_depara.para      ,
                                y.promocao_site      ,
								tbl_depara.peca_para ,
								y.libera_garantia
						FROM (
								SELECT  x.peca                                      ,
										x.referencia                                ,
										x.descricao                                 ,
										x.bloqueada_garantia                        ,
										tbl_peca_fora_linha.peca AS peca_fora_linha,
                                        x.promocao_site                       ,
										tbl_peca_fora_linha.libera_garantia
								FROM (
										SELECT DISTINCT tbl_peca.peca       ,
												tbl_peca.referencia ,
												tbl_peca.descricao  ,
                                                tbl_peca.promocao_site                       ,
												tbl_peca.bloqueada_garantia
										FROM tbl_peca
										$join_busca_referencia";
										if($login_fabrica == 45) $sql .= " JOIN tbl_lista_basica ON tbl_lista_basica.peca = tbl_peca.peca AND tbl_lista_basica.fabrica = $login_fabrica ";
										if($login_fabrica == 20 AND $login_pais <>'BR') $sql .= "JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca AND tabela = $tabela ";
										$sql .= " WHERE tbl_peca.fabrica = $login_fabrica
										AND   tbl_peca.ativo IS TRUE
										$sql_tipo_pedido
										AND   tbl_peca.produto_acabado IS NOT TRUE";
		if (strlen($descricao) > 0) $sql .= " AND UPPER(TRIM(descricao)) ILIKE UPPER(TRIM('%$descricao%'))";
		if ($item_aparencia == 'f') $sql .= " AND item_aparencia IS FALSE";
		if ($login_fabrica==6 and strlen($serie)>0) {
			$sql .= " and tbl_lista_basica.serie_inicial < '$serie'
					  and tbl_lista_basica.serie_final > '$serie'";
		}
		$sql .= "					) AS x
								LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca AND tbl_peca_fora_linha.fabrica=$login_fabrica
							) AS y
						LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca AND tbl_depara.fabrica=$login_fabrica
					) AS z
				LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para AND tbl_peca.fabrica=$login_fabrica
				ORDER BY z.descricao";
	}

     $res = pg_query($con,$sql);
	//echo nl2br($sql);
//if ($ip == '201.13.179.45') echo nl2br($sql);
	if (@pg_num_rows($res) == 0 and strlen($kit_peca_sim) == 0) {
		if ($login_fabrica == 1) {
			echo "<h2>Item '$descricao' não existe <br> para o produto $produto_referencia, <br> consulte a vista explodida atualizada <br> e verifique o código correto</h2>";
		}else{
			if($sistema_lingua == "ES") echo "Pieza '$descricao' no encuentrada <br>para el producto $produto_referencia";
			else                        echo "<h1>Peça '$descricao' não encontrada<br>para o produto $produto_referencia</h1>";
		}
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',10000);";
		echo "</script>";
		exit;
	}
}

if ($tipo == "referencia" && !$mostraApenasKit)  {

	$referencia = trim(strtoupper($_GET["peca"]));
	$referencia = str_replace(".","",$referencia);
	$referencia = str_replace(",","",$referencia);
	$referencia = str_replace("-","",$referencia);
	$referencia = str_replace("/","",$referencia);
	$referencia = str_replace(" ","",$referencia);

	if($login_fabrica == 30){
		$or_busca_referencia = " OR tbl_esmaltec_referencia_antiga.referencia_antiga LIKE '%$referencia%' ";
	}

	echo "<BR><font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>referência da peça</b>: <i>$referencia</i></font>";
	echo "<br><br>";

    	if(strlen($produto) > 0){
    		if($login_fabrica <> 3){
    			$join_campo = " z.type                       ,
    			z.posicao                             ,";
    		}

    		//if($login_fabrica == 3){
    		//	$sql = "(";
    		//}else{
    		//	$sql = "";
    		//}
    		$sql =	"SELECT distinct z.peca                                ,
    						z.referencia       AS peca_referencia ,
    						z.descricao        AS peca_descricao  ,
    						z.bloqueada_garantia                  ,
    						$join_campo
    						z.peca_fora_linha                     ,
    						z.de                                  ,
                            z.promocao_site                       ,
    						z.para                                ,
    						z.peca_para                           , ";
            if (strlen($produto) > 0 AND $login_fabrica <> 43 and $login_fabrica <> 96) {
                $sql .=	"	tbl_lbm.somente_kit                   ,";
            }
    		$sql .=" tbl_peca.descricao AS para_descricao  ,
    						z.libera_garantia
    				FROM (
    						SELECT  y.peca               ,
    								y.referencia         ,
    								y.descricao          ,
    								y.bloqueada_garantia ,
    								y.type               ,
    								y.posicao            ,
    								y.peca_fora_linha    ,
    								tbl_depara.de        ,
    								tbl_depara.para      ,
                                    y.promocao_site                       ,
    								tbl_depara.peca_para,
    								y.libera_garantia
    						FROM (
    								SELECT  x.peca                                      ,
    										x.referencia                                ,
    										x.descricao                                 ,
    										x.bloqueada_garantia                        ,
    										x.type                                      ,
    										x.posicao                                   ,
    										tbl_peca_fora_linha.peca AS peca_fora_linha,
                                            x.promocao_site                       ,
    										tbl_peca_fora_linha.libera_garantia
    								FROM (
    										SELECT  tbl_peca.peca              ,
    												tbl_peca.referencia        ,
    												tbl_peca.descricao         ,
    												tbl_peca.bloqueada_garantia,
    												tbl_lista_basica.type      ,
                                                    tbl_peca.promocao_site                       ,
    												tbl_lista_basica.posicao
    										FROM tbl_peca
    										$join_busca_referencia
    										JOIN tbl_lista_basica ON tbl_peca.peca=tbl_lista_basica.peca AND tbl_lista_basica.fabrica=$login_fabrica
    										JOIN tbl_produto ON tbl_lista_basica.produto=tbl_produto.produto AND tbl_produto.fabrica_i=$login_fabrica ";
    										if($login_fabrica == 20 AND $login_pais <>'BR') $sql .= "JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca AND tabela = $tabela ";
    										$sql .= " WHERE tbl_peca.fabrica = $login_fabrica
    										AND   tbl_produto.produto = $produto
    										$sql_tipo_pedido
    										AND   tbl_peca.produto_acabado IS NOT TRUE
    										AND   tbl_peca.ativo IS TRUE
    										AND   $cond_produto";
    										if ($login_fabrica == 14) $sql .= " AND tbl_lista_basica.ativo IS NOT FALSE";
    										if (strlen($referencia) > 0) $sql .= " AND (tbl_peca.referencia_pesquisa LIKE UPPER(TRIM('%$referencia%')) $or_busca_referencia)";
    										if ($item_aparencia == 'f') $sql .= " AND tbl_peca.item_aparencia IS FALSE";
    										if ($login_fabrica==6 and strlen($serie)>0) {
    											$sql .= " and tbl_lista_basica.serie_inicial < '$serie'
    													  and tbl_lista_basica.serie_final > '$serie'";
    										}
    										$sql .= "					) AS x
    								LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca AND tbl_peca_fora_linha.fabrica=$login_fabrica
    							) AS y
    						LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca AND tbl_depara.fabrica=$login_fabrica
    					) AS z
    				LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para AND tbl_peca.fabrica=$login_fabrica ";
                    if (strlen($produto) > 0 AND $login_fabrica <> 43 and $login_fabrica <> 96) {
                         $sql .= " JOIN tbl_lista_basica AS tbl_lbm ON (tbl_lbm.peca = z.peca AND tbl_lbm.produto = $produto)
                                   JOIN tbl_produto ON (tbl_produto.produto = tbl_lbm.produto AND tbl_produto.produto = $produto)";
                    }

    			    $sql .=" ORDER BY";
    				if($login_fabrica == 45)$sql .= " z.referencia,";//14613 25/2/2008
    				$sql .= " z.descricao";
        }else{
            $sql =  "SELECT distinct z.peca                                ,
                        z.referencia       AS peca_referencia ,
                        z.descricao        AS peca_descricao  ,
                        z.bloqueada_garantia                  ,
                        z.peca_fora_linha                     ,
                        z.de                                  ,
                        z.para                                ,
                        z.peca_para                           ,
                        z.promocao_site                       ,
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
                                y.promocao_site      ,
                                tbl_depara.peca_para ,
                                y.libera_garantia
                        FROM (
                                SELECT  x.peca                                      ,
                                        x.referencia                                ,
                                        x.descricao                                 ,
                                        x.bloqueada_garantia                        ,
                                        tbl_peca_fora_linha.peca AS peca_fora_linha,
                                        x.promocao_site                       ,
                                        tbl_peca_fora_linha.libera_garantia
                                FROM (
                                        SELECT  tbl_peca.peca              ,
                                                tbl_peca.referencia        ,
                                                tbl_peca.descricao         ,
                                                tbl_peca.promocao_site            ,
                                                tbl_peca.bloqueada_garantia
                                        FROM tbl_peca
                                        $join_busca_referencia";
                                        if($login_fabrica == 45) $sql .= " JOIN tbl_lista_basica ON tbl_lista_basica.peca = tbl_peca.peca AND tbl_lista_basica.fabrica = $login_fabrica ";
                                        if($login_fabrica == 20 AND $login_pais <>'BR') $sql .= "JOIN tbl_tabela_item using(peca) AND tabela = $tabela ";
                                        $sql .= " WHERE tbl_peca.fabrica = $login_fabrica
                                        AND tbl_peca.ativo IS TRUE
                                        $sql_tipo_pedido
                                        AND   tbl_peca.produto_acabado IS NOT TRUE";
            if (strlen($referencia) > 0) $sql .= " AND (referencia_pesquisa LIKE UPPER(TRIM('%$referencia%')) $or_busca_referencia)";
            if ($item_aparencia == 'f') $sql .= " AND item_aparencia IS FALSE";
            if ($login_fabrica==6 and strlen($serie)>0) {
                $sql .= " and tbl_lista_basica.serie_inicial < '$serie'
                          and tbl_lista_basica.serie_final > '$serie'";
            }
            $sql .= "                   ) AS x
                                    LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca AND tbl_peca_fora_linha.fabrica=$login_fabrica
                                ) AS y
                            LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca AND tbl_depara.fabrica=$login_fabrica
                        ) AS z
                    LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para AND tbl_peca.fabrica=$login_fabrica
                    ORDER BY";
                    if($login_fabrica == 45)$sql .= " z.referencia,";//14613 25/2/2008
                    $sql .= " z.descricao";
        }
	}

	//echo nl2br($sql);
	//exit;

	$res = @pg_query($con,$sql);

	//if ($ip == '200.228.76.93') echo $sql;
	if (@pg_num_rows($res) == 0) {
		if ($login_fabrica == 1) {
			echo "<h2>Item '$referencia' não existe <br> para o produto $produto_referencia, <br> consulte a vista explodida atualizada <br> e verifique o código correto</h2>";
		}else if(!$mostraApenasKit){
			$mensagem = "<h1>Peça '$referencia' não encontrada<br>para o produto $produto_referencia</h1>";

			if ($login_fabrica == 30 && ($descricao_tipo == 'REMESSA EM GARANTIA' || $descricao_tipo == 'REMESSA EM GAR DE COMPRESSOR')) {
				$sql = "SELECT peca, remessa_garantia, remessa_garantia_compressor FROM tbl_peca WHERE fabrica=30 AND referencia='{$referencia}'";
				$res_peca_existe = pg_query($con, $sql);

				if (pg_num_rows($res_peca_existe) > 0) {
					extract(pg_fetch_array($res_peca_existe));
					if ($descricao_tipo == 'REMESSA EM GARANTIA' && $remessa_garantia == 'f' || $descricao_tipo == 'REMESSA EM GAR DE COMPRESSOR' && $remessa_garantia_compressor == 'f') {
						$mensagem = "<h1>Peça não cadastrada para o tipo de pedido selecionado, favor entrar em contato com o Fabricante</h1>";
					}
				}
			}

			echo $mensagem;
		}
		echo "<script language='JavaScript'>";
		echo "setTimeout('window.close()',10000);";
		echo "</script>";
		exit;
	}


echo "<script language='JavaScript'>\n";
echo "<!--\n";
echo "this.focus();\n";
echo "// -->\n";
echo "</script>\n";

$contador = 999;
if(!$mostraApenasKit){
    for ( $i = 0 ; $i < pg_num_rows($res) ; $i++ ) {

        $peca            = trim(@pg_fetch_result($res,$i,peca));
        $peca_referencia = trim(@pg_fetch_result($res,$i,peca_referencia));

        if($login_fabrica == 30){
            $sql_ref = "SELECT referencia_antiga FROM tbl_esmaltec_referencia_antiga WHERE referencia = '$peca_referencia'";
            $res_ref = @pg_query($con,$sql_ref);
            $referencia_antiga = trim(@pg_fetch_result($res_ref,0,'referencia_antiga'));
        }

        $peca_descricao  = trim(@pg_fetch_result($res,$i,peca_descricao));
        $peca_descricao  = str_replace ('"','',$peca_descricao);
        $peca_descricao  = str_replace ("'","",$peca_descricao);
        $type            = trim(@pg_fetch_result($res,$i,type));
        $posicao         = trim(@pg_fetch_result($res,$i,posicao));
        $peca_fora_linha = trim(@pg_fetch_result($res,$i,peca_fora_linha));
        $peca_para       = trim(@pg_fetch_result($res,$i,peca_para));

        if($login_fabrica == 35){
            $po_peca       = trim(@pg_fetch_result($res,$i,promocao_site));
        }

        $para            = trim(@pg_fetch_result($res,$i,para));
        $somente_kit =  trim(@pg_fetch_result($res,$i,"somente_kit"));

        $para_descricao  = trim(@pg_fetch_result($res,$i,para_descricao));
        $bloqueada_garantia  = trim(@pg_fetch_result($res,$i,bloqueada_garantia));
        $libera_garantia  = trim(@pg_fetch_result($res,$i,libera_garantia));
        //--=== Tradução para outras linguas ============================= Raphael HD:1212
        $sql_idioma = "SELECT * FROM tbl_peca_idioma WHERE peca = $peca AND upper(idioma) = '$sistema_lingua'";

        $res_idioma = @pg_query($con,$sql_idioma);
        if (@pg_num_rows($res_idioma) >0) {
            $peca_descricao  = trim(@pg_fetch_result($res_idioma,0,descricao));
        }

        if ($login_fabrica == 3) {
            $sqlPA = "SELECT parametros_adicionais FROM tbl_peca WHERE fabrica = $login_fabrica AND peca = $peca";
            $resPA = pg_query($con, $sqlPA);

            unset($parametros_adicionais);
            unset($qtde_fotos);
            unset($serial_lcd);

            if (pg_num_rows($resPA) > 0) {
                $parametros_adicionais = pg_fetch_result($resPA, 0, "parametros_adicionais");

                $json = json_decode($parametros_adicionais, true);

                if ($json["qtde_fotos"] > 0) {
                    $qtde_fotos = $json["qtde_fotos"];
                } else {
                    $qtde_fotos = 0;
                }

                if (strlen($json["serial_lcd"]) > 0) {
                    $serial_lcd = $json["serial_lcd"];
                } else {
                    $serial_lcd = "f";
                }
            }
            if($peca_fora_linha > 0 && $_GET['tipo_pedido'] == 2 && $login_fabrica != 3){
                continue;
            }
        }

        $resT = pg_query($con,"SELECT tabela FROM tbl_tabela WHERE fabrica = $login_fabrica AND tbl_tabela.ativa IS TRUE");

		$contax=1;

        if(strlen($para) > 0) {
            for($xx=0;$xx<$contax;$xx++){
                $peca_parax= $peca_para;
                $sql_para="SELECT peca_para,para,(select descricao from tbl_peca where tbl_peca.peca = tbl_depara.peca_para) as descricao FROM tbl_depara join tbl_peca on tbl_peca.peca = tbl_depara.peca_de LEFT JOIN tbl_peca_fora_linha USING(peca) WHERE tbl_depara.fabrica = $login_fabrica AND peca_de = $peca_parax AND peca_fora_linha IS NULL";
                $res_para=pg_query($con,$sql_para);
                if(pg_num_rows($res_para) >0){
                    $peca_para       = trim(@pg_fetch_result($res_para,0,peca_para));
                    $para            = trim(@pg_fetch_result($res_para,0,para));
                    $para_descricao  = trim(@pg_fetch_result($res_para,0,descricao));
                    if(strlen($peca_parax) > 0 and $peca_parax <> $peca_para) {
                        $contax++;
                    }elseif(strlen($peca_parax) == 0){
                        $contax++;
                    }
                }
            }
        }

        if (in_array($login_fabrica, [3,6])) {
            $resT = pg_query($con,"SELECT tabela FROM tbl_posto_linha JOIN tbl_linha using(linha) WHERE posto = $login_posto AND fabrica = $login_fabrica limit 1");
            if(pg_num_rows($resT) <> 1){
                $resT = pg_query($con,"SELECT tabela FROM tbl_tabela WHERE fabrica = $login_fabrica AND tbl_tabela.ativa IS TRUE");
            }
        }

        /* IGOR - HD 9985 - 27-12-2007 - PARA MONDIAL*/
        if($login_fabrica == 5){
            $resT = pg_query($con,"SELECT tabela
							FROM tbl_tabela WHERE fabrica = $login_fabrica
								AND tbl_tabela.ativa IS TRUE
								AND tbl_tabela.tabela = 23");
        }
        if (pg_num_rows($resT) == 1) {
            $tabela = pg_fetch_result ($resT,0,0);
            if (strlen($para) > 0) {
                $sqlT = "SELECT preco FROM tbl_tabela_item WHERE tabela = $tabela AND peca = $peca_para";
            }else{
                $sqlT = "SELECT preco FROM tbl_tabela_item WHERE tabela = $tabela AND peca = $peca";
            }
            $resT = pg_query($con,$sqlT);
            if (pg_num_rows($resT) == 1) {
                $preco = number_format (pg_fetch_result($resT,0,0),2,",",".");
            }else{
                $preco = "";
            }
        }else{
            $preco = "";
        }


        if ($contador > 50) {
            $contador = 0 ;
            echo "</table><table width='100%' border='1'>\n";
            flush();
        }
        $contador++;

        $cor = '#ffffff';
        if (strlen($peca_fora_linha) > 0) $cor = '#FFEEEE';


        echo "<tr bgcolor='$cor'>\n";

        if($login_fabrica == 30){
            echo "<td><font size='1'>Ref. Ant.: $referencia_antiga</font></td>\n";
        }

        if ($login_fabrica == 14) {
            echo "<td nowrap>";
            echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'>$posicao</font>";
            echo "</td>\n";
        }


        if ($login_fabrica == 3) {
            $sql = "SELECT tbl_linha.codigo_linha FROM tbl_linha WHERE linha = (SELECT tbl_produto.linha FROM tbl_produto JOIN tbl_lista_basica ON tbl_produto.produto = tbl_lista_basica.produto WHERE tbl_lista_basica.peca = $peca LIMIT 1)";
            $resX = pg_query ($con,$sql);
            $codigo_linha = @pg_fetch_result ($resX,0,0);

            if (strlen ($codigo_linha) == 0) $codigo_linha = "&nbsp;";

            echo "<td nowrap>";
            echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#999999'>$codigo_linha</font>";
            echo "</td>\n";
        }

        echo "<td nowrap>";
        echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'>$peca_referencia</font>";
        echo "</td>\n";

        echo "<td nowrap>";

        if ((strlen($peca_fora_linha) > 0 OR strlen($para) > 0)) {
            if ($login_fabrica == 30){
                $sql_tabela  = "select tabela
							from tbl_posto_linha
							join tbl_linha on tbl_linha.linha = tbl_posto_linha.linha and tbl_linha.fabrica = $login_fabrica
							where tbl_posto_linha.posto = $login_posto LIMIT 1;";
                $res_tabela  = @pg_query($con,$sql_tabela);
                $tabela      = trim(@pg_fetch_result($res_tabela,0,tabela));

                if(strlen(trim($sql_tipo_pedido))>0) {
                    $sql_tabela  = "select tabela_posto
								from tbl_posto_linha
								join tbl_linha on tbl_linha.linha = tbl_posto_linha.linha and tbl_linha.fabrica = $login_fabrica
								where tbl_posto_linha.posto = $login_posto LIMIT 1;";
                    $res_tabela  = @pg_query($con,$sql_tabela);
                    $tabela_posto      = trim(@pg_fetch_result($res_tabela,0,tabela_posto));

                    $sql_preco = "SELECT ROUND(preco::NUMERIC, 2) AS preco FROM tbl_tabela_item where peca=$peca AND tabela=$tabela_posto";
                } else {
                    $sql_preco = "SELECT ROUND(preco::NUMERIC, 2) AS preco FROM tbl_tabela_item where peca=$peca AND tabela=$tabela";
                }

                $res_preco = @pg_query($con,$sql_preco);
                $preco     = trim(@pg_fetch_result($res_preco,0,preco));

            }
            if (strlen($para) > 0) {
                echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'>$peca_descricao</font>";
            }
            if (strlen($peca_fora_linha) > 0) {
                if($login_fabrica==3 && $libera_garantia=='t'){
                    echo '<a href="javascript: ';
                    echo "window.opener.referencia.value='$peca_referencia';window.opener.descricao.value='$peca_descricao '; ";
                    if (strlen($kit_peca) > 0) {
                        echo " window.opener.$('#$kit_peca').html('');";
                    }


					echo "if(window.opener.tela_pedido == false){window.opener.qtde_fotos.value = '$qtde_fotos';}";

					echo "if(window.opener.tela_pedido == false){window.opener.serial_lcd.value = '$serial_lcd';}";


                    echo "if(window.opener.tela_pedido == false){window.opener.verificaPA($linha_i,0);}";

                    echo ' window.close();"';

                    echo " style='font-family:Arial, Verdana, Times, Sans-Serif;font-size:10px;color:blue'>$peca_descricao</a>";
                }else{
                    echo $peca_descricao;
                }


            }else{
                if (strlen($para) > 0 ) {
                    if ($login_fabrica == 91 || $login_fabrica == 3 ){
                        if (isset($produto)) {
                            if (strlen($produto)>0 ) {
                                $query_prod = " AND tbl_kit_peca_produto.produto = $produto ";
                                $query_prod2 = " AND tbl_lista_basica.produto     = $produto ";
                            }else{
                                $query_prod2 = "";
                                $query_prod = "";
                            }
                        }else{
                                $query_prod2 = "";
                                $query_prod = "";
                            }

						$sql_kit = "SELECT tbl_kit_peca.referencia,
									   tbl_kit_peca.descricao,
									   tbl_kit_peca.kit_peca
								  FROM tbl_kit_peca_peca
								  JOIN tbl_peca              ON tbl_peca.peca                 = tbl_kit_peca_peca.peca
								  JOIN tbl_kit_peca          ON tbl_kit_peca.kit_peca         = tbl_kit_peca_peca.kit_peca
								  JOIN tbl_kit_peca_produto  ON tbl_kit_peca_produto.kit_peca = tbl_kit_peca.kit_peca
								  JOIN tbl_lista_basica      ON tbl_lista_basica.peca         = tbl_peca.peca
								 WHERE tbl_kit_peca_produto.fabrica = $login_fabrica
                                    $query_prod2
                                    $query_prod
								    AND tbl_peca.referencia          = '$para'
								    AND tbl_lista_basica.somente_kit is true;";

						$res_kit = pg_query($con, $sql_kit);
						$tot_kit = pg_num_rows($res_kit);
						if ($tot_kit > 0) {
							for ($yy = 0; $yy < $tot_kit; $yy++) {
								$kit_peca_kit = @pg_result($res_kit, $yy, 'kit_peca');
								$ref_kit      = @pg_result($res_kit, $yy, 'referencia');
								$des_kit      = @pg_result($res_kit, $yy, 'descricao');
                                $kit_peca = "kit_peca_".$linha_i;
								echo "<a href=\"javascript: ";
								echo " window.opener.referencia.value='$ref_kit'; window.opener.descricao.value=''; ";
								echo " window.opener.preco.value='';";
								echo "kitPeca('$kit_peca_kit','$kit_peca','$linha_i'); \">Mudou para: $para - $des_kit</a><br />";

							}

						}else{
                            //echo "window.opener.$('#$kit_peca').html('');";
							echo "<span style='font-family:Arial, Verdana, Times, Sans-Serif;size:10px;color:black;font-weight:bold'>Mudou Para</span>";
							echo " <a href=\"javascript: ";
							echo " window.opener.referencia.value='$para'; window.opener.descricao.value='$peca_descricao'; window.opener.preco.value='$preco'; ";

							if($login_fabrica == 125){
								echo "window.opener.peca_critica.value='$peca_critica';";
							}

							if ($login_fabrica == 30) {
								echo " if (window.opener.qtde) {window.opener.qtde.value='1';} ";
							}

							if (strlen($kit_peca) > 0) {
								echo "window.opener.$('#$kit_peca').html('');";
							}

							if ($login_fabrica == 91 || $login_fabrica == 3) {
								echo "window.parent.$('input[name=kit_kit_peca_".$input_posicao."]').val('');";
							}

							if ($login_fabrica == 3) {
								echo "if(window.opener.qtde_fotos != undefined){window.opener.qtde_fotos.value = '$qtde_fotos';}";
                                echo "if(window.opener.serial_lcd != undefined){window.opener.serial_lcd.value = '$serial_lcd';}";
								echo "window.opener.verificaPA($linha_i);";
							}

                            if($login_fabrica == 35){
                                echo "if(window.opener.tela_pedido == false){window.opener.verificaPOPeca('$po_peca', $linha_i);}";
                            }

							echo " window.close();";
							echo "\"style='font-family:Arial, Verdana, Times, Sans-Serif;size:10px;color:red'>$para</a>";
                        }
                    }
                }
            }
        }elseif($login_fabrica == 35 and $bloqueada_garantia == "t"){
            echo "<span style='font-family:Arial, Verdana, Times, Sans-Serif;font-size:10px;' >$peca_descricao</span>";
        }else{
            //HD92435 - paulo
            if ($login_fabrica == 30){
                $sql_tabela  = "select tabela
							from tbl_posto_linha
							join tbl_linha on tbl_linha.linha = tbl_posto_linha.linha and tbl_linha.fabrica = $login_fabrica
							where tbl_posto_linha.posto = $login_posto LIMIT 1;";
                $res_tabela  = @pg_query($con,$sql_tabela);
                $tabela      = trim(@pg_fetch_result($res_tabela,0,tabela));


                if(strlen(trim($sql_tipo_pedido))>0) {
                    $sql_tabela  = "select tabela_posto
								from tbl_posto_linha
								join tbl_linha on tbl_linha.linha = tbl_posto_linha.linha and tbl_linha.fabrica = $login_fabrica
								where tbl_posto_linha.posto = $login_posto LIMIT 1;";
                    $res_tabela  = @pg_query($con,$sql_tabela);
                    $tabela_posto      = trim(@pg_fetch_result($res_tabela,0,tabela_posto));

                    $sql_preco = "SELECT ROUND(preco::NUMERIC, 2) AS preco FROM tbl_tabela_item where peca=$peca AND tabela=$tabela_posto";
                } else {
                    $sql_preco = "SELECT ROUND(preco::NUMERIC, 2) AS preco FROM tbl_tabela_item where peca=$peca AND tabela=$tabela";
                }

                $res_preco = @pg_query($con,$sql_preco);
                $preco     = trim(@pg_fetch_result($res_preco,0,preco));


            }

            if($login_fabrica == 30 && strlen($os) == 0 && ($peca == 1378800 OR $peca_referencia == "9850002216")){ //hd_chamado=2682154
                echo "<a href=\"javascript: alert('Peça não substituível. \\n \\r Por favor enviar laudo de troca ao Inspetor responsável para análise.')\" " . "style='font-family:Arial, Verdana, Times, Sans-Serif;size:10px;color:#0000FF'>$peca_descricao</a>";
                exit;
            }

            //somente para pedido de pecas faturadas
            if ($login_fabrica == 30 && strlen($preco)==0 && strlen($os)==0) {
                # HD-2306611 echo "<a href=\"javascript: alert('Peça Bloqueada \\n \\r Em caso de dúvidas referente a código de peças que o Sistema não está aceitando, favor entrar em contato com a Fábrica através do Fone: (85) 3299-8992 ou por e-mail: pedidos.at@esmaltec.com.br.')\"><font face='Arial, Verdana, Times, Sans' size='1' color='#0000FF'>$peca_descricao</font></a>";
                echo "<a href=\"javascript: alert('Peça Bloqueada \\n \\r Em caso de dúvidas referente a código de peças que o Sistema não está aceitando, favor entrar em contato com a Fábrica. E-mail: sae@esmaltec.com.br')\"><font face='Arial, Verdana, Times, Sans' size='1' color='#0000FF'>$peca_descricao</font></a>";
                exit;
            }

            if($login_fabrica != 91){
                $verifica_kit = "t";
            }
            if ($login_fabrica == 3 && ($somente_kit == 't' AND $verifica_kit == "t" AND strlen($produto) > 0 AND strlen($peca) > 0 ) ) {

				$sql_kit = "SELECT tbl_kit_peca.referencia,
								   tbl_kit_peca.descricao,
								   tbl_kit_peca.kit_peca
							  FROM tbl_kit_peca_peca
							  JOIN tbl_peca              ON tbl_peca.peca                 = tbl_kit_peca_peca.peca
							  JOIN tbl_kit_peca          ON tbl_kit_peca.kit_peca         = tbl_kit_peca_peca.kit_peca
							  JOIN tbl_kit_peca_produto  ON tbl_kit_peca_produto.kit_peca = tbl_kit_peca.kit_peca
							  JOIN tbl_lista_basica      ON tbl_lista_basica.peca         = tbl_peca.peca
							 WHERE tbl_kit_peca_produto.fabrica = $login_fabrica
							   AND tbl_kit_peca_produto.produto = $produto
							   AND tbl_lista_basica.produto     = $produto
							   AND tbl_kit_peca_peca.peca       = $peca;";

				$res_kit = pg_query($con, $sql_kit);
				$tot_kit = pg_num_rows($res_kit);

				for ($yy = 0; $yy < $tot_kit; $yy++) {
					$kit_peca_kit = @pg_result($res_kit, $yy, 'kit_peca');
					$ref_kit      = @pg_result($res_kit, $yy, 'referencia');
					$des_kit      = @pg_result($res_kit, $yy, 'descricao');

                    $kit_peca = "kit_peca_".$linha_i;

					echo "<a id='teste' href=\"javascript: ";
           			echo " window.opener.referencia.value='$ref_kit'; window.opener.descricao.value='$des_kit'; ";
					echo " window.opener.preco.value=''; ";
                    echo "if(window.opener.tela_pedido == false){window.opener.qtde_fotos.value = '$qtde_fotos';}";
                    echo "if(window.opener.tela_pedido == false){window.opener.serial_lcd.value = '$serial_lcd';}";
                    echo "if(window.opener.tela_pedido == false){window.opener.verificaPA($linha_i);}";
					echo "kitPeca('$kit_peca_kit','$kit_peca','$linha_i'); \">$des_kit</a><br />";

				}

            }else{


                echo "<a href=\"javascript: window.opener.referencia.value='$peca_referencia'; window.opener.descricao.value='$peca_descricao';";


                if ($login_fabrica == 14) {
                    echo " window.opener.posicao.value='$posicao';";
                }else{
                    echo "window.opener.preco.value='$preco';";
                    if(in_array($login_fabrica, [3])){
                        echo "window.opener.qtde.value='1';"; 
                        echo "if (typeof window.opener.qtdechange == 'function') { window.opener.qtdechange($linha_i);} ";                     
                    }
                }
                if ($login_fabrica == 3) {
                    echo "if(window.opener.tela_pedido == false){window.opener.qtde_fotos.value = '$qtde_fotos';}";
                }

                if ($login_fabrica == 3) {
                    echo "if(window.opener.tela_pedido == false){window.opener.serial_lcd.value = '$serial_lcd';}";
                }

                if ($login_fabrica == 3) {
                    echo "if(window.opener.tela_pedido == false){window.opener.verificaPA($linha_i);}";
                }

                if($login_fabrica == 35){
                    echo "if(window.opener.tela_pedido == false){window.opener.verificaPOPeca('$po_peca', $linha_i);}";
                }

                echo " window.close(); window.opener.$('#kit_peca_{$linha_i}').html(' ');\"><font face='Arial, Verdana, Times, Sans' size='1' color='#0000FF'>$peca_descricao</font></a>";
            }

        }
        echo "</td>\n";

        if ($login_fabrica == 1) {
            echo "<td nowrap>";
            echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'>$type</font>";
            echo "</td>\n";
        }

        $sqlX =	"SELECT referencia, to_char(tbl_peca.previsao_entrega,'DD/MM/YYYY') AS previsao_entrega
			FROM tbl_peca
			WHERE referencia_pesquisa = UPPER('$peca_referencia')
			AND   fabrica = $login_fabrica
			AND   previsao_entrega NOTNULL;";
        $resX = pg_query($con,$sqlX);

        if (pg_num_rows($resX) == 0) {
            echo "<td nowrap>";
            if($login_fabrica == 35 and $bloqueada_garantia == 't'){
                echo "<font color='red'> Peça não atendida na garantia</font>";
            }

            if (strlen($peca_fora_linha) > 0) {
                echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'><b>";
                if ($login_fabrica == 1) {
                    echo "É obsoleta,<br>não é mais fornecida";
                }else{// AND $libera_bloqueado<>'t' HD 18085 23/4/2008
                    if($login_fabrica==3 AND $libera_garantia=='t'){
                        echo "Disponível somente para garantia.<br>Caso necessário, favor contatar <br> a Assistência Técnica Britânia";
                    }else{
                        echo "Fora de linha";
                        echo "<br />";
                        echo "<br />";
                        echo "Caro Posto autorizado, <br /><br />
					Esta peça está fora de linha, favor conferir se não há Boletins <br />
					Técnicos orientando o conserto com componentes da mesma, com o <br />
					uso de peça similar ou de conjunto onde a peça é utilizada. <br /> <br />
					Se necessário, contate o SAP para providenciar o atendimento desta OS.";
                    }
                }
                echo "</b></font>";
            }else{
                if (strlen($para) > 0) {
                    if ($login_fabrica == 3) {
                        $camposPA = "";


						$camposPA .= "if(window.opener.tela_pedido == false){ window.opener.qtde_fotos.value = '$qtde_fotos';}";

						$camposPA .= " if(window.opener.tela_pedido == false){window.opener.serial_lcd.value = '$serial_lcd';}";


                        $camposPA .= "if(window.opener.tela_pedido == false){window.opener.verificaPA($linha_i);} ";
                    }

                    if($login_fabrica == 35){
                        $camposPA .= "if(window.opener.tela_pedido == false){window.opener.verificaPOPeca('$po_peca', $linha_i);}";
                    }

                    echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'><b>Mudou Para:</b></font>";
                    echo " <a href=\"javascript: window.opener.referencia.value='$para'; window.opener.descricao.value='$para_descricao'; window.opener.preco.value='$preco'; $camposPA window.close();\"><font face='Arial, Verdana, Times, Sans' size='1' color='#0000FF'>$para</font></a>";
                }else{
                    echo "&nbsp;";
                }
            }
            echo "</td>\n";


            echo "<td nowrap align='right'>";
            $xpecas  = $tDocs->getDocumentsByRef($peca, "peca");
            if (!empty($xpecas->attachListInfo)) {

                $a = 1;
                foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
                    $fotoPeca = $vFoto["link"];
                    if ($a == 1){break;}
                }
                echo "<a href=\"javascript:mostraPeca('$fotoPeca', '$peca')\">";
                echo "<img src='$fotoPeca' border='0'>";
                echo "</a>";
            } else {
                if ($login_fabrica <> 30){
                    if ($dh = opendir($caminho."/pequena/") ) {
                        $contador=0;
                        while (false !== ($filename = readdir($dh))) {
                            if($contador == 1) break;

                            if (strpos($filename,$peca) !== false){
                                $xpeca = $peca.'.';

                                $po = strlen($xpeca);
                                if(substr($filename, 0,$po)==$xpeca){
                                    $contador++;
                                    echo "<a href=\"javascript:mostraPeca('$filename', '$peca')\">";
                                    echo "<img src='$caminho/pequena/$filename' border='0'>";
                                    echo "</a>";
                                }
                            }
                        }
                        if($contador == 0){
                            if ($dh = opendir($caminho."/pequena/")) {
                                $contador=0;
                                while (false !== ($filename = readdir($dh))) {
                                    if($contador == 1) break;
                                    if (strpos($filename,$peca_referencia) !== false){
                                        $contador++;
                                        $po = strlen($peca_referencia);
                                        if(substr($filename, 0,$po)==$peca_referencia){
                                            echo "<a href=\"javascript:mostraPeca('$filename', '$peca')\">";
                                            echo "<img src='$caminho/pequena/$filename' border='0'>";
                                            echo "</a>";
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            echo "</td>\n";

            //--=== Raphael HD: 1244 ==========================================
            if($login_fabrica == 3 AND $peca == '526199' ){
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan='4'align='center'><img src='imagens_pecas/526199.gif' class='Div' >";
                echo "</td>\n";

            }

        }else{
            echo "</tr>\n";
            echo "<tr>\n";
            $peca_previsao    = pg_fetch_result($resX,0,0);
            $previsao_entrega = pg_fetch_result($resX,0,1);

            $data_atual         = date("Ymd");
            $x_previsao_entrega = substr($previsao_entrega,6,4) . substr($previsao_entrega,3,2) . substr($previsao_entrega,0,2);
            echo "<td colspan='2'>\n";
            if ($data_atual < $x_previsao_entrega) {
                echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'><b>";
                echo "Esta peça estará disponível em $previsao_entrega";
                echo "<br>";
                echo "Para as peças com prazo de fornecimento superior a 25 dias, a fábrica tomará as medidas necessárias para atendimento do consumidor";
                echo "</b></font>";
            }
            echo "</td>\n";
        }

        echo "</tr>\n";

        if ($exibe_mensagem == 't' AND $bloqueada_garantia == 't' and $login_fabrica == 3){
            echo "<tr>\n";
            echo "<td colspan='5'>\n";
            echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'><b>";
            //echo "A peça $referencia necessita de autorização da Britânia para atendimento em garantia. Para liberação desta peça, favor enviar e-mail para <a href=\"mailto:assistenciatecnica@britania.com.br\">assistenciatecnica@britania.com.br</A>, informando a OS e a justificativa.";
            echo "A peça $referencia necessita de autorização da Britânia para atendimento em garantia.";
            echo "</b></font>";
            echo "</td>\n";
            echo "</tr>\n";
        }
        /* takashi alterou 05-04-2007 hd1819*/
        if (@pg_num_rows ($res) == 1 and $login_fabrica==24) {

            echo "<script language='JavaScript'>\n";
            echo "window.opener.referencia.value='$peca_referencia';";
            echo " window.opener.descricao.value='$peca_descricao';";
            echo "window.close();";
            echo "</script>\n";
        }
    }
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
