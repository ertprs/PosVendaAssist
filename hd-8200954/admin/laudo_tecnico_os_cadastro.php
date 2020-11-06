<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';

$qry_familia = pg_query($con,"SELECT familia, descricao FROM tbl_familia WHERE fabrica = $login_fabrica ORDER BY descricao");

if ($_POST){
	
	if (!empty($_POST["btnacao"]))    $btnacao    = trim($_POST["btnacao"]);
	
	if (!empty($_POST["cbx_familia"]))          $aux_familia = "'" . trim($_POST["cbx_familia"]) . "'" ;
	if (!empty($_POST["txt_produto"]))          $aux_produto         = "'". trim($_POST["txt_produto"]) ."'";

	//Se família ou produto não preenchido(s)
	if (empty($aux_familia) and (empty($aux_produto)))	$msg_erro        = "Favor informar a familia ou a referência do produto.";

	if (!empty($_POST["txt_titulo"]))           $aux_titulo          = "'". trim($_POST["txt_titulo"]) ."'";
	else                                    $msg_erro        = "Favor informar o título.";
	if (!empty($_POST["chk_afirmativa"]))       $aux_afirmativa      = "TRUE";
	else                                    $aux_afirmativa  = "FALSE";
	if (!empty($_POST["chk_observacao"]))       $aux_observacao      = "TRUE";
	else                                    $aux_observacao   = "FALSE";

	if (strlen($msg_erro) == 0) {

		if ($btnacao == "gravar") {

			//Localizar o código do produto pela referência (caso admim não escolheu a opção de familia)
			if (empty($aux_familia)){
				if (!empty($aux_produto)){
																										//fabrica_origem
					$qry_produto = pg_query ($con,"SELECT produto FROM tbl_produto WHERE referencia = $aux_produto LIMIT 1");
					
					if (pg_num_rows($qry_produto) > 0){
						$aux_cod_produto = pg_result($qry_produto, 0, "produto");
						pg_free_result($qry_produto);
					}

					###INSERE NOVO REGISTRO (Caso produto)
					$sql = "INSERT INTO tbl_laudo_tecnico (
							titulo,
							afirmativa,
							observacao,
							produto,
							admin,
							fabrica
						) VALUES (
							$aux_titulo,
							$aux_afirmativa,
							$aux_observacao,
							$aux_cod_produto,
							$login_admin,
							$login_fabrica
						);";

				}

			} else {

				###INSERE NOVO REGISTRO (Caso família)
				$sql = "INSERT INTO tbl_laudo_tecnico (
							titulo,
							afirmativa,
							observacao,
							familia,
							admin,
							fabrica
						) VALUES (
							$aux_titulo,
							$aux_afirmativa,
							$aux_observacao,
							$aux_familia,
							$login_admin,
							$login_fabrica
						);";
			}


		}/*else{
			###ALTERA REGISTRO

			if (!empty($_POST["laudo_tecnico"]))          $aux_laudo_tecnico = "'" . trim($_POST["laudo_tecnico"]) . "'" ;
			else                                    $msg_erro = "Favor informar o laudo técnico";

				$sql = "UPDATE  tbl_laudo_tecnico SET
						titulo          = $txt_titulo,
						afirmativa      = $chk_afirmativa,
						observacao      = $chk_observacao,
						produto         = $txt_produto,
						familia         = $aux_familia,
						admin           = $login_admin
					WHERE 
						laudo_tecnico   = $aux_laudo_tecnico;";
		}*/
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}
		
	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF");
		exit;

	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		$aux_familia         = trim($_POST["cbx_familia"]);
		$aux_produto         = trim($_POST["txt_produto"]);
		$aux_titulo          = trim($_POST["txt_titulo"]);	
		if (!empty($_POST["chk_afirmativa"]))      $aux_afirmativa      = "TRUE";
		else                                    $aux_afirmativa  = "";
		if (!empty($_POST["chk_observacao"]))       $aux_observacao      = "TRUE";
		else                                    $aux_observacao   = "";
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");	
	}
}

$visual_black = "manutencao-admin";
$layout_menu = "cadastro";
$title = "Cadastro de Laudo Técnico";
if(!isset($semcab))include 'cabecalho.php';
?>

<script language="javascript" type="text/javascript" src="js/js_jean.js"></script>

<style type="text/css">
	.Label{
	font-family: Verdana;
	font-size: 10px;
	}
	.Titulo{
	font-family: Verdana;
	font-size: 12px;
	font-weight: bold;
	}
	.Erro{
	font-family: Verdana;
	font-size: 12px;
	color:#FFF;
	border:#485989 1px solid; background-color: #990000;
	}
