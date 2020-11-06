<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
if($login_fabrica == 1){
    require "../classes/ParametrosAdicionaisFabrica.php";
    $parametrosAdicionaisObject = new ParametrosAdicionaisFabrica($login_fabrica);
    
    require "../classes/form/GeraComboType.php";
}
if (strlen($_POST['btn_lista']) > 0) $btn_lista = $_POST['btn_lista'];
else                                 $btn_lista = $_GET['btn_lista'];

if ($login_fabrica == 3 and $login_login <> "priscila" and $login_login <> "tulio" and $login_login <> "hugo" and $login_login <> "henrique") {
	/*
	   liberado acesso a lista básica para Hugo e Henrique, 
	   de acordo com email enviado pela Priscila, 
	   assunto "Liberação de acesso" de 11/08/2005 às 08:29
	*/
	echo "Apenas PRISCILA pode fazer manutenção da Lista Básica";
	exit;
}

if ($login_fabrica == 6 and $login_login <> "crisfabrini" and $login_login <> "tulio") {
	echo "Apenas CRISTINA pode fazer manutenção da Lista Básica";
	exit;
}

$acao = trim($_GET['acao']);
if ($acao == "excluir"){
	$produto = trim($_GET['produto']);
	$sql = "DELETE FROM tbl_lista_basica WHERE fabrica = $login_fabrica AND produto = $produto";
	$res = pg_exec($con, $sql);

	#-------------------- Envia EMAIL ------------------
	$sql = "SELECT email_gerente FROM tbl_fabrica WHERE fabrica = $login_fabrica AND LENGTH (trim(email_gerente)) > 0";
	$res = pg_exec($con, $sql);
	if (pg_numrows($res) > 0) {
		$email_gerente = pg_result($res,0,0);

		$sql = "SELECT referencia, descricao FROM tbl_produto WHERE produto = $produto";
		$res = pg_exec($con, $sql);
		$produto_referencia = pg_result($res,0,referencia);
		$produto_descricao  = pg_result($res,0,descricao);

		$email_ok = mail ("$email_gerente" , utf8_encode("Lista Básica Apagara") , utf8_encode("Toda a lista básica do produto $produto_referencia - $produto_descricao acaba de ser apagada no site TELECONTROL") , "From: Telecontrol <helpdesk@telecontrol.com.br>" , "-f helpdesk@telecontrol.com.br" );
	}
	#---------------------------------------------------


	header ("Location: $PHP_SELF");
	exit;
}

if (strlen($_POST["qtde_linhas"]) > 0) $qtde_linhas = $_POST["qtde_linhas"];
else                                   $qtde_linhas = 450 ;

if ($login_fabrica == 3) $qtde_linhas = 150;

$msg_erro = '';

$btn_acao = trim(strtolower ($_POST['btn_acao']));
$lbm      = trim(strtolower ($_POST['lbm']));

