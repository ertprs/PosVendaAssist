<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';


# Pesquisa pelo AutoComplete AJAX
$q = trim($_GET["q"]);
if (isset($_GET["q"])){
	$busca      = $_GET["busca"];
	$tipo_busca = $_GET["tipo_busca"];
	if (strlen($q)>2){
		if ($tipo_busca=="produto"){
			$sql = "SELECT tbl_peca.peca,
							tbl_peca.referencia,
							tbl_peca.descricao
					FROM tbl_peca
					WHERE tbl_peca.fabrica = $login_fabrica ";
			
			if ($busca == "codigo"){
				$sql .= " AND UPPER(tbl_peca.referencia) like UPPER('%$q%') ";
			}else{
				$sql .= " AND UPPER(tbl_peca.descricao) like UPPER('%$q%') ";
			}
			
			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$peca       = trim(pg_result($res,$i,peca));
					$referencia = trim(pg_result($res,$i,referencia));
					$descricao  = trim(pg_result($res,$i,descricao));
					echo "$peca|$descricao|$referencia";
					echo "\n";
				}
			}
		}
	}
	exit;
}


if (strlen($_GET["depara"]) > 0) {
	$depara = trim($_GET["depara"]);
}

if (strlen($_POST["depara"]) > 0) {
	$depara = trim($_POST["depara"]);
}

if (strlen($_POST["btnacao"]) > 0) {
	$btnacao = trim($_POST["btnacao"]);
}

if ($btnacao == "deletar" and strlen($depara) > 0) {
	$res = pg_exec ($con,"BEGIN TRANSACTION");
	
	$sql = "DELETE FROM tbl_depara
			WHERE  tbl_depara.depara  = $depara
			AND    tbl_depara.fabrica = $login_fabrica;";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	
	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		
		header ("Location: $PHP_SELF");
		exit;
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		$referencia_de   = $_POST["referencia_de"];
		$descricao_de    = $_POST["descricao_de"];
		$referencia_para = $_POST["referencia_para"];
		$descricao_para  = $_POST["descricao_para"];
		$expira          = $_POST["expira"];
		$digitacao       = $_POST["digitacao"];
		
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}


if ($btnacao == "gravar") {
	if (strlen($_POST["referencia_de"]) > 0) {
		$aux_referencia_de = "'". trim($_POST["referencia_de"]) ."'";
	}else{
		$msg_erro = "Favor informar a referência da peça 'DE'.";
	}
	
	if (strlen($msg_erro) == 0) {
		$sql = "SELECT *
				FROM   tbl_peca
				WHERE  upper(trim(tbl_peca.referencia)) = upper(trim($aux_referencia_de))
				AND    tbl_peca.fabrica = $login_fabrica;";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		
		if (strlen($msg_erro) == 0) {
			if (pg_numrows($res) == 0) $msg_erro = "Peça 'DE' informada não encontrada.";
			else                       $peca_de  = pg_result($res,0,peca);
		}
	}
	
	if (strlen($msg_erro) == 0) {
		if (strlen($_POST["referencia_para"]) > 0) {
			$aux_referencia_para = "'". trim($_POST["referencia_para"]) ."'";
		}else{
			$msg_erro = "Favor informar a referência da peça 'PARA'.";
		}
		
		if (strlen($msg_erro) == 0) {
			$sql = "SELECT *
					FROM   tbl_peca
					WHERE  upper(trim(tbl_peca.referencia)) = upper(trim($aux_referencia_para))
					AND    tbl_peca.fabrica = $login_fabrica;";
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
			
			if (strlen($msg_erro) == 0) {
				if (pg_numrows($res) == 0) $msg_erro = "Peça 'PARA' informada não encontrada.";
				else                       $peca_para = pg_result($res,0,peca);
			}
		}
	}

	$expira = trim($_POST["expira"]);
	if (strlen($expira)==0){
		$expira = " NULL ";
	}else{
		$aux = formata_data($expira);
		$expira = "'".$aux."'";
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		
		if (strlen($depara) == 0) {
			###INSERE NOVO REGISTRO
			$sql = "INSERT INTO tbl_depara (
						fabrica,
						de     ,
						para,
						expira
					) VALUES (
						$login_fabrica    ,
						$aux_referencia_de,
						$aux_referencia_para,
						$expira
					);";
		}else{
			###ALTERA REGISTRO
			$sql = "UPDATE tbl_depara SET
							de        = $aux_referencia_de,
							para      = $aux_referencia_para,
							expira    = $expira
					WHERE  tbl_depara.depara = $depara
					AND    tbl_linha.fabrica = $login_fabrica;";
		}
		$res = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);

	}



	if (strlen ($msg_erro) == 0) {
		$sql = "SELECT fn_depara_lbm($aux_referencia_de,$aux_referencia_para,$login_fabrica);";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		
		header ("Location: $PHP_SELF");
		exit;
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		$referencia_de    = $_POST["referencia_de"];
		$descricao_de     = $_POST["descricao_de"];
		$referencia_para  = $_POST["referencia_para"];
		$descricao_para   = $_POST["descricao_para"];
		$expira           = $_POST["expira"];
		$digitacao        = $_POST["digitacao"];

		if(strpos($msg_erro,'"tbl_depara_unico"'))
		$msg_erro = "De-Para já cadastrado.";

		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}


