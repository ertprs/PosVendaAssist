<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';

$laudo_tecnico = $_GET['laudo'];
if(strlen($laudo_tecnico) == 0) {
	$laudo_tecnico = $_POST['laudo'];
}

$qry_familia = pg_query($con,"SELECT familia, descricao       FROM tbl_familia WHERE fabrica = $login_fabrica ORDER BY descricao");
$qry_linha   = pg_query($con,"SELECT linha, nome as descricao FROM tbl_linha   WHERE fabrica = $login_fabrica ORDER BY descricao");

if ($_POST && !isset($_GET['excluir']) ){
	
	if (!empty($_POST["btnacao"]))    $btnacao    = trim($_POST["btnacao"]);

	//hd 46079
	if (!empty($_POST["cbx_linha"]))          $aux_linha = "'" . trim($_POST["cbx_linha"]) . "'" ;
	
	if (!empty($_POST["cbx_familia"]))          $aux_familia = "'" . trim($_POST["cbx_familia"]) . "'" ;
	if (!empty($_POST["txt_produto"]))          $aux_produto         = "'". trim($_POST["txt_produto"]) ."'";

	//hd 46079
	if ($login_fabrica == 19) {
		if (empty($aux_linha))	$msg_erro        = "Favor informar a linha do produto.";
	} else {
		//Se família ou produto não preenchido(s)
		if (empty($aux_familia) and (empty($aux_produto)))	$msg_erro        = "Favor informar a familia ou a referência do produto.";
	}
	
	if (empty($_POST["cbx_linha"]))            $aux_linha = 'null';

	if (empty($_POST["cbx_familia"]))          $aux_familia = 'null';
	if (empty($_POST["txt_produto"]))          $aux_produto = 'null';

	if (!empty($_POST["txt_ordem"]))           $aux_ordem          = "'". trim($_POST["txt_ordem"]) ."'";
	else                                       $msg_erro        = "Favor informar a Sequência/Ordem.";

	if (!empty($_POST["txt_titulo"]) )          $aux_titulo          = "'". trim($_POST["txt_titulo"]) ."'";
	else                                       $msg_erro        = "Favor Informar o título.";

	//opcional
	if (!empty($_POST["txt_comentario"]))      $aux_comentario    = "'". trim($_POST["txt_comentario"]) ."'";
	else                                       $aux_comentario    = 'null';

	if (!empty($_POST["chk_afirmativa"]))       $aux_afirmativa      = "TRUE";
	else                                        $aux_afirmativa  = "FALSE";
	
	if (!empty($_POST["chk_observacao"]))       $aux_observacao      = "TRUE";
	else                                        $aux_observacao   = "FALSE";

	if (!empty($_POST["chk_usuario_consulta"]))       $aux_usuario_consulta      = "TRUE";
	else                                        $aux_usuario_consulta   = "FALSE";


	if (strlen($msg_erro) == 0) {

		if ($btnacao == "gravar") {
			if(strlen($laudo_tecnico) == 0){
				//Localizar o código do produto pela referência (caso admim não escolheu a opção de familia)
				if ( (empty($aux_familia) or $aux_familia == 'null') and $login_fabrica <> 19 ) {
					if (!empty($aux_produto)){
						//fabrica_origem
						$qry_produto = pg_query ($con,"SELECT produto FROM tbl_produto WHERE referencia = $aux_produto LIMIT 1");
						
						if (pg_num_rows($qry_produto) > 0){
							$aux_cod_produto = pg_result($qry_produto, 0, "produto");
							pg_free_result($qry_produto);
						}

						###INSERE NOVO REGISTRO (Caso produto)
						$sql = "INSERT INTO tbl_laudo_tecnico (
								ordem        ,
								titulo       ,
								afirmativa   ,
								observacao   ,
								produto      ,
								comentario   ,
								admin        ,
								fabrica
							) VALUES (
								$aux_ordem      ,
								$aux_titulo     ,
								$aux_afirmativa ,
								$aux_observacao ,
								'$aux_cod_produto',
								$aux_comentario ,
								$login_admin    ,
								$login_fabrica
							);";

					}

				} else {
					###INSERE NOVO REGISTRO (Caso família)
					$sql = "INSERT INTO tbl_laudo_tecnico (
								ordem       ,
								titulo      ,
								afirmativa  ,
								observacao  ,
								familia     ,
								comentario  ,
								usuario_consulta, 
								admin       ,
								fabrica     ,
								linha       
							) VALUES (
								$aux_ordem      ,
								$aux_titulo     ,
								$aux_afirmativa ,
								$aux_observacao ,
								$aux_familia    ,
								$aux_comentario ,
								$aux_usuario_consulta ,
								$login_admin    ,
								$login_fabrica  ,
								$aux_linha
							);";
				}
			}else{
			###ALTERA REGISTRO

				$sql = "UPDATE  tbl_laudo_tecnico SET
						ordem           = '$txt_ordem'       ,
						titulo          = '$txt_titulo'      ,
						comentario      = '$txt_comentario'  ,
						afirmativa      = '$aux_afirmativa'  ,
						observacao      = '$aux_observacao'  ,
						usuario_consulta      = '$aux_usuario_consulta'  ,
						produto         = $aux_produto       ,
						familia         = $aux_familia       ,
						admin           = '$login_admin'     ,
						linha           = $aux_linha
					WHERE laudo_tecnico  = $laudo_tecnico";
			}
		}
