app.factory('Play', function($log, $timeout, $q, $http, httpPost, Teams, Rounds, Questions, Options) {
     
    var pollingInterval = 500;
    
    Teams.prototype = {
        current: {
            data: undefined,

            check: function() {
                var deferred = $q.defer();

                if (this.current.data != undefined) {

                    return this.getTeam(this.current.data.id);
                }
                else {
                    deferred.reject();
                }

                return deferred.promise;
            }

        },

        setCurrent: function(team) {
            this.current.data = team;
        }
    };

    Rounds.prototype = {
        current: {
            data: undefined,

            handle: 0
        },

        sendStartRound: function() {
            return $http.get(this.baseUrl + "/roundprogress/" + this.current.data.id + "/start");
        },

        sendRevertRound: function() {
            return $http.get(this.baseUrl + "/roundprogress/" + this.current.data.id + "/revert")
        },

        sendCompleteRound: function() {

            return $http.get(this.baseUrl + "/roundprogress/" + this.current.data.id + "/complete")
        },

        canStart: function(startedCallback, completedCallback, callback, shouldRecurse) {
            self = this;
            recurse = function () {
                self.canStart(startedCallback, completedCallback, callback, shouldRecurse);
            }

            $http.get(this.baseUrl + "/roundprogress/" + this.current.data.id)
                .success(function(response) {
                    if (response.Completed === "1"  && response.Started === "1") {

                        $log.info("Round is already complete");

                        completedCallback();
                    }
                    else if (response.Started === "1") {

                        $log.info("Round has started");

                        startedCallback();
                    }
                    else {

                        if (shouldRecurse) {
                            $timeout(recurse, pollingInterval);
                        }

                        callback();
                    }
                });
        },

        canComplete: function(completedCallback, revertedCallback) {
            self = this;
            recurse = function () {
                self.canComplete(completedCallback, revertedCallback);
            }

            $http.get(this.baseUrl + "/roundprogress/" + this.current.data.id)
                .success(function(response) {
                    if (response.Reverted === "1") {
                        $log.info("This round has been Reverted");

                        revertedCallback()
                    }
                    else if (response.Started === "1" && response.Completed === "1") {
                        $log.info("This round has been Completed");

                        completedCallback();
                    }
                    else {
                        
                        $timeout(recurse, pollingInterval);

                        return false;
                    }
                });
        },

        restart: function() {
            var deferred = $q.defer();

            $log.info("Restart this round");

            this.current.handle--;

            this.current.data = this.list[this.current.handle];

            if (this.current.data === undefined) {
                deferred.reject();
            } else {
                return this.start();
            }

            return deferred.promise;
        },

        start: function() {
            var deferred = $q.defer();

            $log.info("Starting Rounds sequence");

            this.current.data = this.list[this.current.handle];

            if (this.current.data === undefined) {
                return this.next();
            }
            else {
                deferred.resolve(this.current.data);
            }

            return deferred.promise;
        },

        next: function() {
            var deferred = $q.defer();

            $log.info("Go to next round");

            this.current.handle++;

            this.current.data = this.list[this.current.handle];

            if (this.current.data === undefined) {
                deferred.reject();
            } else {
                return this.start();
            }

            return deferred.promise;
        }
    };

    Questions.prototype = {

        current: {
            data: undefined,

            handle: 0,

            flags: {},

            resetFlags: function () {
                this.flags.timesUp = false;
                this.flags.madeChoice = false;
            }
        },

        sendStartQuestion: function() {
            return $http.get(this.baseUrl + "/questionprogress/" + this.current.data.id + "/start")
        },

        sendRevertQuestion: function() {
            $http.get(this.baseUrl + "/questionprogress/" + this.current.data.id + "/revert");
        },

        sendCompleteQuestion: function() {

            return $http.get(this.baseUrl + "/questionprogress/" + this.current.data.id + "/complete")
        },

        canStart: function(startedCallback, completedCallback, callback, shouldRecurse) {
            self = this;
            recurse = function () {
                self.canStart(startedCallback, completedCallback, callback, shouldRecurse);
            }

            $http.get(this.baseUrl + "/questionprogress/" + this.current.data.id)
                .success(function(response) {
                    if (response.Completed === "1"  && response.Started === "1") {
                        $log.info("Question is already complete");

                        completedCallback();
                    }
                    else if (response.Started === "1") {
                        $log.info("Question started");

                        startedCallback();
                    }
                    else {

                        if (shouldRecurse) {
                            $timeout(recurse, pollingInterval);
                        }

                        callback();
                    }
                });
        },

        canComplete: function(completedCallback, revertedCallback) {
            self = this;
            recurse = function () {
                self.canComplete(completedCallback, revertedCallback);
            }

            $http.get(this.baseUrl + "/questionprogress/" + this.current.data.id)
                .success(function(response) {
                    if (response.Reverted === "1") {
                        $log.info("This question has been reverted");

                        revertedCallback()
                    }
                    else if (response.Started === "1" && response.Completed === "1") {
                        $log.info("This question has been completed");

                        completedCallback();
                    }
                    else {
                        
                        $timeout(recurse, pollingInterval);
                    }
                });
        },

        restart: function() {
            var deferred = $q.defer();

            $log.info("Go to last question");

            this.current.handle--;

            this.current.data = this.list[this.current.handle];

            if (question === undefined) {
                deferred.reject();
            }
            else { 
                return this.start();
            }

            return deferred.promise;
        },

        start: function() {
            var deferred = $q.defer();

            $log.info("Start Questions sequence");

            this.current.data = this.list[this.current.handle];

            if (this.current.data === undefined) {
                return this.next();
            }
            else {
                deferred.resolve(this.current.data);
            }

            return deferred.promise;
        },

        next: function() {
            var deferred = $q.defer();

            $log.info("Go to next question");

            this.current.handle++;

            this.current.data = this.list[this.current.handle];

            if (this.current.data === undefined) {
                deferred.reject();
            } else {
                return this.start();
            }

            return deferred.promise;
        }
    };

    Options.prototype = {
        hiddenList: [],

        clear: function() {
            this.list = [];
        },

        hideAllBut: function(option) {
            this.hiddenList = this.list;
            this.list = [option];
        },

        showAll: function() {
            this.list = this.hiddenList;
        },
        
        sendChoice: function(teamId, optionId) {
            return httpPost.send(this.baseUrl + "/choices/add",
            {
                teamId: teamId,
                optionId: optionId
            });
        },

        sendNoChoice: function(teamId, questionId) {
          
            return httpPost.send(this.baseUrl + "/choices/addempty", {
                    teamId: teamId,
                    questionId: questionId
                });
        }
    };

    var Play = {
        Teams: Teams,

        Rounds: Rounds,

        Questions: Questions,

        Options: Options
    }

    return Play;

});