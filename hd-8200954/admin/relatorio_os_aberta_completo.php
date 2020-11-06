<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="auditoria";

$gera_automatico = trim($_GET["gera_automatico"]);

if ($gera_automatico != 'automatico'){
	include "autentica_admin.php";
}
include "gera_relatorio_pararelo_include.php";

include 'funcoes.php';


$layout_menu = "auditoria";
$title       = "RELATÓRIO DE OS DIGITADAS";
include "cabecalho.php";
include "javascript_pesquisas.php";

/*
HD 13693 - Raphael Giovanini

SELECT tbL_os.os 
INTO TABLE temp_os_aberta_excluir
FROM tbl_os 
WHERE tbl_os.fabrica = 3
AND   tbl_os.finalizada IS NULL 
AND   tbl_os.excluida   IS NOT TRUE 
AND   tbl_os.posto <> 6359
AND   data_abertura < '2008-02-15'::date - INTERVAL '90 days';

CREATE INDEX temp_os_aberta_excluir_os ON temp_os_aberta_excluir(os);

*/
?>

<script language="JavaScript" src="js/cal2.js"></script>
<script language="JavaScript" src="js/cal_conf2.js"></script>

<script language="JavaScript">
function fnc_pesquisa_posto2 (campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&proximo=";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}

	else
		alert("Digite toda ou parte da informação para realizar a pesquisa!");
}

function fnc_pesquisa_produto (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}


	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.focus();
	}

	else
		alert("Digite toda ou parte da informação para realizar a pesquisa!");
}



</script>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.divisao{
	width:600px;
	text-align:center;
	margin:0 auto;
	font-size:10px;
	background-color:#FEFCCF;
	border:1px solid #928A03;
	padding:5px;
}
.sucesso{
	width:500px;
	text-align:left;
	margin:0 auto;
	font-size:10px;
	background-color:#E3FBE4;
	border:1px solid #0F6A13;
	color:#07340A;
	padding:5px;
	font-size:13px;
}


.menu_ajuda{
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#332D00;
	background-color: #FFF9CA;
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
}

.subtitulo{

color: #7092BE
}

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}
</style>


<p>

<?

if (strlen($_POST["btn_acao"]) > 0 ) $btn_acao = trim($_POST["btn_acao"]);
if (strlen($_GET["btn_acao"]) > 0 )  $btn_acao = trim($_GET["btn_acao"]);


if(strlen($btn_acao) > 0){

	if (strlen($_POST["posto_codigo"]) > 0 ) $posto_codigo = trim($_POST["posto_codigo"]);
	if (strlen($_GET["posto_codigo"]) > 0 )  $posto_codigo = trim($_GET["posto_codigo"]);

	if (strlen($_POST["posto_nome"]) > 0 ) $posto_nome = trim($_POST["posto_nome"]);
	if (strlen($_GET["posto_nome"]) > 0 )  $posto_nome = trim($_GET["posto_nome"]);

	if (strlen($_POST["ano"]) > 0 ) $ano = trim($_POST["ano"]);
	if (strlen($_GET["ano"]) > 0 )  $ano = trim($_GET["ano"]);

	if (strlen($_POST["mes"]) > 0 ) $mes = trim($_POST["mes"]);
	if (strlen($_GET["mes"]) > 0 )  $mes = trim($_GET["mes"]);
	
	if (strlen($_POST["produto_referencia"]) > 0 ) $produto_referencia = trim($_POST["produto_referencia"]);
	if (strlen($_GET["produto_referencia"]) > 0 )  $produto_referencia = trim($_GET["produto_referencia"]);
	
	if (strlen($_POST["tipo_atendimento"]) > 0 ) $tipo_atendimento = trim($_POST["tipo_atendimento"]);
	if (strlen($_GET["tipo_atendimento"]) > 0 )  $tipo_atendimento = trim($_GET["tipo_atendimento"]);
	
	if (strlen($_POST["pais"]) > 0 ) $pais = trim($_POST["pais"]);
	if (strlen($_GET["pais"]) > 0 )  $pais = trim($_GET["pais"]);

	if (strlen($posto_codigo) == 0 AND strlen($posto_nome) == 0 ) {
		$msg_erro .= "É obrigatório o preenchimento do posto<br>";
	}
	if(strlen($qtde_dias) == 0)                                   {
		$msg_erro .= "É obrigatório o preencimento da quantidade de dias<br>";
	}
	if ($qtde_dias<10)                                            {
		$msg_erro .= "No mínimo 10 dias.";
	}
}
?>


