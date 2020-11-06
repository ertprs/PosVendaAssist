<?
if($login_fabrica<>3 OR $login_posto=='6359'){

	########################################################
	# VERIFICA SE TEM PEDIDO EM ABERTO HA MAIS DE UMA SEMANA
	########################################################
	$sqlX = "SELECT to_char (current_date - INTERVAL '6 day', 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dt_inicial = pg_result ($resX,0,0) . " 00:00:00";
	$dt_inicial = '2005-12-26 13:40:00';

	$sql = "SELECT  lpad(tbl_pedido.pedido_blackedecker,5,0) AS pedido_blackedecker
			FROM    tbl_pedido
			WHERE   tbl_pedido.exportado           ISNULL
			AND     tbl_pedido.controle_exportacao ISNULL
			AND     tbl_pedido.admin               ISNULL
			AND     (tbl_pedido.natureza_operacao ISNULL
				  OR tbl_pedido.natureza_operacao <> 'SN-GART' 
				 AND tbl_pedido.natureza_operacao <> 'VN-REV')
			AND     tbl_pedido.pedido_os IS NOT TRUE
			AND     tbl_pedido.pedido_acessorio IS NOT TRUE
			AND     tbl_pedido.pedido_sedex IS NOT TRUE
			AND     tbl_pedido.tabela = 108
			AND     tbl_pedido.posto             = $login_posto
			AND     tbl_pedido.fabrica           = $login_fabrica
			ORDER BY tbl_pedido.pedido DESC LIMIT 1;";
//if ($ip == '201.42.112.110') echo $sql;exit;
	$res = pg_exec ($con,$sql);
	//echo $sql;exit;
	if (pg_numrows($res) > 0) {
		$pedido_blackedecker = trim(pg_result($res,0,pedido_blackedecker));
		echo "<table border=0 width='500'>\n";
		echo "<tr>\n";
		echo "<td>";
		echo "<font size='2' color='#ff0000'><B>Existe o pedido de número <font color='#CC3300'>$pedido_blackedecker</font> sem finalização, o qual ainda não foi enviado para a fábrica.<br>Por gentileza, acesse a tela de digitação de pedidos e clique no botão <font color='#CC3300'>FINALIZAR</font>.</B></font>";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "<br>\n";
	}

	$sql =	"SELECT tbl_os.os                                                  ,
					tbl_os.sua_os                                              ,
					LPAD(tbl_os.sua_os,10,'0')                   AS os_ordem   ,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YY')   AS abertura   ,
					tbl_produto.referencia                                     ,
					tbl_produto.descricao                                      ,
					tbl_produto.voltagem
			FROM tbl_os
			JOIN tbl_produto    ON tbl_produto.produto    = tbl_os.produto
			WHERE tbl_os.fabrica = $login_fabrica
			AND   tbl_os.posto   = $login_posto
			AND   (tbl_os.data_abertura + INTERVAL '15 days') <= current_date ";
			if($login_fabrica<>11)$sql .= " AND   (tbl_os.data_abertura + INTERVAL '30 days') > current_date ";

			$sql .= " AND   tbl_os.data_fechamento IS NULL
			ORDER BY os_ordem";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<table width='500' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align = 'center'>";
		echo "<tr class='Titulo' height='15' bgcolor='#FF0000'>";
		echo "<td colspan='3' >
		&nbsp;OS SEM DATA DE FECHAMENTO A 15 DIAS OU MAIS DA DATA DE ABERTURA&nbsp;
		<br><font color='#FFFF00'>Perigo de PROCON conforme artigo 18 do C.D.C.</font></td>";
		echo "</tr>";
		echo "<tr class='Titulo' height='15' bgcolor='#FF0000'>";
		echo "<td>OS</td>";
		echo "<td>ABERTURA</td>";
		echo "<td>PRODUTO</td>";
		echo "</tr>";
		for ($a = 0 ; $a < pg_numrows($res) ; $a++) {
			$os               = trim(pg_result($res,$a,os));
			$sua_os           = trim(pg_result($res,$a,sua_os));
			$abertura         = trim(pg_result($res,$a,abertura));
			$referencia       = trim(pg_result($res,$a,referencia));
			$descricao        = trim(pg_result($res,$a,descricao));
			$produto_completo = $referencia . " - " . $descricao;

			$cor = ($a % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

			echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
			echo "<td class='Conteudo' ><a href='os_item.php?os=$os'>";
			if($login_fabrica==1)echo $codigo_posto;
			echo "$sua_os</a></td>";
			echo "<td align='center'>" . $abertura . "</td>";
			echo "<td nowrap><acronym title='Referência: $referencia\nDescrição: $descricao' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";
			echo "</tr>";
		}
		echo "</table>";
		echo "<br>";
	}

	##### OS SEM DATA DE FECHAMENTO HÁ 20 DIAS OU MAIS DA DATA DE ABERTURA #####
	if($login_fabrica<>11){
/*
		$sql =	"SELECT tbl_os.os                                                  ,
						tbl_os.sua_os                                              ,
						LPAD(tbl_os.sua_os,10,'0')                   AS os_ordem   ,
						TO_CHAR(tbl_os.data_abertura,'DD/MM/YY')   AS abertura   ,
						tbl_produto.referencia                                     ,
						tbl_produto.descricao                                      ,
						tbl_produto.voltagem
				FROM tbl_os
				JOIN tbl_produto    ON tbl_produto.produto    = tbl_os.produto
				WHERE tbl_os.fabrica = $login_fabrica
				AND   tbl_os.posto   = $login_posto
				AND   (tbl_os.data_abertura + INTERVAL '20 days') <= current_date
				AND   (tbl_os.data_abertura + INTERVAL '30 days') > current_date
				AND   tbl_os.data_fechamento IS NULL
				ORDER BY os_ordem";
	//	if($ip=='200.246.168.219')echo nl2br($sql);
		$res = pg_exec($con,$sql);

		if (pg_numrows($res) > 0) {
			echo "<table width='500' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
			echo "<tr class='Titulo' height='15' bgcolor='#FF0000'>";
			echo "<td colspan='3'>OS SEM DATA DE FECHAMENTO HÁ 20 DIAS OU MAIS DA DATA DE ABERTURA<br><font color='#FFFF00'>Perigo de PROCON conforme artigo 18 do C.D.C.</font></td>";
			echo "</tr>";
			echo "<tr class='Titulo' height='15' bgcolor='#FF0000'>";
			echo "<td>OS</td>";
			echo "<td>ABERTURA</td>";
			echo "<td>PRODUTO</td>";
			echo "</tr>";
			for ($a = 0 ; $a < pg_numrows($res) ; $a++) {
				$os               = trim(pg_result($res,$a,os));
				$sua_os           = trim(pg_result($res,$a,sua_os));
				$abertura         = trim(pg_result($res,$a,abertura));
				$referencia       = trim(pg_result($res,$a,referencia));
				$descricao        = trim(pg_result($res,$a,descricao));
				$voltagem         = trim(pg_result($res,$a,voltagem));
				$produto_completo = $referencia . " - " . $descricao . " - " . $voltagem;

				$cor = ($a % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

				echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
				echo "<td class='Conteudo' ><a href='os_item.php?os=$os'>";
				if($login_fabrica==1)echo $codigo_posto;
				echo "$sua_os</a></td>";
				echo "<td align='center'>" . $abertura . "</td>";
				echo "<td><acronym title='Referência: $referencia\nDescrição: $descricao\nVoltagem: $voltagem' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";
				echo "</tr>";
			}
			echo "</table>";
			echo "<br>";
		}
*/
		##### OS SEM DATA DE FECHAMENTO HÁ 20 DIAS OU MAIS DA DATA DE ABERTURA #####

		##### OS QUE EXCEDERAM O PRAZO LIMITE DE 30 DIAS PARA FECHAMENTO #####
		$sql =	"SELECT tbl_os.os                                                  ,
						tbl_os.sua_os                                              ,
						LPAD(tbl_os.sua_os,10,'0')                   AS os_ordem   ,
						TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura   ,
						tbl_produto.referencia                                     ,
						tbl_produto.descricao                                      ,
						tbl_produto.voltagem
				FROM tbl_os
				JOIN tbl_produto    ON tbl_produto.produto    = tbl_os.produto
				WHERE tbl_os.fabrica = $login_fabrica
				AND   tbl_os.posto   = $login_posto
				AND   (tbl_os.data_abertura + INTERVAL '30 days') <= current_date
				AND   tbl_os.data_fechamento IS NULL
				AND  tbl_os.excluida is not true
				ORDER BY os_ordem";
		$res = pg_exec($con,$sql);
	//if($ip=='200.246.168.219')echo nl2br($sql);
		if (pg_numrows($res) > 0) {
			echo "<table width='500' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
			echo "<tr class='Titulo' height='15' bgcolor='#FF0000'>";
			echo "<td colspan='3'>OS QUE EXCEDERAM O PRAZO LIMITE DE 30 DIAS PARA FECHAMENTO<br><font color='#FFFF00'>Clique na OS para informar o Motivo</font></td>";
			echo "</tr>";
			echo "<tr class='Titulo' height='15' bgcolor='#FF0000'>";
			echo "<td>OS</td>";
			echo "<td>ABERTURA</td>";
			echo "<td>PRODUTO</td>";
			echo "</tr>";
			for ($a = 0 ; $a < pg_numrows($res) ; $a++) {
				$os               = trim(pg_result($res,$a,os));
				$sua_os           = trim(pg_result($res,$a,sua_os));
				$abertura         = trim(pg_result($res,$a,abertura));
				$referencia       = trim(pg_result($res,$a,referencia));
				$descricao        = trim(pg_result($res,$a,descricao));
				$voltagem         = trim(pg_result($res,$a,voltagem));
				$produto_completo = $referencia . " - " . $descricao . " - " . $voltagem;
				
				$cor = ($a % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

				echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
				echo "<td align='center'><a href='os_motivo_atraso.php?os=$os' target='_blank'>";
				if($login_fabrica==1)echo $codigo_posto;
				echo "$sua_os";
				"</a></td>";
				echo "<td align='center'>" . $abertura . "</td>";
				echo "<td><acronym title='Referência: $referencia\nDescrição: $descricao\nVoltagem: $voltagem' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";
				echo "</tr>";
			}
			echo "</table>";
			echo "<br>";
		}
		##### OS QUE EXCEDERAM O PRAZO LIMITE DE 30 DIAS PARA FECHAMENTO #####
	}
//--==== OS RECUSADAS=============================================================--\\

	$sql =	"SELECT tbl_posto_fabrica.codigo_posto ,
					tbl_os.os ,
					tbl_os.sua_os ,
					TO_CHAR(tbl_os.data_digitacao,'DD/MM/YY') AS data_digitacao,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YY') AS data_abertura ,
					(SELECT status_os               FROM tbl_os_status WHERE tbl_os_status.os = tbl_os.os AND tbl_os_status.status_os IN (13,14) ORDER BY tbl_os_status.data DESC LIMIT 1) AS status_os ,
					(SELECT observacao              FROM tbl_os_status WHERE tbl_os_status.os = tbl_os.os AND tbl_os_status.status_os IN (13,14) ORDER BY tbl_os_status.data DESC LIMIT 1) AS observacao ,
					(SELECT tbl_status_os.descricao FROM tbl_os_status JOIN tbl_status_os USING (status_os) WHERE tbl_os_status.os = tbl_os.os AND tbl_os_status.status_os IN (13,14) ORDER BY tbl_os_status.data DESC LIMIT 1) AS status_descricao
				FROM tbl_os
				JOIN tbl_os_extra USING (os)
				JOIN tbl_posto USING (posto)
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE tbl_os.finalizada IS NULL
				AND   tbl_os.data_fechamento IS NULL
				AND   tbl_os_extra.extrato IS NULL
				AND   tbl_os.posto = $login_posto
				AND   tbl_os.fabrica = $login_fabrica
				AND   tbl_os.excluida IS NOT TRUE
				AND length ((SELECT observacao FROM tbl_os_status WHERE tbl_os_status.os = tbl_os.os AND tbl_os_status.status_os IN (13,14) AND observacao <> 'Extrato Acumulado Geral' ORDER BY tbl_os_status.data DESC LIMIT 1)) > 0 ;

				";

	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {

		$extrato = '';
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
			$os             = trim(pg_result($res,$i,os));
			$sua_os         = trim(pg_result($res,$i,sua_os));
			$data_digitacao = trim(pg_result($res,$i,data_digitacao));
			$data_abertura  = trim(pg_result($res,$i,data_abertura));
			$observacao      = trim(pg_result($res,$i,observacao));
			$status_os      = trim(pg_result($res,$i,status_os));

			if($i==0){
				echo "<table width='500' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
				echo "<tr class='Titulo'>";
				echo "<td colspan='4' bgcolor='#FFFFCC' >RELAÇÃO DE OSs RECUSADAS</td>";
				echo "</tr>";
				echo "<tr class='Titulo'  bgcolor='#FFFFCC' >";
				echo "<td>OS</td>";
				echo "<td>ABERTURA</td>";
				echo "<td>STATUS</td>";
				echo "<td>OBSERVAÇÃO</td>";
				echo "</tr>";
			}
			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

			echo "<tr class='Conteudo' bgcolor='$cor' >";
			echo "<td class='Conteudo' ><a href='os_item.php?os=$os'>";
			if($login_fabrica==1)echo $codigo_posto;
			echo "$sua_os</a></td>";
			echo "<td align='center'>" . $data_abertura . "</td>";
			echo "<td align='center'>Recusada</td>";
			echo "<td><b>Obs. Fábrica: </b><br><a href=\"os_cadastro.php?os=$os\" target='_blank'>" . $observacao . "</a></td>";

			echo "</tr>";

		}
		echo "</table>";
		echo "<br>";
	}


}
?>