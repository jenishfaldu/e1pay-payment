<?php
/*
Plugin Name: E1pay payment Gateway for WooCommerce
Plugin URI: 
Description: WooCommerce with e1pay payment gateway.
Version: 1.1

*/
include_once('woo-check-card-class.php');
if (!defined('ABSPATH'))
    exit;
add_action('plugins_loaded', 'woocommerce_e1paypayment_init', 0);
function woocommerce_e1paypayment_init()
{
    if (!class_exists('WC_Payment_Gateway'))
        return;
    /**
     * Gateway class
     */
     
 
     
    class WC_e1paypayment extends WC_Payment_Gateway
    {
        
        public function __construct()
        {
            // Go wild in here
            $this->id           = 'e1paypayment';
            $this->method_title = __('E1pay payment');
            $this->has_fields   = true;
            $this->init_form_fields();
            $this->init_settings();
            $this->title            = $this->settings['title'];
            $this->description      = $this->settings['description'];
            $this->api_token        = $this->settings['api_token'];
            $this->store_id         = $this->settings['store_id'];
            
            $this->card_url        = $this->settings['card_url'];
            $this->host_url         = $this->settings['host_url'];
             $this->validate_url         = $this->settings['validate_url'];
            
            
            $this->e1payment_type   = $this->settings['e1payment_type'];
            $this->status_completed = $this->settings['status_completed'];
            $this->status_cancelled = $this->settings['status_cancelled'];
            $this->status_pending   = $this->settings['status_pending'];
            $this->notify_url       = home_url('/wc-api/WC_e1paypayment');
            $this->msg['message']   = "";
            $this->msg['class']     = "";
          //  add_action('woocommerce_api_wc_tasaction_status', array(  $this, 'check_trasaction'));
            
            
              add_action('woocommerce_api_wc_e1paypayment', array(
                $this,
                'check_e1pay_response'
            ));
            
            add_action('valid-e1paypayment-request', array(
                $this,
                'successful_request'
            ));
            add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
          add_action( 'admin_enqueue_scripts', 'admin_custom_load_scripts' );
            
           
            
            
            if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
                    $this,
                    'process_admin_options'
                ));
            } else {
                add_action('woocommerce_update_options_payment_gateways', array(
                    &$this,
                    'process_admin_options'
                ));
            }
            add_action('woocommerce_receipt_e1paypayment', array(
                $this,
                'receipt_page'
            ));
        }
        

        
        function init_form_fields()
        {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', ''),
                    'type' => 'checkbox',
                    'label' => __('Enable E1pay Payment Module.', ''),
                    'default' => 'no'
                ),
                'e1payment_type' => array(
                    'title' => __('Payment Type', ''),
                    'default' => 'host',
                    'type' => 'select',
                    'options' => array(
                        'card' => __('Card Payment Gateway (Direct)', ''),
                        'host' => __('Payment Gateway (Host)', '')
                    )
                ),
                'title' => array(
                    'title' => __('Title:', ''),
                    'type' => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', ''),
                    'default' => __('E1pay', '')
                ),
                'description' => array(
                    'title' => __('Description:', ''),
                    'type' => 'textarea',
                    'description' => __('This controls the description which the user sees during checkout.', ''),
                    'default' => __('Pay  Credit card  through E1pay Secure Servers.', '')
                ),
                'api_token' => array(
                    'title' => __('API Token:', ''),
                    'type' => 'text',
                    'description' => __('.', ''),
                    'default' => __('MV8xMTEzXzIwMTgwMjI1MDkzMTM0', '')
                ),
                'store_id' => array(
                    'title' => __('Store ID', ''),
                    'type' => 'text',
                    'description' => __('', ''),
                    'default' => __('1113', '')
                ),
                
                'card_url' => array(
                    'title' => __('Card Payment URL', ''),
                    'type' => 'text',
                    'description' => __('', ''),
                    'default' => __('https://my.e1pay.com/api.do', '')
                ),
                
                 'host_url' => array(
                    'title' => __('Host Payment URL', ''),
                    'type' => 'text',
                    'description' => __('', ''),
                    'default' => __('https://my.e1pay.com/processall.do', '')
                ),
                
                
                 'validate_url' => array(
                    'title' => __('Payment Validate URL', ''),
                    'type' => 'text',
                    'description' => __('', ''),
                    'default' => __('https://my.e1pay.com/validate.do', '')
                ),
                
                
                
                'status_completed' => array(
                    'title' => __('If Completed/Successfull/Test Transaction', ''),
                    'default' => 'completed',
                    'type' => 'select',
                    'options' => array(
                        'pending' => __('Pending payment', ''),
                        'processing' => __('Processing', ''),
                        'on-hold' => __('On hold', ''),
                        'completed' => __('Completed', ''),
                        'cancelled' => __('Cancelled', ''),
                        'refunded' => __('Refunded', ''),
                        'failed' => __('Failed', '')
                    )
                ),
                'status_cancelled' => array(
                    'title' => __('If Cancelled/Failed', ''),
                    'default' => 'on-hold',
                    'type' => 'select',
                    'options' => array(
                        'pending' => __('Pending payment', ''),
                        'processing' => __('Processing', ''),
                        'on-hold' => __('On hold', ''),
                        'completed' => __('Completed', ''),
                        'cancelled' => __('Cancelled', ''),
                        'refunded' => __('Refunded', ''),
                        'failed' => __('Failed', '')
                    )
                ),
                'status_pending' => array(
                    'title' => __('If Any error/ No Response', ''),
                    'default' => 'pending',
                    'type' => 'select',
                    'options' => array(
                        'pending' => __('Pending payment', ''),
                        'processing' => __('Processing', ''),
                        'on-hold' => __('On hold', ''),
                        'completed' => __('Completed', ''),
                        'cancelled' => __('Cancelled', ''),
                        'refunded' => __('Refunded', ''),
                        'failed' => __('Failed', '')
                    )
                )
            );
        }
        /**
         * Admin Panel Options
         * - Options for bits like 'title' and availability on a country-by-country basis
         **/
        public function admin_options()
        {
            echo '<h3>' . __('E1pay Payment Gateway', '') . '</h3>';
            echo '<p>' . __('E1pay is most popular payment gateway for online shopping') . '</p>';
            echo '<table class="form-table">';
            $this->generate_settings_html();
            echo '</table>';
        }
        public function validate_fields()
        {
            global $woocommerce;
            if ($this->e1payment_type == 'card') {
                
                $billing_creditcard = str_replace(' ', '', $_POST['billing_creditcard']);
                if (!WC_E_CHECK_CARD::e_valid_card_number($billing_creditcard)) {
                    wc_add_notice(__('Credit card number you entered is invalid.', 'woocommerce'), 'error');
                }
               
                if (!WC_E_CHECK_CARD::e_valid_expiry($_POST['billing_expdatemonth'], $_POST['billing_expdateyear'])) {
                    wc_add_notice(__('Card expiration date is not valid.', 'woocommerce'), 'error');
                }
                if (!WC_E_CHECK_CARD::e_valid_cvv_number($_POST['billing_ccvnumber'])) {
                    wc_add_notice(__('Card verification number (CVV) is not valid. You can find this number on your credit card.', 'woocommerce'), 'error');
                }
            }
        }
        
        
        public function payment_scripts() {
            
     if ($this->e1payment_type == 'card') {
           

             wp_enqueue_script( 'woocommerce_e1_custom',plugins_url( '/assets/js/custom.js', __FILE__ ), array( 'jquery' ) );
              
          
             
     }
        }
        
        
        /**
         *  There are no payment fields for E1pay, but we want to show the description if set.
         **/
        function payment_fields()
        {
            if ($this->e1payment_type == 'host') {
                if ($this->description)
                    echo wpautop(wptexturize($this->description));
            } else {
                $billing_creditcard = isset($_REQUEST['billing_creditcard']) ? esc_attr($_REQUEST['billing_creditcard']) : '';
?>
       <p class="form-row validate-required">
            <?php
                $card_number_field_placeholder = __('Card Number', 'woocommerce');
?>            
            <label><?php
                _e('Card Number', 'woocommerce');
?> <span class="required">*</span></label>
            <input class="input-text check_creditcard" type="text" size="19" maxlength="19" name="billing_creditcard" value="<?php
                echo $billing_creditcard;
?>" placeholder="<?php   echo $card_number_field_placeholder; ?>"  />



        </p>         
          
        <div class="clear"></div>
        <p class="form-row form-row-first">
            <label><?php
                _e('Expiration Date', 'woocommerce');
?> <span class="required">*</span></label>
            <select name="billing_expdatemonth">
                <option value=1>01</option>
                <option value=2>02</option>
                <option value=3>03</option>
                <option value=4>04</option>
                <option value=5>05</option>
                <option value=6>06</option>
                <option value=7>07</option>
                <option value=8>08</option>
                <option value=9>09</option>
                <option value=10>10</option>
                <option value=11>11</option>
                <option value=12>12</option>
            </select>
            <select name="billing_expdateyear">
            <?php
                $today = (int) date('Y', time());
                for ($i = 0; $i < 12; $i++) {
?>
               <option value="<?php
                    echo $today;
?>"><?php
                    echo $today;
?></option>
            <?php
                    $today++;
                }
?>
           </select>            
        </p>
        <div class="clear"></div>
        <p class="form-row form-row-first validate-required">
            <?php
                $cvv_field_placeholder = __('Card Verification Number (CVV)', 'woocommerce');
?>
           <label><?php
                _e('Card Verification Number (CVV)', 'woocommerce');
?> <span class="required">*</span></label>
            <input class="input-text" type="text" size="4" maxlength="4" name="billing_ccvnumber" value="" placeholder="<?php
                echo $cvv_field_placeholder;
?>" />
        </p>
      
        <div class="clear"></div>
        
        <?php
            }
        }
        /**
         * Receipt Page
         **/
        function receipt_page($order)
        {
            if ($this->e1payment_type == 'host') {
                echo '<p>' . __('Thank you for your order, please click the button below to pay with E1pay.', '') . '</p>';
                echo $this->generate_e1pay_form($order);
            }
        }
        /**
         * Process the payment and return the result
         **/
        function process_payment($order_id)
        {
            $order = new WC_Order($order_id);
            if ($this->e1payment_type == 'card') {
                $order_id = $order_id;
                global $woocommerce;
                $items = $woocommerce->cart->get_cart();
                foreach ($items as $item => $values) {
                    $_product        = wc_get_product($values['data']->get_id());
                    $product_title[] = $_product->get_title();
                }
                $product_title              = implode(',', $product_title);
                $the_currency               = get_woocommerce_currency();
                $the_order_total            = @$order->order_total;
                $gateway_url                =  $this->card_url;
                $curlPost                   = array();
                //<!--Replace of 3 very important parameters * your product API code -->
                $curlPost["api_token"]      = $this->api_token; // Store API Token 
                $curlPost["store_id"]       = $this->store_id; // Store Id 
                //<!--default (fixed) value * default -->
                $curlPost["cardsend"]       = "curl";
                $curlPost["client_ip"]      = $_SERVER['REMOTE_ADDR'];
                $curlPost["action"]         = "product";
                //<!--product price,curr and product name * by cart total amount -->
                $curlPost["price"]          = $the_order_total;
                $curlPost["curr"]           = $the_currency;
                $curlPost["product_name"]   = $product_title;
                //<!--billing details of .* customer -->
                $curlPost["ccholder"]       = $order->billing_first_name;
                $curlPost["ccholder_lname"] = $order->billing_last_name;
                $curlPost["email"]          = $order->billing_email;
                $curlPost["bill_street_1"]  = $order->billing_address_1;
                $curlPost["bill_street_2"]  = $order->billing_address_2;
                $curlPost["bill_city"]      = $order->billing_city;
                $curlPost["bill_state"]     = $order->billing_state;
                $curlPost["bill_country"]   = WC()->countries->countries[$order->get_billing_country()];
                $curlPost["bill_zip"]       = $order->billing_postcode;
                $curlPost["bill_phone"]     = $order->billing_phone;
                $curlPost["id_order"]       = $order_id;
                //<!--card details of .* customer -->
                $curlPost["ccno"]           = $_POST['billing_creditcard'];
                $curlPost["ccvv"]           = $_POST['billing_ccvnumber'];
                $curlPost["month"]          = $_POST['billing_expdatemonth'];
                $curlPost["year"]           = $_POST['billing_expdateyear'];
                //$curlPost["notes"]="Remark for transaction";
                $protocol                   = isset($_SERVER["HTTPS"]) ? 'https://' : 'http://';
                $referer                    = $protocol . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
                $curl_cookie                = "";
                $curl                       = curl_init();
                curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
                curl_setopt($curl, CURLOPT_URL, $gateway_url);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
                curl_setopt($curl, CURLOPT_REFERER, $referer);
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
                curl_setopt($curl, CURLOPT_TIMEOUT, 300);
                curl_setopt($curl, CURLOPT_HEADER, 0);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_COOKIE, $curl_cookie);
                $response = curl_exec($curl);
                $results  = json_decode($response, true);
                $status   = $results["status"];
                
               
              
                
           if(isset($results["status"]))
           {
             update_post_meta( $order_id, 'payment_amount', $results['amt'] );
            update_post_meta( $order_id, 'payment_currency', $results['curr'] );
            update_post_meta( $order_id, 'payment_date', $results['tdate'] );
            update_post_meta( $order_id, 'payment_descriptor', $results['descriptor'] );
            update_post_meta( $order_id, 'payment_status', $results["status"] );
            update_post_meta( $order_id, 'transaction_id', $results['transaction_id'] );
            
            
            $order->add_order_note(__('<button id="'.$results['transaction_id'].'" name="current-status" class="button-primary woocommerce-validate-current-status" type="button" value="Validate Current Status">Validate Current Status</button>', ''));
            
           }
                 
                 
                 
             
                 
                
                
                if ($status == "Completed" || $status == "Success" || $status == "Test" || $status == "Test Transaction") {
                    // Payment successful
                    $order->add_order_note(__('E1pay  complete payment.', ''));
                    $order->payment_complete();
                    $order->update_status($this->status_completed);
                    // this is important part for empty cart
                    $woocommerce->cart->empty_cart();
                    // Redirect to thank you page
                    return array(
                        'result' => 'success',
                        'redirect' => $this->get_return_url($order)
                    );
                } else if ($status == "Failed" || $status == "Cancelled") {
               
                    
                     wc_add_notice( sprintf( __($results["reason"].' <a href="%s" class="button alt">Return to Checkout Page</a>'), get_permalink( get_option('woocommerce_checkout_page_id') ) ), 'error' );
                    
                    
                    $order->add_order_note('Error: ' . $results["reason"]);
                    $order->update_status($this->status_cancelled);
                } else {
                    //transiction fail
                    wc_add_notice( sprintf( __($results["reason"].' <a href="%s" class="button alt">Return to Checkout Page</a>'), get_permalink( get_option('woocommerce_checkout_page_id') ) ), 'error' );
                    $order->add_order_note('Error: ' . $results["reason"]);
                    $order->update_status($this->status_pending);
                }
            }
            update_post_meta($order_id, '_post_data', $_POST);
            return array(
                'result' => 'success',
                'redirect' => $order->get_checkout_payment_url(true)
            );
        }
        
        
       
        
        /**
         * Check for valid E1pay server callback
         **/
        function check_e1pay_response()
        {
            global $woocommerce;
            $msg['class']   = 'error';
            $msg['message'] = "Thank you for shopping with us. However, the transaction has been declined.";
            $json_response  = $_POST;
            
              $order_id       = $json_response['id_order'];
            $order_id = explode('_', $order_id);
            $order_id = $order_id[0];
            
            
            
            $check_transaction_id = get_post_meta( $order_id, 'transaction_id', true );
              if(isset($json_response["status"]))
           {
             update_post_meta( $order_id, 'payment_amount', $json_response['amt'] );
            update_post_meta( $order_id, 'payment_currency', $json_response['curr'] );
            update_post_meta( $order_id, 'payment_date', $json_response['tdate'] );
            update_post_meta( $order_id, 'payment_descriptor', $json_response['descriptor'] );
            update_post_meta( $order_id, 'payment_status', $json_response["status"] );
            update_post_meta( $order_id, 'transaction_id', $json_response['transaction_id'] );
            
           }
            
            
            
          
            if (isset($json_response['transaction_id'])) {
                
                if ($order_id != '') {
                    try {
                        $order        = new WC_Order($order_id);
                        if(empty( $check_transaction_id)){
                        $order->add_order_note(__('<button id="'.$json_response['transaction_id'].'" name="current-status" class="button-primary woocommerce-validate-current-status" type="button" value="Validate Current Status">Validate Current Status</button>', ''));
                        }
                        $order_status = $json_response['status'];
                        if ($order->get_status() !== 'Completed') {
                            if ($order_status == "Completed" || $order_status == "Success" || $order_status == "Test" || $order_status == "Test Transaction") {
                                $transauthorised = true;
                                $msg['message']  = "Thank you for shopping with us. Your account has been charged and your transaction is successful. We will be shipping your order to you soon.";
                                $msg['class']    = 'success';
                                if ($order->status != 'processing') {
                                    $order->payment_complete();
                                    $order->update_status($this->status_completed);
                                       if(empty( $check_transaction_id)){
                                    $order->add_order_note('E1pay payment successful<br/>Transaction ID: ' . $json_response['transaction_id']);
                                       }
                                    $woocommerce->cart->empty_cart();
                                }
                            } else if ($status == "Failed" || $status == "Cancelled") {
                                $msg['class']   = 'error';
                                $msg['message'] = " We are waiting for your order status from the bank-Transaction pending";
                                $order->update_status($this->status_cancelled);
                            } else {
                                $msg['class']   = 'error';
                                $msg['message'] = "Thank you for shopping with us. However, the transaction has been declined.";
                                $order->update_status($this->status_pending);
                            }
                            /* if($transauthorised==false){
                            $order -> update_status('failed');
                            $order -> add_order_note('Failed');
                            $order -> add_order_note($this->msg['message']);
                            }*/
                        }
                    }
                    catch (Exception $e) {
                        $msg['class']   = 'error';
                        $msg['message'] = "Thank you for shopping with us. However, the transaction has been declined.";
                    }
                }
            }
            if (function_exists('wc_add_notice')) {
                wc_add_notice($msg['message'], $msg['class']);
            } else {
                if ($msg['class'] == 'success') {
                    $woocommerce->add_message($msg['message']);
                } else {
                    $woocommerce->add_error($msg['message']);
                }
                $woocommerce->set_messages();
            }
           
            $redirect_url = $this->get_return_url($order);
            wp_redirect($redirect_url);
            exit;
        }
        /*
      
        
        /**
         * Generate  button link
         **/
        public function generate_e1pay_form($order_id)
        {
            global $woocommerce;
            $order     = new WC_Order($order_id);
            $order_id  = $order_id;
            $post_data = get_post_meta($order_id, '_post_data', true);
            update_post_meta($order_id, '_post_data', array());
            $the_currency    = get_woocommerce_currency();
            $the_order_total = @$order->order_total;
            if ($this->e1payment_type == 'host') {
                $form = '';
                wc_enqueue_js('
                    $.blockUI({
                        message: "<h2>Please wait while we process your request</h2><p>Since this may take a few seconds, please do not close/refresh this window.</p>",
                        baseZ: 99999,
                        overlayCSS:
                        {
                            background: "#fff",
                            opacity: 0.6
                        },
                        css: {
                            padding:        "20px",
                            zindex:         "9999999",
                            textAlign:      "center",
                            color:          "#555",
                            border:         "3px solid #aaa",
                            backgroundColor:"#fff",
                            cursor:         "wait",
                            lineHeight:     "24px",
                        }
                    });
                jQuery("#submit_e1pay_payment_form").click();
                ');
                $targetto = 'target="_top"';
                $items    = $woocommerce->cart->get_cart();
                foreach ($items as $item => $values) {
                    $_product        = wc_get_product($values['data']->get_id());
                    $product_title[] = $_product->get_title();
                }
                $product_title      = implode(',', $product_title);
                $e1pay_args_array   = array();
                $e1pay_args_array[] = "<input type='hidden' name='api_token' value='" .$this->api_token. "'/>";
                $e1pay_args_array[] = "<input type='hidden' name='store_id' value='" .$this->store_id. "'/>";
                $e1pay_args_array[] = '<input type="hidden" name="cardsend" value="CHECKOUT"/>';
                $e1pay_args_array[] = '<input type="hidden" name="action" value="product"/>';
                $e1pay_args_array[] = '<input type="hidden" name="price" value="' . $the_order_total . '"/>';
                $e1pay_args_array[] = '<input type="hidden" name="curr" value="' . $the_currency . '"/>';
                $e1pay_args_array[] = '<input type="hidden" name="product_name" value="' . $product_title . ' "/>';
                $e1pay_args_array[] = '<input type="hidden" name="ccholder" value="' . @$order->billing_first_name . '"/>';
                $e1pay_args_array[] = '<input type="hidden" name="ccholder_lname" value="' . @$order->billing_last_name . '"/>';
                $e1pay_args_array[] = '<input type="hidden" name="email" value="' . @$order->billing_email . '"/>';
                $e1pay_args_array[] = '<input type="hidden" name="bill_street_1" value="' . @$order->billing_address_1 . '"/>';
                $e1pay_args_array[] = '<input type="hidden" name="bill_street_2" value="' . @$order->billing_address_2 . '"/>';
                $e1pay_args_array[] = '<input type="hidden" name="bill_city" value="' . @$order->billing_city . '"/>';
                $e1pay_args_array[] = '<input type="hidden" name="bill_state" value="' . @$order->billing_state . '"/>';
                $e1pay_args_array[] = '<input type="hidden" name="bill_country" value="' . WC()->countries->countries[$order->get_billing_country()] . '"/>';
                $e1pay_args_array[] = '<input type="hidden" name="bill_zip" value="' . @$order->billing_postcode . '"/>';
                $e1pay_args_array[] = '<input type="hidden" name="bill_phone" value="' . @$order->billing_phone . '"/>';
                $e1pay_args_array[] = '<input type="hidden" name="id_order" value="' . $order_id . '"/>';
                $e1pay_args_array[] = '<input type="hidden" name="notify_url" value="' . $this->notify_url . '"/>';
                $e1pay_args_array[] = '<input type="hidden" name="success_url" value="' . $this->notify_url . '"/>';
                $e1pay_args_array[] = '<input type="hidden" name="error_url" value="' . $this->notify_url . '"/>';
                $form .= '<form action="'. $this->host_url .'" method="post" id="e1pay_payment_form"  ' . $targetto . '>
                ' . implode('', $e1pay_args_array) . '
                <!-- Button Fallback -->
                <div class="payment_buttons">
                <input type="submit" class="button alt" id="submit_e1pay_payment_form" value="' . __('Pay via E1pay', 'woocommerce') . '" /> <a class="button cancel" href="' . esc_url($order->get_cancel_order_url()) . '">' . __('Cancel order &amp; restore cart', 'woocommerce') . '</a>
                </div>
                <script type="text/javascript">
                jQuery(".payment_buttons").hide();
                </script>
                </form>';
                return $form;
            }
        }
    }
    
    
    /**
     * Add the Gateway to WooCommerce
     **/
    function woocommerce_add_e1paypayment_gateway($methods)
    {
        $methods[] = 'WC_e1paypayment';
        return $methods;
    }
    add_filter('woocommerce_payment_gateways', 'woocommerce_add_e1paypayment_gateway');
}


 function admin_custom_load_scripts( $hook ) {
   
    wp_enqueue_script( 'my_custom_script', plugin_dir_url( __FILE__ ) . 'assets/js/admin-custom.js', array(), '1.0' );
}

    

add_action( 'admin_enqueue_scripts', 'admin_custom_load_scripts' );


add_action('wp_ajax_check_transaction_status', 'check_transaction_status');

function check_transaction_status(){


$validateurl=get_option( 'woocommerce_e1paypayment_settings', true );

$transaction_id=$_POST['tra_id'];
$url=$validateurl['validate_url']."?transaction_id=".$transaction_id;
	
	
	
     	$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_POST,0);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false); 
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST, false);
		$response = curl_exec($ch);
		curl_close($ch);
		$json_response = json_decode($response,true);
		
		$htmlresponse="";
		foreach($json_response as $key=>$data)
		{
		    
		  	$htmlresponse.="<br><b>".$key.":</b>".$data;  
		    
		    
		}
	echo $htmlresponse;
	exit;


}



?>