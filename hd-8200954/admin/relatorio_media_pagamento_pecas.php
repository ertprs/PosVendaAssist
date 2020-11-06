<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "financeiro";
include "autentica_admin.php";

include "funcoes.php";
//if($ip <> '201.43.201.204')exit;
if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);
if (strlen($_GET["acao"]) > 0)  $acao = strtoupper($_GET["acao"]);

$msg = "";

if (strlen($acao) > "PESQUISAR") {
	##### Pesquisa entre datas #####
	if (strlen(trim($_POST["data_inicial"])) > 0) $x_data_inicial = trim($_POST["data_inicial"]);
	if (strlen(trim($_GET["data_inicial"])) > 0)  $x_data_inicial = trim($_GET["data_inicial"]);
	if (strlen(trim($_POST["data_final"])) > 0)   $x_data_final   = trim($_POST["data_final"]);
	if (strlen(trim($_GET["data_final"])) > 0)    $x_data_final   = trim($_GET["data_final"]);
	if (strlen($x_data_inicial)> 0 && strlen($x_data_final) > 0) {
	
		//Início Validação de Datas
		if(strlen($x_data_inicial) > 0){
			$dat = explode ("/", $x_data_inicial );//tira a barra
				$d = $dat[0];
				$m = $dat[1];
				$y = $dat[2];
				if(!checkdate($m,$d,$y)) $msg = "Data Inválida";
		}
		if(strlen($x_data_final) > 0){
			$dat = explode ("/", $x_data_final );//tira a barra
				$d = $dat[0];
				$m = $dat[1];
				$y = $dat[2];
				if(!checkdate($m,$d,$y)) $msg = "Data Inválida";
		}
		if(strlen($msg)==0){
			$d_ini = explode ("/", $x_data_inicial);//tira a barra
			$x_data_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...


			$d_fim = explode ("/", $x_data_final);//tira a barra
			$x_data_final = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

			if($x_data_final < $x_data_inicial){
				$msg = "Data Inválida.";
			}

			//Fim Validação de Datas
		}
		
	}

	else{
		$msg = "Data Inválida.";
	}
	##### Pesquisa de produto #####
	if (strlen(trim($_POST["posto_codigo"])) > 0) $posto_codigo  = trim($_POST["posto_codigo"]);
	if (strlen(trim($_GET["posto_codigo"])) > 0)  $posto_codigo  = trim($_GET["posto_codigo"]);
	if (strlen(trim($_POST["posto_nome"])) > 0)   $posto_nome    = trim($_POST["posto_nome"]);
	if (strlen(trim($_GET["posto_nome"])) > 0)    $posto_nome    = trim($_GET["posto_nome"]);
	if (strlen($posto_codigo) > 0 && strlen($posto_nome) > 0) {
		$sql =	"SELECT tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome                ,
						tbl_posto.posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica
				AND   tbl_posto_fabrica.codigo_posto = '$posto_codigo';";
		$res = pg_exec($con,$sql);
		if (pg_numrows($res) == 1) {
			$posto        = pg_result($res,0,posto);
			$posto_codigo = pg_result($res,0,codigo_posto);
			$posto_nome   = pg_result($res,0,nome);
		}else{
			$msg .= " Posto não encontrado. ";
		}

	}
	
	##### Situação do Extrato #####
	if (strlen(trim($_POST["situacao"])) > 0) $situacao = trim($_POST["situacao"]);
	if (strlen(trim($_GET["situacao"])) > 0)  $situacao = trim($_GET["situacao"]);
}

	if($login_fabrica==1){
		$tipo_posto                              = trim($_POST ['tipo_posto']);
		if (strlen(trim($_POST["garantia"])) > 0) $garantia = trim($_POST["garantia"]);
		if (strlen(trim($_GET["garantia"])) > 0)  $garantia= trim($_GET["garantia"]);
	
	}

$layout_menu = "auditoria";
$title = "RELATÓRIO DE PAGAMENTOS";

include "cabecalho.php";
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
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
</style>

<? 
	include "javascript_pesquisas.php"; 
	include "javascript_calendario.php";
?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>

