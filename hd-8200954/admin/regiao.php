<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

//$admin_privilegios = "financeiro,gerencia,call_center";

include 'autentica_admin.php';
include 'funcoes.php';

if(!isFabrica(117)){
	header('Location: menu_cadastro.php');
	die();
}

$exceptions = array();
$success = array();
$errorFields = array();

if(isset($_POST['regiao']) && $_POST['action'] != 'delete'){
	try{
		$id = saveRegiao($_POST['regiao']);
		header('Location: '.$_SERVER['PHP_SELF'].'?success=save#regiao_'.$id);
		die();
	}
	catch(Exception $ex){
		$exceptions[] = $ex;
	}
}
if(isset($_POST['regiao']) && $_POST['action'] == 'delete'){
	try{
		deleteRegiao($_POST['regiao']);
		header('Location: '.$_SERVER['PHP_SELF'].'?success=delete');
		die();
	}
	catch(Exception $ex){
		$exceptions[] = $ex;	
	}
}

$layout_menu = 'cadastro';
$title       = 'Cadastro de Regiões';
include 'cabecalho_new.php';


$regiaoDefault = array(
	'regiao' => null,
	'descricao' => '',
	'ativo' => true,
	'estados_regiao' => array()
);
$regioes = array();
try{
	$regioes = listRegiao();	
}
catch(Exception $ex){
	$exceptions[] = $ex;
}

$regiao = array();
if($_GET['regiao'] && is_numeric($_GET['regiao'])){
	try{
		$regiao = loadRegiao((int)$_GET['regiao']);	
	}
	catch(Exception $ex){
		$exceptions[] = $ex;
		$regiao = $regiaoDefault;
	}
}
else{
	$regiao = array_merge($regiaoDefault,$_POST['regiao']);
}

if($_GET['success']){
	switch ($_GET['success']) {
		case 'save':
			$success[] = 'Região gravada com sucesso!';
			break;
		case 'delete':
			$success[] = 'Região apagada com sucesso!';
			break;
	}
}

$plugins = array( "multiselect" );

include "plugin_loader.php";

?>
<script type="text/javascript">
	$(function(){
		$("select[multiple=multiple]").multiselect({
			header : false,
			click : function(event,ui){
				var select = $(this);
				window.setTimeout(function(){
					var estados = select.val();
					$('#estados-output').val(estados.join(', '));
				},100);
			}
		});
		$("form button[data-loading-text]").click(function(){
			$(this).button('loading');
		});
		$("#delete-regiao").click(function(){
			var input = $('<input name="action" value="delete" type="hidden" />')
			$(this).parents('form').append(input);
		});
		$(".container .alert").each(function(){
			var alert = $(this);
			window.setTimeout(function(){
				alert.fadeOut();
			},8000);
		});
		$("a:not([href^='#'])").click(function(){
			window.loading('show');
		});
	});
</script>

<?php if(!empty($exceptions)): ?>
<div class="container">
	<div class="alert alert-error">
		<?php foreach($exceptions as $exception): if($exception instanceof CheckException) $errorFields[] = $exception->field ; ?>
			<h4>
				<?php echo $exception->getMessage(); ?>
			</h4>
			<pre style="display:none">
				<?php var_dump($exception) ?>
			</pre>
		<?php endforeach;?>
	</div>
</div>
<?php endif;?>


<?php if(!empty($success)): ?>
<div class="container">
	<div class="alert alert-success">
		<?php foreach($success as $successMessage): ?>
			<h4>
				<?php echo $successMessage; ?>
			</h4>
		<?php endforeach;?>
	</div>
