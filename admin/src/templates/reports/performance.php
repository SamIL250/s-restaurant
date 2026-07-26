<div class="section">
    <h2>📊 Performance Metrics Summary</h2>
    
    <?php if (!empty($performance)): ?>
        <div class="metrics-grid">
            <?php
            $totalCategories = count($performance);
            $avgTurnover = 0;
            $avgAccuracy = 0;
            $avgScore = 0;
            $totalCarryingCost = 0;
            
            foreach ($performance as $perf) {
                $avgTurnover += $perf['turnover_rate'];
                $avgAccuracy += $perf['stock_accuracy'];
                $avgScore += $perf['performance_score'];
                $totalCarryingCost += $perf['carrying_cost'];
            }
            
            $avgTurnover = $totalCategories > 0 ? round($avgTurnover / $totalCategories, 2) : 0;
            $avgAccuracy = $totalCategories > 0 ? round($avgAccuracy / $totalCategories, 2) : 0;
            $avgScore = $totalCategories > 0 ? round($avgScore / $totalCategories, 2) : 0;
            ?>
            
            <div class="metric-cell info">
                <div class="metric-value"><?= $totalCategories ?></div>
                <div class="metric-label">📂 Total Categories</div>
            </div>
            <div class="metric-cell success">
                <div class="metric-value"><?= $avgAccuracy ?>%</div>
                <div class="metric-label">🎯 Avg Stock Accuracy</div>
            </div>
            <div class="metric-cell warning">
                <div class="metric-value"><?= $avgTurnover ?></div>
                <div class="metric-label">🔄 Avg Turnover Rate</div>
            </div>
            <div class="metric-cell danger">
                <div class="metric-value">$<?= number_format($totalCarryingCost, 0) ?></div>
                <div class="metric-label">💰 Total Carrying Cost</div>
            </div>
        </div>
        
        <h2>📈 Detailed Performance by Category</h2>
        <table>
            <thead>
                <tr>
                    <th>📂 Category</th>
                    <th>🔄 Turnover Rate</th>
                    <th>🎯 Stock Accuracy</th>
                    <th>💰 Carrying Cost</th>
                    <th>📉 Stockout Rate</th>
                    <th>🏆 Performance Score</th>
                    <th>📊 Performance Level</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($performance as $index => $perf): ?>
                    <?php
                    $score = $perf['performance_score'];
                    $level = 'Poor';
                    $levelClass = 'danger';
                    $levelIcon = '❌';
                    
                    if ($score >= 80) {
                        $level = 'Excellent';
                        $levelClass = 'success';
                        $levelIcon = '🏆';
                    } elseif ($score >= 60) {
                        $level = 'Good';
                        $levelClass = 'warning';
                        $levelIcon = '✅';
                    } elseif ($score >= 40) {
                        $level = 'Fair';
                        $levelClass = 'info';
                        $levelIcon = '📊';
                    }
                    
                    // Add alternating row colors for better readability
                    $rowClass = ($index % 2 == 0) ? 'even-row' : 'odd-row';
                    ?>
                    <tr class="<?= $rowClass ?>">
                        <td><strong><?= htmlspecialchars($perf['category_name']) ?></strong></td>
                        <td><?= number_format($perf['turnover_rate'], 2) ?></td>
                        <td><?= number_format($perf['stock_accuracy'], 1) ?>%</td>
                        <td>$<?= number_format($perf['carrying_cost'], 2) ?></td>
                        <td><?= number_format($perf['stockout_rate'], 1) ?>%</td>
                        <td><strong><?= number_format($perf['performance_score'], 1) ?></strong></td>
                        <td>
                            <span class="performance-badge <?= $levelClass ?>">
                                <?= $levelIcon ?> <?= $level ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h2>📊 Performance Analysis</h2>
        <div class="metrics-grid">
            <?php
            $excellentCount = 0;
            $goodCount = 0;
            $fairCount = 0;
            $poorCount = 0;
            
            foreach ($performance as $perf) {
                $score = $perf['performance_score'];
                if ($score >= 80) $excellentCount++;
                elseif ($score >= 60) $goodCount++;
                elseif ($score >= 40) $fairCount++;
                else $poorCount++;
            }
            
            $total = $excellentCount + $goodCount + $fairCount + $poorCount;
            $excellentPct = $total > 0 ? round(($excellentCount / $total) * 100, 1) : 0;
            $goodPct = $total > 0 ? round(($goodCount / $total) * 100, 1) : 0;
            $fairPct = $total > 0 ? round(($fairCount / $total) * 100, 1) : 0;
            $poorPct = $total > 0 ? round(($poorCount / $total) * 100, 1) : 0;
            ?>
            
            <div class="metric-cell success">
                <div class="metric-value"><?= $excellentCount ?> (<?= $excellentPct ?>%)</div>
                <div class="metric-label">🏆 Excellent (80+)</div>
            </div>
            <div class="metric-cell warning">
                <div class="metric-value"><?= $goodCount ?> (<?= $goodPct ?>%)</div>
                <div class="metric-label">✅ Good (60-79)</div>
            </div>
            <div class="metric-cell info">
                <div class="metric-value"><?= $fairCount ?> (<?= $fairPct ?>%)</div>
                <div class="metric-label">📊 Fair (40-59)</div>
            </div>
            <div class="metric-cell danger">
                <div class="metric-value"><?= $poorCount ?> (<?= $poorPct ?>%)</div>
                <div class="metric-label">❌ Poor (&lt;40)</div>
            </div>
        </div>
        
        <div class="section">
            <h2>💡 Performance Insights</h2>
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #3498db;">
                <?php
                $insights = [];
                
                if ($avgAccuracy >= 90) {
                    $insights[] = "🎯 Excellent overall stock accuracy ({$avgAccuracy}%) - Inventory management is working well.";
                } elseif ($avgAccuracy >= 75) {
                    $insights[] = "📊 Good stock accuracy ({$avgAccuracy}%) - Room for improvement in tracking.";
                } else {
                    $insights[] = "⚠️ Low stock accuracy ({$avgAccuracy}%) - Review inventory processes.";
                }
                
                if ($totalCarryingCost > 10000) {
                    $insights[] = "💰 High carrying cost ($" . number_format($totalCarryingCost) . ") - Consider optimizing stock levels.";
                }
                
                if ($excellentPct >= 50) {
                    $insights[] = "🏆 Strong performance - {$excellentPct}% of categories are performing excellently.";
                } elseif ($poorPct >= 30) {
                    $insights[] = "❌ Performance concerns - {$poorPct}% of categories need attention.";
                }
                
                if (!empty($insights)) {
                    foreach ($insights as $insight) {
                        echo "<p style='margin-bottom: 10px;'><strong>" . $insight . "</strong></p>";
                    }
                } else {
                    echo "<p>📊 Performance data is within normal ranges.</p>";
                }
                ?>
            </div>
        </div>
    <?php else: ?>
        <div class="no-data">
            <h3>📂 No Performance Data Available</h3>
            <p>There is no performance data available for the selected period.</p>
            <p>Please ensure:</p>
            <ul style="text-align: left; display: inline-block;">
                <li>Stock movements have been recorded</li>
                <li>Categories have been properly set up</li>
                <li>Date range includes relevant data</li>
            </ul>
        </div>
    <?php endif; ?>
</div>

<style>
.performance-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.performance-badge.success {
    background: linear-gradient(135deg, #27ae60, #2ecc71);
    color: white;
}

.performance-badge.warning {
    background: linear-gradient(135deg, #f39c12, #f1c40f);
    color: white;
}

.performance-badge.info {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
}

.performance-badge.danger {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
}

.even-row {
    background-color: #ffffff;
}

.odd-row {
    background-color: #f8f9fa;
}
</style>
