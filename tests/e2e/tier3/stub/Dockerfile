# Serves the API stub over TLS as spectrocoin.com, so the plugin reaches it
# through its own shipped Config constants rather than a test-only override.
FROM php:8.3-apache

RUN a2enmod ssl rewrite

COPY site.conf /etc/apache2/sites-available/000-default.conf
COPY index.php /var/www/html/index.php

# Everything is routed through index.php, which dispatches on the path.
RUN printf '%s\n' \
      '<Directory /var/www/html>' \
      '  RewriteEngine On' \
      '  RewriteCond %{REQUEST_FILENAME} !-f' \
      '  RewriteRule ^ /index.php [L]' \
      '</Directory>' > /etc/apache2/conf-available/stub.conf \
    && a2enconf stub

EXPOSE 443
