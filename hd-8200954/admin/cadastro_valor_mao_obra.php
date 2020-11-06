<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["btn_acao"] == "submit") {

	$resT = pg_query($con,"BEGIN");
	$valores_mao_de_obra = array();

	$entrega_tecnica = array(
				"equipamento" 	=> str_replace(",",".", $_POST["equipamento"]) ,
				"entrega"			=> str_replace(",",".", $_POST["entrega"]) ,
				"hora"				=> str_replace(",",".", $_POST["hora"]) ,
				"deslocamento" 	=> str_replace(",",".", $_POST["deslocamento"]) ,
				"apartir" 			=> str_replace(",",".", $_POST["apartir"]) ,
			);

	foreach ($entrega_tecnica as $key => $value) {
		if (strlen($value) == 0 OR $value == "0.00" OR $value == 0.00) {
			$msg = traduz("Por Favor Preencher todos os valores");
		}
	}

	if(empty($msg)){

		$sql = "SELECT linha,nome as descricao from tbl_linha where fabrica = {$login_fabrica}";
		$reslinhas = pg_query($con,$sql);
		$linha_valores = array();
		$array_assist = array();
		while ($linha = pg_fetch_object($reslinhas)) {
			unset($linha_valores);

			if($_POST["assistencia_tecnica_linha_$linha->linha"] == $linha->linha) {

				$linha_valores["linha"] = $linha->linha;
				$linha_valores["valor_deslocamento"] = str_replace(",",".",$_POST["assistencia_tecnica_deslocamento_{$linha->linha}"] ) ;
				$linha_valores["valor_hora"] = str_replace(",",".",$_POST["assistencia_tecnica_hora_{$linha->linha}"] ) ;
				$linha_valores["tipo"] = $_POST["assistencia_tecnica_tipo_{$linha->linha}"] ;

				if($linha_valores["tipo"] == "apartir"){
					if ($linha_valores["valor_deslocamento"] == "0.00" or $linha_valores["valor_deslocamento"] == 0.00 or !strlen($linha_valores["valor_deslocamento"] )) {
						$msg = traduz("Por favor preencher os valores família: %", null, null, [$linha->descricao]);
						break;
					}
				}

				if ( $linha_valores["valor_hora"] == "0.00" or $linha_valores["valor_hora"] == 0.00 or !strlen($linha_valores["valor_hora"])) {
					$msg = traduz("Por favor preencher os valores família: %", null, null, [$linha->descricao]);
					break;
				}

				if($linha_valores["tipo"]=="deslocamento"){
					unset($linha_valores["valor_deslocamento"]);
				}

			}
			$array_assist[] = $linha_valores;
		}

	}

	if(empty($msg)){

		$novo_parametro["assistencia_tecnica"] =  $array_assist ;

		$novo_parametro["entrega_tecnica"] = $entrega_tecnica ;

		$sql = "SELECT parametros_adicionais
				FROM tbl_fabrica
				WHERE fabrica = {$login_fabrica} ";
		$res = pg_query($con,$sql);

		$parametros_adicionais = pg_fetch_result($res, 0, "parametros_adicionais");

		$parametros_adicionais = json_decode($parametros_adicionais,true);

		if(!empty($parametros_adicionais)){
			$parametros_adicionais["valores_mao_de_obra"] = $novo_parametro ;
		}else{
			$parametros_adicionais["valores_mao_de_obra"] = $novo_parametro ;
		}

		$parametros_adicionais = json_encode($parametros_adicionais);

		$sql = "UPDATE tbl_fabrica SET parametros_adicionais = '$parametros_adicionais' WHERE fabrica = $login_fabrica";
		$res = pg_query($con,$sql);
		$msg .= pg_errormessage($con);

	}

	if(!empty($msg)){
		$resT = pg_query($con,"ROLLBACK");
	}else{
		$resT = pg_query($con,"COMMIT");
		$sucesso = traduz("Gravado com Sucesso");
	}


}

$sql = "SELECT fabrica,nome,parametros_adicionais
	FROM tbl_fabrica
	WHERE parametros_adicionais notnull
	AND fabrica = {$login_fabrica}
	ORDER BY nome";
$resParametros = pg_query($con,$sql);


