<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if(strlen($_POST['mes'])>0) $mes = trim ($_POST['mes']);
else                        $mes = trim ($_GET['mes']);

if(strlen($_POST['ano'])>0) $ano = trim ($_POST['ano']);
else                        $ano = trim ($_GET['ano']);

if(strlen($_POST['codigo_posto'])>0) $codigo_posto = trim ($_POST['codigo_posto']);
else                                 $codigo_posto = trim ($_GET['codigo_posto']);

if(strlen($_POST['aprovado'])>0) $aprovado = trim ($_POST['aprovado']);
else                             $aprovado = trim ($_GET['aprovado']);

if(strlen($_POST['acao'])>0) $acao = trim ($_POST['acao']);
else                         $acao = trim ($_GET['acao']);

if(strlen($_POST['produto_descricao'])>0) $produto_descricao = trim ($_POST['produto_descricao']);
else                                      $produto_descricao = trim ($_GET['produto_descricao']);

if(strlen($_POST['produto_referencia'])>0) $produto_referencia = trim ($_POST['produto_referencia']);
else                                       $produto_referencia = trim ($_GET['produto_referencia']);

if(strlen($_POST['familia'])>0) $familia = trim ($_POST['familia']);
else                            $familia = trim ($_GET['familia']);

if(strlen($_POST['estado'])>0) $estado = trim ($_POST['estado']);
else                           $estado = trim ($_GET['estado']);

if(strlen($_POST['pais'])>0) $pais = trim ($_POST['pais']);
else                         $pais = trim ($_GET['pais']);

if(strlen($_POST['defeito'])>0) $defeito = trim ($_POST['defeito']);
else                            $defeito = trim ($_GET['defeito']);

if($acao=="PESQUISAR"){
	if(strlen($mes)==0) $msg_erro = "Informe o mês para pesquisa";
	if(strlen($ano)==0) $msg_erro = "Informe o ano para pesquisa";

	if(strlen($estado)>0){
		$cond_estado = " tbl_posto.estado = '$estado'";
	}else{
		$cond_estado = " 1 = 1 ";
	}

	if(strlen($pais)>0){
		$cond_pais = " tbl_posto.pais = '$pais'";
	}else{
		$cond_pais = " 1 = 1 ";
	}

	if(strlen($defeito)>0){
		$cond_defeito = " tbl_os_orcamento.servico_realizado = $defeito";
	}else{
		$cond_defeito = " 1 = 1 ";
	}


	if(strlen($familia)>0){
		$cond_familia = " tbl_produto.familia = $familia ";
	}else{
		$cond_familia = " 1 = 1 ";
	}

	if(strlen($produto_referencia)>0){
		$sqlP = "SELECT tbl_produto.produto
				 FROM tbl_produto
				 JOIN tbl_linha USING(linha)
				 WHERE tbl_produto.referencia = '$produto_referencia'
				 AND   tbl_linha.fabrica      = $login_fabrica";
		$resP = pg_exec($con,$sqlP);

		if(pg_numrows($resP)>0){
			$produto = pg_result($resP,0,produto);

			$cond_produto = " tbl_os_orcamento.produto = $produto ";
		}
	}else{
		$cond_produto = " 1 = 1 ";
	}

	if (strlen($mes) > 0) {
		$data_inicial = date("Y-m-01", mktime(0, 0, 0, $mes, 1, $ano));
		$data_final   = date("Y-m-t", mktime(0, 0, 0, $mes, 1, $ano));
	}

	if($aprovado=="t"){
		$cond_aprovado = " tbl_os_orcamento.orcamento_aprovado IS TRUE ";
	}else if($aprovado=="f"){
		$cond_aprovado = " tbl_os_orcamento.orcamento_aprovado IS FALSE ";
	}else{
		$cond_aprovado = " 1 = 1 ";
	}

	if(strlen($codigo_posto)>0){
		$sqlP = "SELECT tbl_posto_fabrica.posto
				 FROM tbl_posto_fabrica
				 WHERE tbl_posto_fabrica.codigo_posto = '$codigo_posto'
				 AND   tbl_posto_fabrica.fabrica      = $login_fabrica";
		$resP = pg_exec($con,$sqlP);

		if(pg_numrows($resP)>0){
			$posto = pg_result($resP,0,posto);

			$cond_posto = " tbl_os_orcamento.posto = $posto ";
		}
	}else{
		$cond_posto = " 1 = 1 ";
	}
}

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

