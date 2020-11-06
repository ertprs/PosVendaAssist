<?php
/**
 * Mostra uma janela tipo Windows OS com um título e um texto ou imagens.
 * O conteúdo é HTML normal.
 * Permite acrescentar um check para confirmar leitura. Porém, precisa de 
 * programação extra (ainda em desenvolvimento para facilitar)
 *
 * Variáveis:
 * $wbx_include_jquery	bool	Insere jQuery se true (evita colisões quando a tela já o carrega)
 * $wbx_include_jqUI	bool	Insere jQueryUI se true (ídem)
 * $wbx_modal			bool	Se for true, bloqueia a tela até fechar o popup (X)
 * 								Para outros desbloqueios, usar o javascript $('#bloqPopup').remove();
 * $wbx_size			Array	Para alterar o tamanho padrão (800x480), inicializar o array com
 * 								os índices 'width' e 'height', apenas números. Ex.:
 * 								$wbx_size = array('width'=> 600, 'height'=>450)
 * $wbx_deve_concordar	bool	Inclui jQuery e HTML para um "Li e concordo" no rodapé da janela
 * $wbx_allow_close		bool	Se false, não deixa fechar a janela com o 'X' (Cuidado! Deve haver
 * 								um método alternativo para fechá-la! $('#window_box').html('').remove();)
 * $wbx_window_title	string	Texto para a barra de título da janela
 * $wbx_content_title	string	Opcional, mas recomendado: título do conteúdo, já dentro da janela
 * $wbx_body			string	HTML a ser inserido como corpo da janela, só tomar cuidado com </div>
 * $wbx_footer
 * $wbx_rodape			string	Texto para colocar no rodapé, não pode ser muito extenso. HTML.
 *
 * TODOS estes valores podem ser encapsulados num array $wbx_config, com os índices usando os
 * nomes das variáveis, sem o prexixo 'wbx_'.
 **/

if (is_array($wbx_config))
	extract($wbx_config, EXTR_OVERWRITE | EXTR_PREFIX_ALL, 'wbx');

if ($wbx_include_jquery) { ?>
<script type="text/javascript" src="js/jquery-1.6.1.min.js"></script>
<?}
if ($wbx_include_jqUI) { ?>
<script type="text/javascript" src="js/jquery-ui.min.js"></script>
<?}?>
<link rel="stylesheet" href="css/tc_window_box.css" />
<!--[if IE]>
<style type="text/css">
	#ei_container #msgErro {
		color: #900;
		background-color: #ff8080;
	}
</style>
<![endif]-->

<?if ($wbx_allow_close) { ?>
<style type="text/css">
	#window_box {display: none}
	#window_box #fechar_msg {
		cursor: pointer
	}
</style>
<?}?>

<?if (is_array($wbx_size) and count($wbx_size == 2)) {
	$wbx_w    = $wbx_size['width'] . 'px';
	$wbx_ie_w = (string) ($wbx_size['width'] - 2) . 'px';
?>
<style type="text/css">
#window_box {
	width:  <?=$wbx_w?>;
	*width: <?=$wbx_w?>;
	_width: <?=$wbx_w?>;
}
#window_box #ei_header {
	_width: <?=$wbx_ie_w?>;
	*width: <?=$wbx_ie_w?>;
}
#window_box #ei_container {
	height: <?=$wbx_size['height']?>px;
}
</style>
<?}?>

<script type="text/javascript">
$().ready(function() {
	$('#window_box').draggable({handle: '#ei_header', distance: 16, containment: 'parent', cursor: 'move'});
	var w_x = (document.clientWidth  != undefined) ? document.clientWidth  : window.outerWidth;
	var w_y = (document.clientHeight != undefined) ? document.clientHeight : window.outerHeight;
	w_y = parseInt(w_y) * 0.8;

	var wbx = $('#window_box').width();
	var wby = $('#window_box').height();

	var x_pos = (w_x - wbx) / 2;
	var y_pos = (w_y - wby) / 2;

	$('#window_box').offset({ top: y_pos, left: x_pos}).show();

<?	if ($wbx_deve_concordar) { ?>
	$('#concorda').change(function() {
		if ($('#concorda').is(':checked')) {
			$('button').removeAttr('disabled');
		} else {
			$('button').attr('disabled','disabled');
		}
	});
	$('button').click(function() {
		if ($('#concorda').is(':checked')) {
			$.post('../<?=basename(__FILE__)?>', {
				'ok': 'ok',
				'ajax': 'sim', 'admin': '<?=$login_admin?>'
				},
				function(retorno) {
					if (retorno == 'ok') {
						alert('Obrigado por confirmar a leitura do comunicado.');
						window.location.reload();
					} else {
						//alert(retorno);
						$('#concorda').click();
					}
				});
		}
	});
<?	}?>
<?	if ($wbx_allow_close) { ?>
	$('#fechar_msg').click(function() {
		$("#window_box").html('').remove();
	});
<?	}?>

<?if ($wbx_modal) { ?>
	$('#fechar_msg').unbind('click');
	$('#fechar_msg').click(function() {
		$('#bloqPopup').html('').remove();
	});
<?}?>
});
</script>
<?if ($wbx_modal) { ?>
<div id='bloqPopup'>
<?}?>
 <div id="window_box">
	<div id="ei_header">
		<img src='imagens/tc_2009.ico' class='wbx_icon' />
		<span><?=$wbx_window_title?></span>
	</div>
	<div id="fechar_msg">X</div>
	<div id="ei_container">
		<h3><?=$wbx_content_title?></h3>
<?=$wbx_body?>
<?	if ($wbx_footer or $wbx_rodape or $wbx_deve_concordar) { ?>
		<div id='footer'>
			<?=$wbx_footer . $wbx_rodape?>
<?		if ($wbx_deve_concordar) { ?>
			<hr width='90%' />
			<input id="concorda" name="chk" type="checkbox" />
			<label for="concorda">&nbsp;Estou ciente da alteração</label>&nbsp;&nbsp; 
			<button type='button' style='cursor:pointer' disabled>Confirmo Leitura</button>
<?		}?>
		</div>
<?	}?>
	</div>
</div>
<?if ($wbx_modal) { ?>
</div>
<?}?>
