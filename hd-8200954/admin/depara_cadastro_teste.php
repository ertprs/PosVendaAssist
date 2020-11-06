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
$ordem = $_GET['ordena'];
if(!$ordem)
	$ordem = "tbl_depara.de;";

	
		/*HD 15873 18/3/2008*/

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
		$dat = explode ("/", $expira );//tira a barra
		$d = $dat[0];
		$m = $dat[1];
		$y = $dat[2];
		if(!checkdate($m,$d,$y)) $msg_erro .= "Data Inválida";
		if (strlen($msg_erro) == 0) {
			$aux = formata_data($expira);
			$expira = "'".$aux."'";
		}
	}
	
	if (strlen($msg_erro) == 0 && $aux) {
		$dt_hoje = date("Y-m-d");
		if($aux < $dt_hoje)
			$msg_erro = "Data Informada Menor que Data Atual.";
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
//echo "Teste====".$descricao_para;
$layout_menu = 'cadastro';
$title = "CADASTRAMENTO DE-PARA";
include 'cabecalho.php';
?>

<style type='text/css'>
.conteudo {
	font: bold 11px Arial, Verdana, Helvetica, sans-serif;
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


<form name="frm_depara" method="post" action="<? $PHP_SELF ?>" >
<input type="hidden" name="depara" value="<? echo $depara ?>">
<br>
<table width='700' border='0' class='conteudo' cellpadding='2' cellspacing='1'  align='center' bgcolor='#D9E2EF'>

<? if(strlen($msg_erro)>0){ ?>
	<tr bgcolor='#ff0000' style='font:bold 16px Arial; color:#ffffff;'>
		<td colspan='3' align='center'> <? echo $msg_erro; ?> </td>
	</tr>
<? } ?>
	<tr bgcolor='#596d9b''  style='color:#ffffff;'>
		<td align='center' colspan='3' style='color:#ffffff;'>De</td>
	</tr>
	<tr bgcolor='#D9E2EF'  >
		<td width='70'>&nbsp;</td>
		<td align='left'>Referência</td>
		<td align='left'>Descrição</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td align='left'><input type="text" class="frm" name="referencia_de" id="referencia_de" value="<? echo $referencia_de ?>" size="20" maxlength="20" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_depara1 (document.frm_depara.referencia_de.value, document.frm_depara.descricao_de.value, 'referencia', 'de')" <? } ?>> <img src="imagens/lupa.png" onclick="javascript: fnc_pesquisa_depara (document.frm_depara.referencia_de.value, document.frm_depara.descricao_de.value, 'referencia', 'de')" style='cursor:pointer'></td>
		<td align='left'><input type="text" class="frm" name="descricao_de" id="descricao_de" value="<? echo $descricao_de ?>" size="50" maxlength="50" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_depara1 (document.frm_depara.referencia_de.value, document.frm_depara.descricao_de.value, 'descricao', 'de')" <? } ?>> <img src="imagens/lupa.png" onclick="javascript: fnc_pesquisa_depara (document.frm_depara.referencia_de.value, document.frm_depara.descricao_de.value, 'descricao', 'de')" style='cursor:pointer' ></td>
	</tr>
	</tr>
	<tr><td colspan='3'>&nbsp;</td><tr>
	<tr>
</table>

<table width='700' border='0' class='conteudo' cellpadding='2' cellspacing='1' align='center' bgcolor='#D9E2EF'>
	<tr bgcolor='#596d9b'  style='color:#ffffff;'>
		<td align='center' colspan='3'>Para</td>
	</tr>
	<tr bgcolor='#D9E2EF'>
		<td width='70'>&nbsp;</td>
		<td align='left'>Referência</td>
		<td align='left'>Descrição</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td align='left'><input type="text" class="frm" name="referencia_para" id="referencia_para" value="<? echo $referencia_para ?>" size="20" maxlength="20" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_depara1 (document.frm_depara.referencia_para.value, document.frm_depara.descricao_para.value, 'referencia', 'para')" <? } ?>> <img src="imagens/lupa.png" onclick="javascript: fnc_pesquisa_depara (document.frm_depara.referencia_para.value, document.frm_depara.descricao_para.value, 'referencia', 'para')" style='cursor:pointer' ></td>
		<td align='left'><input type="text" class="frm" name="descricao_para" id="descricao_para" value="<? echo $descricao_para ?>" size="50" maxlength="50" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_depara1 (document.frm_depara.referencia_para.value, document.frm_depara.descricao_para.value, 'descricao', 'para')" <? } ?>> <img src="imagens/lupa.png" onclick="javascript: fnc_pesquisa_depara (document.frm_depara.referencia_para.value, document.frm_depara.descricao_para.value, 'descricao', 'para')" style='cursor:pointer' ></td>
	</tr>
	</tr>
	<tr><td colspan='3'>&nbsp;</td><tr>
	<tr>
</table>

<table width='700' border='0' class='conteudo' cellpadding='2' cellspacing='1' align='center' bgcolor='#D9E2EF'>
	<tr bgcolor='#596d9b'  style='color:#ffffff;'>
		<td align='centro' colspan='5'>Data de Expiração</td>
	</tr>
	<tr>
		<td width='70'>&nbsp;</td>
		<td colspan='3' align='left'>Data</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td align='left' colspan='3'><input type="text" class="frm" name="expira" id="expira" value="<? echo $expira ?>" size="12" maxlength="10" ></span>&nbsp;* Campo opcional, se preenchido, o cadastro será apagado nesta data</td>
	</tr>
	</tr>
	
	<tr>
	<tr><td colspan='4'>&nbsp;</td><tr>
	<tr>
		<td colspan='4' align='center'>
			<input type='hidden' name='btnacao' value=''>
			<input input type="button" style="background:url(imagens_admin/btn_gravar.gif); width:75px; cursor:pointer;" value="&nbsp;" ONCLICK="javascript: if (document.frm_depara.btnacao.value == '' ) { document.frm_depara.btnacao.value='gravar' ; document.frm_depara.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0' >
			<input type="button" style="background:url(imagens_admin/btn_apagar.gif); width:75px; cursor:pointer;" value="&nbsp;" ONCLICK="javascript: if (document.frm_depara.btnacao.value == '' ) { document.frm_depara.btnacao.value='deletar' ; document.frm_depara.submit() } else { alert ('Aguarde submissão') }" ALT="Apagar produto" border='0'>
			<input type="button" style="background:url(imagens_admin/btn_limpar.gif); width:75px; cursor:pointer;" value="&nbsp;" ONCLICK="javascript: if (document.frm_depara.btnacao.value == '' ) { document.frm_depara.btnacao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0'>
		</td>
	<tr>

</table>

</form>

<br>

<p align='center'>Para localizar uma peça na página, tecle <b>CTRL + F</b>.</p>

<?
	
		$sql = "SELECT DISTINCT tbl_depara.depara,
				tbl_depara.de    ,
				tbl_depara.para  ,
				tbl_depara.peca_para,
				tbl_depara.peca_de,
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
		JOIN tbl_depara p1 on tbl_depara.peca_para = p1.peca_de and p1.peca_para <> tbl_depara.peca_de
		WHERE   tbl_depara.fabrica = $login_fabrica
		ORDER BY ".$ordem ;

//echo $sql;
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {

	for ($y = 0 ; $y < pg_numrows($res) ; $y++){
		$depara          = trim(pg_result($res,$y,depara));
		$referencia_de   = trim(pg_result($res,$y,de));
		$descricao_de    = trim(pg_result($res,$y,descricao_de));
		$referencia_para = trim(pg_result($res,$y,para));
		$descricao_para  = trim(pg_result($res,$y,descricao_para));
		$expira          = trim(pg_result($res,$y,expira));
		$digitacao       = trim(pg_result($res,$y,digitacao));
		$peca_para = pg_fetch_result($res,$y,peca_para);
		$peca_de = pg_fetch_result($res,$y,peca_de);
		$contax=1;
		$parar= array();
		array_push($parar,$peca_de);

		for($xx=0;$xx<8;$xx++){
			if($xx == 0) {
				echo "<br><br>Peça De: ",$peca_de,"<br>";
			}
			if($xx ==1) {
				echo "Primeiro Nível:<br>";
				print_r($parar);
				echo "<br>";
			}

			if($xx ==2) {
				echo "Segundo Nível:<br>";
				print_r($parar);
				echo "<br>";

				$sqlu = "UPDATE tbl_depara SET peca_para = $parar[2], para = tbl_peca.referencia from tbl_peca
				WHERE peca_de = $parar[0] and tbl_peca.peca = $parar[2]";
				#$resu = pg_query($con,$sqlu);
			}

			if($xx ==3) {
				echo "Terceiro Nível:<br>";
				print_r($parar);
				echo "<br>";
			}

			if($xx ==4) {
				echo "Quarto Nível:<br>";
				print_r($parar);
				echo "<br>";
			}

			if($xx ==5) {
				echo "Quinto Nível:<br>";
				print_r($parar);
				echo "<br>";
			}

			if(in_array($peca_para,$parar)) {
				break;
			}else{
				array_push($parar,$peca_para);
			}
			$peca_parax= $peca_para;
			
			if(!empty($peca_parax)) {
				$sql_para="SELECT peca_para,para,(select descricao from tbl_peca where tbl_peca.peca = tbl_depara.peca_para) as descricao FROM tbl_depara join tbl_peca on tbl_peca.peca = tbl_depara.peca_de WHERE tbl_depara.fabrica = $login_fabrica AND peca_de = $peca_parax ";
				$res_para=pg_query($con,$sql_para);
				if(pg_num_rows($res_para) >0){
					$peca_para       = trim(@pg_fetch_result($res_para,0,peca_para));
					$para            = trim(@pg_fetch_result($res_para,0,para));
					$para_descricao  = trim(@pg_fetch_result($res_para,0,descricao));
				}
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
