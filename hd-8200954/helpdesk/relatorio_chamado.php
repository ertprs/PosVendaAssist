<?php
include '../admin/dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
$title = "Relatório de chamados ";
$tv = false;
if (!empty($_GET['tv']) and ($_GET['tv'] == 'true')) {
    $tv = true;
}
if (false === $tv) {
    include "menu.php";
    $refresh_content = '90';
} else {
    echo '<html>
            <head>
                <title>Relatório de chamados</title>
                <link type="text/css" rel="stylesheet" href="css/css.css" />
                <link type="text/css" rel="stylesheet" href="css/menu.css" />
                <link type="text/css" rel="stylesheet" href="css/styles_navigation.css" />
                <link href="../imagens/tc_2009.ico" rel="shortcut icon" />
                <head>
            <body>
        ';
    $refresh_content = '300; url=adm_painel.php';
}
$meta_refresh = '';
//or empty($_POST["acao"])
if ($_POST["acao"] == "pesquisar" )
{
	$relatorio         = $_POST["relatorio"];
	$tipo              = "todos";
	$status 		   = "status";
	$tipo_filtro	   = $_POST["tipo_filtro"];
	$atendente_suporte = $_POST["atendente_suporte"];
	$analista          = $_POST["analista"];
	$desenvolvedor     = $_POST["desenvolvedor"];
	$fabrica           = $_POST["fabrica"];
	$chamado	       = $_POST["chamado"];
	$tipo_resultado    = "normal";
	$tipo_data 		   = $_POST["data"];
	if (empty($_POST['acao'])) {
		$relatorio      = 'mensal';
		$tipo           = 'todos';
		$tipo_resultado = 'postit';
		$meta_refresh = '<meta http-equiv="Refresh" content="' . $refresh_content . '">';
	}	
	switch ($relatorio)
	{
		case "semanal":
				$data_inicial = $_POST["startDate"];
				$data_final   = $_POST["endDate"];
				if (strlen($data_inicial) == 0 or strlen($data_final) == 0)
				{
					$msg_erro .= "Data Inválida <br />";
				}
				if(strlen($msg_erro) == 0)
				{
					list($di, $mi, $yi) = explode("/", $data_inicial);
					if(!checkdate($mi,$di,$yi))
						$msg_erro .= "Data Inicial Inválida <br />";
				}
				if(strlen($msg_erro) == 0)
				{
					list($df, $mf, $yf) = explode("/", $data_final);
					if(!checkdate($mf,$df,$yf))
						$msg_erro = "Data Final Inválida <br />";
				}
				if(strlen($msg_erro) == 0)
				{
					$aux_data_inicial = "$yi-$mi-$di";
					$aux_data_final   = "$yf-$mf-$df";
				}
		break;
		case "mensal":
				$data_mes = $_POST["mes"];
				$data_ano = $_POST["ano"];				
				if (empty($_POST['acao'])) {
					date_default_timezone_set('America/Sao_Paulo');
					$data_mes = (int) date('m');
					$data_ano = date('Y');
					$mes = $data_mes;
					$ano = $data_ano;
				}
				if (strlen($ano) == 0 && empty($chamado))
				{
					$msg_erro .= "Selecione um ano <br />";
				}
				if (strlen($mes) == 0 && empty($chamado))
				{
					$msg_erro .= "Selecione um mês <br />";
				}
		break;
	}
	if($tipo_data == "aberto"){
		$campo = "tbl_hd_chamado.data";
	}elseif($tipo_data == "resolvido"){
		$campo = "tbl_hd_chamado.resolvido";
	}elseif($tipo_data == "todos"){
		$campo = "tbl_hd_chamado.hd_chamado";		
	}
	if (empty($msg_erro))
	{
		switch ($relatorio)		
		{			
			case "semanal":
					$where = "AND $campo::date BETWEEN '$aux_data_inicial' AND '$aux_data_final' ";
					$order = "ORDER BY tbl_hd_chamado.hd_chamado";
			break;
			case "mensal":
					if ($tipo_data <> "todos") {
						$select = ", EXTRACT(WEEK FROM $campo) AS semana";						
						$where = "AND DATE_TRUNC('month', $campo) = DATE('$ano-$mes-01') ";
						$order = "ORDER BY EXTRACT(WEEK FROM $campo), tbl_hd_chamado.hd_chamado";
						if ($tipo_data == "aberto") {
							$where = "AND DATE_TRUNC('month', $campo) = DATE('$ano-$mes-01') AND (status != 'Resolvido') ";
						}
					}
			break;
		}
		if (!empty($tipo_filtro))
		{
			$where .= "AND tbl_tipo_chamado.descricao = '$tipo_filtro'";
		}
		if (strlen($atendente_suporte) > 0)
		{
			$where .= "AND tbl_backlog_item.suporte = $atendente_suporte ";
		}
		if (strlen($analista) > 0)
		{
			$where .= "AND tbl_backlog_item.analista = $analista ";
		}
		if (strlen($desenvolvedor) > 0)
		{
			$where .= " AND tbl_backlog_item.desenvolvedor = $desenvolvedor ";
		}
		if (strlen($fabrica) > 0)
		{
			$where .= " AND tbl_hd_chamado.fabrica = $fabrica ";
		}
		if ($tipo_data == "todos")
		{
			$aux_data_inicial = "$data_ano-$data_mes-01";
			$aux_data_final = date("$data_ano-$data_mes-t");
			$where .= " AND (tbl_hd_chamado.data BETWEEN '$aux_data_inicial' and '$aux_data_final' OR tbl_hd_chamado.resolvido BETWEEN '$aux_data_inicial' and '$aux_data_final') ";
		}
		if (strlen($tipo) > 0)
		{
			if ($tipo == "erro")
			{
				$where .= " AND tbl_hd_chamado.tipo_chamado = 5 ";
			}
			else if ($tipo == "alteracao")
			{
				$where .= " AND tbl_hd_chamado.tipo_chamado IN (1, 2, 3, 4, 6, 7) ";
			}
		}				
		if(!empty($chamado))
		{	
			$where = " AND tbl_hd_chamado.hd_chamado = $chamado ";
			$sql = "SELECT
					DISTINCT
						tbl_hd_chamado.hd_chamado,
						tbl_hd_chamado.status,
						tbl_hd_chamado.titulo,
						tbl_hd_chamado.hora_desenvolvimento,					
						to_char(tbl_hd_chamado.data, 'DD/MM/YYYY') as hd_chamado_data,
						to_char(tbl_hd_chamado.resolvido, 'DD/MM/YYYY') as hd_chamado_resolvido,					
						tbl_fabrica.nome,
						tbl_hd_chamado.prazo_horas,
						tbl_hd_chamado.horas_teste,
						tbl_hd_chamado.horas_analise,
						tbl_hd_chamado.horas_desenvolvimento,
						tbl_hd_chamado.horas_suporte,
						tbl_hd_chamado.valor_desconto,
						tbl_tipo_chamado.tipo_chamado,
						tbl_tipo_chamado.descricao,							
						tbl_hd_franquia.valor_hora_franqueada,									
						(
							select
								case when horas_analisadas isnull then 0 else horas_analisadas end||'|'||case when analista isnull then 0 else analista end||'|'||case when desenvolvedor isnull then 0 else desenvolvedor end||'|'||case when suporte isnull then 0 else suporte end
							from
								tbl_backlog_item bi
							where
								bi.hd_chamado = tbl_hd_chamado.hd_chamado
							order by
								backlog asc
							limit 1
						) as backlog_item,
						TO_CHAR(tbl_hd_chamado.resolvido,'DD/MM/YYYY') AS resolvido
						$select
				FROM
					tbl_hd_chamado
				LEFT JOIN
					tbl_backlog_item ON tbl_backlog_item.hd_chamado = tbl_hd_chamado.hd_chamado
				JOIN
					tbl_tipo_chamado ON tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
				JOIN
					tbl_fabrica ON tbl_fabrica.fabrica = tbl_hd_chamado.fabrica
				JOIN
					tbl_hd_franquia ON tbl_hd_franquia.fabrica = tbl_hd_chamado.fabrica_responsavel					
				WHERE fabrica_responsavel = $login_fabrica
					$where									
					$order";			
		} else {
		$sql = "SELECT
					DISTINCT
						tbl_hd_chamado.hd_chamado,
						tbl_hd_chamado.status,
						tbl_hd_chamado.titulo,
						tbl_hd_chamado.hora_desenvolvimento,					
						to_char(tbl_hd_chamado.data, 'DD/MM/YYYY') as hd_chamado_data,
						to_char(tbl_hd_chamado.resolvido, 'DD/MM/YYYY') as hd_chamado_resolvido,					
						tbl_fabrica.nome,
						tbl_hd_chamado.prazo_horas,
						tbl_hd_chamado.horas_teste,
						tbl_hd_chamado.horas_analise,
						tbl_hd_chamado.horas_desenvolvimento,
						tbl_hd_chamado.horas_suporte,
						tbl_hd_chamado.valor_desconto,
						tbl_tipo_chamado.tipo_chamado,
						tbl_tipo_chamado.descricao,							
						tbl_hd_franquia.valor_hora_franqueada,									
						(
							select
								case when horas_analisadas isnull then 0 else horas_analisadas end||'|'||case when analista isnull then 0 else analista end||'|'||case when desenvolvedor isnull then 0 else desenvolvedor end||'|'||case when suporte isnull then 0 else suporte end
							from
								tbl_backlog_item bi
							where
								bi.hd_chamado = tbl_hd_chamado.hd_chamado
							order by
								backlog asc
							limit 1
						) as backlog_item,
						TO_CHAR(tbl_hd_chamado.resolvido,'DD/MM/YYYY') AS resolvido
						$select
				FROM
					tbl_hd_chamado
				LEFT JOIN
					tbl_backlog_item ON tbl_backlog_item.hd_chamado = tbl_hd_chamado.hd_chamado
				JOIN
					tbl_tipo_chamado ON tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
				JOIN
					tbl_fabrica ON tbl_fabrica.fabrica = tbl_hd_chamado.fabrica
				JOIN
					tbl_hd_franquia ON tbl_hd_franquia.fabrica = tbl_hd_chamado.fabrica AND tbl_hd_franquia.mes = '$mes' AND tbl_hd_franquia.ano = $ano
				WHERE fabrica_responsavel = $login_fabrica
					$where
				$order";			
		}
		$resultado = $sql;			
		//echo $sql. "<br><Br><Br><br>";		

		$resx = pg_query($con, $sql);
	
		if (pg_num_rows($resx) == 0)			
		{			
			$msg_erro = "Nenhum Chamado Encontrado <br />";
		}
		else
		{
			if ($relatorio == "mensal" and $tipo == "todos")				
			{				
				$sqlM = "SELECT
							EXTRACT(WEEK FROM tbl_hd_chamado.*) AS semana
						FROM
							tbl_backlog_item
						JOIN
							tbl_hd_chamado
							ON
								tbl_hd_chamado.hd_chamado = tbl_backlog_item.hd_chamado
						JOIN
							tbl_tipo_chamado
							ON
								tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
						JOIN
							tbl_fabrica
							ON
								tbl_fabrica.fabrica = tbl_hd_chamado.fabrica
						WHERE fabrica_responsavel = $login_fabrica
							$where
						GROUP BY
							EXTRACT(WEEK FROM $campo)
						ORDER BY
							EXTRACT(WEEK FROM $campo)";
				//echo "<br><br><br>".$sqlM;				
				$resM = pg_query($con, $sqlM);					
				for ($i = 0; $i < pg_num_rows($resM); $i++)
				{
					$QtdeSemanas[] = pg_result($resM, $i, "semana");
				}
			}
		}
	}
}
function calc_horas($admin, $hd_chamado)
	{
		global $con;
		if(!empty($admin)) {
				$cond = "	AND tbl_hd_chamado_atendente.admin = $admin ";
		}
		$sqlH = "SELECT
					EXTRACT(EPOCH FROM SUM( CASE WHEN data_termino is null THEN CURRENT_TIMESTAMP ELSE data_termino END - data_inicio ))/3600 AS horas_chamado
				FROM
					tbl_backlog_item
				JOIN
					tbl_hd_chamado
					ON
						tbl_hd_chamado.hd_chamado = tbl_backlog_item.hd_chamado
				LEFT JOIN
					tbl_hd_chamado_atendente
					ON
						tbl_hd_chamado_atendente.hd_chamado = tbl_backlog_item.hd_chamado
				JOIN
					tbl_fabrica
					ON
						tbl_fabrica.fabrica = tbl_hd_chamado.fabrica
				WHERE UPPER(tbl_hd_chamado.status) = 'RESOLVIDO'
				AND tbl_hd_chamado_atendente.hd_chamado = $hd_chamado
				$cond
				GROUP BY
					tbl_backlog_item.backlog_item, tbl_hd_chamado_atendente.hd_chamado
				ORDER BY
					tbl_hd_chamado_atendente.hd_chamado";
		$resH = pg_query($con, $sqlH);
		$horas_chamado = pg_result($resH, 0, "horas_chamado");
		if (strlen($horas_chamado) > 0)
		{
			list($h, $m) = explode(".", $horas_chamado);
			$min = ($horas_chamado - $h) * 60;
			list($m, $s) = explode(".", $min);
			if (strlen($m) == 1)
			{
				$m = "0$m";
			}
			//$horas_chamado = "$h.$m";
			$horas_chamado = "$h";
			return $horas_chamado;
		}
		else
		{
			return "0";
		}
	}
	function con_admin($admin)
	{
		global $con;
		$sql = "SELECT nome_completo, login FROM tbl_admin WHERE admin = $admin";
		$res = pg_query($con, $sql);
		if (pg_num_rows($res) > 0)
		{
			$nome = pg_result($res, 0, "nome_completo");
			if (strlen($nome) > 0)
			{
				return $nome;
			}
			else
			{
				$login = pg_result($res, 0, "login");
				if (strlen($login) > 0)
				{
					return $login;
				}
				else
				{
					return "";
				}
			}
		}
		else
		{
			return "";
		}
	}
	function calc_week($day,$month,$year)
	{
		return ceil(($day + date("w",mktime(0,0,0,$month,1,$year)))/7);
	}
