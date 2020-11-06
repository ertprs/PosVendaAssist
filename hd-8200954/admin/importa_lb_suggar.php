<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "financeiro";
include 'autentica_admin.php';

$btnacao = $_POST['btnacao'];
if(strlen($btnacao)>0){
	$produto = trim($_POST['produto']);
	$pecas   = $_POST['pecas'];
	
	$xpecas  = trim($pecas);
	$xpecas  = str_replace("\r\n\r\n", "','", $xpecas);
	$xpecas  = str_replace("\r\n", "','", $xpecas);
	$xpecas  = str_replace("-", "", $xpecas);
	$xpecas  = "'". $xpecas ."'";

	$sql = "select produto from tbl_produto where referencia='$produto'";
	$res = pg_exec($con,$sql);
if(pg_numrows($res)>0){
	$produto_produto = pg_result($res,0,produto);
}else{
$msg_erro = "produto não encontrado";
}
/*echo "1produto: $produto - $produto_produto<BR><BR>";
	echo "2peca: $xpecas<BR><BR>";
exit;*/
//$sql_pecas = "select peca from tbl_peca where fabrica=24 and referencia in($xpecas)";
	if(strlen($msg_erro)==0){
		$sql = "BEGIN TRANSACTION";
		$res = pg_exec($con,$sql);
		
		$sql = "INSERT INTO suggar_peca_importacao(peca, posicao, qtde)
				SELECT peca, posicao, qtde from tbl_lista_basica 
				WHERE peca IN(
								SELECT peca 
								FROM tbl_peca 
								WHERE fabrica=24 
								AND referencia in($xpecas)
							) 
				AND fabrica=24 
				AND produto=$produto_produto";
		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
	//echo "3insere pecas <B>$sql</B><BR><BR>";




	$sql = "INSERT INTO suggar_peca_importacao(peca)
				SELECT peca 
				FROM tbl_peca 
				WHERE fabrica=24 
				AND referencia in($xpecas) 
				AND peca NOT IN (
								SELECT peca 
								FROM suggar_peca_importacao
								)";
		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);	
	//echo "3.1 insere na suggar_peca_importacao o que nao tiver: <B>$sql</B><BR>";


	$sql = "DELETE from suggar_peca_importacao 
			where peca not in(SELECT peca 
				FROM tbl_peca 
				WHERE fabrica=24 
				AND referencia in($xpecas))";
	$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);	
//	echo "3.2 Apaga da suggar_peca_importacao qdo nao tiver : <B>$sql</B><BR>";

		$sql = "DELETE FROM tbl_lista_basica 
				WHERE produto=$produto_produto  
				AND fabrica=24";
		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
	//	echo "4apaga lista basica: <B>$sql</B><BR><BR>";
	
	//insere na lista_basica
	
		$sql = "INSERT INTO tbl_lista_basica(fabrica, ativo, produto, peca,qtde,posicao)
				SELECT '24', 't', $produto_produto, peca, qtde, posicao 
				FROM suggar_peca_importacao";
		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
	//echo "5insere na lb: <B>$sql</B><BR>";
	
		$sql = "DELETE FROM suggar_peca_importacao";
		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
//	echo "6limpa temp: <B>$sql</B>";
	
	
		if (strlen($msg_erro) == 0) {
			$res = pg_exec($con,"COMMIT TRANSACTION");
			header("Location: $PHP_SELF");
			exit;
		}else{
			$res = pg_exec($con,"ROLLBACK TRANSACTION");
		}
	}
}


$layout_menu = "financeiro";
$title='IMPORTA LISTA BÁSICA';
include 'cabecalho.php';



?>
<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 9px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Arial;
	font-size: 9px;
	font-weight: normal;
}
.ConteudoBranco {
	font-family: Arial;
	font-size: 9px;
	color:#FFFFFF;
	font-weight: normal;
}
.Mes{
	font-size: 8px;
}
</style>

<form name="frm_acumular_extratos" method="post" action="<? echo $PHP_SELF; ?>">

<? if (strlen($msg_erro) > 0) { ?>
<table width="700" border="0" cellpadding="2" cellspacing="1" class="error" align='center'>
	<tr>
		<td><? echo $msg_erro; ?></td>
	</tr>
</table>
<? } ?>

<table width='500' class='Conteudo' style='background-color: #485989' border='0' cellpadding='5' cellspacing='1' align='center'>
	<tr >
 		<td class='Titulo' background='imagens_admin/azul.gif'>IMPORTA LISTA BÁSICA</td>
	</tr>
	<tr>
		<td bgcolor='#DBE5F5'>
				<table width="500" border="0" cellpadding="2" cellspacing="1" class="titulo" align='center'>
					<tr>
						<td align='center'><BR>REFERENCIA DO PRODUTO<BR><input type="text" class="frm" name="produto" value="<? echo $produto; ?>" size="10" maxlength="10" ></td>
					</tr>
					<tr>
						<td align='center'><BR>REFERENCIAS DA PEÇAS POR LINHA<BR><textarea name='pecas' cols='20' rows='7' class='frm'><? echo $pecas; ?></textarea>
						</td>
					</tr>
					<tr>
					<td align='center'><BR><BR>
						<img src="imagens/btn_continuar.gif" onclick="javascript: if (document.frm_acumular_extratos.btnacao.value == '' ) { document.frm_acumular_extratos.btnacao.value='gravar' ; document.frm_acumular_extratos.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0' style="cursor:pointer;">
						<img src="imagens_admin/btn_limpar.gif" onclick="javascript: if (document.frm_acumular_extratos.btnacao.value == '' ) { document.frm_acumular_extratos.btnacao.value='reset' ; window.location='<? echo $PHP_SELF; ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0' style="cursor:pointer;">
					</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
<input type='hidden' name='btnacao' value=''>

</form>
