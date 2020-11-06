<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include "autentica_admin.php";
$admin_privilegios="gerencia";
$layout_menu = 'gerencia';
include "funcoes.php";


$array_estado = array("AC"=>"AC - Acre","AL"=>"AL - Alagoas","AM"=>"AM - Amazonas",
"AP"=>"AP - Amapá", "BA"=>"BA - Bahia", "CE"=>"CE - Ceará","DF"=>"DF - Distrito Federal",
"ES"=>"ES - Espírito Santo", "GO"=>"GO - Goiás","MA"=>"MA - Maranhão","MG"=>"MG - Minas Gerais",
"MS"=>"MS - Mato Grosso do Sul","MT"=>"MT - Mato Grosso", "PA"=>"PA - Pará","PB"=>"PB - Paraíba",
"PE"=>"PE - Pernambuco","PI"=>"PI - Piauí","PR"=>"PR - Paraná","RJ"=>"RJ - Rio de Janeiro",
"RN"=>"RN - Rio Grande do Norte","RO"=>"RO - Rondônia","RR"=>"RR - Roraima",
"RS"=>"RS - Rio Grande do Sul", "SC"=>"SC - Santa Catarina","SE"=>"SE - Sergipe",
"SP"=>"SP - São Paulo","TO"=>"TO - Tocantins");

if($_POST){
	$data_inicial       = $_POST['data_inicial'];
	$data_final         = $_POST['data_final'];
	$codigo_posto       = $_POST['codigo_posto'];
	$posto_nome         = $_POST['posto_nome'];
	$produto_referencia = $_POST['produto_referencia'];
	$produto_descricao  = $_POST['produto_descricao'];
	$linha              = $_POST['linha'];
	$familia            = $_POST['familia'];
	$estado             = $_POST['estado'];

	 if(empty($data_inicial) OR empty($data_final)){
        $msg_erro = "O campo data é obrigatório";
    }

	if(strlen($msg_erro)==0){
        list($di, $mi, $yi) = explode("/", $data_inicial);
        if(!checkdate($mi,$di,$yi))
            $msg_erro = "Data Inválida";
    }

    if(strlen($msg_erro)==0){
        list($df, $mf, $yf) = explode("/", $data_final);
        if(!checkdate($mf,$df,$yf))
            $msg_erro = "Data Inválida";
    }

	if(strlen($msg_erro)==0){
        $aux_data_inicial = "$yi-$mi-$di";
        $aux_data_final = "$yf-$mf-$df";

        if(strtotime($aux_data_final) < strtotime($aux_data_inicial)
        or strtotime($aux_data_final) > strtotime('today')){
            $msg_erro = "Data Inválida.";
        }
    }

	if(strlen($msg_erro)==0){
		if (strtotime($aux_data_inicial.'+1 month') < strtotime($aux_data_final) ) {
            $msg_erro = 'O intervalo entre as datas não pode ser maior que 1 mês';
        }
    }

	if(!empty($codigo_posto)){
		$sql = "SELECT posto
				FROM tbl_posto_fabrica
				WHERE fabrica = $login_fabrica
				AND tbl_posto_fabrica.codigo_posto = '$codigo_posto'";
		$res = pg_query($con,$sql);
		if(pg_numrows($res) > 0 ){
			$posto = pg_result($res,0,0);
			$cond = " AND tbl_os.posto = $posto ";
		} else {
			$msg_erro = "Posto não encontrado";
		}
	}

	if(!empty($produto_referencia)){
		$sql = "SELECT produto
				FROM tbl_produto
				WHERE referencia = '$produto_referencia'
				AND fabrica_i = $login_fabrica";
		$res = pg_query($con,$sql);
		if(pg_numrows($res) > 0 ){
			$produto = pg_result($res,0,0);
			$cond .= " AND tbl_os.produto = $produto ";
		} else {
			$msg_erro = "Produto não encontrado";
		}
	}

	if(!empty($linha)){
		$join = " JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto AND tbl_produto.fabrica_i = $login_fabrica JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha AND tbl_linha.fabrica = $login_fabrica";
		$cond .= " AND tbl_produto.linha = $linha";
	}

	if(!empty($familia)){
		$join .= " JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto AND tbl_produto.fabrica_i = $login_fabrica JOIN tbl_familia ON tbl_produto.familia = tbl_familia.familia AND tbl_familia.fabrica = $login_fabrica";
		$cond .= " AND tbl_produto.familia = $familia";
	}

	if(!empty($estado)){
		$cond .= "AND tbl_os.consumidor_estado = '$estado' ";
	}

}

