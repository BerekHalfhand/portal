portalApp.factory('ListDiscus', function (BatchHttp)
{
  return function() {
    var self = this;
    self.cache = {};
    self.set = function(hash, value) { self.cache[hash] = value; }
    self.get = function(hash) { return self.cache[hash] || []; }
    self.loading = {};

    self.byCategory = function(categories, callback) {
      if (categories) {
        callback(self.get('c'+categories));
        self.loading[categories] = true;
        BatchHttp({method: 'GET', url: 'api/discussion/list', params: {categories: categories}}).then(function(response) {
          self.loading[categories] = false;
          self.set('c'+categories, response.data);
          callback(response.data);
        });
      }
    };
    self.byCategoryLimited = function(categories, offset, limit, callback) {
      if (categories) {
        //callback(self.get('cl'+categories+'_'+limit));
        self.loading[categories] = true;
        BatchHttp({method: 'GET', url: 'api/discussion/list', params: {categories: categories, offset: offset, limit: limit}}).then(function(response) {
          //self.set('cl'+categories+'_'+limit, response.data);
          self.loading[categories] = false;
          callback && callback(response.data);
        });
      }
    };
    self.byCategoryFromDate = function(categories, fromDate, callback) {
      if (categories) {
        callback(self.get('cd'+categories+'_'+fromDate));
        self.loading[categories] = true;
        BatchHttp({method: 'GET', url: 'api/discussion/list', params: {categories: categories, fromDate: fromDate}}).then(function(response) {
          self.loading[categories] = false;
          self.set('cd'+categories+'_'+fromDate, response.data);
          callback(response.data);
        });
      }
    };
    self.byCategoryFromDateLimited = function(categories, fromDate, limit, callback, withComments) {
      if (categories) {
        callback(self.get('cd'+categories+'_'+fromDate));
        self.loading[categories] = true;
        BatchHttp({method: 'GET', url: 'api/discussion/list', params: {categories: categories, fromDate: fromDate, limit: limit, withComments:withComments}}).then(function(response) {
          self.loading[categories] = false;
          self.set('cd'+categories+'_'+fromDate, response.data);
          callback(response.data);
        });
      }
    };
    self.lastLimited = function(limit, callback) {
      if (limit) {
        callback(self.get('ll'));
        self.loading['Новые темы'] = true;
        BatchHttp({method: 'GET', url: 'api/discussion/list', params: {limit: limit}}).then(function(response) {
          self.loading['Новые темы'] = false;
          self.set('ll', response.data);
          callback(response.data);
        });
      }
    };
    self.getVoting = function(callback){
      callback(self.get('voting'));
      self.loading['voting'] = true;
      BatchHttp({method: 'GET', url: 'api/portal/voting'}).then(function(response) {
        self.loading['voting'] = false;
        self.set('voting', response.data);
        callback(response.data);
      });
    };
    self.byTypeLimited = function(type, offset, limit, callback) {
      if (type) {
        offset = offset ? offset : 0;
        if (self.get('tl'+type+'_'+limit).length){
          callback(self.get('tl'+type+'_'+limit));
        }
        self.loading[type] = true;
        BatchHttp({method: 'GET', url: 'api/discussion/list', params: {type: type, offset: offset, limit: limit}}).then(function(response) {
          //self.set('tl'+type+'_'+limit, response.data);
          self.loading[type] = false;
          callback(response.data);
        });
      }
    };
    self.byTypeFromDateLimited = function(type, fromDate, limit, callback) {
      if (type) {
        callback(self.get('tl'+type+'_'+limit));
        self.loading[type] = true;
        BatchHttp({method: 'GET', url: 'api/discussion/list', params: {type: type, fromDate: fromDate, limit: limit}}).then(function(response) {
          self.loading[type] = false;
          callback(response.data);
        });
      }
    };
    self.getWaitPerformerTasks = function(offset, limit, callback) {
      if (limit) {
        callback && callback(self.get('waitPerf'));
        BatchHttp({method: 'GET', url: 'api/discussion/list', params: {limit: limit, waitperformer: "1"}}).then(function(response) {
          self.set('waitPerf', response.data);
          callback && callback(response.data);
        });
      }
    }
  }
});

/** Voting factory object */
portalApp.factory('Voting', function(BatchHttp, $rootScope, $timeout, $state, Popup, Colors, localize, TretoDateTime)
{
  var obj = function(doc) {
    var self = this;
    self.doc = doc;
    self.voted = []; // [0, 2, 1]
    self.counts = []; // [666, 22, 0]
    self.maxCount = 0;
    self.isOldVoting = false;
    self.oldVoting = false;
    self.closeDate = '';
    self.isWatched = false;
    self.Math = Math;

    if (self.doc && self.doc.watchedBy)
       self.isWatched = self.doc.watchedBy.indexOf($rootScope.user.username) != -1;

    self.peopleVoted = function() {
      var n = 0;
      for(var i in self.doc.answers) {
        if (self.doc.answers[i][0] !== 0xFFFFFF)
          n++;
      }
      word = n%10;
      if (word > 1 && word < 5)
        n += ' человека';
      else
        n += ' человек';
      return n;
    }

    self.notVoted = function() {
      var res = [];
      angular.forEach(self.doc.security.privileges.vote, function(sec) {
        if (sec['username'] && (!self.doc.answers[sec['username']] || self.doc.answers[sec['username']].length == 0)) {
          res.push(sec['username']);
        }
      });
      return res;
    }

    self.isOldVoting = function() {
      self.oldVoting = (self.doc.answers instanceof Array);
      return self.oldVoting;
    };

    self.generateVoted = function() {
      if (doc.PeriodPoll) {
        self.closeDate = TretoDateTime.iso8601.toDateTime(doc.created);
        self.closeDate.setDate(self.closeDate.getDate() + doc.PeriodPoll*1);
        self.closeDate = TretoDateTime.iso8601.fromDateTime(self.closeDate);
      }
      self.voted = new Array(self.doc.AnswersData.length);
      self.counts = [];
      if(!self.doc.answers) { self.doc.answers = {}; }

      if(self.isOldVoting(self.doc)) { // OLD-style voting
        self.voted = ['disabled'];
        self.doc.answers.every(function(a,i) {
          var xbreak = false;
          self.voted[i] = false;
          self.counts[i] = 0;
          if(a == '-' || a == '') { return true; }
          var voters = a.split(',');
          voters.every(function(v) {
            self.counts[i]++;
            if(v == ($rootScope.user.portalData.LastName+' '+$rootScope.user.portalData.name)) {
              self.voted[i] = true;
              xbreak = true;
              return false;
            }
            return true;
          });
          self.maxCount += self.counts[i];
          return !xbreak;
        });

      } else { // NEW-style voting
        if(! self.doc.answers[$rootScope.user.username]) {
          self.doc.answers[$rootScope.user.username] = [];
          self.voted = [];
        } else {
          self.voted = self.doc.answers[$rootScope.user.username];
        }
        self.doc.AnswersData.forEach(function(a) {
          self.counts.push(0);
          if (a.split("|")[1]) self.doc.withAttach = true;
        });
        self.maxCount = 0;
        for(var username in self.doc.answers) { // for everyone
          self.doc.answers[username].forEach(function(answerDataId) { // for every answer
            if(self.counts.length <= answerDataId) { return; }
            self.counts[answerDataId] += 1/self.doc.answers[username].length;
            self.maxCount += 1/self.doc.answers[username].length;
          });
        }
      }
    };

    self.toggleAnswer = function(answerId) {
      if(self.doc.AnswersLim <= 1) { self.setAnswer(answerId); return; }
      var a = self.doc.answers[$rootScope.user.username].indexOf(answerId);
      if(a > -1) {
        self.doc.answers[$rootScope.user.username].remove(a);
      } else {
        if(self.doc.answers[$rootScope.user.username].length >= self.doc.AnswersLim) {
          new Popup('Voting', localize('limitReached', 'voting', self.doc), 'error');
        } else {
          self.doc.answers[$rootScope.user.username].push(answerId);
        }
      }
    };

    self.setAnswer = function(answerId) {
      self.doc.answers[$rootScope.user.username] = [answerId];
    };
    self.isAnswer = function(answerId) {
      return self.doc.answers[$rootScope.user.username].indexOf(answerId) > -1;
    };

    self.vote = function(abandon) {
      if(abandon) {
        self.doc.answers[$rootScope.user.username] = [0xFFFFFF];
      }
      if (self.doc.answers[$rootScope.user.username].isEmpty()) {
        new Popup('Voting', 'Нужно выбрать, за что голосовать', 'warning');
        return;
      }
      self.voted = self.doc.answers[$rootScope.user.username];
      if($rootScope.$state.current.name==='body.notifications'){
        if (!$rootScope.dismissNotifications) $rootScope.dismissNotifications = [];
        $rootScope.dismissNotifications.push(self.doc.unid);
      }
      
      var data = {'answers': self.voted};
      if ($rootScope.notifExp && $rootScope.notifExp.TimeISO[doc.parentID]) {
        data['timeISO'] = $rootScope.notifExp.TimeISO[doc.parentID];
        data['time'] = $rootScope.notifExp.Time[doc.parentID];
      }
      
      BatchHttp({method: 'POST', url: 'api/discussion/vote/'+self.doc.unid, data: data })
          .then(function(response) {
            if(response.data.success) {
              self.generateVoted();

              if ($state.current.name == 'body.notifications') {
                $timeout(function() {
                  $state.go('body.notifications', null, {reload: true});
                }, 1000);
              };

            } else {
              self.voted = [];
              new Popup('Voting', response.data.message, 'error');
            }
          }, httpErrorHandler);
    };

    self.watchVote = function() {
      BatchHttp({method: 'POST', url: 'api/discussion/watchVote/'+self.doc.unid })
          .then(function(response) {
            if(response.data.success) {
              self.doc._meta.voting.isWatched = response.data.watching;
              self.doc.watchedBy = response.data.watchedBy;
            } else {
              new Popup('Voting', response.data.message, 'error');
            }
          }, httpErrorHandler);
    };

    self.loadVotingList = function(offset, limit, handler /* function(list) */) {
      BatchHttp({method: 'GET', url: '/api/portal/voting', params: { 'offset': (offset||0), 'limit': (limit||20) } })
        .then(function(response) {
          if(response.data.length) {
            handler && handler(response.data);
          }
        }, httpErrorHandler);
    };

    // constructor
    self.doc && self.generateVoted();

  };
  return obj;
});

