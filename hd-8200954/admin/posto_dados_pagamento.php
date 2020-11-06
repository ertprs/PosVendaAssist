<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = "financeiro";
$title = traduz("DADOS DO BANCO PARA PAGAMENTO DE POSTOS");

include 'cabecalho.php';

?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 9px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.titulo_coluna a{
	text-decoration:none;
	color:#FFFFFF;
}
.Conteudo {
	font-family: Arial;
	font-size: 9px;
	font-weight: normal;
}
.Mes{
	font-size: 8px;
}

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

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
}

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}
</style>


<?
$pais = $_POST["pais"];
$ordem = $_GET['ordem'];

if($login_fabrica == 20){
// MLG 2009-08-04 HD 136625
    $sql = 'SELECT pais,nome FROM tbl_pais';
    $res = pg_query($con,$sql);
    $p_tot = pg_num_rows($res);
    for ($i; $i<$p_tot; $i++) {
        list($p_code,$p_nome) = pg_fetch_row($res, $i);
    	$sel_paises .= "\t\t\t\t<option value='$p_code'";
        $sel_paises .= ($pais==$p_code)?" selected":"";
        $sel_paises .= ">$p_nome</option>\n";
    }
	echo "<form name='frm' method='post' action='$PHP_SELF'>";?>
		<select name='pais' size='1' class='frm'>
		 <option></option>
        <?echo $sel_paises;?>
		</select>
<?
	echo "<input type='submit' name='btn_ok' value='OK'>";
	echo "</form>";
}
if($ordem){
	$sql = "SELECT tbl_posto.nome             ,
		tbl_posto_fabrica.codigo_posto    ,
		tbl_posto_fabrica.banco           ,
		tbl_posto_fabrica.nomebanco       ,
		tbl_posto_fabrica.agencia         ,
		tbl_posto_fabrica.conta           ,
		tbl_posto_fabrica.tipo_conta      ,
		tbl_posto_fabrica.categoria       ,
		tbl_posto_fabrica.desconto        ,
		tbl_posto_fabrica.credenciamento  ,
		tbl_posto_fabrica.favorecido_conta 
	FROM tbl_posto 
	JOIN tbl_posto_fabrica USING(posto) 
	WHERE fabrica = $login_fabrica ";
	if(strlen($pais)>0) $sql .= "AND pais = '$pais'";
	$sql .= " ORDER BY ".$ordem." , tbl_posto.nome;";

}
else{
	$sql = "SELECT tbl_posto.nome             ,
			tbl_posto_fabrica.codigo_posto    ,
			tbl_posto_fabrica.banco           ,
			tbl_posto_fabrica.nomebanco       ,
			tbl_posto_fabrica.agencia         ,
			tbl_posto_fabrica.conta           ,
			tbl_posto_fabrica.tipo_conta      ,
			tbl_posto_fabrica.categoria       ,
			tbl_posto_fabrica.desconto        ,
			tbl_posto_fabrica.credenciamento  ,
			tbl_posto_fabrica.favorecido_conta 
		FROM tbl_posto 
		JOIN tbl_posto_fabrica USING(posto) 
		WHERE fabrica = $login_fabrica ";
		if(strlen($pais)>0) $sql .= "AND pais = '$pais'";
		$sql .= " ORDER BY tbl_posto_fabrica.credenciamento , tbl_posto.nome;";
}
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
?>	<table width="700" border="0" cellpadding="0" cellspacing="0" align="center"  >
	<tr style="font-size:12px;">
		<td bgcolor="#FF9999" >&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign="middle" align="left">&nbsp;<b><?=traduz('Descredenciados')?></b></td>
	</tr>
	</table>

