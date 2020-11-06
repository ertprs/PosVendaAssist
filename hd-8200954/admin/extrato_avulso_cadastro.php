<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

$msg_erro = "";

$layout_menu = "financeiro";
$title = "LANÇAMENTO DE EXTRATO AVULSO";

$campos_adicionais = ($login_fabrica == 3) ? json_encode(array('aprovacao' => true)) : "";

if (strlen($_POST["extrato"]) > 0) $extrato = $_POST["extrato"];
if (strlen($_GET["extrato"]) > 0) $extrato = $_GET["extrato"];

if (strlen($_POST["posto"]) > 0) $posto = $_POST["posto"];
if (strlen($_GET["posto"]) > 0) $posto = $_GET["posto"];

include "cabecalho.php";
$cache_bypass=md5(time());

if (strlen($_POST["qtde_produto"]) > 0) $qtde_produto = $_POST["qtde_produto"];
if (strlen($_GET["qtde_produto"]) > 0) $qtde_produto = $_GET["qtde_produto"];

if ($qtde_produto > 0) {
	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$lancamento_array             = $_POST ['lancamento'] ;
	$extrato_lancamento_array     = $_POST ['extrato_lancamento'] ;
	$historico_array              = $_POST ['historico'] ;
	$valor_array                  = $_POST ['valor'] ;
	$competencia_futura_array     = $_POST ['competencia_futura'] ;
	$aux_competencia_futura_array = $_POST ['competencia_futura'] ;
	$posto_codigo_array 		  = $_POST['posto_codigo'];
	$posto_nome_array 			  = $_POST['posto_nome'];

	for ($i=0;$i<$qtde_produto;$i++) {
		$lancamento             = $lancamento_array[$i];
		$extrato_lancamento     = $extrato_lancamento_array[$i];
		$historico              = $historico_array[$i];
		$valor                  = $valor_array[$i];
		$competencia_futura     = $competencia_futura_array[$i];
		$aux_competencia_futura = $aux_competencia_futura_array[$i];
		$posto_codigo 			= $posto_codigo_array[$i];
		$posto_nome 			= $posto_nome_array[$i];

		$historico 				= pg_escape_string($historico);

		if($login_fabrica == 3){
			if($lancamento == 153){
				$campos_adicionais = 'null';
			}else{
				$campos_adicionais = json_encode(array('aprovacao' => true));
			}
		}

		if (strlen($posto_codigo)>0) {
			$sql = "SELECT posto
							FROM   tbl_posto_fabrica
							WHERE  tbl_posto_fabrica.codigo_posto = '$posto_codigo'
							AND    tbl_posto_fabrica.fabrica      = $login_fabrica;";
					//if ($ip == '201.76.85.4') echo "1) $sql<br>";
					$res = pg_exec($con,$sql);
					$posto = pg_result($res,0,posto);
		} else {
			$msg_erro .= " Informe o código do Posto na linha $i <br>";
		}
		

		$competencia_futura = str_replace (" " , "" , $competencia_futura);
		$competencia_futura = str_replace ("-" , "" , $competencia_futura);
		$competencia_futura = str_replace ("/" , "" , $competencia_futura);
		$competencia_futura = str_replace ("." , "" , $competencia_futura);


		if (strlen($extrato_lancamento) > 0 AND strlen($lancamento) == 0 AND strlen($historico) == 0 AND strlen($valor) == 0){
			$sql = "DELETE FROM tbl_extrato_lancamento
					WHERE  extrato_lancamento = $extrato_lancamento;";
			$res = @pg_exec($con,$sql);

			$msg_erro = pg_errormessage($con);
		}

		if (strlen ($competencia_futura) > 0) {
            $competencia_futura = "'".substr ($competencia_futura,2,4) . "-" . substr ($competencia_futura,0,2) . "-01'";

            $qry = pg_query($con, "select to_char(({$competencia_futura}::date + interval '1 month') - interval '1 day', 'YYYY-MM-DD')::date as ultimo_dia");
            $competencia_futura = "'" . pg_fetch_result($qry, 0, 'ultimo_dia') . "'";

			$sql="SELECT $competencia_futura::date < current_date ";
			$res=pg_exec($con,$sql);
			$data_competencia=pg_result($res,0,0);
			if($data_competencia == 't' and strlen($extrato) == 0) {
				$msg_erro .= " A Data de Competência Deveria Ser Maior ou Igual Que A Data Atual  na linha $i <br>";
			}
		}
		if (strlen($lancamento) > 0 OR strlen($historico) > 0 OR strlen($valor) > 0){

			if (strlen($valor) == 0){
				$msg_erro = " Informe o Valor" ;
			}else{
				$xvalor = trim($valor);
			}
			if (strlen($lancamento) == 0){
				$msg_erro = " Informe a Descrição do Lançamento  na linha $i <br>";
			}else{
				$xlancamento = trim($lancamento);
			}
			if (strlen($historico) == 0){
				$msg_erro = "Informe o histórico do Lançamento  na linha $i <br>";
			}else{
				$xhistorico = "'". trim($historico) ."'";
			}
			if (strlen($competencia_futura) == 0){
				$competencia_futura = " null ";
			}
			if (strlen($msg_erro) == 0) {
				$sql = "SELECT debito_credito FROM tbl_lancamento WHERE lancamento = $lancamento and fabrica = $login_fabrica;";
				$resL = @pg_exec($con, $sql);
				$debito_credito = @pg_result($resL,0,debito_credito);
				if(!empty($debito_credito))
					$debito_credito = "$debito_credito";
				else
					$debito_credito = 'null';
				$sql = "SELECT fnc_limpa_moeda('$xvalor');";
				$resM = @pg_exec($con, $sql);
				$xvalor = @pg_result($resM,0,0);
				if ($debito_credito == 'D'){
					if($xvalor > 0 ) 
						$xvalor = '-'.$xvalor;
				}
				if(strlen($extrato) == 0) {
					$extrato = 'null';
				}

				if (strlen ($extrato_lancamento) == 0) {
					$sql = "INSERT INTO tbl_extrato_lancamento (
								posto                ,
								fabrica              ,
								extrato              ,
								lancamento           ,
								historico            ,
								valor                ,
								debito_credito       ,
								campos_adicionais    ,
								admin                ,
								competencia_futura
								) VALUES (
								$posto               ,
								$login_fabrica       ,
								$extrato             ,
								$xlancamento         ,
								$xhistorico          ,
								'$xvalor'            ,
								'$debito_credito'      ,
								'$campos_adicionais' , 
								$login_admin         ,
								$competencia_futura
								);";
				}else{
					$sql = "UPDATE tbl_extrato_lancamento SET
									lancamento         = $xlancamento         ,
									historico          = $xhistorico          ,
									valor              = '$xvalor'            ,
									debito_credito	   = '$debito_credito'   	  ,
									competencia_futura = $competencia_futura
							WHERE   extrato_lancamento = $extrato_lancamento;";
				}
				$res = pg_exec($con,$sql);
				if (pg_errormessage($con)) {
                    $msg_erro .= 'Erro ao cadastrar avulso.';
                }

			}
		}
	}
	
	if (strlen($msg_erro)>0) {
        $res = pg_exec ($con,"ROLLBACK TRANSACTION");
    } else {
        $res = pg_exec($con,"COMMIT TRANSACTION");

        if(strlen(is_int($extrato)) > 0) {
            $sql = " SELECT fn_calcula_extrato($login_fabrica,$extrato) ";
            $res = pg_query($con,$sql);
            $msg_erro = pg_errormessage($con);
        }

    }

	if (strlen ($msg_erro) == 0) {
		if(strlen($extrato) > 0 and $extrato <> 'null') {
		echo "<script>alert('extratos avulsos lançados com sucesso!');
		window.location = 'extrato_posto_mao_obra_novo_britania.php?extrato=$extrato&posto=$posto';</script>";
		}else{
			echo "<script>alert('extratos avulsos lançados com sucesso!');
		window.location = 'menu_financeiro.php';</script>";
		}

		exit;
	}
}

