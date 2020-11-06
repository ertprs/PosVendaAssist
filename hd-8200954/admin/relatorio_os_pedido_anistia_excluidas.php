<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0

include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="auditoria";
include "autentica_admin.php";

$observacoes_exclusao = array(
	'Anistia do pedido de peças',
	'Débito de peças. OS com pedido e mais de 150 dias',
	'Anistia do pedido de peças. OS com pedido e mais de 150 dias'
);

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];

	if (strlen($q)>2){
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

		if ($tipo_busca == "codigo"){
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
		}else{
			$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		}

		$res = pg_query($con,$sql);
		if (pg_num_rows ($res) > 0) {
			for ($i=0; $i<pg_num_rows ($res); $i++ ){
				$cnpj = trim(pg_fetch_result($res,$i,cnpj));
				$nome = trim(pg_fetch_result($res,$i,nome));
				$codigo_posto = trim(pg_fetch_result($res,$i,codigo_posto));
				echo "$cnpj|$nome|$codigo_posto";
				echo "\n";
			}
		}
	}
	exit;
}
$btn_acao = trim($_POST['btn_acao']);
if ($btn_acao == 'Pesquisar'){
    $data_inicial = $_REQUEST["data_inicial"];
    $data_final = $_REQUEST["data_final"];
    
    if(empty($data_inicial) OR empty($data_final)){
        $msg_erro = "Data Inválida";
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
//    if(strlen($msg_erro)==0){
//        if(strtotime($aux_data_final) < strtotime($aux_data_inicial) 
//        or strtotime($aux_data_final) > strtotime('today')){
//            $msg_erro = "Data Inválida.";
//        }
//    }
        
	if(strlen($msg_erro)==0){
    	if (strtotime($aux_data_inicial.'+3 month') < strtotime($aux_data_final) ) {
            $msg_erro = 'O intervalo entre as datas não pode ser maior que 3 meses';
        }
    }

}





if (isset($_POST['debitar_os'])) {
	try {
		$os = intval($_POST['debitar_os']);
		$valor = floatval($_POST['valor']);
		$valor = ($valor > 0) ? $valor * -1 : $valor;
		@$res = pg_query($con, "BEGIN");
		if (pg_last_error($con)) throw new Exception(pg_last_error($con), 1);
		
		$sql = "
		INSERT INTO tbl_extrato_lancamento (
		posto,
		fabrica,
		lancamento,
		descricao,
		debito_credito,
		valor,
		admin
		)
		
		SELECT
		tbl_os.posto,
		tbl_os.fabrica,
		190,
		'OS aberta há mais de 150 dias e posto comunicado',
		'D',
		{$valor},
		{$login_admin}
		
		FROM
		tbl_os
		
		WHERE
		tbl_os.fabrica = {$login_fabrica}
		AND tbl_os.os = {$os}
		";
		@$res = pg_query($con, $sql);
		if (pg_last_error($con)) throw new Exception(pg_last_error($con), 1);
		
		@$res = pg_query($con, "SELECT CURRVAL('seq_extrato_lancamento')");
		if (pg_last_error($con)) throw new Exception(pg_last_error($con), 1);
		$extrato_lancamento = pg_result($res, 0, 0);
		
		$sql = "
		INSERT INTO tbl_extrato_lancamento_os(
		extrato_lancamento,
		os
		)
		
		VALUES(
		{$extrato_lancamento},
		{$os}
		)
		";
		@$res = pg_query($con, $sql);
		if (pg_last_error($con)) throw new Exception(pg_last_error($con), 1);
		
		@$res = pg_query($con, "COMMIT");
		if (pg_last_error($con)) throw new Exception(pg_last_error($con), 1);
		
		echo "ok";
	}
	catch (Exception $e) {
		$res = pg_query("ROLLBACK");
		
		switch ($e->getCode()) {
			case 1:
				$mensagem = "Erro no banco de dados <erro=" . $e->getMessage() . ">";
				break;
				
			default:
				$mensagem = $e->getMessage();
		}
		
		echo "falha|{$mensagem}";
	}
	
	die;
}

$btn_acao = trim($_POST['btn_acao']);
if ($btn_acao == 'Pesquisa'){
    $codigo_posto = $_POST["posto_codigo"];

    if(!empty($codigo_posto)){
    	$sql = "
    	SELECT
    	posto
    	
    	FROM
    	tbl_posto_fabrica ON tbl_posto.posto=tbl_posto_fabrica.posto
    	
    	WHERE
    	tbl_posto_fabrica.fabrica={$login_fabrica}
    	AND tbl_posto.codigo_posto='{$codigo_posto}'
    	";
        $res = pg_query($con, $sql);
        if (pg_num_rows($res) == 1) {
            $posto = trim(pg_fetch_result($res,0,'posto'));
        }
        else {
            $msg_erro = "Posto não encontrado.";
        }
    }
}

$layout_menu = "auditoria";
$title = strtoupper("OS ABERTAS HÁ MAIS DE 150 DIAS EXCLUÍDAS");
    
include "cabecalho.php";
?>


<? include "javascript_calendario.php"; ?>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script src="../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<script type='text/javascript' src='js/dimensions.js'></script>
<script type="text/javascript">
	$(function(){
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$(".porcento_debitar").maskedinput("?999", {placeholder: " "});
	});

    $(document).ready(function() {
        Shadowbox.init();

        function formatItem(row) {
            return row[2] + " - " + row[1];
        }

        function formatResult(row) {
            return row[0];
        }

        /* Busca pelo Código */
        $("#posto_codigo").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
            minChars: 3,
            delay: 150,
            width: 350,
            matchContains: true,
            formatItem: formatItem,
            formatResult: function(row) {return row[2];}
        });

        $("#posto_codigo").result(function(event, data, formatted) {
            $("#posto_nome").val(data[1]) ;
        });

        /* Busca pelo Nome */
        $("#posto_nome").autocomplete("<?echo $PHP_SELF.'?busca=nome'; ?>", {
            minChars: 3,
            delay: 150,
            width: 350,
            matchContains: true,
            formatItem: formatItem,
            formatResult: function(row) {return row[1];}
        });

        $("#posto_nome").result(function(event, data, formatted) {
            $("#posto_codigo").val(data[2]) ;
            //alert(data[2]);
        });
        
        $('.detalhes').each(function() {
            $(this).click(function() {
                var os = $("input.os", $(this).parent()).val();

                if ($(".pecas" + os).css("display") == "none") {
                	$(".pecas" + os).css("display", "table-row");
	                $("img", $(this)).attr("src", "imagens/menos.gif");
                }
                else {
                	$(".pecas" + os).css("display", "none");
                    $("img", $(this)).attr("src", "imagens/mais.gif");
                }

            });
        });
    });

	function pesquisaPosto(campo,tipo){
		var campo = campo.value;

		if (jQuery.trim(campo).length > 2){
			Shadowbox.open({
				content:	"posto_pesquisa_2_nv.php?"+tipo+"="+campo+"&tipo="+tipo,
				player:	"iframe",
				title:		"Pesquisa Posto",
				width:	800,
				height:	500
			});
		}else
			alert("Informe toda ou parte da informação para realizar a pesquisa!");
	}

	function gravaDados(name, valor){
		try {
			$("input[name="+name+"]").val(valor);
		} catch(err){
			return false;
		}
	}

	function retorna_posto(codigo_posto, posto, nome, cnpj, cidade, estado, credenciamento, num_posto){
		gravaDados('posto_codigo',codigo_posto);
		gravaDados('posto_nome',nome);
	}
