<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

if(!isFabrica(117)){
	header('Location: menu_cadastro.php');
	die();
}

require_once __DIR__.'/../classes/api/Client.php';
use api\Client;

$exceptions = array();

if(!empty($_POST) && isset($_POST['linha']) && is_array($_POST['linha'])){
	$client = Client::makeTelecontrolClient('posvenda','linha');
	try{
		$linha = $client->put(array('linha'=>$_POST['linha']['linha']),array('macro_linha'=>$_POST['linha']['macro_linha']));
		header('Location: '.$_SERVER['PHP_SELF'].'?linha[linha]='.$linha['linha']);
		die();
	}
	catch(Exception $ex){
		$exceptions[] = $ex;
	}
}

$linhaResource = Client::makeTelecontrolClient('posvenda','linha');

$linhasDeProdutos = $linhaResource->get();

$macroLinhaResource = Client::makeTelecontrolClient('posvenda','macrolinha');
$macroLinhas = $macroLinhaResource->get();

$linha = null;

if(isset($_GET['linha']['linha'])){
	try{
		$linha = $linhaResource->get(array('linha'=>$_GET['linha']['linha']));	
	}
	catch(Exception $ex){
		$exceptions[] = $ex;
	}
}
else{
	$linha = array(
		'macro_linha'=>array()
	);
}

$title = 'Linhas & Macro Linhas';
include 'cabecalho_new.php';

$plugins = array( "multiselect" );
include "plugin_loader.php";

?>

<script type="text/javascript" >
$(function() {
    $("select[multiple=multiple]").multiselect({
       selectedText: "selecionados # de #"
    });
    $("#produto_linha").change(function(){
    	var linha = $(this).val();
    	if(!linha)
    		return;
    	window.loading('show');
    	window.location = window.location.pathname + '?linha[linha]=' + linha;
    });
    $('input[type=submit]').click(function(){
    	$(this).button('loading');
    });
});
</script>
<?php if(!empty($exceptions)): ?>
<div class="alert alert-error">
<?php foreach($exceptions as $exception): ?>
	<h4><?php echo $exception->getMessage() ?></h4>
	<div style="display:none"><pre><?php var_dump($exception);?></pre></div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<form method="POST" class="form-inline tc_formulario" action="<?php echo $_SERVER['PHP_SELF']; ?>" >
	<div class="titulo_tabela">
		Relacionamento de Linha e Macro Linha
	</div>
	<br />
	<div class="row-fluid" >
		<div class="span2" ></div>
		<div class="span4" >
			<div class="control-group" >
				<label class="control-label" for="produto_linha">
					Linha de Produtos
				</label>
				<div class="controls controls-row" >
					<div class="span12">
						<select id="produto_linha" name="linha[linha]" >
							<option>
							</option>
							<?php foreach($linhasDeProdutos as $linhaDeProduto): $selected = $linha['linha'] == $linhaDeProduto['linha']; ?>
							<option <?php echo $selected?'selected="selected"':'';?> value="<?php echo $linhaDeProduto['linha']; ?>">
								<?php echo utf8_decode($linhaDeProduto['nome']);?>
							</option>
							<?php endforeach; ?>
						</select>
					</div>			
				</div>
			</div>
		</div>
		<div class="span4" >
			<div class="control-group" >
				<label class="control-label" for="linha_macro_linha">
					Macro Linhas
				</label>
				<div class="controls controls-row" >
					<div class="span12">
						<select id="linha_macro_linha" name="linha[macro_linha][]" multiple="multiple" >
							<?php foreach($macroLinhas as $macroLinha ): $selected = in_array($macroLinha['macro_linha'],$linha['macro_linha']); ?>
							<option <?php echo $selected?'selected="selected"':'';?> value="<?php echo $macroLinha['macro_linha']; ?>">
								<?php echo htmlentities(utf8_decode($macroLinha['descricao'])); ?>
							</option>
							<?php endforeach;?>
						</select>
					</div>			
				</div>
			</div>	
		</div>
		<div class="span2" ></div>
	</div>
	<br />
	<br />
	<div class="row-fluid" >
		<div class="span4"></div>
		<div class="span4">
			<div class="control-group">
				<div class="controls controls-row tac">
					<input type="submit" class="btn btn-success" data-loading-text="Gravando..." value="Gravar" />
				</div>
			</div>
		</div>
		<div class="span4"></div>
	</div>
</form>


<?php

include 'rodape.php';
