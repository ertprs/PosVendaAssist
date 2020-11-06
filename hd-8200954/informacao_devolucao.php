<?php
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include __DIR__.'/dbconfig.php';
include __DIR__.'/includes/dbconnect-inc.php';

/*ini_set("display_errors", 1);
error_reporting(E_ALL);*/

if ($areaAdmin === true) {
    $admin_privilegios = "call_center";
    include __DIR__.'/admin/autentica_admin.php';
} else {
    include __DIR__.'/autentica_usuario.php';
}

include_once 'helpdesk/mlg_funciones.php';
include __DIR__.'/funcoes.php';

include_once S3CLASS;

$s3 = new AmazonTC("devolucao", $login_fabrica);

$os_laudo = $_GET['os_laudo'];
$consulta = $_GET['consulta'];

if(strlen($os_laudo) > 0){

    $sql_laudo = "
        SELECT  tbl_os_laudo.os_laudo,
                to_char(tbl_os_laudo.data_digitacao, 'DD/MM/YYYY HH24:MI') as data_digitacao,
                tbl_os_laudo.nome_cliente,
                tbl_os_laudo.data_recebimento,
                tbl_os_laudo.nota_fiscal,
                tbl_os_laudo.data_nf,
                tbl_motivo_sintetico.descricao as descricao_sintetico,
                tbl_motivo_analitico.descricao as descricao_analitico,
                tbl_os_laudo.senha_autorizacao,
                tbl_os_laudo.nome_cliente,
                tbl_os_laudo.cpf_cliente,
                tbl_os_laudo.fone_cliente,
                tbl_os_laudo.celular_cliente,
                tbl_os_laudo.serie,
                tbl_os_laudo.responsavel_analise,
                tbl_defeito_constatado.descricao as descricao_defeito,
                tbl_analise_produto.descricao as descricao_analise,
                tbl_solucao.descricao as descricao_solucao,
                tbl_produto.referencia,
                tbl_produto.descricao as descricao_produto,
                tbl_os_laudo.aparencia_produto
        FROM    tbl_os_laudo
        JOIN    tbl_produto             ON tbl_produto.produto                          = tbl_os_laudo.produto
        JOIN    tbl_motivo_sintetico    ON tbl_motivo_sintetico.motivo_sintetico        = tbl_os_laudo.motivo_sintetico
        JOIN    tbl_motivo_analitico    ON tbl_motivo_analitico.motivo_analitico        = tbl_os_laudo.motivo_analitico
   LEFT JOIN    tbl_defeito_constatado  ON tbl_defeito_constatado.defeito_constatado    = tbl_os_laudo.defeito_constatado
   LEFT JOIN    tbl_solucao             ON tbl_solucao.solucao                          = tbl_os_laudo.solucao
   LEFT JOIN    tbl_analise_produto     ON tbl_analise_produto.analise_produto          = tbl_os_laudo.analise_produto
        WHERE   tbl_os_laudo.fabrica = $login_fabrica
        AND     tbl_os_laudo.os_laudo = $os_laudo";

	$res_laudo = pg_query($con,$sql_laudo);
}

$layout_menu = "callcenter";
$title = "CONFIRMAÇÃO DE DEVOLUÇÃO";
include_once "cabecalho_new.php";

?>
<script language="javascript">
	function printDiv(divName) {
	     var printContents = document.getElementById(divName).innerHTML;
	     var originalContents = document.body.innerHTML;

	     document.body.innerHTML = printContents;

	     window.print();

	     document.body.innerHTML = originalContents;
	}
