<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$ano    = $_GET['ano'];
$mes    = $_GET['mes'];
$estado = $_GET['estado'];
$linha  = $_GET['linha'];

$data_inicial = $ano . "-" . $mes . "-01 00:00:00";
$res = pg_exec ($con,"SELECT ('$data_inicial'::date + interval '1 month' - interval '1 day')::date");
$data_final = pg_result ($res,0,0);
$data_final = $data_final . " 23:59:59";

$title = "RELAÇÃO DE POSTOS POR ESTADO";

?>
<HTML>
<HEAD>
<TITLE><? echo $title; ?></TITLE>
<META NAME="Generator" CONTENT="EditPlus">
<META NAME="Author" CONTENT="">
<META NAME="Keywords" CONTENT="">
<META NAME="Description" CONTENT="">

<style type="text/css">

.titPreto14 {
	color: #000000;
	text-align: center;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.titPreto12 {
	color: #000000;
	text-align: left;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.titDatas12 {
	color: #000000;
	text-align: center;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.titChamada10{
	background-color: #596D9B;
	color: #ffffff;
	text-align: center;
	font:11px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.conteudo10 {
	color: #000000;

	font:10px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.bgTRConteudo1{
	background-color: #FEFEFF;
}

.bgTRConteudo2{
	background-color: #F9FCFF;
}

</style>

<p>

<TABLE WIDTH = '660' align = 'center'>
	<TR>
		<TD class='titPreto14' align = 'center'><B>RELAÇÃO DOS POSTOS DO ESTADO "<?ECHO $estado?>"</B></TD>
	</TR>
	<TR>
		<TD class='titDatas12'>PERÍODO: <? echo $mes."/".$ano ?></TD>
	</TR>
	<TR>
		<TD class='titPreto14'>&nbsp;</TD>
	</TR>
</TABLE>

<?
// ORDENAÇÃO DO SQL (VEM VIA GET)
switch ($_GET['order']){
	case 'codigo_posto':$order = "tbl_posto_fabrica.codigo_posto";	break;
	case 'nome':		$order = "tbl_posto.nome";					break;
	case 'cidade':		$order = "tbl_posto.cidade";				break;
	case 'mo':			$order = "os.mao_de_obra";					break;
	case 'pecas':		$order = "os.pecas";						break;
	case 'qtde':		$order = "os.qtde";							break;
	default:			$order = "tbl_posto_fabrica.codigo_posto";	break;
}

if ($_GET['type'] == 'ASC') 
	$typeRetorno = 'DESC';
else
	$typeRetorno = 'ASC';
?>

<TABLE width='660' cellspacing='1' cellpadding='2' border='0' align = 'center'>
<TR>
	<TD class='titChamada10' style='cursor: hand;text-decoration: underline;' onclick='javascript:document.location="<? echo "$PHP_SELF?ano=$ano&mes=$mes&estado=$estado&order=codigo_posto&type=$typeRetorno";?>";'>CÓDIGO</TD>
	<TD class='titChamada10' style='cursor: hand;text-decoration: underline;' onclick='javascript:document.location="<? echo "$PHP_SELF?ano=$ano&mes=$mes&estado=$estado&order=nome&type=$typeRetorno";?>";'>POSTO</TD>
	<TD class='titChamada10' style='cursor: hand;text-decoration: underline;' onclick='javascript:document.location="<? echo "$PHP_SELF?ano=$ano&mes=$mes&estado=$estado&order=cidade&type=$typeRetorno";?>";'>CIDADE</TD>
	<TD class='titChamada10' style='cursor: hand;text-decoration: underline;' onclick='javascript:document.location="<? echo "$PHP_SELF?ano=$ano&mes=$mes&estado=$estado&order=mo&type=$typeRetorno";?>";'>MO</TD>
	<TD class='titChamada10' style='cursor: hand;text-decoration: underline;' onclick='javascript:document.location="<? echo "$PHP_SELF?ano=$ano&mes=$mes&estado=$estado&order=pecas&type=$typeRetorno";?>";'>PEÇAS</TD>
	<TD class='titChamada10' style='cursor: hand;text-decoration: underline;' onclick='javascript:document.location="<? echo "$PHP_SELF?ano=$ano&mes=$mes&estado=$estado&order=qtde&type=$typeRetorno";?>";'>QTDE OS</TD>
</TR>
<?

if($login_fabrica == 42) {
	$data_extrato = " tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final'";
}else {
	$data_extrato = " tbl_extrato.aprovado BETWEEN '$data_inicial' AND '$data_final'";
}

//hd 55221 - coloquei o update depois pq se colocar o join das peças em um select só duplica o restante
$sql = "SELECT  tbl_posto_fabrica.codigo_posto,
		tbl_posto.nome,
		tbl_posto.cidade,
		CASE WHEN SUM   (tbl_os.mao_de_obra) IS NULL THEN 0 ELSE SUM (tbl_os.mao_de_obra)  END AS mao_de_obra,
		/*CASE WHEN SUM   (tbl_os.custo_peca)  IS NULL THEN 0 ELSE SUM   (tbl_os.custo_peca) END AS pecas,*/
		CASE WHEN COUNT (tbl_os.os)          IS NULL THEN 0 ELSE COUNT (tbl_os.os)         END AS qtde
	INTO TEMP tmp_gasto_por_posto_estado
	FROM    tbl_os
	JOIN    tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i=tbl_os.fabrica";
if (strlen($linha) > 0) $sql .= " JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha ";
$sql .= " JOIN    tbl_os_extra ON tbl_os_extra.os    = tbl_os.os AND tbl_os_extra.i_fabrica=tbl_os.fabrica
         JOIN    tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato AND tbl_os_extra.i_fabrica=tbl_extrato.fabrica
	 JOIN    tbl_posto   ON tbl_os.posto = tbl_posto.posto
	 JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
	 WHERE   $data_extrato 
		AND     tbl_os.fabrica            = $login_fabrica
		AND     tbl_posto_fabrica.fabrica = $login_fabrica ";
if (strlen($linha) > 0) $sql .= "AND tbl_linha.linha in ($linha) ";
$sql .= "   AND     tbl_posto_fabrica.contato_estado = '$estado'
	GROUP BY    tbl_posto_fabrica.codigo_posto,tbl_posto.nome,tbl_posto.cidade;
		
		ALTER TABLE tmp_gasto_por_posto_estado ADD column pecas double precision;

		UPDATE tmp_gasto_por_posto_estado SET pecas = x.pecas
		FROM
			(SELECT  tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome,
					tbl_posto.cidade,
					CASE WHEN SUM   (tbl_os_item.custo_peca * tbl_os_item.qtde) IS NULL THEN 0 ELSE SUM   (tbl_os_item.custo_peca * tbl_os_item.qtde) END AS pecas
			FROM    tbl_os
			JOIN    tbl_produto  ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i=tbl_os.fabrica";
if (strlen($linha) > 0) $sql .= " JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha ";
$sql .= "	JOIN  tbl_os_extra ON tbl_os_extra.os = tbl_os.os AND tbl_os_extra.i_fabrica=tbl_os.fabrica
		JOIN  tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato AND tbl_os_extra.i_fabrica=tbl_extrato.fabrica
		JOIN  tbl_posto           ON tbl_os.posto              = tbl_posto.posto
		JOIN  tbl_posto_fabrica   ON tbl_posto_fabrica.posto   = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		LEFT  JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
		LEFT  JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
		WHERE   $data_extrato
			AND     tbl_os.fabrica            = $login_fabrica
			AND     tbl_posto_fabrica.fabrica = $login_fabrica ";
if (strlen($linha) > 0) $sql .= " AND tbl_linha.linha in( $linha) ";
$sql .= "   AND     tbl_posto_fabrica.contato_estado = '$estado'
			GROUP BY    tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome,
						tbl_posto.cidade
			) as x
			WHERE x.codigo_posto = tmp_gasto_por_posto_estado.codigo_posto
			and   x.nome         = tmp_gasto_por_posto_estado.nome
			and   x.cidade       = tmp_gasto_por_posto_estado.cidade;
		
		SELECT * FROM tmp_gasto_por_posto_estado ORDER BY (mao_de_obra+pecas) DESC";



$res = pg_exec ($con,$sql);

if(pg_numrows($res) > 0){
	for($i=0; $i<pg_numrows($res); $i++){
		$codigo_posto= pg_result($res,$i,codigo_posto);
		$posto       = pg_result($res,$i,nome);
		$cidade      = pg_result($res,$i,cidade);
		$mo          = pg_result($res,$i,mao_de_obra);
		$pecas       = pg_result($res,$i,pecas);
		$qtde        = pg_result($res,$i,qtde);

		$mo          = number_format ($mo,2,",",".");
		$pecas       = number_format ($pecas,2,",",".");

		$cor = '2';
		if ($i % 2 == 0) $cor = '1';

		echo "<TR class='bgTRConteudo$cor'>\n";
		echo "	<TD class='conteudo10' align='left'>$codigo_posto</TD>\n";
		echo "	<TD class='conteudo10' align='left'>$posto</TD>\n";
		echo "	<TD class='conteudo10' align='left'>$cidade</TD>\n";
		echo "	<TD class='conteudo10' align='right'>$mo</TD>\n";
		echo "	<TD class='conteudo10' align='right'>$pecas</TD>\n";
		echo "	<TD class='conteudo10' align='center'>$qtde</TD>\n";
		echo "</TR>\n";
	}
}
?>

</TABLE>
</BODY>
</HTML>
