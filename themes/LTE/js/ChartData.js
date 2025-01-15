var options = {
    series: [44, 55, 67, 83],
    chart: {
        height: 300,
        type: 'radialBar',
    },
    plotOptions: {
        radialBar: {
            dataLabels: {
                name: {
                    fontSize: '22px',
                },
                value: {
                    fontSize: '16px',
                },
                total: {
                    show: true,
                    label: 'Total',
                    formatter: function (w) {
                        // By default this function returns the average of all series. The below is just an example to show the use of custom formatter function
                        return 249
                    }
                }
            }
        }
    },
    labels: ['Apples', 'Oranges', 'Bananas', 'Berries'],
};

var chart = new ApexCharts(document.querySelector("#chart11"), options);
chart.render();
//------------------------------------------------------------------------



var options = {
    series: [{
        name: 'Sales Inquiry',
        data: [45,34,23,12,33,16,18,21,32,20,29,36]
    },
    ],
    chart: {
        height: 350,
        type: 'line'
    },
    dataLabels: {
        enabled: false
    },
    stroke: {
        curve: 'smooth'
    },
    xaxis: {
        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug',
                      'Sep', 'Oct', 'Nov', 'Dec']
    },
    tooltip: {
            enabled: true,
        },
};

var chart = new ApexCharts(document.querySelector("#sales_inquiries_linechart"), options);
chart.render();

//--------------------------------------------------------------------------


var options = {
    series: [{
        name: 'Sales Quotation',
        data: [50,40,20,30,25,35,45,55,65,50,40,45]
    },
    ],
    chart: {
        height: 350,
        type: 'line'
    },
    dataLabels: {
        enabled: false
    },
    stroke: {
        curve: 'smooth'
    },
    xaxis: {
        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug',
                      'Sep', 'Oct', 'Nov', 'Dec']
    },
    tooltip: {
            enabled: true,
        },
};

var chart = new ApexCharts(document.querySelector("#sales_quotations_linechart"), options);
chart.render();

//--------------------------------------------------------------------

var options = {
    series: [{
        name: 'Sales Order',
        data: [35,22,15,14,24,22,19,16,21,17,20,25]
    },
    ],
    chart: {
        height: 350,
        type: 'line'
    },
    dataLabels: {
        enabled: false
    },
    stroke: {
        curve: 'smooth'
    },
    xaxis: {
        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug',
                      'Sep', 'Oct', 'Nov', 'Dec']
    },
    tooltip: {
            enabled: true,
        },
};

var chart = new ApexCharts(document.querySelector("#sales_orders_linechart"), options);
chart.render();

//----------------------------------------------------------------------

var options = {
    series: [{
        name: 'Sales Dispatch',
        data: [30,20,34,15,25,35,52,22,32,42,57,36]
    },
    ],
    chart: {
        height: 350,
        type: 'line'
    },
    dataLabels: {
        enabled: false
    },
    stroke: {
        curve: 'smooth'
    },
    xaxis: {
        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug',
                      'Sep', 'Oct', 'Nov', 'Dec']
    },
    tooltip: {
            enabled: true,
        },
};

var chart = new ApexCharts(document.querySelector("#sales_dispatches_linechart"), options);
chart.render();

//----------------------------------------------------------------

var options = {
    series: [{
        name: 'Sales Invoice',
        data: [25,20,28,15,18,35,42,22,32,38,45,30]
    },
    ],
    chart: {
        height: 350,
        type: 'line'
    },
    dataLabels: {
        enabled: false
    },
    stroke: {
        curve: 'smooth'
    },
    xaxis: {
        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug',
                      'Sep', 'Oct', 'Nov', 'Dec']
    },
    tooltip: {
            enabled: true,
        },
};

var chart = new ApexCharts(document.querySelector("#sales_invoices_linechart"), options);
chart.render();

//--------------------------------------------------------------------------
var options = {
    series: [{
        type: 'column',
        data: [23, 11, 22, 27, 13, 22, 37, 21, 44, 22, 30]
    }],
    chart: {
        height: 150,
        type: 'line',
        stacked: false,
        toolbar: {
            show: false
        }
    },
    stroke: {
        width: [0, 2, 5],
        curve: 'smooth'
    },
    plotOptions: {
        bar: {
            columnWidth: '50%'
        }
    },

    fill: {
        opacity: [0.85, 0.25, 1],
        gradient: {
            inverseColors: false,
            shade: 'light',
            type: "vertical",
            opacityFrom: 0.85,
            opacityTo: 0.55,
            stops: [0, 100, 100, 100]
        }
    },
    markers: {
        size: 0
    },
    tooltip: {
        shared: false,
        intersect: false,
    },
    yaxis: {
        show: false,
        showAlways: false
    },
    xaxis: {
        labels: {
            show: false
        }

    },
    grid: {
        show: false
    }
};

var chart = new ApexCharts(document.querySelector("#chart14"), options);
chart.render();


var options = {
    series: [{
    name: 'PRODUCT A',
    data: [44, 55, 41, 67, 22, 43]
  }, {
    name: 'PRODUCT B',
    data: [13, 23, 20, 8, 13, 27]
  }, {
    name: 'PRODUCT C',
    data: [11, 17, 15, 15, 21, 14]
  }, {
    name: 'PRODUCT D',
    data: [21, 7, 25, 13, 22, 8]
  }],
    chart: {
    type: 'bar',
    height: 280,
    stacked: true,
    toolbar: {
      show: true
    },
    zoom: {
      enabled: true
    }
  },
  responsive: [{
    breakpoint: 480,
    options: {
      legend: {
        position: 'bottom',
        offsetX: -10,
        offsetY: 0
      }
    }
  }],
  plotOptions: {
    bar: {
      horizontal: false,
    },
  },
  xaxis: {
    type: 'datetime',
    categories: ['01/01/2011 GMT', '01/02/2011 GMT', '01/03/2011 GMT', '01/04/2011 GMT',
      '01/05/2011 GMT', '01/06/2011 GMT'
    ],
  },
  legend: {
    position: 'right',
    offsetY: 40
  },
  fill: {
    opacity: 1
  }
  };

  var chart = new ApexCharts(document.querySelector("#chartbar"), options);
  chart.render();
  
  
  //---------------------------------------------------------------------------
  
  
  