portalApp
  .factory('Popup', popup)
  .factory('DeputyPopup', DeputyPopup)
  .factory('TutorPopup', tutorPopup)
  .factory('Tasks', tasks)
  .factory('Tags', tags)
  .factory('History', history)
  .factory('Dictionary', dictionary)
  .factory('DictionaryPrototype', dictionaryPrototype)
  .factory('Statuses', statuses)
  .factory('Colors', colors)
  .factory('Favorites', favorites)
  .factory('FullSearch', fullSearch)
  .factory('Security', security)
  .factory('AutoComplete', AutoComplete)
  .factory('MultiselectHelper', MultiselectHelper)
  .factory('TretoDateTime', tretoDateTime)
  .factory('Decoration', decoration)
  .factory('BatchHttp', batchHttp)
  .factory('Adaptation', adaptation)
  .factory('GUID', GuidFactory)
  .factory('Mail', mail)
  .factory('ListDictionaries', ListDictionaries)
  .factory('Discounts', discounts)
  .factory('DiscountFields', discountFields)
  .factory('Question', question)
  .factory('DiscusSharedSvc', discusSharedSvc)
  .factory('Stat', Stat)
  .factory('C1Logs', C1Logs)
  .factory('SMS', SMS)
  .factory('TECollection', TECollection)
  .factory('Viewport', Viewport)
  .factory('Notificator', Notificator)
  .factory('Graph', Graph)
  .factory('Plural', Plural)
  .factory('Settings', Settings)
  .factory('RequestAnimationFrame', RequestAnimationFrame)
  .factory('CancelAnimationFrame', CancelAnimationFrame)
  .factory('WheelEventName', WheelEventName)
  .factory('Scroll', Scroll)
  .factory('Color', Color)
  .factory('UserSettings', UserSettings)
  ;

function UserSettings($rootScope, $http){
  var self = this;
  self.checkedCommand = false;
  self.dismissEditWarning = false;

  self.getCheckedCommand = function(){
    if($rootScope.user &&
        $rootScope.user.portalData &&
        $rootScope.user.portalData.userSettings &&
        $rootScope.user.portalData.userSettings.checkedCommand){
      return $rootScope.user.portalData.userSettings.checkedCommand;
    }
    return [];
  };
  self.setCheckedCommand = function(v, callback){
    $http.post('api/user/setSettings', {data:{checkedCommand:v}}).then(function(response){
      if(response.data && response.data.success){
        if(!$rootScope.user.portalData.userSettings){
          $rootScope.user.portalData.userSettings = {};
        }
        $rootScope.user.portalData.userSettings.checkedCommand = v;
        if(callback){
          callback();
        }
      }
    });
  };
  
  self.getDismissEditWarning = function(){
    if($rootScope.user &&
        $rootScope.user.portalData &&
        $rootScope.user.portalData.userSettings &&
        $rootScope.user.portalData.userSettings.dismissEditWarning){
      return $rootScope.user.portalData.userSettings.dismissEditWarning;
    }
    return false;
  };
  self.setDismissEditWarning = function(v, callback){
    $http.post('api/user/setSettings', {data:{dismissEditWarning:v}}).then(function(response){
      if(response.data && response.data.success){
        if(!$rootScope.user.portalData.userSettings){
          $rootScope.user.portalData.userSettings = {};
        }
        $rootScope.user.portalData.userSettings.dismissEditWarning = v;
        if(callback){
          callback();
        }
      }
    });
  };

  return self;
}

function DeputyPopup($rootScope, $uibModal) {
  var DeputyPopup = function(user, deputy, terms, isParticipants, callback){
    $rootScope.popup = {};
    $rootScope.popup.modal = $uibModal.open({
      templateUrl: '/bundles/tretoportal/partials/modals/deputySelect.html',
      size: 'lg',
      controller: function($scope){
        $scope.isParticipants = isParticipants;
        $scope.user = user;
        $scope.deputy = deputy;
        $scope.terms = terms;
        $scope.userName = $rootScope.users[user].name;
        $scope.deputyName = $rootScope.users[deputy].name;
        $scope.result = $scope.isParticipants?[$scope.deputy]:$scope.deputy;

        $scope.checkClick = function(login){
          var index = $scope.result.indexOf(login);
          if(index !== -1){
            $scope.result.splice(index, 1);
          }
          else {
            $scope.result.push(login);
          }
        };

        $scope.cancel = function(){
          $rootScope.popup.modal.close();
          callback(false);
        };

        $scope.ok = function(){
          $rootScope.popup.modal.close();
          if(angular.isArray($scope.result)){
            for(var i in $scope.result){
              callback($scope.result[i], true);
            }
          }
          else {
            callback($scope.result, $scope.result == $scope.user);
          }
        }
      },
      resolve: {}
    });
    $rootScope.popup.modal.result.then();
  };
  return (DeputyPopup);
}

function popup($rootScope, $uibModal, localize) {
    var Popup = function(title, message, style, useCancel, onOk, onCancel, buttonTitles) {
        $rootScope.popup = {};
        $rootScope.popup.title = localize(title);
        $rootScope.popup.message = localize(message);
        $rootScope.popup.style = ( style ? 'dialog-header-'+style : '');
        $rootScope.popup.useCancel = useCancel;
        $rootScope.popup.buttonTitles = buttonTitles;
        $rootScope.popup.modal = $uibModal.open({
            templateUrl: '/bundles/tretoportal/partials/modal.html',
            controller: 'popupCtrl',
            resolve: {}
        });
        $rootScope.popup.modal.result.then(onOk,onCancel);
    };
    return (Popup);
}

function tutorPopup($document, $rootScope, $uibModal, localize) {
    var tutorPopup = function(parentSelector, title, message, style, onOk, onCancel) {
        var parentElem = parentSelector ? $(parentSelector) : undefined;
        
        $rootScope.popup = {};
        $rootScope.popup.title = localize(title);
        $rootScope.popup.message = localize(message);
        $rootScope.popup.dismiss = false;
        $rootScope.popup.style = ( style ? 'dialog-header-'+style : '');
        $rootScope.popup.modal = $uibModal.open({
            templateUrl: '/bundles/tretoportal/partials/modals/tutor-popup.html',
            appendTo: parentElem,
            windowClass: 'tutor-popup',
            backdrop: false,
            controller: 'popupCtrl',
        });
        $rootScope.popup.modal.result.then(onOk,onCancel);
    };
    return (tutorPopup);
}