if (!empty($_POST) and $qtde_produto == 0) {
    $qtde_produto = 1;
    $posto = NULL;
    $posto_codigo = '';
    $posto_nome = '';
    $lancamento = '';
    $historico = '';
    $valor = '';
    $competencia_futura = '';
}

if (strlen($posto) > 0){

	$sql = "SELECT tbl_posto.nome                ,
				   tbl_posto_fabrica.codigo_posto
			FROM   tbl_posto
			JOIN   tbl_posto_fabrica ON tbl_posto_fabrica.posto    = $posto
								     AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE  tbl_posto.posto = $posto;";
	$res = pg_exec($con,$sql);
	$posto_codigo       = @pg_result($res,0,codigo_posto);
	$posto_nome         = @pg_result($res,0,nome);
	$readonly = " readonly='readonly' ";
}

?>

<p>

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

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}


.formulario{
	background-color:#F1F4FA;
	font:11px Arial;
	text-align:left;
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
</style>

<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type="text/javascript" src="js/jquery.js"></script>
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/jquery.ajaxQueue.js'></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script type="text/javascript" src="js/jquery.maskmoney.js"></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script language='javascript' src='ajax.js'></script>
<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$("input[id~=competencia_futura]").maskedinput("99/9999");
		$("input[id~=valor]").maskMoney({showSymbol:"", symbol:"", decimal:",", precision:2, thousands:".",maxlength:10});
	});