function m2h($mins) {
	// Se os minutos estiverem negativos 
	// função abs retorna o valor absoluto do campo
	if ($mins < 0)
	$min = abs($mins);
	else
	$min = $mins;

	// Arredonda a hora - função floor
	$h = floor($min / 60);
	$m = ($min - ($h * 60)) / 100;
	$horas = $h + $m;

	if ($mins < 0)
	$horas *= -1;

	// Separa a hora dos minutos
	$sep = explode('.', $horas);
	$h = $sep[0];
	if (empty($sep[1]))
	$sep[1] = 00;
	$m = $sep[1];

	// Aqui coloca um zero no final
	if (strlen($m) < 2)
	$m = $m . 0;

	return sprintf('%02d:%02d', $h, $m);
}

$title = "RELATÓRIO OS FORA DA GARANTIA";
include "cabecalho.php";

?>

<? include "javascript_pesquisas.php"; ?>

<? include "javascript_calendario.php"; ?>

<SCRIPT LANGUAGE="JavaScript">
function fnc_pesquisa_produto2 (campo, campo2, tipo, voltagem) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.referencia   = campo;
		janela.descricao    = campo2;
		if (voltagem != "") {
			janela.voltagem = voltagem;
		}
		janela.focus();
	}
	else
		alert('Preencha toda ou parte da informação para realizar a pesquisa');
}
</SCRIPT>

<script>
	$(function(){
		$('#data_inicial_01').datePicker({startDate:'01/01/2000'});
		$('#data_final_01').datePicker({startDate:'01/01/2000'});
		$("#data_inicial_01").maskedinput("99/99/9999");
		$("#data_final_01").maskedinput("99/99/9999");
	});
</script>

<style>
	.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}

	.Titulo{
		text-align: center;
		font-family: Arial;
		font-size: 10px;
		font-weight: bold;
		color: #FFFFFF;
		background-color: #485989;
	}

	.titulo_coluna td{
	background-color:#596d9b;
	font: bold 11px "Arial" !important;
	color:#FFFFFF;
	text-align:center;
	}
	
	P{
		text-align: center;
		font-family: Arial;
		font-size: 12px;
		font-weight: bold;
		color: #000000;
	}

	.Conteudo{
		font-family: Arial;
		font-size: 11px;
		font-weight: normal;
	}
	
	.ConteudoBranco{
		font-family: Arial;
		font-size: 11px;
		color:#FFFFFF;
		font-weight: normal;
	}
	
	.Mes{
		font-size: 8px;
	}

	td{
		font-family: Arial;
		font-size: 11px;
		font-weight: normal;
	}
	table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
	}

	.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}
</style>


<?
	if(strlen($msg_erro)>0){
		echo "<div class='msg_erro' style='width:700px;margin:auto;'>";
			echo $msg_erro;
		echo "</div>";
	}
