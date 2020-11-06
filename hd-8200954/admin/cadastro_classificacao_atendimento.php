<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "cadastros";

include 'autentica_admin.php';
include 'funcoes.php';
$text_class = "Classificação";
if ($login_fabrica == 189) {
	$text_class = "Registro Ref. a";
} 
if ($_POST["btn_acao"] == "submit") {
	$msg_erro = array();

	$hd_classificacao    = $_POST["hd_classificacao"];
	$descricao           = trim($_POST["descricao"]);
	$notificar_posto 	 = $_POST["notificar_posto"];
	$script_falha        = $_POST["script_falha"];
	$abre_os_pre_os      = $_POST["abre_os_pre_os"];
	$tipo_protocolo      = $_POST["tipo_protocolo"];

	$obriga_campos 	 	 = $_POST["obriga_campos"];

	if(strlen(trim($notificar_posto)) > 0){
		$notificar_posto = "true";
	}else{
		$notificar_posto = "false";
	}

	if (strlen(trim($obriga_campos)) > 0){
		$obriga_campos = "true";
	}else{
		$obriga_campos = "false";
	}

	if(strlen(trim($script_falha)) > 0){
		$script_falha = "true";
	}else{
		$script_falha = "false";
	}

	if (!strlen($abre_os_pre_os) && $login_fabrica == 195) {
		$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
		$msg_erro["campos"][]   = "abre_os_pre_os";
    } 

	

	if (!strlen($descricao)) {
		$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
		$msg_erro["campos"][]   = "descricao";
    } else {
    	if ($login_fabrica == 52) {
    		$auxliar = $descricao;
    		$auxliar = str_replace("ç", "c", $auxliar);
    		$auxliar = str_replace("Ç", "C", $auxliar);
    		$auxliar = str_replace("ã", "a", $auxliar);
    		$auxliar = str_replace("Ã", "A", $auxliar);
    		$auxliar = strtoupper($auxliar);

    		if ($auxliar == "ORIENTACAO") {
    			$msg_erro['msg']['obg'] = $text_class." de Atendimento Bloqueada para Alteração";
		    	$msg_erro["campos"][]   = "descricao";
    		} elseif (strlen($hd_classificacao) > 0) {
	    		if ($descricao == "Orientação" || $descricao == "ORIENTAÇÃO") {
	    			$msg_erro['msg']['obg'] = $text_class." de Atendimento Bloqueada para Alteração";
		    		$msg_erro["campos"][]   = "descricao";
	    		} else {
		    		$aux_sql = "SELECT descricao FROM tbl_hd_classificacao WHERE descricao = '$descricao' AND fabrica = $login_fabrica";
		    		$aux_res = pg_query($con, $aux_sql);
		    		$aux_val = pg_fetch_result($aux_res, 0, 'descricao');
		    		
		    		if (strlen($aux_val) > 0 && ($aux_val == "Orientação" || $aux_val == "ORIENTAÇÃO")) {
		    			$msg_erro['msg']['obg'] = $text_class." de Atendimento Bloqueada para Alteração";
			    		$msg_erro["campos"][]   = "descricao";
			    		$descricao              = "ORIENTAÇÃO";
		    		}
	    		}

	    		if (empty($msg_erro["msg"]["obg"])) {
	    			$sqlver = "
			            SELECT  COUNT(1) AS igual
			            FROM    tbl_hd_classificacao
			            WHERE   fabrica = $login_fabrica
			            AND     descricao ILIKE '$descricao'
			        ";
			        $resver = pg_query($con,$sqlver);

			        if (pg_fetch_result($resver, 0, 'igual') > 0) {
			            $msg_erro['msg']['obg'] = $text_class." já existente";
			            $msg_erro["campos"][]   = "descricao";
			        }
	    		}
    		}
    	} else {
	    	if (empty($hd_classificacao)){
		    	$sqlver = "
		            SELECT  COUNT(1) AS igual
		            FROM    tbl_hd_classificacao
		            WHERE   fabrica = $login_fabrica
		            AND     descricao ILIKE '$descricao'
		        ";
		        $resver = pg_query($con,$sqlver);

		        if (pg_fetch_result($resver,0,igual) > 0) {
		            $msg_erro['msg']['obg'] = $text_class." já existente";
		            $msg_erro["campos"][]   = "descricao";
		        }
		    }
        }
    }

	if (!count($msg_erro)) {
		if (empty($hd_classificacao)) {
			$sql = "INSERT INTO tbl_hd_classificacao (
						fabrica,
						descricao,
						admin,
						envia_email,
						script_falha,
						obriga_campos,
						abre_os_pre_os
					) VALUES (
						$login_fabrica,
						'$descricao',
						{$login_admin},
						{$notificar_posto},
						{$script_falha},
						{$obriga_campos},
						'{$abre_os_pre_os}'
					) RETURNING hd_classificacao";
			$res = pg_query($con, $sql);

			if (!pg_last_error()) {
				$hd_classificacao = pg_fetch_result($res, 0, 'hd_classificacao');
			}

		} else {
			$sql = "UPDATE tbl_hd_classificacao
					SET
						descricao           = '$descricao',
						admin 		= '$login_admin',
						envia_email = {$notificar_posto},
						script_falha = {$script_falha},
						abre_os_pre_os = '{$abre_os_pre_os}',
						obriga_campos = {$obriga_campos}
					WHERE fabrica = $login_fabrica
					AND hd_classificacao = $hd_classificacao";
			$res = pg_query($con, $sql);
		}

		if(in_array($login_fabrica, array(169,170)) AND count($tipo_protocolo) > 0){

			$sql = "DELETE FROM tbl_hd_tipo_chamado_vinculo WHERE hd_classificacao = '{$hd_classificacao}'";
			$res = pg_query($con,$sql);
			
			foreach ($tipo_protocolo as $key => $value) {

				$sql = "INSERT INTO tbl_hd_tipo_chamado_vinculo(hd_tipo_chamado,hd_classificacao) VALUES({$value},{$hd_classificacao})";
				$res = pg_query($con, $sql);
				
			}

		}

		if (!pg_last_error()) {
			$msg_success = true;
			unset($hd_classificacao, $descricao,$tipo_protocolo);
		} else {
			$msg_erro["msg"] = "Erro ao gravar {$text_class} de atendimento";
		}
	}
}

