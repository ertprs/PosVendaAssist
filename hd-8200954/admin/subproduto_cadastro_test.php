<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "cadastros";
include 'autentica_admin.php';
include 'funcoes.php';


# Pesquisa pelo AutoComplete AJAX
$q = trim($_GET["q"]);
if (isset($_GET["q"])){
	$busca      = $_GET["busca"];
	$tipo_busca = $_GET["tipo_busca"];
	if (strlen($q)>2){
		if ($tipo_busca=="produto"){
			$sql = "SELECT tbl_produto.produto,
							tbl_produto.referencia,
							tbl_produto.descricao
					FROM tbl_produto
					JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
					WHERE tbl_linha.fabrica = $login_fabrica ";
			
			if ($busca == "codigo"){
				$sql .= " AND UPPER(tbl_produto.referencia) like UPPER('%$q%') ";
			}else{
				$sql .= " AND UPPER(tbl_produto.descricao) like UPPER('%$q%') ";
			}
			
			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$produto    = trim(pg_result($res,$i,produto));
					$referencia = trim(pg_result($res,$i,referencia));
					$descricao  = trim(pg_result($res,$i,descricao));
					echo "$produto|$descricao|$referencia";
					echo "\n";
				}
			}
		}
	}
	exit;
}


$msg_erro = "";

if (strlen($_GET["produto"]) > 0) $produto = trim($_GET["produto"]);
if (strlen($_POST["produto"]) > 0) $produto = trim($_POST["produto"]);

if (strlen($_GET["subproduto"]) > 0) $subproduto = trim($_GET["subproduto"]);
if (strlen($_POST["subproduto"]) > 0) $subproduto = trim($_POST["subproduto"]);

if (strlen($_POST["btnacao"]) > 0) {
	$btnacao = trim($_POST["btnacao"]);
}



	if ($btnacao == "deletar" and strlen($subproduto) > 0 ) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		$sql = "DELETE FROM tbl_subproduto
				WHERE  tbl_subproduto.produto_pai = tbl_produto.produto
				AND    tbl_produto.linha          = tbl_linha.linha
				AND    tbl_linha.fabrica          = $login_fabrica
				AND    tbl_subproduto.subproduto  = $subproduto;";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (strlen ($msg_erro) == 0) {
			###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
			$res = pg_exec ($con,"COMMIT TRANSACTION");

			header ("Location: $PHP_SELF");
			exit;
		}else{
			###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS

			$produto_pai      = $_POST["produto_pai"];
			$descricao_pai    = $_POST["descricao_pai"];
			$referencia_pai   = $_POST["referencia_pai"];
			$produto_filho    = $_POST["produto_filho"];
			$descricao_filho  = $_POST["descricao_pai"];
			$referencia_filho = $_POST["referencia_filho"];

			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}

	if ($btnacao == "gravar") {

		if (strlen($_POST["produto_pai"]) > 0) {
			$aux_produto_pai = "'". trim($_POST["produto_pai"]) ."'";
		}else{
			$msg_erro .= " Digite o Produto Pai.";
		}

		if (strlen($msg_erro) == 0) {
			if (strlen($_POST["produto_filho"]) > 0) {
				$aux_produto_filho = "'". trim($_POST["produto_filho"]) ."'";
			}else{
				$msg_erro .= " Digite o Produto Filho.";
			}
		}

		if(strlen($msg_erro) == 0){
			if($aux_produto_pai == $aux_produto_filho){
				$msg_erro = "Um Produto não Pode ser Subproduto Dele Próprio.";
			}
		}

		if (strlen($msg_erro) == 0) {
			$res = pg_exec($con,"BEGIN TRANSACTION");
		
			if (strlen($subproduto) == 0) {
				###INSERE NOVO REGISTRO
				$sql =	"INSERT INTO tbl_subproduto (
							produto_pai  ,
							produto_filho
						) VALUES (
							$aux_produto_pai,
							$aux_produto_filho
						);";
			}else{
				###ALTERA REGISTRO
				$sql =	"UPDATE tbl_subproduto SET
								produto_pai   = $aux_produto_pai,
								produto_filho = $aux_produto_filho
						WHERE   tbl_subproduto.produto_pai = tbl_produto.produto
						AND     tbl_produto.linha          = tbl_linha.linha
						AND     tbl_linha.fabrica          = $login_fabrica
						AND     tbl_subproduto.subproduto  = $subproduto;";
			}
			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
			if(strlen($msg_erro)>0){
				$msg_erro = "Este produto já está cadastrado!";
			}
			if (strlen($msg_erro) == 0) {
				###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
				$res = pg_exec ($con,"COMMIT TRANSACTION");

				header ("Location: $PHP_SELF?msg=Gravado com Sucesso!");
				exit;
			}else{
				###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS

				$produto_pai      = $_POST["produto_pai"];
				$descricao_pai    = $_POST["descricao_pai"];
				$referencia_pai   = $_POST["referencia_pai"];
				$produto_filho    = $_POST["produto_filho"];
				$descricao_filho  = $_POST["descricao_pai"];
				$referencia_filho = $_POST["referencia_filho"];

				if (strpos ($msg_erro,"duplicate key violates unique constraint \"tbl_subproduto_unico\"") > 0)
					$msg_erro = "Subconjunto já cadastrado para este produto.";

				$res = pg_exec ($con,"ROLLBACK TRANSACTION");
			}
		} // Fim if msg_erro
	}


