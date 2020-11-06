<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';

include_once "../class/tdocs.class.php";
$tDocs  = new TDocs($con, $login_fabrica);

if ($S3_sdk_OK) {
	include_once S3CLASS;

	$s3ve = new anexaS3('ve', (int) $login_fabrica);
	$S3_online = is_object($s3ve);
}

list($logo_fabrica, $url_fabrica) = @pg_fetch_array(@pg_query($con, "SELECT logo, site FROM tbl_fabrica WHERE fabrica = $login_fabrica"), 0);

if ($login_fabrica == 46 and $AWS_sdk_OK) { // Para a Telecontrol Net, usar logotipos desde o S3
	include_once AWS_SDK;
	$s3logo   = new AmazonS3();
	if (is_object($s3logo)) {
		$logoS3 = 'logos/' . $logo_fabrica;
		$bucket = 'br.com.telecontrol.posvenda-downloads';
		$logo_fabrica = ($usaLogoS3 = $s3logo->if_object_exists($bucket, $logoS3)) ? $s3logo->get_object_url($bucket, $logoS3) : 'logos/' . $logo_fabrica;
	}
}

//  MLG HD 384115 - Atlas pediu uma imagem diferenciada para o Menu Inicial do Posto.
if ($login_fabrica == 74) $logo = 'atlas_saa_anim.gif';

if ($logo_fabrica) {
	include '../fn_logoResize.php';

	if (!($AWS_sdk_OK and $login_fabrica == 46)) $logo_fabrica = "logos/$logo_fabrica";

	$attrLogo = logoSetSize($logo_fabrica, 240, 60);
	$infoFabrica = "<img src='$logo_fabrica' $attrLogo />";
}

function busca_arquivo($dir, $nome) {
	if ($dirlist = glob($dir . "$nome.*")) {
		return basename($dirlist[0]);
	}
	return false;
}

