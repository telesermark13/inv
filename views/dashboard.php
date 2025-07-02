<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
$inventory_value = $conn->query("
    SELECT 
        SUM(unit_price * quantity) as total_nontaxed_value,
        SUM(unit_price * quantity * IF(taxable=1, 1.12, 1)) as total_taxed_value
    FROM items
")->fetch_assoc();
$movement_dates = [];
$items_added_series = [];
$items_removed_series = [];
$sql = "SELECT DATE(created_at) as day, 
        SUM(CASE WHEN movement_type='in' THEN quantity ELSE 0 END) as added, 
        SUM(CASE WHEN movement_type='out' THEN quantity ELSE 0 END) as removed
        FROM inventory_movements
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 DAY)
        GROUP BY day ORDER BY day ASC";
$res = $conn->query($sql);
while ($row = $res->fetch_assoc()) {
  $movement_dates[] = date('D', strtotime($row['day']));
  $items_added_series[] = (int)$row['added'];
  $items_removed_series[] = (int)$row['removed'];
}
$low_stocks = [];
$res = $conn->query("
    SELECT id, name, sku, description, quantity, min_stock_level, unit
    FROM items
    WHERE quantity <= min_stock_level
    ORDER BY quantity ASC
");
while ($row = $res->fetch_assoc()) {
    $low_stocks[] = $row;
}
$low_stocks_old = [];
$res_old = $conn->query("
    SELECT id, name, sku, description, quantity, min_stock_level, unit
    FROM old_stocks
    WHERE quantity <= min_stock_level
    ORDER BY quantity ASC
");
while ($row = $res_old->fetch_assoc()) {
    $low_stocks_old[] = $row;
}
?>
<style>
  :root {
    --primary-color: #4361ee;
    --secondary-color: #3f37c9;
    --accent-color: #4895ef;
    --success-color: #4cc9f0;
    --warning-color: #f8961e;
    --danger-color: #f94144;
    --light-bg: #f8f9fa;
    --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  }

  .dashboard-header {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    color: white;
    border-radius: 12px 12px 0 0 !important;
    padding: 1.5rem 2rem;
  }

  .stat-card {
    border: none;
    border-radius: 12px;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    height: 100%;
  }

  .stat-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--card-shadow);
  }

  .stat-card .card-body {
    padding: 1.5rem;
    color: white;
  }

  .stat-card .stat-icon {
    font-size: 2.5rem;
    opacity: 0.2;
    position: absolute;
    right: 20px;
    top: 20px;
  }

  .chart-row {
    display: flex;
    flex-wrap: wrap;
    margin: -0.5rem;
    /* Adjust gutter spacing */
  }

  .chart-col {
    flex: 0 0 calc(50% - 1rem);
    max-width: calc(50% - 1rem);
    margin: 0.5rem;
    min-height: 280px;
  }

  .chart-wrapper {
    height: 100%;
    display: flex;
    flex-direction: column;
  }

  .chart-container {
    height: 220px;
    /* Make the chart area smaller (try 200-260px for taste) */
    min-height: 160px;
    max-height: 280px;
    position: relative;
    padding: 1rem;
    background: white;
    border-radius: 12px;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .chart-container canvas {
    /* Remove width/height !important! */
    display: block;
    max-width: 100%;
    max-height: 100%;
    margin: 0 auto;
  }

  @media (max-width: 992px) {
    .chart-col {
      flex: 0 0 100%;
      max-width: 100%;
    }
  }

  .activity-item {
    border-left: 3px solid transparent;
    transition: all 0.3s ease;
    padding: 1rem 1.25rem;
  }

  .activity-item:hover {
    background-color: rgba(67, 97, 238, 0.05) !important;
    border-left-color: var(--primary-color);
  }

  .quick-link-btn {
    border-radius: 8px;
    padding: 0.75rem;
    font-weight: 500;
    transition: all 0.3s ease;
    text-align: left;
    margin-bottom: 0.75rem;
  }

  .quick-link-btn i {
    margin-right: 10px;
    font-size: 1.1rem;
  }

  .value-card {
    border-radius: 10px;
    padding: 1.25rem;
    margin-bottom: 1rem;
    border-left: 4px solid;
    transition: transform 0.3s ease;
  }

  .value-card:hover {
    transform: translateY(-3px);
  }

  .welcome-message {
    background: linear-gradient(135deg, #f6faff 0%, #e6f0ff 100%);
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border-left: 4px solid var(--primary-color);
  }
  
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>

<div class="container-fluid px-4">
  <div class="card border-0 shadow-lg overflow-hidden">
    <!-- Dashboard Header -->
    <div class="card-header dashboard-header d-flex justify-content-between align-items-center">
      <div>
        <h2 class="mb-1"><i class="fas fa-boxes me-2"></i>Inventory Dashboard</h2>
        <p class="mb-0 opacity-75">Real-time overview of your inventory system</p>
      </div>
      <div class="badge bg-white text-primary p-2 px-3 rounded-pill">
        <i class="fas fa-calendar-alt me-1"></i> <?= date('F j, Y') ?>
      </div>
    </div>

    <div class="card-body bg-light">
      <!-- Welcome Message -->
      <div class="welcome-message">
        <div class="d-flex align-items-center">
          <div class="flex-grow-1">
            <h4 class="mb-1 text-primary">Welcome back, <?= htmlspecialchars($_SESSION['username']) ?>!</h4>
            <p class="mb-0 text-muted">Here's what's happening with your inventory today</p>
          </div>
          <img src="https://cdn-icons-png.flaticon.com/512/3058/3058971.png" width="80" alt="Inventory illustration">
        </div>
      </div>

      <!-- Summary Cards -->
      <div class="row mb-4 g-4">
        <div class="col-md-4">
          <div class="stat-card" style="background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);">
            <div class="card-body position-relative">
              <i class="fas fa-boxes stat-icon"></i>
              <h5 class="card-title mb-3">Total Items</h5>
              <p class="card-text display-5 fw-bold mb-0"><?= number_format($stats['total_items']) ?></p>
              <small class="opacity-75">Across all categories</small>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="stat-card" style="background: linear-gradient(135deg, #f8961e 0%, #f3722c 100%);">
            <div class="card-body position-relative">
              <i class="fas fa-truck-loading stat-icon"></i>
              <h5 class="card-title mb-3">Pending Deliveries</h5>
              <p class="card-text display-5 fw-bold mb-0"><?= number_format($pending_deliveries) ?></p>
              <small class="opacity-75">Awaiting processing</small>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="stat-card" style="background: linear-gradient(135deg, #f94144 0%, #d90429 100%);">
            <div class="card-body position-relative">
              <i class="fas fa-exclamation-triangle stat-icon"></i>
              <h5 class="card-title mb-3">Low Stock Alerts</h5>
              <p class="card-text display-5 fw-bold mb-0"><?= number_format($stats['low_stock_items']) ?></p>
              <small class="opacity-75">Needs attention</small>
            </div>
          </div>
        </div>
      </div>

      <div class="chart-row">
        <!-- Line Chart Column -->
        <div class="chart-col">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3 d-flex flex-column">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><i class="fas fa-chart-line text-primary me-2"></i>Inventory Movement</h5>
                <span class="badge bg-primary bg-opacity-10 text-primary">Last 7 Days</span>
              </div>
              <div class="chart-container">
                <canvas id="lineChart"></canvas>
              </div>
            </div>
          </div>
        </div>


        <!-- Pie Chart Column -->
        <div class="chart-col">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3 d-flex flex-column">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><i class="fas fa-chart-pie text-warning me-2"></i>Inventory Status</h5>
                <span class="badge bg-warning bg-opacity-10 text-warning">Current</span>
              </div>
              <div class="chart-container">
                <canvas id="pieChart"></canvas>
              </div>
              <div class="mt-3 d-flex justify-content-center gap-2 flex-wrap">
                <span class="badge rounded-pill" style="background: #ff6384">Low Stock</span>
                <span class="badge rounded-pill" style="background: #ffcd56">Pending</span>
                <span class="badge rounded-pill" style="background: #4bc0c0">In Stock</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <br> <br>
      <!-- Bottom Section -->
      <div class="row g-4">
        <!-- Recent Activity -->
        <div class="col-lg-7">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><i class="fas fa-history text-info me-2"></i>Recent Activity</h5>
              </div>

              <div class="list-group list-group-flush">
                <?php foreach ($recent_activity as $activity): ?>
                  <div class="activity-item list-group-item border-0">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                      <div>
                        <span class="fw-bold"><?= htmlspecialchars($activity['user']) ?></span>
                        <span class="<?= $activity['movement_type'] === 'in' ? 'text-success' : 'text-danger' ?>">
                          <?= $activity['movement_type'] === 'in' ? 'added' : 'removed' ?>
                        </span>
                        <span class="fw-bold"><?= $activity['quantity'] ?></span>
                        <span>of</span>
                        <span class="fw-bold"><?= htmlspecialchars($activity['item_name']) ?></span>
                      </div>
                      <small class="text-muted">
                        <?= date('M j, g:i a', strtotime($activity['created_at'])) ?>
                      </small>
                    </div>
                    <div class="d-flex align-items-center">
                      <span class="badge bg-light text-dark me-2">
                        <i class="fas fa-tag me-1"></i>
                        <?= ucfirst($activity['reference_type']) ?> #<?= $activity['reference_id'] ?>
                      </span>
                      <?php if (isset($activity['notes']) && $activity['notes']): ?>
                        <small class="text-muted"><i class="fas fa-comment me-1"></i><?= htmlspecialchars($activity['notes']) ?></small>
                      <?php endif; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Actions & Inventory Value -->
        <div class="col-lg-5">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <h5><i class="fas fa-bolt text-warning me-2"></i>Quick Actions</h5>
              <div class="mb-4">
                <?php if ($_SESSION['role'] === 'admin'): ?>
                  <a href="items.php?action=add" class="btn quick-link-btn btn-outline-primary d-block">
                    <i class="fas fa-plus-circle"></i> Add New Item
                  </a>
                <?php endif; ?>
                <a href="delivery_receipt.php" class="btn quick-link-btn btn-outline-success d-block">
                  <i class="fas fa-truck-fast"></i> Create Delivery Receipt
                </a>
                <?php if ($_SESSION['role'] === 'client'): ?>
                  <a href="materials_request.php" class="btn quick-link-btn btn-outline-warning d-block">
                    <i class="fas fa-file-pen"></i> New Materials Request
                  </a>
                <?php endif; ?>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                  <a href="materials_request_admin.php" class="btn quick-link-btn btn-outline-info d-block">
                    <i class="fas fa-clipboard-check"></i> Approve Requests
                  </a>
                <?php endif; ?>
              </div>

              <!-- Inventory Value Summary -->
              <h5 class="mt-4"><i class="fas fa-coins text-success me-2"></i>Inventory Value</h5>
              <div class="row g-3">
                <div class="col-md-6">
                  <div class="value-card bg-light" style="border-left-color: #4361ee;">
                    <div class="d-flex align-items-center">
                      <div class="bg-primary bg-opacity-10 p-2 rounded me-3">
                        <i class="fas fa-receipt text-primary"></i>
                      </div>
                      <div>
                        <small class="text-muted d-block">Non-Taxed Value</small>
                        <h4 class="mb-0 fw-bold">₱<?= number_format($inventory_value['total_nontaxed_value'] ?? 0, 2) ?></h4>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="value-card bg-light" style="border-left-color: #f8961e;">
                    <div class="d-flex align-items-center">
                      <div class="bg-warning bg-opacity-10 p-2 rounded me-3">
                        <i class="fas fa-file-invoice-dollar text-warning"></i>
                      </div>
                      <div>
                        <small class="text-muted d-block">Taxed Value</small>
                        <h4 class="mb-0 fw-bold">₱<?= number_format($inventory_value['total_taxed_value'] ?? 0, 2) ?></h4>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- System Status -->
              <div class="mt-4 pt-3 border-top">
                <h5><i class="fas fa-heartbeat text-danger me-2"></i>System Status</h5>
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span>Database Health</span>
                  <span class="badge bg-success bg-opacity-10 text-success">Optimal</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span>Storage Usage</span>
                  <span class="badge bg-info bg-opacity-10 text-info">N/A</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                  <span>Last Backup</span>
                  <span class="badge bg-secondary bg-opacity-10 text-secondary">
                    <?= isset($last_backup) ? date('M j, g:i a', strtotime($last_backup)) : 'Not available' ?>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Low Stocks Modal -->
<div class="modal fade" id="lowStocksModal" tabindex="-1" aria-labelledby="lowStocksModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="lowStocksModalLabel"><i class="fas fa-exclamation-triangle me-2"></i>Low Stock Items</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <div class="table-responsive">
          <table class="table table-bordered table-striped m-0">
            <thead>
              <tr>
                <th>Name</th>
                <th>SKU</th>
                <th>Description</th>
                <th>Qty</th>
                <th>Min Stock</th>
                <th>Unit</th>
                <th>Source</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($low_stocks as $item): ?>
                <tr>
                  <td><?= htmlspecialchars($item['name']) ?></td>
                  <td><?= htmlspecialchars($item['sku']) ?></td>
                  <td><?= htmlspecialchars($item['description']) ?></td>
                  <td class="text-danger fw-bold"><?= (int)$item['quantity'] ?></td>
                  <td><?= (int)$item['min_stock_level'] ?></td>
                  <td><?= htmlspecialchars($item['unit']) ?></td>
                  <td><span class="badge bg-primary">Inventory</span></td>
                </tr>
              <?php endforeach; ?>
              <?php foreach ($low_stocks_old as $item): ?>
                <tr>
                  <td><?= htmlspecialchars($item['name']) ?></td>
                  <td><?= htmlspecialchars($item['sku']) ?></td>
                  <td><?= htmlspecialchars($item['description']) ?></td>
                  <td class="text-danger fw-bold"><?= (int)$item['quantity'] ?></td>
                  <td><?= (int)$item['min_stock_level'] ?></td>
                  <td><?= htmlspecialchars($item['unit']) ?></td>
                  <td><span class="badge bg-secondary">Old Stocks</span></td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($low_stocks) && empty($low_stocks_old)): ?>
                <tr><td colspan="7" class="text-center text-muted">No low stock items found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {

    // --- Line Chart ---
    const lineChart = new Chart(
      document.getElementById('lineChart'), {
        type: 'line',
        data: {
          labels: <?= json_encode($movement_dates) ?>,
          datasets: [{
            label: 'Items Added',
            data: <?= json_encode($items_added_series) ?>,
            borderColor: '#4361ee',
            backgroundColor: 'rgba(67, 97, 238, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.3
          }, {
            label: 'Items Removed',
            data: <?= json_encode($items_removed_series) ?>,
            borderColor: '#f94144',
            backgroundColor: 'rgba(249, 65, 68, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.3
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'top',
              labels: {
                boxWidth: 12,
                padding: 10
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              grid: {
                drawBorder: false
              }
            },
            x: {
              grid: {
                display: false
              }
            }
          }
        }
      }
    );

    // --- Pie Chart ---
    const pieData = {
      labels: ['Low Stock', 'Pending Deliveries', 'In Stock'],
      datasets: [{
        data: [
          <?= (int)$stats['low_stock_items'] ?>,
          <?= (int)$pending_deliveries ?>,
          <?= max(0, $stats['total_items'] - $stats['low_stock_items'] - $pending_deliveries) ?>
        ],
        backgroundColor: ['#ff6384', '#ffcd56', '#4bc0c0'],
        borderWidth: 0
      }]
    };

    const pieChart = new Chart(
      document.getElementById('pieChart'), {
        type: 'doughnut',
        data: pieData,
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: '70%',
          plugins: {
            legend: {
              display: false
            },
            datalabels: {
              color: '#fff',
              font: {
                weight: 'bold',
                size: 11
              },
              formatter: (value) => {
                const total = pieData.datasets[0].data.reduce((a, b) => a + b, 0);
                return total ? Math.round((value / total) * 100) + '%' : '0%';
              }
            }
          }
        },
        plugins: [ChartDataLabels]
      }
    );


    // Handle resizing
    function resizeCharts() {
      lineChart.resize();
      pieChart.resize();
    }

    // Add resize observer for containers
    const resizeObserver = new ResizeObserver(entries => {
      entries.forEach(entry => {
        if (entry.target === lineChartContainer) {
          lineChart.resize();
        } else if (entry.target === pieChartContainer) {
          pieChart.resize();
        }
      });
    });

    resizeObserver.observe(lineChartContainer);
    resizeObserver.observe(pieChartContainer);

    // Initial resize after a short delay
    setTimeout(resizeCharts, 50);
    window.addEventListener('resize', resizeCharts);
  });
</script>