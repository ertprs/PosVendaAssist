<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
include "funcoes.php";


# ---- excluir ---- #
$xos = $_GET['excluir'];

if (strlen ($xos) > 0) {
	$sql = "SELECT fn_os_excluida($xos,$login_fabrica,null);";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
}

# ---- fechar ---- #
$fechar = $_GET['fechar'];
if (strlen ($fechar) > 0) {
	include "ajax_cabecalho.php";

	$msg_erro = "";
	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$sql = "UPDATE tbl_os SET data_fechamento = CURRENT_TIMESTAMP WHERE os = $fechar AND fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con) ;

	if (strlen ($msg_erro) == 0 AND $login_fabrica == 1) {
		$sql = "SELECT fn_valida_os_item($fechar, $login_fabrica)";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	if (strlen ($msg_erro) == 0) {
		$sql = "SELECT fn_finaliza_os($fechar, $login_fabrica)";
		$res = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con) ;
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		echo "ok;XX$fechar";
	}else{
		$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
		echo "erro;$sql ==== $msg_erro ";
	}
	flush();
	exit;
}


$layout_menu = "os";
$title       = "Consulta de Ordens de Serviço";
include "cabecalho.php";


?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
</style>

<? include "javascript_pesquisas.php"; ?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language="javascript" src="js/assist.js"></script>

<script language='javascript' src='ajax.js'></script>



<script language='javascript'>
function fnc_pesquisa_revenda (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cnpj") {
		url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj";
	}
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
	janela.nome			= document.frm_consulta.revenda_nome;
	janela.cnpj			= document.frm_consulta.revenda_cnpj;
	janela.fone			= document.frm_consulta.revenda_fone;
	janela.cidade		= document.frm_consulta.revenda_cidade;
	janela.estado		= document.frm_consulta.revenda_estado;
	janela.endereco		= document.frm_consulta.revenda_endereco;
	janela.numero		= document.frm_consulta.revenda_numero;
	janela.complemento	= document.frm_consulta.revenda_complemento;
	janela.bairro		= document.frm_consulta.revenda_bairro;
	janela.cep			= document.frm_consulta.revenda_cep;
	janela.email		= document.frm_consulta.revenda_email;
	janela.focus();
}

function retornaFechamentoOS (http , sinal, excluir, lancar) {
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split(";");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					alert ('OS ' + results[0] + ' fechada com sucesso' );
					sinal.src='/assist/admin/imagens_admin/status_vermelho.gif';
					sinal.src='/assist/imagens/pixel.gif';
					excluir.src='/assist/imagens/pixel.gif';
					lancar.src='/assist/imagens/pixel.gif';
				}else{
					if (http.responseText.indexOf ('de-obra para instala') > 0) {
						alert ('Esta OS não tem mão-de-obra para instalação');
					}else if (http.responseText.indexOf ('Nota Fiscal de Devol') > 0) {
						alert ('Erro no Fechamento da OS. \nPor favor utilizar a tela de Fechamento de OS para informar a Nota Fiscal de Devolução.');
					}else if (http.responseText.indexOf ('Favor informar aparência do produto e acessórios') > 0) {
						alert ('Erro no Fechamento da OS. \nPor favor, verifique os dados digitados, aparência e acessórios, na tela de lançamento de itens.');
					}else if (http.responseText.indexOf ('Type informado para o produto não é válido') > 0) {
						alert ('Type informado para o produto não é válido');
					} else {
						alert ('Erro no Fechamento da OS. \nPor favor, verifique os dados digitados, defeito constatado e solução, na tela de lançamento de itens.');
					}
				}
			}else{
				alert ('Fechamento nao processado');
			}
		}
	}
}

function fechaOS (os , sinal , excluir , lancar ) {
	url = "<?= $PHP_SELF ?>?fechar=" + escape(os) ;
	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaFechamentoOS (http , sinal, excluir, lancar) ; } ;
	http.send(null);
}


</script>

<br>



<?

