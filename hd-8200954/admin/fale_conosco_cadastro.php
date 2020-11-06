<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';

if($_GET['ver_resultado'] == 'sim') {
	echo "<table width='762' cellpadding='5' cellspacing='0' border='0' >";
	echo "<tr>";
	echo "<td colspan='3' bgcolor='#485989' class='conteudo'>
	<font size='1' face='Arial' color='#ffffff'><b>FALE CONOSCO</b></font>";
	echo "</td >";
	echo "</tr><br>";

	echo "<tr>";
		$sql = "SELECT * from tbl_fale_conosco ORDER BY ordem";
		$res = pg_query($con,$sql);

		for($i =1;$i<=pg_numrows($res);$i++){
			$j=$i-1;
			$fale_conosco = pg_fetch_result($res,$j,fale_conosco);
			$ordem=pg_fetch_result($res,$j,ordem);
			$descricao=pg_fetch_result($res,$j,descricao);
			echo "<td  valign='top' id='$ordem'>";
			echo "$descricao</TD>";

			if($i > 0 AND $i%3 == 0) {
				echo "</tr><tr>"; 
			}
		}
	echo "</tr>";
	echo "</table>";
	exit;
}

if ($_GET["ajax_excluir"]=='sim' and isset($_GET['excluir_ordem'])) {
	$res = pg_query($con,"BEGIN TRANSACTION");
	$sql = "DELETE FROM tbl_fale_conosco WHERE fale_conosco=".$_GET['excluir_ordem'];
	$res = pg_query($con,$sql);

	if (strlen($msg_erro) == 0) {
		$res = pg_query($con,"COMMIT TRANSACTION");
		echo "ok";
	}else{
		$res = pg_query($con,"ROLLBACK TRANSACTION");
		echo "erro";
	}
	exit;
}

if (isset($_GET["ids"])) {
	$ids = $_GET["ids"];
	$res = pg_query($con,"BEGIN TRANSACTION");
	
	$total_ids = count($ids);
	for ($idx = 0; $idx < $total_ids; $idx++) {
		$fale_conosco = $ids[$idx];
		$sql = "UPDATE tbl_fale_conosco SET ordem=".($idx+1)." WHERE fale_conosco=$fale_conosco";
		
		$res = pg_query($con,$sql);
	}
	
	if (strlen($msg_erro) == 0) {
		$res = pg_query($con,"COMMIT TRANSACTION");
	}else{
		$res = pg_query($con,"ROLLBACK TRANSACTION");
	}
	exit;
}

$layout_menu = "cadastro";
$title = "Manutenção de Fale Conosco";
echo "<center>";
include 'cabecalho.php';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
	<script type="text/javascript" src="js/jquery.editable-1.3.3.js"></script>
	<!-- <script type="text/javascript" src="js/fckeditor/fckeditor.js"></script> 
	<script type="text/javascript" src="js/thickbox.js"></script> 
	<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" /> -->
	<script type="text/javascript" src="plugins/shadowbox/shadowbox.js"></script>
	<link rel="stylesheet" href="plugins/shadowbox/shadowbox.css" type="text/css" media="screen" />

	<script type="text/javascript">
	$(function(){
		Shadowbox.init();
	});
	</script>

	<style type="text/css">
		h1 { font-size:16pt; }
		h2 { font-size:13pt; }
		ul { width:700px; list-style-type: none; margin:0px; padding:0px; }
		li { float:left; padding:5px; }
		li .container {position:relative;text-align:left;width:210px; height:310px; padding:15px 5px 0 5px; border:solid 1px black;background-color:WhiteSmoke;word-wrap: break-word }
		
		.placeHolder div { background-color:white!important; border:dashed 1px gray !important; }
		.closeWidget{
			margin: 0 0 -100px 0;
			
		}
	</style>
