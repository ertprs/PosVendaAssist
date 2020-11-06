<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<style type="text/css">
	body {
		background-color: #87CEFA;		
	}
	.geral {
		background-color: #87CEFA;
	}
	.title {
		top: 0px;
		background-color: #4682B4;
		width: 100%;
	}
	.title h4 {
		color: white;
		text-align: center;
	}
	.content {
		padding: 20px;
	}
	.btn {
		margin-left: 45%;

	}
</style>
<script>
	function enviar(os, posicao){
		$('#btn_acao').attr('disabled', 'disabled');
		if (parseInt($("#qtde_instalado").val()) > parseInt($("#qtde_instalado").attr('max'))) {
			alert('valor maximo de produto: ' + $("#qtde_instalado").attr('max'));
			$('#btn_acao').removeAttr('disabled');
			return false;
		}
		var houve = $("input[name='houve']:checked").val();
		var qtde_instalado = $("#qtde_instalado").val();		
		if (houve == undefined){
                        alert('Obrigatorio preencher Sim ou Não.');
                        $('#btn_acao').removeAttr('disabled');
                        return false;
        }else{
                if(houve == 'sim' && $("#qtde_instalado").val() == '') {
                        alert('Preencha a quantidade.');
                        $('#btn_acao').removeAttr('disabled');
                        return false;
                }
        }

		$.ajax({
	        url:"<?=$PHP_SELF?>",
	        type:"POST",
	        data:{
	            gravar:os,
	            qtde_instalado: qtde_instalado,
	            houve: houve
	        },
	        success: function(data) {
	        	window.parent.alertOSconfirmeLorenzetti(os, 'sinal_' + posicao, 'excluir_' + posicao, 'lancar_' + posicao, posicao);
	        }
	    });		
	}
