<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastro, gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

try {

	$riMirror = new \Mirrors\Ri\RiMirror($login_fabrica, $login_admin);

	if (isset($_POST['btn_acao'])) {

		$dadosRequest = $riMirror->formataCampos($_POST);

		$riMirror->gravaAdminFollowup($dadosRequest);

	}

	$dadosFollowup = $riMirror->consultaAdminFollowup();

	$dadosFollowup = array_map_recursive("utf8_decode", $dadosFollowup);

} catch(\Exception $e){

    $msg_erro["msg"][] = utf8_decode($e->getMessage());

}

$layout_menu = "gerencia";
$title = "Cadastro Grupo Follow-up";
include 'cabecalho_new.php';

$plugins = array(
   "bootstrap3",
   "shadowbox",
   "dataTableAjax",
   "datepicker",
   "mask",
   "autocomplete"
);

include "plugin_loader.php";
?>
<script>

	$(function(){

		$(".btn-exclui").click(function(){

			let that = $(this);

			let riGrupo = $(that).data("ri_grupo");

			$.ajax({
	            url: "relatorio_informativo/relatorio_informativo_ajax.php",
	            type: "POST",
	            data: {
	                excluir_ri_grupo: true,
	                riGrupo: riGrupo
	            },
	            dataType: "json",
	            beforeSend: function () {
	                loading("show");
	            },
	            complete: function (data) {
	                
	            	if (data.success) {

	            		alert("Registro excluído");
	            		$(that).closest("tr").remove();

	            	} else {

	            		alert("Erro ao excluir registro");

	            	}

	            	loading("hide");

	            }
	        });

		});

	});

</script>
<style>
  #menu_sidebar, #menu_sidebar2 {
      margin-left: 1000px !important;
  }
</style>
<?php
if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
} else if (count($msg_success["msg"]) > 0) { ?>
	<div class="alert alert-success">
		<h4><?=implode("<br />", $msg_success["msg"])?></h4>
    </div>
<?php
} ?>
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' style="height: 175px;">
	<div class='titulo_tabela'>Cadastro admin/follow-up</div>
	<br />
	<div class="col-sm-8 col-sm-offset-2">
        <div class="form-group">
        	Admin: <br />
            <select class="form-control" id="admin" name="admin" style="width: 350px;">
                <option value="">Selecione o admin</option>
                <?php
                $sqlAdminAnalise = "SELECT admin, nome_completo
                                    FROM tbl_admin
                                    WHERE fabrica = {$login_fabrica}
                                    AND json_field('analise_ri', parametros_adicionais) = 't'
                                    AND ativo";
                $resAdminAnalise = pg_query($con, $sqlAdminAnalise);

                while ($dadosAdm = pg_fetch_object($resAdminAnalise)) {
                ?>
                    <option value="<?= $dadosAdm->admin ?>"><?= $dadosAdm->nome_completo ?></option>
                <?php
                } ?>
            </select>
        </div>
        <div class="form-group">
        	Follow-up<br />
            <select class="form-control" name="followup" style="width: 350px;">
                <option value="">Selecione o follow-up</option>
                <?php
                $sqlFollowup = "SELECT ri_followup, nome
                                    FROM tbl_ri_followup
                                    WHERE fabrica = {$login_fabrica}
                                    AND ativo";
                $resFollowup = pg_query($con, $sqlFollowup);

                while ($dados = pg_fetch_object($resFollowup)) {
                ?>
                    <option value="<?= $dados->ri_followup ?>"><?= $dados->nome ?></option>
                <?php
                } ?>
            </select>
        </div>
    </div>
    <div class="col-sm-12" style="text-align: center;">
    	<br />
		<input type="submit" class='btn btn-default' name="btn_acao" value="Gravar" />
	</div>
</form>

<table id='listaRi' class='table table-bordered'>
  <thead>
    <tr class="titulo_coluna">
      <th>Atendente</th>
      <th>Follow-up</th>
      <th>Ações</th>
    </tr>
  </thead>
  <tbody>
  	<?php
  	foreach ($dadosFollowup as $chave => $valor) { ?>
  		<tr>
  			<td><?= $valor["nome_admin"] ?></td>
  			<td><?= $valor["nome_followup"] ?></td>
  			<td>
  				<center>
  					<button class="btn-exclui btn btn-danger" data-ri_grupo="<?= $valor["ri_grupo"] ?>">
  						Excluir
  					</button>
  				</center>
  			</td>
  		</tr>
  	<?php
  	} ?>
  </tbody>
</table>
<?php
include "rodape.php";
?>
