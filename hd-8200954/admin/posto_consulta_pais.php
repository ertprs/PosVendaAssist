<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$msg_erro = "";

$layout_menu = "callcenter";
$title = "CONSULTA DE POSTO POR PAÍS";

include "cabecalho.php";

$btn_acao=$_POST['btn_acao'];

$pais=$_POST['pais'];
?>


<style type="text/css">

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
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
.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}
.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

</style>

<table  align='center' border='0' width='700' cellpadding='0' cellspacing='1' class="formulario">
<form name='frm_consulta' method='post' action='<? echo $PHP_SELF; ?>'>
<tr class='titulo_tabela' height='20'>
	<td align='center'><b>Parâmetros de Pesquisa</b></td>
</tr>
<tr><td>&nbsp;</td></tr>
		<TR width='100%' align="center">
			<TD align='center' >
			<label>Selecione</label>
			<?
				$sql = "SELECT  *
						FROM    tbl_pais
						ORDER BY tbl_pais.nome;";
				$res = pg_exec ($con,$sql);
				
				if (pg_numrows($res) > 0) {
					echo "<select align='center' name='pais'>\n";
					if(strlen($pais) == 0 ) {
						$pais = 'BR';
					}
					
					for ($x = 0 ; $x < pg_numrows($res) ; $x++){
						$aux_pais  = trim(pg_result($res,$x,pais));
						$aux_nome  = trim(pg_result($res,$x,nome));
						
						echo "<option value='$aux_pais'"; 
						if ($pais == $aux_pais){
							echo " SELECTED "; 
							$mostraMsgPais = "<br> do PAÍS $aux_nome";
						}
						echo ">$aux_nome</option>\n";
					}
					echo "</select>\n";
				} ?>
				&nbsp;<input type=submit name=btn_acao value='Pesquisar' >
			</td>
</tr>
<tr><td>&nbsp;</td></tr>
</form>
</table>
<BR>

<?
if(strlen($btn_acao) > 0) {
	$sql="SELECT tbl_posto_fabrica.codigo_posto          ,
				 tbl_posto.nome                          ,
				 tbl_pais.unidade_trabalho               ,
				 tbl_posto_fabrica.desconto              ,
				 tbl_posto_fabrica.desconto_acessorio    ,
				 tbl_posto_fabrica.imposto_al            ,
				 tbl_pais.nome as nome_pais
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING(posto)
				JOIN tbl_pais using(pais)
				WHERE pais='$pais' 
				AND fabrica =$login_fabrica
				AND tbl_posto.nome not ilike '%test%'
				AND tbl_posto_fabrica.credenciamento !='DESCREDENCIADO'
				ORDER BY tbl_posto.nome,
						 tbl_posto_fabrica.codigo_posto
						 ASC";
	$res=pg_exec($con,$sql);
	
	if(pg_numrows($res) > 0) {
		$nome_pais     = trim(pg_result($res,0,nome_pais));	
		$nome_pais     =strtoupper($nome_pais);
		echo "<table border='0' cellspacing='1' width='700' cellpadding='0' class='tabela' align='center'>";
		echo "<tr class='titulo_coluna'>";
		echo "<td colspan='6' align='center'>$nome_pais</td>";
		echo "<tr class='titulo_coluna'>";
		echo "<td>Código do posto</td>";
		echo "<td>Nome do posto</td>";
		echo "<td>Unidade de trabalho</td>";
		echo "<td>Desconto</td>";
		echo "<td>Desconto Acessório</td>";
		echo "<td>Imposto</td>";
		echo "</tr>";

		for($i=0 ; $i<pg_numrows($res) ; $i++) {
			$codigo             = trim(pg_result($res,$i,codigo_posto));
			$nome               = trim(pg_result($res,$i,nome));	
			$desconto           = trim(pg_result($res,$i,desconto));
			$desconto_acessorio = trim(pg_result($res,$i,desconto_acessorio));
			$imposto_al         = trim(pg_result($res,$i,imposto_al));
			$unidade_trabalho   = trim(pg_result($res,$i,unidade_trabalho));
			$unidade_trabalho = number_format($unidade_trabalho, 2, ',', ' ');
			$cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

			echo "<tr class='Conteudo' bgcolor=$cor>";
			echo "<td>$codigo</td>";
			echo "<td>$nome</td>";
			echo "<td>$unidade_trabalho</td>";
			echo "<td>$desconto</td>";
			echo "<td>$desconto_acessorio</td>";
			echo "<td>$imposto_al</td>";
		}
		echo "</tr>";
		echo "</table>";
		echo "<BR><BR>";
		flush();
		echo `rm /tmp/posto-$login_fabrica.$pais.xls`;

		$fp = fopen ("/tmp/posto-$login_fabrica.$pais.html","w");

		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>POSTO - $pais");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");
		
		fputs ($fp,"<table width='100%' align='left' border='1' cellpadding='2' cellspacing='2'>");
		fputs ($fp,"<tr>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>CÓDIGO DO POSTO</b></td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>NOME DO POSTO</b></td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>UNIDADE DE TRABALHO</b></td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>DESCONTO</b></td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>DESCONTO ACESSÓRIO</b></td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>IMPOSTO</b></td>");
		fputs ($fp,"</tr>");
		
		for ($j = 0 ; $j < pg_numrows ($res) ; $j++) {
			$codigo_posto             = trim(pg_result($res,$j,codigo_posto));
			$nome               = trim(pg_result($res,$j,nome));	
			$desconto           = trim(pg_result($res,$j,desconto));
			$desconto_acessorio = trim(pg_result($res,$j,desconto_acessorio));
			$imposto_al         = trim(pg_result($res,$j,imposto_al));
			$unidade_trabalho   = trim(pg_result($res,$j,unidade_trabalho));

			fputs ($fp,"<tr>");
			fputs ($fp,"<td align='center'>&nbsp;$codigo_posto&nbsp;</td>");
			fputs ($fp,"<td align='left' nowrap>$nome</td>");
			fputs ($fp,"<td align='center'>&nbsp;$unidade_trabalho&nbsp;</td>");
			fputs ($fp,"<td align='center'>&nbsp;$desconto&nbsp;</td>");
			fputs ($fp,"<td align='center'>$desconto_acessorio</td>");
			fputs ($fp,"<td  align='center'>&nbsp;$imposto_al&nbsp;</td>");
			fputs ($fp,"</tr>");
		}
		fputs ($fp,"</table>");
		fputs ($fp,"</body>");
		fputs ($fp,"</html>");
		fclose ($fp);

		
	/*echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/tmp/posto-$login_fabrica.$pais.xls /tmp/posto-$login_fabrica.$pais.html`; */

	rename("/tmp/posto-$login_fabrica.$pais.html", "/www/assist/www/admin/xls/posto-$login_fabrica.$pais.xls");
	
	echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
	echo"<tr>";
	echo "<td align='center'>
	<button type='button' onclick=\"window.location='xls/posto-$login_fabrica.$pais.xls'\"> Download em Excel</button>";
	echo "</tr>";
	echo "</table>";
	} else {
		echo "Nehum posto encontrado para país selecionado";
	}

}
?>

<? include "rodape.php" ?>
