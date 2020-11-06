<?php
	
	function retornaResultado($estado,$cond){

		global $con, $login_fabrica;

		$regiao = array('Norte','Nordeste','Centro_oeste','Sudeste','Sul');
		$qtde = (!empty($estado)) ? 1 : count($regiao);

		$retorno = "<table align='center' width='700' class='tabela'>
						<tr bgcolor='#596d9b'>
							<td align='center'><font color='#FFFFFF' face='Arial'><b>Posto</b></font></td>
							<td align='center'><font color='#FFFFFF' face='Arial'><b>Cidade</b></font></td>
							<td align='center'><font color='#FFFFFF' face='Arial'><b>Estado</b></font></td>
							<td align='center'><font color='#FFFFFF' face='Arial'><b>Qtde OS</b></font></td>
						</tr>";
		for($i = 0; $i < $qtde; $i++){

			if($qtde > 1){
				switch($regiao[$i]){
					case 'Norte':
						$estado = "'AC','AP','AM','PA','RO','RR','TO'";
					break;

					case 'Nordeste':
						$estado = "'AL','BA','CE','MA','PB','PE','PI','RN','SE'";
					break;

					case 'Centro_oeste':
						$estado = "'DF','GO','MT','MS'";
					break;

					case 'Sudeste':
						$estado = "'ES','MG','RJ','SP'";
					break;

					case 'Sul':
						$estado = "'PR','RS','SC'";
					break;
				}
			}

			$sql = "SELECT  tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome,
						tbl_posto_fabrica.contato_estado,
						tbl_posto_fabrica.contato_cidade,
						count(tbl_os.os) AS qtde_os
					FROM tbl_os
					JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
					JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					WHERE tbl_os.fabrica = $login_fabrica
					AND tbl_os.excluida IS NOT TRUE 
					AND tbl_posto_fabrica.contato_estado IN($estado)
					$cond
					GROUP BY tbl_posto_fabrica.codigo_posto,
							 tbl_posto.nome,
							 tbl_posto_fabrica.contato_estado,
							 tbl_posto_fabrica.contato_cidade
					ORDER BY qtde_os,tbl_posto.nome ";
					//echo $sql;
			$res = pg_query($con,$sql);

			if(pg_num_rows($res) > 0){

				for($j = 0; $j < pg_num_rows($res); $j++){

					$codigo_posto = pg_fetch_result($res, $j, 'codigo_posto');
					$posto_nome	  = pg_fetch_result($res, $j, 'nome');
					$uf 		  = pg_fetch_result($res, $j, 'contato_estado');
					$cidade 	  = pg_fetch_result($res, $j, 'contato_cidade');
					$qtde_os 	  = pg_fetch_result($res, $j, 'qtde_os');

					$cor = ($j % 2) ? "#F7F5F0" : "#F1F4FA";

					if($qtde > 1 AND $j == 0){
						$retorno .= "<tr bgcolor='#596d9b'>
										<td colspan='4'><font color='#FFFFFF' face='Arial'><b>Região {$regiao[$i]}</b></font></td>
									 </tr>";
					}

					$retorno .= "<tr bgcolor='$cor'>
									<td align='left'>$codigo_posto - $posto_nome</td>
									<td align='left'>$cidade</td>
									<td>$uf</td>
									<td>$qtde_os</td>
								 </tr>";
				}

			}

		}

		$retorno .= "</table>";

		return $retorno;
	}