<? 
if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {
	include "gera_relatorio_pararelo.php";
}

if ($gera_automatico != 'automatico' and strlen($msg_erro)==0){
	include "gera_relatorio_pararelo_verifica.php";
}
?>


<form name='frm_os_posto' action='<? echo $PHP_SELF ?>' method="POST">
<input type="hidden" name='btn_acao' value="">

<table width='700' class='formulario' border='0' cellpadding='5' cellspacing='1' align='center'>
	<? if(strlen($msg_erro) > 0){ ?>
		<tr class='msg_erro'>
			<td><?echo $msg_erro?></td>
		</tr>
	<? } ?>
	<tr class='titulo_tabela'>
		<td>Parâmetros de Pesquisa </td>
	</tr>
	
	<tr>
		<td>
			<table width='500' align='center' border='0' cellspacing='2' cellpadding='2'>
			<tr>
				<td align='right'>Código do Posto</td>
				<td align='left'>
					<input class="frm" type="text" name="posto_codigo" size="15" value="<? echo $posto_codigo ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm_os_posto.posto_codigo,document.frm_os_posto.posto_nome,'codigo')" <? } ?>>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_os_posto.posto_codigo,document.frm_os_posto.posto_nome,'codigo')">
				</td>
			</tr>
			<tr>
				<td align='right'>Nome do Posto</td>
				<td align='left'>
					<input class="frm" type="text" name="posto_nome" size="45" value="<? echo $posto_nome ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm_os_posto.posto_codigo,document.frm_os_posto.posto_nome,'nome')" <? } ?>>&nbsp;<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_os_posto.posto_codigo,document.frm_os_posto.posto_nome,'nome')" style="cursor:pointer;">
				</td>

			</tr>
			<tr>
				<td align='right'>N° Dias em Aberto</td>
				<td align='left'>
					<input class="frm" type="text" name="qtde_dias" size="15" maxlength="4" value="<? echo $qtde_dias ?>">
				</td>
			</tr>
			<tr>

				<td>
				</td>
			</tr>
			<?if($login_fabrica == 20 ){
// MLG 2009-08-04 HD 136625
    $sql = 'SELECT pais,nome FROM tbl_pais';
    $res = pg_query($con,$sql);
    $p_tot = pg_num_rows($res);
    for ($i; $i<$p_tot; $i++) {
        list($p_code,$p_nome) = pg_fetch_row($res, $i);
    	$sel_paises .= "\t\t\t\t<option value='$p_code'";
        $sel_paises .= ($pais==$p_code)?" selected":"";
        $sel_paises .= ">$p_nome</option>\n";
    }
?>
            <tr>
				<td align='left'>País</td>
				<td align='right'>
        			<select name='pais' size='1' class='frm'>
            			 <option></option>
                        <?echo $sel_paises;?>
        			</select>
				</td>

			</tr>
			<tr>
				<td align='left'>Tipo Atendimento</td>
				<td align='right'>
						<select name="tipo_atendimento" size="1" class="frm">
						<option <? if (strlen ($tipo_atendimento) == 0) echo " selected " ?> ></option>
						<?
						$sql = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica ORDER BY tipo_atendimento";
						$res = pg_query ($con,$sql) ;

						for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
							echo "<option ";
							if ($tipo_atendimento == pg_fetch_result ($res,$i,tipo_atendimento) ) echo " selected ";
							echo " value='" . pg_fetch_result ($res,$i,tipo_atendimento) . "'" ;
							echo " > ";
							echo pg_fetch_result ($res,$i,codigo) . " - " . pg_fetch_result ($res,$i,descricao) ;
							echo "</option>\n";
						}
						?>
					</select>
				</td>
			</tr>

			<?}?>
			<tr>
				<td align='right'>Referência</td>
				<td align='left'><input type="text" name="produto_referencia" value="<? echo $produto_referencia ?>" size="15" maxlength="20" class='frm'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_os_posto.produto_referencia,document.frm_os_posto.produto_descricao,'referencia')" alt='Clique aqui para pesquisar pela referência do produto' style='cursor:pointer;'></td>
			</tr>
			<tr>
				<td align='right'>Descrição</td>
				<td align='left'><input type="text" name="produto_descricao" value="<? echo $produto_descricao ?>" size="45" maxlength="50" class='frm'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_os_posto.produto_referencia,document.frm_os_posto.produto_descricao,'descricao')" alt='Clique aqui para pesquisar pela descrição do produto' style='cursor:pointer;'></td>
			</tr>
			</table>
		</td>
	</tr>

	<tr><td colspan="2">&nbsp;</td></tr>
	<tr>
		<td colspan="2" align="center">
				<input type="button" value="&nbsp;" style="background:url(imagens_admin/btn_pesquisar_400.gif); width:400px;cursor:pointer;" onclick="javascript: if (document.frm_os_posto.btn_acao.value == '' ) { document.frm_os_posto.btn_acao.value='continuar' ; document.frm_os_posto.submit() } else { alert ('Aguarde submissão') }" ALT="Confirmar" border='0'>
		</td>
	</tr>
