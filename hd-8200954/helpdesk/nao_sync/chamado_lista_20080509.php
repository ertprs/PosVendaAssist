<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
if($sistema_lingua == "ES") $TITULO = "Lista de llamados - Telecontrol Help-Desk";
else                        $TITULO = "Lista de Chamadas - Telecontrol Hekp-Desk";

include "menu.php";

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

if($_POST['hd_chamado']) $hd_chamado = trim ($_POST['hd_chamado']);
if($_GET ['hd_chamado']) $hd_chamado = trim ($_GET ['hd_chamado']);

$status         = $_GET["status"];
$resolvido      = $_GET["resolvido"];
$exigir_resposta= $_GET["exigir_resposta"];
$admin          = $_GET["admin"];
$titulo         = $_POST['titulo'];
$todos          = $_GET["todos"];
$filtro         = $_GET["filtro"];
if(strlen($_POST['filtro']) >0 )$filtro         = $_POST["filtro"];

if (strlen($titulo) == 0)  $titulo = $_GET['titulo'];

?>

<table width="700" align="center"><tr><td style='font-family: arial ; color: #666666; font-size:10px' align="justify">
<?

echo "<tr style='font-family: arial ; color: #666666' align='center'>";
	echo "<td nowrap align='left'>";
		echo "<img src='/assist/admin/imagens_admin/status_amarelo.gif' valign='absmiddle'> ";
		if($sistema_lingua == 'ES') echo "&nbsp;Aguardando aprobación&nbsp;";
		else                        echo "&nbsp;<a href='$PHP_SELF?status=Aprovação'>Aguardando Aprovação</a>&nbsp;";
	echo "</td>";
	echo "<td width='50%' nowrap align='left'>";
		echo "<li> </li>";
		if($sistema_lingua == 'ES') echo "&nbsp;Meus Chamados&nbsp;";
		else                        echo "&nbsp;<a href='$PHP_SELF?admin=admin'>Meus Chamados</a>&nbsp;";
	echo "</td>";
	echo "<td nowrap align='left'>";
		echo "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle'> ";
		if($sistema_lingua == 'ES') echo "&nbsp;Pendiente&nbsp;";
		else                        echo "&nbsp;<a href='$PHP_SELF?status=Análise&exigir_resposta=f'>Pendente Telecontrol</a>&nbsp;";
	echo "</td>";
	echo "<td nowrap align='left'>";
		echo "<img src='/assist/admin/imagens_admin/status_vermelho.gif' valign='absmiddle'> ";
		if($sistema_lingua == 'ES') echo "&nbsp;Aguardando su respuesta&nbsp;";
		else                        echo "&nbsp;<a href='$PHP_SELF?status=Análise&exigir_resposta=t'>Aguardando sua resposta</a>&nbsp;";
	echo "</td>";
	echo "<td nowrap align='left'>";
		echo "<img src='/assist/admin/imagens_admin/status_verde.gif' valign='absmiddle'> ";
		if($sistema_lingua == 'ES') echo "&nbsp;Resolvido&nbsp;";
		else                        echo "&nbsp;<a href='$PHP_SELF?status=Resolvido&filtro=1'>Meus Resolvidos</a>&nbsp;";
	echo "</td>";
	echo "<td nowrap align='left'>";
		echo "<li> </li>";
		if($sistema_lingua == 'ES') echo "&nbsp;Todos Chamados&nbsp;";
		else                        echo "&nbsp;<a href='$PHP_SELF?todos=todos&filtro=1'>Todos Chamados</a>&nbsp;";
	echo "</td>";
	echo "</tr>";
echo "</table>";

echo "<br>";