</div>
<?php endif;?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form class="form-search form-inline tc_formulario" method="POST" action="<?php echo $_SERVER['PHP_SELF'];?>" >
	<?php if(isset($regiao['regiao'])): ?>
	<input type="hidden" name="regiao[regiao]" value="<?php echo $regiao['regiao']; ?>" />
	<?php endif; ?>
	<div class="titulo_tabela">
		Cadastro de Região
	</div>
	<br />
	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span6">
			<div class="control-group <?php echo in_array('descricao',$errorFields)?'error':''; ?>">
				<label class="control-label" for="regiao_descricao">
					Descrição					
				</label>
				<div class="controls control-rows">
					<h5 class="asteristico">*</h5>
					<input
						id="regiao_descricao"
						name="regiao[descricao]"
						value="<?php echo $regiao['descricao'] ?>"
						class="span10"
						type="text"
						maxlength="60" />
				</div>
			</div>
		</div>
		<div class="span2">
			<div class="control-group  <?php echo in_array('ativo',$errorFields)?'error':''; ?>">
				<label class="control-label" for="regiao_ativo">
					Ativa
				</label>
				<div class="controls control-rows">
					<h5 class="asteristico">*</h5>
					<select id="regiao_ativo" name="regiao[ativo]" class="span12">
						<option value="t" <?php echo ($regiao['ativo']==='t'||$regiao['ativo']===true)?'selected="selected"':'';?> >
							Sim
						</option>
						<option value="f" <?php echo ($regiao['ativo']==='f'||$regiao['ativo']===false)?'selected="selected"':'';?>>
							Não
						</option>
					</select>
				</div>
			</div>
		</div>
		<div class="span2"></div>
	</div>
	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span4">
			<div class="control-group <?php echo in_array('estados_regiao',$errorFields)?'error':''; ?>">
				<label class="control-label" for="regiao_estados" >
					Estados
				</label>
				<div class="controls control-rows">
					<h5 class="asteristico">*</h5>
					<select id="regiao_estados" name="regiao[estados_regiao][]" multiple="multiple" output-element="estados-output">
					<?php foreach($estadosBrasil as $uf => $name): ?>
						<option value="<?php echo $uf; ?>" <?php echo in_array($uf,$regiao['estados_regiao'])?'selected="selected"':''; ?> >
							<?php echo htmlentities($name); ?>
						</option>
					<?php endforeach; ?>
					</select>			
				</div>
			</div>
		</div>
		<div class="span4">
			<label>
				
			</label>
			<div class="control-group">
				<div class="controls control-rows">
					<div class="span12">
						<input id="estados-output" class="span12" value="<?php echo implode(', ',$regiao['estados_regiao']); ?>" type="text" readonly="readonly" />
					</div>
				</div>	
			</div>
		</div>
		<div class="span2"></div>
	</div>
	<br />
	<br />
	<div class="row-fluid">
		<div class="span4"></div>
		<div class="span4">
			<div class="control-group">
				<div class="controls controls-row tac">
					<button class="btn" data-loading-text="Gravando...">
						Gravar
					</button>
					<?php if(isset($regiao['regiao'])): ?>	
					<button id="delete-regiao" class="btn btn-danger" data-loading-text="Apagando...">
						Apagar
					</button>	
					<?php endif; ?>
				</div>
			</div>
		</div>
		<div class="span4"></div>
	</div>
	<br />
</form>


<table class='table table-striped table-bordered table-hover table-large' >
	<thead>
		<tr class='titulo_tabela' >
			<th colspan="3" >
				Regiões
			</th>
		</tr>
		<tr class='titulo_coluna' >
			<th>
				Descrição
			</th>
			<th>
				Estados
			</th>
			<th>
				Ativa
			</th>
		</tr>
	</thead>
	<tbody>
	<?php foreach ($regioes as $regiao):?>
		<tr id="regiao_<?php echo $regiao['regiao']; ?>">
			<td>
				<a href="<?php echo $_SERVER['PHP_SELF']?>?regiao=<?php echo $regiao['regiao']?>" >
					<?php echo htmlentities($regiao['descricao']); ?>
				</a>
			</td>
			<td class="tac" >
				<?php echo implode(', ',$regiao['estados_regiao']); ?>
			</td>
			<td class="tac" >
				<?php if($regiao['ativo']): ?>
					<img src="imagens/status_verde.png" alt="Região Ativa" />
				<?php else: ?>
					<img src="imagens/status_vermelho.png" alt="Região Inativa" />
				<?php endif; ?>
			</td>
		</tr>
	<?php endforeach;?>
	</tbody>
</table>

<?php

include 'rodape.php';

class CheckException extends Exception{

	public $field;

	public function __construct($message,$field){
		parent::__construct($message);
		$this->field = $field;
	}

}

