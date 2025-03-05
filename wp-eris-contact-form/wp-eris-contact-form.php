<?php
/*
Plugin Name: WP Eris Contact Form
Description: A simple contact form plugin for WordPress.
Version: 1.0
Author: Your Name
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

add_shortcode('contact_form', 'eris_contact_form');

function eris_contact_form(){
    $option = get_option('eris_contact_form');
    if(isset($_POST['contact-name'])){
        $name = $_POST['contact-name'];
        $email = $_POST['contact-email'];
        $message = $_POST['contact-message'];

        //$site_key = $option['site_key'];
        $secret_key = $option['secret_key'];
        $captcha = $_POST['cf-turnstile-response'];
        $ip = $_SERVER['REMOTE_ADDR'];
        $url_path = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
        $data = array('secret' => $secret_key, 'response' => $captcha, 'remoteip' => $ip);
         
        $options = array(
            'http' => array(
            'method' => 'POST',
            'content' => http_build_query($data))
        );
         
        $stream = stream_context_create($options);
         
        $result = file_get_contents(
        $url_path, false, $stream);
         
        $response =  $result;
        
        $responseKeys = json_decode($response,true);
        //print_r ($responseKeys);
        $message = '';
        if($responseKeys["success"] !== true) {
            return '<div class="alert alert-danger">You are a robot! Go away!</div>';
        } else { 
            // validate email adress 
            $valid = false;
            if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $valid = true;
                $message .= '<div class="alert alert-danger">Invalid email address</div>';
            }
            if(!empty($name) && !empty($email) && !empty($_POST['contact-message']) && $valid == false) {
                $to = get_option('admin_email');
                $subject = 'Contact form submission from ' . get_bloginfo('name');
                $body = '<p><strong>Name: </strong>' . $name . '</p>';
                $body .= '<p><strong>Email: </strong>' . $email . '</p>';
                $body .= '<p><strong>Message: </strong>' . $_POST['contact-message'] . '</p>';
                $headers = array('Content-Type: text/html; charset=UTF-8');
                wp_mail($to, $subject, $body, $headers);
                $message .= '<div class="alert alert-success">Thank you for your message</div>';
            } else {
                $message .= '<div class="alert alert-danger">All fields are required</div>';
            }
            return $message;
        }
    
    }else{
        ob_start();
        ?>
        <form action="<?=get_permalink();?>" method="post" id="contact-form">
            <div class="form-group">
            <label for="name"><?=__('Name', 'wp-eris-contact-form');?>:</label><br />
            <input type="text" name="contact-name" id="name" class="form-control" required size="40">
            </div>
            <div class="form-group">
            <label for="email"><?=__('Email', 'wp-eris-contact-form');?>:</label><br />
            <input type="email" name="contact-email" id="email" class="form-control" required size="40">
            </div>
            <div class="form-group">
            <label for="message"><?=__('Message',  'wp-eris-contact-form')?>:</label><br />
            <textarea name="contact-message" id="contact-message" class="form-control" required cols="40" rows="10"></textarea>
            </div>
            <div class="form-group">
            <div class="cf-turnstile" data-sitekey="<?= $option['site_key']; ?>" data-callback="onsubmit"></div>
            </div>
            <button type="submit" class="btn"><?=__('Send',  'wp-eris-contact-form');?></button>
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js"></script>
            <script>
            function onsubmit(token) {
                //document.getElementById("contact-form").submit();
            }
            </script>
        </form>
        <?php
        $new_content = ob_get_clean();
        return $new_content;
    }
}

add_action('admin_menu', 'eris_contact_form_menu');

function eris_contact_form_menu() {
    add_options_page(
        'Eris Contact Form Settings',
        'Eris Contact Form',
        'manage_options',
        'eris-contact-form',
        'eris_contact_form_options_page'
    );
}

function eris_contact_form_options_page() {
    ?>
    <div class="wrap">
        <h1><?=__('Eris Contact Form Settings', 'wp-eris-contact-form');?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('eris_contact_form_options_group');
            do_settings_sections('eris-contact-form');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', 'eris_contact_form_settings');

function eris_contact_form_settings() {
    register_setting('eris_contact_form_options_group', 'eris_contact_form', 'sanitize_callback');

    add_settings_section(
        'eris_contact_form_section',
        __('General Settings', 'wp-eris-contact-form'),
        null,
        'eris-contact-form'
    );

    add_settings_field(
        'site_key',
        __('Site Key', 'wp-eris-contact-form'),
        'eris_contact_form_site_key_callback',
        'eris-contact-form',
        'eris_contact_form_section'
    );

    add_settings_field(
        'secret_key',
        __('Secret Key', 'wp-eris-contact-form'),
        'eris_contact_form_secret_key_callback',
        'eris-contact-form',
        'eris_contact_form_section'
    );
}

function eris_contact_form_site_key_callback() {
    $option = get_option('eris_contact_form');
    ?>
    <input type="text" name="eris_contact_form[site_key]" value="<?= isset($option['site_key']) ? esc_attr($option['site_key']) : ''; ?>" class="regular-text">
    <?php
}

function eris_contact_form_secret_key_callback() {
    $option = get_option('eris_contact_form');
    ?>
    <input type="text" name="eris_contact_form[secret_key]" value="<?= isset($option['secret_key']) ? esc_attr($option['secret_key']) : ''; ?>" class="regular-text">
    <?php
}

function sanitize_callback($input) {
    $new_input = array();
    if (isset($input['site_key'])) {
        $new_input['site_key'] = sanitize_text_field($input['site_key']);
    }
    if (isset($input['secret_key'])) {
        $new_input['secret_key'] = sanitize_text_field($input['secret_key']);
    }
    return $new_input;
}