$title = "Relatório de Ordens de Serviço";


include "cabecalho.php";
?>

<style type="text/css">
.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
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

table.tabela{
	padding-right:15px;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
	text-align:left;
}
.espaco{
	padding: 0 0 0 50px;
}

#gridRelatorio th span{
	margin-left:15px;
}

#gridRelatorio_length, #gridRelatorio_filter{
	display:none;
}

#gridRelatorio_info{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

#gridRelatorio_paginate a{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	padding:3px;
}

.first, .previous, .next, .last, .paginate_button, .sorting{
	cursor:pointer;
}

.paginate_active{
	font-size: 12px;
	font-weight: bold;
	color:#FFB70F;
}

.sorting_asc {
	background: url('../plugins/paginacao/imgs/sort_asc.png') no-repeat left;
}

.sorting_desc {
	background: url('../plugins/paginacao/imgs/sort_desc.png') no-repeat left;
}

.sorting {
	background: url('../plugins/paginacao/imgs/sort_both.png') no-repeat left;
}

.sorting_asc_disabled {
	background: url('../plugins/paginacao/imgs/sort_asc_disabled.png') no-repeat left;
}

.sorting_desc_disabled {
	background: url('../plugins/paginacao/imgs/sort_desc_disabled.png') no-repeat left;
}
</style>

<link rel="stylesheet" type="text/css" href="../plugins/jquery/datepick/telecontrol.datepick.css" media="all">
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<? include "javascript_pesquisas_novo.php"; ?>

<script type="text/javascript" src="../js/jquery-1.4.2.js"></script>
<script type="text/javascript" src="../plugins/paginacao/jquery.dataTables.min.js"></script>
<script src="../plugins/shadowbox/shadowbox.js"	type="text/javascript"></script>
<script language='javascript' src='ajax.js'></script>
<script type="text/javascript" src="js/jquery.maskedinput2.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>


<script language="JavaScript">

$(document).ready(function() {
	Shadowbox.init();

	$('#data_inicial').datepick({startDate:'01/01/2000'});
	$('#data_final').datepick({startDate:'01/01/2000'});
	$("#data_inicial").maskedinput("99/99/9999");
	$("#data_final").maskedinput("99/99/9999");


	$('#gridRelatorio').dataTable({
		"sPaginationType": "full_numbers",
		"iDisplayLength": 100,
		"oLanguage": {
		  "sInfo": "Resultados _START_ de _END_ do total de _TOTAL_ registros",
		  "oPaginate": {
			"sFirst": "Primeira",
			"sLast": "Última",
			"sNext": "Próximo",
			"sPrevious": "Anterior"
		  }
		},
		"aaSorting": [[ 1, "asc" ]]

	});

});

function retorna_posto(codigo_posto,posto,nome,cnpj,cidade,estado,credenciamento){
		gravaDados("codigo_posto",codigo_posto);
		gravaDados("posto_nome",nome);
		<?if ($login_fabrica == 19 || $login_fabrica == 10)
		{?>
		gravaDados("codigo_posto_off",codigo_posto);
		gravaDados("posto_nome_off",nome);
		<?}?>
}
function retorna_dados_produto(produto,linha,nome_comercial,voltagem,referencia,descricao,referencia_fabrica,garantia,ativo,valor_troca,troca_garantia,troca_faturada,mobra,off_line,capacidade,ipi,troca_obrigatoria,posicao){
		gravaDados("produto_referencia",referencia);
		gravaDados("produto_descricao",descricao);
}

function aumentaColuna(){
	$(".coluna").attr('width','100%');
}
</script>

