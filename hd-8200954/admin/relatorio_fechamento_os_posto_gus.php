<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "gerencia";

$gera_automatico = trim($_GET["gera_automatico"]);

if ($gera_automatico != 'automatico') {
	include "autentica_admin.php";
}

include "gera_relatorio_pararelo_include.php";
include 'funcoes.php';

$layout_menu = "gerencia";
$title = "RELATÓRIO DE CONTROLE DE FECHAMENTO O.S";

include 'cabecalho.php';
include "javascript_pesquisas.php";
include "javascript_calendario.php";

$data_ini     = $_GET['data_ini'];
$data_fim     = $_GET['data_fim'];
$codigo_posto = $_GET['codigo_posto'];

if (strlen($data_ini) > 0 && strlen($data_fim) > 0) {

	$sql = "SELECT '$data_fim'::date - '$data_ini'::date as data";
	$res = @pg_exec($con, $sql);

	$msg_erro = @pg_errormessage($con);

	if (strlen($msg_erro) == 0) {

		if (pg_fetch_result($res, 0,'data') > 31) {
			$msg_erro = 'Preencha um período inferior ou igual a 31 dias';
		}

	}

} else {

	if (!empty($_GET)) {
		$msg_erro = 'O campo Data Inicial e Data Final é Obrigatório!';
	}

}?>

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

    .formulario{
        background-color:#D9E2EF;
        font:11px Arial;
    }

    .subtitulo{
        color: #7092BE
    }

    table.tabela tr td{
        font-family: verdana;
        font-size: 11px;
        border-collapse: collapse;
        border:1px solid #596d9b;
    }

</style>

<script type="text/javascript" charset="utf-8">
    $(function() {
        $('#data_ini').datePicker({startDate:'01/01/2000'});
        $('#data_fim').datePicker({startDate:'01/01/2000'});
        $("#data_ini").maskedinput("99/99/9999");
        $("#data_fim").maskedinput("99/99/9999");
    });
</script>
<?php

if (!empty($_GET) && strlen($msg_erro) == 0) {
	include "gera_relatorio_pararelo.php";
}

if (strlen($posto) == 0) {
	if ($gera_automatico != 'automatico' and strlen($msg_erro)== 0) {
		include "gera_relatorio_pararelo_verifica.php";
	}
}

?>
<form name="frm_rel" method="get" action="<?=$PHP_SELF;?>">
<table border="0" cellpadding="0" cellspacing="0" align="center" width="700" class="formulario"><?php
	if (strlen($msg_erro) > 0) {?>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td class="msg_erro"><?=$msg_erro?></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr><?php
	}?>
    <tr class="titulo_tabela">
        <td>Relatório de controle de fechamento O.S</td>
    </tr>
    <tr>
        <td valign="top" align="left">
            <table align='center' width='600' border='0'>
                <tr>
					<td colspan="2">&nbsp;</td>
				</tr>
                <tr align='left'>
                    <td>Data Inicial</td>
                    <td>Data Final</td>
                    <td>Posto</td>
                    <td>Nome do Posto</td>
                </tr>
                <tr>
                    <td><input size="12" maxlength="10" type="text" name="data_ini" class="frm" id="data_ini" value="<?=$data_ini;?>" /></td>
                    <td><input size="12" maxlength="10" type="text" name="data_fim" class="frm" id="data_fim" value="<?=$data_fim;?>" /></td>
                    <td>
                        <input type="text" name="codigo_posto" id="codigo_posto" size="8" value="<?=$codigo_posto?>" class="frm" />
                        <img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="fnc_pesquisa_posto(document.getElementById('codigo_posto'), document.getElementById('posto_nome'), 'codigo')" />
                    </td>
                    <td>
                        <input type="text" name="posto_nome" id="posto_nome" size="30" value="<?=$posto_nome?>" class="frm" />
                        <img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="fnc_pesquisa_posto(document.getElementById('codigo_posto'), document.getElementById('posto_nome'), 'nome')" />
                    </td>
                </tr>
                <tr><td colspan="2">&nbsp;</td></tr>
            </table>
        </td>
    </tr>
    <tr>
        <td>
            <input type="submit" style="background:url(imagens_admin/btn_filtrar.gif); width:95px; border:0; cursor:pointer;" value="" />
        </td>
    </tr>
    <tr>
        <td>&nbsp;</td>
    </tr>
</table>

<input type='hidden' name='btnacao' value=''>

</form><?php

