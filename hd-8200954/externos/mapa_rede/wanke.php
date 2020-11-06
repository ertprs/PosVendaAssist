<?php
include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';
include 'mlg_funciones.php';

$fabrica = 91;

//	AJAX
if ($_GET['action']=='cidades') {
	$estado = $_GET['estado'];
	if ($estado == "") exit("<OPTION SELECTED>Sem resultados</OPTION>");

	if(strlen($estado) > 0) {
		$tot_i = false;
		$sql_cidades =	"SELECT  LOWER(mlg_cidade)||'#('||count(mlg_cidade)||')' AS cidade
							FROM (SELECT tbl_posto_fabrica.posto,tipo_posto,UPPER(TRIM(TRANSLATE(contato_cidade,'·‚‡„‰ÈÍËÎÌÓÏÔÛÙÚı˙˘¸Á¡¬¿√ƒ… »ÀÕŒÃœ”‘“’⁄Ÿ‹«',
																							  'aaaaaeeeeiiiioooouuucAAAAAEEEEIIIIOOOOUUUC')))
														AS mlg_cidade,
										contato_estado	AS mlg_estado
							FROM tbl_posto_fabrica
							WHERE credenciamento ='CREDENCIADO'
								AND tbl_posto_fabrica.posto NOT IN(6359)
								AND tbl_posto_fabrica.tipo_posto <> 268
								AND contato_estado='$estado' AND fabrica=$fabrica) mlg_posto
							GROUP BY mlg_posto.mlg_cidade ORDER BY cidade ASC";
		$res_cidades = pg_query($con,$sql_cidades);
        if (is_resource($res_cidades)) {
    		$tot_i       = pg_num_rows($res_cidades);
            if ($tot_i == 0) exit("<OPTION SELECTED>Sem resultados</OPTION>");

    		$cidades     = pg_fetch_all($res_cidades);
            if ($tot_i) echo "<option></option>";
    		if ($debug) pre_echo($cidades, "$tot_i postos");
            foreach($cidades as $info_cidade) {
                list($cidade_i,$cidade_c) = preg_split('/#/',htmlentities($info_cidade['cidade']));
                $sel      = (strtoupper($cidade) == strtoupper($cidade_i))?" SELECTED":"";
    			echo "\t\t\t<OPTION value='$cidade_i'$sel>".ucwords($cidade_i." ".$cidade_c)."</OPTION>\n";
            }
        } else {
    		if ($debug) pre_echo($sql_cidades, "Resultado: $tot_i registro(s)");
            exit('KO|Erro ao acessar o Sistema Telecontrol.');
        }
	}
	exit;
}

