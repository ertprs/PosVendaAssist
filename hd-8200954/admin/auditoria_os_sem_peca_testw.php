<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="auditoria,gerencia";

$gera_automatico = trim($_GET["gera_automatico"]);

if ($gera_automatico != 'automatico'){
	include "autentica_admin.php";
}
$email  = $_GET['email'];
if(strlen($email)==0) $email  = $_POST['email'];
if($email =='true'){
	if(strlen($_POST['btn_mail']) > 0) {
		$msg_erro="";
		$titulo   = trim($_POST['titulo']);
		$conteudo = trim($_POST['conteudo']);
		if(strlen($conteudo)==0) {
			$msg_erro = "Por favor, digite o conteúdo do E-mail";
		}

		if(strlen($titulo)==0) {
			$msg_erro = "Por favor, digite o assunto do E-mail";
		}
		$tipo_os   = trim($_POST['tipo_os']);
		if($tipo_os == 'ate_5') {
			$sql_cond = " AND tbl_os.data_abertura::date > (CURRENT_DATE - INTERVAL '5 days') ";
		}elseif($tipo_os == 'ate_15') {
			$sql_cond = " AND tbl_os.data_abertura::date BETWEEN (CURRENT_DATE - INTERVAL '15 days') AND (CURRENT_DATE - INTERVAL '6 days') ";
		}elseif($tipo_os == 'ate_30') {
			$sql_cond = " AND tbl_os.data_abertura::date BETWEEN (CURRENT_DATE - INTERVAL '30 days') AND (CURRENT_DATE - INTERVAL '16 days') ";
		}elseif($tipo_os =='mais_30') {
			$sql_cond = " AND tbl_os.data_abertura::date < (CURRENT_DATE - INTERVAL '30 days') ";
		}
		$sql_cond2 = " AND     tbl_os.os NOT IN  (
													SELECT interv_reinc.os
													FROM (
															SELECT
															ultima_reinc.os,
															(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = ultima_reinc.os AND status_os IN (13,19,68,67,70,115,118) ORDER BY data DESC LIMIT 1) AS ultimo_reinc_status
															FROM (SELECT DISTINCT os FROM tbl_os_status WHERE status_os IN (13,19,68,67,70,115,118) ) ultima_reinc
														) interv_reinc
													WHERE interv_reinc.ultimo_reinc_status IN (13)
												) ";

		if(strlen($msg_erro) == 0) {
			$sqlx=" SELECT email,nome_completo,to_char(current_timestamp,'MDHI24MISS') as data FROM tbl_admin WHERE admin = $login_admin";
			$resx=pg_exec($con,$sqlx);
			$email_remetente = pg_result($resx,0,email);
			$admin_nome      = pg_result($resx,0,nome_completo);
			$data            = pg_result($resx,0,data);

			$sql ="SELECT tbl_os.posto,count(tbl_os.os) as qtde_os
					INTO TEMP temp_auditoria_$data
					FROM tbl_os
					LEFT JOIN tbl_os_produto USING (os)
					LEFT JOIN tbl_os_item    USING (os_produto)
					WHERE tbl_os.fabrica = $login_fabrica
					AND tbl_os.excluida    IS NOT TRUE
					AND tbl_os_item.os_item IS NULL
					AND tbl_os.data_fechamento IS NULL
					$sql_cond
					$sql_cond2
					GROUP BY tbl_os.posto;

					CREATE INDEX temp_auditoria_POSTO$data ON temp_auditoria_$data(posto);

					SELECT  tbl_posto_fabrica.codigo_posto ,
							tbl_posto.nome                 ,
							tbl_posto.estado               ,
							tbl_posto_fabrica.contato_email
					FROM tbl_posto
					JOIN tbl_posto_fabrica			ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					LEFT JOIN temp_auditoria_$data	ON tbl_posto.posto = temp_auditoria_$data.posto
					WHERE (qtde_os > 0 )
					AND   contato_email IS NOT NULL
					AND credenciamento <>'DESCREDENCIADO'
					ORDER BY tbl_posto.nome";

//			echo nl2br($sql);
//			$res = pg_exec ($con,$sql);
			if(pg_numrows($res) > 0){
				for ( $i = 0 ; $i < pg_numrows($res) ; $i++ ) {

					if($i % 20 ==0 and $i > 0) {
						sleep(5);
					}
					$contato_email = trim(pg_result($res,$i,contato_email));
					$nome          = pg_result($res,$i,nome);
					$codigo_posto  = pg_result($res,$i,codigo_posto);

					$remetente    = $email_remetente;
					$destinatario = $codigo_posto." - ".$nome ." <".$contato_email."> ";
					$headers="Return-Path: <".$remetente.">\nFrom: $admin_nome <".$remetente.">\nContent-type: text/html\n";
					if(mail($destinatario,$titulo,$conteudo,$headers)) {
						$msg_erro=" Email enviado com sucesso";
					};
				}
			}
		}
	}

	if(strlen($msg_erro) >0){
		echo "<table border='0' cellpadding='0' cellspacing='0' align='center' bgcolor='#ffffff' width = '300'>";
		echo "<tr>";
		echo "<td valign='middle' align='center' class='error'>";
		echo $msg_erro;
		echo "</td>";
		echo "</tr>";
		echo "</table>";
	}

	echo "<form name='frm_mail' method='post' action='$PHP_SELF'>";
	echo "<div id='mensagem'>";
	echo "</div>";
	echo "<table width = '350'><tr>";
	echo "<td>Tipo OS</td>";
	echo "<td align='left'>";
	echo "<select name='tipo_os' size='1'>";
	echo "<option value='ate_5'>Até 5 dias</option>";
	echo "<option value='ate_15'>Até 15 dias</option>";
	echo "<option value='ate_30'>Até 30 dias</option>";
	echo "<option value='mais_30'>+ 30 dias</option>";
	echo "</select>";
	echo "</td></tr>";
	echo "<tr>";
	echo "<input type='hidden' name='email' value='true'>";
	echo "<td>Assunto</td><td><input type='text' size='40' name='titulo' value='$titulo'>";
	echo "</td></tr>";
	echo "<tr><td valign='top'>";
	echo "Mensagem</td><td> <textarea name='conteudo' ROWS='10' COLS='48' class='input' value='$conteudo'></textarea>";
	echo "</td></tr>";
	echo "<tr><td align='center' colspan='100%'>";
	echo "<input type='hidden' name='btn_mail' value=''>";
	echo "<input type='button' name='btn_acao' value='Enviar E-MAIL' onclick=\"javascript: if (document.frm_mail.btn_mail.value == '' ) { document.frm_mail.btn_mail.value='continuar' ;  document.frm_mail.submit(); document.getElementById('mensagem').innerHTML='Por favor, não feche esta janela até aparecer a mensagem que foram enviados e-mails com sucesso.'; } else { alert ('Dados já gravados. Se você clicou em Voltar no seu browser ou clicou mais de uma vez no botão, acesse novamente a tela pelos Menus do sistema.') }\" >";
	echo "</td></tr></table>";

	echo "</form>";
	exit;

}

