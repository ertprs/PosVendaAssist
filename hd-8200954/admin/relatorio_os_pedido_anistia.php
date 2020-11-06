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
				WHERE tbl_posto_fabrica.fabrica = {$login_fabrica} ";

		if ($tipo_busca == "codigo"){
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '{$q}' ";
		}else{
			$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		}

		$res = pg_query($con,$sql);
		if (pg_num_rows ($res) > 0) {
			for ($i=0; $i<pg_num_rows ($res); $i++ ){
				$cnpj = trim(pg_fetch_result($res,$i,'cnpj'));
				$nome = trim(pg_fetch_result($res,$i,'nome'));
				$codigo_posto = trim(pg_fetch_result($res,$i,'codigo_posto'));
				echo "$cnpj|$nome|$codigo_posto";
				echo "\n";
			}
		}
	}
	exit;
}
//if ($btn_acao == 'Pesquisar'){
//	if(isset($_POST['posto_nome'])){
//		$nome_posto = $_POST['posto_nome'];
//		$sql = "SELECT nome
//				FROM tbl_posto
//				WHERE tbl_posto_fabrica.fabrica = {$login_fabrica} 
//				AND UPPER(tbl_posto.nome) like UPPER('%$nome_posto%') ";
//		$res = pg_query($con,$sql);
//		if (pg_num_rows ($res) > 0) {
//			$nome_posto = $posto_nome;
//		
//		}
//		
//	}	
//}