###CARREGA REGISTRO
if (strlen($depara) > 0) {
	$sql = "SELECT  tbl_depara.de  ,
					tbl_depara.para,
					TO_CHAR(tbl_depara.expira,'DD/MM/YYYY') AS expira,
					TO_CHAR(tbl_depara.digitacao,'DD/MM/YYYY') AS digitacao,
					(
					SELECT tbl_peca.descricao
					FROM   tbl_peca
					JOIN tbl_depara ON tbl_depara.de   = tbl_peca.peca::text
					WHERE  tbl_peca.referencia = tbl_depara.de
					AND tbl_peca.fabrica = $login_fabrica
					) AS descricao_de,
					(
					SELECT tbl_peca.descricao
					FROM   tbl_peca
					JOIN tbl_depara ON tbl_depara.para = tbl_peca.peca::text
					WHERE  tbl_peca.referencia = tbl_depara.para
					AND tbl_peca.fabrica = $login_fabrica
					) AS descricao_para
			FROM    tbl_depara
			WHERE   tbl_depara.fabrica = $login_fabrica
			AND     tbl_depara.depara  = $depara;";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$referencia_de   = trim(pg_result($res,0,de));
		$descricao_de    = trim(pg_result($res,0,descricao_de));
		$referencia_para = trim(pg_result($res,0,para));
		$descricao_para  = trim(pg_result($res,0,descricao_para));
		$expira          = trim(pg_result($res,0,expira));
		$digitacao       = trim(pg_result($res,0,digitacao));

	}
}

$layout_menu = 'cadastro';
$title = "Cadastramento DE-PARA";
include 'cabecalho.php';
?>

<style type='text/css'>
.conteudo {
	font: bold 10px Verdana, Arial, Helvetica, sans-serif;
	color: #000000;
}
</style>

<script language="JavaScript">
function fnc_pesquisa_depara (campo, campo1, tipo, controle) {
	if (campo.value != "" && campo1 == "" || campo.value == "" && campo1.value != "") {
		var url = "";
		url = "depara_pesquisa.php?controle=" + controle + "&campo=" + campo + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.referencia= document.frm_depara.referencia;
		janela.descricao = document.frm_depara.descricao;
		janela.focus();
	}
}

function fnc_pesquisa_depara1 (campo, campo1, tipo, controle) {
	if (campo.value != "" && campo1 == "" || campo.value == "" && campo1.value != "") {
		var url = "";
		url = "depara_pesquisa.php?controle=" + controle + "&campo=" + campo + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.referencia= document.frm_depara.referencia;
		janela.descricao = document.frm_depara.descricao;
		janela.focus();
	}
}
</script>

<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#digitacao').datePicker();
		$("#digitacao").maskedinput("99/99/9999");
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
	$("#referencia_para").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#referencia_para").result(function(event, data, formatted) {
		$("#descricao_para").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#descricao_para").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#descricao_para").result(function(event, data, formatted) {
		$("#referencia_para").val(data[2]) ;
	});


	/*  PARA  */
	/* Busca por Produto */
	$("#referencia_de").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#referencia_de").result(function(event, data, formatted) {
		$("#descricao_de").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#descricao_de").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#descricao_de").result(function(event, data, formatted) {
		$("#referencia_de").val(data[2]) ;
	});

});
</script>

<? if (strlen($msg_erro) > 0) { ?>
<div class='error'>
	<? echo $msg_erro; ?>
</div>
<? } ?>

<form name="frm_depara" method="post" action="<? $PHP_SELF ?>">
<input type="hidden" name="depara" value="<? echo $depara ?>">

