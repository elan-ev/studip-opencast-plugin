#! /bin/bash

now=`date`
echo "Current date: $now"

echo clear uploaded files
for DIR in archiv assets_cache extern_config media_cache upload_doc
do
    rm -rf {{ studip_base_dir }}/data/$DIR/*
done

echo make sure file permissions for Stud.IP are correct
chown -R root: {{ studip_base_dir }}
chown -R root: {{ studip_base_dir }}/.git
chmod -R 755 {{ studip_base_dir }}
chmod -R 755 {{ studip_base_dir }}/.git
chown -R www-data:www-data {{ studip_base_dir }}/data


echo clear database
mysql -u {{ studip_db.user }} --password={{ studip_db.password }} -e "DROP DATABASE {{ studip_db.name }}"
mysql -u {{ studip_db.user }} --password={{ studip_db.password }} -e "CREATE DATABASE {{ studip_db.name }} CHARACTER SET  utf8mb4  COLLATE utf8mb4_german2_ci"

echo update Stud.IP
cd {{ studip_base_dir }} && git stash && git stash drop && git pull

echo install composer
curl https://getcomposer.org/download/latest-stable/composer.phar -o /usr/local/bin/composer
chmod +x /usr/local/bin/composer
export COMPOSER_ALLOW_SUPERUSER=1
export PATH=$PATH:/usr/local/bin

echo install nvm + npm
# Install npm
NVM_DIR='/root/.nvm'
mkdir $NVM_DIR
NODE_VERSION='{{ node_version }}'

# Install nvm with node and npm
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.3/install.sh | bash
/bin/bash $NVM_DIR/nvm.sh

export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"  # This loads nvm
[ -s "$NVM_DIR/bash_completion" ] && \. "$NVM_DIR/bash_completion"  # This loads nvm bash_completion

nvm install {{ node_version }} \
nvm alias default {{ node_version }} \
nvm use default

echo generate assets
cd {{ studip_base_dir }} && npm install && make

echo install database
mysql -u {{ studip_db.user }} --password={{ studip_db.password }} {{ studip_db.name }} < {{ studip_base_dir }}/db/studip.sql
mysql -u {{ studip_db.user }} --password={{ studip_db.password }} {{ studip_db.name }} < {{ studip_base_dir }}/db/studip_root_user.sql
mysql -u {{ studip_db.user }} --password={{ studip_db.password }} {{ studip_db.name }} < {{ studip_base_dir }}/db/studip_default_data.sql
mysql -u {{ studip_db.user }} --password={{ studip_db.password }} {{ studip_db.name }} < {{ studip_base_dir }}/db/studip_resources_default_data.sql
mysql -u {{ studip_db.user }} --password={{ studip_db.password }} {{ studip_db.name }} < {{ studip_base_dir }}/db/studip_demo_data.sql
mysql -u {{ studip_db.user }} --password={{ studip_db.password }} {{ studip_db.name }} < {{ studip_base_dir }}/db/studip_resources_demo_data.sql

echo run migrations
cd {{ studip_base_dir }} && php ./cli/studip migrate

echo install most recent version of Stud.IP Opencast plugin
rm -rf {{ studip_base_dir }}/public/plugins_packages/elan-ev/OpenCast
git clone https://github.com/elan-ev/studip-opencast-plugin.git {{ studip_base_dir }}/public/plugins_packages/elan-ev/OpenCast

echo build all assets for Stud.IP OCP
cd {{ studip_base_dir }}/public/plugins_packages/elan-ev/OpenCast && npm run build

echo register and activate Stud.IP OCP
cd {{ studip_base_dir }} && php ./cli/studip plugin:register public/plugins_packages/elan-ev/OpenCast
cd {{ studip_base_dir }} && php ./cli/studip plugin:activate OpenCast

echo run Stud.IP OCP migrations
cd {{ studip_base_dir }} && php ./cli/studip plugin:migrate OpenCast

echo configure Stud.IP OCP
mysql -u {{ studip_db.user }} --password={{ studip_db.password }} {{ studip_db.name }} < {{ studip_cronjob_dir }}/oc.sql

echo add info about test credentials
echo "#loginbox::after { content: \"Testzugänge:\aroot@studip / testing\atest_admin / testing\atest_dozent / testing\atest_autor / testing\atest_user / testing\"; position: absolute; left: 600px; top: 200px; max-width: 300px; line-height: 1.2; border: 1px solid silver; border-radius: 5px; padding: 15px; background-color: white; white-space: pre-wrap; }" >> /usr/local/studip/main/public/assets/stylesheets/vue.js.css
