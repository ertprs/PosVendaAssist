<?php
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include '../includes/funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';
include "../monitora.php";

$total_os = $_GET['total_os'];
$produto = $_GET['produto'];
$data_inicial = $_GET['data_inicial'];
$data_final = $_GET['data_final'];
$pais = $_GET['pais'];

$layout_menu = "gerencia";
$title = "RELATÓRIO - FIELD CALL-RATE : OS COM OU SEM PECA";

?>

<style type="text/css">
body,table{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	margin: 0px,0px,0px,0px;
	padding:  0px,0px,0px,0px;
}
#Menu{border-bottom:#485989 1px solid;}
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

#logo{
	BORDER-RIGHT: 1px ;
	BORDER-TOP: 1px ;
	BORDER-LEFT: 1px ;
	BORDER-BOTTOM: 1px ;
	position: absolute;
	top: 1px;
	right: 1px;
	z-index: 5;
}

</style>

<link rel="stylesheet" href="../js/blue/style.css" type="text/css" id="" media="print, projection, screen" />
<script type="text/javascript" src="../js/jquery.js"></script> 
<script type="text/javascript" src="../js/jquery.tablesorter.pack.js"></script> 


	
<table width='100%' cellspacing='0' cellpadding='0' border='0' id='Menu'>
	<tr>
		<td bgcolor='#F5F9FC'>
<?php
			$sql2 = "SELECT referencia,descricao
					FROM tbl_produto 
					JOIN tbl_linha  USING(linha)
					WHERE produto in ($produto)
					AND   fabrica = $login_fabrica
					ORDER BY tbl_produto.referencia";
			$res2 = pg_exec ($con,$sql2);
?>

			<h5>Produto:
<?php
			$produto_referencia = pg_result($res2,0,0);
			$produto_descricao  = pg_result($res2,0,1);
			echo " $produto_referencia - $produto_descricao<br>";

			echo "</h5>";
			
			
			if(strlen($data_inicial)>0){

				list($y,$m,$d) = explode("-",$data_inicial);
				$data_inicial = "$d/$m/$y";

				list($y,$m,$d) = explode("-",$data_final);
				$data_final = "$d/$m/$y";

				echo "Resultado de pesquisa entre os dias <b>$data_inicial</b> e <b>$data_final</b>";

			}
?>
		</td>
	</tr>
	</table>
<?php
	if($total_os){

		if($total_os == "com_peca"){
			$sql = "SELECT BI.os,
					BI.sua_os,
					PF.codigo_posto,
					PA.nome
					FROM bi_os_item BI  
					JOIN tbl_posto PA ON BI.posto = PA.posto
					JOIN tbl_posto_fabrica PF ON PA.posto = PF.posto AND PF.fabrica = $login_fabrica
					WHERE BI.fabrica = $login_fabrica
					AND BI.excluida IS NOT TRUE 
					AND BI.produto = $produto 
					AND BI.pais = '$pais' 
					AND BI.data_fechamento BETWEEN '$data_inicial' AND '$data_final'
					GROUP BY BI.os,BI.sua_os,PF.codigo_posto,PA.nome";
		} else {
			$sql = "SELECT sua_os,
					os,
					codigo_posto,
					nome 
					FROM 
					( SELECT distinct BI.os,
					BI.sua_os,
					PF.codigo_posto,
					PA.nome
					FROM bi_os BI 
					JOIN tbl_posto PA ON BI.posto = PA.posto
					JOIN tbl_posto_fabrica PF ON PA.posto = PF.posto AND PF.fabrica = $login_fabrica
					WHERE BI.fabrica = $login_fabrica 
					AND BI.excluida IS NOT TRUE 
					AND BI.produto = $produto 
					AND BI.pais = '$pais' 
					AND BI.data_fechamento BETWEEN '$data_inicial' AND '$data_final' 
					EXCEPT 
					SELECT DISTINCT BI.os,
					BI.sua_os,
					PF.codigo_posto,
					PA.nome
					FROM bi_os_item BI 
					JOIN tbl_posto PA ON BI.posto = PA.posto
					JOIN tbl_posto_fabrica PF ON PA.posto = PF.posto AND PF.fabrica = $login_fabrica
					WHERE BI.fabrica = $login_fabrica 
					AND BI.peca IS NOT NULL 
					AND BI.excluida IS NOT TRUE 
					AND BI.produto = $produto 
					AND BI.pais = '$pais' AND BI.data_fechamento 
					BETWEEN '$data_inicial' AND '$data_final' )X;";
		}
		#echo nl2br($sql);
			$res = pg_query($con,$sql);

			if(pg_num_rows($res) > 0){
?>
				
				<TABLE width='600' border='0' cellspacing='2' cellpadding='2' align='center'  style=' border:#485989 1px solid; background-color: #e6eef7' name='relatorio' id='relatorio' class='tablesorter'>
				<thead>
				<TR>
				<TD width='100' height='15'><b>OS</b></TD>
				<TD height='15'><b>Posto</b></TD>
				</TR>
				</thead>
				<tbody>
<?php
				for($i = 0; $i < pg_num_rows($res); $i++){
					$os     = pg_result($res,$i,os);
					$sua_os = pg_result($res,$i,sua_os);
					$posto  = pg_result($res,$i,codigo_posto) . " - ". pg_result($res,$i,nome);
?>
					<TR>
					<TD align='left' nowrap><a href='../os_press.php?os=<?=$os?>' target='_blank'><?=$sua_os?></TD>
					<TD align='left' nowrap><?=$posto?></TD>
					<TD align='center' nowrap><?=$peca?></TD>
					</TR>
<?php
				}
?>
				</tbody>
				 </TABLE>
<?php				
			} else {
				echo "Nenhum resultado encontrado";
			}
		
	}

?>