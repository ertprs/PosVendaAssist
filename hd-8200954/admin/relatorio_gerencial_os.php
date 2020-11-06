<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "gerencia";

include "autentica_admin.php";
include 'funcoes.php';

 $array_estado = array("AC"=>"AC - Acre","AL"=>"AL - Alagoas","AM"=>"AM - Amazonas",
  "AP"=>"AP - Amapá", "BA"=>"BA - Bahia", "CE"=>"CE - Ceará","DF"=>"DF - Distrito Federal",
  "ES"=>"ES - Espírito Santo", "GO"=>"GO - Goiás","MA"=>"MA - Maranhão","MG"=>"MG - Minas Gerais",
  "MS"=>"MS - Mato Grosso do Sul","MT"=>"MT - Mato Grosso", "PA"=>"PA - Pará","PB"=>"PB - Paraíba",
  "PE"=>"PE - Pernambuco","PI"=>"PI - Piauí","PR"=>"PR - Paraná","RJ"=>"RJ - Rio de Janeiro",
  "RN"=>"RN - Rio Grande do Norte","RO"=>"RO - Rondônia","RR"=>"RR - Roraima",
  "RS"=>"RS - Rio Grande do Sul", "SC"=>"SC - Santa Catarina","SE"=>"SE - Sergipe",
  "SP"=>"SP - São Paulo","TO"=>"TO - Tocantins");

if($_POST){

	$estado 			= $_POST['estado'];
	$regiao 			= $_POST['regiao'];
	$data_inicial 		= $_POST["data_inicial"];
    $data_final 		= $_POST["data_final"];
    $produto_referencia = $_POST["produto_referencia"];
    $produto_descricao 	= $_POST["produto_descricao"];
    $revenda_cnpj 		= $_POST["revenda_cnpj"];
    $revenda_nome 		= $_POST["revenda_nome"];
    $gerente 			= strtoupper($_POST["gerente"]);

    $revenda_cnpj = str_replace('-', '', $revenda_cnpj);
	$revenda_cnpj = str_replace('/', '', $revenda_cnpj);
	$revenda_cnpj = str_replace('.', '', $revenda_cnpj);
	$revenda_cnpj = trim($revenda_cnpj);

    if(empty($data_inicial) OR empty($data_final)){
        $msg_erro = "Data obrigatória";
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
    }

    if(strlen($msg_erro)==0){
    	if (strtotime($aux_data_inicial.'+1 month') < strtotime($aux_data_final) ) {
            $msg_erro = 'O intervalo entre as datas não pode ser maior que 1 mês';
        }
    }

	if(strlen($estado) > 0){
		$cond .= " AND tbl_os.consumidor_estado = '$estado'";
	}

	if (strlen($regiao) > 0 and strlen($estado) == 0) {
		if ($regiao == 1) {
			$cond .= " AND tbl_posto_fabrica.contato_estado = 'SP'";
		}
		if ($regiao == 2) {
			$cond .= " AND tbl_posto_fabrica.contato_estado IN ('SC', 'RS', 'PR')";
		}
		if ($regiao == 3) {
			$cond .= " AND tbl_posto_fabrica.contato_estado IN ('RJ', 'ES', 'MG')";
		}
		if ($regiao == 4) {
			$cond .= " AND tbl_posto_fabrica.contato_estado IN ('GO', 'MS', 'MT', 'DF', 'CE', 'RN')";
		}
		if ($regiao == 5) {
			$cond .= " AND tbl_posto_fabrica.contato_estado IN ('SE','AL', 'PE', 'PB', 'BA')";
		}
		if ($regiao == 6) {
			$cond .= " AND tbl_posto_fabrica.contato_estado IN ('TO', 'PA', 'AP', 'RR', 'AM', 'AC', 'RO', 'MA', 'PI')";
		}
	}

	if($produto_referencia){
		$sql = "SELECT produto FROM tbl_produto WHERE fabrica_i = $login_fabrica AND referencia = '$produto_referencia'";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
			$produto = pg_fetch_result($res, 0, 'produto');
			$cond .= " AND tbl_os.produto = $produto ";
		}else{
			$msg_erro = "Produto não encontrado";
		}
	}

	if($revenda_cnpj){
		$sql = "SELECT revenda FROM tbl_revenda WHERE cnpj = '$revenda_cnpj'";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
			$revenda = pg_fetch_result($res, 0, 'revenda');
			$cond .= " AND tbl_os.revenda = $revenda ";
		}else{
			$msg_erro = "Revenda não encontrada";
		}
	}

	if($gerente){
		$cond .= " AND tbl_revenda_comercial.nome_gerente LIKE '$gerente%' ";
	}
}

