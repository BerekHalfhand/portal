portalApp
.filter('dateonly', dateOnly)
.filter('datetime', datetime)
.filter('dateMonth', dateMonth)
.filter('taskTimeOld', taskTimeOld)
.filter('defaultValue', defaultValue)
.filter('status', status)
.filter('sex', sex)
.filter('nl2br', nl2br)
.filter('break2br', break2br)
.filter('htmlsafe', htmlsafe)
.filter('encodeUri', encodeUri)
.filter('orderObjectBy', orderObjectBy)
.filter('addressContact', addressContact)
.filter('addressActual', addressActual)
.filter('addressDelivery', addressDelivery)
.filter('addressLegal', addressLegal)
.filter('passportView', passportView)
.filter('smartLabel', smartLabel)
.filter('extractField', extractField)
.filter('cut_tags', cut_tags)
.filter('translateFieldName', translateFieldName)
.filter('unixtime', unixtime)
.filter('join', join)
.filter('trustAsHtml', trustAsHtml)
.filter('htmlToPlaintext', htmlToPlaintext)
.filter('removeBrokenTags', removeBrokenTags)
.filter('shorten', shorten)
.filter('quoteAuthor', queteAuthor)
.filter('lastChange', lastChange)
.filter('reverse', reverse)
.filter('propsFilter', propsFilter)
.filter('bytes', bytes)
.filter('relevantNotifications', relevantNotifications)
.filter('linkCreate', linkCreate)
.filter('arrOrStrOutput', arrOrStrOutput)
.filter('valueEquals', valueEquals)
.filter('getMskDateTime', getMskDateTime)
.filter('shortName', shortName)
.filter('pluralize', pluralize)
.filter('getShareUserName', getShareUserName)
.filter('objLength', objLength)
.filter('emailValidation', emailValidation)
;

function emailValidation(){
    return function(emails){
        var result = true;
        if(emails){
            var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

            for(var emailKey in emails){
                if(emails[emailKey]){
                    result = re.test(emails[emailKey]);
                    if(!result){
                        return result;
                    }
                }
            }


        }

        return result;
    }
}

function dateOnly(TretoDateTime) {
  return function (date) {
    return moment(TretoDateTime.iso8601.toDate(date)).format('DD.MM.YYYY');
  }
}

function datetime(TretoDateTime) {
  return function (date, short) {
    return TretoDateTime.iso8601.display(date, short);
  }
};

function dateMonth(TretoDateTime) {
    return TretoDateTime.iso8601.displayDateMonth;
};

function taskTimeOld() {
    return function(doc, fieldname) {
        if(parseInt(doc[fieldname+'H']) || parseInt(doc[fieldname+'M'])) {
          return '' + ('0'+(doc[fieldname+'H'] || '0')).slice(-2) + ':' + ('0'+(doc[fieldname+'M'] || '0')).slice(-2) + ':00';
        }
        return '';
    };
};

function defaultValue() {
  return function (input, dfltString) {
    input = input || '';
    dfltString = dfltString || '';
    return input || dfltString;
  }
};

function status(Statuses) {
  this.$get = angular.noop;
  return function (input) {
    input = input || '';
    return Statuses[input];
  }
};

function sex() {
    return function(sex) {
        if(typeof sex == 'undefined' || !sex || sex == '0') {
            return 'не указан';
        }
        return sex == 1 ? 'мужской' : 'женский';
    };
};

function nl2br($sce) {
    return function(msg,is_xhtml) {
        //console.log("nl2br");
        var is_xhtml = is_xhtml || true;
        var breakTag = (is_xhtml) ? '<br />' : '<br>';
        var msg = (msg + '').replace(/\<br\ \/\>\n/g,"\n").replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1'+ breakTag +'$2');
        return $sce.trustAsHtml(msg);
    }
};

function break2br($sce) {
    return function(msg,is_xhtml) {
        if (!msg) return '';
        var is_xhtml = is_xhtml || true;
        var breakTag = (is_xhtml) ? '<br />' : '<br>';
        var msg = msg.replace(/\<break\>\<\/break\>/g, breakTag);
        return $sce.trustAsHtml(msg);
    }
};

