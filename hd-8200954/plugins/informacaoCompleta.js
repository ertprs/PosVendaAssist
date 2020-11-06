(function ($) {
	$.informacaoCompleta = function (i, info) {
		$.informacaoCompletaLinhas[i] = $.parseJSON(info);
	};

	$.informacaoCompletaLinhas = {};
})(jQuery);

$(function () {
	$(document).on("click", "button.informacaoCompleta", function () {
		var i = $(this).attr("rel");

		var div = $("<div></div>");
		$(div).append("<div style='height: 100%; overflow-y: scroll;' >\
			<table class='full_info_table table table-striped table-bordered principal' style='width: 100%;' >\
				<tbody>\
					<tr>\
						<th colspan='6' class='full_info_title' >"+$.informacaoCompletaLinhas[i].titulo+"</th>\
					</tr>\
				</tbody>\
			</table>\
		</div>");

		if ($.informacaoCompletaLinhas[i] != undefined) {

			var x = 0;

			$.each($.informacaoCompletaLinhas[i].campos, function (key, array) {
				var span;
				var valor;
				var coluna = array.coluna;
				var colspan;

				if (array.span == undefined) {
					span = 1;
				} else {
					span = array.span;
				}

				switch (span) {
					case "2":
						colspan = 3;
						break;

					case "3":
						colspan = 5;
						break;

					default:
						colspan = 1;
						break;
				}

				if (typeof array.valor != "object") {
					if (array.link === undefined) {
						valor = array.valor;
					} else {
						valor = "<a href='"+array.link+"' target='_blank'>\
							"+array.valor+"\
						</a>";
					}

					if (x == 0) {
						$(div).find("table.principal > tbody").append("<tr>");
						x = span;
					} else if ((parseInt(x) + parseInt(span)) > 3) {
						$(div).find("table.principal > tbody").append("</tr>");
						$(div).find("table.principal > tbody").append("<tr>");
						x = span;
					} else {
						x = parseInt(x) + parseInt(span);
					}

					if ($.informacaoCompletaLinhas[i].campos[(parseInt(key) + 1)] == undefined || typeof $.informacaoCompletaLinhas[i].campos[(parseInt(key) + 1)].valor == "object") {
						switch (x) {
							case 2:
								colspan = 3;
								break;

							case 3:
								colspan = 1;
								break;

							default:
								colspan = 5;
								break;
						}
					}

					$(div).find("table.principal > tbody").append("<th>"+coluna+"</th><td colspan='"+colspan+"'>"+valor+"</td>");

					if ($.informacaoCompletaLinhas[i].campos[(parseInt(key) + 1)] == undefined || typeof $.informacaoCompletaLinhas[i].campos[(parseInt(key) + 1)].valor == "object") {
						$(div).find("table.principal > tbody").append("<tr>");
					}
				} else {
					var sub_div = $("<div></div>");

					$(sub_div).append("<table class='full_info_table table table-striped table-bordered sub_table' style='width: 100%;' >\
						<tbody></tbody>\
					</table>");

					$(sub_div).find("table.sub_table > tbody").append("<tr>");

					$.each(array.valor.colunas, function (k, coluna_nome) {
						$(sub_div).find("table.sub_table > tbody").append("<th style='text-align: left !important;'>"+coluna_nome+"</th>");
					});

					$(sub_div).find("table.sub_table > tbody").append("</tr>");

					$.each(array.valor.valores, function (k, a) {
						$(sub_div).find("table.sub_table > tbody").append("<tr>");

						$.each(a, function (k2, valor) {
							$(sub_div).find("table.sub_table > tbody").append("<td>"+valor+"</td>");
						});

						$(sub_div).find("table.sub_table > tbody").append("</tr>");
					});

					var sub_div_html = $(sub_div).html();

					$(div).find("tbody").append("<tr>\
						<th colspan='6' class='full_info_title' >"+coluna+"</th>\
					</tr>\
					<tr>\
						<td colspan='6' class='tal'>\
						"+sub_div_html+"\
						</td>\
					</tr>");
				}


			})
		}

		Shadowbox.open({
			content: $(div).html(),
			player: "html",
			width: 910,
			height: 600
		});
	});
});