$mesArray[1] = "Janeiro";
$mesArray[2] = "Fevereiro";
$mesArray[3] = "Março";
$mesArray[4] = "Abril";
$mesArray[5] = "Maio";
$mesArray[6] = "Junho";
$mesArray[7] = "Julho";
$mesArray[8] = "Agosto";
$mesArray[9] = "Setembro";
$mesArray[10] = "Outubro";
$mesArray[11] = "Novembro";
$mesArray[12] = "Dezembro";
echo $meta_refresh;
?>
<link href="js/js_custom/themes/custom-theme/jquery.ui.all.css" rel="stylesheet" />
<link href="js/js_custom/themes/custom-theme/jquery-ui-1.8.18.custom.css" rel="stylesheet" media="all"/>
<link href="../plugins/jquery/jpaginate/css/style.css" rel="stylesheet" />

<?php
if ($tipo_resultado == "postit") {
	echo '<link rel="stylesheet" href="css/backlog_postit.css" type="text/css" />';
}
?>

<style>
	.titulo_pagina
	{
		color: #3B3E63;
		font-size: 16px;
		border: 0;
		width: 780px;
		margin: 0 auto;
		margin-top: 14px;
		font-weight: bold;
		text-align: center;
	}
	.center
	{
		margin: 0 auto;
		text-align: center;
	}
	#tbl_pesquisa
	{
		border: 0px;
		width: 820px;
		margin: 0 auto;
		margin-top: 20px;
		margin-bottom: 14px;
		border-collapse: collapse;
	}
	.th_top
	{
		color: #FFF;
		font-size: 12px;
		-webkit-border-radius: 12px 12px 0px 0px;
		-moz-border-radius: 12px 12px 0px 0px;
		-o-border-radius: 12px 12px 0px 0px;
		-ms-border-radius: 12px 12px 0px 0px;
		border-radius: 12px 12px 0px 0px;
		padding: 4px;
		background-color: rgb(59,62,99);
		background-image: linear-gradient(top, rgb(80,81,126) 0%, rgb(59,62,99) 100%);
		background-image: -o-linear-gradient(top, rgb(80,81,126) 0%, rgb(59,62,99) 100%);
		background-image: -moz-linear-gradient(top, rgb(80,81,126) 0%, rgb(59,62,99) 100%);
		background-image: -webkit-linear-gradient(top, rgb(80,81,126) 0%, rgb(59,62,99) 100%);
		background-image: -ms-linear-gradient(top, rgb(80,81,126) 0%, rgb(59,62,99) 100%);
		background-image: -webkit-gradient(
		linear,
		left top,
		left bottom,
		color-stop(0, rgb(80,81,126)),
		color-stop(1, rgb(59,62,99))
		);
		filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#50517E', endColorstr='#3B3E63', GradientType=0);
	}
	.th_bottom
	{
		color: #FFF;
		font-size: 12px;
		-webkit-border-radius: 0px 0px 12px 12px;
		-moz-border-radius: 0px 0px 12px 12px;
		-o-border-radius: 0px 0px 12px 12px;
		-ms-border-radius: 0px 0px 12px 12px;
		border-radius: 0px 0px 12px 12px;
		padding: 4px;
		background-color: rgb(59,62,99);
		background-image: linear-gradient(bottom, rgb(80,81,126) 0%, rgb(59,62,99) 100%);
		background-image: -o-linear-gradient(bottom, rgb(80,81,126) 0%, rgb(59,62,99) 100%);
		background-image: -moz-linear-gradient(bottom, rgb(80,81,126) 0%, rgb(59,62,99) 100%);
		background-image: -webkit-linear-gradient(bottom, rgb(80,81,126) 0%, rgb(59,62,99) 100%);
		background-image: -ms-linear-gradient(bottom, rgb(80,81,126) 0%, rgb(59,62,99) 100%);
		background-image: -webkit-gradient(
		linear,
		left bottom,
		left top,
		color-stop(0, rgb(80,81,126)),
		color-stop(1, rgb(59,62,99))
		);
		filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#50517E', endColorstr='#3B3E63', GradientType=0);
	}
	#tbl_pesquisa td
	{
		color: #000;
		font-size: 12px;
		background-color: #eeeeee;
		font-weight: bold;		
	}
	input[type=text]
	{
		height: 22px;
		border: 1px #666666 solid;
		background-color: #fdfdfd;
		color: #3B3E63;
	}
	select
	{
		width: 180px;
		height: 22px;
		border: 1px #666666 solid;
		background-color: #fdfdfd;
		color: #3B3E63;
	}
	option
	{
		height: 22px;
		border: 1px #585C94 solid;
		background-color: #3B3E63;
		color: #fff;
	}
	#botao a
	{
		text-decoration: none;
		color: #3B3E63;
		font-size: 11px;
	}
	#botao a:hover
	{
		text-decoration: none;
		color: #3B3E63;
		font-size: 11px;
	}
	.btn
	{
		position:relative;
		padding:10px 10px;
		padding-right:40px;
		background-color: rgb(229,229,229);
		background-image: linear-gradient(bottom, rgb(202,202,202) 0%, rgb(229,229,229) 100%);
		background-image: -o-linear-gradient(bottom, rgb(202,202,202) 0%, rgb(229,229,229) 100%);
		background-image: -moz-linear-gradient(bottom, rgb(202,202,202) 0%, rgb(229,229,229) 100%);
		background-image: -webkit-linear-gradient(bottom, rgb(202,202,202) 0%, rgb(229,229,229) 100%);
		background-image: -ms-linear-gradient(bottom, rgb(202,202,202) 0%, rgb(229,229,229) 100%);
		background-image: -webkit-gradient(
		linear,
		left bottom,
		left top,
		color-stop(0, rgb(202,202,202)),
		color-stop(1, rgb(229,229,229))
		);
		filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#CACACA', endColorstr='#E5E5E5', GradientType=0);
		-webkit-border-radius: 5px;
		-moz-border-radius: 5px;
		-o-border-radius: 5px;
		border-radius: 5px;
		-webkit-box-shadow: inset 0px 1px 0px #ECECEC, 0px 5px 0px 0px #858585, 0px 10px 5px #2E2E2E;
		-moz-box-shadow: inset 0px 1px 0px #ECECEC, 0px 5px 0px 0px #858585, 0px 10px 5px #2E2E2E;
		-o-box-shadow: inset 0px 1px 0px #ECECEC, 0px 5px 0px 0px #858585, 0px 10px 5px #2E2E2E;
		box-shadow: inset 0px 1px 0px #ECECEC, 0px 5px 0px 0px #858585, 0px 10px 5px #2E2E2E;
	}
	.btn:active
	{
		top:3px;
		background-image: linear-gradient(bottom, rgb(229,229,229) 0%, rgb(202,202,202) 100%);
		background-image: -o-linear-gradient(bottom, rgb(229,229,229) 0%, rgb(202,202,202) 100%);
		background-image: -moz-linear-gradient(bottom, rgb(229,229,229) 0%, rgb(202,202,202) 100%);
		background-image: -webkit-linear-gradient(bottom, rgb(229,229,229) 0%, rgb(202,202,202) 100%);
		background-image: -ms-linear-gradient(bottom, rgb(229,229,229) 0%, rgb(202,202,202) 100%);
		background-image: -webkit-gradient(
		linear,
		left bottom,
		left top,
		color-stop(0, rgb(229,229,229)),
		color-stop(1, rgb(202,202,202))
		);
		filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#CACACA', endColorstr='#E5E5E5', GradientType=0);
		-webkit-box-shadow: inset 0px 1px 0px #ECECEC, 0px 2px 0px 0px #858585, 0px 5px 3px #2E2E2E;
		-moz-box-shadow: inset 0px 1px 0px #ECECEC, 0px 2px 0px 0px #858585, 0px 5px 3px #2E2E2E;
		-o-box-shadow: inset 0px 1px 0px #ECECEC, 0px 2px 0px 0px #858585, 0px 5px 3px #2E2E2E;
		box-shadow: inset 0px 1px 0px #ECECEC, 0px 2px 0px 0px #858585, 0px 5px 3px #2E2E2E;
	}
	.btn::before
	{
		background-color:#B4B4B4;
		background-image:url(imagens/right_arrow.png);
		background-repeat:no-repeat;
		background-position:center center;
		content:"";
		width:20px;
		height:20px;
		position:absolute;
		right:15px;
		top:50%;
		margin-top:-9px;
		-webkit-border-radius: 50%;
		-moz-border-radius: 50%;
		-o-border-radius: 50%;
		border-radius: 50%;
		-webkit-box-shadow: inset 0px 1px 0px #565656, 0px 1px 0px #F0F0F0;
		-moz-box-shadow: inset 0px 1px 0px #565656, 0px 1px 0px #F0F0F0;
		-o-box-shadow: inset 0px 1px 0px #565656, 0px 1px 0px #F0F0F0;
		box-shadow: inset 0px 1px 0px #565656, 0px 1px 0px #F0F0F0;
	}
	.btn:active::before
	{
		top:50%;
		margin-top:-12px;
		-webkit-box-shadow: inset 0px 1px 0px #F0F0F0, 0px 3px 0px #717171, 0px 6px 3px #A6A6A6;
		-moz-box-shadow: inset 0px 1px 0px #F0F0F0, 0px 3px 0px #717171, 0px 6px 3px #A6A6A6;
		-o-box-shadow: inset 0px 1px 0px #F0F0F0, 0px 3px 0px #717171, 0px 6px 3px #A6A6A6;
		box-shadow: inset 0px 1px 0px #F0F0F0, 0px 3px 0px #717171, 0px 6px 3px #A6A6A6;
	}
	.ui-datepicker select.ui-datepicker-month, .ui-datepicker select.ui-datepicker-year
	{
		width: 80px;
	}
	.ui-datepicker
	{
		width: 320px;
	}
	.ui-button-text
	{
		font-size: 10px;
	}
	#data_semanal
	{
		margin-left: 10px;
		margin-top: 20px;
	}
	#data_semanal #di_df
	{
		background-color: #3B3E63;
		color: #fff;
		font-size: 14px;
		padding: 4px 4px 4px 4px;
		-webkit-border-radius: 6px 6px 6px 6px;
		-moz-border-radius: 6px 6px 6px 6px;
		-o-border-radius: 6px 6px 6px 6px;
		-ms-border-radius: 6px 6px 6px 6px;
		border-radius: 6px 6px 6px 6px;
		width: 320px;
		font-weight: normal;
	}
	#data_semanal #startDate, #data_semanal #endDate
	{
		color: #EBC70B;
	}
	#data_mensal
	{
		padding-left: 10px;
	}
	.msg_erro
	{
		width: 940px;
		background-color:#FF0000;
		font: bold 14px "Arial";
		color:#FFFFFF;
		padding: 5px 0px 5px 0px;
		text-align:center;
		-webkit-border-radius: 12px 12px 12px 12px;
		-moz-border-radius: 12px 12px 12px 12px;
		-o-border-radius: 12px 12px 12px 12px;
		-ms-border-radius: 12px 12px 12px 12px;
		border-radius: 12px 12px 12px 12px;
		background-image: linear-gradient(bottom, rgb(237,0,0) 0%, rgb(231,81,81) 100%);
		background-image: -o-linear-gradient(bottom, rgb(237,0,0) 0%, rgb(231,81,81) 100%);
		background-image: -moz-linear-gradient(bottom, rgb(237,0,0) 0%, rgb(231,81,81) 100%);
		background-image: -webkit-linear-gradient(bottom, rgb(237,0,0) 0%, rgb(231,81,81) 100%);
		background-image: -ms-linear-gradient(bottom, rgb(237,0,0) 0%, rgb(231,81,81) 100%);
		background-image: -webkit-gradient(
		linear,
		left bottom,
		left top,
		color-stop(0, rgb(237,0,0)),
		color-stop(1, rgb(231,81,81))
		);
	}
	#tbl_pesquisa_resultado
	{
		width: 1100px;
		margin: 0 auto;
		border-collapse: collapse;
	}
	#tbl_pesquisa_resultado td, #tbl_pesquisa_resultado th
	{
		padding: 2px;
	}
	.pesquisa_titulo
	{
		color: #FFF;
		font-size: 13px;
		background-color: rgb(59,62,99);
	}
	.pesquisa_titulo_red
	{
		color: #FFF;
		font-size: 13px;
		background-color: rgb(237,0,0);
	}
	.pesquisa_titulo_green
	{
		color: #FFF;
		font-size: 13px;
		background-color: rgb(0,142,0);
	}
	.pesquisa_subtitulo
	{
		color: rgb(59,62,99);
		font-size: 13px;
		font-weight: bold;
		background-color: rgb(255,255,255);
	}
	.pesquisa_dados
	{
		color: rgb(0,0,0);
		font-size: 12px;
		text-align: center;
		background-color: 238,238,238;
	}
	#totais
	{
		width: 620px;
		margin: 0 auto;
		border: 0;
		font-size: 11px;
		font-weight: bold;
	}
	#excel
	{
		margin: 0 auto;
		text-align: center;
	}
	#excel a
	{
		text-decoration: none;
		font-size: 13px;
	}
	#csv
	{
		margin: 0 auto;
		text-align: center;
	}
	#csv a
	{
		text-decoration: none;
		font-size: 13px;
	}
	.ocultar {
		display: none;
	}
