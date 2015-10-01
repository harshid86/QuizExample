app.factory('Options', function($http, httpPost, $q, Upload) {
     
    var Options = function () {
        var baseUrl = "/index.php/ajax";

        this.list = [];

        this.load = function(questionId) {
            var deferred = $q.defer();

            self = this;
            $http.get(baseUrl + "/question/" + questionId + "/options")
                .success(function(response) {
                    self.list = response;
                    
                    deferred.resolve();
                })
                .error(function() {
                    deferred.reject();
                });

            
            return deferred.promise;
        }

        this.addOption = function(questionId, option, score) {
            return httpPost.send(baseUrl + "/options/add", {
                option: option,
                score: score,
                questionId: questionId,
                isAnswer: false
            });
        }

        this.updateOption = function(id, option, score) {
            return httpPost.send(baseUrl + "/option/" + id + "/edit", {
                option: option,
                score: score
            });
        }

        this.getOption = function(id) {
            return $http.get(baseUrl + "/option/" + id);
        }

        this.deleteOption = function(id) {
            return $http.get(baseUrl + "/option/" + id + "/delete");
        }

        this.moveOption = function(id, direction) {

            return $http.get(baseUrl + "/option/" + id + "/move" + direction);
        }

        this.makeCorrectAnswer = function(id) {
            return $http.get(baseUrl + "/option/" + id + "/correctanswer");
        }

        this.uploadImage = function(id, file) {

            return Upload.upload({
                url: baseUrl + "/option/" + id + "/uploadimage",
                file: file
            });
        }
    }

    return Options;

});