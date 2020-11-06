<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="auditoria,gerencia";

$gera_automatico = trim($_GET["gera_automatico"]);

if ($gera_automatico != 'automatico'){
	include "autentica_admin.php";
}

include "gera_relatorio_pararelo_include.php";

$layout_menu = "auditoria";
$title = "Auditoria - OS´s abertas a mais de 30 dias com lançamento de peças";

include 'cabecalho.php';

?>

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
</style>

<table width="700" border="0" cellpadding="0" cellspacing="2" align="center"  >
	<tr >
		<td bgcolor="#FF0000">&nbsp;&nbsp;&nbsp;&nbsp;</td>
		<td width='100%' valign="middle" align="left">&nbsp;OS com mais de 30 dias aberta com lançamento de peça</td>
	</tr>
	<tr >
		<td bgcolor="#FFCC00">&nbsp;&nbsp;&nbsp;&nbsp;</td>
		<td width='100%' valign="middle" align="left">
		OS de 15 A 30 dias aberta com lançamento de peça
		</td>
	</tr>
</table>
<br>
<br>
<?

if (strlen(trim($_POST["btn_acao"])) > 0) $btn_acao = trim($_POST["btn_acao"]);
if (strlen(trim($_GET["btn_acao"])) > 0)  $btn_acao = trim($_GET["btn_acao"]);

if (strlen(trim($_POST["posto"])) > 0) $posto = trim($_POST["posto"]);
if (strlen(trim($_GET["posto"])) > 0)  $posto = trim($_GET["posto"]);

if (strlen(trim($_POST["codigo_posto"])) > 0) $codigo_posto = trim($_POST["codigo_posto"]);
if (strlen(trim($_GET["codigo_posto"])) > 0)  $codigo_posto = trim($_GET["codigo_posto"]);

if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0 ) {
	include "gera_relatorio_pararelo.php";
}

if (strlen($posto)==0){
	if ($gera_automatico != 'automatico' and strlen($msg_erro)==0 ){
		include "gera_relatorio_pararelo_verifica.php";
	}
}

if (strlen($msg_erro) > 0) { ?>
<table width="730" border="0" cellpadding="2" cellspacing="2" align="center" class="error">
	<tr>
		<td><?echo $msg_erro?></td>
	</tr>
</table>
<br>
<? }


$estados = array("AC" => "Acre",		"AL" => "Alagoas",	"AM" => "Amazonas",			"AP" => "Amapá",
				 "BA" => "Bahia",		"CE" => "Ceará",	"DF" => "Distrito Federal",	"ES" => "Espírito Santo",
				 "GO" => "Goiás",		"MA" => "Maranhão",	"MG" => "Minas Gerais",		"MS" => "Mato Grosso do Sul",
				 "MT" => "Mato Grosso", "PA" => "Pará",		"PB" => "Paraíba",			"PE" => "Pernambuco",
				 "PI" => "Piauí",		"PR" => "Paraná",	"RJ" => "Rio de Janeiro",	"RN" => "Rio Grande do Norte",
				 "RO" => "Rondônia",	"RR" => "Roraima",	"RS" => "Rio Grande do Sul","SC" => "Santa Catarina",
				 "SE" => "Sergipe",		"SP" => "São Paulo","TO" => "Tocantins");

if(strlen($posto)==0){
	echo "<a href='".$PHP_SELF."?btn_acao=ok'>Clique aqui para gerar o relatório</a>\n";
}

