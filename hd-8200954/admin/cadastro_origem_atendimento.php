<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "cadastros";
include 'autentica_admin.php';

$text_origem = "Origem";
if ($login_fabrica == 189) {
	$text_origem = "Depto. Gerador da RRC";
} 
if ($_POST["btn_acao"] == "submit") {
	$msg_erro = array();

	$hd_chamado_origem    = $_POST["hd_chamado_origem"];
	$descricao            = trim($_POST["descricao"]);
	$valida_obrigatorio   = trim($_POST['valida_obrigatorio']);
	$tipo_protocolo		  = $_POST['tipo_protocolo'];

	if ($valida_obrigatorio == '' || empty($valida_obrigatorio)){
		$valida_obrigatorio = 'false';
	}

	if (!strlen($descricao)) {
		$msg_erro["msg"][] = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "descricao";
	}

	if (!count($msg_erro)) {
		if (empty($hd_chamado_origem)) {

			$sql_origem = "SELECT hd_chamado_origem FROM tbl_hd_chamado_origem WHERE fabrica = {$login_fabrica} AND descricao ILIKE '$descricao%'";
			$res_origem = pg_query($con, $sql_origem);

			if(pg_num_rows($res_origem) > 0){
				$msg_erro["msg"][] = $text_origem ." já cadastrada.";
			}else{
				$sql = "INSERT INTO tbl_hd_chamado_origem (
							fabrica,
							descricao,
							valida_obrigatorio
						) VALUES (
							$login_fabrica,
							'$descricao',
							'$valida_obrigatorio'
						) RETURNING hd_chamado_origem";
				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					$msg_erro["msg"][] = "Erro ao gravar ".$text_origem ;
				}else{
					$hd_chamado_origem = pg_fetch_result($res, 0, 'hd_chamado_origem');
				}
			}
		} else {
			$sql = "UPDATE tbl_hd_chamado_origem
					SET descricao = '$descricao',
					valida_obrigatorio = '$valida_obrigatorio'
					WHERE fabrica = $login_fabrica
					AND hd_chamado_origem = $hd_chamado_origem";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				$msg_erro["msg"][] = "Erro ao alterar ".$text_origem ;
			}
		}

		if(!count($msg_erro["msg"])){

			if(in_array($login_fabrica, array(169,170)) AND count($tipo_protocolo) > 0){

				$sql = "DELETE FROM tbl_hd_tipo_chamado_vinculo WHERE hd_chamado_origem = '{$hd_chamado_origem}'";
				$res = pg_query($con,$sql);
				
				foreach ($tipo_protocolo as $key => $value) {

					$sql = "INSERT INTO tbl_hd_tipo_chamado_vinculo(hd_tipo_chamado,hd_chamado_origem) VALUES({$value},{$hd_chamado_origem})";
					$res = pg_query($con, $sql);					
				}
			}
		}

		if (!count($msg_erro["msg"])) {
			$msg_success = true;
			unset($hd_chamado_origem, $descricao, $tipo_protocolo);
		}
	}
}

if ($_POST["btn_acao"] == "ativar") {
	$hd_chamado_origem = $_POST["hd_chamado_origem"];

	$sql = "SELECT hd_chamado_origem FROM tbl_hd_chamado_origem WHERE fabrica = {$login_fabrica} AND hd_chamado_origem = {$hd_chamado_origem}";
	$res = pg_query($con, $sql);
	if (pg_num_rows($res) > 0) {
		$sql = "UPDATE tbl_hd_chamado_origem SET ativo = TRUE WHERE fabrica = {$login_fabrica} AND hd_chamado_origem = {$hd_chamado_origem}";
		$res = pg_query($con, $sql);

		if (!pg_last_error()) {
			echo "success";
		} else {
			echo "error";
		}
	}
	exit;
}

if ($_POST["btn_acao"] == "inativar") {
	$hd_chamado_origem = $_POST["hd_chamado_origem"];

	$sql = "SELECT hd_chamado_origem, descricao FROM tbl_hd_chamado_origem WHERE fabrica = {$login_fabrica} AND hd_chamado_origem = {$hd_chamado_origem}";
	$res = pg_query($con, $sql);
	if (pg_num_rows($res) > 0) {
		$descricao_origem = pg_fetch_result($res, 0, descricao);

		$sql = "UPDATE tbl_hd_chamado_origem SET ativo = FALSE WHERE fabrica = {$login_fabrica} AND hd_chamado_origem = {$hd_chamado_origem}";
		$res = pg_query($con, $sql);
		if (!pg_last_error()) {
			echo "success";
		} else {
			echo "error";
		}
	}
	exit;
}

