<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';

if (strlen($_GET['orcamento']) > 0) $orcamento = trim($_GET['orcamento']);

/*================ LE OS DA BASE DE DADOS =========================*/

if (strlen ($orcamento) > 0) {
	$sql = "SELECT	tbl_orcamento.orcamento                                         ,
			to_char(tbl_orcamento.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
			tbl_orcamento.cliente                                               ,
			tbl_orcamento.consumidor_nome                                       ,
			tbl_orcamento.consumidor_fone                                       ,
			tbl_orcamento.vendedor                                              ,
			tbl_orcamento.total_mao_de_obra                                     ,
			tbl_orcamento.total_pecas                                           ,
			tbl_orcamento.brinde                                                ,
			tbl_orcamento.frete                                                 ,
			tbl_orcamento.desconto                                              ,
			tbl_orcamento.acrescimo                                             ,
			tbl_orcamento.total                                                 ,
			tbl_orcamento.aprovado
		FROM  tbl_orcamento
		WHERE tbl_orcamento.orcamento = $orcamento
		AND   tbl_orcamento.loja      = $login_posto";
	//echo $sql."<br>";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows ($res) == 1) {
		$orcamento                 = pg_result ($res,0,orcamento)     ;
		$data_digitacao            = pg_result ($res,0,data_digitacao) ;
		$cliente                   = pg_result ($res,0,cliente)        ;
		$consumidor_nome           = pg_result ($res,0,consumidor_nome);
		$consumidor_fone           = pg_result ($res,0,consumidor_fone);
		$vendedor                  = pg_result ($res,0,vendedor)       ;

		//SELECIONA OS DADOS DO CLIENTE PRA JOGAR NA ORCAMENTO
		if (strlen($cliente) > 0 ) {
			$sql = "SELECT  tbl_cliente.cliente           ,
					tbl_cliente.cpf               ,
					tbl_cliente.nome              ,
					tbl_cliente.endereco          ,
					tbl_cliente.numero            ,
					tbl_cliente.complemento       ,
					tbl_cliente.bairro            ,
					tbl_cliente.cep               ,
					tbl_cliente.rg                ,
					tbl_cliente.fone              ,
					tbl_cliente.contrato          ,
					tbl_cidade.nome      AS cidade,
					tbl_cidade.estado
			FROM tbl_cliente
			LEFT JOIN tbl_cidade USING (cidade)
			WHERE tbl_cliente.cliente = '$cliente' ";
			//echo $sql."<br>";
			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) == 1) {
				$consumidor_cliente     = trim (pg_result ($res,0,cliente))    ;
				$consumidor_cpf         = trim (pg_result ($res,0,cpf))        ;
				//$consumidor_fone        = trim (pg_result ($res,0,fone))       ;
				$consumidor_nome        = trim (pg_result ($res,0,nome))       ;
				$consumidor_endereco    = trim (pg_result ($res,0,endereco))   ;
				$consumidor_numero      = trim (pg_result ($res,0,numero))     ;
				$consumidor_complemento = trim (pg_result ($res,0,complemento));
				$consumidor_bairro      = trim (pg_result ($res,0,bairro))     ;
				$consumidor_cep         = trim (pg_result ($res,0,cep))        ;
				$consumidor_rg          = trim (pg_result ($res,0,rg))         ;
				$consumidor_cidade      = trim (pg_result ($res,0,cidade))     ;
				$consumidor_estado      = trim (pg_result ($res,0,estado))     ;
				$consumidor_contrato    = trim (pg_result ($res,0,contrato))   ;
			}
		}

		$sql = "SELECT  tbl_orcamento_os.tecnico           ,
				tbl_orcamento_os.fabrica           ,
				tbl_orcamento_os.fabricante_nome   ,
				tbl_orcamento_os.abertura          ,
				tbl_orcamento_os.fechamento        ,
				tbl_orcamento_os.defeito_reclamado ,
				tbl_orcamento_os.defeito_constatado,
				tbl_orcamento_os.solucao           ,
				tbl_orcamento_os.produto           ,
				tbl_orcamento_os.produto_descricao ,
				tbl_orcamento_os.serie             ,
				tbl_orcamento_os.aparencia         ,
				tbl_orcamento_os.acessorios        ,
				tbl_orcamento_os.revenda           ,
				to_char(tbl_orcamento_os.data_nf,'DD/MM/YYYY') AS data_nf,
				tbl_orcamento_os.nf                ,
				tbl_orcamento_os.reincidencia      ,
				tbl_produto.referencia             ,
				tbl_produto.descricao
			FROM tbl_orcamento_os
			LEFT JOIN tbl_produto USING (produto)
			WHERE tbl_orcamento_os.orcamento = $orcamento";
		//echo $sql."<br>";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) == 1) {

			$tecnico            = trim (pg_result ($res,0,tecnico))           ;
			$fabrica            = trim (pg_result ($res,0,fabrica))           ;
			$fabricante_nome    = trim (pg_result ($res,0,fabricante_nome))   ;
			$abertura           = trim (pg_result ($res,0,abertura))          ;
			$fechamento         = trim (pg_result ($res,0,fechamento))        ;
			$defeito_reclamado  = trim (pg_result ($res,0,defeito_reclamado)) ;
			$defeito_constatado = trim (pg_result ($res,0,defeito_constatado));
			$solucao            = trim (pg_result ($res,0,solucao))           ;
			$produto            = trim (pg_result ($res,0,produto))           ;
			$produto_referencia = trim (pg_result ($res,0,referencia));
			$produto_descricao  = trim (pg_result ($res,0,produto_descricao)) ;
			$produto_serie      = trim (pg_result ($res,0,serie))             ;
			$produto_aparencia  = trim (pg_result ($res,0,aparencia))         ;
			$produto_acessorios = trim (pg_result ($res,0,acessorios))        ;
			$revenda            = trim (pg_result ($res,0,revenda))           ;
			$data_nf            = trim (pg_result ($res,0,data_nf))           ;
			$nota_fiscal        = trim (pg_result ($res,0,nf))                ;
			$reincidencia       = trim (pg_result ($res,0,reincidencia))      ;

		}else{
			$sql = "SELECT  tbl_orcamento_venda.orcamento         ,
							tbl_orcamento_os.reincidencia
				FROM tbl_orcamento_os
				WHERE tbl_orcamento_os.orcamento = $orcamento";
		}

	}
}


