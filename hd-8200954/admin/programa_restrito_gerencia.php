<?php

include "dbconfig.php";
//$dbnome = "teste";          // ATÉ FUNCIONAR LEGAL VAI FICAR MESMO NO TESTE!!!!
include "includes/dbconnect-inc.php";
if ($dbnome=='teste') $banco_de_testes = "  (BANCO DE TESTE!!)";
$admin_privilegios="gerencia";
include 'autentica_admin.php';
include_once "../class/AuditorLog.php";

$auditor = new AuditorLog();

//  Erros na hora de criar ou excluir uma restrição:
$erros_restricao = array(
	 0 => "Operação realizada com sucesso",
	 1 => "Erro ao gravar informações no banco de dados",
	 2 => "Já existe restrição para este programa",
	 3 => "O programa não está restrito",
	 4 => "O usuário informado não pertence a este fabricante",
	 5 => "Sem restrições!",
	 6 => "Já existe restrição para algum dos programas",
	 7 => "Algum desses programas não está restrito"
);

function iif($condition, $val_true, $val_false = "") {
	if (is_numeric($val_true) and is_null($val_false)) $val_false = 0;
	if (is_null($val_true) or is_null($val_false) or !is_bool($condition)) return null;
	return ($condition) ? $val_true : $val_false;
}

/**
 * Descrição da função
 *
 * @param int $admin
 * @param string $programa
 * @return boolean
 */
function esta_restrito($admin,$programa) {
	global $con, $login_fabrica;
    $sql = "SELECT programa FROM tbl_programa_restrito WHERE fabrica=$login_fabrica AND admin=$admin AND programa='$programa'";
	$resp= pg_query($con, $sql);
	return (pg_num_rows($resp)==1);
}

function restringir($admin,$programa) {
	global $con, $login_fabrica;
	if (esta_restrito($admin,$programa)) return 2;
	if ($programa == "null") {
		return 1;
	}
    echo $sql = "INSERT INTO tbl_programa_restrito (fabrica, programa, admin) VALUES ($login_fabrica, '$programa',$admin)";
	$res = pg_query($con, $sql);
	echo pg_last_error($con);
	return (pg_affected_rows($res)==1) ? 0 : 1;
}

/****************************************************************************************************
 *  Esta função por enquanto não vai ser usada, pois a rotina de atualização apaga as restrições    *
 *  atuais para inserir as novas, não apaga. Mas se precisar fazer que o botão excluir trabalhe     *
 *  direto, a função já existe e é só fazer uma de ajax=excluir com acao=liberar e nome=programa    *
 ***************************************************************************************************/
function liberar($admin,$programa) {
	global $con, $login_fabrica;
	if($programa == 'tudo'){
		$sql = "SELECT programa FROM tbl_programa_restrito WHERE admin = $admin AND fabrica = $login_fabrica";
		$programas = pg_fetch_all(pg_query($con, $sql));
		$restritos = pg_fetch_pairs($con, $sql);

		$logar = array_diff($restritos, $programas);
		foreach ($logar as $excluir) {
			$log_id  = $fabrica . "*" . basename($excluir, '.php');
			$auditor = new AuditorLog();
			$auditor->retornaDadosTabela(
				'tbl_programa_restrito', array(
					"programa" => $programa,
					"admin" => $admin,
					"fabrica" => $fabrica
				), 'login_unico' // ignorar
			);
			$sql = "DELETE FROM tbl_programa_restrito WHERE programa = '$excluir' AND fabrica = $login_fabrica AND admin = $admin";
			$res = pg_query($con, $sql);
			if(is_resource($res)){
				$auditor->retornaDadosTabela()->enviarLog("delete", "tbl_programa_restrito", $log_id);
			}else{
				$auditor = null;
				return 1;
			}
			$sql = "DELETE FROM tbl_programa_restrito WHERE admin = $admin AND fabrica = $login_fabrica";
			$res = pg_query($con, $sql);
			if(is_resource($res)){
				return 0;
			}else{
				return 1;
			}
		}
	}
	foreach ($programa as $prog ) {
		if (!esta_restrito($admin,$prog)) return 3;
		$log_id  = $fabrica . "*" . basename($prog, '.php');
		$auditor = new AuditorLog();
		$auditor->retornaDadosTabela(
			'tbl_programa_restrito', array(
				"programa" => $programa,
				"admin" => $admin,
				"fabrica" => $fabrica
			), 'login_unico' // ignorar
		);
		$sql = "DELETE FROM tbl_programa_restrito WHERE programa = '$prog' AND admin = $admin AND fabrica = $login_fabrica";
		$res = pg_query($con, $sql);
		if(is_resource($res)){
			$auditor->retornaDadosTabela()->enviarLog("delete", "tbl_programa_restrito", $log_id);
			return 0;
		}else{
			$auditor = null;
			return 1;
		}
	}
}
	

