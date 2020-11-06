<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if ($login_posto == "5237") {
	header ("Location: pedido_blackedecker_cadastro.php");
	exit;
}

$arquivo = $_FILES['arquivo']['tmp_name'];

$msg_exito = "";

if (strlen($arquivo) > 0) {
	$filename = basename($_FILES['arquivo']['name']);
	$ext = substr($filename, strrpos($filename, '.') + 1);
	$ext = strtolower($ext); 
	if($ext == "xls"){
		require_once 'admin/xls_reader.php';
		$data = new Spreadsheet_Excel_Reader();
		$data->setOutputEncoding('CP1251');
		$data->read($arquivo);
		$dia = date("Ymd");
		$file = "/tmp/blackedecker/pedidos/pedidos-$login_posto-$dia.txt";
		
		$fp   = fopen($file, 'w');
		for ($i = 1; $i <= $data->sheets[0]['numRows']; $i++) {
			
			for ($j = 1; $j <= $data->sheets[0]['numCols']; $j++) {
				if($data->sheets[0]['cells'][$i][1] == "HEA"){
					switch($j){
						case 1 : $conteudo = str_pad($data->sheets[0]['cells'][$i][$j],3," ");break;
						case 2 : $conteudo = str_pad($data->sheets[0]['cells'][$i][$j],20," ");break;
						case 3 : $conteudo = str_pad($data->sheets[0]['cells'][$i][$j],14," ");break;
						case 4 : $conteudo = str_pad($data->sheets[0]['cells'][$i][$j],5," ");break;
						case 5 : $conteudo = str_pad($data->sheets[0]['cells'][$i][$j],20," ");break;
						case 6 : $conteudo = str_pad($data->sheets[0]['cells'][$i][$j],3," ");break;
						case 7 : $conteudo = str_pad($data->sheets[0]['cells'][$i][$j],143," ");break;
					}

				}

				if($data->sheets[0]['cells'][$i][1] == "DET"){
					switch($j){
						case 1 : $conteudo = str_pad($data->sheets[0]['cells'][$i][$j],3," ");break;
						case 2 : $conteudo = str_pad($data->sheets[0]['cells'][$i][$j],6," ");break;
						case 3 : $conteudo = str_pad($data->sheets[0]['cells'][$i][$j],20," ");break;
						case 4 : $conteudo = str_pad($data->sheets[0]['cells'][$i][$j],53," ");break;
						case 5 : $conteudo = str_pad($data->sheets[0]['cells'][$i][$j],12,"0",STR_PAD_LEFT);break;
						case 6 : $conteudo = str_pad($data->sheets[0]['cells'][$i][$j],114," ");break;
					}

				}

				if($data->sheets[0]['cells'][$i][1] == "FTP"){
					switch($j){
						case 1 : $conteudo = str_pad($data->sheets[0]['cells'][$i][$j],3," ");break;
						case 2 : $conteudo = str_pad($data->sheets[0]['cells'][$i][$j],205," ");break;
					}

				}

				fputs($fp,$conteudo);
			}
			fputs($fp,"\r\n");

		}
		fclose($fp);

		$arquivo = $file;
	}
		#/www/cgi-bin/blackedecker/importa-pedidos.pl
		echo `mkdir -m 777 /tmp/blackedecker 2>/dev/null`;
		echo `/www/cgi-bin/blackedecker/importa-pedidos.pl $arquivo > /tmp/blackedecker/erros-importacao.txt 2>&1`;
		#echo `/home/ronald/perl/blackedecker/importa-pedidos.pl $arquivo > /tmp/blackedecker/erros-importacao.txt 2>&1`;
		$msg = `cat /tmp/blackedecker/erros-importacao.txt`;
		$msg = preg_replace('/current transaction is aborted.+\n/','',$msg);
	
	//if (strpos ($msg,"Fail to add null value in not null attribute preco") > 0) {
	//	$msg = substr ($msg,0,20) . " - Peça não encontrada na Tabela de Preços";
	//}
	//if (strpos ($msg,"Fail to add null value in not null attribute peca") > 0) {
	//	$msg = substr ($msg,0,20) . " - Peça não cadastrada";
	//}
	
	if(strpos($msg,"syntax error at or near")){
		$erro = "Foi detectado o seguinte erro:<br>";
		$msg="Por favor, verifique o conteúdo do arquivo, está faltando algumas informações.";
	}
	if(strpos($msg,"NOTICE:")){
		$msg="";
	}
	if(strpos($msg,"INSERT")){
		$erro = "Foi detectado o seguinte erro:<br>";
		$msg="Houve um erro na hora de cadastrar o pedido, por favor,verificar o arquivo!";
	}
	if (strlen($msg) > 0) {
		$erro  = "Seu pedido foi importado mas detectamos o(s) seguinte(s) erro(s):<br>";
		$msg_erro .= nl2br($msg);
	}else{
		$msg_exito = "Pedido gerado com sucesso!";
	}

}