</script>
	<div id="print">
		<table align="center" id="resultado_os" class='table table-bordered table-large' >

			<tr>
				<td class='titulo_tabela tac' colspan='100%'>Informações Devolução</td>
			</tr>
			<tr>
				<td class='titulo_coluna'>Nº</td>
				<td width="150" class="tac" style="font-size:26px; font-weight:bold; color:orange;"><?= pg_fetch_result($res_laudo, 0, 'os_laudo') ?></td>
				<td class='titulo_coluna' class="tac">Data Digitação</td>
				<td><?= pg_fetch_result($res_laudo, 0, 'data_digitacao') ?></td>
				<td class='titulo_coluna' class="tac">Data Recebimento</td>
				<td><?= mostra_data(pg_fetch_result($res_laudo, 0, 'data_recebimento')) ?></td>
			</tr>
			<tr>
				<td class='titulo_coluna'>Nota Fiscal</td>
				<td class="tac"><?= pg_fetch_result($res_laudo, 0, 'nota_fiscal') ?></td>
				<td class='titulo_coluna'>Emissão Nota Fiscal</td>
				<td class="tac"><?= mostra_data(pg_fetch_result($res_laudo, 0, 'data_nf')) ?></td>
                <td class='titulo_coluna'>Aparência do Produto</td>
                <td class="tac"><?= pg_fetch_result($res_laudo, 0, 'aparencia_produto') ?></td>
			</tr>
			<tr>
				<td class='titulo_coluna'>Motivo Sintético</td>
				<td class="tac"><?= pg_fetch_result($res_laudo, 0, 'descricao_sintetico') ?></td>
				<td class='titulo_coluna'>Motivo Analítico</td>
				<td colspan="3" class="tac"><?= pg_fetch_result($res_laudo, 0, 'descricao_analitico') ?></td>
			</tr>
			<tr>
				<td class='titulo_coluna'>Senha de Autorização</td>
				<td class="tac" colspan="5"><?= pg_fetch_result($res_laudo, 0, 'senha_autorizacao') ?></td>
			</tr>
		</table>

		<table align="center" id="resultado_os" class='table table-bordered table-large' >

			<tr>
				<td class='titulo_tabela tac' colspan='100%'>Informações do Cliente</td>
			</tr>

			<tr>
				<td class='titulo_coluna' width="15%">Nome</td>
				<td nowrap colspan="2"><?= pg_fetch_result($res_laudo, 0, 'nome_cliente') ?></td>
				<td class='titulo_coluna' width="15%">CPF/CNPJ</td>
				<td nowrap colspan="2" class="tac"><?= pg_fetch_result($res_laudo, 0, 'cpf_cliente') ?></td>
			</tr>
			<tr>
				<td class='titulo_coluna'>Telefone</td>
				<td nowrap colspan="2" class="tac"><?= pg_fetch_result($res_laudo, 0, 'fone_cliente') ?></td>
				<td class='titulo_coluna'>Celular</td>
				<td nowrap colspan="2" class="tac"><?= pg_fetch_result($res_laudo, 0, 'celular_cliente') ?></td>
			</tr>
		</table>

		<table align="center" id="resultado_os" class='table table-bordered table-large' >

			<tr>
				<td class='titulo_tabela tac' colspan='100%'>Informações do Produto</td>
			</tr>

			<tr>
				<td class='titulo_coluna'>Produto</td>
				<td ><?= pg_fetch_result($res_laudo, 0, 'referencia') ?> - <?= pg_fetch_result($res_laudo, 0, 'descricao_produto') ?></td>
				<td class='titulo_coluna' nowrap>Número Série</td>
				<td><?= pg_fetch_result($res_laudo, 0, 'serie') ?></td>
			</tr>
			<tr>
				<td class='titulo_coluna'>Defeito Constatado</td>
				<td><?= pg_fetch_result($res_laudo, 0, 'descricao_defeito') ?></td>
				<td class='titulo_coluna'>Solução</td>
				<td><?= pg_fetch_result($res_laudo, 0, 'descricao_solucao') ?></td>
			</tr>
			<tr>
				<td class='titulo_coluna'>Análise Produto</td>
				<td><?= pg_fetch_result($res_laudo, 0, 'descricao_analise') ?></td>
				<td class='titulo_coluna'>Responsável Análise</td>
				<td><?= pg_fetch_result($res_laudo, 0, 'responsavel_analise') ?></td>
			</tr>
		</table>
		<?php
			$sql_peca = "SELECT tbl_peca.referencia, tbl_peca.descricao as descricao_peca, tbl_os_laudo_peca.qtde, tbl_defeito.descricao as descricao_defeito, tbl_servico_realizado.descricao as descricao_servico
						FROM tbl_os_laudo_peca
						JOIN tbl_peca ON tbl_peca.peca = tbl_os_laudo_peca.peca
						JOIN tbl_defeito ON tbl_defeito.defeito = tbl_os_laudo_peca.defeito
						left JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_laudo_peca.servico_realizado
						WHERE tbl_os_laudo_peca.os_laudo = $os_laudo";

			$res_peca = pg_query($con,$sql_peca);

			if (pg_num_rows($res_peca) > 0) {
		?>
		<table align="center" id="resultado_os" class='table table-bordered table-large' >
			<tr colspan="100%">
				<td class='titulo_tabela tac' colspan='100%'>Peças Produto</td>
			</tr>
			<tr class='titulo_coluna'>
				<th>Peça</th>
				<th>Qtde.</th>
				<th>Defeito</th>
				<th>Serviço</th>
			</tr>
			<? for ($i=0;$i<pg_num_rows($res_peca);$i++) { ?>
			<tr>
				<td><?= pg_fetch_result($res_peca, $i, 'referencia') ?> - <?= pg_fetch_result($res_peca, $i, 'descricao_peca') ?></td>
				<td class="tac"><?= pg_fetch_result($res_peca, $i, 'qtde') ?></td>
				<td><?= pg_fetch_result($res_peca, $i, 'descricao_defeito') ?></td>
				<td class="tac"><?= pg_fetch_result($res_peca, $i, 'descricao_servico') ?></td>
			</tr>
			<? } ?>
		</table>
		<?
		} ?>
		</div>
		<center>
            <button class="btn btn-info tac" onclick="printDiv('print')">Imprimir</button>
            <?php
        if ($login_fabrica == 24 AND $areaAdmin !== true) {
?>
            <a target="_blank" class="btn btn-info" role="button" href="devolucao_cadastro.php?os_laudo=<?=$os_laudo?>">Lançar Itens</a>
<?php
        }
