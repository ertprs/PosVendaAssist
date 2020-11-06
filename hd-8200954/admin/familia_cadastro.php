<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';


$admin_privilegios="cadastros";
include 'autentica_admin.php';


include 'funcoes.php';

$layout_menu = "cadastro";
$title = traduz("CADASTRO DE FAMÍLIAS DOS PRODUTOS");

include "cabecalho_new.php";

$plugins = array(
	            "price_format",
				"tooltip",
				"mask"
			 );

include ("plugin_loader.php");

$res = pg_query($con,"SELECT pedido_via_distribuidor FROM tbl_fabrica WHERE fabrica = $login_fabrica");
$pedido_via_distribuidor = pg_fetch_result($res,0,0);

if (strlen($_REQUEST["familia"]) > 0) {
	$familia = trim($_REQUEST["familia"]);
}

if (strlen($_POST["btn_acao"]) > 0) {
	$btnacao = trim($_POST["btn_acao"]);
}

if (strlen($_POST["bosch_cfa"]) > 0) {
	$bosch_cfa = trim($_POST["bosch_cfa"]);
}

if ($btnacao == "deletar" && strlen($familia) > 0 ) {

	if (strlen($_POST["familia"]) > 0) {
		$familia = trim($_POST["familia"]);
	}

	$res = pg_query($con,"BEGIN TRANSACTION");

	$sql = "DELETE FROM tbl_familia_defeito_constatado
			WHERE       tbl_familia_defeito_constatado.familia = $familia";
	$res = pg_query($con,$sql);
	$msg_erro = pg_last_error($con);

	if (strlen ($msg_erro) == 0) {
		$sql = "DELETE FROM tbl_familia
				WHERE  tbl_familia.fabrica = $login_fabrica
				AND    tbl_familia.familia = $familia";
		$res = @pg_query($con,$sql);
		$msg_erro = pg_last_error($con);
		if(strlen($msg_erro)>0)
			$msg_erro = traduz("Esta Família não pode ser excluída porque está em uso em outras partes do sistema.");

		if (strpos ($msg_erro,'tbl_diagnostico') > 0)
			$msg_erro = traduz("Esta familia já possui 'Relacionamento de Integridade' cadastrada, e não pode ser excluida");

		if (strpos ($msg_erro,'tbl_defeito_reclamado') > 0)
			$msg_erro = traduz("Esta família já possui 'Defeitos Reclamados' cadastrada, e não pode ser excluída");

		if (strpos ($msg_erro,'tbl_defeito_constatado') > 0)
			$msg_erro = traduz("Esta família já possui 'Defeitos Constatados' cadastrada, e não pode ser excluída");

		if (strpos ($msg_erro,'update or delete on table "tbl_tipo_cliente" violates foreign key constraint $1 on table "tbl_cliente"') > 0)
			$msg_erro = traduz("Esta família já possui produtos cadastrados, e não pode ser excluída ");

		if (strpos ($msg_erro,'familia_fk') > 0)
			$msg_erro = traduz("Esta familia já possui produtos cadastrados, e não pode ser excluida");

		if (strlen ($msg_erro) == 0) {
			###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
			$res = pg_query($con,"COMMIT TRANSACTION");
			$msg = traduz("Apagado com Sucesso!");

		}else{
			###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS

			$familia   = $_POST["familia"];
			$codigo_familia   = $_POST["codigo_familia"];
			$descricao = $_POST["descricao"];

			$res = pg_query($con,"ROLLBACK TRANSACTION");
		}
	}
}

