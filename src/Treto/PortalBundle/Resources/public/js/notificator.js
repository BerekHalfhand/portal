portalApp
  .controller('notificatorCtrl', notificatorCtrl)
  .directive('notification', notification)
  .directive('notifMenu', notifMenu)
  .directive('notifThread', notifThread)
  .directive('notifPopups', notifPopups)
;

notificatorCtrl.$inject = ['$http', '$scope', '$timeout', '$rootScope', '$state', '$log', '$filter', 'Discus', 'Notificator', 'DiscusSharedSvc', 'Popup', 'History', 'TretoDateTime'];
function notificatorCtrl($http, $scope, $timeout, $rootScope, $state, $log, $filter, Discus, Notificator, DiscusSharedSvc, Popup, History, TretoDateTime) {
  $scope.relevantNotifs = {};
  $scope.urgentNotif = {};
  $scope.normalNotif = {};
  $scope.urgentCount = 0;
  $scope.normalCount = 0;
  $scope.updatingNotifs = false;
  $scope.markingDocumentsRead = false;
  $scope.delayingDocuments = false;
  $scope.unsubscribing = false;
  var hasOpenedRequestedDoc = false;

  $scope.add_favorites = DiscusSharedSvc.add_favorites;
  $scope.del_favorites = DiscusSharedSvc.del_favorites;
  $scope.isFavorite = DiscusSharedSvc.isFavorite;

  Notificator.useNotificatorByDefault(true);
  
  $scope.checkedDocs = sessionStorage.getItem("notificatorCheckedDocs") ? angular.fromJson(sessionStorage.getItem("notificatorCheckedDocs")) : {};
  $scope.checkedDocsCount = 0;
  for (var i in $scope.checkedDocs) $scope.checkedDocsCount++;

  var cleanNotifs = function() {
    for (var i in $scope.urgentNotif)
      if (!$scope.urgentNotif[i].expanded && !(i in $scope.relevantNotifs)) {
        if (i in $scope.checkedDocs) {
          delete $scope.checkedDocs[i];
          $scope.checkedDocsCount -= 0 === $scope.checkedDocsCount ? 0 : 1;
        }
        delete $scope.urgentNotif[i];
        $scope.urgentCount--;
      }
    for (var i in $scope.normalNotif)
      if (!$scope.normalNotif[i].expanded && !(i in $scope.relevantNotifs)) {
        if (i in $scope.checkedDocs) {
          delete $scope.checkedDocs[i];
          $scope.checkedDocsCount -= 0 === $scope.checkedDocsCount ? 0 : 1;
        }
        delete $scope.normalNotif[i];
        $scope.normalCount--;
      }
    sessionStorage.setItem("notificatorCheckedDocs", angular.toJson($scope.checkedDocs));
  };

  var addNotif = function(i, urgency) {
    var list = urgency ? $scope.urgentNotif : $scope.normalNotif;
    if ( i in list ) {
      if ($scope.relevantNotifs[i].addedWhen > list[i].addedWhen && !list[i].expanded) {
        var tmp = angular.copy($scope.relevantNotifs[i]);
        tmp.addedWhen = list[i].addedWhen
        list[i] = tmp;
      }
    } else {
      list[i] = angular.copy($scope.relevantNotifs[i]);
      urgency ? $scope.urgentCount++ : $scope.normalCount++;
    }
    list[i].isUrgent = urgency;
  };

  $scope.updateNotifs = function() {
      $scope.updatingNotifs = true;
      $scope.relevantNotifs = $filter('relevantNotifications')($rootScope.notif);
      cleanNotifs();
      for (var i in $scope.relevantNotifs) {
        var urgency = false;
        if ($scope.relevantNotifs[i].urgency > 1) urgency = true;
        if (!urgency)
          for (var j in $scope.relevantNotifs[i].docs)
            if ($scope.relevantNotifs[i].docs[j].urgency > 1) {
              urgency = true;
              break;
            }
        addNotif(i, urgency);
      }
      $timeout(function() { $scope.updatingNotifs = false; }, 0);
      
      // fix for new notificator's .fast-reply block to stick to the bottom and not go down out of screen
      $timeout(function() { $(window).trigger('fixStickyControllsInNotificator'); }, 200);
  };

  $scope.updateNotifs();

  $scope.$watch(function(scope) { return $rootScope.notif }, function(newValue, oldValue) {
    if (typeof oldValue == "undefined" || newValue != oldValue) {
      $log.info('update notifs');
      $timeout($scope.updateNotifs, 0);
    }
  }, true);

  $scope.openRequestedDoc = function(unid) {
    if (hasOpenedRequestedDoc || $state.params.id !== unid) return false;
    return hasOpenedRequestedDoc = true;
  };

  $scope.closeOpenedThread = function() {
    for (var i in $scope.urgentNotif)
      if ($scope.urgentNotif[i].expanded) $scope.urgentNotif[i].toggleExpand();
    for (var i in $scope.normalNotif)
      if ($scope.normalNotif[i].expanded) $scope.normalNotif[i].toggleExpand();
  };

  $scope.getShareName = function(dataType, sendShareFrom, shareAuthorLogin){
    var result = '';
    if(sendShareFrom, shareAuthorLogin){
      var name = $rootScope.shareUsers[sendShareFrom].name;
      result = Discus.findShareDataByLogins(dataType, sendShareFrom, shareAuthorLogin) + ' ('+name+')';
    }
    return result;
  };

  $scope.changeCheckedState = function(unid) {
    if (unid in $scope.checkedDocs) {
      delete $scope.checkedDocs[unid];
      $scope.checkedDocsCount--;
    } else {
      $scope.checkedDocs[unid] = TretoDateTime.iso8601.fromDateTime($rootScope.serverTime ? new Date($rootScope.serverTime) : new Date());
      $scope.checkedDocsCount++;
    }
    sessionStorage.setItem("notificatorCheckedDocs", angular.toJson($scope.checkedDocs));
  };

  $scope.uncheckAllTheDocs = function() {
    for (var i in $scope.checkedDocs) delete $scope.checkedDocs[i];
    $scope.checkedDocsCount = 0;
    sessionStorage.setItem("notificatorCheckedDocs", angular.toJson($scope.checkedDocs));
  };

  $scope.markCheckedAsRead = function() {
    var docsToCheckAsRead = [];
    for (var i in $scope.checkedDocs)
      if ($scope.checkedDocs[i]) {
        var checkTime = TretoDateTime.iso8601.toDateTime($scope.checkedDocs[i]);
        docsToCheckAsRead.push({
          unid: i,
          parentUnid: i,
          time: $rootScope.formatDateForReadBy(checkTime),
          timeISO: $rootScope.formatDateForReadBy(checkTime, true),
        });
        if (i in $scope.normalNotif && $scope.normalNotif[i].expanded)
          $scope.normalNotif[i].toggleExpand();
      }
    if (!docsToCheckAsRead.length) return;
    $scope.markingDocumentsRead = !$scope.unsubscribing;
    Notificator.markAsRead(docsToCheckAsRead, function() {
      $scope.uncheckAllTheDocs();
      $scope.markingDocumentsRead = false;
      $scope.unsubscribing = false;
    });
  };

  $scope.delayCheckedFor = function(time) {
    for (var i in $scope.checkedDocs)
      if ($scope.checkedDocs[i]) {
        if (!$scope.normalNotif[i]) {
          console.warn('Something went wrong, this document '+i+' could not be checked.');
        } else {
          var unid = $scope.normalNotif[i].parentUnid ? $scope.normalNotif[i].parentUnid : i;
          $scope.delayingDocuments = true;

          $http({method: 'GET', url: 'api/notif/delay/'+unid+'/'+time, params: {'id':unid, 'time':time} })
            .then(function(response) {
              $scope.delayingDocuments = false;
              if (!response.data.success)
                new Popup('Уведомления', 'Ошибка при откладывании уведомления', 'Error');
              else {
                delete $scope.checkedDocs[i];
                $scope.checkedDocsCount--;
                sessionStorage.setItem("notificatorCheckedDocs", angular.toJson($scope.checkedDocs));
              }
            }, httpErrorHandler);
        }
      }
  };

  $scope.toggleCheckedFavorites = function() {
    for (var i in $scope.checkedDocs)
      if ($scope.checkedDocs[i]) {
        if (!$scope.normalNotif[i]) {
          console.warn('Something went wrong, this document '+i+' could not be checked.');
        } else {
          !$scope.isFavorite($scope.normalNotif[i].parentUnid) ? $scope.add_favorites($scope.normalNotif[i].parentUnid) : $scope.del_favorites($scope.normalNotif[i].parentUnid);
        }
      }
    $scope.uncheckAllTheDocs();
  };

  $scope.unsubscribeChecked = function() {
    var list = '', listCount = 0;
    var unsubscribeParentUnids = [];
    for (var i in $scope.checkedDocs) {
      if ($scope.checkedDocs[i]) {
        if (!$scope.normalNotif[i]) {
          console.warn('Something went wrong, this document '+i+' could not be checked.');
        } else {
          listCount++;
          list += listCount+'. '+$scope.normalNotif[i].subject+"<br>";
          unsubscribeParentUnids.push($scope.normalNotif[i].parentUnid);
        }
      }
    }
    Popup("Подтверждение",
          "Вы действительно хотите отписаться от следующих тем?<br><br>"+list,
          '',
          true,
          function() {
            $scope.unsubscribing = true;
            for (var i = 0; i < unsubscribeParentUnids.length; i++) {
              Discus.getDocFromServerByUnid(unsubscribeParentUnids[i], function(respDoc) {
                Discus.removeParticipant($rootScope.user.username, 'username', respDoc, null, true);
              });
            }
            delete unsubscribeParentUnids;
            $scope.markCheckedAsRead();
          },
          function(){
            return false;
          });
  };
};

