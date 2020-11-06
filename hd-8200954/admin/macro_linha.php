<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

require_once __DIR__.'/../classes/api/Client.php';
use api\Client;

/**
* Ajax
*/
if(isset($_POST['macro_linha']) && isset($_POST['linha']) && isset($_POST['acao'])){
	header('Content-Type: application/json ');
	if ($login_fabrica !== 117)
		$client = Client::makeTelecontrolClient('posvenda','macrolinhafabrica');

	$acao = strtolower($_POST['acao']);
	if(!in_array($acao,array('post','delete')))
		die(json_encode(array('error'=>true,'message'=>'fuu')));
	try{
		switch ($acao) {
			case 'post':
				if ($login_fabrica == 117) {
					$sql = "INSERT INTO tbl_macro_linha_fabrica(fabrica, macro_linha, linha) VALUES({$login_fabrica}, ".(int)$_POST['macro_linha'].",".(int)$_POST['linha'].")";
					pg_query($con,$sql);
					if (strlen(pg_last_error()) > 0)
						throw new Exception(json_encode("Ocorreu um erro ao tentar vincular esta Macro-família"));
				}else{
					$response = $client->post(array(),array('linha'=>(int)$_POST['linha'],'macro_linha'=>(int)$_POST['macro_linha'],'fabrica'=>(int)$login_fabrica));
				}
			break;
			case 'delete':
				if ($login_fabrica == 117) {
					$sql = "DELETE FROM tbl_macro_linha_fabrica WHERE fabrica = {$login_fabrica} AND macro_linha = ".(int)$_POST['macro_linha']." AND linha = ".(int)$_POST['linha'];
					pg_query($con, $sql);
					if (strlen(pg_last_error()) > 0)
						throw new Exception(json_encode("Ocorreu um erro ao tentar desvincular esta Macro-família"));
				}else{
					$response = $client->delete(array('linha'=>(int)$_POST['linha'],'macro_linha'=>(int)$_POST['macro_linha']));
				}
			break;
		}
		die(json_encode($response));		
	}
	catch(Exception $ex){
		die(json_encode(array('error'=>true,'message'=>$ex->getMessage())));
	}
}

$exceptions = array();

if ($login_fabrica !== 117)
	$client = Client::makeTelecontrolClient('institucional','MacroLinha');


if($_POST['macro_linha'] && isFabrica(10)){
	try{
		$macrolinha = $_POST['macro_linha'];
		$method = $macrolinha['macro_linha']?'put':'post';
		$response = $client->$method(array('macro_linha'=>$macrolinha['macro_linha']),$macrolinha);
		header('Location: '.$_SERVER['PHP_SELF'].'?success='.$method.'_macro_linha#success-message');
		die();	
	}
	catch(Exception $ex){
		$exceptions[] = $ex;
	}
}

if(isset($_GET['success'])){
	switch ($_GET['success']) {
		case 'put_macro_linha':
			$success = 'Macro linha alterada com sucesso!';
			break;
		case 'post_macro_linha':
			$success = 'Macro linha cadastrada com sucesso!';
			break;
		default:
			$success = array();
			break;
	}
}


$linhas = array();
if ($login_fabrica == 117) {
	$sql_linha = "SELECT DISTINCT
	                  tbl_linha.linha,
	                  tbl_linha.nome,
	                  tbl_macro_linha_fabrica.macro_linha
	                FROM tbl_linha
					JOIN tbl_macro_linha_fabrica ON tbl_linha.linha = tbl_macro_linha_fabrica.linha
	                JOIN tbl_macro_linha ON tbl_macro_linha_fabrica.macro_linha = tbl_macro_linha.macro_linha	                WHERE tbl_linha.fabrica = {$login_fabrica} AND tbl_linha.ativo IS TRUE
	                ORDER BY tbl_linha.nome ";
	$res = pg_query($con, $sql_linha);
	$linhas = pg_fetch_all($res);
}else{
	$linhaResource = Client::makeTelecontrolClient('posvenda','linha');
	$linhas = $linhaResource->get();
}

$title = 'Linha x Macro - Família';
$layout_menu = 'cadastro';
include 'cabecalho_new.php';


$plugins = array( "multiselect" );
include "plugin_loader.php";

