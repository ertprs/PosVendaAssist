<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="cadastros,call_center";
include 'autentica_admin.php';
include '../helpdesk/mlg_funciones.php';

	$sql_pa = <<<LISTSQL
SELECT  /*	Cadastro	*/
		posto,codigo_posto,data AS "Última Atualização",fabrica AS "Fábrica",
		razao_social,tbl_posto_atualizacao.nome_fantasia,cnpj,ie,im,
		/*  Contato */
		fone1 AS "Telefone 1",fone2 AS "Telefone 2",fax,email,
		/*  Endereço    */
		endereco||', '||numero AS "Endereço",complemento,cep,bairro,cidade,estado,pais,
		/*  Dados bancários */
		tbl_posto_atualizacao.banco,tbl_posto_atualizacao.agencia,tbl_posto_atualizacao.conta,
		tbl_posto_atualizacao.favorecido,tbl_posto_atualizacao.favorecido_cnpj,
		tbl_posto_atualizacao.tipo_conta,
		/*  Atendimento */
		CASE WHEN tbl_posto_atualizacao.tipo_posto = 'C' THEN 'Atende Consumidor'
		     WHEN tbl_posto_atualizacao.tipo_posto = 'R' THEN 'Atende Revendas'
		     WHEN tbl_posto_atualizacao.tipo_posto = 'A' THEN 'Atende Consumidor e Revenda'
		     ELSE 'Erro no Cadastro'
		 END AS posto_atende,
		atende_revendas,atende_cidades,linhas,suframa
  FROM  tbl_posto_atualizacao
  JOIN  tbl_posto_fabrica USING(posto,fabrica)
 WHERE  fabrica = $login_fabrica
LISTSQL;

if ($_GET['acao']=='list') {
	//  Gerar arquivo EXCEL para download
	$res = pg_query($con, $sql_pa);
	if (is_resource($res)) {
		$hoje = date('Y-m-d');
		header('Content-type: application/msexcel');
		header("Content-Disposition: attachment; filename=dados_atualizados_postos_$hoje.xls");
		$row = pg_fetch_assoc($res, 0);
		$campos = array_keys($row);
		foreach($campos as $campo) {
			if ($campo == 'posto') continue;
			$campo = str_replace('_', ' ', $campo);
			$xls_header.= "\t\t<th>$campo</th>\n";
		}
		echo "<table>
	<thead>
	<tr>
	$xls_header
	</tr>
	</thead>
	<tbody>\n";
		$total = pg_num_rows($res);
		$link_posto = "<a href='http://posvenda.telecontrol.com.br/$PHP_SELF?acao=detalhe&posto=%s'>'%s</a>";

		for ($i=0; $i < $total; $i++) {
        	$row = pg_fetch_assoc($res, $i);
			$posto = array_shift($row); // Tira o ID do posto do array
			$linha = "\t\t<tr>\n";
			foreach($row as $key => $campo) {
				if ($key == 'codigo_posto') $campo = sprintf($link_posto, $posto, $campo);
				if (stripos($key, 'cnpj') !== false) {
					$campo = (strlen($campo) == 14) ? preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', "$1.$2.$3/$4-$5", $campo) : // CNPJ
													  preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', "$1.$2.$3-$4", $campo); // CPF, vai que um dia precisa...
				}
				$linha.= "\t\t\t<td>$campo</td>\n";
			}
			echo "$linha\t\t</tr>\n";
		}
		echo "\t</tbody>\n</table>";
		exit; // FIM do arquivo 'Excel'
	} else {
		exit('Erro na consulta. Tente novamente daqui uns instantes. Obrigado.<br>'.nl2br(print_r($sql_pa, true)));
	}
}

