<?
/* O Relatorio que é enviado para MIAMI é um excel gerado pelo 
	perl /www/cgi_bin/blackedecker/six-sigma.pl (Geralmente é o Miguel e a Silvania que pede!!!
	Não esquecer de alterar o range das datas....colocar 3 meses.
*/
#echo "temporariamente desativado";exit;
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "gerencia,call_center";
include "autentica_admin.php";

include "funcoes.php";

$erro = "";

if (strlen($_POST["botao"]) > 0) $botao = strtoupper($_POST["botao"]);

if (strtoupper($btnacao) == "BUSCAR") {
	$x_data_inicial = trim($_POST["data_inicial"]);
	$x_data_final   = trim($_POST["data_final"]);
	$linha          = $_POST["linha"];
	$estado         = $_POST["estado"];
	$ordem          = $_POST["ordem"];
	$ordem1         = $_POST["ordem1"];

	if (strlen($x_data_inicial) == 0) $erro .= " Preencha o campo Data Inicial.<br> ";
	if (strlen($x_data_final) == 0)   $erro .= " Preencha o campo Data Final.<br> ";


	if (strlen($erro) == 0) {
		$x_data_inicial = fnc_formata_data_pg($x_data_inicial);
		$x_data_final   = fnc_formata_data_pg($x_data_final);
		
		if ($x_data_inicial != "null") {
			$data_inicial = substr($x_data_inicial,9,2) . "/" . substr($x_data_inicial,6,2) . "/" . substr($x_data_inicial,1,4);
		}else{
			$data_inicial = "";
			$erro .= " Preencha correto o campo Data Inicial.<br> ";
		}
		
		if ($x_data_final != "null") {
			$data_final = substr($x_data_final,9,2) . "/" . substr($x_data_final,6,2) . "/" . substr($x_data_final,1,4);
		}else{
			$data_final = "";
			$erro .= " Preencha correto o campo Data Final.<br> ";
		}
	}
	
	$xdata_i = str_replace("'","",$x_data_inicial);
	$xdata_f = str_replace("'","",$x_data_final);
	
	if (strlen($erro) > 0) {
		$msg = "Foi detectado o seguinte erro:<br>";
		$msg .= $erro;
	}else{
		$relatorio = "gerar";
	}
}

$layout_menu = "auditoria";

$title = "Visão geral por produto";
include 'cabecalho.php';

?>

<style type="text/css">
<!--
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
-->
</style>


<script LANGUAGE="JavaScript">
	function Redirect(produto, data_i, data_f, mobra) {
		window.open('rel_new_visao_geral_peca.php?produto=' + produto + '&data_i=' + data_i + '&data_f=' + data_f + '&mobra=' + mobra,'1', 'height=400,width=750,location=no,scrollbars=yes,menubar=no,toolbar=no,resizable=no')
	}
</script>

<script LANGUAGE="JavaScript">
	function Redirect1(produto, data_i, data_f) {
		window.open('rel_new_visao_os.php?produto=' + produto + '&data_i=' + data_i + '&data_f=' + data_f + '&estado=<? echo $estado; ?>','1', 'height=400,width=750,location=no,scrollbars=yes,menubar=no,toolbar=no,resizable=no')
	}
</script>

<script language="JavaScript" src="js/cal2.js"></script>
<script language="JavaScript" src="js/cal_conf2.js"></script>

<p>

<? if (strlen($erro) > 0) { ?>
<table width="420" border="0" cellpadding="2" cellspacing="0" align="center" class="error">
	<tr>
		<td><?echo $erro?></td>
	</tr>
</table>
<br>
<? } ?>

<br>

<center>
<font face='arial' color='<? echo $cor_forte ?>'><b>Apenas das OS em extratos enviados ao financeiro</b></font>
<center>

<br>
<form method="POST" action="<?echo $PHP_SELF?>" name="frm_os_aprovada">

