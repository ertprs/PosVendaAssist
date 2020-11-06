<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include "autentica_admin.php";

$btn_acao = $_POST['btn_acao'];

if($btn_acao == "PESQUISAR"){

	$data_inicial = $_POST["data_inicial"];
    $data_final   = $_POST["data_final"];

    if(empty($data_inicial) OR empty($data_final)){
        $msg_erro = "Data obrigatória";
    }

    if(strlen($msg_erro)==0){
        list($di, $mi, $yi) = explode("/", $data_inicial);
        if(!checkdate($mi,$di,$yi)) 
            $msg_erro = "Data inicial inválida";
    }
    
    if(strlen($msg_erro)==0){
        list($df, $mf, $yf) = explode("/", $data_final);
        if(!checkdate($mf,$df,$yf)) 
            $msg_erro = "Data final inválida";
    }

    if(strlen($msg_erro)==0){
        $aux_data_inicial = "$yi-$mi-$di";
        $aux_data_final = "$yf-$mf-$df";
    }

    if(strlen($msg_erro)==0){
    if (strtotime($aux_data_inicial.'+3 months') <= strtotime($aux_data_final) ) {
            $msg_erro = 'O intervalo entre as datas não pode ser maior que 3 meses';
        }
    }

    $produto_referencia = $_POST['produto_referencia'];
    $produto_descricao  = $_POST['produto_descricao'];
    if(strlen($produto_referencia) > 0){

    	$sql = "SELECT produto FROM tbl_produto WHERE referencia = '$produto_referencia' AND fabrica_i = $login_fabrica";
    	$res = pg_query($con,$sql);
    	if(pg_num_rows($res) > 0){
    		$produto = pg_fetch_result($res, 0, 'produto');
    		$cond_produto = " AND tbl_os.produto = $produto ";
    	}else{
    		$msg_erro = "Produto não encontrado";
    	}
    	
    }

}

$layout_menu = "gerencia";
$title = "RELATÓRIO TEMPO DEFEITO PRODUTO";

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
table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}
.espaco td{
	padding:10px 0 10px;
}
.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
}
.toggle_os{
        cursor:pointer;
}

.toggle_os:hover{
        background-color: #a1a1a1;
}

</style>

<? include "javascript_pesquisas.php"; ?>

<link rel="stylesheet" type="text/css" href="../plugins/jquery/datepick/telecontrol.datepick.css" />
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3/jquery.min.js" type="text/javascript"></script>
<script type="text/javascript" src="js/jquery.maskedinput2.js"></script>
<script src="../plugins/shadowbox/shadowbox.js"	type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>

