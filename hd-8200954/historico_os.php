<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$os = $_GET['os'];

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

.conteudo2 {
	font-family: Arial;
	FONT-SIZE: 8pt; 
	font-weight: bold;
	text-align: left;
	background: #FFDCDC;
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

<?

if (strlen($os) > 0){
	$sql="SELECT to_char(data,'DD/MM/YYYY') as data,
					  tbl_status_os.descricao,
					  tbl_os_status.observacao,
					  tbl_os_status.status_os,
					  tbl_admin.login
				FROM tbl_os_status
				JOIN tbl_status_os using (status_os)
				LEFT JOIN tbl_admin USING (admin)
				WHERE os = $os";

	$res=pg_exec($con,$sql);

	if(pg_numrows($res) > 0){
		echo "<TABLE width='500' background='#FFDCDC' border='0' align='center' cellspacing='1' cellpadding='0' class='Tabela'>";
			echo "<TR>";
			echo "<TD class='inicio' colspan='3' background='imagens_admin/azul.gif'  height='19px' align='center'>HISTÓRICO COMPLETO DA OS $os</TD>";
			echo "</TR>";
			//echo "<TR><TD class='titulo'>&nbsp;</TD></TR>";
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$data             = pg_result($res,$i,data);
				$descricao_status = pg_result($res,$i,descricao);
				$observacao_status = pg_result($res,$i,observacao);
				$interacao_admin = pg_result($res,$i,login);
				echo "<TR>";
				echo "<TD class='inicio' nowrap>&nbsp;DATA &nbsp;</TD>";
				echo "<TD class='inicio' nowrap>&nbsp;ADMIN &nbsp;</TD>";
				echo "<TD class='inicio' nowrap>&nbsp;STATUS &nbsp;</TD>";
				echo "</TR>";
				echo "<TD class='conteudo' align='center'>&nbsp;$data</TD>";
				echo "<TD class='conteudo' align='center'>&nbsp;$interacao_admin</TD>";
				echo "<TD class='conteudo' align='center'>&nbsp;$descricao_status</TD>";
				echo "</TR>";
				echo "<TR>";
				echo "<TD class='inicio' nowrap>&nbsp;MOTIVO &nbsp;</TD>";
				echo "</TR>";
				echo "<TD class='conteudo2' colspan='3' align='center'>&nbsp;$observacao_status</TD>";
				echo "</TR>";
				if (($i + 1) < pg_numrows ($res)){
					echo "<TR><TD class='titulo' colspan='3'>&nbsp;</TD></TR>";
					}
				}
			echo "</TABLE>";
	}
}

?>