if ($_POST['acao']=='detalhe') {
	$codigo_posto	= $_POST['codigo_posto'];
	$posto			= $_POST['posto'];
	if (!$codigo_posto and !$posto) $msg_erro = 'Por favor, informe o código do Posto';

	$sql_pa = "SET dateStyle TO SQL, dmy;
SELECT  /*	Cadastro	*/
		posto,codigo_posto,data,fabrica,
		razao_social,tbl_posto_atualizacao.nome_fantasia,cnpj,ie,im,
		/*  Contato */
		fone1,fone2,fax,email,
		/*  Endereço    */
		endereco||', '||numero AS endereco,complemento,cep,bairro,cidade,estado,pais,
		/*  Dados bancários */
		tbl_posto_atualizacao.banco,tbl_banco.nome AS banco_nome,
		tbl_posto_atualizacao.agencia,tbl_posto_atualizacao.conta,
		tbl_posto_atualizacao.favorecido,tbl_posto_atualizacao.favorecido_cnpj,
		tbl_posto_atualizacao.tipo_conta,
		/*  Atendimento */
		CASE WHEN tbl_posto_atualizacao.tipo_posto = 'C' THEN 'Atende Consumidor'
		     WHEN tbl_posto_atualizacao.tipo_posto = 'R' THEN 'Atende Revendas'
		     WHEN tbl_posto_atualizacao.tipo_posto = 'A' THEN 'Atende Consumidor e Revenda'
		     ELSE 'Erro no Cadastro'
		 END AS posto_atende,
		atende_revendas,atende_cidades,linhas,suframa
  FROM  tbl_posto_atualizacao
  JOIN  tbl_posto_fabrica USING(posto,fabrica)
  JOIN  tbl_banco ON tbl_posto_atualizacao.banco = tbl_banco.codigo
 WHERE  fabrica = $login_fabrica
";
	$sql_pa.= ($posto) ? " AND posto = $posto" : " AND codigo_posto = '$codigo_posto'";
}

$title = 'Cadastro dos Postos - Dados atualizados';
$body_options = 'init();';
include 'cabecalho.php';
?>
<style type="text/css">
.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
	width: 700px;
	margin: auto;
	text-align: center;
}
.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
	margin: auto;
	width: 700px;
}
.formulario legend,
.formulario caption {
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.header, th {
    background-color: #7092BE;
    font:bold 11px Arial;
    color: #FFFFFF;
}
#lupa_cp {
	border: 0 solid transparent;
	cursor: pointer
	vertical-align: absmiddle;
}

	#extra_info {
	    position: relative;
		margin-bottom: 2em;
	    height:85%;
        padding-top: 32px;
        overflow: hidden;
	}
	#extra_info:hover {
		box-shadow: 3px 3px 3px #ccc;
	}
	#extra_info #ei_container div {
	    width: 100%;
	    margin-left: 2%;
		min-height: 150px;
		float: left;
	}
	#extra_info #ei_header {
		position: absolute;
		top:	0;
		left:	0;
		margin:	0;
		width: 100%;
		height: 30px;
		background-image: url('./imagens_admin/azul.gif');    /* IE */
		background-image: -moz-linear-gradient(top, #3e83c9, #60B0F0 27px, white);
		background-image: -webkit-gradient(linear,  0 0, 0 100%,
												from(#3e83c9),
													color-stop(0.80,#60B0F0),
													color-stop(0.95,white),
												to(white));
	    padding: 2px 1em;
	    color: white;
	    font: normal bold 13px Segoe UI, Verdana, MS Sans-Serif, Arial, Helvetica, sans-serif;
	}
	#extra_info #ei_container {
		margin: 1px;
		padding: 0 2em 1ex 2em;
		overflow-y: auto;
        overflow-x: hidden;
	    height: 100%;
        background-color: #D9E2EF;
	}
	#extra_info #fechar {
		position: absolute;
		top: 3px;
		right: 5px;
		width: 16px;
		height:16px;
		font: normal bold 12px Verdana, Arial, Helvetica, sans-serif;
		color:white;
	    cursor: pointer;
		margin:0;padding:0;
		vertical-align:top;
		text-align:center;
		background-color: #f44;
		border:	1px solid #d00;
		border-radius: 3px;
		-moz-border-radius: 3px;
		box-shadow: 2px 2px 2px #900;
		-moz-box-shadow: 1px 1px 1px #900;
		-webkit-box-shadow: 2px 2px 2px #900;
	}
	#extra_info dl {
		width: 43%;
		position: relative;
		float: left;
		clear: none;
		color: #224B7C;
		margin: 0 0 10px 3%;
        padding: 5px 2px;
		border:	2px solid #9AB8DF;
		background-color: #B8D0EF;
        box-shadow: 2px 2px 4px #ACC2DF;
        -moz-box-shadow: 2px 2px 4px #ACC2DF;
        -webkit-box-shadow: 2px 2px 4px #ACC2DF;
        border-radius: 0 5px 5px 5px;
        -moz-border-radius: 0 5px 5px 5px;
	}
	#extra_info dl label {
		background-color: #7092BE;
		border-radius: 4px 5px 0 0;
		-moz-border-radius: 4px 5px 0 0;
		display: inline-block;
		position: absolute;
		top: -20px;
		left: -2px;
		width: 70%;
		height: 15px;
		margin: 0;
		padding: 0 1ex 3px 1ex;
		font-variant: small-caps;
		font-weight: bold;
		font-size: 12px;
		color: white;
	}
	#extra_info dl dt {
		display: block;
		padding: 5px;
		margin: 0.3em 0 0 0.5em;
		color: #63768F;
		font-weight: bold;
		text-shadow: 2px 2px 3px #ccc;
		width: 60%;
		border-radius: 5px 5px 0 0;
		-moz-border-radius: 5px 5px 0 0;
		background-color: #ACC2DF;
		border-bottom: 1px dashed #7092BE;
	}
	#extra_info dl dd {
		display: block;
		padding: 5px 8px 5px 10px;
		margin: 0 0.5em 0.5em 0.5em;
		margin-bottom: 0.5em;
		width: 90%;
		min-height: 1.2em;
		border-radius: 0 6px 6px 6px;
		-moz-border-radius: 0 6px 6px 6px;
		background-color: #D9E2EF;
	}


