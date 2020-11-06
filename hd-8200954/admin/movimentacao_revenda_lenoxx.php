<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="financeiro";
include "autentica_admin.php";

$layout_menu = "financeiro";
$title = "Movimentação das Revendas";

include "cabecalho.php";

?>

<style type="text/css">
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10PX	;
	font-weight: bold;
	border: 1px solid;
;
	background-color: #D9E2EF
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #FFFFFF
}

</style>

<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>


<?

echo "<FORM method = 'POST' action='$PHP_SELF' name='FORMULARIO'>";
echo "<input type='hidden' name='btnacao' value=''>";

echo "<TABLE width='600' align='center' border='0' cellspacing='5' cellpadding='3' border='0'>";

echo "<TR class='menu_top'>";
echo "<TD align='center' colspan='4'>Digite o intervalo de datas para gerar o relatório</TD>";
echo "</TR>";


echo "<TR class='table_line2'>\n";

echo "	<TD WIDTH='20%'></TD>";
echo "	<TD ALIGN='left'>";
echo "	Data Inicial ";
echo "	<INPUT size='12' maxlength='10' TYPE='text' NAME='data_inicial' class='frm' id='data_inicial' value='$data_inicial'>&nbsp;\n";
echo "	</TD>\n";
echo "	<TD ALIGN='left'>";
echo "	Data Final ";
echo "	<INPUT size='12' maxlength='10' TYPE='text' NAME='data_final' class='frm' id='data_final' value='$data_final'>&nbsp;\n";
echo "</TD>\n";
echo "	<TD WIDTH='20%'></TD>";
echo "</TR>\n";

echo "<TR><TD colspan='4' align='center' background='#D9E2EF'>";
echo "<img src='imagens/btn_continuar.gif' style=\"cursor:pointer\" onclick=\"javascript: if (document.FORMULARIO.btnacao.value == '' ) { document.FORMULARIO.btnacao.value='pesquisar' ; document.FORMULARIO.submit() } else { alert ('Aguarde submissão') }\" ALT=\"Continuar com Ordem de Serviço\" border='0'></TD></TR>";
echo "</TABLE>";
echo "</FORM>";

if($_POST["pesquisar"])    $btnacao = trim($_POST["btnacao"]);