function htmlsafe($sce) {
    return function(msg) {
        return String(msg).replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
};

function encodeUri($window) {
  return $window.encodeURIComponent;
};

function orderObjectBy() {
  return function(items, field, reverse) {
    if (!items) return [];
    var keys = Object.keys(items);
    var filtered = [];
    for (var i = keys.length - 1; i >= 0; i--) {
      filtered.push(items[keys[i]]);
    }
    filtered.sort(function (a, b) {
      return (a[field] > b[field] ? 1 : -1);
    });
    if(reverse) filtered.reverse();
    return filtered;
  };
};

/**
 * for contact discus
 * @param ctc contact entiny
 */
function addressContact() {
  return function (ctc, adrType) {
    ctc = ctc || '';
    var result = [];
    var regEn = /[a-z]/i;
    var fields = [
        "AddressZipCode",
        "AddressCityName",
        "AddressStreetName",
        "AddressHouseNumber",
        "AddressBlockNumber",
        "AddressOfficeSuiteNumber"
    ];
    var keys = ["", "г.", "ул.", "д.", "корп.", "кв."];
    fields.forEach(function(item, i) {
      if (ctc[item+"_"+adrType]){
        result.push(ctc[item+"_"+adrType]);
      }
    });

    if (!regEn.test(result.join(","))){
      result = result.map(function(item, i) {
        return keys[i] + item;
      }).join(", ");
    }else{
      result = result.join(", ");
    }
    return result;
  }
};

/**
 * for contact discus
 * @param ctc contact entiny
 */
function addressActual() {
  return function (ctc) {
    ctc = ctc || '';
    var p = {1:'_Actual',2:'_ForDelivery',3:'_ForLegal'};
    return (ctc['AddressZipCode'+p[1]]  ? '' +  ctc['AddressZipCode'+p[1]] : '') + ' ' + (ctc['AddressCityName'+p[1]] ? 'г. ' +  ctc['AddressCityName'+p[1]]  : '') + ' ' + (ctc['AddressStreetName'+p[1]] ? 'ул.' +  ctc['AddressStreetName'+p[1]]  : '') + ' ' + (ctc['AddressHouseNumber'+p[1]] ? 'д. ' +  ctc['AddressHouseNumber'+p[1]]  : '') + ' ' + (ctc['AddressBlockNumber'+p[1]] ? 'корп. ' +  ctc['AddressBlockNumber'+p[1]]  : '') + ' ' + (ctc['AddressOfficeSuiteNumber'+p[1]] ? 'офис ' + ctc['AddressOfficeSuiteNumber'+p[1]]  : '');
  }
};

/**
 * for contact discus
 * @param ctc contact entiny
 */
function addressDelivery() {
  return function (ctc) {
    ctc = ctc || '';
    var p = {1:'_Actual',2:'_ForDelivery',3:'_ForLegal'};
    return (ctc['AddressZipCode'+p[2]]  ? '' +  ctc['AddressZipCode'+p[2]] : '') + ' ' + (ctc['AddressCityName'+p[2]] ? 'г. ' +  ctc['AddressCityName'+p[2]]  : '') + ' ' + (ctc['AddressStreetName'+p[2]] ? 'ул.' +  ctc['AddressStreetName'+p[2]]  : '') + ' ' + (ctc['AddressHouseNumber'+p[2]] ? 'д. ' +  ctc['AddressHouseNumber'+p[2]]  : '') + ' ' + (ctc['AddressBlockNumber'+p[2]] ? 'корп. ' +  ctc['AddressBlockNumber'+p[2]]  : '') + ' ' + (ctc['AddressOfficeSuiteNumber'+p[2]] ? 'офис ' + ctc['AddressOfficeSuiteNumber'+p[2]]  : '');
  }
};

/**
 * for contact discus
 * @param ctc contact entiny
 */
function addressLegal() {
  return function (ctc) {
    ctc = ctc || '';
    var p = {1:'_Actual',2:'_ForDelivery',3:'_ForLegal'};
    return(ctc['AddressZipCode'+p[3]]  ? '' +  ctc['AddressZipCode'+p[3]] : '') + ' ' + (ctc['AddressCityName'+p[3]] ? 'г. ' +  ctc['AddressCityName'+p[3]]  : '') + ' ' + (ctc['AddressStreetName'+p[3]] ? 'ул.' +  ctc['AddressStreetName'+p[3]]  : '') + ' ' + (ctc['AddressHouseNumber'+p[3]] ? 'д. ' +  ctc['AddressHouseNumber'+p[3]]  : '') + ' ' + (ctc['AddressBlockNumber'+p[3]] ? 'корп. ' +  ctc['AddressBlockNumber'+p[3]]  : '') + ' ' + (ctc['AddressOfficeSuiteNumber'+p[3]] ? 'офис ' + ctc['AddressOfficeSuiteNumber'+p[3]]  : '');
  }
};

/**
 * for contact discus
 * @param ctc contact {{contact|passportView}}
 */
function passportView() {
  return function (ctc) {
    ctc = ctc || '';
    return(ctc.PassportSeries  ? 'серия ' +  ctc.PassportSeries : '') + ' ' + (ctc.PassportNubmer ? ', № ' +  ctc.PassportNubmer  : '') + ' ' + (ctc.PassportDateIssued ? ', выдан ' +  ctc.PassportDateIssued  : '') + ' ' + (ctc.PassportIssuedByOrg ? ' ' +  ctc.PassportIssuedByOrg  : '');
  }
};

/**
 * for portal search, reduces long lines
 */
function smartLabel() {
  return function(sourceLabel) {
      sourceLabel = sourceLabel || '';
      sourceLabel = sourceLabel.replace(/<[^>]+>/g, ' ');
      var len = sourceLabel.length;
      var reduced = len >= 300 ? 300 : len;
      var stop  = len >= 300 + 30 ? len - 30 : -1;
      var pre = sourceLabel.substring (0, reduced);
      var suf = stop > 0 ? ' ......... ' + sourceLabel.substring(stop, len) : '';
      return pre + suf;
    };
};

/**
 * for contact editing, converts [{fieldName:'blabla',..},{...}] => ['blabla',..]
 */
function extractField() { return function(input, fieldName) {
    fieldName = fieldName || ''; input = input || [];
    var collect = [];
    for(var index in input) {
        collect.push(input[index][fieldName])
    }
    return collect;
  };
};

function cut_tags() {
  return function(input) {
    var rest = input || '';
    var regExp = new RegExp("\s*<[^>]+>\s*", "gi")
    rest = rest.replace(regExp, ' ');
    return rest;
  };
};

function translateFieldName() {
  return function(input) {
      input = input || '';
      var t = input.toString().toLowerCase().replace(/clear$/,'').replace(/values$/,'');
      // TODO: replace with localize.js
      if(t == 'group') return 'Группы';
      if(t == 'phone') return 'Телефоны';
      if(t == 'email') return 'Эл.почты';
      if(t == 'site') return 'Вебсайты';
      if(t == 'subject') return 'Тема';
      if(t == 'viewbody') return 'Тело';
      if(t == 'bodyweb') return 'Тело';
      if(t == 'body') return 'Тело';
      if(t == 'status') return 'Статус';
      if(t == 'boss') return 'Руководитель';
      if(t == 'action') return 'Действие';
      if(t == 'authorrus') return 'Автор';
      if(t == 'formProcess') return 'Тема';
      if(t == 'decisionBefor') return 'Принятие решения';
      if(t == 'decisionAfter') return 'Принятие решения';
      if(t == 'messagesubject') return 'Тема';
      return input;
    };
};

function unixtime() {
  return function(input) {
    if(input.match(/\+/)) return input;
    if(input.match(/^[0-9]+$/)) {
      var d = new Date();
      d.setTime( input + '000' );
      return d.toString();
    }
    return input;
  }
};

function join() {
  return function(input) {
    if(typeof input == 'string') {
      return input;
    }
    input = input || [], v = []
    if(typeof input == 'object' && input.hasOwnProperty('length')) {
      for(var w in input) { v.push(input[w]) }; return v.join('; ');
    }
    else
    if(typeof input == 'object') {
      for(var w in input) { v.push(w + ': ' + input[w]) }; return v.join('; ');
    }
    return input;
  };
};

function trustAsHtml($sce){
  return function(text) {
      return $sce.trustAsHtml(text);
  };
};

function htmlToPlaintext() {
  return function(text) {
    return String(text).replace(/<[^>]+>/gm, '');
  };
};

function removeBrokenTags() {
  return function(text) {
    text = String(text).replace(/^[^<]+?>/gm, '');
    return String(text).replace(/<[^>]+?$/gm, '');
  };
};

function shorten() {
  return function(text, maxLen, toAppend) {
    toAppend = toAppend || ' ...';
    maxLen = maxLen || (toAppend.length+256);
    if(text && text.length > maxLen) {
      return ( text.substring(0, maxLen - toAppend.length) + '' + toAppend );
    }
    return text;
  };
};

function queteAuthor() {
  return function(text) {
    return String(text).replace(/\[author\]/g, '<blockquote><p class="quote-author">')
                       .replace(/\[\/author\]/g, ' пишет</p>')
                       .replace(/\[quote\]/g, '')
                       .replace(/\[\/quote\]/g, '</blockquote>');
  }
};

function lastChange() {
  return function(text) {
      var x2js = new X2JS(),

      changeLog = x2js.xml_str2json(text);
      if(changeLog){
        if(changeLog.hasOwnProperty('par')){
            changeLog = changeLog.par
        }
        if (changeLog.hasOwnProperty('History')){
            var count = changeLog.History.Entry.length;
            if(count){
                changeLog = changeLog.History.Entry[count-1];
            }
            else {
                changeLog = changeLog.History.Entry;
            }
            if(changeLog) {
                changeLog = (changeLog['_UserShortName'] ? changeLog['_UserShortName'] : changeLog['_UserName']) + ' ' + changeLog['_Date'];
            }
        }
      }
    return String(changeLog);
  }
};

function reverse() {
  return function(items) {
    return items.slice().reverse();
  };
};

function propsFilter() {
  return function(items, props) {
    var out = [];

    if (angular.isArray(items)) {
      items.forEach(function(item) {
        var itemMatches = false;

        var keys = Object.keys(props);
        for (var i = 0; i < keys.length; i++) {
          var prop = keys[i];
          var text = props[prop].toLowerCase();
          if (item[prop].toString().toLowerCase().indexOf(text) !== -1) {
            itemMatches = true;
            break;
          }
        }

        if (itemMatches) {
          out.push(item);
        }
      });
    } else {
      // Let the output be the input untouched
      out = items;
    }

    return out;
  };
};

function bytes() {
  return function(bytes, precision) {
    if (isNaN(parseFloat(bytes)) || !isFinite(bytes)) return '-';
    if (typeof precision === 'undefined') precision = 1;
    var units = ['bytes', 'кБ', 'МБ', 'ГБ', 'ТБ', 'ПБ'],
            number = Math.floor(Math.log(bytes) / Math.log(1024));
    return (bytes / Math.pow(1024, Math.floor(number))).toFixed(precision) +  ' ' + units[number];
  }
};

function relevantNotifications($rootScope) {
  return function (items) {
    var result = {};
    angular.forEach(items, function (value, key) {
      if (!value.notifyWhen)
        result[key] = value;
      else {
        var date = new Date(value.notifyWhen);
//             console.log('date '+date);
//             console.log('serv '+$rootScope.serverTimeMsk);
        if (date < $rootScope.serverTimeMsk)
          result[key] = value;
      }
    });

    return result;
  }
};

function linkCreate(localize) {
  return function (input, locale) {
      var replaceOldLink = function(inp){
          var page, msg;
          if(inp){
              var regexText = inp+'?';

              regexText.replace(/(OpenPage|OpenDocument)(.*?)url\=(.*?)?\/(.*?)(\&|\?)/gim, function (pageStr, page1, page2, page3, page4) {
                  if(typeof page4 != "undefined"){
                      page = '#/discus/'+page4.toUpperCase()+'/';
                  }
              });
              regexText.replace(/(OpenPage|OpenDocument)(.*?)highlightunid\=(.*?)(\&|\?)/gim, function (msgStr, msg1, msg2, msg3) {
                  if(typeof msg3 != "undefined"){
                      msg = '#/discus/'+msg3.toUpperCase()+'/';
                  }
              });
          }
          if(page || msg){
              inp = page?page:msg;
          }

          return inp;
      };

      var locale = locale || "ru";
      var exp = /(^|[^"'])(https?:\/\/[-A-Z0-9+&\[\]@#\/%?=~_|!:,.;]*[-A-Z0-9+&\[\]@#\/%=~_|])/gim;

      input = input.replace(/\&nbsp\;/ig, ' ');

      var result = input.replace(exp, function (str, p1, p2, offset, s) {
          var match = s.substring(offset, s.length).toLowerCase().match(/\<a|\<\/a/ig);
          if(match && match.length && match[0] == '</a'){
              return str;
          }
          else {
              p2 = replaceOldLink(p2);

              return p1+"<a target='_blank' href='"+p2+"' title='"+p2+"'>"+localize('link',null,null, locale)+"</a>";
          }
      });

      //Convert oldPortal link
      result = result.replace(/href\=\"(.*?(OpenPage|OpenDocument).*?)\"/gim, function (str, p1, p2, offset, s) {
          return 'href="'+replaceOldLink(p1)+'"';
      });

      //Convert oldPortal image
      //result = result.replace(/src\=\"(https\:\/\/portal\.treto\.ru\/portal\.nsf)(.*?(\$File).*?)\"/gim, function (str, p1, p2) {
      //    return 'src="'+p2+'"';
      //});

      return result;
  };
};

function arrOrStrOutput() {
  return function (input, def) {
      if(angular.isArray(input)){
          def = typeof(def)!='undefined'?def:'';
          input = input.length>0 ? input.join(', '):def;
      }
      return input;
  }
};

function valueEquals() {
  return function (items, val) {
    var result = {};
    angular.forEach(items, function (value, key) {
      if (value.indexOf(val) > -1)
        result[key] = value;
    });
    return result;
  }
};

function getMskDateTime() {
  return function (input) {
    var result = '';
    if(input && typeof input.date != 'undefined'){
        var mskTime = new Date(input.date);
        result =  ('0'+mskTime.getDate()).slice(-2)+"."+('0'+(mskTime.getMonth()+1)).slice(-2)+"."+mskTime.getFullYear()+" "+('0'+mskTime.getHours()).slice(-2)+":"+('0'+mskTime.getMinutes()).slice(-2);
    }
    return result;
  }
};

function shortName() {
  return function (input, onlyOneLetterOfFirstName){
    arr = input.split(" ");
    return arr[0] + (arr[1] ? (" " + (true === onlyOneLetterOfFirstName ? arr[1].charAt(0)+'.' : arr[1])) : '');
  }
};

function pluralize(Plural) {
  return function(number, pluralGroup) {
    if (isNaN(number) ||
        !pluralGroup ||
        !pluralGroup in Plural)
        return;

    var i = 'many';
    if (number % 100 <= 10 || number % 100 >= 20) {
      var zero = number === 0;
      var count = number.toString();
      count = +count.slice(count.length - 1);
      switch (count) {
        case 0: i = zero ? '0' : 'many'; break;
        case 1: i = 'one'; break;
        case 2:
        case 3:
        case 4: i = 'few'; break;
        default: i = 'many'; break;
      }
    }

    return Plural[pluralGroup][i].replace(/{}/g, number);
  };
};

function getShareUserName($rootScope) {
  return function(shareLogin, shareSource, shortName) {
    shortName = shortName === true;
    //shareSource = shareSource.replace(/remote.team/g, 'ru'); // for dev server
    var tmpName = shareLogin + ' ('+shareSource+')';
    if (!shareLogin || !shareSource ||
        !$rootScope.shareUsers ||
        !$rootScope.shareUsers[shareSource]) return tmpName;
    
    var shareUserName = null;
    var shareUsers = $rootScope.shareUsers[shareSource].data;
    for (var userGroup in shareUsers) {
      for (var i = 0; i < shareUsers[userGroup].data.length; i++) {
        var user = shareUsers[userGroup].data[i];
        if (user.username === shareLogin) {
          shareUserName = [user.LastName,
                           shortName ? user.name.slice(0,1) + '.' : user.name,
                           '('+shareSource+')'].join(' ');
          break;
        }
      }
      if (shareUserName !== null) break;
    }

    return shareUserName === null ? tmpName : shareUserName;
  };
};

// возвращает суммарную длину всех массивов, вложенных в объект
function objLength() {
  return function(object) {
    var count = 0;

    for(var i in object){
      count += object[i].length;
    }
    return count;
  }
};