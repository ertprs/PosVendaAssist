<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

include 'autentica_admin.php';

include 'funcoes.php';

$layout_menu = "gerencia";
$title = "RELATÓRIO DE DEFEITOS OS X TIPO DE ATENDIMENTO";

include "cabecalho.php";

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
	FONT: 10pt Arial ;
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
</style>


<? include "javascript_calendario.php"; ?>
<? include "javascript_pesquisas.php"; ?>

<script language='javascript' src='../ajax.js'></script>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>


<FORM name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

<table width='550' class='Conteudo' style='background-color: #485989' border='1' cellpadding='5' cellspacing='1' align='center'>
	<tr>
		<td class='Titulo' background='imagens_admin/azul.gif'><?php echo $title; ?></td>
	</tr>
	<tr>
		<td bgcolor='#DBE5F5' valign='bottom'>
			<table border='0' cellspacing='1' cellpadding='2' width="100%">
				<tr class="Conteudo" bgcolor="#D9E2EF">
					<td width="10">&nbsp;</td>
					<td align='right' nowrap><font size='2'>Data Inicial</td>
					<td align='left' nowrap width=150>
						<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='frm' value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
					</td>
					<td align='right' nowrap><font size='2'>Data Final</td>
					<td align='left' nowrap width=150>
						<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='frm' value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
					</td>
					<td width="10">&nbsp;</td>
				</tr>
				<tr class="Conteudo" bgcolor="#D9E2EF">
					<td width="10">&nbsp;</td>
				</tr>

				<tr class="Conteudo" bgcolor="#D9E2EF">
					<td width="10">&nbsp;</td>
					<td align='right' nowrap><font size='2'>Família</td>
					<td align='left' nowrap colspan=3>
						<select name="familia" id="familia" class='frm'>
						<?php
						$sql = "
						SELECT
						familia,
						descricao
						
						FROM
						tbl_familia
						
						WHERE
						fabrica=$login_fabrica
						
						ORDER BY
						descricao
						";
						$res = pg_exec($con,$sql);

						echo "
							<option value=0>---------- TODAS ----------</option>";

						for($i = 0; $i < pg_num_rows($res); $i++)
						{
							$codigo = pg_result($res,$i,familia);
							$descricao = pg_result($res,$i,descricao);

							if($codigo == $familia) $selecionado = " SELECTED ";
							else $selecionado = "";

							echo "
							<option value=$codigo $selecionado>$descricao</option>";
						}
						?>
						</select>
					</td>
					<td width="10">&nbsp;</td>
				</tr>
				<tr class="Conteudo" bgcolor="#D9E2EF">
					<td width="10">&nbsp;</td>
				</tr>

				<tr class="Conteudo" bgcolor="#D9E2EF">
					<td width="10">&nbsp;</td>
					<td  align='right' nowrap ><font size='2'>Código Posto</font></td>
					<td align='left' nowrap><INPUT TYPE="text" NAME="codigo_posto" SIZE="8" value="<?php echo $codigo_posto; ?>" class="frm"><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_tamanho_minimo(document.frm_relatorio.codigo_posto,3); fnc_pesquisa_posto (document.frm_relatorio.codigo_posto,document.frm_relatorio.nome_posto,'codigo')" ></td>
					<td  align='right' nowrap ><font size='2'>Nome Posto</font></td>
					<td align='left' nowrap><INPUT TYPE="text" NAME="nome_posto" size="30" value="<?php echo $nome_posto; ?>" class="frm"> <IMG src="imagens/lupa.png" style="cursor:pointer" align='absmiddle' alt="Clique aqui para pesquisas postos pelo nome" onclick="javascript: fnc_tamanho_minimo(document.frm_relatorio.nome_posto,3); fnc_pesquisa_posto (document.frm_relatorio.codigo_posto,document.frm_relatorio.nome_posto,'nome')" ></td>
					<td width="10">&nbsp;</td>
				</tr>
			</table><br>
			<input type='submit' style="cursor:pointer" name='btn_acao' value='Consultar'>
		</td>
	</tr>
</table>
</FORM>


