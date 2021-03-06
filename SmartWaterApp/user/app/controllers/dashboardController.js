app.controller('DashboardCtrl', function($scope, $http, $rootScope, $location, $cookies)
{
  $rootScope.activetab = $location.path();
  $rootScope.pageTitle = 'Welcome';

  var userCPF = $cookies.get('userCPF');

  //set charts
  setCharts($scope, $http, userCPF);

  //set counters
  setGeneralTotal($rootScope, $http, userCPF);
  setMonthTotal($http,userCPF);
});

function setGeneralTotal($rootScope, $http, userCPF){
  if(typeof generalTotalTimeout !=='undefined'){
    window.clearInterval(generalTotalInterval);
    window.clearTimeout(generalTotalTimeout);
  }
  GeneralCounter = new FlipClock($('.generalTotal'), 1000000000, {
    clockFace: 'Counter'
  });
  $http.get('../api/report/totalByUser/'+userCPF).success(function(data) {
    GeneralCounter.setValue(data.total);
    $rootScope.lastUpdate = data.last_update;
  });

  generalTotalTimeout = setTimeout(function() {
    generalTotalInterval = setInterval(function() {
      $http.get('../api/report/totalByUser/'+userCPF).success(function(data) {
        GeneralCounter.setValue(data.total);
        $rootScope.lastUpdate = data.last_update;
      });
    }, 10000);
  });
}

function setMonthTotal($http, userCPF){
  if(typeof dashboardMonthTotalTimeout !=='undefined'){
    window.clearInterval(dashboardMonthTotalInterval);
    window.clearTimeout(dashboardMonthTotalTimeout);
  }
  monthCounter = new FlipClock($('.monthTotal'), 1000000000, {
    clockFace: 'Counter'
  });
  $http.get('../api/report/monthTotalByUser/'+userCPF).success(function(data) {
    monthCounter.setValue(data.total);
  });

  dashboardMonthTotalTimeout = setTimeout(function() {
    dashboardMonthTotalInterval = setInterval(function() {
      $http.get('../api/report/monthTotalByUser/'+userCPF).success(function(data) {
        monthCounter.setValue(data.total);
      });
    }, 10000);
  });
};

function setCharts($scope, $http, userCPF){
  $http.get('../api/report/lastYear/'+userCPF).success(function(days) {
    $scope.monthCharts = {
      options: {
        chart: {
          type: 'column'
        },
        title: {
          text: 'Consumption per month'
        },
        subtitle: {
          text: 'Last 12 months'
        }
      },
      xAxis: {
        categories: days.categories,
        crosshair: true
      },
      yAxis: {
        min: 0,
        title: {
          text: 'Flow (Liters)'
        },
        plotLines: [{
          label: {
            text: 'Average (' + days.average.toFixed(3) + ' liters)',
            align: 'left'
          },
          dashStyle: 'dash',
          color: 'green',
          value: days.average,
          width: '2',
          zIndex: 2
        }]
      },

      tooltip: {
        headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
        pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
        '<td style="padding:0"><b>{point.y:.1f} mm</b></td></tr>',
        footerFormat: '</table>',
        shared: true,
        useHTML: true,
        valueSuffix: 'liters'
      },
      plotOptions: {
        column: {
          pointPadding: 0.2,
          borderWidth: 0
        }
      },
      series: [{
        name: 'Water (Liters)',
        data: days.series
      }]
    }

  });

};
