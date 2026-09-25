FROM php:8.3-apache

# Ekstensi yang dibutuhkan project ini (koneksi SQLite lewat PDO)
RUN apt-get update \
    && apt-get install -y --no-install-recommends libsqlite3-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo_sqlite

RUN a2dismod mpm_event mpm_worker 2>/dev/null; a2enmod mpm_prefork

# Aktifkan module Apache yang dipakai (.htaccess di folder database/ butuh ini)
RUN a2enmod rewrite headers

# Konfigurasi virtual host: DocumentRoot ke root project (index.php di sini)
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
COPY . /var/www/html

# Folder database/ harus bisa ditulis oleh Apache (www-data) supaya file
# tts_aceh.sqlite bisa dibuat otomatis saat pertama kali diakses.
RUN chown -R www-data:www-data /var/www/html/database

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]
