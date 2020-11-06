<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if($login_fabrica<>10) header ("Location: index.php");

if($_REQUEST['gerar'] =='sim') {
	$admin = (!empty($_REQUEST['consulta_admin'])) ? $_REQUEST['consulta_admin'] : $login_admin;
	$cond = ($admin == '99999')  ? " AND (previsao_termino_interna < CURRENT_TIMESTAMP or previsao_termino < CURRENT_TIMESTAMP) " : " and atendente = $admin ";
	$sql = "SELECT hd_chamado, tipo_chamado, data, (select (data_inicio, data_prazo) from tbl_status_chamado s where atendente = s.admin and s.hd_chamado = hd.hd_chamado order by status_chamado desc limit 1) as datas, previsao_termino_interna, previsao_termino, titulo, atendente, fn_retira_especiais(status) as status, data_aprovacao, (select data_requisito_aprova from tbl_hd_chamado_requisito where tbl_hd_chamado_requisito.hd_chamado = hd.hd_chamado and excluido is not true and data_requisito_aprova notnull order by 1 desc limit 1) as data_requisito_aprova
		from tbl_hd_chamado hd
where fabrica_responsavel = 10 $cond and status not in ('Resolvido', 'Cancelado') and status !~'Aprova';";
$res = pg_query($con, $sql);

if(pg_num_rows($res) > 0) {
	$conteudo = '
  { "evts": [' ;
	$replace= array('(',')','"');
	$result = array('','','');
	for($i = 0; $i< pg_num_rows($res); $i++) {
		$hd_chamado = pg_fetch_result($res, $i, 'hd_chamado') ; 
		$data                     = pg_fetch_result($res,$i,data);
		$titulo                   = pg_fetch_result($res,$i,titulo);
		$atendente                = pg_fetch_result($res,$i,atendente);
		$data                     = trim(pg_fetch_result($res,$i,'data'));
		$data_aprovacao           = trim(pg_fetch_result($res,$i,'data_aprovacao'));
		$data_requisito_aprova    = trim(pg_fetch_result($res,$i,'data_requisito_aprova'));
		$datas                    = trim(pg_fetch_result($res,$i,'datas'));
		$datas = str_replace($replace, $result, $datas);
		$datas = explode(',',$datas);
		$titulo = str_replace('\'','',str_replace('\\','',$titulo));
		$titulo = htmlspecialchars($titulo);

		$status         = trim(pg_fetch_result($res,$i,'status'));
		$previsao_termino         = trim(pg_fetch_result($res,$i,previsao_termino));
		$previsao_termino_interna = trim(pg_fetch_result($res,$i,previsao_termino_interna));
		$ad = 'false';
		$data_termino = null;
		$data_termino = (!empty($previsao_termino_interna)) ? $previsao_termino_interna : $previsao_termino; 

		if(empty($previsao_termino_interna) and empty($previsao_termino)) {
			$data_termino = $data;
			$ad = 'true';
		}
		if(!empty($data_requisito_aprova)) $data = $data_requisito_aprova;
		if(!empty($data_aprovacao)) $data = $data_aprovacao;
		if(!empty($datas[0])) $data = $datas[0];
		if(!empty($datas[1])) $data_termino = $datas[1];
		if(!empty($previsao_termino_interna) and $previsao_termino_interna >$data_termino) $data_termino = $previsao_termino_interna;
		$hoje = date('Y-m-d');
		$ad = (!empty($datas[1])) ?  'false' : $ad;
		switch($status) {
			case 'Requisitos':
				$cid = 1;
				break;
			case 'Analise':
				$cid = 2;
				break;
			case 'AguardExecucao':
				$cid = 3;
				break;
			case 'Execucao':
			case 'Correcao':
				$cid = 4;
				break;
			case 'Orcamento':
				$cid = 5;
				break;
			case 'Validacao':
			case 'ValidacaoHomologacao':
				$cid = 6;
				break;
			case 'Efetivacao':
			case 'EfetivacaoHomologacao':
				$cid = 7;
				break;
			case 'AguardAdmin':
				$cid = 9;
				break;
			default:
				$cid = 8;
				break;

		}
		$conteudo .= ($i == 0) ? "":",";
		$conteudo .=  '{ 
		"id": '.$hd_chamado.',
			"cid": '.$cid.',
			"title": "'.$hd_chamado . " - " .$titulo.'",
			"start": "'.$data . '",
			"end": "'.$data_termino. '",
			"ad": '.$ad.', 
			"url": "adm_chamado_detalhe.php?hd_chamado='.$hd_chamado.'"
	}';
	}
	$conteudo .= ']
	}';
echo $conteudo;exit;
$file = fopen('calendarjs/event-list.js','w');
fputs($file,$conteudo);
fclose($file);
echo "ok";
}
exit;
}

