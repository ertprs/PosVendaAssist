<?php
/********************************************************
* MLG - Helper para a abertura de chamados no Help-Desk *
* ----------------------------------------------------- *
* Tela de preenchimento de novo HD  por parte do Admin  *
* Quando um HD é *NOVO*, abre esta tela para ajudar ao  *
* Admin na abertuda do HD, solicitando informações que  *
* o analista vai precisar, tanto para  valorar o tempo  *
* que vai levar como  para a própria análise  antes da  *
* programação ou correção do erro                       *
********************************************************/

/*  Includes e variáveis globais (AJAX e normal)    */
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
/*include 'autentica_admin.php';*/
include 'mlg_funciones.php';
$login_fabrica=3;

// Variables
$a_abas_admin = array("Gerência" => array("Consultas","Relatórios",
										  "Relatórios Call-Center",
										  "Tarefas Administrativas",
										  "Pesquisa de Opinião,3"),
					  "Call-Center" => array("Call-Center","Call-Center Relatórios",
					  						 "Ordens de Serviço","Revendas - Ordens de Serviço",
											 "Sedex - Ordens de Serviço,1.25","Pedidos de Peças",
											 "Diversos","Relatórios Call-Center",
											 "Pedidos de Peças/Produtos",
											 "Gerenciamento De Revendas,3",
											 "Informações Sobre Peças,14"),
					  "Cadastro" =>	Array("Informações Cadastrais da América Latina,20",
					  					  "Cadastros Referentes a Produtos",
										  "Cadastros Referentes a Pedidos De Peças",
										  "Cadastros de Defeitos - Exceções",
										  "Cadastros Referentes a Extrato",
										  "Cadastros de Transacionadores e Outros Cadastros",
										  "Consulta Loja Virtual,3.10.35"),
 					  					  "Info Técnica","Financeiro","Auditoria");
$a_abas_posto = explode(",","Ordem de Serviço,Info Técnico,Pedidos,Cadastro,Tabela de Preço");
$a_areas = array("AD" => "a_abas_admin", "PA" => "a_abas_posto", "PL" => "a_perls");
?>

<?/*	Aqui começa o AJAX	*/
if ($ajax=="t"):
	if (isset($_REQUEST['aba'])) {	// Retorna o nome das abas das áreas Admin ou Posto
		$c_area = $_REQUEST['area'];
		if ($c_area=="AD" or $c_area=="PA") {
		    $c_aba  = utf8_decode($_REQUEST['aba']);
	// 	    echo "<li>Área: $c_area, módulo $c_aba</li>\n";
			$aba 	= ($c_area=='AD')?$a_abas_admin[$c_aba]:$a_abas_posto[$c_aba];
	// 		pre_echo ($aba,"Abas para selecionar");
		    foreach ($aba as $value) {
				unset($prog,$fabricas,$fabricas_programa);
	// 	    echo "<li>Programa: $value</li>\n";
		        if (strpos($value,",")>0 and $c_area=='AD') {
		            list($prog,$fabricas) = explode(",",$value);
		            $fabricas_programa = iif((strpos($fabricas,".")>0),explode(".",$fabricas),array($fabricas));
				}
		    	if (count($fabricas_programa)>0) {
					if (in_array($login_fabrica,$fabricas_programa)) {
						echo str_repeat("\t", 7)."<li title='$prog'>".$prog.' <input type="hidden" name="telas_erro[]" value="'.$prog.'"></li>';
					}
				} else {
	// 	    echo "<li>Área: $c_area, módulo $c_aba</li>\n";
					echo str_repeat("\t", 7)."<li title='$value'>".$value.' <input type="hidden" name="telas_erro[]" value="teste_'.$value.'"></li>';
				}
		    }
		}
	    exit;
	}   //Fim AJAX retorna abas
endif; // Fim do AJAX
?>

<?/*    Aqui começa o PHP 'normal'  */
$assist = (!in_array($login_fabrica, array(75,76,77,78)));
?>

