// Data contoh untuk prototype. Nantinya data ini bisa diganti dengan respons dari PHP/MySQL.
const $ = (id) => document.getElementById(id);
const rupiah = (number) => new Intl.NumberFormat('id-ID', {
  style: 'currency',
  currency: 'IDR',
  maximumFractionDigits: 0
}).format(number);

const dayStart = (date) => new Date(date.getFullYear(), date.getMonth(), date.getDate());
const addDays = (date, amount) => {
  const result = new Date(date);
  result.setDate(result.getDate() + amount);
  return result;
};
const dateKey = (date) => {
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, '0');
  const d = String(date.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
};
const parseDate = (value) => {
  const [year, month, day] = value.split('-').map(Number);
  return new Date(year, month - 1, day);
};
const shortDate = (date) => new Intl.DateTimeFormat('id-ID', {
  weekday: 'short'
}).format(date);

function createDemoData() {
  const result = [];
  const today = dayStart(new Date());
  for (let i = 119; i >= 0; i--) {
    const date = addDays(today, -i);
    const weekday = date.getDay();
    const weekdayFactor = weekday === 0 || weekday === 6 ? 1.2 : 1;
    const wave = 0.82 + ((Math.sin(i * 1.7) + 1) * 0.23);
    const orders = Math.max(10, Math.round((16 + (i * 7 % 13)) * weekdayFactor * wave));
    const revenue = Math.round((orders * (12000 + (i * 1700 % 18000))) / 1000) * 1000;
    const customers = Math.round(orders * (0.76 + (i % 4) * 0.04));
    result.push({ date, orders, revenue, customers });
  }
  return result;
}

const demoData = createDemoData();
let chart;
let selectedRange = null;

function getRows(start, end) {
  const from = dayStart(start);
  const to = dayStart(end);
  return demoData.filter((row) => row.date >= from && row.date <= to);
}

function total(rows) {
  return rows.reduce((sum, row) => ({
    revenue: sum.revenue + row.revenue,
    orders: sum.orders + row.orders,
    customers: sum.customers + row.customers
  }), { revenue: 0, orders: 0, customers: 0 });
}

function percent(current, previous) {
  if (!previous) return current ? 100 : 0;
  return Math.round(((current - previous) / previous) * 100);
}

function updateTrend(id, current, previous) {
  const change = percent(current, previous);
  const element = $(id);
  const arrow = change > 0 ? '↑' : change < 0 ? '↓' : '→';
  const color = change < 0 ? '#d45a4b' : '#28a66d';
  element.textContent = `${arrow} ${Math.abs(change)}% dari periode sebelumnya`;
  element.style.color = color;
}

function getRange() {
  if (selectedRange) return selectedRange;
  const end = dayStart(new Date());
  const days = Number($('periodSelect').value) || 7;
  return { start: addDays(end, -(days - 1)), end, days };
}

function updateDashboard() {
  const range = getRange();
  const rows = getRows(range.start, range.end);
  const days = Math.max(1, Math.round((dayStart(range.end) - dayStart(range.start)) / 86400000) + 1);
  const previousEnd = addDays(range.start, -1);
  const previousStart = addDays(previousEnd, -(days - 1));
  const previous = total(getRows(previousStart, previousEnd));
  const current = total(rows);

  $('revenueValue').textContent = rupiah(current.revenue);
  $('ordersValue').textContent = current.orders.toLocaleString('id-ID');
  $('customersValue').textContent = current.customers.toLocaleString('id-ID');
  updateTrend('revenueTrend', current.revenue, previous.revenue);
  updateTrend('ordersTrend', current.orders, previous.orders);
  updateTrend('customersTrend', current.customers, previous.customers);
  $('periodRevenue').textContent = rupiah(current.revenue);
  $('periodOrders').textContent = current.orders.toLocaleString('id-ID');
  $('periodHeading').textContent = `${days} Hari Terakhir`;
  renderChart(rows);
}

function renderChart(rows) {
  const labels = rows.map((row) => {
    if (rows.length <= 7) return shortDate(row.date);
    return `${row.date.getDate()}/${row.date.getMonth() + 1}`;
  });
  const values = rows.map((row) => row.revenue);
  const ctx = $('salesChart').getContext('2d');
  if (chart) chart.destroy();

  chart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'Pendapatan',
        data: values,
        backgroundColor: '#285d46',
        hoverBackgroundColor: '#194b35',
        borderRadius: 5,
        borderSkipped: false,
        maxBarThickness: rows.length > 14 ? 14 : 58,
        categoryPercentage: rows.length > 14 ? 0.9 : 0.72,
        barPercentage: 0.88
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          displayColors: false,
          callbacks: {
            label: (context) => `Pendapatan: ${rupiah(context.parsed.y)}`
          }
        }
      },
      scales: {
        x: {
          grid: { display: false },
          border: { display: false },
          ticks: {
            color: '#252525',
            font: { size: 11 },
            maxRotation: 0,
            autoSkip: rows.length > 7,
            maxTicksLimit: 14
          }
        },
        y: {
          beginAtZero: true,
          border: { display: false },
          grid: { display: false },
          ticks: {
            color: '#303030',
            stepSize: 50000,
            font: { size: 10 },
            padding: 8,
            callback: (value) => value.toLocaleString('id-ID')
          }
        }
      }
    }
  });
}

function applyCustomDates() {
  const startValue = $('startDate').value;
  const endValue = $('endDate').value;
  const error = $('dateError');
  if (!startValue || !endValue) {
    error.textContent = 'Pilih tanggal mulai dan akhir.';
    return;
  }
  const start = parseDate(startValue);
  const end = parseDate(endValue);
  if (start > end) {
    error.textContent = 'Tanggal mulai harus sebelum tanggal akhir.';
    return;
  }
  if (end > dayStart(new Date())) {
    error.textContent = 'Tanggal akhir tidak boleh melewati hari ini.';
    return;
  }
  error.textContent = '';
  selectedRange = { start, end };
  updateDashboard();
}

$('periodSelect').addEventListener('change', () => {
  const isCustom = $('periodSelect').value === 'custom';
  $('customDates').hidden = !isCustom;
  selectedRange = null;
  if (isCustom) {
    const today = dayStart(new Date());
    $('startDate').value = dateKey(addDays(today, -6));
    $('endDate').value = dateKey(today);
    $('startDate').max = dateKey(today);
    $('endDate').max = dateKey(today);
  } else {
    updateDashboard();
  }
});

$('applyDates').addEventListener('click', applyCustomDates);

function setupDropdown(buttonId, menuId, otherMenuId) {
  $(buttonId).addEventListener('click', () => {
    const menu = $(menuId);
    $(otherMenuId).hidden = true;
    menu.hidden = !menu.hidden;
  });
}
setupDropdown('notificationButton', 'notificationMenu', 'profileMenu');
setupDropdown('profileButton', 'profileMenu', 'notificationMenu');

$('logoutButton').addEventListener('click', () => {
  $('profileMenu').hidden = true;
  alert('Fitur keluar perlu dihubungkan dengan sistem login.');
});

document.addEventListener('click', (event) => {
  if (!event.target.closest('.navbar')) {
    $('notificationMenu').hidden = true;
    $('profileMenu').hidden = true;
  }
});

updateDashboard();