</script>

<script language="JavaScript">

function fnc_pesquisa_posto(campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
		var xcampo2 = campo2;
		alert(xcampo2.value);
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa");
	}
}

function autocompletar_descricao(campo1,conteudo,campo2) {
	var	url = "ajax_extrato_avulso.php?q=" + conteudo;
	//	alert(url);
	$('#'+campo1).autocomplete(url, {
		minChars: 3,
		delay: 150,
		width: 350,
		scroll: true,
		scrollHeight: 500,
		matchContains: false,
		highlightItem: true,
		formatItem: function (row)   {return row[1]},
		formatResult: function(row)  {return row[0];}
	});

	$('#'+campo1).result(function(event, data, formatted) {
	$('#'+campo2).val(data[2])     ;
	});
}

function autocompletar_descricao2(campo1,conteudo,campo2) {
	var	url = "ajax_extrato_avulso.php?q=" + conteudo;
	//	alert(url);
	$('#'+campo1).autocomplete(url, {
		minChars: 3,
		delay: 150,
		width: 350,
		scroll: true,
		scrollHeight: 500,
		matchContains: false,
		highlightItem: true,
		formatItem: function (row)   {return row[2]},
		formatResult: function(row)  {return row[2];}
	});

	$('#'+campo1).result(function(event, data, formatted) {
	$('#'+campo2).val(data[0])     ;
	});
}

