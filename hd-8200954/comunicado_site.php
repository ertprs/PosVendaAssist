<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

$msg_erro = "";

if (strlen(trim($_POST["btn_acao"])) > 0) $btn_acao = strtoupper(trim($_POST["btn_acao"]));

if ($btn_acao == "CONTINUAR") {
	$qtde_comunicados = $_POST["qtde_comunicados"];
	$ciente           = $_POST["ciente"];

	if ($ciente != "t") $msg_erro .= " Se você já visualizou o comunicado,<br>marque o campo e em seguida clique no botão \"Continuar\". ";

	if (strlen($msg_erro) == 0) {
		$res = pg_exec($con,"BEGIN TRANSACTION");

		for ($k = 0 ; $k < $qtde_comunicados ; $k++) {
			$x_comunicado = $_POST["comunicado_" . $k];
			
			$sql = "INSERT INTO tbl_comunicado_posto_blackedecker (
						comunicado       ,
						fabrica          ,
						posto            ,
						data_confirmacao 
					) VALUES (
						$x_comunicado     ,
						$login_fabrica    ,
						$login_posto      ,
						current_timestamp 
					);";
#	echo bl2br($sql)."<br><br>";
			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
			if (strlen($msg_erro) > 0) break;
		}

		if (strlen($msg_erro) == 0) {
			$res = pg_exec($con,"COMMIT TRANSACTION");
			header("Location: login.php");
			exit;
		}else{
			$res = pg_exec($con,"ROLLBACK TRANSACTION");
		}
	}
}

$title = "Telecontrol ASSIST - Gerenciamento de Assistência Técnica";

$layout_menu = "os";


$sql =	"SELECT tbl_posto_fabrica.codigo_posto                     ,
				tbl_posto_fabrica.pedido_em_garantia               ,
				tbl_posto_fabrica.pedido_faturado                  ,
				tbl_posto.suframa                                  ,
				tbl_posto.nome                       AS posto_nome 
		FROM tbl_posto
		JOIN tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_posto.posto
								AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_posto.posto = $login_posto;";
$res = pg_exec($con,$sql);

if (pg_numrows($res) == 1) {
	$codigo_posto       = pg_result($res,0,codigo_posto);
	$pedido_em_garantia = pg_result($res,0,pedido_em_garantia);
	$pedido_faturado    = pg_result($res,0,pedido_faturado);
	$suframa            = pg_result($res,0,suframa);
	$posto_nome         = pg_result($res,0,posto_nome);
}

?>

<html>

<head>
<title>Telecontrol ASSIST - Gerenciamento de Assistência Técnica</title>
<meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
<meta http-equiv="Expires"       content="0">
<meta http-equiv="Pragma"        content="no-cache, public">
<meta http-equiv="Cache-control" content="no-cache, public, must-revalidate, post-check=0, pre-check=0">
<meta name      ="Author"        content="Telecontrol Networking Ltda">
<meta name      ="Generator"     content="na mão...">
<meta name      ="Description"   content="Sistema de gerenciamento para Postos de Assistência Técnica e Fabricantes.">
<meta name      ="KeyWords"      content="Assistência Técnica, Postos, Manutenção, Internet, Webdesign, Orçamento, Comercial, Jóias, Callcenter">
<link type="text/css" rel="stylesheet" href="css/css.css">
<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Arial;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B
}
.Conteudo {
	font-family: Verdana, Tahoma, Arial;
	font-size: 10px;
	font-weight: normal;
}
</style>
</head>

<body>

<br>

<h1><? echo $login_nome ?></h1>

<center><img src="imagens/bemVindo<? echo $login_fabrica_nome ?>.gif" alt="Bem Vindo!!!"></center>

<?
if (strlen($msg_erro) > 0) {
	echo "<br>";
	echo "<div id='mainCol'>";
	echo "<table width='100%' class='error'><tr><td>";
	echo $msg_erro;
	echo "</td></tr></table>";
	echo "</div>";
	echo "<br>";
}
?>

<form name="frm_comunicado" method="post" action="<?echo $PHP_SELF?>">

<?
$sql =	"SELECT tbl_comunicado.comunicado                                       ,
				tbl_comunicado.descricao                                        ,
				tbl_comunicado.mensagem                                         ,
				tbl_comunicado.extensao                                         ,
				TO_CHAR(tbl_comunicado.data,'DD/MM/YYYY') AS data               ,
				tbl_comunicado.produto                                          ,
				tbl_produto.referencia                    AS produto_referencia ,
				tbl_produto.descricao                     AS produto_descricao
		FROM tbl_comunicado
		LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_comunicado.produto
		LEFT JOIN tbl_comunicado_posto_blackedecker ON  tbl_comunicado_posto_blackedecker.comunicado = tbl_comunicado.comunicado
													AND tbl_comunicado_posto_blackedecker.fabrica    = $login_fabrica
													AND tbl_comunicado_posto_blackedecker.posto      = $login_posto
		WHERE tbl_comunicado.fabrica = $login_fabrica
		AND   tbl_comunicado.obrigatorio_site IS TRUE
		AND   tbl_comunicado_posto_blackedecker.posto IS NULL
		ORDER BY tbl_comunicado.data DESC;";

