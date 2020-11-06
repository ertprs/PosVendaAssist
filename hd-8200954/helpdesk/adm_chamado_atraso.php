<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
require_once '../funcoes.php';

define('BS3', true);

function calcula_atraso($data_prazo , $data_entrega)
{

  if (!empty($data_entrega) && $data_entrega <= $data_prazo) {
      return "Entrega dentro do prazo";
  }

  if (empty($data_entrega)) {
    $data_entrega = date("Y-m-d H:i:s");
  }

  $data_prazo   = date_create($data_prazo);
  $data_entrega = date_create($data_entrega);
  
  $atraso = date_diff($data_prazo, $data_entrega);
  
  if ($data_prazo > $data_entrega) {
    return "Sem atraso";
  } else {
    return $atraso->format('%d Dias %h:%i Horas');
  }
    
}

if (isset($_POST["submit"])) {
  $data_inicial    = $_POST["data_inicial"];
  $data_final      = $_POST["data_final"];

  $xdata_inicial = formata_data($data_inicial);
  $xdata_final   = formata_data($data_final);

  $atendente = $_POST['atendente'];

  if (empty($xdata_inicial) && empty($xdata_final)) {
    $msg_erro[] = "Informe os campos de data";
  }

  if (count($msg_erro) == 0) {
    $condDtEntrega = "AND (sc.data_input BETWEEN '{$xdata_inicial}' AND '{$xdata_final}')";
    $cabecalho_tabela = "Etapas do chamado entregues/pendentes";
  } else {
    $cabecalho_tabela = "Etapas do chamado pendentes para entrega";
    $condDtEntrega = "AND sc.data_entrega IS NULL";
  }

  if (!empty($atendente)) {
    $condAtendente = " AND a.admin = $atendente";
  }

} else {
  $condDtEntrega = "AND sc.data_entrega IS NULL";
  $cabecalho_tabela = "Etapas do chamado pendentes para entrega";
}


$sqlChamado = "SELECT sc.hd_chamado,
                        cs.etapa,
                        sc.admin,
                        sc.data_prazo,
                        sc.data_entrega,
                        a.login,
                        a.nome_completo,
                        coalesce(data_entrega, current_timestamp) - data_prazo::date as dias_entrega,
                        TO_CHAR(coalesce(data_entrega, current_timestamp) - data_prazo, 'HH24:MI') as tempo_entrega,
                        tp.descricao as tipo_chamado
              FROM tbl_status_chamado sc
              JOIN tbl_controle_status cs USING(controle_status)
              JOIN tbl_admin a USING(admin)
              JOIN tbl_hd_chamado USING(hd_chamado)
              JOIN tbl_tipo_chamado tp USING(tipo_chamado)
              WHERE sc.admin IS NOT NULL
              {$condDtEntrega}
              {$condAtendente}
              AND UPPER(tbl_hd_chamado.status) != 'RESOLVIDO' 
              ORDER BY controle_status";
              
$resChamado = pg_query($con, $sqlChamado);

$TITULO    = 'Chamados ';
$bs_extras = array('shadowbox_lupas');
include "menu.php";
?>
<link type="text/css" rel="stylesheet" media="screen" href="../admin/plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
<script src="../admin/plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
<script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
<script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
<script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
<script src='../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.datepicker.min.js'></script>
<script src="../admin/plugins/jquery.alphanumeric.js"></script>
<script src='../admin/plugins/jquery.mask.js'></script>
<script>

    $(function() {
      $("#data_inicial").datepicker().mask("99/99/9999");
      $("#data_final").datepicker().mask("99/99/9999");
    });
</script>
<style>
.table > tbody > tr > td,
.table > thead > tr > th {
  vertical-align: middle;
  text-align: center;
}

.table > tbody > tr > td:first-of-type {
  font-weight: bold;
}

#leg_red {
  background-color: #EE2C2C;
}

#leg_orange {
  background-color: orange;
}

#leg_green {
  background-color: lightgreen;
}

#leg_red, #leg_green, #leg_orange {
  width: 10%;
}

