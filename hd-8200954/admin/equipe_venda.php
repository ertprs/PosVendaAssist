<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios='info_tecnica,gerencia,call_center';
include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = 'cadastro';
$title = 'CADASTRO DE EQUIPE DE VENDA';

$filtros = array();

include 'cabecalho_new.php';

$plugins = array(
	"shadowbox"
);

include 'plugin_loader.php';


if(isset($_GET['equipe_venda'])){
	$equipe_venda = (int)$_GET["equipe_venda"];	
	$sql = "SELECT * from tbl_equipe_venda where equipe_venda = $equipe_venda";
	$res = pg_query($con, $sql);
	if(pg_num_rows($res)>0){
		$codigo = pg_fetch_result($res, 0, 'codigo');
		$descricao = pg_fetch_result($res, 0, 'descricao');
		$ativo = pg_fetch_result($res, 0, 'ativo');
	}
}

if ($_POST["gravar"]) {
	$codigo 		= $_POST['codigo'];
	$descricao 		= $_POST['descricao']; 
	$ativo 			= ($_POST["ativo"] == 't')? 't' : 'f';
	$equipe_venda 	= (int)$_POST["equipe_venda"];

	if(strlen(trim($codigo)) == 0){
		$msg_erro['msg'][] .= "Informe o código da equipe de venda. ";
		$msg_erro['campos'][] = 'codigo';
	}

	if(strlen(trim($descricao)) == 0){
		$msg_erro['msg'][] .= "Informe a descrição da equipe de venda. ";
		$msg_erro['campos'][] = 'descricao';
	}

	if(count(array_filter($msg_erro))==0){
		if($equipe_venda == 0){
			$sql = "INSERT INTO tbl_equipe_venda (codigo, descricao, ativo) values ('$codigo', '$descricao', '$ativo')";
		}else{
			$sql = "UPDATE tbl_equipe_venda SET codigo = '$codigo', descricao = '$descricao', ativo = '$ativo' WHERE equipe_venda = $equipe_venda ";
		}		
		$res = pg_query($con, $sql);

		$msg_sucesso = "Cadastro realizado com sucesso. ";
		$codigo 		= "";
		$descricao 		= ""; 
		$ativo 			= "";
		$equipe_venda 	= "";
	}	
}

?>

<style>

.obs_alteracao{
	font-size: 8px;
	font-style: italic;
}
.titulo{
	font-size: 14px;
	font-weight: bold;
}

</style>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
	<br />
	<div class="alert alert-error"><h4><?=implode("<br />", $msg_erro["msg"])?></h4></div>
<?php
}

if (!empty($msg_sucesso)) {
?>
	<br />
	<div class="alert alert-success"><h4><?=$msg_sucesso?></h4></div>
<?php
}
?>

<form method="POST" class="form-search form-inline" action="equipe_venda.php" >
	<div class="tc_formulario" >
		<div class="titulo_tabela">Parâmetros de Pesquisa</div>
		<br />

		<div class='row-fluid'>
			<div class="span2"></div>

			<div class="span4">
				<div class='control-group <?=(in_array('codigo', $msg_erro['campos'])) ? "error" : "" ?>''>
					<label class="control-label" for="pesquisa_linha">Código</label>
					<div class="controls controls-row">
						<div class="span12">
							<h5 class='asteristico'>*</h5>
							<input type="text"  maxlength="30" name="codigo" value="<?=$codigo?>">
						</div>
					</div>
				</div>
			</div>

			<div class="span4">
				<div class='control-group <?=(in_array('descricao', $msg_erro['campos'])) ? "error" : "" ?>''>
					<label class="control-label" for="pesquisa_familia">Descrição</label>
					<div class="controls controls-row">
						<div class="span12">
							<h5 class='asteristico'>*</h5>
							<input type="text"  maxlength="50" name="descricao" value="<?=$descricao?>">
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class='row-fluid'>
			<div class="span2"></div>

			<div class="span4">
				<div class='control-group'>
					<label class="control-label" for="pesquisa_linha">Ativo</label>
					<div class="controls controls-row">
						<div class="span12">
							<h5 class='asteristico'>*</h5>
							<input type="checkbox" name="ativo" value="t" <?php if($ativo == 't'){ echo " checked "; }?> >
							
						</div>
					</div>
				</div>
			</div>
		</div>
		<br />
		<button type="submit" name="gravar" class="btn" value="gravar" >Gravar</button>
		<input type="hidden" name="equipe_venda" value="<?=$equipe_venda?>">
		<br />
		<br />
	</div>

	<?php
	$sql = "SELECT * FROM tbl_equipe_venda order by descricao ";
	$res = pg_query($con, $sql);

	$qtdeRegistro = pg_num_rows($res);
	
	if (pg_num_rows($res) > 0) {
	?>	
	
		<br>
		<table class="table table-striped table-bordered table-mascaras" style="margin: 0 auto;" >
			<thead>
				<tr>
					<th class="titulo_coluna" colspan="3">
						<span class='titulo'> Relação de Equipes Cadastradas </span> <br> 
						<span class='obs_alteracao'>Para efetuar alterações, clique na descrição ou código da equipe</span>
					</th>
				</tr>
				<tr class="titulo_coluna" >
					<th>Código</th>
					<th>Descrição</th>
					<th>Ativo</th>
				</tr>
			</thead>
			<tbody>
				<?php 
				for($i=0; $i<pg_num_rows($res); $i++){
					$equipe_venda 	= pg_fetch_result($res, $i, 'equipe_venda');
					$codigo 		= pg_fetch_result($res, $i, 'codigo');
					$descricao 		= pg_fetch_result($res, $i, 'descricao');
					$ativo 			= pg_fetch_result($res, $i, 'ativo');
				?>
				<tr>
					<td class='tac'><a href="equipe_venda.php?equipe_venda=<?=$equipe_venda?>"><?=$codigo?></a></td>
					<td><a href="equipe_venda.php?equipe_venda=<?=$equipe_venda?>"><?=$descricao?></a></td>
					<td class='tac'><img src="imagens/<?=($ativo == 't') ? 'status_verde.png' : 'status_vermelho.png'?>"/> </td>
				</tr>

			<?php } ?>
			</tbody>
		</table>		
	<?php
	}
	?>
</form>

<?php

include 'rodape.php';

