<?php
/*
	@author Brayan L. Rastelli
	@description Integrar familia com defeito reclamado. HD 313970
*/
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';

	include_once '../class/AuditorLog.php';

	$admin_privilegios = "cadastros";
	$layout_menu = "cadastro";

	include 'autentica_admin.php';

	$title = traduz("CAD-6110 : INTEGRAÇÃO FAMÍLIA - DEFEITO RECLAMADO");

	include 'cabecalho_new.php';

	$plugins = array(
		"shadowbox",
		"multiselect"
	);

	include("plugin_loader.php");

	/* inicio exclusao de integridade */
	if (isset($_GET['inativar']) ) {

		$id = (int) $_GET['inativar'];
		if(!empty($id)) {

			$auditorLog = new AuditorLog;
			$auditorLog->retornaDadosSelect("SELECT * FROM tbl_diagnostico WHERE diagnostico = {$id} AND fabrica = {$login_fabrica}");

			$sql = "UPDATE tbl_diagnostico SET ativo = false WHERE diagnostico = {$id} AND fabrica = {$login_fabrica}";
			$res = pg_query($con, $sql);
			// $sql = pg_query($con, 'UPDATE tbl_diagnostico SET ativo = false WHERE diagnostico =' . $id . ' AND fabrica = ' . $login_fabrica );

			if (pg_affected_rows($res) > 0){

				$auditorLog->retornaDadosSelect()->enviarLog('update', 'tbl_diagnostico', $login_fabrica);

				$msg = traduz("Inativado com Sucesso!");

			}else{

				$msg_erro = traduz("Essa Integridade não pôde ser excluída, está sendo utilizada!");

			}
		}

	}
	/* fim exclusao */

	/* inicio do ativar de integridade */
	if (isset($_GET['ativar']) ) {

		$id = (int) $_GET['ativar'];
		if(!empty($id)) {

			$auditorLog = new AuditorLog;
			$auditorLog->retornaDadosSelect("SELECT * FROM tbl_diagnostico WHERE diagnostico = {$id} AND fabrica = {$login_fabrica}");

			// $sql = pg_query($con, 'DELETE FROM tbl_diagnostico WHERE diagnostico =' . $id . ' AND fabrica = ' . $login_fabrica );
			$sql = pg_query($con, 'UPDATE tbl_diagnostico SET ativo = true WHERE diagnostico =' . $id . ' AND fabrica = ' . $login_fabrica );
			if ( pg_affected_rows($sql) > 0 ){

				$auditorLog->retornaDadosSelect()->enviarLog('update', 'tbl_diagnostico', $login_fabrica);

				$msg = traduz('Ativado com Sucesso!');

			} else {
				$msg_erro = traduz('Essa Integridade não pôde ser ativada');
			}
		}

	}
	/* fim exclusao */

	// ----- Inicio do cadastro ----------

	if ( isset($_POST['gravar'] ) ) {

		if(!empty($_POST['familia']) && !empty($_POST['defeito']) ) {
			$familias = array();
			$linhas   = array();
			$defeitos = array();
			$garantias = array();

			foreach($_POST['familia'] as $familia)
				$familias[] = ($familia);

			foreach($_POST['defeito'] as $defeito)
				$defeitos[] = ($defeito);

			if ($login_fabrica == 158) {
				foreach($_POST['garantia'] as $garantia){
					$values = explode('_', $garantia);
					$garantias[] = array(
						'garantia' => "'".$values[0]."'",
						'tipo_atendimento' => $values[1]
					);
				}
			}else{
			foreach($_POST['garantia'] as $garantia)
				$garantias[] = ($garantia);
			}

			pg_query($con,"BEGIN");

			for ( $i = 0; $i < count($familias); $i++ ) {

				if($login_fabrica == 158) {
					$cond = " AND tbl_diagnostico.garantia = ".$garantias[$i]['garantia']." AND tbl_diagnostico.tipo_atendimento = ".$garantias[$i]['tipo_atendimento'];
				}else{
					$garantias[$i] = 't';
				}
				$sql = "SELECT *
						FROM tbl_diagnostico
						WHERE tbl_diagnostico.familia = ".$familias[$i]."
						AND tbl_diagnostico.defeito_reclamado = ".$defeitos[$i]."
						AND fabrica = ".$login_fabrica."
						$cond;";

				$query = pg_query($con, $sql);
				if(pg_num_rows($query) > 0)
					continue;

				$auditorLog = new AuditorLog('insert');

				if ($login_fabrica == 158) {
					$sql = "INSERT INTO tbl_diagnostico(
						fabrica,
						familia,
						defeito_reclamado,
						garantia,
						tipo_atendimento,
						admin
					)VALUES(
						$login_fabrica,
						$familias[$i],
						$defeitos[$i],
						".$garantias[$i]['garantia'].",
						".$garantias[$i]['tipo_atendimento'].",
						$login_admin
					) RETURNING diagnostico;";
				}else{
				$sql = "INSERT INTO tbl_diagnostico (fabrica, familia, defeito_reclamado, garantia, admin)
						VALUES(".$login_fabrica.", ".$familias[$i].", ".$defeitos[$i].", '".$garantias[$i]."', ".$login_admin.") RETURNING diagnostico;";
				}

				$query = pg_query($con,$sql);

				$msg_erro = pg_errormessage($con);

				$diagnostico_id = pg_fetch_result($query, 0, "diagnostico");

				$sqlLog = "SELECT * FROM tbl_diagnostico WHERE diagnostico = {$diagnostico_id}";

				$auditorLog->retornaDadosSelect($sqlLog)->enviarLog('insert', 'tbl_diagnostico', $login_fabrica);

			}

			if(empty($msg_erro)) {
				$msg = traduz('Gravado com Sucesso!');
				pg_query($con, "COMMIT");
			}
			else
				pg_query($con, "ROLLBACK");

		}
		else
			$msg_erro = traduz('Escolha um Defeito e uma Família ');

	}
	// fim cadastro
