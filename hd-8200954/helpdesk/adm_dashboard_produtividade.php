<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
require_once '../funcoes.php';

unset($equipe, $atendente);

if (isset($_POST['busca_admin_equipe'])) {

  $equipe = $_POST['equipe_id'];

  $sql = "SELECT admin, nome_completo
          FROM tbl_admin
          WHERE grupo_admin = {$equipe}
          AND ativo
          AND fabrica = 10";
  $res = pg_query($con, $sql);

  $retorno = [];

  if (pg_num_rows($res) > 0) {

    while ($dados = pg_fetch_object($res)) {

      $retorno[$dados->admin] = utf8_encode($dados->nome_completo);

    }

  }

  exit(json_encode($retorno));

}

define('BS3', true);

if (isset($_POST["submit"])) {
  $data_inicial    = $_POST["data_inicial"];
  $data_final      = $_POST["data_final"];

  $atendente          = $_POST['atendente'];
  $equipe             = $_POST['equipe'];
  $com_sem_horas      = $_POST['com_sem_horas'];
  $tipo_chamado       = $_POST['tipo_chamado'];
  $abertos_resolvidos = $_POST['abertos_resolvidos'];
  $status_chamado     = $_POST['status_chamado'];

  if (empty($data_inicial) && empty($data_final) && $abertos_resolvidos == 'resolvidos') {
    $msg_erro[] = "Informe os campos de data";
  }

  if (count($msg_erro) == 0) {

    if (count($tipo_chamado) > 0) {
      $condPrincipal[] = "AND tbl_hd_chamado.tipo_chamado IN (".implode(",", $tipo_chamado).")";
    }

    switch ($equipe) {
      case '1':
        $cond[] = "AND a_principal.nome_completo IS NOT NULL";
        if (!empty($atendente)) {
          $cond[] = "AND a_principal.admin = {$atendente}";
        }
        break;
      case '4':
        $cond[] = "AND p_principal.nome_completo IS NOT NULL";
        if (!empty($atendente)) {
          $cond[] = "AND p_principal.admin = {$atendente}";
        }
        break;
      case '6':
        $cond[] = "AND s_principal.nome_completo IS NOT NULL";
        if (!empty($atendente)) {
          $cond[] = "AND s_principal.admin = {$atendente}";
        }
        break;
    }

    if ($com_sem_horas == 'com') {
      $condPrincipal[] = "AND tbl_hd_chamado.hora_faturada IS NOT NULL";
    } else if ($com_sem_horas == 'sem') {
      $condPrincipal[] = "AND tbl_hd_chamado.hora_faturada IS NULL";
    }

    if ($abertos_resolvidos == 'resolvidos') {

      $xdata_inicial = formata_data($data_inicial);
      $xdata_final   = formata_data($data_final);

      $condPrincipal[] = "AND    tbl_hd_chamado.resolvido BETWEEN '{$xdata_inicial} 00:00:00' AND '{$xdata_final} 23:59:59'
                          AND    tbl_hd_chamado.resolvido IS NOT NULL
                          AND    tbl_hd_chamado.status = 'Resolvido'";

    } else {

      $condPrincipal[] = "AND    tbl_hd_chamado.resolvido IS NULL
                          AND    tbl_hd_chamado.status != 'Resolvido'
                          AND    tbl_hd_chamado.status != 'Cancelado'";

    }

    if (count($status_chamado) > 0) {
      $condPrincipal[] = "AND tbl_hd_chamado.status IN ('".implode("','", $status_chamado)."')";
    }

  }
} else {

  $condPrincipal[] = "AND    tbl_hd_chamado.resolvido IS NULL
                      AND    tbl_hd_chamado.status != 'Resolvido'
                      AND    tbl_hd_chamado.status != 'Cancelado'";

}

