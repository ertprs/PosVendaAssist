/**
 * mt_substr() - multiple substring
 **/
function mt_substring(str, mask) {
  'use strict';
  var ret = '';
  var binRegEx = new RegExp('^[01]{' + str.length + '}$');

  // 1. Binary mask
  if (binRegEx.test(mask)) {

    var binMask = mask.split(''),
        k = 0,
        strLen = str.length;

    while (k < strLen) {
      if (binMask[k++] == '1')
        ret += str[k-1];
    }
    return (ret.length) ? ret : false;
  }

  // 2. simple numeric position
  if (/^\d+$/.test(mask)) {
    var pos = parseInt(mask);

    if (pos < 1 || pos > str.length) {
      return false;
    }
    return str[--pos];
  }

  // 3. multiple numeric positions
  if (/^[0-9,]+$/.test(mask)) {
    var positions = mask.split(',');
    var k = 0, c = 0;

    while (k<str.length) {
      var r = mt_substring(str,positions[k++]);
      if (r === false)
        return false;
      if (r !== undefined)
        ret += r;
    }

    return ret;
  }

  // 4. range
  if(/^(\d+)[-](\d+)$/.test(mask)) {
    var r = mask.match(/^(\d+)[-](\d+)$/);
    var min = r[1], max = r[2];

    if (min < 1 || max < 1 || max > str.length || min > str.length) {
      return false;
    }

    if (max < min) {
      return false; // por enquanto não vai deixar usar essa opção. max += min - 1;
    }

    return str.substr(--min, max-min);
  }

  // 5. Mixed mask (4,6-9)
  if (/^\d[0-9,-]+$/.test(mask)) {
    var positions = mask.split(',');
    var k = 0, c = 0;

    while (k < positions.length) {
      var r = mt_substring(str,positions[k++]);
      if (r === false)
        return false;
      if (r !== undefined)
        ret += r;

      c++;
    }
    return ret;
  }
}

