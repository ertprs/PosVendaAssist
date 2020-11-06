<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

include 'autentica_admin.php';

include 'funcoes.php';

$layout_menu = "gerencia";
$title = "RELATÓRIO POR POSTO";

if ($_POST["formato"] == "xls") {
	ob_start();
}
else {
	include "cabecalho.php";
}
$aux_fabrica=$login_fabrica;

$btn_acao = $_POST['btn_acao'];
if(strlen($btn_acao)>0){ 
	$data_inicial     = $_POST['data_inicial'];
	$data_final       = $_POST['data_final']; 
	$fabrica          = $_POST['fabrica']; 
	$linha            = $_POST['linha']; 
	$estado           = $_POST['estado']; 
	$cidade           = $_POST['cidade'];
	$bairro           = $_POST['bairro'];
	$codigo_posto     = $_POST['codigo_posto'];
	$posto_nome       = $_POST['posto_nome'];
	
		if((strlen($data_inicial)>0) and(strlen($data_final)>0)){
		$dat = explode ("/", $data_inicial );//tira a barra
			$d = $dat[0];
			$m = $dat[1];
			$y = $dat[2];
			if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
			
			if(strlen($msg_erro)==0){
				$dat = explode ("/", $data_final );//tira a barra
					$d = $dat[0];
					$m = $dat[1];
					$y = $dat[2];
					if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
			}
			if(strlen($msg_erro)==0){
				$data_inicial_sql = implode("-", array_reverse(explode("/", $data_inicial)));
				$data_final_sql = implode("-", array_reverse(explode("/", $data_final)));
				if($data_inicial_sql > $data_final_sql)
					$msg_erro = "Data Inválida";
				else{
					$sql = "SELECT '$data_inicial_sql'::date + INTERVAL '1 YEAR' - '$data_final_sql'::date";
					$res = pg_exec($con, $sql);
					if(pg_result($res,0,0) < 0)
						$msg_erro = "Período informado maior que 1 ano";
					else
						$data_inicial_sql = "AND tbl_os.finalizada BETWEEN '$data_inicial_sql'::date AND '$data_final_sql'::date + INTERVAL '1 day'";
				}
			}
	}
	else
	{
		$msg_erro = "Data Inválida";
	}



 if(strlen($msg_erro)==0){
	if (strlen(trim($linha)) > 0) {
		$linha_ilike = "tbl_linha.nome ILIKE '" . implode("' OR tbl_linha.nome ILIKE '", $linha) . "'";

		$sql = "SELECT linha, nome FROM tbl_linha WHERE $linha_ilike";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) == 0) {
			$msg_erro = "Selecione uma linha válida";
		}
		else {
			$linhas_pesquisa = array();
			for($i = 0; $i < pg_num_rows($res); $i++) {
				$linhas_pesquisa[] = pg_result($res, $i, linha);
				$linhas_pesquisa_nome .= "<br>" . pg_result($res, $i, nome);
			}
			$linhas_pesquisa = implode(", ", $linhas_pesquisa);
		}
	}
	else {
		$msg_erro = "Selecione a Linha";
	}
 }

}
if ($_POST["formato"] == "xls") {
}
else {

?>
<style>
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
	color:#ffffff;
	background-color: #445AA8;
	background: url('imagens_admin/azul.gif');
}

