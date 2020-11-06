<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include '../../../funcoes.php';

/* Aprova o Chamado */
if (empty($_GET)=='true' AND empty($_POST)=='true'){
	$_GET["status"] = 'Análises';
	$_GET["exigir_resposta"] = 't';
}

if (isset($_GET['aprovaRequisitos']) && $_GET['aprovaRequisitos'] == 'sim') {
	$hd_chamado = $_GET['hd_chamado_aprovacao'];
	$data = date('d-m-Y');
	$sql_nome_admin = "SELECT nome_completo FROM tbl_admin WHERE admin = $login_admin";
	$nome_completo  = pg_fetch_result(pg_query($con, $sql_nome_admin), 0, 'nome_completo');

	/* Muda o status do chamado para  */
	$sql = "UPDATE tbl_hd_chamado SET status = 'Análise', atendente = 2565  WHERE hd_chamado = $hd_chamado";
	$res = pg_query($con, $sql);


	/* Aprova os Requisitos */
	$sql = "UPDATE tbl_hd_chamado_requisito
			   SET (admin_requisito_aprova,data_requisito_aprova)
					=
				   ($login_admin, CURRENT_TIMESTAMP)
			 WHERE hd_chamado = $hd_chamado
			   AND excluido IS FALSE";
	$res = pg_query($con, $sql);
	//SEM HD - Usuário que aprova requisitos interage ou 'responde' á  solicitaçao.
	//		   Adicionando "exigir_resposta = FALSE".
	$sql = "UPDATE tbl_hd_chamado
			   SET status = CASE WHEN tipo_chamado IN(5,6)
								 THEN 'Analise'
								 ELSE 'Orçamento'
							END,
				   exigir_resposta = FALSE
			 WHERE hd_chamado   = $hd_chamado
			   AND status       NOT IN('Concluido', 'Resolvido');
			INSERT INTO tbl_hd_chamado_item (
				hd_chamado,
				comentario,
				admin
			) VALUES (
				$hd_chamado,
				'MENSAGEM AUTOMÀTICA - REQUISITOS APROVADOS EM $data PELO USUARIO $nome_completo',
				$login_admin
		)";

	$res = pg_query($con, $sql);

	/* $sql = "SELECT titulo FROM tbl_hd_chamado WHERE hd_chamado = $hd_chamado";
	$res = pg_query($con,$sql);
	$titulo = pg_result($res,0,'titulo');
	$assunto = $hd_chamado.": ".$titulo." - Requisitos aprovados";
	$destinatario = "suporte.fabricantes@telecontrol.com.br";
	$mensagem = "Foi aprovado os requisitos do chamado $hd_chamado";

	// To send HTML mail, the Content-type header must be set
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

	// Additional headers
	$headers .= "To: $destinatario" . "\r\n";
	$headers .= 'From: helpdesk@telecontrol.com.br' . "\r\n";

	mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers); */

	if($res){
		echo "sucesso";
	}

	exit;
}



if(isset($_GET['aprovaOrcamento']) && $_GET['aprovaOrcamento'] == 'sim'){
	$hd_chamado = $_GET['hd_chamado_aprovacao'];
	/* Muda o status do chamado para  */
	$sql = "UPDATE tbl_hd_chamado SET status = 'Orçamento', atendente = 2565 WHERE hd_chamado = $hd_chamado";
	$res = pg_query($con, $sql);

	if($res){
		echo "sucesso";
	}

	exit;
}

if($sistema_lingua == "ES") $TITULO = "Lista de llamados - Telecontrol Help-Desk";
else                        $TITULO = "Lista de Chamadas - Telecontrol Hekp-Desk";

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

if($_POST['hd_chamado']) $hd_chamado = trim ($_POST['hd_chamado']);
if($_GET ['hd_chamado']) $hd_chamado = trim ($_GET ['hd_chamado']);

if($_POST['titulo']) $titulo = trim ($_POST['titulo']);
if($_GET ['titulo']) $titulo = trim ($_GET ['titulo']);

if (preg_match("/[0-9]{6,7}/", $titulo)) {
	header("Location: chamado_detalhe.php?hd_chamado=$titulo");
	die;
}

$status         = $_GET["status"];
$resolvido      = $_GET["resolvido"];
$exigir_resposta= $_GET["exigir_resposta"];
$admin          = $_GET["admin"];
$todos          = $_GET["todos"];
$filtro_lista   = $_GET["filtro"];

if (isset($_GET['status']) || (isset($_GET['admin'])) || (isset($_GET['todos'])) || (isset($_POST['btn_acao']) && empty($_POST['hd_chamado']))) {
        $sql_data = "SELECT (current_date - interval '6 MONTH') as data_inicial";
        $res_data = pg_exec($con,$sql_data);
        $data_inicial = pg_result($res_data,0,0);
        $data_final = date('Y-m-d');
        $data_inicial = "$data_inicial";
        $data_final = "$data_final";
        $cond_data .= " AND tbl_hd_chamado.data BETWEEN '$data_inicial' AND '$data_final 23:59:59'";

}

