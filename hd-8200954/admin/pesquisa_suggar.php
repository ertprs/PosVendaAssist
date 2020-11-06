<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";
include "funcoes.php";
/*if($ip<>"201.27.30.119" ){
echo "programa em manunteção";
exit;
}*/
$msg = "";

// pega os valores das variaveis dadas como parametros de pesquisa e coloca em um cookie
setcookie("cookredirect", $_SERVER["REQUEST_URI"]); // expira qdo fecha o browser

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");


$layout_menu = "callcenter";
$title       = "PESQUISA DE SATISFAÇÃO";

if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);
if (strlen($_GET["acao"]) > 0)  $acao = strtoupper($_GET["acao"]);

if ($acao == "PESQUISAR") {
	if (strlen(trim($_GET["opcao1"])) > 0)  $opcao1 = trim($_GET["opcao1"]);
	if (strlen(trim($_GET["opcao2"])) > 0)  $opcao2 = trim($_GET["opcao2"]);
	if (strlen(trim($_GET["opcao4"])) > 0)  $opcao4 = trim($_GET["opcao4"]);
	if (strlen(trim($_GET["opcao5"])) > 0)  $opcao5 = trim($_GET["opcao5"]);
	if (strlen(trim($_GET["opcao6"])) > 0)  $opcao6 = trim($_GET["opcao6"]);

	if (strlen($opcao1) == 0 && strlen($opcao2) == 0 && strlen($opcao4) == 0 && strlen($opcao5) == 0 && strlen($opcao6) == 0) {
		$msg = " Selecione pelo menos uma opção para realizar a pesquisa. ";
	}

	if (strlen($erro) == 0 && strlen($opcao1) > 0) {
		if (strlen($_GET["mes"]) > 0)  $mes = $_GET["mes"];
		if (strlen($_GET["ano"]) > 0)  $ano = $_GET["ano"];

		if (strlen($mes) == 0) $msg .= " Selecione o mês para realizar a pesquisa. ";
		if (strlen($ano) == 0) $msg .= " Selecione o ano para realizar a pesquisa. ";
		if(strlen($opcao2)==0 AND strlen($opcao4)==0 and strlen($opcao6)==0)  $msg .= " Informe mais parametros para pesquisa. ";

	}else{
		$mes = "";
		$ano = "";
	}

	if (strlen($opcao2) > 0) {

		if (strlen($mes) == 0) $msg .= " Selecione o mês para realizar a pesquisa. ";
		if (strlen($ano) == 0) $msg .= " Selecione o ano para realizar a pesquisa. ";

		if (strlen($_GET["posto_codigo"]) > 0) $posto_codigo = "'".trim($_GET["posto_codigo"])."'";
		if (strlen($_GET["posto_nome"]) > 0)   $posto_nome = trim($_GET["posto_nome"]);

		if (strlen($posto_codigo) > 0) {
			$sql =	"SELECT tbl_posto_fabrica.posto        ,
							tbl_posto_fabrica.codigo_posto ,
							tbl_posto.nome
					FROM tbl_posto
					JOIN tbl_posto_fabrica USING (posto)
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica
					AND   tbl_posto_fabrica.codigo_posto = $posto_codigo;";
			$res = pg_exec($con,$sql);
			if (pg_numrows($res) == 1) {
				$posto        = pg_result($res,0,posto);
				$posto_codigo = pg_result($res,0,codigo_posto);
				$posto_nome   = pg_result($res,0,nome);
			}else{
				$msg .= " Posto não encontrado. ";
			}
		}else{
			$msg .= "Para efetuar a pesquisa, selecione um posto"; 
		}
	}else{
		$posto        = "";
		$posto_codigo = "";
		$posto_nome   = "";
	}

	
	if (strlen($opcao4) > 0) {

		if (strlen($mes) == 0) $msg .= " Selecione o mês para realizar a pesquisa. ";
		if (strlen($ano) == 0) $msg .= " Selecione o ano para realizar a pesquisa. ";
		
		if (strlen($_GET["produto_referencia"]) > 0)  $produto_referencia = trim($_GET["produto_referencia"]);
		if (strlen($_GET["produto_descricao"]) > 0)   $produto_descricao  = trim($_GET["produto_descricao"]);
		if (strlen($_GET["produto_voltagem"]) > 0)    $produto_voltagem   = trim($_GET["produto_voltagem"]);

		if (strlen($produto_referencia) > 0 && strlen($produto_descricao) > 0) {
			$sql =	"SELECT tbl_produto.produto    ,
							tbl_produto.referencia ,
							tbl_produto.descricao  ,
							tbl_produto.voltagem
					FROM tbl_produto
					JOIN tbl_linha USING (linha)
					WHERE tbl_linha.fabrica    = $login_fabrica
					AND   tbl_produto.referencia = '$produto_referencia'";
			$res = pg_exec($con,$sql);
			if (pg_numrows($res) == 1) {
				$produto            = pg_result($res,0,produto);
				$produto_referencia = pg_result($res,0,referencia);
				$produto_descricao  = pg_result($res,0,descricao);
				$produto_voltagem   = pg_result($res,0,voltagem);
			}else{
				$msg .= " Produto não encontrado. ";
			}
		}else{
			$msg .= "Para efetuar a pesquisa, selecione um produto"; 
		}
	}else{
		$produto = "";
		$produto_referencia = "";
		$produto_descricao = "";
		$produto_voltagem = "";
	}

	if (strlen($opcao5) > 0) {
		if (strlen($_GET["numero_os"]) > 0)  
		    $numero_os = trim($_GET["numero_os"]);

		if (strlen($numero_os) > 0 && strlen($numero_os) < 3) 
		    $msg .= " Digite o número de série com o mínimo de 3 números. ";
	}else{
		$numero_os = "";
	}

	if (strlen($opcao6) > 0) {
		if (strlen($_GET["mes"]) == 0)  $mes = $_GET["mes"];
		if (strlen($_GET["ano"]) == 0)  $ano = $_GET["ano"];

		if (strlen($mes) == 0) $msg .= " Selecione o mês para realizar a pesquisa. ";
		if (strlen($ano) == 0) $msg .= " Selecione o ano para realizar a pesquisa. ";
	}


}



include "cabecalho.php";
?>

<script language="JavaScript">


