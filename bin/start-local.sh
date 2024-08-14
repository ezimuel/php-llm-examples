#!/bin/sh
# Run Elasticsearch and Kibana on Linux for local testing
# Note: do not use this script in a production environment

set -eu

echo
echo '  ______ _           _   _      '
echo ' |  ____| |         | | (_)     '
echo ' | |__  | | __ _ ___| |_ _  ___ '
echo ' |  __| | |/ _` / __| __| |/ __|'
echo ' | |____| | (_| \__ \ |_| | (__ '
echo ' |______|_|\__,_|___/\__|_|\___|'
echo '--------------------------------------------------------'
echo 'Run Elasticsearch and Kibana for local testing'
echo 'Note: do not use this script in a production environment'
echo '--------------------------------------------------------'

# Trap ctrl-c
trap ctrl_c INT

ctrl_c() { cleanup; }

# Check if a command exists
available() { command -v $1 >/dev/null; }

# Revert the status (remove generated files)
cleanup() { rm docker-compose.yml .env >/dev/null 2>&1; }

# Generates a random password with letters and numbers
# You can pass the size of the password as first parameter (default is 8 characters)
random_password() {
  local LENGTH="${1:-8}"
  echo $(LC_ALL=C tr -dc 'A-Za-z0-9' < /dev/urandom | head -c ${LENGTH})
}

# Returns the latest Elasticsearch tag version
get_latest_version() {
  local version=$(curl -s "https://api.github.com/repos/elastic/elasticsearch/tags" | grep -m 1 '"name"' | grep -Eo '[0-9.]+') 
  echo $version
}

# Check the requirements
if ! available "docker"; then
  echo "Error: docker command is required"
  echo "You can install it from https://docs.docker.com/engine/install/."
  exit 1
fi
if ! available "curl"; then
  echo "Error: curl command is required"
  echo "You can install it from https://curl.se/download.html."
  exit 1
fi
if ! available "grep"; then
  echo "Error: grep command is required"
  echo "You can install it from https://www.gnu.org/software/grep/."
  exit 1
fi
# Check for "docker compose"
set +e
docker compose >/dev/null 2>&1
if [ $? -ne 0 ]; then
    echo "Error: docker compose is required"
    echo "You can install it from https://docs.docker.com/compose/install/"
    exit 1
fi
set -e

if [ -f "docker-compose.yml" ]; then
  echo "Error: a docker-compose.yml already exists. Please move to another folder or remove the file."
  exit 1
fi
if [ -f ".env" ]; then
  echo "Error: a .env file already exists. Please move to another folder or remove the file."
  exit 1
fi

# Generate random passwords
es_password=$(random_password)
kibana_password=$(random_password)
es_version=$(get_latest_version)
kibana_encryption_key=$(random_password 32)

# Create the .env file
cat > .env <<- EOM
ES_LOCAL_VERSION="$es_version"
ES_LOCAL_CONTAINER_NAME="es-local-dev"
ES_LOCAL_DOCKER_NETWORK="elastic-net"
ES_LOCAL_PASSWORD="$es_password"
ES_LOCAL_PORT="9200"
ES_HEAP_INIT="128m"
ES_HEAP_MAX="2g"
KIBANA_LOCAL_CONTAINER_NAME="kibana-local-dev"
KIBANA_LOCAL_PORT="5601"
KIBANA_LOCAL_PASSWORD="$kibana_password"
KIBANA_ENCRYPTION_KEY="$kibana_encryption_key"
EOM

# Equivalent of source command in sh
set +e
. ./.env
if [ $? -ne 0 ]; then
  echo "Error: the .env file is not valid"
  cleanup
  exit 1
fi
set -e

echo "Set up of Elasticsearch and Kibana v${ES_LOCAL_VERSION}..."
echo "- Generated random passwords"
echo "- Created a .env file with the settings"

# Create the docker-compose.yml file
cat >docker-compose.yml <<'EOL'
services:
  es01:
    image: docker.elastic.co/elasticsearch/elasticsearch:${ES_LOCAL_VERSION}
    container_name: ${ES_LOCAL_CONTAINER_NAME}
    volumes:
      - dev-es01:/usr/share/elasticsearch/data
    ports:
      - 127.0.0.1:${ES_LOCAL_PORT}:9200
    environment:
      - discovery.type=single-node
      - ELASTIC_PASSWORD=${ES_LOCAL_PASSWORD}
      - xpack.security.enabled=true
      - xpack.security.http.ssl.enabled=false
      - xpack.license.self_generated.type=trial
      - xpack.ml.use_auto_machine_memory_percent=true
      - ES_JAVA_OPTS=-Xms${ES_HEAP_INIT} -Xmx${ES_HEAP_MAX}
    ulimits:
      memlock:
        soft: -1
        hard: -1
    healthcheck:
      test:
        [
          "CMD-SHELL",
          "curl --output /dev/null --silent --head --fail -u elastic:${ES_LOCAL_PASSWORD} http://es01:${ES_LOCAL_PORT}",
        ]
      interval: 5s
      timeout: 5s
      retries: 10

  kibana_password:
    depends_on:
      es01:
        condition: service_healthy
    image: docker.elastic.co/elasticsearch/elasticsearch:${ES_LOCAL_VERSION}
    restart: no
    command: >
      bash -c '
        echo "Setup the kibana_system password";
        curl -u "elastic:${ES_LOCAL_PASSWORD}" -X POST http://es01:${ES_LOCAL_PORT}/_security/user/kibana_system/_password -d "{\"password\":\"'${KIBANA_LOCAL_PASSWORD}'\"}" -H "Content-Type: application/json";
      '

  kibana:
    depends_on:
      kibana_password:
        condition: service_completed_successfully
    image: docker.elastic.co/kibana/kibana:${ES_LOCAL_VERSION}
    container_name: ${KIBANA_LOCAL_CONTAINER_NAME}
    volumes:
      - dev-kibana:/usr/share/kibana/data
    ports:
      - 127.0.0.1:${KIBANA_LOCAL_PORT}:5601
    environment:
      - SERVERNAME=kibana
      - ELASTICSEARCH_HOSTS=http://es01:9200
      - ELASTICSEARCH_USERNAME=kibana_system
      - ELASTICSEARCH_PASSWORD=${KIBANA_LOCAL_PASSWORD}
      - XPACK_ENCRYPTEDSAVEDOBJECTS_ENCRYPTIONKEY=${KIBANA_ENCRYPTION_KEY}
    healthcheck:
      test:
        [
          "CMD-SHELL",
          "curl -s -I http://kibana:5601 | grep -q 'HTTP/1.1 302 Found'",
        ]
      interval: 10s
      timeout: 10s
      retries: 120

volumes:
  dev-es01:
  dev-kibana:
EOL

echo "- Created a docker-compose.yml file"

# Execute docker compose
echo "- Running docker compose up --wait"
set +e
docker compose up --wait
if [ $? -ne 0 ]; then
  echo "Error: the 'docker compose up --wait' command failed!"
  cleanup
  exit 1
fi
set -e

# Success
echo "Congrats, Elasticsearch and Kibana successfully installed!"
echo 
echo "Open the browser at http://localhost:${KIBANA_LOCAL_PORT}"
echo "Use 'elastic' as username and '${ES_LOCAL_PASSWORD}' as password."
echo "To connect to Elasticsearch use the URL: http://elastic:${ES_LOCAL_PASSWORD}@localhost:${ES_LOCAL_PORT}"
echo
echo "To stop the service: docker compose stop"
echo "To run it again: docker compose up --wait"