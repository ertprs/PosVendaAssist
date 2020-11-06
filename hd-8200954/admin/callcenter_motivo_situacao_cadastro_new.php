<?php

	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	$admin_privilegios="cadastro";
	include 'autentica_admin.php';
	include 'funcoes.php';




	if($_GET['ajax']=='sim' AND $_GET['acao']=='ativar_inativar') { //HD-3261932
		$hd_situacao = trim($_GET['hd_situacao']);

		$sql_status = "SELECT ativo FROM tbl_hd_situacao WHERE hd_situacao = $hd_situacao AND fabrica = $login_fabrica";
		$res_status = pg_query($con, $sql_status);

		$status = pg_fetch_result($res_status,0,'ativo');
		if($status == "t"){
			$sql_inativar = "UPDATE tbl_hd_situacao SET
								ativo = 'f'
					WHERE hd_situacao = $hd_situacao
					AND fabrica = $login_fabrica";
			$res_inativar = pg_query($con,$sql_inativar);

			exit(json_encode(array("success" => "inativado")));
		}else{
			$sql_ativar = "UPDATE tbl_hd_situacao SET
								ativo = 't'
					WHERE hd_situacao = $hd_situacao
					AND fabrica = $login_fabrica";
			$res_ativar = pg_query($con,$sql_ativar);

			exit(json_encode(array("success" => "ativado")));
		}
		exit;
	}

	if(!empty($btn_acao)){
		$descricao         = $_POST['descricao'];
		$hd_chamado_situacao = $_POST['hd_chamado_situacao'];

		if($btn_acao == "cadastrar"){
			if(empty($descricao)){
				$msg_erro["msg"][] = "Preencha os campos obrigatórios";
				$msg_erro["campos"][] = "descricao";
			}else{
				$sql_descricao = "SELECT hd_situacao FROM tbl_hd_situacao WHERE fabrica = $login_fabrica AND descricao = '$descricao'";
				$res_descricao = pg_query($con, $sql_descricao);

				if(pg_num_rows($res_descricao) > 0){
					$msg_erro["msg"][]    = "Já existe essa descrição cadastrada";
					$msg_erro["campos"][] = "descricao";
				}
				if (!count($msg_erro["msg"])) {
					$sql = "INSERT INTO tbl_hd_situacao (
										 fabrica,
										 descricao,
										 tipo_registro,
										 resolvido
										) VALUES (
										 $login_fabrica,
										 '$descricao',
										 'Aberto',
										 false
										)";
					$res = pg_query($con,$sql);

					if (!count($msg_erro["msg"])) {
						$msg_success = "Cadastrado com sucesso";
					}
				}
			}
		}elseif($btn_acao == "atualizar"){
			if(empty($descricao)){
				$msg_erro["msg"][]    = "Informe a descrição da situação";
				$msg_erro["campos"][] = "descricao";
			}else{
				$sql = "UPDATE tbl_hd_situacao SET
									descricao = '$descricao'
						WHERE hd_situacao = $hd_chamado_situacao
						AND fabrica = $login_fabrica";
				$res = pg_query($con,$sql);

				if (!count($msg_erro["msg"])) {
					$msg_success = "Atualizado com sucesso";
				}
			}
		}
	}


	if($login_fabrica == 162){ //HD-3352176
		$title = "Cadastro de Motivos da Transferência";
		$nome_cadastro = "Motivo";
	}else{
		$title = "Cadastro de Situação";
		$nome_cadastro = "Situação";

	}

	$layout_menu = "cadastro";
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

	function carregaCampos(descricao,hd_chamado_situacao){
		$("#descricao").val(descricao);
		$("#hd_chamado_situacao").val(hd_chamado_situacao);
		$("#btn_acao").val('atualizar');
		$(".botao").val("Atualizar");
	}

	function ativa_inativa(hd_situacao,status){
		$.ajax({
            method: "GET",
            url: "<?php echo $_SERVER['PHP_SELF']; ?>",
            data: { ajax: 'sim', acao: 'ativar_inativar', hd_situacao: hd_situacao, status: status},
        }).done(function(data) {
            data = JSON.parse(data);

            if (data.success == 'inativado') {
            	$("#"+hd_situacao).removeClass("btn-danger");
            	$("#"+hd_situacao).addClass('btn-primary');
            	$("#"+hd_situacao).html('Ativar');

            	// $("#label_"+hd_situacao).removeClass("label-info");
            	// $("#label_"+hd_situacao).addClass('label-important');
            	$("#label_"+hd_situacao).html("<img src='imagens/status_vermelho.png'>");
            }else{
                $("#"+hd_situacao).removeClass("btn-primary");
            	$("#"+hd_situacao).addClass('btn-danger');
            	$("#"+hd_situacao).html('Inativar');

            	// $("#label_"+hd_situacao).removeClass("label-important");
            	// $("#label_"+hd_situacao).addClass('label-info');
            	$("#label_"+hd_situacao).html("<img src='imagens/status_verde.png'>");
            }
    	});
	}