</style>

<script src="js/js_custom/jquery-1.7.1.js"></script>
<script src="js/js_custom/ui/jquery-ui-1.8.18.custom.js"></script>
<script src="js/js_custom/ui/jquery.ui.core.js"></script>
<script src="js/js_custom/ui/jquery.ui.widget.js"></script>
<script src="js/js_custom/ui/jquery.ui.button.js"></script>
<script src="js/js_custom/ui/jquery.ui.datepicker.js"></script>
<script src="../plugins/jquery/jpaginate/jquery.paginate.js"></script>
<script src="../js/jquery.maskedinput2.js"></script>
<script>	
	function abreChamado(chamado) {
		window.open('adm_chamado_detalhe.php?hd_chamado=' + chamado);
	}
	jQuery(function($){
		$.datepicker.regional['pt-BR'] = {
			closeText: 'Fechar',
			prevText: '&#x3c;Anterior',
			nextText: 'Pr&oacute;ximo&#x3e;',
			currentText: 'Hoje',
			monthNames: ['Janeiro','Fevereiro','Mar&ccedil;o','Abril','Maio','Junho',
			'Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'],
			monthNamesShort: ['Jan','Fev','Mar','Abr','Mai','Jun',
			'Jul','Ago','Set','Out','Nov','Dez'],
			dayNames: ['Domingo','Segunda-feira','Ter&ccedil;a-feira','Quarta-feira','Quinta-feira','Sexta-feira','Sabado'],
			dayNamesShort: ['Dom','Seg','Ter','Qua','Qui','Sex','Sab'],
			dayNamesMin: ['Dom','Seg','Ter','Qua','Qui','Sex','Sab'],
			weekHeader: 'Sm',
			dateFormat: 'dd/mm/yy',
			firstDay: 0,
			isRTL: false,
			showMonthAfterYear: false,
			yearSuffix: ''};
		$.datepicker.setDefaults($.datepicker.regional['pt-BR']);
	});
	$(function() {
		var startDate;
		var endDate;
		var selectCurrentWeek = function() {
			window.setTimeout(function () {
				$('.week-picker').find('.ui-datepicker-current-day a').addClass('ui-state-active')
			}, 1);
		}
		$('.week-picker').datepicker( {
			showOtherMonths: true,
			selectOtherMonths: true,
			onSelect: function(dateText, inst) {
				var date = $(this).datepicker('getDate');
				startDate = new Date(date.getFullYear(), date.getMonth(), date.getDate() - date.getDay());
				endDate = new Date(date.getFullYear(), date.getMonth(), date.getDate() - date.getDay() + 6);
				var dateFormat = inst.settings.dateFormat || $.datepicker._defaults.dateFormat;
				$('#startDate').html($.datepicker.formatDate( dateFormat, startDate, inst.settings ));
				$('#endDate').html($.datepicker.formatDate( dateFormat, endDate, inst.settings ));
				$('input[name=startDate]').val($.datepicker.formatDate( dateFormat, startDate, inst.settings ));
				$('input[name=endDate]').val($.datepicker.formatDate( dateFormat, endDate, inst.settings ));
				selectCurrentWeek();
			},
			beforeShowDay: function(date) {
				var cssClass = '';
				if(date >= startDate && date <= endDate)
					cssClass = 'ui-datepicker-current-day';
				return [true, cssClass];
			},
			onChangeMonthYear: function(year, month, inst) {
				selectCurrentWeek();
			}
		});
		$('.week-picker .ui-datepicker-calendar tr').live('mousemove', function() { $(this).find('td a').addClass('ui-state-hover'); });
		$('.week-picker .ui-datepicker-calendar tr').live('mouseleave', function() { $(this).find('td a').removeClass('ui-state-hover'); });
		$("#tipo_relatorio").buttonset();
		$("label[for^=tipo_relatorio]").attr("style","width: 80px; font-size: 12px; text-align: center;");
		$("#tipo_data").buttonset();
		$("label[for^=tipo_data]").attr("style","width: 80px; font-size: 12px; text-align: center;");
		$("#tipo_chamado").buttonset();
		$("label[for^=tipo_chamado]").attr("style","width: 80px; font-size: 12px; text-align: center;");
		$("#tipo_resultado").buttonset();
		$("label[for^=tipo_resultado]").attr("style","width: 80px; font-size: 12px; text-align: center;");
		$("input[name=relatorio]").change(function () {
			var valor = $(this).val();
			if (valor == "semanal")
			{
				$("#data_mensal").hide("slow");
				$("#data_semanal").show("slow");
			}
			if (valor == "mensal")
			{
				$("#data_semanal").hide("slow");
				$("#data_mensal").show("slow");
			}
		});
		var relatorio = "<?=$relatorio?>";
		if (relatorio == "semanal")
		{
			$("#data_semanal").show("slow");
		}
		if (relatorio == "mensal" || relatorio == "")
		{
			$("#data_mensal").show("slow");
		}
		$("input[name=data]").change(function () {
			var valor = $(this).val();
			if (valor == "aberto")
			{
				$("#tipo_data_2").hide("slow");
				$("#tipo_data_1").show("slow");
				$("#tipo_data_3").hide("slow");
			}
			if (valor == "resolvido")
			{
				$("#tipo_data_1").hide("slow");
				$("#tipo_data_2").show("slow");
				$("#tipo_data_3").hide("slow");
			}
			if (valor == "todos")
			{
				$("#tipo_data_1").hide("slow");
				$("#tipo_data_2").hide("slow");
				$("#tipo_data_3").show("slow");
			}
		});
		var data = "<?=$tipo_data?>";
		if (data == "aberto")
		{
			$("#tipo_data_1").show("slow");
		}
		if (data == "resolvido" || data == "")
		{
			$("#tipo_data_2").show("slow");
		}
		if (data == "todos" || data == "")
		{
			$("#tipo_data_3").show("slow");
		}
	});