</script>

<?php
if(empty($msg_erro) && $btn_acao == "Pesquisar") {
	try {
		$codigo_posto = $_POST["posto_codigo"];
		
		if (!empty($codigo_posto)) {
			$cond_posto = "AND tbl_posto_fabrica.codigo_posto = '{$codigo_posto}'";
		}
		
//		$data_inicial = $_POST['data_inicial'];
//		$data_final = $_POST['data_final'];
		//TODO: Validar datas aqui. As datas são obrigatórias
		
		$data_inicial = implode('-', array_reverse(explode('/', $data_inicial)));
		$data_final = implode('-', array_reverse(explode('/', $data_final)));
		
        $sql = "
		SELECT
		tbl_os_status.observacao,
		tbl_posto_fabrica.codigo_posto,
		tbl_posto.nome,
		tbl_os.os,
		tbl_os.sua_os,
		TO_CHAR(tbl_os.data_digitacao, 'DD/MM/YYYY') AS data_digitacao,
		TO_CHAR(tbl_os_status.data, 'DD/MM/YYYY') AS data_exclusao,
		tbl_linha.nome AS linha,
		tbl_os.mao_de_obra
		
		FROM
		tbl_os_status
		JOIN tbl_os ON tbl_os_status.os = tbl_os.os
		JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
		JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha
		JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
		JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
		
		WHERE
		tbl_os_status.status_os = 15
		AND tbl_os_status.fabrica_status = {$login_fabrica}
		AND tbl_os_status.observacao IN ('" . implode("','", $observacoes_exclusao) . "')
		AND tbl_os.excluida IS TRUE
		AND tbl_os_status.data BETWEEN '{$data_inicial} 00:00:00' AND '{$data_final} 23:59:59'
		$cond_posto
		
		ORDER BY
		tbl_os_status.observacao,
		tbl_posto.nome,
		tbl_posto_fabrica.codigo_posto
		";
        $res = @pg_query($con, $sql);
        
        if (pg_last_error($con)) {
        	throw new Exception("Erro na consulta <error=" . pg_last_error($con) . ">");
        }
        
        if(pg_num_rows($res) == 0){
        	throw new Exception("Não existem OS abertas a mais de 150 dias excluídas");
        	$msg_erro = "Não existem OS abertas a mais de 150 dias excluídas";
        }
        
        $ultimo_codigo_posto = "";
        $ultima_observacao = "";
        $total_mo = 0;
        $total_pecas_debitadas = 0;
        $contagem = 0;
        
        for ($i = 0; $i < pg_num_rows($res); $i++) {
        	extract(pg_fetch_array($res));
        	
        	if ($observacao != $ultima_observacao) {
        		if (!empty($ultima_observacao)) {
			        $table_result .= "
			        	</table>
		            </td>
		        </tr>
	        	<tr class='titulo_tabela seletor_posto''>
	        		<td>TOTAL</td>
	        	</tr>
	            <tr>
	            	<td>
				        <table width='680' border='0' cellspacing='1' cellpadding='0' class='formulario tabela' align='center'>
					        <tr class='titulo_tabela'>
					        	<td width='210' align='center'>TOTAL DE OS</td>
					        	<td width='249'></td>
					        	<td width='70'>Mão de Obra</td>
					        	<td width='70'>Total Peças</td>
					        	<td width='70'>Debitado</td>
					        </tr>
	        	
			        		<tr bgcolor='#F7F5F0'>
			        			<td align='center'>
			        				{$contagem}
			        			</td>
			        			<td></td>
			        			<td align='center'>
			        				{$total_mo}
			        			</td>
			        			<td align='center'>
			        				{$total_pecas_geral}
			        			</td>
			        			<td align='center'>
			        				{$total_pecas_debitadas}
			        			</td>
			        		</tr>
			        	</table>
		            </td>
		        </tr>
		   </table>";
        			$ultimo_codigo_posto = "";
			        $total_mo = 0;
			        $total_pecas_debitadas = 0;
				$total_pecas_geral = 0;
			        $contagem = 0;
        		}
        $table_result .= "
        <br>
        	<table width='700' border='0' cellspacing='1' cellpadding='0' class='formulario' align='center'>
        		<tr class='titulo_tabela titulo_tipo'>
        			<td colspan='8'>{$observacao}</td>
        		</tr>";
        		
        	}
        	
        	//$codigo_posto = str_pad($codigo_posto, 6, "0", STR_PAD_LEFT);
        	if ($codigo_posto != $ultimo_codigo_posto) {
        		if ($ultimo_codigo_posto != "") {
			        $table_result .= "
			        	</table>
		            </td>
		        </tr>";
        		}
        		
        		
        		
	        	$table_result .= "
	        	<tr class='titulo_tabela seletor_posto' posto='{$posto}'>
	        		<td>{$codigo_posto} - {$nome}</td>
	        	</tr>";
	
	        	$table_result .= "
	            <tr>
	            	<td id='td{$posto}'>
				        <table width='680' border='0' cellspacing='1' cellpadding='0' class='formulario tabela' align='center'>
					        <tr class='titulo_tabela'>
					        	<td width='20'></td>
					        	<td width='190'>Linha</td>
					        	<td width='100'>OS</td>
					        	<td width='70'>Digitação</td>
					        	<td width='70'>Exclusão</td>
					        	<td width='70'>Mão de Obra</td>
					        	<td width='70'>% Debitado</td>
					        	<td width='70'>Debitado</td>
					        </tr>";
        	}
        	
        	$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
        	
        	$sql = "
        	SELECT
        	COALESCE(SUM(valor), 0) AS debitado
        	
        	FROM
        	tbl_extrato_lancamento
        	JOIN tbl_extrato_lancamento_os ON tbl_extrato_lancamento.extrato_lancamento = tbl_extrato_lancamento_os.extrato_lancamento
        	
        	WHERE
        	tbl_extrato_lancamento_os.os = {$os}
        	";
        	$res_lancamento = pg_query($con, $sql);
        	
	        if (pg_last_error($con)) {
	        	throw new Exception("Erro na consulta <error=" . pg_last_error($con) . ">");
	        }
	        
	        extract(pg_fetch_assoc($res_lancamento));
	        $total_mo += $mao_de_obra;
	        $total_pecas_debitadas += $debitado;
	        $contagem++;
	        
        	$dados_os = "
				        	<tr bgcolor='{$cor}' class='linha'>
				        		<td align='center' class='detalhes' os='{$os}'><img src='imagens/mais.gif' /></td>
				        		<td align='center'>{$linha}</td>
				        		<td align='center'><a href='os_press.php?os={$os}' target='_blank' class='sua_os'>{$sua_os}</a><input type='hidden' value='{$os}' class='os os{$os}'/></td>
				        		<td align='center'>{$data_digitacao}</td>
				        		<td align='center'>{$data_exclusao}</td>
				        		<td align='center'>{$mao_de_obra}</td>
				        		<td align='center'>[porcento_debitado]</td>
				        		<td align='center'>{$debitado}</td>
				        	</tr>";
        	
        	$sql = "
			SELECT
			tbl_peca.referencia || ' - ' || tbl_peca.descricao AS peca_descricao,
			tbl_tabela_item.preco
			
			FROM
			tbl_os
			JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
			JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
			JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca
			JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
			JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_os.posto AND tbl_posto_linha.linha = tbl_produto.linha
			JOIN tbl_tabela_item ON tbl_tabela_item.tabela = tbl_posto_linha.tabela AND tbl_tabela_item.peca = tbl_os_item.peca
			
			WHERE
			tbl_os_produto.os = {$os}
			
			ORDER BY
			tbl_peca.referencia
        	";
        	$res_pecas = @pg_query($con, $sql);
        	
	        if (pg_last_error($con)) {
	        	throw new Exception("Erro na consulta <error=" . pg_last_error($con) . ">");
	        }
	        
	        $dados_os .= "
				        	<tr class='dados_pecas pecas{$os}'>
				        		<td colspan='8'>
				        			<table width='600' border='0' cellspacing='1' cellpadding='0' class='formulario tabela' align='center'>
				        				<tr class='titulo_tabela'>
				        					<td width='360'>Peça</td>
				        					<td width='235'>Preço</td>
				        				</tr>";
	        
	        $total_pecas = 0;
	        
	        for ($j = 0; $j < pg_num_rows($res_pecas); $j++) {
	        	$cor = ($j % 2) ? "#F7F5F0" : "#F1F4FA";
	        	extract(pg_fetch_assoc($res_pecas));
	        	
	        	$total_pecas += $preco;
	        	$total_pecas_geral += $preco;
	        	
	        	$dados_os .= "
				        				<tr bgcolor='{$cor}'>
				        					<td align='center'>{$peca_descricao}</td>
				        					<td align='center' class='valor_original'>{$preco}</td>
				        				</tr>"; 
	        }
	        
	        $porcento_debitado = number_format($debitado / $total_pecas * 100, 2);
	        
	        $dados_os = str_replace("[porcento_debitado]", $porcento_debitado, $dados_os);
	        
	        $dados_os .= "
	        						</table>
	        					</td>
	        				</tr>";
	        
	        $table_result .= $dados_os;
	        
        	$ultimo_codigo_posto = $codigo_posto;
        	$ultima_observacao = $observacao;
        }
        
			        $table_result .= "
			        	</table>
		            </td>
		        </tr>
	        	<tr class='titulo_tabela seletor_posto''>
	        		<td>TOTAL</td>
	        	</tr>
	            <tr>
	            	<td>
				        <table width='680' border='0' cellspacing='1' cellpadding='0' class='formulario tabela' align='center'>
					        <tr class='titulo_tabela'>
					        	<td width='210'>TOTAL DE OS</td>
					        	<td width='249'></td>
					        	<td width='70'>Mão de Obra</td>
					        	<td width='70'>Total Peças</td>
					        	<td width='70'>Debitado</td>
					        </tr>
	        	
			        		<tr bgcolor='#F7F5F0'>
			        			<td align='center'>
			        				{$contagem}
			        			</td>
			        			<td></td>
			        			<td align='center'>
			        				{$total_mo}
			        			</td>
			        			<td align='center'>
			        				{$total_pecas_geral}
			        			</td>
			        			<td align='center'>
			        				{$total_pecas_debitadas}
			        			</td>
			        		</tr>
			        	</table>
		            </td>
		        </tr>
		        </table>";        
	}
	catch (Exception $e) {
    	$msg_erro = $e->getMessage();
	}
}
?>



