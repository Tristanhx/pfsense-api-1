<?php

// IMPORTS--------------------------------------------------------------------------------------------------------------
require_once("config.inc");
require_once("auth.inc");
require_once("functions.inc");
require_once("filter.inc");
require_once("api.inc");

// HEADERS--------------------------------------------------------------------------------------------------------------
api_runtime_allowed();    // Check that our configuration allows this API call to run first
header('Content-Type: application/json');
header("Referer: no-referrer");
session_start();    // Start our session. This is only used for tracking user name

// VARIABLES------------------------------------------------------------------------------------------------------------
global $g, $config, $tracker, $api_resp, $client_id, $client_params;
$user_created_msg = $_SESSION["Username"]."@".$_SERVER["REMOTE_ADDR"]." (API)";    // Save the username and ip of client
$read_only_action = false;    // Set whether this action requires read only access
$req_privs = array("page-all", "page-firewall-rules-edit");    // Array of privileges allowing this action
$http_method = $_SERVER['REQUEST_METHOD'];    // Save our HTTP method


// RUN TIME-------------------------------------------------------------------------------------------------------------
// Check that client is authenticated and authorized
if (api_authorized($req_privs, $read_only_action)) {
    // Check that our HTTP method is POST (DELETE)
    if ($http_method === 'POST') {
        if (isset($client_params['id'])) {
            $rule_id = $client_params['id'];
        } else {
            $api_resp = array("status" => "bad request", "code" => 400, "return" => 90);
            $api_resp["message"] = "rule id required";
            http_response_code(400);
            echo json_encode($api_resp) . PHP_EOL;
            die();
        }

        // Add debug data if requested
        if (array_key_exists("debug", $client_params)) {
            echo "RULE ID TO DELETE:" . PHP_EOL;
            echo var_dump($rule_id) . PHP_EOL;
        }

        // Check that our rule ID exists
        if (array_key_exists($rule_id, $config["filter"]["rule"])) {
            $_SESSION["Username"] = $client_id;    // Save our CLIENT ID to session data for logging
            $change_note = " Deleted firewall rule via API";    // Add a change note
            $del_rule = $config["filter"]["rule"][$rule_id];    // Save the rule we are deleting
            unset($config["filter"]["rule"][$rule_id]);    // Remove rule from our config
            sort_firewall_rules();    // Sort our firewall rules
            write_config(sprintf(gettext($change_note)));    // Apply our configuration change
            send_event("filter reload");    // Ensure our firewall filter is reloaded
            // Loop through each alias and see if our alias was added successfully
            $api_resp = array("status" => "ok", "code" => 200, "return" => 0);
            $api_resp["message"] = "firewall rule deleted";
            $api_resp["data"] = $del_rule;
            http_response_code(200);
            echo json_encode($api_resp) . PHP_EOL;
            die();
        } else {
            $api_resp = array("status" => "bad request", "code" => 400, "return" => 91);
            $api_resp["message"] = "rule id does not exist";
            http_response_code(400);
            echo json_encode($api_resp) . PHP_EOL;
            die();
        }
    } else {
        $api_resp = array("status" => "bad request", "code" => 400, "return" => 2, "message" => "invalid http method");
        http_response_code(400);
        echo json_encode($api_resp) . PHP_EOL;
        die();
    }
} else {
    http_response_code(401);
    echo json_encode($api_resp) . PHP_EOL;
    die();
}
