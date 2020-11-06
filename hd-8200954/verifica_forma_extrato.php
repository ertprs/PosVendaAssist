<?php
	/*
     * @description  HD 417698 - Novo processo de geração de extrato
     * @author Brayan L. Rastelli
     * @version 1.0
	 */
	include_once "dbconfig.php";
	include_once "includes/dbconnect-inc.php";
	include 'funcoes.php';

	if (isset($_REQUEST['posto']) && isset($_REQUEST['admin'])) {
		include_once 'admin/autentica_admin.php';
		$login_posto = $_REQUEST['posto'];
	}
	else {
		include_once "autentica_usuario.php";
	}


	$sql = "SELECT valor_minimo_extrato
			FROM tbl_fabrica
			WHERE fabrica = $login_fabrica";

	$res = pg_query($con, $sql);
	$valor_minimo = @pg_result($res,0,0);

	if (isset($_POST['ajax']) && $_POST['ajax'] == 1) {

		$opcao_extrato 	= trim ( addslashes($_POST['opcao_extrato']) );
		$opcao_nf		= trim ( addslashes($_POST['opcao_nf']) );
		$responsavel 	= trim ( utf8_decode(addslashes($_POST['responsavel'])) );

		if ( strpos($opcao_nf, 'online') === false ) {

			$online = 'f';

		} else {
			$online = 't';
		}

		$admin = !empty($cook_admin) ? $cook_admin : 'null';
		if (empty($opcao_nf) || empty($responsavel)) {
			echo 'Preencha todos os campos.';
			return;
		}

		$sql = "SELECT tipo_gera_extrato,
						tipo_envio_nf,
						data_atualizacao,
						data_input
				FROM tbl_tipo_gera_extrato
				WHERE posto = $login_posto
				AND fabrica = $login_fabrica";

		$res = pg_query($con, $sql);

        if ($login_fabrica == 1 && ($opcao_extrato == 4 || !isset($login_admin))) {
            $sqlOpcao = "
                SELECT  intervalo_extrato
                FROM    tbl_intervalo_extrato
                WHERE   fabrica = $login_fabrica
                AND     periodicidade = 30
                AND     estado = (SELECT estado FROM tbl_posto WHERE posto = $login_posto AND estado <> 'EX')
            ";
            $resOpcao = pg_query($con,$sqlOpcao);
            $opcao_extrato = pg_fetch_result($resOpcao,0,intervalo_extrato);
        }

        if ( pg_num_rows($res) == 0 ) {

            $tipo_operacao = "insert";


            $sql = "
                INSERT INTO tbl_tipo_gera_extrato (
                    admin,
                    posto,
                    fabrica,
                    descricao,
                    intervalo_extrato,
                    tipo_envio_nf,
                    envio_online,
                    responsavel
                ) VALUES (
                    $admin,
                    $login_posto,
                    $login_fabrica,
                    'Opcao Extrato',
                    $opcao_extrato,
                    '$opcao_nf',
                    '$online',
                    '$responsavel'
                ) RETURNING tipo_gera_extrato;
            ";
		} else {

			if (empty($admin)) { // somente o admin pode atualizar
				return false;
			}

			$tipo_operacao = "update";

			$tipo_gera_extrato = pg_result($res,0,'tipo_gera_extrato');
			$tipo_envio_nf 	   = pg_result($res,0,'tipo_envio_nf');
			$data_atualizacao  = pg_result($res,0,'data_atualizacao');
			$data_input  = pg_result($res,0,'data_input');
			if(empty($data_atualizacao)) $data_atualizacao = $data_input;

			$sqlA = "SELECT * FROM tbl_tipo_gera_extrato WHERE tipo_gera_extrato = $tipo_gera_extrato";
			$resA = pg_query($con,$sqlA);
			$auditor_antes = pg_fetch_assoc($resA);


			$sql = "
                UPDATE  tbl_tipo_gera_extrato
                SET     admin               = $cook_admin,
                        intervalo_extrato   = $opcao_extrato,
                        tipo_envio_nf       = '$opcao_nf',
                        envio_online        = '$online',
                        responsavel         = '$responsavel',
                        data_atualizacao    = CURRENT_TIMESTAMP,
                        tipo_envio_nf_ant   = '$tipo_envio_nf',
                        data_envio_ant      = '$data_atualizacao'
                WHERE   fabrica             = $login_fabrica
                AND     tipo_gera_extrato   = $tipo_gera_extrato";
		}

		$res = pg_query($con,$sql);

		if($tipo_operacao == "insert"){
			$tipo_gera_extrato = pg_result($res,0,'tipo_gera_extrato');
		}

		$sqlD = "SELECT * FROM tbl_tipo_gera_extrato WHERE tipo_gera_extrato = $tipo_gera_extrato";
		$resD = pg_query($con,$sqlD);
		$auditor_depois = pg_fetch_assoc($resD);
		$nome_servidor = $_SERVER['SERVER_NAME'];
		$nome_uri = $_SERVER['REQUEST_URI'];
		$nome_url = $nome_servidor.$nome_uri;

		auditorLog($tipo_gera_extrato,$auditor_antes,$auditor_depois,"tbl_tipo_gera_extrato",$nome_url,strtoupper($tipo_operacao));

		if ( pg_errormessage($con) ) {
			echo 'Falha ao inserir/atualizar respostas' . pg_errormessage($con);
		} else {
			echo 'ok';
		}
		return;
	}

    $sql = "SELECT  responsavel,
                    tipo_gera_extrato ,
                    intervalo_extrato,
                    tbl_intervalo_extrato.descricao,
                    estado,
                    tipo_envio_nf,
                    admin,
                    TO_CHAR(data_atualizacao, 'DD/MM/YYYY') AS data_atualizacao,
                    TO_CHAR(tbl_intervalo_extrato.data_input,'DD/MM/YYYY')        AS data_input
            FROM    tbl_tipo_gera_extrato
            JOIN    tbl_intervalo_extrato USING(fabrica,intervalo_extrato)
            WHERE   posto   = $login_posto
            AND     fabrica = $login_fabrica";
	$res = pg_query($con, $sql);

	if ( pg_num_rows($res) ) {

        $responsavel            = pg_result($res,0,'responsavel');
        $intervalo              = pg_result($res,0,'intervalo_extrato');
        $intervalo_descricao    = pg_result($res,0,'descricao');
        $intervalo_uf           = pg_result($res,0,'estado');
        $tipo_envio_nf          = pg_result($res,0,'tipo_envio_nf');
        $admin                  = pg_result($res,0,'admin');
        $atualizacao            = pg_result($res,0,'data_atualizacao');
        $data_input             = pg_result($res,0,'data_input');
        $atualizacao            = (empty($atualizacao)) ? $data_input:$atualizacao;
        $tipo_gera_extrato      = pg_result($res, 0, 'tipo_gera_extrato');


	}

