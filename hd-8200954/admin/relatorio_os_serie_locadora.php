<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="auditoria";
include 'autentica_admin.php';

include "funcoes.php";

if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);

$msg = "";

if (isset($acao)) {
	##### Pesquisa entre datas #####
	if (strlen(trim($_POST["data_inicial"])) > 0) $data_inicial = trim($_POST["data_inicial"]);
	if (strlen(trim($_GET["data_inicial"])) > 0)  $data_inicial = trim($_GET["data_inicial"]);
	if (strlen(trim($_POST["data_final"])) > 0)   $data_final   = trim($_POST["data_final"]);
	if (strlen(trim($_GET["data_final"])) > 0)    $data_final   = trim($_GET["data_final"]);
	
	if (strlen($msg) == 0) {
		 if(empty($data_inicial) OR empty($data_final)){
			$msg = "Data Inválida";
		}

		if(strlen($msg)==0){
			list($di, $mi, $yi) = explode("/", $data_inicial);
			if(!checkdate($mi,$di,$yi)) 
				$msg = "Data Inválida";
		}
		if(strlen($msg)==0){
			list($df, $mf, $yf) = explode("/", $data_final);
			if(!checkdate($mf,$df,$yf)) 
				$msg = "Data Inválida";
		}

		if(strlen($msg)==0){
			$x_data_inicial = "$yi-$mi-$di";
			$x_data_final = "$yf-$mf-$df";
		}
		


		if(strlen($msg)==0){
			if(strtotime($x_data_final) < strtotime($x_data_inicial)){
				$msg = "Data Inválida.";
			}
		}
		
		 if(strlen($msg)==0){
			if (strtotime($x_data_inicial) < strtotime($x_data_final . ' -1 month')) {
				$msg = 'O intervalo entre as datas não pode ser maior que 1 mês.';
			}
		 }


	}



	##### Pesquisa de produto #####
	if (strlen(trim($_POST["posto_codigo"])) > 0) $posto_codigo  = trim($_POST["posto_codigo"]);
	if (strlen(trim($_GET["posto_codigo"])) > 0)  $posto_codigo  = trim($_GET["posto_codigo"]);
	if (strlen(trim($_POST["posto_nome"])) > 0)   $posto_nome    = trim($_POST["posto_nome"]);
	if (strlen(trim($_GET["posto_nome"])) > 0)    $posto_nome    = trim($_GET["posto_nome"]);
	if (strlen(trim($_POST["posto_estado"])) > 0) $posto_estado  = trim($_POST["posto_estado"]);
	if (strlen(trim($_GET["posto_estado"])) > 0)  $posto_estado  = trim($_GET["posto_estado"]);
	if (strlen($posto_codigo) > 0 && strlen($posto_nome) > 0) {
		$sql =	"SELECT tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome                ,
						tbl_posto.posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica
 				AND   tbl_posto_fabrica.codigo_posto = '$posto_codigo';";
		$res = pg_query($con,$sql);
		if (pg_num_rows($res) == 1) {
			$posto        = pg_fetch_result($res,0,posto);
			$posto_codigo = pg_fetch_result($res,0,codigo_posto);
			$posto_nome   = pg_fetch_result($res,0,nome);
		}else{
			$msg .= " Posto não encontrado. ";
		}
	}


	if (strlen(trim($_POST["admin"])) > 0) $solucao = trim($_POST["admin"]);
	if (strlen(trim($_GET["admin"])) > 0)  $solucao = trim($_GET["admin"]);
}

$layout_menu = "auditoria";
$title = "RELATÓRIO OS NÚMERO DE SÉRIE LOCADORA";

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

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
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

<? include "javascript_pesquisas.php"; ?>
<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>

<? if (strlen($msg) > 0) { ?>
<table width="700" border="0" cellpadding="2" cellspacing="2" align="center" class="msg_erro">
	<tr>
		<td><?echo $msg?></td>
	</tr>
</table>
<br>
<? } ?>

<form name="frm_consulta" method="post" action="<?echo $PHP_SELF?>">

<input type="hidden" name="acao">