if ($btnacao == "gravar") {
	$codigo_familia        = trim($_POST["codigo_familia"]);
	$codigo_validacao_serie = trim($_POST["codigo_validacao_serie"]);
	$limite_horas_trabalhadas = trim($_POST["limite_horas_trabalhadas"]);

    if ($login_fabrica == 117) {
        if (strlen(trim($_POST["linha"])) > 0) {
            $linha = trim($_POST["linha"]);
        } else {
            $msg_erro = traduz("Favor informar o nome da Macro-Família.");
        }
    }

    if (in_array($login_fabrica, array(20,169,170))) {
		$setor_atividade = $_POST["setor_atividade"];
	}

	if ($login_fabrica == 19) {
	 	$data_fabricacao = $_POST['data_fabricacao'];

	 	if (!empty($data_fabricacao)) {
			$qtd_produto = $_POST['qtd_produto'];
			$array_produtos = array();
			for ($i = 0;$i <= $qtd_produto;$i++) {
				if (!empty($_POST["checkbox_".$i])) {
					$array_produtos[] = $_POST["checkbox_".$i];
				}
			}

			if (!empty($array_produtos)) {
				$cond_produtos = implode(",",$array_produtos);
			}
		}
	}

	if(strlen($codigo_familia)==0){
		$msg_erro .= traduz("Código da família não pode ser em branco.");
	}

	if (strlen($_POST["descricao"]) > 0) $aux_descricao  = "'". trim($_POST["descricao"]) ."'";
	else                                 $msg_erro       = traduz("Favor informar o nome da familia.");
	if($login_fabrica <> 10){
		if (strlen($codigo_familia)==0)      $codigo_familia = '';
		else                                 $codigo_familia = $codigo_familia;
	}
	if (strlen($bosch_cfa)==0)           $aux_bosch_cfa  = 'null';
	else                                 $aux_bosch_cfa  = "'" . $bosch_cfa . "'";
	if (strlen($ativo)==0)               $aux_ativo      = "'f'";
	else                                 $aux_ativo      = "'t'";

	$dadosJson = [];
	if ($login_fabrica == 190 ) {
		if (strlen($limite_horas_trabalhadas) > 0) {
			$dadosJson = ["limite_horas_trabalhadas" => $limite_horas_trabalhadas];
			$aux_bosch_cfa  = "'" . json_encode($dadosJson) . "'";
		} else {
			$aux_bosch_cfa  = 'null';
		}
	}




    $familia                  = $_POST["familia"];
	$laudo                    = $_POST["laudo"];
	$taxa_visita              = trim($_POST["taxa_visita"]);
	$hora_tecnica             = trim($_POST["hora_tecnica"]);
	$hora_tecnica_pta         = trim($_POST["hora_tecnica_pta"]);
	$valor_diaria             = trim($_POST["valor_diaria"]);
	$valor_por_km_caminhao    = trim($_POST["valor_por_km_caminhao"]);
	$valor_por_km_carro       = trim($_POST["valor_por_km_carro"]);
	$regulagem_peso_padrao    = trim($_POST["regulagem_peso_padrao"]);
	$certificado_conformidade = trim($_POST["certificado_conformidade"]);
	$valor_mao_de_obra        = trim($_POST["valor_mao_de_obra"]);
	$paga_km                  = ($login_fabrica == 15) ? trim($_POST["paga_km"]) : null ; //HD 275256 - gabrielSilva

	if (in_array($login_fabrica, array(169,170))) {
		$os_conjunto = ($_POST["os_conjunto"]) ? "true" : "false";
		$garantia_estendida = ($_POST["garantia_estendida"]) ? 1 : 0;

		$rpi = ($_POST["rpi"]) ? "true" : "false";

		$codigo_validacao_serie = $rpi;
	}

	$paga_km = ($paga_km == 'TRUE') ? 't' : 'f' ;

	if (strlen($taxa_visita)==0){
		$aux_taxa_visita = " null ";
	}else{
		$aux_taxa_visita = str_replace(",",".",$taxa_visita);
	}

	if (strlen($valor_diaria)==0){
		$aux_valor_diaria = " null ";
	}else{
		$aux_valor_diaria = str_replace(",",".",$valor_diaria);
	}

	if (strlen($valor_por_km_caminhao)==0){
		$aux_valor_por_km_caminhao = " null ";
	}else{
		$aux_valor_por_km_caminhao = str_replace(",",".",$valor_por_km_caminhao);
	}

	if (strlen($valor_por_km_carro)==0){
		$aux_valor_por_km_carro = " null ";
	}else{
		$aux_valor_por_km_carro = str_replace(",",".",$valor_por_km_carro);
	}

	if (strlen($regulagem_peso_padrao)==0){
		$aux_regulagem_peso_padrao = " null ";
	}else{
		$aux_regulagem_peso_padrao = str_replace(",",".",$regulagem_peso_padrao);
	}

	if (strlen($certificado_conformidade)==0){
		$aux_certificado_conformidade = " null ";
	}else{
		$aux_certificado_conformidade = str_replace(",",".",$certificado_conformidade);
	}

	if(strlen($valor_mao_de_obra)>0  AND strlen($hora_tecnica_pta)>0){
			$msg_erro = traduz("Favor Digitar o valor de hora técnica ou valor de M.O ");
	}

	if (strlen($valor_mao_de_obra)==0){
		if ($login_fabrica != 151) {
			$aux_valor_mao_de_obra = " null ";
		} else {
			$msg_erro = traduz("Informe a mão de obra<Br />");
		}
	}else{
		$aux_valor_mao_de_obra = str_replace(",",".",$valor_mao_de_obra);
	}

	if (strlen($hora_tecnica_pta)==0){
		$aux_hora_tecnica_pta = " null ";
	}else{
		$aux_hora_tecnica_pta = str_replace(",",".",$hora_tecnica_pta);
	}

	if (strlen($hora_tecnica)==0){
		$aux_hora_tecnica = " null ";
	}else{
		$aux_hora_tecnica = str_replace(",",".",$hora_tecnica);
	}
	if(strlen($codigo_familia) > 10){
		$msg_erro .= "Código da família não pode ter mais de 10 caracteres.";
	}


	if (strlen($msg_erro) == 0) {
		$mao_de_obra_adicional_distribuidor = trim ($_POST['mao_de_obra_adicional_distribuidor']);
		$mao_de_obra_familia                = trim($_POST['mao_de_obra_familia']);
		$aux_mao_de_obra_adicional_distribuidor = $mao_de_obra_adicional_distribuidor;
		if (strlen ($aux_mao_de_obra_adicional_distribuidor) == 0) {
			$aux_mao_de_obra_adicional_distribuidor = 0 ;
		}
		$aux_mao_de_obra_adicional_distribuidor = str_replace (",",".",$aux_mao_de_obra_adicional_distribuidor);

		$mao_de_obra_familia = str_replace(".","",$mao_de_obra_familia);
		$aux_mao_de_obra_familia = str_replace(",",".",$mao_de_obra_familia);

		if(in_array($login_fabrica, array(151))){
			$valor_mao_de_obra = str_replace(",",".",$valor_mao_de_obra);
		}

		$res = pg_query($con,"BEGIN TRANSACTION");

		if (!empty($familia)) {
			$whereFamilia = "AND familia != ".$familia;
		}

		$sql = "
			SELECT familia
			FROM tbl_familia
			WHERE fabrica = $login_fabrica
			AND (codigo_familia = '$codigo_familia' OR descricao = $aux_descricao)
			$whereFamilia
		";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){
			$msg_erro = traduz("Código ou descrição da família já existente. ");
		}

		if(strlen($msg_erro) == 0){
			if (strlen($familia) == 0) {
				if (in_array($login_fabrica, array(20,169,170))) {
					$campo_setor_atividade = " setor_atividade , ";
					$value_setor_atividade = " '$setor_atividade' , ";
				}

				###INSERE NOVO REGISTRO
				$sql = "INSERT INTO tbl_familia (
							fabrica                ,
							descricao              ,
							bosch_cfa              ,
							codigo_validacao_serie ,
							$campo_setor_atividade
							ativo                  ,";

				/*HD 275256*/
				if ($login_fabrica == 15){
					$sql .= "
								paga_km,
					";
				}

				if (in_array($login_fabrica,array(151))){
					$sql .= " mao_de_obra_familia, ";
              	}

                if (in_array($login_fabrica,array(117))){
                        $sql .= " linha, ";
                }

				if (in_array($login_fabrica, array(169,170))) {
					$sql .= " deslocamento, black, ";
				}

				$sql .="
							mao_de_obra_adicional_distribuidor,
							codigo_familia
						) VALUES (
							$login_fabrica   ,
							$aux_descricao   ,
							$aux_bosch_cfa   ,
							'$codigo_validacao_serie',
							$value_setor_atividade
							$aux_ativo       ,";

/*HD 275256*/ if ($login_fabrica == 15){

				$sql .= "
							'$paga_km',
				";

              }

      			if(in_array($login_fabrica, array(151))){
      				$sql .= " $valor_mao_de_obra, ";
      			}

      			if (in_array($login_fabrica,array(117))){
                        $sql .= " $linha, ";
                }

				if (in_array($login_fabrica, array(169,170))) {
					$sql .= " $os_conjunto, $garantia_estendida, ";
				}

				$sql .="
							$aux_mao_de_obra_adicional_distribuidor,
							'$codigo_familia'
						);";

				$res = pg_query($con,$sql);

				$msg_erro = pg_last_error($con);

				if (strlen($msg_erro) == 0){
					$res = pg_query($con,"SELECT CURRVAL('seq_familia')");
					if (strlen (pg_last_error($con)) > 0) $msg_erro = pg_errormessage($con);
					else                                    $familia = pg_fetch_result($res,0,0);
				}

			}else{
				###ALTERA REGISTRO

				if (in_array($login_fabrica, array(20,169,170))) {
					$update_setor_atividade = " setor_atividade = '$setor_atividade' , ";

				}

				$sql = "UPDATE tbl_familia SET
						codigo_familia 				= '$codigo_familia',
						descricao      				= $aux_descricao,
						bosch_cfa      				= $aux_bosch_cfa,
						codigo_validacao_serie      = '$codigo_validacao_serie',
						$update_setor_atividade
						ativo         				= $aux_ativo,";

/*HD 275256*/ if ($login_fabrica == 15){

				$sql .= "paga_km       = '$paga_km', ";

              }

				if (in_array($login_fabrica, array(151))){
					$sql .= " mao_de_obra_familia       = $valor_mao_de_obra, ";
	          	}

	          	if (in_array($login_fabrica,array(117))){
                        $sql .= " linha       = $linha, ";
                }

				if (in_array($login_fabrica, array(169, 170))) {
					$sql .= " deslocamento = $os_conjunto, black = $garantia_estendida, ";
				}

				$sql .="
						mao_de_obra_adicional_distribuidor = $aux_mao_de_obra_adicional_distribuidor
					WHERE  tbl_familia.fabrica = $login_fabrica
					AND    tbl_familia.familia   = $familia;";
				$res = @pg_query($con,$sql);
				$msg_erro = pg_last_error($con);

				if ($login_fabrica == 19 && isset($cond_produtos)) {
					$data_fabricacao = array("data_fabricacao" => $data_fabricacao);
					$data_fabricacao = json_encode($data_fabricacao);
					$sql = "UPDATE tbl_produto
							SET parametros_adicionais = '$data_fabricacao'
							WHERE produto IN ($cond_produtos)
						";
					$res = @pg_query($con,$sql);
					$msg_erro = pg_last_error($con);
				}
			}
		}
		if(strpos ($msg_erro,'tbl_familia_unico') > 0) {
			$msg_erro= "Código $codigo_familia já existente";
			if(strlen($codigo_familia) == 0){
				$msg_erro .= traduz("<br>Código da família não pode ser em branco.");
			}
		}
		if ($login_fabrica == 7) {
			if(strlen($msg_erro) == 0){
				$sql = "SELECT familia
						FROM tbl_familia_valores
						WHERE familia = $familia ";
				$res = pg_query($con,$sql);

				if(pg_num_rows($res) > 0){
					$sql = "UPDATE tbl_familia_valores SET
								taxa_visita              = $aux_taxa_visita              ,
								hora_tecnica             = $aux_hora_tecnica             ,
								hora_tecnica_pta         = $aux_hora_tecnica_pta         ,
								valor_diaria             = $aux_valor_diaria             ,
								valor_por_km_caminhao    = $aux_valor_por_km_caminhao    ,
								valor_por_km_carro       = $aux_valor_por_km_carro       ,
								regulagem_peso_padrao    = $aux_regulagem_peso_padrao    ,
								certificado_conformidade = $aux_certificado_conformidade ,
								valor_mao_de_obra        = $aux_valor_mao_de_obra
							WHERE familia = $familia ";
					$res = @pg_query($con,$sql);
					$msg_erro = pg_last_error($con);
				}else{
					$sql = "INSERT INTO tbl_familia_valores (
								familia                  ,
								taxa_visita              ,
								hora_tecnica             ,
								valor_diaria             ,
								valor_por_km_caminhao    ,
								valor_por_km_carro       ,
								regulagem_peso_padrao    ,
								certificado_conformidade ,
								valor_mao_de_obra        ,
								hora_tecnica_pta
							) VALUES (
								$familia                     ,
								$aux_taxa_visita             ,
								$aux_hora_tecnica            ,
								$aux_valor_diaria            ,
								$aux_valor_por_km_caminhao   ,
								$aux_valor_por_km_carro      ,
								$aux_regulagem_peso_padrao   ,
								$aux_certificado_conformidade,
								$aux_valor_mao_de_obra       ,
								$aux_hora_tecnica_pta
							);";
					$res = @pg_query($con,$sql);
					$msg_erro = pg_last_error($con);
				}
			}
		}
	}