function fnc_pesquisa_revenda (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cnpj") {
		url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj";
	}
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
	janela.nome			= document.frm_pesquisa.revenda_nome;
	janela.cnpj			= document.frm_pesquisa.revenda_cnpj;
	janela.fone			= document.frm_pesquisa.revenda_fone;
	janela.cidade		= document.frm_pesquisa.revenda_cidade;
	janela.estado		= document.frm_pesquisa.revenda_estado;
	janela.endereco		= document.frm_pesquisa.revenda_endereco;
	janela.numero		= document.frm_pesquisa.revenda_numero;
	janela.complemento	= document.frm_pesquisa.revenda_complemento;
	janela.bairro		= document.frm_pesquisa.revenda_bairro;
	janela.cep			= document.frm_pesquisa.revenda_cep;
	janela.email		= document.frm_pesquisa.revenda_email;
	janela.focus();
}
function marcaLinha(id){
	var elemento = document.getElementById(id);
	elemento.setAttribute('bgColor', '#c8c6c6');
}
function verificaSatisfeito(tipo){
		var a  = document.getElementById('satisfeito_usar'); 
		var b  = document.getElementById('satisfeito_manual'); 
		var c  = document.getElementById('satisfeito_energia'); 
		var d  = document.getElementById('satisfeito_barulho'); 
		var e  = document.getElementById('satisfeito_cor'); 
		var f  = document.getElementById('satisfeito_usar_1'); 
		var g  = document.getElementById('satisfeito_manual_1'); 
		var h  = document.getElementById('satisfeito_energia_1'); 
		var i  = document.getElementById('satisfeito_barulho_1'); 
		var j  = document.getElementById('satisfeito_cor_1'); 

		var l  = document.getElementById('insatisfeito_usar'); 
		var m  = document.getElementById('insatisfeito_manual'); 
		var n  = document.getElementById('insatisfeito_energia'); 
		var o  = document.getElementById('insatisfeito_barulho'); 
		var p  = document.getElementById('insatisfeito_cor'); 
		var q  = document.getElementById('insatisfeito_usar_1'); 
		var r  = document.getElementById('insatisfeito_manual_1'); 
		var s  = document.getElementById('insatisfeito_energia_1'); 
		var t  = document.getElementById('insatisfeito_barulho_1'); 
		var u  = document.getElementById('insatisfeito_cor_1'); 
		var v  = document.getElementById('insatisfeito_quebra_uso'); 	
		var x  = document.getElementById('insatisfeito_quebra_uso_1'); 

	
	if(tipo=="sim"){
	
		a.disabled = false;
		b.disabled = false;
		c.disabled = false;
		d.disabled = false;
		e.disabled = false;
		
		f.disabled = false;
		g.disabled = false;
		h.disabled = false;
		i.disabled = false;
		j.disabled = false;
	
	
		l.disabled = true;
		m.disabled = true;
		n.disabled = true;
		o.disabled = true;
		p.disabled = true;
		
		q.disabled = true;
		r.disabled = true;
		s.disabled = true;
		t.disabled = true;
		u.disabled = true;
		v.disabled = true;
		x.disabled = true;
		
		
		l.checked = false;
		m.checked = false;
		n.checked = false;
		o.checked = false;
		p.checked = false;

		q.checked = false;
		r.checked = false;
		s.checked = false;
		t.checked = false;
		u.checked = false;	
		v.checked = false;	
		x.checked = false;	

	}

	if(tipo=="nao"){

		a.disabled = true;
		b.disabled = true;
		c.disabled = true;
		d.disabled = true;
		e.disabled = true;
		
		f.disabled = true;
		g.disabled = true;
		h.disabled = true;
		i.disabled = true;
		j.disabled = true;
	
		a.checked = false;
		b.checked = false;
		c.checked = false;
		d.checked = false;
		e.checked = false;

		f.checked = false;
		g.checked = false;
		h.checked = false;
		i.checked = false;
		j.checked = false;
	
		l.disabled = false;
		m.disabled = false;
		n.disabled = false;
		o.disabled = false;
		p.disabled = false;
		
		q.disabled = false;
		r.disabled = false;
		s.disabled = false;
		t.disabled = false;
		u.disabled = false;
		v.disabled = false;
		x.disabled = false;
	}

}
function validaCampos(){
	var count = 0;
	var total = 0;

	if(document.frm_pesquisa_satisfacao.preco[0].checked      == true || document.frm_pesquisa_satisfacao.preco[1].checked      == true){count++;}
	if(document.frm_pesquisa_satisfacao.qualidade[0].checked  == true || document.frm_pesquisa_satisfacao.qualidade[1].checked  == true){count++;}
	if(document.frm_pesquisa_satisfacao.design[0].checked     == true || document.frm_pesquisa_satisfacao.design[1].checked     == true){count++;}
	if(document.frm_pesquisa_satisfacao.tradicao[0].checked   == true || document.frm_pesquisa_satisfacao.tradicao[1].checked   == true){count++;}
	if(document.frm_pesquisa_satisfacao.indicacao[0].checked  == true || document.frm_pesquisa_satisfacao.indicacao[1].checked  == true){count++;}
	if(document.frm_pesquisa_satisfacao.capacidade[0].checked == true || document.frm_pesquisa_satisfacao.capacidade[1].checked == true){count++;}
	if(document.frm_pesquisa_satisfacao.inovacao[0].checked   == true || document.frm_pesquisa_satisfacao.inovacao[1].checked   == true){count++;}

	if(document.frm_pesquisa_satisfacao.satisfeito[0].checked == true || document.frm_pesquisa_satisfacao.satisfeito[1].checked == true){count++;}

	if(document.frm_pesquisa_satisfacao.satisfeito[0].checked == true){
		total = 18;
		if(document.frm_pesquisa_satisfacao.satisfeito_usar[0].checked    == true || document.frm_pesquisa_satisfacao.satisfeito_usar[1].checked    == true){count++;}
		if(document.frm_pesquisa_satisfacao.satisfeito_manual[0].checked  == true || document.frm_pesquisa_satisfacao.satisfeito_manual[1].checked  == true){count++;}
		if(document.frm_pesquisa_satisfacao.satisfeito_energia[0].checked == true || document.frm_pesquisa_satisfacao.satisfeito_energia[1].checked == true){count++;}
		if(document.frm_pesquisa_satisfacao.satisfeito_barulho[0].checked == true || document.frm_pesquisa_satisfacao.satisfeito_barulho[1].checked == true){count++;}
		if(document.frm_pesquisa_satisfacao.satisfeito_cor[0].checked     == true || document.frm_pesquisa_satisfacao.satisfeito_cor[1].checked     == true){count++;}
	}else{
	total = 19;
		if(document.frm_pesquisa_satisfacao.insatisfeito_usar[0].checked       == true || document.frm_pesquisa_satisfacao.insatisfeito_usar[1].checked       == true){count++;}
		if(document.frm_pesquisa_satisfacao.insatisfeito_manual[0].checked     == true || document.frm_pesquisa_satisfacao.insatisfeito_manual[1].checked     == true){count++;}
		if(document.frm_pesquisa_satisfacao.insatisfeito_energia[0].checked    == true || document.frm_pesquisa_satisfacao.insatisfeito_energia[1].checked    == true){count++;}
		if(document.frm_pesquisa_satisfacao.insatisfeito_barulho[0].checked    == true || document.frm_pesquisa_satisfacao.insatisfeito_barulho[1].checked    == true){count++;}
		if(document.frm_pesquisa_satisfacao.insatisfeito_cor[0].checked        == true || document.frm_pesquisa_satisfacao.insatisfeito_cor[1].checked        == true){count++;}
		if(document.frm_pesquisa_satisfacao.insatisfeito_quebra_uso[0].checked == true || document.frm_pesquisa_satisfacao.insatisfeito_quebra_uso[1].checked == true){count++;}
	}

	if(document.frm_pesquisa_satisfacao.atendimento_rapido[0].checked == true || document.frm_pesquisa_satisfacao.atendimento_rapido[1].checked == true){count++;}
	if(document.frm_pesquisa_satisfacao.confianca[0].checked          == true || document.frm_pesquisa_satisfacao.confianca[1].checked          == true){count++;}
	if(document.frm_pesquisa_satisfacao.problema_resolvido[0].checked == true || document.frm_pesquisa_satisfacao.problema_resolvido[1].checked == true){count++;}

	if(document.frm_pesquisa_satisfacao.nota.value.length > 0) {count++;}
	if(document.frm_pesquisa_satisfacao.nota_produto.value.length > 0) {count++;}

	if(count != total){ /*Não foram respondidas todas perguntas*/
		if (confirm('Das ' + total + ' perguntas, somente '+ count +' foram respondidas. Caso continue, as perguntas não respondidas serão consideradas como não, e poderão influenciar negativamente na pesquisa de satisfação. Deseja mesmo assim continuar?') == true) {
			document.frm_pesquisa_satisfacao.btn_gravar.value='continuar' ; 
			document.frm_pesquisa_satisfacao.submit();
		}
	}else{
		document.frm_pesquisa_satisfacao.btn_gravar.value='continuar' ; 
		document.frm_pesquisa_satisfacao.submit();
	}

}
</script>

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