?>

<!DOCTYPE HTML>
<html lang="en-US">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<title>Geração de extrato</title>
	<style type="text/css">

	body{
		background: #eee url(imagens/modal-gloss.png) no-repeat -200px -80px;
		font-size:12px;
		padding: 30px 40px 34px;
		-moz-border-radius: 5px;
		-webkit-border-radius: 5px;
		border-radius: 5px;
		-moz-box-shadow: 0 0 10px rgba(0,0,0,.4);
		-webkit-box-shadow: 0 0 10px rgba(0,0,0,.4);
		-box-shadow: 0 0 10px rgba(0,0,0,.4);
		text-align:justify;
	}

	fieldset{

		border-radius:4px;

	}

	h1 {
		font-size:18px;
		color:gray;
	}

	legend {
		font-weight:bold;
	}

	#salvar {

		border:1px solid white;
		border-radius: 7px;
		background: green;
		color: white;
		width: 100px;
		padding: 5px;
		font-weight: bold;
		cursor:pointer;

		-webkit-box-shadow: 1px 1px 4px rgba(0,0,0,0.4) inset;
		-moz-box-shadow: 1px 1px 4px rgba(0,0,0,0.4) inset;
		box-shadow: 1px 1px 4px rgba(0,0,0,0.4) inset;
	}

	#salvar:active {
		padding: 6px 13px 6px 11px;

	}

	#responsavel {
		padding:3px;
		border-radius:4px;
	}

</style>
</head>
<body>

	<div id="forma_extrato_div" class="reveal-modal">

		<h1>Prezado autorizado,</h1>
		<p>
			Com o objetivo de agilizar o processo de pagamento das garantias (aprovação dos extratos e envio de documentação para a fábrica) faremos duas alterações nesse processo. O intuito é oferecer mais opções para o período de fechamento dos extratos para que o posto de serviços possa escolher a melhor opção de acordo com as particularidades da sua empresa e também a forma de enviar a documentação.<br /><br />
			Dessa forma, solicitamos atenção especial na sua escolha. Gentileza analisar atentamente as questões abaixo.
		</p>
        <form action="<?=$PHP_SELF?>" method="POST" id="form_atualiza_extrato">
