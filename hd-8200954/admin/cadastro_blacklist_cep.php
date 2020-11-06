<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["btn_acao"] == "excluir") {

	$posto_cep_atendimento = $_POST["posto_cep_atendimento"];

	$sql = "SELECT posto_cep_atendimento FROM tbl_posto_cep_atendimento WHERE posto_cep_atendimento = {$posto_cep_atendimento} AND fabrica = {$login_fabrica}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {

		$sql = "DELETE FROM tbl_posto_cep_atendimento WHERE fabrica = {$login_fabrica} AND posto_cep_atendimento = {$posto_cep_atendimento}";
		$res = pg_query($con, $sql);

		if (!pg_last_error()) {
			echo json_encode(array("retorno" => utf8_encode("success"),"posto_cep_atendimento" => utf8_encode($posto_cep_atendimento)));
		} else {
			echo json_encode(array("retorno" => utf8_encode("Erro ao deletar registro.")));
		}
	}else{
		echo json_encode(array("retorno" => utf8_encode("Registro não encontrado.")));
	}
	exit;
}


if ($_POST["importa_csv"] == true) {

    $arquivo = $_FILES["upload"];

	if ($arquivo["size"] == 0) {
        $msg_erro["msg"][]    = "Insira o arquivo CSV";
        $msg_erro["campos"][] = "upload";
    } else {

    	$tipo = $arquivo['type'];

    	if ($tipo == 'text/plain' OR $tipo == 'text/csv'){
    		$arquivo = file_get_contents($arquivo["tmp_name"]);
	        $trata_arquivo = str_replace("\r\n", "\n", $arquivo);
	        $trata_arquivo = str_replace("\r", "\n", $arquivo);
	        $arquivo = explode("\n", $trata_arquivo);
	        $arquivo = array_filter($arquivo);

	        $sql = "SELECT tbl_fabrica.posto_fabrica FROM tbl_fabrica WHERE fabrica = {$login_fabrica}";
			$res = pg_query($con, $sql);
			if(pg_num_rows($res) > 0){
				$posto = pg_fetch_result($res, 0, 'posto_fabrica');
			}

			$ceps_carregados = array();

	        foreach ($arquivo as $key => $value) {
	        	if ($key == 0) {
	                continue;
	            }

	            $limpa_cep = str_replace(';', '', trim($value));
	            $cep = str_replace('-', '', trim($limpa_cep));

	            if (strlen($cep) <> 8) {
	                continue;
	            } else {
	                $ceps_carregados[] = $cep;
	            }
	        }

	        if (!empty($ceps_carregados)) {

	            $ceps_cadastrados = array();
	           	$sql = "SELECT cep_inicial
						FROM tbl_posto_cep_atendimento
						WHERE fabrica = {$login_fabrica}
						AND posto = {$posto}";
				$res = pg_query($con,$sql);

				$ceps_cadastrados = array();

				if (pg_num_rows($res) > 0){
					for ($i=0; $i < pg_num_rows($res) ; $i++) {
						$ceps_cadastrados[] = pg_fetch_result($res, $i, 'cep_inicial');
					}
				}

	            $ceps_novos = array_diff($ceps_carregados, $ceps_cadastrados);

	            if (!empty($ceps_novos)) {
	                foreach ($ceps_novos as $cep_novo) {
                		$sql = "INSERT INTO tbl_posto_cep_atendimento
									(fabrica,posto,cep_inicial,cep_final,blacklist)
								VALUES
									({$login_fabrica}, {$posto}, '{$cep_novo}', '{$cep_novo}', true)";
						$res = pg_query($con, $sql);

	                    if (strlen(pg_last_error()) > 0) {
	                        $msg_erro["msg"][]    = "Erro: CEP: {$cep_novo}, não cadastrado";
	                        $msg_erro["campos"][] = "";
	                    } else {
	                        $success["msg_success"][] = "CEP: {$cep_novo}, cadastrado";
	                    }
	                }
	            } else {
	                $msg_erro["msg"][]    = "CEP(s) já cadastrado(s)";
	                $msg_erro["campos"][] = "";
	            }
	        } else {
	            $msg_erro["msg"][]    = "Erro ao carregar o arquivo, verifique.";
	            $msg_erro["campos"][] = "";
	        }
    	}else{
    		$msg_erro["msg"][]    = "O arquivo deve ser do tipo CSV/TXT";
        	$msg_erro["campos"][] = "upload";
    	}
    }
}