<? if(!empty($msg_erro)){ ?>
	<table align="center" width="700" class="msg_erro">
		<tr><td><?=$msg_erro?></td></td>
	</table>
<? } ?>
<form name="frm_consulta" method="post">
	<table width="700" align="center" class="formulario">
		<caption class="titulo_tabela">Parâmetros de Pesquisa</caption>

		<tr><td colspan="2">&nbsp;</td></tr>

		<tr>
			<td class="espaco">
				Data Inicial <br>
				<input type="text" size="13" name="data_inicial" id="data_inicial" value="<?=$data_inicial?>" class="frm">
			</td>
			<td>
				Data Final <br>
				<input type="text" size="13" name="data_final" id="data_final" value="<?=$data_final?>" class="frm">
			</td>
		</tr>

		<tr><td colspan="2">&nbsp;</td></tr>

		<tr>
			<td class="espaco">
				Posto <br>
				<input type="text" name="codigo_posto" id="codigo_posto" size="13" value="<?=$codigo_posto?>" class="frm">
				<img border="0" align="absmiddle" onclick="javascript: fnc_pesquisa_posto ('',document.frm_consulta.codigo_posto, '')" alt="Clique aqui para pesquisar postos pelo código" style="cursor: pointer;" src="imagens/lupa.png">
			</td>
			<td>
				Descrição <br>
				<input type="text" name="posto_nome" id="posto_nome" size="45" value="<?=$posto_nome?>" class="frm">
				<img border="0" align="absmiddle" onclick="javascript: fnc_pesquisa_posto ('','', document.frm_consulta.posto_nome)" alt="Clique aqui para pesquisar postos pelo código" style="cursor: pointer;" src="imagens/lupa.png">
			</td>
		</tr>

		<tr><td colspan="2">&nbsp;</td></tr>

		<tr>
			<td class="espaco">
				Ref. Produto <br>
				<input type="text" name="produto_referencia" id="produto_referencia" size="13" value="<?=$produto_referencia?>" class="frm">
				<img border="0" align="absmiddle" onclick="javascript: fnc_pesquisa_produto ('', document.frm_consulta.produto_referencia,'')" style="cursor:pointer" src="imagens/lupa.png">
			</td>
			<td>
				Descrição Produto <br>
				<input type="text" name="produto_descricao" name="produto_descricao" size="45" value="<?=$produto_descricao?>" class="frm">
				<img border="0" align="absmiddle" onclick="javascript: fnc_pesquisa_produto (document.frm_consulta.produto_descricao, '','')" style="cursor:pointer" src="imagens/lupa.png">
			</td>
		</tr>

		<tr><td colspan="2">&nbsp;</td></tr>

		<tr>
			<td class="espaco">
				Linha <br>
				<?
				echo "<select name='linha' size='1' class='frm' style='width:95px'>";
				echo "<option value=''></option>";
				$sql = "SELECT linha, nome from tbl_linha where fabrica = $login_fabrica and ativo = true order by nome";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res)>0){
					for($i=0;pg_num_rows($res)>$i;$i++){
						$xlinha = pg_fetch_result($res,$i,linha);
						$xnome = pg_fetch_result($res,$i,nome);

						$selected = ($linha == $xlinha) ? "SELECTED" : "";
						?>
						<option value="<?echo $xlinha;?>" <?echo $selected;?>><?echo $xnome;?></option>
						<?
					}
				}
				echo "</SELECT>";
				?>
			</td>
			<td>
				Família <br>
				<?
					echo "<select name='familia' size='1' class='frm' style='width:95px'>";
					echo "<option value=''></option>";
					$sql = "SELECT familia, descricao from tbl_familia where fabrica = $login_fabrica and ativo = true order by descricao";
					$res = pg_query($con,$sql);
					if(pg_num_rows($res)>0){
						for($i=0;pg_num_rows($res)>$i;$i++){
							$xfamilia = pg_fetch_result($res,$i,familia);
							$xdescricao = pg_fetch_result($res,$i,descricao);

							$selected = ($xfamilia == $familia) ? "SELECTED" : "";
							?>
							<option value="<?echo $xfamilia;?>" <?echo $selected;?>><?echo $xdescricao;?></option>
							<?
						}
					}
					echo "</SELECT>";
					?>
			</td>
		</tr>

		<tr><td colspan="2">&nbsp;</td></tr>

		<tr>
			<td class="espaco">
				<select name="estado" id="estado" size="1" class="frm" style="width:170px">
				<option value="">Selecione um Estado</option>
				<?php
				foreach ($array_estado as $k => $v) {
				echo '<option value="'.$k.'"'.($estado == $k ? ' selected="selected"' : '').'>'.$v."</option>\n";
				}?>
				</select>
			</td>
			<td> &nbsp; </td>
		</tr>

		<tr><td colspan="2">&nbsp;</td></tr>

		<tr>
			<td colspan="2" align="center">
				<input type="submit" value="Pesquisar">
			</td>
		</tr>

		<tr><td colspan="2">&nbsp;</td></tr>
	</table>
