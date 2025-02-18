<?php
class UserAnalytics {
    private $dataFile = 'analytics_data.json';
    private $data;
    private $lastActivityTime;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->data = [];
        $this->loadData();
        $this->cleanupOldSessions();
        $this->updateActiveInstance();
        $this->trackTime();
    }

    private function loadData() {
        try {
            if (file_exists($this->dataFile)) {
                $jsonData = file_get_contents($this->dataFile);
                if ($jsonData === false) {
                    throw new Exception("Failed to read analytics data file");
                }
                $this->data = json_decode($jsonData, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception("Invalid JSON in analytics data");
                }
            }
        } catch (Exception $e) {
            error_log("Analytics data error: " . $e->getMessage());
            $this->initializeDefaultData();
        }

        if (!is_array($this->data)) {
            $this->initializeDefaultData();
        }

        // Ensure all required arrays exist
        $this->ensureDataStructure();
        $this->lastActivityTime = time();
    }

    private function initializeDefaultData() {
        $this->data = [
            'timeSpent' => [
                'today' => 0,
                'yesterday' => 0,
                'week' => 0,
                'month' => 0,
                'lastUpdate' => time(),
                'dailyStats' => [],
                'hourlyStats' => array_fill(0, 24, 0)
            ],
            'activeInstances' => [
                'count' => 0,
                'sessions' => []
            ],
            'fileActivity' => [
                'totalFiles' => 0,
                'recentEdits' => [],
                'mostActive' => [],
                'fileTypes' => [
                    'md' => 0,
                    'txt' => 0
                ],
                'totalEdits' => 0,
                'wordCount' => 0,
                'characterCount' => 0,
                'dailyEdits' => []
            ],
            'productivity' => [
                'peakHours' => array_fill(0, 24, 0),
                'averageSessionLength' => 0,
                'longestSession' => 0,
                'lastActive' => time(),
                'streaks' => [
                    'current' => 0,
                    'longest' => 0,
                    'lastDate' => date('Y-m-d')
                ]
            ]
        ];
    }

    private function ensureDataStructure() {
        // Ensure activeInstances structure
        if (!isset($this->data['activeInstances']) || !is_array($this->data['activeInstances'])) {
            $this->data['activeInstances'] = ['count' => 0, 'sessions' => []];
        }
        if (!isset($this->data['activeInstances']['sessions']) || !is_array($this->data['activeInstances']['sessions'])) {
            $this->data['activeInstances']['sessions'] = [];
        }

        // Ensure other required arrays exist
        if (!isset($this->data['timeSpent']['dailyStats'])) {
            $this->data['timeSpent']['dailyStats'] = [];
        }
        if (!isset($this->data['timeSpent']['hourlyStats'])) {
            $this->data['timeSpent']['hourlyStats'] = array_fill(0, 24, 0);
        }
        if (!isset($this->data['productivity']['peakHours'])) {
            $this->data['productivity']['peakHours'] = array_fill(0, 24, 0);
        }
        if (!isset($this->data['fileActivity']['fileTypes'])) {
            $this->data['fileActivity']['fileTypes'] = ['md' => 0, 'txt' => 0];
        }
    }

    private function cleanupOldSessions() {
        $timeout = 30 * 60; // 30 minutes
        $now = time();
        
        if (!isset($this->data['activeInstances']['sessions'])) {
            $this->data['activeInstances']['sessions'] = [];
        }

        foreach ($this->data['activeInstances']['sessions'] as $id => $lastActivity) {
            if ($now - $lastActivity > $timeout) {
                unset($this->data['activeInstances']['sessions'][$id]);
            }
        }
        
        $this->data['activeInstances']['count'] = count($this->data['activeInstances']['sessions']);
        $this->saveData();
    }

    private function updateActiveInstance() {
        if (!isset($_SESSION['instance_id'])) {
            $_SESSION['instance_id'] = uniqid('', true);
            $_SESSION['session_start'] = time();
        }

        if (!isset($this->data['activeInstances']['sessions'])) {
            $this->data['activeInstances']['sessions'] = [];
        }

        $this->data['activeInstances']['sessions'][$_SESSION['instance_id']] = time();
        $this->data['activeInstances']['count'] = count($this->data['activeInstances']['sessions']);
        $this->saveData();
    }

    private function trackTime() {
        $currentTime = time();
        $lastUpdate = $this->data['timeSpent']['lastUpdate'];
        $timeDiff = min($currentTime - $lastUpdate, 300); // Cap at 5 minutes

        if ($timeDiff > 0) {
            // Update current day stats
            $today = date('Y-m-d');
            if (!isset($this->data['timeSpent']['dailyStats'][$today])) {
                $this->data['timeSpent']['dailyStats'][$today] = 0;
            }
            $this->data['timeSpent']['dailyStats'][$today] += $timeDiff;
            $this->data['timeSpent']['today'] += $timeDiff;

            // Update hourly stats
            $hour = (int)date('G');
            if (!isset($this->data['timeSpent']['hourlyStats'][$hour])) {
                $this->data['timeSpent']['hourlyStats'][$hour] = 0;
            }
            $this->data['timeSpent']['hourlyStats'][$hour] += $timeDiff;

            if (!isset($this->data['productivity']['peakHours'][$hour])) {
                $this->data['productivity']['peakHours'][$hour] = 0;
            }
            $this->data['productivity']['peakHours'][$hour] += $timeDiff;

            // Check for day change
            if (date('Y-m-d', $lastUpdate) !== $today) {
                $this->data['timeSpent']['yesterday'] = $this->data['timeSpent']['today'];
                $this->data['timeSpent']['today'] = 0;

                // Update streak
                $yesterday = date('Y-m-d', strtotime('-1 day'));
                if ($this->data['productivity']['streaks']['lastDate'] === $yesterday) {
                    $this->data['productivity']['streaks']['current']++;
                    if ($this->data['productivity']['streaks']['current'] > $this->data['productivity']['streaks']['longest']) {
                        $this->data['productivity']['streaks']['longest'] = $this->data['productivity']['streaks']['current'];
                    }
                } else {
                    $this->data['productivity']['streaks']['current'] = 1;
                }
                $this->data['productivity']['streaks']['lastDate'] = $today;
            }

            // Calculate weekly and monthly totals
            $this->updateTimeAggregates();
            
            // Update last activity time
            $this->data['timeSpent']['lastUpdate'] = $currentTime;
            $this->data['productivity']['lastActive'] = $currentTime;

            $this->saveData();
        }
    }

    private function updateTimeAggregates() {
        $weekTotal = 0;
        $monthTotal = 0;
        $now = time();
        foreach ($this->data['timeSpent']['dailyStats'] as $date => $time) {
            $dayTime = strtotime($date);
            if ($now - $dayTime <= 7 * 24 * 3600) {
                $weekTotal += $time;
            }
            if ($now - $dayTime <= 30 * 24 * 3600) {
                $monthTotal += $time;
            }
        }
        $this->data['timeSpent']['week'] = $weekTotal;
        $this->data['timeSpent']['month'] = $monthTotal;

        // Clean old stats
        $this->cleanOldStats();
    }

    private function cleanOldStats() {
        $cutoff = date('Y-m-d', strtotime('-30 days'));
        foreach ($this->data['timeSpent']['dailyStats'] as $date => $time) {
            if ($date < $cutoff) {
                unset($this->data['timeSpent']['dailyStats'][$date]);
            }
        }
    }

    private function saveData() {
        try {
            $this->ensureDataStructure();
            $jsonData = json_encode($this->data, JSON_PRETTY_PRINT);
            if ($jsonData === false) {
                throw new Exception("Failed to encode analytics data");
            }
            if (file_put_contents($this->dataFile, $jsonData, LOCK_EX) === false) {
                throw new Exception("Failed to write analytics data");
            }
        } catch (Exception $e) {
            error_log("Analytics save error: " . $e->getMessage());
        }
    }

    public function logFileActivity($filename, $action) {
        $timestamp = time();
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        // Update file type stats
        if ($action === 'create') {
            $this->data['fileActivity']['totalFiles']++;
            if (isset($this->data['fileActivity']['fileTypes'][$ext])) {
                $this->data['fileActivity']['fileTypes'][$ext]++;
            }
        }

        // Track total edits and updates
        if (in_array($action, ['create', 'edit'])) {
            $this->data['fileActivity']['totalEdits']++;
            
            $today = date('Y-m-d');
            if (!isset($this->data['fileActivity']['dailyEdits'][$today])) {
                $this->data['fileActivity']['dailyEdits'][$today] = 0;
            }
            $this->data['fileActivity']['dailyEdits'][$today]++;

            // Update word and character count
            if (file_exists($filename)) {
                $content = file_get_contents($filename);
                if ($content !== false) {
                    $this->data['fileActivity']['wordCount'] = str_word_count($content);
                    $this->data['fileActivity']['characterCount'] = strlen($content);
                }
            }
        } elseif ($action === 'delete') {
            // Update file type counts when deleting
            if (isset($this->data['fileActivity']['fileTypes'][$ext])) {
                $this->data['fileActivity']['fileTypes'][$ext] = max(0, $this->data['fileActivity']['fileTypes'][$ext] - 1);
            }
        }

        // Log recent activity
        $activity = [
            'filename' => $filename,
            'action' => $action,
            'timestamp' => $timestamp,
            'extension' => $ext
        ];

        if (!isset($this->data['fileActivity']['recentEdits'])) {
            $this->data['fileActivity']['recentEdits'] = [];
        }

        array_unshift($this->data['fileActivity']['recentEdits'], $activity);
        $this->data['fileActivity']['recentEdits'] = array_slice(
            $this->data['fileActivity']['recentEdits'], 
            0, 
            10
        );

        // Update most active files
        if (!isset($this->data['fileActivity']['mostActive'])) {
            $this->data['fileActivity']['mostActive'] = [];
        }

        $found = false;
        foreach ($this->data['fileActivity']['mostActive'] as &$file) {
            if ($file['filename'] === $filename) {
                $file['editCount']++;
                $file['lastEdit'] = $timestamp;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $this->data['fileActivity']['mostActive'][] = [
                'filename' => $filename,
                'editCount' => 1,
                'lastEdit' => $timestamp
            ];
        }

        usort($this->data['fileActivity']['mostActive'], function($a, $b) {
            return $b['editCount'] - $a['editCount'];
        });
        $this->data['fileActivity']['mostActive'] = array_slice(
            $this->data['fileActivity']['mostActive'], 
            0, 
            5
        );

        $this->saveData();
    }

    public function getFormattedStats() {
        // Ensure all required data exists
        $this->ensureDataStructure();

        return [
            'timeSpent' => [
                'today' => $this->formatTime($this->data['timeSpent']['today']),
                'yesterday' => $this->formatTime($this->data['timeSpent']['yesterday']),
                'week' => $this->formatTime($this->data['timeSpent']['week']),
                'month' => $this->formatTime($this->data['timeSpent']['month'])
            ],
            'activeInstances' => $this->data['activeInstances']['count'],
            'fileActivity' => [
                'totalFiles' => $this->data['fileActivity']['totalFiles'] ?? 0,
                'recentEdits' => $this->data['fileActivity']['recentEdits'] ?? [],
                'mostActive' => $this->data['fileActivity']['mostActive'] ?? [],
                'fileTypes' => $this->data['fileActivity']['fileTypes'] ?? ['md' => 0, 'txt' => 0],
                'totalEdits' => $this->data['fileActivity']['totalEdits'] ?? 0,
                'wordCount' => $this->data['fileActivity']['wordCount'] ?? 0,
                'characterCount' => $this->data['fileActivity']['characterCount'] ?? 0,
                'dailyEdits' => $this->data['fileActivity']['dailyEdits'] ?? []
            ],
            'dailyStats' => $this->data['timeSpent']['dailyStats'] ?? [],
            'hourlyStats' => $this->data['timeSpent']['hourlyStats'] ?? array_fill(0, 24, 0),
            'productivity' => [
                'averageSession' => $this->formatTime($this->data['productivity']['averageSessionLength']),
                'longestSession' => $this->formatTime($this->data['productivity']['longestSession']),
                'currentStreak' => $this->data['productivity']['streaks']['current'] ?? 0,
                'longestStreak' => $this->data['productivity']['streaks']['longest'] ?? 0,
                'peakHours' => $this->getPeakHours()
            ]
        ];
    }

    private function formatTime($seconds) {
        $seconds = (int)$seconds;
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return sprintf("%02d:%02d", $hours, $minutes);
    }

    private function getPeakHours() {
        if (!isset($this->data['productivity']['peakHours'])) {
            $this->data['productivity']['peakHours'] = array_fill(0, 24, 0);
        }
        $hours = $this->data['productivity']['peakHours'];
        arsort($hours);
        return array_slice($hours, 0, 3, true);
    }

    public function __destruct() {
        if (isset($_SESSION['instance_id']) && isset($this->data['activeInstances']['sessions'])) {
            unset($this->data['activeInstances']['sessions'][$_SESSION['instance_id']]);
            $this->data['activeInstances']['count'] = count($this->data['activeInstances']['sessions']);
            $this->saveData();
        }
    }
}
