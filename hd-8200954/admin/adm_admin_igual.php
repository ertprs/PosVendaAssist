<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

/*Buscando os admin's com usuários em mais de uma fábrica*/
if ($_POST["buscarAdminDuplos"]) {
	$adm_mul = array();
	$aux_fab = (in_array($login_fabrica, array(11, 172))) ? " IN (11, 172) " : " = $login_fabrica ";
	$aux_sql = "
		SELECT tbl_admin.admin, tbl_admin.nome_completo
		  FROM tbl_admin_igual
		  JOIN tbl_admin
		    ON tbl_admin.admin = tbl_admin_igual.admin
		 WHERE tbl_admin.fabrica $aux_fab
		   AND tbl_admin.ativo IS TRUE
		 GROUP BY tbl_admin.admin, tbl_admin.nome_completo
		 ORDER BY tbl_admin.nome_completo
	";
	$aux_res = pg_query($con, $aux_sql);

	if (pg_last_error()) {
		echo "KO|Erro ao buscar os admin\'s";
	} else {
		$num_row = pg_num_rows($aux_res);
		$options = "<option value=''>Selecione</option>";

		for ($i = 0; $i < $num_row; $i++) {
			$admin       = pg_fetch_result($aux_res, $i, 'admin');
			$admin_nome  = pg_fetch_result($aux_res, $i, 'nome_completo');
			$options    .= "<option value='$admin'>$admin_nome</option>";
		}
		echo "OK|$options";
	}
	exit;
}

if ($_POST["informarAdminDuplo"]) {
	$aux_admin = $_POST["admin"];
	$aux_sql   = "SELECT admin_igual FROM tbl_admin_igual WHERE admin = $aux_admin";
	$aux_res   = pg_query($con, $aux_sql);
	$num_row   = pg_num_rows($aux_res);

	if (pg_last_error()) {
		echo "KO|Erro ao localizar as informações desse usuário";
	} else {
		$array_adm = array();
		for ($i = 0; $i < $num_row; $i++) {
			$admin_igual = pg_fetch_result($aux_res, $i, 'admin_igual');
			$array_adm[] = $admin_igual;
		}

		$aux_sql = "
			SELECT tbl_admin.admin
			     , tbl_fabrica.nome
			     , tbl_admin.login
			     , tbl_admin.nome_completo
			  FROM tbl_admin
			  JOIN tbl_fabrica
			    ON tbl_fabrica.fabrica = tbl_admin.fabrica
			 WHERE tbl_admin.admin IN( ". implode(",", $array_adm) ." )
			   AND tbl_admin.ativo IS TRUE
			   AND tbl_fabrica.ativo_fabrica IS TRUE
		";
		$aux_res = pg_query($con, $aux_sql);
		$num_row = pg_num_rows($aux_res);

		if (pg_last_error()) {
			echo "KO|Erro ao localizar as informações desse usuário";
		} else {
			$aux_tab = "";
			for ($i = 0; $i < $num_row; $i++) {
				$fabrica       = pg_fetch_result($aux_res, $i, 'nome');
				$login         = pg_fetch_result($aux_res, $i, 'login');
				$nome_completo = pg_fetch_result($aux_res, $i, 'nome_completo');
				$admin         = pg_fetch_result($aux_res, $i, 'admin');

				$aux_tab .= "
					<tr>
						<td class='tal'>$fabrica</td>
						<td class='tal'>$login</td>
						<td class='tal'>$nome_completo</td>
						<td class='tac'><input type='button' class='btn btn-small btn-danger' value='Excluir' onclick='apagarAssociacao($admin)'></td>
					</tr>
				";
			}

			if (strlen($aux_tab) <= 0) {
				echo "KO|Erro ao localizar as informações desse usuário";
			} else {
				echo "OK|$aux_tab";
			}
		}
	}
	exit;
}

