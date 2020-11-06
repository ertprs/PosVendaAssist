<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

if (strlen($_POST["btnacao"]) > 0){
	$btnacao = trim($_POST["btnacao"]);
}

if ($btnacao == "deletar") {
	$causa_troca = $_POST["causa_troca"];

	$res = pg_query($con,"BEGIN TRANSACTION");

	$sql = "DELETE FROM tbl_causa_troca
			WHERE  tbl_causa_troca.fabrica     = {$login_fabrica}
			AND    tbl_causa_troca.causa_troca = {$causa_troca};";
	$res = pg_query($con,$sql);

	if (pg_last_error($con)) {
		$res    = pg_query($con,"ROLLBACK TRANSACTION");
		$result = array(
			"success" => false,
			"message" => utf8_encode(traduz("Causa da troca não pode ser apagada, já é usada em alguma OS"))
		);

	}else{
		$res    = pg_query($con,"COMMIT TRANSACTION");
		$result = array(
			"success" => true,
			"message" => utf8_encode(traduz("Deletado com Sucesso!"))
		);
	}

	echo json_encode($result);
	exit;
}

if ($btnacao == "gravar"){
	$causa_troca = $_POST["causa_troca"];

	if (strlen(trim($_POST["descricao"])) > 0){
		if (strpos($_POST["descricao"], "'") === false) {
			$descricao = "'". utf8_encode(trim($_POST["descricao"])) ."'";

		} else {
			$descricao = "E'". str_replace("'", "\'", utf8_encode(trim($_POST["descricao"]))) ."'";
		}
	} else {
		$erro = traduz("Preencha todos os campos");
	}

	if (strlen(trim($_POST["codigo"])) > 0){
		$codigo = "'". trim($_POST["codigo"]) ."'";
	}else{
		$codigo = "null";
		$erro = traduz("Preencha todos os campos");
	}

	if (strlen($_POST["tipo"]) > 0){
		$tipo = "'". trim($_POST["tipo"]) ."'";
	}else{
		$tipo = "''";
	}

	if (strlen(trim($erro)) == 0) {
		$res = pg_query ($con,"BEGIN TRANSACTION");

		if (strlen($causa_troca) == 0) {
			$sql = "INSERT INTO tbl_causa_troca (
						fabrica  ,
						codigo   ,
						descricao,
						ativo	 ,
						tipo
					) VALUES (
						$login_fabrica,
						$codigo   ,
						$descricao,
						$ativo    ,
						$tipo
					) RETURNING causa_troca;";
			$res = pg_query($con,$sql);

			$causa_troca = pg_fetch_result($res, 0, "causa_troca");

			if (strlen(pg_last_error($con)) > 0) {
				$erro = pg_last_error($con);
			}
		}else{
			$sql = "UPDATE tbl_causa_troca SET
					codigo     = $codigo   ,
					descricao  = $descricao,
					ativo      = $ativo	   ,
					tipo	   = $tipo
			WHERE  tbl_causa_troca.fabrica     = $login_fabrica
			AND    tbl_causa_troca.causa_troca = $causa_troca";
			$res = pg_query($con,$sql);

			if (strlen(pg_last_error($con)) > 0) {
				$erro = pg_last_error($con);
			}
		}

		if (strlen(trim($erro)) == 0) {
			$res    = pg_query ($con,"COMMIT TRANSACTION");
			$result = array(
				"success"     => true,
				"causa_troca" => $causa_troca,
				"message"     => traduz("Gravado com Sucesso!")
			);
		}else{
			$res = pg_query ($con,"ROLLBACK TRANSACTION");
			$result = array(
				"success"     => false,
				"causa_troca" => $causa_troca,
				"message"     => utf8_encode(traduz($erro))
			);
		}
	} else {
		$result = array(
			"success"     => false,
			"causa_troca" => $causa_troca,
			"message"     => utf8_encode(traduz($erro))
		);
	}

	echo json_encode($result);
	exit;
}

$layout_menu = "cadastro";
$title       = traduz("CADASTRO DE CAUSA DA TROCA DE PRODUTOS");
include 'cabecalho_new.php';
include("plugin_loader.php");
?>
<style type="text/css">
	#td-success {
		background: #468847;
	}