if ($_POST["btn_acao"] == "ativar") {
	$hd_classificacao = $_POST["hd_classificacao"];

	$sql = "SELECT hd_classificacao FROM tbl_hd_classificacao WHERE fabrica = {$login_fabrica} AND hd_classificacao = {$hd_classificacao}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		pg_query($con, "BEGIN");

		$sql = "UPDATE tbl_hd_classificacao SET ativo = TRUE WHERE fabrica = {$login_fabrica} AND hd_classificacao = {$hd_classificacao}";
		$res = pg_query($con, $sql);

		if (!pg_last_error()) {
			pg_query($con, "COMMIT");
			echo "success";
		} else {
			pg_query($con, "ROLLBACK");
			echo "error";
		}
	}

	exit;
}

if ($_POST["btn_acao"] == "inativar") {
	$hd_classificacao = $_POST["hd_classificacao"];

	$sql = "SELECT hd_classificacao, descricao FROM tbl_hd_classificacao WHERE fabrica = {$login_fabrica} AND hd_classificacao = {$hd_classificacao}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {

		$descricao_cla = pg_fetch_result($res, 0, descricao);
		if ($descricao_cla !== 'PROJETO - BACKOFFICE CENTRALIZAÇÃO' ){
			pg_query($con, "BEGIN");

			$sql = "UPDATE tbl_hd_classificacao SET ativo = FALSE WHERE fabrica = {$login_fabrica} AND hd_classificacao = {$hd_classificacao}";
			$res = pg_query($con, $sql);

			if (!pg_last_error()) {
				pg_query($con, "COMMIT");
				echo "success";
			} else {
				pg_query($con, "ROLLBACK");
				echo "error";
			}
		} else {
			echo "fixo";
		}
	}

	exit;
}

if (!empty($_GET["hd_classificacao"])) {
	$hd_classificacao = $_GET["hd_classificacao"];

	$sql = "SELECT abre_os_pre_os,descricao, envia_email, script_falha, obriga_campos
			FROM tbl_hd_classificacao
			WHERE fabrica = $login_fabrica
			AND hd_classificacao = $hd_classificacao;";

	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$descricao       = pg_result($res, 0, 'descricao');
		$notificar_posto = pg_fetch_result($res, 0, 'envia_email');
		$script_falha    = pg_fetch_result($res, 0, "script_falha");
		$obriga_campos 	 = pg_fetch_result($res, 0, "obriga_campos");
		$abre_os_pre_os 	 = pg_fetch_result($res, 0, "abre_os_pre_os");

		if(in_array($login_fabrica, array(169,170))){

			$sql = "SELECT hd_tipo_chamado FROM tbl_hd_tipo_chamado_vinculo WHERE hd_classificacao = {$hd_classificacao}";
			$res = pg_query($con,$sql);
			$result = pg_fetch_all($res);
			
			if(pg_num_rows($res) > 0){
				foreach ($result as $key => $value) {
					$tipo_protocolo[] = $value['hd_tipo_chamado'];
				}
				
			}
		}

	} else {
		$msg_erro["msg"][] = $text_class." de atentimento não encontrada";
	}
}

