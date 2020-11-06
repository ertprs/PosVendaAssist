<?

//Desenvolvedor Inicial: Ébano Lopes
//HD 205958
//Este programa administra as tabelas tbl_help e tbl_help_admin. A idéia é cadastrar ajudas para que os
//usuários sejam obrigados a ler quando tiver alterações. A tbl_help armazena a ajuda para cada tela, se
//não for informado a fábrica, abrirá para todas. a tbl_help_admin indica quais admins já fizeram a leitura,
//enquanto o usuário não clicar confirmando que fez a leitura, a ajuda continuará a aprecer

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';


$layout_menu = "cadastro";
$title = "Cadastramento de Help por Programa X Fábrica";

include "cabecalho.php";

if ($login_fabrica == 10) {
}
else {
	echo "
	<div width=100% align=center>
	Sem permissão de acesso
	</div>
	";
	exit;
}

?>

<script type="text/javascript" src="../js/jquery.js"></script>
<script type="text/javascript" src="../js/jquery.js"></script>
<script type="text/javascript" src="../js/thickbox.js"></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<link rel="stylesheet" type="text/css" href="../js/thickbox.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script language="JavaScript">
$().ready(function() {
	function formatItem(row) {
		return row[0] + " - " + row[1];
	}

	/* Busca da Fábrica */
	$("#fabrica").autocomplete("help_cadastro_ajax.php?acao=fabrica", {
		minChars: 3,
		delay: 250,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[0];}
	});

	$("#fabrica").result(function(event, data, formatted) {
		$("#fabrica_nome").val(data[1]);
	});

	/* Busca do Programa (arquivo) */
	$("#arquivo_descricao").autocomplete("help_cadastro_ajax.php?acao=arquivo", {
		minChars: 3,
		delay: 450,
		width: 450,
		matchContains: true,
		formatItem: function(row) {return row[1];},
		formatResult: function(row) {return row[1];}
	});

	$("#arquivo_descricao").result(function(event, data, formatted) {
		$("#arquivo").val(data[0]) ;
	});


});

</script>

<style>

img {
	border: 0px;
}

.contitulo {
	text-align: center;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}

.linha0 {
	background-color: #F1F4FA;
}

.linha1 {
	background-color: #E6EEF7;
}

.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.msg_geral {
	text-align: center;
	font-family: Arial;
	font-size: 9pt;
	color: #000055;
	background-color: #E6EEF7;
}
.Conteudo {
	font-family: Arial;
	font-size: 10px;
	font-weight: normal;
}
.ConteudoBranco {
	font-family: Arial;
	font-size: 9px;
	color:#FFFFFF;
	font-weight: normal;
}
.Mes{
	font-size: 9px;
}
.Caixa{
	BORDER-RIGHT: #6699CC 1px solid;
	BORDER-TOP: #6699CC 1px solid;
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid;
	BORDER-BOTTOM: #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
}
.Exibe{
	font-family: Arial, Helvetica, sans-serif;
	font-size: 8 px;
	font-weight: none;
	color: #000000;
	text-align: center;
}
.Erro{
	BORDER-RIGHT: #990000 1px solid;
	BORDER-TOP: #990000 1px solid;
	FONT: 10pt Arial ;
	COLOR: #ffffff;
	BORDER-LEFT: #990000 1px solid;
	BORDER-BOTTOM: #990000 1px solid;
	BACKGROUND-COLOR: #FF0000;
}
.Carregando{
	TEXT-ALIGN: center;
	BORDER-RIGHT: #aaa 1px solid;
	BORDER-TOP: #aaa 1px solid;
	FONT: 10pt Arial ;
	COLOR: #000000;
	BORDER-LEFT: #aaa 1px solid;
	BORDER-BOTTOM: #aaa 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
	margin-left:20px;
	margin-right:20px;
}

.relerro{
	color: #FF0000;
	font-size: 11pt;
	padding: 20px;
	background-color: #F7F7F7;
	text-align: center;
}
</style>

<DIV ID='wrapper'>

<? 

$help = $_GET["help"];
$fabrica = $_POST["fabrica"];
$fabrica_nome = $_POST["fabrica_nome"];
$arquivo = $_POST["arquivo"];
$arquivo_descricao = $_POST["arquivo_descricao"];
$mostra_busca = true;
$lista_help_fabrica = false;
if ($_POST["help_novo"]) {
	$help_novo = true;
}
else {
	$help_novo = false;
}
$btn_acao = $_POST["btn_acao"];

