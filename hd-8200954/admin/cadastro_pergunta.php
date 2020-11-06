<?php
/**
 * @author Brayan L. Rastelli
 * @description Cadastrar tipo de pergunta para questionario - HD 408341
 */
 
	unset($descricao,$ativo,$tipo_resposta,$pergunta);
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	$layout_menu = "cadastro";
	$admin_privilegios="cadastro";
	include 'autentica_admin.php';
	
	/* Request para excluir */
	if ( isset( $_GET['excluir'] ) ) {
	
		$pergunta = (int) $_GET['excluir'];
		if(!empty($pergunta)) {
		
			$sql = "DELETE FROM tbl_pergunta
					WHERE pergunta = $pergunta
					AND fabrica = $login_fabrica";
			$res = @pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);
			if(empty($msg_erro)) {
				header('Location: ?msg=Gravado com Sucesso');
			}
			else if ( substr($msg_erro, 'foreign key') ) {
				$msg_erro = "Pergunta já foi utilizada nas pesquisas, portanto não pode ser excluída, apenas inativada.";
			}
	
		}
	
	}
	/* Fim exclusao */
	
	/* Request para gravar */
	
	if( isset($_POST['enviar']) && $_POST['enviar'] == 'Gravar' ) {
	
		$descricao 		= trim( $_POST['descricao'] );
		$ativo			= trim( $_POST['ativo'] );
		$tipo_resposta	= trim( $_POST['tipo_resposta'] );
		$pergunta 		= trim( $_POST['pergunta'] );
		
		if( empty($descricao) )
			$msg_erro = "Digite a descrição.";
		if( empty($tipo_resposta) ) {
			$msg_erro = "Selecione o tipo de resposta.";
		}
		else if( empty($ativo) )
			$msg_erro = "Selecione a situação, ativo ou inativo.";
		
		if(empty($msg_erro)) {
		
			pg_query($con,"BEGIN TRANSACTION");
		
			if(!empty($pergunta)) { // UPDATE
			
				$sql = "SELECT pergunta FROM tbl_pergunta WHERE pergunta = $pergunta";
				$res = pg_query($con,$sql);
				if( pg_num_rows($res) ) {
				
					$sql = "UPDATE tbl_pergunta
							SET descricao = '$descricao',
							ativo = '$ativo',
							tipo_resposta = $tipo_resposta
							WHERE pergunta = $pergunta";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);
					if(empty($msg_erro))
						$msg = "Gravado com Sucesso";
				
				}
				else
					$msg_erro = "Pergunta não encontrada";
				
			}		
			else { //INSERT
			
				$sql = "INSERT INTO tbl_pergunta (descricao,ativo,tipo_resposta,fabrica) 
						VALUES ('$descricao','$ativo',$tipo_resposta,$login_fabrica)";
				$res = pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);
				if(empty($msg_erro))
					$msg = "Gravado com Sucesso";
			
			}
			
			if( empty($msg_erro) ) {
				pg_query($con,"COMMIT");
				unset($descricao,$ativo,$tipo_resposta,$pergunta);
			}
			else
				pg_query($con,"ROLLBACK");
		
		}
	
	}
	
	/* Fim Request */
	
	/* Request GET */
	if ( isset($_GET['pergunta']) ) {
	
		$pergunta = (int) $_GET['pergunta'];
		if(!empty($pergunta)) {
			$sql = "SELECT ativo, descricao, tipo_resposta
					FROM tbl_pergunta
					WHERE pergunta = $pergunta";
			$res = pg_query($con,$sql);
			if( pg_num_rows($res) ) {
				$descricao 		= pg_result($res,0,'descricao');
				$ativo			= pg_result($res,0,'ativo');
				$tipo_resposta	= pg_result($res,0,'tipo_resposta');
			}
		}
	}
	/* Fim request GET */

	/* Setar o select para GET e POST */
	
	if(!empty($ativo)) {		
		$x_ativo 	= ($ativo == 't') ? 'selected' : FALSE;
		$inativo 	= ($ativo == 'f') ? 'selected' : FALSE;		
	}
	
	/* Mensagem por GET */
	if (isset($_GET['msg'])  && !empty($_GET['msg']) ) { 
		$msg = $_GET['msg'];
	}
	
	$title="CADASTRO DE PERGUNTA";
	include 'cabecalho.php';
	
?>

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

<?php if(isset($msg_erro) && !empty($msg_erro)) { ?>
	<div class="msg_erro" style="width:700px;margin:auto; text-align:center;"><?=$msg_erro?> </div>
<?php } ?>
<?php if(isset($msg)) { ?>
	<div class="sucesso" style="width:700px;margin:auto; text-align:center;"><?=$msg?> </div>
<?php } ?>