</style>
<script type="text/javascript">
	$(function(){
		$("#btn_gravar").on("click", function(){
			var causa_troca  = $("#causa_troca").val();
			var codigo       = $("#codigo").val();
			var descricao    = $("#descricao").val();
			var descricao_es = $("#descricao_es").val();
			var tipo         = $("#tipo").val();
			var ativo        = $("#ativo").is(":checked");
			limpar_mensagem();
			$("#btn_gravar").prop("disabled", true).val('<?=traduz("Gravando")?>...');

			if(campo_preenchido(codigo) && campo_preenchido(descricao)){
				$.ajax({
                    url: "causa_troca_cadastro_new.php",
                    type: "POST",
                    data: {
                        btnacao       : "gravar",
                        causa_troca   : causa_troca,
						codigo        : codigo,
						descricao     : descricao,
						descricao_es  : descricao_es,
						tipo          : tipo,
						ativo         : ativo
                    },
                }).done(function(data){
                    data = JSON.parse(data);

                    if(data.success){
                    	var status_ativo = "false";

                    	if(ativo){
							ativo          = "status_verde.png";
							mensagem_ativo = "<?=traduz('Ativo')?>";
							status_ativo   = "true";
                    	} else {
							ativo          = "status_vermelho.png";
							mensagem_ativo = "<?=traduz('Inativo')?>";
                    	}

                    	if(!campo_preenchido(causa_troca)){
                    		causa_troca = data.causa_troca;

							var novo_registro = '<tr id="linha_' + causa_troca + '">';
							novo_registro     += criar_tr_causa_troca(causa_troca, codigo, descricao, tipo, ativo, status_ativo, mensagem_ativo);
							novo_registro     += "</tr>";

							$("#table_causa_troca > tbody").append(novo_registro);

                    	} else {
                    		var novo_registro = criar_tr_causa_troca(causa_troca, codigo, descricao, tipo, ativo, status_ativo, mensagem_ativo);
							$("#table_causa_troca > tbody tr[id=linha_" + causa_troca + "]").html(novo_registro);
                    	}

                        $("#linha_" + causa_troca + " > td").addClass("alert-success");
						$("#btn_gravar").prop("disabled", false).val('<?=traduz("Gravar")?>');
                        mensagem(data.message,"alert-success");
                        limpar_campos();

                        setTimeout(function(){
                        	$("#linha_" + causa_troca + " > td").removeClass("alert-success");
                            limpar_mensagem();
                        },2000);
                    } else {
                    	$("#btn_gravar").prop("disabled", false).val('<?=traduz("Gravar")?>');
                        mensagem(data.message,"alert-error");
                    }
                });
			} else {
				$("#codigo").addClass("error");
				$("#descricao").addClass("error");
				mensagem('<?=traduz("Preencha todos os campos obrigatorio")?>', "alert-error");
				$("#btn_gravar").prop("disabled", false).val('<?=traduz("Gravar")?>');
			}
		});

		$("#btn_limpar").on("click",function(){
			limpar_campos();
		});

		$(document).on("click","input.btn_editar",function(){
			limpar_campos();
			limpar_mensagem();
			var causa_troca = this.id.replace("btn_editar_","");
			$("#" + this.id).prop("disabled", true).val('<?=traduz("Editando")?>...');

			var codigo    = $("#codigo_"    + causa_troca).data('codigo');
			var descricao = $("#descricao_" + causa_troca).data('descricao');
			var tipo      = $("#tipo_"  	+ causa_troca).data('tipo');
			var ativo     = $("#ativo_" 	+ causa_troca).data('ativo');

			$("#causa_troca").val(causa_troca);
			$("#codigo").val(codigo);
			$("#descricao").val(descricao);
			$("#tipo").val(tipo);

			if(ativo){
				$("#ativo").prop("checked", true);
			} else {
				$("#ativo").prop("checked", false);
			}
			$("#" + this.id).prop("disabled", false).val('<?=traduz("Editar")?>');
		});

		$(document).on("click","input.btn_deletar",function(){
			var causa_troca = this.id.replace("btn_deletar_","");
			limpar_mensagem();
			$("#" + this.id).prop("disabled", true).val('<?=traduz("Apagando")?>...');

			if(campo_preenchido(causa_troca)){
				if(confirm("<?=traduz("Deseja realmente exluir?")?>")){
					$.ajax({
	                    url: "causa_troca_cadastro_new.php",
	                    type: "POST",
	                    data: {
	                        btnacao       : "deletar",
	                        causa_troca   : causa_troca
	                    },
	                }).done(function(data){
	                    data = JSON.parse(data);

	                    if(data.success){
	                    	$("#table_causa_troca > tbody > tr[id=linha_" + causa_troca).html("<td colspan='4'><div class='alert alert-success'><h4>" + data.message + "</h4></div></td>");
	                        
	                        setTimeout(function(){
	                            $("#table_causa_troca > tbody > tr[id=linha_" + causa_troca).remove();
	                        },2000);
	                    } else {
	                    	$("#" + this.id).prop("disabled", true).val('<?=traduz("Apagar")?>');
	                        mensagem(data.message, "alert-error");
	                    }
	                });
				}
			}
		});

		function criar_tr_causa_troca(causa_troca, codigo, descricao, tipo, ativo, status_ativo, mensagem_ativo){
			var novo_registro = '<td class="tac" id="codigo_' + causa_troca + '" data-codigo="' + codigo + '">';
			novo_registro += '<input type="hidden" id="causa_troca_' + causa_troca + '" value="' + causa_troca + '" />';
			novo_registro += codigo;
			novo_registro += '</td>';
			novo_registro += '<td class="tac" id="descricao_' + causa_troca + '" data-descricao="' + descricao + '">' + descricao + '</td>';
			<?php if($login_fabrica == 1){ ?>
			novo_registro += '<td class="tac" id="tipo_' + causa_troca + '" data-tipo="' + tipo + '">' + tipo + '</td>';
			<?php } ?>
			novo_registro += '<td class="tac" id="ativo_' + causa_troca + '" data-ativo="' + status_ativo + '" >';
			novo_registro += '<img name="ativo" src="imagens/' + ativo + '" title="' + mensagem_ativo + '"/>';
			novo_registro += '</td>';
			novo_registro += '<td class="tac">';
			novo_registro += '<input type="button" class="btn btn-info btn_editar"    id="btn_editar_' + causa_troca + '"  style="margin-right: 1%;" value="<?=traduz('Editar')?>"/>';
			novo_registro += '<input type="button" class="btn btn-danger btn_deletar" id="btn_deletar_' + causa_troca + '" value="<?=traduz('Apagar')?>"/>';
			novo_registro += '</td>';

			return novo_registro;
		}

		function campo_preenchido(campo){
			if(campo == "" || campo == " " || campo == undefined){
				return false;
			} else {
				return true;
			}

		}

		function mensagem(mensagem, tipo){
			limpar_mensagem();

			if(mensagem != ""){
				$("#mensagem").html("<h4>" + mensagem + "</h4>").addClass(tipo).show();
			}
		}

		function limpar_mensagem(){
			$("#mensagem").hide().html("").removeClass("alert-success alert-error");
		}

		function limpar_campos(){
			$("#causa_troca").val("");
			$("#codigo").val("").removeClass("error");
			$("#descricao").val("").removeClass("error");
			$("#descricao_es").val("");
			$("#tipo").val("");
			$("#ativo").prop("checked","");
		}
	});
