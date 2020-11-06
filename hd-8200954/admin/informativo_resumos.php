<?php
require_once 'dbconfig.php'; 
require_once 'includes/dbconnect-inc.php'; 

include_once '../helpdesk/mlg_funciones.php';

$estilo_td = "font-family: verdana; font-size: 11px; border-collapse: collapse; border:1px solid #596d9b;";
$estilo_tr_coluna = "	background-color:#596d9b; font: bold 11px 'Arial'; color:#FFFFFF; text-align:center;";

$informativo = intval($_GET["informativo"]);

$sql = "SELECT tbl_informativo.data_inicial,
			   tbl_informativo.data_final
		  FROM tbl_informativo
		 WHERE informativo=$informativo";
$resultado = pg_query($con, $sql);

if (pg_num_rows($resultado) == 0) {
	echo "Informativo não encontrado";
	die;
}

$informativo_data_inicial = "'" . pg_fetch_result($resultado, 'data_inicial') . " 00:00:00'";
$informativo_data_final	  = "'" . pg_fetch_result($resultado, 'data_final') . " 23:59:59'";

if ($S3_sdk_OK) {
	include S3CLASS;
	if ($S3_online)
		$s3 = new anexaS3('co', (int) $login_fabrica);
}

echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>Informativo</title>
</head>
<body>';

