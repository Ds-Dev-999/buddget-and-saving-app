<?php
session_start();
require_once '../config/database.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch user data
$user_id = $_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['user_name']);
$current_month = date('Y-m');

// Fetch income per day
$incomeStmt = $pdo->prepare("SELECT income_per_day FROM users WHERE id = :user_id");
$incomeStmt->execute([':user_id' => $user_id]);
$income_per_day = $incomeStmt->fetchColumn() ?? 5000;

// Fetch monthly data
$stmt = $pdo->prepare("
    SELECT 
        IFNULL((SELECT SUM(c.amount_earned) 
                 FROM calendar c 
                 WHERE c.user_id = :user_id AND c.date BETWEEN :start_date AND :end_date), 0) AS total_income,
        IFNULL((SELECT SUM(e.expense_amount) 
                 FROM expenses e 
                 WHERE e.user_id = :user_id AND e.expense_date BETWEEN :start_date AND :end_date), 0) AS total_expenses,
        IFNULL((SELECT b.savings_goal 
                 FROM budgets b 
                 WHERE b.user_id = :user_id AND b.month_year = :month 
                 LIMIT 1), 0) AS savings_goal,
        IFNULL((SELECT b.budget_amount 
                 FROM budgets b 
                 WHERE b.user_id = :user_id AND b.month_year = :month 
                 LIMIT 1), 0) AS budget_amount
");
$stmt->execute([
    ':user_id' => $user_id,
    ':month' => $current_month,
    ':start_date' => date('Y-m-01', strtotime($current_month)), // First day of the month
    ':end_date' => date('Y-m-t', strtotime($current_month)) // Last day of the month
]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

$total_income = $data['total_income'] ?? 0;
$total_expenses = $data['total_expenses'] ?? 0;
$savings_goal = $data['savings_goal'] ?? 0;
$budget_amount = $data['budget_amount'] ?? 0;
$savings = $total_income - $total_expenses;

// Update savings in the budgets table
$updateSavingsStmt = $pdo->prepare("
    UPDATE budgets
    SET savings = :savings
    WHERE user_id = :user_id AND month_year = :month
");
$updateSavingsStmt->execute([
    ':user_id' => $user_id,
    ':month' => $current_month,
    ':savings' => $savings
]);

// Calculate required workdays for savings
$remaining_expenses = $total_expenses - $total_income;
$required_workdays = ($income_per_day > 0 && $remaining_expenses > 0) ? ceil($remaining_expenses / $income_per_day) : 0;

// Fetch expenses
$expensesStmt = $pdo->prepare("
    SELECT expense_name, expense_amount, expense_date, status, id
    FROM expenses 
    WHERE user_id = :user_id AND DATE_FORMAT(expense_date, '%Y-%m') = :month
");
$expensesStmt->execute([
    ':user_id' => $user_id,
    ':month' => $current_month,
]);
$expenses = $expensesStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch budgets for the logged-in user
$stmt = $pdo->prepare("SELECT * FROM budgets WHERE user_id = :user_id ORDER BY month_year DESC");
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$budgets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.6/index.global.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.6/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <header>
        <h1>Welcome, <?= $user_name ?></h1>
        <p>Dashboard - <?= date('F Y') ?></p>
        <a href="logout.php">Logout</a>
    </header>

    <main>
        <!-- Overview Section -->
        <section class="overview">
            <div class="card">
                <h2>Total Income</h2>
                <p>LKR<?= number_format($total_income, 2) ?></p>
            </div>
            <div class="card">
                <h2>Total Expenses</h2>
                <p>LKR<?= number_format($total_expenses, 2) ?></p>
            </div>
            <div class="card">
                <h2><?= $savings >= 0 ? 'Savings' : 'Due' ?></h2>
                <p>LKR<?= $savings >= 0 ? number_format($savings, 2) : number_format(abs($savings), 2) . ' (Due)' ?></p>
            </div>
            <div class="card">
                <h2>Required Days</h2>
                <p><?= $required_workdays ?> days</p>
            </div>
        </section>

        <section class="charts">
            <div>
                <h3>Income vs Expenses</h3>
                <canvas id="incomeExpenseChart"></canvas>
            </div>
            <div>
                <h3>Expense Breakdown</h3>
                <canvas id="expensePieChart"></canvas>
            </div>
            <div>
                <h3>Budget Overview</h3>
                <canvas id="budgetPieChart"></canvas>
            </div>

        </section>


        <!-- Manage Income, Budget, and Expenses -->
        <section id="management" style="padding: 20px 0; text-align: center;">
            <div style="display: flex; justify-content: space-around; flex-wrap: wrap;">
                <!-- Set Income Per Day -->
                <div class="card" style="flex: 1; margin: 10px; min-width: 250px;">
                    <h3>Set Income Per Day</h3>
                    <form id="income-form" style="display: inline-block; text-align: left; width: 100%;">
                        <input type="number" id="income-per-day" value="<?= $income_per_day ?>" placeholder="Income Per Day (LKR)" required style="width: 100%;">
                        <button type="submit" style="width: 100%;">Update Income</button>
                    </form>
                </div>

                <!-- Add Budget for a Specific Month -->
                <div class="card" style="flex: 1; margin: 10px; min-width: 250px;">
                    <h3>Add Budget for a Specific Month</h3>
                    <form id="addBudgetForm" style="display: inline-block; text-align: left; width: 100%;">
                        <label for="month_year">Month (YYYY-MM):</label>
                        <input type="month" id="month_year" name="month_year" required style="width: 100%;">

                        <label for="budget_amount">Budget Amount:</label>
                        <input type="number" id="budget_amount" name="budget_amount" step="0.01" required style="width: 100%;">

                        <label for="savings_goal">Savings Goal (optional):</label>
                        <input type="number" id="savings_goal" name="savings_goal" step="0.01" style="width: 100%;">

                        <button type="submit" style="width: 100%;">Add Budget</button>
                    </form>
                    <div id="response"></div>
                </div>

                <!-- Add Expense -->
                <div class="card" style="flex: 1; margin: 10px; min-width: 250px;">
                    <h3>Add Expense</h3>
                    <form id="expense-form" style="display: inline-block; text-align: left; width: 100%;">
                        <input type="text" id="expense-name" placeholder="Expense Name" required style="width: 100%;">
                        <input type="number" id="expense-amount" placeholder="Expense Amount (LKR)" required style="width: 100%;">
                        <input type="date" id="expense-date" required style="width: 100%;">
                        <button type="submit" style="width: 100%;">Add Expense</button>
                    </form>
                </div>
            </div>
        </section>

        <!-- Expenses Table -->
        <section class="table" id="expense" style="text-align: center;">
            <h3>Monthly Expenses</h3>
            <table id="expense">
                <thead>
                    <tr>
                        <th>Expense Name</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expenses as $expense): ?>
                        <tr>
                            <td><?= htmlspecialchars($expense['expense_name'] ?? 'N/A') ?></td>
                            <td>LKR <?= number_format($expense['expense_amount'] ?? 0, 2) ?></td>
                            <td><?= htmlspecialchars($expense['expense_date'] ?? 'N/A') ?></td>
                            <td>
                                <input
                                    type="checkbox"
                                    id="status-<?= htmlspecialchars($expense['expense_name']) ?>"
                                    class="status-checkbox"
                                    data-name="<?= htmlspecialchars($expense['expense_name']) ?>"
                                    <?= (isset($expense['status']) && $expense['status'] == 1) ? 'checked' : '' ?>>
                                <label
                                    for="status-<?= htmlspecialchars($expense['expense_name']) ?>"
                                    style="color: <?= (isset($expense['status']) && $expense['status'] == 1) ? 'green' : 'yellow' ?>;">
                                    <?= (isset($expense['status']) && $expense['status'] == 1) ? 'Done' : 'Not Done' ?>
                                </label>
                            </td>
                            <td>
                                <form method="POST" action="delete_expense.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this expense?');">
                                    <input type="hidden" name="expense_name" value="<?= htmlspecialchars($expense['expense_name'] ?? '') ?>">
                                    <button type="submit" class="delete-expense">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section class="table" id="budget-calendar" style="text-align: center; display: flex; justify-content: space-around; flex-wrap: wrap;">
            <!-- Monthly Budgets -->
            <div style="flex: 1; margin: 10px; min-width: 250px;">
            <h3>Monthly Budgets</h3>
            <table id="budget">
                <thead>
                <tr>
                    <th>Month</th>
                    <th>Budget Amount</th>
                    <th>Savings Goal</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($budgets as $budget): ?>
                    <tr>
                    <td><?= htmlspecialchars($budget['month_year']) ?></td>
                    <td>LKR <?= number_format($budget['budget_amount'], 2) ?></td>
                    <td>LKR <?= number_format($budget['savings_goal'] ?? 0, 2) ?></td>
                    <td>
                        <form method="POST" action="delete_budget.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this budget?');">
                        <input type="hidden" name="month_year" value="<?= htmlspecialchars($budget['month_year']) ?>">
                        <button type="submit" class="delete-budget">Delete</button>
                        </form>
                    </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>

            <!-- Calendar -->
            <div style="flex: 1; margin: 10px; min-width: 250px;">
            <h3>Work Calendar</h3>
            <div id="calendar"></div>
            </div>
        </section>
    </main>

    <script src="./js/app.js"></script>
    <script>
        // Chart.js - Income vs Expenses
        const incomeExpenseCtx = document.getElementById('incomeExpenseChart').getContext('2d');
        new Chart(incomeExpenseCtx, {
            type: 'bar',
            data: {
                labels: ['Income', 'Expenses', 'Savings'],
                datasets: [{
                    label: 'Amount ($)',
                    data: [<?= $total_income ?>, <?= $total_expenses ?>, <?= $savings ?>],
                    backgroundColor: ['#4caf50', '#f44336', '#2196f3']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Chart.js - Expense Breakdown
        const expensePieCtx = document.getElementById('expensePieChart').getContext('2d');
        new Chart(expensePieCtx, {
            type: 'pie',
            data: {
                labels: ['Expenses', 'Savings'],
                datasets: [{
                    data: [<?= $total_expenses ?>, <?= $savings ?>],
                    backgroundColor: ['#f44336', '#4caf50']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Chart.js - Budget Overview (Budget, Expenses, and Savings/Due)
        const budgetPieCtx = document.getElementById('budgetPieChart').getContext('2d');

        // Calculate values for Budget Overview
        const budget = <?= $budget_amount ?>; // Total budget
        const expenses = <?= $total_expenses ?>; // Total expenses
        const savingsOrDue = <?= $savings ?>; // Savings (can be negative)

        // Prepare data for the chart
        const budgetData = [
            Math.max(savingsOrDue, 0), // Savings
            Math.min(savingsOrDue, 0) * -1, // Due (if savings are negative)
            expenses // Expenses
        ];
        const budgetLabels = ['Savings', 'Due', 'Expenses'];
        const budgetColors = ['#4caf50', '#ff9800', '#f44336'];

        new Chart(budgetPieCtx, {
            type: 'pie',
            data: {
                labels: budgetLabels,
                datasets: [{
                    data: budgetData,
                    backgroundColor: budgetColors
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>

</html>