if (!empty($_GET) && strlen($msg_erro) == 0) {

    echo '<br />';

    $sql = "SELECT COUNT(tbl_os.os) as total,
                   tbl_posto.posto,
                   tbl_posto.nome
              FROM tbl_os
              JOIN tbl_posto         ON tbl_posto.posto = tbl_os.posto
              JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
             WHERE tbl_os.fabrica = $login_fabrica ";
	if (strlen($codigo_posto) > 0) {
		$sql .= "AND tbl_posto_fabrica.codigo_posto = '$codigo_posto' ";
	}
    $sql .= "  AND tbl_os.data_abertura   BETWEEN '$data_ini' AND '$data_fim'
               AND tbl_os.data_fechamento IS NOT NULL
               AND tbl_os.finalizada      IS NOT NULL
               AND (tbl_os.excluida IS NULL OR tbl_os.excluida = 'f')
             GROUP BY tbl_posto.posto, tbl_posto.nome
             ORDER BY total desc;";
echo nl2br($sql);
    $res = pg_exec($con, $sql);
    $tot = pg_numrows($res);

    if ($tot > 0) {

        $dias = 5;

        echo "<table width='700' border='0' cellpadding='2' cellspacing='1' class='tabela'  align='center'>";
            echo "<tr class='titulo_tabela'>";
                echo "<th class='titulo_coluna'>Posto</th>";
                echo "<th class='titulo_coluna'>Nome Posto</th>";
                for ($x = $dias; $x <= ($dias * 6); $x += $dias) {
                    echo "<th class='titulo_coluna'>$x dias</th>";
                }
                echo "<th class='titulo_coluna'>+ " .($x - $dias) . " dias</th>";
				echo "<th class='titulo_coluna'>Total</th>";
                echo "<th class='titulo_coluna'>Média dias</th>";
            echo "</tr>";

            for ($i = 0; $i < $tot; $i++) {

                $total      = trim(pg_result($res, $i, 'total'));
                $posto      = trim(pg_result($res, $i, 'posto'));
                $nome_posto = trim(pg_result($res, $i, 'nome'));

				$subtotal += $total;

                $cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

                echo "<tr bgcolor='$cor' class='Label'>";
                    echo "<td align='left'>$posto</td>";
                    echo "<td nowrap='nowrap' align='left'>$nome_posto</td>";

                    $sql_total = "SELECT (tbl_os.data_fechamento - tbl_os.data_abertura) as total
                                    FROM tbl_os
                                    JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
                                   WHERE tbl_os.fabrica = $login_fabrica
                                     AND tbl_os.data_abertura   BETWEEN '$data_ini' AND '$data_fim'
                                     AND tbl_os.data_fechamento IS NOT NULL
                                     AND tbl_os.finalizada      IS NOT NULL
                                     AND (tbl_os.excluida IS NULL OR tbl_os.excluida = 'f')
                                     AND tbl_os.posto = $posto;";

                    $res_total = pg_exec($con, $sql_total);

                    $vet = array();

                    while ($linha = pg_fetch_assoc($res_total)) {

                        if ($linha['total'] <= 5) {
                            $vet[$dias * 1]++;
                        } else if ($linha['total'] > 5  && $linha['total'] <= 10) {
                            $vet[$dias * 2]++;
                        } else if ($linha['total'] > 10 && $linha['total'] <= 15) {
                            $vet[$dias * 3]++;
                        } else if ($linha['total'] > 15 && $linha['total'] <= 20) {
                            $vet[$dias * 4]++;
                        } else if ($linha['total'] > 20 && $linha['total'] <= 25) {
                            $vet[$dias * 5]++;
                        } else if ($linha['total'] > 25 && $linha['total'] <= 30) {
                            $vet[$dias * 6]++;
                        } else if ($linha['total'] > 30) {
                            $vet[$dias * 7]++;
                        }

                    }

                    for ($x = $dias; $x <= ($dias * 7); $x += $dias) {
                        echo "<td align='center'>".abs($vet[$x])."</td>";
						$media_dias[$x] += $vet[$x];
                    }

                    $sql_media = "SELECT SUM(tbl_os.data_fechamento - tbl_os.data_abertura) / $total as media
                                    FROM tbl_os
                                    JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
                                   WHERE tbl_os.fabrica = $login_fabrica
                                     AND tbl_os.data_abertura   BETWEEN '$data_ini' AND '$data_fim'
                                     AND tbl_os.data_fechamento IS NOT NULL
                                     AND tbl_os.finalizada      IS NOT NULL
                                     AND (tbl_os.excluida IS NULL OR tbl_os.excluida = 'f')
                                     AND tbl_os.posto = $posto;";

                    $res_media = pg_exec($con, $sql_media);

					$media = pg_result($res_media, 0, 'media');
					$submedia += $media;

                    echo "<td align='left'>$total</td>";
                    echo "<td align='center'>".$media."</td>";

                echo "</tr>";

                flush();

            }

            echo "<tr class='titulo_tabela'>";
				echo "<td colspan='2'>Total</td>";
				for ($x = $dias; $x <= ($dias * 7); $x += $dias) {
					echo "<td align='center'>".abs($media_dias[$x])."</td>";
				}
				echo "<td>$subtotal</td>";
				echo "<td>".number_format($submedia/$i,0)."</td>";
            echo "</tr>";

        echo "</table>";

    } else {

        echo "<font size='2' face='Verdana, Tahoma, Arial' color='#D9E2EF'><b>Nenum registro encontrado!<b></font>";

    }

}

echo "<br />";

include "rodape.php";

?>
