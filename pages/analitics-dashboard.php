<?php
$page_title = 'Analytics Dashboard';
$page_description = 'Visualize SDG distribution, researcher contributions, and publication trends on the Wizdam AI analytics dashboard.';
?>
<div class="page-header">
    <div class="container">
        <div class="section-label">Data Analytics</div>
        <h1 class="section-title">Analytics Dashboard</h1>
        <p class="section-subtitle">Real-time visualization of SDG research distribution and contribution trends.</p>
    </div>
</div>

<section class="section">
    <div class="container">

        <div class="alert alert-warning" style="margin-bottom:1.5rem;">
            <i class="fas fa-database"></i>
            <span><strong>TODO:</strong> Dashboard akan menampilkan data real dari SQLite setelah <code>feat/sqlite-setup</code> selesai. Saat ini menampilkan chart placeholder.</span>
        </div>

        <!-- Stats Overview -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:2rem;">
            <div class="stat-card reveal">
                <span class="stat-number">127</span>
                <span class="stat-label">Researchers Analyzed</span>
            </div>
            <div class="stat-card reveal" style="transition-delay:100ms;">
                <span class="stat-number">3,842</span>
                <span class="stat-label">Works Classified</span>
            </div>
            <div class="stat-card reveal" style="transition-delay:200ms;">
                <span class="stat-number">17</span>
                <span class="stat-label">SDGs Covered</span>
            </div>
            <div class="stat-card reveal" style="transition-delay:300ms;">
                <span class="stat-number">89%</span>
                <span class="stat-label">Avg Confidence</span>
            </div>
            <div class="stat-card reveal" style="transition-delay:400ms;">
                <span class="stat-number">248</span>
                <span class="stat-label">Active Contributors</span>
            </div>
        </div>

        <!-- Charts Grid -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2rem;" class="charts-section reveal">
            <div class="chart-container">
                <h4 style="margin-bottom:1rem;"><i class="fas fa-chart-pie" style="color:var(--brand,#ff5627);"></i> SDG Distribution</h4>
                <canvas id="sdgDistChart" height="280"></canvas>
            </div>
            <div class="chart-container">
                <h4 style="margin-bottom:1rem;"><i class="fas fa-chart-bar" style="color:var(--brand,#ff5627);"></i> Top SDGs by Work Count</h4>
                <canvas id="sdgBarChart" height="280"></canvas>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;" class="charts-section reveal">
            <div class="chart-container">
                <h4 style="margin-bottom:1rem;"><i class="fas fa-chart-line" style="color:var(--brand,#ff5627);"></i> Monthly Analyses Trend</h4>
                <canvas id="trendChart" height="220"></canvas>
            </div>
            <div class="chart-container">
                <h4 style="margin-bottom:1rem;"><i class="fas fa-users" style="color:var(--brand,#ff5627);"></i> Contributor Type Distribution</h4>
                <canvas id="contribChart" height="220"></canvas>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart === 'undefined') return;

    const SDG_COLORS = ['#E5243B','#DDA63A','#4C9F38','#C5192D','#FF3A21','#26BDE2','#FCC30B','#A21942','#FD6925','#DD1367','#FD9D24','#BF8B2E','#3F7E44','#0A97D9','#56C02B','#00689D','#19486A'];
    const SDG_LABELS = ['SDG1','SDG2','SDG3','SDG4','SDG5','SDG6','SDG7','SDG8','SDG9','SDG10','SDG11','SDG12','SDG13','SDG14','SDG15','SDG16','SDG17'];

    // Placeholder data
    const sdgData = [45,23,87,62,34,56,19,41,73,28,66,38,92,15,54,47,29];

    new Chart(document.getElementById('sdgDistChart'), {
        type: 'doughnut',
        data: { labels: SDG_LABELS, datasets: [{ data: sdgData, backgroundColor: SDG_COLORS, borderWidth: 2, borderColor: '#fff' }] },
        options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'right', labels:{ font:{size:10}, padding:6 } } } }
    });

    new Chart(document.getElementById('sdgBarChart'), {
        type: 'bar',
        data: {
            labels: SDG_LABELS,
            datasets: [{ label:'Works', data: sdgData, backgroundColor: SDG_COLORS, borderWidth: 0, borderRadius: 6 }]
        },
        options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{display:false} }, scales:{ x:{ticks:{font:{size:10}}}, y:{ticks:{font:{size:11}}} } }
    });

    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: ['Nov','Dec','Jan','Feb','Mar','Apr','May'],
            datasets: [{ label:'Analyses', data: [12,18,25,31,42,38,55], borderColor:'#ff5627', backgroundColor:'rgba(255,86,39,.1)', fill:true, tension:.4, pointBackgroundColor:'#ff5627', borderWidth:2 }]
        },
        options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{ y:{ticks:{font:{size:11}}} } }
    });

    new Chart(document.getElementById('contribChart'), {
        type: 'doughnut',
        data: {
            labels: ['Active Contributor','Relevant Contributor','Discutor','Not Relevant'],
            datasets: [{ data: [248,165,89,42], backgroundColor:['#4c9f38','#26bde2','#fcc30b','#94a3b8'], borderWidth: 2, borderColor: '#fff' }]
        },
        options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'bottom', labels:{font:{size:11},padding:10} } } }
    });
});
</script>
