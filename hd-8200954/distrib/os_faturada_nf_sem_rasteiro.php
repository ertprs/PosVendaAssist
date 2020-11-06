<?php 

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include '../funcoes.php';
include_once '../helpdesk/mlg_funciones.php';

$caminho = dirname(__DIR__);
$caminhoPerl = ($_serverEnvironment == 'development')
  ? "/home/lucas/public_html/Perl"
  : '/var/www/cgi-bin';
?> 
<style>
    
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

    $data_inicial = trim (strtoupper ($_POST['data_inicial']));
    $data_final   = trim (strtoupper ($_POST['data_final']));

    if(empty($data_inicial) && empty($data_final)){
        $msg_erro["msg"][] = " Informe uma data ";
    }

    if($data_inicial && $data_final){
        list($di, $mi, $yi) = explode("/", $data_inicial);
        if(!checkdate($mi,$di,$yi))
            $msg_erro["msg"][] = "Data Inválida";

        list($df, $mf, $yf) = explode("/", $data_final);
        if(!checkdate($mf,$df,$yf))
            $msg_erro["msg"][] = "Data Inválida";

        $aux_data_inicial = "$yi-$mi-$di";
        $aux_data_final = "$yf-$mf-$df";

        if(strlen($msg)==0){
            if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
                $msg_erro["msg"][] = "Data Inválida.";
            }
        }

    }else if(($data_inicial && !$data_final) || (!$data_inicial && $data_final)){
        $msg_erro["msg"][] = "Data Inválida.";
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
          $("#data_inicial").datepicker().mask("99/99/9999");
          $("#data_final").datepicker().mask("99/99/9999");
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
            <div class="titulo_tabela">Pesquisa</div>
              <div class="span12 row-fluid">   
                  <div class="span2"></div> 
                    <div class='span5 control-group <?=(in_array("fabrica", $msg_erro["campos"])) ? "error" : ""?>'>
                      <label class='control-label' for='fabrica'>Data Inicio</label>
                        <div class='controls controls-row'>
                          <div class='span10'>
                            <h5 class='asteristico'>*</h5>
                            <input class="span6" type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>" >
                          </div>
                        </div> 
                    </div> 
                    <div class='span4'>
                    <div class='control-group <?=(in_array("nota_fiscal", $msg_erro["campos"])) ? "error" : ""?>'>
                      <label class='control-label' for='data_final'>Data Final</label>
                        <div class='controls controls-row'>
                          <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                              <input class="span6" type="text" name="data_final" id="data_final" size="12" maxlength="10" value="<? if (strlen($data_final) > 0) echo $data_final;  ?>" >
                          </div>
                        </div>
                    </div>
                  </div>
                </div>
                <p>
                <input class='btn' type='submit' id="btn_click" name='btn_acao' value='Pesquisar' />
              </p>
            <br>
          </div>
        </div>
      </form>
<?php   
  if(!count($msg_erro["msg"]) > 0 && !empty($_POST)){
    $sql = "SELECT DISTINCT tbl_fabrica.nome as nome_fabrica, tbl_faturamento.faturamento, tbl_faturamento.emissao, tbl_pedido.pedido, to_char(tbl_embarque.data,'DD/MM/YYYY') as data_embarque, tbl_faturamento.nota_fiscal, tbl_os.os 
    from tbl_faturamento 
    join tbl_faturamento_item on tbl_faturamento_item.faturamento = tbl_faturamento.faturamento  
    join tbl_pedido_item on tbl_faturamento_item.pedido = tbl_pedido_item.pedido and tbl_pedido_item.peca = tbl_faturamento_item.peca 
    join tbl_pedido on tbl_pedido.pedido = tbl_pedido_item.pedido
    join tbl_peca on tbl_peca.peca = tbl_pedido_item.peca 
    join tbl_embarque_item on tbl_embarque_item.pedido_item = tbl_pedido_item.pedido_item 
    join tbl_embarque on tbl_embarque_item.embarque = tbl_embarque.embarque
    join tbl_fabrica on tbl_pedido.fabrica = tbl_fabrica.fabrica
    left join tbl_os_item on tbl_os_item.pedido_item = tbl_pedido_item.pedido_item
    left join tbl_os_produto on tbl_os_produto.os_produto = tbl_os_item.os_produto 
    left join tbl_os on tbl_os.os = tbl_os_produto.os
    where tbl_faturamento.fabrica = 10 
    and emissao between '$aux_data_inicial' and '$aux_data_final' 
    and tbl_faturamento.conhecimento isnull
    order by tbl_pedido.pedido ";
    $res = pg_query($con,$sql);
    if(pg_num_rows($res) > 0) {
      echo "<center>";
      echo "<form name='frm_nf_atendimento' id='frm_nf_atendimento' method='POST' action='' >";
      echo "<table width='850px' id='relatorio_listagem' name='relatorio_listagem' class='table table-striped table-bordered table-hover table-large'>";
      echo "<thead>"; 
        echo "<tr class = 'titulo_coluna'>";
          echo "<th align='center' width='10%' nowrap>Fábrica</th>";
          echo "<th align='center' width='10%' nowrap>Data do embarque</th>";
          echo "<th align='center' width='10%' nowrap>OS</th>"; 
          echo "<th align='center' width='10%' nowrap>Pedido</th>";
          echo "<th align='center' width='10%' nowrap>Nota fiscal</th>";
        echo "</tr>";

      function clean($str) {
        return str_replace(".", "", trim(strtoupper($str)));
      }

      $total_qtde = 0;

      for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
        $pedido             = pg_fetch_result($res, $i, 'pedido');
        $data_embarque     = substr(pg_fetch_result($res, $i, 'data_embarque'), 0, 10);
        $nota_fiscal = pg_fetch_result($res, $i, 'nota_fiscal');
        $os = pg_fetch_result($res, $i, 'os');
        $nome_fabrica = pg_fetch_result($res, $i, 'nome_fabrica');

        echo "<tr>";
          echo "<td class='tac' nowrap>$nome_fabrica</td>";
          echo "<td class='tac' nowrap>$data_embarque</td>";
          echo "<td class='tac'>$os</td>";
          echo "<td class='tac'>$pedido</td>";
          echo "<td class='tac'>$nota_fiscal</td>";
        echo "</tr>";
      }
      echo "</table>";
      echo "</form>";
      echo "</center>";
  
     ?> 
      
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
?>
 <div id="loading-block" style="width:100%;height:100%;position:fixed;left:0px;top:0px;text-align:center;vertical-align: middle;background-color:#000;opacity:0.3;display:none;z-index:10" >
 </div>
 <div id="loading"  >
  <img src="../imagens/loading_img.gif" style="z-index:11" />
  <input type="hidden" id="loading_action" value="f" />
  <div style="position:fixed;top:0;left:0;width:100%;height:100%;z-index:10000;"></div>
 </div> 
<?php 
 include "rodape.php"; ?>
  
  </body>
</html>



