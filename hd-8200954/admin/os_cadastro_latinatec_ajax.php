<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "call_center,gerencia";

include 'autentica_admin.php';

include 'funcoes.php';

$sql = "SELECT * FROM tbl_fabrica WHERE fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);
$pedir_sua_os = pg_result ($res,0,pedir_sua_os);


if (strlen($_POST['os']) > 0)    $os     = trim($_POST['os'])    ;
if (strlen($_GET['os']) > 0)     $os     = trim($_GET['os'])     ;
if (strlen($_POST['sua_os']) > 0)$sua_os = trim($_POST['sua_os']);
if (strlen($_GET['sua_os']) > 0) $sua_os = trim($_GET['sua_os']) ;

if (strlen($_GET['referencia']) > 0)	$referencia = trim($_GET['referencia']) ;
if (strlen($_GET['ns']) > 0)			$ns = trim($_GET['ns']) ;

//Validação de Número de Série para LatinaTec
if (trim($_GET['verificarNumeroSerie']) == '1'){

	if (strlen($referencia) > 0 AND strlen($ns) > 0){

		$referencia_produto = trim($referencia);
		$numero_serie       = strtoupper(trim($ns));
		$sql = "SELECT numero_serie_obrigatorio
				from tbl_produto
				where referencia = '$referencia_produto'
				";
		//		and tbl_produto.ativo is true
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$serie_obrigatorio = pg_result($res,0,0);
			if($serie_obrigatorio=="t"){
				if(strlen($numero_serie)>10 or strlen($numero_serie)<8){
					echo "Número inválido. Tamanho inválido";
					exit;
				}
				$sql = "SELECT TO_CHAR(CURRENT_DATE,'y')::numeric";
				$res = pg_exec($con,$sql);
				$ano_corrente = pg_result($res,0,0);
			
				$meses = array('A','B','C','D','E','F','G','H','I',
							'J','K','L','M','N','O','P','Q','R','S',
							'T','U','V','W','Y','X','Z');
				
				$sql ="SELECT SUBSTR('ABCDEFGHIJKLMNOPQRSTUVWYXZ',TO_CHAR(CURRENT_DATE,'YYYY')::INTEGER - 1994,1)"; 
			//	echo $sql;
				$res = pg_exec($con,$sql);
				$letra_ano = pg_result($res,0,0);

				$sql ="SELECT SUBSTR('ABCDEFGHIJKL',TO_CHAR(CURRENT_DATE,'MM')::INTEGER ,1)"; 
				//echo $sql;
				$res = pg_exec($con,$sql);
				$letra_mes = pg_result($res,0,0);

		//		echo substr($numero_serie, 0, 1);

				$letra_inicial = array('1','4','9');
				if(!in_array(substr($numero_serie, 0, 1),$letra_inicial)){
		//			echo substr($numero_serie, 0, 1);
					echo "Erro no primeiro digito. Tem que ser 1 ou 4 ou 9";
					exit;
				}

		//		echo "<BR>segunda letra ".substr($numero_serie, 1, 1);
				if(is_numeric(substr($numero_serie, 1, 1))){
				//	echo substr($numero_serie, 1, 1);
					echo "Erro no segundo digito. Tem que ser letra";
					exit;
				}

				//echo "<BR>Terceira letra ".substr($numero_serie, 2, 1);
				if(is_numeric(substr($numero_serie, 2, 1))){
				//	echo substr($numero_serie, 2, 1);
					echo "Erro no terceiro digito. Tem que ser letra";
					exit;
				}

				/* QUARTO CARACTER TEM QUE SER LETRA. ANO */
				/* ANO NÃO PODE SER MAIOR QUE O ATUAL */
				//echo "<BR>Quarta letra ".substr($numero_serie, 3, 1);
				//echo "<BR>ano corrente $letra_ano <BR>";
				if(is_numeric(substr($numero_serie, 3, 1)) or substr($numero_serie, 3, 1) > $letra_ano){
			//		echo substr($numero_serie, 3, 1);
					echo " Erro no Quarta digito. Tem que ser letra";
					exit;
				}
				
				/* QUANDO ANO CORRENTE O MES NÃO PODE SER MAIOR QUE O ATUAL */
				//echo "<BR>Quarta letra 2 - ".substr($numero_serie, 3, 1);
				//echo "<BR>mes corrente $letra_mes <BR> mes da OS ".substr($numero_serie, 2, 1)."<BR>";
				if(substr($numero_serie, 3, 1) == $letra_ano){
					if(substr($numero_serie, 2, 1) > $letra_mes){
					//	echo substr($numero_serie, 3, 1);
						echo " Fabricado neste ano, mas o mes esta superior[".substr($numero_serie, 2, 1)."] que o atual [$letra_mes]";
						exit;
					}
				}
			//	echo "resto : ".substr($numero_serie, 4,strlen(trim($numero_serie))-3);
				if(!is_numeric(substr($numero_serie, 4,strlen(trim($numero_serie))-3) )){
					echo "Erro, radical final tem que ser número. Radical final: ".substr($numero_serie, 4,strlen(trim($numero_serie))-3);
					exit;
				}
			//	echo "<BR><BR><STRONG>PARABENS!!! NÚMERO DE SÉRIE SEM PROBLEMAS!!!</STRONG><br><br>";

			}else{
				echo "Número de série não obrigatório";
				exit;
			}
		}else{
			echo "Produto não encontrado";
			exit;
		}


		//fazer validação
	}
	exit;
}

/*================ LE OS DA BASE DE DADOS =========================*/

