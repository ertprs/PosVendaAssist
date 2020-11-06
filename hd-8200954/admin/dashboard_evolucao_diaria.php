<?php 

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$drop = pg_query($con, "DROP TABLE xtbl_os;");

$mes = date('m');
$ano = date('Y');
$dia = '';
$msg_erro = '';

if (!empty($_POST['mes_select'])) {
	if ($mes == $_POST['mes_select'] && ($ano == $_POST['ano_select'] || empty($_POST['ano_select']))) {
		$dia = date('d');
	} else if ($_POST['mes_select'] > $mes && ($_POST['ano_select'] >= $ano || empty($_POST['ano_select']))) {
		$mes = $_POST['mes_select'];
		$msg_erro = "Período de busca maior que o atual !";
	} else {
		$mes = $_POST['mes_select'];
		if (!empty($_POST['ano_select'])) {
			$dia = cal_days_in_month(CAL_GREGORIAN, $mes , $_POST['ano_select']);
		} else {
			$dia = cal_days_in_month(CAL_GREGORIAN, $mes , $ano);
		}
	}
}

if (!empty($_POST['ano_select'])) {
	$ano = $_POST['ano_select'];
}

if (empty($dia)) {
	$dia = date('d');
}

$new_date = "'$ano-$mes-$dia'::date";

$sql = "	SELECT  os,
					consumidor_nome,
					consumidor_estado,
					data_abertura,
					data_conserto,
					data_fechamento,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto_fabrica.nome_fantasia,
					tbl_produto.referencia,
					tbl_produto.descricao,
					inspetor_posto.nome_completo AS inspetor_sap_posto
			INTO TEMP xtbl_os
			FROM tbl_os
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_admin inspetor_posto ON inspetor_posto.fabrica = $login_fabrica AND inspetor_posto.admin = tbl_posto_fabrica.admin_sap
			JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
			WHERE tbl_os.fabrica = $login_fabrica
			AND tbl_os.excluida IS NOT TRUE
			AND tbl_os.posto <> 6359
			AND ((tbl_os.data_fechamento BETWEEN date_trunc('month', $new_date) AND $new_date) OR tbl_os.data_fechamento IS NULL)
			AND DATE_PART('dow', tbl_os.data_abertura) BETWEEN 1 and 5";
$res = pg_query($con, $sql);

if ($_POST["gerar_relatorio_csv"]) {

	$tipo_grafico = $_POST["tipo_grafico"];

	$dia = $_POST['dia_select'];
	$mes = $_POST['mes_select'];
	$ano = $_POST['ano_select'];

	$data = date("d-m-Y-H:i");

	$filename = "relatorio-dashboard-{$tipo_grafico}-{$data}.csv";

	$file = fopen("/tmp/{$filename}", "w");

	if ($tipo_grafico == "evolucao_diaria") {
		$cabecalho = "OS;Data Abertura;Data Conserto;Data Fechamento;Consumidor;Cód. Posto;Nome Posto\n";
		$where = "AND (data_fechamento IS NULL OR data_fechamento <= $new_date::date)";
	} else if ($tipo_grafico == "reducao_analista") {
		$cabecalho = "OS;Data Abertura;Data Conserto;Consumidor;Estado;Ref. Produto;Desc. Produto;Cód. Posto;Nome Posto;Inspetor\n";
		$where = "	AND inspetor_sap_posto NOTNULL
					AND (data_fechamento IS NULL OR data_fechamento <= $new_date)
					AND data_abertura < $new_date - INTERVAL '1 MONTH'";
	} else {
		$cabecalho = "OS;Data Abertura;Consumidor;Estado;Ref. Produto;Desc. Produto;Cód. Posto;Nome Posto\n";

		$where = "AND (data_fechamento IS NULL OR data_fechamento <= $new_date)";
	}

	fwrite($file, $cabecalho);

	$sql_csv = "SELECT DISTINCT os,
						consumidor_nome,
						consumidor_estado,
						TO_CHAR(data_abertura, 'DD/MM/YYYY') AS data_abertura,
						TO_CHAR(data_conserto, 'DD/MM/YYYY') AS data_conserto,
						TO_CHAR(data_fechamento, 'DD/MM/YYYY') AS data_fechamento,
						codigo_posto,
						nome_fantasia,
						referencia,
						descricao,
						inspetor_sap_posto 
				FROM xtbl_os
				WHERE 1=1
				{$where}
				ORDER BY os DESC";
	$res_csv = pg_query($con, $sql_csv);
	if (pg_num_rows($res_csv) > 0) {
		foreach (pg_fetch_all($res_csv) as $ky => $val) {
			$os                 = $val["os"];
			$consumidor_nome    = $val["consumidor_nome"];
			$consumidor_estado  = $val["consumidor_estado"];
			$data_abertura      = $val["data_abertura"];
			$data_conserto      = $val["data_conserto"];
			$data_fechamento    = $val["data_fechamento"];
			$codigo_posto       = $val["codigo_posto"];
			$nome_fantasia      = $val["nome_fantasia"];
			$referencia         = $val["referencia"];
			$descricao          = (mb_detect_encoding($val["descricao"], "UTF-8")) ? utf8_decode($val["descricao"]) : $val["descricao"];
			$inspetor_sap_posto = $val["inspetor_sap_posto"];

			if ($tipo_grafico == "evolucao_diaria") {
				fwrite($file,"$os;$data_abertura;$data_conserto;$data_fechamento;$consumidor_nome;$codigo_posto;$nome_fantasia\n");
			} else if ($tipo_grafico == "reducao_analista") {
				fwrite($file,"$os;$data_abertura;$data_conserto;$consumidor_nome;$consumidor_estado;$referencia;$descricao;$codigo_posto;$nome_fantasia;$inspetor_sap_posto\n");
			} else {
				$cabecalho = "OS;Data Abertura;Consumidor;Estado;Ref. Produto;Desc. Produto;Cód. Posto;Nome Posto\n";
				fwrite($file,"$os;$data_abertura;$consumidor_nome;$consumidor_estado;$referencia;$descricao;$codigo_posto;$nome_fantasia\n");
			}
		}
	} else {
		echo "error";
		exit();
	}

	fclose($file);

	if (file_exists("/tmp/{$filename}")) {
		system("mv /tmp/{$filename} xls/{$filename}");
		echo "xls/{$filename}";
	} else {
		echo "error";
	}

	exit();
}

