<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
if($login_fabrica<>1)include "autentica_usuario_financeiro.php";

if ($login_fabrica == 3) {
	if ($login_e_distribuidor == 't') {
		header ("Location: new_extrato_distribuidor.php");
		exit;
	}else{
		header ("Location: new_extrato_posto.php");
		exit;
	}
}

if ($login_fabrica == 1) {
	header ("Location: os_extrato_blackedecker.php");
	exit;
}

$msg_erro = "";

$layout_menu = "os";
$title = "Extratos";

include "cabecalho.php";
/*
if ($login_fabrica == 6 AND $ip <> '201.0.9.216') {
	echo "<br>Esta �rea de Extrato est� em manuten��o.";
	exit;
}
*/
?>

<p>

<script language="JavaScript">

function date_onkeydown() {
  if (window.event.srcElement.readOnly) return;
  var key_code = window.event.keyCode;
  var oElement = window.event.srcElement;
  if (window.event.shiftKey && String.fromCharCode(key_code) == "T") {
        var d = new Date();
        oElement.value = String(d.getMonth() + 1).padL(2, "0") + "/" +
                         String(d.getDate()).padL(2, "0") + "/" +
                         d.getFullYear();
        window.event.returnValue = 0;
    }
    if (!window.event.shiftKey && !window.event.ctrlKey && !window.event.altKey) {
        if ((key_code > 47 && key_code < 58) ||
          (key_code > 95 && key_code < 106)) {
            if (key_code > 95) key_code -= (95-47);
            oElement.value =
                oElement.value.replace(/[dma]/, String.fromCharCode(key_code));
        }
        if (key_code == 8) {
            if (!oElement.value.match(/^[dma0-9]{2}\/[dma0-9]{2}\/[dma0-9]{4}$/))
                oElement.value = "dd/mm/aaaa";
            oElement.value = oElement.value.replace(/([dma\/]*)[0-9]([dma\/]*)$/,
                function ($0, $1, $2) {
                    var idx = oElement.value.search(/([dma\/]*)[0-9]([dma\/]*)$/);
                    if (idx >= 5) {
                        return $1 + "a" + $2;
                    } else if (idx >= 2) {
                        return $1 + "m" + $2;
                    } else {
                        return $1 + "d" + $2;
                    }
                } );
            window.event.returnValue = 0;
        }
    }
    if (key_code != 9) {
        event.returnValue = false;
    }
}

</script>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.Mensagem{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#7192C4;
	font-weight: bold;
}
.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
	}

</style>

<?
if(strlen($msg)>0){
	echo "<br><table class='Tabela' width='700' cellspacing='0'  cellpadding='0' align='center'>";
	echo "<tr >";
	echo "<td bgcolor='FFFFFF' width='60' align='top'><img src='imagens/cadeado1.jpg' align='absmiddle'></td><td  class='Mensagem' bgcolor='FFFFFF' align='left'>$msg<br>Para restringir o acesso novamente, sair do sistema. Caso contrario o EXTRATO ficar� com o acesso liberado!<br><center><a href='os_extrato_senha.php?acao=alterar'>Alterar Senha</a> &nbsp; - &nbsp; <a href='os_extrato_senha.php?acao=libera'>Liberar tela</a></center>";
	echo "</td>";
	echo "</tr>";
	echo "</table><br>";
	//echo "<a href='os_extrato_senha.php?acao=alterar'>Alterar senha</a>";
	//echo "&nbsp;&nbsp;<a href='os_extrato_senha.php?acao=libera'>Liberar tela</a>";
}else{
	echo "<table class='Tabela' width='700' cellspacing='0'  cellpadding='0' align='center'>";
	echo "<tr >";
	echo "<td bgcolor='FFFFFF' width='60'><img src='imagens/cadeado2.jpg' align='absmiddle'></td><td  class='Mensagem' bgcolor='FFFFFF' align='left'><a href='os_extrato_senha.php?acao=inserir' >Esta area n�o est� protegida por senha! <br>Para inserir senha para Restri��o do Extrato, clique aqui e saiba mais! </a></td>";
	echo "</tr>";
	echo "</table><br>";
}

$periodo = trim($_POST['periodo']);
if (strlen($_GET['periodo']) > 0) $periodo = trim($_GET['periodo']);

# ----------------------------------------- #
# -- VERIFICA SE � POSTO OU DISTRIBUIDOR -- #
# ----------------------------------------- #
$sql = "SELECT  DISTINCT
			tbl_tipo_posto.tipo_posto ,
			tbl_posto.estado
	FROM    tbl_tipo_posto
	JOIN    tbl_posto_fabrica    ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
								AND tbl_posto_fabrica.posto      = $login_posto
								AND tbl_posto_fabrica.fabrica    = $login_fabrica
	JOIN    tbl_posto            ON tbl_posto.posto = tbl_posto_fabrica.posto
	WHERE   tbl_tipo_posto.distribuidor IS TRUE
	AND     tbl_posto_fabrica.fabrica = $login_fabrica
	AND     tbl_tipo_posto.fabrica    = $login_fabrica
	AND     tbl_posto_fabrica.posto   = $login_posto ";
$res = pg_exec ($con,$sql);
if (pg_numrows($res) == 0) $tipo_posto = "P"; else $tipo_posto = "D";

