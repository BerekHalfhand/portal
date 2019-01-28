portalApp.config(router);

function router($stateProvider, $urlRouterProvider, $locationProvider) {
  $urlRouterProvider.otherwise("/");
  $locationProvider.hashPrefix('');

  $stateProvider
    //.state('chat',{
    //  url: "/chat",
    //  templateUrl: "/bundles/tretoportal/partials/chat.html",
    //  controller: 'chatCtrl'
    //})
    .state('chat-history',{
      url: "/chat-history",
      templateUrl: "/bundles/tretoportal/partials/chatHistory.html",
      controller: 'chatHistoryCtrl'
    })
    .state('body', {
        templateUrl: "/bundles/tretoportal/partials/body.html",
        controller: function($scope, $rootScope, $location, Popup, $state, TretoGlobal, localize, Colors) {
            TretoGlobal.init();
            $rootScope.$state = $state;
            $rootScope.prompt = prompt;
            $rootScope.confirm = function(text) { return confirm(text); }
            $rootScope.localize = localize;

            if (!$rootScope.expiredPopup) {
              $rootScope.expiredPopup = {};
              $rootScope.expiredPopup.show = true;
            }

            httpErrorHandler = function(response) {
                new Popup(localize('HTTP Response failed','',response), response.data, 'error');
            };
        }
        ,
        resolve: {
        waitForAuth: function(Auth) {
            return (new Auth()).auth();
        } }
    })
    .state('body.index', {
      url: "/",
      views: {
        "main": {
          templateUrl: "/bundles/tretoportal/partials/index.html",
          controller: 'indexCtrl'
        }
      }
    })
    .state('body.tasks', {
      url: "/tasks",
      views: {
        "main": {
          templateUrl: "/bundles/tretoportal/partials/tasks.html",
          controller: 'tasksCtrl'
        }
      }
    })
    .state('body.calendar', {
      url: "/calendar",
      views: {
        "main": {
          templateUrl: "/bundles/tretoportal/partials/calendar.html",
          controller: 'calendarCtrl'
        }
      }
    })
    .state('body.tags', {
      url: "/tags/:jsonQuery",
      views: {
        "main": {
          templateUrl: "/bundles/tretoportal/partials/tags.html",
          controller: 'tagsPageCtrl'
        }
      },
      params: { selectedTag: null }
    })
    .state('body.taskSearch', {
      url: "/taskSearch/:jsonQuery",
      views: {
        "main": {
          templateUrl: "/bundles/tretoportal/partials/tasks.html",
          controller: 'taskSearchCtrl'
        }
      }
    })
    .state('body.discusList', {
      url: "/discus/:category",
      views: {
        "main": {
          templateUrl: "/bundles/tretoportal/partials/discusList.html",
          controller: 'discusListCtrl'
        }
      }
    })
    .state('body.discus', {
      url: "/discus/:id/:type?:client&:locale",
      views: {
        "main": {
          templateUrl: "/bundles/tretoportal/partials/discus.html",
          controller: 'discusCtrl'
        }
      }
    })
    .state('body.portalSettings', {
      url: "/portalSettings",
      views: {
        "main": {
          templateUrl: "/bundles/tretoportal/partials/portalSettings.html",
          controller: 'portalSettingsCtrl'
        }
      }
    })
    .state('body.factoryHoliday', {
      url: "/factoryHoliday",
      views: {
        "main": {
          templateUrl: "/bundles/tretoportal/partials/factoryHoliday.html",
          controller: 'factoryHolidayCtrl'
        }
      }
    })
    .state('body.contacts', {
      url: "/contact/list",
      views: {
        "main": {
          templateUrl: "/bundles/tretoportal/partials/contacts.html",
          controller: "contactsCtrl"
        }
      }
    })
    .state('body.contacts.type', {
      url: "/:thistype"
    })
    .state('body.admin', {
      url: "/admin",
      views: { "main": { controller: 'adminCtrl' } }
    })
    .state('body.adminDictionary', {
      url: "/admin/dictionary/:type",
      views: {
        "main": {
          templateUrl: "/bundles/tretoportal/partials/admin/adminDictionaries.html",
          controller: 'adminDictionaryCtrl'
        }
      }
    })
    .state('body.logList', {
      url: "/log/list",
      views: {
        "main": {
          templateUrl: "/bundles/tretoportal/partials/logList.html",
          controller: 'historyCtrl'
        }
      }
    })
    .state('body.logItem', {
      url: "/log/item/:id",
      views: {
        "main": {
          templateUrl: "/bundles/tretoportal/partials/logItem.html",
          controller: 'historyCtrl'
        }
      }
    })
    .state('body.c1logList', {
      url: "/c1log/list",
      views: {
        "main": {
          templateUrl: "/bundles/tretoportal/partials/c1logList.html",
          controller: 'c1logsCtrl'
        }
      }
    })
    .state('body.favorites', {
      url: "/favorites",
      views: {
        "main": {
          templateUrl: "/bundles/tretoportal/partials/favorites.html",
          controller: 'favoritesCtrl'
        }
      }
    })
    .state('body.links', {
      url: "/links",
      views: {
        "main": {
          templateUrl: "/bundles/tretoportal/partials/links.html",
          controller: 'linksCtrl'
        }
      }
    })
    .state('body.serp', {
      url: "/serp/:jsonQuery",
      views: {
        "main": {
          templateUrl: "/bundles/tretoportal/partials/serp.html",
          controller: 'serpCtrl'
        }
      }
    })
    .state('body.displayfile', {
      url: "/display/:hash",
      views: {
        "main": {
          templateUrl: "/bundles/tretoportal/partials/doc_templ/attachment/display.html",
          controller: 'filePreview'
        }
      }
    })
    .state('body.wp', {
      url: "/wp",
      views: {
        "main": {
          templateUrl: "/bundles/tretoportal/partials/links/workplan.html",
          controller: 'workPlanCtrl'
        }
      }
    })
    .state('body.stat', {
      url: "/stat/:tab",
      views: {
        "main": {
          templateUrl: "/bundles/tretoportal/partials/stat/stat.html",
          controller: 'statCtrl'
        }
      }
    })
    .state('body.showMessagesByUser', {
      url: "/showMessagesByUser/:query",
      views: {
        "main": {
          templateUrl: "/bundles/tretoportal/partials/showMessagesByUser.html",
          controller: 'showMessagesByUserCtrl'
        }
      }
    })
    .state('body.adaptation', {
        url: "/adaptation",
        views: {
            "main": {
                templateUrl: "/bundles/tretoportal/partials/adaptation.html",
                controller: "adaptationCtrl"
            }
        }
    })
    .state('body.notifications', {
        url: "/notifications/:id",
        views: {
            "main": {
                templateUrl: "/bundles/tretoportal/partials/notifications.html",
                controller: "notifCtrl"
            }
        }
    })
    .state('body.notificator', {
        url: "/notificator/:id",
        views: {
            "main": {
              templateUrl: "/bundles/tretoportal/partials/notificator/notificator.html",
              controller: "notificatorCtrl"
            }
        }
    })
    .state('body.voting', {
      url: "/voting",
      views: {
        "main": {
          templateUrl: "/bundles/tretoportal/partials/voting-list.html",
          controller: "votingListCtrl"
        }
      }
    })
    .state('body.criterions', {
      url: "/criterions",
      views: {
        "main": {
          templateUrl: "/bundles/tretoportal/partials/criterionList.html",
          controller: "criterionListCtrl"
        }
      },
      resolve: {
        quest: function(Question) {
          return Question.getCriterionsForRouter();
        }
      }
    })
    .state('body.criterions.criterion', {
      url: "/criterion/:unid",
      views: {
        "criterion": {
          templateUrl: "/bundles/tretoportal/partials/criterion.html",
          controller: "criterionCtrl"
        }
      }
    })
    .state('body.criterions.criterionCreate', {
      url: "/criterion-new",
      views: {
        "criterion": {
          templateUrl: "/bundles/tretoportal/partials/criterionCreate.html",
          controller: "criterionCreateCtrl"
        }
      }
    })
    .state('body.criterions.questionary', {
      url: "/questionary/:unid",
      views: {
        "criterion": {
          templateUrl: "/bundles/tretoportal/partials/questionary.html",
          controller: "questionaryCtrl"
        }
      }
    })
    .state('body.criterions.questionaryCreate', {
      url: "/questionary-new",
      views: {
        "criterion": {
          templateUrl: "/bundles/tretoportal/partials/questionaryCreate.html",
          controller: "questionaryCreateCtrl"
        }
      }
    })
    .state('body.criterions.questionCreate',{
      url: "/question/new/:unid",
      views: {
        "criterion": {
          templateUrl: "/bundles/tretoportal/partials/questionNew.html",
          controller: "questionCreateCtrl"
        }
      }
    })
    .state('body.criterions.question',{
      url: "/question/:critunid/:unid",
      views: {
        "criterion": {
          templateUrl: "/bundles/tretoportal/partials/question.html",
          controller: "questionCtrl"
        }
      }
    })
    .state('body.1cCreatePerson', {
      url: "/1c/createPerson?:organization&:organizationId",
      views: {
        "main": {
          templateUrl: "/bundles/tretoportal/partials/createPerson1C.html",
          controller: 'contactNewPersonCtrl'
        }
      }
    })
    .state('body.1cCreateOrganization', {
      url: "/1c/createOrganization",
      views: {
        "main": {
          templateUrl: "/bundles/tretoportal/partials/createOrganization1C.html",
          controller: 'contactNewOrganizationCtrl'
        }
      }
    })
    .state('body.1cEditPerson', {
      url: "/1c/editPerson/:contactId",
      views: {
        "main": {
          templateUrl: "/bundles/tretoportal/partials/editPerson1C.html",
          controller: 'modalEditContact'
        }
      }
    })
    .state('body.1cEditOrganization', {
      url: "/1c/editOrganization/:contactId",
      views: {
        "main": {
          templateUrl: "/bundles/tretoportal/partials/editOrganization1C.html",
          controller: 'modalEditContact'
        }
      }
    })
    .state('body.1cContactList', {
      url: "/1c/contactList",
      views: {
        "main": {
          templateUrl: "/bundles/tretoportal/partials/contacts.html",
          controller: "contactsCtrl"
        }
      }
    })
    .state('body.teCollectionList', {
      url: '/teCollection/list/:type/:period',
      views: {
        'main': {
          templateUrl: '/bundles/tretoportal/partials/teCollectionList.html',
          controller: 'teCollectionList'
        }
      }
    })
    ;
}
