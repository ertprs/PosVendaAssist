<?php
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include __DIR__.'/dbconfig.php';
include __DIR__.'/includes/dbconnect-inc.php';

if ($areaAdmin === true) {
	include __DIR__.'/admin/autentica_admin.php';
} else {
	include __DIR__.'/autentica_usuario.php';
}

include __DIR__.'/funcoes.php';

include_once "helpdesk_posto_autorizado/helpdesk.php";

if (file_exists(__DIR__."/helpdesk_posto_autorizado/{$login_fabrica}/regras.php")) {
	include_once __DIR__."/helpdesk_posto_autorizado/{$login_fabrica}/regras.php";
}

function retiraAcentos($string){ /*HD - 4275929*/
    return preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(nº)/","/(Nº)/","/(ç)/","/(Ç)/"),explode(" ","a A e E i I o O u U n N n N c C"),$string);
}
if (!empty($_POST["csv"])) {
    if (!empty($resPesquisa)) {
        $data_retorno = '';
        if (in_array($login_fabrica , array(30,72))) {
        	$data_retorno = 'Data Retorno;';
        }
        $codigo_posto = '';
        if ($login_fabrica==72) {
        	$codigo_posto = 'Código Posto;';
        }

        if (in_array($login_fabrica, [30, 35])) {
        	$uf_posto = "UF;";
        	$tempo_atendimento = ";Tempo Atendimento";
        	$tempo_atendimento .= ";Referência;Peças;O.S;Pedido;Motivo";
		}
		if($login_fabrica == 35){
			$tempo_atendimento .= ";Ticket Atendimento;Código Localizador;Pré-Logística";
		}
        if (in_array($login_fabrica, [30])) {
        	$uf_posto .= "Responsável Pela Solicitação;";
            $cliente = "Cliente;";
        }

        $content = "N° Help-Desk;{$cliente}Status;Data;{$codigo_posto}Posto;{$uf_posto}Tipo de Solicitação;{$data_retorno}Atendente;Última Interação{$tempo_atendimento}\n";

        if ($login_fabrica == 35) {
        	$content = retiraAcentos($content);
        } 

        while ($fetch = pg_fetch_assoc($resPesquisa)) {
            $content .= $fetch["hd_chamado"] . ';';
            if (in_array($login_fabrica, [30])) {
            	 $content .= $fetch["nome_cliente"] . ';';
            }
            $content .= $fetch["status"] . ';';
            $content .= $fetch["status"] . ';';
            $content .= $fetch["data"] . ';';
            if ($login_fabrica == 72) {
				$content .= $fetch["codigo_posto"] . ';';
            }
            $content .= $fetch["posto"] . ';';
            if (in_array($login_fabrica, [30, 35])) {
            	$content .= $fetch["estado"] . ';';
            }
            if (in_array($login_fabrica, [30])) {
            	$content .= $fetch["responsavel_solicitacao"] . ';';
            }
            
            $content .= $fetch["tipo_solicitacao"] . ';';
            if (in_array($login_fabrica , array(30,72))) {
            	$content .= $fetch["data_providencia"] . ';';
            }
            $content .= $fetch["atendente"] . ';';
            if (in_array($login_fabrica, [35])) {
	        	/*HD - 4220316*/
	        	$aux_sql = "SELECT peca_faltante FROM tbl_hd_chamado_posto WHERE hd_chamado = " . $fetch["hd_chamado"] . " LIMIT 1 ";
				$aux_res = pg_query($con, $aux_sql);
				$aux_sql_pec = pg_fetch_result($aux_res, 0, 'peca_faltante');

				$aux_sql_pec = explode(',', $aux_sql_pec);
				foreach ($aux_sql_pec as $key => $value) {
					$campos = explode("=", $value);
					$aux_sql_pec[$key] = "'".$campos[0]."'";
				}

				$aux_sql = "
					SELECT 
						referencia AS ref_peca_posto,
						descricao AS peca_posto
					FROM
						tbl_peca
					WHERE
						peca IN (" . implode(",", $aux_sql_pec) . ")
				";
				$aux_res = pg_query($con, $aux_sql);
				$aux_tot = (int) pg_num_rows($aux_res);

            	$content .= $fetch["ultima_interacao"] . ";";

            	$dataAbertura = new DateTime( $fetch["data2"]);
				$dataDiff = $dataAbertura->diff(new DateTime( $fetch["ultima_interacao2"] ));
				
				$tempoDuracao = "";

				if ($fetch["status"] == 'Finalizado' || $fetch["status"] == 'Cancelado') {
					$horas_diferenca   = $dataDiff->h + ($dataDiff->days * 24);
					$minutos_diferenca = $dataDiff->i;
					$tempoDuracao      = $horas_diferenca . ':' . $minutos_diferenca;
				}

				$content .= $tempoDuracao . ";";
				$aux_sql_pec = "";
				$aux_sql_ref_pec = "";

				$jsonArrayCampos = json_decode($fetch['array_campos_adicionais'], true);
				if ($aux_tot <= 1) {
					$aux_sql_pec = pg_fetch_result($aux_res, 0, 'peca_posto');
					$aux_sql_ref_pec = pg_fetch_result($aux_res, 0, 'ref_peca_posto');
					$content .= $aux_sql_ref_pec . ";";
					$content .= $aux_sql_pec  .";";
					$content .= $fetch["os"] . ";";
					$content .= $fetch["pedido"] . ";";
					$content .= $fetch["descricao_motivo"] . ";";
					if($login_fabrica == 35) {
						$content .= $jsonArrayCampos["ticket_atendimento"] . ";";
						$content .= $jsonArrayCampos["cod_localizador"] . ";";
						$content .= $jsonArrayCampos["pre_logistica"] . ";";
					}
					$content .=  "\n";
				} else {
					$auxiliar  = $fetch["hd_chamado"] . ';';
		            $auxiliar .= $fetch["status"] . ';';
		            $auxiliar .= $fetch["data"] . ';';
		            $auxiliar .= $fetch["posto"] . ';';
		            $auxiliar .= $fetch["estado"] . ';';
		            $auxiliar .= $fetch["tipo_solicitacao"] . ';';
		            $auxiliar .= $fetch["atendente"] . ';';
		            $auxiliar .= $fetch["ultima_interacao"] . ";";
		            $auxiliar .= $tempoDuracao . ";";



					for ($z = 0; $z < $aux_tot; $z++) {
						$aux_sql_pec  = pg_fetch_result($aux_res, $z, 'peca_posto');
						$aux_sql_ref_pec  = pg_fetch_result($aux_res, $z, 'ref_peca_posto');
						$content  .= $aux_sql_ref_pec . ";"; 
						$content  .= $aux_sql_pec .";"; 

						$content .= $fetch["os"] . ";";
						$content .= $fetch["pedido"] . ";";
						$content .= $fetch["descricao_motivo"] . ";";
						$content .=  "\n";

						if ($z < ($aux_tot - 1)) {
							$content .= $auxiliar;
						}
					}
				}

				

            } else {
            	$content .= $fetch["ultima_interacao"] . "\n";
            }
            
        }

        if ($login_fabrica == 35) {
        	$content = retiraAcentos($content);
        }

        $csv_name = 'xls/helpdesk_posto_autorizado_' . substr(sha1($login_admin), 0, 6) . date('Ymd') . '.csv';

        file_put_contents($csv_name, ($content));

        exit("$csv_name");
    } else {
        exit;
    }
}