// Ajax para recuperar os dados das OS de um posto
if (isset($_GET["buscar_dados_posto"])) {
	try {

		$posto = $_GET["buscar_dados_posto"];
		
		$sql = "
		SELECT
		tbl_os.os,
		tbl_os.sua_os,
		TO_CHAR(tbl_os.data_digitacao, 'DD/MM/YYYY') AS data_digitacao,
		(CURRENT_DATE - tbl_os.data_digitacao::date) AS qtde_dias,
		COUNT(tbl_os_item.os_item) AS qtde_pecas
		
		FROM
		tbl_os
		JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
		JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
		LEFT JOIN tbl_pedido_cancelado ON tbl_os.os = tbl_pedido_cancelado.os AND tbl_os_item.peca = tbl_pedido_cancelado.peca
		LEFT JOIN (
			tbl_os_comunicado
			JOIN tbl_comunicado ON tbl_os_comunicado.comunicado = tbl_comunicado.comunicado AND tbl_comunicado.descricao = 'OS 150 dias com pedido'
		) ON tbl_os.os = tbl_os_comunicado.os
		
		WHERE
		tbl_os.fabrica = {$login_fabrica}
		AND tbl_os.data_digitacao < CURRENT_TIMESTAMP - INTERVAL '150 day'
		AND tbl_os.os_fechada IS FALSE
		AND tbl_os.excluida IS NOT TRUE
		AND tbl_os.posto = {$posto}
		AND tbl_os_comunicado.comunicado IS NULL
		AND tbl_pedido_cancelado.pedido IS NULL
		AND tbl_os_item.servico_realizado <> 96
		AND (SELECT tbl_os_status.status_os FROM tbl_os_status WHERE tbl_os_status.os=tbl_os.os AND tbl_os_status.status_os IN (126, 143)) IS NULL
		
		GROUP BY
		tbl_os.os,
		tbl_os.sua_os,
		tbl_os.data_digitacao
		
		ORDER BY
		tbl_os.os
		";
		$res = @pg_query($con, $sql);
		
		if (pg_last_error($con)) {
			throw new Exception("Falha na consulta <error=" . pg_last_error($con) . ">");
		}
		
		if (pg_num_rows($res) == 0) {
			throw new Exception("Nenhuma OS encontrada");
		}
		
        $resultado = "
        <table width='695' border='0' cellspacing='1' cellpadding='0' class='formulario' align='center'>
	        <tr class='titulo_coluna'>
	        	<td width='695'><input type='button' value='Comunicar Posto' class='comunicar' /></td>
	        </tr>
	    </table>
        <table width='695' border='0' cellspacing='1' cellpadding='0' class='formulario' align='center'>
	        <tr class='titulo_coluna'>
	        	<td width='100'>OS</td>
	        	<td width='80'>Data Digitação</td>
	        	<td width='80'>Dias em Aberto</td>
	        	<td width='280'>Peça</td>
	        	<td width='80'>Preço Atual</td>
	        	<td width='80'>Ação</td>
	        </tr>";
        
        $n_os = 0;
        
		for ($i = 0; $i < pg_num_rows($res); $i++) {
        	extract(pg_fetch_assoc($res));
        	
			$sql = "
			SELECT
			tbl_peca.peca,
			(tbl_peca.referencia || ' - ' || tbl_peca.descricao) AS peca_descricao,
			tbl_tabela_item.preco,
			tbl_linha.nome AS linha
			
			FROM
			tbl_os
			JOIN tbl_os_produto ON tbl_os.os=tbl_os_produto.os
			JOIN tbl_os_item ON tbl_os_produto.os_produto=tbl_os_item.os_produto AND tbl_os_item.fabrica_i=tbl_os.fabrica
			JOIN tbl_pedido ON tbl_os_item.pedido = tbl_pedido.pedido AND tbl_pedido.fabrica = tbl_os.fabrica AND tbl_os.posto=tbl_pedido.posto
			JOIN tbl_pedido_item ON tbl_pedido.pedido = tbl_pedido_item.pedido AND tbl_os_item.peca = tbl_pedido_item.peca
			JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido = tbl_pedido.pedido
			JOIN tbl_faturamento ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento AND tbl_os_item.peca=tbl_faturamento_item.peca
			JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca AND tbl_peca.fabrica=tbl_os.fabrica
			JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
			JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha
			JOIN tbl_posto_linha ON tbl_posto_linha.posto=tbl_os.posto AND tbl_posto_linha.linha = tbl_produto.linha
			JOIN tbl_tabela_item ON tbl_posto_linha.tabela=tbl_tabela_item.tabela AND tbl_os_item.peca=tbl_tabela_item.peca
			
			WHERE
			tbl_os.os = {$os}
			AND tbl_pedido_item.qtde_faturada >= tbl_os_item.qtde
			AND tbl_faturamento.emissao < CURRENT_TIMESTAMP - INTERVAL '20 days'
			";
			$res_pecas = @pg_query($con, $sql);

			if (pg_last_error($con)) {
				throw new Exception("Falha na consulta <error=" . pg_last_error($con) . ">");
			}
			
			if (pg_num_rows($res_pecas) != $qtde_pecas) {
				continue;
			}
			
			$n_os++;
	        $cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
			$borda = "style='border-top: 1px solid #596D9B'";
			$total_pecas = 0;
	        
			for ($j = 0; $j < $qtde_pecas; $j++) {
				extract(pg_fetch_assoc($res_pecas));
				$total_pecas += $preco;
				
				if ($j == 0) {
					$resultado .= "
	        	<tr bgcolor='{$cor}'>
	        		<td {$borda} rowspan='{$qtde_pecas}' class='dados_os'>
	        			<a href='os_press.php?os={$os}' target='_blank' class='sua_os'>{$sua_os}</a>
	        			<input type='hidden' value='{$os}' class='os'/>
	        			<input type='hidden' value='[total_pecas]' class='total_pecas' />
	        			<input type='hidden' value='{$linha}' class='linha' />
	        			<input type='hidden' value='{$qtde_dias}' class='qtde_dias' />
	        		</td>
	        		<td {$borda} rowspan='{$qtde_pecas}'>{$data_digitacao}</td>
	        		<td {$borda} rowspan='{$qtde_pecas}'>{$qtde_dias}</td>
	        		<td {$borda}>{$peca_descricao}</td>
	        		<td {$borda}>{$preco}</td>
	        		<td {$borda} rowspan='{$qtde_pecas}'><input type='button' value='Anistia' class='anistia' /></td>
	        	</tr>";
				}
				else {
	        		$resultado .= "
	        	<tr bgcolor='{$cor}'>
	        		<td>{$peca_descricao}</td>
	        		<td>{$preco}</td>
	        	</tr>";
				}
			}
			
			$resultado = str_replace("[total_pecas]", $total_pecas, $resultado);
		}
		
		if ($n_os == 0) {
			throw new Exception("Nenhuma OS encontrada");
		}
		
        $resultado .= "
        </table>";
        
        echo "ok|{$resultado}";
	}
	catch (Exception $e) {
		echo "falha|" . $e->getMessage();
	}
	die;
}