</head>
<body>
	<form name="frm_fale" method="post" action="<? echo $PHP_SELF ?>">
	<div id='mensagem'></div>
	<br/>
	<br/>
	<input type='button' style='position:relative' rel='button' name='btn_ver'  value='Visualizar o Resultado' onclick='verResultado()' >
	<!-- <a href='fale_conosco_cadastro_editar.php?novo=s&keepThis=true&TB_iframe=true&height=400&width=500' rel="shadowbox"  title='Editar Cadastro'> -->
	<a href='fale_conosco_cadastro_editar.php?novo=s&keepThis=true&TB_iframe=true&height=400&width=500' class='thickbox'  title='Editar Cadastro'>
	<input type='button' style='position:relative' rel='button' name='btn_novo'  value='Novo Cadastro' onclick='novo_cadastro()' >
	</a>
	<input type='button' name='arrastar' value='Mudar de posição' id='arrastar' onclick='mudarPosicao()'>
	<br>
    <div id='principal'>
		
		<?
		echo "<ul id='nomes' >";

			$sql = "SELECT * from tbl_fale_conosco ORDER BY ordem";
			$res = pg_query($con,$sql);

			for($i =0;$i<pg_numrows($res);$i++){
				$fale_conosco = pg_fetch_result($res,$i,fale_conosco);
				$ordem=pg_fetch_result($res,$i,ordem);
				$descricao=pg_fetch_result($res,$i,descricao);
				echo "<li itemID='$fale_conosco' id='$fale_conosco'>";
				
				echo "<div class='container' name='descricao_$fale_conosco' id='text_$fale_conosco'>";
					echo "<p >$descricao</p>";
					
						echo "<a href='fale_conosco_cadastro_editar.php?editar=s&id=$fale_conosco&ordem=$ordem&keepThis=true&TB_iframe=true&height=400&width=500' rel='shadowbox;;width=500;height=400'  title='Editar Cadastro'><input type='button' id='btn_editar' value='Editar' style='cursor:pointer;font: 12px Arial;position:absolute;bottom:0; margin: 0 0 0 45px;'></a>";
						// echo "<a href='fale_conosco_cadastro_editar.php?editar=s&id=$fale_conosco&ordem=$ordem&keepThis=true&TB_iframe=true&height=400&width=500' class='thickbox'  title='Editar Cadastro'><input type='button' id='btn_editar' value='Editar' style='cursor:pointer;font: 12px Arial;position:absolute;bottom:0; margin: 0 0 0 45px;'></a>";
					
						echo "<input type='button' value='Excluir' rel='$fale_conosco' class='closeWidget' style='cursor:pointer;font: 12px Arial;margin:5px;position:absolute;bottom:0; margin: 0 0 0 100px;'>";					
					
				echo "</div>";
				
			
				
				echo "</li>";
			}
			
			
		echo "</ul>";
	?>
		</div>
		<br/>

		
		<script type="text/javascript" src="js/jquery.dragsort-0.3.10.js"></script>
		<script type="text/javascript">
			window.onload = function(){
				

				//$('div[rel=fechar]').before('<span class="closeWidget"><img src="../helpdesk/imagem/closebox.png" alt=""/></span>');
				$('.closeWidget').click(function(){
					if(confirm('Deseja realmente excluir este contato?') == true) {
						var esconde = $(this).parent();
						var ordem = $(this).attr('rel');
						$.get(
							"<?=$PHP_SELF?>",
							{
								excluir_ordem: ordem,
								ajax_excluir: 'sim'
							},
							function(resposta){
								if(resposta=='ok'){
									esconde.hide().html('Excluído com sucesso');
									$('#mensagem').html('Excluído com sucesso');
									
								}else{
									
									$('#mensagem').html('Ocorreu Erro, tente novamente');
									window.location.reload(true);
								}
							}
						)
					}
				});
			}

			function verResultado(){
				url = "<?=$PHP_SELF?>?ver_resultado=sim" ;
				window.open(url, "", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=762, height=500, top=18, left=0");
			
			}
			function mudarPosicao(){
				$("#nomes").dragsort({ dragSelector: "div", placeHolderTemplate: "<li class='placeHolder'><div style='width:220px;height:310px'>&nbsp;</div></li>" });
				$("input[rel=button]").hide();
				$('#arrastar').hide();
				if($('#finalizar').length > 0) {
					$('#finalizar').show();
				}else{
					$('#principal').before("<input type='button' value='Finalizar' onclick='saveOrder()' id='finalizar'>");
				}
			};

		    function saveOrder() {
				$("#nomes").dragsort({dragSelector:"input"});
		        var data = new Array();
		        $("#nomes li").each(function(i, elm) { data[i] = $(elm).attr("itemID"); });
				$('#finalizar').hide();
				$.get("<?=$PHP_SELF?>", { "ids[]": data },function(){
						window.location.reload(true);	
					}
				);

		    };

	    </script>
        
        <div style="clear:both;"></div>
    </div>
	

	
<? include "rodape.php"; ?>

</body>
</html>