if (strlen ($os) > 0) {
	$sql = "SELECT	tbl_os.os                                           ,
			tbl_os.tipo_atendimento                                     ,
			tbl_os.posto                                                ,
			tbl_posto.nome                             AS posto_nome    ,
			tbl_os.sua_os                                               ,
			to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura ,
			to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento ,
			tbl_os.produto                                              ,
			tbl_produto.referencia                                      ,
			tbl_produto.descricao                                       ,
			tbl_os.serie                                                ,
			tbl_os.qtde_produtos                                        ,
			tbl_os.cliente                                              ,
			tbl_os.consumidor_nome                                      ,
			tbl_os.consumidor_cpf                                       ,
			tbl_os.consumidor_fone                                      ,
			tbl_os.consumidor_cidade                                    ,
			tbl_os.consumidor_estado                                    ,
			tbl_os.consumidor_cep                                       ,
			tbl_os.consumidor_endereco                                  ,
			tbl_os.consumidor_numero                                    ,
			tbl_os.consumidor_complemento                               ,
			tbl_os.consumidor_bairro                                    ,
			tbl_os.revenda                                              ,
			tbl_os.revenda_cnpj                                         ,
			tbl_os.revenda_nome                                         ,
			tbl_os.nota_fiscal                                          ,
			to_char(tbl_os.data_nf,'DD/MM/YYYY')       AS data_nf       ,
			tbl_os.aparencia_produto                                    ,
			tbl_os_extra.orientacao_sac                                 ,
			tbl_os_extra.admin_paga_mao_de_obra                        ,
			tbl_os.acessorios                                           ,
			tbl_os.fabrica                                              ,
			tbl_os.quem_abriu_chamado                                   ,
			tbl_os.obs                                                  ,
			tbl_os.consumidor_revenda                                   ,
			tbl_os_extra.extrato                                        ,
			tbl_posto_fabrica.codigo_posto             AS posto_codigo  ,
			tbl_os.codigo_fabricacao                                    ,
			tbl_os.satisfacao                                           ,
			tbl_os.laudo_tecnico                                        ,
			tbl_os.troca_faturada                                       ,
			tbl_os.admin                                                ,
			tbl_os.troca_garantia
			FROM	tbl_os
			JOIN	tbl_produto          ON tbl_produto.produto       = tbl_os.produto
			JOIN	tbl_posto            ON tbl_posto.posto           = tbl_os.posto
			JOIN	tbl_fabrica          ON tbl_fabrica.fabrica       = tbl_os.fabrica
			JOIN	tbl_posto_fabrica    ON tbl_posto_fabrica.posto   = tbl_posto.posto
										AND tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
										AND tbl_fabrica.fabrica       = $login_fabrica
			LEFT JOIN	tbl_os_extra     ON tbl_os.os                 = tbl_os_extra.os
			WHERE	tbl_os.os      = $os
			AND		tbl_os.fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows ($res) == 1) {
		$os			= pg_result ($res,0,os);
		$tipo_atendimento	= pg_result ($res,0,tipo_atendimento);
		$posto			= pg_result ($res,0,posto);
		$posto_nome		= pg_result ($res,0,posto_nome);
		$sua_os			= pg_result ($res,0,sua_os);
		$data_abertura	= pg_result ($res,0,data_abertura);
		$data_fechamento = pg_result ($res,0,data_fechamento);
		$produto_referencia	= pg_result ($res,0,referencia);
		$produto_descricao	= pg_result ($res,0,descricao);
		$produto_serie		= pg_result ($res,0,serie);
		$qtde_produtos      = pg_result ($res,0,qtde_produtos);
		$cliente		= pg_result ($res,0,cliente);
		$consumidor_nome	= pg_result ($res,0,consumidor_nome);
		$consumidor_cpf		= pg_result ($res,0,consumidor_cpf);
		$consumidor_fone	= pg_result ($res,0,consumidor_fone);
		$consumidor_cep			= trim (pg_result ($res,0,consumidor_cep));
		$consumidor_endereco	= trim (pg_result ($res,0,consumidor_endereco));
		$consumidor_numero		= trim (pg_result ($res,0,consumidor_numero));
		$consumidor_complemento	= trim (pg_result ($res,0,consumidor_complemento));
		$consumidor_bairro		= trim (pg_result ($res,0,consumidor_bairro));
		$consumidor_cidade		= pg_result ($res,0,consumidor_cidade);
		$consumidor_estado	= pg_result ($res,0,consumidor_estado);
				
		$revenda		= pg_result ($res,0,revenda);
		$revenda_cnpj		= pg_result ($res,0,revenda_cnpj);
		$revenda_nome		= pg_result ($res,0,revenda_nome);
		$nota_fiscal		= pg_result ($res,0,nota_fiscal);
		$data_nf		= pg_result ($res,0,data_nf);
		$aparencia_produto	= pg_result ($res,0,aparencia_produto);
		$acessorios		= pg_result ($res,0,acessorios);
		$fabrica		= pg_result ($res,0,fabrica);
		$posto_codigo		= pg_result ($res,0,posto_codigo);
		$extrato		= pg_result ($res,0,extrato);
		$quem_abriu_chamado	= pg_result ($res,0,quem_abriu_chamado);
		$obs			= pg_result ($res,0,obs);
		$consumidor_revenda 	= pg_result ($res,0,consumidor_revenda);
		$codigo_fabricacao	= pg_result ($res,0,codigo_fabricacao);
		$satisfacao		= pg_result ($res,0,satisfacao);
		$laudo_tecnico		= pg_result ($res,0,laudo_tecnico);
		$troca_faturada		= pg_result ($res,0,troca_faturada);
		$troca_garantia		= pg_result ($res,0,troca_garantia);
		$admin_os		= trim(pg_result ($res,0,admin));

		$orientacao_sac	= pg_result ($res,0,orientacao_sac);
		$orientacao_sac = html_entity_decode ($orientacao_sac,ENT_QUOTES);
		$orientacao_sac = str_replace ("<br />","",$orientacao_sac);

		$admin_paga_mao_de_obra = pg_result ($res,0,admin_paga_mao_de_obra);
		
		$sql =	"SELECT tbl_os_produto.produto ,
						tbl_os_item.pedido     
				FROM    tbl_os 
				JOIN    tbl_produto using (produto)
				JOIN    tbl_posto using (posto)
				JOIN    tbl_fabrica using (fabrica)
				JOIN    tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_posto.posto
										  AND tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica 
				JOIN    tbl_os_produto USING (os)
				JOIN    tbl_os_item
				ON      tbl_os_item.os_produto = tbl_os_produto.os_produto
				WHERE   tbl_os.os = $os
				AND     tbl_os.fabrica = $login_fabrica";
		$res = pg_exec ($con,$sql);

		if(pg_numrows($res) > 0){
			$produto = pg_result($res,0,produto);
			$pedido  = pg_result($res,0,pedido);
		}

		$sql = "SELECT * FROM tbl_os_extra WHERE os = $os";
		$res = pg_exec($con,$sql);
	
		if (pg_numrows($res) == 1) {
			$taxa_visita              = pg_result ($res,0,taxa_visita);
			$visita_por_km            = pg_result ($res,0,visita_por_km);
			$hora_tecnica             = pg_result ($res,0,hora_tecnica);
			$regulagem_peso_padrao    = pg_result ($res,0,regulagem_peso_padrao);
			$certificado_conformidade = pg_result ($res,0,certificado_conformidade);
			$valor_diaria             = pg_result ($res,0,valor_diaria);
		}
		
		//SELECIONA OS DADOS DO CLIENTE PRA JOGAR NA OS
		if (strlen($consumidor_cidade)==0){
		if (strlen($cpf) > 0 OR strlen($cliente) > 0 ) {
			$sql = "SELECT
					tbl_cliente.cliente,
					tbl_cliente.nome,
					tbl_cliente.endereco,
					tbl_cliente.numero,
					tbl_cliente.complemento,
					tbl_cliente.bairro,
					tbl_cliente.cep,
					tbl_cliente.rg,
					tbl_cliente.fone,
					tbl_cliente.contrato,
					tbl_cidade.nome AS cidade,
					tbl_cidade.estado
					FROM tbl_cliente
					LEFT JOIN tbl_cidade USING (cidade)
					WHERE 1 = 1";
			if (strlen($cpf) > 0) $sql .= " AND tbl_cliente.cpf = '$cpf'";
			if (strlen($cliente) > 0) $sql .= " AND tbl_cliente.cliente = '$cliente'";

			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) == 1) {
				$consumidor_cliente     = trim (pg_result ($res,0,cliente));
				$consumidor_fone        = trim (pg_result ($res,0,fone));
				$consumidor_nome        = trim (pg_result ($res,0,nome));
				$consumidor_endereco    = trim (pg_result ($res,0,endereco));
				$consumidor_numero      = trim (pg_result ($res,0,numero));
				$consumidor_complemento = trim (pg_result ($res,0,complemento));
				$consumidor_bairro      = trim (pg_result ($res,0,bairro));
				$consumidor_cep         = trim (pg_result ($res,0,cep));
				$consumidor_rg          = trim (pg_result ($res,0,rg));
				$consumidor_cidade      = trim (pg_result ($res,0,cidade));
				$consumidor_estado      = trim (pg_result ($res,0,estado));
				$consumidor_contrato    = trim (pg_result ($res,0,contrato));
			}
		}	
	}
	}
}