</script>

<div class="row">
	<b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b>
</div>

<div class="alert" id="mensagem" style="display: none; text-align: center;"></div>

<form name="frm_causa_troca" method="post" action="<? $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>
	<input type="hidden" name="causa_troca" id="causa_troca" value="<?=$causa_troca?>">
	<div class='titulo_tabela '><?=traduz('Cadastro')?></div>
	<br/>
	<div class='row-fluid'>
		<div class="span2"></div>
		<div class="span4">
			<div class='control-group'>
				<label class='control-label' for='codigo'><?=traduz('Código')?></label>
				<div class='controls controls-row'>
					<div class='span7'>
						<h5 class='asteristico'>*</h5>
						<input type="text" id="codigo" name="codigo" maxlength='5' class='span12' value="<?=$codigo?>" >
					</div>
				</div>
			</div>
		</div>
		<div class="span4">
			<div class='control-group'>
				<label class='control-label' for='descricao'><?=traduz('Descrição')?></label>
				<div class='controls controls-row'>
					<div class='span12'>
						<h5 class='asteristico'>*</h5>
						<input type="text" id="descricao" name="descricao" class='span12' maxlength="100" value="<?=$descricao?>" >
					</div>
				</div>
			</div>
		</div>
		<div class="span2"></div>
	</div>
	<?php if($login_fabrica == 20){ ?>
		<div class='row-fluid'>
			<div class="span2"></div><div class="span4"></div>
			<div class="span4">
				<div class='control-group'>
					<label class='control-label' for='descricao_es'><?=traduz('Descrição Espanhol(*)')?></label>
					<div class='controls controls-row'>
						<div class='span12'>
							<h5 class='asteristico'>*</h5>
							<input type="text" id="descricao_es" name="descricao_es" class='span12' maxlength="50" value="<?=$descricao_es?>" >
						</div>
					</div>
				</div>
			</div>
			<div class="span2"></div>
		</div>
	<?php } ?>
	<?php if($login_fabrica == 1){ ?>
		<div class='row-fluid'>
			<div class="span2"></div>
			<div class="span4">
				<div class='control-group'>
					<label class='control-label' for='tipo'><?=traduz('Tipo')?></label>
					<div class='controls controls-row'>
						<div class="span4">
							<select name="tipo" id="tipo">
								<option value=""></option>
								<?php
								$array_tipo = array('T'=>'Todos','C'=>'Consumidor','R'=>'Revenda');
								foreach ($array_tipo as $key => $value) {
									$selected_linha = ( isset($tipo) and ($tipo == $key)) ? "SELECTED" : '' ;
								?>
									<option value="<?=$key?>" <?=$selected_linha?> >
										<?=$value?>
									</option>
								<?php
								}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>
			<div class="span4"></div>
			<div class="span2"></div>
		</div>
	<?php } ?>
	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span4">
			<label class="checkbox">
				<input type="checkbox" name="ativo" id="ativo" value="t" <?php if($ativo == "t") echo "CHECKED"; ?> > <?=traduz("Ativo")?>
			</label>
		</div>
		<div class="span2"></div>
	</div>
	<p><br/>
		<input type='button' name='btn_gravar' id="btn_gravar" class='btn btn_acao' 		 value='<?=traduz("Gravar")?>' alt="Gravar formulário" border='0' style='cursor:pointer;'>
		<input type='button' name='btn_limpar' id="btn_limpar" class='btn btn_acao btn-info' value='<?=traduz("Limpar")?>' alt="Limpar campos" 	   border='0' style='cursor:pointer;'>
	</p><br/>