$title     = "Upload de Pedido de Peças";
$cabecalho = "Upload de Pedido de Peças";

$layout_menu = 'pedido';
include "cabecalho.php";
?>


<!-- ---------------------- Inicio do HTML -------------------- -->

<style type="text/css">
	.msg_erro {
		background-color: #FF0000;
		font: bold 16px "Arial";
		color: #FFFFFF;
		text-align: center;
		width: 680px;
		padding: 10px;
		margin: 0 auto;
		margin-top: 15px;
	}
</style>

	
<? 
		// retira palavra ERROR:
	if (strpos($msg_erro,"ERROR:") !== false) {	
		$msg_erro = str_replace("ERROR:", "", $msg_erro);
	}

	// retira CONTEXT:
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}
	echo "<!-- ERRO INICIO -->";
	$erro = $erro . $msg_erro;

	if(strlen($erro)){
		echo "<div class='msg_erro'>".$erro."</div>";
	}

?>

<?
	if (!empty($msg_exito)) {
		echo "<center>
				<font face='arial, verdana' size='+1' color='#5BB75B'>$msg_exito</font>
			  </center>";
	}
	echo "<p>";
?>


<table width="700" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td>
		<font face='arial,verdana' size='-1'>
		Para enviar seu arquivo de pedidos de peças, digite o caminho completo do arquivo no seu
		computador, ou clique no botão para localizá-lo. Depois clique em FINALIZAR.
		<br>
		Seu pedido será submetido e avaliado no site, e se tudo estiver correto, ele já irá aparecer
		no relatório de pedidos. Se houver algum erro, acerte o problema, e reenvie o novo arquivo.
		</font>

		<br /> <br />
	<td>
</tr>
<tr>
	<td bgcolor="#F5F5F5" style="padding: 0px 0px 10px 10px;">
		<font face='arial,verdana' size='-1' >
		<strong>
			ATENÇÃO:
			<br>
			Foi acrescentada a forma de pagamento no cabeçalho do pedido:  Sequência 5.
			<br>
			Foi acrescentada a unificação do pedido no cabeçalho do pedido:  Sequência 6.
			<br><br>
			Solicitamos que efetuem esta modificação em seus programas de geração do arquivo.
		</strong>
		</font>
	<td>
</tr>
<?php

$queryCatPosto = "SELECT categoria
			  FROM tbl_posto_fabrica
			  WHERE posto = $login_posto
			  AND fabrica = $login_fabrica";

$resCatPosto = pg_query($con, $queryCatPosto);

$categoriaPosto = pg_fetch_result($resCatPosto, 0, 'categoria');

if (!in_array($login_fabrica, [1]) || (in_array($login_fabrica, [1]) && $categoriaPosto != 'Locadora') ) { ?> 
	<tr>
		<td align="center">
			<br><br>
			<font face='arial,verdana' size='-1'>
			<form name='frm_pedido' method='post' action='<? echo $PHP_SELF ?>' enctype='multipart/form-data'>
			Arquivo de Pedidos a enviar: <input name="arquivo" type="file">
			<p>
			<input type="hidden" name="btn_acao" value="">
			<img border="0" src="imagens/btn_finalizar.gif" onclick="javascript: if (document.frm_pedido.btn_acao.value == '' ) { document.frm_pedido.btn_acao.value='enviar' ; document.frm_pedido.submit() } else { alert ('Aguarde submissão') }" ALT="Enviar" style='cursor: pointer'>
			</form>
			</font>
		<td>
	</tr>
<?php } ?>
</table>


