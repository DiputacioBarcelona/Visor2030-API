#!/bin/sh
set -e
ENVIRONMENT="dev"

POSITIONAL=()
while [[ $# -gt 0 ]]
do
key="$1"

case $key in
    -env|--environment)
      if [ "$2" = "prod" ]; then
          ENVIRONMENT="prod"
      fi
    shift # past argument
    shift # past value
    ;;
    *)
    printf "\nComproveu que heu escrit els paràmetres correctament.\nUsage: -env|--environment [dev|test|prod]\n";
    exit 1;
esac
done
set -- "${POSITIONAL[@]}" # restore positional parameters
printf -- "\n### Environment: %s" "${ENVIRONMENT}";

printf -- '\n### Composer ###\n';
if [ "${ENVIRONMENT}" = 'prod' ]; then
  COMPOSER_MEMORY_LIMIT=-1 composer install --no-interaction --ansi
else
  composer clearcache
  COMPOSER_MEMORY_LIMIT=-1 composer update --no-interaction --ansi
fi

printf -- '\n### Yarn install ###\n';
yarn install
npm rebuild node-sass

printf -- '\n### Webpack encore ###\n';
if [ "${ENVIRONMENT}" = 'prod' ]; then
  yarn encore production
else
  yarn encore dev
fi

printf -- '\n### Rebuild locales ###\n';
php bin/console translation:extract --prefix="" --force ca

printf -- '\n### Clear cache ###\n';
if [ "${ENVIRONMENT}" = 'prod' ]; then
  php bin/console cache:clear --no-warmup -e prod
else
  php bin/console cache:clear
fi

printf -- '\n### Firing Tests ###\n';
if [ "${ENVIRONMENT}" = 'dev' ]; then
  bin/console cache:clear --env=test
  php bin/phpunit
fi

if [ "${ENVIRONMENT}" = 'dev' ]; then
  printf -- '\n### Unused translations - Delete? ###\n';
  php bin/console debug:translation ca --domain=messages --only-unused
fi
