<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

$msg_erro = "";

if($_GET["acao"]) $acao = strtoupper($_GET["acao"]);

if($_GET["comunicado"]) $comunicado = $_GET["comunicado"];

$title = "Comunicados $login_fabrica_nome";
$layout_menu = "tecnica";

include "cabecalho.php";
?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Arial, Geneva, Helvetica, sans-serif;
	font-size: 11 px;
	font-weight: bold;
	color: #000000;
	background-color: #D9E2EF
}
.Conteudo {
	font-family: Verdana, Tahoma, Arial, Geneva, Helvetica, sans-serif;
	font-size: 11 px;
	font-weight: normal;
}
</style>

<br>

<?
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
/*
echo "Posto: $codigo_posto - $posto_nome<br>";
echo "Garantia: $pedido_em_garantia<br>";
echo "Faturado: $pedido_faturado<br>";
echo "Suframa:  $suframa<br><br>";
*/
##### COMUNICADO #####

if ($acao == "VER" && strlen($comunicado) > 0) {
	$sql =	"SELECT comunicado                                        ,
					remetente_email                                   ,
					TO_CHAR(tbl_comunicado.data,'dd/mm/yyyy') AS data ,
					descricao                                         ,
					mensagem
			FROM tbl_comunicado
			WHERE fabrica    = $login_fabrica
			AND   comunicado = $comunicado;";
	$res = pg_exec($con,$sql);
	
	if (pg_numrows($res) == 1) {
		echo "<table width='500' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";

		echo "<tr class='Titulo'>";
		echo "<td colspan='2'>COMUNICADO</td>";
		echo "</tr>";

		echo "<tr class='Titulo'>";
		echo "<td>REMETENTE</td>";
		echo "<td>DATA</td>";
		echo "</tr>";
		echo "<tr class='Conteudo' bgcolor='#F1F4FA'>";
		echo "<td>" . pg_result($res,0,remetente_email) . "</td>";
		echo "<td align='center'>" . pg_result($res,0,data) . "</td>";
		echo "</tr>";

		echo "<tr class='Titulo'>";
		echo "<td colspan='2'>ASSUNTO</td>";
		echo "</tr>";
		echo "<tr class='Conteudo' bgcolor='#F1F4FA'>";
		echo "<td colspan='2' align='center'>" . pg_result($res,0,descricao) . "</td>";
		echo "</tr>";

		echo "<tr class='Titulo'>";
		echo "<td colspan='2'>MENSAGEM</td>";
		echo "</tr>";
		echo "<tr class='Conteudo' bgcolor='#F1F4FA'>";
		echo "<td colspan='2' align='center'><b>Prezado(a) $posto_nome,</b><br><br>" . nl2br(pg_result($res,0,mensagem)) . "</td>";
		echo "</tr>";

		$jpg = "/var/www/assist/www/comunicados/$comunicado.jpg";
		$gif = "/var/www/assist/www/comunicados/$comunicado.gif";
		$pdf = "/var/www/assist/www/comunicados/$comunicado.pdf";
		$doc = "/var/www/assist/www/comunicados/$comunicado.doc";
		$xls = "/var/www/assist/www/comunicados/$comunicado.xls";
		
		if (file_exists($jpg) == true)
			$arquivo = "http://www.telecontrol.com.br/assist/comunicados/$comunicado.jpg";
		if (file_exists($gif) == true)
			$arquivo = "http://www.telecontrol.com.br/assist/comunicados/$comunicado.gif";
		if (file_exists($pdf) == true)
			$arquivo = "http://www.telecontrol.com.br/assist/comunicados/$comunicado.pdf";
		if (file_exists($doc) == true)
			$arquivo = "http://www.telecontrol.com.br/assist/comunicados/$comunicado.doc";
		if (file_exists($xls) == true)
			$arquivo = "http://www.telecontrol.com.br/assist/comunicados/$comunicado.xls";

		if (strlen($arquivo) > 0) {
			echo "<tr class='Titulo'>";
			echo "<td colspan='2'>ANEXO</td>";
			echo "</tr>";
			echo "<tr class='Conteudo' bgcolor='#F1F4FA'>";
			echo "<td colspan='2' align='center'><a href='$arquivo' target='_blank'>Clique aqui</td>";
			echo "</tr>";
		}

		echo "</table>";
		echo "<br>";
	}
	echo "<p align='center'><a href='$PHP_SELF?'>Voltar</a></p>";
}

