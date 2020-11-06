<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';
include "monitora.php";
$layout_menu = "gerencia";
$title = "Estudo de Peças Pendentes no Posto x Distribuidor x Fábrica";

include 'cabecalho.php';

if($login_admin <> 586 and $login_admin <> 432){
	header ("Location: menu_gerencia.php");
}

include "javascript_calendario_new.php"; ?>
<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>


<script language='javascript'>
	$(function(){
		$('#data_corte_garantia_posto').datePicker({startDate:'01/01/2000'});
		$('#data_corte_faturada_posto').datePicker({startDate:'01/01/2000'});
		$("#data_corte_garantia_posto").maskedinput("99/99/9999");
		$("#data_corte_faturada_posto").maskedinput("99/99/9999");

		$('#data_corte_garantia_distrib').datePicker({startDate:'01/01/2000'});
		$('#data_corte_faturada_distrib').datePicker({startDate:'01/01/2000'});
		$("#data_corte_garantia_distrib").maskedinput("99/99/9999");
		$("#data_corte_faturada_distrib").maskedinput("99/99/9999");
	});


	function carregaDados(peca,tipo,aux_data_corte_garantia_posto,aux_data_corte_faturada_posto,aux_data_corte_garantia_distrib,aux_data_corte_faturada_distrib,linha,div) {
		
		var mostra = document.getElementById(div);
		
		if (mostra.innerHTML != '') {
			mostra.innerHTML = '';
		} else {
			$.ajax({
				type:'GET',
				url: 'distrib_pendencia_detalhe_estudo.php',
				data: 'peca=' +peca+'&linha=' +linha+'&aux_data_corte_garantia_posto=' +aux_data_corte_garantia_posto+'&aux_data_corte_faturada_posto=' +aux_data_corte_faturada_posto+'&aux_data_corte_garantia_distrib=' +aux_data_corte_garantia_distrib+'&aux_data_corte_faturada_distrib=' +aux_data_corte_faturada_distrib,
				beforeSend: function(){
					$(mostra).html("&nbsp;&nbsp;Carregando...&nbsp;&nbsp;<br><img src='js/loadingAnimation.gif'>").toggle();
				},
				complete: function(resposta){
					resposta_array = resposta.responseText.split("|");
					linha = resposta_array [0];
					$('div[rel=resultado]').hide();
					$(mostra).html(resposta_array[1]).show();
				}
			});
		}

	}

</script>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 11px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Nota {
	font-family: Arial;
	font-size: 9px;
	font-weight: normal;
}

.Erro {
	text-align: center;
	font-family: Arial;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #FF0000;
}

.Conteudo {
	text-align: left;
	font-family: Arial;
	font-size: 11px;
	font-weight: normal;
}

.Conteudo2 {
	text-align: center;
	font-family: Arial;
	font-size: 11px;
	font-weight: normal;
}

label {
	cursor: pointer;
}

