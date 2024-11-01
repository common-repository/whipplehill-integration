<?php
class WH_Integration_Helper
{
	var $config;
	var $options_obj;
	
	

	public function __construct()
	{
		global $wh_integration_config;
		$this->config = $wh_integration_config;
		$this->options_obj = WH_Integration::get_instance('WH_Integration_Options');
	}
    
    function str_replace_once($needle, $replace, $haystack) 
    {
        $pos = strpos($haystack, $needle);
        if ($pos === false) 
        {
            return $haystack;
        }
        return substr_replace($haystack, $replace, $pos, strlen($needle));
    } 
    
    function generate_random_string($length = 32, $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890') 
    {
	    $chars_length = (strlen($chars) - 1);
	    $string = $chars{rand(0, $chars_length)};
	    
	    for ($i = 1; $i < $length; $i = strlen($string))
	    {
	        $r = $chars{rand(0, $chars_length)};   
	       
	        if ($r != $string{$i - 1}) $string .=  $r;
	    }
	   
	    return $string;
	}
	
	function bool_val($in, $strict=false) 
	{
	    $out = null;
	    $in = (is_string($in)?strtolower($in):$in);
	    // if not strict, we only have to check if something is false
	    if (in_array($in,array('false','no', 'n','0','off',false,0), true) || !$in) {
	        $out = false;
	    } else if ($strict) {
	        // if strict, check the equivalent true values
	        if (in_array($in,array('true','yes','y','1','on',true,1), true)) {
	            $out = true;
	        }
	    } else {
	        // not strict? let the regular php bool check figure it out (will
	        //     largely default to true)
	        $out = ($in?true:false);
	    }
	    return $out;
	}

	function is_suadmin(){
		if( is_super_admin() ){
			$current_user = wp_get_current_user();
			if($current_user->user_nicename === 'suadmin'){
				return true;
			}
		} 
		return false;
	}
		
	function log($str){
		$options_class = WH_Integration::get_instance('WH_Integration_Options');
		$options = $options_class->get();

		//if($options['enable_logging']  && defined('WH_INTEGRATION_LOG') ){
			
	        if (is_array($str) || is_object($str)){
	            $str = print_r($str, true);
	        }

	        $str = '( '.date("l, F j, Y - H:i:s").' ) '. $str;
	        
	        error_log( $str );

			//$handle = fopen( . '/log.txt', 'a+');
			//fwrite($handle, "$str\n");
			//fclose($handle);
		//}
	}
	
	function encrypt($input_string, $key){
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$h_key = hash('sha256', $key, TRUE);
		return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $h_key, $input_string, MCRYPT_MODE_ECB, $iv));
	}
	
	function decrypt($encrypted_input_string, $key){
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$h_key = hash('sha256', $key, TRUE);
		return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $h_key, base64_decode($encrypted_input_string), MCRYPT_MODE_ECB, $iv));
	}
	
}