<? include "javascript_pesquisas.php"; ?>

<br>

<? if (strlen($msg) > 0) { ?>
<table width="700" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr>
		<td class="msg_erro"><?echo $msg?></td>
	</tr>
</table>
<? } ?>

<form name="frm_pesquisa" method="get" action="<?echo $PHP_SELF?>">

<input type="hidden" name="acao">

<table width="700" align="center" border="0" cellspacing="0" cellpadding="2" class='formulario'>
	<tr class="titulo_tabela">
		<td colspan="6">Pesquisa Satisfação</td>
	</tr>
	<tr><td colspan="6">&nbsp;</td></tr>
	<tr>
		<td width="10">&nbsp;</td>
		<td align="left"><input type="checkbox" name="opcao1" value="1" class="frm" <? if (strlen($opcao1) > 0) echo "checked"; ?>> Período </td>
		<td>Mês</td>
		<td colspan="2">Ano</td>
		<td width="10">&nbsp;</td>
	</tr>

	<tr>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>
			<select name="mes" size="1" class="frm">
			<option value=''></option>
			<?
			for ($i = 1 ; $i <= count($meses) ; $i++) {
				echo "<option value='$i'";
				if ($mes == $i) echo " selected";
				echo ">" . $meses[$i] . "</option>";
			}
			?>
			</select>
		</td>
		
		<td colspan="2">
			<select name="ano" size="1" class="frm">
			<option value=''></option>
			<?
			for ($i = 2003 ; $i <= date("Y") ; $i++) {
				echo "<option value='$i'";
				if ($ano == $i) echo " selected";
				echo ">$i</option>";
			}
			?>
			</select>
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr><td colspan="6">&nbsp;</td></tr>
	<tr>
		<td>&nbsp;</td>
		<td align="left" colspan="2"><input type="checkbox" name="opcao6" value="6" class="frm" <? if (strlen($opcao6) > 0) echo "checked"; ?>> Período e agrupar por posto</td>
		<td colspan="2">
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td align="left"><input type="checkbox" name="opcao2" value="2" class="frm" <? if (strlen($opcao2) > 0) echo "checked"; ?>> Posto</td>
		<td>Código do Posto</td>
		<td colspan="2">Razão Social</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>
			<input type="text" name="posto_codigo" size="8" value="<?echo $posto_codigo?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.posto_codigo, document.frm_pesquisa.posto_nome, 'codigo')">
		</td>
		<td colspan="2">
			<input type="text" name="posto_nome" size="27" value="<?echo $posto_nome?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.posto_codigo, document.frm_pesquisa.posto_nome, 'nome')">
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr>
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td align="left"><input type="checkbox" name="opcao4" value="4" class="frm" <? if (strlen($opcao4) > 0) echo "checked"; ?>> Produto</td>
		<td>Referência</td>
		<td>Descrição</td>
		<td>Voltagem</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td><input type="text" name="produto_referencia" size="8" value="<?echo $produto_referencia?>" class="frm"> <img src="imagens/lupa.png" style="cursor:pointer;" align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia, document.frm_pesquisa.produto_descricao, 'referencia', document.frm_pesquisa.produto_voltagem)"></td>
		
		<td><input type="text" name="produto_descricao" size="27" value="<?echo $produto_descricao?>" class="frm"> <img src="imagens/lupa.png" style="cursor: pointer;" align='absmiddle' alt="Clique aqui para pesquisas pela referência do aparelho." onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia, document.frm_pesquisa.produto_descricao, 'descricao', document.frm_pesquisa.produto_voltagem)"></td>
		
		<td><input type='text' name='produto_voltagem' size='5' value="<?echo $produto_voltagem?>" class="frm"></td>
		<td>&nbsp;</td>
	</tr>
	
	<tr>
		<td colspan="6">&nbsp;</td>
	</tr>
	
	<tr>
		<td colspan="6"><hr color="#EEEEEE"></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td align="left"><input type="checkbox" name="opcao5" value="5" class="frm" <?php if (strlen($opcao5) > 0) echo "checked"; ?>> Número da OS</td>
		<td colspan="3" align='left'><input type="text" name="numero_os" size="15" value="<?echo $numero_os?>" class="frm"></td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td colspan="6" align="center"><input type='button' value='Pesquisar' onclick="document.frm_pesquisa.acao.value='PESQUISAR'; document.frm_pesquisa.submit();" style="cursor:pointer;" title="Preencha as opções e clique aqui para pesquisar"></td>
	</tr>
</table>

</form>

<br>

<?