if(strlen($posto)>0){
	$sql = "SELECT tbl_posto.nome         ,
		tbl_posto_fabrica.codigo_posto    ,
		tbl_posto_fabrica.contato_email
	FROM tbl_posto
	JOIN tbl_posto_fabrica USING(posto)
	WHERE fabrica = $login_fabrica
	AND   posto   = $posto ";

	$res = pg_query ($con,$sql);

	if (pg_num_rows($res) > 0) {

		$codigo_posto            = trim(pg_fetch_result($res,0,codigo_posto))         ;
		$nome                    = trim(pg_fetch_result($res,0,nome))                 ;
		$contato_email           = trim(pg_fetch_result($res,0,contato_email))         ;

		echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center' width='700'>";
		echo "<tr class='Titulo'>";
		echo "<td colspan='5' background='imagens_admin/azul.gif' height='20'><font size='2'>TOTAL DE OS'S ABERTAS COM LANÇAMENTO DE PEÇAS</font></td>";
		echo "</tr>";
		echo "<tr class='Titulo'>";
		echo "<td colspan='3' height='20'><font size='2'>$codigo_posto - $nome</font></td>";
		echo "<td colspan='2' height='20'><a href='mailto:$contato_email'><font size='2' color='#E8E8E8'>$contato_email</font></a></td>";
		echo "</tr>";

		$sql = "SELECT DISTINCT tbl_os.os                              ,
			tbl_os.sua_os                                              ,
			LPAD(tbl_os.sua_os,10,'0')                   AS os_ordem   ,
			TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura   ,
			tbl_os.data_abertura                                       ,
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
		JOIN tbl_os_produto ON tbl_os_produto.os      = tbl_os.os
		JOIN tbl_os_item    ON tbl_os_item.os_produto = tbl_os_produto.os_produto
		WHERE tbl_os.fabrica = $login_fabrica
		AND   tbl_os.posto   = $posto
		AND   tbl_os.excluida    IS NOT TRUE
		AND   tbl_os.data_fechamento IS NULL
		ORDER BY classificacao,data_abertura";

		$res = pg_query ($con,$sql);

		if (pg_num_rows($res) > 0) {

			echo "<tr class='Titulo'>";
			echo "<td ></td>";
			echo "<td >OS</td>";
			echo "<td >Abertura</td>";
			echo "<td >Produto</td>";
			echo "<td >Voltagem</td>";
			echo "</tr>";

			$total = pg_num_rows($res);

			for ($i=0; $i<pg_num_rows($res); $i++){

				$os                      = trim(pg_fetch_result($res,$i,os))             ;
				$sua_os                  = trim(pg_fetch_result($res,$i,sua_os))         ;
				$abertura                = trim(pg_fetch_result($res,$i,abertura))       ;
				$referencia              = trim(pg_fetch_result($res,$i,referencia))     ;
				$descricao               = trim(pg_fetch_result($res,$i,descricao))      ;
				$voltagem                = trim(pg_fetch_result($res,$i,voltagem))       ;
				$classificacao           = trim(pg_fetch_result($res,$i,classificacao))    ;

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
			echo "<tr class='Titulo'>
					<td colspan='4' style='font-size: 14px; font-weight:bold;'>TOTAL</td>
					<td  style='font-size: 14px; font-weight:bold;'>$total</td>
				</tr>";
			echo "</table>";
		}
	}
}

if(strlen($codigo_posto) > 0) {
		$sql = " SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto='$codigo_posto' AND fabrica = $login_fabrica";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0) {
			$posto_consulta = pg_fetch_result($res,0,posto);
		}
}
if (strlen($btn_acao)>0 AND strlen($msg_erro)==0){
	flush();
	echo "<p>Relatório gerado em ".date("d/m/Y")." as ".date("H:i")."</p>";

	$sql = "
		SELECT DISTINCT tbl_os.posto, count (distinct tbl_os.os) AS qtde_10
		INTO TEMP temp_auditoria10_$login_admin
		FROM tbl_os
		JOIN tbl_os_produto USING (os)
		JOIN tbl_os_item    USING (os_produto)
		WHERE tbl_os.fabrica = $login_fabrica
		AND tbl_os.excluida    IS NOT TRUE
		AND tbl_os.data_abertura::date > (CURRENT_DATE - INTERVAL '10 days')
		AND tbl_os.data_fechamento IS NULL
		GROUP BY tbl_os.posto;

		CREATE INDEX temp_auditoria10_POSTO$login_admin ON temp_auditoria10_$login_admin(posto);

		SELECT DISTINCT tbl_os.posto, count (distinct tbl_os.os) AS qtde_20
		INTO TEMP temp_auditoria20_$login_admin
		FROM tbl_os
		JOIN tbl_os_produto USING (os)
		JOIN tbl_os_item    USING (os_produto)
		WHERE tbl_os.fabrica = $login_fabrica
		AND tbl_os.excluida    IS NOT TRUE
		AND tbl_os.data_abertura::date BETWEEN (CURRENT_DATE - INTERVAL '20 days') AND (CURRENT_DATE - INTERVAL '10 days')
		AND tbl_os.data_fechamento IS NULL
		GROUP BY tbl_os.posto;

		CREATE INDEX temp_auditoria20_POSTO$login_admin ON temp_auditoria20_$login_admin(posto);

		SELECT DISTINCT tbl_os.posto, count (distinct tbl_os.os) AS qtde_30
		INTO TEMP temp_auditoria30_$login_admin
		FROM tbl_os
		JOIN tbl_os_produto USING (os)
		JOIN tbl_os_item    USING (os_produto)
		WHERE tbl_os.fabrica = $login_fabrica
		AND tbl_os.excluida    IS NOT TRUE
		AND tbl_os.data_abertura::date BETWEEN (CURRENT_DATE - INTERVAL '30 days') AND (CURRENT_DATE - INTERVAL '21 days')
		AND tbl_os.data_fechamento IS NULL
		GROUP BY tbl_os.posto;

		CREATE INDEX temp_auditoria30_POSTO$login_admin ON temp_auditoria30_$login_admin(posto);

		SELECT DISTINCT tbl_os.posto, count (distinct tbl_os.os) AS qtde_30_mais
		INTO TEMP temp_auditoria31_$login_admin
		FROM tbl_os
		JOIN tbl_os_produto USING (os)
		JOIN tbl_os_item    USING (os_produto)
		WHERE tbl_os.fabrica = $login_fabrica
		AND tbl_os.excluida    IS NOT TRUE
		AND tbl_os.data_abertura::date < (CURRENT_DATE - INTERVAL '30 days')
		AND tbl_os.data_fechamento IS NULL
		GROUP BY tbl_os.posto;

		CREATE INDEX temp_auditoria31_POSTO$login_admin ON temp_auditoria31_$login_admin(posto);

		SELECT tbl_posto_fabrica.codigo_posto ,
				tbl_posto_fabrica.contato_email ,
			tbl_posto.posto  ,
			tbl_posto.nome   ,
			tbl_posto.estado ,
			dias_10.qtde_10  ,
			dias_20.qtde_20  ,
			dias_30.qtde_30  ,
			dias_30_mais.qtde_30_mais
		FROM tbl_posto
		JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		LEFT JOIN temp_auditoria10_$login_admin  dias_10      ON tbl_posto.posto = dias_10.posto
		LEFT JOIN temp_auditoria20_$login_admin dias_20      ON tbl_posto.posto = dias_20.posto
		LEFT JOIN temp_auditoria30_$login_admin dias_30      ON tbl_posto.posto = dias_30.posto
		LEFT JOIN temp_auditoria31_$login_admin dias_30_mais ON tbl_posto.posto = dias_30_mais.posto
		WHERE (qtde_10 > 0 OR qtde_20 > 0 OR qtde_30 > 0  OR qtde_30_mais > 0 )
		ORDER BY estado,nome
	";
	$res = pg_query ($con,$sql);

	if (pg_num_rows($res) > 0) {


		echo "<br><br>";
		echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center' width='700'>";
		echo "<tr class='Titulo'>";
		$title_colspan = 9;
		echo "<td colspan='$title_colspan' background='imagens_admin/azul.gif' height='20'><font size='2'>TOTAL DE OS'S ABERTAS COM LANÇAMENTO DE PEÇAS</font></td>";
		echo "</tr>";

		echo "<tr class='Titulo'>";
		echo "<td>CÓDIGO POSTO</td>";
		echo "<td>NOME POSTO</td>";
		echo "<td>EMAIL</td>";
		echo "<td>até 10 dias</td>";
		echo "<td>até 20 dias</td>";
		echo "<td>até 30 dias</td>";
		echo "<td>+ 30 dias</td>";
		echo "<td>TOTAL</td>";
		echo "</tr>\n";

		for ($i=0; $i<pg_num_rows($res); $i++){

			$posto			= trim(pg_fetch_result($res,$i,posto));
			$nome			= trim(pg_fetch_result($res,$i,nome));
			$estado			= trim(pg_fetch_result($res,$i,estado));
			$codigo_posto	= trim(pg_fetch_result($res,$i,codigo_posto));
			$contato_email	= trim(pg_fetch_result($res,$i,contato_email));
			$qtde_10		= trim(pg_fetch_result($res,$i,qtde_10));
			$qtde_20		= trim(pg_fetch_result($res,$i,qtde_20));
			$qtde_30		= trim(pg_fetch_result($res,$i,qtde_30));
			$qtde_30_mais	= trim(pg_fetch_result($res,$i,qtde_30_mais));

			$cor = ($cor=="#F1F4FA")?'#F7F5F0':'#F1F4FA';

			echo "<tr class='Conteudo' align='center'>";
			echo "<td bgcolor='$cor'><a href='$PHP_SELF?posto=$posto' target='_blank'>$codigo_posto</a></td>";
			echo "<td bgcolor='$cor' align='left'>$nome</td>";
			echo "<td bgcolor='$cor' align='left'><a href='mailto:$contato_email'>$contato_email</a></td>";
			echo "<td bgcolor='$cor'>$qtde_10</td>";
			echo "<td bgcolor='$cor'>$qtde_20</td>";
			echo "<td bgcolor='#FFCC00'>$qtde_30</td>";
			echo "<td bgcolor='#FF0000'><font color='#FFFFFF'>$qtde_30_mais</font></td>";
			$total = $qtde_10 + $qtde_20 +$qtde_30 + $qtde_30_mais;
			$total_qtde_10        += $qtde_10;
			$total_qtde_20        += $qtde_20;
			$total_qtde_30        += $qtde_30;
			$total_qtde_30_mais   += $qtde_30_mais;
			echo "<td bgcolor='$cor' >$total</td>";
			$total_geral += $total;
			echo "</tr>\n";
		}
		echo "<tfoot>";
		echo "<tr class='Titulo'>
				<td colspan='6' style='font-size:14px;'><b>TOTAL</b></td>
				<td colspan='2' style='font-size:14px;'><b>$total_geral</b></td>
			</tr>";
		echo "</tfoot>";
		echo "</table>";
	}
}
echo "<br><br><br>";
 include "rodape.php" ;
?>
