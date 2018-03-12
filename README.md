# phpAuthRequest

![] (https://i.imgur.com/pbHwVp7.jpg)

This is useful when you have one or more webapps that either lack authentication or whatever authentication they do have built-in is undesirable, for whatever reason.

`NGINX` has the ability to authenticate each request with an external web site. Check out https://nginx.org/en/docs/http/ngx_http_auth_request_module.html for details on how that works.

I run this in it's own `NGINX` docker container, proxy location `/auth` to it, and below is my `NGINX` setup for my main site.

```
  resolver 127.0.0.11;
  set $auth_upstream phpauthrequest;

  location / {
    auth_request /auth_request;
    }
  }

  location  ^~ /auth/ {
    auth_request off;
    rewrite /auth(.*) /$1 break;
    proxy_pass http://phpauthrequest;
  }

  location = /auth_request {
    internal;
    proxy_pass http://$auth_upstream/index.php?status;
    proxy_pass_request_body off;
    proxy_set_header Content-Length "";
    proxy_set_header X-Original-URI $request_uri;
  }

  # If not authenticated, redirect to login page.
  error_page 401 = @error401;
  location @error401 {
    return 302 /auth/?req=$request_uri;
  }
```