<?
$btn_acao = $_POST['btn_acao'];
if(strlen($btn_acao)>0)
{
	if(($data_inicial) && ($data_final))
	{
		$data_inicial_sql = implode("-", array_reverse(explode("/", $data_inicial)));
		$data_final_sql = implode("-", array_reverse(explode("/", $data_final)));
		$sql = "SELECT '$data_inicial_sql'::date + INTERVAL '1 MONTH' - '$data_final_sql'::date";
		$res = pg_exec($con, $sql);
		if(pg_result($res,0,0) < 0)
			$msg_erro = "Período informado maior que 1 mês";
		else
			$data_inicial_sql = "AND tbl_os.finalizada BETWEEN '$data_inicial_sql'::date AND '$data_final_sql'::date + INTERVAL '1 day'";
	}
	else
	{
		$msg_erro = "Informe a data inicial e a data final";
	}

	if(strlen($msg_erro)==0)
	{
		if($familia)
		{
			$familia_sql = "AND tbl_produto.familia=$familia";
		}

		if($codigo_posto)
		{
			$codigo_posto_sql = "AND tbl_posto_fabrica.codigo_posto='$codigo_posto'";
		}

		$sql = "SELECT
					tbl_os.sua_os,
					tbl_familia.descricao AS familia_descricao,
					tbl_produto.referencia,
					tbl_produto.descricao,
					tbl_defeito_constatado.codigo AS defeito_codigo,
					tbl_defeito_constatado.lista_garantia ,
					tbl_defeito_constatado.descricao AS defeito_descricao,
					TO_CHAR(tbl_os.mao_de_obra, '99999999D99') AS mao_de_obra,
					tbl_os.serie,
					TO_CHAR(tbl_os.data_nf, 'DD/MM/YYYY') AS datanf,
					TO_CHAR(tbl_os.data_digitacao, 'DD/MM/YYYY') AS data_digitacao,
					TO_CHAR(tbl_os.finalizada, 'DD/MM/YYYY') AS datafinalizada,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome AS posto_nome
					/*,
					 CASE
						WHEN tbl_os.mao_de_obra=16 THEN 'Atendimento Simples'
						WHEN tbl_os.mao_de_obra=37 THEN 'Atendimento Complexo'
						WHEN tbl_os.mao_de_obra=27 THEN 'Atendimento para Transformação de Gás Natural / Rua'
						WHEN tbl_os.mao_de_obra=6.5 THEN 'Atendimento em Suporte Baby / Fogão 2 Bocas'
						WHEN tbl_os.mao_de_obra=66 THEN 'Reoperação com ou Sem Troca de Compressor (GAS R-13a)'
						WHEN tbl_os.mao_de_obra=20 THEN 'Troca de Produto / Processo Judicial'
						WHEN tbl_os.mao_de_obra=25 THEN 'Atendimento a Lavadoras'
						WHEN tbl_os.mao_de_obra =79 THEN 'Substituição de Gabinete (GAS R-134a)'
						ELSE 'Outros Atendimentos'
					END AS tipoatendimento
					*/
				FROM
				tbl_os
				JOIN tbl_os_defeito_reclamado_constatado USING(os)
				JOIN tbl_defeito_constatado ON tbl_os_defeito_reclamado_constatado.defeito_constatado=tbl_defeito_constatado.defeito_constatado
				JOIN tbl_produto ON tbl_os.produto=tbl_produto.produto
				JOIN tbl_familia ON tbl_produto.familia=tbl_familia.familia
				JOIN tbl_posto ON tbl_os.posto=tbl_posto.posto
				JOIN tbl_posto_fabrica ON tbl_posto.posto=tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica=$login_fabrica

				WHERE
				tbl_os.fabrica=$login_fabrica
				$data_inicial_sql
				$familia_sql
				$codigo_posto_sql

				ORDER BY
				tbl_posto.nome,
				tbl_familia.descricao,
				tbl_os.mao_de_obra,
				tbl_os.sua_os
		";
		$res = pg_exec($con,$sql);
		
		$colunas = array();
		if(pg_numrows($res)>0)
		{
			echo "<table border='0' align='center' cellpadding='1' cellspacing='2' style='border:#485989 1px solid; background-color: #e6eef7;font-size:11px'>";
				echo "<TR >\n";
					echo "<td class='menu_top'>OS</td>\n";
					echo "<td class='menu_top'>Família</td>\n";
					echo "<td class='menu_top'>Referência</td>\n";
					echo "<td class='menu_top'>Descrição</td>\n";
					echo "<td class='menu_top'>Código Defeito</td>\n";
					echo "<td class='menu_top'>Descrição Defeito</td>\n";
					echo "<td class='menu_top'>Tipo Defeito</td>\n";
					echo "<td class='menu_top' width=70>Valor Mão de Obra</td>\n";
					echo "<td class='menu_top'>Número de Série</td>\n";
					echo "<td class='menu_top'>Data Nota Fiscal</td>\n";
					echo "<td class='menu_top'>Data Digitação</td>\n";
					echo "<td class='menu_top'>Data Finalização</td>\n";
					echo "<td class='menu_top'>SAE</td>\n";
					echo "<td class='menu_top'>Tipo Atendimento</td>\n";
				echo "</TR >\n";

			for($i = 0; $i < pg_numrows($res); $i++)
			{
				if ($i % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#e6eef7';}

				echo "<TR bgcolor='$cor'>\n";
					echo "<td nowrap>" . pg_result($res, $i, sua_os) . "</td>\n";
					echo "<td>" . pg_result($res, $i, familia_descricao) . "</td>\n";
					echo "<td>" . pg_result($res, $i, referencia) . "</td>\n";
					echo "<td>" . pg_result($res, $i, descricao) . "</td>\n";
					echo "<td>" . pg_result($res, $i, defeito_codigo) . "</td>\n";
					echo "<td>" . pg_result($res, $i, defeito_descricao) . "</td>\n";
					echo "<td>";
					echo (substr(pg_result($res, $i, defeito_codigo),0,2) == '00') ? "Não Defeito" : "Defeito";
					echo "</td>\n";
					echo "<td>" . pg_result($res, $i, mao_de_obra) . "</td>\n";
					echo "<td>" . pg_result($res, $i, serie) . "</td>\n";
					echo "<td>" . pg_result($res, $i, datanf) . "</td>\n";
					echo "<td>" . pg_result($res, $i, data_digitacao) . "</td>\n";
					echo "<td>" . pg_result($res, $i, datafinalizada) . "</td>\n";
					echo "<td>[" . pg_result($res, $i, codigo_posto) . "] " . pg_result($res, $i, posto_nome) . "</td>\n";
					if(pg_result($res, $i, lista_garantia)==1){
						$tipoatendimento = 'Atendimento Simples / Orientação de uso';
					}
					if(pg_result($res, $i, lista_garantia)==2){
						$tipoatendimento = 'Atendimento Complexo';
					}
					if(pg_result($res, $i, lista_garantia)==4){
						$tipoatendimento = 'Atendimento Suporta Baby/Fogão';
					}
					if(pg_result($res, $i, lista_garantia)==6){
						$tipoatendimento = 'Substituição Gabinete R134A';
					}
					if(pg_result($res, $i, lista_garantia)==7){
						$tipoatendimento = 'Reoperação com Carga R134A';
					}
					if(pg_result($res, $i, lista_garantia)==8){
						$tipoatendimento = 'Troca de Produto / Processo Judicial';
					}
					if(pg_result($res, $i, lista_garantia)==9){
						$tipoatendimento = 'Transformação de Gás';
					}
					if(pg_result($res, $i, lista_garantia)==10){
						$tipoatendimento = 'Atendimento Lavadoras';
					}
					if(pg_result($res, $i, lista_garantia)==11){
						$tipoatendimento = 'Troca do Kit Mecânico Lavadora';
					}
					if(pg_result($res, $i, lista_garantia)==12){
						$tipoatendimento = 'Atendimento Simples da Refrg. Comercial';
					}
					if(pg_result($res, $i, lista_garantia)==13){
						$tipoatendimento = 'Cliente Ausente / End. Não localizado';
					}
					echo "<td>" . $tipoatendimento . "</td>\n";
				echo "</TR >\n";
			}
			echo "</table>";
		}
		else
		{
			echo "<center>Nenhuma OS encontrada para os dados informados</center>";
		}
	}
	else
	{
		echo "<center>$msg_erro</center>";
	}
}
?>

<? include "rodape.php" ?>
