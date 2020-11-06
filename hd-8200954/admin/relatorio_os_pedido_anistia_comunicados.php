<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0

include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="auditoria";
include "autentica_admin.php";

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

if (isset($_REQUEST['debitar_os'])) {
	try {
		$os = intval($_REQUEST['debitar_os']);
		$valor = floatval($_REQUEST['valor']);
		$valor = ($valor > 0) ? $valor * -1 : $valor;
		@$res = pg_query($con, "BEGIN");
		if (pg_last_error($con)) throw new Exception(pg_last_error($con), 1);
		
		$pecas = json_decode(stripslashes($_REQUEST['pecas']));
		
		$align = "align='center'";
		$dados = "
		<table cellspacing='1' style='font-family: verdana; font-size: 11px; border-collapse: collapse; border:1px solid #596d9b;'>
			<tr style='	background-color:#596d9b; font: bold 11px Arial; color:#FFFFFF; text-align:center;'>
				<td {$align}>Peça</td>
				<td {$align}>Valor Debitado</td>
			</tr>";
		
		foreach ($pecas as $peca) {
			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
			
			$dados .= "
			<tr bgcolor='{$cor}'>
				<td {$align}>{$peca->peca}</td>
				<td {$align}>{$peca->valor}</td>
			</tr>";
		}
		
		$dados .= "
		</table>";

		$historico = "OS {$_REQUEST['sua_os']} aberta há mais de 150 dias e posto comunicado. Valores debitados:<br>{$dados}";
		$historico = str_replace("'", "''", $historico);
		$historico = str_replace("\n", "", $historico);
		
		$sql = "
		INSERT INTO tbl_extrato_lancamento (
		posto,
		fabrica,
		lancamento,
		descricao,
		historico,
		debito_credito,
		valor,
		admin
		)
		
		SELECT
		tbl_os.posto,
		tbl_os.fabrica,
		190,
		'OS aberta há mais de 150 dias e posto comunicado',
		'{$historico}',
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
		
		$mensagem = "Conforme comunicado anterior lido pelo Sr.ª {$_REQUEST['leitor']}, as peças da OS {$_REQUEST['sua_os']} (digitada em {$_REQUEST['data_digitacao']}, aberta há {$_REQUEST['qtde_dias']}), foram enviadas em garantia conforme solicitado porém não foi comprovado o atendimento. Estes valores não serão estornados, caso tenha alguma dúvida favor enviar um e-mail para auditoria.at@britania.com.br<br>
		<br>
		{$dados}";
		
		$mensagem = str_replace("'", "''", $mensagem);
		$mensagem = str_replace("\n", "", $mensagem);
		
		$sql = "
		INSERT INTO tbl_comunicado(
		mensagem,
		tipo,
		fabrica,
		obrigatorio_site,
		descricao,
		posto,
		ativo
		)
		
		VALUES(
		'{$mensagem}',
		'Comunicado',
		{$login_fabrica},
		true,
		/* Não mudar a linha abaixo, é usada como índice para localizar comunicados deste processo */
		'Débito de peças de OS 150 dias com pedido',
		(SELECT posto FROM tbl_os WHERE os={$os}),
		true
		)
		";
		@$res = pg_query($con, $sql);
		if (pg_last_error($con)) throw new Exception(pg_last_error($con), 1);
		
		@$res = pg_query($con, "SELECT CURRVAL('seq_comunicado')");
		if (pg_last_error($con)) throw new Exception(pg_last_error($con), 1);
		$comunicado = pg_result($res, 0, 0);
		
		$sql = "
		INSERT INTO tbl_os_comunicado(
		os,
		comunicado
		)
		
		VALUES(
		{$os},
		{$comunicado}
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
$title = strtoupper("OS ABERTAS HÁ MAIS DE 150 DIAS COM COMUNICADO AO POSTO");
    
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
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
		$(".porcento_debitar").maskedinput("?999", {placeholder: " "});
	});

	function excluir_os(os, sua_os, obs_exclusao, botao) {
        $.ajax({
            type: 'POST',
        	url: 'os_cadastro.php',
        	data: {
    			ajax: 'sim',
        		btn_acao: 'apagar',
        		obs_exclusao: obs_exclusao,
        		os: os
        	},
        	success: function(response) {
	            $(botao).val($(botao).attr('oldVal'));
	            $(botao).attr('disabled', false);
	            
            	if (response == 'ok') {
	            	// Todos os botões da mesma OS devem sumir
	            	$("input.anistia, input.debitar", $(botao).parent().parent()).each(function(){
		            	$(this).css("display", "none");
		        	});
	            	$("input", $(".pecas" + os)).each(function() {
		            	$(this).css("display", "none");
		            });
		            
					if ($(botao).val() == "Debitar") {
						var total = $(botao).attr('total');
						var data_digitacao = $('.data_digitacao' + os).html();
						var qtde_dias = $('.qtde_dias' + os).html();
						var leitor = $('.leitor' + os).html();
						var pecas = [];
						var i = 0;

						$(".detalhe_peca", $(".pecas" + os)).each(function() {
							var peca = $(".peca_descricao", $(this)).html();
							var valor = $(".valor_debitar", $(this)).html();
							pecas[i] = '{"peca": "'+peca+'", "valor": '+valor+'}';
							i++;
						});

						pecas = "[" + pecas.join(",") + "]";
		
						$.ajax({
							type: 'POST',
							url: '<?php echo $PHP_SELF; ?>',
							data: {
								debitar_os: os,
								sua_os: sua_os,
								valor: total,
								data_digitacao: data_digitacao,
								qtde_dias: qtde_dias,
								leitor: leitor,
								pecas: pecas
							},
							success: function(response) {
								$(botao).val('Debitar');
								$(botao).attr('disabled', false);
		
								if (response == 'ok') {
									alert('Lançamento efetuado para o próximo extrato.\nOS: ' + sua_os + '\nValor:' + total);
								}
								else {
									response = response.split('|');
									alert(response[1]);
								}
							}
						});
					}
	            	alert('OS ' + sua_os + ' excluída com sucesso');
            	}
            	else {
	            	response = response.split('|');

			switch($(botao).val()) {
				case 'Debitar':
					var mensagem = 'Não foi possível debitar a OS\n\nMotivo: ' + response[1];
					break;

				case 'Anistia':
					var mensagem = 'Não foi possível dar anisitia para OS\n\nMotivo: ' + response[1];
					break;

				default:
					var mensagem = response[1];
			}

	            	alert(mensagem);
            	}
            }
        });
	}

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

        $('.ver_posto').click(function() {
            var posto = $(this).attr('rel');
            $("#"+posto).slideToggle("fade");
        });

        $('.detalhes').each(function() {
            $(this).click(function() {
                var os = $("input.os", $(this).parent()).val();

                if ($(".pecas" + os).css("display") == "none") {
                	$(".pecas" + os).css("display", "table-row");
                	$(".comunicados" + os).css("display", "table-row");
                    $("img", $(this)).attr("src", "imagens/menos.gif");
                }
                else {
                	$(".pecas" + os).css("display", "none");
                	$(".comunicados" + os).css("display", "none");
                    $("img", $(this)).attr("src", "imagens/mais.gif");
                }

            });
        });

        $('.porcento_debitar').each(function() {
            $(this).keyup(function() {
                var valor_original = $('.valor_original', $(this).parent().parent()).html();
                var porcento_debitar = $(this).val();
                var valor_debitar = valor_original * porcento_debitar / 100;
                $('.valor_debitar', $(this).parent().parent()).html(valor_debitar.toFixed(2));
            });
            $(this).blur(function() {
                if ($(this).val() == "") {
                    $(this).val("0");
                    $('.valor_debitar', $(this).parent().parent()).html("0.00");
                }
            });
        });

        $(".anistia").each(function() {
            $(this).click(function() {
	            var botao = this;
	            var os = $(".os", $(botao).parent().parent()).val();
	            var sua_os = $(".sua_os", $(botao).parent().parent()).html();
	            
	            if (confirm('Excluir a OS '+sua_os+' e dar anistia ao pedido de peças?')) {
			    $(botao).attr('oldVal', $(botao).val());
		            $(botao).val('Aguarde...');
		            $(botao).attr('disabled', true);

		            excluir_os(os, sua_os, 'Anistia do pedido de peças. OS com pedido e mais de 150 dias', botao);
	            }
	        });
        });

        $(".debitar").each(function() {
        	$(this).click(function () {
	            var botao = this;
	            var os = $(".os", $(botao).parent().parent()).val();
	            var sua_os = $(".sua_os", $(botao).parent().parent()).html();
	            var total = 0;

	            $(".valor_debitar", $(".pecas" + os)).each(function() {
	                total += parseFloat($(this).html());
	            });

	            total = total.toFixed(2);
	
	            if (confirm("Debitar do posto o valor de " + total + ", referente à OS " + sua_os + " no próximo extrato?")) {
			    $(botao).attr('oldVal', $(botao).val());
		            $(botao).val('Aguarde...');
		            $(botao).attr('disabled', true);
			    $(botao).attr('total', total);
			    excluir_os(os, sua_os, 'Débito de peças. OS com pedido e mais de 150 dias', botao);
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
		
        $sql = "
		SELECT
		tbl_os.posto,
		tbl_posto_fabrica.codigo_posto,
		tbl_posto.nome,
		tbl_linha.nome AS linha,
		tbl_os.os,
		tbl_os.sua_os,
		tbl_os.consumidor_revenda,
		TO_CHAR(tbl_os.data_digitacao, 'DD/MM/YYYY') AS data_digitacao,
		(CURRENT_DATE - tbl_os.data_digitacao::date) AS qtde_dias,
		COUNT(tbl_comunicado.comunicado) AS qtde_comunicado
		
		FROM
		tbl_os
		JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
		JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha
		JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
		JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto
		AND tbl_posto_fabrica.fabrica = tbl_os.fabrica AND tbl_posto_fabrica.fabrica=tbl_os.fabrica
		JOIN tbl_posto_linha ON tbl_posto_linha.posto=tbl_os.posto AND tbl_posto_linha.linha = tbl_produto.linha
		JOIN tbl_os_comunicado ON tbl_os.os = tbl_os_comunicado.os
		JOIN tbl_comunicado ON tbl_os_comunicado.comunicado = tbl_comunicado.comunicado AND tbl_comunicado.descricao = 'OS 150 dias com pedido'
		
		WHERE
		tbl_os.fabrica = {$login_fabrica}
		AND tbl_os.os_fechada IS FALSE
		AND tbl_os.excluida IS NOT TRUE
		AND tbl_os.data_digitacao < CURRENT_TIMESTAMP - INTERVAL '150 day'
		AND tbl_os.data_digitacao > (SELECT MIN(data_digitacao) FROM tbl_os WHERE fabrica=3 AND os_fechada IS FALSE)
		AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
		$cond_posto
		
		GROUP BY
		tbl_os.posto,
		tbl_posto_fabrica.codigo_posto,
		tbl_posto.nome,
		tbl_posto.posto,
		tbl_linha.nome,
		tbl_os.os,
		tbl_os.sua_os,
		tbl_os.consumidor_revenda,
		tbl_os.data_digitacao
		
		ORDER BY
		tbl_posto.nome,
		tbl_posto.posto
		";
        $res = @pg_query($con, $sql);
        
        if (pg_last_error($con)) {
        	throw new Exception("Erro na consulta <error=" . pg_last_error($con) . ">");
        }
        
        if(pg_num_rows($res) == 0){
        	throw new Exception("Não existem OS abertas a mais de 150 dias com comunicados");
        }
        
        $table_result .="
        <br>
        	<table width='700' border='0' cellspacing='1' cellpadding='0' class='formulario' align='center'>";
        
        $ultimo_codigo_posto = "";
        
        for ($i = 0; $i < pg_num_rows($res); $i++) {
        	extract(pg_fetch_array($res));
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
					        	<td width='200'>Linha</td>
					        	<td width='100'>OS</td>
					        	<td width='30'>C/R</td>
					        	<td width='70'>Digitação</td>
					        	<td width='40'>Dias</td>
					        	<td width='200'>Ações</td>
					        </tr>";
        	}
        	
        	$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
        	
        	$table_result .= "
				        	<tr bgcolor='{$cor}' class='linha'>
				        		<td align='center' class='detalhes' os='{$os}'><img src='imagens/mais.gif' /></td>
				        		<td align='center'>{$linha}</td>
				        		<td align='center'><a href='os_press.php?os={$os}' target='_blank' class='sua_os'>{$sua_os}</a><input type='hidden' value='{$os}' class='os os{$os}'/></td>
				        		<td align='center'>{$consumidor_revenda}</td>
				        		<td align='center' class='data_digitacao{$os}'>{$data_digitacao}</td>
				        		<td align='center' class='qtde_dias{$os}'>{$qtde_dias}</td>
				        		<td align='center'>
				        			<input type='button' value='Anistia' class='anistia' class='frm' />
				        			<input type='button' value='Debitar' class='debitar' class='frm' />
				        		</td>
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
	        
	        $table_result .= "
				        	<tr class='dados_pecas pecas{$os}'>
				        		<td colspan='7'>
				        			<table width='600' border='0' cellspacing='1' cellpadding='0' class='formulario tabela' align='center'>
				        				<tr class='titulo_tabela'>
				        					<td width='60%'>Peça</td>
				        					<td width='15%'>Preço</td>
				        					<td width='15%'>Valor Debitar</td>
				        					<td width='10%'>% Debitar</td>
				        				</tr>";
	        
	        for ($j = 0; $j < pg_num_rows($res_pecas); $j++) {
	        	$cor = ($j % 2) ? "#F7F5F0" : "#F1F4FA";
	        	extract(pg_fetch_assoc($res_pecas));
	        	
	        	$table_result .= "
				        				<tr bgcolor='{$cor}' class='detalhe_peca'>
				        					<td align='center' class='peca_descricao'>{$peca_descricao}</td>
				        					<td align='center' class='valor_original'>{$preco}</td>
				        					<td align='center' class='valor_debitar'>{$preco}</td>
				        					<td align='center'><input type='text' value='100' class='frm porcento_debitar' size='4' /></td>
				        				</tr>"; 
	        }
	        
	        $table_result .= "
	        						</table>
	        					</td>
	        				</tr>";
	        
	        $sql = "
	        SELECT
	        tbl_os_comunicado.comunicado,
	        tbl_comunicado_posto_blackedecker.leitor,
		TO_CHAR(tbl_comunicado_posto_blackedecker.data_confirmacao, 'DD/MM/YYYY') AS data_confirmacao
	        
	        FROM
	        tbl_os_comunicado
	        LEFT JOIN tbl_comunicado_posto_blackedecker ON tbl_os_comunicado.comunicado = tbl_comunicado_posto_blackedecker.comunicado
	        
	        WHERE
	        tbl_os_comunicado.os = {$os}
	        ";
	        $res_comunicado = @pg_query($con, $sql);
	        
	        if (pg_last_error($con)) {
	        	throw new Exception("Erro na consulta <error=" . pg_last_error($con) . ">");
	        }
	        
	        $table_result .= "
				        	<tr class='dados_comunicados comunicados{$os}'>
				        		<td colspan='7'>
				        			<table width='600' border='0' cellspacing='1' cellpadding='0' class='formulario tabela' align='center'>
				        				<tr class='titulo_tabela'>
				        					<td width='20%'>Comunicado</td>
				        					<td width='60%'>Leitor</td>
				        					<td width='20%'>Data Leitura</td>
				        				</tr>";
	        
	        for ($j = 0; $j < pg_num_rows($res_comunicado); $j++) {
	        	$cor = ($j % 2) ? "#F7F5F0" : "#F1F4FA";
	        	extract(pg_fetch_assoc($res_comunicado));
	        	
	        	$table_result .= "
				        				<tr bgcolor='{$cor}'>
				        					<td align='center'>{$comunicado}</td>
				        					<td align='center' class='leitor{$os}'>{$leitor}</td>
				        					<td align='center'>{$data_confirmacao}</td>
				        				</tr>"; 
	        }
	        
	        $table_result .= "
	        						</table>
	        					</td>
	        				</tr>";
	        
        
        	$ultimo_codigo_posto = $codigo_posto;
        }
        
			        $table_result .= "
			        	</table>
		            </td>
		        </tr>
        	</table>";        
	}
	catch (Exception $e) {
    	$msg_erro =  $e->getMessage();
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
