<?php
include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';
include 'mlg_funciones.php';

$fabrica = 40;

header('Content-Type: text/html; charset=UTF-8');
//	AJAX
if ($_GET['action']=='cidades') {
	$estado = anti_injection($_GET['estado']);
	if ($estado == "") exit;

	if(strlen($estado) > 0) {
		$sql_cidades =	"SELECT  LOWER(mlg_cidade)||'#('||count(mlg_cidade)||')' AS cidade
							FROM (SELECT DISTINCT posto,tipo_posto,UPPER(TRIM(TRANSLATE(contato_cidade,'áâàãäéêèëíîìïóôòõúùüçÁÂÀÃÄÉÊÈËÍÎÌÏÓÔÒÕÚÙÜÇ',
																							  'aaaaaeeeeiiiioooouuucAAAAAEEEEIIIIOOOOUUUC')))
														AS mlg_cidade,
										contato_estado	AS mlg_estado
							FROM tbl_posto_fabrica
                                   JOIN tbl_posto_linha USING (posto)

								WHERE   posto NOT IN(6359,20462)
									AND credenciamento != 'DESCREDENCIADO'
									AND tipo_posto     != 163
									AND posto         NOT IN(6359,20462)
                                    AND tbl_posto_linha.linha = 453
                                    AND tbl_posto_fabrica.divulgar_consumidor IS TRUE
									AND contato_estado='$estado' AND fabrica = $fabrica) mlg_posto
							GROUP BY mlg_posto.mlg_cidade ORDER BY cidade ASC";
		$res_cidades   = pg_query($con,utf8_decode($sql_cidades));
		$lista_cidades = pg_fetch_all($res_cidades);

		if (pg_num_rows($res_cidades)) {
			foreach ($lista_cidades as $cidade) {
				list($cidade_i,$cidade_c) = explode("#",htmlentities($cidade['cidade']));
	           $sel      = (strtoupper($cidade) == strtoupper($cidade_i))?" SELECTED":"";
				echo "<OPTION value='$cidade_i'$sel>".ucwords($cidade_i." ".$cidade_c)."</OPTION>\n";
				
			}
		} else {
			if ($tot_i==0) echo "<OPTION SELECTED>Sem resultados</OPTION>";
		}
	}
	exit;
}