if ($fabrica_nome == "" && $fabrica) {
	$sql = "
	SELECT
	nome

	FROM
	tbl_fabrica

	WHERE
	fabrica=$fabrica
	";
	$res = pg_query($sql);
	$fabrica_nome = pg_result($res, 0, 0);
}

if ($_GET["listar"]) {
	$btn_acao = "continuar";
	$fabrica = $_GET["fabrica"];
	$arquivo = "";
}

if ($btn_acao) {
	if ($fabrica) {
		if ($arquivo == "") {
			$mostra_busca = false;
			$lista_help_fabrica = true;
			$mostra_editar = false;
		}
		else {
			$fabrica = intval($fabrica);

			$sql = "
			SELECT
			help

			FROM
			tbl_help

			WHERE
			fabrica=$fabrica
			AND arquivo=$arquivo
			";
			@$res_fabrica_arquivo = pg_query($con, $sql);
			if (pg_errormessage($con)) {
				$msg_erro = "Erro de SQL: $sql - " . pg_errormessage($con);
			}
			else if(pg_num_rows($res_fabrica_arquivo)) {
				$help = pg_result($res_fabrica_arquivo, 0, help);
			}
			else {
				$help_novo = true;
			}
		}
	}
	else {
		if ($arquivo == "") {
			$fabrica = intval($fabrica);

			$sql = "
			SELECT
			fabrica,
			nome

			FROM
			tbl_fabrica

			WHERE
			fabrica=$fabrica
			";
			@$res_fabrica = pg_query($con, $sql);
			if (pg_errormessage($con)) {
				$msg_erro = "Erro de SQL: $sql - " . pg_errormessage($con);
			}

			if (pg_num_rows($res_fabrica)) {
				$fabrica_nome = pg_result($res_fabrica, 0, nome);
				$lista_help_fabrica = true;
				$mostra_busca = false;
			}
			else {
				$msg_erro = "Fábrica $fabrica - $fabrica_nome inexistente";
			}
		}
		else {
			$fabrica = intval($fabrica);

			$sql = "
			SELECT
			help

			FROM
			tbl_help

			WHERE
			fabrica=$fabrica
			AND arquivo=$arquivo
			";
			@$res_fabrica_arquivo = pg_query($con, $sql);
			if (pg_errormessage($con)) {
				$msg_erro = "Erro de SQL: $sql - " . pg_errormessage($con);
			}
			else if(pg_num_rows($res_fabrica_arquivo)) {
				$help = pg_result($res_fabrica_arquivo, 0, help);
			}
			else {
				$help_novo = true;
			}
		}
	}
}

if ($lista_help_fabrica) {
	if ($fabrica == "") $fabrica = 0;

	$sql = "
	SELECT
	tbl_help.help,
	tbl_help.arquivo,
	tbl_arquivo.descricao

	FROM
	tbl_help
	JOIN tbl_arquivo ON tbl_help.arquivo=tbl_arquivo.arquivo

	WHERE
	tbl_help.fabrica=$fabrica
	";
	@$res_help_fabrica = pg_query($con, $sql);
	if (pg_errormessage($con)) {
		$msg_erro = "Erro de SQL: $sql - " . pg_errormessage($con);
	}

	if (pg_num_rows($res_help_fabrica)) {
		$lista_help_fabrica = true;
		$mostra_busca = false;
	}
	else {
		if ($fabrica) {
			$msg_erro = "Nenhum Help cadastrado para a Fábrica $fabrica - $fabrica_nome";
		}
		else {
			$msg_erro = "Nenhum Help cadastrado para TODAS AS FÁBRICAS";
		}
		$lista_help_fabrica = false;
		$mostra_busca = true;
	}
}

