echo 'Reloading...'
pid=$(pidof swoole_server_mPHP)
kill -USR1 "$pid"
echo 'Reloaded'
