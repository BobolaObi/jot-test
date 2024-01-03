FROM gitlab.auxiliumgroup.com:4567/devops/docker/nginx_phpfpm:master

ADD version /version

RUN rm /etc/nginx/sites-enabled/*
ADD nginx.conf /etc/nginx/sites-enabled/nginx.conf

ADD ../ /data/www/forms.datalynk.ca
WORKDIR /data/www/forms.datalynk.ca

RUN ln -s cache/uploads uploads

RUN cp ConfigsClass.php-THISISTHECONFIGFILE lib/ConfigsClass.php
