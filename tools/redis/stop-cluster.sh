for port in {9000..9005}
do
redis-cli -c -p $port shutdown
done;