// Funções do banco para iniciar e concluir transações
// Quando fizer testes, mudar a função 'commit' para o 'rollback' ;)
function begin()	{global $con;return pg_query($con, "BEGIN TRANSACTION");}
// function commit()	{global $con;return pg_query($con, "ROLLBACK TRANSACTION");}
function commit()	{global $con;return pg_query($con, "COMMIT TRANSACTION");}
function rollback()	{global $con;return pg_query($con, "ROLLBACK TRANSACTION");}

function atualiza_restricao($admin, $fabrica, $programa, $acao='restringir', $stopOnError=false) {
    begin();
	foreach ($programa as $prog) {
		if($acao == 'restringir')
			$auditor = new AuditorLog('insert');
		else {
			$auditor = new AuditorLog();
			$auditor->retornaDadosTabela(
				'tbl_programa_restrito', array(
					"programa" => $prog,
					"fabrica" => $fabrica,
					"admin" => $admin
				), 'login_unico' // ignorar
			);
		}
		$resultado = $acao($admin,$prog);
		if ($resultado == 1) {rollback(); return 1;}    // Erro ao gravar dados no banco, tem que parar...
	    if ($resultado != 0 and $stopOnError) {rollback(); return $resultado + 4;}   // Erro em caso de array
			
		$log_id = $fabrica . "*" . basename($prog, '.php');

		if($acao=='restringir'){
			$auditor->retornaDadosTabela(
				'tbl_programa_restrito', array(
					"programa" => $prog,
					"fabrica" => $fabrica,
					"admin" => $admin
				), 'login_unico' // ignorar
			)->enviarLog('insert', 'tbl_programa_restrito', $log_id);	
		}else{
			$auditor->retornaDadosTabela()->enviarLog('delete', 'tbl_programa_restrito', $log_id);			
		}
	}
	commit();
	return 0;
}

