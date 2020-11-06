<?php

if (!is_resource($resx)) {
	include_once 'rodape.php';
	exit;
}

$chamados = array();

switch ($relatorio) {
	case "semanal":
		while ($fetch = pg_fetch_assoc($resx)) {
			if ($fetch['tipo_chamado'] == 5) {
				$chamados[1]["erro"][] = $fetch;
			} else {
				$chamados[1]["alteracao"][] = $fetch;
			}
			krsort($chamados[1]);
		}
		break;
	case "mensal":
		$semana_anterior = 0;
		$semana_mes = 0;
		while ($fetch = pg_fetch_assoc($resx)) {
			if ($fetch["semana"] <> $semana_anterior) {
			    $semana_mes++;
				$semana_anterior = $fetch['semana'];
			}
			$indice = $semana_mes;

			if ($fetch['tipo_chamado'] == 5) {
				$chamados[$indice]["erro"][] = $fetch;
			} else {
				$chamados[$indice]["alteracao"][] = $fetch;
			}
			krsort($chamados[$indice]);
		}
		break;
}

$total_chamados = 0;
$total_erros = 0;
$total_desenvolvimento = 0;
$total_horas_analisadas_erros = 0;
$total_horas_analisadas_desenvolvimento = 0;
$total_horas_desenvolvidas_erros = 0;
$total_horas_desenvolvidas_desenvolvimento = 0;

if (count($chamados) == 4) {
    $chamados[] = array('erro' => 0, 'alteracao' => 0);
}


echo '<div class="quadro">';