if ($_POST["btn_acao"] == "submit") {
	$cep = str_replace("-", "", $_POST['cep']);

	if(empty($cep)){
		$msg_erro["msg"][]    = "O campo CEP é obrigatório.";
		$msg_erro["campos"][] = "cep";
	}else{
		$sql = "SELECT tbl_fabrica.posto_fabrica FROM tbl_fabrica WHERE fabrica = {$login_fabrica}";
		$res = pg_query($con, $sql);

		if(pg_num_rows($res) > 0){
			$posto = pg_fetch_result($res, 0, 'posto_fabrica');
		}

		$sql = "SELECT posto_cep_atendimento
				FROM tbl_posto_cep_atendimento
				WHERE fabrica = {$login_fabrica}
				AND posto = {$posto}
				AND cep_inicial = '{$cep}' ";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){
			$msg_erro["msg"][] = "CEP já esta cadastrado na blacklist.";
			$msg_erro["campos"][] = "cep";
		}

		if (!count($msg_erro["msg"])) {
			$sql = "INSERT INTO
						tbl_posto_cep_atendimento
						(fabrica,posto,cep_inicial,cep_final,blacklist)
					VALUES
						({$login_fabrica}, {$posto}, '{$cep}', '{$cep}', true)";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error($con)) > 0) {
				$msg_erro["msg"][] = "Erro ao gravar CEP.";
			}else{
				$msg_success = "CEP cadastrado com sucesso.";
			}
		}
	}
}

if (in_array($login_fabrica, [169,170])) {
	$limit = "LIMIT 500";
}

if($_POST['btn_pesquisa'] == 'Pesquisar'){
	$cep = str_replace("-", "", $_POST['cep']);

	if(strlen($cep) > 0){
		$where = " AND tbl_posto_cep_atendimento.cep_inicial = '$cep'";
	}
	$sql = "SELECT 		tbl_posto_cep_atendimento.posto_cep_atendimento,
				tbl_posto_cep_atendimento.cep_inicial AS cep,
				TO_CHAR(tbl_posto_cep_atendimento.data_input, 'DD/MM/YYYY') AS data
		FROM 	tbl_posto_cep_atendimento
		JOIN 	tbl_fabrica ON tbl_fabrica.posto_fabrica = tbl_posto_cep_atendimento.posto
			AND tbl_fabrica.fabrica = {$login_fabrica}
		WHERE 	tbl_posto_cep_atendimento.fabrica = {$login_fabrica}
			AND tbl_posto_cep_atendimento.blacklist = true
		{$where}
		{$limit};";

	$resSubmit = pg_query($con, $sql);
}

$layout_menu = "cadastro";
$title = "Cadastro de blacklist de CEP";
include 'cabecalho_new.php';


$plugins = array(
	"autocomplete",
	"maskedinput",
	"dataTable"
);

include("plugin_loader.php");
?>

