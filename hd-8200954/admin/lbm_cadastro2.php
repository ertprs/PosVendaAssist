<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

if ($login_fabrica_nome == "Meteor") {
#	header ("Location: lbm_cadastro_meteor.php");
#	exit;
}

$qtde_linhas = 150 ;
$msg_erro = "";

$btn_acao = trim(strtolower($_POST['btn_acao']));

if (trim($btn_acao) == "duplicar") {

	$res = pg_exec($con,"BEGIN TRANSACTION");
	
	$referencia = $_POST['referencia_duplicar'];
	$sql = "SELECT produto FROM tbl_produto WHERE referencia = '$referencia'";
	$res = pg_exec($con, $sql);

	if (pg_numrows($res) == 0) {
		$msg_erro = "Produto a ser duplicado $referencia não cadastrado";
	} else {
		$produto = pg_result($res,0,0);
		
		$sql = "DELETE FROM tbl_lista_basica
				WHERE  tbl_lista_basica.produto = $produto
				AND    tbl_lista_basica.fabrica = $login_fabrica";
		$res = pg_exec($con, $sql);
		
		for ($i = 0 ; $i < $qtde_linhas ; $i++) {
			$peca = $_POST['peca_' . $i] ;
			$ordem = $_POST['ordem_' . $i] ;
			$descricao = $_POST['descricao_' . $i] ;
			
			$ordem = trim($ordem);

			if (strlen($peca) > 0) {
				$sql = "SELECT peca FROM tbl_peca WHERE referencia = '$peca' AND fabrica = $login_fabrica";
				$res = pg_exec($con, $sql);
				if (pg_numrows($res) == 0) {
					$msg_erro = "Peça $peca não cadastrada";
				} else {
					$peca = pg_result($res,0,0);
					$sql = "INSERT INTO tbl_lista_basica (
							fabrica,
							produto,
							peca,
							qtde,
							posicao
							) VALUES (
							$login_fabrica,
							$produto      ,
							$peca,
							1,
							LPAD ('$ordem',2,'0')
					);";
					$res = pg_exec($con, $sql);
					$msg_erro = pg_errormessage($con);
				}//fim else
			}//fim if
		}//fim for
	}//fim else

	if (strlen($msg_erro) == 0) {
		$res = pg_exec($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF");
		exit;
	}

	$referencia_duplicar = $_POST["referencia_duplicar"];
	$descricao_duplicar  = $_POST["descricao_duplicar"];
	$res = pg_exec($con,"ROLLBACK TRANSACTION");
}//fim duplicar

if (trim($btn_acao) == "gravar") {

	if (strlen($msg_erro) == 0) {
		if (strlen($HTTP_POST_VARS["descricao"]) > 0) {
			$aux_descricao = "'". trim($HTTP_POST_VARS["descricao"]) ."'";
		} else {
			$msg_erro = "Favor informar a descrição do produto.";
		}
	}

	if (strlen($msg_erro) == 0) {
		if (strlen($HTTP_POST_VARS["referencia"]) > 0) {
			$aux_referencia = "'". trim($HTTP_POST_VARS["referencia"]) ."'";
		} else {
			$msg_erro = "Favor informar a referência do produto.";
		}
	}
	
	if (strlen($msg_erro) == 0) {
		$res = pg_exec($con,"BEGIN TRANSACTION");
		
		$referencia = $_POST['referencia'];
		$sql = "SELECT produto FROM tbl_produto WHERE referencia = '$referencia'";
		$res = pg_exec($con, $sql);
		if (pg_numrows($res) == 0) {
			$msg_erro = "Produto $referencia não cadastrado";
		} else {
			$produto = pg_result($res,0,0);
			
			$sql = "DELETE FROM tbl_lista_basica
					WHERE  tbl_lista_basica.produto = $produto
					AND    tbl_lista_basica.fabrica = $login_fabrica";
			$res = pg_exec($con, $sql);
			
			for ($i = 0 ; $i < $qtde_linhas ; $i++) {
				$peca = $_POST['peca_' . $i] ;
				$ordem = $_POST['ordem_' . $i] ;
				$descricao = $_POST['descricao_' . $i] ;
				
				$ordem = trim($ordem);

				if (strlen($peca) > 0) {
					$sql = "SELECT peca FROM tbl_peca WHERE referencia = '$peca' AND fabrica = $login_fabrica";
					$res = pg_exec($con, $sql);
					if (pg_numrows($res) == 0) {
						$msg_erro = "Peça $peca não cadastrada";
					} else {
						$peca = pg_result($res,0,0);
						$sql = "INSERT INTO tbl_lista_basica (
								fabrica,
								produto,
								peca,
								qtde,
								posicao
								) VALUES (
								$login_fabrica,
								$produto      ,
								$peca,
								1,
								LPAD ('$ordem',2,'0')
						);";
						$res = pg_exec($con, $sql);
						$msg_erro = pg_errormessage($con);
					}//fim else
				}//fim if
			}//fim for
		}//fim else

		if (strlen($msg_erro) == 0) {
			$res = pg_exec($con,"COMMIT TRANSACTION");
			header ("Location: $PHP_SELF");
			exit;
		}

		$referencia = $_POST["referencia"];
		$descricao  = $_POST["descricao"];
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
	}
}//fim gravar

