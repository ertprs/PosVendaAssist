<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="gerencia";
include 'autentica_admin.php';
include 'funcoes.php';


if($_POST["btn_acao"] == "submit"){
  $data_inicial       = $_POST['data_inicial'];
  $data_final         = $_POST['data_final'];
  $status             = $_POST['status'];

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
        $msg_erro["msg"][] = 'O intervalo entre as datas não pode ser maior que 1 ano';
        $msg_erro["campos"][] = "data";
      }
    }
  }
  ## FIM VALIDAÇÃO DATA ##

  if (!count($msg_erro["msg"])) {
  	## CONDIÇÕES
  	if(strlen($status) > 0){
      $cond_status = " AND tbl_posto_fabrica.credenciamento = '$status'";
    }

		$sql = "SELECT	tbl_credenciamento.posto,
							tbl_posto.cnpj,
							tbl_posto.nome,
							tbl_posto_fabrica.credenciamento
					FROM	tbl_credenciamento
					JOIN	tbl_posto            ON tbl_credenciamento.posto = tbl_posto.posto
					JOIN	tbl_posto_fabrica    ON tbl_credenciamento.posto = tbl_posto_fabrica.posto
					WHERE	tbl_credenciamento.fabrica = $login_fabrica
					AND		tbl_posto_fabrica.fabrica  = $login_fabrica
					AND		tbl_credenciamento.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
					$cond_status
					GROUP BY tbl_credenciamento.posto,
					tbl_posto.cnpj,
					tbl_posto.nome,
					tbl_posto_fabrica.credenciamento
					ORDER BY tbl_posto.nome";
		$resSubmit = pg_query($con, $sql);
	}
}

$layout_menu = "gerencia";
$title = "Relatório de Credenciamento dos postos por período";
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
    Shadowbox.init();
  });

  $(function() {
      var table = new Object();
      table['table'] = '#relatorio_postos_credenciados';
      table['type'] = 'custom';
      table['config'] = Array('paginacao', 'resultados_por_pagina', 'pesquisa');
      $.dataTableLoad(table);
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

if(isset($resSubmit)) {
	if (pg_num_rows($resSubmit) > 0) {
		$count = pg_num_rows($resSubmit);
?>
	<table id="relatorio_postos_credenciados" class='table table-striped table-bordered table-hover table-fixed' >
    <thead>
    	<tr>
    		<th colspan="3" class="titulo_tabela">Postos Credenciados no período</th>
    	</tr>
      <tr class='titulo_coluna' >
        <th>Cnpj</th>
        <th>Nome Posto</th>
        <th>Status do Posto</th>
      </tr>
    </thead>
    <tbody>
    <?php
    	for ($i = 0; $i < $count; $i++) {
	      $posto 	= pg_fetch_result($resSubmit, $i, 'posto');
	      $cnpj		= pg_fetch_result($resSubmit, $i, 'cnpj');
	      $nome 	= pg_fetch_result($resSubmit, $i, 'nome');
	      $status = pg_fetch_result($resSubmit, $i, 'credenciamento');

	      $sql_data = "SELECT TO_CHAR(tbl_credenciamento.data, 'DD/MM/YYYY HH24:MI:SS') as data
                    FROM tbl_credenciamento
                    WHERE tbl_credenciamento.posto = $posto
                    AND tbl_credenciamento.fabrica = $login_fabrica
                    AND tbl_credenciamento.status = '$status'
                    AND tbl_credenciamento.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
                    ORDER BY data DESC LIMIT 1";
        $res_data = pg_query($con,$sql_data);
	      if (pg_num_rows($res_data) > 0) {
	        $data = pg_fetch_result($res_data, 0, data);
	      }
	      $body = "<tr>
                	<td class='tac'>{$cnpj}</td>
                	<td>{$nome}</td>
                	<td>$status - $data</td>
              	</tr>";
      	echo $body;

	    }
    ?>
    </tbody>
  </table>

<?
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
