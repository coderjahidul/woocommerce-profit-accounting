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
The main dashboard provides a quick snapshot of your business performance:
- **Financial Statistics (Monthly)**: Real-time calculation of Revenue, COGS, Operating Expenses, and Net Profit for the current month.
- **Performance Trend Chart**: A visual line graph comparing Monthly Profit vs. Revenue over the course of the year.
- **Delivery Status Chart**: A dynamic doughnut chart showing the distribution of order statuses (Completed, Processing, On-hold, etc.) for a selectable period.

### Expense Management
Log and manage any business-related expenses (Rent, Salaries, Marketing, etc.):
1. Click on **Add Expense**.
2. **Add**: Fill in the Category, Amount, Date, and Note to save a new expense.
3. **Edit**: Click the "Edit" button next to any recent expense to modify its details.
4. **Delete**: Click the "Delete" button to permanently remove an entry (requires confirmation).
These costs are automatically deducted from your profit calculations.

### Facebook Ads Integration (Auto-Expense)
Automatically sync your daily ad spend from Facebook Ads Manager:
1. Navigate to **Profit Manager > Settings**.
2. Enter your **Facebook Access Token** and **Ad Account ID**.
3. Select the **Expense Category** where ad spend should be recorded (default: Marketing).
4. Enable **Auto Daily Sync** to have the plugin automatically fetch yesterday's spend every day via WP-Cron.
5. Use the **Sync Now** button for immediate updates of today's current spend.

### Google Ads Integration (Auto-Expense)
Automatically sync your daily ad spend from Google Ads:
1. Navigate to **Profit Manager > Settings**.
2. Enter your **Developer Token**, **Client ID**, **Client Secret**, **Refresh Token**, and **Customer ID** (digits only).
3. Select the **Expense Category** where ad spend should be recorded.
4. Enable **Auto Daily Sync** for automatic background updates.
5. Use the **Sync Now** button for immediate updates.

### TikTok Ads Integration (Auto-Expense)
Automatically sync your daily ad spend from TikTok Ads:
1. Navigate to **Profit Manager > Settings**.
2. Enter your **Access Token** and **Advertiser ID**.
3. Select the **Expense Category** where ad spend should be recorded.
4. Enable **Auto Daily Sync** for automatic background updates.
5. Use the **Sync Now** button for immediate updates.

### Daily & Yearly Reports
- **Daily Report**: View a day-by-day breakdown of your finances for any selected month.
- **Yearly Report**: View a month-by-month summary for the current year.
    - **Monthly Profit Trend**: A comprehensive bar and line chart overlay showing **Revenue**, **Expenses**, and **Net Profit** trends for the selected year.
- **Detailed View**: Click "View Details" on any report row to see a deep dive:
    - **Daily**: See orders received and specific expenses incurred.
    - **Monthly**: See product-level aggregation with **Qty Sold**, **Total Revenue**, **Total Cost**, and **Net Profit** per item.

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

### Operational Insights (Delivery Status)
Located on the main dashboard, this feature focuses on your order fulfillment performance:
- **Period Filter**: A dedicated selector allows you to filter the delivery breakdown by Today, Yesterday, Last 7 Days, Last 30 Days, This Month, or Last Month.
- **AJAX Refresh**: The chart updates instantly via AJAX without reloading the entire dashboard page.
- **Percentage Breakdown**: The interactive legend displays both the raw order count and the percentage of total orders for each status.
- **Delivered Rate**: Easily track what percentage of your total orders have been successfully 'Completed / Delivered' for any chosen period.

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

### File Structure
The plugin follows a modular, feature-wise file structure for better maintainability:
- `/admin/`: UI components, reports, and export logic.
- `/includes/`: Core business logic, calculators, and constants.
- `/assets/`: CSS styles and admin scripts.

### Database Tables
- `{prefix}wppam_expenses`: Stores expense logs.

### Filters & Hooks (For Extensions)
- `admin_enqueue_scripts`: Used to load assets.
- `woocommerce_product_options_pricing`: Used to add the COGS field to products.

---

## Support
For support, visit [github.com/coderjahidul](https://github.com/coderjahidul).