if (trim($btn_acao) == "apagar" and strlen($lbm) > 0 ) {
	$res = pg_exec($con,"BEGIN TRANSACTION");
	
	$apagar = $_POST["apagar"];
	
	$sql = "DELETE FROM tbl_lista_basica
			WHERE  tbl_lista_basica.fabrica      = $login_fabrica
			AND    tbl_lista_basica.lista_basica = $apagar;";
	$res = pg_exec($con, $sql);
	$msg_erro = pg_errormessage($con);
	
	if (strlen($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec($con,"COMMIT TRANSACTION");
	} else {
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		
		$produto   = $HTTP_POST_VARS["produto"];
		$peca      = $HTTP_POST_VARS["peca"];
		
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
	}
}//fim apagar

$layout_menu = "cadastro";
$title = "Cadastramento de Lista Básica";
include 'cabecalho.php';?>

<script language='javascript'>
function fnc_pesquisa_produto (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.focus();
	}
}

function fnc_pesquisa_peca (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "peca_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.focus();
	}
}
</script>

<body>
<DIV ID='wrapper'>

<form name="frm_lbm" method="post" action="<? $PHP_SELF ?>">

<font face='arial' size='-1' color='#6699FF'><b>Para pesquisar um produto, informe parte da referência ou descrição do produto.</b></font><?php

if (strlen($msg_erro) > 0) {?>

	<div class='error'>
		<?php echo $msg_erro; ?>
	</div><?php

}

$referencia = $_POST['referencia'];

if (strlen($referencia) > 0) {
	$sql = "SELECT produto, descricao FROM tbl_produto WHERE referencia = '$referencia'";
	$res = pg_exec($con, $sql);
	if (pg_numrows($res) == 0) {
		$msg_erro = "Produto $referencia não cadastrado";
	} else {
		$descricao = pg_result($res,0,descricao);
		$produto   = pg_result($res,0,produto);
	}
}?>

<table width='400' align='center' border='0'>
<tr>
	<td align='center'>
		<b>Referência</b>
	</td>
	<td align='center'>
		<b>Descrição</b>
	</td>
</tr>

<tr>
	<td align='center'>
		<input type="text" name="referencia" value="<? echo $referencia ?>" size="15" maxlength="20">&nbsp;<img src='../imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_lbm.referencia,document.frm_lbm.descricao,'referencia')">
	</td>
	<td align='center'>
		<input type="text" name="descricao" value="<? echo $descricao ?>" size="50" maxlength="50">&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' style="cursor:pointer" align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_lbm.referencia,document.frm_lbm.descricao,'descricao')">
	</td>
</tr>
</table>

<input type='hidden' name='btn_lista' value=''>
<p align='center'><img src='imagens/btn_listabasicademateriais.gif' onclick='document.frm_lbm.btn_lista.value="listar"; document.frm_lbm.submit();' style="cursor:pointer;">

<br>

<center><?php

if (file_exists('/var/www/assist/www/vistas/' . $produto . '.gif')) {
	echo "<a href='vista_explodida.php?produto=$produto' target='_new'>Clique aqui</a> para ver a vista-explodida";
} else {
	echo "Produto sem vista explodida";
}
?>
</center>


<!-- 
<p align='center'><input type='submit' name='btn_lista' style="cursor:pointer" value='Lista Básica de Materiais'>
 -->
<br>

<? 
echo"<div class='error'>";
$btn_lista = $_POST['btn_lista'];

