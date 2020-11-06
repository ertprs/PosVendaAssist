<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = 'auditoria';

include 'autentica_admin.php';
include 'funcoes.php';

if (empty($_POST['periodo'])) {
    $_POST['periodo'] = 90;//valor padrao do relatorio
    $primeira_vez = 1;
}

$layout_menu = 'auditoria';
$title = 'Auditoria de OSs abertas a mais de ' . $_POST['periodo'] . ' dias';

include "cabecalho.php";
include 'javascript_pesquisas.php';
?>

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
.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
</style>

<?php

//FILTRO - HD 207142
if ($login_fabrica == 3) {?>
    <form name="frm_pesquisa" method="POST" action="<?=$PHP_SELF?>">
        <table width="700" align="center" border="0" cellspacing="1" cellpadding="0" class="formulario">
            <tbody>
				<tr><td class="titulo_tabela" colspan="3">Parâmetros de Pesquisa</td></tr>
                <tr>
					<td width="20%">&nbsp;</td>
                    <td>
						Período<br />
                        <select name='periodo' class='frm' onchange='document.frm_pesquisa.submit()'><?
                            // echo "<option value='45' ".($_POST['periodo'] == 45 ? ' selected="selected"' : '').">45</option>\n";
                            echo "<option value='90' ".($_POST['periodo'] == 90 ? ' selected="selected"' : '').">90</option>\n";
                            /*
                            for ($x = 1 ; $x <= 90 ; $x++) {
                                echo "<option value='$x' ".($_POST['periodo'] == $x ? ' selected="selected"' : '') .">$x</option>\n";
                            }
                            */?>
                        </select>
                    </td>
                    <td>
						Linha<br />
						<?php
                        $sql = "SELECT *
                                  FROM tbl_linha
                                 WHERE tbl_linha.fabrica = $login_fabrica
                                 ORDER BY tbl_linha.nome;";
                        $res = pg_exec($con,$sql);
                        if (pg_numrows($res) > 0) {
                            echo "<select name='linha' class='frm' onchange='document.frm_pesquisa.submit()'>\n";
                                echo "<option value=''>TODAS</option>\n";
                                for ($x = 0 ; $x < pg_numrows($res) ; $x++) {
                                    $id_linha = trim(pg_result($res,$x,linha));
                                    $aux_nome = trim(pg_result($res,$x,nome));
                                    if ($_POST['periodo'] == 45) {
                                        if ($id_linha == 528) {
                                            echo "<option value='$id_linha'".($_POST['linha'] == $id_linha ? ' selected="selected"' : '').">$aux_nome</option>\n";
                                        }
                                    } else {
                                        echo "<option value='$id_linha'".($_POST['linha'] == $id_linha ? ' selected="selected"' : '').">$aux_nome</option>\n";
                                    }

                                }
                            echo "</select>\n&nbsp;";
                        }?>
                    </td>
                </tr>
                <tr>
					<td>&nbsp;</td>
                    <td>
						Posto<br />
                        <input type="text" name="codigo_posto" id="codigo_posto" size="8" value="<? echo $codigo_posto ?>" class="frm" />
                        <img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto(document.getElementById('codigo_posto'), document.getElementById('posto_nome'), 'codigo')" />
                    </td>
                    <td>
						Nome Posto<br />
                        <input type="text" name="posto_nome" id="posto_nome" size="30" value="<?echo $posto_nome?>" class="frm" />
                        <img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto(document.getElementById('codigo_posto'), document.getElementById('posto_nome'), 'nome')" />
                    </td>
                </tr>
                <tr>
                    <td colspan="3" align="center" style="padding:10px 0 10px;">
                       <center><input type="submit" name="btn_enviar" value="Pesquisar" /></center>
                    </td>
                </tr>
            </tbody>
        </table>
    </form><?php
    if ($primeira_vez) {
        //PRIMEIRO FILTRAR DEPOIS PESQUISAR
        die;
    }
}

/**HD 261150 - estou comentando o if a baixo
if (empty($_POST['codigo_posto'])) {
    echo "<br><center>O campo posto deve ser preenchido.</center>";
    include "rodape.php";
    die;
}
*/

if ($_POST['periodo'] == 45) {
    $sql_tipo  = " 140, 141, 142, 143 ";
    $aprovacao = " 140, 141 ";
    $status_bloq = 140;
    $status_just = 141;
} else {
    $sql_tipo  = " 120, 122, 123, 126 ";
    $aprovacao = " 120, 122 ";
    $status_bloq = 120;
    $status_just = 122;
}

