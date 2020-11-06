<?php 
include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';
include '../../helpdesk/mlg_funciones.php';

$login_fabrica = 15;

$arquivo = 'areas_atuacao_latina.txt';
$areas_atuacao = array( '0' => 'RefrigeraÁ„o Convencional' , 
						'1' => 'RefrigeraÁ„o EletrÙnica' ,
						'2' => 'Lavadora' ,
						'3' => 'Centrifuga' ,
						'4' => 'Ventiladores de Teto');

$style = '
<style type="text/css">
	
	fieldset#container{
		
		border:1px solid;
		display:table;
		width:900px;
		margin:auto;
		border-color: #E6E6E6;
		border-style: solid;
		border-radius: 15px;
		-moz-border-radius: 15px;
		-webkit-border-radius: 15px;
		height:500px;
		max-height:500px;
				
	}

	fieldset#container_res{
		
		border:1px solid;
		display:table;
		width:350px;
		margin:auto;
		border-color: #E6E6E6;
		border-style: solid;
		border-radius: 15px;
		-moz-border-radius: 15px;
		-webkit-border-radius: 15px;

	}

	legend{
		font-size: 16px;
		font-weight: bold;
		color: #447296;

	}

	div#div_mapa{

		float:left;
		display:table;
		margin-left:-10px;
		margin-top:10px;
		/*border: 1px #FF0000 solid;*/

	}


	div#error{
		margin:0px 0px 0px 280px;
		
		font:11px arial;
		color:#FF0000;
		text-align: justify;
		display: none;
		width:210px;
	}

	div#div_form_mapa{
		margin: 10px 0px 0px 0px;
		/*border:1px solid;*/
		width:210px;
		float:left;
		padding:0px 0px 0px 10px;

	}


	body{
		margin:0;
		background-color:#AABDC4;
	}

	img#mapa{
		margin: 0px 0px 0px 0px !important;
	}


	p#sel_cidade{
		display:none;
	}

	button{
		
		border: 1px solid #0D6894;
		color: white;
		font-size: 12px;
		padding: 1px 3px 1px 3px;
		background-color: #0D6894;
		cursor:pointer;
	}

	label{
		font:11px arial;
		color:#555;
		text-align: justify;
	}

	select{
		width:210px;
	}

	tr.res_posto{
		padding:10px 0px 0px 0px;
		font:bold 16px Arial
	}

	tr.res_posto_dados{
		padding:00px 0px 10px 0px;
		font:11px arial;
	}

	hr{
		margin:20px 0px 20px 0px;
		color:#666666;
		border:	dashed 1px;
	}
</style>';

if ($_REQUEST['familia']){
	$familias_p = $_REQUEST['familia'];
}

$sql = "SELECT DISTINCT tbl_familia.familia, tbl_familia.descricao, tbl_produto.linha
			FROM tbl_produto
			JOIN tbl_linha USING(linha)
			JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia
		WHERE tbl_produto.ativo IS TRUE
		  AND tbl_linha.fabrica = $login_fabrica
		  AND tbl_produto.familia IS NOT NULL 
		  AND tbl_familia.familia in ($familias_p)
		ORDER BY tbl_familia.descricao";
$res = pg_query($con, $sql);
$num_fams = pg_num_rows($res);
$linhas	  = array();
$familias = array();


for ($i=0; $i < $num_fams; $i++) {
	list($familia, $nome_familia, $linha) = pg_fetch_row($res, $i);
    $familias[$familia] = $nome_familia;
	if (!in_array($linha, $linhas)) $linhas[] = $linha;
	$linha_familia[$familia] = $linha;
}


##############################
#######               ########
#######     AJAX      ########
#######				  ########
#######	   inicio	  ########
#######               ########
##############################


