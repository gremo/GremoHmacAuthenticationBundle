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
        pattern: ^/api
        stateless: true  # HMAC is stateless!
        hmac:
            auth_header: Authorization # Name of the header to inspect
            service_label: HMAC        # Service name/id
            algorithm: sha256          # Hashing algoritm, see hash_algos()
            verify_headers: []         # Array or comma-separated list of headers
```

## How it works
The authentication manager will inspect the `auth_header` header with the following pattern:

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

Note that both **query params and headers are sorted** before calculating the signature.

Consider the following **configuration**:

```yml
security:
	# ...
    providers:
        in_memory:
            memory:
                users:
                    foo: { password: bar }

	firewalls:
		hmac_secured:
		    pattern: ^/
		    stateless: true
			provider: in_memory
		    hmac:
		        auth_header: Authorization
		        service_label: HMAC
		        algorithm: sha256
		        verify_headers: [Date, Accept, Content-MD5]

		# ...
```

And the **raw HTTP request**:

```
GET /?b=c&a= HTTP/1.1
Accept: application/json
Host: localhost:8080
Authorization: HMAC foo:ZWQyNmYwZWM1MmZkYmIyNTgzYjJiYWQ2Zjg3OGJkYjIzNzU2YTBlYjQ3NGY5ZDg1YWE5ZjYwN2Q1ODg1NWI1MQ==
Date: Mon, 26 Mar 2007 19:37:58 +0000
```

The **canonical string** would be (note the LF where `Content-MD5` should appear):

```
GET
/?a=&b=c
application/json

Mon, 26 Mar 2007 19:37:58 +0000
```

The **hashed value** is (plain password is `bar`):

```
ed26f0ec52fdbb2583b2bad6f878bdb23756a0eb474f9d85aa9f607d58855b51
```

And finally the **base64 encoded value** (that is the signature of `Authorization` header):

```
ZWQyNmYwZWM1MmZkYmIyNTgzYjJiYWQ2Zjg3OGJkYjIzNzU2YTBlYjQ3NGY5ZDg1YWE5ZjYwN2Q1ODg1NWI1MQ==
```
