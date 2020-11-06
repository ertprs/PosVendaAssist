<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "info_tecnica";
include 'autentica_admin.php';
include 'funcoes.php';

$msg_erro = "";
$msg = "";

if (trim($_POST['comunicado']) > 0) $comunicado = trim($_POST['comunicado']);
if (trim($_GET['comunicado']) > 0)  $comunicado = trim($_GET['comunicado']);

$btn_acao = trim (strtolower ($_POST['btn_acao']));

$ativo = trim($_POST['ativo']);

if (trim($btn_acao) == "gravar") {

	if (strlen($tipo) > 0 or strlen($aux_tipo) > 0)
		{
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		#A pedido da Fabíola, não permitir alterar este comunicado de procedimento em garantia
		# Chamado 12535 - 24/01/2008
		if ($login_fabrica==1 AND $comunicado == "27969" AND $login_admin <> 155){
			$msg_erro .= "Este comunicado não pode ser alterado.";
		}

		$peca_referencia        = trim($_POST['peca_referencia']);
		$produto_referencia     = trim($_POST['produto_referencia']);
		$familia                = trim($_POST['familia']);
		$linha                  = trim($_POST['linha']);
		$descricao              = trim($_POST['descricao']);
		$extensao               = trim($_POST['extensao']);
		$tipo                   = trim($_POST['tipo']);
		$mensagem               = trim($_POST['mensagem']);
		$video					= trim($_POST['video']);
		$obrigatorio_os_produto = trim($_POST['obrigatorio_os_produto']);
		$obrigatorio_site       = trim($_POST['obrigatorio_site']);
		$arquivo                = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;
		$codigo_posto           = trim($_POST['codigo_posto']);
		$posto_nome             = trim($_POST['posto_nome']);
		$tipo_posto             = trim($_POST['tipo_posto']);
		$remetente_email        = trim($_POST['remetente_email']);
		$estado                 = trim($_POST['estado']);
		$ativo                  = trim($_POST['ativo']);
		$pais                   = trim($_POST['pais']);

		if($ip=="201.71.54.144"){
		//echo "../comunicados/".strtolower($nome_anexo)."   -".$aux_extensao;
		}
		///////////////////////////////////////////////////
		if (strlen($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			// HD 16279
			echo "<script language='javascript'>";
			if($login_fabrica==3){
				echo "alert('Arquivo cadastrado com Sucesso');";
			}
			echo "window.location='$PHP_SELF'";
			echo "</script>";
			exit;
			}
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
			}
		else{
			$msg_erro = "É necessário escolher o tipo de comunicado";
			}
		}

include 'cabecalho.php';
include "javascript_pesquisas.php";
include "javascript_calendario.php"; 
?>
<script language="JavaScript">
		function toogleProd(radio){
		var obj = document.getElementsByName('radio_qtde_produtos');
			/*for(var x=0 ; x<obj.length ; x++){*/
		if (obj[0].checked){
			$('#id_um').show("slow");
			$('#id_multi').hide("slow");
			$('#id_tres').hide("slow");
		}
		if (obj[1].checked){
			$('#id_um').hide("slow");
			$('#id_multi').show("slow");
			$('#id_tres').hide("slow");
		}

	}
</script>

<style type="text/css">
		.menu_top {
			text-align: center;
			font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
			font-size: x-small;
			font-weight: bold;
			border: 1px solid;
			color:#ffffff;
			background-color: #596D9B
		}
		.table_line {
			text-align: left;
			font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
			font-size: 10px;
			font-weight: normal;
			border: 0px solid;
			background-color: #f5f5f5
		}
		.table_line2 {
			text-align: left;
			background-color: #fcfcfc
		}
		.ok {
			text-align: left;
			background-color: #f5f5f5;
			border:1px solid gray;
			font-size:12px;
			font-weight:bold;
			font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		}
</style>

<!-- Começo do Codigo ========================================================================-->

<form enctype = "multipart/form-data" name="frm_comunicado" method="post" action="<? echo $PHP_SELF ?>">
	<table width='700' border='0' cellpadding='5' cellspacing='3' align='center'>
		<tr>
			<td colspan=3 align='center' class="menu_top">ESCOLHA O CAMPO
			</td>
		</tr>
		<tr>
			<td class='table_line'>
				Produto
			</td>
			<td class='table_line2' colspan='2'>
				<?
				if (count($lista_produtos)>0){
					$display_um_produto    = "display:none";
					$display_multi_produto = "";
					$display_tres          = "";
					$display_um            = "";
					$display_multi         = " CHECKED ";
					$display_tres_produto  = " CHECKED ";
				} 
				else {
					$display_um_produto    = "";
					$display_multi_produto = "display:none";
					$display_tres_produto  = "display:none";
					$produto_tres          = "";
					$display_um            = " CHECKED ";
					$display_multi         = "";
					$display_tres          = "";
				} 
				/*else {
					$display_um_produto    = "";
					$display_multi_produto = "";
					$display_tres_produto  = "";
					$produto_tres          = "";
					$display_um            = "";
					$display_multi         = "";
					$display_tres          = "";
				}*/
				?>
				<div style='background-color:#F8FCDA;text-aling:center;border:1px solid #F9E780;padding:3px;margin:5px;'>
					<img src='imagens/info.png' style='float:right' rel='ajuda' title='amarelo'>
						<b>Campo: </b>&nbsp;&nbsp;&nbsp;&nbsp;
						Produto 1
					<input type="radio" name="radio_qtde_produtos" value='um'  <?=$display_um?>  onClick='javascript:toogleProd(this)'>
						&nbsp;&nbsp;&nbsp;&nbsp;
						Produto 2
					<input type="radio" name="radio_qtde_produtos" value='muitos' <?=$display_multi?> onClick='javascript:toogleProd(this)'>
						&nbsp;&nbsp;&nbsp;&nbsp;
						Produto 3
					<input type="radio" name="radio_qtde_produtos" value='muitos' <?=$display_tres?> onClick='javascript:toogleProd(this)'>
				</div>
				<!-- Produto 1 ================================================================================-->
				<div id='id_um' style='<?echo $display_um_produto;?>'>
					AAA
				</div>
				<!-- Fim Produto 1 ============================================================================-->
				<!-- Produto 2 ================================================================================-->
				<div id='id_multi' style='<?echo $display_multi_produto;?>'>
					BBB
				</div>
				<!-- Fim Produto 2 ============================================================================-->
				<!-- Produto 3 ================================================================================-->
				<div id='id_tres' style='<?echo $display_tres_produto;?>'>
					CCC
				</div>
				<!-- Fim Produto 3 ============================================================================-->
			</td>
		</tr>
		<!-- Fim do Codigo ========================================================================-->
	</table>
</form>

<table border='0' align='center'>
	<tr>
		<td style='color: B1B1B1; font-size: 10px; '>Depois do campo selecionado<br>
		Entre em gravar.<br><td>
	</tr>
</table>
<?
include "rodape.php";
?>