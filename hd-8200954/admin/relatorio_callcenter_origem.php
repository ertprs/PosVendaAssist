<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["btn_acao"] == "submit") {
  $data_inicial       = $_POST['data_inicial'];
  $data_final         = $_POST['data_final'];
  //$origem_atendimento = $_POST['origem_atendimento'];

  if(isset($_POST["origem_atendimento"])){
    if(count($origem_atendimento)>0){
      $linha = $_POST["origem_atendimento"];
    }
  }

  // Validação Data //

  if (!strlen($data_inicial) or !strlen($data_final)) {
    $msg_erro["msg"][]    = traduz("Preencha os campos obrigatórios");
    $msg_erro["campos"][] = "data";
  } else {
    list($di, $mi, $yi) = explode("/", $data_inicial);
    list($df, $mf, $yf) = explode("/", $data_final);

    if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
      $msg_erro["msg"][]    = traduz("Data Inválida");
      $msg_erro["campos"][] = "data";
    } else {
      $aux_data_inicial = "{$yi}-{$mi}-{$di}";
      $aux_data_final   = "{$yf}-{$mf}-{$df}";

      if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
        $msg_erro["msg"][]    = traduz("Data Final não pode ser menor que a Data Inicial");
        $msg_erro["campos"][] = "data";
      }
    }
  }

  // Fim Validação Data //

  if (strlen($origem_atendimento) > 0 || count($origem_atendimento) > 0 ) {

    foreach ($origem_atendimento as $key => $value) {
      $origem[] = "'$value'";
    }

      $origem = implode(",", $origem);
      $cond_1 = " AND tbl_hd_chamado_extra.origem IN ({$origem})";
  }

  if (!count($msg_erro["msg"])) {
    $sql = "SELECT
          tbl_hd_chamado_extra.origem
        FROM tbl_hd_chamado
        JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
        WHERE tbl_hd_chamado.data between '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
        AND tbl_hd_chamado.fabrica = $login_fabrica
        $cond_1
        GROUP BY tbl_hd_chamado_extra.origem
        ORDER BY tbl_hd_chamado_extra.origem
        ";
    $resSubmit = pg_query($con, $sql);

  }

}
// fim $_POST //

$layout_menu = "callcenter";
$title = traduz("RELATÓRIO CALL-CENTER x ORIGEM");
include 'cabecalho_new.php';

$plugins = array(
  "autocomplete",
  "datepicker",
  "shadowbox",
  "mask",
  "dataTable",
  "multiselect"
);

include("plugin_loader.php");
?>
<script type="text/javascript">
  var hora = new Date();
  var engana = hora.getTime();

  $(function() {
    $.datepickerLoad(Array("data_final", "data_inicial"));
    Shadowbox.init();

    $("#origem_atendimento").multiselect({
      selectedText: "selecionados # de #"
    });

  });

</script>
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
  <b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b>
</div>

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
  <div class='titulo_tabela '><?=traduz('Parâmetros de Pesquisa')?></div>
  <br/>

  <div class='row-fluid'>
    <div class='span2'></div>
    <div class='span4'>
      <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
        <label class='control-label' for='data_inicial'><?=traduz('Data Inicial')?></label>
        <div class='controls controls-row'>
          <div class='span4'>
            <h5 class='asteristico'>*</h5>
              <input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
          </div>
        </div>
      </div>
    </div>
    <div class='span4'>
      <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
        <label class='control-label' for='data_final'><?=traduz('Data Final')?></label>
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

  <div class='row-fluid'>
    <div class='span2'></div>
    <div class='span4'>
      <div class='control-group <?=(in_array("origem_atendimento", $msg_erro["campos"])) ? "error" : ""?>'>
        <label class='control-label' for='origem_atendimento'><?=traduz('Origem')?></label>
        <div class='controls controls-row'>
          <div class='span4'>
            <h5 class='asteristico'>*</h5>
            <select name="origem_atendimento[]" id="origem_atendimento" multiple="multiple" >
              <?php
                  if (!(in_array($login_fabrica, array(160)) or $replica_einhell)) {
                    ?>
                      <option value='Telefone'><?=traduz('Telefone')?></option>
                      <option value='Email'>Email</option>
                      <option value='Chat'>Chat</option>
                      <option value='Facebook'>Facebook</option>
                      <option value='LASA'>LASA</option>
                      <option value='NAJ'>NAJ</option>
                      <option value='ReclameAqui'>Reclame Aqui</option>
                      <option value='Relacionamento'><?=traduz('Relacionamento')?></option>
                    <?php
                  } else {
                    $aux_sql = "SELECT descricao FROM tbl_hd_chamado_origem WHERE fabrica = $login_fabrica AND ativo IS TRUE ORDER BY descricao";
                    $aux_res = pg_query($con, $aux_sql);
                    $aux_tot = pg_num_rows($aux_res);

                    if (!empty($aux_tot)) {
                      for ($z = 0; $z < $aux_tot; $z++) { 
                        $descricao         = pg_fetch_result($aux_res, $z, 'descricao');

                        ?> <option value="<?=$descricao;?>"> <?=$descricao;?> </option> <?
                      }
                    }
                  }
              ?>
            </select>
          </div>
        </div>
      </div>
    </div>
    <div class="span4"></div>
    <div class="span2"></div>
  </div>
  <p><br/>
    <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));"><?=traduz('Pesquisar')?></button>
    <input type='hidden' id="btn_click" name='btn_acao' value='' />
  </p><br/>
