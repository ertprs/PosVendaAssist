<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$admin_privilegios="call_center";
$layout_menu = "callcenter";

$title = "CONSULTA DE ATENDIMENTO";

if (strlen($_POST['btn_acao']) > 0 ) {
	$nomex        = trim($_POST['nome']);
	$cidadex      = trim($_POST['cidade']);
	$estadox      = trim($_POST['estado']);
	$produtox     = trim($_POST['produto']) ;      
	$chamadox     = trim($_POST['chamado']) ;      
	$fonex        = trim($_POST['telefone']) ;         
	$data_contatox= trim($_POST['data_contato']) ; 

	$data_inicial       = trim($_POST["data_inicial"]);
	$data_final         = trim($_POST["data_final"]);

		if (strlen($data_inicial) == 0 or strlen($data_final) ==0) {
			$msg_erro="Por favor, selecionar a data inicial e final para pesquisa.";
		}
		if (strlen($data_inicial) > 0) {
			$fnc = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
			if (strlen ( pg_errormessage ($con) ) > 0) $erro = pg_errormessage ($con) ;
			if (strlen($erro) == 0)                    $aux_data_inicial = @pg_result ($fnc,0,0);
		}
		if (strlen($data_final) > 0) {
			$fnc = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
			if (strlen ( pg_errormessage ($con) ) > 0) $erro = pg_errormessage ($con) ;
			if (strlen($erro) == 0)                    $aux_data_final = @pg_result ($fnc,0,0);
		}
		if(strlen($aux_data_inicial) > 0 and strlen($aux_data_final) > 0){
			$cond1=" tbl_hd_chamado.data BETWEEN '$aux_data_inicial' AND '$aux_data_final' ";
		}else{
			$cond1= "1=1";
		}
}


include "cabecalho.php";
include "javascript_calendario.php";
?>

<script type="text/javascript" charset="utf-8">
	$(function()
	{
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>

<link rel="stylesheet" href="../js/blue/style.css" type="text/css" id="" media="print, projection, screen" />

<link rel="stylesheet" type="text/css" href="css/ext-all.css" />

<style type="text/css">

#Formulario {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: none;
	border: 1px solid #596D9B;
	color:#000000;
	background-color: #D9E2EF;
}
#Formulario tbody th{
	text-align: left;
	font-weight: bold;
}
#Formulario tbody td{
	text-align: left;
	font-weight: none;
}
#Formulario caption{
	color:#FFFFFF;
	text-align: center;
	font-weight: bold;
	background-image: url("imagens_admin/azul.gif");
}

#container {
  border: 0px;
  padding:0px 0px 0px 0px;
  margin:0px 0px 0px 0px;
  background-color: white;
}
.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
}
.Erro{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#CC3300;
	font-weight: bold;
	background-color:#FFFFFF;
}
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

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
</style>

<?
if (strlen($msg_erro) > 0){
?>
<table width="730" border="0" cellpadding="2" cellspacing="2" align='center'>
<tr>
	<td align="center" class='error'>
			<? echo $msg_erro ?>
			
	</td>
</tr>
</table>

<br>

<? } ?>


<!-- =========== PESQUISA POR INTERVALO ENTRE DATAS ============ -->
<FORM name="frm_pesquisa" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Titulo" height="30">
		<td align="center">Selecione os parâmetros para a pesquisa.</td>
	</tr>
</table>