<table width='600' border='0' class='conteudo' cellpadding='2' cellspacing='1'  align='center'>
	<tr bgcolor='#D9E2EF'>
		<td align='center' colspan='2'>De</td>
	</tr>
	<tr bgcolor='#D9E2EF'>
		<td align='center'>Referência</td>
		<td align='center'>Descrição</td>
	</tr>
	<tr>
		<td><input type="text" class="frm" name="referencia_de" id="referencia_de" value="<? echo $referencia_de ?>" size="20" maxlength="20" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_depara1 (document.frm_depara.referencia_de.value, document.frm_depara.descricao_de.value, 'referencia', 'de')" <? } ?>> <img src="imagens_admin/btn_buscar5.gif" onclick="javascript: fnc_pesquisa_depara (document.frm_depara.referencia_de.value, document.frm_depara.descricao_de.value, 'referencia', 'de')"></td>
		<td><input type="text" class="frm" name="descricao_de" id="descricao_de" value="<? echo $descricao_de ?>" size="50" maxlength="50" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_depara1 (document.frm_depara.referencia_de.value, document.frm_depara.descricao_de.value, 'descricao', 'de')" <? } ?>> <img src="imagens_admin/btn_buscar5.gif" onclick="javascript: fnc_pesquisa_depara (document.frm_depara.referencia_de.value, document.frm_depara.descricao_de.value, 'descricao', 'de')"></td>
	</tr>
</table>
<br>
<table width='600' border='0' class='conteudo' cellpadding='2' cellspacing='1' align='center'>
	<tr bgcolor='#D9E2EF'>
		<td align='center' colspan='2'>Para</td>
	</tr>
	<tr bgcolor='#D9E2EF'>
		<td align='center'>Referência</td>
		<td align='center'>Descrição</td>
	</tr>
	<tr>
		<td><input type="text" class="frm" name="referencia_para" id="referencia_para" value="<? echo $referencia_para ?>" size="20" maxlength="20" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_depara1 (document.frm_depara.referencia_para.value, document.frm_depara.descricao_para.value, 'referencia', 'para')" <? } ?>> <img src="imagens_admin/btn_buscar5.gif" onclick="javascript: fnc_pesquisa_depara (document.frm_depara.referencia_para.value, document.frm_depara.descricao_para.value, 'referencia', 'para')"></td>
		<td><input type="text" class="frm" name="descricao_para" id="descricao_para" value="<? echo $descricao_para ?>" size="50" maxlength="50" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_depara1 (document.frm_depara.referencia_para.value, document.frm_depara.descricao_para.value, 'descricao', 'para')" <? } ?>> <img src="imagens_admin/btn_buscar5.gif" onclick="javascript: fnc_pesquisa_depara (document.frm_depara.referencia_para.value, document.frm_depara.descricao_para.value, 'descricao', 'para')"></td>
	</tr>
</table>

<table width='600' border='0' class='conteudo' cellpadding='2' cellspacing='1' align='center'>
	<tr bgcolor='#D9E2EF'>
		<td align='center' colspan='3'>Data Expira</td>
	</tr>
	<tr>
		<td width='40%'></td>
		<td align='left'><input type="text" class="frm" name="expira" id="expira" value="<? echo $expira ?>" size="12" maxlength="10" ></span></td>
		<td width='40%' align='left'>* Data opcional<br>* Se preenchido, será apagado nesta data</td>
	</tr>
</table>

<br>
<br>

<center>
<input type='hidden' name='btnacao' value=''>
<IMG SRC="imagens_admin/btn_gravar.gif" ONCLICK="javascript: if (document.frm_depara.btnacao.value == '' ) { document.frm_depara.btnacao.value='gravar' ; document.frm_depara.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0' style="cursor:pointer;">
<IMG SRC="imagens_admin/btn_apagar.gif" ONCLICK="javascript: if (document.frm_depara.btnacao.value == '' ) { document.frm_depara.btnacao.value='deletar' ; document.frm_depara.submit() } else { alert ('Aguarde submissão') }" ALT="Apagar produto" border='0' style="cursor:pointer;">
<IMG SRC="imagens_admin/btn_limpar.gif" ONCLICK="javascript: if (document.frm_depara.btnacao.value == '' ) { document.frm_depara.btnacao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0' style="cursor:pointer;">
</center>

</form>

<br>

<p align='center'>Para localizar uma peça na página, tecle <b>CTRL + F</b>.</p>