if ($help_novo) {
	switch($btn_acao) {
		case "continuar":
			$mostra_busca = false;
			$lista_help_fabrica = false;
			$mostra_editar = true;
			$descricao = "Digite aqui o novo Help para este arquivo";
		break;

		case "gravar":
			$descricao = stripslashes($_POST['FCKeditor1']);
			if ($fabrica) {
				$sql = "
				INSERT INTO
				tbl_help(fabrica, arquivo, descricao)
				VALUES($fabrica, $arquivo, '$descricao')
				";
			}
			else {
				$sql = "
				INSERT INTO
				tbl_help(fabrica, arquivo, descricao)
				VALUES(0, $arquivo, '$descricao')
				";
			}
			
			@$res_gravar_novo = pg_query($sql);
			if (pg_errormessage($con)) {
				$msg_erro = "Erro de SQL: $sql - " . pg_errormessage($con);
			}
			else {
				$sql = "SELECT CURRVAL('seq_help')";
				$res = pg_query($con, $sql);
				$help = pg_result($res, 0, 0);
				$help_novo = false;
			}
		break;
	}
}

if ($help) {
	$sql = "
	SELECT
	tbl_help.*,
	tbl_fabrica.nome AS fabrica_nome,
	tbl_arquivo.descricao AS arquivo_descricao

	FROM
	tbl_help
	JOIN tbl_fabrica ON tbl_help.fabrica=tbl_fabrica.fabrica
	JOIN tbl_arquivo ON tbl_help.arquivo=tbl_arquivo.arquivo

	WHERE
	help=$help
	";
	@$res_help = pg_query($sql);
	if (pg_errormessage($con)) {
		$msg_erro = "Erro de SQL: $sql - " . pg_errormessage($con);
	}

	if (pg_num_rows($res_help)) {
		$mostra_busca = false;
		$lista_help_fabrica = false;
		$mostra_editar = true;

		$fabrica = pg_result($res_help, 0, fabrica);
		$fabrica_nome = pg_result($res_help, 0, fabrica_nome);
		$arquivo = pg_result($res_help, 0, arquivo);
		$arquivo_descricao = pg_result($res_help, 0, arquivo_descricao);
		$descricao = pg_result($res_help, 0, descricao);

		switch($_POST["btn_acao"]) {
			case "gravar":
				$sql = "
				UPDATE
				tbl_help

				SET
				descricao='" . $_POST['FCKeditor1'] . "'

				WHERE
				help=$help
				";
				@$res_gravar = pg_query($con, $sql);

				if (pg_errormessage($con)) {
					$msg_erro = "Erro de SQL: $sql - " . pg_errormessage($con);
				}
				else {
					$msg_geral = "Help $help atualizado com sucesso!";
					$descricao = $_POST['FCKeditor1'];
				}
			break;

			case "apagar":
				$sql = "DELETE FROM tbl_help WHERE help=$help";
				@$res_apagar = pg_query($con, $sql);

				if (pg_errormessage($con)) {
					$msg_erro = "Erro de SQL: $sql - " . pg_errormessage($con);
				}
				else {
					echo "
					<script language=javascript>
					document.location = '$PHP_SELF';
					</script>
					";
					die;
				}
			break;
		}
	}
	else {
		$mostra_busca = true;
		$lista_help_fabrica = false;
		$mostra_editar = false;

		$msg_erro = "Help $help não encontrado";
	}

}

if (strlen($msg_erro) > 0) {
	if (strpos($msg_erro,"ERROR: ") !== false) {
			$erro = "Foi detectado o seguinte erro:<br>";
			$msg_erro = substr($msg_erro, 6);
		}

		// retira CONTEXT:
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}

	echo "<div class='error'>$msg_erro</div>";
}

if ($msg_geral) {
	echo "
	<table width=600 align='center' class='Conteudo' cellpadding='2'>
	<tr class='msg_geral'>
		<td width='600' align=center style='height: 30px;'>
		$msg_geral
		</td>
	</tr>
	</table>";
}

