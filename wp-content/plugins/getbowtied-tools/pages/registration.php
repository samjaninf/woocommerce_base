<div class="stripe_top" style="background-color: <?php echo $getbowtied_settings['stripe_color']; ?>"></div>
<div class="wrap about-wrap getbowtied-about-wrap getbowtied-registration-wrap">
	
    <?php require_once('global/pages-header.php'); ?>

    <?php 

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['getbowtied_key']))
        {
           $rsp = Getbowtied_Admin_Pages::validate_license(trim($_POST['getbowtied_key']));
        }
        elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST['getbowtied_key']) && empty($_POST['action']))
        {
            $rsp = 'Please fill out the product key.';
        }
        elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['action'] == 'delkey')
        {
            delete_option("getbowtied_".THEME_SLUG."_license");
            delete_option("getbowtied_".THEME_SLUG."_license_expired");
        }
    ?>


    <?php 
        $activated = get_option("getbowtied_".THEME_SLUG."_license");
        $expired   = get_option("getbowtied_".THEME_SLUG."_license_expired");

        if ($activated == ''): 
    ?>

    	<div class="getbowtied-registration-form">
            <div class="inner">
                <div class="center">
                    <p>
                        <?php echo __("Connect this domain name to your license to receive updates for both the theme and related plugins.", "getbowtied"); ?>
                    </p>
                </div>

                <!-- <div class="right">
                    <a href="#" class="button"><span class="dashicons dashicons-info"></span> <?php echo __('Help with Product Keys', 'getbowtied'); ?></a>
                </div> -->

                <div class="clear"></div>
            </div>

            <?php 
                if ( ! empty( $rsp['text'] ))
                {
                    echo '<div class="key_error">'.$rsp['text'].'</div>';
                }
            ?>

            <div class="inner steps <?php if (!empty($rsp['domain'])) echo "has-error"; ?>">

                <div class="step step-1">
                    <?php echo __('Step 1', 'getbowtied'); ?><br/>
                    <img src="<?php echo GETBOWTIED_TOOLS_URL . 'img/hand-key.svg'; ?>" />
                    <a class="button generate" href="<?php echo $getbowtied_settings['getbowtied_url']; ?>?page=license&ref=<?php echo urlencode(site_url()); ?>" target="_blank"><?php echo __('Generate a Product Key', 'getbowtied'); ?></a>
                    <br/><?php echo __('for this domain name', 'getbowtied'); ?>
                </div>

                <div class="step step-1 step-error">
                <?php echo __("Site URL Mismatch. You generated a key for:", "getbowtied"); ?><br/>
                <strong><?php echo $rsp['domain']; ?></strong><br/><br/>
                <?php echo __("but the correct domain name is:", "getbowtied"); ?><br/>
                <strong><?php echo get_site_url(); ?></strong><br/><br/>
                <?php echo __("It's the same thing when you type it in your browser because you're being redirected but for the Product Keys system, it's a different URL.", "getbowtied"); ?>
                <br/><br/><?php echo __("Go back to Generate A New Product Key and if you won't be able to figure it out, reach out to the support team.", "getbowtied"); ?>
                <a class="button generate" href="<?php echo $getbowtied_settings['getbowtied_url']; ?>" target="_blank"><?php echo __('Generate a Product Key', 'getbowtied'); ?></a>
                    
                </div>

                <div class="step step-2">
                    <?php echo __('Step 2', 'getbowtied'); ?><br/>
                    <img src="<?php echo GETBOWTIED_TOOLS_URL . 'img/hand-down.svg'; ?>" />
                    <form id="getbowtied_product_registration" action="" method="POST">
                        <input type="hidden" name="register" value="true" />
                        <input type="text" name="getbowtied_key" id="getbowtied_key" placeholder="Paste your Product Key Here" value="" />
                        <br /><br />
                        <button class="button button-primary getbowtied-register" type="submit"><?php echo __( "Activate Product Key", "getbowtied" ); ?></button>
                    </form>
                </div>

                <div class="clear"></div>
        </div>

    <?php elseif ($activated != '' && $expired != 1): ?>

        <div class="getbowtied-registration-done">
                
            <img src="<?php echo GETBOWTIED_TOOLS_URL . 'img/hand-ok.svg'; ?>" />
            <h2><?php echo __("Product Key Active!", "getbowtied"); ?></h2>

            <div class="getbowtied_product_key_wrapper">
                <span class="getbowtied_product_key">
                    <?php echo get_option("getbowtied_".THEME_SLUG."_license");?>
                </span>

                <a class="button button-primary" href="<?php echo $getbowtied_settings['getbowtied_url']; ?>" target="_blank">
                    <?php echo __("Change Domain Name", "getbowtied"); ?>
                </a>

                <form class="delete_key" method="POST" action="">
                    <input type="hidden" name="action" value="delkey">
                    <button class="button" alt="Remove this key" type="submit" value="submit">
                        <?php echo __("Remove Key", "getbowtied"); ?>
                    </button>
                </form>

                <div class="clear"></div>
            </div>

        </div>

    <?php elseif ($activated != '' && $expired == 1): ?>

        <div class="getbowtied-registration-form expired">

            <div class="inner steps has-error">

            <?php 
                if ( ! empty( $rsp['text'] ))
                {
                    echo '<div class="key_error">'.$rsp['text'].'</div><br/><br/>';
                }
            ?>

                <div class="step step-1 step-error">
                <?php echo __("Your <b>Product Key</b>  is no longer active on this domain. 
                Your site will no longer receive automatic theme updates.<br/><br/>
                That's fine if you moved your site and activated it for a new domain.", "getbowtied"); ?>
                <br/><br/><br/><br/><br/><br/><br/><br/>
                <a class="button generate" href="<?php echo $getbowtied_settings['getbowtied_url']; ?>" target="_blank"><?php echo __('Generate a New Product Key', 'getbowtied'); ?></a>
                    
                </div>

                <div class="step step-2">
                    <?php echo __('Step 2', 'getbowtied'); ?><br/>
                    <img src="<?php echo GETBOWTIED_TOOLS_URL . 'img/step2.png'; ?>" />
                    <form id="getbowtied_product_registration" action="" method="POST">
                        <input type="hidden" name="register" value="true" />
                        <input type="text" name="getbowtied_key" id="getbowtied_key" placeholder="Paste your Product Key Here" value="<?php echo get_option("getbowtied_".THEME_SLUG."_license");?>" />
                        <br /><br />
                        <button class="button button-primary getbowtied-register" type="submit"><?php echo __( "Activate Product Key", "getbowtied" ); ?></button>
                    </form>
                </div>

                <div class="clear"></div>
            </div>

        </div>

    <?php endif; ?>

    <div class="getbowtied_footer">
    <a href="<?php echo $getbowtied_settings['product_key_1']; ?>" target="_blank"><span class="dashicons dashicons-info"></span> <?php echo __("Product Key — Common Issues & FAQs", "getbowtied"); ?></a>
    <a href="<?php echo $getbowtied_settings['product_key_2']; ?>" target="_blank"><span class="dashicons dashicons-info"></span> <?php echo __("Can I change the domain name later?", "getbowtied"); ?></a>
    <a href="<?php echo $getbowtied_settings['product_key_3']; ?>" target="_blank"><span class="dashicons dashicons-info"></span> <?php echo __("Can I activate a local / development site?", "getbowtied"); ?></a>
    </div>

    <!-- <i> TESTING BUTTON </i>

    <form method="POST" action="">
    <input type="hidden" name="action" value="delkey">
    <button type="submit" value="submit">DELETE KEY</button>
    </form> -->

</div>