/** Tasking factory object */
portalApp.factory('Tasking', function(BatchHttp, $rootScope, $state, $timeout, $log, Popup, Security, TretoDateTime, TaskHistory)
{
  var hours = [0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23];
  var minutes = [0,5,10,15,20,25,30,35,40,45,50,55];

  var obj = function(doc, main_doc) {
    var self = this;
    self.doc = doc;
    self.main_doc = main_doc || doc;
    self.saved = true;
    self.performers = [];
    self.sharePerformers = {};
    self.timeSelect = { "hours": hours, "minutes": minutes };
    self.today = new Date();
    self.difficultySelect = ["1 Легко","2 Просто","3 Средне","4 Нормально",
      "5 Сложно","6 Трудно","7 Напряжённо","8 Практически невозможно"];
    self.changes = []; // typeDoc = 'changes'
    self.task = []; // typeDoc = 'task'
    self.result = []; // typeDoc = 'result'
    self.allLine = [];
    self.history = [];
    self.parseHistory = function(){
      self.history = [];
      for(key in self.doc.taskHistories){
          self.history.push(new TaskHistory(self.doc.taskHistories[key],self));
      }
    }
    self.parseHistory();
    self.parseOther = function(){
      //console.dir(self.doc.taskOther);
      for(key in self.doc.taskOther){
        if (self.doc.taskOther[key].typeDoc === 'result')
          self.result.push(self.doc.taskOther[key]);
        else if (self.doc.taskOther[key].typeDoc === 'task')
          self.task.push(self.doc.taskOther[key]);
      }
    }
    self.parseOther();

    self.taskAction = function(doc, code, refresh, additionalData, successHandler) {
      if(!self.saved) { return; }
      self.saved = false;
      var meta = doc._meta;
      doc._meta = null;
      delete self.isCompleted;

      if ($state.current.name == 'body.notifications') {
        $rootScope.show.notifLoading = true;
        refresh = true;
        if ($rootScope.notifExp && $rootScope.notifExp.TimeISO[doc.parentID]) {
          additionalData['timeISO'] = $rootScope.notifExp.TimeISO[doc.parentID];
          additionalData['time'] = $rootScope.notifExp.Time[doc.parentID];
        }
      }
      var oldSubjectID = doc.subjectID;
      refresh && self.refreshNotifications();
      BatchHttp({method: 'POST', url: 'api/task/action/'+doc.unid+'/'+code, data: {additionalData: additionalData}})
      .then(function(response) {
        self.saved = true;
        if(response.data.success) {
          if(response.data.task.subjectID == doc.unid && oldSubjectID != response.data.task.subjectID){
            $rootScope.$state.go('body.discus', { id: doc.unid, type: ''}, {reload:true});
          }
          angular.copy(response.data.task, doc);
          doc.taskHistories = response.data.histories;
          doc._meta = meta;
          doc._meta && doc._meta.tasking ? doc._meta.tasking.parseHistory() : null;
          meta = null;
          successHandler && successHandler();
        } else {
          new Popup('Discus', response.data.message, 'error', null, function(){$state.reload();});
        }
      }, httpErrorHandler);
    };

    self.refreshNotifications = function() {
      if ($state.current.name == 'body.notifications') {
        $rootScope.user.waitForUpdateNotif = true;
      };
    };

    self.setPerformer = function(username, whichDoc) {
      if(!whichDoc) { whichDoc = self.doc; }
      var sec = new Security(whichDoc);
      if(! $rootScope.mynameis(whichDoc.taskPerformerLat)) {
        sec.removePrivilege('write', 'username', whichDoc.taskPerformerLat);
        if(sec.hasPrivilege('read','all')) {
          sec.removePrivilege('read', 'username', whichDoc.taskPerformerLat);
        }
      }
      whichDoc.taskPerformerLat = username;
      sec.addPrivilege('write','username',username);
      sec.addPrivilege('read','username',username);
      sec.addPrivilege('subscribed','username',username);
    };

    self.setCheckerLat = function(user) { //отдать на проверку
      var data = {user: user, expectedStatus: self.doc.TaskStateCurrent}
      self.taskAction(self.doc, 20, true, data);
    };

    self.removePerformer = function(u) {
      var i = self.performers.indexOf(u);
      if(i >= 0) {
        self.performers.remove(i);
      }
    };

    self.addPerformer = function(u) {
      var i = self.performers.indexOf(u);
      if(i < 0) {
        self.performers.push(u);
      }
    };

    self.completed = function() {
      if (typeof self.isCompleted == 'undefined') {
//         $log.log('tasking.completed');
        self.isCompleted = false;
        angular.forEach(self.doc.taskHistories, function(hist) {
          if (hist.type == 'completed') self.isCompleted = true;
          if (hist.type == 'reject' || hist.type == 'taskPerformer') self.isCompleted = false;
        })
        return self.isCompleted;
      } else return self.isCompleted;
    }

    self.determineSection = function() {
      var list = ['Отдел разработки портала', 'Отдел разработки сайта', 'Отдел разработки 1С'];
      var section = $rootScope.user.portalData.section;
      for (var i in section) {
        for (var j in list) {
          if (section[i] == list[j]) return list[j];
        }
      }
      return false;
    }

    self.changePerformer = function(perf, sharePerf) { //сменить исполнителя
      var savePerformer = function() {
        var data = {
          performer: perf,
          sharePerformer: typeof sharePerf != 'undefined'?sharePerf:false,
          expectedStatus: self.doc.TaskStateCurrent
        };
        self.taskAction(self.doc, 3, true, data);
      }

      if (self.doc.TaskStateCurrent == 10 || self.doc.TaskStateCurrent == 35) {
        self.reject(null, savePerformer, true);
      } else {
        savePerformer();
      }
    };

    self.existSharePerformer = function(obj){
      for(var key in obj){
        for(var i in obj[key]){
          return true;
        }
      }

      return false;
    };

    self.confirmTimeline = function(dEndFinish) { //сменить сроки
      var data = {difficulty: self.doc.Difficulty,
                  dEndFinish: $rootScope.convertObjDateToStr(dEndFinish),
                  expectedStatus: self.doc.TaskStateCurrent};

      self.taskAction(self.doc, 5, true, data);
    };

    self.changePriority = function(priority) { //сменить приоритет
      var data = {priority: priority, expectedStatus: self.doc.TaskStateCurrent};

      if (self.doc.Priority != priority)
        self.taskAction(self.doc, 7, true, data);
    }

    self.complete = function() { //уведомить об исполнении
      var data = {expectedStatus: self.doc.TaskStateCurrent};
      if((self.doc.Author == $rootScope.user.portalData.FullNameRaw ||
          self.doc.authorLogin == 'portalrobot' ||
          self.doc.authorLogin == $rootScope.user.portalData.Login)
          && !self.doc.shareAuthorLogin){
        self.taskAction(self.doc, 10, false, data, self.close);
      }else{
        self.taskAction(self.doc, 10, true, data);
      }
    };

    self.toApply = function() { //запросить накат
      if (!doc._meta.programmerSection) return false;
      var data = {section: doc._meta.programmerSection, expectedStatus: self.doc.TaskStateCurrent};
      self.taskAction(self.doc, 12, true, data);
    }

    self.toApplyComplete = function() { //уведомить о выполнении наката
      if (!doc._meta.programmerSection) return false;
      var data = {section: doc._meta.programmerSection, expectedStatus: self.doc.TaskStateCurrent};

      if(self.doc.authorLogin == self.doc.taskPerformerLat[0] || self.doc.authorLogin == 'portalrobot'){
        self.taskAction(self.doc, 13, true, data, self.close);
      }else{
        self.taskAction(self.doc, 13, true, data, self.complete);
      }
    }

    self.reject = function(messagebbUnid, handler, doNotRefresh) { //вернуть
      var data = {messagebbUnid: messagebbUnid, expectedStatus: self.doc.TaskStateCurrent}
      self.taskAction(self.doc, 15, !doNotRefresh, data, handler);
    };

    self.close = function() { //принять
      if($rootScope.uploader) delete $rootScope.uploader;
      onOk = function() {
        var data = {expectedStatus: self.doc.TaskStateCurrent};

        self.taskAction(self.doc, 25, true, data);
      }
      if (!self.completed() && self.doc.authorLogin != self.doc.taskPerformerLat[0]){
        Popup("Просьба", "Вы действительно хотите принять просьбу? Исполнитель еще не уведомил о выполнении.", '', true, onOk, function(){});
      }else{
        onOk();
      }
    }

    self.suspend = function() { //подвесить
      var data = {expectedStatus: self.doc.TaskStateCurrent};
      onOk = function() {
        self.taskAction(self.doc, 30, true, data);
      }

      Popup("Просьба", "Внимание! Подвешивая посьбу, вы признаете что не можете сами ее выполнить и не знаете, кому передать. \
                        Так она консервируется в ожидании нового исполнителя. Точно подвесить?", '', true, onOk, function(){});
    };

    self.cancel = function() { //отменить
      var data = {expectedStatus: self.doc.TaskStateCurrent};
      onOk = function() {
        self.taskAction(self.doc, 35, true, data);
      }

      Popup("Просьба", "Вы действительно хотите отменить просьбу?", '', true, onOk, function(){});

    }

  };
  return obj;
});

