<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia,call_center";
include 'autentica_admin.php';

$btn_acao = trim (strtolower ($_POST['btn_acao']));

$msg_erro = "";


// recebe as variaveis
if (strlen($mes) > 0) {
	$data_inicial_abertura = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
	$data_final_abertura   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
}
if($_POST["codigo_posto"])			$codigo_posto          = trim($_POST["codigo_posto"]);
if($_POST["consumidor_nome"])		$consumidor_nome       = trim($_POST["consumidor_nome"]);
if($_POST["tipo_os"])				$tipo_os            = trim($_POST["tipo_os"]);


if($_GET["codigo_posto"])			$codigo_posto          = trim($_GET["codigo_posto"]);
if($_GET["consumidor_nome"])		$consumidor_nome       = trim($_GET["consumidor_nome"]);
if($_GET["tipo_os"])				$tipo_os               = trim($_GET["tipo_os"]);


if(strlen($data_inicial_abertura) ==0 or strlen($data_final_abertura) == 0){
	$msg_erro .="Por favor, ESCOLHER MÊS E O ANO PARA FAZER PESQUISA";
}

if(strlen($codigo_posto) == 0) {
	$msg_erro .="POR FAVOR, ESCOLHER O POSTO PARA FAZER PESQUISA";
}

$layout_menu = "financeiro";
$title = "Relação de Ordens de Serviços Finalizadas";
include "cabecalho.php";

?>

<p>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

a.linkTitulo {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 0px solid;
	color: #ffffff
}

</style>

<?
if (strlen($msg_erro) > 0) {
	echo "<table width='600' align='center' border='0' cellspacing='1' cellpadding='1' class='error'>\n";
	echo "<tr>";
	echo "<td>$msg_erro</td>";
	echo "</tr>";
	echo "</table>\n";
}
echo "<BR>";
// BTN_NOVA BUSCA
echo "<TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
echo "<TR class='table_line'>";
echo "<td align='center' background='#D9E2EF'>";
echo "<a href='os_parametros_finalizada.php'><img src='imagens_admin/btn_nova_busca.gif'></a>";
echo "</td>";
echo "</TR>";
echo "</TABLE>";
				if($data_inicial_abertura <> 'dd/mm/aaaa' AND $data_final_abertura <> 'dd/mm/aaaa'){
					if(strlen($data_inicial_abertura) > 0 AND strlen($data_final_abertura) > 0 AND strlen($codigo_posto) > 0){
						$sql="SELECT tbl_os.sua_os                                       ,
									 tbl_os.consumidor_nome                              ,
									 to_char(tbl_os.finalizada,'DD/MM/YY') as finalizada ,
									 tbl_os.revenda_nome as nome_revenda                 ,
									 tbl_os.mao_de_obra                                  ,
									 tbl_posto.nome      as   nome_posto                 ,
									 tbl_posto_fabrica.codigo_posto                      ,
									 tbl_peca.referencia                                 ,
									 tbl_peca.descricao
								FROM tbl_os_produto
								JOIN tbl_os USING(os)
								JOIN tbl_os_item ON tbl_os_item.os_produto=tbl_os_produto.os_produto
								JOIN tbl_posto USING (posto)
								JOIN tbl_posto_fabrica ON  tbl_posto.posto           = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
								JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca
								LEFT JOIN tbl_revenda ON tbl_revenda.revenda = tbl_os.revenda
								WHERE tbl_posto_fabrica.codigo_posto='$codigo_posto'
								AND tbl_os.fabrica='$login_fabrica' 
								AND tbl_os.finalizada IS NOT NULL 
								AND tbl_os.consumidor_revenda='$tipo_os'";

						$data_inicial     = $data_inicial_abertura;
						$data_final       = $data_final_abertura;

						
						$sql .= " AND (tbl_os.finalizada BETWEEN '$data_inicial'  AND '$data_final') ";
					
				
						if (strlen($consumidor_nome) > 0 AND $tipo_os=='C'){
							$sql .= " AND tbl_os.consumidor_nome ILIKE '%".$consumidor_nome."%' ";
						}elseif(strlen($consumidor_nome) > 0 AND $tipo_os=='R'){
								$sql .= " AND tbl_os.revenda_nome ILIKE '%".$consumidor_nome."%' ";
						}
						$sql.=" ORDER BY finalizada,
										 sua_os";

						$resx=pg_exec($con,$sql);
					}
				}
				$num = (int)pg_num_rows($resx);

				//echo "num = $num";