$layout_menu = "gerencia";
$title = "Relatório Gerencial de OSs";
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

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

table.formulario tr td{
	padding-left: 40px;
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
	paddi
}

table.tabela tr th{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
}

.toggle:hover{
	background-color: #CCC;
	cursor: pointer;
}

</style>

<?php 
	include_once '../js/js_css.php'; /* Todas libs js, jquery e css usadas no Assist - HD 969678 */
?>

<script language="javascript">
	$(document).ready(function(){
		Shadowbox.init();
		$('#data_inicial').datepick({startDate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$("#data_inicial").mask("99/99/9999");
		$("#data_final").mask("99/99/9999");
		$("#consumidor_cpf").mask("999.999.999-99");
		$("#revenda_cnpj").mask("99.999.999/9999-99");

		$(".toggle").click(function(){
			var os = $(this).parent().attr('rel');
			window.open("os_press.php?os="+os);
		})
	});

	function pesquisaProduto(campo, tipo) {
		var campo = $.trim(campo.value);
		var tipo_atendimento = $("#tipo_atendimento").val();

		if (campo.length > 2) {
			Shadowbox.open({
				content: "produto_pesquisa_2_nv.php?"+tipo+"="+campo+"&tipo_atendimento="+tipo_atendimento,
				player: "iframe",
				title: "Pesquisa de Produto",
				width: 800,
				height: 500
			});
		} else {
			alert("Informar toda ou parte da informação para realizar a pesquisa !");
		}
	}

	function retorna_dados_produto(produto,linha,nome_comercial,voltagem,referencia,descricao,garantia,mobra,ativo,off_line,capacidade,valor_troca,troca_garantia,troca_faturada,referencia_antiga,troca_obrigatoria,posicao){
	        gravaDados("produto_referencia",referencia);
	        $('#produto_referencia').blur();
	        gravaDados("produto_descricao",descricao);
	        gravaDados("produto_voltagem",voltagem);
	}

	function retorna_posto(codigo_posto,posto,nome,cnpj,cidade,estado,credenciamento,num_posto,cep, endereco, numero, bairro){
		gravaDados('posto_codigo',codigo_posto);
		gravaDados('posto_nome',nome);
	}

	function pesquisaRevenda(campo,tipo,tipo_revenda){
        var campo = campo.value;
        
        if (jQuery.trim(campo).length > 2){
            Shadowbox.open({
                content:    "pesquisa_revenda_nv.php?"+tipo+"="+campo+"&tipo="+tipo+"&tipo_revenda="+tipo_revenda,
                player: "iframe",
                title:      "Pesquisa Revenda",
                width:  800,
                height: 500
            });
        }else
            alert("Informar toda ou parte da informação para realizar a pesquisa!");
    }

	function retorna_revenda(nome,cnpj,nome_cidade,fone,endereco,numero,complemento,bairro,cep,estado,email){
	    gravaDados("revenda_cnpj",cnpj);
		gravaDados("revenda_nome",nome);
	}

	function gravaDados(name, valor){
	    try {
	        $("input[name="+name+"]").val(valor);
	    } catch(err){
	        return false;
	    }
	}

</script>

<?php
	if(!empty($msg_erro)){
		echo "<table align='center' width='700'>
				<tr class='msg_erro'>
					<td> $msg_erro </td>
				</tr>
			  </table>";
	}
?>

<form name='frm_pesquisa' method='post' action=''>
<table align='center' width='700' class='formulario'>
	<caption class='titulo_tabela'>Parâmetros de Pesquisa</caption>

	<tr>
		<td>
			Data Inicial <br />
			<input type="text" name="data_inicial" id="data_inicial" size="13" value="<? echo $data_inicial; ?>" class="frm">
		</td>

		<td>
			Data Final <br />
			<input type="text" name="data_final" id="data_final" size="13"  value="<? echo $data_final; ?>" class="frm">
		</td>
	</tr>

	<tr>
		<td>
			Estado <br />
			<select name="estado" id="estado" size="1" class="frm" style="width:170px">
				<option value="">Selecione um Estado</option>
				<?php
				foreach ($array_estado as $k => $v) {
				echo '<option value="'.$k.'"'.($estado == $k ? ' selected="selected"' : '').'>'.$v."</option>\n";
				}?>
			</select>
		</td>

		<td>
			Região <br />
			<select name='regiao' size='1' class='frm' style='width:370px'>			
				<option value=''>Selecione uma Região</option>
				<option value='1' <? if ($regiao == 1) echo " SELECTED "; ?>>Estado de São Paulo </option>
				<option value='2' <? if ($regiao == 2) echo " SELECTED "; ?>>Sul (SC,RS e PR)</option>
				<option value='3' <? if ($regiao == 3) echo " SELECTED "; ?>>Sudeste (RJ, ES e MG)</option>
				<option value='4' <? if ($regiao == 4) echo " SELECTED "; ?>>Centro Oeste e Nordeste (GO, MS, MT, DF e CE, RN)</option>
				<option value='5' <? if ($regiao == 5) echo " SELECTED "; ?>>Nordeste (SE, AL, PE, PB e BA)</option>
				<option value='6' <? if ($regiao == 6) echo " SELECTED "; ?>>Norte e Nordeste (TO, PA, AP, RR, AM, AC, RO e MA, PI)</option>
			</select>
		</td>
	</tr>

	<tr>
		<td>
			Referência Produto <br />
			<input class="frm" type="text" name="produto_referencia" id="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>"  >&nbsp;
			<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript:  pesquisaProduto(document.frm_pesquisa.produto_referencia,'referencia'); " style='cursor: hand'>                    
		</td>

		<td>
			Descrição Produto <br />
			<input class="frm" type="text" name="produto_descricao" id="produto_descricao" size="35" value="<? echo $produto_descricao ?>">&nbsp;
			<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: pesquisaProduto(document.frm_pesquisa.produto_descricao,'descricao');"  style='cursor: pointer'>
		</td>
	</tr>	

	<tr>
		<td>
			CNPJ Revenda <br />
			<input class="frm" type="text" name="revenda_cnpj" size="16" maxlength="14" id="revenda_cnpj" value="<? echo $revenda_cnpj ?>" >&nbsp;
			<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: pesquisaRevenda(document.frm_pesquisa.revenda_cnpj, "cnpj");' style='cursor: pointer'>
		</td>

		<td>
            Nome Revenda <br />
            <input class="frm" type="text" name="revenda_nome" id="revenda_nome" size="35" maxlength="50" value="<? echo $revenda_nome ?>">&nbsp;            
                <img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: pesquisaRevenda (document.frm_pesquisa.revenda_nome, "nome");' style='cursor: pointer'> 
        </td>
	</tr>

	<tr>
		<td colspan='2' >
			Gerente <br />
			<input type='text' name='gerente' size='35' value='<?=$gerente?>' class='frm'>
		</td>
	</tr>

	<tr>
		<td colspan='2' align='center' style='padding-left:0px;'>
			<input type='submit' value='Pesquisar'>
		</td>
	</tr>
</table>
</form>

<?php

	if($_POST AND empty($msg_erro)){
		$sql = "SELECT tbl_os.os,
						tbl_os.sua_os,
						to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS abertura,
						to_char(tbl_os.data_conserto,'DD/MM/YYYY') AS data_conserto,
						to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento,
						tbl_os.serie,
						tbl_os.consumidor_nome,
						tbl_os.consumidor_cidade,
						tbl_os.consumidor_estado,
						tbl_os.consumidor_fone,
						tbl_os.revenda_nome,
						tbl_os.revenda_cnpj,
						tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome AS posto_nome,
						tbl_produto.referencia,
						tbl_produto.descricao,
						tbl_revenda_comercial.nome_gerente,
						tbl_revenda_comercial.nome_representante
					FROM tbl_os
					JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = 45
					JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto AND tbl_produto.fabrica_i = 45
					LEFT JOIN tbl_revenda_comercial ON tbl_os.revenda = tbl_revenda_comercial.revenda AND tbl_revenda_comercial.fabrica = 45
					WHERE tbl_os.fabrica = 45
					AND tbl_os.data_abertura BETWEEN '$aux_data_inicial' and '$aux_data_final'
					$cond";
		$resxls = pg_query($con,$sql);

		if(pg_num_rows($resxls) > 0){

			$tabela = "
						<table align='center' class='tabela'>
							<tr bgcolor='#596d9b'>
								<th align='center'><font color='#FFFFFF'><b>OS</b></font></th>
								<th align='center'><font color='#FFFFFF'><b>Produto</b></font></th>
								<th align='center'><font color='#FFFFFF'><b>Série</b></font></th>
								<th align='center'><font color='#FFFFFF'><b>Data Abertura</b></font></th>
								<th align='center'><font color='#FFFFFF'><b>Data Conserto</b></font></th>
								<th align='center'><font color='#FFFFFF'><b>Data Fechamento</b></font></th>
								<th align='center'><font color='#FFFFFF'><b>Posto</b></font></th>
								<th align='center'><font color='#FFFFFF'><b>Consumidor</b></font></th>
								<th align='center'><font color='#FFFFFF'><b>Telefone</b></font></th>
								<th align='center'><font color='#FFFFFF'><b>Cidade</b></font></th>
								<th align='center'><font color='#FFFFFF'><b>Estado</b></font></th>
								<th align='center'><font color='#FFFFFF'><b>Revenda</b></font></th>
								<th align='center'><font color='#FFFFFF'><b>Gerente</b></font></th>
								<th align='center'><font color='#FFFFFF'><b>Representante</b></font></th>
							</tr>
							";

			for($x =0;$x<pg_num_rows($resxls);$x++) {

				$os             	= trim(pg_fetch_result($resxls,$x,os));
				$sua_os             = trim(pg_fetch_result($resxls,$x,sua_os));
				$abertura           = trim(pg_fetch_result($resxls,$x,abertura));
				$fechamento         = trim(pg_fetch_result($resxls,$x,fechamento));
				$data_conserto      = trim(pg_fetch_result($resxls,$x,data_conserto));
				$serie              = trim(pg_fetch_result($resxls,$x,serie));
				$consumidor_nome    = trim(pg_fetch_result($resxls,$x,consumidor_nome));
				$consumidor_fone    = trim(pg_fetch_result($resxls,$x,consumidor_fone));
				$consumidor_cidade  = trim(pg_fetch_result($resxls,$x,consumidor_cidade));
				$consumidor_estado  = trim(pg_fetch_result($resxls,$x,consumidor_estado));
				$codigo_posto       = trim(pg_fetch_result($resxls,$x,codigo_posto));
				$posto_nome         = trim(pg_fetch_result($resxls,$x,posto_nome));
				$produto_referencia = trim(pg_fetch_result($resxls,$x,referencia));
				$produto_descricao  = trim(pg_fetch_result($resxls,$x,descricao));
				$revenda_cnpj  		= trim(pg_fetch_result($resxls,$x,revenda_cnpj));
				$revenda_nome  		= trim(pg_fetch_result($resxls,$x,revenda_nome));
				$nome_gerente  		= trim(pg_fetch_result($resxls,$x,nome_gerente));
				$nome_representante = trim(pg_fetch_result($resxls,$x,nome_representante));

				$cor   = ($x % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

				$tabela .= "							 
							<tr bgcolor='".$cor."' rel='".$os."'>
								<td class='toggle' nowrap>".$sua_os."</td>
								<td align='left' nowrap>".$produto_referencia . " - " . $produto_descricao."</td>
								<td align='left'>".$serie."</td>
								<td>".$abertura."</td>
								<td>".$data_conserto."</td>
								<td>".$fechamento."</td>
								<td align='left' nowrap>".$codigo_posto . " - " . $posto_nome."</td>
								<td align='left' nowrap>".$consumidor_nome."</td>
								<td nowrap>".$consumidor_fone."</td>
								<td align='left'>".$consumidor_cidade."</td>
								<td>".$consumidor_estado."</td>
								<td align='left' nowrap>".$revenda_cnpj . " - " . $revenda_nome."</td>
								<td align='left' nowrap>".$nome_gerente."</td>
								<td align='left' nowrap>".$nome_representante."</td>
							</tr>
							";				
			}
			$tabela .= "</tabela>";

			$data         = date ("Y-m-d");
			$arquivo_nome = "xls/relatorio-gerencial-os-$login_fabrica-$data.xls";

			echo $tabela;

			$fp = fopen($arquivo_nome,"w");
			fwrite($fp,$tabela);
			fclose($fp);

			$resposta .= "<br>";
			$resposta .= "<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
			$resposta .= "<tr>";
			$resposta .= "<td colspan=\"$colspan_excel\" style='border: 0; font: bold 14px \"Arial\";'><a href=\"$arquivo_nome\" target=\"_blank\" style=\"text-decoration: none; \"><img src=\"imagens/excel.png\" height=\"20px\" width=\"20px\" align=\"absmiddle\">&nbsp;&nbsp;&nbsp;Gerar Arquivo Excel</a></td>";
			$resposta .= "";
			$resposta .= "</tr>";
			$resposta .= "</table>";
			echo $resposta;
			echo "<br/>";

		}
	}
	include "rodape.php";
?>



			
				
			