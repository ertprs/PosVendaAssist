
<script type="text/javascript">
function lista_risco(sel) {
    var url = "";
        url = "os_risco_lista.php?sel=" + sel;
        janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=650,height=600,top=18,left=20");
        janela.focus();
}
</script>

<?
include_once "funcoes.php";

if($login_fabrica == 1){
#------ Volta a OS de troca recusada para aprovação -----#
$os_troca_aprovacao = $_GET['troca_aprovacao'];
if (strlen ($os_troca_aprovacao) > 0) {

    $atualiza = true;

    include 'anexaNF_inc.php';

    if (!temNF($os_troca_aprovacao, 'bool')) {
        $sqlObs = "SELECT observacao FROM tbl_os_status
                WHERE os = $os_troca_aprovacao
                AND status_os = 13
                AND fabrica_status = 1
                ORDER BY tbl_os_status.data DESC LIMIT 1";
        $qryObs = pg_query($con, $sqlObs);

        if (pg_num_rows($qryObs) > 0) {
            $obs = pg_fetch_result($qryObs, 0, 'observacao');

            $pos = strpos($obs, 'SEM NOTA FISCAL');

            if ($pos !== false) {
                echo '<script>alert("Essa OS foi recusada pelo fabricante por falta de Nota Fiscal. Para retornar a OS para aprovação,é necessário anexar o arquivo. Em caso de dúvida, favor entrar em contato com o suporte de sua região.")</script>';
                $atualiza = false;
            }
        }
    }

    if (true === $atualiza) {
		$sql_aprova = "update tbl_os_troca set status_os = null WHERE os = $os_troca_aprovacao;";
		$res_aprova = @pg_query ($con,$sql_aprova);
    }
}

#---------------- Fim troca aprovação -------------------#
}

if ($login_fabrica == 24){
	$cond_data = " AND tbl_os.data_abertura >= '2013-09-30' AND data_digitacao > '2013-09-30 00:00:00' ";
	$cond_cancelada = "AND tbl_os.cancelada IS NOT TRUE ";
}

/**
 *
 * HD 749695 - Latinatec não listar OSs em auditoria (há mais de 60 dias)
 *
 */
$extraCond = '';
if ($login_fabrica == 15){
	$extraCond = "AND tbl_os.os not in (select distinct os from tbl_os_status where tbl_os_status.fabrica_status=$login_fabrica AND status_os in (120, 122, 123, 126) and os = tbl_os.os  and (select status_os from tbl_os_status where tbl_os_status.fabrica_status=$login_fabrica AND status_os in (120, 122, 123, 126) and os = tbl_os.os order by data desc limit 1) = 120)";
}

if($login_fabrica == 1){

	$sql =	"SELECT tbl_os.os                                                  ,
			tbl_os.sua_os                                              ,
			LPAD(tbl_os.sua_os,10,'0')                   AS os_ordem   ,
			TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura   ,
			tbl_produto.produto                                        ,
			tbl_produto.referencia                                     ,
			tbl_produto.descricao                                      ,
			tbl_produto.voltagem,
			tbl_posto_fabrica.codigo_posto
		FROM tbl_os
		JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i=$login_fabrica
		LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_os.fabrica = $login_fabrica
			AND   tbl_os.posto   = $login_posto
			AND   (tbl_os.data_abertura + INTERVAL '60 days') <= current_date
			AND   tbl_os.data_fechamento IS NULL
			$extraCond
			$sql_cond
			AND  tbl_os.excluida is FALSE LIMIT 3";

	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		echo $cabecalho->alert(
			"O SEU POSTO DE SERVIÇOS POSSUI OS'S ABERTAS HÁ MAIS DE 30 DIAS QUE NÃO FORAM FINALIZADAS. SE ESSAS OS'S NÃO FOREM FECHADAS NUM PRAZO DE ATÉ 60 DIAS A SUA TELA SERÁ BLOQUEADA PARA CADASTRO DE NOVAS OS'S. SE HOUVER ALGUMA O.S COM PENDÊNCIA OU DÚVIDA QUE PRECISE DO NOSSO AUXÍLIO SOLICITAMOS QUE ABRA UM CHAMADO PARA O SEU SUPORTE. <a href='os_consulta_avancada.php?btn_acao=listar_90' target='_blank' style='color:#FF0000'>CLIQUE AQUI</a> PARA VISUALIZAR ESSAS OS'S.",
			"warning"
			);
		echo "<br>";
	}
		##### OS QUE EXCEDERAM O PRAZO LIMITE DE 90 DIAS PARA FECHAMENTO #####
}

