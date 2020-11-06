<?

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";
include "funcoes.php";

/**
 * Funcao para exibir as informacoes de determinada questao.
 * Ela ja cria o link para o popup de informacoes adicionais.
 * HD 102091
 *
 * @param   string    $exibir_texto           Texto a ser exibido no link
 * @param   string    $detalhe                Nome do detalhe que deve ser exibido no popup, ver arquivo: pesquisa_suggar_consulta_popup.php
 * @param   string    $resposta               Nome da resposta do detalhe que desejamos vizualisar
 * @return  string
 * @author Augusto Pascutti <augusto.hp@gmail.com>
 */
function exibir_detalhes_pesquisa($exibir_texto,$detalhe = null,$resposta = null) {
    global $data_inicial, $data_final, $produto, $filtrar;

    $prod     = (int) $produto;
    $params   = array();
    $params[] = "data_ini={$data_inicial}";
    $params[] = "data_fim={$data_final}";
    $params[] = "prod={$prod}";
    $params[] = "detalhe={$detalhe}";
    $params[] = "resposta={$resposta}";
    $params[] = "filtrar={$filtrar}";
    $url      = "pesquisa_suggar_consulta_popup.php?".implode('&',$params);

    $exibir = ( is_float($exibir_texto) ) ? number_format($exibir_texto,2,',','.') : $exibir_texto ;
    $exibir = '<a href="javascript: abrirNovaJanela(\''.$url.'\');">'.$exibir.'</a>';
    return $exibir;
}

//////////////////////////////////////////
/*if($ip<>"201.27.30.119" ){
echo "programa em manunteção";
exit;
}*/
$msg_erro = "";

// pega os valores das variaveis dadas como parametros de pesquisa e coloca em um cookie
setcookie("cookredirect", $_SERVER["REQUEST_URI"]); // expira qdo fecha o browser

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");


$layout_menu = "callcenter";
$title       = "PESQUISA SATISFAÇÃO";

include "cabecalho.php";
?>


<style type="text/css">
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
table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial" !important;
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
    margin: 0 auto;
    padding: 3px 0;
    width: 700px;
}

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}
.subtitulo{
    background-color: #7092BE;
    font:bold 11px Arial;
    color: #FFFFFF;
}
</style>
<?php include "../js/js_css.php";?>
<script type="text/javascript" language="javascript">


function abrirNovaJanela(url) {
	popupw = window.open(url,null,'scrollbars=yes,width=750,height=450,top=315,left=0');
    //popupw = window.open(url, 'Detalhes da Pesquisa','scrollbars=yes,width=750,height=450,top=315,left=0');
    popupw.focus();
}
$().ready(function(){

    setTimeout(function() {
        $(".grafico").hide();
    }, 2000);

    $( "#data_inicial" ).datepick({startDate : "01/01/2000"});
    $( "#data_inicial" ).mask("99/99/9999");
	$( "#data_final" ).datepick({startDate : "01/01/2000"});
    $( "#data_final" ).mask("99/99/9999");

    $(".mostra_grafico").click(function(){
    	var posicao = $(this).attr('rel');

    	$("#columnchart_"+posicao).toggle();
    });

});
</script>
<? include "javascript_pesquisas.php"; 


$btn_acao = $_REQUEST['acao'];
if(strlen($btn_acao)>0){

	$data_inicial = $_POST["data_inicial"];
    $data_final = $_POST["data_final"];


    $data_inicial_get = $_GET["data_inicial"];
    $data_final_get = $_GET["data_final"];

    if ( strlen($_POST['acao']) > 0) {
    if(empty($data_inicial) OR empty($data_final)){
        $msg_erro = "Data Inválida";
    }

    if(empty($data_inicial_get) && empty($data_final_get)){
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
	}


    if(strlen($msg_erro)==0){
        $aux_data_inicial = "$yi-$mi-$di";
        $aux_data_final = "$yf-$mf-$df";
    }
    if(strlen($msg_erro)==0){
        if(($aux_data_final < $aux_data_inicial)  or ($aux_data_final > date('Y-m-d'))){
            $msg_erro = "Data Inválida.";
        }
    }

	$x_data_inicial = $aux_data_inicial;
	$x_data_final = $aux_data_final;
	}

	if ( strlen($_GET['acao']) > 0) {
		$data_inicial = $_GET["data_inicial"];
    	$data_final = $_GET["data_final"];

    	if(strlen($msg_erro)==0){
	        list($yi, $mi, $di) = explode("-", $data_inicial);
	        if(!checkdate($mi,$di,$yi)) 
	            $msg_erro = "Data Inválida";
	    }
	    if(strlen($msg_erro)==0){
	        list($yf, $mf, $df) = explode("-", $data_final);
	        if(!checkdate($mf,$df,$yf)) 
	            $msg_erro = "Data Inválida";
	    }
	    if(strlen($msg_erro)==0){
	        $data_inicial = "$di-$mi-$yi";
	        $data_final = "$df-$mf-$yf";
	    }
	}
}

    if(strlen($msg_erro)>0)
        echo "<div class='msg_erro'>{$msg_erro}</div>";
?>
<form name="frm_pesquisa" method="POST" action="<?echo $PHP_SELF?>">

<input type="hidden" name="acao">

<table width="700" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
	<tr class="titulo_tabela">
		<td colspan="2">Parâmetros de Pesquisa</td>
	</tr>
	<tr>
		<td colspan="2">&nbsp;</td>
	</tr>

	<tr >
		<td colspan='2'>
			<br>
			<table width='100%' border="0">
				<tr>
					<td width='30%'>&nbsp;</td>
					<td width='20%'>
							Data Inicial
							<input type="text" id="data_inicial" name="data_inicial" size="11" maxlength="10" value="<? echo $data_inicial ?>" class="frm">
					</td>
					<td align='left' width='20%'>
							Data Final
							<input type="text" name="data_final" id="data_final" size="11" maxlength="10" value="<? echo $data_final ?>" class="frm">
					</td>
					<td width='30%'>&nbsp;</td>
				</tr>
				<tr><td colspan='2'>&nbsp;</td></tr>
				<tr>
					<td width='30%'>&nbsp;</td>
					<td width='20%'>
							<input type="radio" name="filtrar" value="os" <?php echo $checked = $filtrar != 'pesquisa' ? "checked": ""?>>Data abertura da OS
					</td>
					<td align='left' width='20%'>
							<input type="radio" name="filtrar" value="pesquisa" <?php echo $checked = $filtrar == 'pesquisa' ? "checked": ""?> >Data da pesquisa
					</td>
					<td width='30%'>&nbsp;</td>
				</tr>
			</table>
			<br>
		</td>
	</tr>
	
	<tr >
		<td align='right' width='30%'>Produto 
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		</td>
		
		<td>
			<?
			$sql = "select tbl_produto.referencia,
							tbl_produto.descricao, tbl_produto.produto
					FROM tbl_produto
					JOIN tbl_os on tbl_os.produto = tbl_produto.produto
					JOIN tbl_suggar_questionario on tbl_suggar_questionario.os= tbl_os.os
					GROUP BY
						tbl_produto.referencia,
						tbl_produto.descricao, tbl_produto.produto
					ORDER BY
						tbl_produto.referencia";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res)>0){
			//	echo $sql;
				echo "<Select name='produto' value='1' style='width:350px' class='frm'>";
					echo "<option value='' > TODOS</option>";
				for($x=0;$x<pg_numrows($res);$x++){
					$xproduto = pg_result($res,$x,produto);
					$produto_referencia = pg_result($res,$x,referencia);
					$produto_descricao = pg_result($res,$x,descricao);
					echo "<option value='$xproduto' "; if($produto==$xproduto){echo "SELECTED";}
					echo "> $produto_referencia - $produto_descricao</option>";
				}
				echo "</select>";
			}else {
			echo "Nenhum resultado";
			}

			?>
		</td>
	</tr>
	<tr><td colspan='2'>&nbsp;</td></tr>
	<tr>
    	<td align='right'>
    	   <input type="checkbox" name="notas" <?php echo (isset($_POST['notas']))?'checked="checked"':''; ?>/>Notas
		   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        </td>
    	
		<td align='left'>
    	   Média por Postos Independente do Produto
        </td>
	</tr>
	<tr><td colspan='2'>&nbsp;</td></tr>
	<tr>
    	<td align='right'>
    	   <input type="checkbox" name="resumo" <?php echo (isset($_POST['resumo']))?'checked="checked"':''; ?>>Resumo
		   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        </td>
    	
		<td align='left'>
    	   Resumo de quantidade por produto pesquisado Independente do Produto
    	</td>
	</tr>	
	<tr>
		<td colspan="4">&nbsp;</td>
	</tr>
	
	<tr>
		<td colspan="4" align="center"><input type='button' value='Pesquisar' onclick="document.frm_pesquisa.acao.value='PESQUISAR'; document.frm_pesquisa.submit();" style="cursor: pointer;" alt="Preencha as opções e clique aqui para pesquisar"></td>
	</tr>