<table width="700" align="center" border="0" cellspacing="0" cellpadding="2" class='formulario'>
	<tr class="titulo_tabela">
		<td colspan="5">Parâmetros de Pesquisa</td>
	</tr>
	<tr>
		<td width="70">&nbsp;</td>
		<td width='130'>
				Data Inicial <br />
				<input size="12" maxlength="10" TYPE="text" NAME="data_inicial" id="data_inicial" value="<?echo substr($data_inicial,0,10);?>"  class="frm">	
			</td>
		<td  width='130' colspan='2'>
				Data Final <br />
				<input size="12" maxlength="10" TYPE="text" NAME="data_final" id="data_final" value="<?echo substr($data_final,0,10);?>" class="frm">
			</td>
		<td width="10">&nbsp;</td>
	</tr>
		
	<TR>
		<TD>&nbsp;</TD>

		<TD>
				Cod. Posto <br />
				<input type="text" name="posto_codigo" size="8" value="<?echo $posto_codigo?>" class="frm">
				<img src="imagens/lupa.png" style="cursor: hand;" align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.posto_codigo, document.frm_consulta.posto_nome,'codigo');">
		</TD>

		<TD width='250'>
				Nome do Posto <br />
				<input type="text" name="posto_nome" size="30" value="<?echo $posto_nome?>" class="frm">
				<img src="imagens/lupa.png" style="cursor: hand;" align='absmiddle' alt="Clique aqui para pesquisas postos pelo nome" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.posto_codigo, document.frm_consulta.posto_nome,'nome');">
		</TD>
		
		<TD colspan='2'>
				Admin <br />
				<select name="admin" size="1" class="frm">
					<option name=""></option>
					<?
						$sql =	"SELECT admin,
								login
							FROM tbl_admin
							WHERE fabrica = $login_fabrica
							AND   ativo
							ORDER BY login;";
						$res = pg_query($con,$sql);

						if (pg_num_rows($res) > 0) {
							for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
								$x_admin = trim(pg_fetch_result($res,$i,admin));
								$login   = trim(pg_fetch_result($res,$i,login));
								echo "<option value='$x_admin'";
								if ($admin == $x_admin) echo " selected";
								echo ">$login</option>";
							}
						}
					?>
				</select>
		</TD>
		
	</TR>
	
	<tr >
		<td colspan="5" align="center" style='padding:20px 0 20px 0;'><input type="button" value="Pesquisar" onclick="javascript: document.frm_consulta.acao.value='PESQUISAR'; document.frm_consulta.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar"></td>
	</tr>