// ajax do change do <select> dos estados
	if ($_GET['action']=='cidades') {
		
		$estado = $_GET['estado'];
		$familia = $_GET['familia'];
		$familias = explode(',', $familia);
		$linha = array();
		if ($estado == "") exit("<option SELECTED>Sem resultados</option>");

		if(strlen($estado) > 0) {
			
			foreach ($familias as $familia) {
				
				if (!in_array($linha_familia[$familia], $linha)){

					$linha[]  = $linha_familia[$familia];
					
				}
				
			}
			$linha = implode(',', $linha);

			$tot_i = false;
			$debug = $_REQUEST['debug'];
			$sql_cidades =	"SELECT LOWER(mlg_cidade)||'#('||count(mlg_cidade)||')' AS cidade
								FROM (
									SELECT x.posto, x.contato_cidade,x.tipo_posto,x.mlg_cidade
									FROM(
										(
											SELECT tbl_posto_fabrica.posto,contato_cidade,tipo_posto, UPPER(TRIM(TRANSLATE(tbl_ibge.cidade,'·‚‡„‰ÈÍËÎÌÓÏÔÛÙÚı˙˘¸Á¡¬¿√ƒ… »ÀÕŒÃœ”‘“’⁄Ÿ‹«','aaaaaeeeeiiiioooouuucAAAAAEEEEIIIIOOOOUUUC')))
											AS mlg_cidade
											FROM tbl_posto_fabrica
											JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto_fabrica.posto AND tbl_posto_linha.linha in ($linha)
											JOIN tbl_posto_fabrica_ibge on tbl_posto_fabrica.fabrica = tbl_posto_fabrica_ibge.fabrica and tbl_posto_fabrica.posto = tbl_posto_fabrica_ibge.posto
											JOIN tbl_ibge on tbl_posto_fabrica_ibge.cod_ibge = tbl_ibge.cod_ibge and tbl_ibge.estado = '$estado'
											WHERE tbl_posto_fabrica.credenciamento <> 'DESCREDENCIADO'
											AND tbl_posto_fabrica.tipo_posto <> 163
											AND tbl_posto_fabrica.posto not in(6359)
											AND tbl_ibge.estado='$estado'
											AND tbl_posto_fabrica.divulgar_consumidor IS TRUE
											AND tbl_posto_fabrica.fabrica=15
										)UNION(
											SELECT tbl_posto_fabrica.posto,contato_cidade,tipo_posto, UPPER(TRIM(TRANSLATE(tbl_posto_fabrica.contato_cidade,'·‚‡„‰ÈÍËÎÌÓÏÔÛÙÚı˙˘¸Á¡¬¿√ƒ… »ÀÕŒÃœ”‘“’⁄Ÿ‹«','aaaaaeeeeiiiioooouuucAAAAAEEEEIIIIOOOOUUUC')))
											AS mlg_cidade
											FROM tbl_posto_fabrica
											JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto_fabrica.posto AND tbl_posto_linha.linha in ($linha)
											WHERE tbl_posto_fabrica.credenciamento <> 'DESCREDENCIADO'
											AND tbl_posto_fabrica.tipo_posto <> 163
											AND tbl_posto_fabrica.posto not in(6359)
											AND tbl_posto_fabrica.contato_estado='$estado'
											AND tbl_posto_fabrica.divulgar_consumidor IS TRUE
											AND tbl_posto_fabrica.fabrica=15
										)
									) x
								) mlg_posto
								GROUP BY mlg_posto.mlg_cidade ORDER BY cidade ASC";
			
			
			$res_cidades = pg_query($con,$sql_cidades);
			$tot_i       = pg_num_rows($res_cidades);
			if ($debug) pre_echo($sql_cidades, "Resultado: $tot_i registro(s)");
	        if ($tot_i) echo "<option>Escolha a cidade</option>";
			if ($debug) pre_echo($cidades, "$tot_i postos");
			$cidades = pg_fetch_all($res_cidades);
	        foreach($cidades as $info_cidade) {
	            list($cidade_i,$cidade_c) = preg_split('/#/',htmlentities($info_cidade['cidade']));
	            $sel      = (strtoupper($cidade) == strtoupper($cidade_i))?" SELECTED":"";
				echo "\t\t\t<option value='$cidade_i'$sel>".ucwords($cidade_i." ".$cidade_c)."</option>\n";
	        }
	        if ($tot_i==0) echo "<option SELECTED>Sem resultados</option>";

		}
		exit;
	}