function tasks($http, BatchHttp, Popup, $rootScope, TretoDateTime){
  var obj = function(usersArr) {
    var self = this;
    self.startDate = new Date(1990, 0, 1);
    self.endDate = new Date();
    self.statusOptions = [{name:"Все", value: 'all'},
                          /*{name:"Открыта", value:"open"},
                          {name:"Закрыта", value:"close"},*/
                          {name:"Выполненные", value:"completed"},
                          {name:"Не выполненные", value:"incomplete"},
                          {name:"Не принятые", value:"notAccepted"},
                          {name:"Просроченные", value:"overdue"},
                          {name:"Подвешенные", value:"suspended"}];
    self.params = {
      iAmAuthor: true,
      iAmPerformer: true,
      status: 'all',
      subject: null,
      author: null,
      performer: null,
      created: {start: null, end: null},
      completed: {start: null, end: null},
      priority: undefined
    };

    self.oldStatus = self.params.status;

    self.overdueDate = new Date();
    self.overdueDate.setDate(self.overdueDate.getDate() - 1)
    self.overdueDate.setHours(23);
    self.overdueDate.setMinutes(59);
    self.overdueDate.setSeconds(59);

    self.searchEnd = false;
    self.users = [];
    if (usersArr) {
      for (var i in usersArr) {
        self.users.push(usersArr[i].login);
      }
    }
    self.time = 0;
    self.saved = true;
    self.records = [];

    self.daysAgo = function(date, days) {
      daysInSec = days * 86400;
      now = new Date();
      now = now.getTime() / 1000 | 0;
      dateDayAgo = now - daysInSec;
      docDate = TretoDateTime.iso8601.toDate(date);
      docDate = docDate.getTime() / 1000 | 0;
      return dateDayAgo < docDate;
    }

    self.search = function(query, offset, limit, sort) {
      if(! self.saved) { return; }
      var timeStart = new Date().getTime();
      //self.records = [];
      self.saved = false;
      self.recordsLength = self.records.length;

      var sortParam = !sort || typeof sort.reverse == 'undefined' || !sort.predicate ? {reverse:true, predicate:'created'} : sort;

      var now = new Date();

      var date = ('0' + (now.getDate())).slice(-2) + '.' + ('0' + (now.getMonth() + 1)).slice(-2) + '.' +(now.getFullYear());
      var url = 'api/wp/user/'+ $rootScope.user.portalData.unid +'/'+ date;
      var daysOff = 0;
      BatchHttp({method: 'GET', url: url}).
        then(function(response) {
          daysOff = response.data.daysOff;
        });

      if (!query) {
        //var q = self.parseQuery(); console.log('q: ', q);
        query = btoa(escape(angular.toJson(self.parseQuery())));
      } else {
        query = btoa(query);
      }
      BatchHttp({method: 'GET', url: 'api/discussion/tasks' + (query ? '/'+query : ''), params: {offset: offset, limit: limit, sort: sortParam}}).
        then(function(response) {
          self.saved = true;
          if(!response.data.success) { new Popup('tasks', response.data.message, 'error'); return; }
          self.records = self.records.concat(response.data.documents);

          //temp fix for time format
          for (var i = 0; i < self.records.length; i++) {
            if (!self.records[i].taskDateRealEnd) self.records[i].taskDateRealEnd = self.records[i].taskDateCompleted || '';
            if (self.records[i].taskDateRealEnd.length > 8)
              self.records[i].taskDateRealEnd = self.records[i].taskDateRealEnd.substr(0, 8);
          }

          //this allows to show not only 'open' and 'close' status but several more specific
          for (var i = 0; i < self.records.length; i++) {
            self.records[i].parsedStatus = '';
            if (self.records[i].status === 'close') {
              if (self.records[i].TaskStateCurrent === 25)
                self.records[i].parsedStatus = 'completed';
              else
                self.records[i].parsedStatus = 'close';
            } else if (self.records[i].status === 'open') {
              if ((!self.records[i].taskDateCompleted || self.records[i].taskDateCompleted === '')
                  && (self.records[i].TaskStateCurrent === 30)) {
                self.records[i].parsedStatus = 'suspended';
              } else if ((!self.records[i].taskDateCompleted || self.records[i].taskDateCompleted === '')
                 && (!self.records[i].taskDateRealEnd || self.records[i].taskDateRealEnd === '')) {
                self.records[i].parsedStatus = 'notAccepted';
              } else if ((!self.records[i].taskDateCompleted || self.records[i].taskDateCompleted === '')
                        && (self.records[i].taskDateRealEnd || self.records[i].taskDateRealEnd !== '')) {
                if (self.records[i].taskDateRealEnd < TretoDateTime.iso8601.fromDateTime(self.overdueDate)
                    && (self.records[i].TaskStateCurrent == 5 || self.records[i].TaskStateCurrent == 7))
                  self.records[i].parsedStatus = 'overdue';
                else
                  self.records[i].parsedStatus = 'incomplete';
              } else {
                self.records[i].parsedStatus = 'open';
              }
            } else {
              self.records[i].parsedStatus = self.records[i].status;
            }
          }

          self.time = (new Date().getTime() - timeStart) / 1000;

          if ($rootScope.$state.current.name == "body.index") {
            self.Tags = {};
            $rootScope.expiredPopup.Expired        = [],
            $rootScope.expiredPopup.NotAccepted    = [],
            $rootScope.expiredPopup.EscalatedOnce  = [],
            $rootScope.expiredPopup.EscalatedTwice = [];

            self.toShowPopup = false;
            for (var i in self.records) {

              if ((!self.records[i].taskDateRealEnd || !self.daysAgo(self.records[i].taskDateRealEnd, 1+daysOff))
                 && self.records[i].TaskStateCurrent >= 0 && self.records[i].TaskStateCurrent != 10 && self.records[i].TaskStateCurrent < 20) {

                self.toShowPopup = true;
                var isEscalated = false;
                if (self.records[i].EscalationManagers && self.records[i].EscalationManagers.length > 0) {
                  for (var j in self.records[i].EscalationManagers) {
                    if (self.records[i].EscalationManagers[j].type == 'notify') {
                      if (!isEscalated) {
                        isEscalated = true;
                        $rootScope.expiredPopup.EscalatedOnce.push(self.records[i]);
                        $rootScope.expiredPopup.EscalatedOnceBoss =  self.records[i].EscalationManagers[j].login;
                      } else {
                        $rootScope.expiredPopup.EscalatedTwice.push(self.records[i]);
                        $rootScope.expiredPopup.EscalatedTwiceBoss = self.records[i].EscalationManagers[j].login;
                      }
                    }
                  }
                }
                if (!isEscalated) {
                  if      (!self.records[i].taskDateRealEnd)                          $rootScope.expiredPopup.NotAccepted.push(self.records[i]);
                  else if (!self.daysAgo(self.records[i].taskDateRealEnd, 1+daysOff)) $rootScope.expiredPopup.Expired.push(self.records[i]);
                }
              }

              if (self.toShowPopup) $rootScope.expiredPopup.ready = true;

              if (self.records[i].tags.length == 0) {
                self.Tags['Без тега'] = self.Tags['Без тега'] ? self.Tags['Без тега'] : [];
                self.Tags['Без тега'].push(self.records[i]);
              } else {
                for (var j in self.records[i].tags) {
                  var tag = self.records[i].tags[j].name;
                  self.Tags[tag] = self.Tags[tag] ? self.Tags[tag] : [];
                  self.Tags[tag].push(self.records[i]);
                }
              }
            }

          }
          self.searchEnd = self.records.length - self.recordsLength < limit;
        }, httpErrorHandler);
    };

    self.performerMe = function(on, query) {
      if(on) {
        query.$and[0] = {'taskPerformerLat': {'$in': [$rootScope.user.username,
                                          $rootScope.user.portalData.FullName,
                                          $rootScope.user.portalData.FullNameRaw]}};
      } else {
        delete query.$and[0];
      }
      //console.dir(query);
    };



    // This group of functions is marked obsolete as of May 26, 2017
    // For these functions aren't being used anymore
    /*self.authorMe = function(on, query) {
      if(on) {
        query.$and[0] = {'Author': {'$in': [$rootScope.user.username,
                                          $rootScope.user.portalData.FullName,
                                          $rootScope.user.portalData.FullNameRaw]}};
      } else {
        delete query.$and[0];
      }
    };

    self.performerSet = function(on, query) {
      if(on) {
        query.$and[0] = {$or:[]};
        for (var i in $rootScope.tasksUsersArr) {
          query.$and[0].$or.push({"taskPerformerLat": {'$in': [$rootScope.tasksUsersArr[i].login,
                                            $rootScope.tasksUsersArr[i].FullName,
                                            $rootScope.tasksUsersArr[i].FullNameRaw]}});
        }
      } else {
        delete query.$and[0];
      }
      //console.dir(query);
    };

    self.authorSet = function(on, query) {
      if(on) {
        query.$and[0] = {$or:[]};
        for (var i in $rootScope.tasksUsersArr) {
          query.$and[0].$or.push({"Author": {'$in': [$rootScope.tasksUsersArr[i].login,
                                            $rootScope.tasksUsersArr[i].FullName,
                                            $rootScope.tasksUsersArr[i].FullNameRaw]}});
        }
      } else {
        delete query.$and[0];
      }
      //console.dir(query);
    };

    self.mine = function(on, query) {
      if (on){
        query.$and[1] = {$or : [{"taskPerformerLat": {'$in': [$rootScope.user.username,
                                            $rootScope.user.portalData.FullName,
                                            $rootScope.user.portalData.FullNameRaw]}},
                                {"Author": {'$in': [$rootScope.user.username,
                                            $rootScope.user.portalData.FullName,
                                            $rootScope.user.portalData.FullNameRaw]}}]};
      }else{
        delete query.$and[1];
      }
      //console.dir(query);
    }

    self.findMine = function(on, query) {
      self.users = [];
      $rootScope.tasksUsersArr = [];
      if (on){
        $rootScope.tasksUsersArr.push({'login': $rootScope.user.username,
                                      'FullName': $rootScope.user.portalData.FullName,
                                      'FullNameRaw': $rootScope.user.portalData.FullNameRaw,
                                      'shortName': $rootScope.user.portalData.LastName+' '+$rootScope.user.portalData.name
        });
        self.users.push($rootScope.tasksUsersArr[0].shortName);

        if (query.$and[0] && query.$and[0].$or[0].taskPerformerLat) {
          self.performerSet(true, query);
        }
        if (query.$and[0] && query.$and[0].$or[0].Author) {
          self.authorSet(true, query);
        }

        query.$and[1] = {$or : [{"taskPerformerLat": {'$in': [$rootScope.user.username,
                                            $rootScope.user.portalData.FullName,
                                            $rootScope.user.portalData.FullNameRaw]}},
                                {"Author": {'$in': [$rootScope.user.username,
                                            $rootScope.user.portalData.FullName,
                                            $rootScope.user.portalData.FullNameRaw]}}]};

      }else{
        delete query.$and[1];
      }
      //console.dir(query);
    }

    self.addUser = function(user, query) {
      console.log('adding user');
      $rootScope.tasksUsersArr = $rootScope.tasksUsersArr ? $rootScope.tasksUsersArr : [];
      $rootScope.tasksUsersArr.push(user);
      if (query.$and && query.$and[0]) delete query.$and[0];
      if (!query.$and[1]){
        query.$and[1] = {$or:[]};
      }
      query.$and[1].$or.push({$or:[{"taskPerformerLat": {'$in': [user.login,
                                            user.FullName,
                                            user.FullNameRaw]}},
                                      {"Author": {'$in': [user.login,
                                            user.FullName,
                                            user.FullNameRaw]}}]});
      //console.dir(query);
    };

    self.getUsers = function(query) {
      var res = [];
      if (query.$and[1] && query.$and[1].$or && query.$and[1].$or[0].$or){
        angular.forEach(query.$and[1].$or, function(or) {
          res.push(or.$or[1]['Author'].$in[0]);
        })
      }
      return res;
    };

    self.delUser = function(login, query) {
      //console.dir(query);
      for (var i in $rootScope.tasksUsersArr) {
        console.log(login+'?='+$rootScope.tasksUsersArr[i].login);
        if ($rootScope.tasksUsersArr[i].login == login) {
          $rootScope.tasksUsersArr.splice(i, 1);
        }
      }
      if (query.$and[1]){
        for (var i in query.$and[1].$or) {
          if ((query.$and[1].$or[i].$or[0].taskPerformerLat && query.$and[1].$or[i].$or[0].taskPerformerLat.$in[0] == login) ||
              (query.$and[1].$or[i].$or[0].Author && query.$and[1].$or[i].$or[0].Author.$in[0]) == login) {
                query.$and[1].$or.splice(i, 1);
              }
        }
      }
      if (query.$and[0]){
        for (var i in query.$and[0].$or) {
          if ((query.$and[0].$or[i].taskPerformerLat && query.$and[0].$or[i].taskPerformerLat.$in[0] == login) ||
              (query.$and[0].$or[i].Author && query.$and[0].$or[i].Author.$in[0]) == login) {
                query.$and[0].$or.splice(i, 1);
              }
        }
      }
      //console.dir(query);
    };

    self.setStatus = function(status, query) {
      self.clearPeriod(query);
      switch (status){
        case 'success':
          query.$and[2] = {$and: [ { 'taskDateCompleted': {$ne: ""} }, { 'taskDateCompleted': { $exists: true } } ]};
          break;
        case 'notSuccess':
          query.$and[2] = {$or: [ { 'taskDateCompleted': "" }, { 'taskDateCompleted': { $exists: false } } ]};
          break;
        case 'notGet':
          query.$and[2] = {$and: [ {$or: [ { 'taskDateCompleted': "" }, { 'taskDateCompleted': { $exists: false } } ] },
                                   {$or: [ { 'taskDateRealEnd': ""}, {'taskDateRealEnd': {$exists: false}} ] } ] };
          break;
        case 'timedOut':
          var dat = new Date();
          query.$and[2] = {$and: [ {$or: [ { 'taskDateCompleted': "" }, { 'taskDateCompleted': { $exists: false } } ] },
                                         { 'taskDateRealEnd': {$lte: dat.toISOString().replace(/[\-\:]/g, "")}}, {'status':{$ne:"cancelled"}} ] };
          break;
        default:
          delete query.$and[2];
      }
      //console.dir(query);
    };

    self.clearPeriod = function(query) {
      delete query.$and[3];
      delete query.$and[4];
    };*/

    self.setAuthor = function(author) {
      if ( _.isObject(author)
        && _.isString(author.name)
        && author.name !== ''
        && _.isString(author.id)
        && author.id !== '' ) {
        self.params.author = author;
        self.params.iAmAuthor = self.params.author.id === $rootScope.user.username;
      } else new Popup('Error', 'Incorrect data given to Tasks.setAuthor(author), @author shoud implement interface {id: STRING, name: STRING} and each of these fields should not be empty string');
    };

    self.removeAuthor = function() {
      self.params.author = null;
      self.params.iAmAuthor = false;
    };

    self.getAuthorName = function() {
      return self.params.author === null || !self.params.author.name ? '' : self.params.author.name;
    };

    self.setPerformer = function(performer) {
      if ( _.isObject(performer)
        && _.isString(performer.name)
        && performer.name !== ''
        && _.isString(performer.id)
        && performer.id !== '' ) {
        self.params.performer = performer;
        self.params.iAmPerformer = self.params.performer.id === $rootScope.user.username;
      } else new Popup('Error', 'Incorrect data given to Tasks.setPerformer(performer), @performer shoud implement interface {id: STRING, name: STRING} and each of these fields should not be empty string');
    };

    self.removePerformer = function() {
      self.params.performer = null;
      self.params.iAmPerformer = false;
    };

    self.getPerformerName = function() {
      return self.params.performer === null || !self.params.performer.name ? '' : self.params.performer.name;
    };

    // function covers part of functionlity that previously was in view for self.params.state checkeboxes
    self.changeStatus = function() {
      switch (self.params.status) {
        case undefined:
        case 'completed':
        case 'incomplete':
          if (self.oldStatus === 'overdue') {
            self.params.completed.end = null;
          }
          break;
        case 'notAccepted':
          self.params.completed.start = null;
          self.params.completed.end = null;
          break;
        case 'overdue':
          self.params.completed.start = null;
          self.params.completed.end = self.overdueDate;
          break;
        case 'suspended':
          self.params.completed.start = null;
          self.params.completed.end = null;
          self.params.iAmPerformer = false;
          self.removePerformer();
          break;
      }
      if (self.oldStatus === 'overdue') {
        self.params.completed.end = null;
      }
      self.oldStatus = self.params.status;
    };

    self.parseQuery = function() {
      var q = {$and:[]};

      if (self.params.subject) {
        q.$and.push({subject: {$regex: self.params.subject, $options: 'i'}});
      }

      if (self.params.author && !self.params.iAmAuthor) {
        var aut = {$or:[]};
        aut.$or.push({
          Author: {
            $in: [
              self.params.author.id,
              self.params.author.FullName,
              self.params.author.FullNameRaw]}});
        aut.$or.push({
          authorLogin: self.params.author.id
        });
        q.$and.push(aut);
      }

      if (self.params.performer && !self.params.iAmPerformer) {
        var perf = {$or:[]};
          perf.$or.push({
            taskPerformerLat: {
              $in: [
                self.params.performer.id,
                self.params.performer.FullName,
                self.params.performer.FullNameRaw]}});
        q.$and.push(perf);
      }

      if (self.params.iAmAuthor || self.params.iAmPerformer) {
        var mine = {$or:[]};
        if (self.params.iAmAuthor) {
          mine.$or.push({
            Author: {$in: [$rootScope.user.username,
                          $rootScope.user.portalData.FullName,
                          $rootScope.user.portalData.FullNameRaw]}});
          mine.$or.push({
            authorLogin: $rootScope.user.username
          });
        }
        if (self.params.iAmPerformer) {
          mine.$or.push({
            taskPerformerLat: {$in: [$rootScope.user.username,
                                    $rootScope.user.portalData.FullName,
                                    $rootScope.user.portalData.FullNameRaw]}});
        }
        q.$and.push(mine);
      }

      if (self.params.created.start || self.params.created.end) {
        var cre = {$and:[]};
        if (self.params.created.start) {
          var start = new Date(self.params.created.start);
          start = TretoDateTime.iso8601.fromDate(start);
          cre.$and.push({created: {$gte: start}});
        }
        if (self.params.created.end) {
          var end = new Date(self.params.created.end);
          end = TretoDateTime.iso8601.fromDate(end) + 'T235959';
          cre.$and.push({created: {$lte: end}});
        }
        q.$and.push(cre);
      }

      if (self.params.status === 'open' || self.params.status === 'close') {
        q.$and.push({status: self.params.status});
      } else if (self.params.status === 'notAccepted') {
        q.$and.push({status: 'open'});
        q.$and.push({$or:[{taskDateCompleted: ''}, {taskDateCompleted: {$exists: false}}]});
        q.$and.push({$or:[{taskDateRealEnd: ''}, {taskDateRealEnd: {$exists: false}}]});
      } else if (self.params.status === 'overdue') {
        q.$and.push({status: 'open'});
        q.$and.push({$or:[{taskDateCompleted: ''}, {taskDateCompleted: {$exists: false}}]});
        q.$and.push({$and:[{taskDateRealEnd: {$lt: TretoDateTime.iso8601.fromDateTime(self.overdueDate)}}, {taskDateRealEnd: {$exists: true}}, {taskDateRealEnd: {$ne: ''}}]});
        q.$and.push({TaskStateCurrent: {'$in': [5, 7]}})
      } else if (self.params.status === 'incomplete') {
        q.$and.push({status: 'open'});
        q.$and.push({$or:[{taskDateCompleted: ''}, {taskDateCompleted: {$exists: false}}]});
      } else if (self.params.status === 'completed') {
        q.$and.push({status: 'close'});
        q.$and.push({TaskStateCurrent: 25});
      } else if (self.params.status === 'suspended') {
        q.$and.push({status: 'open'});
        q.$and.push({TaskStateCurrent: 30});
      }

      if (self.params.status !== 'overdue' && self.params.status !== 'notAccepted' && self.params.status !== 'incomplete' && (self.params.completed.start || self.params.completed.end)) {
        var com = {$and:[]};
        if (self.params.completed.start) {
          var start = new Date(self.params.completed.start);
          start = TretoDateTime.iso8601.fromDate(start);
          if (self.params.status === 'completed' || self.params.status === 'close')
            com.$and.push({taskDateCompleted: {$gte: start}});
          else
            com.$and.push({taskDateRealEnd: {$gte: start}});
        }
        if (self.params.completed.end) {
          var end = new Date(self.params.completed.end);
          end = TretoDateTime.iso8601.fromDate(end) + 'T235959';
          if (self.params.status === 'completed' || self.params.status === 'close')
            com.$and.push({taskDateCompleted: {$lte: end}});
          else
            com.$and.push({taskDateRealEnd: {$lte: end}});
        }
        q.$and.push(com);
      }

      if (self.params.priority) {
        q.$and.push({Priority: self.params.priority});
      }

      return q;
    };
  }
  return obj;
}