foreach ($chamados as $key => $value) {
    echo '<div class="semana">';
        echo '<div class="header">' , $key , 'ª Semana</div>';
        echo '<div class="dev">';

            echo '<table align="center">';

            echo '<tr>';

            $hd_desenvolvimento = 0;
            $hd_erro = 0;

            if (is_array($value['alteracao'])) {
            	$hd_desenvolvimento = count($value['alteracao']);
            }

            if (is_array($value['erro'])) {
	            $hd_erro = count($value['erro']);
	        }

	        $total_desenvolvimento+= $hd_desenvolvimento;
	        $total_erros+= $hd_erro;
	        $total_chamados+= $hd_desenvolvimento + $hd_erro;

            $i = 0;

            foreach ($value['alteracao'] as $desenvolvimento) {
                $i++;

                list($xhoras_analisadas, $xnome_a, $xnome_d, $xnome_s) = explode("|", $desenvolvimento['backlog_item']);
				$horas_analisadas = $xhoras_analisadas;

				$analista         = $xnome_a;
				$desenvolvedor    = $xnome_d;
				$suporte          = $xnome_s;

				$horas_utilizadas = calc_horas($desenvolvedor, $desenvolvimento['hd_chamado']);
				$nome_a = con_admin($analista);
				$nome_d = con_admin($desenvolvedor);
				$nome_s = con_admin($suporte);

				$total_horas_analisadas_desenvolvimento+= $horas_analisadas;
				$total_horas_desenvolvidas_desenvolvimento+= $horas_utilizadas;

                echo '<td>';
                echo '    <div class="card" onClick="abreChamado(\'' , $desenvolvimento['hd_chamado'] , '\')" style="cursor: pointer;">' , "\n";
                echo '        <img src="imagens_painel/post-it-branco.png" class="card-image">' , "\n";
                echo '    <div class="content">' , "\n";

                echo '    <div class="titulo_chamado">' , "\n";
                echo '        <h4 title="' , $desenvolvimento['titulo'] , '"><span class="num" id="num">' , $desenvolvimento['hd_chamado'] , '</span></h4>' , "\n";
                echo '    </div>' , "\n";

                echo '<div class="fabrica">' , "\n";
                echo '    <p><span class="bold">Fabrica: </span>' , $desenvolvimento['nome'] , '</p><br/>' , "\n";
                echo '    <p>' , $desenvolvimento['descricao'] , '</p>' , "\n";
                echo '</div>';

                echo '<div class="infos">' , "\n";
                echo '    <p><span class="bold">Hr. An.:</span> ' . $horas_analisadas . ' <span class="onright bold">Hr. Dv.: </span>' . $horas_utilizadas . '</p>' , "\n";
                echo '    <p><span class="bold">Analista: </span> ' . $nome_a . '</p><br/>' , "\n";
                echo '    <p><span class="bold">Desenvolvedor: </span> ' . $nome_d . '</p><br/>' , "\n";
                echo '    <p><span class="bold">Suporte: </span> ' . $nome_s . '</p>' , "\n";
                echo '</div>';
                echo '</td>';

                if ($i % 3 == 0) {
                    echo '</tr><tr>';
                }
            }

            echo '</table>';

        echo '</div>';

        echo '<div class="erros" id="erro_' , $key , '" >';
        	echo '<table align="center">';

            echo '<tr>';

            $i = 0;

            foreach ($value['erro'] as $erro) {
                $i++;

                list($xhoras_analisadas, $xnome_a, $xnome_d, $xnome_s) = explode("|", $erro['backlog_item']);
				$horas_analisadas = $xhoras_analisadas;

				$analista         = $xnome_a;
				$desenvolvedor    = $xnome_d;
				$suporte          = $xnome_s;

				$horas_utilizadas = calc_horas($desenvolvedor, $erro['hd_chamado']);
				$nome_a = con_admin($analista);
				$nome_d = con_admin($desenvolvedor);
				$nome_s = con_admin($suporte);

				$total_horas_analisadas_erros+= $horas_analisadas;
				$total_horas_desenvolvidas_erros+= $horas_utilizadas;

                echo '<td>';
                echo '    <div class="card" onClick="abreChamado(\'' , $erro['hd_chamado'] , '\')" style="cursor: pointer;">' , "\n";
                echo '        <img src="imagens_painel/post-it-rosa.png" class="card-image">' , "\n";
                echo '    <div class="content">' , "\n";

                echo '    <div class="titulo_chamado">' , "\n";
                echo '        <h4 title="' , $erro['titulo'] , '"><span class="num" id="num">' , $erro['hd_chamado'] , '</span></h4>' , "\n";
                echo '    </div>' , "\n";

                echo '<div class="fabrica">' , "\n";
                echo '    <p><span class="bold">Fabrica: </span>' , $erro['nome'] , '</p><br/>' , "\n";
                echo '    <p>' , $erro['descricao'] , '</p>' , "\n";
                echo '</div>';

                echo '<div class="infos">' , "\n";
                echo '    <p><span class="bold">Hr. An.:</span> ' . $horas_analisadas . ' <span class="onright bold">Hr. Dv.: </span>' . $horas_utilizadas . '</p>' , "\n";
                echo '    <p><span class="bold">Analista: </span> ' . $nome_a . '</p><br/>' , "\n";
                echo '    <p><span class="bold">Desenvolvedor: </span> ' . $nome_d . '</p><br/>' , "\n";
                echo '    <p><span class="bold">Suporte: </span> ' . $nome_s . '</p>' , "\n";
                echo '</div>';
                echo '</td>';

                if ($i % 3 == 0) {
                    echo '</tr><tr>';
                }
            }

            echo '</table>';
        echo '</div>';
    echo '</div>';
}

echo '</div><br/>';


$total_horas_analisadas_geral = $total_horas_analisadas_erros + $total_horas_analisadas_desenvolvimento;
$total_horas_desenvolvidas_geral = $total_horas_desenvolvidas_erros + $total_horas_desenvolvidas_desenvolvimento;

