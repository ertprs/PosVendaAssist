<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';

$btnacao = trim($_REQUEST["btnacao"]);

$produto = $_REQUEST['produto'];
$referencia = $_REQUEST['referencia'];
$descricao  = $_REQUEST['descricao'];
$tipo_mascara   = @$_REQUEST['tipo_mascara'];
$min   = @$_REQUEST['min'];
$max   = @$_REQUEST['max'];
$msg_erro = "";

if ($btnacao == "deletar") {
	$sql = "SELECT 
			tbl_produto.produto
		FROM tbl_produto
			LEFT JOIN tbl_produto_valida_serie USING(produto)
			JOIN tbl_linha USING(linha)
		WHERE tbl_linha.fabrica = $login_fabrica
		AND tbl_produto.referencia = '$referencia';";

	$res = pg_query($con, $sql);

	if(pg_num_rows($res)>0){
		$produto = pg_fetch_result($res,0,produto);
	}

	if(strlen($produto)>0){
		$sql = "DELETE FROM tbl_produto_valida_serie WHERE produto = $produto;";
		$res = pg_query ($con,$sql);
		header ("Location: $PHP_SELF?msg=Removido com Sucesso");
		exit;
	}
}

if ($btnacao == "gravar") {
	if(strlen($referencia) == 0) 
		$msg_erro = "Informe a Referência do Produto";
	else{
		$sql = "SELECT 
				tbl_produto.produto
			FROM tbl_produto
				JOIN tbl_linha USING(linha)
			WHERE tbl_linha.fabrica = $login_fabrica
				AND tbl_produto.referencia = '$referencia' ";
		$res = pg_query($con, $sql);

		if(pg_num_rows($res)>0){
			$xproduto = pg_fetch_result($res,0,produto);
		}else{
			$msg_erro = "Informe um Produto Válido";
		}
	}

	if(strlen($min) == 0 AND strlen($msg_erro) == 0){
		$msg_erro = "Informe uma quantidade mínima";
	}

	if(strlen($max) == 0 AND strlen($msg_erro) == 0){
		$msg_erro = "Informe uma quantidade máxima";
	}

	if(strlen($msg_erro) == 0){
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		$res = pg_exec ($con,"DELETE FROM tbl_produto_valida_serie WHERE fabrica = $login_fabrica AND produto = $xproduto;");

		$total = $max + 1;
		for($i = $min; $i < $total; $i++){
			$serie = str_pad($numero, $i, $tipo_mascara, STR_PAD_LEFT);
			$sql = "INSERT INTO tbl_produto_valida_serie (fabrica ,produto ,mascara) VALUES ($login_fabrica,$xproduto ,'$serie');";
			$res = pg_query ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		if (strlen($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			header ("Location: $PHP_SELF?msg=Gravado com Sucesso!");
			exit;
		}else{
			$msg_erro = "Erro ao cadastrar numero de serie";
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}
}

$msg = $_GET['msg'];

$layout_menu = "cadastro";
$title = "CADASTRAMENTO DE MÁSCARA DE NÚMERO DE SÉRIE";
include 'cabecalho.php';
?>

<style>
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

.sucesso{
    background-color:#008000;
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

.espaco{
	padding-left:150px;
}
</style>
<script type='text/javascript' src='js/jquery-1.6.1.min.js'></script>
<script type="text/javascript">
	function fnc_pesquisa_produto (campo, tipo) {
		if (campo.value != "") {
			var url = "";
			url = "produto_pesquisa.php?retorno=<? echo $PHP_SELF ?>&campo=" + campo.value + "&tipo=" + tipo ;
			janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=0, left=0");
			janela.retorno = "<? echo $PHP_SELF ?>";
			janela.referencia= document.frm_produto.referencia;
			janela.descricao = document.frm_produto.descricao;
			janela.linha     = document.frm_produto.linha;
			janela.familia   = document.frm_produto.familia;
			janela.focus();
		}
	}
</script>
<?
//CARREGA REGISTRO
if(strlen($produto)>0){
	$sql = "
			SELECT 
				tbl_produto.referencia							,
				tbl_produto.descricao							,
				LPAD (tbl_produto_valida_serie.mascara,1) AS masc		,
				MIN(LENGTH(tbl_produto_valida_serie.mascara)) AS min	,
				MAX(LENGTH(tbl_produto_valida_serie.mascara)) AS max
			FROM tbl_produto
				JOIN tbl_linha USING(linha)
				JOIN tbl_produto_valida_serie USING(produto)
			WHERE 
				tbl_linha.fabrica = $login_fabrica 
				AND tbl_produto_valida_serie.produto = $produto
			GROUP BY 
				tbl_produto.referencia					,
				tbl_produto.descricao					,
				LPAD (tbl_produto_valida_serie.mascara,1)
			ORDER BY tbl_produto.referencia;";
	$res = pg_query($con, $sql);

	if(pg_num_rows($res)>0){
		$referencia		= pg_fetch_result($res,0,referencia);
		$descricao		= pg_fetch_result($res,0,descricao);
		$min			= pg_fetch_result($res,0,min);
		$max			= pg_fetch_result($res,0,max);
		$tipo_mascara	= pg_fetch_result($res,0,masc);
	}
}
 ?>
<br>
<form method="post" action="<? echo $PHP_SELF; ?>" name="frm_produto">
<table width="700" cellpadding='3' cellspacing="1" align="center" class="formulario" border="0">
	<?php if(strlen($msg_erro)>0){ ?>
			<tr class='msg_erro'>
				<td colspan='2'><?php echo $msg_erro; ?> </td>
			</tr>
	<?php } ?>

	<?php if(strlen($msg)>0){ ?>
			<tr class='sucesso'>
				<td colspan='2'><?php echo $msg; ?> </td>
			</tr>
	<?php } ?>

	<tr class='titulo_tabela'><td colspan="2">Cadastro</td></tr>
	<tr><td colspan="2">&nbsp;</td></tr>
	<tr>
		<td class='espaco' width="130">
			Referência<br>
			<input type="text" class="frm" name="referencia" value="<? echo $referencia ?>" size="12" maxlength="20" />
			<a  href='#'><img src="imagens/lupa.png" onclick="javascript: fnc_pesquisa_produto (document.frm_produto.referencia, 'referencia')"></a>
		</td>
		<td>
			Descrição<br>
			<input type="text" class="frm" size="40" name="descricao" value="<? echo $descricao ?>" maxlength="50" >
			<a href='#'><img src="imagens/lupa.png" onclick="javascript: fnc_pesquisa_produto (document.frm_produto.descricao, 'descricao')"></a>
		</td>
	</tr>
	<?php 
		function verificaSelect($v1, $v2){
			echo ($v1 == $v2) ? " selected " : "";
		}

		if(strlen($tipo_cadastro) == 0){
			$tipo_cadastro = "0";
		}
	?>
	<tr>
		<td align="left" class='espaco' >
			Tipo de Máscara<br>
			<select name='tipo_mascara' id='tipo_mascara' class='frm' style='width: 110px'>
				<option value='l' <?php verificaSelect('l',$tipo_mascara); ?>>Alfa</option>
				<option value='n' <?php verificaSelect('n',$tipo_mascara); ?>>Numérico</option>
				<option value='q' <?php verificaSelect('q',$tipo_mascara); ?>>Alfanumérico</option>
			</select>
		</td>
		<td  align="left" valign='bottom'>
			<span style='margin-right: 30px;'>
				Min
				<input type='text' name='min' style='width: 50px' id='min' class='frm' value='<?php echo $min;?>' />
			</span>
			<span>
				Max
				<input type='text' name='max' style='width: 50px' id='max' class='frm' value='<?php echo $max;?>' />
			</span>
		</td>
	</tr>
	<tr>
		<td colspan="2" align="center"><br>
			<input type='hidden' name='btnacao' value=''>
			<input type="button" value="Gravar" onclick="javascript: if (document.frm_produto.btnacao.value == '' ) { document.frm_produto.btnacao.value='gravar' ; document.frm_produto.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário"  border='0' style="cursor:pointer;">
			<input type="button" value="Apagar" ONCLICK="javascript: if (document.frm_produto.btnacao.value == '' ) { document.frm_produto.btnacao.value='deletar' ; document.frm_produto.submit() } else { alert ('Aguarde submissão') }" ALT="Apagar" border='0' style="cursor:pointer;">
			<br><br>
		</td>
	</tr>
</table>
</form>
<br />
<?php

	$sql = "	SELECT 
				tbl_produto.produto							,
				tbl_produto.referencia							,
				tbl_produto.descricao							,
				tbl_produto.ativo								,
				LPAD (tbl_produto_valida_serie.mascara,1) AS masc		,
				MIN(LENGTH(tbl_produto_valida_serie.mascara)) AS min	,
				MAX(LENGTH(tbl_produto_valida_serie.mascara)) AS max
			FROM tbl_produto
				JOIN tbl_linha USING(linha)
				JOIN tbl_produto_valida_serie USING(produto)
			WHERE 
				tbl_linha.fabrica = $login_fabrica
			GROUP BY 
				tbl_produto.produto					,
				tbl_produto.referencia					,
				tbl_produto.descricao					,
				tbl_produto.ativo						,
				LPAD (tbl_produto_valida_serie.mascara,1)
			ORDER BY tbl_produto.referencia;";
	$res = pg_query($con, $sql);

	if(pg_num_rows($res)>0){
		echo "<table width='700' cellpadding='4' cellspacing='1' align='center' class='tabela' >";
			echo "<tr class='titulo_coluna'>";
				echo "<td>Status</td>";
				echo "<td>Referência</td>";
				echo "<td>Descrição</td>";
				echo "<td>Tipo</td>";
				echo "<td>Mínimo</td>";
				echo "<td>Máximo</td>";
			echo "</tr>";

			for($i=0; $i<pg_num_rows($res); $i++){
				$produto	= pg_fetch_result($res,$i,produto);
				$referencia	= pg_fetch_result($res,$i,referencia);
				$descricao	= pg_fetch_result($res,$i,descricao);
				$ativo	= pg_fetch_result($res,$i,ativo);
				$min		= pg_fetch_result($res,$i,min);
				$max		= pg_fetch_result($res,$i,max);
				$mascara	= pg_fetch_result($res,$i,masc);

				switch ($mascara) {
					case "l"  : $mascara = "Alfa"; break;
					case "n" : $mascara = "Numérico"; break;
					case "q" : $mascara = "Alfanumérico"; break;
					default: $mascara = "";
				}

				$cor = ($i%2) ? "#F7F5F0" : "#F1F4FA";
				echo "<tr bgcolor='$cor'>";
					echo "<td align='center'>";
						echo ($ativo <> 't') ?"<img src='imagens_admin/status_vermelho.gif' border='0' alt='Inativo'>" : "<img src='imagens_admin/status_verde.gif' border='0' alt='Ativo'>";
					echo "</td>";
					echo "<td><a href='$PHP_SELF?produto=$produto'>$referencia</a></td>";
					echo "<td align='left'><a href='$PHP_SELF?produto=$produto'>$descricao</a></td>";
					echo "<td align='left'>$mascara</td>";
					echo "<td align='left'>$min</td>";
					echo "<td align='left'>$max</td>";
				echo "</tr>";
			}
			echo "</table>";
	}

include "rodape.php";
?>
