### How to run

Client information

Client ID: `pyclient`

Client secret: `pysecret`

Client name: Test Python

Redirect URL: http://localhost:8888/callback

### Run server

`pip install -r requirements.txt`

`sh run.sh`

### After script run

1. Goto URL auth server: http://localhost:8080/authorize?response_type=code&client_id=pyclient&redirect_uri=http://localhost:8888/callback&scope=basic&state=xyz

2. Click Approve, you will be redirected to the callback response and got the token
