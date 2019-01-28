authApp.config(router);

function router($stateProvider, $urlRouterProvider) {
    $stateProvider 
        .state('login', {
            url: "/login",
            templateUrl: "/bundles/tretouser/partials/login.html",
            controller: 'loginCtrl'
        })
        .state('resetting', {
            url: "/resetting",
            templateUrl: "/bundles/tretouser/partials/resetting.html",
            controller: 'resettingCtrl'
        })
        .state('logout', {
            url: "/logout",
            templateUrl: "/bundles/tretouser/partials/logout.html",
            controller: 'logoutCtrl'
        })
        .state('body.profileDisplay', {
            url: "/profile/display/{id:[A-Za-z0-9 \/\=]+}/{force}/{blogs}",
            views: {
                "main": {
                    templateUrl: "/bundles/tretouser/partials/profile/display.html",
                    controller: "profileDisplayCtrl"
                }
            }
        })
        .state('body.profileEdit', {
            url: "/profile/edit/:id",
            views: {
                "main": {
                    templateUrl: "/bundles/tretouser/partials/profile/edit.html",
                    controller: "profileEditCtrl"
                }
            }
        }).state('body.profileManagerEdit', {
            url: "/profile/manager-edit/:id",
            views: {
                "main": {
                    templateUrl: "/bundles/tretouser/partials/profile/manager-edit.html",
                    controller: "profileEditCtrl"
                }
            }
        });
}