<style type="text/css">
	table.tabela tr td{
	    font-family: verdana;
	    font-size: 11px;
	    border-collapse: collapse;
	    border:1px solid #596d9b;
	}
	
	.titulo_tipo td {
		padding: 10px;
	}

    .titulo_tabela{
        background-color:#596d9b;
        font: bold 14px "Arial";
        color:#FFFFFF;
        text-align:center;	
        padding: 2px;
    }

    .titulo_coluna{
        background-color:#95A4C6;
        font: bold 11px "Arial";
        color:#FFFFFF;
        text-align:center;
    }

    .titulo_coluna_2{
        background-color:#B8C2D8;
        font: bold 11px "Arial";
        color:#FFFFFF;
        text-align:center;    
    }

    .msg_erro{
        background-color:#FF0000;
        font: bold 14px "Arial";
        color:#FFFFFF;
        text-align:center;
        padding: 3px 0;
        margin: 0 auto;
        width: 700px;
    }

    .formulario{
        background-color:#D9E2EF;
        font:11px Arial;
        text-align:left;
    }

    table.tabela tr td{
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
        border-collapse: collapse;
        border:1px solid #596d9b;
    }

    .no-result{
        padding: 20px; 
        text-align: center;
        font-size: 18px;
    }
    
    .seletor_posto {
    text-align: left;
    text-transform: uppercase;
    }
    
    .seletor_posto>td, .dados_posto>td{
    padding: 2px;
    }
    
    .dados_pecas, .dados_comunicados {
    display: none;
    }
    
    .dados_posto td {
    padding: 5px;
    text-align:center;
    }
    
    .detalhes {
    cursor: pointer;
    }
    

    #msg{ width:700px; margin:auto; }
