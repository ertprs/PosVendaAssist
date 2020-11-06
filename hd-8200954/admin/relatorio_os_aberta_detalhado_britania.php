<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="auditoria";


$gera_automatico = trim($_GET["gera_automatico"]);

if ($gera_automatico != 'automatico'){
	include "autentica_admin.php";
}
include "gera_relatorio_pararelo_include.php";

include 'funcoes.php';

$layout_menu = "auditoria";
$title       = "RELATÓRIO DE OSs EM ABERTO";
include "cabecalho.php";
include "javascript_pesquisas.php";

?>

<script language="JavaScript" src="js/cal2.js"></script>
<script language="JavaScript" src="js/cal_conf2.js"></script>

<script language="JavaScript">
function fnc_pesquisa_posto2 (campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&proximo=";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
	else
		alert('Preencha toda ou parte da informação para fazer a pesquisa!');
}

function fnc_pesquisa_produto (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}


	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.focus();
	}
	else
		alert('Preencha toda ou parte da informação para fazer a pesquisa!');
}



</script>

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
.espaco {padding-left:140px;}
</style>

<?

if (strlen($_POST["btn_acao"]) > 0 ) $btn_acao = trim($_POST["btn_acao"]);
if (strlen($_GET["btn_acao"]) > 0 )  $btn_acao = trim($_GET["btn_acao"]);

if(strlen($btn_acao) > 0){
	if (strlen($_POST["posto_codigo"]) > 0 ) $posto_codigo = trim($_POST["posto_codigo"]);
	if (strlen($_GET["posto_codigo"]) > 0 )  $posto_codigo = trim($_GET["posto_codigo"]);

	if (strlen($_POST["posto_nome"]) > 0 ) $posto_nome = trim($_POST["posto_nome"]);
	if (strlen($_GET["posto_nome"]) > 0 )  $posto_nome = trim($_GET["posto_nome"]);

	if (strlen($_POST["linha"]) > 0 ) $linha = trim($_POST["linha"]);
	if (strlen($_GET["linha"]) > 0 )  $linha = trim($_GET["linha"]);

	if (strlen($_POST["qtde_dias"]) > 0 ) $qtde_dias = trim($_POST["qtde_dias"]);
        if (strlen($_GET["qtde_dias"]) > 0 )  $qtde_dias = trim($_GET["qtde_dias"]);

    $qtde_dias = intval($qtde_dias);
	if($qtde_dias == 0) {
		$msg_erro = "É obrigatório o preenchimento da quantidade de dias";
	}

	if($qtde_dias < 6 AND empty($msg_erro)) {
		$msg_erro = "A quantidade de dias deve ser maior ou igual a 6";
	}

	$intervencao = $_GET['intervencao'];
	$excluidas   = $_GET['excluidas'];
	if ( empty($excluidas) ) {
		$excluidas = $_POST['excluidas'];
	}

	if ( empty($intervencao) ) {
		$intervencao = $_POST['intervencao'];
	}

	if (strlen(trim($_POST["cancelada_90_dias"])) > 0) $cancelada_90_dias = trim($_POST["cancelada_90_dias"]);
	if (strlen(trim($_GET["cancelada_90_dias"])) > 0)  $cancelada_90_dias = trim($_GET["cancelada_90_dias"]);

	if (strlen(trim($_POST["cancelada_45_dias"])) > 0) $cancelada_45_dias = trim($_POST["cancelada_45_dias"]);
	if (strlen(trim($_GET["cancelada_45_dias"])) > 0)  $cancelada_45_dias = trim($_GET["cancelada_45_dias"]);

	if (strlen(trim($_POST["cancelada_manual"])) > 0) $cancelada_manual = trim($_POST["cancelada_manual"]);
	if (strlen(trim($_GET["cancelada_manual"])) > 0)  $cancelada_manual = trim($_GET["cancelada_manual"]);
}
?>

<?
if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {
	include "gera_relatorio_pararelo.php";
}

if ($gera_automatico != 'automatico' and strlen($msg_erro)==0){
	include "gera_relatorio_pararelo_verifica.php";
}
?>

