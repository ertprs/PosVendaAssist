<?php 

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include '../funcoes.php';
include_once '../helpdesk/mlg_funciones.php';


$plugins = array(
  "autocomplete",
  "datepicker",
  "shadowbox",
  "mask",
  "dataTable"
);

include("plugin_loader.php");

if($_POST["btn_acao"] == "Pesquisar"){

    $data_inicial  = $_POST['data_inicial'];
    $data_final  = $_POST['data_final'];
    $numero_embarque  = $_POST['numero_embarque'];
    $cnpj_posto  = $_POST['cnpj_posto'];
    $fabrica      = (int)$_POST["fabrica"];

    $somente_log  = $_POST["somente_log"];

    if($somente_log != "sim"){
      $condFaturar = " and faturar is not null  ";
    }

    if($fabrica > 0){
      $condFabrica = " AND tbl_embarque.fabrica = $fabrica"; 
    }

    if($numero_embarque > 0){
      $condEmbarque = " AND tbl_embarque.embarque = $numero_embarque ";
    }

    if($cnpj_posto > 0 ){
      $condCnpj = "AND tbl_posto.cnpj = '$cnpj_posto' ";
    }


  if(!$data_inicial AND !$data_final AND $fabrica == 0 AND strlen(trim($numero_embarque))==0 AND strlen(trim($cnpj_posto))==0){
    $msg_erro['msg'] = "Informe um parametro para pesquisa.";
    $msg_erro["campos"][] = "data_inicial";
    $msg_erro["campos"][] = "data_final";
    $msg_erro["campos"][] = "fabrica";
    $msg_erro["campos"][] = "numero_embarque";
    $msg_erro["campos"][] = "cnpj_posto";
  }

  //Início Validação de Datas
    if($data_inicial) {
      list($di, $mi, $yi) = explode("/", $data_inicial); //tira a barra
      if(!checkdate($mi, $di, $yi)) {
        $msg_erro['msg'] = "Data Inválida";
        $msg_erro["campos"][] = "data_inicial";
      }
    }
    if($data_final) {
      list($df, $mf, $yf) = explode ("/", $data_final );//tira a barra
      if(!checkdate($mf, $df, $yf)) {
        $msg_erro['msg'] = "Data Inválida";
        $msg_erro["campos"][] = "data_final";
      }
    }
    if(!$msg_erro) {
      $nova_data_inicial = strtotime("$yi-$mi-$di");
      $nova_data_final   = strtotime("$yf-$mf-$df");

      if($nova_data_final < $nova_data_inicial){
        $msg_erro['msg'] = "Data Inválida.";
        $msg_erro["campos"][] = "data_inicial";
        $msg_erro["campos"][] = "data_final";
      }
      //Fim Validação de Datas

      if(strlen($data_inicial)>0 and strlen($data_final)>0){
        list($di, $mi, $yi) = explode("/", $data_inicial);
        $xdata_inicial = "$yi-$mi-$di"; 

        list($df, $mf, $yf) = explode("/", $data_final);
        $xdata_final = "$yf-$mf-$df";
        $condData = " and tbl_embarque.data BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59' ";
      }
    }
  
  if(count($msg_erro)==0){

    $sql_principal = "SELECT tbl_embarque.embarque, tbl_embarque.fabrica, tbl_embarque.faturar, tbl_posto_fabrica.codigo_posto, tbl_posto.nome as nome_posto  FROM tbl_embarque 
                        JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_embarque.posto and tbl_posto_fabrica.fabrica = tbl_embarque.fabrica
                        JOIN tbl_posto on tbl_posto.posto = tbl_posto_fabrica.posto
                      WHERE 
                       1 = 1
                       $condFaturar
                      $condData
                      $condFabrica
                      $condEmbarque
                      $condCnpj
                       order by embarque desc ";
    $res_principal = pg_query($con, $sql_principal);
  }
}
?>
<html>
    <head>     
      <title><?php echo $title ?></title>
      <link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/bootstrap.css" />
      <link type="text/css" rel="stylesheet" media="screen" href="../plugins/shadowbox_lupa/shadowbox.css" />
      <link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/extra.css" />
      <link type="text/css" rel="stylesheet" media="screen" href="../css/tc_css.css" />
      <link media="screen" type="text/css" rel="stylesheet" href="../bootstrap/css/ajuste.css" />
      <link media="screen" type="text/css" rel="stylesheet" href="../plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
      <script type="text/javascript" src="../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
      <script type="text/javascript" src="../plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
      <script src='../plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.datepicker.min.js'></script>
      <script src='../plugins/jquery.mask.js'></script>
      <script src='../plugins/shadowbox_lupa/shadowbox.js'></script>
    
        <script type="text/javascript">
          $(function() {
            $.datepickerLoad(Array("data_final", "data_inicial"));
            Shadowbox.init();

            $(".btn_estornar").click(function(){
                var embarque = $(this).data('embarque');
                var fabrica = $(this).data('fabrica');

                Shadowbox.open({
                  content:    "motivo_estorno.php?embarque="+embarque+"&fabrica="+fabrica,
                    player:   "iframe",
                    title:    "Estorno",
                    width:    870,
                    height:   250
                });        
            });

            $(".btn_log").click(function(){
                var embarque = $(this).data('embarque');
                var fabrica = $(this).data('fabrica');

                Shadowbox.open({
                  content:    "log_estorno.php?embarque="+embarque+"&fabrica="+fabrica,
                    player:   "iframe",
                    title:    "Visualizar Log Estorno",
                    width:    870,
                    height:   500
                });        
            });

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
      <h4><?= $msg_erro["msg"]?></h4>
    </div>
<?php
}
?>
      <div class="row">
        <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
      </div>
      <form id="frm" name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'> 
        <div id="frm_pesquisa_balanco" class="tc_formulario" >
            <div class="titulo_tabela">Pesquisar</div>
              <br>
              <div class="span12 row-fluid">   
                <div class="span2"></div> 
                  <div class='span4'>
                      <div class='control-group <?=(in_array("data_inicial", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='data_inicial'>Data Inicial</label>
                          <div class='controls controls-row'>
                            <div class='span8'>
                              <h5 class='asteristico'>*</h5>
                                <input type="text" name="data_inicial" id="data_inicial" maxlength="10" class='span12' value="<?=$data_inicial?>" >
                            </div>
                          </div>
                      </div>
                  </div>
                  <div class='span4'>
                      <div class='control-group <?=(in_array("data_final", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='data_final'>Data Final</label>
                          <div class='controls controls-row'>
                            <div class='span8'>
                              <h5 class='asteristico'>*</h5>
                                <input type="text" name="data_final" id="data_final" maxlength="10" class='span12' value="<?=$data_final?>" >
                            </div>
                          </div>
                      </div>
                  </div>
                <div class="span2"></div> 
              </div>
              <div class="span12 row-fluid">   
                  <div class="span2"></div> 
                    <div class='span4 control-group <?=(in_array("fabrica", $msg_erro["campos"])) ? "error" : ""?>'>
                      <label class='control-label' for='fabrica'>Fábrica</label>
                        <div class='controls controls-row'>
                          <div class='span8'>
                            <h5 class='asteristico'>*</h5>
                            <select name='fabrica' id='fabrica' class='span12'>
                              <option value=''>Selecionar</option>
                              <?  
                                //Se adicionar mais  uma fabrica aqui, colocar tambem no select de pesquisa da tela
                                //usar depois $telecontrol_distrib
                                $sql = "SELECT fabrica,nome 
                                        FROM tbl_fabrica 
										WHERE parametros_adicionais ~'telecontrol_distrib'
										and ativo_fabrica
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
                    <div class='span4'>
                    <div class='control-group <?=(in_array("numero_embarque", $msg_erro["campos"])) ? "error" : ""?>'>
                      <label class='control-label' for='data_final'>Número do Embarque</label>
                        <div class='controls controls-row'>
                          <div class='span8'>
                            <h5 class='asteristico'>*</h5>
                              <input type="text" name="numero_embarque" id="numero_embarque" size="12" maxlength="12" class='span12' value="<?=$numero_embarque?>" >
                          </div>
                        </div>
                    </div>
                  </div>
                </div>
                <div class="span12 row-fluid">   
                  <div class="span2"></div> 
                  <div class='span4'>
                    <div class='control-group <?=(in_array("cnpj_posto", $msg_erro["campos"])) ? "error" : ""?>'>
                      <label class='control-label' for='data_final'>CNPJ do Posto</label>
                        <div class='controls controls-row'>
                          <div class='span8'>
                            <h5 class='asteristico'>*</h5>
                              <input type="text" name="cnpj_posto" id="cnpj_posto" size="12" maxlength="14" class='span12' value="<?=$cnpj_posto?>" >
                          </div>
                        </div>
                    </div>
                  </div>
                  <div class="span4">
                    <div class='control-group <?=(in_array("cnpj_posto", $msg_erro["campos"])) ? "error" : ""?>'>
                      <label class='control-label' for='data_final'>Somente Log</label>
                        <div class='controls controls-row'>
                          <div class='span8'>
                              <input type="checkbox" name="somente_log" id="somente_log" value="sim" >
                          </div>
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
        
      </form>
    </div>
<?php   

if(pg_num_rows($res_principal)>0){
    echo "<div class='container-fluid'> 
    <div class='row-fluid'>";
    echo "<table  id='relatorio_listagem' name='relatorio_listagem' class='table table-striped table-bordered table-hover'>";
        echo "<thead>"; 
          echo "<tr class = 'titulo_coluna'>";
            echo "<th align='center' width='10%' nowrap>Nº Embarque</th>";
            echo "<th align='center' width='10%' nowrap>Posto</th>";
            echo "<th align='center' width='10%' nowrap>Peça</th>";
            if($somente_log != 'sim'){
              echo "<th align='center' width='10%' nowrap>Faturado</th>";
            }
            echo "<th align='center' width='10%' nowrap>Ação</th>";
          echo "</tr>";
        echo "<thead>";
        echo "<tbody>";

        for($i=0; $i<pg_num_rows($res_principal); $i++){
          $embarque = pg_fetch_result($res_principal, $i, 'embarque');
          $faturar  = substr(mostra_data(pg_fetch_result($res_principal, $i, 'faturar')),0,16);
          $nome_posto  = substr(pg_fetch_result($res_principal, $i, 'nome_posto'),0,60);
          $codigo_posto  = pg_fetch_result($res_principal, $i, 'codigo_posto');
          $fabrica  = pg_fetch_result($res_principal, $i, 'fabrica');


          $sql_pecas = "SELECT * FROM tbl_embarque_item 
                        JOIN tbl_peca on tbl_peca.peca = tbl_embarque_item.peca
                        where embarque = $embarque";
          $res_peca = pg_query($con, $sql_pecas);
          $pecas_embarque = "";
          for($p = 0; $p<pg_num_rows($res_peca); $p++){
              $peca = pg_fetch_result($res_peca, $p, peca);
              $referencia  = pg_fetch_result($res_peca, $p, referencia);
              $descricao  = pg_fetch_result($res_peca, $p, descricao);

              $pecas_embarque .= "$referencia - $descricao <Br>";
          }

       
          echo "<tr>";
            echo "<td class='tac'>$embarque</td>";
            echo "<td nowrap>$codigo_posto - $nome_posto </td>";
            echo "<td nowrap>$pecas_embarque</td>";
            if($somente_log != 'sim'){
              echo "<td class='tac'>$faturar</td>";
            }

            if($somente_log == 'sim'){
              echo "<td class='tac'><button class='btn btn-primary btn_log' data-embarque='$embarque' data-fabrica='$fabrica'>Visualizar Log</button></td>";
            }else{
              echo "<td class='tac'><button class='btn btn-danger btn_estornar' data-embarque='$embarque' data-fabrica='$fabrica'>Estornar</button></td>";  
            }
            
          echo "</tr>";

        }

        echo "</tbody>";


}
   
    echo "</table>";
  echo "</div></div>";
 include "rodape.php"; ?>
  
  </body>
</html>




