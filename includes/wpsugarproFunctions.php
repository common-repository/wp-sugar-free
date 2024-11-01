<?php
/**
 * WP SugarCRM plugin file.
 *
 * Copyright (C) 2010-2020, Smackcoders Inc - info@smackcoders.com
 */

if ( ! defined( 'ABSPATH' ) )
	exit; // Exit if accessed directly

if(!defined('sugarEntry') || !sugarEntry)
{
	define('sugarEntry', TRUE);
	include_once(SM_LB_SUGAR_DIR.'lib/nusoap/nusoap.php');
}
class PROFunctions{
	public $username;
	public $accesskey;
	public $url;
	public $result_emails;
	public $result_ids;
	public function __construct()
	{
		$WPCapture_includes_helper_Obj = new WPCapture_includes_helper_PRO();
		$activateplugin = $WPCapture_includes_helper_Obj->ActivatedPlugin;
		if(isset($_REQUEST['crmtype']))
		{
			$crmtype = sanitize_text_field( $_REQUEST['crmtype'] );
			$SettingsConfig = get_option("wp_{$crmtype}_settings");
		}
		else
		{
			$SettingsConfig = get_option("wp_{$activateplugin}_settings");
		}
		$this->username = $SettingsConfig['username'];
		$this->accesskey = $SettingsConfig['password'];
		$this->url = $SettingsConfig['url'];
	}

	public function login($url,$username,$password)
	{
		$parse_url = parse_url($url, PHP_URL_HOST);
		$exp_url = explode(".", $parse_url);
		$domain = end($exp_url); 
		
		$client = new nusoapclient($url.'/soap.php?wsdl',true);

		if($domain == 'eu'){
			$user_auth = array(
				'user_auth' => array(
					'user_name' => $username,
					'password' => $password,
					'version' => '0.1'
				),
				'application_name' => 'wp-sugar-pro'
			);
		}
		else{
			$user_auth = array(
				'user_auth' => array(
					'user_name' => $username,
					'password' => md5($password),
					'version' => '0.1'
				),
				'application_name' => 'wp-sugar-pro'
			);
		}

		$login = $client->call('login',$user_auth);
		$session_id = $login['id'];
		$client_array = array( 'login' => $login , 'session_id' => $session_id , "clientObj" => $client );
		return $client_array;
	}

	public function testlogin( $url , $username , $password )
	{		
		$this->url = $url;
		$this->username = $username;
		$this->accesskey = $password;
		$login = $this->login($url,$username,$password);
		return $login;
	}