$btn_acao = $_POST['acao'];
if(strlen($btn_acao)>0){
/*SUA OS*/
	$sua_os             = trim (strtoupper ($_POST['sua_os']));
	if (strlen($sua_os) > 0 and strlen($sua_os) > 4) {
		$pos = strpos($sua_os, "-");
		if ($pos === false) {
		$pos = strlen($sua_os) - 5;
		}else{
		$pos = $pos - 5;
		}
		$sua_os = substr($sua_os, $pos,strlen($sua_os));
		$condicao_0 = " AND (tbl_os.sua_os like '%$sua_os%') ";
		$condicao_1 = "";
		$condicao_2 = "";
		$condicao_3 = "";
		$condicao_5 = "";
		$condicao_6 = "";
		$condicao_7 = "";
		$condicao_8 = "";

	}
	if(strlen($sua_os) > 0 AND strlen($sua_os) < 5) $msg = "Favor digitar no minimo 5(cinco) caracteres";
/*SUA OS*/	
/*SÉRIE*/
	$serie              = trim (strtoupper ($_POST['serie']));
	if ( strlen ($serie) > 0 AND strlen ($serie) < 5) {
		$msg = "Digite no mínimo 5 letras para o número de série";
	}
	if ( strlen ($serie) > 0 AND strlen ($serie) > 5) {
		$condicao_1 = " AND tbl_os.serie like '%$serie%' ";
		$condicao_5 = "";
		$condicao_6 = "";
		$condicao_7 = "";
		$condicao_8 = "";
	}	
/*NOTA FISCAL*/	
	$nf_compra          = trim (strtoupper ($_POST['nf_compra']));
	if ( strlen ($nf_compra) > 0) {
		$condicao_2 = " AND tbl_os.nota_fiscal like '%$nf_compra%' ";
		$condicao_5 = "";
		$condicao_6 = "";
		$condicao_7 = "";
		$condicao_8 = "";
	}	
/*CPF*/	
	$consumidor_cpf = trim (strtoupper ($_POST['consumidor_cpf']));
	$consumidor_cpf = str_replace (".","",$consumidor_cpf);
	$consumidor_cpf = str_replace (" ","",$consumidor_cpf);
	$consumidor_cpf = str_replace ("-","",$consumidor_cpf);
	$consumidor_cpf = str_replace ("/","",$consumidor_cpf);
	if (strlen ($consumidor_cpf) <> 11 AND strlen ($consumidor_cpf) <> 14 AND strlen ($consumidor_cpf) <> 0) {
		$msg = "Tamanho do CPF do consumidor inválido";
	}
	if (strlen ($consumidor_cpf) == 11 OR strlen ($consumidor_cpf) == 14) {
		$condicao_3 = " AND tbl_os.consumidor_cpf LIKE '%$consumidor_cpf%' ";
		$condicao_5 = "";
		$condicao_6 = "";
		$condicao_7 = "";
		$condicao_8 = "";
	}

/*OS ABERTA*/
	$os_aberta          = trim (strtoupper ($_POST['os_aberta']));
	if (strlen($os_aberta) > 0) {
		$condicao_4 = " AND tbl_os.os_fechada IS FALSE ";
	}
/*MES ANO*/
	$mes = trim (strtoupper ($_POST['mes']));
	$ano = trim (strtoupper ($_POST['ano']));
	if (strlen($mes) > 0 AND strlen ($ano) > 0) {
		$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
		$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
		$condicao_5 = " AND tbl_os.data_digitacao between '$data_inicial' and '$data_final' ";
		$condicao_0 = "";
		$condicao_1 = "";
		$condicao_2 = "";
		$condicao_3 = "";
	}
	if (strlen ($mes) == 0 AND strlen ($ano) > 0) {
		$msg = "Selecione o mês";
	}
/*CONSUMIDOR NOME*/
	$consumidor_nome = trim (strtoupper ($_POST['consumidor_nome']));
	if ( strlen ($consumidor_nome) > 0 AND strlen ($consumidor_nome) < 5) {
		$msg = "Digite no mínimo 5 letras para o nome do consumidor";
	}
	if ( strlen ($consumidor_nome) > 0 AND strlen ($consumidor_nome) > 5) {
		$condicao_6 = " AND UPPER(tbl_os.consumidor_nome) like '%$consumidor_nome%' ";
		$condicao_0 = "";
		$condicao_1 = "";
		$condicao_2 = "";
		$condicao_3 = "";
	}
/*PRODUTO*/
	$produto_referencia = trim (strtoupper ($_POST['produto_referencia']));
	$produto_descricao  = trim (strtoupper ($_POST['produto_descricao']));	

	if (strlen ($produto_referencia) > 0) {
		$sqlX = "SELECT produto 
				FROM tbl_produto 
				JOIN tbl_linha USING (linha) 
				WHERE tbl_linha.fabrica = $login_fabrica 
				AND tbl_produto.referencia = '$produto_referencia'";
		$resX = pg_exec ($con,$sqlX);
		$produto = pg_result ($resX,0,0);
		$condicao_7 = " AND  tbl_os.produto = $produto";
		$condicao_0 = "";
		$condicao_1 = "";
		$condicao_2 = "";
		$condicao_3 = "";
	}
/*REVENDA*/
	$revenda_nome = trim (strtoupper ($_POST['revenda_nome']));
	$revenda_cnpj = trim (strtoupper ($_POST['revenda_cnpj']));
	if(strlen($revenda_nome)>0 and strlen($revenda_cnpj)>0){

		$revenda_cnpj = str_replace("-","",$revenda_cnpj);
		$revenda_cnpj = str_replace(",","",$revenda_cnpj);
		$revenda_cnpj = str_replace(".","",$revenda_cnpj);
	
		$sql = "SELECT revenda from tbl_revenda where cnpj='$revenda_cnpj' limit 1";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$revenda = pg_result ($res,0,0);
			$condicao_8 = " AND tbl_os.revenda = $revenda ";
			$condicao_0 = "";
			$condicao_1 = "";
			$condicao_2 = "";
			$condicao_3 = "";
		}
	}

	if ( strlen ($sua_os) == 0 AND strlen ($serie) == 0 AND strlen ($nf_compra) == 0 AND strlen ($consumidor_cpf) == 0 AND  strlen ($mes) == 0 AND strlen ($ano) == 0 )  {
		$msg = "Selecione o mês e o ano para fazer a pesquisa";
	}

