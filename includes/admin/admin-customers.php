<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class LTW_Admin_Customers {

    protected $model;

    public function __construct() {
        $this->model = new LTW_Model_Customer();
        add_action( 'admin_init', array( $this, 'handle_csv_export' ) );
    }

    /**
     * 1. HANDLE CSV EXPORT (UPDATED WITH DATE FILTER)
     */
    public function handle_csv_export() {
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'ltw_export_customers' ) {
            
            if ( ! current_user_can( 'manage_options' ) ) return;

            // Get filter parameters from URL
            $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
            $end_date   = isset($_GET['end_date'])   ? sanitize_text_field($_GET['end_date'])   : '';

            if ( ob_get_length() ) ob_end_clean();

            // Call Model with date parameters
            $rows = $this->model->get_all_customers($start_date, $end_date);
            
            // Updated filename to English
            $filename = 'customer-list-' . date('Y-m-d_H-i') . '.csv';

            header( 'Content-Type: text/csv; charset=utf-8' );
            header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
            header( 'Pragma: no-cache' );
            header( 'Expires: 0' );

            $output = fopen( 'php://output', 'w' );
            fputs( $output, "\xEF\xBB\xBF" ); // Add BOM for Excel compatibility

            fputcsv( $output, array( 'Full Name', 'Phone', 'Email', 'Gift Won', 'Date Joined' ) );

            if ( ! empty( $rows ) && is_array( $rows ) ) {
                foreach ( $rows as $r ) {
                    $gift = isset($r['getgift']) ? $r['getgift'] : (isset($r['gift']) ? $r['gift'] : '');
                    $date = isset($r['created_at']) ? $r['created_at'] : (isset($r['datecreate']) ? $r['datecreate'] : '');
                    $phone = isset($r['phone']) ? $r['phone'] : '';

                    fputcsv( $output, array(
                        isset($r['fullname']) ? $r['fullname'] : '',
                        $phone, 
                        isset($r['email']) ? $r['email'] : '',
                        $gift,
                        $date
                    ));
                }
            }
            fclose( $output );
            exit;
        }
    }

    public function render() {
        // --- 1. GET FILTER PARAMETERS ---
        $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
        $end_date   = isset($_GET['end_date'])   ? sanitize_text_field($_GET['end_date'])   : '';

        // --- 2. FETCH DATA FROM MODEL (WITH FILTER) ---
        $all_rows = $this->model->get_all_customers($start_date, $end_date);
        if (!is_array($all_rows)) { $all_rows = []; }

        // --- 3. PAGINATION ---
        $total_items = count($all_rows);
        $items_per_page = 20; 
        $current_page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
        if($current_page < 1) $current_page = 1;
        
        $total_pages = ceil($total_items / $items_per_page);
        $offset = ($current_page - 1) * $items_per_page;
        
        $rows = array_slice($all_rows, $offset, $items_per_page);

        // --- CSS ---
        ?>
        <style>
            .ltw-pagination { margin: 15px 0; text-align: right; }
            .ltw-pagination .page-numbers {
                display: inline-block; padding: 5px 10px; margin-left: 4px;
                font-size: 14px; font-weight: 500; text-decoration: none;
                color: #0073aa; background: #fff; border: 1px solid #ccd0d4;
                border-radius: 3px;
            }
            .ltw-pagination .page-numbers.current { background: #0073aa; color: #fff; border-color: #0073aa; }
            
            /* Filter Bar Styles */
            .ltw-filter-bar {
                background: #fff; padding: 15px; margin: 20px 0;
                border: 1px solid #ccd0d4; border-left: 4px solid #2271b1;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
                display: flex; align-items: center; justify-content: space-between;
            }
            .ltw-filter-group { display: flex; align-items: center; gap: 10px; }
            .ltw-export-btn {
                background-color: #2271b1; border-color: #2271b1; color: #fff;
                text-decoration: none; padding: 6px 15px; border-radius: 3px;
                font-weight: 500; font-size: 13px; display: inline-flex; align-items: center;
            }
            .ltw-export-btn:hover { background-color: #135e96; color: #fff; }
            .dashicons { margin-right: 5px; }
        </style>

        <div class="wrap ltw-admin-section">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'Customers List', 'zuta-lucky-wheel' ); ?></h1>
            <hr class="wp-header-end">
            
            <form method="get">
                <input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
                <?php if(isset($_REQUEST['tab'])): ?>
                    <input type="hidden" name="tab" value="<?php echo esc_attr( $_REQUEST['tab'] ); ?>" />
                <?php endif; ?>

                <div class="ltw-filter-bar">
                    <div class="ltw-filter-group">
                        <label><strong><?php esc_html_e('Filter Date:', 'zuta-lucky-wheel'); ?></strong></label>
                        
                        <input type="date" name="start_date" value="<?php echo esc_attr($start_date); ?>" placeholder="Start Date">
                        <span>to</span>
                        <input type="date" name="end_date" value="<?php echo esc_attr($end_date); ?>" placeholder="End Date">
                        
                        <button type="submit" class="button button-secondary">
                            <span class="dashicons dashicons-filter"></span> Filter
                        </button>
                        
                        <?php if($start_date || $end_date): ?>
                            <a href="?page=<?php echo esc_attr($_REQUEST['page']); ?>&tab=customer" class="button">Reset</a>
                        <?php endif; ?>
                    </div>

                    <div class="ltw-actions">
                        <?php 
                            // Create Export link with current date parameters
                            $export_args = array( 
                                'action' => 'ltw_export_customers',
                                'start_date' => $start_date,
                                'end_date' => $end_date
                            );
                            $export_link = add_query_arg( $export_args, admin_url('admin.php') ); 
                        ?>
                        <a href="<?php echo esc_url($export_link); ?>" class="ltw-export-btn">
                            <span class="dashicons dashicons-download"></span> Export Excel (.csv)
                        </a>
                    </div>
                </div>

                <?php $this->display_pagination($total_pages, $current_page); ?>

                <table class="widefat fixed striped table-view-list">
                    <thead>
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th><?php esc_html_e('Fullname','zuta-lucky-wheel');?></th>
                            <th><?php esc_html_e('Phone','zuta-lucky-wheel');?></th>
                            <th><?php esc_html_e('Email','zuta-lucky-wheel');?></th>
                            <th><?php esc_html_e('Gift Won','zuta-lucky-wheel');?></th>
                            <th><?php esc_html_e('Date','zuta-lucky-wheel');?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ( ! empty( $rows ) ) : foreach ( $rows as $r ) : ?>
                        <tr>
                            <td><?php echo esc_html( isset($r['idcustomer']) ? $r['idcustomer'] : '' ); ?></td>
                            <td><strong><?php echo esc_html( isset($r['fullname']) ? $r['fullname'] : '' ); ?></strong></td>
                            <td><?php echo esc_html( isset($r['phone']) ? $r['phone'] : '' ); ?></td>
                            <td><?php echo esc_html( isset($r['email']) ? $r['email'] : '' ); ?></td>
                            <td><span style="color: green; font-weight: 500;">
                                <?php echo esc_html( isset($r['getgift']) ? $r['getgift'] : (isset($r['gift']) ? $r['gift'] : '-') ); ?>
                            </span></td>
                            <td><?php echo esc_html( isset($r['created_at']) ? $r['created_at'] : (isset($r['datecreate']) ? $r['datecreate'] : '') ); ?></td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr><td colspan="6" style="text-align:center; padding: 20px;"><?php esc_html_e( 'No customers found for this period.', 'zuta-lucky-wheel' ); ?></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>

                <?php $this->display_pagination($total_pages, $current_page); ?>
            </form>
        </div>
        <?php
    }

    private function display_pagination($total_pages, $current_page) {
        if ($total_pages <= 1) return;
        
        // IMPORTANT: Preserve filter parameters during pagination
        $page_links = paginate_links( array(
            'base' => add_query_arg( 'paged', '%#%' ),
            'format' => '',
            'prev_text' => __('&laquo; Previous'),
            'next_text' => __('Next &raquo;'),
            'total' => $total_pages,
            'current' => $current_page,
            'type' => 'plain'
        ) );
        
        if ( $page_links ) {
            echo '<div class="ltw-pagination">' . $page_links . '</div>';
        }
    }
}