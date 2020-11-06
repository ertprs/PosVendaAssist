<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';

$btn_acao = $_POST['btn_acao'];
if(strlen($btn_acao)>0){
	$referencia = $_POST['referencia'];
	$descricao  = $_POST['descricao'];
	$pergunta   = $_POST['pergunta'];
	$faq        = $_POST['faq'];
	$resposta   = $_POST['resposta'];
	if(strlen($faq)==0){ /*cadastro de uma situacao*/
		if(strlen($pergunta)==0){$msg_erro = "Por favor informar a pergunta";}

		$sql = "SELECT produto from tbl_produto where referencia='$referencia'";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$produto = pg_result($res,0,0);
		}else{
			$msg_erro = "Produto não encontrado";
		}
		if(strlen($msg_erro)==0){
			$sql = "insert into tbl_faq(produto,situacao,fabrica)values($produto,'$pergunta',$login_fabrica)";
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);

			if(strlen($msg_erro)==0){
				$sql = "SELECT CURRVAL ('seq_faq')";
				$res = pg_exec($con,$sql);
				$faq = pg_result($res,0,0);
				header("Location: $PHP_SELF?faq=$faq");
				exit;
			}
		
		}
	}
	if(strlen($faq)>0 and strlen($resposta)>0){/*cadastro de uma situacao*/
//		$sql = "INSERT INTO tbl_faq_solucao()";
	
	}
}

$title = "Atendimento Call-Center"; 
$layout_menu = 'callcenter';

include 'funcoes.php';
$faq = $_GET['faq'];
if(strlen($faq)>0){
	$sql = "select tbl_produto.referencia,
					tbl_produto.descricao,
					tbl_faq.situacao as pergunta
			from tbl_faq
			JOIN tbl_produto on tbl_produto.produto = tbl_faq.produto
			WHERE tbl_faq.faq=$faq";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		$referencia = pg_result($res,0,referencia);
		$descricao  = pg_result($res,0,descricao);
		$pergunta   = pg_result($res,0,pergunta);
	}

}

include "cabecalho.php";

?>
<style>
.input {font-size: 10px; 
		  font-family: verdana; 
		  BORDER-RIGHT: #666666 1px double; 
		  BORDER-TOP: #666666 1px double; 
		  BORDER-LEFT: #666666 1px double; 
		  BORDER-BOTTOM: #666666 1px double; 
		  BACKGROUND-COLOR: #ffffff}

.respondido {font-size: 10px; 
				color: #4D4D4D;
			  font-family: verdana; 
			   BORDER-RIGHT: #666666 1px double; 
		  BORDER-TOP: #666666 1px double; 
		  BORDER-LEFT: #666666 1px double; 
		  BORDER-BOTTOM: #666666 1px double; 
			  BACKGROUND-COLOR: #ffffff;
			 }
</style>

<!--=============== <FUNÇÕES> ================================!-->
<? include "javascript_pesquisas.php" ?>
<script language="JavaScript">

function fnc_pesquisa_produto (campo, tipo) {

	if (campo.value != "") {
		var url = "";
		url = "produto_pesquisa.php?campo=" + campo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.referencia= document.frm_cadastro.referencia;
		janela.descricao = document.frm_cadastro.descricao;
		janela.focus();
	}
}
</script>
<br><br>
<? if(strlen($msg_erro)>0){ 
	$callcenter                = trim($_POST['callcenter']);

?>
 <table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F7503E;font-size:10px'><tr>
<td align='center'><? echo "<font color='#FFFFFF'>$msg_erro</font>"; ?>
</td>
</tr>
</table>
<?}?>
<form name="frm_cadastro" method="post" action="<?$PHP_SELF?>">
<input name="faq" class="input" type="hidden" value='<?echo $faq;?>'>
<table width="450" border="0" align="center" cellpadding="2" cellspacing="2" style='font-size:11px'>
  <tr>
    <td align='left'>
	<table width="100%" border='0'>
	<tr>
	<td align='left'><strong>Cadastro de Faq</strong></td>
	</tr>
	</table>

        <table width='450' border='0' align='center' cellpadding="2" cellspacing="2" style='border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
          <tr> 
            <td align='left'><strong>Referência:</strong></td> 
            <td align='left'>
			<input type="text"  name="referencia" value="<? echo $referencia ?>" <?if(strlen($faq)>0)echo "DISABLED"; ?> size="10" maxlength="15">&nbsp;<a href='#'><img src="imagens_admin/btn_buscar5.gif" onclick='javascript:fnc_pesquisa_produto(document.frm_cadastro.referencia,"referencia")'></a>
            </td>
            <td align='left'><strong>Descrição</strong></td>
            <td align='left'>
			<input type="text" name="descricao" value="<? echo $descricao ?>" <?if(strlen($faq)>0)echo "DISABLED"; ?> size="25" maxlength="50">&nbsp;<a href='#'><img src="imagens_admin/btn_buscar5.gif" onclick='javascript:fnc_pesquisa_produto(document.frm_cadastro.descricao,"descricao")'></a>	</td>
          </tr>
		  <tr> 
            <td align='left'><strong>Pergunta?</strong></td> 
            <td align='left' colspan='3'>
			<input type="text"  name="pergunta" value="<? echo $pergunta ?>" <?if(strlen($faq)>0)echo "DISABLED"; ?> size="60" maxlength="500">
            </td>
          </tr>
		<?if(strlen($faq)>0){?>
		  <tr> 
            <td align='left'><strong>Resposta</strong></td> 
            <td align='left' colspan='3'>
			<TEXTAREA NAME="resposta"  ROWS="6" COLS="72"  class="input" style='font-size:10px'><?echo $resposta ;?></TEXTAREA>
            </td>
          </tr>
		<?}?>
        </table>

</td>
  </tr>
  <tr>
    <td align='left'>
		<table width='450' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#006633 1px solid; background-color: #DCF1DF;font-size:10px'>
		<tr> 
		 <td align='center' colspan='5'><input class="botao" type="hidden" name="btn_acao"  value=''>
			<input  class="input"  type="button" name="bt"        value='Gravar' onclick="javascript:if (document.frm_cadastro.btn_acao.value!='') alert('Aguarde Submissão'); else{document.frm_cadastro.btn_acao.value='Gravar';document.frm_cadastro.submit();}">
		</td>
		</tr>
		</table>
	  </td>
  </tr>

</table>
</form>
<? include "rodape.php";?>