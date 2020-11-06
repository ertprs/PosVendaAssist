
/* =============================================================================================
 * jsGears Image Uploader v1.0
 *
 * Copyright (c) 2008 Hunter Wu (http://jsgears.com/)
 * Code licensed under the MIT License.
 *
 * @class JSG.imgUploader
 * @Required jquery core
 * @Required jquery.form (http://malsup.com/jquery/form/)
 * =============================================================================================
 */
var JSG = JSG || {};

JSG.imgUploader = function(config) {

  //default config
 	var defConfig = {
    fileLimits: 1,                  //圖檔數量的限制 (-1 是不限制)
    actionUrl: null,                //圖檔上傳的處理程式
    fileInputName: 'myfile',        //檔案輸入框的名稱
    inputContainer: null,           //檔案輸入框的放置位置
    previewContainer: null,         //預覽圖檔的放置位置
    hideInputIfReachLimits: true,   //達到檔案數量限制時是否隱藏輸入框，若否，則採用 disable
    confirmDeleteMsg: 'Confirma exclusão?',
    previewClass: 'JSGImgPreview',
    elementPrefix: 'JSGImgUploader',
    loadingIcon: '../../imagens/loading_indicator_big.gif',
    deleteIcon: '../../imagens/icon_delete.gif',
    outputDelimiter: ':',
    uniqueId: null
 	};
 	config = $.extend(defConfig, config);

  //check containers
  config.inputContainer = $('#' + config.inputContainer);
  if (config.inputContainer.length == 0) {
    alert('Input container not exist!');
    return null;
  }
  config.previewContainer = $('#' + config.previewContainer);
  if (config.previewContainer.length == 0) {
    alert('Preview container not exist!');
    return null;
  }

  //generate unique id
  while (config.uniqueId == null) {
    var tmpId = parseInt(Math.random() * 10000, 10);
    if ($('#' + tmpId + '_form1').length == 0)
      config.uniqueId = tmpId;
  }

  var currentFileCount = 0;
  var currentFormCount = 0;
  var currentFormId = null;
  var files = [];
  /*
  files = [
    {
      available: true,
      ready: false,
      filename: ''
    },
    {
      available: false, //deleted
      ready: true,
      filename: '1.jpg'
    }
  ];
  */

  function toggleInputLimits() {
    if (config.fileLimits == -1 || (currentFileCount < config.fileLimits)) {
      if (config.hideInputIfReachLimits) {
        $('#' + currentFormId).show();
      } else {
        $('#' + currentFormId).children('input').attr('disabled', false);
      }
    } else {
      if (config.hideInputIfReachLimits) {
        $('#' + currentFormId).hide();
      } else {
        $('#' + currentFormId).children('input').attr('disabled', true);
      }
    }
  }

  function generatePreview(cnt) {
    var elmId = config.elementPrefix + config.uniqueId + '_preview' + cnt;
    $('<div class="' + config.previewClass + '" id="' + elmId + '" style="position: relative"></div>')
      .css('backgroundImage', 'url(' + config.loadingIcon + ')')
      .appendTo(config.previewContainer);
  }

  (function generateNewInput() {
    var uploadHandler = function() {
      files.push({
        available: true,
        ready: false,
        filename: ''
      });

      var _seqid = parseInt($(this).attr('seqid'), 10);
      var handleUploadSuccess = function(data) {
        var fileseq = _seqid - 1;
        var elmId = config.elementPrefix + config.uniqueId + '_preview' + _seqid;
        files[fileseq].ready = true;

        //error
        if ('error' in data || !('success' in data)) {
          if ('error' in data)
            alert(data.error);
          else
            alert('unknow error!');

          files[fileseq].available = false;
          --currentFileCount;
          $('#' + elmId).fadeOut();
          toggleInputLimits();
          return;
        }

        files[fileseq].filename = data.success;
        var $deleteIcon = $('<div style="position: absolute; right: 0; top: 0; cursor: pointer"><img src="' + config.deleteIcon + '" /></div>')
          .click(function() {
            if (confirm(config.confirmDeleteMsg)) {
              //remove uploaded file
              files[fileseq].available = false;
              --currentFileCount;
              $(this).parent().fadeOut();
              toggleInputLimits();
            }
          });
        $('#' + elmId)
          .css('backgroundImage', 'url(' + data.success + ')')
          .append($deleteIcon);
      }; //end of handleUploadSuccess

      $(this).parent().ajaxSubmit({success: handleUploadSuccess, dataType: 'json'});
      ++currentFileCount;
      generatePreview(currentFormCount);
      generateNewInput();
      toggleInputLimits();
    }; //end of uploadHandler

    var preFormId = config.elementPrefix + config.uniqueId + '_form' + currentFormCount;
    $('#' + preFormId).hide();

    ++currentFormCount;
    currentFormId = config.elementPrefix + config.uniqueId + '_form' + currentFormCount;
    var currentInputId = config.elementPrefix + config.uniqueId + '_input' + currentFormCount;
    var $fileInput = $('<input type="file" id="' + currentInputId + '" name="' + config.fileInputName + '" />')
      .change(uploadHandler)
      .attr('seqid', currentFormCount);

    $('<form id="' + currentFormId + '" action="' + config.actionUrl + '" method="POST" enctype="multipart/form-data" style="margin: 0 3px 0; display: inline"></form>')
      .append($fileInput)
      .appendTo(config.inputContainer);
  }());

  //public functions
  return {
    isReady: function() {
      var len = files.length;
      for (var i = 0; i < len; ++i)
        if (files[i].available == true && files[i].ready == false)
          return false;
      return true;
    },
    getFiles: function() {
      var len = files.length;
      var filenames = [];
      for (var i = 0; i < len; ++i)
        if (files[i].available == true && files[i].ready == true)
          filenames.push(files[i].filename);
      return filenames.join(config.outputDelimiter);
    }
  };

};

