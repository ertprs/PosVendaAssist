<?
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_usuario.php';
include_once 'funcoes.php';

$os 		= $_GET['os'];
$sinal 		= 'null';
$excluir 	= 'null';
$lancar 	= 'null';
$conserto 	= 'null';
$tela 		= $_GET['tela'];

?>

<link media="screen" type="text/css" rel="stylesheet" href="bootstrap/css/bootstrap.css" />
<link media="screen" type="text/css" rel="stylesheet" href="bootstrap/css/extra.css" />
<link media="screen" type="text/css" rel="stylesheet" href="css/tc_css.css" />
<link media="screen" type="text/css" rel="stylesheet" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
<link media="screen" type="text/css" rel="stylesheet" href="bootstrap/css/ajuste.css" />

<script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script type="text/javascript" src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
<script type="text/javascript" src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
<script type="text/javascript" src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
<script type="text/javascript" src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
<script type="text/javascript" src="bootstrap/js/bootstrap.js"></script>
<script src="plugins/shadowbox/shadowbox.js" type="text/javascript"></script>

<script>
	function fecharJanela(fechar){		
		if(fechar == 'Não' || fechar == 'nao'){			
			window.parent.Shadowbox.close();
		}
	}

	function confirmaJanela(confirma){
		if(confirma == 'Sim' || confirma == 'sim'){			
			window.parent.fechaOS(<?=$os?>,<?=$sinal?>,<?=$excluir?>,<?=$lancar?>,<?=$conserto?>);
			window.parent.Shadowbox.close();
		}
	}

	function confirmaJanelaFechamento(confirma){
		if(confirma == 'Sim' || confirma == 'sim'){			
			window.parent.confirmafecha();
			window.parent.Shadowbox.close();
		}
	}

</script>
<style type="text/css">
	.titulo_tabela{
	    background-color:#596d9b;
	    font: bold 14px "Arial";
	    color:#FFFFFF;
	    text-align:center;
	}

	.formulario{
	    background-color:#D9E2EF;
	    font:11px Arial;
	    text-align:left;
	}

	.subtitulo{

	    background-color: #7092BE;
	    font:bold 14px Arial;
	    color: #FFFFFF;
	    text-align:center;
	}

	table.tabela tr td{
	    font-family: verdana;
	    font-size: 11px;
	    border-collapse: collapse;
	    border:1px solid #596d9b;
	}

	.espaco{
	    padding-left:80px;
	    width: 220px;
	}

	.fonte_botao{
		font-weight: bold;
		color: #FF0000;
	}	
</style>

<br><br>
<center>
	<form name="frm_confirmacao" method="post" action="<?echo $PHP_SELF?>">
		
		<table align="center" class="formulario" width="700" border="0">
			<tbody>
			    <tr>
			        <td class="titulo_tabela" align="center"><?php echo traduz('confirmação.de.recebimento.da.peça');?></td>
			    </tr>
			</tbody>
		</table>

		<table align="center" class="formulario" width="700" border="0">
			<tbody>
			    <tr align='center' class='espaco'>
			        <td>
			        	<br>
			        	<? 
			        		echo traduz('essa.os.poderá.ser.finalizada.após.a.confirmação.do.recebimento.da.peça') . '<br><br>';
			        		echo '<b><font size="4">' . traduz('você.confirma.o.recebimento.da.peça.?') . '</font></b>';
			        	?>
			        	<br><br>
			        	<?
			        		if($tela == 'os_fechamento'){
			        			$chama_funcao = 'confirmaJanelaFechamento(this.value);';
			        		} else {
			        			$chama_funcao = 'confirmaJanela(this.value);';
			        		}
			        	?>
						<input type="button" name='btn_sim' value='<?=traduz('sim')?>' id='sim' class='fonte_botao' onclick='<?=$chama_funcao ?>' />&nbsp;&nbsp;&nbsp;
						<input type="button" name='btn_nao' value='<?=traduz('nao')?>' id='nao' class='fonte_botao' onclick='fecharJanela(this.value);' />
						<br><br>
			        </td>
			    </tr>
			</tbody>
		</table>
	</form>
</center>