/////////////////////
	// grava familia_defeito_constatado
	if (strlen($msg_erro) == 0){

		for ($i = 0 ; $i < $qtde_item ; $i++) {
			$novo   = $_POST["novo_".$i];

			$defeito_constatado     = $_POST['defeito_constatado_' . $i];
			$aux_defeito_constatado = $_POST['aux_defeito_constatado_' . $i];

			if(strlen($aux_defeito_constatado) > 0 AND strlen($defeito_constatado) == 0) {
				if ($novo == 'f') {
					$sql = "DELETE FROM tbl_familia_defeito_constatado
							WHERE  defeito_constatado = $aux_defeito_constatado
							AND    familia            = $familia ";
					$res = @pg_query($con,$sql);
					$msg_erro = pg_last_error($con);
				}
			}

			if (strlen ($msg_erro) == 0 AND strlen($defeito_constatado) > 0) {
				if ($novo == 't'){
					$sql = "INSERT INTO tbl_familia_defeito_constatado (
								defeito_constatado,
								familia
							) VALUES (
								$defeito_constatado,
								$familia
							)";
				}else{
					$sql = "UPDATE tbl_familia_defeito_constatado SET
								defeito_constatado = $defeito_constatado,
								familia            = $familia
							WHERE  defeito_constatado = $defeito_constatado
							AND    familia            = $familia ";
				}

				$res = pg_query($con,$sql);
				$msg_erro = pg_last_error($con);
			}
		}
	}
/////////////////////

    /**
     * - Para ORBIS:
     * Fará a relação de família com
     * laudos (pesquisas) para
     */
    if($login_fabrica == 88 && strlen($laudo) > 0){
        /**
         * - PRIMEIRO: Verifica se já há
         * laudo vinculado à família.
         * Se tiver, apaga.
         */
        $sqlProcura = "
            SELECT  COUNT(1) AS tem_laudo
            FROM    tbl_familia_laudo
            WHERE   familia     = $familia
        ";
        $resProcura = pg_query($con,$sqlProcura);
        $tem_laudo = pg_fetch_result($resProcura,0,tem_laudo);

        if($tem_laudo == 1){
            $sqlDeleta = "
                DELETE  FROM tbl_familia_laudo
                WHERE   familia = $familia
            ";
            $resDeleta = pg_query($con,$sqlDeleta);
        }

        $sqlLaudo = "
            INSERT INTO tbl_familia_laudo(
                familia,
                pesquisa
            )VALUES(
                $familia,
                $laudo
            )
        ";
        $resLaudo = pg_query($con,$sqlLaudo);

    }

	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		unset($mao_de_obra_familia);
		unset($familia);
		if ($login_fabrica == 117) {
			unset($linha);
		}
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		$msg = traduz("Gravado com Sucesso!");

	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS

		$codigo_familia    = $_POST["codigo_familia"];
		$descricao         = $_POST["descricao"];
		$ativo             = $_POST["ativo"];
		if ($login_fabrica == 117) {
			$linha = $_POST["linha"];
		}
		$res = pg_query($con,"ROLLBACK TRANSACTION");
	}
}