	public function getCrmFields( $module )
	{
		$WPCapture_includes_helper_Obj = new WPCapture_includes_helper_PRO();
		$activateplugin = $WPCapture_includes_helper_Obj->ActivatedPlugin;
		if(isset($_REQUEST['crmtype']))
		{
			$crmtype = sanitize_text_field( $_REQUEST['crmtype'] );
			$SettingsConfig = get_option("wp_{$crmtype}_settings");
		}
		else
		{
			$SettingsConfig = get_option("wp_{$activateplugin}_settings");
		}
		$username = $SettingsConfig['username'];
		$password = $SettingsConfig['password'];
		$url = $SettingsConfig['url'];

		$client_array = $this->login($url,$username,$password);
		$client = $client_array['clientObj'];
		$recordInfo = $client->call('get_module_fields', array('session' => $client_array['session_id'], 'module_name' => $module));
		$config_fields = array();
		if(isset($recordInfo))
		{
			$j=0;
			$module = $recordInfo['module_name'];
			$AcceptedFields = Array( 'text' => 'text' , 'bool' => 'boolean', 'enum' => 'picklist' , 'varchar' => 'string' , 'url' => 'url' , 'phone' => 'phone' , 'multienum' => 'multipicklist' , 'radioenum' => 'radioenum', 'currency' => 'currency' ,'date' => 'date' , 'datetime' => 'date' , 'int' => 'text' , 'decimal' => 'text' , 'currency_id' => 'text' );
			for($i=0;$i<count($recordInfo['module_fields']);$i++)
			{
				if(array_key_exists($recordInfo['module_fields'][$i]['type'], $AcceptedFields)){
					if(($recordInfo['module_fields'][$i]['type'] == 'enum') || ($recordInfo['module_fields'][$i]['type'] == 'multienum') || ($recordInfo['module_fields'][$i]['type'] == 'radioenum')){
						$optionindex = 0;
						$picklistValues = array();
						foreach($recordInfo['module_fields'][$i]['options'] as $option)
						{
							$picklistValues[$optionindex]['label'] = $option['name'] ;
							$picklistValues[$optionindex]['value'] = $option['value'];
							$optionindex++;
						}
						$recordInfo['module_fields'][$i]['type'] = Array ( 'name' => $AcceptedFields[$recordInfo['module_fields'][$i]['type']] , 'picklistValues' => $picklistValues );
					}
					else
					{
						$recordInfo['module_fields'][$i]['type'] = Array( 'name' => $AcceptedFields[$recordInfo['module_fields'][$i]['type']]);
					}
					$config_fields['fields'][$j] = $recordInfo['module_fields'][$i];
					$config_fields['fields'][$j]['order'] = $j;
					$config_fields['fields'][$j]['publish'] = 1;
					$config_fields['fields'][$j]['display_label'] = trim($recordInfo['module_fields'][$i]['label'], ':');
					if($recordInfo['module_fields'][$i]['required'] == 1)
					{

						$config_fields['fields'][$j]['wp_mandatory'] = 1;
						$config_fields['fields'][$j]['mandatory'] = 2;
					}
					else
					{
						$config_fields['fields'][$j]['wp_mandatory'] = 0;
					}
					$j++;
				}
			}
			$config_fields['check_duplicate'] = 0;
			$config_fields['isWidget'] = 0;
			$users_list = $this->getUsersList();
			if(isset($users_list['id'][0])) {
				$config_fields['assignedto'] = $users_list['id'][0];
			} else {
				$config_fields['assignedto'] = '';	
			}			
			$config_fields['module'] = $module;
		}
		return $config_fields;
	}

	public function getUsersList()
	{
		$user_details = array();
		$WPCapture_includes_helper_Obj = new WPCapture_includes_helper_PRO();
		$activateplugin = $WPCapture_includes_helper_Obj->ActivatedPlugin;
		if(isset($_REQUEST['crmtype']))
		{
			$crmtype = sanitize_text_field( $_REQUEST['crmtype'] );
			$SettingsConfig = get_option("wp_{$crmtype}_settings");
		}
		else
		{
			$SettingsConfig = get_option("wp_{$activateplugin}_settings");
		}
		$username = $SettingsConfig['username'];
		$password = $SettingsConfig['password'];
		$url = $SettingsConfig['url'];
		$client_array = $this->login($url,$username,$password);
		$client = $client_array['clientObj'];

		//$domain = end(explode(".", parse_url($url, PHP_URL_HOST))); 
		$parse_url = parse_url($url, PHP_URL_HOST);
		$exp_url = explode(".", $parse_url);
		$domain = end($exp_url); 
		if($domain == 'eu'){
			$recordInfo = $client->call('user_list', array('user_name' => $this->username, 'password' => $this->accesskey));
		}
		else{
			$recordInfo = $client->call('user_list', array('user_name' => $this->username, 'password' => md5($this->accesskey)));
		}
		
		$userindex = 0;
		if(is_array($recordInfo))
			foreach($recordInfo as $record)
			{
				$user_details['user_name'][$userindex] = $record['user_name'];
				$user_details['id'][$userindex] = $record['id'];
				$user_details['first_name'][$userindex] = $record['first_name'];
				$user_details['last_name'][$userindex] = $record['last_name'];
				$userindex++;
			}
		return $user_details;
	}