//foi adicionado a pedido do natanael pois os valores do extrato est�o errados.
if($login_fabrica == 19 and $login_posto <> 13663 and 1==2 ){
	$sql  = "SELECT * FROM tbl_extrato WHERE $login_posto IN (SELECT posto FROM tbl_extrato WHERE extrato IN (48397,37809,52080,58391,48390,43751,52088,52057,48401,31397,52092,48367,43731,52060,34551,52059,52058,48383,48364,43753,48365,48389,52097,48396,52099,43727,48404,46728,44947,48369))";
	$resX = pg_exec ($con,$sql);
	if (pg_numrows ($resX) > 0) {
		echo "<BR><BR><BR>";
		echo "<TABLE style='font-family: verdana; font-size: 12px'>";
		echo "<tr>";
		echo "<TD><B>A tela de extrato est� em manuten��o no momento.</B></TD>";
		echo "</tr>";
		echo "<tr>";
		echo "<TD align='center'><B>Retornar� em alguns instantes. Obrigado</B></TD>";
		echo "</tr>";
		echo "</table>";
	}
	exit ;
}

if($login_fabrica==11){
	echo "<TABLE style='font-family: verdana; font-size: 12px' align='center'>";
	echo "<tr class='table_line'>\n";
	echo "<td align=\"center\"><B>EMITIR NOTA FISCAL:</B><BR>
	Aulik  Industria e Comercio Ltda.<BR>
	Rua Carlos Alberto Santos, s/nr. - QD. �D� � LT. 20/21 - Miragem<BR>
	Lauro de Freitas / BA.<BR>
	CNPJ: 05.256.426/0001-24 <BR>
	INSCR.EST. : 62.942.325</td>\n";
	echo "</tr>\n";
	echo "<tr class='table_line'>\n";
	echo "<td align=\"center\"><B>ENVIAR PARA:</b><BR>
	Aulik  Industria e Comercio Ltda.<BR>
	Rua Bela Cintra, 986 � 3 andar � Bela Vista<BR>
	S�o Paulo / SP. � CEP: 01415-000</td>\n";
	echo "</tr>\n";
	echo "<tr class='table_line'>\n";
	echo "<td align=\"center\"><B>Descri��o da Nota Fiscal:</b><BR>
	*Servi�os prestados em aparelhos de sua comercializa��o, sob garantia durante o m�s de Outubro de 2006.*<BR>
	A N.F.dever� estar na Empresa at� dia 06/11/06<BR>
	Devido Feriado dia 02/11/06, o pagamento ser� dia 09/11/06.</td>\n";
	echo "</tr>\n";
	echo "</table>";
}
if ($login_fabrica == 3) {
	#if (substr($ip,0,10) <> '192.168.0.') {
	#	echo "<h1><center>Extratos sendo recalculados pela TELECONTROL</center></h1>";
	#	exit;
	#}

	# --------------------------------------- #
	# -- MONTA COMBO COM DATAS DE EXTRATOS -- #
	# --------------------------------------- #
	$sql = "SELECT      DISTINCT
						date_trunc('day',tbl_extrato.data_geracao)      AS data_extrato,
						to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data        ,
						to_char(tbl_extrato.data_geracao, 'YYYY-MM-DD') AS periodo
			FROM        tbl_extrato
			JOIN        tbl_posto_fabrica    ON tbl_posto_fabrica.posto   = tbl_extrato.posto
											AND tbl_posto_fabrica.fabrica = $login_fabrica ";

	if ($tipo_posto == "D") $sql .= " WHERE (tbl_posto_fabrica.posto = $login_posto OR tbl_posto_fabrica.distribuidor = $login_posto) ";
	else                    $sql .= " WHERE tbl_posto_fabrica.posto  = $login_posto ";

	$sql .="AND      tbl_extrato.fabrica = $login_fabrica
			AND      tbl_extrato.aprovado IS NOT NULL
			ORDER BY to_char(tbl_extrato.data_geracao, 'YYYY-MM-DD') DESC";

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<form name=\"frm_periodo\" method=\"get\" action=\"$PHP_SELF\">";
		echo "<input type=\"hidden\" name=\"exibir\" value=\"acumulado\">";
		
		echo "<table width='80%' border='0' cellpadding='2' cellspacing='2' align='center'>";
		echo "<tr>";
		
		echo "<td bgcolor='#FFFFFF' align='center'>";
		echo "<select name='periodo' onchange='javascript:frm_periodo.submit()'>\n";
		echo "<option value=''>INFORME O PER�ODO PARA CONSULTA</option>\n";
		
		for ($x = 0 ; $x < pg_numrows($res) ; $x++){
			$aux_data  = trim(pg_result($res,$x,data));
			$aux_extr  = trim(pg_result($res,$x,data_extrato));
			$aux_peri  = trim(pg_result($res,$x,periodo));
			
			echo "<option value='$aux_peri'"; if ($periodo == $aux_peri) echo " SELECTED "; echo ">$aux_data</option>\n";
		}
		
		echo "</select>\n";
		echo "</td>";
		
		echo "</tr>";
		echo "</table>";
		
		echo "</form>";
	}

	# ----------------------------------------- #
	# -- SE FOI SELECIONADO PER�ODO NO COMBO -- #
	# ----------------------------------------- #
	if (strlen($periodo) > 0) {
		$exibir = $_POST['exibir'];
		if (strlen($_GET['exibir']) > 0) $exibir = $_GET['exibir'];
		
		if ($exibir == 'acumulado') {
			# -- EXIBE VALORES ACUMULADOS DOS EXTRATOS -- #
			# -- SELECIONA EXTRATOS DOS POSTOS -- #
			$sql = "SELECT      tbl_linha.linha                                                    ,
								tbl_linha.nome                                       AS linha_nome ,
								count(tbl_os.os)                                     AS qtde_os    ,
								tbl_os.mao_de_obra                                   AS mo_unit    ,
								sum (tbl_os.mao_de_obra)                             AS mo_posto   ,
								sum (tbl_familia.mao_de_obra_adicional_distribuidor) AS mo_adicional
					FROM        tbl_os
					JOIN        tbl_os_extra         ON tbl_os_extra.os           = tbl_os.os
													AND tbl_os.fabrica            = $login_fabrica
					JOIN        tbl_extrato          ON tbl_extrato.extrato       = tbl_os_extra.extrato
													AND tbl_extrato.fabrica       = $login_fabrica
					JOIN        tbl_produto          ON tbl_produto.produto       = tbl_os.produto
					JOIN        tbl_linha            ON tbl_produto.linha         = tbl_linha.linha
													AND tbl_linha.fabrica         = $login_fabrica
					LEFT JOIN   tbl_familia          ON tbl_produto.familia       = tbl_familia.familia
					JOIN        tbl_posto_fabrica    ON tbl_os.posto              = tbl_posto_fabrica.posto
													AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
					WHERE       tbl_posto_fabrica.fabrica = $login_fabrica ";
			
			if ($tipo_posto == "D") $sql .= "AND (tbl_posto_fabrica.distribuidor = $login_posto OR tbl_posto_fabrica.posto = $login_posto) ";
			else                    $sql .= "AND tbl_os.posto = $login_posto ";
			
			$sql .="AND         tbl_extrato.data_geracao BETWEEN '$periodo 00:00:00' AND '$periodo 23:59:59'
					GROUP BY    tbl_linha.linha    ,
								tbl_linha.nome     ,
								tbl_os.mao_de_obra
					ORDER BY    linha_nome         ,
								tbl_os.mao_de_obra ";
			$res = pg_exec($con,$sql);
			
			echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
			
			if (pg_numrows($res) > 0) {
				$qtde_linhas     = pg_numrows($res);
				$qtde_os         = 0;
				$mo_posto        = 0;
				$mo_adicional    = 0;
				$pecas_total     = 0;
				$adicional_pecas = 0;
				$total           = 0;
				
				echo "<form method=post name=frm_extrato action=\"$PHP_SELF\">";
				
				echo "<tr class='table_line2' style='background-color: #D9E2EF;'>";
				echo "<td nowrap align='center'><b>LINHA</b></td>";
				echo "<td nowrap align='center'><b>M.O.<br>UNIT.</b></td>";
				echo "<td nowrap align='center'><b>QTDE</b></td>";
				echo "<td nowrap align='center'><b>M.O.<br>POSTOS</b></td>";
				if ($tipo_posto == "D") echo "<td nowrap align='center'><b>M.O.<br>ADICIONAL</b></td>";
				if ($tipo_posto == "D") echo "<td nowrap align='center'><b>PE�AS<br>TOTAL</b></td>";
				if ($tipo_posto == "D") echo "<td nowrap align='center'><b>ADICIONAL<br>PE�AS</b></td>";
				if ($tipo_posto == "D") echo "<td nowrap align='center'><b>N.F.<br>SERVI�O</b></td>";
				echo "<td nowrap align='center'>&nbsp;</td>";
				echo "</tr>";
				
				for ($y=0; $y < pg_numrows($res); $y++) {
					$linha        = trim(pg_result($res,$y,linha));
					$nome_linha   = trim(pg_result($res,$y,linha_nome));
					$mo_unit      = trim(pg_result($res,$y,mo_unit));
					$qtde_os      = trim(pg_result($res,$y,qtde_os));
					$mo_posto     = trim(pg_result($res,$y,mo_posto));
					$mo_adicional = trim(pg_result($res,$y,mo_adicional));
					
					//////////////////////////////////////////////
					$btn = 'azul';
					
					$cor = "#F7F5F0"; 
					if ($y % 2 == 0) $cor = '#F1F4FA';
					
					echo "<tr class='table_line2' style='background-color: $cor;'>\n";
					echo "<td align='left'>$nome_linha</td>\n";
					echo "<td align='right'>". number_format($mo_unit,2,",",".") ."</td>\n";
					echo "<td align='right'>$qtde_os</td>\n";
					echo "<td align='right'>". number_format($mo_posto,2,",",".") ."</td>\n";
					
					if ($tipo_posto == "D") {
						echo "<td align='right'>". number_format($mo_adicional,2,",",".") ."</td>\n";
						
						$sql = "SELECT ROUND (SUM (tbl_os_item.qtde * tbl_tabela_item.preco)::numeric, 2) AS preco
								FROM    tbl_os
								JOIN    tbl_os_produto       ON tbl_os.os                 = tbl_os_produto.os
								JOIN    tbl_os_item          ON tbl_os_produto.os_produto = tbl_os_item.os_produto
								JOIN    tbl_os_extra         ON tbl_os.os                 = tbl_os_extra.os
								JOIN    tbl_extrato          ON tbl_extrato.extrato       = tbl_os_extra.extrato
								JOIN	tbl_produto          ON tbl_os.produto            = tbl_produto.produto
								JOIN    tbl_linha            ON tbl_produto.linha         = tbl_linha.linha
								JOIN	tbl_familia          ON tbl_produto.familia       = tbl_familia.familia
								JOIN    tbl_posto_fabrica    ON tbl_os.posto              = tbl_posto_fabrica.posto
															AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
								JOIN    tbl_posto_linha      ON tbl_posto_linha.posto     = $login_posto
															AND tbl_posto_linha.linha     = $linha
								JOIN    tbl_tabela_item      ON tbl_tabela_item.tabela    = tbl_posto_linha.tabela
															AND tbl_tabela_item.peca      = tbl_os_item.peca
								WHERE   (tbl_posto_fabrica.distribuidor = $login_posto OR tbl_os.posto = $login_posto)
								AND     tbl_extrato.data_geracao BETWEEN '$periodo 00:00:00' AND '$periodo 23:59:59'
								AND     tbl_os.fabrica     = $login_fabrica
								AND     tbl_linha.linha    = $linha
								AND     tbl_os.mao_de_obra = $mo_unit ";
						$resX = pg_exec ($con,$sql);
						
						if (pg_numrows($resX) > 0) {
							$pecas_preco    = pg_result ($resX,0,preco);
							$adicional      = $pecas_preco * 0.5385;
							$nf_servico     = $mo_posto + $mo_adicional + $adicional;
							$t_pecas_total += $pecas_preco;
							
							echo "<td align='right'>". number_format($adicional,2,",",".")    ."</td>\n";
							echo "<td align='right'>". number_format($mo_adicional,2,",",".") ."</td>\n";
							echo "<td align='right'>". number_format($nf_servico,2,",",".")   ."</td>\n";
						}
					}
					
					if ($y == 0) {
						echo "<td width='85' rowspan='$qtde_linhas' valign='center'><a href='$PHP_SELF?periodo=$periodo&exibir=detalhado'><img src='imagens/btn_detalhar_".$btn.".gif'></a></td>\n";
					}
					
					$t_qtde_os         += $qtde_os;
					$t_mo_posto        += $mo_posto;
					$t_mo_adicional    += $mo_adicional;
					$t_adicional_pecas += $adicional;
					$total             += $nf_servico;
					
					echo "</tr>\n";
				}
				
				echo "<tr class='table_line2' style='background-color: #D9E2EF;'>\n";
				echo "<td align='center' colspan='2' nowrap><b>TOTAIS</b></td>\n";
				echo "<td nowrap align='right'><b>$t_qtde_os</b></td>";
				echo "<td nowrap align='right'><b>" . number_format ($t_mo_posto,2,",",".") . "</b></td>";
				if ($tipo_posto == "D") echo "<td nowrap align='right'><b>" . number_format ($t_mo_adicional,2,",",".")    . "</b></td>";
				if ($tipo_posto == "D") echo "<td nowrap align='right'><b>" . number_format ($t_pecas_total,2,",",".")     . "</b></td>";
				if ($tipo_posto == "D") echo "<td nowrap align='right'><b>" . number_format ($t_adicional_pecas,2,",",".") . "</b></td>";
				if ($tipo_posto == "D") echo "<td nowrap align='right'><b>" . number_format ($total,2,",",".") . "</b></td>";
				echo "<td align='right' colspan='2' nowrap>&nbsp;</td>\n";
				echo "</tr>\n";
				
				echo "</form>";
			}else{
				echo "<tr class='table_line'>\n";
				echo "<td align=\"center\">NENHUM EXTRATO FOI ENCONTRADO</td>\n";
				echo "</tr>\n";
				
				echo "<tr>\n";
				echo "<td align=\"center\">\n";
				echo "<br><a href='menu_os.php'><img src='imagens/btn_voltar.gif'></a>";
				echo "</td>\n";
				echo "</tr>\n";
			}
			echo "</table>\n";
		}else{

			$condicao_lorenzetti = "";
			if ($login_fabrica == 19) $condicao_lorenzetti = " AND tbl_extrato.nf_recebida IS NOT NULL " ;

			# -- EXIBE VALORES DETALHADOS DOS EXTRATOS -- #
			$sql = "SELECT  tbl_posto_fabrica.codigo_posto                                                                 ,
					tbl_posto.nome                                                                                 ,
					tbl_extrato.posto                                                                              ,
					tbl_extrato.extrato                                                                            ,
					to_char(tbl_extrato.data_geracao, 'YYYY-MM-DD')                                 AS data_extrato,
					to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY')                                 AS data_geracao,
					tbl_extrato.mao_de_obra                                                                        ,
					tbl_extrato.mao_de_obra_postos                                                                 ,
					SUM (tbl_os_extra.mao_de_obra + tbl_os_extra.taxa_visita + tbl_os_extra.deslocamento_km) AS extra_mo  ,
					sum(tbl_familia.mao_de_obra_adicional_distribuidor)                             AS adicional   ,
					tbl_extrato.pecas                                                                              ,
					SUM (tbl_os_extra.custo_pecas)                                                  AS extra_pecas ,
					tbl_extrato.protocolo                                                                          ,
					tbl_posto.estado                                                                               ,
					tbl_posto_fabrica.pedido_via_distribuidor
				FROM        tbl_extrato
				JOIN        tbl_posto_fabrica    ON tbl_extrato.posto         = tbl_posto_fabrica.posto
					AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN        tbl_posto            ON tbl_extrato.posto         = tbl_posto.posto
				JOIN        tbl_os_extra         ON tbl_os_extra.extrato      = tbl_extrato.extrato
				JOIN        tbl_os               ON tbl_os.os                 = tbl_os_extra.os
				JOIN        tbl_produto          ON tbl_produto.produto       = tbl_os.produto
				LEFT JOIN   tbl_familia          ON tbl_familia.familia       = tbl_produto.familia
				WHERE       tbl_extrato.fabrica = $login_fabrica ";
			
			if ($tipo_posto == "D") $sql .= "AND (tbl_posto_fabrica.distribuidor = $login_posto OR tbl_posto_fabrica.posto = $login_posto) ";
			else                    $sql .= "AND tbl_extrato.posto   = $login_posto ";
			
			$sql .="AND         tbl_extrato.data_geracao BETWEEN '$periodo 00:00:00' AND '$periodo 23:59:59'
					AND         tbl_extrato.aprovado IS NOT NULL
					$condicao_lorenzetti
					GROUP BY    tbl_posto_fabrica.codigo_posto            ,
								tbl_posto.nome                            ,
								tbl_extrato.posto                         ,
								tbl_extrato.extrato                       ,
								tbl_extrato.data_geracao                  ,
								tbl_posto_fabrica.pedido_via_distribuidor ,
								tbl_extrato.mao_de_obra                   ,
								tbl_extrato.mao_de_obra_postos            ,
								tbl_extrato.protocolo                     ,
								tbl_extrato.pecas                         ,
								tbl_posto.estado
					ORDER BY tbl_extrato.data_geracao DESC";
			$res = pg_exec ($con,$sql);

			echo "<table width='580' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
			if (pg_numrows($res) > 0) {
				echo "<tr class='table_line'>";
				
				echo "<td colspan=8 align='center'>\n";
				echo "&nbsp;";
				echo "</td>\n";
				
				echo "</tr>\n";
				
				echo "<form method=post name=frm_extrato action=\"$PHP_SELF\">";
				
				echo "<tr class='menu_top'>\n";
				
				echo "<td align=\"center\">EXTRATO</td>\n";
				echo "<td align=\"center\">POSTO</td>\n";
				echo "<td align=\"center\">GERA��O</td>\n";
				echo "<td align=\"center\">M.OBRA</td>\n";
				echo "<td align=\"center\">PE�AS</td>\n";
				echo "<td align=\"center\">TOTAL</td>\n";
				echo "<td align=\"center\">&nbsp;</td>\n";
				echo "<td align=\"center\">&nbsp;</td>\n";
				
				echo "</tr>\n";
				
				for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
					$xmao_de_obra            = 0;
					$posto                   = trim(pg_result($res,$i,posto));
					$posto_codigo            = trim(pg_result($res,$i,codigo_posto));
					$posto_nome              = trim(substr(pg_result($res,$i,nome),0,25));
					$extrato                 = trim(pg_result($res,$i,extrato));
					$data_geracao            = trim(pg_result($res,$i,data_geracao));
					$pedido_via_distribuidor = trim(pg_result($res,$i,pedido_via_distribuidor));
					$data_extrato            = trim(pg_result($res,$i,data_extrato));
					$mao_de_obra             = trim(pg_result($res,$i,mao_de_obra));
					$mao_de_obra_postos      = trim(pg_result($res,$i,mao_de_obra_postos));
					$extra_mo                = trim(pg_result($res,$i,extra_mo));
					$adicional               = trim(pg_result($res,$i,adicional));
					$pecas                   = trim(pg_result($res,$i,pecas));
					$extra_pecas             = trim(pg_result($res,$i,extra_pecas));
					$extrato                 = trim(pg_result($res,$i,extrato));
					$estado                  = trim(pg_result($res,$i,estado));
					$protocolo               = trim(pg_result($res,$i,protocolo));
					
					if (strlen($adicional) == 0) $adicional = 0;
					
					# soma valores
					if ($tipo_posto == "P") {
						$xmao_de_obra += $mao_de_obra_postos;
						$xvrmao_obra   = $mao_de_obra_postos;
					}else{
						$xmao_de_obra += $mao_de_obra;
						$xvrmao_obra   = $mao_de_obra;
					}
					
					if ($xvrmao_obra == 0)  $xvrmao_obra   = $mao_de_obra;
					if ($xmao_de_obra == 0) $xmao_de_obra += $mao_de_obra;
					
					$total = $xmao_de_obra + $pecas;
					
					$data_geracao;
					
					//////////////////////////////////////////////
					$cor = "#F7F5F0"; 
					$btn = 'amarelo';
					if ($i % 2 == 0) 
					{
						$cor = '#F1F4FA';
						$btn = 'azul';
					}
					
					echo "<tr class='table_line' style='background-color: $cor;'>\n";
					
					echo "<td align='left' style='padding-left:7px;'>";
					if ($login_fabrica == 1) echo $protocolo;
					else                     echo $extrato;
					echo "</td>\n";
					echo "<td align='left' nowrap>$posto_codigo - $posto_nome</td>\n";
					
					if ($tipo_posto == "D"){
						echo "<td align='center'><a href='extrato_distribuidor.php?data=$data_extrato'>$data_geracao</a></td>\n";
					}else{
						echo "<td align='center'>$data_geracao</td>\n";
					}
					
					if ($login_fabrica == 19) {
					    $xvrmao_obra = pg_result ($res,$i,extra_mo) ;
					    $pecas       = pg_result ($res,$i,extra_pecas) ;
					    $total       = $pecas + $xvrmao_obra ;
					}
					
					echo "<td align='right'  style='padding-right:3px;' nowrap>". number_format($xvrmao_obra,2,",",".") ."</td>\n";
					echo "<td align='right'  style='padding-right:3px;' nowrap>". number_format($pecas,2,",",".") ."</td>\n";
					echo "<td align='right'  style='padding-right:3px;' nowrap>". number_format($total,2,",",".") ."</td>\n";
					echo "<td><a href='os_extrato_detalhe.php?extrato=$extrato&posto=$posto'><img src='imagens/btn_detalhar_".$btn.".gif'></a></td>\n";
					echo "<td><a href='os_extrato_pecas_retornaveis.php?extrato=$extrato'><img src='imagens/btn_pecasretornaveis_".$btn.".gif'></a></TD>\n";
					
					echo "</tr>\n";
				}
				echo "<input type='hidden' name='total' value='$i'>";
				
				echo "</form>";
			}else{
				echo "<tr class='table_line'>\n";
				echo "<td align=\"center\">NENHUM EXTRATO FOI ENCONTRADO</td>\n";
				echo "</tr>\n";
				
				echo "<tr>\n";
				echo "<td align=\"center\">\n";
				echo "<br><a href='menu_os.php'><img src='imagens/btn_voltar.gif'></a>";
				echo "</td>\n";
				echo "</tr>\n";
			}
			
			echo "</table>\n";
		}
	}
}else{ // OUTROS FABRICANTES
	# -- EXIBE VALORES DETALHADOS DOS EXTRATOS -- #
	$sql = "SELECT  tbl_posto_fabrica.codigo_posto                                                                 ,
			tbl_posto.nome                                                                                 ,
			tbl_extrato.posto                                                                              ,
			tbl_extrato.extrato                                                                            ,
			to_char(tbl_extrato.data_geracao, 'YYYY-MM-DD')                                 AS data_extrato,
			to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY')                                 AS data_geracao,
			tbl_extrato.mao_de_obra                                                                        ,
			tbl_extrato.mao_de_obra_postos                                                                 ,
			SUM (tbl_os_extra.mao_de_obra + tbl_os_extra.taxa_visita + tbl_os_extra.deslocamento_km) AS extra_mo  ,
			tbl_extrato.protocolo                                                                          ,
			0                                                                               AS adicional   ,
			tbl_extrato.pecas                                                                              ,
			tbl_extrato.avulso                                                                              ,
			SUM (tbl_os_extra.custo_pecas)                                                  AS extra_pecas ,
			tbl_posto.estado                                                                               ,
			tbl_posto_fabrica.pedido_via_distribuidor,
			tbl_extrato.total                        
		FROM        tbl_extrato
		JOIN        tbl_posto_fabrica    ON tbl_extrato.posto         = tbl_posto_fabrica.posto
					AND tbl_posto_fabrica.fabrica = $login_fabrica
		JOIN        tbl_posto            ON tbl_extrato.posto         = tbl_posto.posto
		LEFT JOIN   tbl_os_extra         ON tbl_os_extra.extrato      = tbl_extrato.extrato
		LEFT JOIN   tbl_os               ON tbl_os.os                 = tbl_os_extra.os
		LEFT JOIN   tbl_produto          ON tbl_produto.produto       = tbl_os.produto
		LEFT JOIN   tbl_familia          ON tbl_familia.familia       = tbl_produto.familia
		WHERE       tbl_extrato.fabrica = $login_fabrica ";
	
	if ($tipo_posto == "P") $sql .= "AND tbl_extrato.posto   = $login_posto ";
	else                    $sql .= "AND (tbl_posto_fabrica.distribuidor = $login_posto OR tbl_posto_fabrica.posto = $login_posto) ";
	
	$sql .="AND         tbl_extrato.posto   = $login_posto
		AND         tbl_extrato.aprovado IS NOT NULL
		AND         tbl_os.os NOT IN (SELECT tbl_os_status.os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND tbl_os_status.status_os IN (13,15) AND tbl_os_status.extrato=tbl_extrato.extrato) 
		";
	
#		AND         tbl_extrato.extrato NOT IN (37809,48367,48397,52080,58391,48390,43751,52088,52057,48401,31397,52092,43731,52060,34551,52059,52058,48383,48364,43753,48365,48389,52097,48396,52099,43727,48404,46728,44947,48369) 

		if ($login_fabrica == 6 or $login_fabrica == 14 or $login_fabrica == 15) {
		$sql .= "AND tbl_extrato.liberado IS NOT NULL ";
	}
	
	$sql .= "GROUP BY   tbl_posto_fabrica.codigo_posto            ,
						tbl_posto.nome                            ,
						tbl_extrato.posto                         ,
						tbl_extrato.extrato                       ,
						tbl_extrato.data_geracao                  ,
						tbl_posto_fabrica.pedido_via_distribuidor ,
						tbl_extrato.mao_de_obra                   ,
						tbl_extrato.mao_de_obra_postos            ,
						tbl_extrato.pecas                         ,
						tbl_extrato.avulso                        ,
						tbl_extrato.total                         ,
						tbl_extrato.protocolo                     ,
						tbl_posto.estado
			ORDER BY tbl_extrato.data_geracao DESC";
	$res = pg_exec ($con,$sql);
if($login_fabrica==19){
	echo "<table width='700' height=16 border='0' cellspacing='0' cellpadding='5' align='center'>";
	echo "<tr>";
	echo "<td align='center'><font size=1>* NF s e Recibos que chegarem entre os dias 20 a 05, ser�o pagos dia 10;<BR>
  NF s e Recibos que chegarem entre os dias 06 a 20, ser�o pagos dia 25;<BR>
  As data de pagamentos 10 e 25, poderao ser alteradas, nos meses<BR> em que
  estas data forem feriados ou finais de semana.<BR>
</font></td>";
	echo "</tr>";
	echo "</table>";
	echo "<br>";
}

//echo nl2br($sql);
	echo "<table width='700' height=16 border='0' cellspacing='0' cellpadding='5' align='center'>";
	echo "<tr>";
	echo "<td align='center' width='16' bgcolor='#FFE1E1'>&nbsp;</td>";
	echo "<td align='left'><font size=1><b>&nbsp; Extrato Avulso</b></font></td>";
	echo "</tr>";
	echo "</table>";
	echo "<br>";

	echo "<table width='580' align='center' border='0' cellspacing='1' cellpadding='5'>\n";
	if (pg_numrows($res) > 0) {
		if ($login_fabrica == 2){
			echo "<tr class='table_line'>";
			echo "<td colspan='9' align='center'>\n";
			echo "<br><b>ENVIAR PARA A DYNACOM A NOTA FISCAL DE PRESTA��O DE SERVI�O E AS ORDENS DE SERVI�O REFERENTE AO ABAIXO. <br><font color='#FF0000'>� OBRIGAT�RIO O ENVIO DAS O.S.</font></b><br><br>(Clique no n�mero do extrato para abrir os dados da Nota Fiscal de devolu��o)<br><br>\n";
			echo "</td>\n";
			echo "</tr>\n";
		}

		echo "<form method=post name=frm_extrato action=\"$PHP_SELF\">";

		echo "<tr class='menu_top'>\n";
		
		echo "<td align='center'>EXTRATO</td>\n";
		if ($login_fabrica == 19) echo "<td align='center'>RELATORIO</td>\n";
		echo "<td align='center'>POSTO</td>\n";
		echo "<td align='center'>GERA��O</td>\n";
		echo "<td align='center'>MO</td>\n";
		echo "<td align='center'>PE�AS</td>\n";
		echo "<td align='center'>TOTAL</td>\n";
		echo "<td align='center' nowrap>+ AVULSO</td>\n";
		echo "<td align='center' nowrap>(*)PREVIS�O</td>\n";
		if ($login_fabrica == 19) echo "<td align='center' nowrap>PAGAMENTO</td>\n";
		echo "<td align='center' colspan='2'>A��es</td>\n";

		echo "</tr>\n";
		
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
			$xmao_de_obra            = 0;
			$posto                   = trim(pg_result($res,$i,posto));
			$posto_codigo            = trim(pg_result($res,$i,codigo_posto));
			$posto_nome              = trim(pg_result($res,$i,nome));
			$extrato                 = trim(pg_result($res,$i,extrato));
			$data_geracao            = trim(pg_result($res,$i,data_geracao));
			$pedido_via_distribuidor = trim(pg_result($res,$i,pedido_via_distribuidor));
			$data_extrato            = trim(pg_result($res,$i,data_extrato));
			$mao_de_obra             = trim(pg_result($res,$i,mao_de_obra));
			$mao_de_obra_postos      = trim(pg_result($res,$i,mao_de_obra_postos));
			$extra_mo                = trim(pg_result($res,$i,extra_mo));
			$adicional               = trim(pg_result($res,$i,adicional));
			$pecas                   = trim(pg_result($res,$i,pecas));
			$extra_pecas             = trim(pg_result($res,$i,extra_pecas));
			$extrato                 = trim(pg_result($res,$i,extrato));
			$estado                  = trim(pg_result($res,$i,estado));
			$avulso                  = trim(pg_result($res,$i,avulso));
			$protocolo               = trim(pg_result($res,$i,protocolo));


			$sqlX = "SELECT TO_CHAR (tbl_extrato_financeiro.previsao,'DD/MM/YYYY') FROM tbl_extrato_financeiro WHERE extrato = $extrato";
			$resX = pg_exec ($con,$sqlX);
			$previsao = trim(@pg_result($resX,0,0));
			
			$sqlX = "SELECT TO_CHAR (tbl_extrato_financeiro.pagamento,'DD/MM/YYYY') FROM tbl_extrato_financeiro WHERE extrato = $extrato";
			$resX = pg_exec ($con,$sqlX);
			$pagamento = trim(@pg_result($resX,0,0));

			if (strlen($adicional) == 0) $adicional = 0;
			
			# soma valores
			if ($tipo_posto == "P") {
				$xmao_de_obra += $mao_de_obra_postos;
				$xvrmao_obra   = $mao_de_obra_postos;
			}else{
				$xmao_de_obra += $mao_de_obra;
				$xvrmao_obra   = $mao_de_obra;
			}
			
			if ($xvrmao_obra == 0)  $xvrmao_obra   = $mao_de_obra;
			if ($xmao_de_obra == 0) $xmao_de_obra += $mao_de_obra;
			
			$total = $xmao_de_obra + $pecas;
			
			$data_geracao;
			
			//////////////////////////////////////////////
			$cor = "#F7F5F0"; 
			$btn = 'amarelo';
			if ($i % 2 == 0) 
			{
				$cor = '#F1F4FA';
				$btn = 'azul';
			}

			##### LAN�AMENTO DE EXTRATO AVULSO - IN�CIO #####
			if (strlen($extrato) > 0) {
				$sql = "SELECT count(*) as existe
						FROM   tbl_extrato_lancamento
						WHERE  extrato = $extrato
						and    posto   = $login_posto
						and    fabrica = $login_fabrica";
				$res_avulso = pg_exec($con,$sql);
				
				if (@pg_numrows($res_avulso) > 0) {
					if (@pg_result($res_avulso, 0, existe) > 0) $cor = "#FFE1E1";
				}
			}
			##### LAN�AMENTO DE EXTRATO AVULSO - FIM #####

			echo "<tr class='table_line' style='background-color: $cor;'>\n";
			
			if ($login_fabrica == 2){
				echo "<td align='left' style='padding-left:7px;'>\n";
				echo "<a href='nf_dynacom_consulta.php?extrato=$extrato' target='_blank'>$extrato</a>\n";
				echo " - <a href='nf_servico_dynacom_consulta.php?extrato=$extrato' target='_blank'>NF</a>\n";
				echo "</td>\n";
			}else{
				echo "<td align='left' style='padding-left:7px;padding-right:7px;'>";
				if ($login_fabrica == 1) echo $protocolo;
				else                     echo $extrato;
				echo "</td>\n";
			}
			if ($login_fabrica == 19) {
				echo "<td align='left' style='padding-left:7px;padding-right:7px;'>";
				echo $protocolo;
				echo "</td>\n";
			}
			echo "<td align='left' nowrap><acronym title='$posto_codigo - $posto_nome'>$posto_codigo - " . substr($posto_nome,0,20) . "</acronym></td>\n";
			
			if ($login_fabrica == 3 AND $tipo_posto == "D"){
				echo "<td align='center'><a href='extrato_distribuidor.php?data=$data_extrato'>$data_geracao</a></td>\n";
			}else{
				echo "<td align='center'>$data_geracao</td>\n";
			}
			
			$aguardando_nf = "";
			if ($login_fabrica == 19) {
			    $xvrmao_obra = pg_result ($res,$i,extra_mo) ;
			    $pecas       = pg_result ($res,$i,extra_pecas) ;
			    $total       = $pecas + $xvrmao_obra ;
			    
			    $sql = "SELECT tbl_extrato.extrato FROM tbl_extrato WHERE nf_recebida IS NOT TRUE AND extrato < $extrato and posto = $login_posto and fabrica = $login_fabrica AND aprovado IS NOT NULL ORDER BY extrato " ;
			    $resX = pg_exec ($con,$sql);
#			    if (pg_numrows ($resX) > 0) {
#					$extrato_anterior = pg_result ($resX,0,0);
#					$aguardando_nf = 't';
#					$mensagem_aguardando_nf = "Seu lote ($extrato_anterior) est� com pend�ncia da Nota Fiscal. <br> O lote atual permanecer� bloqueado at� regulariza��o. <br> D�vidas, entrar em contato atrav�s do <b>0800 160212</b>";
#			    }
			}
			
			if ($aguardando_nf == 't') {
			    echo "<td align='left' bgcolor='#9999aa' colspan='4' nowrap style='color: #ffffff'>$mensagem_aguardando_nf</td>";
			}else{
			    echo "<td align='right'  style='padding-right:3px;' nowrap> ". number_format($xvrmao_obra,2,",",".") ."</td>\n";
			    echo "<td align='right'  style='padding-right:3px;' nowrap> ". number_format($pecas,2,",",".") ."</td>\n";
			    echo "<td align='right'  style='padding-right:3px;' nowrap> ". number_format($total,2,",",".") ."</td>\n";
			    echo "<td align='right'  style='padding-right:3px;' nowrap> ". number_format($avulso,2,",",".") ."</td>\n";
			    echo "<td align='right'  style='padding-right:3px;' nowrap> ". $previsao ."</td>\n";
			    if ($login_fabrica == 19) echo "<td align='right'  style='padding-right:3px;' nowrap> ". $pagamento ."</td>\n";

			}
			if ($login_fabrica == 1){
				echo "<td><img src='imagens/btn_imprimirdetalhado_15.gif' onclick=\"javascript: janela=window.open('os_extrato_detalhe_print_blackedecker.php?extrato=$extrato','extrato');\" ALT=\"Imprimir detalhado\" border='0' style=\"cursor:pointer;\"></td>\n";
			}else{
				echo "<td><a href='os_extrato_detalhe.php?extrato=$extrato&posto=$posto'><img src='imagens/btn_detalhar_".$btn.".gif'></a></td>\n";
				if ($login_fabrica == 14) {
					echo "<td nowrap><a href='os_extrato_pecas_retornaveis.php?extrato=$extrato'>Pe�as Trocadas</a></TD>\n";
				}else{
					echo "<td><a href='os_extrato_pecas_retornaveis.php?extrato=$extrato'><img src='imagens/btn_pecasretornaveis_".$btn.".gif'></a></TD>\n";
				}
			}
			echo "</tr>\n";
		}
		echo "<input type='hidden' name='total' value='$i'>";
		
		echo "</form>";
	}else{
		echo "<tr class='table_line'>\n";
		echo "<td align=\"center\">NENHUM EXTRATO FOI ENCONTRADO</td>\n";
		echo "</tr>\n";
		
		echo "<tr>\n";
		echo "<td align=\"center\">\n";
		echo "<br><a href='menu_os.php'><img src='imagens/btn_voltar.gif'></a>";
		echo "</td>\n";
		echo "</tr>\n";
	}
	
	echo "</table>\n";
}

?>

<p><p>

<? include "rodape.php"; ?>
