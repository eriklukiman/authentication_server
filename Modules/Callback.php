<?php

namespace Lukiman\AuthServer\Modules;

use Lukiman\Cores\Controller;

class Callback extends Controller
{
    public function do_Index()
    {
        // This is a simple callback handler that displays the authorization code
        // In a real application, this would be your client application that receives the code

        $code = $_GET['code'] ?? null;
        $state = $_GET['state'] ?? null;
        $error = $_GET['error'] ?? null;

        if ($error) {
            $errorDescription = $_GET['error_description'] ?? 'Unknown error';
            return "<h1>Authorization Error</h1><p><strong>Error:</strong> $error</p><p><strong>Description:</strong> $errorDescription</p>";
        }

        if (!$code) {
            return "<h1>Callback Error</h1><p>No authorization code received.</p>";
        }

        $hint = "curl -X POST http://localhost:8080/access_token -d grant_type=authorization_code -d client_id={{client}}";
        $hint.= " -d client_secret={{secret}}";
        $hint.= " -d redirect_uri=http://localhost:8080/callback";
        $hint.= " -d code=CODE_RECEIVED_FROM_CALLBACK";
        header('Content-type: application/json');
        return json_encode([
            'message' => 'Authorization code received successfully.',
            'code' => $code,
            'state' => $state,
            'next_step' => 'Use the authorization code to request an access token.',
            'curl_example' => $hint
        ], JSON_PRETTY_PRINT);
    }
}