if (count($msg_erro) == 0) {

  $sqlPrincipal = "
      WITH chamados as (

        SELECT hd_chamado,
               tbl_tipo_chamado.descricao as tipo_chamado,
               tbl_tipo_chamado.tipo_chamado as tipo_chamado_id,
               tbl_hd_chamado.hora_faturada,
               TO_CHAR(data, 'DD/MM/YYYY HH24:MI')      as abertura,
               TO_CHAR(previsao_termino, 'DD/MM/YYYY HH24:MI') as prazo_entrega,
               EXTRACT(epoch FROM (previsao_termino - COALESCE(resolvido, current_timestamp))) / 3600 as atraso_na_entrega,
               TO_CHAR(resolvido, 'DD/MM/YYYY HH24:MI') as resolvido,
               COALESCE(horas_desenvolvimento, 0)       as horas_desenvolvimento,
               COALESCE(horas_suporte, 0)               as horas_suporte,
               COALESCE(horas_teste, 0)                 as horas_teste,
               COALESCE(horas_analise, 0)               as horas_analise,
               (COALESCE(horas_desenvolvimento, 0) + COALESCE(horas_suporte, 0) + COALESCE(horas_teste, 0) + COALESCE(horas_analise, 0)) as total_horas_orcamento
        FROM   tbl_hd_chamado
        JOIN   tbl_tipo_chamado ON tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
        WHERE  tbl_hd_chamado.fabrica_responsavel = 10
        ".implode(" ", $condPrincipal)."

      ), horas_trabalhadas_grupo AS (

        SELECT hca.hd_chamado,
               SUM(hca.data_termino - hca.data_inicio) FILTER(WHERE tbl_grupo_admin.grupo_admin = 4)           as total_trabalhadas_programador,
               SUM(hca.data_termino - hca.data_inicio) FILTER(WHERE tbl_grupo_admin.grupo_admin = 6)           as total_trabalhadas_suporte,
               SUM(hca.data_termino - hca.data_inicio) FILTER(WHERE tbl_grupo_admin.grupo_admin = 11)          as total_trabalhadas_comercial,
               SUM(hca.data_termino - hca.data_inicio) FILTER(WHERE tbl_grupo_admin.grupo_admin = 1)           as total_trabalhadas_analistas,
               SUM(hca.data_termino - hca.data_inicio) FILTER(WHERE tbl_grupo_admin.grupo_admin = 9)           as total_trabalhadas_gerente,
               SUM(hca.data_termino - hca.data_inicio) FILTER(WHERE tbl_grupo_admin.grupo_admin NOT IN (9,11)) as tempo_total_gasto_pela_equipe
        FROM tbl_hd_chamado_atendente hca
        JOIN tbl_admin ON tbl_admin.admin = hca.admin
        JOIN tbl_grupo_admin ON tbl_admin.grupo_admin = tbl_grupo_admin.grupo_admin
        JOIN chamados  ON hca.hd_chamado = chamados.hd_chamado
        GROUP BY hca.hd_chamado

      ), dados_individuais AS (
        SELECT tbl_hd_chamado_atendente.hd_chamado,
               tbl_admin.nome_completo,
               tbl_admin.admin,
               tbl_admin.grupo_admin,
               SUM(data_termino - data_inicio) as total_trabalhadas_atendente
        FROM tbl_hd_chamado_atendente
        JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado_atendente.admin
        JOIN tbl_grupo_admin ON tbl_admin.grupo_admin = tbl_grupo_admin.grupo_admin
        JOIN chamados  ON tbl_hd_chamado_atendente.hd_chamado = chamados.hd_chamado
        GROUP BY tbl_hd_chamado_atendente.hd_chamado,
                 tbl_admin.nome_completo,
                 tbl_admin.grupo_admin,
                 tbl_grupo_admin.descricao,
                 tbl_admin.admin

      ), principais_atendentes AS (
        
        SELECT *
        FROM dados_individuais
        WHERE (hd_chamado, grupo_admin, total_trabalhadas_atendente) IN (
          SELECT hd_chamado,
                 grupo_admin,
                 MAX(total_trabalhadas_atendente)
          FROM  dados_individuais
          GROUP BY hd_chamado,
                   grupo_admin
        )

      )
      SELECT chamados.hd_chamado,
             chamados.tipo_chamado,
             CASE
                WHEN chamados.hora_faturada IS NULL
                THEN 'SEM'
                ELSE 'COM'
             END as com_sem_horas_faturadas,
             chamados.abertura,
             chamados.resolvido,
             chamados.prazo_entrega,
             chamados.atraso_na_entrega,

            -- programadores
            chamados.horas_desenvolvimento as horas_orcadas_desenvolvimento,
            p_principal.nome_completo as principal_programador,
            (chamados.horas_desenvolvimento - EXTRACT(epoch FROM horas_trabalhadas_grupo.total_trabalhadas_programador) /3600)::integer as horas_orcadas_x_trabalhadas_programador,
            p_principal.total_trabalhadas_atendente as trabalhadas_principal_programador,
            (horas_trabalhadas_grupo.total_trabalhadas_programador - p_principal.total_trabalhadas_atendente) as trabalhadas_programadores,

            -- suporte
            (chamados.horas_suporte + chamados.horas_teste) as horas_orcadas_suporte,
            s_principal.nome_completo as principal_suporte,
            ((chamados.horas_suporte + chamados.horas_teste) - EXTRACT(epoch FROM horas_trabalhadas_grupo.total_trabalhadas_suporte) /3600)::integer as horas_orcadas_x_trabalhadas_suporte,
            s_principal.total_trabalhadas_atendente as trabalhadas_principal_suporte,
            (horas_trabalhadas_grupo.total_trabalhadas_suporte - s_principal.total_trabalhadas_atendente) as trabalhadas_suportes,

            -- analistas
            chamados.horas_analise as horas_orcadas_analise,
            a_principal.nome_completo as principal_analista,
            ((chamados.horas_analise) - EXTRACT(epoch FROM horas_trabalhadas_grupo.total_trabalhadas_analistas) /3600)::integer as horas_orcadas_x_trabalhadas_analise,
            a_principal.total_trabalhadas_atendente as trabalhadas_principal_analista,
            (horas_trabalhadas_grupo.total_trabalhadas_analistas - a_principal.total_trabalhadas_atendente) as trabalhadas_analistas,

            -- geral
            chamados.total_horas_orcamento,
            horas_trabalhadas_grupo.tempo_total_gasto_pela_equipe,
            (chamados.total_horas_orcamento - EXTRACT(epoch FROM horas_trabalhadas_grupo.tempo_total_gasto_pela_equipe) /3600)::integer as total_horas_orcadas_x_trabalhadas_equipe

      FROM chamados
      LEFT JOIN horas_trabalhadas_grupo        ON horas_trabalhadas_grupo.hd_chamado       = chamados.hd_chamado

      LEFT JOIN (
        SELECT hd_chamado, 
               nome_completo, 
               total_trabalhadas_atendente,
               admin
        FROM principais_atendentes 
        WHERE principais_atendentes.grupo_admin = 1
      ) a_principal ON a_principal.hd_chamado = chamados.hd_chamado

      LEFT JOIN (
        SELECT hd_chamado, 
               nome_completo, 
               total_trabalhadas_atendente, 
               admin
        FROM principais_atendentes 
        WHERE principais_atendentes.grupo_admin = 4
      ) p_principal ON p_principal.hd_chamado = chamados.hd_chamado

      LEFT JOIN (
        SELECT hd_chamado,
               nome_completo, 
               total_trabalhadas_atendente,
               admin
        FROM principais_atendentes 
        WHERE principais_atendentes.grupo_admin = 6
      ) s_principal ON s_principal.hd_chamado = chamados.hd_chamado

      WHERE 1=1
      ".implode(" ", $cond)."
      ORDER BY chamados.hd_chamado DESC
  ";
  $resPrincipal = pg_query($con, $sqlPrincipal);
}

