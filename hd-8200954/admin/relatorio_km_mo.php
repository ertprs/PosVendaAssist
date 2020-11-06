<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if($_POST["btn_acao"] == "submit"){
  $data_inicial       = $_POST['data_inicial'];
  $data_final         = $_POST['data_final'];
  $codigo_posto       = $_POST['codigo_posto'];
  $descricao_posto    = $_POST['descricao_posto'];
  $status             = $_POST['status'];


  ## CONSULTA POSTO ##
  if (strlen($codigo_posto) > 0 or strlen($descricao_posto) > 0){
    $sql = "SELECT tbl_posto_fabrica.posto
        FROM tbl_posto
        JOIN tbl_posto_fabrica USING(posto)
        WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
        AND (
          (UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}'))
          OR
          (TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('{$descricao_posto}'), 'LATIN-9'))
        )";
    $res = pg_query($con ,$sql);

    if (!pg_num_rows($res)) {
      $msg_erro["msg"][]    = "Posto não encontrado";
      $msg_erro["campos"][] = "posto";
    } else {
      $posto = pg_fetch_result($res, 0, "posto");
    }
  }
  ## FIM CONSULTA POSTO ##

  ## VALIDAÇÂO DATA ##
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

      if (strtotime($aux_data_inicial.'+12 months') < strtotime($aux_data_final) ) {
        $msg_erro["msg"][] = 'O intervalo entre as datas não pode ser maior que 6 meses';
        $msg_erro["campos"][] = "data";
      }
    }
  }
  ## FIM VALIDAÇÃO DATA ##

  if (!count($msg_erro["msg"])) {
    ## CONDIÇÕES
    if (!empty($posto)) {
      $cond_posto = " AND tbl_posto_fabrica.posto = $posto ";
    }

    if(strlen($status) > 0){

      $cond_status = " AND tbl_posto_fabrica.credenciamento = '$status'";
    }

    $sql = "SELECT tbl_posto.posto,
            tbl_posto.nome,
            tbl_posto.cidade,
            tbl_posto.estado,
            tbl_posto_fabrica.nome_fantasia,
            tbl_posto_fabrica.codigo_posto,
            tbl_posto_fabrica.credenciamento,
            tbl_extrato.extrato,
            tbl_extrato.mao_de_obra,
            TO_CHAR(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao,
            tbl_extrato.deslocamento
            FROM tbl_posto
            JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
            JOIN tbl_credenciamento ON tbl_credenciamento.posto = tbl_posto.posto
            JOIN tbl_extrato ON tbl_extrato.posto = tbl_posto_fabrica.posto AND tbl_extrato.fabrica = $login_fabrica
            WHERE tbl_posto_fabrica.fabrica = $login_fabrica
            AND tbl_extrato.data_geracao between '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
            $cond_posto
            $cond_status
            GROUP BY tbl_posto.posto,
            tbl_posto.nome,
            tbl_posto.cidade,
            tbl_posto.estado,
            tbl_posto_fabrica.codigo_posto,
            tbl_posto_fabrica.credenciamento,
            tbl_extrato.extrato,
            tbl_extrato.mao_de_obra,
            tbl_extrato.data_geracao,
            tbl_extrato.deslocamento,
            tbl_posto_fabrica.nome_fantasia
            ORDER BY tbl_posto.nome, tbl_extrato.data_geracao";
           //echo $sql;exit;
    $resSubmit = pg_query($con, $sql);
  }

  if ($_POST["gerar_excel"]) {
    if (pg_num_rows($resSubmit) > 0) {
      $data = date("d-m-Y-H:i");

      $fileName = "relatorio_km_mo-{$data}.xls";

      $file = fopen("/tmp/{$fileName}", "w");

      $thead = "
        <table border='1'>
          <thead>
            <tr>
              <th colspan='16' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
                RELATÓRIO KM MÃO DE OBRA
              </th>
            </tr>
            <tr>
              <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Código</th>
              <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nome Posto</th>
              <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nome Fantasia</th>
              <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Cidade</th>
              <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Estado</th>
              <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Região</th>
              <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Valor M.O</th>
              <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data M.O</th>
              <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Reajuste M.O %</th>
              <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Valor KM Mês</th>
              <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data geração KM</th>
              <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Reajuste KM</th>
              <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>KM Fixo</th>
              <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Status do Posto</th>
              <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Alteração Status</th>
              <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Motivo</th>
            </tr>
          </thead>
          <tbody>
          ";
          fwrite($file, $thead);

          for ($i = 0; $i < pg_num_rows($resSubmit); $i++) {
            $posto                  = pg_fetch_result($resSubmit, $i, 'posto');
            $nome                   = pg_fetch_result($resSubmit, $i, 'nome');
            $nome_fantasia          = pg_fetch_result($resSubmit, $i, 'nome_fantasia');
            $cidade                 = pg_fetch_result($resSubmit, $i, 'cidade');
            $estado                 = pg_fetch_result($resSubmit, $i, 'estado');
            $codigo_posto           = pg_fetch_result($resSubmit, $i, 'codigo_posto');
            $credenciamento         = pg_fetch_result($resSubmit, $i, 'credenciamento');
            $data_alteracao         = pg_fetch_result($resSubmit, $i, 'data_alteracao');
            $mao_de_obra            = pg_fetch_result($resSubmit, $i, 'mao_de_obra');
            $data_geracao           = pg_fetch_result($resSubmit, $i, 'data_geracao');
            $deslocamento           = pg_fetch_result($resSubmit, $i, 'deslocamento');

            if(strlen($valor_mao_obra_ant) > 0){
              $diferenca_mao_obra = (($mao_de_obra - $valor_mao_obra_ant) / $mao_de_obra) * 100;
              $diferenca_km = (($deslocamento - $valor_km_ant) / $deslocamento) * 100;
            }

            // $sql2 = "SELECT tbl_credenciamento.texto,
            //           TO_CHAR(tbl_credenciamento.data, 'DD/MM/YYYY') AS data_alteracao,
            //           FROM tbl_credenciamento
            //           WHERE tbl_credenciamento.fabrica = $login_fabrica
            //           AND tbl_credenciamento.posto = $posto
            //           AND tbl_credenciamento.texto <> ''
            //           GROUP BY tbl_credenciamento.texto, tbl_credenciamento.data";
            // $res2 = pg_query($con, $sql2);

            // if (pg_num_rows($res2) > 0) {
            //   $motivo = pg_fetch_result($res2, 0, texto);
            // }

            $sqlKm = "SELECT parametros_adicionais
                      FROM tbl_posto_fabrica
                      WHERE fabrica = $login_fabrica
                      AND posto = $posto
            ";
            $resKm = pg_query($con,$sqlKm);
            $parametros_adicionais = pg_fetch_result($resKm,0,parametros_adicionais);

            $adicionais = json_decode($parametros_adicionais,true);

            $fixo_km_valor = $adicionais['valor_km_fixo'];
            if(!empty($fixo_km_valor)){
              $km_fixo = "Sim";
            }else{
              $km_fixo = "Não";
            }

            $sqlRegiao = "SELECT descricao
                          FROM tbl_regiao
                          WHERE fabrica = $login_fabrica
                          AND estados_regiao ILIKE '%$estado%'";
            $resRegiao = pg_query($con,$sqlRegiao);
            if (pg_num_rows($resRegiao) > 0) {
              $regiao = pg_fetch_result($resRegiao, 0, descricao);
            }

            $sql_motivo = "SELECT texto,
                    TO_CHAR(data, 'DD/MM/YYYY') AS data_alteracao
                    FROM tbl_credenciamento
                    WHERE tbl_credenciamento.posto = $posto
                    AND tbl_credenciamento.fabrica = $login_fabrica
                    ORDER BY data DESC";
            $res_motivo = pg_query($con,$sql_motivo);
              if (pg_num_rows($res_motivo) > 0) {
              $motivo = pg_fetch_result($res_motivo, 0, texto);
              $data_alteracao = pg_fetch_result($res_motivo, 0, data_alteracao);
            }

            $body .= "<tr>
                      <td nowrap align='left' valign='top'>{$codigo_posto}</td>
                      <td nowrap align='left' valign='top'>{$nome}</td>
                      <td nowrap align='left' valign='top'>{$nome_fantasia}</td>
                      <td nowrap align='center' valign='top'>{$cidade}</td>
                      <td nowrap align='center' valign='top'>{$estado}</td>
                      <td nowrap align='center' valign='top'>{$regiao}</td>
                      <td nowrap align='center' valign='top'>{$mao_de_obra}</td>
                      <td nowrap align='center' valign='top'>{$data_geracao}</td>
                      <td nowrap align='center' valign='top'>".number_format($diferenca_mao_obra,2,',','.')."%</td>
                      <td nowrap align='center' valign='top'>{$deslocamento}</td>
                      <td nowrap align='center' valign='top'>{$data_geracao}</td>
                      <td nowrap align='center' valign='top'>".number_format($diferenca_km,2,',','.')." %</td>
                      <td nowrap align='center' valign='top'>{$km_fixo}</td>
                      <td nowrap align='center' valign='top'>{$credenciamento}</td>
                      <td nowrap align='center' valign='top'>{$data_alteracao}</td>
                      <td nowrap align='center' valign='top'>{$motivo}</td>
                    </tr>";
            $valor_mao_obra_ant = $mao_de_obra;
            $valor_km_ant = $deslocamento;
          }
          fwrite($file, $body);
          fwrite($file, "
            </tbody>
        </table>
      ");

      fclose($file);
      if (file_exists("/tmp/{$fileName}")) {
        system("mv /tmp/{$fileName} xls/{$fileName}");

        echo "xls/{$fileName}";
      }

    }
    exit;
  }


}



$layout_menu = "financeiro";
$title = "RELATÓRIO DE KM x Mão de obra";
include 'cabecalho_new.php';


$plugins = array(
  "autocomplete",
  "datepicker",
  "shadowbox",
  "mask",
  "dataTable"
);

include("plugin_loader.php");

?>

<script type="text/javascript">
  var hora = new Date();
  var engana = hora.getTime();

  $(function() {
    $.datepickerLoad(Array("data_final", "data_inicial"));
    $.autocompleteLoad(Array("posto"));
    Shadowbox.init();

    $("span[rel=lupa]").click(function () {
      $.lupa($(this));
    });
  });

  $(function() {
      var table = new Object();
      table['table'] = '#resultado_km_mo';
      table['type'] = 'custom';
      table['config'] = Array('paginacao', 'resultados_por_pagina', 'pesquisa');
      $.dataTableLoad(table);
  });

  function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
    $("#descricao_posto").val(retorno.nome);
  }
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

