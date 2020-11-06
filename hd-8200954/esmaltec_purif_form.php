<?php
//  01/10/2010 MLG - HD 308656
	if ($_POST['ajax']=='frm') {
		include "dbconfig.php";
		include "includes/dbconnect-inc.php";
		include "autentica_usuario.php";

		include "helpdesk/mlg_funciones.php";
		$posto	= getPost('posto');
		$apto	= getPost('apto');
		$valor	= getPost('valor');

		if ($posto != $login_posto) exit('Erro de autentica��o de usu�rio. Recarregue a tela (F5), por favor.');
		if (strpos('naosim', $apto) === false) exit('Por favor, informe se est� ou n�o apto para realizar a instala��o.');
		$apto	= (strpos('naosim', $apto)>0)? 'TRUE':'FALSE'; // Se for nao, strpos vai devolver 0 (0>0  = false), se for 'sim', vai devolver 3 (3>0=true)
		$valor	= str_replace(',', '.', $valor); // PHP e PostgreSQL usam '.' para decimais
		if (!is_numeric($valor)) exit('O valor de m�o-de-obra informado est� inv�lido. Por favor, digite um valor v�lido');

		$sql_i = "INSERT INTO tbl_pesquisa_purificador_esmaltec
						(posto, apto, valor)
					VALUES
						($posto, $apto, $valor)";
		$res_i = @pg_query($con, $sql_i);
		if (!is_resource($res_i)) exit ('Erro ao gravar as informa��es. Por favor, tente novamente.');
		exit('OK');
	}