$TITULO    = 'Produtividade';
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
<script src='../admin/plugins/multiselect/multiselect.js'></script>
<link rel='stylesheet' type='text/css' href='../admin/plugins/multiselect/multiselect.css' />
<script>
    $(function() {

      $("#data_inicial").datepicker().mask("99/99/9999");
      $("#data_final").datepicker().mask("99/99/9999");

      $("#tipo_chamado").multiselect({
          selectedText: "selecionados # de #"
      });

      $("#status_chamado").multiselect({
          selectedText: "selecionados # de #"
      });

      var at_selecionado = '<?= $atendente ?>';

      $("#equipe").change(function(){

        if ($(this).val() != "") {

          $.ajax({
              async: false,
              url : window.location,
              type: "POST",
              data: {
                  busca_admin_equipe : true,
                  equipe_id: $(this).val()
              },
              dataType: "JSON",
              complete: function(data){

                var dados = JSON.parse(data.responseText);

                $(".div_atendentes").show();
                $("#atendentes > option:not(:first)").remove();

                $.each(dados, function(id, nome) {
                    
                  let option = $("<option></option>", {
                    value: id,
                    text: nome,
                    selected: (at_selecionado == id) ? true : false
                  });

                  $("#atendentes").append(option);

                });

              }
          });

        } else {

          $(".div_atendentes").hide();
          $("#atendentes > option:not(:first)").remove();

        }

      });
      $("#equipe").change();

      $(".abertos_resolvidos").click(function(){

        if ($(this).val() == 'abertos') {

          $(".campos_resolvidos").hide("fast");

        } else {

          $(".campos_resolvidos").show("fast");

        }

      });
      $(".abertos_resolvidos:checked").click();

    });
