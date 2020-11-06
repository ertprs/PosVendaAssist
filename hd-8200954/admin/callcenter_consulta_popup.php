<?
require_once 'dbconfig.php';
require_once 'includes/dbconnect-inc.php';

$mesmo_dia         = $_GET['mesmo_dia'];
$dia1              = $_GET['dia1'];
$dias2             = $_GET['dias2'];
$dias3             = $_GET['dias3'];
$mais_dias         = $_GET['mais_dias'];
$total_ocorrencias = $_GET['total_ocorrencias'];
?>

<html>
<head>
<title>Relatório Callcenter - Chamados</title>
</head>
<FRAMESET ROWS="25%,*,0%" FRAMEBORDER="0" FRAMESPACING="0">
  <FRAME SRC="callcenter_consulta_info.php" NAME="superior" NORESIZE SCROLLING="NO">
  <FRAME <? echo "SRC='callcenter_consulta_imagem_2r.php?mesmo_dia=$mesmo_dia&dia1=$dia1&dias2=$dias2&dias3=$dias3&mais_dias=$mais_dias&total_ocorrencias=$total_ocorrencias'" ?> NAME="inferior" NORESIZE >
</FRAMESET>
  <noframes>
  <body>
  </body>
  </noframes>
</frameset>
</html>