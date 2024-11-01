<?php
/**
 * WP SugarCRM plugin file.
 *
 * Copyright (C) 2010-2020, Smackcoders Inc - info@smackcoders.com
 */

if ( ! defined( 'ABSPATH' ) )
	exit; // Exit if accessed directly

class SugarFreeSmLBAdmin {

	public function __construct() {

	}

	public function setEventObj()
	{
		$obj = new mainCrmHelper();
		return $obj;
	}

	public function user_module_mapping_view() {
		include ('views/form-usermodulemapping.php');
	}

	public function mail_sourcing_view() {
		include('views/form-campaign.php');
	}

	public function new_lead_view() {
		global $lb_crm;
		include ('views/form-managefields.php');
	}

	public function new_contact_view() {
		global $lb_crm;
		$module = "Contacts";
		$lb_crm->setModule($module);
		include ('views/form-managefields.php');
	}


	public function show_form_crm_forms() {
		include ('views/form-crmforms.php');
	}

	public function show_form_settings() {
		include ('views/form-settings.php');
	}

	public function show_usersync() {
		include ('views/form-usersync.php');
	}

	public function show_ecom_integ() {
		include ('views/form-ecom-integration.php');
	}

	public function show_vtiger_crm_config() {
		include ('views/form-vtigercrmconfig.php');
	}

	public function show_sugar_crm_config() {
		include ('views/form-sugarcrmconfig.php');
	}

	public function show_suite_crm_config() {
		include ('views/form-sugarcrmconfig.php');
	}

	public function show_zoho_crm_config() {
		include ('views/form-zohocrmconfig.php');
	}

	public function show_zohoplus_crm_config() {
		include ('views/form-zohocrmconfig.php');
	}

	public function show_freshsales_crm_config() {
		include ('views/form-freshsalescrmconfig.php');
	}

	public function show_salesforce_crm_config() {
		include('views/form-salesforcecrmconfig.php');
	}

	public function sugarproSettings( $sugarSettArray )
	{
		$sugar_config_array = $sugarSettArray['REQUEST'];
		$fieldNames = array(
			'url' => __('Sugar Host Address', SM_LB_URL ),
			'username' => __('Sugar Username' , SM_LB_URL ),
			'password' => __('Sugar Password' , SM_LB_URL ),
			'smack_email' => __('Smack Email'),
			'email' => __('Email id'),
			'emailcondition' => __('Emailcondition'),
			'debugmode' => __('Debug Mode'),
		);

		foreach ($fieldNames as $field=>$value){
			if(isset($sugar_config_array[$field]))
			{
				$config[$field] = $sugar_config_array[$field];
			}
		}
		require_once(SM_LB_SUGAR_DIR . "includes/wpsugarproFunctions.php");
		$FunctionsObj = new mainCrmHelper( );
		$testlogin_result = $FunctionsObj->testlogin( $config['url'] , $config['username'] , $config['password'] );
		if(isset($testlogin_result['login']) && ($testlogin_result['login']['id'] != -1) && is_array($testlogin_result['login']))
		{
			$successresult = "Settings Saved";
			$result['error'] = 0;
			$result['success'] = $successresult;
			$WPCapture_includes_helper_Obj = new WPCapture_includes_helper_PRO();
			$activateplugin = $WPCapture_includes_helper_Obj->ActivatedPlugin;
			update_option("wp_{$activateplugin}_settings", $config);
		}
		else
		{
			$sugarcrmerror = "Please Verify Your Sugar CRM credentials";
			$result['error'] = 1;
			$result['errormsg'] = $sugarcrmerror ;
			$result['success'] = 0;
		}
		return $result;
		$WPCapture_includes_helper_Obj = new WPCapture_includes_helper_PRO();
		$activateplugin = $WPCapture_includes_helper_Obj->ActivatedPlugin;
		update_option("wp_{$activateplugin}_settings", $config);
	}
}

global $lb_crm;
$lb_crm = new SugarFreeSmLBAdmin();