if ($acao == "PESQUISAR" && strlen($msg) == 0 and strlen($os)==0 and strlen($opcao6)==0) {
	$cond_1 = " 1 = 1 ";
	$cond_2 = " 1 = 1 ";
	$cond_3 = " 1 = 1 ";
	$cond_4 = " 1 = 1 ";

	if (strlen($mes) > 0 && strlen($ano) > 0) {
		$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
		$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
		$cond_1 = " tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final' ";
	}
	
	if (strlen($opcao2) > 0) {
		$cond_2 = " tbl_os.posto= $posto ";
	}
	
	if (strlen($opcao4) > 0) {
				
		$cond_3 = " tbl_os.produto = $produto ";
	}
	if (strlen($opcao5) > 0) {
		$cond_4 = " tbl_os.os = $numero_os ";
	}
	
	$sql = "SELECT 	tbl_os.os                                                          ,
					tbl_posto_fabrica.codigo_posto                                     ,
					tbl_posto.nome                                                     ,
					tbl_os.consumidor_nome                                             ,
					tbl_os.consumidor_cidade                                           ,
					tbl_os.consumidor_estado                                           ,
					tbl_os.consumidor_fone                                             ,
					tbl_os.revenda_nome                                                ,
					tbl_os.revenda_cnpj                                                ,
					to_char(tbl_os.data_abertura, 'DD/MM/YYYY') as data_abertura       ,
					to_char(tbl_os.data_fechamento, 'DD/MM/YYYY') as data_fechamento   ,
					tbl_produto.descricao as produto_descricao                         ,
					tbl_produto.referencia as produto_referencia                       
			FROM tbl_os
			JOIN tbl_produto on tbl_produto.produto = tbl_os.produto
			Join tbl_posto   on tbl_posto.posto = tbl_os.posto
			join tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto 
			AND  tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_os.fabrica = $login_fabrica
			AND  tbl_os.excluida is false
			and $cond_1
			and $cond_2
			and $cond_3
			and $cond_4
			ORDER BY tbl_os.os";
	$res = pg_exec($con,$sql);	
	//echo $sql;
	if(pg_numrows($res)>0){
		echo "<table border='0' cellpadding='1' cellspacing='1' width='700' align='center' class='tabela'>";
		echo "<tr class='titulo_coluna'>";
		echo "<td>OS</td>";
		echo "<td>Abertura</td>";
		echo "<td>Fechamento</td>";
		echo "<td>Posto</td>";
		echo "<td>Produto</td>";
		echo "<td>Consumidor</td>";
		echo "<td>Telefone</td>";
		echo "<td>Ação</td>";
		echo "</tr>";
		
		for($x=0;pg_numrows($res)>$x;$x++){
			$os           = pg_result($res,$x,os);
			$codigo_posto = pg_result($res,$x,codigo_posto);
			$nome_posto   = pg_result($res,$x,nome);
			$consumidor_nome   = pg_result($res,$x,consumidor_nome   );
			$consumidor_cidade = pg_result($res,$x,consumidor_cidade );
			$consumidor_estado = pg_result($res,$x,consumidor_estado );
			$consumidor_fone   = pg_result($res,$x,consumidor_fone   );
			$data_abertura     = pg_result($res,$x,data_abertura     );
			$data_fechamento   = pg_result($res,$x,data_fechamento   );
			$produto_descricao = pg_result($res,$x,produto_descricao );

			$cor = ($x % 2) ? "#F7F5F0" : "#F1F4FA";

			echo "<tr bgcolor='$cor'>";
			echo "<td><a href='os_press.php?os=$os' target='_blank'>$os</a></td>";
			echo "<td align='center'>$data_abertura</td>";
			echo "<td align='center'>$data_fechamento</td>";
			echo "<td align='left'>$codigo_posto - $nome_posto</td>";
			echo "<td align='left'>$produto_descricao</td>";
			echo "<td align='left'>$consumidor_nome</td>";
			echo "<td align='left'>$consumidor_fone</td>";
			echo "<td align='center'><input type='button' style='cursor:pointer;font:11px Arial' value='Pesquisar' onclick=\" window.location='$PHP_SELF?os=$os' \"></td>";
			echo "</tr>";
		}
		echo "</table>";
	}else{
		echo "<center>Nenhum resultado encontrado</center>";
	}


}

if ($acao == "PESQUISAR" && strlen($msg) == 0 and strlen($os)==0 and strlen($opcao6)>0) {
	$cond_1 = " 1 = 1 ";
	$cond_2 = " 1 = 1 ";
	$cond_3 = " 1 = 1 ";
	$cond_4 = " 1 = 1 ";

	if (strlen($mes) > 0 && strlen($ano) > 0) {
		$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
		$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
		$cond_1 = " tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final' ";
	}
	
	if (strlen($opcao2) > 0) {
		$cond_2 = " tbl_os.posto= $posto ";
	}
	
	if (strlen($opcao4) > 0) {
				
		$cond_3 = " tbl_os.produto = $produto ";
	}

	
	$sql = "SELECT 	count(tbl_os.os) as qtde                                           ,
					tbl_posto_fabrica.codigo_posto                                     ,
					tbl_posto.nome                                                     ,
					tbl_posto.posto
			FROM tbl_os
			JOIN tbl_produto on tbl_produto.produto = tbl_os.produto
			Join tbl_posto   on tbl_posto.posto = tbl_os.posto
			join tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto 
			AND  tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_os.fabrica = $login_fabrica
			AND  tbl_os.excluida is false
			and $cond_1
			and $cond_2
			and $cond_3
			group by tbl_posto_fabrica.codigo_posto                                     ,
					tbl_posto.nome                                                     ,
					tbl_posto.posto
			ORDER by qtde desc";
	$res = pg_exec($con,$sql);	
	//echo $sql;
//exit;
	if(pg_numrows($res)>0){
		echo "<table border='0' cellpadding='2' cellspacing='1' width='700' bgcolor='#596D9B' align='center' class='tabela'>";
		echo "<tr>";
		echo "<td><font color='#FFFFFF'><B>Posto</B></FONT></td>";
		echo "<td><font color='#FFFFFF'><B>Qtde</B></FONT></td>";
		echo "</tr>";
		
		for($x=0;pg_numrows($res)>$x;$x++){
			$qtde         = pg_result($res,$x,qtde);
			$codigo_posto = pg_result($res,$x,codigo_posto);
			$nome_posto   = pg_result($res,$x,nome);

			$cor = ($x % 2) ? "#F7F5F0" : "#F1F4FA";

			echo "<tr bgcolor='$cor'>";
			echo "<td align='left'><a href='$PHP_SELF?acao=PESQUISAR&opcao2=2&posto_codigo=$codigo_posto&posto_nome=$nome_posto&opcao1=1&mes=$mes&ano=$ano&opcao4=$opcao4&produto_referencia=$produto_referencia&produto_descricao=$produto_descricao'>$codigo_posto - $nome_posto</a></td>";
			echo "<td align='center'>$qtde</td>";
			echo "</tr>";
		}
		echo "</table>";
	}else{
		echo "<center>Nenhum resultado encontrado</center>";
	}




}


