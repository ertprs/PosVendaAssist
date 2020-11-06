<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

$login_resumo 	  = $_GET['login_resumo'];
$periodo     	  = $_GET['periodo'];
$l_fabrica     	  = $_GET['login_fabrica'];
$tipo_pesquisa    = $_GET['tipo_pesquisa'];

?>

<style type="text/css">

a:link {text-decoration:none;color:#0000ff;} 
a:visited {text-decoration:none;color:#0000ff;} 
a:active {text-decoration:none;color:#0000ff;} 
a:hover {text-decoration:underline;color:#0000ff;} 

.estilo_tabela{
	font-size: 11px;
	font-weight: bold;
	font-family: arial;	
	border:1px solid black;
	color: #000000;
	text-align: center;
	width: 75%;	
}

.titulo_tabela{
	font-size: 11px;
	font-weight: bold;
	font-family: arial;
	text-align: center;
	color: #FFFFFF;
	border-bottom-width: medium;
	border-bottom-style: solid;
	border-bottom-color: #E4E4E4;
	background-color: #596D9B;
	text-transform: uppercase;
}

</style>

<?php

if ($tipo_pesquisa == 'ultima') {
	$campo = "(
					SELECT tbl_hd_chamado_item.data
			        FROM   tbl_hd_chamado_item
			        WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
			        ORDER BY tbl_hd_chamado_item.data DESC
			        LIMIT 1
				)";
} else {
	$campo = "data";
}

switch ($periodo) {
	case "mais120":		
		$caso_periodo = "(current_timestamp - {$campo} > interval '119 days')";
		break;
	case "90a120":		
		$caso_periodo = "(current_timestamp - {$campo} BETWEEN interval '89 days' AND interval '120 days')";
		break;
	case "60a89":		
		$caso_periodo = "(current_timestamp - {$campo} BETWEEN interval '59 days' AND interval '89 days')";
		break;
	case "45a59": 
		$caso_periodo = "(current_timestamp - {$campo} BETWEEN interval '44 days' AND interval '59 days')";
		break;
	case "30a44":
		$caso_periodo = " (current_timestamp - {$campo} BETWEEN interval '29 days' AND interval '44 days')";
		break;
	case "15a29":
		$caso_periodo = "(current_timestamp - {$campo} BETWEEN interval '14 days' AND interval '29 days')";
		break;
	case "7a14":
		$caso_periodo = "(current_timestamp - {$campo} BETWEEN interval '6 days' AND interval '14 days')";
		break;
	case "1a6":		
		$caso_periodo = "(current_timestamp - {$campo} BETWEEN interval '0 day' AND interval '6 days')";
		break;
	default:		
		$caso_periodo = " ";
}

$sql_atendimentos = "SELECT 
							tbl_hd_chamado.hd_chamado AS hd_chamado,
							tbl_admin.login AS usuario_admin,
							tbl_hd_chamado.status AS status_chamado, 	
							tbl_hd_chamado.fabrica_responsavel AS id_fabrica,							
							tbl_admin.nome_completo AS nome_completo_admin,
							tbl_fabrica.nome AS nome_fabrica							
						FROM tbl_hd_chamado
						JOIN tbl_admin ON tbl_hd_chamado.atendente = tbl_admin.admin
						JOIN tbl_fabrica ON tbl_hd_chamado.fabrica = tbl_fabrica.fabrica
						WHERE tbl_admin.login = '{$login_resumo}' AND tbl_hd_chamado.status='Aberto' AND tbl_hd_chamado.fabrica_responsavel = '{$l_fabrica}' AND {$caso_periodo}
						GROUP BY tbl_hd_chamado.hd_chamado, tbl_admin.login, tbl_admin.nome_completo, tbl_hd_chamado.status, tbl_hd_chamado.fabrica_responsavel, tbl_admin.nome_completo, tbl_fabrica.nome
					";

//echo $sql_atendimentos; exit;

$res_atendimento = pg_query($con,$sql_atendimentos);

echo "<br><br>";
echo "<table align=center class='estilo_tabela'>
		<tr style='text-align:center;' class='titulo_tabela'>
			<td>Nome do Atendente</td>
			<td>Fábrica</td>
			<td>Protocolo</td>
			<td>Status do Atendimento</td>			
		</tr>
	";

for($x=0;pg_num_rows($res_atendimento)>$x;$x++){						
	
	$hd_chamado          = pg_result($res_atendimento,$x,hd_chamado);	
	$status_chamado      = pg_result($res_atendimento,$x,status_chamado);	
	$id_fabrica          = pg_result($res_atendimento,$x,id_fabrica);		
	$nome_completo_admin = pg_result($res_atendimento,$x,nome_completo_admin);
	$nome_fabrica        = pg_result($res_atendimento,$x,nome_fabrica);	

	echo "<tr style='text-align:center;'>
			<td>$nome_completo_admin</td>
			<td>$nome_fabrica</td>
			<td><a href='callcenter_interativo_new.php?callcenter=$hd_chamado' target='_blank'>$hd_chamado</a></td>
			<td>$status_chamado</td>
		</tr>
		";
}

echo "</table>";