if(!empty($exigir_resposta)) {
	$cond_data = null;
}
?>

<script type='text/javascript' src='../admin/js/jquery-1.3.2.js'></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<script type="text/javascript" src="../js/jquery.maskedinput2.js"></script>



<?php

if(strlen($_POST['filtro']) >0 )
	$filtro_lista    = $_POST["filtro"];

	include "menu.php";

?>

</script>

<style type="text/css">
    @import "../plugins/jquery/datepick/telecontrol.datepick.css";
</style>
<? // include "javascript_pesquisas.php"; ?>


<script language='javascript'>

	$(function() {
		$('#data_pesquisa_inicial').datepick({startDate:'01/01/2000'});
		$('#data_pesquisa_final').datepick({startDate:'01/01/2000'});
		$('#data_pesquisa_inicial').mask('99/99/9999');
		$('#data_pesquisa_final').mask('99/99/9999');

	});

	/* Aprova Requisitos */
	function aprovaRequisitos(hd_chamado){

		var link = window.location.href;

		$.ajax({
			url: link,
			type: 'GET',
			data: 'aprovaRequisitos=sim&hd_chamado_aprovacao='+hd_chamado,
			beforeSend: function(){

			},
			success: function(data){
				if(data){
					alert('Chamado '+hd_chamado+' aprovado com Sucesso!');
					window.location.href = 'chamado_detalhe.php?hd_chamado='+hd_chamado;
				}else{
					alert('erro');
				}
			}
		});

	}

	/* Aprova Orçamento */
	function aprovaOrcamento(hd_chamado){

		var link = window.location.href;

		$.ajax({
			url: link,
			type: 'GET',
			data: 'aprovaOrcamento=sim&hd_chamado_aprovacao='+hd_chamado,
			beforeSend: function(){

			},
			success: function(data){
				if(data){
					window.location.href = 'aprova_faturada.php?hd_chamado='+hd_chamado;
				}else{
					alert('erro');
				}
			}
		});

	}


	function soNums(e,args){
		if (document.all){var evt=event.keyCode;} // caso seja IE
		else{var evt = e.charCode;}	// do contrário deve ser Mozilla
		var valid_chars = '0123456789'+args;	// criando a lista de teclas permitidas
		var chr= String.fromCharCode(evt);	// pegando a tecla digitada
		if (valid_chars.indexOf(chr)>-1 ){return true;}	// se a tecla estiver na lista de permissão permite-a
		// para permitir teclas como <BACKSPACE> adicionamos uma permissão para
		// códigos de tecla menores que 09 por exemplo (geralmente uso menores que 20)
		if (valid_chars.indexOf(chr)>-1 || evt < 9){return true;}	// se a tecla estiver na lista de permissão permite-a
		return false;	// do contrário nega
	}

</script>
<table width="700" align="center"><tr><td style='font-family: arial ; color: #666666; font-size:10px' align="justify">
<?
echo "<tr style='font-family: arial ; color: #666666' align='center'>";
	echo "<td nowrap align='left'>";
		echo "<img src='../admin/imagens_admin/status_amarelo.gif' valign='absmiddle'> ";
		if($sistema_lingua == 'ES') echo "&nbsp;Aguardando aprobación&nbsp;";
		else                        echo "&nbsp;<a href='chamado_lista.php?status=Aprovação'>Aguarda Aprovação</a>&nbsp;";
	echo "</td>";
	echo "<td width='50%' nowrap align='left'>";
		echo "<img src='../admin/imagens_admin/status_cinza.gif' valign='absmiddle'> ";
		if($sistema_lingua == 'ES') echo "&nbsp;Meus Llamados&nbsp;";
		else                        echo "&nbsp;<a href='chamado_lista.php?admin=admin'>Meus Chamados</a>&nbsp;";
	echo "</td>";
	echo "<td nowrap align='left'>";
		echo "<img src='../admin/imagens_admin/status_azul.gif' align='absmiddle'> ";
		if($sistema_lingua == 'ES') echo "&nbsp;Pendiente&nbsp;";
		else                        echo "&nbsp;<a href='chamado_lista.php?status=Análise&exigir_resposta=f'>Pendentes Telecontrol</a>&nbsp;";
	echo "</td>";
	echo "<td nowrap align='left'>";
		echo "<img src='../admin/imagens_admin/status_vermelho.gif' valign='absmiddle'> ";
		if($sistema_lingua == 'ES') echo "&nbsp;Aguardando su respuesta&nbsp;";
		else                        echo "&nbsp;<a href='chamado_lista.php?status=Análise&exigir_resposta=t'>Aguarda sua resposta</a>&nbsp;";
	echo "</td>";
	## COMENTADO NO hd_chamado=2728371 ##
	// echo "<td nowrap align='left'>";
	// 	echo "<img src='../admin/imagens_admin/status_laranja.gif' align='absmiddle'>";
	// 	if($sistema_lingua == 'ES') echo "&nbsp;Concluido&nbsp;";
	// 	else 						echo "&nbsp;<a href='chamado_lista.php?status=Concluido&filtro=1'> Meus Concluídos</a>&nbsp;";
	// echo "</td>";
	echo "<td nowrap align='left'>";
		echo "<img src='../admin/imagens_admin/status_verde.gif' valign='absmiddle'> ";
		if($sistema_lingua == 'ES') echo "&nbsp;Resuelto&nbsp;";
		else                        echo "&nbsp;<a href='chamado_lista.php?status=Resolvido&filtro=1'>Meus Resolvidos</a>&nbsp;";
	echo "</td>";
	echo "<td nowrap align='left'>";
		echo "<img src='../admin/imagens_admin/status_azul_bb.gif' valign='absmiddle'> ";
		if($sistema_lingua == 'ES') echo "&nbsp;Todos los Llamados&nbsp;";
		else                        echo "&nbsp;<a href='chamado_lista.php?todos=todos&filtro=1'>Todos Chamados</a>&nbsp;";
	echo "</td>";
	echo "<td nowrap align='left'>";
		echo "<img src='../admin/imagens_admin/status_rosa.gif' valign='absmiddle'> ";
		if($sistema_lingua == 'ES') echo "&nbsp;Informe Mensual&nbsp;";
		else 						echo "&nbsp;<a href='relatorio_horas_cobradas.php'>Relatório Mensal</a>&nbsp;";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "<br>";