<p>


<table width="700" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td bgcolor='#9999ff' align='center' colspan='7'>
		<font face='arial,verdana' color='#ffffff'><b>
		Layout do arquivo texto.
		</b></font>
	</td>
</tr>

<tr>
	<td bgcolor='#eeeeee' align='center' colspan='7'>
		<font face='arial,verdana' color='#0000ff'><b>
		Registro HEADER - Cabeçalho do arquivo
		</b></font>
	</td>
</tr>

<tr>
	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Seq.
		</font>
	</td>

	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Campo
		</font>
	</td>

	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Início
		</font>
	</td>

	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Final
		</font>
	</td>

	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Tamanho
		</font>
	</td>

	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Tipo
		</font>
	</td>

	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Exemplo
		</font>
	</td>
</tr>







<tr>
	<td>
		<font face='arial,verdana' size='-1'>
		1
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Tipo Registro
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		001
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		003
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		003
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Alfa
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		HEA
		</font>
	</td>
</tr>



<tr>
	<td>
		<font face='arial,verdana' size='-1'>
		2
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Brancos
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		004
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		023
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		020
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Alfa
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		&nbsp;
		</font>
	</td>
</tr>



<tr>
	<td>
		<font face='arial,verdana' size='-1'>
		3
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		CNPJ do Posto
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		024
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		037
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		014
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Num
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		02494691000130
		</font>
	</td>
</tr>


<tr>
	<td>
		<font face='arial,verdana' size='-1'>
		4
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Brancos
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		038
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		042
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		005
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Alfa
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		&nbsp;
		</font>
	</td>
</tr>

<tr>
	<td>
		<font face='arial,verdana' size='-1'>
		5
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Condição de Pagamento
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		043
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		062
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		020
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Alfa
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		<?
		$sql =	"SELECT tbl_condicao.descricao
				FROM tbl_condicao
				JOIN tbl_posto_linha_condicao USING (condicao)
				WHERE tbl_posto_linha_condicao.posto = $login_posto
				AND   tbl_condicao.visivel IS TRUE";
		$res = @pg_exec($con,$sql);
		if (@pg_numrows($res) > 0) {
			for ($x = 0 ; $x < @pg_numrows($res) ; $x++) {
				echo trim(pg_result($res,$x,descricao))."<br>";
			}
		}
/*		$sql = "SELECT  tbl_condpgto.condpgto,
						tbl_condpgto.descricao
				FROM    tbl_condpgto
				JOIN    tbl_tabela_politica ON  tbl_condpgto.condpgto          = tbl_tabela_politica.condpgto
											AND tbl_tabela_politica.tipo_posto = $tipo_posto
											AND tbl_tabela_politica.exibe      = 't'
				ORDER BY tbl_condpgto.ordem;";
		$res = pg_exec ($con,$sql);
		
		for ($x=0; $x < @pg_numrows($res); $x++) {
			$condicao = trim(pg_result($res,$x,descricao));
			echo "$condicao<br>";
		}*/
		?>
		</font>
	</td>
</tr>


<tr>
	<td>
		<font face='arial,verdana' size='-1'>
		6
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Unificar Pedido
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		063
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		065
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		003
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Alfa
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		sim ou não (minúsculo)
		</font>
	</td>
</tr>




<tr>
	<td>
		<font face='arial,verdana' size='-1'>
		7
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Brancos
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		066
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		208
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		143
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Alfa
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		&nbsp;
		</font>
	</td>
</tr>








<tr>
	<td bgcolor='#eeeeee' align='center' colspan='7'>
		<font face='arial,verdana' color='#0000ff'><b>
		Registro DETALHE - Itens do pedido
		</b></font>
	</td>
</tr>

<tr>
	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Seq.
		</font>
	</td>

	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Campo
		</font>
	</td>

	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Início
		</font>
	</td>

	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Final
		</font>
	</td>

	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Tamanho
		</font>
	</td>

	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Tipo
		</font>
	</td>

	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Exemplo
		</font>
	</td>
</tr>







<tr>
	<td>
		<font face='arial,verdana' size='-1'>
		1
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Tipo Registro
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		001
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		003
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		003
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Alfa
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		DET
		</font>
	</td>
</tr>


