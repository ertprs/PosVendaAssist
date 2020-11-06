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

if(strlen($_GET['data'])==0){
	$data = "2007-09-25";
}else{
	$data = $_GET['data'];
}


$sql = "SELECT  TO_CHAR(data_inicio,'dd/mm/yyyy')          AS data_inicio ,
		TO_CHAR(data_inicio,' hh24:mi')            AS hora_inicio ,
		TO_CHAR(data_termino,'dd/mm/yyyy')         AS data_termino,
		TO_CHAR(data_termino,' hh24:mi')           AS hora_termino,
		tbl_hd_chamado.hd_chamado                                 ,
		tbl_hd_chamado.titulo                                     ,
		tbl_hd_chamado.status                                     ,
		tbl_admin.admin                                           ,
		tbl_admin.nome_completo                                   ,
		tbl_fabrica.nome
	FROM tbl_hd_chamado_atendente
	JOIN tbl_admin using(admin)
	JOIN tbl_hd_chamado using(hd_chamado)
	JOIN tbl_fabrica    ON tbl_fabrica.fabrica = tbl_hd_chamado.fabrica
	WHERE data_inicio BETWEEN  ('$data 00:00:00')::timestamp AND ('$data 23:59:00')::timestamp
	ORDER BY nome_completo,hora_inicio";

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

		if($cor=="#F1F4FA")$cor = '#F7F5F0';
		else               $cor = '#F1F4FA';

		if($admin<>$admin_anterior){
		$sql2 = "select x.dia + y.ultimo
from(
select to_char(sum(data_termino-data_inicio), 'HH24:MI') as dia,admin 
from tbl_hd_chamado_atendente where admin=$admin 
and data_inicio between current_date||' 00:00:00' 
and current_date||' 23:59:59'
group by admin
) x
left join(

(select to_char((current_time - data_inicio), 'HH24:MI') as ultimo,admin 
from tbl_hd_chamado_atendente where admin=$admin 
and data_inicio between current_date||' 00:00:00' and current_date|| ' 23:59:59' 
and data_termino is null 
group by admin,data_inicio
)a
) y  ON  x.admin=y.admin";

$sql2 = "select x.dia 
from(
select to_char(sum(data_termino-data_inicio), 'HH24:MI') as dia,admin 
from tbl_hd_chamado_atendente where admin=$admin 
and data_inicio between current_date||' 00:00:00' 
and current_date||' 23:59:59'
group by admin
) x
";
//echo $sql2;
$res2 = pg_exec ($con,$sql2);

$horas_trabalho  = trim(@pg_result($res2,0,0)) ;
		if($i<>0)echo "</table>";
			echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center'>";
			echo "<tr class='Titulo'>";
			echo "<td background='../admin/imagens_admin/azul.gif' height='20'><font size='2'>Início</font></td>";
			echo "<td background='../admin/imagens_admin/azul.gif' height='20'><font size='2'>Termino</font></td>";
			echo "<td background='../admin/imagens_admin/azul.gif' height='20'><font size='2'>Chamado - $nome_completo $horas_trabalho</font></td>";
			echo "<td background='../admin/imagens_admin/azul.gif' height='20'><font size='2'>Status</font></td>";
			echo "<td background='../admin/imagens_admin/azul.gif' height='20'><font size='2'>Fábrica</font></td>";
			echo "</tr>";
		}

		echo "<tr class='Conteudo'align='center'>";
		echo "<td bgcolor='$cor' align='left' width='60'>$hora_inicio</td>";
		echo "<td bgcolor='$cor' align='left' width='60'>$hora_termino</td>";
		echo "<td bgcolor='$cor' align='left' width='300'>$hd_chamado - $titulo</td>";
		echo "<td bgcolor='$cor' align='left' width='50'>$status</td>";
		echo "<td bgcolor='$cor' align='left' width='80'>$fabrica_nome </td>";

		$admin_anterior = $admin;

	}
  echo "</table>";
}

include 'rodape.php';

?>