function tags($http, BatchHttp, $rootScope, TretoDateTime){
  var obj = {
    loadAllTags: function(callback) {
      BatchHttp({method: 'GET', url: '/api/tags/tagsList' })
      .then(function(response) {
        if (response.data.success) {
          callback && callback(response.data);
        } else {
          console.log(response.data.message);
          callback && callback({});
        }
      }, httpErrorHandler);
    },

    loadMyTags: function(callback, limit) {
      if (!limit) limit = 0;
      BatchHttp({method: 'GET', url: '/api/tags/myTagsList', params: { 'limit': limit } })
      .then(function(response) {
        if (response.data.success) {
           callback && callback(response.data);
        } else {
          console.log(response.data.message);
          callback && callback({});
        }
      }, httpErrorHandler);
    },

    loadPopularTags: function(callback) {
      BatchHttp({method: 'GET', url: '/api/tags/popularTagsList' })
      .then(function(response) {
        if (response.data.success) {
          callback && callback(response.data);
        } else {
          console.log(response.data.message);
          callback && callback({});
        }
      }, httpErrorHandler);
    },

    findByTag: function(params, callbackSuccess, callbackFail) {
    $http({method: 'GET', url: 'api/tags/findByTag', params: params })
      .then(function(response) {
        if(response.data.success) {
          callbackSuccess && callbackSuccess(response.data)
        } else {
          console.log(response.data.message);
          callbackFail && callbackFail(response.data);
        }
      }, httpErrorHandler);
    },

    foundTags: [],
    foundLetters: [],

  }

  return obj;
}

function history (BatchHttp) {
  var obj = {
    add: function ( subject ) {
      BatchHttp( { method: 'POST', url: 'api/history/add', data: { subject: subject } } );
    },
    add_full: function ( subject, type, docid, updateDiscus ) {
      BatchHttp( { method: 'POST', url: 'api/history/add_full/'+type+'/'+docid, data: { subject: subject, updateDiscus: updateDiscus } } );
    },
    list: function ( offset, limit, res ){
      BatchHttp( { method: 'GET', url: 'api/history/list', params: { limit:limit, offset: offset } } ).
      then(function( response ) {
        res( response.data );
      })
    }
  }

  return obj;
}

function discounts (BatchHttp) {
  return function( ContactId, res ){
    BatchHttp({method: 'GET', url: 'api/contact/list', params: {limit: 0, query: {form: 'formDiscount', ContactId: ContactId}}}).
        then(function(response) {
          res(response.data);
        });
  }
}

function discountFields (BatchHttp) {
  return function () {
    var self = this;

    self.ConditionDiscount1 = [
      'меняем размер',
      'меняем цвет',
      'покупаем',
      'предоплачиваем'
    ];
    self.ConditionDiscount2 = [
      'больше',
      'меньше',
      'минимум',
      'полную',
      'равно',
      'сумма'
    ];
    self.ConditionDiscount4 =[
      '%',
      'кв.м',
      'паллеты',
      'упаковки',
      'шт',
      'евро'
    ];
    self.ConditionDiscount5 = [
      'наценка',
      'скидка',
      'цена'
    ];
    self.ObjectDiscount = [
      'для декоров',
      'для фона',
      'общая',
      'специальная'
    ];
    self.UseDiscount = [
      'всех серий',
      'выбранных артикулов/размеров/серий'
    ];
    self.BasicDiscount = [
      'не зависит от объема'
    ];
    self.IsSupposed = [
      'подтвержденная',
      'предпологаемая'
    ];
    self.symbols = [
      '%',
      '%',
      '€'
    ];

    self.getCollections = function(id, callback) {
      //callback(self.get('coll'+id));
      BatchHttp({method: 'GET', url: 'api/contact/collections', params: {id: id}}).then(function(response) {
        //self.set('coll'+id, response.data);
        callback(response.data);
      });
    };
  }
}

function dictionaryPrototype(BatchHttp, Popup) {
  var self = this;
  self.whenReady = false;
  self.ready = false;

  self.reloadPrototype = function(fullreset){
    if(typeof fullreset !== 'undefined' && fullreset){
      self.whenReady = false;
      self.ready = false;
    }
    BatchHttp({method: 'GET', url: 'api/dictionaries/get/prototype'})
        .then(function(response) {
          if(response.data.success) {
            if(! response.data.dictionary || ! response.data.dictionary[0]) {
              throw new Error('Cannot find "prototype" dictionary');
            }
            self.availableTypes = {};
            var keys = response.data.dictionary[0].key.split(',');
            var values = response.data.dictionary[0].value.split(',');
            keys.forEach(function(k, i) {
              self.availableTypes[k.trim()] = values[i] ? values[i].trim() : k.trim();
            });
            self.ready = true;
            self.whenReady && self.whenReady();
          } else {
            new Popup('dictionaryPrototype', response.data.message, 'error');
          }
        }, httpErrorHandler);
  };

  if(! self.availableTypes) {
    self.reloadPrototype();
  };

  self.getName = function(type) {
    if(self.availableTypes && self.availableTypes[type]) {
      return self.availableTypes[type];
    }
    return type;
  };
  return self;
}