if (trim($btn_acao) == "duplicar") {

	$produto = $_POST['produto'];

	$res = pg_exec($con,"BEGIN TRANSACTION");
	
	$sql = "DELETE FROM tbl_lista_basica
			WHERE  tbl_lista_basica.produto = $produto
			AND    tbl_lista_basica.fabrica = $login_fabrica";
	$res = pg_exec($con, $sql);
		
	for ($i = 0 ; $i < $qtde_linhas ; $i++) {
		$peca      = $_POST['peca_' . $i] ;
		$ordem     = $_POST['ordem_' . $i] ;
		$serie_inicial = $_POST['serie_inicial_' . $i] ;
		$serie_final   = $_POST['serie_final_' . $i] ;
		$posicao   = $_POST['posicao_' . $i] ;
		$descricao = $_POST['descricao_' . $i] ;
		$type      = $_POST['type_' . $i] ;
		$qtde      = $_POST['qtde_' . $i] ;
		
		$ordem = trim($ordem);
		$posicao = trim($posicao);

		$serie_inicial = trim($serie_inicial);
		$serie_inicial = str_replace(".","",$serie_inicial);
		$serie_inicial = str_replace("-","",$serie_inicial);
		$serie_inicial = str_replace("/","",$serie_inicial);
		$serie_inicial = str_replace(" ","",$serie_inicial);

		$serie_final   = trim($serie_final);
		$serie_final = str_replace(".","",$serie_final);
		$serie_final = str_replace("-","",$serie_final);
		$serie_final = str_replace("/","",$serie_final);
		$serie_final = str_replace(" ","",$serie_final);


		if (strlen($type) == 0) $aux_type = 'null';
		else                    $aux_type = $type;

		if (strlen($qtde) == 0) $aux_qtde = 1;
		else                    $aux_qtde = $qtde;
		
		if (strlen($ordem) == 0) $ordem = "null";


		if (strlen($peca) > 0) {
			$sql = "SELECT peca FROM tbl_peca WHERE referencia = '$peca' AND fabrica = $login_fabrica";
			$res = @pg_exec($con, $sql);
			if (@pg_numrows($res) == 0) {
				$msg_erro = "Peça $peca não cadastrada";
			} else {
				$peca = @pg_result($res,0,0);
				$sql = "INSERT INTO tbl_lista_basica (
							fabrica,
							produto,
							peca,
							qtde,
							posicao,
							ordem,
							serie_inicial,
							serie_final,
							type
						) VALUES (
							$login_fabrica,
							$produto      ,
							$peca         ,
							$aux_qtde     ,
							'$posicao'    ,
							$ordem        ,
							'$serie_inicial' ,
							'$serie_final'   ,
							'$aux_type'
				);";
				$res = @pg_exec($con, $sql);
				$msg_erro = pg_errormessage($con);
			}
		}
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_exec($con,"COMMIT TRANSACTION");

		#-------------------- Envia EMAIL ------------------
		$sql = "SELECT email_gerente FROM tbl_fabrica WHERE fabrica = $login_fabrica AND LENGTH (trim(email_gerente)) > 0";
		$res = pg_exec($con, $sql);
		if (pg_numrows($res) > 0) {
			$email_gerente = pg_result($res,0,0);

			$sql = "SELECT referencia, descricao FROM tbl_produto WHERE produto = $produto";
			$res = pg_exec($con, $sql);
			$produto_referencia = pg_result($res,0,referencia);
			$produto_descricao  = pg_result($res,0,descricao);

			$email_ok = mail ("$email_gerente" , utf8_encode("Duplicação de Lista Básica") , utf8_encode("A lista básica do produto $produto_referencia - $produto_descricao acaba de ser criada a partir de uma duplicação no site TELECONTROL") , "From: Telecontrol <helpdesk@telecontrol.com.br>" , "-f helpdesk@telecontrol.com.br" );
		}
		#---------------------------------------------------

		header ("Location: $PHP_SELF");
		exit;
	}

	$referencia_duplicar = $_POST["referencia_duplicar"];
	$descricao_duplicar  = $_POST["descricao_duplicar"];
	$produto             = $_POST["produto"];
	$res = pg_exec($con,"ROLLBACK TRANSACTION");
}

