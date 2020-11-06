<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

$layout_menu = "financeiro";
$title = "Relatório de Valores de extratos";

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
.Conteudo {
	font-family: Arial;
	font-size: 9px;
	font-weight: normal;
}
.Mes{
	font-size: 8px;
}
</style>


<?

//--=== RESULTADO DA PESQUISA ====================================================--\\


		$sql = "select 	tbl_posto_fabrica.codigo_posto, 
						tbl_black_questionario.* 
						from tbl_black_questionario 
						JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto= tbl_black_questionario.posto 
						AND tbl_posto_fabrica.fabrica=1;";

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {

		flush();
	
		echo "<br><br>";
		echo "<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo "<tr>";
		echo "<td align='center'><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Aguarde, processando arquivo PostScript</font></td>";
		echo "</tr>";
		echo "</table>";
	
		flush();
	
		$data = date ("dmY");

		echo `rm /tmp/assist/pesquisa_black.xls`;

		$fp = fopen ("/tmp/assist/pesquisa_black.html","w");

		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>Pesquisa Black");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");


		fputs ($fp,"<br><table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center'>");
		fputs ($fp,"<tr class='Titulo'>");
		fputs ($fp,"<td >codigo posto</td>");
		fputs ($fp,"<td colspan='5'>1. Marque quais as linhas de produto Black & Decker sua empresa atende:</td>");
		fputs ($fp,"<td >2. Sua empresa tem técnicos treinados?</td>");
		fputs ($fp,"<td >3. Marque os demais fabricantes além da Black & Decker que sua empresa é autorizada.</td>");
		fputs ($fp,"<td colspan='2'>4. Fez treinamento com outras fabricantes?</td>");
		fputs ($fp,"<td colspan='2'>5. Sua empresa é autorizada de alguma marca de ferramentas pneumáticas?</td>");
		fputs ($fp,"<td colspan='2'>6. Alguns de seus técnicos já participaram de treinamento de ferramentas pneumáticas?</td>");
		fputs ($fp,"</tr>");

		for ($i=0; $i<pg_numrows($res); $i++){

		$codigo_posto              = trim(pg_result($res,$i,codigo_posto))            ;
		$eletro                    = trim(pg_result($res,$i,eletro))                  ;
		$dewalt                    = trim(pg_result($res,$i,dewalt))                  ;
		$black                     = trim(pg_result($res,$i,black))                   ;
		$lavadora                  = trim(pg_result($res,$i,lavadora))                ;
		$nome_tecnico              = trim(pg_result($res,$i,nome_tecnico))            ;
		$fabricantes               = trim(pg_result($res,$i,fabricantes))             ;
		$treinamento               = trim(pg_result($res,$i,treinamento))             ;
		$treinamento_fabrica       = trim(pg_result($res,$i,treinamento_fabrica))     ;
		$pneumatico                = trim(pg_result($res,$i,pneumatico))              ;
		//autorizada pneumatica
		$pneumatico_fabrica        = trim(pg_result($res,$i,pneumatico_fabrica))      ;
		$pneumatico_treinamento    = trim(pg_result($res,$i,pneumatico_treinamento))  ;
		//fez treinamento com pneumatica
		$pneumatico_treinamento_fabrica = trim(pg_result($res,$i,pneumatico_treinamento_fabrica));
		

		if($eletro=="t"){     $eletro="Eletro";          }else{$eletro="";}
		if($dewalt=="t"){     $dewalt="Dewalt";          }else{$dewalt="";}
		if($black=="t"){      $black="Black & Decker";   }else{$black="";}
		if($lavadora=="t"){   $lavadora="Lavadora";      }else{$lavadora="";}
		if($compressor=="t"){ $compressor="Compressores";}else{$compressor="";}
		if($treinamento=="t"){$treinamento="Sim";        }else{$treinamento="Não";}
		if($pneumatico=="t"){ $pneumatico="Sim";         }else{$pneumatico="Não";}
		if($pneumatico_treinamento=="t"){$pneumatico_treinamento="Sim";}else{$pneumatico_treinamento="Não";}
		if(strlen($treinamento_fabrica)==0){$treinamento_fabrica="";}
		if($cor=="#F1F4FA")$cor = '#F7F5F0';
		else               $cor = '#F1F4FA';

		fputs ($fp,"<tr class='Conteudo'>");
	
		fputs ($fp,"<td  bgcolor='$cor'>$codigo_posto</td>");
		fputs ($fp,"<td  bgcolor='$cor'>$eletro</td>");// 1 
		fputs ($fp,"<td  bgcolor='$cor'>$dewalt</td>");// 1 
		fputs ($fp,"<td  bgcolor='$cor'>$black</td>");// 1 
		fputs ($fp,"<td  bgcolor='$cor'>$lavadora</td>");// 1 
		fputs ($fp,"<td  bgcolor='$cor'>$compressor</td>");// 1 

		fputs ($fp,"<td bgcolor='$cor' >$nome_tecnico</td>");// 2 
		fputs ($fp,"<td  bgcolor='$cor'>$fabricantes</td>");// 3

		fputs ($fp,"<td  bgcolor='$cor'>$treinamento</td>");// 4
		fputs ($fp,"<td  bgcolor='$cor'>$treinamento_fabrica</td>");// 4

		fputs ($fp,"<td  bgcolor='$cor'>$pneumatico</td>");// 5
		fputs ($fp,"<td  bgcolor='$cor'>$pneumatico_fabrica</td>");// 5

		fputs ($fp,"<td  bgcolor='$cor'>$pneumatico_treinamento</td>");// 6
		fputs ($fp,"<td  bgcolor='$cor'>$pneumatico_treinamento_fabrica</td>");// 6
		fputs ($fp,"</tr>");

		}

		fputs ($fp,"</table>");
		fputs ($fp,"</body>");
		fputs ($fp,"</html>");
		fclose ($fp);

		echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/xls/pesquisa_black.xls /tmp/assist/pesquisa_black.html`;
		
		echo "<table  border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo "<tr>";
		echo "<td><img src='imagens/excell.gif'></td><td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='xls/pesquisa_black.xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
		echo "</tr>";
		echo "</table>";
	}

?>