<?
		/*HD 15873 18/3/2008*/
		$sql = "SELECT  tbl_depara.depara,
				tbl_depara.de    ,
				tbl_depara.para  ,
				tbl_depara.peca_de ,
				tbl_depara.peca_para ,
				TO_CHAR(tbl_depara.expira,'DD/MM/YYYY') AS expira,
				TO_CHAR(tbl_depara.digitacao,'DD/MM/YYYY') AS digitacao,
				(
					SELECT tbl_peca.descricao
					FROM   tbl_peca
					WHERE  tbl_peca.referencia = tbl_depara.de
					AND    tbl_peca.fabrica    = $login_fabrica
					LIMIT 1
				) AS descricao_de,
				(
					SELECT tbl_peca.descricao
					FROM   tbl_peca
					WHERE  tbl_peca.referencia = tbl_depara.para
					AND    tbl_peca.fabrica    = $login_fabrica
					LIMIT 1
				) AS descricao_para
		FROM    tbl_depara
		WHERE   tbl_depara.fabrica = $login_fabrica
		ORDER BY tbl_depara.de;";

//echo $sql;
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {

	for ($y = 0 ; $y < pg_numrows($res) ; $y++){
		$depara          = trim(pg_result($res,$y,depara));
		$referencia_de   = trim(pg_result($res,$y,de));
		$descricao_de    = trim(pg_result($res,$y,descricao_de));
		$referencia_para = trim(pg_result($res,$y,para));
		$peca_de       = trim(pg_result($res,$y,peca_de));
		$peca_para       = trim(pg_result($res,$y,peca_para));
		$descricao_para  = trim(pg_result($res,$y,descricao_para));
		$expira          = trim(pg_result($res,$y,expira));
		$digitacao       = trim(pg_result($res,$y,digitacao));

		$cor = ($y % 2 == 0) ? "#FFFFFF": "#F1F4FA";

		if ($y == 0 ) {
			if ($y <> 0 ) echo "</table>\n<br>\n";
			echo "<table width='750' border='0' class='conteudo' align='center' cellpadding='2' cellspacing='1'>\n";
			echo "<tr bgcolor='#D9E2EF'>\n";
			echo "<td width='50%' align='center' colspan='2'>De</td>\n";
			echo "<td align='center' colspan='2'>Para</td>\n";
			echo "</tr>\n";
			echo "<tr bgcolor='#D9E2EF'>\n";
			echo "<td align='center'>Referência</td>\n";
			echo "<td align='center'>Descrição</td>\n";
			echo "<td align='center'>Referência</td>\n";
			echo "<td align='center'>Descrição</td>\n";
			echo "<td align='center'>Expira</td>\n";
			if($login_fabrica==3)echo "<td align='center'>Inclusão</td>\n";
			echo "</tr>\n";
		}

		$sqly = "select * from tbl_lista_basica where peca = $peca_de";
		$resy = pg_exec ($con,$sqly);
		$sqlx = "select * from tbl_lista_basica where peca = $peca_para";
		$resx = pg_exec ($con,$sqlx);
		if(pg_numrows($resx) == pg_numrows($resy)){
#			$resultado = "OK";
		}else{
				$resultado = "de = ".pg_numrows($resy)." para= ".pg_numrows($resx);
		echo "<tr bgcolor='$cor'>\n";
		echo "<td align='left' nowrap>$referencia_de</td>\n";
		echo "<td align='left' nowrap>$descricao_de</td>\n";
		echo "<td align='left' nowrap><a href='$PHP_SELF?depara=$depara'>$referencia_para</a></td>\n";
		echo "<td align='left' nowrap><a href='$PHP_SELF?depara=$depara'>$descricao_para</a></td>\n";
		echo "<td align='left' nowrap>$expira</td>\n";
		echo "<td align='center' nowrap>$resultado</td>\n";

		if($login_fabrica==3)echo "<td align='left' nowrap>$digitacao</td>\n";
		echo "</tr>\n";
			if(pg_numrows($resx) != 0 ){
#				$sqlz = "insert into tbl_lista_basica (produto, peca, qtde, fabrica, type, ordem, ativo, admin, data_alteracao) select produto, $peca_de, qtde, fabrica, type, ordem, ativo, admin, data_alteracao from tbl_lista_basica where peca = $peca_para";
#				$resz = pg_exec ($con,$sqlz);
			}
		}


	}
	echo "</table>\n";
}
?>
<?
	include "rodape.php";
?>
</body>
</html>
