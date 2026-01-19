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

        return "
            <h1>Authorization Successful!</h1>
            <p>Authorization code received. Use this code to exchange for an access token.</p>
            <p><strong>Code:</strong> <code>$code</code></p>
            <p><strong>State:</strong> $state</p>
            <hr>
            <h3>Next Step: Exchange Code for Token</h3>
            <p>Run this command to exchange the code for an access token:</p>
            <pre style='background: #f4f4f4; padding: 10px; border-radius: 5px;'>
curl -X POST http://localhost:8080/access_token \\
  -d \"grant_type=authorization_code\" \\
  -d \"client_id=testclient\" \\
  -d \"client_secret=testsecret\" \\
  -d \"redirect_uri=http://localhost:8080/callback\" \\
  -d \"code=$code\"
            </pre>
        ";
    }
}
