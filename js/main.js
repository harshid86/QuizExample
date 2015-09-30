var app = angular.module("myQuizApp", ['ngStorage', 'ngFileUpload', 'ngAnimate', 'ui.bootstrap'])
.config(function($interpolateProvider){
    $interpolateProvider.startSymbol('[[').endSymbol(']]')
});