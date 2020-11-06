<?php
  include "dbconfig.php";
  include "includes/dbconnect-inc.php";
  include 'autentica_usuario.php';
  include 'funcoes.php';
  include "javascript_calendario.php";

  $title = "Estatísticas de Técnico";
  include "cabecalho.php";

  $btn_acao = $_POST['btn_acao'];
  if(!empty($btn_acao)){
    
    $data_inicial = trim($_POST['data_inicial']);
    $data_final   = trim($_POST['data_final']);

    if(empty($data_inicial) OR empty($data_final))
        $msg_erro = "Data Inválida";

    if(empty($msg_erro)){
      list($df, $mf, $yf) = explode("/", $data_final);
      if(!checkdate($mf,$df,$yf)) 
        $msg_erro = "Data Inválida";
    }

    if(empty($msg_erro)){
      list($di, $mi, $yi) = explode("/", $data_inicial);
      if(!checkdate($mi,$di,$yi)) 
        $msg_erro = "Data Inválida";
    }

    if(empty($msg_erro)){
      $aux_data_inicial = "$yi-$mi-$di";
      $aux_data_final = "$yf-$mf-$df";

      if(strtotime($aux_data_final) < strtotime($aux_data_inicial) OR strtotime($aux_data_final) > strtotime('today')){
        $msg_erro = "Data Inválida";
      }
    }

    if(empty($msg_erro)){
      $aux_data_inicial = "$yi-$mi-$di";
      $aux_data_final = "$yf-$mf-$df";

      if(strtotime($aux_data_final) < strtotime($aux_data_inicial) OR strtotime($aux_data_final) > strtotime('today')){
        $msg_erro = "Data Inválida";
      }
    }

    if(empty($msg_erro)){
      if (strtotime($aux_data_inicial.'+2 month') < strtotime($aux_data_final) ) {
        $msg_erro = 'O intervalo entre as datas não pode ser maior que 2 mês';
      }else{
        $aux_data_inicial .=" 00:00:00";
        $aux_data_final .=" 23:59:59";
      }
    }
    
  }
?>
<style> 
    a {
        text-decoration: none;
        color: #000000;
    }

    a:hover {
        text-decoration: underline;
    }

    .titulo_coluna{
           background-color:#596d9b;
           font: bold 11px "Arial";
           color:#FFFFFF;
           text-align:center;
    }

   .titulo_tabela{
           background-color:#596d9b;
           font: bold 14px "Arial";
           color:#FFFFFF;
           text-align:center;
    }

    table.tabela tr td{
           font-family: verdana;
           font-size: 11px;
           border-collapse: collapse;
           border:1px solid #596d9b;
    }
    .formulario{
           background-color:#D9E2EF;
           font:11px Arial;
           text-align:left;
    }

    .formulario td{
      font-weight: bold;
    }

    .msg_erro {
        background: #FF0000;
        color: #FFFFFF;
        font: bold 16px "Arial";
        text-align:center;
        width: 700px;
        padding: 2px 0;
    }

    .texto_avulso{
       font: 14px Arial; color: rgb(89, 109, 155);
       background-color: #d9e2ef;
       text-align: center;
       width:700px;
       margin: 0 auto;
       border-collapse: collapse;
       border:1px solid #596d9b;
    }

    .nao_disponivel {
       font: 14px Arial; color: rgb(200, 109, 89);
       background-color: #ffddff;
       border:1px solid #DD4466;
    }

    .espaco{
       padding:0 0 0 150px;
    }

    input[type="text"] {
      font-weight: normal !important;
    }

    #linhas{
      width: 660px;
      margin: 0 auto;
    }

    #linhas ul, #linhas ul li{
      list-style: none;
      padding: 0;
      margin: 0;
    }

     #linhas ul li{
       float: left;
       width: 219px;
     }

     erro{
       display: none;
     }

