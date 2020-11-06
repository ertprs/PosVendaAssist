<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';

$layout_menu = "callcenter";
$title = "MENU CALL-CENTER";
include 'cabecalho.php';

$brilho = "style=\"background-color:#F0F4FF \" onmouseover=\"this.style.backgroundColor='#D2DFF2';this.style.cursor='hand';\" onmouseout=\"this.style.backgroundColor='#F0F4FF';\"";
?>
<style>
.Conteudo{
	font-family: Arial;
	font-size: 12px;
	color: #333333;
}
.Caixa{
	FONT: 8pt Arial ;
	BORDER-RIGHT:     #6699CC 1px solid;
	BORDER-TOP:       #6699CC 1px solid;
	BORDER-LEFT:      #6699CC 1px solid;
	BORDER-BOTTOM:    #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
}
a:link{
	font-family: Verdana;
	font-size: 12px;
	font-weight: bold;
	color:#333;
}
a:visited{
	font-family: Verdana;
	font-size: 12px;
	font-weight: bold;
	color:#333;
}

a:hover{
	font-family: Verdana;
	font-size: 12px;
	font-weight: bold;
	color:#333;
}
a:hover{
	font-family: Verdana;
	font-size: 12px;
	font-weight: bold;
	color:#3399FF;
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

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
}

</style>

<?

echo "<table class='tabela' align='center' width='700' border='0' cellspacing='0' class='formulario'>";
echo "<tr height='20'>";
echo "<td>";
echo "<table width='100%' class='formulario'>";
echo "<tr class='titulo_tabela'>";
echo "<td>Sistema de Revendas <div style='float:right;'>Usuário: <b>$login_login</b></div></td>";
echo "</tr>";
echo "</table>";