</style>
<style type="text/css">
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
.Mes{
	font-size: 9px;
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

.titulo_tabela{
background-color:#596d9b;
font: bold 14px "Arial";
color:#FFFFFF;
text-align:center;
}


.titulo_coluna{
background-color:#596d9b;
font: bold 11px "Arial";
color:#FFFFFF;
text-align:center;
}

.msg_erro{
background-color:#FF0000;
font: bold 16px "Arial";
color:#FFFFFF;
text-align:center;
}

.formulario{
background-color:#D9E2EF;
font:11px Arial;
}

.subtitulo{

color: #7092BE
}

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
</style>

<?
if($login_fabrica <> 51 and $login_fabrica <> 81){
	exit;
}

if(strlen($_POST[script])==0){
	$script = 'nao_gerar';
}else{
	$script = $_POST[script];
}

if (strlen($_GET['data_corte_garantia_posto']) > 0) $data_corte_garantia_posto = $_GET['data_corte_garantia_posto'];
else                                   $data_corte_garantia_posto = $_POST['data_corte_garantia_posto'];
if (strlen($_GET['data_corte_faturada_posto']) > 0) $data_corte_faturada_posto = $_GET['data_corte_faturada_posto'];
else                                   $data_corte_faturada_posto = $_POST['data_corte_faturada_posto'];
if (strlen($_GET['data_corte_garantia_distrib']) > 0) $data_corte_garantia_distrib = $_GET['data_corte_garantia_distrib'];
else                                   $data_corte_garantia_distrib = $_POST['data_corte_garantia_distrib'];
if (strlen($_GET['data_corte_faturada_distrib']) > 0) $data_corte_faturada_distrib = $_GET['data_corte_faturada_distrib'];
else                                   $data_corte_faturada_distrib = $_POST['data_corte_faturada_distrib'];


if(strlen($msg_erro)==0){
	list($di, $mi, $yi) = explode("/", $data_corte_garantia_posto);
	if(!checkdate($mi,$di,$yi)) 
		$msg_erro = "Data Inválida";
}
if(strlen($msg_erro)==0){
	$aux_data_corte_garantia_posto = $yi."-".$mi."-".$di;
}
if(strlen($msg_erro)==0){
	list($di, $mi, $yi) = explode("/", $data_corte_faturada_posto);
	if(!checkdate($mi,$di,$yi)) 
		$msg_erro = "Data Inválida";
}
if(strlen($msg_erro)==0){
	$aux_data_corte_faturada_posto = $yi."-".$mi."-".$di;
}
if(strlen($msg_erro)==0){
	list($di, $mi, $yi) = explode("/", $data_corte_garantia_distrib);
	if(!checkdate($mi,$di,$yi)) 
		$msg_erro = "Data Inválida";
}
if(strlen($msg_erro)==0){
	$aux_data_corte_garantia_distrib = $yi."-".$mi."-".$di;
}
if(strlen($msg_erro)==0){
	list($di, $mi, $yi) = explode("/", $data_corte_faturada_distrib);
	if(!checkdate($mi,$di,$yi)) 
		$msg_erro = "Data Inválida";
}
if(strlen($msg_erro)==0){
	$aux_data_corte_faturada_distrib = $yi."-".$mi."-".$di;
}

$btn_simular = $_POST['btn_simular'];

# Garantia
if($login_fabrica == 51){
	$condicao_gar = "1024";
	$tipo_pedido_gar = "132";
}else{
	$condicao_gar = "1397";
	$tipo_pedido_gar = "154";
}
$posto    = "4311";

# Faturado
if($login_fabrica == 51){
	$tabela_fat   = "215";
	$condicao_fat = "1083";
	$linha_fat    = "505";
	$tipo_pedido_fat = "131";
}else{
	$tabela_fat   = "283";
	$condicao_fat = "1396";
	$linha_fat    = "567";
	$tipo_pedido_fat = "153";
}

if($script=='gerar'){
	//$file_script = fopen ('/tmp/script_cancela_pedido.sql',"w");
	$sql_insert = "INSERT INTO tbl_pedido (
							posto        ,
							fabrica      ,
							condicao     ,
							tipo_pedido  ,
							status_pedido
					) VALUES (
							$posto      ,
							$login_fabrica    ,
							$condicao_gar   ,
							$tipo_pedido_gar,
							1
					)RETURNING pedido;";
	$res_insert = pg_query ($con,$sql_insert);
	$pedido_garantia = pg_result($res_insert,0,pedido); 

	$sql_insert = "INSERT INTO tbl_pedido (
							posto        ,
							fabrica      ,
							tabela       ,
							linha        ,
							condicao     ,
							tipo_pedido  ,
							status_pedido
					) VALUES (
							$posto        ,
							$login_fabrica,
							$tabela_fat       ,
							$linha_fat        ,
							$condicao_fat     ,
							$tipo_pedido_fat  ,
							1
					)RETURNING pedido;";
	$res_insert = pg_query ($con,$sql_insert);
	$pedido_faturado = pg_result($res_insert,0,pedido); 
}
if( $btn_simular == "EXECUTAR" and strlen($data_corte_garantia_posto) > 0 
AND strlen($data_corte_faturada_posto) > 0 
AND strlen($data_corte_garantia_distrib) > 0 
AND strlen($data_corte_faturada_distrib) > 0 ){
	$sql = "drop table if exists tmp_distrib_pendencia_estudo;

			SELECT tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao, peca_critica, troca_obrigatoria,
			/* pendencia do posto --- o ditrib precisa enviar peça para o posto  */
			0 as qtde_pendente_garantia, 0 as qtde_pendente_faturada, 
			0 as qtde_pendente_garantia_distrib, 0 as qtde_pendente_faturada_distrib,
			0 as qtde_atendido_gar_nf_posto, 0 as qtde_atendido_gar_nf_distrib,
			0 as qtde_atendido_fat_nf_posto, 0 as qtde_atendido_fat_nf_distrib,
			0 as qtde_estoque
			INTO TEMP tmp_distrib_pendencia_estudo
			FROM tbl_peca
			where tbl_peca.fabrica = $login_fabrica;

			UPDATE tmp_distrib_pendencia_estudo set qtde_pendente_garantia = x.qtde_pendente
			FROM (
				SELECT SUM (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada_distribuidor) AS qtde_pendente, tbl_pedido_item.peca
				FROM tbl_pedido
				JOIN tbl_pedido_item USING (pedido)
				WHERE tbl_pedido.distribuidor = 4311
				and   tbl_pedido.data > '$aux_data_corte_garantia_posto 23:59:59'
				/* existem pedido que estao faturado e cancelado, com embaque impresso, mas sem nf ex 1459358 */
				and   tbl_pedido_item.qtde > ( tbl_pedido_item.qtde_cancelada + tbl_pedido_item.qtde_faturada_distribuidor )
				AND   tbl_pedido.fabrica = $login_fabrica
				/* AND   tbl_pedido.status_pedido <> 13 */";
	if($login_fabrica==51){
		$sql .= " AND tbl_pedido.tipo_pedido = 132 ";
	}
	if($login_fabrica==81){
		$sql .= " AND tbl_pedido.tipo_pedido = 154 ";
	}
	$sql .= "	
				GROUP BY tbl_pedido_item.peca
			) x WHERE tmp_distrib_pendencia_estudo.peca = x.peca;";

	if($script == 'gerar'){
		$sql_gera_script = "SELECT x.distribuidor, x.fabrica, x.pedido, x.peca, x.qtde_cancelar, 'Cancelado pela rotina gerencial de compra' as motivo, $login_admin as admin
							FROM (
							SELECT tbl_pedido.distribuidor, tbl_pedido.fabrica, tbl_pedido.pedido, tbl_pedido_item.peca, 
							SUM (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada_distribuidor) AS qtde_cancelar
							FROM tbl_pedido
							JOIN tbl_pedido_item USING (pedido)
							JOIN tbl_peca using(peca)
							WHERE tbl_pedido.distribuidor = 4311
							and   tbl_pedido.data <= '$aux_data_corte_garantia_posto 23:59:59'
							and   tbl_pedido_item.qtde > ( tbl_pedido_item.qtde_cancelada + tbl_pedido_item.qtde_faturada_distribuidor )
							AND   tbl_pedido.fabrica = $login_fabrica ";
		if($login_fabrica==51){
			$sql_gera_script .=  " AND tbl_pedido.tipo_pedido = 132 ";
		}
		if($login_fabrica==81){
			$sql_gera_script .= " AND tbl_pedido.tipo_pedido = 154 ";
		}
		$sql_gera_script .= " GROUP BY tbl_pedido.distribuidor, tbl_pedido.fabrica, tbl_pedido.pedido, tbl_pedido_item.peca
							) x  ";
		$res_gera_script = pg_exec($con,$sql_gera_script);
		//fputs ($file_script,"# CANCELAMENTO DE PEDIDO EM GARANTIA - POSTO X DISTRIB \n");
		for ($i=0;$i<pg_numrows($res_gera_script);$i++){ 
			$s_distribuidor  = pg_result($res_gera_script,$i,distribuidor);
			$s_fabrica       = pg_result($res_gera_script,$i,fabrica);
			$s_pedido        = pg_result($res_gera_script,$i,pedido);
			$s_peca          = pg_result($res_gera_script,$i,peca);
			$s_qtde_cancelar = pg_result($res_gera_script,$i,qtde_cancelar);
			$s_motivo        = pg_result($res_gera_script,$i,motivo);
			$s_admin         = pg_result($res_gera_script,$i,admin);
			#fputs ($file_script,"select fn_pedido_cancela_gama($s_distribuidor,$s_fabrica,$s_pedido,$s_peca,$s_qtde_cancelar,'$s_motivo',$s_admin); \n");
			$sql_can="select fn_pedido_cancela_gama($s_distribuidor,$s_fabrica,$s_pedido,$s_peca,$s_qtde_cancelar,'$s_motivo',$s_admin);";
			$res_can = pg_exec($con,$sql_can);
			$sqlq = " SELECT fn_atualiza_status_pedido($s_fabrica,$s_pedido)";
			$resq = pg_query($con,$sqlq);

		}
	}

	$sql .=" UPDATE tmp_distrib_pendencia_estudo set qtde_pendente_faturada = x.qtde_pendente
			FROM (
				SELECT SUM (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada_distribuidor) AS qtde_pendente, tbl_pedido_item.peca
				FROM tbl_pedido
				JOIN tbl_pedido_item USING (pedido)
				WHERE tbl_pedido.distribuidor = 4311
				and   tbl_pedido.data > '$aux_data_corte_faturada_posto 23:59:59'
				
				AND   tbl_pedido.posto NOT IN (SELECT posto FROM tbl_contas_receber WHERE tbl_contas_receber.posto = tbl_pedido.posto AND tbl_contas_receber.recebimento IS NULL AND tbl_contas_receber.vencimento <  CURRENT_DATE - INTERVAL '30 days' )

				/* existem pedido que estao faturado e cancelado, com embaque impresso, mas sem nf ex 1459358 */
				and   tbl_pedido_item.qtde > ( tbl_pedido_item.qtde_cancelada + tbl_pedido_item.qtde_faturada_distribuidor )
				AND   tbl_pedido.fabrica = $login_fabrica
				/* AND   tbl_pedido.status_pedido <> 13 */ ";
	if($login_fabrica==51){
		$sql .= " AND tbl_pedido.tipo_pedido = 131 ";
	}
	if($login_fabrica==81){
		$sql .= " AND tbl_pedido.tipo_pedido = 153 ";
	}
	$sql .= "	
				GROUP BY tbl_pedido_item.peca
			) x WHERE tmp_distrib_pendencia_estudo.peca = x.peca; ";
	if($script == 'gerar'){
		$sql_gera_script = "SELECT x.distribuidor, x.fabrica, x.posto, x.pedido, x.finalizado, x.peca, x.qtde_cancelar, 
									case when tbl_peca.peca_critia = 't' or tbl_peca.troca_obrigatoria = 't' then 
										'Cancelamento por peça crítica ou troca obrigatória' 
										else 
										'Cancelado pela rotina gerencial de compra' 
									end	as motivo,
							$login_admin as admin
							FROM (
							SELECT tbl_pedido.distribuidor, tbl_pedido.fabrica, tbl_pedido.posto, tbl_pedido.pedido, TO_CHAR(tbl_pedido.finalizado, 'dd/mm/YYYY') AS finalizado, tbl_pedido_item.peca, 
							SUM (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada_distribuidor) AS qtde_cancelar
							FROM tbl_pedido
							JOIN tbl_pedido_item USING (pedido)
							JOIN tbl_peca USING (peca)
							WHERE tbl_pedido.distribuidor = 4311
							and   (tbl_pedido.data <= '$aux_data_corte_faturada_posto 23:59:59' or tbl_peca.peca_critia = 't' or tbl_peca.troca_obrigatoria = 't')
							AND   tbl_pedido.posto IN (SELECT posto FROM tbl_contas_receber WHERE tbl_contas_receber.posto = tbl_pedido.posto AND tbl_contas_receber.recebimento IS NULL AND tbl_contas_receber.vencimento <  CURRENT_DATE - INTERVAL '30 days' )
							and   tbl_pedido_item.qtde > ( tbl_pedido_item.qtde_cancelada + tbl_pedido_item.qtde_faturada_distribuidor )
							AND   tbl_pedido.fabrica = $login_fabrica ";
		if($login_fabrica==51){
			$sql_gera_script .= " AND tbl_pedido.tipo_pedido = 131 ";
		}
		if($login_fabrica==81){
			$sql_gera_script .= " AND tbl_pedido.tipo_pedido = 153 ";
		}
		$sql_gera_script .= " 
							GROUP BY tbl_pedido.distribuidor, tbl_pedido.fabrica, tbl_pedido.posto, tbl_pedido.pedido, finalizado, tbl_pedido_item.peca
							) x  ";
		$res_gera_script = pg_exec($con,$sql_gera_script);
		//fputs ($file_script,"# CANCELAMENTO DE PEDIDO FATURADO - POSTO X DISTRIB \n");
		$cor = "#CCCCCC";
		for ($i=0;$i<pg_numrows($res_gera_script);$i++){ 
			$s_distribuidor  = pg_result($res_gera_script,$i,distribuidor);
			$s_fabrica       = pg_result($res_gera_script,$i,fabrica);
			$s_pedido        = pg_result($res_gera_script,$i,pedido);
			$s_posto         = pg_result($res_gera_script,$i,posto);
			$s_finalizado    = pg_result($res_gera_script,$i,finalizado);
			$s_peca          = pg_result($res_gera_script,$i,peca);

			$sql_peca        = "SELECT referencia, descricao FROM tbl_peca where peca = $s_peca;";
			$res_peca        = pg_exec($con,$sql_peca);
			$referencia      = pg_result($res_peca,0,referencia);
			$descricao       = pg_result($res_peca,0,descricao);

			$s_qtde_cancelar = pg_result($res_gera_script,$i,qtde_cancelar);
			$s_motivo        = pg_result($res_gera_script,$i,motivo);
			$s_admin         = pg_result($res_gera_script,$i,admin);
			//fputs ($file_script,"select fn_pedido_cancela_gama($s_distribuidor,$s_fabrica,$s_pedido,$s_peca,$s_qtde_cancelar,'$s_motivo',$s_admin); \n");
			$sql_can = "select fn_pedido_cancela_gama($s_distribuidor,$s_fabrica,$s_pedido,$s_peca,$s_qtde_cancelar,'$s_motivo',$s_admin);";
			$res_can = pg_exec($con,$sql_can);
			$sqlq = " SELECT fn_atualiza_status_pedido($s_fabrica,$s_pedido)";
			$resq = pg_query($con,$sqlq);


			$itens_cancelados = "<tr bgcolor=$cor><td>$referencia - $descricao</td><td>$s_qtde_cancelar</td></tr>";
			$mensagem = "<font face=arial color=#000000 size=1><b>Informamos que alguns itens do pedido de compra $s_pedido, finalizado em $s_finalizado, foram cancelados devido a ter expirado o prazo para atendimento. Caso ainda necessite das pe<E7>as, inseri-las novamente em um novo pedido</b>. Segue abaixo a lista com os itens cancelados:<table border=1 width=100% style=font-size:8pt;><tr><td>Componente</td><td>Qtde Cancelada</td></tr>$itens_cancelados</table></font>";
			//fputs ($file_script,"INSERT INTO tbl_comunicado (mensagem,tipo,fabrica,posto,obrigatorio_site,ativo)	 VALUES ('$mensagem','Comunicado',$login_fabrica,$s_posto,'t','t'); \n");
			$sql_can = "INSERT INTO tbl_comunicado (mensagem,tipo,fabrica,posto,obrigatorio_site,ativo)	 VALUES ('$mensagem','Comunicado',$login_fabrica,$s_posto,'t','t');";
			$res_can = pg_exec($con,$sql_can);
		}
	}

	$sql .= " UPDATE tmp_distrib_pendencia_estudo set qtde_pendente_garantia_distrib = x.qtde_pendente
			FROM (
				SELECT SUM (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada) AS qtde_pendente, tbl_pedido_item.peca
				FROM tbl_pedido
				JOIN tbl_pedido_item USING (pedido)
				WHERE tbl_pedido.posto = 4311
				and   tbl_pedido.data > '$aux_data_corte_garantia_distrib 23:59:59'
				and   tbl_pedido_item.qtde > ( tbl_pedido_item.qtde_cancelada + tbl_pedido_item.qtde_faturada )
				AND   tbl_pedido.fabrica = $login_fabrica
				/* AND   tbl_pedido.status_pedido <> 13 */";
	if($login_fabrica==51){
		$sql .= " AND tbl_pedido.tipo_pedido = 132 ";
	}
	if($login_fabrica==81){
		$sql .= " AND tbl_pedido.tipo_pedido = 154 ";
	}
	$sql .= "	
				GROUP BY tbl_pedido_item.peca
			) x WHERE tmp_distrib_pendencia_estudo.peca = x.peca; ";

	if($script == 'gerar'){
		$sql_gera_script = "SELECT x.distribuidor, x.fabrica, x.pedido, x.peca, x.qtde_cancelar, 
									case when tbl_peca.peca_critia = 't' or tbl_peca.troca_obrigatoria = 't' then 
										'Cancelamento por peça crítica ou troca obrigatória' 
										else 
										'Cancelado pela rotina gerencial de compra' 
									end	as motivo,
							$login_admin as admin
							FROM (
							SELECT tbl_pedido.distribuidor, tbl_pedido.fabrica, tbl_pedido.pedido, tbl_pedido_item.peca, 
							SUM (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada) AS qtde_cancelar
							FROM tbl_pedido
							JOIN tbl_pedido_item USING (pedido)
							JOIN tbl_peca USING (peca)
							WHERE tbl_pedido.posto = 4311
							and   ( tbl_pedido.data <= '$aux_data_corte_garantia_distrib 23:59:59' or tbl_peca.peca_critia = 't' or tbl_peca.troca_obrigatoria = 't')
							and   tbl_pedido_item.qtde > ( tbl_pedido_item.qtde_cancelada + tbl_pedido_item.qtde_faturada )
							AND   tbl_pedido.fabrica = $login_fabrica ";
		if($login_fabrica==51){
			$sql_gera_script .= " AND tbl_pedido.tipo_pedido = 132 ";
		}
		if($login_fabrica==81){
			$sql_gera_script .= " AND tbl_pedido.tipo_pedido = 154 ";
		}
		$sql_gera_script .= " 
							GROUP BY tbl_pedido.distribuidor, tbl_pedido.fabrica, tbl_pedido.pedido, tbl_pedido_item.peca
							) x  ";
		$res_gera_script = pg_exec($con,$sql_gera_script);
		#fputs ($file_script,"# CANCELAMENTO DE PEDIDO GARANTIA - DISTRIB X FABRICA \n");
		for ($i=0;$i<pg_numrows($res_gera_script);$i++){ 
			$s_distribuidor  = pg_result($res_gera_script,$i,distribuidor);
			$s_fabrica       = pg_result($res_gera_script,$i,fabrica);
			$s_pedido        = pg_result($res_gera_script,$i,pedido);
			$s_peca          = pg_result($res_gera_script,$i,peca);
			$s_qtde_cancelar = pg_result($res_gera_script,$i,qtde_cancelar);
			$s_motivo        = pg_result($res_gera_script,$i,motivo);
			$s_admin         = pg_result($res_gera_script,$i,admin);
			if(strlen($s_distribuidor)==0){
				$s_distribuidor = "null";
			}
			#fputs ($file_script,"select fn_pedido_cancela_gama($s_distribuidor,$s_fabrica,$s_pedido,$s_peca,$s_qtde_cancelar,'$s_motivo',$s_admin); \n");
			$sql_can = "select fn_pedido_cancela_gama($s_distribuidor,$s_fabrica,$s_pedido,$s_peca,$s_qtde_cancelar,'$s_motivo',$s_admin);";
			$res_can = pg_exec($con,$sql_can);
			$sqlq = " SELECT fn_atualiza_status_pedido($s_fabrica,$s_pedido)";
			$resq = pg_query($con,$sqlq);

		}
	}


	$sql .= " UPDATE tmp_distrib_pendencia_estudo set qtde_pendente_faturada_distrib = x.qtde_pendente
			FROM (
				SELECT SUM (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada) AS qtde_pendente, tbl_pedido_item.peca
				FROM tbl_pedido
				JOIN tbl_pedido_item USING (pedido)
				WHERE tbl_pedido.posto = 4311
				and   tbl_pedido.data > '$aux_data_corte_faturada_distrib 23:59:59'
				/* existem pedido que estao faturado e cancelado, com embaque impresso, mas sem nf ex 1459358 */
				and   tbl_pedido_item.qtde > ( tbl_pedido_item.qtde_cancelada + tbl_pedido_item.qtde_faturada )
				AND   tbl_pedido.fabrica = $login_fabrica
				/* AND   tbl_pedido.status_pedido <> 13 */";
	if($login_fabrica==51){
		$sql .= " AND tbl_pedido.tipo_pedido = 131 ";
	}
	if($login_fabrica==81){
		$sql .= " AND tbl_pedido.tipo_pedido = 153 ";
	}
	$sql .= "	
				GROUP BY tbl_pedido_item.peca
			) x WHERE tmp_distrib_pendencia_estudo.peca = x.peca; ";

	if($script == 'gerar'){
		$sql_gera_script = "SELECT x.distribuidor, x.fabrica, x.pedido, x.peca, x.qtde_cancelar, 
									case when tbl_peca.peca_critia = 't' or tbl_peca.troca_obrigatoria = 't' then 
										'Cancelamento por peça crítica ou troca obrigatória' 
										else 
										'Cancelado pela rotina gerencial de compra' 
									end	as motivo,
							$login_admin as admin
							FROM (
							SELECT tbl_pedido.distribuidor, tbl_pedido.fabrica, tbl_pedido.pedido, tbl_pedido_item.peca, 
							SUM (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada) AS qtde_cancelar
							FROM tbl_pedido
							JOIN tbl_pedido_item USING (pedido)
							JOIN tbl_peca USING (peca)
							WHERE tbl_pedido.posto = 4311
							and   ( tbl_pedido.data <= '$aux_data_corte_faturada_distrib 23:59:59' or tbl_peca.peca_critia = 't' or tbl_peca.troca_obrigatoria = 't')
							and   tbl_pedido_item.qtde > ( tbl_pedido_item.qtde_cancelada + tbl_pedido_item.qtde_faturada )
							AND   tbl_pedido.fabrica = $login_fabrica ";
		if($login_fabrica==51){
			$sql_gera_script .= " AND tbl_pedido.tipo_pedido = 131 ";
		}
		if($login_fabrica==81){
			$sql_gera_script .= " AND tbl_pedido.tipo_pedido = 153 ";
		}
		$sql_gera_script .= " 
							GROUP BY tbl_pedido.distribuidor, tbl_pedido.fabrica, tbl_pedido.pedido, tbl_pedido_item.peca
							) x  ";
		$res_gera_script = pg_exec($con,$sql_gera_script);
		#fputs ($file_script,"# CANCELAMENTO DE PEDIDO FATURADO - DISTRIB X FABRICA \n");
		for ($i=0;$i<pg_numrows($res_gera_script);$i++){ 
			$s_distribuidor  = pg_result($res_gera_script,$i,distribuidor);
			$s_fabrica       = pg_result($res_gera_script,$i,fabrica);
			$s_pedido        = pg_result($res_gera_script,$i,pedido);
			$s_peca          = pg_result($res_gera_script,$i,peca);
			$s_qtde_cancelar = pg_result($res_gera_script,$i,qtde_cancelar);
			$s_motivo        = pg_result($res_gera_script,$i,motivo);
			$s_admin         = pg_result($res_gera_script,$i,admin);
			if(strlen($s_distribuidor)==0){
				$s_distribuidor = "null";
			}
			#fputs ($file_script,"select fn_pedido_cancela_gama($s_distribuidor,$s_fabrica,$s_pedido,$s_peca,$s_qtde_cancelar,'$s_motivo',$s_admin); \n");
			$sql_can = "select fn_pedido_cancela_gama($s_distribuidor,$s_fabrica,$s_pedido,$s_peca,$s_qtde_cancelar,'$s_motivo',$s_admin);";
			$res_can = pg_exec($con,$sql_can);
			$sqlq = " SELECT fn_atualiza_status_pedido($s_fabrica,$s_pedido)";
			$resq = pg_query($con,$sqlq);
		}
	}

	$sql .= " UPDATE tmp_distrib_pendencia_estudo set qtde_atendido_gar_nf_posto = x.qtde_atendido_gar_nf_posto
			FROM (
				SELECT sum (tbl_faturamento_item.qtde) as qtde_atendido_gar_nf_posto, tbl_faturamento_item.peca
				FROM tbl_faturamento
				JOIN tbl_faturamento_item using(faturamento)
				WHERE (tbl_faturamento.fabrica = $login_fabrica or tbl_faturamento.fabrica = 10)
				AND   tbl_faturamento.posto <> 4311 
				and   tbl_faturamento.distribuidor = 4311 ";
			if($login_fabrica==51){
				$sql .= " AND (tbl_faturamento.tipo_pedido = 132 or tbl_faturamento.tipo_pedido = 158 )";
			}
			if($login_fabrica==81){
				$sql .= " AND tbl_faturamento.tipo_pedido = 154 ";
			}

	$sql .= " GROUP by tbl_faturamento_item.peca
			) x WHERE tmp_distrib_pendencia_estudo.peca = x.peca;

			UPDATE tmp_distrib_pendencia_estudo set qtde_atendido_gar_nf_distrib = x.qtde_atendido_gar_nf_distrib
			FROM (
				SELECT sum (tbl_faturamento_item.qtde_estoque) as qtde_atendido_gar_nf_distrib, tbl_faturamento_item.peca
				FROM tbl_faturamento
				JOIN tbl_faturamento_item using(faturamento)
				WHERE tbl_faturamento.posto = 4311 
				AND (
					tbl_faturamento.distribuidor IN (
						SELECT DISTINCT distribuidor FROM tbl_faturamento WHERE fabrica = 10 AND posto = 4311)
					OR
					tbl_faturamento.fabrica in ($login_fabrica,10)
					AND tbl_faturamento.distribuidor is null
				)
				AND tbl_faturamento.cancelada IS NULL
				AND tbl_faturamento.cfop = '5949'
				";

				/*
				WHERE  tbl_faturamento.posto = $login_posto
				AND (
					tbl_faturamento.distribuidor IN (
						SELECT DISTINCT distribuidor FROM tbl_faturamento WHERE fabrica = 10 AND posto = 4311)
					OR
					tbl_faturamento.fabrica in (3,25,51,81,10)
					AND tbl_faturamento.distribuidor is null
				)
				AND tbl_peca.referencia = '$referencia'
				AND tbl_faturamento.cancelada IS NULL
				*/

	$sql .= " GROUP by tbl_faturamento_item.peca
			) x WHERE tmp_distrib_pendencia_estudo.peca = x.peca;



			UPDATE tmp_distrib_pendencia_estudo set qtde_atendido_fat_nf_posto = x.qtde_atendido_fat_nf_posto
			FROM (
				SELECT sum (tbl_faturamento_item.qtde) as qtde_atendido_fat_nf_posto, tbl_faturamento_item.peca
				FROM tbl_faturamento
				JOIN tbl_faturamento_item using(faturamento)
				WHERE (tbl_faturamento.fabrica = $login_fabrica or tbl_faturamento.fabrica = 10)
				AND   tbl_faturamento.posto <> 4311 
				AND   tbl_faturamento.distribuidor = 4311 ";
			if($login_fabrica==51){
				$sql .= " AND ( tbl_faturamento.tipo_pedido = 131 or tbl_faturamento.tipo_pedido = 76)";
			}
			if($login_fabrica==81){
				$sql .= " AND tbl_faturamento.tipo_pedido = 153 ";
			}

	$sql .= " GROUP by tbl_faturamento_item.peca
			) x WHERE tmp_distrib_pendencia_estudo.peca = x.peca;

			UPDATE tmp_distrib_pendencia_estudo set qtde_atendido_fat_nf_distrib = x.qtde_atendido_fat_nf_distrib
			FROM (
				SELECT sum (tbl_faturamento_item.qtde_estoque) as qtde_atendido_fat_nf_distrib, tbl_faturamento_item.peca
				FROM tbl_faturamento
				JOIN tbl_faturamento_item using(faturamento)
				WHERE tbl_faturamento.posto = 4311 
				AND (
					tbl_faturamento.distribuidor IN (
						SELECT DISTINCT distribuidor FROM tbl_faturamento WHERE fabrica = 10 AND posto = 4311)
					OR
					tbl_faturamento.fabrica in ($login_fabrica,10)
					AND tbl_faturamento.distribuidor is null
				)
				AND tbl_faturamento.cancelada IS NULL
				AND tbl_faturamento.cfop = '5102'
				GROUP by tbl_faturamento_item.peca
			) x WHERE tmp_distrib_pendencia_estudo.peca = x.peca;



			UPDATE tmp_distrib_pendencia_estudo set qtde_estoque = qtde
			FROM   tbl_posto_estoque
			where  tbl_posto_estoque.posto = 4311
			and    tbl_posto_estoque.peca = tmp_distrib_pendencia_estudo.peca;
			
			SELECT * from tmp_distrib_pendencia_estudo 
			WHERE   qtde_pendente_garantia <> 0
			or     qtde_pendente_faturada <> 0
			or     qtde_pendente_garantia_distrib <> 0
			or     qtde_pendente_faturada_distrib <> 0
			order by referencia;
			";
	//echo nl2br($sql);
	//exit;
	$res = pg_exec ($con,$sql);

	echo (isset($tipo)) ? "<h1>".strtoupper($tipo)."</h1>" : "";
	$total_sugestao_pedido_garantia = 0;
	$total_sugestao_pedido_faturada = 0;
	$total_preco_sugestao_pedido_garantia = 0;
	$total_preco_sugestao_pedido_faturada = 0;
	$total_pecas_devolver = 0;
	$total_nao_tem_devolver = 0;
	$total_valor_pagar = 0;
	$total_valor_nao_tem_devolver = 0;

	?>
	
	<table width='600' class='Conteudo' style='background-color: #ffffff' border='1' cellpadding='5' cellspacing='0' align='center'>
		<tr class='Titulo' background='#0033FF'>
			<td colspan='2'>Gerencial</td>
			<td colspan='2'>Peças Pendentes<br>posto x distrib</td>
			<td colspan='2'>Peças Pendentes<br>distrib x fábrica</td>
			<td colspan='2'><font color='red'><b>SUGESTÃO PEDIDO</b></font></td>
			<td colspan='1'>Saldo em</td>
			<td colspan='2'>Peças Enviadas<br>ditrib -> posto</td>
			<td colspan='2'>Peças Enviadas<br>fabrica -> distrib</td>
			<td colspan='2'>Acerto</td>
		</tr>

		<tr class='Titulo' background='#0033FF'>
			<td nowrap >Peça</td>
			<td nowrap >Descrição</td>
			<td nowrap >Qtde<br>garantia<br>Posto</td>
			<td nowrap >Qtde<br>faturada<br>Posto</td>
			<td nowrap >Qtde<br>garantia<br>Distrib</td>
			<td nowrap >Qtde<br>faturada<br>distrib</td>
			<td nowrap bgcolor="#FFDFF1"><font color='red'><b>Garantia</b></font></td>
			<td nowrap bgcolor="#FFDFF1"><font color='red'><b>Compra</b></font></td>
			<td nowrap >Qtde<br>Estoque</td>
			<td nowrap >Qtde<br>NF garantia<br>Posto</td>
			<td nowrap >Qtde<br>NF faturada<br>Posto</td>
			<td nowrap >Qtde<br>NF garantia<br>Distrib</td>
			<td nowrap >Qtde<br>NF faturada<br>distrib</td>
			<td nowrap >Peças<br>Devolver</td>
			<td nowrap >Valor a pagar</td>
		</tr>

		<?
		//$resultados = pg_fetch_all($res);

		flush();
		$data = date ("d/m/Y H:i:s");
		
		$arquivo_nome     = "relatorio-pendencia-peca-$login_admin.xls";
		$path             = "/www/assist/www/admin/xls/";
		$path_tmp         = "/tmp/";

		$arquivo_completo     = $path.$arquivo_nome;
		$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

		echo `rm $arquivo_completo_tmp `;
		echo `rm $arquivo_completo `;

		$fp = fopen ($arquivo_completo_tmp,"w");

		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>RELATÓRIO DE MÃO-DE-OBRA DEWALT - $data");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");
		fputs ($fp,"<table width='600' class='Conteudo' style='background-color: #ffffff' border='1' cellpadding='5' cellspacing='0' align='center'>");
		fputs ($fp,"<tr class='Titulo' background='imagens_admin/azul.gif'>");
		fputs ($fp,"<td colspan='11'>Peças Pendentes no Distribuidor</td>");
		fputs ($fp,"</tr>");
		fputs ($fp,"<tr class='Titulo' background='imagens_admin/azul.gif'>");
		fputs ($fp,"<td nowrap >Peça</td>");
		fputs ($fp,"<td nowrap >Descrição</td>");
		fputs ($fp,"<td nowrap >Qtde Garantia Posto</td>");
		fputs ($fp,"<td nowrap >Qtde Faturada Posto</td>");
		fputs ($fp,"<td nowrap >Qtde Garantia Distrib</td>");
		fputs ($fp,"<td nowrap >Qtde Faturada Distrib</td>");
		fputs ($fp,"<td nowrap >Sugestão Pedido Garantia</td>");
		fputs ($fp,"<td nowrap >Sugestão Pedido Compra</td>");
		fputs ($fp,"<td nowrap >Qtde Estoque</td>");
		fputs ($fp,"<td nowrap >Qtde NF Garantia Posto</td>");
		fputs ($fp,"<td nowrap >Qtde NF Faturada Posto</td>");
		fputs ($fp,"<td nowrap >Qtde NF Garantia Distrib</td>");
		fputs ($fp,"<td nowrap >Qtde NF Faturada Distrib</td>");
		fputs ($fp,"<td nowrap >Peças Devolver</td>");
		fputs ($fp,"<td nowrap >Valor a pagar</td>");
		fputs ($fp,"</tr>");
		fputs ($fp,"<tbody>");

		if (pg_numrows($res) > 0) {
			
			for ($j=0;$j<pg_numrows($res);$j++){ 
				$resultado_key = $j;

				$peca              = pg_result($res,$j,peca); 
				$peca_critica      = pg_result($res,$j,peca_critica); 
				$troca_obrigatoria = pg_result($res,$j,troca_obrigatoria); 

				echo "<tr onMouseOver='this.style.cursor=\"pointer\" ; this.style.background=\"#cccccc\"'  onMouseOut='this.style.backgroundColor=\"#ffffff\" '  onClick=\"carregaDados('$peca','$tipo','$aux_data_corte_garantia_posto','$aux_data_corte_faturada_posto','$aux_data_corte_garantia_distrib','$aux_data_corte_faturada_distrib','$resultado_key','div_detalhe_$resultado_key'); \" >";
				fputs($fp, "<tr>");

				echo "<td ";
					if($peca_critica=='t' or $troca_obrigatoria == 't'){
						echo "bgcolor='#FF9999' title='Esta peça está marcada como PEÇA CRÍTICA ou como TROCA OBRIGATÓRIA e será cancelada quando efetuar Cancelamentos (menos o pedido de peça em garantia do posto, que deverá ser feito o processo de troca pela fábrica)' ";
					}else{
						echo "title='Referência da peça'";
					}

				echo "> ";

				$sqlT = "SELECT preco FROM tbl_tabela_item WHERE tabela = $tabela_fat AND peca = $peca";
				$resT = pg_query($con,$sqlT);
				$preco = pg_fetch_result($resT,0,0);
				if(strlen($preco)==0) $preco = "null";

				echo pg_result($res,$j,referencia)."<br>".number_format($preco,2,',','.');
				echo "</td>";
				fputs($fp, "<td>");
				fputs($fp, pg_result($res,$j,referencia));
				fputs($fp, "</td>");



				echo "<td title='Descrição da peça'>";
				echo pg_result($res,$j,descricao);
				echo "</td>";
				fputs($fp, "<td >");
				fputs($fp, pg_result($res,$j,descricao));
				fputs($fp, "</td>");

				echo "<td align='right' title='Qtde. de peças de pedidos em GARANTIA pendentes do posto x distribuidor'>";
				echo pg_result($res,$j,qtde_pendente_garantia);
				echo "</td>";
				fputs($fp, "<td align='right'>");
				fputs($fp, pg_result($res,$j,qtde_pendente_garantia));
				fputs($fp, "</td>");

				echo "<td align='right'  title='Qtde. de peças de pedidos FATURADOS pendentes do posto x distribuidor'>";
				echo pg_result($res,$j,qtde_pendente_faturada);
				echo "</td>";
				fputs($fp, "<td align='right'>");
				fputs($fp, pg_result($res,$j,qtde_pendente_faturada));
				fputs($fp, "</td>");


				echo "<td align='right' title='Qtde. de peças de pedidos em GARANTIA pendentes do distribuidor x fábrica'>";
				echo pg_result($res,$j,qtde_pendente_garantia_distrib);
				echo "</td>";
				fputs($fp, "<td align='right'>");
				fputs($fp, pg_result($res,$j,qtde_pendente_garantia_distrib));
				fputs($fp, "</td>");

				echo "<td align='right' title='Qtde. de peças de pedidos FATURADOS pendentes do distribuidor x Fábrica'>";
				echo pg_result($res,$j,qtde_pendente_faturada_distrib);
				echo "</td>";
				fputs($fp, "<td align='right'>");
				fputs($fp, pg_result($res,$j,qtde_pendente_faturada_distrib));
				fputs($fp, "</td>");


				$sugestao_pedido_garantia       = 0;
				$qtde_pendente_garantia         = pg_result($res,$j,qtde_pendente_garantia);
				$qtde_estoque                   = pg_result($res,$j,qtde_estoque);
				$qtde_pendente_garantia_distrib = pg_result($res,$j,qtde_pendente_garantia_distrib);
				//echo " qtde_pendente_garantia $qtde_pendente_garantia <br>";
				//echo " qtde_estoque $qtde_estoque <br>";
				//echo " qtde_pendente_garantia_distrib $qtde_pendente_garantia_distrib <br>";

				if( $qtde_pendente_garantia > ( $qtde_estoque + $qtde_pendente_garantia_distrib ) ) {
					//echo " sugestao_pedido_garantia $sugestao_pedido_garantia <br>";
					//echo " qtde_pendente_garantia $qtde_pendente_garantia <br>";
					//echo " qtde_estoque $qtde_estoque <br>";
					//echo " qtde_pendente_garantia_distrib $qtde_pendente_garantia_distrib <br>";
					//echo " $sugestao_pedido_garantia = $sugestao_pedido_garantia + $qtde_pendente_garantia - ( $qtde_estoque + $qtde_pendente_garantia_distrib ) ";
					$sugestao_pedido_garantia = $sugestao_pedido_garantia + $qtde_pendente_garantia - ( $qtde_estoque + $qtde_pendente_garantia_distrib ) ;
					//echo " sugestao_pedido_garantia $sugestao_pedido_garantia <br>";
					//exit;

				}else{
					$sugestao_pedido_garantia = 0;
				}
				if($peca_critica=='t' or $troca_obrigatoria == 't'){
					$sugestao_pedido_garantia = 0;
				}


				if( $script == 'gerar' && $sugestao_pedido_garantia>0 ) {
					$sql_ins_item = "INSERT INTO tbl_pedido_item (
											pedido,
											peca  ,
											qtde  ,
											qtde_faturada,
											qtde_cancelada,
											preco
									) VALUES (
											$pedido_garantia,
											$peca  ,
											$sugestao_pedido_garantia,
											0      ,
											0      ,
											$preco)";
					$res_ins_item = pg_exec ($con, $sql_ins_item);
				}
				echo "<td align='right' bgcolor='#FFDFF1' title='Sugestão de pedido de peças em GARANTIA para Fábrica'>";
				echo "<font color='BLUE'><b>$sugestao_pedido_garantia</b></font>";
				if($preco=="null"){
					$preco = 0;
				}
				$total_sugestao_pedido_garantia = $total_sugestao_pedido_garantia + $sugestao_pedido_garantia;
				$total_preco_sugestao_pedido_garantia = $total_preco_sugestao_pedido_garantia + ($sugestao_pedido_garantia * $preco);
				echo "<br>".number_format($sugestao_pedido_garantia * $preco,2,',','.');
				echo "<br>".number_format($total_preco_sugestao_pedido_garantia,2,',','.');
				echo "</td>";
				fputs($fp, "<td align='right'>");
				fputs($fp, $sugestao_pedido_garantia);
				fputs($fp, "</td>");

				$sugestao_pedido_faturada       = 0;
				$qtde_pendente_faturada         = pg_result($res,$j,qtde_pendente_faturada);
				$qtde_pendente_faturada_distrib = pg_result($res,$j,qtde_pendente_faturada_distrib);
				if($sugestao_pedido_garantia > 0){
					if( $qtde_pendente_faturada > $qtde_pendente_faturada_distrib ) {
						$sugestao_pedido_faturada = $sugestao_pedido_faturada + $qtde_pendente_faturada - $qtde_pendente_faturada_distrib;
					}else{
						$sugestao_pedido_faturada = 0;
					}
				}else{
					if( $qtde_pendente_faturada > ( $qtde_estoque + $qtde_pendente_faturada_distrib ) ) {
						$sugestao_pedido_faturada = $sugestao_pedido_faturada + $qtde_pendente_faturada - ( $qtde_estoque + $qtde_pendente_faturada_distrib );
					}else{
						$sugestao_pedido_faturada = 0;
					}
				}
				$sqlT = "SELECT preco FROM tbl_tabela_item WHERE tabela = $tabela_fat AND peca = $peca";
				$resT = pg_query($con,$sqlT);
				$preco = pg_fetch_result($resT,0,0);
				
				if(strlen($preco)==0) $preco = "null";

				if($peca_critica=='t' or $troca_obrigatoria == 't'){
					$sugestao_pedido_faturada = 0;
				}

				if( $script == 'gerar' && $sugestao_pedido_faturada > 0 ) {
					$sql_ins_item = "INSERT INTO tbl_pedido_item (
											pedido,
											peca  ,
											qtde  ,
											qtde_faturada,
											qtde_cancelada,
											preco
									) VALUES (
											$pedido_faturado,
											$peca  ,
											$sugestao_pedido_faturada,
											0      ,
											0      ,
											$preco)";
					$res_ins_item = pg_exec ($con, $sql_ins_item);
				}

				echo "<td align='right' bgcolor='#FFDFF1' title='Sugestão de pedido de peças FATURADAS (compra) para Fábrica'>";
				echo "<font color='GREEN'><b>$sugestao_pedido_faturada</b></font>";
				if($preco == "null"){
					$preco = 0;
				}
				$total_sugestao_pedido_faturada       = $total_sugestao_pedido_faturada       + $sugestao_pedido_faturada;
				$total_preco_sugestao_pedido_faturada = $total_preco_sugestao_pedido_faturada + ($sugestao_pedido_faturada * $preco);
				//echo "total_sugestao_pedido_faturada $total_sugestao_pedido_faturada total_preco_sugestao_pedido_faturada $total_preco_sugestao_pedido_faturada preco $preco <br>";
				echo "<br>".number_format($sugestao_pedido_faturada * $preco,2,',','.');
				echo "<br>".number_format($total_preco_sugestao_pedido_faturada,2,',','.');
				echo "</td>";
				fputs($fp, "<td align='right'>");
				fputs($fp, $sugestao_pedido_faturada);
				fputs($fp, "</td>");


				echo "<td align='right' title='Qtde de saldo do estoque'>";
				echo pg_result($res,$j,qtde_estoque);
				echo "</td>";
				fputs($fp, "<td align='right'>");
				fputs($fp, pg_result($res,$j,qtde_estoque));
				fputs($fp, "</td>");

				echo "<td align='right' title='Qtde de peças em NF de garantia do DISTRIBUIDOR -> POSTO'>";
				echo pg_result($res,$j,qtde_atendido_gar_nf_posto);
				echo "</td>";
				fputs($fp, "<td align='right'>");
				fputs($fp, pg_result($res,$j,qtde_atendido_gar_nf_posto));
				fputs($fp, "</td>");

				echo "<td align='right' title='Qtde de peças em NF de venda do DISTRIBUIDOR -> POSTO'>";
				echo pg_result($res,$j,qtde_atendido_fat_nf_posto);
				echo "</td>";
				fputs($fp, "<td align='right'>");
				fputs($fp, pg_result($res,$j,qtde_atendido_fat_nf_posto));
				fputs($fp, "</td>");

				echo "<td align='right' title='Qtde de peças em NF de garantia do FABRICANTE -> DISTRIBUIDOR'>";
				echo pg_result($res,$j,qtde_atendido_gar_nf_distrib);
				echo "</td>";
				fputs($fp, "<td align='right'>");
				fputs($fp, pg_result($res,$j,qtde_atendido_gar_nf_distrib));
				fputs($fp, "</td>");

				echo "<td align='right' title='Qtde de peças em NF de VENDA do FABRICANTE -> DISTRIBUIDOR'>";
				echo pg_result($res,$j,qtde_atendido_fat_nf_distrib);
				echo "</td>";
				fputs($fp, "<td align='right'>");
				fputs($fp, pg_result($res,$j,qtde_atendido_fat_nf_distrib));
				fputs($fp, "</td>");

				echo "<td align='right' title='Total de peças a devolver para o Fabricante / Total de peças que o DISTRIBUIDOR não tem para devolver ao FABRICANTE'>";
				$pecas_devolver = pg_result($res,$j,qtde_atendido_gar_nf_distrib) - pg_result($res,$j,qtde_atendido_gar_nf_posto);
				$total_pecas_devolver = $total_pecas_devolver + $pecas_devolver;
				$nao_tem_devolver = 0;
				if($qtde_estoque > $pecas_devolver){
					echo $pecas_devolver;
				}else{
					echo $qtde_estoque;
					$nao_tem_devolver = $pecas_devolver - $qtde_estoque;
					echo "<font color='red'>&nbsp;$nao_tem_devolver</font>";
				}
				$total_nao_tem_devolver = $total_nao_tem_devolver + $nao_tem_devolver;
				echo "</td>";
				fputs($fp, "<td align='right'>");
				fputs($fp, $pecas_devolver);
				fputs($fp, "</td>");

				echo "<td align='right' title='Valor de peças que o DISTRIBUIDOR deve para o FABRICANTE sobre peças compradas / Valor das peças que o DISTRIBUIDOR deveria devolver para o FABRICANTE'>";
				$valor_pagar = pg_result($res,$j,qtde_atendido_fat_nf_posto) - pg_result($res,$j,qtde_atendido_fat_nf_distrib);
				$valor_pagar = $valor_pagar * $preco;
				$total_valor_pagar = $total_valor_pagar + $valor_pagar;
				echo number_format($valor_pagar, 2, ',', '.');
				if($nao_tem_devolver>0){
					$valor_nao_tem_devolver = $nao_tem_devolver * $preco;
					$total_valor_nao_tem_devolver = $total_valor_nao_tem_devolver + $valor_nao_tem_devolver;
					echo "<font color='red'>&nbsp;".number_format($valor_nao_tem_devolver, 2, ',', '.')."</font>";
				}
				fputs($fp, "<td align='right'>");
				fputs($fp, $pecas_devolver, $total_valor_nao_tem_devolver);
				fputs($fp, "</td>");
				echo "</td>";

				echo "</tr>";

				echo "<tr><td colspan='15'>";
				echo "<div id='div_detalhe_$resultado_key' rel='resultado'></div>";
				echo "</td></tr>";








				fputs($fp, "</tr>");
			}

		}
		?>
		<tr class='Titulo' background='#0033FF'>
			<td colspan='2'>Total</td>
			<td colspan='2'>&nbsp;</td>
			<td colspan='2'>&nbsp;</td>
			<td colspan='1' align='right' bgcolor='#FFDFF1'><font color='red'><b><? echo $total_sugestao_pedido_garantia."<br>".number_format($total_preco_sugestao_pedido_garantia, 2, ',', '.'); ?></b></font></td>
			<td colspan='1' align='right' bgcolor='#FFDFF1'><font color='red'><b><? echo $total_sugestao_pedido_faturada."<br>".number_format($total_preco_sugestao_pedido_faturada, 2, ',', '.'); ?></b></font></td>
			<td colspan='1'>&nbsp;</td>
			<td colspan='2'>&nbsp;</td>
			<td colspan='2'>&nbsp;</td>
			<td colspan='1' align='right'><? echo $total_pecas_devolver."&nbsp;<font color='red'>".$total_nao_tem_devolver."</font>"; ?></td>
			<td colspan='1' align='right'><? echo number_format($total_valor_pagar,2,',','.')."&nbsp;<font color='red'>".number_format($total_valor_nao_tem_devolver,2,',','.')."</font>"; ?></td>
		</tr>
		<?

		fputs ($fp,"</tbody>");
		fputs ($fp, "</table>");
		fputs ($fp, " </body>");
		fputs ($fp, " </html>");
		
		echo ` cp $arquivo_completo_tmp $path `;
		//echo ` cp /tmp/script_cancela_pedido.sql /www/assist/www/xls/script_cancela_pedido.sql `;

		echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f $arquivo_completo $arquivo_completo_tmp `;
		$resposta .= "<br>";
		$resposta .="<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
		$resposta .="<tr>";
		$resposta .= "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='xls/$arquivo_nome' target='_blank'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
		$resposta .= "</tr>";
		$resposta .= "</table>";
		echo $resposta;
		if($script=='gerar'){
			$sql_insert = "SELECT fn_pedido_finaliza ($pedido_garantia,$fabrica)";
			$res_insert = pg_exec($con,$sql_insert);

			$sql_insert = "SELECT fn_pedido_finaliza ($pedido_faturado,$fabrica)";
			$res_insert = pg_exec($con,$sql_insert);

			$resposta .= "<br>";
			$resposta .="<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
			$resposta .="<tr>";
			$resposta .= "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Pedidos Gerados: Garantia </font>
			<a href=pedido_admin_consulta.php?pedido=$pedido_garantia target=_blank><font color=blue>$pedido_garantia</font></a>
			Faturado <a href=pedido_admin_consulta.php?pedido=$pedido_faturado target=_blank><font color=blue>$pedido_faturado</font></a></td>";
			$resposta .= "</tr>";
			$resposta .= "</table>";
			echo $resposta;
		}
		?>

	</table>
<?
}else{
	$msg_erro ="Favor digitar todas as datas de corte!";
}
?>

								<font class='Nota'>- Não estamos considerando devolução de posto nas NFS, porque muitas foram feitas como acerto de estoque, <br>
									logo, alguns casos teremos mais faturamento para posto do que recebemos. Ex: MEX1271 da Gama Italy<br>
									- Ronaldo, seria interessante você fazer somente a simulação, quando for EFETUAR CANCELAMENTOS, peça para a Marisa rodar com seu usuário.<br>Mesmo que não retorne na tela, é possível controlar/acompanhar o processamento (um pouco demorado) no banco.</font>
<form name='form_pend' action='<?=$PHP_SELF?>' method='POST'>
	<table width='700px' class='formulario' border='0' cellpadding='0' cellspacing='1' align='center'>
		<?php
			if($msg_erro!=""){ ?>
			<tr>
				<td align="center" class='msg_erro'><? echo $msg_erro; ?></td>
			</tr>

		<?php
			}
		?>
		<tr>
			<td class='titulo_tabela'>Parâmetros de Pesquisa</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>
				<table width='100%' border='0' cellspacing='1' cellpadding='2' class='formulario'>
					<tr>
						<td width='230px'>Pendência do Posto x Distrib</td>
						<td align="left" nowrap width='230px' title='Desconsiderando todos os pedidos até esta data!'>
							Data Corte Pedido em Garantia (?)<br />
							<input type="text" name="data_corte_garantia_posto" id="data_corte_garantia_posto" size="12" maxlength="10" class='frm' value="<? if (strlen($data_corte_garantia_posto) > 0) echo $data_corte_garantia_posto; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';"> 
						</td>
						<td nowrap align="left" width='230px' title='Desconsiderando todos os pedidos até esta data!'>
							Data Corte Pedido faturado (?)<br />
							<input type="text" name="data_corte_faturada_posto" id="data_corte_faturada_posto" size="12" maxlength="10" class='frm' value="<? if (strlen($data_corte_faturada_posto) > 0) echo $data_corte_faturada_posto; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';"> 
						</td>
					</tr>
					<tr>
						<td>&nbsp;&nbsp;</td>
						<td>&nbsp;&nbsp;</td>
					</tr>
					<tr>
						<td width='180px'>Pendência do Distribuidor x Fábrica</td>
						<td align="left" nowrap width='230px' title='Desconsiderando todos os pedidos até esta data!'>
							Data Corte Pedido em Garantia (?)<br />
							<input type="text" name="data_corte_garantia_distrib" id="data_corte_garantia_distrib" size="12" maxlength="10" class='frm' value="<? if (strlen($data_corte_garantia_distrib) > 0) echo $data_corte_garantia_distrib; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';"> 
						</td>
						<td align="left" nowrap width='230px' title='Desconsiderando todos os pedidos até esta data!'>
							Data Corte Pedido faturado (?)<br />
							<input type="text" name="data_corte_faturada_distrib" id="data_corte_faturada_distrib" size="12" maxlength="10" class='frm' value="<? if (strlen($data_corte_faturada_distrib) > 0) echo $data_corte_faturada_distrib; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';"> 
						</td>
					</tr>
					<tr>
						<td colspan='3'>NOTA: Não consideramos posto com pendência financeira superior a 30 dias para pedido faturado!</td>
					</tr>
					<tr align='center' width='100%'>
						<td colspan='3' align='center'>
							<input type='radio'name='script' value='gerar'     id='gerar'>    <label for='script'>EFETUAR CANCELAMENTOS</label>
							<input type='radio'name='script' value='nao_gerar' id='nao_gerar'><label for='nao_script'>SIMULAR</label>
						</td>
					</tr>
					<tr align='center' width='100%'>
						<td colspan='3' align='center'>
							<input type='submit' name='btn_simular' value='EXECUTAR' />
						</td>
					</tr>

				</table>
			</td>
		</tr>
	</table>
</form>
<?
include 'rodape.php';
?>
