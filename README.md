# Fix Password Reset Behind Page Cache

A lightweight WordPress must-use (MU) plugin that helps password reset
links continue working when a hosting provider's page cache interferes
with the password-reset cookie flow.

It is particularly useful on sites using [WPS Hide
Login](https://wordpress.org/plugins/wps-hide-login/) when the host's
cache does not correctly exclude the site's custom login URL.

## The problem

During a normal WordPress password reset, WordPress receives the reset
link's `login` and `key` values, stores them in a `wp-resetpass` cookie,
and redirects the browser.

If a host-level page cache interferes with that cookie or serves a
cached response during this process, the reset flow can fail. The user
may see an **invalid key** message even when using a valid link from the
password-reset email.

This can happen when a host treats the custom login URL provided by WPS
Hide Login like a cacheable page instead of recognizing it as part of
the login and password-reset flow.

## What this plugin does

The plugin works around the cookie dependency for password-reset
requests:

1.  **Recognizes password-reset requests.** It runs during WordPress's
    `login_init` action and only handles the `rp` and `resetpass`
    actions.
2.  **Reads credentials from the reset link.** When the request includes
    `login` and `key` in the URL, it captures those values and removes
    them from `$_GET` so WordPress does not follow its normal
    URL-to-cookie redirect flow.
3.  **Preserves credentials on form submission.** It adds the username
    and reset key to the reset form as hidden fields. On submission, it
    reads those values from `rp_login` and `rp_key`.
4.  **Reconstructs the reset cookie for the current request.** It
    populates WordPress's expected `wp-resetpass` cookie value in the
    request's `$_COOKIE` data, allowing the normal password-reset
    process to continue without depending on the browser receiving that
    cookie through the initial redirect.
5.  **Sends no-cache headers.** It calls `nocache_headers()` for the
    password-reset request.
6.  **Sets a restrictive referrer policy.** It adds a `no-referrer` meta
    tag to the login page to help prevent the reset URL and its key from
    being sent as a referrer to other pages.

The reset key is still validated by WordPress through its normal
`check_password_reset_key()` function. The plugin does not bypass that
validation or create its own password-reset tokens.

## Installation

This is a must-use plugin, so it should be installed in the site's MU
plugins directory:

``` text
wp-content/
└── mu-plugins/
    └── fix-password-reset.php
```

If the `mu-plugins` directory does not exist, create it.

Upload `fix-password-reset.php` to that directory. MU plugins are loaded
automatically by WordPress and do not need to be activated through the
Plugins screen.

## Testing

After installation:

1.  Confirm WPS Hide Login is enabled and the custom login URL is
    working.
2.  Request a password reset using the site's normal **Lost your
    password?** flow.
3.  Open the reset link from the email in a browser.
4.  Confirm the password-reset form loads instead of displaying an
    invalid-key error.
5.  Submit a new password and confirm that WordPress accepts it.
6.  Repeat the test with a newly generated reset link to verify the
    complete flow.

If the issue persists, check whether the host or CDN is still serving a
cached response for the custom login URL or its password-reset requests.

## Important notes and limitations

-   **This is a workaround, not a cache configuration.** The plugin does
    not create host-level cache exclusions, purge existing cache
    entries, or control a CDN or reverse proxy.
-   **The host should still exclude login and password-reset requests
    from caching.** Where possible, configure the host and any CDN to
    bypass cache for the custom login URL and password-reset requests,
    including requests using the `rp` and `resetpass` actions.
-   **The plugin only handles the reset flow.** It does not change
    general page caching behavior or affect ordinary front-end pages.
-   **WordPress remains responsible for token validation.** The reset
    key is passed through WordPress's normal password-reset validation.
-   **The referrer meta tag is a browser-side safeguard.** It is not a
    replacement for cache rules or other security controls.
-   **Test after WordPress, WPS Hide Login, or hosting-cache changes.**
    Changes to the login flow or cache behavior may affect whether this
    workaround is needed.

## Troubleshooting

If password-reset links still fail:

-   Verify the MU plugin is in `wp-content/mu-plugins/` and that the PHP
    file has no syntax errors.
-   Clear the host's page cache and any CDN cache.
-   Ask the host to confirm that the custom login URL and password-reset
    requests bypass page caching.
-   Test with a newly generated password-reset email rather than reusing
    an older link.
-   Check whether a security plugin, redirect rule, or other login
    customization is interfering with the reset request.

## Compatibility

Designed for WordPress sites using WPS Hide Login where host-level page
caching disrupts the standard password-reset cookie flow.

The plugin has no settings screen and requires no activation step.
Compatibility should be verified on the site's specific WordPress,
login-plugin, and hosting configuration.
