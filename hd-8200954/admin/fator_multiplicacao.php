<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "call_center,gerencia";

include 'autentica_admin.php';

if(strlen($_POST['btn_acao'])>0){
	$cont_fator = $_POST['cont_fator'];
	$erro= "";
	$res = pg_exec ($con,"BEGIN TRANSACTION");

	for($i=0; $i < $cont_fator; $i++){
		$erro_msg= "";

		$fator				=$_POST["fator_multiplicacao_$i"];
		$valor_inicio		=$_POST["valor_inicio_$i"];
		//VALOR_INICIO
		if($valor_inicio >= 0)	
			$valor_inicio= number_format(str_replace( ',', '.', $valor_inicio), 2, '.','');

		else{
			$erro_msg.= "Digite o valor de início para o nº $fator!<br>";
		}

		$valor_fim			=$_POST["valor_fim_$i"];
		//VALOR_FIM
		if($valor_fim > 0)	
			$valor_fim = number_format(str_replace( ',', '.', $valor_fim), 2, '.','');
		else{
			$erro_msg.= "Digite o valor de fim para o nº $fator!<br>";
		}
		
		$porcentagem_fator	=$_POST["porcentagem_fator_$i"];

		//PORCENTAGEM
		if($porcentagem_fator > 0){
			$porcentagem_fator = number_format(str_replace( ',', '.', $porcentagem_fator), 2, '.','');
		}else{
			$erro_msg.= "Digite a porcentagem para o nº $fator!<br>";
		}

		if(strlen($erro_msg)==0){
			$sql= "UPDATE tbl_fator_multiplicacao
				SET		valor_inicio = $valor_inicio,
						valor_fim	 = $valor_fim,
						porcentagem_fator = $porcentagem_fator
				WHERE  fator_multiplicacao = $fator";

			$res= pg_exec ($con,$sql);
			if(pg_result_error($res)){
				$erro_msg.= "Falha na Alteração!";
				$erro.= $erro_msg;
			}
		}else{
			$erro.= $erro_msg;
		}

	}	
	echo "<font color='red' > $erro</font> ";
	if(pg_result_error($res) or (strlen($erro)>0)) {
		$res = pg_exec ($con,"ROLLBACK;");
		$erro_msg.= "Falha na Alteração!";
	}else{
		$res = pg_exec ($con,"COMMIT;");
		$ok_msg= "Alterado com sucesso!";
	}
}

	$layout_menu = "cadastro";
	$title = "CADASTRAMENTO DE TIPO DE POSTOS";
	include 'cabecalho.php';


?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<!-- AQUI COMEÇA O HTML DO MENU -->

<head>

	<title>CADASTRO DE COTAÇÃO</title>

	<meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
	<meta http-equiv="Expires"       content="0">
	<meta http-equiv="Pragma"        content="no-cache, public">
	<meta http-equiv="Cache-control" content="no-cache, public, must-revalidate, post-check=0, pre-check=0">
	<meta name      ="Author"        content="Telecontrol Networking Ltda">
	<meta name      ="Generator"     content="na mão...">
	<meta name      ="Description"   content="Sistema de gerenciamento para Postos de Assistência Técnica e Fabricantes.">
	<meta name      ="KeyWords"      content="Assistência Técnica, Postos, Manutenção, Internet, Webdesign, Orçamento, Comercial, Jóias, Callcenter">

	<link type="text/css" rel="stylesheet" href="css/css.css">
</head>

<script language="JavaScript">
function abrir() {
var x="5"; 

//x = validarFormulario(nome_campo,tipo_campo,desc_campo,bool_campo);
alert('Cotação salva com sucesso. Foram enviados email para os Fornecedores');
}

function fnc_pesquisa_produto (campo, tipo) {
	if (campo.value != "") {
		var url = "";
		url = "produto_pesquisa.php?retorno=<? echo $PHP_SELF ?>&forma=reload&campo=" + campo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.descricao = document.frm_produto.descricao;
		janela.referencia= document.frm_produto.referencia;
		//janela.linha     = document.frm_produto.linha;
		//janela.familia   = document.frm_produto.familia;
		janela.focus();
	}
}
</script>



<body bgcolor='#ffffff' marginwidth='2' marginheight='2' topmargin='2' leftmargin='2' >