include "gera_relatorio_pararelo_include.php";

$layout_menu = "auditoria";
$title = "AUDITORIA - OS ABERTAS SEM LANÇAMENTO DE PEÇAS";

include 'cabecalho.php';

?>
<script language="JavaScript">
function enviaEmail() {
	var url = "";
	url = "<? echo $PHP_SELF;?>?email=true";
	janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
}
</script>
<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 9px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Arial;
	font-size: 9px;
	font-weight: normal;
}
.ConteudoBranco {
	font-family: Arial;
	font-size: 9px;
	color:#FFFFFF;
	font-weight: normal;
}
.Mes{
	font-size: 8px;
}

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
}
</style>

<table width="700" border="0" cellpadding="0" cellspacing="2" align="center"  >
	<tr style="font:12px Arial;">
		<td bgcolor="#FF0000">&nbsp;&nbsp;&nbsp;&nbsp;</td>
		<td width='100%' valign="middle" align="left">&nbsp;OS aberta sem lançamento de peças com mais de 30 dias</td>
	</tr>
	<tr style="font:12px Arial;">
		<td bgcolor="#FFCC00">&nbsp;&nbsp;&nbsp;&nbsp;</td>
		<td width='100%' valign="middle" align="left">
			&nbsp;<?if($login_fabrica==6) echo "OS de 20 a 30 dias aberta sem lançamento de peças";
									 else echo "OS aberta sem lançamento de peças entre 15 e 30 dias";?>
		</td>
	</tr>
</table>

<?

if (strlen(trim($_POST["btn_acao"])) > 0) $btn_acao = trim($_POST["btn_acao"]);
if (strlen(trim($_GET["btn_acao"])) > 0)  $btn_acao = trim($_GET["btn_acao"]);

