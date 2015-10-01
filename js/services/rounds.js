app.factory('Rounds', function($http, httpPost, $q) {
     
    var Rounds = function () {
        this.baseUrl = "/index.php/ajax";

        this.list = [];

        this.load = function() {
            var deferred = $q.defer();

            self = this;
            $http.get(this.baseUrl + "/rounds")
                .success(function(response) {
                    self.list = response;
                    
                    deferred.resolve();
                })
                .error(function() {
                    deferred.reject();
                });

            
            return deferred.promise;
        }

        this.addRound = function(roundName) {
            return httpPost.send(this.baseUrl + "/rounds/add", {
                name: roundName
            });
        }

        this.updateRound = function(id, roundName) {
            return httpPost.send(this.baseUrl + "/round/" + id + "/edit", {
                name: roundName
            });
        }

        this.getRound = function(id) {
            return $http.get(this.baseUrl + "/round/" + id);
        }

        this.deleteRound = function(id) {
            return $http.get(this.baseUrl + "/round/" + id + "/delete");
        }

        this.moveRound = function(id, direction) {

            return $http.get(this.baseUrl + "/round/" + id + "/move" + direction);
        }
    }

    return Rounds;
});