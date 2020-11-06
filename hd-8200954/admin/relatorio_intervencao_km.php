<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

include "funcoes.php";
include "monitora.php";

$gera_automatico = trim($_GET["gera_automatico"]);

if ($gera_automatico != 'automatico'){
	include "autentica_admin.php";
}

//include "gera_relatorio_pararelo_include.php";


$msg_erro = "";
$intervencao_em_aprovacao = '1';
$intervencao_aprovada     = '1';
$intervencao_recusada     = '1';

if (strlen($_POST["btn_acao"]) > 0 ) $btn_acao = strtoupper($_POST["btn_acao"]);
if (strlen($_GET["btn_acao"]) > 0 )  $btn_acao = strtoupper($_GET["btn_acao"]);

if (strlen($btn_acao) > 0) {
	
	if (strlen(trim($_POST["intervencao_em_aprovacao"])) > 0) $intervencao_em_aprovacao = trim($_POST["intervencao_em_aprovacao"]);
	else $intervencao_em_aprovacao = '';

	if (strlen(trim($_POST["intervencao_aprovada"])) > 0) $intervencao_aprovada = trim($_POST["intervencao_aprovada"]);
	else $intervencao_aprovada = '';

	if (strlen(trim($_POST["intervencao_recusada"])) > 0) $intervencao_recusada = trim($_POST["intervencao_recusada"]);
	else $intervencao_recusada = '';
	
	if ($intervencao_em_aprovacao == '1') $status_consulta  = '98';
	if ($intervencao_aprovada     == '1') if (strlen($status_consulta)>0) $status_consulta .= ',99'; else $status_consulta = '99';
	if ($intervencao_recusada     == '1') if (strlen($status_consulta)>0) $status_consulta .= ',101'; else $status_consulta = '101';

	if (strlen(trim($_POST["data_inicial"])) > 0) $x_data_inicial = trim($_POST["data_inicial"]);
	if (strlen(trim($_GET["data_inicial"])) > 0) $x_data_inicial = trim($_GET["data_inicial"]);

	if ($x_data_inicial=='dd/mm/aaaa') $x_data_inicial="";
	
	$x_data_inicial = fnc_formata_data_pg($x_data_inicial);
	
	if (strlen(trim($_POST["data_final"])) > 0) $x_data_final   = trim($_POST["data_final"]);
	if (strlen(trim($_GET["data_final"])) > 0) $x_data_final = trim($_GET["data_final"]);

	if ($x_data_final=='dd/mm/aaaa') $x_data_final="";
	
	if(strlen($x_data_final) == 0 or strlen($x_data_inicial) == 0) {
		$msg_erro = "Favor informar o período da data";
	}else{
		$x_data_final   = fnc_formata_data_pg($x_data_final);
		$sql = "SELECT $x_data_final::date - $x_data_inicial::date";
		//echo $sql;
		$res = pg_exec($con,$sql);
		$qtd_dia = pg_result($res,0,0);
		if ( $qtd_dia > 31 ) {
			$msg_erro = "O limite máximo de datas é apenas 31 dias";
		}
	}
	if (strlen($x_data_inicial) > 0 && $x_data_inicial != "null") {
		$x_data_inicial = str_replace("'", "", $x_data_inicial);
		$dia_inicial = substr($x_data_inicial, 8, 2);
		$mes_inicial = substr($x_data_inicial, 5, 2);
		$ano_inicial = substr($x_data_inicial, 0, 4);
		$data_inicial = $dia_inicial . "/" . $mes_inicial . "/" . $ano_inicial;
	}
	
	if (strlen($x_data_final) > 0 && $x_data_final != "null") {
		$x_data_final = str_replace("'", "", $x_data_final);
		$dia_final = substr($x_data_final, 8, 2);
		$mes_final = substr($x_data_final, 5, 2);
		$ano_final = substr($x_data_final, 0, 4);
		$data_final = $dia_final . "/" . $mes_final . "/" . $ano_final;
	}
	
	if (strlen(trim($_POST["codigo_posto"])) > 0) $codi_posto = trim($_POST["codigo_posto"]);
	if (strlen(trim($_GET["codigo_posto"])) > 0)  $codi_posto = trim($_GET["codigo_posto"]);

	if (strlen($codi_posto)>0){
		$sql_adicional = " AND tbl_posto_fabrica.codigo_posto = '$codi_posto' ";
		$sql = "SELECT  tbl_posto_fabrica.codigo_posto as cod, tbl_posto.nome as nome, tbl_posto.posto as posto
			FROM tbl_posto JOIN tbl_posto_fabrica USING(posto)
			WHERE tbl_posto_fabrica.fabrica=$login_fabrica
			$sql_adicional";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res)>0){
			$posto_codigo = pg_result ($res,0,cod);
			$posto_nome  = pg_result ($res,0,nome);
			$posto  = pg_result ($res,0,posto);
			$sql_adicional = " AND tbl_os.posto = $posto";
		}
	}

	if (strlen($x_data_inicial)>0 AND $x_data_inicial!='null' AND strlen($x_data_final)>0 AND $x_data_final!='null' ){
		$sql_adicional_5 = " os_intervencao_km.status_data2 BETWEEN '$x_data_inicial 00:00:01' AND '$x_data_final 23:59:59'";			
	}
}