if (!empty($_GET["hd_chamado_origem"])) {
	$hd_chamado_origem = $_GET["hd_chamado_origem"];

	$sql = "SELECT hd_chamado_origem, descricao
			FROM tbl_hd_chamado_origem
			WHERE fabrica = $login_fabrica
			AND hd_chamado_origem = $hd_chamado_origem;";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$descricao           = pg_result($res, 0, 'descricao');

		if(in_array($login_fabrica, array(169,170))){

			$sql = "SELECT hd_tipo_chamado FROM tbl_hd_tipo_chamado_vinculo WHERE hd_chamado_origem = {$hd_chamado_origem}";
			$res = pg_query($con,$sql);
			$result = pg_fetch_all($res);
			
			if(pg_num_rows($res) > 0){
				foreach ($result as $key => $value) {
					$tipo_protocolo[] = $value['hd_tipo_chamado'];
				}				
			}
		}

	} else {
		$msg_erro["msg"][] = "Classificação de atentimento não encontrada";
	}
}

$layout_menu = "cadastro";
$title       = "Cadastro de ".$text_origem ;
$title_page  = "Cadastro de ".$text_origem ;

if ($_GET["hd_chamado_origem"] || strlen($hd_chamado_origem) > 0) {
	$title_page = "Alteração de Cadastro de ".$text_origem ;
}

include 'cabecalho_new.php';

$plugins = array(
	"multiselect"
);

include "plugin_loader.php";

if ($msg_success) {
?>
    <div class="alert alert-success">
		<h4><?php echo $text_origem;?>, gravada com sucesso</h4>
    </div>
<?php
}

if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<script type="text/javascript">
	$(function () {

		$("#tipo_protocolo").multiselect({
			selectedText: "selecionados # de #"
		});

		$(document).on("click", "button[name=ativar]", function () {
			if (ajaxAction()) {
				var that     = $(this);
				var hd_chamado_origem = $(this).attr("rel");
				$.ajax({
					async: false,
					url: "<?=$_SERVER['PHP_SELF']?>",
					type: "POST",
					dataType: "JSON",
					data: { btn_acao: "ativar", hd_chamado_origem: hd_chamado_origem },
					beforeSend: function () {
						loading("show");
					},
					complete: function (data) {
						data = data.responseText;
						console.log(data);
						if (data == "success") {
							$(that).removeClass("btn-success").addClass("btn-danger");
							$(that).attr({ "name": "inativar", "title": "Inativar origem" });
							$(that).text("Inativar");
							$(that).parents("tr").find("img[name=visivel]").attr({ "src": "imagens/status_verde.png?" + (new Date()).getTime() });
						}
						loading("hide");
					}
				});
			}
		});

		$(document).on("click", "button[name=inativar]", function () {
			if (ajaxAction()) {
				var that     = $(this);
				var hd_chamado_origem = $(this).attr("rel");

				$.ajax({
					async: false,
					url: "<?=$_SERVER['PHP_SELF']?>",
					type: "POST",
					dataType: "JSON",
					data: { btn_acao: "inativar", hd_chamado_origem: hd_chamado_origem },
					beforeSend: function () {
						loading("show");
					},
					complete: function (data) {
						data = data.responseText;
						console.log(data);
						if (data == "success") {
							$(that).removeClass("btn-danger").addClass("btn-success");
							$(that).attr({ "name": "ativar", "title": "Ativar origem" });
							$(that).text("Ativar");
							$(that).parents("tr").find("img[name=visivel]").attr({ "src": "imagens/status_vermelho.png?" + (new Date()).getTime() });
						}
						loading("hide");
					}
				});
			}
		});

	});
</script>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name="frm_condicao" method="POST" enctype="multipart/form-data" class="form-search form-inline tc_formulario" action="<?=$PHP_SELF?>" >
	<div class='titulo_tabela '><?=$title_page?></div>
	<br/>
	<input type="hidden" name="hd_chamado_origem" value="<?=$hd_chamado_origem?>" />
	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span8'>
				<div class='control-group <?=(in_array("descricao", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao'>Descrição</label>
					<div class='controls controls-row'>
						<div class='span12'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="descricao" id="descricao" size="12" class='span12' maxlength="50" value= "<?=$descricao?>" />
							<?php
								if ($login_fabrica == 174)
								{
							?>
								<input type='checkbox' name='valida_obrigatorio' id='valida_obrigatorio' value='true' checked="checked" /><label>Validar Obrigatorio</label>
							<?php
								}
							?>
						</div>
					</div>
				</div>
			</div>
		<div class='span2'></div>
	</div>

	<?php if(in_array($login_fabrica, array(169,170))){ ?>
	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span8">
			<div class="control-group">
				
					<label>Tipo Protocolo</label>
					<div class='controls controls-row'>
						<div class='span12'>
							<select name="tipo_protocolo[]" id="tipo_protocolo" multiple="multiple">
								<?php
									$sql = "SELECT hd_tipo_chamado, descricao FROM tbl_hd_tipo_chamado WHERE fabrica = {$login_fabrica} AND ativo ORDER BY descricao";
									
									$res = pg_query($con,$sql);
									$tipos = pg_fetch_all($res);


									foreach($tipos AS $k => $row){

										$selected = (in_array($row['hd_tipo_chamado'], $tipo_protocolo)) ? "SELECTED" : "";

										echo "<option value='".$row['hd_tipo_chamado']."' {$selected}>".$row['descricao']."</option>";
									}
								?>
							</select>
						</div>
				</div>
			</div>
		</div>
	</div>
	<?php } ?>

	<p><br/>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
		<button class='btn' type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
		<?php
		if (strlen($_GET["hd_chamado_origem"]) > 0) {
		?>
			<button class='btn btn-warning' type="button"  onclick="window.location = '<?=$_SERVER["PHP_SELF"]?>';">Limpar</button>
		<?php
		}
		?>
	</p><br/>