function function1(linha2) {
	var linha = document.getElementById('qtde_produto').value;
	var valida_linha = document.getElementById('valida_linha_'+linha);
	//alert(valida_linha.value);
	//alert(linha);
	linha = parseInt(linha) + 1;
	/*se ainda na criou a linha de item */
	if (!document.getElementById('item'+linha)) {

		var tbl = document.getElementById('tabela_itens');
		//var lastRow = tbl.rows.length;
		//var iteration = lastRow;

		//Atualiza a qtde de linhas
		$('#qtde_produto').val(linha);

		/*Criar TR - Linha*/
		var nova_linha = document.createElement('tr');
		nova_linha.setAttribute('rel', linha);

		/********************* COLUNA 1 ****************************/

		/*Cria TD */
		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';

		var el = document.createElement('input');
		el.setAttribute('type', 'text');
		el.setAttribute('name', 'posto_codigo[]');
		el.setAttribute('id', 'posto_codigo_' + linha);
		<?if(!empty($posto)) { ?>
		el.setAttribute('value', '<?=$posto_codigo?>');
		el.setAttribute('readonly', 'readonly');
		<?}?>
		el.setAttribute('size', '15');
		<?if(empty($posto)) { ?>
		el.onfocus = function(){
			autocompletar_descricao('posto_codigo_'+linha,this.value,'posto_nome_'+linha);
            $('#posto_codigo_' + linha).click();
		}
		<?}?>
		el.onblur = function () {
			valida_codigo(linha);
			validacao_campos(linha,0);
		}
		celula.appendChild(el);

		nova_linha.appendChild(celula);

		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';
		var el = document.createElement('input');
		el.setAttribute('type', 'text');
		el.setAttribute('name', 'posto_nome[]');
		el.setAttribute('id', 'posto_nome_' + linha);
		<?if(!empty($posto)) { ?>
		el.setAttribute('value', '<?=$posto_nome?>');
		el.setAttribute('readonly', 'readonly');
		<?}?>
		el.setAttribute('size', '30');
		el.setAttribute('rel', linha);
		<? if(empty($posto)) { ?>
		el.onfocus = function(){
			autocompletar_descricao2('posto_nome_'+linha,this.value,'posto_codigo_'+linha);
            $('#posto_nome_' + linha).click();
		}
		<?}?>
		celula.appendChild(el);
		el.onblur = function () {
			validacao_campos(linha,0);
		}
		nova_linha.appendChild(celula);

		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';
		var teste_array = '<?	$sql = "SELECT  lancamento, descricao FROM    tbl_lancamento WHERE   tbl_lancamento.fabrica = $login_fabrica	AND      tbl_lancamento.ativo IS TRUE ORDER BY tbl_lancamento.descricao"; $res1 = pg_exec ($con,$sql); if (pg_numrows($res1) > 0) { 				for ($x = 0 ; $x < pg_numrows($res1) ; $x++){$aux_lancamento = trim(pg_result($res1,$x,lancamento)); $aux_descricao  = trim(pg_result($res1,$x,descricao));   $aux_descricao  = str_replace("'", " ",$aux_descricao); echo $aux_lancamento;echo'/';echo $aux_descricao;echo '|'; }	 }	?>';
		teste_array = teste_array.split('|');
		var qtd = teste_array.length;
		var el = document.createElement("select");
		el.setAttribute('name', 'lancamento[]');
		el.setAttribute('id', 'lancamento_' + linha);
		el.style.cssText = 'width: 170px;';
		elop=document.createElement("OPTION");
		elop.setAttribute('value','');
		texto1=document.createTextNode("ESCOLHA");
		elop.appendChild(texto1);
		el.appendChild(elop);
		el.onblur = function () {
			validacao_campos(linha,0);
		}

		for ($i=0;$i<qtd;$i++) {
			var array = teste_array[$i].split('/');
			var codigo = array[0];
			var nome = array[1];

			if (codigo != '') {
				elop=document.createElement("OPTION");
				elop.setAttribute('value',codigo);
				texto1=document.createTextNode(nome);
				elop.appendChild(texto1);
				el.appendChild(elop);
			}
		}

		celula.appendChild(el);
		nova_linha.appendChild(celula);

		/*Cria TD */
		var celula = criaCelula('');
		var el = document.createElement('textarea');
		el.setAttribute('name', 'historico[]');
		el.setAttribute('id', 'historico_' + linha);
		el.setAttribute('rows', '3');
		el.setAttribute('cols', '30');
		el.onblur = function () {
			validacao_campos(linha,0);
		}
		celula.appendChild(el);
		nova_linha.appendChild(celula);

		/********************* COLUNA 4 ****************************/
		/*Cria TD */
		var celula = criaCelula('');

		var el = document.createElement('input');
		el.setAttribute('type', 'text');
		el.setAttribute('name', 'valor[]');
		el.setAttribute('id', 'valor_' + linha);
		el.setAttribute('size', '10');
		el.setAttribute('style', 'text-align:right');
		el.onblur = function() {
			validacao_campos(linha);
			chamar_funcao(linha);
		}
		celula.appendChild(el);

		nova_linha.appendChild(celula);

		/********************* COLUNA 5 ****************************/

		/*Cria TD */
		var celula = criaCelula('');

		var el = document.createElement('input');
		el.setAttribute('type', 'text');
		el.setAttribute('name', 'competencia_futura[]');
		el.setAttribute('id', 'competencia_futura_' + linha);
		el.setAttribute('size', '8');
		el.setAttribute('rel', 'mascara_data');
		el.onblur = function () {
			validacao_campos(linha,1);
		}
		celula.appendChild(el);

		nova_linha.appendChild(celula);


		var celula = criaCelula('');

		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');
		el.setAttribute('name', 'valida_linha[]');
		el.setAttribute('id', 'valida_linha_' + linha);
		el.setAttribute('size', '8');
		el.setAttribute('value', 'sim');
		celula.appendChild(el);

		nova_linha.appendChild(celula);

		var celula = criaCelula('');

		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');
		el.setAttribute('name', 'extrato_lancamento[]');
		el.setAttribute('id', 'extrato_lancamento_' + linha);
		celula.appendChild(el);

		nova_linha.appendChild(celula);


		/************ FINALIZA LINHA DA TABELA ***********/
		var tbody = document.createElement('TBODY');
		tbody.appendChild(nova_linha);
		tbl.appendChild(tbody);

		$('#competencia_futura_'+linha).maskedinput('99/9999');
		$('#valor_'+linha).maskMoney({showSymbol:"", symbol:"", decimal:",", precision:2, thousands:".",maxlength:10});
	};

	if (valida_linha.value == 'nao') {
		valida_linha.value = 'ok';
	}

};

