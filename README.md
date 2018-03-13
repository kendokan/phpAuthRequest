# phpAuthRequest

### Description
![](https://i.imgur.com/pbHwVp7.jpg)

This is useful when you have one or more webapps that either lack authentication, or whatever authentication they do have built-in is undesirable. You could use `.htaccess` files, or you could use something like this.

`NGINX` has the ability to authenticate each request with another page. For example, if I try to browse to `https://site.local/`, `NGINX` can call another URL to see if it should allow access or not. If that other URL returns a 200, access is permitted. Anything else, and access is denied. This script provides that function, along with session login/logout utilities. Check out https://nginx.org/en/docs/http/ngx_http_auth_request_module.html for more details on how that works.

### Installation
Create a database using the schema in `phpAuthRequest.sql`. I use `SQLite`, but any database that works with `PDO` should be fine (`MySQL`, `PostgreSQL`, etc.). A full list can be found at https://secure.php.net/manual/en/pdo.drivers.php.

I run this in its own Docker container called `phpauthrequest` in the example below. Proxy location `/login/` on your public web server to the container/server running `phpAuthRequest`. Below is a `NGINX` setup snippet for the public web server.

```
location / {
  # check with auth_request before serving up anything from this location
  auth_request /auth_request;
}

location  ^~ /login/ {
  # auth_request is off here, we need unauthenticated users to be able to login
  auth_request off;
  rewrite /login(.*) /$1 break;
  proxy_pass http://phpauthrequest;
}

location = /auth_request {
  internal;
  proxy_pass http://phpauthrequest/index.php?action=status;
  proxy_pass_request_body off;
  proxy_set_header Content-Length "";
  proxy_set_header X-Original-URI $request_uri;
}

# if not authenticated, redirect to login page
error_page 401 = @error401;
location @error401 {
  return 302 /login/?redirect=$request_uri;
}
```

It also understands basic access levels, so you could do something like:

```
location / {
  # check with auth_request_user before serving up anything from this location
  auth_request /auth_request_user;
}

location ^~ /supersecret/ {
  # check with auth_request_admin before serving up anything from this location
  auth_request /auth_request_admin;
}

location  ^~ /login/ {
  # auth_request is off here, we need unauthenticated users to be able to login
  auth_request off;
  rewrite /login(.*) /$1 break;
  proxy_pass http://phpauthrequest;
}

location = /auth_request_user {
  internal;
  proxy_pass http://phpauthrequest/index.php?action=status&access-level=1;
  proxy_pass_request_body off;
  proxy_set_header Content-Length "";
  proxy_set_header X-Original-URI $request_uri;
}

location = /auth_request_admin {
  internal;
  proxy_pass http://phpauthrequest/index.php?action=status&access-level=100;
  proxy_pass_request_body off;
  proxy_set_header Content-Length "";
  proxy_set_header X-Original-URI $request_uri;
}
```