/*============= RECARREGA FORM EM CASO DE ERRO ==================*/

if (strlen($msg_erro) > 0 and $btn_troca <> "trocar") {
	$os                 = $_POST['os'];
	$tipo_atendimento   = $_POST['tipo_atendimento'];
	$sua_os             = $_POST['sua_os'];
	$data_abertura      = $_POST['data_abertura'];
	$data_fechamento    = $_POST['data_fechamento'];
	$cliente            = $_POST['cliente'];
	$consumidor_nome    = $_POST['consumidor_nome'];
	$consumidor_cpf     = $_POST['consumidor_cpf'];
	$consumidor_fone    = $_POST['consumidor_fone'];
	$revenda            = $_POST['revenda'];
	$revenda_cnpj       = $_POST['revenda_cnpj'];
	$revenda_nome       = $_POST['revenda_nome'];
	$nota_fiscal        = $_POST['nota_fiscal'];
	$data_nf            = $_POST['data_nf'];
	$produto_referencia = $_POST['produto_referencia'];
	$cor                = $_POST['cor'];
	$acessorios         = $_POST['acessorios'];
	$aparencia_produto  = $_POST['aparencia_produto'];
	$obs                = $_POST['obs'];
	$orientacao_sac     = $_POST['orientacao_sac'];
	$consumidor_revenda = $_POST['consumidor_revenda'];
	$qtde_produtos      = $_POST['qtde_produtos'];
	$produto_serie      = $_POST['produto_serie'];

	$codigo_fabricacao  = $_POST['codigo_fabricacao'];
	$satisfacao         = $_POST['satisfacao'];
	$laudo_tecnico      = $_POST['laudo_tecnico'];
	$troca_faturada     = $_POST['troca_faturada'];

	$quem_abriu_chamado       = $_POST['quem_abriu_chamado'];
	$taxa_visita              = $_POST['taxa_visita'];
	$visita_por_km            = $_POST['visita_por_km'];
	$hora_tecnica             = $_POST['hora_tecnica'];
	$regulagem_peso_padrao    = $_POST['regulagem_peso_padrao'];
	$certificado_conformidade = $_POST['certificado_conformidade'];
	$valor_diaria             = $_POST['valor_diaria'];

	$admin_paga_mao_de_obra   = $_POST['admin_paga_mao_de_obra'];

	$sql =	"SELECT descricao
			FROM    tbl_produto
			JOIN    tbl_linha USING (linha)
			WHERE   tbl_produto.referencia = UPPER ('$produto_referencia')
			AND     tbl_linha.fabrica      = $login_fabrica
			AND     tbl_produto.ativo IS TRUE";
	$res = pg_exec ($con,$sql);
	$produto_descricao = @pg_result ($res,0,0);
}


if(strlen($os)==0)$body_onload = "onload = 'javascript: document.frm_os.posto_codigo.focus()'";
$title       = "Cadastro de Ordem de Serviço - ADMIN"; 
$layout_menu = 'callcenter';

include "cabecalho.php";

?>

<!--=============== <FUNÇÕES> ================================!-->


<? include "javascript_pesquisas.php" ?>

<script language='javascript' src='ajax.js'></script>
<script language='javascript' src='ajax_produto.js'></script>

<script language="JavaScript">

function VerificaSuaOS (sua_os){
	if (sua_os.value != "") {
		janela = window.open("pesquisa_sua_os.php?sua_os=" + sua_os.value,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=500,height=250,top=50,left=10");
		janela.focus();
	}
}

// ========= Função PESQUISA DE POSTO POR CÓDIGO OU NOME ========= //

function fnc_pesquisa_posto2 (campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&proximo=t";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		if ("<? echo $pedir_sua_os; ?>" == "t") {
			janela.proximo = document.frm_os.data_abertura;
		}else{
			janela.proximo = document.frm_os.sua_os;
		}
		janela.focus();
	}
}

// ========= Função PESQUISA DE PRODUTO POR REFERÊNCIA OU DESCRIÇÃO ========= //

function fnc_pesquisa_produto2 (campo, campo2, tipo, voltagem) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&proximo=t";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.referencia   = campo;
		janela.descricao    = campo2;
		janela.proximo      = document.frm_os.produto_serie;
		if (voltagem != "") {
			janela.voltagem = voltagem;
		}
		janela.focus();
	}
}

// ========= Função PESQUISA DE PRODUTO LISTA BÁSICA  ========= //
function fnc_pesquisa_lista_basica (produto_referencia, peca_referencia, peca_descricao, peca_preco, voltagem, tipo, peca_qtde) {
        var url = "";
        if (tipo == "tudo") {
                url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
        }

        if (tipo == "referencia") {
                url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&peca=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value +"&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
        }

        if (tipo == "descricao") {
                url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value +"&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
        }
        janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
        janela.produto          = produto_referencia;
        janela.referencia       = peca_referencia;
        janela.descricao        = peca_descricao;
        janela.preco            = peca_preco;
        janela.qtde             = peca_qtde;
        janela.focus();

}

