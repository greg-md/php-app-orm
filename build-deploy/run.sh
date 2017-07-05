#!/usr/bin/env bash

# Migrations
echo "Run migrations..."

vendor/bin/phinx migrate