/** Discussion factory object */
portalApp.factory('DiscusInstance',
function(BatchHttp, $location, Popup, DeputyPopup, TutorPopup, Socket, $state, $window, $rootScope, $log, $http, $mdDialog, $mdBottomSheet,
         Profile, Dictionary, Voting, Tasking, Security, $timeout, Adaptation, Viewport, Notificator, UserSettings,
         TretoDateTime, translit, GUID, $uibModal, Mail, $filter, localize, Contact, Question, TaskHistory, Scroll)
{
  var instanceCount = 0;
  var obj = function() {
    var self = this;
    self.instanceId = instanceCount++;
    self.main_doc = null;
    self.current = null;
    self.comments = [];
    self.sectionDict = null;
    self.emplSectionDict = null;
    self.selectedParticipants = [];
    self.tempParticipants = [];
    self.tempShareParticipants = [];
    self.tretoDateTime = TretoDateTime;
    self.participantsList = {sections: {}, checked: false, saved: false, sectionsLength: 0};
    self.vacancyRegionDict = null;
    self.roleDict = new Dictionary('role', true);
    self.saved = true;
    self.progress = 0;
    self.profile = new Profile;
    self.participantsModal = {};
    self.displayParticipants = false;
    self.isPublic = true;
    self.expanded = { all: true };
    self.expandedFrom = '';
    self.expandedLimit = 50;
    self.newPosts = {count: 0};
    self.isContact = false;
    self.isBlog = false;
    self.quote = '';
    self.subject = '';
    self.fastAnswer = [];
    self.genders = [{key:0, value:'Женский'},{key:1, value:'Мужской'},{key:undefined, value:'Не указано'}];
    self.private = false;
    self.discusUsers = {};
    self.usrTyping = {};
    self.include = {};
    self.shareSecurity = {};
    self.readDocsExternalHandler = null;
    self.include.shownThreadParticipants = {
      read: [],
      unread: [],
      subscribed: [],
      unsubscribed: [],
      shareSubscribed: [],
      refresh: function () {
        self.include.shownThreadParticipants.read =         self.getParticipants('read');
        self.include.shownThreadParticipants.unread =       self.getParticipants('unread');
        self.include.shownThreadParticipants.subscribed =   self.getParticipants('subscribed');
        self.include.shownThreadParticipants.unsubscribed = self.getParticipants('unsubscribed');
        var shareParticipants = self.getShareParticipants(self.main_doc.shareSecurity, 'subscribed');
        var shareUnsubscribed = self.getShareParticipants(self.main_doc.shareSecurity, 'unsubscribed');
        self.include.shownThreadParticipants.shareSubscribed = [];
        self.include.shownThreadParticipants.shareUnsubscribed = [];

        if(shareParticipants){
          for(var domain in shareParticipants){
            for(var i in shareParticipants[domain]){
              self.include.shownThreadParticipants.shareSubscribed.push({username:shareParticipants[domain][i], domain:domain});
            }
          }
        }

        if(shareUnsubscribed){
          for(var domain in shareUnsubscribed){
            for(var i in shareUnsubscribed[domain]){
              self.include.shownThreadParticipants.shareUnsubscribed.push({username:shareUnsubscribed[domain][i], domain:domain});
            }
          }
        }

        if(self.main_doc.form == "Contact" &&
           self.main_doc.Group &&
           self.main_doc.Group.indexOf('Фабрики') > -1 &&
           self.main_doc.ContactStatus &&
           self.main_doc.ContactStatus.indexOf('11') > -1){

         function setAutoNitif(dic){
             var personLogin = dic.getRecordValue('Уведомления по фабрикам');

             if(personLogin){
               var match = false;

               for(var i in self.include.shownThreadParticipants.subscribed){
                 if(self.include.shownThreadParticipants.subscribed[i].username == personLogin){
                   match = true;
                   break;
                 }
               }

               if(!match){
                 self.include.shownThreadParticipants.subscribed.push({
                   username:personLogin,
                   autoNotif:'(сотрудник подписан на все фабрики)'
                 });
               }
             }
         }

         if($rootScope.autoTaskDict && $rootScope.autoTaskDict.records.length > 0){
           setAutoNitif($rootScope.autoTaskDict);
         }
         else {
           $rootScope.autoTaskDict = new Dictionary('AutoTaskPersons', true, false, true, function(dic){setAutoNitif(dic);});
         }
        }
      }
    };

    self.getMain = function(){
      return this.main_doc;
    };

    $rootScope.getDocUri = function(whichDoc) {
      if (!window.location.origin) {
      //fix for IE
        window.location.origin = window.location.protocol + "//" + window.location.hostname + (window.location.port ? ':' + window.location.port: '');
      }
      var id = whichDoc.unid;
      if ($state.current.name == 'body.notifications' && whichDoc.urgency != undefined) id = whichDoc.parentUnid;
      var url = document.location.origin + document.location.pathname
      + $state.href('body.discus',{ id: id, type: (whichDoc.form==='Contact'?'contact':'') });
      var r;
      if (window.clipboardData && window.clipboardData.setData) {
        r = clipboardData.setData("Text", url);
      } else if (document.queryCommandSupported && document.queryCommandSupported("copy")) {
        var textarea = document.createElement("textarea");
        textarea.textContent = url;
        textarea.style.position = "fixed";
        document.body.appendChild(textarea);
        textarea.select();
        try {
          r = document.execCommand("copy");
        } catch (ex) {
          r = false;
        } finally {
          document.body.removeChild(textarea);
        }
      }
      if(r){
        alert('Готово! Ссылка скопирована в буфер.');
      }else{
        return prompt('Document URL:', url);
      }


    };

    $rootScope.devInfo = function(whichDoc) {
      new Popup('DevInfo', angular.toJson(whichDoc, true), 'notify');
    }

    $rootScope.getAttachmentUri = function(attachmentItem) {
      return prompt('Attachment URL', document.location.origin + attachmentItem.link);
    };

    $rootScope.sendToChat = function(whichDoc) {
      var p = new Profile();
      p.findUserById(whichDoc.Author, function(u) {
        if(u) {
          $window.open('https://chat.treto.ru/jwchat/skvirel.html?open&login='+$rootScope.user.username+
              '&with='+u.username+'&subj='+whichDoc.unid+'|'+whichDoc.subject+'|portal.nsf',
              '_blank');
        } else {
          new Popup('Discus', p.error, 'error');
        }
      });
    };

    self.getGender = function(key) {
      var result = 'Не указано';
      self.genders.every(function(o,i) { if(key == o.key) { result = o.value; return false; } return true; });
      return result;
    }

    self.sortShareByName = function(share){
      var newShare = {};

      for(var participants in share){
        if($rootScope.shareUsers[share[participants].domain]){
          var portalName = $rootScope.shareUsers[share[participants].domain].name;
        }

        portalName = portalName?portalName:share[participants].domain;
        if(!newShare[portalName]){
          newShare[portalName] = [];
        }

        newShare[portalName].push(share[participants]);
      }

      return newShare;
    };

    self.getByUnid = function(unid, remote) {
      var result = null;
      if(!self.main_doc) { return null; }
      if(self.main_doc.unid == unid) {
        return self.main_doc;
      } else {
        self.comments.every(function(c) {
          if(c.unid == unid) {
            result = c;
            return false;
          }
          return true;
        });
      }
      if (remote && !result) {
        self.getDocFromServerByUnid(unid, function (doc) {
          if (!self.tasksSubjects) self.tasksSubjects = [];
          self.tasksSubjects[unid] = doc.subject;
          return doc;
        })
      }else{
        return result;
      }
    };

    self.doSaveCurrent = function(successHandler, silent, explicitEditing) {
      BatchHttp({method: 'POST', url: 'api/discussion/set/'+self.current._id, data: { document: self.current, silent: (silent || false), explicitEditing: (explicitEditing || false) } })
          .then(function(response) {
            if(response.data.success) {
              successHandler && successHandler(response.data.document);
              if(response.data.takeOut){
                $rootScope.$state.go('body.discus', { id: response.data.takeOut, type: ''}, {reload:true});
              }
            } else {
              new Popup('Discus', response.data.message, 'error', null, function(){$state.reload();});
            }
          }, httpErrorHandler);
    };

    self.shareToBackend = function(obj){
      var sharePerformers = [];
      for(var domain in obj){
        for(var i in obj[domain]){
          sharePerformers.push({domain:domain, login:obj[domain][i]});
        }
      }

      return sharePerformers;
    };

    self.saveCurrent = function(explicitEditing) {
      if(!(self.saved && self.current)) { return; }
      var isPublic = false;
      for (var i in self.current.security.privileges.read) {
        if (self.current.security.privileges.read[i].role && self.current.security.privileges.read[i].role == 'all') {
          isPublic = true;
          break;
        }
      }

      self.current.security.privileges.subscribed = [];
      if (isPublic) {
        self.addParticipant('all', 'role', self.current);
      } else {
        self.current.security.privileges.read = [];
      }

      self.addParticipant($rootScope.user.username, 'username', self.current, null, null, true);

      if (self.tempParticipants.length > 0) {
        for (key in self.tempParticipants) {
          self.addParticipant(self.tempParticipants[key], 'username', self.current);
        }
        self.tempParticipants = [];
        self.saveParticipants(self.current);
      }

      self.saved = false;

      if (self.current.form == 'formTask') {
        if(self.current._meta && self.current._meta.tasking){
          self.current.TaskPerformers = self.current._meta.tasking.performers;
          if(Object.keys(self.current._meta.tasking.sharePerformers).length){
            if(self.main_doc && self.main_doc.form && $rootScope.shareForms.indexOf(self.main_doc.form) == -1){
              new Popup('Внимание!', 'Просьба будет вынесена из главного документа.', 'warning');
            }

            self.current.TaskSharePerformers = self.shareToBackend(self.current._meta.tasking.sharePerformers);
          }
        }
      }

      if (self.current.form == 'formVoting') {
        angular.forEach(self.current.AnswersData, function(value, key) {
          if (self.current.AnswersPictures[key]){
            self.current.AnswersData[key] += "|" + self.current.AnswersPictures[key];
          }
        })
      };

      self.current._meta = null;
      self.doSaveCurrent(function() {
        self.saved = true;

        if ($state.current.name == 'body.notifications' && (self.current.form == 'message' || self.current.form == 'messagebb')) {
          $rootScope.notificationsDiscus = {};
          $state.go('body.notifications', null, {reload: true});
        } else {
          if(self.current.form === 'formVoting' && self.current.ShowOnIndex == 1)
            $state.go('body.index', null, {reload: true});
          else
            if ((!self.current.parentID && (self.current.form == 'formTask' || self.current.form == 'formVoting')) || self.current.form == 'formProcess')
              $state.go('body.discus', { id: self.current.unid, type: self.current.type }, {reload: true});
        }

        self.current = {unid:GUID()};
      }, null, explicitEditing);
    };

    self.loadArchive = function(unid) {
      $log.log('discus.loadArchive');
      self.archived[unid].fetchingProgress = 1;
      var from = self.archived[unid].from;
      var to = self.archived[unid].to;
      var startTime = 0, finishTime = 0;
      
      for (var i in self.comments) {
        if (self.comments[i].threadNum == from) startTime = self.comments[i].created;
        else if (self.comments[i].threadNum == to+1) {
          finishTime = self.comments[i].created;
          break;
        }
      }
      
      $log.log('from '+startTime+' to '+finishTime);
      var offset = 0;
      var quantityLeft = 0;
      var fetchingQuantity = 50;

      var iterate = function(){
        $log.warn('iteration');
        
        $log.info('offset = '+offset);
        quantityLeft = quantityLeft ? quantityLeft - fetchingQuantity : self.archived[unid].count;
        $log.info('quantityLeft = '+quantityLeft);

        if (quantityLeft < fetchingQuantity) {fetchingQuantity = quantityLeft}
        if (quantityLeft <= 0) {
          delete self.archived[unid];
          return false;
        }

        $log.info('fetchingQuantity = '+fetchingQuantity);
        self.loadDocuments(null,
                           iterate,
                           fetchingQuantity,
                           offset,
                           startTime,
                           finishTime
                          );
        
        if (offset > 0) self.archived[unid].fetchingProgress = (self.archived[unid].count - quantityLeft)/(self.archived[unid].count / 100);
        $log.warn('fetchingProgress = '+self.archived[unid].fetchingProgress);
        
        offset = offset + fetchingQuantity;
        

      };

      iterate(fetchingQuantity, quantityLeft, offset);

//       self.expandedLimit = self.commentsCount;
    };

    self.loadDocuments = function(docid, handler, quantity, offset, from, to) {
      $log.log('discus.loadDocuments');
      if (!quantity) quantity = 50;
      if (!offset)   offset = 0;

      if(docid) { // READ
        self.clear();
        self.progress = 'requesting data';
        self.saved = false;
        BatchHttp({method: 'GET', url: 'api/history/get/'+docid })
            .then(function(response) {
              if (response.data.success)
                self.lastVisited = response.data.lastTime;
            }, httpErrorHandler);

        var req = {method: 'GET', url: 'api/discussion/get/chain/'+docid+'?quantity='+quantity+'&offset='+offset};
        if(typeof $state.params.locale != 'undefined'){
          req.url += '&locale='+$state.params.locale;
        }

        BatchHttp(req).then(function(response) {
          self.saved = true;
          self.progress = 'preparing documents';
          if(!response.data.success) {
            if(response.data.message=='permission denied'){
              self.progress = 0;
              self.private = true;
            }
            else {
              new Popup('Discus', response.data.message, 'error');
            }
          } else {
            self.private = false;
            self.unids = [];
            angular.extend(self, response.data.documents);

            self.commentsCount = parseInt(response.data.countMess);
            self.archived = response.data.archived;
            self.expandedLimit = 50;
            self.prepareForReadAfter(self.main_doc);
            if(self.main_doc.unid == docid) {
              self.main_doc._meta.selected = true;
              self.postSelected = true;
            }

            var sec = new Security(self.main_doc);

            self.isPublic = sec.hasPrivilege('read','all') ? true : false;

            var savedStates = JSON.parse(localStorage.getItem('expandedDocs'));
            if (!savedStates) savedStates = {};

            if (!savedStates[self.main_doc.unid]){
              self.expanded[self.main_doc.unid] = (self.main_doc.AttachedDoc && self.main_doc.AttachedDoc.length) ? 'attached' : 'shown';
            }
            else {
              self.expanded[self.main_doc.unid] = savedStates[self.main_doc.unid];
            }

            self.expandedLastFive = [];
            if (self.comments.length > 5) {
              for(var i = self.comments.length - 5; i < self.comments.length; i++){
                self.expandedLastFive.push(self.comments[i].unid);
              }
            }
            else {
              for(var comment in self.comments){
                self.expandedLastFive.push(comment.unid);
              }
            }

            self.comments && self.comments.forEach(function(e,i) {
              self.unids.push(e.unid);
              self.prepareForReadAfter(self.comments[i]);
              if(e._id == docid || e.unid == docid) {
                self.comments[i]._meta.selected = true;
                self.postSelected = true;
              }

              if(typeof e.mailHash != "undefined" && e.mailHash.length){
                self.expanded[e.unid] = 'shown';
              } else if((e.AttachedDoc && e.AttachedDoc.length) || $rootScope.$state.current.name==='body.notifications' || self.isNew(e)){
                self.expanded[e.unid] = 'attached';
              } else {
                if (self.expandedLastFive.indexOf(e.unid) >= 0) {
                  self.expanded[e.unid] = !savedStates[e.unid] || savedStates[e.unid] == 'shown'?'attached':savedStates[e.unid];
                } else {
                  self.expanded[e.unid] = savedStates[e.unid]?savedStates[e.unid]:'shown';
                }
              }
            });
            delete self.expandedLastFive;
            self.progress = 'finishing';
            handler && handler();
            self.progress = 0;
          }
        }, httpErrorHandler);
      }
      else {  //Archives
        if(self.fetchingInProgress == 1){
          return false;
        }

        docid = self.main_doc.unid;
        self.fetchingInProgress = 1;
        
        from = from || -1;
        to = to || -1;
        
        var req = {method: 'GET', url: 'api/discussion/get/chain/'+docid+'?quantity='+quantity+'&offset='+offset+'&from='+from+'&to='+to+'&archive=true' };

        BatchHttp(req).then(function(response) {
          if(!response.data.success) {
            if(response.data.message=='permission denied'){
              self.progress = 0;
              self.private = true;
            }
            else {
              new Popup('Discus', response.data.message, 'error');
            }
          } else {

            var newComments = response.data.documents.comments;
            var tmp = self.comments;

            newComments && newComments.forEach(function(e,i) {
              if ($.inArray(e.unid, self.unids) < 0) {
                self.unids.push(e.unid);
                self.prepareForReadAfter(newComments[i]);

                tmp.unshift(e);

                if((e.AttachedDoc && e.AttachedDoc.length) ||
                    $rootScope.$state.current.name==='body.notifications' ||
                    self.isNew(e)
                ){
                  self.expanded[e.unid] = 'attached';
                }
                else {
                  self.expanded[e.unid] = 'shown';
                }
              }
            });
            self.comments = tmp;
            self.fetchingInProgress = 0;
            handler && handler();
          }
        }, httpErrorHandler)
      }
    };
    
    self.loadPreviousComment = function(main, doc) {
      $log.log('discus.loadPreviousComment');
      $http({method: 'GET', url: 'api/discussion/get/'+main.unid+'/'+doc.created+'/true'})
          .then(function(response) {
            if (!response.data.success)
              new Popup('Уведомления', 'Ошибка при откладывании уведомления', 'Error');
            else {
              if (response.data.comments && response.data.comments[0])
              self.prepareForReadAfter(response.data.comments[0]);
              self.expanded[response.data.comments[0].unid] = 'shown';
              response.data.comments[0]._meta.loadedAsPreviousComment = true;
              self.comments.unshift(response.data.comments[0]);

              // fix for new notificator's .fast-reply block to stick to the bottom and not go down out of screen
              $timeout(function() { $(window).trigger('fixStickyControllsInNotificator'); }, 200);
            }
          }, httpErrorHandler);
    };

    self.checkScroll = function(loadStatus){
      if(self.lastId && loadStatus){
        var e = document.getElementById(self.lastId);
        if(e){
          e.scrollIntoView(false);
          window.scrollBy(0, 10)
        }
      }
    };

    self.scrollOnThemeOpen = function() {
      if( ~$state.current.name.indexOf('notifications') ) return false;
      var target = null,
          newPost = null;
      for (var i = 0; i < self.comments.length; i++) {
        var comment = self.comments[i];
        if ($state.params.id === comment.unid || $state.params.id === comment._id) {
          target = comment.unid;
          break;
        } else if ( !newPost && self.isNew(comment) ) {
          newPost = comment.unid;
        }
      }
      var realScroll = function() {
        ( Scroll.intoView('#'+target) || Scroll.intoView('#'+newPost) ) ||
        ( self.comments.length && Scroll.intoView( '#'+self.comments[self.comments.length - 1].unid ) );
      }
      var waitForRendering = function() {
        if ($rootScope.$$phase === '$apply' || $rootScope.$$phase === '$digest')
          $timeout(waitForRendering, 100);
        else $timeout(realScroll, 0);
      };
      waitForRendering();
    };

    self.scrollUnid = function(unid) {
      Scroll.intoView('#'+unid);
    };

    self.isNew = function (doc) {
      if (!self || !self.main_doc || !doc) {
        return false;
      }

      self.newPosts = self.newPosts || {count: 0};
      if (doc.unid in self.newPosts) return self.newPosts[doc.unid];

      if (doc.unid != self.main_doc.unid && (doc.authorLogin == $rootScope.user.username || doc.Author == $rootScope.user.portalData.FullNameRaw)) {
//         $log.log('IsNew: Author');
        return self.removeNewPostLabel(doc);
      }

      if ((typeof self.main_doc.readBy === 'undefined' || self.main_doc.readBy.length == 0) && !(doc.authorLogin == $rootScope.user.username || doc.Author == $rootScope.user.portalData.FullNameRaw)) {
        return self.addNewPostLabel(doc);
      }

      var readBy = self.main_doc.readBy;
      var lastVisited = readBy?readBy[$rootScope.user.username]:null;
      if (lastVisited) {
        lastVisited = new Date(lastVisited.substr(6,4), lastVisited.substr(3,2)*1-1, lastVisited.substr(0,2), lastVisited.substr(11,2), lastVisited.substr(14,2), lastVisited.substr(17,2));
        
        // console.log(lastVisited);
        if (lastVisited < $rootScope.convertStrISOToObj(doc.created)) {
          // $log.log('IsNew: true');
          return self.addNewPostLabel(doc);
        } else if (doc.taskHistories &&
                   doc.taskHistories.length &&
                   lastVisited < $rootScope.convertStrISOToObj(doc.taskHistories[doc.taskHistories.length - 1].created)
                  ) {
            // $log.log('IsNew: true');
            return self.addNewPostLabel(doc);
        }
      }

      if (!lastVisited && !(doc.unid == self.main_doc.unid && (doc.authorLogin == $rootScope.user.username || doc.Author == $rootScope.user.portalData.FullNameRaw))) {
//         $log.log('IsNew: Never visited');
        return self.addNewPostLabel(doc);
      }

      //$log.log('IsNew: false');
      return self.removeNewPostLabel(doc);
    }

    self.removeNewPostLabel = function(doc) {
      self.newPosts = self.newPosts || {count: 0};
      if ( self.newPosts[doc.unid] === true ) {
        self.newPosts.count--;
      }
      self.newPosts[doc.unid] = false;
      return false;
    };

    self.addNewPostLabel = function(doc) {
      self.newPosts = self.newPosts || {count: 0};
      if ( self.newPosts[doc.unid] !== true ) {
        self.newPosts.count++;
      }
      self.newPosts[doc.unid] = true;
      return true;
    };

    self.getPostsInViewport = function(viewport, strict) {
      strict = strict === true;
      viewport = viewport || self.getDiscusViewport();
      var upper = null,
          lower = null;
      for (var i = 0; i < self.comments.length; i++) {
        if (Viewport.isInViewport('#'+self.comments[i].unid, viewport, strict)) {
          upper = upper === null || upper > i ? i : upper;
          lower = lower === null || lower < i ? i : lower;
        }
      }
      return {
        upper: upper,
        lower: lower
      };
    };

    self.hasNewPostsUpViewport = function() {
      var result = false;
      var inViewport = self.getPostsInViewport();
      for (var i = inViewport.upper - 1; i >= 0; i--) {
        if (self.isNew(self.comments[i])) {
          result = true;
          break;
        }
      }
      //console.log('up', i, result);
      return result;
    };

    self.hasNewPostsDownViewport = function() {
      var result = false;
      var inViewport = self.getPostsInViewport();
      for (var i = inViewport.lower + 1; i < self.comments.length; i++) {
        if (self.isNew(self.comments[i])) {
          result = true;
          break;
        }
      }
      //console.log('down', i, result);
      return result;
    };

    self.scrollToNewPost = function(up) {
      //if (!self.newPosts.length) return;
      up = up === true;
      var viewport = self.getDiscusViewport();
      var inViewport = self.getPostsInViewport(viewport);
      var target = up ? inViewport.upper : inViewport.lower;
      while (target >= 0 && target < self.comments.length && !self.isNew(self.comments[target]))
        target += up ? -1 : 1;
      target = Math.min(Math.max(0, target), self.comments.length - 1);
      Scroll.intoView('#'+self.comments[target].unid);
    };

    self.getDiscusViewport = function() {
      var viewport = Viewport.get();
      if (!self.main_doc || !self.main_doc.unid) return viewport;
      var topMenu = $('#'+self.main_doc.unid).parent().find('.theme-top-menu');
      var fastReply = $('#fast-reply');
      if (topMenu.length) viewport.top += topMenu.offset().top - viewport.top + topMenu.outerHeight();
      if (fastReply.length) viewport.bottom -= fastReply.outerHeight();
      return viewport;
    };

    self.readDocs = function(onlyOnscreenDocs) {
      if (self.newPosts.count === 0) return false;
      onlyOnscreenDocs = onlyOnscreenDocs === true;
      var read = false;
      if (self.isNew(self.main_doc) && (!onlyOnscreenDocs || Viewport.isInViewport('#'+self.main_doc.unid))) {
          read = true;
          self.removeNewPostLabel(self.main_doc);
      }
      var startAtComment = 0;
      var stopAtComment = self.comments.length - 1;
      if (onlyOnscreenDocs) {
        var inViewport = self.getPostsInViewport();
        startAtComment = inViewport.upper;
        stopAtComment = inViewport.lower;
      }
      for (var i = startAtComment; i <= stopAtComment; i++) {
        if ( self.isNew(self.comments[i]) ) {
          read = true;
          self.removeNewPostLabel(self.comments[i]);
        }
      }
      if (read) {
        Notificator.markAsRead([{unid: self.main_doc.unid, parentUnid: self.main_doc.unid}]);
        return true;
      }
      return false;
    };

    self.readOnscreenDocs = function() {
      return self.readDocs(true);
    };

    self.readAllTheDocs = function() {
      return self.readDocs(false);
    };

    self.countLikes = function(doc) {
      doc._meta = doc._meta ? doc._meta : [];
      doc._meta.confirmLike = false;
      doc._meta.qLikes = 0;
      doc._meta.qDislikes = 0;
      for(var i in doc.likes) {
        if (doc.likes[i].isLike == 0) doc._meta.qDislikes++;
        else if(doc.likes[i].isLike == 1)doc._meta.qLikes++;
      }
    }

    self.iLikeThis = function (doc, iDo){
      if ($rootScope.user.username != doc.authorLogin) {
        var isLike = iDo;
        if (!doc.likes) doc.likes = [];
        if (doc.likes[$rootScope.user.username]) {
          if (doc.likes[$rootScope.user.username].isLike == iDo) isLike = -1;
        }
        BatchHttp({method: 'POST', url: 'api/discussion/like/'+doc.unid, data: {'isLike': isLike, 'login': $rootScope.user.username}})
        .then(function(response) {
          if(!response.data.success) {
            new Popup('Discus', response.data.message, 'error');
          } else {
            doc.likes = {};
            angular.merge(doc, response.data.document);
            self.countLikes(doc);
          }
        }, httpErrorHandler);
      }
    }

    self.requestLikeConfirmation = function (doc, isLike) {
      doc._meta.confirmLike = true;
      doc._meta.confirmIsLike = isLike;
    }

    self.getDocFromServerByUnid = function(unid, callback, commentsSince){
      BatchHttp({method: 'GET', url: (commentsSince ? 'api/discussion/get/'+unid+'/'+commentsSince : 'api/discussion/get/'+unid)}).then(function(response) {
        self.saved = true;
        self.newPosts = {count: 0};
        if(!response.data.success) {
          new Popup('Discus', response.data.message, 'error');
        } else {
          if (commentsSince)
            callback(response.data['document'], response.data['comments']);
          else
            callback(response.data['document']);
        }
      }, httpErrorHandler);
    }

    self.getWithUnreadedComments = function(unid, docs, callback){
      BatchHttp({method: 'POST', url: 'api/discussion/getWithUnreaded/'+unid, data: {'docs': docs}}).then(function(response) {
        self.saved = true;
        if(!response.data.success) {
          new Popup('Discus', response.data.message, 'error');
        } else {
          if (response.data['comments'])
            callback(response.data['document'], response.data['comments']);
          else
            callback(response.data['document']);
        }
      }, httpErrorHandler);
    }

    self.getDocumentFilename = function (fileDoc, unid, q) {
      var fileName = fileDoc.originalFilename;
      if(typeof fileDoc.references != "undefined"){
        for(var key in fileDoc.references){
          if(fileDoc.references[key].unid == unid){
            fileName = fileDoc.references[key].originalFilename;
            break;
          }
        }
      }

      return fileName;
    };

    self.prepareForWrite = function(type, docid, taskid, typedoc) {
//       $log.log('discus.prepareForWrite');
      if(!self.current) self.current = {};
      if(docid) { // EDIT
        self.saved = false;
        self.getDocFromServerByUnid(docid, function(respDoc) {
          self.current = respDoc;
          var security = new Security(self.current);
          self.current.isPublic = security.hasPrivilege('read','all') ? true : false;
          self.prepareForWriteAfter(false);
        })
      } else { // CREATE
        self.current._id = 0;
        if(!self.current.unid){
          self.current.unid = GUID();
        }

        self.current.form = type;
        if(type != 'formAdapt' && (self.isContact || self.getMain() && self.getMain().form == 'Contact')) {
          self.current.ParentDbName = 'Contacts';
        } else {
          self.isContact = false;
        }
        self.current.status = 'open';
        self.current.body = '';
        self.current.subject = '';
        self.current._meta = null;
        self.current.Author = $rootScope.user.username;
        self.current.AuthorLogin = $rootScope.user.username;
        self.current.AuthorRus = $rootScope.user.portalData.LastName + " " + $rootScope.user.portalData.name;

        var security = new Security(self.current);
        self.current.isPublic = true;

        if(self.isBlog) {
          self.current.type = 'Blog';
        }
        if(self.main_doc) {
          if (self.main_doc.form === "formTask" && self.main_doc.parentID) {
            self.current.parentID = self.main_doc.parentID;
            self.current.subjectID = self.main_doc.subjectID;
          } else {
            self.current.parentID = self.main_doc.unid;
            self.current.subjectID = self.main_doc.unid;
          }
          if(taskid) {
            self.current.taskID = taskid;
            if(typedoc == 'REJECT') {
              typedoc = 'task';
              self.current.action = 'reject';
            }
            if(typedoc == 'CHECK') {
              typedoc = 'task';
              self.current.action = 'check';
            }
            self.current.typeDoc = typedoc;
            self.current.viewBody = '';
          }
        }
        if(self.current.form != 'formAdapt') {
          security.addPrivilege('read','role','all');
          if(self.current.form == 'formVoting') {
            security.addPrivilege('vote','role','all');
            //security.addPrivilege('vote','username',$rootScope.user.username);
          }
        } else {
          if ($rootScope.dictionaryCache['AutoTaskPersons']) { // Won't load the names otherwise
            $rootScope.dictionaryCache['AutoTaskPersons'] = undefined;
          }

          personDict = new Dictionary('AutoTaskPersons', true);

          personDict.whenReady = function() {
            self.current.Recruter = $rootScope.user.username;
            if (self.current.Recruter != undefined) self.current.TempRecruter = $rootScope.users[self.current.Recruter].name;
            self.addParticipant(self.current.Recruter, 'username', self.current, true);
            self.current.HeadIT = personDict.getRecordValue('Начальник ИТ');
            if (self.current.HeadIT != undefined) self.current.TempHeadIT = $rootScope.users[self.current.HeadIT].name;
            self.addParticipant(self.current.HeadIT, 'username', self.current, true);
            self.current.ManagerHR = personDict.getRecordValue('Специалист HR');
            if (self.current.ManagerHR != undefined) self.current.TempManagerHR = $rootScope.users[self.current.ManagerHR].name;
            self.addParticipant(self.current.ManagerHR, 'username', self.current, true);
            self.current.HeadFin = personDict.getRecordValue('Главный бухгалтер');
            if (self.current.HeadFin != undefined) self.current.TempHeadFin = $rootScope.users[self.current.HeadFin].name;
            self.addParticipant(self.current.HeadFin, 'username', self.current, true);
            self.current.HeadHR = personDict.getRecordValue('Директор HR');
            if (self.current.HeadHR != undefined) self.current.TempHeadHR = $rootScope.users[self.current.HeadHR].name;
            self.addParticipant(self.current.HeadHR, 'username', self.current, true);
          }
        }
        if(self.current.form !== 'formVoting' && self.tempParticipants.length == 0)
          self.tempParticipants.push($rootScope.user.username);


        security.addPrivilege('write','username',$rootScope.user.username);

        self.prepareForWriteAfter(true);
      }
    };

    self.prepareForReadAfter = function(docInst) {
      docInst._meta = docInst._meta || {};
      delete docInst._meta.write;
      switch(docInst.form) {
        case 'formVoting':
          docInst._meta.voting = new Voting(docInst);
          if(!docInst.answers) { docInst.answers = {}; }
          break;
        case 'formTask':
          docInst._meta.tasking = new Tasking(docInst, self.main_doc);
          break;
        case 'messagebb':
          var task = self.getByUnid(docInst.taskID || docInst.parentID);
          if(task && task._meta && task._meta.tasking) {
            switch(docInst.typeDoc) {
              case 'changes':
              case 'task':
              case 'result':
                if (!docInst.action) {
                  task._meta.tasking[docInst.typeDoc].push(docInst);
                }
                task._meta.tasking.allLine.push(docInst);
                break;
              case 'question':
                task._meta.tasking.allLine.push(docInst);
                break;
            }
          }
          break;
        case 'formAdapt':
          docInst._meta.adaptation = new Adaptation(docInst);
          break;
        case 'formProcess':
          if(docInst.SelectRegion && docInst.SelectRegion.length) {
            self.vacancyRegionDict = self.vacancyRegionDict || new Dictionary('VacancyRegion',true);
          }
          break;
      }
      self.countLikes(docInst);
    }

    self.prepareForWriteAfter = function(isCreation) {
//       $log.log('discus.prepareForWriteAfter');
      if(! self.current._meta) { self.current._meta = {}; }
      self.current._meta.write = isCreation ? 'creation' : 'editing';
      if (!isCreation) { //load participants for editing
        var readPrivs = self.current.security?self.current.security.privileges.read:[];
        for(var i in readPrivs) {
          if (readPrivs[i].username) self.tempParticipants.push(readPrivs[i].username);
        }
        if(self.current.shareSecurity){
          self.tempShareParticipants = self.getShareParticipants(self.current.shareSecurity);
        }
      }

      switch(self.current.form) {
        case 'formProcess':
          if(!self.isBlog) {
            self.sectionDict = self.sectionDict || new Dictionary('DiscusSection', true,false,true);
            self.vacancyRegionDict = self.vacancyRegionDict || new Dictionary('VacancyRegion',true);
          }
          if(isCreation) {
            self.current.C1 = 'Общекорпоративные';
          }
          break;
        case 'formVoting':
          self.sectionDict = self.sectionDict || new Dictionary('DiscusSection', true,false,true);
          if(isCreation) {
            self.current.AnswersData = ['', ''];
            self.current.AnswersPictures = [];
            self.current.AnswersPicturesNames = [];
            self.current.answers = [];
            self.current.AnswersLim = 1;
            self.current.subject = '';
          }
          self.current._meta.voting = new Voting(self.current);
          break;
        case 'formTask':
          if(isCreation) {
            // var taskTime = TretoDateTime.iso8601.fromDate();
            self.current.C1 = 'Общекорпоративные';
            self.current.status = 'open';
            // self.current.taskDateStart = taskTime;
            // self.current.taskDateEnd = taskTime;
            self.current.DocSubType = '';
            self.current.DocType = null;
            self.current.Priority = 0;
            self.current.taskPerformerLat = '';
            self.current.Difficulty = '';
            self.current.Author = $rootScope.user.username;
            self.current.TaskStateCurrent = 0;
            self.current.TaskStatePrevious = 0;
          }
          self.current._meta.tasking = new Tasking(self.current, self.main_doc);
          break;
        case 'formAdapt':
          self.current._meta.adaptation = new Adaptation(self.current);
          break;
        case 'message':
          if(isCreation) {
            if(self.quote) {
              self.current.body = self.quote;
            }
            if(self.subject) {
              self.current.subject = subject;
            }else{
              self.current.subject = '';
            }
          }
          break;
      }
    }

    self.clear = function() {
      self.main_doc = null;
      self.current = null;
      self.comments = [];
      self.newPosts = {count: 0};
      self.saved = true;
      self.isBlog = false;
      self.profile = null;
      self.displayParticipants = false;
      self.participantsModal = {};
      self.tempParticipants = [];
      self.tempShareParticipants = [];
      self.sharePushArray = {};
      self.expanded = { all: true };
      self.commentsCount = 0;
      self.progress = 0;
    };

    self.initDictionaries = function(whichDoc) {
      whichDoc = whichDoc || self.main_doc;
      if(!self.profile) { self.profile = new Profile(); }
      var sourceNames = [];
      new Security(whichDoc);
    }

    self.changeSharePushArray = function(action, domain, data, recursive){
      if(typeof self.sharePushArray[domain] == 'undefined'){
        self.sharePushArray[domain] = [];
      }

      switch(action){
        case 'empl':
          var indexOfUsername = self.sharePushArray[domain].indexOf(data.username);

          if(data.checked && indexOfUsername == -1){
            self.sharePushArray[domain].push(data.username);
          }
          else if(!data.checked && indexOfUsername != -1) {
            self.sharePushArray[domain].splice(indexOfUsername, 1);
          }

          break;
        case 'section':
          for(var emplKey in data.data){
            data.data[emplKey].checked = data.checked;
            self.changeSharePushArray('empl', domain, data.data[emplKey], true);
          }
          break;
        case 'portal':
          for(var sectionKey in data.data){
            data.data[sectionKey].checked = data.checked;
            self.changeSharePushArray('section', domain, data.data[sectionKey], true);
          }
          break;
      }

      if(typeof recursive == 'undefined' || !recursive){
        self.buildShareTree();
      }
    };

    // выбрать сотрудников всех команд (окно добавить сотрудников)
    self.changeAllSharePushArray = function(object, check) {
      for (var i in object) {
        object[i].checked = check;
        self.changeSharePushArray('portal', i, object[i], true);
      }
    };

    self.getShareEnvironment = function(domain){
      var result = false;
      if(domain){

        for(var shareDomain in $rootScope.shareUsers){
          if(domain == shareDomain){
            result = $rootScope.shareUsers[shareDomain].environment;
            break;
          }
        }
      }

      return result;
    };

    self.findShareDataByLogins = function(dataType, domain, login){
      var result = '';

      if(domain && login){
        for(var portalDomain in $rootScope.shareUsers){
          if(portalDomain == domain){
            for(var sectionName in $rootScope.shareUsers[portalDomain].data){
              var section = $rootScope.shareUsers[portalDomain].data[sectionName].data;
              for(var empl in section){
                switch(dataType){
                  case 'fullName':
                    if(section[empl].username == login){
                      return section[empl].LastName+ ' '+section[empl].name;
                    }
                    break;
                  case 'WorkGroup':
                    if(section[empl].username == login){
                      return section[empl].WorkGroup?section[empl].WorkGroup.join(','):'';
                    }
                    break;
                }
              }
            }
          }
        }

        if(dataType == 'fullName' && !result){
          result = login;
        }
      }

      return result;
    };

    self.buildShareTree = function () {
      if(!self.sharePushArray){
        self.sharePushArray = {};
      }
      var reset = true;
      for(var i in self.sharePushArray){ reset = false; break; }

      self.shareTree = $rootScope.shareUsers;

      for(var portalDomain in $rootScope.shareUsers){
        if(typeof self.shareTree[portalDomain].checked == 'undefined'){
          self.shareTree[portalDomain].checked = false;
          self.shareTree[portalDomain].open = false;
        }
        for(var sectionName in $rootScope.shareUsers[portalDomain].data){
          if(typeof self.shareTree[portalDomain].data[sectionName].checked == 'undefined'){
            self.shareTree[portalDomain].data[sectionName].checked = false;
            self.shareTree[portalDomain].data[sectionName].open = false;
          }
          for(var userKey in $rootScope.shareUsers[portalDomain].data[sectionName].data){
            var checked = false;

            if(typeof self.sharePushArray[portalDomain] != 'undefined'){
              var username = $rootScope.shareUsers[portalDomain].data[sectionName].data[userKey].username;
              checked = self.sharePushArray[portalDomain].indexOf(username) != -1;
              self.shareTree[portalDomain].data[sectionName].data[userKey].checked = checked;

              if(checked){
                self.shareTree[portalDomain].checked = true;
                self.shareTree[portalDomain].open = true;
                self.shareTree[portalDomain].data[sectionName].checked = true;
                self.shareTree[portalDomain].data[sectionName].open = true;
              }
            } else if(reset){
              self.shareTree[portalDomain].checked = false;
              self.shareTree[portalDomain].open = false;
              self.shareTree[portalDomain].data[sectionName].checked = false;
              self.shareTree[portalDomain].data[sectionName].open = false;
            }
          }
        }
      }
    };

    self.selectParticipants = function(doc, attrs) {
      self.attrs = attrs;
      self.displayParticipants = true;
      self.displayParticipantsDoc = doc;
      var timerId;

      if(self.shareEnable){
        self.buildShareTree();
      }

      //wait for chat loaded
      function checkUsers() {
        for(var user in $rootScope.users) {
          if ($rootScope.users.hasOwnProperty(user)) {
            clearTimeout(timerId);
            getUsers();
            $rootScope.$apply();
            break;
          }
        }
      };

      timerId = setInterval(checkUsers, 200);

      function getUsers() {
        var users = $rootScope.users;

        //get users from chat and sort by sections
        for(var user in users) {
          if(!users[user].section || users[user].section.length == 0){
            users[user].section = ['Без отдела'];
          }
          else {
            for(var inc = 0; inc < users[user].section.length; inc++){
              if(!users[user].section[inc] && users[user].section[inc] == ""){
                users[user].section[inc] = 'Без отдела';
              }
            }
          }

          var sections = users[user].section;
          for(var inc = 0; inc < sections.length; inc++){
            var section = sections[inc];
            if(!self.participantsList.sections[section]){
              self.participantsList.sections[section] = {
                name: section,
                expanded: false,
                checked: false,
                participants: {}
              };
              self.participantsList.sectionsLength++;
            }
          }
        }

        for(var user in users) {
          for(var section in self.participantsList.sections){
            var u = users[user],
                s = self.participantsList.sections[section];

            if(u.section.indexOf(s.name) !== -1){
              s.participants[u.id] = {
                name: u.name,
                WorkGroup: u.WorkGroup,
                checked: false
              };
            }
          }
        }

        self.participantsList.saved = true;
      };
    };

    self.allSectionCheckToggle = function () {
      self.participantsList.checked = !self.participantsList.checked;
      for(var section in self.participantsList.sections){
        section = self.participantsList.sections[section];
        section.checked = self.participantsList.checked;
        for(var participant in section.participants){
          if(self.participantsList.checked){
            self.selectedParticipants.push(participant);
          }else{
            self.selectedParticipants = [];
          }
          participant = section.participants[participant];
          participant.checked = section.checked;
        };
      };
    };

    self.sectionCheckToggle = function (section) {
      if (self.attrs.section == "true" && self.attrs.multiple == "false") {
        return false;
      }
      section.checked = !section.checked;
      for(var participant in section.participants){
        section.participants[participant].checked = section.checked;
        var a = self.selectedParticipants.indexOf(participant);

        if(a == -1 && section.checked){
          self.selectedParticipants.push(participant);
        }
        else if(a != -1 && !section.checked){
          self.selectedParticipants.remove(a);
        }
        //self.setByName(section.participants[participant].name, section.checked, section.name);
      };
    };

    self.setByName = function (name, checked, sectionName) {
      for(var keySection in self.participantsList.sections) {
        var otherSelected = false;
        for(var partic in self.participantsList.sections[keySection].participants){
          if(self.participantsList.sections[keySection].participants[partic].name == name){
            self.participantsList.sections[keySection].participants[partic].checked = checked;
            self.participantsList.sections[keySection].checked = checked;
          }
          else if(self.participantsList.sections[keySection].participants[partic].checked){
            otherSelected = true;
          }
        }
        if(!checked && otherSelected){
          if(typeof sectionName != 'undefined' && sectionName == self.participantsList.sections[keySection].name){
            continue;
          }
          self.participantsList.sections[keySection].checked = true;
        }
      }
    };

    self.participantCheckToggle = function (section, participant) {
      if (self.attrs.section == "true" && self.attrs.multiple == "false") {
        for (var i in self.participantsList.sections) {
          self.participantsList.sections[i].checked = false;
          for (var j in self.participantsList.sections[i].participants)
            self.participantsList.sections[i].participants[j].checked = false;
        }
        self.selectedParticipants = [];
      }
      participant.checked = !participant.checked;
      var checkedCount = 0;
      for(var p in section.participants){
        if (participant.name == section.participants[p].name){
          var a = self.selectedParticipants.indexOf(p);
          if(a != -1 && !participant.checked){
            self.selectedParticipants.remove(a);
            //self.setByName(participant.name, false);
          }
          else if(a == -1 && participant.checked) {
            self.selectedParticipants.push(p);
            //self.setByName(participant.name, true);
          }
          self.participantsList.checked = (self.selectedParticipants.length) ? true : false;
        }
        (section.participants[p].checked) ? checkedCount++ : false;
        section.checked = (checkedCount) ? true : false;
      };
    };

    self.privateWarning = function () {
      function clearPopup() {
        $rootScope.popup = null;
      }

      var alertMsg = 'Внимание! Вы включили настройку "только для участников". Это сделает тему недоступной всем остальным.';
      alertMsg += '\n';
      alertMsg += 'Мы открытая компания, и если ничего секретного в теме нет, то лучше если она будет доступной для всех.'

      new Popup('Discus', alertMsg, 'warning', false, clearPopup, clearPopup);
    }

    self.saveSelectedParticipants = function (whichDoc, pushArray, recursive) {
        var pushArray = pushArray || self.tempParticipants;
        var deputyLogins = [];
        for (var k in self.selectedParticipants) {
          var username = self.selectedParticipants[k];
          for (var sections in self.participantsList.sections) {
            var section = self.participantsList.sections[sections];
            if (typeof section.participants[username] != 'undefined') {
              var name = section.participants[username].name;
            }
          };
          var inArr = false;
          for (var i = 0; i < pushArray.length; i++) {
            if (pushArray[i] == username) {
              inArr = true;
              break;
            }
          }

          if (!inArr){
            if(typeof $rootScope.users[username].status.deputyLogin != 'undefined' && (typeof recursive == 'undefined' || !recursive)){
              deputyLogins.push({
                login: username,
                deputyLogin: $rootScope.users[username].status.deputyLogin,
                isParticipants: self.attrs && self.attrs.isParticipants,
                terms: $rootScope.users[username].status.terms
              });
            }
            else {
              pushArray.push(username);
            }
          }
        };

        self.selectDeputy(deputyLogins, function (login) {
          for(var v in login){
            if(pushArray.indexOf(login[v]) == -1){
              pushArray.push(login[v]);
            }
            self.selectedParticipants.splice(self.selectedParticipants.indexOf(username), 1);
            self.saveSelectedParticipants(whichDoc, pushArray);
          }
        });

        self.saveParticipants(whichDoc);
        for (section in self.participantsList.sections){
          self.participantsList.sections[section].checked = false;
        }
        self.selectedParticipants = [];
        self.participantsList.checked = false;
    };

    self.selectDeputy = function(deputyLogins, callback, rec, result){
      if(deputyLogins.length){
        var deputy = [];
        if(typeof rec == 'undefined' || !rec){
          rec = 0;
          result = [];
        }

        deputy = deputyLogins[rec];

        var checkEnd = function(){
          if(deputyLogins.length-1 > rec){
            self.selectDeputy(deputyLogins, callback, ++rec, result);
          }
          else {
            callback(result);
          }
        };

        new DeputyPopup(
            deputy.login,
            deputy.deputyLogin,
            deputy.terms,
            deputy.isParticipants,
            function(login, recurs){
              if(login){
                result.push(login);
              }
              checkEnd();
            });
      }
    };

    self.addParticipantsFromSections = function(sectionValues, whichDoc) {
      if(!self.saved) { return; }
      self.saved = false;
      BatchHttp({method: 'POST', url: 'api/get-usernames-for-section', data: { section: sectionValues.join(',') }})
        .then(function(response) {
          self.saved = true;
          if(!response.data.success) {
            new Popup('Discus', response.data.message, 'error');
          }
          if(response.data.usernames.isEmpty()) {
            new Popup('Discus', 'discus.emptySection', 'warning');
          } else {
            for(var k in response.data.usernames) {
              self.addParticipant(k,'username', whichDoc);
            }
            self.saveParticipants(whichDoc);
          }
        }, httpErrorHandler);
    }

    self.addVacManager = function(name, whichDoc) {
      if(!self.saved) return;
      if(!whichDoc) whichDoc = self.main_doc;
      if(!whichDoc.VacManager) whichDoc.VacManager = [];
      if(whichDoc.VacManager.indexOf(name) == -1) whichDoc.VacManager.push(name);
    }

    self.delVacManager = function(name, whichDoc) {
      if(!self.saved) return;
      if(!whichDoc) whichDoc = self.main_doc;
      whichDoc.VacManager.splice(whichDoc.VacManager.indexOf(name), 1);
    }

    self.showHelper = function(){
      console.log(self.emplSectionDict);
    }

    self.addParticipant = function(subject, type, whichDoc, silent, save, suppressImpliedPerms) {
      if(!self.saved) { return; }
      $log.log('discus.addParticipant: subject='+subject+', type='+type+', save='+save);
      if(!whichDoc) { whichDoc = self.main_doc; }
      var security = new Security(whichDoc);
      security.addPrivilege('read',type,subject);
      if(subject == 'all' && type == 'role'){
        for(var domain in whichDoc.shareSecurity){
          security.addSharePrivilege(domain, 'read', type, subject);
        }
      }
      if (type != 'role' && !suppressImpliedPerms){
        security.addPrivilege('subscribed',type,subject);
      }
      if(whichDoc.form == 'formVoting' && !suppressImpliedPerms){
        security.addPrivilege('vote',type,subject);
      }

      if(!silent) {
        if(!$rootScope.popup){
          function clearPopup(){
            $rootScope.popup = null;
          }
        }
      }

      if(subject == 'all' && type == 'role') { self.current.isPublic = true; }
      if (save) {
        self.saveParticipants(whichDoc, function() {
          self.include.shownThreadParticipants.refresh();
        });
      }
    };

    self.removeParticipant = function(subject, type, whichDoc, removeAccess, save) {
      $log.log('discus.removeParticipant: subject='+subject+', type='+type+', save='+save);
      type=type?type:'read';
      if(!self.saved) { return; }
      if(!whichDoc) { whichDoc = self.main_doc; }
      var security = new Security(whichDoc);
      if(subject == 'all' && type == 'role' && removeAccess){
        for(var domain in whichDoc.shareSecurity){
          security.removeSharePrivilege(domain, 'read', type, subject);
        }
      }
      security.removePrivilege('subscribed', type, subject);
      if (removeAccess) {
        security.removePrivilege('read', type, subject);
        if(whichDoc.form == 'formVoting') { //???
          security.removePrivilege('vote', type, subject);
        }
      }
      if(subject == 'all') { self.current.isPublic = false; }
      // self.selectParticipants();
//       console.dir(security.doc.security);
      if (save) {
        self.saveParticipants(whichDoc, function() {
          self.include.shownThreadParticipants.refresh();
        });
      }
    };

    self.isParticipant = function() {
      var security = new Security(self.main_doc);
      return security.hasPrivilege('subscribed', $rootScope.user.username, true);
    };

    self.saveParticipants = function(whichDoc, callback, fromSubjMenu) {
      $log.log('discus.saveParticipants');
      if(!self.saved) { return; }
      whichDoc = whichDoc || self.main_doc || {}; //fix for user @saveParticipants() outside discussions
      if(whichDoc._id){
        self.saved = false;
        // console.log(whichDoc.form, whichDoc._id, whichDoc.security);
        var form = whichDoc.form == 'Contact' ? 'Contacts' : 'Portal';
        var data = {
          'security': whichDoc.security,
          'repository': form,
          'fromSubjMenu':self.participantsModal.showDsk || (typeof fromSubjMenu != 'undefined' && fromSubjMenu)
        };

        if(whichDoc.shareSecurity){
          data['shareSecurity'] = whichDoc.shareSecurity;
        }

        BatchHttp({method: 'POST', url: 'api/security/set/'+whichDoc._id, data: data})
            .then(function(response) {
              self.saved = true;
              if(!response.data.success) {
                new Popup('Discus', response.data.message, 'error');
              }else{
                whichDoc.security = response.data.security;

                if(response.data.shareSecurity){
                  whichDoc.shareSecurity = response.data.shareSecurity;
                }
                if (callback) callback();
              }
            }, httpErrorHandler);
      }
    };

    self.countShareArr = function(arr){
      var result = 0;
      for(var domain in arr){
        for(var key in arr[domain]){
          result++;
        }
      }

      return result;
    };

    self.setNewSharePrivileges = function () {
      if(typeof self.sharePushArray != 'undefined'){
        var security = new Security(self.current);

        if(security){
         // var doc = self.main_doc && self.main_doc.shareSecurity?self.main_doc:self.current;
          var doc = self.main_doc && self.main_doc.shareSecurity?self.main_doc:self.current;
          if(self.main_doc && !doc.shareSecurity){
            doc = self.main_doc;
            doc.shareSecurity = {};
          }
          var docSec = new Security(doc);
          var difference = self.getDifferenceSharePrivileges(self.sharePushArray, self.getShareParticipants(doc.shareSecurity));

          for(var domain in difference){
            for(var inc in difference[domain]){
              security.removeSharePrivilege(domain, 'read', 'username', difference[domain][inc]);
              security.removeSharePrivilege(domain, 'subscribed', 'username', difference[domain][inc]);
            }
          }
          for(var domain in self.sharePushArray){
            for(var inc in self.sharePushArray[domain]){
              security.addSharePrivilege(domain, 'read', 'username', self.sharePushArray[domain][inc]);
              security.addSharePrivilege(domain, 'subscribed', 'username', self.sharePushArray[domain][inc]);
            }
          }

          if(docSec.hasPrivilege('read','all')){
            security.addToAllSharePrivilege('read', 'role', 'all');
          }
          else {
            security.removeToAllSharePrivilege('read', 'role', 'all');
          }

          doc.shareSecurity = security.getShareSecurity();

          if(self.show && self.show.Parts){
            self.show.Parts = false;
            $timeout(function(){
              self.show.Parts = true;
            }, 500);
          }
        }
      }
      else {
        console.log('share undefined');
      }
    };

    self.getDifferenceSharePrivileges = function (newPrivileges, oldPrivileges) {
      var result = [];
      for(var oldDomain in oldPrivileges){
        for(var oldInc in oldPrivileges[oldDomain]){
          if(!newPrivileges[oldDomain]){
            newPrivileges[oldDomain] = [];
          }

          if(newPrivileges[oldDomain].indexOf(oldPrivileges[oldDomain][oldInc]) == -1){
            if(!result[oldDomain]){
              result[oldDomain] = [];
            }

            result[oldDomain].push(oldPrivileges[oldDomain][oldInc]);
          }
        }
      }

      return result;
    };

    self.applyAdding = function(users) { //for the directive
      $log.log('discus.applyAdding');
      var userList = [];

      if (users) {
        for (var key in users) {
          self.addParticipant(users[key], 'username', self.main_doc);
          userList.push(users[key]);
        }

        self.saveParticipants(discus.main_doc, function() {
          self.addNotifToUsers(self.main_doc, userList, 0);
          self.include.shownThreadParticipants.refresh();
        });
      }
    };

    self.updateSecurity = self.saveParticipants;

    self.addNotifToUsers = function(doc, userList, urgency, callback) {
      if (!self || !self.main_doc) return false;
        BatchHttp({method: 'POST', url: 'api/notif/addNotifToUsers', data: {'parent': self.main_doc.unid, 'doc': doc.unid, 'userList': userList, 'urgency': urgency}})
            .then(function(response) {
              if(!response.data.success) {
                console.log(response.data.message);
                new Popup('Discus', response.data.message, 'error');
              }else if (callback){
                callback();
              }
            }, httpErrorHandler);
    };

    self.deleteUndeleteDocument = function(whichDoc) {
      if(!self.saved) { return; }
      self.saved = false;
      whichDoc.status = (whichDoc.status && whichDoc.status == 'deleted') ? 'open' : 'deleted';
      var data = {document:{status: whichDoc.status, form: whichDoc.form }};
      if(whichDoc.parentID || whichDoc.subjectID){
        data.document['parentID'] = whichDoc.parentID?whichDoc.parentID:whichDoc.subjectID;
        data.document['subjectID'] = whichDoc.subjectID?whichDoc.subjectID:whichDoc.parentID;
      }

      var url = whichDoc.form == 'Contact'?'api/v1/contact/remove/'+whichDoc.unid:'api/discussion/set/'+whichDoc._id;

      BatchHttp({method: 'POST', url: url, data: data})
          .then(function(response) {
            self.saved = true;
            if(!response.data.success) {
              new Popup('Discus', response.data.message, 'error');
            }
            else {
              $state.go($state.current, {}, {reload: true});
            }
          }, httpErrorHandler);
    };

    self.toggleParent = function(whichDoc) {
      if(!self.saved) { return; }
      if(whichDoc.parentID && whichDoc.parentID != whichDoc.unid) {
        whichDoc.parentID = '';
        whichDoc.subjectID = '';
        var security = new Security(whichDoc);
        security.addPrivilege('read','role','all');
        security.addPrivilege('read','username',$rootScope.user.username);
        security.addPrivilege('subscribed','username',$rootScope.user.username);
      } else {
        whichDoc.parentID = self.main_doc.unid;
        whichDoc.subjectID = self.main_doc.unid;
        whichDoc.security = {};
      }
    };

    self.update = function(whichDoc) {
      if(!self.saved) { return; }
      self.saved = false;
      var meta = whichDoc._meta;
      whichDoc._meta = null;

      BatchHttp({method: 'POST', url: 'api/discussion/set/'+whichDoc._id, data: { 'document': whichDoc } })
          .then(function(response) {
            self.saved = true;
            if(!response.data.success) {
              new Popup('Discus', response.data.message, 'error');
            } else {
              angular.copy(response.data.document, whichDoc);
              if (whichDoc.isLinked == 1 || (whichDoc.linkedUNID && whichDoc.linkedUNID.length > 0))
                self.loadLinks(whichDoc);
              whichDoc._meta = meta;
              whichDoc._meta && whichDoc._meta.tasking ? whichDoc._meta.tasking.parseHistory() : null;
              meta = null;
            }
          }, httpErrorHandler);
    };

    self.shareMail = function(unid, status){
      var action = status == 'close'?'open':'close';
      BatchHttp({method: 'POST', url: 'api/discussion/shareMail/'+unid+'/'+action})
          .then(function(response) {
            if(response.data && response.data.success){
              self.mailStatus = action;
            }
          }, httpErrorHandler);
    };

    self.switchExpanded = function(doc) {
      if(doc) { // specified doc
        if(self.expanded[doc.unid] != 'collapsed') {
          self.expanded[doc.unid] = 'collapsed';
        } else {
          self.expanded[doc.unid] = 'shown';
        }
        console.log(self.expanded[doc.unid]);
      } else { // all docs
        if(!self.expanded.initial) {
          self.expanded.initial = true;
          self.main_doc.ddCollapsed && self.main_doc.toggle();
          self.comments && self.comments.forEach(function(v) {
              if(v.ddCollapsed) {
                v.toggle();
              }
            });
        } else {
          if(self.expanded.all) {
            for(var k in self.expanded) {
              if(k != 'all' && k != 'initial' && k != self.main_doc.unid && self.expanded[k] != 'attached') {
                self.expanded[k] = 'collapsed';
              }
            }
            self.expanded.all = false;
          } else {
            self.comments && self.comments.forEach(function(v) {
              if(!self.expanded[v.unid] || self.expanded[v.unid] == 'collapsed') {
                self.expanded[v.unid] = (v.AttachedDoc && v.AttachedDoc.length) ? 'attached' : 'shown';
              }
            });
            self.expanded.all = true;
          }
        }
      }
      var savedStates = JSON.parse(localStorage.getItem('expandedDocs'));
      if (!savedStates) savedStates = {};
      for (var attrname in self.expanded) {
        if (attrname != 'all' && attrname != 'initial')
          savedStates[attrname] = self.expanded[attrname] == 'attached' ? 'shown' : self.expanded[attrname];
      }
      localStorage.setItem('expandedDocs', JSON.stringify(savedStates));
    };

    self.generateLdapNameForContacts = function(whichDoc) {
      // if(!whichDoc.created) {
        whichDoc.ContactName = [whichDoc.LastName, whichDoc.FirstName, whichDoc.MiddleName].join(' ').trim();
        whichDoc.UserNotesName = 'CN='+translit(whichDoc.ContactName)+'/O=skvirel';
      // }
    };

    self.answer = function(whichDoc, quote, noblockquote, subject, withoutAuthor) {

      function openWindow(discus, isNotification) {
        //discus.current = {};
        discus.quote = '';

        if (quote){ //&& (whichDoc.body || whichDoc.viewBody)){
          var doc = whichDoc;
          if (doc.AuthorRus && !withoutAuthor){
            discus.quote = '<p>'+doc.AuthorRus+ ' ('+$filter('date')(doc.created, "dd.MM.yyyy HH:mm:ss") + ') пишет</p>'+ '<a href="#/discus/'+doc.unid+'/" class="highlighting-quote-'+doc.unid+'">'+quote+'</a>';
          }else{
            discus.quote = '<p>'+ quote +'</p>';
          }
          if (!noblockquote) {
            discus.quote = '<blockquote contenteditable="false">' + discus.quote  + '</blockquote><p><br/></p>'
          }
        }
        if (subject) discus.subject = subject;

        if (isNotification)
          self = discus;

        self.showEditForm('message');
      }

      if ( ~['body.notifications', 'body.notificator'].indexOf($state.current.name) ){
        openWindow(self, true);
        self.prepareForWrite('message');
      } else {
        openWindow($rootScope.mainDiscus, false);
      }

    };


    self.getDatabaseCollection = function(whichDoc) {
      if(self.main_doc && self.isContact && whichDoc && (self.main_doc.unid == whichDoc.unid)) {
        return 'Contacts';
      }
      return 'Portal';
    };

    self.placeMenu = function(doc) {
      self.closeBottomSheet(doc);
      
      if ($rootScope.windowWidth <= 768) doc._meta.isMobile = true;
      else doc._meta.isMobile = false;
      
      if ($('#'+doc.unid+'_dropdown').attr("class").indexOf('open')>0) {
        var menu = $('#'+doc.unid+'_menu');
        menu.removeClass('dropup-menu');
        var menuHeight = menu.css("height").replace('px', '') * 1;
        var menuPosition = menu.offset().top * 1;
        menuPosition = menuPosition - $(window).scrollTop();
        if ($rootScope.windowWidth > 950) menuPosition -= $rootScope.windowHeight;
        menuPosition = Math.round(menuPosition);
        var pageHeight = $(window).height() * 1;
        if (menuHeight + menuPosition > pageHeight && menuHeight < menuPosition) {
          menu.addClass('dropup-menu');
        }
      }
    };

    self.toggleArrayElement = function(whichDoc,whichField,value) {
      if(!whichDoc[whichField]) { whichDoc[whichField] = [value]; return; }
      var a = whichDoc[whichField].indexOf(value);
      if(a > -1) {
        whichDoc[whichField].remove(a);
      } else {
        whichDoc[whichField].push(value);
      }
    };

    self.leaveDiscus = function() {
      Socket.get(function(socket) {
        socket.emit('leaveDiscus', self.main_doc.unid);
        socket.off('refreshDiscusUsers');
        socket.off('addComment');
        socket.off('typing');
      });
    };

    self.joinDiscus = function() {
      Socket.get(function(socket) {
        socket.emit('joinDiscus', self.main_doc.unid);
        socket.off('refreshDiscusUsers');
        socket.off('addComment');
        socket.off('typing');
        socket.on('refreshDiscusUsers', function(users) {
          self.discusUsers = users;
          $rootScope.$apply();
        });
        socket.on('addComment', function(comment) {
          /* flag @scrollToNewComment === TRUE shows that new comment should be scrolled into viewport */
          var body = document.body || document.documentElement;
          var scrollToNewComment;
          if ( ~['body.notificator'].indexOf($state.current.name) )
            scrollToNewComment = Viewport.isInViewport('.expanded.notification .fast-reply-wrap', Notificator.getViewport());
          else if ( ~['body.discus'].indexOf($state.current.name) )
            scrollToNewComment = Viewport.get().bottom === body.offsetHeight; 

          var edited = self.getByUnid(comment.unid);
          if (edited) {
            if (edited._meta && edited._meta.tasking) edited._meta.tasking = null;
            if (comment._meta && comment._meta.tasking) comment._meta.tasking = null;
            angular.merge(edited, comment);
            
            self.prepareForReadAfter(edited);
            
            if (edited.form === 'formTask') edited.linked = self.loadLinks(edited);
            if (comment.authorLogin !== $rootScope.user.username) {
              self.addNewPostLabel(comment);
              $(window).scroll();
            }
          } else {
            self.prepareForReadAfter(comment);
            comment['fromSocket'] = true;
            self.comments.push(comment);
            if (comment.authorLogin === $rootScope.user.username)
              scrollToNewComment = true;
          }
          self.expanded[comment.unid] = 'attached';
          if(!$rootScope.$$phase) $rootScope.$apply();

          if ( scrollToNewComment ) {
            if ( ~['body.notificator'].indexOf($state.current.name) )
              $timeout(Scroll.intoView, 0, true, '.expanded.notification .fast-reply-wrap');
            else if ( ~['body.discus'].indexOf($state.current.name) )
              Scroll.intoView('#'+comment.unid);
          }
          else {
            /* soon here will be the code to show button 'have a mew message' with an arrow down */
          }
          self.sending = false;
          if (typeof self.readDocsExternalHandler === 'function') $timeout(self.readDocsExternalHandler, 0);
        })
        socket.on('typing', function(login) {
          clear = false;
          if (self.usrTyping[login]){
            clearTimeout(self.usrTyping[login]);
            clear = true;
          }
          self.usrTyping[login] = setTimeout(function() {
            delete self.usrTyping[login];
            if(!$rootScope.$$phase) $rootScope.$apply();
          }, 2000);
          if (!clear) if(!$rootScope.$$phase) $rootScope.$apply();
        })
      });
    }

    self.removeReadRoles = function(rolesArr) {
//       $log.warn('discus.removeReadRoles');
      res = [];
      angular.forEach(rolesArr, function(val, key) {
        if (!val.role) res.push(val);
      })
      return res;
    }

    self.getShareParticipants = function (shareSecurity, type) {
        type = typeof type != 'undefined' && type?type:'read';
        var result = {};
        if(shareSecurity){
            for(var replaceDoteDomain in shareSecurity){

                if(typeof result[shareSecurity[replaceDoteDomain].domain] == 'undefined'){
                    result[shareSecurity[replaceDoteDomain].domain] = [];
                }

                var portalSecurity = shareSecurity[replaceDoteDomain];
                if(portalSecurity.privileges && portalSecurity.privileges[type]){
                    for(var i in portalSecurity.privileges[type]){
                        if(typeof portalSecurity.privileges[type][i].username != 'undefined'){
                          var exist = false;
                          for(var t in result){
                            if(result[t] == portalSecurity.privileges[type][i].username){
                              exist = true;
                              break;
                            }
                          }

                          if(!exist){
                            result[shareSecurity[replaceDoteDomain].domain].push(portalSecurity.privileges[type][i].username);
                          }
                        }
                    }
                }
            }
        }

        return result;
    };

    self.getParticipants = function(type) {
//       $log.log('discus.getParticipants: type='+type);
      type = type?type:'read';
      var result = [];

      function isUnique(arr, username) {
        var matched = false;
        angular.forEach(arr, function(item){
          if (item.username == username) matched = true;
        });

        return !matched;
      }

      if (self.main_doc && self.main_doc.security.privileges[type]){
        angular.forEach(self.main_doc.security.privileges[type], function(priv) {
          if (priv.username) {
            priv.name = self.profile.translateName(priv.username);
            if (isUnique(result, priv.username)) result.push(priv);
          }
        })
      }

      return result;
    };

    self.toogleWriteIn = function(){
      if(self.current.parentID && self.current.subjectID){
        delete self.current.parentID;
        delete self.current.subjectID;
      } else {
        self.current.parentID = self.main_doc.unid;
        self.current.subjectID = self.main_doc.unid;
      }

      return self.current.parentID && self.current.subjectID;
    };

    self.edit = function(doc, taskId) {
      var dismissWarning = UserSettings.getDismissEditWarning();
      
      if (!dismissWarning) new TutorPopup('#'+doc.unid+'_dropmenu-wrap', 'Редактирование комментария',
                                      'Просьба не редактировать комментарий, если речь идет \
                                       о добавлении информации, а не исправлении ошибок.', 'notify',
                                       function(){ //onOk
                                         UserSettings.setDismissEditWarning($rootScope.popup.dismiss);
                                         self.showEditForm(doc.form, doc._id, taskId, doc.DocumentType);
                                       },
                                       function(){ //onCancel
                                         UserSettings.setDismissEditWarning($rootScope.popup.dismiss);
                                       }
                                     );
      else self.showEditForm(doc.form, doc._id, taskId, doc.DocumentType);
    };
    
    self.showEditForm = function(form, id, taskId, typeDoc){
//       $log.log('discus.showEditForm');
      var searchParam = $location.search();
      if(form == 'Contact' && searchParam.client == '1C' && (typeDoc == 'Person' || typeDoc == 'Organization')){
        var stateName = typeDoc == 'Person'?'body.1cEditPerson':'body.1cEditOrganization';
        $state.go(stateName, {contactId:id}, {reload: true});
      }
      else {
        self.selectedEditForm = form;
        self.selectedDocId = id;
        self.selectedTaskId = taskId;
        self.selectedTypeDoc = typeDoc;
      }
    };
    self.hideEditForm = function() {
//       $log.log('discus.hideEditForm');
      self.selectedEditForm = null;
      self.selectedDocId = null;
      self.selectedTaskId = null;
      self.selectedTypeDoc = null;
      $rootScope.show.create = {form: '', formType: '', formCats: ''};
    };
    
    self.mobileCheck = function(action, doc, $event) {
//       $log.log('discus.mobileCheck');
      
      if ($rootScope.windowWidth <= 768) {
        var words = {
          task:   'уточнение',
          result: 'результат',
        };

        $mdDialog.show({
          targetEvent: $event,
          template:
            '<md-dialog>' +
            '  <md-dialog-content class="md-dialog-content">' +
            '    <h2 class="md-title">'+words[action][0].toUpperCase() + words[action].substr(1)+'</h2>' +
            '     Добавить '+words[action]+' или показать существующие?</md-dialog-content>' +
            '  <md-dialog-actions>' +
            '    <md-button ng-click="add();" class="">' +
            '      Добавить' +
            '    </md-button>' +
            '    <md-button ng-click="$event.stopPropagation(); show();" class="">' +
            '      Просмотреть' +
            '    </md-button>' +
            '  </md-dialog-actions>' +
            '</md-dialog>',
          controller: 'bottomSheetCtrl',
          clickOutsideToClose: true,
          escapeToClose: true,
          locals: {doc: doc,
                   discus: self,
                   action: action,
          }
        });
      } else {
        self.showEditForm('messagebb', null, doc.unid, action);
      }
    };
    
    self.showBottomSheet = function(action, doc, ev) {
//       $log.log('discus.showBottomSheet');
      if (action == 'result')
        doc._meta.resultsOpen = true;
      else if (action == 'task')
        doc._meta.specifOpen = true;
      else if (action == 'history')
        doc._meta.historyOpen = true;
    };
    
    self.closeBottomSheet = function(doc) {
//       $log.log('discus.closeBottomSheet');
      doc._meta = doc._meta || {};
      doc._meta.historyOpen = false;
      doc._meta.specifOpen = false;
      doc._meta.resultsOpen = false;
    }
    
    self.initQuestionaries = function() {
      self.questionariesList = {};
      Question.getQuestionaries(function(quests) {
        angular.forEach(quests, function(q) {
          self.questionariesList[q.unid] = q.name;
        });
      })
    };
    self.loadLinks = function(doc) {
      var unid = doc.linkedUNID;
      if (!unid) unid = 0;

      BatchHttp({method: 'POST', url: 'api/discussion/loadLinks/'+unid, data: {docUnid: doc.unid}})
            .then(function(response) {
              if (response.data.success) {
                $log.info('loadLinks success');
                doc.linked = {};
                doc.linked.Parent = response.data.parent;
                doc.linked.Children = response.data.children;
                doc.linked.ChildrenCount = Object.keys(response.data.children).length;
                if (unid == 0 &&
                    self.linked &&
                    self.linked.MesLinks &&
                    self.main_doc.linked &&
                    self.main_doc.linked.Children)
                  $.extend(self.main_doc.linked.Children, self.linked.MesLinks);
              } else {
                $log.error('loadLinks error');
              }
            }, httpErrorHandler);
    };
  };
  return obj;
});