if($filtro==1){
	echo "<FORM METHOD='POST' ACTION='$PHP_SELF'>";
	echo "<table width = '400' align = 'center' cellpadding='0' cellspacing='0' border='0' style='font-family: verdana ; font-size:11px ; color: #666666'>";
	echo "<input type='hidden' name='status' value='$status'>";
	echo "<input type='hidden' name='todos' value='$todos'>";
	echo "<input type='hidden' name='filtro' value='$filtro'>";
	echo "<tr>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   align = 'center' width='100%' style='font-family: arial ; color:#666666'>&nbsp;</td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "<tr bgcolor='#D9E8FF' >";
		echo "<td><b><CENTER>Pesquisa Chamados</CENTER></b></td>";
	echo "</tr>";
	echo "<tr align='left'  height ='70' valign='top'>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td>";

		echo "<table border='0'  cellpadding='2' cellspacing='3' width='400' align='center' style='font-family: verdana ; font-size:11px ; color: #00000'>";
		echo "<tr>";
			echo "<td align='center' colspan='2'>NÚMERO DO CHAMADO</td>";
		echo "</tr>";
		echo "<tr>";
			echo "<td align='center' colspan='2'><input type='text' size='6' maxlength='8' name=hd_chamado value=$hd_chamado></td>";
		echo "</tr>";
		echo "<tr>";
			echo "<td align='center'>Mês</td>";
			echo "<td align='center'>Ano</td>";
		echo "</tr>";
		echo "<tr>";
			echo "<td width='50%' align='center'>";
				echo "<select name='mes' size='1'>";
				echo "<option value=''></option>";
				
				for ($i = 1 ; $i <= count($meses) ; $i++) {
					echo "<option value='$i'";
					if ($mes == $i) echo " selected";
					echo ">" . $meses[$i] . "</option>";
				}
				
				echo "</select>";
			echo "</td>";
			echo "<td width='50%' align='center'>";
				echo "<select name='ano' size='1'>";
				echo "<option value=''></option>";
				
				for ($i = 2006 ; $i <= date("Y") ; $i++) {
					echo "<option value='$i'";
					if ($ano == $i) echo " selected";
					echo ">$i</option>";
				}
				
				echo "</select>";
			echo "</td>";
		echo "</tr>";


/*
			echo "<td width='150'>Status</td>";
			echo "<td width='250'>";
			echo "<select class='frm' style='width: 180px;' name='status'>\n";
			echo "<option value=''></option>\n";
			echo "<option value='n'>Novo</option>\n";
			echo "<option value='a'>Análise</option>\n";
			echo "<option value='ae'>Aguardando Execução</option>\n";
			echo "<option value='e'>Execução</option>\n";
			echo "<option value=''>Em Aberto</option>\n";
			echo "<option value='r'>Resolvido</option>\n";
			echo "<option value='p'>Aprovação</option>\n";
			echo "<option value='c'>Cancelado</option>\n";
			echo "</td>";
*/
		echo "</tr>";
		echo "<tr>";
		echo "<td colspan='2' align='center'> <INPUT TYPE='submit' name='btn_acao' value='Pesquisar'>";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' align = 'center' width='100%'></td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "</table>";
	echo "</FORM>";
	echo "<br>";


if(strlen($_POST['btn_acao']) > 0 ){
	$mes        = trim (strtoupper ($_POST['mes']));
	if(strlen($mes) ==0)	$mes = trim ($_GET['mes']);
	$ano        = trim (strtoupper ($_POST['ano']));
	if(strlen($ano) ==0)	$ano = trim ($_GET['ano']);
	$hd_chamado = trim (($_POST['hd_chamado']));

	if (strlen($mes) > 0) {
		$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
		$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
	}

	$status = trim ($_POST['status']);
	$todos = trim ($_POST['todos']);
	
	if(strlen($mes) > 0 and strlen($ano) > 0){
		if(strlen($todos) > 0){
			$cond_tempo=" AND tbl_hd_chamado.data between '$data_inicial' AND '$data_final' ";
		}else{
			$cond_tempo=" AND tbl_hd_chamado.resolvido between '$data_inicial' AND '$data_final' ";
		}
	}

	if($status=='Resolvido'){
		$cond_admin= "AND tbl_hd_chamado.admin = $login_admin
					AND status<>'Cancelado'";
	}
	// HD 19304
	if(strlen($hd_chamado)>0){
		$cond_chamado=" AND tbl_hd_chamado.hd_chamado='$hd_chamado'";
	}
		$sql_acao = "SELECT  tbl_admin.nome_completo      AS admin_nome   ,
						tbl_hd_chamado.hd_chamado          ,
						tbl_hd_chamado.admin               ,
						to_char (tbl_hd_chamado.data,'DD/MM/YY HH24:MI') AS data,
						tbl_hd_chamado.titulo              ,
						tbl_hd_chamado.status              ,
						tbl_hd_chamado.atendente           ,
						TO_CHAR(tbl_hd_chamado.resolvido,'dd/mm/YYYY') AS resolvido    ,
						tbl_hd_chamado.exigir_resposta     ,
						tbl_hd_chamado.hora_desenvolvimento,
						to_char(tbl_hd_chamado.previsao_termino,'DD/MM/YYYY HH24:MI')as  previsao_termino,
						at.nome_completo AS atendente_nome ,
						CASE 
						WHEN (tbl_hd_chamado.status = 'Resolvido'                  
							AND tbl_hd_chamado.resolvido is null)                 THEN 1
						WHEN tbl_hd_chamado.status = 'Aprovação'                  THEN 2
						WHEN tbl_hd_chamado.status = 'Cancelado'                  THEN 10 
						WHEN tbl_hd_chamado.resolvido is not null 
							AND tbl_hd_chamado.status = 'Resolvido'           THEN 9
						ELSE 5 
						END AS classificacao ,
						(SELECT admin FROM tbl_hd_chamado_item WHERE hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data DESC LIMIT 1) AS admin_item ,
						(SELECT tbl_admin.nome_completo FROM tbl_hd_chamado_item JOIN tbl_admin USING (admin) WHERE hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data DESC LIMIT 1) AS nome_item ,
						(SELECT data  FROM tbl_hd_chamado_item WHERE hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data DESC LIMIT 1) AS data_item
				FROM       tbl_hd_chamado
				JOIN       tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin
				LEFT JOIN  tbl_admin at ON (tbl_hd_chamado.atendente = at.admin )
				WHERE      tbl_admin.fabrica=$login_fabrica 
				$cond_tempo
				$cond_admin
				$cond_chamado
				AND tbl_hd_chamado.fabrica_responsavel = 10 
				ORDER BY classificacao, tbl_hd_chamado.data ";
				
				$res_acao = pg_exec ($con,$sql_acao);
				$qtde_res=pg_numrows($res_acao);
}
}else{
		$cond_status = " 1=1 ";
		$cond_admin  = " 1=1 ";

		if(strlen($status) >0 ){
			if($exigir_resposta == "f" and ($status =="Análise" OR $status =="Execução" OR $status =="Novo")) {
				$cond_status = " tbl_hd_chamado.exigir_resposta = 'f' and (tbl_hd_chamado.status = 'Análise' OR tbl_hd_chamado.status = 'Execução' OR tbl_hd_chamado.status = 'Novo' ) ";
			}elseif($exigir_resposta == "t" AND $status<>'Cancelado' AND $status<>'Resolvido' OR ($status == "Resolvido" AND strlen($resolvido)==0 )){
				// HD 19210 
				$cond_status = " (exigir_resposta is true AND status<>'Cancelado' AND status<>'Resolvido' /*OR (status = 'Resolvido' AND resolvido is null)*/) AND
				tbl_hd_chamado.admin=$login_admin";
			}elseif (($status == "Resolvido" AND strlen($resolvido)>0) OR $status == "Cancelado") {
				$cond_status = "(tbl_hd_chamado.admin = $login_admin AND ((tbl_hd_chamado.status = 'Resolvido' AND tbl_hd_chamado.resolvido is not null) OR tbl_hd_chamado.status = 'Cancelado'))";
			}elseif ($status == "Aprovação") {
				$cond_status = " tbl_hd_chamado.status = 'Aprovação'";
			}else{
				echo "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle'> ";
			}
		}

		if($admin == 'admin' ){//pega o mês atual
			$sql_data = "SELECT to_char((to_char((current_date)::date,'YYYY') || '-' || to_char(current_date,'MM') || '-01')::date , 'YYYY-MM-DD') AS data_final;";
			$res_data = pg_exec($con,$sql_data);
			$data_inicial = pg_result($res_data,0,0);

			$sql_data = "SELECT to_char(((to_char(('$data_inicial')::date,'YYYY')) || '-' || to_char(current_date,'MM') || '-01')::date + interval '1 MONTH','YYYY-MM-DD') AS data";
			$res_data = pg_exec($con,$sql_data);
			$data_final = pg_result($res_data,0,0);

			$data_inicial = "$data_inicial 00:00:00";
			$data_final = "$data_final 00:00:00";
		}

			if ($admin == 'admin'){
				$cond_admin = " tbl_hd_chamado.admin = $login_admin 
								AND (tbl_hd_chamado.resolvido between '$data_inicial' AND '$data_final' 
									OR resolvido IS NULL)
								AND status<>'Cancelado' ";
			}
		$sql_acao = "SELECT  tbl_admin.nome_completo      AS admin_nome   ,
						tbl_hd_chamado.hd_chamado          ,
						tbl_hd_chamado.admin               ,
						to_char (tbl_hd_chamado.data,'DD/MM/YY HH24:MI') AS data,
						tbl_hd_chamado.titulo              ,
						tbl_hd_chamado.status              ,
						tbl_hd_chamado.atendente           ,
						TO_CHAR(tbl_hd_chamado.resolvido,'dd/mm/YYYY') AS resolvido    ,
						tbl_hd_chamado.exigir_resposta     ,
						tbl_hd_chamado.hora_desenvolvimento,
						to_char(tbl_hd_chamado.previsao_termino,'DD/MM/YYYY HH24:MI')as  previsao_termino,
						at.nome_completo AS atendente_nome ,
						CASE 
						WHEN (tbl_hd_chamado.status = 'Resolvido'                  
							AND tbl_hd_chamado.resolvido is null)                 THEN 1
						WHEN tbl_hd_chamado.status = 'Aprovação'                  THEN 2
						WHEN tbl_hd_chamado.status = 'Cancelado'                  THEN 10 
						WHEN tbl_hd_chamado.resolvido is not null 
							AND tbl_hd_chamado.status = 'Resolvido'           THEN 9
						ELSE 5 
						END AS classificacao ,
						(SELECT admin FROM tbl_hd_chamado_item WHERE hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data DESC LIMIT 1) AS admin_item ,
						(SELECT tbl_admin.nome_completo FROM tbl_hd_chamado_item JOIN tbl_admin USING (admin) WHERE hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data DESC LIMIT 1) AS nome_item ,
						(SELECT data  FROM tbl_hd_chamado_item WHERE hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data DESC LIMIT 1) AS data_item
				FROM       tbl_hd_chamado
				JOIN       tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin
				LEFT JOIN  tbl_admin at ON (tbl_hd_chamado.atendente = at.admin )
				WHERE      tbl_admin.fabrica=$login_fabrica 
				AND $cond_admin 
				AND tbl_hd_chamado.fabrica_responsavel = 10 
				AND $cond_status
				ORDER BY classificacao, tbl_hd_chamado.data ";
	$res_acao = pg_exec ($con,$sql_acao);
	$qtde_res=pg_numrows($res_acao);
	}

	if ($qtde_res>0) {
		
	/*--===============================TABELA DE CHAMADOS========================--*/
		echo "<table width = '630' align = 'center' cellpadding='0' cellspacing='0'>";
		echo "<tr>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif' colspan='8' align = 'center' width='100%' style='font-family: arial ; color:#666666'><b>";
		if($sistema_lingua == "ES") echo "Lista de llamados";
		else                        echo "Lista de Chamados";
		echo "</b></td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</tr>";
		echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
		echo "<td ><strong>Nº </strong></td>";
		echo "<td nowrap><strong>Título</strong><img src='/assist/imagens/pixel.gif' width='5'></td>";
		echo "<td ><strong>Status</strong></td>";
		echo "<td ><strong>";
		if($sistema_lingua == "ES") echo "Fecha";
		else                        echo "Data";
		echo "</strong></td>";
		echo "<td ><strong>";
		if($sistema_lingua == "ES") echo "Solicitante";
		else                        echo "Solicitante";
		echo "</strong></td>";
		echo "<td nowrap><strong>";
		if($sistema_lingua == "ES") echo "Hora ";
		else                        echo "Hora ";
		echo "</strong></td>";
		echo "<td nowrap><strong>";
		if($sistema_lingua == "ES") echo "Prev.Término";
		else                        echo "Prev.Término";
		echo "</strong></td>";
		echo "<td ><strong>";
		if($sistema_lingua == "ES") echo "&nbsp;Resolvido";
		else                        echo "&nbsp;Resolvido";
		echo "</strong></td>";

	//	echo "<td nowrap><img src='/assist/imagens/pixel.gif' width='5'><strong>Último Autor </strong></td>";
		echo "</tr>";

		for ($i = 0 ; $i < pg_numrows ($res_acao) ; $i++) {
			$hd_chamado           = pg_result($res_acao,$i,hd_chamado);
			$admin                = pg_result($res_acao,$i,admin);
			$admin_nome           = pg_result($res_acao,$i,admin_nome);
			$data                 = pg_result($res_acao,$i,data);
			$titulo               = pg_result($res_acao,$i,titulo);
			$resolvido            = pg_result($res_acao,$i,resolvido);
			$status               = pg_result($res_acao,$i,status);
			$atendente            = pg_result($res_acao,$i,atendente);
			$atendente_nome       = pg_result($res_acao,$i,atendente_nome);
			$admin_item           = pg_result($res_acao,$i,admin_item);
			$nome_item            = pg_result($res_acao,$i,nome_item);
			$exigir_resposta      = pg_result($res_acao,$i,exigir_resposta);
			$hora_desenvolvimento = pg_result($res_acao,$i,hora_desenvolvimento);
			$previsao_termino     = pg_result($res_acao,$i,previsao_termino);



			$cor='#F2F7FF';
			if ($i % 2 == 0) $cor = '#FFFFFF';

			echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
			echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "<td nowrap>";
			if($status =="Análise" AND $exigir_resposta <> "t") {
				echo "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle'> ";
			}elseif($exigir_resposta == "t" AND $status<>'Cancelado' AND $status<>'Resolvido' OR ($status == "Resolvido" AND strlen($resolvido)==0 )) {
				echo "<img src='/assist/admin/imagens_admin/status_vermelho.gif' align='absmiddle'> ";
			}elseif (($status == "Resolvido" AND strlen($resolvido)>0) OR $status == "Cancelado") {
					echo "<img src='/assist/admin/imagens_admin/status_verde.gif' align='absmiddle'> ";
				}elseif ($status == "Aprovação") {
					echo "<img src='/assist/admin/imagens_admin/status_amarelo.gif' align='absmiddle'> ";
				}else{
					echo "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle'> ";
				}

		//echo "status: $status - res: $resolvido - exig_resp: $exigir_resposta-";


			echo $hd_chamado;
			echo "</td>";
			echo "<td nowrap><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>$titulo</a></td>";
			echo "<td>$status</td>";
			echo "<td nowrap>$data&nbsp;</td>";
			echo "<td nowrap>$admin_nome</td>";
			if(strlen($hora_desenvolvimento)>0){
				echo "<td nowrap align='center'>$hora_desenvolvimento</td>";
			}else{
				echo "<td nowrap>&nbsp;</td>";
			}
			echo "<td nowrap>$previsao_termino </td>";
			echo "<td nowrap>$resolvido</td>";
			echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "</a></tr>"; 
		}

		echo "<tr>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='8' align = 'center' width='100%'></td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</tr>";
		echo "</table>"; //fim da tabela de chamadas
	}else{
		if($sistema_lingua == "ES") echo "<center><h3>NINGÚN LLAMADO</h3></center>";
		else                        echo "<center><h3>NENHUM CHAMADO</h3></center>";
	}

?>

</td>
</tr>
</table>
<? include "rodape.php" ?>