function dictionary(BatchHttp, Popup, DictionaryPrototype, $rootScope, $uibModal, $http, $state) {
  $rootScope.dictionaryCache = {};

  var Dict = function(type, noProto, noCache, generateTreeOfRecords, callback) {
    var self = this;

    if(!type) { throw new Error('Dictionary: wrong type'); }

    self.type = type;
    self.name = noProto ? type : DictionaryPrototype.getName(type);
    self.records = [];
    self.recordsTree = {};
    self.recordsIndices = {};
    self.nested = (type == 'StatusList' || type == 'DiscusSection');
    self.saved = true;
    self.noCache = noCache;
    self.whenReady = null;

    self.addDictionaryModal = function(){
      $rootScope.expiredPopup.modal = $uibModal.open({
        templateUrl: '/bundles/tretoportal/partials/admin/addDictionariesModal.html',
        size: 'md',
        controller: function($scope, $state){
          $scope.key = '';
          $scope.value = '';
          $scope.error = '';

          $scope.ok = function(){
            if(!$scope.key || !$scope.value){
              $scope.error = 'Поля "value" и "error" обязательные.';
            }

            if(!$scope.error){
              BatchHttp({method: 'GET', url: 'api/dictionaries/add/prototype', data: {key:$scope.key, value:$scope.value}})
                  .then(function(response) {
                    if(response.data.error){
                      if(response.data.error == 'You entered value or key already exists.'){
                        $scope.error = 'Введенные вами поля уже существуют.';
                      }
                      else {
                        $scope.error = 'Не известная ошибка.';
                      }
                    }
                    else {
                      DictionaryPrototype.reloadPrototype(true);
                      $state.go('body.adminDictionary', {type: $scope.key}, {reload: true});
                      $rootScope.expiredPopup.modal.close();
                    }
                  }, httpErrorHandler);
            }
          };

          $scope.cancel = function(){
            $rootScope.expiredPopup.modal.close();
          };
        }});
    };

    self.saveRecords = function() {
      self.saved = false;
      BatchHttp({method: 'POST', url: 'api/dictionaries/set/'+self.type, data: {'records':self.records} })
      .then(function(response) {
        self.saved = true;
        if(response.data.success && typeof response.data.changed != "undefined") {
          self.records = response.data.changed;
          new Popup('Dictionary', 'dictionary.saved', 'notify');
        } else {
          new Popup('Dictionary', response.data.message, 'error');
        }
      }, httpErrorHandler);
    };

    self.addRecord = function() {
      self.records.push({
        "type": self.type,
        "subtype": (self.type == 'StatusList') ? {person:true, organization:true, show:'1'} : null,
        "key": "",
        "value": "",
        "parentKey": ""
      });
    };
    /**
      * if cannot find record, returns key
      * @param key string
    */
    self.getRecordValue = function(key) {
      if (!key) {
        return '';
      }
      var result = key;
      for (i in self.records) {
        if(self.records[i].key == key) {
          result = self.records[i].value;
          break;
        }
      }
      return result;
    };

    self.getRecordValues = function(keys) {
      var result = [];
      if (!keys || !keys instanceof Array) {
        if(typeof keys == "string") {
          return self.getRecordValue(keys);
        }
      }
      if(keys instanceof Array){
        keys.forEach(function(e,i) {
          result.push(self.getRecordValue(e));
        });
      }
      return result;
    };

    self.getAllRecordValues = function() {
      var values = [];
      self.records.forEach(function(r) {
        values.push(r.value);
      });
      return values;
    };

    self.loadRecordsFromCache = function() {
      if($rootScope.dictionaryCache[self.type]) {
        self.records = $rootScope.dictionaryCache[self.type];
        if (generateTreeOfRecords) {
          if($rootScope.dictionaryCache[self.type+'_recordsTree']) {
            self.recordsTree = $rootScope.dictionaryCache[self.type+'_recordsTree'];
          } else {
            self.generateRecordsTree();
          }
        }
        return true;
      }
      return false;
    };

    self.generateRecordsTree = function() {
      self.recordsTree = { children: {} };
      self.recordsIndices = {};
      self.records.forEach(function(record) {
        self.recordsIndices[record.key] = record;
      });
      self.records.forEach(function(record) {
        if (record.parentKey) {
          self._generateBranch(self.recordsIndices[record.parentKey],record,0);
        } else if(!self.recordsTree.children[record.key]) {
          self.recordsTree.children[record.key] = record;
        }
      });
      self.recordsIndices = {};
      return self.recordsTree;
    };

    self._generateBranch = function(parent,record,level) {
      if (parent) {
        if (!parent.children) {
          parent.children = {};
        }
        if (!parent.children[record.key]) { // if doesn't exist
          parent.children[record.key] = record;
          if (parent.parentKey) {
            ++level;
            if (level > 16) {
              throw new Error('dictionary: _generateBranch: recursion limit exceeded');
            }
            self._generateBranch(self.recordsIndices[parent.parentKey], parent, level);
          }
        }
      }
    };

    // Constructor
    if(self.saved && (self.noCache || !self.loadRecordsFromCache())) {
      self.saved = false;
      BatchHttp({method: 'GET', url: 'api/dictionaries/get/'+self.type})
        .then(function(response) {
          self.saved = true;
          if(response.data.success) {
            $rootScope.dictionaryCache[self.type] = response.data.dictionary;
            self.records = $rootScope.dictionaryCache[self.type];
            self.whenReady && self.whenReady();
            generateTreeOfRecords && self.generateRecordsTree();
            callback && callback(self);
          } else {
            new Popup('Dictionary', response.data.message);
          }
        }, httpErrorHandler);
    }
  };
  return Dict;
}

function ListDictionaries(BatchHttp) {
  var list = function() {
    var self = this;
    self.bySubtype = function(type, subtype, callback) {
      if (subtype) {
        BatchHttp({
          method: 'GET',
          url: 'api/dictionaries/get/' + type,
          params: {subtype: subtype}
        }).then(function (response) {
          callback(response.data)
        });
      }
    }
  };
  return list;
}


function colors() {
  var self = this;

  self.randomTable = [];
  self.pseudoRandomTable = [];

  self.getRandomInt = function(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
  }

  self.generateRandom = function(hmin, hmax, smin, smax, vmin, vmax) {
    return 'hsl('+self.getRandomInt(hmin,hmax)+','
                 +self.getRandomInt(smin,smax)+'%,'
                 +self.getRandomInt(vmin,vmax)+'%)';
  };



  self.generateColors = function(cup) {
    for(var i=0; i < cup; ++i) {
      self.randomTable[i] = self.generateRandom(0,360, 0,100, 0,100);
      self.pseudoRandomTable[i] = self.generateRandom(0,360, 60,100, 40,60);
    }
  };

  self.generateColors(64);

  return self;
}

function statuses(){
  return {
    open: "Открыта",
    close: "Закрыта",
    cancelled: "Отменена",
    edit: "редактирована",
    deleted: "Удалена",
    draft: "Черновик"
  }
}

function fullSearch($http, $rootScope) {
  var ser = {
    srch:
      function(query, callback) {
        var q = angular.copy(query);
        if (q.params){
          q.params = btoa(angular.toJson(q.params));
        }
        //$http({method:'GET', url:'api/serp/search', params: q})
        $http({method:'GET', url:'api/serp/elasearch', params: q})
        .then(function(response){
          if (response.data.success){
            callback(response.data.docs, response.data.parents);
          }
        }, httpErrorHandler);
      },
    addAuthor:
      function(author, q) {
        if (!q.params) q.params = {};
        if (author){
          q.params['Author'] = {'$in': [author.id||author.login||author.Login||author.username, author.FullName, author.FullNameRaw]};
        }else{
          delete q.params['Author'];
        }
      },
    myPartipiate:
      function(q) {
        if (!q.params) q.params = {};
        if (!q.params[1]){
          q.params[1] = {'taskPerformerLat': {'$in': [$rootScope.user.username, $rootScope.user.portalData.FullName, $rootScope.user.portalData.FullNameRaw]}};
        }else{
          delete q.params[1];
        }
      },
    searchIn:
      function(type, q) {
        if (!q.params) q.params = {};
        if(type == 'all'){
          delete q.params['form'];
        }else{
          q.params['form'] = {'$in': ['formTask', 'formProcess']};
        }
      }
  }
  return ser;
}

function favorites(BatchHttp, Popup, $rootScope) {
  return function() {
    this.myFavorites = function(callback, offset, limit) {
      offset = offset ? offset : 0;
      limit = limit ? limit : 100;
      BatchHttp({method: 'GET', url: 'api/portal/my-favorites', params: {'offset': offset, 'limit':limit}})
      .then(function(response) {
        if (response.data.success) {
          callback && callback( response.data.result );
        }
      }, httpErrorHandler);
    }
    this.addFavorites = function(unid, callback) {
      BatchHttp({method: 'GET', url: 'api/portal/add-favorites/'+unid})
      .then(function(response) {
        if (!response.data.success) {
          new Popup('Favorites', response.data.message);
        }else{
          $rootScope.user.portalData.favorites.push(unid);
        }
        callback && callback( response.data.success );
      }, httpErrorHandler);
    }
    this.delFavorites = function(unid, callback) {
      BatchHttp({method: 'POST', url: 'api/portal/del-favorites/'+unid})
      .then(function(response) {
        if (!response.data.success) {
          new Popup('Favorites', response.data.message);
        }else{
          var index = $rootScope.user.portalData.favorites.indexOf(unid);
          if (index >= 0){
            $rootScope.user.portalData.favorites.remove(index);
          }
        }
        callback && callback( response.data.success );
      }, httpErrorHandler);
    }
  }
}

function security($rootScope) {
  var obj = function(secureDocument) {
    var self = this;

    self.types = ['username','fullname','ldap','role'];
    self.doc = secureDocument;
    if (self.doc) { //too frequent, gotta investigate
      if(! self.doc.security) { self.doc.security = {}; }
      if(! self.doc.security.privileges) { self.doc.security.privileges = {}; }
      if(! self.doc.security.log) { self.doc.security.log = {}; }
      if(! self.doc.security.privileges.read) { self.doc.security.privileges.read = []; }
//       if(! self.doc.security.privileges.unread) { self.doc.security.privileges.unread = []; }
      if(! self.doc.security.privileges.write) { self.doc.security.privileges.write = []; }
//       if(! self.doc.security.privileges.unwrite) { self.doc.security.privileges.unwrite = []; }
      if(! self.doc.security.privileges.subscribed) { self.doc.security.privileges.subscribed = []; }
//       if(! self.doc.security.privileges.unsubscribed) { self.doc.security.privileges.unsubscribed = []; }
      if(self.doc.form == 'formVoting' && !self.doc.security.privileges.vote) {
        self.doc.security.privileges.vote = [];
      }

      /** returns { index: INT, privilege: OBJECT, type: STRING } when found
      if subject has no read privs it checks for write privileges. In other words, subject cannot write without reading */
      self.hasPrivilege = function(action, subject, dontTraverseToHigher) {
      if(! action || !subject) { throw new Error('Security: empty action or subject'); };
//       console.log('security.hasPrivilege: action='+action+', subject='+subject);
        var found = null;
        for (i in self.doc.security.privileges[action]) {
          for (j in self.types) {
            if(self.doc.security.privileges[action][i][self.types[j]] == subject) {
              found = { index: i, privilege: self.doc.security.privileges[action][i][self.types[j]], type: self.types[j] };
              break;
            }
          }
          if(found) break;
        }
        if(!found && action == 'read' && !dontTraverseToHigher) {
          return self.hasPrivilege('write', subject);
        }
//         console.dir(found);
        return found;
      };

      self.getShareSecurity = function () {
        return self.doc.shareSecurity;
      };

      self.addSharePrivilege = function (domain, action, type, subject) {
        var replaceDoteDomain = domain.replace(/\./g, "");

        self.initShareSecurity(replaceDoteDomain, domain);
        if(!self.hasSharePrivilege(replaceDoteDomain, action, type, subject)){
          var push = {};
          push[type] = subject;
          self.doc.shareSecurity[replaceDoteDomain].privileges[action].push(push);
        }
      };

      self.addToAllSharePrivilege = function (action, type, subject) {
        for(var domainKey in self.doc.shareSecurity){
          if(self.doc.shareSecurity[domainKey]['domain']){
            self.addSharePrivilege(self.doc.shareSecurity[domainKey]['domain'], action, type, subject);
          }
        }
      };

      self.removeToAllSharePrivilege = function (action, type, subject) {
        for(var domainKey in self.doc.shareSecurity){
          if(self.doc.shareSecurity[domainKey]['domain']){
            self.removeSharePrivilege(self.doc.shareSecurity[domainKey]['domain'], action, type, subject);
          }
        }
      };

      self.removeSharePrivilege = function (domain, action, type, subject) {
        var replaceDoteDomain = domain.replace(/\./g, "");
        self.initShareSecurity(replaceDoteDomain, domain);
        return self.hasSharePrivilege(replaceDoteDomain, action, type, subject, true);
      };

      self.hasSharePrivilege = function (replaceDoteDomain, action, type, subject, remove) {
        if(typeof self.doc.shareSecurity[replaceDoteDomain].privileges[action] == 'undefined'){
          self.doc.shareSecurity[replaceDoteDomain].privileges[action] = [];
        }

        for(var i in self.doc.shareSecurity[replaceDoteDomain].privileges[action]){
          var priv = self.doc.shareSecurity[replaceDoteDomain].privileges[action][i];
          if(typeof priv[type] != 'undefined' && priv[type] == subject){
            if(typeof remove != 'undefined'){
              self.doc.shareSecurity[replaceDoteDomain].privileges[action].splice(i, 1);
            }

            return true;
          }
        }

        return false;
      };

      self.initShareSecurity = function (replaceDoteDomain, domain) {
        if(!self.doc.shareSecurity){ self.doc.shareSecurity = {}; }
        if(!self.doc.shareSecurity[replaceDoteDomain]){ self.doc.shareSecurity[replaceDoteDomain] = {domain:domain}; }
        if(!self.doc.shareSecurity[replaceDoteDomain].privileges){ self.doc.shareSecurity[replaceDoteDomain].privileges = {}; }
        if(!self.doc.shareSecurity[replaceDoteDomain].privileges.read){ self.doc.shareSecurity[replaceDoteDomain].privileges.read = []; }
        if(!self.doc.shareSecurity[replaceDoteDomain].privileges.write){ self.doc.shareSecurity[replaceDoteDomain].privileges.write = []; }
        if(!self.doc.shareSecurity[replaceDoteDomain].privileges.subscribed){ self.doc.shareSecurity[replaceDoteDomain].privileges.subscribed = []; }
      };

      self.getUserNames = function(action) {
        var res = [];
        for (var i in self.doc.security.privileges[action]) {
          res.push(self.doc.security.privileges[action][i]['username']);
        }
        return res;
      };

      self.addPrivilege = function(action, type, subject) {
//         console.log('security.addPrivilege: action='+action+', type='+type+', subject='+subject);
        if(! type) { throw new Error('Security: empty type'); };
        if(self.hasPrivilege(action,subject,true)) { return false; }
        var priv = {};
        priv[type] = subject;

        if(!Array.isArray(self.doc.security.privileges[action]) && typeof self.doc.security.privileges[action] == 'object'){
          self.doc.security.privileges[action] = Object.values(self.doc.security.privileges[action]);
        }

        self.doc.security.privileges[action].push(priv);

        return true;
      };

      self.getAuthor = function() {
        var found = null;
        for (i in self.doc.security.privileges.write) {
          for (j in self.types) {
            if(self.doc.security.privileges.write[i][self.types[j]] != 'role') {
              found = self.doc.security.privileges.write[i][self.types[j]];
              break;
            }
          }
          if(found) break;
        }
        return found;
      };

      self.removePrivilege = function (action, type, subject) {
        console.log('security.removePrivilege: action='+action+', type='+type+', subject='+subject);
        if (!action || !subject) {
          return true;
        }

        if (!self.hasPrivilege(action, subject)) {
          return false;
        }

        for(var i in self.doc.security.privileges[action]){
          if(typeof self.doc.security.privileges[action][i][type] != 'undefined' && self.doc.security.privileges[action][i][type] == subject){
            if(!Array.isArray(self.doc.security.privileges[action]) && typeof self.doc.security.privileges[action] == 'object'){
              self.doc.security.privileges[action] = Object.values(self.doc.security.privileges[action]);
            }
            self.doc.security.privileges[action].splice(i, 1);
          }
        }

        return true;
      };
    }
  };
  return obj;
}