function saveRegiao($regiao){
	checkRegiao($regiao);
	if(isset($regiao['regiao'])){
		updateRegiao($regiao);
		return $regiao['regiao'];
	}
	return insertRegiao($regiao);
}

function deleteRegiao($regiao){
	global $con,$login_fabrica;
	$sql = 'DELETE FROM tbl_regiao WHERE regiao = $1 AND fabrica =  $2;';
	$params = array(
		(int)$regiao['regiao'],
		(int)$login_fabrica
	);
	$result = pg_query_params($con,$sql,$params);
	if($result === false)
		throw new Exception(pg_last_error());
	$rows = pg_affected_rows($result);
	if($rows === 0)
		throw new \Exception('Não foi possivel apagar região');
}

function updateRegiao($regiao){
	global $con,$login_fabrica;
	$sql = 'UPDATE tbl_regiao SET descricao = $1,estados_regiao = $2, ativo = $3 WHERE regiao = $4 AND fabrica = $5;';
	$params = array(
		$regiao['descricao'],
		implode(', ',$regiao['estados_regiao']),
		$regiao['ativo'],
		(int)$regiao['regiao'],
		(int)$login_fabrica
	);
	$result = pg_query_params($con,$sql,$params);
	if($result === false)
		throw new Exception(pg_last_error($con));
	$rows = pg_affected_rows($result);
	if($rows === 0)
		throw new Exception('Não foi possivel atualizar Região');
}

function insertRegiao($regiao){
	global $con,$login_fabrica;
	$sql = 'INSERT INTO tbl_regiao(fabrica,descricao,estados_regiao,ativo) VALUES ($1,$2,$3,$4) RETURNING regiao;';
	$params = array(
		(int)$login_fabrica,
		$regiao['descricao'],
		implode(', ',$regiao['estados_regiao']),
		$regiao['ativo']
	);
	$result = pg_query_params($con,$sql,$params);	
	if($result === false)
		throw new Exception('Não foi possivel inserir Região ('.pg_last_error($con).')');
	$result = pg_fetch_assoc($result);
	return $result['regiao'];
}

function listRegiao(){
	global $con,$login_fabrica;	
	$sql = 'SELECT * FROM tbl_regiao WHERE fabrica = $1 ORDER BY ativo DESC,descricao;';
	$params = array(
		(int)$login_fabrica
	);
	$result = pg_query_params($con,$sql,$params);
	if($result === false)
		throw new Exception('Não foi possivel carregar as Regiões ('.pg_last_error($con).')');
	$result = pg_fetch_all($result);
	foreach ($result as &$row) {
		prepareRegiaoFromDB($row);
	}
	return $result;
}

function loadRegiao($regiao){
	global $con,$login_fabrica;
	$sql = 'SELECT * FROM tbl_regiao WHERE fabrica = $1 AND regiao = $2 LIMIT 1;';
	$params = array(
		(int)$login_fabrica,
		(int)$regiao,
	);
	$result = pg_query_params($con,$sql,$params);
	if($result === false)
		throw new Exception('Não foi possivel encontrar está região! ('.pg_last_error($con).')');
	$result = pg_fetch_all($result);
	if(empty($result))
		throw new Exception('Região inexistenete!');
	return prepareRegiaoFromDB($result[0]);
}

function checkRegiao($regiao){
	if(preg_match('@^[ \n\t]*$@',$regiao['descricao'])){
		throw new CheckException('Descrição da Região não pode ser Vazia','descricao');
	}
	if(strlen($regiao['descricao']) < 3){
		throw new CheckException('Descrição da Região muito curta, utilize mais que 3 caracteres.','descricao');
	}
	if(empty($regiao['estados_regiao'])){
		throw new CheckException('Selecione pelo menos um estado para a região.','estados_regiao');
	}
	if(!in_array($regiao['ativo'],array('t','f'))){
		throw new CheckException('Valor para região ativa inválido!','ativo');
	}
	return true;
}

function prepareRegiaoFromDB(&$regiao){
	$regiao['estados_regiao'] = array_map('trim',explode(',',$regiao['estados_regiao']));
	$regiao['ativo'] = ($regiao['ativo'] === 't' || $regiao['ativo'] === true);
	return $regiao;
}