//		echo "$sql ---- $laudo_tecnico";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}
		
	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		if( isset( $_GET['excluir'] ) ) $msg = 'Excluído com Sucesso!';
		else $msg = 'Gravado com Sucesso!';

		header ("Location: $PHP_SELF?msg=$msg");
		exit;

	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		$aux_familia         = trim($_POST["cbx_familia"]);
		$aux_linha           = trim($_POST["cbx_linha"]);
		$aux_produto         = trim($_POST["txt_produto"]);
		$aux_ordem           = trim($_POST["txt_ordem"]);
		$aux_titulo          = trim($_POST["txt_titulo"]);
		$aux_comentario      = trim($_POST["txt_comentario"]);
		if (!empty($_POST["chk_afirmativa"]))      $aux_afirmativa      = "TRUE";
		else                                    $aux_afirmativa  = "";
		if (!empty($_POST["chk_observacao"]))       $aux_observacao      = "TRUE";
		else                                    $aux_observacao   = "";
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");	
	}
}

if(strlen($laudo_tecnico) > 0){
	if ($login_fabrica == 19) {
		$sql = "SELECT  tbl_linha.linha                             ,
						tbl_laudo_tecnico.laudo_tecnico                 ,
						tbl_laudo_tecnico.ordem                         ,
						tbl_laudo_tecnico.titulo as titulo_laudo        ,
						tbl_laudo_tecnico.comentario                    ,
						tbl_laudo_tecnico.afirmativa as afirmativa_laudo,
						tbl_laudo_tecnico.usuario_consulta as usuario_consulta,
						tbl_laudo_tecnico.observacao as observacao_laudo
				FROM  tbl_laudo_tecnico
				JOIN tbl_linha ON tbl_laudo_tecnico.linha = tbl_linha.linha
				WHERE tbl_laudo_tecnico.laudo_tecnico = $laudo_tecnico
				AND   tbl_laudo_tecnico.fabrica = $login_fabrica";
	} else {
		$sql = "SELECT  tbl_familia.familia                             ,
						tbl_produto.referencia as produto_referencia    ,
						tbl_produto.descricao  as produto_descricao     ,
						tbl_laudo_tecnico.laudo_tecnico                 ,
						tbl_laudo_tecnico.ordem                         ,
						tbl_laudo_tecnico.titulo as titulo_laudo        ,
						tbl_laudo_tecnico.comentario                    ,
						tbl_laudo_tecnico.afirmativa as afirmativa_laudo,
						tbl_laudo_tecnico.observacao as observacao_laudo
				FROM  tbl_laudo_tecnico
				LEFT JOIN tbl_familia ON tbl_laudo_tecnico.familia = tbl_familia.familia
				LEFT JOIN tbl_produto ON tbl_laudo_tecnico.produto = tbl_produto.produto
				WHERE tbl_laudo_tecnico.laudo_tecnico = $laudo_tecnico
				AND   tbl_laudo_tecnico.fabrica = $login_fabrica
				AND   (tbl_familia.fabrica = $login_fabrica OR tbl_produto.familia IN (SELECT familia FROM tbl_familia WHERE fabrica = $login_fabrica))
		";
	}
	$res = pg_exec ($con,$sql);

	if(pg_numrows($res) > 0){
		for ($i = 0 ; $i < pg_numrows($res) ; $i++){
			if ($login_fabrica == 19) {
				$aux_linha            = trim(pg_result($res,$i,linha));
			} else {
				$aux_familia            = trim(pg_result($res,$i,familia));
				$aux_produto            = trim(pg_result($res,$i,produto_referencia));
				//$produto_descricao      = trim(pg_result($res,$i,produto_descricao));
			}
			$laudo_tecnico          = trim(pg_result($res,$i,laudo_tecnico));
			$aux_ordem              = trim(pg_result($res,$i,ordem));
			$aux_titulo             = trim(pg_result($res,$i,titulo_laudo));
			$aux_comentario         = trim(pg_result($res,$i,comentario));
			$aux_afirmativa         = trim(pg_result($res,$i,afirmativa_laudo));
			$aux_observacao         = trim(pg_result($res,$i,observacao_laudo));
			$aux_usuario_consulta  = trim(pg_result($res,$i,usuario_consulta));

		}
	}
}