function AutoComplete($http, Popup, $filter, $rootScope, BatchHttp) {
  var obj = function() {
    var self = this;
    self.users = function(val) {
      if (val.length < 3) { return []; }
      return $http.get('api/portal/users', { params: { query:{ name:val } }}).then(function(response){ return response.data.result; });
    };
    self.companies = function(val) {
      if (val.length < 3) return [];
      return $http.get('api/contact/companies', { params: { query:{ name:val } }}).then(function(response){ return response.data.result; });
    };
    self.persons = function(val, rank) {
      rank = rank || '';
      if (val.length < 4) return [];
      return $http.get('api/contact/persons', { params: { query:{ name:val , rank: rank} }}).then(function(response){
        return response.data.result;
      });
    };
    self.profile = function (name, section, fieldName) {
      section = section?section:0;
      name = name?name.replace(/\/|\\/g,"\\"):0;
      return BatchHttp({
        method: 'GET',
        url: 'api/user/listbysection/'+section+'/'+name
      }).then(function(response) {
        var result = [];
        for(var i in response.data.users){
          result.push({name:response.data.users[i][fieldName]})
        }
        return result;
      }, httpErrorHandler);
    };
    self.contacts = function (s, fieldName) {
      if (s.text.length < 2) return;
      s.fields = ["ContactName", "FullName"];
      return BatchHttp({
        method: 'GET',
        url: 'api/serp/contact_auto',
        params: s
      }).then(function(response) {
        var result = response.data.variants.map(function (val) {
          return {name: val};
        });

        return result;
      }, httpErrorHandler);
    };
    self.main = function(query) {
      s = {query: query, fields: ["subject", "ContactName", "SiteName", "FullName"], type: ''}
      return BatchHttp({
        method: 'GET',
        url: 'api/serp/autocomplete',
        params: s
      }).then(function(response) {
        return response.data.variants.map(function(v) {
          return {name: $filter('removeBrokenTags')(v.val), unid: v.unid};
        });
      })
    }
  };
  return obj;
}
function MultiselectHelper ($rootScope) {
  var ret = function() {
    var self = this;

    self.selectCheckbox = function(document, field, key){
      if(!angular.isArray(document[field]) || !document[field]){
        document[field] = [];
      }

      if(field == 'WorkGroup'){
        if(document.WorkGroupEng.indexOf(key.key) == -1){
          document.WorkGroupEng.push(key.key);
        }
        else {
          document.WorkGroupEng.splice(document.WorkGroupEng.indexOf(key.key), 1);
        }

        key = key.value;
      }

      var indexOf = document[field].indexOf(key);

      if(indexOf == -1){
        document[field].push(key);
      }
      else {
        document[field].splice(indexOf, 1);
      }
    };
  };
  return ret;
}

function tretoDateTime() {
  var self = this;

  self.iso8601 = {};

  self.iso8601.toDate = function(iso) { return self.iso8601.toDateTime(iso,true); }
  self.iso8601.toDateTime = function(iso, excludeTime) {
    var dt = new Date();
    if(iso) {
      dt.setTime(0);
      if(iso.length >= 8) {
        dt = new Date(iso.substr(0,4), parseInt(iso.substr(4,2))-1, iso.substr(6,2));
      }
      if(!excludeTime && iso.length >= 15 && iso.length <= 21) {
        dt.setHours(iso.substr(9,2));
        dt.setMinutes(iso.substr(11,2));
        dt.setSeconds(iso.substr(13,2));
      }
    }
    return dt;
  };

  self.iso8601.fromDate = function(dt) { return self.iso8601.fromDateTime(dt,true); }
  self.iso8601.fromDateTime = function(dt, excludeTime) {
    if(! dt) { dt = new Date(); }
    var iso = dt.getFullYear().toString() + ('0'+(dt.getMonth()+1)).slice(-2) + ('0'+dt.getDate()).slice(-2);
    if(!excludeTime && dt.getHours()+dt.getMinutes()+dt.getSeconds() != 0) {
      iso += 'T'+('0'+dt.getHours()).slice(-2)+('0'+dt.getMinutes()).slice(-2)+('0'+dt.getSeconds()).slice(-2);
    }
    return iso;
  };

  self.iso8601.display = function(iso, short) {
    var d = iso;
    var year = short?2:0;
    if(!d) {
      return '';
    } else if(d.length == 8 && /^[\d]+$/.test(d)) {
      return d.substr(6,2)+'.'+d.substr(4,2)+'.'+d.substr(year,4-year);
    } else if((d.length == 15 || d.length == 21) && /^[\dT\,\+\-]+$/.test(d)) {
      var his = d.substr(9,2)+':'+d.substr(11,2);
      return d.substr(6,2)+'.'+d.substr(4,2)+'.'+d.substr(year,4-year)+' '
          + (his == '00:00' ? '' : his);
    }
    return d;
  };

  self.iso8601.displayDateMonth = function(iso) {
    var d = iso;
    if(!d) {
      return '';
    } else if((d.length == 8 && /^[\d]+$/.test(d)) || (d.length == 15 || d.length == 21) && /^[\dT\,\+\-]+$/.test(d)) {
      var m = +d.substr(4,2);
      var month = '';
      switch (m) {
        case 1: month = 'января'; break;
        case 2: month = 'февряля'; break;
        case 3: month = 'марта'; break;
        case 4: month = 'апреля'; break;
        case 5: month = 'мая'; break;
        case 6: month = 'июня'; break;
        case 7: month = 'июля'; break;
        case 8: month = 'августа'; break;
        case 9: month = 'сентября'; break;
        case 10: month = 'октября'; break;
        case 11: month = 'ноября'; break;
        case 12: month = 'декабря'; break;
      }
      return ''+(+d.substr(6,2))+' '+month;
    }
    return d;
  }

  self.iso8601.add = function(iso1, iso2) {
    dt1 = self.iso8601.toDateTime(iso1);
    dt2 = self.iso8601.toDateTime(iso2);
    var result = new Date();
    result.setTime(dt1.getTime() + dt2.getTime());
    return result;
  };

  self.iso8601.sub = function(iso1, iso2) {
    dt1 = self.iso8601.toDateTime(iso1);
    dt2 = self.iso8601.toDateTime(iso2);
    var result = new Date();
    result.setTime(dt1.getTime() - dt2.getTime());
    return result;
  };

  self.newDate = function(a,b,c,d,e,f) { return new Date(a,b,c,d,e,f); }

  return self;
}

function decoration($timeout) {
  var self = this;

  /** adds cssClass to jqueryElement for timeoutMs and removes it after timeout */
  self.blink = function(jqueryElement, cssClass, timeoutMs) {
    var e = angular.element(jqueryElement);
    e.addClass(cssClass || 'blink');
    $timeout(function() {
      e.removeClass(cssClass || 'blink');
    }, timeoutMs || 800);
  };

  return self;
}

function batchHttp($timeout, $http, $rootScope, $q, Popup) { // factory definition
  var disabled = false;

  return function(req) { // factory object
    if(disabled || (req.method && req.method != 'GET')) { return $http(req); } // process only GET requests as batch

    return $q(function(resolve, reject) { // call promise
      if(! $rootScope.batchHttp) {
        $rootScope.batchHttp = {
          pending: [],
          busy: false
        };
      }
      var self = $rootScope.batchHttp;

      self.pending.push({
        'req': req,
        'resolve': resolve,
        'reject': reject
      });
      if(self.busy) { return; } // timer already started

      self.busy = true;
      $timeout(function() {
        self.busy = false;
        if(self.pending.length > 0) {
          var jobs = self.pending;
          self.pending = [];
          var requests = [];
          jobs.forEach(function(j,i) {
            requests.push(j.req);
          });

          $http({ method: 'POST', url: 'api/batch-request', data: {'requests' :requests} })
            .then(function(response) {
              if(response.data.success) {
                response.data.responses.forEach(function(r,i) {
                  jobs[i].resolve && jobs[i].resolve({ data: r });
                });
              } else {
                new Popup('BatchHttp', response.data.message, 'error');
              }
            }, httpErrorHandler);
        }
      }, 260);
    });
  }
}