if (strlen($ajax = $_REQUEST['ajax'])>0) {  //Seta a variável e confere o length para saber se é uma requisição ajax...

/*  Listado de usuários admin   */
	if ($ajax=='usuarios') {    // Devolve a lista de admins do fabricante, por ordem alfabética de NOME COMPLETO pronta para inserir no SELECT
		$sql = "SELECT admin,login FROM tbl_admin WHERE fabrica=$login_fabrica AND ativo IS TRUE ORDER BY nome_completo";
		$resa= pg_query($con, $sql);
		if ($resa !== false) {
			$numads = pg_num_rows($resa);
			if ($numads == 0) {
				echo "5";
				exit;
			}
			$admins = pg_fetch_all($resa);
			echo "<option value='".$_POST['editar_admin']."'></option>\n";
			foreach ($admins as $adminInfo) {
				$edit_admin = ($_REQUEST['editar_admin'] == $adminInfo['admin'])?" SELECTED":"";
				echo "<option value='".$adminInfo['admin']."'$edit_admin>".$adminInfo['login']."</option>\n";
			}
			exit;
		}
	}

/*  Listado de programas restritos para esses admin */
	if ($ajax=='programas') {
	    if (strlen($admin = trim($_REQUEST['admin']))==0) {
			//echo "KO|Bad request!";
			exit;
		}
	    $sql = "SELECT programa FROM tbl_programa_restrito WHERE fabrica=$login_fabrica AND admin=$admin ORDER BY programa";
		$resp= pg_query($con, $sql);
		if ($resp !== false) {
			$nprogs = pg_num_rows($resp);
			if ($nprogs == 0) {
				echo "OK|".$erros_restricao[5];
				exit;
			}
			$progs = pg_fetch_all($resp);
			foreach ($progs as $programa) {
			    $prog_c = $programa['programa'];
			    $prog_n = basename($prog_c, '.php');
				echo "<option value='$prog_c'>$prog_n</option>\n";
			}
			exit;
		}
	}

/**
 * Atualiza as restrições com o conteúdo do <SELECT> #programas para o admin #admin
 *
 * @param	int				$admin
 * @param	string/array	$programas
 * @return	ERRORLEVEL
 * @author:	Manuel López <manolo@telecontrol.com.br>
 * Requires:begin(), commit(), rollback(), atualiza_restricao(), iif()
 */
	if ($ajax=='atualiza') {
	    if (!isset($_POST['admin'])) {
			echo "KO|alert alert-error|Bad request!";
			exit;
		}
	    $admin = $_POST['admin'];
		$progs = $_POST['programas'];
// 	    echo "KO|Var: $progs\nArray: "; var_dump($progs);
// 	    exit;

		if (!is_array($progs)) {
		    begin();
			if (!file_exists("/var/www$progs")) {
			    echo "KO|alert alert-error|Não existe o programa $progs!! Quem é você?!";
			    exit;
			}
			$atualizou = atualiza_restricao($admin,$login_fabrica,$progs);
			if ($atualizou == 0) {
				echo 'OK|alert alert-success|Restrições atualizadas com sucesso!';
			} else {
				echo 'KO|alert alert-error|Erro ao ' , $acao , ' o acesso à tela: ' , $progs , '.';
			}
		} else {
			liberar($admin,"tudo");
			$atualizou = atualiza_restricao($admin,$login_fabrica,$progs);
			if ($atualizou == 0) {
				echo 'OK|alert alert-success|Restrições atualizadas com sucesso!';
				commit();
			} else {
				echo 'KO|alert alert-error|Erro ao ' , $acao , ' o acesso às telas selecionadas.';
				rollback();
			}
		}
		exit;
	}
	
//  Chama com ajax=apagar & admin = #usuario.value
	if ($ajax=='apagar') {
		$admin = $_REQUEST['admin'];
		if ($admin != '') {
			begin();
			$retorno = liberar($admin, "tudo");
			if ($retorno == 0) {
				commit();
				echo "OK|alert alert-success|Todas as restrições para este usuário foram excluídas!";
			} else {
				rollback();
			    echo "KO|alert alert-error|Erro ao excluir as restrições deste usuário!";
			}
			exit;
		} else {
			echo "KO|alert alert-error|Usuário não encontrado.";
		}
	}

	if ($ajax=='apagar_programa') {
		$admin    = $_REQUEST['admin'];
		$progr = $_REQUEST['programa'];
		if ($admin != '' && $progr != '') {
			begin();
			$retorno = liberar($admin, $progr);
			if ($retorno == 0) {
				commit();
				echo "OK|alert alert-success|A restrição para esse programa foi excluída.";
			} else {
				rollback();
			    echo "KO|alert alert-error|Erro ao excluir essa restrição!";
			}
			exit;
		} else if($admin == ''){
			echo "KO|alert alert-error|Usuário não encontrado.";
		}else if($progr == ''){
			echo "KO|alert alert-error|Restrição não encontrado.";
		}
	}
}