?>
        </center>
		<br /><br />
		<div id="div_anexos" class="tc_formulario">
        <div class="titulo_tabela">Anexos</div>
		<br />
		<?
		$fabrica_qtde_anexos = 3;
            if ($fabrica_qtde_anexos > 0) {
                    list($data_inp, $hora_inp) = explode(" ", pg_fetch_result($res_laudo, 0, 'data_digitacao'));
                    list($dia,$mes,$ano) = explode("/",$data_inp) ;
                    //echo $dia."/".$mes."//".$ano;

                echo "<input type='hidden' name='anexo_chave' value='{$anexo_chave}' />";

                for ($i = 0; $i < $fabrica_qtde_anexos; $i++) {
                    unset($anexo_link);

                    $anexo_imagem = "imagens/imagem_upload.png";
                    $anexo_s3     = false;
                    $anexo        = "";

                    if (strlen(getValue("anexo[{$i}]")) > 0 && getValue("anexo_s3[{$i}]") != "t") {

                        $anexos = $s3->getObjectList(getValue("anexo[{$i}]"), true);

                        $ext = strtolower(preg_replace("/.+\./", "", basename($anexos[0])));

                        if ($ext == "pdf") {
                            $anexo_imagem = "imagens/pdf_icone.png";
                        } else if (in_array($ext, array("doc", "docx"))) {
                            $anexo_imagem = "imagens/docx_icone.png";
                        } else {
                            $anexo_imagem = $s3->getLink("thumb_".basename($anexos[0]), true);
                        }

                        $anexo_link = $s3->getLink(basename($anexos[0]), true);

                        $anexo        = getValue("anexo[$i]");
                     } else if(strlen($os_laudo) > 0) {

                        $anexos = $s3->getObjectList("{$login_fabrica}_{$os_laudo}_{$i}", false, $ano, $mes);

                        if (count($anexos) > 0) {

                            $ext = strtolower(preg_replace("/.+\./", "", basename($anexos[0])));
                            if ($ext == "pdf") {
                                $anexo_imagem = "imagens/pdf_icone.png";
                            } else if (in_array($ext, array("doc", "docx"))) {
                                $anexo_imagem = "imagens/docx_icone.png";
                            } else {
                                $anexo_imagem = $s3->getLink("thumb_".basename($anexos[0]), false, $ano, $mes);
                            }

                            $anexo_link = $s3->getLink(basename($anexos[0]), false, $ano, $mes);

                            $anexo        = basename($anexos[0]);
                            $anexo_s3     = true;
                        }
                    }
                    ?>
                    <div id="div_anexo_<?=$i?>" class="tac" style="display: inline-block; margin: 0px 5px 0px 5px;">
                        <?php if (isset($anexo_link)) { ?>
                            <a href="<?=$anexo_link?>" target="_blank" >
                        <?php } ?>

                        <img src="<?=$anexo_imagem?>" class="anexo_thumb" style="width: 100px; height: 90px;" />

                        <?php if (isset($anexo_link)) { ?>
                            </a>
                        <?php } ?>

                        <?php
                        if ($anexo_s3 === false) {
                        ?>
                            <button id="anexar_<?=$i?>" type="button" class="btn btn-mini btn-primary btn-block btn_acao_anexo" name="anexar" rel="<?=$i?>" >Anexar</button>
                        <?php
                        }
                        ?>

                        <img src="imagens/loading_img.gif" class="anexo_loading" style="width: 64px; height: 64px; display: none;" />

                        <input type="hidden" rel="anexo" name="anexo[<?=$i?>]" value="<?=$anexo?>" />
                        <input type="hidden" name="anexo_s3[<?=$i?>]" value="<?=($anexo_s3) ? 't' : 'f'?>" />
                        <?php
                        if ($anexo_s3 === true) {?>
                            <button id="baixar_<?=$i?>" type="button" class="btn btn-mini btn-info btn-block" name="baixar" onclick="window.open('<?=$anexo_link?>')">Baixar</button>

                        <?php
                        }
                        ?>
                    </div>
                <?php
                }
            }
?>
				<br /><br />
			</div>
			<br />
            <?php if($consulta != true){ ?>
			<div class="row row-fluid">
				<div class="tac">
					<a href="devolucao_cadastro.php?os_laudo_info=<?= $os_laudo ?>">
						<button class="btn">Continuar cadastrando devoluções da mesma NF</button>
					</a>
					<br /><br />
					<a href="devolucao_cadastro.php">
						<button class="btn">Cadastrar nova devolução</button>
					</a>
				</div>
			</div>
            <?php } ?>

<?

/* Rodapé */
	include 'rodape.php';
?>