/*
 * To enable autoreading docs just uncomment all the throttledScanOnscreenDocs function calls and bindings
 */
notification.$inject = ['$rootScope', 'DiscusInstance', 'Scroll', '$timeout', 'Viewport', 'Notificator', 'History'];
function notification($rootScope, DiscusInstance, Scroll, $timeout, Viewport, Notificator, History) {
  return {
    restrict: 'E',
    replace: true,
    templateUrl: '/bundles/tretoportal/partials/notificator/notification.html',
    scope: {
      notif: '=ngModel' 
    },
    require: '^notificatorCtrl',
    controller: ['$scope', function($scope) {
      var n = $scope.notif;
      n.expanded = false;
      var environment = $rootScope.environment;
      $scope.discus = null;
      $scope.avatarUrl = '/public/img_site/'+environment+'/thumb_'+n.AuthorLogin+'.jpeg';
      $scope.getShareName = $scope.$parent.getShareName;
      $scope.checkedDocs = $scope.$parent.checkedDocs;
      $scope.changeCheckedState = $scope.$parent.changeCheckedState;
      var closeOpenedThread = $scope.$parent.closeOpenedThread;
      var updateNotifs = $scope.$parent.updateNotifs;
      var isWndActive = true;
      var readedOnce = false;

      var TIME_TO_READ = 2000;
      var readTimerCount, docsToReadCount, docsToRead;
      var addToReadList = function(doc, now) {
        if ($scope.discus.isNew(doc) &&
            !(doc.unid in docsToRead) &&
            Viewport.isInViewport('#'+doc.unid, Notificator.getViewport())) {
          docsToReadCount++;
          docsToRead[doc.unid] = {
            doc: doc,
            time: now
          };
          return true;
        }
        return false;
      };
      var scanOnscreenDocs = function() {
        if (!isWndActive || document.hidden || !$scope.discus) return;
        var now = Date.now();
        var hasDocToRead = false;
        if ($scope.discus.newPosts.count > 0) {
          hasDocToRead = addToReadList($scope.discus.main_doc, now);
          for (var i = 0; i < $scope.discus.comments.length; i++)
            hasDocToRead = addToReadList($scope.discus.comments[i], now) || hasDocToRead;
        }
        if (!hasDocToRead && readedOnce) return;
        readedOnce = true;
        readTimerCount++;
        $timeout(readFunc, TIME_TO_READ);
      };
      var readFunc = function() {
        if (!isWndActive || document.hidden || !$scope.discus || !$scope.discus.main_doc) return;
        readTimerCount -= 0 === readTimerCount ? 0 : 1;
        var now = Date.now();
        for (var i in docsToRead)
          if ( Viewport.isInViewport('#'+docsToRead[i].doc.unid, Notificator.getViewport()) ) {
            if (TIME_TO_READ < now - docsToRead[i].time) {
              $scope.discus.removeNewPostLabel(docsToRead[i].doc);
              docsToReadCount--;
              delete docsToRead[i];
            }
          } else {
            docsToReadCount--;
            delete docsToRead[i];
          }
        if (0 === readTimerCount && 0 < docsToReadCount)
          $timeout(readFunc, 200);
        if (0 === $scope.discus.newPosts.count)
          Notificator.markAsRead([{
            unid: $scope.discus.main_doc.unid,
            parentUnid: $scope.discus.main_doc.unid
          }]);
      };
      var throttledScanOnscreenDocs = _.throttle(scanOnscreenDocs, 200);

      var initAfterLoadingDiscus = function() {
        if ($rootScope.$$phase === '$apply' || $rootScope.$$phase === '$digest')
          $timeout(initAfterLoadingDiscus, 50);
        else {
          readTimerCount = 0;
          docsToReadCount = 0;
          docsToRead = {};
          Scroll.intoView( document.getElementById('notif_'+n.unid) );
          // $(window).on('mousemove.notif_'+n.unid, throttledScanOnscreenDocs);
          // $(window).on('scroll.notif_'+n.unid, throttledScanOnscreenDocs);
          $(window).on('focus.notif_'+n.unid, function() {
            isWndActive = true;
            // throttledScanOnscreenDocs();
          });
          $(window).on('blur.notif_'+n.unid, function() {
            isWndActive = false;
          });
          $(window).trigger('notifExpand_'+n.unid);
          // $timeout(throttledScanOnscreenDocs, 250);
        }
      };

      n.toggleExpand = function() {
        console.log('expand', n.unid);
        if (!n.expanded) {
          closeOpenedThread();
          n.expanded = true;
          $scope.discus = new DiscusInstance();
          $scope.discus.progress = 'loading discus with unreaded comments';
          $scope.discus.getWithUnreadedComments(n.parentUnid, n.docs, function(respDoc, comments) {
            if (!n.expanded) return;
            $scope.discus.prepareForReadAfter(respDoc);
            $scope.discus.main_doc = respDoc;
            $scope.discus.expanded[$scope.discus.main_doc.unid] = 'shown';

            if (comments && comments.length > 0) {
              $scope.discus.comments = comments;
              $scope.discus.unreaded = [];
              for (var i in $scope.discus.comments) {
                $scope.discus.expanded[$scope.discus.comments[i].unid] = 'shown';
                if ($scope.discus.comments[i].form == 'formTask' ||
                    $scope.discus.comments[i].form == 'formVoting')
                  $scope.discus.prepareForReadAfter($scope.discus.comments[i]);
              }
            }

            if ($rootScope.user.settings &&
                $rootScope.user.settings.notifHistory &&
                n.form != 'Empl') {
              History.add_full($scope.discus.main_doc.subject,
                              n.parentForm,
                              $scope.discus.main_doc.unid);
            }
            $scope.discus.initDictionaries();
            $scope.discus.joinDiscus();
            $scope.discus.progress = 0;
            // $scope.discus.readDocsExternalHandler = throttledScanOnscreenDocs;
            initAfterLoadingDiscus();
          });
        } else {
          $(window).off('.notif_'+n.unid);
          $(window).trigger('notifCollapse_'+n.unid);
          n.expanded = false;
          if ($scope.discus && $scope.discus.main_doc) {
            $scope.discus.leaveDiscus();
            $scope.discus.clear();
          }
          $scope.discus = null;
          $timeout(updateNotifs, 350);
        }
      };

      $scope.$on('$destroy', function() {
        $(window).off('.notif_'+n.unid);
        if (!$scope.discus) return;
        $scope.discus.leaveDiscus();
        $scope.discus.clear();
        $scope.discus = null;
      });

      $scope.$parent.openRequestedDoc(n.parentUnid) && n.toggleExpand();
    }]
  };
};