/* ========== Função AJUSTA CAMPO DE DATAS =========================
Nome da Função : ajustar_data (input, evento)
		Ajusta a formatação da Máscara de DATAS a medida que ocorre
		a digitação do texto.
=================================================================*/
function ajustar_data(input , evento){
	var BACKSPACE=  8; 
	var DEL=  46; 
	var FRENTE=  39; 
	var TRAS=  37; 
	var key; 
	var tecla; 
	var strValidos = "0123456789" ;
	var temp;
	tecla= (evento.keyCode ? evento.keyCode: evento.which ? evento.which : evento.charCode)

	if (( tecla == BACKSPACE )||(tecla == DEL)||(tecla == FRENTE)||(tecla == TRAS)) {
		return true; 
	}
	if ( tecla == 13) return false; 
	if ((tecla<48)||(tecla>57)){
		return false;
	}
	key = String.fromCharCode(tecla); 
	input.value = input.value+key;
	temp="";
	for (var i = 0; i<input.value.length;i++ ){
		if (temp.length==2) temp=temp+"/";
		if (temp.length==5) temp=temp+"/";
		if ( strValidos.indexOf( input.value.substr(i,1) ) != -1 ) {
			temp=temp+input.value.substr(i,1);
		}
	}
	input.value = temp.substr(0,10);
	return false;
}


function verificarNS(numero){

	ns = numero.value;
	if (ns.length>0){
		var referencia = document.getElementById('produto_referencia').value;

		if (referencia.length==0){
			return false;
		}

		var curDateTime = new Date();

		url = "<?=$PHP_SELF ?>?verificarNumeroSerie=1&referencia="+referencia+"&ns="+ns+"&data="+curDateTime;

		http_prod[curDateTime] = createRequestObject();
		http_prod[curDateTime].open('GET',url,true);
		http_prod[curDateTime].onreadystatechange = function(){
			if (http_prod[curDateTime].readyState == 4){
				if (http_prod[curDateTime].status == 200 || http_prod[curDateTime].status == 304){
					var response2 = http_prod[curDateTime].responseText;
					if (response2.length>0){
						alert(response2);
					}
				}
			}
		}
		http_prod[curDateTime].send(null);
	}
}

function listaSolucao(defeito_constatado, produto_linha,defeito_reclamado, produto_familia) {
//verifica se o browser tem suporte a ajax
		try {ajax = new ActiveXObject("Microsoft.XMLHTTP");} 
	catch(e) { try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
	catch(ex) { try {ajax = new XMLHttpRequest();}
		catch(exc) {alert("Esse browser não tem recursos para uso do Ajax"); ajax = null;}
	}
	}
//se tiver suporte ajax
		if(ajax) {
	//deixa apenas o elemento 1 no option, os outros são excluídos
			document.forms[0].solucao_os.options.length = 1;
	//opcoes é o nome do campo combo
			idOpcao  = document.getElementById("opcoes");
	//	 ajax.open("POST", "ajax_produto.php", true);
	ajax.open("GET", "ajax_solucao.php?defeito_constatado="+defeito_constatado+"&defeito_reclamado="+defeito_reclamado+"&produto_linha="+produto_linha+"&produto_familia="+produto_familia);
	ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	
	ajax.onreadystatechange = function() {
		if(ajax.readyState == 1) {idOpcao.innerHTML = "Carregando...!";}//enquanto estiver processando...emite a msg
		if(ajax.readyState == 4 ) {
			if(ajax.responseXML) { 
				montaComboSolucao(ajax.responseXML);//após ser processado-chama fun
			} else {
				idOpcao.innerHTML = "Selecione o defeito constatado";//caso não seja um arquivo XML emite a mensagem abaixo
			}
		}
	}
	//passa o código do produto escolhido
			var params = "defeito_constatado="+defeito_constatado+"&defeito_reclamado="+defeito_reclamado+"&produto_linha="+produto_linha+"&produto_familia="+produto_familia;
	ajax.send(null);
		}
}

</script>

<!--========================= AJAX ==================================.-->
<script language='javascript' src='ajax.js'></script>
<script language='javascript' src='ajax_cep.js'></script>
<script language='javascript' src='ajax_os_cadastro.js'></script>
<? include "javascript_pesquisas.php" ?>

<style>

.Label{
font-family: Verdana;
font-size: 10px;
}
.Titulo{
font-family: Verdana;
font-size: 12px;
font-weight: bold;
}
.Erro{
font-family: Verdana;
font-size: 12px;
color:#FFF;
border:#485989 1px solid; background-color: #990000;
}


</style>
<!-- alterado gustavo HD 6673 25/10/2007 -->
<form style="MARGIN: 0px; WORD-SPACING: 0px" name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
		<input type="hidden" name="os"        value="<?echo $os?>">
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td valign="top" align="left">
		<table style=' border:#485989 1px solid; background-color: #e6eef7 ' align='center' width='750' border='0'>
		<tr>
			<td nowrap class='Label'>Código do Posto</td>
			<td><input class="frm" type="text" name="posto_codigo" size="15" value="<? echo $posto_codigo ?>" onblur="fnc_pesquisa_posto2(document.frm_os.posto_codigo,document.frm_os.posto_nome,'codigo')" >&nbsp;<img src='imagens/btn_lupa_novo.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_os.posto_codigo,document.frm_os.posto_nome,'codigo')">
			</td>

			<td nowrap class='Label'>Nome do Posto</td>
			<td><input class="frm" type="text" name="posto_nome" size="50" value="<? echo $posto_nome ?>">&nbsp;<img src='imagens/btn_lupa_novo.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_os.posto_codigo,document.frm_os.posto_nome,'nome')" style="cursor:pointer;"></A>
			</td>
		</tr>
		</table>
	</td>
