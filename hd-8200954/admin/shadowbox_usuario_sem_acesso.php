<?php define('BI_BACK', (strpos($_SERVER['PHP_SELF'],'/bi/') == true)?'../':''); ?>
<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
<link href="css/tooltips.css" type="text/css" rel="stylesheet" />
<link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen">
<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
<link href="plugins/shadowbox_lupa/shadowbox.css" type="text/css" rel="stylesheet" media="screen" />

<script src="<?=BI_BACK?>plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="<?=BI_BACK?>plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script src="<?=BI_BACK?>plugins/shadowbox_lupa/shadowbox.js"></script>
<script>
	function abreShadowbox() {
		Shadowbox.init();
		var link_shadowbox = "<?php echo BI_BACK . 'shadowbox_usuario_sem_acesso.php?acao=abrir'; ?> ";

	    Shadowbox.open({
	        content: link_shadowbox,
	        player: "iframe",
	        title: "Usuário sem Acesso",
	        width: 900,
	        height: 250,
	        options:{ 
            	modal: false,
            	onClose: function() {
					window.location = "<?=$aux_url_shadowbox;?>";
            	} 
            } 
	    });
	}
</script>
<style>
	body {
		padding: 8px 35px 8px 14px;
	    margin-bottom: 20px;
	    color: #c09853;
	    text-shadow: 0 1px 0 rgba(255, 255, 255, 0.5);
	    background-color: #fcf8e3;
	    -webkit-border-radius: 4px;
	    -moz-border-radius: 4px;
	}
	.container {
		padding-top: 3% !important;
	}
</style>
<?php 
	if ($_GET["acao"] == "abrir") {
		echo '<div class="container">
			<center>
				<div class="container">
			        <div class="alert">
			            <h1><b>SEM PERMISSÃO DE ACESSO!</b></h1>
			            <h3>Solicite ajuda ao usuário <b>MASTER</b> do sistema</h3>
			        </div>  
			    </div>
			</center>
		</div>';
		exit;
	}
?>
<body onload="abreShadowbox()">
</body>

<?php exit;