</form>
<br>

	<table id="table_causa_troca" class='table table-striped table-bordered table-hover table-fixed'>
		<thead>
			<tr class='titulo_tabela' >
				<th colspan="4"><?=traduz('Relação da Causa de Troca de Produtos')?></th>
			</tr>
			<tr class='titulo_coluna'>
				<th><?=traduz('Código')?></th>
				<th><?=traduz('Descrição')?></th>
				<?php if($login_fabrica == 1){
					?>
					<th><?=traduz('Tipo')?></th>
					<?php 
				} 
				?>
				<th><?=traduz('Status')?></th>
				<th><?=traduz('Acao')?></th>
			</tr>
		</thead>
		<tbody>
		<?php
		$sql = "SELECT  tbl_causa_troca.causa_troca,
				tbl_causa_troca.codigo,
				tbl_causa_troca.tipo,
				tbl_causa_troca.descricao,
				tbl_causa_troca.ativo
			FROM    tbl_causa_troca
			WHERE   tbl_causa_troca.fabrica = $login_fabrica
			ORDER BY  tbl_causa_troca.codigo;";
		$res = pg_query ($con,$sql);

		if (pg_numrows($res) > 0) {
			for ($i = 0; $i < pg_numrows($res); $i++){
				$causa_troca = trim(pg_result($res,$i,'causa_troca'));
				$descricao   = trim(pg_result($res,$i,'descricao'));
				$codigo      = trim(pg_result($res,$i,'codigo'));
				$tipo        = trim(pg_result($res,$i,'tipo'));
				$ativo       = trim(pg_result($res,$i,'ativo'));
				$ativo_bd    = $ativo == 't' ? "true" : "false";

				$imagem  = $ativo == 't' ? 'status_verde.png' : 'status_vermelho.png';
				$ativo   = $ativo == 't' ? traduz('Ativo') : traduz('Inativo');
				$deletar = traduz("Apagar");
				$editar  = traduz("Editar");
				?>
				<tr id="linha_<?=$causa_troca?>">
					<td class="tac" id="codigo_<?=$causa_troca?>" data-codigo="<?=$codigo?>">
						<input type="hidden" id="causa_troca_<?=$causa_troca?>" value="<?=$causa_troca?>" />
						<?=$codigo?>
					</td>
					<td class="tac" id="descricao_<?=$causa_troca?>" data-descricao="<?=$descricao?>" ><?=$descricao?></td>
					<?php if($login_fabrica == 1){ ?>
					<td class="tac" id="tipo_<?=$causa_troca?>" data-tipo="<?=$array_tipo[$tipo]?>"><?=$array_tipo[$tipo];?></td>
					<?php } ?>
					<td class="tac" id="ativo_<?=$causa_troca?>" data-ativo="<?=$ativo_bd?>">
						<img name="ativo" src="imagens/<?=$imagem?>" title="<?=$ativo?>"/>
					</td>
					<td class="tac">
						<input type="button" class="btn btn-info btn_editar"    id="btn_editar_<?=$causa_troca?>"  value="<?=$editar?>"/>
						<input type="button" class="btn btn-danger btn_deletar" id="btn_deletar_<?=$causa_troca?>" value="<?=$deletar?>"/>
					</td>
				</tr>
				<?php
			}
		}
		?>

		</tbody>
	</table>
<?php
include "rodape.php";
?>