if($login_fabrica <> 0 AND $login_fabrica <> 35){


	########################################################
	# VERIFICA SE TEM PEDIDO EM ABERTO HA MAIS DE UMA SEMANA
	########################################################
	$sqlX = "SELECT to_char (current_date - INTERVAL '6 day', 'YYYY-MM-DD')";
	$resX = pg_query ($con,$sqlX);
	$dt_inicial = pg_fetch_result ($resX,0,0) . " 00:00:00";
	$dt_inicial = '2005-12-26 13:40:00';

	if ($login_fabrica == 1) {
		$sql = "SELECT  lpad(tbl_pedido.pedido_blackedecker::text,5,'0') AS pedido_blackedecker,
						tbl_pedido.seu_pedido
				FROM    tbl_pedido
				WHERE   tbl_pedido.exportado           ISNULL
				AND     tbl_pedido.controle_exportacao ISNULL
				AND     tbl_pedido.admin               ISNULL
				AND     (tbl_pedido.natureza_operacao ISNULL
					  OR tbl_pedido.natureza_operacao <> 'SN-GART'
					 AND tbl_pedido.natureza_operacao <> 'VN-REV')
				AND     tbl_pedido.pedido_os IS NOT TRUE
				AND     tbl_pedido.pedido_acessorio IS NOT TRUE
				AND     tbl_pedido.pedido_sedex IS NOT TRUE
				AND     tbl_pedido.tabela = 108
				AND     tbl_pedido.status_pedido <> 14
				AND     tbl_pedido.posto             = $login_posto
				AND     tbl_pedido.fabrica           = $login_fabrica
				ORDER BY tbl_pedido.pedido DESC LIMIT 1;";
		$res = pg_query ($con,$sql);
		if (pg_num_rows($res) > 0) {
			$pedido_blackedecker = trim(pg_fetch_result($res,0,pedido_blackedecker));
			$seu_pedido          = trim(pg_fetch_result($res,0,seu_pedido));

			if (strlen($seu_pedido)>0){
				$pedido_blackedecker = fnc_so_numeros($seu_pedido);
			}
			echo $cabecalho->alert(
				"Existe o pedido de número <font color='#CC3300'>$pedido_blackedecker</font> sem finalização, o qual ainda não foi enviado para a fábrica.<br>Por gentileza, acesse a tela de digitação de pedidos e clique no botão <font color='#CC3300'>FINALIZAR</font>.",
				"warning"
			);
			echo "<br>";
		}
	}

	if(!in_array($login_fabrica, array(30,91,86))){

		if($login_fabrica == 24 or $login_fabrica == 104){
			$left_join_os_produto = " LEFT JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os ";
		}

		if(in_array($login_fabrica,array(151))){
			$left_join_os_campo_extra = " LEFT JOIN tbl_os_campo_extra ON tbl_os.os = tbl_os_campo_extra.os ";
			$cond_bloqueada = " AND tbl_os_campo_extra.os_bloqueada IS NOT TRUE ";
		}

		$sql =	"SELECT tbl_os.os                                                  ,
				tbl_os.sua_os                                              ,
				tbl_os.tipo_atendimento                                    ,
				LPAD(tbl_os.sua_os,10,'0')                   AS os_ordem   ,
				TO_CHAR(tbl_os.data_abertura,'DD/MM/YY')   AS abertura     ,
				tbl_produto.produto                                        ,
				tbl_produto.referencia                                     ,
				tbl_produto.descricao                                      ,
				tbl_produto.voltagem                                       ,
				tbl_posto_fabrica.codigo_posto
			FROM    tbl_os
			JOIN    tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i=$login_fabrica
			LEFT    JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			$left_join_os_produto
			$left_join_os_campo_extra
			WHERE   tbl_os.fabrica = $login_fabrica
			AND   tbl_os.posto   = $login_posto
			$cond_data
			$cond_cancelada
			$cond_bloqueada
			AND   tbl_os.excluida IS FALSE $extraCond ";
			if($login_fabrica == 11 or $login_fabrica == 51 or $login_fabrica == 81) {
				$sql .= " AND tbl_os.data_abertura <= CURRENT_DATE - INTERVAL '30 days' ";
			}elseif($login_fabrica == 24 or $login_fabrica == 104){
				$sql .= " AND tbl_os.data_abertura > CURRENT_DATE - INTERVAL '15 days' AND tbl_os_produto.os_produto IS NULL ";
			}else{
				$sql .= " AND tbl_os.data_abertura <= CURRENT_DATE - INTERVAL '15 days'";
			}
			$sql .= " AND   tbl_os.data_fechamento IS NULL LIMIT 3";
			$res = pg_query($con,$sql);
		if (pg_num_rows($res) > 0) {
			echo "
				<table class='table_tc table-bordered  table-hover table-large'>
				<thead>
				<tr class='titulo_coluna'>
				<th colspan='3'>
			";
			if (!in_array($login_fabrica,array(11,51,81))) {
				if ($sistema_lingua == "ES") {
					echo "&nbsp;OS SIN FECHA DE CIERRE HACE 15 DÍAS O MÁS DE LA FECHA DE ABERTURA&nbsp;";
				} else {
					if (in_array($login_fabrica,array(24,104))) {
						echo "&nbsp;O.S ATÉ 15 DIAS SEM SOLICITA&Ccedil;&Atilde;O DE PE&Ccedil;AS";
					} else {
						echo "&nbsp;OS SEM DATA DE FECHAMENTO HÁ 15 DIAS OU MAIS DA DATA DE ABERTURA&nbsp;";
					}
				}
			} else if (in_array($login_fabrica,array(11,51,81))){ //HD 52453
				echo "&nbsp;OS PENDENTES A MAIS DE 30 DIAS&nbsp;";
			}
			if($login_fabrica <> 11){
				echo "<br>";
				if($sistema_lingua == "ES") {
					echo "";
				}else{
					echo "fffPerigo de PROCON conforme artigo 18 do C.D.C.";
				}
			}
			echo "
					</th>
				</tr>
				<tr class='titulo_coluna'>
					<th>OS</th>
					<th>ABERTURA</th>
					<th>" . strtoupper(traduz("produto")) . "</th>
				</tr>
				</thead>
				<tbody>
			";
			for ($a = 0 ; $a < pg_num_rows($res) ; $a++) {
				$os               = trim(pg_fetch_result($res,$a,os));
				$sua_os           = trim(pg_fetch_result($res,$a,sua_os));
				$tipo_atendimento = trim(pg_fetch_result($res,$a,tipo_atendimento));
				$abertura         = trim(pg_fetch_result($res,$a,abertura));
				$produto          = trim(pg_fetch_result($res,$a,produto));
				$referencia       = trim(pg_fetch_result($res,$a,referencia));
				$descricao        = trim(pg_fetch_result($res,$a,descricao));
				$codigo_posto     = trim(pg_fetch_result($res,$a,codigo_posto)); #HD 252858

				//--=== Tradução para outras linguas ============================= Raphael HD:1212
				$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = '$sistema_lingua'";

				$res_idioma = @pg_query($con,$sql_idioma);
				if (@pg_num_rows($res_idioma) >0) {
					$descricao  = trim(@pg_fetch_result($res_idioma,0,descricao));
				}
				//--=== Tradução para outras linguas ================================================

				$cor = ($a % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

				$produto_completo = $referencia . " - " . $descricao;

				echo "
					<tr>
					<td class='tal'>
				";
				if ($login_fabrica == 3) {
					echo "<a href='os_press.php?os=$os' target='_new'>";
				}else{
					if ($login_fabrica == 1){
						echo "<a href='os_press.php?os=$os'>";
					}else{
						if ($login_fabrica == 91) {
							echo "<a href='os_consulta_lite.php?sua_os=$sua_os&btn_acao=Pesquisar'>";
						} else {
							echo "<a href='os_item.php?os=$os'>";
						}
					}
				}
				if($login_fabrica==1)echo $codigo_posto;
				if(strlen($sua_os)==0)echo $os;
				else                  echo "$sua_os";
				echo "
					</a>
					</td>
					<td class='tal'>" . $abertura . "</td>
				";
				if ($sistema_lingua=='ES') echo "<td class='tal' title='Referencia: $referencia\nDescripción: $descricao' style='cursor:help;'>" . substr($produto_completo,0,30) . "</td>";
				else echo "<td class='tal' title='Referência: $referencia\nDescrição: $descricao' style='cursor:help;'>" . substr($produto_completo,0,30) . "</td>";

				echo "</tr>";
			}
			if($login_fabrica <> 24 and $login_fabrica <> 104){
				echo "
					<tr class='titulo_coluna'>
						<th colspan='3'><a href= \"javascript: lista_risco(15)\" style='color: #fff;'>LISTAR TODAS</a></th>
					</tr>
				";
			}
			echo "
				</tbody>
				</table>";
			echo "<br>";
		}
	}

	if (!in_array($login_fabrica, array(11,24,30,86,91,104))) {

		##### OS QUE EXCEDERAM O PRAZO LIMITE DE 30 DIAS PARA FECHAMENTO #####
		if($login_fabrica == 15) {
			$sql_cond = " AND tbl_os.fabrica <> 15 ";
		}

		if(in_array($login_fabrica,array(151))){
			$left_join_os_campo_extra = " LEFT JOIN tbl_os_campo_extra ON tbl_os.os = tbl_os_campo_extra.os ";
			$cond_bloqueada = " AND tbl_os_campo_extra.os_bloqueada IS NOT TRUE ";
		}

		$sql =	"SELECT tbl_os.os                                                  ,
				tbl_os.sua_os                                              ,
				LPAD(tbl_os.sua_os,10,'0')                   AS os_ordem   ,
				TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura   ,
				tbl_produto.produto                                        ,
				tbl_produto.referencia                                     ,
				tbl_produto.descricao                                      ,
				tbl_produto.voltagem,
				tbl_posto_fabrica.codigo_posto
			FROM tbl_os
			JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i=$login_fabrica
			LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			$left_join_os_campo_extra
			WHERE tbl_os.fabrica = $login_fabrica
				AND   tbl_os.posto   = $login_posto
				AND   (tbl_os.data_abertura + INTERVAL '30 days') <= current_date
				AND   tbl_os.data_fechamento IS NULL
				and   cancelada is not true
				$extraCond
				$sql_cond
				$cond_data
				$cond_bloqueada
				AND  tbl_os.excluida is FALSE LIMIT 3";
		$res = pg_query($con,$sql);

		$contador_res = pg_num_rows($res);

		if ($contador_res > 0) {
			echo "
				<table class='table_tc table-bordered  table-hover table-large'>
					<thead>
						<tr class='titulo_coluna'>
							<th colspan='3' >
			";
			if($sistema_lingua == "ES") echo "OS QUE EXCEDERAN EL PLAZO LIMITE DE 30 DÍAS PARA CIERRE";
			else                        echo "OS QUE EXCEDERAM O PRAZO LIMITE DE 30 DIAS PARA FECHAMENTO <br>";
			if($sistema_lingua == "ES") echo "Clique em la OS para informar el motivo";
			else                        echo "Clique na OS para informar o Motivo";
			echo "
				</th>
				</tr>
				<tr class='titulo_coluna'>
					<th>OS</th>
					<th>Abertura</th>
					<th>" . strtoupper(traduz("produto")) . "</th>
				</tr>
				</thead>
				<tbody>
			";
			for ($a = 0 ; $a < $contador_res; $a++) {
				$os               = trim(pg_fetch_result($res,$a,os));
				$sua_os           = trim(pg_fetch_result($res,$a,sua_os));
				$abertura         = trim(pg_fetch_result($res,$a,abertura));
				$produto          = trim(pg_fetch_result($res,$a,produto));
				$referencia       = trim(pg_fetch_result($res,$a,referencia));
				$descricao        = trim(pg_fetch_result($res,$a,descricao));
				$voltagem         = trim(pg_fetch_result($res,$a,voltagem));
				$codigo_posto   = trim(pg_fetch_result($res,$a,codigo_posto));
				//--=== Tradução para outras linguas ============================= Raphael HD:1212
				$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = '$sistema_lingua'";

				$res_idioma = @pg_query($con,$sql_idioma);
				if (@pg_num_rows($res_idioma) >0) {
					$descricao  = trim(@pg_fetch_result($res_idioma,0,descricao));
				}
				//--=== Tradução para outras linguas ================================================

				$produto_completo = $referencia . " - " . $descricao . " - " . $voltagem;

				$cor = ($a % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

				echo "
					<tr>
					<td class='tac'><a href='os_motivo_atraso.php?os=$os' target='_blank'>
				";
				if($login_fabrica==1)echo $codigo_posto;
				if(strlen($sua_os)==0)echo $os;
				else                  echo $sua_os;
				"</a></td>";
				echo "<td class='tac'>" . $abertura . "</td>";
				if ($sistema_lingua=='ES') echo "<td title='Referencia: $referencia\nDescripción: $descricao\nVoltaje: $voltagem' style='cursor:help;'>" . substr($produto_completo,0,30) . "</td>";
				else echo "<td class='tal' title='Referência: $referencia\nDescrição: $descricao\nVoltagem: $voltagem' style='cursor:help;'>" . substr($produto_completo,0,30) . "</td>";
				echo "</tr>";
			}
			if($login_fabrica <> 24){
				echo "<tr class='titulo_coluna'>";
					echo "<th colspan='3'><a style='color: #fff;' href= \"javascript: lista_risco(30)\">LISTAR TODAS</a></th>";
				echo "</tr>";
			}
			echo "</tbody></table>";
			echo "<br>";
		}
		##### OS QUE EXCEDERAM O PRAZO LIMITE DE 30 DIAS PARA FECHAMENTO #####
	}

	//--==== OS RECUSADAS=============================================================--\\


	if($login_fabrica == 1){

		$sql = "SELECT tbl_posto_fabrica.codigo_posto           ,
			tbl_os.os                                ,
			tbl_os.sua_os                            ,
			tbl_os.tipo_atendimento                  ,
			TO_CHAR(tbl_os.data_digitacao,'DD/MM/YY') AS data_digitacao,
			TO_CHAR(tbl_os.data_abertura,'DD/MM/YY') AS data_abertura ,
			(SELECT status_os      FROM tbl_os_status WHERE tbl_os_status.fabrica_status=$login_fabrica AND tbl_os_status.os = tbl_os.os AND tbl_os_status.status_os IN (13,14,91) ORDER BY tbl_os_status.data DESC LIMIT 1) AS status_os ,
			(SELECT observacao     FROM tbl_os_status WHERE tbl_os_status.fabrica_status=$login_fabrica AND tbl_os_status.os = tbl_os.os AND tbl_os_status.status_os IN (13,14,91) ORDER BY tbl_os_status.data DESC LIMIT 1) AS observacao ,
			(SELECT tbl_status_os.descricao FROM tbl_os_status JOIN tbl_status_os USING (status_os) WHERE tbl_os_status.fabrica_status=$login_fabrica AND tbl_os_status.os = tbl_os.os AND tbl_os_status.status_os IN (13,14,91) ORDER BY tbl_os_status.data DESC LIMIT 1) AS status_descricao,
			(SELECT tbl_os_status.status_os_troca FROM tbl_os_status JOIN tbl_status_os USING (status_os) WHERE tbl_os_status.fabrica_status=$login_fabrica AND tbl_os_status.os = tbl_os.os AND tbl_os_status.status_os IN (13,14,91) ORDER BY tbl_os_status.data DESC LIMIT 1) AS status_os_troca,
			(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.fabrica_status=$login_fabrica AND tbl_os_status.os = tbl_os.os AND tbl_os_status.status_os IN (19) ORDER BY tbl_os_status.data DESC LIMIT 1) AS troca_aprovada,
			tbl_auditoria_os.reprovada AS recusada,
			tbl_auditoria_os.justificativa,

	(SELECT observacao FROM tbl_os_status WHERE tbl_os_status.fabrica_status=$login_fabrica AND tbl_os_status.os = tbl_os.os AND tbl_os_status.status_os IN (13,14,91) AND observacao <> 'Extrato Acumulado Geral' ORDER BY tbl_os_status.data DESC LIMIT 1) as obs_status

		INTO TEMP tmp_recusadas
		FROM tbl_os
		LEFT JOIN tbl_auditoria_os ON tbl_auditoria_os.os = tbl_os.os
		JOIN tbl_os_extra ON tbl_os.os=tbl_os_extra.os AND tbl_os_extra.i_fabrica=tbl_os.fabrica
		JOIN tbl_posto USING (posto)
		JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_os.finalizada IS NULL
			AND   tbl_os.data_fechamento IS NULL
			AND   tbl_os_extra.extrato IS NULL
			AND   tbl_os.posto = $login_posto
			AND   tbl_os.fabrica = $login_fabrica
			AND   tbl_os.excluida IS NOT TRUE
			$cond_data ;

			Select * from tmp_recusadas WHERE  recusada is not null OR length(obs_status) > 0 ";
	}else{
	$sql =	"SELECT tbl_posto_fabrica.codigo_posto           ,
			tbl_os.os                                ,
			tbl_os.sua_os                            ,
			tbl_os.tipo_atendimento                  ,
			TO_CHAR(tbl_os.data_digitacao,'DD/MM/YY') AS data_digitacao,
			TO_CHAR(tbl_os.data_abertura,'DD/MM/YY') AS data_abertura ,
			(SELECT status_os      FROM tbl_os_status WHERE tbl_os_status.fabrica_status=$login_fabrica AND tbl_os_status.os = tbl_os.os AND tbl_os_status.status_os IN (13,14,91) ORDER BY tbl_os_status.data DESC LIMIT 1) AS status_os ,
			(SELECT observacao     FROM tbl_os_status WHERE tbl_os_status.fabrica_status=$login_fabrica AND tbl_os_status.os = tbl_os.os AND tbl_os_status.status_os IN (13,14,91) ORDER BY tbl_os_status.data DESC LIMIT 1) AS observacao ,
			(SELECT tbl_status_os.descricao FROM tbl_os_status JOIN tbl_status_os USING (status_os) WHERE tbl_os_status.fabrica_status=$login_fabrica AND tbl_os_status.os = tbl_os.os AND tbl_os_status.status_os IN (13,14,91) ORDER BY tbl_os_status.data DESC LIMIT 1) AS status_descricao,
			(SELECT tbl_os_status.status_os_troca FROM tbl_os_status JOIN tbl_status_os USING (status_os) WHERE tbl_os_status.fabrica_status=$login_fabrica AND tbl_os_status.os = tbl_os.os AND tbl_os_status.status_os IN (13,14,91) ORDER BY tbl_os_status.data DESC LIMIT 1) AS status_os_troca,
			(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.fabrica_status=$login_fabrica AND tbl_os_status.os = tbl_os.os AND tbl_os_status.status_os IN (19) ORDER BY tbl_os_status.data DESC LIMIT 1) AS troca_aprovada
		FROM tbl_os
		JOIN tbl_os_extra ON tbl_os.os=tbl_os_extra.os AND tbl_os_extra.i_fabrica=tbl_os.fabrica
		JOIN tbl_posto USING (posto)
		JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_os.finalizada IS NULL
			AND   tbl_os.data_fechamento IS NULL
			AND   tbl_os_extra.extrato IS NULL
			AND   tbl_os.posto = $login_posto
			AND   tbl_os.fabrica = $login_fabrica
			AND   tbl_os.excluida IS NOT TRUE
			$cond_data
			".(($login_fabrica == 91) ? " AND tbl_os.os IN (
                                        SELECT interv_reinc.os
                                        FROM (
                                                SELECT
                                                ultima_reinc.os,
                                                (
                                                        SELECT status_os
                                                        FROM tbl_os_status
                                                        WHERE tbl_os_status.os = ultima_reinc.os
                                                        AND   tbl_os_status.fabrica_status = $login_fabrica
                                                        AND   status_os IN (179, 13, 19)
                                                        ORDER BY os_status DESC LIMIT 1
                                                ) AS ultimo_reinc_status

                                                FROM (
                                                        SELECT DISTINCT os
                                                        FROM tbl_os_status
                                                        WHERE tbl_os_status.fabrica_status = $login_fabrica
                                                        AND status_os IN (179, 13, 19)
                                                ) ultima_reinc
                                        ) interv_reinc
                                        WHERE interv_reinc.ultimo_reinc_status IN (13)
                        ) " : "")."
			AND length ((SELECT observacao FROM tbl_os_status WHERE tbl_os_status.fabrica_status=$login_fabrica AND tbl_os_status.os = tbl_os.os AND tbl_os_status.status_os IN (13,14,91) AND observacao <> 'Extrato Acumulado Geral' ORDER BY tbl_os_status.data DESC LIMIT 1)) > 0 ;
				";
	}
	$res = pg_query($con,$sql);
	$contador_res = pg_num_rows($res);

	if ($contador_res > 0) {

		unset($arr_sua_os);
		$extrato = '';
		$pendencia_doc = array();

		$j=0;
	
		for ($i = 0 ; $i < $contador_res; $i++) {
			$codigo_posto   = trim(pg_fetch_result($res,$i,codigo_posto));
			$os             = trim(pg_fetch_result($res,$i,os));
			$sua_os         = trim(pg_fetch_result($res,$i,sua_os));
			if ($login_fabrica == 1) {
				$sql_status = "SELECT tbl_auditoria_os.reprovada 
							   FROM tbl_os 
							   JOIN tbl_auditoria_os ON tbl_os.os = tbl_auditoria_os.os 
							   WHERE tbl_os.os = $os AND tbl_os.fabrica = $login_fabrica LIMIT 1";
				$res_status = pg_query($con, $sql_status);
				$xreprovada = pg_fetch_result($res_status, 0, reprovada);
				if (!empty($xreprovada)) {
					if (isset($arr_sua_os)) {
						if (in_array($os,$arr_sua_os)) {
							continue;
						} else {
							$arr_sua_os[] = $os;
						}
					} else {
						$arr_sua_os[] = $os;
					}
				} else {
					$sql_os_status = "SELECT tbl_status_os.descricao 		
									  FROM tbl_os_status 
									  JOIN tbl_status_os USING (status_os) 	
									  WHERE tbl_os_status.fabrica_status = $login_fabrica 
									  AND tbl_os_status.os = $os 
									  AND tbl_os_status.status_os IN (13,14,91) 
									  ORDER BY tbl_os_status.data DESC LIMIT 1";
					$res_os_status = pg_query($con, $sql_os_status);
					if (pg_num_rows($res_os_status) > 0) {
						$arr_sua_os[] = $os;	
					} else {
						continue;
					}
				}
			}
			$tipo_atendimento = trim(pg_fetch_result($res,$i,tipo_atendimento));
			$data_digitacao = trim(pg_fetch_result($res,$i,data_digitacao));
			$data_abertura  = trim(pg_fetch_result($res,$i,data_abertura));
			$observacao     = trim(pg_fetch_result($res,$i,observacao));
			$status_os      = trim(pg_fetch_result($res,$i,status_os));
			$status_os_troca = trim(pg_fetch_result($res,$i,status_os_troca));
			$troca_aprovada = trim(pg_fetch_result($res,$i,troca_aprovada));
			if($login_fabrica == 1){
				$recusada 		= trim(pg_fetch_result($res,$i,recusada));
				$justificativa 		= trim(pg_fetch_result($res,$i,justificativa));
			}

			#Se tiver status 19, a OS foi recusada mas depois APROVADA, entao nao deve ser mostrado
			#HD 13013
			if ($troca_aprovada=="19" AND $status_os_troca == 't' AND ($status_os <> 91 OR $status_os != '')) {
				continue;
			}

			if(strlen($recusada) == 0 ){
				$sql2 = "SELECT status_os FROM tbl_os_troca WHERE os = $os";
				$res2 = pg_query($con,$sql2);
				if(pg_num_rows($res2)>0){
					if(strlen(trim(pg_fetch_result($res2,0,0)))==0) continue;
				}
			}

			if($j==0){
				echo "
					<table class='table_tc table-bordered  table-hover table-large' style='text-align:center;'>
						<thead>
							<tr class='titulo_coluna'>
								<th colspan='5' >
				";
				if($login_fabrica==20){
					if($sistema_lingua == "ES") echo "RELACIÓN DE OS ACUMULADAS";
					else                        echo "RELAÇÃO DE OSs ACUMULADAS";
				}
				else                   echo "RELAÇÃO DE OSs RECUSADAS";
				echo "
					</th>
					</tr>
					<tr class='titulo_coluna'>
						<th>OS</th>
						<th>Abertura</th>
						<th>Status</th>
						<th>" . strtoupper(traduz("Observação")) . "</th>
				";
				if($login_fabrica == 1) echo "<th>Alterar a OS</th>";
				echo "
					</tr>
					</thead>
					<tbody>
				";
			}

			if($status_os == 91){

				array_push($pendencia_doc,array($codigo_posto    ,
												$os              ,
												$sua_os          ,
												$data_abertura   ,
												$tipo_atendimento,
												$observacao
												));

			}else{

				echo "<tr>";
				echo "<td>";
				if ($login_fabrica == 1){
					echo "<a href='os_press.php?os=$os'>"; // HD-2087594
				}else{
					if ($login_fabrica == 91) {
						echo "<a href='os_consulta_lite.php?sua_os=$sua_os&btn_acao=Pesquisar'>";
					} else {
						echo "<a href='os_item.php?os=$os'>";
					}
				}
				if($login_fabrica==1)
					echo $codigo_posto.$sua_os;
				else
					echo $sua_os;

				echo "</a></td>";
				echo "<td class='tac'>" . $data_abertura . "</td>";
				echo "<td class='tac'>";
				if($status_os==13) {
					if ($status_os_troca=='t'){
						echo "Troca Recusada";
					}else{
						if ($sistema_lingua=='ES')	echo "Rechazada";
						else						echo "Recusada";
					}
				} elseif($status_os==14)echo "Retirada";

				if(strlen($recusada)>0){
					echo "Reprovada da Auditoria";
					if($login_fabrica == 1){
						if(strlen(trim($observacao))==0){
							$observacao = $justificativa;
						}
					}
				}
				echo "</td>";

				if ($sistema_lingua=='ES') {
					echo "<td><b>Obs. Planta: </b><br><a href=\"os_cadastro.php?os=$os\" target='_blank'>" . $observacao . "</a></td>";
				}else{
					if ($login_fabrica == 1 ) {
					 	if ($status_os_troca=='t') {
							echo "<td><b>Obs. Fábrica: </b><br>$observacao </td>";
							echo "<td>";
							echo "<a class='btn'  href='os_cadastro_troca.php?os=$os&alterar=true' id='troca_aprovacao_$i'>Alterar a OS</a>";
							echo "</td>";
						} elseif ($tipo_atendimento=="17" OR $tipo_atendimento=='18') {
							echo "<td><b>Obs. Fábrica: </b><br>$observacao </td>";
							echo "<td>";
							echo "<a class='btn' href='os_cadastro_troca.php?os=$os'>Alterar a OS</a>";
							echo "</td>";
						} elseif ($tipo_atendimento == 334) {
							echo "<td><b>Obs. Fábrica: </b><br>$observacao </td>";
							echo "<td>";
							echo "<a class='btn' href='os_item.php?os=$os&alterar=true'>Alterar a OS</a>";
							echo "</td>";
						} else {
							echo "<td><b>Obs. Fábrica: </b><br>$observacao </td>";
							echo "<td>";
							echo "<a class='btn' href='os_item.php?os=$os'>Alterar a OS</a>";
							echo "</td>";
						}
					}else{
						echo "<td><b>Obs. Fábrica: </b><br><a href=\"os_cadastro.php?os=$os\" target='_blank'>" . $observacao . "</a></td>";
					}
				}
				echo "</tr>";
			}
			$j++;
		}
		if ($j>0){
			echo "</table>";
			echo "<br>";
		}
	}

	if(sizeof($pendencia_doc) > 0){
		echo "<table width='500' border='1' class='tabela'>";
		echo "<tr class='titulo_tabela'>";
		echo "<td colspan='4' height='25'>";
			echo "RELAÇÃO DE OSs COM PENDÊNCIA DE DOCUMENTO";
		echo "</td>";
		echo "</tr>";
		echo "<tr class='titulo_coluna'>";
		echo "<td>OS</td>";
		echo "<td>Abertura</td>";
		echo "<td>Status</td>";
		echo "<td>Observação</td>";
		echo "</tr>";

		for($i=0;$i<sizeof($pendencia_doc);$i++){
			$codigo_posto          = $pendencia_doc[$i][0];
			$os                    = $pendencia_doc[$i][1];
			$sua_os                = $pendencia_doc[$i][2];
			$data_abertura         = $pendencia_doc[$i][3];
			$tipo_atendimento      = $pendencia_doc[$i][4];
			$observacao            = $pendencia_doc[$i][5];

			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

			echo "<tr bgcolor='$cor' >";
			echo "<td>";
			if ($login_fabrica == 1){
				echo "<a href='os_press.php?os=$os'>";
			}else{
				if ($login_fabrica == 91) {
					echo "<a href='os_consulta_lite.php?sua_os=$sua_os&btn_acao=Pesquisar'>";
				} else {
					echo "<a href='os_item.php?os=$os'>";
				}
			}
			if($login_fabrica==1)
				echo $codigo_posto.$sua_os;
			else
				echo $sua_os;

			echo "</a></td>";
			echo "<td align='center'>" . $data_abertura . "</td>";
			echo "<td align='center'>";
				echo "Pendência Doc.";
			echo "</td>";

			if ($login_fabrica == 1 AND ($tipo_atendimento=="17" OR $tipo_atendimento=='18')){
				echo "<td><b>Obs. Fábrica: </b><br><a href=\"os_cadastro_troca.php?os=$os\" target='_blank'>" . $observacao . "</a></td>";
			}else{
				echo "<td><b>Obs. Fábrica: </b><br><a href=\"os_cadastro.php?os=$os\" target='_blank'>" . $observacao . "</a></td>";
			}
			echo "</tr>";
		}
		echo "</table>";
	}

    /**
     * - OS com LGR Gerados
     * via API (MONDIAL)
     */
    if ($login_fabrica == 151) {
        $sql = "
            SELECT  tbl_os.os,
                    tbl_os.sua_os,
                    TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
                    tbl_peca.referencia                                     ,
                    tbl_peca.descricao
            FROM    tbl_os
            JOIN    tbl_os_produto          ON  tbl_os.os                   = tbl_os_produto.os
            JOIN    tbl_os_item             ON  tbl_os_produto.os_produto   = tbl_os_item.os_produto
            JOIN    tbl_os_extra            ON  tbl_os_extra.os             = tbl_os.os
            JOIN    tbl_peca                ON  tbl_peca.peca               = tbl_os_item.peca
                                            AND tbl_os_extra.extrato        IS NULL
            JOIN    tbl_os_campo_extra      ON  tbl_os_campo_extra.os       = tbl_os.os
            JOIN    tbl_faturamento_item    ON  (
                                                tbl_faturamento_item.pedido     = tbl_os_item.pedido
                                            OR  tbl_faturamento_item.os_item    = tbl_os_item.os_item
                                            )
                                            AND tbl_faturamento_item.peca   = tbl_os_item.peca
            JOIN    tbl_faturamento         ON  tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                                            AND tbl_faturamento.fabrica     = $login_fabrica
       LEFT JOIN    tbl_faturamento_item b  ON  b.os                        = tbl_os.os
                                            AND tbl_os.fabrica              = $login_fabrica
                                            AND tbl_os_item.peca            = b.peca
		 and b.os_item isnull
       LEFT JOIN    tbl_faturamento a       ON  a.faturamento               = b.faturamento
                                            AND a.fabrica                   = $login_fabrica
                                            AND a.distribuidor              = tbl_os.posto
            JOIN    tbl_posto               ON  tbl_posto.posto             = tbl_os.posto
                                            AND tbl_posto.posto             = $login_posto
            JOIN    tbl_posto_fabrica       ON  tbl_posto_fabrica.posto     = tbl_posto.posto
                                            AND tbl_posto_fabrica.fabrica   = $login_fabrica
            JOIN    tbl_produto             ON  tbl_produto.produto         = tbl_os_produto.produto
                                            AND tbl_produto.fabrica_i       = $login_fabrica
		 WHERE tbl_os_campo_extra.os_bloqueada IS NOT TRUE
		 AND tbl_os_item.peca_obrigatoria IS TRUE
		 AND b.faturamento_item IS NULL
		 AND JSON_FIELD('bloqueio',tbl_os_item.parametros_adicionais) IS NOT NULL
 		 AND tbl_os.posto = $login_posto
		 AND tbl_os.fabrica = $login_fabrica
		 AND tbl_os.finalizada isnull
		 AND (
		 tbl_os_item.qtde > COALESCE(b.qtde_inspecionada,0)
		 )
        ";
        $res = pg_query($con,$sql);

        if (pg_num_rows($res) > 0) {
?>

            <table class='table_tc table-bordered  table-hover table-large'>
                <thead>
                    <tr class='titulo_coluna'>
                        <th colspan='3'>OS COM DEVOLUÇÃO OBRIGATÓRIA PENDENTE</th>
                    </tr>
                    <tr class='titulo_coluna'>
                        <th>OS</th>
                        <th>Abertura</th>
                        <th>Peças</th>
                    </tr>
                </thead>
                <tbody>
<?php
            while ($result = pg_fetch_object($res)) {
?>
                    <tr>
                        <td class="tac"><a href='os_press.php?os=<?=$result->os?>'><?=$result->sua_os?></a></td>
                        <td class="tac"><?=$result->data_abertura?></td>
                        <td><?=$result->referencia." - ".$result->descricao?></td>
                    </tr>
<?php
            }
?>
                </tbody>
            </table>
<?php
        }
    }

//--==== OS SEDEX RECUSADAS=============================================================--\\
	if($login_fabrica == 1){
		$sql = "SELECT  tbl_os_sedex.os_sedex      ,
				tbl_os_sedex.sua_os_destino,
				tbl_os_sedex.data          ,
				tbl_os_status.observacao
			FROM tbl_os_sedex
			JOIN tbl_os_status ON tbl_os_sedex.os_sedex = tbl_os_status.os_sedex AND tbl_os_status.fabrica_status=$login_fabrica
			JOIN tbl_os ON tbl_os.sua_os = tbl_os_sedex.sua_os_destino
			AND tbl_os.fabrica = $login_fabrica AND tbl_os.posto = $login_posto
			AND tbl_os.excluida IS NOT TRUE
			WHERE tbl_os_sedex.posto_destino = $login_posto
			AND   tbl_os_sedex.fabrica = $login_fabrica
			/*	AND   tbl_os_sedex.data_digitacao > CURRENT_DATE - interval '3 months' */
			AND   tbl_os_sedex.finalizada ISNULL;
			";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) > 0) {

			$extrato = '';
			for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
				$os_sedex       = trim(pg_fetch_result($res,$i,os_sedex));
				$data           = trim(pg_fetch_result($res,$i,data));
				$sua_os_destino = trim(pg_fetch_result($res,$i,sua_os_destino));
				$observacao     = trim(pg_fetch_result($res,$i,observacao));
				//2006-01-01
				$data = substr($data,8,2) ."/". substr($data,5,2) ."/". substr($data,0,4);

				if($i==0){
					echo "<table class='tabela'>";
					echo "<tr class='titulo_tabela'>";
					echo "<td colspan='4'>RELAÇÃO DE OSs SEDEX RECUSADAS</td>";
					echo "</tr>";
					echo "<tr class='titulo_coluna'>";
					echo "<td>OS</td>";
					echo "<td>Abertura</td>";
					echo "<td>Status</td>";
					echo "<td>Observação</td>";
					echo "</tr>";
				}
				$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

				echo "<tr bgcolor='$cor' >";
				echo "<td><a href='sedex_cadastro_complemento.php?os_sedex=$os_sedex'>";
				echo $sua_os_destino;
				echo "</a></td>";
				echo "<td align='center'>" . $data . "</td>";
				echo "<td align='center'>Recusada</td>";
				echo "<td><b>Obs. Fábrica: </b><br><a href='sedex_cadastro_complemento.php?os_sedex=$os_sedex' target='_blank'>" . $observacao . "</a></td>";

				echo "</tr>";

			}
			echo "</table>";
			echo "<br>";
		}
	}
}