</table>

</form>
<Br><BR>
<?
    $notas= $_POST['notas'];
    $resumo= $_POST['resumo'];
    $produto = $_POST['produto'];
    
    if(strlen($produto) ==0) 
        $produto = $_GET['produto'];
        
    $cond_os_produto   = "1=1";
    $cond_data= "1=1";


    $x_data_inicial = trim($_POST["data_inicial"]);
	$x_data_final = trim($_POST["data_final"]);

	$x_data_inicial_get = trim($_GET["data_inicial"]);
	$x_data_final_get = trim($_GET["data_final"]);


	if(empty($x_data_inicial_get) && empty($x_data_final_get)){

		if (strlen($x_data_inicial) > 0 AND strlen($x_data_final) > 0){
		    $x_data_inicial = fnc_formata_data_pg($x_data_inicial);
			$x_data_final   = fnc_formata_data_pg($x_data_final);
			$y_data_inicial = substr($x_data_inicial,9,2) . substr($x_data_inicial,6,2) . substr($x_data_inicial,1,4);
			$y_data_final = substr($x_data_final,9,2) . substr($x_data_final,6,2) . substr($x_data_final,1,4);
			
			$x_data_inicial = str_replace("'","",$x_data_inicial);	
			$x_data_final = str_replace("'","",$x_data_final);	
			

			if ($x_data_inicial != "null") {
			}else{
				$data_inicial = "";
				$erro .= " Preencha correto o campo Data Inicial.<br> ";
			}

			if ($x_data_final != "null") {
			}else{
				$data_final = "";
				$erro .= " Preencha correto o campo Data Final.<br> ";
			}
	  
	  	}

	}else{

		$x_data_inicial = $x_data_inicial_get;	
		$x_data_final = $x_data_final_get;	

	}