</script>

<?php

if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}

if(strlen(trim($msg_success)) > 0){
?>
	<div class="alert alert-success">
		<h4><?=$msg_success?></h4>
    </div>
<?php
}
$titulo_tabela = ($login_fabrica <> 162) ? "Cadastro Situação" : "Cadastro Motivo" ;
?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name='frm_cadastro' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
<div class='titulo_tabela '><?=$titulo_tabela?></div>
		<br/>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span8'>
				
				<div class='control-group <?=(in_array("descricao", $msg_erro["campos"])) ? "error" : ""?>'>
				 <label class='control-label' for='descricao'>Descrição</label>
					<div class='controls controls-row'>
						
						<div class='span9'>
							<h5 class="asteristico">*</h5>
							<input type="text" id="descricao" name="descricao" class='span12' maxlength="50" value="<? echo $descricao ?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<p><br/>
			<input type="hidden" name="btn_acao" id="btn_acao" value="">
			<input type="hidden" name="hd_chamado_situacao" id="hd_chamado_situacao" value="">
			<input type="button" class='btn botao' value="Gravar" onclick="javascript: if(document.frm_cadastro.btn_acao.value ==''){document.frm_cadastro.btn_acao.value='cadastrar'; document.frm_cadastro.submit();} else{document.frm_cadastro.btn_acao.value='atualizar'; document.frm_cadastro.submit();}">
		</p><br/>
</form>

<?php
		$sql = "SELECT hd_situacao,descricao,ativo FROM tbl_hd_situacao WHERE fabrica = $login_fabrica ORDER BY descricao";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
	?>		
			<table id="motivo_situacao_cadastro" class='table table-striped table-bordered table-hover table-fixed' >
				<thead>
					<tr class='titulo_tabela'>
						<th colspan='7'><?=$nome_cadastro?></th>
					</tr>	
					</tr>
					<tr class='titulo_coluna'>
						<th class="tac"> Descrição </th>
						<th>Status</th>
						<th>Ação</th>
					</tr>
				</thead>
				<tbody>
					<?php
						for($i = 0; $i < pg_num_rows($res); $i++){
							$descricao = pg_fetch_result($res,$i,'descricao');
							$hd_situacao = pg_fetch_result($res,$i,'hd_situacao');
							$ativo = pg_fetch_result($res,$i,'ativo');

							if($ativo == 'f'){
								$class_btn = "btn-primary";
								$title_button = "Ativar";
								$status = "<img src='imagens/status_vermelho.png'>";
								$class_label = "label-important";
							}else{
								$class_btn = "btn-danger";
								$title_button = "Inativar";
								$status = "<img src='imagens/status_verde.png'>";
								$class_label = "label-info";
							}
					?>
							<tr>
								<td align="left"><a href="javascript: void(0);" onclick="carregaCampos(<?php echo "'$descricao','$hd_situacao'";?>);"><?php echo $descricao;?></a></td>
								<td class='tac'>
									<p id="label_<?=$hd_situacao?>"><?=$status?></p>
								</td>
								<td class='tac'>
									<button id="<?=$hd_situacao?>" class='btn btn-small <?=$class_btn?>' onclick="ativa_inativa(<?php echo "'$hd_situacao'"; ?>)" ><?=$title_button?></button>
								</td>
							</tr>
					<?php
						}
					?>
				</tbody>
			</table>

	</form>
 

	<?php
		}else{
			echo '
				<div class="container">
				<div class="alert">
					    <h4>Nenhum resultado encontrado</h4>
				</div>
				</div>';
		}
	?>


<?php include 'rodape.php'; ?>