if ($_GET['action']=='postos') {
	$estado = $_GET['estado'];
	if (isset($_GET['cidade'])) $cidade=strtoupper(utf8_decode($_GET['cidade']));
	if ($estado == "" or $cidade=="") exit("Selecione o Estado e a Cidade para Pesquisar!");

	$sql = "SELECT
				tbl_posto.posto,
                TRIM(tbl_posto.nome)						AS nome,
				TRIM(tbl_posto_fabrica.contato_endereco)	AS endereco,
				tbl_posto_fabrica.contato_numero			AS numero,
				tbl_posto_fabrica.contato_complemento		AS complemento,
				LOWER(TRIM(TRANSLATE(tbl_posto_fabrica.contato_cidade,'¡¬¿√ƒ… »ÀÕŒÃœ”‘“’⁄Ÿ‹«',
																'·‚‡„‰ÈÍËÎÌÓÏÔÛÙÚı˙˘¸Á')))
															AS cidade,
				tbl_posto_fabrica.contato_bairro			AS bairro,
				tbl_posto_fabrica.contato_cep				AS cep,
				tbl_posto_fabrica.contato_estado			AS estado,
				tbl_posto_fabrica.nome_fantasia,
                tbl_posto.latitude  AS longitude,
                tbl_posto.longitude AS latitude,
                TRIM(LOWER(tbl_posto_fabrica.contato_email)) AS email,
				tbl_posto_fabrica.contato_fone_comercial	AS fone,
				tbl_posto.fantasia AS fantasia
			FROM  tbl_posto
			JOIN  tbl_posto_fabrica USING (posto)
			JOIN  tbl_fabrica       USING (fabrica)
			WHERE tbl_posto_fabrica.fabrica = $fabrica
			  AND tbl_posto_fabrica.contato_estado ILIKE '$estado'
			  AND UPPER(TRIM(TRANSLATE(contato_cidade,'·‚‡„‰ÈÍËÎÌÓÏÔÛÙÚı˙˘¸Á¡¬¿√ƒ… »ÀÕŒÃœ”‘“’⁄Ÿ‹«',
													  'aaaaaeeeeiiiioooouuucAAAAAEEEEIIIIOOOOUUUC')))
						= '".tira_acentos($cidade)."'
			AND tbl_posto.posto not in(6359,20462)
			AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
			AND tbl_posto_fabrica.tipo_posto <> 268
			AND tbl_posto_fabrica.divulgar_consumidor IS TRUE
			ORDER BY tbl_posto_fabrica.contato_bairro, tbl_posto.nome";
			//echo nl2br($sql);
		$res = pg_query($con,$sql);
		$total_postos = ($tem_mapa=pg_num_rows($res));
		$cidade = pg_fetch_result($res, $total_postos-1, cidade);
        if ($debug) exit($sql);
		if($total_postos > 0){
    		echo "<h4>Rela&ccedil;&atilde;o de Postos ";
    		echo ($cidade<>"")?"da cidade de <span class='nome_cidade'>".change_case($cidade,'l')."</span> ":"";
    		echo ($estado=='DF')?"no Distrito Federal":"no estado de {$estados[$estado]}";
    		echo "</h4>";

			for ($i = 0 ; $i < $total_postos ; $i++) {
                $row = pg_fetch_array($res, $i);
                foreach ($row as $campo => $valor) {
                    $$campo = trim($valor);
                }
//                 p_echo("preg_replace(\"/[\.|\,|\,$numero|\,".addcslashes($numero{$complemento},'\'".,/\\$[]-+*?')."|$complemento]$/\", '', $endereco))");
                $endereco = preg_replace("/[\.|\,|\,$numero|\,".addcslashes($numero{$complemento},'\'".,/\\$[]-+*?')."|".addcslashes($complemento,'\'".,/\\$[]')."]$/", '', $endereco);
				$end_completo = "<address>$endereco, $numero $complemento<br>".
                                "<b>CEP:</b> $cep - <b>Bairro:</b> $bairro<br>".
                                mb_convert_case($cidade, MB_CASE_TITLE)." - $estado</address>\n";
				$end_mapa     = "$endereco, $numero, $cep, $cidade, $estado, Brasil";
				if (is_numeric($longitude) and is_numeric($latitude)) { // lat e long est„o ao contr·rio no banco
    				$link_mapa = "<a title='Localizar no mapa' href='https://maps.google.com/maps?f=q&source=s_q&hl=pt-BR&sll=$latitude,$longitude&ie=utf-8' target='_blank'>";
                } else {
    				$link_mapa = "<a title='Localizar no mapa' href='https://maps.google.com/maps?f=q&source=s_q&hl=pt-BR&q=$end_mapa' target='_blank'>";
				}
				$link_mapa.= "<img src='imagens/mapIcon.png' width='24'></a>";
				$posto_nome =  $nome_fantasia;//iif((strlen($fantasia)>0),$fantasia,$nome_fantasia);
				$tooltip .= " title='".iif(($posto_nome==$nome_fantasia),"$posto_nome ($nome)",
										iif((strlen($posto_nome)>=50),"$posto_nome"),'')."'";

                if (strlen($email)>5 and is_email($email)) {
                	$link_email = "<a href='mailto:".mb_strtolower($email)."'>$email</a>";
                } else {
					$link_email = "<img src='/mlg/imagens/cross.png'>";
				}

				$linhas_posto = array();

				$sql_linhas = "SELECT linha, nome FROM tbl_linha JOIN tbl_posto_linha USING(linha) WHERE fabrica = $fabrica AND posto=$posto";
				$res_linhas_posto = pg_query($con, $sql_linhas);
				if (is_resource($res_linhas_posto)) {
					for ($l = 0; $l < pg_num_rows($res_linhas_posto); $l++) {
						$linhas_posto[] = pg_result($res_linhas_posto, $l, nome);
					}
				} else {
					$msg_erro[]= 'Erro ao acessar o Sistema Telecontrol.';
				}

                echo "
					<dl id='posto_$posto'>
						<dt>Nome:</dt>
							<dd$tooltip>$posto_nome</dd><br>
						<dt>Raz„o Social:</dt>
							<dd>$nome</dd><br>
						<dt valign='top'>EndereÁo:<span style='text-decoration:none'>&nbsp;$link_mapa</span><br><br></dt>
							<dd valign='top'>$end_completo</dd><br>
						<dt>Telefone(s):</dt>
							<dd>$fone</dd><br>
						<dt>E-Mail:</dt>
							<dd style='text-transform:none'>$link_email</dd><br>
						<dt>Atende:</dt>
					    	<dd style='text-transform:none'>".implode(",",$linhas_posto)."</dd> <br />";

				        if(strlen($posto) > 0){

				        	echo "<dt>Cidades que Atende:</dt> <br />";

				        	$sql_cidades_atende = "SELECT
				                        tbl_posto_fabrica_ibge.posto_fabrica_ibge,
				                        tbl_cidade.nome AS cidade,
				                        tbl_cidade.estado,
				                        tbl_posto_fabrica_ibge.posto_fabrica_ibge_tipo,
				                        /* tbl_posto_fabrica_ibge_tipo.nome AS tipo_nome, */
				                        tbl_posto_fabrica_ibge.km,
				                        tbl_posto_fabrica_ibge.bairro
				                    FROM tbl_posto_fabrica_ibge
				                    INNER JOIN tbl_cidade ON tbl_cidade.cidade = tbl_posto_fabrica_ibge.cidade
				                    /* $inner JOIN tbl_posto_fabrica_ibge_tipo ON tbl_posto_fabrica_ibge_tipo.posto_fabrica_ibge_tipo = tbl_posto_fabrica_ibge.posto_fabrica_ibge_tipo AND tbl_posto_fabrica_ibge_tipo.fabrica = {$login_fabrica} */
				                    WHERE tbl_posto_fabrica_ibge.fabrica = {$fabrica}
				                    AND tbl_posto_fabrica_ibge.posto = {$posto}";
				            $res_cidades_atende = pg_query($con, $sql_cidades_atende);
				            $rows = pg_num_rows($res_cidades_atende);

				            if ($rows > 0) {
				                for ($j = 0; $j < $rows; $j++) {

				                    $posto_fabrica_ibge      = pg_fetch_result($res_cidades_atende, $j, "posto_fabrica_ibge");
				                    $cidade                  = pg_fetch_result($res_cidades_atende, $j, "cidade");
				                    $estado                  = pg_fetch_result($res_cidades_atende, $j, "estado");
				                    $posto_fabrica_ibge_tipo = pg_fetch_result($res_cidades_atende, $j, "posto_fabrica_ibge_tipo");
				                    $tipo_nome               = pg_fetch_result($res_cidades_atende, $j, "tipo_nome");
				                    $km                      = pg_fetch_result($res_cidades_atende, $j, "km");
				                    $bairros                 = json_decode(pg_fetch_result($res_cidades_atende, $j, "bairro"), true);

				                    echo "<dd style='margin-left: 0px; margin-bottom: 10px;'>";
				                    	echo "Cidade: ".$cidade." - ".$estado." <br /> ";
				                    	 if (count($bairros) > 0) {
				                    	 		echo " &nbsp; &nbsp; Bairro(s) ";
				                    	 		$k = 0;
			                                    foreach ($bairros as $bairro) {
			                                        if (!strlen($bairro)) {
			                                            continue;
			                                        }

			                                        $bairro = strtoupper(utf8_decode($bairro));
			                                        echo $bairro;
			                                        echo ($k++ < count($bairros) - 1) ? ", " : ""; 
			                                    }
			                                }
				                    echo "</dd> <br /> ";

				                }
				             }

				        }

  					echo "</dl>";
				unset ($end_mapa, $link_mapa, $end_completo, $posto_nome, $email, $tooltip);
			}
		}else{
			echo "\t<tr><td class='fontenormal'> Nenhuma AssistÍncia TÈcnica encontrada.</td></tr>";
		}
	exit;
}
//  FIM AJAX
$page_title = 'AssistÍncia TÈcnica :: Busca';
$sqlInsertLog = "INSERT INTO tbl_log_conexao(programa) VALUES ('$PHP_SELF')";
$resInsertLog = pg_query($con, $sqlInsertLog);