<tr>
	<td>
		<font face='arial,verdana' size='-1'>
		2
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Brancos
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		004
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		009
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		006
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Alfa
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		&nbsp;
		</font>
	</td>
</tr>





<tr>
	<td>
		<font face='arial,verdana' size='-1'>
		3
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Referência da Peça
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		010
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		029
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		020
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Alfa
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		612407 - Espaços a Direita
		</font>
	</td>
</tr>



<tr>
	<td>
		<font face='arial,verdana' size='-1'>
		4
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Brancos
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		030
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		082
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		053
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Alfa
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		&nbsp;
		</font>
	</td>
</tr>


<tr>
	<td>
		<font face='arial,verdana' size='-1'>
		5
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Quantidade
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		083
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		094
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		012
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Num
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		000000000021 (21 peças)
		</font>
	</td>
</tr>




<tr>
	<td>
		<font face='arial,verdana' size='-1'>
		6
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Brancos
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		095
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		208
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		114
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Alfa
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		&nbsp;
		</font>
	</td>
</tr>











<tr>
	<td bgcolor='#eeeeee' align='center' colspan='7'>
		<font face='arial,verdana' color='#0000ff'><b>
		Registro TRAILLER - Final da Transmissão
		</b></font>
	</td>
</tr>

<tr>
	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Seq.
		</font>
	</td>

	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Campo
		</font>
	</td>

	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Início
		</font>
	</td>

	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Final
		</font>
	</td>

	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Tamanho
		</font>
	</td>

	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Tipo
		</font>
	</td>

	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Exemplo
		</font>
	</td>
</tr>







<tr>
	<td>
		<font face='arial,verdana' size='-1'>
		1
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Tipo Registro
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		001
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		003
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		003
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Alfa
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		FTP
		</font>
	</td>
</tr>




<tr>
	<td>
		<font face='arial,verdana' size='-1'>
		2
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Brancos
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		004
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		208
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		205
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Alfa
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		&nbsp;
		</font>
	</td>
</tr>
<tr><td>&nbsp;</td></tr>
<tr>
	<td colspan="7" align="center">
		<font face='arial,verdana' size='-1'>
			<b><a href="comunicados/pedidos-black.txt" target="__blank">Clique aqui</a> para baixar o exemplo do arquivo TXT</b>
		</font>
	</td>
</tr>
</table>

<br /> <br />

