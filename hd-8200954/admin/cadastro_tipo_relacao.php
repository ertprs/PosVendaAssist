<?php
/**
 * @author Brayan L. Rastelli
 * @description Cadastrar tipo de pergunta para questionario callcenter - HD 674943
 */
 
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	$layout_menu = "cadastro";
	$admin_privilegios="cadastros";
	include 'autentica_admin.php';
	
	/* Request para excluir */
	if ( isset( $_GET['excluir'] ) ) {
	
		$tipo = (int) $_GET['excluir'];
		if(!empty($tipo)) {
		
			$sql = "DELETE FROM tbl_tipo_relacao
					WHERE fabrica = $login_fabrica
					AND tipo_relacao = $tipo";
			$res = @pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);
			if(empty($msg_erro)) {
				header('Location: ?msg=Gravado com Sucesso');
			}
			else if ( substr($msg_erro, 'foreign key') )
				$msg_erro = "Para excluir esse tipo de relação, exclua ou modifique os tipos de perguntas relacionados";
		
		}
	
	}
	/* Fim exclusao */
	
	/* Request para gravar */
	
	if( isset($_POST['enviar']) && $_POST['enviar'] == 'Gravar' ) {
	
		$descricao 		= trim( $_POST['descricao'] );
		$ativo			= trim( $_POST['ativo'] );
		$sigla			= trim( $_POST['sigla'] );
		$relacao		= trim( $_POST['relacao'] );
		$tipo_relacao 	= trim( $_POST['tipo_relacao'] );
		
		if( empty($descricao) )
			$msg_erro = "Digite a descrição.";
		else if( empty($ativo) )
			$msg_erro = "Selecione a situação, ativo ou inativo.";
		
		if(empty($msg_erro)) {
		
			pg_query($con,"BEGIN TRANSACTION");
		
			if(!empty($tipo_relacao)) { // UPDATE
			
				$sql = "SELECT tipo_relacao FROM tbl_tipo_relacao WHERE tipo_relacao = $tipo_relacao AND fabrica = $login_fabrica";
				$res = pg_query($con,$sql);

				if( pg_num_rows($res) ) {
				
					$sql = "UPDATE tbl_tipo_relacao
							SET descricao = '$descricao',
							ativo = '$ativo'
							WHERE tipo_relacao = $tipo_relacao";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);
					if(empty($msg_erro))
						$msg = "Gravado com Sucesso";
				
				}
				else
					$msg_erro = "Tipo de Pergunta não encontrado";
				
			}		
			else { //INSERT
			
				$sql = "INSERT INTO tbl_tipo_relacao (descricao,sigla_relacao,ativo,fabrica) 
						VALUES ('$descricao', '$sigla', '$ativo',$login_fabrica)";
				$res = pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);
				if(empty($msg_erro))
					$msg = "Gravado com Sucesso";
			
			}
			
			if( empty($msg_erro) ) {
				pg_query($con,"COMMIT");
				unset($descricao,$ativo,$tipo_relacao,$relacao);
			}
			else
				pg_query($con,"ROLLBACK");
		
		}
	
	}
	
	/* Fim Request */
	
	/* Request GET */
	if ( isset($_GET['tipo_relacao']) ) {
	
		$tipo_relacao = (int) $_GET['tipo_relacao'];

		if(!empty($tipo_relacao)) {

			$sql = "SELECT tbl_tipo_relacao.ativo, tbl_tipo_relacao.descricao, sigla_relacao
					FROM tbl_tipo_relacao
					WHERE tbl_tipo_relacao.fabrica = $login_fabrica AND tipo_relacao = $tipo_relacao";

			$res = pg_query($con,$sql);

			if( pg_num_rows($res) ) {
				$descricao 	= pg_result($res,0,'descricao');
				$ativo		= pg_result($res,0,'ativo');
				$relacao	= pg_result($res,0,'sigla_relacao');
				$sigla	= pg_result($res,0,'sigla_relacao');
			}

		}
	}
	/* Fim request GET */
	
	if(!empty($ativo)) {		
		$x_ativo 	= ($ativo == 't') ? 'selected' : FALSE;
		$inativo 	= ($ativo == 'f') ? 'selected' : FALSE;		
	}
	
	/* Mensagem por GET */
	if (isset($_GET['msg'])  && !empty($_GET['msg']) ) { 
		$msg = $_GET['msg'];
	}
	
	$title="CADASTRO DE TIPO DE RELAÇÃO";

	if ($login_fabrica == 52) {

		$title = "CADASTRO DE RELAÇÃO DOS TIPO DE PERGUNTAS";
	}

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
		<table style="width:350px;margin:auto; text-align:left; border:none;">
			<tr>
				<td width="250">
					<label for="descricao">Descrição</label><br />
					<input type="hidden" name="tipo_relacao" value="<?=$tipo_relacao;?>" />
					<input type="text" value="<?=$descricao;?>" size="35" id="descricao" name="descricao" class="frm" />
				</td>
				<td width="250">
					<label for="sigla">Sigla</label><br />
					<input type="text" <?=(!empty($tipo_relacao)) ? 'disabled="disabled"' : ''?> value="<?=$sigla;?>" size="2" maxlength="1" id="sigla" name="sigla" class="frm" />
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
					<?php if(!empty($tipo_relacao)) { ?>
						&nbsp;&nbsp;<input type="button" name="limpar" value="Limpar" />
						&nbsp;&nbsp;<input type="button" name="excluir" id="<?=$tipo_relacao?>" value="Excluir" />
					<? } ?>
				</td>
			</tr>
		</table>
	</div>
	</form>
</div>

<?php
	$sql = "SELECT CASE WHEN tbl_tipo_relacao.ativo THEN 'Ativo' ELSE 'Inativo' END as ativo,
				   tbl_tipo_relacao.descricao, tipo_relacao
			FROM tbl_tipo_relacao
			WHERE tbl_tipo_relacao.fabrica = $login_fabrica
			ORDER BY descricao";
	$res = pg_query($con,$sql);

	if( pg_num_rows($res) ) {
?>
		<br />
		<table class="formulario" width="700" align="center" cellspacing="1" id="relatorio">
			<tr class="titulo_coluna">
				<th nowrap>Descrição</th>
				<th>Ativo</th>
			</tr>
			<tbody>
				<?php 
					for($i = 0 ; $i < pg_num_rows( $res ) ; $i++ ) { 
						$x_ativo 		= pg_result($res,$i,'ativo');
						$x_descricao 	= pg_result($res,$i,'descricao');
						$tipo_relacao  = pg_result($res,$i,'tipo_relacao');
						$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
				?>
					<tr bgcolor="<?=$cor?>" id="<?=$tipo_relacao?>">
						<td align="center">&nbsp;<?=$x_descricao?></td>
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
			window.location = '?tipo_relacao=' + tipo;
		
		});
		<?php if(!empty($tipo_relacao)) { ?>
			$("input[name=excluir]").click(function(e) {
			
				if (confirm ("Deseja mesmo excluir esse tipo de pergunta?") ) {
					window.location = '?excluir=' + $(this).attr('id');
				}
				else return false;
				
				e.preventDefault();
			
			});
			
			$("input[name=limpar]").click(function(e) {
			
				e.preventDefault();
				window.location = 'cadastro_tipo_relacao.php';
			
			});
		<?php } ?>
	
	});
</script>

<?php include 'rodape.php'; ?>
