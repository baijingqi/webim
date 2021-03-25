# shellcheck disable=SC1113
#/bin/bash
# shellcheck disable=SC2046
# shellcheck disable=SC2154
echo "Reloading..."
cmd=$(pidof reload_chat_server);
kill -USR1 $cmd;
echo "Reloaded"