$title = "Restrição de Acesso - Gerenciamento";
$layout_menu = "gerencia";
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
$().ready(function() {
	phpSelf = '<?=$PHP_SELF?>'; /* ;)   */
	lendo = '<option></option><option></option><option value="">Atualizando...</option>';

	var edit_admin = '<?=$_GET['edit_admin']?>';

	$.post(phpSelf,{'ajax':'usuarios','editar_admin':edit_admin},function(data) {
	    if (data.indexOf('|') > 0) {
	        info = data.split("|");
	        alert(info[1]);
		} else {
		    $('#admin').html(data);
		    if ($('#admin').val() != "") $('#admin').change();
		    $('#org').html(data).val('');
	    }
	});
	$('#admin').change(function() {
		user = $('#admin').val();
		$('#programas').html(lendo);
		$.post(phpSelf,{'ajax':'programas','admin':user},function(data) {
		    if (data.indexOf('|') > 0) {
		        info = data.split("|");
		        if (info[0] == "KO") alert(info[1]);
				$('#programas').html('');
		    } else {
				$('#programas').html(data);
			}
		});
	});

	$('#org').change(function() {
		user = $('#org').val();
		$('#restricoes').html(lendo);
		$.post(phpSelf,{'ajax':'programas','admin':user},function(data) {
		    if (data.indexOf('|') > 0) {
		        info = data.split("|");
		        alert(info[1]);
				$('#restricoes').html('');
		    } else {
				$('#restricoes').html(data);
			}
		});
	});

/*	$('#programas').change(function () {
		$('fieldset[for=usuario] button').removeAttr('disabled');
	});

	$('#restricoes').change(function () {
		$('fieldset[for=restricoes] button').removeAttr('disabled');
	});
*/
	$('#kill_all').click(function() {
		user = $('#admin').val();
		if(user != '')
		{
			var res = window.confirm("Deseja realmente excluir todas as restrições desse admin?");
	    	if(res==true){
	    		$("#info_aguarde").css("display", "block");
	    		user = $('#admin').val();
			    $.post(phpSelf,{'ajax':'apagar','admin':user},function(data) {
				    if (data.indexOf('|') > 0) {
				        info = data.split("|");
				        $("#info_class").addClass(info[1]);
				        $("#info_message").html(info[2]);
				        $("#info_aguarde").css("display", "none");
				        $("#informativo").css("display", "block");
				        if(info[0]=="OK"){
				        	$('#admin').change();
				        }
					} else alert ('erro de AJAX!'+data);
				});
	    	}else{
	    		alert("Exclusões canceladas.");
	    	}
		}else{
			alert("Nenhum usuário selecionado.");			
		}
	});
    $('#atualiza').click(function() {
    	$("#info_aguarde").css("display", "block");
		user = $('#admin').val();
		$('#programas option').attr('selected','selected');
		var progs= $('#programas').val();
	    $.post(phpSelf,{'ajax':'atualiza','admin':user,'programas[]':progs},function(data) {
		    if (data.indexOf('|') > 0) {
		        info = data.split("|");
		        $("#info_class").addClass(info[1]);
		    	$("#info_message").html(info[2]);
		    	$("#info_aguarde").css("display", "none");
		        $("#informativo").css("display", "block");
			} else {
			    $('#admin').change();
			}
		});
	});

    $('#excl_sel').click(function() {
    	user = $('#admin').val();
    	if(user != '')
    	{
    		var res = window.confirm("Deseja realmente excluir essa restrição?");
	    	if(res==true){
	    		$("#info_aguarde").css("display", "block");
	    		programa = $('#programas').val();
			    $.post(phpSelf,{'ajax':'apagar_programa','admin':user, 'programa':programa},function(data) {
				    if (data.indexOf('|') > 0) {
				    	info = data.split("|")
				    	$("#info_class").addClass(info[1]);
				    	$("#info_message").html(info[2]);
				    	$("#info_aguarde").css("display", "none");
				        $("#informativo").css("display", "block");
				        if(info[0]=="OK"){
				        	$('#programas option:selected').remove();
				        }
					} else alert ('erro de AJAX!'+data);
				});
	    	}else{
	    		alert("Exclusão cancelada.");
	    	}
    	}else{
    		alert("Nenhum usuário selecionado.");
    	}
	});
    $('#reset_prg').click(function() {$('#admin').change();});
    $('#reset_rs').click(function() {$('#org').change();});

    $('#copy_sel').click(function() {
		opcoes = $('#restricoes option:selected');
		for (i = 0; i < opcoes.length; i++) {
		    valorOpcao = opcoes[i].value;
		    if ($('#programas').html().indexOf(valorOpcao) == -1) {
		 		$('#programas').append(opcoes[i]);
			}
		}
	});
	$('#copy_all').click(function() {
		opcoes = $('#restricoes option');
		for (i = 0; i < opcoes.length; i++) {
		    valorOpcao = opcoes[i].value;
		    if ($('#programas').html().indexOf(valorOpcao) == -1) {
		 		$('#programas').append(opcoes[i]);
			}
		}
	});
}); // FIM jQuery
</script>