if (isset($_REQUEST['comunicar_posto'])) {
	try {
		$posto = intval($_REQUEST['comunicar_posto']);
		
		@$res = pg_query($con, "BEGIN");
		if (pg_last_error($con)) throw new Exception(pg_last_error($con), 1);
		
		$infos = json_decode(stripslashes($_REQUEST['infos']), true);
		
		$align = "align='center'";
		$dados = "
		<table cellspacing='1' style='font-family: verdana; font-size: 11px; border-collapse: collapse; border:1px solid #596d9b;'>
			<tr style='	background-color:#596d9b; font: bold 11px Arial; color:#FFFFFF; text-align:center;'>
				<td {$align}>OS</td>
				<td {$align}>Dias em Aberto</td>
				<td {$align}>Linha</td>
				<td {$align}>Valor das Peças</td>
			</tr>";
		
		$listaOs = array();
		
		foreach ($infos as $i => $info) {
			extract($info);
			$linha = utf8_decode($linha);
			
			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
			
			$dados .= "
			<tr bgcolor='{$cor}'>
				<td {$align} nowrap><a href='os_press.php?os={$os}' target='_blank'>{$sua_os}</a></td>
				<td {$align}>{$qtde_dias}</td>
				<td {$align}>{$linha}</td>
				<td {$align}>{$total_pecas}</td>
			</tr>";
			
			$listaOs[] = $os;
		}
		
		$dados .= "
		</table>";
		
		$mensagem = "Abaixo segue a relação de os's que estão em aberto =>150 dias com peças atendidas em garantia, solicitamos sua analise e solução, as ordens de serviço que não foram finalizadas e comprovados o atendimento através de comprovantes. Será debitado da sua mão de obra o valor da peça integral.<br>
		<br>
		{$dados}";
		
		$mensagem = str_replace("'", "\'", $mensagem);
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
		'OS 150 dias com pedido',
		{$posto},
		true
		)
		";
		@$res = pg_query($con, $sql);
		if (pg_last_error($con)) throw new Exception(pg_last_error($con), 1);
		
		@$res = pg_query($con, "SELECT CURRVAL('seq_comunicado')");
		if (pg_last_error($con)) throw new Exception(pg_last_error($con), 1);
		$comunicado = pg_result($res, 0, 0);
		
		foreach ($listaOs as $i => $os) {
			$listaOs[$i] = "({$os}, {$comunicado})";
		}
		$listaOs = implode(", ", $listaOs);
		
		$sql = "
		INSERT INTO tbl_os_comunicado(
		os,
		comunicado
		)
		
		VALUES
		{$listaOs}
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
if ($btn_acao == 'Pesquisar'){
    $codigo_posto = $_POST["posto_codigo"];
    if(!empty($codigo_posto)){
    	$sql = "
    	SELECT
    	posto
    	
    	FROM
    	tbl_posto_fabrica
    	
    	WHERE
    	tbl_posto_fabrica.fabrica={$login_fabrica}
    	AND tbl_posto_fabrica.codigo_posto='{$codigo_posto}'
    	";
    	
        $res = pg_query($con, $sql);
        if (pg_num_rows($res) == 1) {
            $posto = trim(pg_fetch_result($res,0,'posto'));
            $condicao = "AND tbl_os.posto = {$posto}";
        }
        else {
            $msg_erro = "Posto não encontrado.";
        }
    }
}

$layout_menu = "auditoria";
$title = strtoupper("OS ABERTAS HÁ MAIS DE 150 DIAS COM PEDIDOS DE PEÇAS ATENDIDOS");
    
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
            //alert(data[1]);
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
            //alert(data[1]);
        });

        $('.ver_posto').click(function() {
            var posto = $(this).attr('rel');
            $("#"+posto).slideToggle("fade");
        });

        $(".seletor_posto").each(function(){
            $(this).click(function() {
            	var posto = $(this).attr('posto');

            	if ($("#td"+posto).html() == "") {
                	$("#tr"+posto).css("display", "block");
                	$("#td"+posto).html("<div><img src='imagens/loading.gif' /></div>");

			        $.ajax({
			            url: '<?php echo $PHP_SELF?>',
			            data: 'buscar_dados_posto='+posto,
			            success: function(result) {
				            result = result.split('|');
				            $("#td"+posto).html(result[1]);
				            $(".comunicar", $("#td"+posto)).each(function() {
					            $(this).click(function(){
						            var infos = [];
						            $(".dados_os", $("#td"+posto)).each(function() {
							            var info = '{'+
							            	'"os":"'+$(".os", $(this)).val()+'",'+
									    	'"sua_os":"'+$(".sua_os", $(this)).html()+'",'+
									    	'"qtde_dias":"'+$(".qtde_dias", $(this)).val()+'",'+
									    	'"linha":"'+$(".linha", $(this)).val()+'",'+
									    	'"total_pecas":"'+$(".total_pecas", $(this)).val()+'"'+
									    '}';

									    infos[infos.length] = info;
							        });

							        infos = '[' + infos.join(',') + ']';

							        console.log(infos);
							        
						            if (confirm('Enviar comunicado ao posto?')) {
							            $.ajax({
								            type: 'POST',
								            url: '<? echo $PHP_SELF ?>',
								            data: {
									            comunicar_posto: posto,
									            infos: infos
									        },
									        success: function(response) {
										        if (response == "ok") {
											        alert('Posto comunicado com sucesso');

											        $(".comunicar, .anistia", $("#td"+posto)).each(function() {
												        $(this).css("display", "none");
												});
										        }
										        else {
									            	response = response.split('|');
									            	alert(response[1]);
										        }
									        }
								        });
						            }
						        });
					        });
					        
				            $(".anistia", $("#td"+posto)).each(function() {
					            $(this).click(function() {
						            var botao = this;
						            var sua_os = $(".sua_os", $(this).parent().parent()).html();      
						            var os = $(".os", $(this).parent().parent()).val();
						            console.log(os);
						            if (confirm('Excluir a OS '+sua_os+' e dar anistia ao pedido de peças?')) {
							            var os = $(".os", $(this).parent().parent()).val();
							            var time = new Date();
							            
							            $.ajax({
								            type: 'POST',
							            	url: 'os_cadastro.php',
							            	data: {
						            			ajax: 'sim',
							            		btn_acao: 'apagar',
							            		obs_exclusao: 'Anistia do pedido de peças. OS com pedido e mais de 150 dias.',
							            		os: os,
							            		time: time.getTime()
							            	},
							            	success: function(response) {
								            	if (response == 'ok') {
									            	$(botao).css("display", "none");
									            	alert('OS ' + sua_os + ' excluída com sucesso');
								            	}
								            	else {
									            	response = response.split('|');
									            	alert(response[1]);
								            	}
								            }
								        });
						            }
						        });
					        });
			            }
			        });
            	}
            	else {
            		$("#tr"+posto).css("display", "none");
            		$("#td"+posto).html("");
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
       $sql = "
		SELECT
		DISTINCT
		tbl_posto.posto,
		tbl_posto_fabrica.codigo_posto,
		tbl_posto.nome
		
		FROM
		tbl_os
		JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
		JOIN tbl_os_item ON tbl_os_produto.os_produto=tbl_os_item.os_produto AND tbl_os_item.fabrica_i=tbl_os.fabrica
		JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
		JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto
		AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
		JOIN tbl_pedido ON tbl_os_item.pedido=tbl_pedido.pedido
		LEFT JOIN (
			tbl_os_comunicado
			JOIN tbl_comunicado ON tbl_os_comunicado.comunicado = tbl_comunicado.comunicado AND tbl_comunicado.descricao = 'OS 150 dias com pedido'
		) ON tbl_os.os = tbl_os_comunicado.os
		
		WHERE
		tbl_os.fabrica = {$login_fabrica}
		AND tbl_os.os_fechada IS FALSE
		AND tbl_os.excluida IS NOT TRUE
		AND tbl_os.data_digitacao < CURRENT_TIMESTAMP - INTERVAL '150 day'
		AND tbl_os.data_digitacao > (SELECT MIN(data_digitacao) FROM tbl_os WHERE fabrica=3 AND os_fechada IS FALSE)
		{$condicao}
		AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
		AND tbl_os_item.pedido IS NOT NULL
		AND tbl_os_comunicado.comunicado IS NULL
		AND tbl_os_item.servico_realizado <> 96
		AND tbl_os.status_os_ultimo NOT IN (126, 143)
		
		ORDER BY
		tbl_posto.nome
		";
        $res = @pg_query($con, $sql);
        
        if (pg_last_error($con)) {
        	throw new Exception("Erro na consulta <error=" . pg_last_error($con) . ">");
        }
        
        if(pg_num_rows($res) == 0){
        	throw new Exception("Não existem OS abertas a mais de 150 dias");
        	$msg_erro = "Não existem OS abertas a mais de 150 dias";
        }
        
        $table_result .= "
        <br><table width='710' border='0' cellspacing='1' cellpadding='0' class='tabela formulario' align='center'>";
        
        for ($i = 0; $i < pg_num_rows($res); $i++) {
        	extract(pg_fetch_array($res));
        	//$codigo_posto = str_pad($codigo_posto, 6, "0", STR_PAD_LEFT);
        	$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
        	
        	$table_result .=  "
        	<tr class='titulo_tabela seletor_posto' posto='{$posto}' >
        		<td>{$codigo_posto} - {$nome}</td>
        	</tr>";

            $table_result .=  "
            <tr class='dados_posto' id='tr{$posto}' bgcolor='$cor'>
            	<td id='td{$posto}'></td>
            </tr>";
        }
        
        $table_result .=  "
        </table>";        
	}
	catch (Exception $e) {
   		$msg_erro = $e->getMessage();
	}
}
?>


<style type="text/css">
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
    cursor: pointer;
    text-align: left;
    text-transform: uppercase;
    cursor: pointer;
    }
    
    .seletor_posto>td, .dados_posto>td{
    padding: 2px;
    }
    
    .dados_posto {
    display: none;
    }
    
    .dados_posto td {
    padding: 5px;
    text-align:center;
    }

    #msg{ width:700px; margin:auto; }
</style>

<form name="frm_relatorio" method="post" action="<? echo $PHP_SELF ?>">
    <input type="hidden" name="btn_acao" value='PesquisaDados' />
    <?php
        if (!empty($msg_erro))
            echo "<div class='msg_erro'>{$msg_erro}</div>";
    ?>
    <table width="710" align="center" border="0" cellspacing='1' cellpadding='2' class='formulario'>
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

<?php echo $table_result;?>

<br /><br />
<?php include "rodape.php"; ?>