portalApp.controller('modalCreateTask', function ($scope, $log, $rootScope, $state, $stateParams, Discus, Popup) {
  if ($state.current.name.indexOf('body.discus') == -1){
    Discus.clear();
  }
  Discus.prepareForWrite('formTask');

  $scope.discus = Discus;
  if ($scope.linkedTo) {
    if($scope.discus.main_doc == null){
      $scope.discus.main_doc = $scope.$parent.mdoc;
    }
    else {
      $scope.discus.toogleWriteIn($scope.linkedTo);
    }
  }

  $scope.ok = function() {
    $scope.discus.current.body = angular.element("[id^='ui-tinymce']").html();
    if (!($scope.discus.current._meta.tasking.performers.length ||
        $scope.discus.current.taskPerformerLat) &&
        !Object.keys($scope.discus.current._meta.tasking.sharePerformers).length) {
      $scope.modalForm.$setValidity('currentPerformer', false);
      return false;
    }
    else {
      $scope.modalForm.$setValidity('currentPerformer', true);
    }

    if ($scope.modalForm.$valid) {
      message = '';

      if (!($scope.discus.current._meta.tasking.performers.length || $scope.discus.current.taskPerformerLat) &&
        !Object.keys($scope.discus.current._meta.tasking.sharePerformers).length) {
        message += "Вы не добавили исполнителя. ";
      }
      if (message.length > 0) {
        new Popup('Discus', message, 'error');
        return false;
      }

      if($rootScope.uploader) delete $rootScope.uploader;
      if ($scope.linkedTo) {
        $scope.discus.current.linkedUNID = $scope.linkedTo;
        $scope.discus.current.SubID = $scope.linkedTo;
      }
      $scope.discus.saveCurrent();
      $rootScope.show.create.form = null;
    }
  }

  $scope.close = function(){
    if($rootScope.uploader) delete $rootScope.uploader;
    $scope.linkedTo = null;
    $rootScope.show.create.form = null;
    $scope.discus.tempParticipants = [];
  }

  $scope.saveDate = function(dEnd) {
    $scope.showDatePeriod=false;
    if (dEnd)
      Discus.current.taskDateEnd = dEnd.getFullYear() + ('0' + (dEnd.getMonth() + 1)).slice(-2) + ('0' + dEnd.getDate()).slice(-2);
  }
});