	public function getUsersListHtml( $shortcode = "" )
	{
		$HelperObj = new WPCapture_includes_helper_PRO();
		$module = $HelperObj->Module;
		$moduleslug = $HelperObj->ModuleSlug;
		$activatedplugin = $HelperObj->ActivatedPlugin;
		$activatedpluginlabel = $HelperObj->ActivatedPluginLabel;
		$formObj = new CaptureData();
		if(isset($shortcode) && ( $shortcode != "" ))
		{
			$config_fields = $formObj->getFormSettings( $shortcode );  // Get form settings 
		}
		$users_list = get_option('crm_users');
		$users_list = $users_list[$activatedplugin];
		$html = "";
		$html = '<select class="selectpicker form-control" name="assignedto" id="assignedto">';
		$content_option = "";
		if(isset($users_list['user_name']))
			for($i = 0; $i < count($users_list['user_name']) ; $i++)
			{
				$content_option.="<option id='{$users_list['id'][$i]}' value='{$users_list['id'][$i]}'";

				if($users_list['id'][$i] == $config_fields->assigned_to)
				{
					$content_option.=" selected";

				}
				$content_option.=">{$users_list['first_name'][$i]} {$users_list['last_name'][$i]}</option>";
			}
		$content_option .= "<option id='owner_rr' value='Round Robin'";
		if( $config_fields->assigned_to == 'Round Robin' )
		{
			$content_option .= "selected";
		}
		$content_option .= "> Round Robin </option>";

		$html .= $content_option;
		$html .= "</select> <span style='padding-left:15px; color:red;' id='assignedto_status'></span>";
		return $html;
	}

	public function getAssignedToList()
	{
		$users_list = $this->getUsersList();

		for($i = 0; $i < count($users_list['user_name']) ; $i++)
		{
			$user_list_array[$users_list['id'][$i]] = $users_list['first_name'][$i] ." ". $users_list['last_name'][$i];
		}

		return $user_list_array;
	}

	public function mapUserCaptureFields( $user_firstname , $user_lastname , $user_email )
	{
		$post = array();
		$post['first_name'] = $user_firstname;
		$post['last_name'] = $user_lastname;
		$post[$this->duplicateCheckEmailField()] = $user_email;
		return $post;
	}

	public function assignedToFieldId()
	{
		return "assigned_user_id";
	}

	public function createRecordOnUserCapture( $module , $module_fields )
	{
		return $this->createRecord( $module , $module_fields );

	}

	public function createRecord( $module , $module_fields )
	{
		$WPCapture_includes_helper_Obj = new WPCapture_includes_helper_PRO();
		$activateplugin = $WPCapture_includes_helper_Obj->ActivatedPlugin;
		if(isset($_REQUEST['crmtype']))
		{
			$crmtype = sanitize_text_field( $_REQUEST['crmtype'] );
			$SettingsConfig = get_option("wp_{$crmtype}_settings");
		}
		else
		{
			$SettingsConfig = get_option("wp_{$activateplugin}_settings");
		}
		$username = $SettingsConfig['username'];
		$password = $SettingsConfig['password'];
		$url = $SettingsConfig['url'];
		$client_array = $this->login($url,$username,$password);
		$client = $client_array['clientObj'];
		$fieldvalues = array();
		foreach($module_fields as $key => $value)
		{
			$fieldvalues[] = array('name' => $key, 'value' => $value);
		}
		$set_entry_parameters = array(
			//session id
			"session" => $client_array['session_id'],
			//The name of the module from which to retrieve records.
			"module_name" =>  $module,
			//Record attributes
			"name_value_list" => $fieldvalues,
		);
		$response = $client->call('set_entry',  $set_entry_parameters , $this->url );
		if(isset($response['id']))
		{
			$data['result'] = "success";
			$data['failure'] = 0;
		}
		else
		{
			$data['result'] = "failure";
			$data['failure'] = 1;
			$data['reason'] = "failed adding entry";
		}
		return $data;
	}

	function duplicateCheckEmailField()
	{
		return "email1";
	}

}