###CARREGA REGISTRO
if (strlen($_GET["familia"]) > 0 && empty($msg_erro)) {
	$familia_alteracao = $_GET["familia"];
	$sql = "
		SELECT
			tbl_familia.familia,
			tbl_familia.descricao,
			tbl_familia.codigo_familia,
			tbl_familia.codigo_validacao_serie,
			tbl_familia.mao_de_obra_familia,
			tbl_familia.bosch_cfa,
			tbl_familia.ativo,
			tbl_familia.paga_km,
			tbl_familia.setor_atividade,
			tbl_familia.mao_de_obra_adicional_distribuidor,
			tbl_familia.linha,
			tbl_familia.deslocamento,
			tbl_familia.black
		FROM tbl_familia
		WHERE tbl_familia.fabrica = {$login_fabrica}
		AND tbl_familia.familia = {$familia_alteracao};
	";

	$res = pg_query($con,$sql);

	//	echo $sql;
	if (pg_num_rows($res) > 0) {
		$familia_alteracao = trim(pg_result($res,0,familia));
		$codigo_familia = trim(pg_result($res,0,codigo_familia));
		$codigo_validacao_serie = trim(pg_result($res,0,codigo_validacao_serie));
		$mao_de_obra_familia = trim(pg_result($res,0,mao_de_obra_familia));
		$valor_mao_de_obra = trim(pg_result($res,0,mao_de_obra_familia));
		$valor_mao_de_obra = number_format($valor_mao_de_obra,2,",",".");
		$bosch_cfa = trim(pg_result($res,0,bosch_cfa));
		$descricao = trim(pg_result($res,0,descricao));
		$ativo = trim(pg_result($res,0,ativo));
		$paga_km = trim(pg_result($res,0,paga_km));
		$setor_atividade = trim(pg_result($res,0,setor_atividade));
		$linha = trim(pg_result($res,0,linha));
		$mao_de_obra_adicional_distribuidor = trim(pg_result($res,0,mao_de_obra_adicional_distribuidor));

		if ($login_fabrica == 190) {
			$xbosch_cfa  = json_decode($bosch_cfa, 1);
			$limite_horas_trabalhadas = isset($xbosch_cfa["limite_horas_trabalhadas"]) ? $xbosch_cfa["limite_horas_trabalhadas"] : 'false';
		}


		if (in_array($login_fabrica, array(169,170))) {
			$os_conjunto = pg_fetch_result($res, 0, "deslocamento");
			$garantia_estendida = pg_fetch_result($res, 0, "black");
			$rpi = $codigo_validacao_serie;
		}

		$sql = "
			SELECT
				taxa_visita,
				hora_tecnica,
				hora_tecnica_pta,
				valor_diaria,
				valor_por_km_caminhao,
				valor_por_km_carro,
				regulagem_peso_padrao,
				certificado_conformidade,
				valor_mao_de_obra
			FROM tbl_familia_valores
			WHERE familia = {$familia};
		";
		$res = pg_query($con,$sql);
		if (pg_num_rows($res) > 0) {
			$taxa_visita = trim(pg_fetch_result($res,0,taxa_visita));
			$hora_tecnica = trim(pg_fetch_result($res,0,hora_tecnica));
			$hora_tecnica_pta = trim(pg_fetch_result($res,0,hora_tecnica_pta));
			$valor_diaria = trim(pg_fetch_result($res,0,valor_diaria));
			$valor_por_km_caminhao = trim(pg_fetch_result($res,0,valor_por_km_caminhao));
			$valor_por_km_carro = trim(pg_fetch_result($res,0,valor_por_km_carro));
			$regulagem_peso_padrao = trim(pg_fetch_result($res,0,regulagem_peso_padrao));
			$certificado_conformidade = trim(pg_fetch_result($res,0,certificado_conformidade));
			$valor_mao_de_obra = trim(pg_fetch_result($res,0,valor_mao_de_obra));
		}

		$sql = "
            SELECT pesquisa
            FROM tbl_familia_laudo
            WHERE familia = {$familia_alteracao};
		";
		$res = pg_query($con,$sql);
		if (pg_num_rows($res) == 1) {
            $laudo = pg_fetch_result($res,0,pesquisa);
		}
	}
}
?>
<style>
table tr>td:first-of-type {
    text-align: right;
    padding-right: 1em;
}
</style>

<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
<script>
<? if (strlen($_GET['familia']) >0){?>
	$(function(){

		$("input[name=data_fabricacao]").mask("99/9999");

		var scroll = $(".scroll").offset();
		$(document).scrollTop(parseInt(scroll.top));

		$("#seleciona_todos").click(function() {
			var checkbox_todos = $(this);
			if ($(checkbox_todos).is(":checked")) {
				$(".checkbox_produto").each(function() {
					$(this).prop("checked", true);
				});
			} else {
				$(".checkbox_produto").each(function() {
					$(this).prop("checked", false);
				});
			}
		});
	});
<? } ?>
	function limpa(){
		document.frm_familia.descricao.value = "";
		document.frm_familia.codigo_familia.value = "";

	}

	$(function() {
		$('input[name=mao_de_obra_familia]').numeric({ allow : ',.' });
		$("#btnPopover").popover();
	});
</script>

<? if (strlen($msg_erro) > 0) { ?>
	<div class="alert alert-error">
		<h4><?echo $msg_erro;?></h4>
	</div>
<? }
if (strlen($msg) > 0) {
	unset(
		$codigo_familia,
		$descricao,
		$ativo,
		$mao_de_obra_adicional_distribuidor,
		$bosch_cfa,
		$paga_km,
		$codigo_validacao_serie,
		$taxa_visita,
		$valor_diaria,
		$hora_tecnica_pta,
		$valor_mao_de_obra,
		$regulagem_peso_padrao,
		$certificado_conformidade,
		$valor_por_km_carro,
		$valor_por_km_caminhao,
		$hora_tecnica,
		$laudo,
		$setor_atividade,
		$familia,
		$os_conjunto,
		$garantia_estendida,
		$rpi
	);
?>
	<div class="alert alert-success">
		<h4><?echo $msg;$msg="";?></h4>
	</div>
<? } ?>

<br/>
<div class="row">
	<b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b>
