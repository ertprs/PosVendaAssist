<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "call_center,gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

//VALIDAÇÕES DE DATAS
if ( isset($_POST['gerar']) ) {
	
	if($_POST["data_inicial"]) $data_inicial = $_POST["data_inicial"];
	if($_POST["data_final"]) $data_final = $_POST["data_final"];

	if( empty($data_inicial) OR empty($data_final) )
		$msg_erro = traduz("Data Inválida");
		
	if(strlen($msg_erro)==0){
		
		list($di, $mi, $yi) = explode("/", $data_inicial);
		if(!checkdate($mi,$di,$yi)) 
			$msg_erro = traduz("Data Inválida");
			
	}
	
	if(strlen($msg_erro)==0){
	
		list($df, $mf, $yf) = explode("/", $data_final);
		if(!checkdate($mf,$df,$yf)) 
			$msg_erro = traduz("Data Inválida");
			
	}
	
	if(strlen($msg_erro)==0){
	
		$aux_data_inicial = "$yi-$mi-$di";
		$aux_data_final = "$yf-$mf-$df";
		
	}
	
	if(strlen($msg_erro)==0)
		if(strtotime($aux_data_final) < strtotime($aux_data_inicial))
			$msg_erro = traduz("Data Inválida.");
			
	if(strlen($msg_erro)==0)
		if (strtotime($aux_data_inicial) < strtotime($aux_data_final . ' -3 month')) 
			$msg_erro = traduz('O intervalo entre as datas não pode ser maior que 90 dias.');
			
}

$layout_menu = 'callcenter';
$title = traduz("RELATÓRIO DE MAILING - OS");
include "cabecalho.php";


?>
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script type="text/javascript" src="js/datePicker.v1.js"></script>
<? include "javascript_calendario_new.php";     include_once '../js/js_css.php';?>