</style>
  <script src="js/jquery-ui.min.js" type="text/javascript"></script>
  <script type="text/javascript">
    $(function() {
      $('#data_inicial').datePicker({startDate:'01/01/2000'});
      $('#data_final').datePicker({startDate:'01/01/2000'});
      $("#data_inicial").maskedinput("99/99/9999");
      $("#data_final").maskedinput("99/99/9999");
    });
  </script>


  <br />
  <?php 
    if(!empty($msg_erro)){
      echo "<div class='msg_erro'>{$msg_erro}</div>";
    }
  ?>
  <form name="frm_pesquisa" method="post" action="<?=$_SERVER['PHP_SELF']?>">
      <table cellpadding="2" cellspacing="1" width="700px" border="0" class="formulario" align="center">
          <tr class="titulo_tabela">
              <th colspan="4">Parâmetros de Pesquisa</th>
          </tr>
          <tr>
              <td width='233px'>&nbsp;</td>
              <td width='233px'>&nbsp;</td>
              <td width='233px'>&nbsp;</td>
              <td width='233px'>&nbsp;</td>
          </tr>
          <tr>            
            <td>&nbsp;</td>
            <td>
              Data Inicial <br />
              <input type="text" name="data_inicial" id="data_inicial" size="13" maxlength="10" value="<?php echo $data_inicial;?>" class="frm" />
            </td>
            <td>
              Data Final<br />
              <input type="text" name="data_final" id="data_final" size="13" maxlength="10" value="<?php echo $data_final;?>" class="frm" />
            </td>
            <td>&nbsp;</td>
          </tr>
          <tr>
            <td colspan="4" style='padding:15px; text-align: center'>
              <input type="submit" value="Pesquisar" name='btn_acao' />
            </td>
          </tr>
      </table>
  </form>

  <?

  if(!empty($btn_acao) AND empty($msg_erro)){
    
    $sql = "SELECT 
              tbl_tecnico.tecnico,
              tbl_tecnico.nome,
              tbl_tecnico.linhas,
              count(DISTINCT tbl_os.os) as produtos,
              count(tbl_os_item.peca) as pecas,
              (SUM(data_conserto::date - data_abertura)::float / count(DISTINCT tbl_os.os))::float AS tmat,
              (SELECT COUNT(tbl_os.os) FROM tbl_os WHERE tbl_os.os_reincidente AND tbl_os.tecnico = tbl_tecnico.tecnico AND tbl_os.data_conserto NOTNULL  AND tbl_os.data_fechamento NOTNULL  AND tbl_os.data_digitacao BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}') AS retorno
            FROM tbl_os 
              LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os AND tbl_os_produto.produto = tbl_os.produto
              LEFT JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
              JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_os.tecnico 
            WHERE 
              tbl_os.data_conserto NOTNULL
              AND tbl_os.data_fechamento NOTNULL
              AND tbl_os.posto = {$login_posto} 
              AND tbl_os.fabrica = {$login_fabrica} 
              AND tbl_os.data_digitacao BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'
            GROUP BY tbl_tecnico.tecnico,tbl_tecnico.nome,tbl_tecnico.linhas;";
    //exit(nl2br($sql));
    $res = pg_query($con, $sql);

    if(pg_num_rows($res)){
      $sql = "SELECT linha, nome INTO TEMP TABLE tmp_linha_$login_posto FROM tbl_linha WHERE fabrica = {$login_fabrica};";
      $res_linha = pg_query($con, $sql);

      echo "<table align='center' width='700px' border='0' cellpadding='0' cellspacing='1' class='tabela' >";

      echo "<tr class='titulo_coluna'>";
          echo "<td rowspan='2'>Técnico</td>";
          echo "<td rowspan='2'>Linhas Habilitadas</td>";
          echo "<td colspan='4'>Estatísticas de Produtos e Peças</td>";
      echo "</tr>"; 

      echo "<tr class='titulo_coluna'>";
          echo "<td>Produtos</td>";
          echo "<td>Retorno</td>";
          echo "<td>Peças</td>";
          echo "<td>TMAT</td>";
      echo "</tr>"; 

      for ($i=0; $i < pg_num_rows($res); $i++) { 
        extract(pg_fetch_array($res));

        //pegas as linha que o técnico atende e retorna tudo em array
        $linhas = preg_replace("/[{}]/", "", $linhas);
        $sql = "SELECT array(SELECT nome FROM tmp_linha_$login_posto WHERE linha IN ({$linhas}));";
        $res_linha = pg_query($con, $sql);
        $linhas = preg_replace("/[}{\"]/", "", pg_fetch_result($res_linha, 0));
        $linhas = str_replace(",", ", ", $linhas);

        $tmat = number_format($tmat,0,'.','');

        $cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
        echo "<tr bgcolor='$cor' id='{$tecnico}'>";
            echo "<td><a href='tecnico_cadastro_new.php?tecnico={$tecnico}' target='_blank'>&nbsp;{$nome}</a></td>";
            echo "<td>&nbsp;{$linhas}</td>";
            echo "<td align='center'>&nbsp;{$produtos}</td>";
            echo "<td align='center'>&nbsp;{$retorno}</td>";
            echo "<td align='center'>&nbsp;{$pecas}</td>";
            echo "<td align='center'>&nbsp;{$tmat}</td>";
        echo "</tr>";
      }
      echo "</table>";     
    } 
  }

  include "rodape.php";?>
</body>
</html>