if ($login_fabrica == 117) {
	$sql = "SELECT
	            DISTINCT tbl_macro_linha.macro_linha,
	            tbl_macro_linha.descricao,
	            tbl_macro_linha.ativo
	        FROM tbl_macro_linha
	            JOIN tbl_macro_linha_fabrica ON tbl_macro_linha.macro_linha = tbl_macro_linha_fabrica.macro_linha
	        WHERE  tbl_macro_linha_fabrica.fabrica = {$login_fabrica}
	            AND     tbl_macro_linha.ativo = TRUE
	        ORDER BY tbl_macro_linha.descricao;";
	$res = pg_query ($con,$sql);
	$macrolinhas = pg_fetch_all($res);
}else{
	try{
		$params = isFabrica(10)?array('ativo'=>'%'):array('ativo'=>'t');
		$macrolinhas = $client->get($params);
	}
	catch(Exception $ex){
		$exceptions[] = $ex;
	}
}

$macrolinha	= null;
if($_GET['macro_linha']){
	try{
		$macrolinha = $client->get(array('macro_linha'=>$_GET['macro_linha'],'ativo'=>'%'));
	}
	catch(Exception $ex){
		$exceptions[] = $ex;
	}
}
else{
	$macrolinha = array(
		'descricao' => '',
		'ativo' => 't'
	);
	$macrolinha = array_merge(
		$macrolinha,
		isset($_REQUEST['macro_linha'])? $_REQUEST['macro_linha']:array()
	);
}

?>

<script type="text/javascript">
	$(function(){

		var sortSelect = function(select){
			var selecteds = $(select).find('option[selected]').clone();
			var nselecteds = $(select).find('option:not([selected])').clone();
			$(select).html('');
			$(select).append(selecteds);
			$(select).append(nselecteds);
		};
		$(".container .alert-success").first().each(function(){
			var container = $(this).parent(".container");
			window.setTimeout(function(){
				$(container).fadeOut();
			},8000);
		});
		$("select[multiple=multiple]").each(function(){
			sortSelect($(this)[0]);
		});
		$("select[multiple=multiple]").on('change',function(evt,ui){
			console.debug($(this),evt,ui);
		});
		$("select[multiple=multiple]").multiselect({header:false});
		$("select[multiple=multiple]").on('multiselectclick',function(evt,ui){
			var select = $(this)[0];
			var option = $(this).find("option[value='"+ui.value+"']");
			var checkbox = $(this).multiselect('widget').find("input[type=checkbox][value='"+ui.value+"']");
			var rollback = function(){
				$(select).multiselect('destroy');
				if(ui.checked)
					option.removeAttr('selected');
				else
					option.attr('selected','selected');
				$(select).multiselect();
			};
			var commit = function(){
				if(ui.checked)
					option.attr('selected','selected');
				else
					option.removeAttr('selected');
				sortSelect(select);
				$(select).multiselect('refresh');
			};
			var form = $(this).parents('form')[0];
			var macrolinha = $(form).attr('macro-linha');
			var linha = ui.value;
			var action = ui.checked;
			window.loading(true);
			$.ajax({
				url : form.action,
				type : form.method,
				method: form.method,
				data : {
					macro_linha : macrolinha,
					linha : linha,
					acao : (action?'POST':'DELETE')
				},
				success : function(data){
					if(data &&'error' in data && data.error){
						rollback();
						$(select).parents('tr').addClass('error');
						window.setTimeout(function(){
							$(select).parents('tr').removeClass('error');
						},1000);
						alert(data.message);
					}
					else{
						commit();
						$(select).parents('tr').addClass('success');
						window.setTimeout(function(){
							$(select).parents('tr').removeClass('success');
						},1000);
					}
				},
				error : function(){
					rollback();
					$(select).parents('tr').addClass('error');
						window.setTimeout(function(){
							$(select).parents('tr').removeClass('error');
					},1000);	
					alert('Verifique sua conexão com a Internet');
				},
				complete : function(){
					window.loading(false);
				}
			});
		});
		$(".btn[data-loading-text]").on('click',function(){
			$(this).button('loading');
		});
	});
</script>

<?php if(!empty($exceptions)): ?>
	<div class="container" >
		<div class="alert alert-error">
			<?php foreach($exceptions as $exception): ?>
				<h4><?php echo $exception->getMessage() ?></h4>
				<div style="display:none"><pre><?php var_dump($exception);?></pre></div>
			<?php endforeach; ?>
		</div>
	</div>
