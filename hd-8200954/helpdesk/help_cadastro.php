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

include 'menu.php';

?>
<script type="text/javascript" src="../js/jquery.js"></script>
<script type="text/javascript" src="../js/thickbox.js"></script>
<link rel="stylesheet" type="text/css" href="../js/thickbox.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<style>

.leiturapendente {
	width: 100% - 10px;
	background-color: #FFDDCC;
	border: 1px solid #CC9988;
	padding: 5px;
	font-size: 9pt;
	color: #440000;
}

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

$sql = "
SELECT
tbl_help.help,
tbl_help.arquivo,
tbl_arquivo.descricao

FROM
tbl_help
JOIN tbl_arquivo ON tbl_help.arquivo=tbl_arquivo.arquivo

WHERE
tbl_help.fabrica=$login_fabrica
OR tbl_help.fabrica = 0
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
	$msg_geral = "Nenhum Help cadastrado para a Fábrica $login_fabrica - $login_fabrica_nome";
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

if (pg_num_rows($res_help_fabrica)) {
	$sql = "
	SELECT
	nome

	FROM
	tbl_fabrica

	WHERE
	fabrica=$login_fabrica
	";
	$res = pg_query($sql);
	$login_fabrica_nome = pg_result($res, 0, 0);

	$titulo_fabrica = "Helps para a Fábrica $login_fabrica - $login_fabrica_nome";

	echo "
	<table width=600 align='center' class='Conteudo' cellpadding='2'>
	<tr class='Titulo'>
		<td colspan=3 align=center style='font-size: 12pt; height: 30px;'>$titulo_fabrica</td>
	</tr>
	<tr class=linha0>
		<td colspan=3 align=left>
		Clique sobre o ID ou nome do programa (arquivo) para visualizá-lo<br>
		Para visualizar como o usuário admin, clique na lupa
		</td>
	</tr>
	<tr class='Titulo'>
		<td width='580'>Programa (arquivo)</td>
		<td width='20'>Ações</td>
	</tr>";

	for ($i = 0; $i < pg_num_rows($res_help_fabrica); $i++) {
		$help_lista = pg_result($res_help_fabrica, $i, help);
		$arquivo = pg_result($res_help_fabrica, $i, arquivo);
		$descricao = pg_result($res_help_fabrica, $i, descricao);
		$descricao = str_replace("/var/www", "", $descricao);
		$descricao = str_replace("/www", "", $descricao);
		$descricao = "http://www.telecontrol.com.br" . $descricao;

		if ($i % 2) {
			$cor = "#F1F4FA";
		}
		else {
			$cor = "#E6EEF7";
		}

		$sql = "
		SELECT
		help

		FROM
		tbl_help_admin

		WHERE
		help=$help_lista
		AND admin=$login_admin
		";
		$res = pg_query($con, $sql);
		if (pg_num_rows($res)) {
			$class = "";
		}
		else {
			$class = "class='leiturapendente'";
		}

		echo "
	<tr bgcolor='$cor' $class>
		<td><a href='$descricao' target='_blank'>$descricao</a></td>
		<td><a href=\"help_visualiza.php?help=$help_lista&keepThis=true&TB_iframe=true&height=450&width=760&modal=true\" title='Telecontrol - Ajuda' class='thickbox'><img src='imagens/lupa.png'></a></td> 
	</tr>";
	}

	echo "
	</table>";
}
?>
</div>
<?
	include "rodape.php";
?>