$excluir_laudo = $_GET['excluir'];

if(strlen($excluir_laudo) > 0){

	$sql = "SELECT laudo_tecnico 
			FROM tbl_laudo_tecnico 
			WHERE laudo_tecnico = $excluir_laudo
			AND   fabrica       = $login_fabrica;";
	$res      = pg_exec($con,$sql);
	$msg_erro = pg_errormessage($con);

	if(pg_numrows($res) > 0 AND strlen($msg_erro) == 0){
		$sql2 = "DELETE FROM tbl_laudo_tecnico WHERE laudo_tecnico = $excluir_laudo;";
		$res2 = pg_exec($con,$sql2);

		$msg_erro = pg_errormessage($con);
		if( empty($msg_erro) ){
			header ("Location: $PHP_SELF?msg=Excluído com Sucesso!");
			exit;
		}
	}
}

$visual_black = "manutencao-admin";
$layout_menu = "cadastro";
$title = "CADASTRO DE LAUDO TÉCNICO";
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
	table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
	
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
.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
}
table.comespaco tr td{ padding-left:50px; }

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

</style>

<BR>
<form name="frm_laudo_tecnico" method="post" action="<? echo $PHP_SELF;if(isset($semcab))echo "?semcab=yes"; ?>">

<? if (strlen($msg_erro) > 0) { ?>
<table width="700" border="0" cellpadding="2" cellspacing="1" class="error" align='center'>
	<tr>
		<td><?echo $msg_erro;?></td>
	</tr>
</table>
<? } 
	if(isset($_GET['msg'])):
?>
		<table width="700" border="0" cellpadding="2" cellspacing="1" align='center'>
			<tr>
				<td class="sucesso"><?echo $_GET['msg'];?></td>
			</tr>
		</table>
<?php endif; ?>

