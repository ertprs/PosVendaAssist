<?php
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_usuario.php';
include_once 'regras/menu_posto/menu.helper.php';

/* Menu parametrizado via array, com opções para mostrar por fábrica ou excluir de fábrica */
$menu_pedido = array();

if ($_POST['ajax'] == 'ajax') {

	if ($_POST['acao'] == 'comunicadoPedido') {
		$sql = "
            SELECT comunicado
              FROM tbl_comunicado
             WHERE fabrica = {$login_fabrica}
               AND tipo    = 'Acessório'
               AND ativo
          ORDER BY comunicado DESC;";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res)) {
            die(pg_fetch_result($res, 0, 0));
		}
        echo 0;
	}
	exit;
}

ob_start();
?>
<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="screen">
<script src="plugins/shadowbox/shadowbox.js"	type="text/javascript"></script>

<script type="text/javascript">
	$(document).ready(function(){
		Shadowbox.init();

		$(".comunicadoPedido").click(function(){
			var link = $(this).attr('rel');

			$.ajax({
				type: "POST",
  				url: document.location.pathname,
  				cache: false,
  				data: "ajax=ajax&acao=comunicadoPedido",
				success: function(retorno) {
				   	if(retorno == 0){
				   		window.location = link;
				   	}else{
				   		comunicadoPedido(retorno);
				   	}
				}
			});
		});
	});

	function comunicadoPedido(comunicado){
		Shadowbox.open({
				content:"comunicado_pedido.php?comunicado="+comunicado,
				player:	"iframe",
				title:	"Comunicado",
				width:	800,
				height:	500
			});
	}
</script>
<?
$headerHTML  = ob_get_clean();
$layout_menu = "pedido";
$title       = traduz("menu.de.pedido.de.pecas",$con);

include_once 'cabecalho.php';

// Monta o menu
echo $cabecalho->menu(include(MENU_DIR . 'menu_pedido.php'))->HTML;
include "rodape.php";