if(strlen($msg_erro) == 0)  {
if(strlen($btn_acao)>0 and strlen($notas) and strlen($resumo)){
	
	if(strlen($x_data_inicial) > 0 and strlen($x_data_final) > 0){
		if ($filtrar == 'os') {
			$cond_data = " tbl_os.data_abertura between '$x_data_inicial 00:00:00' and '$x_data_final 23:59:59' ";
		}else{
			$cond_data = " tbl_suggar_questionario.data_input between '$x_data_inicial 00:00:00' and '$x_data_final 23:59:59' ";
		}			
	}

	$sql = "SELECT count(tbl_os.produto) as total
			FROM tbl_suggar_questionario
			JOIN tbl_os on tbl_os.os = tbl_suggar_questionario.os  AND tbl_os.fabrica = $login_fabrica
			JOIN tbl_produto on tbl_produto.produto = tbl_os.produto
			WHERE $cond_data";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		$total = pg_result($res,0,total);
	}

	$sqlx = "SELECT tbl_produto.produto         ,
			tbl_produto.referencia              ,
			tbl_produto.descricao               ,
			count(tbl_os.produto) as total_prod ,
			(((count(tbl_os.produto)*100)::numeric(12,2) /$total)::numeric(12,2))::numeric(12,2) as percentual_prod
			FROM tbl_suggar_questionario
			JOIN tbl_os      ON tbl_os.os           = tbl_suggar_questionario.os AND tbl_os.fabrica = $login_fabrica
			JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
			WHERE $cond_data
			GROUP BY tbl_produto.produto   ,
					 tbl_produto.referencia,
					 tbl_produto.descricao
			ORDER BY total_prod ASC";

	if($total == 0){
		echo "<table border='0' cellpadding='1' cellspacing='1'  bgcolor='red' width='700' align='center' style='font-family: verdana; font-size: 11px'>";
		echo "<tr>";
		echo "<td  colspan='5'>Não foi encontrado Pesquisa com esses parametros.</td>";
		echo "</tr>";
		echo "</table>";
		exit;
	}

	echo "<table border='0' cellpadding='4' cellspacing='1' width='700' align='center' class='tabela'>";
	echo "<tr class='titulo_tabela'>";
	echo "<td  colspan='7'>Total de produtos pesquisados: $total</td>";
	echo "</tr>";
	echo "<tr class='titulo_coluna'>";
	echo "<td width='70%'><B>Produto</B></td>";
	echo "<td align='center' width='15%'>Quantidade</B></td>";
	echo "<td align='center' width='15%'><B>Percentual</B></td>";
	echo "<td align='center' width='40'><B>Sim</B></td>";
	echo "<td align='center' width='40'><B>Não</B></td>";
	echo "<td align='center' width='55' nowrap><B>Sim %</B></td>";
	echo "<td align='center' width='55' nowrap><B>Não %</B></td>";
	echo "</tr>";

	$resx = pg_exec($con,$sqlx);
	for($x=0;pg_numrows($resx)>$x;$x++){
		$xxproduto           = pg_result($resx,$x,produto);
		$referencia         = pg_result($resx,$x,referencia);
		$descricao          = pg_result($resx,$x,descricao);
		$total_produto      = pg_result($resx,$x,total_prod);
		$percentual_produto = pg_result($resx,$x,percentual_prod);

		$sql="  SELECT 	CASE WHEN
							satisfeito is true then 'sim'
							else 'nao' end as sim_nao ,
						count(questionario) as qtde
				FROM tbl_suggar_questionario
				JOIN tbl_os ON tbl_os.os = tbl_suggar_questionario.os and tbl_os.produto=$xxproduto AND  tbl_os.fabrica=$login_fabrica
				WHERE $cond_data
				AND satisfeito is not null
				GROUP BY satisfeito;";
		$res=pg_exec($con,$sql);
		
		$cor = ($x % 2) ? "#F7F5F0" : "#F1F4FA";
		
		if(pg_numrows($res)>0){
			$sim=0;
			$nao=0;
			$xsim=0;
			$xnao=0;
			for($y=0;pg_numrows($res)>$y;$y++){
				if(pg_result($res,$y,sim_nao)=="sim")$sim = pg_result($res,$y,qtde);
				if(pg_result($res,$y,sim_nao)=="nao")$nao = pg_result($res,$y,qtde);
			}
		}

		$xtotal = $sim + $nao;
		if($xtotal <>0) {
			$xsim = (($sim*100)/$xtotal);
			$xnao = (($nao*100)/$xtotal);
		}
		if(strlen($sim) ==0) $sim=0;
		if(strlen($nao) ==0) $nao=0;


		echo "<tr bgcolor='$cor' >";
		echo "<td align='left' width='70%'><B><a href='$PHP_SELF?produto=$xxproduto&data_inicial=$x_data_inicial&data_final=$x_data_final&acao=listar&filtrar=$filtrar' target='_blank'>$descricao</a></B></td>";
		echo "<td align='center' width='15%'><B>$total_produto</B></td>";
		echo "<td align='center' width='15%'><B>$percentual_produto%</B></td>";
		echo "<td align='center' width='40'><B>$sim</B></td>";
		echo "<td align='center' width='40'><B>$nao</B></td>";
		echo "<td align='center' width='55'><B>".number_format($xsim,1,",",".")."%</B></td>";
		echo "<td align='center' width='55'><B>".number_format($xnao,1,",",".")."%</B></td>";
		echo "</tr>";
	}
	echo "</table><BR><BR>";

$sql = "SELECT sum(tbl_suggar_questionario.nota) as nota,
					tbl_posto.nome,
					tbl_posto_fabrica.codigo_posto,
					count(tbl_os.posto) as qtde
			FROM tbl_suggar_questionario
			JOIN tbl_os using(os)
			JOIN tbl_posto on tbl_os.posto = tbl_posto.posto
			JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto
			AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE $cond_data
			GROUP BY
				tbl_posto.nome,
				tbl_posto_fabrica.codigo_posto
			order by nota";
	$res = pg_exec($con,$sql);

	if(pg_numrows($res)>0){
		echo "<table border='0' cellpadding='4' cellspacing='1'  width='700' align='center' class='tabela'>";
			echo "<tr class='titulo_coluna'>";
			echo "<td  align='center' width='60%'><B>Posto</B></td>";
			echo "<td align='center' width='20'><B>Pesquisa</B></td>";
			echo "<td align='center' width='20'><B>Média Nota</B></td>";
			echo "</tr>";
		for($i=0;$i<pg_numrows($res);$i++){
			$nota         = pg_result($res,$i,nota);
			$nome         = pg_result($res,$i,nome);
			$codigo_posto = pg_result($res,$i,codigo_posto);
			$qtde         = pg_result($res,$i,qtde);
			$nota = $nota / $qtde;
		$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
			$medias[$i]= $nota;
			$posto[$i]=$codigo_posto;
			echo "<tr bgcolor='$cor'>";
			echo "<td align='left' width='60%' nowrap>$codigo_posto - $nome</td>";
			echo "<td align='center' width='20'>$qtde</td>";
			echo "<td align='center' width='20'>$nota</td>";
			echo "</tr>";
		}
		echo "</table><BR><BR>";

	}
} elseif(strlen($btn_acao)>0 and strlen($notas) and (strlen($msg_erro) == 0)){
	

	if (strlen($x_data_inicial) > 0 AND strlen($x_data_final) > 0){
		
		if(strlen($erro) == 0){
			if ($filtrar == 'os') {
				$cond_data = " tbl_os.data_abertura between '$x_data_inicial 00:00:00' and '$x_data_final 23:59:59' ";
			}else{
				$cond_data = " tbl_suggar_questionario.data_input between '$x_data_inicial 00:00:00' and '$x_data_final 23:59:59' ";
			}				
		}
	}
	$sql = "SELECT sum(tbl_suggar_questionario.nota) as nota,
					tbl_posto.nome,
					tbl_posto_fabrica.codigo_posto,
					count(tbl_os.posto) as qtde
			FROM tbl_suggar_questionario
			JOIN tbl_os using(os)
			JOIN tbl_posto on tbl_os.posto = tbl_posto.posto
			JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto
			AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE $cond_data
			GROUP BY
				tbl_posto.nome,
				tbl_posto_fabrica.codigo_posto
			order by nota";
	$res = pg_exec($con,$sql);

	if(pg_numrows($res)>0){
		echo "<table border='0' cellpadding='4' cellspacing='1' width='700' align='center' class='tabela'>";
			echo "<tr class='titulo_coluna'>";
			echo "<td align='center' width='60%'><B>Posto</B></td>";
			echo "<td align='center' width='20'><B>Pesquisa</B></td>";
			echo "<td align='center' width='20'><B>Média Nota</B></td>";
			echo "</tr>";
		for($i=0;$i<pg_numrows($res);$i++){
			$nota         = pg_result($res,$i,nota);
			$nome         = pg_result($res,$i,nome);
			$codigo_posto = pg_result($res,$i,codigo_posto);
			$qtde         = pg_result($res,$i,qtde);
			$nota = $nota / $qtde;
			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
			$medias[$i]= $nota;
			$posto[$i]=$codigo_posto;
			echo "<tr bgcolor=$cor>";
			echo "<td align='left' width='60%' nowrap>$codigo_posto - $nome</td>";
			echo "<td align='center' width='20'>$qtde</td>";
			echo "<td align='center' width='20'>$nota</td>";
			echo "</tr>";
		}
		echo "</table><BR><BR>";
	}

} elseif(strlen($btn_acao)>0 and strlen($resumo) ){

	$x_data_inicial = trim($_POST["data_inicial"]);
	$x_data_final = trim($_POST["data_final"]);

	if (strlen($x_data_inicial) > 0 AND strlen($x_data_final) > 0){
		
		if(strlen($erro) == 0){
			if ($filtrar == 'os') {
				$cond_data = " tbl_os.data_abertura between '$x_data_inicial 00:00:00' and '$x_data_final 23:59:59' ";
			}else{
				$cond_data = " tbl_suggar_questionario.data_input between '$x_data_inicial 00:00:00' and '$x_data_final 23:59:59' ";
			}				
		}
	}

$sql = "SELECT count(tbl_os.produto) as total
		FROM tbl_suggar_questionario
		JOIN tbl_os on tbl_os.os = tbl_suggar_questionario.os  AND tbl_os.fabrica = $login_fabrica
		JOIN tbl_produto on tbl_produto.produto = tbl_os.produto
		WHERE $cond_data";
$res = pg_exec($con,$sql);
			if(pg_numrows($res)>0){
				$total = pg_result($res,0,total);
			}
$sqlx = "SELECT tbl_os.produto,
			tbl_produto.referencia,
			tbl_produto.descricao,
			count(tbl_os.produto) as total_prod,
			(((count(tbl_os.produto)*100)::numeric(12,2) /$total)::numeric(12,2))::numeric(12,2) as percentual_prod
		FROM tbl_suggar_questionario
		JOIN tbl_os on tbl_os.os = tbl_suggar_questionario.os AND tbl_os.fabrica = $login_fabrica
		JOIN tbl_produto on tbl_produto.produto = tbl_os.produto
		WHERE $cond_data
		GROUP BY tbl_produto.referencia,
				 tbl_produto.descricao ,
				 tbl_os.produto
		ORDER BY total_prod ASC";

	if($total == 0){
		echo "<table border='0' cellpadding='4' cellspacing='1'  bgcolor='red' width='700' align='center' style='font-family: verdana; font-size: 11px'>";
		echo "<tr>";
		echo "<td  colspan='5'>Não foi encontrado Pesquisa com esses parametros.</td>";
		echo "</tr>";
		echo "</table>";
		exit;
	}

	echo "<table border='0' cellpadding='1' cellspacing='1' width='700' align='center' class='tabela'>";
	echo "<tr class='titulo_tabela'>";
	echo "<td  colspan='7'>Total de produtos pesquisados: $total</td>";
	echo "</tr>";
	echo "<tr class='titulo_coluna'>";
	echo "<td  align='center' width='70%'><B>Produto</B></td>";
	echo "<td align='center' width='15%'><B>Quantidade</B></td>";
	echo "<td  align='center' width='15%'><B>Percentual</B></td>";
	echo "<td  align='center' width='40'><B>Sim</B></td>";
	echo "<td  align='center' width='40'><B>Não</B></td>";
	echo "<td align='center' width='55' nowrap><B>Sim %</B></td>";
	echo "<td  align='center' width='55' nowrap><B>Não %</B></td>";
	echo "</tr>";

	$resx = pg_exec($con,$sqlx);
	for($x=0;pg_numrows($resx)>$x;$x++){
		$xxproduto           = pg_result($resx,$x,produto);
		$referencia         = pg_result($resx,$x,referencia);
		$descricao          = pg_result($resx,$x,descricao);
		$total_produto      = pg_result($resx,$x,total_prod);
		$percentual_produto = pg_result($resx,$x,percentual_prod);

		$sql="  SELECT 	CASE WHEN
							satisfeito is true then 'sim'
							else 'nao' end as sim_nao ,
						count(questionario) as qtde
				FROM tbl_suggar_questionario
				JOIN tbl_os ON tbl_os.os = tbl_suggar_questionario.os and tbl_os.produto=$xxproduto AND  tbl_os.fabrica=$login_fabrica
				JOIN tbl_produto on tbl_produto.produto = tbl_os.produto
				WHERE $cond_data
				GROUP BY satisfeito;";
		$res=pg_exec($con,$sql);
		
		if(pg_numrows($res)>0){
			$sim=0;
			$nao=0;
			$xsim=0;
			$xnao=0;
			for($y=0;pg_numrows($res)>$y;$y++){
				if(pg_result($res,$y,sim_nao)=="sim")$sim = pg_result($res,$y,qtde);
				if(pg_result($res,$y,sim_nao)=="nao")$nao = pg_result($res,$y,qtde);
			}
		}

		$xtotal = $sim + $nao;
		if($xtotal <>0) {
			$xsim = (($sim*100)/$xtotal);
			$xnao = (($nao*100)/$xtotal);
		}	
		$cor = ($x % 2) ? "#F7F5F0" : "#F1F4FA";
		echo "<tr bgcolor='$cor'>";
		echo "<td align='left' width='70%'><B><a href='$PHP_SELF?produto=$xxproduto&data_inicial=$x_data_inicial&data_final=$x_data_final&acao=listar' target='_blank'>$descricao</a> </td>";
		echo "<td align='center' width='15%'> $total_produto </td>";
		echo "<td align='center' width='15%'> $percentual_produto% </td>";
		echo "<td align='center' width='40'> $sim </td>";
		echo "<td  align='center' width='40'> $nao </td>";
		echo "<td  align='center' width='55'> ".number_format($xsim,1,",",".")."% </td>";
		echo "<td   align='center' width='55'> ".number_format($xnao,1,",",".")."% </td>";
		echo "</tr>";
	}

	echo "</table>";

} else {

	if(strlen($btn_acao)>0){
		if(strlen($produto)>0){
			$cond_os_produto = "tbl_os.produto = $produto";

			$sql = "select tbl_produto.referencia,
							tbl_produto.descricao, tbl_produto.produto
					FROM tbl_produto
								where tbl_produto.produto = $produto";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res)>0){
				$produto_referencia = pg_result($res,0,referencia);
				$produto_descricao = pg_result($res,0,descricao);
			}
		}else{
			//$produto_referencia = pg_result($res,0,referencia);
			$produto_descricao = " os Produtos da Suggar";
		}


		if (strlen($x_data_inicial) > 0 AND strlen($x_data_final) > 0){
			
			if(strlen($erro) == 0){
				if ($filtrar == 'os') {
					$cond_data = " tbl_os.data_abertura between '$x_data_inicial 00:00:00' and '$x_data_final 23:59:59' ";
				}else{
					$cond_data = " tbl_suggar_questionario.data_input between '$x_data_inicial 00:00:00' and '$x_data_final 23:59:59' ";
				}					
			}
		}

	//echo $produto;

	$sql = "select count(questionario) as total
			from tbl_suggar_questionario
			join tbl_os on tbl_os.os = tbl_suggar_questionario.os and $cond_os_produto AND tbl_os.fabrica=$login_fabrica
			WHERE $cond_data";
	$res = pg_exec($con,$sql);
	$total = pg_result($res,0,0);

	if($total == 0){
		echo "<table border='0' cellpadding='4' cellspacing='1'  bgcolor='red' width='600' align='center' style='font-family: verdana; font-size: 11px'>";
		echo "<tr>";
		echo "<td  colspan='5'>Não foi encontrado Pesquisa com esses parametros.</td>";
		echo "</tr>";
		echo "</table>";
		exit;
	}


	echo "<table border='0' cellpadding='1' cellspacing='1' width='700' align='center' class='tabela'>";
	echo "<tr >";
	echo "<td  colspan='5' class='titulo_tabela'><a href='$PHP_SELF?listarcliente=1&produto=$produto&data_inicial=$data_inicial&data_final=$data_final'><font color='#FFCC00' >Relação dos $total consumidor(es)</FONT></a></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td  colspan='5' class='titulo_tabela'>Total de consumidores consultados $total</td>";
	echo "</tr>";
	echo "<tr class='titulo_coluna'>";
	echo "<td align='center' width='60%'>Pergunta</td>";
	echo "<td align='center' width='40'>Sim</td>";
	echo "<td align='center' width='40'>Não</td>";
	echo "<td align='center' width='55' nowrap>Sim %</td>";
	echo "<td align='center' width='55' nowrap>Não %</td>";
	echo "</tr>";
	echo "</table>";

	echo "<table border='0' cellpadding='1' cellspacing='1'  width='700' align='center' class='tabela'>";
	echo "<tr>";

	$totalSim = [];
	$totalNao = [];

	echo "<td  align='left' colspan='5' class='subtitulo'>O que levou a escolher $produto_descricao ?</td>";
	echo "</tr>";

		$sql = "select 	case when
							preco is true then 'sim'
							else 'nao' end as sim_nao ,
						count(questionario) as qtde
				from tbl_suggar_questionario
				join tbl_os on tbl_os.os = tbl_suggar_questionario.os and $cond_os_produto AND  tbl_os.fabrica=$login_fabrica
				WHERE $cond_data
				AND preco is not null
				group by preco;";
		//echo 'Preco: ',$sql,'<hr />'; // xxx
		$res = pg_exec($con,$sql);
		$sim=0;
		$nao=0;
		$xsim=0;
		$xnao=0;
		if(pg_numrows($res)>0){
			for($x=0;pg_numrows($res)>$x;$x++){
				if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
				if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
			}
		}

		$totalSim["a"] = $sim;
		$totalNao["a"] = $nao;

		$escolha[0]=$sim;
		$xtotal = $sim + $nao;
		if($xtotal <>0) {
			$xsim = (($sim*100)/$xtotal);
			$xnao = (($nao*100)/$xtotal);
		}

	echo "<tr bgcolor='#F7F5F0'>";
	echo "<td align='left' width='60%'>a. Foi o preço</td>";
	echo "<td align='center' width='40'><B>$sim</B></td>";
	echo "<td align='center' width='40'><B>$nao</B></td>";
	echo "<td align='center' width='55'><B>".exibir_detalhes_pesquisa($xsim,'preco','sim')."%</B></td>";
	echo "<td align='center' width='55'><B>".exibir_detalhes_pesquisa($xnao,'preco','nao')."%</B></td>";
	echo "</tr>";

		$sql = "select 	case when
							qualidade is true then 'sim'
							else 'nao' end as sim_nao ,
						count(questionario) as qtde
				from tbl_suggar_questionario
				join tbl_os on tbl_os.os = tbl_suggar_questionario.os and $cond_os_produto AND  tbl_os.fabrica=$login_fabrica
				WHERE $cond_data
				AND qualidade is not null
				group by qualidade;";
		//echo 'Qualidade: ',$sql; // xxx
		$res = pg_exec($con,$sql);
		$sim=0;
		$nao=0;
		$xsim=0;
		$xnao=0;
	if(pg_numrows($res)>0){
		for($x=0;pg_numrows($res)>$x;$x++){
			if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
			if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
		}
	}

		$totalSim["b"] = $sim;
		$totalNao["b"] = $nao;

		$escolha[1]=$sim;
		$xtotal = $sim + $nao;
		if($xtotal <>0) {
			$xsim = (($sim*100)/$xtotal);
			$xnao = (($nao*100)/$xtotal);
		}
	echo "<tr bgcolor='#F1F4FA'>";
	echo "<td align='left' width='60%'>b. Foi a qualidade</td>";
	echo "<td align='center' width='40'><B>$sim</B></td>";
	echo "<td align='center' width='40'><B>$nao</B></td>";
	echo "<td align='center' width='55'><B>".exibir_detalhes_pesquisa($xsim,'qualidade','sim')."%</B></td>";
	echo "<td align='center' width='55'><B>".exibir_detalhes_pesquisa($xnao,'qualidade','nao')."%</B></td>";
	echo "</tr>";


		$sql = "select 	case when
							design is true then 'sim'
							else 'nao' end as sim_nao ,
						count(questionario) as qtde
				from tbl_suggar_questionario
				join tbl_os on tbl_os.os = tbl_suggar_questionario.os and $cond_os_produto AND  tbl_os.fabrica=$login_fabrica
				WHERE $cond_data
				AND design is not null
				group by design;";
		//echo $sql;
		$res = pg_exec($con,$sql);
		$sim=0;
		$nao=0;
		$xsim=0;
		$xnao=0;
	if(pg_numrows($res)>0){
		for($x=0;pg_numrows($res)>$x;$x++){
			if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
			if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
		}
	}

		$totalSim["c"] = $sim;
		$totalNao["c"] = $nao;

		$escolha[2]=$sim;
		$xtotal = $sim + $nao;
		if($xtotal <>0) {
			$xsim = (($sim*100)/$xtotal);
			$xnao = (($nao*100)/$xtotal);
		}
	echo "<tr bgcolor='#F7F5F0'>";
	echo "<td align='left' width='60%'>c. Foi o design</td>";
	echo "<td align='center' width='40'><B>$sim</B></td>";
	echo "<td align='center' width='40'><B>$nao</B></td>";
	echo "<td align='center' width='55'><B>".exibir_detalhes_pesquisa($xsim,'design','sim')."%</B></td>";
	echo "<td align='center' width='55'><B>".exibir_detalhes_pesquisa($xnao,'design','nao')."%</B></td>";
	echo "</tr>";

		$sql = "select 	case when
							tradicao is true then 'sim'
							else 'nao' end as sim_nao ,
						count(questionario) as qtde
				from tbl_suggar_questionario
				join tbl_os on tbl_os.os = tbl_suggar_questionario.os and $cond_os_produto AND  tbl_os.fabrica=$login_fabrica
				WHERE $cond_data
				AND tradicao is not null
				group by tradicao;";
		//echo $sql;
		$res = pg_exec($con,$sql);
		$sim=0;
		$nao=0;
		$xsim=0;
		$xnao=0;
	if(pg_numrows($res)>0){
		for($x=0;pg_numrows($res)>$x;$x++){
			if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
			if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
		}
	}

		$totalSim["d"] = $sim;
		$totalNao["d"] = $nao;

		$escolha[3]=$sim;
		$xtotal = $sim + $nao;
		if($xtotal <>0) {
			$xsim = (($sim*100)/$xtotal);
			$xnao = (($nao*100)/$xtotal);
		}
	echo "<tr bgcolor='#F1F4FA'>";
	echo "<td align='left' width='60%'>d. Foi a tradição da marca</td>";
	echo "<td align='center' width='40'><B>$sim</B></td>";
	echo "<td align='center' width='40'><B>$nao</B></td>";
	echo "<td align='center' width='55'><B>".exibir_detalhes_pesquisa($xsim,'tradicao','sim')."%</B></td>";
	echo "<td align='center' width='55'><B>".exibir_detalhes_pesquisa($xnao,'tradicao','nao')."%</B></td>";
	echo "</tr>";

		$sql = "select 	case when
							indicacao is true then 'sim'
							else 'nao' end as sim_nao ,
						count(questionario) as qtde
				FROM tbl_suggar_questionario
				JOIN tbl_os on tbl_os.os = tbl_suggar_questionario.os and $cond_os_produto AND  tbl_os.fabrica=$login_fabrica
				WHERE $cond_data
				AND indicacao is not null
				group by indicacao;";
		//echo $sql;
		$res = pg_exec($con,$sql);
		$sim=0;
		$nao=0;
		$xsim=0;
		$xnao=0;
	if(pg_numrows($res)>0){
		for($x=0;pg_numrows($res)>$x;$x++){
			if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
			if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
		}
	}

		$totalSim["e"] = $sim;
		$totalNao["e"] = $nao;

		$escolha[4]=$sim;
		$xtotal = $sim + $nao;
		if($xtotal <>0) {
			$xsim = (($sim*100)/$xtotal);
			$xnao = (($nao*100)/$xtotal);
		}
	echo "<tr bgcolor='#F7F5F0'>";
	echo "<td align='left' width='60%'>e. Foi por indicação</td>";
	echo "<td align='center' width='40'><B>$sim</B></td>";
	echo "<td align='center' width='40'><B>$nao</B></td>";
	echo "<td align='center' width='55'><B>".exibir_detalhes_pesquisa($xsim,'indicacao','sim')."%</B></td>";
	echo "<td align='center' width='55'><B>".exibir_detalhes_pesquisa($xnao,'indicacao','nao')."%</B></td>";
	echo "</tr>";

		$sql = "select 	case when
							tbl_suggar_questionario.capacidade is true then 'sim'
							else 'nao' end as sim_nao ,
						count(questionario) as qtde
				from tbl_suggar_questionario
				join tbl_os on tbl_os.os = tbl_suggar_questionario.os and $cond_os_produto AND  tbl_os.fabrica=$login_fabrica
				WHERE $cond_data
				and tbl_suggar_questionario.capacidade is not null
				group by tbl_suggar_questionario.capacidade;";
		//echo $sql;
		$res = pg_exec($con,$sql);
		$sim=0;
		$nao=0;
		$xsim=0;
		$xnao=0;
		if(pg_numrows($res)>0){
		for($x=0;pg_numrows($res)>$x;$x++){
			if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
			if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
		}
		}

		$totalSim["f"] = $sim;
		$totalNao["f"] = $nao;

		$escolha[5]=$sim;
		$xtotal = $sim + $nao;
		if($xtotal <>0) {
			$xsim = (($sim*100)/$xtotal);
			$xnao = (($nao*100)/$xtotal);
		}
	echo "<tr bgcolor='#F1F4FA'>";
	echo "<td align='left' width='60%'>f. Foi pela capacidade</td>";
	echo "<td align='center' width='40'><B>$sim</B></td>";
	echo "<td align='center' width='40'><B>$nao</B></td>";
	echo "<td align='center' width='55'><B>".exibir_detalhes_pesquisa($xsim,'capacidade','sim')."%</B></td>";
	echo "<td align='center' width='55'><B>".exibir_detalhes_pesquisa($xnao,'capacidade','nao')."%</B></td>";
	echo "</tr>";

		$sql = "select 	case when
							inovacao is true then 'sim'
							else 'nao' end as sim_nao ,
						count(questionario) as qtde
				from tbl_suggar_questionario
				join tbl_os on tbl_os.os = tbl_suggar_questionario.os and $cond_os_produto AND  tbl_os.fabrica=$login_fabrica
				WHERE $cond_data
				and inovacao is not null
				group by inovacao;";
		//echo $sql;
		$res = pg_exec($con,$sql);
		$sim=0;
		$nao=0;
		$xsim=0;
		$xnao=0;
		if(pg_numrows($res)>0){
		for($x=0;pg_numrows($res)>$x;$x++){
			if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
			if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
		}
		}

		$totalSim["g"] = $sim;
		$totalNao["g"] = $nao;

		$escolha[6]=$sim;
		$xtotal = $sim + $nao;
		if($xtotal <>0) {
			$xsim = (($sim*100)/$xtotal);
			$xnao = (($nao*100)/$xtotal);
		}
	echo "<tr bgcolor='#F7F5F0'>";
	echo "<td align='left' width='60%'>g. Foi por inovação</td>";
	echo "<td align='center' width='40'><B>$sim</B></td>";
	echo "<td align='center' width='40'><B>$nao</B></td>";
	echo "<td align='center' width='40'><B>".exibir_detalhes_pesquisa($xsim,'inovacao','sim')."%</B></td>";
	echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>".exibir_detalhes_pesquisa($xnao,'inovacao','nao')."%</B></td>";
	echo "</tr>";
	echo "</table>";
	
	echo "<BR>";
	echo "<BR>";

	?>
	<?php

	$totalSim = [];
	$totalNao = [];

	echo "<table border='0' cellpadding='1' cellspacing='1'  width='700' align='center' class='tabela'>";
	echo "<tr >";
	echo "<tr class='titulo_coluna'>";
	echo "<td align='center' width='60%'>Pergunta</td>";
	echo "<td align='center' width='40'>Sim</td>";
	echo "<td align='center' width='40'>Não</td>";
	echo "<td align='center' width='55' nowrap>Sim %</td>";
	echo "<td align='center' width='55' nowrap>Não %</td>";
	echo "</tr>";
	echo "<td class='subtitulo' align='left' colspan='5'><B>Com relação ao produto $produto_descricao</B></td>";
	echo "</tr>";
		$sql = "select 	case when
							satisfeito is true then 'sim'
							else 'nao' end as sim_nao ,
						count(questionario) as qtde
				from tbl_suggar_questionario
				join tbl_os on tbl_os.os = tbl_suggar_questionario.os and $cond_os_produto AND  tbl_os.fabrica=$login_fabrica
				WHERE $cond_data
				and satisfeito is not null
				group by satisfeito;";
		//echo $sql;
		$res = pg_exec($con,$sql);
		$sim=0;
		$nao=0;
		$xsim=0;
		$xnao=0;
		if(pg_numrows($res)>0){
		for($x=0;pg_numrows($res)>$x;$x++){
			if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
			if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
		}
		}

		$totalSim["a"] = $sim;
		$totalNao["a"] = $nao;

		$xtotal = $sim + $nao;
		if($xtotal <>0) {
			$xsim = (($sim*100)/$xtotal);
			$xnao = (($nao*100)/$xtotal);
		}
		$satisfeito_sim_nao[] = $sim ;
		$satisfeito_sim_nao[] = $nao ;

	echo "<tr bgcolor='#F7F5F0'>";
	echo "<td align='left' width='60%'>a. Satisfeito?</td>";
	echo "<td align='center' width='40'><B>$sim</B></td>";
	echo "<td align='center' width='40'>";
	echo "<B>$nao</B>";
	echo "</td>";
	echo "<td align='center' width='55'><B>".exibir_detalhes_pesquisa($xsim,'satisfeito','sim')."%</B></td>";
	echo "<td align='center' width='55'><B>".exibir_detalhes_pesquisa($xnao,'satisfeito','nao')."%</B></td>";
	echo "</tr>";
	echo "</table>";

	echo "<BR>";
	echo "<BR>";
	
	echo "<table border='0' cellpadding='1' cellspacing='1' width='700' align='center' class='tabela'>";
	echo "<tr class='titulo_coluna'>";
	echo "<td align='center' width='60%'>Pergunta</td>";
	echo "<td align='center' width='40'>Sim</td>";
	echo "<td align='center' width='40'>Não</td>";
	echo "<td align='center' width='55' nowrap>Sim %</td>";
	echo "<td align='center' width='55' nowrap>Não %</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='subtitulo' align='left' colspan='5'>b. Se satisfeito: Sua satisfação é com relação</td>";
	echo "</tr>";
		$sql = "select 	case when satisfeito_modo_usar is true then 'sim'
							when satisfeito_modo_usar is false then 'nao'
						end as sim_nao ,
						count(questionario) as qtde
				from tbl_suggar_questionario
				join tbl_os on tbl_os.os = tbl_suggar_questionario.os and $cond_os_produto AND  tbl_os.fabrica=$login_fabrica
				where satisfeito='t'
					AND $cond_data
				group by satisfeito_modo_usar;";
		//echo $sql;
		$res = pg_exec($con,$sql);

		$sim=0;
		$nao=0;
		$xsim=0;
		$xnao=0;
		if(pg_numrows($res)>0){
		for($x=0;pg_numrows($res)>$x;$x++){
			if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
			if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
		}
		}

		$totalSim["a"] = $sim;
		$totalNao["a"] = $nao;

		$xtotal = $sim + $nao;
		if($xtotal <> 0){
			$xsim = (($sim*100)/$xtotal);
			$xnao = (($nao*100)/$xtotal);
		}
		$satisfeito[0] = $sim;
		echo "</table>";

		$totalSim = [];
		$totalNao = [];

	echo "<table border='0' cellpadding='1' cellspacing='1'  width='700' align='center' class='tabela'>";
	echo "<tr bgcolor='#F7F5F0'>";
	echo "<td align='left' width='60%'>i. Modo de usar o produto</td>";
	echo "<td align='center' width='40'><B>$sim</B></td>";
	echo "<td align='center' width='40'><B>$nao</B></td>";
	echo "<td align='center' width='55'><B>".exibir_detalhes_pesquisa($xsim,'satisfeito_modo_usar','sim')."%</B></td>";
	echo "<td align='center' width='55'><B>".exibir_detalhes_pesquisa($xnao,'satisfeito_modo_usar','nao')."%</B></td>";
	echo "</tr>";
	$sql = "select 	case when satisfeito_manual is true then 'sim'
								when satisfeito_manual is false then 'nao'
							end as sim_nao ,
						count(questionario) as qtde
				from tbl_suggar_questionario
				join tbl_os on tbl_os.os = tbl_suggar_questionario.os and $cond_os_produto AND  tbl_os.fabrica=$login_fabrica
				where satisfeito='t'
					AND $cond_data
				group by satisfeito_manual;";
		//echo $sql;
		$res = pg_exec($con,$sql);
		$sim=0;
		$nao=0;
		$xsim=0;
		$xnao=0;
		if(pg_numrows($res)>0){

		for($x=0;pg_numrows($res)>$x;$x++){
			if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
			if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
		}
		}

		$totalSim["b"] = $sim;
		$totalNao["b"] = $nao;

		$xtotal = $sim + $nao;
		if($xtotal <> 0){
			$xsim = (($sim*100)/$xtotal);
			$xnao = (($nao*100)/$xtotal);
		}
		$satisfeito[1] = $sim;

	echo "<tr bgcolor='#F1F4FA'>";
	echo "<td align='left' width='60%'>ii. Manual de orientação</td>";
	echo "<td align='center' width='40'><B>$sim</B></td>";
	echo "<td align='center' width='40'><B>$nao</B></td>";
	echo "<td align='center' width='40'><B>".exibir_detalhes_pesquisa($xsim,'satisfeito_manual','sim')."%</B></td>";
	echo "<td align='center' width='40'><B>".exibir_detalhes_pesquisa($xnao,'satisfeito_manual','nao')."%</B></td>";
	echo "</tr>";
	$sql = "select 	case when
							satisfeito_energia is true then 'sim'
							 when
							satisfeito_energia is false then 'nao'
						end as sim_nao ,
						count(questionario) as qtde
				from tbl_suggar_questionario
				join tbl_os on tbl_os.os = tbl_suggar_questionario.os and $cond_os_produto AND  tbl_os.fabrica=$login_fabrica
				where satisfeito='t'
					AND $cond_data
				group by satisfeito_energia;";
		//echo $sql;
		$res = pg_exec($con,$sql);
		$sim=0;
		$nao=0;
		$xsim=0;
		$xnao=0;
		if(pg_numrows($res)>0){
		for($x=0;pg_numrows($res)>$x;$x++){
			if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
			if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
		}
		}

		$totalSim["c"] = $sim;
		$totalNao["c"] = $nao;

		$xtotal = $sim + $nao;
		if($xtotal <> 0){
			$xsim = (($sim*100)/$xtotal);
			$xnao = (($nao*100)/$xtotal);
		}
		$satisfeito[2] = $sim;

	echo "<tr bgcolor='#F7F5F0'>";
	echo "<td align='left'>iii. Consumo de energia</td>";
	echo "<td align='center' width='40'><B>$sim</B></td>";
	echo "<td align='center' width='40'><B>$nao</B></td>";
	echo "<td align='center' width='40'><B>".exibir_detalhes_pesquisa($xsim,'satisfeito_energia','sim')."%</B></td>";
	echo "<td align='center' width='40'><B>".exibir_detalhes_pesquisa($xnao,'satisfeito_energia','nao')."%</B></td>";
	echo "</tr>";

	$sql = "select 	case when
							satisfeito_barulho is true then 'sim'
							 when
							satisfeito_barulho is false then 'nao'
						end as sim_nao ,
						count(questionario) as qtde
				from tbl_suggar_questionario
				join tbl_os on tbl_os.os = tbl_suggar_questionario.os and $cond_os_produto AND  tbl_os.fabrica=$login_fabrica
				where satisfeito='t'
					AND $cond_data
				group by satisfeito_barulho;";
		//echo $sql;
		$res = pg_exec($con,$sql);
		$sim=0;
		$nao=0;
		$xsim=0;
		$xnao=0;
		if(pg_numrows($res)>0){
		for($x=0;pg_numrows($res)>$x;$x++){
			if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
			if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
		}
		}

		$totalSim["d"] = $sim;
		$totalNao["d"] = $nao;

		$xtotal = $sim + $nao;
		if($xtotal <> 0){
			$xsim = (($sim*100)/$xtotal);
			$xnao = (($nao*100)/$xtotal);
		}
		$satisfeito[3] = $sim;


	echo "<tr bgcolor='#F1F4FA'>";
	echo "<td align='left'>iv. Nível de ruído</td>";
	echo "<td align='center' width='40'><B>$sim</B></td>";
	echo "<td align='center' width='40'><B>$nao</B></td>";
	echo "<td align='center' width='40'><B>".exibir_detalhes_pesquisa($xsim,'satisfeito_barulho','sim')."%</B></td>";
	echo "<td align='center' width='40'><B>".exibir_detalhes_pesquisa($xnao,'satisfeito_barulho','nao')."%</B></td>";
	echo "</tr>";

	$sql = "select 	case when
							satisfeito_cor is true then 'sim'
							 when
							satisfeito_cor is false then 'nao'
						end as sim_nao ,
						count(questionario) as qtde
				from tbl_suggar_questionario
				join tbl_os on tbl_os.os = tbl_suggar_questionario.os and $cond_os_produto AND  tbl_os.fabrica=$login_fabrica
				where satisfeito='t'
					AND $cond_data
				group by satisfeito_cor;";
		//echo $sql;
		$res = pg_exec($con,$sql);

		$sim=0;
		$nao=0;
		$xsim=0;
		$xnao=0;
	if(pg_numrows($res)>0){

		for($x=0;pg_numrows($res)>$x;$x++){
			if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
			if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
		}
	}

		$totalSim["e"] = $sim;
		$totalNao["e"] = $nao;

		$xtotal = $sim + $nao;
		if($xtotal <> 0){
			$xsim = (($sim*100)/$xtotal);
			$xnao = (($nao*100)/$xtotal);
		}
		$satisfeito[4] = $sim;
	echo "<tr bgcolor='#F7F5F0'>";
	echo "<td align='left'>v. Cor do produto</td>";
	echo "<td align='center' width='40'><B>$sim</B></td>";
	echo "<td align='center' width='40'><B>$nao</B></td>";
	echo "<td align='center' width='40'><B>".exibir_detalhes_pesquisa($xsim,'satisfeito_cor','sim')."%</B></td>";
	echo "<td align='center' width='40'><B>".exibir_detalhes_pesquisa($xnao,'satisfeito_cor','nao')."%</B></td>";
	echo "</tr>";
	echo "</table>";

	echo "<BR>";
	echo "<BR>";
	echo "<BR>";

			?>
	<br />
	<?php

	$totalSim = [];
	$totalNao = [];
	
	echo "<table border='0' cellpadding='1' cellspacing='1' width='700' align='center' class='tabela'>";
	echo "<tr class='titulo_coluna'>";
	echo "<td align='center' width='60%'>Pergunta</td>";
	echo "<td align='center' width='40'>Sim</td>";
	echo "<td align='center' width='40'>Não</td>";
	echo "<td align='center' width='55' nowrap>Sim %</td>";
	echo "<td align='center' width='55' nowrap>Não %</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='subtitulo' align='left' colspan='5'><B>b. Se insatisfeito: Sua insatisfação é com relação</B></td>";
	echo "</tr>";
		$sql = "select 	case when
							insatisfeito_modo_usar is true then 'sim'
							 when
							insatisfeito_modo_usar is false then 'nao'
						end as sim_nao ,
						count(questionario) as qtde
				from tbl_suggar_questionario
				join tbl_os on tbl_os.os = tbl_suggar_questionario.os and $cond_os_produto AND  tbl_os.fabrica=$login_fabrica
				where satisfeito='f'
					AND $cond_data
				group by insatisfeito_modo_usar;";
		//echo $sql;
		$res = pg_exec($con,$sql);
		$sim=0;
		$nao=0;
		$xsim=0;
		$xnao=0;
		if(pg_numrows($res)>0){

			for($x=0;pg_numrows($res)>$x;$x++){
				if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
				if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
			}

			$totalSim["a"] = $sim;
			$totalNao["a"] = $nao;

			$xtotal = $sim + $nao;
			if($xtotal <> 0){
				$xsim = (($sim*100)/$xtotal);
				$xnao = (($nao*100)/$xtotal);
			}
		}

		$insatisfeito[0] = $sim;
	echo "<tr bgcolor='#F7F5F0'>";
	echo "<td align='left' width='60%'>i. Modo de usar o produto</td>";
	echo "<td align='center' width='40'><B>$sim</B></td>";
	echo "<td align='center' width='40'><B>$nao</B></td>";
	echo "<td align='center' width='55'><B>".exibir_detalhes_pesquisa($xsim,'insatisfeito_modo_usar','sim')."%</B></td>";
	echo "<td align='center' width='55'><B>".exibir_detalhes_pesquisa($xnao,'insatisfeito_modo_usar','nao')."%</B></td>";
	echo "</tr>";

		$sql = "SELECT 	case when
							insatisfeito_manual is true then 'sim'
							 when
							insatisfeito_manual is false then 'nao'
						end as sim_nao ,
						count(questionario) as qtde
				FROM tbl_suggar_questionario
				JOIN tbl_os on tbl_os.os = tbl_suggar_questionario.os and $cond_os_produto AND  tbl_os.fabrica=$login_fabrica
				WHERE satisfeito='f'
				AND $cond_data
				GROUP BY insatisfeito_manual;";
		//echo $sql;
		$res = pg_exec($con,$sql);
		$sim=0;
		$nao=0;
		$xsim=0;
		$xnao=0;
		if(pg_numrows($res)>0){
			for($x=0;pg_numrows($res)>$x;$x++){
				if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
				if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
			}

			$totalSim["b"] = $sim;
			$totalNao["b"] = $nao;

			$xtotal = $sim + $nao;
			if($xtotal <> 0){
				$xsim = (($sim*100)/$xtotal);
				$xnao = (($nao*100)/$xtotal);
			}
		}

		$insatisfeito[1] = $sim;
	echo "<tr bgcolor='#F1F4FA'>";
	echo "<td align='left'>ii. Manual de orientação</td>";
	echo "<td align='center' width='40'><B>$sim</B></td>";
	echo "<td align='center' width='40'><B>$nao</B></td>";
	echo "<td align='center' width='40'><B>".exibir_detalhes_pesquisa($xsim,'insatisfeito_manual','sim')."%</B></td>";
	echo "<td align='center' width='40'><B>".exibir_detalhes_pesquisa($xnao,'insatisfeito_manual','nao')."%</B></td>";
	echo "</tr>";

		$sql = "select 	case when
							insatisfeito_energia is true then 'sim'
							when
							insatisfeito_energia is false then 'nao'
						end as sim_nao ,
						count(questionario) as qtde
				from tbl_suggar_questionario
				join tbl_os on tbl_os.os = tbl_suggar_questionario.os and $cond_os_produto AND  tbl_os.fabrica=$login_fabrica
				where satisfeito='f'
					AND $cond_data
				group by insatisfeito_energia;";
		//echo $sql;
		$res = pg_exec($con,$sql);
		$sim=0;
		$nao=0;
		$xsim=0;
		$xnao=0;
		if(pg_numrows($res)>0){
			for($x=0;pg_numrows($res)>$x;$x++){
				if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
				if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
			}

			$totalSim["c"] = $sim;
			$totalNao["c"] = $nao;

			$xtotal = $sim + $nao;
			if($xtotal <> 0){
				$xsim = (($sim*100)/$xtotal);
				$xnao = (($nao*100)/$xtotal);
			}
		}

		$insatisfeito[2] = $sim;
	echo "<tr bgcolor='#F7F5F0'>";
	echo "<td align='left'>iii. Consumo de energia</td>";
	echo "<td align='center' width='40'><B>$sim</B></td>";
	echo "<td align='center' width='40'><B>$nao</B></td>";
	echo "<td align='center' width='40'><B>".exibir_detalhes_pesquisa($xsim,'insatisfeito_energia','sim')."%</B></td>";
	echo "<td align='center' width='40'><B>".exibir_detalhes_pesquisa($xnao,'insatisfeito_energia','nao')."%</B></td>";
	echo "</tr>";

		$sql = "select 	case when
							insatisfeito_barulho is true then 'sim'
							when
							insatisfeito_barulho is true then 'nao'
						end as sim_nao ,
						count(questionario) as qtde
				from tbl_suggar_questionario
				join tbl_os on tbl_os.os = tbl_suggar_questionario.os and $cond_os_produto AND  tbl_os.fabrica=$login_fabrica
				where satisfeito='f'
					AND $cond_data
				group by insatisfeito_barulho;";
		//echo $sql;
		$res = pg_exec($con,$sql);
		$sim=0;
		$nao=0;
		$xsim=0;
		$xnao=0;
		if(pg_numrows($res)>0){
			for($x=0;pg_numrows($res)>$x;$x++){
				if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
				if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
			}

			$totalSim["d"] = $sim;
			$totalNao["d"] = $nao;

			$xtotal = $sim + $nao;
			if(($sim> 0 or $nao >0) and $xtotal <>0 ) {
				$xsim = (($sim*100)/$xtotal);
				$xnao = (($nao*100)/$xtotal);
			} else {
				$xsim = 0;
				$xnao = 0;
			}

		}

		$insatisfeito[3] = $sim;
	echo "<tr bgcolor='#F1F4FA'>";
	echo "<td align='left'>iv. Nível de ruído</td>";
	echo "<td align='center' width='40'><B>$sim</B></td>";
	echo "<td align='center' width='40'><B>$nao</B></td>";
	echo "<td align='center' width='40'><B>".exibir_detalhes_pesquisa($xsim,'insatisfeito_barulho','sim')."%</B></td>";
	echo "<td align='center' width='40'><B>".exibir_detalhes_pesquisa($xnao,'insatisfeito_baruhlo','nao')."%</B></td>";
	echo "</tr>";

		$sql = "select 	case when
							insatisfeito_cor is true then 'sim'
							when
							insatisfeito_cor is false then 'nao'
						end as sim_nao ,
						count(questionario) as qtde
				from tbl_suggar_questionario
				join tbl_os on tbl_os.os = tbl_suggar_questionario.os and $cond_os_produto AND  tbl_os.fabrica=$login_fabrica
				where satisfeito='f'
					AND $cond_data
				group by insatisfeito_cor;";
		//echo $sql;
		$res = pg_exec($con,$sql);
		$sim=0;
		$nao=0;
		$xsim=0;
		$xnao=0;
		if(pg_numrows($res)>0){
			for($x=0;pg_numrows($res)>$x;$x++){
				if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
				if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
			}

			$totalSim["e"] = $sim;
			$totalNao["e"] = $nao;

			$xtotal = $sim + $nao;

			if($xtotal <> 0){
				$xsim = (($sim*100)/$xtotal);
				$xnao = (($nao*100)/$xtotal);
			}

			$insatisfeito[4] = $sim;
			echo "<tr  bgcolor='#F7F5F0'>";
			echo "<td align='left'>v. Cor do produto</td>";
			echo "<td align='center' width='40'><B>$sim</B></td>";
			echo "<td align='center' width='40'><B>$nao</B></td>";
			echo "<td align='center' width='40'><B>".exibir_detalhes_pesquisa($xsim,'insatisfeito_cor','sim')."%</B></td>";
			echo "<td align='center' width='40'><B>".exibir_detalhes_pesquisa($xnao,'insatisfeito_cor','nao')."%</B></td>";
			echo "</tr>";
		}
		$sql = "select 	case when
							insatisfeito_quebra_uso is true then 'sim'
							when
							insatisfeito_quebra_uso is false then 'nao'
						end as sim_nao ,
						count(questionario) as qtde
				from tbl_suggar_questionario
				join tbl_os on tbl_os.os = tbl_suggar_questionario.os and $cond_os_produto AND  tbl_os.fabrica=$login_fabrica
				where satisfeito='f'
					AND $cond_data
				group by insatisfeito_quebra_uso;";
		//echo $sql;
		$res = pg_exec($con,$sql);
		$sim=0;
		$nao=0;
		$xsim=0;
		$xnao=0;
		if(pg_numrows($res)>0){
			for($x=0;pg_numrows($res)>$x;$x++){
				if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
				if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
			}

			$totalSim["f"] = $sim;
			$totalNao["f"] = $nao;

			$xtotal = $sim + $nao;
			if($xtotal <>0) {
				$xsim = (($sim*100)/$xtotal);
				$xnao = (($nao*100)/$xtotal);
			}

			$insatisfeito[5] = $sim;

			echo "<tr bgcolor='#F1F4FA'>";
			echo "<td align='left'>vi. Quebrou com pouco uso</td>";
			echo "<td align='center' width='40'><B>$sim</B></td>";
			echo "<td align='center' width='40'><B>$nao</B></td>";
			echo "<td align='center' width='40'><B>".exibir_detalhes_pesquisa($xsim,'insatisfeito_quebra_uso','sim')."%</B></td>";
			echo "<td align='center' width='40'><B>".exibir_detalhes_pesquisa($xnao,'insatisfeito_quebra_uso','nao')."%</B></td>";
			echo "</tr>";
			echo "</table>";

		}
		echo "<BR>";
		echo "<br>";	

		?>
		<br />
		<?php

		$totalSim = [];
		$totalNao = [];

		echo "<table border='0' cellpadding='4' cellspacing='1' width='700' align='center' class='tabela'>";
		echo "<tr class='titulo_coluna'>";
		echo "<td align='center' width='60%'>Pergunta</td>";
		echo "<td align='center' width='40'>Sim</td>";
		echo "<td align='center' width='40'>Não</td>";
		echo "<td align='center' width='55' nowrap>Sim %</td>";
		echo "<td align='center' width='55' nowrap>Não %</td>";
		echo "</tr>";
		
		echo "<tr>";
		echo "<td class='subtitulo' align='left' colspan='5'><B>Com relação ao atendimento da autorizada</B></td>";
		echo "</tr>";

		$sql = "select 	case when
							atendimento_rapido is true then 'sim'
							else 'nao' end as sim_nao ,
						count(questionario) as qtde
				from tbl_suggar_questionario
				join tbl_os on tbl_os.os = tbl_suggar_questionario.os and $cond_os_produto AND  tbl_os.fabrica=$login_fabrica
				WHERE $cond_data
				group by atendimento_rapido;";
		//echo $sql;
		$res = pg_exec($con,$sql);
		$sim=0;
		$nao=0;
		$xsim=0;
		$xnao=0;
		if(pg_numrows($res)>0){
			for($x=0;pg_numrows($res)>$x;$x++){
				if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
				if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
			}
		}

		$totalSim["a"] = $sim;
		$totalNao["a"] = $nao;

		$xtotal = $sim + $nao;
		if($xtotal <>0) {
			$xsim = (($sim*100)/$xtotal);
			$xnao = (($nao*100)/$xtotal);
		}
		$atendimento_rapido[] = $sim;
		$atendimento_rapido[] = $nao;
	echo "<tr bgcolor='#F7F5F0'>";
	echo "<td align='left' width='60%'>a. O atendimento foi rápido?</td>";
	echo "<td align='center' width='40'><B>$sim</B></td>";
	echo "<td align='center' width='40'><B>$nao</B></td>";
	echo "<td align='center' width='55'><B>".exibir_detalhes_pesquisa($xsim,'atendimento_rapido','sim')."%</B></td>";
	echo "<td align='center' width='55'><B>".exibir_detalhes_pesquisa($xnao,'atendimento_rapido','nao')."%</B></td>";
	echo "</tr><BR /><BR />";

		$sql = "select 	case when
							confianca is true then 'sim'
							else 'nao' end as sim_nao ,
						count(questionario) as qtde
				from tbl_suggar_questionario
				join tbl_os on tbl_os.os = tbl_suggar_questionario.os and $cond_os_produto AND  tbl_os.fabrica=$login_fabrica
				WHERE $cond_data
				group by confianca;";
		//echo $sql;
		$res = pg_exec($con,$sql);
		$sim=0;
		$nao=0;
		$xsim=0;
		$xnao=0;
		if(pg_numrows($res)>0){
			for($x=0;pg_numrows($res)>$x;$x++){
				if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
				if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
			}
		}

		$totalSim["b"] = $sim;
		$totalNao["b"] = $nao;

		$xtotal = $sim + $nao;
		if($xtotal <>0) {
			$xsim = (($sim*100)/$xtotal);
			$xnao = (($nao*100)/$xtotal);
		}
		$confianca[] = $sim;
		$confianca[] = $nao;
	echo "<tr bgcolor='#F1F4FA'>";
	echo "<td align='left'>b. O aspecto da loja, gerou confiança?</td>";
	echo "<td align='center' width='40'><B>$sim</B></td>";
	echo "<td align='center' width='40'><B>$nao</B></td>";
	echo "<td align='center' width='40'><B>".exibir_detalhes_pesquisa($xsim,'confianca','sim')."%</B></td>";
	echo "<td align='center' width='40'><B>".exibir_detalhes_pesquisa($xnao,'confianca','nao')."%</B></td>";
	echo "</tr>";

		$sql = "select 	case when
							problema_resolvido is true then 'sim'
							else 'nao' end as sim_nao ,
						count(questionario) as qtde
				from tbl_suggar_questionario
				join tbl_os on tbl_os.os = tbl_suggar_questionario.os and $cond_os_produto AND  tbl_os.fabrica=$login_fabrica
				WHERE $cond_data
				group by problema_resolvido;";
		//echo $sql;
		$res = pg_exec($con,$sql);
		$sim=0;
		$nao=0;
		$xsim=0;
		$xnao=0;
		if(pg_numrows($res)>0){
			for($x=0;pg_numrows($res)>$x;$x++){
				if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
				if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
			}
		}

		$totalSim["c"] = $sim;
		$totalNao["c"] = $nao;

		$xtotal = $sim + $nao;
		if($xtotal <>0) {
			$xsim = (($sim*100)/$xtotal);
			$xnao = (($nao*100)/$xtotal);
		}
		$problema_resolvido[] = $sim;
		$problema_resolvido[] = $nao;
	echo "<tr bgcolor='#F7F5F0'>";
	echo "<td align='left'>c. O problema foi resolvido?</td>";
	echo "<td align='center' width='40'><B>$sim</B></td>";
	echo "<td align='center' width='40'><B>$nao</B></td>";
	echo "<td align='center' width='40'><B>".exibir_detalhes_pesquisa($xsim,'problema_resolvido','sim')."%</B></td>";
	echo "<td align='center' width='40'><B>".exibir_detalhes_pesquisa($xnao,'problema_resolvido','nao')."%</B></td>";
	echo "</tr>";
		$sql = "select 	sum(nota) as nota
				from tbl_suggar_questionario
				join tbl_os on tbl_os.os = tbl_suggar_questionario.os and $cond_os_produto
				WHERE {$cond_data}";
	//	echo $sql;
		$res = pg_exec($con,$sql);
		$nota = pg_result($res,0,0);
		$nota = $nota/$total;
		$nota = $nota;
	echo "<tr bgcolor='#F1F4FA'>";
	echo "<td align='left'>d. De 0 a 10, qual nota daria ao posto autorizado?</td>";
	echo "<td align='center' width='40' colspan='4'><B>Média: ".number_format($nota,2,",",".")."</B></td>";
	echo "</tr>";
		$sql = "select 	sum(nota_produto) as nota_produto
				from tbl_suggar_questionario
				join tbl_os on tbl_os.os = tbl_suggar_questionario.os and $cond_os_produto
				WHERE $cond_data";
		//echo $sql;
		$res = pg_exec($con,$sql);
		$nota_produto = pg_result($res,0,0);
		$nota_produto = $nota_produto/$total;
		$nota_produto = $nota_produto;
	echo "<tr bgcolor='#F7F5F0'>";
	echo "<td align='left'>e. De 0 a 10, qual nota daria para $produto_descricao?</td>";
	echo "<td align='center' width='40' colspan='4'><B>Média: ".number_format($nota_produto,2,",",".")."</B></td>";
	echo "</tr>";
	echo "</table>";

	echo "<BR>";
	}

}

