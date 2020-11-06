<?php 

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';
include_once '../helpdesk/mlg_funciones.php';

$caminho = dirname(__DIR__);
$caminhoPerl = ($_serverEnvironment == 'development')
  ? "/home/lucas/public_html/Perl"
  : '/var/www/cgi-bin';

if($_POST['verifica_os_fabrica'] == true){
  $num_os     = $_POST["os"];
  $fabrica    = $_POST["fabrica"];
  $peca       = $_POST["peca"];

  $sql = "SELECT tbl_os.os FROM tbl_os 
          join tbl_os_produto on tbl_os_produto.os = tbl_os.os
          join tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto
          where tbl_os.os = $num_os and tbl_os.fabrica = $fabrica and tbl_os_item.peca = $peca ";
  $res = pg_query($con, $sql);
  if(pg_num_rows($res)==0){
      echo "Ordem de Serviço não encontrada para essa fábrica ou peça não pertence a essa ordem de serviço.";
  }
  exit;
}

if($_POST['verifica_pedido_fabrica'] == true){
  $pedido     = $_POST["pedido"];
  $fabrica    = $_POST["fabrica"];
  $peca       = $_POST["peca"];

  $sql = "SELECT tbl_pedido_item.peca, tbl_pedido.pedido 
          from tbl_pedido 
          inner join tbl_pedido_item on tbl_pedido.pedido = tbl_pedido_item.pedido 
          where tbl_pedido.pedido = $pedido 
          and tbl_pedido_item.peca = $peca 
          and tbl_pedido.fabrica = $fabrica ";
  $res = pg_query($con, $sql);
  if(pg_num_rows($res)==0){
      echo "Pedido não encontrado para essa fábrica ou peça não pertence ao pedido.";
  }
  exit;
}