$layout_menu = "callcenter";
$title = "Relatório de OS com intervenção de KM";
?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
</style>
<script>
function fnc_pesquisa_posto2 (campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
}

</script>

<?
include "cabecalho.php";
?>

<!--
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->

<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>
<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>

<script language="javascript">

function fnc_pesquisa_peca_lista(peca_referencia, peca_descricao, tipo) {
	var url = "";
	if (tipo == "referencia") {
		url = "peca_pesquisa_lista.php?peca=" + peca_referencia.value + "&tipo=" + tipo + "&exibe=/assist/admin/relatorio_intervencao.php";
	}

	if (tipo == "descricao") {
		url = "peca_pesquisa_lista.php?descricao=" + peca_descricao.value + "&tipo=" + tipo + "&exibe=/assist/admin/relatorio_intervencao.php";
	}
	if (peca_referencia.value.length >= 3 || peca_descricao.value.length >= 3) {
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
		janela.referencia	= peca_referencia;
		janela.descricao	= peca_descricao;
		janela.preco		= document.frm_relatorio.preco_null;
		janela.focus();
	}else{
		alert("Digite pelo menos 3 caracteres!");
	}
}
</script>

<br>
<? 

if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {
	//include "gera_relatorio_pararelo.php";
}

if ($gera_automatico != 'automatico' and strlen($msg_erro)==0){
	//include "gera_relatorio_pararelo_verifica.php";
}

?>

<? if (strlen($msg_erro) > 0) { ?>
<table width="600" border="0" cellspacing="0" cellpadding="2" align="center" class="Error">
	<tr>
		<td><?echo $msg_erro?></td>
	</tr>
</table>
<br>
<? } ?>


