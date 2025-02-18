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
        <div class="profile-section h-100">
            <!-- Header -->
            <div class="px-4 py-4 border-bottom" style="background: var(--bg-subtle);">
                <h3 class="mb-0 h4 fw-semibold">Analytics Dashboard</h3>
            </div>
            
            <!-- Analytics Content -->
            <div class="analytics-content overflow-auto" style="height: calc(100vh - 60px);">
                <div class="p-4">
                    <div class="row g-4">
                        <!-- Overview Cards -->
                        <div class="col-12">
                            <div class="row g-4">
                                <div class="col-xl-3 col-md-6">
                                    <div class="card border-0 shadow-md h-100 overflow-hidden" 
                                         style="background: linear-gradient(135deg, var(--primary-main), var(--primary-dark)); transition: var(--transition);">
                                        <div class="card-body position-relative">
                                            <div class="position-absolute top-0 end-0 opacity-10">
                                                <i class="bi bi-clock display-4"></i>
                                            </div>
                                            <h6 class="text-white opacity-75 mb-2">Today's Activity</h6>
                                            <div class="d-flex align-items-baseline">
                                                <h3 class="display-4 mb-0 me-2 text-white fw-bold"><?php echo $stats['timeSpent']['today']; ?></h3>
                                                <small class="text-white opacity-75">hours</small>
                                            </div>
                                            <div class="mt-3 text-white opacity-75">
                                                <?php echo $stats['fileActivity']['dailyEdits'][date('Y-m-d')] ?? 0; ?> edits today
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-md-6">
                                    <div class="card border-0 shadow-md h-100" style="transition: var(--transition);">
                                        <div class="card-body">
                                            <h6 class="text-secondary mb-2">Current Streak</h6>
                                            <div class="d-flex align-items-center">
                                                <h3 class="display-4 mb-0 me-2 fw-bold"><?php echo $stats['productivity']['currentStreak']; ?></h3>
                                                <div class="text-success">
                                                    <i class="bi bi-calendar-check"></i>
                                                    <small>days</small>
                                                </div>
                                            </div>
                                            <div class="mt-2 text-secondary">
                                                Longest: <?php echo $stats['productivity']['longestStreak']; ?> days
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-md-6">
                                    <div class="card border-0 shadow-md h-100" style="transition: var(--transition);">
                                        <div class="card-body">
                                            <h6 class="text-secondary mb-2">Total Files</h6>
                                            <div class="d-flex align-items-baseline">
                                                <h3 class="display-4 mb-0 me-2 fw-bold"><?php echo $stats['fileActivity']['totalFiles']; ?></h3>
                                                <small class="text-secondary">files</small>
                                            </div>
                                            <div class="mt-3 d-flex align-items-center gap-3">
                                                <span class="text-primary fw-medium">
                                                    <i class="bi bi-markdown"></i>
                                                    <?php echo $stats['fileActivity']['fileTypes']['md']; ?> md
                                                </span>
                                                <span class="text-secondary">
                                                    <i class="bi bi-file-text"></i>
                                                    <?php echo $stats['fileActivity']['fileTypes']['txt']; ?> txt
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-md-6">
                                    <div class="card border-0 shadow-md h-100" style="transition: var(--transition);">
                                        <div class="card-body">
                                            <h6 class="text-secondary mb-2">Total Edits</h6>
                                            <div class="d-flex align-items-baseline">
                                                <h3 class="display-4 mb-0 me-2 fw-bold"><?php echo $stats['fileActivity']['totalEdits']; ?></h3>
                                                <small class="text-secondary">edits</small>
                                            </div>
                                            <div class="mt-3 text-secondary">
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
                            <div class="card border-0 shadow-md" style="transition: var(--transition);">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h4 class="card-title h5 fw-semibold mb-0">Activity Timeline</h4>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-light active fw-medium" onclick="updateTimeline('week')">Week</button>
                                            <button type="button" class="btn btn-light fw-medium" onclick="updateTimeline('month')">Month</button>
                                        </div>
                                    </div>
                                    <div class="chart-container" style="height: 300px;">
                                        <canvas id="activityTimeline"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Peak Hours -->
                        <div class="col-xl-4">
                            <div class="card border-0 shadow-md h-100" style="transition: var(--transition);">
                                <div class="card-body">
                                    <h4 class="card-title h5 fw-semibold mb-4">Peak Activity Hours</h4>
                                    <div class="chart-container" style="height: 300px;">
                                        <canvas id="peakHoursChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Most Active Files -->
                        <div class="col-xl-6">
                            <div class="card border-0 shadow-md" style="transition: var(--transition);">
                                <div class="card-body">
                                    <h4 class="card-title h5 fw-semibold mb-4">Most Active Files</h4>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle">
                                            <thead>
                                                <tr>
                                                    <th class="border-bottom border-2">File</th>
                                                    <th class="text-center border-bottom border-2">Edits</th>
                                                    <th class="border-bottom border-2">Last Modified</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($stats['fileActivity']['mostActive'] as $file): ?>
                                                <tr>
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
                                                        <span class="badge bg-primary bg-opacity-10 text-primary px-2 rounded-pill">
                                                            <?php echo $file['editCount']; ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-secondary">
                                                        <?php echo date('M d, H:i', $file['lastEdit']); ?>
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
                            <div class="card border-0 shadow-md" style="transition: var(--transition);">
                                <div class="card-body">
                                    <h4 class="card-title h5 fw-semibold mb-4">Recent Activity</h4>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <tbody>
                                                <?php foreach ($stats['fileActivity']['recentEdits'] as $activity): ?>
                                                <tr>
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
                                                    <td class="text-end text-secondary">
                                                        <?php echo date('M d, H:i', $activity['timestamp']); ?>
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
            .card {
                border-radius: 0.75rem;
            }
            .card:hover {
                transform: translateY(-2px);
            }
            .table > :not(:first-child) {
                border-top: none;
            }
            .table td {
                padding: 1rem;
                border-color: var(--border);
            }
            .table th {
                padding: 1rem;
                font-weight: 600;
                color: var(--text-secondary);
                background: var(--bg-subtle);
            }
            .shadow-md {
                box-shadow: var(--shadow-md);
            }
        </style>

        <!-- Initialize Charts -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
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

                // Peak Hours Chart
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

                window.updateTimeline = function(range) {
                    const days = range === 'week' ? 7 : 30;
                    activityChart.data.labels = Object.keys(timelineData).slice(-days);
                    activityChart.data.datasets[0].data = Object.values(timelineData)
                        .slice(-days)
                        .map(seconds => seconds / 3600);
                    activityChart.update();

                    // Update button states
                    document.querySelectorAll('.btn-group .btn').forEach(btn => {
                        btn.classList.toggle('active', btn.innerText.toLowerCase() === range);
                    });
                };
            });
        </script>
        <?php
        return ob_get_clean();
    }
}
