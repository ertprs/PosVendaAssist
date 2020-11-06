<?php

header('Content-type: text/html; charset=iso-8859-1');

if (empty($_GET['token'])) {
    exit('Acesso negado');
}

include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';
include 'mlg_funciones.php';

$fabrica = 86;
$token = sha1('#' . $fabrica . '?famastil*');

if ($_GET['token'] !== $token) {
    die('Token inv·lida');
}

//	AJAX
if ($_GET['action']=='cidades') {
    if (!empty($_GET['regiao'])) {
        $regiao = $_GET['regiao'];

        if (empty($regiao)) {
            exit("<OPTION SELECTED>Sem resultados</OPTION>");
        }

        $estado = array(
                'N'  => "'AC', 'AM', 'RR', 'RO', 'AP', 'PA', 'TO'",
                'NE' => "'MA', 'PI', 'CE', 'RN', 'PB', 'PE', 'AL', 'SE', 'BA'",
                'CO' => "'DF', 'GO', 'MT', 'MS'",
                'SE' => "'ES', 'MG', 'RJ', 'SP'",
                'S'  => "'PR', 'RS', 'SC'",
            );
    }

    if (!empty($_GET['estado'])) {
        $estado = $_GET['estado'];
    }
	
	if ($estado == "") exit("<OPTION SELECTED>Sem resultados</OPTION>");

	if (!empty($estado)) {
        if (is_array($estado)) {
            $condEstado = ' IN (' . $estado[$regiao] . ')';
        } else {
            $condEstado = " = '$estado' ";
        }

        $condCredenciado = '';

        if (!empty($_GET['credenciamento'])) {
            $credenciamento = strtoupper($credenciamento);
            switch ($credenciamento) {
                case 'CREDENCIADO':
                case 'EM DESCREDENCIAMENTO':
                case 'DESCREDENCIADO':
                    $condCredenciado = " AND tbl_posto_fabrica.credenciamento = '$credenciamento' ";
                    break;
                    // AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'            
                default:
                    $condCredenciado = '';
                    break;
            }
        }

		$tot_i = false;
		$sql_cidades =	"SELECT  LOWER(mlg_cidade)||'#('||count(mlg_cidade)||')' AS cidade
							FROM (SELECT tbl_posto_fabrica.posto,
                                          tipo_posto,
                                          UPPER(TRIM(TRANSLATE(contato_cidade,'·‚‡„‰ÈÍËÎÌÓÏÔÛÙÚı˙˘¸Á¡¬¿√ƒ… »ÀÕŒÃœ”‘“’⁄Ÿ‹«','aaaaaeeeeiiiioooouuucAAAAAEEEEIIIIOOOOUUUC'))) AS mlg_cidade,
										  contato_estado AS mlg_estado
							FROM tbl_posto_fabrica
							WHERE tbl_posto_fabrica.posto NOT IN(6359)
                            $condCredenciado
							AND contato_estado $condEstado AND fabrica=$fabrica) mlg_posto
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
    			echo "\t\t\t<OPTION value='$cidade_i'$sel>".ucwords($cidade_i)."</OPTION>\n";
            }
        } else {
    		if ($debug) pre_echo($sql_cidades, "Resultado: $tot_i registro(s)");
            exit('KO|Erro ao acessar o Sistema Telecontrol.');
        }
	}
	exit;
}