###CARREGA REGISTRO
if (strlen($subproduto) > 0) {
	$sql = "SELECT  tbl_subproduto.produto_pai  ,
					tbl_subproduto.produto_filho
			FROM    tbl_subproduto
			JOIN    tbl_produto ON tbl_produto.produto = tbl_subproduto.produto_pai
			JOIN    tbl_linha   ON tbl_linha.linha = tbl_produto.linha
			WHERE   tbl_linha.fabrica         = $login_fabrica
			AND     tbl_subproduto.subproduto = $subproduto;";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$produto_pai   = trim(pg_result($res,0,produto_pai));
		$produto_filho = trim(pg_result($res,0,produto_filho));
		
		$sql = "SELECT  tbl_produto.referencia AS referencia_pai,
						tbl_produto.descricao  AS descricao_pai
				FROM    tbl_subproduto
				JOIN    tbl_produto ON tbl_produto.produto = tbl_subproduto.produto_pai
				JOIN    tbl_linha   ON tbl_linha.linha = tbl_produto.linha
				WHERE   tbl_linha.fabrica          = $login_fabrica
				AND     tbl_subproduto.produto_pai = $produto_pai;";
		$res = pg_exec ($con,$sql);
		
		if (pg_numrows($res) > 0) {
			$referencia_pai = trim(pg_result($res,0,referencia_pai));
			$descricao_pai  = trim(pg_result($res,0,descricao_pai));
		}
		
		$sql = "SELECT  tbl_produto.referencia AS referencia_filho,
						tbl_produto.descricao  AS descricao_filho
				FROM    tbl_subproduto
				JOIN    tbl_produto ON tbl_produto.produto = tbl_subproduto.produto_filho
				JOIN    tbl_linha   ON tbl_linha.linha = tbl_produto.linha
				WHERE   tbl_linha.fabrica            = $login_fabrica
				AND     tbl_subproduto.produto_filho = $produto_filho;";
		$res = pg_exec ($con,$sql);
		
		if (pg_numrows($res) > 0) {
			$referencia_filho = trim(pg_result($res,0,referencia_filho));
			$descricao_filho  = trim(pg_result($res,0,descricao_filho));
		}
	}
}

$layout_menu = 'cadastro';
$title = "CADASTRAMENTO DE SUBCONJUNTO";
include 'cabecalho.php';

$msg = $_GET['msg'];
?>

<style type='text/css'>


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
	background-color:green;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
}
</style>

<script language="JavaScript">
function fnc_pesquisa_subproduto (campo, tipo, controle) {
	
	if (campo.length >= 3) {
		var url = "";
		url = "subproduto_pesquisa_test.php?controle=" + controle + "&campo=" + campo + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.referencia= document.frm_subproduto.referencia;
		janela.descricao = document.frm_subproduto.descricao;
		janela.focus();
	}

	else{
		alert("O campo "+tipo+" deve conter pelo menos 3 letras.");
	}
}
</script>


<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#expira').datePicker();
		$("#expira").maskedinput("99/99/9999");
	});