</script>

<?php if (false === $tv): ?>

<form id="frm_pesquisa" method="POST">

	<div class="titulo_pagina">
		Relatório de Chamados
	</div>

	<?
	if (strlen($msg_erro) > 0)
	{
	?>		
		<div class="msg_erro" style="margin: 0 auto; margin-top: 20px;">
			<?
			echo $msg_erro;
			?>
		</div>
	<?
	}
	?>

	<div class="center">		
		<table id="tbl_pesquisa" border="0">
			<tr>
				<th class="th_top" colspan="6">
					Parâmetros de Pesquisa
				</th>
			</tr>
			<tr>
				<td colspan="6" style="text-align: center; font-weight: bold; padding-top: 20px;">
					<div id="tipo_relatorio" class="ocultar">
						<input type="radio" name="relatorio" id="tipo_relatorio_2" value="mensal" <?if ($relatorio == "mensal" or empty($relatorio)) echo "CHECKED";?> />
						<label for="tipo_relatorio_2">Mensal</label>
					</div>
				</td>
			</tr>
			<tr>
				<td colspan="6" style="text-align: center; font-weight: bold; padding-top: 20px;">
					<div id="tipo_data">
						<input type="radio" name="data" id="tipo_data_1" value="aberto" <?if ($tipo_data == "aberto") echo "CHECKED";?> />
						<label for="tipo_data_1">Aberto</label>
						<input type="radio" name="data" id="tipo_data_2" value="resolvido" <?if ($tipo_data == "resolvido" or empty($data)) echo "CHECKED";?> />
						<label for="tipo_data_2">Resolvido</label>
						<input type="radio" name="data" id="tipo_data_3" value="todos" <?if ($tipo_data == "todos" or empty($data)) echo "CHECKED";?> />
						<label for="tipo_data_3">Todos</label>
					</div>
				</td>
			</tr>
			<tr>				
				<td colspan="6" style="text-align: center; padding: 10px 0px 10px 0px;">
					<div id="data_semanal" style="display: none;">
						<div class="week-picker"></div>
						<div id="di_df">
							<label>Data Inicial:&nbsp;</label><span id="startDate"><?=$data_inicial?></span>
							<input type="hidden" name="startDate" value="<?=$data_inicial?>" />
							&nbsp;&nbsp;&nbsp;
							<label>Data Final:&nbsp;</label><span id="endDate"><?=$data_final?></span>
							<input type="hidden" name="endDate" value="<?=$data_final?>" />
						</div>
					</div>
				</td>
			</tr>
			<tr>				
				<td colspan="6">
					<div class="center" id="data_mensal">
						<div style="width: 480px; float: left;">
							Mês
							<br />
							<select name="mes">
								<option value=""></option>
								<?
								$i = 1;
								foreach ($mesArray as $mes)
								{
									if ($data_mes == $i)
									{
										$selected = "selected";
									}
									else
									{
										$selected = "";
									}
									echo "<option value='$i' $selected>$mes</option>";
									$i++;
								}
								?>
							</select>
						</div>					
						<div style="width: 10px; float: left;">
							Ano
							<br />
							<select name="ano">
								<?
								$ano_atual = Date("Y");
								for ($ano = $ano_atual; $ano >= 2000; $ano--)
								{
									if ($data_ano == $ano)
									{
										$selected = "SELECTED";
									}
									else
									{
										$selected = "";
									}
									echo "<option value='$ano' $selected>$ano</option>";
								}
								?>
							</select>
						</div>
					</div>
				</td>							
			</tr>
			<tr>
				<td colspan="6">&nbsp;</td>				
			</tr>
			<tr>
				<td style="text-align: center; padding: 10px 0px 10px 0px;">
					Tipo
					<br />
					<select name="tipo_filtro">
						<option value=""></option>
						<?
						$sql = "SELECT
									tbl_tipo_chamado.descricao									
								FROM
									tbl_tipo_chamado							
								ORDER BY
									tbl_tipo_chamado.descricao";
						$res = pg_query($con, $sql);
						if (pg_num_rows($res) > 0)
						{
							for ($i = 0; $i < pg_num_rows($res); $i++)
							{
								$tipo_filtro = pg_result($res, $i, "descricao");			
								echo "<option value='$tipo_filtro' $selected>";
								echo $tipo_filtro;
								echo "</option>";
							}
						}
						?>
					</select>
				</td>
				<td style="text-align: center; padding: 10px 0px 10px 0px;">
					Suporte
					<br />
					<select name="atendente_suporte">
						<option value=""></option>
						<?
						$sql = "SELECT
									tbl_admin.admin,
									tbl_admin.nome_completo,
									tbl_admin.login
								FROM
									tbl_admin
								JOIN
									tbl_grupo_admin
									ON
										tbl_grupo_admin.grupo_admin = tbl_admin.grupo_admin
								WHERE
									tbl_admin.fabrica = 10
									AND
										tbl_admin.ativo = TRUE
									AND
										tbl_grupo_admin.grupo_admin = 6
								ORDER BY
									tbl_admin.nome_completo";
						$res = pg_query($con, $sql);
						if (pg_num_rows($res) > 0)
						{
							for ($i = 0; $i < pg_num_rows($res); $i++)
							{
								$admin         = pg_result($res, $i, "admin");
								$nome_completo = pg_result($res, $i, "nome_completo");
								$login         = pg_result($res, $i, "login");
								if ($atendente_suporte == $admin)
								{
									$selected = "selected";
								}
								else
								{
									$selected = "";
								}
								echo "<option value='$admin' $selected>";
									if (strlen($nome_completo) == 0)
									{
										echo $login;
									}
									else
									{
										echo $nome_completo;
									}
								echo "</option>";
							}
						}
						?>
					</select>
				</td>
				<td style="text-align: center; padding: 10px 0px 10px 0px;">
					Analista
					<br />
					<select name="analista">
						<option value=""></option>
						<?
						$sql = "SELECT
									tbl_admin.admin,
									tbl_admin.nome_completo,
									tbl_admin.login
								FROM
									tbl_admin
								JOIN
									tbl_grupo_admin
									ON
										tbl_grupo_admin.grupo_admin = tbl_admin.grupo_admin
								WHERE
									tbl_admin.fabrica = 10
									AND
										tbl_admin.ativo
									AND
										tbl_grupo_admin.grupo_admin in (1,2,7)
									ORDER BY
										tbl_admin.nome_completo";
						$res = pg_query($con, $sql);
						if (pg_num_rows($res) > 0)
						{
							for ($i = 0; $i < pg_num_rows($res); $i++)
							{
								$admin         = pg_result($res, $i, "admin");
								$nome_completo = pg_result($res, $i, "nome_completo");
								$login         = pg_result($res, $i, "login");
								if ($analista == $admin)
								{
									$selected = "selected";
								}
								else
								{
									$selected = "";
								}
								echo "<option value='$admin' $selected>";
									if (strlen($nome_completo) == 0)
									{
										echo $login;
									}
									else
									{
										echo $nome_completo;
									}
								echo "</option>";
							}
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="6">&nbsp;</td>
			</tr>
			<tr>
				<td style="text-align: center; padding: 10px 0px 10px 0px;">
					Fábrica
					<br />
					<select name="fabrica">
						<option value=""></option>
						<?
						$sql = "SELECT
									fabrica,
									nome
								FROM
									tbl_fabrica
								WHERE
									ativo_fabrica = TRUE
								ORDER BY
									nome";
						$res = pg_query($con, $sql);
						if (pg_num_rows($res) > 0)
						{
							for ($i = 0; $i < pg_num_rows($res); $i++)
							{
								$fabrica_id = pg_result($res, $i, "fabrica");
								$nome       = pg_result($res, $i, "nome");
								if ($fabrica == $fabrica_id)
								{
									$selected = "selected";
								}
								else
								{
									$selected = "";
								}
								echo "<option value='$fabrica_id' $selected>$nome</option>";
							}
						}
						?>
					</select>
				</td>
				<td style="text-align: center; padding: 10px 0px 10px 0px;">
					Desenvolvedor
					<br />
					<select name="desenvolvedor">
						<option value=""></option>
						<?
						$sql = "SELECT
									tbl_admin.admin,
									tbl_admin.nome_completo,
									tbl_admin.login
								FROM
									tbl_admin
								JOIN
									tbl_grupo_admin
									ON
										tbl_grupo_admin.grupo_admin = tbl_admin.grupo_admin
								WHERE
									tbl_admin.fabrica = 10
									AND
										tbl_admin.ativo = TRUE
									AND
										tbl_grupo_admin.grupo_admin in (4,2,1)
								ORDER BY
									tbl_admin.nome_completo";
						$res = pg_query($con, $sql);
						if (pg_num_rows($res) > 0)
						{
							for ($i = 0; $i < pg_num_rows($res); $i++)
							{
								$admin         = pg_result($res, $i, "admin");
								$nome_completo = pg_result($res, $i, "nome_completo");
								$login         = pg_result($res, $i, "login");
								if ($desenvolvedor == $admin)
								{
									$selected = "selected";
								}
								else
								{
									$selected = "";
								}
								echo "<option value='$admin' $selected>";
									if (strlen($nome_completo) == 0)
									{
										echo $login;
									}
									else
									{
										echo $nome_completo;
									}
								echo "</option>";
							}
						}
						?>
					</select>
				</td>
				<td style="text-align: center; padding: 10px 0px 10px 0px;">
					Chamado
					<br />
					<input type="text" name="chamado" value="" />
				</td>
			</tr>
			<tr>
				<td colspan="6">&nbsp;</td>
			</tr>
			<tr>
				<th class="th_bottom" colspan="6" style="text-align: center; padding: 20px 0px 30px 0px">
					<input type="hidden" name="acao" value="" />
					<p id="botao" style="cursor: pointer;" onclick="$('input[name=acao]').val('pesquisar'); $('form[id=frm_pesquisa]').submit();">
						<a class="btn">
							Pesquisar
						</a>
					</p>
				</th>
			</tr>
		</table>		
	</div>

</form>

<?php endif ?>

<?
if (pg_num_rows($resx) > 0) {
if ($tipo == "todos" and $relatorio == "mensal") {
		flush();
		echo `rm /tmp/assist/relatorio_backlog.html`;
		$fp = fopen ("/tmp/assist/relatorio_backlog.html","w");		
		$fpcsv = fopen ("/tmp/assist/relatorio_backlog.csv","w+");			
			$x = 0;
			$total_chamados = 0;
			$total_horas_analisadas = 0;
			$total_horas_desenvolvidas = 0;
			$total_suporte = 0 ;
			$totais_horas = 0 ;
			$semana_calc = "";
			for ($i = 0; $i < pg_num_rows($resx); $i++)
			{
				$tipo_chamado = pg_result($resx, $i, "tipo_chamado");
				$semana       = pg_result($resx, $i, "semana");
					$resolvido    = pg_result($resx, $i, "resolvido");
					if (strlen($resolvido) > 0)
					{
						$resolvido    = explode("/", $resolvido);
						$semana_calc = calc_week($resolvido[0],$resolvido[1],$resolvido[2]);
						break;
					}
			}
			echo "<table border='1' id='tbl_pesquisa_resultado'>
					<!--<tr class='pesquisa_titulo_red'>
						<th colspan='100%'>
							Chamados de Erros em Programas
						</th>
					</tr>-->
					<tr class='pesquisa_titulo'>
						<th colspan='100%'>
							 Mês de $mesArray[$data_mes]
						</th>
					</tr>
					<tr class='pesquisa_subtitulo'>
						<th rowspan='2'>Chamado</th>						
						<th rowspan='2'>Fábrica</th>
						<th rowspan='2'>Tipo</th>
						<th rowspan='2'>Status</th>
						<th rowspan='2'>Aberto em</th>
						<th rowspan='2'>Resolvido em</th>
						<th rowspan='2'>Analista</th>
						<th rowspan='2'>Desenvolvedor</th>
						<th rowspan='2'>Suporte</th>
						<th rowspan='2'>Total Hrs. Aprovado</th>
						<th rowspan='2'>Valor Hrs.</th>						
						<th rowspan='2'>Desconto R$</th>
						<th rowspan='2'>Valor Aprovado</th>
						<th colspan='5'>Orçamento</th>
						<th colspan='4'>Trabalhadas</th>
					</tr>
					<tr class='pesquisa_subtitulo'>
						<!-- Orcamento -->
						<th>Hrs. Analis.</th>
						<th>Hrs. Desenvol.</th>
						<th>Hrs. Suporte</th>
						<th>Hrs. Testes</th>
						<th>Hrs. Total</th>
						<!-- Trabalhadas -->
						<th>Hrs. Analis.</th>
						<th>Hrs. Desenvol.</th>
						<th>Hrs. Suporte</th>						
						<th>Hrs. Total</th>";
					echo"</tr>";
			fputs ($fp, "<table border='1'>
							<tr style='color: #fff; font-size: 14px;'>
								<th colspan='100%' style='background-color: rgb(237,0,0);'>
									Chamados de Erros em Programas
								</th>
							</tr>
							<tr style='color: #fff; font-size: 14px;'>
								<th colspan='100%' style='background-color: rgb(59,62,99);'>
									{$semana_calc}ª Semana do Mês de $mesArray[$data_mes]
								</th>
							</tr>
							<tr style='color: rgb(59,62,99); font-size: 14px;'>
								<th style='background-color: rgb(255,255,255);'>Chamado</th>	
								<th style='background-color: rgb(255,255,255);'>Fábrica</th>
								<th style='background-color: rgb(255,255,255);'>Tipo</th>
								<th style='background-color: rgb(255,255,255);'>Status</th>
								<th style='background-color: rgb(255,255,255);'>Aberto em</th>
								<th style='background-color: rgb(255,255,255);'>Resolvido em</th>
								<th style='background-color: rgb(255,255,255);'>Analista</th>
								<th style='background-color: rgb(255,255,255);'>Desenvolvedor</th>
								<th style='background-color: rgb(255,255,255);'>Suporte</th>
								<th style='background-color: rgb(255,255,255);'>Total Hrs. Aprovado</th>
								<th style='background-color: rgb(255,255,255);'>Valor Hrs.</th>
								<th style='background-color: rgb(255,255,255);'>Desconto R$</th>
								<th style='background-color: rgb(255,255,255);'>Valor Aprovado</th>
								<th style='background-color: rgb(255,255,255);'>Horas Analisadas</th>
								<th style='background-color: rgb(255,255,255);'>Horas Desenvolvidas</th>
								<th style='background-color: rgb(255,255,255);'>Horas Suporte</th>
								<th style='background-color: rgb(255,255,255);'>Horas Testes</th>
								<th style='background-color: rgb(255,255,255);'>Horas Total</th>
								<th style='background-color: rgb(255,255,255);'>Horas Analisadas</th>
								<th style='background-color: rgb(255,255,255);'>Horas Desenvolvidas</th>
								<th style='background-color: rgb(255,255,255);'>Horas Suporte</th>
								<th style='background-color: rgb(255,255,255);'>Horas Total</th>
								<th style='background-color: rgb(255,255,255);'>Horas Analisadas</th>
								<th style='background-color: rgb(255,255,255);'>Horas Desenvolvidas</th>
								<th style='background-color: rgb(255,255,255);'>Horas Suporte</th>
								<th style='background-color: rgb(255,255,255);'>Horas Total</th>
							</tr>");


			fputs ($fpcsv, "Chamado;Fábrica;Tipo;Status;Aberto;Resolvido;Analista;Desenvolvedor;Suporte;Total Hts. Aprovado;Valor Hrs;Desconto R$;Valor Aprovado;Horas Analisadas;Horas Desenvolvidas;Horas Suporte;Hrs Teste;Hrs Total;Horas Analisadas;Horas Desenvolvidas;Horas Suporte;Hrs Total;");

			for ($i = 0; $i < pg_num_rows($resx); $i++)
			{				
									$hd_chamado       = pg_result($resx, $i, "hd_chamado");			
									if(!empty($atendente_suporte)) {
										$where_sup = " AND tbl_hd_chamado_atendente.admin = tbl_hd_chamado_item.admin";
									}
									$sql_sup ="SELECT TO_CHAR((EXTRACT(epoch FROM SUM(termino-data_inicio))/60 || ' minutes')::INTERVAL, 'HH24:MI')
												 FROM tbl_hd_chamado_atendente
											     JOIN tbl_admin using(admin)
												 JOIN tbl_hd_chamado_item USING(hd_chamado,admin)
												WHERE tbl_hd_chamado_atendente.hd_chamado = $hd_chamado
												  AND data = data_inicio
												  AND data_inicio NOTNULL
												  AND termino NOTNULL
											      AND grupo_admin=6
											      $where_sup";
									$res_sup = pg_query($con, $sql_sup);									
									if (pg_num_rows($res_sup) > 0) {
										$horas_sup = pg_fetch_result($res_sup, 0, 0);
									}

									if (strlen($horas_sup) == 0) {
										$horas_sup = "00:00";
									} else {
										$xhoras_sup = explode(":",$horas_sup);
										$horas_sup = $xhoras_sup[0].":".$xhoras_sup[1];
										$h_suporte[] = $horas_sup;
									}

									if(!empty($desenvolvedor)) {
										$where_dev = " AND tbl_hd_chamado_atendente.admin = tbl_hd_chamado_item.admin";
									}
									$sql_dev ="SELECT TO_CHAR((EXTRACT(epoch FROM SUM(termino-data_inicio))/60 || ' minutes')::INTERVAL, 'HH24:MI')
											  FROM tbl_hd_chamado_atendente
											  JOIN tbl_admin using(admin)
											  JOIN tbl_hd_chamado_item USING(hd_chamado,admin)
											 WHERE tbl_hd_chamado_atendente.hd_chamado = $hd_chamado
											   AND data = data_inicio
											   AND data_inicio NOTNULL
										 	   AND termino NOTNULL
											   AND grupo_admin=4
											   $where_dev";
									$res_dev = pg_query($con, $sql_dev);
									if (pg_num_rows($res_dev) > 0) {
										$horas_dev= pg_fetch_result($res_dev, 0, 0);
									}

									if (strlen($horas_dev) == 0) {
										$horas_dev = "00:00";
									} else {
										$xhoras_dev = explode(":",$horas_dev);
										$horas_dev = $xhoras_dev[0].":".$xhoras_dev[1];
										$h_dev[] = $horas_dev;
									}
									if(!empty($analista)) {
										$where_analista = " AND tbl_hd_chamado_atendente.admin = tbl_hd_chamado_item.admin";
									}
									$sql_analista ="SELECT TO_CHAR((EXTRACT(epoch FROM SUM(termino-data_inicio))/60 || ' minutes')::INTERVAL, 'HH24:MI')
											  FROM tbl_hd_chamado_atendente
											  JOIN tbl_admin using(admin)
											  JOIN tbl_hd_chamado_item USING(hd_chamado,admin)
											 WHERE tbl_hd_chamado_atendente.hd_chamado = $hd_chamado
											   AND data = data_inicio
											   AND data_inicio NOTNULL
										 	   AND termino NOTNULL
											   AND grupo_admin=1
											   AND grupo_admin=2 
											   $where_analista";
									$res_dev = pg_query($con, $sql_analista);
									if (pg_num_rows($res_analista) > 0) {
										$horas_analista= pg_fetch_result($res_analista, 0, 0);
									}

									if (strlen($horas_analista) == 0) {
										$horas_analista = "00:00";
									} else {
										$xhoras_analista = explode(":",$horas_analista);
										$horas_analista = $xhoras_analista[0].":".$xhoras_analista[1];
										$h_analista[] = $horas_analista;
									}

					$horas_tra = somaHoras(array($horas_sup,$horas_dev,$horas_analista));	
					$t_h_analista += $horas_analista;
					$total_hrs_tra_analista = somaHoras(array($t_h_analista));
					$t_h_dev += $horas_dev;
					$total_hrs_tra_dev = somaHoras(array($t_h_dev));
					$t_h_sup += $horas_sup;
					$total_hrs_tra_sup = somaHoras(array($t_h_sup));
					$t_h_tra += $horas_tra;
					$total_hrs_tra = somaHoras(array($t_h_tra));
									
					$tipo_chamado = pg_result($resx, $i, "tipo_chamado");				
					$semana       = pg_result($resx, $i, "semana");										
					$nome             = pg_result($resx, $i, "nome");
					$descricao        = pg_result($resx, $i, "descricao");
					$status           = pg_result($resx, $i, "status");
					$data_aberto      = pg_result($resx, $i, "hd_chamado_data");
					$data_resolvido   = pg_result($resx, $i, "hd_chamado_resolvido");					
					$prazo_horas      = pg_result($resx, $i, "prazo_horas");								
					if(empty($prazo_horas)) {
						$prazo_horas = 0;
					}
					$desconto         = pg_result($resx, $i, "valor_desconto");
					if(empty($desconto)) {
						$desconto = 0;
					}			
					$horas_teste   = pg_result($resx, $i, "horas_teste");
					if(empty($horas_teste)) {
						$horas_teste = 0;
					}
					$taxa_abertura = pg_result($resx, $i, "taxa_abertura");
					$horas_suporte = pg_result($resx, $i, "horas_suporte");
					if(empty($horas_suporte)) {
						$horas_suporte = 0;
					}
					$horas_telefone = pg_result($resx, $i, "horas_telefone");
					$horas_analise = pg_result($resx, $i, "horas_analise");	
					if(empty($horas_analise)) {
						$horas_analise = 0;
					}				
					$horas_efetivacao = pg_result($resx, $i, "horas_efetivacao");					
					$horas_desenvolvimento = pg_result($resx, $i, "horas_desenvolvimento");	
					if(empty($horas_desenvolvimento)) {
						$horas_desenvolvimento = 0;
					}
					$total_hrs_orcamento = ($horas_analise + $horas_desenvolvimento + $horas_teste + $horas_suporte);
					$orcamento = pg_result($resx, $i, "valor_hora_franqueada");
					if(empty($orcamento)) {
						$orcamento = 0;
					}
					$valor_aprovado = (($total_hrs_orcamento * $orcamento) - $desconto);
					$total_valor_aprovado += $valor_aprovado;
					$backlog_item     = pg_result($resx, $i, "backlog_item");
					if($tipo_data == "aberto"){
						$data_valor = 		   pg_result($resx, $i, "hd_chamado_data");
					}elseif($tipo_data == "resolvido"){
						$data_valor = 		   pg_result($resx, $i, "hd_chamado_resolvido");
					}elseif($tipo_data == "todos"){
						$data_valor = 		   pg_result($resx, $i, "hd_chamado_todos");
					}else{
						$data_valor = "";
					}
					list($xhoras_analisadas, $xnome_a, $xnome_d, $xnome_s) = explode("|", $backlog_item);
					$horas_analisadas = $xhoras_analisadas;
					if(empty($horas_analisadas)) {
						$horas_analisadas = 0;
					}
					$analista         = $xnome_a;
					$desenvolvedor    = $xnome_d;
					$suporte          = $xnome_s;					
					$horas_utilizadas = calc_horas($desenvolvedor, $hd_chamado);
					//$horas_suporte    = calc_horas($suporte, $hd_chamado);
					$horas_totais     = calc_horas(null, $hd_chamado);
					$nome_a = con_admin($analista);
					$nome_d = con_admin($desenvolvedor);
					$nome_s = con_admin($suporte);
					$total_horas_aprovadas += $total_hrs_orcamento;
					$total_orcamentos += $orcamento;
					$total_descontos += $desconto;
					$total_horas_analisadas    += $horas_analise;
					$total_horas_desenvolvidas += $horas_desenvolvimento;
				    $total_suporte += $horas_suporte;				    
				    $total_testes += $horas_teste;
				    $totais_horas += $horas_totais;				    
				    $totais_hrs_orcamento += $total_hrs_orcamento;
					$cor = ($x % 2) ? "#F7F5F0" : "#F1F4FA";
					echo "<tr class='pesquisa_dados' style='background-color: $cor;'>
							<td>
								<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado' target='_blank'>
									$hd_chamado
								</a>
							</td>
							<td nowrap>
								$nome
							</td>
							<td nowrap>
								$descricao
							</td>
							<td nowrap>
								$status
							</td>
							<td nowrap>
								$data_aberto
							</td>
							<td nowrap>
								$data_resolvido
							</td>
							<td nowrap>
								$nome_a
							</td>
							<td nowrap>
								$nome_d
							</td>
							<td nowrap>
								$nome_s
							</td>
							<td nowrap>
								$total_hrs_orcamento
							</td>
							<td nowrap>								
								" . 'R$ '.number_format($orcamento, 2, ',', '.') . "
							</td>
							<td nowrap>								
								" . 'R$ '.number_format($desconto, 2, ',', '.') . "
							</td>
							<td nowrap>								
								" . 'R$ '.number_format($valor_aprovado, 2, ',', '.') . "
							</td>							
							<td>							
								$horas_analise
							</td>
							<td>
								$horas_desenvolvimento
							</td>
							<td>$horas_suporte</td>
							<td>$horas_teste</td>
							<td>$total_hrs_orcamento</td>
							<td>$horas_analista</td>
							<td>$horas_dev</td>
							<td>$horas_sup</td>
							<td>$horas_tra</td>
						</tr>";
					fputs ($fp, "<tr style='font-size: 14px; text-align: center;'>
									<td style='background-color: $cor;'>
										<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado' target='_blank'>
											$hd_chamado
										</a>
									</td>
									<td nowrap style='background-color: $cor;'>
										$nome
									</td>
									<td nowrap style='background-color: $cor;'>
										$descricao
									</td>
									<td nowrap style='background-color: $cor;'>
										$status
									</td>
									<td nowrap style='background-color: $cor;'>
										$data_inicial
									</td>
									<td nowrap style='background-color: $cor;'>
										$data_final
									</td>
									<td nowrap style='background-color: $cor;'>
										$nome_a
									</td>
									<td nowrap style='background-color: $cor;'>
										$nome_d
									</td>
									<td nowrap style='background-color: $cor;'>
										$nome_s
									</td>
									<td nowrap style='background-color: $cor;'>
										$total_hrs_orcamento
									</td>	
									<td nowrap style='background-color: $cor;'>										
										" . 'R$ '.number_format($orcamento, 2, ',', '.') . "
									</td>	
									<td nowrap style='background-color: $cor;'>										
										" . 'R$ '.number_format($desconto, 2, ',', '.') . "
									</td>	
									<td nowrap style='background-color: $cor;'>										
										" . 'R$ '.number_format($valor_aprovado, 2, ',', '.') . "
									</td>																	
									<td style='background-color: $cor;'>
										$horas_analise
									</td>
									<td style='background-color: $cor;'>
										$horas_desenvolvimento
									</td>
									<td>$total_suporte</td>
									<td>$horas_teste</td>
								    <td>$horas_tra</td>
								</tr>");
					fputs ($fpcsv, "\n$hd_chamado;$nome;$descricao;$status;$data_aberto;$data_resolvido;$nome_a;$nome_d;$nome_s;$total_hrs_orcamento;$orcamento;$desconto;$valor_aprovado;$horas_analise;$horas_desenvolvimento;$horas_suporte;$horas_teste;$total_hrs_orcamento;$horas_analista;$horas_dev;$horas_sup;$horas_tra");
					$x = $x + 1;					
			}
			$total_chamados = $x;
			echo "<tr class='pesquisa_subtitulo'>
					<th nowrap>Total HD $total_chamados</th>
					<th colspan='8'>&nbsp;</th>
					<th nowrap>Total $total_horas_aprovadas</th>
					<th nowrap>&nbsp;</th>
					<th nowrap>Total " . 'R$ '.number_format($total_descontos, 2, ',', '.') . "</th>
					<th nowrap>Total " . 'R$ '.number_format($total_valor_aprovado, 2, ',', '.') . "</th>
					<th nowrap>Total $total_horas_analisadas</th>
					<th nowrap>Total $total_horas_desenvolvidas</th>
					<th nowrap>Total $total_suporte</th>
					<th nowrap>Total $total_testes</th>
					<th nowrap>Total $totais_hrs_orcamento</th>
					<th nowrap>Total $total_hrs_tra_analista</th>
					<th nowrap>Total $total_hrs_tra_dev</th>
					<th nowrap>Total $total_hrs_tra_sup</th>
					<th nowrap>Total $total_hrs_tra</th>
					<!-- <th colspan='2'>&nbsp;</th> -->
				  </tr>
				</table>";
			fputs ($fp, "<tr style='color: rgb(59,62,99); font-size: 14px;'>
							<th nowrap style='background-color: rgb(255,255,255);'>Total HD $total_chamados</th>
							<th colspan='8' style='background-color: rgb(255,255,255);'>&nbsp;</th>
							<th nowrap style='background-color: rgb(255,255,255);'>Total $total_horas_aprovadas</th>
							<th nowrap style='background-color: rgb(255,255,255);'>&nbsp;</th>
							<th nowrap style='background-color: rgb(255,255,255);'>Total " . 'R$ '.number_format($total_descontos, 2, ',', '.') . "</th>
							<th nowrap style='background-color: rgb(255,255,255);'>Total " . 'R$ '.number_format($total_valor_aprovado, 2, ',', '.') . "</th>
							<th nowrap style='background-color: rgb(255,255,255);'>Total $total_horas_analisadas</th>
							<th nowrap style='background-color: rgb(255,255,255);'>Total $total_horas_desenvolvidas</th>
							<th nowrap>Total $total_suporte</th>
							<th nowrap>Total $total_testes</th>
						    <th nowrap>Total $totais_hrs_orcamento</th>
						    <th nowrap>Total $total_hrs_tra_analista</th>
						    <th nowrap>Total $total_hrs_tra_dev</th>
						    <th nowrap>Total $total_hrs_tra_sup</th>
						    <th nowrap>Total $total_hrs_tra</th>
							<!-- <th colspan='2' style='background-color: rgb(255,255,255);'>&nbsp;</th> -->
						  </tr>
						</table>");
			fputs ($fpcsv, "\nTotal HD $total_chamados;;;;;;;;;Total $total_horas_aprovadas;;Total R$ $total_descontos;Total R$ $total_valor_aprovado;Total $total_horas_analisadas;Total $total_horas_desenvolvidas;Total $total_suporte;Total $total_testes;Total $totais_hrs_orcamento;Total $total_hrs_tra_analista;Total $total_hrs_tra_dev; Total $total_hrs_tra_sup; Total $total_hrs_tra");
			echo "<br />";
			fputs ($fp, "<br />");
			$x = 0;
			$total_chamados = 0;
			$total_horas_analisadas = 0;
			$total_horas_desenvolvidas = 0;
				$total_suporte = 0 ;
			$totais_horas = 0 ;
			$semana_calc = "";
		echo "<br />";
		$x = 0;
		$total_horas_a_erro = 0;
		$total_horas_d_erro = 0;
		for ($i = 0; $i < pg_num_rows($resx); $i++)
		{
			$tipo_chamado = pg_result($resx, $i, "tipo_chamado");
			if (in_array($tipo_chamado, Array(5))){
				$xhd_chamado      = pg_result($resx, $i, "hd_chamado");
				$backlog_item     = pg_result($resx, $i, "backlog_item");
				list($xhoras_analisadas, $xnome_a, $xnome_d, $xnome_s) = explode("|", $backlog_item);
				$xdesenvolvedor   = $xnome_d;
				$horas_analisadas = $xhoras_analisadas;
				$horas_utilizadas = calc_horas($xdesenvolvedor, $xhd_chamado);
				$horas_suporte = calc_horas($suporte, $hd_chamado);
				$total_horas_a_erro = $total_horas_a_erro + $horas_analisadas;
				$total_horas_d_erro = $total_horas_d_erro + $horas_utilizadas;
				$x = $x + 1;
			}
			$total_erros = $x;
		}
		$x = 0;
		$total_horas_a_desenvolvimento = 0;
		$total_horas_d_desenvolvimento = 0;
		for ($i = 0; $i < pg_num_rows($resx); $i++)
		{
			$tipo_chamado = pg_result($resx, $i, "tipo_chamado");
			if (!in_array($tipo_chamado, Array(5))) {
				$xhd_chamado       = pg_result($resx, $i, "hd_chamado");
				$backlog_item     = pg_result($resx, $i, "backlog_item");
				list($xhoras_analisadas, $xnome_a, $xnome_d, $xnome_s) = explode("|", $backlog_item);
				$xdesenvolvedor   = $xnome_d;
				$horas_analisadas = $xhoras_analisadas;
				$horas_utilizadas = calc_horas($xdesenvolvedor, $xhd_chamado);
				$horas_suporte = calc_horas($suporte, $hd_chamado);
				$total_horas_a_desenvolvimento = $total_horas_a_desenvolvimento + $horas_analisadas;
				$total_horas_d_desenvolvimento = $total_horas_d_desenvolvimento + $horas_utilizadas;
				$x = $x + 1;
			}
			$total_desenvolvimento = $x;
		}
		$total = $total_erros + $total_desenvolvimento;
		$total_geral_horas_a = $total_horas_a_erro + $total_horas_a_desenvolvimento;
		$total_geral_horas_d = $total_horas_d_erro + $total_horas_d_desenvolvimento;
		$porcentagem_erros = ($total_erros * 100) / $total;
		$porcentagem_erros = number_format($porcentagem_erros, 0, "", "");
		$porcentagem_desenvolvimento = ($total_desenvolvimento * 100) / $total;
		$porcentagem_desenvolvimento = number_format($porcentagem_desenvolvimento, 0, "", "");
		$porcentagem_horas_a_erros = ($total_horas_a_erro * 100) / $total_geral_horas_a;
		$porcentagem_horas_a_erros = number_format($porcentagem_horas_a_erros, 0, "", "");
		$porcentagem_horas_a_desenvolvimento = ($total_horas_a_desenvolvimento * 100) / $total_geral_horas_a;
		$porcentagem_horas_a_desenvolvimento = number_format($porcentagem_horas_a_desenvolvimento, 0, "", "");
		$porcentagem_horas_d_erros = ($total_horas_d_erro * 100) / $total_geral_horas_d;;
		$porcentagem_horas_d_erros = number_format($porcentagem_horas_d_erros, 0, "", "");
		$porcentagem_horas_d_desenvolvimento = ($total_horas_d_desenvolvimento * 100) / $total_geral_horas_d;;
		$porcentagem_horas_d_desenvolvimento = number_format($porcentagem_horas_d_desenvolvimento, 0, "", "");
		echo "<table id='totais'>
				<tr>
					<td colspan='3' style='text-align: center;'>
						Total de Chamados do Mês de $mesArray[$data_mes] = $total
					</td>
				</tr>
				<tr>
					<td style='text-align: right;'>
						Total HD Erros: $total_erros = $porcentagem_erros%
					</td>
					<td rowspan='4' style='width: 0.1px; background-color: #000;'>
					</td>
					<td style='text-align: left;'>
						Total HD Desenvolvimento: $total_desenvolvimento = $porcentagem_desenvolvimento%
					</td>
				</tr>
				<tr>
					<td style='text-align: right;'>
						Total Horas Analisadas Erros: $total_horas_a_erro = $porcentagem_horas_a_erros%
					</td>
					<td style='text-align: left;'>
						Total Horas Analisadas Desenvolvimento: $total_horas_a_desenvolvimento = $porcentagem_horas_a_desenvolvimento%
					</td>
				</tr>
				<tr>
					<td style='text-align: right;'>
						Total Horas Desenvolvidas Erros: $total_horas_d_erro = $porcentagem_horas_d_erros%
					</td>
					<td style='text-align: left;'>
						Total Horas Desenvolvidas Alteração: $total_horas_d_desenvolvimento = $porcentagem_horas_d_desenvolvimento%
					</td>
				</tr>
				<tr>
					<td style='text-align: right;'>
						Total Geral Horas Analisadas: $total_geral_horas_a
					</td>
					<td style='text-align: left;'>
						Total Geral Horas Desenvolvidas: $total_geral_horas_d
					</td>
				</tr>
			  </table>";
		fputs ($fp, "<br />");
		fputs ($fp, "<table style='font-weight: bold;'>
						<tr>
							<td colspan='3'>
								&nbsp;
							</td>
							<td colspan='2' style='text-align: center;' nowrap>
								Total de Chamados do Mês de $mesArray[$data_mes] = $total
							</td>
							<td colspan='3'>
								&nbsp;
							</td>
						</tr>
						<tr>
							<td colspan='3'>
								&nbsp;
							</td>
							<td style='text-align: right;' nowrap>
								Total HD Erros: $total_erros = $porcentagem_erros%
							</td>
							<td style='text-align: left;' nowrap>
								Total HD Desenvolvimento: $total_desenvolvimento = $porcentagem_desenvolvimento%
							</td>
							<td colspan='3'>
								&nbsp;
							</td>
						</tr>
						<tr>
							<td colspan='3'>
								&nbsp;
							</td>
							<td style='text-align: right;' nowrap>
								Total Horas Analisadas Erros: $total_horas_a_erro = $porcentagem_horas_a_erros%
							</td>
							<td style='text-align: left;' nowrap>
								Total Horas Analisadas Desenvolvimento: $total_horas_a_desenvolvimento = $porcentagem_horas_a_desenvolvimento%
							</td>
							<td colspan='3'>
								&nbsp;
							</td>
						</tr>
						<tr>
							<td colspan='3'>
								&nbsp;
							</td>
							<td style='text-align: right;' nowrap>
								Total Horas Desenvolvidas Erros: $total_horas_d_erro = $porcentagem_horas_d_erros%
							</td>
							<td style='text-align: left;' nowrap>
								Total Horas Desenvolvidas Alteração: $total_horas_d_desenvolvimento = $porcentagem_horas_d_desenvolvimento%
							</td>
							<td colspan='3'>
								&nbsp;
							</td>
						</tr>
						<tr>
							<td colspan='3'>
								&nbsp;
							</td>
							<td style='text-align: right;' nowrap>
								Total Geral Horas Analisadas: $total_geral_horas_a
							</td>
							<td style='text-align: left;' nowrap>
								Total Geral Horas Desenvolvidas: $total_geral_horas_d
							</td>
							<td colspan='3'>
								&nbsp;
							</td>
						</tr>
					  </table>");
		fputs ($fpcsv, "\n\nTotal de Chamados do Mês de $mesArray[$data_mes] = $total;
						Total HD Erros: $total_erros = $porcentagem_erros%;
						Total HD Desenvolvimento: $total_desenvolvimento = $porcentagem_desenvolvimento%;
						Total Horas Analisadas Erros: $total_horas_a_erro = $porcentagem_horas_a_erros%;
						Total Horas Analisadas Desenvolvimento: $total_horas_a_desenvolvimento = $porcentagem_horas_a_desenvolvimento%;
						Total Horas Desenvolvidas Erros: $total_horas_d_erro = $porcentagem_horas_d_erros%;
						Total Horas Desenvolvidas Alteração: $total_horas_d_desenvolvimento = $porcentagem_horas_d_desenvolvimento%;
						Total Geral Horas Analisadas: $total_geral_horas_a;
						Total Geral Horas Desenvolvidas: $total_geral_horas_d;");
		fclose ($fp);
		fclose ($fpcsv);
		$data = date("Y-m-d").".".date("H-i-s");
		copy ("/tmp/assist/relatorio_backlog.html", "../admin/xls/relatorio_backlog.$data.xls");		
		echo "<br />";
		/*echo "<div id='excel'>
				<a href='../admin/xls/relatorio_backlog.$data.xls' target='_blank'>
					<img src='imagens/excel.png' height='20px' width='20px' align='absmiddle'>
					&nbsp;
					Gerar Arquivo XLS
				</a>
			 </div>";*/

		// Gerar CSV
		copy ("/tmp/assist/relatorio_backlog.csv", "../admin/xls/relatorio_backlog.$data.csv");
		echo "<br />";
		echo "<div id='csv'>
				<a href='../admin/xls/relatorio_backlog.$data.csv' target='_blank'>
					<img src='imagens/icon_csv.png' height='20px' width='20px' align='absmiddle'>
					&nbsp;
					Gerar Arquivo CSV
				</a>
			 </div>";
	}
}
include "rodape.php";
?>
