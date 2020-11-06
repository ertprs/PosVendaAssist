<?php
/**
 * @author Brayan L. Rastelli
 * @description Cadastrar tipo de pergunta para questionario callcenter - HD 674943
 */
 
	unset($descricao,$ativo,$tipo_pergunta,$pergunta);
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	$layout_menu = "callcenter";
	$admin_privilegios="cadastro";
	include 'autentica_admin.php';
	
	/* Request para excluir */
	if ( isset( $_GET['excluir'] ) ) {
	
		$pergunta = (int) $_GET['excluir'];
		if(!empty($pergunta)) {
		
			$sql = "DELETE FROM tbl_pergunta
					WHERE pergunta = $pergunta";
			$res = @pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);
			if(empty($msg_erro)) {
				header('Location: ?msg=Gravado com Sucesso');
			}
			else if ( substr($msg_erro, 'foreign key') ) {
				$msg_erro = "Pergunta já foi respondida no callcenter, portanto não pode ser excluída.";
			}
	
		}
	
	}
	/* Fim exclusao */
	
	/* Request para gravar */
	
	if( isset($_POST['enviar']) && $_POST['enviar'] == 'Gravar' ) {
	
		$descricao 		= trim( $_POST['descricao'] );
		$ativo			= trim( $_POST['ativo'] );
		$tipo_pergunta	= trim( $_POST['tipo_pergunta'] );
		$pergunta 		= trim( $_POST['pergunta'] );
		
		if( empty($descricao) )
			$msg_erro = "Digite a descrição.";
		else if( empty($tipo_pergunta) )
			$msg_erro = "Selecione o tipo da pergunta.";
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
							tipo_pergunta = '$tipo_pergunta'
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
			
				$sql = "INSERT INTO tbl_pergunta (descricao,ativo,tipo_pergunta, fabrica) 
						VALUES ('$descricao','$ativo',$tipo_pergunta, $login_fabrica)";
				$res = pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);
				if(empty($msg_erro))
					$msg = "Gravado com Sucesso";
			
			}
			
			if( empty($msg_erro) ) {
				pg_query($con,"COMMIT");
				unset($descricao,$ativo,$tipo_pergunta,$pergunta);
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
			$sql = "SELECT ativo, descricao, tipo_pergunta
					FROM tbl_pergunta
					WHERE pergunta = $pergunta";
			$res = pg_query($con,$sql);
			if( pg_num_rows($res) ) {
				$descricao 		= pg_result($res,0,'descricao');
				$ativo			= pg_result($res,0,'ativo');
				$tipo_pergunta	= pg_result($res,0,'tipo_pergunta');
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
	<form action="pergunta_cadastro.php" method="POST">
	<div style="padding:10px;">
		<table style="width:600px;margin:auto; text-align:left; border:none;">
			<tr>	
				<td>
					<label for="relacao">Tipo de Pergunta</label><br />
					<select name="tipo_pergunta" id="tipo_pergunta" class="frm" style="width:250px;">
						<option value=""></option>
						<?php
							$sql = "SELECT tipo_pergunta,descricao
									FROM tbl_tipo_pergunta
									WHERE fabrica = $login_fabrica AND ativo";
							$res = pg_query($con,$sql);
							for($i = 0; $i < pg_num_rows($res); $i++) {
								$tipo = pg_result($res,$i,'tipo_pergunta');
								$descricao_combo = pg_result($res,$i,'descricao');
								$selected = ($tipo_pergunta == $tipo) ? 'selected' : '';
						?>

								<option value="<?=$tipo?>" <?=$selected?>><?=$descricao_combo?></option>

						<?php } ?>
					</select>
				</td>
				<td width="320">
					<label for="descricao">Descrição</label><br />
					<input type="hidden" name="pergunta" value="<?=$pergunta;?>" />
					<input type="text" value="<?=$descricao;?>" size="40" id="descricao" name="descricao" class="frm" />
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
				   tbl_pergunta.descricao, tbl_tipo_pergunta.descricao as tipo_pergunta, pergunta,
				   tbl_tipo_relacao.descricao as relacao
			FROM tbl_pergunta
			JOIN tbl_tipo_pergunta ON tbl_pergunta.tipo_pergunta = tbl_tipo_pergunta.tipo_pergunta
			JOIN tbl_tipo_relacao USING(tipo_relacao)
			WHERE tbl_pergunta.fabrica = $login_fabrica
			ORDER BY tbl_pergunta.descricao";
	$res = pg_query($con,$sql);
	if( pg_num_rows($res) ) {
?>
		<br />
		<table class="formulario" width="700" align="center" cellspacing="1" id="relatorio">
			<tr class="titulo_coluna">
				<th>Ativo</th>
				<th nowrap>Descrição</th>
				<th>Tipo de Pergunta</th>
				<th>Relação</th>
			</tr>
			<tbody>
				<?php 
					for($i = 0 ; $i < pg_num_rows( $res ) ; $i++ ) { 
						$x_ativo 		= pg_result($res,$i,'ativo');
						$x_descricao 	= pg_result($res,$i,'descricao');
						$x_relacao		= pg_result($res,$i,'relacao');
						$tipo_pergunta  = pg_result($res,$i,'tipo_pergunta');
						$pergunta  = pg_result($res,$i,'pergunta');
						$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
				?>
					<tr bgcolor="<?=$cor?>" id="<?=$pergunta?>">
						<td>&nbsp;<?=$x_ativo?></td>
						<td>&nbsp;<?=$x_descricao?></td>
						<td>&nbsp;<?=$tipo_pergunta?></td>
						<td>&nbsp;<?=$x_relacao?></td>
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
		<?php if(!empty($tipo_pergunta)) { ?>
			$("input[name=excluir]").click(function(e) {
			
				if (confirm ("Deseja mesmo excluir essa pergunta?") ) {
					window.location = '?excluir=' + $(this).attr('id');
				}
				
				e.preventDefault();
			
			});
			
			$("input[name=limpar]").click(function(e) {
			
				e.preventDefault();
				window.location = 'pergunta_cadastro.php';
			
			});
		<?php } ?>
	
	});
</script>

<?php include 'rodape.php'; ?>