$sql =  "SELECT DISTINCT os
           INTO TEMP tmp_interv_90_$login_admin_2
           FROM tbl_os_status
          WHERE fabrica_status = $login_fabrica
            AND status_os IN($sql_tipo);

         SELECT interv.os
           INTO TEMP tmp_interv_90_$login_admin
           FROM (SELECT ultima.os,
                        (SELECT status_os
                           FROM tbl_os_status
                          WHERE fabrica_status = $login_fabrica
                            AND status_os IN($sql_tipo)
                            AND tbl_os_status.os = ultima.os
                          ORDER BY data
                           DESC LIMIT 1
                        ) AS ultimo_status
                   FROM (SELECT os FROM tmp_interv_90_$login_admin_2) ultima
                ) interv
          WHERE interv.ultimo_status IN ($aprovacao)
           $Xos;

    CREATE INDEX tmp_interv_OS_90_$login_admin ON tmp_interv_90_$login_admin(os);

	SELECT tbl_os.fabrica,
		   tbl_os.os,
		   tbl_os.produto,
		   tbl_os.data_abertura,
		   tbl_os.excluida,
		   tbl_os.finalizada,
		   tbl_os.data_fechamento,
		   tbl_posto.posto,
           tbl_posto_fabrica.codigo_posto,
           tbl_posto.nome,
           tbl_posto_fabrica.contato_estado as estado
	INTO   TEMP tmp_os_90_$login_admin
	FROM   tmp_interv_90_$login_admin X
	JOIN   tbl_os ON tbl_os.os = X.os
	JOIN   tbl_posto using(posto)
	JOIN   tbl_posto_fabrica ON tbl_posto.posto     = tbl_posto_fabrica.posto
           AND tbl_posto_fabrica.fabrica = $login_fabrica
	LEFT JOIN tbl_produto  ON tbl_produto.produto = tbl_os.produto
	WHERE tbl_os.fabrica = $login_fabrica 
	  AND tbl_os.fabrica = tbl_produto.fabrica_i ";

    if (!empty($_REQUEST['linha'])) {
        $sql .=  " AND linha = " . $_REQUEST['linha'];
    }
    if (!empty($_REQUEST['codigo_posto'])) {
        $sql .=  " AND tbl_posto_fabrica.codigo_posto = '" . $_REQUEST['codigo_posto']."'";
    }

     $sql .= " AND excluida IS NOT TRUE
              AND finalizada IS NULL
	      AND data_fechamento IS NULL; ";

	$sql .=" CREATE INDEX tmp_os_90_$login_admin_os ON tmp_os_90_$login_admin(os);

    SELECT tmp_os_90_$login_admin.posto,
           tmp_os_90_$login_admin.codigo_posto,
           tmp_os_90_$login_admin.nome,
           tmp_os_90_$login_admin.estado,
           count(tmp_os_90_$login_admin.os) as qtde_os
      FROM tmp_interv_90_$login_admin X
      JOIN tmp_os_90_$login_admin ON tmp_os_90_$login_admin.os = X.os
      GROUP BY tmp_os_90_$login_admin.posto,
               tmp_os_90_$login_admin.codigo_posto,
               tmp_os_90_$login_admin.nome,
               tmp_os_90_$login_admin.estado
      ORDER BY tmp_os_90_$login_admin.nome ";
#echo nl2br($sql);
#die;
$res = pg_exec($con,$sql);