<?php endif; ?>
<?php if(!empty($success)): $success = is_array($success)?$success:array($success); ?>
	<div id="success-message" class="container" >
		<div class="alert alert-success" >
			<?php foreach($success as $successMessage): ?>
				<h4><?php echo $successMessage ?></h4>
			<?php endforeach; ?>
		</div>
	</div>
<?php endif; ?>
<?php if(isFabrica(10)): ?>
	<form id="macro-linha-form" method="POST" class="form-inline tc_formulario" action="<?php echo $_SERVER['PHP_SELF']; ?>" >
		<?php if(isset($macrolinha['macro_linha'])):?>
			<input type="hidden" name="macro_linha[macro_linha]" value="<?php echo $macrolinha['macro_linha']; ?>" />
		<?php endif; ?>
		<div class="titulo_tabela">
			Cadastro de Macro Linha
		</div>
		<br />
		<div class="row-fluid" >
			<div class="span2" ></div>
			<div class="span4" >
				<div class="control-group" >
					<label class="control-label" for="macro_linha_descricao">
						Descrição
					</label>
					<div class="controls controls-row" >
						<div class="span12">
							<input id="macro_linha_descricao" name="macro_linha[descricao]" type="text" value="<?php echo $macrolinha['descricao'] ?>" />
						</div>			
					</div>
				</div>
			</div>
			<div class="span4" >
				<div class="control-group" >
					<label class="control-label" for="macro_linha_descricao" >
						Ativo
					</label>
					<div class="controls controls-row" >
						<div class="span12">
							<select id="macro_linha_descricao" name="macro_linha[ativo]">
								<option <?php echo ((($macrolinha['ativo']==='t')||($macrolinha['ativo']===true))?'selected="selected"':'') ?> value="t">Sim</option>
								<option <?php echo ((($macrolinha['ativo']==='f')||($macrolinha['ativo']===false))?'selected="selected"':'') ?> value="f">Não</option>
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
			<div class="span4 tac">
				<input type="submit" class="btn btn-success" data-loading-text="Gravando..." value="Gravar" />
				<?php if(isset($macrolinha['macro_linha'])):?>
					<a class="btn btn-warning" data-loading-text="Limpando..." href="<?php echo $_SERVER['PHP_SELF']; ?>#macro-linha-form" />
						Limpar
					</a>
				<?php endif; ?>
			</div>
			<div class="span4"></div>
		</div>
	</form>
<?php endif; ?>

<table class="table table-striped table-bordered table-hover table-fixed" style="table-layout:fixed">
	<thead>
		<tr class="titulo_tabela">
			<th colspan="3" >
				Linhas				
			</th>
		</tr>
		<tr class="titulo_coluna" >
			<th>Descrição</th>
			<th>Ativo</th>
			<th>Macro Famílias</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach($macrolinhas as $macrolinha): ?>
			<tr id="macro_linha_<?php echo $macrolinha['macro_linha']; ?>">
				<td>
					<?php if(isFabrica(10)): ?><a href="?macro_linha=<?php echo $macrolinha['macro_linha']; ?>#macro-linha-form"><?php endif; ?>
						<?php echo htmlentities($macrolinha['descricao']); ?>
					<?php if(isFabrica(10)): ?></a><?php endif ?>
				</td>
				<td class="tac" >
					<img src="imagens/<?php echo $macrolinha['ativo']?'status_verde':'status_vermelho'; ?>.png" />
				</td>
				<td class="tac" >
					<form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" style="margin: 0 auto;" macro-linha="<?php echo $macrolinha['macro_linha']; ?>" >
						<select name="macro_linha[linha]" multiple="multiple" >
							<?php foreach($linhas as $linha):
							if ($login_fabrica == 117) {
								$selected = ($macrolinha['macro_linha'] == $linha['macro_linha']) ? true : false;
							}else{
								$selected = in_array($macrolinha['macro_linha'],$linha['macro_linha']);
							}?>
								<option value="<?php echo $linha['linha'] ?>" <?php echo $selected?'selected="selected"':''; ?> >
									<?php echo htmlentities($linha['nome']); ?>
								</option>
							<?php endforeach; ?>					
						</select>
					</form>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<?php

include 'rodape.php';