if ($_GET['action']=='postos') {
	$estado = getPost("estado");
	if (isset($_GET['cidade'])) $cidade = getPost("cidade");
	if ($estado == "" or $cidade == "") exit("<p>Erro na consulta!</p>");

	$sql = "SELECT
				tbl_posto.posto,
				tbl_posto_fabrica.codigo_posto,
				TRIM(tbl_posto_fabrica.contato_endereco)	AS endereco,
				tbl_posto_fabrica.contato_numero			AS numero,
                TRIM(tbl_posto.nome)						AS nome,
				UPPER(TRIM(TRANSLATE(tbl_posto_fabrica.contato_cidade,'ÁÂÀÃÄÉÊÈËÍÎÌÏÓÔÒÕÚÙÜÇ',
																'áâàãäéêèëíîìïóôòõúùüç')))
															AS cidade,
				tbl_posto_fabrica.contato_complemento		AS complemento,
				tbl_posto_fabrica.contato_estado			AS estado,
				tbl_posto_fabrica.contato_bairro			AS bairro,
				tbl_posto_fabrica.contato_cep				AS cep,
				tbl_posto_linha.linha				AS linha,
				tbl_posto_fabrica.nome_fantasia,
                tbl_posto.latitude,
                tbl_posto.longitude,
                TRIM(tbl_posto_fabrica.contato_email)		AS email,
				tbl_posto_fabrica.contato_fone_comercial	AS fone,
				tbl_posto_fabrica.contato_fone_residencial	AS fone2
			FROM   tbl_posto
			JOIN    tbl_posto_fabrica USING (posto)
			JOIN    tbl_posto_linha USING (posto)
			WHERE   tbl_posto_fabrica.fabrica = $fabrica
			AND tbl_posto_fabrica.contato_estado = '$estado'
			AND tbl_posto.posto NOT IN(6359,20462)
			AND tbl_posto_fabrica.credenciamento <> 'DESCREDENCIADO'
			AND tbl_posto_fabrica.tipo_posto <> 163
			AND tbl_posto_linha.linha = 453
			AND tbl_posto_fabrica.divulgar_consumidor IS TRUE
			AND UPPER(TRIM(TRANSLATE(contato_cidade,'áâàãäéêèëíîìïóôòõúùüçÁÂÀÃÄÉÊÈËÍÎÌÏÓÔÒÕÚÙÜÇ',
													'aaaaaeeeeiiiioooouuucAAAAAEEEEIIIIOOOOUUUC')))
						~* '$cidade'
			ORDER BY tbl_posto_fabrica.contato_bairro, tbl_posto.nome";
		$res = pg_query($con,utf8_decode($sql));
		$total_postos = ($tem_mapa=pg_num_rows($res));
		$cidade = ($total_postos) ? pg_fetch_result($res, $total_postos-1, cidade) : '';
		
		echo "<table cellpadding='0' id='postos'>\n";
		echo "<caption>Rela&ccedil;&atilde;o de Postos ";
		echo ($cidade<>"")?"da cidade de <span class='nome_cidade'>".ucwords(mb_strtolower(utf8_encode($cidade)))."</span> ":"";
		echo ($estado=='DF')?"no Distrito Federal":"no estado de " . utf8_encode($estados[$estado]);
		echo "</caption>";

		if($total_postos > 0){?>
        <thead>
            <tr align='center' class='bold'>
                <th style='width:200px' width='210'>Nome do Posto</th>
                <th style='width:230px' width='262'>Bairro - Endere&ccedil;o</th>
                <th style='width:115px' width='82'>Telefone</th>
				<th style='width:115px' width='82'>Telefone 2</th>
                <th style='width: 40px' width= '38'>E-Mail</th>
                <th style='width: 40px' width= '38'>Mapa</th>
            </tr>
        </thead>
<?
			for ($i = 0 ; $i < $total_postos ; $i++) {
                $row = pg_fetch_array($res, $i);
                foreach ($row as $campo => $valor) {
                    $$campo = utf8_encode(trim($valor));
                }
				$end_completo = $bairro . " - " . $endereco . ", " . $numero . " - " . $complemento;
				$end_mapa     = "$endereco, $numero, $cep, $cidade, $estado, Brasil";
// 				if (is_numeric($longitude) and is_numeric($latitude)) { // lat e long estão ao contrário no banco
				$link_mapa = "<a title='Localizar no mapa' href='https://maps.google.com/maps?f=q&source=s_q&hl=pt-BR&q=$end_mapa&ie=windows-1251' target='_blank'>".
							 "<img src='https://www.google.com/options/icons/maps.gif' width='16'></a>";
// 				}

				echo "\t\t<tr>";
				$posto_nome = iif((strlen($nome_fantasia)>0),$nome_fantasia,$nome);
				$tooltip .= " title='".iif(($posto_nome==$nome_fantasia),"$posto_nome ($nome)",
										iif((strlen($posto_nome)>=30),"$posto_nome",""))."'";
				echo "\t\t\t<td class='ell'$tooltip>$posto_nome</td>";
				unset($tooltip);
				$tooltip = (strlen($end_completo)>=35)?" title='$end_completo'":"";
				echo "\t\t\t<td class='ell'$tooltip>";
				echo $end_completo;
				echo "</td>";
				echo "\t\t\t<td>$fone</td>";
				echo "\t\t\t<td>$fone2</td>";
                if (is_email($email)) {
					echo "\t\t\t<td title='$email'>";
                	echo "<a href='mailto:".strtolower($email)."'><img src='email_envelope.jpg'></a>";
                } else {
					echo "\t\t\t<td><img src='no_e-mail.jpg'></td>";
				}
				echo "\t\t\t<td>$link_mapa</td>";
				echo "\t\t</tr>";
				unset ($end_mapa, $link_mapa, $end_completo, $posto_nome, $email, $tooltip);
			}
		}else{
			echo "\t<tr><td class='fontenormal'> Nenhuma Assistência Técnica encontrada.</td></tr>";
		}
		echo "</table>\n<br>";
	exit;
}

