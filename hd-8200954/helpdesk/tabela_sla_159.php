<style>

	.cabecalho, .fabricante{
		background: #E7E6E6;
		font-size: 14px;
		font-weight: bold;
	}

	.fabricante{
		background: #D0CECE;
	}

	.p1{
		background: #FF5050;
		font-size: 14px;
	}

	.p2{
		background: #F8CBAD;
		font-size: 14px;
	}
	.p3{
		background: #FFF2CC;
		font-size: 14px;
	}
	.p4{
		background: #C6E0B4;
		font-size: 14px;
	}

	.tabela_sla_modal {
		margin: 15px 0 0 25px;
	}

	.mouse_pointer {
		cursor: pointer;
	}

</style>
<script type="text/javascript" src="../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox_lupa/shadowbox.css" />
<script src="../plugins/shadowbox_lupa/shadowbox.js"></script>

<script type="text/javascript">
		$(function(){
			$(".prioridade").click(function(){ 
				var prioridade = $(this).text();
				$("#local").html("");
				$("#local").load("helpdesk_classificacao_sla.php?clique=true&classificacao="+prioridade);  

				$("html #sb-wrapper-inner, #sb-info",window.parent.document).css('width', '800px');
				$("#sb-wrapper-inner",window.parent.document).css('height', '420px');
				$("#sb-wrapper",window.parent.document).css("left", "30%");
				$("#sb-wrapper",window.parent.document).css("top", "20%");
				
				$("#tabela_sla").hide();
			});

			$(document).on('click', '.voltar', function(){
				$("#tabela_sla").show();		
				$("#local").html("");

				$("html #sb-wrapper-inner, #sb-info",window.parent.document).css('width', '350px');
				$("#sb-wrapper-inner",window.parent.document).css('height', '190px');

				$("#sb-wrapper",window.parent.document).css("left", "40%");
				$("#sb-wrapper",window.parent.document).css("top", "30%");
			});
		});
</script>

<div id="local" style="background-color: white;">
	
</div>


<div id="tabela_sla" class="tabela_sla_modal">
	<table width="300" border=1 cellpadding="4" cellspacing="0">
		<tr class="fabricante">
			<td align="center" colspan="2">
				SLA Ingersoll-Rand
			</td>
		</tr>
		<tr class="cabecalho">
			<td align="center">Classificação SLA</td>
			<td align="center">Prazo</td>
		</tr>
		<tr class="p1 mouse_pointer">
			<td align="center" class='prioridade'>P1</td>
			<td align="center">2 a 4 Horas</td>
		</tr>
		<tr class="p2 mouse_pointer">
			<td align="center" class='prioridade'>P2</td>
			<td align="center">1 Dia útil</td>
		</tr>
		<tr class="p3 mouse_pointer">
			<td align="center" class='prioridade'>P3</td>
			<td align="center">3 Dias úteis</td>
		</tr>
		<tr class="p4 mouse_pointer">
			<td align="center" class='prioridade'>P4</td>
			<td align="center">5 Dias úteis</td>
		</tr>
	</table>
</div>