if ($_GET['action']=='postos') {
    if (!empty($_GET['regiao'])) {
        $regiao = $_GET['regiao'];

        if (empty($regiao)) {
            exit("<OPTION SELECTED>Sem resultados</OPTION>");
        }

        $estado = array(
                'N'  => "'AC', 'AM', 'RR', 'RO', 'AP', 'PA', 'TO'",
                'NE' => "'MA', 'PI', 'CE', 'RN', 'PB', 'PE', 'AL', 'SE', 'BA'",
                'CO' => "'DF', 'GO', 'MT', 'MS'",
                'SE' => "'ES', 'MG', 'RJ', 'SP'",
                'S'  => "'PR', 'RS', 'SC'",
            );
    }

	if (!empty($_GET['estado'])) {
        $estado = $_GET['estado'];
    }

	if (isset($_GET['cidade'])) $cidade=strtoupper(utf8_decode($_GET['cidade']));

	if (($estado == "" or $regiao == "") and $cidade=="") exit("Selecione o Estado e a Cidade para Pesquisar!");

    if (is_array($estado)) {
        $condEstado = ' IN (' . $estado[$regiao] . ')';
    } else {
        $condEstado = " = '$estado' ";
    }

    $condCredenciado = '';

    if (!empty($_GET['credenciamento'])) {
        $credenciamento = strtoupper($credenciamento);
        switch ($credenciamento) {
            case 'CREDENCIADO':
            case 'EM DESCREDENCIAMENTO':
            case 'DESCREDENCIADO':
                $condCredenciado = " AND tbl_posto_fabrica.credenciamento = '$credenciamento' ";
                break;
                // AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'            
            default:
                $condCredenciado = '';
                break;
        }
    }

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
			  AND tbl_posto_fabrica.contato_estado $condEstado
			  AND UPPER(TRIM(TRANSLATE(contato_cidade,'·‚‡„‰ÈÍËÎÌÓÏÔÛÙÚı˙˘¸Á¡¬¿√ƒ… »ÀÕŒÃœ”‘“’⁄Ÿ‹«',
													  'aaaaaeeeeiiiioooouuucAAAAAEEEEIIIIOOOOUUUC')))
						= '".tira_acentos($cidade)."'
			AND tbl_posto.posto not in(6359)
            $condCredenciado			
			AND tbl_posto_fabrica.divulgar_consumidor IS TRUE
			ORDER BY tbl_posto_fabrica.contato_bairro, tbl_posto.nome";
			//echo nl2br($sql);
		$res = pg_query($con,$sql);
		$total_postos = ($tem_mapa=pg_num_rows($res));
		$cidade = pg_fetch_result($res, $total_postos-1, cidade);
        if ($debug) exit($sql);

		if($total_postos > 0){
    		echo "<h5 style='color: #FFFFFF;'>Rela&ccedil;&atilde;o de Postos ";
    		echo ($cidade<>"")?"da cidade de <span class='nome_cidade'>".ucwords($cidade)."</span> ":"";
    		echo ($estado=='DF')?"no Distrito Federal":"no estado de {$estados[$estado]}";
    		echo "</h5>";

			for ($i = 0 ; $i < $total_postos ; $i++) {
                $row = pg_fetch_array($res, $i);
                foreach ($row as $campo => $valor) {
                    $$campo = trim($valor);
                }
//                 p_echo("preg_replace(\"/[\.|\,|\,$numero|\,".addcslashes($numero{$complemento},'\'".,/\\$[]-+*?')."|$complemento]$/\", '', $endereco))");
                $endereco = preg_replace("/[\.|\,|\,$numero|\,".addcslashes($numero{$complemento},'\'".,/\\$[]-+*?')."|".addcslashes($complemento,'\'".,/\\$[]')."]$/", '', $endereco);
				
                $end_completo = "EndereÁo: $endereco, $numero $complemento &nbsp; ".
                                "$bairro<br/> CEP $cep &nbsp; ".
                                mb_convert_case($cidade, MB_CASE_TITLE)." - $estado\n";
				
                $end_mapa     = "$endereco, $numero, $cep, $cidade, $estado, Brasil";				
				
                // $posto_nome = iif((strlen($fantasia)>0),$fantasia,$nome_fantasia);

                if (!empty($nome_fantasia)) {
                    $posto_nome = $nome_fantasia;
                } else {
                    $posto_nome = $nome;
                }

				$tooltip .= " title='".iif(($posto_nome==$nome_fantasia),"$posto_nome ($nome)",
										iif((strlen($posto_nome)>=50),"$posto_nome"),'')."'";

    //             if (strlen($email)>5 and is_email($email)) {
    //             	$link_email = "<a href='mailto:".mb_strtolower($email)."'>$email</a>";
    //             } else {
				// 	$link_email = "<img src='/mlg/imagens/cross.png'>";
				// }

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

                $p = $row['posto'];
                $t = md5('#-*-@' . $fabrica . '@-*-!');

                if (empty($posto_nome)) {
                    $posto_nome = '&nbsp;';
                }

                if (empty($fone)) {
                    $fone = '&nbsp;';
                }

                if (!empty($linhas_posto)) {
                    // $linhas_posto = implode(', ',$linhas_posto);

                    $lp = '<ul style="list-style-type: disc;">';
                    foreach ($linhas_posto as $l) {
                        $lp.= '<li>' . $l . '</li>';
                    }
                    $lp.= '</ul>';

                    $linhas_posto = $lp;

                } else {
                    $linhas_posto = '&nbsp;';
                }

                echo "
<dl id='posto_$posto'>
	<dt style='width: 500px; font-size: 13px;' $tooltip><strong>$posto_nome</strong></dt><br>
	<dt style='width: 500px; font-weight: normal;'>$end_completo</dt><br>
    <dt style='width: 500px; font-weight: normal; margin-bottom: 10px;'>Telefone: $fone</dt>
	<dt style='width: 500px; font-size: 13px; text-transform:none; margin-bottom: 10px; float: left;'>
        <div class='linhas'>
            Linhas: &nbsp;&nbsp; $linhas_posto
        </div>
        <div class='historico_pa'>
            <input type='button' style='cursor: pointer; width: 190px;' value='HistÛrico dos Status do Posto' onClick=\"historicoCredenciamento('$p', '$t')\" />
        </div>
    </dt>
    <dd>
        <div style='width: 500px; clear: both;'></div>
    </dd>";
  
  echo "<br></dl>";

   echo '<div id="' . $p . '"></div>';

				unset ($end_mapa, $link_mapa, $end_completo, $posto_nome, $email, $tooltip);
			}
		}else{
			echo "\t<div style='color: #FFFFFF;'> Nenhuma AssistÍncia TÈcnica encontrada.</div>";
		}
	exit;
}
//  FIM AJAX
$page_title = 'AssistÍncia TÈcnica :: Busca';
?>