notifMenu.$inject = ['$timeout', '$rootScope', 'Notificator'];
function notifMenu($timeout, $rootScope, Notificator) {
  return {
    restrict: 'E',
    replace: true,
    templateUrl: '/bundles/tretoportal/partials/notificator/notif-menu.html',
    require: '^notification',
    scope: false,
    link: function($scope, $element, $attrs) {
      var notifMenu = $element;
      var header = $('#header');

      var makeNofitMenuVisible = function() {
        if (!$scope.notif.expanded) return;
        var v = Notificator.getViewport();
        if (notifMenu.parent().offset().top < v.top) {
          var position = (
                          notifMenu.parent().find('.fast-reply-wrap').length ?
                            notifMenu.parent().find('.fast-reply-wrap').offset().top :
                            notifMenu.parent().outerHeight() + notifMenu.parent().offset().top
                         )
                       - v.top
                       - notifMenu.outerHeight();
          var positionStyle = position < 0 ? 'translate3d(0,'+position+'px,0)' : ''
          notifMenu.addClass('fixed')
            .css({
              'top': header.outerHeight() + 'px',
              '-webkit-transform': positionStyle,
              '-moz-transform': positionStyle,
              '-ms-transform': positionStyle,
              '-o-transform': positionStyle,
              'transform': positionStyle
            })
            .parent().css('padding-top', notifMenu.outerHeight() + 'px');
        } else {
          notifMenu.removeClass('fixed')
            .css({
              'top': '',
              '-webkit-transform': '',
              '-moz-transform': '',
              '-ms-transform': '',
              '-o-transform': '',
              'transform': ''
            })
            .parent().css('padding-top', '');
        }
      };
      var throttledMakeNofitMenuVisible = _.throttle(makeNofitMenuVisible, 50);

      var init = function() {
        throttledMakeNofitMenuVisible();
        $(window).on('resize.notifMenu_'+$scope.notif.unid, throttledMakeNofitMenuVisible);
        $(window).on('scroll.notifMenu_'+$scope.notif.unid, throttledMakeNofitMenuVisible);
        $(window).on('fixStickyControllsInNotificator.notifMenu_'+$scope.notif.unid, throttledMakeNofitMenuVisible);
      };
      
      $(window).on('notifExpand_'+$scope.notif.unid+'.notifMenu_'+$scope.notif.unid, init);
      $(window).on('notifCollapse_'+$scope.notif.unid+'.notifMenu_'+$scope.notif.unid, function() {
        $(window).off('resize.notifMenu_'+$scope.notif.unid);
        $(window).off('scroll.notifMenu_'+$scope.notif.unid);
        $(window).off('fixStickyControllsInNotificator.notifMenu_'+$scope.notif.unid);
        notifMenu.removeClass('fixed')
          .css({
              'top': '',
              '-webkit-transform': '',
              '-moz-transform': '',
              '-ms-transform': '',
              '-o-transform': '',
              'transform': ''
            })
          .parent().css('padding-top', '');
      });

      $scope.$on('$destroy', function() {
        $(window).off('.notifMenu_'+$scope.notif.unid);
        $(window).off('.notifMenuSize_'+$scope.notif.unid);
      });
    }
  };
};