if(strlen($os)==0)$body_onload = "onload = 'javascript: document.frm_os.posto_codigo.focus()'";
$title       = "Cadastro de Ordem de Serviço - ADMIN"; 

//include "cabecalho.php";

?>



<? include "javascript_pesquisas.php" ?>

<script language="JavaScript">


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


</script>

<!--========================= AJAX ==================================.-->
<script language='javascript' src='ajax.js'></script>
<script language='javascript' src='ajax_cep.js'></script>
<script language='javascript' src='ajax_orcamento.js'></script>
<? include "javascript_pesquisas.php" ?>

<style>
a{
	font-family: Verdana;
	font-size: 10px;
	font-weight: bold;
	color:#3399FF;
}
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

img{
	border:0;
}
.Caixa{
	FONT: 8pt Arial ;
	BORDER-RIGHT:     #6699CC 1px solid;
	BORDER-TOP:       #6699CC 1px solid;
	BORDER-LEFT:      #6699CC 1px solid;
	BORDER-BOTTOM:    #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
}


</style>
<?$data_abertura = date("d/m/Y");?>
<form style="MARGIN: 0px; WORD-SPACING: 0px" name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
<input class="Caixa" type="hidden" name="orcamento" value="<? echo $orcamento ?>">
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td valign="top" align="left">
		<table style=' border:#485989 1px solid; background-color: #e6eef7 ' align='center' width='750' border='0'>
			<tr >
			<td width='20'></td>
			<td nowrap class='Label' width='80'>Data Abertura</td>
			<td><input name="data_abertura" size="12" maxlength="10" value="<? echo $data_abertura ?>" type="text" class="Caixa" tabindex="0" READONLY ><font size='-3' COLOR='#000099'></td>
				<td width='100'></td>
				<td nowrap class='Label' width='80'>Vendedor</td>
				<td>
				<?
				echo "<select class='Caixa' size='1' name='vendedor'>";
				echo "<option selected></option>";
				$sql = "SELECT *
						FROM   tbl_empregado
						WHERE  tbl_empregado.posto = $login_posto
						AND    tbl_empregado.ativo IS TRUE
						ORDER BY nome;";
				$res = pg_exec ($con,$sql) ;
				
				for ($x = 0 ; $x < pg_numrows ($res) ; $x++ ) {
					echo "<option ";
					if ($vendedor == pg_result ($res,$x,empregado)) echo " selected ";
					echo " value='" . pg_result ($res,$x,empregado) . "'>" ;
					echo pg_result($res,$x,nome);
					echo "</option>";
				}
				echo "</select>";
				?>
				</td>
				<td class='Label' nowrap><INPUT TYPE="radio" NAME="tipo_orcamento" value='V'> Venda</td>
				<td class='Label' nowrap><INPUT TYPE="radio" NAME="tipo_orcamento" value='R' <?if(strlen($produto_descricao)>0) echo "CHECKED";?>> OS</td>

				<td width='100'></td>
			</tr>
		</table>

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
			<td class='Titulo'>Consumidor</td>
			</td>
		<tr>
			<td class='Label'>Nome:</td>
			<td><input class="Caixa" type="text" name="consumidor_nome" size="40" maxlength="50" value="<? echo $consumidor_nome ?>"></td>
			<td class='Label'>CPF:</td>
			<td><input class="Caixa" type="text" name="consumidor_cpf" size="10" maxlength="20" value="<? echo $consumidor_cpf ?>">
			<img src='imagens/btn_lupa_novo.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_cpf,"cpf")'  style='cursor: pointer'>
			</td>
			<td class='Label'>Telefone:</td>
			<td><input class="Caixa" type="text" name="consumidor_fone"   size="15" maxlength="30" value="<? echo $consumidor_fone ?>"></td>
		</tr>
		<tr>
			<td class='Label'>CEP</td>
			<td><input class="Caixa" type="text" name="consumidor_cep"   size="10" maxlength="10" value="<? echo $consumidor_cep ?>" onblur="buscaCEP(this.value, document.frm_os.consumidor_endereco, document.frm_os.consumidor_bairro, document.frm_os.consumidor_cidade, document.frm_os.consumidor_estado) ;"></td>
		</tr>
		<tr>
			<td class='Label'>Endereço:</td>
			<td><input class="Caixa" type="text" name="consumidor_endereco"   size="40" maxlength="50" value="<? echo $consumidor_endereco ?>"></td>
			<td class='Label'>Número:</td>
			<td><input class="Caixa" type="text" name="consumidor_numero"   size="5" maxlength="10" value="<? echo $consumidor_numero ?>"></td>
			<td class='Label'>Complemento:</td>
			<td><input class="Caixa" type="text" name="consumidor_complemento"   size="5" maxlength="10" value="<? echo $consumidor_complemento ?>"></td>
		<tr>
		</tr>
			<td class='Label'>Bairro:</td>
			<td><input class="Caixa" type="text" name="consumidor_bairro"   size="20" maxlength="10" value="<? echo $consumidor_bairro ?>"></td>
			<td class='Label'>Cidade:</td>
			<td><input class="Caixa" type="text" name="consumidor_cidade"   size="15" maxlength="50" value="<? echo $consumidor_cidade ?>"></td>
			<td class='Label'>Estado:</td>
			<td><input class="Caixa" type="text" name="consumidor_estado"   size="2" maxlength="2" value="<? echo $consumidor_estado ?>"></td>
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
			<td class='Titulo' colspan='4'>Reparação do Produto</td>
		</tr>
		<tr>
			<td nowrap class='Label' width='140'>Referência</td>
			<td><input class="Caixa" type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" >&nbsp;<img src='imagens/btn_lupa_novo.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'referencia',document.frm_os.produto_voltagem)"></td>
			<td nowrap class='Label'>Descrição</td>
			<td><input class="Caixa" type="text" name="produto_descricao" size="30" value="<? echo $produto_descricao ?>" >&nbsp;<img src='imagens/btn_lupa_novo.gif'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'descricao',document.frm_os.produto_voltagem)"></A>
			</td>
		</tr>
		<tr>
			</td>
			<td nowrap class='Label'>N. Série.</td>
			<td><input class="Caixa" type="text" name="produto_serie" size="15" maxlength="20" value="<? echo $produto_serie ?>" onblur='javascript:liberar_os_item(this.form);'></td>
			<td nowrap class='Label'>Aparência</td>
			<td><input class="Caixa" type="text" name="produto_aparencia" size="15" value="<? echo $produto_aparencia;?>" ></td>

			<td nowrap class='Label'>Acessórios</td>
			<td><input class="Caixa" type="text" name="produto_acessorios" size="15" value="<? echo $produto_acessorios ?>" ></td>
			
		</tr>
		</table>
		<input class="Caixa" type="hidden" name="produto_voltagem" size="15" maxlength="20" value="">
		<!-- Informações da OS - FIM  -->

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
			<td><input class="Caixa" type="text" name="revenda_nome" size="30" maxlength="50" value="<? echo $revenda_nome ?>" ></td>
			<td class='Label'>Nota Fiscal:</td>
			<td><input class="Caixa" type="text" name="nota_fiscal"  size="8"  maxlength="8"  value="<? echo $nota_fiscal ?>" ></td>
			<td class='Label'>Data Compra:</td>
			<td><input class="Caixa" type="text" name="data_nf"    size="12" maxlength="10" value="<? echo $data_nf ?>" tabindex="0" onKeyUp="formata_data(this.value,'frm_os', 'data_nf')"> <font size='-3' color='#000099'>Ex.: 25/10/2006</td>
		</tr>
		</table>
		<!-- Informações da Revenda  FIM -->

	</td>