</div>
<form class='form-search form-inline tc_formulario' name="frm_familia" method="post" action="<? echo $PHP_SELF;if(isset($semcab))echo "?semcab=yes"; ?>">
	<input type="hidden" name="familia" value="<?= $familia ?>" />

	<div class="titulo_tabela"><?=traduz('Cadastro de Família')?></div>
	<br/>
	<? if ($login_fabrica == 19) {
		$span = 1;
	} else {
		$span = 2;
	} ?>
	<div class="row-fluid">
		<!-- Margem -->
		<div class="span<?= $span ?>"></div>
		<div class="span4">
			<div class="control-group">
				<label class="control-label" for=''><?=traduz('Código da Família')?></label>
				<div class='controls controls-row'>
					<h5 class="asteristico">*</h5>
					<?php if (in_array($login_fabrica, array(175))) { 
							if (strlen($codigo_familia)) {
								$read_only = 'readonly=readonly'; 
							}
						} ?>
				    <input class="span10" type="text" id="codigo_familia" name="codigo_familia" value="<? echo $codigo_familia ?>" maxlength="30" <?=$read_only;?> />
			    </div>
			</div>
		</div>

		<div class="span4">
			<div class='control-group <?=(strpos($msg_erro,"nome da familia") !== false) ? "error" : "" ?>'>
				<label class="control-label" for=""><?=traduz('Descrição da Família')?></label>
				<div class="controls controls-row">
					<h5 class="asteristico">*</h5>
					<input  type="text" id="descricao" name="descricao" value="<? echo $descricao ?>" maxlength="30" />
				</div>
			</div>
		</div>

		<? if ($login_fabrica == 19 && strlen($_GET['familia']) > 0) { ?>
			<div class="span2">
				<div class='control-group'>
					<label class="control-label" for=""><?=traduz('Data Fabricação')?></label>
					<div class="controls controls-row">
						<input class='span10' type="text" id="data_fabricacao" name="data_fabricacao" />
					</div>
				</div>
			</div>
		<? } ?>
		<div class="span1">
			<div class="control-group tac">
				<label class="control-label" for="">&nbsp;</label>
				<div class="controls controls-row tac">
					<label class="checkbox" >
						<input type='checkbox' name='ativo' id='ativo' value='TRUE' <?= ($ativo == 't' || $ativo == 'TRUE') ? "CHECKED" : "";?> /> <?=traduz('Ativo')?>
					</label>
				</div>
			</div>
		</div>
	    <!-- Margem -->
	    <div class="span2"></div>
	</div>
	<?php if ($login_fabrica == 190) { ?>
	<div class="row-fluid">
		<div class="span1"></div>
		<div class="span5">
			<div class="control-group tac">
				<label class="control-label" for="">&nbsp;</label>
				<div class="controls controls-row tac">
					<label class="checkbox" >
						<input type='checkbox' name='limite_horas_trabalhadas' id='limite_horas_trabalhadas' value='true' <?= ($limite_horas_trabalhadas == 'true') ? "CHECKED" : "";?> /> Limite de horas Trabalhadas
					</label>
				</div>
			</div>
		</div>
	</div>
	<?php } ?>

	<? if ($login_fabrica == 117) { ?>
	<div class="row-fluid">
        <!-- Margem -->
        <div class="span2"></div>
        <div class="span4">
            <div class="control-group ">
                <label for="linha" class="control-label"><?=traduz('Macro-Família')?></label>
                <div class="controls controls-row">
                    <div class="span10  ">
                        <h5 class="asteristico">*</h5>
                        <?php
                        $sql_macro = "SELECT DISTINCT tbl_linha.linha,
                                         tbl_linha.nome
                                    FROM tbl_linha
                                        JOIN tbl_macro_linha_fabrica ON tbl_linha.linha = tbl_macro_linha_fabrica.linha
                                        JOIN tbl_macro_linha ON tbl_macro_linha_fabrica.macro_linha = tbl_macro_linha.macro_linha AND tbl_macro_linha.ativo
                                    WHERE tbl_macro_linha_fabrica.fabrica = $login_fabrica
                                        $sqlMacroLinha_p
                                        AND     tbl_linha.ativo = TRUE
                                        ORDER BY tbl_linha.nome;";
                                    $res_macro = pg_query($con,$sql_macro);
                                    ?>
                                    <select name='linha' id='linha' class='span12'>
                                        <option value=''>Selecione</option>
                                        <?php
                                        for($i = 0; $i < pg_num_rows($res_macro); $i++){
                                                $macro_linha = pg_fetch_result($res_macro, $i, "linha");
                                                $macro_nome = pg_fetch_result($res_macro, $i, "nome");

                                                if ($linha == $macro_linha) {
                                                        $selected_macro = 'selected';
                                                }else{
                                                $selected_macro = '';
                                                } ?>
                                                <option value='<?=$macro_linha?>' <?=$selected_macro?> ><?=$macro_nome?></option>
                                        <?php } ?>

                                    </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="span4"></div>
		<!-- Margem -->
	    <div class="<?= $span ?>"></div>
	</div>
<? }
if (in_array($login_fabrica, array(169,170))) { ?>
	<div class="row-fluid">
		<!-- Margem -->
		<div class="span2"></div>
		<div class="span8">
			<div class="control-group <?=(in_array('setor_atividade', $msg_erro['campos'])) ? "error" : "" ?>">
                <label class="control-label" ><?=traduz('Permissão de instalação')?></label>
                <div class="controls controls-row">
                    <div class="span12" >
                        <label class="radio inline">
                            <input type="radio" name="setor_atividade" <?= ($setor_atividade == "I") ? "checked" : ""?> value="I" /> <?=traduz('Instalação')?>
                        </label>
                        <label class="radio inline">
                            <input type="radio" name="setor_atividade" <?= ($setor_atividade == "C") ? "checked" : ""?> value="C" /> <?=traduz('Conversão')?>
                        </label>
                        <label class="radio inline">
                            <input type="radio" name="setor_atividade" <?= ($setor_atividade == "IC") ? "checked" : ""?> value="IC" /> <?=traduz('Instalação/Conversão')?>
                        </label>
                        <label class="radio inline">
                            <input type="radio" name="setor_atividade" <?= (empty($setor_atividade)) ? "checked" : ""?> value="" /> <?=traduz('Nenhum')?>
                        </label>
                    </div>
                </div>
            </div>
		</div>
		<!-- Margem -->
		<div class="span2"></div>
	</div>

	<div class="row-fluid" >
		<div class="span2" ></div>
		<div class="span2">
			<div class="control-group">
				<label class="control-label" for="">&nbsp;</label>
				<div class="controls controls-row">
					<label class="checkbox" >
						<input type='checkbox' name='os_conjunto' id='os_conjunto' value='TRUE' <?if($os_conjunto == 't' || strtolower($os_conjunto) == "true") echo "CHECKED";?> /> <?=traduz('OS de Conjunto')?>
					</label>
				</div>
			</div>
		</div>
		<div class="span3">
			<div class="control-group tac">
				<label class="control-label" for="">&nbsp;</label>
				<div class="controls controls-row tac">
					<label class="checkbox" >
						<input type='checkbox' name='garantia_estendida' id='garantia_estendida' value='1' <?if($garantia_estendida == 1) echo "CHECKED";?> /> <?=traduz('Garantia Estendida')?>
					</label>
				</div>
			</div>
		</div>
		<div class="span3">
			<div class="control-group tac">
				<label class="control-label" for="">&nbsp;</label>
				<div class="controls controls-row tac">
					<label class="checkbox" >
						<input type='checkbox' name='rpi' id='rpi' value='true' <?if($rpi == 'true') echo "CHECKED";?> /> <?=traduz('RPI')?>
					</label>
				</div>
			</div>
		</div>
	</div>
<? }

if ($login_fabrica == 88) { ?>
	<div class="row-fluid">
	    <div class="span2"></div>
	    <div class="span8">
	        <div class='control-group <?=(strpos($msg_erro,"laudo") !== false) ? "error" : "" ?>'>
	            <label class="control-label" for="laudo"><?=traduz('Laudo')?></label>
	            <div class="controls controls-row">
	                <select name="laudo">
	                    <option value=""><?=traduz('SELECIONE O LAUDO')?></option>
	                    <option value="32" <?=($laudo == 32) ? "selected" : ""?>><?=traduz('LAUDO A')?></option>
	                    <option value="33" <?=($laudo == 33) ? "selected" : ""?>><?=traduz('LAUDO B')?></option>
	                    <option value="34" <?=($laudo == 34) ? "selected" : ""?>><?=traduz('LAUDO C')?></option>
	                    <option value="35" <?=($laudo == 35) ? "selected" : ""?>><?=traduz('LAUDO D')?></option>
	                    <option value="36" <?=($laudo == 36) ? "selected" : ""?>><?=traduz('LAUDO E')?></option>
	                </select>
	            </div>
	        </div>
	    </div>
		<div class="span2"></div>
	</div>
<? }

if ($pedido_via_distribuidor == 't') { ?>
	<div class="row-fluid">
		<!-- Margem -->
		<div class="span2"></div>
		<div class="span8">
			<div class="control-group">
				<label class="control-label" for=""><?=traduz('M.O. adicional para Distribuidor')?></label>
				<div class="controls controls-row">
					<input type='text' name='mao_de_obra_adicional_distribuidor' value="<?=$mao_de_obra_adicional_distribuidor?>" maxlength='10'>
				</div>
			</div>
		</div>
		<!-- Margem -->
		<div class="span2"></div>
	</div>

<? }
if($login_fabrica == 20){ ?>
	<div class="row-fluid">
		<!-- Margem -->
		<div class="span2"></div>
		<div class="span4">
			<div class="control-group">
				<label class="control-label" for=""><?=traduz('CFA')?></label>
				<div class="controls controls-row">
					<input type='text' class='frm' name='bosch_cfa' value="<?=$bosch_cfa?>" maxlength='10'>
				</div>
			</div>
		</div>
		<div class="span4">
			<div class="control-group">
				<label class="control-label" for=""><?=traduz('Setor de Atividade')?></label>
				<div class="controls controls-row">
					<input type='text' class='frm' name='setor_atividade' value="<?=$setor_atividade?>" maxlength='20'>
				</div>
			</div>
		</div>
		<!-- Margem -->
		<div class="span2"></div>
	</div>
<? }

