#!/usr/bin/bash

RELEASE="/var/www/revenue.magellano.ai"

# copy .env
echo -e "Copying the ENV file"
aws secretsmanager get-secret-value \
	--secret-id "revenue/prod" \
	--query SecretString \
	--version-stage AWSCURRENT \
	--region eu-west-1 \
	--output text | \
	jq -r 'to_entries|map("\(.key)=\"\(.value|tostring)\"")|.[]' > "${RELEASE}/.env" || {
	    echo -e "ERROR creating .env from secret"
	    exit 1
	}

chown apache:apache "${RELEASE}/.env"

#sudo -u apache mkdir "${RELEASE}/storage/logs" || true

echo -e "Artisan: migrate"
sudo -u apache /usr/bin/php ${RELEASE}/artisan migrate --force
echo -e "Artisan: clear cache"
sudo -u apache /usr/bin/php ${RELEASE}/artisan cache:clear
sudo -u apache /usr/bin/php ${RELEASE}/artisan auth:clear-resets
echo -e "Artisan: config"
sudo -u apache /usr/bin/php ${RELEASE}/artisan config:clear
echo -e "Artisan: config:clear"
sudo -u apache /usr/bin/php ${RELEASE}/artisan optimize

#Remove the .git folder
echo -e "Removing the .git folder"
rm -rf "${RELEASE}/.git"

echo -e "Restart HTTPD & PHP-FPM"
systemctl restart httpd php-fpm || true

echo -e "All Done"