if ($btnacao=='pesquisar') {
	$data_inicial = $_POST['data_inicial'];
	if (strlen($_POST['data_inicial']) > 0) $data_inicial = $_POST['data_inicial'];
	$data_final   = $_POST['data_final'];
	if (strlen($_POST['data_final']) > 0) $data_final = $_POST['data_final'];

	$data_inicialx = str_replace (" " , "" , $data_inicial);
	$data_inicialx = str_replace ("-" , "" , $data_inicialx);
	$data_inicialx = str_replace ("/" , "" , $data_inicialx);
	$data_inicialx = str_replace ("." , "" , $data_inicialx);

	$data_finalx = str_replace (" " , "" , $data_final);
	$data_finalx = str_replace ("-" , "" , $data_finalx);
	$data_finalx = str_replace ("/" , "" , $data_finalx);
	$data_finalx = str_replace ("." , "" , $data_finalx);

	if (strlen ($data_inicialx) == 6) $data_inicialx = substr ($data_inicialx,0,4) . "20" . substr ($data_inicialx,4,2);
	if (strlen ($data_finalx)   == 6) $data_finalx   = substr ($data_finalx ,0,4) . "20" . substr ($data_finalx ,4,2);

	if (strlen ($data_inicialx) > 0) $data_inicialx = substr ($data_inicialx,0,2) . "/" . substr ($data_inicialx,2,2) . "/" . substr ($data_inicialx,4,4);
	if (strlen ($data_finalx)   > 0) $data_finalx   = substr ($data_finalx,0,2)   . "/" . substr ($data_finalx,2,2)   . "/" . substr ($data_finalx,4,4);

	if (strlen ($data_inicialx) < 8) $data_inicialx = date ("d/m/Y");
		$data_inicialx = substr ($data_inicialx,6,4) . "-" . substr ($data_inicialx,3,2) . "-" . substr ($data_inicialx,0,2);

	if (strlen ($data_finalx) < 10) $data_finalx = date ("d/m/Y");
		$data_finalx = substr ($data_finalx,6,4) . "-" . substr ($data_finalx,3,2) . "-" . substr ($data_finalx,0,2);

	$sql = "SELECT COUNT(tbl_os_extra.os) AS qtde_os
			FROM tbl_extrato
			JOIN tbl_os_extra USING(extrato)
			WHERE tbl_extrato.fabrica     = $login_fabrica 
			AND tbl_extrato.data_geracao BETWEEN '$data_inicialx 00:00:00' AND '$data_finalx 23:59:59'
			AND tbl_extrato.liberado NOTNULL
			AND tbl_extrato.posto NOT IN ('6359','14301','20321')";
	$res = pg_exec($con,$sql);
	$qtde_os_total = trim(pg_result($res,0,qtde_os));


	$sql =	"SELECT substr(tbl_revenda.cnpj,1,8) as revenda_cnpj,
			sum(tbl_os.pecas) as pecas,
			sum(tbl_os.mao_de_obra) as mao_de_obra,
			count(tbl_os.os) AS qtde_os
	INTO TEMP tmp_mov_revenda_lenoxx_$login_admin
	FROM tbl_os_extra
	JOIN tbl_extrato USING(extrato)
	JOIN tbl_os on tbl_os_extra.os =tbl_os.os and tbl_os.fabrica=$login_fabrica
	JOIN tbl_revenda on tbl_revenda.revenda = tbl_os.revenda
	WHERE tbl_os.fabrica     = $login_fabrica 
	AND tbl_extrato.data_geracao BETWEEN '$data_inicialx 00:00:00' AND '$data_finalx 23:59:59'
	AND tbl_extrato.liberado NOTNULL
	AND tbl_extrato.posto NOT IN ('6359','14301','20321')
	GROUP BY substr(tbl_revenda.cnpj,1,8)
	ORDER BY  substr(tbl_revenda.cnpj,1,8);

	SELECT	*
	FROM tmp_mov_revenda_lenoxx_$login_admin ;";

	$res = pg_exec($con,$sql);

	if (pg_numrows ($res) > 0) {

		$data = date ("d-m-Y");

		$arquivo_nome     = "movimentacao-revenda-lenoxx-$data.xls";
		$path             = "/www/assist/www/admin/xls/";
		$path_tmp         = "/tmp/assist/";

		$arquivo_completo     = $path.$arquivo_nome;
		$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

		echo `rm $arquivo_completo_tmp `;
		echo `rm $arquivo_completo_tmp.zip `;
		echo `rm $arquivo_completo.zip `;
		echo `rm $arquivo_completo `;

		$fp = fopen ($arquivo_completo_tmp,"w");

		
		fputs ($fp,"MOVIMENTAÇÃO DAS REVENDAS - $data\n\n");
		fputs ($fp,"");

		$total_pecas = 0;
		$total_mao_de_obra = 0;

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$revenda_cnpj       = trim(pg_result($res,$i,revenda_cnpj));
			$pecas   = trim(pg_result($res,$i,pecas));
			$mao_de_obra   = trim(pg_result($res,$i,mao_de_obra));
			$qtde_os       = trim(pg_result($res,$i,qtde_os));

			$total_pecas = $total_pecas + $pecas;
			$total_mao_de_obra = $total_mao_de_obra + $mao_de_obra;

			$sql2 =	"SELECT nome as revenda_nome
						FROM tbl_revenda
						WHERE substr(tbl_revenda.cnpj,1,8)='$revenda_cnpj'";
	

			$res2 = pg_exec($con,$sql2);

			$nome       = trim(pg_result($res2,0,revenda_nome));
			$porcentagem   = ($qtde_os / $qtde_os_total)*100;
			$porcentagem   = number_format ($porcentagem,2,',','.');

			if ($i == 0) {

				fputs ($fp,"Raíz do CNPJ \t Revenda \t Peças \t Mão-de-obra \t Qtde. OS \t % OS \r\n");

				echo "<table width='700' align='center' border='0' cellpadding='3' cellspacing='3'>";
				echo "<tr class = 'menu_top'>";
				echo "<td align='left' nowrap>Raíz do CNPJ</td>";
				echo "<td align='left' nowrap>Revenda</td>";
				echo "<td align='left' nowrap>Peças</td>";
				echo "<td align='left' nowrap>Mão-de-obra</td>";
				echo "<td align='left' nowrap>Qtde OS</td>"; # HD 23195
				echo "<td align='left' nowrap>% OS</td>";    # HD 23195
				echo "</tr>";
			}

			echo "<tr class = 'table_line'>";

			if (strlen($posto) > 40) $posto = substr($posto,0,39);

			$mao_de_obra   = number_format ($mao_de_obra,2,',','.');
			$pecas        = number_format ($pecas,2,',','.');

			echo "<td align='left' nowrap>$revenda_cnpj</td>";
			echo "<td align='left' nowrap>$nome</td>";
			echo "<td align='right' nowrap>$pecas</td>";
			echo "<td align='right' nowrap>$mao_de_obra</td>";
			echo "<td align='right' nowrap>$qtde_os</td>\n";
			echo "<td align='right' nowrap>$porcentagem</td>\n";
			echo "</tr>";

			fputs ($fp,"$revenda_cnpj \t $nome \t $pecas \t $mao_de_obra \t $qtde_os \t $porcentagem \r\n");
		}
		echo "</table>";		

		$total_mao_de_obra = number_format ($total_mao_de_obra,2,',','.');
		$total_pecas = number_format ($total_pecas,2,',','.');

		fputs ($fp,"\n\n");
		fputs ($fp,"TOTAL GERAL \r\n");
		fputs ($fp,"Mão de Obra: $total_mao_de_obra \r\n");
		fputs ($fp,"Peças: $total_pecas \r\n");
		fputs ($fp,"Total OS: $qtde_os_total \r\n");

		fclose ($fp);
		
		echo `cd $path_tmp; rm -rf $arquivo_nome.zip; zip -o $arquivo_nome.zip $arquivo_nome > /dev/null ; mv  $arquivo_nome.zip $path `;

		echo "<BR><table width='700' align='center' border='1' cellspacing='2'>";
		echo "<tr class = 'menu_top'>";
		echo "<td align='center' nowrap rowspan=2>TOTAL GERAL</td>";
		echo "<td align='center' nowrap>Peças</td>";
		echo "<td align='center' nowrap>Mão-de-obra</td>";
		echo "<td align='center' nowrap>Total OS</td>";
		echo "</tr>";
		echo "<tr class = table_line>";
		echo "<td align='right' nowrap>$total_pecas</td>";
		echo "<td align='right' nowrap>$total_mao_de_obra</td>";
		echo "<td align='right' nowrap>$qtde_os_total</td>";# HD 23195
		echo "</tr>";
		echo "</table>";

		echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo"<tr>";
		echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'><br><a href='xls/$arquivo_nome.zip'><img src='/assist/imagens/excel.gif'><br><font color='#3300CC'>Fazer download do relatório</font></a></td>";
		echo "</tr>";
		echo "</table>";

		?>
		<BR>
		<center>
		<img src='imagens/btn_imprimir.gif' onclick="javascript: window.open('movimentacao_revenda_lenoxx_print.php?btnacao=pesquisar&inicio=<? echo $data_inicial.'&fim='.$data_final ?>','printmov','toolbar=no,location=no,directories=no,status=no,scrollbars=yes,menubar=yes,resizable=yes,width=700,height=480')" ALT='Imprimir' border='0' style='cursor:pointer;'>
		<?
	} 
		else echo "<center>NENHUM EXTRATO ENCONTRADO</center>";
}

include "rodape.php";
?>