function adaptation($rootScope, BatchHttp, Popup, Dictionary, translit, localize) {
  var obj = function(forDoc) {
    var self = this;

    self.doc = forDoc;
    self.documents = [];
    self.noDocuments = false;
    self.positionsDict = new Dictionary('Positions', true);
    self.departmentDict = new Dictionary('Department', true);
    self.sectionDict = new Dictionary('Section', true);
    self.companyNameDict = new Dictionary('companyName', true);
    self.countries = new Dictionary('Country', true);
    self.currencyDict = new Dictionary('Currency', true);

    self.loadList = function() {
      BatchHttp({ method: 'GET', url: 'api/adaptation/list'}).then(function(resp) {
        if(resp.data.success) {
          if (resp.data.documents.length == 0)
            self.noDocuments = true;
          else
            self.documents = resp.data.documents;
        } else {
          new Popup('Adaptation', resp.data.message, 'Error');
        }
      }, httpErrorHandler);
    };

    self.fillNames = function() {
      self.doc.FullNameInRus = [self.doc.LastName, self.doc.name, self.doc.MiddleName].join(' ').trim();
      self.doc.subject = self.doc.FullNameInRus;
      self.doc.FullNameRaw = translit(self.doc.FullNameInRus);
      self.doc.FullName = 'CN='+self.doc.FullNameRaw+'/O=skvirel';
    };

  };
  return obj;
}

function GuidFactory() {
  var s4 = function() {
    return Math.floor((1 + Math.random()) * 0x10000).toString(16).substring(1);
  };
  return function() {
    return (s4() + s4() + '-' + s4() + '-' + s4() + '-' +
           s4() + '-' + s4() + s4() + s4()).toUpperCase();
  };
}

function mail($http, $filter) {
  var m = {
    newMailCount: function(callback) {
      $http({method: 'GET', url: 'api/user/mail/count'}).
      then(function(response) {
        if (response.data.success) {
          callback( response.data.count );
        }
      });
    },

    mailHeaders: function(query, callback) {
      $http.get('api/user/mail/search', { params: query}).
      then(function(response) {
        if (response.data.success) {
          callback( $filter('orderBy')(response.data.hdrs,'date') );
        }
      });
    },

    mailByIds: function(ids, callback) { //ids: string "1,2,3"
      if (ids){
        $http({method: 'GET', url: 'api/user/mail/getByIds/'+ids}).
        then(function(response) {
          if (response.data.success) {
            callback( $filter('orderBy')(response.data.mails, '-date') );
          }
        });
      }else{
        callback([]);
      }
    },

    sendQuestionary: function(contact, questionary, callback) {
      $http.get('api/mail/send-questionary', { params: {'contact': contact, 'questionary' : questionary} })
      .then(function(response){
        if (response.data.success){
          callback(response.data.hitlist);
        }else{
          new Popup('MailQuestionary', response.data.message);
        }
      });
    }
  }

  return m
}

function question(BatchHttp, $http) {
  var q = {
    getCriterionsForRouter: function() {
      return BatchHttp({ method: 'GET', url: 'api/question/criterions'}).then(function(resp) {
        if(resp.data.success) {
          return {criterions:resp.data.criterions, questionaries:resp.data.questionaries};
        }
      });
    },
    getQuestionaries: function(callback) {
      BatchHttp({method: 'GET', url: '/api/question/questionaries' })
      .then(function(response) {
        if (response.data.success) {
          callback(response.data.documents);
        }else{
          new Popup('Question', response.data.message);
        }
      }, httpErrorHandler);
    },
    setCriterion: function(crit, callback) {
      BatchHttp({method: 'POST', url: 'api/question/set', data: {query: crit} })
      .then(function(response) {
        if (response.data.success) {
          callback( response.data.document );
        }else{
          new Popup('Question', response.data.message);
        }
      }, httpErrorHandler);
    },
    delCriterion: function(unid, callback) {
      BatchHttp({method: 'GET', url: '/api/question/del/' + unid })
      .then(function(response) {
        if (response.data.success) {
          callback();
        }else{
          new Popup('Question', response.data.message);
        }
      }, httpErrorHandler);
    }
  }
  return q;
}

function discusSharedSvc($http, $rootScope, Favorites) {
  var svc = {};
  var favors = new Favorites();
  svc.add_favorites = function(unid) {
    favors.addFavorites(unid, function(res) {});
  }
  svc.del_favorites = function(unid) {
    favors.delFavorites(unid, function(res) {});
  }
  svc.isFavorite = function(unid) {
    var favorites = $rootScope.user.portalData.favorites;
    for (var key in favorites) {
      var fav_unid = favorites[key].split('|')[0];
      if (fav_unid == unid) {
        return true;
      }
    }
    return false;
  }
  svc.getBlockState = function (blockName, change) {
    var collapseState = JSON.parse(localStorage.getItem('collapseState'));
    collapseState = !collapseState?{}:collapseState;

    if(collapseState[blockName] == undefined){
      collapseState[blockName] = true;
    }

    if(change){
      collapseState[blockName] = !collapseState[blockName];
      localStorage.setItem('collapseState', JSON.stringify(collapseState));
    }

    return collapseState[blockName];
  };

  return svc;
}

Stat.$inject = ['$log', '$http', '$rootScope', 'TretoDateTime', 'Popup', '$timeout'];
function Stat($log, $http, $rootScope, TretoDateTime, Popup, $timeout) {
  var self = {};
  self.queryCounter = 0;

  self.getLikes = function(callback, daysCount, limit) {
    self.queryCounter++;
    daysCount = daysCount || 7;
    limit = limit || 20;

    $http({ method: 'GET', url: 'api/stat/getLikes', params: {daysCount: daysCount, limit: limit} })
          .then(function(response) {
            if (response.data.success) {
              if (angular.isArray(response.data.documents) && response.data.documents.length) {
                var yesterday = new Date();
                yesterday.setDate(yesterday.getDate() - 1);
                yesterday = TretoDateTime.iso8601.fromDateTime(yesterday);
                for (var i = 0; i < response.data.documents.length; i++) {
                  var change = -response.data.documents[i].expiredCount;
                  for (var j = 0; j < response.data.documents[i].LikeDateList.length; j++) {
                    if (response.data.documents[i].LikeDateList[j] > yesterday) change++;
                  }
                  response.data.documents[i].change = change;
                }
              }
              callback(response.data.documents);
            } else {
              $log.error(response.data);
            }
            self.queryCounter--;
          }, httpErrorHandler);
  };

  self.getDislikes = function(callback, daysCount, limit) {
    self.queryCounter++;
    daysCount = daysCount || 7;
    limit = limit || 20;

    $http({ method: 'GET', url: 'api/stat/getDislikes', params: {daysCount: daysCount, limit: limit} })
          .then(function(response) {
            if (response.data.success) {
              if (angular.isArray(response.data.documents) && response.data.documents.length) {
                var yesterday = new Date();
                yesterday.setDate(yesterday.getDate() - 1);
                yesterday = TretoDateTime.iso8601.fromDateTime(yesterday);
                for (var i = 0; i < response.data.documents.length; i++) {
                  var change = -response.data.documents[i].expiredCount;
                  for (var j = 0; j < response.data.documents[i].LikeNotDateList.length; j++) {
                    if (response.data.documents[i].LikeNotDateList[j] > yesterday) change++;
                  }
                  response.data.documents[i].change = change;
                }
              }
              callback(response.data.documents);
            } else {
              $log.error(response.data);
            }
            self.queryCounter--;
          }, httpErrorHandler);
  };

  self.getPopularThemes = function(callback, user, limit, daysCount) {
    self.queryCounter++;
    user = user || 'all';
    daysCount = daysCount || 7;
    limit = limit || 10;
    
    $http({ method: 'GET', url: 'api/stat/getPopularThemes', params: {daysCount: daysCount, limit: limit, user: user} })
          .then(function(response) {
            if (response.data.success) {
              callback(response.data.documents);
            } else {
              $log.error(response.data);
            }
            self.queryCounter--;
          }, httpErrorHandler);
  };

  self.getMainStat = function(callback) {
    self.queryCounter++;
    $http({ method: 'GET', url: 'api/stat/getMainStat', data: {} })
      .then(function(response) {
        if (response.data.success) {
          self.mainStat = response.data.documents;

          if (!self.mainStat) {
            new Popup('Не удалось получить данных статистики MainStat с сервера', 'Error');
            self.queryCounter--;
            return;
          }

          callback(self.mainStat);
        } else {
          $log.error(response.data);
          new Popup(response.data, 'Error');
        }
        self.queryCounter--;
      }, httpErrorHandler);
  };

  self.getStat = function(query, callback) {
    self.queryCounter++;
    $http({ method: 'GET', url: 'api/stat/get/stat', params: query || {} })
          .then(function(response) {
            if (response.data.success) {
              self.params.state.rocketChatMsgsParsed = false;
              callback(response.data.dailyStat);
            } else {
              $log.error(response.data);
            }
            self.queryCounter--;
          }, httpErrorHandler);
  };

  if (!self.params) {
    self.params = {
      getDateFormatted: function(date) {
        if (!date || !angular.isDate(date))
          date = new Date();
        var d = date.toISOString();
        return d.slice(0, d.indexOf('T')).replace(/-/g, '');
      },
      state: {
        period: {
          type: 'week'
        },
        users: [],
        rocketChatMsgsParsed: false,
        messagesStatGraph: {
          periodPerKnot: 1,
          dataSelected: 'messages',
          mode: 'curve'
        },
        clickStatGraph: {
          periodPerKnot: 1,
          mode: 'curve'
        }
      }
    };
  }

  self.getSinceDate = function() {
    return self.params.state.period.start === null ?
            '' :
            TretoDateTime.iso8601.fromDate(self.params.state.period.start);
  };

  self.getUntilDate = function() {
    return self.params.state.period.end === null ?
            '' :
            TretoDateTime.iso8601.fromDate(self.params.state.period.end);
  };

  self.fastSelectPeriod = function(period) {
    var now = new Date();
    var options = {
      'day'   : (new Date()).setDate(now.getDate() - 1),
      'week'  : (new Date()).setDate(now.getDate() - 7),
      'month' : (new Date()).setMonth(now.getMonth() - 1),
      'quarter': (new Date()).setMonth(now.getMonth() - 3),
      'year'  : (new Date()).setFullYear(now.getFullYear() - 1)
    };
    if (period === 'full') {
      self.params.state.period.start = null;
      self.params.state.period.end = null;
    } else if (period !== 'custom' || options[period]) {
      self.params.state.period.start = new Date(options[period]);
      self.params.state.period.end = now;
    }
    self.params.state.period.type = period;
  };
  self.fastSelectPeriod('week');

  self.getQueryByDate = function() {
    return {
      sinceDate: self.getSinceDate(),
      untilDate: self.getUntilDate()
    };
  };

  self.getQueryForMessagesByUser = function(user) {
    var q = {
      user: user || '',
      since: self.getSinceDate(),
      until: self.getUntilDate()
    };
    return btoa(angular.toJson(q));
  };

  self.getMessagesByUser = function(query, callback) {
    self.queryCounter++;
    $http({ method: 'GET', url: 'api/stat/getMessagesByUser/' + query, data: {} })
      .then(function(response) {
        if (response.data.success) {
          callback(response.data.documents);
        } else {
          $log.error(response.data);
        }
        self.queryCounter--;
      }, httpErrorHandler);
  };

  self.logClick = function(label) {
    if ( !label ) {
      new Popup('Error', 'Log-click directive should have a label identifying the button');
      return false;
    }
    label = $.trim(label);
    $http({ method: 'POST', url: 'api/stat/logClick', data: {label: label}})
      .then(function(response) {
        if (!response.data.success) {
          $log.error('Error occured while trying to log click event on the button ('+label+'): ', response.data);
          new Popup('Error',
                    'Error occured while trying to log click event on the button ('+label+'): ' + response.data.message);
        }
      }, httpErrorHandler);
  };

  self.getClickStat = function(query, callback) {
    self.queryCounter++;
    $http({ method: 'GET', url: 'api/stat/getClickStat', params: query || {} })
      .then(function(response) {
        self.queryCounter--;
        if (!response.data.success) {
          $log.error('Error occured while trying to load click logs: ', response.data);
          new Popup('Error',
                    'Error occured while trying to log click event on the button ('+label+'): ' +
                    response.data.message);
        } else {
          callback(response.data.documents);
        }
      }, httpErrorHandler);
  };

  
  self.monthlyClickLog = {
                          data: null,
                          loading: false,
                          timestapm: null, 
                          isUserClickWatcher: function() {
                            return $rootScope.user && ~$rootScope.user.portalData.role.indexOf('clickWatcher');
                          }
                         };

  self.getMonthlyClickLog = function(callback) {
    var mcl = self.monthlyClickLog;
    var now = new Date();

    var callbackThrottler = function() {
      if (mcl.loading) return $timeout(callbackThrottler, 2000);
      callback();
    };
    
    if ( !mcl.timestapm || ( now.getDate() !== mcl.timestapm.getDate() ) ) {
      if (!mcl.loading) {
        mcl.loading = true;
        return self.getClickStat(
          {
            sinceDate: TretoDateTime.iso8601.fromDate( new Date(now.setMonth(now.getMonth() - 1)) ),
            untilDate: TretoDateTime.iso8601.fromDate( new Date() )
          },
          function(data) {
            mcl.data = {};
            for (var date in data)
              for (var btn in data[date]) {
                if ( !mcl.data[btn] ) mcl.data[btn] = 0
                mcl.data[btn] += data[date][btn];
              }
            mcl.timestapm = new Date();
            mcl.loading = false;
            typeof callback === 'function' && callback();
          }
        );
      }
      typeof callback === 'function' && $timeout(callbackThrottler, 2000);
    }
  };

  return self;
};

