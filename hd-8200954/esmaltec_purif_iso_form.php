<?php
	if ($_POST['ajax']=='frm') {
		include "dbconfig.php";
		include "includes/dbconnect-inc.php";
		include "autentica_usuario.php";

		include "helpdesk/mlg_funciones.php";

		$posto      = getPost('posto');
		$pergunta1	= getPost('p1');
		$pergunta2	= getPost('p2');
		$pergunta3	= getPost('p3');
		$pergunta4	= getPost('p4');
		
		if ($posto != $login_posto) exit('Erro de autenticação de usuário. Recarregue a tela (F5), por favor.');

		$sql = "INSERT INTO tbl_pesquisa_purificador_iso(posto, pergunta1,pergunta2,pergunta3,pergunta4)
		               VALUES($posto,'$pergunta1','$pergunta2','$pergunta3','$pergunta4')";
		$res = pg_exec($con,$sql);

		if(strlen(pg_errormessage($con)) == 0){
			echo "OK";
		}
		else{
			echo "A pesquisa não pode ser cadastrada, tente novamente.";
		}
		exit;
	}

?>
<head>
	<script src="js/jquery-1.6.2.js" type="text/javascript"></script>
    <script type="text/javascript">
	$().ready(function() {
		$('#btn_send').click(function() {
			var posto     = $('input[name=posto]').val();
			var pergunta1 = $('input[name=pergunta1]:radio:checked').val();
			var pergunta2 = $('input[name=pergunta2]:radio:checked').val();
			var pergunta3 = $('input[name=pergunta3]').val();
			var pergunta4 = $('input[name=pergunta4]:radio:checked').val();
		
			if(pergunta1 == undefined || pergunta2 == undefined || pergunta3 == "" || pergunta4 == undefined){
				alert("Todas as questões devem ser respondidas");
			}
			else{
				$.post('esmaltec_purif_iso_form.php',
					   {
					    ajax  :'frm',
					    posto : posto,
					    p1    : pergunta1,
					    p2    : pergunta2,
					    p3    : pergunta3,
					    p4    : pergunta4
						},
						function(data) {
							if (data=='OK') {
								window.location.reload();
							} else {
								alert(data);
								return false;
							}
						});
			}
		})	
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
	
	</style>
  </head>
  <body>
	 <div id="window_box">
		<div id="ei_header"><img src='/img/favicon.ico' style='padding: 4px 1ex 0 0' />
		Pesquisa para ISO com rede SAE</div>
	 	<div id="ei_container">
			<form action="#" method='post'>
				<input type="hidden" name="posto"	value='<?=$login_posto?>' />
				<p>1 - Possui cadastro técnico Federal no IBAMA</p>
				<p style='margin-top:-15px;'>
					<input type='radio' name='pergunta1' value='t'>&nbsp;Sim
					&nbsp;&nbsp;
					<input type='radio' name='pergunta1' value='f'>&nbsp;Não
				</p>

				<p>2 - Possui maquina recolhedora dos gases refrigerantes?</p>
				<p style='margin-top:-15px;'>
					<input type='radio' name='pergunta2' value='t'>&nbsp;Sim
					&nbsp;&nbsp;
					<input type='radio' name='pergunta2' value='f'>&nbsp;Não
				</p>

				<p>
					3 - Qual a quantidade de gás recolhida no ano de 2010? 
				</p>
				<p style='margin-top:-15px;'>
					<input type='text' name='pergunta3' size='20' class='frm'>				
				</p>
				<p style='margin-top:-15px; font-size:10px;'>
					Favor enviar a cópia do	relatório de recolhimento do encaminhamento do IBAMA. <br>
					Para o e-mail: vendas.at.@esmaltec.com.br
				</p>

				<p>
					4 - Possui certificado de treinamento dos técnicos em refrigeração?					
				</p>
				<p style='margin-top:-15px;'>
					<input type='radio' name='pergunta4' value='t'>&nbsp;Sim
					&nbsp;&nbsp;
					<input type='radio' name='pergunta4' value='f'>&nbsp;Não
				</p>
				<p style='margin-top:-15px; font-size:10px;'>
					Favor encaminhar pelo menos uma cópia do certificado por SAE. <br>
					Para o e-mail: vendas.at.@esmaltec.com.br
				</p>
				<p style='padding-left: 2em'>
					<button type='button' title='Enviar formulário' id='btn_send'>Enviar</button>
					&nbsp;
					<button type='reset' title='Redefinir'>Limpar</button>
				</p>
			</form>
			<p>Desde já agradecemos sua atenção, e desejamos bons negócios.</p>
			<p>Atenciosamente,</p>
			<p style='padding-left: 3em'>
				<b>Magnus Zeidan Pavão</b><br>
				<i>Gerente de Assistência Técnica</i>
			</p>
		</div>
	 </div>
<?  // O usuário não vai poder usar o sistema enquanto não responder o questionário
include 'rodape.php';
exit;?>