</form>
<br />
	<?php
	if($_POST AND empty($msg_erro)){
		$sql = "SELECT DISTINCT tbl_posto_fabrica.codigo_posto::text  AS posto_codigo          ,
				tbl_posto.nome                               AS posto_nome            ,
				tbl_os.os                                                             ,
				tbl_os.sua_os                                                         ,
				to_char(tbl_os.data_digitacao,'DD/MM/YYYY')  AS data_digitacao        ,
				to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura         ,
				to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento       ,
				to_char(tbl_os.finalizada,'DD/MM/YYYY')      AS finalizada            ,
				tbl_os.produto                                                        ,
				tbl_os.serie                                                          ,
				tbl_os.consumidor_nome                                                ,
				tbl_os.consumidor_fone                                                ,
				tbl_os.consumidor_cpf                                                 ,
				tbl_os.consumidor_endereco                                            ,
				tbl_os.consumidor_numero                                              ,
				tbl_os.consumidor_complemento                                         ,
				tbl_os.consumidor_bairro                                              ,
				tbl_os.consumidor_cep                                                 ,
				tbl_os.consumidor_cidade                                              ,
				tbl_os.consumidor_estado                                              ,
				tbl_os.consumidor_email                                               ,
				tbl_os.consumidor_revenda                                             ,
				tbl_os.defeito_reclamado_descricao           AS defeito_reclamado     ,
				tbl_defeito_constatado.descricao             AS defeito_constatado    ,
				tbl_os.solucao_os                                                     ,
				tbl_os.nota_fiscal                                                    ,
				to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf               ,
				tbl_os.revenda_nome                                                   ,
				tbl_os.revenda_cnpj                                                   ,
				tbl_os.aparencia_produto                                              ,
				tbl_os.acessorios
				INTO TEMP tmp_os_mensal_$login_admin
				FROM tbl_os
				JOIN tbl_posto         ON tbl_posto.posto         = tbl_os.posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado AND tbl_defeito_constatado.fabrica = $login_fabrica
				LEFT JOIN tbl_os_produto ON tbl_os_produto.os      = tbl_os.os
				$join
				WHERE tbl_os.fabrica = $login_fabrica
				AND tbl_os.data_abertura BETWEEN '$aux_data_inicial' and '$aux_data_final'
				$cond;

				CREATE INDEX tmp_os_mensal_PRODUTO_$login_admin ON tmp_os_mensal_$login_admin(produto);

				CREATE INDEX tmp_os_mensal_SOLUCAO_$login_admin ON tmp_os_mensal_$login_admin(solucao_os);

				SELECT posto_codigo                   ,
				posto_nome                            ,
				os                                    ,
				sua_os                                ,
				data_digitacao                        ,
				data_abertura                         ,
				data_fechamento                       ,
				finalizada                            ,
				tbl_produto.referencia                ,
				tbl_produto.descricao                 ,
				serie                                 ,
				consumidor_nome                       ,
				consumidor_fone                       ,
				consumidor_cpf                        ,
				consumidor_endereco                   ,
				consumidor_numero                     ,
				consumidor_complemento                ,
				consumidor_bairro                     ,
				consumidor_cep                        ,
				consumidor_cidade                     ,
				consumidor_estado                     ,
				consumidor_email                      ,
				consumidor_revenda                    ,
				defeito_reclamado                     ,
				defeito_constatado                    ,
				tbl_solucao.descricao AS solucao      ,
				nota_fiscal                           ,
				data_nf                               ,
				revenda_nome                          ,
				revenda_cnpj                          ,
				aparencia_produto                     ,
				acessorios
				FROM tmp_os_mensal_$login_admin
				LEFT JOIN tbl_produto ON tbl_produto.produto = tmp_os_mensal_$login_admin.produto
				LEFT JOIN tbl_solucao ON tbl_solucao.solucao = tmp_os_mensal_$login_admin.solucao_os AND tbl_solucao.fabrica = $login_fabrica
				ORDER BY posto_nome";

		$res = pg_query($con,$sql);
		#echo nl2br($sql);
		if(pg_numrows($res) > 0 ){
			$total_registros = pg_numrows($res);
			$rows = pg_num_rows($res);

			echo "<center><span style='font-size:10px;'>Total de Registros:". number_format($rows,0)."</span></center>";
			$resultado = "<table align='center' class='tabela' border='1' id='gridRelatorio' cellpadding='1' cellspacing='1'>";
							$resultado .= "<thead>";
								$resultado .= "
								<tr class='titulo_coluna'>
									<th><span>Código Posto</span></th>
									<th>Nome Posto</th>
									<th>OS</th>
									<th><span>Abertura</span></th>
									<th><span>Digitação</span></th>
									<th><span>Fechamento</span></th>
									<th>Produto</th>
									<th>Série</th>
									<th>Consumidor Nome</th>
									<th><span>Consumidor Fone</span></th>
									<th><span>Consumidor CPF</span></th>
									<th><span>Consumidor Endereço</span></th>
									<th><span>Consumidor Número </span></th>
									<th><span>Consumidor Complemento</span></th>
									<th><span>Consumidor Bairro</span></th>
									<th><span>Consumidor Cep</span></th>
									<th><span>Consumidor Cidade</span></th>
									<th><span>Consumidor Estado</span></th>
									<th><span>Consumidor Email</span></th>
									<th>Defeito Reclamado</th>
									<th>Defeito Constatado</th>
									<th>Solução</th>
									<th><span>Peças Solicitadas (Qtde)</span></th>
									<th>Nota Fiscal / Data</th>
									<th>Revenda Nome</th>
									<th><span>Revenda CNPJ</span></th>
								</tr>";
						$resultado .= "</thead>";
					$resultado .= "<tbody>";

			for($i = 0; $i < $total_registros; $i++){
				$posto_codigo           = pg_result($res, $i, posto_codigo);
				$posto_nome             = pg_result($res, $i, posto_nome);
				$sua_os                 = pg_result($res, $i, sua_os);
				$os                     = pg_result($res, $i, os);
				$data_digitacao         = pg_result($res, $i, data_digitacao);
				$data_abertura          = pg_result($res, $i, data_abertura);
				$data_fechamento        = pg_result($res, $i, data_fechamento);
				$finalizada             = pg_result($res, $i, finalizada);
				$referencia             = pg_result($res, $i, referencia);
				$descricao              = pg_result($res, $i, descricao);
				$serie                  = pg_result($res, $i, serie);
				$consumidor_nome        = pg_result($res, $i, consumidor_nome);
				$consumidor_fone        = pg_result($res, $i, consumidor_fone);
				$consumidor_endereco    = pg_result($res, $i, consumidor_endereco);
				$consumidor_numero      = pg_result($res, $i, consumidor_numero);
				$consumidor_complemento = pg_result($res, $i, consumidor_complemento);
				$consumidor_bairro      = pg_result($res, $i, consumidor_bairro);
				$consumidor_cep         = pg_result($res, $i, consumidor_cep);
				$consumidor_cidade      = pg_result($res, $i, consumidor_cidade);
				$consumidor_estado      = pg_result($res, $i, consumidor_estado);
				$consumidor_email       = pg_result($res, $i, consumidor_email);
				$consumidor_cpf         = pg_result($res, $i, consumidor_cpf);
				$defeito_reclamado      = pg_result($res, $i, defeito_reclamado);
				$defeito_constatado     = pg_result($res, $i, defeito_constatado);
				$solucao                = pg_result($res, $i, solucao);
				$nota_fiscal            = pg_result($res, $i, nota_fiscal);
				$data_nf                = pg_result($res, $i, data_nf);
				$revenda_nome           = pg_result($res, $i, revenda_nome);
				$revenda_cnpj           = pg_result($res, $i, revenda_cnpj);
				$aparencia_produto      = pg_result($res, $i, aparencia_produto);
				$acessorios             = pg_result($res, $i, acessorios);

				$cor = ($i % 2) ?"#F7F5F0":'#F1F4FA';

				$resultado .= "<tr bgcolor='$cor'>";
					$resultado .= "<td nowrap>$posto_codigo&nbsp;</td>";
					$resultado .= "<td nowrap>$posto_nome</td>";
					$resultado .= "<td nowrap><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></td>";
					$resultado .= "<td nowrap align='center'>$data_abertura</td>";
					$resultado .= "<td nowrap align='center'>$data_digitacao</td>";
					$resultado .= "<td nowrap align='center'>$data_fechamento</td>";
					$resultado .= "<td nowrap>$referencia - $descricao</td>";
					$resultado .= "<td nowrap>$serie</td>";
					$resultado .= "<td nowrap>$consumidor_nome</td>";
					$resultado .= "<td nowrap>$consumidor_fone</td>";
					$resultado .= "<td nowrap>$consumidor_cpf</td>";
					$resultado .= "<td nowrap>$consumidor_endereco</td>";
					$resultado .= "<td nowrap align='center'>$consumidor_numero</td>";
					$resultado .= "<td nowrap>$consumidor_complemento</td>";
					$resultado .= "<td nowrap>$consumidor_bairro</td>";
					$resultado .= "<td nowrap>$consumidor_cep</td>";
					$resultado .= "<td nowrap>$consumidor_cidade</td>";
					$resultado .= "<td nowrap align='center'>$consumidor_estado</td>";
					$resultado .= "<td nowrap>$consumidor_email</td>";
					$resultado .= "<td nowrap>$defeito_reclamado</td>";
					$resultado .= "<td nowrap>$defeito_constatado</td>";
					$resultado .= "<td nowrap>$solucao</td>";
					$resultado .= "<td nowrap>";

					$sqlP = "SELECT tbl_peca.referencia, tbl_peca.descricao, SUM(tbl_os_item.qtde) AS qtde
								FROM tbl_os_produto
								JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
								JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca AND tbl_peca.fabrica = $login_fabrica WHERE tbl_os_produto.os = $os
								GROUP BY tbl_peca.referencia, tbl_peca.descricao;";
					$resP = pg_query($con,$sqlP);
					if(pg_numrows($resP) > 0){
						for($x = 0; $x < pg_numrows($resP); $x++){
							$resultado .= pg_result($resP,$x,'referencia')." - ".pg_result($resP,$x,'descricao');
							$resultado .= " - (".pg_result($resP,$x,'qtde').") <br />";

						}
					}
					$resultado .= "</td>";

					$resultado .= "<td nowrap>$nota_fiscal - $data_nf</td>";
					$resultado .= "<td nowrap>$revenda_nome</td>";
					$resultado .= "<td nowrap>$revenda_cnpj&nbsp;</td>";
				$resultado .= "</tr>";

			}
			$resultado .= "</tbody>";
			$resultado .= "</table>";

			echo $resultado;

			$data = date('Y-m-d');
			$arquivo = "xls/relatorio-ordens-servico-".$login_fabrica."-".$data.".xls";
			$fp = fopen($arquivo,"w");
			fwrite($fp,$resultado);
			fclose($fp);
			echo "<br><a href='$arquivo'><img src='imagens/excel.png' height='20px' width='20px' align='absmiddle'>&nbsp;&nbsp;&nbsp;Download Arquivo Excel</a>";
		} else {
			echo "<center>Nenhum resultado encontrado</center>";
		}
	}
?>

<? include "rodape.php"; ?>