/* COMENTADO NO HD hd_chamado=2728371
$sql="SELECT *
		FROM	tbl_change_log
		LEFT jOIN tbl_fabrica ON tbl_fabrica.fabrica=tbl_change_log.fabrica
		LEFT join tbl_change_log_admin On tbl_change_log.change_log=tbl_change_log_admin.change_log AND tbl_change_log_admin.admin = $login_admin
		WHERE tbl_change_log_admin.data IS NULL
		AND  ( tbl_change_log.fabrica  = $login_fabrica OR tbl_change_log.fabrica IS NULL)
		AND   length(tbl_change_log.change_log_fabrica) >0 ";

$res = pg_exec ($con,$sql);
if(pg_numrows($res) >0) {
	echo "<br>";
	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>
	<tr valign='middle' align='center' class='change_log'><td><a href='change_log_mostra.php' target='_blank'>Existem CHANGE LOG para ser lido. Clique aqui para visualizar</a></td></tr>
	</table><br>";

}
*/

// >>>>>> PESQUISA
if(strlen($_POST['btn_acao']) > 0 ){

	if($_POST['titulo']) $titulo = ($_POST['titulo']);
	if($_POST['hd_status']) $hd_status = ($_POST['hd_status']);
	if($_POST['hd_chamado'])  $hd_chamado = trim ($_POST['hd_chamado']);
	if($_POST['data_pesquisa_inicial'])  $aux_data_inicial = ($_POST['data_pesquisa_inicial']);
	if($_POST['data_pesquisa_final'])  $aux_data_final = ($_POST['data_pesquisa_final']);
	if($_POST['http']) $http = ($_POST['http']);

	if( strlen($aux_data_inicial) > 0 && strlen($aux_data_final)>0){
		if( strlen($aux_data_inicial) > 0 && strlen($aux_data_final)>0){
			list($d,$m,$y)=explode("/", $aux_data_inicial);
			$aux_data_inicial = "$y-$m-$d" ;
			list($d,$m,$y)=explode("/", $aux_data_final);
			$aux_data_final = "$y-$m-$d" ;

	    	if(strtotime($aux_data_inicial) > strtotime($aux_data_final)){
	        		$msg_erro .= traduz("Data inicial não pode ser maior que a final.");
	        	}
			if (strtotime($aux_data_final) > strtotime($aux_data_inicial . ' + 6 MONTH')) {
	            		$msg_erro .= traduz("Período não pode ser maior que 6 meses");
	        	}
			if(strlen($msg_erro)== 0){
				$cond_data = " AND tbl_hd_chamado.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'";
		    	}
		}else{
		    	$msg_erro .= "Por favor inserir as datas. ";
    		}
	}
	//var_dump($aux_data_inicial);echo "<<<>>>";var_dump($aux_data_final);
	if($status=='Concluido'){
		$cond_admin= "AND tbl_hd_chamado.admin = $login_admin
					  AND status = 'Concluido'";

	}
	if($status=='Resolvido'){
		$cond_admin= "AND tbl_hd_chamado.admin = $login_admin
					  AND status = 'Resolvido'";
	}
  	if(strlen($data_pesquisa)>0){
           if ($data_pesquisa == 'aprovacao'){
           	 if ((strlen($aux_data_inicial) > 0) AND (strlen($aux_data_final) > 0))  {
	           		$join_abertura="JOIN tbl_hd_chamado_item ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado";
                	$cond_abertura .=" AND (tbl_hd_chamado_item.comentario ilike('%ESTE CHAMADO FOI ABERTO EM%') AND (tbl_hd_chamado_item.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59') )";
               		$cond_data = "";
                }else{
	           		$join_abertura="JOIN tbl_hd_chamado_item ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado ";
	                $cond_abertura  =" AND (tbl_hd_chamado_item.comentario ilike('%ESTE CHAMADO FOI ABERTO EM%') ) ";
                }
           }
           if ($data_pesquisa == 'finalizacao'){
				if ((strlen($aux_data_inicial) > 0) AND (strlen($aux_data_final) > 0))  {
                			$cond_abertura .="  AND (tbl_hd_chamado_item.termino BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59')
            									AND tbl_hd_chamado_item.hd_chamado_item = (SELECT hd_chamado_item
            																				FROM tbl_hd_chamado_item
            																				WHERE status_item = 'Resolvido'
            																				AND hd_chamado = tbl_hd_chamado.hd_chamado
            																				ORDER BY data DESC LIMIT 1)";
                			$join_abertura="JOIN tbl_hd_chamado_item ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado ";
					$cond_data ="";
                		}else{
					$join_abertura="JOIN tbl_hd_chamado_item ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado ";//
					$cond_abertura=" AND tbl_hd_chamado_item.status_item = 'Resolvido' ";
           			}
		}
   	}
	if(strlen($hd_chamado)>0){
		$cond_chamado=" AND tbl_hd_chamado.hd_chamado='$hd_chamado' ";
	}
	if(strlen($hd_status)>0){
		$cond_tipo=" AND tbl_tipo_chamado.tipo_chamado='$hd_status'";
		$cond_join=" JOIN 	   tbl_tipo_chamado ON tbl_hd_chamado.tipo_chamado = tbl_tipo_chamado.tipo_chamado ";
	}
	if(strlen($titulo)>0){
		$cond_titulo=" AND tbl_hd_chamado.titulo ilike '%$titulo%' ";
	}
	if(strlen($http)>0){
		$qnt_array =  count(explode("/", $http));
		$link = explode("/", $http);
		$ultumaparte = $link[$qnt_array - 1];
		$penultimo = $link[$qnt_array - 2];
		$len = strlen(strstr($ultumaparte, '?'));
		if ($len > 0 ) {
			$ultumaparte = substr($ultumaparte, 0, strlen($ultumaparte) - $len);
			}
		$join_http =" JOIN tbl_hd_chamado_questionario ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_questionario.hd_chamado " ;
		$cond_http =" AND tbl_hd_chamado_questionario.http ilike '%$penultimo/$ultumaparte%' ";
	}
	if(strlen($msg_erro)== 0){


		$sql_acao = "SELECT tbl_admin.nome_completo      AS admin_nome   ,
							tbl_hd_chamado.hd_chamado          ,
							tbl_hd_chamado.admin              ,
							to_char (tbl_hd_chamado.data,'DD/MM/YY HH24:MI') AS data,
							tbl_hd_chamado.titulo              ,
							tbl_hd_chamado.status              ,
							tbl_hd_chamado.atendente           ,
							TO_CHAR(tbl_hd_chamado.resolvido   ,'dd/mm/YYYY') AS resolvido    ,
							tbl_hd_chamado.exigir_resposta     ,
							tbl_hd_chamado.hora_desenvolvimento,
							to_char(tbl_hd_chamado.previsao_termino,'DD/MM/YYYY HH24:MI')as  previsao_termino,
							at.nome_completo AS atendente_nome ,
							CASE
							WHEN (tbl_hd_chamado.status = 'Concluido'
								AND tbl_hd_chamado.resolvido is null)                 THEN 1
							WHEN tbl_hd_chamado.status = 'Aprovação'                  THEN 2
							WHEN tbl_hd_chamado.status = 'Cancelado'                  THEN 10
							WHEN tbl_hd_chamado.resolvido is not null
								AND tbl_hd_chamado.status = 'Concluido'           THEN 9
							ELSE 5
							END AS classificacao ,
							(SELECT admin FROM tbl_hd_chamado_item WHERE hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data DESC LIMIT 1) AS admin_item ,
							(SELECT tbl_admin.nome_completo FROM tbl_hd_chamado_item JOIN tbl_admin USING (admin) WHERE hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data DESC LIMIT 1) AS nome_item ,
							(SELECT data  FROM tbl_hd_chamado_item WHERE hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data DESC LIMIT 1) AS data_item
					FROM       tbl_hd_chamado
					$cond_join
					$join_http
					$join_abertura
					JOIN       tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin AND tbl_admin.fabrica=$login_fabrica
					LEFT JOIN  tbl_admin at ON (tbl_hd_chamado.atendente = at.admin  AND at.fabrica=$login_fabrica)
					WHERE      tbl_hd_chamado.fabrica=$login_fabrica
					$cond_tempo
					$cond_data
					$cond_http
					$cond_admin
					$cond_chamado
					$cond_abertura
					$cond_tipo
					$cond_titulo
					AND tbl_hd_chamado.fabrica_responsavel = 10
					ORDER BY classificacao, tbl_hd_chamado.data ";
				//echo nl2br($sql_acao);
				//exit;
				$res_acao = pg_query($con,$sql_acao);
				$qtde_res = pg_num_rows($res_acao);
	}else{
		echo "
			<br>
			<table width='100%' cellpadding='0' cellspacing='0' border='0'  >
				<tr valign='middle' align='center' class='msg_erro' >
					<td bgcolor='#FF0000' style='color:#fff; font-weight:bold; font-size:20'>
						$msg_erro
					</td>
				</tr>
			</table>
			<br>
		";
	}
}