function removerIntegridade(iidd){
	var tbl = document.getElementById('tbl_integridade');
	tbl.deleteRow(iidd.parentNode.parentNode.rowIndex);
}

function criaCelula(texto) {
	var celula = document.createElement('td');
	var textoNode = document.createTextNode(texto);
	celula.appendChild(textoNode);
	return celula;
}

function Numero(e){
	if (window.event){   //IE
		tecla = e.keyCode;
	} else if (e.which){ //FF
		tecla = e.which;
	}
	//teclas dos numemros(0 - 9) de 48 a 57
	//techa==8 é para permitir o backspace funcionar para apagar
	if ( (tecla >= 48 && tecla <= 57)||(tecla == 8 ) ) {
		 true;
	}else{
		return false;
	}
}

function chamar_funcao(linha) {
	var validar = document.getElementById("validacao").value;
	var valida_linha = document.getElementById("valida_linha_"+linha).value;
	if (validar != 'sim' && valida_linha == 'nao') {
		function1(linha);
	}
}

function valida_codigo(linha) {
	var linha = linha;
	var codigo = document.getElementById("posto_codigo_"+linha).value;
	var nome = document.getElementById("posto_nome_"+linha).value;

	if (codigo != '' && nome == '') {
		document.getElementById("posto_codigo_"+linha).value = "";
		document.getElementById("validacao").value='sim';
	};
}