<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
	<tr>
		<td valign="top" align="left">
			<table class='formulario comespaco' align='center' width='700' border='0' style="">
				<tr  bgcolor="#596D9B" >					
					<INPUT TYPE="hidden" name='laudo' value='<? echo "$laudo_tecnico"; ?>'>
					<td align='left' colspan='5' class="titulo_tabela">Cadastro de Laudo Técnico</td>
				</tr>
				<tr class='Label'>
					<td>&nbsp;</td>
				</tr>
				<tr class='Label'>
					<td nowrap><label for="txt_ordem" style="cursor:pointer">Sequência / Ordem</label></td>
					<td>
						<input type="text" id="txt_ordem" name="txt_ordem" value="<?=$aux_ordem;?>" size="12" maxlength="20" class="frm">
					</td>
				</tr>
				<tr class='Label'>
					<td nowrap><label for="txt_titulo" style="cursor:pointer">Título</label></td>
					<td>
						<input type="text" id="txt_titulo" name="txt_titulo" value="<?=$aux_titulo;?>" size="66" maxlength="250" class="frm">
					</td>
				</tr>
				<tr class='Label'>
					<td nowrap><label for="txt_titulo" style="cursor:pointer">Comentário/<br> Observação</label></td>
					<td>
						<input type="text" id="txt_comentario" name="txt_comentario" value="<?=$aux_comentario;?>" size="66" maxlength="250" class="frm">
					</td>
				</tr>

				<? //hd 46079
				if ($login_fabrica==19) {
					echo "<tr class='Label'>";
						echo "<td nowrap ><label for='cbx_linha' style='cursor:pointer'>Linha</label></td>";
						echo "<td>";
							echo "<select id='cbx_linha' name='cbx_linha' class='frm'>";
								echo "<option value='' selected='selected'></option>";
									if (pg_num_rows($qry_linha) > 0 ) {
										while ($rs_linha = pg_fetch_array($qry_linha)){
											$linha     = $rs_linha['linha'];
											$descricao = $rs_linha['descricao'];
											echo "<option value=$linha "; if ($linha == $aux_linha) echo "selected='selected'"; echo">";
												echo " $descricao ";
											echo "</option>";
										}
									}
									pg_free_result($qry_linha);
							echo "</select>";
						echo "</td>";
					echo "</tr>";
				} else {
					echo "<tr class='Label'>";
						echo "<td nowrap ><label for='cbx_familia' style='cursor:pointer'>Família</label></td>";
						echo "<td>";
							echo "<select id='cbx_familia' name='cbx_familia' class='frm'>";
								echo "<option value='' selected='selected'></option>";
									if (pg_num_rows($qry_familia) > 0 ) {
										while ($rs_familia = pg_fetch_array($qry_familia)){
											$familia	= $rs_familia['familia'];
											$descricao	= $rs_familia['descricao'];
											echo "<option value='$familia'"; if ($familia == $aux_familia) echo "selected='selected'"; echo ">";
												echo " $descricao ";
											echo "</option>";
										}
									}
									pg_free_result($qry_familia);
							echo "</select>";
						echo "</td>";
					echo "</tr>";
				} ?>

				<? if ($login_fabrica<>19) { ?>
					<tr class='Label'>
						<td nowrap ><label for="txt_produto" style="cursor:pointer">Produto</label></td>
						<td>
							<input type="text" id="txt_produto" name="txt_produto" value="<?=$aux_produto;?>" size="12" maxlength="20" class="frm">&nbsp;
								<a href="javascript:pesquisa_generica('janela1', '', 500, 400, 10, 10, document.frm_laudo_tecnico.txt_produto, 'referencia', '<?= $_PHP_SELF; ?>', 'laudo_tecnico_pesquisa.php');" ><img src="../imagens/lupa.png" align="absmiddle">
								</a>&nbsp;
							<input type="text" id="txt_produto_descricao" name="txt_produto_descricao" value="" size="40" maxlength="50" class="frm">&nbsp;
								<a href="javascript:pesquisa_generica('janela1', '', 500, 400, 10, 10, document.frm_laudo_tecnico.txt_produto_descricao, 'descricao', '<?= $_PHP_SELF; ?>', 'laudo_tecnico_pesquisa.php');"><img src="../imagens/lupa.png" align="absmiddle">
								</a>
						</td>
					</tr>
				<? } ?>
				<tr class='Label'>
					<td nowrap ><label for="chk_afirmativa" style="cursor:pointer">Afirmativa</label></td>
					<td colspan='3'><input type='checkbox' name='chk_afirmativa' id='chk_afirmativa' 
						<? if ($aux_afirmativa == 't'){ echo ' checked="checked"'; } ?>>
					</td>
				</tr>
				<tr class='Label'>
					<td nowrap ><label for="chk_observacao" style="cursor:pointer">Observação</label></td>
					<td colspan='3'><input type='checkbox' name='chk_observacao' id='chk_observacao'
						<? if ($aux_observacao == 't'){ echo ' checked="checked"'; } ?>>
					</td>
				</tr>
				<?php if($login_fabrica == 19){?>
				<tr class='Label'>
					<td nowrap ><label for="chk_usuario_consulta" style="cursor:pointer">Usuário de Consulta</label></td>
					<td colspan='3'><input type='checkbox' name='chk_usuario_consulta' id='chk_usuario_consulta'
						<? if ($aux_usuario_consulta == 't'){ echo ' checked="checked"'; } ?>>
					</td>
				</tr>
				<?php }?>
				<tr class='Label'>
					<td>&nbsp;</td>
				</tr>

				<tr>
					<td class='Label' colspan="2" style="padding-bottom:5px;">
						<center>
							<input type="button" style="background:url('imagens_admin/btn_gravar.gif'); cursor:pointer; width:75px; height:22px;" value=" " onclick="javascript: if (document.frm_laudo_tecnico.btnacao.value == '' ) { document.frm_laudo_tecnico.btnacao.value='gravar' ; document.frm_laudo_tecnico.submit() } else { alert ('Aguarde submissão') }" />
							&nbsp;
							<input type="button" onclick="javascript: if (document.frm_laudo_tecnico.btnacao.value == '' ) { document.frm_laudo_tecnico.btnacao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" style="background:url('imagens_admin/btn_limpar.gif'); cursor:pointer; width:75px; height:22px;" value=" "/>

						</center>
					</td>
				</tr>
			</table>

		<input type='hidden' name='btnacao' value=''>

		<div style="height:20px"></div>

	</td>