if($btn_acao)
	echo "<br><font style='font:12px Arial;'>* Relatório gerado em ".date("d/m/Y")." as ".date("H:i")."</font><br><br>";

if (strlen(trim($_POST["posto"])) > 0) $posto = trim($_POST["posto"]);
if (strlen(trim($_GET["posto"])) > 0)  $posto = trim($_GET["posto"]);

if (strlen(trim($_POST["codigo_posto"])) > 0) $codigo_posto = trim($_POST["codigo_posto"]);
if (strlen(trim($_GET["codigo_posto"])) > 0)  $codigo_posto = trim($_GET["codigo_posto"]);

$filtro_estado = false;
if (strlen(trim($_POST['filtro_estado'])) == 2) $filtro_estado = $_POST['filtro_estado'];

if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0 and !$filtro_estado) {
	include "gera_relatorio_pararelo.php";
}

if (strlen($posto)==0){
	if ($gera_automatico != 'automatico' and strlen($msg_erro)==0 and !$filtro_estado){
		include "gera_relatorio_pararelo_verifica.php";
	}
}

if (strlen($msg_erro) > 0) { ?>
<table width="700" border="0" cellpadding="2" cellspacing="2" align="center" >
	<tr class="msg_erro">
		<td><?echo $msg_erro?></td>
	</tr>
</table>
<br />
<? }

if($login_fabrica == 50 and strlen($posto) == 0) { // HD 56651 ?>
<? include "javascript_pesquisas.php" ?>
<FORM name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
<table width='700' class='formulario'  border='0' cellpadding='5' cellspacing='1' align='center'>
	<tr class="titulo_tabela">
		<td>Relatório Avulsos Pagos em Extrato</td>
	</tr>
	<tr>
		<td bgcolor='#DBE5F5' valign='bottom'>
			<table width='100%' border='0' cellspacing='1' cellpadding='2' >
				<tr class="titulo_coluna">
					<td width="10">&nbsp;</td>
					<td align='right' nowrap><font size='2'>Código Posto</td>
					<td align='left'>
						<input type="text" name="codigo_posto" id="codigo_posto" size="12"  value="<? echo $codigo_posto ?>" class="Caixa">
						<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'codigo')">
					</td>
					<td align='right' nowrap><font size='2'>Nome do Posto</td>
					<td align='left'>
						<input type="text" name="posto_nome" id="posto_nome" size="30"  value="<?echo $posto_nome?>" class="Caixa">
						<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'nome')">
					</td>
					<td width="10">&nbsp;</td>
				</tr>
				<tr>
					<td align='center' colspan='100%'><br>
						<input type='submit' name='btn_acao' value='Consultar'>
					</td>
				</tr>
				</table><br>
		</td>
	</tr>
</table>
</FORM>
<? }

$estados = array("AC" => "Acre",		"AL" => "Alagoas",	"AM" => "Amazonas",			"AP" => "Amapá",
				 "BA" => "Bahia",		"CE" => "Ceará",	"DF" => "Distrito Federal",	"ES" => "Espírito Santo",
				 "GO" => "Goiás",		"MA" => "Maranhão",	"MG" => "Minas Gerais",		"MS" => "Mato Grosso do Sul",
				 "MT" => "Mato Grosso", "PA" => "Pará",		"PB" => "Paraíba",			"PE" => "Pernambuco",
				 "PI" => "Piauí",		"PR" => "Paraná",	"RJ" => "Rio de Janeiro",	"RN" => "Rio Grande do Norte",
				 "RO" => "Rondônia",	"RR" => "Roraima",	"RS" => "Rio Grande do Sul","SC" => "Santa Catarina",
				 "SE" => "Sergipe",		"SP" => "São Paulo","TO" => "Tocantins");

if(strlen($posto)==0){
	if ($login_fabrica==24) {   //  08/12/2009 MLG HD 173646
?>	<form action="<?=$PHP_SELF?>" name="gera_relatorio" id="gr" title="Gerar Relatório" method="POST">
		<label for="filtro_estado" accesskey="E" title="Selecione um estado para consultar só esses postos">
		Estado:
		</label>
		<select name="filtro_estado" class="frm" id="filtro_estado" title="Para filtrar por estado, selecione o Estado">
			<option value="">Todos</option>
<?
	    foreach ($estados as $sigla=>$nome_estado) {
	        $estado_sel = ($sigla == $filtro_estado) ? " selected":"";
	    	echo "\t\t\t<option value='$sigla'$estado_sel>$nome_estado</option>\n";
	    }
?>		</select>
		<button name='btn_acao' class="frm" value='ok'>Clique aqui para gerar o relatório</button>
	</form>
<?	} else {
		echo "<a href='".$PHP_SELF."?btn_acao=ok'><button name='btn_acao' value=''>Gerar Relatório</button></a>\n";
	}
}