if ($num == 0) {
	echo "<TABLE width='700' align='center' height='50'><TR><TD align='center'>Nenhum resultado encontrado.</TD></TR></TABLE>";
}else{
	$nome_posto         = trim(pg_result ($resx,0,nome_posto));
	$codigo_posto       = trim(pg_result ($resx,0,codigo_posto));
	echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";

	echo "<TR class='menu_top'>\n";
	echo "<TD colspan=9>POSTO $codigo_posto - $nome_posto</TD>\n";
	echo "</TR>\n";
	echo "<TR class='menu_top'>\n";
	echo "<TD>OS</TD>\n";
	echo "<TD width='135'>DATA FINALIZADA</TD>\n";
	if($tipo_os=='C') echo "<TD align=center>NOME DO CONSUMIDOR</TD>\n";
	elseif($tipo_os=='R') echo "<TD align=center>NOME DA REVENDA</TD>\n";
	echo "<TD width='130'>PEÇA</TD>\n";
	echo "<TD>MÃO DE OBRA </TD>\n";
	echo "</TR>\n";
	
	for ($i = 0 ; $i < $num ; $i++){

		$nome_revenda         = trim(pg_result ($resx,$i,nome_revenda));
		$sua_os             = trim(pg_result ($resx,$i,sua_os));
		$finalizada      = trim(pg_result ($resx,$i,finalizada));
		$consumidor_nome    = trim(pg_result ($resx,$i,consumidor_nome));
		$referencia = trim(pg_result ($resx,$i,referencia));
		$descricao  = trim(pg_result ($resx,$i,descricao));
		$mao_de_obra  = trim(pg_result ($resx,$i,mao_de_obra));

		if(strlen($mao_de_obra) == 0){
			$mao_de_obra = 0;
		}
		
		$cor = "#F7F5F0"; 
		$btn = "amarelo";
		if ($i % 2 == 0) {
			$cor = "#F1F4FA";
			$btn = "azul";
		}
		
		
		echo "<TR class='table_line' style='background-color: $cor;'>\n";
		echo "<TD nowrap>";
		echo "$sua_os";
		echo "</TD>\n";
		echo "<TD align='center' nowrap>$finalizada</TD>\n";
		echo "<TD nowrap>";
		if($tipo_os=='C') echo "$consumidor_nome";
		elseif($tipo_os=='R') echo "$nome_revenda";
		echo "</TD>\n";
		echo "<TD nowrap><ACRONYM TITLE=\"$referencia - $descricao\">".substr($descricao,0,17)."</ACRONYM></TD>\n";
		echo "<TD align='center'>R$ ". number_format($mao_de_obra, 2, ',', ' ')."</TD>\n";
		echo "</TR>\n";
	}

	echo "</TABLE>\n";


	
	flush();
		
	echo "<br><br>";
	echo "<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
	echo "<tr>";
	echo "<td align='center'><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Aguarde, processando arquivo PostScript</font></td>";
	echo "</tr>";
	echo "</table>";
	flush();
	
	$data = date ("d/m/Y H:i:s");

	echo `rm /tmp/assist/relatorio-consulta-os-finalizada-$login_fabrica.xls`;


	$fp = fopen ("/tmp/assist/relatorio-consulta-os-finalizada-$login_fabrica.html","w");

	fputs ($fp,"<html>");
	fputs ($fp,"<head>");
	fputs ($fp,"<title>Relação de Ordens de Serviços Finalizadas - $data");
	fputs ($fp,"</title>");
	fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
	fputs ($fp,"</head>");
	fputs ($fp,"<body>");
	
	fputs ($fp,"<table align='center' border='1' cellspacing='1' cellpadding='1'>\n");
	
	fputs ($fp, "<tr bgcolor='#FFCC00' align='center'>\n");
	fputs ($fp, "<td colspan='5'><FONT  COLOR='#FFFFFF'>POSTO $codigo_posto - $nome_posto</FONT></td>\n");
	fputs ($fp, "</tr>\n");

	fputs ($fp, "<tr bgcolor='#0000FF' align='center'>\n");
	fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>OS</FONT></TD>\n");
	fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>DATA FINALIZADA</FONT></TD>\n");
	if($tipo_os=='C') {
		fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>CONSUMIDOR</FONT></td>\n");
	}
	elseif($tipo_os=='R') {
		fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>REVENDA</FONT></td>\n");
	}
	fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>PEÇA</FONT></td>\n");
	fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>MÃO DE OBRA</FONT></td>\n");
	fputs ($fp, "</tr>\n");

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++){

		$nome_revenda         = trim(pg_result ($res,$i,nome_revenda));
		$sua_os             = trim(pg_result ($res,$i,sua_os));
		$finalizada      = trim(pg_result ($res,$i,finalizada));
		$consumidor_nome    = trim(pg_result ($res,$i,consumidor_nome));
		$referencia = trim(pg_result ($res,$i,referencia));
		$descricao  = trim(pg_result ($res,$i,descricao));
		$mao_de_obra  = trim(pg_result ($res,$i,mao_de_obra));

		$cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

		fputs ($fp, "<tr class='table_line' bgcolor='$cor;'>\n");


		fputs ($fp, "<TD nowrap>$sua_os</TD>\n");
		fputs ($fp, "<td align='center'><acronym title='Data FINALIZADA: $finalizada'  >$finalizada</acronym></td>\n");
		if($tipo_os=='C') {
			fputs ($fp, "<td nowrap><acronym title='Consumidor: $consumidor_nome'  >" . substr($consumidor_nome,0,15) . "</acronym></td>\n");
		} elseif($tipo_os=='R') {
			fputs ($fp, "<td nowrap><acronym title='REVENDA: $revenda_nome'  >" . substr($nome_revenda,0,15) . "</acronym></td>\n");
		}
		fputs ($fp, "<TD nowrap><ACRONYM TITLE=\"$referencia - $descricao\">".substr($descricao,0,17)."</ACRONYM></TD>\n");
		fputs ($fp, "<TD align='center'>R$ ".$mao_de_obra."</TD>\n");
		fputs ($fp, "</tr>\n");
	}
	fputs ($fp, "</table>\n");
	fputs ($fp, "<br>");
	fputs ($fp, "<table height='20'><tr class='menu_top'><td align='center'>Total de " . pg_numrows($res) . " resultado(s) encontrado(s).</td></tr></table>");

	fputs ($fp,"</body>");
	fputs ($fp,"</html>");
	fclose ($fp);


	$data = date("Y-m-d").".".date("H-i-s");

	echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/xls/relatorio-consulta-os-finalizada-$login_fabrica.$data.xls /tmp/assist/relatorio-consulta-os-finalizada-$login_fabrica.html`;
	
	echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
	echo"<tr>";
	echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='xls/relatorio-consulta-os-finalizada-$login_fabrica.$data.xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
	echo "</tr>";
	echo "</table>";
}


	echo "<TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
	echo "<TR class='table_line'>";
	echo "<td align='center' background='#D9E2EF'>";
	echo "<a href='os_parametros_finalizada.php'><img src='imagens_admin/btn_nova_busca.gif'></a>";
	echo "</td>";
	echo "</TR>";
	echo "</TABLE>";
	echo "<br>";

include "rodape.php"; 

?>