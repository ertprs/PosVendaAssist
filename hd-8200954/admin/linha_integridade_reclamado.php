<?php
/*
	@author Brayan L. Rastelli
	@description Integrar linha com defeito reclamado. HD 313970
*/
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';

	include_once '../class/AuditorLog.php';

	$admin_privilegios = "cadastros";
	$layout_menu = "cadastro";

	include 'autentica_admin.php';
	include 'funcoes.php';

	if ($login_fabrica == 175) {
		$title = traduz("INTEGRAÇÃO LINHA - DEFEITO RECLAMADO");
	} else {
		$title = traduz("INTEGRAÇÃO FAMÍLIA - DEFEITO RECLAMADO");
	}

	include 'cabecalho_new.php';

	$plugins = array(
		"shadowbox"
	);

	include("plugin_loader.php");

	/* inicio exclusao de integridade */
	if (isset($_GET['inativar']) ) {
		$id = (int) $_GET['inativar'];
		if(!empty($id)) {
			$auditorLog = new AuditorLog;
			$auditorLog->retornaDadosSelect("SELECT l.nome, 
													dr.descricao, 
													d.ativo 
											FROM tbl_diagnostico d 
											JOIN tbl_linha l using(linha) 
											JOIN tbl_defeito_reclamado dr using(defeito_reclamado) 
											WHERE d.diagnostico = {$id} 
											AND d.fabrica = {$login_fabrica}");

			$sql = "UPDATE tbl_diagnostico SET ativo = false WHERE diagnostico = {$id} AND fabrica = {$login_fabrica}";
			$res = pg_query($con, $sql);
			
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
			$auditorLog->retornaDadosSelect("SELECT l.nome, 
													dr.descricao, 
													d.ativo 
											FROM tbl_diagnostico d 
											JOIN tbl_linha l using(linha) 
											JOIN tbl_defeito_reclamado dr using(defeito_reclamado) 
											WHERE d.diagnostico = {$id} 
											AND d.fabrica = {$login_fabrica}");
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

		if(!empty($_POST['linha']) && !empty($_POST['defeito']) ) {
			$linhas = array();
			$linhas   = array();
			$defeitos = array();
			$garantias = array();

			foreach($_POST['linha'] as $linha)
				$linhas[] = ($linha);

			foreach($_POST['defeito'] as $defeito)
				$defeitos[] = ($defeito);

			foreach($_POST['garantia'] as $garantia)
				$garantias[] = ($garantia);

			pg_query($con,"BEGIN");

			for ( $i = 0; $i < count($linhas); $i++ ) {

				if($login_fabrica == 158) {
					$cond = " AND tbl_diagnostico.garantia = '".$garantias[$i]."' ";
				}else{
					$garantias[$i] = 't';
				}
				$sql = "SELECT *
						FROM tbl_diagnostico
						WHERE tbl_diagnostico.linha = ".$linhas[$i]."
						AND tbl_diagnostico.defeito_reclamado = ".$defeitos[$i]."
						AND fabrica = ".$login_fabrica."
						$cond;";
				$query = pg_query($con, $sql);
				if(pg_num_rows($query) > 0)
					continue;

				$auditorLog = new AuditorLog('insert');

				$sql = "INSERT INTO tbl_diagnostico (fabrica, linha, defeito_reclamado, garantia, admin)
						VALUES(".$login_fabrica.", ".$linhas[$i].", ".$defeitos[$i].", '".$garantias[$i]."', ".$login_admin.") RETURNING diagnostico;";
				$query = pg_query($con,$sql);

				$msg_erro = pg_errormessage($con);

				$diagnostico_id = pg_fetch_result($query, 0, "diagnostico");

				$sqlLog = "SELECT l.nome, 
									dr.descricao, 
									d.ativo 
							FROM tbl_diagnostico d 
							JOIN tbl_linha l using(linha) 
							JOIN tbl_defeito_reclamado dr using(defeito_reclamado) 
							WHERE d.diagnostico = {$diagnostico_id} 
							AND d.fabrica = {$login_fabrica}";
				
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

<?php if(isset($msg_erro) && !empty($msg_erro)) { ?>
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
                <label class='control-label' for='linha'><?=traduz('Linha')?></label>
                <div class='controls controls-row'>
                    <div class='span11'>
                        <select name="linha" id="linha" class='span12'>
                        	<option value=""></option>
                        	<?php
								$sql ="SELECT linha, nome from tbl_linha where fabrica=$login_fabrica AND ativo = 't' order by nome;";
								$res = pg_exec ($con,$sql);
								for ($y = 0 ; $y < pg_numrows($res) ; $y++){
									$linha			= trim(pg_result($res,$y,linha));
									$descricao			= trim(pg_result($res,$y,nome));
									echo "<option value='$linha'";
										if ($linha == $aux_linha) echo " SELECTED ";
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
                        <select name="defeito" id="defeito" class='span12'>
                        	<option value=""></option>

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
            <div class='span2'>
                <div class='control-group <?=(in_array("garantia", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='garantia'><?=traduz('Garantia')?></label>
                    <div class='controls controls-row'>
                        <div class="span12">
                            <h5 class='asteristico'>*</h5>
                            <select name='garantia' id='garantia' class='span12'>
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
		<input type="button" class="btn" value='<?=traduz("Adicionar")?>' onclick="addDefeito()" />
	</p>
	<br />
	<form action="<?= $_SERVER['PHP_SELF'] ?>" method="POST">
		<div class="row-fluid">
			<div class="span1"></div>
			<div class="span10">
				<table id="integracao" class="table table-bordered" width="100%">
					<thead>
						<tr class="titulo_coluna">
							<th><?=traduz('Linha')?></th>
							<th><?=traduz('Defeito Reclamado')?></th>
							<? if ($login_fabrica == 158) { ?>
								<th><?=traduz('Garantia')?></th>
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
$int_cadastrados = "SELECT
					tbl_diagnostico.diagnostico,
					tbl_diagnostico.ativo,
					tbl_diagnostico.garantia,
					tbl_defeito_reclamado.descricao AS defeito_descricao,
					tbl_defeito_reclamado.codigo AS defeito_codigo,
					tbl_linha.nome AS linha_descricao
					FROM tbl_diagnostico
					JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_diagnostico.defeito_reclamado AND tbl_defeito_reclamado.fabrica = $login_fabrica
					JOIN tbl_linha ON tbl_linha.linha = tbl_diagnostico.linha
					WHERE tbl_diagnostico.fabrica = $login_fabrica
					AND tbl_diagnostico.defeito_constatado IS NULL
					ORDER BY linha_descricao, defeito_descricao ASC;";

$query = pg_query($con,$int_cadastrados);
if (pg_num_rows($query) > 0) { ?>
	<div id="cadastrados">
		<br />
		<table class="table table-bordered" width="100%">
			<thead>
				<tr class="titulo_coluna">
					<th colspan="100%"><?=traduz('Defeitos Cadastrados')?></th>
				</tr>
				<tr class="titulo_coluna">
					<th><?=traduz('Linha')?></th>
					<th><?=traduz('Defeito Reclamado')?></th>
					<th><?=traduz('Ativo')?></th>
					<? if ($login_fabrica == 158) { ?>
						<th><?=traduz('Garantia')?></th>
					<? } ?>
					<th><?=traduz('Ações')?></th>
				</tr>
			</thead>
			<tbody>
				<? for ($i = 0; $i < pg_numrows($query); $i++) {
					$linha = trim(pg_result($query,$i,linha_descricao));
					$defeito = trim(pg_result($query,$i,defeito_descricao));
					$defeito_codigo = trim(pg_result($query,$i,defeito_codigo));
					$id = trim(pg_result($query,$i,diagnostico));
					$ativo = trim(pg_result($query,$i,ativo));
					$garantia = trim(pg_result($query,$i,garantia));
					$imagem_ativo = ($ativo == "t") ? 'status_verde.png' : 'status_vermelho.png';
					$imagem_garantia = ($garantia == "t") ? 'status_verde.png' : 'status_vermelho.png';
					$btn_acao = ($ativo == "t") ? '<a href="linha_integridade_reclamado.php?inativar='.$id.'" class="btn btn-danger" style="width: 50px;">Inativar</a>' : '<a href="linha_integridade_reclamado.php?ativar='.$id.'" class="btn btn-success" style="width: 50px;">Ativar</a>';
					$class_btn = ($ativo == "t") ? 'btn-danger' : 'btn-success'; ?>
					<tr>
						<td><?= $linha; ?></td>
						<td><?= (in_array($login_fabrica, array(158)) ? $defeito_codigo." - ".$defeito : $defeito); ?></td>
						<td class="tac"><img src="imagens/<?= $imagem_ativo; ?>" /></td>
						<? if ($login_fabrica == 158) { ?>
							<td class="tac"><img src="imagens/<?= $imagem_garantia; ?>" /></td>
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

<?php } ?>

<script type="text/javascript">

	$(function(){

		Shadowbox.init();

	});

	i = 0;
	function deletaitem(n){
		var linha_tr = $("#"+n);
		linha_tr.remove();	
	}				
	function addDefeito() {

		var defeito = $('#defeito').val();
		var linha = $("#linha").val();
		var garantia = $("#garantia").val();
		var txt_defeito = $('#defeito').find('option').filter(':selected').text();
		var txt_linha = $('#linha').find('option').filter(':selected').text();
		var txt_garantia = $('#garantia').find('option').filter(':selected').text();

		var cor = (i % 2) ? "#F7F5F0" : "#F1F4FA";
		var garantia;

		<? if($login_fabrica == 158) { ?>
			 garantia = '<td class="tac"><input type="hidden" value="'+garantia+'" name="garantia['+i+']" />'+txt_garantia+'</td>';
		<? } ?>
		var htm_input = '<tr id="'+i+'" bgcolor="'+cor+'"><td><input type="hidden" value="'+linha+'" name="linha['+i+']" />'+txt_linha+'</td><td><input type="hidden" value="'+defeito+'" name="defeito['+i+']" />'+txt_defeito+'</td>'+garantia+'<td class="tac"><button type="button" onclick="deletaitem('+i+')" class="btn">Remover</button></td></tr>';

		if (linha  === '') {
			alert('<?=traduz("Escolha uma Linha")?>');
			return false;
		}

		if (defeito  === '') {
			alert('<?=traduz("Escolha um Defeito")?>');
			return false;
		}

		if (garantia  === '') {
			alert('<?=traduz("Escolha se em Garantia ou não")?>');
			return false;
		}else {
			i++;
			$("#tabela").css("display","block");
			$(htm_input).appendTo("#integracao");
		}
	}
	
</script>

<?php include 'rodape.php'; ?>