if ($_GET['action']=='xls') {
	$hoje = date('Ymd');
	$arquivo = '../xls/maparedemasterfrio' . $hoje . '.xls';
	
	ob_start();

	$sql = "SELECT
				tbl_posto.posto,
				tbl_posto_fabrica.codigo_posto,
				TRIM(tbl_posto_fabrica.contato_endereco)	AS endereco,
				tbl_posto_fabrica.contato_numero			AS numero,
                TRIM(tbl_posto.nome)						AS nome,
				UPPER(TRIM(TRANSLATE(tbl_posto_fabrica.contato_cidade,'ÁÂÀÃÄÉÊÈËÍÎÌÏÓÔÒÕÚÙÜÇ',
																'áâàãäéêèëíîìïóôòõúùüç')))
															AS cidade,
				tbl_posto_fabrica.contato_estado			AS estado,
				tbl_posto_fabrica.contato_bairro			AS bairro,
				tbl_posto_fabrica.contato_cep				AS cep,
				tbl_posto_fabrica.nome_fantasia,
                tbl_posto.latitude,
                tbl_posto.longitude,
                TRIM(tbl_posto_fabrica.contato_email)		AS email,
				tbl_posto_fabrica.contato_fone_comercial	AS fone
			FROM   tbl_posto
			JOIN    tbl_posto_fabrica USING (posto)
			WHERE   tbl_posto_fabrica.fabrica = $fabrica
			AND tbl_posto.posto NOT IN(6359,20462)
			AND tbl_posto_fabrica.credenciamento <> 'DESCREDENCIADO'
			AND tbl_posto_fabrica.tipo_posto <> 163
			AND tbl_posto_fabrica.divulgar_consumidor IS TRUE
			ORDER BY tbl_posto_fabrica.contato_estado, tbl_posto_fabrica.contato_cidade, tbl_posto_fabrica.contato_bairro, tbl_posto.nome";
		$res = pg_query ($con,$sql);
		$total_postos = pg_num_rows($res);
		
		if($total_postos > 0){?>
        <table>
            <tr align='center' class='bold' style='background-color: #444444; color: #FFFFFF'>
                <th style='width:250px' width='250'>Nome do Posto</th>
                <th style='width:250px' width='250'>Endere&ccedil;o</th>
                <th style='width: 80px' width= '80'>CEP</th>
                <th style='width: 150px' width= '150'>Cidade</th>
                <th style='width: 70px' width= '70'>Estado</th>
                <th style='width: 100px' width= '100'>Telefone</th>
            </tr>
<?
			for ($i = 0 ; $i < $total_postos ; $i++) {
                $row = pg_fetch_array($res, $i);
                foreach ($row as $campo => $valor) {
                    $$campo = trim($valor);
                }
				$end_completo = $endereco . ", " . $numero . " - " . $bairro;
				$posto_nome = iif((strlen($nome_fantasia)>0),$nome_fantasia,$nome);
				$cor = $i % 2 ? '#EEEEEE' : '#CCCCCC';

				echo "<tr style='background-color: $cor;'>";
				echo "<td>$posto_nome</td>";
				echo "<td>$end_completo</td>";
				echo "<td>$cep</td>";
				echo "<td>$cidade</td>";
				echo "<td>$estado</td>";
				echo "<td>$fone</td>";
				echo "</tr>";
				unset ($posto_nome, $end_completo, $cep, $cidade, $estado, $fone);
			}
		}

		//MLG 20-10-2011 - Dispensei a geração do arquivo 'XLS'
		$xls = ob_get_clean();
		header('Content-type: application/msexcel');
		header("Content-Disposition: attachment; filename=dados_atualizados_postos_$hoje.xls");

		echo $xls;
	exit;
}
//  FIM AJAX
$sqlInsertLog = "INSERT INTO tbl_log_conexao(programa) VALUES ('$PHP_SELF')";
$resInsertLog = pg_query($con, $sqlInsertLog);