<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<Td nowrap>Data Inicial <INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial" id="data_inicial" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>" ></TD>
		<Td nowrap>Data Final<INPUT size="12" maxlength="10" TYPE="text" NAME="data_final" id="data_final" value="<? if (strlen($data_final) > 0) echo $data_final; ?>" ></TD>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
	<TD colspan='2' align='center'>
	<BR>&nbsp;&nbsp;&nbsp;
	<INPUT TYPE='checkbox' name='nome' value ='nome'> NOME&nbsp;&nbsp;
	<INPUT TYPE='checkbox' name='cidade' value ='cidade'> CIDADE&nbsp;&nbsp;
	<INPUT TYPE='checkbox' name='produto' value ='produto'> PRODUTO&nbsp;&nbsp;
	<INPUT TYPE='checkbox' name='data_contato' value ='data_contato'> DATA DO CONTATO&nbsp;&nbsp;<BR>&nbsp;&nbsp;&nbsp;
	<INPUT TYPE='checkbox' name='telefone' value ='telefone'> TELEFONE&nbsp;&nbsp;
	<INPUT TYPE='checkbox' name='estado' value ='estado'> ESTADO&nbsp;&nbsp;
	<INPUT TYPE='checkbox' name='chamado' value ='chamado'> NÚMERO DO CHAMADO&nbsp;&nbsp;
	</TD>
	</TR>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td colspan='2'><BR>&nbsp;</td>
	</tr>

	
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan='2' align='center'><br><input type="submit" name="btn_acao" value="Gerar Relatório"><br></td>
	</tr>