</script>
<style>
.table > tbody > tr > td,
.table > thead > tr > th {
  vertical-align: middle;
  text-align: center;
}

.table > thead > tr > th {
  background-color: #2b2c50;
  color: white;
}

.table tr:hover {
  background-color: #e3e4e6;
  cursor: pointer;
}

.table > tbody > tr > td:first-of-type {
  font-weight: bold;
}

.programador:last-of-type {
  border-right: solid 1px black;
}

.analista {
  background-color: #ffd4ba;
}
.programador {
  background-color: #d9d9ff;
}
.suporte {
  background-color: #fffed4;
}

.equipe {
  background-color: #e6e6e8;
}

#leg_blue {
  background-color: #d9d9ff;
}

#leg_yellow {
  background-color: #fffed4;
}

#leg_red {
  background-color: #ffd4ba;
}

#leg_grey {
  background-color: #e6e6e8;
}

.legendas {
  position: relative;
  left: 35%;
  width: 30%;
  font-weight: bolder;
}

.last {
  border-right: 1px solid black !important;
}
</style>
    <div class="container">
      <div class="panel panel-default">
        <div class="panel-heading">
        <h3 class="panel-title">Produtividade Helpdesk Telecontrol</h3>
        </div>
        <div class="panel-body">
          <form name="form_altera" method="POST" action="<?= $_SERVER['PHP_SELF'] ?>">
            <div class="row">
              <div class="col-md-2 col-sm-4 col-xs-6">
              </div>
              <div class="col-md-5 col-sm-4 col-xs-6">
                <div class="form-group">
                  <div class="input-group date col-md-8">
                    <label>
                      <input class="abertos_resolvidos" type="radio" name="abertos_resolvidos" value="abertos" <?= (!isset($_POST["abertos_resolvidos"]) || $_POST["abertos_resolvidos"] == "abertos") ? "checked" : "" ?> /> Chamados em Aberto
                    </label>
                  </div>
                </div>
              </div>
              <div class="col-md-5 col-sm-4 col-xs-6">
                <div class="form-group">
                  <div class="input-group date col-md-8">
                    <label>
                      <input class="abertos_resolvidos" type="radio" name="abertos_resolvidos" value="resolvidos" <?= ($_POST["abertos_resolvidos"] == "resolvidos") ? "checked" : "" ?> /> Chamados Resolvidos
                    </label>
                  </div>
                </div>
              </div>
            </div>
            <div class="row campos_resolvidos" hidden>
              <div class="col-md-2 col-sm-4 col-xs-6">
              </div>
              <div class="col-md-5 col-sm-4 col-xs-6">
                <div class="form-group">
                  <label for="data_inicial"><span style="color: red;">*</span> <?=traduz("Data Inicial (resolvido)")?></label>
                  <div class="input-group date col-md-5" align="center">
                    <input type="text" class="form-control" id="data_inicial" name="data_inicial" placeholder="<?=traduz("Data Inicial")?>" value="<?= $data_inicial ?>">
                    <span class="input-group-addon"><i class="glyphicon glyphicon-th"></i></span>
                  </div>
                </div>
              </div>
              <div class="col-md-5 col-sm-4 col-xs-6">
                <div class="form-group">
                  <label for="data_final"><span style="color: red;">*</span> <?=traduz('Data Final (resolvido)')?></label>
                  <div class="input-group date col-md-5" align="center">
                    <input type="text" class="form-control" id="data_final" name="data_final" placeholder="<?=traduz('Data Final')?>" value="<?= $data_final ?>">
                    <span class="input-group-addon"><i class="glyphicon glyphicon-th"></i></span>
                  </div>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-2 col-sm-4 col-xs-6">
              </div>
              <div class="col-md-5 col-sm-4 col-xs-6">
                <div class="form-group">
                  <div class="input-group date col-md-8">
                    <label>
                      <input type="radio" name="com_sem_horas" value="ambos" <?= (!isset($_POST["com_sem_horas"]) || $_POST["com_sem_horas"] == "ambos") ? "checked" : "" ?> /> Ambos
                    </label>
                    <br />
                    <label>
                      <input type="radio" name="com_sem_horas" value="com" <?= ($_POST["com_sem_horas"] == "com") ? "checked" : "" ?> /> Com h/ faturadas 
                    </label>
                    <br />
                    <label>
                      <input type="radio" name="com_sem_horas" value="sem" <?= ($_POST["com_sem_horas"] == "sem") ? "checked" : "" ?> /> Sem h/ faturadas 
                    </label>
                  </div>
                </div>
              </div>
              <div class="col-md-5 col-sm-4 col-xs-6">
                <div class="form-group">
                  <label for="data_final"><?= traduz('Tipo de Chamado') ?></label>
                  <div class="input-group date col-md-10">
                    <select class="form-control col-md-10" id="tipo_chamado" name="tipo_chamado[]" multiple>
                      <?php
                      $sqlTp = "SELECT tipo_chamado, descricao 
                              FROM tbl_tipo_chamado";
                      $resTp = pg_query($con, $sqlTp);

                      while ($dadosTp = pg_fetch_object($resTp)) { 

                        if (!isset($_POST['tipo_chamado'])) {
                          $selectedTp = "selected";
                        } else {
                          $selectedTp = (in_array($dadosTp->tipo_chamado, $tipo_chamado)) ? "selected" : "";
                        } ?>

                        <option value="<?= $dadosTp->tipo_chamado ?>" <?= $selectedTp ?>><?= $dadosTp->descricao ?></option>

                      <?php
                      } ?>
                    </select>
                  </div>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-2 col-sm-4 col-xs-6">
              </div>
              <div class="col-md-5 col-sm-4 col-xs-6">
                <div class="form-group">
                  <label for="data_inicial"><?= traduz("Equipe") ?></label>
                  <div class="input-group date col-md-8">
                    <select class="form-control" id="equipe" name="equipe">
                      <option value="">Todos</option>
                      <option value="1" <?= ($equipe == 1) ? "selected" : "" ?>>Analista</option>
                      <option value="4" <?= ($equipe == 4) ? "selected" : "" ?>>Desenvolvedor</option>
                      <option value="6" <?= ($equipe == 6) ? "selected" : "" ?>>Suporte</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="col-md-5 col-sm-4 col-xs-6 div_atendentes" hidden>
                <div class="form-group">
                  <label for="data_final"><?= traduz('Atendente') ?></label>
                  <div class="input-group date col-md-8">
                    <select name="atendente" id="atendentes" class="form-control">
                      <option value="">Selecione</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-2 col-sm-4 col-xs-6"></div>
              <div class="col-md-5 col-sm-4 col-xs-6">
                <div class="form-group">
                  <label for="data_final">Status Atual</label>
                  <div class="input-group date col-md-10">
                    <select class="form-control col-md-10" id="status_chamado" name="status_chamado[]" multiple>
                      <?php
                      $sqlSt = "SELECT DISTINCT status
                                FROM tbl_hd_chamado
                                WHERE fabrica_responsavel = 10
                                AND status IS NOT NULL";
                      $resSt = pg_query($con, $sqlSt);

                      while ($dadosSt = pg_fetch_object($resSt)) { 

                        if (!isset($_POST['status_chamado'])) {
                          $selectedSt = "selected";
                        } else {
                          $selectedSt = (in_array($dadosSt->status, $status_chamado)) ? "selected" : "";
                        } ?>

                        <option value="<?= $dadosSt->status ?>" <?= $selectedSt ?>><?= $dadosSt->status ?></option>

                      <?php
                      } ?>
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
          <td id="leg_red" width="38"></td><td style="padding-right: 30px;">Analista</td>
          <td id="leg_blue" width="38"></td><td style="padding-right: 30px;">Programador</td>
          <td id="leg_yellow" width="38"></td><td style="padding-right: 30px;">Suporte</td>
          <td id="leg_grey" width="38"></td><td style="padding-right: 30px;">Equipe</td>
        </tr>
      </table>
      </div>
      <?php
      if (isset($_POST) && count($msg_erro) == 0) {
        $arquivo      = "../admin/xls/relatorio-produtividade-".date('Y-m-d h:i:s').".xls";
      ?>
      <div class="gerar_excel_os btn_excel" style="width: 100%;text-align: center;cursor: pointer;margin: 30px;" onclick="window.open('<?= $arquivo ?>')">
        <span><img style="width: 40px;" src='../imagens/excel.png' /></span>
        <span class="txt">Arquivo Excel</span>
      </div>

      <?php

      ob_start();

      ?>
        <table class="table table-large table-bordered">
          <thead>
            <tr>
              <th>Chamado</th>
              <th>Tipo Chamado</th>
              <th>Com / Sem horas faturadas</th>
              <th>Data Abertura</th>
              <th>Data Resolvido</th>
              <th>Prazo Entrega</th>
              <th>Atraso Entrega (horas)</th>
              <?php
              if ($equipe == 1 || empty($equipe)) { ?>
                <th class="analista">Analista Principal</th>
                <th class="analista">h/ Orçadas Análise</th>
                <th class="analista">Trabalhadas Principal Analista</th>
                <th class="analista">Trabalhadas Outros Analistas</th>
                <th class="analista">Orçadas x Trabalhadas</th>
              <?php
              }

              if ($equipe == 4 || empty($equipe)) { ?>
                <th class="programador">Programador Principal</th>
                <th class="programador">h/ Orçadas Desenvolvimento</th>
                <th class="programador">Trabalhadas Programador Principal</th>
                <th class="programador">Trabalhadas Outros Programadores</th>
                <th class="programador">Orçadas x Trabalhadas</th>
              <?php
              }

              if ($equipe == 6 || empty($equipe)) { ?>
                <th class="suporte">Suporte Principal</th>
                <th class="suporte">h/ Orçadas Suporte/Teste</th>
                <th class="suporte">Trabalhadas Principal Suporte</th>
                <th class="suporte">Trabalhadas Outros Suportes</th>
                <th class="suporte">Orçadas x Trabalhadas</th>
              <?php
              } ?>
              <th>Total h/ Orçamento</th>
              <th>Total h/ Trabalhadas Equipe</th>
              <th>Total Orçadas x Trabalhadas Equipe</th>
            </tr>
          </thead>
          <tbody>
           <?php 
           while ($dados = pg_fetch_object($resPrincipal)) { ?>
            <tr>
              <td><a target="_blank" href="adm_chamado_detalhe.php?hd_chamado=<?= $dados->hd_chamado ?>&consultar=sim"><?= $dados->hd_chamado ?></a></td>
              <td><?= $dados->tipo_chamado ?></td>
              <td><?= $dados->com_sem_horas_faturadas ?></td>
              <td><?= $dados->abertura ?></td>
              <td><?= $dados->resolvido ?></td>
              <td><?= (empty($dados->prazo_entrega)) ? "Sem prazo" : $dados->prazo_entrega ?></td>
              <?php
              if (empty($dados->prazo_entrega)) { 
                $cor2 = "black"; 
              } else if ($dados->atraso_na_entrega < 0) { 
                $cor2 = "red";
              } else {
                $cor2 = "blue";
              }
              ?>
              <td style="font-weight: bolder;color: <?= $cor2 ?>">
                <?= (empty($dados->prazo_entrega)) ? "Sem prazo" : round($dados->atraso_na_entrega, 2) ?>
              </td>
              <?php
              if ($equipe == 1 || empty($equipe)) { ?>
                <td class="analista"><?= $dados->principal_analista ?></td>
                <td class="analista"><?= $dados->horas_orcadas_analise ?></td>
                <td class="analista"><?= $dados->trabalhadas_principal_analista ?></td>
                <td class="analista"><?= $dados->trabalhadas_analistas ?></td>
                <td class="analista last" style="font-weight: bolder;color: <?= ($dados->horas_orcadas_x_trabalhadas_analise < 0 && $dados->horas_orcadas_analise > 0) ? "red" : "blue" ?> ;">
                  <?= ($dados->horas_orcadas_analise == 0) ? 0 : $dados->horas_orcadas_x_trabalhadas_analise ?>
                </td>
              <?php
              } 

              if ($equipe == 4 || empty($equipe)) { ?>
                <td class="programador"><?= $dados->principal_programador ?></td>
                <td class="programador"><?= $dados->horas_orcadas_desenvolvimento ?></td>
                <td class="programador"><?= $dados->trabalhadas_principal_programador ?></td>
                <td class="programador"><?= $dados->trabalhadas_programadores ?></td>
                <td class="programador last" style="font-weight: bolder;color: <?= ($dados->horas_orcadas_x_trabalhadas_programador < 0 && $dados->horas_orcadas_desenvolvimento > 0) ? "red" : "blue" ?>;">
                  <?= ($dados->horas_orcadas_desenvolvimento == 0) ? 0 : $dados->horas_orcadas_x_trabalhadas_programador ?>
                </td>
              <?php
              }

              if ($equipe == 6 || empty($equipe)) { ?>
                <td class="suporte"><?= $dados->principal_suporte ?></td>
                <td class="suporte"><?= $dados->horas_orcadas_suporte ?></td>
                <td class="suporte"><?= $dados->trabalhadas_principal_suporte ?></td>
                <td class="suporte"><?= $dados->trabalhadas_suportes ?></td>
                <td class="suporte last" style="font-weight: bolder;color: <?= ($dados->horas_orcadas_x_trabalhadas_suporte < 0 && $dados->horas_orcadas_suporte > 0) ? "red" : "blue" ?>;">
                  <?= ($dados->horas_orcadas_suporte == 0) ? 0 : $dados->horas_orcadas_x_trabalhadas_suporte ?>
                </td>
              <?php
              } ?>
                <td class="equipe"><?= $dados->total_horas_orcamento ?></td>
                <td class="equipe"><?= $dados->tempo_total_gasto_pela_equipe ?></td>
                <td class="equipe" style="font-weight: bolder;color: <?= ($dados->total_horas_orcadas_x_trabalhadas_equipe < 0 && $dados->total_horas_orcamento > 0) ? "red" : "blue" ?>;">
                  <?= ($dados->total_horas_orcamento == 0) ? 0 : $dados->total_horas_orcadas_x_trabalhadas_equipe ?>
                </td>
            </tr>
           <?php
           } ?>
           <tr>

           </tr>
          </tbody>
        </table>
      <?php

        $excel = ob_get_contents();
        $fp = fopen($arquivo,"w");
        fwrite($fp, $excel);
        fclose($fp);

      }
include "rodape.php"
?>
