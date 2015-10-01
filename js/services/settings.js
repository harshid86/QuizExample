app.service('Settings', function($http, httpPost, $q) {
     
    var baseUrl = "/index.php/ajax";

    this.data = undefined;

    this.load = function() {
        var deferred = $q.defer();

        self = this;
        $http.get(baseUrl + "/settings")
            .success(function(response) {
                self.data = response;
                
                deferred.resolve();
            })
            .error(function() {
                deferred.reject();
            });

        
        return deferred.promise;
    }

    this.get = function() {
        return this.data;
    }

    this.save = function(settings) {
        return httpPost.send(baseUrl + "/settings", settings);
    }
});