if(strlen($msg)==0){
	$sql = "SELECT 	tbl_os.os                                                     ,
					tbl_os.sua_os                                                 ,
					tbl_os.serie                                                  ,
					TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS digitacao      ,
	 				TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY') AS abertura        ,
	 				TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento    ,
					tbl_os.nota_fiscal                                            ,
					tbl_os.consumidor_cpf                                         ,
					tbl_os.consumidor_nome                                        ,
					tbl_os.produto                                                ,
					tbl_produto.referencia as produto_referencia                  ,
					tbl_produto.descricao  as produto_descricao                   ,
					tbl_os.os_reincidente  AS reincidencia                        ,
					tbl_os.revenda_nome                                           ,
					tbl_os_extra.impressa as os_impressa                          ,
					tbl_os_extra.extrato                                          ,
					tbl_os.tipo_os_cortesia                                       ,
					tbl_os.cortesia                                               ,
					tbl_os.os_fechada                                             ,
					tbl_os.consumidor_revenda, tbl_os.excluida                    ,
					tbl_os.tipo_atendimento                     ,
					(SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os ORDER BY data DESC LIMIT 1) AS status_os,
					tbl_os.admin
			FROM tbl_os 
			JOIN tbl_produto on tbl_os.produto = tbl_produto.produto
			JOIN tbl_os_extra on tbl_os.os = tbl_os_extra.os
			WHERE tbl_os.fabrica = $login_fabrica
			AND tbl_os.posto = $login_posto
			$condicao_0
			$condicao_1
			$condicao_2
			$condicao_3
			$condicao_4
			$condicao_5
			$condicao_6
			$condicao_7
			$condicao_8 ORDER by data_abertura desc";

	$res = pg_exec($con,$sql);
//echo $sql;

if(pg_numrows($res)>0){
##### LEGENDAS - INICIO #####
	echo "<div align='left' style='position: relative; left: 25'>";
	echo "<table border='0' cellspacing='0' cellpadding='0'>";
	if ($Xexcluida == "t") {
		echo "<tr height='18'>";
		echo "<td width='18' bgcolor='#FFE1E1'>&nbsp;</td>";
		echo "<td align='left'><font size='1'><b>&nbsp; Excluídas do sistema</b></font></td>";
		echo "</tr>";
		echo "<tr height='3'><td colspan='2'></td></tr>";
	}
		echo "<tr height='18'>";
		echo "<td width='18' bgcolor='#FFCC66'>&nbsp;</td>";
		echo "<td align='left'><font size='1'><b>&nbsp; OSs sem lancamento de itens há mais de 5 dias, efetue o lançamento</b></font></td>";
		echo "</tr>";
		echo "<tr height='3'><td colspan='2'></td></tr>";
		echo "<tr height='18'>";
		echo "<td width='18' bgcolor='#FF0000'>&nbsp;</td>";
		echo "<td align='left'><font size='1'><b>&nbsp; OSs que excederam o prazo limite de 30 dias para fechamento, informar \"Motivo\"</b></font></td>";
		echo "</tr>";
		echo "<tr height='3'><td colspan='2'></td></tr>";
		echo "<tr height='18'>";
		echo "<td width='18' bgcolor='#91C8FF'>&nbsp;</td>";
		echo "<td align='left'><font size='1'><b>&nbsp; ";
		echo "OS aberta a mais de 25 dias.";
		echo "</b></font></td>";
		echo "</tr>";
		echo "<tr height='3'><td colspan='2'></td></tr>";
		echo "<tr height='18'>";
		echo "<td width='18' bgcolor='#D7FFE1'>&nbsp;</td>";
		echo "<td align='left'><font size='1'><b>&nbsp; Reincidências</b></font></td>";
		echo "</tr>";
		echo "<tr height='3'><td colspan='2'></td></tr>";
	echo "</table>";
	echo "</div>";
	##### LEGENDAS - FIM #####

	echo "<br>";

	echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' width='98%'>";
	echo "<tr class='Titulo' height='25' background='admin/imagens_admin/azul.gif'>";
	echo "<td width='100'>OS</td>";
	echo "<td>Série</td>";
	echo "<td>AB</td>";
	echo "<td>FC</td>";
	echo "<td>Consumidor</td>";
	echo "<td nowrap>Produto</td>";
	echo "<td><img border='0' src='imagens/img_impressora.gif' alt='Imprimir OS'></td>";
	echo "<td><img border='0' width='20' heigth='20' src='imagens/envelope.png' alt='Carta Registrada'></td>";
	echo "<td nowrap>Tipo</td>";
	echo "<td colspan='7'>Ações</td>";
echo "</tr>";
	for($i=0;pg_numrows($res)>$i;$i++){
		$xos                = pg_result($res,$i,os);
		$xsua_os            = pg_result($res,$i,sua_os);
		$xserie             = pg_result($res,$i,serie);
		$xdigitacao         = pg_result($res,$i,digitacao);
		$xabertura          = pg_result($res,$i,abertura);
		$xfechamento        = pg_result($res,$i,fechamento);
		$xnota_fiscal       = pg_result($res,$i,nota_fiscal);
		$xconsumidor_cpf    = pg_result($res,$i,consumidor_cpf);
		$xconsumidor_nome   = pg_result($res,$i,consumidor_nome);
		$xproduto           = pg_result($res,$i,produto);
		$xproduto_referencia= pg_result($res,$i,produto_referencia);
		$xproduto_descricao = pg_result($res,$i,produto_descricao);
		$xreincidencia      = pg_result($res,$i,reincidencia);
		$xrevenda_nome      = pg_result($res,$i,revenda_nome);
		$xos_impressa       = pg_result($res,$i,os_impressa);
		$xtipo_os_cortesia  = pg_result($res,$i,tipo_os_cortesia);
		$xextrato           = pg_result($res,$i,extrato);
		$xcortesia          = pg_result($res,$i,cortesia);
		$xos_fechada        = pg_result($res,$i,os_fechada);
		$xconsumidor_revenda= pg_result($res,$i,consumidor_revenda);
		$xexcluida          = pg_result($res,$i,excluida);
		$xtipo_atendimento  = pg_result($res,$i,tipo_atendimento);
		$xstatus_os         = pg_result($res,$i,status_os);
		$xadmin             = pg_result($res,$i,admin);
		if ($i % 2 == 0) {
			$cor   = "#F1F4FA";
			$botao = "azul";
		}else{
			$cor   = "#F7F5F0";
			$botao = "amarelo";
		}
		if ($xexcluida == "t")            $cor = "#FFE1E1";
		if ($xreincidencia =='t')	 $cor = "#D7FFE1";
		if ($login_fabrica == 1) {
			$aux_abertura = fnc_formata_data_pg($xabertura);

			$sqlX = "SELECT TO_CHAR(current_date + INTERVAL '5 days','YYYY-MM-DD')";
			$resX = pg_exec($con,$sqlX);
			$data_hj_mais_5 = pg_result($resX,0,0);

			$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '5 days','YYYY-MM-DD')";
			$resX = pg_exec ($con,$sqlX);
			$aux_consulta = pg_result($resX,0,0);

			$sql = "SELECT COUNT(tbl_os_item.*) AS total_item
					FROM tbl_os_item
					JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
					JOIN tbl_os         ON tbl_os.os                 = tbl_os_produto.os
					WHERE tbl_os.os = $xos
					AND   tbl_os.data_abertura::date >= '$aux_consulta'";
			//echo $sql;
			$resItem = pg_exec($con,$sql);

			$itens = pg_result($resItem,0,total_item);

			if ($itens == 0 && $aux_consulta > $data_hj_mais_5) $cor = "#FFCC66";

			$mostra_motivo = 2;
		}
		// Verifica se está sem fechamento há 20 dias ou mais da data de abertura
		if (strlen($fechamento) == 0 && $mostra_motivo == 2 && $login_fabrica == 1) {
			$aux_abertura = fnc_formata_data_pg($xabertura);

			$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '20 days','YYYY-MM-DD')";
			$resX = pg_exec($con,$sqlX);
			$aux_consulta = pg_result($resX,0,0);

			$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
			$resX = pg_exec($con,$sqlX);
			$aux_atual = pg_result($resX,0,0);

			if ($consumidor_revenda != "R") {
				if ($aux_consulta < $aux_atual && strlen($xfechamento) == 0) {
					$mostra_motivo = 1;
					$cor = "#91C8FF";
				}
			}
		}
		if (strlen($xfechamento) == 0 && $login_fabrica == 1) {
				$aux_abertura = fnc_formata_data_pg($xabertura);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '30 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_atual = pg_result($resX,0,0);

				if ($consumidor_revenda != "R"){
					if ($aux_consulta < $aux_atual && strlen($xfechamento) == 0) {
						$mostra_motivo = 1;
						$cor = "#FF0000";
					}
				}
			}
		$xsua_os = $login_codigo_posto . $xsua_os;
		echo "<tr class='Conteudo' height='15' bgcolor='$cor' align='left'>";
		echo "<td  width='50' nowrap>$xsua_os</td>";
		echo "<td width='55' nowrap>" . $xserie . "</td>";
		echo "<td nowrap ><acronym title='Data Abertura: $xabertura' style='cursor: help;'>" . substr($xabertura,0,5) . "</acronym></td>";
		echo "<td nowrap ><acronym title='Data Fechamento: $xfechamento' style='cursor: help;'>" . substr($xfechamento,0,5) . "</acronym></td>";
		echo "<td nowrap>$xconsumidor_nome</td>";
		echo "<td nowrap>$xproduto_referencia - " . substr($xproduto_descricao,0,10) . " </td>";
		echo "<td>";
		if (strlen($xos_impressa) > 0) {echo "<img border='0' src='imagens/img_ok.gif' alt='OS já foi impressa'>";
		}else{   echo "<img border='0' src='imagens/img_impressora.gif' alt='Imprimir OS'>"; }
		echo "</td>";
		echo "<td width='30' align='center'>";
		if($xconsumidor_revenda == 'C' ){
			if(strlen($xfechamento) == 0){
				$sql_sedex = "SELECT SUM(current_date - data_abertura) as final FROM tbl_os WHERE os=$xos ;";
				$res_sedex = pg_exec($con,$sql_sedex);
				$sedex_dias = pg_result($res_sedex,0,final);
				if($sedex_dias > 15){
					$sql_sedex = "SELECT sua_os_origem FROM tbl_os_sedex WHERE sua_os_origem = $xos AND fabrica = $login_fabrica";
					$res_sedex = pg_exec($con,$sql_sedex);
					if(pg_numrows($res_sedex) == 0){
						echo "<a href='carta_registrada.php?os=$xos'><img border='0' width='20' heigth='20' src='imagens/envelope.png' alt='Inserir informações da Carta Registrada'></a>";
					}else{
						echo "<a href='carta_registrada.php?os=$xos'><img border='0' width='20' heigth='20' src='imagens/img_ok.gif' alt='Visualizar as informações da Carta Registrada'></a>";
					}
				}
				echo "&nbsp;";
			}else{
				echo "&nbsp;";
			}
		}
		 echo "</td>";
		echo "<td>";
		if($xcortesia=="t"){ 
			echo "Cortesia";
		}else{
			if($xconsumidor_revenda=="R"){ echo "Revenda";}else{ echo "Consumidor"; }
		}
		echo "</td>";
		echo "<td>";
		if ($xexcluida == "f" || strlen($xexcluida) == 0) echo "<a href='os_press.php?os=$xos' target='_blank'><img border='0' src='imagens/btn_consulta.gif'></a>";
		echo "</td>";
		echo "<td>";
		if ($xexcluida == "f" || strlen($xexcluida) == 0) {
			if ($login_fabrica == 1 && $xtipo_os_cortesia == "Compressor") {
				if($login_posto=="6359"){
					echo "<a href='os_print.php?os=$xos' target='_blank'>";
				}else{
					echo "<a href='os_print_blackedecker_compressor.php?os=$xos' target='_blank'>";
				//takashi alterou 03/11
				}
			}else{
				echo "<a href='os_print.php?os=$xos' target='_blank'>";
			}
			echo "<img border='0' src='imagens/btn_imprime.gif'></a>";
		}
		echo "</td>";
		echo "<td>";
		if (($xexcluida == "f" || strlen($xexcluida) == 0) && strlen($xfechamento) == 0) {
			if(strlen($xtipo_atendimento) == 0){
				if($xconsumidor_revenda=="C"){
					echo "<a href='os_cadastro.php?os=$xos'><img border='0' src='imagens/btn_alterar_cinza.gif'></a>";
				}else{
					echo "<a href='os_revenda_alterar.php?os=$xos'><img border='0' 	src='imagens/btn_alterar_cinza.gif'></a>";
				}
			}else{
				echo "<a href='os_cadastro_troca.php?os=$xos'><img border='0' src='imagens/btn_alterar_cinza.gif'></a>";
			}
		}else{
			echo "&nbsp;";
		}
		echo "</td>";
		echo "<td>";
		if ($troca_garantia == "t"  OR  ($xstatus_os=="62" || $xstatus_os=="65" || $xstatus_os=="72")) {
			}elseif (($login_fabrica == 3 || $login_fabrica == 6) && strlen ($xfechamento) == 0) {
				if ($xexcluida == "f" || strlen($xexcluida) == 0) {
					echo "<a href='os_item.php?os=$xos' target='_blank'><img id='lancar_$i' border='0' src='imagens/btn_lanca.gif'></a>";
				}
			}elseif ($login_fabrica == 1 && strlen ($xfechamento) == 0 ) {
				if ($xexcluida == "f" || strlen($xexcluida) == 0) {
					if ($login_fabrica == 1 AND $xtipo_os_cortesia == "Compressor") {
						if($login_posto=="6359"){
							echo "<a href='os_item.php?os=$xos' target='_blank'>";
						}else{
							echo "<a href='os_print_blackedecker_compressor.php?os=$xos' target='_blank'>";
						//takashi alterou 03/11
						}
					}else{
						echo "<a href='os_item.php?os=$xos' target='_blank'>";
					}
					echo "<img id='lancar_$i' border='0' src='imagens/btn_lanca.gif'></a>";
				}
			}elseif (strlen($xfechamento) == 0 ) {
				if ($xexcluida == "f" OR strlen($xexcluida) == 0) {
					if ($login_fabrica == 1) {
						if($xtipo_os_cortesia == "Compressor"){
							if($login_posto=="6359"){
								echo "<a href='os_item.php?os=$xos' target='_blank'>";
							}else{
								echo "<a href='os_print_blackedecker_compressor.php?os=$xos' target='_blank'>";
							//takashi alterou 03/11
							}
						}
						if(strlen($xtipo_atendimento) == 0){
							echo "<a href='os_item.php?os=$xos' target='_blank'>";
						}
					}else{
							echo "<a href='os_item.php?os=$xos' target='_blank'>";
					}
					echo "<img id='lancar_$i' border='0' src='imagens/btn_lanca.gif'></a>";
				}
			}elseif (strlen($xfechamento) > 0 && strlen($xextrato) == 0) {
				if ($xexcluida == "f" || strlen($xexcluida) == 0) {
					if (strlen ($importacao_fabrica) == 0) {
							if($login_fabrica == 1 AND ($xtipo_atendimento == 17 OR $xtipo_atendimento == 18)) echo "&nbsp;";
							else echo "<a href='os_item.php?os=$xos&reabrir=ok'><img border='0' src='imagens/btn_reabriros.gif'></a>";
					}
				}
			}else{
				echo "&nbsp;";
			}
		echo "</td>";

		echo "<td width='60' align='center'>";
		if (strlen($xadmin) == 0 AND strlen ($xfechamento) == 0 AND ($xexcluida == "f" OR strlen($xexcluida) == 0) AND $mostra_motivo == 1) {
			echo "<a href='os_motivo_atraso.php?os=$xos' target='_blank'><img border='0' src='imagens/btn_motivo.gif'></a>";
		}else{
			echo "&nbsp;";
		}
		echo "</td>\n";
		echo "<td>";
		if (strlen($xfechamento) == 0 && strlen($xpedido) == 0 && $login_fabrica != 7  && $xstatus_os!="62" && $xstatus_os!="65" && $xstatus_os!="72") {
				if ($xexcluida == "f" || strlen($xexcluida) == 0) {
					if (strlen ($xadmin) == 0) {
						echo "<a href=\"javascript: if (confirm('Deseja realmente excluir a OS $xsua_os ?') == true) { window.location='$PHP_SELF?excluir=$xos'; }\"><img id='excluir_$i' border='0' src='imagens/btn_excluir.gif'></a>";
					}else{
						echo "<img id='excluir_$i' border='0' src='imagens/pixel.gif'>";
					}
				}
			}else{
				echo "&nbsp;";
			}
		echo "</td>";
		echo "<td>";
		if (strlen($xfechamento) == 0 AND $xstatus_os!="62" && $sxtatus_os!="65" && $xstatus_os!="72") {
			if ($xexcluida == "f" || strlen($xexcluida) == 0) {

				echo "<a href=\"javascript: if (confirm('Caso a data da entrega do produto para o consumidor não seja HOJE, utilize a opção de Fechamento de OS para informar a data correta! Confirma o fechamento da OS $xsua_os com a data de HOJE?') == true) { fechaOS ($xos,sinal_$i,excluir_$i, lancar_$i) ; }\"><img id='sinal_$i' border='0' src='/assist/imagens/btn_fecha.gif'></a>";
				}
			}else{
				echo "&nbsp;";
			}
		echo "</td>";
		echo "</tr>";
}
echo "</table><BR><font size='2'>Resultado: ". pg_numrows($res) . " Registro(s)</font> <BR>";
}else{
echo "<center><font size='2'>Nenhum resultado encontrado!</font></center>";
}

}
/*
echo "os $sua_os<BR>";
echo "serie $serie<BR>";
echo "nf $nf_compra<BR>";
echo "cpj $consumidor_cpf<BR>";
echo "mes $mes<BR>";
echo "ano $ano<BR>";
echo "consumidor $consumidor_nome<BR>";
echo "prod $produto_referencia<BR>";
echo "prod $produto_descricao<BR>";
echo "revenda $revenda_nome<BR>";
echo "rev cnpj $revenda_cnpj<BR>";
echo "revenda $revenda<BR>";
*/

}
?>
<? echo $msg; ?>

