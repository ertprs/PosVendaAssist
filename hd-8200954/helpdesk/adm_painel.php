<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'mlg_funciones.php';

$painel = new Painel($con);

if($_POST) {
	if ($_POST['acao'] == 'statusAlterar') {
		$salva_status = $painel->alteraStatus($_POST, $login_admin);
	}
	elseif ($_POST['acao'] == 'statusVoltar') {
		$salva_status = $painel->voltaStatus($_POST['hd_chamado'], $login_admin);
	}
	
	if ($salva_status) {
		header('Location: '.$_SERVER['PHP_SELF']);
		exit;
	}
	else {
		$msg_erro = "Erro ao alterar status do chamado {$_POST['hd_chamado']}!";
	}
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>HD - Painel de Fluxo dos Chamados</title>
<script src="js/jquery-1.5.2.min.js"></script>
<script type="text/javascript" src="../plugins/fixedtableheader/jquery.fixedtableheader.min.js"></script>
<link type="text/css" href="js/js_custom/themes/base/jquery.ui.all.css" rel="stylesheet" />
<script type="text/javascript" src="js/js_custom/ui/jquery-ui-1.8.18.custom.js"></script>
<script type="text/javascript" src="js/js_custom/ui/jquery.ui.dialog.js"></script>
<script>
$(document).ready(function() {
	$('.fixed').fixedtableheader({
		 headerrowsize:2
	});
});

$(function() {
	$( "#dialog:ui-dialog" ).dialog( "destroy" );
	
	var status = $( "#status" ),
		justificativa = $( "#justificativa" ),
		allFields = $( [] ).add( status ).add( justificativa ),
		tips = $( ".validateTips" );

	function updateTips( t ) {
		tips
			.text( t )
			.addClass( "ui-state-highlight" );
		setTimeout(function() {
			tips.removeClass( "ui-state-highlight", 1500 );
		}, 500 );
	}

	function checkLength( o, n) {
		if ( o.val().length == 0 ) {
			o.addClass( "ui-state-error" );
			updateTips( " - " + n + " é obrigatória " );
			return false;
		} else {
			return true;
		}
	}

	/* DIV de passagem de status- Qualquer para Impedimento ou Parado */
	$( "#dialog-form" ).dialog({
		autoOpen: false,
		resizable: false,
		height: 400,
		width: 420,
		modal: true,
		buttons: {
			"Salvar": function() {
				var bValid = true;
				allFields.removeClass( "ui-state-error" );

				bValid = bValid && checkLength( justificativa, "Justificativa" );

				if ( bValid ) {
					// faz o post
					$("#modal-form").submit(); 
					$( this ).dialog( "close" );
				}
			},
			"Cancelar": function() {
				$( this ).dialog( "close" );
			}
		},
		close: function() {
			allFields.val( "" ).removeClass( "ui-state-error" );
		},
		create: function(event, ui) {
			$(event.target).parent().css('position', 'fixed');
		},
	});

	$("div.card-mine").click(function() {
		var hd_chamado    = $(this).children("#hd_chamado").val();
		var altera_status = $(this).children("#altera_status").val();
		if(altera_status == 1) {
			document.forms[0].hd_chamado.value = hd_chamado;
			$( "#dialog-form" ).dialog( "open" );
		}
	});

	/* DIV de passagem de status- Impedimento ou Parado para Anterior */
	$( "#dialog-form-return" ).dialog({
		autoOpen: false,
		resizable: false,
		height: 170,
		width: 320,
		modal: true,
		buttons: {
			"Sim": function() {
				// faz o post
				$("#modal-form-return").submit(); 
				$( this ).dialog( "close" );
			},
			"Cancelar": function() {
				$( this ).dialog( "close" );
			}
		},
		close: function() {
			allFields.val( "" ).removeClass( "ui-state-error" );
		},
		create: function(event, ui) {
			$(event.target).parent().css('position', 'fixed');
		},
	});

	$("div.card-mine-return").click(function() {
		var hd_chamado = $(this).children("#hd_chamado").val();
		var altera_status = $(this).children("#altera_status").val();
		if(altera_status == 1) {
			document.forms[1].hd_chamado.value = hd_chamado;
			$( "#dialog-form-return" ).dialog( "open" );
		}
	});

	/* Mensagem de erro */
	$( "#dialog-message" ).dialog({
		modal: true,
		buttons: {
			Ok: function() {
				$( this ).dialog( "close" );
			}
		}
	});

});
</script>

<link rel="stylesheet" href="css/painel.css" type="text/css" />
</head>
<body>
	<div id="dialog-form" title="Alteração no Status do Chamado">
		<p class="validateTips">Todos os campos são obrigatórios.</p>
		<form name="modal-form" id="modal-form" method="POST">
			<input type="hidden" name="hd_chamado" value="" id="hd_chamado">
			<input type="hidden" name="acao" value="statusAlterar" id="acao">
			<fieldset>
				<label>Status</label>
				<br />
				<input type="radio" name="status" value="Impedimento" id="status" class="" checked /> <strong>Impedimento</strong> <em>(não tendo como prosseguir com o chamado)</em> <br />
				<input type="radio" name="status" value="Parado"      id="status" class="" /> <strong>Parado</strong> <em>(dá lugar a outro chamado - maior prioridade)</em>
				<br /><br />
				<label for="justificativa">Justifique a mudança de status</label>
				<br />
				<textarea name="justificativa" id="justificativa" rows="9" style="width:99%" class="text ui-widget-content ui-corner-all" /></textarea>
			</fieldset>
		</form>
	</div>
	<div id="dialog-form-return" title="Alteração no Status do Chamado">
		<form name="modal-form-return" id="modal-form-return" method="POST">
			<input type="hidden" name="hd_chamado" value="" id="hd_chamado">
			<input type="hidden" name="acao" value="statusVoltar" id="acao">
			<fieldset>
				<label>Deseja voltar o chamado ao seu status anterior?</label>
			</fieldset>
		</form>
	</div>

<?
if ($msg_erro) :
?>
	<div id="dialog-message" title="Mensagem">
		<p>
			<span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>
			<? echo $msg_erro; ?>
		</p>
	</div>
<?
endif;
?>
	<table class="board-table fixed" id="board-table" cellpadding='0' cellspacing='0'>
		<thead>
			<tr>
				<td class="col bg first"    rowspan="2" style="widtd: 6.25%;"><h2>HDs<br />Aprovados</h2></td>
				<td class="col bgRequisito" colspan="3" style="widtd:18.75%;"><h2>Requisito</h2></td>
				<td class="col bgAnalise"   colspan="4" style="widtd:25.00%;"><h2>An&aacutelise</h2></td>
				<td class="col bgExecucao"  colspan="3" style="widtd:18.75%;"><h2>Execu&ccedil&atildeo</h2></td>
				<td class="col bgTeste"     colspan="2" style="widtd:12.50%;"><h2>Teste</h2></td>
				<td class="col bgCommit"                style="widtd: 6.25%;"><h2>Commit</h2></td>
				<td class="col bgDeploy"    colspan="2" style="widtd:12.50%;"><h2>Deploy</h2></td>
			</tr>
			<tr>
				<td class="colSub first"><h3>Desenvolvimento</h3></td>
				<td class="colSub"><h3>Aprovação</h3></td>
				<td class="colSub"><h3>Finalizado</h3></td>
				<td class="colSub"><h3>Orçamento</h3></td>
				<td class="colSub"><h3>Aprovação</h3></td>
				<td class="colSub"><h3>Desenvolvimento</h3></td>
				<td class="colSub"><h3>Finalizado</h3></td>
				<td class="colSub"><h3>A fazer</h3></td>
				<td class="colSub"><h3>Desenvolvimento</h3></td>
				<td class="colSub"><h3>Finalizado</h3></td>
				<td class="colSub"><h3>Desenvolvimento</h3></td>
				<td class="colSub"><h3>Finalizado</h3></td>
				<td class="colSub"><h3>Desenvolvimento</h3></td>
				<td class="colSub"><h3>Desenvolvimento</h3></td>
				<td class="colSub"><h3>Finalizado</h3></td>
			</tr>
		</thead>
		<tbody>
			<tr>
				
<?php
/** Coluna (1) **/
for($i = 1; $i< 17 ;$i++){
	echo '<td class="colSubContent" id="'.$i.'">';
	$html = '';
	$registros = '';
	$dadosColuna = "dadosColuna".$i;
	$registros = $painel->$dadosColuna();

	$status =  (in_array($i, array(2,5,7,10,12,14))) ? 1 : 0;
	if(is_array($registros))
		echo '<div class="qtdTickets" >'.count($registros).'</div>';
	
	$total += count($registros);
	if ($registros) {
		foreach($registros as $registro) {
			$html .= $painel->montaTicket($registro,$status);
		}
		echo $html;
	}
	echo '</td>';
}
?>
			</tr>
		</tbody>
	</table>
</body>
</html>
