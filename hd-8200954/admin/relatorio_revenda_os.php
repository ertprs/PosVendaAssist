<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include 'autentica_admin.php';



$layout_menu = "gerencia";
$title = "RELATÓRIO REVENDA X PRODUTO";

$btn_acao = $_POST['acao'];

if (strlen($btn_acao)>0){
    $data_inicial = $_REQUEST["data_inicial"];
    $data_final = $_REQUEST["data_final"];
	
	if(empty($data_inicial) OR empty($data_final)){
        $msg_erro = "Data Inválida";
    }
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

    if(strlen($msg_erro)==0){
        $aux_data_inicial = "$yi-$mi-$di";
        $aux_data_final = "$yf-$mf-$df";
    }
    if(strlen($msg_erro)==0){
        if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
            $msg_erro = "Data Inválida.";
        }
    }
	
	
	
	
	$cond_1 = " 1 = 1 ";
	$estado = $_POST['estado'];
	if(strlen($estado)>0){
		$cond_1 = " tbl_cidade.estado = '$estado' ";
	
	}

	
	
	$produto_referencia = trim($_POST['produto_referencia']); // HD 2003 TAKASHI
	$produto_descricao  = trim($_POST['produto_descricao']) ;// HD 2003 TAKASHI
	$cond_3 = " 1 = 1 ";
	if(strlen($produto_referencia)>0 and strlen($produto_descricao)>0){ // HD 2003 TAKASHI
		$sql = "SELECT produto 
				from tbl_produto 
				join tbl_familia using(familia)
				where tbl_familia.fabrica = $login_fabrica
				and tbl_produto.referencia = '$produto_referencia'";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$produto = pg_result($res,0,produto);
			$cond_3 = "tbl_os.produto          = $produto";
		}

	}else{
		$msg_erro = "Favor informar o produto";
	}


	$cnpj = $_POST['cnpj'];
	$nome_revenda = $_POST['nome'];
	$cond_2 = " 1=1 ";
	if (strlen($cnpj) > 0){
		$xcnpj = str_replace (".","",$cnpj);
		$xcnpj = str_replace ("-","",$xcnpj);
		$xcnpj = str_replace ("/","",$xcnpj);
		$xcnpj = str_replace (" ","",$xcnpj);
		$sql = "SELECT revenda from tbl_revenda where cnpj='$xcnpj'";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$revenda = pg_result($res,0,0);
			$cond_2 = " tbl_os.revenda = $revenda ";
		}
	}
}

include "cabecalho.php";

?>
<style>
.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
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

</style>
<script language="JavaScript">

function fnc_revenda_pesquisa (campo, campo2, tipo) {
	if (tipo == "nome" ) {
		var xcampo = campo;
	}

	if (tipo == "cnpj" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "revenda_pesquisa.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.nome	= campo;
		janela.cnpj	= campo2;
		janela.focus();
	} else{
        alert("Preencha toda ou parte da informação para realizar a pesquisa!");
    }
}

</script>



<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language='javascript' src='../ajax.js'></script>


<? include "javascript_pesquisas.php" ?>
<?php include "javascript_calendario.php"?>

<script>
$().ready(function(){
    $( "#data_inicial" ).datePicker({startDate : "01/01/2000"});
    $( "#data_inicial" ).maskedinput("99/99/9999");
    $( "#data_final" ).datePicker({startDate : "01/01/2000"});
    $( "#data_final" ).maskedinput("99/99/9999");
});
</script>

<? if (strlen($msg_erro)>0){?>
<table width='700' class='msg_erro' border='0' cellpadding='1' cellspacing='1' align='center'>
<tr>
	<td><? echo $msg_erro; ?></td>
</tr>
</table>
<?}?>

<FORM name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">