?>
<html class=" x-viewport" id="ext-gen215"><head><meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
    <title>Calendario Pessoal</title>
	<!-- Ext includes -->
<? 
	$consulta_admin = (!empty($_REQUEST['consulta_admin'])) ? $_REQUEST['consulta_admin'] : $login_admin; 
$sql = "select nome_completo from tbl_admin where admin = $consulta_admin";
$res = pg_query($con, $sql);
$nome_completo = pg_fetch_result($res, 0, 'nome_completo');

echo "<script>
	var consulta_admin=  $consulta_admin ; 
	var nome_admin = '$nome_completo' ;
	</script>";
?>
    <link rel="stylesheet" type="text/css" href="css/ext-all.css">
<script type="text/javascript" src="js/ext-base.js"></script>
<script type="text/javascript" src="js/ext-all.js"></script>
    
    <!-- Calendar-specific includes -->
	<link rel="stylesheet" type="text/css" href="./calendarjs/calendar.css">
    <script type="text/javascript" src="./calendarjs/Ext.calendar.js"></script>
    <script type="text/javascript" src="./calendarjs/DayHeaderTemplate.js"></script>
    <script type="text/javascript" src="./calendarjs/DayBodyTemplate.js"></script>
    <script type="text/javascript" src="./calendarjs/DayViewTemplate.js"></script>
    <script type="text/javascript" src="./calendarjs/BoxLayoutTemplate.js"></script>
    <script type="text/javascript" src="./calendarjs/MonthViewTemplate.js"></script>
    <script type="text/javascript" src="./calendarjs/CalendarScrollManager.js"></script>
    <script type="text/javascript" src="./calendarjs/StatusProxy.js"></script>
    <script type="text/javascript" src="./calendarjs/CalendarDD.js"></script>
    <script type="text/javascript" src="./calendarjs/DayViewDD.js"></script>
    <script type="text/javascript" src="./calendarjs/EventRecord.js"></script>
	<script type="text/javascript" src="./calendarjs/MonthDayDetailView.js"></script>
    <script type="text/javascript" src="./calendarjs/CalendarPicker.js"></script>
    <script type="text/javascript" src="./calendarjs/WeekEventRenderer.js"></script>
    <script type="text/javascript" src="./calendarjs/CalendarView.js"></script>
    <script type="text/javascript" src="./calendarjs/MonthView.js"></script>
    <script type="text/javascript" src="./calendarjs/DayHeaderView.js"></script>
    <script type="text/javascript" src="./calendarjs/DayBodyView.js"></script>
    <script type="text/javascript" src="./calendarjs/DayView.js"></script>
    <script type="text/javascript" src="./calendarjs/WeekView.js"></script>
    <script type="text/javascript" src="./calendarjs/CalendarPanel.js"></script>
    <script type="text/javascript" src="./calendarjs/DateRangeField.js"></script>
    <script type="text/javascript" src="./calendarjs/ReminderField.js"></script>
    <script type="text/javascript" src="./calendarjs/EventEditForm.js"></script>
	   <script type="text/javascript" src="calendarjs/EventEditWindow.js"></script>
    <!-- App -->
    <link rel="stylesheet" type="text/css" href="./calendarjs/examples.css">
	<script type="text/javascript" src="./calendarjs/calendar-list.js"></script>
    <script type="text/javascript" src="./calendarjs/event-list.js"></script>

	<script type="text/javascript" src="./calendarjs/test-app.js"></script> 
</head>
<body>
	    <div style="display:none;">
			    <div id="app-header-content"></div>
		 </div>
        <span id="msg-div" class="x-hidden"></span>

</body>

</html>