?>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
	<style type="text/css">
	#mapabr {position:relative;text-align:center;width:540px;margin-bottom:1em}
	#mapabr form img {border: 0 solid transparent;margin-right: 2em}

	form {
		font-family: Arial, Helvetica, sans-serif;
		margin: 10px 5px;
		padding: 1em 1ex;
	}
    form fieldset {
        border-top: 1px solid white;
        margin: 0 auto;
        text-align: left;
    }
	form legend {
		font-weight:bold;
		font-size: 11px;
        padding-bottom: 1.6em;
	}
	#sel_cidade, #tblres {display: none;}
	area {cursor: pointer}
	a img {border: 0 solid transparent;}
	label, select {text-align:left;}
    select {width: 155px;}
	button {margin-left: 55px;width: 100px;}

    #tblres {
        width: 540px;
        margin: 2em auto 10px auto;
        position:relative;
    }
    #tblres dl {
        font-size: 12px;
        width: 500px;
        margin: 10px 10px 1em 10px;
        text-align:left;
        border: 1px dotted grey;
        border-radius: 6px;
        -moz-border-radius: 6px;
        padding: 5px 8px;
        background: url(imagens/dl_bg_grey.png);
        background-size: 100% 101%;
        background-image: -moz-linear-gradient(top, #ddd, #c5c5c5 45%, #c5c5c5 55%, #ddd);
        background-image: -webkit-gradient(linear, 0 0, 0% 100%, from(#ddd), color-stop(40%, #c5c5c5), color-stop(60%, #c5c5c5), to(#ddd));
        filter: progid:DXImageTransform.Microsoft.Gradient(GradientType=,StartColorStr='#dddddd',EndColorStr='#c5c5c5');
    }
    dl dt {display:inline-block;_zoom:1;_float:left;*float:left;width: 6.5em;_width: 7.5em;*width: 7.5em;font-weight: bold;vertical-align: top;font-size:11px;}
    dl dt img {line-height:14px;vertical-align: top;}
    dl > dd {display:inline-block;_float:left;*float:left;color: #333;text-transform: capitalize;font-size:11px;;vertical-align: top;}
    dl > dd > dt {margin-left: 6.2em;display:inline-block}
    dl > dd > address dd {display:inline-block;color:red}
	.branco {
		font-weight: bold;
		color: white;
	}
	.azul {color:#102d65}
	.fundo_vermelho {background-color: #A10F15}
	.cinza {#666}
	.bold {
		font-weight: bold;
	}
    </style>

	<script type="text/javascript">
	$(function() {
//  Adiciona um evento onClick para cada 'area' que vai alterar o valor do SELECT 'estado'
		$('map area').click(function() {
			$('#estado').val($(this).attr('name'));
			$('#estado').change();
		});
		$('#sel_cidade').hide('fast');

//		Quando muda o valor do select 'estado' requisita as cidades onde tem postos autorizados e os
//		insere no select 'cidades'
		$('#estado').change(function() {
		    var estado = $('#estado').val();
		    if (estado == '') {
				$('#sel_cidade').fadeOut(300).find('select').html('<option></option>');
				$('#tblres').html('').fadeOut(400);
				return false;
			}
			$.get(location.pathname, {'action': 'cidades','estado': estado},
			  function(data){
				if (data.indexOf('Sem resultados') < 0) {
					$('#sel_cidade').fadeIn(500);
				    $('#cidade').html(data).val('').removeAttr('disabled');
				} else {
				    $('#cidade').html(data).val('Sem resultados').attr('disabled','disabled');
				}
				$('#tblres').html('').fadeOut(400);
			});
		});
		$('#cidade').change(function() {
			$('#tblres').fadeOut('fast');
		    var estado = $('#estado').val();
			var cidade = $('#cidade').val();
			if (estado == '' && cidade == '') {
				$('#tblres').html('<p>Selecione o Estado e a Cidade para Pesquisar!</p>');
				return true;
			}
			$.get(location.pathname, {'action': 'postos','estado': estado,'cidade': cidade},
			  function(data){
// 				alert(data);
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
					$('#mapabr fieldset').animate({height: 175});
					$('#tblres').html(data).fadeIn('normal');
				}
			  });
		});
		$('button').click(function () {
			$('#cidade').change();
			return false;
		});
	});
    </script>
<div class="TITULO">
<img src="http://beta.wanke.com.br/arquivo_imagens/titulos/10.jpg" alt="AssistÍncia TÈcnica" />
</div>
<div class="CONTEUDO">
<div class="CONTEUDO_INTERNAS_MAIOR">
	<div id='mapabr'>
		<form>
			<fieldset>
				<legend>&nbsp;Mapa da Rede&nbsp;</legend>
				<img src='imagens/mapa_vermelho.png' alt='Mapa do Brasil' title='Selecione o Estado'
					usemap='#Map2' style='float:left;' />
				<label for="estado">Selecione o Estado:</label><br />
				<select title="Selecione o Estado" name="estado" id="estado" tabindex="1">
					<option></option>
				<?
				foreach ($estados as $uf=>$nome) {
                	echo str_repeat("\t", 8)."<option value='$uf'>$nome</option>\n";
                }
				?>
                </select><br />
				<div id='sel_cidade'>
					<label for="cidade">Selecione a Cidade:</label><br />
					<select name="cidade" id="cidade" tabindex="2">
					</select>
				</div><br />
				<button type="submit" tabindex="3">Pesquisar</button>
			</fieldset>
		</form>
<!--	<div style='position: absolute;bottom:2em;right:2.5em;text-align:right'>
		Se a sua cidade n„o se encontra na relaÁ„o,<br>pode fazer a pesquisa no <a href="https://www.telecontrol.com.br/mapa_rede.php?fabrica=91" target='_blank'> <i>site</i> da <b>Telecontrol</b></a>.
	</div>
-->
	<div id='tblres'></div>
	</div>
</div>
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
<div class="RODAPE">Copyright: <a href="http://www.wanke.com.br" target="_blank">www.wanke.com.br</a> | <a href="mailto:falecom@wanke.com.br">falecom@wanke.com.br</a> | <span class="RODAPE_DET">SAC: 0800 724 2511</span></div>
<script type="text/javascript">
	_uacct = "UA-154760-26";
	urchinTracker();
</script>