<table width='700' class='formulario' border='0' cellpadding='1' cellspacing='1' align='center'>
	
	<tr>
		<td class='titulo_tabela'>Parâmetros de Pesquisa</td>
	</tr>
	
	<tr>
		<td>
	
			<table width='100%' border='0' cellspacing='0' cellpadding='0' class='formulario'>
				<tr> <td>&nbsp;</td> </tr>
				
				<tr>
					<td width="15%">&nbsp;</td>
					<td width='35%'>
						Data Inicial<br>
						<INPUT size="12" maxlength="10" TYPE="text" class='frm' NAME="data_inicial" id="data_inicial" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>">
					</td>
					<td width='35%'>
						Data Final<br>
						<INPUT size="12" maxlength="10" TYPE="text" class='frm' NAME="data_final" id="data_final" value="<? if (strlen($data_final) > 0) echo $data_final; ?>">
					</td>
					<td width="15%">&nbsp;</td>
				</tr>
			</table>

		</td>
	</tr>


	<tr>
		<td  width='100%'>
			<table width='100%' border='0' cellspacing='0' cellpadding='0' class='formulario'>
				<tr>
					<TD style="width: 15%">&nbsp;</TD>
				
					<td width='35%'> Ref. Produto <br>
						<input type="text" name="produto_referencia" size="12" class='frm' maxlength="20" value="<? echo $produto_referencia ?>" > 
						<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao,'referencia')">
					</td>
					
					<td width='35%'> Descrição<br>
						<input type="text" name="produto_descricao" style="width:200px" maxlength='60' size="20" class='frm' value="<? echo $produto_descricao ?>" >
						<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao,'descricao')">
					<TD style="width: 15%">&nbsp;</TD>
				</tr>
			</table>
		</td>
	</tr>

	
	<tr>
		<td  width='100%'>
			<table width='100%' border='0' cellspacing='0' cellpadding='0' class='formulario'>	
				<tr>
					<td width="15%">&nbsp;</td>
					
					<td width="35%">CNPJ<br>
						<input type="text" name="cnpj" size="12" maxlength="18" value="<? echo $cnpj ?>" class='frm'>&nbsp;<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_revenda_pesquisa (document.frm_relatorio.nome,document.frm_relatorio.cnpj,'cnpj')">
					</td>
					
					<td width="35%">Razão Social<br>
						<input type="text" name="nome" size="12" maxlength="60" value="<? echo $nome ?>" style="width:200px" class='frm'>&nbsp;<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_revenda_pesquisa (document.frm_relatorio.nome,document.frm_relatorio.cnpj,'nome')">
					</td>
					
					<td width="15%">&nbsp;</td>
				</tr>
			</table>
		</td>
	</tr>



	<tr>
		<td  width='100%'>
			<table width='100%' border='0' cellspacing='0' cellpadding='0' class='formulario'>	
				<tr>
					<td width="15%">&nbsp;</td>
					<td width="35%">Estado<br>
						<select name='estado' style='width:200px' class='frm'>
							<option value=""   <? if (strlen($estado) == 0)    echo " selected "; ?>>TODOS OS ESTADOS</option>
							<option value="AC" <? if ($estado == "AC") echo " selected "; ?>>AC - Acre</option>
							<option value="AL" <? if ($estado == "AL") echo " selected "; ?>>AL - Alagoas</option>
							<option value="AM" <? if ($estado == "AM") echo " selected "; ?>>AM - Amazonas</option>
							<option value="AP" <? if ($estado == "AP") echo " selected "; ?>>AP - Amapá</option>
							<option value="BA" <? if ($estado == "BA") echo " selected "; ?>>BA - Bahia</option>
							<option value="CE" <? if ($estado == "CE") echo " selected "; ?>>CE - Ceará</option>
							<option value="DF" <? if ($estado == "DF") echo " selected "; ?>>DF - Distrito Federal</option>
							<option value="ES" <? if ($estado == "ES") echo " selected "; ?>>ES - Espírito Santo</option>
							<option value="GO" <? if ($estado == "GO") echo " selected "; ?>>GO - Goiás</option>
							<option value="MA" <? if ($estado == "MA") echo " selected "; ?>>MA - Maranhão</option>
							<option value="MG" <? if ($estado == "MG") echo " selected "; ?>>MG - Minas Gerais</option>
							<option value="MS" <? if ($estado == "MS") echo " selected "; ?>>MS - Mato Grosso do Sul</option>
							<option value="MT" <? if ($estado == "MT") echo " selected "; ?>>MT - Mato Grosso</option>
							<option value="PA" <? if ($estado == "PA") echo " selected "; ?>>PA - Pará</option>
							<option value="PB" <? if ($estado == "PB") echo " selected "; ?>>PB - Paraíba</option>
							<option value="PE" <? if ($estado == "PE") echo " selected "; ?>>PE - Pernambuco</option>
							<option value="PI" <? if ($estado == "PI") echo " selected "; ?>>PI - Piauí</option>
							<option value="PR" <? if ($estado == "PR") echo " selected "; ?>>PR - Paraná</option>
							<option value="RJ" <? if ($estado == "RJ") echo " selected "; ?>>RJ - Rio de Janeiro</option>
							<option value="RN" <? if ($estado == "RN") echo " selected "; ?>>RN - Rio Grande do Norte</option>
							<option value="RO" <? if ($estado == "RO") echo " selected "; ?>>RO - Rondônia</option>
							<option value="RR" <? if ($estado == "RR") echo " selected "; ?>>RR - Roraima</option>
							<option value="RS" <? if ($estado == "RS") echo " selected "; ?>>RS - Rio Grande do Sul</option>
							<option value="SC" <? if ($estado == "SC") echo " selected "; ?>>SC - Santa Catarina</option>
							<option value="SE" <? if ($estado == "SE") echo " selected "; ?>>SE - Sergipe</option>
							<option value="SP" <? if ($estado == "SP") echo " selected "; ?>>SP - São Paulo</option>
							<option value="TO" <? if ($estado == "TO") echo " selected "; ?>>TO - Tocantins</option>
						</select>
					</td>
					
					
					<td width="35%">
					<br>
						<input type='button' value='Pesquisar' onclick="document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar">
						<input type='hidden' name='acao' value=''>
					</td>

				<td width="10">&nbsp;</td>
				</tr>
			</table>
		</td>
	</tr>
	</table>
