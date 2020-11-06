<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
/*	ESSE PROGRAMA É PARECIDO COM O EMBARQUE_NOTA_FISCAL, MAS NÃO TEM A PARTE DE FATURAMENTO.
	É PARA GERAR O ARQUIVO DE IMPRESSÃO DE NOTAS DE DEVOLUÇÃO
	IGOR - HD 5501 28/12/2007
*/


/* Importar Notas Avulsas */
$nota_fiscal = $_POST['nota_fiscal'];
if (strlen($nota_fiscal)==0){
	$nota_fiscal    = $_GET['nota_fiscal'];
}

$copia_nota_fiscal = $nota_fiscal;

if (strlen ($nota_fiscal) > 0) {
	$qtde_embarques = 0;
	$Importar  = "1";
}

/* Variaveis Diversas */
$taxa_administrativa = 1 ;

$msg_erro = "";

if ($Importar=='1' AND strlen ($copia_nota_fiscal) > 0) {

	$qtde_volume = $_GET['qtde_volume'];
	if(strlen($qtde_volume)==0) $msg_erro.=	" Preencher o volume.";
	$valor_frete  = $_GET['valor_frete'];
	if(strlen($valor_frete)==0) $msg_erro.=	" Preencher o valor do frete.";
	$transportadora = $_GET['transportadora'];
	if(strlen($transportadora)==0) $msg_erro.=	" Selecionar a transportadora.";

	$qtde_volume = str_replace (",","",$qtde_volume);
	$qtde_volume = str_replace (".","",$qtde_volume);

	$valor_frete = str_replace (",",".",$valor_frete);

	$sql = "SELECT * 
			FROM tbl_faturamento 
			WHERE nota_fiscal  = '$copia_nota_fiscal' 
				AND distribuidor = $login_posto 
				AND fabrica IN (".implode(",", $fabricas).")";

	$resNF = pg_exec ($con,$sql);
	
	if(pg_numrows($resNF)>0){

		$sql = "
		BEGIN; 
		
		UPDATE tbl_faturamento 
					SET qtde_volume = $qtde_volume,
						valor_frete = $valor_frete,
						transportadora =$transportadora
				WHERE nota_fiscal  = '$copia_nota_fiscal' 
					AND distribuidor = $login_posto 
					AND fabrica IN (".implode(",", $fabricas).")";

		$resNF = pg_exec ($con,$sql);
		if(strlen(pg_errormessage($con))>0){
			$resNF = pg_exec ($con,"rollback;");
		}else{
			$resNF = pg_exec ($con,"commit;");
		}
	}


	$sql = "SELECT * 
			FROM tbl_faturamento 
			WHERE nota_fiscal  = '$copia_nota_fiscal' 
				AND distribuidor = $login_posto 
				AND fabrica IN (".implode(",", $fabricas).")";

	$resNF = pg_exec ($con,$sql);

	$arquivo_nf = "";

	for ($nf = 0 ; $nf < pg_numrows ($resNF) ; $nf++) {

		$faturamento = pg_result ($resNF,$nf,faturamento);
		$posto       = pg_result ($resNF,$nf,posto);
		$embarque    = pg_result ($resNF,$nf,embarque);

		$sql = "SELECT tbl_posto.posto, tbl_posto.nome, tbl_posto.cnpj, tbl_posto.ie, tbl_posto.endereco, tbl_posto.numero, tbl_posto.bairro, tbl_posto.complemento, tbl_posto.cep, tbl_posto.cidade, tbl_posto.estado, tbl_posto.fone 
				FROM tbl_posto WHERE tbl_posto.posto = $posto";
		$resPosto = pg_exec ($con,$sql);

		$razao    = "BRITANIA ELETRODOMESTICOS LTDA";
		$endereco = "Rua Dona Francisca, 8300 -Mod.4 e 5 -Bloco A";
		$bairro   = "Dona Francisca";
		$cidade   = "Joinville";
		$estado   = "SC";
		$cep      = "89239270";
		$fone     = "(41) 2102-7700";
		$cnpj     = "76492701000742";
		$ie       = "254.861.652";

		#------------------ Gera arquivo texto -----------------#

		$tipo_pedido = pg_result ($resNF,$nf,tipo_pedido);

		$arquivo_nf .= "\n\n";
		$arquivo_nf .= "*** HEADER ***";
		$arquivo_nf .= "\nNota Fiscal # " . pg_result ($resNF,$nf,nota_fiscal) ;
		$arquivo_nf .= "\n";

		$nome = $razao;
		$nome = "($embarque) " . $nome ;

		$endereco = $endereco;
		#$numero   = trim (pg_result ($resPosto,0,numero));
		#if (strlen ($numero) > 0) $endereco .= " , n. " . $numero;

		$endereco = substr (sprintf ("%-50s",trim ($endereco)),0,50);

		$arquivo_nf .= substr (sprintf ("%06d" ,trim (pg_result ($resPosto,0,posto))),0,6);
		$arquivo_nf .= substr (sprintf ("%-40s",$nome),0,40);
		$arquivo_nf .= substr (sprintf ("%-14s",$cnpj),0,14);
		$arquivo_nf .= substr (sprintf ("%-20s",$ie),0,20);
		$arquivo_nf .= substr (sprintf ("%-50s",$endereco),0,50);
		$arquivo_nf .= "          " ;  # numero
		$arquivo_nf .= substr (sprintf ("%-20s",""),0,20);
		$arquivo_nf .= substr (sprintf ("%-30s",$bairro),0,30);
		$arquivo_nf .= substr (sprintf ("%-30s",$cidade),0,30);
		$arquivo_nf .= substr (sprintf ("%-02s",$estado),0,2);
		$arquivo_nf .= substr (sprintf ("%-08s",$cep),0,8);
		$arquivo_nf .= substr (sprintf ("%-15s",$fone),0,15);

		
		$emissao = pg_result ($resNF,$nf,emissao);
		$emissao = substr ($emissao,8,2) . "/" . substr ($emissao,5,2) . "/" . substr ($emissao,0,4) ;
		$arquivo_nf .= $emissao ;

		$arquivo_nf .= substr (sprintf ("%-10s",trim (pg_result ($resNF,$nf,cfop))),0,10);
		$arquivo_nf .= substr (sprintf ("%-25s",trim (pg_result ($resNF,$nf,natureza))),0,25);
		
		$arquivo_nf .= "\n*** DETALHE *** \n" ;
		
		$faturamento = pg_result ($resNF,$nf,faturamento);

		$sql = "SELECT tbl_peca.referencia, 
						tbl_peca.descricao, 
						tbl_faturamento_item.aliq_ipi, 
						tbl_faturamento_item.aliq_icms, 
						tbl_faturamento_item.preco, 
						tbl_peca.produto_acabado, 
						SUM (tbl_faturamento_item.qtde) AS qtde
				FROM   tbl_peca
				JOIN   tbl_faturamento_item USING (peca)
				WHERE  tbl_faturamento_item.faturamento = $faturamento
				GROUP BY tbl_peca.referencia, 
					tbl_peca.descricao, 
					tbl_faturamento_item.aliq_ipi, 
					tbl_faturamento_item.aliq_icms, 
					tbl_faturamento_item.preco,
					tbl_peca.produto_acabado
				ORDER BY tbl_peca.referencia";
		$resPeca = pg_exec ($con,$sql);

		for ($p = 0 ; $p < pg_numrows ($resPeca) ; $p++) {
			//$arquivo_nf .= substr (sprintf ("%06d" ,trim (pg_result ($resPeca,$p,referencia))),0,6);
			$arquivo_nf .= substr (sprintf ("%-10s" ,trim (pg_result ($resPeca,$p,referencia))),0,10);
			$arquivo_nf .= substr (sprintf ("%-40s",trim (pg_result ($resPeca,$p,descricao))),0,40);
			$arquivo_nf .= substr (sprintf ("%06d" ,trim (pg_result ($resPeca,$p,qtde))),0,6);
			$produto_acabado = pg_result ($resPeca,$p,produto_acabado);

			$preco = pg_result ($resPeca,$p,preco) ;

			if (pg_result ($resNF,$nf,tipo_pedido) <> "101" and pg_result ($resNF,$nf,tipo_pedido) <> "105" ) {
				$preco = $preco * ( 1 + (pg_result ($resPeca,$p,aliq_ipi) /100) );
			}
			/*HD: 110229 - SISTEMA DE IMPRESSAO DE NOTA SOMA O VALOR DE ICMS, ENTÃO TEM QUE TIRAR NA GERACAO DO ARQUIVO*/
			if($produto_acabado == 't'){
				$preco = $preco / ( 1 + (pg_result ($resPeca,$p,aliq_ipi) /100) );
			}

			$preco = number_format ($preco,2,".",",");

			$arquivo_nf .= substr (sprintf ("%012.2f" ,trim ($preco)),0,12);

			$arquivo_nf .= substr (sprintf ("%02d" ,pg_result ($resPeca,$p,aliq_icms)),0,2);
			$arquivo_nf .= substr (sprintf ("%02d" ,pg_result ($resPeca,$p,aliq_ipi)),0,2);
			$arquivo_nf .= "PC";

			$arquivo_nf .= "\n";
		}

		#------ 8 linhas de mensagens com 90 caracteres #
		$arquivo_nf .= "\n*** MENSAGEM *** \n";
		$arquivo_nf .= "                                                                                          ";
		$arquivo_nf .= "\n";
		$arquivo_nf .= "                                                                                          ";
		$arquivo_nf .= "\n";
		$arquivo_nf .= "                                                                                          ";

	// campos novos
		$arquivo_nf .= "\n";
		$arquivo_nf .= "                                                                                          ";
		$arquivo_nf .= "\n";
		$arquivo_nf .= "                                                                                          ";
		$arquivo_nf .= "\n";
		$arquivo_nf .= "                                                                                          ";
		$arquivo_nf .= "\n";
		$arquivo_nf .= "                                                                                          ";
		$arquivo_nf .= "\n";
		$arquivo_nf .= "                                                                                          ";

		$arquivo_nf .= "\n";

		$arquivo_nf .= "\n*** TRAILLER *** \n";

		#- Desconto -#
		$arquivo_nf .= "000000000.00";

		#- Despesas Acessorias -#
		$arquivo_nf .= "000000000.00";

		#- Cond. PG -#
		$condicao_pg = "           ";
		if (pg_result ($resNF,$nf,tipo_pedido) == "2")         $condicao_pg = "30,60      ";
		if (pg_result ($resNF,$nf,tipo_pedido) == "3")         $condicao_pg = "00         "; # não gera financeiro
		if (pg_result ($resNF,$nf,tipo_pedido) == "99")        $condicao_pg = "00         "; # não gera financeiro
		if (pg_result ($resNF,$nf,tipo_pedido) == "101")       $condicao_pg = "00         "; # não gera financeiro
		if (pg_result ($resNF,$nf,tipo_pedido) == "105")       $condicao_pg = "00         "; # não gera financeiro

		if (pg_result ($resNF,$nf,garantia_antecipada) == "t") $condicao_pg = "00         "; # não gera financeiro

		$arquivo_nf .= $condicao_pg ;


		#- Transportadora -#
	#	echo "SEDEX                         "; #Nome
	#	echo "00111222000101 ";                #CNPJ
	#	echo "111222333444        ";           #I.E.
	#	echo "RUA XPTO, 123                 "; #Endereco
	#	echo "MARILIA                       "; #Cidade
	#	echo "SP";                             #Estado

		$transportadora = pg_result ($resNF,$nf,transportadora);

		$sql = "SELECT * from tbl_transportadora WHERE transportadora = $transportadora";
		$resTransp = pg_exec ($con,$sql);

		$arquivo_nf .= substr (sprintf ("%-30s",trim (pg_result ($resTransp,0,nome))),0,30);
		$arquivo_nf .= substr (sprintf ("%-15s",trim (pg_result ($resTransp,0,cnpj))),0,15);
		$arquivo_nf .= "                    ";           #I.E.
		$arquivo_nf .= substr (sprintf ("%-30s",trim (pg_result ($resTransp,0,endereco))),0,30);
		$arquivo_nf .= substr (sprintf ("%-30s",trim (pg_result ($resTransp,0,cidade))),0,30);
		$arquivo_nf .= substr (sprintf ("%-2s", trim (pg_result ($resTransp,0,estado))),0,2);


		#- Tipo do Frete -#
	#	if ($tipo_pedido == "2") echo "FOB";
	#	if ($tipo_pedido == "3") echo "CIF";
		$arquivo_nf .= "CIF";

		#- Valor do Frete  (Deve ser somado ao total da Nota) -#
		$arquivo_nf .= substr (sprintf ("%012.2f" ,trim (pg_result ($resNF,$nf,valor_frete))),0,12);

		#- Qtde Volumes -#
		$arquivo_nf .= substr (sprintf ("%04d" ,trim (pg_result ($resNF,$nf,qtde_volume))),0,4);

		#- Peso em Kg -#
		$arquivo_nf .= "0000.0";

		#- Especie -#
		$arquivo_nf .= "CX";

		#- Marca -#
		$arquivo_nf .= "BRITANIA            ";
		$arquivo_nf .= "S";

		$arquivo_nf .= "\n\n\n";


	}

	if (strlen ($arquivo_nf) > 0) {
		#header("Content-type: text/plain");
		#header("Content-Disposition: attachment ; filename=nota_fiscal.telecontrol");
		#header("Content-Length: " . strlen ($arquivo_nf) . " bytes");
		#header("Content-Description: Geracao de NF");

/*		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Content-Description: File Transfer');
		header('Content-Type: application/force-download');
		header('Cache-Control: private', false );
		header('Content-Disposition: attachment; filename=nota_fiscal.telecontrol');
		header( "Content-Transfer-Encoding: binary" );
		header("Content-Length: " . strlen ($arquivo_nf) . " bytes"); 
		readfile($url) OR die(); */

		$arquivo  = fopen ("nota_fiscal.telecontrol", "w+");
		fwrite($arquivo, "$arquivo_nf");
		fclose ($arquivo);
		if (strlen($copia_nota_fiscal)>0){
			echo "Nota fiscal: ".$copia_nota_fiscal." -> ";
		}
		echo "<a href='embarque_nota_fiscal_download.php?arquivo=nota_fiscal.telecontrol' style='font-size:16px'>Clique aqui para importar</a>";
	}else{
		echo "Não foi gerado o arquivo. Verifique.";
	}
	exit;
}
?>