<table width="500" border="0" cellpadding="2" bgcolor='#D9E2EF' cellspacing="2" align="center" background="<?echo $fundo?>">
	<tr>
		<td width="250" class="Conteudo" bgcolor="#D9E2EF" align="center">
			Data Início
		</td>
		<td class="Conteudo" bgcolor="#D9E2EF" align="center">
			Data Final
		</td>
	</tr>
		
	<tr>		
		<td align="center" bgcolor="#FFFFFF"><input type="text" name="data_inicial" size="11" maxlength="10" value="<? echo $data_inicial ?>" class="frm">
		<img border="0" src="imagens_admin/btn_lupa.gif" align="absmiddle" onclick="javascript: showCal('PesquisaInicial');" style="cursor: hand;" alt="Clique aqui para abrir o calendário"><font size='1'>(dd/mm/aaaa)</font></td>
		<td align="center" bgcolor="#FFFFFF"><input type="text" name="data_final" size="11" maxlength="10" value="<? echo $data_final ?>" class="frm">
		<img border="0" src="imagens_admin/btn_lupa.gif" align="absmiddle" onclick="javascript: showCal('PesquisaFinal');" style="cursor: hand;" alt="Clique aqui para abrir o calendário"><font size='1'>(dd/mm/aaaa)</font></td>
	</tr>
		

	<tr>
		<td width="250" class="Conteudo" bgcolor="#D9E2EF" align="center">
			Linha
		</td>
		<td class="Conteudo" bgcolor="#D9E2EF" align="center">
			Estado
		</td>
	</tr>

	<tr>
		<td align="center" bgcolor="#FFFFFF">
			<?
			$sql = "SELECT   linha,
							 nome
					FROM     tbl_linha
					where    fabrica = $login_fabrica
					ORDER BY nome;";
			$res = pg_exec ($con,$sql);
			
			if (@pg_numrows($res) > 0) {
				echo "<select name='linha'>\n";
				echo "<option value=''></option>\n";
				
				for ($x = 0 ; $x < pg_numrows($res) ; $x++){
					$aux_linha = trim(pg_result($res,$x,linha));
					$aux_nome  = trim(pg_result($res,$x,nome));
					
					echo "<option value='$aux_linha'"; if ($linha == $aux_linha) echo " SELECTED "; echo ">$aux_nome</option>\n";
				}
				
				echo "</select>\n";
			}
			?>
		</td>

		<td align="center" bgcolor="#FFFFFF">
			<select name="estado" size="1">
			<option value=""   <? if (strlen($estado) == 0)    echo " selected "; ?>>UF</option>
			<option value="AC" <? if ($estado == "AC") echo " selected "; ?>>AC - Acre</option>
			<option value="AL" <? if ($estado == "AL") echo " selected "; ?>>AL - Alagoas</option>
			<option value="AM" <? if ($estado == "AM") echo " selected "; ?>>AM - Amazonas</option>
			<option value="AP" <? if ($estado == "AP") echo " selected "; ?>>AP - Amapá</option>
			<option value="BA" <? if ($estado == "BA") echo " selected "; ?>>BA - Bahia</option>
			<option value="CE" <? if ($estado == "CE") echo " selected "; ?>>CE - Ceará</option>
			<option value="DF" <? if ($estado == "DF") echo " selected "; ?>>DF - Distrito Federal</option>
			<option value="ES" <? if ($estado == "ES") echo " selected "; ?>>ES - Espírito Santo</option>
			<option value="GO" <? if ($estado == "GO") echo " selected "; ?>>GO - Goiás</option>
			<option value="MA" <? if ($estado == "MA") echo " selected "; ?>>MA - Maranhão</option>
			<option value="MG" <? if ($estado == "MG") echo " selected "; ?>>MG - Minas Gerais</option>
			<option value="MS" <? if ($estado == "MS") echo " selected "; ?>>MS - Mato Grosso do Sul</option>
			<option value="MT" <? if ($estado == "MT") echo " selected "; ?>>MT - Mato Grosso</option>
			<option value="PA" <? if ($estado == "PA") echo " selected "; ?>>PA - Pará</option>
			<option value="PB" <? if ($estado == "PB") echo " selected "; ?>>PB - Paraíba</option>
			<option value="PE" <? if ($estado == "PE") echo " selected "; ?>>PE - Pernambuco</option>
			<option value="PI" <? if ($estado == "PI") echo " selected "; ?>>PI - Piauí</option>
			<option value="PR" <? if ($estado == "PR") echo " selected "; ?>>PR - Paraná</option>
			<option value="RJ" <? if ($estado == "RJ") echo " selected "; ?>>RJ - Rio de Janeiro</option>
			<option value="RN" <? if ($estado == "RN") echo " selected "; ?>>RN - Rio Grande do Norte</option>
			<option value="RO" <? if ($estado == "RO") echo " selected "; ?>>RO - Rondônia</option>
			<option value="RR" <? if ($estado == "RR") echo " selected "; ?>>RR - Roraima</option>
			<option value="RS" <? if ($estado == "RS") echo " selected "; ?>>RS - Rio Grande do Sul</option>
			<option value="SC" <? if ($estado == "SC") echo " selected "; ?>>SC - Santa Catarina</option>
			<option value="SE" <? if ($estado == "SE") echo " selected "; ?>>SE - Sergipe</option>
			<option value="SP" <? if ($estado == "SP") echo " selected "; ?>>SP - São Paulo</option>
			<option value="TO" <? if ($estado == "TO") echo " selected "; ?>>TO - Tocantins</option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="Conteudo" bgcolor="#ffffff" align="center" colspan="2" >
			<input type="submit" value="BUSCAR" name="btnacao" class="btnrel" style="width:100px">
		</td>
	</tr>