if(strlen($status) >0 ){
	# COMENTADO NO HD hd_chamado=2728371
	#if($exigir_resposta == "f" and ($status =="Análise" OR $status =="Execução" OR $status =="Novo")) {
	if($exigir_resposta == "f" and ($status =="Análise" OR $status =="Execução" OR $status == "Validação"
		OR $status == "Aguard.Execução" OR $status == "EfetivaçãoHomologação" OR $status == "ValidaçãoHomologação"
		OR $status == "Efetivação" OR $status == "Correção" OR $status == "Parado"
	)) {
		#	COMENTADO NO HD hd_chamado=2728371
		#  $cond_status = "AND tbl_hd_chamado.exigir_resposta IS NOT TRUE and (tbl_hd_chamado.status = 'Análise' OR tbl_hd_chamado.status = 'Execucao' OR tbl_hd_chamado.status = 'Novo' ) ";
		$cond_status = "AND tbl_hd_chamado.exigir_resposta IS NOT TRUE and (tbl_hd_chamado.status = 'Análise' OR tbl_hd_chamado.status = 'Execução'
			OR tbl_hd_chamado.status = 'Validação' OR tbl_hd_chamado.status = 'Aguard.Execução' OR tbl_hd_chamado.status = 'EfetivaçãoHomologação'
			OR tbl_hd_chamado.status = 'ValidaçãoHomologação' OR tbl_hd_chamado.status = 'Efetivação' OR tbl_hd_chamado.status = 'Correção'
			OR tbl_hd_chamado.status = 'Parado'
		)";
	}elseif($exigir_resposta == 't' AND $status == "Análises"){
		$cond_status = "AND (exigir_resposta is true AND status<>'Cancelado' AND status<>'Resolvido' OR (status = 'Resolvido' AND resolvido is null) OR (tbl_hd_chamado.status = 'Aguard.Admin' AND exigir_resposta is true) OR( status = 'Requisitos' OR status = 'Orçamento' OR status = 'Novo')) AND
		tbl_hd_chamado.admin=$login_admin";
	}
	elseif($exigir_resposta == "t" AND $status<>'Cancelado' AND $status<>'Concluido' OR ($status == "Concluido" AND strlen($resolvido)==0 ) OR ($status == "Aguard.Admin")){
		// HD 19210
		$cond_status = "AND (exigir_resposta is true AND status<>'Cancelado' AND status<>'Resolvido' OR (status = 'Resolvido' AND resolvido is null) OR (tbl_hd_chamado.status = 'Aguard.Admin' AND exigir_resposta is true)) AND
		tbl_hd_chamado.admin=$login_admin";
	}elseif (($status == "Concluido" AND strlen($resolvido)>0) OR $status == "Cancelado") {
		$cond_status = "AND (tbl_hd_chamado.admin = $login_admin AND ((tbl_hd_chamado.status = 'Concluido' AND tbl_hd_chamado.resolvido is not null) OR tbl_hd_chamado.status = 'Cancelado'))";
	}elseif ($status == "Aprovação") {
		$cond_status = "AND (tbl_hd_chamado.status ~'Aprova' OR tbl_hd_chamado.status = 'Novo' OR tbl_hd_chamado.status = 'Requisitos' OR tbl_hd_chamado.status = 'Orçamento')";
	}elseif($status=="Suspenso"){
		$cond_status = "AND tbl_hd_chamado.status = 'Suspenso' ";
	}
}

