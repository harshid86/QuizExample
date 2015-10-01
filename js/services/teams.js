app.service('Teams', function($http, httpPost, $q) {
     
    var Teams = function () {
        this.baseUrl = "/index.php/ajax";

        this.list = [];

        this.load = function() {
            var deferred = $q.defer();

            self = this;
            $http.get(this.baseUrl + "/teams")
                .success(function(response) {
                    self.list = response;
                    
                    deferred.resolve();
                })
                .error(function() {
                    deferred.reject();
                });

            
            return deferred.promise;
        }

        this.addTeam = function(teamName) {
            return httpPost.send(this.baseUrl + "/teams/add", {
                name: teamName
            });
        }

        this.updateTeam = function(id, teamName) {
            return httpPost.send(this.baseUrl + "/team/" + id + "/edit", {
                name: teamName
            });
        }

        this.getTeam = function(id) {
            return $http.get(this.baseUrl + "/team/" + id);
        }

        this.deleteTeam = function(id) {
            return $http.get(this.baseUrl + "/team/" + id + "/delete");
        }
    }

    return Teams;

});