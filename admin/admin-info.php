<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display the Plugin Info & Documentation page.
 */
function wppam_info_page()
{
    ?>
    <div class="wrap wppam-dashboard-wrapper wppam-animate">
        <div class="wppam-header">
            <h1>Plugin Documentation & Help</h1>
        </div>

        <div class="wppam-tabs-container">
            <div class="wppam-tabs-nav">
                <button class="wppam-tab-btn active" onclick="wppamOpenTab(event, 'getting-started')">🚀 Getting
                    Started</button>
                <button class="wppam-tab-btn" onclick="wppamOpenTab(event, 'features')">✨ Features</button>
                <button class="wppam-tab-btn" onclick="wppamOpenTab(event, 'calculations')">🧮 Calculations</button>
                <button class="wppam-tab-btn" onclick="wppamOpenTab(event, 'faq')">❓ FAQ</button>
            </div>

            <div id="getting-started" class="wppam-tab-content active">
                <div class="wppam-table-card wppam-p-30">
                    <h3 class="wppam-mt-0 wppam-text-primary">Quick Start Guide</h3>
                    <p>Follow these steps to set up your profit tracking accurately:</p>
                    <div class="wppam-step-list">
                        <div class="wppam-step-item">
                            <span class="wppam-step-number">1</span>
                            <div class="wppam-step-text">
                                <strong>Define Product Costs:</strong>
                                <p>Go to your WooCommerce products. In the "General" tab, you'll find a new field
                                    <strong>"Product Cost (COGS)"</strong>. Enter your purchase price there.
                                </p>
                            </div>
                        </div>
                        <div class="wppam-step-item">
                            <span class="wppam-step-number">2</span>
                            <div class="wppam-step-text">
                                <strong>Log & Manage Expenses:</strong>
                                <p>Navigate to "Add Expense" to record business costs. You can also **Edit** or **Delete** existing entries from the recent expenses list.</p>
                            </div>
                        </div>
                        <div class="wppam-step-item">
                            <span class="wppam-step-number">3</span>
                            <div class="wppam-step-text">
                                <strong>Check Order Profit:</strong>
                                <p>Open any WooCommerce order or check the orders list. A new "Profit" column and meta
                                    box show the profitability of each order.</p>
                            </div>
                        </div>
                        <div class="wppam-step-item">
                            <span class="wppam-step-number">4</span>
                            <div class="wppam-step-text">
                                <strong>Check Inventory Value:</strong>
                                <p>Navigate to "Inventory Report" to see your total stock value at cost and identify
                                    dead stock.</p>
                            </div>
                        </div>
                        <div class="wppam-step-item">
                            <span class="wppam-step-number">5</span>
                            <div class="wppam-step-text">
                                <strong>Review Reports:</strong>
                                <p>Visit the Dashboard, Daily Reports, and Yearly Reports to see your real-time financial
                                    health.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="features" class="wppam-tab-content">
                <div class="wppam-table-card wppam-p-30">
                    <h3 class="wppam-mt-0 wppam-text-primary">Key Features</h3>
                    <ul class="wppam-feature-list">
                        <li><strong>Automated COGS Calculation:</strong> Automatically calculates the cost of every sale.
                        </li>
                        <li><strong>Order-Level Profit Visibility:</strong> See profit data directly in the WooCommerce
                            Orders list and single order pages.</li>
                        <li><strong>Inventory Valuation:</strong> Real-time report of total stock value at cost price.</li>
                        <li><strong>Dead Stock ID:</strong> Automatically identify products that haven't sold in 30 days.</li>
                        <li><strong>Expense Management:</strong> Add, edit, and delete business costs categorized for analysis.</li>
                        <li><strong>Interactive Dashboard:</strong> Visual charts powered by Chart.js.</li>
                        <li><strong>Detailed Drill-down:</strong> See exact products sold, per-item profit, and expense lists for any day or month.</li>
                        <li><strong>Data Export:</strong> Download your reports in CSV or PDF format for accounting.</li>
                    </ul>
                </div>
            </div>

            <div id="calculations" class="wppam-tab-content">
                <div class="wppam-table-card wppam-p-30">
                    <h3 class="wppam-mt-0 wppam-text-primary">Methodology</h3>
                    <div class="wppam-calc-box">
                        <code>Net Profit = (Total Revenue) - (Total COGS + Total Expenses)</code>
                    </div>
                    <p><strong>Revenue:</strong> Sum of totals from orders marked as 'Processing' or 'Completed'.</p>
                    <p><strong>COGS:</strong> Sum of (Product Cost * Quantity) for all items in those orders.</p>
                    <p><strong>Expenses:</strong> Sum of all entries in the expense table for the specified date range.</p>
                </div>
            </div>

            <div id="faq" class="wppam-tab-content">
                <div class="wppam-table-card wppam-p-30">
                    <h3 class="wppam-mt-0 wppam-text-primary">FAQs</h3>
                    <div class="wppam-faq-item">
                        <strong>Q: What happens if I forget to set a product cost?</strong>
                        <p>A: The plugin will treat the cost as 0.00, meaning the entire sale amount will be counted as
                            gross profit before expenses.</p>
                    </div>
                    <div class="wppam-faq-item">
                        <strong>Q: How do I correct an wrong expense?</strong>
                        <p>A: Go to the "Add Expense" page. In the "Recent Expenses" table, click **Edit** to modify the amount or category, or **Delete** to remove it permanently.</p>
                    </div>
                    <div class="wppam-faq-item">
                        <strong>Q: Does it support variable products?</strong>
                        <p>A: Yes! You can set individual costs for each variation. If a variation cost is missing, it
                            falls back to the parent product's cost.</p>
                    </div>
                    <div class="wppam-faq-item">
                        <strong>Q: Where can I see profit for a specific order?</strong>
                        <p>A: Go to WooCommerce > Orders to see the "Profit" column, or click on an order to see the
                            "Profit Breakdown" sidebar meta box.</p>
                    </div>
                    <div class="wppam-faq-item">
                        <strong>Q: How is "Potential Profit" calculated in Inventory Report?</strong>
                        <p>A: It's the difference between the current selling price and the cost price, multiplied by the
                            remaining stock quantity.</p>
                    </div>
                    <div class="wppam-faq-item">
                        <strong>Q: What is "Dead Stock"?</strong>
                        <p>A: Any product that has zero items sold in the last 30 days but still has positive stock quantity
                            is flagged as dead stock.</p>
                    </div>
                    <div class="wppam-faq-item">
                        <strong>Q: How do I export data?</strong>
                        <p>A: Look for the export buttons on the Dashboard or Report pages to generate CSV or PDF
                            files.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="wppam-footer-info wppam-mt-30">
            <div class="wppam-table-card wppam-p-24">
                <h4 class="wppam-mt-0">Technical Info</h4>
                <small>Version: 2.0.0 | Text Domain: woocommerce-profit-accounting</small>
            </div>
            <div class="wppam-table-card wppam-p-24">
                <h4 class="wppam-mt-0">Need Help?</h4>
                <div style="display: flex; gap: 15px; align-items: center;">
                    <a href="https://github.com/coderjahidul/woocommerce-profit-accounting/issues" target="_blank" style="text-decoration: none; color: var(--wppam-primary); font-weight: 600;">GitHub Issues</a>
                    <a href="https://github.com/coderjahidul/" target="_blank" class="wppam-btn-primary" style="font-size: 12px; padding: 8px 15px;">Official Support</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function wppamOpenTab(evt, tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("wppam-tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
                tabcontent[i].classList.remove("active");
            }
            tablinks = document.getElementsByClassName("wppam-tab-btn");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
            document.getElementById(tabName).style.display = "block";
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.className += " active";
        }
    </script>
    <?php
}