if ($_POST["buscarFabricante"]) {
	$adm_mul = array();
	$aux_fab = (in_array($login_fabrica, array(11, 172))) ? " IN (11, 172) " : " = $login_fabrica ";
	$aux_sql = "SELECT fabrica, nome FROM tbl_fabrica WHERE fabrica $aux_fab";
	$aux_res = pg_query($con, $aux_sql);

	if (pg_last_error()) {
		echo "KO|Erro ao buscar o fabricante.";
	} else {
		$num_row = pg_num_rows($aux_res);
		$options = "<option value=''>Fabricante</option>";

		for ($i = 0; $i < $num_row; $i++) {
			$fabrica  = pg_fetch_result($aux_res, $i, 'fabrica');
			$nome     = strtoupper(pg_fetch_result($aux_res, $i, 'nome'));
			if ($nome == "LENOXX") $nome = "AULIK";
			$options .= "<option value='$fabrica'>$nome</option>";
		}
		echo "OK|$options";
	}
	exit;
}

if ($_POST["buscarSubsidiaria"]) {
	$sede    = $_POST["sede"];
	$options = "<option value=''>Selecione um fabricante</option>\n";
	if ($sede == 11) {
		$options .= "<option value='172'>Pacific</option>\n";
	} else if ($sede == 172) {
		$options .= "<option value='11'>AULIK</option>\n";
	}

	echo "OK|$options";
	exit;
}

if ($_POST["informarLogin"]) {
	$fabrica = $_POST["fabrica"];
	$aux_sql = "SELECT admin, login FROM tbl_admin	WHERE fabrica = $fabrica AND ativo IS TRUE ORDER BY login";
	$aux_res = pg_query($con, $aux_sql);
	$num_row = pg_num_rows($aux_res);

	if (pg_last_error()) {
		echo "KO|Erro ao localizar os usuários do fabricante selecionado.";
	} else {
		$options = "<option value=''>Selecione</option>";
		for ($i = 0; $i < $num_row; $i++) {
			$admin    = pg_fetch_result($aux_res, $i, 'admin');
			$login    = pg_fetch_result($aux_res, $i, 'login');
			$options .= "<option value='$admin'>$login</option>";
		}
		echo "OK|$options";
	}
	exit;
}

if ($_POST["buscarNomeUsuario"]) {
	$admin   = $_POST["admin"];
	$aux_sql = "SELECT nome_completo FROM tbl_admin WHERE admin = $admin";
	$aux_res = pg_query($con, $aux_sql);
	$num_row = pg_num_rows($aux_res);

	if (pg_last_error()) {
		echo "KO|Erro ao localizar o nome do usuário selecionado";
	} else {
		$nome_completo = pg_fetch_result($aux_res, $i, 'nome_completo');
		echo "OK|$nome_completo";
	}
	exit;
}

if ($_POST["informarNovoUsuario"]) {
	$fabrica = $_POST["fabrica"];
	$aux_sql = "SELECT admin, login FROM tbl_admin WHERE fabrica = $fabrica AND ativo IS TRUE ORDER BY login";
	$aux_res = pg_query($con, $aux_sql);
	$num_row = pg_num_rows($aux_res);

	if (pg_last_error()) {
		echo "KO|Erro ao localizar os admin\'s da fábrica selecionada";
	} else {
		$options = "<option value=''>Selecione</option>";
		for ($i = 0; $i < $num_row; $i++) {
			$admin    = pg_fetch_result($aux_res, $i, 'admin');
			$login    = pg_fetch_result($aux_res, $i, 'login');
			$options .= "<option value='$admin'>$login</option>";
		}
		echo "OK|$options";
	}
	exit;
}