if (trim($btn_acao) == "gravar") {

	echo $qtde_linhas;
	exit;

	$produto = $_POST['produto'];

	$res = pg_exec($con,"BEGIN TRANSACTION");
/*	
	$referencia = $_POST['referencia'];
	$sql = "SELECT produto FROM tbl_produto WHERE referencia = '$referencia'";
	$res = pg_exec($con, $sql);
	if (pg_numrows($res) == 0) {
		$msg_erro = "Produto $referencia não cadastrado";
	} else {
		$produto = pg_result($res,0,0);
*/		
		$sql = "DELETE FROM tbl_lista_basica
				WHERE  tbl_lista_basica.produto = $produto
				AND    tbl_lista_basica.fabrica = $login_fabrica";
		$res = pg_exec($con, $sql);
		
		for ($i = 0 ; $i < $qtde_linhas ; $i++) {
			$peca      = $_POST['peca_' . $i] ;
			$ordem     = $_POST['ordem_' . $i] ;
			$serie_inicial = $_POST['serie_inicial_' . $i] ;
			$serie_final   = $_POST['serie_final_' . $i] ;
			$posicao   = $_POST['posicao_' . $i] ;
			$descricao = $_POST['descricao_' . $i] ;
			$type      = $_POST['type_' . $i] ;
			$qtde      = $_POST['qtde_' . $i] ;
			
			$ordem = trim($ordem);
			$posicao = trim($posicao);

			$serie_inicial = trim($serie_inicial);
			$serie_inicial = str_replace(".","",$serie_inicial);
			$serie_inicial = str_replace("-","",$serie_inicial);
			$serie_inicial = str_replace("/","",$serie_inicial);
			$serie_inicial = str_replace(" ","",$serie_inicial);

			$serie_final   = trim($serie_final);
			$serie_final = str_replace(".","",$serie_final);
			$serie_final = str_replace("-","",$serie_final);
			$serie_final = str_replace("/","",$serie_final);
			$serie_final = str_replace(" ","",$serie_final);
			
			if (strlen($type) == 0) $aux_type = "'null'";
			else                    $aux_type = "'$type'";

			if (strlen($qtde) == 0) $aux_qtde = 1;
			else                    $aux_qtde = $qtde;
			
			if (strlen($ordem) == 0) $ordem = "null";
			
			if (strlen($peca) > 0) {
				$sql = "SELECT peca FROM tbl_peca WHERE referencia = '$peca' AND fabrica = $login_fabrica";
				$res = @pg_exec($con, $sql);
				if (@pg_numrows($res) == 0) {
					$msg_erro = "Peça $peca não cadastrada";
				} else {
					$peca = @pg_result($res,0,0);
					$sql = "INSERT INTO tbl_lista_basica (
								fabrica,
								produto,
								peca,
								qtde,
								posicao,
								ordem,
								serie_inicial,
								serie_final,
								type
							) VALUES (
								$login_fabrica,
								$produto      ,
								$peca,
								$aux_qtde,
								'$posicao',
								$ordem,
								'$serie_inicial',
								'$serie_final',
								'$type'
					);";
					$res = @pg_exec($con, $sql);
					$msg_erro = pg_errormessage($con);
				}
			}
		}
	//}

	if (strlen($msg_erro) == 0) {
		$res = pg_exec($con,"COMMIT TRANSACTION");

		#-------------------- Envia EMAIL ------------------
		$sql = "SELECT email_gerente FROM tbl_fabrica WHERE fabrica = $login_fabrica AND LENGTH (trim(email_gerente)) > 0";
		$res = pg_exec($con, $sql);
		if (pg_numrows($res) > 0) {
			$email_gerente = pg_result($res,0,0);

			$sql = "SELECT referencia, descricao FROM tbl_produto WHERE produto = $produto";
			$res = pg_exec($con, $sql);
			$produto_referencia = pg_result($res,0,referencia);
			$produto_descricao  = pg_result($res,0,descricao);

			$email_ok = mail ("$email_gerente" , utf8_encode("Alteração de Lista Básica") , utf8_encode("A lista básica do produto $produto_referencia - $produto_descricao acaba de ser alterada no site TELECONTROL") , "From: Telecontrol <helpdesk@telecontrol.com.br>" , "-f helpdesk@telecontrol.com.br" );
		}
		#---------------------------------------------------

		header ("Location: $PHP_SELF");
		exit;
	}

	$referencia = $_POST["referencia"];
	$descricao  = $_POST["descricao"];
	$res = pg_exec($con,"ROLLBACK TRANSACTION");
}

$apagar = $_POST["apagar"];