</tr>
</table>

<?
//hd 46079
if ($login_fabrica==19) {
	$sql = "SELECT  tbl_linha.nome as linha_descricao      ,
					tbl_laudo_tecnico.laudo_tecnico                 ,
					tbl_laudo_tecnico.ordem                         ,
					tbl_laudo_tecnico.titulo as titulo_laudo        ,
					tbl_laudo_tecnico.afirmativa as afirmativa_laudo,
					tbl_laudo_tecnico.observacao as observacao_laudo,
					tbl_laudo_tecnico.usuario_consulta as usuario_consulta
			FROM  tbl_laudo_tecnico
			JOIN tbl_linha ON tbl_laudo_tecnico.linha = tbl_linha.linha AND tbl_linha.fabrica = $login_fabrica
			WHERE tbl_laudo_tecnico.fabrica = $login_fabrica
			ORDER BY  tbl_linha.nome, tbl_laudo_tecnico.ordem";
} else {
	$sql = "SELECT  tbl_familia.descricao as familia_descricao      ,
					tbl_produto.referencia as produto_referencia    ,
					tbl_produto.descricao  as produto_descricao     ,
					tbl_laudo_tecnico.laudo_tecnico                 ,
					tbl_laudo_tecnico.ordem                         ,
					tbl_laudo_tecnico.titulo as titulo_laudo        ,
					tbl_laudo_tecnico.afirmativa as afirmativa_laudo,
					tbl_laudo_tecnico.observacao as observacao_laudo
			FROM  tbl_laudo_tecnico
			LEFT JOIN tbl_familia ON tbl_laudo_tecnico.familia = tbl_familia.familia AND tbl_familia.fabrica = $login_fabrica
			LEFT JOIN tbl_produto ON tbl_laudo_tecnico.produto = tbl_produto.produto
			WHERE tbl_laudo_tecnico.fabrica = $login_fabrica
			ORDER BY  tbl_laudo_tecnico.ordem";
}
$res = pg_exec ($con,$sql);