function retorna_seg_sex($data_busca) {
	global $con;
	$sql_dia_semana = " SELECT  CASE WHEN TO_CHAR('$data_busca'::date, 'D') = 2::text THEN 'SEG'
					         		WHEN TO_CHAR('$data_busca'::date, 'D') = 3::text THEN 'TER'
					         		WHEN TO_CHAR('$data_busca'::date, 'D') = 4::text THEN 'QUA'
					         		WHEN TO_CHAR('$data_busca'::date, 'D') = 5::text THEN 'QUI'
					         		WHEN TO_CHAR('$data_busca'::date, 'D') = 6::text THEN 'SEX'
					    	    END AS dia_da_semana";
	$res_dia_semana = pg_query($con, $sql_dia_semana);

	return pg_fetch_result($res_dia_semana, 0, 'dia_da_semana');
}

// grafico - Evolução Diária

$total_os_mes_dia = [];
$total_os_mes_anterior = [];
$total_os_mes_anterior_dia = [];
$total_os = [];

for ($i=1; $i <= $dia; $i++) { 

	$data_busca = $ano.'-'.$mes.'-'.$i;
	$dia_mes = $i.'/'.$mes;

	$sql_semana = " SELECT (DATE_PART('dow', '$data_busca'::date) BETWEEN 1 and 5) AS semana";
	$res_semana = pg_query($con, $sql_semana);
	$semana = pg_fetch_result($res_semana, 0, 'semana'); 

	if ($semana == 'f') {
		continue;
	}

	$sql = "SELECT COUNT(os) AS os
		       FROM xtbl_os
		       WHERE (data_fechamento IS NULL OR data_fechamento = '$data_busca'::date)
		       AND data_abertura <='$data_busca'::date
		       AND data_abertura > '$data_busca'::date - INTERVAL '1 MONTH'";
	$res = pg_query($con, $sql);
	
	if (pg_num_rows($res) > 0) {
		$total_os_mes_dia[$dia_mes]["os"]  = pg_fetch_result($res, 0, 'os');
		$total_os_mes_dia[$dia_mes]["dia"] = $dia_mes.' '.retorna_seg_sex($data_busca);
	} else {
		$total_os_mes_dia[$dia_mes]["os"]  = 0;
		$total_os_mes_dia[$dia_mes]["dia"] = $dia_mes.' '.retorna_seg_sex($data_busca);
	}

	$sql = " SELECT COUNT(os) AS os
		      FROM xtbl_os
		      WHERE data_abertura < '$data_busca'::date - INTERVAL '1 MONTH'
		      AND (data_fechamento IS NULL OR data_fechamento = '$data_busca'::date)";
	$res = pg_query($con, $sql);
	if (pg_num_rows($res) > 0) {
		$total_os_mes_anterior_dia[$dia_mes]["os"]  = pg_fetch_result($res, 0, 'os');
		$total_os_mes_anterior_dia[$dia_mes]["dia"] = $dia_mes.' '.retorna_seg_sex($data_busca);
	} else {
		$total_os_mes_anterior_dia[$dia_mes]["os"] = 0;
		$total_os_mes_anterior_dia[$dia_mes]["dia"] = $dia_mes.' '.retorna_seg_sex($data_busca);
	}
}

