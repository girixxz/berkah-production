{{-- Component: Customer Trend Chart --}}
<div x-data="{
    chart: null,
    loading: false,
    currentYear: new Date().getFullYear(),
    currentMonth: new Date().getMonth() + 1,
    selectedYear: new Date().getFullYear(),
    selectedMonth: new Date().getMonth() + 1,
    
    get monthYearLabel() {
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                           'July', 'August', 'September', 'October', 'November', 'December'];
        return monthNames[this.selectedMonth - 1] + ' ' + this.selectedYear;
    },
    
    init() {
        this.createChart();
        this.fetchData();
    },
    
    createChart() {
        const options = {
            series: [{
                name: 'Customers',
                data: []
            }],
            chart: {
                type: 'area',
                height: 320,
                toolbar: { show: false },
                zoom: { enabled: false }
            },
            dataLabels: { enabled: false },
            stroke: {
                curve: 'smooth',
                width: 2
            },
            colors: ['#10b981'],
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.4,
                    opacityTo: 0.1,
                }
            },
            xaxis: {
                categories: [],
                labels: {
                    style: { fontSize: '11px' }
                }
            },
            yaxis: {
                labels: {
                    style: { fontSize: '11px' },
                    formatter: (val) => Math.floor(val)
                },
                min: 0,
                forceNiceScale: true,
                tickAmount: 5
            },
            grid: {
                borderColor: '#f1f1f1',
                strokeDashArray: 4
            },
            tooltip: {
                y: {
                    formatter: (val) => val + ' Customers'
                }
            }
        };
        
        this.chart = new ApexCharts(this.$refs.chartContainer, options);
        this.chart.render();
    },
    
    previousMonth() {
        if (this.selectedMonth === 1) {
            this.selectedMonth = 12;
            this.selectedYear--;
        } else {
            this.selectedMonth--;
        }
        this.fetchData();
    },
    
    nextMonth() {
        if (this.selectedMonth === 12) {
            this.selectedMonth = 1;
            this.selectedYear++;
        } else {
            this.selectedMonth++;
        }
        this.fetchData();
    },
    
    resetToCurrentMonth() {
        this.selectedYear = this.currentYear;
        this.selectedMonth = this.currentMonth;
        this.fetchData();
    },
    
    fetchData() {
        this.loading = true;
        
        fetch('/owner/dashboard/chart/customer-trend?year=' + this.selectedYear + '&month=' + this.selectedMonth, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            this.chart.updateOptions({
                xaxis: { categories: data.labels },
                series: [{ data: data.values }]
            });
            this.loading = false;
        })
        .catch(error => {
            console.error('Error:', error);
            this.loading = false;
        });
    }
}" class="bg-white border border-gray-200 rounded-lg p-4 md:p-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-2">
        <h3 class="text-center md:text-left text-lg font-bold text-gray-900">Customer Per Month</h3>
        
        <div class="flex items-center justify-center sm:justify-start gap-2 sm:gap-3">
            <button @click="previousMonth()" class="p-1.5 bg-primary-light hover:bg-primary/10 rounded-md transition-colors">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            
            <span class="text-xs sm:text-sm font-medium text-gray-700 min-w-[110px] sm:min-w-[130px] text-center" x-text="monthYearLabel"></span>
            
            <button @click="nextMonth()" class="p-1.5 bg-primary-light hover:bg-primary/10 rounded-md transition-colors">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
            
            <button @click="resetToCurrentMonth()" class="px-2.5 py-1.5 sm:px-3 text-xs sm:text-sm font-medium text-white bg-primary hover:bg-primary-dark rounded-md transition-colors">
                Reset
            </button>
        </div>
    </div>
    
    {{-- Chart Container with Horizontal Scroll --}}
    <div class="relative">
        <div class="overflow-x-auto overflow-y-hidden">
            <div style="min-width: 600px;">
                <div x-ref="chartContainer"></div>
            </div>
        </div>
        
        {{-- Loading Overlay --}}
        <div x-show="loading" x-cloak class="absolute inset-0 bg-white/80 flex items-center justify-center rounded-lg">
            <div class="text-center">
                <svg class="animate-spin h-8 w-8 text-primary mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-sm text-gray-500">Loading...</p>
            </div>
        </div>
    </div>
</div>