$base_url = 'https://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['PHP_SELF']).'/';
$html_titulo = "Masterfrio - Mapa da Rede Autorizada";
include "cabecalho.php";
?>
	<base href="<?php echo $base_url;?>imagens/">
	<link rel="stylesheet" href="../css/mf.css" type="text/css">
	<!-- JavaScript -->
	<script type="text/javascript" src="../../../js/jquery-1.6.1.min.js"></script>
	<!--[if IE]>
	<script type="text/javascript">
	var curvyCornersNoAutoScan = true;
    </script>
	<script type="text/javascript" src="../jquery.curvycorners.js"></script>
	<![endif]-->

	<script type="text/javascript">
		var doc_self = location.pathname;
		$(document).ready(function() {
			$('map#MapaEstados>area').click(function() {
				$('#estado').val($(this).attr('name'));
				$('#estado').change();
			});
			$('#estado').change(function () {
				var estado = $(this).val();
				var nome_estado = $(this).find('option:selected').text();
				if (estado.length == 2) {
					if (estado != $('#nome_estado').text()) {
						$('#ul_cidades select').html('<option>Carregando...</option>');
						$('#tbl_postos').html('');
					}
					$('#mapabr').fadeOut('slow').parent().find('#postos_estado').hide(10).delay(500).fadeIn('slow');
					$('#nome_estado').text(nome_estado);
					if ($.support.opacity != true) {
					}
					$('#ul_cidades select').load(doc_self+'?action=cidades&estado='+estado, function() {
						$('#cidade').change(function () {
							var cidade = $(this).val();
							$('#tbl_postos table').slideUp('fast');
							$('#tbl_postos').html('<i>Carregando informações...</i>');
							$.get(doc_self, 'action=postos&estado='+estado+'&cidade='+cidade, function(data){
								$('#tbl_postos').hide().html(data).fadeIn('normal');
							});
						});
                    });
				}
			});
			$('#voltar,#menu_sup_mapa').click(function () {
				window.location.href = doc_self;
			});
		}); // FIM do jQuery
	</script>
</head>

