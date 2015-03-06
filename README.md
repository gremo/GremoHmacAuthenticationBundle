# GremoHmacAuthenticationBundle
[![GitHub issues](https://img.shields.io/github/issues/gremo/GremoHmacAuthenticationBundle.svg?style=flat-square)](https://github.com/gremo/GremoHmacAuthenticationBundle/issues) [![Downloads total](https://img.shields.io/packagist/dt/gremo/hmac-authentication-bundle.svg?style=flat-square)](https://packagist.org/packages/gremo/hmac-authentication-bundle)

Symfony 2 bundle adding REST HMAC HTTP authentication.

## Installation

```json
{
    "require": {
        "gremo/hmac-authentication-bundle": "dev-master"
    },
}
```

Register the bundle in your `app/AppKernel.php`:

```php
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new Gremo\HmacAuthenticationBundle\GremoHmacAuthenticationBundle(),
        );
        
        // ...
    }
```

## Configuration
Not needed.

## Usage
Protect part of your application in `security.yml` using the `hmac` key:

```yml
# ...
firewalls:
    # ...
    hmac_secured:
        pattern: /api/.*
        stateless: true  # HMAC is stateless!
        http:
            auth_header: Authorization # Name of the header to inspect
            service_label: HMAC        # Service name/id
            algorithm: sha256          # Hashing algoritm, see hash_algos()
            verify_headers: []         # Array or comma-separated list of headers
```

## How it works
The authentication manager will inspect the `auth_header` looking for the following pattern:

```
<auth_header>: <service_label> <client_id>:<signature>
```

If the service label matches, the manager loads the user with `<client_id>` username. The password is used to re-compute the signature, base64-enconding the hashed canonical string: 

```
<canonical_string> = <http_method> + "\n" +
                     <path_with_sorted_query_string> + "\n" +
                     <verify_header1> + "\n" +
                     <verify_header2> + "\n" +
                     ...
                     <verify_headerN>;
```

Note that both **query params and verify headers are sorted** before calculating the signature.

An example canonical string with `verify_headers: Content-Type, Content-MD5, Date` (note the LF where `Content-MD5` should appear):

```
GET
/foo/bar?a&b=c
application/json

Date: Mon, 26 Mar 2007 19:37:58 +0000
```