portalApp.controller('modalCreateProcess', function ($scope, $rootScope, $state, $stateParams, Discus, Popup, $http) {
  if($scope.linkedTo &&  Discus.main_doc == null) {
    Discus.main_doc = $scope.$parent.mdoc;
  }

  if (Discus && Discus.main_doc) {
    $scope.discusData = {};
    $scope.discusData._id = Discus.main_doc._id;
    $scope.discusData.unid = Discus.main_doc.unid;
    $scope.discusData.type = Discus.main_doc.type;
  }

  Discus.clear();
  if ($rootScope.show.create.formType == 'Blog') Discus.isBlog = true;
  Discus.prepareForWrite('formProcess');
  $scope.discus = Discus;

  if ($rootScope.show.create.formCats){
    if (typeof $rootScope.show.create.formCats != 'string') {
      angular.forEach($rootScope.show.create.formCats, function(cat, i) {
  $scope.discus.current['C' + (i+1)] = cat;
      })
    } else {
      $scope.discus.current['C1'] = $rootScope.show.create.formCats;
    }
  }

  $scope.ok = function() {
    $scope.discus.current.body = angular.element("[id^='ui-tinymce']").html();

    if(typeof $scope.discus.current.archiveVacUnid != 'undefined' && $scope.discus.current.archiveVacUnid.length > 0){
      $http({method: 'POST', url: '/api/discussion/get/'+$scope.discus.current.archiveVacUnid})
          .then(function(response) {
            if(typeof response.data != 'undefined' && typeof response.data.success != 'undefined' && response.data.success){
              valid();
            }
            else {
              new Popup('Архивная вакансия', 'Архивная вакансия не найдена, проверьте unid', 'notify');
            }
          });
    }
    else {
      valid();
    }

    function valid(){
      if ($scope.modalForm.$valid) {
        confirmNews(function(){
          if($rootScope.uploader) delete $rootScope.uploader;
          if ($scope.linkedTo)
            $scope.discus.current.linkedUNID = $scope.linkedTo;
          $scope.discus.current.SubID = $scope.linkedTo;
          $scope.discus.saveCurrent();
          $rootScope.show.create.form = null;
        })
      }
    }

    function confirmNews(callback){
      if ($scope.discus.current['C1'] == 'Новости') {
         Popup("Внимание", "Уверены что это требует размещения именно в новостях?", '', true, function(){
          callback();
        }, function(){});
      }else{
        callback();
      }
    }
  };

  $scope.close = function(){
    if($rootScope.uploader) delete $rootScope.uploader;
    $scope.linkedTo = null;
    $rootScope.show.create.form = null;
    if ($scope.discusData && $state.current.name != 'body.index' && $state.current.name != 'body.notifications') {
      $state.go('body.discus', { id: $scope.discusData.unid, type: $scope.discusData.type }, {reload: true});
      delete $scope.discusData;
    }
  }
});

