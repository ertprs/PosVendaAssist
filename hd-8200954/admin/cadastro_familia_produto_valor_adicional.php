<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'funcoes.php';

$admin_privilegios="cadastro";
include 'autentica_admin.php';
$qtde_linhas = 3;

if ($_POST["btn_acao"] == "submit") {

	$familia    = $_POST["familia"];
	$capacidade = array_filter($_POST["capacidade"]);
	$valor      = array_filter($_POST["valor"]);
	$capacidade_acima   = $_POST["capacidade_acima"];
	$valor_acima    	= $_POST["valor_acima"];

	# Validações
	if (!strlen($familia)) {
		$msg_erro["msg"]["familia"] = "Preencha os campos obrigatórios";
		$msg_erro["campos"][]   = "familia";
	}else{
		$sql = "SELECT descricao FROM tbl_familia WHERE familia = {$familia}";
		$res = pg_query($con,$sql);
		$desc_familia = pg_fetch_result($res, 0, 'descricao');
	}

	if((count($capacidade) == 0 OR count($valor) == 0) AND (empty($capacidade_acima) AND empty($valor_acima)) ){
		$msg_erro["msg"][] = "Preencha os campos obrigatórios";
		$msg_erro["campos"][]   = "valor";
	}

	if(count($msg_erro["msg"]) == 0){
		
		switch ($desc_familia) {
			case 'SERIE R22':
				$texto = "Carga de gás R-22, capacidade do equipamento de ";
			break;

			case 'SERIE A':
			case 'SERIE G':
				$texto = "Carga de gás R-410 A, capacidade do equipamento de ";
			break;			
		}

		for($j = 0; $j < count($capacidade); $j++){

			$valor_capacidade = $capacidade[$j];

			$sql = "SELECT 	produto 
					FROM tbl_produto 
					WHERE fabrica_i = {$login_fabrica}
					AND familia = {$familia}
					AND referencia ~* E'\\\w+".$valor_capacidade."'
					AND UPPER(descricao) ~ 'CONDENSADOR'";
			$res = pg_query($con,$sql);

			if(pg_num_rows($res) > 0){

				$carga = $texto . $capacidade[$j].".000 BTU/h";
				$valor_carga = $valor[$j];
				$valor_adicional = '{"'.$carga.'":"'.$valor_carga.'"}';

				for ($i=0; $i < pg_num_rows($res); $i++) { 
					$produto 	= pg_fetch_result($res, $i, 'produto');

					$sql = "UPDATE tbl_produto SET valores_adicionais = '$valor_adicional' WHERE produto = {$produto}";
					$resU = pg_query($con,$sql);

				}
			}
		}

		if(!empty($capacidade_acima) AND !empty($valor_acima)){

			$sql = "SELECT 	produto, referencia
					FROM tbl_produto 
					WHERE fabrica_i = {$login_fabrica}
					AND familia = {$familia}
					AND UPPER(descricao) ~ 'CONDENSADOR'";
			$res = pg_query($con,$sql);

			if(pg_num_rows($res) > 0){

				for ($i=0; $i < pg_num_rows($res); $i++) { 
					$produto 	= pg_fetch_result($res, $i, 'produto');
					$referencia = pg_fetch_result($res, $i, 'referencia');
					preg_match("/^[A-Za-z]{1,}[0-9]{2}/", $referencia, $btu);
					$btu = preg_replace("/\D/", "", $btu[0]);

					if($btu > $capacidade_acima){
						$carga = $texto . $btu.".000 BTU/h";
						$valor_carga = $valor_acima;
						$valor_adicional = '{"'.$carga.'":"'.$valor_carga.'"}';
						
						$sql = "UPDATE tbl_produto SET valores_adicionais = '$valor_adicional' WHERE produto = {$produto}";
						$resU = pg_query($con,$sql);
					}

				}
			}
			
		}

		if(count($msg_erro["msg"]) == 0){
			header("Location: cadastro_familia_produto_valor_adicional.php?msg=gravou");
		}
		
	}

}

$layout_menu = "cadastro";
$title = "CADASTRO DE VALORES ADICIONAIS POR FAMÍLIA";

include "cabecalho_new.php";

$plugins = array(
	"price_format",
	"alphanumeric"
);

include("plugin_loader.php");

?>

<style type="text/css">
	#modelo_valores{
		display: none;
	}