//HD 275256 - gabrielSilva
if ($login_fabrica == 15){?>
	<div class="row-fluid">
		<!-- Margem -->
		<div class="span9"></div>
		<div class="span1">
			<div class="control-group tac">
				<label class="control-label" for=""><?=traduz('Paga KM')?></label>
				<div class="controls controls-row tac">
					<input type='checkbox' name='paga_km' id='paga_km' value='TRUE' <?if($paga_km == 't') echo "CHECKED";?>>
				</div>
			</div>
		</div>
		<!-- Margem -->
		<div class="span1"></div>
	</div>

<?}?>

<? if (in_array($login_fabrica, array(45,129))) { ?>

	<div class="row-fluid">
		<!-- Margem -->
		<div class="span2"></div>

		<div class="span8">
			<div class="control-group">
				<label class="control-label" for=""><?=traduz('Cód. Validação de Número de Série')?></label>
				<div class="controls controls-row">
					<div class="input-append">
						<input type='text' class="span10" id='codigo_validacao_serie' name='codigo_validacao_serie' value="<?php echo $codigo_validacao_serie;?>" maxlength='30'>
						<span class="add-on">
							<i id="btnPopover" rel="popover" data-placement="top" data-trigger="hover" data-delay="500" title="Info" data-content='<?traduz("Caso a família tenha mais de um código, separar por virgula. Ex: TS,TK")?>' class="icon-question-sign"></i>
						</span>
					</div>
				</div>
			</div>
		</div>

		<!-- Margem -->
		<div class="span2"></div>
	</div>
<? }

if ($login_fabrica == 151) { ?>
	<div class="row-fluid">
        <!-- Margem -->
        <div class="span2"></div>

        <div class="span4">
            <div class="control-group">
                <label class="control-label" for="valor_mao_de_obra"><?=traduz('Valor da Mão de Obra')?></label>
                <div class="controls controls-row">
					<h5 class="asteristico" >*</h5>
                    <input type="text" id="valor_mao_de_obra" name="valor_mao_de_obra" class='frm' value="<? echo $valor_mao_de_obra ?>" size="10" price="true" >
                </div>
            </div>
        </div>

        <!-- Margem -->
        <div class="span2"></div>
	</div>

<?php
}

if ($login_fabrica == 7) { ?>
<br/>
<div class="titulo_tabela"><?=traduz('Valores')?></div>
	<div class="row-fluid">
		<!-- Margem -->
		<div class="span2"></div>

		<div class="span4">
			<div class="control-group">
				<label class="control-label" for=""><?=traduz('Taxa de Visita')?></label>
				<div class="controls controls-row">
					<input class="span10" type="text" id="taxa_visita" name="taxa_visita" value="<? echo $taxa_visita ?>" maxlength="10">
				</div>
			</div>
		</div>

		<div class="span4">
			<div class="control-group">
				<label class="control-label" for=""><?=traduz('Diária')?></label>
				<div class="controls controls-row">
					<input class="span10" type="text" id="valor_diaria" name="valor_diaria" value="<? echo $valor_diaria ?>" maxlength="10">
				</div>
			</div>
		</div>

		<!-- Margem -->
		<div class="span2"></div>
	</div>

	<div class="row-fluid">
		<!-- Margem -->
		<div class="span2"></div>

		<div class="span4">
			<div class="control-group">
				<?
					$title = traduz("Valor pago por hora para PTA por cada reparo por cada produto dessa família, Não é pago o valor de mão de obra.");
				?>
				<label class="control-label" for=""><abbr title="<? echo $title;?>"><?=traduz('Hora Técnica PTA')?></abbr></label>
				<div class="controls controls-row">
					<abbr title="<? echo $title;?>"><input type="text" id="hora_tecnica_pta" name="hora_tecnica_pta" value="<? echo $hora_tecnica_pta ?>" maxlength="10"></abbr>
				</div>
			</div>
		</div>

		<div class="span4">
			<div class="control-group">
				<?
					$title = traduz("Valor de mão de obra pago para PTA por cada reparo de produto da família, Não pagar por hora técnica.");
				?>
				<label class="control-label" for=""><abbr title="<? echo $title;?>"><?=traduz('Valor M.O.')?></abbr></label>
				<div class="controls controls-row">
					<abbr title="<? echo $title;?>"><input type="text" name="valor_mao_de_obra" class='frm' value="<? echo $valor_mao_de_obra ?>" size="10" maxlength="10"></abbr>
				</div>
			</div>
		</div>

		<!-- Margem -->
		<div class="span2"></div>
	</div>

	<div class="row-fluid">
		<!-- Margem -->
		<div class="span2"></div>

		<div class="span4">
			<div class="control-group">
				<label class="control-label" for=""><?=traduz('Regulagem')?></label>
				<div class="controls controls-row">
					<input type="text" id="regulagem_peso_padrao" name="regulagem_peso_padrao" value="<? echo $regulagem_peso_padrao ?>" maxlength="10">
				</div>
			</div>
		</div>

		<div class="span4">
			<div class="control-group">
				<label class="control-label" for=""><?=traduz('Certificado')?></label>
				<div class="controls controls-row">
					<input type="text" id="certificado_conformidade" name="certificado_conformidade" class='span10' value="<? echo $certificado_conformidade ?>" maxlength="10">
				</div>
			</div>
		</div>

		<!-- Margem -->
		<div class="span2"></div>
	</div>

	<div class="row-fluid">
		<!-- Margem -->
		<div class="span2"></div>

		<div class="span4">
			<div class="control-group">
				<label class="control-label" for=""><?=traduz('Valor Por KM - Carro')?></label>
				<div class="controls controls-row">
					<input type="text" id="valor_por_km_carro" name="valor_por_km_carro" class='span10' value="<? echo $valor_por_km_carro ?>" maxlength="10">
				</div>
			</div>
		</div>

		<div class="span4">
			<div class="control-group">
				<label class="control-label" for=""><?=traduz('Valor Por KM - Caminhão')?></label>
				<div class="controls controls-row">
					<input type="text" name="valor_por_km_caminhao" class='span10' value="<? echo $valor_por_km_caminhao ?>" maxlength="10">
				</div>
			</div>
		</div>

		<!-- Margem -->
		<div class="span2"></div>
	</div>

	<div class="row-fluid">
		<!-- Margem -->
		<div class="span2"></div>

		<div class="span4">
			<div class="control-group">
				<? $title = "Hora Técnica cobrada do consumidor/cliente."; ?>
				<label class="control-label" for=""><abbr title="<? echo $title; ?>"> <?=traduz('Hora Técnica')?></abbr></label>
				<div class="controls controls-row">
					<abbr title="<? echo $title; ?>"><input type="text" id="hora_tecnica" name="hora_tecnica" class='span10' value="<? echo $hora_tecnica ?>" maxlength="10"></abbr>
				</div>
			</div>
		</div>

		<!-- Margem -->
		<div class="span2"></div>
	</div>

<? } ?>