</table>

<br>



</form>

<br>

<?

if (strlen($btn_acao) > 0 AND strlen($msg_erro) == 0){

	if (strlen($posto_codigo) > 0){
		$sqlPosto =	"SELECT posto
					FROM tbl_posto_fabrica
					WHERE codigo_posto = '$posto_codigo'
					AND fabrica = $login_fabrica";
		$res = pg_query($con,$sqlPosto);
		if (pg_num_rows($res) == 1){
			$posto = pg_fetch_result($res,0,0);
		}
	}
	$sql = "SELECT tbL_os.os 
			INTO TEMP TABLE temp_os_aberta 
			FROM tbl_os 
			$cond_estado 
			WHERE tbl_os.fabrica = $login_fabrica 
			AND   tbl_os.finalizada IS NULL 
			AND   tbl_os.excluida   IS NOT TRUE 
			AND   tbl_os.posto <> 6359
			AND   tbl_os.posto = $posto
			AND   data_abertura < current_date - INTERVAL '$qtde_dias days';

			CREATE INDEX temp_os_aberta_os ON temp_os_aberta(os);
			
			SELECT  tbl_os.os                                                               ,
					tbl_os.sua_os                                                           ,
					tbl_os.consumidor_nome                                                  ,
					tbl_os.consumidor_fone                                                  ,
					tbl_os.serie                                                            ,
					tbl_os.pecas                                                            ,
					tbl_os.mao_de_obra                                                      ,
					tbl_os.nota_fiscal                                                      ,
					to_char (tbl_os.data_digitacao,'DD/MM/YYYY')      AS data_digitacao     ,
					to_char (tbl_os.data_abertura,'DD/MM/YYYY')       AS data_abertura      ,
					to_char (tbl_os.data_fechamento,'DD/MM/YYYY')     AS data_fechamento    ,
					to_char (tbl_os.finalizada,'DD/MM/YYYY')          AS data_finalizada    ,
					to_char (tbl_os.data_nf,'DD/MM/YYYY')             AS data_nf            ,
					data_abertura::date - data_nf::date               AS dias_uso           ,
					tbl_produto.referencia                            AS produto_referencia ,
					tbl_produto.descricao                             AS produto_descricao  ,
					tbl_peca.peca                                                           ,
					tbl_peca.referencia                               AS peca_referencia    ,
					tbl_peca.descricao                                AS peca_descricao     ,
					tbl_servico_realizado.descricao                   AS servico            ,
					tbl_defeito_constatado.descricao                  AS defeito_constatado ,
					TO_CHAR (tbl_os_item.digitacao_item,'DD/MM')      AS data_digitacao_item,
					tbl_os_item.pedido                                                      ,
					tbl_posto.posto                                                         ,
					tbl_posto_fabrica.codigo_posto                                          ,
					tbl_posto.nome AS nome_posto                                            ,
					tbl_posto.pais AS posto_pais                                            ,
					tbl_tipo_atendimento.codigo                       AS ta_codigo          ,
					tbl_tipo_atendimento.descricao                    AS ta_descricao
			FROM      tbl_os
			JOIN      temp_os_aberta          ON  tbl_os.os                     = temp_os_aberta.os
			JOIN      tbl_produto             ON  tbl_os.produto               = tbl_produto.produto
			JOIN      tbl_posto               ON  tbl_os.posto                 = tbl_posto.posto
			JOIN      tbl_posto_fabrica       ON  tbl_posto.posto              = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_os_produto          ON  tbl_os.os                    = tbl_os_produto.os
			LEFT JOIN tbl_os_item             ON  tbl_os_produto.os_produto    = tbl_os_item.os_produto
			LEFT JOIN tbl_peca                ON  tbl_os_item.peca             = tbl_peca.peca
			LEFT JOIN tbl_defeito_constatado  ON  tbl_os.defeito_constatado    = tbl_defeito_constatado.defeito_constatado
			LEFT JOIN tbl_servico_realizado   ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
			LEFT JOIN tbl_tipo_atendimento    USING(tipo_atendimento)
			WHERE tbl_os.fabrica = $login_fabrica
			AND   tbl_os.posto   = $posto";

	if (strlen($posto) > 0)             $sql .= " AND tbl_os.posto = $posto ";
	if (strlen($uf) > 0)                $sql .= " AND tbl_posto.estado = '$uf' ";
	if (strlen($produto_ref) > 0)       $sql .= " AND tbl_produto.referencia = '$produto_ref' " ;
	if (strlen($pais) > 0)              $sql .= " AND tbl_posto.pais = '$pais' " ;
	if (strlen($tipo_atendimento) > 0)  $sql .= " AND tbl_os.tipo_atendimento = '$tipo_atendimento' " ;
	$sql .= " ORDER BY tbl_os.sua_os;";
#echo nl2br($sql);
#exit;
	$res = pg_query($con,$sql);
	$numero_registros = pg_num_rows($res);


	$conteudo = "";
	$data = date("Y-m-d").".".date("H-i-s");

	$arquivo_nome     = "relatorio-os-aberta-$login_fabrica.$login_admin.xls";
	$path             = "/www/assist/www/admin/xls/";
	$path_tmp         = "/tmp/";

	$arquivo_completo     = $path.$arquivo_nome;
	$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

	$fp = fopen ($arquivo_completo_tmp,"w");

	fputs ($fp,"<html>");
	fputs ($fp,"<body>");

	echo "<p id='id_download' style='display:none'><a href='xls/$arquivo_nome'>Fazer download do arquivo em  XLS </a></p><br>";

	if (pg_num_rows($res) > 0) {

		if($login_fabrica==20){
			for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
				$sua_os      = pg_fetch_result($res,$i,sua_os);
				$pecas       = pg_fetch_result($res,$i,pecas);
				$mao_de_obra = pg_fetch_result($res,$i,mao_de_obra);
				$vet_pecas[$sua_os]= $pecas;
				$vet_mao_de_obra[$sua_os]= $mao_de_obra;
			}
		}
		$conteudo .=  "<table width='700' align='center' class='tabela' cellspacing='1' cellpadding='2'>";
		$conteudo .=  "<tr class='titulo_coluna'>";
		$conteudo .=  "<td nowrap>OS</td>";
		if($login_fabrica==20) $conteudo .=  "<td nowrap>Tipo Atendimento</td>";
		$conteudo .=  "<td nowrap>Consumidor</td>";
		$conteudo .=  "<td nowrap>Telefone</td>";
		$conteudo .=  "<td nowrap>Nº Série</td>";
		$conteudo .=  "<td nowrap>Digitação</td>";
		$conteudo .=  "<td nowrap>Abertura</td>";
		$conteudo .=  "<td nowrap>Fechamento</td>";
		$conteudo .=  "<td nowrap>Finalizada</td>";
		$conteudo .=  "<td nowrap>NF Compra</td>";
		$conteudo .=  "<td nowrap>Data NF</td>";
		$conteudo .=  "<td nowrap>Dias em Uso</td>";
		$conteudo .=  "<td nowrap>Produto Referência</td>";
		$conteudo .=  "<td nowrap>Produto Descrição</td>";
		$conteudo .=  "<td nowrap>Peça Referência</td>";
		$conteudo .=  "<td nowrap>Peça Descrição</td>";
		if($login_fabrica == 3 ){
			$conteudo .=  "<td nowrap>NF Envio</td>";
			$conteudo .=  "<td nowrap>Emissão NF</td>";
		}

		if($login_fabrica == 20 ){
			$conteudo .=  "<td nowrap>Valor Total MO</td>";
			$conteudo .=  "<td nowrap>Valor Total Peças</td>";
		}
		$conteudo .=  "<td nowrap>Data Item</TD>";
		$conteudo .=  "<td nowrap>Defeito Constatado</td>";
		$conteudo .=  "<td nowrap>Serviço Realizado</td>";
		$conteudo .=  "<td nowrap>Código Posto</td>";
		$conteudo .=  "<td nowrap>Razão Social</td>";
		if($login_fabrica == 20) $conteudo .=  "<td nowrap>País</td>";
		$conteudo .=  "</tr>";

		echo $conteudo;
		fputs ($fp,$conteudo);

		for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
			$os                 = pg_fetch_result($res,$i,os);
			$sua_os             = pg_fetch_result($res,$i,sua_os);
			$consumidor_nome    = pg_fetch_result($res,$i,consumidor_nome);
			$consumidor_fone    = pg_fetch_result($res,$i,consumidor_fone);
			$serie              = pg_fetch_result($res,$i,serie);
			$nota_fiscal        = pg_fetch_result($res,$i,nota_fiscal);
			$data_digitacao     = pg_fetch_result($res,$i,data_digitacao);
			$data_abertura      = pg_fetch_result($res,$i,data_abertura);
			$data_fechamento    = pg_fetch_result($res,$i,data_fechamento);
			$data_finalizada    = pg_fetch_result($res,$i,data_finalizada);
			$data_nf            = pg_fetch_result($res,$i,data_nf);
			$dias_uso           = pg_fetch_result($res,$i,dias_uso);
			$produto_referencia = pg_fetch_result($res,$i,produto_referencia);
			$produto_descricao  = pg_fetch_result($res,$i,produto_descricao);
			$peca_referencia    = pg_fetch_result($res,$i,peca_referencia);
			$peca_descricao     = pg_fetch_result($res,$i,peca_descricao);
			$servico            = pg_fetch_result($res,$i,servico);
			$posto              = pg_fetch_result($res,$i,posto);
			$codigo_posto       = pg_fetch_result($res,$i,codigo_posto);
			$nome_posto         = pg_fetch_result($res,$i,nome_posto);
			$defeito_constatado	= pg_fetch_result($res,$i,defeito_constatado);
			$data_digitacao_item= pg_fetch_result($res,$i,data_digitacao_item);
			$pedido             = pg_fetch_result($res,$i,pedido);
			$peca               = pg_fetch_result($res,$i,peca);

			$posto_pais         = pg_fetch_result($res,$i,posto_pais);
			$ta_codigo          = pg_fetch_result($res,$i,ta_codigo);
			$ta_descricao       = pg_fetch_result($res,$i,ta_descricao);

			if ($i % 2 == 0) $cor = '#F1F4FA';
			else             $cor = '#F7F5F0';

			if ($login_fabrica == 1) $sua_os = $codigo_posto.$sua_os;

			$fat_emissao = "";
			$fat_nf      = "";
			if(strlen($pedido)>0 AND strlen($peca)>0){
				$sql2 = "SELECT TO_CHAR(emissao,'dd/mm/YYYY') AS emissao,
								nota_fiscal
						FROM tbl_faturamento_item
						JOIN (
						SELECT faturamento,emissao,nota_fiscal
						FROM tbl_faturamento
						WHERE tbl_faturamento.posto = $posto
						AND tbl_faturamento.fabrica = 3
						AND tbl_faturamento.conferencia IS NULL
						AND tbl_faturamento.cancelada  IS NULL
						AND tbl_faturamento.distribuidor IS NULL
						) fat ON tbl_faturamento_item.faturamento = fat.faturamento
						WHERE tbl_faturamento_item.peca   = $peca
						AND   tbl_faturamento_item.pedido = $pedido
						AND   tbl_faturamento_item.os     = $os
						;";
				$res2 = pg_query($con,$sql2);
				if(pg_num_rows($res2)>0){
					$fat_emissao = pg_fetch_result($res2,0,0);
					$fat_nf      = pg_fetch_result($res2,0,1);
				}else{
					$fat_emissao = "Pendente";
					$fat_nf      = "Pendente";
				}
			}
			$conteudo = "";

			$conteudo .=  "<tr class='Conteudo' bgcolor='$cor'>";
			$conteudo .=  "<td nowrap align='center'><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></td>";
			if($login_fabrica == 20) $conteudo .=  "<td nowrap align='left'>$ta_codigo - $ta_descricao</td>";
			if ($ant_consumidor_nome == $consumidor_nome) $conteudo .=  "<td>&nbsp;</td>";
			else $conteudo .=  "<td nowrap align='left'>$consumidor_nome</td>";
			if ($ant_consumidor_fone == $consumidor_fone) $conteudo .=  "<td>&nbsp;</td>";
			else $conteudo .=  "<td nowrap align='center'>$consumidor_fone</td>";
			if ($ant_serie == $serie) $conteudo .=  "<td>&nbsp;</td>";
			else $conteudo .=  "<td nowrap align='center'>$serie</td>";
			if ($ant_data_digitacao == $data_digitacao) $conteudo .=  "<td>&nbsp;</td>";
			else $conteudo .=  "<td nowrap align='center'>$data_digitacao</td>";
			if ($ant_data_abertura == $data_abertura) $conteudo .=  "<td>&nbsp;</td>";
			else $conteudo .=  "<td nowrap align='center'>$data_abertura</td>";
			if ($ant_data_fechamento == $data_fechamento) $conteudo .=  "<td>&nbsp;</td>";
			else $conteudo .=  "<td nowrap align='center'>$data_fechamento</td>";
			if ($ant_data_finalizada == $data_finalizada) $conteudo .=  "<td>&nbsp;</td>";
			else $conteudo .=  "<td nowrap align='center'>$data_finalizada</td>";
			if ($ant_nota_fiscal == $nota_fiscal) $conteudo .=  "<td>&nbsp;</td>";
			else $conteudo .=  "<td nowrap align='center'>$nota_fiscal</td>";
			if ($ant_data_nf == $data_nf) $conteudo .=  "<td>&nbsp;</td>";
			else $conteudo .=  "<td nowrap align='center'>$data_nf</td>";
			if ($ant_dias_uso == $dias_uso) $conteudo .=  "<td>&nbsp;</td>";
			else $conteudo .=  "<td nowrap align='center'>$dias_uso</td>";
			if ($ant_produto_referencia == $produto_referencia) $conteudo .=  "<td>&nbsp;</td>";
			else $conteudo .=  "<td nowrap align='center'>$produto_referencia</td>";
			if ($ant_produto_descricao == $produto_descricao) $conteudo .=  "<td>&nbsp;</td>";
			else $conteudo .=  "<td nowrap align='left'>$produto_descricao</td>";
			$conteudo .=  "<td nowrap align='center'>$peca_referencia</td>";
			$conteudo .=  "<td nowrap align='left'>$peca_descricao</td>";
			if($login_fabrica == 3){
				$conteudo .=  "<td nowrap align='center'>$fat_nf</td>";
				$conteudo .=  "<td nowrap align='center'>$fat_emissao</td>";
			}
			if($login_fabrica == 20 ){
				$conteudo .=  "<td nowrap align='right'>".number_format($vet_pecas[$sua_os],2,',','.')."</td>";
				$conteudo .=  "<td nowrap align='right'>".number_format($vet_mao_de_obra[$sua_os],2,',','.')."</td>";
				//DEPOIS DE IMPRIMIR, APAGA O VALOR PARA NÃO DUPLICAR QUANTO TIVER VARIAS OS_ITEM
				$vet_pecas[$sua_os]= "";
				$vet_mao_de_obra[$sua_os]= "";

			}

			$conteudo .=  "<td nowrap align='center'>$data_digitacao_item</td>";
			$conteudo .=  "<td nowrap align='left'>$defeito_constatado</td>";
			$conteudo .=  "<td nowrap align='left'>$servico</td>";
			$conteudo .=  "<td nowrap align='center'>$codigo_posto</td>";
			$conteudo .=  "<td nowrap align='left'>$nome_posto</td>";
			if($login_fabrica == 20) $conteudo .=  "<td nowrap align='left'>$posto_pais</td>";
			$conteudo .= "</tr>";

			echo $conteudo;
			fputs ($fp,$conteudo);
		}
		
		$conteudo  = "<tr>";
		$conteudo  = "<td nowrap align='left'>";
		$conteudo  = "</table>";
		$conteudo .= "<BR><CENTER>".$numero_registros." Registros encontrados</CENTER>";
		echo $conteudo;

		fputs ($fp,$conteudo);
		fputs ($fp,"</body>");
		fputs ($fp,"</html>");
		fclose ($fp);
		flush();
		echo ` cp $arquivo_completo_tmp $path `;

		echo "<script language='javascript'>";
		echo "document.getElementById('id_download').style.display='block';";
		echo "</script>";
		flush();
		echo "<br>";
	}else {
		echo "<p>Nenhum registro encontrado!</p>";
	}
}

echo "<br>";

include "rodape.php";
?>
