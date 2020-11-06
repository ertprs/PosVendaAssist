<?php

	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';

	$admin_privilegios = "financeiro";

	$gera_automatico = trim($_GET["gera_automatico"]);

	if ($gera_automatico != 'automatico'){
		include "autentica_admin.php";
	}

	if(isset($_POST['aprova_avulso'])){

		$extrato_lancamento = $_POST["extrato_lancamento"];
		$aprova1 			= $_POST["aprova1"];
		$aprova2 			= $_POST["aprova2"];
		$campos_adicionais  = array();

		$sqlAvulso = "SELECT tbl_extrato_lancamento.valor 
					FROM tbl_extrato_lancamento 
					WHERE tbl_extrato_lancamento.extrato_lancamento = $extrato_lancamento";
		$resAvulso = pg_query($con, $sqlAvulso);

		if(pg_num_rows($resAvulso)>0){
			$valor = pg_fetch_result($resAvulso, 0, valor);
		}

		if($aprova1 == 't'){
			if($valor < 3000){
				$campos_adicionais['aprovacao'] = false;
			}else{
				$campos_adicionais['aprovacao'] = true;
				$campos_adicionais['valor_superior'] = true;
			}
		}

		if($aprova2 == 't'){
			$campos_adicionais['aprovacao'] = false;
		}

		$campos_adicionais['admin'] = $login_admin; 
		$campos_adicionais['data_aprovacao'] = date("Y-m-d");

		$campos_adicionais = json_encode($campos_adicionais);

		$sqlUpd = " UPDATE tbl_extrato_lancamento set campos_adicionais = '$campos_adicionais' WHERE extrato_lancamento = $extrato_lancamento";
		$resUpd = pg_query($con, $sqlUpd);
		if(strlen(pg_last_error($con))==0){
			echo json_encode(array('retorno'=>'Aprovado com sucesso.'));
		}else{
			echo json_encode(array('retorno'=>'Falha ao aprovar.'));
		}

		exit;
	}

	$msg_erro = array();

	$layout_menu = "financeiro";
	$title = "RELATÓRIO DE VALORES DE EXTRATOS";

	include 'cabecalho_new.php';

	$plugins = array(
		"mask",
		"datepicker",
		"shadowbox",
		"dataTable",
		"autocomplete"
	);

	include "plugin_loader.php";
	?>

	<script>
		$(function() {			
			$(".aprovar").click(function(){
				
				var extrato_lancamento = $(this).data('extrato_lancamento');
				var aprova1 = $(".aprovar").data('aprova1');
				var aprova2 = $(".aprovar").data('aprova2');
				
				$.ajax({
		            url: "<?php echo $_SERVER['PHP_SELF']; ?>",
		            type: 'POST',
		            data: {
		            	aprova_avulso:true,
		            	extrato_lancamento: extrato_lancamento,
		            	aprova1: aprova1,
		            	aprova2: aprova2		            	
		            },
		            beforeSend: function () {
	                    $(".loading_pre_cadastro_"+extrato_lancamento).show();
	                    $("#aprovar_"+extrato_lancamento).hide();
	                },
		            complete: function(data) {
		            	var data = $.parseJSON(data.responseText);
		            	alert(data.retorno);
		            	$(".loading_pre_cadastro_"+extrato_lancamento).hide();
		            }
		        });
			});
		});
	</script>
	
	<!-- FORMULÁRIO DE PESQUISA -->
	<?php 
		$permissao = false;
		$sqlAdmin = " SELECT parametros_adicionais FROM tbl_admin WHERE  fabrica = $login_fabrica and admin = $login_admin ";
		$resAdmin = pg_query($con, $sqlAdmin);
		if(pg_num_rows($resAdmin)>0){
			$parametros_adicionais = json_decode(pg_fetch_result($resAdmin, 0, parametros_adicionais), true);
		}

		if($parametros_adicionais['aprova_avulso_2'] == 't'){
			$permissao = true;
			$condvalor = " AND tbl_extrato_lancamento.valor > 3000  
						   AND campos_adicionais->>'valor_superior' = 'true' "; 
		}elseif($parametros_adicionais['aprova_avulso_1'] == 't'){
			$permissao = true;
			$condvalor = " AND campos_adicionais->>'valor_superior' is null  "; 
		}

		if (count($msg_erro["msg"]) > 0) {
		?>
			<br />
			<div class="alert alert-error"><h4><?=implode("<br />", $msg_erro["msg"])?></h4></div>
		<?php
		}else{

		$sqlAvulso = "SELECT tbl_extrato_lancamento.extrato_lancamento,  tbl_extrato_lancamento.historico, tbl_extrato_lancamento.extrato, tbl_extrato_lancamento.data_lancamento, tbl_extrato_lancamento.valor, tbl_posto_fabrica.codigo_posto, tbl_posto.nome, tbl_posto_fabrica.posto, 
		tbl_lancamento.descricao as descricao_lancamento, campos_adicionais->>'aprovacao' as aprovacao 
						from tbl_extrato_lancamento 
						join tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_extrato_lancamento.posto 
						and tbl_posto_fabrica.fabrica = $login_fabrica 
						join tbl_posto on tbl_posto_fabrica.posto = tbl_posto.posto
						join tbl_lancamento on tbl_lancamento.Lancamento = tbl_extrato_lancamento.lancamento and tbl_lancamento.fabrica = $login_fabrica 
						where tbl_extrato_lancamento.fabrica = $login_fabrica 
						and campos_adicionais->>'aprovacao' = 'true' 
						$condvalor
						order by extrato_lancamento desc ";
		$resAvulso = pg_query($con, $sqlAvulso);

		if(pg_num_rows($resAvulso)>0 and count($msg_erro["msg"]) == 0 ){
	?>
	<table style="width: 100%" class='tabela_item table table-striped table-bordered  table-large' >
		<tr class='titulo_coluna'>
			<th>Posto</th>
			<th>Data Lançamento</th>
			<th>Extrato</th>
			<th>Valor</th>
			<th>Histórico</th>
			<th>Observação</th>
			<?php if($permissao != false){ ?>
			<th>Ações</th>
			<?php } ?>
		</tr>
		<?php for($i=0; $i<pg_num_rows($resAvulso); $i++){ 
			$extrato_lancamento = pg_fetch_result($resAvulso, $i, extrato_lancamento);
			$extrato 	= pg_fetch_result($resAvulso, $i, extrato);
			$valor 		= number_format(pg_fetch_result($resAvulso, $i, valor), 2, ',', '');
			$data_lancamento = mostra_data(substr(pg_fetch_result($resAvulso, $i, data_lancamento),0,10));
			$nome 			= pg_fetch_result($resAvulso, $i, nome);
			$codigo_posto 	= pg_fetch_result($resAvulso, $i, codigo_posto);
			$historico 		= pg_fetch_result($resAvulso, $i, historico);
			$descricao_lancamento = pg_fetch_result($resAvulso, $i, descricao_lancamento);

		?>
		<tr>
			<td><?=$codigo_posto . " - " . $nome?></td>
			<td class='tac'><?=$data_lancamento?></td>
			<td class="tac"><?=$extrato?></td>
			<td class="tac">R$ <?=$valor?></td>
			<td class="tac"><?=$historico?></td>
			<td class="tac"><?=$descricao_lancamento?></td>
			<?php if($permissao != false){ ?>
				<td class="tac"><button data-aprova1="<?=$parametros_adicionais['aprova_avulso_1']?>" data-aprova2="<?=$parametros_adicionais['aprova_avulso_2']?>" data-extrato_lancamento="<?=$extrato_lancamento?>" id="aprovar_<?=$extrato_lancamento?>" class="btn btn-success aprovar">Aprovar</button>
				<img src="imagens/loading_img.gif" style="display: none; height: 20px; width: 20px;" class="loading_pre_cadastro_<?=$extrato_lancamento?>" />
				</td>
			<?php } ?>


		</tr>
		<?php } ?>
	</table>
	<?php }else{ ?>
		<div class="alert alert-warning"><h4>Nenhum registro encontrado</h4></div>
	<?php } ?>
</div>

<?php
}

echo "<br />";

include 'rodape.php';

?>