function validacao_campos(linha,mensagem) {
	var mensagem = mensagem;
	//alert(mensagem);
	var linha = linha;
	var codigo = document.getElementById("posto_codigo_"+linha).value;
	var nome = document.getElementById("posto_nome_"+linha).value;
	var lancamento = document.getElementById("lancamento_"+linha).value;
	var historico = document.getElementById("historico_"+linha).value;
	var valor = document.getElementById("valor_"+linha).value;
	var competencia_futura = document.getElementById("competencia_futura_"+linha).value;
	var extrato_lancamento = document.getElementById("extrato_lancamento_"+linha).value;
	var erro = '';

	if (extrato_lancamento.length == 0) {
		if (codigo == '') {
			erro += 'CODIGO - ';
		}

		if (nome == '') {
			erro += 'NOME - ';
		}

		if (lancamento == '') {
			erro += 'DESCRICAO - ';
		}

		if (historico == '') {
			erro += 'HISTORICO - ';
		}

		if (valor == '') {
			erro += 'VALOR - ';
		}

		if (competencia_futura != '__/____' && competencia_futura.length>0) {
			var hoje = new Date();
			var ano = competencia_futura.substr(3,4) ;
			var mes = competencia_futura.substr(0,2);
			var dia = hoje.getDate();
	//		alert(dia);

			var data = new Date();
			data.setFullYear(ano,mes-1,dia);

			var hoje = new Date();


			if (data<hoje){
				erro += '| COMPETENCIA: A DATA DE COMPETÊNCIA DEVE SER MAIOR QUE A DATA ATUAL |';
			} else {
				var mes_novo = new Number(mes);
                if(mes_novo < 12){
                    mes_novo = new Number(mes_novo + 1);
                }else{
                    mes_novo = 1;
                }
                <?php if ($login_fabrica <> '74'): ?>
				alert('COMPETENCIA: ESTE LANCAMENTO SERÁ LANÇADO NO EXTRATO DO MÊS '+ mes_novo +'');
                <?php endif; ?>
			}

		}
	}
	//linha = linha + 1;
	var linha_msg = linha + 1;
	if (erro != '') {
		if (mensagem != '0') {
			alert('Existem erros nos campos: '+erro+' da linha '+linha_msg);
		}
		document.getElementById("validacao").value='sim';
	}
	else {
		if (document.getElementById("valida_linha_"+linha).value != 'ok') {
			document.getElementById("valida_linha_"+linha).value='nao';
		}
		document.getElementById("validacao").value='nao';
	}
}

//Formata número tipo moeda usando o evento onKeyDown

function MascaraMoeda(campo,tammax,teclapres,decimal) {
	var regrenpontos = new RegExp("[,.]+","g");
	var regrenZeros = new RegExp("^0+","g");

	var tecla = teclapres.keyCode;
	vr = Limpar(campo.value,"0123456789");
	vr = vr.replace(regrenpontos,"");
	vr = vr.replace(regrenZeros,"");
	tam = vr.length;
	dec=decimal

	if (tam < tammax && tecla != 8)
		{ tam = vr.length + 0 ; }

	if (tecla == 8 )
		{ tam = tam - 0 ; }

	if ( tecla == 8 || tecla >= 48 && tecla <= 57 || tecla >= 96 && tecla <= 105 ){
		if ( tam <= dec ){
			campo.value = vr ;
		}

		if ( (tam > 0) && (tam <= 1) ){
			campo.value = vr.substr( 0, tam - 2 ) + "0.0" + vr.substr( tam - dec, tam ) ;
		}

		if ( (tam > 1) && (tam <= 2) ){
			campo.value = vr.substr( 0, tam - 2 ) + "0." + vr.substr( tam - dec, tam ) ;
		}

		if ( (tam > 2) && (tam <= 20) ){
			campo.value = vr.substr( 0, tam - 2 ) + "." + vr.substr( tam - dec, tam ) ;
		}
	}
}

function SomenteNumero(e){
	if (window.event){   //IE
		tecla = e.keyCode;
	}else if (e.which){ //FF
		tecla = e.which;
	}
	//teclas dos numemros(0 - 9) de 48 a 57
	//techa==8 é para permitir o backspace funcionar para apagar
	if ( (tecla >= 48 && tecla <= 57)||(tecla == 8 ) ) {
		return true;
	}else{
		return false;
	}
}

</script>

<script language='javascript' src='ajax.js'></script>
<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>
<?if (strlen ($msg_erro) > 0) {?>
<table border="0" cellpadding="0" cellspacing="0" align="center" width = '700'>
	<tr>
		<td valign="middle" align="center" class='msg_erro'>
			<?
			echo $msg_erro;
			?>
		</td>
	</tr>
</table>
<? } ?>
<FORM METHOD='POST' NAME='frm_extrato_avulso' ACTION="<? echo $PHP_SELF ?>">
	<input type='hidden' name='btn_acao' value=''>
	<input type='hidden' name='posto' value='<?echo $posto;?>'>
	<input type='hidden' name='extrato' value='<?echo $extrato;?>'>

	<table width='700' align='center' border='0' cellspacing='4' cellpadding='2'>
		<tr class='texto_avulso'>