portalApp.controller('modalEditForm', function ($scope, $rootScope, $state, $stateParams, Discus, Popup, $http, Dictionary) {
  $scope.locale = new Dictionary('Locale', true);
  $scope.shareCheckerModel = {};
  if( !~['body.notifications', 'body.notificator'].indexOf($state.current.name) ){
    Discus.prepareForWrite(Discus.selectedEditForm, Discus.selectedDocId, Discus.selectedTaskId, Discus.selectedTypeDoc);
    $scope.discus = Discus;
  } else {
    $scope.discus = $scope.$parent.$parent.$parent.discus;
    $scope.discus.prepareForWrite($scope.discus.selectedEditForm,
                                  $scope.discus.selectedDocId,
                                  $scope.discus.selectedTaskId,
                                  $scope.discus.selectedTypeDoc);
  }

  $scope.getSharePerformer = function (perf) {
    var sharePerf = {};

    if(perf && perf.length > 0){
      for(var key in perf){
        if(!sharePerf[perf[key].domain]){
          sharePerf[perf[key].domain] = [];
        }

        sharePerf[perf[key].domain].push(perf[key].login);
      }
    }

    return sharePerf;
  };

  if($rootScope.$state.current.name==='body.notifications' && $rootScope.notifExp) {
    $scope.discus.current.readAt = $rootScope.notifExp.Time[$scope.discus.main_doc.unid];
    $scope.discus.current.readAtISO = $rootScope.notifExp.TimeISO[$scope.discus.main_doc.unid];
  }

  if($scope.discus.current.shareChecker){
    $scope.shareCheckerModel = $scope.getSharePerformer($scope.discus.current.shareChecker);
  }

  okSave = function(explicitEditing) {
    $scope.discus.hideEditForm();
    if($rootScope.uploader) delete $rootScope.uploader;
    if ($scope.expanded && $scope.expanded[$scope.discus.main_doc.unid] !== undefined) {
      $scope.expanded[$scope.discus.main_doc.unid] = false;
    }
    $scope.discus.saveCurrent(explicitEditing);
  };

  $scope.ok = function(explicitEditing){
    $scope.discus.current.body = angular.element(".edit-doc [id^='ui-tinymce']").html();
    $scope.discus.quote = '';

    if($scope.shareCheckerModel && Object.keys($scope.shareCheckerModel).length > 0){
      $scope.discus.current.shareChecker = $scope.discus.shareToBackend($scope.shareCheckerModel);
    }

    if(typeof $scope.discus.current.archiveVacUnid != 'undefined' && $scope.discus.current.archiveVacUnid.length > 0){
      $http({method: 'POST', url: '/api/discussion/get/'+$scope.discus.current.archiveVacUnid})
        .then(function(response) {
          if(typeof response.data != 'undefined' && typeof response.data.success != 'undefined' && response.data.success){
            valid();
          }
          else {
            new Popup('Архивная вакансия', 'Архивная вакансия не найдена, проверьте unid', 'notify');
          }
        });
    }
    else {
      valid();
    }

    function valid(){
      if ($scope.discus.current.action === 'check' && (!$scope.discus.current.CheckerLat ||
          $scope.discus.current.CheckerLat.length === 0) && (!$scope.discus.current.shareChecker ||
          $scope.discus.current.shareChecker.length == 0)) {
        $scope.modalForm.$setValidity('CheckerLat', false);
        return false;
      }

      var main = $scope.discus.main_doc;

      if ((main.ToSite === '1' || (main.form == 'Contact' && (main.ContactStatus.indexOf(7) !== -1 || main.ContactStatus.indexOf('7') !== -1 ||
          main.ContactStatus.indexOf(10) !== -1 && main.ContactStatus.indexOf('10') !== -1))) && $scope.discus.current.NotForSite !== '1'){
        Popup("Публичная тема", "Внимание. Ответ публикуется на сайте! Публиковать?", '', true, function(){
          okSave(explicitEditing);
        }, function(){});
      }else{
        okSave(explicitEditing);
      }
    }
  };

  $scope.close = function() {
    if($rootScope.uploader) delete $rootScope.uploader;
    $scope.discus.current = null;
    $scope.discus.hideEditForm();
  };

  $scope.saveDate = function(dEnd) {
    $scope.showDatePeriod=false;
    if (dEnd)
      Discus.current.taskDateEnd = dEnd.getFullYear() + ('0' + (dEnd.getMonth() + 1)).slice(-2) + ('0' + dEnd.getDate()).slice(-2);
  }
});