$sql = "SELECT tbl_comunicado.comunicado                                       ,
				tbl_comunicado.descricao                                       ,
				TO_CHAR(tbl_comunicado.data,'DD/MM/YYYY') AS data              ,
				tbl_comunicado.produto                                         ,
				tbl_comunicado.tipo                                            ,
				tbl_produto.referencia                    AS produto_referencia,
				tbl_produto.descricao                     AS produto_descricao  
		FROM tbl_comunicado
		LEFT JOIN tbl_comunicado_posto_blackedecker ON tbl_comunicado_posto_blackedecker.comunicado = tbl_comunicado.comunicado
		LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_comunicado.produto
		WHERE tbl_comunicado_posto_blackedecker.data_confirmacao IS NULL
		AND   tbl_comunicado.fabrica                             = $login_fabrica
		AND   (UPPER(tbl_comunicado.tipo) <> 'MANUAL'          OR tbl_comunicado.tipo IS NULL)
		AND   (tbl_comunicado.destinatario = $login_tipo_posto OR tbl_comunicado.destinatario_especifico ILIKE '%$codigo_posto%')";

if ($pedido_em_garantia == "t") {
	$sql .=	" AND ( tbl_comunicado.pedido_em_garantia IS TRUE OR tbl_comunicado.pedido_em_garantia IS NULL )";
}
if ($pedido_faturado == "t") {
	$sql .=	" AND ( tbl_comunicado.pedido_faturado IS TRUE OR tbl_comunicado.pedido_faturado IS NULL )";
}
if ($suframa == "t") {
	$sql .=	" AND ( tbl_comunicado.suframa IS TRUE OR tbl_comunicado.suframa IS NULL )";
}
$sql .=	" ORDER BY tbl_comunicado.data DESC;";
$res = pg_exec($con,$sql);

if (pg_numrows($res) > 0) {
	echo "<div id='mainCol'>";
	echo "<div class='contentBlockLeft' style='background-color: #FFCC00;'>";
	echo "<b>COMUNICADOS</b>";
	echo "<br><br>";
	echo "<table width='500' border='1' cellspadding='0' cellpadding='2' style='border-collapse: collapse' bordercolor='#000000'>";
	echo "<tr class='Titulo'>";
	echo "<td>Data</td>";
	echo "<td>Produto</td>";
	echo "<td>Título</td>";
	echo "<td>Arquivo</td>";
	echo "</tr>";

	if ($S3_sdk_OK) {
		include_once S3CLASS;
		$s3 = new anexaS3('ve', (int) $login_fabrica);
	}

	for ($k = 0 ; $k < pg_numrows($res) ; $k++) {
		$data               = pg_result($res,$k,data);
		//$produto            = pg_result($res,$k,produto);
		$produto_referencia = pg_result($res,$k,produto_referencia);
		$produto_descricao  = pg_result($res,$k,produto_descricao);
		//$mensagem           = pg_result($res,$k,mensagem);
		$descricao          = pg_result($res,$k,descricao);
		$tipo               = pg_result($res,$k,tipo);
		$comunicado         = pg_result($res,$k,comunicado);
		//$extensao           = pg_result($res,$k,extensao);

		$cor = ($k % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

		echo "<tr class='Conteudo' bgcolor='$cor'>";
		echo "<td align='center'><input type='hidden' name='comunicado_$k' value='" . $comunicado . "'>" . $data . "</td>";
		echo "<td>";
		if (strlen($produto) > 0) echo $produto_referencia . " - " . $produto_descricao;
		else                      echo "&nbsp;";
		echo "</td>";
		echo "<td>";
		if (strlen($mensagem) > 0)
			echo "<div style='position: relative; float: right; width: 5; height: 5;'><acronym title='Mensagem: " . htmlspecialchars($mensagem) . "'><img border='0' src='imagens/comentario.gif' align='top' style='cursor: hand;'></acronym></div>";
		echo $descricao . "</td>";
		echo "<td align='center'>";

		if ($S3_online) {
			$tipo_s3 = in_array($tipo, explode(',', utf8_decode(anexaS3::TIPOS_VE))) ? 've' : 'co';
			if ($s3->tipo_anexo != $tipo_s3)
				$s3->set_tipo_anexoS3($tipo_s3);

			$s3->temAnexos($comunicado);
		}
		$fileLink = ($S3_online and $s3->temAnexo) ? $s3->url : "comunicados/$com_comunicado.$com_extensao";
		if (strlen($comunicado) > 0 && strlen($extensao) > 0) echo "<a href='$fileLink' targer='_blank'>Abrir arquivo</a>";

		else "&nbsp;";
		echo "</td>";
		echo "</tr>";
	}
	echo "</table>";
	echo "<br>";
	echo "<font size='1'><b>Obs.:</b> Passe o mouse sobre a marcação vermelha <img border='0' src='imagens/comentario.gif' align='top'> para visualizar a mensagem.</font>";
	echo "</div>";
	echo "<div class='contentBlockLeft'>";
	echo "<input type='checkbox' name='ciente' value='t'> Li e estou ciente do(s) comunicado(s) acima";
	echo "</div>";
	echo "</div>";
	echo "<input type='hidden' name='qtde_comunicados' value='" . pg_numrows($res) . "'>";
}
?>

<input type="hidden" name="btn_acao">
<img border="0" src="imagens/btn_continuar.gif" onclick="javascript: if (document.frm_comunicado.btn_acao.value == '' ) { document.frm_comunicado.btn_acao.value='continuar' ; document.frm_comunicado.submit() } else { alert ('Aguarde submissão') }" ALT="Continuar" style="cursor: pointer;">

</form>

<div id="footer">
	Telecontrol Networking Ltda - 2005<br>
	<a href="http://www.telecontrol.com.br" target="_blank">www.telecontrol.com.br</a><br>
	<font color='#fefefe'>Deus é o Provedor</font>
</div>

</body>

</html>