</tr>
<tr><td><img height="2" width="16" src="imagens/spacer.gif"></td></tr>
<tr>
	<td valign="top" align="left">

		<!-- Informações da OS  -->
		<table style=' border:#485989 1px solid; background-color: #e6eef7 ' align='center' width='750' border='0'>
		<tr valign="top">
			<?php if($login_fabrica != 15){ ?>
			<td class='Label'>OS Fabricante</td>
			<td><input name ="sua_os" class ="frm" type = "text" size = "15" maxlength = "20" value ="<? echo $sua_os ?>" <?if(strlen($os)==0){?> onblur   = "VerificaSuaOS(this); this.className='frm'; displayText('&nbsp;');"<?}else{}?> > </td>
			<?php } ?>
			<td nowrap class='Label'>Data Abertura</td>
			<td><input name="data_abertura" size="12" maxlength="10" value="<? echo $data_abertura ?>" type="text" class="frm" tabindex="0"  ><font size='-3' COLOR='#000099'> Ex.: <?=date('d/m/Y');?></td>

		</tr>

		<tr>
	<? if($login_fabrica <> 15 ) { ?>
			
			<td nowrap class='Label'>Referência do Produto</td>
			<td><input class="frm" type="text" name="produto_referencia" id="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" onblur="fnc_pesquisa_produto2 (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'referencia')">&nbsp;<img src='imagens/btn_lupa_novo.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto2 (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'referencia')"></td>
			<td nowrap class='Label'>Descrição do Produto</td>
			<td><input class="frm" type="text" name="produto_descricao" size="30" value="<? echo $produto_descricao ?>" onblur="fnc_pesquisa_produto2(document.frm_os.produto_referencia,document.frm_os.produto_descricao,'descricao')"  >&nbsp;<img src='imagens/btn_lupa_novo.gif'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto2(document.frm_os.produto_referencia,document.frm_os.produto_descricao,'descricao')"></A>
			</td>

	<? } else { ?>	
			
			<td nowrap class='Label'>Descrição do Produto</td>
			<td><input class="frm" type="text" name="produto_descricao" size="30" value="<? echo $produto_descricao ?>" onblur="fnc_pesquisa_produto2(document.frm_os.produto_referencia,document.frm_os.produto_descricao,'descricao')"  >&nbsp;<img src='imagens/btn_lupa_novo.gif'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto2(document.frm_os.produto_referencia,document.frm_os.produto_descricao,'descricao')"></A>
			</td>
			<td nowrap class='Label'>Referência do Produto</td>
			<td><input class="frm" type="text" name="produto_referencia" id="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" onblur="fnc_pesquisa_produto2 (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'referencia')">&nbsp;<img src='imagens/btn_lupa_novo.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto2 (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'referencia')"></td>
	<? } ?>
		</tr>
		<tr>
			<td nowrap class='Label'>N. Série.</td>
			<td><input class="frm" type="text" name="produto_serie" size="15" maxlength="20" value="<? echo $produto_serie ?>" onblur='javascript:verificarNS(this);liberar_os_item(this.form);'>
			</td>
		</tr>
		</table>
		<!-- Informações da OS - FIM  -->

	</td>
</tr>
<tr><td><img height="2" width="16" src="imagens/spacer.gif"></td></tr>
<tr>
	<td valign="top" align="left">

		<!-- Informações do Consumidor  -->
		<input type="hidden" name="consumidor_cliente">
		<input type="hidden" name="consumidor_rg">

		<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='750' border='0'>
		<tr>
			<td class='Label'>Nome Consumidor:</td>
			<td><input class="frm" type="text" name="consumidor_nome" size="40" maxlength="50" value="<? echo $consumidor_nome ?>"></td>
			<td class='Label'>Telefone:</td>
			<td><input class="frm" type="text" name="consumidor_fone"   size="15" maxlength="50" value="<? echo $consumidor_fone ?>"></td>
			<td class='Label'>CEP:</td>
			<td><input class="frm" type="text" name="consumidor_cep"   size="10" maxlength="8" value="<? echo $consumidor_cep ?>"></td>
		</tr>
		<tr>
			<td class='Label'>Endereço:</td>
			<td><input class="frm" type="text" name="consumidor_endereco"   size="15" maxlength="50" value="<? echo $consumidor_endereco ?>"></td>
			<td class='Label'>Número:</td>
			<td><input class="frm" type="text" name="consumidor_numero"   size="4" maxlength="10" value="<? echo $consumidor_numero ?>"></td>
			<td class='Label'>Bairro:</td>
			<td><input class="frm" type="text" name="consumidor_bairro"   size="15" maxlength="40" value="<? echo $consumidor_estado ?>"></td>
		</tr>
		<tr>
			<td class='Label'>Complemento:</td>
			<td><input class="frm" type="text" name="consumidor_complemento"   size="15" maxlength="20" value="<? echo $consumidor_complemento ?>"></td>
			<td class='Label'>Cidade:</td>
			<td><input class="frm" type="text" name="consumidor_cidade"   size="15" maxlength="50" value="<? echo $consumidor_cidade ?>"></td>
			<td class='Label'>Estado:</td>
			<td><input class="frm" type="text" name="consumidor_estado"   size="2" maxlength="2" value="<? echo $consumidor_estado ?>"></td>
		</tr>

		</table>
		<!-- Informações do Consumidor - FIM -->
	</td>
</tr>
<tr><td><img height="2" width="16" src="imagens/spacer.gif"></td></tr>
<tr>
	<td valign="top" align="left">

		<!-- Informações da Revenda  -->
		<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='750' border='0'>
		<tr>
		<tr>
			<td class='Label'>Nome Revenda:</td>
			<td><input class="frm" type="text" name="revenda_nome" size="30" maxlength="50" value="<? echo $revenda_nome ?>" ></td>
			<td class='Label'>Nota Fiscal:</td>
			<td><input class="frm" type="text" name="nota_fiscal"  size="8"  maxlength="8"  value="<? echo $nota_fiscal ?>" ></td>
			<td class='Label'>Data Compra:</td>
			<td nowrap><input class="frm" type="text" name="data_nf"    size="12" maxlength="10" value="<? echo $data_nf ?>" tabindex="0" > <font size='-3' color='#000099'>Ex.: 25/10/2006</td>
		</tr>
		<? if($login_fabrica == 15) {?>
			<tr valign='top'>
				<td>					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Cartão Clube</font>
				</td>
				<td nowrap style='font-size: 10px'>
					<input  name ="cartao_clube" class ="frm" type ="text" size ="15" maxlength="15" value ="<? echo $cartao_clube ?>" onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o número do Cartão Clube, caso tenha.');"><br><i>Caso o consumidor <b>não</b> tenha, deixe em branco.</i>
				</td>
			</tr>
		<?}?>

		</table>
		<!-- Informações da Revenda  FIM -->

	</td>
</tr>
<tr><td><img height="2" width="16" src="imagens/spacer.gif"></td></tr>
<tr>
	<td valign="top" align="left">
<?if(strlen($os)>0){


	$sql = "SELECT tbl_os.produto,tbl_linha.linha,tbl_familia.familia 
		FROM tbl_os 
		JOIN tbl_produto USING(produto) 
		JOIN tbl_linha   ON tbl_linha.linha     = tbl_produto.linha
		JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia
		WHERE tbl_os.os = $os
		AND   tbl_os.fabrica = $login_fabrica";
	$res = pg_exec($con,$sql) ;
	echo "$sql;";
	$produto = pg_result($res,0,produto);
	$familia = pg_result($res,0,familia);
	$linha   = pg_result($res,0,linha);
}

?>
	<input type='hidden' name='produto' id='produto' value='<?=$produto?>'>
	<input type='hidden' name='linha'   id='linha'   value='<?=$linha?>'>
	<input type='hidden' name='familia' id='familia' value='<?=$familia?>'>

<?

//--==== Defeito Reclamado ===============================================================================
	echo "<table style=' border: #D3BE96 1px solid; background-color: #FCF0D8 ' align='center' width='750' border='0'>";
	echo "<tr>";
	echo "<td class='Titulo' align='left' colspan='2'>Análise de Produto: <div id='dados' style='display:inline;'><i><u> Não informado</i></u></div>";
	echo "</td>";
	echo "</tr>";
