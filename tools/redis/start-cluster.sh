#start redis
for port in {9000..9005}
do src/redis-server $port/redis-cluster.conf
done;