</style>

<form name="frm_relatorio" method="post" action="<? echo $PHP_SELF ?>">
    <input type="hidden" name="btn_acao" value='PesquisaDados' />
    <?php
        if (!empty($msg_erro))
            echo "<div class='msg_erro'>{$msg_erro}</div>";
    ?>
    <table width="700" align="center" border="0" cellspacing='1' cellpadding='2' class='formulario'>
        <tbody>
            <tr>
                <td class="titulo_tabela" colspan="4">Parâmetros de Pesquisa</td>
            </tr>
            <tr>
                <td width='100px'>&nbsp;</td>
                <td width='250px'>&nbsp;</td>
                <td width='250px'>&nbsp;</td>
                <td width='100px'>&nbsp;</td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td nowrap>
                    Código do Posto<br>
                    <input class="frm" type="text" name="posto_codigo"  id="posto_codigo" style='width: 200px;' value="<? echo $posto_codigo ?>" />
                    <img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: pesquisaPosto (document.frm_relatorio.posto_codigo, 'codigo');" />
                </td>

                <td nowrap>
                    Nome do Posto<br>
                    <input class="frm" type="text" name="posto_nome" id="posto_nome" style='width: 200px;' value="<? echo $posto_nome ?>" />
                    <img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: pesquisaPosto (document.frm_relatorio.posto_nome, 'nome');" style="cursor:pointer;" />
                </td>
                <td>&nbsp;</td>
            </tr>
			<tr>
                <td>&nbsp;</td>
				<td>
					Data Inicial<br>
					<input type="text" name="data_inicial" id="data_inicial" size="11" maxlength="10" value="<? echo $_POST['data_inicial'] ?>" class="frm">
				</td>
				<td>
					Data Final<br>
					<input type="text" name="data_final" id="data_final" size="11" maxlength="10" value="<? echo $_POST['data_final'] ?>" class="frm">
				</td>
                <td>&nbsp;</td>
			</tr>
            <tr>
                <td colspan="4" style="padding: 20px; text-align: center">
                    <input type="submit" name="btn_acao" value=" Pesquisar " />
                </td>
            </tr>
        </tbody>
    </table>
</form>
<br />


<?php 
echo $table_result;
?>

<br /><br />
<?php include "rodape.php"; ?>
