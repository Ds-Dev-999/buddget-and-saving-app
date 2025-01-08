document.addEventListener("DOMContentLoaded", function () {
    // Fetch Monthly Summary and Update the Dashboard
    const fetchMonthlySummary = () => {
        fetch("getMonthlySummary.php")
            .then((response) => response.json())
            .then((data) => {
                const remainingBudgetEl = document.getElementById("remaining-budget");
                const requiredWorkdaysEl = document.getElementById("required-workdays");

                if (remainingBudgetEl) {
                    remainingBudgetEl.textContent = `Remaining Budget: $${data.remaining_budget}`;
                } else {
                    console.error("Element with ID 'remaining-budget' not found.");
                }

                if (requiredWorkdaysEl) {
                    requiredWorkdaysEl.textContent = `Required Workdays: ${data.required_workdays}`;
                } else {
                    console.error("Element with ID 'required-workdays' not found.");
                }

                updateExpenseTable(data.expenses);
                updateIncomeExpenseChart(data.total_income, data.total_expenses, data.savings);
                updateExpensePieChart(data.total_expenses, data.savings);
            })
            .catch((error) => console.error("Error fetching summary:", error));
    };

    // Update Income Expense Chart


    // Update Expense Table
    const updateExpenseTable = (expenses) => {
        const tableBody = document.querySelector("#expense-table tbody");
        tableBody.innerHTML = ""; // Clear existing rows
        expenses.forEach((expense) => {
            const row = document.createElement("tr");
            row.innerHTML = `
                <td>${expense.expense_name}</td>
                <td>$${expense.expense_amount}</td>
                <td>${expense.expense_date}</td>
                <td>
                    <button class="edit-expense" data-id="${expense.id}">Edit</button>
                    <button class="delete-expense" data-id="${expense.id}">Delete</button>
                </td>
            `;
            tableBody.appendChild(row);
        });
    };

    // Income Per Day Update
    document.getElementById("income-form").addEventListener("submit", function (e) {
        e.preventDefault();
        const incomePerDay = document.getElementById("income-per-day").value;

        fetch("updateIncomePerDay.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ income_per_day: incomePerDay })
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    alert("Income per day updated successfully.");
                    fetchMonthlySummary();
                } else {
                    alert(data.error || "Failed to update income.");
                }
            });
    });

    // Expense Form Submission
    document.getElementById("expense-form").addEventListener("submit", function (e) {
        e.preventDefault();

        const expenseName = document.getElementById("expense-name").value;
        const expenseAmount = document.getElementById("expense-amount").value;
        const expenseDate = document.getElementById("expense-date").value;

        fetch("addExpense.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                expense_name: expenseName,
                expense_amount: expenseAmount,
                expense_date: expenseDate
            })
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    alert("Expense added successfully.");
                    fetchMonthlySummary();
                } else {
                    alert(data.error || "Failed to add expense.");
                }
            });
    });

    // Initialize FullCalendar
    const calendarEl = document.getElementById("calendar");
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: "dayGridMonth",
        editable: true,
        selectable: true,
        events: "getEvents.php",
        select: function (info) {
            const amountEarned = prompt("Enter earnings for this day:", "5000");
            if (amountEarned) {
                fetch("saveEvent.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ date: info.startStr, amount: amountEarned })
                })
                    .then(() => {
                        calendar.refetchEvents();
                        fetchMonthlySummary();
                    });
            }
            calendar.unselect();
        },
        eventClick: function (info) {
            if (confirm("Do you want to delete this earning?")) {
                fetch("deleteEvent.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ id: info.event.id })
                })
                    .then(() => {
                        calendar.refetchEvents();
                        fetchMonthlySummary();
                    });
            }
        }
    });
    calendar.render();

    document.querySelectorAll('.delete-expense').forEach(button => {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            const form = this.closest('form');
            const formData = new FormData(form);

            if (confirm('Are you sure you want to delete this expense?')) {
                fetch('delete_expense.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.success); // Show success alert
                            form.closest('tr').remove(); // Remove the row from the table
                        } else {
                            alert(data.error || 'An error occurred. Please try again.'); // Show error alert
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                    });
            }
        });
    });

    document.getElementById('addBudgetForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        const response = await fetch('add_budget.php', {
            method: 'POST',
            body: JSON.stringify(Object.fromEntries(formData)),
            headers: { 'Content-Type': 'application/json' }
        });
        const result = await response.json();
        document.getElementById('response').innerText = result.success
            ? "Budget added successfully!"
            : `Error: ${result.error}`;
    });


    function updateExpenseStatus(name, status) {
        fetch('updateExpenseStatus.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ name: name, status: status }),
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Expense status updated successfully.');
                } else {
                    alert('Failed to update expense status.');
                }
            })
            .catch(error => console.error('Error:', error));
    }

    // Add event listeners to checkboxes
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.status-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                const expenseName = this.dataset.name;
                const status = this.checked ? 1 : 0;
                updateExpenseStatus(expenseName, status);
            });
        });
    });

    document.querySelectorAll('.delete-budget').forEach(button => {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            const form = this.closest('form');
            const formData = new FormData(form);
    
            fetch('delete_budget.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    form.closest('tr').remove();
                    alert('Budget deleted successfully!');
                } else {
                    alert(data.error || 'Failed to delete budget.');
                }
            })
            .catch(error => console.error('Error:', error));
        });
    })

    // Initial Fetch of Summary
    fetchMonthlySummary();
});
