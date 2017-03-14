echo "Reloading..."
cmd=$(pidof swoole_im_master)
kill -USR1 "$cmd"
echo "Reloaded"