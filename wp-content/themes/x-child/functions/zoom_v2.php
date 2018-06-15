<?php

class ZoomAPI_v2 {
    /*The API Key, Secret, & URL will be used in every function.*/
    private $api_key = '-ClCjbdwQAe5UCDnl160Lw'; //Details from admin@sihq.com.au // Password is Secretoh.123 //P.s not really the pass
    private $api_secret = 'nBOqpS4WJVwdph35YcJxmoXZ4jImhncoPSbL';
    private $api_url = 'https://api.zoom.us/v2/';
    //private $userID = 'dah_HLrgQIGZSRWMY3iheg'; // This is mikko1243supplier@gmail.com account `id`, found through listUsers() below
    
    function get_token()
    {
        $request = new stdClass();
        $request->secret_key = $this->api_secret;
        $request->api_key = $this->api_key;
        $request->exp = time() + 1800;

        $Jwt_Auth_Public = new Jwt_Auth_Public('jwt-auth','1.1.0');
        return $Jwt_Auth_Public->generate_token($request);
    }

    /* Function to send HTTP POST Requests */
    /* Used by every function below to make HTTP POST call */
    function send_request_v2($called_function, $data) {
        /*Creates the endpoint URL*/

        $request_url = $this->api_url.$called_function;

        if(!empty($data))
        {
            $data = json_encode($data);
            $post_fields = http_build_query($data); 
        }  

        /*Preparing Query...*/
        $ch = curl_init($request_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $this->get_token(),
            'Content-Type: application/json'
        ));
        
        if(!empty($data))
        {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data); 
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        $response = curl_exec($ch);
        /*Check for any errors*/
        $error_message = curl_exec($ch);

        curl_close($ch);

        if(!$response){
            return false;
        }
        /*Return the data in JSON format*/
        return $response;
    }


    function get_zoom_user_id($wordpressUserID){
        $user_email = get_userdata($wordpressUserID)->user_email;
        $request_url = $this->api_url.'users/'.$user_email;

        $ch = curl_init($request_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $this->get_token(),
            'Content-Type: application/json'
        ));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        $response = curl_exec($ch);

        $response = json_decode($response, true);
        return $response['id'];
        
    }
    function create_user_v2($wordpressUserID,$data){

        $host_id = get_user_meta($wordpressUserID, 'zoom_host_id', true); //needs a better way to check if zoom user is created in zoom database not wordpress

        // $host_id = get_zoom_user_id($wordpressUserID);
        if(!empty($host_id))
        {
            return $host_id;
        }
        $request_url = $this->api_url.'users';

        $ch = curl_init($request_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $this->get_token(),
            'Content-Type: application/json'
        ));
        if(!empty($data))
        {
            $data = json_encode($data);
        }  
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data); 

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        $response = curl_exec($ch);
        /*Check for any errors*/
        $error_message = curl_exec($ch);

        curl_close($ch);
        if(!$response){
            return false;
        }
        /*Return the data in JSON format*/
        $response = json_decode($response, true);
        update_user_meta($wordpressUserID,'zoom_host_id',$response['id']);
        return $response['id'];

    }
    function listUsers_v2() {
        return $this->send_request_v2('users');
    }

    // This function isn't used, create_scheduled_meeting is
    function createInstantMeeting_v2($wordpressUserID) {
        $parameters = array();
        $parameters['topic'] = 'testmeetingcreation';
        $parameters['type'] = '1';

        $user_param = array();
        $user_param['action'] ='custCreate';
        $user_param['user_info'] = array('email'=>get_userdata($wordpressUserID)->user_email,'type'=>'1');
        $user_ID = $this->create_user_v2($wordpressUserID,$user_param);

        return $this->send_request_v2('users/'.$user_ID.'/meetings', $parameters);
    }

    function create_scheduled_meeting_v2($UTCDate,$wordpressUserID){
        $parameters = array();
        $parameters['topic'] = 'Counselling appointment';
        $parameters['type'] = '2';
        $parameters['start_time'] = $UTCDate;

        $user_param = array();
        $user_param['action'] ='custCreate';
        $user_param['user_info'] = array('email'=>get_userdata($wordpressUserID)->user_email,'type'=>1);
        $user_ID = $this->create_user_v2($wordpressUserID,$user_param);

        return $this->send_request_v2('users/'.$user_ID.'/meetings', $parameters);
        //return $this->send_request_v2('users/'.$this->userID.'/meetings', $parameters);
    } 
}