if(strlen($posto)>0){
	$sql = "SELECT tbl_posto.nome         ,
		tbl_posto_fabrica.codigo_posto    ,
		tbl_posto_fabrica.contato_email
	FROM tbl_posto
	JOIN tbl_posto_fabrica USING(posto)
	WHERE fabrica = $login_fabrica
	AND   posto   = $posto ";

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {

		$codigo_posto            = trim(pg_result($res,0,codigo_posto))         ;
		$nome                    = trim(pg_result($res,0,nome))                 ;
		$contato_email           = trim(pg_result($res,0,contato_email))         ;

		echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center' width='700'>";
		echo "<tr class='titulo_tabela'>";
		echo "<td colspan='5' height='20'><font size='2'>Total de OS Abertas sem Lançamento de Peças</font></td>";
		echo "</tr>";
		echo "<tr class='Titulo'>";
		echo "<td colspan='3' height='20'><font size='2'>$codigo_posto - $nome</font></td>";
		echo "<td colspan='2' height='20'><a href='mailto:$contato_email'><font size='2' color='#E8E8E8'>$contato_email</font></a></td>";
		echo "</tr>";

		if($login_fabrica==50){
			$sql_cond = " AND     tbl_os.os NOT IN  (
														SELECT interv_reinc.os
														FROM (
																SELECT
																ultima_reinc.os,
																(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = ultima_reinc.os AND status_os IN (13,19,68,67,70,115,118) ORDER BY data DESC LIMIT 1) AS ultimo_reinc_status
																FROM (SELECT DISTINCT os FROM tbl_os_status WHERE status_os IN (13,19,68,67,70,115,118) )ultima_reinc
															) interv_reinc
														WHERE interv_reinc.ultimo_reinc_status IN (13)
													) ";
		}

		$sql = "SELECT  tbl_os.os                                      ,
			tbl_os.sua_os                                              ,
			LPAD(tbl_os.sua_os,10,'0')                   AS os_ordem   ,
			TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura   ,
			tbl_produto.referencia                                     ,
			tbl_produto.descricao                                      ,
			tbl_produto.voltagem                                       ,
			CASE
				WHEN tbl_os.data_abertura::date < CURRENT_DATE - INTERVAL '30 days' THEN 0
				WHEN tbl_os.data_abertura::date   BETWEEN CURRENT_DATE - INTERVAL '30 days' AND CURRENT_DATE - INTERVAL '16 days' THEN 1
				ELSE 2
			END                                           AS classificacao
		FROM      tbl_os
		JOIN      tbl_produto    ON tbl_produto.produto    = tbl_os.produto
		LEFT JOIN tbl_os_produto ON tbl_os_produto.os      = tbl_os.os
		LEFT JOIN tbl_os_item    ON tbl_os_item.os_produto = tbl_os_produto.os_produto
		WHERE tbl_os.fabrica = $login_fabrica
		AND   tbl_os.posto   = $posto
		AND   tbl_os.excluida    IS NOT TRUE
		AND   tbl_os_item.os_item    IS NULL
		AND   tbl_os.data_fechamento IS NULL
		$sql_cond
		ORDER BY tbl_os.data_abertura, os_ordem";


		if($login_fabrica == 6){
			$sql = "SELECT  tbl_os.os                                                  ,
				tbl_os.sua_os                                              ,
				LPAD(tbl_os.sua_os,10,'0')                   AS os_ordem   ,
				TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura   ,
				tbl_produto.referencia                                     ,
				tbl_produto.descricao                                      ,
				tbl_produto.voltagem                                       ,
				CASE
					WHEN tbl_os.data_abertura::date < CURRENT_DATE - INTERVAL '30 days' THEN 0
					WHEN tbl_os.data_abertura::date   BETWEEN CURRENT_DATE - INTERVAL '30 days' AND CURRENT_DATE - INTERVAL '21 days' THEN 1
					ELSE 2
				END                                           AS classificacao
			FROM      tbl_os
			JOIN      tbl_produto    ON tbl_produto.produto    = tbl_os.produto
			LEFT JOIN tbl_os_produto ON tbl_os_produto.os      = tbl_os.os
			LEFT JOIN tbl_os_item    ON tbl_os_item.os_produto = tbl_os_produto.os_produto
			WHERE tbl_os.fabrica = $login_fabrica
			AND   tbl_os.posto   = $posto
			AND   tbl_os.excluida    IS NOT TRUE
			AND   tbl_os_item.os_item    IS NULL
			AND   tbl_os.data_fechamento IS NULL
			ORDER BY tbl_os.data_abertura, os_ordem";
		}
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {

			echo "<tr class='titulo_coluna'>";
			echo "<td ></td>";
			echo "<td >OS</td>";
			echo "<td >Abertura</td>";
			echo "<td >Produto</td>";
			echo "<td >Voltagem</td>";
			echo "</tr>";

			$total = pg_numrows($res);

			for ($i=0; $i<pg_numrows($res); $i++){

				$os                      = trim(pg_result($res,$i,os))             ;
				$sua_os                  = trim(pg_result($res,$i,sua_os))         ;
				$abertura                = trim(pg_result($res,$i,abertura))       ;
				$referencia              = trim(pg_result($res,$i,referencia))     ;
				$descricao               = trim(pg_result($res,$i,descricao))      ;
				$voltagem                = trim(pg_result($res,$i,voltagem))       ;
				$classificacao           = trim(pg_result($res,$i,classificacao))    ;

				if($cor=="#F1F4FA")$cor = '#F7F5F0';
				else               $cor = '#F1F4FA';
				if($classificacao==0) $cor = "#FF0000";
				if($classificacao==1) $cor = "#FFCC00";
				if($classificacao_anterior <> $classificacao)
					$x = 1;
				echo "<tr class='";
				if($classificacao == 0) echo "ConteudoBranco";
				else                    echo "Conteudo"      ;
				echo "'align='center'>";
				echo "<td bgcolor='$cor' >$x</td>";
				echo "<td bgcolor='$cor' >";
				echo "<a href='os_press?os=$os' target='_blank'>$sua_os</a></td>";
				echo "<td bgcolor='$cor' >$abertura</td>";
				echo "<td bgcolor='$cor' align='left'>$referencia - $descricao</td>";
				echo "<td bgcolor='$cor' >$voltagem</td>";
				echo "</tr>";

				$x = $x+1;
				$classificacao_anterior = $classificacao;

			}
			echo "<tr class='titulo_coluna'>
					<td colspan='4'>Total</td>
					<td >$total</td>
				</tr>";
			echo "</table>";
		}
	}
}