</style>
<script type="text/javascript">
	function init() {
		var campo_cp= document.getElementById('codigo_posto');
		var lupa_cp	= document.getElementById('lupa_cp');
		var btn_list= document.getElementById('xls');

// 	    campo_cp.onchange = function() {
// 			if (this.value != '') fnc_pesquisa_posto(campo_cp);
// 	    };
	    lupa_cp.onclick = function() {
			if (this.value != '') fnc_pesquisa_posto(campo_cp);
	    };
		document.getElementById('reset').onclick = function() {
			campo_cp.value = '';
			document.getElementById('nome_posto').value = '';
			return false; // Se não, ele volta com os valores padrão... ou seja, o que está nos 'value="..."'
        };
	    btn_list.onclick = function() {
			window.open(location.pathname + '?acao=list',
						"arquivo",
						"toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0"
			);
	    };
	}

	function fnc_pesquisa_posto(campo) {
		tipo = (campo.name == 'codigo_posto') ? "codigo" : "nome";

	    if (campo.value != "") {
	        var url = "";
	        url = "posto_pesquisa_2.php?campo=" + campo.value + "&tipo=" + tipo;
	        janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
	        janela.codigo  = document.getElementById('codigo_posto');
	        janela.nome    = document.getElementById('nome_posto');
	        janela.focus();
	    }
	    else{
	        alert("Informar toda ou parte da informação para realizar a pesquisa!");
	    }
	}
</script>
<?

if (count($_POST) and $msg_erro) echo " <div class='msg_erro'>$msg_erro</div>\n";
?>
<form action="<?=$PHP_SELF?>" method='post' name='consulta'>
<table class="formulario">
	<caption>Parâmetros da Pesquisa</caption>
	<thead>
	<tr>
		<td width="10px"></td>
		<th width='30%'>Código do Posto</th>
		<th>Nome</th>
		<td width="10px"></td>
	</tr>
	<tr>
		<td width="10px"></td>
		<td><input type="text" name="codigo_posto" id="codigo_posto" class="frm"
			value="<?=$codigo_posto;?>" size="20" maxlength="20" />&nbsp;
			<img src="../imagens/lupa.png" id='lupa_cp' valign='middle'>
		</td>
		<td><input type='text' class='frm' id='nome_posto' name='nome_posto' value='<?=$nome_posto?>' size='50' readonly></td>
		<td width="10px"></td>
	</tr>
	<tr>
		<td width="10px"></td>
		<td colspan='2' align='center'>
			<button type='submit' name='acao' value='detalhe'>Pesquisar</button>
			<button type='button' id='xls' title='Clique aqui para fazer download dos últimos dados de todos os postos'>Gerar EXCEL</button>
			<button id='reset' type="reset">Redefinir</button>
		</td>
		<td width="10px"></td>
	</tr>
	</thead>