</table>
</form>
<p>
<br>
<div id="grid-example">
<?
	if(strlen($msg_erro) == 0 and strlen($_POST['btn_acao']) > 0){
		$sql="SELECT tbl_hd_chamado_extra.nome     ,
					 tbl_cidade.nome as cidade_nome,
					 tbl_cidade.estado             ,
					 fone                          ,
					 descricao                     ,
					 referencia                    ,
					 tbl_hd_chamado.hd_chamado     ,
					 to_char(tbl_hd_chamado.data,'dd/mm/yyyy') as data         
				FROM tbl_hd_chamado
				JOIN tbl_hd_chamado_extra USING(hd_chamado)
				JOIN tbl_produto USING (produto)
				JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade=tbl_cidade.cidade
				WHERE tbl_hd_chamado.fabrica_responsavel=$login_fabrica
				AND   $cond1
				ORDER BY tbl_hd_chamado_extra.nome";
		$res=pg_exec($con,$sql);

		if(pg_numrows($res) >0 ){
			echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
		
			echo "<TR class='menu_top'>\n";
			echo "<TD colspan='100%'>Resultado da pesquisa de $data_inicial até $data_final</TD>\n";
			echo "</TR>\n";
			echo "<TR class='menu_top'>\n";
			if(strlen($nomex) > 0)         echo "<TD>NOME DO CONSUMIDOR</TD>\n";
			if(strlen($cidadex) > 0)       echo "<TD>CIDADE</TD>\n";
			if(strlen($estadox) > 0)       echo "<TD>ESTADO</TD>\n";
			if(strlen($produtox) > 0)      echo "<TD>PRODUTO</TD>\n";
			if(strlen($chamadox) > 0)      echo "<TD>N. CHAMADO</TD>\n";
			if(strlen($fonex) > 0)         echo "<TD>TELEFONE</TD>\n";
			if(strlen($data_contatox) > 0) echo "<TD>DATA DO CONTATO</TD>\n";

			flush();
			
			echo `rm /tmp/assist/callcenter-consulta-$login_fabrica.xls`;

			$fp = fopen ("/tmp/assist/callcenter-consulta-$login_fabrica.html","w");

			fputs ($fp,"<html>");
			fputs ($fp,"<head>");
			fputs ($fp,"<title>Consulta Callcenter");
			fputs ($fp,"</title>");
			fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
			fputs ($fp,"</head>");
			fputs ($fp,"<body>");

			fputs ($fp,"<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n");
		
			fputs ($fp,"<TR class='menu_top'>\n");
			fputs ($fp,"<TD colspan='100%'>Resultado da pesquisa de $data_inicial até $data_final</TD>\n");
			fputs ($fp,"</TR>\n");
			fputs ($fp,"<TR class='menu_top'>\n");
			if(strlen($nomex) > 0)         fputs ($fp,"<TD>NOME DO CONSUMIDOR</TD>\n");
			if(strlen($cidadex) > 0)       fputs ($fp,"<TD>CIDADE</TD>\n");
			if(strlen($estadox) > 0)       fputs ($fp,"<TD>ESTADO</TD>\n");
			if(strlen($produtox) > 0)      fputs ($fp,"<TD>PRODUTO</TD>\n");
			if(strlen($chamadox) > 0)      fputs ($fp,"<TD>N. CHAMADO</TD>\n");
			if(strlen($fonex) > 0)         fputs ($fp,"<TD>TELEFONE</TD>\n");
			if(strlen($data_contatox) > 0) fputs ($fp,"<TD>DATA DO CONTATO</TD>\n");

			echo "</TR>\n";
			fputs ($fp, "</TR>\n");
			for($i=0;$i<pg_numrows($res);$i++){
				$nome       = pg_result($res,$i,nome);
				$cidade_nome= pg_result($res,$i,cidade_nome);
				$data       = pg_result($res,$i,data);
				$estado     = pg_result($res,$i,estado);
				$descricao  = pg_result($res,$i,descricao);
				$referencia = pg_result($res,$i,referencia);
				$fone       = pg_result($res,$i,fone);
				$hd_chamado = pg_result($res,$i,hd_chamado);
				


				if ($i % 2 == 0)  $cor = '#F1F4FA';
				else $cor = "#F7F5F0"; 
				echo "<TR class='table_line' style='background-color: $cor;'>\n";
				if(strlen($nomex) > 0)         echo "<TD align=center nowrap>$nome</TD>\n";
				if(strlen($cidadex) > 0)       echo "<TD align=center nowrap>$cidade_nome</TD>\n";
				if(strlen($estadox) > 0)       echo "<TD align=center nowrap>$estado</TD>\n";
				if(strlen($produtox) > 0)      echo "<TD align=center nowrap>$referencia - $descricao</TD>\n";
				if(strlen($chamadox) > 0)      echo "<TD align=center nowrap>$hd_chamado</TD>\n";
				if(strlen($fonex) > 0)         echo "<TD align=center nowrap>$fone</TD>\n";
				if(strlen($data_contatox) > 0) echo "<TD align=center nowrap>$data</TD>\n";
				echo "</TR>\n";

				fputs ($fp,"<TR class='table_line' style='background-color: $cor;'>\n");
				if(strlen($nomex) > 0)         fputs ($fp,"<TD align=center nowrap>$nome</TD>\n");
				if(strlen($cidadex) > 0)       fputs ($fp,"<TD align=center nowrap>$cidade_nome</TD>\n");
				if(strlen($estadox) > 0)       fputs ($fp,"<TD align=center nowrap>$estado</TD>\n");
				if(strlen($produtox) > 0)      fputs ($fp,"<TD align=center nowrap>$referencia - $descricao</TD>\n");
				if(strlen($chamadox) > 0)      fputs ($fp,"<TD align=center nowrap>$hd_chamado</TD>\n");
				if(strlen($fonex) > 0)         fputs ($fp,"<TD align=center nowrap>$fone</TD>\n");
				if(strlen($data_contatox) > 0) fputs ($fp,"<TD align=center nowrap>$data</TD>\n");
				fputs ($fp,"</TR>\n");
			}
			echo "</TABLE>\n";
			fputs ($fp,"</TABLE>\n");

				fputs ($fp,"</body>");
				fputs ($fp,"</html>");
				fclose ($fp);

				$data = date("Y-m-d").".".date("H-i-s");

				echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/xls/callcenter-consulta-$login_fabrica.xls /tmp/assist/callcenter-consulta-$login_fabrica.html`;
				echo "<BR><BR>";
				echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
				echo"<tr>";
				echo "<td align='center'><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='xls/callcenter-consulta-$login_fabrica.xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
				echo "</tr>";
				echo "</table>";
		}else{
		
			
		}
}
?>
</div>

<? include "rodape.php" ?>