if ($_POST["cadastrarNovo_usuario"]) {
	$novo_usuario          = $_POST["novo_usuario"];
	$usuario_copia         = $_POST["usuario_copia"];
	$fabrica_usuario_copia = (int) $_POST["fabrica_usuario_copia"];
	$verificar_erro = false;

	if (empty($novo_usuario)) {
		echo "KO|Erro ao identificar o usuário que será vinculado à fábrica";
	} else if (empty($usuario_copia)) {
		echo "KO|Erro ao identificar o usuário ao qual permitir acesso à fábrica";
	} else {
		$aux_sql = " SELECT admin_igual	FROM tbl_admin_igual WHERE admin = $novo_usuario";
		$aux_res = pg_query($con, $aux_sql);
		$num_row = pg_num_rows($aux_res);

		if ($num_row > 0) {
			for ($i = 0; $i < $num_row; $i++) {
				$aux_adm_igual = pg_fetch_result($aux_res, $i, 'admin_igual');

				$aux_sql = "SELECT fabrica FROM tbl_admin WHERE admin = $aux_adm_igual";
				$aux_res = pg_query($con, $aux_sql);
				$aux_fab = (int) pg_fetch_result($aux_res, 0, 'fabrica');

				if ($aux_fab == $fabrica_usuario_copia) {
					echo "KO|Essa associação já existe";
					$verificar_erro = true;
					break;
				}
			}
		}

		if ($verificar_erro === false) {
			$aux_sql = "INSERT INTO tbl_admin_igual VALUES ($novo_usuario, $usuario_copia)";
			$aux_res = pg_query($con, $aux_sql);

			if (pg_last_error()) {
				echo "KO|Erro ao vincular o usuário";
			} else {
				echo "OK|Usuário vinculado com sucesso!";
			}
		}
	}
	exit;
}

if ($_POST["apagarAssociacao"]) {
	$admin       = $_POST["admin"];
	$admin_igual = $_POST["admin_igual"];

	if (empty($admin) || empty($admin_igual)) {
		echo "KO|Erro ao identificar o usuário que será desvinculado";
	} else {
		$aux_sql = "DELETE FROM tbl_admin_igual WHERE admin = $admin AND admin_igual = $admin_igual";
		$aux_res = pg_query($con, $aux_sql);

		if (pg_last_error()) {
			echo "KO|Erro ao desvincular o usuário";
		} else {
			echo "OK|Vínculo revogado com sucesso!";
		}
	}

	exit;
}