<script type="text/javascript">
	$().ready(function(){
    	$('#data_inicial').datepick({startdate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
	});
</script>

<style type="text/css">
	.titulo_tabela{
		background-color:#596d9b;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.titulo_coluna{
		background-color:#596d9b;
		font: bold 11px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.msg_erro{
		background-color:#FF0000;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.formulario{
		background-color:#D9E2EF;
		font:11px Arial;
		text-align:left;
	}
	table.tabela tr td{
		font-family: verdana;
		font-size: 11px;
		border-collapse: collapse;
		border:1px solid #596d9b;
	}
	
</style>

<?if ($msg_erro){?>
	<table class="msg_erro" align="center" width="700px">
		<tr>
			<td> <?echo $msg_erro?></td>
		</tr>
	</table>
<?}?>

<form action="<?=$_SERVER['PHP_SELF'];?>" method="POST">

	<table cellspacing="0" cellpadding="1" align="center" class='formulario' width="700px">
		
		<tr>
		
			<td class="titulo_tabela"> <?=traduz('Parâmetros de Pesquisa ')?></td>
			
		</tr>
		
		<tr>
			<td>&nbsp;</td>
		</tr>
		
		<tr>
		
			<td>
			
				<table width="500px" align="center">
				
					<tr>
					
						<td>
						
							<label for="data_inicial"><?=traduz('Data Inicial')?></label><br />
							
							<input type="text" name="data_inicial" id="data_inicial" class="frm" size="12" value="<?=isset($_POST['data_inicial'])?$_POST['data_inicial'] : '' ?>" />
						
						</td>
						
						<td>
						
							<label for="data_final"><?=traduz('Data Final')?></label><br />
							
							<input type="text" name="data_final" id="data_final" class="frm" size="12" value="<?=isset($_POST['data_final'])?$_POST['data_final'] : ''?>"/>
						
						</td>
						
						<td>
						
							<label for="estado"><?=traduz('Estado')?></label><br />
							
							<select name="estado" id='estado' class='frm' style='font-size:11px'>
							<option value=""><?=traduz('Todos')?></option>
							<? $ArrayEstados = array('','AC','AL','AM','AP',
														'BA','CE','DF','ES',
														'GO','MA','MG','MS',
														'MT','PA','PB','PE',
														'PI','PR','RJ','RN',
														'RO','RR','RS','SC',
														'SE','SP','TO'
													);
								for ($i=0; $i<=27; $i++){
									echo"<option value='".$ArrayEstados[$i]."'";
									if ($estado == $ArrayEstados[$i]) echo " selected='selected' ";
									echo ">".$ArrayEstados[$i]."</option>\n";
								}
							?>
							</select>
							
						</td>
						
					</tr>
					
					<tr>
						<td>&nbsp;</td>
					</tr>
					
					<tr>
					
						<td colspan="3" style="padding-top:5px; text-align:center;">
						
							<input type="submit" name="gerar" value="<?=traduz('Gerar')?>" />
							
						</td>
						
					</tr>
					
				</table>
				
			</td>
		</tr>
		
		<tr>
			<td>&nbsp;</td>
		</tr>
		
	</table>
	
</form>

<!-- resultado da requisição -->
<?php 
	if ( isset($_POST['gerar']) ) {
	
		if(strlen($msg_erro)==0) {

			if ( !empty($_POST['estado']) ) 
				$cond = ' AND tbl_os.consumidor_estado = \'' . $_POST['estado'] . '\'';
			else 
				$cond = '';

			$sql = "SELECT DISTINCT
					tbl_os.consumidor_nome,
					tbl_os.consumidor_email,
					tbl_os.consumidor_cidade,
					tbl_os.consumidor_estado,
					tbl_produto.descricao,
					tbl_posto.cnpj::text,
					tbl_posto.nome
					
					FROM
					tbl_os
					JOIN tbl_produto USING(produto)
					JOIN tbl_posto_fabrica on (tbl_os.fabrica = tbl_posto_fabrica.fabrica and tbl_os.posto = tbl_posto_fabrica.posto)
					JOIN tbl_posto         on (tbl_posto_fabrica.posto = tbl_posto.posto)
				
					WHERE
					tbl_os.fabrica = $login_fabrica
					AND fn_valida_email(tbl_os.consumidor_email, false)
					AND (tbl_os.consumidor_email <> '' or  tbl_os.consumidor_email is not null)
					AND tbl_os.data_abertura BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
					$cond 
					ORDER BY consumidor_estado,consumidor_nome,consumidor_cidade;
				";


			$query = pg_query($con,$sql);
			if (pg_numrows($query) > 0) {
			
				//gera xls
				$arq      = "xls/relatorio-consumidores-contato-email-$aux_data_inicial-$aux_data_final.xls";
				$arq_html = "xls/relatorio-consumidores-contato-email-$aux_data_inicial-$aux_data_final.html";
				
				if(file_exists($arq_html))
					exec ("rm -f $arq_html");
				if(file_exists($arq))
					exec ("rm -f $arq");
				$fp = fopen($arq_html,"w");

				fputs($fp, '
					<html>
						<head>
							<title>'.traduz("RELATÓRIO DE EMAIL DOS CONSUMIDORES").'</title>
						</head>
						<body>
							<table border="1">
								<tr class="titulo_coluna">
									<th>'.traduz("Nome").'</th>
									<th>'.traduz("E-Mail").'</th>
									<th>'.traduz("Cidade").'</th>
									<th>'.traduz("Estado").'</th>
									<th>'.traduz("Produto").'</th>
									<th>'.traduz("ASSISTENCIA TECNICA").'</th>
									<th>'.traduz("CNPJ").'</th>
								</tr>
				');

				for ( $i=0; $i < pg_numrows($query); $i++ ) {
				
					$email			= trim(pg_result ($query,$i,'consumidor_email'));
					$nome			= trim(pg_result ($query,$i,'consumidor_nome'));
					$cidade			= trim(pg_result ($query,$i,'consumidor_cidade'));
					$estado			= trim(pg_result ($query,$i,'consumidor_estado'));
					$produto_desc	= trim(pg_result ($query,$i,'descricao'));
					$cnpj			= trim(pg_result ($query,$i,'cnpj'));
					$nome_posto		= trim(pg_result ($query,$i,'nome'));
					
					$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
					
					fputs($fp, '
						<tr bgcolor="'.$cor.'">
							<td nowrap>'.$nome.'</td>
							<td nowrap>'.$email.'</td>
							<td nowrap>'.$cidade.'</td>
							<td nowrap>'.$estado.'</td>
							<td nowrap>'.$produto_desc.'</td>
							<td nowrap>=TEXTO('.$cnpj.';"00000000000000")</td>
							<td nowrap>'.$nome_posto.'</td>
						</tr>
					');

				}

				fputs($fp, "\t\t	</table>
						</body>
					</html>" . PHP_EOL);

				rename($arq_html, $arq);

				echo '<hr /><center><button type="button" onclick="window.open(\''.$arq.'\')">Download em EXCEL</button></center><br />';

			}
			else
				echo '<p style="text-align:center;">
						'.traduz("Não foram encontrados resultados para esta pesquisa.").'
					  </p>';
		}
		
	}

include 'rodape.php'; ?>