if ($login_fabrica == 138) {
    /*
     * - Mostra OS Abertas e
     * Faturadas há mais de 10 dias.
     */
    $sqlOsFaturada = "
        SELECT  DISTINCT
                tbl_os.os,
                TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
                tbl_faturamento.nota_fiscal,
                tbl_faturamento.emissao,
                TO_CHAR(tbl_faturamento.emissao,'DD/MM/YYYY') AS data_emissao
        FROM    tbl_os
        JOIN    tbl_os_produto          USING(os)
        JOIN    tbl_os_item             USING(os_produto)
        JOIN    tbl_pedido_item         USING(pedido_item)
        JOIN    tbl_faturamento_item    USING(pedido_item)
        JOIN    tbl_faturamento         USING(faturamento)
        WHERE   tbl_os.fabrica = $login_fabrica
        AND     tbl_os.posto    = $login_posto
        AND     tbl_faturamento.emissao + INTERVAL '10 days' < CURRENT_DATE
        AND     tbl_os.finalizada IS NULL
  ORDER BY      tbl_faturamento.emissao DESC
        LIMIT   10
    ";
//     echo nl2br($sqlOsFaturada);
    $resOsFaturada = pg_query($con,$sqlOsFaturada);
?>
    <table class='table_tc table-bordered  table-hover table-large'>
        <thead>
            <tr class='titulo_coluna'>
                <th colspan='4'>RELAÇÃO DE OSs ABERTAS E FATURADAS HÁ MAIS DE 10 DIAS</th>
            </tr>
            <tr class='titulo_coluna'>
                <th>OS</th>
                <th>ABERTURA</th>
                <th>EMISSÃO</th>
                <th>NOTA FISCAL</th>
            </tr>
        </thead>
        <tbody>
<?php
    $i = 0;
    while ($resultado = pg_fetch_object($resOsFaturada)) {
        $cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
?>
            <tr bgcolor="<?=$cor?>">
                <td align='center'><a href='os_press.php?os=<?=$resultado->os?>'><?=$resultado->os?></a></td>
                <td align='center'><?=$resultado->data_abertura?></td>
                <td align='center'><?=$resultado->data_emissao?></td>
                <td align='center'><?=$resultado->nota_fiscal?></td>
            </tr>
<?php
        $i++;
    }
?>
        </tbody>
    </table>
<?php
}