<?
	echo "<br><table border='0' cellpadding='2' cellspacing='1'  align='center' class='tabela'>";
	echo "<tr class='titulo_coluna'>";
	echo "<td><a href='?ordem=nome'>".traduz("Nome Posto")." <img src='imagens/ordena_desc.gif' width='3%'></a></td>";
	echo "<td><a href='?ordem=codigo_posto'>".traduz("Código Posto")."</a></td>";
	echo "<td><a href='?ordem=banco'>".traduz("Banco")."</a></td>";
	echo "<td><a href='?ordem=nomebanco'>".traduz("Nome do Banco")."</a></td>";
	echo "<td><a href='?ordem=agencia'>".traduz("Agência")."</a></td>";
	echo "<td><a href='?ordem=conta'>".traduz("Conta")."</a></td>";
	echo "<td><a href='?ordem=tipo_conta'>".traduz("Tipo Conta")."</a></td>";
	echo "<td><a href='?ordem=desconto'>".traduz("Desconto")."</a></td>";
	echo "<td><a href='?ordem=categoria'>".traduz("Categoria")."</a></td>";
	if ($login_fabrica == 45) { // hd 62937
		echo "<td>".traduz("Favorecido")."</td>";
	}
	echo "</tr>";

	for ($i=0; $i<pg_numrows($res); $i++){

		$nome                    = trim(pg_result($res,$i,nome))             ;
		$codigo_posto            = trim(pg_result($res,$i,codigo_posto))     ;
		$banco                   = trim(pg_result($res,$i,banco))            ;
		$nomebanco               = trim(pg_result($res,$i,nomebanco))        ;
		$agencia                 = trim(pg_result($res,$i,agencia))          ;
		$conta                   = trim(pg_result($res,$i,conta))            ;
		$tipo_conta              = trim(pg_result($res,$i,tipo_conta))       ;
		$desconto                = trim(pg_result($res,$i,desconto))         ;
		$categoria               = trim(pg_result($res,$i,categoria))        ;
		$credenciamento          = trim(pg_result($res,$i,credenciamento))   ;
		$favorecido_conta        = trim(pg_result($res,$i,favorecido_conta)) ;

		if($cor=="#F1F4FA")$cor = '#F7F5F0';
		else               $cor = '#F1F4FA';
		if($credenciamento == "DESCREDENCIADO") $cor = '#FF9999';
		echo "<tr class='Conteudo'>";
		echo "<td bgcolor='$cor' align='left'>$nome</td>";
		echo "<td bgcolor='$cor' align='left'>$codigo_posto</td>";
		echo "<td bgcolor='$cor' align='left'>$banco</td>";
		echo "<td bgcolor='$cor' align='center'>$nomebanco</td>";
		echo "<td bgcolor='$cor' align='center'>$agencia</td>";
		echo "<td bgcolor='$cor' align='center'>$conta</td>";
		echo "<td bgcolor='$cor' align='center'>$tipo_conta</td>";
		echo "<td bgcolor='$cor' align='center'>$desconto</td>";
		echo "<td bgcolor='$cor' align='left'>$categoria</td>";
		if ($login_fabrica == 45) { // hd 62937
			echo "<td bgcolor='$cor' align='left'>$favorecido_conta</td>";
		}
		echo "</tr>";
	}
	echo "</table>";

	flush();
	$data = date ("d/m/Y H:i:s");

	$arquivo_nome     = "relatorio-dados-bancarios-$login_fabrica.xls";
	$path             = "/www/assist/www/admin/xls/";
	$path_tmp         = "/tmp/";

	$arquivo_completo     = $path.$arquivo_nome;
	$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

	echo `rm $arquivo_completo_tmp `;
	echo `rm $arquivo_completo `;

	$fp = fopen ($arquivo_completo_tmp,"w");

	fputs ($fp,"<html>");
	fputs ($fp,"<head>");
	fputs ($fp,"<title>Relatório de Dados Bancários - $data");
	fputs ($fp,"</title>");
	fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
	fputs ($fp,"</head>");
	fputs ($fp,"<body>");
	fputs ($fp, "<br><table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center' >");
	fputs ($fp, "<tr class='Titulo'>");
	fputs ($fp, "<td background='imagens_admin/azul.gif'>NOME POSTO</td>");
	fputs ($fp, "<td background='imagens_admin/azul.gif'>CÓDIGO POSTO</td>");
	fputs ($fp, "<td background='imagens_admin/azul.gif'>BANCO</td>");
	fputs ($fp, "<td background='imagens_admin/azul.gif'>NOME DO BANCO</td>");
	fputs ($fp, "<td background='imagens_admin/azul.gif'>AGENCIA</td>");
	fputs ($fp, "<td background='imagens_admin/azul.gif'>CONTA</td>");
	fputs ($fp, "<td background='imagens_admin/azul.gif'>TIPO_CONTA</td>");
	fputs ($fp, "<td background='imagens_admin/azul.gif'>DESCONTO</td>");
	fputs ($fp, "<td background='imagens_admin/azul.gif'>CATEGORIA</td>");
	fputs ($fp, "<td background='imagens_admin/azul.gif'>FAVORECIDO</td>");
	fputs ($fp, "</tr>");

	for ($i=0; $i<pg_numrows($res); $i++){

		$nome                    = trim(pg_result($res,$i,nome))             ;
		$codigo_posto            = trim(pg_result($res,$i,codigo_posto))     ;
		$banco                   = trim(pg_result($res,$i,banco))            ;
		$nomebanco               = trim(pg_result($res,$i,nomebanco))        ;
		$agencia                 = trim(pg_result($res,$i,agencia))          ;
		$conta                   = trim(pg_result($res,$i,conta))            ;
		$tipo_conta              = trim(pg_result($res,$i,tipo_conta))       ;
		$desconto                = trim(pg_result($res,$i,desconto))         ;
		$categoria               = trim(pg_result($res,$i,categoria))        ;
		$credenciamento          = trim(pg_result($res,$i,credenciamento))   ;
		$favorecido_conta        = trim(pg_result($res,$i,favorecido_conta)) ;

		if($cor=="#F1F4FA")$cor = '#F7F5F0';
		else               $cor = '#F1F4FA';
		if($credenciamento == "DESCREDENCIADO") $cor = '#FF9999';
		fputs ($fp, "<tr class='Conteudo'>");
		fputs ($fp, "<td bgcolor='$cor' align='left'>$nome</td>");
		fputs ($fp, "<td bgcolor='$cor' align='left'>$codigo_posto</td>");
		fputs ($fp, "<td bgcolor='$cor' align='left'>$banco</td>");
		fputs ($fp, "<td bgcolor='$cor' align='center'>$nomebanco</td>");
		fputs ($fp, "<td bgcolor='$cor' align='center'>$agencia</td>");
		fputs ($fp, "<td bgcolor='$cor' align='center'>$conta</td>");
		fputs ($fp, "<td bgcolor='$cor' align='center'>$tipo_conta</td>");
		fputs ($fp, "<td bgcolor='$cor' align='center'>$desconto</td>");
		fputs ($fp, "<td bgcolor='$cor' align='left'>$categoria</td>");
		fputs ($fp, "<td bgcolor='$cor' align='left'>$favorecido_conta</td>");
		fputs ($fp, "</tr>");
	}
	fputs ($fp, "</table>");
	echo ` cp $arquivo_completo_tmp $path `;
	$data = date("Y-m-d").".".date("H-i-s");

	echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f $arquivo_completo $arquivo_completo_tmp `;
	if ($login_fabrica == 45) { // HD 62937
		echo "<br>";
		echo"<table width='700' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo"<tr>";
		echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='xls/$arquivo_nome'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
		echo "</tr>";
		echo "</table>";
	}
}

?>
<? include "rodape.php" ?>
