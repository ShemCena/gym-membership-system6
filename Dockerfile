FROM php:8.3-apache

# Install MySQL PDO extension for your database
RUN docker-php-ext-install pdo pdo_mysql

# Copy your gym project into the container
COPY . /var/www/html/

# Create uploads directory if it doesn't exist
RUN mkdir -p /var/www/html/gym/uploads

# Set permissions for uploads (NOW it exists)
RUN chown -R www-data:www-data /var/www/html/gym/uploads

# Set working directory
WORKDIR /var/www/html/gym

# Enable Apache rewrite module for MVC routing
RUN a2enmod rewrite

EXPOSE 80

CMD ["apache2-foreground"]