$listarcliente= $_GET['listarcliente'];
if($listarcliente == 1){
	$x_data_inicial = trim($_GET["data_inicial"]);
	$x_data_final = trim($_GET["data_final"]);
	$produto= $_GET['produto'];

	if(strlen($produto)>0){
			$cond_os_produto = "tbl_os.produto = $produto";

			$sql = "select tbl_produto.referencia,
							tbl_produto.descricao, tbl_produto.produto
					FROM tbl_produto
								where tbl_produto.produto = $produto";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res)>0){
				$produto_referencia = pg_result($res,0,referencia);
				$produto_descricao = pg_result($res,0,descricao);
			}
		}
	if (strlen($x_data_inicial) > 0 AND strlen($x_data_final) > 0){
		
		if(strlen($erro) == 0){
			if ($filtrar == 'os') {
				$cond_data = " tbl_os.data_abertura between '$x_data_inicial 00:00:00' and '$x_data_final 23:59:59' ";
			}else{
				$cond_data = " tbl_suggar_questionario.data_input between '$x_data_inicial 00:00:00' and '$x_data_final 23:59:59' ";
			}			
		}
	}





	$sql="SELECT DISTINCT  tbl_os.os                ,
				 tbl_os.consumidor_nome   ,
				 tbl_os.consumidor_cidade ,
				 tbl_os.consumidor_estado
			FROM tbl_suggar_questionario
			JOIN tbl_os using(os)
			WHERE $cond_os_produto
			AND $cond_data";
	echo "<table border='0' cellpadding='2' cellspacing='1'  width='700' align='center' class='tabela'>";
	echo "<tr class='titulo_coluna'>";
	echo "<TD>	";
	echo "OS";
	echo "</td>";
	echo "<TD>";
 	echo "Consumidor";
	echo "</td>";
	echo"<TD>";
	echo "Cidade";
	echo "</td>";
	echo"<TD>";
	echo "Estado";
	echo "</td>";
	echo "</tr>";

	$res = @pg_exec($con,$sql);
	for($i=0; $i < pg_numrows($res); $i++){
		$os           = pg_result($res,$i,os);
		$nome         = pg_result($res,$i,consumidor_nome);
		$cidade       = pg_result($res,$i,consumidor_cidade);
		$estado       = pg_result($res,$i,consumidor_estado);

		echo "<tr>\n";
		echo "<td bgcolor='#FFFFFF' align='center'><a href='os_press.php?os=$os' target='blank'>$os</a></td>\n";
		echo "<td bgcolor='#FFFFFF' align='center'>$nome</td>\n";
		echo "<td bgcolor='#FFFFFF' align='center'>$cidade</td>\n";
		echo "<td bgcolor='#FFFFFF' align='center'>$estado</td>\n";
		echo "</tr>\n";
	}
	echo "</table>";
}
}//end if validação da data
?>

<br>

<? include "rodape.php" ?>