</script>
<?php
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_usuario.php';
include_once 'funcoes.php';
$os = ($_POST['gravar']) ? $_POST['gravar'] : null;
if (!is_null($os) && $_POST['houve'] == 'sim') {
	$sqlOS = "	
			SELECT 	tbl_os.fabrica,
					tbl_os.posto,
					tbl_os.data_nf,
					tbl_os.revenda_cnpj,
					tbl_os.revenda_nome,
					tbl_os.revenda_fone,
					tbl_os.revenda,
					tbl_os.consumidor_revenda,
					tbl_os.consumidor_cpf,
					tbl_os.consumidor_cidade,
					tbl_os.consumidor_estado,
					tbl_os.consumidor_fone,
					tbl_os.consumidor_endereco,
					tbl_os.consumidor_numero,
					tbl_os.consumidor_cep,
					tbl_os.consumidor_complemento,
					tbl_os.consumidor_bairro,
					tbl_os.produto,
					tbl_os.qtde_produtos,
					tbl_os.serie,
					tbl_os.sua_os,
					tbl_os.nota_fiscal,
					tbl_os.garantia_produto,
					tbl_tipo_atendimento.codigo,
					tbl_os.os_sequencia,
					tbl_os.consumidor_email,
					tbl_os.os_numero,
					tbl_os.admin_altera,
					tbl_os.finalizada,
					tbl_os.data_fechamento,
					tbl_os.consumidor_nome,
					tbl_os.tecnico_nome
			FROM tbl_os
			JOIN tbl_tipo_atendimento USING(tipo_atendimento)
			WHERE os = {$os}
			AND tbl_os.fabrica = {$login_fabrica}
			AND finalizada IS NULL";
	$resOS = pg_query($con, $sqlOS);

	if (pg_num_rows($resOS) == 0) {
		exit('erro');
	}

	$fabrica 				= pg_fetch_result($resOS, 0, 'fabrica');
	$posto   				= pg_fetch_result($resOS, 0, 'posto');
	$dataNf  				= pg_fetch_result($resOS, 0, 'data_nf');	
	$revendaCnpj			= pg_fetch_result($resOS, 0, 'revenda_cnpj');
	$revendaNome			= pg_fetch_result($resOS, 0, 'revenda_nome');
	$revendaFone			= pg_fetch_result($resOS, 0, 'revenda_fone');
	$revenda 				= pg_fetch_result($resOS, 0, 'revenda');
	$consumidorRevenda		= pg_fetch_result($resOS, 0, 'consumidor_revenda');
	$consumidor_cpf			= pg_fetch_result($resOS, 0, 'consumidor_cpf');
	$consumidorCidade		= pg_fetch_result($resOS, 0, 'consumidor_cidade');
	$consumidorEstado		= pg_fetch_result($resOS, 0, 'consumidor_estado');
	$consumidorFone			= pg_fetch_result($resOS, 0, 'consumidor_fone');
	$consumidorEndereco		= pg_fetch_result($resOS, 0, 'consumidor_endereco');
	$consumidorNumero		= pg_fetch_result($resOS, 0, 'consumidor_numero');
	$consumidorCep 			= pg_fetch_result($resOS, 0, 'consumidor_cep');
	$consumidorComplemento	= pg_fetch_result($resOS, 0, 'consumidor_complemento');
	$consumidorBairro		= pg_fetch_result($resOS, 0, 'consumidor_bairro');
	$produto 				= pg_fetch_result($resOS, 0, 'produto');
	$qtdeProdutos			= pg_fetch_result($resOS, 0, 'qtde_produtos');
	$serie	 				= pg_fetch_result($resOS, 0, 'serie');	
	$suaOs 					= pg_fetch_result($resOS, 0, 'sua_os');	
	$notaFiscal				= pg_fetch_result($resOS, 0, 'nota_fiscal');
	$garantiaProduto		= pg_fetch_result($resOS, 0, 'garantia_produto');
	$tipoAtendimento 		= pg_fetch_result($resOS, 0, 'codigo');	
	$osSequencia			= pg_fetch_result($resOS, 0, 'os_sequencia');
	$consumidorEmail		= pg_fetch_result($resOS, 0, 'consumidor_email');
	$osNumero				= pg_fetch_result($resOS, 0, 'os_numero');
	$adminAltera			= pg_fetch_result($resOS, 0, 'admin_altera');
	$finalizada				= pg_fetch_result($resOS, 0, 'finalizada');
	$dataFechamento			= pg_fetch_result($resOS, 0, 'data_fechamento');
	$tecnico_nome = pg_fetch_result($resOS, 0, 'tecnico_nome');
	$consumidor_nome = pg_fetch_result($resOS, 0, 'consumidor_nome');
	pg_query($con,"BEGIN TRANSACTION");
	$sql_sequencia = "SELECT os_sequencia FROM tbl_os LEFT JOIN tbl_os_excluida ON tbl_os.os = tbl_os_excluida.os WHERE tbl_os.os_numero= '$osNumero' and (tbl_os.fabrica = {$login_fabrica} or (tbl_os.fabrica =  0 and tbl_os_excluida.fabrica = {$login_fabrica})) and tbl_os.posto = $posto ORDER BY os_sequencia DESC limit 1;";
	$res_sequencia = pg_query($con, $sql_sequencia);
	$osSequencia = pg_fetch_result($res_sequencia, 0, 'os_sequencia');

	if ($osSequencia == 0 ) {
		$osSequencia++;
		$updateAnterior = "	UPDATE tbl_os 
							SET os_sequencia = {$osSequencia},
							sua_os = '{$osNumero}-{$osSequencia}'
							WHERE os = $os";
		//pg_query($con,$updateAnterior);
	}
	if (isset($_POST['qtde_instalado']) and $_POST['qtde_instalado'] > 0) {
		$qtdeProdutos = $_POST['qtde_instalado'];
	}
	$osSequencia++;
	if ($tipoAtendimento == 20) {
		$sqlTipoAtendimento = "SELECT tipo_atendimento
            FROM tbl_tipo_atendimento
            WHERE  codigo = 21";
	    $resTipoAtendimento = pg_query($con, $sqlTipoAtendimento);
	    $tipoAtendimento = pg_result($resTipoAtendimento, 0, tipo_atendimento);
	} else {
		$sqlTipoAtendimento = "SELECT tipo_atendimento
            FROM tbl_tipo_atendimento
            WHERE  codigo = 18";
	    $resTipoAtendimento = pg_query($con, $sqlTipoAtendimento);
	    $tipoAtendimento = pg_result($resTipoAtendimento, 0, tipo_atendimento);	
	}
	if($osSequencia == 1) {
		$osSequencia++;
	}
	$suaOs 			= "{$osNumero}-{$osSequencia}";
	$finalizada		= date("Y-m-d H:i:s.u"); 
	$dataFechamento = date("Y-m-d");
	$garantiaProduto = (strlen($garantiaProduto) == 0) ? 0 : $garantiaProduto;

	if(empty($serie)) {
		$serie = "";
	}

	if(empty($revenda)) {
		$revenda = "NULL";
	}

	if (empty($adminAltera)) {
		$adminAltera = 'NULL';
	}

	$insertOS = "INSERT INTO tbl_os(
						fabrica,
						posto,
						data_nf,
						revenda_cnpj,
						revenda_nome,
						revenda_fone,
						revenda,
						consumidor_revenda,
						consumidor_cpf,
						consumidor_cidade,
						consumidor_estado,
						consumidor_fone,
						consumidor_endereco,
						consumidor_numero,
						consumidor_cep,
						consumidor_complemento,
						consumidor_bairro,
						produto,
						qtde_produtos,
						serie,
						sua_os,
						nota_fiscal,
						garantia_produto,
						tipo_atendimento,
						os_sequencia,
						consumidor_email,
						os_numero,
						admin_altera,
						finalizada,
						data_fechamento,
						data_abertura,
						consumidor_nome,
						tecnico_nome
				) VALUES (
                		'{$fabrica}',
						'{$posto}',
						'{$dataNf}',
						'{$revendaCnpj}',
						'{$revendaNome}',
						'{$revendaFone}',
						{$revenda},
						'{$consumidorRevenda}',
						'{$consumidorCpf}',
						'{$consumidorCidade}',
						'{$consumidorEstado}',
						'{$consumidorFone}',
						'{$consumidorEndereco}',
						'{$consumidorNumero}',
						'{$consumidorCep}',
						'{$consumidorComplemento}',
						'{$consumidorBairro}',
						'{$produto}',
						'{$qtdeProdutos}',
						'{$serie}',
						'{$suaOs}',
						'{$notaFiscal}',
						'{$garantiaProduto}',
						'{$tipoAtendimento}',
						'{$osSequencia}',
						'{$consumidorEmail}',
						'{$osNumero}',
						$adminAltera,
						'{$finalizada}',
						'{$dataFechamento}',
						'{$dataFechamento}',
						'{$consumidor_nome}',
						'{$tecnico_nome}'
                	) returning os ";
    $result = pg_query($con,$insertOS);
 	
	$os_instalacao = pg_fetch_row($result);

	pg_query($con, "INSERT INTO tbl_os_extra(os, i_fabrica, i_posto,os_reincidente) VALUES ({$os_instalacao[0]}, {$fabrica}, {$posto}, {$os})");
    
    if (pg_last_error()) {
    	var_dump(pg_last_error());
    	$retorno = 'erro';
        pg_query($con,"ROLLBACK TRANSACTION");
    } else {
    	$retorno = 'ok';
        pg_query($con,"COMMIT TRANSACTION");
    }
	exit($retorno);
}

