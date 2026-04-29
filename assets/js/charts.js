// Chart JavaScript - assets/js/chart.js

// Chart configuration and utilities
const ChartManager = {
    defaultColors: [
        '#e5243b', '#dda63a', '#4c9f38', '#c5192d', '#ff3a21',
        '#26bde2', '#fcc30b', '#a21942', '#fd6925', '#dd1367',
        '#fd9d24', '#bf8b2e', '#3f7e44', '#0a97d9', '#56c02b',
        '#00689d', '#19486a'
    ],

    chartOptions: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 20,
                    usePointStyle: true,
                    font: {
                        size: 12,
                        family: "'Inter', system-ui, sans-serif"
                    }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleColor: '#fff',
                bodyColor: '#fff',
                borderColor: '#667eea',
                borderWidth: 1,
                cornerRadius: 8,
                displayColors: true
            }
        },
        animation: {
            duration: 1000,
            easing: 'easeInOutCubic'
        }
    },

    // Initialize SDG distribution chart
    initSDGChart: function(canvasId, data, definitions) {
        const ctx = document.getElementById(canvasId);
        if (!ctx || !data) return null;

        const labels = Object.keys(data).map(sdg => 
            definitions[sdg] && definitions[sdg].title ? definitions[sdg].title : sdg
        );
        
        const values = Object.values(data).map(item => item.work_count || item);
        
        const colors = Object.keys(data).map(sdg => 
            definitions[sdg] && definitions[sdg].color ? definitions[sdg].color : '#667eea'
        );

        const config = {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderWidth: 3,
                    borderColor: '#fff',
                    hoverBorderWidth: 4,
                    hoverOffset: 10
                }]
            },
            options: {
                ...this.chartOptions,
                cutout: '60%',
                plugins: {
                    ...this.chartOptions.plugins,
                    legend: {
                        ...this.chartOptions.plugins.legend,
                        position: 'right',
                        labels: {
                            ...this.chartOptions.plugins.legend.labels,
                            generateLabels: function(chart) {
                                const data = chart.data;
                                if (data.labels.length && data.datasets.length) {
                                    return data.labels.map((label, i) => {
                                        const dataset = data.datasets[0];
                                        const value = dataset.data[i];
                                        const percentage = ((value / dataset.data.reduce((a, b) => a + b, 0)) * 100).toFixed(1);
                                        
                                        return {
                                            text: `${label} (${value} - ${percentage}%)`,
                                            fillStyle: dataset.backgroundColor[i],
                                            strokeStyle: dataset.borderColor,
                                            lineWidth: dataset.borderWidth,
                                            hidden: false,
                                            index: i
                                        };
                                    });
                                }
                                return [];
                            }
                        }
                    },
                    tooltip: {
                        ...this.chartOptions.plugins.tooltip,
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${label}: ${value} works (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        };

        return new Chart(ctx, config);
    },

    // Initialize timeline chart
    initTimelineChart: function(canvasId, timelineData) {
        const ctx = document.getElementById(canvasId);
        if (!ctx || !timelineData) return null;

        const config = {
            type: 'line',
            data: {
                labels: timelineData.labels,
                datasets: timelineData.datasets.map((dataset, index) => ({
                    ...dataset,
                    borderColor: this.defaultColors[index] || '#667eea',
                    backgroundColor: (this.defaultColors[index] || '#667eea') + '20',
                    borderWidth: 3,
                    fill: false,
                    tension: 0.4,
                    pointRadius: 5,
                    pointHoverRadius: 8,
                    pointBackgroundColor: '#fff',
                    pointBorderWidth: 2
                }))
            },
            options: {
                ...this.chartOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Publications',
                            font: {
                                size: 14,
                                weight: 'bold'
                            }
                        },
                        grid: {
                            color: '#e9ecef'
                        },
                        ticks: {
                            font: {
                                size: 12
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Year',
                            font: {
                                size: 14,
                                weight: 'bold'
                            }
                        },
                        grid: {
                            color: '#e9ecef'
                        },
                        ticks: {
                            font: {
                                size: 12
                            }
                        }
                    }
                },
                plugins: {
                    ...this.chartOptions.plugins,
                    legend: {
                        ...this.chartOptions.plugins.legend,
                        position: 'top'
                    },
                    tooltip: {
                        ...this.chartOptions.plugins.tooltip,
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            title: function(tooltipItems) {
                                return `Year: ${tooltipItems[0].label}`;
                            },
                            label: function(context) {
                                return `${context.dataset.label}: ${context.parsed.y} publications`;
                            }
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        };

        return new Chart(ctx, config);
    },

    // Initialize bar chart for SDG comparison
    initBarChart: function(canvasId, data, definitions) {
        const ctx = document.getElementById(canvasId);
        if (!ctx || !data) return null;

        const labels = Object.keys(data).map(sdg => 
            definitions[sdg] && definitions[sdg].title ? definitions[sdg].title : sdg
        );
        
        const values = Object.values(data).map(item => item.work_count || item);
        
        const colors = Object.keys(data).map(sdg => 
            definitions[sdg] && definitions[sdg].color ? definitions[sdg].color : '#667eea'
        );

        const config = {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Publications',
                    data: values,
                    backgroundColor: colors.map(color => color + '80'),
                    borderColor: colors,
                    borderWidth: 2,
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: {
                ...this.chartOptions,
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Publications',
                            font: {
                                size: 14,
                                weight: 'bold'
                            }
                        },
                        grid: {
                            color: '#e9ecef'
                        },
                        ticks: {
                            font: {
                                size: 12
                            }
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 11
                            },
                            callback: function(value, index) {
                                const label = this.getLabelForValue(value);
                                return label.length > 20 ? label.substring(0, 20) + '...' : label;
                            }
                        }
                    }
                },
                plugins: {
                    ...this.chartOptions.plugins,
                    legend: {
                        display: false
                    },
                    tooltip: {
                        ...this.chartOptions.plugins.tooltip,
                        callbacks: {
                            title: function(tooltipItems) {
                                return tooltipItems[0].label;
                            },
                            label: function(context) {
                                return `Publications: ${context.parsed.x}`;
                            }
                        }
                    }
                }
            }
        };

        return new Chart(ctx, config);
    },

    // Initialize confidence score chart
    initConfidenceChart: function(canvasId, confidenceData) {
        const ctx = document.getElementById(canvasId);
        if (!ctx || !confidenceData) return null;

        const labels = Object.keys(confidenceData);
        const values = Object.values(confidenceData).map(v => (v * 100).toFixed(1));

        const config = {
            type: 'radar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Confidence Score (%)',
                    data: values,
                    borderColor: '#667eea',
                    backgroundColor: '#667eea20',
                    borderWidth: 3,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                ...this.chartOptions,
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            stepSize: 20,
                            font: {
                                size: 10
                            }
                        },
                        grid: {
                            color: '#e9ecef'
                        },
                        angleLines: {
                            color: '#e9ecef'
                        },
                        pointLabels: {
                            font: {
                                size: 11
                            }
                        }
                    }
                },
                plugins: {
                    ...this.chartOptions.plugins,
                    legend: {
                        display: false
                    },
                    tooltip: {
                        ...this.chartOptions.plugins.tooltip,
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: ${context.parsed.r}%`;
                            }
                        }
                    }
                }
            }
        };

        return new Chart(ctx, config);
    },

    // Initialize analysis components chart
    initComponentsChart: function(canvasId, componentsData) {
        const ctx = document.getElementById(canvasId);
        if (!ctx || !componentsData) return null;

        const labels = Object.keys(componentsData);
        const values = Object.values(componentsData);
        const colors = ['#e5243b', '#4c9f38', '#26bde2', '#fcc30b'];

        const config = {
            type: 'polarArea',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors.map(color => color + '60'),
                    borderColor: colors,
                    borderWidth: 2
                }]
            },
            options: {
                ...this.chartOptions,
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 1,
                        ticks: {
                            stepSize: 0.2,
                            font: {
                                size: 10
                            },
                            callback: function(value) {
                                return (value * 100).toFixed(0) + '%';
                            }
                        },
                        grid: {
                            color: '#e9ecef'
                        }
                    }
                },
                plugins: {
                    ...this.chartOptions.plugins,
                    tooltip: {
                        ...this.chartOptions.plugins.tooltip,
                        callbacks: {
                            label: function(context) {
                                const percentage = (context.parsed.r * 100).toFixed(1);
                                return `${context.label}: ${percentage}%`;
                            }
                        }
                    }
                }
            }
        };

        return new Chart(ctx, config);
    },

    // Utility function to resize charts
    resizeCharts: function() {
        Chart.helpers.each(Chart.instances, function(instance) {
            instance.resize();
        });
    },

    // Utility function to update chart data
    updateChartData: function(chart, newData) {
        if (!chart || !newData) return;

        chart.data.datasets[0].data = newData.values;
        chart.data.labels = newData.labels;
        chart.update('active');
    },

    // Export chart as image
    exportChart: function(chartInstance, filename = 'chart.png') {
        if (!chartInstance) return;

        const url = chartInstance.toBase64Image();
        const link = document.createElement('a');
        link.download = filename;
        link.href = url;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    },

    // Destroy all charts
    destroyAllCharts: function() {
        Chart.helpers.each(Chart.instances, function(instance) {
            instance.destroy();
        });
    }
};

// Global chart instances storage
window.chartInstances = {};

// Initialize charts when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Check if chart data is available
    if (typeof sdgData !== 'undefined' && typeof sdgDefinitions !== 'undefined') {
        // Initialize SDG distribution chart
        const sdgChart = ChartManager.initSDGChart('sdgChart', sdgData, sdgDefinitions);
        if (sdgChart) {
            window.chartInstances.sdgChart = sdgChart;
        }

        // Initialize bar chart if container exists
        const barChart = ChartManager.initBarChart('sdgBarChart', sdgData, sdgDefinitions);
        if (barChart) {
            window.chartInstances.sdgBarChart = barChart;
        }
    }

    // Initialize timeline chart if data is available
    if (typeof timelineData !== 'undefined') {
        const timelineChart = ChartManager.initTimelineChart('timelineChart', timelineData);
        if (timelineChart) {
            window.chartInstances.timelineChart = timelineChart;
        }
    }

    // Initialize confidence chart if data is available
    if (typeof confidenceData !== 'undefined') {
        const confidenceChart = ChartManager.initConfidenceChart('confidenceChart', confidenceData);
        if (confidenceChart) {
            window.chartInstances.confidenceChart = confidenceChart;
        }
    }

    // Initialize components chart if data is available
    if (typeof componentsData !== 'undefined') {
        const componentsChart = ChartManager.initComponentsChart('componentsChart', componentsData);
        if (componentsChart) {
            window.chartInstances.componentsChart = componentsChart;
        }
    }

    // Handle window resize
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            ChartManager.resizeCharts();
        }, 100);
    });

    // Chart export functionality
    window.exportChart = function(chartName, filename) {
        const chart = window.chartInstances[chartName];
        if (chart) {
            ChartManager.exportChart(chart, filename);
        }
    };

    console.log('Chart Manager initialized successfully');
});

// Export ChartManager for global access
window.ChartManager = ChartManager;