<? //chamado 2977 - HD 82470
if (in_array($login_fabrica, array(1,2,5,8,10,14,16,20,66))) { ?>
	<div class="titulo_tabela"><B><?=traduz('Selecione os Defeitos Constatados da Família')?></B></div>
	<div class="row-fluid">
		<!-- Margem -->
		<div class="span2"></div>
		<div class="span4">


<? $familia = $_GET['familia'];

	$sql = "SELECT *
			FROM  tbl_defeito_constatado
			WHERE fabrica = $login_fabrica
			ORDER BY LPAD(codigo,5,'0') ASC";
	$res = @pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {

		$y=1;

		for($i=0; $i<pg_num_rows($res); $i++){
			$defeito_constatado = trim(pg_fetch_result($res,$i,defeito_constatado));
			$codigo             = trim(pg_fetch_result($res,$i,codigo));
			$descricao          = trim(pg_fetch_result($res,$i,descricao));

			if (strlen($familia) > 0) {
				$sql = "SELECT  tbl_familia_defeito_constatado.familia_defeito_constatado,
								tbl_familia_defeito_constatado.defeito_constatado
						FROM    tbl_familia_defeito_constatado
						WHERE   tbl_familia_defeito_constatado.defeito_constatado = $defeito_constatado
						AND     tbl_familia_defeito_constatado.familia            = $familia";
				$res2 = @pg_query($con,$sql);

				if (pg_num_rows($res2) > 0) {
					$novo                       = 'f';
					$familia_defeito_constatado = trim(pg_fetch_result($res2,0,familia_defeito_constatado));
					$xdefeito_constatado        = trim(pg_fetch_result($res2,0,defeito_constatado));
				}else{
					$novo                       = 't';
					$familia_defeito_constatado = "";
					$xdefeito_constatado         = "";
				}
			}else{
				$novo                       = 't';
				$familia_defeito_constatado = "";
				$xdefeito_constatado         = "";
			}

			$resto = $y % 2;
			$y++;

			if ($xdefeito_constatado == $defeito_constatado)
				$check = " checked ";
			else
				$check = "";
?>


				<label class="checkbox" for="">
					<input type='hidden' name='novo_<? echo $i;?>' value='<?echo $novo;?>' />
					<input type='hidden' name='aux_defeito_constatado_<?echo $i;?>' value='<? echo $defeito_constatado;?>' />
					<input type='checkbox' name='defeito_constatado_<? echo $i;?>' value='<?echo $defeito_constatado;?>' <? echo $check;?>> <? echo $codigo." - ".$descricao;?>
				</label>


		<?	if($resto == 0){ ?>

				</div>
				<!-- Margem -->
				<div class="span2"></div>
			</div>
			<div class="row-fluid">
				<!-- Margem -->
				<div class="span2"></div>
				<div class="span4">
		<?	}else{ ?>

				</div>
				<div class="span4">
		<?	}
		}
	}
?>
	<input type='hidden' name='qtde_item' value='<? echo $i;?>'>
	</div>
</div>
<? } ?>

	<br/>
	<div class="row-fluid">
		<!-- Margem -->
		<div class="span4"></div>

		<div class="span4 tac">
			<button type="button" class="btn"  onclick="submitForm($(this).parents('form'),'gravar');" alt="Gravar formulário" ><?=traduz('Gravar')?></button>
			<? if(strlen($familia) > 0){ ?>
				<button type="button" class="btn btn-danger"  onclick="submitForm($(this).parents('form'),'deletar');" alt="Apagar familia" ><?=traduz('Apagar')?></button>
			<? } ?>
				<input type='hidden' id="btn_click" name='btn_acao' value='' />

		</div>
		<!-- Margem -->
		<div class="span4"></div>
	</div>
	<br/>
<?
if(strlen($familia) > 0){
	 $sql = "SELECT    tbl_familia.descricao AS descricao_familia,
					  tbl_produto.produto                       ,";
	if($login_fabrica == 96){
		$sql .= "tbl_produto.referencia_fabrica,";
	}
	if($login_fabrica == 19){
		$sql .= "tbl_produto.parametros_adicionais,
				 tbl_linha.nome,
				 tbl_produto.linha ,";
	}
	$sql .= " tbl_produto.referencia        ,
		      tbl_produto.descricao
			FROM      tbl_produto
			LEFT JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia
			LEFT JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
			WHERE     tbl_familia.fabrica = $login_fabrica
			AND       tbl_produto.fabrica_i = $login_fabrica
			AND       tbl_produto.familia = $familia";

	if ($login_fabrica == 14) $sql .= " AND tbl_produto.abre_os = true";

	$sql .= " ORDER BY  tbl_produto.descricao;";
	$res = @pg_query($con,$sql);

	if (pg_num_rows($res) > 0){
			$linha = pg_fetch_result($res,0,linha);
		?>

		<table class='table table-striped table-bordered table-hover table-fixed scroll'>
			<thead>
				<tr >
					<th class='titulo_tabela' colspan='<?= ($login_fabrica == 19) ? '3' : '2' ?>'><? echo "Produtos da Família ".pg_fetch_result($res,0,descricao_familia)?></th>
				</tr>
				<tr class='titulo_coluna'>
		<?
		if($login_fabrica == 96 || $login_fabrica == 15){ ?>
					<th >Referência</th>
					<th>Nome Comercial</th>
		<? }else{
				if ($login_fabrica == 19) {	?>
					<th>
						<label class='checkbox'>
							<input type="checkbox" name="seleciona_todos" id="seleciona_todos" /><?=traduz('Todos')?>
						</label>
					</th>
				<? } ?>
					<th><?=traduz('Referência')?></th>
					<th><?=traduz('Descrição')?></th>
		<? } ?>
				</tr>
			</thead>
			<tbody>

		<? for ($i = 0 ; $i < @pg_num_rows($res) ; $i++) {
			$produto       = trim(pg_fetch_result($res,$i,'produto'));
			if($login_fabrica == 96){
				$referencia_fabrica    = trim(pg_fetch_result($res,$i,'referencia_fabrica'));
			}
			$referencia    = trim(pg_fetch_result($res,$i,'referencia'));
			$descricao     = trim(pg_fetch_result($res,$i,'descricao'));

			?>

			<tr>
				<?
				if ($login_fabrica == 19) { ?>
					<td class='tac'>
						<input type='checkbox' name='checkbox_<?= $i ?>' class='checkbox_produto' value='<?= $produto ?>' />
					</td>
				<?
				}
				?>
				<td><? echo $referencia;?></td>
			<?if ($login_fabrica == 96) {?>
				<td><? echo $referencia_fabrica;?></td>
			<? } ?>
				<td><a href="<? echo "produto_cadastro.php?produto=".$produto; ?>" target='_blank'> <? echo $descricao;?></a></td>
			</tr>
		<? } ?>
			<? if ($login_fabrica == 19) { ?>
				<input type="hidden" name="qtd_produto" value="<?= $i ?>" />
			<? } ?>
			</tbody>
		</table>

	<? }else if (pg_num_rows($res) == 0){ ?>
		<div class="alert scroll">
			<h4><?=traduz('ESTA FAMÍLIA NÃO POSSUI PRODUTOS CADASTRADOS')?></h4>
		</div>
	<? }
} ?>
</form>
<table class='table table-striped table-bordered table-hover table-fixed'>
	<thead>
		<tr class='titulo_tabela'>
			<th colspan='<?=(in_array($login_fabrica, array(169,170))) ? 7 : 4?>'><?=traduz('Relação das Famílias Cadastradas')?></th>
		</tr>
		<tr class='titulo_coluna'>
			<th><?=traduz('Código')?></th>
			<th><?=traduz('Descrição')?></th>

