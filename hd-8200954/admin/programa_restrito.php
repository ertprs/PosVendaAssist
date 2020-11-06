<?
include_once "dbconfig.php";
include_once "includes/dbconnect-inc.php";
include_once "../class/AuditorLog.php";

$admin_privilegios="gerencia";
include_once 'autentica_admin.php';

/*$btn_acao = strtolower($_POST["btn_acao"]);*/

$auditor = new AuditorLog();

if ($_POST["btn_acao"] == "submit"){

	$qtde_item = trim($_POST["qtde_item"]);
	$programa  = trim($_POST["programa"]);
	$log_id    = $login_fabrica . "*" . basename($programa, '.php');
	$acoes     = array('insert' => array(), 'delete' => array());


	for ($i = 0; $i <= $qtde_item; $i ++){
		$admin        = trim($_POST['admin_'.$i]);
		$liberado     = trim($_POST['liberado_'.$i]);

		if (strlen ($admin) > 0) {
			if (strlen($liberado) > 0) {
				$sql = "SELECT * FROM tbl_programa_restrito WHERE programa = '$programa' AND admin = $admin";
				$res = pg_query($con,$sql);
				
				if (pg_numrows ($res) == 0) {
					$acoes['insert'][] = $admin;
				}
			} else {
				$acoes['delete'][] = $admin;
			}
		}
	}

	if (count($insData = $acoes['insert'])) {
		$auditor->retornaDadosSelect(
			"SELECT admin||''||fabrica as id,programa,admin,fabrica FROM tbl_programa_restrito WHERE programa = '{$programa}' AND fabrica = {$login_fabrica}"
		);

		$fabrica = $login_fabrica;

		foreach ($insData as $admin) {
			$sql = sql_cmd(
				'tbl_programa_restrito',
				compact('fabrica', 'programa', 'admin')
			);
			$res = pg_query($con, $sql);
			if (!is_resource($res)) {
				$msg_erro['msg'][] = pg_last_error($con);
			}
		}
		$auditor->retornaDadosSelect()->enviarLog('insert', 'tbl_programa_restrito', $log_id);
	}

	else if (count($delData = $acoes['delete'])) {
		$auditor->retornaDadosSelect(
			"SELECT admin||''||fabrica as id,programa,admin,fabrica FROM tbl_programa_restrito WHERE programa = '{$programa}' AND fabrica = {$login_fabrica}"
		);

		$fabrica = $login_fabrica;

		foreach ($delData as $admin) {
			$sql = sql_cmd(
				'tbl_programa_restrito', 'delete',
				compact('fabrica', 'programa', 'admin')
			);
			$res = pg_query($con, $sql);
			if (!is_resource($res)) {
				$msg_erro['msg'][] = pg_last_error($con);
			}
		}
		$auditor->retornaDadosSelect()->enviarLog('delete', 'tbl_programa_restrito', $log_id);
	}

	if (count($msg_erro["msg"]) == 0) {
		header ("Location: $programa");
		exit;
	}
}

$aux_title = "- " . basename($programa, '.php');
$title = "RESTRIÇÃO DE ACESSO $aux_title";
$layout_menu = "gerencia";
include_once 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable",
	"tooltip"
);


include_once("plugin_loader.php");
?>

