<?php

//Replace FORMID with your ID of your gravity form
add_action( 'gform_confirmation_FORMID', 'xendit_send_payment', 10, 3 );


function xendit_send_payment( $confirmation, $form, $entry ) {

    $entry_id = rgar( $entry, 'id' ); //Entry ID
    $checker = rgar( $entry, '137' ); 
	$tenure = rgar( $entry, '33' );
    $credit_card = rgar( $entry, '164' );
    $ewallets = rgar( $entry, '165' );
	$payer_email1 = rgar( $entry, '146' );
	$payer_email2 = rgar( $entry, '169' );
    $confirmation = "";
	$payer_email = "";
	
	
	$customer_fname = rgar( $entry, '143.3' );
	$customer_lname = rgar( $entry, '143.6' );
	$customer_phone = rgar( $entry, '145' );
	
	//Store your own API key in the $username variable (You will get it from Xendit Dashboard)
    // $username = 'XXXXXXX'; //Live
	// $username = 'XXXXXXX'; // Sandbox
      $password = '';

    //Send to xendit based on Field Option (For Recurring)
 	if($tenure == "Monthly"){

        $endpoint_url = 'https://api.xendit.co/recurring_payments'; 
        $timestamp = time();
        $reference_id = 'order-id-' . $timestamp; //Unique ID
        $body = array(
            'external_id' => $reference_id,
            'currency' => 'PHP',
            'amount' => rgar( $entry, '133' ),
            'interval' => 'MONTH',
			'interval_count' => '1',
			'total_recurrence' => '12',
			'payer_email' => $payer_email2,
			'success_redirect_url' => "https://abc.com/thank-you/?res=true&bgf_token=$entry_id"
            );
        
        $headers = array(
            'Authorization' => 'Basic ' . base64_encode( $username.':'.$password ),
            'Content-Type' => 'application/json',
        );
        
        $json_body = json_encode($body);
     
        $response = wp_remote_post( $endpoint_url, array( 'headers' => $headers, 'body' => $json_body, 'method' => 'POST', 'data_format' => 'body' ) );
		GFCommon::log_debug( 'gform_confirmation: response => ' . print_r( $response, true ) );
        if ( is_wp_error( $response ) ) {
             return false;
        }
        $body_data =  wp_remote_retrieve_body($response);
        $decode_arr = json_decode($body_data, true);

        $response_acions = $decode_arr;
        $checkout_url = $response_acions['last_created_invoice_url'];
        $confirmation = array( 'redirect' => $checkout_url );		
		
		
	}else{
	//Checking what user chosen
    if($ewallets != '' && $ewallets != 'Credit_Card'){
        $endpoint_url = 'https://api.xendit.co/ewallets/charges';
        $timestamp = time();
        $reference_id = 'order-id-' . $timestamp;
        $body = array(
            'reference_id' => $reference_id,
            'currency' => 'PHP',
            'amount' => rgar( $entry, '133' ),
            'checkout_method' => 'ONE_TIME_PAYMENT',
            'channel_code' => rgar( $entry, '165' ),
            'channel_properties' => array('success_redirect_url' =>  "https://abc.com/xyz/?res=true&bgf_token=$entry_id", 'failure_redirect_url' =>  'https://abc.com', 'cancel_redirect_url' =>  'https://abc.com/xyz/' )
            );
        
        $headers = array(
            'Authorization' => 'Basic ' . base64_encode( $username.':'.$password ),
            'Content-Type' => 'application/json',
        );
        
        $json_body = json_encode($body);
     
        $response = wp_remote_post( $endpoint_url, array( 'headers' => $headers, 'body' => $json_body, 'method' => 'POST', 'data_format' => 'body' ) );
        if ( is_wp_error( $response ) ) {
             return false;
        }
        $body_data =  wp_remote_retrieve_body($response);
        $decode_arr = json_decode($body_data, true);

        $response_acions = $decode_arr['actions'];
        $checkout_url = $response_acions['desktop_web_checkout_url'];
        $confirmation = array( 'redirect' => $checkout_url );

    }else {

		if($payer_email1 == ''){
			$payer_email = $payer_email2;
		}else{
			$payer_email = $payer_email1;
		}
		
		if($customer_fname != '' || $customer_lname != '' || $customer_phone != ''){
			
			$customer_arr['customer']['given_names'] = $customer_fname;
			$customer_arr['customer']['surname'] = $customer_lname;
			$customer_arr['customer']['mobile_number'] = $customer_phone;
		}
		
        $endpoint_url = 'https://api.xendit.co/v2/invoices';
        $timestamp = time();
        $external_id = 'order-id-' . $timestamp;
        $body = array(
            'external_id' => $external_id,
            'amount' => rgar( $entry, '133' ),
            'payer_email' => $payer_email,
            'description' => "Medicare Plus",
			'success_redirect_url' => "https://abc.com/xyz/?res=true&bgf_token=$entry_id",
			'payment_methods' => array('CREDIT_CARD'),
			 $customer_arr
			
           );
        
        $headers = array(
            'Authorization' => 'Basic ' . base64_encode( $username.':'.$password ),
            'Content-Type' => 'application/json',
        );
        GFCommon::log_debug( 'gform_after_submission: body => ' . print_r( $body, true ) ); //Saving Response in Gravity Forms logs
        
        $json_body = json_encode($body);
     
        $response = wp_remote_post( $endpoint_url, array( 'headers' => $headers, 'body' => $json_body, 'method' => 'POST', 'data_format' => 'body' ) );
        GFCommon::log_debug( 'gform_confirmation: response => ' . print_r( $response, true ) );
        if ( is_wp_error( $response ) ) {
             return false;
        }
        $body_data =  wp_remote_retrieve_body($response);
        $decode_arr = json_decode($body_data, true);

        $checkout_url = $decode_arr['invoice_url'];
        $confirmation = array( 'redirect' => $checkout_url );

    }
	}	
    return $confirmation;
    
    
}

