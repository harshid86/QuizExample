app.factory('Questions', function($http, httpPost, $q) {
     
    var Questions = function () {
        this.baseUrl = "/index.php/ajax";

        this.list = [];

        this.load = function(roundId) {
            var deferred = $q.defer();

            self = this;
            $http.get(this.baseUrl + "/round/" + roundId + "/questions")
                .success(function(response) {
                    self.list = response;
                    
                    deferred.resolve();
                })
                .error(function() {
                    deferred.reject();
                });

            
            return deferred.promise;
        }

        this.addQuestion = function(roundId, question) {
            return httpPost.send(this.baseUrl + "/questions/add", {
                questionText: question,
                roundId: roundId
            });
        }

        this.updateQuestion = function(id, question) {
            return httpPost.send(this.baseUrl + "/question/" + id + "/edit", {
                questionText: question
            });
        }

        this.getQuestion = function(id) {
            return $http.get(this.baseUrl + "/question/" + id);
        }

        this.deleteQuestion = function(id) {
            return $http.get(this.baseUrl + "/question/" + id + "/delete");
        }

        this.moveQuestion = function(id, direction) {

            return $http.get(this.baseUrl + "/question/" + id + "/move" + direction);
        }
    }

    return Questions;

});