$os = $_GET['os'];
$posicao = $_GET['posicao'];

if ($login_fabrica == 19) {
	$sql = "SELECT fn_calcula_os_lorenzetti(tbl_os.sua_os) FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
	$res = pg_query($con, $sql);
	$error = pg_last_error($con);

	if (strpos($error, 'não tem mão-de-obra para atendimento') !== false) {
		die("<script>alert('Esta OS não tem mão-de-obra para este atendimento'); window.parent.fecharShadowbox();</script>");
	}
}

$consulta = "   SELECT  tbl_tipo_atendimento.codigo as tipo_atendimento, 
                                tbl_produto.linha,
                                tbl_os.qtde_produtos
                FROM tbl_os 
                JOIN tbl_tipo_atendimento 
                    ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
                JOIN tbl_produto
                    ON tbl_produto.produto = tbl_os.produto
                WHERE tbl_os.fabrica = $login_fabrica
                AND tbl_os.os = $os";
$resConsulta = pg_query($con, $consulta);
$atendimento = pg_fetch_result($resConsulta, 0, 'tipo_atendimento');
$linha       = pg_fetch_result($resConsulta, 0, 'linha');
$qtde_produtos       = pg_fetch_result($resConsulta, 0, 'qtde_produtos');
if (($linha == 928 && in_array($atendimento, [15,16])) || $atendimento == 20 || $atendimento == 26) {?>
    <body>
		<?php echo ($atendimento == 15) ? "<div class='geral'>" : "<div class='geral' style='height:250px;'>"; ?>
			<div class='title'>
				<?php echo ($atendimento == 15) ? traduz('houve.instalacao.nesta.visita', $con) : traduz('houve.instalacao.nesta.visita.revisao', $con); ?>
			</div>
			<div class='content'>
				<input type='radio' name='houve' id='houve' value='sim'>Sim</input>
				<?php 
				if (in_array($atendimento, [16, 20, 26])) {
					echo "<br>";
					echo "<br>";
					echo traduz('quantos.produtos.foram.instalados', $con);
					echo "<input type='number' min='0' max='{$qtde_produtos}' name='qtde_instalado' id='qtde_instalado'/>";
				}
				?>
				<br>
				<br>
				<input type='radio' name='houve' id='houve' value='nao'>Não</input>
				<br>
				<br>
				<input type='button' class='btn btn-success dropdown-toggle' name='btn_acao' id='btn_acao' value='Gravar' onClick='enviar(<?=$os?>, <?=$posicao?>)' />
			</div>
		</div>
	</body>
<?php } 
