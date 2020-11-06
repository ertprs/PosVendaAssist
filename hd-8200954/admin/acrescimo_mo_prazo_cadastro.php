<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

$excecao_mobra = (isset($_GET["excecao_mobra"])) ? trim($_GET["excecao_mobra"]) : trim($_POST["excecao_mobra"]);

if (strlen($_POST["btnacao"]) > 0) {
	$btnacao = trim($_POST["btnacao"]);
}

if ($btnacao == "deletar" and strlen($excecao_mobra) > 0 ) {
	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$sql = "DELETE FROM tbl_excecao_mobra
			WHERE  tbl_excecao_mobra.fabrica       = $login_fabrica
			AND    tbl_excecao_mobra.excecao_mobra = $excecao_mobra";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF?msg=Removido com Sucesso!");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if ($btnacao == "gravar") {

	extract($_POST);
	$aux_familia = (strlen($familia) == 0) ? 'null' :  "$familia";

	$upload = $_FILES['upload']['name'];
	if(empty($upload)) {
		if($aux_familia =='null'){
			$msg_erro = "É necessário escolher a Família";
		}

		if (!empty($adicional_mao_de_obra) and strlen($msg_erro)==0) {
			$aux_adicional_mao_de_obra = "'". str_replace(",",".",$adicional_mao_de_obra)."'";
		}else{
			$msg_erro = "É necessário preencher o acréscimo de mão-de-obra";
		}

		if (strlen($qtde_dias) ==0 and strlen($msg_erro)==0) {
			$msg_erro = "É necessário preencher a quantidade de dias";
		}else{
			$aux_qtde_dias = "'". str_replace(",",".",$qtde_dias)."'";
		}
	}

	if(strlen($msg_erro) == 0 and !empty($familia)) {
		$sql = "SELECT excecao_mobra
				FROM tbl_excecao_mobra
				WHERE fabrica = $login_fabrica
				AND   familia = $aux_familia
				AND   qtde_dias = $aux_qtde_dias";
		$res = pg_exec ($con,$sql);
		if(pg_numrows($res) > 0){
			$msg_erro = "Este prazo já está cadastrado para esta Família";
		};
	}

	if(strlen($msg_erro) == 0 and !empty($familia)) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		$sql = "SELECT excecao_mobra
				FROM tbl_excecao_mobra
				WHERE fabrica = $login_fabrica
				AND   familia = $aux_familia
				AND   adicional_mao_de_obra =$aux_adicional_mao_de_obra
				AND   qtde_dias = $aux_qtde_dias";
		$res = pg_exec ($con,$sql);
		$excecao_mobra = (pg_numrows($res) > 0) ? pg_result($res,0,excecao_mobra) : "";

		if (strlen ($msg_erro) == 0) {
			if (strlen($excecao_mobra) == 0) {
				###INSERE NOVO REGISTRO
				$sql = "INSERT INTO tbl_excecao_mobra (
							fabrica              ,
							familia              ,
							adicional_mao_de_obra,
							qtde_dias
						) VALUES (
							$login_fabrica             ,
							$aux_familia               ,
							$aux_adicional_mao_de_obra ,
							$aux_qtde_dias
						);";
			}else{
				###ALTERA REGISTRO
				$sql = "UPDATE  tbl_excecao_mobra SET
								familia                = $aux_familia ,
								adicional_mao_de_obra  = $aux_adicional_mao_de_obra,
								qtde_dias              = $aux_qtde_dias
						WHERE   tbl_excecao_mobra.fabrica       = $login_fabrica
						AND     tbl_excecao_mobra.excecao_mobra = $excecao_mobra;";
			}
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			header ("Location: $PHP_SELF?msg=Gravado com Sucesso!");
			exit;
		}else{
			if (strpos ($msg_erro,"duplicate key violates unique constraint \"tbl_excecao_mobra_unico\"") > 0)
			$msg_erro = "Esta exceção já esta cadastrada e não pode ser duplicada.";
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}

	if(strlen($msg_erro) == 0 and !empty($upload)) {
	        $_UP['pasta']       = '/tmp/';
            $_UP['tamanho']     = 1024 * 1024 * 2; // 2Mb
            $_UP['extensoes']   = array('TXT','txt');
            $_UP['renomeia']    = true;

            $extensao = strtolower(end(explode('.', $_FILES['upload']['name'])));
            if (array_search($extensao, $_UP['extensoes']) === false) {
                $msg_erro[] = "Por favor, envie arquivos com as seguintes extensões: ".implode(',',$_UP['extensoes']);
            }else if ($_UP['tamanho'] < $_FILES['upload']['size']) {
                $msg_erro[] = "O arquivo enviado é muito grande, envie arquivos de até {$_UP['tamanho']}Mb.";
            }else {
                if ($_UP['renomeia'] == true) {
                    $arquivo_final = time().'.'.$extensao;
                } else {
                    $arquivo_final = $_FILES['upload']['name'];
                }

                if (move_uploaded_file($_FILES['upload']['tmp_name'], $_UP['pasta'] . $arquivo_final)) {
                    unset($_POST);
                    $ponteiro = fopen ($_UP['pasta'] . $arquivo_final, "r");

                    $linha = 0;
                    while (!feof ($ponteiro)) {
                        $linha += 1;

                        $dados = fgets($ponteiro, 4096);
						if(strlen(trim($dados)) == 0) {
							continue;
						}
                        $x_linha = sprintf("%03d", $linha);
                        $data = explode(";",$dados);

                        //somente para facilitar e evitar acidente de programação
                        $x_familia   = trim($data[0]);
                        $x_adicional = trim($data[1]);
                        $x_qtde_dias = trim($data[2]);

						$x_adicional = str_replace(".","",$x_adicional);
						$x_adicional = str_replace(",",".",$x_adicional);

						$sql = "SELECT familia FROM tbl_familia where fabrica = $login_fabrica and codigo_familia = '$x_familia'";
						$res = pg_query($con,$sql);
						if(pg_num_rows($res) == 0) {
							$msg_erro_upload[] = "Erro na linha $x_linha, familia não encontrada";
							$familia = null;
						}else{
							$familia = pg_fetch_result($res,0,0);
						}

                        if(count($data) <> 3){
                            $msg_erro_upload[] = "ERRO: Linha {$x_linha}, formato de layout inválido!";
                        }elseif(!empty($familia)){
							$sql = "SELECT excecao_mobra
									FROM tbl_excecao_mobra
									WHERE fabrica = $login_fabrica
									AND   familia = $familia
									AND   adicional_mao_de_obra ='$x_adicional'
									AND   qtde_dias = '$x_qtde_dias'";
							$res = pg_query($con,$sql);

                            if(pg_num_rows($res) == 0){
								$sqlx = "INSERT INTO tbl_excecao_mobra (
											fabrica              ,
											familia              ,
											adicional_mao_de_obra,
											qtde_dias
										) VALUES (
											$login_fabrica             ,
											$familia               ,
											'$x_adicional' ,
											'$x_qtde_dias'
										);";
							}else{
                                $excecao_mobra = pg_fetch_result($res,0,"excecao_mobra");
								$sqlx = "UPDATE  tbl_excecao_mobra SET
												familia                = $x_familia ,
												adicional_mao_de_obra  = '$x_adicional',
												qtde_dias              = '$x_qtde_dias'
										WHERE   tbl_excecao_mobra.fabrica       = $login_fabrica
										AND     tbl_excecao_mobra.excecao_mobra = $excecao_mobra;";
							}
							$resx = pg_query($con,$sqlx);
                        }
                    }
                    fclose ($ponteiro);

                    unlink($_UP['pasta'] . $arquivo_final);

                } else {
					 $msg_erro = "Não foi possível enviar o arquivo, tente novamente";
                }
            }

            if(count($msg_erro_upload) == 0){
                echo "<script type='text/javascript'>window.location = '{$PHP_SELF}?msg=Gravado com Sucesso';</script>";
                exit;
			}else{
				$msg_erro = implode("<br>", $msg_erro_upload);
            }
	}
}

