<?php

$plugins = array(
    "font_awesome",
);

include ("plugin_loader.php");

?>
<style type="text/css">
	#pesquisas-satisfacao {width:100%;line-height:12px;text-align:center;font-family:sans-serif;}
	#pesquisas-satisfacao:hover {cursor:pointer;}
	#pesquisas-satisfacao img {width:35px;display:block;margin:0 auto;}
	#pesquisas-satisfacao small {font-size:10px;font-weight:600;color:#777;text-align:justify;}

	#pesquisas-satisfacao .rounder {
		width: 17px;
		height: 17px;
		background-color: #FC6C5F;
		position: absolute;
		border-radius: 100%;
		right: 15px;

	}

	#pesquisas-satisfacao .counter {
		width: 17px;
		height: 17px;
		position: absolute;
		z-index: 999;
		right: 15px;
		font-size: 12px;
		margin-top: 3px;
		color: #FFF;
		font-weight: bold;
	}

	#pesquisas-list {
		width: 360px;
		height: 200px;
		background-color: #F1F1F1;
		position: absolute;
		margin-top: -70px;
		right: 0;
		z-index: 9999;
		padding: 0px 0px 0px 0px !important;
		box-sizing: border-box;
		box-shadow: 0 0 15px #CCC;
		display: none;
		border-radius: 3px;
	}

	#pesquisas-list #head-list {
		width: 100%;
		height: 15%;
		margin: 0 0 1px 0;
		padding: 0px;
		color: #FFF;
		font-weight: bold;
		background-color:#596d9b;
		border-top-left-radius: 3px;
		border-top-right-radius: 3px;
	}

	#pesquisas-list #head-list span {
		font-family: "sans-serif";
		font-size: 12px;
		font-weight: bold;
	}

	#pesquisas-list #head-list div:first-child {float:left;padding:6px 0 0 10px;}
	#pesquisas-list #head-list div:nth-child(2) {float:right;padding:6px 10px 0 0;}
	#pesquisas-list #head-list #close-list {font-size:18px;}
	#pesquisas-list #head-list #close-list:hover {cursor:pointer;color:#CCC;}

	#pesquisas-list #head-list .fa-redo {font-size:11px;position:absolute;margin-top:4px;right:30px;}
	#pesquisas-list #head-list .fa-redo:hover {color: #F1F1F1; cursor:pointer;}


	#pesquisas-list #body-list {
		margin: 0;
		width: 100%;
		height: 85%;
		background-color: #FFF;
		font-size: 12px;
		padding: 0px;
	}
	#pesquisas-list #table-body-list {text-align:center;}

	#pesquisas-list #table-body-list tr {border-bottom: 1px solid #F1F1F1;width:100%;}
	#pesquisas-list #table-body-list tr:hover {background-color: #F1F1F1;cursor: pointer;}

	#pesquisas-list #table-body-list tr td {padding: 5px 0;}
	#pesquisas-list #table-body-list tr td:first-child {font-weight:bold;width:190px;}
	#pesquisas-list #table-body-list tr td:nth-child(2) {width:170px;}
</style>
<div id="pesquisas-satisfacao">
	<span class="rounder"></span>
	<span class="counter"></span>
	<center><img src="imagens/botoes/analytics.png"></center>
	<small>Pesquisas de Satisfação</small>
</div>
<div id="pesquisas-list">
	<div id="head-list">
		<div>
			<span>Pesquisas Abertas Há Mais de 72 Horas</span>
		</div>
		<div>
			<i id="reload-list" class="fas fa-redo"></i>
			<i id="close-list">&times;</i>
		</div>
	</div>
	<div id="body-list">
		<table style="width:100%;">
			<thead style="background-color:#596d9b;color:#FFF;padding:2px;text-align:center;display:block">
				<tr>
					<th style="width:170px;">HD</th>
					<th style="width:190px;">Data de Envio</th>
				</tr>
			</thead>
			<tbody id="table-body-list" style="display:block;overflow:auto;max-height:144px"></tbody>
		</table>
	</div>
</div>
<script type="text/javascript">
	$(function () {
		loadPesquisas();

		$("#pesquisas-satisfacao").on("click", function () {
			$("#pesquisas-list").fadeIn(200);
		});

		$("#close-list").on("click", function () {
			$(this).parents("#pesquisas-list").fadeOut(200);
		});

		$("#reload-list").on("click", function () {
			loadPesquisas();
		})
	});

	function loadPesquisas() {
		$("#table-body-list").html("");

		$.ajax({
			url: 'ajax_pendencia_pesquisas_satisfacao.php',
			type: 'POST',
			async: true,
			data: {
				ajax: 'loadPesquisas'
			}
		}).fail(function (response) {
			let trNone = $("<tr></tr>", {
				css: {
					"text-align": "center",
					"max-width": "360px"
				}
			});

			let tdNone = $("<td></td>", {
				attr: {
					"colspan": "2"
				},
				text: "Ocorreu uma falha ao buscar pesquisas pendentes. Tente recarregar a tela.",
				css: {
					"width": "360px"
				}
			});

			$(trNone).append(tdNone);
			$("#table-body-list").append(trNone);
		}).done(function (response) {
			response = JSON.parse(response);
			if (response.exception) {
				$(".counter").text("0");

				let trNone = $("<tr></tr>", {
					css: {
						"text-align": "center",
						"max-width": "360px"
					}
				});

				let tdNone = $("<td></td>", {
					attr: {
						"colspan": "2"
					},
					text: response.exception,
					css: {
						"width": "360px"
					}
				});

				$(trNone).append(tdNone);
				$("#table-body-list").append(trNone);
				return;
			}

			$(".counter").text(response.quantidade);
			
			$.each(response.pesquisas, function (index, element) {
				let tr = $("<tr></tr>", {
					css: {
						"max-width": "360px"
					}
				});
				$(tr).on("click", function () {
					window.open('callcenter_interativo_new.php?callcenter=' + element.hd_chamado, '_blank')
				});

				let tdCall = $("<td></td>", {
					text: element.hd_chamado,
					css: {
						"width": "170px"
					}
				});

				let tdDate = $("<td></td>", {
					text: element.data,
					css: {
						"width": "190px"
					}
				});

				$(tr).prepend(tdDate);
				$(tr).prepend(tdCall);

				$("#table-body-list").append(tr);
			});
		});
	}
</script>