//ajax que exibe os postos relacionados a cidade escolhida no <select> das cidades
	if ($_GET['action']=='postos') {
		$estado = $_GET['estado'];
		$familia= $_GET['familia'];

		$familias = explode(',', $familia);
		$linha = array();

		if (isset($_GET['cidade'])) $cidade=strtoupper(utf8_decode($_GET['cidade']));
		if ($estado == "" or $cidade=="") exit("Erro na consulta!");

		foreach ($familias as $familia) {
				
			if (!in_array($linha_familia[$familia], $linha)){

				$linha[]  = $linha_familia[$familia];
				
			}
			
		}
		$linha = implode(',', $linha);

		echo $style;
		?>
		<fieldset id="container_res" >
			<legend>Resultados da Busca</legend>
			<table cellspacing='1' align='center' width='100%' id='postos'>
				<tbody>

		<?

		//ESTE BLOCO … RESPONS¡VEL POR EXIBIR OS POSTOS COM ORDENA«√O -> POSTOS DA CIDADE - POSTOS VIP CIDADE
		$sql1 = "SELECT distinct
					tbl_posto.posto,
					tbl_posto_fabrica.codigo_posto,
					TRIM(tbl_posto_fabrica.contato_endereco) AS endereco,
					tbl_posto_fabrica.contato_numero AS numero,
	                TRIM(tbl_posto.nome) AS nome,
					LOWER(TRIM(TRANSLATE(tbl_posto_fabrica.contato_cidade,'¡¬¿√ƒ… »ÀÕŒÃœ”‘“’⁄Ÿ‹«',
																	'·‚‡„‰ÈÍËÎÌÓÏÔÛÙÚı˙˘¸Á')))
																AS cidade_q,
					tbl_posto_fabrica.contato_estado AS estado_q,
					tbl_posto_fabrica.contato_bairro AS bairro,
					tbl_posto_fabrica.contato_complemento as complemento,
					tbl_posto_fabrica.contato_cep AS cep,
					tbl_posto_fabrica.categoria,
					tbl_posto_fabrica.nome_fantasia,
					CASE WHEN NOT (tbl_posto.latitude IS NULL)
						THEN tbl_posto.longitude ||','|| tbl_posto.latitude
						ELSE NULL END AS latlong,
	                TRIM(tbl_posto_fabrica.contato_email) AS email,
	                tbl_posto_fabrica.contato_telefones,	                
					tbl_posto_fabrica.contato_fone_comercial AS fone
				
				FROM   	tbl_posto
				JOIN    tbl_posto_fabrica USING (posto) 
				JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto_fabrica.posto AND tbl_posto_linha.linha in  ($linha)
				JOIN 	tbl_fabrica on (tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica)
				
				WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
				

				AND UPPER(TRIM(TRANSLATE(tbl_posto_fabrica.contato_cidade,'·‚‡„‰ÈÍËÎÌÓÏÔÛÙÚı˙˘¸Á¡¬¿√ƒ… »ÀÕŒÃœ”‘“’⁄Ÿ‹«',
														'aaaaaeeeeiiiioooouuucAAAAAEEEEIIIIOOOOUUUC')))
							ILIKE '%".tira_acentos($cidade)."%'
				AND tbl_posto.posto not in(6359)
				AND tbl_posto_fabrica.credenciamento <> 'DESCREDENCIADO'
				AND tbl_posto_fabrica.tipo_posto <> 163 
				AND tbl_posto_fabrica.contato_estado = '$estado'
				AND tbl_posto_fabrica.divulgar_consumidor IS TRUE
				ORDER BY tbl_posto_fabrica.categoria desc, nome_fantasia";
		
		$res1 = pg_query ($con,$sql1);
		$total_postos1 = ($tem_mapa=pg_num_rows($res1));
		$cidade1 = pg_fetch_result($res1, $total_postos1-1, 'cidade');

		if($total_postos1 > 0){?>
	        
	        <?
	        	
	        	$postos_cidade = array();
	        	for ($z = 0 ; $z < $total_postos1 ; $z++) {
	                $row1 = pg_fetch_array($res1, $z);
	                foreach ($row1 as $campo => $valor) {
	                    $$campo = trim($valor);
	                }

	                $postos_cidade[] = $posto;

	                $sql_cidades_atendidas = "
	                select tbl_ibge.cidade ||'('|| tbl_ibge.estado ||')' as cidades_atendidas 
	                from tbl_posto_fabrica_ibge 
	                JOIN tbl_ibge using(cod_ibge) 
	                WHERE tbl_posto_fabrica_ibge.posto = $posto 
	                and tbl_posto_fabrica_ibge.fabrica = $login_fabrica 
	                ";
	                $res_cidades_atendidas = pg_query($con,$sql_cidades_atendidas);
	                $cidades_atendidas = array();
	                for ($o=0; $o < pg_num_rows($res_cidades_atendidas); $o++) { 
	                	$cidades_atendidas[] = pg_result($res_cidades_atendidas,$o,'cidades_atendidas');
	                }
	                $cidades_atendidas = implode(', ', $cidades_atendidas);

	                $chars_replace = array('{','}','"');
					$contato_telefones = str_replace($chars_replace, "", $contato_telefones );
					
					$fones_latina = array();
					$fones_latina = preg_split('/,/', $contato_telefones);
					
					if(strlen($fone)==0 and strlen($fones_latina[0])==0 ){
						$fone  = $fones_latina[0];
					}

					if (strlen($fones_latina[1])==0){
						$fone2 = '';
					}else{

						$fone2 = " - ".$fones_latina[1];

					}

					$end_completo = $endereco . ", " . $numero . " - " .$bairro; 
					$end_mapa     = "q=$endereco, $numero, $cep, $cidade_q, $estado_q, Brasil";
	//  				if (!is_null($latlong)) $end_mapa = "ll=$latlong";
					$link_mapa = "<a title='Localizar no mapa' href='http://maps.google.com/maps?f=q&source=s_q&hl=pt-BR&$end_mapa&ie=windows-1252' target='_blank'>".
								 "<img src='http://www.google.com/options/icons/maps.gif' width='16'></a>";
	// 				}

					echo "<tr class='res_posto'>";
					$posto_nome = iif((strlen($nome_fantasia)>0),$nome_fantasia,$nome);
					
						echo "<td>$posto_nome</td>";
					
					echo "</tr>";
					
					echo "<tr class='res_posto_dados'>";

	// 				$tooltip = (strlen($end_completo)>=35)?" title='$end_completo'":"";
					echo "<td><b>Endere&ccedil;o:</b> $end_completo <br>";
					echo "<b>Complemento:</b> $complemento <br>";
					echo "<b>Telefone:</b> $fone $fone2 <br>";
					echo "<b>Cidade:</b> $cidade_q <br>";
					echo "<b>Estado:</b> $estado_q <br>";
					echo "<b>CEP:</b> $cep <br>";
					echo "<b>E-mail:</b> $email <br>";
					echo "<b>Cidades atendidas:</b> $cidades_atendidas <br>";
	               	echo "<b>Mapa:</b> $link_mapa <br></td>";
					
					echo "</tr>";
	               	echo "<tr><td><hr> </td></tr>";
					unset ($end_mapa, $link_mapa, $end_completo, $posto_nome, $email,$cidade_q,$estado_q,$cep);
				}

				$postos_cidade = implode(',', $postos_cidade);
	    }


		
		//ESTE BLOCO … RESPONS¡VEL POR EXIBIR OS POSTOS COM ORDENA«√O -> VIP (cidades atendidas) - CIDADES ATENDIDAS
		$sql = "SELECT distinct
					tbl_posto.posto,
					tbl_posto_fabrica.codigo_posto,
					TRIM(tbl_posto_fabrica.contato_endereco) AS endereco,
					tbl_posto_fabrica.contato_numero AS numero,
	                TRIM(tbl_posto.nome) AS nome,
					LOWER(TRIM(TRANSLATE(tbl_posto_fabrica.contato_cidade,'¡¬¿√ƒ… »ÀÕŒÃœ”‘“’⁄Ÿ‹«',
																	'·‚‡„‰ÈÍËÎÌÓÏÔÛÙÚı˙˘¸Á')))
																AS cidade,
					tbl_posto_fabrica.contato_estado AS estado,
					tbl_posto_fabrica.contato_bairro AS bairro,
					tbl_posto_fabrica.contato_complemento as complemento,
					tbl_posto_fabrica.contato_cep AS cep,tbl_posto_fabrica.categoria,
					tbl_posto_fabrica.nome_fantasia,
					CASE WHEN NOT (latitude IS NULL)
						THEN longitude ||','|| latitude
						ELSE NULL END AS latlong,
	                TRIM(tbl_posto_fabrica.contato_email) AS email,
	                tbl_posto_fabrica.contato_telefones,	                
					tbl_posto_fabrica.contato_fone_comercial AS fone
				
				FROM   	tbl_posto
				JOIN    tbl_posto_fabrica USING (posto) 
				JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto_fabrica.posto AND tbl_posto_linha.linha in  ($linha)
				JOIN 	tbl_posto_fabrica_ibge on (tbl_posto_fabrica.posto = tbl_posto_fabrica_ibge.posto and tbl_posto_fabrica.fabrica = tbl_posto_fabrica_ibge.fabrica)
				JOIN 	tbl_ibge on tbl_posto_fabrica_ibge.cod_ibge = tbl_ibge.cod_ibge
				JOIN 	tbl_fabrica on (tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica)
				
				WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
				

				AND UPPER(TRIM(TRANSLATE(tbl_ibge.cidade,'·‚‡„‰ÈÍËÎÌÓÏÔÛÙÚı˙˘¸Á¡¬¿√ƒ… »ÀÕŒÃœ”‘“’⁄Ÿ‹«',
														'aaaaaeeeeiiiioooouuucAAAAAEEEEIIIIOOOOUUUC')))
							ILIKE '%".tira_acentos($cidade)."%'
				AND tbl_posto.posto not in(6359) 
				AND tbl_posto.posto not in ($postos_cidade)
				AND tbl_posto_fabrica.credenciamento <> 'DESCREDENCIADO'
				AND tbl_posto_fabrica.tipo_posto <> 163 
				AND tbl_ibge.estado = '$estado'
				AND tbl_posto_fabrica.divulgar_consumidor IS TRUE 
				ORDER BY tbl_posto_fabrica.categoria desc, nome_fantasia";
			
			
			$res = pg_query ($con,$sql);
			$total_postos = ($tem_mapa=pg_num_rows($res));
			$cidade = pg_fetch_result($res, $total_postos-1, 'cidade');
			
			
			
			if($total_postos > 0){?>
	        
	        <?
	        	for ($i = 0 ; $i < $total_postos ; $i++) {
	                $row = pg_fetch_array($res, $i);
	                foreach ($row as $campo => $valor) {
	                    $$campo = trim($valor);
	                }

	                $sql_cidades_atendidas = "
	                select tbl_ibge.cidade ||'('|| tbl_ibge.estado ||')' as cidades_atendidas 
	                from tbl_posto_fabrica_ibge 
	                JOIN tbl_ibge using(cod_ibge) 
	                WHERE tbl_posto_fabrica_ibge.posto = $posto 
	                and tbl_posto_fabrica_ibge.fabrica = $login_fabrica 
	                ";
	                $res_cidades_atendidas = pg_query($con,$sql_cidades_atendidas);
	                $cidades_atendidas = array();
	                for ($o=0; $o < pg_num_rows($res_cidades_atendidas); $o++) { 
	                	$cidades_atendidas[] = pg_result($res_cidades_atendidas,$o,'cidades_atendidas');
	                }
	                $cidades_atendidas = implode(', ', $cidades_atendidas);

	                $chars_replace = array('{','}','"');
					$contato_telefones = str_replace($chars_replace, "", $contato_telefones );
					
					$fones_latina = array();
					$fones_latina = preg_split('/,/', $contato_telefones);
					
					if(strlen($fone)==0 and strlen($fones_latina[0])==0 ){
						$fone  = $fones_latina[0];
					}

					if (strlen($fones_latina[1])==0){
						$fone2 = '';
					}else{

						$fone2 = " - ".$fones_latina[1];

					}

					$end_completo = $endereco . ", " . $numero . " - " .$bairro; 
					$end_mapa     = "q=$endereco, $numero, $cep, $cidade, $estado, Brasil";
	//  				if (!is_null($latlong)) $end_mapa = "ll=$latlong";
					$link_mapa = "<a title='Localizar no mapa' href='http://maps.google.com/maps?f=q&source=s_q&hl=pt-BR&$end_mapa&ie=windows-1252' target='_blank'>".
								 "<img src='http://www.google.com/options/icons/maps.gif' width='16'></a>";
	// 				}

					echo "<tr class='res_posto'>";
					$posto_nome = iif((strlen($nome_fantasia)>0),$nome_fantasia,$nome);
					
					echo "<td>$posto_nome</td>";
					
					echo "</tr>";
					
					echo "<tr class='res_posto_dados'>";

	// 				$tooltip = (strlen($end_completo)>=35)?" title='$end_completo'":"";
					echo "<td><b>Endere&ccedil;o:</b> $end_completo <br>";
					echo "<b>Complemento:</b> $complemento <br>";
					echo "<b>Telefone:</b> $fone $fone2 <br>";
					echo "<b>Cidade:</b> $cidade <br>";
					echo "<b>Estado:</b> $estado <br>";
					echo "<b>CEP:</b> $cep <br>";
					echo "<b>E-mail:</b> $email <br>";
					echo "<b>Cidades atendidas:</b> $cidades_atendidas <br>";
	               	echo "<b>Mapa:</b> $link_mapa <br></td>";
					
					echo "</tr>";
	               	echo "<tr><td><hr> </td></tr>";
					unset ($end_mapa, $link_mapa, $end_completo, $posto_nome, $email,$cidade,$estado,$cep);
				}
			?>
	        </tbody>
			<?
				
			}
			?>
			</table>

			</fieldset>
			<?php
		exit;
	}