</script>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}

	/* DE */
	/* Busca por Produto */
	$("#referencia_pai").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#referencia_pai").result(function(event, data, formatted) {
		$("#produto_pai").val(data[0]) ;
		$("#descricao_pai").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#descricao_pai").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#descricao_pai").result(function(event, data, formatted) {
		$("#produto_pai").val(data[0]) ;
		$("#referencia_pai").val(data[2]) ;
	});


	/*  PARA  */
	/* Busca por Produto */
	$("#referencia_filho").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#referencia_filho").result(function(event, data, formatted) {
		$("#produto_filho").val(data[0]) ;
		$("#descricao_filho").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#descricao_filho").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#descricao_filho").result(function(event, data, formatted) {
		$("#produto_filho").val(data[0]) ;
		$("#referencia_filho").val(data[2]) ;
	});

});
</script>

<div id="wrapper">
<form name="frm_subproduto" method="post" action="<? $PHP_SELF ?>">
<input type="hidden" name="subproduto"	value="<? echo $subproduto ?>">
<input type="hidden" id="produto_pai"	name="produto_pai"	 value="<? echo $produto_pai ?>">
<input type="hidden" id="produto_filho"	name="produto_filho" value="<? echo $produto_filho ?>">


<table border="0"  align='center' cellpadding="2" cellspacing="1" class="formulario" width="700">
	<? if (strlen($msg_erro) > 0){?>
		<tr class="msg_erro"><td colspan="4"><? echo $msg_erro; ?></td></tr>
	<? } ?>

	<? if (strlen($msg) > 0){?>
		<tr class="sucesso"><td colspan="4"><? echo $msg; ?></td></tr>
	<? } ?>

	<tr class="titulo_tabela"><td colspan="3">Cadastro de Subconjunto</td></tr>
	<tr>
		<td width="70">&nbsp;</td>
		<td align="left">Produto Pai (*)</td>
		<td align="left">Descrição (*)</td>
	</tr>
	<tr>
		<td width="70">&nbsp;</td>
		<td align="left">
			<input type="text" class="frm" name="referencia_pai" id="referencia_pai" value="<? echo $referencia_pai ?>" size="20" maxlength="20">
			<img src="imagens/lupa.png" onclick="javascript: fnc_pesquisa_subproduto (document.frm_subproduto.referencia_pai.value, 'referencia', 'pai')" border="0" style="cursor:pointer;">
		</td>
		<td align="left">
			<input type="text" class="frm" name="descricao_pai" id="descricao_pai" value="<? echo $descricao_pai ?>" size="50" maxlength="50">
			<img src="imagens/lupa.png" onclick="javascript: fnc_pesquisa_subproduto (document.frm_subproduto.descricao_pai.value, 'descricao', 'pai')" border="0" style="cursor:pointer;">
		</td>
	</tr>
</table>

<table border="0"  align='center' cellpadding="2" cellspacing="1" class="formulario" width="700">
	<tr>
		<td width="70">&nbsp;</td>
		<td align="left">Produto Filho (*)</td>
		<td align="left">Descrição (*)</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td align="left">
			<input type="text" class="frm" name="referencia_filho" id="referencia_filho" value="<? echo $referencia_filho ?>" size="20" maxlength="20">
			<img src="imagens/lupa.png" onclick="javascript: fnc_pesquisa_subproduto (document.frm_subproduto.referencia_filho.value, 'referencia', 'filho')" border="0" style="cursor:pointer;">
		</td>
		<td align="left">
			<input type="text" class="frm" name="descricao_filho" id="descricao_filho" value="<? echo $descricao_filho ?>" size="50" maxlength="50">
			<img src="imagens/lupa.png" onclick="javascript: fnc_pesquisa_subproduto (document.frm_subproduto.descricao_filho.value, 'descricao', 'filho')" border="0" style="cursor:pointer;">
		</td>
	</tr>
	<tr><td colspan="3">&nbsp;</td></tr>
	<tr>
		<td colspan="3">
			<input type='hidden' name='btnacao' value=''>
			<input type="button" style="background:url(imagens_admin/btn_gravar.gif); width:75px; cursor:pointer;" value="&nbsp;" ONCLICK="javascript: if (document.frm_subproduto.btnacao.value == '' ) { document.frm_subproduto.btnacao.value='gravar' ; document.frm_subproduto.submit() } else { alert ('Aguarde submissão') } return false;" ALT="Gravar formulário" border='0' >
			<input type="button" style="background:url(imagens_admin/btn_apagar.gif); width:75px; cursor:pointer;" value="&nbsp;" ONCLICK="javascript: if (document.frm_subproduto.btnacao.value == '' ) { document.frm_subproduto.btnacao.value='deletar' ; document.frm_subproduto.submit() } else { alert ('Aguarde submissão') } return false;" ALT="Apagar produto" border='0' >
			<input type="button" style="background:url(imagens_admin/btn_limpar.gif); width:75px; cursor:pointer;" value="&nbsp;" ONCLICK="javascript: window.location='<? echo $PHP_SELF ?>'; return false;" ALT="Limpar campos" border='0' >
		</td>
	</tr>