/*HD - 4275929*/
if (!empty($_POST["gerarRelatorioInteracoes"])) {
	if (!empty($resPesquisa)) {
		$head  = "Help-Desk"                   . ";";
		$head .= "Data de Abertura"            . ";";
		$head .= "Status"                      . ";";
		$head .= "Data de Fechamento"          . ";";
		$head .= "O.S."                        . ";";
		$head .= "Posto"                       . ";";
		$head .= "UF"                          . ";";
		$head .= "Tipo Solicitação"            . ";";
		$head .= "Atendente"                   . ";";
		$head .= "Tempo de Tratamento (HH:MM)" . ";";
		$head .= "Referência"                  . ";";
		$head .= "Peça"                        . "\n";

		$body = "";

		while ($fetch = pg_fetch_assoc($resPesquisa)) {
			$hd_chamado       = $fetch["hd_chamado"];
			$data_abertura    = $fetch["data"];
			$status           = $fetch["status"];
			$tipo_solicitacao = $fetch["tipo_solicitacao"];
			$atendente        = $fetch["atendente"];
			$posto            = $fetch["posto"];
			$estado           = $fetch["estado"];

			$aux_sql = "SELECT TO_CHAR(data, 'DD/MM/YYYY HH24:MI') AS data_fechamento FROM tbl_hd_chamado_item WHERE hd_chamado = $hd_chamado AND status_item = 'Finalizado' ORDER BY data DESC LIMIT 1";
			$aux_res = pg_query($con, $aux_sql);

			if (pg_num_rows($aux_res) > 0) {
				$data_fechamento = pg_fetch_result($aux_res, 0, 'data_fechamento');
			} else {
				$data_fechamento = "";
			}

			$aux_sql = "SELECT status_item, data FROM tbl_hd_chamado_item WHERE hd_chamado = $hd_chamado ORDER BY data ASC";
			$aux_res = pg_query($con, $aux_sql);
			$aux_row = pg_num_rows($aux_res);

			if ($aux_row > 0) {
				$aux_total_horas   = 0;
				$aux_total_minutos = 0;

				for ($w=0; $w < $aux_row; $w++) { 
					$aux_status = pg_fetch_result($aux_res, $w, 'status_item');

					if ($aux_status == 'Ag. Fábrica') {
						$aux_data_atual   = date_create(pg_fetch_result($aux_res, $w, 'data'));
						$aux_proxima_data = date_create(pg_fetch_result($aux_res, ($w + 1), 'data'));


						if (!empty($aux_data_atual) > 0 && !empty($aux_proxima_data) > 0) {
							$diff              = $aux_data_atual->diff($aux_proxima_data);
							$horas_diferenca   = $diff->h + ($diff->days * 24);
							$minutos_diferenca = $diff->i;

							$aux_total_horas   += $horas_diferenca;
							$aux_total_minutos += $minutos_diferenca;

							unset($diff, $horas_diferenca, $minutos_diferenca);
						}
					}
				}

				if (!empty($aux_total_horas) || !empty($aux_total_minutos)) {
					if ($aux_total_minutos > 60) {
						$horas_adicionais = 0;

						while ($aux_total_minutos >= 60) {
							$aux_total_minutos = $aux_total_minutos - 60;
							$horas_adicionais++;
						}

						$aux_total_horas += $horas_adicionais;
					}
					$tempo_tratamento = $aux_total_horas . ":" . $aux_total_minutos;
				} else {
					$tempo_tratamento = "";
				}
			} else {
				$tempo_tratamento = "";
			}

			$aux_sql = "SELECT os FROM tbl_hd_chamado_extra WHERE hd_chamado = $hd_chamado";
			$aux_res = pg_query($con, $aux_sql);

			if (pg_num_rows($aux_res) > 0) {
				$os = pg_fetch_result($aux_res, 0, 'os');
			} else {
				$os = "";
			}

			$aux_sql = "SELECT peca_faltante FROM tbl_hd_chamado_posto WHERE hd_chamado = $hd_chamado";
			$aux_res = pg_query($con, $aux_sql);
			$aux_row = pg_num_rows($aux_res);

			if ($aux_row > 0) {
				for ($w=0; $w < $aux_row; $w++) { 
					$peca_faltante = pg_fetch_result($aux_res, $w, 'peca_faltante');
					$peca_faltante = explode(",", $peca_faltante);

					$referencia = array();
					$descricao  = array();

					foreach ($peca_faltante as $peca) {
						$campos = explode("=", $peca);
						if (!empty($peca)) {
							$aux_sql = "SELECT referencia, descricao FROM tbl_peca WHERE peca = ".$campos[0]." AND fabrica = $login_fabrica";
							$aux_res = pg_query($con, $aux_sql);

							if (pg_num_rows($aux_res) > 0) {
								$referencia[] =  pg_fetch_result($aux_res, 0, 'referencia');
								$descricao[]       =  pg_fetch_result($aux_res, 0, 'descricao');
							}
						}
					}

					$referencia = implode(" ", $referencia);
					$peca       = implode(" ", $descricao);
				}
			} else {
				$referencia = "";
				$peca       = "";
			}

			$body .= "\"$hd_chamado\";";
			$body .= "\"$data_abertura\";";
			$body .= "\"$status\";";
			$body .= "\"$data_fechamento\";";
			$body .= "\"$os\";";
			$body .= "\"$posto\";";
			$body .= "\"$estado\";";
			$body .= "\"$tipo_solicitacao\";";
			$body .= "\"$atendente\";";
			$body .= "\"$tempo_tratamento\";";
			$body .= "\"$referencia\";";
			$body .= "\"$peca\"\n";
		}

		$arquivo = $head . $body;

		$csv_name = 'xls/helpdesk_relatorio_interacao_' . substr(sha1($login_admin), 0, 6) . date('Ymd') . '.csv';
		file_put_contents($csv_name, utf8_encode($arquivo));

	    echo $csv_name;
	    exit;
	} else {
		exit;
	}
}

if ($areaAdmin === true) {
	$layout_menu = "callcenter";
} else {
	$layout_menu = "os";
}

$title = (!in_array($login_fabrica, [169,170])) ? "Helpdesk do Posto Autorizado" : "Helpdesk de Suporte Técnico";

$title = (in_array($login_fabrica, [198])) ? "Help-Desk Interno" : $title;

if ($areaAdmin === true) {
	include __DIR__.'/admin/cabecalho_new.php';
} else {
	include __DIR__.'/cabecalho_new.php';
}

$plugins = array(
   "datepicker",
   "shadowbox",
   "highcharts",
   "maskedinput",
   "select2",
   "dataTable"
);

include __DIR__.'/admin/plugin_loader.php';

?>

<style>

div.accordion-heading, div.accordion-inner {
	border: 1px #CCC solid;
	background-color: #FFF;
}

</style>

<script>

$(function() {

	$("#data_inicial, #data_final").datepicker({ dateFormat: "dd/mm/yy" }).mask("99/99/9999");

	$("select").select2();

	$(document).on("click", ".disponibilizar", function() {
		var linha = $(this).attr('rel');
		var admin = $('#admin_'+linha).val();
		var segundos = 3;

		$.ajax({
			url: 'helpdesk_posto_autorizado_listar.php',
			type: 'POST',
			data: { disponibilizar: true, admin: admin }
		}).done(function(data) {
			data = $.parseJSON(data);

			if (data.error) {
				$("#msgAdminDisponibilidade").html("<div class='alert alert-error'><h4>"+data.error+"</h4></div>");
				$("#msgAdminDisponibilidade").fadeIn();
				setTimeout(function(){
					$('#msgAdminDisponibilidade').fadeOut();
				}, segundos * 1000)
			} else {
				$("#msgAdminDisponibilidade").html("<div class='alert alert-success'><h4>"+data.success+"</h4></div>");
				$("#msgAdminDisponibilidade").fadeIn();
				setTimeout(function(){
					$('#msgAdminDisponibilidade').fadeOut();
				}, segundos * 1000)
				refreshAdmin();
				$("tr[rel="+linha+"]").fadeOut();
				$('#motivo').val('');
				$('#admin_disp option[value=""]').attr('selected', true);
			}
		})
	});

	$("#indisponibilizar").click( function() {
		var admin = $('#admin_disp').val();
		var motivo = $.trim($('#motivo').val());
		var segundos = 3;

		if (admin.length == 0) {
			$("#msgAdminDisponibilidade").html("<div class='alert alert-error'><h4>Escolha um Admin!</h4></div>");
			$("#msgAdminDisponibilidade").fadeIn();
			setTimeout(function(){
				$('#msgAdminDisponibilidade').fadeOut();
			}, segundos * 1000);
		} else if (motivo.length == 0) {
			$("#msgAdminDisponibilidade").html("<div class='alert alert-error'><h4>Escreva um motivo!</h4></div>");
			$("#msgAdminDisponibilidade").fadeIn();
			setTimeout(function(){
				$('#msgAdminDisponibilidade').fadeOut();
			}, segundos * 1000);
		} else {
			$.ajax({
				url: 'helpdesk_posto_autorizado_listar.php',
				type: 'POST',
				data: { indisponibilizar: true, admin: admin, motivo: motivo }
			}).done(function(data) {
				data = $.parseJSON(data);

				if (data.error) {
					$("#msgAdminDisponibilidade").html("<div class='alert alert-error'><h4>"+data.error+"</h4></div>");
					$("#msgAdminDisponibilidade").fadeIn();
					setTimeout(function(){
						$('#msgAdminDisponibilidade').fadeOut();
					}, segundos * 1000)
				} else {
					$("#msgAdminDisponibilidade").html("<div class='alert alert-success'><h4>"+data.success+"</h4></div>");
					$("#msgAdminDisponibilidade").fadeIn();
					setTimeout(function(){
						$('#msgAdminDisponibilidade').fadeOut();
					}, segundos * 1000)
					refreshAdmin();
					var registros = $("#pesquisa_admin_indisponivel > tbody").find("tr").length;
					$('#pesquisa_admin_indisponivel > tbody:last-child').append('<tr rel="'+registros+'">\
					<input type="hidden" id="admin_'+registros+'" value="'+data.admin+'">\
					<td class="tac">'+data.nome_completo+'</td>\
					<td class="tal">'+data.motivo+'</td>\
					<td class="tac"><button class="btn btn-success disponibilizar" rel="'+registros+'">Disponibilizar</button></td>\
					</tr>');
					$('#motivo').val('');
					$('#admin_disp option[value=""]').attr('selected', true);
				}
			})
		}

		event.preventDefault();
	});

	Shadowbox.init();

	$("span[rel=lupa]").click(function() {
		$.lupa($(this));
	});
	$("#gerar_grafico").click(function() {
		var exibir_novo_grafico = $("#status").val();

		if (exibir_novo_grafico == "Ag. Fábrica") {
			$("#novo_grafico").toggle();
		}

		$("#grafico").toggle();
	});

	var table = new Object();
	table['table'] = '#table_atendimento';
	table['type'] = 'basic';
	$.dataTableLoad(table);

});

