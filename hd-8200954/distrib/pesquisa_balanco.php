<?php 

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';
include_once '../helpdesk/mlg_funciones.php';

?> <style>
    
  .btn_excel {
    cursor: pointer;
    width: 185px;
    margin: 0 auto;
  }

  .btn_excel span {
    display: inline-block;
    float: none;
  }

  .btn_excel span img {
    width: 20px;
    height: 20px;
    border: 0px;
    vertical-align: middle;
  }

  .btn_excel span.txt {
    color: #FFF; 
    font-size: 14px;
    font-weight: bold;
    border-radius: 4px 4px 4px 4px;
    border-width: 1px;
    border-style: solid;
    border-color: #4D8530;
    background: -moz-linear-gradient(top, #559435 0%, #63AE3D 72%);
    background: -webkit-linear-gradient(top, #559435 0%, #63AE3D 72%);
    background: -o-linear-gradient(top, #559435 0%, #63AE3D 72%);
    background: -ms-linear-gradient(top, #559435 0%, #63AE3D 72%);
    background: linear-gradient(top, #559435 0%, #63AE3D 72%);
    filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#559435', endColorstr='#63AE3D',GradientType=1 );
    line-height: 18px;
    padding-right: 3px;
    padding-left: 3px;
  }
  
  </style> 

<?php

if(strlen($login_unico)>0 AND $login_unico_master <>'t'){
    if($login_unico_distrib_total <>'t') {
        echo "<center><h1>Você não tem autorização para acessar este programa!</h1><br><br><a href='javascript:history.back();'>Voltar</a></center>";
        exit;
    }
}
$plugins = array(
  "autocomplete",
  "datepicker",
  "shadowbox",
  "mask",
  "dataTable"
);

include("plugin_loader.php");

if($_POST["btn_acao"] == "Pesquisar"){
    $data_inicial       = $_POST['data_inicial'];
    $data_final         = $_POST['data_final'];
    $fabrica            = $_POST['fabrica'];

    if (!strlen($data_inicial) or !strlen($data_final)) {
      $msg_erro["msg"][]    = "Preencha os campos obrigatórios";
      $msg_erro["campos"][] = "data";
    } else {
      list($di, $mi, $yi) = explode("/", $data_inicial);
      list($df, $mf, $yf) = explode("/", $data_final);
        if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
          $msg_erro["msg"][]    = "Data Inválida";
          $msg_erro["campos"][] = "data";
        } else {
          $aux_data_inicial = "{$yi}-{$mi}-{$di}";
          $aux_data_final   = "{$yf}-{$mf}-{$df}";
            if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
              $msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
              $msg_erro["campos"][] = "data";
            }
        }
    }
}
?>
<html>
    <head>     
      <title><?php echo $title ?></title>
      <link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/bootstrap.css" />
      <link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/extra.css" />
      <link type="text/css" rel="stylesheet" media="screen" href="../css/tc_css.css" />
      <link media="screen" type="text/css" rel="stylesheet" href="../bootstrap/css/ajuste.css" />
      <link media="screen" type="text/css" rel="stylesheet" href="../plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
      <script type="text/javascript" src="../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
      <script type="text/javascript" src="../plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
      <script src='../plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.datepicker.min.js'></script>
      <script src='../plugins/jquery.mask.js'></script>
    
        <script type="text/javascript">
          $(function() {
          $.datepickerLoad(Array("data_final", "data_inicial"));
          });
        </script>  
    </head>
  <body>
    <? include 'menu.php' ?>
    <div class="container">
        <Br>
<?php
  if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
      <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>
      <div class="row">
        <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
      </div>
      <form id="frm" name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'> 
        <div id="frm_pesquisa_balanco" class="tc_formulario" >
            <div class="titulo_tabela">Pesquisar Balanços</div>
              <div class='span12 row-fluid'>
                <div class='span2'></div>
                  <div class='span4'>
                    <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                      <label class='control-label' for='data_inicial'>Data Inicial</label>
                        <div class='controls controls-row'>
                          <div class='span4'>
                            <h5 class='asteristico'>*</h5>
                              <input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
                          </div>
                        </div>
                    </div>
                  </div>
                  <div class='span2'></div>
                  <div class='span4'>
                    <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                      <label class='control-label' for='data_final'>Data Final</label>
                        <div class='controls controls-row'>
                          <div class='span4'>
                            <h5 class='asteristico'>*</h5>
                              <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
                          </div>
                        </div>
                    </div>
                  </div>
                  <div class='span2'></div>
              </div>  
                <div class="span12 row-fluid">   
                  <div class="span2"></div> 
                    <div class='span10 control-group <?=(in_array("familia", $msg_erro["campos"])) ? "error" : ""?>'>
                      <label class='control-label' for='familia'>Fábrica</label>
                        <div class='controls controls-row'>
                          <div class='span4'>
                            <select name='fabrica' id='fabrica'>
                              <option value=''>Selecionar</option>
                              <?  
                                //Se adicionar mais  uma fabrica aqui, colocar tambem no select de pesquisa da tela
                                $sql = "SELECT fabrica,nome 
                                        FROM tbl_fabrica 
                                        WHERE
                                        fabrica in ($telecontrol_distrib)
                                        ORDER BY nome";
                                $res = pg_query($con,$sql);
                                    if(pg_num_rows($res)>0){
                                     for($x = 0; $x < pg_num_rows($res);$x++) {
                                        $aux_fabrica = pg_fetch_result($res,$x,fabrica);
                                        $aux_nome    = pg_fetch_result($res,$x,nome);
                                        echo "<option value='$aux_fabrica'" ;if($fabrica==$aux_fabrica) echo "selected"; echo ">$aux_nome</option>";
                                      }
                                    } ?>
                                </select>
                          </div>
                        </div>
                    </div>
                </div>
              <div class="span12 row-fluid"></div>
              <p>
                <input class='btn' type='submit' id="btn_click" name='btn_acao' value='Pesquisar' />
              </p>
            <br>
          </div>
          <br>
        </div>
      </form>
<?php   
  if(!count($msg_erro["msg"]) > 0 && !empty($_POST)){
    $cond = (strlen($fabrica)>0) ? " tbl_peca.fabrica = '{$fabrica}' " :" 1 = 1 ";
    
    $sql = " SELECT  tbl_posto_estoque_acerto.posto_estoque_acerto,
            tbl_posto_estoque_acerto.qtde,
            tbl_posto_estoque_acerto.motivo,
            TO_CHAR(tbl_posto_estoque_acerto.data,'DD/MM/YYYY')AS data,
            tbl_peca.peca,
            tbl_posto.nome,
            tbl_peca.referencia,
            tbl_peca.descricao,
            tbl_fabrica.nome as fabrica,
            tbl_login_unico.nome as login_unico_nome
        FROM tbl_posto_estoque_acerto
        JOIN tbl_posto USING (posto)
        JOIN tbl_peca ON tbl_peca.peca = tbl_posto_estoque_acerto.peca
        JOIN tbl_login_unico USING (login_unico) 
        JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_peca.fabrica
        WHERE tbl_posto_estoque_acerto.data BETWEEN '{$data_inicial} 00:00:00.000' AND '{$data_final} 23:59:59.599'
        AND $cond
        AND motivo LIKE 'Localização DE:%'
        ORDER BY tbl_posto_estoque_acerto.data ASC";
    $res = pg_exec ($con,$sql);

    if(pg_numrows ($res) > 0) { 

      $hora = time();

      flush();

      $xlsdata = date ("d/m/Y H:i:s");

      system("rm /tmp/assist/pesquisa_balanco_".$login_posto."_data_".$hora.".csv");
      $fp = fopen ("/tmp/assist/pesquisa_balanco_".$login_posto."_data_".$hora.".csv","w");

      fputs ($fp,"Relatório Balanço de Estoque\n");

      $cabecalho = array();

     
      echo "<center>";
      echo "<table width='850px' id='relatorio_listagem' name='relatorio_listagem' class='table table-striped table-bordered table-hover table-large'>";
      echo "<thead>"; 
        echo "<tr class = 'titulo_coluna'>";
          echo "<th align='center' width='10%' nowrap>Fábrica</th>";
          $cabecalho[] = "Fábrica";
          echo "<th align='center' width='30%' nowrap>Peça</th>";
          $cabecalho[] = "Peça";
          echo "<th align='center' width='5%' nowrap>Qtde</th>";
          $cabecalho[] = "Qtde";
          echo "<th align='center' width='40%' nowrap>Motivo</th>";
          $cabecalho[] = "Motivo";
          echo "<th align='center' width='10%' nowrap>Data</th>";
          $cabecalho[] = "Data";
          echo "<th align='center' width='15%' nowrap>Admin</th>";
          $cabecalho[] = "Admin";
        echo "</tr>";

      fputs ($fp, implode(";", $cabecalho)."\n");


      function clean($str) {
        return str_replace(".", "", trim(strtoupper($str)));
      }

      $total_qtde = 0;

      for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
        $nome = pg_fetch_result($res, $i, fabrica);
        $login_unico_nome = pg_fetch_result($res, $i, login_unico_nome);
        $motivo = pg_fetch_result($res, $i, motivo);
        $peca = pg_fetch_result($res, $i, peca);
        $exp_motivo = explode(" ", $motivo);
        $arr_motivo = array_map("clean", $exp_motivo);
        $k = array_search("OS", $arr_motivo);

        if ($k !== false) {
          $v_os = $arr_motivo[$k + 1];
          $pexec = pg_execute($con, "check_os", array($v_os, $peca));
          if (pg_num_rows($pexec) > 0) {
            $vnf = trim(pg_fetch_result($pexec, 0, 'nota_fiscal'));
            if (in_array($vnf, $arr_nota_fiscal)) {
              continue;
            }
          }
        }
        $total_qtde += pg_result($res, $i, qtde);

       // strpos($motivo, "Localização")

        $linha = [];

        $cor = "#eeeeee";
        if (($i%2) == 0) $cor = '#cccccc';

        echo "<tr bgcolor='$cor'>";

      
        echo "<td align='center' title='Quantidade'>&nbsp;";
        echo $nome;
        $linha[] = $nome;
        echo "</td>";

        echo "<td align='center' title='Código da peça'>&nbsp;";
        echo pg_result ($res,$i,referencia) . " - " .pg_result ($res,$i,descricao);
        $linha[] = pg_result ($res,$i,descricao);
        echo "</td>";

        $qtde_fabrica = pg_result ($res,$i,qtde);
        if ($qtde_fabrica < 0) $qtde_fabrica = 0;

        echo "<td align='center' title='Quantidade'>&nbsp;";
        echo pg_result ($res,$i,qtde);
        $linha[] = pg_result ($res,$i,qtde);
        echo "</td>";

        echo "<td align='left' title='Motivo'>&nbsp;";
        echo nl2br($motivo);
        $linha[] = nl2br($motivo);
        echo "</td>";

        echo "<td align='center' title='Data'>&nbsp;";
        echo pg_result ($res,$i,data);
        $linha[] = pg_result ($res,$i,data);
        echo "</td>";

        echo "<td align='center' title='Admin'>&nbsp;";
        echo $login_unico_nome;
        $linha[] = $login_unico_nome;
        echo "</td>";

        echo "</tr>";

        fputs($fp, implode(";", $linha)."\n");
      }
      echo "
      <tr bgcolor='#663366' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>
      <td colspan='2'>TOTAIS</td>
      <td>$total_qtde</td>
      <td></td>
      <td></td>
      <td></td>
      </tr>";
      echo "</theader>";
      echo "</table>";
      echo "</center>";
    
      fclose ($fp);

      $data = date("Y-m-d").".".date("H-i-s");

      rename("/tmp/assist/pesquisa_balanco_".$login_posto."_data_".$hora.".csv","xls/relatorio-balanco-estoque-$login_posto-$data.csv");

     ?> 
      <center>     
        <div class="col-12">
          <div class="btn_excel" > 
            <a href='xls/relatorio-balanco-estoque-<?=$login_posto?>-<?=$data?>.csv' target='_blank'>       
              <span class="txt" style="background: #5e9c76;">Gerar Arquivo CSV</span>
              <span><img style="width:40px ; height:40px;" src="../imagens/icon_csv.png"></span>
            </a>
          </div>
        </div>
      </center>
  <?php 

    }else{
      echo '
      <div class="container">
        <div class="alert">
          <h4>Nenhum resultado encontrado</h4>
        </div>
      </div>';
    }
  } 

 include "rodape.php"; ?>
  
  </body>
</html>