?>

	<form name="frm_pesquisa" method="POST" action="<?echo $PHP_SELF?>">
	<input type="hidden" name="acao">
	<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2">
		<tr>
			<td colspan="4" class="titulo_tabela">Parâmetros de Pesquisa</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF">
			<td width='10%'></td>
			<td width='40%' align='left'>Mês</td>
			<td width='40%' align='left'>Ano</td>
			<td width='10%'></td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF">
			<td width='10%'></td>
			<td width='40%' align='left'>
				<select name="mes" size="1" class="frm">
				<option value=''></option>
				<?
				for ($i = 1 ; $i <= count($meses) ; $i++) {
					echo "<option value='$i'";
					if ($mes == $i) echo " selected";
					echo ">" . $meses[$i] . "</option>";
				}
				?>
				</select>
			</td>
			<td width='40%' align='left'>
				<select name="ano" size="1" class="frm">
				<option value=''></option>
				<?
				for($i = date("Y"); $i > 2003; $i--){
					echo "<option value='$i'";
					if ($ano == $i) echo " selected";
					echo ">$i</option>";
				}
				?>
				</select>
			</td>
			<td width='10%'></td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF">
			<td width='10%'></td>
			<td width='40%' align='left' >Código Posto</td>
			<td width='40%' align='left' >Nome Posto</td>
			<td width='10%'></td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF">
			<td width='10%'></td>
			<td width='40%' align='left'>
				<input type='text' name='codigo_posto' id='codigo_posto' size='15' value='<? echo $codigo_posto ?>' class='frm'>
				<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'codigo')">
			</td>
			<td width='40%' align='left'>
				<input type='text' name='posto_nome' id='posto_nome' size='25' value='<? echo $posto_nome ?>' class='frm'>
				<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'nome')">
			</td>
			<td width='10%'></td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF">
			<td width='10%'></td>
			<td width='40%' align='left' >
				Referência Produto
			</td>
			<td width='40%' align='left' >
				Descrição Produto
			</td>
			<td width='10%'></td>
		</tr>
		<tr bgcolor="#D9E2EF">
			<td width='10%'></td>
			<td nowrap align='left'>
			<input class="frm" type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto2 (document.frm_pesquisa.produto_referencia,document.frm_pesquisa.produto_descricao,'referencia')">
			</td>
			<td nowrap align='left'>
			<input class="frm" type="text" name="produto_descricao" size="30" value="<? echo $produto_descricao ?>">&nbsp;<img
			src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript:
			fnc_pesquisa_produto2 (document.frm_pesquisa.produto_referencia,document.frm_pesquisa.produto_descricao,'descricao')"></A>
			</td>
			<td width='10%'></td>
		</tr>
		<tr bgcolor="#D9E2EF">
			<td width='10%'></td>
			<td width='40%' align='left'>Familia</td>
			<td colspan='2' align='left'>Estado</td>
		</tr>
		<tr bgcolor="#D9E2EF">
			<td width='10%'></td>
			<td align='left'>
				<?
				$sqlf = "SELECT  *
						FROM    tbl_familia
						WHERE   tbl_familia.fabrica = $login_fabrica
						ORDER BY tbl_familia.descricao;";
				$resf = pg_exec ($con,$sqlf);

				if (pg_numrows($resf) > 0) {
					echo "<select class='frm' style='width:auto;' name='familia'>\n";
					echo "<option value=''>ESCOLHA</option>\n";

					for ($x = 0 ; $x < pg_numrows($resf) ; $x++){
						$aux_familia = trim(pg_result($resf,$x,familia));
						$aux_descricao  = trim(pg_result($resf,$x,descricao));

						echo "<option value='$aux_familia'"; if ($familia == $aux_familia) echo " SELECTED "; echo ">$aux_descricao</option>\n";
					}
					echo "</select>\n";
				}
				?>
			</td>
			<td colspan='2' align='left'>
				<select class='frm' name="estado" size="1">
					<option value=""   <? if (strlen($estado) == 0)    echo " selected "; ?>>TODOS OS ESTADOS</option>
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
		<tr bgcolor="#D9E2EF">
			<td width='10%'></td>
			<td width='40%' align='left'>Defeito</td>
			<td colspan='2'></td>
		</tr>
		<tr bgcolor="#D9E2EF">
			<td width='10%'></td>
			<td colspan='3' align='left'>
				<select name="defeito" class='frm'>
					<option></option>
					<?
					$sql = "SELECT * FROM tbl_servico_realizado
							WHERE tbl_servico_realizado.fabrica = $login_fabrica ";
					$res = pg_query ($con,$sql) ;

					for ($x = 0 ; $x < pg_num_rows($res) ; $x++ ) {
						$descricao_d= pg_fetch_result ($res,$x,descricao);
						$s_r_id		= pg_fetch_result($res,$x,servico_realizado);
						$s_r_sel	= ($servico_realizado == $s_r_id)?"SELECTED":"";
						//--=== Tradução para outras linguas ===== Raphael HD:1212
						$sql_idioma = "SELECT * FROM tbl_servico_realizado_idioma WHERE servico_realizado = ".pg_fetch_result ($res,$x,servico_realizado)." AND upper(idioma) = '$sistema_lingua'";
						$res_idioma = @pg_query($con,$sql_idioma);
						if (@pg_num_rows($res_idioma) >0) {
							$descricao_d  = trim(@pg_fetch_result($res_idioma,0,descricao));
						}
						//--=== Tradução para outras linguas ======================
						echo "<option $s_r_sel value='$s_r_id'>$descricao_d</option>\n";
					}
					?>
				</select>
			</td>
		</tr>
		<tr bgcolor="#D9E2EF">
			<td width='10%'></td>
			<td width='40%' align='left'>Status Orçamento</td>
			<td colspan='2' align='left'>País</td>
		</tr>
		<tr bgcolor="#D9E2EF">
			<td width='10%'></td>
			<td width='40%' align='left'>
				<SELECT NAME="aprovado" class='frm'>
					<OPTION></OPTION>
					<OPTION value="t" <? if($aprovado=="t") echo "SELECTED"; ?>>Aprovado</OPTION>
					<OPTION value="f" <? if($aprovado=="f") echo "SELECTED"; ?>>Recusado</OPTION>
				</SELECT>
			</td>
			<td colspan="2" align='left'>
				<SELECT NAME="pais" class='frm'>
					<OPTION VALUE=""></OPTION>
					<?
						$sqlP = "SELECT pais,nome FROM tbl_pais";
						$resP = pg_exec($con, $sqlP);

						if(pg_numrows($resP)>0){
							for($x=0; $x<pg_numrows($resP); $x++){
								$xpais = pg_result($resP,$x,pais);
								$xnome = pg_result($resP,$x,nome);
								
								echo "<OPTION VALUE='$xpais'";
								if($pais==$xpais) echo "selected";
								echo ">$xnome</OPTION>";
							}
						}
					?>
				</SELECT>
			</td>
		</tr>
		<tr bgcolor="#D9E2EF">
			<td colspan="4" align="center">
				<BR>
				<input type="button" onclick="document.frm_pesquisa.acao.value='PESQUISAR'; document.frm_pesquisa.submit();" 
				style="background:url(imagens/btn_pesquisar_400.gif); width:400px; height:20px;cursor:pointer; margin-bottom:5px;" />
			</td>
		</tr>
	</table>
	</form>