function C1Logs($http) {
  var obj = {
    get: function(offset, limit, callback) {
      offset = offset || 0;
      limit = limit || 10;
      $http({method: 'POST', url: 'api/c1logs', data:{offset:offset, limit:limit}})
      .then(function(response){
        if (response.data.success){
          callback(response.data.docs);
        }else{
          new Popup('Logs', 'Не понятно как так вышло!');
        }
      }, httpErrorHandler);
    }
  }
  return obj;
}

function SMS(Popup, Socket) {
  var obj = {
    self: this,
    send: function(_from, _to, text) {
      if (!_from || !_to) {
        new Popup('Error', 'Не указан отправитель или адресат.');
      } else {
        text = text || _from + ' просит Вас выйти на связь на портале';
        Socket.get(function(socket) {
          socket.emit("sendSMS", {from: _from, to: _to, text: text});
        });
        new Popup('Notification', 'SMS успешно отправлено');
      }
    }
  }
  return obj;
}

function TECollection($http) {
  var self = {};

  self.params = {
    period: 'week'
  };

  self.getLastThree = function(collectionType, callback) {
    $http({method: 'GET', url: 'api/teCollection/getLastThree', params: {collectionType: collectionType}})
      .then(function(response) {
        if (response.data.success) {
          callback(response.data.documents);
        } else {
          //console.log('error. response: ', response);
        }
      });
  };

  self.getCollections = function(options, callback) {
    var defaults = {
      type: 'publication',
      since: null,
      until: null
    };
    var params = $.extend({}, defaults, options);
    $http({method: 'GET', url: 'api/teCollection/getCollections', params: params})
      .then(function(response) {
        if (response.data.success && response.data.documents) {
          callback(response.data.documents);
        }
      });
  };

  return self;
}

function Viewport() {
  var self = {};

  self.getViewportHeight = function() {
    return document.documentElement.clientHeight || document.body.clientHeight;
  };

  self.getViewportWidth = function() {
    return document.documentElement.clientWidth || document.body.clientWidth;
  };

  self.getScrollTop = function() {
    return document.documentElement.scrollTop || document.body.scrollTop;
  };

  self.getScrollLeft = function() {
    return document.documentElement.scrollLeft || document.body.scrollLeft;
  };

  self.getViewport = function() {
    var viewport = {};
    viewport.top = self.getScrollTop();
    viewport.left = self.getScrollLeft();
    viewport.bottom = viewport.top + self.getViewportHeight();
    viewport.right = viewport.left + self.getViewportWidth();
    return viewport;
  };

  self.get = self.getViewport;

  self.isInViewport = function(el, viewport, strict) {
    el = $(el);
    if (!el.length) return false;
    viewport = viewport || self.get();
    strict = strict === true;
    var top = el.offset().top;
    var h = el.outerHeight();
    return strict ?
      ((top >= viewport.top && top < viewport.bottom) && (top + h <= viewport.bottom && top + h > viewport.top)) :
      ((top >= viewport.top && top < viewport.bottom) || (top + h <= viewport.bottom && top + h > viewport.top));
  }

  return self;
};

function Notificator($http, Viewport, $rootScope) {
  self = {};

  self.getViewport = function() {
    var notificatorViewport = Viewport.get();
    notificatorViewport.top += document.getElementById('header').offsetHeight;
    return notificatorViewport;
  };

  self.useNotificatorByDefault = function(yes) {
    if (yes) {
      localStorage.setItem('useNotificatorByDefault', 'yes');
      $rootScope.useNotificatorByDefault = true;
    } else {
      localStorage.removeItem('useNotificatorByDefault');
      $rootScope.useNotificatorByDefault = false;
    }
  };

  /* docs should be an array of objects like
    * {
    *  unid: *doc_unid*
    *  parentUnid: *unid of doc's parent or if not present then doc_unid*
    *  time: *!optional - read time in the format like '07.11.2016 13:17:16'*
    * }
    */
  self.markAsRead = function(docs, callback, notificator) {
    $http({method: 'POST', url: 'api/notif/markAsRead', data: {docs: docs, fromNotificator: notificator === true ? 1 : 0}})
      .then(function(response) {
        if (typeof callback === 'function')
          callback(response);
      }, httpErrorHandler);
  };

  return self;
};

function Graph() {
  self = {};

  var getControllPoints = function(p0, p1, p2, mu) {
    var d01 = Math.sqrt( Math.pow(p1.x - p0.x, 2) + Math.pow(p1.y - p0.y, 2) );
    var d12 = Math.sqrt( Math.pow(p2.x - p1.x, 2) + Math.pow(p2.y - p1.y, 2) );

    var fa = mu * d01 / (d01 + d12);
    var fb = mu - fa;

    var cp1 = {
      x: Math.round(p1.x + fa * (p0.x - p2.x)),
      y: Math.round(p1.y + fa * (p0.y - p2.y))
    };

    var cp2 = {
      x: Math.round(p1.x - fb * (p0.x - p2.x)),
      y: Math.round(p1.y - fb * (p0.y - p2.y))
    }

    return [cp1, cp2];
  };

  var pointToString = function(point) {
    return '' + point.x + ',' + point.y;
  };

  self.getLine = function(points) {
    var line = 'M ' + pointToString(points[0]);
    for (var i = 1; i < points.length; i++) {
      line += ' L ' + pointToString(points[i]);
    }
    for (var i = points.length - 1; i >= 0; i--) {
      line += ' L ' + pointToString(points[i]);
    }
    line += ' Z';
    return line;
  };

  self.getCurve = function(points, zero) {
    if (points.length < 3) return self.getLine(points);
    var CURVING_STRENGTH = 0.33;
    var curve = 'M ' + pointToString(points[0]);
    var controllPoints = [];
    var cpCopy;

    for (var i = 1; i < points.length - 1; i++) {
      var cp = getControllPoints(points[i-1], points[i], points[i+1], CURVING_STRENGTH);
      controllPoints.push(cp[0], cp[1]);
    }

    cpCopy = controllPoints.slice();
    for (var i = 1; i < points.length; i++) {
      if (points[i].y === zero && points[i - 1].y === zero) {
        curve += ' L ';
        cpCopy.shift();
        if (i !== 1 && i !== points.length - 1) cpCopy.shift();
      } else if (i === 1 || i === points.length - 1) {
        curve += ' Q ' + pointToString(cpCopy.shift());
      } else {
        curve += ' C ' + pointToString(cpCopy.shift()) + ' ' + pointToString(cpCopy.shift());
      }
      curve += ' ' + pointToString(points[i]);
    }

    cpCopy = controllPoints.slice();
    for (var i = points.length - 2; i >= 0; i--) {
      if (points[i].y === zero && points[i + 1].y === zero) {
        curve += ' L ';
        cpCopy.pop();
        if (i !== 0 && i !== points.length - 2) cpCopy.pop();
      } else if (i === 0 || i === points.length - 2) {
        curve += ' Q ' + pointToString(cpCopy.pop());
      } else {
        curve += ' C ' + pointToString(cpCopy.pop()) + ' ' + pointToString(cpCopy.pop());
      }
      curve += ' ' + pointToString(points[i]);
    }
    curve +=' Z';

    return curve;
  };

  self.getTrendline = function(points) {
    var n = points.length;
    for (var a = 0, sumX = 0, sumY = 0, c = 0, i = 0; i < n; i++) {
      a += points[i].x * points[i].y * n;
      sumX += points[i].x;
      sumY += points[i].y;
      c += Math.pow(points[i].x, 2) * n;
    }
    var b = sumX * sumY;
    var d = Math.pow( sumX, 2 );
    var m = (a - b) / (c - d);
    var e = sumY;
    var f = sumX * m;
    var l = (e - f) / n;
    var y = function(x) { return Math.round(m * x + l); };
    var getTrendPoint = function(x) { return {x: x, y: y(x)}; };
    return 'M ' + pointToString(getTrendPoint(points[0].x))
            + ' L ' + pointToString(getTrendPoint(points[n - 1].x))
            + ' Z';
  };

  self.getCurveAndTrend = function(points, zero) {
    return self.getCurve(points, zero) + ' ' + self.getTrendline(points);
  };

  return self;
};

function Plural() {
  return {
    year: {
      '0': '',
      'one': '{} год',
      'few': '{} года',
      'many': '{} лет'
    },
    month: {
      '0': '',
      'one': '{} месяц',
      'few': '{} месяца',
      'many': '{} месяцев'
    },
    day: {
      '0': '',
      'one': '{} день',
      'few': '{} дня',
      'many': '{} дней'
    },
    message: {
      '0': '0 сообщений',
      'one': '{} сообщение',
      'few': '{} сообщения',
      'many': '{} сообщений'
    },
    user: {
      '0': '0 пользователей',
      'one': '{} пользователь',
      'few': '{} пользователя',
      'many': '{} пользователей'
    },
    human: {
      '0': '0 человек',
      'one': '{} человек',
      'few': '{} человека',
      'many': '{} человек'
    },
    male: {
      '0': '0 мальчиков',
      'one': '{} мальчик',
      'few': '{} мальчика',
      'many': '{} мальчиков'
    },
    female: {
      '0': '0 девочек',
      'one': '{} девочка',
      'few': '{} девочки',
      'many': '{} девочек'
    },
    notMaleNotFemale: {
      '0': '0 неопределившихся',
      'one': '{} неопределившийся',
      'few': '{} неопределившихся',
      'many': '{} неопределившихся'
    }
  };
};