?>

<?php if ($login_fabrica == 151) { /*HD - 6184665*/
	$aux_multiple = 'multiple="multiple"';
	$aux_option   = ''; ?>

	 <script>
	    $(function() {
	        $("#familia, #defeito").multiselect({
	           selectedText: "selecionados # de #"
	        });
	    });
    </script>
<?php } else {
	$aux_multiple = '';
	$aux_option = '<option value=""></option>';
} 

if(isset($msg_erro) && !empty($msg_erro)) { ?>
	<div class="alert alert-error tac"><h4><?=$msg_erro?></h4></div>
<?php }
if(isset($msg)) { ?>
	<div class="alert alert-success tac"><h4><?=$msg?></h4></div>
<?php } ?>

<div class="row">
    <b class="obrigatorio pull-right">* <?=traduz('Campos obrigatórios')?></b>
</div>

<div class="tc_formulario">
	<div class="titulo_tabela"><?=traduz('Paramêtros de Cadastro')?></div>
	<br />
	<div class="row-fluid">
		<div class="span2"></div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='familia'><?=traduz('Família')?></label>
                <div class='controls controls-row'>
                    <div class='span11'>
                        <select name="familia" id="familia" class='span12' <?=$aux_multiple;?>>
                        	<?=$aux_option;?>

                        	<?php
								$sql ="SELECT familia, descricao from tbl_familia where fabrica=$login_fabrica AND ativo = 't' order by descricao;";
								$res = pg_exec ($con,$sql);
								for ($y = 0 ; $y < pg_numrows($res) ; $y++){
									$familia			= trim(pg_result($res,$y,familia));
									$descricao			= trim(pg_result($res,$y,descricao));
									echo "<option value='$familia'";
										if ($familia == $aux_familia) echo " SELECTED ";
									echo ">$descricao</option>";
								}
							?>

                        </select>
                    </div>
                </div>
            </div>
        </div>
		<div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='defeito'><?=traduz('Defeito Reclamado')?></label>
                <div class='controls controls-row'>
                    <div class='span11'>
                        <select name="defeito" id="defeito" class='span12' <?=$aux_multiple;?>>
                        	<?=$aux_option;?>

							<?php
								$sql ="SELECT defeito_reclamado, descricao, codigo from tbl_defeito_reclamado where fabrica=$login_fabrica and ativo='t' order by descricao;";
								$res = pg_exec ($con,$sql);
								for ($y = 0 ; $y < pg_numrows($res) ; $y++){
									$defeito_reclamado   = trim(pg_result($res,$y,defeito_reclamado));
									$descricao = trim(pg_result($res,$y,descricao));
									$codigo = trim(pg_result($res,$y,codigo));
									echo '<option value="'.$defeito_reclamado.'"';

									if (in_array($login_fabrica, array(30,158))) {
										echo ">$codigo - $descricao</option>";
									} else {
										echo ">$descricao</option>";
									}
								}
							?>

                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="span2"></div>
    </div>
    <!-- Garantia/Fora Garantia -->
    <? if (in_array($login_fabrica, array(158))) { ?>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group'>
                    <div class='control-label'><?=traduz('Tipo')?></div>
                    <label class="radio inline" for="tipoGar">
                        <input id="tipoGar" type="radio" name="tipo" value="273" <?=(empty($tipo) || $tipo == 273) ? 'checked' : ''?>>
                        <?=traduz('Garantia')?></label>
                    <label class="radio inline" for="tipoPiso">
                        <input id="tipoPiso" type="radio" name="tipo" value="272" <?=($tipo == 272) ? 'checked' : ''?>>
                        <?=traduz('Atendimento Tipo Piso')?></label>
                </div>
            </div>
            <div class="span5">
                <div class="control-group">
                    <label class="control-label" for="garantia"><?=traduz('Garantia')?></label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <?php $hidden = ($tipo == 272) ? "style='display: none;'" : '' ?>
                            <select name='garantia' id='garantia' class='span5' <?=$hidden?>>
                                <option value=""><?=traduz('Selecione')?></option>
                                <option value="t"><?=traduz('Sim')?></option>
                                <option value="f"><?=traduz('Não')?></option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
    <? } ?>
    <p>
		<input type="button" class="btn" value="Adicionar" onclick="addDefeito()" />
	</p>
	<br />
	<form action="<?= $_SERVER['PHP_SELF'] ?>" method="POST">
		<div class="row-fluid">
			<div class="span1"></div>
			<div class="span10">
				<table id="integracao" class="table table-bordered" width="100%">
					<thead>
						<tr class="titulo_coluna">
							<th><?=traduz('Família')?></th>
							<th><?=traduz('Defeito Reclamado')?></th>
							<? if ($login_fabrica == 158) { ?>
								<th><?=traduz('Garantia')?></th>
								<th><?=traduz('Piso')?></th>
							<? } ?>
							<th><?=traduz('Ações')?></th>
						</tr>
					</thead>
				</table>
				<p class="tac">
					<input type="submit" value='<?=traduz("Gravar")?>' class="btn" name="gravar" />
				</p>
			</div>
		</div>
	</form>