</tr>
<tr><td><img height="2" width="16" src="imagens/spacer.gif"></td></tr>
<tr>
	<td valign="top" align="left">
<?if(strlen($orcamento)>0){
	$sql = "SELECT tbl_orcamento_os.produto,tbl_linha.linha,tbl_familia.familia 
		FROM tbl_orcamento_os 
		JOIN tbl_produto USING(produto) 
		JOIN tbl_linha   ON tbl_linha.linha     = tbl_produto.linha
		JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia
		WHERE tbl_orcamento_os.orcamento = $orcamento";
	$res = pg_exec($con,$sql) ;
	if(pg_numrows($res)>0){
		$produto = pg_result($res,0,produto);
		$familia = pg_result($res,0,familia);
		$linha   = pg_result($res,0,linha)  ;
	}
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

echo "<tr>";
echo "<td class='Label' align='left' width='120'>Defeito Reclamado:</td>";
echo "<td><input type='text' name='defeito_reclamado' size='40' class='Caixa' value='$defeito_reclamado'></td>";
echo "</tr>";

echo "<tr>";
echo "<td class='Label' align='left' >Defeito Constatado:</td>";
echo "<td><input type='text' name='defeito_constatado' size='40' class='Caixa' value='$defeito_constatado'></td>";
echo "</tr>";

echo "<tr>";
echo "<td class='Label' align='left' >Solução:</td>";
echo "<td><input type='text' name='solucao_os' size='40' class='Caixa' value='$solucao'></td>";
echo "</tr>";
/*
echo "<tr>";
echo "<td class='Label' align='left' >Defeito Reclamado:</td>";
echo "<td><select name='defeito_reclamado'  class='Caixa' style='width:220px;' onfocus='listaDefeitos(document.frm_os.produto_referencia.value);' >";
echo "<option id='opcoes' value=''></option>";
echo "</select>";
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
	echo "<select name='defeito_constatado'  class='Caixa' style='width: 220px;' onfocus='listaConstatado(document.frm_os.linha.value, document.frm_os.familia.value,document.frm_os.defeito_reclamado.value);' >";
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
	echo "<select name='solucao_os' class='Caixa'  style='width:200px;' onfocus='listaSolucao(document.frm_os.defeito_constatado.value, document.frm_os.linha.value, document.frm_os.defeito_reclamado.value,  document.frm_os.familia.value);' >";
	echo "<option id='opcoes' value=''></option>";
	echo "</select>";
	echo "</td>";
	echo "</tr>";

}
*/

echo "</table>";

?>
	</td>
</tr>
<tr><td><img height="0" width="16" src="imagens/spacer.gif"></td></tr>
<tr>
	<td valign="top" align="left">
<?
$qtde_item = 10;

if(strlen($orcamento) > 0 AND strlen ($msg_erro) == 0){
	$inicio_itens = 0;
	$qtde_item = 10;
	$sql = "SELECT  tbl_orcamento_item.orcamento_item                                  ,
			tbl_orcamento_item.qtde                                            ,
			tbl_orcamento_item.descricao                   AS item_descricao   ,
			tbl_orcamento_item.preco                                           ,
			tbl_peca.referencia                                                ,
			tbl_peca.descricao                                                 ,
			tbl_defeito.defeito                                                ,
			tbl_servico_realizado.servico_realizado
		FROM    tbl_orcamento_item
		LEFT JOIN    tbl_peca              USING (peca)
		LEFT JOIN tbl_defeito              USING (defeito)
		LEFT JOIN tbl_servico_realizado    USING (servico_realizado)
		WHERE   tbl_orcamento_item.orcamento = $orcamento
		ORDER BY tbl_orcamento_item.orcamento_item;";

	$res = pg_exec ($con,$sql) ;

	if (pg_numrows($res) > 0) {
		$fim_itens = $inicio_itens + pg_numrows($res);
		$i = 0;
		for ($k = $inicio_itens ; $k < $fim_itens ; $k++) {
			$orcamento_item[$k]          = trim(pg_result($res,$i,orcamento_item))   ;
			$peca[$k]                    = trim(pg_result($res,$i,referencia))       ;
			$qtde[$k]                    = trim(pg_result($res,$i,qtde))             ;
			$descricao[$k]               = trim(pg_result($res,$i,descricao))        ;
			$item_descricao[$k]          = trim(pg_result($res,$i,item_descricao))   ;
			$defeito[$k]                 = trim(pg_result($res,$i,defeito))          ;
			$servico[$k]                 = trim(pg_result($res,$i,servico_realizado));

			if(strlen($descricao[$k])==0) $descricao[$k] = $item_descricao[$k];

			$i++;

		}
	}
}


//--===== Lançamento das Peças da OS ====================================================================
echo "<input type='hidden' name='qtde_item' value='$qtde_item'>";
echo "<table style=' border:#76D176 1px solid; background-color: #EFFAEF' align='center' width='750' border='0'>";
echo "<tr height='20' bgcolor='#76D176'>";
echo "<td align='center' class='Titulo'><b>Código</b>&nbsp;&nbsp;&nbsp;<div id='lista_basica' style='display:inline;'></div></td>";
echo "<td align='center' class='Titulo'><b>Descrição</b></td>";
echo "<td align='center' class='Titulo'><b>Qtde</b></td>";
echo "<td align='center' class='Titulo'><b>Defeito</b></td>";
echo "<td align='center' class='Titulo'><b>Serviço</b></td>";
echo "</tr>";

$loop = $qtde_item;

$offset = 0;
for ($i = 0 ; $i < $loop ; $i++) {

	echo "<tr>";

	
	echo "<input type='hidden' name='descricao'>";
	echo "<input type='hidden' name='preco'>";

	echo "<td align='center'>";
	echo "<input type='hidden' name='orcamento_item_$i' value='$orcamento_item[$i]'>";
	echo "<input class='Caixa' type='text' name='peca_$i' size='15' value='$peca[$i]' > &nbsp;";
	echo "<img src='imagens/btn_lupa_novo.gif' border='0' align='absmiddle' onclick='javascript:fnc_pesquisa_peca_lista (document.frm_os.produto.value , document.frm_os.peca_$i, document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.produto_voltagem, \"referencia\" )' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'>";
	echo "</td>";
	
	echo "<td align='center'><input class='Caixa' type='text' name='descricao_$i' size='25' value='$descricao[$i]' > &nbsp;";
	echo "<img src='imagens/btn_lupa_novo.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto.value , document.frm_os.peca_$i, document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.produto_voltagem, \"descricao\" )' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'>";
	echo "</td>";

	echo "<td align='center'><input class='Caixa' type='text' name='qtde_$i' size='5' value='$qtde[$i]' > &nbsp;";
	echo "</td>";

	//--===== Defeito do Item ===============================================================================
	echo "<td align='center'>";
	echo "<select class='Caixa' size='1' name='defeito_$i'>";
	echo "<option value=''></option>";

	$sql = "SELECT *
		FROM   tbl_defeito
		WHERE  tbl_defeito.fabrica = $login_fabrica
		AND    tbl_defeito.ativo IS TRUE
		ORDER BY descricao;";
	$res = pg_exec ($con,$sql) ;
	
	for ($x = 0 ; $x < pg_numrows ($res) ; $x++ ) {
		echo "<option ";
		if ($defeito[$i] == pg_result ($res,$x,defeito)) echo " SELECTED ";
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
	//--===== FIM - Defeito da Peça =========================================================================

	//--===== Serviço Realizado =============================================================================
	echo "<td align='center'>";
	echo "<select class='Caixa' size='1' name='servico_$i' style='width:150px'>";
	echo "<option value=''></option>";

	$sql = "SELECT *
		FROM   tbl_servico_realizado
		WHERE  tbl_servico_realizado.fabrica = $login_fabrica 
		AND tbl_servico_realizado.linha IS NULL
		AND tbl_servico_realizado.ativo IS TRUE
		ORDER BY gera_pedido DESC, descricao ASC;";

	$res = pg_exec($con,$sql) ;

	for ($x = 0 ; $x < pg_numrows($res) ; $x++ ){
		echo "<option ";
		if ($servico[$i] == pg_result ($res,$x,servico_realizado)) echo " SELECTED ";
		echo " value='" . pg_result ($res,$x,servico_realizado) . "'>" ;
		echo pg_result ($res,$x,descricao) ;
		echo "</option>";

	}
	
	echo "</select>";
	echo "</td>";
	//--===== FIM - Serviço Realizado =======================================================================

	echo "</tr>";
	
	$offset = $offset + 1;
}
echo "</table>";
//--===== FIM - Lançamento de Peças =====================================================================

?>
	</td>
</tr>

<tr>
	<td><img height="2" width="16" src="imagens/spacer.gif"></td></tr>
<tr>
	<td valign="top" align="left">

<?
echo "<table style=' border:#76D176 1px solid; background-color: #EFFAEF' align='center' width='750' border='0'>";
echo "<tr height='20' bgcolor='#76D176'>";
echo "<td class='Titulo' colspan='4'>Mão de Obra</td>";
echo "</tr>";
echo "<tr height='20' >";
echo "<td colspan='4'>";

echo "<table><tr>";
echo "<td class='Label'>Descricao</td>";
echo "<td class='Label' width='150'><input name='mao_de_obra_descricao' id='mao_de_obra_descricao' type='text' class='Caixa'></td>";
echo "<td class='Label'>Valor</td>";
echo "<td class='Label' width='150'><input name='mao_de_obra_descricao' id='mao_de_obra_valor' type='text' class='Caixa'></td>";
echo "<td class='Label'><input name='gravar_mao_de_obra' id='gravar_mao_de_obra' type='button' value='Adicionar'></td>";
echo "</tr></table>";

echo "</td>";
echo "</tr>";
echo "<tr height='20' bgcolor='#76D176'>";
echo "<td align='center' class='Titulo'><b>#</b></td>";
echo "<td align='center' class='Titulo'><b>Descrição</b></td>";
echo "<td align='center' class='Titulo'><b>Valor</b></td>";
echo "<td align='center' class='Titulo'><b>Ação</b></td>";
echo "</tr>";
echo "</table>";
?>
<tr>
	<td><img height="2" width="16" src="imagens/spacer.gif"></td></tr>
<tr>
	<td valign="top" align="left">
<?

//--===== Data Fechamento da OS =========================================================================
echo "<table style=' border:#B63434 1px solid; background-color: #EED5D2' align='center' width='750' border='0'height='40'>";
echo "<tr>";
echo "<td valign='middle' align='LEFT' class='Label' >";
echo "<INPUT TYPE='checkbox' NAME='aprovado' value='t'> APROVADO</td>";
echo "<td width='50' valign='middle' align='LEFT'><input type='button' name='btn_acao' value='Gravar' onClick=\"if (this.value=='Gravando...'){ alert('Aguarde');}else {this.value='Gravando...'; gravar_os(this.form);}\" style=\"width: 150px;\"></td>";
echo "<td width='300'><div id='saida' style='display:inline;'></div></td>";
echo "</tr>";
echo "</table>";
//--=====================================================================================================

?>
	</td>
</tr>
</table>
</form>
<div id='erro' style='visibility:hidden;opacity:.85;' class='Erro'></div>


<?
 //include "rodape.php";
 ?>
