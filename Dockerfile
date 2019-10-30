FROM php:zts-alpine3.9
MAINTAINER Loc Tran <loc@route42.nl>

# Config
COPY src /usr/local/nsbot
COPY run.sh run.sh

RUN chmod +x /run.sh
RUN mkdir "/usr/local/nsbot/cache"
RUN ls -lah

CMD ["/run.sh"]

WORKDIR /usr/local/nsbot
CMD [ "php", "./index.php" ]