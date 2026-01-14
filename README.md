# WooCommerce Profit & Accounting Manager

[![WordPress Version](https://img.shields.io/badge/WordPress-6.0+-blue.svg)](https://wordpress.org/)
[![WooCommerce Version](https://img.shields.io/badge/WooCommerce-7.0+-purple.svg)](https://woocommerce.com/)
[![License](https://img.shields.io/badge/license-GPL--2.0-orange.svg)](LICENSE.txt)

A powerful, lightweight, and modern financial management tool for WooCommerce. Track your real-time net profit by automatically calculating Revenue, Cost of Goods Sold (COGS), and business Expenses.

---

## 🔥 Key Features

### 📊 Advanced Dashboard
- **Financial Snapshot**: Real-time monthly summary of Revenue, COGS, Expenses, and Net Profit.
- **Performance Trends**: Smooth line charts comparing Monthly Profit vs. Revenue.
- **Delivery Insights**: Interactive doughnut chart for order fulfillment analysis.
- **AJAX Filtering**: Toggle between timeframes (Today, Last 7/30 Days, This/Last Month) instantly without page reloads.

### 💸 Expense Management
- **Full CRUD Support**: Add, Edit, and Delete business expenses (Rent, Salaries, Marketing).
- **Categorized Tracking**: Organize costs for better financial reporting.
- **Automated Deductions**: Expenses are automatically subtracted from your net profit reports.

### 📱 Facebook Ads Automation
- **Auto-Expense Sync**: Automatically fetch your daily Facebook Ad spend.
- **WP-Cron Integration**: Seamlessly syncs yesterday's spend every 24 hours.
- **Manual Sync**: Instant "Sync Now" button for real-time spend updates.

### 📅 Comprehensive Reporting
- **Daily Reports**: Itemized financial breakdown for any day of the month.
- **Yearly Summaries**: Month-by-month financial health overview.
- **Deep-Dive Details**: Drill down into any day or month to see exact products sold and specific costs.

### 📦 Inventory Intelligence
- **Total Stock Valuation**: Real-time calculation of your entire inventory value at cost.
- **Dead Stock ID**: Automatically identify products with zero sales in the last 30 days.
- **Potential Profit**: Estimate future earnings based on remaining stock.

### 🛒 Order-Level Analytics
- **Profit Column**: See profit data direktly in the WooCommerce Orders list.
- **Profit Breakdown**: Dedicated meta box on single order pages showing precise Revenue, COGS, and Margin.

---

## 🚀 Getting Started

### Installation
1. Upload the plugin folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. The plugin will automatically set up the required database tables.

### Setup
To get accurate reports, ensure your products have cost data:
1. Edit a product.
2. In the **General** tab, find the **Product Cost (COGS)** field.
3. Enter your purchase price. For variable products, set costs per variation.

---

## 🛠 Developer Info

### Methodology
- **Revenue**: Sum of totals from `processing` and `completed` orders.
- **COGS**: `(Product Cost) * (Quantity Sold)`.
- **Net Profit**: `Revenue - (COGS + Expenses)`.

### Tech Stack
- **Backend**: PHP, WordPress Database (WPDB).
- **Frontend**: Vanilla JS, Chart.js, Modern CSS (Custom properties & Flexbox).
- **Communication**: WordPress AJAX API for real-time UI updates.

---

## 📄 License
This plugin is licensed under the [GPL-2.0-or-later](LICENSE.txt).

## 🆘 Support
For technical issues or feature requests, please visit [github.com/coderjahidul](https://github.com/coderjahidul).
