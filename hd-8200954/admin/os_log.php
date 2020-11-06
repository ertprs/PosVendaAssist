<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';


#------------ Detecta OS para Auditoria -----------#
$os= $_GET['os'];

$title = "LOG Ordem de Serviço";

$layout_menu = 'os';
include "cabecalho.php";

?>
<style type="text/css">

body {
	margin: 0px;
}

body {
	margin: 0px;
}

.titulo {
	font-family: Arial;
	font-size: 7pt;
	text-align: right;
	color: #000000;
	background: #ced7e7;
}
.titulo2 {
	font-family: Arial;
	font-size: 7pt;
	text-align: center;
	color: #000000;
	background: #ced7e7;
}
.titulo3 {
	font-family: Arial;
	font-size: 7pt;
	text-align: left;
	color: #000000;
	background: #ced7e7;
}
.inicio {
	font-family: Arial;
	FONT-SIZE: 8pt; 
	font-weight: bold;
	text-align: left;
	color: #FFFFFF;
}

.conteudo {
	font-family: Arial;
	FONT-SIZE: 8pt; 
	font-weight: bold;
	text-align: left;
	background: #F4F7FB;
}
.Tabela{
	border:1px solid #d2e4fc;
	background-color:#485989;
	}
.subtitulo {
	font-family: Verdana;
	FONT-SIZE: 9px; 
	text-align: left;
	background: #F4F7FB;
	padding-left:5px
}
.justificativa{
    font-family: Arial;
    FONT-SIZE: 10px;
    background: #F4F7FB;
}
.inpu{
	border:1px solid #666;
}
</style>

<style type="text/css">
div.banner {
  margin:       0;
  font-size:   10px;
  position:    absolute;
  top:         0em;
  left:        auto;
  width:       100%;
  right:       0em;
  background:  #F7F5F0;
  border-bottom: 1px solid #FF9900;
}

</style>

<?

// Verifica se o pedido de peça foi cancelado ou autorizado caso a peça esteja bloqueada para garantia

if ($login_fabrica == 11) {

	$sql= "SELECT 
				tbl_os_log.nota_fiscal   as l_nota_fiscal  ,
				to_char(tbl_os_log.data_nf,'dd/mm/yyyy')       as l_data_nf      ,
				to_char(tbl_os_log.digitacao,'dd/mm/yyyy hh24:mi')     as l_digitacao      ,
				tbl_os_log.numero_serie  as l_numero_serie   ,
				tbl_os_log.cnpj_revenda  as l_cnpj_revenda   ,
				tbl_os_log.nome_revenda  as l_nome_revenda   ,
				tbl_os_log.os_atual             ,
				to_char(tbl_os_log.data_abertura,'dd/mm/yyyy')  as l_data_abertura  ,

				to_char(tbl_os.data_abertura,'dd/mm/yyyy')  as os_data_abertura,
				to_char(tbl_os.data_nf,'dd/mm/yyyy')        as os_data_nf,
				tbl_os.revenda_cnpj   as os_revenda_cnpj,
				tbl_os.revenda_nome   as os_revenda_nome,
				tbl_os.nota_fiscal   as os_nota_fiscal,
				to_char(tbl_os.data_digitacao,'dd/mm/yyyy hh24:mi')     as os_digitacao    ,
				tbl_os.serie         as os_numero_serie 
		   FROM tbl_os_log
		   JOIN TBL_OS ON TBL_OS.OS = TBL_OS_LOG.OS_ATUAL
		   WHERE tbl_os_log.fabrica = $login_fabrica and os_atual=$os;";

	$res= pg_exec($con,$sql);
	if (pg_numrows($res)>0) {

		$l_nota_fiscal          = trim(pg_result($res,0,l_nota_fiscal));
		$l_data_nf              = trim(pg_result($res,0,l_data_nf));
		$l_digitacao            = trim(pg_result($res,0,l_digitacao));
		$l_numero_serie         = trim(pg_result($res,0,l_numero_serie));
		$l_cnpj_revenda         = trim(pg_result($res,0,l_cnpj_revenda));
		$l_nome_revenda         = trim(pg_result($res,0,l_nome_revenda));
		$l_os_atual             = trim(pg_result($res,0,os_atual));
		$l_data_abertura        = trim(pg_result($res,0,l_data_abertura));

		$os_nota_fiscal          = trim(pg_result($res,0,os_nota_fiscal));
		$os_data_nf              = trim(pg_result($res,0,os_data_nf));
		$os_digitacao            = trim(pg_result($res,0,os_digitacao));
		$os_numero_serie         = trim(pg_result($res,0,os_numero_serie));
		$os_revenda_cnpj         = trim(pg_result($res,0,os_revenda_cnpj));
		$os_revenda_nome         = trim(pg_result($res,0,os_revenda_nome));
		$os_data_abertura        = trim(pg_result($res,0,os_data_abertura));

		echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#003399'  align='center' width='700'>";
		echo "<tr>";
		echo "<td colspan='4'><b><font color='#000099'>OSs QUE ESTIVERAM FORA DE GARANTIA</font></b></td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td class='subtitulo' colspan='2' align='center'>OS COM ERRO</td>";
		echo "<td class='subtitulo' colspan='2' align='center'>OS GRAVADA</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td class='subtitulo'>Nota Fiscal</td>";
		echo "<td class='Conteudo'><font color='#990000'>$l_nota_fiscal</font></td>";
		echo "<td class='subtitulo'>Nota Fiscal</td>";
		echo "<td class='Conteudo'><font color='#990000'>$os_nota_fiscal</font></td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td class='subtitulo'>Data Abertura</td>";
		echo "<td class='Conteudo'><font color='#990000'>$l_data_abertura</font></td>";
		echo "<td class='subtitulo'>Data Abertura</td>";
		echo "<td class='Conteudo'><font color='#990000'>$os_data_abertura</font></td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td class='subtitulo'>Data de Compra</td>";
		echo "<td class='Conteudo'><font color='#990000'>$l_data_nf</font></td>";
		echo "<td class='subtitulo'>Data de Compra</td>";
		echo "<td class='Conteudo'><font color='#990000'>$os_data_nf</font></td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td class='subtitulo'>Data Digitação</td>";
		echo "<td class='Conteudo'><font color='#990000'>$l_digitacao</font></td>";
		echo "<td class='subtitulo'>Data Digitação</td>";
		echo "<td class='Conteudo'><font color='#990000'>$os_digitacao</font></td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td class='subtitulo'>Número de Série</td>";
		echo "<td class='Conteudo'><font color='#990000'>$l_numero_serie</font></td>";
		echo "<td class='subtitulo'>Número de Série</td>";
		echo "<td class='Conteudo'><font color='#990000'>$os_numero_serie</font></td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td class='subtitulo'>CNPJ Revenda</td>";
		echo "<td class='Conteudo'><font color='#990000'>$l_cnpj_revenda</font></td>";
		echo "<td class='subtitulo'>CNPJ Revenda</td>";
		echo "<td class='Conteudo'><font color='#990000'>$os_revenda_cnpj</font></td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td class='subtitulo'>Nome Revenda</td>";
		echo "<td class='Conteudo'><font color='#990000'>$l_nome_revenda</font></td>";
		echo "<td class='subtitulo'>Nome Revenda</td>";
		echo "<td class='Conteudo'><font color='#990000'>$os_revenda_nome</font></td>";
		echo "</tr>";
		echo "</table>";
	}
}
 include "rodape.php"; 

?>