<script LANGUAGE="JavaScript">

	$(function(){
		$("#data_inicial").datePicker({startDate:'01/01/2000'});
		$("#data_final").datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput('99/99/9999');
		$("#data_final").maskedinput('99/99/9999');
	});

	function Redirect(pedido) {
		window.open('detalhe_pedido.php?pedido=' + pedido,'1', 'height=400,width=750,location=no,scrollbars=yes,menubar=no,toolbar=no,resizable=no')
	}
</script>

<br>



<form name="frm_consulta" method="post" action="<?echo $PHP_SELF?>">

<input type="hidden" name="acao">

<table width="700" align="center" border="0" cellspacing="0" cellpadding="2" class='formulario'>
	<? if (strlen($msg) > 0) { ?>

		<tr class="msg_erro">
			<td colspan="4"><?echo $msg?></td>
		</tr>
	
	<? } ?>
	<tr class="titulo_tabela">
		<td colspan="4">Parâmetros de Pesquisa</td>
	</tr>
	<tr>
		<td width="150">&nbsp;</td>
		<td>Data Inicial</td>
		<td>Data Final</td>
		<td width="150">&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>
			<input size="12" maxlength="10" TYPE="text" NAME="data_inicial" id="data_inicial" value="<?if (strlen($data_inicial) == 0) echo 'dd/mm/aaaa'; else echo substr($data_inicial,0,10);?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') { this.value = ''; }" class="frm">
			
		</td>
		<td>
			<input size="12" maxlength="10" TYPE="text" NAME="data_final"  id="data_final"value="<?if (strlen($data_final) == 0) echo 'dd/mm/aaaa'; else echo substr($data_final,0,10);?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') { this.value = ''; }" class="frm">
			
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan="4"></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>Código do Posto</td>
		<td>Nome do Posto</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>
			<input type="text" name="posto_codigo" size="8" value="<?echo $posto_codigo?>" class="frm">
			<img src="imagens/lupa.png" style="cursor: hand;" align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.posto_codigo, document.frm_consulta.posto_nome,'codigo');">
		</td>
		<td>
			<input type="text" name="posto_nome" size="15" value="<?echo $posto_nome?>" class="frm">
			<img src="imagens/lupa.png" style="cursor: hand;" align='absmiddle' alt="Clique aqui para pesquisas postos pelo nome" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.posto_codigo, document.frm_consulta.posto_nome,'nome');">
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan="4"></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td colspan="2" >
			<fieldset style="width:260px;">
				<legend>Situação do Extrato</legend>
				<table width="100%" >
					<tr>		
						<td>
							<input type="radio" name="situacao" value="GERACAO" <? if ($situacao == "GERACAO") echo "checked"; ?>> Aberto
						</td>
						<td>
							<input type="radio" name="situacao" value="APROVACAO" <? if ($situacao == "APROVACAO") echo "checked"; ?>> Aprovado
						</td>
					</tr>
					<tr>
						<td colspan="2" align="left>">
							<input type="radio" name="situacao" value="FINANCEIRO" <? if (strlen($situacao) == 0 || $situacao == "FINANCEIRO") echo "checked"; ?>> Enviado p/ financeiro
						</td>
					</tr>
				</table>
			</fieldset>
		</td>
		<td>&nbsp;</td>
	</tr>
	
	<tr>
		<td colspan="4"></td>
	</tr>
<? if ($login_fabrica==1) {?>
	<tr>
				<td>&nbsp;</td>
		<td colspan="2" >
			<fieldset style="width:260px;">
				<legend>Garantia</legend>
				<table width="100%">
					<tr>
						<td>
							<input type="radio" name="garantia" value="t" <? if ($garantia == "t") echo "checked"; ?>> Sim
							&nbsp;&nbsp;
							<input type="radio" name="garantia" value="f" <? if ($garantia == "f") echo "checked"; ?>> Não
						</td>
					</tr>
					<tr>
						<td colspan="2" >
							<input type="radio" name="garantia" value="" <? if (strlen($garantia) == 0) echo "checked"; ?>> Todos
						</td>
					</tr>
				</table>
			</fieldset>
			
		</td>
		<td>&nbsp;</td>
	</tr>
	
	<tr>
		<td colspan="4"></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td colspan="2" >
			TIPO DO POSTO
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
	<td colspan="2" >
			<select name='tipo_posto' size='1' class="frm">
				<option value=""> </option>
				<?
					$sql = "SELECT *
							FROM   tbl_tipo_posto
							WHERE  tbl_tipo_posto.fabrica = $login_fabrica
							ORDER BY tbl_tipo_posto.descricao";
					$res = pg_exec ($con,$sql);
						for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
							echo "<option value='" . pg_result ($res,$i,tipo_posto) . "' ";
								if ($tipo_posto == pg_result ($res,$i,tipo_posto)) echo " selected ";
							echo ">";
							echo pg_result ($res,$i,descricao);
					echo "</option>";
					}
				?>
			</select>
		</td>
		<td>&nbsp;</td>
	</tr>
