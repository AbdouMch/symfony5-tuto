#!/bin/bash

if [ -e composer.json ]; then \
      echo "installing php dependencies"; \
      composer install --no-progress; \
      composer require friendsofphp/php-cs-fixer; \
fi

if [ -e package.json ]; then \
      echo "installing js dependencies"; \
      yarn install; \
      yarn run dev; \
fi