if(strlen($titulo)>0){
	$cond_titulo=" AND tbl_hd_chamado.titulo ~* \$T$$titulo\$T$"; // Pesquisa por parte do Titulo
	if (preg_match("/[0-9]{6,7}/", $titulo)) {
		$cond_titulo = "AND tbl_hd_chamado.hd_chamado = $titulo";
	}
}

if ($_GET['admin'] == 'admin'){
	$cond_admin = " AND tbl_hd_chamado.admin = $login_admin
				AND (tbl_hd_chamado.resolvido between '$data_inicial' AND '$data_final'
					OR resolvido IS NULL)
				AND status<>'Cancelado' ";
}
if($status=='Concluido'){
		$cond_admin= "AND tbl_hd_chamado.admin = $login_admin
					AND status = 'Concluido'";
		$cond_status = " ";
}
if($status=='Resolvido'){
		$cond_admin= "AND tbl_hd_chamado.admin = $login_admin
					AND status = 'Resolvido'";
		$cond_status = " ";
}
// >>>>>>>>>>>>>>> PESQUISA VIA STATUS


	if (isset($_GET['status']) || (isset($_GET['admin'])) || (isset($_GET['todos']))){
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
							WHEN (tbl_hd_chamado.status = 'Concluido'
								AND tbl_hd_chamado.resolvido is null)                 THEN 1
							WHEN tbl_hd_chamado.status ~ 'Aprova'                  THEN 2
							WHEN tbl_hd_chamado.status = 'Cancelado'                  THEN 10
							WHEN tbl_hd_chamado.resolvido is not null
								AND tbl_hd_chamado.status = 'Concluido'           THEN 9
							ELSE 5
							END AS classificacao ,
							(SELECT admin FROM tbl_hd_chamado_item WHERE hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data DESC LIMIT 1) AS admin_item ,
							(SELECT tbl_admin.nome_completo FROM tbl_hd_chamado_item JOIN tbl_admin USING (admin) WHERE hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data DESC LIMIT 1) AS nome_item ,
							(SELECT data  FROM tbl_hd_chamado_item WHERE hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data DESC LIMIT 1) AS data_item
					FROM       tbl_hd_chamado
					JOIN       tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin
					LEFT JOIN  tbl_admin at ON (tbl_hd_chamado.atendente = at.admin )
					WHERE      tbl_admin.fabrica=$login_fabrica
					$cond_data
					$cond_admin
					AND tbl_hd_chamado.fabrica_responsavel = 10
					$cond_status
					$cond_titulo
					ORDER BY classificacao, tbl_hd_chamado.data ";
		$res_acao = pg_exec ($con,$sql_acao);
		$qtde_res=pg_numrows($res_acao);
		#echo nl2br($sql_acao);
	}

	echo "<form method='POST' action='$PHP_SELF'>";
	echo "<table width = '400' align = 'center' cellpadding='0' cellspacing='0' border='0' style='font-family: verdana ; font-size:11px ; color: #666666'>";
	echo "<input type='hidden' name='status' value='$status'>";
	echo "<input type='hidden' name='todos' value='$todos'>";
	echo "<input type='hidden' name='filtro' value='$filtro_lista'>";
	echo "<tr>";
		echo "<td background='../helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='../imagens/pixel.gif' width='9'></td>";
		echo "<td background='../helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   align = 'center' width='100%' style='font-family: arial ; color:#666666'>&nbsp;</td>";
		echo "<td background='../helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  rowspan='2'><img src='../imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "<tr bgcolor='#D9E8FF' >";
		if($sistema_lingua == 'ES'){
			echo "<td><b><CENTER>Buscar Llamados</CENTER></b></td>";
		}else{
			echo "<td><b><CENTER>Pesquisa Chamados</CENTER></b></td>";
		}
	echo "</tr>";
	echo "<tr align='left'  height ='70' valign='top'>";
		echo "<td background='../helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='../imagens/pixel.gif' width='9'></td>";
		echo "<td>";

		echo "<table border='0'  cellpadding='2' cellspacing='3' width='400' align='center' style='font-family: verdana ; font-size:11px ; color: #00000'>";
		echo "<tr>";
			if($sistema_lingua == 'ES'){
				echo "<td align='center' colspan='2'>Numero Llamado</td>";
			}else{
				echo "<td align='center' colspan='2'>Número do chamado</td>";
			}
		echo "</tr>";
		echo "<tr>";
		?>
			<td align='center' colspan='2'><input type='text' size='6' maxlength='8' name='hd_chamado' id='hd_chamado' value='<? echo $hd_chamado ?>' onKeyPress ="return (soNums(event,''));"></td>
		<?
		echo "</tr>";
		echo "<tr>";
			if($sistema_lingua == 'ES'){
				echo "<td align='center'>Fecha inicial</td>";
				echo "<td align='center'>Fecha finalización</td>";
			}else{
				echo "<td align='center'>Data inicial</td>";
				echo "<td align='center'>Data final</td>";
			}
		echo "</tr>";
		echo "<tr>";
			echo "<td width='50%' align='center'>";
				 ?>
				<input type="text" name="data_pesquisa_inicial" id="data_pesquisa_inicial" size="13" maxlength="10" value="<? echo substr($data_pesquisa_inicial,0,10);?>"/>
				<?
				echo "</td>";
			echo "<td width='50%' align='center'>";
			?>
			 	<input type="text" name="data_pesquisa_final" id="data_pesquisa_final" size="13" maxlength="10" value="<? echo substr($data_pesquisa_final,0,10);?>"/>
			<?
			echo "</td>";
		echo "</tr>";
		echo "<tr>";
			if($sistema_lingua == 'ES'){
				echo "<td align='center' colspan='2'>Tipo del llamado</td>";
			}else{
				echo "<td align='center' colspan='2'>Tipo do chamado</td>";
			}
		echo "</tr>";
		echo "<tr>
			<td align='center' colspan='2'>";

		$sql="SELECT tipo_chamado,descricao
			FROM	tbl_tipo_chamado
			ORDER BY descricao
			";
			$res = pg_query($con,$sql);
			$tipo_chamado = pg_fetch_all($res);
		?>
		<select name="hd_status" id="hd_status" >