$dias_arr = [];
$mes_ant_arr = [];
$mes_arr = [];
$media_arr = [];

foreach ($total_os_mes_dia as $key => $value) {
	$dias_arr[]    = "'".$value['dia']."'";
	$mes_ant_arr[] = $total_os_mes_anterior_dia[$key]["os"];
	$mes_arr[]     = $value['os'];
	$media_arr[]   = number_format((float) (($total_os_mes_anterior_dia[$key]["os"]*100) / ($value['os']+$total_os_mes_anterior_dia[$key]["os"])), 2, '.', ''); //round()
}

$dias_arr = implode(",", $dias_arr);
$mes_ant_arr = implode(",", $mes_ant_arr);
$mes_arr = implode(",", $mes_arr);
$media_arr = implode(",", $media_arr);


// grafico - Comparativo Estado D

$total_os_mes_dia_uf = [];
$total_os_mes_anterior_dia_uf = [];

$sql = " SELECT COUNT(os) AS os,
	        	consumidor_estado
		 FROM xtbl_os
		 WHERE (data_fechamento IS NULL OR data_fechamento = $new_date)
		 AND data_abertura > $new_date - INTERVAL '1 MONTH'
		 GROUP BY consumidor_estado 
		 ORDER BY consumidor_estado";
$res = pg_query($con, $sql);
if (pg_num_rows($res) > 0) {
	$total_os_mes_dia_uf = pg_fetch_all($res);
}

$sql = " SELECT COUNT(os) AS os,
	   			consumidor_estado
		 FROM xtbl_os
		 WHERE data_abertura < $new_date - INTERVAL '1 MONTH'
		 AND (data_fechamento IS NULL OR data_fechamento = $new_date)
		 GROUP BY consumidor_estado
		 ORDER BY consumidor_estado ";
$res = pg_query($con, $sql);
if (pg_num_rows($res) > 0) {
	$total_os_mes_anterior_dia_uf = pg_fetch_all($res);
}

$estados_arr_uf = [];
$mes_ant_arr_uf = [];
$mes_arr_uf = [];
$media_arr_uf = [];

if (count($total_os_mes_anterior_dia_uf) > count($total_os_mes_dia_uf)) {
	foreach ($total_os_mes_anterior_dia_uf as $key => $value) {
		$estados_arr_uf[] = "'".$value['consumidor_estado']."'";
		$mes_ant_arr_uf[]     = $value['os'];
		$n_tem_vl = true;
		foreach ($total_os_mes_dia_uf as $k => $v) {
			if ($value['consumidor_estado'] == $v['consumidor_estado']) {
				$n_tem_vl = false;
				$mes_arr_uf[] = $v["os"];
				$media_arr_uf[]   = number_format((float) (($value['os']*100) / ($v["os"]+$value['os'])), 2, '.', '');
			}
		}

		if ($n_tem_vl) {
			$mes_arr_uf[] = 0;
			$media_arr_uf[]   = 100;
		}
	}
} else {
	foreach ($total_os_mes_dia_uf as $key => $value) {
		$estados_arr_uf[] = "'".$value['consumidor_estado']."'";
		$mes_arr_uf[]     = (empty($value['os'])) ? 0 : $value['os'];
		$n_tem_vl = true;
		foreach ($total_os_mes_anterior_dia_uf as $k => $v) {
			if ($value['consumidor_estado'] == $v['consumidor_estado']) {
				$n_tem_vl = false;
				$mes_ant_arr_uf[] = $v['os'];
				$media_arr_uf[]   = number_format((float) (($v["os"]*100) / ($value['os']+$v["os"])), 2, '.', '');
			}
		}

		if ($n_tem_vl) {
			$mes_ant_arr_uf[] = 0;
			$media_arr_uf[]   = 100;
		}
	}
}