<?php if (in_array($login_fabrica, array(30,35,72,151,203))) : ?>
	function gera_csv() {
	    var protocolo = $("#protocolo").val();
	    var data_inicial = $("#data_inicial").val();
	    var data_final = $("#data_final").val();
	    var status = $("#status").val();
	    var tipo_solicitacao = $("#tipo_solicitacao").val();
	    var atendente = $("#atendente").val();
	    var posto_codigo = $("#posto_codigo").val();
	    var posto_nome = $("#posto_nome").val();
	    <?php if (in_array($login_fabrica, [30])) { ?>
	    	var estado = $("#estado").val();
	    <?php } ?>



	    $.ajax({
	        type: 'POST',
	        url: 'helpdesk_posto_autorizado_listar.php',
	        data: {
	            pesquisar_helpdesk: "Pesquisar",
	            csv: true,
	            protocolo: protocolo,
	            data_inicial: data_inicial,
	            data_final: data_final,
	            status: status,
	            tipo_solicitacao: tipo_solicitacao,
	            atendente: atendente,
	            posto_codigo: posto_codigo,
	            posto_nome: posto_nome
				<?php if (in_array($login_fabrica, [30])) { ?>
	    			,
	            	estado: estado
	    		<?php } ?>	            
	        },
	    }).done(function(data) {
	    	console.log(data);
	        location = data;
	    });
	}
<?php endif;
	
/*HD - 4275929*/
if ($login_fabrica == 35) {?>

	function gerarRelatorioInteracoes() {
		var protocolo = $("#protocolo").val();
	    var data_inicial = $("#data_inicial").val();
	    var data_final = $("#data_final").val();
	    var status = $("#status").val();
	    var tipo_solicitacao = $("#tipo_solicitacao").val();
	    var atendente = $("#atendente").val();
	    var posto_codigo = $("#posto_codigo").val();
	    var posto_nome = $("#posto_nome").val();

	    alert("Aguarde, gerando o relatório.")

		$.ajax({
	        type: 'POST',
	        url: 'helpdesk_posto_autorizado_listar.php',
	        data: {
	            pesquisar_helpdesk: "Pesquisar",
	            gerarRelatorioInteracoes: true,
	            protocolo: protocolo,
	            data_inicial: data_inicial,
	            data_final: data_final,
	            status: status,
	            tipo_solicitacao: tipo_solicitacao,
	            atendente: atendente,
	            posto_codigo: posto_codigo,
	            posto_nome: posto_nome
	        },
	    }).done(function(data) {
	        location = data;
	    });
	}

<?php } ?>

function refreshAdmin() {
	$.ajax({
		url: "helpdesk_posto_autorizado_listar.php",
		type: "POST",
		data: { admin_refresh: true },
	}).done(function(data) {
		data = $.parseJSON(data);
		$("#admin_disp").find("option").remove();
		$("#admin_disp").append("<option value=''>Escolha</option>");
		$.each(data, function (admin, nome_completo) {
			$("#admin_disp").append("<option value='"+admin+"'>"+nome_completo+"</option>");
		});
	})
}

/**
 * Função de retorno da lupa do posto
 */
function retorna_posto(retorno) {
	/**
	 * A função define os campos código e nome como readonly e esconde o botão
	 * O posto somente pode ser alterado quando clicar no botão trocar_posto
	 * O evento do botão trocar_posto remove o readonly dos campos e dá um show nas lupas
	 */
	$("#posto_id").val(retorno.posto);
	$("#posto_codigo").val(retorno.codigo);
	$("#posto_nome").val(retorno.nome);
	$("#div_trocar_posto").show();
	$("#div_informacoes_posto").find("span[rel=lupa]").hide();
}

</script>
<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <br/>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?
}
?>