if (in_array($login_fabrica, array(1,2,15,24,30,35,86,91,104))) {
	$idioma = strtoupper(substr($cook_idioma, 0, 2));
	$sql_2 = "	SELECT distinct
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YY') as data_abertura, 
					tbl_produto.referencia,
					tbl_produto.produto,
					COALESCE(TPI.descricao, tbl_produto.descricao) AS descricao,
					tbl_os_interacao.os, tbl_os.posto,
					tbl_os.sua_os,
					(SELECT CASE WHEN admin notnull
					             and current_date > data + interval '3 days' 
					             then 'sim'
					             else 'nao'
					        end as bloqueia
					    from tbl_os_interacao h
					    where h.os = tbl_os.os
					    order by data desc limit 1
					) as aguardando_interacao
			FROM tbl_os_interacao
			JOIN tbl_os USING(os)
			JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto AND tbl_produto.fabrica_i = $login_fabrica
			LEFT JOIN tbl_produto_idioma AS TPI
			       ON TPI.idioma = '$idioma'
			      AND TPI.produto = tbl_produto.produto
			WHERE tbl_os_interacao.fabrica = $login_fabrica
			AND tbl_os.fabrica = $login_fabrica
			AND tbl_os.posto = $login_posto
			AND tbl_os_interacao.admin IS NOT NULL
			AND tbl_os_interacao.interno is false
			AND tbl_os_interacao.exigir_resposta is true 
			AND tbl_os.excluida is not true
			AND tbl_os.finalizada IS NULL";

	if ( in_array($login_fabrica, [30]) ) {
		$sql_2 = "
	            SELECT DISTINCT ON (tbl_os.os) tbl_os.os, tbl_os.sua_os, TO_CHAR(tbl_os_interacao.data, 'DD/MM/YYYY HH24:MI') AS data_abertura, (select admin from tbl_os_interacao where os = tbl_os.os and fabrica = $login_fabrica and admin is not null order by data desc limit 1) AS admin
	            FROM tbl_os_interacao
	            INNER JOIN tbl_os ON tbl_os.os = tbl_os_interacao.os AND tbl_os.fabrica = {$login_fabrica} AND tbl_os.posto = {$login_posto}
	    WHERE tbl_os_interacao.interno IS NOT TRUE
		    AND tbl_os.finalizada IS NULL
		    AND tbl_os_interacao.posto = $login_posto
	            AND tbl_os_interacao.confirmacao_leitura IS NULL
	            AND tbl_os_interacao.admin IS NOT NULL
	            AND tbl_os_interacao.data > CURRENT_TIMESTAMP - INTERVAL '1 YEAR'
	            AND tbl_os.excluida is not true
	            AND tbl_os.cancelada is not true
	            $cond_tecnico
	            ORDER BY tbl_os.os, tbl_os_interacao.data DESC ";
	}
	
	$res_2 = pg_query($con, $sql_2);

	if (pg_num_rows($res_2) >0) {

		$dados = pg_fetch_all($res_2);
		$ab = traduz('abertura');
		$prd = traduz('produto');
		$act = traduz('acoes');
		foreach ($dados as $i => $row) {
			$tabela[$i] = array(
				'OS' => $row['sua_os'],
				$ab  => $row['data_abertura'],
				$prd => str_words($row['referencia'] . ' - ' . $row['descricao']),
				$act => '',
			);
			$os = $row['os'];
			// title do produto.
			if ($sistema_lingua=='ES') echo "<td nowrap><acronym title='Referencia: $referencia\nDescripción: $descricao' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";
			else echo "<td nowrap><acronym title='Referência: $referencia\nDescrição: $descricao' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";
			if($row['aguardando_interacao'] == 'nao') {
				$nao_tem++;
				continue;
			}

			if (isFabrica(24, 30)) {
				$tabela[$i][$act] = "<a href='os_press.php?os=$os' target='_new'>Interagir</a>";
			}
		}

		if (!isFabrica(24, 30, 104) and $nao_tem < $i) {
			$tfoot = "<div><button type='button' class='btn' onclick='lista_risco(25)'>LISTAR TODAS</button></div>";
		}

		if($login_fabrica == 91){
			echo "
				<table class='table_tc table-bordered  table-hover table-large'>
					<thead>
						<tr class='titulo_coluna'>
							<th colspan='3' >
								&nbsp;O.S's COM INTERAÇÃO A MAIS DE 3 DIAS AGUARDANDO RESPOSTA DO POSTO. &nbsp;
								<br>Perigo de PROCON conforme artigo 18 do C.D.C.";
		} else {
			if ($login_fabrica == 24) {
				$caption = $cook_idioma == 'pt-br'
				? "<table class='table_tc table-bordered  table-hover table-large'>
						<thead>
							<tr class='titulo_coluna'>
								<th colspan='4' >
									&nbsp;O.S's COM INTERAÇÃO A MAIS DE 3 DIAS AGUARDANDO RESPOSTA DO POSTO. &nbsp;
									<br>Perigo de PROCON conforme artigo 18 do C.D.C.</th><tr>"
				: '';
			} else {
				$caption = $cook_idioma == 'pt-br'
					? "&nbsp;O.S's COM INTERAÇÃO A MAIS DE 3 DIAS AGUARDANDO RESPOSTA DO POSTO. &nbsp;".
					  "<br>Perigo de PROCON conforme artigo 18 do C.D.C."
					: '';
			}
		}


		if($login_fabrica == 91){
			echo $caption, "</th></tr>";
		}

        global $tableAttrs;
        $tableAttrs = array(
            'tableAttrs' => 'class="table_tc table-bordered table-hover table-large"',
            'captionAttrs' => 'class="titulo_coluna"',
            'headerAttrs' => 'class="titulo_coluna"',
        );

        if (in_array($login_fabrica, array(30))) {
        	?>
			<table id="interacoes_pendentes" class="table_tc" style="margin: 0 auto; width: 100%;">
				<thead>
				<tr>
					<th style="background-color: #DD0010; color: #FFFFFF; text-align: center;" colspan="2">OSs com interações pendentes</th>
				</tr>
				<tr class="titulo_coluna">
					<th style="text-align: center;">OS</th>
					<th style="text-align: center;">Data Interação</th>
				</tr>
				</thead>
				<tbody style="max-height: 50px; overflow-y: auto;">
					<?php foreach ($dados as $dados) { ?>
					<tr>
						<td style="text-align: center;"><a href="os_press.php?os=<?=$row['os']?>" target="_blank"><?=$dados['os']?></a></td>
						<td style="text-align: center;"><?=$dados['data_abertura']?></td>
					</tr>
					<?php } ?>
				</tbody>
			</table>        	
        	<?php
        } else {
        	echo array2table($tabela, $caption);
        }

	}

	if ($login_fabrica == 24) {
		$cond_campo_extra = " JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os AND tbl_os_campo_extra.fabrica = $login_fabrica AND tbl_os_campo_extra.os_bloqueada IS TRUE ";
	}

	$sql = "SELECT tbl_os.os                                                ,
		tbl_os.sua_os                                           ,
		tbl_os.tipo_atendimento                                 ,
		LPAD(tbl_os.sua_os,10,'0') AS os_ordem                  ,
		TO_CHAR(tbl_os.data_abertura,'DD/MM/YY') AS abertura    ,
		tbl_produto.produto                                     ,
		tbl_produto.referencia                                  ,
		tbl_produto.descricao                                   ,
		tbl_produto.voltagem
	FROM tbl_os
	JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i=$login_fabrica
	$cond_campo_extra
	LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
	WHERE tbl_os.fabrica     = $login_fabrica
		AND tbl_os.posto         = $login_posto
		$cond_data
		AND tbl_os.excluida IS NOT TRUE $extraCond ";
	if($login_fabrica == 24){
		$sql .= " AND tbl_os.data_abertura BETWEEN CURRENT_DATE - INTERVAL '25 days' AND CURRENT_DATE - INTERVAL '15 days'
	 			  AND tbl_os.cancelada IS NOT TRUE ";
	}else if($login_fabrica == 86){
		$sql .= " AND tbl_os.data_abertura < CURRENT_DATE - INTERVAL '3 days' ";
	}else if($login_fabrica == 91){
		$sql .= " AND tbl_os.data_abertura < CURRENT_DATE - INTERVAL '5 days' ";
	}else{
		$sql .= " AND tbl_os.data_abertura <= CURRENT_DATE - INTERVAL '15 days' ";
	}

	$sql .= " AND tbl_os.data_fechamento IS NULL
		AND coalesce(tbl_os_produto.os_produto,null) is null
		LIMIT 3;";
	
	//die(nl2br($sql));

	$res = pg_query($con,$sql);
	$contador_res = pg_num_rows($res);

	if(!in_array($login_fabrica,array(30))){
		if ($contador_res > 0) {
			echo "
				<table class='table_tc table-bordered  table-hover table-large'>
					<thead>
						<tr class='titulo_coluna'>
							<th colspan='3' >
			";
			if($login_fabrica == 86){
				$dias = 3;
			}elseif($login_fabrica == 91){
				$dias = 5;
			}else{
				$dias = 15;
			}
			
			if($sistema_lingua == "ES") {
				echo "&nbsp;OS SIN FECHA DE CIERRE HACE $dias DÍAS O MÁS DE LA FECHA DE ABERTURA&nbsp;";
			}else{
				echo "&nbsp;OS ABERTAS A MAIS DE $dias DIAS SEM LANÇAMENTO DE PEÇAS&nbsp;";
			}

			if($sistema_lingua == "ES") {
				echo "";
			}else{
				echo "<br>Perigo de PROCON conforme artigo 18 do C.D.C.";
			}
			echo "
				</th>
				</tr>
				<tr class='titulo_coluna'>
					<th>OS</th>
					<th>Abertura</th>
					<th>" . strtoupper(traduz("produto")) . "</th>
				</tr>
				</thead>
				<tbody>
			";

			if($login_fabrica == 24){
				echo "<td>A&ccedil;&otilde;es</td>";
			}

			for ($a = 0 ; $a < $contador_res; $a++) {
				$os               = trim(pg_fetch_result($res,$a,os));
				$sua_os           = trim(pg_fetch_result($res,$a,sua_os));
				$tipo_atendimento = trim(pg_fetch_result($res,$a,tipo_atendimento));
				$abertura         = trim(pg_fetch_result($res,$a,abertura));
				$produto          = trim(pg_fetch_result($res,$a,produto));
				$referencia       = trim(pg_fetch_result($res,$a,referencia));
				$descricao        = trim(pg_fetch_result($res,$a,descricao));


				//--=== Tradução para outras linguas ============================= Raphael HD:1212
				$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = '$sistema_lingua'";

				$res_idioma = @pg_query($con,$sql_idioma);
				if (@pg_num_rows($res_idioma) >0) {
					$descricao  = trim(@pg_fetch_result($res_idioma,0,descricao));
				}
				//--=== Tradução para outras linguas ================================================

				$produto_completo = $referencia . " - " . $descricao;

				echo "<td align='center'>";
				if ($login_fabrica == 91) {
					echo "<a href='os_consulta_lite.php?sua_os=$sua_os&btn_acao=Pesquisar'>";
				} else {
					echo "<a href='os_item.php?os=$os'>";
				}

				if(strlen($sua_os)==0) echo $os;
				else                  echo "$sua_os";
				echo "</a></td>";
				echo "<td align='center'>" . $abertura . "</td>";

				if ($sistema_lingua=='ES') echo "<td nowrap><acronym title='Referencia: $referencia\nDescripción: $descricao' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";
				else echo "<td nowrap><acronym title='Referência: $referencia\nDescrição: $descricao' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";

				if($login_fabrica == 24){
					echo "<td nowrap>";
					echo "<button onclick='window.location=\"os_press.php?os=$os\"'>Interagir</button>";
					echo "<button onclick='window.location=\"os_fechamento.php?sua_os=$os&btn_acao_pesquisa=continuar\"'>Fechar / Consertar</button>";
					echo "</td>";
				}
				echo "</tr>";
			}
			if($login_fabrica <> 24 and $login_fabrica <> 104){
				if($login_fabrica == 91){
					$listar_risco = 5;
				}else{
					$listar_risco = 15;
				}
				echo "
					<tr class='titulo_coluna'>
						<th colspan='3'>
							<a style='color: #fff;' href= \"javascript: lista_risco(" . $listar_risco . ")\">LISTAR TODAS</a>
						</th>
					</tr>";
			}

			if($login_fabrica == 86){
				echo "<tr class='titulo_coluna'>
					<td colspan='3'>
						Obs.: Caso a OS não necessite de troca de peças, por favor informe a peça em que foi feito algum reparo ou manutenção e selecione o tipo de ajuste realizado
					</td>
				  </tr>";
			}
			echo "</table>";
			echo "<br>";
		}
	}
	if ($login_fabrica == 24) {
		$cond_campo_extra = " JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os AND tbl_os_campo_extra.fabrica = $login_fabrica AND tbl_os_campo_extra.os_bloqueada IS TRUE ";
		$cond_cancelada = "AND tbl_os.cancelada IS NOT TRUE ";
	}


	$sql = "SELECT tbl_os.os ,
			tbl_os.sua_os ,
			tbl_os.tipo_atendimento ,
			LPAD(tbl_os.sua_os,10,'0') AS os_ordem ,
			TO_CHAR(tbl_os.data_abertura,'DD/MM/YY') AS abertura ,
			tbl_produto.produto ,
			tbl_produto.referencia ,
			tbl_produto.descricao ,
			tbl_produto.voltagem
		FROM tbl_os
		JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i=$login_fabrica
		$cond_campo_extra
		WHERE tbl_os.fabrica = $login_fabrica
			AND tbl_os.posto = $login_posto";
			if($login_fabrica == 91){
				$sql .= " AND tbl_os.data_abertura <= CURRENT_DATE - INTERVAL '23 days' ";	
			} else {
				$sql .= " AND tbl_os.data_abertura <= CURRENT_DATE - INTERVAL '25 days'";
			}			
			$sql .= "AND tbl_os.data_fechamento IS NULL
			$cond_data
			$cond_cancelada

			AND tbl_os.excluida IS NOT TRUE $extraCond
			ORDER BY tbl_os.os DESC
			LIMIT 3;";

	$res = pg_query($con,$sql);
	$contador_res = pg_num_rows($res);

	if ($contador_res > 0 AND $login_fabrica <> 86 AND $login_fabrica <> 30) {
		$cols = ($login_fabrica == 24) ? 4 : 3;

		echo "
			<table class='table_tc table-bordered  table-hover table-large'>
				<thead>
					<tr class='titulo_coluna'>
						<th colspan='".$cols."' >
		";
		if($sistema_lingua == "ES") {
			echo "&nbsp;OS SIN FECHA DE CIERRE HACE 15 DÍAS O MÁS DE LA FECHA DE ABERTURA&nbsp;";
		}else{
			if($login_fabrica == 91) {
				echo "&nbsp;O.S's ABERTAS A MAIS DE 23 DIAS SEM DATA DE FECHAMENTO, INDEPENDENTE DO LANÇAMENTO DE PEÇAS&nbsp;";
			} else {
				echo "&nbsp;O.S's ABERTAS A MAIS DE 25 DIAS SEM DATA DE FECHAMENTO, INDEPENDENTE DO LANÇAMENTO DE PEÇAS&nbsp;";
			}
		}
		if($sistema_lingua == "ES") {
			echo "";
		}else{
			echo "<br>Perigo de PROCON conforme artigo 18 do C.D.C.";
		}
		echo "
			</th>
			</tr>
			<tr class='titulo_coluna'>
				<th>OS</th>
				<th>Abertura</th>
				<th>" . strtoupper(traduz("produto")) . "</th>
		";

		if($login_fabrica == 24){
			echo "<th>A&ccedil;&otilde;es</th>";
		}		

		echo "</tr>
			</thead>
			<tbody>";

		for ($a = 0 ; $a < $contador_res; $a++) {
			$os               = trim(pg_fetch_result($res,$a,os));
			$sua_os           = trim(pg_fetch_result($res,$a,sua_os));
			$tipo_atendimento = trim(pg_fetch_result($res,$a,tipo_atendimento));
			$abertura         = trim(pg_fetch_result($res,$a,abertura));
			$produto          = trim(pg_fetch_result($res,$a,produto));
			$referencia       = trim(pg_fetch_result($res,$a,referencia));
			$descricao        = trim(pg_fetch_result($res,$a,descricao));


			//--=== Tradução para outras linguas ============================= Raphael HD:1212
			$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = '$sistema_lingua'";

			$res_idioma = @pg_query($con,$sql_idioma);
			if (@pg_num_rows($res_idioma) >0) {
				$descricao  = trim(@pg_fetch_result($res_idioma,0,descricao));
			}
			//--=== Tradução para outras linguas ================================================

			$produto_completo = $referencia . " - " . $descricao;

			echo "<td>";
			if ($login_fabrica == 91) {
				echo "<a href='os_consulta_lite.php?sua_os=$sua_os&btn_acao=Pesquisar'>";
			} else {
				echo "<a href='os_item.php?os=$os' >";
			}

			if(strlen($sua_os)==0) echo $os;
			else                  echo "$sua_os";
			echo "</a></td>";
			echo "<td align='center'>" . $abertura . "</td>";

			if ($sistema_lingua=='ES') echo "<td nowrap><acronym title='Referencia: $referencia\nDescripción: $descricao' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";
			else echo "<td nowrap><acronym title='Referência: $referencia\nDescrição: $descricao' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";

			if($login_fabrica == 24){
				echo "<td nowrap>";
				echo "<button onclick='window.location=\"os_press.php?os=$os\"'>Integarir</button>";
				echo "<button onclick='window.location=\"os_fechamento.php?sua_os=$os&btn_acao_pesquisa=continuar\"'>Fechar / Consertar</button>";
				echo "</td>";
			}

			echo "</tr>";
		}
		if($login_fabrica <> 24 and $login_fabrica <> 104){
			echo "<tr class='titulo_coluna'>";
			if($login_fabrica == 91){
				echo "<th colspan='3'><a style='color: #fff;' href= \"javascript: lista_risco(23)\">LISTAR TODAS</a></th>";
			} else {
				echo "<th colspan='3'><a style='color: #fff;' href= \"javascript: lista_risco(25)\">LISTAR TODAS</a></th>";
			}
			echo "</tr>";
		}
		echo "</table>";
		echo "<br>";
	}

	if($login_fabrica == 91){
		$sql_25_30_dias = "SELECT tbl_os.os ,
									tbl_os.sua_os ,
									tbl_os.tipo_atendimento ,
									LPAD(tbl_os.sua_os,10,'0') AS os_ordem ,
									TO_CHAR(tbl_os.data_abertura,'DD/MM/YY') AS abertura ,
									tbl_produto.produto ,
									tbl_produto.referencia ,
									tbl_produto.descricao ,
									tbl_produto.voltagem
								FROM tbl_os
								JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i={$login_fabrica}								
								WHERE tbl_os.fabrica = {$login_fabrica}
									AND tbl_os.posto = {$login_posto}
									AND tbl_os.data_abertura BETWEEN CURRENT_DATE - INTERVAL '30 days' AND CURRENT_DATE - INTERVAL '25 days'									
									AND tbl_os.data_fechamento IS NULL
									AND tbl_os.excluida IS NOT TRUE 
									ORDER BY tbl_os.os DESC
									LIMIT 3";

		//die(nl2br($sql_25_30_dias));

		$res_25_30_dias = pg_query($con, $sql_25_30_dias);
		$contador_25_30_dias = pg_num_rows($res_25_30_dias);

		if($contador_25_30_dias > 0){
			echo "
				<table class='table_tc table-bordered  table-hover table-large'>
					<thead>
						<tr class='titulo_coluna'>
							<th colspan='3' >
			";
			if($sistema_lingua == "ES") {
				echo "&nbsp;OS SIN FECHA DE CIERRE HACE 25 DÍAS O MÁS DE LA FECHA DE ABERTURA&nbsp;";
			}else {
				echo "&nbsp;O.S's ABERTAS A MAIS DE 25 DIAS SEM DATA DE FECHAMENTO, INDEPENDENTE DO LANÇAMENTO DE PEÇAS&nbsp;";
			}			

			if($sistema_lingua == "ES") {
				echo "";
			}else{
				echo "<br>Perigo de PROCON conforme artigo 18 do C.D.C.";
			}
			echo "
				</th>
				</tr>
				<tr class='titulo_coluna'>
					<th>OS</th>
					<th>Abertura</th>
					<th>" . strtoupper(traduz("produto")) . "</th>
				</tr>
				</thead>
				<tbody>
			";			

			for ($a = 0 ; $a < $contador_25_30_dias; $a++) {
				$os               = trim(pg_fetch_result($res_25_30_dias,$a,os));
				$sua_os           = trim(pg_fetch_result($res_25_30_dias,$a,sua_os));
				$tipo_atendimento = trim(pg_fetch_result($res_25_30_dias,$a,tipo_atendimento));
				$abertura         = trim(pg_fetch_result($res_25_30_dias,$a,abertura));
				$produto          = trim(pg_fetch_result($res_25_30_dias,$a,produto));
				$referencia       = trim(pg_fetch_result($res_25_30_dias,$a,referencia));
				$descricao        = trim(pg_fetch_result($res_25_30_dias,$a,descricao));

				//--=== Tradução para outras linguas ============================= Raphael HD:1212
				$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = '$sistema_lingua'";

				$res_idioma = @pg_query($con,$sql_idioma);
				if (@pg_num_rows($res_idioma) >0) {
					$descricao  = trim(@pg_fetch_result($res_idioma,0,descricao));
				}
				//--=== Tradução para outras linguas ================================================

				$produto_completo = $referencia . " - " . $descricao;

				echo "<td>";
				if ($login_fabrica == 91) {
					echo "<a href='os_consulta_lite.php?sua_os=$sua_os&btn_acao=Pesquisar'>";
				} else {
					echo "<a href='os_item.php?os=$os' >";
				}

				if(strlen($sua_os)==0) echo $os;
				else                  echo "$sua_os";
				echo "</a></td>";
				echo "<td align='center'>" . $abertura . "</td>";

				if ($sistema_lingua=='ES') echo "<td nowrap><acronym title='Referencia: $referencia\nDescripción: $descricao' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";
				else echo "<td nowrap><acronym title='Referência: $referencia\nDescrição: $descricao' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";

				echo "</tr>";				
			}		
			
			echo "<tr class='titulo_coluna'>";
			echo "<th colspan='3'><a style='color: #fff;' href= \"javascript: lista_risco(25)\">LISTAR TODAS</a></th>";
			echo "</tr>";
			echo "</table>";
			echo "<br>";
		}

		$sql_30_dias = "SELECT tbl_os.os ,
									tbl_os.sua_os ,
									tbl_os.tipo_atendimento ,
									LPAD(tbl_os.sua_os,10,'0') AS os_ordem ,
									TO_CHAR(tbl_os.data_abertura,'DD/MM/YY') AS abertura ,
									tbl_produto.produto ,
									tbl_produto.referencia ,
									tbl_produto.descricao ,
									tbl_produto.voltagem
								FROM tbl_os
								JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i={$login_fabrica}								
								WHERE tbl_os.fabrica = {$login_fabrica}
									AND tbl_os.posto = {$login_posto}
									AND tbl_os.data_abertura <= CURRENT_DATE - INTERVAL '31 days'
									AND tbl_os.data_fechamento IS NULL
									AND tbl_os.excluida IS NOT TRUE 
									ORDER BY tbl_os.os DESC
									LIMIT 3";

		$res_30_dias = pg_query($con, $sql_30_dias);
		$contador_30_dias = pg_num_rows($res_30_dias);

		if($contador_30_dias > 0){
			echo "
				<table class='table_tc table-bordered  table-hover table-large'>
					<thead>
						<tr class='titulo_coluna'>
							<th colspan='3' >
			";
			if($sistema_lingua == "ES") {
				echo "&nbsp;OS SIN FECHA DE CIERRE HACE 30 DÍAS O MÁS DE LA FECHA DE ABERTURA&nbsp;";
			}else {
				echo "&nbsp;O.S's ABERTAS A MAIS DE 30 DIAS SEM DATA DE FECHAMENTO, INDEPENDENTE DO LANÇAMENTO DE PEÇAS&nbsp;";
			}			

			if($sistema_lingua == "ES") {
				echo "";
			}else{
				echo "<br>Perigo de PROCON conforme artigo 18 do C.D.C.";
			}
			echo "
				</th>
				</tr>
				<tr class='titulo_coluna'>
					<th>OS</th>
					<th>Abertura</th>
					<th>" . strtoupper(traduz("produto")) . "</th>
				</tr>
				</thead>
				<tbody>
			";
			
			for ($a = 0 ; $a < $contador_30_dias; $a++) {
				$os               = trim(pg_fetch_result($res_30_dias,$a,os));
				$sua_os           = trim(pg_fetch_result($res_30_dias,$a,sua_os));
				$tipo_atendimento = trim(pg_fetch_result($res_30_dias,$a,tipo_atendimento));
				$abertura         = trim(pg_fetch_result($res_30_dias,$a,abertura));
				$produto          = trim(pg_fetch_result($res_30_dias,$a,produto));
				$referencia       = trim(pg_fetch_result($res_30_dias,$a,referencia));
				$descricao        = trim(pg_fetch_result($res_30_dias,$a,descricao));

				//--=== Tradução para outras linguas ============================= Raphael HD:1212
				$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = '$sistema_lingua'";

				$res_idioma = @pg_query($con,$sql_idioma);
				if (@pg_num_rows($res_idioma) >0) {
					$descricao  = trim(@pg_fetch_result($res_idioma,0,descricao));
				}
				//--=== Tradução para outras linguas ================================================

				$produto_completo = $referencia . " - " . $descricao;

				echo "<td>";
				if ($login_fabrica == 91) {
					echo "<a href='os_consulta_lite.php?sua_os=$sua_os&btn_acao=Pesquisar'>";
				} else {
					echo "<a href='os_item.php?os=$os' >";
				}

				if(strlen($sua_os)==0) echo $os;
				else                  echo "$sua_os";
				echo "</a></td>";
				echo "<td align='center'>" . $abertura . "</td>";

				if ($sistema_lingua=='ES') echo "<td nowrap><acronym title='Referencia: $referencia\nDescripción: $descricao' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";
				else echo "<td nowrap><acronym title='Referência: $referencia\nDescrição: $descricao' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";

				echo "</tr>";				
			}

			echo "<tr class='titulo_coluna'>";
			echo "<th colspan='3'><a style='color: #fff;' href= \"javascript: lista_risco(30)\">LISTAR TODAS</a></th>";
			echo "</tr>";			

			echo "</table>";
			echo "<br>";
		}
	}

}


?>