if($_POST['gera_embarque']=='true'){
  $post = true;

  $fabrica = $_POST["fabrica"];
  $dadosOS = $_POST['os'];
  $dadosPedidos = $_POST['pedido'];
  $gerado = [];


  $sql_fabrica = "SELECT nome from tbl_fabrica WHERE fabrica = $fabrica";
  $res_fabrica = pg_query($con, $sql_fabrica);
  if(pg_num_rows($res_fabrica)>0){
    $nome_fabrica = strtolower(pg_fetch_result($res_fabrica, 0, nome));
  }  

  $validacao = false;
  foreach ($dadosOS as $key => $value) {
    if(!empty($value)){ 
      $num_os = trim($value);

        if(empty($num_os)){
          continue;
        }

        if(array_key_exists($num_os, $gerado)){
          $retorno[$key]['pedido'] = $gerado[$num_os]['pedido'];
          $retorno[$key]['embarque'] = $gerado[$num_os]['embarque'];
          continue;
        }

        $peca = $_POST['peca'][$key];

        $sql_estoque = "SELECT tbl_os.os, tbl_os_item.peca, tbl_posto_estoque.qtde as estoque from tbl_os 
          inner join tbl_os_produto on tbl_os_produto.os = tbl_os.os
          inner join tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto
          inner join tbl_posto_estoque on tbl_posto_estoque.peca = tbl_os_item.peca
          where tbl_os.os = $num_os and tbl_os_item.peca = $peca AND tbl_posto_estoque.qtde >= tbl_os_item.qtde";
        $res_estoque = pg_query($con, $sql_estoque);
        if(pg_num_rows($res_estoque)>0){
          $validacao = true;
        }else{
          $embarque_gerado = utf8_encode("Peça sem estoque");
        }

        if($validacao == true){
          $sql_troca = "SELECT from tbl_os_troca where fabric = $fabrica and os = ".$num_os;
          $res_troca = pg_query($con, $sql_troca);
          if(pg_num_rows($res_troca)>0){
              //executa rotina de troca          
            exec("php $caminho/rotinas/$nome_fabrica/gera-pedido-troca.php $num_os" );
          }else{
            exec("php $caminho/rotinas/$nome_fabrica/gera-pedido.php $num_os" );
          }

          $sqlPedido = " SELECT tbl_os_item.pedido from tbl_os_item 
                    inner join tbl_os_produto on tbl_os_item.os_produto = tbl_os_produto.os_produto
                    where tbl_os_produto.os = $num_os and tbl_os_item.peca = $peca";
          $resPedido = pg_query($con, $sqlPedido);

          if(pg_num_rows($resPedido)>0){
              $pedido_gerado = pg_fetch_result($resPedido, 0, pedido);
          }

          $retorno[$key]['pedido'] = $pedido_gerado;  

          $embarque_gerado = exec("perl $caminhoPerl/distrib/embarque_novo.pl $num_os" );

          $sql_num_embarque = " SELECT tbl_pedido_item.pedido, tbl_embarque_item.embarque 
                              FROM tbl_pedido_item 
                              JOIN tbl_embarque_item on tbl_pedido_item.pedido_item = tbl_embarque_item.pedido_item 
                              WHERE tbl_pedido_item.pedido = $pedido_gerado ";
          $res_num_embarque = pg_query($con, $sql_num_embarque);

          if(pg_num_rows($res_num_embarque)>0){
              $embarque_gerado = pg_fetch_result($res_num_embarque, 0, embarque);
          }else{
            $embarque_gerado = "";
          }
        }

        $retorno[$key]['embarque'] = $embarque_gerado;
        $gerado[$num_os]['pedido'] = $pedido_gerado;
        $gerado[$num_os]['embarque'] = $embarque_gerado;
    }    
  }
  
  foreach ($dadosPedidos as $key => $value) {

      $num_pedido = trim($value);

      if(empty($num_pedido)){
        continue;
      }

      if(array_key_exists($num_pedido, $gerado)){
        $retorno[$key]['pedido'] = ""; 
        $retorno[$key]['embarque'] = $gerado[$num_pedido]['embarque'];
        continue;
      }

      $peca = $_POST['peca'][$key];

      $sql_estoque = "SELECT tbl_posto_estoque.peca as estoque from tbl_pedido_item 
                      inner join tbl_posto_estoque on tbl_posto_estoque.peca = tbl_pedido_item.peca
                      where tbl_pedido_item.pedido = $num_pedido and tbl_pedido_item.peca = $peca
                      AND tbl_posto_estoque.qtde >= tbl_pedido_item.qtde ";
      $res_estoque = pg_query($con, $sql_estoque);
      if(pg_num_rows($res_estoque)>0){
        $validacao = true;        
      }else{
        $embarque_gerado = utf8_encode("Peça sem estoque");
      }

      if($validacao == true){
        $sql_pedido_fabrica = "SELECT tbl_tipo_pedido.tipo_pedido, pedido, codigo from tbl_pedido join tbl_tipo_pedido on tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido and tbl_pedido.fabrica = $fabrica where pedido = $num_pedido ";
        $res_pedido_fabrica = pg_query($con, $sql_pedido_fabrica);
        if(pg_num_rows($res_pedido_fabrica)>0){
            $codigo = pg_fetch_result($res_pedido_fabrica, 0, 'codigo');
        }

        if($codigo == 'FAT'){
          exec("php $caminho/rotinas/distrib/embarque_novo_faturado.php $num_pedido" );
        }

        $sql_num_embarque = "SELECT tbl_pedido_item.pedido, tbl_embarque_item.embarque 
                              FROM tbl_pedido_item 
                              JOIN tbl_embarque_item on tbl_pedido_item.pedido_item = tbl_embarque_item.pedido_item 
                              WHERE tbl_pedido_item.pedido = $num_pedido";
        $res_num_embarque = pg_query($con, $sql_num_embarque);
        if(pg_num_rows($res_num_embarque)>0){
            $embarque_gerado = pg_fetch_result($res_num_embarque, 0, embarque);
        }else{
          $embarque_gerado = "";
        }
      }
      $retorno[$key]['embarque'] = $embarque_gerado;
      $retorno[$key]['pedido'] = ""; 

      $gerado[$num_pedido]['embarque'] = $embarque_gerado;

  }

  echo json_encode($retorno);


exit;
}



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

    $nota_fiscal  = $_POST['nota_fiscal'];
    $fabrica      = (int)$_POST["fabrica"];

    if($fabrica == 0){
      $msg_erro["msg"][]    = "Preencha os campos obrigatórios";
      $msg_erro["campos"][] = "fabrica";
    }

    if(strlen(trim($nota_fiscal))==0){ 
      $msg_erro["msg"][]    = "Preencha os campos obrigatórios";
      $msg_erro["campos"][] = "nota_fiscal";
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
            $("input[class^='os_']").blur(function(){
                var posicao = $(this).data("posicao");
                var peca = $(this).data("peca");
                var numos = $(this).val();
                var fabrica = $("#fabrica").val(); 

                if(numos.length > 0){
                  $.ajax({
                      url:"nf_atendimento.php",
                      type: "POST",
                      data: {verifica_os_fabrica:true, os:numos, fabrica:fabrica, peca:peca},
                      complete: function(data){
                          data = data.responseText;
                          if(data.length > 0){
                            alert(data);
                            $(".os_"+posicao).val('');
                          }else{
                            $(".pedido_"+posicao).attr('readonly', true);
                          }
                      }
                  });

                  
                }else{
                  $(".pedido_"+posicao).attr('readonly', false);
                }               
            });

            $("input[class^='pedido_']").blur(function(){
                var posicao = $(this).data("posicao");
                var peca = $(this).data("peca");
                var pedido = $(this).val();
                var fabrica = $("#fabrica").val(); 

                if(pedido.length > 0){
                  $.ajax({
                      url:"nf_atendimento.php",
                      type: "POST",
                      data: {verifica_pedido_fabrica:true, pedido:pedido, fabrica:fabrica, peca:peca},
                      complete: function(data){
                          data = data.responseText;
                          if(data.length > 0){
                            alert(data);
                            $(this).val('');
                            $(".pedido_"+posicao).val('');
                          }else{
                            $(".os_"+posicao).attr('readonly', true);
                          }
                      }
                  });
                  
                }else{
                  $(".os_"+posicao).attr('readonly', false);
                }               
            });

            $("#gerar_embarque").click(function(){

                var dados = $('#frm_nf_atendimento').serialize()+'&gera_embarque=true';
                valida = false;
                $("[name^=os],[name^=pedido]").each( function(i, el){
                  if ($(el).val().length > 0 ) valida = true;
                });

                if(valida == false){
                  alert('Informe o número de O.S ou Pedido Faturado.');
                  return false;
                }
                $.ajax({
                    url:"nf_atendimento.php",
                    type: "POST",
                    data: dados,
                    beforeSend: function () {
                        $('#loading-block').show();
                        $('#loading').show();
                    },
                    complete: function(data){
                        data = $.parseJSON(data.responseText);
                        $('#loading-block').hide();
                        $('#loading').hide();

                        $.each(data , function(index, value){
                          if(value.pedido.length > 0){
                            $('.pedido_'+index).val(value.pedido);
                          }

                          if(value.embarque.length > 0){
                            $('.embarque_'+index).text(value.embarque);
                          }else{
                            $('.embarque_'+index).text('Falha ao gerar embarque');
                          }
                        });
                        
                        alert("Operações realizadas com sucesso.");
                        
                    }
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
              <div class="span12 row-fluid">   
                  <div class="span2"></div> 
                    <div class='span4 control-group <?=(in_array("fabrica", $msg_erro["campos"])) ? "error" : ""?>'>
                      <label class='control-label' for='fabrica'>Fábrica</label>
                        <div class='controls controls-row'>
                          <div class='span4'>
                            <select name='fabrica' id='fabrica'>
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
                    <div class='control-group <?=(in_array("nota_fiscal", $msg_erro["campos"])) ? "error" : ""?>'>
                      <label class='control-label' for='data_final'>Nota Fiscal</label>
                        <div class='controls controls-row'>
                          <div class='span8'>
                            <h5 class='asteristico'>*</h5>
                              <input type="text" name="nota_fiscal" id="nota_fiscal" size="12" maxlength="12" class='span12' value="<?=$nota_fiscal?>" >
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
        </div>
      </form>
<?php   
  if(!count($msg_erro["msg"]) > 0 && !empty($_POST)){
    
    $sql = "SELECT tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao, tbl_posto_estoque.qtde as qtde_estoque
            FROM tbl_faturamento 
            JOIN tbl_faturamento_item ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
            JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca and tbl_peca.fabrica = $fabrica
            join tbl_posto_estoque on tbl_posto_estoque.peca = tbl_peca.peca 
            WHERE nota_fiscal = '$nota_fiscal' 
            and tbl_faturamento.fabrica = 10 
            and tbl_faturamento.posto in (4311, 20682)
            AND tbl_faturamento_item.qtde_estoque > 0 ";
    $res = pg_query($con,$sql);
    $qtde_total = pg_num_rows($res);

    if(pg_num_rows($res) > 0) {

      echo "<center>";
      echo "<form name='frm_nf_atendimento' id='frm_nf_atendimento' method='POST' action='' >";
      echo "<table width='850px' id='relatorio_listagem' name='relatorio_listagem' class='table table-striped table-bordered table-hover table-large'>";
      echo "<thead>"; 
        echo "<tr class = 'titulo_coluna'>";
          echo "<th align='center' width='10%' nowrap>Peça</th>";
          echo "<th align='center' width='10%' nowrap>Estoque</th>";
          echo "<th align='center' width='10%' nowrap>OS</th>";
          echo "<th align='center' width='10%' nowrap>Pedido Faturado</th>";
          echo "<th align='center' width='10%' nowrap>Embarque</th>";
        echo "</tr>";

      function clean($str) {
        return str_replace(".", "", trim(strtoupper($str)));
      }

      $total_qtde = 0;


      for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
        $peca             = pg_fetch_result($res, $i, 'peca');
        $qtde_estoque     = pg_fetch_result($res, $i, 'qtde_estoque');
        $referencia_peca = pg_fetch_result($res, $i, 'referencia');
        $descricao_peca = pg_fetch_result($res, $i, 'descricao');

        echo "<tr>";
          echo "<td class='tac' nowrap>$referencia_peca - $descricao_peca</td>";
          echo "<td class='tac' width='20'>$qtde_estoque</td>";
          echo "<td class='tac'><input type='text' class='os_$i' data-peca='$peca' data-posicao='$i' name='os[]' value=''></td>";
          echo "<td class='tac'><input type='text' class='pedido_$i' data-peca='$peca' data-posicao='$i' name='pedido[] value=''>          
          <input type='hidden' name='peca[]' value='$peca'>
          </td>";
          echo "<td class='tac embarque_$i'></td>";
        echo "</tr>";
      }
      echo "<tr>";
        echo "<td colspan='5' class='tac'>
            <button type='button' name='btnacao' id='gerar_embarque'>Gerar Embarque</button>
            <input type='hidden' name='fabrica' value='$fabrica'>
            <input type='hidden' name='qtde_total' id='qtde_total' value='$qtde_total'>
        </td>";
      echo "</tr>";
      echo "</table>";
      echo "</form>";
      echo "</center>";
  
     ?> 
      
  <?php 

    }elseif($post != true){
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