$disabled = "";

if ($login_fabrica == 52 && ($descricao == "Orientação" || $descricao == "ORIENTAÇÃO")) {
	$disabled = " disabled='disabled' ";
}

$layout_menu = "cadastro";
$title       = "CADASTRO DE ".strtoupper($text_class)." DE ATENDIMENTO";
$title_page  = "Cadastro";

if ($_GET["hd_classificacao"] || strlen($hd_classificacao) > 0) {
	$title_page = "Alteração de Cadastro";
}

include 'cabecalho_new.php';

$plugins = array(
	"multiselect"
);

include "plugin_loader.php";

if ($msg_success) {
?>
    <div class="alert alert-success">
		<h4><?php echo $text_class;?> de atendimento, gravada com sucesso</h4>
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

		var text_class = "<?php echo $text_class;?>";
		$(document).on("click", "button[name=ativar]", function () {
			if (ajaxAction()) {
				var that     = $(this);
				var hd_classificacao = $(this).attr("rel");

				$.ajax({
					async: false,
					url: "<?=$_SERVER['PHP_SELF']?>",
					type: "POST",
					dataType: "JSON",
					data: { btn_acao: "ativar", hd_classificacao: hd_classificacao },
					beforeSend: function () {
						loading("show");
					},
					complete: function (data) {
						data = data.responseText;

						if (data == "success") {
							$(that).removeClass("btn-success").addClass("btn-danger");
							$(that).attr({ "name": "inativar", "title": "Inativar "+text_class });
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
				var hd_classificacao = $(this).attr("rel");

				$.ajax({
					async: false,
					url: "<?=$_SERVER['PHP_SELF']?>",
					type: "POST",
					dataType: "JSON",
					data: { btn_acao: "inativar", hd_classificacao: hd_classificacao },
					beforeSend: function () {
						loading("show");
					},
					complete: function (data) {
						data = data.responseText;

						if (data == "success") {
							$(that).removeClass("btn-danger").addClass("btn-success");
							$(that).attr({ "name": "ativar", "title": "Ativar "+text_class });
							$(that).text("Ativar");
							$(that).parents("tr").find("img[name=visivel]").attr({ "src": "imagens/status_vermelho.png?" + (new Date()).getTime() });
						}

						if(data == "fixo") {
							alert('A '+text_class+' não pode ser Inativada!');
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

<form name="frm_condicao" method="POST" class="form-search form-inline tc_formulario" action="cadastro_classificacao_atendimento.php" >
	<div class='titulo_tabela '><?=$title_page?></div>
	<br/>
	<input type="hidden" name="hd_classificacao" value="<?=$hd_classificacao?>" />
	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span8'>
				<div class='control-group <?=(in_array("descricao", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao'>Descrição</label>
					<div class='controls controls-row'>
						<div class='span12'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="descricao" id="descricao" size="12" class='span12' maxlength="50" value= "<?=$descricao?>" <?php echo $disabled; ?>/>
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
	<?php } ?>

	<?php if(in_array($login_fabrica, array(169,170,178,186))){ ?>
	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span8">
			<div class="control-group">
				<?php if (in_array($login_fabrica, array(178,186))){ ?>
					<label class="checkbox">
				    	<input type="checkbox" name="obriga_campos" <?=($obriga_campos == "t" OR (count($msg_erro["msg"]) == 0 AND empty($_GET["hd_classificacao"]))) ? "checked" : ""?> value='t'> Campos Obrigatórios
				    </label>
				<?php }else{?>
					<label class="checkbox">
				    	<input type="checkbox" name="notificar_posto" <?=($notificar_posto == "t") ? "checked" : ""?> value='t'> Notificar Posto
				    </label>
					<label class="checkbox">
				    	<input type="checkbox" name="script_falha" <?=($script_falha == "t") ? "checked" : ""?> value='t'> Obriga Script de Falha
				    </label>
			    <?php } ?>
			</div>
		</div>
	</div>
	<?php } ?>
	<?php if ($usaScriptFalha == 't' AND $login_fabrica <> 175){ ?>
	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span8">
			<div class="control-group">
				<label class="checkbox">
			    	<input type="checkbox" name="script_falha" <?=($script_falha == "t") ? "checked" : ""?> value='t'> Obriga Script de Falha
			    </label>
			</div>
		</div>
	</div>
	<?php } ?>	
	<?php if ($login_fabrica == 195){ ?>
	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span8">
			<div class="control-group <?=(in_array("abre_os_pre_os", $msg_erro["campos"])) ? "error" : ""?>">
				<label class="checkbox">
			    	<input type="radio" name="abre_os_pre_os" <?=($abre_os_pre_os == "os") ? "checked" : ""?> value='os'> Abrir OS
			    	<input type="radio" name="abre_os_pre_os" <?=($abre_os_pre_os == "pre") ? "checked" : ""?> value='pre'> Abrir Pré-OS
			    </label>
			</div>
		</div>
	</div>
	<?php } ?>
	<p><br/>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
		<button class='btn' type="button"  onclick="submitForm($(this).parents('form'));" <?php echo $disabled; ?>>Gravar</button>
		<?php
		if (strlen($_GET["hd_classificacao"]) > 0) {
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
			<th><?php echo $text_class;?> de Atendimento</th>
			<?php if(in_array($login_fabrica, array(169,170))){ ?>
				<th>Tipo Protocolo</th>
				<th>Notificar Posto</th>
				<th>Obriga Script de Falha</th>
			<?php } ?>
			<?php if(in_array($login_fabrica, array(195))){ ?>
				<th>Abre O.S.</th>
				<th>Abre Pré-O.S.</th>
			<?php } ?>
			<th>Ações</th>
		</tr>
	</thead>
	<tbody>
		<?php
			$sql = "SELECT abre_os_pre_os,descricao,hd_classificacao,ativo, envia_email, script_falha
					FROM tbl_hd_classificacao
					WHERE fabrica = $login_fabrica
					ORDER BY descricao";
			$res = pg_query($con, $sql);

			$rows = pg_num_rows($res);

			for ($i = 0; $i < $rows; $i++) {
				$descricao   = pg_fetch_result($res, $i, "descricao");
				$hd_classificacao   = pg_fetch_result($res, $i, "hd_classificacao");
				$ativo   = pg_fetch_result($res, $i, "ativo");
				$envia_email = pg_fetch_result($res, $i, 'envia_email');
				$script_falha = pg_fetch_result($res, $i, "script_falha");
				$abre_os_pre_os = pg_fetch_result($res, $i, "abre_os_pre_os");

				$envia_email = ($envia_email == "t") ? "Sim" : "Não" ;
				$script_falha = ($script_falha == "t") ? "Sim" : "Não";

				$sqlTipoProtocolo = "SELECT tbl_hd_tipo_chamado.descricao
						FROM tbl_hd_tipo_chamado
						JOIN tbl_hd_tipo_chamado_vinculo USING(hd_tipo_chamado)
						WHERE tbl_hd_tipo_chamado.fabrica = {$login_fabrica}
						AND tbl_hd_tipo_chamado_vinculo.hd_classificacao = {$hd_classificacao}";
				$resTipoProtocolo = pg_query($con,$sqlTipoProtocolo);
				$result = pg_fetch_all($resTipoProtocolo);
				unset($tiposProtocolo);


				if(pg_num_rows($resTipoProtocolo) > 0){
					foreach ($result as $key => $value) {
						$tiposProtocolo[] = $value['descricao'];
					}
					
				}

				echo "<tr>
					<td><a href='{$_SERVER['PHP_SELF']}?hd_classificacao={$hd_classificacao}' >{$descricao}</a></td>";
				if(in_array($login_fabrica, array(169,170))){
				?>
					<td nowrap><?php echo implode(' / ',$tiposProtocolo); ?></td>
	                <td class='tac'><img src="imagens/<?=($envia_email == 'Sim') ? 'status_verde.png' : 'status_vermelho.png'?>"/></td>
					<td class='tac'><img src="imagens/<?=($script_falha == 'Sim') ? 'status_verde.png' : 'status_vermelho.png'?>"/></td>
				<?php
				}



				if(in_array($login_fabrica, array(195))){
					if ($abre_os_pre_os == "os") {
						echo '
						<td class="tac">
							<img src="imagens/status_verde.png"/>
						</td>';
					} else {
						echo '
							<td class="tac">
								<img src="imagens/status_vermelho.png"/>
							</td>';
					}

					if ($abre_os_pre_os == "pre") {
						echo '
						<td class="tac">
							<img src="imagens/status_verde.png"/>
						</td>';
					} else {
						echo '
							<td class="tac">
								<img src="imagens/status_vermelho.png"/>
							</td>';
					}
					
				}






				echo "<td class='tac'>";
				if ($ativo != "t") {
					echo "<button type='button' rel='{$hd_classificacao}' name='ativar' class='btn btn-small btn-success' title='Ativar {$text_class}' >Ativar</button>";
				} else {
					echo "<button type='button' rel='{$hd_classificacao}' name='inativar' class='btn btn-small btn-danger' title='Inativar {$text_class}' >Inativar</button>";
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