if($login_fabrica <> 15) {
	
	echo "<tr>";
	echo "<td class='Label' align='left' >Defeito Reclamado:</td>";
	echo "<td><select name='defeito_reclamado'  class='frm' style='width:220px;' onfocus='listaDefeitos(document.frm_os.produto_referencia.value);' >";
	echo "<option id='opcoes' value=''></option>";
	echo "</select>";
	
} else {
	
	echo "<tr>";
	echo "<td class='Label' align='left' >Defeito Reclamado:</td>";
	echo "<td><input type='text' name='defeito_reclamado_descricao' class='frm' value='$defeito_reclamado_descricao' size='40'>";
	
}
	echo "</td>";
	echo "</tr>";


//--==== Defeito Constatado ==============================================================================
if ($pedir_defeito_constatado_os_item != "f") {
	echo "<tr>";
	echo "<td class='Label' align='left'>";
  echo "Defeito Constatado:";
  echo "<a href=\"javascript:Integridade(document.frm_os.linha.value,document.frm_os.familia.value,document.frm_os.defeito_reclamado.value);\"><img src='imagens/mais.gif' id='img_inte'></a>";
  echo"<div id='integrigade' style='position: absolute;visibility:hidden; opacity:.90;filter: Alpha(Opacity=90);width:401px; border: #555555 1px solid; background-color: #EFEFEF'></div>";
  echo "</td>";
	echo "<td>";
	if($login_fabrica <> 15){
		echo "<select name='defeito_constatado'  class='frm' style='width: 220px;' onfocus='listaConstatado(document.frm_os.linha.value, document.frm_os.familia.value,document.frm_os.defeito_reclamado.value);' >";
	}else{
		echo "<select name='defeito_constatado'  class='frm' style='width: 220px;' onfocus='listaConstatado(document.frm_os.linha.value, document.frm_os.familia.value);' >";
	}
	echo "<option id='opcoes2' value=''></option>";
	echo "</select>";
	echo "</td>";
	echo "</tr>";
}

if ($pedir_solucao_os_item <> 'f') {
	echo "<tr>";
	echo "<td class='Label'align='left' >";

  echo "Solução:</td>";
	echo "<td>";
	if($login_fabrica <> 15){
		echo "<select name='solucao_os' class='frm'  style='width:200px;' onfocus='listaSolucao(document.frm_os.defeito_constatado.value, document.frm_os.linha.value, document.frm_os.defeito_reclamado.value,  document.frm_os.familia.value);' >";
	}else{
		echo "<select name='solucao_os' class='frm'  style='width:200px;' onfocus='listaSolucao(document.frm_os.defeito_constatado.value, document.frm_os.linha.value, 0, document.frm_os.familia.value);' >";
	}
	echo "<option id='opcoes' value=''></option>";
	echo "</select>";
	echo "</td>";
	echo "</tr>";

}
echo "</table>";

?>

	</td>
</tr>
<tr><td><img height="0" width="16" src="imagens/spacer.gif"></td></tr>
<tr>
	<td valign="top" align="left">

<?



if(strlen($os)==0){
	echo " <table width='750'align='center' border='0'cel>";
	echo "<tr>";
	echo "<td align='left'>";
	echo "<div id='esconde'  style='position: absolute;visibility:visible; opacity:.90;filter: Alpha(Opacity=90);height: 300px; width: 750px; border: #555555 1px solid; background-color: #EFEFEF'> Lançamento Bloqueado </div>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
}
if (strlen($os) > 0) {
	$sql = "SELECT  tbl_os.*                                      ,
			tbl_produto.referencia                        ,
			tbl_produto.descricao                         ,
			tbl_produto.voltagem                          ,
			tbl_produto.linha                             ,
			tbl_produto.familia                           ,
			tbl_os_extra.os_reincidente AS reincidente_os ,
			tbl_posto_fabrica.codigo_posto                ,
			tbl_posto_fabrica.reembolso_peca_estoque      
		FROM    tbl_os
		JOIN    tbl_os_extra USING (os)
		JOIN    tbl_produto  USING (produto)
		JOIN    tbl_posto         USING (posto)
		JOIN    tbl_posto_fabrica ON  tbl_posto.posto           = tbl_posto_fabrica.posto
			  AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE   tbl_os.os = $os";
	$res = @pg_exec ($con,$sql) ;

	if (@pg_numrows($res) > 0) {
		$login_posto                 = pg_result($res,0,posto);
		$linha                       = pg_result($res,0,linha);
		$familia                     = pg_result($res,0,familia);
		$consumidor_nome             = pg_result($res,0,consumidor_nome);
		$sua_os                      = pg_result($res,0,sua_os);
		$produto_os                  = pg_result($res,0,produto);
		$produto_referencia          = pg_result($res,0,referencia);
		$produto_descricao           = pg_result($res,0,descricao);
		$produto_voltagem            = pg_result($res,0,voltagem);
		$produto_serie               = pg_result($res,0,serie);
		$qtde_produtos               = pg_result($res,0,qtde_produtos);
		$produto_type                = pg_result($res,0,type);
		$defeito_reclamado           = pg_result($res,0,defeito_reclamado);
		$defeito_constatado          = pg_result($res,0,defeito_constatado);
		$causa_defeito               = pg_result($res,0,causa_defeito);
		$posto                       = pg_result($res,0,posto);
		$obs                         = pg_result($res,0,obs);
		$os_reincidente              = pg_result($res,0,reincidente_os);
		$codigo_posto                = pg_result($res,0,codigo_posto);
		$reembolso_peca_estoque      = pg_result($res,0,reembolso_peca_estoque);
		$consumidor_revenda          = pg_result($res,0,consumidor_revenda);
		$troca_faturada              = pg_result($res,0,troca_faturada);
		$motivo_troca                = pg_result($res,0,motivo_troca);
		$defeito_reclamado_descricao = pg_result ($res,0,defeito_reclamado_descricao);

	}
}



#---------------- Carrega campos de configuração da Fabrica -------------
$sql = "SELECT  tbl_fabrica.os_item_subconjunto   ,
				tbl_fabrica.pergunta_qtde_os_item ,
				tbl_fabrica.os_item_serie         ,
				tbl_fabrica.os_item_aparencia     ,
				tbl_fabrica.qtde_item_os
		FROM    tbl_fabrica
		WHERE   tbl_fabrica.fabrica = $login_fabrica;";
$resX = pg_exec ($con,$sql);