$estados_arr_uf = implode(",", $estados_arr_uf);
$mes_ant_arr_uf = implode(",", $mes_ant_arr_uf);
$mes_arr_uf = implode(",", $mes_arr_uf);
$media_arr_uf = implode(",", $media_arr_uf);


// grafico - Comparativo Estados 30 D

$total_os_estado = [];
$estados_arr = [];

$sql = " SELECT COUNT(os) AS os,
			    consumidor_estado
		 FROM xtbl_os
		 WHERE data_abertura < $new_date - INTERVAL '1 MONTH'
		 AND (data_fechamento IS NULL OR data_fechamento = $new_date)
		 GROUP BY consumidor_estado
		 ORDER BY consumidor_estado ASC ";
$res = pg_query($con, $sql);
if (pg_num_rows($res) > 0) {
	foreach (pg_fetch_all($res) as $key => $value) {
	 	$estados_arr[] = "'".$value['consumidor_estado']."'";
	 	$total_os_estado[] = $value['os'];
	 } 

	$estados_arr = implode(",", $estados_arr);
	$total_os_estado = implode(",", $total_os_estado);
}

// grafico - Por Analista

$total_os_inspetor = [];
$arr_total_os_inspetor_d = [];
$arr_total_os_inspetor_nome = [];
$arr_total_os_inspetor_dd = [];
$arr_dif = [];

$sql = " SELECT COUNT(os) FILTER(WHERE (data_fechamento IS NULL OR data_fechamento = $new_date - INTERVAL '1 DAYS')) AS os_ontem,
			    COUNT(os) FILTER(WHERE (data_fechamento IS NULL OR data_fechamento = $new_date)) AS os_hj,
			    inspetor_sap_posto
		 FROM xtbl_os
		 WHERE data_abertura < $new_date - INTERVAL '1 MONTH'
		 GROUP BY inspetor_sap_posto
		 ORDER BY inspetor_sap_posto ASC ";
$res = pg_query($con, $sql);
if (pg_num_rows($res) > 0) {
	$total_os_inspetor = pg_fetch_all($res);

	foreach ($total_os_inspetor as $key => $value) {
		$arr_total_os_inspetor_nome[$key] = "'".$value['inspetor_sap_posto']."'";
		$arr_total_os_inspetor_dd[$key]   = (empty($value['os_ontem'])) ? 0 : $value['os_ontem'];
		$arr_total_os_inspetor_d[$key]    = (empty($value['os_hj'])) ? 0 : $value['os_hj'];
		$arr_dif[]                        = $arr_total_os_inspetor_d[$key] - $arr_total_os_inspetor_dd[$key];
	}
	
	$arr_total_os_inspetor_nome = implode(",", $arr_total_os_inspetor_nome);
	$arr_total_os_inspetor_dd   = implode(",", $arr_total_os_inspetor_dd);
	$arr_total_os_inspetor_d    = implode(",", $arr_total_os_inspetor_d);
	$arr_dif                    = implode(",", $arr_dif);
}
?>