<?php
$questao = "QUESTÃO";
if (isset($login_admin)) {
?>
		<fieldset>

			<legend>QUESTÃO 1</legend>

			<div>
				Verificar qual opção se adapta melhor para a movimentação de OS's da sua empresa com relação ao volume e disponibilidade para envio de documentação para a fábrica. Segue opções abaixo e descrição de cada uma delas.
				Por favor, selecione a opção de sua escolha.
			</div>

				<input type="hidden" name="posto" value="<?=$login_posto?>" />
				<input type="hidden" name="admin" value="<?=$login_admin?>" />
				<?php

					$sql = "SELECT intervalo_extrato,
                            descricao, observacao
							FROM tbl_intervalo_extrato
							WHERE fabrica = $login_fabrica
							AND  estado IS NULL
							ORDER BY descricao";
					$res = pg_query($con,$sql);

					for ($i=0; $i < pg_num_rows($res) ; $i++) {

						$descricao = pg_result($res, $i, 'descricao');
						$observacao= pg_result($res, $i, 'observacao');
						$intervalo_extrato = pg_result($res, $i, 'intervalo_extrato');

						$selected  = (($intervalo == $intervalo_extrato) || ($intervalo_extrato == 4 && $intervalo_descricao == "Mensal")) ? 'checked' : '';

						echo '<p>
								<input type="radio" name="opcao_extrato" '.$selected.' id="'.$descricao.'" value="'.$intervalo_extrato.'" />
								<label for="'.$descricao.'">'.$descricao.' : '.$observacao.'</label>
							  </p>';

					}

				?>

				<h1>IMPORTANTE: </h1>

				<p>
					O prazo normal para aprovação é de até 5 dias úteis após a data de geração do extrato.
					O sistema considera um valor mínimo de R$ <?=number_format($valor_minimo, 2, ',', '.')?> para o fechamento do extrato. Independente da opção escolhida acima, o extrato não será gerado se tiver valor inferior a R$ <?=number_format($valor_minimo, 2, ',', '.')?>. Porém, mensalmente (primeira semana do mês) faremos uma análise nesses extratos que não alcançaram o valor mínimo para verificar a possibilidade de liberação de acordo com cada caso.
				</p>

			</fieldset>
<?php

    $questao = "QUESTÃO 2";
}
?>
			<fieldset>
				<legend><?=$questao?></legend>
				<div>
					Atualmente temos em todas as ordens de serviço a opção de anexar a NF do cliente e também vários postos já utilizam a NF de serviços eletrônica. Levando em consideração esses dois pontos, podemos resumir a nossa rotina de recebimento da documentação para uma rotina automática onde todos os documentos seriam enviados online (anexados no sistema) e não mais por correspondência. Esse sistema agiliza o processo de pagamento, pois não teríamos que aguardar o prazo de entrega dos correios para realizar a conferência. <br /><br />
					Por isso, precisamos que escolha abaixo a opção que melhor atende o seu posto. Segue abaixo detalhamento sobre cada uma delas. Gentileza avaliar e selecionar a opção que se adapta melhor ao seu negócio.
				</div>

				<p>
					<input type="radio" name="opcao_nf" value="correios" id="correios" <?=$tipo_envio_nf == 'correios' ? 'checked' : ''?> />
					<label for="correios">ENVIO DE DOCUMENTAÇÃO VIA CORREIOS (Opção usada atualmente): Nessa opção o posto digita as OS's e aguarda a aprovação do extrato. Assim que o extrato é aprovado o posto precisa separar toda a documentação do extrato para envio via correios para a fábrica. Após o recebimento da documentação fazemos a conferência e estando tudo correto é enviado ao financeiro para programação do pagamento.</label>
				</p>

				<p>
					<input type="radio" name="opcao_nf" value="online_possui_nfe" id="online_possui_nfe" <?=$tipo_envio_nf == 'online_possui_nfe' ? 'checked' : ''?> />
					<label for="online_possui_nfe">ENVIO DA DOCUMENTA&Ccedil;&Atilde;O ONLINE &ndash; POSTOS QUE POSSUEM NF ELETR&Ocirc;NICA (NOVA OP&Ccedil;&Atilde;O): O requisito b&aacute;sico nessa op&ccedil;&atilde;o &eacute; o posto trabalhar com NF de servi&ccedil;os eletr&ocirc;nica. Nessa op&ccedil;&atilde;o &eacute; obrigat&oacute;rio anexar a NF do cliente antes de gravar a ordem de servi&ccedil;o. O extrato ser&aacute; analisado e quando for aprovado ser&aacute; necess&aacute;rio apenas anexar a NF de servi&ccedil;os eletr&ocirc;nica e estando correta ser&aacute; enviado ao financeiro para programa&ccedil;&atilde;o do pagamento.</label>
				</p>

				<p>
					<input type="radio" name="opcao_nf" value="online_nao_possui_nfe" id="online_nao_possui_nfe" <?=$tipo_envio_nf == 'online_nao_possui_nfe' ? 'checked' : ''?> />
					<label for="online_nao_possui_nfe">ENVIO DA DOCUMENTA&Ccedil;&Atilde;O ONLINE &ndash; POSTOS QUE N&Atilde;O POSSUEM NF ELETR&Ocirc;NICA (NOVA OP&Ccedil;&Atilde;O): Nessa op&ccedil;&atilde;o &eacute; obrigat&oacute;rio anexar a NF do cliente antes de gravar a ordem de servi&ccedil;o. O extrato ser&aacute; analisado e quando for aprovado ser&aacute; necess&aacute;rio enviar a NF de servi&ccedil;os via correios. Ap&oacute;s o recebimento desta, e estando correta, o extrato ser&aacute; enviado ao financeiro para programa&ccedil;&atilde;o do pagamento.</label>
				</p>

				<h1>IMPORTANTE: </h1>

				<p>
					Os postos que optarem para envio de documentação online devem ficar atentos com relação às O.S's em aberto porque à partir do momento que gravarem a pesquisa o sistema considerará a opção escolhida para a próxima geração de extratos. Sendo assim, se optou por envio online deverá anexar as cópias da NF's dos clientes nas OS's já abertas anteriormente que ainda não foram geradas em extrato.
				</p>

			</fieldset>

			<p>
				<label for="responsavel">Responsável pela resposta</label>
				<input type="text" name="responsavel" id="responsavel" value="<?=$responsavel?>" />
			</p>

			<?php if (!empty($login_admin)) : ?>

				<p>

					<?php

						$sql = "SELECT nome_completo FROM tbl_admin WHERE admin = $admin";
						$res = pg_query($con, $sql);


						if ( pg_num_rows($res) ) {

							echo 'Última atualização: ' . pg_result($res, 0, 0) . ' em ' . $atualizacao;

						}

					?>

				</p>

			<?php endif; ?>

			<p>Lembrando que, após a escolha, o sistema será alterado automaticamente e na próxima semana já funcionará conforme descrição. </p>

			<p>Qualquer dúvida solicitamos que entre em contato com o seu suporte.</p>

			<div style="float:left;">
				Agradecemos a colaboração.<br />

				Departamento de Assistência técnica.<br />
				Stanley Black & Decker <br><br>