<form name="frm_consulta" method="post" action="<?echo $PHP_SELF?>">

<input type="hidden" name="acao">

<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
<tr class="Titulo" height="30">
	<td align="center" colspan='6'>Selecione os parâmetros para a pesquisa</td>
</tr>
<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
	<td width='10'>&nbsp;</td>
	<td>Número da OS</td>
	<td>Número de Série</td>
	<td>NF. Compra</td>
	<td >CPF Consumidor</td>
	<td width='10'>&nbsp;</td>
</tr>
<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
<td width='10'>&nbsp;</td>
	<td><input type="text" name="sua_os"    size="10" value="<?echo $sua_os?>"    class="frm"></td>
	<td><input type="text" name="serie"     size="10" value="<?echo $serie?>"     class="frm"></td>
	<td><input type="text" name="nf_compra" size="10" value="<?echo $nf_compra?>" class="frm"></td>
	<td><input type="text" name="consumidor_cpf" size="11" value="<?echo $consumidor_cpf?>" class="frm"></td>
<td width='10'>&nbsp;</td>
</tr>

<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
	<td width='10'>&nbsp;</td>
	<td colspan='5'><input type='checkbox' name='os_aberta' value='1' <? if (strlen ($os_aberta) > 0 ) echo " checked " ?> >Apenas OS em aberto
	</td>