<?if (strlen($msg_erro) > 0) {?>
<table width='700' align='center' border='0' cellspacing='1' cellpadding='0'>
	<tr>
		<td class="msg_erro"><?echo $msg_erro?></td>
	</tr>
</table>
<?}?>

<form name='frm_os_posto' action='<? echo $PHP_SELF ?>' method="POST">
<input type="hidden" name='btn_acao' value="">

<table width='700' class='formulario' border='0' cellpadding='0' cellspacing='1' align='center'>
	<tr>
		<td class='titulo_tabela' colspan="2">Parâmetros de Pesquisa</td>
	</tr>
	<tr>
		<td align='left' colspan="2" class="espaco">
			Linha<br />
			<?
			$sql = "SELECT  *
					FROM    tbl_linha
					WHERE   tbl_linha.fabrica = $login_fabrica
					ORDER BY tbl_linha.nome;";
			$res = pg_exec ($con,$sql);
			if (pg_numrows($res) > 0) {
				echo "<select name='linha' class='frm'>\n";
				echo "<option value=''>ESCOLHA</option>\n";
				for ($x = 0 ; $x < pg_numrows($res) ; $x++){
					$aux_linha = trim(pg_result($res,$x,linha));
					$aux_nome  = trim(pg_result($res,$x,nome));
					echo "<option value='$aux_linha'";
					if ($linha == $aux_linha){
						echo " SELECTED ";
						$mostraMsgLinha = "<br> da LINHA $aux_nome";
					}
					echo ">$aux_nome</option>\n";
				}
				echo "</select>\n&nbsp;";
			}
		?>
		</td>
	</tr>
	<tr>
		<td style="width:150px;" class="espaco">
			Cod. Posto<br />
			<input class="frm" type="text" name="posto_codigo" size="13" value="<? echo $posto_codigo ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm_os_posto.posto_codigo,document.frm_os_posto.posto_nome,'codigo')" <? } ?>>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_os_posto.posto_codigo,document.frm_os_posto.posto_nome,'codigo')">
		</td>
		<td>
			Nome do Posto<br />
			<input class="frm" type="text" name="posto_nome" size="30" value="<? echo $posto_nome ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm_os_posto.posto_codigo,document.frm_os_posto.posto_nome,'nome')" <? } ?>>&nbsp;<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_os_posto.posto_codigo,document.frm_os_posto.posto_nome,'nome')" style="cursor:pointer;">
		</td>

	</tr>
	<tr>
		<td align='left' class="espaco">
			Dias em Aberto<br />
			<input class="frm" type="text" name="qtde_dias" size="13" maxlength="4" value="<? echo $qtde_dias ?>">
		</td>
	</tr>
	<tr>
		<td colspan='2' class="espaco">
			<fieldset style="width:300px;">
				<legend>Situação das Peças</legend>
				<table>
					<tr>
						<td>
						<input type='radio' name='situacao' value='c'<?if($situacao=='c')echo "checked";?>> Com Peça
						</td>
						<td>
						<input type='radio' name='situacao' value='s'<?if($situacao=='s')echo "checked";?>> Sem Peça
						</td>
						<td>
						<input type='radio' name='situacao' value='a'<?if($situacao=='a')echo "checked";?>> Ambos
						</td>
					</tr>
				</table>
				</fieldset>
				<fieldset style="width:300px;">
					<legend>Situação dos Consertos</legend>
					<table>
					<tr>
						<td>
							<INPUT TYPE="radio" NAME="conserto" value="t"<?if($conserto=='t')echo "checked";?>>Todas
						</td>
						<td>
							<INPUT TYPE="radio" NAME="conserto" value="c"<?if($conserto=='c')echo "checked";?>>Consertadas
						</td>
						<td>
							<INPUT TYPE="radio" NAME="conserto" value="n"<?if($conserto=='n')echo "checked";?>>Não Consertadas
						</td>
					</tr>
				</table>
				</fieldset>
				<input type='checkbox' name='intervencao' value='t' <?if($intervencao=='t')echo "checked";?>> OSs que não estão em intervenção<br />
				<input type='checkbox' name='excluidas' value='t' <?if($excluidas=='t')echo "checked";?>> Desconsiderar OSs excluídas <br />
				<input type='checkbox' name='cancelada_90_dias' value='t' <? if ($cancelada_90_dias == 't') echo "checked";?> /> Desconsiderar OSs Canceladas (OS aberta a mais 90 dias - Cancelada)<br />
				<input type='checkbox' name='cancelada_45_dias' value='t' <?if ($cancelada_45_dias == 't') echo "checked";?>> Desconsiderar OSs Canceladas (OS aberta a mais 45 dias - Cancelada)<br>
				<input type='checkbox' name='cancelada_manual' value='t' <?if ($cancelada_manual == 't') echo "checked";?>> Desconsiderar OSs Canceladas (Cancelamento manual)
					

		</td>
	</tr>
	<tr>
		<td colspan="2" align="center" style="padding:10px 0 10px;">
			<input type="button" value="Pesquisar" style="cursor:pointer" onclick="javascript: if (document.frm_os_posto.btn_acao.value == '' ) { document.frm_os_posto.btn_acao.value='continuar' ; document.frm_os_posto.submit() } else { alert ('Aguarde submissão') }" />
		</td>
	</tr>