</div>

<?php
if ($_GET['listar']=='piso') {
    $filtro = 'AND tbl_diagnostico.tipo_atendimento = 272';
}
$int_cadastrados = "
      SELECT tbl_diagnostico.diagnostico
           , tbl_diagnostico.ativo
           , tbl_diagnostico.garantia
           , tbl_defeito_reclamado.descricao AS defeito_descricao
           , tbl_defeito_reclamado.codigo AS defeito_codigo
           , tbl_familia.descricao AS familia_descricao
           , tbl_diagnostico.tipo_atendimento
        FROM tbl_diagnostico
        JOIN tbl_defeito_reclamado
          ON tbl_defeito_reclamado.defeito_reclamado = tbl_diagnostico.defeito_reclamado
         AND tbl_defeito_reclamado.fabrica           = $login_fabrica
        JOIN tbl_familia ON tbl_familia.familia      = tbl_diagnostico.familia
       WHERE tbl_diagnostico.fabrica             = $login_fabrica
         AND tbl_diagnostico.defeito_constatado IS NULL
         $filtro
    ORDER BY familia_descricao, defeito_descricao ASC;";
$query = pg_query($con,$int_cadastrados);

if (pg_num_rows($query) > 0) {
    $captionText = isFabrica(158)
        ? "<th colspan='4'>".traduz("Defeitos Cadastrados")."</th>".
          ($_GET['listar'] == 'piso'
            ? "<th colspan='2'><a class='btn btn-mini btn-info' href='{$_SERVER['PHP_SELF']}'>Listar Todos</a></th>"
            : "<th colspan='2'><a class='btn btn-mini btn-info' href='?listar=piso'>Listar Piso</a></th>"
          )
        : "<th colspan='100%'>".traduz("Defeitos Cadastrados")."</th>"; // Este é para o resto
?>
	<div id="cadastrados">
		<br />
		<table class="table table-bordered" width="100%">
			<thead>
            <tr class="titulo_coluna"><?=$captionText?></tr>
				<tr class="titulo_coluna">
					<th><?=traduz('Família')?></th>
					<th><?=traduz('Defeito Reclamado')?></th>
					<th><?=traduz('Ativo')?></th>
					<? if ($login_fabrica == 158) { ?>
						<th><?=traduz('Garantia')?></th>
						<th><?=traduz('Piso')?></th>
					<? } ?>
					<th><?=traduz('Ações')?></th>
				</tr>
			</thead>
			<tbody>
    <? for ($i = 0; $i < pg_numrows($query); $i++) {
        $familia         = trim(pg_result($query,$i,familia_descricao));
        $defeito         = trim(pg_result($query,$i,defeito_descricao));
        $defeito_codigo  = trim(pg_result($query,$i,defeito_codigo));
        $id              = trim(pg_result($query,$i,diagnostico));
        $ativo           = trim(pg_result($query,$i,ativo));
        $garantia        = trim(pg_result($query,$i,garantia));
        $imagem_ativo    = ($ativo == "t")    ? 'status_verde.png' : 'status_vermelho.png';
        $imagem_garantia = ($garantia == "t") ? 'status_verde.png' : 'status_vermelho.png';
        if ($login_fabrica == 158) {
            $tipo_atendimento = pg_fetch_result($query, $i, "tipo_atendimento");
            $imagem_piso = ($tipo_atendimento == 272) ? 'status_verde.png' : 'status_vermelho.png';
        }
        $btn_acao = ($ativo == "t")
            ? '<a href="familia_integridade_reclamado.php?inativar='.$id.'" class="btn btn-danger" style="width: 50px;">Inativar</a>'
            : '<a href="familia_integridade_reclamado.php?ativar='.$id.'" class="btn btn-success" style="width: 50px;">Ativar</a>';
        $class_btn = ($ativo == "t") ? 'btn-danger' : 'btn-success'; ?>
        <tr>
            <td><?= $familia; ?></td>
            <td><?= (in_array($login_fabrica, array(158)) ? $defeito_codigo." - ".$defeito : $defeito); ?></td>
            <td class="tac"><img src="imagens/<?= $imagem_ativo; ?>" /></td>
            <? if ($login_fabrica == 158) { ?>
                <td class="tac"><img src="imagens/<?= $imagem_garantia; ?>" /></td>
                <td class="tac"><img src="imagens/<?= $imagem_piso; ?>" /></td>
            <? } ?>
            <td class="tac"><?= $btn_acao; ?></td>
         </tr>
    <? } ?>
			</tbody>
		</table>

	</div>

	<br />
	<div class='tac'>
		<a rel='shadowbox' href='relatorio_log_alteracao_new.php?parametro=tbl_diagnostico&titulo=INTEGRAÇÃO FAMÍLIA - DEFEITO RECLAMADO'><?=traduz('Visualizar Log Auditor')?></a>
	</div>
	<br />

<? } else { ?>
	<div class="alert">
		<h5><?=traduz('Nenhum resultado encontrado')?>.
    <?php if ($_GET['listar'] == 'piso'): ?>
        <a class='btn btn-mini btn-default' href='<?=$_SERVER['PHP_SELF']?>'><?=traduz('Listar Todos')?></a>
    <?php endif; ?>
	</h5></div>
<? } ?>