<?

if($acao=="PESQUISAR" AND strlen($msg_erro)==0){
	if($login_fabrica == 20){
		$cond_conserto_horas = " 1 = 1 ";
	}else{
		$cond_conserto_horas = " tbl_os_orcamento.conserto IS NOT NULL AND   tbl_os_orcamento.horas_aguardando_retirada IS NOT NULL ";
	}	
	
	$sql = "SELECT  tbl_os_orcamento.os_orcamento                                                                ,
					tbl_os_orcamento.posto                                                                       ,
					tbl_posto.nome                                                                               ,
					tbl_posto.pais                                                                               ,
					tbl_os_orcamento.produto                                                                     ,
					tbl_os_orcamento.servico_realizado                                                           ,
					to_char(tbl_os_orcamento.abertura, 'dd/mm/yyyy HH24:MI')            AS abertura              ,
					to_char(tbl_os_orcamento.orcamento_envio, 'dd/mm/yyyy HH24:MI')     AS orcamento_envio       ,
					to_char(tbl_os_orcamento.orcamento_aprovacao, 'dd/mm/yyyy HH24:MI') AS orcamento_aprovacao   ,
					tbl_os_orcamento.orcamento_aprovado                                                          ,
					to_char(tbl_os_orcamento.conserto, 'dd/mm/yyyy HH24:MI')            AS conserto              ,
					to_char(tbl_os_orcamento.fechamento, 'dd/mm/yyyy HH24:MI')          AS fechamento            ,
					tbl_os_orcamento.consumidor_nome                                                             ,
					tbl_os_orcamento.consumidor_fone                                                             ,
					tbl_os_orcamento.consumidor_email                                                            ,
					tbl_os_orcamento.horas_aguardando_orcamento                                                  ,
					tbl_os_orcamento.horas_aguardando_aprovacao                                                  ,
					tbl_os_orcamento.horas_aguardando_conserto                                                   ,
					tbl_os_orcamento.horas_aguardando_retirada
			FROM  tbl_os_orcamento
			JOIN  tbl_produto ON tbl_produto.produto = tbl_os_orcamento.produto
			JOIN  tbl_posto   ON tbl_posto.posto     = tbl_os_orcamento.posto
			WHERE tbl_os_orcamento.abertura BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
			AND   $cond_conserto_horas
			AND   $cond_posto
			AND   $cond_aprovado
			AND   $cond_produto
			AND   $cond_familia
			AND   $cond_estado
			AND   $cond_pais
			AND   $cond_defeito
			AND tbl_os_orcamento.fabrica = $login_fabrica
			ORDER BY tbl_posto.nome ASC";
	//echo nl2br($sql);

	$resxls = pg_exec($con,$sql);

	##### PAGINAÇÃO - INÍCIO #####
	$sqlCount  = "SELECT count(*) FROM (";
	$sqlCount .= $sql;
	$sqlCount .= ") AS count";

	require "_class_paginacao.php";

	// definicoes de variaveis
	$max_links = 11;				// máximo de links à serem exibidos
	$max_res   = 50;				// máximo de resultados à serem exibidos por tela ou pagina
	$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
	$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

	$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");
	##### PAGINAÇÃO - FIM #####
if(pg_numrows($res)>0) {
	################# GERA XLS ################################
	flush();
	$data = date ("d/m/Y H:i:s");

	$arquivo_nome     = "consulta-os-fora-garantia-$login_fabrica.xls";
	$path             = "/www/assist/www/admin/xls/";
	$path_tmp         = "/tmp/";

	$arquivo_completo     = $path.$arquivo_nome;
	$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

	echo `rm $arquivo_completo_tmp `;
	echo `rm $arquivo_completo `;

	$fp = fopen ($arquivo_completo_tmp,"w");
	fputs ($fp,"<table border='1' cellpadding='4' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'  align='center' width='96%'>");
	fputs ($fp,"<tr>");
	fputs ($fp,"<td><b>Posto</b></td>");
	fputs ($fp,"<td><b>País</b></td>");
	fputs ($fp,"<td><b>Nome Consumidor</b></td>");
	fputs ($fp,"<td><b>Fone Consumidor</b></td>");
	fputs ($fp,"<td><b>Email Consumidor</b></td>");
	fputs ($fp,"<td><b>Produto</b></td>");
	fputs ($fp,"<td><b>Defeito</b></td>");
	fputs ($fp,"<td><b>Abertura</b></td>");
	fputs ($fp,"<td><b>Envio do Orçamento</b></td>");
	fputs ($fp,"<td><b>Aprovação</b></td>");
	fputs ($fp,"<td><b>Orçamento Aprovado</b></th>");
	fputs ($fp,"<td><b>Conserto</b></th>");
	fputs ($fp,"<td><b>Fechamento</b></th>");
	fputs ($fp,"<td><b>Horas Aguardando Orçamento</b></td>");
	fputs ($fp,"<td><b>Horas Aguardando Aprovação</b></td>");
	fputs ($fp,"<td><b>Horas Aguardando Conserto</b></td>");
	fputs ($fp,"<td><b>Horas Aguardando Retirada</b></td>");
	fputs ($fp,"<td><b>Horas Orçamento + Conserto</b></td>");
	fputs ($fp,"</TR>");

	for($x =0;$x<pg_num_rows($resxls);$x++) {
		$posto               = pg_result($resxls,$x,posto);
		$pais                = pg_result($resxls,$x,pais);
		$produto             = pg_result($resxls,$x,produto);
		$servico_realizado   = pg_result($resxls,$x,servico_realizado);
		$abertura            = pg_result($resxls,$x,abertura);
		$orcamento_envio     = pg_result($resxls,$x,orcamento_envio);
		$orcamento_aprovacao = pg_result($resxls,$x,orcamento_aprovacao);
		$orcamento_aprovado  = pg_result($resxls,$x,orcamento_aprovado);
		$conserto            = pg_result($resxls,$x,conserto);
		$fechamento          = pg_result($resxls,$x,fechamento);
		$consumidor_nome     = pg_result($resxls,$x,consumidor_nome);
		$consumidor_fone     = pg_result($resxls,$x,consumidor_fone);
		$consumidor_email    = pg_result($resxls,$x,consumidor_email);
		$horas_aguardando_orcamento = pg_result($resxls,$x,horas_aguardando_orcamento);
		$horas_aguardando_aprovacao = pg_result($resxls,$x,horas_aguardando_aprovacao);
		$horas_aguardando_conserto  = pg_result($resxls,$x,horas_aguardando_conserto);
		$horas_aguardando_retirada  = pg_result($resxls,$x,horas_aguardando_retirada);

		$horas_orcamento_conserto   = $horas_aguardando_orcamento + $horas_aguardando_conserto;

		$horas_aguardando_orcamento = m2h($horas_aguardando_orcamento);
		$horas_aguardando_aprovacao = m2h($horas_aguardando_aprovacao);
		$horas_aguardando_conserto  = m2h($horas_aguardando_conserto);
		$horas_aguardando_retirada  = m2h($horas_aguardando_retirada);
		$horas_orcamento_conserto   = m2h($horas_orcamento_conserto);

		if(strlen($posto)>0){
			$sqlN = "SELECT tbl_posto_fabrica.codigo_posto,
							tbl_posto.nome
					 FROM tbl_posto
					 JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					 WHERE tbl_posto.posto = $posto";
			$resN = pg_exec($con,$sqlN);

			if(pg_numrows($resN)>0){
				$codigo_posto = pg_result($resN,0,codigo_posto);
				$posto_nome   = pg_result($resN,0,nome);
			}
		}

		if(strlen($produto)>0){
			$sqlP = "SELECT referencia, descricao FROM tbl_produto WHERE produto = $produto";
			$resP = pg_exec($con,$sqlP);

			if(pg_numrows($resP)>0){
				$referencia_produto = pg_result($resP,0,referencia);
				$descricao_produto  = pg_result($resP,0,descricao);
			}
		}

		$descricao_defeito = " ";
		if(strlen($servico_realizado)>0){
			$sqlDC = "SELECT descricao FROM tbl_servico_realizado WHERE servico_realizado = $servico_realizado";
			$resDC = pg_exec($con,$sqlDC);

			if(pg_numrows($resDC)>0){
				$descricao_defeito = pg_result($resDC,0,descricao);
			}
		}

		if($orcamento_aprovado=="t") $orcamento_aprovado = "Aprovado";
		else                         $orcamento_aprovado = "Recusado";

		fputs ($fp,"<tr class='Conteudo' align='left'>");
		fputs ($fp,"<td nowrap>" . $codigo_posto ." - ". $posto_nome . "</td>");
		fputs ($fp,"<td nowrap>" . $pais . "</td>");
		fputs ($fp,"<td nowrap>" . $consumidor_nome . "</td>");
		fputs ($fp,"<td nowrap>" . $consumidor_fone . "</td>");
		fputs ($fp,"<td nowrap>" . $consumidor_email . "</td>");
		fputs ($fp,"<td nowrap>" . $referencia_produto ." - ". $descricao_produto . "</td>");
		fputs ($fp,"<td nowrap>" . $descricao_defeito . "</td>");
		fputs ($fp,"<td nowrap>" . $abertura . "</td>");
		fputs ($fp,"<td nowrap>" . $orcamento_envio . "</td>");
		fputs ($fp,"<td nowrap>" . $orcamento_aprovacao . "</td>");
		fputs ($fp,"<td nowrap>" . $orcamento_aprovado . "</td>");
		fputs ($fp,"<td nowrap>" . $conserto . "</td>");
		fputs ($fp,"<td nowrap>" . $fechamento . "</td>");
		fputs ($fp,"<td nowrap>" . $horas_aguardando_orcamento . "</td>");
		fputs ($fp,"<td nowrap>" . $horas_aguardando_aprovacao . "</td>");
		fputs ($fp,"<td nowrap>" . $horas_aguardando_conserto . "</td>");
		fputs ($fp,"<td nowrap>" . $horas_aguardando_retirada . "</td>");
		fputs ($fp,"<td nowrap>" . $horas_orcamento_conserto . "</td>");
		fputs($fp,"</tr>");
	}
	fputs ($fp, " </TABLE>");


	echo ` cp $arquivo_completo_tmp $path `;
	$data = date("Y-m-d").".".date("H-i-s");

	echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f $arquivo_completo $arquivo_completo_tmp `;
	$resposta .= "<br>";
	$resposta .="<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
	$resposta .="<tr>";
	$resposta .= "<td align='center'><button onclick=\"window.location='xls/$arquivo_nome'\"> Download em Excel</button></td>";
	$resposta .= "</tr>";
	$resposta .= "</table>";
	echo $resposta;

	################# GERA XLS FIM ############################

	echo "<br>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='1' align='center' class='tabela'>";
		echo "<tr class='titulo_coluna'>";
			echo "<td nowrap>Posto</td>";
			echo "<td nowrap>País</td>";
			echo "<td nowrap>Nome Consumidor</td>";
			echo "<td nowrap>Fone Consumidor</td>";
			echo "<td nowrap>Email Consumidor</td>";
			echo "<td nowrap>Descrição Produto</td>";
			echo "<td nowrap>Defeito</td>";
			echo "<td nowrap>Abertura</td>";
			echo "<td nowrap>Envio do Orçamento</td>";
			echo "<td nowrap>Aprovação</td>";
			echo "<td nowrap>Orçamento Aprovado</td>";
			echo "<td nowrap>Conserto</td>";
			echo "<td nowrap>Fechamento</td>";
			echo "<td nowrap>Horas Aguardando Orçamento</td>";
			echo "<td nowrap>Horas Aguardando Aprovação</td>";
			echo "<td nowrap>Horas Aguardando Conserto</td>";
			echo "<td nowrap>Horas Aguardando Retirada</td>";
			echo "<td nowrap>Horas Orçamento + Conserto</td>";
		echo "</tr>";

		for($i=0; $i<pg_numrows($res); $i++){
			$os_orcamento        = pg_result($res,$i,os_orcamento);
			$posto               = pg_result($res,$i,posto);
			$pais                = pg_result($res,$i,pais);
			$produto             = pg_result($res,$i,produto);
			$servico_realizado   = pg_result($res,$i,servico_realizado);
			$abertura            = pg_result($res,$i,abertura);
			$orcamento_envio     = pg_result($res,$i,orcamento_envio);
			$orcamento_aprovacao = pg_result($res,$i,orcamento_aprovacao);
			$orcamento_aprovado  = pg_result($res,$i,orcamento_aprovado);
			$conserto            = pg_result($res,$i,conserto);
			$fechamento          = pg_result($res,$i,fechamento);
			$consumidor_nome     = pg_result($res,$i,consumidor_nome);
			$consumidor_fone     = pg_result($res,$i,consumidor_fone);
			$consumidor_email    = pg_result($res,$i,consumidor_email);
			$horas_aguardando_orcamento = pg_result($res,$i,horas_aguardando_orcamento);
			$horas_aguardando_aprovacao = pg_result($res,$i,horas_aguardando_aprovacao);
			$horas_aguardando_conserto  = pg_result($res,$i,horas_aguardando_conserto);
			$horas_aguardando_retirada  = pg_result($res,$i,horas_aguardando_retirada);

			$horas_orcamento_conserto   = $horas_aguardando_orcamento + $horas_aguardando_conserto;

			$horas_aguardando_orcamento = m2h($horas_aguardando_orcamento);
			$horas_aguardando_aprovacao = m2h($horas_aguardando_aprovacao);
			$horas_aguardando_conserto  = m2h($horas_aguardando_conserto);
			$horas_aguardando_retirada  = m2h($horas_aguardando_retirada);
			$horas_orcamento_conserto   = m2h($horas_orcamento_conserto);

			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

			if(strlen($posto)>0){
				$sqlN = "SELECT tbl_posto_fabrica.codigo_posto,
								tbl_posto.nome
						 FROM tbl_posto
						 JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
						 WHERE tbl_posto.posto = $posto";
				$resN = pg_exec($con,$sqlN);

				if(pg_numrows($resN)>0){
					$codigo_posto = pg_result($resN,0,codigo_posto);
					$posto_nome   = pg_result($resN,0,nome);
				}
			}

			if(strlen($produto)>0){
				$sqlP = "SELECT referencia, descricao FROM tbl_produto WHERE produto = $produto";
				$resP = pg_exec($con,$sqlP);

				if(pg_numrows($resP)>0){
					$referencia_produto = pg_result($resP,0,referencia);
					$descricao_produto  = pg_result($resP,0,descricao);
				}
			}

			$descricao_defeito = " ";
			if(strlen($servico_realizado)>0){
				$sqlDC = "SELECT descricao FROM tbl_servico_realizado WHERE servico_realizado = $servico_realizado";
				$resDC = pg_exec($con,$sqlDC);

				if(pg_numrows($resDC)>0){
					$descricao_defeito = pg_result($resDC,0,descricao);
				}
			}

			if($orcamento_aprovado=="t") $orcamento_aprovado = "Aprovado";
			else                         $orcamento_aprovado = "Recusado";

			echo "<tr bgcolor='$cor'>";
				echo "<td nowrap align='left'>$codigo_posto - $posto_nome</td>";
				echo "<td nowrap align='left'>$pais</td>";
				echo "<td nowrap align='left'>$consumidor_nome</td>";
				echo "<td nowrap>$consumidor_fone</td>";
				echo "<td nowrap>$consumidor_email</td>";
				echo "<td nowrap align='left'>$referencia_produto - $descricao_produto</td>";
				echo "<td nowrap align='left'>$descricao_defeito</td>";
				echo "<td nowrap>$abertura</td>";
				echo "<td nowrap>$orcamento_envio</td>";
				echo "<td nowrap>$orcamento_aprovacao</td>";
				echo "<td nowrap align='center'>$orcamento_aprovado</td>";
				echo "<td nowrap>$conserto</td>";
				echo "<td nowrap>$fechamento</td>";
				echo "<td nowrap>$horas_aguardando_orcamento</td>";
				echo "<td nowrap>$horas_aguardando_aprovacao</td>";
				echo "<td nowrap>$horas_aguardando_conserto</td>";
				echo "<td nowrap>$horas_aguardando_retirada</td>";
				echo "<td nowrap>$horas_orcamento_conserto</td>";
			echo "</tr>";
		}
		echo "</table>";
		echo "<br>";

	##### PAGINAÇÃO - INÍCIO #####
	echo "<br>";
	echo "<div>";
	if($pagina < $max_links) $paginacao = pagina + 1;
	else                     $paginacao = pagina;

	// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
	@$todos_links		= $mult_pag->Construir_Links("strings", "sim");

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
			echo "<font color='#cccccc' size='1'>";
				echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
			echo "</font>";
		echo "</div>";
	}
	##### PAGINAÇÃO - FIM #####
	}else{
		echo "<P>Não foram Encontrados Resultados para esta Pesquisa!</P>";
	}
}

?>


<? include "rodape.php" ?>