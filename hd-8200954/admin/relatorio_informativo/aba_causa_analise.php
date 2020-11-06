<input type="hidden" name="ri_analise[id]" value="<?= $dadosRequisicao["ri_analise"]["id"] ?>" />
<div align='center' class='form-horizontal tc_formulario'>
    <div class="row row-relatorio">
        <div class='titulo_tabela' style="margin-bottom: 30px;">Análise de Causa</div>
        <div class="col-sm-10 col-sm-offset-1">
            <div class="alert alert-info">
                <div class="row">
                    <div class="col-sm-12" style="text-align: center;">
                        <h4><strong>Analisar e identificar a causa raíz do problema:</strong></h4><br />
                    </div>
                    <div class="col-sm-6 col-sm-offset-3">
                        <ul class="quantificar-problema">
                            <li>Preencher o diagrama de causa e efeito</li>
                            <li>Análise dos 5 porquês</li>
                            <li>Anexar fotos, documentos e resultados de testes</li>
                            <li>Identificar a causa raíz do problema</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row row-relatorio">
        <div class='titulo_tabela'>Diagrama de Causa e Efeito</div>
        <div class="row row-relatorio">
            <div class="col-sm-8">
                <div class="row">
                    <div class="col-sm-3 div-causa-textarea">
                        <div class="form-group">
                            <label class="control-label alinhar-esquerda">MATERIAL</label><br />
                            <textarea placeholder="Informe o material utilizado" class="form-control obrigatorio textarea-causa-efeito" rows="5" name="ri_analise[causa_efeito][material]"><?= $dadosRequisicao["ri_analise"]["causa_efeito"]["material"] ?></textarea>
                        </div>
                    </div>
                    <div class="col-sm-3 div-causa-textarea">
                        <div class="form-group">
                            <label class="control-label alinhar-esquerda">MÃO-DE-OBRA</label><br />
                            <textarea placeholder="Descreva a mão de obra" class="form-control obrigatorio textarea-causa-efeito" rows="5" name="ri_analise[causa_efeito][mao_de_obra]"><?= $dadosRequisicao["ri_analise"]["causa_efeito"]["mao_de_obra"] ?></textarea>
                        </div>
                    </div>
                    <div class="col-sm-3 div-causa-textarea">
                        <div class="form-group">
                            <label class="control-label alinhar-esquerda">MÉTODO</label><br />
                            <textarea placeholder="Descreva o método utilizado" class="form-control obrigatorio textarea-causa-efeito" rows="5" name="ri_analise[causa_efeito][metodo]"><?= $dadosRequisicao["ri_analise"]["causa_efeito"]["metodo"] ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <span class="linha-vertical" style="margin-left: 14%;"></span>
                    <span class="linha-vertical" style="margin-left: 28%;"></span>
                    <span class="linha-vertical" style="margin-left: 28%;"></span>
                    <hr style="margin-top: 0 !important;margin-bottom: 0 !important;margin-left: 30px;" />
                    <span class="linha-vertical" style="margin-left: 14%;"></span>
                    <span class="linha-vertical" style="margin-left: 28%;"></span>
                    <span class="linha-vertical" style="margin-left: 28%;"></span>
                </div>
                <div class="row">
                    <div class="col-sm-3 div-causa-textarea">
                        <div class="form-group">
                            <label class="control-label alinhar-esquerda">MÁQUINA</label><br />
                            <textarea placeholder="Informe a máquina utilizada" class="form-control obrigatorio textarea-causa-efeito" rows="5" name="ri_analise[causa_efeito][maquina]"><?= $dadosRequisicao["ri_analise"]["causa_efeito"]["maquina"] ?></textarea>
                        </div>
                    </div>
                    <div class="col-sm-3 div-causa-textarea">
                        <div class="form-group">
                            <label class="control-label alinhar-esquerda">MEIO-AMBIENTE</label><br />
                            <textarea placeholder="Descreva o ambiente" class="form-control obrigatorio textarea-causa-efeito" rows="5" name="ri_analise[causa_efeito][ambiente]"><?= $dadosRequisicao["ri_analise"]["causa_efeito"]["ambiente"] ?></textarea>
                        </div>
                    </div>
                    <div class="col-sm-3 div-causa-textarea">
                        <div class="form-group">
                            <label class="control-label alinhar-esquerda">MEDIÇÃO</label><br />
                            <textarea placeholder="Informe o método de medição" class="form-control obrigatorio textarea-causa-efeito" rows="5" name="ri_analise[causa_efeito][medicao]"><?= $dadosRequisicao["ri_analise"]["causa_efeito"]["medicao"] ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-2" style="padding-top: 80px;margin-left: 10px;">
                <div class="form-group">
                    <label class="control-label alinhar-esquerda">PROBLEMA</label><br />
                    <textarea placeholder="Informe o problema ocorrido" class="form-control obrigatorio textarea-causa-efeito" rows="5" name="ri_analise[causa_efeito][problema]"><?= $dadosRequisicao["ri_analise"]["causa_efeito"]["problema"] ?></textarea>
                </div>
            </div>
        </div>
    </div>
    <div class="row row-relatorio">
        <div class='titulo_tabela'>Análise dos 5 porquês ( <i>Ocorrência</i> e <i>Não-detecção</i> )</div>
        <div class="row row-relatorio">
            <div class="col-sm-12" style="text-align: center;padding-left: 50px">
                <div class="row row-relatorio">
                    <div class="col-sm-11" style="text-align: center;">
                        <h4><strong>Porque da OCORRÊNCIA?</strong></h4>
                    </div>
                </div>
                <div class="row row-relatorio">
                    <?php
                    for ($x=0;$x < 5;$x++) { ?>
                        <div class="col-sm-2 div-causa-textarea">
                            <div class="form-group">
                                <div class="col-sm-10" style="padding-left: 0 !important;padding-right: 0 !important;">
                                    <textarea placeholder="<?= $x + 1 ?>º porque da ocorrência" class="form-control obrigatorio textarea-causa-efeito" rows="5" name="ri_analise[porque_ocorrencia][]"><?= $dadosRequisicao["ri_analise"]["porque_ocorrencia"][$x] ?></textarea>
                                </div>
                                <div class="col-sm-1" style="padding-top: 35px;">
                                    <?php
                                    if ($x < 4) {
                                        echo '<span class="glyphicon glyphicon-arrow-right"></span>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    <?php
                    }
                    ?>
                </div>
                <div class="row row-relatorio">
                    <div class="col-sm-11" style="text-align: center;">
                        <h4><strong>Porque da NÃO DETECÇÃO?</strong></h4>
                    </div>
                </div>
                <div class="row row-relatorio">
                    <?php
                    for ($x=0;$x < 5;$x++) { ?>
                        <div class="col-sm-2 div-causa-textarea">
                            <div class="form-group">
                                <div class="col-sm-10" style="padding-left: 0 !important;padding-right: 0 !important;">
                                    <textarea placeholder="<?= $x + 1 ?>º porque da não detecção" class="form-control obrigatorio textarea-causa-efeito" rows="5" name="ri_analise[porque_nao_deteccao][]"><?= $dadosRequisicao["ri_analise"]["porque_nao_deteccao"][$x] ?></textarea>
                                </div>
                                <div class="col-sm-1" style="padding-top: 35px;">
                                    <?php
                                    if ($x < 4) {
                                        echo '<span class="glyphicon glyphicon-arrow-right"></span>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class="row row-relatorio">
        <div class='titulo_tabela' style="margin-bottom: 30px;">Identificação da Causa Raíz</div>
        <div class="col-sm-7">
            <textarea name="ri_analise[causa_raiz]" class="textarea_ckeditor" rows="6" cols="110" style='font-size:10px;'><?= $dadosRequisicao["ri_analise"]["causa_raiz"] ?></textarea>
        </div>
        <div class="col-sm-5">
            <?php
            include "../box_uploader.php";
            ?>
        </div>
    </div>
    <br />
    <div class="row row-relatorio">
        <div class="col-sm-12" style="text-align: center;">
            <hr />
            <br />
            <input data-aba="<?= $codigoAba ?>" type="button" class="btn-submit btn btn-default btn-lg" value="<?= !empty($dadosRequisicao["ri_analise"]["causa_raiz"]) ? 'Alterar' : 'Gravar' ?>" style="width: 150px;" />
        </div>
    </div>
	<br /><br />
</div>