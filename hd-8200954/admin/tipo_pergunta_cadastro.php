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
	include 'funcoes.php';

	/* Request para excluir */
	if ( isset( $_GET['excluir'] ) ) {

		$tipo = (int) $_GET['excluir'];
		if(!empty($tipo)) {

			$sql = "DELETE FROM tbl_tipo_pergunta
					WHERE fabrica = $login_fabrica
					AND tipo_pergunta = $tipo";
			$res = @pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);
			if(empty($msg_erro)) {
				header('Location: ?msg='.traduz("Gravado com Sucesso").'');
			}
			else if ( strpos($msg_erro, 'foreign key') )
				$msg_erro = traduz("Para excluir esse tipo de pergunta, exclua primeiro as perguntas cadastradas neste tipo");

		}

	}
	/* Fim exclusao */

	/* Request para gravar */

	if( isset($_POST['enviar']) && $_POST['enviar'] == 'Gravar' ) {

		$descricao 		= trim( $_POST['descricao'] );
		$ativo			= trim( $_POST['ativo'] );
		$relacao		= trim( $_POST['relacao'] );

        if(in_array($login_fabrica,array(1,35,85,88,94,129,138,145,151,152,161,169,170,180,181,182))){
            $relacao = 'NULL';
        }
		$tipo_pergunta 	= trim( $_POST['tipo_pergunta'] );

		if(empty($descricao)) {
			$msg_erro = "Digite a descrição.";

		} else if (empty($relacao) && !in_array($login_fabrica,array(1,85,94,129,138,145,151,152,161,169,170,180,181,182))) {
			$msg_erro = traduz("Selecione a relação.");
		} else if (empty($ativo)) {
			$msg_erro = traduz("Selecione a situação, ativo ou inativo.");
		}

		if (empty($msg_erro)) {

			pg_query($con,"BEGIN TRANSACTION");

			if (!empty($tipo_pergunta)) { // UPDATE

				$sql = "SELECT tipo_pergunta FROM tbl_tipo_pergunta WHERE tipo_pergunta = $tipo_pergunta AND fabrica = $login_fabrica";
				$res = pg_query($con,$sql);
				if( pg_num_rows($res) ) {

					$sql = "UPDATE tbl_tipo_pergunta
							SET descricao = '$descricao',
							ativo = '$ativo',
							tipo_relacao = $relacao
							WHERE tipo_pergunta = $tipo_pergunta";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);
					if(empty($msg_erro))
						$msg = traduz("Gravado com Sucesso");

				} else {
					$msg_erro = traduz("Tipo de Pergunta não encontrado");
                }
			} else { //INSERT

				$sql = "INSERT INTO tbl_tipo_pergunta (descricao,ativo,tipo_relacao,fabrica)
						VALUES ('$descricao','$ativo',$relacao,$login_fabrica)";
				$res = pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);
				if(empty($msg_erro))
					$msg = traduz("Gravado com Sucesso");
			}

			if( empty($msg_erro) ) {
				pg_query($con,"COMMIT");
				unset($descricao,$ativo,$tipo_pergunta,$relacao);
				header('Location: ?msg='.traduz("Gravado com Sucesso").'');
			} else {
				pg_query($con,"ROLLBACK");
            }
		}

	}

	/* Fim Request */

	/* Request GET */
	if ( isset($_GET['tipo_pergunta']) ) {

		$tipo_pergunta = (int) $_GET['tipo_pergunta'];
		if(!empty($tipo_pergunta)) {
			$sql = "SELECT tbl_tipo_pergunta.ativo, tbl_tipo_pergunta.descricao, tipo_pergunta,tipo_relacao
					FROM tbl_tipo_pergunta
					WHERE tbl_tipo_pergunta.fabrica = $login_fabrica AND tipo_pergunta = $tipo_pergunta";
			$res = pg_query($con,$sql);
			if( pg_num_rows($res) ) {
				$descricao 	= pg_result($res,0,'descricao');
				$ativo		= pg_result($res,0,'ativo');
				$relacao	= pg_result($res,0,'tipo_relacao');
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

	$title= traduz("CADASTRO DE TIPO DE PERGUNTA");
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

	<div class="titulo_tabela"><?=traduz('Cadastro')?></div>
	<form action="tipo_pergunta_cadastro.php" method="POST">
	<div style="padding:10px;">
		<table style="width:500px;margin:auto; text-align:left; border:none;">
			<tr>
				<td width="250">
					<label for="descricao">Descrição</label><br />
					<input type="hidden" name="tipo_pergunta" value="<?=$tipo_pergunta;?>" />
					<input type="text" value="<?=$descricao;?>" size="35" id="descricao" name="descricao" class="frm" />
				</td>
<?php
    if (!in_array($login_fabrica,array(1,35,85,88,94,129,138,145,151,152,161,169,170,180,181,182))) {

?>
				<td>
					<label for="relacao"><?=traduz('Relação')?></label><br />
					<select name="relacao" id="relacao" class="frm">
						<option value=""></option>
						<?php

						        $sql = "SELECT tipo_relacao, descricao
						                FROM tbl_tipo_relacao
						                WHERE fabrica = $login_fabrica";

						        $res = pg_query($con, $sql);

						        for ($i=0; $i < pg_num_rows($res); $i++) {

						                $selected = ( pg_result($res, $i, 'tipo_relacao') == $relacao ) ? 'selected' : '';

						                echo '<option value="'.pg_result($res, $i, 'tipo_relacao').'" '.$selected.'>' . pg_result($res, $i, 'descricao') . '</option>';

						        }

						?>
					</select>
				</td>
<?
    }
?>
                <td>
					<label for="ativo"><?=traduz('Ativo')?></label><br />
					<select name="ativo" id="ativo" class="frm">
						<option value=""></option>
						<option value="t" <? echo $x_ativo; ?>><?=traduz('Ativo')?></option>
						<option value="f" <? echo $inativo; ?>><?=traduz('Inativo')?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="3" align="center">
					<input type="submit" name="enviar" value="Gravar" />
					<?php if(!empty($tipo_pergunta)) { ?>
						&nbsp;&nbsp;<input type="button" name="limpar" value='<?=traduz("Limpar")?>' />
						&nbsp;&nbsp;<input type="button" name="excluir" id="<?=$tipo_pergunta?>" value='<?=traduz("Excluir")?>' />
					<? } ?>
				</td>
			</tr>
		</table>
	</div>
	</form>
</div>

<?php
	$sql = "SELECT  CASE WHEN tbl_tipo_pergunta.ativo
                         THEN 'Ativo'
                         ELSE 'Inativo'
                    END AS ativo,
                    tbl_tipo_pergunta.descricao         ,
                    tipo_pergunta                       ,
                    tbl_tipo_relacao.descricao AS relacao
			FROM    tbl_tipo_pergunta
       LEFT JOIN    tbl_tipo_relacao USING(tipo_relacao)
			WHERE   tbl_tipo_pergunta.fabrica = $login_fabrica
      ORDER BY      ativo       ,
                    descricao
    ";
	$res = pg_query($con,$sql);
	if( pg_num_rows($res) ) {
?>
		<br />
		<table class="formulario" width="700" align="center" cellspacing="1" id="relatorio">
			<tr class="titulo_coluna">
				<th><?=traduz('Ativo')?></th>
				<th nowrap><?=traduz('Descrição')?></th>
<?php
    if(!in_array($login_fabrica,array(35,85,88,94,129,138,145,151,152,161,169,170,180,181,182))){
?>
				<th><?=traduz('Relação')?></th>
<?php
    }
?>
                </tr>
			<tbody>
<?php
					for($i = 0 ; $i < pg_num_rows( $res ) ; $i++ ) {
						$x_ativo 		= pg_result($res,$i,'ativo');
						$x_descricao 	= pg_result($res,$i,'descricao');
						$x_relacao		= pg_result($res,$i,'relacao');
						$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
						$tipo_pergunta  = pg_result($res,$i,'tipo_pergunta');

                        $img_src = ($x_ativo == 'Ativo') ? "imagens/icone_ok.gif" : "imagens/icone_deletar.png";
?>
					<tr bgcolor="<?=$cor?>" id="<?=$tipo_pergunta?>">
						<td>&nbsp;<img src="<?echo $img_src?>" alt=""></td>
						<td>&nbsp;<?=$x_descricao?></td>
<?php
    if (!in_array($login_fabrica,array(35,85,88,94,129,138,145,151,152,161,169,170,180,181,182))) {
?>
						<td>&nbsp;<?=$x_relacao?></td>
<?php
    }
?>
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
			window.location = '?tipo_pergunta=' + tipo;

		});
		<?php if(!empty($tipo_pergunta)) { ?>
			$("input[name=excluir]").click(function(e) {

				if (confirm ('<?=traduz("Deseja mesmo excluir esse tipo de pergunta?")?>') ) {
					window.location = '?excluir=' + $(this).attr('id');
				}
				else return false;

				e.preventDefault();

			});

			$("input[name=limpar]").click(function(e) {

				e.preventDefault();
				window.location = 'tipo_pergunta_cadastro.php';

			});
		<?php } ?>

	});
</script>

<?php include 'rodape.php'; ?>