<?		echo "<option value=''></option>\n";
			foreach($tipo_chamado as $tipo_tipo) {
				$sel = ($tipo_tipo['tipo_chamado'] == $_POST['hd_status']) ? ' SELECTED' : '';
?>				<option value="<?php echo $tipo_tipo['tipo_chamado'] ?>"<?php echo $sel ?>><?php echo traduz($tipo_tipo['descricao']) ?></option>
<?			}
?>			</select>
<?

		echo "</td>
			</tr>";
			if($sistema_lingua == 'ES'){
				echo "<td align='center' colspan='2'>Tipo de Fecha</td>";		
			}else{
				echo "<td align='center' colspan='2'>Tipo de Data</td>";
			}
		
       	echo "</tr>";
       	echo "<tr>
               <td align='center' colspan='2'>";

?>
       	<select name="data_pesquisa" id="data_pesquisa" >
           <option value="abertura"<?php if($data_pesquisa=='abertura')  echo ' SELECTED ' ?> ><?=traduz("Data de Abertura")?>   </option>
               <option value="aprovacao"<?php if($data_pesquisa=='aprovacao')  echo ' SELECTED ' ?> ><?=traduz("Data de Aprovacao")?>    </option>
               <option value="finalizacao"<?php if($data_pesquisa=='finalizacao')  echo ' SELECTED ' ?> ><?=traduz("Data de Finalizacao")?>   </option>
       	</select>
