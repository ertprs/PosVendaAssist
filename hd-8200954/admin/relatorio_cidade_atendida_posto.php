<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

// if ($_POST["listar"] == "Listar" OR $_POST["listar_todos"] == "Listar Todos") {

if(isset($_POST['listar']) OR isset($_POST['listar_todos'])){
  $codigo_posto       = $_POST['codigo_posto'];
  $descricao_posto    = $_POST['descricao_posto'];


  if(isset($_POST['listar'])){
    if(empty($codigo_posto)){
      $msg_erro["msg"][]    = "Favor Digitar o Posto";
      $msg_erro["campos"][] = "posto";
    }
  }


  if(strlen($codigo_posto) > 0 or strlen($descricao_posto) > 0){
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

    if(!pg_num_rows($res)) {
      $msg_erro["msg"][]    = "Posto não encontrado";
      $msg_erro["campos"][] = "posto";
    }else{
      $posto = pg_fetch_result($res, 0, "posto");
    }
  }

  if (!count($msg_erro["msg"])) {


    if(isset($_POST['listar_todos'])){
      $cond_posto = "";
    }else{
      $cond_posto = " AND tbl_posto_fabrica_ibge.posto = {$posto} ";
    }

    $sql = "SELECT tbl_posto_fabrica.contato_cidade AS municipio,
                    tbl_posto_fabrica.contato_estado AS uf_municipio,
                    tbl_posto.nome AS nome_posto,
                    tbl_posto_fabrica.codigo_posto,
                    tbl_cidade.nome AS cidade,
                    tbl_cidade.estado AS uf
            FROM tbl_posto_fabrica_ibge
            JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica_ibge.posto
            JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
            JOIN tbl_cidade ON tbl_cidade.cidade = tbl_posto_fabrica_ibge.cidade
            LEFT JOIN tbl_posto_fabrica_ibge_tipo ON tbl_posto_fabrica_ibge_tipo.posto_fabrica_ibge_tipo = tbl_posto_fabrica_ibge.posto_fabrica_ibge_tipo AND tbl_posto_fabrica_ibge_tipo.fabrica = $login_fabrica
            WHERE tbl_posto_fabrica_ibge.fabrica = $login_fabrica
			AND   tbl_posto_fabrica.credenciamento <> 'DESCREDENCIADO'
            $cond_posto
            ORDER BY municipio, nome_posto, cidade";
    $resSubmit = pg_query($con, $sql);
  }


  if ($_POST["gerar_excel"]) {
    if (pg_num_rows($resSubmit) > 0) {
      $data = date("d-m-Y-H:i");

      $fileName = "relatorio_cidade_atendida_posto-{$data}.xls";

      $file = fopen("/tmp/{$fileName}", "w");
      $thead = "
        <table border='1'>
          <thead>
            <tr>
              <th colspan='3' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
                RELATÓRIO DE CIDADES ATENDIDAS PELO POSTO
              </th>
            </tr>
            <tr>
              <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Município</th>
              <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>UF</th>
              <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Posto</th>
              <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Cidades Atendidas</th>
              <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>UF</th>
            </tr>
          </thead>
          <tbody>
      ";
      fwrite($file, $thead);

      for ($i = 0; $i < pg_num_rows($resSubmit); $i++) {
        $uf_municipio = pg_fetch_result($resSubmit, $i, 'uf_municipio');
        $municipio    = pg_fetch_result($resSubmit, $i, 'municipio');
        $nome_posto   = pg_fetch_result($resSubmit, $i, 'nome_posto');
        $codigo_posto = pg_fetch_result($resSubmit, $i, 'codigo_posto');
        $cidade       = pg_fetch_result($resSubmit, $i, 'cidade');
        $uf           = pg_fetch_result($resSubmit, $i, 'uf');

        $body .="
            <tr>
              <td nowrap align='left' valign='top'>{$municipio}</td>
              <td nowrap align='center' valign='top'>{$uf_municipio}</td>
              <td nowrap align='left' valign='top'>{$codigo_posto} - {$nome_posto}</td>
              <td nowrap align='left' valign='top'>{$cidade}</td>
              <td nowrap align='center' valign='top'>{$uf}</td>
            </tr>";
      }
      fwrite($file, $body);
      fwrite($file, "
            <tr>
              <th colspan='3' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de ".pg_num_rows($resSubmit)." registros</th>
            </tr>
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

$layout_menu = "gerencia";
$title = "RELATÓRIO DE CIDADES ATENDIDAS PELO POSTO";
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
    $.autocompleteLoad(Array("posto"));
    Shadowbox.init();

    $("span[rel=lupa]").click(function () {
      $.lupa($(this));
    });
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

<div class="row">
  <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
    <br/>

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

    <p><br/>
      <!-- <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
      <input type='hidden' id="btn_click" name='btn_acao' value='' /> -->
      <input type="submit" class="btn" value='Listar' name='listar'>
      <input type="submit" class="btn" value='Listar Todos' name='listar_todos'>
    </p><br/>
</form>

<?php
if (isset($resSubmit)) {
    if (pg_num_rows($resSubmit) > 0) {
      echo "<br />";

      if (pg_num_rows($resSubmit) > 500) {
        $count = 500;
        ?>
        <div id='registro_max'>
          <h6>Em tela serão mostrados no máximo 500 registros, para visualizar todos os registros baixe o arquivo Excel no final da tela.</h6>
        </div>
      <?php
      } else {
        $count = pg_num_rows($resSubmit);
      }
    ?>
      <table id="resultado_relatorio_cidade_atendida_posto" class='table table-striped table-bordered table-hover table-fixed' >
        <thead>
          <tr class='titulo_coluna' >
            <th>Município</th>
            <th>UF</th>
            <th>Posto</th>
            <th>Cidades Atendidas</th>
            <th>UF</th>
          </tr>
        </thead>
        <tbody>
          <?php
          for ($i = 0; $i < $count; $i++) {
            $uf_municipio    = pg_fetch_result($resSubmit, $i, 'uf_municipio');
            $municipio    = pg_fetch_result($resSubmit, $i, 'municipio');
            $nome_posto   = pg_fetch_result($resSubmit, $i, 'nome_posto');
            $codigo_posto = pg_fetch_result($resSubmit, $i, 'codigo_posto');
            $cidade       = pg_fetch_result($resSubmit, $i, 'cidade');
            $uf           = pg_fetch_result($resSubmit, $i, 'uf');

            $body = "<tr>
                  <td class='tal'>{$municipio}</td>
                  <td class='tal'>{$uf_municipio}</td>
                  <td class='tal'>{$codigo_posto} - {$nome_posto}</td>
                  <td class='tal'>{$cidade}</td>
                  <td class='tal'>{$uf}</td>
            </tr>";
            echo $body;
          }
          ?>
        </tbody>
      </table>

      <?php
      if ($count > 50) {
      ?>
        <script>
          $.dataTableLoad({ table: "#resultado_relatorio_cidade_atendida_posto" });
        </script>
      <?php
      }
      ?>

      <br />

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



include 'rodape.php';?>
