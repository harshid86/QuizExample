app.service('httpPost', function($http){
    this._createPostObject = function(url, data) {
        return {
            method: 'POST',
            url: url,
            data: data,
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        };
    } 
    
    this.send = function(url, data) {
        return $http(this._createPostObject(url, data));
    }
});