portalApp.controller('createSubTotalCtrl', function ($scope, $rootScope, $state, $stateParams, Discus, Popup, $http, Dictionary) {
  $scope.locale = new Dictionary('Locale', true);

  if (Discus.selectedEditForm)
    Discus.prepareForWrite(Discus.selectedEditForm, Discus.selectedDocId, Discus.selectedTaskId, Discus.selectedTypeDoc);
  else
    Discus.prepareForWrite($rootScope.show.create.form);

  $scope.discus = Discus;

  okSave = function(explicitEditing) {
    $scope.discus.hideEditForm();
    if($rootScope.uploader) delete $rootScope.uploader;
    if ($scope.expanded && $scope.expanded[$scope.discus.main_doc.unid] !== undefined) {
      $scope.expanded[$scope.discus.main_doc.unid] = false;
    }
    $scope.discus.saveCurrent(explicitEditing);
  };

  $scope.ok = function(explicitEditing){
    $scope.discus.current.body = angular.element("[id^='ui-tinymce']").html();
    if(valid()) {
      okSave(explicitEditing);
    }

    function valid(){
      if ($scope.discus.current.subject != '') {
        return true;
      } else return false;
    }
  };

  $scope.close = function() {
    if($rootScope.uploader) delete $rootScope.uploader;
    $scope.discus.current = null;
    $scope.discus.hideEditForm();
  };

});