<?

       	echo "</td>
           </tr>";

		echo "<tr>";
			echo "<td align='center' colspan=2>".traduz('Título')."</td>";
		echo "</tr>";
		echo "<tr>";
			echo "<td align='center' colspan=2><input type='text' name='titulo' id='titulo' value='".$_POST['titulo']."'></td>";
		echo "</tr>";
		echo "<tr>";
			if($sistema_lingua == 'ES'){
				echo "<td align='center' colspan=2>Busca a través http:</td>";
			}else{
				echo "<td align='center' colspan=2>Pesquisa via http:</td>";
			}
		echo "</tr>";
		echo "<tr>";
			echo "<td align='center' colspan=2><input type='text' name='http' id='http' value=$http></td>";
		echo "</tr>";




		echo "</tr>";
		echo "<tr>";
		echo "<td colspan='2' align='center'> <INPUT TYPE='submit' name='btn_acao' value=".traduz('Pesquisar').">";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		echo "<td background='../helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='../imagens/pixel.gif' width='9'></td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td background='../helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='../imagens/pixel.gif' width='9'></td>";
		echo "<td background='../helpdesk/imagem/fundo_tabela_baixo_centro.gif' align = 'center' width='100%'></td>";
		echo "<td background='../helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='../imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "</table>";
	echo "</FORM>";
	echo "<br>";



	if ($qtde_res>0) {
	/*--===============================TABELA DE CHAMADOS========================--*/
		echo "<table width = '630' align = 'center' cellpadding='0' cellspacing='0'>";
		echo "<tr>";
		echo "<td background='../helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='../imagens/pixel.gif' width='9'></td>";
		echo "<td background='../helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif' colspan='8' align = 'center' width='100%' style='font-family: arial ; color:#666666'><b>";
		if($sistema_lingua == "ES") echo "Lista de llamados";
		else                        echo "Lista de Chamados";
		echo "</b></td>";
		echo "<td background='../helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif' rowspan='2'><img src='../imagens/pixel.gif' width='9'></td>";
		echo "</tr>";
		echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
		echo "<td ><strong>Nº </strong></td>";
		echo "<td nowrap><strong>".traduz('Título')."</strong><img src='../imagens/pixel.gif' width='5'></td>";
		echo "<td ><strong>".traduz('Status')."&nbsp;</strong></td>";
		echo "<td ><strong>";
		if($sistema_lingua == "ES") echo "Fecha&nbsp;";
		else                        echo "Data&nbsp;";
		echo "</strong></td>";
		echo "<td ><strong>";
		if($sistema_lingua == "ES") echo "Solicitante";
		else                        echo "Solicitante";
		echo "</strong></td>";
		echo "<td nowrap><strong>";
		if($sistema_lingua == "ES") echo "Hora&nbsp;";
		else                        echo "Hora&nbsp;";
		echo "</strong></td>";
		echo "<td nowrap><strong>";
		if($sistema_lingua == "ES") echo "Prev. del Final";
		else                        echo "Prev.Término";
		echo "</strong></td>";
		echo "<td ><strong>";
		if($sistema_lingua == "ES") echo "&nbsp;Terminado";
		else                        echo "&nbsp;Concluido";
		echo "</strong></td>";

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

			echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" >
				<a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
				echo "<td background='../helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='../imagens/pixel.gif' width='9'></td>";
				echo "<td nowrap>";

				if($status =="Análise" OR $status == "Execucao" OR $status == "Validação" OR $status == "Aguard.Execução"
					OR $status == "EfetivaçãoHomologação" OR $status == "ValidaçãoHomologação" OR $status == "Efetivação"
					OR $status == "Correção" OR $status == "Parado" AND $exigir_resposta <> "t") {
					echo "<img src='../admin/imagens_admin/status_azul.gif' align='absmiddle'> ";
				}elseif($exigir_resposta == "t" AND $status<>'Cancelado' AND ($status<>'Resolvido' AND $status<>'Concluido') OR ($status == "Concluido" AND strlen($resolvido)==0) OR ($status == "Aguard.Admin" AND $exigir_resposta == "t") OR ($status == "Resolvido" AND $resolvido == 0)) {
					echo "<img src='../admin/imagens_admin/status_vermelho.gif' align='absmiddle'> ";
				}elseif (($status == "Resolvido" AND strlen($resolvido)>0) OR $status == "Cancelado") {
					echo "<img src='../admin/imagens_admin/status_verde.gif' align='absmiddle'> ";
				}elseif ($status == "Aprovação" or $status == 'Novo') {
					echo "<img src='../admin/imagens_admin/status_amarelo.gif' align='absmiddle'> ";
				}elseif ($status == "Concluido") {
					echo "<img src='../admin/imagens_admin/status_laranja.gif' align='absmiddle'> ";
				}elseif ($status == 'Requisitos') {
                    $sql_req = "SELECT * FROM tbl_hd_chamado_requisito
                        WHERE hd_chamado = $hd_chamado
                        AND excluido IS NOT TRUE
                        AND data_requisito_aprova IS NULL";
                    $res_req = pg_query($con, $sql_req);

                    if (pg_num_rows($res_req) > 0) {
                        echo "<img src='../admin/imagens_admin/status_amarelo.gif' align='absmiddle'> ";
                    } else {
                        echo "<img src='../admin/imagens_admin/status_azul.gif' align='absmiddle'> ";
                    }
                }elseif ($status == 'Orçamento') {
                    $sql_orc = "SELECT * FROM tbl_hd_chamado
                        WHERE hd_chamado = $hd_chamado
                        AND hora_desenvolvimento IS NOT NULL
                        AND data_aprovacao IS NULL";
                    $res_orc = pg_query($con, $sql_orc);

                    if (pg_num_rows($res_orc) > 0) {
                        echo "<img src='../admin/imagens_admin/status_amarelo.gif' align='absmiddle'> ";
                    } else {
                        echo "<img src='../admin/imagens_admin/status_azul.gif' align='absmiddle'> ";
                    }
                } else {
					echo "<img src='../admin/imagens_admin/status_azul.gif' align='absmiddle'> ";
				}

				//echo "status: $status - res: $resolvido - exig_resp: $exigir_resposta-";

				echo $hd_chamado;
				echo " &nbsp; </td>";
				echo "<td nowrap><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>$titulo</a> &nbsp; </td>";
				echo "<td>&nbsp;".traduz($status)."&nbsp;</td>";
				echo "<td nowrap> &nbsp; $data &nbsp; </td>";
				echo "<td nowrap>$admin_nome</td>";
				if(strlen($hora_desenvolvimento)>0){
					echo "<td nowrap align='center'>$hora_desenvolvimento</td>";
				}else{
					echo "<td nowrap>&nbsp;</td>";
				}
				echo "<td nowrap>$previsao_termino </td>";
				echo "<td nowrap>$resolvido";
				if($status == 'Cancelado'){
					$sqlCan = "SELECT requisito FROM tbl_hd_chamado_requisito WHERE hd_chamado = $hd_chamado AND admin_requisito_aprova IS NULL AND data_requisito_aprova IS NULL";
					$resCan = pg_query($con, $sqlCan);
					if(pg_num_rows($resCan) > 0){

						echo " &nbsp; <button type='button' id='aprovaRequisitos' onclick='aprovaRequisitos($hd_chamado)'>Aprovar Requisitos</button>";

					}else{
						/* Cancelou o Chamado no Orçamento */
						if(strlen($hora_desenvolvimento) > 0){
							echo "<input type='hidden' name='hd_chamado_hidden' id='hd_chamado_hidden' value='$hd_chamado' />";
							echo " &nbsp; <button type='button' id='aprovaOrcamento' onclick='aprovaOrcamento($hd_chamado)'>Aprovar Orçamento</button>";
						}
					}
				}
				echo "</td>";
				echo "<td background='../helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='../imagens/pixel.gif' width='9'></td>";
				echo "</a>
			</tr>";
		}

		echo "<tr>";
		echo "<td background='../helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='../imagens/pixel.gif' width='9'></td>";
		echo "<td background='../helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='8' align = 'center' width='100%'></td>";
		echo "<td background='../helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='../imagens/pixel.gif' width='9'></td>";
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
<? //include "mensagem_forca_tarefa.html" ?>
<? include "rodape.php" ?>