if (trim($btn_acao) == "apagar" and strlen($apagar) > 0 ) {
	
	$res = pg_exec($con,"BEGIN TRANSACTION");
	$sql = "DELETE FROM tbl_lista_basica
			WHERE  tbl_lista_basica.fabrica      = $login_fabrica
			AND    tbl_lista_basica.lista_basica = $apagar;";
	$res = pg_exec($con, $sql);
	$msg_erro = pg_errormessage($con);
	
	if (strlen($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec($con,"COMMIT TRANSACTION");

		#-------------------- Envia EMAIL ------------------
		$sql = "SELECT email_gerente FROM tbl_fabrica WHERE fabrica = $login_fabrica AND LENGTH (trim(email_gerente)) > 0";
		$res = pg_exec($con, $sql);
		if (pg_numrows($res) > 0) {
			$email_gerente = pg_result($res,0,0);

			$sql = "SELECT referencia, descricao FROM tbl_produto WHERE produto = $produto";
			$res = pg_exec($con, $sql);
			$produto_referencia = pg_result($res,0,referencia);
			$produto_descricao  = pg_result($res,0,descricao);

			$email_ok = mail ("$email_gerente" , utf8_encode("Item apagado da Lista Básica") , utf8_encode("Uma peça foi apagada da lista básica do produto $produto_referencia - $produto_descricao no site TELECONTROL") , "From: Telecontrol <helpdesk@telecontrol.com.br>" , "-f helpdesk@telecontrol.com.br" );
		}
		#---------------------------------------------------

	} else {
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		
		$produto   = $HTTP_POST_VARS["produto"];
		$peca      = $HTTP_POST_VARS["peca"];
		
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
	}
}

if (strlen($_POST['produto']) > 0) $produto = $_POST['produto'];
else                               $produto = $_GET["produto"];

if (strlen($_POST['referencia']) > 0) $referencia = $_POST['referencia'];
else                                  $referencia = $_GET["referencia"];


if (strlen($btn_lista) > 0) {//se o botão foi clicado
	if (strlen($produto) > 0) {
		$sql = "SELECT  tbl_produto.referencia,
						tbl_produto.descricao ,
						tbl_produto.voltagem
				FROM    tbl_produto
				JOIN    tbl_linha    ON tbl_linha.linha   = tbl_produto.linha
									AND tbl_linha.fabrica = $login_fabrica
				WHERE   tbl_produto.produto = $produto;";
		$res = pg_exec($con, $sql);
		
		if (pg_numrows($res) > 0) {
			$referencia = trim(pg_result($res,0,referencia));
			$descricao  = trim(pg_result($res,0,descricao));
			if ($login_fabrica == 1){
				$voltagem  = trim(pg_result($res,0,voltagem));
				$descricao = $descricao." ".$voltagem;
			}
		}
	}
	
	if (strlen($referencia) == 0) $msg_erro = "Preencha a referência do produto";
}

$layout_menu = "cadastro";
$title = "Cadastramento de Lista Básica";
include 'cabecalho.php';

?>

<script language='javascript'>
function fnc_pesquisa_produto (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = '';
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&lbm=1" ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.produto		= document.frm_lbm.produto;
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
		var url = '';
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
<form name="frm_lbm" method="post" action="<? echo $PHP_SELF ?>">

<? if (strlen($msg_erro) > 0) { ?>
<div class='error'><? echo $msg_erro; ?></div>
<p>
<? } ?> 

<center>
<?
#$produto    = $_POST['produto'];
#$referencia = $_POST['referencia'];
#if (strlen($referencia) == 0) $referencia = trim($_GET['referencia']);

if (strlen($produto) == 0) {
	if (strlen($referencia) > 0) {
		$sql = "SELECT produto, descricao FROM tbl_produto WHERE referencia = '$referencia'";
		$res = pg_exec($con, $sql);
		if (pg_numrows($res) == 0) {
			$msg_erro  = "Produto $referencia não cadastrado";
			$descricao = '';
			$produto   = '';
		} else {
			$descricao = pg_result($res,0,descricao);
			$produto   = pg_result($res,0,produto);
		}
	}
}
echo "<INPUT TYPE=\"hidden\" name='produto' value='$produto'>";
?>
</center>

<?
if (strlen($btn_lista) == 0)
{
?>
	<font face='arial' size='-1' color='#6699FF'><b>Para pesquisar um produto, informe parte da referência ou descrição do produto.</b></font>
	<table width='400' align='center' border='1'>
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
			<input type="text" name="descricao" value="<? echo $descricao; ?>" size="50" maxlength="50">&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' style="cursor:pointer" align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_lbm.referencia,document.frm_lbm.descricao,'descricao')">
		</td>
	</tr>
	</table>

	<input type='hidden' name='btn_lista' value=''>
	<p align='center'><img src='imagens/btn_listabasicademateriais.gif' onclick='document.frm_lbm.btn_lista.value="listar"; document.frm_lbm.submit();' style="cursor:pointer;">

	<br>

	<center>
	<?
	if (file_exists ('/www/htdocs/assist/vistas/' . $produto . '.gif')) {
		echo "<a href='vista_explodida.php?produto=$produto' target='_blank'>Clique aqui</a> para ver a vista-explodida";
	} else {
		echo "Produto sem vista explodida";
	}
	?>
	</center>
<?
} else {
?>
	<br>
	<table width='400' align='center' border='1'>
		<tr>
			<td align='center'><b>Referência</b></td>
			<td align='center'><b>Descrição</b></td>
		</tr>
		<tr>
			<td align='center'><? echo $referencia ?></td>
			<td align='center'><? echo $descricao ?></td>
		</tr>
	</table>
<BR>
	<center>
	<?
	if (file_exists ('/www/htdocs/assist/vistas/' . $produto . '.gif')) {
		echo "<a href='vista_explodida.php?produto=$produto' target='_blank'>Clique aqui</a> para ver a vista explodida";
	} else {
		echo "Produto sem vista explodida";
	}
	?>
	</center>
<BR>

	<p align="center"><a href='<?echo $PHP_SELF?>?'>Clique aqui para pesquisar outro produto</a></p>

<?
}
?>
<!-- 
<p align='center'><input type='submit' name='btn_lista' style="cursor:pointer" value='Lista Básica de Materiais'>
 -->
<br>

<?
if (strlen($btn_lista) > 0 OR strlen($produto) > 0) {
	$referencia = trim($_POST['referencia']);
	if (strlen($referencia) == 0) $referencia = trim($_GET['referencia']);

	if (strlen($produto) > 0) {
		
		$sql = "SELECT      tbl_lista_basica.lista_basica  ,
							tbl_lista_basica.posicao       ,
							tbl_lista_basica.ordem         ,
							tbl_lista_basica.serie_inicial ,
							tbl_lista_basica.serie_final   ,
							tbl_lista_basica.qtde          ,
							tbl_lista_basica.type          ,
							tbl_peca.referencia            ,
							tbl_peca.descricao
					FROM    tbl_lista_basica
					JOIN    tbl_peca USING (peca)
					WHERE   tbl_lista_basica.fabrica = $login_fabrica
					AND     tbl_lista_basica.produto = $produto
					ORDER BY tbl_peca.referencia, tbl_peca.descricao";
//					ORDER BY lpad(tbl_lista_basica.posicao,20,''), tbl_peca.descricao";

		$sql = "SELECT      tbl_lista_basica.lista_basica  ,
							tbl_lista_basica.ordem         ,
							tbl_lista_basica.posicao       ,
							tbl_lista_basica.serie_inicial ,
							tbl_lista_basica.serie_final   ,
							tbl_lista_basica.qtde          ,
							tbl_lista_basica.type          ,
							tbl_peca.referencia            ,
							tbl_peca.descricao
					FROM    tbl_lista_basica
					JOIN    tbl_peca USING (peca)
					WHERE   tbl_lista_basica.fabrica = $login_fabrica
					AND     tbl_lista_basica.produto = $produto ";

		$order_by = trim($_GET['ordem']);

		if (strlen($order_by) == 0) {
			if ($login_fabrica == 1) $sql .= "ORDER BY tbl_lista_basica.type, tbl_lista_basica.ordem";
			else 					 $sql .= "ORDER BY tbl_peca.referencia, tbl_peca.descricao";
		} else {
			switch ($order_by){
				case 'referencia':	$sql .= "ORDER BY tbl_peca.referencia";	break;
				case 'descricao':	$sql .= "ORDER BY tbl_peca.descricao";	break;
				case 'posicao':		$sql .= "ORDER BY tbl_lista_basica.posicao";	break;
				case 'qtde':		$sql .= "ORDER BY tbl_lista_basica.qtde";		break;
				case 'ordem':		$sql .= "ORDER BY tbl_lista_basica.ordem";		break;
			}
		}
		$res = pg_exec($con, $sql);
//if ($ip == '201.0.9.216') echo $sql;

		echo "<input type='hidden' name='btn_acao' value=''>";
		echo "<input type='hidden' name='apagar' value=''>";
		echo "<input type='hidden' name='duplicar' value=''>";
		
		echo "<table width='300' align='center' border='0'>";
		echo "<tr bgcolor='#FFFFFF'>";
		echo "<td align='center' bgcolor='#91C8FF'>&nbsp;&nbsp;</td>";
		echo "<td align='left'><b>Peça Alternativa</b></td>";
		echo "<td align='center' bgcolor='#00B95C'>&nbsp;&nbsp;</td>";
		echo "<td align='left'><b>De-Para</b></td>";
		echo "</tr>";
		echo "</table>";

		// Verifica se o resultado da lista básica é maior que a qtde padrão
		echo $qtde_linhas . " / " . pg_numrows($res);
		if (pg_numrows($res) > $qtde_linhas) {
			$qtde_linhas = pg_numrows($res) + 50;
			echo "<input type='hidden' name='qtde_linhas' value='$qtde_linhas'>";
		}

		for ($i = 0 ; $i < $qtde_linhas ; $i++) {
			if ($i % 20 == 0) {
				if ($i > 0) echo "</table>";
				
				if ($i > 1) {
					echo "<p align='center'><img src='imagens_admin/btn_gravar.gif' onclick='document.frm_lbm.btn_acao.value = \"gravar\" ; document.frm_lbm.submit()' style='cursor:pointer;'>";
				
					echo "<p>";
				}
				
				echo "<table width='400' align='center' border='0'>";
				echo "<tr bgcolor='#cccccc'>";
				echo "<td align='center'><a href='$PHP_SELF?referencia=$referencia&ordem=ordem'><b>Ordem</b></a></td>";
				if ($login_fabrica == 6) {
					echo "<td align='center'><b>Série IN</b></td>";
					echo "<td align='center'><b>Série OUT</b></td>";
				}
				echo "<td align='center'><a href='$PHP_SELF?referencia=$referencia&ordem=posicao'><b>Posição</b></a></td>";
				echo "<td align='center'><a href='$PHP_SELF?referencia=$referencia&ordem=referencia'><b>Peça</b></a></td>";
				echo "<td align='center'><a href='$PHP_SELF?referencia=$referencia&ordem=descricao'><b>Descrição</b></a></td>";
				if ($login_fabrica == 1) {
					echo "<td align='center'><b>Type</b></td>";
				}
				echo "<td align='center'><a href='$PHP_SELF?referencia=$referencia&ordem=qtde'><b>Qtde</b></a></td>";
				echo "</tr>";
			}
			
			$peca = "peca_$i" ;
			$peca = $$peca;
			
			$ordem = "ordem_$i" ;
			$ordem = $$ordem;
			
			$serie_inicial = "serie_inicial_$i" ;
			$serie_inicial = $$serie_inicial;
			
			$serie_final = "serie_final_$i" ;
			$serie_final = $$serie_final;
			
			$posicao = "posicao_$i" ;
			$posicao = $$posicao;
			
			$descricao = "descricao_$i" ;
			$descricao = $$descricao;
			
			$type = "type_$i" ;
			$type = $$type;

			$qtde = "qtde_$i" ;
			$qtde = $$qtde;
			
			if (strlen($btn_lista) > 0) {
				$ordem = '';
				$posicao = '';
				$peca = '';
				$descricao = '';
				$type = '';
				$qtde = '';
			}
			
			if ($i < pg_numrows($res) AND strlen($msg_erro) == 0) {
				$cor       = "#FFFFFF";
				
				$lbm           = pg_result($res,$i,lista_basica);
				$ordem         = pg_result($res,$i,ordem);
				$posicao       = pg_result($res,$i,posicao);
				$serie_inicial = pg_result($res,$i,serie_inicial);
				$serie_final   = pg_result($res,$i,serie_final);
				$peca          = pg_result($res,$i,referencia);
				$descricao     = pg_result($res,$i,descricao);
				$type          = pg_result($res,$i,type);
				$qtde          = pg_result($res,$i,qtde);
				
				$sql = "SELECT  tbl_peca_alternativa.para
						FROM    tbl_peca_alternativa
						WHERE   tbl_peca_alternativa.para    = '$peca'
						AND     tbl_peca_alternativa.fabrica = $login_fabrica;";
				$res1 = pg_exec($con, $sql);
				
				if (pg_numrows($res1) > 0) $cor = "#91C8FF";
				
				$sql = "SELECT  tbl_depara.para
						FROM    tbl_depara
						WHERE   tbl_depara.para    = '$peca'
						AND     tbl_depara.fabrica = $login_fabrica;";
				$res1 = pg_exec($con, $sql);
				
				if (pg_numrows($res1) > 0) $cor = "#00B95C";
			}
			
			echo "<tr>";
			
			echo "<td bgcolor='$cor'>";
			echo "<input type='text' name='ordem_$i' value='$ordem' size='3' maxlength='3'><br>&nbsp;";
			echo "</td>";

			if ($login_fabrica == 6) {
				echo "<td bgcolor='$cor'>";
				echo "<input type='text' name='serie_inicial_$i' value='$serie_inicial' size='10' maxlength='20'><br>&nbsp;";
				echo "</td>";

				echo "<td bgcolor='$cor'>";
				echo "<input type='text' name='serie_final_$i' value='$serie_final' size='10' maxlength='20'><br>&nbsp;";
				echo "</td>";
			}

			echo "<td bgcolor='$cor'>";
			echo "<input type='text' name='posicao_$i' value='$posicao' size='5' maxlength='10'><br>&nbsp;";
			echo "</td>";
			
			echo "<td bgcolor='$cor'>";
			echo "<input type='text' name='peca_$i' value='$peca' size='20' maxlength='20'>";
			echo "&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca (document.frm_lbm.peca_$i , document.frm_lbm.descricao_$i , \"referencia\")' style='cursor:pointer'><br>&nbsp;";
			echo "<font size='-3' color='#ffffff'>$peca</font>";
			echo "</td>";
			
			echo "<td bgcolor='$cor'>";
			echo "<input type='text' name='descricao_$i' value='$descricao' size='50' maxlength='50'>";
			echo "&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca (document.frm_lbm.peca_$i , document.frm_lbm.descricao_$i , \"descricao\")' style='cursor:pointer'><br>&nbsp;";
			echo "<font size='-3' color='#ffffff'>$descricao</font>";
			echo "</td>";
			
			if ($login_fabrica == 1) {
	                    GeraComboType::makeComboType($parametrosAdicionaisObject, $type, null, array("index"=>$i));

				echo "<td bgcolor='$cor' valign='top'>";
	                        echo GeraComboType::getElement();
	                        echo "<br>.&nbsp;";
				echo "</td>";
			}
			
			echo "<td bgcolor='#FFFFFF'>";
			echo "<input type='text' name='qtde_$i' value='$qtde' size='5' maxlength=''>";
			echo "&nbsp;<img src='../imagens/btn_apaga_15.gif' alt='Clique aqui para apagar este item da lista básica.' onclick='document.frm_lbm.btn_acao.value = \"apagar\" ; document.frm_lbm.apagar.value = \"$lbm\" ; document.frm_lbm.submit()' style='cursor:pointer'><br>&nbsp;";
			echo "</td>";
			
			echo "</tr>";
		}?>
	</table>

	<p align='center'><img src='imagens_admin/btn_gravar.gif' onclick='document.frm_lbm.btn_acao.value = "gravar" ; document.frm_lbm.submit()'>
	<p>
	<a href="javascript: if (confirm ('Deseja realmente excluir todos os itens desta Lista Básica ?') == true ) { window.location = '<? echo $PHP_SELF . '?produto=$produto&acao=excluir' ?>' }" >Excluir esta Lista Básica</a>

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
		<!--
		<td align='center'>
			<b>Qtde</b>
		</td>
		-->
	</tr>

	<tr>
		<td align='center'>
			<input type="text" name="referencia_duplicar" value="<? echo $referencia_duplicar ?>" size="15" maxlength="20">&nbsp;<img src='../imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_lbm.referencia_duplicar,document.frm_lbm.descricao_duplicar,'referencia')">
		</td>
		<td align='center'>
			<input type="text" name="descricao_duplicar" value="<? echo $descricao_duplicar ?>" size="50" maxlength="50">&nbsp;<img src='../imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_lbm.referencia_duplicar,document.frm_lbm.descricao_duplicar,'descricao')">
		</td>
		<!--
		<td align='center'>
			<input type="text" name="qtde" value="<? echo $qtde ?>" size="5" maxlength="">
		</td>
		-->
	</tr>
	</table>
	<p align='center'><img src='imagens_admin/btn_duplicar.gif' style="cursor:pointer" onclick='document.frm_lbm.btn_acao.value = "duplicar" ; document.frm_lbm.submit()'><?php

	}

}?>

</form>
</div><?php

include "rodape.php";?>

</body>
</html>