$parametros_adicionais = pg_fetch_result($res, 0, "parametros_adicionais");
$parametros_adicionais = json_decode($parametros_adicionais,true);

if(!empty($parametros_adicionais)){

	$novo_parametro = $parametros_adicionais["valores_mao_de_obra"];

}

	$layout_menu = "cadastros";
	$title = "Parâmetros de Mão de Obra";

	include "cabecalho_new.php";

	$plugins = array(
 		"alphanumeric",
 		"price_format"
	);

	include("plugin_loader.php");

?>
<style type="text/css">
	.deslocamento{
		display:none;
	}
</style>
<script type="text/javascript">

$(function() {

	function esconde_input(data,valor){
		var div ;
		if (valor == "apartir") {
			$(".deslocamento").find("input[name=assistencia_tecnica_deslocamento_"+data+"]").val('').show();
		}else{
			$(".deslocamento").find("input[name=assistencia_tecnica_deslocamento_"+data+"]").val('').hide();
		}
	}

	$(".control-row").find("input[type=radio]").each(function(){
		$(this).change(function(){
			esconde_input($(this).attr("data"),$(this).val());
		});
	});

	$('.numeric').priceFormat({
		prefix: '',
	     thousandsSeparator: '',
	     centsSeparator: ',',
	     centsLimit: 2
	});

	$(".integer").numeric();

});


</script>

<?php
	if (strlen($msg) > 0) {
?>
    <div class="alert alert-error">
        <h4><?=$msg?></h4>
    </div>
<?php
	}
