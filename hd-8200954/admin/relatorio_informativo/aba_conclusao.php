<div align='center' class='form-horizontal tc_formulario'>
    <div class="row row-relatorio">
        <div class='titulo_tabela' style="margin-bottom: 30px;">Conclusão (Fechamento do RI)</div>
        <div class="col-sm-10 col-sm-offset-1">
            <div class="alert alert-info">
                <h5>Reconhecimento do time. Após gravar, o RI será finalizado e não poderá ser alterado.</h5>
            </div>
        </div>
        <div class="col-sm-7">
            <textarea name="ri[conclusao]" class="textarea_ckeditor" rows="6" cols="110" style='font-size:10px;'><?= $dadosRequisicao["ri"]["conclusao"] ?></textarea>
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
            <input data-aba="<?= $codigoAba ?>" type="button" class="btn-submit btn btn-default btn-lg" value="Gravar" style="width: 150px;" />
        </div>
    </div>
	<br /><br />
</div>