</form>

<table id="classificacoes_cadastradas" class='table table-striped table-bordered table-hover table-fixed' >
	<thead>
		<tr class="titulo_coluna" >
			<th>Descrição</th>
			<?php if(in_array($login_fabrica, array(169,170))){ ?>
					<th>Tipo Protocolo</th>
			<?php } ?>

			<th>Ações</th>
		</tr>
	</thead>
	<tbody>
		<?php
			$sql = "SELECT hd_chamado_origem,descricao,ativo
					FROM tbl_hd_chamado_origem
					WHERE fabrica = $login_fabrica
					ORDER BY descricao";
			$res = pg_query($con, $sql);
			$rows = pg_num_rows($res);

            $origem_padrao = array();

            if (in_array($login_fabrica, array(169,170))) {
                $origem_padrao[] = "Chat";
                $origem_padrao[] = "Email";
                $origem_padrao[] = "Fale Conosco";
                $origem_padrao[] = "Telefone";
            } else if ($login_fabrica == 174) {
                $origem_padrao[] = "Fale Conosco";
                $origem_padrao[] = "Telefone";
	    		$origem_padrao[] = "Marketplace Mercado Livre";
            } else if (in_array($login_fabrica, array(175,178,183,191))) {
            	$origem_padrao = array("Fale Conosco", "Telefone", "E-mail");
            } else if (in_array($login_fabrica, array(177))) {
            	$origem_padrao[] = "Fale Conosco";
            }

			for ($i = 0; $i < $rows; $i++) {
				$descricao   = pg_fetch_result($res, $i, "descricao");
				$hd_chamado_origem   = pg_fetch_result($res, $i, "hd_chamado_origem");
				$ativo   = pg_fetch_result($res, $i, "ativo");


				echo "<tr>";

				if(in_array($descricao, $origem_padrao)){
					echo "<td>{$descricao}</td>";
				}else{
					echo "<td><a href='{$_SERVER['PHP_SELF']}?hd_chamado_origem={$hd_chamado_origem}' >{$descricao}</a></td>";
				}

				if(in_array($login_fabrica, array(169,170))){
					$sqlTipoProtocolo = "SELECT tbl_hd_tipo_chamado.descricao
							FROM tbl_hd_tipo_chamado
							JOIN tbl_hd_tipo_chamado_vinculo USING(hd_tipo_chamado)
							WHERE tbl_hd_tipo_chamado.fabrica = {$login_fabrica}
							AND tbl_hd_tipo_chamado_vinculo.hd_chamado_origem = {$hd_chamado_origem}";
					$resTipoProtocolo = pg_query($con,$sqlTipoProtocolo);
					$result = pg_fetch_all($resTipoProtocolo);
					unset($tiposProtocolo);


					if(pg_num_rows($resTipoProtocolo) > 0){
						foreach ($result as $key => $value) {
							$tiposProtocolo[] = $value['descricao'];
						}

						echo "<td>".implode(' / ',$tiposProtocolo)."</td>";
						
					}else{
						echo "<td></td>";
					}
				}

				echo "<td class='tac'>";

				if(!in_array($descricao, $origem_padrao)){
					if ($ativo != "t") {
						echo "<button type='button' rel='{$hd_chamado_origem}' name='ativar' class='btn btn-small btn-success' title='Ativar classificação' >Ativar</button>";
					} else {
						echo "<button type='button' rel='{$hd_chamado_origem}' name='inativar' class='btn btn-small btn-danger' title='Inativar classificação' >Inativar</button>";
					}
				}
				echo "
					</td>
				</tr>";
			}
		?>
	</tbody>
</table>

<?php
include "rodape.php";
?>
