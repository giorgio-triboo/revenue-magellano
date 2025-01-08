@extends('layouts.dashboard')

@section('title', 'Dashboard')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="space-y-8">
            <!-- Header -->
            <div class="md:flex md:items-center md:justify-between mb-4">
                <div class="min-w-0 flex-1">
                    <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                        Dashboard
                    </h2>
                </div>
            </div>


            <!-- Welcome Card -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-xl font-medium leading-6 text-gray-900">
                        Ciao {{ auth()->user()->first_name }}!
                    </h3>
                </div>
            </div>

            <!-- Quick Access Cards -->
            <div class="grid grid-cols-3 gap-4">
                <!-- Card Dashboard - Per tutti -->
                <a href="{{ route('dashboard') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100">
                            <i data-lucide="layout-grid" class="w-6 h-6 text-blue-600"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-xl font-medium text-gray-900">Dashboard</h3>
                            <p class="text-md text-gray-500">Panoramica generale</p>
                        </div>
                    </div>
                </a>

                <!-- Card Dashboard - Per tutti -->
                <a href="{{ route('profile.show') }}"
                    class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-gray-100">
                            <i data-lucide="user" class="w-6 h-6 text-gray-600"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-xl font-medium text-gray-900">Profilo Personale</h3>
                            <p class="text-md text-gray-500">Profilo Utente</p>
                        </div>
                    </div>
                </a>

                @if (auth()->user()->isAdmin())
                    <!-- Card Editori -->
                    <a href="{{ route('publishers.index') }}"
                        class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100">
                                <i data-lucide="book-open" class="w-6 h-6 text-green-600"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-xl font-medium text-gray-900">Lista Editori</h3>
                                <p class="text-md text-gray-500">Gestione editori</p>
                            </div>
                        </div>
                    </a>

                    <!-- Card Upload -->
                    <a href="{{ route('uploads.index') }}"
                        class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-100">
                                <i data-lucide="upload" class="w-6 h-6 text-purple-600"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-xl font-medium text-gray-900">Upload</h3>
                                <p class="text-md text-gray-500">Carica documenti</p>
                            </div>
                        </div>
                    </a>


                    <!-- Card Gestione Profili -->
                    <a href="{{ route('users.index') }}"
                        class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-red-100">
                                <i data-lucide="users" class="w-6 h-6 text-red-600"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-xl font-medium text-gray-900">Gestione Profili</h3>
                                <p class="text-md text-gray-500">Gestione utenti</p>
                            </div>
                        </div>
                    </a>
                @endif

                <!-- Card Consuntivi -->
                <a href="{{ route('statements.index') }}"
                    class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100">
                            <i data-lucide="file-text" class="w-6 h-6 text-yellow-600"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-xl font-medium text-gray-900">Consuntivi</h3>
                            <p class="text-md text-gray-500">Gestione consuntivi</p>
                        </div>
                    </div>
                </a>
            </div>



        </div>
    </div>

    @push('scripts')
        <script>
            function publisherRankings() {
                return {
                    chart: null,
                    loadData() {
                        const data = @json($charts['publisherRankings'] ?? []);

                        this.chart = new ApexCharts(document.querySelector("#publisherRankingChart"), {
                            chart: {
                                type: 'bar',
                                height: '100%',
                                toolbar: {
                                    show: true,
                                    tools: {
                                        download: true,
                                        selection: false,
                                        zoom: false,
                                        zoomin: false,
                                        zoomout: false,
                                        pan: false,
                                        reset: false
                                    }
                                }
                            },
                            plotOptions: {
                                bar: {
                                    horizontal: true,
                                    borderRadius: 4,
                                    barHeight: '70%',
                                    dataLabels: {
                                        position: 'bottom'
                                    }
                                }
                            },
                            series: [{
                                name: 'Fatturato',
                                data: data.map(item => item.value)
                            }],
                            xaxis: {
                                categories: data.map(item => item.publisher),
                                labels: {
                                    style: {
                                        fontSize: '12px',
                                        fontWeight: 500
                                    }
                                }
                            },
                            yaxis: {
                                labels: {
                                    formatter: function(value) {
                                        return '€ ' + value.toLocaleString('it-IT', {
                                            minimumFractionDigits: 0,
                                            maximumFractionDigits: 0
                                        });
                                    },
                                    style: {
                                        fontSize: '12px'
                                    }
                                }
                            },
                            fill: {
                                opacity: 0.9,
                                type: 'gradient',
                                gradient: {
                                    shade: 'dark',
                                    type: 'horizontal',
                                    shadeIntensity: 0.2,
                                    gradientToColors: undefined,
                                    inverseColors: true,
                                    opacityFrom: 0.85,
                                    opacityTo: 0.85,
                                    stops: [0, 100]
                                }
                            },
                            dataLabels: {
                                enabled: true,
                                formatter: function(value) {
                                    return '€ ' + value.toLocaleString('it-IT');
                                },
                                style: {
                                    fontWeight: 500
                                }
                            },
                            tooltip: {
                                y: {
                                    formatter: function(value) {
                                        return '€ ' + value.toLocaleString('it-IT', {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2
                                        });
                                    }
                                }
                            },
                            grid: {
                                borderColor: '#f1f1f1',
                                padding: {
                                    top: 10,
                                    right: 10,
                                    bottom: 10,
                                    left: 10
                                }
                            },
                            theme: {
                                mode: 'light',
                                palette: 'palette1'
                            }
                        });

                        this.chart.render();
                    }
                }
            }

            function totalRevenueChart() {
                return {
                    chart: null,
                    loadData() {
                        const data = @json($charts['monthlyRevenue'] ?? []);
                        const months = ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'];

                        this.chart = new ApexCharts(document.querySelector("#totalRevenueChart"), {
                            chart: {
                                type: 'area',
                                height: '100%',
                                toolbar: {
                                    show: true,
                                    tools: {
                                        download: true,
                                        selection: false,
                                        zoom: false,
                                        zoomin: false,
                                        zoomout: false,
                                        pan: false,
                                        reset: false
                                    }
                                }
                            },
                            series: [{
                                name: 'Fatturato Totale',
                                data: data.map(item => item.value)
                            }],
                            xaxis: {
                                categories: months,
                                labels: {
                                    rotateAlways: false,
                                    style: {
                                        fontSize: '12px',
                                        fontWeight: 500
                                    }
                                }
                            },
                            yaxis: {
                                labels: {
                                    formatter: function(value) {
                                        return '€ ' + value.toLocaleString('it-IT', {
                                            minimumFractionDigits: 0,
                                            maximumFractionDigits: 0
                                        });
                                    },
                                    style: {
                                        fontSize: '12px'
                                    }
                                }
                            },
                            stroke: {
                                curve: 'smooth',
                                width: 2
                            },
                            fill: {
                                type: 'gradient',
                                gradient: {
                                    shadeIntensity: 1,
                                    opacityFrom: 0.7,
                                    opacityTo: 0.9,
                                    stops: [0, 90, 100]
                                }
                            },
                            dataLabels: {
                                enabled: false
                            },
                            tooltip: {
                                y: {
                                    formatter: function(value) {
                                        return '€ ' + value.toLocaleString('it-IT', {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2
                                        });
                                    }
                                }
                            },
                            grid: {
                                borderColor: '#f1f1f1',
                                padding: {
                                    top: 10,
                                    right: 10,
                                    bottom: 10,
                                    left: 10
                                }
                            },
                            theme: {
                                mode: 'light',
                                palette: 'palette1'
                            }
                        });

                        this.chart.render();
                    }
                }
            }

            function singlePublisherChart() {
                return {
                    chart: null,
                    loadData() {
                        const data = @json($charts['publisherRevenue'] ?? []);
                        const months = ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'];

                        this.chart = new ApexCharts(document.querySelector("#singlePublisherChart"), {
                            chart: {
                                type: 'line',
                                height: '100%',
                                toolbar: {
                                    show: true,
                                    tools: {
                                        download: true,
                                        selection: false,
                                        zoom: false,
                                        zoomin: false,
                                        zoomout: false,
                                        pan: false,
                                        reset: false
                                    }
                                }
                            },
                            series: [{
                                name: 'Fatturato Mensile',
                                data: data.map(item => item.value)
                            }],
                            xaxis: {
                                categories: months,
                                labels: {
                                    rotateAlways: false,
                                    style: {
                                        fontSize: '12px',
                                        fontWeight: 500
                                    }
                                }
                            },
                            yaxis: {
                                labels: {
                                    formatter: function(value) {
                                        return '€ ' + value.toLocaleString('it-IT', {
                                            minimumFractionDigits: 0,
                                            maximumFractionDigits: 0
                                        });
                                    },
                                    style: {
                                        fontSize: '12px'
                                    }
                                }
                            },
                            stroke: {
                                curve: 'smooth',
                                width: 2
                            },
                            markers: {
                                size: 4,
                                strokeWidth: 2,
                                hover: {
                                    size: 6
                                }
                            },
                            dataLabels: {
                                enabled: false
                            },
                            tooltip: {
                                y: {
                                    formatter: function(value) {
                                        return '€ ' + value.toLocaleString('it-IT', {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2
                                        });
                                    }
                                }
                            },
                            grid: {
                                borderColor: '#f1f1f1',
                                padding: {
                                    top: 10,
                                    right: 10,
                                    bottom: 10,
                                    left: 10
                                }
                            },
                            theme: {
                                mode: 'light',
                                palette: 'palette1'
                            }
                        });

                        this.chart.render();
                    }
                }
            }
        </script>
    @endpush
@endsection