###CARREGA REGISTRO
if (strlen($excecao_mobra) > 0) {
	$sql = "SELECT 	tbl_excecao_mobra.familia    ,
					tbl_excecao_mobra.adicional_mao_de_obra,
					tbl_excecao_mobra.qtde_dias
			FROM    tbl_excecao_mobra
			LEFT JOIN tbl_familia   ON tbl_familia.familia     = tbl_excecao_mobra.familia
			WHERE   tbl_excecao_mobra.fabrica            = $login_fabrica
			AND     tbl_excecao_mobra.excecao_mobra      = $excecao_mobra;";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$familia               = trim(pg_result($res,0,familia));
		$adicional_mao_de_obra = trim(pg_result($res,0,adicional_mao_de_obra));
		$qtde_dias             = trim(pg_result($res,0,qtde_dias));
	}
}
$msg = $_GET['msg'];
$layout_menu = 'financeiro';
$title = "CADASTRAMENTO DE ACRÉSCIMO DE M.O POR PRAZO DE ATENDIMENTO";
include 'cabecalho_new.php';
$plugins = array(
	"price_format",
	"dataTable"
);

include("plugin_loader.php");
?>


    <?php
        if(!empty($msg_erro)){
            if(is_array($msg_erro))
                $msg_erro = implode("<br />",$msg_erro);

            echo "
                <div class='alert alert-error'>
                    <h4>{$msg_erro}</h4>
                </div>";
			$controlgrup = "control-group error";
		}else{
			$controlgrup = "control-group";
		}
    ?>
    <?php
		if(!empty($msg)){
	?>
	<div class="alert alert-success">
		<h4><?echo $msg;$msg="";?></h4>
	</div>
	<?php
		}
	?>
    <div class="row-fluid>">
        <div class="alert">
            Layout do anexo com delimitador 'ponto e vírgula': Código da familia;acrescimo de mão-de-obra;qtde dias<br />
            <strong>Exemplo: 68000;10,2;2</strong>
        </div>
    </div>

	<div class="row">
		<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
	</div>
	<form name='frm_formulario' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center'  enctype='multipart/form-data' class='form-search form-inline tc_formulario' >
		<input type="hidden" name="excecao_mobra" value="<? echo $excecao_mobra ?>">
		<input type="hidden" name="linha" value="<? echo $linha ?>">
		<input type="hidden" name="familia" value="<? echo $familia ?>">

        <div class="titulo_tabela">Cadastro</div><br />

        <div class='row-fluid'>
            <div class='span1'></div>
            <div class='span4'>
				<div class="<? echo $controlgrup ?>">
                    <label class='control-label' for='familia'>Família</label>
                    <div class='controls controls-row'>
						<div class='span4'>
						<h5 class="asteristico">*</h5>
							<?php
                			$sql = "SELECT  *
									FROM    tbl_familia
									WHERE   tbl_familia.fabrica = $login_fabrica
									ORDER BY tbl_familia.descricao;";
							$res = pg_exec ($con,$sql);

							if (pg_numrows($res) > 0) {
								echo "<select style='width: 230px;' id='familia' name='familia'>\n";
								echo "<option value=''>ESCOLHA</option>\n";

								for ($x = 0 ; $x < pg_numrows($res) ; $x++){
									$aux_familia = trim(pg_result($res,$x,familia));
									$aux_descricao  = trim(pg_result($res,$x,descricao));

									echo "<option value='$aux_familia'"; if ($familia == $aux_familia) echo " SELECTED "; echo ">$aux_descricao</option>";
								}
								echo "</select>";
							}
						?>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span3'>
				<div class="<? echo $controlgrup ?>">
                    <label class='control-label' for='adicional_mao_de_obra'>Acréscimo Mão-de-obra</label>
                    <div class='controls controls-row'>
                        <div class='span10'>
							<h5 class="asteristico">*</h5>
                            <input type="text" id="adicional_mao_de_obra" name="adicional_mao_de_obra" class='span10' price='true' value="<? echo priceFormat($adicional_mao_de_obra) ?>" >
                        </div>
                    </div>
                </div>
			</div>
			<div class='span3'>
				<div class="<? echo $controlgrup ?>">
                    <label class='control-label' for='qtde_dias'>Qtde Dias</label>
                    <div class='controls controls-row'>
                        <div class='span8'>
							<h5 class="asteristico">*</h5>
                            <input type="number" id="qtde_dias" name="qtde_dias" class='span8' value="<? echo $qtde_dias ?>" >
                        </div>
                    </div>
                </div>
            </div>
            <div class='span1'></div>
        </div>

        <div class="row-fluid">
            <div class="span2"></div>
            <div class="span6">
                <div class='control-group'>
                    <label class='control-label' for='upload'>Upload de dados</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type="file" id="upload" name="upload" class='span12'>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <p><br/>

		<div class="row-fluid">
			<div class="span12 tac">
				<input type='hidden' name='btnacao' value=''>
				<input type='button' class='btn' value="Gravar" ONCLICK="javascript: if (document.frm_formulario.btnacao.value == '' ) { document.frm_formulario.btnacao.value='gravar' ; document.frm_formulario.submit() } else { alert ('Aguarde submissão') } return false;"/>
				<input type='button' class='btn btn-danger' value="Excluir"  ONCLICK="javascript: if (document.frm_formulario.btnacao.value == '' ) { document.frm_formulario.btnacao.value='deletar' ; document.frm_formulario.submit() } else { alert ('Aguarde submissão') } return false;"/>
				<input type='button' class='btn btn-warning' value="Limpar" ONCLICK="javascript: window.location='<? echo $PHP_SELF ?>'; return false;"/>
			</div>
		</div>

        </p><br/>

    </form>