<html>
<head>
<title>Faturamento de Devolução - Gerar Nota Fiscal</title>
<script type="text/javascript" src="js/jquery-latest.pack.js"></script>

<style>
.cabeca{
	background-color:'#FF9933';
	color:#ffffff;
	font-weight:bold;
}

.row1{
	font-size: 12px;
	background-color:#FFE2C6;
}

.row2{
	font-size: 12px;
	background-color:#FFFFFF;
}

tr.linha td {
	border-bottom: 1px solid #EDEDE9; 
	border-top: none; 
	border-right: none; 
	border-left: none; 
}
</style>

<script type="text/javascript">

var semafaro_faturar  = 0;
var semafaro_importar = 0;
var importado = 0;

function iniciarFaturamento(){
	alert('ATENÇÃO:\n\n1) Não click no botão VOLTAR no Navegador\n2) Aguarde faturar todas os embarques\n3) O sistema vai gerar automaticamente o arquivo e você terá que clicar no link indicado\n4) Qualquer problema contate o Suporte Telecontrol.\n\nAperte OK para iniciar o faturamento.');
	$("input[@name=btn_faturar_0]").click();
}

function faturar(botao,embarque,atual,qtde_embarques){

	if(semafaro_faturar==0){
		botao.disabled = true;
		semafaro_faturar = 1;
		botao.value='FATURANDO....AGUARDE';
		$.ajax({
			type: "POST",
			url: "<? echo $PHP_SELF ?>",
			data: "Faturar=1&embarque="+embarque,
			success: function(msg){
				semafaro_faturar = 0;
				var mensagem = msg.split("|");
				if (mensagem[0] == 'erro'){
					botao.disabled = false;
					alert(mensagem[1]);
				}
				if (mensagem[0] == 'ok'){
					botao.disabled = true;
					botao.value=mensagem[1];

					proximo_botao = atual +1;

					if (proximo_botao >= qtde_embarques){
						importado = 1;
						importarNotas(document.frm_importar);
						return;
					}else{
						$("input[@name=btn_faturar_"+proximo_botao+"]").click();
					}
				}
			}
		});
	}else{
		alert('Aguarde faturar...');
	}
}