<!-- HTML PAGINA -->

<!-- Campos obrigatorios -->
<div class="row">
  <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<!-- /// -->

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >

  <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
  <br/>
  <!-- Data Inicial / Data Final -->
  <div class='row-fluid'>
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
  <!-- /// -->

  <!-- Pesquisa Posto -->
  <div class='row-fluid'>
    <div class='span2'></div>
    <div class='span4'>
      <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
        <label class='control-label' for='codigo_posto'>Código Posto</label>
        <div class='controls controls-row'>
          <div class='span7 input-append'>
            <input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
            <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
            <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
          </div>
        </div>
      </div>
    </div>
    <div class='span4'>
      <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
        <label class='control-label' for='descricao_posto'>Nome Posto</label>
        <div class='controls controls-row'>
          <div class='span12 input-append'>
            <input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
            <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
            <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
          </div>
        </div>
      </div>
    </div>
    <div class='span2'></div>
  </div>
  <!-- /// -->

  <!-- Pesquisa Status -->
  <div class='row-fluid'>
    <div class='span2'></div>
    <div class='span4'>
      <div class='control-group <?=(in_array("status", $msg_erro["campos"])) ? "error" : ""?>'>
        <label class='control-label' for='status'>Status</label>
        <div class='controls controls-row'>
          <div class='span4'>
            <select name="status" id="status">
              <option value="">TODOS</option>
              <option value='CREDENCIADO'<? if ($credenciamento== "CREDENCIADO") echo " SELECTED ";?> >CREDENCIADO</option>
              <option value='DESCREDENCIADO' <? if ($credenciamento== "DESCREDENCIADO") echo " SELECTED "; ?> >DESCREDENCIADO</option>
              <option value='EM CREDENCIAMENTO' <? if ($credenciamento== "EM CREDENCIAMENTO") echo " SELECTED "; ?> >EM CREDENCIAMENTO</option>
              <option value='EM DESCREDENCIAMENTO' <? if ($credenciamento== "EM DESCREDENCIAMENTO") echo " SELECTED "; ?> >EM DESCREDENCIAMENTO</option>
             </select>
          </div>
        </div>
      </div>
    </div>
  </div>
  <p><br/>
    <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
    <input type='hidden' id="btn_click" name='btn_acao' value='' />
  </p><br/>
