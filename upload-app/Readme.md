## Overview

This directory contains an example of a client application that consumes a token to authorize access to resources. In other words, it can be referred to as a Resource Server. This source code illustrates a use case where the application is implemented using another framework and deployed as a standalone service.

This example resource server only have two endpoints. Please refer to `public/index.php`. You will found `/health` and `/upload`. We can pass Bearer token during upload into header `Authorization`.

Run this command with replace token and file that you want to upload.

```
curl --request POST \
  --url http://localhost:8888/upload \
  --header 'Authorization: Bearer {{ token }}' \
  --header 'content-type: multipart/form-data' \
  -F "file=@/path/to/file.jpg"
  ```