if ($login_fabrica == 1) {
    $arquivo_pdf = "<html><body>";
$produto = $_GET['produto'];
$depara_query = " (select referencia from tbl_peca where fabrica = {$login_fabrica} and peca = tbl_depara.peca_para) as peca_para, ";
$depara_join = "LEFT JOIN tbl_depara ON tbl_peca.peca = tbl_depara.peca_de ";
$indspl = "AND (upper(informacoes) != 'INDISPL' or informacoes is null)";

$sql = "SELECT tbl_lista_basica.ordem         ,
               tbl_lista_basica.posicao       ,
               tbl_lista_basica.qtde          ,
               tbl_peca.referencia            ,
               tbl_peca.descricao             ,
               tbl_peca.peca                  ,
               $depara_query
               case when tbl_lista_basica.garantia_peca > 0 then tbl_lista_basica.garantia_peca else tbl_peca.garantia_diferenciada end   AS desgaste         
          FROM tbl_lista_basica
          JOIN tbl_peca USING (peca)
          $depara_join
          WHERE tbl_lista_basica.fabrica = $login_fabrica
          $indspl
          AND tbl_lista_basica.produto = $produto 
          ORDER BY tbl_lista_basica.ordem";

$res = @pg_query($con,$sql);

//PEGA IMAGEM PRODUTO
$sql_comu = "SELECT DISTINCT(comunicado), tipo, extensao
               FROM tbl_comunicado
               LEFT JOIN tbl_comunicado_produto USING(comunicado)
              WHERE fabrica = $login_fabrica
                AND (tbl_comunicado.produto = $produto  OR tbl_comunicado_produto.produto = $produto)
                AND tipo IN ('Foto','Esquema Elétrico','Informativo','Informativo tecnico','Manual','Manual Técnico','Vista Explodida','Alterações Técnicas')
                AND ativo IS TRUE";

$res_comu = @pg_query($con,$sql_comu);

if (@pg_num_rows($res_comu) > 0) {
    $img = [];
    for ($i = 0; $i < pg_num_rows($res_comu); $i++) {
      $img[$i]['comunicado'] = pg_fetch_result($res_comu,$i,'comunicado');
      $img[$i]['tipo'] = pg_fetch_result($res_comu,$i,'tipo');
      $img[$i]['extensao'] = pg_fetch_result($res_comu,$i,'extensao');
    }
}

$destino = __DIR__ . "/comunicados/";
$caminho = "comunicados/";

$peca_abs_thumb = __DIR__ . "/imagens_pecas/$login_fabrica/pequena/";
$peca_rel_thumb = "imagens_pecas/$login_fabrica/pequena/";

$imagem = [];

foreach ($img as $key => $value) {

  $imagem[$key]['url'] = ($S3_online and $s3ve->temAnexos($value['comunicado'])) ? $s3ve->url : $caminho . busca_arquivo($destino, $value['comunicado']);
  $imagem[$key]['fn'] = $value['tipo']."_".$key.".".$value['extensao'];

  if ($imagem[$key]['url'] == $caminho) {
    unset($imagem[$key]);
  }
}

//MOSTRA TITULO DO PRODUTO
$sql_prod = "SELECT fn_retira_especiais(descricao) as descricao 
               FROM tbl_produto
              WHERE produto = $produto";

$res_prod = @pg_query($con,$sql_prod);

if (@pg_num_rows($res_prod) > 0) {
    $desc_produto = str_replace(" ", "_", pg_fetch_result($res_prod,0,'descricao').".pdf") ;
    $arquivo_pdf .= "<center>$infoFabrica</center>";
    $arquivo_pdf .= "<h2 align='center'>".pg_fetch_result($res_prod,0,'descricao')."</h2>";
}

if (count($imagem) > 0) {

  //verificar se existe e limpa , se não cria
  $tmp_dir = '../download/zip_vista';
  if (is_dir($tmp_dir)) {
    chdir($tmp_dir);
    $glob_arquivos = glob('*');
    foreach( $glob_arquivos as $f)
      unlink ($f);
    chdir ( __DIR__ );      
  }else{
    mkdir('../download/zip_vista',0777,true);
  }

  foreach ($imagem as $k => $v) {
    $fn = $v['fn'];
    $url = $v['url'];
    
    file_put_contents(
      "../download/zip_vista/$fn", 
      file_get_contents($url)
    );    
  }

  //$arquivo_pdf .= "<center><iframe id='pdf' name='pdf'  src='https://docs.google.com/viewer?url=".urlencode($imagem)."&embedded=true' width='900px' height='3600' scrolling='no' frameborder='0'></iframe></center>";
}

if (@pg_num_rows($res) > 0) {
    $arquivo_pdf .= "<table cellpadding='5' cellspacing='0' width='900px' border='1' align='center'>";        
        $arquivo_pdf .= "<tr bgcolor='#CCCCCC'>";
            $arquivo_pdf .= "<th>Posição</th>";
            $arquivo_pdf .= "<th>Peça</th>";
            $arquivo_pdf .= "<th>Referência</th>";
            $arquivo_pdf .= "<th>Qtde</th>";
            $arquivo_pdf .= "<th>Garantia da peça / Meses</th>";
        $arquivo_pdf .= "</tr>";

        for ($i = 0; $i < pg_num_rows($res); $i++) {
          
          $aux_posicao = pg_fetch_result($res,$i,'posicao');
          $aux_id_peca = pg_fetch_result($res,$i,'peca');
    
          if ($login_fabrica == 1 && empty($aux_posicao)){
            $aux_sql = "SELECT ordem FROM tbl_lista_basica WHERE fabrica = $login_fabrica AND produto = $produto AND peca = $aux_id_peca LIMIT 1";
            $aux_res     = pg_query($con, $aux_sql);
            $aux_posicao = pg_fetch_result($aux_res, 0, 0);
          }

            $cor = ($i % 2 == 0) ? '#FFFFFF' : '#EEEEEE';            
            $arquivo_pdf .= "<tr bgcolor='".$cor."'>";
            if ($login_fabrica == 1){
              $arquivo_pdf .= "<td align='center'>&nbsp;".$aux_posicao."</td>";  
            }else{
              $arquivo_pdf .= "<td align='center'>&nbsp;".pg_fetch_result($res,$i,'posicao')."</td>";
            }
            $arquivo_pdf .= "<td align='center'>&nbsp;".pg_fetch_result($res,$i,'referencia')."</td>";
            if (strlen(pg_fetch_result($res,$i,'peca_para')) > 0 and $login_fabrica == 1) {
                $descri_peca = pg_fetch_result($res,$i,'descricao')."<br>&nbsp;Mudou para: ".pg_fetch_result($res,$i,'peca_para');
            }else{
                $descri_peca = pg_fetch_result($res,$i,'descricao');
            }
            $arquivo_pdf .= "<td>&nbsp;".$descri_peca."</td>";
            $arquivo_pdf .= "<td align='center'>&nbsp;".pg_fetch_result($res,$i,'qtde')."</td>";
            
            $desgaste = pg_fetch_result($res, $i, desgaste);
            if (strlen(trim($desgaste)) == 0) {
                $sql_g = "SELECT garantia FROM tbl_produto where fabrica_i = {$login_fabrica} and produto = {$produto};";
                $res_g = pg_query($con, $sql_g);
                if (pg_num_rows($res_g) > 0) {
                    $desgaste_black = pg_fetch_result($res_g, 0, garantia);
                }                           
            }else{
                $desgaste_black = $desgaste;
            }
            $arquivo_pdf .= "<td align='center'>&nbsp;".$desgaste_black."</td>";
            $arquivo_pdf .= "</tr>";
        }
    $arquivo_pdf .= "</table>";

} else {

    $arquivo_pdf .= "<h2 align='center'>Nenhum registro encontrado!</h2>";

}
$arquivo_pdf .= "</body></html>";
$arquivo_pdf = utf8_encode( $arquivo_pdf);

//echo $arquivo_pdf;exit;
  //PDF
  //require "classes/mpdf/src/Mpdf.php";
  require "../classes/mpdf61/mpdf.php";
  
  $pdf = new mPDF;
  $pdf->SetDisplayMode('fullpage');

  $pdf->WriteHTML($arquivo_pdf);
  $desc_produto_pdf = str_replace(" ", "_", $desc_produto);
  $pdf->Output('../download/zip_vista/'.$desc_produto_pdf,'F');

  ob_end_flush();

  //Fazendo o zip dos dois arquivos
  if (count(glob($tmp_dir."/*"))) {
    // Black vai pedir o nome da familia... 
    $zipfile = "../download/zip_vista/Vista_explodida.zip";
    //ob_start para não mostrar o system na tela
    ob_start();
    chdir($tmp_dir);
    system("zip Vista_explodida.zip * ");
    chdir ( __DIR__ ); 
    ob_clean();
    //system("zip -DF $zipfile download/zip_vista/*");
  }
  //exit;

  ?>

  <script>
    window.open('../download/zip_vista/Vista_explodida.zip');

    setTimeout(function(){
      window.close();
    },3000);
    
  </script>
  <?php

  exit;
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
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
if ($login_fabrica == 1) {
    $depara_query = " (select referencia from tbl_peca where fabrica = {$login_fabrica} and peca = tbl_depara.peca_para) as peca_para, ";
    $depara_join = "LEFT JOIN tbl_depara ON tbl_peca.peca = tbl_depara.peca_de ";
}

$sql = "SELECT tbl_lista_basica.ordem         ,
               tbl_lista_basica.posicao       ,
               tbl_lista_basica.qtde          ,
               tbl_peca.referencia            ,
               tbl_peca.descricao             ,
               tbl_peca.peca                  ,
               $depara_query
               tbl_peca.garantia_diferenciada   AS desgaste         
          FROM tbl_lista_basica
          JOIN tbl_peca USING (peca)
          $depara_join
         WHERE tbl_lista_basica.fabrica = $login_fabrica
           AND tbl_lista_basica.produto = $produto 
         ORDER BY tbl_lista_basica.ordem";

$res = @pg_query($con,$sql);

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

$imagem = ($S3_online and $s3ve->temAnexos($img)) ? $s3ve->url : $caminho . busca_arquivo($destino, $img);

// Se não existe o arquivo no S3, tentar no diretório 'local'
if ($S3_online and $imagem == false)
	$imagem = $caminho . busca_arquivo($destino, $img);

// Se não existe arquivo no S3 nem no diretório local, $imagem = false
if ($imagem == $caminho)
	$imagem = false;

//MOSTRA TITULO DO PRODUTO
$sql_prod = "SELECT fn_retira_especiais(descricao) as descricao 
               FROM tbl_produto
              WHERE produto = $produto";

$res_prod = @pg_query($con,$sql_prod);

if (@pg_num_rows($res_prod) > 0) {
    
    if ($login_fabrica == 45 && date('Y-m-d') <= '2011-06-01') {

        echo "<center><img src='/assist/logos/nks20_anos.jpg' alt='$login_fabrica_site' border='0' height='40' /></center>";

    } else {

        echo "<center>$infoFabrica</center>";

    }

    echo '<h2 align="center">'.pg_fetch_result($res_prod,0,'descricao').'</h2>';

}

if ($imagem !== false && $tipo == 1) {

    echo '<center><img src="' . $imagem . '" border="0" /></center>';

} else if ($imagem !== false && $tipo == 2) {

    echo '<center><iframe id="pdf" name="pdf"  src="https://docs.google.com/viewer?url='.urlencode($imagem).'&embedded=true" width="900px" height="3600" scrolling="no" frameborder="0"></iframe></center>';

}

if (@pg_num_rows($res) > 0) {?>

    <table cellpadding="5" cellspacing="0" width="900px" border="1" align="center">
        <?php
        if ($login_fabrica == 1) {
            ?>
            <tr bgcolor="#CCCCCC">
                <th>Posição</th>
                <th>Peça</th>
                <th>Referência</th>
                <th>Qtde</th>
                <th>Garantia da peça / Meses</th>
            </tr>
            <?php
        }else{
            ?>
            <tr bgcolor="#CCCCCC">
                <th>Posição</th>
                <th>Ordem</th>
                <th>Peça</th>
                <th>Descrição</th>
                <th>Qtde</th>
                <th>Imagem</th>
            </tr>
            <?php
        }
        
        
        for ($i = 0; $i < pg_num_rows($res); $i++) {
            $cor = ($i % 2 == 0) ? '#FFFFFF' : '#EEEEEE';
            if ($login_fabrica == 1) {
                echo '<tr bgcolor="'.$cor.'">';
                    echo '<td align="center">&nbsp;'.pg_fetch_result($res,$i,'posicao').'</td>';
                    echo '<td align="center">&nbsp;'.pg_fetch_result($res,$i,'referencia').'</td>';
                    if (strlen(pg_fetch_result($res,$i,'peca_para')) > 0 and $login_fabrica == 1) {
                        $descri_peca = pg_fetch_result($res,$i,'descricao')."<br>&nbsp;Mudou para: ".pg_fetch_result($res,$i,'peca_para');
                    }else{
                        $descri_peca = pg_fetch_result($res,$i,'descricao');
                    }
                    echo '<td>&nbsp;'.$descri_peca.'</td>';
                    echo '<td align="center">&nbsp;'.pg_fetch_result($res,$i,'qtde').'</td>';
                    $desgaste = pg_fetch_result($res, $i, desgaste);
                    if (strlen(trim($desgaste)) == 0) {
                        $sql_g = "SELECT garantia FROM tbl_produto where fabrica_i = {$login_fabrica} and produto = {$produto};";
                        $res_g = pg_query($con, $sql_g);
                        if (pg_num_rows($res_g) > 0) {
                            $desgaste_black = pg_fetch_result($res_g, 0, garantia);
                        }                           
                    }else{
                        $desgaste_black = $desgaste;
                    }
                    echo '<td align="center">&nbsp;'.$desgaste_black.'</td>';                    
                echo '</tr>';
                
            }else{
                echo '<tr bgcolor="'.$cor.'">';
                    echo '<td align="center">&nbsp;'.pg_fetch_result($res,$i,'posicao').'</td>';
                    echo '<td align="center">&nbsp;'.pg_fetch_result($res,$i,'ordem').'</td>';
                    echo '<td align="center">&nbsp;'.pg_fetch_result($res,$i,'referencia').'</td>';
                    echo '<td>&nbsp;'.pg_fetch_result($res,$i,'descricao').'</td>';
                    echo '<td align="center">&nbsp;'.pg_fetch_result($res,$i,'qtde').'</td>';

                    $xpecas = $tDocs->getDocumentsByRef(pg_fetch_result($res,$i,'peca'), "peca");
                    if (!empty($xpecas->attachListInfo)) {

                      $a = 1;
                      foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
                          $fotoPeca = $vFoto["link"];
                          if ($a == 1){break;}
                      }
                          echo '<td><img src='.$fotoPeca.' border="0" /></td>';
                    } else {
                        $img_peca = busca_arquivo($peca_abs_thumb, pg_fetch_result($res,$i,'peca'));

                        if ($img_peca !== false) {
                            echo '<td><img src='.($peca_rel_thumb.$img_peca).' border="0" /></td>';
                        } else {
                            echo '<td>Sem Imagem</td>';
                        }
                    }
                echo '</tr>';
            }
            
        }?>
    </table>
    <center>
        <br />
        <?php
        if ($login_fabrica == 1) {?>
            <script type="text/javascript">
                $(function(){
                    window.print();
                });
            </script>
        <?php
        }
        ?>
        
        <a id="imprimir" href="javascript:window.print();">Clique aqui para imprimir</a>
        <br />
        <br />
    </center><?php

} else {

    echo '<h2 align="center">Nenhum registro encontrado!</h2>';

}?>
</body>
</html>
