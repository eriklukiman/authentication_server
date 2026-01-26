from fastapi import FastAPI, Request
import requests

app = FastAPI()

TOKEN_URL = "http://localhost:8080/access_token"

@app.get("/callback")
async def callback(request: Request):
    code = request.query_params.get("code")
    state = request.query_params.get("state")

    token_response = requests.post(
        TOKEN_URL,
        data={
            "grant_type": "authorization_code",
            "client_id": "pyclient",
            "client_secret": "pysecret",
            "redirect_uri": "http://localhost:8888/callback",
            "code": code
        }
    )

    json_token = token_response.text

    return {
        "authorization_code": code,
        "token_response": json_token,
        "state": state
    }