if ($acao == "ANTIGOS") {

	##### COMUNICADOS ANTIGOS #####
	$sql =	"SELECT 	tbl_comunicado_blackedecker.comunicado                                     ,
						tbl_comunicado_blackedecker.assunto                                        ,
						tbl_comunicado_blackedecker.destinatario_especifico                        ,
						to_char(tbl_comunicado_blackedecker.data_envio,'DD/MM/YYYY')             AS data_envio, 
						to_char(tbl_comunicado_posto_blackedecker.data_confirmacao,'DD/MM/YYYY') AS data_confirmacao,
						tbl_comunicado_blackedecker.pede_peca_garantia                             ,
						tbl_comunicado_blackedecker.pede_peca_faturada                             ,
						tbl_comunicado_blackedecker.suframa
				FROM	tbl_comunicado_blackedecker 
				JOIN    tbl_comunicado_posto_blackedecker ON tbl_comunicado_posto_blackedecker.comunicado = tbl_comunicado_blackedecker.comunicado
				JOIN    tbl_posto            ON tbl_posto.posto                 = tbl_comunicado_posto_blackedecker.posto
				WHERE   tbl_comunicado_blackedecker.destinatario_especifico ILIKE '%$codigo_posto%'
				AND     tbl_comunicado_posto_blackedecker.posto = $login_posto
				ORDER BY tbl_comunicado_blackedecker.comunicado DESC ";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			if ($i == 0) {
				echo "<table width='500' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
				echo "<tr class='Titulo'>";
				echo "<td colspan='2' align='center'>COMUNICADOS ANTIGOS</td>";
				echo "</tr>";
				echo "<tr class='Titulo'>";
				echo "<td align='center'>DESCRIÇÃO</td>";
				echo "<td align='center'>DATA</td>";
				echo "</tr>";
			}

			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;

			echo "<tr class='Conteudo' bgcolor='$cor'>";
			echo "<td><a href='comunicados_blackedecker_visualiza?comunicado=" . pg_result($res,$i,comunicado) . "' targer='_blank'>" . pg_result($res,$i,assunto) . "</a></td>";
			echo "<td align='center'>" . pg_result($res,$i,data_envio) . "</td>";
			echo "</tr>";
		}
		echo "</table>";
		echo "<br>";
	}

	echo "<p align='center'><a href='$PHP_SELF?'>Voltar</a></p>";

	echo "<p align='center'><a href='$PHP_SELF?acao='>Comunicados Novos</a></p>";

}