?>
<? if (strlen($sucesso) > 0 AND strlen($msg)==0) { ?>
    <div class="alert alert-success">
        <h4><? echo $sucesso; ?></h4>
    </div>
<? } ?>

	<div class="row">
	    <b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b>
	</div>

	<form name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>
	    <div class='titulo_tabela '><?=traduz('ORDEM DE SERVIÇO DE ENTREGA TÉCNICA')?></div>
	    <br/>
		<div class='row-fluid'>
	     		<div class='span2'></div>

		  <div class='span4'>
				<div class='control-group <?=(in_array("valor", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='equipamento'><?=traduz('Valor por equipamento:')?></label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="equipamento" id="equipamento" class='span6 numeric' value="<? echo $novo_parametro['entrega_tecnica']['equipamento'] ?>" >
						</div>
					</div>
				</div>
			</div>

		  	<div class='span4'>
				<div class='control-group <?=(in_array("valor", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='entrega'><?=traduz('Valor por entrega:')?></label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="entrega"	id="entrega"  class='span6 numeric' value="<? echo $novo_parametro['entrega_tecnica']['entrega'] ?>" >
						</div>
					</div>
				</div>
			</div>

        		<div class='span2'></div>
	    	</div>
		<div class='row-fluid'>
	     		<div class='span2'></div>

    			<div class='span4'>
				<div class='control-group <?=(in_array("valor", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='hora'><?=traduz('Valor por hora:')?></label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="hora"	id="hora"  class='span6 numeric' value="<? echo $novo_parametro['entrega_tecnica']['hora']?>" >
						</div>
					</div>
				</div>
			</div>

		 	<div class='span4'>
				<div class='control-group <?=(in_array("valor", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='deslocamento'><?=traduz('Valor por deslocamento:')?></label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="deslocamento" id="deslocamento"  class='span6 numeric' value="<? echo $novo_parametro['entrega_tecnica']['deslocamento'] ?>" >
						</div>
					</div>
				</div>
			</div>

        		<div class='span2'></div>
	    	</div>
		<div class='row-fluid'>
	     		<div class='span2'></div>

		  <div class='span4'>
				<div class='control-group <?=(in_array("valor", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='apartir'><?=traduz('Valor apartir de x horas:')?></label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="apartir" id="apartir"  class='span6 integer' value="<? echo $novo_parametro['entrega_tecnica']['apartir']?>" >
						</div>
					</div>
				</div>
			</div>

		  	<div class='span4'></div>

        		<div class='span2'></div>
    		</div>


		<div class='titulo_tabela '><?=traduz('ORDEM DE SERVIÇO DE ASSISTÊNCIA TÉCNICA')?></div>
		<br />
		<br />

			<div class="row-fluid" >
				<div class="span2 " ></div>

				<!-- Família -->
				<div class="span2 " >
						<label class="control-label" ><strong><?=traduz('Linha')?></strong></label>
				</div>

				<!-- Tipo -->
				<div class="span2" >
					<label class="control-label" ><strong><?=traduz('Tipo de Pagamento')?></strong></label>
				</div>

				<!-- Deslocamento -->
				<div class="span2" >
						<label class="control-label" ><strong><?=traduz('Valor por hora de deslocamento')?></strong></label>
				</div>

				<!-- valor de mão de obra -->
				<div class="span2" >
					<label class="control-label" ><strong><?=traduz('Valor por hora trabalhada')?></strong></label>
				</div>

				<div class="span2" ></div>
			</div>

		<?php
			$sql = "SELECT linha,nome as descricao from tbl_linha where fabrica = {$login_fabrica}";
			$reslinhas = pg_query($con,$sql);
			while ($linha = pg_fetch_object($reslinhas)) {
				unset($valor_deslocamento,$valor_hora,$valor_tipo);
				foreach ($novo_parametro["assistencia_tecnica"] as $key => $value) {
					if($value["linha"] == $linha->linha){
						$valor_deslocamento = (!strlen($value['valor_deslocamento'])) ? $_POST["assistencia_tecnica_deslocamento_{$linha->linha}"] : $value['valor_deslocamento'] ;
						$valor_hora = (!strlen($value['valor_hora'])) ? $_POST["assistencia_tecnica_hora_{$linha->linha}"] : $value['valor_hora'] ;
						$valor_tipo = (!strlen($value['tipo'])) ? $_POST["assistencia_tecnica_tipo_{$linha->linha}"] : $value['tipo'] ;
					}
				}

		?>


			<div class="row-fluid div_linha"  >
				<div class="span2" ></div>
				<!-- Família -->
				<div class="span2" >
						<div class='control control-row'>
							<div class='span'>
								<input type="hidden"
									name="assistencia_tecnica_linha_<?=$linha->linha?>"
									class="form-control"
									value="<?=$linha->linha?>" ><?=$linha->descricao?></input>
							</div>
						</div>
				</div>
				<!-- Tipo -->
				<div class="span2" >
					<div class="control control-row" >
						<div class="radio">
								<p>
								<label><?=traduz('Hora Técnica')?></label>
								<input type="radio"
									data="<?=$linha->linha?>"
									name="assistencia_tecnica_tipo_<?=$linha->linha?>"
									value="apartir"
									<?php if ($valor_tipo == "apartir"){ echo "checked ='checked' "; }?> >
								</p>
								<p>
								<label><?=traduz('Hora Corrida')?></label>
								<input type="radio"
									data="<?=$linha->linha?>"
									name="assistencia_tecnica_tipo_<?=$linha->linha?>"
									value="deslocamento"
									<?php if ($valor_tipo == "deslocamento"){ echo "checked ='checked' "; }?> >
								</p>
						</div>

					</div>
				</div>
				<?
				unset($display);
				if($valor_tipo == "deslocamento"){
					$display = "style='display: none;'";
				}
				?>
				<!-- Deslocamento -->
				<div class="span2 deslocamento"  >
						<div class='control control-row'  >
									<input 	<?=$display?>
										type="text"
										name="assistencia_tecnica_deslocamento_<?=$linha->linha?>"
										class='span12 numeric'
										value="<?=str_replace(".", ".",$valor_deslocamento)?>"
									>
						</div>
				</div>
				<!-- valor de mão de obra -->
				<div class="span2" >
					<div class='control control-row'>
							<h5 class='asteristico'>*</h5>
							<input
								type="text"
								name="assistencia_tecnica_hora_<?=$linha->linha?>"
								id="valor"
								class='span12 numeric'
								value="<?=str_replace(".", ".",$valor_hora)?>"
							>
					</div>
				</div>

				<div class="span2" ></div>
			</div>
			<br />
		<?php
		}
		?>


        	<button class='btn ' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));"><?=traduz('Gravar')?></button>
        	<input type='hidden' id="btn_click" name='btn_acao' value='' />
    	</p>

    	<br />

	</form>


</div> <!-- Aqui fecha a DIV Container que abre no cabeçãlho -->
<?php
	include 'rodape.php';
?>