<form name="frm_relatorio" method="post" action="<?echo $PHP_SELF?>">
<input type="hidden" name="btn_acao">
<input type="hidden" name="preco_null">
<table width='500' class='Conteudo' style='background-color: #485989' border='0' cellpadding='5' cellspacing='1' align='center'>
	
	<tr>
		<td class='Titulo' background='imagens_admin/azul.gif'>Relatório de Intervenção de KM</td>
	</tr>
	
	<tr>
		<td bgcolor='#DBE5F5'>
	
			<table width='100%' border='0' cellspacing='1' cellpadding='2' class='Conteudo'>
	
				<tr width='100%' >
					<td colspan='2' align='right' height='20'>Código Posto:&nbsp;</td>
					<td colspan='2' align='left'>
						<input class="frm" type="text" name="codigo_posto" size="10" value="<? echo $codi_posto ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm.codigo_posto,document.frm.posto_nome,'codigo')" <? } ?>>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'codigo')"></A>
					</td>	
				</tr>
				<tr>
					<td colspan='2' align='right'>Razão Social:&nbsp;</td>
					<td colspan='2' align='left'><input class="frm" type="text" name="posto_nome" size="30" value="<? echo $posto_nome ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm.codigo_posto,document.frm.posto_nome,'nome')" <? } ?>>&nbsp;<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'nome')" style="cursor:pointer;"></A>
					</td>
				</tr>

				<tr width='100%' >
					<td colspan='2' align='right' height='20'>Data Inicial:&nbsp;</td>
					<td colspan='2' align='left'>
						<input class='frm' type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" value="<? if (strlen($data_final) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
					</td>
				</tr>
				<tr width='100%' >
					<td colspan='2' align='right' height='20'>Data Final:&nbsp;</td>
					<td colspan='2' align='left'>
						<input class='frm' type="text" name="data_final" id="data_final" size="12" maxlength="10" value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
					</td>	
				</tr>
				<tr width='100%' >
					<td colspan='2' align='right' height='20'>&nbsp;</td>
					<td colspan='2' align='left'>
						(*) Data da Intervenção
					</td>	
				</tr>
				<tr width='100%' >
					<td colspan='2' align='right' height='20'>Mostrar:&nbsp;</td>
					<td colspan='2' align='left'>
						<INPUT TYPE="checkbox" NAME="intervencao_em_aprovacao" value='1' <? if (strlen($intervencao_em_aprovacao)>0) echo 'checked';?>> Em aprovação&nbsp;&nbsp;&nbsp;&nbsp;
						<INPUT TYPE="checkbox" NAME="intervencao_aprovada" value='1' <? if (strlen($intervencao_aprovada)>0)         echo 'checked';?>> Aprovada&nbsp;&nbsp;&nbsp;&nbsp;
						<INPUT TYPE="checkbox" NAME="intervencao_recusada" value='1' <? if (strlen($intervencao_recusada)>0)         echo 'checked';?>> Recusada
					</td>
				</tr>
				<tr bgcolor="#D9E2EF">
					<td colspan="4" align="center" ><br><img border="0" src="imagens/btn_pesquisar_400.gif"
					onClick="if (document.frm_relatorio.btn_acao.value=='PESQUISAR')
					alert('Aguarde submissão');
					else{
					document.frm_relatorio.btn_acao.value='PESQUISAR'; document.frm_relatorio.submit();}" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar"></td>
				</tr>

			</table>
		</td>
	</tr>
</table>

<br>

<?
if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {

	$arquivo = "xls/relatorio_intervencao_km_" . $login_fabrica . ".xls";

	echo "<p align='center' id='id_aguardando'>Aguarde, processando...</p>";
	echo "<p align='center' id='id_download' style='display:none'><a href='".$arquivo."'>Clique aqui para fazer o download em XLS (Excel)</a><br></p>";

	flush();

	##### LEGENDAS #####
	echo "<BR>";
	echo "<table width='200' border='0' align='center'>";
	echo "<tr><td windth='100%' align='left'><div name='leg' align='left' style='padding-left:10px'><b style='border:1px solid #666666;background-color:#C1E0FF;'>&nbsp; &nbsp;&nbsp;</b>&nbsp;<b> KM Aprovada</b></div></td></TR>";
	echo "<tr><td windth='100%' align='left'><div name='leg' align='left' style='padding-left:10px'><b style='border:1px solid #666666;background-color:#FFBFBF;'>&nbsp; &nbsp;&nbsp;</b>&nbsp;<b> KM Recusada</b></div></td></TR>";
	echo "</div>";
	
	
	echo "</table>";

	if (strlen($sql_adicional_5)>0)	$sql_adicional_5 = " AND ".$sql_adicional_5;

	$lista_status = "98,99,100,101";

	if (strlen($status_consulta)==0){
		$status_consulta = "98,99,100,101";
	}

	if (strlen($posto)>0) $posto = " AND tbl_os.posto=$posto";

		$sql =  "SELECT interv_km.os,
						interv_km.ultimo_status,
						interv_km.status_observacao,
						/*interv_km.status_observacao_primeiro,*/
						interv_km.status_data,
						interv_km.status_data2,
						interv_km.status_admin
				INTO TEMP temp_intervencao_km_$login_admin
				FROM ( 
					SELECT  ultima.os, 
							(
							SELECT status_os 
							FROM tbl_os_status 
							WHERE tbl_os_status.os = ultima.os AND status_os IN ($lista_status)
							ORDER BY data 
							DESC LIMIT 1
							) AS ultimo_status, ";
				if($login_fabrica <> 3){ 
					$sql .=" (SELECT observacao FROM tbl_os_status WHERE tbl_os_status.os = ultima.os AND status_os IN ($lista_status) ORDER BY data DESC LIMIT 1) AS status_observacao,
							(SELECT observacao FROM tbl_os_status WHERE tbl_os_status.os = ultima.os AND status_os IN ($lista_status) ORDER BY data ASC LIMIT 1) AS status_observacao_primeiro,
							(SELECT TO_CHAR(data,'DD/MM/YYYY')  FROM tbl_os_status WHERE tbl_os_status.os = ultima.os AND status_os IN ($lista_status) ORDER BY data DESC LIMIT 1) AS status_data,
							(SELECT data  FROM tbl_os_status WHERE  tbl_os_status.os = ultima.os AND status_os IN ($lista_status) ORDER BY data DESC LIMIT 1) AS status_data2, ";
				}else{
					$sql .=" (SELECT observacao FROM tbl_os_status WHERE tbl_os_status.os = ultima.os AND status_os IN ($lista_status) ";
					if ($intervencao_em_aprovacao != '1') $sql.=" AND tbl_os_status.admin IS not null ";
					$sql .="ORDER BY data DESC LIMIT 1) AS status_observacao,
							(SELECT TO_CHAR(data,'DD/MM/YYYY')  FROM tbl_os_status WHERE tbl_os_status.os = ultima.os AND status_os IN ($lista_status) ";
					if ($intervencao_em_aprovacao != '1') $sql.=" AND tbl_os_status.admin IS not null ";
					$sql .=" ORDER BY data DESC LIMIT 1) AS status_data,
							(SELECT data  FROM tbl_os_status WHERE  tbl_os_status.os = ultima.os AND status_os IN ($lista_status) ";
					if ($intervencao_em_aprovacao != '1') $sql.=" AND tbl_os_status.admin IS not null ";
					$sql .= " ORDER BY data DESC LIMIT 1) AS status_data2, ";
				}
				if($login_fabrica <> 3){ 
					$sql .= " (SELECT tbl_admin.login FROM tbl_os_status LEFT JOIN tbl_admin USING(admin) WHERE  tbl_os_status.os = ultima.os AND status_os IN ($lista_status) ORDER BY data DESC LIMIT 1) AS status_admin ";
				}else{
					$sql .= " (SELECT tbl_admin.login FROM tbl_os_status JOIN tbl_admin USING(admin) WHERE  tbl_os_status.os = ultima.os AND status_os IN ($lista_status) ORDER BY data DESC LIMIT 1) AS status_admin ";
				}
				$sql .= " FROM (
							SELECT DISTINCT os FROM tbl_os_status WHERE status_os IN ($lista_status)
						) ultima 
					) interv_km 
				WHERE interv_km.ultimo_status IN ($status_consulta);

				CREATE INDEX temp_intervencao_os_km_$login_admin   ON temp_intervencao_km_$login_admin(os);
				CREATE INDEX temp_intervencao_data_km_$login_admin ON temp_intervencao_km_$login_admin(status_data2);

				SELECT tbl_os.os                                                 ,
					tbl_os.sua_os                                                     ,
					LPAD(tbl_os.sua_os,20,'0')                   AS ordem             ,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura          ,
					TO_CHAR(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf           ,
					tbl_os.nota_fiscal                                                ,
					tbl_os.data_abertura   AS abertura_os       ,
					tbl_os.admin                                                      ,
					tbl_posto_fabrica.codigo_posto                                    ,
					tbl_posto.nome as posto_nome                                      ,
					tbl_produto.referencia                      AS produto_referencia ,
					tbl_produto.descricao                       AS produto_descricao  ,
					tbl_os.qtde_km                                                    ,
					os_intervencao_km.ultimo_status AS status_os                      ,
					os_intervencao_km.status_observacao                               ,
					/*os_intervencao_km.status_observacao_primeiro,*/
					os_intervencao_km.status_data                                     ,
					os_intervencao_km.status_data2                                    ,
					os_intervencao_km.status_admin
				FROM temp_intervencao_km_$login_admin os_intervencao_km
				JOIN tbl_os            ON os_intervencao_km.os       = tbl_os.os AND tbl_os.fabrica=$login_fabrica
				JOIN tbl_posto         ON tbl_posto.posto         = tbl_os.posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN tbl_produto       ON tbl_produto.produto     = tbl_os.produto ";

			$sql .= " WHERE tbl_os.fabrica = $login_fabrica
				/*AND tbl_os.posto <> 6359*/
				$sql_adicional
				$sql_adicional_5
				ORDER BY status_data2 DESC";

	//echo $sql;

	$res = pg_exec($con,$sql);
	if (pg_numrows($res) > 0) {
		$arquivo_conteudo  = "";
		$arquivo_conteudo .= "<table width='650' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
		$arquivo_conteudo .=  "<tr class='Titulo'>";
		$arquivo_conteudo .=  "<td colspan='14'>RELAÇÃO DE OS</td>";
		$arquivo_conteudo .=  "</tr>";
		$arquivo_conteudo .=  "<tr class='Titulo'>";
		$arquivo_conteudo .=  "<td>OS</td>";
		$arquivo_conteudo .=  "<td>ABERTURA</td>";
		if ($login_fabrica==3)$arquivo_conteudo .=  "<td>DATA NF</td>";
		if ($login_fabrica==3)$arquivo_conteudo .=  "<td>NF</td>";
		$arquivo_conteudo .=  "<td>CÓDIGO POSTO</td>";
		$arquivo_conteudo .=  "<td>POSTO</td>";
		$arquivo_conteudo .=  "<td>PRODUTO</td>";
		$arquivo_conteudo .=  "<td>KM</td>";
		$arquivo_conteudo .=  "<td>SITUAÇÃO</td>";
		$arquivo_conteudo .=  "<td>DATA FINAL INTERVENÇÃO</td>";
		$arquivo_conteudo .=  "<td>ADMIN</td>";
		if ($login_fabrica==3){
			$arquivo_conteudo .=  "<td>JUSTIFICATIVA</td>";
		}
		$arquivo_conteudo .=  "</tr>";

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$os                 = trim(pg_result($res,$i,os));
			$sua_os             = trim(pg_result($res,$i,sua_os));
			$data_abertura      = trim(pg_result($res,$i,abertura));
			$data_nf            = trim(pg_result($res,$i,data_nf));
			$nota_fiscal        = trim(pg_result($res,$i,nota_fiscal));
			$codigo_posto       = trim(pg_result($res,$i,codigo_posto));
			$posto_nome         = trim(pg_result($res,$i,posto_nome));
			$produto_referencia = trim(pg_result($res,$i,produto_referencia));
			$produto_descricao  = trim(pg_result($res,$i,produto_descricao));
			$qtde_km            = trim(pg_result($res,$i,qtde_km));
			$status_data		= trim(pg_result($res,$i,status_data));
			$status_os			= trim(pg_result($res,$i,status_os));
			$status_observacao	= trim(pg_result($res,$i,status_observacao));
			//$status_observacao_primeiro = trim(pg_result($res,$i,status_observacao_primeiro));
			$admin				= trim(pg_result($res,$i,status_admin));
			$status_os			= trim(pg_result($res,$i,status_os));
			

			if ($status_os==98){
				$status='Em aprovação';
			}
			if ($status_os==99){
				$status='Aprovada';
			}
			if ($status_os==101){
				$status='Recusada';
			}

			//$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;
			if ($os==$os_anterior){

			}else{
				if ($cor=="#F7F5F0")
					$cor="#F1F4FA";
				else
					$cor="#F7F5F0";
			}

			$os_anterior = $os;

			if ($status_os=="98") 
				$cor="#F1F4FA";
			if ($status_os=="99")
				$cor="#C1E0FF";
			if ($status_os=="101")
				$cor="#FFBFBF";


			$arquivo_conteudo .=  "<tr class='Conteudo' bgcolor='$cor'>";
			$arquivo_conteudo .=  "<td nowrap align='center'><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></td>";
			$arquivo_conteudo .=  "<td nowrap align='center'>".$data_abertura . "</td>";
			if ($login_fabrica==3)$arquivo_conteudo .=  "<td nowrap align='center'>".$data_nf . "</td>";
			if ($login_fabrica==3)$arquivo_conteudo .=  "<td nowrap align='center'>".$nota_fiscal . "</td>";
			$arquivo_conteudo .=  "<td nowrap align='center'>".$codigo_posto . "</td>";
			$arquivo_conteudo .=  "<td nowrap align='left'>" . $posto_nome . "</td>";
			$arquivo_conteudo .=  "<td nowrap align='left'>$produto_referencia - $produto_descricao</td>";
			$arquivo_conteudo .=  "<td nowrap align='center'>&nbsp;" . $qtde_km . "&nbsp;</td>";
			$arquivo_conteudo .=  "<td nowrap align='center'>" . $status . "</td>";
			$arquivo_conteudo .=  "<td nowrap align='center'>" . $status_data . "</td>";
			$arquivo_conteudo .=  "<td nowrap align='center'>" . $admin . "</td>";

			if ($login_fabrica==3){
				$justificativa = trim($status_observacao);
				$arquivo_conteudo .=  "<td nowrap align='left'>".trim($justificativa)."</td>";
			}

			$arquivo_conteudo .=  "</tr>";
		}
		$arquivo_conteudo .=  "</table>";
		echo $arquivo_conteudo;
		echo "<br>";

		$fp = fopen ($arquivo,"w");
		fwrite ($fp,$arquivo_conteudo);
		fclose($fp);
		echo "<script language='javascript'>document.getElementById('id_download').style.display='inline'</script>";
		echo "<script language='javascript'>document.getElementById('id_aguardando').style.display='none'</script>";
	} else {
		echo "Nenhuma Os encontrada.";
	}
}

include "rodape.php";
?>