</style>

<script type="text/javascript">
	$(document).ready(function(){
		
		/**
		 * Evento que adiciona uma nova linha de capacidade e valor
		 */
		$("button[name=adicionar_linha]").click(function() {
			
			var nova_linha = $("#modelo_valores").clone();

			$("#valores").append($(nova_linha).html());
			
		});
	});

	

</script>
<?php
	if($_GET['msg'] == 'gravou'){
		$msg = "Gravado com sucesso";
	}else if($_GET['msg'] == 'excluiu'){
		$msg = "Excluído com sucesso";
	}else{
		$msg = "";
	}

if (strlen($msg) > 0) {
?>
    <div class="alert alert-success">
		<h4><?=$msg?></h4>
    </div>
<?php
}
?>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>
<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form method="post" class='form-search form-inline tc_formulario' >
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<br/>	

	<div class='row-fluid'>
		<div class='span2'></div>

		<div class='span4'>
            <div class='control-group <?=(in_array("famila", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='famila'>Família</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <h5 class='asteristico'>*</h5>
                        <select name="familia" id="familia">
                                <?php
                                $sql = "SELECT familia,descricao
                                                FROM tbl_familia
                                                WHERE fabrica = {$login_fabrica}
										ORDER BY descricao";
                                $res = pg_query($con,$sql);
                                foreach (pg_fetch_all($res) as $key) {
                                        $selected_familia = ( $familia == $key["famila"] ) ? "SELECTED" : '' ;

                                ?>
                                        <option value="<?php echo $key['familia']?>" <?php echo $selected_familia ?> >

                                                <?php echo $key['descricao']?>

                                        </option>
                                <?php
                                }
                                ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
		
		<div class='span2'></div>
	</div>

	<div id="valores">
		<div class='row-fluid'>
			<div class='span2'></div>

			<div class='span4'>
				<label class='control-label' for='descricao_posto'>Capacidade</label>				
			</div>

			<div class='span4'>
				<label class='control-label' for='descricao_posto'>Valor</label>				
			</div>

			<div class='span2'></div>
		</div>

		<?php
			for($x = 0; $x < $qtde_linhas; $x++){
		?>
				<div class='row-fluid'>
					<div class='span2'></div>

					<div class='span4'>
						<div class='control-group <?=(in_array("valor", $msg_erro["campos"])) ? "error" : ""?>'>
							<div class='controls controls-row'>
								<div class='span7 input-append'>
									<h5 class='asteristico'>*</h5>
									<input type="text" name="capacidade[]" class='span12' value="" >
								</div>
							</div>
						</div>
					</div>

					<div class='span4'>
						<div class='control-group <?=(in_array("valor", $msg_erro["campos"])) ? "error" : ""?>'>
							<div class='controls controls-row'>
								<div class='span7 input-append'>
									<h5 class='asteristico'>*</h5>
									<input type="text" name="valor[]" price="true" class='span12' value="" >
								</div>
							</div>
						</div>
					</div>

					<div class='span2'></div>
				</div>
		<?php
			}
		?>
	</div>	

		<button type="button" name="adicionar_linha" class="btn btn-primary" >Adicionar nova linha</button>
	<br /><br />

	<div class='row-fluid'>
		<div class='span2'></div>

		<div class='span4'>
			<div class='control-group <?=(in_array("valor", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='famila'>Capacidade acima de</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<h5 class='asteristico'>*</h5>
						<input type="text" name="capacidade_acima" class='span12' value="" >
					</div>
				</div>
			</div>
		</div>

		<div class='span4'>
			<div class='control-group <?=(in_array("valor", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='famila'>Valor</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<h5 class='asteristico'>*</h5>
						<input type="text" name="valor_acima" price="true" class='span12' value="" >
					</div>
				</div>
			</div>
		</div>

		<div class='span2'></div>
	</div>
	<p><br/>
		<button class='btn btn-success' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
	</p><br/>
</form>

<div id="modelo_valores">
	<div class='row-fluid'>
			<div class='span2'></div>

			<div class='span4'>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<input type="text" name="capacidade[]" class='span12' value="" >
					</div>
				</div>
			</div>

			<div class='span4'>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<input type="text" name="valor[]" price="true" class='span12' value="" >
					</div>
				</div>
			</div>

			<div class='span2'></div>
		</div>
</div>
<?php
include "rodape.php";
?>