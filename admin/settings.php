<?php
/** @wordpress-plugin
 * Author:            GHAX
 * Author URI:        https://leadtrail.io/
 */

	global $lmPluginName;
	global $PluginTextDomain;
        
        
        $about_co_show = $_email_show = $_payment_show = $_gen_show = $shipping_show = 0;
        $about_co_tab = $_email_tab = $_payment_tab = $_gen_tab = $shipping_tab = '';
        
        if(isset($_REQUEST['tab'])){
            if($_REQUEST['tab']=='_about_co'){
                $about_co_tab = 'nav-tab-active';
                $about_co_show = 1;
            }elseif($_REQUEST['tab']=='_emails'){
                $_email_tab = 'nav-tab-active';
                $_email_show = 1;
            }elseif($_REQUEST['tab']=='_payments'){
                $_payment_tab = 'nav-tab-active';
                $_payment_show = 1;
            }elseif($_REQUEST['tab']=='_general'){
                $_gen_tab = 'nav-tab-active';
                $_gen_show = 1;
            }else{
                $shipping_tab = 'nav-tab-active';
                $shipping_show = 1;
            }
        }else{
            $shipping_tab = 'nav-tab-active';
            $shipping_show = 1;
        }
        
?>

<div class="wrap">
    
        <h2 class="nav-tab-wrapper">
            <a href="<?=admin_url('admin.php?page=deliveryman&tab=_shipping_stngs');?>" title="Shipping carrier & pricing" class="nav-tab <?php echo $shipping_tab;?>"><?php _e('SHIPPING CARRIER & PRICING');?></a>
            <a href="<?=admin_url('admin.php?page=deliveryman&tab=_emails');?>" title="Email templates" class="nav-tab <?php echo $_email_tab;?>"><?php _e('EMAIL TEMPLATES');?></a>
            <a href="<?=admin_url('admin.php?page=deliveryman&tab=_payments');?>" title="Payments" class="nav-tab <?php echo $_payment_tab;?>"><?php _e('PAYMENTS');?></a>
            <a href="<?=admin_url('admin.php?page=deliveryman&tab=_general');?>" title="General" class="nav-tab <?php echo $_gen_tab;?>"><?php _e('GENERAL INFO');?></a>
            <a href="<?=admin_url('admin.php?page=deliveryman&tab=_about_co');?>" title="About" class="nav-tab <?php echo $about_co_tab;?>"><?php _e('ABOUT');?></a>
        </h2>
        
    <?php show_error_message();?>
    <?php

        if($about_co_show==1){
            ?>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label><?php _e('Developed By:');?></label>
                            </th>
                            <td>cWebConsultants India
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label><?php _e('Support Email Address:');?></label>
                            </th>
                            <td><a href="mailto:info@cwebconsultants.com">info@cwebconsultants.com</a>
                            </td>
                        </tr>  
                    </tbody>
                </table>
        <?php    
        }elseif($shipping_show==1){ 
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/setting_tabs/shipping.php';
        }
        elseif($_gen_show==1){ 
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/setting_tabs/general.php';
        }
        elseif($_email_show==1){ 
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/setting_tabs/email.php';
        }
        elseif($_payment_show==1){ 
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/setting_tabs/payment.php';
        }
    ?>
    
        <div class="clear"></div>
</div>