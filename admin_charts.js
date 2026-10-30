/**
 * Chart.js Visualization Engine
 * Project: Student Feedback and Course Evaluation System
 */

document.addEventListener('DOMContentLoaded', function () {
    const searchParams = window.location.search;
    const apiUrl = `get_analytics_data.php${searchParams}`;

    fetch(apiUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                console.warn('Analytics API error:', data.message);
                return;
            }

            const themePrimary = getComputedStyle(document.documentElement).getPropertyValue('--theme-secondary').trim() || '#1a4a82';
            const themeDark = getComputedStyle(document.documentElement).getPropertyValue('--theme-primary').trim() || '#0a2240';

            // 1. BAR CHART: Faculty Performance Comparison
            const facultyCtx = document.getElementById('facultyBarChart');
            if (facultyCtx) {
                new Chart(facultyCtx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: data.faculty.labels.length > 0 ? data.faculty.labels : ['No Faculty Data'],
                        datasets: [{
                            label: 'Average Appraisal Rating (out of 5.0)',
                            data: data.faculty.scores.length > 0 ? data.faculty.scores : [0],
                            backgroundColor: themePrimary,
                            borderColor: themeDark,
                            borderWidth: 1,
                            borderRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 5.0,
                                ticks: { stepSize: 1.0 }
                            }
                        },
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            }

            // 2. PIE / DOUGHNUT CHART: Student Satisfaction Distribution
            const satisfactionCtx = document.getElementById('satisfactionPieChart');
            if (satisfactionCtx) {
                const totalSatisfaction = data.satisfaction.counts.reduce((a, b) => a + b, 0);
                new Chart(satisfactionCtx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: data.satisfaction.labels,
                        datasets: [{
                            data: totalSatisfaction > 0 ? data.satisfaction.counts : [1, 0, 0, 0],
                            backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444'],
                            borderWidth: 2,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom' }
                        }
                    }
                });
            }

            // 3. RADAR / BAR CHART: 7 Academic Categories Performance
            const categoryCtx = document.getElementById('categoryBarChart');
            if (categoryCtx) {
                new Chart(categoryCtx.getContext('2d'), {
                    type: 'radar',
                    data: {
                        labels: data.categories.labels,
                        datasets: [{
                            label: 'Average Score (out of 5.0)',
                            data: data.categories.scores,
                            fill: true,
                            backgroundColor: 'rgba(2, 132, 199, 0.2)',
                            borderColor: '#0284c7',
                            pointBackgroundColor: '#0a2240',
                            pointBorderColor: '#fff',
                            pointHoverBackgroundColor: '#fff',
                            pointHoverBorderColor: '#0a2240'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            r: {
                                angleLines: { display: true },
                                suggestedMin: 0,
                                suggestedMax: 5.0
                            }
                        }
                    }
                });
            }

            // 4. LINE CHART: Feedback Submissions Timeline Trend
            const trendCtx = document.getElementById('trendLineChart');
            if (trendCtx) {
                new Chart(trendCtx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: data.trend.labels.length > 0 ? data.trend.labels : ['Recent'],
                        datasets: [{
                            label: 'Submissions Count',
                            data: data.trend.counts.length > 0 ? data.trend.counts : [0],
                            fill: true,
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderColor: '#10b981',
                            tension: 0.35,
                            borderWidth: 2,
                            pointRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { precision: 0 }
                            }
                        },
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            }
        })
        .catch(err => {
            console.error('Failed to load chart metrics:', err);
        });
});

