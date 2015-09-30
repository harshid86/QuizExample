app.directive('counter', function ($parse) {
    return {
        restrict: "E",
        link: function (scope, element, attrs) {

            element.pieChartCountDown(attrs.time, {});

            attrs.$observe('time', function(value){
                element.pieChartCountDown(value, {
                    color : '#7E0946',
                    background: '#FFFFF',
                    size : attrs.size,
                    border: 0,
                    infinite : false
                });
            });

        }
    };
});