if ($acao != "ANTIGOS" && $acao != "VER") {

	##### COMUNICADOS NOVOS #####

	$sql =	"SELECT tbl_comunicado.comunicado                                        ,
					tbl_comunicado.descricao                                         ,
					TO_CHAR(tbl_comunicado.data,'dd/mm/yyyy') AS data 
			FROM tbl_comunicado
			LEFT JOIN tbl_comunicado_posto_blackedecker ON tbl_comunicado_posto_blackedecker.comunicado = tbl_comunicado.comunicado
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
		for ($j = 0 ; $j < pg_numrows($res) ; $j++) {
	/*		$qtde_destinatario_especifico = explode(",", pg_result($res,$i,destinatario_especifico));
			for ($j = 0 ; $j <= count($qtde_destinatario_especifico) ; $j++) {
				$destinatario_especifico = str_replace("'", "", $qtde_destinatario_especifico[$j]);
				$destinatario_especifico = str_replace("(", "", $destinatario_especifico);
				$destinatario_especifico = str_replace(")", "", $destinatario_especifico);
				if ($destinatario_especifico == $codigo_posto) {
					echo "teste";
				}
			}*/

			if ($j == 0) {
				echo "<table width='500' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
				echo "<tr class='Titulo'>";
				echo "<td colspan='2' align='center'>COMUNICADOS NOVOS</td>";
				echo "</tr>";
				echo "<tr class='Titulo'>";
				echo "<td align='center'>DESCRIÇÃO</td>";
				echo "<td align='center'>DATA</td>";
				echo "</tr>";
			}

			echo "<tr class='Conteudo'>";
			echo "<td><a href='$PHP_SELF?acao=VER&comunicado=" . pg_result($res,$j,comunicado) . "'>" . pg_result($res,$j,descricao) . "</a></td>";
			echo "<td align='center'>" . pg_result($res,$j,data) . "</td>";
			echo "</tr>";
		}
		echo "</table>";
		echo "<br>";
	}

	##### 10 COMUNICADOS MAIS RECENTES #####

	$sql =	"SELECT comunicado                                        ,
					descricao                                         ,
					TO_CHAR(tbl_comunicado.data,'dd/mm/yyyy') AS data 
			FROM    tbl_comunicado
			WHERE   (tbl_comunicado.fabrica     = $login_fabrica OR tbl_comunicado.destinatario_especifico ILIKE '%$codigo_posto%' OR tbl_comunicado.destinatario = $login_tipo_posto)
			AND     UPPER(tbl_comunicado.tipo) <> 'MANUAL' ";
	
	if ($pedido_em_garantia == "t") {
		$sql .=	" AND ( pedido_em_garantia IS TRUE OR pedido_em_garantia IS NULL )";
	}
	if ($pedido_faturado == "t") {
		$sql .=	" AND ( pedido_faturado IS TRUE OR pedido_faturado IS NULL )";
	}
	if ($suframa == "t") {
		$sql .=	" AND ( suframa IS TRUE OR suframa IS NULL )";
	}
	$sql .=	" ORDER BY tbl_comunicado.data DESC LIMIT 10;";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
	/*		$qtde_destinatario_especifico = explode(",", pg_result($res,$i,destinatario_especifico));
			for ($j = 0 ; $j <= count($qtde_destinatario_especifico) ; $j++) {
				$destinatario_especifico = str_replace("'", "", $qtde_destinatario_especifico[$j]);
				$destinatario_especifico = str_replace("(", "", $destinatario_especifico);
				$destinatario_especifico = str_replace(")", "", $destinatario_especifico);
				if ($destinatario_especifico == $codigo_posto) {
					echo "teste";
				}
			}*/

			if ($i == 0) {
				echo "<table width='500' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
				echo "<tr class='Titulo'>";
				echo "<td colspan='2' align='center'>10 COMUNICADOS MAIS RECENTES</td>";
				echo "</tr>";
				echo "<tr class='Titulo'>";
				echo "<td align='center'>DESCRIÇÃO</td>";
				echo "<td align='center'>DATA</td>";
				echo "</tr>";
			}

			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;

			echo "<tr class='Conteudo' bgcolor='$cor'>";
			echo "<td><a href='$PHP_SELF?acao=VER&comunicado=" . pg_result($res,$i,comunicado) . "'>" . pg_result($res,$i,descricao) . "</a></td>";
			echo "<td align='center'>" . pg_result($res,$i,data) . "</td>";
			echo "</tr>";
		}
		echo "</table>";
		echo "<br>";
	}

	echo "<p align='center'><a href='$PHP_SELF?acao=ANTIGOS'>Comunicados Antigos</a></p>";

}

include "rodape.php"; 
?>