$layout_menu = "gerencia";
$title = "CADASTRO DE USUÁRIOS MULTIFÁBRICA";
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
	function buscarAdminDuplos() {
		$.ajax({
			async: true,
			type: "POST",
			url: '<?=$PHP_SELF?>',
			data: {
				buscarAdminDuplos: true
			},
		}).done(function(data){
			data = data.split("|");
			if(data.error){
				$("#div_msg").removeClass("alert alert-success");
				$("#div_msg").addClass("alert alert-error");
				$("#div_msg").css("display", "block");
				$("#h4_msg").html(data[1]);
			} else {
				if (data[0] == "OK") {
					$("#select_usuario").html(data[1]);
				} else {
					$("#div_msg").removeClass("alert alert-success");
					$("#div_msg").addClass("alert alert-error");
					$("#div_msg").css("display", "block");
					$("#h4_msg").html(data[1]);
				}
			}
		});
	}

	function informarAdminDuplo(admin) {
		$.ajax({
			async: true,
			type: "POST",
			url: '<?=$PHP_SELF?>',
			data: {
				informarAdminDuplo: true,
				admin: admin
			},
		}).done(function(data){
			data = data.split("|");
			if(data.error){
				$("#div_msg").removeClass("alert alert-success");
				$("#div_msg").addClass("alert alert-error");
				$("#div_msg").css("display", "block");
				$("#h4_msg").html(data[1]);
			} else {
				if (data[0] == "OK") {
					$("#div_tabela_dados_usuario").css("display", "block");
					$("#tbody_tabela_dados_usuario").html(data[1]);
				} else {
					$("#div_msg").removeClass("alert alert-success");
					$("#div_msg").addClass("alert alert-error");
					$("#div_msg").css("display", "block");
					$("#h4_msg").html(data[1]);
				}
			}
		});
	}

	function buscarFabricante() {
		$.ajax({
			async: true,
			type: "POST",
			url: '<?=$PHP_SELF?>',
			data: {
				buscarFabricante: true
			},
		}).done(function(data){
			data = data.split("|");
			if(data.error){
				$("#div_msg").removeClass("alert alert-success");
				$("#div_msg").addClass("alert alert-error");
				$("#div_msg").css("display", "block");
				$("#h4_msg").html(data[1]);
			} else {
				if (data[0] == "OK") {
					$("#select_cad_usuario").html(data[1]);
				} else {
					$("#div_msg").removeClass("alert alert-success");
					$("#div_msg").addClass("alert alert-error");
					$("#div_msg").css("display", "block");
					$("#h4_msg").html(data[1]);
				}
			}
		});
	}

	function buscarSubsidiaria() {
		var sede = $("#select_cad_usuario").val();
		$.ajax({
			async: true,
			type: "POST",
			url: '<?=$PHP_SELF?>',
			data: {
				buscarSubsidiaria: true,
				sede : sede
			},
		}).done(function(data){
			data = data.split("|");
			if(data.error){
				$("#div_msg").removeClass("alert alert-success");
				$("#div_msg").addClass("alert alert-error");
				$("#div_msg").css("display", "block");
				$("#h4_msg").html(data[1]);
			} else {
				if (data[0] == "OK") {
					$("#select_novo_fabricante").html(data[1]);
				} else {
					$("#div_msg").removeClass("alert alert-success");
					$("#div_msg").addClass("alert alert-error");
					$("#div_msg").css("display", "block");
					$("#h4_msg").html(data[1]);
				}
			}
		});
	}

	function informarLogin(fabrica) {
		$.ajax({
			async: true,
			type: "POST",
			url: '<?=$PHP_SELF?>',
			data: {
				informarLogin: true,
				fabrica: fabrica
			},
		}).done(function(data){
			data = data.split("|");
			if(data.error){
				$("#div_msg").removeClass("alert alert-success");
				$("#div_msg").addClass("alert alert-error");
				$("#div_msg").css("display", "block");
				$("#h4_msg").html(data[1]);
			} else {
				if (data[0] == "OK") {
					$("#select_cad_login").html(data[1]);
				} else {
					$("#div_msg").removeClass("alert alert-success");
					$("#div_msg").addClass("alert alert-error");
					$("#div_msg").css("display", "block");
					$("#h4_msg").html(data[1]);
				}
			}
		});
	}

	function buscarNomeUsuario(admin, campo) {
		$.ajax({
			async: true,
			type: "POST",
			url: '<?=$PHP_SELF?>',
			data: {
				buscarNomeUsuario: true,
				admin: admin
			},
		}).done(function(data){
			data = data.split("|");
			if(data.error){
				$("#div_msg").removeClass("alert alert-success");
				$("#div_msg").addClass("alert alert-error");
				$("#div_msg").css("display", "block");
				$("#h4_msg").html(data[1]);
			} else {
				if (data[0] == "OK") {
					$("#" + campo).val(data[1]);
				} else {
					$("#div_msg").removeClass("alert alert-success");
					$("#div_msg").addClass("alert alert-error");
					$("#div_msg").css("display", "block");
					$("#h4_msg").html(data[1]);
				}
			}
		});
	}

	function informarNovoUsuario(fabrica) {
		$.ajax({
			async: true,
			type: "POST",
			url: '<?=$PHP_SELF?>',
			data: {
				informarNovoUsuario: true,
				fabrica: fabrica
			},
		}).done(function(data){
			data = data.split("|");
			if(data.error){
				$("#div_msg").removeClass("alert alert-success");
				$("#div_msg").addClass("alert alert-error");
				$("#div_msg").css("display", "block");
				$("#h4_msg").html(data[1]);
			} else {
				if (data[0] == "OK") {
					$("#select_novo_login").html(data[1]);
				} else {
					$("#div_msg").removeClass("alert alert-success");
					$("#div_msg").addClass("alert alert-error");
					$("#div_msg").css("display", "block");
					$("#h4_msg").html(data[1]);
				}
			}
		});
	}

	function cadastrarNovo_usuario(novo_usuario, usuario_copia) {
		var fabrica_usuario_copia = $("#select_novo_fabricante").val();
		$.ajax({
			async: true,
			type: "POST",
			url: '<?=$PHP_SELF?>',
			data: {
				cadastrarNovo_usuario: true,
				novo_usuario: novo_usuario,
				usuario_copia: usuario_copia,
				fabrica_usuario_copia: fabrica_usuario_copia
			},
		}).done(function(data){
			data = data.split("|");
			if(data.error){
				$("#div_msg").removeClass("alert alert-success");
				$("#div_msg").addClass("alert alert-error");
				$("#div_msg").css("display", "block");
				$("#h4_msg").html(data[1]);
			} else {
				if (data[0] == "OK") {
					$("#div_msg").removeClass("alert alert-error");
					$("#div_msg").addClass("alert alert-success");
					$("#div_msg").css("display", "block");
					$("#h4_msg").html(data[1]);
				} else {
					$("#div_msg").removeClass("alert alert-success");
					$("#div_msg").addClass("alert alert-error");
					$("#div_msg").css("display", "block");
					$("#h4_msg").html(data[1]);
				}
			}
		});
	}

	function apagarAssociacao(admin_igual) {
		admin = $("#select_usuario").val();
		$.ajax({
			async: true,
			type: "POST",
			url: '<?=$PHP_SELF?>',
			data: {
				apagarAssociacao: true,
				admin: admin,
				admin_igual: admin_igual
			},
		}).done(function(data){
			data = data.split("|");
			if(data.error){
				$("#div_msg").removeClass("alert alert-success");
				$("#div_msg").addClass("alert alert-error");
				$("#div_msg").css("display", "block");
				$("#h4_msg").html(data[1]);
			} else {
				if (data[0] == "OK") {
					$("#div_msg").removeClass("alert alert-error");
					$("#div_msg").addClass("alert alert-success");
					$("#div_msg").css("display", "block");
					$("#h4_msg").html(data[1]);
				} else {
					$("#div_msg").removeClass("alert alert-success");
					$("#div_msg").addClass("alert alert-error");
					$("#div_msg").css("display", "block");
					$("#h4_msg").html(data[1]);
				}
			}
		});
		setTimeout(function(){ window.location.reload(); },3000);
	}

	$(function() {
		buscarAdminDuplos();

		$("#select_usuario").change(function(){
			var admin = $(this).val();
			informarAdminDuplo(admin);
		});

		$("#cadastrar_novo").click( function(){
			$("#div_tabela_dados_usuario").css("display", "none");
			$("#frm_listar_atendente").css("display", "none");
			buscarFabricante();
			$("#frm_cad_usuario").css("display", "block");
			$("#div_tabela_cad_usuario").css("display", "block");
		});

		$("#select_cad_usuario").change( function(){
			$("#lbl_cad_usuario").val("");
			var fabrica = $(this).val();
			informarLogin(fabrica);
			buscarSubsidiaria(fabrica);
		});

		$("#select_cad_login").change( function(){
			var admin = $(this).val();
			buscarNomeUsuario(admin, "lbl_cad_usuario");
		});

		$("#listar_todos").click( function(){
			window.location.reload();
		});

		$("#select_novo_fabricante").change( function() {
			fabrica = $(this).val();
			informarNovoUsuario(fabrica);
			$("#lbl_novo_usuario_nome").val("Selecione um Usuário (Login)");
		});

		$("#select_novo_login").change( function() {
			var admin = $(this).val();
			buscarNomeUsuario(admin, "lbl_novo_usuario_nome");
		});

		$("#cadastrar_usuario").click( function() {
			var msg_erro = "";

			if ($("#lbl_cad_usuario").val() == "") {
				msg_erro = "Selecione antes o usuário que será associado!";
			} else if ($("#lbl_novo_usuario_nome").val() == "Selecione um fabricante" || $("#lbl_novo_usuario_nome").val() == "Selecione um Usuário (Login)") {
				msg_erro = "Selecione o usuário que deseja associar!"
			}

			if (msg_erro != "") {
				$("#div_msg").addClass("alert alert-error");
				$("#div_msg").css("display", "block");
				$("#h4_msg").html(msg_erro);
			} else {
				$("#div_msg").css("display", "none");
				var novo_usuario  = $("#select_cad_login").val();
				var usuario_copia = $("#select_novo_login").val();
				cadastrarNovo_usuario(novo_usuario, usuario_copia);
			}
		});
	});
