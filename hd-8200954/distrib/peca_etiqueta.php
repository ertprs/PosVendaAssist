<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
  
if($_POST){
	$pecas = $_POST['peca_etiqueta'];
	$pecas = implode(',',$pecas);

	$res = pg_query($con,"DELETE FROM tmp_etiqueta_contagem_4311;");
	$msg_erro = pg_last_error($con);

        $sql = "INSERT INTO tmp_etiqueta_contagem_4311(peca,referencia,descricao,localizacao,qtde,data)
		SELECT  tbl_peca.peca,
		tbl_peca.referencia, 
		tbl_peca.descricao, 
		tbl_posto_estoque_localizacao.localizacao, 
		tbl_posto_estoque.qtde, 
		TO_CHAR (CURRENT_DATE,'DD/MM/YYYY')
		FROM tbl_peca
		JOIN tbl_posto_estoque_localizacao ON tbl_posto_estoque_localizacao.posto = 4311 
		AND tbl_peca.peca = tbl_posto_estoque_localizacao.peca
		JOIN tbl_posto_estoque             ON tbl_posto_estoque.posto = 4311 
		AND tbl_peca.peca = tbl_posto_estoque.peca 
		WHERE tbl_peca.peca IN($pecas)";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_last_error($con);
}


$title = "Etiquetas do Estoque";
?>
<html>
<head>
<title><?php echo $title ?></title>
<link type="text/css" rel="stylesheet" href="css/css.css">

<style>

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.sucesso{
	background-color:green;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}
</style>

<?include "javascript_calendario_new.php"; ?>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script type="text/javascript">
$(document).ready(function (){
	
	 $('form').submit(function(){
		$('#peca_etiqueta option').attr('selected','selected');
          });


	function formatItem(row) {
		return row[0] + " - " + row[1] + " - " + row[2];
	}

	function formatResult(row) {
		return row[0];
	}

	$("#peca_referencia").autocomplete("<?echo 'peca_consulta_ajax.php?busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1]; return row[2];}
	});

	$("#peca_referencia").result(function(event, data, formatted) {
		$("#peca_referencia").val(data[1]) ;
		$("#peca_descricao").val(data[2]) ;
		$("#peca").val(data[3]) ;
	});

	$("#peca_descricao").autocomplete("<?echo 'peca_consulta_ajax.php?busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1]; return row[2];}
	});

	$("#peca_descricao").result(function(event, data, formatted) {
		$("#peca_referencia").val(data[1]) ;
		$("#peca_descricao").val(data[2]) ;
		$("#peca").val(data[3]) ;
	});

	
	$('#peca_etiqueta option:selected').remove();
	if($('.select').length ==0) {
		$('#peca_etiqueta').addClass('select');
	}
});

function addItPeca() {
	if ($('#peca_referencia').val()=='') return false;
	if ($('#peca_descricao').val()=='') return false;
	if ($('#peca').val()=='') return false;
	var ref_peca  = $('#peca_referencia').val();
	var desc_peca = $('#peca_descricao').val();
	var peca      = $('#peca').val();
	
	$('#peca_etiqueta').append("<option value='"+peca+"'>"+ref_peca+ ' - ' + desc_peca +"</option>");
	
	if($('.select').length ==0) {
		$('#peca_etiqueta').addClass('select');
	}

	$('#peca_referencia').val("").focus();
	$('#peca_descricao').val("");
	$('#peca').val("");
}

function delItPeca() {
	$('#peca_etiqueta option:selected').remove();
        if($('.select').length ==0) {
        	$('#peca_etiqueta').addClass('select');
        }
}
</script>
</head>
<body>
<? include 'menu.php'; ?>
<center><h1><? echo $title; ?></h1></center>
<form name="frm_etiqueta" action="<? echo $PHP_SELF ?>" method="post">
	<table border='0' align='center' cellspacing='1' cellpadding='2' class='formulario' style='width: 600px'>
		<? if($_POST AND !empty($msg_erro)){ ?>
		<tr class='msg_erro'>
			<td colspan='4'>Erro ao separar os itens</td>
		</tr>
		<? } ?>

		<? if($_POST AND empty($msg_erro)){ ?>
		<tr class='sucesso'>
			<td colspan='4'>Itens separados com sucesso</td>
		</tr>
		<? } ?>

		<tr>
			<td colspan='4' class='titulo_tabela'>Parâmetro de Pesquisa</td>
		</tr>
		<tr>
			<td colspan='4'>&nbsp;</td>
		</tr>
		<tr >
			<td style='padding-left:65px;'>
				<label for="">Referência</label> <br>
				<input class='frm' type="hidden" name="peca"  id="peca" value="" >
				<input class='frm' type="text" name="peca_referencia"  id="peca_referencia" value="" size="12" maxlength="20">
			</td>
			<td>
				<label for="">Descrição:</label><br>
				<input class='frm' type="text" name="peca_descricao" id="peca_descricao" value="" size="45" maxlength="50">
			</td>
		</tr>
		<tr>
			<td colspan="2" align='right'>
				<input type='button' name='adicionar_peca' id='adicionar_peca' value='Adicionar' class='frm' onClick='addItPeca();' style='margin-right:70px;'>
			</td>
		</tr>

		<tr>
			<td align='center' colspan="3">
				<select multiple="multiple" SIZE='6' id='peca_etiqueta' class='select ' name="peca_etiqueta[]" class='frm' style='width:470px;'>
				<?
					if(count($peca_etiqueta) > 0) {
						for($i =0;$i<count($peca_etiqueta);$i++) {
							list($ref,$qtde) = explode('|', $peca_etiqueta[$i]);
							$sql = " SELECT tbl_peca.referencia,
											tbl_peca.descricao
									FROM tbl_peca
									WHERE fabrica = $login_fabrica
									AND   referencia  = '".$ref."'";
							$res = pg_query($con,$sql);
							if(pg_num_rows($res) > 0){
								echo "<option value='".pg_fetch_result($res,0,referencia);
                                if($qtde){
                                    echo "|".$qtde;
                                }
                                echo "' >";

                                echo pg_fetch_result($res,0,referencia) . " - " . pg_fetch_result($res,0,descricao);
                                if($qtde){
                                    echo " - ".$qtde;
                                }
                                 echo "</option>";
							}
						}
					}
				?>
				</select>
			</td>
		</tr>
		<tr>
			<td colspan="2" align='right'>
				<input type="button" value="Remover" onClick="delItPeca();" class='frm' style='margin-right:70px;'>
			</td>
		</tr>
		<tr>
			<td colspan='4'>&nbsp;</td>
		</tr>

		<tr>
			<td colspan='4' align="center"><input type="submit" value="Enviar"></td>
		</tr>

		<tr>
			<td colspan='4'>&nbsp;</td>
		</tr>
	</table>
</form>

<? include "rodape.php"; ?>
</body>