<?
	$sql = "SELECT  tbl_excecao_mobra.excecao_mobra       ,
				tbl_familia.familia                       ,
				tbl_familia.descricao AS familia_descricao,
				tbl_excecao_mobra.adicional_mao_de_obra   ,
				tbl_excecao_mobra.qtde_dias,
				codigo_familia
			FROM    tbl_excecao_mobra
			LEFT JOIN tbl_familia     ON tbl_familia.familia           = tbl_excecao_mobra.familia AND tbl_familia.fabrica         = $login_fabrica
			WHERE   tbl_excecao_mobra.fabrica = $login_fabrica
			AND     tbl_excecao_mobra.qtde_dias IS NOT NULL
			ORDER BY adicional_mao_de_obra desc,qtde_dias asc";

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<table id='resultado' class='table table-striped table-bordered table-hover table-fixed'>";
		echo "<thead>";
		echo "<tr class='titulo_coluna'> ";

		echo "<td class='tac'>Família</td>";
		echo "<td class='tac'>Acréscimo</td>";
		echo "<td class='tac'>Qtde Dias</td>";

		echo "</tr></thead>";

		for ($z = 0 ; $z < pg_numrows($res) ; $z++){
			$cor = ($z % 2 == 0) ? '#F1F4FA' : '#F7F5F0';

			$excecao_mobra     = trim(pg_result($res,$z,excecao_mobra));
			$codigo_familia    = trim(pg_result($res,$z,codigo_familia));
			$familia           = trim(pg_result($res,$z,familia));
			$familia_descricao = trim(pg_result($res,$z,familia_descricao));
			$adicional_mobra   = trim(pg_result($res,$z,adicional_mao_de_obra));
			$adicional_mobra   = number_format($adicional_mobra,2,",",".");
			$qtde_dias         = trim(pg_result($res,$z,qtde_dias));

			echo "<tr>";
			if(!empty($familia_descricao)) {
				echo "<td class='tac'><a href='$PHP_SELF?excecao_mobra=$excecao_mobra'>$familia_descricao</a></td>";
			}else{
				echo "<td class='tac'><a href='$PHP_SELF?excecao_mobra=$excecao_mobra'>Todas as famílias</a></td>";
			}
			echo "<td class='tar'>$adicional_mobra %</td>";
			echo "<td class='tar'>$qtde_dias</td>";

			echo "</tr>";
		}
		echo "</table>";
		echo '<script>
	                $.dataTableLoad({
	                    table : "#resultado"
	                });
	            </script>';

	}

include "rodape.php";
?>

</body>
</html>
