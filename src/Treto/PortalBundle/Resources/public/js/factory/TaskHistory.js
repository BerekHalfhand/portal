portalApp.factory('TaskHistory', function (BatchHttp, $rootScope, TretoDateTime, localize, Profile) {
  var obj = function (history, metaTasking) {
    var self = this;
    self.profile = new Profile;
    // raw values
    self.current = history ? history : {
      taskId: '',
      type: '',
      oldValue: {},
      value: {},
      flags: {}
    };
    self.priorityNames = {
      0: 'Нет',
      1: 'Низкий',
      2: 'Средний',
      3: 'Высокий',
    };

    self.getShareName = function(domain, username){
      for(var portalDomain in $rootScope.shareUsers){
        if(portalDomain == domain){
          for(var sectionName in $rootScope.shareUsers[portalDomain].data){
            var section = $rootScope.shareUsers[portalDomain].data[sectionName].data;
            for(var empl in section){
              if(section[empl].username == username){
                return section[empl].LastName+ ' '+section[empl].name+' ('+$rootScope.shareUsers[portalDomain].name+')';
              }
            }
          }
        }
      }

      return username?username+' ('+domain+')':'';
    };

    // extract user name from value
    self.userNameFromValue = function (value) {
      if (value.fullNameInRus) {
        nameArr = value.fullNameInRus.split(" ");
        return nameArr[0] + " " + nameArr[1];
      }
      if (value.login && !value.domain && $rootScope.users && $rootScope.users[value.login]) {
        return $rootScope.users[value.login].name;
      }
      if(value.login && value.domain && $rootScope.shareUsers){
        return self.getShareName(value.domain, value.login);
      }
      if (value.fullName) {
        return value.fullName;
      }
      return value.login ? value.login : '';
    }
    self.getAuthor = function () {
      if(self.current.authorLogin && self.current.domain){
        return self.getShareName(self.current.domain, self.current.authorLogin);
      }
      else if (self.current.authorLogin && $rootScope.users && $rootScope.users[self.current.authorLogin]) {
        return self.profile.translateName(self.current.authorLogin);
      }
      else if (self.current.authorLogin) {
        return self.current.authorLogin;
      }
      else if (metaTasking.doc.taskPerformer && metaTasking.doc.taskPerformer[0]) {
        return metaTasking.doc.taskPerformer[0];
      }
      return '';
    }
    //generate text for view
    self.genText = function () {
      var history = self.current;
      if (!history) {
        return '';
      }
      var text = '<span class="grey">' + TretoDateTime.iso8601.display(history.created) + '</span> <span class="black_33">';
      switch (history.type) {
        case 'priority':
          text += self.getAuthor() + (history.oldValue && history.oldValue.weight ? ' изменил приоритет с ' + self.priorityNames[history.oldValue.weight] + ' на ' : ' установил приоритет: ') + self.priorityNames[history.value.weight];
          break;
        case 'taskDateReal':
          if (history.flags && history.flags.initialTimeline)
            text += 'исполнитель установил срок ' + (history.value.end ? TretoDateTime.iso8601.display(history.value.end) : '');
          else
            text += 'подтв. срок ' + TretoDateTime.iso8601.display(history.oldValue.end) + ' изменен на ' + TretoDateTime.iso8601.display(history.value.end);
          break;
        case 'difficulty':
          if (history.flags && history.flags.initialDifficulty)
            text += self.getAuthor() + ' принял просьбу и установил сложность ' + history.value.text;
          else
            text += self.getAuthor() + ' изменил сложность на ' + history.value.text;
          break;
        case 'dateAndDiff':
          if (history.flags && history.flags.initialTimeline)
            text += self.getAuthor() + ' принял просьбу со сроком '+ TretoDateTime.iso8601.display(history.value.end) +' и установил сложность ' + history.value.difficulty;
          else
            text += self.getAuthor() + ' изменил срок на '+ TretoDateTime.iso8601.display(history.value.end) +' и установил сложность ' + history.value.difficulty;
          break;
        case 'taskPerformer':
          var oldUser = self.userNameFromValue(history.oldValue ? history.oldValue : {});
          var user = self.userNameFromValue(history.value);
          text += ((oldUser && oldUser != 'Просьба подвешена') ? 'ответственный ' + oldUser + ' заменен на ' : 'был назначен ответственный: ') + user;
          break;
        case 'checker':
          var oldUser = self.userNameFromValue(history.oldValue ? history.oldValue : {});
          var user = self.userNameFromValue(history.value);
          text += ' Был назначен проверяющий: ' + user;
          break;
        case 'status':
          switch (history.value.type) {
            case 'notify':
              text += self.getAuthor() + ' уведомляет о выполнении.';
              break;
            case 'close':
              text += self.getAuthor() + ' принял выполнение';
              break;
            case 'cancelled':
            case 'cancel':
              text += self.getAuthor() + ' отменил просьбу';
              break;
            case 'wait':
              text += self.getAuthor() + ' подвесил просьбу';
              break;
            default:
              text += ' ' + (history.oldValue && history.oldValue.date ? 'Статус был изменен с ' + localize('task.' + history.oldValue.type) + ' на ' : 'Установлен статус: ') + localize('task.' + history.value.type);
          }
          break;
        case 'toApply':
          text += self.getAuthor() + ' запросил накат изменений. Ответственный: ' + self.profile.translateName(history.value.responsible);
          break;
        case 'toApplyCompleted':
          text += self.profile.translateName(history.value.responsible) + ' уведомил о накате изменений';
          break;
        case 'completed':
          text += self.getAuthor() + ' уведомляет о выполнении просьбы.';
            if (history.value && history.value.checker && history.value.checker != [] && history.value.checker != '')
              text += ' Проверяющий: '+self.profile.translateName(history.value.checker);
          break;
        case 'reject':
          text += self.getAuthor() + ' вернул просьбу.';// Новый срок: ' + TretoDateTime.iso8601.display(history.value.newDate);
          break;
        case 'const':
          text += ' ' + history.value.text;
          break;
        case 'notify':
          text += ' Отправлено уведомление о просрочке руководителю ('+self.profile.translateName(history.value.boss, history.value.domain)+', '+history.value.bossGroup+')';
          break;
        case 'remind':
          text += ' Отправлено уведомление о просрочке исполнителю ('+self.profile.translateName(history.value.performer, history.value.domain)+')';
          break;
        case 'check':
          text += ' ' + 'Отправлена на проверку: ';
          for (key in history.value.checkers) {
            text += self.userNameFromValue(history.value.checkers[key]);
          }
          break;
        default:
          text += ' ' + history.type;
//           console.log(history);
      }
      text += '</span>'
      return text;
    };
    
    self.isNew = function() {
      var history = self.current;
      var created = $rootScope.convertStrISOToObj(history.created);
      var readBy = null;
      if (metaTasking.doc.readBy && metaTasking.doc.readBy[$rootScope.user.username])
        readBy = metaTasking.doc.readBy;
      else if (metaTasking.main_doc.readBy && metaTasking.main_doc.readBy[$rootScope.user.username])
        readBy = metaTasking.main_doc.readBy;
      
      if (readBy && readBy[$rootScope.user.username]) {
        var read = readBy[$rootScope.user.username];
        read = $rootScope.convertReadableStrToObj(read);
        
        if (read < created) return true;
      } else return true;
      
      return false;
    }
    //text for view
    self.text = self.genText();
    self.new = self.isNew();
    //save history item
    self.doSaveCurrent = function (successHandler, notifiedHandler, silent, doc) {
      BatchHttp({method: 'POST', url: 'api/taskHistory/set/' + (self.current._id ? self.current._id : 0), data: {history: self.current, silent: (silent || false)}})
              .then(function (response) {
                if (response.data.success) {
                  if (doc)
                    successHandler && successHandler(doc, null);
                  else
                    successHandler && successHandler(response.data.document, null);
                  
                  notifiedHandler && response.data.notified && notifiedHandler(response.data.notified);
                } else {
                  new Popup('Discus', response.data.message, 'error');
                }
              }, httpErrorHandler);
    };
  }
  return obj;
});