<body>
<!-- Mapas de imagens -->
	<map id="menu_map" name="menu_map">
		<area shape="rect" href="https//www.masterfrio.com.br/" alt="Voltar para o site da Masterfrio" title="Voltar para o site da Masterfrio" coords="0,0,539,26">
		<area shape="rect" href="https//www.masterfrio.com.br/" alt="Voltar para o site da Masterfrio" title="Voltar para o site da Masterfrio" coords="764,0,994,26">
		<area shape="rect" style='cursor:pointer' alt="Voltar para mapa" id='menu_sup_mapa' title="Voltar para a seleção de estado e cidade" coords="542,0,760,26">
	</map>
	<map id="MapaEstados" name="MapaEstados">
		<area shape="poly" name='RS' title="Rio Grande do Sul"	 coords="204,397,211,390,213,383,228,369,238,354,233,351,236,345,230,345,221,337,200,331,180,345,168,358,179,369,185,368,197,378,207,387,204,395,">
		<area shape="poly" name='SC' title="Santa Catarina"		 coords="199,328,213,330,227,335,233,343,240,343,239,351,246,344,251,335,251,320,242,320,232,321,223,326,202,322,">
		<area shape="poly" name='PR' title="Paraná"				 coords="192,314,200,312,203,322,222,324,231,318,242,321,250,318,256,312,249,306,243,306,238,292,222,287,206,287,195,301,">
		<area shape="poly" name='SP' title="São Paulo"			 coords="213,282,235,285,242,291,246,304,254,303,257,311,274,296,293,290,289,284,274,288,269,284,269,273,266,273,263,261,256,259,250,262,240,259,230,258,223,269,212,282,">
		<area shape="poly" name='RJ' title="Rio de Janeiro"		 coords="296,288,295,288,297,287,297,284,307,287,320,291,324,297,335,296,334,287,324,288,315,283,327,278,323,273,317,269,311,277,302,280,292,283,">
		<area shape="poly" name='ES' title="Espírito Santo"		 coords="327,267,333,259,340,269,357,269,355,255,340,256,336,254,338,242,333,237,328,241,328,254,321,261,323,268,">
		<area shape="poly" name='BA' title="Bahia"				 coords="339,238,343,234,344,215,346,194,357,176,351,163,356,157,352,148,343,142,329,152,324,145,308,154,302,150,295,160,288,168,280,162,272,170,277,201,293,197,301,203,315,206,325,210,338,217,333,231,">
		<area shape="poly" name='SE' title="Sergipe"			 coords="360,171,364,166,367,171,366,180,382,179,381,168,367,164,371,160,358,153,360,161,354,163,">
		<area shape="poly" name='AL' title="Alagoas"			 coords="358,148,373,158,377,154,383,162,394,161,394,151,383,150,381,147,376,147,366,150,359,145,">
		<area shape="poly" name='PE' title="Pernambuco"			 coords="327,141,331,147,340,139,354,146,358,143,367,148,384,144,385,137,395,142,410,142,407,130,393,130,382,130,367,139,361,136,364,128,354,133,344,134,332,129,">
		<area shape="poly" name='PB' title="Paraíba"			 coords="350,132,362,125,368,126,367,135,384,129,389,122,408,124,407,112,390,112,383,120,373,119,369,123,358,120,351,120,">
		<area shape="poly" name='RN' title="Rio Grande do Norte" coords="351,117,363,113,363,122,369,122,371,115,384,119,381,107,390,101,390,89,374,89,374,101,373,106,361,104,">
		<area shape="poly" name='CE' title="Ceará"				 coords="331,126,340,126,346,133,348,122,348,114,359,101,343,88,332,83,324,84,327,103,330,119,">
		<area shape="poly" name='PI' title="Piauí"				 coords="285,162,293,160,297,153,301,146,311,149,329,134,331,123,322,85,318,85,308,95,309,107,307,112,308,124,297,123,287,131,281,132,279,144,281,154,">
		<area shape="poly" name='MA' title="Maranhão"			 coords="276,65,269,90,255,104,263,110,263,127,266,134,272,134,270,144,277,157,277,144,279,129,294,122,306,120,305,108,306,98,311,87,316,81,308,82,301,78,294,80,286,71,">
		<area shape="poly" name='PA' title="Pará"				 coords="274,66,261,59,250,61,234,48,220,64,214,67,205,45,189,36,185,29,179,29,176,37,168,33,151,42,149,56,178,76,156,121,168,147,231,152,243,138,242,128,245,120,254,113,251,105,260,98,271,82,">
		<area shape="poly" name='AP' title="Amapá"				 coords="216,62,225,50,236,41,235,36,228,31,221,9,209,31,192,30,203,39,">
		<area shape="poly" name='RR' title="Roraima"			 coords="145,39,138,30,137,20,139,12,134,3,124,6,121,12,113,12,109,15,95,13,100,25,108,31,116,42,115,52,113,60,119,62,124,56,132,59,136,50,145,48,">
		<area shape="poly" name='AM' title="Amazonas"			 coords="169,78,151,65,146,53,137,53,134,64,126,60,120,70,110,62,115,56,111,43,108,35,100,35,78,48,67,44,64,33,54,37,41,36,49,43,49,48,39,52,47,66,37,97,20,102,11,114,3,123,34,134,69,150,89,145,100,132,111,132,122,141,150,141,154,129,150,120,">
		<area shape="poly" name='AC' title="Acre"				 coords="2,128,11,143,10,148,16,149,26,153,31,147,36,163,52,166,65,152,33,137,">
		<area shape="poly" name='RO' title="Rondônia"			 coords="74,154,88,154,87,168,94,177,110,180,119,189,132,190,132,189,138,177,135,167,120,164,120,144,109,138,101,136,96,147,89,145,">
		<area shape="poly" name='MT' title="Mato Grosso"		 coords="123,144,124,162,139,166,138,174,140,181,134,192,139,218,155,218,155,226,163,232,173,227,186,230,197,228,204,236,207,221,214,213,222,203,229,194,230,182,230,164,231,153,170,149,155,134,154,143,">
		<area shape="poly" name='TO' title="Tocantins"			 coords="254,108,249,119,244,128,245,138,236,150,231,180,240,184,246,180,259,186,272,182,269,170,271,163,276,159,264,148,265,139,267,135,258,129,261,114,">
		<area shape="poly" name='MS' title="Mato Grosso do Sul"	 coords="162,236,158,251,157,259,158,277,169,279,171,276,180,281,184,287,184,295,193,295,199,285,213,277,221,260,225,257,225,250,205,241,197,238,198,231,190,233,174,230,">
		<area shape="poly" name='MG' title="Minas Gerais"		 coords="229,254,239,254,246,259,255,257,263,256,266,269,272,271,272,282,278,285,293,280,310,276,314,264,319,259,326,249,323,241,331,236,327,228,335,218,323,214,316,209,305,204,299,205,294,200,277,208,273,205,267,209,269,218,264,219,264,225,261,231,262,241,247,240,235,244,228,254,">
		<area shape="poly" name='GO' title="Goiâs"			 coords="226,249,233,241,246,239,258,240,264,235,260,232,263,224,263,218,246,218,245,205,263,206,267,214,269,206,275,204,275,199,272,187,257,190,245,185,242,190,233,183,228,201,222,209,215,217,207,229,209,239,">
		<area shape="poly" name='DF' title="Distrito Federal"	 coords="247,207,262,208,262,216,247,216,">
		<area shape="rect" coords="412,398,414,400" href="https//www.image-maps.com/index.php?aff=mapped_users_4201006150903168" title="Image Map">
	</map>
	<center>
		<!-- <img src="mf_menu_sup.png" alt="menu_superior" id="menu_sup" usemap='#menu_map' style='cursor:pointer' /> >
		<input type="button" class="btn btn-info" value="Voltar" onclick="window.location='https//www.acquamaxrj.com.br'" style='float:left;cursor:pointer;margin-top: 1em;margin-left:1em;'>
		<br> -->
		<img src="mf_logo.png" alt="Masterfrio" title="Masterfrio" style='margin-top: 1em;margin-right:780px'>
		<div id="mapabr">
			<img id="mf_mapa_rede" src="mf_mapa.png" usemap="#MapaEstados" style="float:left;width:414px;height:400px;" />
			<fieldset style='float:right;border:none'>
				<label for="estado" title="Selecione o estado">Selecione seu estado:</label>
				<select name="estado" id="estado">
					<option value="">---</option>
					<option value="AC">Acre</option>
					<option value="AL">Alagoas</option>
					<option value="AP">Amapá</option>
					<option value="AM">Amazonas</option>
					<option value="BA">Bahia</option>
					<option value="CE">Ceará</option>
					<option value="DF">Distrito Federal</option>
					<option value="ES">Espírito Santo</option>
					<option value="GO">Goiâs</option>
					<option value="MA">Maranhão</option>
					<option value="MG">Minas Gerais</option>
					<option value="MS">Mato Grosso do Sul</option>
					<option value="MT">Mato Grosso</option>
					<option value="PA">Pará</option>
					<option value="PB">Paraíba</option>
					<option value="PR">Paraná</option>
					<option value="PE">Pernambuco</option>
					<option value="PI">Piauí</option>
					<option value="RJ">Rio de Janeiro</option>
					<option value="RN">Rio Grande do Norte</option>
					<option value="RS">Rio Grande do Sul</option>
					<option value="RO">Rondônia</option>
					<option value="RR">Roraima</option>
					<option value="SC">Santa Catarina</option>
					<option value="SE">Sergipe</option>
					<option value="SP">São Paulo</option>
					<option value="TO">Tocantins</option>
				</select><br>
				OU <a href="<?=$PHP_SELF?>?action=xls">clique aqui para fazer o download de todos postos da rede autorizada</a>
			</fieldset>
		</div>
		<div id="postos_estado">
			<div id="voltar" title='Voltar ao mapa'>&nbsp;</div>
			<h2 id="nome_estado"></h2>
			<div id="ul_cidades">
				<h4>Selecione a cidade</h4>
				<select name="cidade" id="cidade" size='10'></select>
			</div>
			<div id="tbl_postos">Selecione uma cidade...</div>
		</div>
	</center>
	<div id='gmapsd'></div>
</body>
</html>