</form>

<?php

if(isset($resSubmit)) {
  if(pg_num_rows($resSubmit) > 0) {
    $count = pg_num_rows($resSubmit);
?>
  <table id="relatorio_callcenter_origem" class='table table-striped table-bordered table-fixed' >
    <thead>
      <tr class='titulo_coluna' >
        <th>Origem</th>
        <th colspan="2">Quantidade</th>
      </tr>
    </thead>
    <tbody>
    <?php
      $aux_origem  = array();
      $aux_grafico = array(); 
      $total_qtde  = 0;
      for ($i = 0; $i < $count; $i++) {
        $origem = pg_fetch_result($resSubmit, $i, 'origem');

        if (!in_array($origem, $aux_origem)) {
          $aux_origem[] = $origem;
          $aux_grafico[$i]["origem"] = $origem;
        } else {
          continue;
        }


        $sql2 = "SELECT
                    tbl_hd_chamado_extra.hd_chamado
                  FROM tbl_hd_chamado
                  JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
                  WHERE tbl_hd_chamado.data between '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
                  AND tbl_hd_chamado.fabrica = $login_fabrica
                  AND tbl_hd_chamado_extra.origem = '$origem'";
        $res2 = pg_query($con, $sql2);

        if(pg_num_rows($res2) > 0){
          $hd_chamado = pg_fetch_result($res2, 0, hd_chamado);
          $qtde_res = pg_fetch_all($res2);
        }

        $qtde_origem = count($qtde_res);
        $total_qtde += $qtde_origem;

        $aux_grafico[$i]["qtde_origem"] = $qtde_origem;
        if ((in_array($login_fabrica, array(160)) or $replica_einhell) && strlen($origem) == 0) {
          $origem = "Sem Origem";
          $aux_grafico[$i]["origem"] = $origem;
        }

        $body = "
          <tr>
            <td class='tac'>{$origem}</td>
            <td class='tac'><a href='relatorio_callcenter_origem_detalhe.php?origem=$origem&data_inicial=$aux_data_inicial&data_final=$aux_data_final' target='blank_'>{$qtde_origem}</a></td>
          </tr>
        ";
        echo $body;
      }

      if ((in_array($login_fabrica, array(160)) or $replica_einhell)) {
        $aux_grafico["total_qtde"] = $total_qtde;
      ?>
        <tfoot>
          <tr class="titulo_coluna">
            <td class="tac"><?=traduz('Total')?></td>
            <td class="tac"> <?=$total_qtde;?> </td>
          </tr>
        </tfoot>
      <?php } ?>
</tbody>
</table>
<?php
  if ((in_array($login_fabrica, array(160)) or $replica_einhell)) {
    ?>
      <script type="text/javascript" src="https://code.highcharts.com/highcharts.js"></script>
      <script src="https://code.highcharts.com/modules/data.js"></script>
      <script src="https://code.highcharts.com/modules/drilldown.js"></script>
      <div id="container" style="width: 700px; margin: 0 auto" ></div>
    <?php

      foreach ($aux_grafico as $key => $value) {
        if (!empty($value["origem"])) {
          $qtde_pessoal  = $value["qtde_origem"];
          $qtde_pessoal  = round(($qtde_pessoal / $aux_grafico["total_qtde"])*100, 2);
          $graph_array[] = "['".$value["origem"]."', $qtde_pessoal]";
        }
      }
      
      $chart_data = implode(',', $graph_array);
      $chart_title = "Gráfico Percentual";

      if ($chart_data){
        echo $grafico = "<script>
          $(function () {
              $('#container').highcharts({
            chart: {
                  plotBackgroundColor: null,
                      plotBorderWidth: null,
                  plotShadow: false
            },
            title: {
              text: '$chart_title'
            },
            tooltip: {
              formatter: function() {
                return '<b>'+ this.point.name +'</b>: '+ Highcharts.numberFormat(this.y, 2, '.') +' %';
              }
            },
            plotOptions: {
              pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                  enabled: true,
                  color: '#000000',
                  connectorColor: '#000000',
                  formatter: function() {
                    return '<b>'+ this.point.name +'</b>: '+ Highcharts.numberFormat(this.y, 2, '.') +' %';
                  }
                }
              }
            },
            series: [{
              type: 'pie',
              name: 'Browser share',
              data: [
                $chart_data
              ]
            }]
          });
        });
      </script>";
    }
  }
?>
<br />
<?php
  }else{
    echo '
      <div class="container">
      <div class="alert">
            <h4>'.traduz("Nenhum resultado encontrado").'</h4>
      </div>
      </div>';
  }
}
include 'rodape.php';?>
