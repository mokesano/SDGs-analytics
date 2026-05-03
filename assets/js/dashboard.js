/**
 * dashboard.js — Chart.js helpers for SDG Analytics Dashboard
 * Used by pages/analitics-dashboard.php
 */

const SDG_COLORS = {
    'SDG1':'#E5243B','SDG2':'#DDA63A','SDG3':'#4C9F38','SDG4':'#C5192D',
    'SDG5':'#FF3A21','SDG6':'#26BDE2','SDG7':'#FCC30B','SDG8':'#A21942',
    'SDG9':'#FD6925','SDG10':'#DD1367','SDG11':'#FD9D24','SDG12':'#BF8B2E',
    'SDG13':'#3F7E44','SDG14':'#0A97D9','SDG15':'#56C02B','SDG16':'#00689D',
    'SDG17':'#19486A'
};

const SDG_TITLES = {
    'SDG1':'No Poverty','SDG2':'Zero Hunger','SDG3':'Good Health',
    'SDG4':'Quality Education','SDG5':'Gender Equality','SDG6':'Clean Water',
    'SDG7':'Clean Energy','SDG8':'Decent Work','SDG9':'Industry & Innovation',
    'SDG10':'Reduced Inequalities','SDG11':'Sustainable Cities',
    'SDG12':'Responsible Consumption','SDG13':'Climate Action',
    'SDG14':'Life Below Water','SDG15':'Life on Land',
    'SDG16':'Peace & Justice','SDG17':'Partnerships'
};

/**
 * Create a horizontal bar chart showing SDG distribution.
 */
function createSdgBarChart(canvasId, labels, data) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;
    const colors = labels.map(l => SDG_COLORS[l] || '#94a3b8');
    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels.map(l => l + ' ' + (SDG_TITLES[l] || '')),
            datasets: [{
                label: 'Karya',
                data,
                backgroundColor: colors,
                borderRadius: 4,
                borderSkipped: false,
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        title: t => t[0].label,
                        label: t => ' ' + t.raw + ' karya'
                    }
                }
            },
            scales: {
                x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(0,0,0,.05)' } },
                y: { grid: { display: false }, ticks: { font: { size: 11 } } }
            }
        }
    });
}

/**
 * Create a doughnut chart for contributor type distribution.
 */
function createContributorDoughnut(canvasId, labels, data) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;
    const COLORS = {
        'Active Contributor':'#4C9F38',
        'Relevant Contributor':'#26BDE2',
        'Discutor':'#FCC30B',
        'Not Relevant':'#e2e8f0'
    };
    return new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data,
                backgroundColor: labels.map(l => COLORS[l] || '#94a3b8'),
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: {
                legend: { position: 'bottom', labels: { padding: 16, font: { size: 12 } } },
                tooltip: { callbacks: { label: t => ' ' + t.label + ': ' + t.raw } }
            }
        }
    });
}

/**
 * Create a line chart for yearly publication trend.
 */
function createYearlyTrendChart(canvasId, labels, data) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;
    return new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Publikasi',
                data,
                fill: true,
                backgroundColor: 'rgba(255,86,39,.08)',
                borderColor: '#ff5627',
                pointBackgroundColor: '#ff5627',
                pointRadius: 4,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: t => ' ' + t.raw + ' publikasi' } }
            },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(0,0,0,.05)' } },
                x: { grid: { display: false } }
            }
        }
    });
}

/**
 * Animate stat counter from 0 to target.
 */
function animateCounter(el, target, duration = 1200) {
    const start = performance.now();
    const update = (now) => {
        const elapsed = now - start;
        const progress = Math.min(elapsed / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3); // ease-out cubic
        el.textContent = Math.round(eased * target).toLocaleString('id-ID');
        if (progress < 1) requestAnimationFrame(update);
    };
    requestAnimationFrame(update);
}

// Auto-animate counters with data-counter attribute
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-counter]').forEach(el => {
        const target = parseInt(el.dataset.counter, 10);
        if (!isNaN(target)) {
            const observer = new IntersectionObserver(entries => {
                if (entries[0].isIntersecting) {
                    animateCounter(el, target);
                    observer.disconnect();
                }
            }, { threshold: 0.5 });
            observer.observe(el);
        }
    });
});