<?php if (!empty($login_admin)){
?>
				<a target='_BLANK' href='admin/relatorio_log_alteracao.php?parametro=tbl_tipo_gera_extrato&id=<?php echo $tipo_gera_extrato; ?>'>Visualizar Log Auditor</a>
<?php } ?>
			</div>

			<div style="float:right;">
				<button id="salvar">Salvar</button>
			</div>

			<div style="clear:both; overflow:hidden;"></div>

	    </form>

	</div>
	<script type="text/javascript" src="js/jquery-1.6.2.js"></script>
	<script type="text/javascript">

		$(function() {

			$("#salvar").click(function(e){

				e.preventDefault();

				if ( ! $("input[name=opcao_nf]").is(':checked') ) {

					alert('Selecione uma opção para a questão 2');
					return false;

				}

				if ( $.trim($("#responsavel").val()) == 0 ) {
					alert('Digite o nome do Responsável');
					$("#responsavel").focus();
					return false;
				}

				$.post('verifica_forma_extrato.php', $("#form_atualiza_extrato").serialize() + '&ajax=1', function(data) {

					data = $.trim(encodeURIComponent(data));

					if ( data === 'ok' ) {

						alert ('Obrigado por responder');
						window.parent.Shadowbox.close();
						return true;

					} else {

						alert( data );
						return false;

					}

				});

			});

		});

	</script>

</body>
</html>