##############################
#######               ########
#######     AJAX      ########
#######				  ########
#######	     fim	  ########
#######               ########
##############################
$sqlInsertLog = "INSERT INTO tbl_log_conexao(programa) VALUES ('$PHP_SELF')";
$resInsertLog = pg_query($con, $sqlInsertLog);

?>
<!DOCTYPE html>
<html>
<head>
<script type="text/javascript" src="../../js/jquery.js"></script>
<script type="text/javascript">
	var php_self = window.location.pathname;
	$(function(){
		
		$('map area').click(function() {
			$('#estado').val($(this).attr('name'));
			$('#estado').change();
		});

		//change function para o <select> das familias (linhas no caso da exibiÁ„o na pagina)
		$('#familia').change(function() {

			$('#tblres').fadeOut('fast');

		    var estado = $('#estado').val();

			var cidade = $('#cidade').val();

			if (estado != '') {
				$('#estado').change();
				return false;
			}

		});

		//change function para o <select> dos estados
		$('#estado').change(function() {

		    var estado = $('#estado').val();
			var familia= $('#familia').val();

		    if (estado == '') {

				$('#sel_cidade').fadeOut(500);
				$('#tblres').html('').fadeOut(400);
				return false;

			}

			$.get(php_self, {'action': 'cidades','estado': estado, 'familia': familia},
			  function(data){

				if (data.indexOf('Sem resultados') < 0) {

					$('#sel_cidade').fadeIn(500);
				    $('#cidade').html(data).val('').removeAttr('disabled');

				} else {

					$('#sel_cidade').fadeIn(500);
				    $('#cidade').html(data).val('Sem resultados').attr('disabled','disabled');

				}

				
				$('#tblres').html('').fadeOut(400);

			});
		});

		//change function para o <select> das cidades
		$('#cidade').change(function() {

			$('#tblres').fadeOut('fast');
		    var estado = $('#estado').val();
			var cidade = $('#cidade').val();
			var familia= $('#familia').val();

			if (cidade == ''){

				$('#error').fadeIn('fast');
					$('#error').html('Selecione a Cidade');
				return false;

			}

			$.get(php_self, {'action': 'postos','estado': estado,'cidade': cidade, 'familia': familia},
			function(data){

					    if (data.indexOf('Nenhuma') < 0) {

							if ($('#mapabr fieldset > img').width() > 250) {

								$('#mapabr fieldset > img').animate({

									width: 150,
									marginRight: '+=125'
									}, function() {

									$(this).bind('mouseover', function() {

										$(this).animate({width: 276,marginRight: '-=125'});
										$('#mapabr fieldset').animate({height: 300});
										$(this).unbind('mouseover');

									});

								});

							}
							$('#mapabr fieldset').animate({height: 185});
							$('#tblres').html(data).fadeIn('normal');
		//					$('#tblres select[name^=familias_]').linkselect();

						}

			});
		});


		$('button').click(function () {
			$('#tblres').fadeOut('fast');
		    var estado = $('#estado').val();
			var cidade = $('#cidade').val();
			if (cidade != '' && estado != '') {
				$('#cidade').change();
				$('#error').hide();
				return false;
			}else{
				if (cidade == '') {
					
					$('#error').fadeIn('fast');
					$('#error').html('Selecione a Cidade');

				}
				if (estado == '') {

					$('#error').fadeIn('fast');
					$('#error').html('Selecione o Estado');

				}
				return false;
			}
		});

	});

	