<script type="text/javascript">

	$(function() {
        $(".show-log").click(function() {
        	var programa = $("#auditor_programa").val();
        	if(programa == "KO")
        	{
        		alert("Selecione um programa para visualizar seu log.");
        	}else{
				var url = 'relatorio_log_alteracao_new.php?' +
					'parametro=tbl_' + $(this).data('object') +
					'&id=' + $(this).data('value') + programa;

				Shadowbox.init();

				Shadowbox.open({
					content: url,
					player: "iframe",
					height: 600,
					width: 800
				});
			}
		});

	});
</script>

<div class="alert alert-info" id="info_aguarde" style="display: none;">
	<h4>Aguarde um momento por gentileza</h4>
</div>

<div class="container" id="informativo" style="display: none;">
	<div id="info_class">
		<h4 id="info_message"></h4>
	</div>
</div>

<center>
<form class='form-search form-inline tc_formulario'>
	<div class='titulo_tabela '>Cadastro</div>
	<br/>
	<div class="row-fluid">
		<fieldset for='restricoes' class="span6">
			<div class='row-fluid'>
				<div class='span2'></div>
					<div class='span4'>
						<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
							<legend style="position: absolute; border-bottom: none;">Copiar Restri&ccedil;&otilde;es</legend>
							<div class='controls controls-row'>
							</div>
						</div>
					</div>
				<div class='span4'>
				</div>
				<div class='span2'></div>
			</div>
			<div class='row-fluid'>
				<div class='span2'></div>
					<div class='span4'>
						<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
							<label for='org'>Usu&aacute;rio</label>
							<div class='controls controls-row'>
								<div class='span4'>
										<select name='org' id='org'>
											<option></option>
										</select>
								</div>
							</div>
						</div>
					</div>
				<div class='span4'>
				</div>
				<div class='span2'></div>
			</div>
			<div class='row-fluid'>
				<div class='span2'></div>
					<div class='span4'>
						<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
							<label for='programas'>Restrições</label>
							<div class='controls controls-row'>
								<div class='span4'>
										<select title="Restrições ativas para este usuário" class="lista"				 	 name="restricoes" id="restricoes" size="10" multiple>
											<option></option>
								        </select>
								</div>
							</div>
						</div>
					</div>
				<div class='span4'>
				</div>
				<div class='span2'></div>
			</div><br>
			<div class='row-fluid'>
				<div class='span2'></div>
					<div class='span4'>
						<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
							<input type='button' class="btn btn-primary" id='copy_sel' title='Copia as restrições selecionadas para o usuário' value="Copiar">
							<div class='controls controls-row'>
								<div class='span4'>
										<br>
										<input type='button' class="btn btn-primary " id='copy_all' title='Copia todas as restrições para o usuário' value="Copiar Tudo">
								</div>
							</div>
						</div>
					</div>
				<div class='span4'>
					<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
						<input type='button' class="btn btn-warning" id='reset_rs' title='Recarrega as restrições para o usuário selecionado' value="Recarregar">
						<div class='controls controls-row'>
							<div class='span4'>
							</div>
						</div>
					</div>
				</div>
				<div class='span2'></div>
			</div>
		</fieldset>
		<fieldset for='usuario' class="span6">
			<div class='row-fluid'>
				<div class='span2'></div>
					<div class='span4'>
						<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
							<legend style="position: absolute; border-bottom: none;">Editar Usu&aacute;rio<?=$banco_de_testes?></legend>
							<div class='controls controls-row'>
							</div>
						</div>
					</div>
				<div class='span4'>
				</div>
				<div class='span2'></div>
			</div>
			<div class='row-fluid'>
				<div class='span2'></div>
					<div class='span4'>
						<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
							<label for='usuario'>Usu&aacute;rio</label>
							<div class='controls controls-row'>
								<div class='span4'>
									<select name='admin' id='admin'>
										<option></option>
									</select>
								</div>
							</div>
						</div>
					</div>
				<div class='span4'>
					<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
						<div class='controls controls-row'>
							<div class='span4'>
							</div>
						</div>
					</div>
				</div>
				<div class='span2'></div>
			</div>
			<div class='row-fluid'>
				<div class='span2'></div>
					<div class='span4'>
						<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
							<label for='programas'>Restrições</label>
							<div class='controls controls-row'>
								<div class='span4'>
									<select title="Restrições ativas para este usuário" class="lista" name="programas[]" id="programas" size="10" multiple>
										<option></option>
							        </select>
								</div>
							</div>
						</div>
					</div>
				<div class='span4'>
					<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
						<div class='controls controls-row'>
							<div class='span4'>
							</div>
						</div>
					</div>
				</div>
				<div class='span2'></div>
			</div>
			<br>
			<div class='row-fluid'>
				<div class='span2'></div>
					<div class='span4'>
						<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
							<input class="btn btn-danger" type='button' id='excl_sel'  title='Exlcui as restrições selecionadas' value="Excluir">
							<div class='controls controls-row'>
								<div class='span4'>
									<br>
									<input class="btn btn-danger" type='button' id='kill_all'  title='Apaga todas as restrições deste usuário' value="Apagar Restrições">
								</div>
							</div>
						</div>
					</div>
				<div class='span4'>
					<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
						<input class="btn btn-success " type='button' id='atualiza'  title='Atualiza as alterações' value="Atualizar Restrições">
						<div class='controls controls-row'>
							<div class='span4'>
							<br>
								<div class="span2">
									<input class="btn btn-warning" type='button' id='reset_prg' title='Anula as alterações NÃO SALVAS' value="Cancelar">
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class='span2'></div>
			</div>
		</fieldset>
	</div>
<br>
</form>
<br>
	<?php $log_id    = $login_fabrica . "*"; ?>
<button type="button" class="show-log btn btn-link" data-object="programa_restrito" data-value="<?=$log_id;?>">Ver Log de Alteração</button>
<select name="auditor_programa" id="auditor_programa">
<option value="KO">Selecione o programa</option>
	<?php  
		$sqlPR = "SELECT DISTINCT programa FROM tbl_programa_restrito WHERE fabrica = $login_fabrica ORDER BY programa";
		$resPR = pg_query($con, $sqlPR);
		$count = pg_num_rows($resPR);
		if($count > 0){
			for ($i = 0; $i < $count ; $i++) { 
				$programaPR = trim(pg_result($resPR,$i,programa));
				$aux_prog   = explode("admin/", $programaPR);
				$prog = substr($aux_prog[1], 0, -4);
				echo "<option value='$prog'>$prog</option>";
			}
		}
	?>
</select>
<br>
</center>
<? include "rodape.php"; ?>