function importarNotas(form){
	if (importado > 0){
		if(semafaro_importar == 0){
			alert('Agora o sistema criará o arquivo de importação.\n\nAperte OK para continuar.');
			$("input[@name=btn_importar]").attr({disabled: true});
			$("input[@name=btn_importar]").attr({value: 'Gerando arquivo, aguarde...'});
			semafaro_importar = 1;
			$.ajax({
				type: "POST",
				url: "<? echo $PHP_SELF ?>",
				data: "Importar=1&embarques_importar="+form.embarques_importar.value,
				dataType: "html",
				success: function(msg){
					semafaro_importar = 0;
					//$("input[@name=btn_importar]").hide();
					$("input[@name=btn_importar]").fadeOut("slow");
					//$("#resutado_importacao").slideDown("slow");
					$("#resutado_importacao").html(msg);
					$("#resutado_importacao").fadeIn("slow");
				}
			});
		}else{
			alert('Aguarde importar...');
		}
	}else{
		alert('Aguarde faturar todas os embarques.');
	}
}
</script>

</head>
<body>

<? include 'menu.php' ?>

<center><h1>Faturar Embarque (TESTE)</h1></center>

<p>


<?

if($qtde_embarques>0){
	echo "<input type='button' value='Iniciar' onClick='javascript:iniciarFaturamento();'>";
	echo "<br>";
	echo '<form name="frm_faturar" method="post"  action="'.$PHP_SELF.'">';
	echo "<table width='300' align='center' cellpadding='5'>";
	echo "<tr class='cabeca'>";
	echo "	<td align='center'>Embarque</td>";
	echo "	<td align='center'>Ação</td>";
	echo "</tr>";

	# Verifica os embarque já faturados em caso de erro: 

	if(1==2){
		$qtde_embarques_aux = $qtde_embarques;

		for ($i=0;$i<$qtde_embarques_aux;$i++) {
			$sql = "SELECT faturamento 
					FROM tbl_faturamento 
					WHERE fabrica IN (".implode(",", $fabricas).")
					AND embarque=".$embarques[$i];
			$res = pg_exec ($con,$sql);
			if (pg_numrows($res)==1){
				$qtde_embarques--;
				array_splice($embarques, $i, 1);
			}
		}
	}

	for ($i=0;$i<$qtde_embarques;$i++) {
		
		$embarque_X = $embarques[$i];

		$fundo = $i%2==0?'row1':'row2';

		echo "<tr class='$fundo'>\n";
		echo "	<td align='center' nowrap>";
		echo "		<input type='text' name='embarque_$i' value='$embarque_X' size='14' maxlength='10' readOnly='readonly'>";
		echo "	</td>\n";
		echo "	<td align='center' nowrap>";
		echo "		<input type='button' value='Faturar Este Embarque' name='btn_faturar_$i' onClick='faturar(this,$embarque_X,$i,$qtde_embarques)'>";
		echo "	</td>\n";
		echo "</tr>\n";
	}

	echo "</table>";
	echo "</form>";

	echo "<br>";

	echo '<form name="frm_importar" method="post" action="'.$PHP_SELF.'">';
	#echo "<table width='400' align='center'>";
	#echo "<tr class='cabeca'>";
	#echo "	<td align='center'>Importar Embarques para Programa do Sono</td>";
	#echo "</tr>";
	#echo "<tr class='cabeca'>";
	#echo "	<td align='center'>";
	#echo "		<textarea name='notas_fiscais' rows='20' cols='10'></textarea>";
	echo "Após faturar todos os Embarques, o sistema gerará um Arquivo automaticamente <br>";
	echo "		<input type='hidden' name='embarques_importar' value='$embarque_array'>";
	#echo "		<br>";
	echo "<br><span id='resutado_importacao'></span><br>";
	echo "		<input type='button' name='btn_importar' value='Importar Embarques para Programa do Sono' onClick='importarNotas(this.form)'";
	#echo "	</td>";
	#echo "</tr>";
	echo "</form>";
	echo "<br>";
	echo "<br>";
	echo "<br>";

}else{
	echo "Nenhum embarque.";
}
exit;