.legendas {
  position: relative;
  left: 25%;
  width: 50%;
  font-weight: bolder;
}
</style>
    <div class="container">
      <div class="panel panel-default">
        <div class="panel-heading">
        <h3 class="panel-title">Relatório chamados atrasados </h3>
        </div>
        <div class="panel-body">
          <form name="form_altera" method="POST" action="<?= $_SERVER['PHP_SELF'] ?>">
            <div class="row">
              <div class="col-md-2 col-md-offset-1 col-sm-4 col-xs-6">
              </div>
              <div class="col-md-3 col-sm-4 col-xs-6">
                <div class="form-group">
                  <label for="data_inicial"><span style="color: red;">*</span> <?=traduz("Data Inicial")?></label>
                  <div class="input-group date col-md-7">
                    <input type="text" class="form-control" id="data_inicial" name="data_inicial" placeholder="<?=traduz("Data Inicial")?>" value="<?=$_POST['data_inicial']?>">
                    <span class="input-group-addon"><i class="glyphicon glyphicon-th"></i></span>
                  </div>
                </div>
              </div>
              <div class="col-md-3 col-sm-4 col-xs-6">
                <div class="form-group">
                  <label for="data_final"><span style="color: red;">*</span> <?=traduz('Data Final')?></label>
                  <div class="input-group date col-md-7">
                    <input type="text" class="form-control" id="data_final" name="data_final" placeholder="<?=traduz('Data Final')?>" value="<?=$_POST['data_final']?>">
                    <span class="input-group-addon"><i class="glyphicon glyphicon-th"></i></span>
                  </div>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-2 col-md-offset-1 col-sm-4 col-xs-6">
              </div>
              <div class="col-md-4 col-sm-4 col-xs-6">
                <div class="form-group">
                  <label for="data_final"> <?=traduz('Atendente')?></label>
                  <div class="input-group">
                    <?php
                      $sqlatendente = "SELECT  nome_completo, admin
                                      FROM    tbl_admin
                                      WHERE   tbl_admin.fabrica = 10
                                      AND tbl_admin.grupo_admin in (1,2,4)
                                      AND ativo
                                      ORDER BY tbl_admin.nome_completo;";
                      $resatendente = pg_query($con,$sqlatendente);
                    ?>
                    <select name="atendente" class="form-control">
                      <option value=""></option>
                      <?php
                        while ($atendente = pg_fetch_array($resatendente)) { 
                          $selected = ($atendente['admin'] == $_POST['atendente']) ? 'selected' : '';
                          ?>
                          <option value="<?= $atendente['admin'] ?>" <?= $selected ?>><?= $atendente['nome_completo'] ?></option>
                      <?php
                        }
                      ?>
                    </select>
                  </div>
                </div>
              </div>
            </div>
            <div class="row">
                <div class="row-fluid text-center">
                  <div>
                    <button type="submit" id="clear-form" name="submit" class="btn btn-default"><?=traduz('Pesquisar')?></button>
                  </div>
                </div>
             </div>
          </form>
        </div>
      </div>
      <table class="table table-bordered legendas">
        <tr>
          <td id="leg_red"></td><td>Chamados com o prazo vencido ainda não entregues</td>
          <td id="qtd_vencido"></td>
        </tr>
        <tr>
          <td id="leg_orange"></td><td>Chamados entregues fora do prazo</td>
          <td id="entregue_fora_prazo"></td>
        </tr>
        <tr>
          <td id="leg_green"></td><td>Chamados entregues dentro do prazo</td>
          <td id="qtd_no_prazo"></td>
        </tr>
      </table>
      <table class="table table-large table-bordered">
        <thead>
          <tr>
            <th colspan="100%"><?= $cabecalho_tabela ?></th>
          </tr>
          <tr>
            <th>Chamado</th>
            <th>Tipo Chamado</th>
            <th>Etapa</th>
            <th>Admin</th>
            <th>Data Prazo</th>
            <?php 
            if (isset($_POST["submit"]) && count($msg_erro) == 0) { ?>
              <th>Data Entrega</th>
              <th>Dias de Atraso</th>
            <?php
            }
            ?>
          </tr>
        </thead>
        <tbody>
          <?php 
          $entregues_no_prazo       = 0;
          $nao_entregues_fora_prazo = 0;
          $entregues_fora_prazo     = 0;

          for ($x=0;$x < pg_num_rows($resChamado);$x++) { 
            $hd_chamado      = pg_fetch_result($resChamado, $x, 'hd_chamado');
            $etapa           = pg_fetch_result($resChamado, $x, 'etapa');
            $admin           = pg_fetch_result($resChamado, $x, 'nome_completo');
            $data_prazo      = pg_fetch_result($resChamado, $x, 'data_prazo');
            $data_entrega    = pg_fetch_result($resChamado, $x, 'data_entrega');
            $tipo_chamado    = pg_fetch_result($resChamado, $x, 'tipo_chamado');
            $cor             = "";

            $atraso = calcula_atraso($data_prazo, $data_entrega);

            if ($atraso == 'Entrega dentro do prazo') {
              $cor = "lightgreen";
              $entregues_no_prazo += 1;
            } else if ($atraso != 'Sem atraso') {

              if (empty($data_entrega)) {
                $cor = "#EE2C2C";
                $nao_entregues_fora_prazo += 1;
              } else {
                $cor = "orange";
                $entregues_fora_prazo += 1;
              }

            }

            ?>
            <tr style="background-color: <?= $cor ?> ;">
              <td><a href="adm_chamado_detalhe.php?hd_chamado=<?= $hd_chamado ?>&consultar=sim"><?= $hd_chamado ?></td>
              <td><?= $tipo_chamado ?></td>
              <td><?= $etapa ?></td>
              <td><?= $admin ?></td>
              <td><?= mostra_data($data_prazo) ?></td>
              <?php 
              if (isset($_POST["submit"]) && count($msg_erro) == 0) { ?>
                <td><?= mostra_data($data_entrega) ?></td>
                <td><?= $atraso?></td>
              <?php 
              } ?>
            </tr>
          <?php
          }
          ?>
        </tbody>
      </table>
    </div>
    <script>
      $("#entregue_fora_prazo").text("<?= $entregues_fora_prazo ?>");
      $("#qtd_no_prazo").text("<?= $entregues_no_prazo ?>");
      $("#qtd_vencido").text("<?= $nao_entregues_fora_prazo ?>");
    </script>
<?php
include "rodape.php"
?>