<div class="tc_formulario" >
	<div class="titulo_tabela"><?= (in_array($login_fabrica, [169,170,198])) ? 'HELP-DESK INTERNO' : 'HELP-DESK DO POSTO AUTORIZADO'; ?></div>
	<br />
	<a target="_blank" class="btn btn-success" id="btn_abrir_atendimento" href="helpdesk_posto_autorizado_novo_atendimento.php"><?=($login_fabrica == 30)? 'ABRIR HELP-DESK' : 'Abrir novo help-desk'?></a>
	<br />
	<br />

	<?
	if ($areaAdmin === true) {
	?>
		<div id="atendimentos_dashboard" class="accordion" >

			<?
			$andStatus = (in_array($login_fabrica, [198])) ? "AND tbl_hd_chamado.status IN('Ag. Fábrica')" : "AND tbl_hd_chamado.status IN('Ag. Posto', 'Ag. Fábrica')";

			$sql = "SELECT
						tbl_hd_chamado.hd_chamado,
						tbl_hd_chamado.status,
						TO_CHAR(tbl_hd_chamado.data, 'DD/MM/YYYY HH24:MI') AS data,
						tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome AS posto,
						tbl_posto_fabrica.contato_estado AS uf, 
						tbl_tipo_solicitacao.descricao AS tipo_solicitacao,
						tbl_admin.nome_completo AS atendente,
						TO_CHAR(tbl_hd_chamado.data_providencia, 'DD/MM/YYYY') AS data_providencia,
						TO_CHAR(MAX(tbl_hd_chamado_item.data), 'DD/MM/YYYY HH24:MI') AS ultima_interacao
					FROM tbl_hd_chamado
					INNER JOIN tbl_tipo_solicitacao ON tbl_tipo_solicitacao.tipo_solicitacao = tbl_hd_chamado.tipo_solicitacao AND tbl_tipo_solicitacao.fabrica = {$login_fabrica}
					INNER JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.atendente AND tbl_admin.fabrica = {$login_fabrica}
					INNER JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
					INNER JOIN tbl_posto ON tbl_posto.posto = tbl_hd_chamado.posto
					INNER JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
					WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
					AND tbl_hd_chamado.atendente = {$login_admin}
					{$andStatus}
					GROUP BY
						tbl_hd_chamado.hd_chamado,
						tbl_hd_chamado.status,
						tbl_hd_chamado.data,
						tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome,
						tbl_posto_fabrica.contato_estado, 
						tbl_tipo_solicitacao.descricao,
						tbl_admin.nome_completo,
						tbl_hd_chamado.data_providencia
					ORDER BY tbl_hd_chamado.data DESC";

			$res = pg_query($con, $sql);

			$atendimentos = array(
				"ag_posto"     => array_filter(pg_fetch_all($res), function($a) { return ($a["status"] == "Ag. Posto"); }),
				"ag_fabrica"   => array_filter(pg_fetch_all($res), function($a) { return ($a["status"] == "Ag. Fábrica"); })
			);

			if (count($atendimentos["ag_fabrica"]) > 0 && !in_array($login_fabrica, [169,170])) {
			?>
				<div class="accordion-group">
					<div class="accordion-heading">
						<a class="accordion-toggle" data-toggle="collapse" data-parent="#atendimentos_dashboard" href="#ag_fabrica">
							Ag. Fábrica <span class="badge badge-inverse"><?=count($atendimentos["ag_fabrica"])?></span>
						</a>
					</div>
					<div id="ag_fabrica" class="accordion-body collapse">
						<div class="accordion-inner">
							<table class="table table-bordered table-striped">
								<thead>
									<tr class="titulo_coluna" >
										<th>Nº Help-Desk</th>
										<th>Status</th>
										<th>Data</th>
										<?php if (in_array($login_fabrica, array(72))) { ?>
											<th>Código Posto</th>
										<?php }?>
										<th>Posto Autorizado</th>
                                        <th>UF do Posto</th>
										<th>Tipo de Solicitação</th>
										<?php if (in_array($login_fabrica, array(30,72))) { ?>
											<th>Data Retorno</th>
										<?php }?>
										<th>Atendente</th>
										<th>Última interação</th>
									</tr>
								</thead>
								<tbody>
									<?
									foreach ($atendimentos["ag_fabrica"] as $atendimento) {
										$atendimento = (object) $atendimento;
										?>
										<tr>
											<td><a target="_blank" href="helpdesk_posto_autorizado_atendimento.php?hd_chamado=<?=$atendimento->hd_chamado?>" ><?=$atendimento->hd_chamado?></a></td>
											<td><?=$atendimento->status?></td>
											<td><?=$atendimento->data?></td>
											<?php if (in_array($login_fabrica, array(72))) { ?>
												<td><?=$atendimento->codigo_posto?></td>
											<?php }?>
											<td><?=$atendimento->posto?></td>
											<td><?=$atendimento->uf?></td>
											<td><?=$atendimento->tipo_solicitacao?></td>
											<?php if (in_array($login_fabrica, array(30,72))) { ?>
												<td><?=$atendimento->data_providencia?></td>
											<?php }?>
											<td><?=$atendimento->atendente?></td>
											<td><?=$atendimento->ultima_interacao?></td>
										</tr>
									<?
									}
									?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			<?
			}

			if (count($atendimentos["ag_posto"]) > 0 && !in_array($login_fabrica, [169,170,198])) {
			?>
				<div class="accordion-group">
					<div class="accordion-heading">
						<a class="accordion-toggle" data-toggle="collapse" data-parent="#atendimentos_dashboard" href="#ag_posto">
							Ag. Posto <span class="badge badge-warning"><?=count($atendimentos["ag_posto"])?></span>
						</a>
					</div>
					<div style="height: 0px;" id="ag_posto" class="accordion-body collapse">
						<div class="accordion-inner">
							<table class="table table-bordered table-striped">
								<thead>
									<tr class="titulo_coluna" >
										<th>Nº Help-Desk</th>
										<th>Status</th>
										<th>Data</th>
										<?php if (in_array($login_fabrica, array(72))) { ?>
											<th>Código Posto</th>
										<?php }?>
										<th>Posto Autorizado</th>
										<th>Tipo de Solicitação</th>
										<?php if (in_array($login_fabrica, array(30,72))) { ?>
											<th>Data Retorno</th>
										<?php }?>
										<th>Atendente</th>
										<th>Última interação</th>
									</tr>
								</thead>
								<tbody>
									<?
									foreach ($atendimentos["ag_posto"] as $atendimento) {
										$atendimento = (object) $atendimento;
										?>
										<tr>
											<td><a target="_blank" href="helpdesk_posto_autorizado_atendimento.php?hd_chamado=<?=$atendimento->hd_chamado?>" ><?=$atendimento->hd_chamado?></a></td>
											<td><?=$atendimento->status?></td>
											<td><?=$atendimento->data?></td>
											<?php if (in_array($login_fabrica, array(72))) { ?>
												<td><?=$atendimento->codigo_posto?></td>
											<?php }?>
											<td><?=$atendimento->posto?></td>
											<td><?=$atendimento->tipo_solicitacao?></td>
											<?php if (in_array($login_fabrica, array(30,72))) { ?>
												<td><?=$atendimento->data_providencia?></td>
											<?php }?>
											<td><?=$atendimento->atendente?></td>
											<td><?=$atendimento->ultima_interacao?></td>
										</tr>
									<?
									}
									?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			<?
			}
			?>
		</div>
	<?
	} else {
	?>
		<div id="atendimentos_dashboard" class="accordion" >
			<?
			$andStatus = (in_array($login_fabrica, [198])) ? "AND tbl_hd_chamado.status IN('Ag. Conclusão', 'Ag. Fábrica')" : "AND tbl_hd_chamado.status IN('Ag. Conclusão', 'Ag. Posto', 'Ag. Fábrica')";

			$sql = "SELECT
						tbl_hd_chamado.hd_chamado,
						tbl_hd_chamado.status,
						TO_CHAR(tbl_hd_chamado.data, 'DD/MM/YYYY HH24:MI') AS data,
						tbl_tipo_solicitacao.descricao AS tipo_solicitacao,
						tbl_admin.nome_completo AS atendente,
						TO_CHAR(tbl_hd_chamado.data_providencia, 'DD/MM/YYYY') AS data_providencia,
						TO_CHAR(MAX(tbl_hd_chamado_item.data), 'DD/MM/YYYY HH24:MI') AS ultima_interacao
					FROM tbl_hd_chamado
					INNER JOIN tbl_tipo_solicitacao ON tbl_tipo_solicitacao.tipo_solicitacao = tbl_hd_chamado.tipo_solicitacao AND tbl_tipo_solicitacao.fabrica = {$login_fabrica}
					INNER JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.atendente AND tbl_admin.fabrica = {$login_fabrica}
					INNER JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
					WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
					AND tbl_hd_chamado.posto = {$login_posto}
					{$andStatus}
					AND (tbl_tipo_solicitacao.codigo <> 'I' OR tbl_tipo_solicitacao.codigo IS NULL)
					GROUP BY
						tbl_hd_chamado.hd_chamado,
						tbl_hd_chamado.status,
						tbl_hd_chamado.data,
						tbl_tipo_solicitacao.descricao,
						tbl_admin.nome_completo,
						tbl_hd_chamado.data_providencia
					ORDER BY tbl_hd_chamado.data DESC";
			$res = pg_query($con, $sql);

			$atendimentos = array(
				"ag_conclusao" => array_filter(pg_fetch_all($res), function($a) { return ($a["status"] == "Ag. Conclusão"); }),
				"ag_posto"     => array_filter(pg_fetch_all($res), function($a) { return ($a["status"] == "Ag. Posto"); }),
				"ag_fabrica"   => array_filter(pg_fetch_all($res), function($a) { return ($a["status"] == "Ag. Fábrica"); })
			);

			if (count($atendimentos["ag_conclusao"]) > 0) {
			?>
				<div class="accordion-group">
					<div class="accordion-heading">
						<a class="accordion-toggle" data-toggle="collapse" data-parent="#atendimentos_dashboard" href="#ag_conclusao">
							Ag. Conclusão <span class="badge badge-success"><?=count($atendimentos["ag_conclusao"])?></span>
						</a>
					</div>
					<div style="height: auto;" id="ag_conclusao" class="accordion-body in collapse">
						<div class="accordion-inner">
							<table class="table table-bordered table-striped">
								<thead>
									<tr class="titulo_coluna" >
										<th>Nº Help-Desk</th>
										<th>Status</th>
										<th>Data</th>
										<th>Tipo de Solicitação</th>
										<?php if (in_array($login_fabrica, array(30,72))) {?>
											<th>Data Retorno</th>
										<?php }?>
										<th>Atendente</th>
										<th>Última interação</th>
									</tr>
								</thead>
								<tbody>
									<?
									foreach ($atendimentos["ag_conclusao"] as $atendimento) {
										$atendimento = (object) $atendimento;
										?>
										<tr>
											<td><a target="_blank" href="helpdesk_posto_autorizado_atendimento.php?hd_chamado=<?=$atendimento->hd_chamado?>" ><?=$atendimento->hd_chamado?></a></td>
											<td><?=$atendimento->status?></td>
											<td><?=$atendimento->data?></td>
											<td><?=$atendimento->tipo_solicitacao?></td>
											<?php if (in_array($login_fabrica, array(30,72))) {?>
												<td><?=$atendimento->data_providencia?></td>
											<?php }?>
											<td><?=$atendimento->atendente?></td>
											<td><?=$atendimento->ultima_interacao?></td>
										</tr>
									<?
									}
									?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			<?
			}

			if (count($atendimentos["ag_posto"]) > 0 && !in_array($login_fabrica, [198])) {
			?>
				<div class="accordion-group">
					<div class="accordion-heading">
						<a class="accordion-toggle" data-toggle="collapse" data-parent="#atendimentos_dashboard" href="#ag_posto">
							Ag. Posto <span class="badge badge-warning"><?=count($atendimentos["ag_posto"])?></span>
						</a>
					</div>
					<div style="height: 0px;" id="ag_posto" class="accordion-body collapse">
						<div class="accordion-inner">
							<table class="table table-bordered table-striped">
								<thead>
									<tr class="titulo_coluna" >
										<th>Nº Help-Desk</th>
										<th>Status</th>
										<th>Data</th>
										<th>Tipo de Solicitação</th>
										<?php if (in_array($login_fabrica, array(30,72))) {?>
											<th>Data Retorno</th>
										<?php }?>
										<th>Atendente</th>
										<th>Última interação</th>
									</tr>
								</thead>
								<tbody>
									<?
									foreach ($atendimentos["ag_posto"] as $atendimento) {
										$atendimento = (object) $atendimento;
										?>
										<tr>
											<td><a target="_blank" href="helpdesk_posto_autorizado_atendimento.php?hd_chamado=<?=$atendimento->hd_chamado?>" ><?=$atendimento->hd_chamado?></a></td>
											<td><?=$atendimento->status?></td>
											<td><?=$atendimento->data?></td>
											<td><?=$atendimento->tipo_solicitacao?></td>
											<?php if (in_array($login_fabrica, array(30,72))) {?>
												<td><?=$atendimento->data_providencia?></td>
											<?php }?>
											<td><?=$atendimento->atendente?></td>
											<td><?=$atendimento->ultima_interacao?></td>
										</tr>
									<?
									}
									?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			<?php
			}

			if (count($atendimentos["ag_fabrica"]) > 0) {
			?>
				<div class="accordion-group">
					<div class="accordion-heading">
						<a class="accordion-toggle" data-toggle="collapse" data-parent="#atendimentos_dashboard" href="#ag_fabrica">
							Ag. Fábrica <span class="badge badge-inverse"><?=count($atendimentos["ag_fabrica"])?></span>
						</a>
					</div>
					<div id="ag_fabrica" class="accordion-body collapse">
						<div class="accordion-inner">
							<table class="table table-bordered table-striped">
								<thead>
									<tr class="titulo_coluna" >
										<th>Nº Help-Desk</th>
										<th>Status</th>
										<th>Data</th>
										<th>Tipo de Solicitação</th>
										<?php if (in_array($login_fabrica, array(30,72))) {?>
											<th>Data Retorno</th>
										<?php }?>
										<th>Atendente</th>
										<th>Última interação</th>
									</tr>
								</thead>
								<tbody>
									<?
									foreach ($atendimentos["ag_fabrica"] as $atendimento) {
										$atendimento = (object) $atendimento;
										?>
										<tr>
											<td><a target="_blank" href="helpdesk_posto_autorizado_atendimento.php?hd_chamado=<?=$atendimento->hd_chamado?>" ><?=$atendimento->hd_chamado?></a></td>
											<td><?=$atendimento->status?></td>
											<td><?=$atendimento->data?></td>
											<td><?=$atendimento->tipo_solicitacao?></td>
											<?php if (in_array($login_fabrica, array(30,72))) {?>
												<td><?=$atendimento->data_providencia?></td>
											<?php }?>
											<td><?=$atendimento->atendente?></td>
											<td><?=$atendimento->ultima_interacao?></td>
										</tr>
									<?
									}
									?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			<?
			}
			?>
		</div>

		<br />
	<?
	}
	?>

	<form method="POST" action="<?echo $PHP_SELF?>" name="frm_pesquisa_atendimento" align='center' class='form-search form-inline tc_formulario'>
		<div class="titulo_tabela">Informações da Pesquisa</div>

		<br />

		<div class='row-fluid'>
			<div class="span1"></div>

			<div class="span2">
				<div class='control-group'>
					<label class="control-label" for="protocolo">Nº Help-Desk</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="protocolo" name="protocolo" class="span12" type="text" value="<?=getValue('protocolo')?>" />
						</div>
					</div>
				</div>
			</div>
			<div class="span2">
				<div class='control-group <?=(in_array('data_inicial', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="data_inicial">Data Inicial</label>
					<div class="controls controls-row">
						<div class="span12">
							<h5 class='asteristico'>*</h5>
							<input id="data_inicial" name="data_inicial" class="span12" type="text" value="<?=getValue('data_inicial')?>" autocomplete="off"/>
						</div>
					</div>
				</div>
			</div>

			<div class="span2">
				<div class='control-group <?=(in_array('data_final', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="data_final">Data Final</label>
					<div class="controls controls-row">
						<div class="span12">
							<h5 class='asteristico'>*</h5>
							<input id="data_final" name="data_final" class="span12" type="text" value="<?=getValue('data_final')?>" autocomplete="off" />
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class='row-fluid'>
			<div class="span1"></div>

			<div class="span3">
				<div class='control-group' >
					<label class='control-label' for='status'>Status</label>
					<div class="controls controls-row">
						<div class="span12">
							<select id="status" name="status" class="span12" >
								<option value="" >Selecione</option>
								<?php
								if (!in_array($login_fabrica, [169,170])) {
								?>
									<?php if (!in_array($login_fabrica, [198])) { ?>
										<option value="Ag. Posto" <?=(getValue("status") == "Ag. Posto") ? "selected" : ""?> >Ag. Posto</option>
									<?php } ?>
									<option value="Ag. Fábrica" <?=(getValue("status") == "Ag. Fábrica") ? "selected" : ""?> >Ag. Fábrica</option>
									<option value="Ag. Conclusão" <?=(getValue("status") == "Ag. Conclusão") ? "selected" : ""?> >Ag. Conclusão</option>
									<option value="Ag. Finalização" <?=(getValue("status") == "Ag. Finalização") ? "selected" : ""?> >Ag. Finalização</option>
									<option value="Finalizado" <?=(getValue("status") == "Finalizado") ? "selected" : ""?> >Finalizado</option>
									<option value="Cancelado" <?=(getValue("status") == "Cancelado") ? "selected" : ""?> >Cancelado</option>
								<?php
								} else { ?>
									<option value="Call Center" <?=(getValue("status") == "Call Center") ? "selected" : ""?> >Call Center</option>
									<option value="Eng. Servicos" <?=(getValue("status") == "Eng. Servicos") ? "selected" : ""?> >Eng. Serviços</option>
									<option value="Finalizado" <?=(getValue("status") == "Finalizado") ? "selected" : ""?> >Finalizado</option>
									<option value="Cancelado" <?=(getValue("status") == "Cancelado") ? "selected" : ""?> >Cancelado</option>
								<?php
								}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>

			<div class="span3">
				<div class='control-group' >
					<label class='control-label' for='tipo_solicitacao'>Tipo de Solicitação</label>
					<div class="controls controls-row">
						<div class="span12">
							<select id="tipo_solicitacao" name="tipo_solicitacao" class="span12" >
								<option value="" >Selecione</option>
								<?php
								$where = "";
								if ($areaAdmin == false) {
									$where = "AND codigo IS NULL";
								}
								$sql = "SELECT tipo_solicitacao, descricao
										FROM tbl_tipo_solicitacao
										WHERE fabrica = {$login_fabrica}
										AND ativo IS TRUE {$where}
										ORDER BY descricao ASC";
								$res = pg_query($con, $sql);

								if (pg_num_rows($res) > 0) {
									while ($tipo_solicitacao = pg_fetch_object($res)) {
										$selected = ($tipo_solicitacao->tipo_solicitacao == getValue("tipo_solicitacao")) ? "selected" : "";

										echo "<option value='{$tipo_solicitacao->tipo_solicitacao}' {$selected} >{$tipo_solicitacao->descricao}</option>";
									}
								}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>

			<?php
			if ($areaAdmin === true) {
			?>
				<div class="span3">
					<div class='control-group' >
						<label class='control-label' for='atendente'>Atendente</label>
						<div class="controls controls-row">
							<div class="span12">
								<select id="atendente" name="atendente" class="span12" >
									<option value="" >Selecione</option>
									<?php
									$cond = ($login_fabrica == 151) ? " AND callcenter_supervisor IS TRUE " : " AND admin_sap IS TRUE ";

									if (in_array($login_fabrica, [169,170])) {
										$cond = "AND JSON_FIELD('suporte_tecnico',parametros_adicionais) = 't'";
									}

									$sql = "SELECT admin, nome_completo
											FROM tbl_admin
											WHERE fabrica = {$login_fabrica}
											AND ativo IS TRUE
											$cond
											ORDER BY nome_completo ASC";
									$res = pg_query($con, $sql);
									
									if (pg_num_rows($res) > 0) {
										while ($atendente = pg_fetch_object($res)) {
											if(!isset($_POST["pesquisar_helpdesk"])){
												$selected = ($atendente->admin == $login_admin) ? "selected" : "";
											}else{
												$selected = ($atendente->admin == getValue("atendente")) ? "selected" : "";
											}

											echo "<option value='{$atendente->admin}' {$selected} >{$atendente->nome_completo}</option>";
										}
									}
									?>
								</select>
							</div>
						</div>
					</div>
				</div>
			<?php
			}
			?>
		</div>
		<?php
		if ($areaAdmin === true && !in_array($login_fabrica, [169,170,198])) {
		?>
			<div class='row-fluid'>
				<div class='span1'></div>
				<input type="hidden" id="posto" name="posto" value="<?=$posto?>" />
				<div class="span2">
					<div class='control-group' >
							<label class="control-label" for="posto_codigo">Código do Posto</label>
							<div class="controls controls-row">
								<div class="span10 input-append">
									<input id="posto_codigo" name="posto[codigo]" class="span12" type="text" value="<?=getValue('posto[codigo]')?>" <?=$posto_readonly?> />
									<span class="add-on" rel="lupa" <?=$posto_esconde_lupa?> >
										<i class="icon-search"></i>
									</span>
									<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
								</div>
							</div>
						</div>
				</div>
				<div class="span4">
					<div class='control-group' >
							<label class="control-label" for="posto_nome">Nome do Posto</label>
							<div class="controls controls-row">
								<div class="span10 input-append">
									<input id="posto_nome" name="posto[nome]" class="span12" type="text" value="<?=getValue('posto[nome]')?>" <?=$posto_readonly?> />
									<span class="add-on" rel="lupa" <?=$posto_esconde_lupa?> >
										<i class="icon-search"></i>
									</span>
									<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
								</div>
							</div>
						</div>
				</div>
				<?php if (in_array($login_fabrica, [30])) { ?>
					<div class="span4">
						<div class='control-group' >
							<label class='control-label' for='estado'>Estado</label>
							<div class='controls controls-row'>
								<select name="estado" class='span12' id="estado">
										<option value=""   <? if (strlen($estado) == 0)    echo " selected "; ?>>TODOS OS ESTADOS</option>
										<option value="centro-oeste" <? if ($estado == "centro-oeste") echo " selected "; ?>>Região Centro-Oeste (GO,MT,MS,DF)</option>
										<option value="nordeste"     <? if ($estado == "nordeste")     echo " selected "; ?>>Região Nordeste (MA,PI,CE,RN,PB,PE,AL,SE,BA)</option>
										<option value="norte"        <? if ($estado == "norte")        echo " selected "; ?>>Região Norte (AC,AM,RR,RO,PA,AP,TO)</option>
										<option value="sudeste"      <? if ($estado == "sudeste")      echo " selected "; ?>>Região Sudeste (MG,ES,RJ,SP)</option>
										<option value="sul"          <? if ($estado == "sul")          echo " selected "; ?>>Região Sul (PR,SC,RS)</option>
										<option value="AC" <? if ($estado == "AC") echo " selected "; ?>>AC - Acre</option>
										<option value="AL" <? if ($estado == "AL") echo " selected "; ?>>AL - Alagoas</option>
										<option value="AM" <? if ($estado == "AM") echo " selected "; ?>>AM - Amazonas</option>
										<option value="AP" <? if ($estado == "AP") echo " selected "; ?>>AP - Amapá</option>
										<option value="BA" <? if ($estado == "BA") echo " selected "; ?>>BA - Bahia</option>
										<option value="CE" <? if ($estado == "CE") echo " selected "; ?>>CE - Ceará</option>
										<option value="DF" <? if ($estado == "DF") echo " selected "; ?>>DF - Distrito Federal</option>
										<option value="ES" <? if ($estado == "ES") echo " selected "; ?>>ES - Espírito Santo</option>
										<option value="GO" <? if ($estado == "GO") echo " selected "; ?>>GO - Goiás</option>
										<option value="MA" <? if ($estado == "MA") echo " selected "; ?>>MA - Maranhão</option>
										<option value="MG" <? if ($estado == "MG") echo " selected "; ?>>MG - Minas Gerais</option>
										<option value="MS" <? if ($estado == "MS") echo " selected "; ?>>MS - Mato Grosso do Sul</option>
										<option value="MT" <? if ($estado == "MT") echo " selected "; ?>>MT - Mato Grosso</option>
										<option value="PA" <? if ($estado == "PA") echo " selected "; ?>>PA - Pará</option>
										<option value="PB" <? if ($estado == "PB") echo " selected "; ?>>PB - Paraíba</option>
										<option value="PE" <? if ($estado == "PE") echo " selected "; ?>>PE - Pernambuco</option>
										<option value="PI" <? if ($estado == "PI") echo " selected "; ?>>PI - Piauí</option>
										<option value="PR" <? if ($estado == "PR") echo " selected "; ?>>PR - Paraná</option>
										<option value="RJ" <? if ($estado == "RJ") echo " selected "; ?>>RJ - Rio de Janeiro</option>
										<option value="RN" <? if ($estado == "RN") echo " selected "; ?>>RN - Rio Grande do Norte</option>
										<option value="RO" <? if ($estado == "RO") echo " selected "; ?>>RO - Rondônia</option>
										<option value="RR" <? if ($estado == "RR") echo " selected "; ?>>RR - Roraima</option>
										<option value="RS" <? if ($estado == "RS") echo " selected "; ?>>RS - Rio Grande do Sul</option>
										<option value="SC" <? if ($estado == "SC") echo " selected "; ?>>SC - Santa Catarina</option>
										<option value="SE" <? if ($estado == "SE") echo " selected "; ?>>SE - Sergipe</option>
										<option value="SP" <? if ($estado == "SP") echo " selected "; ?>>SP - São Paulo</option>
										<option value="TO" <? if ($estado == "TO") echo " selected "; ?>>TO - Tocantins</option>
									</select>
							</div>
						</div>
					</div>
				<?php } ?>
				<div class='span1'></div>
			</div>
		<?php
		}

        if (in_array($login_fabrica, [169,170])) { 
            ?>
            <div class="row-fluid">
                <div class="span1"></div>
                <div class="span4">
                    <div class='control-group <?=(in_array('providencia', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="providencia" >Providência</label>
                        <div class="controls controls-row" >
                            <div class="span12" >
                                <select class="span12" id="providencia" name="providencia" >
                                    <option value="">Selecione</option>
                                    <?php
                                    foreach ($arrProvidencia as $codigoProvidencia => $descricaoProvidencia) { 

                                        $selected = (getValue("providencia") == $codigoProvidencia) ? "selected" : "";
                                        ?>
                                        <option value="<?= $codigoProvidencia ?>" <?= $selected ?>><?= $descricaoProvidencia ?></option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span4">
                    <div class='control-group <?=(in_array('sub_item', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="sub_item" >Sub-item</label>
                        <div class="controls controls-row" >
                            <div class="span12" >
                                <select class="span12" id="sub_item" name="sub_item" >
                                    <option value="">Selecione</option>
                                    <?php
                                    foreach ($arrSubItem as $codigoSubItem => $descricaoSubItem) { 

                                        $selected = (getValue("sub_item") == $codigoSubItem) ? "selected" : "";
                                        ?>
                                        <option value="<?= $codigoSubItem ?>" <?= $selected ?>><?= $descricaoSubItem ?></option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row-fluid">
                <div class="span1"></div>
                <div class="span4">
                    <div class='control-group <?=(in_array('origem', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="origem" >Origem</label>
                        <div class="controls controls-row" >
                            <div class="span12" >
                                <select class="span12" id="origem" name="origem" >
                                    <option value="">Selecione</option>
                                    <?php
                                    foreach ($arrOrigem as $descricaoOrigem) { 

                                        $selected = (getValue("origem") == $descricaoOrigem) ? "selected" : "";
                                        ?>
                                        <option value="<?= $descricaoOrigem ?>" <?= $selected ?>><?= $descricaoOrigem ?></option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php
        } ?>
		<div class='row-fluid'>
			<div class='span5'></div>
			<div class="span2">
				<div class='control-group' >
					<div class="controls controls-row">
						<div class="span10 input-append">
							<button type="submit" id="pesquisar" name="pesquisar" class="btn" style="webkit-border-radius: 0 0 0 0 !important; border-radius: 0 0 0 0 !important;" >Pesquisar</button>
							<input type="hidden" name="pesquisar_helpdesk" value="pesquisar_helpdesk" />
						</div>
					</div>
				</div>
			</div>
			<div class='span1'></div>
		</div>
	</form>
</div>
</div>
<?
if (pg_num_rows($resPesquisa) > 0) { ?>
	<table id="table_atendimento" class="table table-bordered table-fixed">
		<thead>
			<tr class="titulo_coluna" >
				<th colspan="100" >Atendimentos</th>
			</tr>
			<tr class="titulo_coluna">
				<th>Nº Help-Desk</th>
				<th>Status</th>
				<th>Data</th>
				<?php
				if($areaAdmin === true && !in_array($login_fabrica, [169,170])){ ?>
					<?php if ($login_fabrica == 72) {?>
						<th>Código Posto</th>
					<?php }?>
					<th>Posto</th>
                    <?php if(in_array($login_fabrica, [30])): ?>
                        <th>Cliente</th>
                    <?php endif; ?>
					<?php
					if (in_array($login_fabrica, [30, 35])) { ?>
						<th>UF</th>
					<?php
					}
				} 

				if (in_array($login_fabrica, [169,170])) { ?>
					<th>Providência</th>
					<th>Sub-item</th>
					<th>Origem</th>
				<?php
				}
				?>
				<th>Tipo de Solicitação</th>
				<?php if (in_array($login_fabrica, array(30,72))) {?>
					<th>Data Retorno</th>
				<?php }?>
				<th>Atendente</th>
				<th>Última Interação</th>
				<?php

				if (in_array($login_fabrica, [169,170])) { ?>
					<th>Tempo em Aberto</th>
				<?php
				}

				if (in_array($login_fabrica, [35])) { ?>
					<th>Tempo Atendimento</th>
					<th>Referência</th>
					<th>Peças</th>
					<th>O.S</th>
					<th>Pedido</th>
					<th>Motivo</th>
					<th>Ticket de Atendimento</th>
					<th>Código Localizador</th>
					<th>Pré-Logística</th>
				<?php
				} ?>
			</tr>
		</thead>
		<tbody>
			<?
			$array_grafico      = array();
			$novo_array_grafico = array();

			if (!empty($_POST["status"])) { /*HD - 4275929*/
				$filtrar_status = true;
			} else {
				$filtrar_status = false;
			}

			while($objeto_atendimento = pg_fetch_object($resPesquisa)){

				$jsonArrayCampos = json_decode($objeto_atendimento->array_campos_adicionais, true);

				if (in_array($login_fabrica, [169,170])) {
					$corLinha = ($jsonArrayCampos["sub_item"] == "05") ? "red" : "white";
				} ?>
				<tr style="background-color: <?= $corLinha ?>;">
					<td><a target="_blank" href="helpdesk_posto_autorizado_atendimento.php?hd_chamado=<?=$objeto_atendimento->hd_chamado?>" ><?=$objeto_atendimento->hd_chamado?></a></td>
					<td><?=$objeto_atendimento->status?></td>
					<td><?=$objeto_atendimento->data?></td>
					<?php
					if ($login_fabrica == 35) {
						$aux_status = $objeto_atendimento->status;

						if ($aux_status == "Finalizado" && $filtrar_status == true) {
							$aux_sql = "SELECT status_item, data FROM tbl_hd_chamado_item WHERE hd_chamado = $objeto_atendimento->hd_chamado ORDER BY data ASC";
							$aux_res = pg_query($con, $aux_sql);
							$aux_row = pg_num_rows($aux_res);

							if ($aux_row > 0) {
								$aux_total_horas   = 0;
								$aux_total_minutos = 0;

								for ($w=0; $w < $aux_row; $w++) { 
									$aux_status = pg_fetch_result($aux_res, $w, 'status_item');

									if ($aux_status == 'Ag. Fábrica') {
										$aux_data_atual   = date_create(pg_fetch_result($aux_res, $w, 'data'));
										$aux_proxima_data = date_create(pg_fetch_result($aux_res, ($w + 1), 'data'));


										if (!empty($aux_data_atual) > 0 && !empty($aux_proxima_data) > 0) {
											$diff              = $aux_data_atual->diff($aux_proxima_data);
											$horas_diferenca   = $diff->h + ($diff->days * 24);
											$minutos_diferenca = $diff->i;

											$aux_total_horas   += $horas_diferenca;
											$aux_total_minutos += $minutos_diferenca;

											unset($diff, $horas_diferenca, $minutos_diferenca);
										}
									}
								}

								if (!empty($aux_total_horas) || !empty($aux_total_minutos)) {
									if ($aux_total_minutos > 60) {
										$horas_adicionais = 0;

										while ($aux_total_minutos >= 60) {
											$aux_total_minutos = $aux_total_minutos - 60;
											$horas_adicionais++;
										}

										$aux_total_horas += $horas_adicionais;
									}
								}
							}

							if ($aux_total_horas <= 24) {
								$novo_status = "Finalizados em até 24";
							} else {
								$novo_status = "Finalizados após 24 horas";
							}

							$array_grafico[$novo_status][] = $objeto_atendimento->hd_chamado;
						} else if ($aux_status == "Ag. Posto" && $filtrar_status == true) {
							$novo_status = $objeto_atendimento->tipo_solicitacao;
							$array_grafico[$novo_status][] = $objeto_atendimento->hd_chamado;
						} else if ($aux_status == "Ag. Fábrica" && $filtrar_status == true) { 
							$novo_status = $objeto_atendimento->tipo_solicitacao;
							$array_grafico[$novo_status][] = $objeto_atendimento->hd_chamado;

							$aux_atendente = $objeto_atendimento->atendente;
							$novo_array_grafico[$aux_atendente][] = $objeto_atendimento->hd_chamado;
						} else {
							$array_grafico[$aux_status][] = $objeto_atendimento->hd_chamado;
						}
					}
					if($areaAdmin === true && !in_array($login_fabrica, [169,170])){ ?>
						<?php if ($login_fabrica == 72) {?>
							<td><?=$objeto_atendimento->codigo_posto?></td>
						<?php }?>
						<td><?=$objeto_atendimento->posto?></td>
                        <?php if(in_array($login_fabrica, [30])): ?>
                            <td><?=$objeto_atendimento->nome_cliente?></td>
                        <?php endif; ?>
						<?php
						if (in_array($login_fabrica, [30, 35])) { ?>
							<td><?=$objeto_atendimento->estado?></td>
						<?php
						}
					}

					if (in_array($login_fabrica, [169,170])) { 

					?>	
						<td><?= $arrProvidencia[$jsonArrayCampos["providencia"]] ?></td>
						<td>
							<?= $arrSubItem[$jsonArrayCampos["sub_item"]] ?>
						</td>
						<td><?= $objeto_atendimento->origem ?></td>
					<?php
					}
					?>
					<td><?=$objeto_atendimento->tipo_solicitacao?></td>
					<?php if (in_array($login_fabrica, array(30,72))) {?>
						<td><?=$objeto_atendimento->data_providencia?></td>
					<?php }?>
					<td><?=$objeto_atendimento->atendente?></td>
					<td>
						<?php
							if ($login_fabrica == 35) {
								$aux_ult = date_create($objeto_atendimento->ultima_interacao);
								$aux_ult = date_format($aux_ult, 'd-m-Y H:i:s');
								$aux_ult = str_replace("-", "/", $aux_ult);
								echo $aux_ult;
							} else {
								echo $objeto_atendimento->ultima_interacao;
							}
						?>
					</td>
					<?php

					if (in_array($login_fabrica, [169,170])) {

						$dataAbertura = new DateTime( $objeto_atendimento->data2 );

						if ($objeto_atendimento->status == 'Finalizado' || $objeto_atendimento->status == 'Cancelado') {
							$dataFinal = $objeto_atendimento->ultima_interacao2;
						} else {
							$dataFinal = date("Y-m-d h:i:s");
						}

						$dataDiff = $dataAbertura->diff(new DateTime($dataFinal));
						
						$tempoDuracao = ($dataDiff->y > 0)? $dataDiff->format("%y ano(s) "):"";
						$tempoDuracao .= ($dataDiff->m > 0)? $dataDiff->format("%m mês(s) "):"";
						$tempoDuracao .= ($dataDiff->d > 0)? $dataDiff->format("%d dia(s) "):"";
						$tempoDuracao .= ($dataDiff->H > 0)? $dataDiff->format("%H hora(s) "):"";
						$tempoDuracao .= ($dataDiff->i > 0)? $dataDiff->format("%i minuto(s) "):"";
						$tempoDuracao .= ($dataDiff->s > 0 && $dataDiff->i == 0)? $dataDiff->format("%s segundo(s) "):"";
						?>
						<td>
							<?= $tempoDuracao ?>								
						</td>

					<?php	
					}

					if (in_array($login_fabrica, [35])) {

						$dataAbertura = new DateTime( $objeto_atendimento->data2 );
						$dataDiff = $dataAbertura->diff(new DateTime( $objeto_atendimento->ultima_interacao2 ));
						
						if ($objeto_atendimento->status == 'Finalizado' || $objeto_atendimento->status == 'Cancelado') { 
							$tempoDuracao = ($dataDiff->y > 0)? $dataDiff->format("%y ano(s) "):"";
							$tempoDuracao .= ($dataDiff->m > 0)? $dataDiff->format("%m mês(s) "):"";
							$tempoDuracao .= ($dataDiff->d > 0)? $dataDiff->format("%d dia(s) "):"";
							$tempoDuracao .= ($dataDiff->H > 0)? $dataDiff->format("%H hora(s) "):"";
							$tempoDuracao .= ($dataDiff->i > 0)? $dataDiff->format("%i minuto(s) "):"";
							$tempoDuracao .= ($dataDiff->s > 0 && $dataDiff->i == 0)? $dataDiff->format("%s segundo(s) "):"";
							?>
							<td>
								<?=$tempoDuracao?>								
							</td>
						<?
						} else { ?>
							<td></td>
						<?php
						}

						/*HD - 4220316*/
						$aux_sql = "SELECT peca_faltante FROM tbl_hd_chamado_posto WHERE hd_chamado = " . $objeto_atendimento->hd_chamado . "LIMIT 1 ";
						$aux_res = pg_query($con, $aux_sql);
						$aux_sql_pec = pg_fetch_result($aux_res, 0, 'peca_faltante');

						$aux_sql_pec = explode(',', $aux_sql_pec);
						foreach ($aux_sql_pec as $key => $value) {
							$campos = explode("=", $value);
							$aux_sql_pec[$key] = "'".$campos[0]."'";
						}

						$aux_sql = "
							SELECT 
								referencia AS ref_peca_posto,
								descricao AS peca_posto
							FROM
								tbl_peca
							WHERE
								peca IN (" . implode(",", $aux_sql_pec) . ")
						";
						$aux_res = pg_query($con, $aux_sql);
						$aux_tot = (int) pg_num_rows($aux_res);
						unset($aux_sql_pec);

						if ($aux_tot <= 1) {
							$aux_sql_pec = pg_fetch_result($aux_res, 0, 'peca_posto');
							$aux_sql_ref_pec = pg_fetch_result($aux_res, 0, 'ref_peca_posto');
							?> 
							<td class='tal'> <?=$aux_sql_ref_pec;?> </td>
							<td class='tal'> <?=$aux_sql_pec;?> </td> <?
							echo "<td>".$objeto_atendimento->os."</td>";
							echo "<td>".$objeto_atendimento->pedido."</td>";
							echo "<td>".$objeto_atendimento->descricao_motivo."</td>";
							echo "<td>".$jsonArrayCampos['ticket_atendimento']."</td>";
							echo "<td>".$jsonArrayCampos['cod_localizador']."</td>";
							echo "<td>".$jsonArrayCampos['pre_logistica']."</td>";
						} else {
							$repetir_linha = "
								<tr>
									<td>
										<a target='_blank' href='helpdesk_posto_autorizado_atendimento.php?hd_chamado=" . $objeto_atendimento->hd_chamado ."'>". $objeto_atendimento->hd_chamado . "</a>
									</td>
									<td>" . $objeto_atendimento->status . "</td>
									<td>" . $objeto_atendimento->data . "</td>
							";

							if ($areaAdmin === true) {
								$repetir_linha .= "
									<td>" . $objeto_atendimento->posto . "</td>
									<td>" . $objeto_atendimento->estado . "</td>
								";
							}

							$repetir_linha .= "
								<td>" . $objeto_atendimento->tipo_solicitacao ."</td>
								<td>" . $objeto_atendimento->atendente . "</td>
								<td>" . $objeto_atendimento->ultima_interacao . "</td>
								<td>" . $tempoDuracao . "</td>
							";

							for ($z = 0; $z < $aux_tot; $z++) { 
								$aux_sql_pec = pg_fetch_result($aux_res, $z, 'peca_posto');
								$aux_sql_ref_pec = pg_fetch_result($aux_res, $z, 'ref_peca_posto');
								?> 
								<td class='tal'> <?=$aux_sql_ref_pec;?> </td>
								<td class='tal'> <?=$aux_sql_pec;?> </td> 
								<?php
									echo "<td>".$objeto_atendimento->os."</td>";
									echo "<td>".$objeto_atendimento->pedido."</td>";
									echo "<td>".$objeto_atendimento->descricao_motivo."</td>";
								?>
								</tr> <?
								
								if ($z < ($aux_tot - 1)) echo $repetir_linha;
							}
						}

						

					}
				if ($login_fabrica != 35) { ?>
					</tr>
				<? }
			}
			?>
		</tbody>
	</table>

	<?php
    if (in_array($login_fabrica, array(30,35,72,151,203))) {
        echo '<div class="btn_excel" onClick="gera_csv()">  
                <span><img src="imagens/excel.png" /></span>
                <span class="txt">Gerar Arquivo Excel</span>
            </div>';
    }

    if (in_array($login_fabrica, [35])) { ?>
    	<br />
		<div class='row-fluid'>
			<div class='span5'></div>
			<div class="span2">
				<div class='control-group' >
					<div class="controls controls-row">
						<div class="span12">
							<button id="gerar_grafico" name="gerar_grafico" class="btn btn-primary" >Gerar Gráfico</button>
						</div>
					</div>
				</div>
			</div>
			<div class="span4">
				<div class='control-group' >
					<button id="gerar_grafico_interacoes" name="gerar_grafico_interacoes" onclick="gerarRelatorioInteracoes();" class="btn btn-success" >Relatório de Interações</button>
				</div>
			</div>
			<div class='span4'></div>
		</div>
    	<div id="grafico" style="display:none" >    		
			<table id="table_grafico" class="table table-bordered table-striped table-fixed">
				<thead>
					<tr class="titulo_coluna">
						<th>Status</th>
						<th>Qtde</th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ($array_grafico as $grafico_status => $grafico_valor) { ?>
						<tr>
							<td><?=$grafico_status?></td>
							<td><?=count($grafico_valor)?></td>
						</tr>
					<?php
					} ?>
				</tbody>
			</table>

			<div id="grafico_pizza" style="width: 850px !important;"></div>
    	</div>
    	<div id="novo_grafico" style="display:none" >    		
			<table id="novo_table_grafico" class="table table-bordered table-striped table-fixed">
				<thead>
					<tr class="titulo_coluna">
						<th>Status</th>
						<th>Qtde</th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ($novo_array_grafico as $grafico_status => $grafico_valor) { ?>
						<tr>
							<td><?=$grafico_status?></td>
							<td><?=count($grafico_valor)?></td>
						</tr>
					<?php
					} ?>
				</tbody>
			</table>

			<div id="novo_grafico_pizza" style="width: 850px !important;"></div>
    	</div>
    	<script type="text/javascript">
    		/* Inicio gráfico Pizza*/
			if ($("#grafico_pizza").length > 0) {
			    $("#grafico_pizza").highcharts({
			        chart: {
			            plotBackgroundColor: null,
			            plotBorderWidth: null,
			            plotShadow: false,
			            type: "pie"
			        },
			        title: {
			            text: ""
			        },
					plotOptions: {
				        pie: {
				            allowPointSelect: true,
				            cursor: 'pointer',
				            dataLabels: {
				                enabled: true,
				                format: '<b>{point.name}</b>: {point.percentage:.1f}%',
				                style: {
				                    color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
				                }
				            },
            				showInLegend: true
				        }
				    },
				    series: [{
				        name: 'Chamado(s) ',
				        colorByPoint: true,
				        data: [
				        <?php
				        foreach ($array_grafico as $grafico_status => $grafico_valor) { ?>
				        	{
					            name: '<?=$grafico_status?>',
					            y: <?=count($grafico_valor)?>
					        },
					    <?php
				        } ?>
				        ]
				    }]
			    });
			}
			/* Fom gráfico Pizza*/

			if ($("#novo_grafico_pizza").length > 0) {
			    $("#novo_grafico_pizza").highcharts({
			        chart: {
			            plotBackgroundColor: null,
			            plotBorderWidth: null,
			            plotShadow: false,
			            type: "pie"
			        },
			        title: {
			            text: ""
			        },
					plotOptions: {
				        pie: {
				            allowPointSelect: true,
				            cursor: 'pointer',
				            dataLabels: {
				                enabled: true,
				                format: '<b>{point.name}</b>: {point.percentage:.1f}%',
				                style: {
				                    color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
				                }
				            },
            				showInLegend: true
				        }
				    },
				    series: [{
				        name: 'Chamado(s) ',
				        colorByPoint: true,
				        data: [
				        <?php
				        foreach ($novo_array_grafico as $grafico_status => $grafico_valor) { ?>
				        	{
					            name: '<?=$grafico_status?>',
					            y: <?=count($grafico_valor)?>
					        },
					    <?php
				        } ?>
				        ]
				    }]
			    });
			}
    	</script>
    <?php
    }
}

if ($areaAdmin == true && !in_array($login_fabrica, [169,170])) {
?>
<div class="container"> 
	<br />

	<form method="POST" action="<?= $PHP_SELF?>" name="frm_grava_disponibilidade" align='center' class='form-search form-inline tc_formulario'>
		<div class="titulo_tabela">Cadastrar Indisponibilidade Atendente</div>

		<br />

		<div class='row-fluid'>
			<div class="span2"></div>
			<div class="span8">
				<div class='control-group<?=(in_array('admin_disp', $msg_erro['campos'])) ? " error" : "" ?>'>
					<label class="control-label" for="admin_disp">Atendente:</label>
					<div class="controls controls-row">
						<div class="span6">
							<h5 class='asteristico'>*</h5>
							<select id="admin_disp" name="admin_disp">
								<option value="">Escolha</option>
								<? $adminsDisponiveis = selectAdminsDisponiveis();
								foreach ($adminsDisponiveis as $admin) {
								?>
									<option value="<?= $admin['admin']; ?>"><?= $admin['nome_completo']; ?></option>
								<? } ?>
							</select>
						</div>
					</div>
				</div>
			</div>
			<div class="span2"></div>
		</div>
		<div class='row-fluid'>
			<div class="span2"></div>
			<div class="span8">
				<div class='control-group<?=(in_array('motivo', $msg_erro['campos'])) ? " error" : "" ?>'>
					<label class="control-label" for="motivo">Motivo:</label>
					<div class="controls controls-row">
						<div class="span12">
							<h5 class='asteristico'>*</h5>
							<textarea id="motivo" name="motivo" rows="3" class="span12"></textarea>
						</div>
					</div>
				</div>
			</div>
			<div class="span2"></div>
		</div>
		<br />
		<div class="row-fluid">
			<div class="span2"></div>
			<div class="span8">
				<center><button id="indisponibilizar" class="btn btn-danger">Indisponibilizar</button></center>
			</div>
			<div class="span2"></div>
		</div>
	</form>
<?php
}
?>

<div id="msgAdminDisponibilidade" style="display:none;"></div>
<? 
if ($areaAdmin == true && !in_array($login_fabrica, [169,170])) {
	$adminsIndisponiveis = selectAdminsIndisponiveis();
	?>

	<table id="pesquisa_admin_indisponivel" class="table table-bordered table-striped table-hover" >
		<thead>
			<tr class="titulo_tabela">
				<th colspan="3">Atendentes Indisponíveis</th>
			</tr>
			<tr>
				<th>Atendente</th>
				<th>Motivo</th>
				<th>Ações</th>
			</tr>
		</thead>
		<tbody>
			<? 
			if ($adminsIndisponiveis != false) {
				foreach ($adminsIndisponiveis as $linha => $admin) { 
				?>
					<tr rel="<?= $linha; ?>">
						<input type="hidden" id="admin_<?= $linha; ?>" value="<?= $admin['admin']; ?>">
						<td class="tac"><?= $admin['nome_completo']; ?></td>
						<td class="tal"><?= utf8_decode($admin['nao_disponivel']); ?></td>
						<td class="tac"><button class="btn btn-success disponibilizar" rel="<?= $linha; ?>">Disponibilizar</button></td>
					</tr>
				<? 
				}
			} 
			?>
		</tbody>
	</table>
<? 
}

include "rodape.php";
?>