</form>
</div>
<?php
if (isset($resSubmit)) {

  if (pg_num_rows($resSubmit) > 0) {
    $count = pg_num_rows($resSubmit);
?>
  <table id="resultado_km_mo" class='table table-striped table-bordered table-hover table-large' >
    <thead>
      <tr class='titulo_coluna' >
        <th>Código</th>
        <th>Nome Posto</th>
        <th>Nome Fantasia</th>
        <th>Cidade</th>
        <th>Estado</th>
        <th>Região</th>
        <th>Valor M.O</th>
        <th>Data M.O</th>
        <th>Reajuste M.O %</th>
        <th>Valor KM Mês</th>
        <th>Data geração KM</th>
        <th>Reajuste KM</th>
        <th>KM Fixo</th>
        <th>Status do Posto</th>
        <th>Data Alteração Status</th>
        <th>Motivo</th>
      </tr>
    </thead>
    <tbody>
  <?php

    for ($i = 0; $i < $count; $i++) {
      $posto                  = pg_fetch_result($resSubmit, $i, 'posto');
      $nome                   = pg_fetch_result($resSubmit, $i, 'nome');
      $nome_fantasia          = pg_fetch_result($resSubmit, $i, 'nome_fantasia');
      $cidade                 = pg_fetch_result($resSubmit, $i, 'cidade');
      $estado                 = pg_fetch_result($resSubmit, $i, 'estado');
      $codigo_posto           = pg_fetch_result($resSubmit, $i, 'codigo_posto');
      $credenciamento         = pg_fetch_result($resSubmit, $i, 'credenciamento');
      $data_alteracao         = pg_fetch_result($resSubmit, $i, 'data_alteracao');
      $mao_de_obra            = pg_fetch_result($resSubmit, $i, 'mao_de_obra');
      $data_geracao           = pg_fetch_result($resSubmit, $i, 'data_geracao');
      $deslocamento           = pg_fetch_result($resSubmit, $i, 'deslocamento');

      $sqlKm = "SELECT parametros_adicionais
                FROM tbl_posto_fabrica
                WHERE fabrica = $login_fabrica
                AND posto = $posto
      ";
      $resKm = pg_query($con,$sqlKm);
      $parametros_adicionais = pg_fetch_result($resKm,0,parametros_adicionais);

      $adicionais = json_decode($parametros_adicionais,true);

      $fixo_km_valor = $adicionais['valor_km_fixo'];
      if(!empty($fixo_km_valor)){
        $km_fixo = "<img src='imagens/status_verde.png' border='0' title='Ativo' alt='Ativo'>";
      }else{
        $km_fixo = "<img src='imagens/status_vermelho.png' border='0' align='center' title='Inativo' alt='Inativo'>";
      }

      $sqlRegiao = "SELECT descricao
                    FROM tbl_regiao
                    WHERE fabrica = $login_fabrica
                    AND estados_regiao ILIKE '%$estado%'";
      $resRegiao = pg_query($con,$sqlRegiao);
      if (pg_num_rows($resRegiao) > 0) {
        $regiao = pg_fetch_result($resRegiao, 0, descricao);
      }

      $sql_motivo = "SELECT texto,
                    TO_CHAR(data, 'DD/MM/YYYY') AS data_alteracao
                    FROM tbl_credenciamento
                    WHERE tbl_credenciamento.posto = $posto
                    AND tbl_credenciamento.fabrica = $login_fabrica
                    ORDER BY data DESC";
      $res_motivo = pg_query($con,$sql_motivo);
      if (pg_num_rows($res_motivo) > 0) {
        $motivo = pg_fetch_result($res_motivo, 0, texto);
        $data_alteracao = pg_fetch_result($res_motivo, 0, data_alteracao);
      }

      if(strlen($valor_mao_obra_ant) > 0){

        $diferenca_mao_obra = (($mao_de_obra - $valor_mao_obra_ant) / $mao_de_obra) * 100;

        $diferenca_km = (($deslocamento - $valor_km_ant) / $deslocamento) * 100;
      }

      $body = "<tr>
                <td class='tac' id='$posto'>{$codigo_posto}</td>
                <td>{$nome}</td>
                <td>{$nome_fantasia}</td>
                <td class='tac'>{$cidade}</td>
                <td class='tac'>{$estado}</td>
                <td class='tac'>{$regiao}</td>
                <td class='tac'>{$mao_de_obra}</td>
                <td class='tac'>{$data_geracao}</td>
                <td class='tac'>".number_format($diferenca_mao_obra,2,',','.')."%</td>
                <td class='tac'>{$deslocamento}</td>
                <td class='tac'>{$data_geracao}</td>
                <td class='tac'>".number_format($diferenca_km,2,',','.')." %</td>
                <td class='tac'>{$km_fixo}</td>
                <td class='tac'>{$credenciamento}</td>
                <td class='tac'>{$data_alteracao}</td>
                <td class='tac'>{$motivo}</td>
              </tr>";
              echo $body;
      $valor_mao_obra_ant = $mao_de_obra;
      $valor_km_ant = $deslocamento;
    }
  ?>
    </tbody>
  </table>


  <?php
    $jsonPOST = excelPostToJson($_POST);
  ?>

  <div id='gerar_excel' class="btn_excel">
    <input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
    <span><img src='imagens/excel.png' /></span>
    <span class="txt">Gerar Arquivo Excel</span>
  </div>
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

include 'rodape.php';
?>