echo "<table border='0' cellspacing='0' width='100%' class='formulario'>";
echo "<tr><td><br>";
$aba = 1;
include "revenda_cabecalho.php";
echo "<br>&nbsp;</td></tr>";
echo "<tr>";
echo "<td align='left'>";
echo "<table width='90%' align='center' ><tr><td>";
/*echo "A TELECONTROL está desenvolvendo um meio de informação centralizada para gerenciamento do fluxo de produtos entre a REVENDA <-> REDE AUTORIZADA & REVENDA <-> FABRICANTE.<br>
Trata-se de um sistema via web onde a revenda estará informando pelo site todos as remessas para conserto, troca e devolução enviadas à Rede e à Fábrica. A grande vantagem é a informação acessível e on-line para todos.<br>
Em breve, quando este sistema estiver completo será possível:<br>
<li> consultar o andamento por Produto, por Nota Fiscal, por Data e por Lote.<br>
<li> administração das pendências e divergência nas remessas.<br>
<li> controle eficaz dos prazos.<br>
<li> importar a relação de produtos comercializados entre a FÁBRICA e a REVENDA com os códigos internos de ambas.<br>
<li> solicitações de coletas e confirmação de recebimento.<br><br>
Informação precisa e em tempo real é vital para gerenciar recursos de nossas empresas."; */
echo "</td></tr></table>";
echo "</td>";
echo "</tr>";
echo "</table>";
/*
	echo "<table border='0' cellspacing='0' width='100%'>";
	echo "<tr bgcolor='#BCCBE0' class='Conteudo'>";
	echo "<td align='left'><b>Notas fiscais não recebidas</td>";
	echo "</tr>";
	echo "<tr bgcolor='#F0F4FF' class='Conteudo'>";
	$sql = "SELECT  tbl_lote_revenda.lote                                                ,
					TO_CHAR(tbl_lote_revenda.data_digitacao,'dd/mm/YYYY')  AS data       ,
					tbl_lote_revenda.nota_fiscal                                         ,
					TO_CHAR(tbl_lote_revenda.data_nf,'dd/mm/YYYY')         AS data_nf    ,
					tbl_posto.nome                                                       ,
					tbl_posto_fabrica.codigo_posto                                       ,
					tbl_revenda.nome                                       AS revenda_nome
			FROM tbl_lote_revenda 
			JOIN tbl_revenda       USING(revenda)
			JOIN tbl_posto         USING(posto)
			JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_lote_revenda.fabrica = $login_fabrica
			AND   tbl_lote_revenda.conferencia IS NOT TRUE
			AND   tbl_lote_revenda.posto <> 6359
			ORDER BY tbl_posto.nome";

	echo "<td align='left'>";
	$res = pg_exec ($con,$sql);
	if(pg_numrows($res)>0) {
		echo "<br><table border='0' cellspacing='0' width='98%' align='center'>";
		echo "<tr bgcolor='#BCCFFF' class='Conteudo'>";
		echo "<td align='left'> <b>Posto</td>";
		echo "<td align='left'> <b>Revenda</td>";
		echo "<td align='left'> <b>Data</td>";
		echo "<td align='left'> <b>Lote</td>";
		echo "<td align='left'> <b>Nota Fiscal</td>";
		echo "<td align='left'> <b>Data Nota Fiscal</td>";
		echo "</tr>";
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$lote         = pg_result ($res,$i,lote);
			$data         = pg_result ($res,$i,data);
			$nota_fiscal  = pg_result ($res,$i,nota_fiscal);
			$data_nf      = pg_result ($res,$i,data_nf);
			$nome         = pg_result ($res,$i,nome);
			$codigo_posto = pg_result ($res,$i,codigo_posto);
			$revenda_nome = pg_result ($res,$i,revenda_nome);

			if($cor == "#D7E1FF") $cor = '#F0F4FF';
			else                  $cor = '#D7E1FF';

			echo "<tr bgcolor='$cor' class='Conteudo'>";
			echo "<td align='left' title='$codigo_posto - $nome'> $nome </td>";
			echo "<td align='left'> $revenda_nome </td>";
			echo "<td align='left'> $data</td>";
			echo "<td align='left'> $lote</td>";
			echo "<td align='left'> $nota_fiscal</td>";
			echo "<td align='left'> $data_nf</td>";
			echo "</tr>";
		}
		echo "</table><br><br>";
	}
	echo "</td>";

	echo "</tr>";
	echo "</table>";


	echo "<table border='0' cellspacing='0' width='100%'>";
	echo "<tr bgcolor='#BCCBE0' class='Conteudo'>";
	echo "<td align='left'><b>Notas Fiscais divergentes Revenda - Posto</td>";
	echo "</tr>";
	echo "<tr bgcolor='#F0F4FF' class='Conteudo'>";
	$sql = "SELECT  tbl_lote_revenda.lote                                                ,
					TO_CHAR(tbl_lote_revenda.data_digitacao,'dd/mm/YYYY')  AS data       ,
					tbl_lote_revenda.nota_fiscal                                         ,
					TO_CHAR(tbl_lote_revenda.data_nf,'dd/mm/YYYY')         AS data_nf    ,
					tbl_posto.nome                                                       ,
					tbl_posto_fabrica.codigo_posto                                       ,
					tbl_revenda.nome                                       AS revenda_nome
			FROM tbl_lote_revenda 
			JOIN tbl_revenda       USING(revenda)
			JOIN tbl_posto         USING(posto)
			JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_lote_revenda.fabrica = $login_fabrica
			AND   tbl_lote_revenda.lote_revenda IN (select lote_revenda from tbl_lote_revenda_item where conferencia_qtde <> qtde)
			AND   tbl_lote_revenda.posto <> 6359
			ORDER BY tbl_posto.nome";

	echo "<td align='left'>";
	$res = pg_exec ($con,$sql);
	if(pg_numrows($res)>0) {
		echo "<br><table border='0' cellspacing='0' width='98%' align='center'>";
		echo "<tr bgcolor='#BCCFFF' class='Conteudo'>";
		echo "<td align='left'> <b>Posto</td>";
		echo "<td align='left'> <b>Revenda</td>";
		echo "<td align='left'> <b>Data</td>";
		echo "<td align='left'> <b>Lote</td>";
		echo "<td align='left'> <b>Nota Fiscal</td>";
		echo "<td align='left'> <b>Data Nota Fiscal</td>";
		echo "</tr>";
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$lote         = pg_result ($res,$i,lote);
			$data         = pg_result ($res,$i,data);
			$nota_fiscal  = pg_result ($res,$i,nota_fiscal);
			$data_nf      = pg_result ($res,$i,data_nf);
			$nome         = pg_result ($res,$i,nome);
			$codigo_posto = pg_result ($res,$i,codigo_posto);
			$revenda_nome = pg_result ($res,$i,revenda_nome);

			if($cor == "#D7E1FF") $cor = '#F0F4FF';
			else                  $cor = '#D7E1FF';

			echo "<tr bgcolor='$cor' class='Conteudo'>";
			echo "<td align='left' title='$codigo_posto - $nome'> $nome </td>";
			echo "<td align='left'> $revenda_nome </td>";
			echo "<td align='left'> $data</td>";
			echo "<td align='left'> $lote</td>";
			echo "<td align='left'> $nota_fiscal</td>";
			echo "<td align='left'> $data_nf</td>";
			echo "</tr>";
		}
		echo "</table><br>";
	}
	echo "</td>";

	echo "</tr>";
	echo "</table>";
*/
?>
</td>
</tr>
</table>

<?include "rodape.php"?>



