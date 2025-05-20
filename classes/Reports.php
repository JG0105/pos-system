<?php
// classes/Reports.php
class Reports {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get GST Report for date range
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getGSTReport($startDate, $endDate) {
        try {
            $report = [
                'sales' => [
                    'total' => 0,
                    'gst_collected' => 0,
                    'subtotal' => 0,
                    'transactions' => []
                ],
                'purchases' => [
                    'total' => 0,
                    'gst_paid' => 0,
                    'subtotal' => 0,
                    'transactions' => []
                ],
                'gst_payable' => 0,
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate
                ]
            ];

            // Get Sales GST
            $sql = "SELECT s.*, 
                          c.company_name as customer_name,
                          c.first_name, 
                          c.last_name
                   FROM sales s
                   LEFT JOIN customers c ON s.customer_id = c.customer_id
                   WHERE DATE(s.sale_date) BETWEEN :start_date AND :end_date
                   AND s.payment_status != 'cancelled'
                   ORDER BY s.sale_date";

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            $stmt->execute();
            
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $report['sales']['total'] += $row['total_amount'];
                $report['sales']['gst_collected'] += $row['tax_amount'];
                $report['sales']['subtotal'] += $row['subtotal'];
                $report['sales']['transactions'][] = $row;
            }

            // Get Purchases GST
            $sql = "SELECT p.*, 
                          s.company_name as supplier_name
                   FROM purchases p
                   LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
                   WHERE DATE(p.purchase_date) BETWEEN :start_date AND :end_date
                   AND p.status != 'cancelled'
                   ORDER BY p.purchase_date";

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            $stmt->execute();
            
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $report['purchases']['total'] += $row['total_amount'];
                $report['purchases']['gst_paid'] += $row['tax_amount'];
                $report['purchases']['subtotal'] += $row['subtotal'];
                $report['purchases']['transactions'][] = $row;
            }

            // Calculate GST Payable (GST Collected - GST Paid)
            $report['gst_payable'] = $report['sales']['gst_collected'] - $report['purchases']['gst_paid'];

            return $report;
        } catch(PDOException $e) {
            error_log("GST Report Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get Quarterly GST Reports
     * @param int $year
     * @return array
     */
    public function getQuarterlyGSTReports($year) {
        $quarters = [
            1 => ['start' => "$year-07-01", 'end' => "$year-09-30"], // Q1 (Jul-Sep)
            2 => ['start' => "$year-10-01", 'end' => "$year-12-31"], // Q2 (Oct-Dec)
            3 => ['start' => ($year+1)."-01-01", 'end' => ($year+1)."-03-31"], // Q3 (Jan-Mar)
            4 => ['start' => ($year+1)."-04-01", 'end' => ($year+1)."-06-30"]  // Q4 (Apr-Jun)
        ];

        $reports = [];
        foreach($quarters as $quarter => $dates) {
            $reports[$quarter] = $this->getGSTReport($dates['start'], $dates['end']);
        }

        return $reports;
    }

    /**
     * Get Business Activity Statement Report
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getBASReport($startDate, $endDate) {
        $gstReport = $this->getGSTReport($startDate, $endDate);
        
        return [
            'period' => [
                'start' => $startDate,
                'end' => $endDate
            ],
            'g1_total_sales' => $gstReport['sales']['total'],
            'g2_export_sales' => 0, // If you handle exports
            'g3_gst_free_sales' => 0, // If you handle GST-free items
            'g4_input_taxed_sales' => 0, // If applicable
            'g5_total_sales' => $gstReport['sales']['total'],
            'g6_total_purchases' => $gstReport['purchases']['total'],
            'g7_non_capital_purchases' => $gstReport['purchases']['total'],
            'g8_capital_purchases' => 0, // If you track capital purchases separately
            'g9_non_creditable_purchases' => 0, // If applicable
            'g10_total_purchases' => $gstReport['purchases']['total'],
            'g11_gst_collected' => $gstReport['sales']['gst_collected'],
            'g12_gst_paid' => $gstReport['purchases']['gst_paid'],
            'g13_gst_payable' => $gstReport['gst_payable'],
            'g14_wine_equalisation_tax' => 0, // If applicable
            'g15_luxury_car_tax' => 0, // If applicable
            'g16_other_taxes' => 0, // If applicable
            'g17_total_tax_payable' => $gstReport['gst_payable']
        ];
    }
}
?>
