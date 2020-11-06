<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

if (strlen($_GET["tipo_posto"]) > 0) {
	$tipo_posto = trim($_GET["tipo_posto"]);//dados pelo browser, trim -> tira os espaços
}

if (strlen($_POST["tipo_posto"]) > 0) {//dados do campo formulario
	$tipo_posto = trim($_POST["tipo_posto"]);
}

if (strlen($_POST["btnacao"]) > 0) {
	$btnacao = trim($_POST["btnacao"]);
}


if ($btnacao == "deletar") {
	$res = pg_exec ($con,"BEGIN TRANSACTION");
	
	$sql = "DELETE FROM tbl_tipo_posto
			WHERE  fabrica = $login_fabrica
			AND    tipo_posto = $tipo_posto";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	
	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		
		header ("Location: $PHP_SELF");
		exit;
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		$acrescimo_tabela_base = $_POST["acrescimo_tabela_base"];
		$tipo_posto =  $_POST["tipo_posto"];
		$codigo      = $_POST["codigo"];
		$descricao   = $_POST["descricao"];
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if ($btnacao == "gravar") {
	
	if (strlen($msg_erro) == 0) {

		if (strlen($_POST["descricao"]) > 0) {
			$aux_descricao = "'". trim($_POST["descricao"]) ."'";
			$aux_descricao = str_replace('"', "'", $aux_descricao);
		}else{
			$msg_erro = "Favor informar a descrição do Tipo de Posto";
		}
	}

	$aux_codigo = trim ($_POST ['codigo']);
	if (strlen ($aux_codigo) == 0) {
		$aux_codigo = "null";
	}else{
		$aux_codigo = "'" . $aux_codigo . "'";
	}

	$aux_acrescimo_tabela_base = trim ($_POST ['acrescimo_tabela_base']);
	if (strlen ($aux_acrescimo_tabela_base) == 0) {
		$aux_acrescimo_tabela_base = "null";
	}else{
		$xacrescimo_tabela_base = $aux_acrescimo_tabela_base / 100;
		$xacrescimo_tabela_base = $xacrescimo_tabela_base + 1;
		$aux_acrescimo_tabela_base = "'" . $xacrescimo_tabela_base . "'";
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		
		if (strlen($tipo_posto) == 0){
			###INSERE NOVO REGISTRO
			$sql = "INSERT INTO tbl_tipo_posto (
						fabrica,
						codigo,
						descricao,
						acrescimo_tabela_base
					) VALUES (
						$login_fabrica,
						$aux_codigo   ,
						$aux_descricao,
						$aux_acrescimo_tabela_base
					)";
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}else{
			###ALTERA REGISTRO
			$sql = "UPDATE tbl_tipo_posto SET
							codigo                =  $aux_codigo,
							descricao             =  $aux_descricao,
							acrescimo_tabela_base =  $aux_acrescimo_tabela_base
					WHERE  fabrica     = $login_fabrica
					AND    tipo_posto =  $tipo_posto";
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
	}
	
	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		
		header ("Location: $PHP_SELF");
		exit;
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		$tipo_posto				= $_POST["tipo_posto"];
		$codigo					= $_POST["codigo"];
		$descricao				= $_POST["descricao"];
		$acrescimo_tabela_base	= $_POST["acrescimo_tabela_base"];
		
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

###CARREGA REGISTRO
if (strlen($tipo_posto) > 0 AND strlen ($msg_erro) == 0) {
	$sql = "SELECT  codigo               ,
					descricao            ,
					acrescimo_tabela_base
			FROM    tbl_tipo_posto
			WHERE   fabrica    = $login_fabrica
			AND     tipo_posto = $tipo_posto;";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$codigo					= trim(pg_result($res,0,codigo));
		$descricao				= trim(pg_result($res,0,descricao));
		$acrescimo_tabela_base	= trim(pg_result($res,0,acrescimo_tabela_base));
	}
}
?>

<?
	$layout_menu = "cadastro";
	$title = "Cadastramento de Tipo de Postos";
	include 'cabecalho.php';
?>

<p>
<div id='wrapper'>
	<form name="frm_tipoposto" method="post" action="<? $PHP_SELF ?>">
		<input type="hidden" name="tipo_posto" value="<? echo $tipo_posto ?>">

<? if (strlen($msg_erro) > 0) { ?>

		<div id="wrapper">
			<b><? 
			if (strpos ($msg_erro,'duplicate key') > 0) $msg_erro = "Código duplicado não permitido";
			echo $msg_erro; 
			?></b>
		</div>

<? } ?>

		<div id="wrapper">
			<div id="middleCol" style="width: 205px; ">
				<b>Descrição</b>
			</div>
			<div id="middleCol" style="width: 100px; ">
				<b>Código</b>
			</div>

			<?
			$sql = "SELECT acrescimo_tabela_base
					FROM   tbl_fabrica
					WHERE  fabrica = $login_fabrica";
			$res = pg_exec($con,$sql);
			$acrescimo_tabela_base_t = pg_result($res,0,acrescimo_tabela_base);

			//if (pg_result($res,0,acrescimo_tabela_base) == 't'){
			if ($acrescimo_tabela_base_t == 't'){
			?>

			<div id="middleCol" style="width: 150px; ">
				<b>Acréscimo</b>
			</div>
			
			<?}?>

		</div>
		<div id='middleCol' style='width: 205px; '>
			<input class='frm' type="text" name="descricao" value="<? echo $descricao ?>" size="30" maxlength="50">
		</div>
		<div id='middleCol' style='width: 100px; '>
			<input class='frm' type="text" name="codigo" value="<? echo $codigo ?>" size="10" maxlength="6">
		</div>
		<?
		if ($acrescimo_tabela_base_t == 't'){
			$xacrescimo_tabela_base = $acrescimo_tabela_base - 1;
			$xacrescimo_tabela_base = $xacrescimo_tabela_base * 100;

		?>
		<div id='middleCol' style='width: 150px; '>
			<input class='frm' type="text" name="acrescimo_tabela_base" value="<? echo $acrescimo_tabela_base ?>" size="10" maxlength="6">
		</div>
		<?}?>
<p></p>
<BR>

<div id='wrapper'>
	<input type='hidden' name='btnacao' value=''>
	<a href='#'><IMG SRC="imagens_admin/btn_gravar.gif" ONCLICK="javascript: if (document.frm_tipoposto.btnacao.value == '' ) { document.frm_tipoposto.btnacao.value='gravar' ; document.frm_tipoposto.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0'></a>
	<a href='#'><IMG SRC="imagens_admin/btn_apagar.gif" ONCLICK="javascript: if (document.frm_tipoposto.btnacao.value == '' ) { document.frm_tipoposto.btnacao.value='deletar' ; document.frm_tipoposto.submit() } else { alert ('Aguarde submissão') }" ALT="Apagar Informação" border='0'></a>
	<a href='#'><IMG SRC="imagens_admin/btn_limpar.gif" ONCLICK="javascript: if (document.frm_tipoposto.btnacao.value == '' ) { document.frm_tipoposto.btnacao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0'></a>
</div>

<p>

<div id="subBanner">
	<h1>Relação dos Tipos de Postos</h1>
</div>

<div id="wrapper">
	<b>Para efetuar alterações, clique na descrição do Tipo do Posto.</b>
</div>

<?
$sql = "SELECT  tipo_posto    ,
				descricao     ,
				codigo        ,
				acrescimo_tabela_base
		FROM    tbl_tipo_posto
		WHERE   fabrica = $login_fabrica
		ORDER BY descricao";

$res0 = pg_exec ($con,$sql);

echo "<BLOCKQUOTE>";

for ($y = 0 ; $y < pg_numrows($res0) ; $y++){
	$tipo_posto				= trim(pg_result($res0,$y,tipo_posto));
	$codigo					= trim(pg_result($res0,$y,codigo));
	$descricao				= trim(pg_result($res0,$y,descricao));
	$acrescimo_tabela_base	= trim(pg_result($res0,$y,acrescimo_tabela_base));

	echo "<div id=\"middlecol\" align=\"left\">\n";
	echo "<a href='$PHP_SELF?tipo_posto=$tipo_posto'>$descricao</a>\n";
	echo "</div>";
}

echo "</BLOCKQUOTE>";

?>
</div>
</form>
</div>
<?
	include "rodape.php";
?>