<div class="formulario" style="width:700px; margin:auto;">
	
	<div class="titulo_tabela">Cadastro</div>
	<form action="<?=$PHP_SELF?>" method="POST">
	<div style="padding:10px;">
		<table style="width:600px;margin:auto; text-align:left; border:none;">
			<tr>	
				<td>
					<label for="relacao">Tipo de Resposta</label><br />
					<select name="tipo_resposta" id="tipo_resposta" class="frm" style="width:250px;">
						<option value=""></option>
						<?php
							$cond_ativo = (!empty($pergunta)) ? '' : ' AND ativo';
							$sql = "SELECT tipo_resposta,descricao
									FROM tbl_tipo_resposta
									WHERE fabrica = $login_fabrica $cond_ativo";
							$res = pg_query($con,$sql);
							for($i = 0; $i < pg_num_rows($res); $i++) {
								$tipo = pg_result($res,$i,'tipo_resposta');
								$descricao_combo = pg_result($res,$i,'descricao');
								$selected = ($tipo_resposta == $tipo) ? 'selected' : '';
						?>

								<option value="<?=$tipo?>" <?=$selected?>><?=$descricao_combo?></option>

						<?php } ?>
					</select>
				</td>
				<td width="320">
					<label for="descricao">Descrição</label><br />
					<input type="hidden" name="pergunta" value="<?=$pergunta;?>" />
					<input type="text" value="<?=$descricao;?>" size="45" id="descricao" name="descricao" class="frm" />
				</td>
				<td>
					<label for="ativo">Ativo</label><br />
					<select name="ativo" id="ativo" class="frm">
						<option value=""></option>
						<option value="t" <? echo $x_ativo; ?>>Ativo</option>
						<option value="f" <? echo $inativo; ?>>Inativo</option>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="3" align="center">
					<input type="submit" name="enviar" value="Gravar"  />
					<?php if(!empty($pergunta)) { ?>
						&nbsp;&nbsp;<input type="button" name="limpar" value="Limpar" />
						&nbsp;&nbsp;<input type="button" name="excluir" id="<?=$pergunta?>" value="Excluir" />
					<? } ?>
				</td>
			</tr>
		</table>
	</div>
	</form>
</div>

<?php
	$sql = "SELECT CASE WHEN tbl_pergunta.ativo THEN 'Ativo' ELSE 'Inativo' END as ativo,
				   tbl_pergunta.descricao, tbl_tipo_resposta.descricao as tipo_resposta, pergunta
			FROM tbl_pergunta
			JOIN tbl_tipo_resposta ON tbl_pergunta.tipo_resposta = tbl_tipo_resposta.tipo_resposta
			WHERE tbl_pergunta.fabrica = $login_fabrica
			ORDER BY tbl_pergunta.descricao";
	$res = pg_query($con,$sql);
	if( pg_num_rows($res) ) {
?>
		<br />
		<table class="tabela" width="700" align="center" cellspacing="1" id="relatorio">
			<tr class="titulo_coluna">
				<th nowrap>Descrição</th>
				<th>Tipo de Resposta</th>
				<th>Ativo</th>
			</tr>
			<tbody>
				<?php 
					for($i = 0 ; $i < pg_num_rows( $res ) ; $i++ ) { 
						$x_ativo 		= pg_result($res,$i,'ativo');
						$x_descricao 	= pg_result($res,$i,'descricao');
						$tipo_resposta  = pg_result($res,$i,'tipo_resposta');
						$pergunta  = pg_result($res,$i,'pergunta');
						$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
				?>
					<tr bgcolor="<?=$cor?>" id="<?=$pergunta?>">
						<td nowrap>&nbsp;<?=$x_descricao?></td>
						<td>&nbsp;<?=$tipo_resposta?></td>
						<td>&nbsp;<?=$x_ativo?></td>
					</tr>
				<?php } ?>	
			</tbody>
		</table>
		
<?  } ?>
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript">
	$().ready(function(){
	
		$("#relatorio > tbody > tr").click(function(){
		
			tipo = $(this).attr('id');
			window.location = '?pergunta=' + tipo;
		
		});
		<?php if(!empty($tipo_resposta)) { ?>
			$("input[name=excluir]").click(function(e) {
			
				if (confirm ("Deseja mesmo excluir essa pergunta?") ) {
					window.location = '?excluir=' + $(this).attr('id');
				}
				
				e.preventDefault();
			
			});
			
			$("input[name=limpar]").click(function(e) {
			
				e.preventDefault();
				window.location = 'cadastro_pergunta.php';
			
			});
		<?php } ?>
	
	});
</script>

<?php include 'rodape.php'; ?>
