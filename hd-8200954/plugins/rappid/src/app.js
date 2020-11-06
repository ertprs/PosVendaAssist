// @import jquery.js
// @import lodash.js
// @import backbone.js
// @import geometry.js
// @import vectorizer.js
// @import joint.clean.js
// @import joint.shapes.qad.js
// @import selection.js
// @import factory.js
// @import snippet.js

var app = app || {};
var qad = window.qad || {};
var resolution = [];
var pergunta = [];
var resposta = [];
var instrucao = "";
var pergunta_anterior = "";
var ultima_pergunta = "";
var callcenter = "";

app.AppView = joint.mvc.View.extend({

    el: '#app',

    events: {
        'click #toolbar .add-question': 'addQuestion',
        'click #toolbar .add-answer': 'addAnswer',
        'click #toolbar .preview-dialog': 'previewDialog',
        'click #toolbar .execution-script-json': 'executionScriptJson',
        'click #toolbar .script-json': 'scriptJson',
        'click #toolbar .show-resolution': 'showResolution'
    },

    init: function() {

        this.initializePaper();
        this.initializeSelection();
        this.initializeHalo();
        this.initializeInlineTextEditor();
        //this.initializeTooltips();

        //this.loadExample();
        //this.loadScriptFalha();
    },

    initializeTooltips: function() {

        new joint.ui.Tooltip({
            rootTarget: '#paper',
            target: '.joint-element',
            content: _.bind(function(target) {

                var cell = this.paper.findView(target).model;

                var text = '- Double-click to edit text inline.';
                if (cell.get('type') === 'qad.Question') {
                    text += '<br/><br/>- Connect a port with another Question or an Answer.';
                }

                return  text;

            }, this),
            direction: 'right',
            right: '#paper',
            padding: 20
        });
    },

    initializeInlineTextEditor: function() {

        var cellViewUnderEdit;

        var closeEditor = _.bind(function() {

            if (this.textEditor) {
                this.textEditor.remove();
                // Re-enable dragging after inline editing.
                cellViewUnderEdit.setInteractivity(true);
                this.textEditor = cellViewUnderEdit = undefined;
            }
        }, this);

        this.paper.on('cell:pointerdblclick', function(cellView, evt) {

            // Clean up the old text editor if there was one.
            closeEditor();

            var vTarget = V(evt.target);
            var text;
            var cell = cellView.model;

            switch (cell.get('type')) {

                case 'qad.Question':

                    text = joint.ui.TextEditor.getTextElement(evt.target);
                    if (!text) {
                        break;
                    }
                    if (vTarget.hasClass('body') || V(text).hasClass('question-text')) {

                        text = cellView.$('.question-text')[0];
                        cellView.textEditPath = 'question';

                    } else if (V(text).hasClass('option-text')) {

                        cellView.textEditPath = 'options/' + _.findIndex(cell.get('options'), { id: V(text.parentNode).attr('option-id') }) + '/text';
                        cellView.optionId = V(text.parentNode).attr('option-id');

                    } else if (vTarget.hasClass('option-rect')) {

                        text = V(vTarget.node.parentNode).find('.option-text');
                        cellView.textEditPath = 'options/' + _.findIndex(cell.get('options'), { id: V(vTarget.node.parentNode).attr('option-id') }) + '/text';
                    }
                    break;

                case 'qad.Answer':
                    text = joint.ui.TextEditor.getTextElement(evt.target);
                    cellView.textEditPath = 'answer';
                    break;
            }

            if (text) {

                this.textEditor = new joint.ui.TextEditor({ text: text });
                this.textEditor.render(this.paper.el);

                this.textEditor.on('text:change', function(newText) {

                    var cell = cellViewUnderEdit.model;
                    // TODO: prop() changes options and so options are re-rendered
                    // (they are rendered dynamically).
                    // This means that the `text` SVG element passed to the ui.TextEditor
                    // no longer exists! An exception is thrown subsequently.
                    // What do we do here?
                    cell.prop(cellViewUnderEdit.textEditPath, newText);

                    // A temporary solution or the right one? We just
                    // replace the SVG text element of the textEditor options object with the new one
                    // that was dynamically created as a reaction on the `prop` change.
                    if (cellViewUnderEdit.optionId) {
                        this.textEditor.options.text = cellViewUnderEdit.$('.option.option-' + cellViewUnderEdit.optionId + ' .option-text')[0];
                    }

                }, this);

                cellViewUnderEdit = cellView;
                // Prevent dragging during inline editing.
                cellViewUnderEdit.setInteractivity(false);
            }
        }, this);

        $(document.body).on('click', _.bind(function(evt) {

            var text = joint.ui.TextEditor.getTextElement(evt.target);
            if (this.textEditor && !text) {
                closeEditor();
            }

        }, this));
    },

    initializeHalo: function() {

        this.paper.on('element:pointerup', function(elementView, evt) {

            var halo = new joint.ui.Halo({
                cellView: elementView,
                useModelGeometry: true,
                type: 'toolbar'
            });

            halo.removeHandle('unlink')
                .removeHandle('rotate')
                .removeHandle('fork')
                .removeHandle('link')
                .render();

        }, this);
    },

    initializeSelection: function() {

        var paper = this.paper;
        var graph = this.graph;
        var selection = this.selection = new app.Selection;

        selection.on('add reset', function() {
            var cell = this.selection.first();
            if (cell) {
                this.status('Selection: ' + cell.get('type'));
            } else {
                this.status('Selection emptied.');
            }
        }, this);

        paper.on({
            'element:pointerup': function(elementView) {
                this.selection.reset([elementView.model]);
            },
            'blank:pointerdown': function() {
                this.selection.reset([]);
            }
        }, this);

        graph.on('remove', function() {
            this.selection.reset([]);
        }, this);

        new app.SelectionView({
            model: selection,
            paper: paper
        });

        document.body.addEventListener('keydown', _.bind(function(evt) {

            var code = evt.which || evt.keyCode;
            // Do not remove the element with backspace if we're in inline text editing.
            if ((code === 8 || code === 46) && !this.textEditor && !this.selection.isEmpty()) {
                this.selection.first().remove();
                this.selection.reset([]);
                return false;
            }

            return true;

        }, this), false);
    },

    initializePaper: function() {

        this.paper = new joint.dia.Paper({
            el: this.$('#paper'),
            width: 1280,
            height: 1280,
            gridSize: 10,
            snapLinks: {
                radius: 75
            },
            linkPinning: false,
            multiLinks: false,
            defaultLink: app.Factory.createLink(),
            validateConnection: function(cellViewS, magnetS, cellViewT, magnetT, end, linkView) {
                // Prevent linking from input ports.
                if (magnetS && magnetS.getAttribute('port-group') === 'in') return false;
                // Prevent linking from output ports to input ports within one element.
                if (cellViewS === cellViewT) return false;
                // Prevent linking to input ports.
                return (magnetT && magnetT.getAttribute('port-group') === 'in') || (cellViewS.model.get('type') === 'qad.Question' && cellViewT.model.get('type') === 'qad.Answer');
            },
            validateMagnet: function(cellView, magnet) {
                // Note that this is the default behaviour. Just showing it here for reference.
                return magnet.getAttribute('magnet') !== 'passive';
            }
        });

        this.graph = this.paper.model;
    },

    // Show a message in the statusbar.
    status: function(m) {
        this.$('#statusbar .message').text(m);
    },

    addQuestion: function() {

        app.Factory.createQuestion('Pergunta').addTo(this.graph);
        this.status('Question added.');

        var height = $($("#paper").find("g")[0]).height();

        if (height > 1024) {
            height += 400;
            $("#paper").height(height);
        }
    },

    addAnswer: function() {

        app.Factory.createAnswer('Instru��o').addTo(this.graph);
        this.status('Answer added.');

        var height = $($("#paper").find("g")[0]).height();
        
        if (height > 1024) {
            height += 400;
            $("#paper").height(height);
        }
    },

    previewDialog: function() {
        resolution = [];

        var cell = this.selection.first();
        var dialogJSON = app.Factory.createDialogJSON(this.graph, cell);

        var $background = $('<div/>').addClass('background').on('click', function() {
            $('#preview').empty();
        });

        $('#preview')
            .empty()
            .append([
                $background,
                qad.renderDialog(dialogJSON)
            ])
            .show();
    },

    previewDialogCallcenter: function(dados, tab_atual) {
        resolution = [];
        pergunta = [];
        resposta = [];
        instrucao = "";
        pergunta_anterior = "";
        ultima_pergunta = "";
        callcenter = "callcenter";

        var $background = $('<div/>').addClass('background').on('click', function() {
            // $('[rel="'+tab_atual+'"]').parent().removeClass();
            // $('[rel="'+tab_atual+'"]').parent().addClass('tabs-selected');
            // $('#preview').empty();
        });

        $(document).on('click', ".btn_cancelar", function() {
            $('[rel="'+tab_atual+'"]').parent().removeClass();
            $('[rel="'+tab_atual+'"]').parent().addClass('tabs-selected');
            $('#preview').empty();
            resolution = [];
        });

        $(document).on('click', ".btn_finalizar", function() {
            $('[rel="'+tab_atual+'"]').parent().removeClass();
            $('[rel="'+tab_atual+'"]').parent().addClass('tabs-selected');
            $('#preview').empty();
            window.parent.retorna_resolution(resolution, ultima_pergunta, resposta, instrucao);
        });

        $('#preview')
            .empty()
            .append([
                $background,
                qad.renderDialog(dados)
            ])
            .show();
    },

    // loadExample: function() {
    //     this.graph.fromJSON(
    //         {"cells":[{"type":"qad.Answer","position":{"x":30,"y":230},"size":{"width":396.0333251953125,"height":134},"angle":0,"answer":"Não abrir chamado, orientar o cliente a efetuar\na limpeza do filtro, em caso de dúvidas consultar\no Manual do Proprietário.\n\nEncerrar o atendimento.","id":"761a857a-bfab-42fd-86ea-e307cfc48852","z":2,"attrs":{"text":{"text":" Não abrir chamado, orientar o cliente a efetuar\na limpeza do filtro, em caso de dúvidas consultar\no Manual do Proprietário.\n\nEncerrar o atendimento."}}},{"type":"qad.Answer","position":{"x":900,"y":30},"size":{"width":371.1833190917969,"height":134},"angle":0,"answer":"Orientar o cliente que desobstrua o aparelho,\nem caso de dúvidas consultar o Manual do\nProprietário.\n\nEncerrar o atendimento.","id":"104c3f88-6c46-403e-97c9-64d470313699","z":6,"attrs":{"text":{"text":"Orientar o cliente que desobstrua o aparelho,\nem caso de dúvidas consultar o Manual do\nProprietário.\n\nEncerrar o atendimento."}}},{"type":"qad.Answer","position":{"x":680,"y":420},"size":{"width":487.70001220703125,"height":150.8},"angle":0,"answer":"Informar o cliente que verifque com o reponsável elétrico\nas instalações elétricas. Se o problema persistir retornar\no contato com o mesmo número de protocolo para prosseguir\no atendimento da onde parou.\n\nEncerrar o atendimento.","id":"eccc513c-0e09-4ae9-be3e-e295f7fd8739","z":10,"attrs":{"text":{"text":"Informar o cliente que verifque com o reponsável elétrico\nas instalações elétricas. Se o problema persistir retornar\no contato com o mesmo número de protocolo para prosseguir\no atendimento da onde parou.\n\nEncerrar o atendimento."}}},{"type":"qad.Answer","position":{"x":30,"y":600},"size":{"width":480.6333312988281,"height":167.60000000000002},"angle":0,"answer":"Orientar o cliente que posicione os botões corretamente,\nem caso de dúvidas consultar o manual do Proprietário.\nSe o problemas persistir retornar o contato com o mesmo\nnúmero de protocolo para prosseguir o atendimento da onde\nparou.\n\nEncerrar o atendimento.","id":"a0973176-1583-4005-b099-c76eecf4034b","z":14,"attrs":{"text":{"text":"Orientar o cliente que posicione os botões corretamente,\nem caso de dúvidas consultar o manual do Proprietário.\nSe o problemas persistir retornar o contato com o mesmo\nnúmero de protocolo para prosseguir o atendimento da onde\nparou.\n\nEncerrar o atendimento."}}},{"type":"qad.Answer","position":{"x":350,"y":800},"size":{"width":484.1333312988281,"height":184.4},"angle":0,"answer":"Informar o cliente que será aberto um chamado para verificar\no produto, caso seja um problema de instalação ou erro de\ndimencionamento de carga térmica, a garantia não cobre\neste tipo de reparo conforme o certificado de garantia.\n\nAbrir um atendimento de garantia da fábrica.\n\nEncerrar o atendimento.","id":"10e4950e-9d6c-41ac-917a-ef258e9f3112","z":16,"attrs":{"text":{"text":"Informar o cliente que será aberto um chamado para verificar\no produto, caso seja um problema de instalação ou erro de\ndimencionamento de carga térmica, a garantia não cobre\neste tipo de reparo conforme o certificado de garantia.\n\nAbrir um atendimento de garantia da fábrica.\n\nEncerrar o atendimento."}}},{"type":"qad.Question","optionHeight":30,"questionHeight":45,"paddingBottom":30,"minWidth":150,"ports":{"groups":{"in":{"position":"top","attrs":{"circle":{"magnet":"passive","stroke":"white","fill":"#9B9BB2","r":14},"text":{"pointerEvents":"none","fontSize":12,"fill":"white"}},"label":{"position":{"name":"left","args":{"x":5}}}},"out":{"position":"right","attrs":{"circle":{"magnet":true,"stroke":"none","fill":"#FAC81A","r":14}}}},"items":[{"group":"in","attrs":{"text":{"text":""}},"id":"da86d4e8-82d7-4cb4-9fb9-3952ccf0e3a3"},{"group":"out","id":"Sim","args":{"y":60}},{"group":"out","id":"Não","args":{"y":90}}]},"position":{"x":30,"y":40},"size":{"width":271.29998779296875,"height":135},"angle":0,"question":"Foi realizado a limpeza do filtro de ar?","inPorts":[{"id":"in","label":""}],"options":[{"id":"Sim","text":"Sim"},{"id":"Não","text":"Não"}],"id":"5ec466ad-4be7-46e2-8211-b42599fbc89b","z":18,"attrs":{".options":{"refY":45},".question-text":{"text":"Foi realizado a limpeza do filtro de ar?"},".option-Sim":{"transform":"translate(0, 0)","dynamic":true},".option-Sim .option-rect":{"height":30,"dynamic":true},".option-Sim .option-text":{"text":"Sim","dynamic":true,"refY":15},".option-Não":{"transform":"translate(0, 30)","dynamic":true},".option-Não .option-rect":{"height":30,"dynamic":true},".option-Não .option-text":{"text":"Não","dynamic":true,"refY":15}}},{"type":"link","source":{"id":"5ec466ad-4be7-46e2-8211-b42599fbc89b","selector":"g:nth-child(7) > circle:nth-child(1)","port":"Não"},"target":{"id":"761a857a-bfab-42fd-86ea-e307cfc48852"},"router":{"name":"manhattan"},"connector":{"name":"rounded"},"id":"f4a7209a-a014-4816-b290-5cfab9e26177","z":20,"attrs":{".marker-target":{"d":"M 10 0 L 0 5 L 10 10 z","fill":"#6a6c8a","stroke":"#6a6c8a"},".connection":{"stroke":"#6a6c8a","strokeWidth":2}}},{"type":"qad.Question","optionHeight":30,"questionHeight":45,"paddingBottom":30,"minWidth":150,"ports":{"groups":{"in":{"position":"top","attrs":{"circle":{"magnet":"passive","stroke":"white","fill":"#9B9BB2","r":14},"text":{"pointerEvents":"none","fontSize":12,"fill":"white"}},"label":{"position":{"name":"left","args":{"x":5}}}},"out":{"position":"right","attrs":{"circle":{"magnet":true,"stroke":"none","fill":"#FAC81A","r":14}}}},"items":[{"group":"in","attrs":{"text":{"text":""}},"id":"b07e2b0b-9833-4b1f-af78-b687795689ab"},{"group":"out","id":"Sim","args":{"y":60}},{"group":"out","id":"Não","args":{"y":90}}]},"position":{"x":460,"y":40},"size":{"width":357.6833190917969,"height":135},"angle":0,"question":"O produto está sendo obstruído por algum objeto?\n(cortinas, móveis, portas, etc)","inPorts":[{"id":"in","label":""}],"options":[{"id":"Sim","text":"Sim"},{"id":"Não","text":"Não"}],"id":"b4cf4080-154d-42f0-9151-b5b7323d5af6","z":21,"attrs":{".options":{"refY":45},".question-text":{"text":"O produto está sendo obstruído por algum objeto?\n(cortinas, móveis, portas, etc)"},".option-Sim":{"transform":"translate(0, 0)","dynamic":true},".option-Sim .option-rect":{"height":30,"dynamic":true},".option-Sim .option-text":{"text":"Sim","dynamic":true,"refY":15},".option-Não":{"transform":"translate(0, 30)","dynamic":true},".option-Não .option-rect":{"height":30,"dynamic":true},".option-Não .option-text":{"text":"Não","dynamic":true,"refY":15}}},{"type":"link","source":{"id":"b4cf4080-154d-42f0-9151-b5b7323d5af6","selector":"g:nth-child(6) > circle:nth-child(1)","port":"Sim"},"target":{"id":"104c3f88-6c46-403e-97c9-64d470313699"},"router":{"name":"manhattan"},"connector":{"name":"rounded"},"id":"348822fa-a740-407e-95a7-3e3be1578670","z":22,"attrs":{".marker-target":{"d":"M 10 0 L 0 5 L 10 10 z","fill":"#6a6c8a","stroke":"#6a6c8a"},".connection":{"stroke":"#6a6c8a","strokeWidth":2}}},{"type":"link","source":{"id":"5ec466ad-4be7-46e2-8211-b42599fbc89b","selector":"g:nth-child(6) > circle:nth-child(1)","port":"Sim"},"target":{"id":"b4cf4080-154d-42f0-9151-b5b7323d5af6","selector":"g:nth-child(5) > circle:nth-child(1)","port":"b07e2b0b-9833-4b1f-af78-b687795689ab"},"router":{"name":"manhattan"},"connector":{"name":"rounded"},"id":"be0d9dc5-d94d-4168-8903-23262cc84d4b","z":24,"attrs":{".marker-target":{"d":"M 10 0 L 0 5 L 10 10 z","fill":"#6a6c8a","stroke":"#6a6c8a"},".connection":{"stroke":"#6a6c8a","strokeWidth":2}}},{"type":"qad.Question","optionHeight":30,"questionHeight":45,"paddingBottom":30,"minWidth":150,"ports":{"groups":{"in":{"position":"top","attrs":{"circle":{"magnet":"passive","stroke":"white","fill":"#9B9BB2","r":14},"text":{"pointerEvents":"none","fontSize":12,"fill":"white"}},"label":{"position":{"name":"left","args":{"x":5}}}},"out":{"position":"right","attrs":{"circle":{"magnet":true,"stroke":"none","fill":"#FAC81A","r":14}}}},"items":[{"group":"in","attrs":{"text":{"text":""}},"id":"1ae8ac3f-5ffe-4943-a66a-1f5638c6941d"},{"group":"out","id":"Sim","args":{"y":60}},{"group":"out","id":"Não","args":{"y":90}}]},"position":{"x":460,"y":230},"size":{"width":345.20001220703125,"height":135},"angle":0,"question":"A tensão do produto é compatível com a tomada\nonde o produto foi ligado?","inPorts":[{"id":"in","label":""}],"options":[{"id":"Sim","text":"Sim"},{"id":"Não","text":"Não"}],"id":"97fca6c8-55f5-4620-8ed4-528bad3483b0","z":25,"attrs":{".options":{"refY":45},".question-text":{"text":"A tensão do produto é compatível com a tomada\nonde o produto foi ligado?"},".option-Sim":{"transform":"translate(0, 0)","dynamic":true},".option-Sim .option-rect":{"height":30,"dynamic":true},".option-Sim .option-text":{"text":"Sim","dynamic":true,"refY":15},".option-Não":{"transform":"translate(0, 30)","dynamic":true},".option-Não .option-rect":{"height":30,"dynamic":true},".option-Não .option-text":{"text":"Não","dynamic":true,"refY":15}}},{"type":"link","source":{"id":"97fca6c8-55f5-4620-8ed4-528bad3483b0","selector":"g:nth-child(7) > circle:nth-child(1)","port":"Não"},"target":{"id":"eccc513c-0e09-4ae9-be3e-e295f7fd8739"},"router":{"name":"manhattan"},"connector":{"name":"rounded"},"id":"711959aa-2e3e-47db-95ee-4d1b9fdabed9","z":26,"attrs":{".marker-target":{"d":"M 10 0 L 0 5 L 10 10 z","fill":"#6a6c8a","stroke":"#6a6c8a"},".connection":{"stroke":"#6a6c8a","strokeWidth":2}}},{"type":"link","source":{"id":"b4cf4080-154d-42f0-9151-b5b7323d5af6","selector":"g:nth-child(7) > circle:nth-child(1)","port":"Não"},"target":{"id":"97fca6c8-55f5-4620-8ed4-528bad3483b0","selector":"g:nth-child(5) > circle:nth-child(1)","port":"1ae8ac3f-5ffe-4943-a66a-1f5638c6941d"},"router":{"name":"manhattan"},"connector":{"name":"rounded"},"id":"53e5f75b-2685-4eba-8bb1-43aa71d203bf","z":28,"attrs":{".marker-target":{"d":"M 10 0 L 0 5 L 10 10 z","fill":"#6a6c8a","stroke":"#6a6c8a"},".connection":{"stroke":"#6a6c8a","strokeWidth":2}}},{"type":"qad.Question","optionHeight":30,"questionHeight":45,"paddingBottom":30,"minWidth":150,"ports":{"groups":{"in":{"position":"top","attrs":{"circle":{"magnet":"passive","stroke":"white","fill":"#9B9BB2","r":14},"text":{"pointerEvents":"none","fontSize":12,"fill":"white"}},"label":{"position":{"name":"left","args":{"x":5}}}},"out":{"position":"right","attrs":{"circle":{"magnet":true,"stroke":"none","fill":"#FAC81A","r":14}}}},"items":[{"group":"in","attrs":{"text":{"text":""}},"id":"15cbbecf-29cb-4eea-9c33-25f4c53d50f5"},{"group":"out","id":"Sim","args":{"y":60}},{"group":"out","id":"Não","args":{"y":90}}]},"position":{"x":30,"y":420},"size":{"width":457.3166809082031,"height":135},"angle":0,"question":"O botão de temperatura está no mais frio, o botão de velocidade\ndo ar está na posição 2 ou 3? (azul)","inPorts":[{"id":"in","label":""}],"options":[{"id":"Sim","text":"Sim"},{"id":"Não","text":"Não"}],"id":"cf3c101d-9cda-49b3-ad5e-3f69d8117bfb","z":29,"attrs":{".options":{"refY":45},".question-text":{"text":"O botão de temperatura está no mais frio, o botão de velocidade\ndo ar está na posição 2 ou 3? (azul)"},".option-Sim":{"transform":"translate(0, 0)","dynamic":true},".option-Sim .option-rect":{"height":30,"dynamic":true},".option-Sim .option-text":{"text":"Sim","dynamic":true,"refY":15},".option-Não":{"transform":"translate(0, 30)","dynamic":true},".option-Não .option-rect":{"height":30,"dynamic":true},".option-Não .option-text":{"text":"Não","dynamic":true,"refY":15}}},{"type":"link","source":{"id":"97fca6c8-55f5-4620-8ed4-528bad3483b0","selector":"g:nth-child(6) > circle:nth-child(1)","port":"Sim"},"target":{"id":"cf3c101d-9cda-49b3-ad5e-3f69d8117bfb","selector":"g:nth-child(5) > circle:nth-child(1)","port":"15cbbecf-29cb-4eea-9c33-25f4c53d50f5"},"router":{"name":"manhattan"},"connector":{"name":"rounded"},"id":"64d48e31-8b88-4776-89c7-a562a8ff1350","z":30,"attrs":{".marker-target":{"d":"M 10 0 L 0 5 L 10 10 z","fill":"#6a6c8a","stroke":"#6a6c8a"},".connection":{"stroke":"#6a6c8a","strokeWidth":2}}},{"type":"link","source":{"id":"cf3c101d-9cda-49b3-ad5e-3f69d8117bfb","selector":"g:nth-child(6) > circle:nth-child(1)","port":"Sim"},"target":{"id":"10e4950e-9d6c-41ac-917a-ef258e9f3112"},"router":{"name":"manhattan"},"connector":{"name":"rounded"},"id":"2ce2b37b-6fe2-4225-a9b7-7952b7747df6","z":31,"attrs":{".marker-target":{"d":"M 10 0 L 0 5 L 10 10 z","fill":"#6a6c8a","stroke":"#6a6c8a"},".connection":{"stroke":"#6a6c8a","strokeWidth":2}}},{"type":"link","source":{"id":"cf3c101d-9cda-49b3-ad5e-3f69d8117bfb","selector":"g:nth-child(7) > circle:nth-child(1)","port":"Não"},"target":{"id":"a0973176-1583-4005-b099-c76eecf4034b"},"router":{"name":"manhattan"},"connector":{"name":"rounded"},"id":"103ea5f6-e007-4037-9a5f-daf14990c81d","z":32,"attrs":{".marker-target":{"d":"M 10 0 L 0 5 L 10 10 z","fill":"#6a6c8a","stroke":"#6a6c8a"},".connection":{"stroke":"#6a6c8a","strokeWidth":2}}}]}
    //     );
    // },

    loadScriptFalha: function(dados) {
        if(dados.length > 0 ){
            dados = JSON.parse(dados);
            this.graph.fromJSON(
                dados
            );
            
            var height = $($("#paper").find("g")[0]).height();
            
            if (height > 1024) {
                height += 400;
                $("#paper").height(height);
            }
        }
    },

    clear: function() {
        this.graph.clear();
    },

    executionScriptJson: function() {
        var cell = this.selection.first();
        var dialogJSON = app.Factory.createDialogJSON(this.graph, cell);
        var snippet = JSON.stringify(dialogJSON);
        var content = '<textarea readonly>' + snippet + '</textarea>';

        var dialog = new joint.ui.Dialog({
            width: '50%',
            height: 200,
            draggable: false,
            title: 'Json de execu��o do script',
            content: content
        });
        dialog.open();
    },

    scriptJson: function() {
        var snippet = JSON.stringify(this.graph.toJSON());
        var content = '<textarea readonly>' + snippet + '</textarea>';

        var dialog = new joint.ui.Dialog({
            width: '50%',
            height: 200,
            draggable: false,
            title: 'Json do script',
            content: content
        });
        dialog.open();
    },

    getExecutionScriptJson: function() {
        var cell = this.selection.first();
        cell = "";
        var dialogJSON = app.Factory.createDialogJSON(this.graph, cell);
        var snippet = JSON.stringify(dialogJSON);
        return snippet;
    },

    getScriptJson: function() {
        var snippet = JSON.stringify(this.graph.toJSON());
        return snippet;
    },

    showResolution: function() {
        alert(resolution.join("\n\n"));
    }
});
