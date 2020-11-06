<?php
if (count($_POST)) {
	require('dbconfig.php');
	require('includes/dbconnect-inc.php');

	if ($_POST['ok']=='ok' and $_POST['ajax'] == 'sim') {
		if (!is_numeric($_POST['admin'])) die('ko');

		$admin = $_POST['admin'];
		$sql = "SELECT nome_completo FROM tbl_admin WHERE admin = $admin";
		$res = pg_query($con, $sql);
		if (pg_num_rows($res) == 0) die('ko');

		$sql = "INSERT INTO tbl_comunicado_tc_leitura (admin) VALUES ($admin)";
		$res = pg_query($con, $sql);

		if (is_resource($res) and pg_affected_rows($res) == 1) die('ok');
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
		font-size: 11px;
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
		font-size: 12px;
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
		color: #313452;
		margin: auto;
		overflow-y: auto;
		padding-right: 1ex;
		position:relative;
		text-align:justify;
		width: 98%;
	    height: 480px;
        background-color: #fdfdfd;
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
		bottom: 1ex;
		background-color: white;
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
			if ($('#concorda').is(':checked')) {
				$('button').removeAttr('disabled');
			} else {
				$('button').attr('disabled','disabled');
			}
		});
		$('button').click(function() {
			if ($('#concorda').is(':checked')) {
				$.post('<?=basename(__FILE__)?>', {
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
	});
    </script>
	 <div id="window_box">
		<div id="ei_header"><img src='imagens/tc_2009.ico' style='padding: 4px 1ex 0 0;width:16px;height:16px' />* ALTERA��O NO HELP DESK *</div>
		<div id="fechar_msg" title='Deshabilitado'>X</div>
	 	<div id="ei_container">
			<h3>Novas Regras Help Desk 2012</h3>
			<h4> Prezados Parceiros, </h4>

			<p>Hoje o cliente pode abrir chamado apenas quando a janela mensal est� aberta, de acordo com o calend�rio passado pela Telecontrol. Iremos alterar para que o cliente possa abrir chamados de desenvolvimento quando desejar, por�m chamados de desenvolvimento s� poder�o ser APROVADOS dentro da janela conforme calend�rio Telecontrol.</p>
			<p>Desta forma conseguiremos ter um controle exato de quantos chamados de desenvolvimento teremos para trabalhar durante o m�s, e nossos clientes poder�o ter certeza de que, o que for aprovado dentro da janela, ser� desenvolvido durante o m�s.</p>
			<p>Regras referentes aos chamados de desenvolvimento abertos no Telecontrol:</p>

			<ol>
				<li>Chamado aberto ter� o prazo de duas janelas para ser aprovado, caso n�o seja aprovado, o mesmo ser� automaticamente exclu�do.<br /> 
				Exemplo: O cliente abriu o chamado dia 20/03/2012, as duas pr�ximas janelas ser�o abertas entre as seguintes datas: 1� janela abertura 09/04/2012 fechamento 13/04/2012; 2� janela abertura 07/05/2012 fechamento 11/05/2012, caso o chamado n�o seja aprovado em nenhuma das duas janelas, o mesmo ser� automaticamente exclu�do ap�s o fechamento da segunda janela.</li>
				<li>Chamados aprovados na janela ser�o levantados os requisitos e passaremos para aprova��o dos requisitos, caso os requisitos n�o seja aprovado em cinco (5) dias �teis, o chamado ser� automaticamente exclu�do e ser� debitada 1 hora.</li>
				<li>Chamado com requisitos aprovados a Telecontrol ir� passar o or�amento para aprova��o, caso o or�amento n�o seja aprovado em dez (10) dias �teis, o chamado ser� automaticamente exclu�do e ser�o debitadas 2 horas.</li>
				<li>Os chamados cancelados nas tr�s situa��es acima ser�o armazenados na Telecontrol por 30 dias corridos, caso o cliente n�o queira resgatar o mesmo em 30 dias, ent�o os chamados ser�o exclu�dos definitivamente. O cliente ter� que abrir um novo chamado caso queira novamente a altera��o.</li>
				<li>Ser�o disparados diariamente emails, alertando o admin e os supervisores de Help Desk sobre as aprova��es pendentes, ent�o, por favor, todos os supervisores devem dar total aten��o quanto a essas aprova��es e acompanhamento de cada trabalho.</li>
			</ol>
			<br />
			<p align="right">
				Atenciosamente,<br />
				<strong>Equipe Telecontrol</strong>
			</p>
		</div>
		<div id='footer'>
			<hr width='90%' />
			<input id="concorda" name="chk" type="checkbox" />
			<label for="concorda">&nbsp;Estou ciente da altera��o</label>&nbsp;&nbsp; 
			<button type='button' style='cursor:pointer' disabled>Confirmo Leitura</button>
		</div>
	</div>