<?
if ($login_fabrica == 151) {
?>
			<th><?=traduz('Valor da Mão de Obra')?></th>
<?php
}
if ($login_fabrica == 117) {
?>
	<th><?=traduz('Macro-Família')?></th>
<?php
}

if ($login_fabrica == 45){
?>
			<th><?=traduz('Codigo Validação Série')?></th>
<?
}

			if (in_array($login_fabrica, array(169, 170))) {
			?>
				<th><?=traduz('Setor Atividade')?></th>
				<th><?=traduz('OS de Conjunto')?></th>
				<th><?=traduz('Garantia Estendida')?></th>
				<th><?=traduz('RPI')?></th>
			<? } ?>
			<?php if ($login_fabrica == 190) { ?>
				<th><?=traduz('Limite de Horas Trabalhadas')?></th>
			<?php } ?>
			<th><?=traduz('Status')?></th>
			<? if ($login_fabrica == 15) { ?>
				<th><?=traduz('Paga KM')?></th>
			<? } ?>
		</tr>
	</thead>
	<tbody>
<? $sql = "SELECT  tbl_familia.familia  ,
		tbl_familia.descricao        ,
		tbl_familia.mao_de_obra_adicional_distribuidor,
		tbl_familia.mao_de_obra_familia,
		tbl_familia.codigo_familia   ,
		tbl_familia.paga_km          ,
		tbl_familia.codigo_validacao_serie,
		tbl_familia.ativo,
		tbl_familia.deslocamento,
		tbl_familia.black,
		tbl_familia.setor_atividade,
		tbl_familia.bosch_cfa,
		tbl_linha.nome as macro_familia
	FROM    tbl_familia
		LEFT JOIN tbl_linha USING(linha)
	WHERE   tbl_familia.fabrica = $login_fabrica
	AND       tbl_familia.familia not in (2615,2716,2711,2645,2640,2651,2614,2623,2648,2646,2625,2652,2635,2641,2658,2647,2656,2631,2621,2620,2626,2643,2616,2630,2629)
	ORDER BY tbl_familia.ativo DESC, tbl_familia.descricao;";
$res = pg_exec ($con,$sql);

for ($x = 0 ; $x < pg_numrows($res) ; $x++){
	$familia                            = trim(pg_result($res,$x,familia));
	$descricao                          = trim(pg_result($res,$x,descricao));
	$codigo_familia                     = trim(pg_result($res,$x,codigo_familia));
	$codigo_validacao_serie             = trim(pg_result($res,$x,codigo_validacao_serie));
	$ativo                              = trim(pg_result($res,$x,ativo));
	$mao_de_obra_adicional_distribuidor = trim(pg_result($res,$x,mao_de_obra_adicional_distribuidor));
	$mao_de_obra_familia                = trim(pg_result($res,$x,mao_de_obra_familia));
	$paga_km                            = ($login_fabrica == 15) ? trim(pg_result($res,$x,paga_km)) : null;
	$macro_familia                		= trim(pg_result($res,$x,macro_familia));
	$setor_atividade                    = trim(pg_result($res,$x,setor_atividade));

	if ($login_fabrica == 190) {
		$bosch_cfa  = json_decode(trim(pg_result($res,$x,'bosch_cfa')), 1);
		$limite_horas_trabalhadas = isset($bosch_cfa["limite_horas_trabalhadas"]) ? $bosch_cfa["limite_horas_trabalhadas"] : false;
	}

	if (in_array($login_fabrica, array(169,170))) {
		$os_conjunto = pg_fetch_result($res,$x,"deslocamento");
		if($os_conjunto=='t') $os_conjunto = "<img title='".traduz("OS de Conjunto habilitada")."' src='imagens/status_verde.png'>";
		else            $os_conjunto = "<img title='".traduz("OS de Conjunto não habilitada")."' src='imagens/status_vermelho.png'>";

		$garantia_estendida = pg_fetch_result($res,$x,"black");
		if($garantia_estendida==1) $garantia_estendida = "<img title='".traduz("Garantia Estendida habilitada")."' src='imagens/status_verde.png'>";
		else            $garantia_estendida = "<img title='".traduz("Garantia Estendida não habilitada")."' src='imagens/status_vermelho.png'>";

		$rpi = $codigo_validacao_serie;

		if($rpi=='true') $rpi = "<img title='".traduz("OS de Conjunto habilitada")."' src='imagens/status_verde.png'>";
		else          $rpi = "<img title='".traduz("OS de Conjunto não habilitada")."' src='imagens/status_vermelho.png'>";
	}

	$cor = ($x % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

	if($limite_horas_trabalhadas=='true') $limite_horas_trabalhadas = "<img title='".traduz("Sim")."' src='imagens/status_verde.png'>";
	else            $limite_horas_trabalhadas = "<img title='".traduz("Não")."' src='imagens/status_vermelho.png'>";
	if($ativo=='t') $ativo = "<img title='".traduz("Ativo")."' src='imagens/status_verde.png'>";
	else            $ativo = "<img title='".traduz("Inativo")."' src='imagens/status_vermelho.png'>";

	if($paga_km=='t') $paga_km = "<img title='".traduz("Sim")."' src='imagens/status_verde.png'>";
	else              $paga_km = "<img title='".traduz("Não")."' src='imagens/status_vermelho.png'>";
	?>


	<tr >
		<td><? echo $codigo_familia;?></td>
		<td>
			<a href="<? echo $PHP_SELF.'?familia='.$familia;if(isset($semcab)) echo '&semcab=yes';echo '' ?>"><? echo $descricao;?></a>
		</td>
	<?php

	if ($login_fabrica == 151) {
	?>
		<td><?=number_format($mao_de_obra_familia,2,",",".")?></td>
	<?php
	}

	if ($login_fabrica == 117) {
	?>
		<td><?=$macro_familia?></td>
	<?php
	}

	if ($login_fabrica == 45){
	?>
		<td align='left'><?echo $codigo_validacao_serie?></td>
	<?
	}


	if (in_array($login_fabrica, array(169,170))) { ?>
		<td class="tac" ><?= $setor_atividade; ?></td>
		<td class="tac" ><?= $os_conjunto; ?></td>
		<td class="tac" ><?= $garantia_estendida; ?></td>
		<td class="tac"><?=$rpi?></td>
	<? } ?>
	<?php if ($login_fabrica == 190) { ?>
		<td class="tac"><?= $limite_horas_trabalhadas; ?></td>
	<?php } ?>
		<td class="tac"><?= $ativo; ?></td>

	<? if ($pedido_via_distribuidor == 't') { ?>
		<td><?= $mao_de_obra_adicional_distribuidor; ?></td>
		<? }
		if ($login_fabrica == 15) { ?>
		<td align='left'><?= $paga_km;?></td>
	<? } ?>
	</tr>
<? } ?>
	</tbody>
</table>
<? if (!isset($semcab)) include "rodape.php"; ?>