</table>
</form>
<?
if(isset($_POST['acao']) AND empty($msg)) {
	if(!empty($posto)) {
		$cond_posto_evenda = " AND tbl_os_revenda.posto  = $posto ";
		$cond_posto_os= " AND tbl_os.posto  = $posto ";
	}
	if(!empty($admin)) {
		$cond_admin_revenda = " AND tbl_os_revenda.admin  = $admin ";
		$cond_admin_os= " AND tbl_os.admin  = $admin ";
	}
	$sql = "SELECT DISTINCT
				A.os_revenda         ,
				A.sua_os             ,
				A.tipo_os            ,
				A.tipo_atendimento   ,
				A.explodida          ,
				A.abertura           ,
				A.digitacao          ,
				A.data_fechamento    ,
				A.excluida           ,
				A.serie              ,
				A.codigo_posto       ,
				A.nome_posto         ,
				A.produto_referencia ,
				A.produto_descricao  ,
				A.impressa           ,
				A.extrato            ,
				A.tipo_os_cortesia   ,
				A.admin_nome         ,
				A.consumidor_nome    ,
				A.consumidor_revenda ,
				A.qtde_item          ,
				SUBSTRING(A.sua_os,1,5) as sub_sua_os 
				FROM (
				(
					SELECT  DISTINCT
							tbl_os_revenda.os_revenda                                                ,
							tbl_os_revenda.sua_os                                                    ,
							tbl_os_revenda.tipo_os                                                   ,
							tbl_os_revenda.tipo_atendimento                                          ,
							tbl_os_revenda.explodida                                                 ,
							TO_CHAR(tbl_os_revenda.data_abertura,'DD/MM/YYYY') AS abertura           ,
							to_char(tbl_os_revenda.digitacao,'DD/MM/YYYY')     AS digitacao          ,
							current_date                                       AS data_fechamento    ,
							false                                              AS excluida           ,
							tbl_os_revenda_item.serie                          AS serie              ,
							tbl_posto_fabrica.codigo_posto                                           ,
							tbl_posto.nome                                     AS nome_posto         ,
							tbl_produto.referencia                             AS produto_referencia ,
							tbl_produto.descricao                              AS produto_descricao  ,
							current_date                                       AS impressa           ,
							0                                                  AS extrato            ,
							tbl_os_revenda.tipo_os_cortesia                                          ,
							tbl_admin.login                                    AS admin_nome         ,
							null                                               AS consumidor_nome    ,
							null                                               AS consumidor_revenda ,
							0                                                  AS qtde_item
					FROM      tbl_os_revenda
					JOIN      tbl_os_revenda_item ON  tbl_os_revenda_item.os_revenda = tbl_os_revenda.os_revenda
					JOIN tbl_posto                ON  tbl_posto.posto                = tbl_os_revenda.posto
					JOIN tbl_posto_fabrica        ON  tbl_posto_fabrica.posto        = tbl_posto.posto  AND tbl_posto_fabrica.fabrica      = $login_fabrica
					JOIN tbl_admin on tbl_os_revenda.admin = tbl_admin.admin
					JOIN tbl_locacao ON tbl_os_revenda_item.serie = tbl_locacao.serie
					JOIN tbl_produto on tbl_os_revenda_item.produto = tbl_produto.produto
					WHERE tbl_os_revenda.fabrica = $login_fabrica 
					AND   (tbl_os_revenda.cortesia is true OR tbl_os_revenda.tipo_atendimento = 35)
					AND   NOT(tbl_os_revenda.admin IS NULL)
					AND   NOT(tbl_os_revenda_item.serie IS NULL)
					AND   tbl_os_revenda.digitacao > '2010-09-06 00:00:00'
					AND   tbl_os_revenda.digitacao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'
					$cond_posto_revenda
					$cond_admin_revenda ";

					$sql .= " ) UNION (
						SELECT  tbl_os.os                                  AS os_revenda         ,
								tbl_os.sua_os                                                    ,
								tbl_os.tipo_os                                                   ,
								tbl_os.tipo_atendimento                                          ,
								NULL                                        AS explodida         ,
								TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')  AS abertura          ,
								TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS digitacao         ,
								tbl_os.data_fechamento                                           ,
								tbl_os.excluida                                                  ,
								tbl_os.serie                                                     ,
								tbl_posto_fabrica.codigo_posto                                   ,
								tbl_posto.nome as posto                                          ,
								tbl_produto.referencia                     AS produto_referencia ,
								tbl_produto.descricao                      AS produto_descricao  ,
								tbl_os_extra.impressa                                            ,
								tbl_os_extra.extrato                                             ,
								tbl_os.tipo_os_cortesia                                          ,
								tbl_admin.login as admin_nome                                    ,
								tbl_os.consumidor_nome                                           ,
								tbl_os.consumidor_revenda                                        ,
								(
									SELECT COUNT(1) AS qtde_item
									FROM   tbl_os_item
									JOIN   tbl_os_produto USING (os_produto)
									WHERE  tbl_os_produto.os = tbl_os.os
								)                                          AS qtde_item
						FROM tbl_os
						JOIN tbl_os_extra       ON  tbl_os_extra.os           = tbl_os.os
						JOIN tbl_produto        ON  tbl_produto.produto       = tbl_os.produto
						JOIN tbl_posto          ON  tbl_posto.posto           = tbl_os.posto
						JOIN tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
						JOIN tbl_admin on tbl_os.admin=tbl_admin.admin
						JOIN tbl_locacao ON tbl_os.serie = tbl_locacao.serie
						WHERE tbl_os.fabrica = $login_fabrica
						AND   NOT(tbl_os.admin IS NULL)
						AND   NOT(tbl_os.serie IS NULL)
						AND   (tbl_os.cortesia is true OR tbl_os.tipo_atendimento = 35)
						AND   tbl_os.data_digitacao > '2010-09-06 00:00:00'
						AND   tbl_os.data_digitacao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'
						$cond_posto_os
						$cond_admin_os ";
	
	$sql .= " )
			) AS A
		WHERE (1=1 ) ORDER BY SUBSTRING(A.sua_os,1,5) ASC, A.os_revenda ASC ";
	$res = pg_query($con,$sql);
	

	if (@pg_num_rows($res) == 0) {
		echo "<TABLE width='700' height='50'><TR><TD align='center'>Nenhum resultado encontrado.</TD></TR></TABLE>";
	}else{
		echo "<br>";
		echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='1' class='tabela'>\n";

		echo "<TR class='menu_top'>\n";
		echo "<TD colspan=13>$msg</TD>\n";
		echo "</TR>\n";
		echo "<TR class='titulo_coluna'>\n";
		echo "<TD>OS</TD>\n";
		echo "<TD>Série</TD>\n";
		echo "<TD width='075'>Abertura</TD>\n";
		echo "<TD width='075'>Fechamento</TD>\n";
		echo "<TD width='130'>Admin</TD>\n";
		echo "<TD width='130'>Consumidor</TD>\n";
		echo "<TD width='130'>Posto</TD>\n";
		echo "<TD>Produto</TD>\n";
		echo "<TD NOWRAP>Tipo OS Cortesia</TD>\n";
		echo "<TD width='170' colspan='2' align='center'>Ações</TD>\n";
		echo "</TR>\n";

		for ($i = 0 ; $i < pg_num_rows ($res) ; $i++){
			$os                 = trim(pg_fetch_result ($res,$i,os_revenda));
			$data               = trim(pg_fetch_result ($res,$i,digitacao));
			$abertura           = trim(pg_fetch_result ($res,$i,abertura));
			$sua_os             = trim(pg_fetch_result ($res,$i,sua_os));
			$serie              = trim(pg_fetch_result ($res,$i,serie));
			$consumidor_nome    = trim(pg_fetch_result ($res,$i,consumidor_nome));
			$posto_nome         = trim(pg_fetch_result ($res,$i,nome_posto));
			$codigo_posto       = trim(pg_fetch_result ($res,$i,codigo_posto));
			$produto_nome       = trim(pg_fetch_result ($res,$i,produto_descricao));
			$produto_referencia = trim(pg_fetch_result ($res,$i,produto_referencia));
			$data_fechamento    = trim(pg_fetch_result ($res,$i,data_fechamento));
			$excluida           = trim(pg_fetch_result ($res,$i,excluida));
			$tipo_os_cortesia   = trim(pg_fetch_result ($res,$i,tipo_os_cortesia));
			$tipo_os            = trim(pg_fetch_result ($res,$i,tipo_os));
			$tipo_atendimento   = trim(pg_fetch_result ($res,$i,tipo_atendimento));
			$admin_nome         = trim(pg_fetch_result ($res,$i,admin_nome));
			$explodida          = trim(pg_fetch_result ($res,$i,explodida));
			$consumidor_revenda = trim(pg_fetch_result ($res,$i,consumidor_revenda));

			$cor = "#F7F5F0"; 
			$btn = 'amarelo';
			if ($i % 2 == 0) 
			{
				$cor = '#F1F4FA';
				$btn = 'azul';
			}
			
			if ($excluida == "t") $cor = "#FFE1E1";

			if (strlen (trim ($sua_os)) == 0) $sua_os = $os;

			$referencia = str_replace("-","",$produto_referencia);
			$referencia = str_replace(" ","",$referencia);
			$referencia = str_replace("/","",$referencia);
			$referencia = str_replace(".","",$referencia);

			$referencia_produto = explode('_',$referencia);
			$sqlv ="SELECT locacao
							FROM tbl_produto
							JOIN tbl_linha USING(linha)
							JOIN tbl_locacao USING(produto)
							WHERE tbl_linha.fabrica = $login_fabrica
							AND   trim(tbl_locacao.serie)::text = '$serie'
							AND   referencia_pesquisa like '$referencia_produto[0]%'
							LIMIT 1";
			$resv = pg_query($con,$sqlv);
			if(pg_num_rows($resv) == 0){
				continue;
			}
			$existe_os = true;
			echo "<TR style='background-color: $cor;'>\n";
			echo "<TD nowrap>&nbsp;$serie</TD>\n";
			echo "<TD align='center'>&nbsp;$abertura</TD>\n";
			echo "<TD align='center'>&nbsp;$fechamento</TD>\n";
			echo "<TD nowrap>&nbsp;<ACRONYM TITLE=\"$admin_nome\">".substr($admin_nome,0,17)."</ACRONYM></TD>\n";
			echo "<TD nowrap>&nbsp;<ACRONYM TITLE=\"$consumidor_nome\"> ".substr($consumidor_nome,0,17)."</ACRONYM></TD>\n";
			echo "<TD nowrap>&nbsp;<ACRONYM TITLE=\"$codigo_posto - $posto_nome\">".substr($posto_nome,0,17)."</ACRONYM></TD>\n";
			echo "<TD nowrap>&nbsp;<ACRONYM TITLE=\"$produto_referencia - $produto_nome\">".substr($produto_referencia,0,17)."</ACRONYM></TD>\n";
			echo "<TD align='center' nowrap>&nbsp;".$tipo_os_cortesia."";

			if($tipo_atendimento == 35) {
				$sqlt = " SELECT descricao
								FROM tbl_tipo_atendimento
							WHERE tipo_atendimento = $tipo_atendimento ";
				$rest = @pg_query($con,$sqlt);
				if(pg_num_rows($res) > 0){
					echo "<br/>(".@pg_fetch_result($rest,0,0). ")" ;
				}
			}
			echo "</TD>\n";

			echo "<TD>";
			if(strlen($consumidor_revenda)==0 and ($excluida == "f" OR strlen($excluida) == 0)){
				echo "&nbsp;";
			}elseif ($excluida == "f" OR strlen($excluida) == 0) {
				echo "<input type='button' onclick='javascript: window.open(\"os_press.php?os=$os\")' value='Consultar'>";
			}
			echo "</TD>\n";

			echo "<TD>";
			if (strlen($consumidor_revenda) ==0 and strlen($explodida) == 0){ 
			echo "<input type='button' onclick='javascript: window.open(\"os_revenda_finalizada.php?os_revenda=$os&btn_acao=explodir\")' value='Explodir'>";
			}else{
				echo "&nbsp;";
			}
			echo "</td>";
			echo "</TR>\n";
		}

		if(!$existe_os) {
			echo "<tr><td colspan='100%' align='center'>Nenhum resultado encontrado</td></tr>";
		}
	}
	echo "</TABLE>\n";
}

?>
<? include "rodape.php" ?>
