<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if($login_fabrica<>10){
	header ("Location: index.php");
}
$TITULO = "ADM - Relatório Diário";
?>
<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 13px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Arial;
	font-size: 12px;
	font-weight: normal;
}
.ConteudoBranco {
	font-family: Arial;
	font-size: 12px;
	color:#FFFFFF;
	font-weight: normal;
}
.Mes{
	font-size: 12px;
}
.Caixa{
	BORDER-RIGHT: #6699CC 1px solid; 
	BORDER-TOP: #6699CC 1px solid; 
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid; 
	BORDER-BOTTOM: #6699CC 1px solid; 
	BACKGROUND-COLOR: #FFFFFF
}
.Exibe{
	font-family: Arial, Helvetica, sans-serif;
	font-size: 8 px;
	font-weight: none;
	color: #000000;
	text-align: center;
}
</style>
<?
include "menu.php";

$sql = "SELECT  TO_CHAR(data_inicio,'dd/mm/yyyy')          AS data_inicio ,
		TO_CHAR(data_inicio,' hh24:mi')            AS hora_inicio ,
		TO_CHAR(data_termino,'dd/mm/yyyy')         AS data_termino,
		TO_CHAR(data_termino,' hh24:mi')           AS hora_termino,
		tbl_hd_chamado.hd_chamado                                 ,
		tbl_hd_chamado.titulo                                     ,
		tbl_hd_chamado.status                                     ,
		tbl_admin.admin                                           ,
		tbl_admin.nome_completo                                   ,
		tbl_fabrica.nome,
		TO_CHAR(tbl_hd_chamado.data,'dd/mm/yyyy')         AS  data
	FROM tbl_hd_chamado_atendente
	JOIN tbl_admin using(admin)
	JOIN tbl_hd_chamado using(hd_chamado)
	JOIN tbl_fabrica    ON tbl_fabrica.fabrica = tbl_hd_chamado.fabrica
	WHERE data_inicio BETWEEN  ('2008-06-30'||' 00:00:00')::timestamp AND ('2008-07-28'||' 23:59:00')::timestamp
	ORDER BY nome_completo,data_inicio, hora_inicio";
echo $sql;
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	for ($i=0; $i<pg_numrows($res); $i++){
		$hora_inicio   = trim(pg_result($res,$i,hora_inicio))  ;
		$hora_termino  = trim(pg_result($res,$i,hora_termino)) ;
		$hd_chamado    = trim(pg_result($res,$i,hd_chamado))   ;
		$titulo        = trim(pg_result($res,$i,titulo))       ;
		$status        = trim(pg_result($res,$i,status))       ;
		$admin         = trim(pg_result($res,$i,admin))        ;
		$nome_completo = trim(pg_result($res,$i,nome_completo));
		$data_inicio   = trim(pg_result($res,$i,data_inicio))  ;
		$data_termino  = trim(pg_result($res,$i,data_termino)) ;
		$fabrica_nome  = trim(pg_result($res,$i,nome)) ;
		$data          = trim(pg_result($res,$i,data)) ;

		if($cor=="#F1F4FA")$cor = '#F7F5F0';
		else               $cor = '#F1F4FA';

		if($admin<>$admin_anterior OR $data_inicio<>$data_inicio_anterior){
			$sql2 = "select x.dia 
				from(
				select sum(to_char(data_termino,'HH24:MI:SS')::time - to_char(data_inicio,'HH24:MI:SS')::time) as dia,admin 
				from tbl_hd_chamado_atendente where admin=$admin 
				and data_inicio between ('$data_inicio'||' 00:00:00')::timestamp 
				and ('$data_inicio'||' 23:59:59')::timestamp
				group by admin
				) x
				";
		//echo $sql2;
		$res2 = pg_exec ($con,$sql2);

		$horas_trabalho  = trim(@pg_result($res2,0,0)) ;
		if($i<>0)echo "</table>";
			echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center'>";
			echo "<tr class='Titulo'>";
			echo "<td background='../admin/imagens_admin/azul.gif' height='20'><font size='2'>$data_inicio - $nome_completo $horas_trabalho</font></td>";
			echo "</tr>";
		}

		$admin_anterior = $admin;
		$data_inicio_anterior = $data_inicio;
	}
  echo "</table>";
}

include 'rodape.php';

?>