if (1==2){#######
	$embarque       = $_POST['embarque'];
	$posto          = $_POST['posto'];
	$transportadora = $_POST['transportadora'];
	$qtde_volume    = $_POST['qtde_volume'];
	$valor_frete    = $_POST['valor_frete'];

	$qtde_volume = str_replace (",","",$qtde_volume);
	$qtde_volume = str_replace (".","",$qtde_volume);

	$valor_frete = str_replace (",",".",$valor_frete);
	if (substr_count ($valor_frete,".") <> 1 AND strlen ($nota_fiscal) == 0) {
		echo "<h1>Valor do Frete errado ($valor_frete)</h1>";
		exit;
	}
	$res = @pg_exec ($con,"SELECT fn_fecha_embarque ($posto, $embarque, $qtde_volume, $mbarque_total_frete, $embarque_transportadora)");

	$qtde_embarques = 1 ;
	$embarques[0] = $embarque;
}######


$copia_nota_fiscal = $nota_fiscal;
if (strlen ($nota_fiscal) > 0) {
	$qtde_embarques = 0;
}

$embarque_total_frete    = $valor_frete    ;
$embarque_qtde_volume    = $qtde_volume    ;
$embarque_transportadora = $transportadora ;


$taxa_administrativa = 1 ;

?>


<p>

<? include "rodape.php"; ?>

</body>
</html>
