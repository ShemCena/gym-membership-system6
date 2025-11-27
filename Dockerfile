FROM php:8.3-apache

# Install MySQL PDO extension for your database
RUN docker-php-ext-install pdo pdo_mysql

# Copy your gym project into the container
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html/gym

# Enable Apache rewrite module for MVC routing
RUN a2enmod rewrite

# Set permissions for uploads
RUN chown -R www-data:www-data /var/www/html/gym/uploads

EXPOSE 80

CMD ["apache2-foreground"]