<style type="text/css">
.menu_top {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	border: 0px;
}
.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color: #000000;
	border: 0px;
}

.titulo {
	font-family: Arial;
	font-size: 10pt;
	color: #000000;
	background: #ced7e7;
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

.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.subtitulo{
	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
</style>
<?


?>

<table class='table_line' width='700' border='0' cellspacing='1' cellpadding='2' class='formulario' align='center'>
<FORM name='frm_produto' action="<? $PHP_SELF ?>" METHOD='POST'>
	
  
  <tr>
    <td colspan='3' align='center'>

  <table width='700' align='center' class='formulario'>
	<?php if(strlen($ok_msg)>0){?>
			<tr class='sucesso'><td colspan='6'><?php echo $ok_msg; ?> </td></tr>
	<?php } ?>
	<tr>	
    <td colspan='6' nowrap class='titulo_tabela'>Cadastro do Fator de Multiplicação para Definição de Preço Sugerido</td>
  </tr>
	<tr class='titulo_coluna'>	
	  <td nowrap width='50px' align='center'>#</td>
	  <td nowrap align='center' nowrap>Valor Início</td>
	  <td nowrap width='10px' align='center' nowrap>&nbsp;</td>
	  <td nowrap align='center' nowrap>Valor Fim</td>
	  <td nowrap align='center' nowrap>Fator Multiplicação</td>
	</tr>


<?

$sql= "SELECT fator_multiplicacao,
		REPLACE(CAST(CAST(valor_inicio AS NUMERIC(12,2)) AS VARCHAR(14)),'.', ',') as valor_inicio,
		REPLACE(CAST(CAST(valor_fim AS NUMERIC(12,2)) AS VARCHAR(14)),'.', ',') as valor_fim,
		REPLACE(CAST(CAST(porcentagem_fator AS NUMERIC(12,2)) AS VARCHAR(14)),'.', ',') as porcentagem_fator
		FROM tbl_fator_multiplicacao
		order by fator_multiplicacao";

$res = $res = pg_exec ($con,$sql);
if(@pg_numrows($res)>0){
	$c=1;
	for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {
		$fator_multiplicacao= trim(pg_result($res,$i,fator_multiplicacao));
		$valor_inicio		= trim(pg_result($res,$i,valor_inicio));
		$valor_fim			= trim(pg_result($res,$i,valor_fim));
		$porcentagem_fator	=trim(pg_result($res,$i,porcentagem_fator));

		if ($c==2){ 
			echo "<tr bgcolor='#F7F5F0'>"; 
			$c=0;
		}else {
			echo "<tr bgcolor='#F1F4FA'>";
		}
		echo "<td nowrap align='center'>";
  		echo "<input type='hidden' name='fator_multiplicacao_$i' value='$fator_multiplicacao' size='8'><font color='#0000ff'>$fator_multiplicacao</font>";
		echo "</td>";
		echo "<td nowrap align='center'> <input type='text' name='valor_inicio_$i' value='$valor_inicio' size='8' class='frm'></td>";
		echo "<td nowrap align='center' >a</td>";
		echo "<td nowrap align='center' ><input type='text' name='valor_fim_$i' value='$valor_fim' size='8' class='frm'></td>";
		echo "<td nowrap align='center' >%<input type='text' name='porcentagem_fator_$i' value='$porcentagem_fator' size='8' class='frm'></td>";
		echo "</tr>";

		$c++;
	}
}else{
			echo "<tr bgcolor='#ff5555'><td colspan='5'> Sem requisições cadastradas&nbsp;</td></tr>"; 


}
?>  

  <tr>
   <td colspan='5' align='center'>
	  <input type='hidden' name='cont_fator' value='<? echo $i;?>'>
	 <input type='submit' name='btn_acao' value='Gravar'>
   </td>
  </tr>
   </table>
  </td>
  </tr>
  </form>
  <tr>
   <td>
   <table width='100%' align='left'>
	<tr>
      <td nowrap class='table_line' align='left'>&nbsp;
	  <!--<input type='button' onClick="abrir();" name='enviar' value='Gravar'>--></td>
      <td nowrap class='table_line' align='right'>&nbsp;
	  <!--<input type='submit' name='enviar' value='Cancelar'>--></td>
	</tr>
   </table>
	</td>
   </tr>
		
</table>
</body>
</html>

<? include "rodape.php"; ?>