</table>
</form>
<script type="text/javascript">
init();
</script>

<?	if ($_POST['acao'] == 'detalhe' and !$msg_erro) {
	$res = pg_query($con, $sql_pa);
// 	echo $sql_pa;
	$dados = pg_fetch_assoc($res, 0);
// print_r($dados);
	extract($dados, EXTR_PREFIX_ALL, 'posto');
?>
	<p>&nbsp;</p>
    <div id='extra_info' class='formulario'>
		<h1 class='header' style='width: 680px;text-align: center;margin: auto;height: 100%;'>
			Informações atualizadas em data <?=$posto_data?> do posto <?=$codigo_posto?>
		</h1>
    <div id='ei_container'>
	<p>&nbsp;</p>
		<div>
	        <dl id="dados">
				<label for="mais_info">Cadastro</label>
	            <dt>Razão Social</dt>
	                <dd><?=$posto_razao_social?></dd>
	            <dt>Nome Fantasia</dt>
	                <dd><?=$posto_nome_fantasia?></dd>
	            <dt>CNPJ</dt>
	                <dd><?=$posto_cnpj?></dd>
	            <dt>Inscrição Estadual</dt>
	                <dd><?=$posto_ie?></dd>
	            <dt>Inscrição Municipal</dt>
	                <dd><?=$posto_im?></dd>
			</dl>
			<dl id='contato'>
				<label for="mais_info">Dados de Contato</label>
	            <dt>Endereço</dt>
	                <dd>
						<?echo $posto_endereco . iif((strlen(trim($posto_complemento))>0),", ".$posto_complemento);?><br>
						CEP: <?=preg_replace('/(\d{5})(\d{3})/','$1-$2',$posto_cep)?>,&nbsp;<?=$posto_bairro?><br>
						<?echo "$posto_cidade - $posto_estado";?>
					</dd>
	            <dt>Telefones</dt>
	                <dd>
						Fone 1: <?=$posto_fone1?><br>
						Fone 2: <?=$posto_fone2?><br>
						Fax: <?=$posto_fax?></dd>
	            <dt>E-Mail</dt>
	                <dd><?=$posto_email?></dd>
			</dl>
		</div>
	    <p>&nbsp;</p>
		<div stlye='height: 40%'>
			<dl id='mais_info'>
				<label for="mais_info">Dados Bancários</label>
	            <dt>Entidade Bancária</dt>
	                <dd><?=$posto_banco_nome?></dd>
	            <dt>Nº de Conta</dt>
	                <dd><?="$posto_banco - $posto_agencia - $posto_conta"?></dd>
	            <dt>Tipo de Conta</dt>
	                <dd><?=$posto_tipo_conta?></dd>
	            <dt>Favorecido</dt>
	                <dd>Nome: <?=$posto_favorecido?><br>
						CNPJ: <?=$posto_favorecido_cnpj?></dd>
			</dl>
			<dl id='info_fabricas'>
				<label>Atendimento</label>
	            <dt>Atende a(s) cidade(s) de</dt>
	                <dd><?=$posto_atende_cidades?></dd>
	            <dt>Este Posto...</dt>
	                <dd><?=$posto_posto_atende?></dd>
		<?if ($posto_atende_revendas) { ?>
	            <dt>Atende as Revendas</dt>
	                <dd><?=ucwords($posto_atende_revendas)?></dd>
		<?}?>
			<dt>Linhas que atende</dt>
			    <dd><?=$posto_linhas?></dd>
			</dl>
		</div>
	</div>
	</div>

<?}
	include 'rodape.php';	?>