try 
{
	
	switch($_GET["tipo"])
	{
		case "informativotecnico":
			$sql = "SELECT
			tbl_comunicado.comunicado,
			tbl_comunicado.descricao,
			tbl_produto.referencia || ' - ' || tbl_produto.descricao AS produto,
			array(SELECT tbl_produto.referencia || ' - ' || tbl_produto.descricao FROM tbl_comunicado_produto JOIN tbl_produto ON tbl_comunicado_produto.produto=tbl_produto.produto AND tbl_comunicado_produto.comunicado=tbl_comunicado.comunicado) AS produtos
			FROM tbl_comunicado
			
			LEFT JOIN tbl_produto ON tbl_comunicado.produto=tbl_produto.produto
			 
			WHERE
			tbl_comunicado.fabrica=1
			AND tbl_comunicado.tipo='Informativo tecnico'
			AND tbl_comunicado.ativo
			AND tbl_comunicado.data BETWEEN {$informativo_data_inicial} AND {$informativo_data_final}
			";
			$resultado = pg_query($con, $sql);
			
			echo '<table width=600 cellspacing="1">
			<tr style="'.$estilo_tr_coluna.'">
				<td colspan=2 style="'.$estilo_td.'">Informativos Técnicos</td>
			</tr>
			';
			
			if (pg_num_rows($resultado) == 0) {
				echo '<tr style="">';		
					echo '<td align="center" style="'.$estilo_td.'" colspan="2">';
						echo "Nenhum informativo técnico para este período";
					echo '</td>';
				echo '</tr>';
			}
			else {
				echo '<tr style="'.$estilo_tr_coluna.'">';		
					echo '<td width="90%" style="'.$estilo_td.'">';
						echo "Informativo";
					echo '</td>';
					
					echo '<td width="10%" style="'.$estilo_td.'">';
						echo "Anexo ";
					echo '</td>';
				echo '</tr>';
				
				while ($row = pg_fetch_assoc($resultado)) 
				{
					$cor = ($cor == '#F1F4FA') ? "#F7F5F0" : "#F1F4FA";
					$row['produtos'] = implode('<br />', pg_parse_array($row['produtos']));

					$row['produtos'] .= $row['produto'];			//concatena com produto

					if ($S3_online) {
						$tipo_s3 = in_array($row['tipo'], explode(',', utf8_decode(anexaS3::TIPOS_VE))) ? 've' : 'co';
						if ($s3->tipo_anexo != $tipo_s3)
							$s3->set_tipo_anexoS3($tipo_s3);
						$s3->temAnexos($row['comunicado']);
						if ($s3->temAnexo) {
							$anexo = $s3->url;
						}
					} else {

						if(file_exists("http://posvenda.telecontrol.com.br/assist/comunicados/{$row['comunicado']}.pdf")) { //verifica se arquivo existe
							$anexo = "http://posvenda.telecontrol.com.br/assist/comunicados/{$row['comunicado']}.pdf";//joga o link do arquivo no anexo
						} else {
							$anexo = NULL;
						}
					}

					if (strlen($row[1]) > 0 && strlen($row[3]) > 0) $row[1] .= ":<br><br>";

					echo '<tr>';			
						echo '<td width="90%" style="'.$estilo_td.'; background-color:' .$cor. ';">';
							echo $row[1] . $row[3];
						echo '</td>';
					
						echo '<td width="10%" style="'.$estilo_td.'; background-color:' .$cor. ';">';
							if($anexo != NULL) { echo "<a href='$anexo' target='_blank'>Anexo</a>"; } else { echo "&nbsp;"; }
						echo '</td>';
					echo '</tr>';
				}
			}
			
			echo '</table>';
		break;
			
		case "novosparceitos":
		case "novosparceiros":
			//novos parceiros
			$sql = "SELECT tbl_credenciamento.data, tbl_posto.nome,
						   CASE WHEN tbl_tipo_posto.descricao ILIKE '%locadora%'
								THEN 'LOCADORA'
								ELSE 'POSTO AUTORIZADO'
					       END AS tipo
					  FROM tbl_credenciamento
					  JOIN tbl_posto_fabrica ON tbl_credenciamento.posto     = tbl_posto_fabrica.posto
										    AND tbl_credenciamento.fabrica   = tbl_posto_fabrica.fabrica
					  JOIN tbl_posto         ON tbl_posto_fabrica.posto      = tbl_posto.posto
					  JOIN tbl_tipo_posto    ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
					 WHERE tbl_credenciamento.fabrica = 1
					   AND tbl_credenciamento.status  = 'CREDENCIADO'
					   AND (SELECT COUNT(*)
							  FROM tbl_credenciamento AS tbl_credenciamento_interna
							 WHERE fabrica = tbl_credenciamento.fabrica
							   AND posto   = tbl_credenciamento.posto) = 1
					   AND tbl_credenciamento.data BETWEEN {$informativo_data_inicial} AND {$informativo_data_final}
					 ORDER BY tbl_posto.nome ASC;";
			
			$resultado = pg_query($con, $sql);
			
			echo '<table width=600 cellspacing="1">';
				echo '<tr style="'.$estilo_tr_coluna.'">';
					echo '<td colspan=2 style="'.$estilo_td.'">';
						echo 'Novos parceiros';
					echo '</td>';
				echo '</tr>';
			echo '</tr>';
			
			if (pg_num_rows($resultado) == 0) {
				echo '<tr style="">';		
					echo '<td align="center" style="'.$estilo_td.'" colspan="2">';
						echo "Nenhum novo parceiro neste período";
					echo '</td>';
				echo '</tr>';
			}
			else {
				$i = 0;
				
				while ($row = pg_fetch_row($resultado)) 
				{
				$i++;
				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
					echo '<tr>';			
						echo '<td width="30%" style="'.$estilo_td.'; background-color:' .$cor. ';">';
							echo "$row[2]";
						echo '</td>';
					
						echo '<td width="70%" style="'.$estilo_td.'; background-color:' .$cor. ';">';
							echo "$row[1]";
						echo '</td>';
					echo '</tr>';
				}
				
				echo '</table>';
			}
		break;
			
		case "chamadosporregiao":
			$sql ="SELECT
			COUNT(*),
			tbl_posto.estado 

			FROM
			tbl_hd_chamado
			JOIN tbl_posto ON tbl_hd_chamado.posto=tbl_posto.posto 

			WHERE
			tbl_hd_chamado.fabrica = 1
			AND tbl_hd_chamado.data BETWEEN {$informativo_data_inicial} AND {$informativo_data_final} 
			AND tbl_posto.estado IS NOT NULL

			GROUP BY tbl_posto.estado";
			
			$resultado = pg_query($con, $sql);
			$retornos = pg_num_rows($resultado);
			
			echo '<table width=600 cellspacing="1">';
			echo '<tr style="'.$estilo_tr_coluna.'">';	
				echo '<td colspan=5 style="' . $estilo_td . '">';
					echo 'Chamados por Região';
				echo '</td>';
			echo '</tr>';
			
			if (pg_num_rows($resultado) == 0) {
				echo '<tr style="">';		
					echo '<td align="center" style="'.$estilo_td.'" colspan="5">';
						echo "Nenhum chamado neste período";
					echo '</td>';
				echo '</tr>';
				echo '</table>';
			}
			else {
				$somanorte 		 = 0;
				$somacentrooeste = 0;
				$somanordeste 	 = 0;
				$somasudeste 	 = 0;
				$somasul 		 = 0;
				$norte 		 	 = array('AC','AM','RO','RR','AP','PA','TO');
				$centrooeste 	 = array('MT','MS','GO','DF');
				$nordeste 	 	 = array('MA','PI','BA','CE','PE','RN','PB','AL','SE');
				$sudeste 	 	 = array('SP','RJ','MG','ES');
				$sul 		 	 = array('PR','SC','RS');
				$i = 0;
				
				while ($row = pg_fetch_row($resultado)) 
				{
					if (in_array($row[1], $norte)) //se o estado estive dentro da variavel ele vai somando
					{
						$somanorte = $somanorte + $row[0];
					}
					
					if (in_array($row[1], $centrooeste)) 
					{
						$somacentrooeste = $somacentrooeste + $row[0];
					}
					
					if (in_array($row[1], $nordeste)) 
					{
						$somanordeste = $somanordeste + $row[0];
					}
					
					if (in_array($row[1], $sudeste)) 
					{
						$somasudeste = $somasudeste + $row[0];
					}
					
					if (in_array($row[1], $sul)) 
					{
						$somasul = $somasul + $row[0];
					}
				}

				echo '<tr style="'.$estilo_tr_coluna.'">';
				echo '	  <td style="'.$estilo_td.'" width="20%">Norte</td>';
				echo '	  <td style="'.$estilo_td.'" width="20%">Centro Oeste</td>';
				echo '	  <td style="'.$estilo_td.'" width="20%">Nordeste</td>';
				echo '	  <td style="'.$estilo_td.'" width="20%">Sudeste</td>';
				echo '	  <td style="'.$estilo_td.'" width="20%">Sul</td>';
				echo '</tr>';

				//total por regiao norte, nordeste, sul, sudeste, centro oeste
				  
				echo '<tr>';
				echo '    <td style="'.$estilo_td.'; background-color:' .$cor. ';">' . $somanorte . '</td>';
				echo '    <td style="'.$estilo_td.'; background-color:' .$cor. ';">' . $somacentrooeste . '</td>';
				echo '    <td style="'.$estilo_td.'; background-color:' .$cor. ';">' . $somanordeste . '</td>';
				echo '	  <td style="'.$estilo_td.'; background-color:' .$cor. ';">' . $somasudeste . '</td>';
				echo '    <td style="'.$estilo_td.'; background-color:' .$cor. ';">' . $somasul . '</td>';
				echo '</tr>';
				
				$array = pg_fetch_all($resultado);
				$max = ceil(pg_num_rows($resultado) / 3);
				
				echo '<table width=600 cellspacing="1">';
				echo '<tr style="'.$estilo_tr_coluna.'">';	
					echo '<td colspan=5 style="' . $estilo_td . '">';
						echo 'Chamados por Estado';
					echo '</td>';
				echo '</tr>';
				
				for ($i=0; $i<$max; $i++)
				{
					$inicial = $i * 3;
					$final	 = (($i + 1) * 3) - 1;
					$d++;
					$cor = ($d % 2) ? "#F7F5F0" : "#F1F4FA";

					echo '<tr>';
					
					for ($j=$inicial; $j<=$final; $j++)
					{	
						echo '<td width="20%" style="'.$estilo_td.'; background-color:' .$cor. '; font-weight: bold;">';
						echo $array[$j]["estado"];
						echo '</td>';
					} 
					echo '</tr>';
					echo '<tr>';
					
					for ($j=$inicial; $j<=$final; $j++)
					{	
						echo '<td width="20%" style="'.$estilo_td.'; background-color:' .$cor. ';">';
						echo $array[$j]["count"];
						echo '</td>';
					}
					echo '</tr>';
				}
				echo '</table>';
				echo '</table>';
			}
		break;
	}
}
catch(Exception $e) 
	{
		$msg_erro[] = ($e->getCode() == 1 ? "Falha ao localizar informativo" : "") . $e->getMessage();
		die;
	}
echo '
</body>
</html>
';
?>
