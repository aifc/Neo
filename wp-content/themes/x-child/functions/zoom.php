<?php

class ZoomAPI {
    /*The API Key, Secret, & URL will be used in every function.*/
    private $api_key = '-ClCjbdwQAe5UCDnl160Lw';
    private $api_secret = 'nBOqpS4WJVwdph35YcJxmoXZ4jImhncoPSbL';
    private $api_url = 'https://api.zoom.us/v1/';
    
    //functions below has been deprecated since since Nov 1, 2017
    /* Function to send HTTP POST Requests */
    /* Used by every function below to make HTTP POST call */
    function send_request($called_function, $data) {
        /*Creates the endpoint URL*/
        $request_url = $this->api_url.$called_function;

        /*Adds the Key, Secret, & Datatype to the passed array*/
        $data['api_key'] = $this->api_key;
        $data['api_secret'] = $this->api_secret;
        $data['data_type'] = 'JSON';

        $post_fields = http_build_query($data);
        /*Check to see queried fields*/
        /*Used for troubleshooting/debugging*/
        //echo $post_fields;

        /*Preparing Query...*/
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_URL, $request_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        $response = curl_exec($ch);

        /*Check for any errors*/
        $error_message = curl_exec($ch);
        echo $error_message;
        curl_close($ch);

        /*Will print back the response from the call*/
        /*Used for troubleshooting/debugging        */
        echo $request_url;
        var_dump($data);
        var_dump($response);
        if(!$response){
            return false;
        }
        /*Return the data in JSON format*/
        return $response;
    }

    function listUsers() {
        return $this->send_request('user/list', array());
    }

    // This function isn't used, create_scheduled_meeting is
    function createInstantMeeting() {
        $parameters = array();
        // This is mikkooz1243@gmail.com account `id`, found through listUsers() above
        $parameters['host_id'] = 'EE_36CM8RUGT4li4S0ZH1g';
        $parameters['topic'] = 'testmeetingcreation';
        $parameters['type'] = 1;
        return $this->send_request('meeting/create', $parameters);
    }

    function create_scheduled_meeting($UTCDate){
        $parameters = array();
        // This is mikkooz1243@gmail.com account `id`, found through listUsers() above
        $parameters['host_id'] = 'EE_36CM8RUGT4li4S0ZH1g';
        $parameters['topic'] = 'Counselling appointment';
        $parameters['type'] = 2;
        $parameters['time'] = $UTCDate;
        return $this->send_request('meeting/create', $parameters);
    } 
}