</table>

</form>

<H1>Programa em manutenção, <br> Favor conferir o resultado antes de aplicar. Aguardo sua confirmação. <br> Estamos criando o LINK com Postos x OS </H1>

<?
echo "<table width='650' border='0' cellpadding='2' cellspacing='2' align='center'>";
echo "<tr>";

echo "<td bgcolor='#FFFFFF' align='center' width='100%'>";
echo "<font face='Verdana, Arial, Helvetica, sans' color='$css' size='2'>$msg</font>";
echo "</td>";

echo "</tr>";
echo "</table>";



if ($relatorio == "gerar" ) {
	
	# Estes SQLs não têm função alguma neste programa. 
	# Devem ser rodados manualmente para extrair informações do banco antigo
	# e importar para o banco novo, possibilitando as pesquisas deste relatório
	#			CASE WHEN tbl_new_os_item.preco IS NULL THEN 0 ELSE tbl_new_os_item.preco END AS preco                    ,

	$sql_antigo = "
		DROP TABLE garantias;
		SELECT  tbl_produto.referencia_black           AS produto_referencia_black ,
				tbl_produto.referencia                 AS referencia               ,
				tbl_produto.voltagem                   AS voltagem                 ,
				tbl_produto.linha_blackedecker         AS linha                    ,
				tbl_new_os_extra.total_mao_de_obra     AS mobra                    ,
				tbl_new_os_extra.total_pecas           AS pecas                    ,
				tbl_new_os_extra.total                 AS TOTAL                    ,
				tbl_posto.codigo                       AS codigo_posto             ,
				tbl_cidade.estado                      AS estado                   ,
				tbl_new_extrato_financeiro.data_envio::date AS data_financeiro
		INTO TEMP garantias
		FROM tbl_new_os
		JOIN tbl_produto                      ON tbl_produto.produto                    = tbl_new_os.produto
		JOIN tbl_posto                        ON tbl_posto.posto                        = tbl_new_os.posto
		JOIN tbl_cidade                       ON tbl_cidade.municipio                   = tbl_posto.municipio
		JOIN tbl_new_os_extra                 ON tbl_new_os_extra.new_os                = tbl_new_os.new_os
		JOIN tbl_new_extrato                  ON tbl_new_extrato.new_extrato            = tbl_new_os_extra.new_extrato
		JOIN tbl_new_extrato_financeiro       ON tbl_new_extrato_financeiro.new_extrato = tbl_new_extrato.new_extrato
		;
		COPY garantias TO '/tmp/blackedecker/garantias_os.txt' ;


		DROP TABLE garantias;
		SELECT  tbl_produto.referencia_black           AS produto_referencia_black ,
				tbl_produto.referencia                 AS referencia               ,
				tbl_produto.voltagem                   AS voltagem                 ,
				tbl_produto.linha_blackedecker         AS linha                    ,
				tbl_posto.codigo                       AS codigo_posto             ,
				tbl_cidade.estado                      AS estado                   ,
				tbl_peca.referencia                    AS peca_referencia          ,
				tbl_new_os_item.qtde                   AS qtde                     ,
				tbl_new_os_item.preco                  AS preco                    ,
				tbl_new_extrato_financeiro.data_envio::date AS data_financeiro
		INTO TEMP garantias
		FROM tbl_new_os
		JOIN tbl_new_os_item                  ON tbl_new_os.new_os                      = tbl_new_os_item.new_os
		JOIN tbl_peca                         ON tbl_new_os_item.peca                   = tbl_peca.peca
		JOIN tbl_produto                      ON tbl_produto.produto                    = tbl_new_os.produto
		JOIN tbl_posto                        ON tbl_posto.posto                        = tbl_new_os.posto
		JOIN tbl_cidade                       ON tbl_cidade.municipio                   = tbl_posto.municipio
		JOIN tbl_new_os_extra                 ON tbl_new_os_extra.new_os                = tbl_new_os.new_os
		JOIN tbl_new_extrato                  ON tbl_new_extrato.new_extrato            = tbl_new_os_extra.new_extrato
		JOIN tbl_new_extrato_financeiro       ON tbl_new_extrato_financeiro.new_extrato = tbl_new_extrato.new_extrato
		;
		COPY garantias TO '/tmp/blackedecker/garantias_item.txt' ;


		DROP TABLE black_antigo_os;
		CREATE TABLE black_antigo_os (
				produto_referencia_black text    ,
				referencia               text    ,
				voltagem                 text    ,
				linha                    text    ,
				mobra                    float   ,
				pecas                    float   ,
				TOTAL                    float   ,
				codigo_posto             text    ,
				estado                   char(2) ,
				data_financeiro          date
		);
		COPY black_antigo_os FROM '/tmp/blackedecker/garantias_os.txt';


		DROP TABLE black_antigo_item;
		CREATE TABLE black_antigo_item (
				produto_referencia_black text    ,
				referencia               text    ,
				voltagem                 text    ,
				linha                    text    ,
				codigo_posto             text    ,
				estado                   char(2) ,
				peca_referencia          text    ,
				qtde                     int4    ,
				preco                    float   ,
				data_financeiro          date
		);
		COPY black_antigo_item FROM '/tmp/blackedecker/garantias_item.txt';

		grant select on black_antigo_os   to telecontrol ;
		grant select on black_antigo_item to telecontrol ;
		update black_antigo_os set voltagem = '110' where voltagem = '120';

		alter table black_antigo_os add column referencia_pesquisa text ;
		update black_antigo_os set referencia_pesquisa = referencia ;
		update black_antigo_os set referencia_pesquisa = substr (referencia,1,(strpos (referencia,'-')-1)) where strpos (referencia,'-') > 0 ;


		
		
		update black_antigo_item set voltagem = '110' where voltagem = '120';
		alter table black_antigo_item add column referencia_pesquisa text ;
		update black_antigo_item set referencia_pesquisa = referencia ;
		update black_antigo_item set referencia_pesquisa = substr (referencia,1,(strpos (referencia,'-')-1)) where strpos (referencia,'-') > 0 ;
		
		update black_antigo_item set produto = tbl_produto.produto
		from  tbl_produto join tbl_linha using (linha)
		where tbl_linha.fabrica = 1
		and   trim (tbl_produto.referencia_pesquisa) = trim (black_antigo_item.referencia_pesquisa)
		and   substr (trim (tbl_produto.voltagem),1,3) = trim (black_antigo_item.voltagem) ;
		
		update black_antigo_item set produto = tbl_produto.produto
		from  tbl_produto join tbl_linha using (linha)
		where tbl_linha.fabrica = 1
		and   trim (black_antigo_item.referencia_pesquisa) ILIKE '%' || trim (tbl_produto.referencia_pesquisa) || '%'
		and   substr (trim (tbl_produto.voltagem),1,3) = trim (black_antigo_item.voltagem) 
		and   black_antigo_item.produto is null ;
		
		update black_antigo_item set produto = tbl_produto.produto
		from  tbl_produto join tbl_linha using (linha)
		where tbl_linha.fabrica = 1
		and   trim (tbl_produto.referencia_pesquisa) ILIKE '%' || trim (black_antigo_item.referencia_pesquisa) || '%'
		and   substr (trim (tbl_produto.voltagem),1,3) = trim (black_antigo_item.voltagem) 
		and   black_antigo_item.produto is null ;


		update black_antigo_item set produto = tbl_produto.produto
		from  tbl_produto join tbl_linha using (linha)
		where tbl_linha.fabrica = 1
		and   trim (black_antigo_item.referencia_pesquisa) ILIKE '%' || trim (tbl_produto.referencia_pesquisa) || '%'
		and   black_antigo_item.produto is null ;

		update black_antigo_item set produto = tbl_produto.produto
		from  tbl_produto join tbl_linha using (linha)
		where tbl_linha.fabrica = 1
		and   trim (tbl_produto.referencia_pesquisa) ILIKE '%' || trim (black_antigo_item.referencia_pesquisa) || '%'
		and   black_antigo_item.produto is null ;
		

		
		
		";


	
	
	$arquivo = "/var/www/blackedecker/www/download/quebra_produto.csv";
	$fp = fopen ($arquivo,"w");
	
	flush();
	
	$x_data_inicial = trim($_POST["data_inicial"]);
	$x_data_final   = trim($_POST["data_final"]);
	$linha          = trim($_POST["linha"]);
	$estado         = trim($_POST["estado"]);
	
	$cond_linha     = "1=1";
	if (strlen ($linha) > 0) $cond_linha = " tbl_produto.linha = $linha ";

	$cond_estado    = "1=1";
	$cond_estado2   = "1=1";
	if (strlen ($estado) > 0) {
		$cond_estado  = " tbl_posto.estado = '$estado' ";
		$cond_estado2 = " black_antigo_os.estado = '$estado' ";
	}

	$x_data_inicial = "'" . substr ($x_data_inicial,6,4) . "-" . substr ($x_data_inicial,3,2) . "-" . substr ($x_data_inicial,0,2) . " 00:00:00" . "'";
	$x_data_final   = "'" . substr ($x_data_final  ,6,4) . "-" . substr ($x_data_final  ,3,2) . "-" . substr ($x_data_final  ,0,2) . " 23:59:59" . "'";

	$sql = "SELECT  fcr.referencia        AS referencia ,
					(SELECT descricao FROM tbl_produto WHERE referencia_fabrica = fcr.referencia LIMIT 1) AS nome ,
					(SELECT voltagem  FROM tbl_produto WHERE referencia_fabrica = fcr.referencia LIMIT 1) AS voltagem ,
					(SELECT tbl_linha.nome FROM tbl_produto JOIN tbl_linha USING (linha) WHERE referencia_fabrica = fcr.referencia LIMIT 1) AS linha_nome ,
					fcr.ocorrencia                  ,
					fcr.mao_de_obra                 ,
					fcr.pecas
			FROM   (SELECT	tbl_produto.referencia_fabrica  AS referencia ,
							SUM (tbl_os.pecas)              AS pecas_x    ,
							SUM (tbl_os.mao_de_obra)        AS mao_de_obra,
							COUNT(*)                        AS ocorrencia ,
							SUM (xos.pecas)                 AS pecas
					FROM tbl_os
					JOIN tbl_produto              ON tbl_os.produto            = tbl_produto.produto
					JOIN  (
							SELECT tbl_os.os, SUM (tbl_os_item.custo_peca * tbl_os_item.qtde) AS pecas
							FROM tbl_os
							JOIN tbl_os_extra           ON tbl_os.os                 = tbl_os_extra.os
							JOIN tbl_extrato            ON tbl_os_extra.extrato      = tbl_extrato.extrato
							JOIN tbl_extrato_financeiro ON tbl_extrato.extrato       = tbl_extrato_financeiro.extrato
							JOIN tbl_os_produto         ON tbl_os.os                 = tbl_os_produto.os
							JOIN tbl_os_item            ON tbl_os_produto.os_produto = tbl_os_item.os_produto
							JOIN tbl_produto            ON tbl_os.produto            = tbl_produto.produto
							JOIN tbl_posto              ON tbl_os.posto              = tbl_posto.posto
							WHERE tbl_os.fabrica = $login_fabrica
							AND   tbl_extrato_financeiro.data_envio BETWEEN $x_data_inicial AND $x_data_final
							AND   tbl_os_item.custo_peca IS NOT NULL
							AND   tbl_os_item.qtde       IS NOT NULL
							AND   tbl_os_item.servico_realizado = 90
							AND   $cond_linha
							AND   $cond_estado
							AND  (
								(SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND tbl_os_status.extrato = tbl_extrato.extrato ORDER BY data DESC LIMIT 1) NOT IN (13,15)
						        OR 
								(SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND tbl_os_status.extrato = tbl_extrato.extrato ORDER BY data DESC LIMIT 1) IS NULL
							)
							GROUP BY tbl_os.os
					) xos ON tbl_os.os = xos.os
					WHERE  tbl_os.fabrica = $login_fabrica
					GROUP BY tbl_produto.referencia_fabrica
			) fcr 
			ORDER BY referencia ";

			$sql_continuacao = "
			UNION
			SELECT black_antigo_os.produto_referencia_black AS referencia  ,
				   tbl_produto.descricao                    AS nome        ,
				   tbl_produto.voltagem                     AS voltagem    ,
				   COUNT(*)                                 AS ocorrencia  ,
				   SUM (black_antigo_os.mobra)              AS mao_de_obra ,
				   SUM (black_antigo_os.pecas)              AS pecas       ,
					tbl_linha.nome AS linha_nome
			FROM black_antigo_os
			LEFT JOIN tbl_produto ON  black_antigo_os.produto = tbl_produto.produto
			LEFT JOIN tbl_linha   ON  tbl_produto.linha = tbl_linha.linha
			WHERE tbl_linha.fabrica = $login_fabrica
			AND   black_antigo_os.data_financeiro BETWEEN $x_data_inicial AND $x_data_final
			AND   $cond_linha
			AND   $cond_estado2
			GROUP BY black_antigo_os.produto_referencia_black ,
				     tbl_produto.descricao                    ,
				     tbl_produto.voltagem                     ,
				     tbl_produto.linha                        ,
					 tbl_linha.nome
			ORDER BY referencia;

			";

#			) fcr ON tbl_produto.referencia = fcr.referencia AND tbl_produto.linha IN (SELECT linha FROM tbl_linha WHERE fabrica = $login_fabrica)


#					AND    tbl_extrato.data_geracao BETWEEN $x_data_inicial AND $x_data_final

#					JOIN   tbl_extrato_financeiro ON tbl_extrato.extrato = tbl_extrato_financeiro.extrato
#					AND    tbl_extrato.aprovado IS NOT NULL
#					AND    tbl_os.data_digitacao BETWEEN $x_data_inicial AND $x_data_final


#	echo $sql;
#	exit;

/*

update black_antigo_os set produto = tbl_produto.produto from tbl_produto join tbl_linha using (linha) where tbl_linha.fabrica = 1 and black_antigo_os.produto is null and black_antigo_os.referencia_pesquisa ILIKE '%' || TRIM (tbl_produto.referencia) || '%' AND substr (black_antigo_os.voltagem,1,3) = substr (tbl_produto.voltagem,1,3) ;

update black_antigo_os set produto = tbl_produto.produto from tbl_produto join tbl_linha using (linha) where tbl_linha.fabrica = 1 and black_antigo_os.produto is null and tbl_produto.referencia ilike '%' || trim (black_antigo_os.referencia_pesquisa) || '%' AND substr (black_antigo_os.voltagem,1,3) = substr (tbl_produto.voltagem,1,3) ;

update black_antigo_os set produto = tbl_produto.produto from tbl_produto join tbl_linha using (linha) where tbl_linha.fabrica = 1 and black_antigo_os.produto is null and black_antigo_os.referencia_pesquisa ILIKE '%' || TRIM (tbl_produto.referencia_fabrica) || '%' AND substr (black_antigo_os.voltagem,1,3) = substr (tbl_produto.voltagem,1,3) ;

			LEFT JOIN tbl_produto ON  black_antigo_os.referencia_pesquisa LIKE '%' || TRIM (tbl_produto.referencia) || '%'
								  AND SUBSTR (black_antigo_os.voltagem,1,3)    = SUBSTR (tbl_produto.voltagem,1,3)


					AND    tbl_extrato_financeiro.data_envio BETWEEN $x_data_inicial AND $x_data_final


			INTO TEMP TABLE black_antigo

			SELECT referencia  ,
				   produto     ,
				   nome        ,
				   voltagem    ,
				   SUM (ocorrencia)  AS ocorrencia  ,
				   SUM (mao_de_obra) AS mao_de_obra ,
				   SUM (pecas)       AS pecas
			FROM black_antigo
			GROUP BY referencia, produto, nome, voltagem
			ORDER BY referencia ;


*/


#echo $sql;
#exit;

#if ($ip == '201.52.111.170') echo $sql; exit;
#if ($linha == 199) { 
	$sql = "SELECT	tbl_produto.descricao AS nome, 
					tbl_produto.voltagem  AS voltagem, 
					tbl_linha.nome        AS linha_nome, 
					tbl_produto.referencia_fabrica AS referencia , 
					SUM (tbl_os.pecas) AS pecas_x , 
					SUM (tbl_os.mao_de_obra) AS mao_de_obra, 
					COUNT(*) AS ocorrencia , 
					SUM (xos.pecas) AS pecas 
			FROM tbl_os 
			JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
			JOIN tbl_linha   ON tbl_produto.linha = tbl_linha.linha
			JOIN ( SELECT tbl_os.os, SUM (tbl_os_item.custo_peca * tbl_os_item.qtde ) AS pecas 
					FROM tbl_os 
					JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os 
					JOIN tbl_extrato ON tbl_os_extra.extrato = tbl_extrato.extrato 
					JOIN tbl_extrato_financeiro ON tbl_extrato.extrato = tbl_extrato_financeiro.extrato 
					JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
					JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
					JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
					WHERE tbl_os.fabrica = 1 
					AND tbl_extrato_financeiro.data_envio BETWEEN $x_data_inicial AND $x_data_final 
					AND tbl_os.pecas IS NOT NULL 
					AND tbl_os_item.servico_realizado = 90 
					AND tbl_produto.linha = $linha 
					AND ( 
							( 
							SELECT status_os 
							FROM tbl_os_status 
							WHERE tbl_os.os = tbl_os_status.os 
							AND tbl_os_status.extrato = tbl_extrato.extrato 
							ORDER BY data DESC 
							LIMIT 1
							) NOT IN (13,15) 
						OR 
							(
							SELECT status_os 
						    FROM tbl_os_status 
						    WHERE tbl_os.os = tbl_os_status.os A
						    AND tbl_os_status.extrato = tbl_extrato.extrato 
						    ORDER BY data DESC
							LIMIT 1
							) IS NULL
						) 
					GROUP BY tbl_os.os 
				) xos ON tbl_os.os = xos.os 
				WHERE tbl_os.fabrica = 1 
				GROUP BY tbl_produto.descricao, tbl_produto.voltagem, linha_nome, tbl_produto.referencia_fabrica 
				ORDER BY tbl_produto.referencia_fabrica";


	$sql = "SELECT	tbl_produto.descricao AS nome, 
					tbl_produto.voltagem  AS voltagem, 
					tbl_linha.nome        AS linha_nome, 
					tbl_produto.referencia_fabrica AS referencia , 
					SUM (tbl_os.pecas) AS pecas_x , 
					SUM (tbl_os.pecas_pagas) AS pecas , 
					SUM (tbl_os.mao_de_obra) AS mao_de_obra, 
					COUNT(*) AS ocorrencia 
			FROM tbl_os 
			JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
			JOIN tbl_linha   ON tbl_produto.linha = tbl_linha.linha
			JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os
			JOIN tbl_extrato  ON tbl_os_extra.extrato = tbl_extrato.extrato
			JOIN tbl_extrato_financeiro ON tbl_extrato.extrato = tbl_extrato_financeiro.extrato
			WHERE tbl_os.fabrica = 1
			AND   tbl_produto.linha = $linha
			AND   tbl_extrato_financeiro.data_envio BETWEEN $x_data_inicial AND $x_data_final 
			GROUP BY tbl_produto.descricao, tbl_produto.voltagem, linha_nome, tbl_produto.referencia_fabrica 
			ORDER BY tbl_produto.referencia_fabrica";

	$sql = "SELECT	tbl_produto.descricao AS nome, 
					tbl_produto.voltagem  AS voltagem, 
					tbl_linha.nome        AS linha_nome, 
					tbl_produto.referencia_fabrica AS referencia ,
					SUM (xos.pecas)       AS pecas ,
					SUM (xos.mao_de_obra) AS mao_de_obra ,
					SUM (xos.ocorrencia)  AS ocorrencia
			FROM (
				SELECT	tbl_os.produto , 
				tbl_produto.linha ,
				SUM (tbl_os.pecas) AS pecas_x , 
				SUM (tbl_os.pecas_pagas) AS pecas , 
				SUM (tbl_os.mao_de_obra) AS mao_de_obra, 
				COUNT(*) AS ocorrencia 
				FROM tbl_os 
				JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
				JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os
				JOIN tbl_extrato  ON tbl_os_extra.extrato = tbl_extrato.extrato
				JOIN tbl_extrato_financeiro ON tbl_extrato.extrato = tbl_extrato_financeiro.extrato
				WHERE tbl_os.fabrica = 1
				AND   tbl_extrato_financeiro.data_envio BETWEEN $x_data_inicial AND $x_data_final 
				GROUP BY tbl_os.produto, tbl_produto.linha
			) xos
			JOIN tbl_produto ON xos.produto = tbl_produto.produto
			JOIN tbl_linha   ON tbl_produto.linha = tbl_linha.linha
			WHERE tbl_linha.linha = $linha 
			GROUP BY tbl_produto.descricao, tbl_produto.voltagem, tbl_linha.nome, tbl_produto.referencia_fabrica 
			ORDER BY tbl_produto.referencia_fabrica";


#}
//if ($ip == '201.52.111.170') echo $sql; exit;
$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<table width='700' border='0' cellpadding='2' cellspacing='2' align='center'>";
		echo "<tr>";
		
		echo "<td bgcolor='#B6DADA' align='center'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>Produto</b></font>";
		echo "</td>";
		
		echo "<td bgcolor='#B6DADA' align='center'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>Referência</b></font>";
		echo "</td>";
		
		echo "<td bgcolor='#B6DADA' align='center'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>Ocorrência</b></font>";
		echo "</td>";
		
		echo "<td bgcolor='#B6DADA' align='center'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>Total MO</b></font>";
		echo "</td>";
		
		echo "<td bgcolor='#B6DADA' align='center'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>Total PC</b></font>";
		echo "</td>";
		
		echo "<td bgcolor='#B6DADA' align='center'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>Total GERAL</b></font>";
		echo "</td>";
		
		echo "<td bgcolor='#B6DADA' align='center'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>%</b></font>";
		echo "</td>";
		
		echo "</tr>";
		
		for ($x = 0; $x < pg_numrows($res); $x++) {
			$total_ocorrencia = $total_ocorrencia + pg_result($res,$x,ocorrencia);
			$total_mobra      = $total_mobra + pg_result($res,$x,mao_de_obra);
			$total_peca       = $total_peca + pg_result($res,$x,pecas);
			$total_geral      = $total_geral + pg_result($res,$x,mao_de_obra) + pg_result($res,$x,pecas);
		}
		
		$total_final = $total_geral + $total_sedex + $total_avulso;
		
		for ($x = 0; $x < pg_numrows($res); $x++) {
			$referencia = pg_result($res,$x,referencia);
			$voltagem   = pg_result($res,$x,voltagem);
			$ocorrencia = pg_result($res,$x,ocorrencia);
			$soma_mobra = pg_result($res,$x,mao_de_obra);//esta pegando esse valor na tbl_os
			$soma_peca  = pg_result($res,$x,pecas);//esta pegando esse valor na tbl_os_item
			$linha_nome = pg_result($res,$x,linha_nome);
			$soma_total = $soma_mobra + $soma_peca;
			
			if ($soma_total > 0 AND $total_geral > 0) {
				$porcentagem = ($soma_total / $total_geral * 100);
			}
			
			$total_porcentagem	= $total_porcentagem + $porcentagem;
			
			$cor = '#EFF5F5';
			
			if ($x % 2 == 0) $cor = '#B6DADA';
			
			echo "<tr>";
			
			echo "<td bgcolor='$cor' align='left' nowrap>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>";
			echo substr(pg_result($res,$x,nome),0,45);
			echo "</font>";
			echo "</td>";

			$x_data_inicial = str_replace ("'","",$x_data_inicial);
			$x_data_final   = str_replace ("'","",$x_data_final);

			echo "<td bgcolor='$cor' align='left' nowrap>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2' nowrap>";
			echo "<a href='black_quebra_acumulado_pecas.php?referencia=$referencia&voltagem=$voltagem&data_inicial=$x_data_inicial&data_final=$x_data_final&linha=$linha&estado=$estado' target='_blank'>";
			echo $referencia ." - ". $voltagem ;
			echo "</a>";
			echo "</font>";
			echo "</td>";
			
			echo "<td bgcolor='$cor' align='center'>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>";
			echo "<a href='black_quebra_acumulado_os.php?referencia=$referencia&voltagem=$voltagem&data_inicial=$x_data_inicial&data_final=$x_data_final&linha=$linha&estado=$estado' target='_blank'>";
			echo $ocorrencia;
			echo "</a>";
			echo "</font>";
			echo "</td>";
			
			echo "<td bgcolor='$cor' align='right'>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>";
			echo number_format($soma_mobra,2,",",".");
			echo "</td>";
			
			echo "<td bgcolor='$cor' align='right'>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>";
			echo number_format($soma_peca,2,",",".");
			echo "</font>";
			echo "</td>";
			
			echo "<td bgcolor='$cor' align='right'>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>";
			echo number_format($soma_total,2,",",".");
			echo "</font>";
			echo "</td>";
			
			echo "<td bgcolor='$cor' align='center'>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>";
			echo number_format($porcentagem,2,",",".");
			echo "</font>";
			echo "</td>";
			echo "<td bgcolor='$cor' align='center' nowrap >";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>";
			echo $linha_nome;
			echo "</font>";
			echo "</td>";
			echo "</tr>";
			
			
			fwrite ($fp,pg_result($res,$x,nome));
			fwrite ($fp,"\t");
			
			fwrite ($fp,$referencia);
			fwrite ($fp,"\t");
			
			fwrite ($fp,$voltagem);
			fwrite ($fp,"\t");
			
			fwrite ($fp,$ocorrencia);
			fwrite ($fp,"\t");
			
			fwrite ($fp,number_format ($soma_mobra,2,",","."));
			fwrite ($fp,"\t");
			
			fwrite ($fp,number_format ($soma_peca,2,",","."));
			fwrite ($fp,"\t");
			
			fwrite ($fp,number_format ($soma_total,2,",","."));
			fwrite ($fp,"\t");
			
			fwrite ($fp,number_format ($porcentagem,2,",","."));
			fwrite ($fp,"\t");
			
			fwrite ($fp,"\n");
			
		}
		echo "<tr>";
		
		echo "<td bgcolor='#B6DADA' align='left' colspan='2'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>TOTAL</b></font>";
		echo "</td>";
		
		echo "<td bgcolor='#B6DADA' align='center'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>$total_ocorrencia</font>";
		echo "</td>";
		
		echo "<td bgcolor='#B6DADA' align='right'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>". number_format($total_mobra,2,",",".") ."</font>";
		echo "</td>";
		
		echo "<td bgcolor='#B6DADA' align='right'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>". number_format($total_peca,2,",",".") ."</font>";
		echo "</td>";
		
		echo "<td bgcolor='#B6DADA' align='right'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>". number_format($total_geral,2,",",".") ."</font>";
		echo "</td>";
		
		echo "<td bgcolor='#B6DADA' align='center'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>100%</font>";
		echo "</td>";
		
		echo "</tr>";
		echo "</table>";
		
#		echo "<p><center><a href='/download/quebra_produto.csv'>Clique aqui</a> com o botão direito do mouse para salvar o arquivo em seu computador</center></p>";
		
		fclose ($fp);
	#	unlink ($arquivo);
	}
}


echo "<p>";

if (strlen($meu_grafico) > 0) {
	echo $meu_grafico;
}

echo "<p>";

include 'rodape.php';
?>
