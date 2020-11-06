<?php
/**
 * @author Brayan L. Rastelli
 * @description Cadastrar tipo de pergunta para questionario callcenter - HD 674943
 */

	unset($descricao,$ativo,$tipo_pergunta,$pergunta,$tipo_resposta);
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	$layout_menu = "cadastro";
	$admin_privilegios="cadastros";
	include 'autentica_admin.php';
	include 'funcoes.php';

	/* Request para excluir */
	if ( isset( $_GET['excluir'] ) ) {

		$pergunta = (int) $_GET['excluir'];
		if(!empty($pergunta)) {

			$sql = "DELETE FROM tbl_pergunta
					WHERE pergunta = $pergunta";
			$res = @pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);
			if(empty($msg_erro)) {
				header('Location: ?msg='.traduz("Gravado com Sucesso").'');
			}
			else if ( strpos($msg_erro, 'foreign key') ) {
				$msg_erro = traduz("Pergunta já foi respondida no callcenter, portanto não pode ser excluída.");
			}

		}

	}
	/* Fim exclusao */

	/* Request para gravar */

	if( isset($_POST['enviar']) && $_POST['enviar'] == 'Gravar' ) {

		$descricao 		= trim( $_POST['descricao'] );
		$ativo			= trim( $_POST['ativo'] );
		$tipo_pergunta	= trim( $_POST['tipo_pergunta'] );
		$tipo_resposta	= trim( $_POST['tipo_resposta'] );
		$pergunta 		= trim( $_POST['pergunta'] );
		$texto_ajuda	= trim( $_POST['texto_ajuda'] );

		if( empty($descricao) )
			$msg_erro = traduz("Digite a descrição.");
        else if (empty($tipo_pergunta))
            $msg_erro = traduz("Selecione o tipo/requisito da pergunta");
		else if (empty($tipo_resposta))
			$msg_erro = traduz("Selecione o tipo da resposta");
		else if( empty($ativo) )
			$msg_erro = traduz("Selecione a situação, ativo ou inativo.");

		if(empty($msg_erro)) {

			if (!empty($tipo_resposta)) {

				$sql_p = "SELECT resposta FROM tbl_resposta WHERE pergunta = $tipo_pergunta LIMIT 1;";
				$res_p = pg_query($con,$sql_p);

				if (pg_num_rows($res_p) == 0) {
					$update_tipo_resposta_field = ",tipo_resposta = ";
					$update_tipo_resposta_value = "$tipo_resposta";
				}

				

				$insert_tipo_resposta_field = "tipo_resposta , ";
				$insert_tipo_resposta_value = "$tipo_resposta ,";

			}

			pg_query($con,"BEGIN TRANSACTION");

			if(!empty($pergunta)) { // UPDATE

				$sql = "SELECT pergunta FROM tbl_pergunta WHERE pergunta = $pergunta";
				$res = pg_query($con,$sql);
				if( pg_num_rows($res) ) {

					$sql = "UPDATE tbl_pergunta
							SET descricao = '$descricao',
							ativo = '$ativo',
							tipo_pergunta = $tipo_pergunta,
							texto_ajuda = '$texto_ajuda'
							$update_tipo_resposta_field $update_tipo_resposta_value
							WHERE pergunta = $pergunta";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);
					if(empty($msg_erro))
						$msg = traduz("Gravado com Sucesso");

				}
				else
					$msg_erro = traduz("Pergunta não encontrada");

			}else{ //INSERT

				$sql = "INSERT INTO tbl_pergunta (descricao,ativo,tipo_pergunta, $insert_tipo_resposta_field fabrica, texto_ajuda)
						VALUES ('$descricao','$ativo',$tipo_pergunta, $insert_tipo_resposta_value $login_fabrica, '$texto_ajuda')";
				$res = pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);
				if(empty($msg_erro))
					$msg = traduz("Gravado com Sucesso");

			}

			if( empty($msg_erro) ) {
				pg_query($con,"COMMIT");
				unset($descricao,$ativo,$tipo_pergunta,$pergunta,$tipo_resposta,$texto_ajuda);
				header('Location: ?msg='.traduz("Gravado com Sucesso").'');
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
			$sql = "SELECT ativo, descricao, tipo_pergunta, tipo_resposta, ordem, texto_ajuda
					FROM tbl_pergunta
					WHERE pergunta = $pergunta";
			$res = pg_query($con,$sql);
			if( pg_num_rows($res) ) {
				$descricao 		= pg_result($res,0,'descricao');
				$ativo			= pg_result($res,0,'ativo');
				$tipo_pergunta	= pg_result($res,0,'tipo_pergunta');
				$tipo_resposta	= pg_result($res,0,'tipo_resposta');
				$texto_ajuda	= pg_result($res,0,'texto_ajuda');

				$sql_p = "SELECT resposta FROM tbl_resposta WHERE pergunta = $pergunta LIMIT 1;";
				$res_p = pg_query($con,$sql_p);

				if (pg_num_rows($res_p) > 0) {
					$block_tipo_resposta = ' tabindex= "-1";	aria-disabled= "true";';
					$block_tipo_resposta_class = "bloqueia_edicao"; 
					
					$bloqueia_edicao = "";
					$bloqueia_edicao_select = "";
					$bloqueia_edicao_class = "";
					
					if ($login_fabrica == 52) {
						$bloqueia_edicao = "readonly: readonly";
						$bloqueia_edicao_select = '	tabindex= "-1";	aria-disabled= "true";';
						$bloqueia_edicao_class = "bloqueia_edicao";
					}
				}
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

	$title= traduz("CADASTRO DE PERGUNTA");
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
.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 10px auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
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

.bloqueia_edicao {
	pointer-events: none;
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

	<form action="<?=$PHP_SELF?>" method="POST">
		<div style="padding:10px;">
			<table style="width:600px;margin:auto; text-align:left; border:none;">
				<tr>
					<td>

						<label for="relacao"><?=traduz('Tipo de Pergunta/Requisito')?></label><br />
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
					<td>
						<label for="relacao"><?=traduz('Tipo de Resposta')?></label><br />						
						<select name="tipo_resposta" id="tipo_resposta" class="frm" style="width:250px;" <?=$block_tipo_resposta?> >
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
				</tr>
				<tr>
					<td width="320">
						<label for="descricao"><?=traduz('Descrição')?></label><br />
						<input type="hidden" name="pergunta" value="<?=$pergunta;?>" />
						<input type="text" value="<?=$descricao;?>" size="40" id="descricao" name="descricao" class="frm" <?=$bloqueia_edicao?> />
					</td>
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
					<td colspan="3">
						<label for="ativo"><?=traduz('Informação de ajuda sobre a pergunta')?></label><br />
						<textarea rows="8" cols="78" id="texto_ajuda" name="texto_ajuda" class="frm"><?=$texto_ajuda;?></textarea>
					</td>
				</tr>

				<tr>
					<td colspan="3" align="center">
						<input type="submit" name="enviar" value="Gravar"  />
						<?php if(!empty($pergunta)) { ?>
							&nbsp;&nbsp;<input type="button" name="limpar" value='<?=traduz("Limpar")?>' />
							&nbsp;&nbsp;<input type="button" name="excluir" id="<?=$pergunta?>" value='<?=traduz("Excluir")?>' />
						<? } ?>
					</td>
				</tr>
			</table>
		</div>
	</form>
</div>

<?php
	$sql = "SELECT  CASE WHEN tbl_pergunta.ativo
                         THEN 'Ativo'
                         ELSE 'Inativo'
                    END AS ativo                                ,
                    pergunta                                    ,
                    tbl_pergunta.ordem                          ,
                    tbl_pergunta.descricao                      ,
                    tbl_pergunta.texto_ajuda                    ,
                    tbl_tipo_pergunta.descricao as tipo_pergunta,
                    tbl_tipo_resposta.descricao as tipo_resposta
			FROM    tbl_pergunta
			JOIN    tbl_tipo_pergunta ON tbl_pergunta.tipo_pergunta = tbl_tipo_pergunta.tipo_pergunta
       LEFT JOIN    tbl_tipo_resposta USING(tipo_resposta)
			WHERE   tbl_pergunta.fabrica = $login_fabrica
        ORDER BY    ativo                       ,
                    tbl_tipo_pergunta.descricao ,
                    tbl_pergunta.descricao";
	$res = pg_query($con,$sql);

	if( pg_num_rows($res) ) {
?>
		<br />
		<table class="formulario" align="center" cellspacing="1" id="relatorio">
			<thead>
				<tr class="titulo_coluna">
					<th><?=traduz('Ativo')?></th>
					<th><?=traduz('Descrição')?></th>
					<th><?=traduz('Tipo de Pergunta / Requisito')?></th>
					<th width="120"><?=traduz('Tipo de Resposta')?></th>
					<th width="150"><?=traduz('Informação de Ajuda')?></th>
				</tr>
			</thead>
			<tbody>
				<?php
					for($i = 0 ; $i < pg_num_rows( $res ) ; $i++ ) {
						$x_ativo 		= pg_result($res,$i,'ativo');
						$x_descricao 	= pg_result($res,$i,'descricao');
						$tipo_pergunta  = pg_result($res,$i,'tipo_pergunta');
						$pergunta  = pg_result($res,$i,'pergunta');
						$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

						$img_src = ($x_ativo == 'Ativo') ? "imagens/icone_ok.gif" : "imagens/icone_deletar.png";
				?>
					<tr bgcolor="<?=$cor?>" id="<?=$pergunta?>">
						<td>&nbsp;<img src="<?echo $img_src?>" alt=""> </td>
						<td nowrap>&nbsp;<?=$x_descricao?></td>
						<td>&nbsp;<?=$tipo_pergunta?></td>
						<td>&nbsp;<?=pg_result($res,$i,'tipo_resposta')?></td>
						<td>&nbsp;<?=pg_result($res,$i,'texto_ajuda')?></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>

<?  } ?>
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
<script type="text/javascript">
	$().ready(function(){

		$("#ordem").numeric({'allow' : '.'});

		$("#relatorio > tbody > tr").click(function(){

			tipo = $(this).attr('id');
			window.location = '?pergunta=' + tipo;

		});
		<?php if(!empty($tipo_pergunta)) { ?>
			$("input[name=excluir]").click(function(e) {

				if (confirm ('<?=traduz("Deseja mesmo excluir essa pergunta?")?>' ) ) {
					window.location = '?excluir=' + $(this).attr('id');
				}

				e.preventDefault();

			});

			$("input[name=limpar]").click(function(e) {

				e.preventDefault();
				window.location = 'pergunta_cadastro_new.php';

			});
		<?php } ?>

	});
</script>

<?php include 'rodape.php'; ?>