</script>

<style>
	#frm_cad_usuario, #div_tabela_cad_usuario {
		display: none;
	}

	#span_alert {
		width: 120px;
		height: 20px;
		font-size: 12px;
		padding: 2px;
	}
</style>

<div id="div_msg" style="display: none;">
	<h4 id="h4_msg"></h4>
</div>
<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_listar_atendente' id="frm_listar_atendente" METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
	<div class='titulo_tabela '>Usuários com Acesso Multifábrica</div>
	<br/>
	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("select_usuario", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='select_usuario'>Selecione um Usuário</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<select name="select_usuario" id="select_usuario"></select>
						</div>
					</div>
				</div>
			</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<div class='controls controls-row'>
					<div class='span4'>
							<input type="button" class="btn btn-success" name="cadastrar_novo" id="cadastrar_novo" size="12" maxlength="10" class='span12' value="Cadastrar Novo" >
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
</form>

<div id="div_tabela_dados_usuario">
	<table id="tabela_dados_usuario" class='table table-striped table-bordered table-hover table-fixed'>
		<thead>
			<tr class="titulo_coluna">
				<th>Fábrica</th>
				<th>Nome Completo</th>
				<th>Login</th>
				<th>Ação</th>
			</tr>
		</thead>
		<tbody id="tbody_tabela_dados_usuario">
			<tr>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
			</tr>
		</tbody>
	</table>
</div>

<form name='frm_cad_usuario' id="frm_cad_usuario" METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
	<div class='titulo_tabela '>Cadastro de Usuário Multifábrica</div>
	<br/>
	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("select_usuario", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='select_usuario'>Fabricante</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<select name="select_cad_usuario" id="select_cad_usuario"></select>
						</div>
					</div>
				</div>
			</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("select_usuario", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='select_usuario'>Login</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<select name="select_cad_login" id="select_cad_login">
								<option value="">Selecione</option>
							</select>
						</div>
					</div>
				</div>
			</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='select_usuario'>Nome do Usuário</label>
				<div class='controls controls-row'>
					<div class='span4'>
							<input type="text" readonly="readonly" nome="lbl_cad_usuario" id="lbl_cad_usuario">
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("select_usuario", $msg_erro["campos"])) ? "error" : ""?>'>
					<div class='controls controls-row'>
						<div class='span4'>
							<input type="button" class="btn btn-warning" name="listar_todos" id="listar_todos" size="12" maxlength="10" class='span12' value="Listar Todos os Usuário" >
							</select>
						</div>
					</div>
				</div>
			</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<div class='controls controls-row'>
					<div class='span4'>
							<input type="button" class="btn btn-success" name="cadastrar_usuario" id="cadastrar_usuario" size="12" maxlength="10" class='span12' value="cadastrar" >
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
</form>

<div id="div_tabela_cad_usuario">
	<span id="span_alert" class="label label-info">Pode acessar como</span>
	<br>
	<table id="tabela_cad_usuario" class='table table-striped table-bordered table-hover table-fixed'>
		<thead>
			<tr class="titulo_coluna">
				<th>Fabricante</th>
				<th>Login</th>
				<th>Nome</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>
					<select name="select_novo_fabricante" id="select_novo_fabricante">
						<option value="">Selecione um fabricante</option>
					</select>
				</td>
				<td>
					<select name="select_novo_login" id="select_novo_login">
						<option value="">Selecione um fabricante</option>
					</select>
				</td>
				<td>
					<input type="text" readonly="readonly" nome="lbl_novo_usuario_nome" id="lbl_novo_usuario_nome" value="Selecione um fabricante">
				</td>
			</tr>
		</tbody>
	</table>
</div>
<br>
<? include 'rodape.php' ?>