<script type="text/javascript">

	$(function(){

		Shadowbox.init();

	    $('input[name=tipo]').on('change', function(){
	        if ($(this).val() == '272') {
	            $('#garantia').hide();
	        }else{
	            $('#garantia').show();
	        }
	    });

	});

	i = 0;
	function deletaitem(n){
		var linha_tr = $("#"+n);
		linha_tr.remove();
	}
	function addDefeito() {

		var defeito = $('#defeito').val();
		var familia = $("#familia").val();
		var tipo         = $('input[name=tipo]:checked').val();
		var garantia = $("#garantia").val();
		var txt_defeito = $('#defeito').find('option').filter(':selected').text();
		var txt_familia = $('#familia').find('option').filter(':selected').text();
		var txt_garantia = $('#garantia').find('option').filter(':selected').text();

		if (txt_garantia == 'Selecione' && tipo == 273) {
			alert('Selecione uma opção de garantia (Sim ou Não)');
			return;
		}

		var cor = (i % 2) ? "#F7F5F0" : "#F1F4FA";
		var garantia;

		<? if($login_fabrica == 158) { ?>
			if (tipo == 272) {
				tipo_texto   = 'SIM';
				txt_garantia = '';
				garantia     = 'f';
			}else{
				tipo_texto = '';
			}

			garantia = '<td class="tac"><input type="hidden" value="'+garantia+'_'+tipo+'" name="garantia['+i+']" />'+txt_garantia+'</td><td>'+tipo_texto+'</td>';
		<? } ?>
		var htm_input = '<tr id="'+i+'" bgcolor="'+cor+'"><td><input type="hidden" value="'+familia+'" name="familia['+i+']" />'+txt_familia+'</td><td><input type="hidden" value="'+defeito+'" name="defeito['+i+']" />'+txt_defeito+'</td>'+garantia+'<td class="tac"><button type="button" onclick="deletaitem('+i+')" class="btn">Remover</button></td></tr>';

		<?php
		if ($login_fabrica == 151) { /*HD - 6184665*/ ?>
			if (familia == null || defeito == null) {
				alert('Favor escolher ao menos um "Defeito" e / ou "Família"');
				return false;
			} else {
				var contador_familia = familia.length;
				var contador_defeito = defeito.length;

				for (var wx = 0; wx < contador_familia; wx++) {
					for (var yz = 0; yz < contador_defeito; yz++) {
						cor         = (i % 2) ? "#F7F5F0" : "#F1F4FA";
						txt_familia = $('#familia [value=' + familia[wx] + ']').text();
						txt_defeito = $('#defeito [value=' + defeito[yz] + ']').text();

						var htm_input = '<tr id="'+i+'" bgcolor="'+cor+'"><td><input type="hidden" value="'+familia[wx]+'" name="familia['+i+']" />'+txt_familia+'</td><td><input type="hidden" value="'+defeito[yz]+'" name="defeito['+i+']" />'+txt_defeito+'</td>'+garantia+'<td class="tac"><button type="button" onclick="deletaitem('+i+')" class="btn">Remover</button></td></tr>';

						i++;
						$("#tabela").css("display","block");
						$(htm_input).appendTo("#integracao");
					}
				}
			}
		<?php } else { ?>
			var htm_input = '<tr id="'+i+'" bgcolor="'+cor+'"><td><input type="hidden" value="'+familia+'" name="familia['+i+']" />'+txt_familia+'</td><td><input type="hidden" value="'+defeito+'" name="defeito['+i+']" />'+txt_defeito+'</td>'+garantia+'<td class="tac"><button type="button" onclick="deletaitem('+i+')" class="btn">Remover</button></td></tr>';

			if (familia  === '') {
				alert('<?=traduz("Escolha uma Família")?>');
				return false;
			}

			if (defeito  === '') {
				alert('<?=traduz("Escolha um Defeito")?>');
				return false;
			}

			if (garantia  === '') {
				alert('<?=traduz("Escolha se em Garantia ou não")?>');
				return false;
			} else {
				i++;
				$("#tabela").css("display","block");
				$(htm_input).appendTo("#integracao");
			}
		<?php } ?>
	}

</script>
<?php include 'rodape.php';