.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 10px;
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
	font-size: 9px;
}
.Caixa{
	BORDER-RIGHT: #6699CC 1px solid;
	BORDER-TOP: #6699CC 1px solid;
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid;
	BORDER-BOTTOM: #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
}
.Exibe{
	font-family: Arial, Helvetica, sans-serif;
	font-size: 8 px;
	font-weight: none;
	color: #000000;
	text-align: center;
}
.Erro{
	BORDER-RIGHT: #990000 1px solid;
	BORDER-TOP: #990000 1px solid;
	FONT: 14pt Arial ;
	COLOR: #ffffff;
	BORDER-LEFT: #990000 1px solid;
	BORDER-BOTTOM: #990000 1px solid;
	BACKGROUND-COLOR: #FF0000;
}
.Carregando{
	TEXT-ALIGN: center;
	BORDER-RIGHT: #aaa 1px solid;
	BORDER-TOP: #aaa 1px solid;
	FONT: 10pt Arial ;
	COLOR: #000000;
	BORDER-LEFT: #aaa 1px solid;
	BORDER-BOTTOM: #aaa 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
	margin-left:20px;
	margin-right:20px;
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
<? include "javascript_calendario_new.php"; 
include_once '../js/js_css.php';
?>

<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>
<script language='javascript' src='ajax.js'></script>

<script type="text/javascript" charset="utf-8">

$(function(){
		$('#data_inicial').datepick({startDate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$("#data_inicial").mask("99/99/9999");
		$("#data_final").mask("99/99/9999");
	});

function buscacidadeposto(dados,tipo_busca) {
$.ajax({
	type: "GET",
	url: "ajax_busca_cidade_posto.php",
	data: "dados=" + dados+"&tipo_busca="+tipo_busca,
	cache: false,
	beforeSend: function() {
		// enquanto a função esta sendo processada, você
		// pode exibir na tela uma
		// msg de carregando
	},
	success: function(txt) {
		// pego o id da div que envolve o select com
		// name="id_modelo" e a substituiu
		// com o texto enviado pelo php, que é um novo
		//select com dados da marca x
		if (tipo_busca == 'estado')
		   $('#cidade').html(txt);
		if (tipo_busca == 'cidade')
		   $('#bairro').html(txt);
	},
	error: function(txt) {
		alert(txt);
	}
});
}
</script>


<FORM name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

<table width='700' class='formulario' border='0' cellpadding='5' cellspacing='1' align='center'>
	<?php if(strlen($msg_erro)>0){ ?>
			<tr class="msg_erro"><td><?php echo $msg_erro; ?></td></tr>
	<?php } ?>
	<tr class="titulo_tabela">
		<td>Parâmetros de Pesquisa</td>
	</tr>
	<tr>
		<td valign='bottom'>
			<table border='0' cellspacing='1' cellpadding='2' width="100%" class="formulario">
				<tr>
					<td width="180">&nbsp;</td>
					<td colspan='6'>
						<table width="100%">
							<tr>
								<td align='left' nowrap width='150'>Data Inicial</td>
								<td align='left' nowrap >Data Final</td>
							</tr>
								<td align='left' nowrap width='150'>
									<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='frm' value="<? if (strlen($data_inicial) > 0) echo $data_inicial;  ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
								</td>
								
								<td align='left' nowrap >
									<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='frm' value="<? if (strlen($data_final) > 0) echo $data_final; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
								</td>
							<tr>

							</tr>
						</table>
					</td>
					
				</tr>
				

				<tr>
					<td width="100">&nbsp;</td>
					<td align='left' nowrap colspan="6">Linha</td>
				</tr>
				<tr>
					<td width="100">&nbsp;</td>
					<td align='left' nowrap colspan="6">
						<select name="linha[]" id="linha" class='frm' multiple>
						<?
						$sql = "SELECT   DISTINCT TRIM(UPPER(nome)) AS nome
								FROM     tbl_linha
								WHERE    ativo is true;";
						$res = pg_exec($con,$sql);

						for($i = 0; $i < pg_num_rows($res); $i++)
						{
							$xlinha = pg_result($res,$i,nome);

							if($xlinha == $linha) $selecionado = " SELECTED ";
							else $selecionado = "";

							echo "
							<option value='$xlinha' $selecionado>$xlinha</option>";
						}
						?>
						</select>
						<? echo "<br>Linhas Pesquisadas:".$linhas_pesquisa_nome; ?>
					</td>
					
				</tr>
					<td width="100">&nbsp;</td>
					<td align='left' nowrap colspan="3" width="130">Estado</td>
					<td align='left' nowrap colspan="3">Cidade</td>
				</tr>
				<tr>
					<td width="80">&nbsp;</td>
					<td colspan="3" align="left" width="130">
						<select name="estado" class='frm' onchange="buscacidadeposto(this.value,'estado')">
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
					
					<td align='left' nowrap colspan="3">
						<input type="text" size="20" name="cidade" id="cidade" class='frm' value="<? echo $cidade; ?>">
					</td>
				
				</tr>

				<tr>
					
					<td align='center' nowrap colspan="7"><input type="checkbox" value="xls" name="formato" id="formato" <? if ($_POST["formato"] == "xls") echo "checked"; ?>> Gerar arquivo Excel (XLS)</td>
					
				</tr>

			</table><br>
			<input type='submit' style="cursor:pointer" name='btn_acao' value='Consultar'>
		</td>
	</tr>
</table> <br>
</FORM>


<?
}
if(strlen($btn_acao)>0){ 
	if(strlen($msg_erro)==0)
	{	
		if(strlen($estado)>0){
			$cond_estado = " AND tbl_posto.estado = '$estado' ";
		}

		if(strlen($codigo_posto)>0){
			$cond_posto = " AND tbl_posto.posto = $codigo_posto";
		}

		if(strlen($cidade)>0){
			$cond_cidade = " AND tbl_posto.cidade ILIKE '%$cidade%'";
		}

		if(strlen($bairro)>0){
			$cond_bairro = " AND tbl_posto.bairro = '$bairro'";
		}

		$sql = "
		SELECT
		tbl_posto.posto,
		COUNT(bi_os.os) AS qtde_os,
		tbl_posto.nome,
		tbl_posto.nome_fantasia,
		tbl_posto.endereco,
		tbl_posto.numero,
		tbl_posto.bairro,
		tbl_posto.cidade,
		tbl_posto.estado,tbl_posto.fone,
		tbl_posto.email,
		tbl_posto.contato

		FROM
		bi_os
		JOIN tbl_posto USING(posto)

		WHERE
		bi_os.fabrica<>0
		AND excluida IS NOT TRUE
		AND data_abertura BETWEEN '$data_inicial' AND '$data_final'
		AND bi_os.linha IN ($linhas_pesquisa)
		$cond_bairro
		$cond_cidade
		$cond_estado
		$cond_posto

		GROUP BY
		tbl_posto.posto,
		tbl_posto.nome,
		tbl_posto.nome_fantasia,
		tbl_posto.endereco,
		tbl_posto.numero,
		tbl_posto.bairro,
		tbl_posto.cidade,
		tbl_posto.estado,tbl_posto.fone,
		tbl_posto.email,
		tbl_posto.contato

		ORDER BY
		COUNT(bi_os.os) DESC
		";
//		echo nl2br($sql);
//		exit;
		$res = pg_exec($con,$sql);

		if(pg_numrows($res)>0){
			echo "<table border='0' cellpadding='2' cellspacing='1' class='tabela' align='center'>";
			echo "<tr class='titulo_coluna' height='25'>";
			echo "<td>Quantidade de OS</td>";
			echo "<td>Razão Social</td>";
			echo "<td>Nome Fantasia</td>";
			echo "<td>Endereço</td>";
			echo "<td>Número</td>";
			echo "<td>Cidade</td>";
			echo "<td>Estado</td>";
			echo "<td>Bairro</td>";
			echo "<td>Contato</td>";
			echo "<td>Telefone</td>";
			echo "<td>E-mail</td>";
			if(strlen($familia)>0){
				echo "<td>Familia</td>";
			}
			echo "</tr>";

			for($i = 0; $i < pg_numrows($res); $i++)
			{
				$qtde_os          = pg_result($res,$i,qtde_os); 
				$nome             = pg_result($res,$i,nome);
				$nome_fantasia    = pg_result($res,$i,nome_fantasia);
				$endereco         = pg_result($res,$i,endereco);
				$numero           = pg_result($res,$i,numero);
				$cidade           = pg_result($res,$i,cidade);
				$estado           = pg_result($res,$i,estado);
				$bairro           = pg_result($res,$i,bairro);
				$contato          = pg_result($res,$i,contato);
				$fone             = pg_result($res,$i,fone);
				$email            = pg_result($res,$i,email);

				$aux_familia='';
				if(strlen($familia)>0){
					$aux_familia = $familia;
				}

				if ($i % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#F7F5F0';}
				echo "<TR bgcolor='$cor'>\n";

				echo "<td nowrap>$qtde_os</td>";
				echo "<td nowrap>$nome</td>";
				echo "<td nowrap>$nome_fantasia</td>";
				echo "<td nowrap>$endereco</td>";
				echo "<td nowrap>$numero</td>";
				echo "<td nowrap>$cidade</td>";
				echo "<td nowrap>$estado</td>";
				echo "<td nowrap>$bairro</td>";
				echo "<td nowrap>$contato</td>";
				echo "<td nowrap>$fone</td>";
				echo "<td nowrap>$email</td>";
				if (strlen($familia)> 0) {
					echo "<td nowrap>$aux_familia</td>";
				}
				echo "</tr>";
			}
			echo "</table>";
		}
	}
}
?>
<?
if ($_POST["formato"] == "xls") {
	$conteudo_excel = ob_get_clean();
	$arquivo = fopen("xls/relatorio_pa_todos.xls", "w+");
	fwrite($arquivo, $conteudo_excel);
	fclose($arquivo);
	header("location:xls/relatorio_pa_todos.xls");
	die;
}
else {
	include "rodape.php";
}

?>