</script>
<?php  echo $style; ?>
</head>

<body>
	<fieldset style='padding-left: 1em' id="container">
	<legend>&nbsp;Mapa da Rede&nbsp;</legend>
	
		<div id="div_mapa">
		    <img id="mapa" src='imagens/mapa_suggar.png' alt='Mapa do Brasil' title='Selecione o Estado'
						usemap='#Map2' style='float:left;margin-left: 1em;margin-right:3em' />
	    </div>
	    <div id="div_form_mapa">
			<form>
				<p>
					<label>Linha de Produto</label> <br>
					<select name="familia" id="familia">
						<?php 
							foreach ($areas_atuacao as $id=>$area) {
								if(file_exists($arquivo))
								{
									
									$arq = fopen($arquivo,"r");
									$i = 0;

									while(!feof($arq))
									{
									
										$row = fgets($arq);
									
										if(!empty($row))
										{

											$linha = explode(';',$row);

											if ($linha[0]==$id){

												$familias = explode(',',$linha[1]);
												$familias = implode(',', $familias);
												$familias = trim(str_replace('\n', '', $familias));
												?>
												<option value='<?php echo $familias ?>'>
													<?php echo $area ?>
												</option>
												<?php

											}

										}

									}

								}
						    }
						?>

					</select>
				</p>	
				
				<p>
					<label>Selecione o Estado (UF)</label>
					
					<br>

					<select title="Selecione o Estado" name="estado" id="estado" tabindex="1">
						<option></option>
						<?
						foreach ($estados as $uf=>$nome) {
							echo str_repeat("\t", 8)."<option value='$uf'>$nome</option>\n";
				    	}
						?>
				    </select>
				</p>

				<p id='sel_cidade'>
					
					<label for="cidade">Selecione a cidade:</label><br />
					<select name="cidade" id="cidade">
					</select>
					
				</p>

				<p>
					<button>Pesquisar</button>
				</p>
			</form>
	    </div>
		<div id='tblres' style="height:455px;	overflow:auto;	width:400px;	margin:10px 0px 0px 495px;" >
		</div>
	    <div id="error">
	    	
	    </div>
	
	
	</fieldset>
    <map name="Map2" id="Map2">
			<area shape="poly" name="RS" coords="122,238,142,221,164,232,148,262">
			<area shape="poly" name="SC" coords="143,214,172,215,169,235,143,219">
			<area shape="poly" name="PR" coords="138,202,148,191,166,192,175,207,171,214,139,213">
			<area shape="poly" name="SP" coords="152,187,162,173,182,174,186,187,188,194,197,190,197,198,177,206,168,190">

			<area shape="poly" name="MS" coords="136,195,156,171,138,159,124,159,117,182">
			<area shape="poly" name="MT" coords="117,151,143,151,160,127,160,106,120,105,111,101,98,102,107,117,100,131,102,142">
			<area shape="poly" name="RO" coords="93,126,98,118,94,113,86,105,86,100,80,93,73,102,67,108,67,116,77,121">
			<area shape="poly" name="AC" coords="50,106,10,91,13,101,23,104,29,104,30,112,44,113">
			<area shape="poly" name="AM" coords="11,87,53,101,74,88,105,91,117,55,103,43,89,50,76,43,77,30,62,37,43,30,40,38,33,75,21,75,13,82">
			<area shape="poly" name="RR" coords="74,13,74,18,82,25,84,41,93,40,102,31,96,21,97,9,90,11">
			<area shape="poly" name="PA" coords="112,33,114,40,127,50,117,82,121,95,162,99,174,77,173,68,193,48,172,54,158,55,145,45,133,25">
			<area shape="poly" name="AP" coords="145,25,153,23,157,13,164,29,153,41">
			<area shape="poly" name="MA" coords="196,50,185,72,194,90,212,82,215,59">

			<area shape="poly" name="TO" coords="179,83,165,120,189,128,185,101">
			<area shape="poly" name="GO" coords="159,166,148,157,165,131,188,136,170,151">
			<area shape="poly" name="PI" coords="201,92,216,86,223,64,228,85,219,98,207,99,206,107,199,107">
			<area shape="poly" name="RJ" coords="206,201,202,190,214,189,218,181,226,187">
			<area shape="poly" name="MG" coords="171,164,190,162,192,145,205,140,217,146,224,154,217,169,212,183,193,183,185,170">
			<area shape="poly" name="ES" coords="236,167,228,162,221,177,226,183">
			<area shape="poly" name="BA" coords="198,113,196,134,213,133,230,139,235,146,231,157,235,160,240,142,241,127,249,124,243,113,243,105,234,106,225,107,215,107,207,115">
			<area shape="poly" name="CE" coords="230,59,235,86,241,86,252,70,239,61">
			<area shape="poly" name="SE" coords="250,108,248,113,251,118,257,113,252,109">

			<area shape="poly" name="AL" coords="266,102,258,104,251,102,260,110,266,104">
			<area shape="poly" name="PE" coords="269,94,269,99,262,99,256,101,251,98,246,98,239,96,234,100,231,95,234,92,243,93,251,94,255,96">
			<area shape="poly" name="PB" coords="269,85,262,85,257,88,253,85,248,87,257,90,263,91,268,89">
			<area shape="poly" name="RN" coords="256,73,249,81,256,80,257,83,270,82,265,76">
			<area shape="poly" name="DF" coords="168,162,171,153,183,149,182,161">
	</map>
</body>
</html>