if(strlen($codigo_posto) > 0) {
		$sql = " SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto='$codigo_posto' AND fabrica = $login_fabrica";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res) > 0) {
			$posto_consulta = pg_result($res,0,posto);
		}
}
if (strlen($btn_acao)>0 AND strlen($msg_erro)==0){
	flush();
	//echo "<p>Relatório gerado em ".date("d/m/Y")." as ".date("H:i")."</p>";

	if($login_fabrica==50){
		$sql_cond = " AND     tbl_os.os NOT IN  (
													SELECT interv_reinc.os
													FROM (
															SELECT
															ultima_reinc.os,
															(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = ultima_reinc.os AND status_os IN (13,19,68,67,70,115,118) ORDER BY data DESC LIMIT 1) AS ultimo_reinc_status
															FROM (SELECT DISTINCT os FROM tbl_os_status WHERE status_os IN (13,19,68,67,70,115,118) ) ultima_reinc
														) interv_reinc
													WHERE interv_reinc.ultimo_reinc_status IN (13)
												) ";
		if(strlen($posto_consulta) > 0) {
			$sql_cond2 = " AND tbl_os.posto = $posto_consulta ";
		}
		$sql_cond3 = " AND credenciamento <>'DESCREDENCIADO' ";
	}



	$sql = "
		SELECT DISTINCT tbl_os.posto, count (tbl_os.os) AS qtde_5
		INTO TEMP temp_auditoria5_$login_admin
		FROM tbl_os
		$sql_join
		JOIN tbl_posto USING(posto)
		LEFT JOIN tbl_os_produto USING (os)
		LEFT JOIN tbl_os_item    USING (os_produto)
		WHERE tbl_os.fabrica = $login_fabrica
		AND tbl_os.excluida    IS NOT TRUE
		AND tbl_os.data_abertura::date > (CURRENT_DATE - INTERVAL '5 days')
		AND tbl_os_item.os_item IS NULL
		AND tbl_os.data_fechamento IS NULL
		$sql_cond
		$sql_cond2";

		if ($login_fabrica != 2) {
			$sql .= ($filtro_estado) ? "AND estado = '$filtro_estado' " : " ";
		} 
		
		$sql .= "

		GROUP BY tbl_os.posto;

		CREATE INDEX temp_auditoria5_POSTO$login_admin ON temp_auditoria5_$login_admin(posto);

		SELECT DISTINCT tbl_os.posto, count (tbl_os.os) AS qtde_15
		INTO TEMP temp_auditoria15_$login_admin
		FROM tbl_os
		$sql_join
		LEFT JOIN tbl_os_produto USING (os)
		LEFT JOIN tbl_os_item    USING (os_produto)
		WHERE tbl_os.fabrica = $login_fabrica
		AND tbl_os.excluida    IS NOT TRUE
		AND tbl_os.data_abertura::date BETWEEN (CURRENT_DATE - INTERVAL '15 days') AND (CURRENT_DATE - INTERVAL '6 days')
		AND tbl_os_item.os_item IS NULL
		AND tbl_os.data_fechamento IS NULL
		$sql_cond
		$sql_cond2
		GROUP BY tbl_os.posto;

		CREATE INDEX temp_auditoria15_POSTO$login_admin ON temp_auditoria15_$login_admin(posto);

		SELECT DISTINCT tbl_os.posto, count (tbl_os.os) AS qtde_30
		INTO TEMP temp_auditoria30_$login_admin
		FROM tbl_os
		$sql_join
		LEFT JOIN tbl_os_produto USING (os)
		LEFT JOIN tbl_os_item    USING (os_produto)
		WHERE tbl_os.fabrica = $login_fabrica
		AND tbl_os.excluida    IS NOT TRUE
		AND tbl_os.data_abertura::date BETWEEN (CURRENT_DATE - INTERVAL '30 days') AND (CURRENT_DATE - INTERVAL '16 days')
		AND tbl_os_item.os_item IS NULL
		AND tbl_os.data_fechamento IS NULL
		$sql_cond
		$sql_cond2
		GROUP BY tbl_os.posto;

		CREATE INDEX temp_auditoria30_POSTO$login_admin ON temp_auditoria30_$login_admin(posto);

		SELECT DISTINCT tbl_os.posto, count (tbl_os.os) AS qtde_30_mais
		INTO TEMP temp_auditoria31_$login_admin
		FROM tbl_os
		$sql_join
		LEFT JOIN tbl_os_produto USING (os)
		LEFT JOIN tbl_os_item    USING (os_produto)
		WHERE tbl_os.fabrica = $login_fabrica
		AND tbl_os.excluida    IS NOT TRUE
		AND tbl_os.data_abertura::date < (CURRENT_DATE - INTERVAL '30 days')
		AND tbl_os_item.os_item IS NULL
		AND tbl_os.data_fechamento IS NULL
		$sql_cond
		$sql_cond2
		GROUP BY tbl_os.posto;

		CREATE INDEX temp_auditoria31_POSTO$login_admin ON temp_auditoria31_$login_admin(posto);

		SELECT tbl_posto_fabrica.codigo_posto ,
				tbl_posto_fabrica.contato_email ,
			tbl_posto.posto  ,
			tbl_posto.nome   ,
			tbl_posto.estado ,
			dias_5.qtde_5    ,
			dias_15.qtde_15  ,
			dias_30.qtde_30  ,
			dias_30_mais.qtde_30_mais
		FROM tbl_posto
		JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		LEFT JOIN temp_auditoria5_$login_admin  dias_5       ON tbl_posto.posto = dias_5.posto
		LEFT JOIN temp_auditoria15_$login_admin dias_15      ON tbl_posto.posto = dias_15.posto
		LEFT JOIN temp_auditoria30_$login_admin dias_30      ON tbl_posto.posto = dias_30.posto
		LEFT JOIN temp_auditoria31_$login_admin dias_30_mais ON tbl_posto.posto = dias_30_mais.posto
		WHERE (qtde_5 > 0 OR qtde_15 > 0 OR qtde_30 > 0  OR qtde_30_mais > 0 )
		$sql_cond3
	";

		$sql .= " ORDER BY tbl_posto.nome;";

	if($login_fabrica == 6){
		$sql = "
			SELECT tbl_os.posto, count (tbl_os.os) AS qtde_5
			INTO TEMP temp_auditoria5_$login_admin
			FROM   tbl_os
			LEFT JOIN tbl_os_produto USING (os)
			LEFT JOIN tbl_os_item    USING (os_produto)
			WHERE tbl_os.fabrica = $login_fabrica
			AND tbl_os.excluida    IS NOT TRUE
			AND tbl_os.data_abertura::date > (CURRENT_DATE - INTERVAL '5 days')
			AND tbl_os_item.os_item IS NULL
			AND tbl_os.data_fechamento IS NULL
			GROUP BY tbl_os.posto;

			CREATE INDEX temp_auditoria5_POSTO$login_admin ON temp_auditoria5_$login_admin(posto);

			SELECT tbl_os.posto, count (tbl_os.os) AS qtde_15
			INTO TEMP temp_auditoria15_$login_admin
			FROM tbl_os
			LEFT JOIN tbl_os_produto USING (os)
			LEFT JOIN tbl_os_item    USING (os_produto)
			WHERE tbl_os.fabrica = $login_fabrica
			AND   tbl_os.excluida    IS NOT TRUE
			AND tbl_os.data_abertura::date BETWEEN (CURRENT_DATE - INTERVAL '20 days') AND (CURRENT_DATE - INTERVAL '6 days')
			AND tbl_os_item.os_item IS NULL
			AND tbl_os.data_fechamento IS NULL
			GROUP BY tbl_os.posto;

			CREATE INDEX temp_auditoria15_POSTO$login_admin ON temp_auditoria15_$login_admin(posto);

			SELECT tbl_os.posto, count (tbl_os.os) AS qtde_30
			INTO TEMP temp_auditoria30_$login_admin
			FROM tbl_os
			LEFT JOIN tbl_os_produto USING (os)
			LEFT JOIN tbl_os_item    USING (os_produto)
			WHERE tbl_os.fabrica = $login_fabrica
			AND   tbl_os.excluida    IS NOT TRUE
			AND tbl_os.data_abertura::date BETWEEN (CURRENT_DATE - INTERVAL '30 days') AND (CURRENT_DATE - INTERVAL '21 days')
			AND tbl_os_item.os_item IS NULL
			AND tbl_os.data_fechamento IS NULL
			GROUP BY tbl_os.posto;

			CREATE INDEX temp_auditoria30_POSTO$login_admin ON temp_auditoria30_$login_admin(posto);

			SELECT tbl_os.posto, count (tbl_os.os) AS qtde_30_mais
			INTO TEMP temp_auditoria31_$login_admin
			FROM tbl_os
			LEFT JOIN tbl_os_produto USING (os)
			LEFT JOIN tbl_os_item    USING (os_produto)
			WHERE tbl_os.fabrica = $login_fabrica
			AND tbl_os.excluida    IS NOT TRUE
			AND tbl_os.data_abertura::date < (CURRENT_DATE - INTERVAL '30 days')
			AND tbl_os_item.os_item IS NULL
			AND tbl_os.data_fechamento IS NULL
			GROUP BY tbl_os.posto;

			CREATE INDEX temp_auditoria31_POSTO$login_admin ON temp_auditoria31_$login_admin(posto);

			SELECT  tbl_posto_fabrica.codigo_posto ,
				tbl_posto_fabrica.contato_email,
				tbl_posto.posto  ,
				tbl_posto.nome   ,
				tbl_posto.estado ,
				dias_5.qtde_5    ,
				dias_15.qtde_15  ,
				dias_30.qtde_30  ,
				dias_30_mais.qtde_30_mais
			FROM tbl_posto
			JOIN tbl_posto_fabrica                               ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN temp_auditoria5_$login_admin  dias_5       ON tbl_posto.posto = dias_5.posto
			LEFT JOIN temp_auditoria15_$login_admin dias_15      ON tbl_posto.posto = dias_15.posto
			LEFT JOIN temp_auditoria30_$login_admin dias_30      ON tbl_posto.posto = dias_30.posto
			LEFT JOIN temp_auditoria31_$login_admin dias_30_mais ON tbl_posto.posto = dias_30_mais.posto
			WHERE (qtde_5 > 0 OR qtde_15 > 0 OR qtde_30 > 0  OR qtde_30_mais > 0 )
			ORDER BY tbl_posto.nome
		";
	}
