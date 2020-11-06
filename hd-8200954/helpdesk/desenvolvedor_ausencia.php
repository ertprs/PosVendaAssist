<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if ($_POST["ajax_remove_ausencia"]) {
	$admin =  $_POST["admin"];

	if (empty($admin)) {
		$retorno = array("erro" => utf8_encode("Desenvolvedor não informado"));
	} else {
		$sql = "UPDATE tbl_admin SET nao_disponivel = null WHERE fabrica = {$login_fabrica} AND admin = {$admin}";
		$res = pg_query($con, $sql);
		
		if (strlen(pg_last_error()) > 0) {
			$retorno = array("erro" => utf8_encode("Erro ao remover ausência"));
		} else {
			$retorno = array("ok" => true);
		}
	}

	exit(json_encode($retorno));
}

if ($_POST["ajax_atualiza_select"]) {
	$sql = "SELECT admin, nome_completo FROM tbl_admin WHERE fabrica = {$login_fabrica} AND ativo IS TRUE AND nao_disponivel IS NULL AND grupo_admin IN (2, 4) AND admin NOT IN (2466) ORDER BY nome_completo ASC";
	$res = pg_query($con, $sql);

	$desenvolvedores = array();

	while ($desenvolvedor = pg_fetch_object($res)) {
		$desenvolvedores[] = array(
			"admin" => $desenvolvedor->admin,
			"nome_completo" => utf8_encode($desenvolvedor->nome_completo)
		);
	}

	exit(json_encode(array("desenvolvedores" => $desenvolvedores)));
}

if ($_POST["ajax_adiciona_ausente"]) {
	$desenvolvedor   = $_POST["desenvolvedor"];
	$motivo_ausencia = utf8_decode(trim($_POST["motivo_ausencia"]));

	if (empty($desenvolvedor)) {
		$retorno = array("erro" => utf8_encode("Desenvolvedor não informado"));
	} else if (empty($motivo_ausencia)) {
		$retorno = array("erro" => utf8_encode("Motivo de ausência não informado"));
	} else {
		$sql = "UPDATE tbl_admin SET nao_disponivel = '{$motivo_ausencia}' WHERE fabrica = {$login_fabrica} AND admin = {$desenvolvedor}";
		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0) {
			$retorno = array("erro" => utf8_encode("Erro ao gravar ausência"));
		} else {
			$sql = "SELECT admin, nome_completo, nao_disponivel AS motivo_ausencia FROM tbl_admin WHERE fabrica = {$login_fabrica} AND admin = {$desenvolvedor}";
			$res = pg_query($con, $sql);

			$admin = pg_fetch_result($res, 0, "admin");
			$nome_completo = pg_fetch_result($res, 0, "nome_completo");
			$motivo_ausencia = pg_fetch_result($res, 0, "motivo_ausencia");

			$retorno = array(
				"admin" => $admin,
				"nome_completo" => utf8_encode($nome_completo),
				"motivo_ausencia" => utf8_encode($motivo_ausencia)
			);
		}
	}

	exit(json_encode($retorno));
}

$TITULO = "Ausência Desenvolvedores";
include "menu.php";

