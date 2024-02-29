FROM gitlab.auxiliumgroup.com:4567/devops/docker/nginx_phpfpm:master

ADD docker/version /version

RUN rm /etc/nginx/sites-enabled/*
ADD docker/nginx.conf /etc/nginx/sites-enabled/nginx.conf

ADD . /data/www/jot.datalynk.ca
WORKDIR /data/www/jot.datalynk.ca

RUN ln -s cache/uploads uploads

RUN /data/www/jot.datalynk.ca/composer.phar install -n


#setting permissions
RUN chown -R www-data:www-data /data/www/jot.datalynk.ca

RUN find /data/www/jot.datalynk.ca -type d -exec chmod 755 {} \; && \
    find /data/www/jot.datalynk.ca -type f -exec chmod 644 {} \;