$os = $_GET['os'];
if(strlen($os)>0){

	$xsql = "SELECT 	tbl_os.os                                                          ,
					tbl_posto_fabrica.codigo_posto                                     ,
					tbl_posto.nome                                                     ,
					tbl_posto.fone as fone_posto                                       ,
					tbl_os.consumidor_nome                                             ,
					tbl_os.consumidor_cidade                                           ,
					tbl_os.consumidor_estado                                           ,
					tbl_os.consumidor_fone                                             ,
					tbl_os.consumidor_endereco                                         ,
					tbl_os.consumidor_numero                                           ,
					tbl_os.revenda_nome                                                ,
					tbl_os.revenda_cnpj                                                ,
					tbl_revenda.fone as fone_revenda                                   ,
					to_char(tbl_os.data_abertura, 'DD/MM/YYYY') as data_abertura       ,
					to_char(tbl_os.data_fechamento, 'DD/MM/YYYY') as data_fechamento   ,
					to_char(tbl_os.data_nf, 'DD/MM/YYYY') as data_nf                   ,
					tbl_os.nota_fiscal                                                 ,
					tbl_produto.descricao as produto_descricao                         ,
					tbl_produto.referencia as produto_referencia                       
			FROM tbl_os
			JOIN tbl_produto on tbl_produto.produto = tbl_os.produto
			Join tbl_posto   on tbl_posto.posto = tbl_os.posto
			join tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto 
			AND  tbl_posto_fabrica.fabrica = $login_fabrica
			left JOIN tbl_revenda on tbl_os.revenda = tbl_revenda.revenda
			WHERE tbl_os.fabrica = $login_fabrica
			AND  tbl_os.excluida is false
			AND tbl_os.os = $os
			ORDER BY tbl_os.os";
//	echo $xsql;
	$xres = pg_exec($con,$xsql);	
	
	if(pg_numrows($xres)>0){
		$os           = pg_result($xres,0,os);
		$codigo_posto = pg_result($xres,0,codigo_posto);
		$nome_posto   = pg_result($xres,0,nome);
		$consumidor_nome   = pg_result($xres,0,consumidor_nome   );
		$consumidor_cidade = pg_result($xres,0,consumidor_cidade );
		$consumidor_estado = pg_result($xres,0,consumidor_estado );
		$consumidor_fone   = pg_result($xres,0,consumidor_fone   );
		$data_abertura     = pg_result($xres,0,data_abertura     );
		$data_fechamento   = pg_result($xres,0,data_fechamento   );
		$produto_descricao = pg_result($xres,0,produto_descricao );
		$produto_referencia= pg_result($xres,0,produto_referencia );
		$revenda_nome      = pg_result($xres,0,revenda_nome );
		$data_nf           = pg_result($xres,0,data_nf );
		$nota_fiscal       = pg_result($xres,0,nota_fiscal );
		$fone_revenda      = pg_result($xres,0,fone_revenda );
		$fone_posto        = pg_result($xres,0,fone_posto );
		$consumidor_endereco   = pg_result($xres,0,consumidor_endereco );
		$consumidor_numero   = pg_result($xres,0,consumidor_numero );

		echo "<table border='0' cellpadding='4' cellspacing='1'  bgcolor='#596D9B' width='700' align='center' style='font-family: verdana; font-size: 10px; text-align: left;'>";
		echo "<tr>";
		echo "<td  colspan='5' class='titulo_coluna'>Dados para entrevista</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td bgcolor='#FFFFFF' rowspan='3' align='center'><font size='3' ><a href='os_press.php?os=$os' target='_blank'><B>$os</B></a></font></td>";
		echo "<td bgcolor='#d2d7e1'><B>Produto: </B></td>";
		echo "<td bgcolor='#FFFFFF' colspan='3'>$produto_referencia - $produto_descricao</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td bgcolor='#d2d7e1'><B>Abertura: </B></td>";
		echo "<td bgcolor='#FFFFFF'>$data_abertura</td>";
		echo "<td bgcolor='#d2d7e1'><B>Fechamento: </B></td>";
		echo "<td bgcolor='#FFFFFF'>$data_fechamento</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td bgcolor='#d2d7e1'><B>Data NF: </B></td>";
		echo "<td bgcolor='#FFFFFF'>$data_nf</td>";
		echo "<td bgcolor='#d2d7e1'><B>Nota Fiscal: </B></td>";
		echo "<td bgcolor='#FFFFFF'>$nota_fiscal</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<tr>";
		echo "<td bgcolor='#d2d7e1'><B>Revenda: </B></td>";
		echo "<td bgcolor='#FFFFFF' colspan='2'>$revenda_nome</td>";
		echo "<td bgcolor='#d2d7e1'><B>Telefone: </B></td>";
		echo "<td bgcolor='#FFFFFF'>$fone_revenda</td>";
		echo "</tr>";
		echo "<td bgcolor='#d2d7e1'><B>Posto: </B></td>";
		echo "<td bgcolor='#FFFFFF' colspan='2'>$codigo_posto - $nome_posto</td>";
		echo "<td bgcolor='#d2d7e1'><B>Telefone: </B></td>";
		echo "<td bgcolor='#FFFFFF'>$fone_posto</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td bgcolor='#d2d7e1'><B>Consumidor nome:</B></td>";
		echo "<td bgcolor='#FFFFFF' colspan='2'>$consumidor_nome</td>";
		echo "<td bgcolor='#d2d7e1'><B>Telefone:</B></td>";
		echo "<td bgcolor='#FFFFFF'>$consumidor_fone</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td bgcolor='#d2d7e1'><B>Consumidor endereço:</B></td>";
		echo "<td bgcolor='#FFFFFF' colspan='2'>$consumidor_endereco $consumidor_numero</td>";
		echo "<td bgcolor='#d2d7e1'><B>Cidade:</B></td>";
		echo "<td bgcolor='#FFFFFF'>$consumidor_cidade - $consumidor_estado</td>";
		echo "</tr>";

		echo "</table>";
		$sql = " select os from tbl_suggar_questionario where os = $os";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			echo "<BR><table border='0' cellpadding='4' cellspacing='1'  bgcolor='#FF3300' width='700' align='center' style='font-family: verdana; font-size: 11px'><tr><TD align='center'>Pesquisa já realizada para essa OS</TD></tr></table>";
		
		}
		echo "<BR>	";
#HD 353066 INICIO
		echo "<form  name='frm_pesquisa_satisfacao' method='post' action='$PHP_SELF'>";
		echo "<table border='0' cellpadding='2' cellspacing='1'  bgcolor='#596D9B' width='700' align='center' style='font-family: verdana; font-size: 11px'>"; 
		echo "<tr>";
		echo "<td  colspan='5' class='titulo_tabela'>Perguntas da entrevista</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td class='subtitulo' colspan='4' align='left'>O que levou a escolher o produto $produto_descricao?</td>";
		echo "</tr>";
		
		echo "<tr id = 'linha_1' bgcolor='#F7F5F0'>";
		echo "<td  align='left' width='200'>&nbsp;&nbsp;&nbsp;&nbsp; a. Foi o preço </td>";
		echo "<td width='60%' align='left'><input type='radio' name='preco' id='preco' value='t' onclick=\"javascript:marcaLinha('linha_1');\"> Sim ";
		echo "<input type='radio' name='preco' id='preco' value='f' onclick=\"javascript:marcaLinha('linha_1');\"> Não</td>";
		echo "</tr>";
		
		echo "<tr id = 'linha_2' bgcolor='#F1F4FA'>";
		echo "<td align='left' width='200'>&nbsp;&nbsp;&nbsp;&nbsp; b. Foi a qualidade </td>";
		echo "<td width='60%' align='left'><input type='radio' name='qualidade' id='qualidade' value='t' onclick=\"javascript:marcaLinha('linha_2');\"> Sim ";
		echo "<input type='radio' name='qualidade' id='qualidade' value='f' onclick=\"javascript:marcaLinha('linha_2');\"> Não</td>";
		echo "</tr>";
		
		echo "<tr id = 'linha_3' bgcolor='#F7F5F0'>";
		echo "<td align='left' width='200'>&nbsp;&nbsp;&nbsp;&nbsp;  c. Foi o design </td>";
		echo "<td width='60%' align='left'><input type='radio' name='design' id='design' value='t' onclick=\"javascript:marcaLinha('linha_3');\"> Sim ";
		echo "<input type='radio' name='design' id='design' value='f' onclick=\"javascript:marcaLinha('linha_3');\"> Não</td>";
		echo "</tr>";
		
		echo "<tr id = 'linha_4' bgcolor='#F1F4FA'>";
		
		echo "<td align='left' width='200'>&nbsp;&nbsp;&nbsp;&nbsp;  d. Foi a tradição da marca </td>";
		echo "<td width='60%' align='left'><input type='radio' name='tradicao' id='tradicao' value='t' onclick=\"javascript:marcaLinha('linha_4');\"> Sim ";
		echo "<input type='radio' name='tradicao' id='tradicao' value='f' onclick=\"javascript:marcaLinha('linha_4');\"> Não</td>";
		echo "</tr>";
		
		echo "<tr id = 'linha_5' bgcolor='#F7F5F0'>";
		
		echo "<td align='left' width='200'>&nbsp;&nbsp;&nbsp;&nbsp;  e. Foi por indicação </td>";
		echo "<td width='60%' align='left'><input type='radio' name='indicacao' id='indicacao' value='t' onclick=\"javascript:marcaLinha('linha_5');\"> Sim ";
		echo "<input type='radio' name='indicacao' id='indicacao' value='f' onclick=\"javascript:marcaLinha('linha_5');\"> Não</td>";
		echo "</tr>";
		
		echo "<tr id = 'linha_6' bgcolor='#F1F4FA'>";
		
		echo "<td align='left' width='200'>&nbsp;&nbsp;&nbsp;&nbsp;  f. Foi pela capacidade </td>";
		echo "<td width='60%' align='left'><input type='radio' name='capacidade' id='capacidade' value='t' onclick=\"javascript:marcaLinha('linha_6');\"> Sim";
		echo "<input type='radio' name='capacidade' id='capacidade' value='f' onclick=\"javascript:marcaLinha('linha_6');\"> Não</td>";
		echo "</tr>";

		echo "<tr id = 'linha_7' bgcolor='#F7F5F0'>";
		echo "<td  align='left' width='200'>&nbsp;&nbsp;&nbsp;&nbsp;  g. Foi por inovação </td>";
		echo "<td width='60%' align='left'><input type='radio' name='inovacao' id='inovacao' value='t' onclick=\"javascript:marcaLinha('linha_7');\"> Sim ";
		echo " <input type='radio' name='inovacao' id='inovacao' value='f' onclick=\"javascript:marcaLinha('linha_7');\"> Não</td>";
		echo "</tr>";

		echo "<tr id = 'linha_8' class='subtitulo'>";
		echo "<td colspan='3' align='left'><B>Com relação ao $produto_descricao </B></td>";
		echo "</tr>";

		echo "<tr id = 'linha_9' bgcolor='#F1F4FA'>";
		echo "<td  align='left' width='200'>&nbsp;&nbsp;&nbsp;&nbsp; a. Satisfeito </td>";
		echo "<td width='60%' align='left'><input type='radio' name='satisfeito' id='satisfeito' value='t' onClick=\"javascript:verificaSatisfeito('sim');marcaLinha('linha_9');\"> Sim ";
		echo " <input type='radio' name='satisfeito' id='satisfeito' value='f' onClick=\"javascript:verificaSatisfeito('nao');marcaLinha('linha_9');\"> Não</td>";
		echo "</tr>";

		echo "<tr id = 'linha_10' class='subtitulo'>";
		echo "<td  colspan='3' align='left'> &nbsp;&nbsp;<B>b. Se satisfeito: Sua satisfação é com relação.</B></td>";
		echo "</tr>";

		echo "<tr id = 'linha_11' bgcolor='#F7F5F0'>";
		echo "<td  align='left' width='200'>&nbsp;&nbsp;&nbsp;&nbsp; i. Modo de usar o produto </td>";
		echo "<td width='60%' align='left'><input type='radio' name='satisfeito_usar' id='satisfeito_usar' value='t' onclick=\"javascript:marcaLinha('linha_11');\"> Sim ";
		echo " <input type='radio' name='satisfeito_usar' id='satisfeito_usar_1' value='f' onclick=\"javascript:marcaLinha('linha_11');\"> Não</td>";
		echo "</tr>";

		echo "<tr id = 'linha_12' bgcolor='#F1F4FA'>";
		echo "<td  align='left' width='200'>&nbsp;&nbsp;&nbsp;&nbsp; ii. Manual de orientação </td>";
		echo "<td width='60%' align='left'><input type='radio' name='satisfeito_manual' id='satisfeito_manual' value='t' onclick=\"javascript:marcaLinha('linha_12');\"> Sim ";
		echo " <input type='radio' name='satisfeito_manual' id='satisfeito_manual_1' value='f' onclick=\"javascript:marcaLinha('linha_12');\"> Não</td>";
		echo "</tr>";

		echo "<tr id = 'linha_13' bgcolor='#F7F5F0'>";
		echo "<td  align='left' width='200'>&nbsp;&nbsp;&nbsp;&nbsp; iii. Consumo de energia </td>";
		echo "<td width='60%' align='left'><input type='radio' name='satisfeito_energia' id='satisfeito_energia' value='t' onclick=\"javascript:marcaLinha('linha_13');\"> Sim ";
		echo " <input type='radio' name='satisfeito_energia' id='satisfeito_energia_1' value='f' onclick=\"javascript:marcaLinha('linha_13');\"> Não</td>";
		echo "</tr>";

		echo "<tr id = 'linha_14' bgcolor='#F1F4FA'>";
		echo "<td  align='left' width='200'>&nbsp;&nbsp;&nbsp;&nbsp; iv. Nível de ruído </td>";
		echo "<td width='60%' align='left'><input type='radio' name='satisfeito_barulho' id='satisfeito_barulho' value='t' onclick=\"javascript:marcaLinha('linha_14');\"> Sim ";
		echo " <input type='radio' name='satisfeito_barulho' id='satisfeito_barulho_1' value='f' onclick=\"javascript:marcaLinha('linha_14');\"> Não</td>";
		echo "</tr>";

		echo "<tr id = 'linha_15' bgcolor='#F7F5F0'>";
		echo "<td  align='left' width='200'>&nbsp;&nbsp;&nbsp;&nbsp; v. Cor do produto </td>";
		echo "<td width='60%' align='left'><input type='radio' name='satisfeito_cor' id='satisfeito_cor' value='t' onclick=\"javascript:marcaLinha('linha_15');\"> Sim ";
		echo " <input type='radio' name='satisfeito_cor' id='satisfeito_cor_1' value='f' onclick=\"javascript:marcaLinha('linha_15');\"> Não</td>";
		echo "</tr>";

		echo "<tr id = 'linha_16' class='subtitulo'>";
		echo "<td colspan='3' align='left'> &nbsp;&nbsp;c. Se insatisfeito: Sua insatisfação é com relação.</td>";
		echo "</tr>";

		echo "<tr id = 'linha_17' bgcolor='#F1F4FA'>";
		echo "<td  align='left' width='200'>&nbsp;&nbsp;&nbsp;&nbsp; i. Modo de usar o produto </td>";
		echo "<td width='60%' align='left'><input type='radio' name='insatisfeito_usar' id='insatisfeito_usar' value='t' onclick=\"javascript:marcaLinha('linha_17');\"> Sim ";
		echo " <input type='radio' name='insatisfeito_usar' id='insatisfeito_usar_1' value='f' onclick=\"javascript:marcaLinha('linha_17');\"> Não</td>";
		echo "</tr>";

		echo "<tr id = 'linha_18' bgcolor='#F7F5F0'>";
		echo "<td  align='left' width='200'>&nbsp;&nbsp;&nbsp;&nbsp; ii. Manual de orientação </td>";
		echo "<td width='60%' align='left'><input type='radio' name='insatisfeito_manual' id='insatisfeito_manual' value='t' onclick=\"javascript:marcaLinha('linha_18');\"> Sim ";
		echo " <input type='radio' name='insatisfeito_manual' id='insatisfeito_manual_1' value='f' onclick=\"javascript:marcaLinha('linha_18');\"> Não</td>";
		echo "</tr>";

		echo "<tr id = 'linha_19' bgcolor='#F1F4FA'>";
		echo "<td  align='left' width='200'>&nbsp;&nbsp;&nbsp;&nbsp; iii. Consumo de energia </td>";
		echo "<td width='60%' align='left'><input type='radio' name='insatisfeito_energia' id='insatisfeito_energia' value='t' onclick=\"javascript:marcaLinha('linha_19');\"> Sim ";
		echo " <input type='radio' name='insatisfeito_energia' id='insatisfeito_energia_1' value='f' onclick=\"javascript:marcaLinha('linha_19');\"> Não</td>";
		echo "</tr>";

		echo "<tr id = 'linha_20' bgcolor='#F7F5F0'>";
		echo "<td  align='left' width='200'>&nbsp;&nbsp;&nbsp;&nbsp; iv. Nível de ruído </td>";
		echo "<td width='60%' align='left'><input type='radio' name='insatisfeito_barulho' id='insatisfeito_barulho' value='t' onclick=\"javascript:marcaLinha('linha_20');\"> Sim ";
		echo " <input type='radio' name='insatisfeito_barulho' id='insatisfeito_barulho_1' value='f' onclick=\"javascript:marcaLinha('linha_20');\"> Não</td>";
		echo "</tr>";

		echo "<tr id = 'linha_21' bgcolor='#F1F4FA'>";
		echo "<td  align='left' width='200'>&nbsp;&nbsp;&nbsp;&nbsp; v. Cor do produto </td>";
		echo "<td width='60%' align='left'><input type='radio' name='insatisfeito_cor' id='insatisfeito_cor' value='t' onclick=\"javascript:marcaLinha('linha_21');\"> Sim ";
		echo " <input type='radio' name='insatisfeito_cor' id='insatisfeito_cor_1' value='f' onclick=\"javascript:marcaLinha('linha_21');\"> Não</td>";
		echo "</tr>";

		echo "<tr id = 'linha_22' bgcolor='#F7F5F0'>";
		echo "<td  align='left' width='200'>&nbsp;&nbsp;&nbsp;&nbsp; vi. Quebrou com pouco uso </td>";
		echo "<td width='60%' align='left'><input type='radio' name='insatisfeito_quebra_uso' id='insatisfeito_quebra_uso' value='t' onclick=\"javascript:marcaLinha('linha_22');\"> Sim ";
		echo " <input type='radio' name='insatisfeito_quebra_uso' id='insatisfeito_quebra_uso_1' value='f' onclick=\"javascript:marcaLinha('linha_22');\"> Não</td>";
		echo "</tr>";


		echo "<tr class='subtitulo'>";
		echo "<td colspan='3' align='left'><B>Com relação ao atendimento da autorizada</B></td>";
		echo "</tr>";

		echo "<tr id = 'linha_23' bgcolor='#F1F4FA'>";
		echo "<td  align='left' width='200'>&nbsp;&nbsp;&nbsp;&nbsp; O atendimento foi rápido </td>";
		echo "<td width='60%' align='left'><input type='radio' name='atendimento_rapido' id='atendimento_rapido' value='t' onclick=\"javascript:marcaLinha('linha_23');\"> Sim ";
		echo " <input type='radio' name='atendimento_rapido' id='atendimento_rapido' value='f' onclick=\"javascript:marcaLinha('linha_23');\"> Não</td>";
		echo "</tr>";
		
		echo "<tr id = 'linha_24' bgcolor='#F7F5F0'>";
		echo "<td  align='left' width='200'>&nbsp;&nbsp;&nbsp;&nbsp; b. O aspecto da loja, gerou confiança? </td>";
		echo "<td width='60%' align='left'><input type='radio' name='confianca' id='confianca' value='t' onclick=\"javascript:marcaLinha('linha_24');\"> Sim ";
		echo " <input type='radio' name='confianca' id='confianca' value='f' onclick=\"javascript:marcaLinha('linha_24');\"> Não</td>";
		echo "</tr>";
		
		echo "<tr id = 'linha_25' bgcolor='#F1F4FA'>";
		echo "<td  align='left' width='200'>&nbsp;&nbsp;&nbsp;&nbsp; c. O problema foi resolvido </td>";
		echo "<td width='60%' align='left'><input type='radio' name='problema_resolvido' id='problema_resolvido' value='t' onclick=\"javascript:marcaLinha('linha_25');\"> Sim ";
		echo " <input type='radio' name='problema_resolvido' id='problema_resolvido' value='f' onclick=\"javascript:marcaLinha('linha_25');\"> Não</td>";
		echo "</tr>";
		
		echo "<tr id = 'linha_26' bgcolor='#F7F5F0'>";
		echo "<td  align='left' width='200'>&nbsp;&nbsp;&nbsp;&nbsp; d. De 0 a 10, qual nota daria ao posto autorizado? </td>";
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "<td width='60%' align='left'>&nbsp;<input type='text' name='nota' id='nota' value='' maxlength='2' size='3'> Nota </td>";
		echo "</tr>";
		
		echo "<tr id = 'linha_27' bgcolor='#F1F4FA'>";
		echo "<td  align='left' width='200'>&nbsp;&nbsp;&nbsp;&nbsp; e. De 0 a 10, qual nota daria ao produto? </td>";
		echo "<td width='60%' align='left'>&nbsp;<input type='text' name='nota_produto' id='nota_produto' value='' maxlength='2' size='3'> Nota </td>";
		echo "</tr>";

		echo "</table>";
		echo "<BR>";
echo "<input type='hidden' name='btn_gravar' id='btn_gravar' value=''>";
echo "<input type='hidden' name='os' id='os' value='$os'>";
		echo "<input type='button' value='Continuar' style='cursor:pointer' onclick=\"javascript: if (document.frm_pesquisa_satisfacao.btn_gravar.value == '' ) { validaCampos(); } else { alert ('Aguarde submissão') }\" title='Confirmar pesquisa'>";
echo "</form>";
	}
