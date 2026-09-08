#!/bin/bash
set -euo pipefail

# you should configure this stuff
PUSH_REPOSITORY=origin
REMOTE_HOST=bitfighter.org
REMOTE_DIRECTORY=/var/www/html/pleiades

# find base path
project_root="$(cd "$(dirname "$0")" && pwd)"

UPDATE_SCHEMA=false
if [ "${1:-}" = "-s" ]; then
	echo 'updating schema'
	UPDATE_SCHEMA=true
fi

if [ "$UPDATE_SCHEMA" = true ]; then
	# make schema, add a commit
	echo 'Creating schema dump...'
	"$project_root/app/Console/cake" schema generate --snapshot
	git add .
	git commit -am 'updated schema'
fi

git push "$PUSH_REPOSITORY"

ssh_commands="cd $REMOTE_DIRECTORY && git pull && git submodule init && git submodule update && cd app/tmp/cache"

if [ "$UPDATE_SCHEMA" = true ]; then
	ssh_commands="$ssh_commands && ./app/Console/cake schema update --dry-run"
fi

ssh "$REMOTE_HOST" "$ssh_commands"