if (pg_numrows($resX) > 0) {
	$os_item_subconjunto = pg_result($resX,0,os_item_subconjunto);
	if (strlen ($os_item_subconjunto) == 0) $os_item_subconjunto = 't';
	
	$pergunta_qtde_os_item = pg_result($resX,0,pergunta_qtde_os_item);
	if (strlen ($pergunta_qtde_os_item) == 0) $pergunta_qtde_os_item = 'f';
	
	$os_item_serie = pg_result($resX,0,os_item_serie);
	if (strlen ($os_item_serie) == 0) $os_item_serie = 'f';
	
	$os_item_aparencia = pg_result($resX,0,os_item_aparencia);
	if (strlen ($os_item_aparencia) == 0) $os_item_aparencia = 'f';
	
	$qtde_item = pg_result($resX,0,qtde_item_os);
	if (strlen ($qtde_item) == 0) $qtde_item = 5;
}



if(strlen($os) > 0 AND strlen ($msg_erro) == 0){
	if ($os_item_aparencia == 't' AND $posto_item_aparencia == 't' and $os_item_subconjunto == 'f') {
		$sql = "SELECT  tbl_peca.peca
			FROM    tbl_peca
			JOIN    tbl_lista_basica USING (peca)
			JOIN    tbl_produto      USING (produto)
			WHERE   tbl_produto.produto     = $produto_os
			AND     tbl_peca.fabrica        = $login_fabrica
			AND     tbl_peca.item_aparencia = 't'
			ORDER BY tbl_peca.referencia;";
		$resX = @pg_exec($con,$sql);
		$inicio_itens = @pg_numrows($resX);
	}else   $inicio_itens = 0;

	$sql = "SELECT  tbl_os_item.pedido                                                 ,
			tbl_os_item.qtde                                                   ,
			tbl_os_item.liberacao_pedido                                       ,
			tbl_os_item.obs                                                    ,
			tbl_os_item.posicao                                                ,
			tbl_os_item.causa_defeito                                          ,
			tbl_os_item.admin                       AS admin_peca              ,
			tbl_peca.referencia                                                ,
			tbl_peca.descricao                                                 ,
			tbl_defeito.defeito                                                ,
			tbl_defeito.descricao                   AS defeito_descricao       ,
			tbl_produto.referencia                  AS subconjunto             ,
			tbl_os_produto.produto                                             ,
			tbl_os_produto.serie                                               ,
			tbl_servico_realizado.servico_realizado                            ,
			tbl_servico_realizado.descricao         AS servico_descricao       ,
			tbl_causa_defeito.descricao             AS causa_defeito_descricao
		FROM    tbl_os_item
		JOIN    tbl_os_produto             USING (os_produto)
		JOIN    tbl_produto                USING (produto)
		JOIN    tbl_os                     USING (os)
		JOIN    tbl_peca                   USING (peca)
		LEFT JOIN tbl_defeito              USING (defeito)
		LEFT JOIN tbl_servico_realizado    USING (servico_realizado)
		LEFT JOIN tbl_causa_defeito ON tbl_os_item.causa_defeito = tbl_causa_defeito.causa_defeito
		WHERE   tbl_os.os      = $os
		AND     tbl_os.fabrica = $login_fabrica
		AND     tbl_os_item.pedido                     IS NULL
		AND     tbl_os_item.liberacao_pedido_analisado IS FALSE
		ORDER BY tbl_os_item.os_item;";
	$res = pg_exec ($con,$sql) ;
	
	if (pg_numrows($res) > 0) {
		$fim_itens = $inicio_itens + pg_numrows($res);
		$i = 0;
		for ($k = $inicio_itens ; $k < $fim_itens ; $k++) {
			$pedido[$k]                  = pg_result($res,$i,pedido);
			$peca[$k]                    = pg_result($res,$i,referencia);
			$qtde[$k]                    = pg_result($res,$i,qtde);
			$posicao[$k]                 = pg_result($res,$i,posicao);
			$produto[$k]                 = pg_result($res,$i,subconjunto);
			$serie[$k]                   = pg_result($res,$i,serie);
			$descricao[$k]               = pg_result($res,$i,descricao);
			$defeito[$k]                 = pg_result($res,$i,defeito);
			$defeito_descricao[$k]       = pg_result($res,$i,defeito_descricao);
			$pcausa_defeito[$k]          = pg_result($res,$i,causa_defeito);
			$causa_defeito_descricao[$k] = pg_result($res,$i,causa_defeito_descricao);
			$servico[$k]                 = pg_result($res,$i,servico_realizado);
			$servico_descricao[$k]       = pg_result($res,$i,servico_descricao);
			$admin_peca[$k]              = pg_result($res,$i,admin_peca);//aqui
			if(strlen($admin_peca[$k])==0) $admin_peca[$k]="P";
			$i++;
		}
	}else{
		for ($i = 0 ; $i < $qtde_item ; $i++) {
			$produto[$i]        = $_POST["produto_"        . $i];
			$serie[$i]          = $_POST["serie_"          . $i];
			$posicao[$i]        = $_POST["posicao_"        . $i];
			$peca[$i]           = $_POST["peca_"           . $i];
			$qtde[$i]           = $_POST["qtde_"           . $i];
			$defeito[$i]        = $_POST["defeito_"        . $i];
			$pcausa_defeito[$i] = $_POST["pcausa_defeito_" . $i];
			$servico[$i]        = $_POST["servico_"        . $i];
			$admin_peca[$i]     = $_POST["admin_peca_"     . $i];
			
			if (strlen($peca[$i]) > 0) {
				$sql = "SELECT  tbl_peca.referencia,
							tbl_peca.descricao
					FROM    tbl_peca
					WHERE   tbl_peca.fabrica    = $login_fabrica
					AND     tbl_peca.referencia = $peca[$i];";
				$resX = @pg_exec ($con,$sql) ;
				
				if (@pg_numrows($resX) > 0) $descricao[$i] = trim(pg_result($resX,0,descricao));
			}
		}
	}
}else{//ok
	for ($i = 0 ; $i < $qtde_item ; $i++) {
		$produto[$i]        = $_POST["produto_"        . $i];
		$serie[$i]          = $_POST["serie_"          . $i];
		$posicao[$i]        = $_POST["posicao_"        . $i];
		$peca[$i]           = $_POST["peca_"           . $i];
		$qtde[$i]           = $_POST["qtde_"           . $i];
		$defeito[$i]        = $_POST["defeito_"        . $i];
		$pcausa_defeito[$i] = $_POST["pcausa_defeito_" . $i];
		$servico[$i]        = $_POST["servico_"        . $i];
		$admin_peca[$i]     = $_POST["admin_peca_"     . $i];//aqui
		if (strlen($peca[$i]) > 0) {
			$sql = "SELECT  tbl_peca.referencia,
						tbl_peca.descricao
				FROM    tbl_peca
				WHERE   tbl_peca.fabrica    = $login_fabrica
				AND     tbl_peca.referencia = '$peca[$i]';";
			$resX = @pg_exec ($con,$sql) ;
			
			if (@pg_numrows($resX) > 0) $descricao[$i] = trim(pg_result($resX,0,descricao));
		}
	}
}