</style>

<form name="frm_laudo_tecnico" method="post" action="<? echo $PHP_SELF;if(isset($semcab))echo "?semcab=yes"; ?>">

<? if (strlen($msg_erro) > 0) { ?>
<table width="600" border="0" cellpadding="2" cellspacing="1" class="error" align='center'>
	<tr>
		<td><?echo $msg_erro;?></td>
	</tr>
</table>
<? } ?>

<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
	<tr>
		<td valign="top" align="left">
			<table style=' border:#485989 1px solid; background-color: #e6eef7 ' align='center' width='750' border='0'>
				<tr  bgcolor="#596D9B" >
					<td align='left' colspan='4'><font size='2' color='#ffffff'>Cadastro de Laudo Técnico OS</font></td>
				</tr>
				<tr class='Label'>
					<td>&nbsp;</td>
				</tr>
				<tr class='Label'>
					<td nowrap ><label for="txt_titulo" style="cursor:pointer">Digite o Título</label></td>
					<td>
						<input type="text" id="txt_titulo" name="txt_titulo" value="<?=$aux_titulo;?>" size="40" maxlength="40" class="frm">
					</td>
				</tr>
				<tr class='Label'>
					<td nowrap ><label for="cbx_familia" style="cursor:pointer">Família</label></td>
					<td>
						<select id="cbx_familia" name="cbx_familia" class="frm">
							<option value="" selected="selected"></option>
							<?	if (pg_num_rows($qry_familia) > 0 ) {
									while ($rs_familia = pg_fetch_array($qry_familia)){
										$familia	= $rs_familia['familia'];
										$descricao	= $rs_familia['descricao'];
							?>
							<option value="<?= $familia ?>" <? if ($familia == $aux_familia) echo 'selected="selected"'; ?>><?= $descricao ?></option>
							<?		}
								}
								pg_free_result($qry_familia);
							?>
						</select>
					</td>
				</tr>
				<tr class='Label'>
					<td nowrap ><label for="txt_produto" style="cursor:pointer">Produto</label></td>
					<td>
						<input type="text" id="txt_produto" name="txt_produto" value="<?=$aux_produto;?>" size="12" maxlength="20" class="frm">&nbsp;
							<a href="javascript:pesquisa_generica('janela1', '', 500, 400, 10, 10, document.frm_laudo_tecnico.txt_produto, 'referencia', '<?= $_PHP_SELF; ?>', 'laudo_tecnico_pesquisa.php');"><img src="imagens_admin/btn_buscar5.gif" align="absmiddle">
							</a>&nbsp;
						<input type="text" id="txt_produto_descricao" name="txt_produto_descricao" value="" size="40" maxlength="50" class="frm">&nbsp;
							<a href="javascript:pesquisa_generica('janela1', '', 500, 400, 10, 10, document.frm_laudo_tecnico.txt_produto_descricao, 'descricao', '<?= $_PHP_SELF; ?>', 'laudo_tecnico_pesquisa.php');"><img src="imagens_admin/btn_buscar5.gif" align="absmiddle">
							</a>
					</td>
				</tr>
				<tr class='Label'>
					<td nowrap ><label for="chk_afirmativa" style="cursor:pointer">Afirmativa</label></td>
					<td colspan='3'><input type='checkbox' name='chk_afirmativa' id='chk_afirmativa' 
						<? if (!empty($aux_afirmativa)){ echo ' checked="checked"'; } ?>>
					</td>
				</tr>
				<tr class='Label'>
					<td nowrap ><label for="chk_observacao" style="cursor:pointer">Observação</label></td>
					<td colspan='3'><input type='checkbox' name='chk_observacao' id='chk_observacao'
						<? if (!empty($aux_observacao)){ echo ' checked="checked"'; } ?>>
					</td>
				</tr>
				<tr class='Label'>
					<td>&nbsp;</td>
				</tr>
			</table>

		<input type='hidden' name='btnacao' value=''>

		<div style="height:20px"></div>

		<center>

			<img src="imagens_admin/btn_gravar.gif" onclick="javascript: if (document.frm_laudo_tecnico.btnacao.value == '' ) { document.frm_laudo_tecnico.btnacao.value='gravar' ; document.frm_laudo_tecnico.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0' style="cursor:pointer;">

			<img src="imagens_admin/btn_limpar.gif" onclick="javascript: if (document.frm_laudo_tecnico.btnacao.value == '' ) { document.frm_laudo_tecnico.btnacao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0' style="cursor:pointer;">

		</center>

	</td>
</tr>
</table>

<div style="height:100px"></div>

<? if(!isset($semcab))include "rodape.php"; ?>