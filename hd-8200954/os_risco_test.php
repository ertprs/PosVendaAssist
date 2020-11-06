<script type="text/javascript">
function lista_risco(sel) {
    var url = "";
        url = "os_risco_lista.php?sel=" + sel;
        janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=650,height=600,top=18,left=20");
        janela.focus();
}
</script>

<?

include_once "funcoes.php";

if($login_fabrica == 1){
#------ Volta a OS de troca recusada para aprovação -----#
$os_troca_aprovacao = $_GET['troca_aprovacao'];
if (strlen ($os_troca_aprovacao) > 0) {
		$sql_aprova = "update tbl_os_troca set status_os = null WHERE os = $os_troca_aprovacao;";
		$res_aprova = @pg_exec ($con,$sql_aprova);
}

#---------------- Fim troca aprovação -------------------#
}

if($login_fabrica <> 0 AND $login_fabrica <> 35){

	########################################################
	# VERIFICA SE TEM PEDIDO EM ABERTO HA MAIS DE UMA SEMANA
	########################################################
	$sqlX = "SELECT to_char (current_date - INTERVAL '6 day', 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dt_inicial = pg_result ($resX,0,0) . " 00:00:00";
	$dt_inicial = '2005-12-26 13:40:00';

	if ($login_fabrica == 1) {
		$sql = "SELECT  lpad(tbl_pedido.pedido_blackedecker::text,5,'0') AS pedido_blackedecker,
						tbl_pedido.seu_pedido
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
				AND     tbl_pedido.status_pedido <> 14
				AND     tbl_pedido.posto             = $login_posto
				AND     tbl_pedido.fabrica           = $login_fabrica
				ORDER BY tbl_pedido.pedido DESC LIMIT 1;";
		$res = pg_exec ($con,$sql);
		if (pg_numrows($res) > 0) {
			$pedido_blackedecker = trim(pg_result($res,0,pedido_blackedecker));
			$seu_pedido          = trim(pg_result($res,0,seu_pedido));

			if (strlen($seu_pedido)>0){
				$pedido_blackedecker = fnc_so_numeros($seu_pedido);
			}

			echo "<table border=0 width='500'>\n";
			echo "<tr>\n";
			echo "<td>";
			echo "<font size='2' color='#ff0000'><B>Existe o pedido de número <font color='#CC3300'>$pedido_blackedecker</font> sem finalização, o qual ainda não foi enviado para a fábrica.<br>Por gentileza, acesse a tela de digitação de pedidos e clique no botão <font color='#CC3300'>FINALIZAR</font>.</B></font>";
			echo "</td>\n";
			echo "</tr>\n";
			echo "</table>\n";
			echo "<br>\n";
		}
	}

	$sql =	"SELECT tbl_os.os                                                  ,
					tbl_os.sua_os                                              ,
					tbl_os.tipo_atendimento                                    ,
					LPAD(tbl_os.sua_os,10,'0')                   AS os_ordem   ,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YY')   AS abertura     ,
					tbl_produto.produto                                        ,
					tbl_produto.referencia                                     ,
					tbl_produto.descricao                                      ,
					tbl_produto.voltagem
			FROM tbl_os
			JOIN tbl_produto    ON tbl_produto.produto    = tbl_os.produto
			WHERE tbl_os.fabrica = $login_fabrica
			AND   tbl_os.posto   = $login_posto
			AND   tbl_os.excluida IS FALSE ";
			if($login_fabrica == 11 or $login_fabrica == 51) {
				$sql .= " AND tbl_os.data_abertura <= CURRENT_DATE - INTERVAL '30 days' ";
			}else{
				$sql .= " AND tbl_os.data_abertura <= CURRENT_DATE - INTERVAL '15 days'";
			}
			$sql .= " AND   tbl_os.data_fechamento IS NULL LIMIT 3";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<table width='500' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align = 'center'>";
		echo "<tr  height='15' bgcolor='#FF0000' height='30'>";
		echo "<td colspan='3' background='admin/imagens_admin/vermelho.gif' class='Titulo' height='30'>";
		if($login_fabrica <> 11 and $login_fabrica <> 51){
			if($sistema_lingua == "ES") {
				echo "&nbsp;OS SIN FECHA DE CIERRE HACE 15 DÍAS O MÁS DE LA FECHA DE ABERTURA&nbsp;";
			}else{
				echo "&nbsp;OS SEM DATA DE FECHAMENTO HÁ 15 DIAS OU MAIS DA DATA DE ABERTURA&nbsp;";
			}
		}elseif($login_fabrica ==11 or $login_fabrica == 51){ //HD 52453
			echo "&nbsp;OS PENDENTES A MAIS DE 30 DIAS&nbsp;";
		}
		if($login_fabrica <> 11){
			echo "<br><font color='#FFFF00'>";
			if($sistema_lingua == "ES") {
				echo "";
			}else{
				echo "Perigo de PROCON conforme artigo 18 do C.D.C.";
			}
		}
		echo "</font></td>";
		echo "</tr>";
		echo "<tr class='Titulo' height='15' bgcolor='#FF0000'>";
		echo "<td>OS</td>";
		echo "<td>ABERTURA</td>";
		echo "<td>";
		if($sistema_lingua == "ES") {
			echo "PRODUCTO";
		}else{
			echo "PRODUTO";
		}
		echo "</td>";
		echo "</tr>";
		for ($a = 0 ; $a < pg_numrows($res) ; $a++) {
			$os               = trim(pg_result($res,$a,os));
			$sua_os           = trim(pg_result($res,$a,sua_os));
			$tipo_atendimento = trim(pg_result($res,$a,tipo_atendimento));
			$abertura         = trim(pg_result($res,$a,abertura));
			$produto          = trim(pg_result($res,$a,produto));
			$referencia       = trim(pg_result($res,$a,referencia));
			$descricao        = trim(pg_result($res,$a,descricao));


			//--=== Tradução para outras linguas ============================= Raphael HD:1212
			$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = '$sistema_lingua'";

			$res_idioma = @pg_exec($con,$sql_idioma);
			if (@pg_numrows($res_idioma) >0) {
				$descricao  = trim(@pg_result($res_idioma,0,descricao));
			}
			//--=== Tradução para outras linguas ================================================

			$cor = ($a % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

			$produto_completo = $referencia . " - " . $descricao;

			echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
			echo "<td class='Conteudo' >";
			if ($login_fabrica == 3) {
				echo "<a href='os_press.php?os=$os' target='_new'>";
			}else{
				if ($login_fabrica == 1 AND ($tipo_atendimento=="17" OR $tipo_atendimento=='18')){
					echo "<a href='os_press.php?os=$os'>";
				}else{
					echo "<a href='os_item.php?os=$os'>";
				}
			}
			if($login_fabrica==1)echo $codigo_posto;
			if(strlen($sua_os)==0)echo $os;
			else                  echo "$sua_os";
			echo "</a></td>";
			echo "<td align='center'>" . $abertura . "</td>";

			if ($sistema_lingua=='ES') echo "<td nowrap><acronym title='Referencia: $referencia\nDescripción: $descricao' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";
			else echo "<td nowrap><acronym title='Referência: $referencia\nDescrição: $descricao' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";

			echo "</tr>";
		}
		echo "<tr>";
			echo "<td class='Conteudo' colspan='3' align='center'><a href= \"javascript: lista_risco(15)\">LISTAR TODAS</a></td>";
		echo "</tr>";
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
						tbl_produto.produto                                        ,
						tbl_produto.referencia                                     ,
						tbl_produto.descricao                                      ,
						tbl_produto.voltagem,
						tbl_posto_fabrica.codigo_posto
				FROM tbl_os
				JOIN tbl_produto    ON tbl_produto.produto    = tbl_os.produto
				LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE tbl_os.fabrica = $login_fabrica
				AND   tbl_os.posto   = $login_posto
				AND   (tbl_os.data_abertura + INTERVAL '30 days') <= current_date
				AND   tbl_os.data_fechamento IS NULL
				AND  tbl_os.excluida is FALSE LIMIT 3";
		$res = pg_exec($con,$sql);
		if ($ip=="200.228.76.93") echo $sql;

		if (pg_numrows($res) > 0) {
			echo "<table width='500' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
			echo "<tr class='Titulo' height='15' bgcolor='#FF0000'>";
			echo "<td colspan='3'  background='admin/imagens_admin/vermelho.gif'>";
			if($sistema_lingua == "ES") echo "OS QUE EXCEDERAN EL PLAZO LIMITE DE 30 DÍAS PARA CIERRE";
			else                        echo "OS QUE EXCEDERAM O PRAZO LIMITE DE 30 DIAS PARA FECHAMENTO";
			echo "<br><font color='#FFFF00'>";
			if($sistema_lingua == "ES") echo "Clique em la OS para informar el motivo";
			else                        echo "Clique na OS para informar o Motivo";
			echo "</font></td>";
			echo "</tr>";
			echo "<tr class='Titulo' height='15' bgcolor='#FF0000' >";
			echo "<td>OS</td>";
			echo "<td>ABERTURA</td>";
			echo "<td>";
			if($sistema_lingua == "ES") echo "PRODUCTO";
			else                        echo "PRODUTO";
			echo "</td>";
			echo "</tr>";
			for ($a = 0 ; $a < pg_numrows($res) ; $a++) {
				$os               = trim(pg_result($res,$a,os));
				$sua_os           = trim(pg_result($res,$a,sua_os));
				$abertura         = trim(pg_result($res,$a,abertura));
				$produto          = trim(pg_result($res,$a,produto));
				$referencia       = trim(pg_result($res,$a,referencia));
				$descricao        = trim(pg_result($res,$a,descricao));
				$voltagem         = trim(pg_result($res,$a,voltagem));
				$codigo_posto   = trim(pg_result($res,$a,codigo_posto));
				//--=== Tradução para outras linguas ============================= Raphael HD:1212
				$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = '$sistema_lingua'";

				$res_idioma = @pg_exec($con,$sql_idioma);
				if (@pg_numrows($res_idioma) >0) {
					$descricao  = trim(@pg_result($res_idioma,0,descricao));
				}
				//--=== Tradução para outras linguas ================================================

				$produto_completo = $referencia . " - " . $descricao . " - " . $voltagem;

				$cor = ($a % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

				echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
				echo "<td align='center'><a href='os_motivo_atraso.php?os=$os' target='_blank'>";
				if($login_fabrica==1)echo $codigo_posto;
				if(strlen($sua_os)==0)echo $os;
				else                  echo $sua_os;
				"</a></td>";
				echo "<td align='center'>" . $abertura . "</td>";
				if ($sistema_lingua=='ES') echo "<td><acronym title='Referencia: $referencia\nDescripción: $descricao\nVoltaje: $voltagem' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";
				else echo "<td><acronym title='Referência: $referencia\nDescrição: $descricao\nVoltagem: $voltagem' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";
				echo "</tr>";
			}
			echo "<tr>";
				echo "<td class='Conteudo' colspan='3' align='center'><a href= \"javascript: lista_risco(30)\">LISTAR TODAS</a></td>";
			echo "</tr>";
			echo "</table>";
			echo "<br>";
		}
		##### OS QUE EXCEDERAM O PRAZO LIMITE DE 30 DIAS PARA FECHAMENTO #####
	}

//--==== OS RECUSADAS=============================================================--\\

	$sql =	"SELECT tbl_posto_fabrica.codigo_posto           ,
					tbl_os.os                                ,
					tbl_os.sua_os                            ,
					tbl_os.tipo_atendimento                  ,
					TO_CHAR(tbl_os.data_digitacao,'DD/MM/YY') AS data_digitacao,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YY') AS data_abertura ,
					(SELECT status_os               FROM tbl_os_status WHERE tbl_os_status.os = tbl_os.os AND tbl_os_status.status_os IN (13,14,91) ORDER BY tbl_os_status.data DESC LIMIT 1) AS status_os ,
					(SELECT observacao              FROM tbl_os_status WHERE tbl_os_status.os = tbl_os.os AND tbl_os_status.status_os IN (13,14,91) ORDER BY tbl_os_status.data DESC LIMIT 1) AS observacao ,
					(SELECT tbl_status_os.descricao FROM tbl_os_status JOIN tbl_status_os USING (status_os) WHERE tbl_os_status.os = tbl_os.os AND tbl_os_status.status_os IN (13,14,91) ORDER BY tbl_os_status.data DESC LIMIT 1) AS status_descricao,
					(SELECT tbl_os_status.status_os_troca FROM tbl_os_status JOIN tbl_status_os USING (status_os) WHERE tbl_os_status.os = tbl_os.os AND tbl_os_status.status_os IN (13,14,91) ORDER BY tbl_os_status.data DESC LIMIT 1) AS status_os_troca,
					(SELECT status_os               FROM tbl_os_status WHERE tbl_os_status.os = tbl_os.os AND tbl_os_status.status_os IN (19) ORDER BY tbl_os_status.data DESC LIMIT 1) AS troca_aprovada
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
				AND length ((SELECT observacao FROM tbl_os_status WHERE tbl_os_status.os = tbl_os.os AND tbl_os_status.status_os IN (13,14,91) AND observacao <> 'Extrato Acumulado Geral' ORDER BY tbl_os_status.data DESC LIMIT 1)) > 0 ;
				";

	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {

		$extrato = '';
		$pendencia_doc = array();

		$j=0;

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
			$os             = trim(pg_result($res,$i,os));
			$sua_os         = trim(pg_result($res,$i,sua_os));
			$tipo_atendimento = trim(pg_result($res,$i,tipo_atendimento));
			$data_digitacao = trim(pg_result($res,$i,data_digitacao));
			$data_abertura  = trim(pg_result($res,$i,data_abertura));
			$observacao     = trim(pg_result($res,$i,observacao));
			$status_os      = trim(pg_result($res,$i,status_os));
			$status_os_troca= trim(pg_result($res,$i,status_os_troca));
			$troca_aprovada = trim(pg_result($res,$i,troca_aprovada));


			#Se tiver status 19, a OS foi recusada mas depois APROVADA, entao nao deve ser mostrado
			#HD 13013
			if ($troca_aprovada=="19" AND $status_os_troca == 't' AND $status_os <> 91){
				continue;
			}
			$sql2 = "SELECT status_os FROM tbl_os_troca WHERE os = $os";
			$res2 = pg_exec($con,$sql2);
			if(pg_numrows($res2)>0){
				if(strlen(trim(pg_result($res2,0,0)))==0) continue;
			}

			if($j==0){
				echo "<table width='500' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
				echo "<tr class='Titulo'>";
				echo "<td colspan='5' bgcolor='#FFFFCC' background='admin/imagens_admin/amarelo.gif' height='25'>";
				if($login_fabrica==20){
					if($sistema_lingua == "ES") echo "RELACIÓN DE OS ACUMULADAS";
					else                        echo "RELAÇÃO DE OSs ACUMULADAS";
				}
				else                   echo "RELAÇÃO DE OSs RECUSADAS";
				echo "</td>";
				echo "</tr>";
				echo "<tr class='Titulo'  bgcolor='#FFFFCC' >";
				echo "<td>OS</td>";
				echo "<td>ABERTURA</td>";
				echo "<td>STATUS</td>";
				if ($sistema_lingua=='ES') echo "<td>OBSERVACIÓN</td>";
				else echo "<td>OBSERVAÇÃO</td>";
				if($login_fabrica==1)echo "<td>VOLTAR OS P/ APROVAÇÃO</td>";
				if($login_fabrica == 51) echo "<td>Ação</td>";
				echo "</tr>";
			}
			$cor = ($j % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

			if($status_os == 91){

				array_push($pendencia_doc,array($codigo_posto    ,
												$os              ,
												$sua_os          ,
												$data_abertura   ,
												$tipo_atendimento,
												$observacao
												));

			}else{

				echo "<tr class='Conteudo' bgcolor='$cor' >";
				echo "<td class='Conteudo' >";
				if ($login_fabrica == 1 AND ($tipo_atendimento=="17" OR $tipo_atendimento=='18')){
					echo "<a href='os_cadastro_troca.php?os=$os'>";
				}else{
					echo "<a href='os_item.php?os=$os'>";
				}
				if($login_fabrica==1)
					echo $codigo_posto.$sua_os;
				else
					echo $sua_os;

				echo "</a></td>";
				echo "<td align='center'>" . $data_abertura . "</td>";
				echo "<td align='center'>";
				if($status_os==13) {
					if ($status_os_troca=='t'){
						echo "Troca Recusada";
					}else{
						if ($sistema_lingua=='ES')	echo "Rechazada";
						else						echo "Recusada";
					}
				} elseif($status_os==14)echo "Retirada";
				echo "</td>";

				if ($sistema_lingua=='ES') {
					echo "<td><b>Obs. Planta: </b><br><a href=\"os_cadastro.php?os=$os\" target='_blank'>" . $observacao . "</a></td>";
				}else{
					if ($login_fabrica == 1 AND $status_os_troca=='t'){
						echo "<td><b>Obs. Fábrica: </b><br>$observacao </td>";
						echo "<td>";
							echo "<FORM METHOD='POST' ACTION='$PHP_SELF'>";
							echo "<a href=\"javascript: if (confirm('Deseja realmente voltar a OS $sua_os para aprovação. ?') == true) { window.location='$PHP_SELF?troca_aprovacao=$os'; }\"><img id='troca_aprovacao_$i' border='0' src='imagens/btn_aprovacao.gif'></a>";
							echo "</FORM>";
						echo "</td>";
					}else{
						echo "<td><b>Obs. Fábrica: </b><br><a href=\"os_cadastro.php?os=$os\" target='_blank'>" . $observacao . "</a></td>";
					}
				}
				echo "</tr>";
			}
			$j++;
		}
		if ($j>0){
			echo "</table>";
			echo "<br>";
		}
	}

	if(sizeof($pendencia_doc) > 0){
		echo "<table width='500' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
		echo "<tr class='Titulo'>";
		echo "<td colspan='4' bgcolor='#FFFFCC' background='admin/imagens_admin/amarelo.gif' height='25'>";
			echo "RELAÇÃO DE OSs COM PENDÊNCIA DE DOCUMENTO";
		echo "</td>";
		echo "</tr>";
		echo "<tr class='Titulo'  bgcolor='#FFFFCC' >";
		echo "<td>OS</td>";
		echo "<td>ABERTURA</td>";
		echo "<td>STATUS</td>";
		echo "<td>OBSERVAÇÃO</td>";
		echo "</tr>";

		for($i=0;$i<sizeof($pendencia_doc);$i++){
			$codigo_posto          = $pendencia_doc[$i][0];
			$os                    = $pendencia_doc[$i][1];
			$sua_os                = $pendencia_doc[$i][2];
			$data_abertura         = $pendencia_doc[$i][3];
			$tipo_atendimento      = $pendencia_doc[$i][4];
			$observacao            = $pendencia_doc[$i][5];

			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

			echo "<tr class='Conteudo' bgcolor='$cor' >";
			echo "<td class='Conteudo' >";
			if ($login_fabrica == 1 AND ($tipo_atendimento=="17" OR $tipo_atendimento=='18')){
				echo "<a href='os_cadastro_troca.php?os=$os'>";
			}else{
				echo "<a href='os_item.php?os=$os'>";
			}
			if($login_fabrica==1)
				echo $codigo_posto.$sua_os;
			else
				echo $sua_os;

			echo "</a></td>";
			echo "<td align='center'>" . $data_abertura . "</td>";
			echo "<td align='center'>";
				echo "Pendência Doc.";
			echo "</td>";

			if ($login_fabrica == 1 AND ($tipo_atendimento=="17" OR $tipo_atendimento=='18')){
				echo "<td><b>Obs. Fábrica: </b><br><a href=\"os_cadastro_troca.php?os=$os\" target='_blank'>" . $observacao . "</a></td>";
			}else{
				echo "<td><b>Obs. Fábrica: </b><br><a href=\"os_cadastro.php?os=$os\" target='_blank'>" . $observacao . "</a></td>";
			}
			echo "</tr>";
		}
		echo "</table>";
	}


//--==== OS SEDEX RECUSADAS=============================================================--\\
	if($login_fabrica == 1){
		$sql = "SELECT  tbl_os_sedex.os_sedex      ,
						tbl_os_sedex.sua_os_destino,
						tbl_os_sedex.data          ,
						tbl_os_status.observacao
					FROM tbl_os_sedex
					JOIN tbl_os_status ON tbl_os_sedex.os_sedex = tbl_os_status.os_sedex
				WHERE tbl_os_sedex.posto_destino = $login_posto
				AND   tbl_os_sedex.fabrica = $login_fabrica
				AND   tbl_os_sedex.finalizada ISNULL;
			";

		$res = pg_exec($con,$sql);

		if (pg_numrows($res) > 0) {

			$extrato = '';
			for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
				$os_sedex       = trim(pg_result($res,$i,os_sedex));
				$data           = trim(pg_result($res,$i,data));
				$sua_os_destino = trim(pg_result($res,$i,sua_os_destino));
				$observacao     = trim(pg_result($res,$i,observacao));
				//2006-01-01
				$data = substr($data,8,2) ."/". substr($data,5,2) ."/". substr($data,0,3);

				if($i==0){
					echo "<table width='500' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
					echo "<tr class='Titulo'>";
					echo "<td colspan='4' bgcolor='#FFFFCC' >RELAÇÃO DE OSs SEDEX RECUSADAS</td>";
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
				echo "<td class='Conteudo' ><a href='sedex_cadastro_complemento.php?os_sedex=$os_sedex'>";
				echo $sua_os_destino;
				echo "</a></td>";
				echo "<td align='center'>" . $data . "</td>";
				echo "<td align='center'>Recusada</td>";
				echo "<td><b>Obs. Fábrica: </b><br><a href='sedex_cadastro_complemento.php?os_sedex=$os_sedex' target='_blank'>" . $observacao . "</a></td>";

				echo "</tr>";

			}
			echo "</table>";
			echo "<br>";
		}
	}
}

if($login_fabrica == 35){
	$sql = "SELECT tbl_os.os                                                ,
				tbl_os.sua_os                                           ,
				tbl_os.tipo_atendimento                                 ,
				LPAD(tbl_os.sua_os,10,'0') AS os_ordem                  ,
				TO_CHAR(tbl_os.data_abertura,'DD/MM/YY') AS abertura    ,
				tbl_produto.produto                                     ,
				tbl_produto.referencia                                  ,
				tbl_produto.descricao                                   ,
				tbl_produto.voltagem
			FROM tbl_os
			JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
			LEFT JOIN tbl_os_produto using(os)
			WHERE tbl_os.fabrica     = $login_fabrica
			AND tbl_os.posto         = $login_posto
			AND tbl_os.data_abertura <= CURRENT_DATE - INTERVAL '15 days'
			AND tbl_os.data_fechamento IS NULL
			AND coalesce(tbl_os_produto.os_produto,null) is null
			LIMIT 3;";

	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<table width='500' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align = 'center'>";
		echo "<tr  height='15' bgcolor='#FF0000' height='30'>";
		echo "<td colspan='3' background='admin/imagens_admin/vermelho.gif' class='Titulo' height='30'>";
		if($sistema_lingua == "ES") {
			echo "&nbsp;OS SIN FECHA DE CIERRE HACE 15 DÍAS O MÁS DE LA FECHA DE ABERTURA&nbsp;";
		}else{
			echo "&nbsp;O.S's ABERTAS A MAIS DE 15 DIAS SEM LANÇAMENTO DE PEÇAS&nbsp;";
		}
		echo "<br><font color='#FFFF00'>";
		if($sistema_lingua == "ES") {
			echo "";
		}else{
			echo "Perigo de PROCON conforme artigo 18 do C.D.C.";
		}
		echo "</font></td>";
		echo "</tr>";
		echo "<tr class='Titulo' height='15' bgcolor='#FF0000'>";
		echo "<td>OS</td>";
		echo "<td>ABERTURA</td>";
		echo "<td>";
		if($sistema_lingua == "ES") {
			echo "PRODUCTO";
		}else{
			echo "PRODUTO";
		}
		echo "</td>";
		echo "</tr>";
		for ($a = 0 ; $a < pg_numrows($res) ; $a++) {
			$os               = trim(pg_result($res,$a,os));
			$sua_os           = trim(pg_result($res,$a,sua_os));
			$tipo_atendimento = trim(pg_result($res,$a,tipo_atendimento));
			$abertura         = trim(pg_result($res,$a,abertura));
			$produto          = trim(pg_result($res,$a,produto));
			$referencia       = trim(pg_result($res,$a,referencia));
			$descricao        = trim(pg_result($res,$a,descricao));


			//--=== Tradução para outras linguas ============================= Raphael HD:1212
			$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = '$sistema_lingua'";

			$res_idioma = @pg_exec($con,$sql_idioma);
			if (@pg_numrows($res_idioma) >0) {
				$descricao  = trim(@pg_result($res_idioma,0,descricao));
			}
			//--=== Tradução para outras linguas ================================================

			$cor = ($a % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

			$produto_completo = $referencia . " - " . $descricao;

			echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
			echo "<td class='Conteudo' >";
			echo "<a href='os_item.php?os=$os'>";

			if(strlen($sua_os)==0) echo $os;
			else                  echo "$sua_os";
			echo "</a></td>";
			echo "<td align='center'>" . $abertura . "</td>";

			if ($sistema_lingua=='ES') echo "<td nowrap><acronym title='Referencia: $referencia\nDescripción: $descricao' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";
			else echo "<td nowrap><acronym title='Referência: $referencia\nDescrição: $descricao' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";

			echo "</tr>";
		}
		echo "<tr>";
			echo "<td class='Conteudo' colspan='3' align='center'><a href= \"javascript: lista_risco(15)\">LISTAR TODAS</a></td>";
		echo "</tr>";
		echo "</table>";
		echo "<br>";
	}


	$sql = "SELECT tbl_os.os ,
					tbl_os.sua_os ,
					tbl_os.tipo_atendimento ,
					LPAD(tbl_os.sua_os,10,'0') AS os_ordem ,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YY') AS abertura ,
					tbl_produto.produto ,
					tbl_produto.referencia ,
					tbl_produto.descricao ,
					tbl_produto.voltagem
				FROM tbl_os
				JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
				WHERE tbl_os.fabrica = $login_fabrica
				AND tbl_os.posto = $login_posto
				AND tbl_os.data_abertura <= CURRENT_DATE - INTERVAL '25 days'
				AND tbl_os.data_fechamento IS NULL
				LIMIT 3;";

	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<table width='500' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align = 'center'>";
		echo "<tr  height='15' bgcolor='#FF0000' height='30'>";
		echo "<td colspan='3' background='admin/imagens_admin/vermelho.gif' class='Titulo' height='30'>";
		if($sistema_lingua == "ES") {
			echo "&nbsp;OS SIN FECHA DE CIERRE HACE 15 DÍAS O MÁS DE LA FECHA DE ABERTURA&nbsp;";
		}else{
			echo "&nbsp;O.S's ABERTAS A MAIS DE 25 DIAS SEM DATA DE FECHAMENTO, INDEPENDENTE DO LANÇAMENTO DE PEÇAS&nbsp;";
		}
		echo "<br><font color='#FFFF00'>";
		if($sistema_lingua == "ES") {
			echo "";
		}else{
			echo "Perigo de PROCON conforme artigo 18 do C.D.C.";
		}
		echo "</font></td>";
		echo "</tr>";
		echo "<tr class='Titulo' height='15' bgcolor='#FF0000'>";
		echo "<td>OS</td>";
		echo "<td>ABERTURA</td>";
		echo "<td>";
		if($sistema_lingua == "ES") {
			echo "PRODUCTO";
		}else{
			echo "PRODUTO";
		}
		echo "</td>";
		echo "</tr>";
		for ($a = 0 ; $a < pg_numrows($res) ; $a++) {
			$os               = trim(pg_result($res,$a,os));
			$sua_os           = trim(pg_result($res,$a,sua_os));
			$tipo_atendimento = trim(pg_result($res,$a,tipo_atendimento));
			$abertura         = trim(pg_result($res,$a,abertura));
			$produto          = trim(pg_result($res,$a,produto));
			$referencia       = trim(pg_result($res,$a,referencia));
			$descricao        = trim(pg_result($res,$a,descricao));


			//--=== Tradução para outras linguas ============================= Raphael HD:1212
			$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = '$sistema_lingua'";

			$res_idioma = @pg_exec($con,$sql_idioma);
			if (@pg_numrows($res_idioma) >0) {
				$descricao  = trim(@pg_result($res_idioma,0,descricao));
			}
			//--=== Tradução para outras linguas ================================================

			$cor = ($a % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

			$produto_completo = $referencia . " - " . $descricao;

			echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
			echo "<td class='Conteudo' >";
			echo "<a href='os_item.php?os=$os' >";

			if(strlen($sua_os)==0) echo $os;
			else                  echo "$sua_os";
			echo "</a></td>";
			echo "<td align='center'>" . $abertura . "</td>";

			if ($sistema_lingua=='ES') echo "<td nowrap><acronym title='Referencia: $referencia\nDescripción: $descricao' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";
			else echo "<td nowrap><acronym title='Referência: $referencia\nDescrição: $descricao' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";

			echo "</tr>";
		}
		echo "<tr>";
			echo "<td class='Conteudo' colspan='3' align='center'><a href= \"javascript: lista_risco(25)\">LISTAR TODAS</a></td>";
		echo "</tr>";
		echo "</table>";
		echo "<br>";
	}
}


?>
