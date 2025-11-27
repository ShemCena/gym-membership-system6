FROM php:8.3-apache

# Install MySQL PDO extension for your database
RUN docker-php-ext-install pdo pdo_mysql

# Copy your gym project into the container
COPY . /var/www/html/

# Create uploads directory if it doesn't exist
RUN mkdir -p /var/www/html/gym/uploads

# Set permissions for uploads
RUN chown -R www-data:www-data /var/www/html/gym/uploads

# Set working directory
WORKDIR /var/www/html/gym

# Enable Apache rewrite module for MVC routing
RUN a2enmod rewrite

# FIX 1: Set DocumentRoot to /var/www/html/gym so Apache serves your code
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/gym|g' /etc/apache2/sites-available/000-default.conf

# FIX 2: Add index.php to DirectoryIndex so Apache looks for it first
RUN sed -i 's|DirectoryIndex index.html|DirectoryIndex index.php index.html|g' /etc/apache2/mods-enabled/dir.conf

# FIX 3: Suppress ServerName warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

EXPOSE 80

CMD ["apache2-foreground"]