<?
    if($login_fabrica != 74){
        $texto = "data de competência";
    }else{
        $texto = "data de validade";
    }
?>
			<td ALIGN='center'>
				Informar a <?=$texto?> caso necessite definir o mês do pagamento. Avulsos sem a <?=$texto?> entrarão automaticamente no próximo fechamento de extrato.
			</td>
		</tr>
	</table>

	<br />

	<TABLE width='100%' border='0' align='center' cellspacing='2' cellpadding='4' name='tabela_itens' id='tabela_itens' class='formulario'>
		<thead>
			<caption class='titulo_tabela' border='0'>Lançamento</caption>
			<TR class='titulo_coluna'>
				<td>Código do Posto</td>
				<td>Nome do Posto</td>
				<td>Descrição</td>
				<td>Histórico</td>
				<td>Valor</td>
				<td><? if($login_fabrica != 74){echo "Competência Futura";}else{echo "Data Validade";} ?></td>
			</tr>
		</thead>
		<tbody>
			<?
			$tem_lancamento = false;
			if(strlen($extrato) > 0 ) {
				$sql = " SELECT tbl_posto_fabrica.codigo_posto,
								tbl_posto.nome,
								tbl_extrato_lancamento.lancamento,
								tbl_extrato_lancamento.extrato_lancamento,
								tbl_extrato_lancamento.historico,
								tbl_extrato_lancamento.valor,
								to_char(tbl_extrato_lancamento.competencia_futura,'MM/YYYY') as competencia_futura
						FROM tbl_extrato_lancamento
						JOIN tbl_posto USING(posto)
						JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
						WHERE extrato = $extrato
						AND   lancamento not in (61,73,81,104)";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) > 0){
					$qtde_produto = pg_num_rows($res) + 1;
					$tem_lancamento = true;
				}else{
					$qtde_produto = 1;
				}
			}

			if ($qtde_produto == '') {
				$qtde_produto = 1;
			}

			for ( $i = 0 ; $i < $qtde_produto ; $i++ ) {
				if(strlen($extrato) > 0 ) {
					$j = $i-1;
					if($tem_lancamento and $i < $qtde_produto) {
						$lancamento         = pg_fetch_result($res,$j,lancamento);
						$extrato_lancamento = pg_fetch_result($res,$j,extrato_lancamento);
						$historico          = pg_fetch_result($res,$j,historico);
						$valor              = pg_fetch_result($res,$j,valor);
						$competencia_futura = pg_fetch_result($res,$j,competencia_futura);
						$aux_competencia_futura = pg_fetch_result($res,$j,competencia_futura);
					}else{
						$lancamento         = "";
						$extrato_lancamento = "";
						$historico          = "";
						$valor              = "";
						$competencia_futura = "";
						$aux_competencia_futura = "";
					}
					if (strlen ($msg_erro) > 0) {
						$posto_codigo = $_POST['posto_codigo_'.$i];
						$posto_nome = $_POST['posto_nome_'.$i];
						$lancamento         = $_POST ['lancamento_' . $i] ;
						$historico          = $_POST ['historico_' . $i] ;
						$valor              = $_POST ['valor_' . $i] ;
						$extrato_lancamento =  $_POST ['extrato_lancamento_' . $i] ;
						$competencia_futura = $_POST ['competencia_futura_' . $i] ;
						$aux_competencia_futura = $_POST ['competencia_futura_' . $i] ;
					}

				}
				$cor = "#F1F4FA";
				if ($i % 2 == 0) $cor = "#F7F5F0";
				?>
				<tr align='left' bgcolor="<?php echo $cor; ?>">
					<td nowrap align='left' nowrap>
						<input type='hidden' name='extrato_lancamento[]' value='<?=$extrato_lancamento?>'>
						<input type='hidden' name='extrato_lancamento_<?=$i?>' value='<?=$extrato_lancamento?>' id='extrato_lancamento_<?=$i?>'>
						<input type="text" name="posto_codigo[]" id="posto_codigo_<?=$i?>" size="15"  value="<?echo $posto_codigo?>"
						<?if(empty($posto)){ ?>onfocus="autocompletar_descricao('posto_codigo_<?=$i?>',this.value,'posto_nome_<?=$i?>')" <?}?>onblur="javascript: if (this.value != '' && posto_nome_<?=$i;?>.value == '') { this.value = '';validacao.value='sim';} validacao_campos(<?echo $i;?>,0);" <?=$readonly?> class='frm'>
					</td>
					<td align='left' nowrap>
						<input type="text" name="posto_nome[]" id="posto_nome_<?=$i?>" size="30" value="<?echo $posto_nome?>" <?if(empty($posto)){ ?>onfocus="autocompletar_descricao2('posto_nome_<?=$i?>',this.value,'posto_codigo_<?=$i?>')" <?}?> onblur="validacao_campos(<?echo $i;?>,0);" <?=$readonly?> class='frm'>
					</td>
					<td align='left' nowrap>
						<select style='width: 170px;' id='lancamento_<?=$i?>' name='lancamento[]' onchange="validacao_campos(<?echo $i;?>,0);" class='frm'>
						<option value=''>ESCOLHA</option>
						<?
						$select="";
						$sql = "SELECT  lancamento, descricao
								FROM    tbl_lancamento
								WHERE   tbl_lancamento.fabrica = $login_fabrica
                                AND     tbl_lancamento.ativo IS TRUE
								ORDER BY tbl_lancamento.descricao;";
						$res1 = pg_exec ($con,$sql);
						if(pg_numrows($res1) > 0){
							for($y=0;$y<pg_numrows($res1);$y++){
								$xlancamento     = pg_result($res1,$y,lancamento);
								$xdescricao =       pg_result($res1,$y,descricao);
								echo "<option value='$xlancamento'"; if($lancamento == $xlancamento) echo " SELECTED "; echo ">$xdescricao</option>";
							}
						}
						?>
						</select>
					</td>
					<td>
						<textarea id="historico_<?=$i?>" name="historico[]" rows="3" cols="30" onblur="validacao_campos(<?echo $i;?>,0);" class='frm'><?echo $historico;?></textarea>
					</td>
					<td>
						<input type='text' id='valor_<?=$i?>' name='valor[]' value='<?echo $valor;?>' style="text-align:right;" size='10' maxlength='10' onblur="validacao_campos(<?=$i;?>);javascript: if(validacao.value != 'sim' && valida_linha_<?=$i?>.value == 'nao') { function1(<?echo $i;?>); };" class='frm'>
					</td>
					<td align='center'>
						<input type='text' id='competencia_futura_<?=$i?>' name='competencia_futura[]' value='<?echo $aux_competencia_futura;?>' id='competencia_futura_<?=$i?>' size='8' maxlength='7' onblur="validacao_campos(<?echo $i;?>,1);" class='frm'>


						<input type='hidden' id='valida_linha_<?=$i?>' name='valida_linha[]' value='sim' size='8' maxlength='7' onblur="validacao_campos(<?echo $i;?>,1);">
					</td>


				</tr>
			<?}?>
		</tbody>
	</table>
	<input type='hidden' name='validacao' id='validacao' value='sim'>
	<INPUT TYPE='hidden' NAME='qtde_produto' value='<? echo $i= $i-1;?>' id='qtde_produto'>

	<p>
	<input type='button' value='Gravar' onclick="javascript: if (document.frm_extrato_avulso.btn_acao.value==''){
		document.frm_extrato_avulso.btn_acao.value='gravar' ;
		document.frm_extrato_avulso.submit();
	}else{
		alert('Aguarde submissão');
	}
	" ALT="Gravar formulário" border='0'>
</form>

<p>
<? include "rodape.php"; ?>
