<?php

unset($nome);

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$layout_menu       = 'callcenter';
$admin_privilegios = 'cadastro';

include 'autentica_admin.php';

/* Request para excluir */
if (isset($_GET['excluir'])) {

	$projeto = (int) $_GET['excluir'];

	if (!empty($projeto)) {
	
		$sql = "DELETE FROM tbl_projeto WHERE projeto = $projeto";
		$res = @pg_query($con, $sql);
		$msg_erro = pg_errormessage($con);

		if (empty($msg_erro)) {
			header('Location: ?msg=Excluído com Sucesso');
		} else if (substr($msg_erro, 'foreign key') ) {
			$msg_erro = "Projeto já possuí itens e não pode ser excluído";
		}

	}

}

if (isset($_POST['enviar']) && $_POST['enviar'] == 'Gravar') {

	$nome = trim($_POST['nome']);
	
	if (empty($nome)) $msg_erro = "Digite um nome para o projeto.";
	
	if (empty($msg_erro)) {
	
		pg_query($con, "BEGIN TRANSACTION");
	
		if (!empty($projeto)) { // UPDATE
		
			$sql = "SELECT projeto FROM tbl_projeto WHERE projeto = $projeto";
			$res = pg_query($con,$sql);

			if (pg_num_rows($res)) {
			
				$sql = "UPDATE tbl_projeto SET nome = '$nome' WHERE projeto = $projeto";
				$res = pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);

				if (empty($msg_erro)) $msg = 'Gravado com Sucesso';
			
			} else {
				$msg_erro = 'Projeto não encontrado';
			}
			
		} else { //INSERT
		
			$sql = "INSERT INTO tbl_projeto (nome) VALUES ('$nome')";
			$res = pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);

			if (empty($msg_erro)) $msg = "Gravado com Sucesso";
		
		}
		
		if (empty($msg_erro)) {
			pg_query($con,"COMMIT");
			unset($nome);
		} else {
			pg_query($con,"ROLLBACK");
		}
	
	}

}

if (isset($_GET['projeto'])) {

	$projeto = (int) $_GET['projeto'];

	if (!empty($projeto)) {

		$sql = "SELECT nome FROM tbl_projeto WHERE projeto = $projeto";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res)) {
			$nome = pg_result($res, 0, 'nome');
		}

	}

}

/* Mensagem por GET */
if (isset($_GET['msg']) && !empty($_GET['msg']) ) { 
	$msg = $_GET['msg'];
}

$title = "CADASTRO DE PROJETOS";
include 'cabecalho.php';?>

<style type="text/css">

	.titulo_tabela{
		background-color:#596d9b;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.titulo_coluna{
		background-color:#596d9b;
		font: bold 11px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.msg_erro{
		background-color:#FF0000;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.formulario{
		background-color:#D9E2EF;
		font:11px Arial;
		text-align:left;
	}
	table.tabela tr td{
		font-family: verdana;
		font-size: 11px;
		border-collapse: collapse;
		border:1px solid #596d9b;
	}
	#tabela{display:none;}
	.sucesso{
		background-color:#008000;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	#relatorio tr td{ cursor:pointer; }

</style>

<?php if (isset($msg_erro) && !empty($msg_erro)) { ?>
	<div class="msg_erro" style="width:700px;margin:auto; text-align:center;"><?=$msg_erro?> </div>
<?php } ?>
<?php if (isset($msg)) { ?>
	<div class="sucesso" style="width:700px;margin:auto; text-align:center;"><?=$msg?> </div>
<?php } ?>

<div class="formulario" style="width:700px; margin:auto;">
	
	<div class="titulo_tabela"><?=$title?></div>
	<form action="backlog_projeto_cadastro.php" method="POST">
	<div style="padding:10px;">
		<table style="width:600px;margin:auto; text-align:left; border:none;">
			<tr>	
				<td width="320">
					<label for="nome">Nome do Projeto</label><br />
					<input type="hidden" name="projeto" id="projeto" value="<?=$projeto;?>" />
					<input type="text" value="<?=$nome;?>" size="100" id="nome" name="nome" class="frm" />
				</td>
			</tr>
			<tr>
				<td colspan="3" align="center">
					<input type="submit" name="enviar" value="Gravar"  />
					<?php if(!empty($projeto)) { ?>
						&nbsp;&nbsp;<input type="button" name="limpar" value="Limpar" />
						&nbsp;&nbsp;<input type="button" name="excluir" id="<?=$projeto?>" value="Excluir" />
					<? } ?>
				</td>
			</tr>
		</table>
	</div>
	</form>
</div><?php

$sql = "SELECT projeto, nome FROM tbl_projeto ORDER BY nome";
$res = pg_query($con,$sql);
$tot = pg_num_rows($res);

if ($tot) {?>

	<br />
	<table class="formulario" width="700" align="center" cellspacing="1" id="relatorio">
		<tr class="titulo_coluna">
			<th nowrap>Nome</th>
		</tr>
		<tbody><?php 
			for ($i = 0; $i < $tot; $i++) { 

				$projeto = pg_result($res, $i, 'projeto');
				$nome    = pg_result($res, $i, 'nome');

				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";?>

				<tr bgcolor="<?=$cor?>" id="<?=$projeto?>">
					<td>&nbsp;<?=$nome?></td>
				</tr><?php

			} ?>
		</tbody>
	</table><?php

} ?>

<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript">
	$().ready(function(){
	
		$("#relatorio > tbody > tr").click(function() {
		
			tipo = $(this).attr('id');
			window.location = '?projeto=' + tipo;
		
		});<?php

		if (!empty($projeto)) { ?>

			$("input[name=excluir]").click(function(e) {
			
				if (confirm ("Deseja mesmo excluir este projeto?") ) {
					window.location = '?excluir=' + $(this).attr('id');
				}
				
				e.preventDefault();
			
			});
			
			$("input[name=limpar]").click(function(e) {
			
				e.preventDefault();
				window.location = 'backlog_projeto_cadastro.php';
			
			});<?php

		} ?>
	
	});
</script>

<?php include 'rodape.php'; ?>
