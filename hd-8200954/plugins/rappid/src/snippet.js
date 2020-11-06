var qad = window.qad || {};

qad.renderDialog = function(dialog, node) {

    this.dialog = dialog;

    if (!node) {

        for (var i = 0; i < dialog.nodes.length; i++) {

            if (dialog.nodes[i].id === dialog.root) {

                node = dialog.nodes[i];
                break;
            }
        }
    }

    if (!node) {

        throw new Error('It is not clear where to go next.');
    }

    if (!this.el) {
        this.el = this.createElement('div', 'qad-dialog');
    }

    // Empty previously rendered dialog.
    this.el.textContent = '';

    switch (node.type) {

        case 'qad.Question':
            resolution.push("Pergunta: "+node.question);
            pergunta.push(node.question);
            this.renderQuestion(node);
            break;
        case 'qad.Answer':
            resolution.push("Instrução: "+node.answer);
            instrucao = node.answer;
            this.renderAnswer(node);
            break;
    }

    this.currentNode = node;

    return this.el;
};

qad.createElement = function(tagName, className) {

    var el = document.createElement(tagName);
    el.setAttribute('class', className);

    return el;
};

qad.renderOption = function(option) {

    var elOption = this.createElement('button', 'qad-option qad-button');
    elOption.textContent = option.text;
    elOption.setAttribute('data-option-id', option.id);

    var self = this;
    elOption.addEventListener('click', function(evt) {

        self.onOptionClick(evt);

    }, false);

    return elOption;
};

qad.renderQuestion = function(node) {

    var elContent  = this.createElement('div', 'qad-content joint-theme-modern joint-dialog modal rendered');
    var elFg       = this.createElement('div', 'fg');
    var elPr       = this.createElement('div', 'pergunta_resposta');
    var elP        = this.createElement('p', 'perguntas');
    var elR        = this.createElement('p', 'respostas');
    var elTitleBar = this.createElement('div', 'titlebar');
    var elBody     = this.createElement('div', 'body');
    var elOptions  = this.createElement('div', 'qad-options');
    var elCancelar = this.createElement('div', 'cancelar');
    var elBtn      = this.createElement('button', 'btn_cancelar');

    elContent.style.maxWidth = 'none';
    elContent.style.width = '50%';
    elTitleBar.innerHTML = "Pergunte ao consumidor: ";
    elBtn.innerHTML = "Cancelar Script";

    var count_pergunta = pergunta.length;
    var posicao = (count_pergunta - 2);

    for (var i = 0; i < count_pergunta; i++) {
        if(pergunta[posicao] != undefined){
            pergunta_anterior = pergunta[posicao];
        }
    }
    if(pergunta_anterior.length > 0){
        elP.innerHTML = "Ultima pergunta: <span class='pergunta_result'>"+pergunta_anterior+"</span>";
    }
    if(resposta.length > 0){
        elR.innerHTML = "Ultima resposta: <span class='resposta_result'>"+resposta+"</span>";
    }


    for (var i = 0; i < node.options.length; i++) {
        elOptions.appendChild(this.renderOption(node.options[i]));
    }

    var elQuestion = this.createElement('h3', 'qad-question-header');
    elQuestion.innerHTML = node.question.replace(/\n/g, "<br />");

    elContent.appendChild(elFg);
    elFg.appendChild(elPr);
    if(callcenter.length > 0){
         elPr.appendChild(elP);
         elPr.appendChild(elR);
    }

    elFg.appendChild(elTitleBar);
    elFg.appendChild(elBody);
    elBody.appendChild(elQuestion);
    elBody.appendChild(elOptions);


    if(callcenter.length > 0){
        elBody.appendChild(elCancelar);
        elCancelar.appendChild(elBtn);
    }

    this.el.appendChild(elContent);
};

qad.renderAnswer = function(node) {

    var elContent  = this.createElement('div', 'qad-content joint-theme-modern joint-dialog modal rendered');
    var elFg       = this.createElement('div', 'fg');
    var elPr       = this.createElement('div', 'pergunta_resposta');
    var elP        = this.createElement('p', 'perguntas');
    var elR        = this.createElement('p', 'respostas');
    var elTitleBar = this.createElement('div', 'titlebar');
    var elBody     = this.createElement('div', 'body');
    var elAnswer   = this.createElement('h3', 'qad-answer-header');
    var elCancelar = this.createElement('div', 'cancelar');
    var elBtn      = this.createElement('button', 'btn_cancelar');
    var elBtnF     = this.createElement('button', 'btn_finalizar');

    elContent.style.maxWidth    = 'none';
    elContent.style.width       = '50%';
    elTitleBar.innerHTML        = "Instrução";
    elBtn.innerHTML             = "Cancelar Script";
    elBtnF.innerHTML            = "Finalizar Script";

    elAnswer.innerHTML = node.answer.replace(/\n/g, "<br />");;

    var count_pergunta  = pergunta.length;
    var posicao         = (count_pergunta - 1);

    for (var i = 0; i < count_pergunta; i++) {
        if(pergunta[posicao] != undefined){
            pergunta_anterior = pergunta[posicao];
            ultima_pergunta = pergunta[posicao];
        }
    }

    if(pergunta_anterior.length > 0){
        elP.innerHTML = "Ultima pergunta: <span class='pergunta_result'>"+pergunta_anterior+"</span>";
    }
    if(resposta.length > 0){
        elR.innerHTML = "Ultima resposta: <span class='resposta_result'>"+resposta+"</span>";
    }

    elContent.appendChild(elFg);
    elFg.appendChild(elPr);

     if(callcenter.length > 0){
        elPr.appendChild(elP);
        elPr.appendChild(elR);
     }

    elFg.appendChild(elTitleBar);
    elFg.appendChild(elBody);
    elBody.appendChild(elAnswer);

    if(callcenter.length > 0){
        elBody.appendChild(elCancelar);
        elCancelar.appendChild(elBtn);
        elCancelar.appendChild(elBtnF);
    }
    this.el.appendChild(elContent);
};

qad.onOptionClick = function(evt) {

    var elOption = evt.target;
    var optionId = elOption.getAttribute('data-option-id');
    
    resposta = elOption.textContent;
    resolution.push("Resposta: "+resposta);

    var outboundLink;
    for (var i = 0; i < this.dialog.links.length; i++) {

        var link = this.dialog.links[i];
        if (link.source.id === this.currentNode.id && link.source.port === optionId) {

            outboundLink = link;
            break;
        }
    }

    if (outboundLink) {

        var nextNode;
        for (var j = 0; j < this.dialog.nodes.length; j++) {

            var node = this.dialog.nodes[j];
            if (node.id === outboundLink.target.id) {

                nextNode = node;
                break;
            }
        }

        if (nextNode) {

            this.renderDialog(this.dialog, nextNode);
        }
    }
};