if (pg_numrows($res)>0) {

    echo "<br><table width='700' border='0' align='center' cellpadding='3' cellspacing='1' class='tabela'>";
    echo "<tr class='titulo_coluna'>";
    echo "<td >Posto</td>";
    echo "<td >Nome Posto</td>";
    echo "<td >Estado</td>";
    echo "<td >OS sem Justificativa</td>";
    echo "<td >OS com Justificativa</td>";
    echo "<td >Qtde Total</td>";
    echo "</tr>";

    $cores = '';
    $qtde_intervencao = 0;

    for ($x=0; $x < pg_numrows($res);$x++) {

        $posto             = pg_result($res, $x, posto);
        $codigo_posto      = pg_result($res, $x, codigo_posto);
        $nome              = pg_result($res, $x, nome);
        $estado            = pg_result($res, $x, estado);
        $qtde_os           = pg_result($res, $x, qtde_os);

        $sql_sem = "SELECT count(*) AS qtde_sem
                    FROM (
                        SELECT
                        ultima.os,
                        (
                            SELECT status_os
                            FROM tbl_os_status
                            WHERE fabrica_status=$login_fabrica and status_os IN ($aprovacao)
                                AND tbl_os_status.os = ultima.os
                            ORDER BY data
                            DESC LIMIT 1
                        ) AS ultimo_status
                        FROM (
                                SELECT DISTINCT os
                                FROM tmp_interv_90_$login_admin
                                JOIN tmp_os_90_$login_admin USING(os)
                                LEFT JOIN tbl_produto ON tbl_produto.produto = tmp_os_90_$login_admin.produto
                                WHERE posto   = $posto
                                AND   fabrica = $login_fabrica 
				AND   tbl_produto.fabrica_i = $login_fabrica ";
                    if (!empty($_POST['linha'])) {
                        $sql_sem .=    " AND linha = " . $_POST['linha'];
                    }
                    $sql_sem .=    " AND excluida is not true
                                     AND finalizada is null
				     AND data_fechamento is null
                        ) ultima
                    ) interv
                    JOIN tmp_os_90_$login_admin USING(os)
                    WHERE interv.ultimo_status IN ($status_bloq)
                    AND   tmp_os_90_$login_admin.posto = $posto
                    AND excluida is not true
                    AND finalizada is null
		    AND data_fechamento is null
                    AND fabrica  = $login_fabrica";
        //echo nl2br($sql_sem);
        //if($ip == '201.76.71.206') echo nl2br($sql_sem);
        $res_sem = pg_exec($con,$sql_sem);
        if (pg_numrows($res_sem) > 0) $qtde_sem = pg_result($res_sem,0,'qtde_sem');
        else                          $qtde_sem = 0;
        //$qtde_sem = 0;
        $sql_com = "SELECT count(*) as qtde_com
                    FROM (
                    SELECT
                    ultima.os,
                    (
                        SELECT status_os
                        FROM tbl_os_status
                        WHERE fabrica_status = $login_fabrica and status_os IN ($aprovacao)
                            AND tbl_os_status.os = ultima.os
                        ORDER BY data
                        DESC LIMIT 1
                    ) AS ultimo_status
                    FROM (
                            SELECT DISTINCT os
                            FROM tmp_interv_90_$login_admin
                            JOIN tmp_os_90_$login_admin USING(os)
                            LEFT JOIN tbl_produto ON tbl_produto.produto = tmp_os_90_$login_admin.produto
                            WHERE posto   = $posto
                            AND   fabrica = $login_fabrica 
			    AND   tbl_produto.fabrica_i=$login_fabrica ";
                    if (!empty($_POST['linha'])) {
                        $sql_com .=    " AND linha = " . $_POST['linha'];
                    }
                    $sql_com .=    " AND excluida is not true
                                     AND finalizada is null
				     AND data_fechamento is null
                    ) ultima
                ) interv
                JOIN tmp_os_90_$login_admin USING(os)
                WHERE interv.ultimo_status IN ($status_just)
                AND   tmp_os_90_$login_admin.posto = $posto
                AND excluida is not true
                AND finalizada is null
		AND data_fechamento is null
                AND fabrica  = $login_fabrica";

        $res_com = pg_exec($con,$sql_com);
        if (pg_numrows($res_com) > 0) $qtde_com = pg_result($res_com,0,'qtde_com');
        else                          $qtde_com = 0;
        //$qtde_com = 0;
        $cor = ($x % 2) ? "#F7F5F0" : "#F1F4FA";
        echo "<tr bgcolor='$cor' >";
        echo "<td nowrap ><a href='auditoria_os_aberta_90_aprova.php?posto=$posto'  target='_blank'>$codigo_posto</a></td>";
        echo "<td align='left'>".$nome. "</td>";
        echo "<td nowrap><acronym title='Estado: $estado' style='cursor: help'>". $estado."</acronym></td>";
        echo "<td nowrap><acronym style='cursor: help'><a href='auditoria_os_aberta_{$_POST['periodo']}_aprova.php?posto=$posto&status=$status_bloq&linha={$_POST['linha']}'  target='_blank'>$qtde_sem</a></acronym></td>";
        echo "<td nowrap><acronym style='cursor: help'><a href='auditoria_os_aberta_{$_POST['periodo']}_aprova.php?posto=$posto&status=$status_just&linha={$_POST['linha']}'  target='_blank'>$qtde_com</a></acronym></td>";
        echo "<td nowrap><acronym style='cursor: help'><a href='auditoria_os_aberta_{$_POST['periodo']}_aprova.php?posto=$posto&linha={$_POST['linha']}' target='_blank'>$qtde_os</a></acronym></td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<br/>";
    echo "<center><table class='table_line'>
        <tr>
            <td style='font-size: 9px; font-family: verdana' nowrap>
                <a href='auditoria_os_aberta_{$_POST['periodo']}_download.php' target='_blank'>Clique aqui para download de todas as OS's em Auditoria.</a>
            </td>
        </tr>
        </table></center>";


} else {
    echo "<br><center>Nenhum OS encontrada.</center>";
}

include "rodape.php";?>