//--===== Lançamento das Peças da OS ====================================================================
echo "<input type='hidden' name='qtde_item' value='$qtde_item'>";
echo "<table style=' border:#76D176 1px solid; background-color: #EFFAEF' align='center' width='750' border='0'>";
echo "<tr height='20' bgcolor='#76D176'>";
echo "<td align='center' class='Titulo'><b>Código</b>&nbsp;&nbsp;&nbsp;</td>";
echo "<td align='center' class='Titulo'><b>Descrição</b></td>";
// lista basica - HD 6673 29/10/2007
echo "<td align='center' class='Titulo'><div id='lista_basica' style='display:inline;'></div></td>";
echo "<td align='center' class='Titulo'><b>Defeito</b></td>";
echo "<td align='center' class='Titulo'><b>Serviço</b></td>";
echo "</tr>";

echo "<input type='hidden' name='descricao'>";
echo "<input type='hidden' name='preco'>";
echo "<input type='hidden' name='voltagem'>";

$loop = 10;

$offset = 0;
for ($i = 0 ; $i < $loop ; $i++) {
	$xproduto = $produto[$i];

	echo "<tr>";
	
	echo "<input type='hidden' name='admin_peca_$i' value='$admin_peca[$i]'>";
	echo "<input type='hidden' name='produto_$i' value='$produto_referencia'>";
	echo "<input type='hidden' name='serie_$i'>";

	echo "<td align='center'><input class='frm' type='text' name='peca_$i' size='15' value='$peca[$i]'>&nbsp;";
	echo "<img src='imagens/btn_lupa_novo.gif' border='0' align='absmiddle' onclick='javascript:fnc_pesquisa_peca_lista (document.frm_os.produto_referencia.value , document.frm_os.peca_$i, document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"referencia\" )' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'>";
	echo "</td>";
	
	echo "<td align='center'><input class='frm' type='text' name='descricao_$i' size='25' value='$descricao[$i]'>&nbsp;";
	echo "<img src='imagens/btn_lupa_novo.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_referencia.value , document.frm_os.peca_$i, document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"descricao\" )' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'>";
	echo "</td>";

	// lista basica - HD 6673 29/10/2007
	echo "<td width='60' align='center'><img src='imagens/btn_lista.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_lista_basica (document.frm_os.produto_referencia.value , document.frm_os.peca_$i, document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"referencia\" )' alt='LISTA BÁSICA' style='cursor:pointer;'></TD>";         

	//--===== Defeito do Item ========================================================================
	echo "<td align='center'>";
	echo "<select class='frm' size='1' name='defeito_$i'>";
	echo "<option selected></option>";

	$sql = "SELECT *
			FROM   tbl_defeito
			WHERE  tbl_defeito.fabrica = $login_fabrica
			AND    tbl_defeito.ativo IS TRUE
			ORDER BY descricao;";
	$res = pg_exec ($con,$sql) ;
	
	for ($x = 0 ; $x < pg_numrows ($res) ; $x++ ) {
		echo "<option ";
		if ($defeito[$i] == pg_result ($res,$x,defeito)) echo " selected ";
		echo " value='" . pg_result ($res,$x,defeito) . "'>" ;
		if (strlen(trim(pg_result($res,$x,codigo_defeito))) > 0) {
			echo pg_result($res,$x,codigo_defeito);
			echo " - " ;
		}
		echo pg_result($res,$x,descricao);
		echo "</option>";
	}
	
	echo "</select>";
	echo "</td>";
	//--===== FIM - Defeito da Peça ===================================================================


	//--===== Serviço Realizado =======================================================================
	echo "<td align='center'>";
	echo "<select class='frm' size='1' name='servico_$i' style='width:150px'>";
	echo "<option selected></option>";

	$sql = "SELECT *
		FROM   tbl_servico_realizado
		WHERE  tbl_servico_realizado.fabrica = $login_fabrica 
		AND tbl_servico_realizado.linha IS NULL
		AND tbl_servico_realizado.ativo   IS TRUE 
		ORDER BY gera_pedido DESC, descricao ASC;";

	$res = pg_exec($con,$sql) ;
	
	if (pg_numrows($res) == 0) {
		$sql = "SELECT *
			FROM   tbl_servico_realizado
			WHERE  tbl_servico_realizado.fabrica = $login_fabrica 
			AND tbl_servico_realizado.linha IS NULL

			AND tbl_servico_realizado.ativo IS TRUE
			ORDER BY gera_pedido DESC, descricao ASC;";
		$res = pg_exec($con,$sql) ;
	}

	for ($x = 0 ; $x < pg_numrows($res) ; $x++ ) {
		echo "<option ";
		if ($servico[$i] == pg_result ($res,$x,servico_realizado)) echo " selected ";
		echo " value='" . pg_result ($res,$x,servico_realizado) . "'>" ;
		echo pg_result ($res,$x,descricao) ;
		if (pg_result ($res,$x,gera_pedido) == 't' AND $login_fabrica == 6) echo " - GERA PEDIDO DE PEÇA ";
		echo "</option>";
	}
	
	echo "</select>";
	echo "</td>";
	//--===== FIM - Serviço Realizado ===================================================================

	echo "</tr>";
	
	$offset = $offset + 1;
}
echo "</table>";
//--===== FIM - Lançamento de Peças =====================================================================

?>
	</td>
</tr>
<tr><td><img height="2" width="16" src="imagens/spacer.gif"></td></tr>
<tr>
	<td valign="top" align="left">
<?

//--===== Data Fechamento da OS =========================================================================
echo "<table style=' border:#B63434 1px solid; background-color: #cfc0c0' align='center' width='750' border='0'height='40'>";
echo "<tr>";
echo "<td valign='middle' align='LEFT' class='Label' nowrap><INPUT TYPE='checkbox' NAME='admin_paga_mao_de_obra' value='t'>Pagar mão-de-obra</td>";
echo "<td valign='middle' align='RIGHT' class='Label'>Data Fechamento:</td>";
echo "<td valign='middle' align='LEFT' class='Label' >";
echo "<INPUT TYPE='text' NAME='data_fechamento' value='$data_fechamento' size='12' maxlength='10' class='frm'> dd/mm/aaaa</td>";
echo "<td valign='middle' align='LEFT' nowrap><input type='button' name='btn_acao' value='Gravar' onClick=\" gravar_os(this.form); \"></td>";
echo "<td width='250'><div id='saida' style='display:inline;'></div></td>";
echo "</tr>";
echo "</table>";
//--=====================================================================================================

?>
		

	</td>

</tr>
</table>
</form>
<div id='erro' style='visibility:hidden;opacity:.85;' class='Erro'></div>

<p>
<p>
</table></table>
<? include "rodape.php";?>