if ($mostra_editar) {
	$partes = explode("/", $arquivo_descricao);
	unset($partes[0]);
	unset($partes[1]);
	unset($partes[2]);
	unset($partes[4]);
	$arquivo_link = "http://" . $_SERVER["HTTP_HOST"] . "/" . implode("/", $partes);

	if ($fabrica) {
		$fabrica_titulo = "<b><a href='" . $PHP_SELF . "?fabrica=$fabrica&listar=sim' style='font-size: 10pt; color:#FFFFFF;' title='Clique aqui para listar todos os helps específicos desta fábrica'>Fábrica $fabrica - $fabrica_nome</b></a></font><br>
		Este Help irá aparecer somente para esta fábrica, substituindo o help geral de todas as fábricas";
	}
	else {
		$fabrica_titulo = "<b><a href='" . $PHP_SELF . "?fabrica=$fabrica&listar=sim' style='font-size: 10pt; color:#FFFFFF;' title='Clique aqui para listar todos os helps gerais - TODAS AS FÁBRICAS'>Todas as Fábricas</a></b><br>
		Este Help irá aparecer para todas as fábricas, a não ser que já tenha um Help específico cadastrado";
	}

	if ($help_novo) {
		$titulo_acao = "Inserindo novo Help";
	}
	else {
		$titulo_acao = "Editando Help $help";
	}

	echo "
	<form name='frm_help_editar' method='post' action='$PHP_SELF?help=$help' enctype='multipart/form-data'>
	<table width=600 align='center' class='Conteudo' cellpadding='2'>
	<tr class='Titulo'>
		<td width='600' align=center style='height: 30px;'>
		<font style='font-size: 12pt;'>$titulo_acao</font><br>
		<b>Arquivo:</b> <a href='$arquivo_link' style='color:#FFFFFF;' title='Clique aqui para abrir o link do arquivo em uma nova janela' target='_blank'>$arquivo_descricao</a>
		</td>
	</tr>
	<tr class='Titulo'>
		<td>$fabrica_titulo</td>
	</tr>";
	
	if ($help) {
		$sql = "
		SELECT
		help

		FROM
		tbl_help

		WHERE
		fabrica=0
		AND arquivo IN (SELECT arquivo FROM tbl_help WHERE help=$help AND fabrica<>0)
		";
		$res_geral = pg_query($con, $sql);
		if (pg_errormessage($con)) {
			$msg_erro = "Erro de SQL: $sql - " . pg_errormessage($con);
		}
		elseif (pg_num_rows($res_geral)) {
			echo "
		<tr>
			<td class=linha1>
				Existe help geral cadastrado para este Programa (arquivo)
			</td>
		</tr>";
		}
	}

	echo "
	<tr valign=top>
		<td>";

	include_once("../admin/js/fckeditor/fckeditor.php");
	$oFCKeditor = new FCKeditor('FCKeditor1') ;
	$oFCKeditor->BasePath = '../admin/js/fckeditor/' ;
	$oFCKeditor->Value = $descricao ;
	$oFCKeditor->Width = "100%";
	$oFCKeditor->Height = "500";
	$oFCKeditor->Create() ;

	if ($help_novo) {
		$btn_apagar = "";
		$help_novo_hidden = "<input type='hidden' name='help_novo' id='help_novo' value='sim'>";
	}
	else {
		$btn_apagar = '<img src=\'imagens/btn_apagar.gif\' onclick="if (confirm(\'Apagar este Help?\')) { document.frm_help_editar.btn_acao.value=\'apagar\'; document.frm_help_editar.submit(); }" style="cursor:pointer;">';
		$help_novo_hidden = "";
	}

	echo '
		</td>
	</tr>
	<tr>
		<td>
		' . $help_novo_hidden . '
		<input type="hidden" name="btn_acao" id="btn_acao" value="">
		<input type="hidden" name="arquivo" id="arquivo" value="' . $arquivo . '">
		<input type="hidden" name="fabrica" id="fabrica" value="' . $fabrica . '">
		<img src=\'imagens/btn_gravar.gif\' onclick="document.frm_help_editar.btn_acao.value=\'gravar\'; document.frm_help_editar.submit();" style="cursor:pointer;">
		' . $btn_apagar . '
		<a href="help_cadastro.php" title="Clique aqui para voltar para a pesquisa"><img src="imagens/btn_voltar.gif"></a>
		</td>
	</tr>
	</table>
	</form>';
}

