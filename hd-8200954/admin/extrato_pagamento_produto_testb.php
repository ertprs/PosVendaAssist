<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';
include 'funcoes.php';
include "monitora.php";
$layout_menu = "financeiro";
$title = "RELATÓRIO CUSTO X PRODUTO";


flush();


$data_inicial = $_POST['data_inicial'];
$data_final   = $_POST['data_final']  ;

//if (strlen($_GET['data_inicial']) > 0) $data_inicial = $_GET['data_inicial'];
//if (strlen($_GET['data_final']) > 0)   $data_final   = $_GET['data_final']  ;

if($_POST['btn_gravar']){
	if($data_inicial == "")
		$msg_erro = "Data Inválida.";

	elseif(strlen($data_final)== 0)
		$msg_erro = "Data Inválida.";

	if(strlen($msg_erro) == 0){
	//Início Validação de Datas
	
	$d_ini = explode ("/", $data_inicial);//tira a barra
	$nova_data_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...
	$d = $d_ini[0];
		$m = $d_ini[1];
		$y = $d_ini[2];
		if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida.";

	$d_fim = explode ("/", $data_final);//tira a barra
	$nova_data_final = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...
	$d = $d_fim[0];
		$m = $d_fim[1];
		$y = $d_fim[2];
		if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida.";
	
	if(strlen($msg_erro)== 0){
		if($nova_data_final < $nova_data_inicial){
			$msg_erro = "Data Inválida.";
		}
	}
	
	if(strlen($msg_erro)== 0){
		$nova_data_inicial = mktime(0,0,0,$d_ini[1],$d_ini[0],$d_ini[2]); // timestamp da data inicial
		$nova_data_final = mktime(0,0,0,$d_fim[1],$d_fim[0],$d_fim[2]); // timestamp da data final
		$cont = 0;
		while($nova_data_inicial <= $nova_data_final){//enquanto uma data for inferior a outra {      
		  $nova_data_inicial += 86400; // adicionando mais 1 dia (em segundos) na data inicial
		  $cont++;
		}

		if($cont > 30){
			$msg_erro="O intervalo entre as datas não pode ser maior que 30 dias.";
		}
	}

	//Fim Validação de Datas
}
}


include 'cabecalho.php';

?>

<style type="text/css">
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
	BACKGROUND-COLOR: #FFFFFF
}
.Exibe{
	font-family: Arial, Helvetica, sans-serif;
	font-size: 8 px;
	font-weight: none;
	color: #000000;
	text-align: center;
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

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
}
</style>


<script language="javascript" src="js/jquery.js"></script>
<script language="javascript" src="js/maskedinput.jquery.js"></script>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>
<? include "javascript_pesquisas.php";
   include "javascript_calendario.php";
?>
<FORM name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center'>
<table width='700' class='formulario'  border='0' cellpadding='5' cellspacing='1' align='center'>
<? if(strlen($msg_erro) > 0){ ?>
	<tr class="msg_erro">
		<td><? echo $msg_erro; ?></td>
	</tr>
<? } ?>
<tr>
	<td class='titulo_tabela'>Parâmetros de Pesquisa</td>
</tr>
	
<tr>
<td>
	<table width='100%' border='0' cellspacing='1' cellpadding='2' class='Conteudo'>
		<tr>
			<td width="10">&nbsp;</td>
			<td align='right' nowrap><font size='2'>Data Inicial</td>
			<td align='left'>
				<input type="text" id="data_inicial" name="data_inicial" size="12" maxlength="10" class='frm' value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
				
			</td>
			<td align='right' nowrap><font size='2'>Data Final</td> 
			<td align='left'>
				<input type="text" id="data_final" name="data_final" size="12" maxlength="10" class='frm' value="<? if (strlen($data_final) > 0) echo $data_final; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
				
			</td>
			<td width="10">&nbsp;</td>
		</tr>
<? if($login_fabrica==20){//hd 2003 takashi ?>

	<tr>
		<TD  style="width: 10px">&nbsp;</TD>
		<td  align='right' nowrap ><font size='2'>Ref. Produto</font></td>
		<td align='left'>
		<input class="frm" type="text" name="produto_referencia" size="10" class='Caixa' maxlength="20" value="<? echo $produto_referencia ?>" > 
		
		</td>
		<td align='right' nowrap  ><font size='2'>Descrição</font></td>
		<td  align='left'>
		<input class="frm" type="text" name="produto_descricao" size="10" class='Caixa' value="<? echo $produto_descricao ?>" >
		</TD>
	</tr>
<? } ?>

	</table>
	<center><br><input type='submit' name='btn_gravar' value='Consultar'><input type='hidden' name='acao' value=$acao></center>
</td>
</tr>
</table>