portalApp.controller('modalCreateVoteCtrl', function ($scope, $state, $log, $http, $rootScope, Contact, Dictionary, Profile, AutoComplete, MultiselectHelper, Discus, Voting, Popup) {
  if ($state.current.name.indexOf('body.discus') == -1){
    Discus.clear();
  }
  Discus.prepareForWrite('formVoting');
  $scope.discus = Discus;
  $scope.discus.current._meta.voting = new Voting($scope.doc);

  $scope.discus.current.AnswersData = ['', ''];
  $scope.discus.current.AnswersPictures = ['', ''];
  $scope.discus.current.AnswersPicturesNames = ['', ''];
  $scope.discus.current.answers = [];
  $scope.discus.current.AnswersLim = 1;
  $scope.discus.current.subject = '';

  if ($rootScope.show.create.formType == 'index') $scope.discus.current.ShowOnIndex = 1;

  $scope.doc = $scope.discus.current;

  $scope.toggleRequired = function(index, enable) {
    if (!enable)
      $('#answer_'+index).removeAttr('required');
    else
      $('#answer_'+index).attr('required', 'required');
  }
  $scope.uploadPicHandler = function() {
    if ($scope.discus.current.uploaderInvoked != null && $scope.discus.current.uploaderInvoked != undefined) {
      var index = $scope.discus.current.uploaderInvoked;
      var attach = $scope.discus.current.attachments[$scope.discus.current.attachments.length-1][0];
      if (attach) {
        $scope.discus.current.AnswersPictures[index] = attach.link;
        $scope.discus.current.AnswersPicturesNames[index] = attach.doc.originalFilename;
      }
      delete $scope.discus.current.uploaderInvoked;
    }
  }
  $scope.close = function() {
    if($rootScope.uploader) delete $rootScope.uploader;
    $rootScope.show.create.form = null;
    $rootScope.show.create.formType = null;
  }
  $scope.ok = function() {
    if ($rootScope.show.create.formType == 'regular' && $scope.discus.tempParticipants.length < 1) {
      $scope.modalForm.$setValidity('participantsRequired', false);
      return false;
    }
    else
      $scope.modalForm.$setValidity('participantsRequired', true);

    if ($scope.modalForm.$valid) {
      var conf = false;
      if ($rootScope.show.create.formType == 'regular') {
        conf = true;
      }
      message = '';

      if (($scope.doc.AnswersData[0].length == 0 && $scope.doc.AnswersPictures[0].length == 0) || ($scope.doc.AnswersData[1].length == 0 && $scope.doc.AnswersPictures[1].length == 0)) {
        message += "Должно быть минимум 2 ответа. ";
      }
      if (message.length > 0) {
        new Popup('Discus', message, 'error');
        return false;
      }

      if (!conf) conf = confirm("Сохранить опрос? В дальнейшем редактирование опроса будет недоступно.");
      if (conf == true){
        if($rootScope.uploader) delete $rootScope.uploader;
        $scope.discus.saveCurrent()
        $scope.close();
      } else return false;
    }
  }
});

portalApp.controller('modalCreateAdaptCtrl', function ($scope, $state, $http, $log, $rootScope, Contact, Dictionary,
                  Profile, AutoComplete, MultiselectHelper, Discus, Adaptation, Popup) {
  if ($state.current.name.indexOf('body.discus') == -1){
    Discus.clear();
  }
  Discus.prepareForWrite('formAdapt');
  $scope.discus = Discus;

  $scope.multiselectHelper = new MultiselectHelper();
  $scope.discus.current._meta.adaptation = new Adaptation($scope.discus.current);
  $scope.discus.current.profile = new Profile();
  $scope.doc = $scope.discus.current;
  $scope.RegionIDDict = new Dictionary('RegionID', true);
  $scope.doc.WorkGroup = [];
  $scope.doc.WorkGroupEng = [];
  $scope.doc.companyName = '';
  $scope.doc.DtWork = '';
  $scope.doc.Sex = 0;
  $scope.doc.currency = '';

  $scope.close = function() {
    if($rootScope.uploader) delete $rootScope.uploader;
    $rootScope.show.create.form = null;
  };
  $scope.ok = function() {
    if($scope.doc.WorkGroup.length == 0 || $scope.doc.WorkGroupEng.length == 0){
      $scope.modalForm.$setValidity('workGroup', false);
    }
    else {
      $scope.modalForm.$setValidity('workGroup', true);
    }

    if($scope.doc.Salary && (typeof Number($scope.doc.Salary) != 'number' || isNaN(Number($scope.doc.Salary)))){
      $scope.modalForm.$setValidity('Salary', false);
    }
    else {
      $scope.modalForm.$setValidity('Salary', true);
    }

    if($scope.doc.Salary && !$scope.doc.currency){
      $scope.modalForm.$setValidity('Currency', false);
    }
    else {
      $scope.modalForm.$setValidity('Currency', true);
    }


    if(!$scope.doc.Country){
      $scope.modalForm.$setValidity('Country', false);
    }
    else {
      $scope.modalForm.$setValidity('Country', true);
    }

    if ($scope.modalForm.$valid) {
      message = '';

      if(!$scope.doc.companyName){
        $scope.doc.companyName = null;
        message += "Имя компании должно быть указано.";
      }

      if (!$scope.doc.Manager) {
        $scope.doc.TempManager = null;
        message += "Руководитель не выбран. ";
      }
      if (!$scope.doc.HeadIT) {
        $scope.doc.TempHeadIT = null;
        message += "Начальник IT отдела не выбран. ";
      }
      if (!$scope.doc.HeadHR) {
        $scope.doc.TempHeadHR = null;
        message += "Директор по персоналу не выбран. ";
      }
      if ($scope.doc.Login == $scope.user.username) {
        $scope.doc.Login = null;
        message += "Некорректный логин. ";
      }
      if ($scope.doc.section.length == 0) {
        message += "Отдел не выбран. ";
      }
      if (!$scope.doc.DtWork) {
        message += "Укажите дату приёма на работу. ";
      }
      if (!$scope.doc.Sex) {
        message += "Укажите пол. ";
      }
      if (message.length > 0) {
        $log.warn(message);
        new Popup('Discus', message, 'error');
        return false;
      }
      $scope.close();
      if($rootScope.uploader) delete $rootScope.uploader;
      $scope.discus.saveCurrent();
    }
  }
});

portalApp.controller('voteMenuCtrl', function ($scope, $state, $http, $rootScope, Contact, Dictionary,
                      Profile, AutoComplete, MultiselectHelper, Discus, Adaptation) {
  $scope.doc.AuthorRusShort = $scope.doc.AuthorRus.substring(0, $scope.doc.AuthorRus.indexOf(" ")+2)+'.';
});

portalApp.controller('docMenuCtrl', function($scope, $rootScope, $http, Security, Tasking, BatchHttp, Discus){
  
  $scope.loadParentTask = function(doc){
    if (doc.parentForm != 'formTask' || (doc._meta && doc._meta.tasking)) return false;
                     
    BatchHttp({method: 'GET', url: 'api/discussion/get/'+doc.parentUnid}).then(function(response) {
      var result = response.data['document'];
      result._meta = result._meta || {};
      result._meta.tasking = new Tasking(result);
      
      $scope.doc = result;
      $scope.discus = Discus;
      $scope.discus.main_doc = $scope.doc;
      }, httpErrorHandler);
  };
  
  $scope.checkWriteSecurity = function(doc){
    var security = new Security(doc);
    return security.hasPrivilege('write', $rootScope.user.username) != null;
  };

  $scope.showFormEmit = function(form, type, cats, linkedTo, parentDoc, quote, data){
    $scope.$emit('showFormEvent', {
      form:form,
      type:type,
      cats:cats,
      linkedTo:linkedTo,
      parentDoc:parentDoc,
      quote:quote,
      data:data
    });
  }
});

portalApp.controller('tagsCtrl', function ($scope, $state, $http, $rootScope, $log, BatchHttp, Contact, Dictionary,
                      Profile, AutoComplete, MultiselectHelper, Discus, Voting, Popup) {
  $scope.showAddTagForm = false;
  if (!$scope.doc.Tags) $scope.doc.Tags = [];
  $scope.params = {};
  $scope.params.MyOnly = false;

  $scope.invokeMenu = function() {
    $scope.showAddTagForm = !$scope.showAddTagForm;
    $scope.doc.tempTag = '';
  }

  $scope.addTag = function(doc) {
    var tag = doc.tempTag;
    doc.tempTag = '';
    tag = tag.replace(/[&\/\\#+()$~%.'":*?<>{}]/g, '');

    if (tag.length < 1) return false;

    var tags = tag.split(",");

    for (var i=0; i<tags.length; i++) {
      tags[i] = tags[i].trim();
      if (tags[i].length == 0) {
        tags.splice(i, 1);
        i--;
        continue;
      }
      if ($scope.doc.Tags && $scope.doc.Tags.length > 0) {
        for (var j = 0; j < $scope.doc.Tags.length; j++) {
          if ($scope.doc.Tags[j]['name'] == tags[i]) {
            var present = false;
            for (user in $scope.doc.Tags[j]['users']) {
              if ($scope.doc.Tags[j]['users'][user] == $rootScope.user.username) present = true;
            }
            if (!present) $scope.doc.Tags[j]['users'].push($rootScope.user.username);
            return true;
          }
        }
      }
      var newTag = {'name': tags[i], 'users': [$rootScope.user.username]};
      $scope.doc.Tags.push(newTag);
    }

    if (doc._id != 0)
      $scope.saveTagDB(tags);
  }

  $scope.removeTag = function(tag) {
    if ($scope.doc.Tags && $scope.doc.Tags.length > 0) {
      for (var i = 0; i < $scope.doc.Tags.length; i++) {
        if ($scope.doc.Tags[i]['name'] == tag) {
          var present = -1;
          for (var j = 0; j < $scope.doc.Tags[i]['users'].length; j++) {
            if ($scope.doc.Tags[i]['users'][j] == $rootScope.user.username) present = j;
          }
          if (present >= 0) {
            $scope.doc.Tags[i]['users'].splice(present, 1);
              if ($scope.doc.Tags[i]['users'].length == 0) {
                $scope.doc.Tags.splice(i, 1);
              }
            }
            return true;
          }
        }
      }
  }

  $scope.saveTagDB = function(tags) {
      BatchHttp({method: 'POST', url: 'api/tags/addTags/'+$scope.doc.unid, data: { 'tags': tags } })
      .then(function(response) {
        if(!response.data.success) {
          new Popup('Discus', response.data.message, 'error');
        }
      }, httpErrorHandler);
  }
  $scope.deleteTagDB = function(tag) {
    BatchHttp({method: 'POST', url: 'api/tags/deleteTag/'+$scope.doc.unid, data: { 'tag': tag } })
    .then(function(response) {
      if(!response.data.success) {
        new Popup('Discus', response.data.message, 'error');
      }
    }, httpErrorHandler);
  }
  $scope.getTags = function(tag) {
    return $http.get('api/tags/getTags', {params: { tag: tag, myonly: $scope.params.MyOnly }})
    .then(function(response){
      return response.data.tags.map(function(item){
        return item.name;
      });
    });
  }

});

/** Discussion factory singleton */
portalApp.factory('Discus', function(DiscusInstance)
{
  return new DiscusInstance();
});