echo "<table width='800' border='0' cellspacing='1' align='center' class='tabela'>";
echo "<tr>";
echo "<td colspan='7' class='titulo_tabela'><b>QUESTÕES CADASTRADAS</b></td>";
echo "</tr>";
echo "<tr bgcolor='#D9E2EF'>";
echo "<td class='titulo_coluna'>Ordem /<BR>Sequência</td>";
echo "<td class='titulo_coluna'><b>Título</b></td>";

//hd 46079
if ($login_fabrica==19) {
	echo "<td class='titulo_coluna'>Linha</td>";
} else {
	echo "<td class='titulo_coluna'>Família</td>";
	echo "<td class='titulo_coluna'>Produto</td>";
}

echo "<td class='titulo_coluna'>Afirmativa</td>";
echo "<td class='titulo_coluna'>Observação</td>";
if($login_fabrica == 19)
	echo "<td class='titulo_coluna'>Usuário de Consulta</td>";
echo "<td class='titulo_coluna'>Ação</td>";
echo "</tr>";

for ($i = 0 ; $i < pg_numrows($res) ; $i++){
	//hd 46079
	if ($login_fabrica==19) {
		$linha_descricao  = trim(pg_result($res,$i,linha_descricao));
	} else {
		$familia_descricao  = trim(pg_result($res,$i,familia_descricao));
		$produto_referencia = trim(pg_result($res,$i,produto_referencia));
		$produto_descricao  = trim(pg_result($res,$i,produto_descricao));
	}

	$laudo_tecnico      = trim(pg_result($res,$i,laudo_tecnico));
	$ordem_laudo        = trim(pg_result($res,$i,ordem));
	$titulo_laudo       = trim(pg_result($res,$i,titulo_laudo));
	$afirmativa_laudo   = trim(pg_result($res,$i,afirmativa_laudo));
	$observacao_laudo   = trim(pg_result($res,$i,observacao_laudo));
	$usuario_consulta   = trim(@pg_result($res,$i,usuario_consulta));

	$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

	echo "<tr bgcolor='$cor' class='Label'>";

	echo "<td align='left'>$ordem_laudo</a></td>";
	echo "<td align='left' nowrap><b><a href='$PHP_SELF?laudo=$laudo_tecnico'>$titulo_laudo</b></a></td>";
	//hd 46079
	if ($login_fabrica==19) {
		echo "<td align='left'>$linha_descricao</a></td>";
	} else {
		echo "<td align='left'>$familia_descricao</a></td>";
		echo "<td align='center'>$produto_referencia - $produto_descricao</a></td>";
	}
	echo "<td align='center'>"; if($afirmativa_laudo == 't'){ echo "SIM"; }else{ echo "NÃO";} echo "</a></td>";
	echo "<td align='center'>"; if($observacao_laudo == 't'){ echo "SIM"; }else{ echo "NÃO";} echo "</a></td>";
	if ($login_fabrica==19)
		echo "<td align='center'>"; if($usuario_consulta == 't'){ echo "SIM"; }else{ echo "NÃO";} echo "</a></td>";

	echo "<td align='left' nowrap><b><input type=\"button\" value=\"Excluir\" onclick=\"window.location='$PHP_SELF?excluir=$laudo_tecnico'\" /></b></td>";

	echo "</tr>";
}
echo "</table>";
?>


<div style="height:40px"></div>

<? if(!isset($semcab))include "rodape.php"; ?>