</table>

</form>

<br>

<?
flush();
if (strlen($btn_acao) > 0 AND strlen($msg_erro) == 0){

	if (strlen($posto_codigo) > 0){
		$sqlPosto =	"SELECT posto
					FROM tbl_posto_fabrica
					WHERE codigo_posto = '$posto_codigo'
					AND fabrica = $login_fabrica";
		$res = pg_exec($con,$sqlPosto);
		if (pg_numrows($res) == 1){
			$posto = pg_result($res,0,0);
			$sql_posto = " AND tbl_os.posto = $posto ";
			$sql_posto_bi = " AND bi_os.posto = $posto ";
		}
	}

	if (strlen($situacao) > 0) {
		if($situacao=='s') $cond_sem_peca = " AND temp_os_aberta.os_produto IS NULL ";
		if($situacao=='c') $cond_sem_peca = " AND temp_os_aberta.os_produto IS NOT NULL ";
		if($situacao=='a') $cond_sem_peca = " AND (temp_os_aberta.os_produto IS NULL OR temp_os_aberta.os_produto IS NOT NULL) ";
	}else {
		$cond_sem_peca = "";
	}

	if (strlen($conserto) > 0) {
		if ($conserto=='t') $cond_data_conserto = "AND 1=1 ";
		if ($conserto=='c') $cond_data_conserto = "AND temp_os_aberta.data_conserto is not null ";
		if ($conserto=='n') $cond_data_conserto = "AND temp_os_aberta.data_conserto is null ";
	} else {
		$cond_data_conserto = "";
	}

	if (strlen($intervencao) > 0 AND $login_fabrica == 3) {
//		$join_intervencao = "LEFT JOIN tbl_os_status using(os)";
		$temp_intervencao = " AND tbl_os.os NOT IN (SELECT os FROM tbl_os_status WHERE tbl_os_status.fabrica_status=$login_fabrica AND tbl_os.os = tbl_os_status.os AND status_os IN (62,64,65)) ";
	}

	$temp_cancelada_90_dias = " ";
	if (strlen($cancelada_90_dias) > 0 AND $login_fabrica == 3) {
		$sql_cancelada_90_dias = "SELECT os
					  INTO TEMP tmp_canceladas_90_dias
					  FROM tbl_os_status
					  WHERE status_os = 126 and fabrica_status=$login_fabrica;

					  CREATE INDEX tmp_canceladas_90_dias_os ON tmp_canceladas_90_dias(os);";

		$temp_cancelada_90_dias = "
						AND tbl_os.os NOT IN ( SELECT os
								FROM tmp_canceladas_90_dias) ";
	}

	$temp_cancelada_45_dias = " ";
	if (strlen($cancelada_45_dias) > 0 AND $login_fabrica == 3) {
		$sql_cancelada_45_dias="SELECT os
					INTO TEMP tmp_canceladas_45_dias
					FROM tbl_os_status
					WHERE status_os = 143 and fabrica_status=$login_fabrica;

					CREATE INDEX tmp_canceladas_45_dias_os ON tmp_canceladas_45_dias(os);";


		$temp_cancelada_45_dias = "
						AND tbl_os.os NOT IN ( SELECT os
								FROM tmp_canceladas_45_dias ) ";
	}

	$temp_cancelada_manual = " ";
	if (strlen($cancelada_manual) > 0 AND $login_fabrica == 3) {
		$sql_cancelada_manual="SELECT os
					INTO TEMP tmp_cancelada_manual
					FROM tbl_os_status
					WHERE status_os = 246 and fabrica_status=$login_fabrica;

					CREATE INDEX tmp_cancelada_manual_os ON tmp_cancelada_manual(os);";


		$temp_cancelada_manual = "
						AND tbl_os.os NOT IN ( SELECT os
								FROM tmp_cancelada_manual ) ";
	}

	if ($excluidas) {
		$sql_excluida = " AND temp_os_aberta.os NOT IN (SELECT os FROM tbl_os_excluida WHERE tbl_os_excluida.fabrica=$login_fabrica AND os=temp_os_aberta.os)";
		$sql_excluida .= " AND temp_os_aberta.excluida <> 't'";
	}

	#$cond_ano='';
	#if ((strlen($cancelada_90_dias) == 0 AND $login_fabrica == 3) AND
	#    (strlen($cancelada_45_dias) == 0 AND $login_fabrica == 3)) 
	#{
		$cond_ano=" AND tbl_os.data_abertura > current_date - INTERVAL '1 year'";
	#}


 	$sql_bi = "SELECT os 
 				INTO TEMP TABLE temp_os_aberta_bi
 				FROM bi_os
				WHERE
					data_abertura > current_date - INTERVAL '1 year'
					AND data_abertura < current_date - INTERVAL '$qtde_dias days'
					AND fabrica = $login_fabrica
					AND data_fechamento IS NULL
					AND posto <> 6359
					{$sql_posto_bi};

				CREATE INDEX temp_os_aberta_bi_os ON temp_os_aberta_bi(os);";

	$sql = "
			$sql_bi;

			$sql_cancelada_90_dias

			$sql_cancelada_45_dias

			$sql_cancelada_manual

			SELECT	tbl_os.os                                                               ,
				tbl_os.sua_os                                                           ,
				tbl_os.consumidor_nome                                                  ,
				tbl_os.consumidor_fone                                                  ,
				tbl_os.serie                                                            ,
				tbl_os.pecas                                                            ,
				tbl_os.mao_de_obra                                                      ,
				tbl_os.nota_fiscal                                                      ,
				to_char (tbl_os.data_digitacao,'DD/MM/YYYY')      AS data_digitacao     ,
				to_char (tbl_os.data_abertura,'DD/MM/YYYY')       AS data_abertura      ,
				to_char (tbl_os.data_fechamento,'DD/MM/YYYY')     AS data_fechamento    ,
				to_char (tbl_os.data_conserto,'DD/MM/YYYY')       AS data_conserto      ,
				to_char (tbl_os.finalizada,'DD/MM/YYYY')          AS data_finalizada    ,
				to_char (tbl_os.data_nf,'DD/MM/YYYY')             AS data_nf            ,
				current_date - tbl_os.data_abertura                      AS dias_uso           ,
				tbl_os_produto.os_produto,
		        tbl_produto.referencia,
                tbl_produto.descricao,
				tbl_os.posto,
				tbl_os.excluida,
				tbl_os.defeito_constatado,
				tbl_os.fabrica,
				CASE WHEN tbl_os_excluida.os IS NULL THEN 'f'
				ELSE 't' END AS os_excluida
			INTO TEMP TABLE temp_os_aberta
			FROM tbl_os
				JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto and tbl_produto.fabrica_i = tbl_os.fabrica
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.fabrica=tbl_os.fabrica
					AND  tbl_posto_fabrica.posto=tbl_os.posto 
					AND  tbl_posto_fabrica.credenciamento in ('CREDENCIADO','EM DESCREDENCIAMENTO')
					LEFT JOIN tbl_os_produto USING(os)
					LEFT JOIN tbl_os_excluida ON tbl_os_excluida.os = tbl_os.os
				$join_intervencao
				JOIN temp_os_aberta_bi ON temp_os_aberta_bi.os = tbl_os.os
			WHERE tbl_os.fabrica = $login_fabrica
				AND   tbl_os.finalizada IS NULL
				AND   tbl_os.data_fechamento IS NULL
				AND   tbl_os.os_fechada IS FALSE
				AND   tbl_os.os_fechada IS NOT TRUE
				AND   tbl_os.posto <> 6359
				$cond_ano
				$sql_posto
				AND   tbl_os.data_abertura < current_date - INTERVAL '$qtde_dias days'
				$temp_intervencao
				$temp_cancelada_90_dias
				$temp_cancelada_45_dias
				$temp_cancelada_manual ";

			if (strlen($linha) > 0)             $sql .= " AND tbl_produto.linha = $linha ";


			$sql .= "; CREATE INDEX temp_os_aberta_os ON temp_os_aberta(os);
			CREATE INDEX temp_os_aberta_fabrica ON temp_os_aberta(fabrica);
			CREATE INDEX temp_os_aberta_os_produto ON temp_os_aberta(os_produto);

			SELECT  temp_os_aberta.referencia                         AS produto_referencia ,
				temp_os_aberta.descricao                          AS produto_descricao  ,
				tbl_posto_fabrica.codigo_posto                                          ,
				tbl_posto.nome AS nome_posto                                            ,
				tbl_posto.pais AS posto_pais                              ,
				temp_os_aberta.*
			INTO TEMP temp_os_aberta_res
			FROM    temp_os_aberta
				JOIN    tbl_posto         ON  temp_os_aberta.posto  = tbl_posto.posto
				JOIN    tbl_posto_fabrica ON  tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE temp_os_aberta.fabrica = $login_fabrica
				$cond_sem_peca
				$cond_data_conserto
				$sql_excluida;
			";

	//$sql .= " ORDER BY dias_uso,temp_os_aberta.sua_os;";
	$sql .= "CREATE INDEX temp_os_aberta_res_dias_uso ON temp_os_aberta_res (dias_uso);
			CREATE INDEX temp_os_aberta_res_sua_os ON temp_os_aberta_res (sua_os);";

	$sql_pecas = "SELECT temp_os_aberta.os,
						tbl_os_item.pedido,
						to_char(tbl_pedido.data, 'DD/MM/YYYY') as data_pedido,
						tbl_status_pedido.descricao as pedido_descricao,
						sum(tbl_os_item.qtde) as peca_qtde
						INTO TEMP temp_os_aberta_peca_res
					FROM tbl_os_item
						JOIN temp_os_aberta USING (os_produto)
						JOIN tbl_pedido USING (pedido)
						JOIN tbl_status_pedido USING (status_pedido)
					WHERE tbl_os_item.fabrica_i=$login_fabrica
			          	    AND tbl_pedido.fabrica=$login_fabrica
					GROUP BY temp_os_aberta.os, pedido, data_pedido, pedido_descricao;

					CREATE INDEX temp_os_aberta_peca_res_os ON temp_os_aberta_peca_res (os);";

	$sql_result = "SELECT * FROM temp_os_aberta_res LEFT JOIN temp_os_aberta_peca_res USING (os) ORDER BY temp_os_aberta_res.dias_uso, temp_os_aberta_res.sua_os";

	//echo nl2br($sql);
	//echo nl2br($sql_pecas);
	//echo nl2br($sql_result);
	//exit;
	$res = pg_query($con, $sql);
	$res = pg_query($con, $sql_pecas);
	$res = pg_query($con, $sql_result);

	$numero_registros = pg_num_rows($res);
	//$all = pg_fetch_all($res);


	$conteudo = "";
	$data = date("Y-m-d").".".date("H-i-s");

	$arquivo_nome     = "relatorio-os-aberta-$login_fabrica.$login_admin.xls";
	$path             = "xls/";
	$path_tmp         = "/tmp/";

	$arquivo_completo     = $path.$arquivo_nome;
	$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

	$fp = fopen ($arquivo_completo_tmp,"w");

	fputs ($fp,"<html>");
	fputs ($fp,"<body>");

	echo "<p id='id_download' style='display:none'><a href='{$path}{$arquivo_nome}' target='_blank'><img src='../imagens/excel.gif'><br/>Fazer download do arquivo em  XLS </a></p>";

	if ($numero_registros > 0) {

		$conteudo .=  "<table width='700' class='tabela' cellspacing='1' cellpadding='0'>";
		$conteudo .=  "<tr class='titulo_coluna'>";
		$conteudo .=  "<td nowrap>OS</td>";
		$conteudo .=  "<td nowrap>Abertura</td>";
		$conteudo .=  "<td nowrap>Digitação</td>";
		$conteudo .=  "<td nowrap>Conserto</td>";
		$conteudo .=  "<td nowrap>Consumidor</td>";
		$conteudo .=  "<td nowrap>Descrição Produto</td>";
		$conteudo .=  "<td nowrap>Referência Produto</td>";
		$conteudo .=  "<td nowrap>Razão Social</td>";
		$conteudo .=  "<td nowrap>Código Posto</td>";
		$conteudo .=  "<td nowrap>Data Último</td>";
		$conteudo .=  "<td nowrap>Último Pedido</td>";
		$conteudo .=  "<td nowrap>Status Último Pedido</td>";
		$conteudo .=  "<td nowrap>Qtde Peças</td>";
		$conteudo .=  "<td nowrap>Dias em Aberto</td>";
		$conteudo .=  "</tr>";

		/**
		 * @since HD 802851 - alterado para não mais exibir o resultado na tela, apenas download
		 */
		#echo $conteudo;
		fputs ($fp,$conteudo);

		while ($fetch = pg_fetch_array($res)) {
			$os                 = $fetch['os'];
			$sua_os             = $fetch['sua_os'];
			$consumidor_nome    = $fetch['consumidor_nome'];
			$consumidor_fone    = $fetch['consumidor_fone'];
			$serie              = $fetch['serie'];
			$nota_fiscal        = $fetch['nota_fiscal'];
			$data_digitacao     = $fetch['data_digitacao'];
			$data_abertura      = $fetch['data_abertura'];
			$data_fechamento    = $fetch['data_fechamento'];
			$data_conserto      = $fetch['data_conserto'];
			$data_finalizada    = $fetch['data_finalizada'];
			$data_nf            = $fetch['data_nf'];
			$dias_uso           = $fetch['dias_uso'];
			$produto_referencia = $fetch['produto_referencia'];
			$produto_descricao  = $fetch['produto_descricao'];
			$posto              = $fetch['posto'];
			$codigo_posto       = $fetch['codigo_posto'];
			$nome_posto         = $fetch['nome_posto'];
			$defeito_constatado	= $fetch['defeito_constatado'];
			$posto_pais         = $fetch['posto_pais'];
			$os_excluida        = $fetch['os_excluida'];

			$cor = ($i % 2 == 0) ? '#F1F4FA' : '#F7F5F0';
			$i++;

			#-------- Desconsidera ou marca em vermelho OS Excluidas ------
			#$sql = "SELECT * FROM tbl_os_excluida WHERE fabrica = $login_fabrica and os = $os";
			#$resX = pg_query ($con,$sql);
			$dica = "";
			if ($os_excluida == 't') {
				$cor = "#FF3300";
				$dica = "OS excluída";
				if ($excluidas == 't') {
					continue ;
				}
			}

			$data_ult_ped = $fetch['data_pedido'];
			$pedido_ult   = $fetch['pedido'] ;
			$status_ult   = $fetch['pedido_descricao'];

			//if(strlen($os)>0) {

			/*	$sql_dat_ult = "SELECT	to_char(data,'DD/MM/YYYY'),
							tbl_pedido.pedido,
							tbl_status_pedido.descricao
						from tbl_pedido
						join tbl_os_item using(pedido)
						join tbl_os_produto using(os_produto)
						join tbl_status_pedido using(status_pedido)
						join tbl_pedido_item ON tbl_pedido.pedido = tbl_pedido_item.pedido
						where tbl_pedido.fabrica=$login_fabrica
						and tbl_os_produto.os = $os order by tbl_pedido.data desc limit 1";*/
				//echo nl2br($sql_dat_ult);
/*				$res_dat_ult = pg_exec($con,$sql_dat_ult);
				if (pg_num_rows($res_dat_ult)>0) {
					$data_ult_ped = pg_result($res_dat_ult,0,0);
					$pedido_ult = pg_result($res_dat_ult,0,1);
					$status_ult = pg_result($res_dat_ult,0,2);
				}


				$sqlpecas = "SELECT COUNT(*) from tbl_os_produto join tbl_os_item using(os_produto) where os = $os";
				$respecas = pg_exec($con,$sqlpecas);

				$qtdepecas = pg_result($respecas,0,0);*/
				/*
				$sql2 = "SELECT TO_CHAR(emissao,'dd/mm/YYYY') AS emissao,
								nota_fiscal
						FROM tbl_faturamento_item
						JOIN (
						SELECT faturamento,emissao,nota_fiscal
						FROM tbl_faturamento
						WHERE tbl_faturamento.posto = $posto
						AND tbl_faturamento.fabrica = 3
						AND tbl_faturamento.conferencia IS NULL
						AND tbl_faturamento.cancelada  IS NULL
						AND tbl_faturamento.distribuidor IS NULL
						) fat ON tbl_faturamento_item.faturamento = fat.faturamento
						WHERE tbl_faturamento_item.peca   = $peca
						AND   tbl_faturamento_item.pedido = $pedido
						AND   tbl_faturamento_item.os     = $os
						;";

				$res2 = pg_exec($con,$sql2);
				if(pg_numrows($res2)>0){
					$fat_emissao = pg_result($res2,0,0);
					$fat_nf      = pg_result($res2,0,1);
				}else{
					$fat_emissao = "Pendente";
					$fat_nf      = "Pendente";
				}
				*/

			//}

			$conteudo = "";
			$conteudo .=  "<tr class='Conteudo' bgcolor='$cor' title='$dica'>";
			$conteudo .=  "<td nowrap align='center'><a target='_blank' href='http://posvenda.telecontrol.com.br/assist/admin/os_press.php?os=$os' title='Clique para visualizar a OS em uma nova tela'>$sua_os</a></td>";
			$conteudo .=  "<td nowrap align='center'>$data_abertura</td>";
			$conteudo .=  "<td nowrap align='center'>$data_digitacao</td>";
			$conteudo .=  "<td nowrap align='center'>$data_conserto</td>";
			$conteudo .=  "<td nowrap align='left'>$consumidor_nome</td>";
			$conteudo .=  "<td nowrap align='left'>$produto_descricao</td>";
			$conteudo .=  "<td nowrap align='center'>$produto_referencia</td>";
			$conteudo .=  "<td nowrap align='left'>$nome_posto</td>";
			$conteudo .=  "<td nowrap align='center'>$codigo_posto</td>";

			$conteudo .=  "<td nowrap align='center'>$data_ult_ped</td>";
			$conteudo .=  "<td nowrap align='center'>$pedido_ult</td>";
			$conteudo .=  "<td nowrap align='center'>$status_ult</td>";
			$conteudo .=  "<td nowrap align='center'>$qtdepecas</td>";

			$conteudo .=  "<td nowrap align='center'>$dias_uso</td>";

			$conteudo .= "</tr>";

			#echo $conteudo;
			fputs ($fp,$conteudo);
		}

		$conteudo  = "<tr>";
		$conteudo  = "<td nowrap align='left'>";
		$conteudo  = "</table>";
		#$conteudo .= "<BR><CENTER>".$numero_registros." Registros encontrados</CENTER>";
		#echo $conteudo;

		fputs ($fp,$conteudo);
		fputs ($fp,"</body>");
		fputs ($fp,"</html>");
		fclose ($fp);
		flush();
		echo ` cp $arquivo_completo_tmp $path `;

		echo "<script language='javascript'>";
		echo "document.getElementById('id_download').style.display='block';";
		echo "</script>";
		flush();
		echo "<br>";
	} else {
		echo "<p style='font-size:13px;'>Não foram encontrados resultados para esta pesquisa!</p>";
	}
}

echo "<br>";

include "rodape.php";
?>