if ($login_fabrica == 10) {
        $sql = "SELECT grupo_admin FROM tbl_admin WHERE fabrica = {$login_fabrica} AND admin = {$login_admin}";
        $res = pg_query($con, $sql);

        $grupo_admin = pg_fetch_result($res, 0, "grupo_admin");

        if (in_array($grupo_admin, array(1)) || in_array($login_admin, array(586))) {
        ?>
                <script>
                
                function removerAusencia(button) {
                        var admin = $(button).data("admin");

                        if (typeof admin != "undefined") {
                                $.ajax({
                                        url: "desenvolvedor_ausencia.php",
                                        type: "post",
                                        data: { ajax_remove_ausencia: true, admin: admin },
                                        beforeSend: function() {
                                                $(button).hide().after("<span>Aguarde...</span>");
                                        },
                                        complete: function(data) {
                                                data = JSON.parse(data.responseText);

						if (data.erro) {
                                                        alert(data.erro);
                                                        $(button).next("span").remove();
                                                        $(button).show();
                                                } else {
                                                        $(button).parents("tr").remove();
                                                        atualizaDesenvolvedorSelect();
                                                }
                                        }
                                });
                        }
                }

                function atualizaDesenvolvedorSelect() {
                        $.ajax({
                                url: "desenvolvedor_ausencia.php",
                                type: "post",
                                data: { ajax_atualiza_select: true },
                                complete: function(data) {
                                        data = JSON.parse(data.responseText);

                                        $("#desenvolvedor > option.padrao").nextAll().remove();;

                                        $.each(data.desenvolvedores, function(key, value) {
                                                $("#desenvolvedor").append("<option value='"+value.admin+"' >"+value.nome_completo+"</option>");
					});
                                }
                        });
                }

                function adiciona_desenvolvedor_ausente() {
                        try {
                                var desenvolvedor   = $("#desenvolvedor").val();
                                var motivo_ausencia = $.trim($("#motivo_ausencia").val());

                                if (typeof desenvolvedor == "undefined" || desenvolvedor.length == 0) {
                                        throw new Error("Selecione o Desenvolvedor");
                                }

                                if (typeof motivo_ausencia == "undefined" || motivo_ausencia.length == 0) {
                                        throw new Error("Informe o motivo da ausência");
                                }

                                $.ajax({
                                        url: "desenvolvedor_ausencia.php",
                                        type: "post",
                                        data: { ajax_adiciona_ausente: true, desenvolvedor: desenvolvedor, motivo_ausencia: motivo_ausencia },
                                        beforeSend: function() {
                                                $("#adiciona_desenvolvedor_ausente").hide().after("<span>Aguarde...</span>");
                                        },
                                        complete: function(data) {
                                                data = JSON.parse(data.responseText);

                                                if (data.erro) {
                                                        alert(data.erro);
                                                } else {
                                                        atualizaDesenvolvedorSelect();
                                                        $("#desenvolvedor_tabela > tbody").append("<tr><td>"+data.nome_completo+"</td><td>"+data.motivo_ausencia+"</td><td style='text-align: center;'><button type='button' class='remover_ausencia' data-admin='"+data.admin+"' onClick='removerAusencia($(this));' >Remover Ausência</button></td></tr>");
                                                }

                                                $("#adiciona_desenvolvedor_ausente").show().next("span").remove();
                                        }
                                });
			} catch(e) {
                                alert(e.message);
                        }
                }

                </script>

                <table style="width: 700px; margin: 0 auto;" >
                        <thead>
                                <tr>
                                        <th colspan="3" style="color: #FFF; background-color: #363B60; text-align: center;" >Adicionar Desenvolvedor Ausente</th>
                                </tr>
                        </thead>
                        <tbody>
                                <tr>
                                        <td>
                                                <select id="desenvolvedor" style="width: 100%;" >
                                                        <option class="padrao" value="" >Desenvolvedor</option>
                                                        <?php
                                                        $sql = "SELECT admin, nome_completo FROM tbl_admin WHERE fabrica = {$login_fabrica} AND grupo_admin IN (2,4) AND ativo IS TRUE AND nao_disponivel IS NULL AND admin NOT IN (2466) ORDER BY nome_completo ASC";
                                                        $res = pg_query($con, $sql);

                                                        if (pg_num_rows($res) > 0) {
                                                                while ($desenvolvedor = pg_fetch_object($res)) {
                                                                        echo "<option value='{$desenvolvedor->admin}' >{$desenvolvedor->nome_completo}</option>";
                                                                }
                                                        }
                                                        ?>
                                                </select>
                                        </td>
                                        <td>
                                                <input type="text" id="motivo_ausencia" placeholder="Motivo ausência" style="width: 100%;" />
                                        </td>
                                        <td style="text-align: center;" >
                                                <button id="adiciona_desenvolvedor_ausente" type="button" onClick="adiciona_desenvolvedor_ausente();" >Adicionar</button>
                                        </td>
                                </tr>
                        </tbody>
		</table>
                <br />

                <table id="desenvolvedor_tabela" style="width: 700px; margin: 0 auto;" >
                        <thead>
                                <tr>
                                        <th colspan="3" style="color: #FFF; background-color: #C17C33; text-align: center;" >Desenvolvedores Ausentes</th>
                                </tr>
                                <tr>
                                        <th style="color: #FFF; background-color: #363B60; text-align: center;" >Desenvolvedor</th>
                                        <th style="color: #FFF; background-color: #363B60; text-align: center;" >Motivo Ausência</th>
                                        <th style="color: #FFF; background-color: #363B60; text-align: center;" >Ação</th>
                                </tr>
                        </thead>
                        <tbody>
                                <?php
                                $sql = "SELECT admin, nome_completo, nao_disponivel FROM tbl_admin WHERE fabrica = $login_fabrica AND grupo_admin IN (2, 4) AND ativo IS TRUE AND nao_disponivel IS NOT NULL";
                                $res = pg_query($con, $sql);

                                if (pg_num_rows($res) > 0) {
                                        while ($desenvolvedor_ausente = pg_fetch_object($res)) {
                                                echo "
                                                        <tr>
                                                                <td>".$desenvolvedor_ausente->nome_completo."</td>
                                                                <td>".$desenvolvedor_ausente->nao_disponivel."</td>
                                                                <td style='text-align: center;' ><button type='button' class='remover_ausencia' data-admin='{$desenvolvedor_ausente->admin}' onClick='removerAusencia($(this));' >Remover Ausência</button></td>
                                                        </tr>
                                                ";
                                        }
                                }
                                ?>
                        </tbody>
                </table>
                <br />
        <?php
        }
}

include "rodape.php";
?>
