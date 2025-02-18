<?php
require_once __DIR__ . '/../analytics/UserAnalytics.php';

class ProfileSection {
    private $analytics;

    public function __construct() {
        $this->analytics = new UserAnalytics();
    }

    public function render() {
        $stats = $this->analytics->getFormattedStats();
        ob_start();
        ?>
        <div class="dashboard-container">
            <!-- Background Texture -->
            <div class="texture-overlay"></div>

            <!-- Header -->
            <div class="dashboard-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0 h4 fw-semibold text-gradient">Analytics Dashboard</h3>
                    <div class="header-actions">
                        <button type="button" class="btn btn-sm btn-light-subtle" onclick="refreshDashboard()">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                        <button type="button" class="btn btn-sm btn-light-subtle ms-2" onclick="exportAnalytics()">
                            <i class="bi bi-download"></i> Export
                        </button>
                    </div>
                </div>
                <div class="date-range-selector mt-3">
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-light active" data-range="day">Today</button>
                        <button type="button" class="btn btn-light" data-range="week">Week</button>
                        <button type="button" class="btn btn-light" data-range="month">Month</button>
                        <button type="button" class="btn btn-light" data-range="custom">
                            <i class="bi bi-calendar3"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Analytics Content -->
            <div class="analytics-content">
                <div class="p-4">
                    <div class="row g-4">
                        <!-- Overview Cards -->
                        <div class="col-12">
                            <div class="row g-4">
                                <div class="col-xl-3 col-md-6">
                                    <div class="stat-card primary">
                                        <div class="card-body position-relative">
                                            <div class="card-bg-icon">
                                                <i class="bi bi-clock"></i>
                                            </div>
                                            <h6>Today's Activity</h6>
                                            <div class="d-flex align-items-baseline">
                                                <h3 class="display-4 mb-0 me-2"><?php echo $stats['timeSpent']['today']; ?></h3>
                                                <small>hours</small>
                                            </div>
                                            <div class="mt-3">
                                                <?php echo $stats['fileActivity']['dailyEdits'][date('Y-m-d')] ?? 0; ?> edits today
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-md-6">
                                    <div class="stat-card success">
                                        <div class="card-body">
                                            <div class="card-bg-icon">
                                                <i class="bi bi-calendar-check"></i>
                                            </div>
                                            <h6>Current Streak</h6>
                                            <div class="d-flex align-items-center">
                                                <h3 class="display-4 mb-0 me-2"><?php echo $stats['productivity']['currentStreak']; ?></h3>
                                                <div>
                                                    <i class="bi bi-calendar-check"></i>
                                                    <small>days</small>
                                                </div>
                                            </div>
                                            <div class="mt-2">
                                                Longest: <?php echo $stats['productivity']['longestStreak']; ?> days
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-md-6">
                                    <div class="stat-card info">
                                        <div class="card-body">
                                            <div class="card-bg-icon">
                                                <i class="bi bi-files"></i>
                                            </div>
                                            <h6>Total Files</h6>
                                            <div class="d-flex align-items-baseline">
                                                <h3 class="display-4 mb-0 me-2"><?php echo $stats['fileActivity']['totalFiles']; ?></h3>
                                                <small>files</small>
                                            </div>
                                            <div class="mt-3 d-flex align-items-center gap-3">
                                                <span class="badge">
                                                    <i class="bi bi-markdown"></i>
                                                    <?php echo $stats['fileActivity']['fileTypes']['md']; ?> md
                                                </span>
                                                <span class="badge">
                                                    <i class="bi bi-file-text"></i>
                                                    <?php echo $stats['fileActivity']['fileTypes']['txt']; ?> txt
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-md-6">
                                    <div class="stat-card warning">
                                        <div class="card-body">
                                            <div class="card-bg-icon">
                                                <i class="bi bi-pencil"></i>
                                            </div>
                                            <h6>Total Edits</h6>
                                            <div class="d-flex align-items-baseline">
                                                <h3 class="display-4 mb-0 me-2"><?php echo $stats['fileActivity']['totalEdits']; ?></h3>
                                                <small>edits</small>
                                            </div>
                                            <div class="mt-3">
                                                <i class="bi bi-pencil"></i>
                                                <?php echo number_format($stats['fileActivity']['wordCount']); ?> words
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Activity Over Time -->
                        <div class="col-xl-8">
                            <div class="analytics-card">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h4 class="card-title">Activity Timeline</h4>
                                        <div class="card-actions">
                                            <button type="button" class="btn btn-sm btn-icon" onclick="toggleFullscreen(this)">
                                                <i class="bi bi-fullscreen"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container" style="height: 300px;">
                                        <canvas id="activityTimeline"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Peak Hours -->
                        <div class="col-xl-4">
                            <div class="analytics-card h-100">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h4 class="card-title">Peak Hours</h4>
                                        <div class="card-actions">
                                            <button type="button" class="btn btn-sm btn-icon" onclick="toggleFullscreen(this)">
                                                <i class="bi bi-fullscreen"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container" style="height: 300px;">
                                        <canvas id="peakHoursChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Most Active Files -->
                        <div class="col-xl-6">
                            <div class="analytics-card">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h4 class="card-title">Most Active Files</h4>
                                        <div class="card-actions">
                                            <button type="button" class="btn btn-sm btn-icon" onclick="toggleTableView(this)">
                                                <i class="bi bi-grid"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>File</th>
                                                    <th class="text-center">Edits</th>
                                                    <th>Last Modified</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($stats['fileActivity']['mostActive'] as $file): ?>
                                                <tr class="animate-row">
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <?php if (str_ends_with($file['filename'], '.md')): ?>
                                                                <i class="bi bi-markdown text-primary me-2"></i>
                                                            <?php else: ?>
                                                                <i class="bi bi-file-text text-secondary me-2"></i>
                                                            <?php endif; ?>
                                                            <?php echo htmlspecialchars($file['filename']); ?>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge badge-soft-primary">
                                                            <?php echo $file['editCount']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center text-secondary">
                                                            <i class="bi bi-clock me-1"></i>
                                                            <?php echo date('M d, H:i', $file['lastEdit']); ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        <div class="col-xl-6">
                            <div class="analytics-card">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h4 class="card-title">Recent Activity</h4>
                                        <div class="activity-filter">
                                            <select class="form-select form-select-sm">
                                                <option value="all">All Actions</option>
                                                <option value="create">Created</option>
                                                <option value="edit">Edited</option>
                                                <option value="delete">Deleted</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <tbody>
                                                <?php foreach ($stats['fileActivity']['recentEdits'] as $activity): ?>
                                                <tr class="animate-row" data-action="<?php echo $activity['action']; ?>">
                                                    <td style="width: 45px;">
                                                        <?php 
                                                            $actionIcon = match($activity['action']) {
                                                                'create' => '<i class="bi bi-plus-circle text-success"></i>',
                                                                'edit' => '<i class="bi bi-pencil text-primary"></i>',
                                                                'delete' => '<i class="bi bi-trash text-danger"></i>',
                                                                default => '<i class="bi bi-arrow-right text-secondary"></i>'
                                                            };
                                                            echo $actionIcon;
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <?php if ($activity['extension'] === 'md'): ?>
                                                                <i class="bi bi-markdown text-primary me-2"></i>
                                                            <?php else: ?>
                                                                <i class="bi bi-file-text text-secondary me-2"></i>
                                                            <?php endif; ?>
                                                            <?php echo htmlspecialchars($activity['filename']); ?>
                                                        </div>
                                                        <small class="text-secondary">
                                                            <?php echo ucfirst($activity['action']); ?>
                                                        </small>
                                                    </td>
                                                    <td class="text-end">
                                                        <div class="d-flex align-items-center justify-content-end text-secondary">
                                                            <i class="bi bi-clock me-1"></i>
                                                            <?php echo date('M d, H:i', $activity['timestamp']); ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            /* Dashboard Container */
            .dashboard-container {
                position: relative;
                min-height: 100vh;
                background: linear-gradient(135deg, var(--bg-white), var(--bg-subtle));
                overflow-x: hidden;
            }

            /* Texture Overlay */
            .texture-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                pointer-events: none;
                opacity: 0.4;
                background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23000000' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            }

            /* Dashboard Header */
            .dashboard-header {
                padding: 1.5rem 2rem;
                background: rgba(255, 255, 255, 0.8);
                backdrop-filter: blur(10px);
                border-bottom: 1px solid var(--border);
                position: sticky;
                top: 0;
                z-index: 1000;
            }

            .text-gradient {
                background: linear-gradient(135deg, var(--primary-main), var(--primary-dark));
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }

            /* Stat Cards */
            .stat-card {
                border-radius: 1rem;
                border: none;
                overflow: hidden;
                transition: var(--transition);
                background: rgba(255, 255, 255, 0.9);
                backdrop-filter: blur(10px);
                box-shadow: var(--shadow-md);
            }

            .stat-card:hover {
                transform: translateY(-4px);
            }

            .stat-card .card-body {
                padding: 1.5rem;
            }

            .card-bg-icon {
                position: absolute;
                top: 1rem;
                right: 1rem;
                font-size: 3rem;
                opacity: 0.1;
                transform: rotate(-15deg);
                transition: var(--transition);
            }

            .stat-card:hover .card-bg-icon {
                transform: rotate(0) scale(1.1);
                opacity: 0.15;
            }

            .stat-card h6 {
                color: var(--text-secondary);
                font-weight: 500;
                margin-bottom: 0.75rem;
            }

            /* Analytics Cards */
            .analytics-card {
                border-radius: 1rem;
                border: none;
                background: rgba(255, 255, 255, 0.9);
                backdrop-filter: blur(10px);
                box-shadow: var(--shadow-md);
                transition: var(--transition);
            }

            .analytics-card:hover {
                transform: translateY(-2px);
            }

            .analytics-card .card-header {
                background: transparent;
                border-bottom: 1px solid var(--border);
                padding: 1.25rem 1.5rem;
            }

            .analytics-card .card-title {
                font-size: 1rem;
                font-weight: 600;
                color: var(--text-primary);
                margin: 0;
            }

            /* Tables */
            .table {
                margin: 0;
            }

            .table th {
                background: var(--bg-subtle);
                font-weight: 600;
                color: var(--text-secondary);
                padding: 1rem;
                border-bottom: 2px solid var(--border);
            }

            .table td {
                padding: 1rem;
                vertical-align: middle;
                border-color: var(--border);
            }

            .animate-row {
                animation: fadeIn 0.3s ease-out;
            }

            /* Badges */
            .badge-soft-primary {
                background: var(--primary-light);
                color: var(--primary-main);
                font-weight: 500;
                padding: 0.5em 1em;
            }

            /* Buttons */
            .btn-light-subtle {
                background: rgba(255, 255, 255, 0.8);
                border: 1px solid var(--border);
                color: var(--text-secondary);
                transition: var(--transition);
            }

            .btn-light-subtle:hover {
                background: var(--bg-subtle);
                color: var(--text-primary);
                transform: translateY(-1px);
            }

            .btn-icon {
                width: 32px;
                height: 32px;
                padding: 0;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 0.5rem;
                background: transparent;
                color: var(--text-secondary);
                border: 1px solid var(--border);
            }

            .btn-icon:hover {
                background: var(--bg-subtle);
                color: var(--text-primary);
            }

            /* Card Variations */
            .stat-card.primary {
                background: linear-gradient(135deg, var(--primary-main), var(--primary-dark));
                color: white;
            }
            
            .stat-card.success {
                background: linear-gradient(135deg, #059669, #047857);
                color: white;
            }
            
            .stat-card.info {
                background: linear-gradient(135deg, #3B82F6, #2563EB);
                color: white;
            }
            
            .stat-card.warning {
                background: linear-gradient(135deg, #F59E0B, #D97706);
                color: white;
            }

            .stat-card.primary h6,
            .stat-card.success h6,
            .stat-card.info h6,
            .stat-card.warning h6 {
                color: rgba(255, 255, 255, 0.8);
            }

            /* Additional Animations */
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }

            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }

            .animate-spin {
                animation: spin 1s linear infinite;
            }

            /* Table Card View */
            .table-cards tr {
                display: block;
                margin-bottom: 1rem;
                background: var(--bg-subtle);
                border-radius: 0.5rem;
                padding: 1rem;
            }

            .table-cards td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                border: none;
                padding: 0.5rem 0;
            }

            .table-cards td:before {
                content: attr(data-label);
                font-weight: 600;
                margin-right: 1rem;
            }

            /* Fullscreen Mode */
            .analytics-card.fullscreen {
                background: var(--bg-white);
                padding: 2rem;
                animation: scaleIn 0.3s ease-out;
            }

            .analytics-card.fullscreen .chart-container {
                height: calc(100vh - 200px) !important;
            }

            @keyframes scaleIn {
                from { transform: scale(0.95); opacity: 0; }
                to { transform: scale(1); opacity: 1; }
            }

            /* Responsive Adjustments */
            @media (max-width: 768px) {
                .dashboard-header {
                    padding: 1rem;
                }

                .analytics-card {
                    margin-bottom: 1rem;
                }

                .stat-card .card-body {
                    padding: 1rem;
                }

                .table td, .table th {
                    padding: 0.75rem;
                }
            }
        </style>

        <!-- Dashboard Functionality -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                initializeCharts();
                initializeAnimations();
                setupEventListeners();
            });

            function initializeCharts() {
                // Activity Timeline Chart
                const timelineCtx = document.getElementById('activityTimeline').getContext('2d');
                const timelineData = <?php echo json_encode($stats['dailyStats']); ?>;
                
                const activityChart = new Chart(timelineCtx, {
                    type: 'line',
                    data: {
                        labels: Object.keys(timelineData).slice(-7),
                        datasets: [{
                            label: 'Hours Spent',
                            data: Object.values(timelineData).slice(-7).map(seconds => seconds / 3600),
                            borderColor: 'var(--primary-main)',
                            backgroundColor: 'var(--primary-light)',
                            fill: true,
                            tension: 0.4,
                            borderWidth: 2,
                            pointRadius: 4,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: 'var(--primary-main)',
                            pointHoverRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: {
                            duration: 1000,
                            easing: 'easeInOutQuart'
                        },
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: 'var(--bg-white)',
                                titleColor: 'var(--text-primary)',
                                bodyColor: 'var(--text-secondary)',
                                borderColor: 'var(--border)',
                                borderWidth: 1,
                                padding: 12,
                                boxPadding: 6,
                                usePointStyle: true,
                                callbacks: {
                                    label: (context) => `${context.parsed.y.toFixed(1)} hours`
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: value => value.toFixed(1) + 'h',
                                    color: 'var(--text-secondary)'
                                },
                                grid: {
                                    color: 'var(--border)',
                                    drawBorder: false
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    maxRotation: 0,
                                    color: 'var(--text-secondary)'
                                }
                            }
                        }
                    }
                });

                // Peak Hours Chart with enhanced styling
                const peakHoursCtx = document.getElementById('peakHoursChart').getContext('2d');
                const hourlyStats = <?php echo json_encode($stats['hourlyStats']); ?>;
                
                new Chart(peakHoursCtx, {
                    type: 'bar',
                    data: {
                        labels: Object.keys(hourlyStats).map(hour => 
                            `${hour.padStart(2, '0')}:00`
                        ),
                        datasets: [{
                            data: Object.values(hourlyStats).map(seconds => seconds / 3600),
                            backgroundColor: 'var(--primary-light)',
                            borderColor: 'var(--primary-main)',
                            borderWidth: 1.5,
                            borderRadius: 6,
                            hoverBackgroundColor: 'var(--primary-main)',
                            hoverBorderColor: 'var(--primary-dark)'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: {
                            duration: 1000,
                            easing: 'easeInOutQuart'
                        },
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: 'var(--bg-white)',
                                titleColor: 'var(--text-primary)',
                                bodyColor: 'var(--text-secondary)',
                                borderColor: 'var(--border)',
                                borderWidth: 1,
                                padding: 12,
                                boxPadding: 6,
                                usePointStyle: true,
                                callbacks: {
                                    label: (context) => `${context.parsed.y.toFixed(1)} hours`
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: value => value.toFixed(1) + 'h',
                                    color: 'var(--text-secondary)'
                                },
                                grid: {
                                    color: 'var(--border)',
                                    drawBorder: false
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    maxRotation: 0,
                                    color: 'var(--text-secondary)',
                                    font: {
                                        size: 11
                                    }
                                }
                            }
                        }
                    }
                });
            }

            function initializeAnimations() {
                // Animate stat cards on scroll
                const cards = document.querySelectorAll('.stat-card, .analytics-card');
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.style.opacity = '1';
                            entry.target.style.transform = 'translateY(0)';
                        }
                    });
                }, { threshold: 0.1 });

                cards.forEach(card => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'all 0.5s ease-out';
                    observer.observe(card);
                });
            }

            function setupEventListeners() {
                // Date range selector
                document.querySelectorAll('[data-range]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        document.querySelectorAll('[data-range]').forEach(b => b.classList.remove('active'));
                        btn.classList.add('active');
                        updateDateRange(btn.dataset.range);
                    });
                });

                // Activity filter
                document.querySelector('.activity-filter select').addEventListener('change', function() {
                    const filter = this.value;
                    document.querySelectorAll('[data-action]').forEach(row => {
                        row.style.display = (filter === 'all' || row.dataset.action === filter) ? '' : 'none';
                    });
                });
            }

            function toggleFullscreen(button) {
                const card = button.closest('.analytics-card');
                card.classList.toggle('fullscreen');
                if (card.classList.contains('fullscreen')) {
                    card.style.position = 'fixed';
                    card.style.top = '0';
                    card.style.left = '0';
                    card.style.width = '100vw';
                    card.style.height = '100vh';
                    card.style.zIndex = '1050';
                    button.innerHTML = '<i class="bi bi-fullscreen-exit"></i>';
                } else {
                    card.style.position = '';
                    card.style.top = '';
                    card.style.left = '';
                    card.style.width = '';
                    card.style.height = '';
                    card.style.zIndex = '';
                    button.innerHTML = '<i class="bi bi-fullscreen"></i>';
                }
            }

            function toggleTableView(button) {
                const table = button.closest('.analytics-card').querySelector('.table');
                table.classList.toggle('table-cards');
                button.innerHTML = table.classList.contains('table-cards') ? 
                    '<i class="bi bi-list"></i>' : 
                    '<i class="bi bi-grid"></i>';
            }

            function refreshDashboard() {
                const button = document.querySelector('button[onclick="refreshDashboard()"]');
                button.disabled = true;
                button.innerHTML = '<i class="bi bi-arrow-clockwise animate-spin"></i> Refreshing...';
                
                // Simulate refresh with animation
                setTimeout(() => {
                    location.reload();
                }, 500);
            }

            function exportAnalytics() {
                // Implementation for analytics export
                console.log('Exporting analytics...');
            }
        </script>
        <?php
        return ob_get_clean();
    }
}