notifThread.$inject = ['Notificator', '$timeout'];
function notifThread(Notificator, $timeout) {
  return {
    restrict: 'E',
    replace: true,
    templateUrl: '/bundles/tretoportal/partials/notificator/notif-thread.html',
    require: '^notification',
    scope: false,
    link: function($scope, $element, $attrs) {
      var fastReply;

      var makeFastReplyVisible = function() {
        var v = Notificator.getViewport();
        if ($element.offset().top + $element.outerHeight() < v.bottom) {
          fastReply.removeClass('fixed')
            .css({
              '-webkit-transform': '',
              '-moz-transform': '',
              '-ms-transform': '',
              '-o-transform': '',
              'transform': ''
            });
        } else {
          var position = $element.offset().top
                       + fastReply.outerHeight()
                       - v.bottom;
          var positionStyle = position > 0 ? 'translate3d(0,'+position+'px,0)' : '';
          fastReply.addClass('fixed')
            .css({
              '-webkit-transform': positionStyle,
              '-moz-transform': positionStyle,
              '-ms-transform': positionStyle,
              '-o-transform': positionStyle,
              'transform': positionStyle
            });
        }
      };
      var throttledMakeFastReplyVisible = _.throttle(makeFastReplyVisible, 50);

      var init = function() {
        fastReply = $element.find('.fast-reply');
        if (!fastReply.length)
          $timeout(init, 100);
        else {
          throttledMakeFastReplyVisible();
          $(window).on('resize.notifThread_'+$scope.notif.unid, throttledMakeFastReplyVisible);
          $(window).on('scroll.notifThread_'+$scope.notif.unid, throttledMakeFastReplyVisible);
          $(window).on('fixStickyControllsInNotificator.notifThread_'+$scope.notif.unid, throttledMakeFastReplyVisible);
        }
      };
      
      $(window).on('notifExpand_'+$scope.notif.unid+'.notifThread_'+$scope.notif.unid, init);
      $(window).on('notifCollapse_'+$scope.notif.unid+'.notifThread_'+$scope.notif.unid, function() {
        $(window).off('resize.notifThread_'+$scope.notif.unid);
        $(window).off('scroll.notifThread_'+$scope.notif.unid);
        $(window).off('fixStickyControllsInNotificator.notifThread_'+$scope.notif.unid);
        fastReply && fastReply.length && fastReply.parent().css('padding-bottom', '') && fastReply.css({
          '-webkit-transform': '',
          '-moz-transform': '',
          '-ms-transform': '',
          '-o-transform': '',
          'transform': ''
        });
      });

      $scope.$on('$destroy', function() {
        $(window).off('.notifThread_'+$scope.notif.unid);
      });
    }
  };
};

notifPopups.$inject = [];
function notifPopups() {
  return {
    restrict: 'E',
    replace: true,
    templateUrl: '/bundles/tretoportal/partials/notificator/notif-popups.html',
    require: '^notification',
    scope: false,
    link: function($scope, $element, $attrs) {

    }
  };
};