</table>

<br>

<?
if (strlen($produto) == 0) {

	$sql = "SELECT  DISTINCT
					tbl_subproduto.produto_pai,
					tbl_produto.referencia,
					tbl_produto.descricao
			FROM    tbl_subproduto
			JOIN    tbl_produto ON tbl_produto.produto = tbl_subproduto.produto_pai
			JOIN    tbl_linha   ON tbl_linha.linha     = tbl_produto.linha
			WHERE   tbl_linha.fabrica = $login_fabrica
			ORDER BY tbl_produto.descricao;";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<hr>";
		echo "<h2>Clique no Produto para listar os Sub-Produtos</h2>";
		echo "<table width='700' border='0' align='center' class='conteudo' cellpadding='2' cellspacing='1'>\n";
		echo "<tr class='titulo_tabela'>";
		echo "<td colspan='2'>Relação dos Produtos</td>";
		echo "</tr>";
		echo "<tr class='titulo_coluna'>\n";
		echo "<td nowrap>Referência</td>\n";
		echo "<td nowrap>Descrição</td>\n";
		echo "</tr>\n";
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$produto    = trim(pg_result($res,$i,produto_pai));
			$referencia = trim(pg_result($res,$i,referencia));
			$descricao  = trim(pg_result($res,$i,descricao));

			$cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

			echo "<tr bgcolor='$cor'>\n";
			echo "<td align='left'><a href='$PHP_SELF?produto=$produto'>$referencia</a></td>\n";
			echo "<td align='left'><a href='$PHP_SELF?produto=$produto'>$descricao</a></td>\n";
			echo "</tr>\n";
		}
		echo "</table>";
	}
}else{
	$sql =	"SELECT x.subproduto                                    ,
					x.referencia           AS subproduto_referencia ,
					x.descricao            AS subproduto_descricao  ,
					x.produto_pai          AS produto               ,
					x.ativo                                         ,
					tbl_produto.referencia AS produto_referencia    ,
					tbl_produto.descricao  AS produto_descricao     
			FROM (
				SELECT tbl_subproduto.subproduto  ,
						tbl_subproduto.produto_pai ,
						tbl_produto.referencia     ,
						tbl_produto.descricao      ,
						tbl_produto.ativo          
				FROM    tbl_subproduto
				JOIN    tbl_produto ON tbl_produto.produto = tbl_subproduto.produto_filho
				JOIN    tbl_linha   ON tbl_linha.linha     = tbl_produto.linha
				WHERE   tbl_linha.fabrica          = $login_fabrica
				AND     tbl_subproduto.produto_pai = $produto
				ORDER BY tbl_produto.descricao
			) AS x
			JOIN tbl_produto ON tbl_produto.produto = x.produto_pai;";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<hr>";
		echo "<h1>.:: Relação dos Sub-Produtos ::.</h1>";
		echo "<h2>Clique no Sub-Produto para efetuar alterações</h2>";
		echo "<table width='700' border='0' align='center' class='conteudo' cellpadding='2' cellspacing='1'>\n";
		echo "<tr class='titulo_tabela'>\n";
		echo "<td colspan='3' nowrap> Produto: <i>".trim(pg_result($res,0,produto_referencia))." - ".trim(pg_result($res,0,produto_descricao))."</i></td>\n";
		echo "</tr>\n";
		echo "<tr class='titulo_coluna'>\n";
		echo "<td nowrap>Referência</td>\n";
		echo "<td nowrap>Descrição</td>\n";
		echo "<td nowrap>Status</td>\n";
		echo "</tr>\n";
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$subproduto    = trim(pg_result($res,$i,subproduto));
			$referencia = trim(pg_result($res,$i,subproduto_referencia));
			$descricao  = trim(pg_result($res,$i,subproduto_descricao));
			$ativo      = trim(pg_result($res,$i,ativo));

			if($ativo=='t') $ativo = "<img src='imagens/status_verde.gif'> Ativo";
			else            $ativo = "<img src='imagens/status_vermelho.gif'> Inativo";

			$cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

			echo "<tr bgcolor='$cor'>\n";
			echo "<td nowrap><a href='$PHP_SELF?produto=$produto&subproduto=$subproduto'>$referencia</a></td>\n";
			echo "<td nowrap><a href='$PHP_SELF?produto=$produto&subproduto=$subproduto'>$descricao</a></td>\n";
			echo "<td nowrap>$ativo</td>\n";
			echo "</tr>\n";
		}
		echo "</table>";
		echo "<br>";
		echo "<a href='$PHP_SELF'><img border='0' src='imagens_admin/btn_voltar.gif'></a>";
	}
}
/*
$sql = "SELECT  DISTINCT
				tbl_subproduto.produto_pai
		FROM    tbl_subproduto
		JOIN    tbl_produto ON tbl_produto.produto = tbl_subproduto.produto_pai
		JOIN    tbl_linha   ON tbl_linha.linha     = tbl_produto.linha
		WHERE   tbl_linha.fabrica = $login_fabrica
		ORDER BY tbl_subproduto.produto_pai;";
$res = pg_exec ($con,$sql);

for ($x = 0 ; $x < pg_numrows($res) ; $x++){
	$div = false;

	$produto        = trim(pg_result($res,$x,produto_pai));

	$sql = "SELECT  tbl_subproduto.subproduto ,
					tbl_subproduto.produto_pai,
					tbl_produto.referencia    ,
					tbl_produto.descricao
			FROM    tbl_subproduto
			JOIN    tbl_produto ON tbl_produto.produto = tbl_subproduto.produto_filho
			JOIN    tbl_linha   ON tbl_linha.linha     = tbl_produto.linha
			WHERE   tbl_linha.fabrica          = $login_fabrica
			AND     tbl_subproduto.produto_pai = $produto
			ORDER BY tbl_produto.descricao;";
	$res0 = pg_exec ($con,$sql);

	if (pg_numrows($res0) > 0) {
		$div = true;
	}

	for ($y = 0 ; $y < pg_numrows($res0) ; $y++){
		$subproduto  = trim(pg_result($res0,$y,subproduto));
		$produto_pai = trim(pg_result($res0,$y,produto_pai));
		$referencia  = trim(pg_result($res0,$y,referencia));
		$descricao   = trim(pg_result($res0,$y,descricao));

		$sql = "SELECT  tbl_produto.referencia,
						tbl_produto.descricao
				FROM    tbl_produto
				JOIN    tbl_linha ON tbl_linha.linha = tbl_produto.linha
				WHERE   tbl_linha.fabrica   = $login_fabrica
				AND     tbl_produto.produto = $produto_pai
				ORDER BY tbl_linha.linha;";
		$res1 = pg_exec($con,$sql);

		if (pg_numrows($res1) > 0) {
			$nome = trim(pg_result($res1,0,referencia))." - ".trim(pg_result($res1,0,descricao));
		}

		$cor = ($y % 2 == 0) ? "#FFFFFF" : "#F1F4FA";

		if ($produto_pai <> $produto_pai_anterior) {
			echo "<table width='400' border='0' class='conteudo' cellpadding='2' cellspacing='1'>\n";
			echo "<tr bgcolor='#D9E2EF'>\n";
			echo "<td colspan='2' nowrap>$nome</td>\n";
			echo "</tr>\n";
			$quebra = true;
		}else{
			$quebra = false;
			echo "<tr bgcolor='$cor'>\n";
			echo "<td width='15%' nowrap>$referencia</td>";
			echo "<td width='85%' align='left' nowrap><a href='$PHP_SELF?subproduto=$subproduto'>$descricao</a></td>\n";
			echo "</tr>\n";
		}

		if ($quebra == true) {
			echo "<tr bgcolor='$cor'>\n";
			echo "<td width='15%' nowrap>$referencia</td>";
			echo "<td width='85%' align='left' nowrap><a href='$PHP_SELF?subproduto=$subproduto'>$descricao</a></td>\n";
			echo "</tr>\n";
		}

		$produto_pai_anterior = trim(pg_result($res0,$y,produto_pai));
	}
	echo "</table>\n\n";
	echo "<br>\n\n";
}
*/
?>
</form>
</div>

<? include "rodape.php"; ?>

</body>
</html>