<script language="JavaScript">

	$(document).ready(function() {	
		Shadowbox.init();

		$('#data_inicial').datepick({startDate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");

		$('.toggle_os').bind('click', function(){
                var os = $(this).parent().attr('rel');
                window.open("os_press.php?os="+os);
        });

	});

	function pesquisaProduto(produto,tipo){

        if (jQuery.trim(produto.value).length > 2){
            Shadowbox.open({
                content:    "produto_pesquisa_nv.php?"+tipo+"="+produto.value,
                player: "iframe",
                title:      "Produto",
                width:  800,
                height: 500
            });
        }else{
            alert("Informe toda ou parte da informação para realizar a pesquisa!");
            produto.focus();
        }
    }

    function retorna_dados_produto(referencia,descricao,produto,linha,nome_comercial,voltagem,referencia_fabrica,garantia,ativo,valor_troca,troca_garantia,troca_faturada){
        gravaDados('produto_referencia',referencia);
        gravaDados('produto_descricao',descricao);
        gravaDados('produto',produto);
    }

    function gravaDados(name, valor){
		try{
			$("input[name="+name+"]").val(valor);
		} catch(err){
			return false;
		}
	}
</script>

<? if (strlen($msg_erro) > 0) { ?>
<table width="700" border="0" cellpadding="0" cellspacing="1" align="center">
	<tr>
		<td class="msg_erro"><?echo $msg_erro; ?></td>
	</tr>
</table>

<? } ?>

<form name="frm_relatorio" method="post" action="<? echo $PHP_SELF ?>">
	
	<TABLE width="700" align="center" border="0" cellspacing='1' cellpadding='0' class='formulario'>

		<caption class="titulo_tabela">Parâmetros de Pesquisa</caption>

		<tbody>
			<tr>
				<td colspan="2">&nbsp;</td>
			</tr>

			 <tr>
			 	<td width="150"> &nbsp; </td>
                 <td>
                     Referência Produto<br>
                     <input type="text" name="produto_referencia" id="produto_referencia" value="<?php echo $produto_referencia;?>" size="15" maxlength="20" class='frm' /> 
 					 <img src='../imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: pesquisaProduto(document.frm_relatorio.produto_referencia,'referencia');" alt='Clique aqui para pesquisar pela referência do produto' style='cursor:pointer;'>
 					 <input type="hidden" name="produto" id="produto" value="<?php echo $produto;?>" class='frm' /> 
                 </td>

                 <td>
                     Descrição Produto&nbsp;<br>
                     <input type="text" name="produto_descricao" id="produto_descricao" value="<?php echo $produto_descricao;?>" size="30" maxlength="50" class='frm'>&nbsp;<img src='../imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: pesquisaProduto(document.frm_relatorio.produto_descricao,'descricao')" alt='Clique aqui para pesquisar pela descrição do produto' style='cursor:pointer;'>
                 </td>
             </tr>

			<tr>
				<td colspan="2">&nbsp;</td>
			</tr>

			<tr valign='top'>
				<td> &nbsp; </td>
				<td>
					Data Inicial <br />
					<input type="text" name="data_inicial" id="data_inicial" size="13" maxlength="10" value="<?=$data_inicial?>" class="frm">
				</td>
				<td>
					Data Final <br />
					<input type="text" name="data_final" id="data_final" size="13" maxlength="10" value="<?=$data_final?>" class="frm">
				</td>
			</tr>

			<tr>
				<td colspan="2">&nbsp;</td>
			</tr>

			<tr>
				<td colspan="3" align='center' style="padding-bottom:10px;">
					<input type="hidden" name="btn_acao" value="">
					<input type="button" onclick="javascript: document.frm_relatorio.btn_acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor:pointer " value="Pesquisar" />
				</td>
			</tr>
		</tbody>
	</table>
</form>
<br />

<?php
	if(strlen($msg_erro) == 0 AND $btn_acao == "PESQUISAR"){
		$sql = "SELECT  tbl_produto.produto,
						tbl_produto.referencia,
						tbl_produto.descricao,
						tbl_defeito_constatado.descricao AS defeito_constatado,
						TO_CHAR(tbl_os.data_nf,'DD/MM/YYYY') AS data_compra,
						TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
						(tbl_os.data_abertura - tbl_os.data_nf) AS tempo_utilizacao,
						tbl_os.revenda_nome AS revenda,
						tbl_os.consumidor_nome AS consumidor,
						tbl_posto.nome,
						tbl_posto_fabrica.codigo_posto,
						tbl_os.os,
						tbl_os.sua_os
					INTO TEMP tmp_relatorio_produto_defeito
				FROM tbl_os
				JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto AND tbl_produto.fabrica_i = $login_fabrica
				JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado AND tbl_defeito_constatado.fabrica = $login_fabrica
				JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
				JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE tbl_os.fabrica = $login_fabrica
				AND tbl_os.data_abertura BETWEEN '$aux_data_inicial' and '$aux_data_final'
				$cond_produto
				ORDER BY data_abertura DESC, tempo_utilizacao DESC;

				SELECT SUM(tempo_utilizacao) AS dias, produto
				INTO TEMP tmp_tempo_utilizacao_produto
				FROM tmp_relatorio_produto_defeito
				GROUP BY produto;

				SELECT tmp_relatorio_produto_defeito.referencia,
				tmp_relatorio_produto_defeito.descricao,
				tmp_relatorio_produto_defeito.tempo_utilizacao,
				tmp_relatorio_produto_defeito.defeito_constatado,
				tmp_relatorio_produto_defeito.data_compra,
				tmp_relatorio_produto_defeito.data_abertura,
				tmp_relatorio_produto_defeito.revenda,
				tmp_relatorio_produto_defeito.consumidor,
				tmp_relatorio_produto_defeito.codigo_posto,
				tmp_relatorio_produto_defeito.nome,
				tmp_relatorio_produto_defeito.os,
				tmp_relatorio_produto_defeito.sua_os,
				CASE
					WHEN tmp_relatorio_produto_defeito.tempo_utilizacao > 0 THEN
						((tmp_relatorio_produto_defeito.tempo_utilizacao * 100)/365)
					ELSE
						0
				END AS porcentagem
				FROM tmp_relatorio_produto_defeito
				JOIN tmp_tempo_utilizacao_produto USING(produto)
				ORDER BY tmp_relatorio_produto_defeito.produto;
				";
		$res = pg_query($con,$sql);


		if(pg_num_rows($res) > 0){
			$arquivo = "xls/relatorio_tempo_defeito-".date('Y-m-d').".xls";
			$resultado = "
						<img src='imagens/excel.png' width='30'> <br>
						<input type='button' value='Download Excel' onclick=\"javascript: window.open('{$arquivo}');\"> <br /><br />

						<table align='center' class='tabela' >
							<tr class='titulo_tabela'><td colspan='11'><b>Relatório Tempo Defeito Produto</b></td></tr>
							<tr class='titulo_coluna'>
								<th>OS</th>
								<th>Consumidor</th>
								<th>Revenda</th>
								<th>Posto</th>
								<th>Referência</th>
								<th>Descrição</th>
								<th>Data de Compra</th>
								<th>Defeito Constatado</th>
								<th>Data Defeito</th>
								<th>Tempo de Utilização</th>
								<th>% Tempo Defeito</th>
							</tr>";

			for($i = 0; $i < pg_num_rows($res); $i++){

				$referencia_produto = pg_fetch_result($res, $i, 'referencia');
				$descricao_produto  = pg_fetch_result($res, $i, 'descricao');
				$defeito_constatado = pg_fetch_result($res, $i, 'defeito_constatado');
				$data_compra 		= pg_fetch_result($res, $i, 'data_compra');
				$data_abertura 		= pg_fetch_result($res, $i, 'data_abertura');
				$tempo_utilizacao	= pg_fetch_result($res, $i, 'tempo_utilizacao');
				$os					= pg_fetch_result($res, $i, 'os');
				$sua_os				= pg_fetch_result($res, $i, 'sua_os');
				$revenda			= pg_fetch_result($res, $i, 'revenda');
				$consumidor			= pg_fetch_result($res, $i, 'consumidor');
				$codigo_posto		= pg_fetch_result($res, $i, 'codigo_posto');
				$nome_posto			= pg_fetch_result($res, $i, 'nome');
				$porcentagem		= pg_fetch_result($res, $i, 'porcentagem');

				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

				$resultado .= "<tr bgcolor='$cor' rel='{$os}'>
									<td class='toggle_os' align='left'>".$sua_os."</td>
									<td align='left'>".$consumidor."</td>
									<td align='left'>".$revenda."</td>
									<td align='left'>".$codigo_posto." - ".$nome_posto."</td>
									<td align='left'>".$referencia_produto."</td>
									<td align='left'>".$descricao_produto."</td>
									<td>".$data_compra."</td>
									<td align='left'>".$defeito_constatado."</td>
									<td>".$data_abertura."</td>
									<td>".$tempo_utilizacao."</td>
									<td>".number_format($porcentagem,2,',','.')."</td>
								</tr>";
			}
			$resultado .= "</table>";

			echo $resultado;

			$resultado = str_replace("class='tabela'","border='1'",$resultado);
			$resultado = str_replace("<tr class='titulo_tabela'><td colspan='11'><b>Relatório Tempo Defeito Produto</b></td></tr>","",$resultado);
			$resultado = str_replace("<th>","<th nowrap><font color='#FFFFFF'>",$resultado);
			$resultado = str_replace("</th>","</font></th>",$resultado);
			$resultado = str_replace("#F7F5F0","",$resultado);
			$resultado = str_replace("#F1F4FA","",$resultado);
			$resultado = str_replace("class='titulo_coluna'","bgcolor='#596d9b'",$resultado);
			$resultado = str_replace("class='titulo_tabela'","bgcolor='#596d9b'",$resultado);

			
			$xls = fopen($arquivo,"w");
			fwrite($xls, $resultado);
			fclose($xls);			

		}else{

			echo "<center>Nenhum resultado encontrado</center>";
		}

	}
?>

<?php include "rodape.php"; ?>