</tr>

<tr class="Conteudo" bgcolor="#D9E2EF">
	<td colspan='6' align='center'><BR><img border="0" src="imagens/btn_pesquisar_400.gif" onclick="document.frm_consulta.acao.value='PESQUISAR'; document.frm_consulta.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar"></td>
</tr>

<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
	<td colspan='6'> <hr> </td>
</tr>

<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
	<td width='10'>&nbsp;</td>
	<td colspan='4'>Data referente à digitação da OS no site (obrigatório para a pesquisa)</td>
	<td width='10'>&nbsp;</td>
</tr>
<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
<td width='10'>&nbsp;</td>
	<td colspan='2'>Mês</td>
	<td colspan='2'>Ano</td>
<td width='10'>&nbsp;</td>
</tr>

<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
	<td width='10'>&nbsp;</td>
	<td colspan='2'>
	<select name="mes" size="1" class="frm" style='width:120px'>
	<option value=''></option>
	<?
	$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
	for ($i = 1 ; $i <= count($meses) ; $i++) {
		echo "<option value='$i'";
		if ($mes == $i) echo " selected";
		echo ">" . $meses[$i] . "</option>";
	}
	?>
	</select>
	</td>
	<td colspan='2'>
	<select name="ano" size="1" class="frm">
	<option value=''></option>
	<?
	//for ($i = 2003 ; $i <= date("Y") ; $i++) {
	for($i = date("Y"); $i > 2003; $i--){
		echo "<option value='$i'";
		if ($ano == $i) echo " selected";
		echo ">$i</option>";
	}
	?>
	</select>
	</td>