<?
	if(strlen($msg_erro)==0){
	$data_inicial = str_replace (" " , "" , $data_inicial);
	$data_inicial = str_replace ("-" , "" , $data_inicial);
	$data_inicial = str_replace ("/" , "" , $data_inicial);
	$data_inicial = str_replace ("." , "" , $data_inicial);

	$data_final   = str_replace (" " , "" , $data_final)  ;
	$data_final   = str_replace ("-" , "" , $data_final)  ;
	$data_final   = str_replace ("/" , "" , $data_final)  ;
	$data_final   = str_replace ("." , "" , $data_final)  ;

	if (strlen ($data_inicial) == 6) $data_inicial = substr ($data_inicial,0,4) . "20" . substr ($data_inicial,4,2);
	if (strlen ($data_final)   == 6) $data_final   = substr ($data_final  ,0,4) . "20" . substr ($data_final  ,4,2);

	if (strlen ($data_inicial) > 0)  $data_inicial = substr ($data_inicial,0,2) . "/" . substr ($data_inicial,2,2) . "/" . substr ($data_inicial,4,4);
	if (strlen ($data_final)   > 0)  $data_final   = substr ($data_final,0,2)   . "/" . substr ($data_final,2,2)   . "/" . substr ($data_final,4,4);


	if(strlen($data_inicial) > 0 AND strlen($data_final)>0){

	$produto_referencia = trim($_POST['produto_referencia']);
		$produto_descricao  = trim($_POST['produto_descricao']) ;
		
		if(strlen($produto_referencia)>0 and strlen($produto_descricao)>0){ // HD 2003 TAKASHI
			$sql = "SELECT produto 
					from tbl_produto 
					join tbl_familia using(familia)
					where tbl_familia.fabrica = $login_fabrica
					and tbl_produto.referencia = '$produto_referencia'";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res)>0){
				$produto = pg_result($res,0,produto);
			}

		}
		$cond_4 = " and 1=1 "; // HD 2003 TAKASHI
		if (strlen ($produto)  > 0) $cond_4 = " and tbl_os.produto    = $produto "; // HD 2003 TAKASHI

	$sql = "SELECT  tbl_os.sua_os                                                         ,
			tbl_os.serie                                                          ,
			tbl_os.mao_de_obra                                                    ,
			tbl_os.pecas                                                          ,
			tbl_os.solucao_os                                                     ,
			tbl_produto.descricao                            AS produto_descricao ,
			tbl_produto.referencia                           AS produto_referencia,
			tbl_defeito_constatado.codigo                    AS defeito_codigo    ,
			tbl_defeito_constatado.descricao                 AS defeito_descricao ,
			tbl_causa_defeito.codigo                         AS causa_codigo      ,
			tbl_causa_defeito.descricao                      AS causa_descricao   ,
			(tbl_os.mao_de_obra + tbl_os.pecas)              AS total             ,
			to_char (tbl_extrato_extra.exportado,'DD/MM/YY') AS data_exportado    ,
			to_char (tbl_extrato.data_geracao,'DD/MM/YY')    AS data_geracao
		FROM tbl_os
		JOIN tbl_produto            USING (produto)
		JOIN tbl_os_extra           USING (os)
		JOIN tbl_extrato            ON tbl_extrato.extrato = tbl_os_extra.extrato
		JOIN tbl_extrato_extra      ON tbl_extrato_extra.extrato = tbl_os_extra.extrato
		LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
		LEFT JOIN tbl_causa_defeito      ON tbl_causa_defeito.causa_defeito           = tbl_os.causa_defeito
		WHERE tbl_extrato.fabrica = $login_fabrica $cond_4 ";


		if (strlen ($data_inicial) < 8) $data_inicial = date ("d/m/Y");
			$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);
		
		if (strlen ($data_final) < 8) $data_final = date ("d/m/Y");
			$x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);
		
		if($login_fabrica <> 20){
			if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0)
				$sql .= " AND tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
		}else{
			if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0)
				$sql .= " AND tbl_extrato_extra.exportado BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
		}
		$sql .= " ORDER BY tbl_produto.descricao ";


		// ##### PAGINACAO ##### //
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
		
		// ##### PAGINACAO ##### //
		
		
		
		
		if (pg_numrows($res) > 0) {

			echo "<br><table border='0' class='tabela' cellspacing='2' cellpadding='2' align='center'>";
			echo "<tr>";
			echo "<td><img src='imagens/excell.gif'></td><td align='left'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='extrato_pagamento_produto-xls_hmlg.php?btnacao=filtrar&data_inicial=$data_inicial&data_final=$data_final&produto_referencia=$produto_referencia' target='_blank'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a><br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
			echo "</tr>";
			echo "</table>";
			echo "<br><table border='0' class='tabela' cellpadding='2' cellspacing='1'  align='center' >";
			echo "<tr class='titulo_coluna' height='25' >";
		if($login_fabrica<>20){	echo "<td >OS</td>";}
			echo "<td >Produto</td>";
			echo "<td >Série</td>";
		if($login_fabrica<>20){	echo "<td >";
			if($login_fabrica ==20) echo "Reparo";
			else                    echo "Defeito Constatado";
			echo "</td>";
	}
			if($login_fabrica == 20){
				 echo "<td >Identificação</td>";
				 echo "<td >Defeito</td>";
			}
			echo "<td >Data Pagamento</td>";
			echo "<td >M.O</td>";
			echo "<td >Peças</td>";
			echo "<td >Total</td>";
			echo "</tr>";
		
			for ($i=0; $i<pg_numrows($res); $i++){
		
				$sua_os                  = trim(pg_result($res,$i,sua_os))            ;
				$produto_referencia      = trim(pg_result($res,$i,produto_referencia));
				$produto_descricao       = trim(pg_result($res,$i,produto_descricao)) ;
				$serie                   = trim(pg_result($res,$i,serie))             ;
				$solucao_os              = trim(pg_result($res,$i,solucao_os))        ;
				$defeito_codigo          = trim(pg_result($res,$i,defeito_codigo))    ;
				$defeito_descricao       = trim(pg_result($res,$i,defeito_descricao)) ;
				$causa_codigo            = trim(pg_result($res,$i,causa_codigo))      ;
				$causa_descricao         = trim(pg_result($res,$i,causa_descricao))   ;
				$mao_de_obra             = trim(pg_result($res,$i,mao_de_obra))       ;
				$pecas                   = trim(pg_result($res,$i,pecas))             ;
				$total                   = trim(pg_result($res,$i,total))             ;
				$data_geracao            = trim(pg_result($res,$i,data_geracao))      ;
				$data_exportado          = trim(pg_result($res,$i,data_exportado))    ;

				if($cor=="#F1F4FA")$cor = '#F7F5F0';
				else               $cor = '#F1F4FA';
		
				$pecas       = number_format ($pecas,2,",",".")      ;
				$mao_de_obra = number_format ($mao_de_obra,2,",",".");
				$total       = number_format ($total,2,",",".")      ;



				echo "<tr>";
			if($login_fabrica<>20){	echo "<td bgcolor='$cor' >$sua_os</td>";}
				echo "<td bgcolor='$cor' align='left' title='$produto_descricao'>$produto_referencia - ".substr($produto_descricao,0,20)."</td>";
				echo "<td bgcolor='$cor' >$serie</td>";
			if($login_fabrica<>20){	echo "<td bgcolor='$cor' align='left'>$defeito_codigo - $defeito_descricao</td>";}

				if($login_fabrica==20){
					$xsolucao="";
					if(strlen($solucao_os)>0){
						$xsql="SELECT descricao from tbl_servico_realizado where servico_realizado= $solucao_os limit 1";
						$xres = pg_exec($con, $xsql);
						$xsolucao = trim(pg_result($xres,0,descricao));
					}
					echo "<td bgcolor='$cor' align='left'>$xsolucao</td>";
					echo "<td bgcolor='$cor' align='left'>$causa_codigo- $causa_descricao</td>";
				}
				echo "<td bgcolor='$cor' align='left'>";
				if($login_fabrica == 20) echo "$data_exportado";
				else                     echo "$data_geracao";
				echo "</td>";
				echo "<td bgcolor='$cor' align='right'>R$ $mao_de_obra</td>";
				echo "<td bgcolor='$cor' align='right'>R$ $pecas</td>";
				echo "<td bgcolor='$cor' align='right'>R$ $total</td>";
				echo "</tr>";
			}
			echo "</table>";
		}
		else{
			echo "<center>Nenhum Produto Encontrado.</center>";
		}
	### PÉ PAGINACAO###

		echo "<table border='0' align='center'>";
		echo "<tr>";
		echo "<td colspan='9' align='center'>";

		// ##### PAGINACAO ##### //

		// links da paginacao
		echo "<br>";

		if($pagina < $max_links) {
			$paginacao = pagina + 1;
		}else{
			$paginacao = pagina;
		}

		// paginacao com restricao de links da paginacao

		// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
		$todos_links		= $mult_pag->Construir_Links("strings", "sim");

		// função que limita a quantidade de links no rodape
		$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

		for ($n = 0; $n < count($links_limitados); $n++) {
			echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
		}



		$resultado_inicial = ($pagina * $max_res) + 1;
		$resultado_final   = $max_res + ( $pagina * $max_res);
		$registros         = $mult_pag->Retorna_Resultado();

		$valor_pagina   = $pagina + 1;
		$numero_paginas = intval(($registros / $max_res) + 1);

		if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

		if ($registros > 0){
			echo "<br>";
			echo "<font size='2'>Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.</font>";
			echo "<font color='#cccccc' size='1'>";
			echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
			echo "</font>";
			echo "</div>";
		}
		// ##### PAGINACAO ##### //
		echo "</td>";
		echo "</tr>";

		echo "</table>";
		
	}

}

include 'rodape.php';
?>