</FORM>

<?



if(strlen($btn_acao)>0) {

	if(strlen($msg_erro)==0){
		$sql = "
			SELECT 
					tbl_produto.referencia,
					tbl_produto.descricao ,
					tbl_revenda.nome as revenda_nome,
					tbl_revenda.cnpj,
					tbl_cidade.nome,
					tbl_cidade.estado,
					count(tbl_os.os) as qtde
			from tbl_os
			join tbl_produto on tbl_os.produto = tbl_produto.produto
			JOIN tbl_revenda on tbl_os.revenda = tbl_revenda.revenda
			join tbl_cidade on tbl_cidade.cidade = tbl_revenda.cidade
			where tbl_os.fabrica = $login_fabrica
			AND tbl_os.consumidor_revenda = 'R'
			AND $cond_1
			AND $cond_3
			AND tbl_os.data_abertura between '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'
			AND tbl_os.revenda_cnpj notnull
			AND $cond_2
			AND tbl_os.excluida is not true
			group by 
				tbl_produto.referencia,
				tbl_produto.descricao ,
				tbl_revenda.nome,
				tbl_revenda.cnpj,
				tbl_cidade.nome,
				tbl_cidade.estado
				order by qtde desc;
		";
	// echo nl2br($sql); exit;

		
		$res = pg_exec($con,$sql);
		if (pg_numrows($res) > 0 && empty($msg_erro) ){
			$total = 0;
			?>	
			
			
			<br>
			<table border='0' width="700px" class='tabela' cellpadding='1' cellspacing='1'   align='center'>
				<tr class='titulo_tabela'>
					<td colspan='6'>
						Resultado de pesquisa entre data da abertura da OS <? echo " $data_inicial e $data_final";?>
					</td>
				</tr>
				<TR class='titulo_coluna'>
					<TD>Produto</TD>
					<TD>CNPJ</TD>
					<TD>Nome Revenda</TD>
					<TD>Cidade</TD>
					<TD>Estado</TD>
					<TD>Qtde</TD>
				</TR>
			
			<?
			for ($i=0; $i<pg_numrows($res); $i++){
				$produto_referencia  = trim(pg_result($res,$i,referencia)) ;
				$produto_descricao   = trim(pg_result($res,$i,descricao))     ;
				$revenda_nome        = trim(pg_result($res,$i,revenda_nome))     ;
				$cnpj                = trim(pg_result($res,$i,cnpj))     ;
				$cidade              = trim(pg_result($res,$i,nome))     ;
				$estado              = trim(pg_result($res,$i,estado))     ;
				$qtde                = trim(pg_result($res,$i,qtde));
				
				if($cor=="#F1F4FA")$cor = '#F7F5F0';
				else               $cor = '#F1F4FA';

				echo "<TR bgcolor='$cor'>";
				echo "<TD align='left'>$produto_referencia - $produto_descricao</TD>";
				echo "<TD  align='center'>$cnpj</TD>";
				echo "<TD  align='left'>$revenda_nome</TD>";
				echo "<TD  align='left'>$cidade</TD>";
				echo "<TD  align='center'>$estado</TD>";
				echo "<TD  align='center'>$qtde</TD>";

				echo "</TR>";
			}
			echo " </TABLE>";
			
			echo "<br>";
			echo "<br>";
				
		}else{
			echo  "<br>";
			
			echo "<b>Nenhum resultado encontrado entre $data_inicial e $data_final</b>";
		}
	}
}



?>


<? include "rodape.php" ?>