function Settings($http) {
  var self = this;
    self.data = {
      sharePortal:[],
      selfSalt:[{value:'', edit:false, status:'active'}],
      indexBlocks:[],
    };

    self.get = function(callback) {
      console.log('Settings.get');
      self.loading = true;
      $http.get('api/portalSettings/get').then(function(response){
        self.loading = false;
        self.data = response.data.response;

        if(typeof self.data.sharePortal == "undefined"){
          self.data.sharePortal = [];
        }
        if(typeof self.data.selfSalt == "undefined" || self.data.selfSalt.length < 1){
          self.data.selfSalt = [{value:'', edit:false, status:'active'}];
        };

        callback && callback(response.data.response);
      });
    };

    self.check = function(settings, callback){
      console.log('Settings.check');
      $http.post('api/portalSettings/check', {data:{settings:settings}}).then(function(response){
        callback && callback(response.data);
      });
    }

    self.set = function (callback) {
      console.log('Settings.set');
      $http.post('api/portalSettings/set', {data:self.data}).then(function(response){
        self.data = response.data.response;
        callback && callback(response.data.response);
      });
    };

    self.addSharePortal = function () {
      console.log('Settings.addSharePortal');
      self.data.sharePortal.push({
        domain:'',
        companyName:'',
        salt:'',
        edit:true,
        status:'active',
        type:'sharePortal'
      })
    };

  return self;
};

function RequestAnimationFrame() {
  return  window.requestAnimationFrame ||
          window.webkitRequestAnimationFrame ||
          window.mozRequestAnimationFrame ||
          window.msRequestAnimationFrame ||
          window.oRequestAnimationFrame ||
          function (callback) {
              return setTimeout(function() { callback( Date().getTime() ); }, 16);
          };
};
		
function CancelAnimationFrame() {
  return  window.cancelAnimationFrame ||
          window.webkitCancelRequestAnimationFrame ||
          window.mozCancelAnimationFrame ||
          function (framebuffer) {
              clearTimeout(framebuffer);
              framebuffer = null;
          };
};

function WheelEventName() {
  if (document.addEventListener) {
      if ('onwheel' in document) return 'wheel';
      else if ('onmousewheel' in document) return 'mousewheel';
      else if ('DOMMouseScroll' in document) return 'DOMMouseScroll';
      else return 'MozMousePixelScroll';
  } else return 'onmousewheel';
};

Scroll.$inject = ['Viewport', 'RequestAnimationFrame', 'CancelAnimationFrame'];
function Scroll(Viewport, RequestAnimationFrame, CancelAnimationFrame) {
  var scrollAnimationHandle = null;

  var scrollTo = function(topOffset) {
    CancelAnimationFrame(scrollAnimationHandle);
    
    var body = document.body || document.documentElement;
    var header = document.getElementById('header');
    var topMenu = document.querySelector('#discus [floating-menu]');
    var scrollDown = topOffset > body.scrollTop;
    var targetScroll = topOffset - (topMenu && !scrollDown ? topMenu.offsetHeight : 0)
                     - (header ? header.offsetHeight : 0);

    if (targetScroll === body.scrollTop) return true;

    $(body).stop().animate(
      { 'scrollTop': targetScroll },
      Math.min( Math.abs(targetScroll - body.scrollTop), 1000)
    );

    return true;
  };

  var scrollIntoView = function(el) {
    CancelAnimationFrame(scrollAnimationHandle);

    var header = document.getElementById('header');
    var topMenu = document.querySelector('#discus [floating-menu]');
    var fastReply = document.getElementById('fast-reply');
    var startViewport = Viewport.get();
    startViewport.top += (topMenu ? topMenu.offsetHeight : (header ? header.offsetHeight : 0))
                       + (topMenu && header ? $(topMenu).offset().top - $(header).offset().top : 0);
    startViewport.bottom -= fastReply ? fastReply.offsetHeight : 0;
    var elTop = el.offset().top;
    var elHeight = el.outerHeight();

    if ( Viewport.isInViewport(el, startViewport, true) ||
         elTop === startViewport.top ) return true;

    /**
     * this block is the initial calculation where to scroll
     */
    var startScroll = Viewport.getScrollTop();
    var scrollDown = elTop > startViewport.top;
    if (scrollDown) {
      var pxTopMenuToSlideUp = (header && topMenu ?
                                  $(topMenu).offset().top + topMenu.offsetHeight -
                                  $(header).offset().top - header.offsetHeight :
                                  0
                               );
      var scrollLengthToShowBottom = elTop + elHeight - startViewport.bottom;
      var targetScroll = startViewport.top + scrollLengthToShowBottom +
                         Math.min(pxTopMenuToSlideUp, scrollLengthToShowBottom) > elTop ?
                          startScroll - startViewport.top + elTop + Math.min(pxTopMenuToSlideUp, elTop - startViewport.top) :
                          startScroll + scrollLengthToShowBottom;
    } else {
      var pxTopMenuToSlideDown = (header && topMenu ?
                                  $(header).offset().top + header.offsetHeight - $(topMenu).offset().top :
                                  0
                                 );
      var targetScroll = startScroll - startViewport.top + elTop
                       - Math.min(pxTopMenuToSlideDown, startViewport.top - elTop);
    }
    
    var animationTime = Math.min( Math.abs(targetScroll - startScroll), 1000);
    var startAnimationTime = Date.now();
    var animate = function() {
      var progress = Math.min( (Date.now() - startAnimationTime) / animationTime, 1 );
      var t = progress < 0.5 ? 1 - Math.cos( progress * Math.PI / 2 ) :
                               Math.sin( progress * Math.PI / 2 );

      /**
       * because some elements may still be being rendered and some images may still be being loaded
       * and their position and size changes this recalculations should re applied
       */
      var newElTop = el.offset().top;
      var newElHeight = el.outerHeight();
      if (newElTop !== elTop || newElHeight !== elHeight) {
        if (scrollDown) {
          scrollLengthToShowBottom = newElTop + newElHeight - startViewport.bottom;
          targetScroll = startViewport.top + scrollLengthToShowBottom +
                         Math.min(pxTopMenuToSlideUp, scrollLengthToShowBottom) > newElTop ?
                          startScroll - startViewport.top + newElTop + Math.min(pxTopMenuToSlideUp, newElTop - startViewport.top) :
                          startScroll + scrollLengthToShowBottom;
        } else {
          targetScroll = startScroll - startViewport.top + newElTop
                       - Math.min(pxTopMenuToSlideDown, startViewport.top - newElTop);
        }
      }
      
      window.scrollTo( startViewport.left, (targetScroll - startScroll) * t + startScroll );
      if (progress < 1) scrollAnimationHandle = RequestAnimationFrame(animate);
    };
    scrollAnimationHandle = RequestAnimationFrame(animate);

    return true;
  };

  var self = {
    intoView: function(selector) {
      var el = $(selector);
      if (!el.length) return false;
      return scrollIntoView(el.eq(0));
    },
    to: function(selector) {
      var el = $(selector);
      if (!el.length) return false;
      return scrollTo( el.eq(0).offset().top );
    }
  };
  
  return self;
};

function Color() {
  var self = {};
  /**
   * Converts an RGB color value to HSL. Conversion formula
   * adapted from http://en.wikipedia.org/wiki/HSL_color_space.
   * Assumes r, g, and b are contained in the set [0, 255] and
   * returns h, s, and l in the set [0, 1].
   *
   * @param   Number  r       The red color value
   * @param   Number  g       The green color value
   * @param   Number  b       The blue color value
   * @return  Array           The HSL representation
   */
  self.rgbToHsl = function(r, g, b) {
    r /= 255, g /= 255, b /= 255;

    var max = Math.max(r, g, b), min = Math.min(r, g, b);
    var h, s, l = (max + min) / 2;

    if (max == min) {
      h = s = 0; // achromatic
    } else {
      var d = max - min;
      s = l > 0.5 ? d / (2 - max - min) : d / (max + min);

      switch (max) {
        case r: h = (g - b) / d + (g < b ? 6 : 0); break;
        case g: h = (b - r) / d + 2; break;
        case b: h = (r - g) / d + 4; break;
      }

      h /= 6;
    }

    return [ h, s, l ];
  };

  /**
   * Converts an HSL color value to RGB. Conversion formula
   * adapted from http://en.wikipedia.org/wiki/HSL_color_space.
   * Assumes h, s, and l are contained in the set [0, 1] and
   * returns r, g, and b in the set [0, 255].
   *
   * @param   Number  h       The hue
   * @param   Number  s       The saturation
   * @param   Number  l       The lightness
   * @return  Array           The RGB representation
   */
  self.hslToRgb = function(h, s, l) {
    var r, g, b;

    if (s == 0) {
      r = g = b = l; // achromatic
    } else {
      function hue2rgb(p, q, t) {
        if (t < 0) t += 1;
        if (t > 1) t -= 1;
        if (t < 1/6) return p + (q - p) * 6 * t;
        if (t < 1/2) return q;
        if (t < 2/3) return p + (q - p) * (2/3 - t) * 6;
        return p;
      }

      var q = l < 0.5 ? l * (1 + s) : l + s - l * s;
      var p = 2 * l - q;

      r = hue2rgb(p, q, h + 1/3);
      g = hue2rgb(p, q, h);
      b = hue2rgb(p, q, h - 1/3);
    }

    return [ Math.round(r * 255), Math.round(g * 255), Math.round(b * 255) ];
  };

  /**
   * Converts an RGB color value to HSV. Conversion formula
   * adapted from http://en.wikipedia.org/wiki/HSV_color_space.
   * Assumes r, g, and b are contained in the set [0, 255] and
   * returns h, s, and v in the set [0, 1].
   *
   * @param   Number  r       The red color value
   * @param   Number  g       The green color value
   * @param   Number  b       The blue color value
   * @return  Array           The HSV representation
   */
  self.rgbToHsv = function(r, g, b) {
    r /= 255, g /= 255, b /= 255;

    var max = Math.max(r, g, b), min = Math.min(r, g, b);
    var h, s, v = max;

    var d = max - min;
    s = max == 0 ? 0 : d / max;

    if (max == min) {
      h = 0; // achromatic
    } else {
      switch (max) {
        case r: h = (g - b) / d + (g < b ? 6 : 0); break;
        case g: h = (b - r) / d + 2; break;
        case b: h = (r - g) / d + 4; break;
      }

      h /= 6;
    }

    return [ h, s, v ];
  };

  /**
   * Converts an HSV color value to RGB. Conversion formula
   * adapted from http://en.wikipedia.org/wiki/HSV_color_space.
   * Assumes h, s, and v are contained in the set [0, 1] and
   * returns r, g, and b in the set [0, 255].
   *
   * @param   Number  h       The hue
   * @param   Number  s       The saturation
   * @param   Number  v       The value
   * @return  Array           The RGB representation
   */
  self.hsvToRgb = function(h, s, v) {
    var r, g, b;

    var i = Math.floor(h * 6);
    var f = h * 6 - i;
    var p = v * (1 - s);
    var q = v * (1 - f * s);
    var t = v * (1 - (1 - f) * s);

    switch (i % 6) {
      case 0: r = v, g = t, b = p; break;
      case 1: r = q, g = v, b = p; break;
      case 2: r = p, g = v, b = t; break;
      case 3: r = p, g = q, b = v; break;
      case 4: r = t, g = p, b = v; break;
      case 5: r = v, g = p, b = q; break;
    }

    return [ Math.round(r * 255), Math.round(g * 255), Math.round(b * 255) ];
  };

  /**
   * Returns distinct bright RGB colors.
   * Assumes index is an integer >= 0 and
   * returns r, g, and b in the set [0, 255].
   *
   * @param   Number  index   The zero based color index
   * @return  Array           The RGB representation
   */
  self.getPseudoRandomRgb = function(index) {
    var goldenRation = 0.618033988749895;
    var h = goldenRation * index % 1;
    return self.hslToRgb(h, 0.85, 0.5);
  };

  return self;
};
