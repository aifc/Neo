<?php
defined("ABSPATH") or die("");

if (DUP_PRO_U::PHP56()) {

    require_once(DUPLICATOR_PRO_PLUGIN_PATH . 'lib/onedrive/autoload.php');

    abstract class DUP_PRO_OneDrive_Config
    {
        const ONEDRIVE_CLIENT_ID                = '15fa3a0d-b7ee-447c-8093-7bfcf30b0797';
        const ONEDRIVE_CLIENT_SECRET            = 'ahYN901]gvemuEUKKB45}|_';
        const ONEDRIVE_REDIRECT_URI             = 'https://snapcreek.com/misc/onedrive/redir2.php';
        const ONEDRIVE_ACCESS_SCOPE   = "onedrive.appfolder offline_access";
    }

    class DUP_PRO_Onedrive_U
    {
        public static function get_raw_onedrive_client()
        {
            $onedrive = new Krizalys\Onedrive\Client([
                'client_id' => DUP_PRO_OneDrive_Config::ONEDRIVE_CLIENT_ID,
            ]);

            return $onedrive;
        }

        public static function get_onedrive_client_from_state($state)
        {
            $onedrive = new Krizalys\Onedrive\Client([
                'client_id' => DUP_PRO_OneDrive_Config::ONEDRIVE_CLIENT_ID,
                'state' => $state,
            ]);

            return $onedrive;
        }

        public static function get_onedrive_auth_url_and_client($callback_uri)
        {
            $onedrive = self::get_raw_onedrive_client();

            $redirect_uri = DUP_PRO_OneDrive_Config::ONEDRIVE_REDIRECT_URI . "?callback_uri=$callback_uri&action_type=get_token";

            // Gets a log in URL with sufficient privileges from the OneDrive API.
            $url = $onedrive->getLogInUrl(self::get_scope_array(), $redirect_uri);

            return ['url' => $url,'client' => $onedrive];
        }

        public static function get_onedrive_logout_url($callback_uri){
            //https://login.live.com/oauth20_logout.srf?client_id={client_id}&redirect_uri={redirect_uri}
            $base_url = "https://login.live.com/oauth20_logout.srf";
            $redirect_uri = DUP_PRO_OneDrive_Config::ONEDRIVE_REDIRECT_URI."?callback_uri=$callback_uri&action_type=logout_user";
            $fields_arr =[
                "client_id" => DUP_PRO_OneDrive_Config::ONEDRIVE_CLIENT_ID,
                "redirect_uri" => $redirect_uri
            ];
            $fields = http_build_query($fields_arr);
            $logout_url = $base_url."?$fields";

            return $logout_url;
        }

        public static function get_scope_array(){
            return explode(' ',DUP_PRO_OneDrive_Config::ONEDRIVE_ACCESS_SCOPE);
        }
    }
}