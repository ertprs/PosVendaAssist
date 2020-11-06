<input type="hidden" name="ri_posvenda[id]" value="<?= $dadosRequisicao["ri_posvenda"]['id'] ?>" />
<input type="hidden" id="transferencia_id" name="ri_transferencia[id]" value="<?= $dadosRequisicao["ri_transferencia"]['id'] ?>" />
<input type="hidden" id="transferencia_admin" name="ri_transferencia[admin]" value="<?= $login_admin ?>" />
<div align='center' class='form-horizontal tc_formulario'>
    <div class="row row-relatorio">
        <div class='titulo_tabela'>Dados RI</div>
        <div class="col-sm-5 col-sm-offset-1">
    		<div class="form-group">
                <label for="data_abertura" class="col-sm-2 control-label">Data Abertura</label>
                <div class="col-sm-5">
                    <input class="form-control obrigatorio" type="text" style="width: 140px;" name="ri[data_abertura]" id="data_abertura" maxlength="10" value= "<?= (!empty($dadosRequisicao["ri"]["data_abertura"])) ? mostra_data($dadosRequisicao["ri"]["data_abertura"]) : date('d/m/Y') ?>" />
                </div>
            </div>
            <div class="form-group">
                <label for="codigo" class="col-sm-2 control-label">Código</label>
                <div class="col-sm-6">
                    <input class="form-control obrigatorio" type="text" style="width: 140px;" id="codigo" maxlength="10" placeholder="Identificador Único" name="ri[codigo]" value= "<?= $dadosRequisicao["ri"]["codigo"] ?>" />
                </div>
            </div>
            <div class="form-group">
                <label for="data_chegada" class="col-sm-2 control-label">Data Chegada</label>
                <div class="col-sm-6">
                    <input placeholder="dd/mm/aaaa" class="form-control" type="text" style="width: 140px;" name="ri[data_chegada]" id="data_chegada" maxlength="10" value="<?= $dadosRequisicao["ri"]["data_chegada"] ?>" />
                </div>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="form-group">
                <label for="admin_nome" class="col-sm-2 control-label">Aberto por</label>
                <div class="col-sm-8">
                    <?php
                    if (!empty($dadosRequisicao["ri"]["admin"])) {
                        $sqlNomeAdm = "SELECT nome_completo FROM tbl_admin WHERE admin = ".$dadosRequisicao["ri"]["admin"];
                        $resNomeAdm = pg_query($con, $sqlNomeAdm);

			$nome_adm = pg_fetch_result($resNomeAdm, 0, "nome_completo");
                    }
                    ?>
                    <input readonly class="form-control" style="width: 200px;" type="text" id="admin_nome" value= "<?= empty($dadosRequisicao["ri"]["admin"]) ? $login_nome_completo : $nome_adm ?>" />
                    <input type="hidden" name="ri[admin]" value="<?= empty($dadosRequisicao["ri"]["admin"]) ? $login_admin : $dadosRequisicao["ri"]["admin"] ?>" />
                </div>
            </div>
            <div class="form-group">
                <label for="titulo" class="col-sm-2 control-label">Título</label>
                <div class="col-sm-8">
                    <input placeholder="Informe um título" class="form-control obrigatorio" type="text" id="titulo" name="ri[titulo]" value= "<?= $dadosRequisicao["ri"]["titulo"] ?>">
                </div>
            </div>
        </div>
    </div>
    <div class="row row-relatorio" id="posvenda_produtos">
        <div class='titulo_tabela'>Dados do Pós-Vendas</div>
        <div class="row row-relatorio">
            <div class="col-sm-5 col-sm-offset-1">
                <div class="form-group">
                    <label for="emitente" class="col-sm-2 control-label">Emitente</label>
                    <div class="col-sm-8">
		    <input placeholder="Informe o Emitente" class="form-control obrigatorio" type="text" id="emitente" name="ri_posvenda[emitente]" value= "<?= empty($dadosRequisicao["ri_posvenda"]["emitente"]) ? $login_nome_completo :  $dadosRequisicao["ri_posvenda"]["emitente"] ?>" />
                    </div>
                </div>
                <div class="form-group">
                    <label for="setor" class="col-sm-2 control-label">Setor</label>
                    <div class="col-sm-8">
                        <input placeholder="Informe um Setor" class="form-control obrigatorio" type="text" id="setor" name="ri_posvenda[setor]" value= "<?= $dadosRequisicao["ri_posvenda"]["setor"] ?>" />
                    </div>
                </div>
                <div class="form-group">
                    <label for="familia" class="col-sm-2 control-label">Família</label>
                    <div class="col-sm-8">
                        <select class="form-control obrigatorio" type="text" id="familia" name="ri[familia]">
                            <option value="">Selecione a Família</option>
                            <?php
                            $sqlFamilia = "SELECT familia, descricao
                                           FROM tbl_familia
                                           WHERE fabrica = {$login_fabrica}
                                           ORDER BY descricao ASC";
                            $resFamilia = pg_query($con, $sqlFamilia);

                            while ($dados = pg_fetch_object($resFamilia)) { 

                                $selected = ($dadosRequisicao["ri"]["familia"] == $dados->familia) ? "selected" : "";

                            ?>

                                <option value="<?= $dados->familia ?>" <?= $selected ?>><?= $dados->descricao ?></option>

                            <?php
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="email" class="col-sm-2 control-label">E-mail</label>
                    <div class="col-sm-8">
                        <input placeholder="informe um e-mail válido" class="form-control obrigatorio" type="email" id="email" name="ri_posvenda[email]" value= "<?= empty($dadosRequisicao["ri_posvenda"]["email"]) ? $login_email : $dadosRequisicao["ri_posvenda"]["email"] ?>" />
                    </div>
                </div>
                <div class="form-group">
                    <label for="qualidade" class="col-sm-2 control-label">Qualidade</label>
                    <div class="col-sm-8">
                        <select class="form-control obrigatorio" name="ri_posvenda[qualidade]" id="qualidade" style="width: 210px;">
                            <option value="">Selecione uma Opção</option>
                            <option value="BSS" <?= $dadosRequisicao["ri_posvenda"]["qualidade"] == "BSS" ? "selected" : "" ?>>BSS</option>
                            <option value="FG" <?= $dadosRequisicao["ri_posvenda"]["qualidade"] == "FG" ? "selected" : "" ?>>FG</option>
                            <option value="Manaus" <?= $dadosRequisicao["ri_posvenda"]["qualidade"] == "Manaus" ? "selected" : "" ?>>Manaus</option>
                        </select>
                    </div>
                </div>

            </div>
        </div>
        <?php
        $listaProdutos = $dadosRequisicao["ri_posvenda_produto"];

        $totalLinhas = count($listaProdutos["produto"]) == 0 ? 1 : count($listaProdutos["produto"]);

        ?>
        <div class="row row-relatorio titulo-produtos">Produtos Específicos</div>
        <div id="lista_produtos">
            <?php
            for($key = 0; $key < $totalLinhas; $key++) {
            ?>
                <input type="hidden" name="ri_posvenda_produto[id][]" value="<?= $listaProdutos['id'][$key] ?>" />
                <div class="row row-relatorio linha-produto" data-contador="<?= $key ?>">
                    <div class="row row-relatorio" style="padding-bottom: 0px !important;">
                        <div class="col-sm-1">
                            <?php
                            $hiddenExcluir = $key == 0 ? "hidden" : "";
                            ?>
                            <div <?= $hiddenExcluir ?> class="form-group div-btn-excluir" style="text-align: center;">
                                <br />
                                <button class="btn btn-danger btn-sm btn-remover-produto" style="position: relative;top: 50px;" type="button">
                                    X
                                </button>
                            </div>
                        </div>

                        <input type="hidden" class="id_produto" name="ri_posvenda_produto[produto][]" value="<?= $listaProdutos['produto'][$key] ?>" />

                        <div class="col-sm-3">
                            <div class="form-group col-sm-12 input-append">
                                <label class="control-label">Referência</label><br />
                                <input placeholder="Digite e clique na lupa" type="text" name="ri_posvenda_produto[referencia][]" class='form-control referencia_produto' maxlength="20" value="<?= $listaProdutos['referencia'][$key] ?>" style="width: 70%;font-size: 12px;" />
                                <span class='add-on' style="width: 35px;" rel="lupa" ><i class='icon-search'></i></span>
                                <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" posicao="<?= $key ?>" />
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group col-sm-12 input-append">
                                <label class="control-label">Descrição</label><br />
                                <input placeholder="Digite e clique na lupa" type="text" name="ri_posvenda_produto[descricao][]" class='form-control descricao_produto' value="<?= $listaProdutos['descricao'][$key] ?>" style="width: 80%;font-size: 12px;" />
                                <span class='add-on' rel="lupa" style="width: 35px;"><i class='icon-search'></i></span>
                                <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" posicao="<?= $key ?>" />
                            </div>
                        </div>
                        <div class="col-sm-1">
                            <div class="form-group col-sm-12">
                                <label class="control-label">Qtde.</label>
                                <input type="number" min="1" name="ri_posvenda_produto[qtde][]" class='form-control qtde' value="<?= $listaProdutos['qtde'][$key] ?>" />
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group col-sm-12">
                                <label class="control-label">Defeito Contatado</label><br />
                                <select class="form-control defeito_constatado" name="ri_posvenda_produto[defeito_constatado][]">
                                    <option value="">Selecione o defeito constatado</option>
                                </select>
                                <input type="hidden" class="defeito_constatado_anterior" value="<?= $listaProdutos["defeito_constatado"][$key] ?>" />
                            </div>
                        </div>
                    </div>
                    <div class="row row-relatorio" style="padding-bottom: 5px !important;">
                        <div class="col-sm-4 col-sm-offset-1">
                            <div class="form-group col-sm-12">
                                <label class="control-label">Observação</label><br />
                                <textarea class="form-control" name="ri_posvenda_produto[observacao][]" placeholder="Digite aqui informações adicionais do produto..."><?= $listaProdutos["observacao"][$key] ?></textarea>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group col-sm-12">
                                <label class="control-label">Disposição</label><br />
                                <textarea class="form-control" name="ri_posvenda_produto[disposicao][]" placeholder="Informe a disposição do produto..."><?= $listaProdutos["disposicao"][$key] ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            <?php
            } ?>
        </div>
        <div class="row row-relatorio" style="background-color: #72849e;width: 95%;margin-left: 2.5% !important;">
            <div class="col-sm-12" style="text-align: center;">
                <br />
                <input type="button" id="btn_nova_linha" class="btn btn-info" value="+ Acrescentar Nova Linha" />
            </div>
        </div>
    </div>
    <div class="row row-relatorio">
        <div class='titulo_tabela' style="margin-bottom: 30px;">Despesas da Garantia</div>
        <div class="col-sm-3">
            <div class="form-group">
                <label for="custo_peca" class="col-sm-2 control-label">Custo da Peça</label>
                <div class="col-sm-6">
                    <input class="form-control money-input valores-custo" type="text" id="custo_peca" placeholder="0,00" name="ri_posvenda[custo_peca]" value= "<?= number_format($dadosRequisicao["ri_posvenda"]["custo_peca"], 2, ",", ".") ?>" />
                </div>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                <label for="valor_frete" class="col-sm-2 control-label">Valor Frete</label>
                <div class="col-sm-6">
                    <input class="form-control money-input valores-custo" type="text" id="valor_frete" placeholder="0,00" name="ri_posvenda[valor_frete]" value= "<?= number_format($dadosRequisicao["ri_posvenda"]["valor_frete"], 2, ",", ".") ?>" />
                </div>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                <label for="mao_de_obra" class="col-sm-2 control-label">Mão de Obra</label>
                <div class="col-sm-6">
                    <input class="form-control money-input valores-custo" type="text" id="mao_de_obra" placeholder="0,00" name="ri_posvenda[mao_de_obra]" value= "<?= number_format($dadosRequisicao["ri_posvenda"]["mao_de_obra"], 2, ",", ".") ?>" />
                </div>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                <label for="total" class="col-sm-2 control-label">Total</label>
                <div class="col-sm-6">
                    <input readonly class="form-control" type="text" id="total" placeholder="0,00" name="ri_posvenda[total]" value= "<?= number_format($dadosRequisicao["ri_posvenda"]["total"], 2, ",", ".") ?>" />
                </div>
            </div>
        </div>
    </div>
    <div class="row row-relatorio">
        <div class='titulo_tabela' style="margin-bottom: 30px;">Descrição do Problema</div>
        <div class="col-sm-7">
            <textarea name="ri_posvenda[descricao_problema]" class="textarea_ckeditor" rows="6" cols="80" style='font-size:10px; width: 80%;'><?= $dadosRequisicao["ri_posvenda"]["descricao_problema"] ;?></textarea>
        </div>
        <div class="col-sm-5">
            <?php
            include "../box_uploader.php";
            ?>
        </div>
    </div>
    <div class="row row-relatorio">
        <div class="col-sm-12" style="text-align: center;">
            <hr />
            <br />
            <input data-aba="<?= $codigoAba ?>" type="button" class="btn-submit btn btn-default btn-lg" value="<?= !empty($dadosRequisicao['ri_posvenda']['id']) ? 'Alterar' : 'Gravar' ?>" style="width: 150px;" />
        </div>
    </div>
    <br /><br />
</div>