<script type="text/javascript">

	$(function() {
		var table = new Object();
        table['table'] = '#resultado_pesquisa_admin';
        table['type'] = 'full';
        $.dataTableLoad(table);

        $(".show-log").click(function() {
			var url = 'relatorio_log_alteracao_new.php?' +
				'parametro=tbl_' + $(this).data('object') +
				'&id=' + $(this).data('value');

			Shadowbox.init();

			Shadowbox.open({
				content: url,
				player: "iframe",
				height: 600,
				width: 800
			});
		});

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

<br>
<?

$sql = "SELECT privilegios FROM tbl_admin WHERE admin = $login_admin";
$res = pg_query ($con,$sql);
$privilegios = pg_result ($res,0,0);

if (strpos ($privilegios,'*') === false ) {
	?> 
		<div class='container'>
	        <div class="alert alert-error">
	            <h4>Apenas usuário <b>MASTER</b> pode realizar restrições de programas.</h4>
	        </div>  
	    </div>
		<br><br>	    
	<?php
	include_once "rodape.php"; 
	exit;
}

$programa = $_GET['programa'];
?>

<div class='container'>
    <div class="alert">
        <h4>Ao marcar uma ou mais opções a baixo, o acesso ao programa <i>"<?php echo basename($programa, '.php'); ?>"</i> fica restrito ao grupo selecionado.</h4>
    </div>  
</div>

<div class='container'>
    <div class="alert alert-info">
        <h4>Clique no nome do admin para acessar à página de "Gerenciamento de Restrições". Com isso é possível editar todos os programas liberados apenas a esse usuário.</h4>
    </div>  
</div>

<?php
echo "<form name='frm_admin' method='post' action='$PHP_SELF '>";
echo "<input type='hidden' name='btn_acao' value=''>";
echo "<input type='hidden' name='programa' value='$programa'>";

?>

<table id="resultado_pesquisa_admin" class='table table-striped table-bordered table-hover table-fixed' >
	<thead>
		<tr class="titulo_coluna">
			<th>Nome</th>
			<th>
				<i>Login</i>
				 <span class="add-on">
				 	<i id="btnPopover" rel="popover" data-placement="top" data-trigger="hover" data-delay="500" title="Clique no nome do admin para acessar à página de 'Gerenciamento de Restrições'. Com isso é possível editar todos os programas liberados apenas a esse usuário." class="icon-question-sign"></i>
				 </span>
			</th>
			<th>Restringir o Acesso</th>
		</tr>
	</thead>
	<tbody>

<?php
$sql = "SELECT tbl_admin.*, tbl_programa_restrito.admin AS liberado
		FROM   tbl_admin
		LEFT JOIN tbl_programa_restrito USING (admin)
		WHERE tbl_admin.fabrica = $login_fabrica
		AND   (
			tbl_programa_restrito.programa = '$programa'
			OR tbl_programa_restrito.programa IS NULL
		)
		ORDER BY tbl_admin.login";

$sql = "SELECT tbl_admin.*
		FROM   tbl_admin
		WHERE tbl_admin.fabrica = $login_fabrica
		AND ativo
		ORDER BY tbl_admin.nome_completo";
$resx = pg_query ($con,$sql);
$count = pg_num_rows($resx);
for ($i = 0; $i < $count; $i ++){
	$liberado = "";
	$admin = trim(pg_result($resx,$i,admin));
	$nome  = trim(pg_result($resx,$i,nome_completo));
	$login = trim(pg_result($resx,$i,login));
	
	$sql = "SELECT tbl_programa_restrito.admin AS liberado
			FROM   tbl_programa_restrito
			WHERE  tbl_programa_restrito.admin    = $admin
			AND    tbl_programa_restrito.programa = '$programa'";
	$res = pg_query ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$liberado = trim(pg_result ($res,0,liberado));
	}
	
	echo "<tr>\n";
	echo "<input type='hidden' name='admin_$i' value='$admin'>\n";
	echo "<td class='tal'>$nome</td>";
	echo "<td class='tal'><a target='_blank' href='./programa_restrito_gerencia.php?edit_admin=$admin'>$login</td>";
	echo "<td class='tac'><input type='checkbox' name='liberado_$i' value='1'";
	if (strlen($liberado) > 0) echo " checked ";
	echo "> &nbsp;</td>";
	echo "</tr>";
}

$log_id = $login_fabrica . "*" . basename($programa, '.php');
?>

	</tbody>
</table> 
<center>
	<p><br/>
		<button class='btn btn-success' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
		<input type='hidden' id="qtde_item" name='qtde_item' value="<?=$count;?>">
	</p><br/>
	</form>
	<br>
	<button type="button" class="show-log btn btn-link" data-object="programa_restrito" data-value="<?=$log_id;?>">Ver Log de Alteração</button>
</center>

<? include_once "rodape.php"; ?>