if ($lista_help_fabrica) {
	if (pg_num_rows($res_help_fabrica)) {
		if ($fabrica) {
			$titulo_fabrica = "Helps para a Fábrica $fabrica - $fabrica_nome";
		}
		else {
			$titulo_fabrica = "Helps gerais para TODAS AS FÁBRICAS";
		}
		echo "
		<table width=600 align='center' class='Conteudo' cellpadding='2'>
		<tr class='Titulo'>
			<td colspan=3 align=center style='font-size: 12pt; height: 30px;'>$titulo_fabrica</td>
		</tr>
		<tr class=linha0>
			<td colspan=3 align=left>
			Clique sobre o ID ou nome do programa (arquivo) para editá-lo<br>
			Para visualizar como o usuário admin, clique na lupa
			</td>
		</tr>
		<tr class='Titulo'>
			<td width='80'>ID Help</td>
			<td width='500'>Programa (arquivo)</td>
			<td width='20'>Ações</td>
		</tr>";

		for ($i = 0; $i < pg_num_rows($res_help_fabrica); $i++) {
			$help_lista = pg_result($res_help_fabrica, $i, help);
			$arquivo = pg_result($res_help_fabrica, $i, arquivo);
			$descricao = pg_result($res_help_fabrica, $i, descricao);

			if ($i % 2) {
				$cor = "#F1F4FA";
			}
			else {
				$cor = "#E6EEF7";
			}

			$link = $PHP_SELF . "?help=$help_lista";
			
			echo "
		<tr bgcolor='$cor'>
			<td><a href='$link'>$help_lista</a></td>
			<td><a href='$link'>$descricao</a></td>
			<td><a href=\"help_visualiza.php?help=$help_lista&keepThis=true&TB_iframe=true&height=450&width=760&modal=true\" title='Telecontrol - Ajuda' class='thickbox'><img src='imagens/lupa.png'></a></td> 
		</tr>";
		}

		echo "
		<tr>
			<td colspan=2 align=center>
			<a href='help_cadastro.php' title='Clique aqui para voltar para a pesquisa'><img src='imagens/btn_voltar.gif'></a>
			</td>
		</tr>
		</table>";
	}
}

if ($mostra_busca) {
?>
	<form name="frm_help" method="post" action="<? echo $PHP_SELF ?>" enctype='multipart/form-data'>
		<table width='600' align='center' border='0'>
		<tr bgcolor='#d9e2ef'>
			<td colspan=2 align='left'>
				Preencha os dados abaixo para localizar um Help:<br>
				- Preenchendo <b>somente a Fábrica</b> irá listar todos os Helps que tem entradas específicas para a Fábrica<br>
				- Preenchendo <b>somente o Programa</b> irá localizar o Help geral do programa (para todas as fábricas)<br>
				- Preenchendo <b>Fábrica e Programa</b> irá localizar o Help específico para a Fábrica<br>
				- Deixado os <b>campos em branco</b> irá mostrar todos os Helps gerais cadastrados<br>
				<br>
				Ao localizar um Help o sistema irá abrir a tela para inserir um novo, caso não exista, ou abrir o existente para alterações
			</td>
		</tr>
		<tr class=Titulo>
			<td align='center'>
				<b>Fábrica</b>
			</td>
			<td align='center'>
				<b>Programa (arquivo)</b>
			</td>
		</tr>

		<tr>
			<td align='center' nowrap>
				<input type="text" name="fabrica" id="fabrica" value="<? echo $fabrica ?>" size="15" maxlength="5">
				<input type="hidden" name="fabrica_nome" id="fabrica_nome" value="<? echo $fabrica_nome ?>">
			</td>
			<td align='center' nowrap>
				<input type="text" name="arquivo_descricao" id="arquivo_descricao" value="<? echo $arquivo_descricao; ?>" size="50" maxlength="1000">
				<input type="hidden" name="arquivo" id="arquivo" value="<? echo $arquivo ?>">
			</td>
		</tr>
		</table>

		<input type='hidden' name='btn_lista' value=''>
		<p align='center'>
		<input type="hidden" name="btn_acao" id="btn_acao" value="">
		<img src='imagens/btn_continuar.gif' onclick="document.frm_help.btn_acao.value='continuar'; document.frm_help.submit();" style="cursor:pointer;">
		<img src='imagens/btn_limpar.gif' title='Clique aqui para limpar os campos da busca' onclick='$("#arquivo_descricao").val(""); $("#arquivo").val(""); $("#fabrica_nome").val(""); $("#fabrica").val("");' style="cursor:pointer;">
		</p>

		<br>

	</form>
<?
}
?>

</div>
<?
	include "rodape.php";
?>
