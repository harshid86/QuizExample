app.service('Counter', function($timeout, $log){
     
    this.timeoutHandle = null;

    this.countdown = 0;

    this.start = function(time, callback) {
        if (this.timeoutHandle != null) {
            $log.warn("There is already a counter running!");
            return;
        }

        $log.info("Starting counter");

        this.countdown = time;

        timeMs = (time * 1000);

        self = this;
        this.timeoutHandle = $timeout(function() {
             self.cancel();
             callback();
        }, timeMs);

        return time;
    };

    this.cancel = function() {
        this.countdown = 0;
        this.timeoutHandle = null;
    };

    this.stop = function() {
        if (this.timeoutHandle == null) {
            $log.warn("There isnt a counter running to stop!");
            return;
        }

        $log.info("Stopping counter");

        this.cancel();
        $timeout.cancel(this.timeoutHandle);

        return 0;
    };

});