<html>
<head>
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
    <style type="text/css">
    #mapabr {position:relative;text-align:center;width:540px;margin-bottom:1em;}
    #mapabr form img {border: 0 solid transparent;margin-right: 2em}

    form {
        font-family: Arial, Helvetica, sans-serif;
        margin: 10px 5px;
        padding: 1em 1ex;
    }
    form fieldset {
        border-radius: 5px;
        -moz-border-radius: 5px;
        -webkit-border-radius: 5px;
        height: 365px;
        width: 500px;
    }
    form legend {
        font-weight: bold;
        font-size: 11px;
        padding-bottom: 0.8em;
    }
    #tblres {display: none;}
    area {cursor: pointer}
    a img {border: 0 solid transparent;}
    label, select {text-align:left;}
    select {width: 155px;}
    button {margin-left: 55px;width: 100px;}

    #tblres {
        width: 540px;
        margin: 2em auto 10px auto;
        position:relative;
        font-family: Arial;
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
        background-size: 100% 101%;
        background: #d6d7d6; /* Old browsers */
        background: -moz-linear-gradient(top,  #d6d7d6 0%, #dcdcdd 15%, #fbfafb 69%, #ffffff 83%, #ffffff 100%); /* FF3.6+ */
        background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#d6d7d6), color-stop(15%,#dcdcdd), color-stop(69%,#fbfafb), color-stop(83%,#ffffff), color-stop(100%,#ffffff)); /* Chrome,Safari4+ */
        background: -webkit-linear-gradient(top,  #d6d7d6 0%,#dcdcdd 15%,#fbfafb 69%,#ffffff 83%,#ffffff 100%); /* Chrome10+,Safari5.1+ */
        background: -o-linear-gradient(top,  #d6d7d6 0%,#dcdcdd 15%,#fbfafb 69%,#ffffff 83%,#ffffff 100%); /* Opera 11.10+ */
        background: -ms-linear-gradient(top,  #d6d7d6 0%,#dcdcdd 15%,#fbfafb 69%,#ffffff 83%,#ffffff 100%); /* IE10+ */
        background: linear-gradient(to bottom,  #d6d7d6 0%,#dcdcdd 15%,#fbfafb 69%,#ffffff 83%,#ffffff 100%); /* W3C */
        filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#d6d7d6', endColorstr='#ffffff',GradientType=0 ); /* IE6-9 */
    }
    dl dt { 
        display: inline-block;
        float:left;
        width: 40px;
        font-weight: bold;
        font-size:12px;
    }
    dl dt img {line-height:14px;vertical-align: top;}
    dl > dd {display:inline-block;_float:left;*float:left;color: #333;text-transform: capitalize;font-size:12px;vertical-align: top;}
    dl > dd > dt {display:inline-block}
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

    table.bordasimples { font-family: Arial; font-size: 13px; border-collapse: collapse; width: 520px; }
    table.bordasimples tr td { border:1px solid #111111; }

    .historico_pa { display: block; text-align: right; margin-top: 20px; width: 270px; float: left; }
    .linhas { width: 220px; float: left; }

    </style>

    <script type="text/javascript">

    function verificaRegiao (estado) {
        var regioes = {
                N:  ['AC', 'AM', 'RR', 'RO', 'AP', 'PA', 'TO'],
                NE: ['MA', 'PI', 'CE', 'RN', 'PB', 'PE', 'AL', 'SE', 'BA'],
                CO: ['DF', 'GO', 'MT', 'MS'],
                SE: ['ES', 'MG', 'RJ', 'SP'],
                S:  ['PR', 'RS', 'SC']
            };

        for (var regiao in regioes) {
            if ($.inArray(estado, regioes[regiao]) > -1) {
                return regiao;
            }
        }

        return false;
    }

    $(function() {
//  Adiciona um evento onClick para cada 'area' que vai alterar o valor do SELECT 'estado'
        $('map area').click(function() {
            // $('#sel_estado').show('fast');

            $('#estado').val($(this).attr('name'));

            var estado = $('#estado').val();
            var regiao = verificaRegiao(estado);

            if (regiao) {
                $('#regiao').val(regiao);
            };

            $('input:radio[name=credenciamento][value=Todos]').attr('checked', 'checked');
            $('#estado').change();
        });
        // $('#sel_cidade').hide('fast');

//      Quando muda o valor do select 'estado' requisita as cidades onde tem postos autorizados e os
//      insere no select 'cidades'
        $('#estado').change(function() {
            var estado = $('#estado').val();
            if (estado == '') {
                // $('#sel_regiao').show('fast');
                $('#sel_regiao option[value=""]').attr({ selected : "selected" });
                $('#sel_cidade').find('select').html('<option></option>');
                $('#tblres').html('').fadeOut(400);
                return false;
            }

            var regiao = verificaRegiao(estado);

            if (regiao) {
                $('#regiao').val(regiao);
            } else {
                $('#sel_regiao option[value=""]').attr({ selected : "selected" });
            }

            var credenciamento = $('input:radio[name=credenciamento]:checked').val();

            $.get(location.pathname, {
                'action': 'cidades',
                'estado': estado, 
                'credenciamento': credenciamento,
                'token': '<?php echo $token ?>'
            },
              function(data){
                if (data.indexOf('Sem resultados') < 0) {
                    $('#sel_cidade').fadeIn(500);
                    $('#cidade').html(data).val('').removeAttr('disabled');
                } else {
                    $('#cidade').html(data).val('Sem resultados').attr('disabled','disabled');
                }
                $('#tblres').html('').fadeOut(400);
            });
            // $('#sel_regiao').hide('fast');
        });

        $('#regiao').change(function() {
            var regiao = $('#regiao').val();

            if (regiao == '') {
                // $('#sel_estado').show('fast');
                $('#sel_cidade').find('select').html('<option></option>');
                $('#tblres').html('').fadeOut(400);
                return false;
            }

            $('#sel_estado option[value=""]').attr({ selected : "selected" });

            var credenciamento = $('input:radio[name=credenciamento]:checked').val();

            $.get(location.pathname, {
                'action': 'cidades',
                'regiao': regiao, 
                'credenciamento': credenciamento,
                'token': '<?php echo $token ?>'
            },
              function(data){
                if (data.indexOf('Sem resultados') < 0) {
                    $('#sel_cidade').fadeIn(500);
                    $('#cidade').html(data).val('').removeAttr('disabled');
                } else {
                    $('#cidade').html(data).val('Sem resultados').attr('disabled','disabled');
                }
                $('#tblres').html('').fadeOut(400);
            });

            // $('#sel_estado').hide('fast');
        });

        $('#cidade').change(function() {
            $('#tblres').fadeOut('fast');
            var estado = $('#estado').val();
            var cidade = $('#cidade').val();
            var regiao = $('#regiao').val();
            var credenciamento = $('input:radio[name=credenciamento]:checked').val();

            if ((regiao == '' || estado == '') && cidade == '') {
                $('#tblres').html('<p>Selecione o Estado e a Cidade para Pesquisar!</p>');
                return true;
            }

            $.get(location.pathname, {
                'action': 'postos',
                'estado': estado,
                'cidade': cidade, 
                'regiao': regiao, 
                'credenciamento': credenciamento,
                'token': '<?php echo $token ?>'
            },
              function(data){
//              alert(data);
                // if (data.indexOf('Nenhuma') < 0) {
                //     if ($('#mapabr fieldset > img').width() > 250) {
                //         $('#mapabr fieldset > img').animate({
                //             width: 150,
                //             marginRight: '+=125'
                //             }, function() {
                //             $(this).bind('mouseover', function() {
                //                 $(this).animate({width: 276,marginRight: '-=125'});
                //                 $('#mapabr fieldset').animate({height: 300});
                //                 $(this).unbind('mouseover');
                //             });
                //         });
                //     }
                //     $('#mapabr fieldset').animate({height: 175});
                     $('#tblres').html(data).fadeIn('normal');
                // }
              });
        });

        $('.credenciamento').change(function () {
            if ($('input:radio[name=credenciamento]:checked').length > 0 && $('#cidade').val()) {
                $('#cidade').change();
            };
        });

        $('button').click(function () {
            $('#cidade').change();
            return false;
        });
    });

        function historicoCredenciamento(posto, token){

            if ($('#' + posto).html().length > 0){
                $('#' + posto).html('');
                $('#' + posto).hide();
                return true;
            };

            $.ajax({
                url: "posto_credenciamento.php",
                type: "POST",
                data: "posto="+posto+"&token="+token,
                success : function(retorno){

                    if (retorno.status == "true") {
                        var html = '<table class="bordasimples" align="center">';

                        html = html + '<tr align="center" style="background-color:#292929; color: #FFFFFF;">';
                        html = html + '<td width="20%">Data</td>';
                        html = html + '<td width="25%">Status</td>';
                        html = html + '<td width="15%">Qtde Dias</td>';
                        html = html + '<td width="40%">ObservaÁ„o</td>';
                        html = html + '</tr>';

                        for (var i in retorno.result) {

                            var bg = '';

                            if (i % 2 == 0) {
                                bg = '#FFFFFF';
                            } else {
                                bg = '#DCDCDC';
                            }

                            html = html + '<tr align="center" style="background-color: ' + bg + ';">';
                            html = html + '<td>' + retorno.result[i].data + '</td>';
                            html = html + '<td>' + retorno.result[i].status + '</td>';
                            html = html + '<td>' + retorno.result[i].dias + '</td>';
                            html = html + '<td align="left">' + retorno.result[i].texto + '</td>';
                            html = html + '</tr>';
                        };

                        html = html + '</table>';

                        $('#' + posto).html(html);
                        $('#' + posto).show();

                    };
                    
                }
            });
        }

    </script>
    <!--[if IE]>   
        <style type="text/css">
            .endereco { width: 350px; float: left; }
            dl dt { width: 80px; display: inline-block; float: left;}
            dl dd { width: 350px; display: inline-block; float: left; }
        </style>
    <!--<![endif]-->
</head>

<body style="background-color: #3E4C2A;">
    <div class="TITULO">
    </div>
    <div class="CONTEUDO">
    <div class="CONTEUDO_INTERNAS_MAIOR">
    	<div id='mapabr'>            
    		<form>
    			<fieldset style="background-color: #FFFFFF;">
                    <div>Mapa da Rede</div><br/>
    				<img src='imagens/mapa_famastil.png' alt='Mapa do Brasil' title='Selecione o Estado' usemap='#Map2' style='float:left;' border="0" />

                    <div id='sel_regiao'>
                        <label for="regiao">Selecione a Regi„o</label><br/>
                        <select title="Selecione a Regi„o" name="regiao" id="regiao" tabindex="1">
                            <option value=""></option>
                            <option value="N">Norte</option>
                            <option value="NE">Nordeste</option>
                            <option value="CO">Centro-Oeste</option>
                            <option value="SE">Sudeste</option>
                            <option value="S">Sul</option>
                        </select>
                    </div>

                    <div id='sel_estado'>
        				<label for="estado">Selecione o Estado:</label><br />
        				<select title="Selecione o Estado" name="estado" id="estado" tabindex="1">
        					<option value=""></option>
        				<?php
        				foreach ($estados as $uf=>$nome) {
                        	echo str_repeat("\t", 8)."<option value='$uf'>$nome</option>\n";
                        }
        				?>
                        </select>
                    </div>

    				<div id='sel_cidade'>
    					<label for="cidade">Selecione a Cidade:</label><br />
    					<select name="cidade" id="cidade" tabindex="2">
    					</select>
    				</div><br/>

                    <div style="width: 170px; float: right; text-align: left; font-size: 12px;">
                        <input type="radio" name="credenciamento" class="credenciamento" value="Todos" checked="checked" />Todos<br/>
                        <input type="radio" name="credenciamento" class="credenciamento" value="Credenciado" />Credenciado<br/>
                        <input type="radio" name="credenciamento" class="credenciamento" value="Em Descredenciamento" />Em Descredenciamento<br/>
                        <input type="radio" name="credenciamento" class="credenciamento" value="Descredenciado" />Descredenciado
                    </div>

    			</fieldset>
    		</form>
    <!--	<div style='position: absolute;bottom:2em;right:2.5em;text-align:right'>
    		Se a sua cidade n„o se encontra na relaÁ„o,<br>pode fazer a pesquisa no <a href="http://www.telecontrol.com.br/mapa_rede.php?fabrica=91" target='_blank'> <i>site</i> da <b>Telecontrol</b></a>.
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
    <div class="RODAPE">
    </div>
</body>
</html>