<?/*	Aqui começa o HTML	*/?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=windows-1252">
	<meta name="generator" content="PSPad editor, www.pspad.com">
	<title>Informa&ccedil;&atilde;o para Chamado</title>
	<link type="text/css" href="mlg/css/start/jquery-ui-1.7.2.custom.css" rel="stylesheet">
    <style type="text/css">
    <!--
     body {font: normal normal 9pt Verdana, Arial, sans serif}
     #accordion,#hd_erro_tabs {float: left;}
	 #accordion {width: 256px;height: 75%;margin-right: 1em;font-size: 0.8em}
	 #accordion a {font-size: 8pt;}
	 #hd_erro_tabs {clear: right;padding-left: 1em}
	 h3 {font-size: 11pt}
	 .sort_list_item, fieldset li {
		list-style: none outside;
		width: 90%;
		height: 20px;
		border: 1px dotted #bbb;
		margin: 2px;
		padding: 0 4px;
	}
	 .sort_list_item:hover,.sort_list_item:hover {
		border: 1px dashed #999;
	}
	#hd_erro_programas li,#hd_erro_progs_erro li {
		cursor: move;
		width: 18em;
		padding-right: 5px;
        white-space: nowrap;
		overflow: hidden;
	}
	#hd_erro_programas li:before {
		width: 16px;
		height: 16px;
		overflow: hidden;
		content: 'xxi';
		color: transparent;
		background: url('mlg/css/start/images/ui-icons_0078ae_256x240.png') no-repeat;
		background-position-x: -112px;
		background-position-y: -192px;
	}
	#hd_erro_progs_erro li:before {
        width: 16px;
        height: 16px;
        overflow: hidden;
        content: 'xxi';
        color: transparent;
        background: url('mlg/css/start/images/ui-icons_0078ae_256x240.png') no-repeat;
		background-position-x: -144px;
		background-position-y: -192px;
	}
	 form fieldset div {margin-right: 1.2em; width: 30%}
	 #hd_erro_admin_a, #hd_erro_prog_org, #hd_erro_prog_end {position: relative; float: left}
    //-->
    </style>
	<script type="text/javascript" src="/mlg/js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="/mlg/js/jquery-ui.min.js"></script>
	<script type="text/javascript">
		$(function() {
			$("#accordion").accordion({
				collapsible: true,
				autoHeight: false
			}).change(function(){
			    $('#info_hd_erro').open();
			});
			$('#hd_erro_aba_ad').change(function () {
			                            $('#hd_erro_programas').load('<?=$PHP_SELF?>',{'ajax':'t','area':'AD','aba':$(this).val()})});
			                            $('#hd_erro_programas li').addClass('sort_list_item');
			$("#hd_erro_tabs").tabs();
			$('#hd_erro_progs_erro').sortable({cancel: '.ui-state-disabled',
											   placeholder: 'ui-state-highlight',
											   items: 'li:not(.ui-state-disabled)',
											   change: function(){
													$('#hd_erro_desc').removeAttr('disabled');
											   }
									})
									.find('li').addClass('sort_list_item');
			$('#hd_erro_programas' ).sortable({connectWith: '#hd_erro_progs_erro',
											   placeholder: 'ui-state-highlight'
									})
									.find('li').css('cursor','move').addClass('sort_list_item');
			$('#hd_erro_progs_erro').sortable('option', 'connectWith', '#hd_erro_programas');
		}); // Finaliza o jQuery
	</script>
</head>
<body>

<!--	jAccordion para os diferentes tipos de chamado	-->
	<h3>Tipos de chamado</h3>
	<div id='accordion'>
		<h3><a href='#'>&nbsp;Tipos de chamado</a></h3>
		<div name='acc-0'>
		    <p>Informa&ccedil;&otilde;es sobre os diferentes tipos de chamados.
		</div>
		<h3><a href='#'>Chamado de erro</a></h3>
		<div name='acc-1'>
			<p>Este tipo de chamado &eacute; para quando se detecte um erro no sistema.</p>
			<p>Estes chamados n&atilde;o precisam de aprova&ccedil;&atilde;o do supervisor do Help-Desk.
			   Mas, se aberto com o tipo errado, pode perder um tempo importante, pois se n&atilde;o for erro,
			   o chamado ser&aacute; re-tipificado e precisar&aacute; da aprova&ccedil;&atilde;o.</p>
			<p>Quando trata-se de erros, os analistas v&atilde;o precisar do m&aacute;ximo de informa&ccedil;&otilde;es sobre o erro
			   que voc&ecirc; possa fornecer, para agilizar tanto a an&aacute;lise quanto a resolu&ccedil;&atilde;o.</p>
			<p>Preencha o m&aacute;ximo de informa&ccedil;&otilde;es para nos ajudar com a localiza&ccedil;&atilde;o do erro.</p>
		    <p class='ui-state-error ui-widget-shadow'>O chamado de <i>erro</i> <b>n&atilde;o</b> deve ser usado para
			solicita&ccedil;&atilde;o de altera&ccedil;&otilde;es no programa, solicita&ccedil;&atilde;o de <b>novos processos</b> ou para
			altera&ccedil;&atilde;o de dados no banco de dados.<br>
			Para esse tipo de solicita&ccedil;&atilde;oes existem outros tipos de chamado.</p>
		</div>
		<h3><a href='#'>Mudan&ccedil;a em tela ou processo</a></h3>
		<div name='acc-2'>
		    <p>Este tipo de chamado &eacute; para a solicita&ccedil;&atilde;o de altera&ccedil;&atilde;o de um programa.</p>
		    <p>Inclui, por exemplo, a inclus&atilde;o ou exclus&atilde;o de uma coluna num relat&oacute;rio, ou adicionar um
		    bot&atilde;o de a&ccedil;&atilde;o, informa&ccedil;&atilde;o extra de algum tipo, etc.</p>
		    <p>Pode ser solicitada tamb&eacute;m a altera&ccedil;&atilde;o do agendamento da execu&ccedil;&atilde;o de um processo autom&aacute;tico
		    do sistema (integra&ccedil;&atilde;o, gera&ccedil;&atilde;o de extrato...) ou a altera&ccedil;&atilde;o desses processos.</p>
		    <p><b>N&atilde;o</b> deve ser usado para erros no programa, solicita&ccedil;&atilde;o de <b>novos processos</b> ou
		    para altera&ccedil;&atilde;o de dados no banco de dados. Para esse tipo de solicita&ccedil;&atilde;oes existem outros
		    tipo de chamado.</p>
		</div>
	</div>