<table width="700" border="0" cellpadding="2" cellspacing="2" align="center">
	<tr>
		<td bgcolor="#9999ff" colspan="4" align="center">
			<font face='arial,verdana' color="#ffffff"><b>Layout do arquivo Excel(XLS)</b></font>
		</td>
	</tr>
	<tr>
		<td bgcolor="#eeeeee" colspan="4" align="center">
			<font face='arial,verdana' color="#0000ff"><b>Registro HEADER - Cabeçalho do arquivo  </b> </font>
		</td>
	</tr>
	<tr>
		<td align="center" bgcolor="#9999ff"><font face='arial,verdana' size='-1'>Seq.</font></td>
		<td align="center" bgcolor="#9999ff"><font face='arial,verdana' size='-1'>Campo </font></td>
		<td align="center" bgcolor="#9999ff"><font face='arial,verdana' size='-1'>Tipo</font></td>
		<td align="center" bgcolor="#9999ff"><font face='arial,verdana' size='-1'>Exemplo</font> </td>
	</tr>
	<tr>
		<td><font face='arial,verdana' size='-1'>1</font></td>
		<td><font face='arial,verdana' size='-1'>Tipo Registro</font> </td>
		<td><font face='arial,verdana' size='-1'>Alfa</font></td>
		<td><font face='arial,verdana' size='-1'>HEA </font></td>
	</tr>
	<tr>
		<td><font face='arial,verdana' size='-1'>2</font></td>
		<td><font face='arial,verdana' size='-1'>Vazio</font> </td>
		<td><font face='arial,verdana' size='-1'>Vazio</font></td>
		<td><font face='arial,verdana' size='-1'>Vazio </font></td>
	</tr>
	<tr>
		<td><font face='arial,verdana' size='-1'>3</font></td>
		<td><font face='arial,verdana' size='-1'>CNPJ do Posto</font></td>
		<td><font face='arial,verdana' size='-1'>Num</font></td>
		<td><font face='arial,verdana' size='-1'>02494691000130</font> </td>
	</tr>
	<tr>
		<td><font face='arial,verdana' size='-1'>4</font></td>
		<td><font face='arial,verdana' size='-1'>Vazio</font> </td>
		<td><font face='arial,verdana' size='-1'>Vazio</font></td>
		<td><font face='arial,verdana' size='-1'>Vazio </font></td>
	</tr>
	<tr>
		<td><font face='arial,verdana' size='-1'>5</font></td>
		<td><font face='arial,verdana' size='-1'>Condição de Pagamento</font></td>
		<td><font face='arial,verdana' size='-1'>Alfa</font></td>
		<td><font face='arial,verdana' size='-1'>15</font></td>
	</tr>
	<tr>
		<td><font face='arial,verdana' size='-1'>6</font></td>
		<td><font face='arial,verdana' size='-1'>Unificar Pedido</font></td>
		<td><font face='arial,verdana' size='-1'>Alfa</font></td>
		<td><font face='arial,verdana' size='-1'>sim</font></td>
	</tr>

	<tr>
		<td bgcolor="#eeeeee" colspan="4" align="center">
			<font face='arial,verdana' color="#0000ff"><b>Registro DETALHE - Itens do pedido </b> </font>
		</td>
	</tr>
	<tr>
		<td align="center" bgcolor="#9999ff"><font face='arial,verdana' size='-1'>Seq.</font></td>
		<td align="center" bgcolor="#9999ff"><font face='arial,verdana' size='-1'>Campo </font></td>
		<td align="center" bgcolor="#9999ff"><font face='arial,verdana' size='-1'>Tipo</font></td>
		<td align="center" bgcolor="#9999ff"><font face='arial,verdana' size='-1'>Exemplo</font> </td>
	</tr>
	<tr>
		<td><font face='arial,verdana' size='-1'>1</font></td>
		<td><font face='arial,verdana' size='-1'>Tipo Registro </font></td>
		<td><font face='arial,verdana' size='-1'>Alfa</font></td>
		<td><font face='arial,verdana' size='-1'>DET </font> </td>
	</tr>
	<tr>
		<td><font face='arial,verdana' size='-1'>2</font></td>
		<td><font face='arial,verdana' size='-1'>Referência da Peça</font></td>
		<td><font face='arial,verdana' size='-1'>Alfa</font></td>
		<td><font face='arial,verdana' size='-1'>612407</font></td>
	</tr>
	<tr>
		<td><font face='arial,verdana' size='-1'>3</font></td>
		<td><font face='arial,verdana' size='-1'>Quantidade</font></td>
		<td><font face='arial,verdana' size='-1'>Num</font></td>
		<td><font face='arial,verdana' size='-1'>21</font></td>
	</tr>

	<tr>
		<td bgcolor="#eeeeee" colspan="4" align="center">
			<font face='arial,verdana' color="#0000ff"><b>Registro DETALHE - Itens do pedido</b> </font>
		</td>
	</tr>
	<tr>
		<td align="center" bgcolor="#9999ff"><font face='arial,verdana' size='-1'>Seq.</font></td>
		<td align="center" bgcolor="#9999ff"><font face='arial,verdana' size='-1'>Campo</font> </td>
		<td align="center" bgcolor="#9999ff"><font face='arial,verdana' size='-1'>Tipo</font></td>
		<td align="center" bgcolor="#9999ff"><font face='arial,verdana' size='-1'>Exemplo</font> </td>
	</tr>
	<tr>
		<td><font face='arial,verdana' size='-1'>1</font></td>
		<td><font face='arial,verdana' size='-1'>Tipo Registro</font> </td>
		<td><font face='arial,verdana' size='-1'>Alfa</font></td>
		<td><font face='arial,verdana' size='-1'>FTP</font></td>
	</tr>
	<tr><td>&nbsp;</td></tr>
	<tr>
		<td colspan="7" align="center">
			<font face='arial,verdana' size='-1'>
				<b><a href="comunicados/pedidos-black.xls" target="__blank">Clique aqui</a> para baixar o exemplo do arquivo XLS</b>
			</font>
		</td>
	</tr>
</table>

<p>


<?include "rodape.php";?>
