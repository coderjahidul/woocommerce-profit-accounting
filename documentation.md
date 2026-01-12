# WooCommerce Profit & Accounting Manager - Documentation

## Introduction
**WooCommerce Profit & Accounting Manager** is a lightweight yet powerful financial tracking tool for WooCommerce. It helps you monitor your Net Profit by tracking Revenue, Cost of Goods Sold (COGS), and business Expenses.

---

## 1. Getting Started

### Installation
1. Upload the `woocommerce-profit-accounting` folder to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Upon activation, the plugin creates a database table for expenses (`wp_wppam_expenses`).

### Initial Setup
To get accurate profit reports, you must provide cost data for your products.
1. Go to any Product Edit page.
2. In the **General** tab of the Product Data section, you will see a field named **Product Cost (COGS)**.
3. Enter your purchase price for that product and save.

---

## 2. Core Features

### Dashboard Overview
The main dashboard provides a quick snapshot of your business performance for the current month:
- **Monthly Revenue**: Total sales from 'Processing' and 'Completed' orders.
- **Product COGS**: Total cost of items sold.
- **Expenses**: Total business costs logged.
- **Net Profit**: Revenue - (COGS + Expenses).

### Expense Management
Log any business-related expenses (Rent, Salaries, Marketing, etc.):
1. Click on **Add Expense**.
2. Fill in the Title, Category, Amount, and Date.
3. These expenses will be automatically deducted from your profit calculations in reports.

### Daily & Yearly Reports
- **Daily Report**: View a day-by-day breakdown of your finances for any selected month.
- **Yearly Report**: View a month-by-month summary for the current year.
- **Detailed View**: Click "View Details" on any report row to see exactly which products were sold or which expenses were incurred on that specific day/month.

### Inventory Valuation Report
A comprehensive report located under **Profit Manager > Inventory Report** that provides:
- **Total Inventory Value**: The combined cost of all items currently in stock.
- **Sold vs Remaining**: Track how many units have been sold all-time versus what's currently available.
- **Potential Profit**: see the estimated profit if all remaining items were sold at current prices.
- **Dead Stock Identification**: Automatically identifies products with zero sales in the last 30 days.

### Order-Level Profit Breakdown
Monitor the profitability of individual orders directly within the WooCommerce interface:
- **Orders List Column**: A new "Profit" column in the WooCommerce Orders list shows the net profit for each order at a glance.
- **Single Order Meta Box**: View a detailed "Profit Breakdown" on the single order page, showing Revenue, COGS, and Net Profit for that specific order.
- **Color Coding**: Profit values are color-coded (Green for profit, Red for loss) for quick identification.

---

## 3. Data Integration & Calculations

### Revenue Calculation
Revenue is calculated based on WooCommerce orders with statuses:
- `processing`
- `completed`
*Note: This includes taxes and shipping if they are part of the order total.*

### COGS Calculation
`COGS = (Product Cost Price) * (Quantity Sold)`
The plugin looks for the `_cost_price` meta key. If a variation doesn't have a specific cost, it falls back to the parent product's cost.

### Profit Formula
`Net Profit = Total Revenue - (Total COGS + Total Expenses)`

---

## 4. Exporting Data
You can export your yearly performance data:
- **CSV Export**: Standard spreadsheet format.
- **PDF Export**: Basic PDF report (Requires `dompdf` library if installed via composer, currently basic implementation).

---

## 5. Developer Information

### Database Tables
- `{prefix}wppam_expenses`: Stores expense logs.

### Filters & Hooks (For Extensions)
- `admin_enqueue_scripts`: Used to load assets.
- `woocommerce_product_options_pricing`: Used to add the COGS field to products.

---

## Support
For support, visit [jahidulsabuz.com](https://jahidulsabuz.com).