<!--	Seleciona o programa (Assist, PedidoWeb ou PERLs)	-->
			<div id='hd_erro_tabs'>
			<h4>Selecione a área e o programa onde está o erro</h4>
				<ul>
<? if ($assist) {?>
					<li><a href='#admin'>Tela no ASSIST (&aacute;rea Admin)</a></li>
					<li><a href='#posto'>Tela no ASSIST (&aacute;rea do Posto)</a></li>
					<li><a href='#assist'>Tela no ASSIST (Admin e Posto)</a></li>
					<li><a href='#perla'>Processo autom&aacute;tico ASSIST</a></li>
<? } else {?>
					<li><a href='#pednet'>Tela no PedidoWeb</a></li>
					<li><a href='#perlp'>Processo autom&aacute;tico PedidoWeb</a></li>
<? }?>
            </ul>
<? if ($assist) {?>
			<div id='admin'>
			<h3>Dados do programa que gerou o erro</h3>
		    <form action='<?=$PHP_SELF?>' name='hdw' id='hdw' class='frm' method='post'
				   title='Informações para a abertura de chamado'>
		        <fieldset for='hdw' id='hd_erro_admin'>
		        <div id='hd_erro_admin_a'>
				<p>&nbsp;</p>
		<!--	Se o erro está na área do Admin, selecionar a aba e depois o(s) programa(s)	-->
		            <label for='progs_admin_erro'>
					Módulos da área do ADMIN</label><br>
					<select id='hd_erro_aba_ad' class='aa_ui_sel'
						 title='Selecione o módulo onde está o programa com erro'>
<?
    foreach (array_keys($a_abas_admin) as $value) {
    	echo str_repeat("\t", 6)."<option value='$value'>$value</option>\n";
    }
?>
					</select>
					</div>
                    <div id='hd_erro_prog_org'>
						<label for="hd_erro_programas">Telas do(s) módulo(s) selecionado(s)</label>
						<ul id="hd_erro_programas" class='connectedSortable ui-helper-reset'>
						</ul>
					</div>

                    <div id='hd_erro_prog_end'>
                        <p>Coloque aqui a(s)tela(s) que apresentam erro</p>
						<ul id="hd_erro_progs_erro" class='connectedSortable ui-helper-reset'>
                            <li class='ui-state-disabled'>Tela com erro</li>
						</ul>
					</div>
                    <br>
                    <p style='clear: both'>&nbsp;</p>
					<p><label for='hd_erro_desc'>Descri&ccedil;&atilde;o do erro</label><br>
					<textarea id='hd_erro_desc' disabled cols='80' rows='5'></textarea></p>
				</fieldset>
			</form>
			</div>
			<div id='posto'>
			ASSIST, Área do Posto
<!--		-->
            <fieldset id='abas_posto'>
			<legend>Progama na &aacute;rea do Posto</legend>
	            <label for='progs_posto_erro' title='Selecione a aba onde está o programa com erro'>
				Aba Posto</label>
				<select></select>
				<br>
                <label for="progs_posto_erro" title="Qual tela está com erro?">Tela com erro</label>
				<select></select>
			</fieldset>
			</div>
			<div id='assist'>
			ASSIST, Admin e Posto
<!--		-->
            <fieldset id='abas_assist'>
			<legend>Progama na &aacute;rea do Admin ou Posto</legend>
	            <label for='abas_assist_erro' title='Selecione a aba onde está o programa com erro'>
				Aba ADMIN ou Posto</label>
				<select></select>
				<br>
                <label for="progs_assist_erro" title="Qual tela está com erro?">Tela com erro</label>
				<select></select>
			</fieldset>
			</div>
			<div id='perla'>
			Processos automáticos
<!--		-->
            <fieldset id='abas_admin'>
			<legend>Selecione o processo que apresenta erro</legend>
	            <label for='aba_erro' title='Selecione a aba onde está o programa com erro'>
				Processos automáticos</label>
				<select></select>
				<br>
                <label for="prog_admin_erro" title="Qual tela está com erro?">Processo(s) com erro</label>
				<select></select>
			</fieldset>
			</div>
<? } else {?>
			<div id='pednet'>Pedido Web</div>
			<div id='perlp'>Processos automáticos</div>
<? }?>
	</div>
<!--		-->
</body>
</html>