<? } ?>
	<tr><td colspan="4">&nbsp;</td></tr>
	<tr>
		<td colspan="4" align="center"><input type="button" style="background:url(imagens_admin/btn_pesquisar_400.gif); width:400px;cursor:pointer;" value="&nbsp;" onclick="javascript: document.frm_consulta.acao.value='PESQUISAR'; document.frm_consulta.submit();" alt="Preencha as opções e clique aqui para pesquisar"></td>
	</tr>
</table>

</form>

<?
if (strlen($msg) == 0 && strlen($acao) > 0) {
	/*
	$sql = "SELECT  DISTINCT
					tbl_extrato.extrato												,
					tbl_extrato.protocolo											,
					tbl_extrato.total												,
					TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_extrato  ,
					TO_CHAR(tbl_extrato.aprovado,'DD/MM/YYYY')     AS data_aprovado ,
					TO_CHAR(tbl_extrato_financeiro.data_envio,'DD/MM/YYYY')                                 				                               AS data_financeiro ,
					(TO_CHAR(tbl_extrato_financeiro.data_envio,'YYYY-MM-DD')::date - TO_CHAR(tbl_extrato.data_geracao,'YYYY-MM-DD')::date) AS dias   ,
					tbl_posto_fabrica.codigo_posto                 AS posto_codigo  ,
					tbl_posto.nome                                 AS posto_nome    ,
					tbl_extrato_extra.nota_fiscal_mao_de_obra						,
					(SELECT	COUNT(*) AS qtd_os 
						FROM tbl_os 
						JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
						JOIN tbl_linha   ON tbl_produto.linha = tbl_linha.linha
						JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os
						JOIN tbl_extrato  ON tbl_os_extra.extrato = tbl_extrato.extrato
						JOIN tbl_extrato_financeiro ON tbl_extrato.extrato = tbl_extrato_financeiro.extrato ";
						if ($situacao == "FINANCEIRO") $sql .= " WHERE tbl_extrato_financeiro.data_envio BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'
																AND tbl_extrato_financeiro.data_envio IS NOT NULL";
						
						if ($situacao == "GERACAO") $sql .= " WHERE tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'
															AND tbl_extrato.aprovado IS NULL";
															
						if ($situacao == "APROVACAO") $sql .= " WHERE tbl_extrato.aprovado BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'
																AND tbl_extrato_financeiro.data_envio IS NULL
																AND tbl_extrato.aprovado IS NOT NULL";

						if (strlen($posto) > 0) $sql .= " AND tbl_os.posto = $posto";
						$sql .=	" AND tbl_extrato.fabrica = $login_fabrica ";

		$sql .= ") AS qtd_os
			FROM tbl_extrato
			JOIN tbl_extrato_extra           on tbl_extrato_extra.extrato = tbl_extrato.extrato
			LEFT JOIN tbl_extrato_financeiro ON tbl_extrato_financeiro.extrato = tbl_extrato.extrato
			LEFT JOIN tbl_extrato_lancamento ON tbl_extrato.extrato = tbl_extrato_lancamento.extrato
			JOIN tbl_posto                   ON tbl_posto.posto = tbl_extrato.posto
			JOIN tbl_posto_fabrica           ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica";

	if ($situacao == "FINANCEIRO") $sql .= " WHERE tbl_extrato_financeiro.data_envio BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'
											AND tbl_extrato_financeiro.data_envio IS NOT NULL";
	
	if ($situacao == "GERACAO") $sql .= " WHERE tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'
										AND tbl_extrato.aprovado IS NULL";
										
	if ($situacao == "APROVACAO") $sql .= " WHERE tbl_extrato.aprovado BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'
											AND tbl_extrato_financeiro.data_envio IS NULL
											AND tbl_extrato.aprovado IS NOT NULL";

	if (strlen($posto) > 0) $sql .= " AND tbl_posto.posto = $posto";
// takashi 22-05-07 hd 2381  -  left join tbl_extrato_lancamento tbl_os, tbl_os_extra
//			LEFT JOIN tbl_os_extra           ON tbl_os_extra.extrato = tbl_extrato.extrato
			//LEFT JOIN tbl_os                 ON tbl_os.os  = tbl_os_extra.os
	$sql .=	" AND tbl_extrato.fabrica = $login_fabrica
			GROUP BY  tbl_extrato.extrato               ,
						tbl_extrato.protocolo             ,
						tbl_extrato.total                 ,
						tbl_extrato.data_geracao          ,
						tbl_extrato.aprovado              ,
						tbl_extrato_financeiro.data_envio ,
						tbl_posto_fabrica.codigo_posto    ,
						tbl_posto.nome                    ,
						tbl_extrato_extra.nota_fiscal_mao_de_obra               
			ORDER BY tbl_posto_fabrica.codigo_posto";
*/
if($login_fabrica==1){
	$sql_garantia   = "1=1";
	$sql_tipo_posto = "1=1";
	$sql_garantia_condicao ="1=1";
	if(strlen($garantia) > 0){
		$sql_garantia =" tbl_posto_fabrica.reembolso_peca_estoque='$garantia'";
		if($garantia == 't'){
			$sql_garantia_valor=" ,tbl_extrato_lancamento.valor ";
			$sql_garantia_join =" LEFT JOIN tbl_extrato_lancamento ON temp_media_pg2.extrato = tbl_extrato_lancamento.extrato ";
			$sql_garantia_condicao=" tbl_extrato_lancamento.lancamento= 47";
		}
	}
	if(strlen($tipo_posto)> 0){
		$sql_tipo_posto=" tbl_posto_fabrica.tipo_posto='$tipo_posto'";
	}
}

$sql_posto = "1=1";
if (strlen($posto) > 0) $sql_posto = " tbl_posto.posto = $posto ";

if ($situacao == "FINANCEIRO") $sql_situacao = " WHERE tbl_extrato_financeiro.data_envio BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'
												 AND tbl_extrato_financeiro.data_envio IS NOT NULL";
		
if ($situacao == "GERACAO")    $sql_situacao = " WHERE tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'
												 AND tbl_extrato.aprovado IS NULL";
											
if ($situacao == "APROVACAO")  $sql_situacao = " WHERE tbl_extrato.aprovado BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'
												 AND tbl_extrato_financeiro.data_envio IS NULL
												 AND tbl_extrato.aprovado IS NOT NULL";

$sql = "
	SELECT DISTINCT tbl_extrato.extrato                                                                                                    ,
			tbl_extrato.data_geracao                                                                                                       ,
			tbl_extrato.protocolo                                                                                                          ,
			tbl_extrato.total                                                                                                              ,
			TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_extrato                                                                 ,
			tbl_posto.nome as posto_nome                                                                                                   ,
			tbl_posto_fabrica.codigo_posto  as posto_codigo
			into temp temp_media_pg
			FROM tbl_extrato 
			LEFT JOIN tbl_extrato_financeiro USING (extrato)
			JOIN tbl_posto ON tbl_extrato.posto = tbl_posto.posto
			JOIN tbl_posto_fabrica ON tbl_extrato.posto=tbl_posto_fabrica.posto and tbl_extrato.fabrica = tbl_posto_fabrica.fabrica
			$sql_situacao
			AND $sql_garantia
			AND $sql_tipo_posto
			AND $sql_posto
			AND tbl_extrato.fabrica = $login_fabrica  ;
	
	CREATE INDEX temp_media_pg_extrato ON temp_media_pg(extrato);

	SELECT	DISTINCT temp_media_pg.extrato        ,
			temp_media_pg.data_geracao            ,
			temp_media_pg.protocolo               ,
			temp_media_pg.total                   ,
			temp_media_pg.data_extrato            ,
			temp_media_pg.posto_nome              ,
			temp_media_pg.posto_codigo            ,
			COUNT(tbl_os_extra.os) AS qtde_os     ,
			SUM (tbl_os.pecas) AS pecas
		into temp temp_media_pg2
		FROM temp_media_pg
		JOIN tbl_os_extra      ON tbl_os_extra.extrato = temp_media_pg.extrato
		JOIN tbl_os            ON tbl_os_extra.os = tbl_os.os
		GROUP BY	temp_media_pg.extrato         ,
					temp_media_pg.data_geracao    ,
					temp_media_pg.protocolo       ,
					temp_media_pg.total           ,
					temp_media_pg.data_extrato    ,
					temp_media_pg.posto_nome      ,
					temp_media_pg.posto_codigo    ;

	CREATE INDEX temp_media_pg2_extrato ON temp_media_pg2(extrato);

	SELECT	DISTINCT temp_media_pg2.extrato                 ,
			temp_media_pg2.data_geracao                     ,
			temp_media_pg2.protocolo                        ,
			temp_media_pg2.total                            ,
			temp_media_pg2.data_extrato                     ,
			temp_media_pg2.qtde_os                          ,
			temp_media_pg2.pecas                            ,
			temp_media_pg2.posto_nome                       ,
			temp_media_pg2.posto_codigo                     ,
			tbl_extrato_extra.nota_fiscal_mao_de_obra       
			$sql_garantia_valor
	FROM temp_media_pg2
	$sql_garantia_join
	JOIN tbl_extrato_extra           ON temp_media_pg2.extrato = tbl_extrato_extra.extrato 
	where $sql_garantia_condicao
	ORDER BY temp_media_pg2.posto_codigo; ";
	
	//echo $sql;
	$res = pg_exec($con,$sql);

//if ($ip == '189.18.85.78') {echo nl2br($sql); exit;}
//	if (getenv("REMOTE_ADDR") == "200.246.168.219") echo nl2br($sql) . "<br>" . pg_numrows(pg_exec($con,$sql));
//echo $sql;
	##### PAGINAÇÃO - INÍCIO #####
/*
	$sqlCount  = "SELECT COUNT(*) FROM (" . $sql . ") AS count";

	require "_class_paginacao.php";

	// definicoes de variaveis
	$max_links = 11;				// máximo de links à serem exibidos
	$max_res   = 30;				// máximo de resultados à serem exibidos por tela ou pagina
	$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
	$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

	$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");
	##### PAGINAÇÃO - FIM #####
*/
	if (pg_numrows($res) > 0) {
		/* takashi 22-05-07 hd 2381*/
		echo "<BR><BR><table width='700' height=16 border='0' cellspacing='1' cellpadding='0' align='center'>";
		echo "<tr>";
		echo "<td align='center' width='16' bgcolor='#FFE1E1'>&nbsp;</td>";
		echo "<td align='left'><font size=1><b>&nbsp; Extrato Avulso</b></font></td>";
		echo "</tr>";
		echo "</table>";
		/* takashi 22-05-07 hd 2381*/
		echo "<BR><table border='1' cellpadding='2' cellspacing='1' class='tabela' align='center'>";
		echo "<tr class='titulo_coluna' height='15'>";
		echo "<td>Código</td>";
		echo "<td>Posto</td>";
		echo "<td>Extrato</td>";
		echo "<td>NF Autorizado</td>";
		echo "<td>Total</td>";
		echo "<td>Total Peças</td>";
		if($garantia == 't'){
			echo "<td>Total Avulso</td>";
		}
		echo "<td>Geração</td>";
		echo "<td>Qtd.OS</td>";
		echo "</tr>";
		$total_final = "";
		$total_qtd_os = 0;
		for ($x = 0; $x < pg_numrows($res); $x++) {
			$extrato           = trim(pg_result($res,$x,extrato));
			$protocolo         = trim(pg_result($res,$x,protocolo));
			$total             = trim(pg_result($res,$x,total));
			$qtd_os             = trim(pg_result($res,$x,qtde_os));
			if(strlen($qtd_os) == 0) $qtd_os = 0;
			$data_extrato      = trim(pg_result($res,$x,data_extrato));
			$posto_codigo      = trim(pg_result($res,$x,posto_codigo));
			$posto_nome        = trim(pg_result($res,$x,posto_nome));
			$nf_mao_de_obra    = trim(pg_result($res,$x,nota_fiscal_mao_de_obra));
			if($garantia == 't'){
				$valor             = trim(pg_result($res,$x,valor));
			}
			$pecas             = trim(pg_result($res,$x,pecas));

			$cor = ($x % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;
			
			/* takashi 22-05-07 hd 2381*/
			if (strlen($extrato) > 0) {
				$sql = "SELECT count(*) as existe
						FROM   tbl_extrato_lancamento
						WHERE  extrato = $extrato
						and    fabrica = $login_fabrica";
				$res_avulso = pg_exec($con,$sql);

				if (@pg_numrows($res_avulso) > 0) {
					if (@pg_result($res_avulso, 0, existe) > 0) $cor = "#FFE1E1";
				}


			}
			/* takashi 22-05-07 hd 2381*/

			$total_final       = $total + $total_final;
			$total_qtd_os      = $total_qtd_os + $qtd_os;
			$total_pecas_final = $pecas + $total_pecas_final;
			$total_avulso_final= $valor + $total_avulso_final;

			echo "<tr height='15' bgcolor='$cor'>";
			echo "<td nowrap>" . $posto_codigo . "</td>";
			echo "<td nowrap align='left'>" . $posto_nome . "</td>";
			echo "<td nowrap>" .  $protocolo . "</td>";
			echo "<td nowrap>" . $nf_mao_de_obra . "</td>";
			echo "<td nowrap align='right'>R$ " . number_format($total,2,",",".") . "</td>";
			echo "<td nowrap align='right'>R$ " . number_format($pecas,2,",",".") . "</td>";
			if($garantia == 't'){
				echo "<td nowrap align='right'>R$ " . number_format($valor,2,",",".") . "</td>";
			}
			echo "<td nowrap>" . $data_extrato . "</td>";
			echo "<td nowrap>" . $qtd_os . "</td>";
			echo "</tr>";
			
		}
		
	
			echo "<tr  height='15' bgcolor='$cor'>";
			echo "<td nowrap>&nbsp;</td>";
			echo "<td nowrap align='left'>Total</td>";
			echo "<td nowrap>&nbsp;</td>";
			echo "<td nowrap>&nbsp;</td>";
			echo "<td nowrap align='right'>R$ " . number_format($total_final,2,",",".") . "</td>";
			echo "<td nowrap align='right'>R$ " . number_format($total_pecas_final,2,",",".") . "</td>";
			if($garantia == 't'){
				echo "<td nowrap align='right'>R$ " . number_format($total_avulso_final,2,",",".") . "</td>";
			}
			echo "<td nowrap>&nbsp;</td>";
			echo "<td nowrap>" . $total_qtd_os . "</td>";
			echo "</tr>";
		echo "</table>";
	} else {
		echo " Não foi encontrado nenhum resultado.";
	}

//echo $total_final;
/*
	##### PAGINAÇÃO - INÍCIO #####
	// links da paginacao
	echo "<br>";
	echo "<div>";

	if($pagina < $max_links) $paginacao = pagina + 1;
	else                     $paginacao = pagina;

	// paginacao com restricao de links da paginacao

	// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
	$todos_links		= $mult_pag->Construir_Links("strings", "sim");

	// função que limita a quantidade de links no rodape
	$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

	for ($n = 0; $n < count($links_limitados); $n++) {
		echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
	}

	echo "</div>";

	$resultado_inicial = ($pagina * $max_res) + 1;
	$resultado_final   = $max_res + ( $pagina * $max_res);
	$registros         = $mult_pag->Retorna_Resultado();

	$valor_pagina   = $pagina + 1;
	$numero_paginas = intval(($registros / $max_res) + 1);

	if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

	if ($registros > 0){
		echo "<br>";
		echo "<div>";
		echo "Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.";
		echo " <font color='#cccccc' size='1'>(Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)</font>";
		echo "</div>";
	}
	##### PAGINAÇÃO - FIM #####
*/
}

echo "<br>";

include "rodape.php";
?>