<td width='10'>&nbsp;</td>
</tr>

<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
	<td width='10'>&nbsp;</td>
	<td colspan='4'>Nome do Consumidor</td>
	<td width='10'>&nbsp;</td>
</tr>
<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
	<td width='10'>&nbsp;</td>
	<td colspan='4'><input type="text" name="consumidor_nome" size="46" value="<?echo $consumidor_nome?>" class="frm"></td>
	<td width='10'>&nbsp;</td>
</tr>

<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
	<td width='10'>&nbsp;</td>
	<td colspan='2'>Ref. Produto</td>
	<td colspan='2'>Descrição Produto</td>
	<td width='10'>&nbsp;</td>
</tr>
<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
	<td width='10'>&nbsp;</td>
	<td colspan='2'>
	<input class="frm" type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" > 
	<img src='imagens/btn_lupa.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_consulta.produto_referencia, document.frm_consulta.produto_descricao,'referencia', document.frm_consulta.produto_voltagem)">
	</td>
	<td colspan='2'>
	<input class="frm" type="text" name="produto_descricao" size="20" value="<? echo $produto_descricao ?>" >
	&nbsp;	<input type='hidden' name = 'produto_voltagem'>
	<img src='imagens/btn_lupa.gif'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_consulta.produto_referencia, document.frm_consulta.produto_descricao,'descricao', document.frm_consulta.produto_voltagem)">
	<td width='10'>&nbsp;</td>
