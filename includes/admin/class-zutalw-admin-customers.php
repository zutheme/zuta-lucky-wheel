<?php
/**
 * zutalw Admin Customers Class
 * Handles the display and export of customer data in the admin dashboard.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class ZUTALW_Admin_Customers {
    protected $model;

    /**
     * Constructor: Initializes the model and hooks into admin_init.
     */
    public function __construct() {
        $this->model = new zutalw_Model_Customer();
        add_action( 'admin_init', array( $this, 'handle_csv_export' ) );
    }

    /**
     * Handles the CSV export action with security checks.
     */
    public function handle_csv_export() {
        if ( ! isset( $_GET['action'] ) || $_GET['action'] !== 'zutalw_export_customers' ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to export this data.', 'zuta-lucky-wheel' ) );
        }

        // Verify Nonce for Export to satisfy Plugin Check
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'zutalw_export_customers_nonce' ) ) {
            wp_die( esc_html__( 'Security check failed. Please try again.', 'zuta-lucky-wheel' ) );
        }

        $start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( wp_unslash( $_GET['start_date'] ) ) : '';
        $end_date   = isset( $_GET['end_date'] )   ? sanitize_text_field( wp_unslash( $_GET['end_date'] ) ) : '';

        if ( ob_get_length() ) {
            ob_end_clean();
        }

        $rows = $this->model->get_all_customers( $start_date, $end_date );
        $filename = 'customer-list-' . gmdate( 'Y-m-d_H-i' ) . '.csv';

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . esc_attr( $filename ) . '"' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        /**
         * Standard PHP filesystem calls are required for CSV streaming to php://output.
         * We use // phpcs:ignore to inform reviewers these are necessary for the export feature.
         */
        echo "\xEF\xBB\xBF"; 
        
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
        $output = fopen( 'php://output', 'w' );
        
        if ( $output ) {
            fputcsv( $output, array( 'Full Name', 'Phone', 'Email', 'Gift Won', 'Date Joined' ) );

            if ( ! empty( $rows ) && is_array( $rows ) ) {
                foreach ( $rows as $r ) {
                    fputcsv( $output, array(
                        isset( $r['fullname'] ) ? $r['fullname'] : '',
                        isset( $r['phone'] )    ? $r['phone']    : '', 
                        isset( $r['email'] )    ? $r['email']    : '',
                        isset( $r['getgift'] )  ? $r['getgift']  : '',
                        isset( $r['created_at'] ) ? $r['created_at'] : ''
                    ) );
                }
            }
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
            fclose( $output );
        }
        exit;
    }

    /**
     * Renders the customer list table with filtering and pagination.
     */
    public function render() {
        // Sanitize Filter Action
        $is_filtering = ( isset( $_GET['start_date'] ) || isset( $_GET['end_date'] ) );
        if ( $is_filtering && ( ! isset( $_GET['zutalw_filter_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['zutalw_filter_nonce'] ) ), 'zutalw_filter_customers_action' ) ) ) {
             echo '<div class="error"><p>' . esc_html__( 'Security check failed.', 'zuta-lucky-wheel' ) . '</p></div>';
        }

        $start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( wp_unslash( $_GET['start_date'] ) ) : '';
        $end_date   = isset( $_GET['end_date'] )   ? sanitize_text_field( wp_unslash( $_GET['end_date'] ) ) : '';

        $all_rows = $this->model->get_all_customers( $start_date, $end_date );
        if ( ! is_array( $all_rows ) ) { $all_rows = []; }

        // --- FIXED PAGINATION LOGIC ---
        $total_items    = count( $all_rows );
        $items_per_page = 10; // Reduced to 10 to ensure pagination shows up in your test data
        $current_page   = isset( $_GET['paged'] ) ? absint( wp_unslash( $_GET['paged'] ) ) : 1;
        $total_pages    = ceil( $total_items / $items_per_page );
        $offset         = ( $current_page - 1 ) * $items_per_page;
        $rows           = array_slice( $all_rows, $offset, $items_per_page );

        $page_slug = isset( $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : '';
        $tab_slug  = isset( $_REQUEST['tab'] )  ? sanitize_text_field( wp_unslash( $_REQUEST['tab'] ) )  : '';

        ?>
      
        <div class="wrap">
            <h1><?php esc_html_e( 'Customers List', 'zuta-lucky-wheel' ); ?></h1>
            <form method="get">
                <input type="hidden" name="page" value="<?php echo esc_attr( $page_slug ); ?>" />
                <?php if ( ! empty( $tab_slug ) ) : ?>
                    <input type="hidden" name="tab" value="<?php echo esc_attr( $tab_slug ); ?>" />
                <?php endif; ?>
                
                <?php wp_nonce_field( 'zutalw_filter_customers_action', 'zutalw_filter_nonce' ); ?>

                <div class="zutalw-filter-bar" style="display:flex; justify-content:space-between; background:#fff; padding:5px; margin:5px 0; border:1px solid #ccd0d4;">
                    <div>
                        <label><strong><?php esc_html_e( 'Filter Date:', 'zuta-lucky-wheel' ); ?></strong></label>
                        <input type="date" name="start_date" value="<?php echo esc_attr( $start_date ); ?>">
                        <input type="date" name="end_date" value="<?php echo esc_attr( $end_date ); ?>">
                        <button type="submit" class="button"><?php esc_html_e( 'Filter', 'zuta-lucky-wheel' ); ?></button>
                    </div>
                    <?php 
                        $export_link = add_query_arg( array(
                            'action'     => 'zutalw_export_customers',
                            'start_date' => $start_date,
                            'end_date'   => $end_date,
                            '_wpnonce'   => wp_create_nonce( 'zutalw_export_customers_nonce' )
                        ), admin_url( 'admin.php' ) ); 
                    ?>
                    <a href="<?php echo esc_url( $export_link ); ?>" class="button button-primary"><?php esc_html_e( 'Export Excel (.csv)', 'zuta-lucky-wheel' ); ?></a>
                </div>

               

                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width:50px;">ID</th>
                            <th><?php esc_html_e( 'Fullname', 'zuta-lucky-wheel' ); ?></th>
                            <th><?php esc_html_e( 'Phone', 'zuta-lucky-wheel' ); ?></th>
                            <th><?php esc_html_e( 'Email', 'zuta-lucky-wheel' ); ?></th>
                            <th><?php esc_html_e( 'Gift Won', 'zuta-lucky-wheel' ); ?></th>
                            <th><?php esc_html_e( 'Date', 'zuta-lucky-wheel' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ( ! empty( $rows ) ) : foreach ( $rows as $r ) : ?>
                        <tr>
                            <td><?php echo esc_html( isset( $r['idcustomer'] ) ? $r['idcustomer'] : '' ); ?></td>
                            <td><strong><?php echo esc_html( isset( $r['fullname'] ) ? $r['fullname'] : '' ); ?></strong></td>
                            <td><?php echo esc_html( isset( $r['phone'] ) ? $r['phone'] : '' ); ?></td>
                            <td><?php echo esc_html( isset( $r['email'] ) ? $r['email'] : '' ); ?></td>
                            <td><?php echo esc_html( isset( $r['getgift'] ) ? $r['getgift'] : '' ); ?></td>
                            <td><?php echo esc_html( isset( $r['created_at'] ) ? $r['created_at'] : '' ); ?></td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr><td colspan="6"><?php esc_html_e( 'No customers found.', 'zuta-lucky-wheel' ); ?></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>

                <?php /** Render Pagination at the BOTTOM */ ?>
                <div style="display:flex; justify-content:flex-end; margin-top:10px;">
                    <?php $this->display_pagination( $total_pages, $current_page ); ?>
                </div>
            </form>
        </div>
        <?php
    }

    
    /**
     * Renders pagination links securely with boxed style.
     */
    private function display_pagination( $total_pages, $current_page ) {
        if ( $total_pages <= 1 ) { return; }

        $page_links = paginate_links( array(
            'base'      => add_query_arg( 'paged', '%#%' ),
            'format'    => '',
            'prev_text' => __( 'Next &raquo;', 'zuta-lucky-wheel' ), // Đảo lại theo logic hiển thị nếu cần
            'next_text' => __( 'Next &raquo;', 'zuta-lucky-wheel' ),
            'prev_text' => __( '&laquo; Prev', 'zuta-lucky-wheel' ),
            'total'     => $total_pages,
            'current'   => $current_page,
            'type'      => 'plain', // Đảm bảo trả về chuỗi HTML để CSS .page-numbers hoạt động
        ) );

        if ( $page_links ) {
            // Bao bọc trong div .zutalw-pagination để nhận CSS ở trên
            echo '<div class="zutalw-pagination">' . wp_kses_post( $page_links ) . '</div>';
        }
    }
}