echo nl2br($sql);
exit;
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {


		echo "<br><br>";
		if ($filtro_estado) echo "<p align='center' style='font-size: 12px'>Postos do estado: <b>".$estados[$filtro_estado]."</b></p>";
		echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center' width='700'>";
		echo "<tr class='titulo_tabela'>";
		$title_colspan = ($filtro_estado) ? 8 : 9;
		echo "<td colspan='$title_colspan' height='20'><font size='2'>Total de OS Abertas sem Lançamento de Peças</font></td>";
		echo "</tr>";

		echo "<tr class='titulo_coluna'>";
		if (!$filtro_estado and $login_fabrica==24) echo "<td>UF</td>";
		echo "<td>Código Posto</td>";
		echo "<td>Nome Posto</td>";
		echo "<td>E-mail</td>";
		echo "<td>até 5 dias</td>";
		if($login_fabrica == 6) echo "<td>até 20 dias</td>"; else echo "<td>até 15 dias</td>";
		echo "<td>até 30 dias</td>";
		echo "<td>+ 30 dias</td>";
		echo "<td>Total</td>";
		echo "</tr>\n";

		for ($i=0; $i<pg_numrows($res); $i++) {

			$posto			= trim(pg_result($res,$i,posto));
			$nome			= trim(pg_result($res,$i,nome));
			$estado			= trim(pg_result($res,$i,estado));
			$codigo_posto	= trim(pg_result($res,$i,codigo_posto));
			$contato_email	= trim(pg_result($res,$i,contato_email));
			$qtde_5			= trim(pg_result($res,$i,qtde_5));
			$qtde_15		= trim(pg_result($res,$i,qtde_15));
			$qtde_30		= trim(pg_result($res,$i,qtde_30));
			$qtde_30_mais	= trim(pg_result($res,$i,qtde_30_mais));

			$cor = ($cor=="#F1F4FA")?'#F7F5F0':'#F1F4FA';

			echo "<tr class='Conteudo' align='center'>";
			if (!$filtro_estado and $login_fabrica==24) echo "<td bgcolor='$cor' title='{$estados[$estado]}'>$estado</td>";
			echo "<td bgcolor='$cor'><a href='$PHP_SELF?posto=$posto' target='_blank'>$codigo_posto</a></td>";
			echo "<td bgcolor='$cor' align='left'>$nome</td>";
			echo "<td bgcolor='$cor' align='left'><a href='mailto:$contato_email'>$contato_email</a></td>";
			echo "<td bgcolor='$cor'>$qtde_5</td>";
			echo "<td bgcolor='$cor'>$qtde_15</td>";
			echo "<td bgcolor='#FFCC00'>$qtde_30</td>";
			echo "<td bgcolor='#FF0000'><font color='#FFFFFF'>$qtde_30_mais</font></td>";
			$total = $qtde_5 + $qtde_15 +$qtde_30 + $qtde_30_mais;
			$total_qtde_5         += $qtde_5;
			$total_qtde_15        += $qtde_15;
			$total_qtde_30        += $qtde_30;
			$total_qtde_30_mais   += $qtde_30_mais;
			echo "<td bgcolor='$cor' >$total</td>";
			$total_geral = $total + $total_geral;
			echo "</tr>\n";
		}
		echo "<tfoot>";
		if($login_fabrica==50) { // HD 57319
			echo "<tr class='Titulo'>
					<td colspan='3' style='font-size:14px;'><b>Subtotal</b></td>
					<td style='font-size:14px;'><b>$total_qtde_5</b></td>
					<td style='font-size:14px;'><b>$total_qtde_15</b></td>
					<td style='font-size:14px;'><b>$total_qtde_30</b></td>
					<td style='font-size:14px;'><b>$total_qtde_30_mais</b></td>
					<td style='font-size:14px;'></td>
				</tr>";
		}
		echo "<tr class='titulo_coluna'>
				<td colspan='6' style='font-size:14px;'><b>Total</b></td>
				<td colspan='2' style='font-size:14px;'><b>$total_geral</b></td>
			</tr>";
		if($login_fabrica==50) { // HD 57316
		echo "<tr>
			<td colspan='7'>&nbsp;</td>
			<td ><input type='button' onClick=\"javascript: enviaEmail();\" value='Enviar E-mail'></td>
		</tr>";
		}
		echo "</tfoot>";
		echo "</table>";
	}
}
echo "<br><br><br>";
 include "rodape.php" ;
?>