<script type="text/javascript">
	var hora = new Date();
	var engana = hora.getTime();

	$(function() {
		$("#cep").mask("99999-999",{placeholder:""});

		$("#upload").change(function(){
			$("#file-name").text($("#upload").val());
		});
	});

	$(document).on('click','button.excluir', function(){
		if (confirm('Deseja excluir o registro ?')) {
			var btn = $(this);
	        var text = $(this).text();
	        var posto_cep_atendimento = $(btn).data('posto_cep_atendimento');
	        var obj_datatable = $("#resultado").dataTable()

	        $(btn).prop({disabled: true}).text("Excluindo...");
	        $.ajax({
	            method: "POST",
	            url: "<?=$_SERVER['PHP_SELF']?>",
	            data: { btn_acao: 'excluir', posto_cep_atendimento: posto_cep_atendimento},
	            timeout: 8000
	        }).fail(function(){
	        	alert("Não foi possível excluir o registro, tempo limite esgotado!");
	        }).done(function(data) {
	            data = JSON.parse(data);
	            if (data.retorno == "success") {
	                $(btn).text("Excluido");
	                setTimeout(function(){
	                	$(obj_datatable.fnGetData()).each(function(idx,elem){
	                		if($(elem[2]).data('posto_cep_atendimento') == posto_cep_atendimento){
	                			obj_datatable.fnDeleteRow(idx);
	                			return;
	                		}
	                	});
	                }, 1000);
	            }else{
	                $(btn).prop({disabled: false}).text(text);
				}
	        });
	    }else{
	    	return false;
	    }
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

if (count($success["msg_success"]) > 0) {
?>
	<div class="alert alert-success">
		<h4><?=implode("<br />", $success["msg_success"])?></h4>
    </div>
<?php
}

if(strlen($msg_success) > 0){
?>
	<div class="alert alert-success">
		<h4><?=$msg_success?></h4>
    </div>
<?php
}
?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
	<div class='titulo_tabela '>Cadastra Cep</div>
	<br/>

	<div class='row-fluid'>
		<div class='span2'></div>
		<div class="span4">
                <div class='control-group <?=(in_array('cep', $msg_erro['campos'])) ? "error" : "" ?>' >
                    <label class="control-label" for="cep">CEP</label>
                    <div class="controls controls-row">
                        <div class="span6">
                            <h5 class='asteristico'>*</h5>
                            <input id="cep" name="cep" class="span12" type="text" value="<?=$cep?>"/>
                        </div>
                    </div>
                </div>
            </div>
		<div class='span6'></div>
	</div>

	<p><br/>
		<input class='btn' name="btn_pesquisa" type="submit" value='Pesquisar'>
		<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
		<button class='btn btn-warning' type="button"  onclick="window.location = '<?=$_SERVER["PHP_SELF"]?>';">Limpar</button>
	</p><br/>
</form>

<form name="upload_cep" id="upload_cep" method="post" class="form-search form-inline tc_formulario" action="<?= $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data">
    <div class="titulo_tabela" >Upload arquivo CSV/TXT</div>
    <br />
    <div class="row-fluid" >
        <div class="span2" ></div>
        <div class="span8">
            <br />
            <div class="alert">
               <h4> Layout do arquivo: </h4>
               	CEP <br/>
                11111-111;<br />
                99999-999;</b>
                <br /> separados por ponto e virgula (;)
            </div>
            <br />
        </div>
        <div class="span2" ></div>
    </div>
    <div class="row-fluid" >
        <div class="span2" ></div>
        <div class="span5" >
            <div class="control-group" >
                <label style="background-color: #3498db; border-radius: 5px; color: #fff; cursor: pointer; margin-top: 20px; padding: 6px 20px; " class="control-label" for="upload" >
                	Selecionar um arquivo CSV/TXT &#187;
                </label>
                <input type="hidden" name="importa_csv" id="importa_csv" value="true" />
                <input type="file" style="display: none;" name="upload" id="upload" class="span12" />
                <span id='file-name'></span>
            </div>
        </div>
        <div class="span2">
            <div class="controls controls-row" >
                <div class="span8" >
                    <br />
                    <input type="submit" class="btn btn-primary" data-loading-text="Realizando upload..." value="Realizar Upload" />
                </div>
            </div>
        </div>
    </div>
    <br />
</form>

<?php
if (isset($resSubmit)) {

		if (pg_num_rows($resSubmit) > 0) {
			echo "<br />";
			$count = pg_num_rows($resSubmit);
		?>
			<table id="resultado" class='table table-striped table-bordered table-hover table-fixed' >
				<thead>
					<tr class='titulo_coluna' >
						<th>Cep</th>
						<th>Data Bloqueio</th>
						<th>Ação</th>
                    </tr>
				</thead>
				<tbody>
					<?php
					for ($i = 0; $i < $count; $i++) {
						$cep      				= pg_fetch_result($resSubmit, $i, 'cep');
						$cep 					= preg_replace('/(\d{5})(\d{3})/','$1-$2',$cep);
						$data 					= pg_fetch_result($resSubmit, $i, 'data');
						$posto_cep_atendimento 	= pg_fetch_result($resSubmit, $i, 'posto_cep_atendimento');
					?>
						<tr id='<?=$posto_cep_atendimento?>'>
								<td class='tac' style='vertical-align: middle;'><?=$cep?></td>
								<td class='tac' style='vertical-align: middle;'><?=$data?></td>
								<td style='vertical-align: middle;' class='tac'>
									<button class='btn btn-danger btn-small excluir' data-posto_cep_atendimento='<?=$posto_cep_atendimento?>'>Excluir</button>
								</td>
						</tr>
					<?php
					}
					?>
				</tbody>
			</table>

			<?php if(pg_num_rows($resSubmit) > 50){ ?>
				<script>
				$.dataTableLoad({ table: "#resultado" });
			</script>
			<br />
		<?php }
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