if (strlen($_POST['btn_lista']) > 0) {
	$referencia = $_POST['referencia'];
	if (strlen($referencia) == 0) {
		//echo "<p><center><font face='arial, verdana' color='#FF0033' size='+1'>Preencha a referência do produto</font></center>";
		echo $msg_erro = "Preencha a referência do produto";
	} else {

$sql = "SELECT produto FROM tbl_produto WHERE referencia = '$referencia'";
$res = pg_exec($con, $sql);
if (pg_numrows($res) > 0) {
	$produto = pg_result($res,0,0);
} else {
	$produto = 0;

}
echo"</div>";

$sql = "SELECT  tbl_lista_basica.lista_basica,
				tbl_lista_basica.posicao     ,
				tbl_lista_basica.qtde        ,
				tbl_peca.referencia          ,
				tbl_peca.descricao
			FROM tbl_lista_basica JOIN tbl_peca USING (peca)
			WHERE tbl_lista_basica.fabrica = $login_fabrica
			AND   tbl_lista_basica.produto = $produto
			ORDER BY tbl_lista_basica.posicao";
$res = pg_exec($con, $sql);

for ($i = 0 ; $i < $qtde_linhas ; $i++) {
	
	if ($i % 20 == 0) {
		if ($i > 0) echo "</table>";
		echo "<table width='400' align='center' border='0'>";
		echo "<tr bgcolor='#cccccc'>";
		echo "<td align='center'><b>Ordem</b></td>";
		echo "<td align='center'><b>Peça</b></td>";
		echo "<td align='center'><b>Descrição</b></td>";
		echo "</tr>";
	}

	
	$peca = "peca_$i" ;
	$peca = $$peca;
	
	$ordem = "ordem_$i" ;
	$ordem = $$ordem;
	
	$descricao = "descricao_$i" ;
	$descricao = $$descricao;
	
	if (strlen($btn_lista) > 0) {
		$ordem = "";
		$peca = "";
		$descricao = "";
	}
	
	if ($i < pg_numrows($res) AND strlen($msg_erro) == 0) {
		$lbm       = pg_result($res,$i,lista_basica);
		$ordem     = pg_result($res,$i,posicao);
		$peca      = pg_result($res,$i,referencia);
		$descricao = pg_result($res,$i,descricao);
	}
	
	echo "<tr>";
	
	echo "<td>";
	echo "<input type='text' name='ordem_$i' value='$ordem' size='2' maxlength='2'>";
	echo "</td>";
	
	echo "<td>";
	echo "<input type='text' name='peca_$i' value='$peca' size='20' maxlength='20'>";
	echo "&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca (document.frm_lbm.peca_$i , document.frm_lbm.descricao_$i , \"referencia\")'>";
	echo "</td>";
	
	echo "<td>";
	echo "<input type='text' name='descricao_$i' value='$descricao' size='30' maxlength='50'>";
	echo "&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca (document.frm_lbm.peca_$i , document.frm_lbm.descricao_$i , \"descricao\")'>";
	
	echo "&nbsp;<a href='#'><img src='../imagens/btn_apaga_15.gif' alt='Clique aqui para apagar este item da lista básica.' onclick='document.frm_lbm.btn_acao.value = \"apagar\" ; document.frm_lbm.apagar.value = \"$lbm\" ; document.frm_lbm.submit()'></a>";
	echo "</td>";
	
	echo "</tr>";
}

?>
</table>

<input type='hidden' name='btn_acao' value=''>
<input type='hidden' name='apagar' value=''>
<input type='hidden' name='duplicar' value=''>

<p align='center'><img src='imagens_admin/btn_gravar.gif' onclick='document.frm_lbm.btn_acao.value = "gravar" ; document.frm_lbm.submit()'>

<p>


<!-- ---------------------- Duplicar Lista Básica ---------------------- -->

<center><font face='arial' size='+1'><b>Duplicar Lista Básica para produto</b></font>
</center>

<table width='400' align='center' border='0'>
<tr>
	<td align='center'>
		<b>Referência</b>
	</td>
	<td align='center'>
		<b>Descrição</b>
	</td>
</tr>


<tr>
	<td align='center'>
		<input type="text" name="referencia_duplicar" value="<? echo $referencia_duplicar ?>" size="15" maxlength="20">&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_lbm.referencia_duplicar,document.frm_lbm.descricao_duplicar,'referencia')">
	</td>

	<td align='center'>
		<input type="text" name="descricao_duplicar" value="<? echo $descricao_duplicar ?>" size="50" maxlength="50">&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_lbm.referencia_duplicar,document.frm_lbm.descricao_duplicar,'descricao')">
	</td>
</tr>
</table>

<p align='center'><img src='imagens_admin/btn_duplicar.gif' onclick='document.frm_lbm.btn_acao.value = "duplicar" ; document.frm_lbm.submit()'>


<?

	}
}
?>


</form>
</div>
<?
	include "rodape.php";
?>

</body>
</html>