<html>
	<head>
		<link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/bootstrap.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/extra.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="../css/tc_css.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="../css/tooltips.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="../plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
		<link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/ajuste.css" />
		<script src="../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="../plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
		<script src="../plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
		<script src="../plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
		<script src="../plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
		<script src="../plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
		<script src="../bootstrap/js/bootstrap.js"></script>
		<script src="https://code.highcharts.com/highcharts.js"></script>
		<script src="https://code.highcharts.com/modules/exporting.js"></script>
		<script src="https://code.highcharts.com/modules/export-data.js"></script>

		<title>Dashboard Evolução Diária</title>
		<style>
			.body {
				padding: 2%;
			}

			.dark_body {
				background: #353536;
			}

			body {
				background: #365a7b;
			}

			.font_h3 {
				color: #fefeff;
			}

			#evolucao_diaria {
			    height: 400px;
			}

			#inspetor_dia {
				height: 400px;
			}

			.grafico_quadro {
				width: 96%; 
				margin-top: 1%;
				/*border: 1px solid black;
				box-shadow: 0px 0px 0 #333;
   				-webkit-box-shadow: 0px 0px 0 #333;
   				-moz-box-shadow: 0px 0px 0 #333;*/
   				height: 380px;
			}

			.div_consulta {
				color: #cfdae3;
			}
		</style>
		<script type="text/javascript">

			// Theme Dark
			Highcharts.theme = {
			  colors: ['#2b908f', '#90ee7e', '#f45b5b', '#7798BF', '#aaeeee', '#ff0066',
			    '#eeaaee', '#55BF3B', '#DF5353', '#7798BF', '#aaeeee'
			  ],
			  chart: {
			    backgroundColor: {
			      linearGradient: {
			        x1: 0,
			        y1: 0,
			        x2: 1,
			        y2: 1
			      },
			      stops: [
			        [0, '#2a2a2b'],
			        [1, '#3e3e40']
			      ]
			    },
			    style: {
			      fontFamily: '\'Unica One\', sans-serif'
			    },
			    plotBorderColor: '#606063'
			  },
			  title: {
			    style: {
			      color: '#E0E0E3',
			      textTransform: 'uppercase',
			      fontSize: '20px'
			    }
			  },
			  subtitle: {
			    style: {
			      color: '#E0E0E3',
			      textTransform: 'uppercase'
			    }
			  },
			  xAxis: {
			    gridLineColor: '#707073',
			    labels: {
			      style: {
			        color: '#E0E0E3'
			      }
			    },
			    lineColor: '#707073',
			    minorGridLineColor: '#505053',
			    tickColor: '#707073',
			    title: {
			      style: {
			        color: '#A0A0A3'

			      }
			    }
			  },
			  yAxis: {
			    gridLineColor: '#707073',
			    labels: {
			      style: {
			        color: '#E0E0E3'
			      }
			    },
			    lineColor: '#707073',
			    minorGridLineColor: '#505053',
			    tickColor: '#707073',
			    tickWidth: 1,
			    title: {
			      style: {
			        color: '#A0A0A3'
			      }
			    }
			  },
			  tooltip: {
			    backgroundColor: 'rgba(0, 0, 0, 0.85)',
			    style: {
			      color: '#F0F0F0'
			    }
			  },
			  plotOptions: {
			    series: {
			      dataLabels: {
			        color: '#F0F0F3',
			        style: {
			          fontSize: '11px'
			        }
			      },
			      marker: {
			        lineColor: '#333'
			      }
			    },
			    boxplot: {
			      fillColor: '#505053'
			    },
			    candlestick: {
			      lineColor: 'white'
			    },
			    errorbar: {
			      color: 'white'
			    }
			  },
			  legend: {
			    backgroundColor: 'rgba(0, 0, 0, 0.5)',
			    itemStyle: {
			      color: '#E0E0E3'
			    },
			    itemHoverStyle: {
			      color: '#FFF'
			    },
			    itemHiddenStyle: {
			      color: '#606063'
			    },
			    title: {
			      style: {
			        color: '#C0C0C0'
			      }
			    }
			  },
			  credits: {
			    style: {
			      color: '#666'
			    }
			  },
			  labels: {
			    style: {
			      color: '#707073'
			    }
			  },

			  drilldown: {
			    activeAxisLabelStyle: {
			      color: '#F0F0F3'
			    },
			    activeDataLabelStyle: {
			      color: '#F0F0F3'
			    }
			  },

			  navigation: {
			    buttonOptions: {
			      symbolStroke: '#DDDDDD',
			      theme: {
			        fill: '#505053'
			      }
			    }
			  },

			  // scroll charts
			  rangeSelector: {
			    buttonTheme: {
			      fill: '#505053',
			      stroke: '#000000',
			      style: {
			        color: '#CCC'
			      },
			      states: {
			        hover: {
			          fill: '#707073',
			          stroke: '#000000',
			          style: {
			            color: 'white'
			          }
			        },
			        select: {
			          fill: '#000003',
			          stroke: '#000000',
			          style: {
			            color: 'white'
			          }
			        }
			      }
			    },
			    inputBoxBorderColor: '#505053',
			    inputStyle: {
			      backgroundColor: '#333',
			      color: 'silver'
			    },
			    labelStyle: {
			      color: 'silver'
			    }
			  },

			  navigator: {
			    handles: {
			      backgroundColor: '#666',
			      borderColor: '#AAA'
			    },
			    outlineColor: '#CCC',
			    maskFill: 'rgba(255,255,255,0.1)',
			    series: {
			      color: '#7798BF',
			      lineColor: '#A6C7ED'
			    },
			    xAxis: {
			      gridLineColor: '#505053'
			    }
			  },

			  scrollbar: {
			    barBackgroundColor: '#808083',
			    barBorderColor: '#808083',
			    buttonArrowColor: '#CCC',
			    buttonBackgroundColor: '#606063',
			    buttonBorderColor: '#606063',
			    rifleColor: '#FFF',
			    trackBackgroundColor: '#404043',
			    trackBorderColor: '#404043'
			  }
			};

			// Apply the theme
			Highcharts.setOptions(Highcharts.theme);

			$(function() {
				$(".csv").on("click", function() {
					let tipo_grafico = $(this).attr("data-grafico");
					let label_btn    = $(this).html();

					let dia = $("#dia_select option:selected").val();
					let mes = $("#mes_select option:selected").val();
					let ano = $("#ano_select option:selected").val();

					$.ajax({
				        async: true,
				        type: 'POST',
				        dataType:"JSON",
				        url: 'dashboard_evolucao_diaria.php',
				        data: {
				            gerar_relatorio_csv:true,
				            tipo_grafico:tipo_grafico,
				            dia_select:dia,
				            mes_select:mes,
				            ano_select:ano
				        },
				        beforeSend: function(){
		                    $(".div_csv").hide('slow');
		                    $(this).html("&nbsp;&nbsp;Gerando...&nbsp;&nbsp;<br><img src='imagens/loading_bar.gif'> ").show('slow');
		                },
		                complete: function(data) {
					    	$(this).html(label_btn);
					    	$(".div_csv").show('slow');

					        if(data.responseText == 'error'){
					        	alert('Erro ao gerar CSV');
					            return false;
					        } else {
					        	window.open(data.responseText);
					        }
				    	}
					});
				});
			});
		</script>
	</head>
	<body>
		<div class="body">
			<div class="row-fluid dark_body">
				<div class="row">
					<div class="span12 tac">
						<h3 class="font_h3">Parâmetros de Pesquisa</h3>
					</div>
				</div>
			</div>
			<div class="row-fluid dark_body div_consulta">
				<form name="frm_consultar" method="post" action="dashboard_evolucao_diaria.php">
					<div class="span1"></div>
					<div class="span3">
						<?php 
							$arr_mes =  [
											'01'=>'Janeiro',
											'02'=>'Fevereiro',
											'03'=>'Março',
											'04'=>'Abril',
											'05'=>'Maio',
											'06'=>'Junho',
											'07'=>'Julho',
											'08'=>'Agosto',
											'09'=>'Setembro',
											'10'=>'Outubro',
											'11'=>'Novembro',
											'12'=>'Dezembro'
										]
						?>
						<select name="mes_select" id="mes_select">
							<option value="">Mês</option>
							<?php
								foreach ($arr_mes as $key => $value) {
									$selected_mes = '';
									if ($key == $mes) {
										$selected_mes = 'selected';
									}
							?>
										<option <?=$selected_mes?> value="<?=$key?>"><?=$value?></option>
							<?php
								}
							?>
						</select>
					</div>
					<div class="span1"></div>
					<div class="span3">
						<?php
							$sqlAno = " SELECT DISTINCT date_part('year', data_abertura) as ano FROM xtbl_os ";
							$resAno = pg_fetch_all(pg_query($con, $sqlAno));
						?>
						<select name="ano_select" id="ano_select">
							<option value="">Ano</option>
							<?php 
								foreach ($resAno as $key => $value) {
									$selected_ano = '';
									if ($value['ano'] == $ano) {
										$selected_ano = 'selected';
									}
							?>
									<option <?=$selected_ano?> value="<?=$value['ano']?>"><?=$value['ano']?></option>
							<?php
								}
							?>
						</select>
					</div>
					<div class="span1"></div>
					<div class="span3">
						<button class="btn btn-light" type="submit" name="btn_consulta" value="sim">Consultar</button>
						<input type="hidden" name="dia_select" id="dia_select" value="<?=$dia?>">
					</div>
				</form>
			</div>
	<?php if (!empty($msg_erro)) { ?>
			<div class="row-fluid dark_body div_consulta">
				<div class="span12 tac">
					<h3><?=$msg_erro?></h3>
				</div>
			</div>
	<?php } else { ?>
			<div class="row-fluid">
				<div class="span8 grafico_quadro" id="evolucao_diaria">
					<script>
						Highcharts.chart('evolucao_diaria', {
						    chart: {
						        type: 'column'
						    },
						    title: {
						        text: 'ACOMPANHAMENTO DE OS'
						    },
						    subtitle: {
						        text: 'EVOLUÇÃO DIÁRIA'
						    },
						    xAxis: [{
						        categories: [<?=$dias_arr?>],
						        labels: {
						            step: 0
						        }
						    }],
						    yAxis: [{
						        title: {
						            text: 'Porcentagem',
						            style: {
						                color: Highcharts.getOptions().colors[0]
						            }
						        },
						        labels: {
						            format: '{value}%',
						            style: {
						                color: Highcharts.getOptions().colors[0]
						            }
						        },
						        opposite: true
						    },
						    {
						        labels: {
						            style: {
						                color: Highcharts.getOptions().colors[1]
						            }
						        },
						        title: {
						            text: 'Quantidade',
						            style: {
						                color: Highcharts.getOptions().colors[1]
						            }
						        }
						    }],
						    tooltip: {
						     pointFormat: '<span style="color:{series.color}">{series.name}</span>: <b>{point.y}</b><br/>',
						        shared: true
						    },
						    legend: {
						        align: 'left',
						        x: 180,
						        verticalAlign: 'top',
						        y: 38,
						        floating: true,
						        itemStyle: {
					                fontSize: '10px'
					            },
						        shadow: false
						    },
						    plotOptions: { //deixa uma coluna sobre a outra
						        column: {
						            stacking: 'normal',
						            dataLabels: {
						                enabled: true
						            }
						        }
						    },
						    series: [{
							    	dataLabels: {
								        enabled: true,
								            format: '{point.y}'
								    },
							    	name: 'OS ACIMA DE 30 DIAS',
							        type: 'column',
							        yAxis: 1,
							        data: [<?=$mes_ant_arr?>],
							    },
							    {
							    	dataLabels: {
								        enabled: true,
								            format: '{point.y}'
								    },
							        name: 'OS ATÉ 30 DIAS',
							        type: 'column',
							        yAxis: 1,
							        data: [<?=$mes_arr?>],
							    },
							    {
							    	dataLabels: {
								        enabled: true,
								            format: '{point.y:.1f}'
								    },
							        name: '% OS ACIMA DE 30 DIAS',
							        type: 'spline',
							        data: [<?=$media_arr?>],
							        tooltip: {
							            valueSuffix: '%'
							    	}
								}]
						});
					</script>
				</div>
				<div class="span4 grafico_quadro" id="inspetor_dia">
					<script>
						Highcharts.chart('inspetor_dia', {
						    chart: {
						        type: 'column'
						    },
						    title: {
						        text: 'REDUÇÃO DE OS ACIMA DE 30 DIAS'
						    },
						    subtitle: {
						        text: 'POR ANALISTA'
						    },
						    xAxis: {
						        categories: [<?=$arr_total_os_inspetor_nome?>],
						    },
						    yAxis: [{
						        title: {
						            text: 'Quantidade',
						            style: {
						                color: Highcharts.getOptions().colors[1]
						            }
						        },
						        labels: {
						            format: '{value}',
						            style: {
						                color: Highcharts.getOptions().colors[1]
						            }
						        },
						        opposite: false
						    }],
						    credits: {
						        enabled: false
						    },
						     series: [{
						       dataLabels: {
						        enabled: true,
						            format: '{point.y}'
						    },
						        name: 'D-1',
						        data: [<?=$arr_total_os_inspetor_dd?>]

						    },
						    {
						        dataLabels: {
						        enabled: true,
						            format: '{point.y}'
						    },
						      	name: 'D',
						       	data: [<?=$arr_total_os_inspetor_d?>]

						    },
						    {
						        dataLabels: {
						        enabled: true,
						            format: '{point.y}'
						    },
						      	name: 'Diferença',
						        data: [<?=$arr_dif?>]
						    }]
						});
					</script>
				</div>
			</div>
			<div class="row-fluid">
				<div class="span6 grafico_quadro" id="estado_dia">
					<script>
						Highcharts.chart('estado_dia', {
						    chart: {
						        type: 'column'
						    },
						    title: {
						        text: 'ACOMPANHAMENTO DE OS'
						    },
						    subtitle: {
						        text: 'COMPARATIVO POR ESTADO EM "D"'
						    },
						    xAxis: [{
						        categories: [<?=$estados_arr_uf?>],
						        labels: {
						            step: 1
						        }
						    }],
						    yAxis: [{
						        title: {
						            text: 'Porcentagem',
						            style: {
						                color: Highcharts.getOptions().colors[0]
						            }
						        },
						        labels: {
						            format: '{value}%',
						            style: {
						                color: Highcharts.getOptions().colors[0]
						            }
						        },
						        opposite: true
						    },
						    {
						        labels: {
						            style: {
						                color: Highcharts.getOptions().colors[1]
						            }
						        },
						        title: {
						            text: 'Quantidade',
						            style: {
						                color: Highcharts.getOptions().colors[1]
						            }
						        }
						    }],
						    tooltip: {
						     pointFormat: '<span style="color:{series.color}">{series.name}</span>: <b>{point.y}</b><br/>',
						        shared: true
						    },
						    legend: {
						        align: 'left',
						        x: 110,
						        verticalAlign: 'top',
						        y: 36,
						        floating: true,
						        itemStyle: {
					                fontSize: '10px'
					            },
						        shadow: false
						    },
						    plotOptions: {
						        column: {
						            stacking: 'normal',
						            dataLabels: {
						                enabled: true
						            }
						        }
						    },
						    series: [{
							    	dataLabels: {
								        enabled: true,
								            format: '{point.y}'
								    },
							    	name: 'ACIMA DE 30 DIAS',
							        type: 'column',
							        yAxis: 1,
							        data: [<?=$mes_ant_arr_uf?>],
							    },
							    {
							    	dataLabels: {
								        enabled: true,
								            format: '{point.y}'
								    },
							        name: 'ATÉ 30 DIAS',
							        type: 'column',
							        yAxis: 1,
							        data: [<?=$mes_arr_uf?>],
							    },
							    {
							    	dataLabels: {
								        enabled: true,
								            format: '{point.y:.1f}'
								    },
							        name: '% > 30 DIAS',
							        type: 'spline',
							        data: [<?=$media_arr_uf?>],
							        tooltip: {
							            valueSuffix: '%'
							    	}
								}]
						});
					</script>
				</div>
				<div class="span6 grafico_quadro" id="estado_30">
					<script>
						Highcharts.chart('estado_30', {
						  chart: {
						    type: 'column'
						  },
						  title: {
						    text: "ACOMPANHAMENTO DE OS"
						  },
						  xAxis: {
						    categories: [<?=$estados_arr?>]
						  },
						  subtitle: {
						    text: "COMPARATIVO POR ESTADO ACIMA DE 30 DIAS"
						  },
						  yAxis: {
						    labels: {
					            style: {
					                color: Highcharts.getOptions().colors[1]
					            }
					        },
					        title: {
					            text: 'Quantidade',
					            style: {
					                color: Highcharts.getOptions().colors[1]
					            }
					        }
						  },
						  legend: {
						        enabled: true
						    },
						  tooltip: {
						        enabled: false
						    },

						  series: [{
						   dataLabels: {
						        enabled: true,
						            format: '{point.y}'
						    },
						    data: [<?=$total_os_estado?>],
						    showInLegend: false
						  }]
						})
					</script>
				</div>
			</div>
			<br />
			<div class="row-fluid dark_body">
				<div class="row">
					<div class="span12 tac">
						<h3 class="font_h3">Relatórios CSVs</h3>
					</div>
				</div>
			</div>
			<div class="row-fluid dark_body">
				<div class="span3 div_csv">
					<div id='' class="btn_excel tac">
						<span><img src='imagens/icon_csv.png' /></span>
						<span class="txt csv" data-grafico="evolucao_diaria" id="csv_evolucao_diaria">Evolução Diária</span>
					</div>
				</div>
				<div class="span3 div_csv">
					<div id='' class="btn_excel tac">
						<span><img src='imagens/icon_csv.png' /></span>
						<span class="txt csv" data-grafico="reducao_analista" id="csv_reducao_analista">Redução Analista</span>
					</div>
				</div>
				<div class="span3 div_csv">
					<div id='' class="btn_excel tac">
						<span><img src='imagens/icon_csv.png' /></span>
						<span class="txt csv" data-grafico="comparativo_d" id="csv_comparativo_d">Comparativo D</span>
					</div>
				</div>
				<div class="span3 div_csv">
					<div id='' class="btn_excel tac">
						<span><img src='imagens/icon_csv.png' /></span>
						<span class="txt csv" data-grafico="comparativo_30_d" id="csv_comparativo_30_d">Comparativo 30 D</span>
					</div>
				</div>
			</div>
	<?php } ?>
		</div>

	</body>
</html>