</tr>

<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
	<td width='10'>&nbsp;</td>
	<td colspan='2'>Cnpj Revenda</td>
	<td colspan='2'>Nome Revenda</td>
	<td width='10'>&nbsp;</td>
</tr>

<tr class="Conteudo" bgcolor="#D9E2EF">
	<td width='10'>&nbsp;</td>
		<td colspan='2'>
			<input type="text" name="revenda_cnpj" size="15" value="<?echo $revenda_cnpj?>">
			<img border="0" src="imagens/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar revendas pelo código" onclick="javascript: fnc_pesquisa_revenda (document.frm_consulta.revenda_cnpj, 'cnpj');">
		</td>
		<td colspan="2">
			<input type="text" name="revenda_nome" size="20" value="<?echo $revenda_nome?>">
			<img border="0" src="imagens/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar pelo nome da revenda." onclick="javascript: fnc_pesquisa_revenda (document.frm_consulta.revenda_nome, 'nome');">
		</td>
		<td>
			&nbsp;
			<input type='hidden' name = 'revenda_fone'>
			<input type='hidden' name = 'revenda_cidade'>
			<input type='hidden' name = 'revenda_estado'>
			<input type='hidden' name = 'revenda_endereco'>
			<input type='hidden' name = 'revenda_numero'>
			<input type='hidden' name = 'revenda_complemento'>
			<input type='hidden' name = 'revenda_bairro'>
			<input type='hidden' name = 'revenda_cep'>
			<input type='hidden' name = 'revenda_email'>
		</td>
</tr>

<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
	<td colspan='6'> <hr> </td>
</tr>

<tr class="Conteudo" bgcolor="#D9E2EF">
	<td colspan='6' align='center'><img border="0" src="imagens/btn_pesquisar_400.gif" onclick="document.frm_consulta.acao.value='PESQUISAR'; document.frm_consulta.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar"></td>
</tr>
</table>
</form>

<? include "rodape.php" ?>
