<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

include 'autentica_admin.php';

include 'funcoes.php';

$layout_menu = "callcenter";
$title = "RELATÓRIO FORMULÁRIO CONSULTA";


?>
<style>
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
	color:#ffffff;
	background-color: #445AA8;
}

.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Arial;
	font-size: 9px;
	font-weight: normal;
}
.ConteudoBranco {
	font-family: Arial;
	font-size: 9px;
	color:#FFFFFF;
	font-weight: normal;
}

.Caixa{
	BORDER-RIGHT: #6699CC 1px solid; 
	BORDER-TOP: #6699CC 1px solid; 
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid; 
	BORDER-BOTTOM: #6699CC 1px solid; 
	BACKGROUND-COLOR: #FFFFFF;
}
.Erro{
	BORDER-RIGHT: #990000 1px solid; 
	BORDER-TOP: #990000 1px solid; 
	FONT: 10pt Arial ;
	COLOR: #ffffff;
	BORDER-LEFT: #990000 1px solid; 
	BORDER-BOTTOM: #990000 1px solid; 
	BACKGROUND-COLOR: #FF0000;
}

</style>


<?
	$data_inicial       = $_GET['data_inicial'];
	$data_final         = $_GET['data_final'];
	$inspetor           = $_GET['inspetor'];
	$formulario         = $_GET['formulario'];
	$pergunta           = $_GET['pergunta'];
	$nota               = $_GET['nota'];

	if(strlen($msg_erro)==0){


		$sql="SELECT tbl_visita_posto.posto         ,
					 tbl_visita_posto.visita_posto  ,
				 	 tbl_visita_posto.admin         ,
					 to_char(tbl_visita_posto.data,'DD/MM/YYYY') as data         ,
				 	 tbl_posto.nome                 ,
					 tbl_admin.nome_completo        
				FROM tbl_visita_posto
				JOIN tbl_admin ON tbl_admin.admin=tbl_visita_posto.admin
				JOIN tbl_posto ON tbl_posto.posto=tbl_visita_posto.posto";
		if(strlen($data_inicial) > 0 and strlen($inspetor) >0 ) {
			$sql .= " WHERE tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'
						AND tbl_visita_posto.admin=$inspetor";
		}else{
			if (strlen($data_inicial) > 0 and strlen($inspetor) == 0) {
				$sql .= " WHERE tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'";
			}
		}
		if (strlen($inspetor) > 0 and strlen($data_inicial) == 0) {
			$sql .= " WHERE tbl_visita_posto.admin=$inspetor";
		}
		if(strlen($nota) > 0) {
			$sql.=" AND $pergunta=$nota ";
		}
		
		$res = pg_exec($con,$sql);
		
		if (pg_numrows($res) == 0) {
				echo "<font align=center size=4 color=red>Nenhum posto encontrado</font>";
		} else {
				for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
				if ($i % 50 == 0) {
					echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'  align='center' width='680'>";
				}
				if ($i % 50 == 0) {
					echo "<tr class='Titulo' height='15'>";
					$xpergunta = strtoupper($pergunta);
					echo "<td colspan='4'>Os postos que deram nota $nota para $xpergunta</td>";
					echo "</tr>";
					echo "<tr  bgcolor='#d2d7e1' class='Conteudo' height='15'>";
					echo "<td align='center'>Nome de Inspetor</td>";
					echo "<td align='center'>Data</td>";
					echo "<td align='center'>Nome do Posto</td>";
					echo "<td align='center'>Consultar</td>";
					echo "</tr>";
				}
					$nome_posto               = trim(pg_result($res,$i,nome));
					$nome_admin               = trim(pg_result($res,$i,nome_completo));
					$data                     = trim(pg_result($res,$i,data));
					$visita_posto             = trim(pg_result($res,$i,visita_posto));

					echo "<tr class='Conteudo' height='15'>";
					echo "<td align='center'>$nome_admin</td>";
					echo "<td align=center>$data</td>";
					echo "<td align='center'>$nome_posto</td>";
					echo "<td width='60' align='center'>";
					echo "<a href=rg-gat-001_suggar.php?visita_posto=$visita_posto target='blank'><img src='imagens/btn_consulta.gif' border=0></a>";
					echo "</td>\n";

					echo "</tr>";
			}
					echo "</table>";
		}

	}



?>