?>
<head>
	<script src="js/jquery-1.6.2.js" type="text/javascript"></script>
    <script type="text/javascript">
	$().ready(function() {
		$('#btn_send').click(function() {
			var valor = $('#valor_mo_inst').val();
			var posto = $('#frm_es_pur input[name=posto]').val();
			var apto  = $('input:radio:checked').val();
			if (apto == undefined) {
				alert("Por favor, informe se est� ou n�o apto para realizar a instala��o.\n  Obrigado.");
				return false;
			}
// 			alert("Posto "+posto+' '+apto+' est� apto. Valor: R$'+valor);
// 			return false;
			$.post('esmaltec_purif_form.php','ajax=frm&posto='+posto+'&apto='+apto+'&valor='+valor,function(data) {
				if (data=='OK') {
					window.location.reload();
				} else {
					alert(data);
					return false;
				}
			});
			return false; // N�o deixa dar submit!!
		});
	});
    </script>
	<link type="text/css" rel='stylesheet' href="/assist/css/css.css">
	<style type="text/css">
	html body {margin:0;padding:0}
	div.oculto {text-align: left;padding: 8px 16px;background-color: #f0f0fa;}
	#window_box {
	    display: block;
	    opacity: 0.9;
	    background-color: #ffffff;
	    position: relative;
		text-align: left;
	    top:  0;
	    left: 0;
        padding: 32px 0 1em 1em;
		height: 70%;
		margin: 1em 10%;
	    border: 2px solid #68769f;
        border-radius: 8px;
        -moz-border-radius: 8px;
		box-shadow: 5px 4px 5px grey;
		-moz-box-shadow: 5px 4px 5px grey;
		-webkit-box-shadow: 5px 4px 5px grey;
        overflow: hidden;
		z-index: 10000;
		*width:780px;
		_width:780px;
		_margin: 1em 15%;
		*margin: 1em 15%;
	}
	#window_box:hover {
		opacity: 1;
		box-shadow: 3px 3px 3px #ccc;
		-webkit-box-shadow: 3px 3px 3px #ccc;
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
		_width: 780px;
		*width: 780px;
		height:28px;
		background-image: url('/assist/admin/imagens_admin/azul.gif');    /* IE, Opera */
		background-image: -moz-linear-gradient(top, #b4bbce, #68769f 6px, #68769f 15px, #7889bb);
		background-image: -webkit-gradient(linear,  0 0, 0 100%,
												from(#b4bbce),
													color-stop(0.07,#68769f),
													color-stop(0.20,#68769f),
												to(#7889bb));
	    padding: 2px 1em;
	    color: white;
	    font: normal bold 13px Segoe UI, Verdana, MS Sans-Serif, Arial, Helvetica, sans-serif;
	}
	#window_box #ei_container {
		margin: 1px;
		padding-bottom: 1ex;
		overflow-y: auto;
        overflow-x: hidden;
	    height: 100%;
		font-size: 11px;
		color: #313452;
        background-color: #fdfdfd;
	}
	#ei_container form label {color: #900}
	#window_box #fechar_msg {
		position: absolute;
		top: 3px;
		right: 5px;
		width: 16px;
		height:16px;
		font: normal bold 12px Verdana, Arial, Helvetica, sans-serif;
		color:white;
	    cursor: pointer;
		margin:0;padding:0;
		vertical-align:top;
		text-align:center;
		background-color: #f44;
		border:	1px solid #d00;
		border-radius: 3px;
		-moz-border-radius: 3px;
		box-shadow: 2px 2px 2px #900;
		-moz-box-shadow: 1px 1px 1px #900;
		-webkit-box-shadow: 2px 2px 2px #900;
	}
	</style>
  </head>
  <body>
	 <div id="window_box">
		<div id="ei_header"><img src='/img/favicon.ico' style='padding: 4px 1ex 0 0' />
		GER�NCIA DE ASSIST�NCIA T�CNICA - An�lise de mercado</div>
		<div id="fechar_msg" title='Mensagem lida.'>X</div>
	 	<div id="ei_container">
			<h1>GER�NCIA DE ASSIST�NCIA T�CNICA</h1>
			<h3>AN�LISE DE MERCADO</h3>
			<img src="/assist/imagens/purif_esmaltec.jpg" alt="" style='float:right' />
			<p>Prezados SAE�s, no m�s de outubro a <strong>Esmaltec</strong> estar� efetuando o lan�amento
			do seu Purificador de �gua, e para isso gostar�amos de saber se nossa rede esta
			preparada para efetuar a instala��o.</p>

			<h3>A instala��o se comp�e de:</h3>
			<ul>
				<li>Dois furos na parede (Seguir o gabarito de fura��o que acompanha junto � embalagem)</li>
				<li>Coloca��o das buchas</li>
				<li>Fixa��o do aparelho na parede</li>
				<li>Acoplamento da mangueira a torneira de sa�da d��gua</li>
			</ul>
			<form action="#" id='frm_es_pur' name='frm_es_pur'>
				<input type="hidden" name="posto"	value='<?=$login_posto?>' />
				<input type="radio" name="apto"		value='sim' id="frm_es_pur_sim" />
				<label for="frm_es_pur_sim" style='margin-right:10em'>Sim, estou apto</label>
				<input type="radio" name="apto"		value='nao' id="frm_es_pur_nao" />
				<label for="">N�o estou apto</label>
			<br>
			<p>Este servi�o ser� pago pelo cliente, e para isso gostar�amos de saber o valor m�dio
				de mercado.<br>
				Favor informar qual valor sugerido de cobran�a pelo seu SAE para realiza��o da
				instala��o.
			</p>
			<p style='padding-left: 2em'>
				<label for="valor_mo_inst">R$</label>
				<input type="numeric" maxlength="6" name="valor_mo_inst" id='valor_mo_inst' size="10"
					  style="border-width: 0 0 1px 0;border-color:grey;margin-right:4em;text-align:right"
					  class="frm" title="Valor de m�o de obra sugerido" value='0,00'>
				<button type='button' title='Enviar formul�rio' id='btn_send'>Enviar</button>
				&nbsp;
				<button type='reset' title='Redefinir'>Limpar</button>
			</p>
			</form>
			<p>&nbsp;</p>
			<p>Desde j� agradecemos sua aten��o, e desejamos bons neg�cios.</p>
			<p>Atenciosamente,</p>
			<p style='padding-left: 3em'>
				<b>Magnus Zeidan Pav�o</b><br>
				<i>Gerente de Assist�ncia T�cnica</i>
			</p>
		</div>
	 </div>
<?  // O usu�rio n�o vai poder usar o sistema enquanto n�o responder o question�rio
include 'rodape.php';
exit;?>