#HD 353066 FIM
}
$btn_gravar = $_POST['btn_gravar'];
if(strlen($btn_gravar)>0){

$os            = $_POST['os'     ];
$preco         = $_POST['preco'     ];
$qualidade     = $_POST['qualidade' ];
$design        = $_POST['design'    ];
$tradicao      = $_POST['tradicao'  ];
$indicacao     = $_POST['indicacao' ];
$capacidade    = $_POST['capacidade'];
$inovacao      = $_POST['inovacao'  ];

if(strlen($preco     )==1) $preco     = "'$preco'";
if(strlen($qualidade )==1) $qualidade = "'$qualidade'";
if(strlen($design    )==1) $design    = "'$design'";
if(strlen($tradicao  )==1) $tradicao  = "'$tradicao'";
if(strlen($indicacao )==1) $indicacao = "'$indicacao'";
if(strlen($capacidade)==1) $capacidade= "'$capacidade'";
if(strlen($inovacao  )==1) $inovacao  = "'$inovacao'";

if(strlen($preco     )==0) $preco     = "null";
if(strlen($qualidade )==0) $qualidade = "null";
if(strlen($design    )==0) $design    = "null";
if(strlen($tradicao  )==0) $tradicao  = "null";
if(strlen($indicacao )==0) $indicacao = "null";
if(strlen($capacidade)==0) $capacidade= "null";
if(strlen($inovacao  )==0) $inovacao  = "null";



$satisfeito    = $_POST['satisfeito'];
if(strlen($satisfeito)==1) $satisfeito     = "'". $satisfeito    . "'";

$satisfeito_usar     = $_POST['satisfeito_usar'   ];
$satisfeito_manual   = $_POST['satisfeito_manual' ];
$satisfeito_energia  = $_POST['satisfeito_energia'];
$satisfeito_barulho  = $_POST['satisfeito_barulho'];
$satisfeito_cor      = $_POST['satisfeito_cor'    ];

$insatisfeito_usar        = $_POST['insatisfeito_usar'   ];
$insatisfeito_manual      = $_POST['insatisfeito_manual' ];
$insatisfeito_energia     = $_POST['insatisfeito_energia'];
$insatisfeito_barulho     = $_POST['insatisfeito_barulho'];
$insatisfeito_cor         = $_POST['insatisfeito_cor'    ];
$insatisfeito_quebra_uso  = $_POST['insatisfeito_quebra_uso'];


if(strlen($satisfeito_usar   )==1) $satisfeito_usar     = "'". $satisfeito_usar    . "'";
if(strlen($satisfeito_manual )==1) $satisfeito_manual   = "'". $satisfeito_manual  . "'";
if(strlen($satisfeito_energia)==1) $satisfeito_energia  = "'". $satisfeito_energia . "'";
if(strlen($satisfeito_barulho)==1) $satisfeito_barulho  = "'". $satisfeito_barulho . "'";
if(strlen($satisfeito_cor    )==1) $satisfeito_cor      = "'". $satisfeito_cor     . "'";

if(strlen($satisfeito_usar   )==0) $satisfeito_usar     = "null";
if(strlen($satisfeito_manual )==0) $satisfeito_manual   = "null";
if(strlen($satisfeito_energia)==0) $satisfeito_energia  = "null";
if(strlen($satisfeito_barulho)==0) $satisfeito_barulho  = "null";
if(strlen($satisfeito_cor    )==0) $satisfeito_cor      = "null";

if(strlen($insatisfeito_usar   )==1) $insatisfeito_usar     = "'". $insatisfeito_usar    . "'";
if(strlen($insatisfeito_manual )==1) $insatisfeito_manual   = "'". $insatisfeito_manual    . "'";
if(strlen($insatisfeito_energia)==1) $insatisfeito_energia  = "'". $insatisfeito_energia    . "'";
if(strlen($insatisfeito_barulho)==1) $insatisfeito_barulho  = "'". $insatisfeito_barulho    . "'";
if(strlen($insatisfeito_cor    )==1) $insatisfeito_cor      = "'". $insatisfeito_cor    . "'";
if(strlen($insatisfeito_quebra_uso)==1) $insatisfeito_quebra_uso= "'". $insatisfeito_quebra_uso    . "'";

if(strlen($insatisfeito_usar   )==0) $insatisfeito_usar     = "null";
if(strlen($insatisfeito_manual )==0) $insatisfeito_manual   = "null";
if(strlen($insatisfeito_energia)==0) $insatisfeito_energia  = "null";
if(strlen($insatisfeito_barulho)==0) $insatisfeito_barulho  = "null";
if(strlen($insatisfeito_cor    )==0) $insatisfeito_cor      = "null";
if(strlen($insatisfeito_quebra_uso )==0) $insatisfeito_quebra_uso = "null";

/*Insatisfeito*/
if($satisfeito == "'f'"){
	$satisfeito_usar       = "null";
	$satisfeito_manual     = "null";
	$satisfeito_energia    = "null";
	$satisfeito_barulho    = "null";
	$satisfeito_cor        = "null";
	$satisfeito_quebra_uso = "null";
}
if($satisfeito == "'t'"){
	$insatisfeito_usar       = "null";
	$insatisfeito_manual     = "null";
	$insatisfeito_energia    = "null";
	$insatisfeito_barulho    = "null";
	$insatisfeito_cor        = "null";
	$insatisfeito_quebra_uso = "null";
}


$atendimento_rapido  = $_POST['atendimento_rapido'];
$confianca           = $_POST['confianca'         ];
$problema_resolvido  = $_POST['problema_resolvido'];
$nota                = $_POST['nota'              ];
$nota_produto        = $_POST['nota_produto'      ];

if(strlen($atendimento_rapido)==1) $atendimento_rapido= "'$atendimento_rapido'";
if(strlen($confianca         )==1) $confianca         = "'$confianca'";
if(strlen($problema_resolvido)==1) $problema_resolvido= "'$problema_resolvido'";

if(strlen($atendimento_rapido)==0) $atendimento_rapido= "null";
if(strlen($confianca         )==0) $confianca         = "null";
if(strlen($problema_resolvido)==0) $problema_resolvido= "null";
if(strlen($nota              )==0) $nota              = "null";
if(strlen($nota_produto      )==0) $nota_produto      = "null";

$sql = "INSERT into tbl_suggar_questionario(
					os                     ,
					admin                  ,
					preco                  ,
					qualidade              ,
					design                 ,
					tradicao               ,
					indicacao              ,
					capacidade             ,
					inovacao               ,
					satisfeito             ,
					satisfeito_modo_usar   ,
					satisfeito_manual      ,
					satisfeito_energia     ,
					satisfeito_barulho     ,
					satisfeito_cor         ,
					insatisfeito_modo_usar ,
					insatisfeito_manual    ,
					insatisfeito_energia   ,
					insatisfeito_barulho   ,
					insatisfeito_cor       ,
					insatisfeito_quebra_uso,
					atendimento_rapido     ,
					confianca              ,
					problema_resolvido     ,
					nota                   ,
					nota_produto
		)values(
					$os                      ,
					$login_admin             ,
					$preco                   ,
					$qualidade               ,
					$design                  ,
					$tradicao                ,
					$indicacao               ,
					$capacidade              ,
					$inovacao                ,
					$satisfeito              ,
					$satisfeito_usar         ,
					$satisfeito_manual       ,
					$satisfeito_energia      ,
					$satisfeito_barulho      ,
					$satisfeito_cor          ,
					$insatisfeito_usar       ,
					$insatisfeito_manual     ,
					$insatisfeito_energia    ,
					$insatisfeito_barulho    ,
					$insatisfeito_cor        ,
					$insatisfeito_quebra_uso ,
					$atendimento_rapido    ,
					$confianca             ,
					$problema_resolvido    ,
					$nota                ,
					$nota_produto
		)";
//echo $sql;
$res =pg_exec($con,$sql);
$msg_erro = pg_errormessage($con);
if(strlen($msg_erro)==0){echo "Pesquisa Realizada com Sucesso!";}
}

?>

<br>

<? include "rodape.php" ?>
