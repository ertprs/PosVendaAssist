<?php
if (count($_POST) and $_POST['tc_com_admin_ok']=='ok') {
	require_once('dbconfig.php');
	require_once('includes/dbconnect-inc.php');

	if ($_POST['ajax'] == 'sim') {
		if (!is_numeric($_POST['admin'])) die('ko');

		$admin = $_POST['admin'];

		if(strlen($admin) > 0){
			$sql = "SELECT nome_completo FROM tbl_admin WHERE admin = $admin";
			$res = pg_query($con, $sql);
			if (pg_num_rows($res) == 0) die('ko');

			$sql = "INSERT INTO tbl_comunicado_tc_leitura (admin, comunicado_tc) VALUES ($admin, 2)";
			$res = pg_query($con, $sql);

			if (is_resource($res) and pg_affected_rows($res) == 1) die('ok');
		}
	}
	die('ko');
}
?>
	<script type="text/javascript" src="../js/jquery-1.6.1.min.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.min.js"></script>
	<style type="text/css">
	#window_box {
	    display: block;
	    background-color: #ffffff;
	    position: relative;
		text-align: left;
	    top:  0;
	    left: 0;
        padding: 32px 0 1em 1em;
	/*	height: 70%;*/
		margin: 20px auto;
	    border: 2px solid #68769f;
        border-radius: 8px;
        -moz-border-radius: 8px;
		box-shadow: 3px 3px 3px #ccc;
		-moz-box-shadow: 3px 3px 3px #ccc;
        overflow: hidden;
		z-index: 450;
		width:800px;
		*width:800px;
		_width:800px;
		_margin: 1em 15%;
		*margin: 1em 15%;
	}
	#window_box:hover {
		box-shadow: 5px 4px 5px grey;
		-moz-box-shadow: 5px 4px 5px grey;
	}
	#window_box #ei_container p {
		font-size: 14px;
	    padding: .5ex 1ex;
		overflow-y:auto;
	}
	#window_box #ei_header {
		position: absolute;
		top:	0;
		left:	0;
		margin:	0;
		width: 100%;
		_width: 798px;
		*width: 798px;
		height:28px;
		border-radius: 7px 7px 0 0 ;
        -moz-border-radius: 7px 7px 0 0 ;
        -webkit-border-radius: 7px 7px 0 0;
		background-image: url('/assist/admin/imagens_admin/azul.gif');    /* IE, Opera 11- */
		background-image: linear-gradient(top        , #b4bbce, #68769f 6px, #68769f 15px, #7889bb);
		background-image: -o-linear-gradient(top     , #b4bbce, #68769f 6px, #68769f 15px, #7889bb);
		background-image: -ms-linear-gradient(top    , #b4bbce, #68769f 6px, #68769f 15px, #7889bb);
		background-image: -moz-linear-gradient(top   , #b4bbce, #68769f 6px, #68769f 15px, #7889bb);
		background-image: -webkit-linear-gradient(top, #b4bbce, #68769f 6px, #68769f 15px, #7889bb);
		background-image: -webkit-gradient(linear,  0 0, 0 100%,
											from(#b4bbce),
												color-stop(0.07,#68769f),
												color-stop(0.20,#68769f),
											to(#7889bb));
	    padding: 2px 1em;
	    color: white;
	    font: normal bold 13px Segoe UI, Verdana, MS Sans-Serif, Arial, Helvetica, sans-serif;
		cursor: move;
	}
	#window_box #ei_container {
        background-color: #fdfdfd;
		margin: auto;
		overflow-y: auto;
	    height: 480px;
		font-size: 15px;
		text-align:justify;
		color: #313452;
		width: 96%;
		position:relative;
	}
	#window_box #fechar_msg {
		position: absolute;
		top: 4px;
		right: 5px;
		width: 16px;
		height:16px;
		font: normal bold 12px Verdana, Arial, Helvetica, sans-serif;
		color:white;
		cursor: wait;
		margin:0;padding:0;
		vertical-align:top;
		text-align:center;
		background-color: #f44;
		border:	1px solid #d00;
		border-radius: 3px;
		-moz-border-radius: 3px;
		box-shadow: 2px 2px 2px #900;
	}
	#legenda {
		width: 95%;
		display: none;
		margin:auto;
		background-color: #fcc;
		color:darkred;
	}
	#window_box h3 {
		text-align: center;
		font-size: 1.5em;
		text-shadow: 2px 2px 4px #666;
	}
	#window_box div#footer {
		font-weight:bold;
		text-align:center;
		margin:auto;
		width: 96%;
		position: absolute;
		bottom: 1ex;
		background-color: white;
		color: black;
		margin: auto;
	}
	#window_box ul li {
		list-style-type: circle;
		margin-top: 0.5em;
	}
	</style>

<!--[if IE]>
  	<style type="text/css">
		#ei_container #msgErro {
			color: #900;
			background-color: #ff8080;
		}
	</style>
<![endif]-->

    <script type="text/javascript">
	$().ready(function() {
		$('#window_box').draggable({handle: '#ei_header', distance: 16, containment: 'parent', cursor: 'move'});
		$('#concorda').change(function() {
			//console.log('Sim ou não...');
			if ($('#concorda').is(':checked')) {
				$('button').removeAttr('disabled');
			} else {
				$('button').attr('disabled','disabled');
			}
		});
		$('button').click(function() {
			if ($('#concorda').is(':checked')) {
				//console.log('Enviando... ');
				$.post('../<?=basename(__FILE__)?>', {
					'tc_com_admin_ok': 'ok',
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
	});
    </script>
	 <div id="window_box">
		<div id="ei_header"><img src='imagens/tc_2009.ico' style='padding: 4px 1ex 0 0;width:16px;height:16px' />&nbsp;Comunicado Telecontrol</div>
		<div id="fechar_msg" title='Desabilitado'>X</div>
	 	<div id="ei_container">
			<h3>NOVO PROTOCOLO DE LOGIN</h3>
<p>Com o objetivo de nos alinharmos com as regras internacionais exigidas pela normativa <em>SOX</em> (Sarbanes-Oxley), <strong>não permitiremos que duas ou mais</strong> pessoas utilizem o mesmo Login simultaneamente.</p>
<p>Estas regras são utilizadas para todas empresas com capital aberto em Bolsa de Valores, e a <span style="color:#2B2B4D;font-weight:bold">Telecontrol</span>, além de usar estas técnicas para seus clientes multinacionais, também estendeu estas "boas-práticas" para seus clientes nacionais.</p>
<?php
if ($login_fabrica != 1) {?>
<p style="color:#2B2B4D;font-weight:bold">Implementaremos esta mudança à partir do dia 22 de outubro de 2012.</p>
<?php
}
?>

<p>No início de 2013 implementaremos dois novos protocolos de Login e autenticação, mundialmente reconhecidos por sua segurançca, o <strong>OAuth</strong>, e o <strong>OpenID</strong>, que explicaremos oportunamente.</p>

			<div id='footer'>
				<p align="right"style="color:#2B2B4D;">
					<img style='float:right;margin-left: 0.5em'
						   alt='TelecontrolCloud'
                           src='../imagens/tc_cloud_computing_pq.png' height='64' />
					Atenciosamente,<br /><strong>Equipe Telecontrol</strong></p>
				<hr width='90%' />
				<input id="concorda" name="chk" type="checkbox" />
				<label for="concorda" style="color:#2B2B4D;font-weight:bold">&nbsp;Estou ciente da alteração</label>&nbsp;&nbsp; 
				<button type='button' style='cursor:pointer' disabled>Confirmo Leitura</button>
			</div>
		</div>
	</div>
	<script type='text/javascript' src='../js/FancyZoom.js'></script>
	<script type='text/javascript' src='../js/FancyZoomHTML.js'></script>
	<script type="text/javascript">
	setupZoom();
	</script>