echo '<table id="totais" style="margin-bottom: 20px; padding-top: 15px;">' , "\n";
echo '    <tr>' , "\n";
echo '        <td colspan="3" align="center">' , "\n";
echo '            Total de chamados: ' , $total_chamados , "\n";
echo '        </td>' , "\n";
echo '    <tr>' , "\n";
echo '        <td align="right">' , "\n";
echo '            <div style="margin-right: 5px;">Total HD erros: ' , $total_erros , ' - ' , number_format((($total_erros / $total_chamados) * 100), 0, '', '') , "%</div>\n";
echo '        </td><td rowspan="4" style="width: 0.1px; background-color: #000;">' , "\n";
echo '        <td>' , "\n";
echo '            <div style="margin-left: 5px;">Total HD desenvolvimento: ' , $total_desenvolvimento , ' - ' , number_format((($total_desenvolvimento / $total_chamados) * 100), 0, '', '') , "%</div>\n";
echo '        </td>' , "\n";
echo '    </tr>' , "\n";
echo '    <tr>' , "\n";
echo '        <td align="right">' , "\n";
echo '            <div style="margin-right: 5px;">Total horas analisadas erros: ' , $total_horas_analisadas_erros , ' - ' , number_format((($total_horas_analisadas_erros / $total_horas_analisadas_geral) * 100), 0, '', '') , "%</div>\n";
echo '        </td>' , "\n";
echo '        <td>' , "\n";
echo '            <div style="margin-left: 5px;">Total horas analisadas alteração: ' , $total_horas_analisadas_desenvolvimento , ' - ' , number_format((($total_horas_analisadas_desenvolvimento / $total_horas_analisadas_geral) * 100), 0, '', '') , "%</div>\n";
echo '        </td>' , "\n";
echo '    </tr>' , "\n";
echo '    <tr>' , "\n";
echo '        <td align="right">' , "\n";
echo '            <div style="margin-right: 5px;">Total horas desenvolvidas erros: ' , $total_horas_desenvolvidas_erros  , ' - ' , number_format((($total_horas_desenvolvidas_erros / $total_horas_desenvolvidas_geral) * 100), 0, '', '') , "%</div>\n";
echo '        </td>' , "\n";
echo '        <td>' , "\n";
echo '            <div style="margin-left: 5px;">Total horas desenvolvidas alteração: ' , $total_horas_desenvolvidas_desenvolvimento , ' - ' , number_format((($total_horas_desenvolvidas_desenvolvimento / $total_horas_desenvolvidas_geral) * 100), 0, '', '') , "%</div>\n";
echo '        </td>' , "\n";
echo '    </tr>' , "\n";
echo '    <tr>' , "\n";
echo '        <td align="right">' , "\n";
echo '            <div style="margin-right: 5px;">Total horas analisadas geral: ' , $total_horas_analisadas_geral , "</div>\n";
echo '        </td>' , "\n";
echo '        <td>' , "\n";
echo '            <div style="margin-left: 5px;">Total horas desenvolvidas geral: ' , $total_horas_desenvolvidas_geral , "</div>\n";
echo '        </td>' , "\n";
echo '    </tr>' , "\n";
echo '</table>';

echo "<script>
        function redimensionaDiv() {
            var divdev = $('.dev');
            var diverr = $('.erros');

            var maior = 0;
            var tamanho = 0;
            var tamanhodev = 0;
            var tamanhoerr = 0;
            var tamanhotmp = 0;

            for (i = 0; i < diverr.length; i++) {
               tamanhodev = parseInt($(divdev[i]).css('height'));
               tamanhoerr = parseInt($(diverr[i]).css('height'));

               tamanho = tamanhodev + tamanhoerr;

               if (tamanho > maior) {
                   maior = tamanho;
               }
               
            }

            for (i=0; i<diverr.length; i++) {
               tamanhodev = parseInt($(divdev[i]).css('height'));
               tamanhoerr = maior - tamanhodev;
               $('#erro_' + (i+1)).css('height', tamanhoerr + 'px');
               console.log(tamanhoerr);
            }

        }

        $(function(){
                redimensionaDiv();
        });
    </script>";

if (false === $tv) {
    include_once 'rodape.php';
} else {
    echo '</body></html>';
}
